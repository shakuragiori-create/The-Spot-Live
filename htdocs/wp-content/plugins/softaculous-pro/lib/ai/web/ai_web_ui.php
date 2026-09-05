<?php

if(!defined('SOFTACULOUS')){
	die('Hacking Attempt');
}

include_once(__DIR__.'/../ai_launcher.php');

function ai_web_ui(){
	global $user, $globals, $l, $theme, $softpanel, $error, $insid, $software, $soft;
	global $edited, $settings, $iscripts, $catwise, $scripts, $noheader;

	$insid = GET('insid', '');
	$custom_path = GET('path', '');

	if(empty($insid) && empty($custom_path)){
		return ai_web_ui_select();
	}

	if(!empty($insid)){
		if(empty($user['ins'][$insid])){
			reporterror(__('Invalid Installation'), __('The installation ID is invalid or does not exist.'));
			return false;
		}
		$data = $user['ins'][$insid];
		$soft = get_sid_by_version($data['ver'], $data['sid']);
		$software = !empty($iscripts[$soft]) ? $iscripts[$soft] : array('name' => 'Software');
		$softpath = $data['softpath'];
		$softurl = !empty($data['softurl']) ? $data['softurl'] : '';
		$back_url = $globals['ind'].'act=editdetail&insid='.$insid;
		$is_custom_path = false;
		$username = !empty($user['username']) ? $user['username'] : '';
	}elseif(!empty($custom_path)){
		$home_dir = !empty($softpanel->user['homedir']) ? $softpanel->user['homedir'] : (function_exists('ai_get_homedir') ? ai_get_homedir($softpanel->user['name']) : '/home/' . $softpanel->user['name']);
		$custom_path = trim($custom_path);
		if(strpos($custom_path, './') !== false || strpos($custom_path, '../') !== false || strpos($custom_path, '/..') !== false || strpos($custom_path, '..') === 0){
			reporterror(__('Invalid Path'), __('Path traversal is not allowed.'));
			return false;
		}
		if(strpos($custom_path, $home_dir) === 0){
			$custom_path = substr($custom_path, strlen($home_dir) + 1);
		}
		$softpath = cleanpath($home_dir . '/' . $custom_path);
		if($softpath !== $home_dir && strpos($softpath, $home_dir . '/') !== 0){
			reporterror(__('Invalid Path'), __('The path must be within your home directory.'));
			return false;
		}
		if(empty($softpath) || !@is_dir($softpath)){
			reporterror(__('Invalid Path'), __('The directory path is invalid or does not exist.'));
			return false;
		}
		$software = array('name' => basename($softpath));
		$softurl = '';
		$back_url = $globals['ind'].'act=ai';
		$is_custom_path = true;
		$username = !empty($user['username']) ? $user['username'] : '';
	}else{
		return ai_web_ui_select();
	}

	ai_php_init_classes();

	$csrf = '';
	if(function_exists('csrf_display')){
		ob_start();
		csrf_display();
		$csrf_html = ob_get_clean();
		if(preg_match('/value="([^"]+)"/', $csrf_html, $m)){
			$csrf = $m[1];
		}
	}

	$api_base = $globals['index'].'act=ai';
	if(!empty($insid)){
		$api_base .= '&insid='.$insid;
	}else{
		$api_base .= '&path='.urlencode($custom_path);
	}

	$session = AISession::load($username, $softpath);
	$settings = new AISettings($username);
	$status = array(
		'running' => !empty($session),
		'session' => $session,
		'providers' => $settings->get_all_providers()
	);
	$running = !empty($status['running']);

	softheader(__('$0 - Code with AI', array($software['name'])));

	echo '<style>
		.ai-webui-container { display: flex; flex-direction: column; height: calc(100vh - 120px); min-height: 600px; }
		.ai-webui-iframe { flex: 1; border: none; background: #1a1a2e; }
		.ai-webui-overlay { position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.8); display: flex; flex-direction: column; align-items: center; justify-content: center; z-index: 1000; }
		.ai-webui-overlay.hidden { display: none; }
		.ai-webui-spinner { width: 50px; height: 50px; border: 4px solid #333; border-top-color: #00d9ff; border-radius: 50%; animation: spin 1s linear infinite; margin-bottom: 20px; }
		@keyframes spin { to { transform: rotate(360deg); } }
		.ai-webui-status { color: #fff; font-size: 16px; margin-bottom: 10px; }
		.ai-webui-error { color: #ff6b6b; font-size: 14px; max-width: 400px; text-align: center; background: rgba(255,107,107,0.2); padding: 15px; border-radius: 8px; margin-bottom: 20px; }
		.ai-webui-btn { padding: 12px 24px; background: #00d9ff; color: #000; border: none; border-radius: 8px; font-size: 14px; font-weight: 600; cursor: pointer; }
		.ai-webui-btn:hover { background: #00b8d9; }
		.ai-webui-btn:disabled { opacity: 0.5; cursor: not-allowed; }
		.ai-webui-progress { width: 300px; height: 4px; background: #333; border-radius: 2px; margin-top: 20px; overflow: hidden; }
		.ai-webui-progress-bar { height: 100%; background: #00d9ff; width: 0%; transition: width 0.3s; }
		#ai_webui_frame { width: 100%; height: 100%; border: none; }
	</style>';

	echo '<script>
		var csrfToken = "'.htmlspecialchars($csrf).'";
		var apiBase = "'.htmlspecialchars($api_base).'";
		var softpath = "'.htmlspecialchars($softpath).'";
		var username = "'.htmlspecialchars($username).'";
		var isRunning = '.($running ? 'true' : 'false').';
		var currentStatus = '.json_encode($status).';
		var providersList = '.json_encode($status['providers'] ?? array()).';

		function aiWebUIPollStatus(){
			fetch(apiBase + "&ai_api=status" + "&csrf_token=" + csrfToken)
				.then(r => r.json())
				.then(data => {
					if(data.running){
						document.getElementById("ai_webui_overlay").classList.add("hidden");
						document.getElementById("ai_webui_progress").style.width = "100%";
						setTimeout(function(){
							document.getElementById("ai_webui_progress").style.width = "0%";
						}, 500);
					}else{
						setTimeout(aiWebUIPollStatus, 2000);
					}
				})
				.catch(err => {
					console.error("Status poll error:", err);
					setTimeout(aiWebUIPollStatus, 5000);
				});
		}

		function aiWebUIStart(){
			document.getElementById("ai_webui_status").textContent = "Starting AI daemon...";
			document.getElementById("ai_webui_start_btn").disabled = true;
			document.getElementById("ai_webui_progress").style.width = "30%";

			fetch(apiBase + "&ai_api=start" + "&csrf_token=" + csrfToken, { method: "POST" })
				.then(r => r.json())
				.then(data => {
					if(data.error){
						document.getElementById("ai_webui_error").textContent = data.error;
						document.getElementById("ai_webui_start_btn").disabled = false;
						document.getElementById("ai_webui_progress").style.width = "0%";
						return;
					}
					document.getElementById("ai_webui_status").textContent = "Daemon started. Loading interface...";
					document.getElementById("ai_webui_progress").style.width = "60%";
					aiWebUIPollStatus();
				})
				.catch(err => {
					document.getElementById("ai_webui_error").textContent = "Failed to start: " + err.message;
					document.getElementById("ai_webui_start_btn").disabled = false;
					document.getElementById("ai_webui_progress").style.width = "0%";
				});
		}

		document.addEventListener("DOMContentLoaded", function(){
			if(!isRunning){
				document.getElementById("ai_webui_overlay").classList.remove("hidden");
				aiWebUIStart();
			}else{
				document.getElementById("ai_webui_overlay").classList.add("hidden");
			}
		});
	</script>';

	echo '<div class="ai-webui-container">
		<iframe id="ai_webui_frame" src="'.htmlspecialchars($globals['index']).'act=ai_frame'.(!empty($insid) ? '&insid='.htmlspecialchars($insid) : '&path='.urlencode($custom_path)).'"></iframe>
		
		<div id="ai_webui_overlay" class="ai-webui-overlay">
			<div class="ai-webui-spinner"></div>
			<div id="ai_webui_status" class="ai-webui-status">Starting AI Assistant...</div>
			<div id="ai_webui_error" class="ai-webui-error" style="display:none;"></div>
			<button id="ai_webui_start_btn" class="ai-webui-btn" onclick="aiWebUIStart()">
				<i class="fas fa-play"></i> Start AI Assistant
			</button>
			<div class="ai-webui-progress">
				<div id="ai_webui_progress" class="ai-webui-progress-bar"></div>
			</div>
		</div>
	</div>';

	softfooter();
}

function ai_web_ui_select(){
	global $user, $globals, $l, $theme, $softpanel, $error, $iscripts;

	$username = !empty($user['username']) ? $user['username'] : '';
	$home_dir = !empty($softpanel->user['homedir']) ? $softpanel->user['homedir'] : (function_exists('ai_get_homedir') ? ai_get_homedir($username) : '/home/' . $username);

	$installations = array();
	if(!empty($user['ins'])){
		foreach($user['ins'] as $insid => $data){
			$soft = get_sid_by_version($data['ver'], $data['sid']);
			$software = !empty($iscripts[$soft]) ? $iscripts[$soft] : array('name' => 'Software');
			$installations[] = array(
				'insid' => $insid,
				'name' => $software['name'],
				'url' => !empty($data['softurl']) ? $data['softurl'] : '',
				'path' => $data['softpath']
			);
		}
	}

	softheader(__('$0 - Code with AI', array(APP)));

	echo '<style>
		body { background: #f1f6fa !important; }
		.ai-select-container { max-width: 800px; margin: 40px auto; padding: 0; background: transparent; border: none; box-shadow: none; }
		.ai-select-header { background: #fff; border-radius: 12px 12px 0 0; padding: 24px 30px; border-bottom: 1px solid #e1e8ed; }
		.ai-select-title { font-size: 22px; font-weight: 600; color: #1a1a2e; margin-bottom: 6px; display: flex; align-items: center; gap: 10px; }
		.ai-select-title i { color: #6c5ce7; font-size: 24px; }
		.ai-select-subtitle { font-size: 14px; color: #6f6f6f; margin: 0; }
		.ai-select-alert { background: #fff3cd; border: 1px solid #ffc107; border-radius: 8px; padding: 12px 16px; margin: 20px 0 0; font-size: 13px; color: #856404; display: flex; align-items: center; gap: 10px; }
		.ai-select-alert i { color: #ffc107; }
		.ai-custom-section { background: #fff; border-radius: 12px; padding: 24px 30px; margin-bottom: 20px; border: 1px solid #e1e8ed; }
		.ai-custom-title { font-size: 15px; font-weight: 600; color: #1a1a2e; margin-bottom: 6px; display: flex; align-items: center; gap: 8px; }
		.ai-custom-title i { color: #6c5ce7; }
		.ai-custom-desc { font-size: 13px; color: #6f6f6f; margin-bottom: 16px; }
		.ai-custom-form { display: flex; gap: 10px; align-items: center; }
		.ai-custom-home { font-size: 13px; color: #8f8f8f; background: #f8f9fa; padding: 10px 12px; border: 1px solid #e1e8ed; border-right: none; border-radius: 6px 0 0 6px; font-family: monospace; white-space: nowrap; }
		.ai-custom-input { flex: 1; padding: 10px 14px; border: 1px solid #e1e8ed; border-radius: 0 6px 6px 0; background: #fff; color: #1a1a2e; font-size: 14px; font-family: monospace; }
		.ai-custom-input:focus { outline: none; border-color: #6c5ce7; }
		.ai-custom-btn { padding: 10px 20px; background: #6c5ce7; color: #fff; border: none; border-radius: 6px; font-size: 13px; font-weight: 600; cursor: pointer; white-space: nowrap; }
		.ai-custom-btn:hover { background: #5a4ad1; }
		.ai-or-divider { text-align: center; margin: 20px 0; color: #8f8f8f; font-size: 13px; position: relative; }
		.ai-or-divider::before, .ai-or-divider::after { content: ""; position: absolute; top: 50%; width: 40%; height: 1px; background: #e1e8ed; }
		.ai-or-divider::before { left: 0; }
		.ai-or-divider::after { right: 0; }
		.ai-installs-section { background: #fff; border-radius: 12px; padding: 24px 30px; border: 1px solid #e1e8ed; }
		.ai-installs-header { font-weight: 600; color: #6f6f6f; margin-bottom: 16px; font-size: 12px; text-transform: uppercase; letter-spacing: 0.5px; display: flex; align-items: center; gap: 8px; }
		.ai-installs-header i { color: #6c5ce7; }
		.ai-install-list { display: flex; flex-direction: column; gap: 10px; }
		.ai-install-item { display: flex; align-items: center; padding: 14px 16px; border: 1px solid #e1e8ed; border-radius: 10px; cursor: pointer; transition: all 0.2s; text-decoration: none; color: inherit; }
		.ai-install-item:hover { border-color: #6c5ce7; background: #fafafe; transform: translateY(-1px); box-shadow: 0 2px 8px rgba(108,92,231,0.1); }
		.ai-install-icon { width: 40px; height: 40px; background: linear-gradient(135deg, #6c5ce7, #a29bfe); border-radius: 10px; display: flex; align-items: center; justify-content: center; color: #fff; margin-right: 14px; font-size: 16px; flex-shrink: 0; }
		.ai-install-info { flex: 1; min-width: 0; }
		.ai-install-name { font-weight: 600; color: #1a1a2e; margin-bottom: 4px; font-size: 14px; }
		.ai-install-url { font-size: 12px; color: #6c5ce7; margin-bottom: 2px; word-break: break-all; }
		.ai-install-path { font-size: 11px; color: #8f8f8f; font-family: monospace; }
		.ai-install-arrow { color: #ccc; font-size: 14px; margin-left: 10px; }
	</style>';

	echo '<div class="ai-select-container">
		<div class="ai-select-header">
			<div class="ai-select-title">
				<i class="fas fa-robot"></i> Code with AI
			</div>
			<div class="ai-select-subtitle">Select a project to start coding with AI</div>
			<div class="ai-select-alert">
				<i class="fas fa-info-circle"></i>
				You are selecting a project to work with AI Assistant
			</div>
		</div>

		<div class="ai-custom-section">
			<div class="ai-custom-title">
				<i class="fas fa-folder-open"></i> Open Custom Path
			</div>
			<div class="ai-custom-desc">Enter a directory path to work with AI on any project</div>
			<form method="get" action="'.$globals['index'].'">
				<input type="hidden" name="act" value="ai_webui" />
				<div class="ai-custom-form">
					<span class="ai-custom-home">'.$home_dir.'/</span>
					<input type="text" name="path" class="ai-custom-input" placeholder="public_html/project" />
					<button type="submit" class="ai-custom-btn">Open</button>
				</div>
			</form>
		</div>';

	if(!empty($installations)){
		echo '<div class="ai-or-divider">OR select from your installations</div>

		<div class="ai-installs-section">
			<div class="ai-installs-header">
				<i class="fas fa-globe"></i> Your Installations
			</div>
			<div class="ai-install-list">';

		foreach($installations as $ins){
			echo '<a href="'.$globals['ind'].'act=ai_webui&insid='.$ins['insid'].'" class="ai-install-item">
				<div class="ai-install-icon"><i class="fas fa-wordpress"></i></div>
				<div class="ai-install-info">
					<div class="ai-install-name">'.htmlspecialchars($ins['name']).'</div>
					<div class="ai-install-url">'.htmlspecialchars($ins['url']).'</div>
					<div class="ai-install-path">'.htmlspecialchars($ins['path']).'</div>
				</div>
				<div class="ai-install-arrow"><i class="fas fa-chevron-right"></i></div>
			</a>';
		}
		echo '</div></div>';
	}

	echo '</div>';

	softfooter();
}