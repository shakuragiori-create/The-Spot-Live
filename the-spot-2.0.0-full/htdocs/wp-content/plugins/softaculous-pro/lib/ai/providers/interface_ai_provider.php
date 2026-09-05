<?php

if(!defined('SOFTACULOUS')){
	die('Hacking Attempt');
}

interface AIProvider {
	public function get_id();
	public function get_name();
	public function get_logo();
	public function get_api_endpoint();
	public function get_auth_type();
	public function get_supported_models();
	public function get_default_model();
	public function get_context_window();
	public function supports_streaming();
	public function validate_config(array $config);
	public function get_chat_url();
	public function build_headers(array $config);
	public function build_payload(array $messages, $model, array $options = array());
	public function parse_stream_chunk($chunk);
	public function parse_response($response);
	public function get_error_message(array $response);
}

abstract class BaseAIProvider implements AIProvider {
	protected $id;
	protected $name;
	protected $logo;
	protected $api_endpoint;
	protected $auth_type = 'api_key';
	protected $default_model;
	protected $models = array();
	protected $context_window = 128000;
	protected $supports_streaming = true;

	public function get_id() {
		return $this->id;
	}

	public function get_name() {
		return $this->name;
	}

	public function get_logo() {
		return $this->logo;
	}

	public function get_api_endpoint() {
		return $this->api_endpoint;
	}

	public function get_auth_type() {
		return $this->auth_type;
	}

	public function get_supported_models() {
		return $this->models;
	}

	public function get_default_model() {
		return $this->default_model;
	}

	public function get_context_window() {
		return $this->context_window;
	}

	public function supports_streaming() {
		return $this->supports_streaming;
	}

	public function validate_config(array $config) {
		$errors = array();
		if ($this->auth_type === 'api_key' && empty($config['api_key'])) {
			$errors[] = 'API key is required';
		}
		return $errors;
	}

	public function get_chat_url() {
		return rtrim($this->api_endpoint, '/') . '/chat/completions';
	}

	public function build_headers(array $config) {
		$headers = array(
			'Content-Type: application/json',
			'Accept: application/json'
		);

		if ($this->auth_type === 'api_key' && !empty($config['api_key'])) {
			$headers[] = 'Authorization: Bearer ' . $config['api_key'];
		}

		return $headers;
	}

	public function build_payload(array $messages, $model, array $options = array()) {
		$payload = array(
			'model' => $model,
			'messages' => $messages,
			'stream' => !empty($options['stream'])
		);

		if (!empty($options['temperature'])) {
			$payload['temperature'] = floatval($options['temperature']);
		}

		if (!empty($options['max_tokens'])) {
			$payload['max_tokens'] = intval($options['max_tokens']);
		}

		return $payload;
	}

	public function parse_stream_chunk($chunk) {
		$data = json_decode($chunk, true);
		if (empty($data['choices'][0]['delta']['content'])) {
			return array();
		}
		return array(
			'content' => $data['choices'][0]['delta']['content'],
			'done' => !empty($data['choices'][0]['finish_reason'])
		);
	}

	public function parse_response($response) {
		$data = json_decode($response, true);
		if (empty($data['choices'][0]['message']['content'])) {
			return array('error' => 'Invalid response format');
		}
		return array(
			'content' => $data['choices'][0]['message']['content'],
			'usage' => !empty($data['usage']) ? $data['usage'] : null
		);
	}

	public function get_error_message(array $response) {
		if (!empty($response['error']['message'])) {
			return $response['error']['message'];
		}
		if (!empty($response['message'])) {
			return $response['message'];
		}
		return 'Unknown error occurred';
	}
}