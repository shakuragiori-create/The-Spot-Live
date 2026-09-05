<?php

if(!defined('SOFTACULOUS')){
	die('Hacking Attempt');
}

function ai(){
	global $user, $globals, $l, $theme, $softpanel, $error, $insid, $software, $soft;
	global $edited, $settings, $iscripts, $catwise, $scripts, $noheader;
	global $softpath, $custom_path;
	
	if(version_compare(PHP_VERSION, '7.1', '<')){
		echo 'PHP 7.1 required to use this feature';
		die();
	}
	
	if(empty($globals['lictype']) || !empty($globals['licexpired'])){
		echo 'An active '.APP.' license is required to use this feature!';
		die();
	}

	$insid = GET('insid', '');
	$custom_path = GET('path', '');
	$project_id = GET('project_id', '');

	$username = $softpanel->user['name'];
	include_once(__DIR__.'/ai_launcher.php');
	require_once(__DIR__ . '/core/class_ai_file_handler.php');

	$api_sp = '';
	if(!empty($project_id)){
		ai_php_init_classes();
		require_once(__DIR__ . '/core/class_project.php');
		$_proj = AIProject::load($username, $project_id);
		if($_proj && !empty($_proj['path'])) $api_sp = $_proj['path'];
	}elseif(!empty($insid) && !empty($user['ins'][$insid])){
		$api_sp = $user['ins'][$insid]['softpath'];
	}elseif(!empty($custom_path)){
		$_hd = !empty($softpanel->user['homedir']) ? $softpanel->user['homedir'] : ai_get_homedir($username);
		$_cp = trim($custom_path);
		if(strpos($_cp, $_hd) === 0) $_cp = substr($_cp, strlen($_hd) + 1);
		$api_sp = cleanpath($_hd . '/' . $_cp);
	}

	if(optGET('ai_php_api')){
		ai_handle_php_api($username, $api_sp);
		die();
	}

	if(optGET('ai_chat_stream')){
		ai_php_init_classes();
		$content = !empty($_POST['content']) ? $_POST['content'] : '';
		$options = array();
		if(!empty($_POST['conversation_id'])) $options['conversation_id'] = $_POST['conversation_id'];
		if(!empty($_POST['attachments'])) $options['attachments'] = json_decode($_POST['attachments'], true) ? json_decode($_POST['attachments'], true) : array();
		ai_php_send_prompt_stream($username, $api_sp, $content, $options);
		die();
	}

	if(empty($insid) && empty($custom_path) && empty($project_id)){
		$theme['init_theme'] = 'ai';
		$theme['init_theme_name'] = 'AI Assistant';
		$theme['init_theme_func'] = array('ai_theme');
		$theme['call_theme_func'] = 'ai_theme';
		return true;
	}
	
	if(!empty($project_id)){
		include_once(__DIR__.'/ai_launcher.php');
		ai_php_init_classes();
		require_once(__DIR__ . '/core/class_project.php');
		$project = AIProject::load($softpanel->user['name'], $project_id);
		if($project && !empty($project['path'])){
			$custom_path = $project['path'];
			$insid = !empty($project['insid']) ? $project['insid'] : '';
		}
	}
	
	if(!empty($insid)){
		if(empty($user['ins'][$insid])){
			reporterror(__('Invalid Installation'), __('The installation ID is invalid or does not exist'));
			return false;
		}

		$data = $user['ins'][$insid];
		$soft = get_sid_by_version($data['ver'], $data['sid']);
		$software = !empty($iscripts[$soft]) ? $iscripts[$soft] : array('name' => 'Software');
		$softpath = $data['softpath'];
	}else{
		$home_dir = $softpanel->user['homedir'];
		$custom_path = trim($custom_path);
		if(empty($custom_path)){
			$custom_path = $home_dir;
		}
		
		if(strpos($custom_path, './') !== false || strpos($custom_path, '../') !== false || strpos($custom_path, '/..') !== false || strpos($custom_path, '..') === 0){
			reporterror(__('Invalid Path'), __('Path traversal is not allowed'));
			return false;
		}
		if(strpos($custom_path, $home_dir) === 0){
			$custom_path = substr($custom_path, strlen($home_dir) + 1);
		}
		$softpath = cleanpath($home_dir . '/' . $custom_path);
		if($softpath !== $home_dir && strpos($softpath, $home_dir . '/') !== 0){
			reporterror(__('Invalid Path'), __('The path must be within your home directory'));
			return false;
		}
		if(empty($softpath) || !@is_dir($softpath)){
			reporterror(__('Invalid Path'), __('The directory path is invalid or does not exist.'));
			return false;
		}
		$software = array('name' => basename($softpath));
		$insid = '';
	}

	$username = $softpanel->user['name'];

	include_once(__DIR__.'/ai_launcher.php');

	if(optGET('ai_chat_stream')){
		ai_php_init_classes();
		$content = !empty($_POST['content']) ? $_POST['content'] : '';
		$options = array();
		if(!empty($_POST['conversation_id'])) $options['conversation_id'] = $_POST['conversation_id'];
		if(!empty($_POST['attachments'])) $options['attachments'] = json_decode($_POST['attachments'], true) ? json_decode($_POST['attachments'], true) : array();
		ai_php_send_prompt_stream($username, $softpath, $content, $options);
		die();
	}

	$theme['init_theme'] = 'ai';
	$theme['init_theme_name'] = 'AI Assistant';
	$theme['init_theme_func'] = array('ai_theme');
	$theme['call_theme_func'] = 'ai_theme';
}

function ai_handle_php_api($username, $softpath){
	global $globals, $user, $softpanel, $iscripts;

	ai_php_init_classes();
	require_once(__DIR__ . '/core/class_project.php');

	$action = optGET('ai_php_api');
	header('Content-Type: application/json; charset='.$globals['charset']);

	$user_home_dir = !empty($softpanel->user['homedir']) ? $softpanel->user['homedir'] : ai_get_homedir($username);
	
	$needs_path = array('status', 'start', 'stop', 'file_tree', 'read_file', 'write_file', 'search', 'snapshot', 'snapshots', 'restore', 'diff', 'project_info', 'conversations', 'conversation', 'new_session', 'switch_conversation', 'delete_conversation', 'clear', 'set_mode', 'regenerate', 'edit_message', 'auto_title', 'abort');
	if(empty($softpath) && in_array($action, $needs_path)){
		echo json_encode(array('error' => 'No project path set'));
		die();
	}

	switch($action){
		case 'projects_list':
			$projects = AIProject::list_all($username);
			echo json_encode($projects);
			break;

		case 'projects_get':
			$project_id = isset($_GET['project_id']) ? $_GET['project_id'] : '';
			if(empty($project_id)){
				echo json_encode(array('error' => 'Project ID is required'));
				break;
			}
			$project = AIProject::load($username, $project_id);
			if(!$project){
				echo json_encode(array('error' => 'Project not found'));
				break;
			}
			echo json_encode($project);
			break;

		case 'projects_create':
			$path = isset($_POST['path']) ? $_POST['path'] : '';
			$name = isset($_POST['name']) ? $_POST['name'] : '';
			$type = isset($_POST['type']) ? $_POST['type'] : 'custom';
			$insid = isset($_POST['insid']) ? $_POST['insid'] : '';

			$home_dir = $softpanel->user['homedir'];

			if(empty($path)){
				$path = $home_dir;
			}

			$path = trim($path);
			if(strpos($path, './') !== false || strpos($path, '../') !== false || strpos($path, '/..') !== false || strpos($path, '..') === 0){
				echo json_encode(array('error' => 'Path traversal is not allowed'));
				break;
			}
			if(strpos($path, $home_dir) === 0){
				$path = substr($path, strlen($home_dir) + 1);
			}
			$full_path = cleanpath($home_dir . '/' . $path);
			if($full_path !== $home_dir && strpos($full_path, $home_dir . '/') !== 0){
				echo json_encode(array('error' => 'The path must be within your home directory'));
				break;
			}
			if(empty($full_path) || !@is_dir($full_path)){
				echo json_encode(array('error' => 'The directory path is invalid or does not exist'));
				break;
			}

			if(!empty($insid)){
				$project_id = AIProject::create_from_installation($username, $insid, $full_path, $name, $type);
			} else {
				$project_id = AIProject::create_from_path($username, $full_path, $name);
			}
			$project = AIProject::load($username, $project_id);
			echo json_encode($project);
			break;

		case 'projects_update':
			$project_id = isset($_POST['project_id']) ? $_POST['project_id'] : '';
			$name = isset($_POST['name']) ? $_POST['name'] : '';
			if(empty($project_id)){
				echo json_encode(array('error' => 'Project ID is required'));
				break;
			}
			$data = array();
			if(!empty($name)) $data['name'] = $name;
			AIProject::update($username, $project_id, $data);
			echo json_encode(AIProject::load($username, $project_id));
			break;

		case 'projects_delete':
			$project_id = isset($_POST['project_id']) ? $_POST['project_id'] : '';
			if(empty($project_id)){
				echo json_encode(array('error' => 'Project ID is required'));
				break;
			}
			AIProject::delete($username, $project_id);
			echo json_encode(array('success' => true));
			break;

		case 'projects_wordpress':
			$installations = array();
			if(!empty($user['ins'])){
				foreach($user['ins'] as $insid => $idata){
					$soft = get_sid_by_version($idata['ver'], $idata['sid']);
					$software = !empty($iscripts[$soft]) ? $iscripts[$soft] : array('name' => 'Software');
					$installations[] = array(
						'insid' => $insid,
						'name' => $software['name'],
						'path' => $idata['softpath'],
						'url' => !empty($idata['softurl']) ? $idata['softurl'] : ''
					);
				}
			}
			echo json_encode($installations);
			break;

		case 'status':
			$session = AISession::load($username, $softpath);
			$settings = new AISettings($username);
			echo json_encode(array(
				'session' => $session,
				'providers' => $settings->get_all_providers()
			));
			break;

		case 'start':
			$provider = isset($_POST['provider']) ? $_POST['provider'] : 'anthropic';
			$model = isset($_POST['model']) ? $_POST['model'] : '';
			$mode = isset($_POST['mode']) ? $_POST['mode'] : 'build';
			$session = AISession::load($username, $softpath);
			if(empty($session)) $session = array();
			$session['provider'] = $provider;
			$session['model'] = $model;
			$session['mode'] = $mode;
			AISession::save($username, $softpath, $session);
			echo json_encode(array('success' => true));
			break;

		case 'stop':
			AISession::delete($username, $softpath);
			echo json_encode(array('success' => true));
			break;

		case 'providers':
			$settings = new AISettings($username);
			echo json_encode($settings->get_all_providers());
			break;

		case 'models':
			$settings = new AISettings($username);
			echo json_encode($settings->get_all_models());
			break;

		case 'conversation':
			$conv_id = isset($_GET['conversation_id']) ? $_GET['conversation_id'] : (isset($_POST['conversation_id']) ? $_POST['conversation_id'] : AISession::get_active_conversation_id($username, $softpath));
			$conv_dir = AISession::get_conversations_dir($username, $softpath);
			$conv_file = $conv_dir . '/' . $conv_id . '.json.php';
			$conv = null;
			if(file_exists($conv_file)){
				$conv = AIConversation::load($conv_file);
			}
			if(!$conv){
				require_once(__DIR__ . '/core/class_ai_file_handler.php');
				foreach(AIFileHandler::list_files($conv_dir, 'conv_*.json.php') as $f){
					$d = AIFileHandler::read($f);
					if(!empty($d['id']) && $d['id'] === $conv_id){
						$conv = AIConversation::load($f);
						break;
					}
				}
			}
			echo json_encode($conv ? $conv->get_all() : array('messages' => array()));
			break;

		case 'conversations':
			$conv_dir = AISession::get_conversations_dir($username, $softpath);
			echo json_encode(AIConversation::list_for_project($conv_dir));
			break;

		case 'new_session':
			$conv_id = 'conv_' . substr(md5(uniqid(mt_rand(), true)), 0, 12);
			AISession::set_active_conversation($username, $softpath, $conv_id);
			$conv_dir = AISession::get_conversations_dir($username, $softpath);
			AIConversation::create($conv_dir . '/' . $conv_id . '.json.php', $softpath, $conv_id);
			echo json_encode(array('success' => true, 'id' => $conv_id));
			break;

		case 'switch_conversation':
			$conv_id = isset($_POST['conversation_id']) ? $_POST['conversation_id'] : '';
			if(!empty($conv_id)){
				AISession::set_active_conversation($username, $softpath, $conv_id);
			}
			echo json_encode(array('success' => true));
			break;

		case 'delete_conversation':
			$conv_id = isset($_POST['conversation_id']) ? $_POST['conversation_id'] : '';
			if(!empty($conv_id)){
				$conv_dir = AISession::get_conversations_dir($username, $softpath);
				$conv_file = $conv_dir . '/' . $conv_id . '.json.php';
				if(file_exists($conv_file)){
					AIConversation::delete($conv_file);
				}else{
					foreach(AIFileHandler::list_files($conv_dir, 'conv_*.json.php') as $f){
						$d = AIFileHandler::read($f);
						if(!empty($d['id']) && $d['id'] === $conv_id){
							AIConversation::delete($f);
							break;
						}
					}
				}
				$session = AISession::load($username, $softpath);
				$session = $session ? $session : array();
				if(!empty($session['active_conversation']) && $session['active_conversation'] === $conv_id){
					unset($session['active_conversation']);
					AISession::save($username, $softpath, $session);
				}
			}
			echo json_encode(array('success' => true));
			break;

		case 'clear':
			$conv_id = AISession::get_active_conversation_id($username, $softpath);
			$conv_dir = AISession::get_conversations_dir($username, $softpath);
			$conv = AIConversation::load($conv_dir . '/' . $conv_id . '.json.php');
			if($conv){
				$conv->clear();
				$conv->save();
			}
			echo json_encode(array('success' => true));
			break;

		case 'set_mode':
			$mode = isset($_POST['mode']) ? $_POST['mode'] : 'build';
			$session = AISession::load($username, $softpath);
			$session = $session ? $session : array();
			$session['mode'] = $mode;
			AISession::save($username, $softpath, $session);
			$conv_id = AISession::get_active_conversation_id($username, $softpath);
			$conv_dir = AISession::get_conversations_dir($username, $softpath);
			$conv = AIConversation::load($conv_dir . '/' . $conv_id . '.json.php');
			if($conv){
				$conv->set_mode($mode);
				$conv->save();
			}
			echo json_encode(array('success' => true));
			break;

		case 'file_tree':
			$path = isset($_GET['path']) ? $_GET['path'] : '/';
			$depth = intval(isset($_GET['depth']) ? $_GET['depth'] : 3);
			$fm = new AIFileManager($softpath, $user_home_dir);
			$result = $fm->list_directory($path, $depth);
			echo json_encode($result);
			break;

		case 'read_file':
			$path = isset($_GET['path']) ? $_GET['path'] : '';
			$fm = new AIFileManager($softpath, $user_home_dir);
			echo json_encode($fm->read_file($path));
			break;

		case 'write_file':
			$path = isset($_POST['path']) ? $_POST['path'] : '';
			$content = isset($_POST['content']) ? $_POST['content'] : '';
			$fm = new AIFileManager($softpath, $user_home_dir);
			echo json_encode($fm->write_file($path, $content, true));
			break;

		case 'search':
			$pattern = isset($_GET['pattern']) ? $_GET['pattern'] : '';
			$path = isset($_GET['path']) ? $_GET['path'] : '/';
			$ext = isset($_GET['include']) ? $_GET['include'] : '';
			$fm = new AIFileManager($softpath, $user_home_dir);
			$exts = !empty($ext) ? array($ext) : array();
			echo json_encode($fm->search_in_files($pattern, $path, $exts));
			break;

		case 'snapshot':
			$message = isset($_POST['message']) ? $_POST['message'] : 'Snapshot at '.date('Y-m-d H:i:s');
			$sm = new AISnapshotManager($softpath, true, $user_home_dir);
			echo json_encode($sm->create_snapshot($message));
			break;

		case 'snapshots':
			$limit = intval(isset($_GET['limit']) ? $_GET['limit'] : 50);
			$sm = new AISnapshotManager($softpath, true, $user_home_dir);
			echo json_encode($sm->list_snapshots($limit));
			break;

		case 'restore':
			$id = isset($_POST['id']) ? $_POST['id'] : '';
			$sm = new AISnapshotManager($softpath, true, $user_home_dir);
			echo json_encode($sm->restore_snapshot($id));
			break;

		case 'diff':
			$sm = new AISnapshotManager($softpath, true, $user_home_dir);
			echo json_encode($sm->get_working_diff());
			break;

		case 'project_info':
			$ctx = new ProjectContext($softpath);
			echo json_encode(array(
				'type' => $ctx->detect_type(),
				'overview' => $ctx->get_overview(),
				'path' => $softpath
			));
			break;

		case 'settings_load':
			$settings = new AISettings($username);
			$providers = $settings->get_connected_providers();
			foreach($providers as &$p){
				if(!empty($p['api_key'])){
					$p['api_key_masked'] = substr($p['api_key'], 0, 8) . '...' . substr($p['api_key'], -4);
					unset($p['api_key']);
				}
			}
			echo json_encode(array('providers' => $providers));
			break;

		case 'settings_save':
			$provider_id = isset($_POST['provider_id']) ? $_POST['provider_id'] : '';
			$api_key = isset($_POST['api_key']) ? $_POST['api_key'] : '';
			$name = isset($_POST['name']) ? $_POST['name'] : '';
			$base_url = isset($_POST['base_url']) ? $_POST['base_url'] : '';
			$models_raw = isset($_POST['models']) ? $_POST['models'] : 'array()';
			$models = @json_decode($models_raw, true) ? @json_decode($models_raw, true) : array();

			$no_key_providers = array('ollama', 'opencode_zen');
			if(strpos($provider_id, 'custom:') === 0){
				$no_key_providers[] = $provider_id;
			}

			if(empty($provider_id) || (empty($api_key) && !in_array($provider_id, $no_key_providers))){
				echo json_encode(array('error' => 'Provider ID and API key are required'));
				break;
			}

			$config = array('api_key' => $api_key, 'connected_at' => time());
			if(!empty($name)) $config['name'] = $name;
			if(!empty($base_url)) $config['base_url'] = $base_url;
			if(!empty($models)) $config['models'] = $models;

			if(strpos($provider_id, 'custom:') === 0){
				if(empty($base_url)){
					echo json_encode(array('error' => 'Base URL is required for custom providers'));
					break;
				}
				if(empty($name)) $config['name'] = str_replace('custom:', '', $provider_id);
				if(empty($models)) $config['models'] = array('default' => 'Default Model');
			}

			$settings = new AISettings($username);
			$settings->save_provider($provider_id, $config);
			echo json_encode(array('success' => true));
			break;

		case 'settings_delete':
			$provider_id = isset($_POST['provider_id']) ? $_POST['provider_id'] : '';
			$settings = new AISettings($username);
			$settings->delete_provider($provider_id);
			echo json_encode(array('success' => true));
			break;

		case 'test_connection':
			$provider_id = isset($_POST['provider_id']) ? $_POST['provider_id'] : '';
			$api_key = isset($_POST['api_key']) ? $_POST['api_key'] : '';
			$model = isset($_POST['model']) ? $_POST['model'] : '';
			$base_url = isset($_POST['base_url']) ? $_POST['base_url'] : '';
			$models_raw = isset($_POST['models']) ? $_POST['models'] : '';
			$models = $models_raw ? (@json_decode($models_raw, true) ?: array()) : array();

			$settings = new AISettings($username);
			$provider_config = $settings->get_provider_config($provider_id);
			$provider_config = $provider_config ? $provider_config : array();
			if(!empty($api_key)) $provider_config['api_key'] = $api_key;
			if(!empty($base_url)) $provider_config['base_url'] = $base_url;
			if(!empty($models)) $provider_config['models'] = $models;

			$provider_instance = ai_php_get_provider_instance($provider_id, $provider_config);
			$test_model = !empty($model) ? $model : $provider_instance->get_default_model();
			if(empty($test_model)){
				if(!empty($provider_config['models'])){
					$test_model = array_key_first($provider_config['models']);
				}
				if(empty($test_model)){
					echo json_encode(array('error' => 'No model specified. Please add at least one model.'));
					break;
				}
			}
			$client = new AIClient($provider_instance, isset($provider_config['api_key']) ? $provider_config['api_key'] : '', $test_model, $provider_config);
			echo json_encode($client->test_connection());
			break;

		case 'edit_message':
			$msg_id = isset($_POST['message_id']) ? $_POST['message_id'] : '';
			$new_content = isset($_POST['content']) ? $_POST['content'] : '';
			if(empty($msg_id) || empty($new_content)){
				echo json_encode(array('error' => 'message_id and content are required'));
				break;
			}
			$conv_id = AISession::get_active_conversation_id($username, $softpath);
			$conv_dir = AISession::get_conversations_dir($username, $softpath);
			$conv = AIConversation::load($conv_dir . '/' . $conv_id . '.json.php');
			if($conv){
				$conv->truncate_after_message($msg_id);
				$conv->edit_message($msg_id, $new_content);
				$conv->save();
				echo json_encode(array('success' => true));
			}else{
				echo json_encode(array('error' => 'Conversation not found'));
			}
			break;

		case 'regenerate':
			$conv_id = AISession::get_active_conversation_id($username, $softpath);
			$conv_dir = AISession::get_conversations_dir($username, $softpath);
			$conv = AIConversation::load($conv_dir . '/' . $conv_id . '.json.php');
			if($conv){
				$last_asst = $conv->get_last_assistant_message_id();
				if($last_asst){
					$conv->truncate_after_message($last_asst);
					$conv->save();
				}
				echo json_encode(array('success' => true));
			}else{
				echo json_encode(array('error' => 'Conversation not found'));
			}
			break;

		case 'changes':
			$sm = new AISnapshotManager($softpath, true, $user_home_dir);
			$diff = $sm->get_working_diff();
			$snapshots = $sm->list_snapshots(20);
			echo json_encode(array('diff' => $diff, 'snapshots' => $snapshots));
			break;

		case 'resolve_file':
			$path = isset($_GET['path']) ? $_GET['path'] : '';
			$fm = new AIFileManager($softpath, $user_home_dir);
			$result = $fm->read_file($path);
			if(!empty($result['error'])){
				echo json_encode(array('error' => $result['error']));
			}else{
				echo json_encode(array('content' => $result['content'], 'path' => $path));
			}
			break;

		case 'auto_title':
			$conv_id = AISession::get_active_conversation_id($username, $softpath);
			$conv_dir = AISession::get_conversations_dir($username, $softpath);
			$conv = AIConversation::load($conv_dir . '/' . $conv_id . '.json.php');
			if($conv){
				$msgs = $conv->get_messages();
				$title = '';
				foreach($msgs as $m){
					if(($m['role'] ?? '') === 'user' && !empty($m['content'])){
						$title = mb_substr($m['content'], 0, 60);
						break;
					}
				}
				if($title && empty($conv->get_title())){
					$conv->set_title($title);
					$conv->save();
				}
				echo json_encode(array('success' => true, 'title' => $title));
			}else{
				echo json_encode(array('error' => 'Conversation not found'));
			}
			break;

		case 'abort':
			$conv_id = AISession::get_active_conversation_id($username, $softpath);
			$conv_dir = AISession::get_conversations_dir($username, $softpath);
			$abort_file = $conv_dir . '/' . $conv_id . '.abort';
			@file_put_contents($abort_file, time());
			echo json_encode(array('success' => true));
			break;

		default:
			echo json_encode(array('error' => 'Unknown action: '.$action));
	}

	die();
}