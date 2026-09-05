<?php

if(!defined('SOFTACULOUS')){
	die('Hacking Attempt');
}

require_once(__DIR__ . '/class_ai_file_handler.php');

class AISettings {
	private $file_path;
	private $data;

	public function __construct(string $username){
		$dir = AIFileHandler::get_ai_base_dir($username);
		$this->file_path = $dir . '/settings.json.php';

		$json_data = AIFileHandler::read($this->file_path);
		if($json_data === null){
			$this->data = ['providers' => []];
		}else{
			$this->data = $json_data;
		}
	}

	public function get_connected_providers(): array{
		return $this->data['providers'] ?? [];
	}

	public function is_provider_connected(string $provider_id): bool{
		if(!empty($this->data['providers'][$provider_id])) return true;
		$no_key = ['opencode_zen'];
		if(in_array($provider_id, $no_key)) return true;
		if(strpos($provider_id, 'custom:') === 0 && !empty($this->data['providers'][$provider_id])) return true;
		return false;
	}

	public function get_provider_config(string $provider_id): ?array{
		return $this->data['providers'][$provider_id] ?? null;
	}

	public function get_api_key(string $provider_id): string{
		$config = $this->data['providers'][$provider_id] ?? [];
		$key = $config['api_key'] ?? '';
		if(empty($key)){
			$no_key = ['opencode_zen'];
			if(in_array($provider_id, $no_key)) return 'public';
		}
		return $key;
	}

	public function save_provider(string $provider_id, array $config): void{
		$config['connected_at'] = $config['connected_at'] ?? time();
		$this->data['providers'][$provider_id] = $config;
		$this->save();
	}

	public function delete_provider(string $provider_id): void{
		unset($this->data['providers'][$provider_id]);
		$this->save();
	}

	public function get_all_providers(): array{
		require_once(__DIR__ . '/../providers/interface_ai_provider.php');
		require_once(__DIR__ . '/../providers/class_providers.php');

		$builtin = [
			['id' => 'openai', 'name' => 'OpenAI', 'logo' => 'openai', 'auth_type' => 'api_key',
				'models' => ['gpt-4o' => 'GPT-4o', 'gpt-4o-mini' => 'GPT-4o Mini', 'gpt-4-turbo' => 'GPT-4 Turbo']],
			['id' => 'anthropic', 'name' => 'Anthropic', 'logo' => 'anthropic', 'auth_type' => 'api_key',
				'models' => ['claude-sonnet-4-20250514' => 'Claude Sonnet 4', 'claude-opus-4-20250514' => 'Claude Opus 4', 'claude-3-5-sonnet-20241022' => 'Claude 3.5 Sonnet', 'claude-3-5-haiku-20241022' => 'Claude 3.5 Haiku']],
			['id' => 'google', 'name' => 'Google', 'logo' => 'google', 'auth_type' => 'api_key',
				'models' => ['gemini-2.5-pro' => 'Gemini 2.5 Pro', 'gemini-2.5-flash' => 'Gemini 2.5 Flash', 'gemini-1.5-pro' => 'Gemini 1.5 Pro']],
			['id' => 'openrouter', 'name' => 'OpenRouter', 'logo' => 'openrouter', 'auth_type' => 'api_key',
				'models' => ['anthropic/claude-sonnet-4-20250514' => 'Claude Sonnet 4', 'openai/gpt-4o' => 'GPT-4o', 'google/gemini-2.5-pro' => 'Gemini 2.5 Pro']],
			['id' => 'groq', 'name' => 'Groq', 'logo' => 'groq', 'auth_type' => 'api_key',
				'models' => ['llama-3.3-70b-versatile' => 'Llama 3.3 70B', 'llama-3.1-8b-instant' => 'Llama 3.1 8B']],
			['id' => 'deepseek', 'name' => 'DeepSeek', 'logo' => 'deepseek', 'auth_type' => 'api_key',
				'models' => ['deepseek-chat' => 'DeepSeek Chat', 'deepseek-coder' => 'DeepSeek Coder']],
			['id' => 'together', 'name' => 'Together AI', 'logo' => 'together', 'auth_type' => 'api_key',
				'models' => ['meta-llama/Llama-3-70b' => 'Llama 3 70B', 'deepseek-ai/DeepSeek-R1' => 'DeepSeek R1']],
			['id' => 'ollama', 'name' => 'Ollama (Local)', 'logo' => 'ollama', 'auth_type' => 'none',
				'models' => ['llama3' => 'Llama 3', 'codellama' => 'Code Llama', 'mistral' => 'Mistral']],
			['id' => 'ollama_cloud', 'name' => 'Ollama Cloud', 'logo' => 'ollama', 'auth_type' => 'api_key',
				'base_url' => 'https://api.ollama.com/v1',
				'models' => $this->get_flat_models('ollama_cloud')],
			['id' => 'minimax', 'name' => 'MiniMax', 'logo' => 'minimax', 'auth_type' => 'api_key',
				'base_url' => 'https://api.minimax.chat/v1',
				'models' => ['MiniMax-Text-01' => 'MiniMax-Text-01', 'MiniMax-M1' => 'MiniMax-M1']],
			['id' => 'opencode_zen', 'name' => 'OpenCode Zen (Free)', 'logo' => 'opencode', 'auth_type' => 'none',
				'base_url' => 'https://opencode.ai/zen/v1',
				'models' => $this->get_flat_models('opencode_zen'),
				'no_key' => true],
			['id' => 'opencode_zen_premium', 'name' => 'OpenCode Zen (Premium)', 'logo' => 'opencode', 'auth_type' => 'api_key',
				'base_url' => 'https://opencode.ai/zen/v1',
				'extra_headers' => ['HTTP-Referer: https://opencode.ai/', 'X-Title: opencode'],
				'models' => $this->get_flat_models('opencode_zen_premium'),
				'no_key' => false],
			['id' => 'custom', 'name' => 'Custom Provider (OpenAI Compatible)', 'logo' => 'custom', 'auth_type' => 'api_key',
				'is_custom_type' => true, 'models' => [], 'no_key' => false],
		];

		foreach($builtin as &$p){
			if(!empty($p['is_custom_type'])){
				$p['connected'] = false;
			}else{
				$p['connected'] = $this->is_provider_connected($p['id']);
			}
		}

		foreach($this->data['providers'] as $pid => $config){
			if(strpos($pid, 'custom:') === 0){
				$entry = [
					'id' => $pid,
					'name' => $config['name'] ?? 'Custom Provider',
					'logo' => 'custom',
					'auth_type' => 'api_key',
					'models' => $config['models'] ?? [],
					'connected' => true,
					'is_custom' => true
				];
				if(!empty($config['base_url'])){
					$entry['base_url'] = $config['base_url'];
				}
				$builtin[] = $entry;
			}
		}

		return $builtin;
	}

	private function get_flat_models($provider_id) {
		if ($provider_id === 'opencode_zen' || $provider_id === 'opencode_zen_premium') {
			$provider = new OpenCodeZenProvider($provider_id);
		} elseif ($provider_id === 'ollama_cloud') {
			$provider = new OllamaCloudProvider();
		} else {
			return array();
		}
		$models = $provider->get_supported_models();
		$flat = array();
		foreach ($models as $mid => $minfo) {
			$flat[$mid] = is_array($minfo) ? ($minfo['name'] ?? $mid) : $minfo;
		}
		return $flat;
	}

	public function get_all_models(): array{
		$providers = $this->get_all_providers();
		$models = [];
		foreach($providers as $p){
			if(!$p['connected']) continue;
			foreach($p['models'] as $mid => $mname){
				$models[] = ['id' => $mid, 'name' => is_array($mname) ? ($mname['name'] ?? $mid) : $mname, 'provider' => $p['id'], 'provider_name' => $p['name']];
			}
		}
		return $models;
	}

	public function save(): bool{
		return AIFileHandler::write($this->file_path, $this->data);
	}
}
