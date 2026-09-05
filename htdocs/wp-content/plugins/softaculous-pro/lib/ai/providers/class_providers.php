<?php

if(!defined('SOFTACULOUS')){
	die('Hacking Attempt');
}

require_once(__DIR__ . '/interface_ai_provider.php');
require_once(__DIR__ . '/../core/class_ai_file_handler.php');

class OpenAIProvider extends BaseAIProvider {
	protected $id = 'openai';
	protected $name = 'OpenAI';
	protected $logo = 'openai.svg';
	protected $api_endpoint = 'https://api.openai.com/v1';
	protected $auth_type = 'api_key';
	protected $default_model = 'gpt-4o';
	protected $models = array(
		'gpt-4o' => array('name' => 'GPT-4o', 'context_window' => 128000),
		'gpt-4o-mini' => array('name' => 'GPT-4o Mini', 'context_window' => 128000),
		'gpt-4-turbo' => array('name' => 'GPT-4 Turbo', 'context_window' => 128000),
		'gpt-3.5-turbo' => array('name' => 'GPT-3.5 Turbo', 'context_window' => 16385)
	);
	protected $context_window = 128000;
	protected $supports_streaming = true;
}

class AnthropicProvider extends BaseAIProvider {
	protected $id = 'anthropic';
	protected $name = 'Anthropic';
	protected $logo = 'anthropic.svg';
	protected $api_endpoint = 'https://api.anthropic.com/v1';
	protected $auth_type = 'api_key';
	protected $default_model = 'claude-sonnet-4-20250514';
	protected $models = array(
		'claude-opus-4-20250514' => array('name' => 'Claude Opus 4', 'context_window' => 200000),
		'claude-sonnet-4-20250514' => array('name' => 'Claude Sonnet 4', 'context_window' => 200000),
		'claude-3-5-sonnet-20241022' => array('name' => 'Claude 3.5 Sonnet', 'context_window' => 200000),
		'claude-3-5-haiku-20241022' => array('name' => 'Claude 3.5 Haiku', 'context_window' => 200000),
		'claude-3-opus-20240229' => array('name' => 'Claude 3 Opus', 'context_window' => 200000),
		'claude-3-sonnet-20240229' => array('name' => 'Claude 3 Sonnet', 'context_window' => 200000),
		'claude-3-haiku-20240307' => array('name' => 'Claude 3 Haiku', 'context_window' => 200000)
	);
	protected $context_window = 200000;
	protected $supports_streaming = true;

	public function get_chat_url() {
		return 'https://api.anthropic.com/v1/messages';
	}

	public function build_headers(array $config) {
		$api_key = isset($config['api_key']) ? $config['api_key'] : '';
		$headers = array(
			'Content-Type: application/json',
			'x-api-key: ' . $api_key,
			'anthropic-version: 2023-06-01',
			'Accept: application/json'
		);
		return $headers;
	}

	public function build_payload(array $messages, $model, array $options = array()) {
		$system_message = '';
		$filtered_messages = array();

		foreach ($messages as $msg) {
			if ($msg['role'] === 'system') {
				$system_message .= $msg['content'] . "\n";
			} else {
				$filtered_messages[] = $msg;
			}
		}

		$payload = array(
			'model' => $model,
			'messages' => $filtered_messages,
			'stream' => !empty($options['stream'])
		);

		if (!empty($system_message)) {
			$payload['system'] = trim($system_message);
		}

		if (!empty($options['temperature'])) {
			$payload['temperature'] = floatval($options['temperature']);
		}

		if (!empty($options['max_tokens'])) {
			$payload['max_tokens'] = intval($options['max_tokens']);
		} else {
			$payload['max_tokens'] = 4096;
		}

		return $payload;
	}

	public function parse_stream_chunk($chunk) {
		if (strpos($chunk, 'data: ') === 0) {
			$json_str = substr($chunk, 6);
			if (trim($json_str) === '[DONE]') {
				return array('done' => true);
			}
			$data = json_decode($json_str, true);
			if (!empty($data['type']) && $data['type'] === 'content_block_delta') {
				if (!empty($data['delta']['text'])) {
					return array(
						'content' => $data['delta']['text'],
						'done' => false
					);
				}
			}
		}
		return array();
	}

	public function parse_response($response) {
		$data = json_decode($response, true);
		if (!empty($data['error'])) {
			$error_msg = isset($data['error']['message']) ? $data['error']['message'] : 'Unknown error';
			return array('error' => $error_msg);
		}
		if (!empty($data['content'][0]['text'])) {
			return array(
				'content' => $data['content'][0]['text'],
				'usage' => !empty($data['usage']) ? $data['usage'] : null
			);
		}
		return array('error' => 'Invalid response format');
	}

	public function get_error_message(array $response) {
		if (!empty($response['error']['message'])) {
			return $response['error']['message'];
		}
		return 'Unknown error occurred';
	}
}

class GoogleProvider extends BaseAIProvider {
	protected $id = 'google';
	protected $name = 'Google';
	protected $logo = 'google.svg';
	protected $api_endpoint = 'https://generativelanguage.googleapis.com/v1';
	protected $auth_type = 'api_key';
	protected $default_model = 'gemini-2.5-pro';
	protected $models = array(
		'gemini-2.5-pro' => array('name' => 'Gemini 2.5 Pro', 'context_window' => 1000000),
		'gemini-2.5-flash' => array('name' => 'Gemini 2.5 Flash', 'context_window' => 1000000),
		'gemini-1.5-pro' => array('name' => 'Gemini 1.5 Pro', 'context_window' => 1000000),
		'gemini-1.5-flash' => array('name' => 'Gemini 1.5 Flash', 'context_window' => 1000000),
		'gemini-1.5-flash-8b' => array('name' => 'Gemini 1.5 Flash 8B', 'context_window' => 1000000)
	);
	protected $context_window = 1000000;
	protected $supports_streaming = true;

	public function get_chat_url() {
		return 'https://generativelanguage.googleapis.com/v1beta/models';
	}

	public function build_headers(array $config) {
		$headers = array(
			'Content-Type: application/json'
		);
		return $headers;
	}

	public function get_chat_url_with_model($model, array $config) {
		$key = !empty($config['api_key']) ? '?key=' . $config['api_key'] : '';
		return 'https://generativelanguage.googleapis.com/v1beta/models/' . $model . ':generateContent' . $key;
	}

	public function build_payload(array $messages, $model, array $options = array()) {
		$contents = array();
		foreach ($messages as $msg) {
			$role = ($msg['role'] === 'assistant') ? 'model' : 'user';
			$contents[] = array(
				'role' => $role,
				'parts' => array(array('text' => $msg['content']))
			);
		}

		$payload = array(
			'contents' => $contents,
			'generationConfig' => array(
				'temperature' => !empty($options['temperature']) ? floatval($options['temperature']) : 0.9,
				'maxOutputTokens' => !empty($options['max_tokens']) ? intval($options['max_tokens']) : 8192
			)
		);

		return $payload;
	}

	public function parse_stream_chunk($chunk) {
		$data = json_decode($chunk, true);
		if (!empty($data['candidates'][0]['content']['parts'][0]['text'])) {
			return array(
				'content' => $data['candidates'][0]['content']['parts'][0]['text'],
				'done' => !empty($data['candidates'][0]['finishReason'])
			);
		}
		return array();
	}

	public function parse_response($response) {
		$data = json_decode($response, true);
		if (!empty($data['error'])) {
			$error_msg = isset($data['error']['message']) ? $data['error']['message'] : 'Unknown error';
			return array('error' => $error_msg);
		}
		if (!empty($data['candidates'][0]['content']['parts'][0]['text'])) {
			return array(
				'content' => $data['candidates'][0]['content']['parts'][0]['text'],
				'usage' => !empty($data['usageMetadata']) ? $data['usageMetadata'] : null
			);
		}
		return array('error' => 'Invalid response format');
	}
}

class OpenRouterProvider extends BaseAIProvider {
	protected $id = 'openrouter';
	protected $name = 'OpenRouter';
	protected $logo = 'openrouter.svg';
	protected $api_endpoint = 'https://openrouter.ai/api/v1';
	protected $auth_type = 'api_key';
	protected $default_model = 'anthropic/claude-3.5-sonnet';
	protected $models = array(); // Dynamic
	protected $context_window = 128000;
	protected $supports_streaming = true;

	public function get_supported_models() {
		if (!empty($this->models)) {
			return $this->models;
		}
		return array(
			'anthropic/claude-3.5-sonnet' => array('name' => 'Claude 3.5 Sonnet'),
			'openai/gpt-4o' => array('name' => 'GPT-4o'),
			'openai/gpt-4o-mini' => array('name' => 'GPT-4o Mini'),
			'meta-llama/llama-3-70b' => array('name' => 'Llama 3 70B'),
			'google/gemini-2.5-pro' => array('name' => 'Gemini 2.5 Pro')
		);
	}
}

class OllamaProvider extends BaseAIProvider {
	protected $id = 'ollama';
	protected $name = 'Ollama (Local)';
	protected $logo = 'ollama.svg';
	protected $api_endpoint = 'http://127.0.0.1:11434/v1';
	protected $auth_type = 'none';
	protected $default_model = 'llama3';
	protected $models = array(); // Dynamic from server
	protected $context_window = 4096;
	protected $supports_streaming = true;

	public function get_supported_models() {
		return $this->models;
	}

	public function fetch_models_from_server(array $config) {
		$url = 'http://127.0.0.1:11434/api/tags';
		$ch = curl_init($url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_TIMEOUT, 5);
		$response = curl_exec($ch);
		curl_close($ch);

		if ($response) {
			$data = json_decode($response, true);
			if (!empty($data['models'])) {
				$models = array();
				foreach ($data['models'] as $model) {
					$name = $model['name'];
					$models[$name] = array('name' => $name);
				}
				ksort($models);
				$this->models = $models;
			}
		}
		return $this->models;
	}
}

class OllamaCloudProvider extends BaseAIProvider {
	protected $id = 'ollama_cloud';
	protected $name = 'Ollama Cloud';
	protected $logo = 'ollama.svg';
	protected $api_endpoint = 'https://ollama.com/v1';
	protected $auth_type = 'api_key';
	protected $default_model = 'llama3';
	protected $models = array(
		'llama3' => array('name' => 'Llama 3', 'context_window' => 128000),
		'codellama' => array('name' => 'Code Llama', 'context_window' => 128000),
		'mistral' => array('name' => 'Mistral', 'context_window' => 128000),
	);
	protected $context_window = 128000;
	protected $supports_streaming = true;

	private function get_cache_file() {
		$dir = AIFileHandler::get_ai_base_dir('') . '/provider';
		return $dir . '/' . $this->id . '.json.php';
	}

	private function load_from_cache() {
		$file = $this->get_cache_file();
		$data = AIFileHandler::read($file);
		if (empty($data) || empty($data['fetched_at']) || empty($data['models'])) {
			return null;
		}
		if (time() - intval($data['fetched_at']) > 86400) {
			return null;
		}
		return $data['models'];
	}

	private function save_to_cache(array $models) {
		$file = $this->get_cache_file();
		AIFileHandler::write($file, array(
			'fetched_at' => time(),
			'models' => $models
		));
	}

	public function fetch_models_from_server(array $config = array()) {
		$base_url = !empty($config['base_url']) ? rtrim($config['base_url'], '/') : rtrim($this->api_endpoint, '/');
		$url = $base_url . '/models';
		$ch = curl_init($url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_TIMEOUT, 10);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
		if (!empty($config['api_key'])) {
			curl_setopt($ch, CURLOPT_HTTPHEADER, array('Authorization: Bearer ' . $config['api_key']));
		}
		$response = curl_exec($ch);
		$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
		curl_close($ch);

		if ($response === false || $http_code !== 200) {
			return array();
		}

		$data = json_decode($response, true);
		if (empty($data) || !is_array($data)) {
			return array();
		}

		$raw_models = array();
		if (!empty($data['data']) && is_array($data['data'])) {
			$raw_models = $data['data'];
		} elseif (!empty($data['models']) && is_array($data['models'])) {
			$raw_models = $data['models'];
		}

		if (empty($raw_models)) {
			return array();
		}

		$models = array();
		foreach ($raw_models as $model) {
			if (empty($model['id'])) {
				continue;
			}
			$mid = $model['id'];
			$models[$mid] = array(
				'name' => !empty($model['name']) ? $model['name'] : $mid,
				'context_window' => !empty($model['context_window']) ? intval($model['context_window']) : $this->context_window
			);
		}

		ksort($models);

		return $models;
	}

	public function get_supported_models() {
		$cached = $this->load_from_cache();
		if ($cached !== null) {
			return $cached;
		}

		$api_models = $this->fetch_models_from_server();
		if (!empty($api_models)) {
			$this->save_to_cache($api_models);
			return $api_models;
		}

		return $this->models;
	}

	public function get_default_model() {
		$models = $this->get_supported_models();
		if (!empty($models[$this->default_model])) {
			return $this->default_model;
		}
		if (!empty($models)) {
			return array_key_first($models);
		}
		return $this->default_model;
	}
}

class GroqProvider extends BaseAIProvider {
	protected $id = 'groq';
	protected $name = 'Groq';
	protected $logo = 'groq.svg';
	protected $api_endpoint = 'https://api.groq.com/openai/v1';
	protected $auth_type = 'api_key';
	protected $default_model = 'llama-3.3-70b';
	protected $models = array(
		'llama-3.3-70b' => array('name' => 'Llama 3.3 70B', 'context_window' => 128000),
		'mixtral-8x7b' => array('name' => 'Mixtral 8x7B', 'context_window' => 32000),
		'llama-3.1-8b-instant' => array('name' => 'Llama 3.1 8B Instant', 'context_window' => 128000)
	);
	protected $context_window = 128000;
	protected $supports_streaming = true;
}

class TogetherProvider extends BaseAIProvider {
	protected $id = 'together';
	protected $name = 'Together AI';
	protected $logo = 'together.svg';
	protected $api_endpoint = 'https://api.together.xyz/v1';
	protected $auth_type = 'api_key';
	protected $default_model = 'meta-llama/Llama-3-70b';
	protected $models = array(
		'meta-llama/Llama-3-70b' => array('name' => 'Llama 3 70B'),
		'meta-llama/Llama-3.3-70b' => array('name' => 'Llama 3.3 70B'),
		'deepseek-ai/DeepSeek-R1' => array('name' => 'DeepSeek R1')
	);
	protected $context_window = 128000;
	protected $supports_streaming = true;
}

class DeepSeekProvider extends BaseAIProvider {
	protected $id = 'deepseek';
	protected $name = 'DeepSeek';
	protected $logo = 'deepseek.svg';
	protected $api_endpoint = 'https://api.deepseek.com/v1';
	protected $auth_type = 'api_key';
	protected $default_model = 'deepseek-chat';
	protected $models = array(
		'deepseek-chat' => array('name' => 'DeepSeek Chat', 'context_window' => 128000),
		'deepseek-coder' => array('name' => 'DeepSeek Coder', 'context_window' => 128000)
	);
	protected $context_window = 128000;
	protected $supports_streaming = true;
}

class AzureProvider extends BaseAIProvider {
	protected $id = 'azure';
	protected $name = 'Azure OpenAI';
	protected $logo = 'azure.svg';
	protected $api_endpoint = 'https://{resource}.openai.azure.com/openai/deployments/{deployment}/chat/completions';
	protected $auth_type = 'api_key';
	protected $default_model = 'gpt-4o';
	protected $models = array();
	protected $context_window = 128000;
	protected $supports_streaming = true;

	public function validate_config(array $config) {
		$errors = array();
		if (empty($config['resource'])) {
			$errors[] = 'Azure resource name is required';
		}
		if (empty($config['deployment'])) {
			$errors[] = 'Azure deployment name is required';
		}
		if (empty($config['api_key'])) {
			$errors[] = 'API key is required';
		}
		return $errors;
	}

	public function get_chat_url() {
		return 'https://{resource}.openai.azure.com/openai/deployments/{deployment}/chat/completions?api-version=2024-02-01';
	}

	public function build_headers(array $config) {
		$api_key = isset($config['api_key']) ? $config['api_key'] : '';
		return array(
			'Content-Type: application/json',
			'api-key: ' . $api_key
		);
	}
}

class BedrockProvider extends BaseAIProvider {
	protected $id = 'bedrock';
	protected $name = 'AWS Bedrock';
	protected $logo = 'aws.svg';
	protected $api_endpoint = 'bedrock';
	protected $auth_type = 'aws';
	protected $default_model = 'anthropic.claude-3-sonnet';
	protected $models = array();
	protected $context_window = 200000;
	protected $supports_streaming = true;

	public function validate_config(array $config) {
		$errors = array();
		if (empty($config['region'])) {
			$errors[] = 'AWS region is required';
		}
		if (empty($config['access_key']) && empty($config['use_instance_role'])) {
			$errors[] = 'AWS access key or instance role is required';
		}
		return $errors;
	}
}

class FireworksProvider extends BaseAIProvider {
	protected $id = 'fireworks';
	protected $name = 'Fireworks AI';
	protected $logo = 'fireworks.svg';
	protected $api_endpoint = 'https://api.fireworks.ai/v1';
	protected $auth_type = 'api_key';
	protected $default_model = 'accounts/fireworks/models/llama-3-70b';
	protected $models = array(
		'accounts/fireworks/models/llama-3-70b' => array('name' => 'Llama 3 70B'),
		'accounts/fireworks/models/qwen2-72b' => array('name' => 'Qwen2 72B')
	);
	protected $context_window = 128000;
	protected $supports_streaming = true;
}

class CloudflareProvider extends BaseAIProvider {
	protected $id = 'cloudflare';
	protected $name = 'Cloudflare AI Gateway';
	protected $logo = 'cloudflare.svg';
	protected $api_endpoint = 'https://api.cloudflare.com/client/v4/ai';
	protected $auth_type = 'api_key';
	protected $default_model = '@cf/meta/llama-3.1-8b-instruct';
	protected $models = array(
		'@cf/meta/llama-3.1-8b-instruct' => array('name' => 'Llama 3.1 8B'),
		'@cf/meta/llama-3.1-70b-instruct' => array('name' => 'Llama 3.1 70B')
	);
	protected $context_window = 128000;
	protected $supports_streaming = true;
}

class HuggingFaceProvider extends BaseAIProvider {
	protected $id = 'huggingface';
	protected $name = 'Hugging Face';
	protected $logo = 'huggingface.svg';
	protected $api_endpoint = 'https://api-inference.huggingface.co/v1';
	protected $auth_type = 'api_key';
	protected $default_model = 'meta-llama/Llama-3-70b';
	protected $models = array(); // Dynamic
	protected $context_window = 128000;
	protected $supports_streaming = true;
}

class OpenCodeZenProvider extends BaseAIProvider {
	protected $id = 'opencode_zen';
	protected $name = 'OpenCode Zen (Free)';
	protected $logo = 'opencode.svg';
	protected $api_endpoint = 'https://opencode.ai/zen/v1';
	protected $auth_type = 'none';
	protected $default_model = 'gpt-5-nano';
	protected $models = array(
		'gpt-5-nano' => array('name' => 'GPT-5 Nano', 'context_window' => 128000),
		'grok-code' => array('name' => 'Grok Code Fast 1', 'context_window' => 128000),
		'glm-5-free' => array('name' => 'GLM-5 Free', 'context_window' => 128000),
		'kimi-k2.5-free' => array('name' => 'Kimi K2.5 Free', 'context_window' => 128000),
		'qwen3.6-plus-free' => array('name' => 'Qwen3.6 Plus Free', 'context_window' => 128000),
		'minimax-m2.5-free' => array('name' => 'MiniMax M2.5 Free', 'context_window' => 128000),
	);
	protected $context_window = 128000;
	protected $supports_streaming = true;

	public function __construct($provider_id = 'opencode_zen') {
		$this->id = $provider_id;
		if ($provider_id === 'opencode_zen_premium') {
			$this->name = 'OpenCode Zen (Premium)';
			$this->auth_type = 'api_key';
		}
	}

	private function get_cache_dir() {
		$dir = AIFileHandler::get_ai_base_dir('') . '/provider';
		AIFileHandler::ensure_dir($dir, true);
		return $dir;
	}

	private function get_cache_file($provider_id) {
		return $this->get_cache_dir() . '/' . $provider_id . '.json.php';
	}

	private function load_from_cache() {
		$file = $this->get_cache_file($this->id);
		$data = AIFileHandler::read($file);
		if (empty($data) || empty($data['fetched_at']) || empty($data['models'])) {
			return null;
		}
		if (time() - intval($data['fetched_at']) > 86400) {
			return null;
		}
		return $data['models'];
	}

	private function save_filtered_cache($provider_id, array $models, $timestamp) {
		$file = $this->get_cache_file($provider_id);
		AIFileHandler::write($file, array(
			'fetched_at' => $timestamp,
			'models' => $models
		));
	}

	private function fetch_and_cache_all() {
		$url = rtrim($this->api_endpoint, '/') . '/models';
		$ch = curl_init($url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_TIMEOUT, 10);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
		$response = curl_exec($ch);
		$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
		curl_close($ch);

		if ($response === false || $http_code !== 200) {
			return null;
		}

		$data = json_decode($response, true);
		if (empty($data) || !is_array($data)) {
			return null;
		}

		$raw_models = array();
		if (!empty($data['data']) && is_array($data['data'])) {
			$raw_models = $data['data'];
		} elseif (!empty($data['models']) && is_array($data['models'])) {
			$raw_models = $data['models'];
		}

		if (empty($raw_models)) {
			return null;
		}

		$all_models = array();
		foreach ($raw_models as $model) {
			if (empty($model['id'])) {
				continue;
			}
			$mid = $model['id'];
			$all_models[$mid] = array(
				'name' => !empty($model['name']) ? $model['name'] : $mid,
				'context_window' => !empty($model['context_window']) ? intval($model['context_window']) : $this->context_window
			);
		}

		ksort($all_models);

		$free_models = array();
		$premium_models = array();
		foreach ($all_models as $id => $info) {
			if (strpos($id, 'free') !== false) {
				$free_models[$id] = $info;
			} else {
				$premium_models[$id] = $info;
			}
		}

		$timestamp = time();
		$this->save_filtered_cache('opencode_zen', $free_models, $timestamp);
		$this->save_filtered_cache('opencode_zen_premium', $premium_models, $timestamp);

		return $all_models;
	}

	private function get_fallback_models() {
		$filtered = array();
		foreach ($this->models as $id => $info) {
			if ($this->id === 'opencode_zen' && strpos($id, 'free') !== false) {
				$filtered[$id] = $info;
			} elseif ($this->id === 'opencode_zen_premium' && strpos($id, 'free') === false) {
				$filtered[$id] = $info;
			}
		}
		return $filtered;
	}

	public function get_supported_models() {
		$cached = $this->load_from_cache();
		if ($cached !== null) {
			return $cached;
		}

		$all_models = $this->fetch_and_cache_all();
		if ($all_models !== null) {
			$filtered = array();
			foreach ($all_models as $id => $info) {
				if ($this->id === 'opencode_zen' && strpos($id, 'free') !== false) {
					$filtered[$id] = $info;
				} elseif ($this->id === 'opencode_zen_premium' && strpos($id, 'free') === false) {
					$filtered[$id] = $info;
				}
			}
			return $filtered;
		}

		return $this->get_fallback_models();
	}

	public function get_default_model() {
		$models = $this->get_supported_models();
		if (!empty($models[$this->default_model])) {
			return $this->default_model;
		}
		if (!empty($models)) {
			return array_key_first($models);
		}
		return $this->default_model;
	}
}

class MiniMaxProvider extends BaseAIProvider {
	protected $id = 'minimax';
	protected $name = 'MiniMax';
	protected $logo = 'minimax.svg';
	protected $api_endpoint = 'https://api.minimax.chat/v1';
	protected $auth_type = 'api_key';
	protected $default_model = 'MiniMax-Text-01';
	protected $models = array(
		'MiniMax-Text-01' => array('name' => 'MiniMax-Text-01', 'context_window' => 1048576),
		'MiniMax-M1' => array('name' => 'MiniMax-M1', 'context_window' => 1048576)
	);
	protected $context_window = 1048576;
	protected $supports_streaming = true;
}

class CustomProvider extends BaseAIProvider {
	protected $id = 'custom';
	protected $name = 'Custom Provider';
	protected $logo = 'custom.svg';
	protected $api_endpoint = '';
	protected $auth_type = 'api_key';
	protected $default_model = '';
	protected $models = array();
	protected $context_window = 128000;
	protected $supports_streaming = true;
	protected $custom_config = array();

	public function __construct($base_url = '', $id = 'custom', $config = array()) {
		$this->id = $id;
		$this->api_endpoint = rtrim($base_url, '/');
		$this->custom_config = $config;
		if(!empty($config['models'])){
			$this->models = $config['models'];
			$this->default_model = array_key_first($config['models']);
		}
	}

	public function validate_config(array $config) {
		$errors = array();
		if (empty($config['base_url'])) {
			$errors[] = 'Base URL is required';
		}
		return $errors;
	}

	public function get_chat_url() {
		return rtrim($this->api_endpoint, '/') . '/chat/completions';
	}
}
