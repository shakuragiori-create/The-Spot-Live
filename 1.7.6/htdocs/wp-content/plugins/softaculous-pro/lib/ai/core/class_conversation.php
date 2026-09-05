<?php

if(!defined('SOFTACULOUS')){
	die('Hacking Attempt');
}

require_once(__DIR__ . '/class_ai_file_handler.php');

class AIConversation {
	private $file_path;
	private $data;

	private function __construct($file_path, array $data = array()){
		$this->file_path = $file_path;
		$this->data = $data;
	}

	public static function create($file_path, $project_path, $id = ''){
		AIFileHandler::ensure_dir(dirname($file_path));
		$file_path = preg_replace('/\.json$/', '.json.php', $file_path);

		if(empty($id)){
			$id = 'conv_' . substr(md5(uniqid(mt_rand(), true)), 0, 12);
		}

		$data = array(
			'id' => $id,
			'title' => '',
			'project_path' => $project_path,
			'mode' => 'build',
			'todos' => array(),
			'created_at' => time(),
			'updated_at' => time(),
			'messages' => array()
		);

		$conv = new self($file_path, $data);
		$conv->save();
		return $conv;
	}

	public static function load($file_path){
		$file_path = preg_replace('/\.json$/', '.json.php', $file_path);
		$data = AIFileHandler::read($file_path);
		if($data === null) return null;
		return new self($file_path, $data);
	}

	public static function delete($file_path){
		$file_path = preg_replace('/\.json$/', '.json.php', $file_path);
		return AIFileHandler::delete($file_path);
	}

	public static function list_for_project($dir){
		AIFileHandler::ensure_dir($dir);
		$files = AIFileHandler::list_files($dir, 'conv_*.json.php');
		$sessions = array();
		foreach($files as $f){
			$data = AIFileHandler::read($f);
			if(!empty($data)){
				$sessions[] = array(
					'id' => $data['id'],
					'title' => !empty($data['title']) ? $data['title'] : 'Untitled',
					'mode' => isset($data['mode']) ? $data['mode'] : 'build',
					'message_count' => isset($data['messages']) ? count($data['messages']) : 0,
					'created_at' => isset($data['created_at']) ? $data['created_at'] : 0,
					'updated_at' => isset($data['updated_at']) ? $data['updated_at'] : 0
				);
			}
		}
		usort($sessions, function($a, $b){ return (isset($b['updated_at']) ? $b['updated_at'] : 0) - (isset($a['updated_at']) ? $a['updated_at'] : 0); });
		return $sessions;
	}

	public function add_user_message($content, array $attachments = array()){
		$msg = array(
			'id' => 'msg_' . substr(md5(uniqid(mt_rand(), true)), 0, 10),
			'role' => 'user',
			'content' => $content,
			'attachments' => $attachments,
			'time' => time()
		);
		$this->data['messages'][] = $msg;
		$this->data['updated_at'] = time();
		return $msg['id'];
	}

	public function add_assistant_content(array $parts, $model = '', array $usage = array()){
		$msg = array(
			'id' => 'msg_' . substr(md5(uniqid(mt_rand(), true)), 0, 10),
			'role' => 'assistant',
			'parts' => $parts,
			'model' => $model,
			'usage' => $usage,
			'time' => time()
		);
		$this->data['messages'][] = $msg;
		$this->data['updated_at'] = time();
		return $msg['id'];
	}

	public function add_tool_result($tool_call_id, $content, $is_error = false){
		$this->data['messages'][] = array(
			'role' => 'tool_result',
			'tool_call_id' => $tool_call_id,
			'content' => $content,
			'is_error' => $is_error,
			'time' => time()
		);
		$this->data['updated_at'] = time();
	}

	public function get_for_api(){
		$messages = array();

		foreach($this->data['messages'] as $msg){
			$role = isset($msg['role']) ? $msg['role'] : '';

			if($role === 'user'){
				$messages[] = array('role' => 'user', 'content' => isset($msg['content']) ? $msg['content'] : '');
			}elseif($role === 'assistant'){
				$parts = isset($msg['parts']) ? $msg['parts'] : array();
				$text_parts = array();
				$tool_calls = array();

				foreach($parts as $part){
					$part_type = isset($part['type']) ? $part['type'] : '';
					if($part_type === 'text' && !empty($part['text'])){
						$text_parts[] = $part['text'];
					}elseif($part_type === 'tool_use'){
						$tool_calls[] = $part;
					}
				}

				if(!empty($tool_calls)){
					$content = '';
					if(!empty($text_parts)) $content = implode("\n", $text_parts);

					$assistant_msg = array('role' => 'assistant');
					if(!empty($content)) $assistant_msg['content'] = $content;
					else $assistant_msg['content'] = null;

					$assistant_msg['tool_calls'] = array_map(function($tc, $i){
						return array(
							'id' => isset($tc['id']) ? $tc['id'] : 'call_'.$i,
							'type' => 'function',
							'function' => array(
								'name' => isset($tc['name']) ? $tc['name'] : '',
								'arguments' => json_encode(isset($tc['input']) ? $tc['input'] : array())
							)
						);
					}, $tool_calls, array_keys($tool_calls));

					$messages[] = $assistant_msg;
				}else{
					$messages[] = array('role' => 'assistant', 'content' => implode("\n", $text_parts));
				}

			}elseif($role === 'tool_result'){
				$messages[] = array(
					'role' => 'tool',
					'tool_call_id' => isset($msg['tool_call_id']) ? $msg['tool_call_id'] : '',
					'content' => isset($msg['content']) ? $msg['content'] : ''
				);
			}
		}

		return $messages;
	}

	public function get_for_api_anthropic(){
		$messages = array();

		foreach($this->data['messages'] as $msg){
			$role = isset($msg['role']) ? $msg['role'] : '';

			if($role === 'user'){
				$messages[] = array('role' => 'user', 'content' => isset($msg['content']) ? $msg['content'] : '');
			}elseif($role === 'assistant'){
				$parts = isset($msg['parts']) ? $msg['parts'] : array();
				$content = array();

				foreach($parts as $part){
					$part_type = isset($part['type']) ? $part['type'] : '';
					if($part_type === 'text' && !empty($part['text'])){
						$content[] = array('type' => 'text', 'text' => $part['text']);
					}elseif($part_type === 'tool_use'){
						$content[] = array(
							'type' => 'tool_use',
							'id' => isset($part['id']) ? $part['id'] : '',
							'name' => isset($part['name']) ? $part['name'] : '',
							'input' => isset($part['input']) ? $part['input'] : array()
						);
					}
				}

				$messages[] = array('role' => 'assistant', 'content' => $content);

			}elseif($role === 'tool_result'){
				$messages[] = array(
					'role' => 'user',
					'content' => array(array(
						'type' => 'tool_result',
						'tool_use_id' => isset($msg['tool_call_id']) ? $msg['tool_call_id'] : '',
						'content' => isset($msg['content']) ? $msg['content'] : ''
					))
				);
			}
		}

		return $messages;
	}

	public function get_for_api_google(){
		$contents = array();

		foreach($this->data['messages'] as $msg){
			$role = isset($msg['role']) ? $msg['role'] : '';

			if($role === 'user' && isset($msg['content']) && is_string($msg['content'])){
				$contents[] = array('role' => 'user', 'parts' => array(array('text' => $msg['content'])));
			}elseif($role === 'assistant'){
				$parts = isset($msg['parts']) ? $msg['parts'] : array();
				$g_parts = array();
				foreach($parts as $part){
					$part_type = isset($part['type']) ? $part['type'] : '';
					if($part_type === 'text' && !empty($part['text'])){
						$g_parts[] = array('text' => $part['text']);
					}elseif($part_type === 'tool_use'){
						$g_parts[] = array('functionCall' => array('name' => isset($part['name']) ? $part['name'] : '', 'args' => isset($part['input']) ? $part['input'] : array()));
					}
				}
				if(!empty($g_parts)){
					$contents[] = array('role' => 'model', 'parts' => $g_parts);
				}
			}elseif($role === 'tool_result'){
				$contents[] = array(
					'role' => 'function',
					'parts' => array(array('text' => isset($msg['content']) ? $msg['content'] : ''))
				);
			}
		}

		return $contents;
	}

	public function get_all(){
		return $this->data;
	}

	public function get_messages(){
		return isset($this->data['messages']) ? $this->data['messages'] : array();
	}

	public function get_id(){
		return isset($this->data['id']) ? $this->data['id'] : '';
	}

	public function get_title(){
		return isset($this->data['title']) ? $this->data['title'] : '';
	}

	public function set_title($title){
		$this->data['title'] = $title;
	}

	public function get_mode(){
		return isset($this->data['mode']) ? $this->data['mode'] : 'build';
	}

	public function set_mode($mode){
		$this->data['mode'] = $mode;
	}

	public function get_todos(){
		return isset($this->data['todos']) ? $this->data['todos'] : array();
	}

	public function set_todos(array $todos){
		$this->data['todos'] = $todos;
	}

	public function count_messages(){
		return isset($this->data['messages']) ? count($this->data['messages']) : 0;
	}

	public function count_user_messages(){
		$count = 0;
		$msgs = isset($this->data['messages']) ? $this->data['messages'] : array();
		foreach($msgs as $msg){
			if((isset($msg['role']) ? $msg['role'] : '') === 'user') $count++;
		}
		return $count;
	}

	public function get_token_estimate(){
		$total_text = '';
		$msgs = isset($this->data['messages']) ? $this->data['messages'] : array();
		foreach($msgs as $msg){
			if(!empty($msg['content']) && is_string($msg['content'])){
				$total_text .= $msg['content'] . ' ';
			}
			if(!empty($msg['parts']) && is_array($msg['parts'])){
				foreach($msg['parts'] as $part){
					if(!empty($part['text'])) $total_text .= $part['text'] . ' ';
					if(!empty($part['input'])) $total_text .= json_encode($part['input']) . ' ';
				}
			}
		}
		return intval(strlen($total_text) / 4);
	}

	public function compact($keep_last_n = 4){
		$msgs = $this->data['messages'];
		$user_msgs = array();
		foreach($msgs as $i => $msg){
			if((isset($msg['role']) ? $msg['role'] : '') === 'user') $user_msgs[] = $i;
		}

		if(count($user_msgs) <= $keep_last_n) return;

		$keep_from = $user_msgs[max(0, count($user_msgs) - $keep_last_n)];

		$removed = array_slice($msgs, 0, $keep_from);
		$summary_parts = array();
		$task_summary = array();
		foreach($removed as $msg){
			$role = isset($msg['role']) ? $msg['role'] : '';
			if($role === 'user' && !empty($msg['content'])){
				$summary_parts[] = 'User: ' . mb_substr($msg['content'], 0, 150);
			}elseif($role === 'assistant'){
				$parts = isset($msg['parts']) ? $msg['parts'] : array();
				foreach($parts as $part){
					if(($part['type'] ?? '') === 'tool_use'){
						$task_summary[] = 'Used ' . ($part['name'] ?? 'tool') . ': ' . mb_substr(json_encode($part['input'] ?? []), 0, 80);
					}
				}
				$text = '';
				foreach($parts as $part){
					if(($part['type'] ?? '') === 'text' && !empty($part['text'])) $text .= $part['text'];
				}
				if($text) $summary_parts[] = 'Assistant: ' . mb_substr($text, 0, 150);
			}
		}

		$full_summary = implode("\n", array_merge($summary_parts, array_slice($task_summary, 0, 10)));

		$this->data['messages'] = array_slice($msgs, $keep_from);
		if(!empty($full_summary)){
			array_unshift($this->data['messages'], array(
				'id' => 'msg_compact',
				'role' => 'user',
				'content' => "[Previous conversation summary]:\n" . $full_summary,
				'time' => time()
			));
		}
	}

	public function clear(){
		$this->data['messages'] = array();
		$this->data['todos'] = array();
		$this->data['updated_at'] = time();
	}

	public function truncate_after_message($message_id){
		$found = false;
		$kept = array();
		foreach($this->data['messages'] as $msg){
			if($found) break;
			$kept[] = $msg;
			if(isset($msg['id']) && $msg['id'] === $message_id){
				$found = true;
			}
		}
		$this->data['messages'] = $kept;
		$this->data['updated_at'] = time();
		return $found;
	}

	public function edit_message($message_id, $new_content){
		foreach($this->data['messages'] as $i => $msg){
			if(isset($msg['id']) && $msg['id'] === $message_id){
				$this->data['messages'][$i]['content'] = $new_content;
				$this->data['updated_at'] = time();
				return true;
			}
		}
		return false;
	}

	public function get_last_assistant_message_id(){
		$messages = $this->data['messages'];
		for($i = count($messages) - 1; $i >= 0; $i--){
			if(($messages[$i]['role'] ?? '') === 'assistant'){
				return $messages[$i]['id'] ?? null;
			}
		}
		return null;
	}

	public function get_last_user_message_id(){
		$messages = $this->data['messages'];
		for($i = count($messages) - 1; $i >= 0; $i--){
			if(($messages[$i]['role'] ?? '') === 'user'){
				return $messages[$i]['id'] ?? null;
			}
		}
		return null;
	}

	public function save(){
		$this->data['updated_at'] = time();
		AIFileHandler::ensure_dir(dirname($this->file_path));
		return AIFileHandler::write($this->file_path, $this->data);
	}
}