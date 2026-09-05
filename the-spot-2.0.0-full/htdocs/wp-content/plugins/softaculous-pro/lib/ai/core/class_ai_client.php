<?php

if(!defined('SOFTACULOUS')){
	die('Hacking Attempt');
}

class AIClient {
	private $provider;
	private $api_key;
	private $model;
	private $provider_type;
	private $base_url;
	private $provider_config;

	public function __construct($provider, $api_key, $model, $provider_config = array()){
		$this->provider = $provider;
		$this->api_key = $api_key;
		$this->model = $model;
		$this->provider_type = $provider->get_id();
		$this->base_url = $provider->get_api_endpoint();
		$this->provider_config = $provider_config;
	}

	public function chat($messages, $tools = array(), $options = array()){
		if($this->provider_type === 'anthropic'){
			return $this->anthropic_chat($messages, $tools, $options);
		}elseif($this->provider_type === 'google'){
			return $this->google_chat($messages, $tools, $options);
		}elseif($this->provider_type === 'azure'){
			return $this->azure_chat($messages, $tools, $options);
		}
		return $this->openai_chat($messages, $tools, $options);
	}

	public function chat_stream($messages, $tools, $on_event, $options = array()){
		if($this->provider_type === 'anthropic'){
			return $this->anthropic_stream($messages, $tools, $on_event, $options);
		}elseif($this->provider_type === 'google'){
			return $this->google_stream($messages, $tools, $on_event, $options);
		}elseif($this->provider_type === 'azure'){
			return $this->azure_stream($messages, $tools, $on_event, $options);
		}
		return $this->openai_stream($messages, $tools, $on_event, $options);
	}

	public function test_connection(){
		$messages = array(array('role' => 'user', 'content' => 'Say "OK" in one word.'));
		try{
			$result = $this->chat($messages, array(), array('max_tokens' => 10));
			if(!empty($result['error'])){
				return array('success' => false, 'error' => $result['error']);
			}
			return array('success' => true, 'message' => 'Connection successful');
		}catch(\Exception $e){
			return array('success' => false, 'error' => $e->getMessage());
		}
	}

	// ---- OpenAI Compatible ----

	private function openai_headers(){
		$h = array(
			'Content-Type: application/json'
		);
		if(!empty($this->api_key)){
			$h[] = 'Authorization: Bearer ' . $this->api_key;
		}
		if($this->provider_type === 'opencode_zen' || $this->provider_type === 'opencode_zen_premium' || $this->provider_type === 'openrouter'){
			$h[] = 'HTTP-Referer: https://opencode.ai/';
			$h[] = 'X-Title: Softaculous AI';
		}
		if(!empty($this->provider_config['extra_headers'])){
			foreach($this->provider_config['extra_headers'] as $eh){
				$h[] = $eh;
			}
		}
		return $h;
	}

	private function openai_chat($messages, $tools, $options){
		$url = rtrim($this->base_url, '/') . '/chat/completions';
		$headers = $this->openai_headers();
		$payload = $this->openai_build_payload($messages, $tools, $options);
		$payload['stream'] = false;

		$response = $this->curl_post($url, $headers, $payload, isset($options['timeout']) ? $options['timeout'] : 120);
		if(!empty($response['error'])){
			return $response;
		}
		return $this->openai_parse_response($response['body']);
	}

	private function openai_stream($messages, $tools, $on_event, $options){
		$url = rtrim($this->base_url, '/') . '/chat/completions';
		$headers = $this->openai_headers();
		$payload = $this->openai_build_payload($messages, $tools, $options);
		$payload['stream'] = true;

		$accumulated = array('text' => '', 'reasoning' => '', 'tool_calls' => array(), 'usage' => array());
		$tool_call_buffers = array();
		$sse_buffer = '';

		$this->curl_post_stream($url, $headers, $payload, function($chunk) use ($on_event, &$accumulated, &$tool_call_buffers, &$sse_buffer){
			$sse_buffer .= $chunk;
			while(($pos = strpos($sse_buffer, "\n")) !== false){
				$line = substr($sse_buffer, 0, $pos);
				$sse_buffer = substr($sse_buffer, $pos + 1);
				$line = trim($line);
				if(strpos($line, 'data: ') !== 0) continue;
				$data_str = substr($line, 6);
				if(trim($data_str) === '[DONE]') return;

				$data = @json_decode($data_str, true);
				if(!$data) continue;

				if(!empty($data['choices'][0])){
					$choice = $data['choices'][0];
					$delta = (!empty($choice['delta']) ? $choice['delta'] : array());

					if(!empty($delta['content'])){
						$accumulated['text'] .= $delta['content'];
						$on_event(array('type' => 'text_delta', 'text' => $delta['content']));
					}

					if(!empty($delta['reasoning_content'])){
						$accumulated['reasoning'] .= $delta['reasoning_content'];
						$on_event(array('type' => 'reasoning_delta', 'text' => $delta['reasoning_content']));
					}

					if(!empty($delta['tool_calls'])){
						foreach($delta['tool_calls'] as $tc){
							$idx = isset($tc['index']) ? $tc['index'] : 0;
							if(!isset($tool_call_buffers[$idx])){
								$tool_call_buffers[$idx] = array(
									'id' => isset($tc['id']) ? $tc['id'] : '',
									'name' => isset($tc['function']['name']) ? $tc['function']['name'] : '',
									'arguments' => ''
								);
							}
							if(!empty($tc['id'])) $tool_call_buffers[$idx]['id'] = $tc['id'];
							if(!empty($tc['function']['name'])) $tool_call_buffers[$idx]['name'] = $tc['function']['name'];
							if(!empty($tc['function']['arguments'])) $tool_call_buffers[$idx]['arguments'] .= $tc['function']['arguments'];
						}
					}

					if(!empty($choice['finish_reason'])){
						if($choice['finish_reason'] === 'tool_calls' || $choice['finish_reason'] === 'function_call'){
						}
					}
				}

				if(!empty($data['usage'])){
					$accumulated['usage'] = $data['usage'];
				}
			}
		}, isset($options['timeout']) ? $options['timeout'] : 120);

		if(!empty($tool_call_buffers)){
			foreach($tool_call_buffers as $i => $tc){
				$args = @json_decode($tc['arguments'], true);
				if($args === null) $args = array();
				$tool_call = array(
					'id' => $tc['id'] ? $tc['id'] : 'call_'.$i,
					'name' => $tc['name'],
					'input' => $args
				);
				$accumulated['tool_calls'][] = $tool_call;
				$on_event(array('type' => 'tool_call', 'id' => $tool_call['id'], 'name' => $tool_call['name'], 'input' => $args));
			}
		}

		$on_event(array('type' => 'done', 'usage' => $accumulated['usage']));

		return array(
			'content' => $accumulated['text'],
			'tool_calls' => $accumulated['tool_calls'],
			'usage' => $accumulated['usage'],
			'parts' => $this->build_parts($accumulated)
		);
	}

	private function openai_build_payload($messages, $tools, $options){
		$payload = array(
			'model' => $this->model,
			'messages' => $messages
		);

		if(!empty($tools)){
			$payload['tools'] = array_map(function($t){
				return array(
					'type' => 'function',
					'function' => array(
						'name' => $t['name'],
						'description' => $t['description'],
						'parameters' => $t['parameters']
					)
				);
			}, $tools);
		}

		if(!empty($options['temperature'])){
			$payload['temperature'] = floatval($options['temperature']);
		}
		if(!empty($options['max_tokens'])){
			$payload['max_tokens'] = intval($options['max_tokens']);
		}

		return $payload;
	}

	private function openai_parse_response($body){
		$data = @json_decode($body, true);
		if(!$data){
			return array('error' => 'Invalid JSON response from API');
		}
		if(!empty($data['error'])){
			$msg = is_array($data['error']) ? (isset($data['error']['message']) ? $data['error']['message'] : json_encode($data['error'])) : $data['error'];
			return array('error' => $msg);
		}

		if(empty($data['choices'])) $choice = null; else $choice = $data['choices'][0];
		if(!$choice) return array('error' => 'No response choices');

		if(empty($choice['message']['content'])) $text = ''; else $text = $choice['message']['content'];
		$reasoning = '';
		if(!empty($choice['message']['reasoning_content'])) $reasoning = $choice['message']['reasoning_content'];
		$tool_calls = array();

		if(!empty($choice['message']['tool_calls'])){
			foreach($choice['message']['tool_calls'] as $tc){
				$args = @json_decode(isset($tc['function']['arguments']) ? $tc['function']['arguments'] : '{}', true);
				if($args === null) $args = array();
				$tool_calls[] = array(
					'id' => isset($tc['id']) ? $tc['id'] : '',
					'name' => isset($tc['function']['name']) ? $tc['function']['name'] : '',
					'input' => $args
				);
			}
		}

		$accumulated = array('text' => $text, 'reasoning' => $reasoning, 'tool_calls' => $tool_calls, 'usage' => isset($data['usage']) ? $data['usage'] : array());
		return array(
			'content' => $text,
			'tool_calls' => $tool_calls,
			'usage' => isset($data['usage']) ? $data['usage'] : array(),
			'parts' => $this->build_parts($accumulated)
		);
	}

	private function anthropic_chat($messages, $tools, $options){
		$url = 'https://api.anthropic.com/v1/messages';
		$headers = array(
			'Content-Type: application/json',
			'x-api-key: ' . $this->api_key,
			'anthropic-version: 2023-06-01',
			'Accept: application/json'
		);
		$payload = $this->anthropic_build_payload($messages, $tools, $options);
		$payload['stream'] = false;

		$response = $this->curl_post($url, $headers, $payload, isset($options['timeout']) ? $options['timeout'] : 120);
		if(!empty($response['error'])) return $response;
		return $this->anthropic_parse_response($response['body']);
	}

	private function anthropic_stream($messages, $tools, $on_event, $options){
		$url = 'https://api.anthropic.com/v1/messages';
		$headers = array(
			'Content-Type: application/json',
			'x-api-key: ' . $this->api_key,
			'anthropic-version: 2023-06-01',
			'Accept: text/event-stream'
		);
		$payload = $this->anthropic_build_payload($messages, $tools, $options);
		$payload['stream'] = true;

		$accumulated = array('text' => '', 'reasoning' => '', 'tool_calls' => array(), 'usage' => array());
		$current_tool = null;
		$current_tool_args = '';
		$sse_buffer = '';

		$this->curl_post_stream($url, $headers, $payload, function($chunk) use ($on_event, &$accumulated, &$current_tool, &$current_tool_args, &$sse_buffer){
			$sse_buffer .= $chunk;
			while(($pos = strpos($sse_buffer, "\n")) !== false){
				$line = substr($sse_buffer, 0, $pos);
				$sse_buffer = substr($sse_buffer, $pos + 1);
				$line = trim($line);
				if(strpos($line, 'data: ') !== 0) continue;
				$data_str = substr($line, 6);
				$event = @json_decode($data_str, true);
				if(!$event) continue;

				$type = isset($event['type']) ? $event['type'] : '';

				if($type === 'content_block_delta'){
					$delta = isset($event['delta']) ? $event['delta'] : array();
					$delta_type = isset($delta['type']) ? $delta['type'] : '';
					if($delta_type === 'text_delta' && !empty($delta['text'])){
						$accumulated['text'] .= $delta['text'];
						$on_event(array('type' => 'text_delta', 'text' => $delta['text']));
					}elseif($delta_type === 'thinking_delta' && !empty($delta['thinking'])){
						$accumulated['reasoning'] .= $delta['thinking'];
						$on_event(array('type' => 'reasoning_delta', 'text' => $delta['thinking']));
					}elseif($delta_type === 'input_json_delta' && !empty($delta['partial_json'])){
						$current_tool_args .= $delta['partial_json'];
					}
				}elseif($type === 'content_block_start'){
					$block = isset($event['content_block']) ? $event['content_block'] : array();
					if((isset($block['type']) ? $block['type'] : '') === 'tool_use'){
						$current_tool = array(
							'id' => isset($block['id']) ? $block['id'] : '',
							'name' => isset($block['name']) ? $block['name'] : '',
							'input' => array()
						);
						$current_tool_args = '';
					}
				}elseif($type === 'content_block_stop'){
					if($current_tool){
						$args = @json_decode($current_tool_args, true);
						if($args === null) $args = array();
						$current_tool['input'] = $args;
						$accumulated['tool_calls'][] = $current_tool;
						$on_event(array('type' => 'tool_call', 'id' => $current_tool['id'], 'name' => $current_tool['name'], 'input' => $args));
						$current_tool = null;
						$current_tool_args = '';
					}
				}elseif($type === 'message_delta'){
					if(!empty($event['usage'])){
						$accumulated['usage']['output_tokens'] = isset($event['usage']['output_tokens']) ? $event['usage']['output_tokens'] : 0;
					}
				}elseif($type === 'message_start'){
					if(!empty($event['message']['usage'])){
						$accumulated['usage']['input_tokens'] = isset($event['message']['usage']['input_tokens']) ? $event['message']['usage']['input_tokens'] : 0;
						if(!empty($event['message']['usage']['cache_creation_input_tokens'])) $accumulated['usage']['cache_creation_input_tokens'] = $event['message']['usage']['cache_creation_input_tokens'];
						if(!empty($event['message']['usage']['cache_read_input_tokens'])) $accumulated['usage']['cache_read_input_tokens'] = $event['message']['usage']['cache_read_input_tokens'];
					}
				}elseif($type === 'message_stop'){
					// done
				}elseif($type === 'error'){
					$err_msg = isset($event['error']['message']) ? $event['error']['message'] : 'Unknown error';
					$on_event(array('type' => 'error', 'message' => $err_msg));
				}
			}
		}, isset($options['timeout']) ? $options['timeout'] : 120);

		$on_event(array('type' => 'done', 'usage' => $accumulated['usage']));

		return array(
			'content' => $accumulated['text'],
			'tool_calls' => $accumulated['tool_calls'],
			'usage' => $accumulated['usage'],
			'parts' => $this->build_parts($accumulated)
		);
	}

	private function anthropic_build_payload($messages, $tools, $options){
		$system_msg = '';
		$filtered = array();
		foreach($messages as $msg){
			if($msg['role'] === 'system'){
				$system_msg .= $msg['content'] . "\n";
			}else{
				$filtered[] = $msg;
			}
		}

		$payload = array(
			'model' => $this->model,
			'messages' => $filtered,
			'max_tokens' => intval(isset($options['max_tokens']) ? $options['max_tokens'] : 8192)
		);

		if(!empty($system_msg)){
			$payload['system'] = trim($system_msg);
		}

		if(!empty($tools)){
			$payload['tools'] = array_map(function($t){
				return array(
					'name' => $t['name'],
					'description' => $t['description'],
					'input_schema' => $t['parameters']
				);
			}, $tools);
		}

		if(!empty($options['temperature'])){
			$payload['temperature'] = floatval($options['temperature']);
		}

		return $payload;
	}

	private function anthropic_parse_response($body){
		$data = @json_decode($body, true);
		if(!$data) return array('error' => 'Invalid JSON response');
		if(!empty($data['error'])) return array('error' => isset($data['error']['message']) ? $data['error']['message'] : json_encode($data['error']));

		$text = '';
		$tool_calls = array();
		foreach(isset($data['content']) ? $data['content'] : array() as $block){
			if((isset($block['type']) ? $block['type'] : '') === 'text'){
				$text .= isset($block['text']) ? $block['text'] : '';
			}elseif((isset($block['type']) ? $block['type'] : '') === 'tool_use'){
				$tool_calls[] = array(
					'id' => isset($block['id']) ? $block['id'] : '',
					'name' => isset($block['name']) ? $block['name'] : '',
					'input' => isset($block['input']) ? $block['input'] : array()
				);
			}
		}

		$accumulated = array('text' => $text, 'tool_calls' => $tool_calls, 'usage' => isset($data['usage']) ? $data['usage'] : array());
		return array(
			'content' => $text,
			'tool_calls' => $tool_calls,
			'usage' => isset($data['usage']) ? $data['usage'] : array(),
			'parts' => $this->build_parts($accumulated)
		);
	}

	private function google_chat($messages, $tools, $options){
		$key = $this->api_key;
		$url = 'https://generativelanguage.googleapis.com/v1beta/models/' . $this->model . ':generateContent?key=' . $key;
		$headers = array('Content-Type: application/json');
		$payload = $this->google_build_payload($messages, $tools, $options);

		$response = $this->curl_post($url, $headers, $payload, isset($options['timeout']) ? $options['timeout'] : 120);
		if(!empty($response['error'])) return $response;
		return $this->google_parse_response($response['body']);
	}

	private function google_stream($messages, $tools, $on_event, $options){
		$key = $this->api_key;
		$url = 'https://generativelanguage.googleapis.com/v1beta/models/' . $this->model . ':streamGenerateContent?alt=sse&key=' . $key;
		$headers = array('Content-Type: application/json');
		$payload = $this->google_build_payload($messages, $tools, $options);

		$accumulated = array('text' => '', 'tool_calls' => array(), 'usage' => array());
		$sse_buffer = '';

		$this->curl_post_stream($url, $headers, $payload, function($chunk) use ($on_event, &$accumulated, &$sse_buffer){
			$sse_buffer .= $chunk;
			while(($pos = strpos($sse_buffer, "\n")) !== false){
				$line = substr($sse_buffer, 0, $pos);
				$sse_buffer = substr($sse_buffer, $pos + 1);
				$line = trim($line);
				if(strpos($line, 'data: ') !== 0) continue;
				$data_str = substr($line, 6);
				$data = @json_decode($data_str, true);
				if(!$data) continue;

				if(!empty($data['candidates'][0]['content']['parts'])){
					foreach($data['candidates'][0]['content']['parts'] as $part){
						if(!empty($part['text'])){
							$accumulated['text'] .= $part['text'];
							$on_event(array('type' => 'text_delta', 'text' => $part['text']));
						}
						if(!empty($part['functionCall'])){
							$tc = array(
								'id' => 'gcall_'.count($accumulated['tool_calls']),
								'name' => isset($part['functionCall']['name']) ? $part['functionCall']['name'] : '',
								'input' => isset($part['functionCall']['args']) ? $part['functionCall']['args'] : array()
							);
							$accumulated['tool_calls'][] = $tc;
							$on_event(array('type' => 'tool_call', 'id' => $tc['id'], 'name' => $tc['name'], 'input' => $tc['input']));
						}
					}
				}
				if(!empty($data['usageMetadata'])){
					$accumulated['usage'] = $data['usageMetadata'];
				}
			}
		}, isset($options['timeout']) ? $options['timeout'] : 120);

		$on_event(array('type' => 'done', 'usage' => $accumulated['usage']));
		return array(
			'content' => $accumulated['text'],
			'tool_calls' => $accumulated['tool_calls'],
			'usage' => $accumulated['usage'],
			'parts' => $this->build_parts($accumulated)
		);
	}

	private function google_build_payload($messages, $tools, $options){
		$contents = array();
		$system_instruction = '';

		foreach($messages as $msg){
			$role = isset($msg['role']) ? $msg['role'] : 'user';
			if($role === 'system'){
				$system_instruction .= $msg['content'] . "\n";
				continue;
			}
			if($role === 'tool_result' || $role === 'tool'){
				if(!empty($msg['content'])){
					$contents[] = array(
						'role' => 'function',
						'parts' => array(array('text' => is_string($msg['content']) ? $msg['content'] : json_encode($msg['content'])))
					);
				}
				continue;
			}
			$grole = ($role === 'assistant') ? 'model' : 'user';
			$content = isset($msg['content']) ? $msg['content'] : '';
			if(is_array($content)) $content = json_encode($content);
			$contents[] = array(
				'role' => $grole,
				'parts' => array(array('text' => $content))
			);
		}

		$payload = array('contents' => $contents);

		if(!empty($system_instruction)){
			$payload['systemInstruction'] = array('parts' => array(array('text' => trim($system_instruction))));
		}

		$gen_config = array();
		if(!empty($options['temperature'])) $gen_config['temperature'] = floatval($options['temperature']);
		if(!empty($options['max_tokens'])) $gen_config['maxOutputTokens'] = intval($options['max_tokens']);
		if(!empty($gen_config)) $payload['generationConfig'] = $gen_config;

		if(!empty($tools)){
			$payload['tools'] = array(array('functionDeclarations' => array_map(function($t){
				return array(
					'name' => $t['name'],
					'description' => $t['description'],
					'parameters' => $t['parameters']
				);
			}, $tools)));
		}

		return $payload;
	}

	private function google_parse_response($body){
		$data = @json_decode($body, true);
		if(!$data) return array('error' => 'Invalid JSON response');
		if(!empty($data['error'])) return array('error' => isset($data['error']['message']) ? $data['error']['message'] : json_encode($data['error']));

		$text = '';
		$tool_calls = array();
		$parts = isset($data['candidates'][0]['content']['parts']) ? $data['candidates'][0]['content']['parts'] : array();
		foreach($parts as $part){
			if(!empty($part['text'])) $text .= $part['text'];
			if(!empty($part['functionCall'])){
				$tool_calls[] = array(
					'id' => 'gcall_'.count($tool_calls),
					'name' => isset($part['functionCall']['name']) ? $part['functionCall']['name'] : '',
					'input' => isset($part['functionCall']['args']) ? $part['functionCall']['args'] : array()
				);
			}
		}

		$accumulated = array('text' => $text, 'tool_calls' => $tool_calls, 'usage' => isset($data['usageMetadata']) ? $data['usageMetadata'] : array());
		return array(
			'content' => $text,
			'tool_calls' => $tool_calls,
			'usage' => isset($data['usageMetadata']) ? $data['usageMetadata'] : array(),
			'parts' => $this->build_parts($accumulated)
		);
	}

	private function azure_chat($messages, $tools, $options){
		$config = isset($this->provider_config) ? $this->provider_config : array();
		$resource = isset($config['resource']) ? $config['resource'] : '{resource}';
		$deployment = isset($config['deployment']) ? $config['deployment'] : $this->model;
		$url = "https://{$resource}.openai.azure.com/openai/deployments/{$deployment}/chat/completions?api-version=2024-02-01";
		$headers = array(
			'Content-Type: application/json',
			'api-key: ' . $this->api_key
		);
		$payload = $this->openai_build_payload($messages, $tools, $options);
		$payload['stream'] = false;

		$response = $this->curl_post($url, $headers, $payload, isset($options['timeout']) ? $options['timeout'] : 120);
		if(!empty($response['error'])) return $response;
		return $this->openai_parse_response($response['body']);
	}

	private function azure_stream($messages, $tools, $on_event, $options){
		$config = isset($this->provider_config) ? $this->provider_config : array();
		$resource = isset($config['resource']) ? $config['resource'] : '{resource}';
		$deployment = isset($config['deployment']) ? $config['deployment'] : $this->model;
		$url = "https://{$resource}.openai.azure.com/openai/deployments/{$deployment}/chat/completions?api-version=2024-02-01";
		$headers = array(
			'Content-Type: application/json',
			'api-key: ' . $this->api_key
		);
		$payload = $this->openai_build_payload($messages, $tools, $options);
		$payload['stream'] = true;

		return $this->openai_stream_impl($url, $headers, $payload, $on_event, $options);
	}

	private function openai_stream_impl($url, $headers, $payload, $on_event, $options){
		$accumulated = array('text' => '', 'reasoning' => '', 'tool_calls' => array(), 'usage' => array());
		$tool_call_buffers = array();
		$sse_buffer = '';

		$this->curl_post_stream($url, $headers, $payload, function($chunk) use ($on_event, &$accumulated, &$tool_call_buffers, &$sse_buffer){
			$sse_buffer .= $chunk;
			while(($pos = strpos($sse_buffer, "\n")) !== false){
				$line = substr($sse_buffer, 0, $pos);
				$sse_buffer = substr($sse_buffer, $pos + 1);
				$line = trim($line);
				if(strpos($line, 'data: ') !== 0) continue;
				$data_str = substr($line, 6);
				if(trim($data_str) === '[DONE]') return;
				$data = @json_decode($data_str, true);
				if(!$data) continue;
				if(!empty($data['choices'][0])){
					$choice = $data['choices'][0];
					$delta = isset($choice['delta']) ? $choice['delta'] : array();
					if(!empty($delta['content'])){
						$accumulated['text'] .= $delta['content'];
						$on_event(array('type' => 'text_delta', 'text' => $delta['content']));
					}
					if(!empty($delta['reasoning_content'])){
						$accumulated['reasoning'] .= $delta['reasoning_content'];
						$on_event(array('type' => 'reasoning_delta', 'text' => $delta['reasoning_content']));
					}
					if(!empty($delta['tool_calls'])){
						foreach($delta['tool_calls'] as $tc){
							$idx = isset($tc['index']) ? $tc['index'] : 0;
							if(!isset($tool_call_buffers[$idx])){
								$tool_call_buffers[$idx] = array('id' => '', 'name' => '', 'arguments' => '');
							}
							if(!empty($tc['id'])) $tool_call_buffers[$idx]['id'] = $tc['id'];
							if(!empty($tc['function']['name'])) $tool_call_buffers[$idx]['name'] = $tc['function']['name'];
							if(!empty($tc['function']['arguments'])) $tool_call_buffers[$idx]['arguments'] .= $tc['function']['arguments'];
						}
					}
				}
				if(!empty($data['usage'])) $accumulated['usage'] = $data['usage'];
			}
		}, isset($options['timeout']) ? $options['timeout'] : 120);

		if(!empty($tool_call_buffers)){
			foreach($tool_call_buffers as $i => $tc){
				$args = @json_decode($tc['arguments'], true);
				if($args === null) $args = array();
				$tool_call = array('id' => $tc['id'] ? $tc['id'] : 'call_'.$i, 'name' => $tc['name'], 'input' => $args);
				$accumulated['tool_calls'][] = $tool_call;
				$on_event(array('type' => 'tool_call', 'id' => $tool_call['id'], 'name' => $tool_call['name'], 'input' => $args));
			}
		}

		$on_event(array('type' => 'done', 'usage' => $accumulated['usage']));
		return array(
			'content' => $accumulated['text'],
			'tool_calls' => $accumulated['tool_calls'],
			'usage' => $accumulated['usage'],
			'parts' => $this->build_parts($accumulated)
		);
	}

	private function build_parts($accumulated){
		$parts = array();
		if(!empty($accumulated['reasoning'])){
			$parts[] = array('type' => 'reasoning', 'text' => $accumulated['reasoning']);
		}
		if(!empty($accumulated['text'])){
			$parts[] = array('type' => 'text', 'text' => $accumulated['text']);
		}
		foreach($accumulated['tool_calls'] as $tc){
			$parts[] = array('type' => 'tool_use', 'id' => $tc['id'], 'name' => $tc['name'], 'input' => $tc['input']);
		}
		return $parts;
	}

	private function curl_post($url, $headers, $payload, $timeout = 120){
		$ch = curl_init($url);
		curl_setopt_array($ch, array(
			CURLOPT_HTTPHEADER => $headers,
			CURLOPT_POST => true,
			CURLOPT_POSTFIELDS => json_encode($payload),
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_TIMEOUT => $timeout,
			CURLOPT_SSL_VERIFYPEER => false,
			CURLOPT_CONNECTTIMEOUT => 10
		));

		$body = curl_exec($ch);
		$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
		$curl_error = curl_error($ch);
		curl_close($ch);

		if($body === false){
			return array('error' => 'cURL error: ' . $curl_error);
		}

		if($http_code >= 400){
			$err_data = @json_decode($body, true);
			if(!empty($err_data['error']['message'])){
				return array('error' => $err_data['error']['message']);
			}
			if(!empty($err_data['error'])){
				$e = $err_data['error'];
				$msg = is_string($e) ? $e : (isset($e['message']) ? $e['message'] : json_encode($e));
				return array('error' => $msg);
			}
			return array('error' => "HTTP {$http_code}: " . mb_substr($body, 0, 500));
		}

		return array('body' => $body);
	}

	private function curl_post_stream($url, $headers, $payload, $on_chunk, $timeout = 120){
		$ch = curl_init($url);
		curl_setopt_array($ch, array(
			CURLOPT_HTTPHEADER => $headers,
			CURLOPT_POST => true,
			CURLOPT_POSTFIELDS => json_encode($payload),
			CURLOPT_RETURNTRANSFER => false,
			CURLOPT_TIMEOUT => $timeout,
			CURLOPT_SSL_VERIFYPEER => false,
			CURLOPT_CONNECTTIMEOUT => 10,
			CURLOPT_WRITEFUNCTION => function($ch, $data) use ($on_chunk){
				$on_chunk($data);
				return strlen($data);
			}
		));
		curl_exec($ch);
		curl_close($ch);
	}
}
