<?php

if(!defined('SOFTACULOUS')){
	die('Hacking Attempt');
}

class AIFileHandler {

	private static function ensure_index_html($dir){
		$index_file = $dir . '/index.html';
		if(!file_exists($index_file)){
			@file_put_contents($index_file, '');
		}
	}


	private static function ensure_ai_dir($username){
		$dir = defined('SOFTACULOUS_PRO_AI_STORAGE') ? SOFTACULOUS_PRO_AI_STORAGE : ai_get_softdir($username) .'/.softaculous/ai';
		if(!is_dir($dir)){
			@mkdir($dir, 0755, true);
		}
		self::ensure_index_html($dir);
		return $dir;
	}

	public static function ensure_dir($path, $is_dir = false){
		
		if(empty($is_dir)){
			$dir = dirname($path);
		} else {
			$dir = $path;
		}

		if(!is_dir($dir)){
			@mkdir($dir, 0755, true);
		}
		self::ensure_index_html($dir);
		return $dir;
	}

	public static function read($file_path){
		if(!is_safe_file($file_path)){
			return null;
		}
		if(!file_exists($file_path)){
			return null;
		}
		$fp = @fopen($file_path, 'r');
		if(!$fp){
			return null;
		}
		if(!flock($fp, LOCK_SH)){
			fclose($fp);
			return null;
		}
		$content = stream_get_contents($fp);
		flock($fp, LOCK_UN);
		fclose($fp);
		if(empty($content)){
			return null;
		}
		$lines = explode("\n", $content, 2);
		if(count($lines) < 2){
			return null;
		}
		$json = substr($content, strpos($content, "\n") + 1);
		return @json_decode($json, true);
	}

	public static function write($file_path, $data){
		if(!is_safe_file($file_path)){
			return false;
		}
		self::ensure_dir($file_path);
		$json = json_encode($data, JSON_PRETTY_PRINT);
		$content = "<?php exit(); ?>\n" . $json;
		$fp = @fopen($file_path, 'c');
		if(!$fp){
			return false;
		}
		if(!flock($fp, LOCK_EX)){
			fclose($fp);
			return false;
		}
		ftruncate($fp, 0);
		rewind($fp);
		$result = fwrite($fp, $content) !== false;
		flock($fp, LOCK_UN);
		fclose($fp);
		return $result;
	}

	public static function delete($file_path){
		if(!is_safe_file($file_path)){
			return false;
		}
		if(file_exists($file_path)){
			return @unlink($file_path);
		}
		return true;
	}

	public static function list_files($dir, $pattern){
		if(!is_safe_file($dir)){
			return array();
		}
		if(!is_dir($dir)){
			return array();
		}
		$files = array();
		$escaped_pattern = str_replace(['*', '.'], ['[^/]*', '\\.'], $pattern);
		foreach(glob($dir . '/' . $pattern) as $f){
			if(preg_match('#/(' . $escaped_pattern . ')$#', $f)){
				$files[] = $f;
			}
		}
		return $files;
	}

	public static function get_ai_base_dir($username){
		return self::ensure_ai_dir($username);
	}
}
