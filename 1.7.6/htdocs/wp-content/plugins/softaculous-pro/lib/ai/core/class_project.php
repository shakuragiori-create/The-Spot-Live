<?php

if(!defined('SOFTACULOUS')){
	die('Hacking Attempt');
}

require_once(__DIR__ . '/class_ai_file_handler.php');

class AIProject {

	public static function get_projects_dir($username){
		$base_dir = AIFileHandler::get_ai_base_dir($username);
		$dir = $base_dir . '/projects';
		AIFileHandler::ensure_dir($dir, true);
		return $dir;
	}

	public static function get_project_file($username, $project_id){
		return self::get_projects_dir($username) . '/' . $project_id . '.json.php';
	}

	public static function generate_project_id($username, $project_path){
		return $username . '_' . substr(md5($project_path), 0, 12);
	}

	public static function load($username, $project_id){
		$file = self::get_project_file($username, $project_id);
		return AIFileHandler::read($file);
	}

	public static function save($username, $project_id, array $data){
		$file = self::get_project_file($username, $project_id);
		$data['project_id'] = $project_id;
		$data['username'] = $username;
		$data['updated_at'] = time();
		if(empty($data['created_at'])) $data['created_at'] = time();
		return AIFileHandler::write($file, $data);
	}

	public static function delete($username, $project_id){
		$file = self::get_project_file($username, $project_id);
		return AIFileHandler::delete($file);
	}

	public static function list_all($username){
		$dir = self::get_projects_dir($username);
		$projects = array();
		if(is_dir($dir)){
			foreach(AIFileHandler::list_files($dir, '*.json.php') as $file){
				$data = AIFileHandler::read($file);
				if($data){
					$projects[] = array(
						'project_id' => $data['project_id'],
						'name' => !empty($data['name']) ? $data['name'] : basename($data['path']),
						'path' => $data['path'],
						'type' => !empty($data['type']) ? $data['type'] : 'custom',
						'created_at' => !empty($data['created_at']) ? $data['created_at'] : 0,
						'updated_at' => !empty($data['updated_at']) ? $data['updated_at'] : 0
					);
				}
			}
		}
		usort($projects, function($a, $b){
			return ($b['updated_at'] ?? 0) - ($a['updated_at'] ?? 0);
		});
		return $projects;
	}

	public static function create_from_installation($username, $insid, $path, $name, $type = 'wordpress'){
		$project_id = self::generate_project_id($username, $path);
		if(empty($name)){
			$name = basename($path);
		}
		$data = array(
			'project_id' => $project_id,
			'username' => $username,
			'name' => $name,
			'path' => $path,
			'type' => $type,
			'insid' => $insid,
			'created_at' => time(),
			'updated_at' => time()
		);
		self::save($username, $project_id, $data);
		return $project_id;
	}

	public static function create_from_path($username, $path, $name = ''){
		$project_id = self::generate_project_id($username, $path);
		if(empty($name)){
			$name = basename($path);
		}
		$data = array(
			'project_id' => $project_id,
			'username' => $username,
			'name' => $name,
			'path' => $path,
			'type' => 'custom',
			'created_at' => time(),
			'updated_at' => time()
		);
		self::save($username, $project_id, $data);
		return $project_id;
	}

	public static function find_by_path($username, $path){
		$project_id = self::generate_project_id($username, $path);
		return self::load($username, $project_id);
	}

	public static function update($username, $project_id, array $data){
		$existing = self::load($username, $project_id);
		if(!$existing) return false;
		$data = array_merge($existing, $data);
		$data['updated_at'] = time();
		return self::save($username, $project_id, $data);
	}
}