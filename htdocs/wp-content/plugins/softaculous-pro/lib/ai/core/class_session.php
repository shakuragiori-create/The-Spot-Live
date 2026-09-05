<?php

if(!defined('SOFTACULOUS')){
	die('Hacking Attempt');
}

require_once(__DIR__ . '/class_ai_file_handler.php');

class AISession {

	public static function get_base_dir($username){
		return AIFileHandler::get_ai_base_dir($username);
	}

	public static function get_session_key($username, $project_path){
		return $username . '_' . substr(md5($project_path), 0, 16);
	}

	public static function get_session_file($username, $project_path){
		return self::get_base_dir($username) . '/' . self::get_session_key($username, $project_path) . '.json.php';
	}

	public static function load($username, $project_path){
		$file = self::get_session_file($username, $project_path);
		return AIFileHandler::read($file);
	}

	public static function save($username, $project_path, array $data){
		AIFileHandler::get_ai_base_dir($username);
		$file = self::get_session_file($username, $project_path);
		$data['username'] = $username;
		$data['project_path'] = $project_path;
		$data['updated_at'] = time();
		if(empty($data['created_at'])) $data['created_at'] = time();
		return AIFileHandler::write($file, $data);
	}

	public static function delete($username, $project_path){
		$file = self::get_session_file($username, $project_path);
		return AIFileHandler::delete($file);
	}

	public static function get_conversations_dir($username, $project_path){
		$dir = self::get_base_dir($username) . '/' . self::get_session_key($username, $project_path) . '_conversations';
		AIFileHandler::ensure_dir($dir, true);
		return $dir;
	}

	public static function get_active_conversation_id($username, $project_path){
		$session = self::load($username, $project_path);
		if(!empty($session['active_conversation'])) return $session['active_conversation'];
		$conv_id = 'conv_' . substr(md5(uniqid(mt_rand(), true)), 0, 12);
		$session = $session ? $session : array();
		$session['active_conversation'] = $conv_id;
		self::save($username, $project_path, $session);
		return $conv_id;
	}

	public static function set_active_conversation($username, $project_path, $conv_id){
		$session = self::load($username, $project_path);
		$session = $session ? $session : array();
		$session['active_conversation'] = $conv_id;
		self::save($username, $project_path, $session);
	}
}