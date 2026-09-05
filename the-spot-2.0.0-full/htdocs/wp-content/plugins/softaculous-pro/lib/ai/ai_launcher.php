<?php

if(!defined('SOFTACULOUS')){
	die('Hacking Attempt');
}

function ai_get_homedir($username = ''){
	if(!empty($username)){
		if(!empty($_SERVER['HOME'])){
			$home = rtrim($_SERVER['HOME'], '/');
			if(basename($home) === $username && is_dir($home)){
				return $home;
			}
		}
		if(!empty($_SERVER['DOCUMENT_ROOT'])){
			$parent = dirname(rtrim($_SERVER['DOCUMENT_ROOT'], '/'));
			if(basename($parent) === $username && is_dir($parent)){
				return $parent;
			}
		}
		return '/home/' . $username;
	}
	if(!empty($_SERVER['HOME'])){
		return rtrim($_SERVER['HOME'], '/');
	}
	if(!empty($_SERVER['DOCUMENT_ROOT'])){
		return dirname(rtrim($_SERVER['DOCUMENT_ROOT'], '/'));
	}
	return '/home/' . $username;
}

function ai_get_softdir($username = ''){
	global $softpanel;
	
	if(defined('ABSPATH')){
		return rtrim(ABSPATH, '/');
	}
	
	if(!empty($softpanel->user['softdir'])){
		return $softpanel->user['softdir'];
	}
	return ai_get_homedir($username);
}

function ai_php_init_classes(){
	require_once(__DIR__ . '/core/class_session.php');
	require_once(__DIR__ . '/core/class_conversation.php');
	require_once(__DIR__ . '/core/class_file_manager.php');
	require_once(__DIR__ . '/core/class_snapshot_manager.php');
	require_once(__DIR__ . '/core/class_ai_client.php');
	require_once(__DIR__ . '/core/class_tool_definitions.php');
	require_once(__DIR__ . '/core/class_tool_executor.php');
	require_once(__DIR__ . '/core/class_project_context.php');
	require_once(__DIR__ . '/core/class_settings.php');
	require_once(__DIR__ . '/providers/interface_ai_provider.php');
	require_once(__DIR__ . '/providers/class_providers.php');
}

function ai_php_get_provider_instance($provider_id, $config = []){
	$providers = [
		'openai' => 'OpenAIProvider',
		'anthropic' => 'AnthropicProvider',
		'google' => 'GoogleProvider',
		'openrouter' => 'OpenRouterProvider',
		'ollama' => 'OllamaProvider',
		'ollama_cloud' => 'OllamaCloudProvider',
		'groq' => 'GroqProvider',
		'together' => 'TogetherProvider',
		'deepseek' => 'DeepSeekProvider',
		'azure' => 'AzureProvider',
		'bedrock' => 'BedrockProvider',
		'fireworks' => 'FireworksProvider',
		'cloudflare' => 'CloudflareProvider',
		'huggingface' => 'HuggingFaceProvider',
		'minimax' => 'MiniMaxProvider',
		'opencode_zen' => 'OpenCodeZenProvider',
		'opencode_zen_premium' => 'OpenCodeZenProvider',
	];

	if(strpos($provider_id, 'custom:') === 0){
		$base_url = $config['base_url'] ?? '';
		return new CustomProvider($base_url, $provider_id, $config);
	}

	$class = $providers[$provider_id] ?? null;
	if($class && class_exists($class)){
		if($provider_id === 'opencode_zen' || $provider_id === 'opencode_zen_premium'){
			return new $class($provider_id);
		}
		return new $class();
	}
	return new OpenAIProvider();
}

function ai_php_build_system_prompt($project_path, $mode = 'build'){
	ai_php_init_classes();
	$ctx = new ProjectContext($project_path);
	$type = $ctx->detect_type();
	$overview = $ctx->get_overview();
	$type_advice = $ctx->get_system_prompt_additions();

	$prompt = "You are an expert AI coding assistant. You help users with their coding tasks.\n\n";
	$prompt .= "CURRENT MODE: " . ($mode === 'plan' ? "PLAN MODE (read-only - explore and plan, do NOT edit or write files)" : "BUILD MODE (full access - you can read, write, edit files and run commands)") . "\n\n";
	$prompt .= "PROJECT INFORMATION:\n{$overview}\n\n";
	$prompt .= "GUIDELINES:\n";
	$prompt .= "- {$type_advice}\n";
	$prompt .= "- Read files before modifying them to understand context\n";
	$prompt .= "- Make minimal, focused changes that solve the problem\n";
	$prompt .= "- Use edit_file for targeted changes (preferred over write_file for existing files)\n";
	$prompt .= "- Use write_file only for creating new files or when changes are very large\n";
	$prompt .= "- Use glob/grep to find relevant files before reading them\n";
	$prompt .= "- Explain what you are going to do before making changes\n";
	$prompt .= "- If a task is complex, break it into steps using the todo_write tool\n";
	$prompt .= "- After making changes, verify them by reading the file or running relevant commands\n";
	$prompt .= "- If you are unsure about something, ask the user for clarification\n";
	$prompt .= "- Always use clear, concise responses\n";
	$prompt .= "- When showing code, use markdown code blocks with the appropriate language\n";
	$prompt .= "- Preserve existing code style and conventions\n";
	$prompt .= "- Never add comments unless explicitly asked\n\n";
	$prompt .= "IMPORTANT: When you use a tool, wait for the result before proceeding. Do not assume the result.";

	return $prompt;
}

function ai_php_sse_event($event, $data){
	$data['_event'] = $event;
	echo "data: " . json_encode($data) . "\n\n";
	if(ob_get_level() > 0) ob_flush();
	flush();
}

function ai_php_send_prompt_stream($username, $project_path, $content, $options = []){
	ai_php_init_classes();

	header('Content-Type: text/event-stream');
	header('Cache-Control: no-cache');
	header('Connection: keep-alive');
	header('X-Accel-Buffering: no');
	@ini_set('output_buffering', 'off');
	@ini_set('zlib.output_compression', false);
	while(ob_get_level()) ob_end_clean();

	$session = AISession::load($username, $project_path);
	if(!$session){
		$session = ['provider' => '', 'model' => '', 'mode' => 'build'];
		AISession::save($username, $project_path, $session);
	}

	$settings = new AISettings($username);
	$provider_id = $session['provider'] ?? '';
	$model = $session['model'] ?? '';
	$mode = $session['mode'] ?? 'build';
	$conv_id = $options['conversation_id'] ?? AISession::get_active_conversation_id($username, $project_path);

	$no_key_providers = ['opencode_zen', 'ollama'];
	$provider_config = $settings->get_provider_config($provider_id);
	if(!$provider_config && !in_array($provider_id, $no_key_providers) && strpos($provider_id, 'custom:') !== 0){
		ai_php_sse_event('error', ['message' => "Provider '{$provider_id}' is not connected. Please connect it in Settings."]);
		return;
	}

	$api_key = $settings->get_api_key($provider_id);
	if(empty($api_key) && !in_array($provider_id, $no_key_providers) && strpos($provider_id, 'custom:') !== 0){
		ai_php_sse_event('error', ['message' => "No API key configured for {$provider_id}."]);
		return;
	}

	$conv_dir = AISession::get_conversations_dir($username, $project_path);
	$conv_file = $conv_dir . '/' . $conv_id . '.json.php';
	$conversation = AIConversation::load($conv_file);
	if(!$conversation){
		$conversation = AIConversation::create($conv_file, $project_path, $conv_id);
	}

	$conversation->set_mode($mode);
	$conversation->add_user_message($content, $options['attachments'] ?? []);
	$conversation->save();

	$lock_file = $conv_dir . '/' . $conv_id . '.lock';
	$lock_fp = fopen($lock_file, 'c');
	if(!$lock_fp || !flock($lock_fp, LOCK_EX | LOCK_NB)){
		ai_php_sse_event('error', ['message' => 'A generation is already in progress. Please wait or stop it first.']);
		return;
	}

	$system_prompt = ai_php_build_system_prompt($project_path, $mode);
	$provider_instance = ai_php_get_provider_instance($provider_id, $provider_config ?: []);
	$client = new AIClient($provider_instance, $api_key, $model, $provider_config ?: []);
	$tools = ToolDefinitions::get_for_mode($mode);
	$tool_defs = array_values($tools);
	$user_home_dir = '';
	global $softpanel;
	if(!empty($softpanel->user['homedir'])){
		$user_home_dir = $softpanel->user['homedir'];
	}elseif(!empty($username)){
		$user_home_dir = ai_get_homedir($username);
	}

	$file_manager = new AIFileManager($project_path, $user_home_dir);
	$tool_executor = new ToolExecutor($file_manager, $project_path, $user_home_dir, $mode);

	$max_iterations = 25;
	$iteration = 0;
	$total_usage = ['input_tokens' => 0, 'output_tokens' => 0, 'cached_tokens' => 0];
	$tool_call_count = 0;

	while($iteration < $max_iterations){
		$iteration++;

		if(connection_aborted()){
			break;
		}

		if($iteration > 1){
			ai_php_sse_event('iteration-start', ['iteration' => $iteration]);
		}

		$msgs_api = $conversation->get_for_api();
		array_unshift($msgs_api, ['role' => 'system', 'content' => $system_prompt]);

		$max_retries = 2;
		$retry_count = 0;
		$response = null;
		
		while($retry_count <= $max_retries){
			$sync_fallback = ($provider_id === 'opencode_zen' || $provider_id === 'opencode_zen_premium');
			if($sync_fallback){
				$response = $client->chat($msgs_api, $tool_defs, ['max_tokens' => 8192, 'timeout' => 120]);

				$parts = $response['parts'] ?? [];
				$full_text = '';
				foreach($parts as $part){
					if(($part['type'] ?? '') === 'reasoning' && !empty($part['text'])){
						ai_php_sse_event('reasoning-delta', ['text' => $part['text']]);
					}elseif(($part['type'] ?? '') === 'text' && !empty($part['text'])){
						$full_text .= $part['text'];
					}elseif(($part['type'] ?? '') === 'tool_use'){
						ai_php_sse_event('tool-call', [
							'id' => $part['id'] ?? '',
							'name' => $part['name'] ?? '',
							'input' => $part['input'] ?? [],
							'status' => 'running',
							'iteration' => $iteration
						]);
					}
				}
				if(!empty($full_text)){
					ai_php_sse_event('text-delta', ['text' => $full_text]);
				}
			}else{
				$response = $client->chat_stream($msgs_api, $tool_defs, function($event){
					if($event['type'] === 'text_delta'){
						ai_php_sse_event('text-delta', ['text' => $event['text']]);
					}elseif($event['type'] === 'reasoning_delta'){
						ai_php_sse_event('reasoning-delta', ['text' => $event['text']]);
					}elseif($event['type'] === 'tool_call'){
						ai_php_sse_event('tool-call', [
							'id' => $event['id'],
							'name' => $event['name'],
							'input' => $event['input'],
							'status' => 'running',
							'iteration' => $GLOBALS['ai_iteration'] ?? 0
						]);
					}
				}, ['max_tokens' => 8192, 'timeout' => 120]);
			}

			$GLOBALS['ai_iteration'] = $iteration;

			if(!empty($response['error'])){
				$retry_count++;
				if($retry_count <= $max_retries){
					ai_php_sse_event('status', ['message' => 'Retrying ('.$retry_count.'/'.$max_retries.')...']);
					usleep(pow(2, $retry_count) * 500000);
					continue;
				}
				ai_php_sse_event('error', ['message' => $response['error']]);
				break;
			}
			break;
		}

		if(!empty($response['usage'])){
			if(!empty($response['usage']['input_tokens'])) $total_usage['input_tokens'] += $response['usage']['input_tokens'];
			if(!empty($response['usage']['output_tokens'])) $total_usage['output_tokens'] += $response['usage']['output_tokens'];
			if(!empty($response['usage']['prompt_tokens'])) $total_usage['input_tokens'] += $response['usage']['prompt_tokens'];
			if(!empty($response['usage']['completion_tokens'])) $total_usage['output_tokens'] += $response['usage']['completion_tokens'];
			if(!empty($response['usage']['cache_read_input_tokens'])) $total_usage['cached_tokens'] += $response['usage']['cache_read_input_tokens'];
			if(!empty($response['usage']['cache_creation_input_tokens'])) $total_usage['cached_tokens'] += $response['usage']['cache_creation_input_tokens'];
			if(!empty($response['usage']['prompt_tokens_details']['cached_tokens'])) $total_usage['cached_tokens'] += $response['usage']['prompt_tokens_details']['cached_tokens'];
		}

		$conversation->add_assistant_content($response['parts'] ?? [], $model, $response['usage'] ?? []);

		$token_estimate = $conversation->get_token_estimate();
		$accum_cost = 0;
		foreach($conversation->get_messages() as $msg){
			if(!empty($msg['usage']['cost'])) $accum_cost += $msg['usage']['cost'];
			if(!empty($msg['usage']['cost_details']['upstream_inference_cost'])) $accum_cost += $msg['usage']['cost_details']['upstream_inference_cost'];
		}

		if(!empty($response['tool_calls'])){
			ai_php_sse_event('usage', [
				'usage' => $total_usage,
				'iterations' => $iteration,
				'context_tokens' => $token_estimate,
				'tool_calls' => $tool_call_count,
				'cost' => $accum_cost
			]);
		}

		if(empty($response['tool_calls'])){
		ai_php_sse_event('done', [
			'usage' => $total_usage,
			'iterations' => $iteration,
			'context_tokens' => $token_estimate,
			'tool_calls' => $tool_call_count,
			'cost' => $accum_cost,
			'conversation_id' => $conv_id
		]);
			break;
		}

		foreach($response['tool_calls'] as $tool_call){
			$tool_call_count++;
			$tool_name = $tool_call['name'] ?? '';
			$tool_input = $tool_call['input'] ?? [];
			$tool_call_id = $tool_call['id'] ?? 'call_0';

			if(in_array($tool_name, ['write_file', 'edit_file'])){
				$sm = new AISnapshotManager($project_path, true, $user_home_dir);
				$sm->create_snapshot('Auto-snapshot before '.$tool_name.' '.$tool_input['path']);
			}

			$result = $tool_executor->execute($tool_name, $tool_input);

			$tool_result_data = [
				'id' => $tool_call_id,
				'name' => $tool_name,
				'status' => !empty($result['is_error']) ? 'error' : 'completed',
				'output' => mb_substr($result['output'] ?? '', 0, 2000),
				'is_error' => !empty($result['is_error'])
			];
			if(!empty($result['diff'])){
				$tool_result_data['diff'] = mb_substr($result['diff'], 0, 8000);
			}
			ai_php_sse_event('tool-result', $tool_result_data);

			$conversation->add_tool_result(
				$tool_call_id,
				$result['output'] ?? $result['error'] ?? 'No output',
				!empty($result['is_error'])
			);

			if($tool_name === 'todo_write' && !empty($result['todos'])){
				$conversation->set_todos($result['todos']);
				ai_php_sse_event('todos', ['todos' => $result['todos']]);
			}
		}

		$conversation->save();

		$token_estimate = $conversation->get_token_estimate();
		if($token_estimate > 100000){
			$conversation->compact(3);
		}
	}

	if($conversation->count_user_messages() <= 1 && empty($conversation->get_title())){
		$conversation->set_title(mb_substr($content, 0, 80));
	}

	flock($lock_fp, LOCK_UN);
	fclose($lock_fp);
	@unlink($lock_file);

	$conversation->save();
	AISession::set_active_conversation($username, $project_path, $conv_id);
}
