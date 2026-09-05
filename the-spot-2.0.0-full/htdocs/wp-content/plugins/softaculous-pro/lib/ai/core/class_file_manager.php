<?php

if(!defined('SOFTACULOUS')){
	die('Hacking Attempt');
}

class AIFileManager {
	private $project_path;
	private $user_home_dir;
	private $max_read_size = 512000; // 512KB default
	private $max_write_size = 10485760; // 10MB default
	private $max_tree_depth = 10;
	private $max_files_in_tree = 1000;

	private static $write_protected_dirs = array('.softaculous', '.ssh', '.gnupg', '.config/google-cloud', '.aws', 'softaculous-pro');

	public function __construct($project_path, $user_home_dir = null, array $config = array()) {
		$this->project_path = rtrim($project_path, '/');
		$this->user_home_dir = $user_home_dir ? rtrim($user_home_dir, '/') : $this->project_path;
		if (!empty($config['max_read_size'])) {
			$this->max_read_size = intval($config['max_read_size']);
		}
		if (!empty($config['max_write_size'])) {
			$this->max_write_size = intval($config['max_write_size']);
		}
	}

	public function get_user_home_dir() {
		return $this->user_home_dir;
	}

	private function is_write_protected($path) {
		$full_path = $this->resolve_path($path);
		$real_path = realpath($full_path);
		if ($real_path === false) {
			$dir_check = realpath(dirname($full_path));
			if ($dir_check === false) return false;
			$real_path = $dir_check . '/' . basename($full_path);
		}
		$relative = substr($real_path, strlen($this->user_home_dir));
		if ($relative === false || $relative === '') return false;
		$relative = ltrim($relative, '/');
		foreach (self::$write_protected_dirs as $dir) {
			if ($relative === $dir || strpos($relative, $dir . '/') === 0) {
				return $dir;
			}
		}
		return false;
	}

	public function validate_path($path) {
		$full_path = $this->resolve_path($path);

		if (strpos($full_path, $this->project_path) !== 0) {
			return false;
		}

		$real_path = realpath($full_path);
		if ($real_path === false) {
			$dir_check = realpath(dirname($full_path));
			if ($dir_check === false) {
				return false;
			}
			$real_path = $dir_check . '/' . basename($full_path);
		}

		if (strpos($real_path, $this->user_home_dir . '/') !== 0 && $real_path !== $this->user_home_dir) {
			error_log("AI Security: Blocked access outside user home: {$real_path} (user home: {$this->user_home_dir})");
			return false;
		}

		return true;
	}

	public function resolve_path($path) {
		if(empty($path) || $path === '/'){
			return $this->project_path;
		}
		if (strpos($path, '/') !== 0) {
			$path = $this->project_path . '/' . ltrim($path, '/');
		}else{
			$path = $this->project_path . $path;
		}
		$parts = explode('/', $path);
		$resolved = array();
		foreach ($parts as $part) {
			if ($part === '..') {
				array_pop($resolved);
			} elseif ($part !== '.' && $part !== '') {
				$resolved[] = $part;
			}
		}
		return '/' . implode('/', $resolved);
	}

	public function read_file($path, $max_size = null) {
		if (!$this->validate_path($path)) {
			return array('error' => 'Path outside project directory');
		}

		$full_path = $this->resolve_path($path);
		if (!file_exists($full_path)) {
			return array('error' => 'File not found');
		}

		if (!is_file($full_path)) {
			return array('error' => 'Path is not a file');
		}

		$size = filesize($full_path);
		$max = ($max_size !== null) ? $max_size : $this->max_read_size;
		if ($size > $max) {
			return array('error' => 'File too large (max: ' . $this->format_size($max) . ')');
		}

		$content = @file_get_contents($full_path);
		if ($content === false) {
			return array('error' => 'Failed to read file');
		}

		return array(
			'path' => $path,
			'content' => $content,
			'size' => $size,
			'modified' => filemtime($full_path)
		);
	}

	public function write_file($path, $content, $backup = true) {
		if (!$this->validate_path($path)) {
			return array('error' => 'Path outside project directory');
		}

		$protected = $this->is_write_protected($path);
		if ($protected) {
			return array('error' => 'Writing to ' . $protected . '/ is not allowed');
		}

		$full_path = $this->resolve_path($path);

		if (strlen($content) > $this->max_write_size) {
			return array('error' => 'Content too large (max: ' . $this->format_size($this->max_write_size) . ')');
		}

		$dir = dirname($full_path);
		if (!is_dir($dir)) {
			if (!@mkdir($dir, 0755, true)) {
				return array('error' => 'Failed to create directory');
			}
		}

		if ($backup && file_exists($full_path)) {
			$this->create_backup($full_path);
		}

		if (@file_put_contents($full_path, $content) === false) {
			return array('error' => 'Failed to write file');
		}

		return array(
			'path' => $path,
			'size' => strlen($content),
			'written' => true
		);
	}

	public function list_directory($path, $depth = 2) {
		if (!$this->validate_path($path)) {
			return array('error' => 'Path outside project directory');
		}

		$full_path = $this->resolve_path($path);
		if (!is_dir($full_path)) {
			return array('error' => 'Directory not found');
		}

		return $this->scan_directory($full_path, $depth);
	}

	private function scan_directory($path, $depth, $current_depth = 0) {
		$result = array(
			'path' => str_replace($this->project_path, '', $path) ?: '/',
			'directories' => array(),
			'files' => array()
		);

		if ($current_depth >= $depth) {
			return $result;
		}

		$items = @scandir($path);
		if ($items === false) {
			return $result;
		}

		$file_count = 0;
		foreach ($items as $item) {
			if ($item === '.' || $item === '..') continue;
			if ($file_count >= $this->max_files_in_tree) break;

			$item_path = $path . '/' . $item;
			$relative_path = str_replace($this->project_path, '', $item_path);

			if (is_dir($item_path)) {
				if ($current_depth + 1 < $depth) {
					$result['directories'][] = array(
						'name' => $item,
						'path' => $relative_path,
						'children' => $this->scan_directory($item_path, $depth, $current_depth + 1)
					);
				} else {
					$result['directories'][] = array(
						'name' => $item,
						'path' => $relative_path,
						'children' => null
					);
				}
			} else {
				$result['files'][] = array(
					'name' => $item,
					'path' => $relative_path,
					'size' => filesize($item_path),
					'modified' => filemtime($item_path)
				);
				$file_count++;
			}
		}

		return $result;
	}

	public function get_file_tree($path = '/', $depth = 3) {
		return $this->list_directory($path, $depth);
	}

	public function search_in_files($pattern, $path = '/', array $extensions = array()) {
		if (!$this->validate_path($path)) {
			return array('error' => 'Path outside project directory');
		}

		$full_path = $this->resolve_path($path);
		if (!is_dir($full_path)) {
			return array('error' => 'Directory not found');
		}

		$results = array();
		$this->do_search($full_path, $pattern, $extensions, $results, 0, 5);
		return $results;
	}

	private function do_search($path, $pattern, array $extensions, array &$results, $depth, $max_depth) {
		if ($depth >= $max_depth || count($results) >= 100) return;

		$items = @scandir($path);
		if ($items === false) return;

		foreach ($items as $item) {
			if ($item === '.' || $item === '..') continue;

			$item_path = $path . '/' . $item;

			if (is_dir($item_path)) {
				$this->do_search($item_path, $pattern, $extensions, $results, $depth + 1, $max_depth);
			} else {
				if (!empty($extensions)) {
					$ext = pathinfo($item, PATHINFO_EXTENSION);
					if (!in_array($ext, $extensions)) continue;
				}

				$relative_path = str_replace($this->project_path, '', $item_path);
				$content = @file_get_contents($item_path);
				if ($content === false) continue;

				$matches = array();
				if (preg_match_all('/' . preg_quote($pattern, '/') . '/i', $content, $matches, PREG_OFFSET_CAPTURE)) {
					foreach ($matches[0] as $idx => $match) {
						$line_no = substr_count(substr($content, 0, $match[1]), "\n") + 1;
						$results[] = array(
							'file' => $relative_path,
							'line' => $line_no,
							'match' => trim(substr($content, max(0, $match[1] - 50), 100))
						);
						if (count($results) >= 100) break;
					}
				}
			}
		}
	}

	public function create_backup($path){
		$full_path = $this->resolve_path($path);
		if (!file_exists($full_path)) {
			return '';
		}

		$backup_dir = $this->project_path . '/.ai_backups';
		if (!is_dir($backup_dir)) {
			@mkdir($backup_dir, 0755, true);
		}

		$timestamp = date('Y-m-d_H-i-s');
		$filename = basename($full_path);
		$backup_path = $backup_dir . '/' . $filename . '.' . $timestamp . '.bak';

		if (@copy($full_path, $backup_path)) {
			return $backup_path;
		}
		return '';
	}

	public function delete_file($path){
		if (!$this->validate_path($path)) {
			return array('error' => 'Path outside project directory');
		}

		$protected = $this->is_write_protected($path);
		if ($protected) {
			return array('error' => 'Deleting files in ' . $protected . '/ is not allowed');
		}

		$full_path = $this->resolve_path($path);
		if (!file_exists($full_path)) {
			return array('error' => 'File not found');
		}

		if (!@unlink($full_path)) {
			return array('error' => 'Failed to delete file');
		}

		return array('deleted' => true, 'path' => $path);
	}

	public function create_directory($path){
		if (!$this->validate_path($path)) {
			return array('error' => 'Path outside project directory');
		}

		$protected = $this->is_write_protected($path);
		if ($protected) {
			return array('error' => 'Creating directories in ' . $protected . '/ is not allowed');
		}

		$full_path = $this->resolve_path($path);
		if (!@mkdir($full_path, 0755, true)) {
			return array('error' => 'Failed to create directory');
		}

		return array('created' => true, 'path' => $path);
	}

	public function rename($old_path, $new_path){
		if (!$this->validate_path($old_path) || !$this->validate_path($new_path)) {
			return array('error' => 'Path outside project directory');
		}

		$protected_old = $this->is_write_protected($old_path);
		if ($protected_old) {
			return array('error' => 'Renaming files in ' . $protected_old . '/ is not allowed');
		}
		$protected_new = $this->is_write_protected($new_path);
		if ($protected_new) {
			return array('error' => 'Renaming to ' . $protected_new . '/ is not allowed');
		}

		$old_full = $this->resolve_path($old_path);
		$new_full = $this->resolve_path($new_path);

		if (!file_exists($old_full)) {
			return array('error' => 'Path not found');
		}

		if (!@rename($old_full, $new_full)) {
			return array('error' => 'Failed to rename');
		}

		return array('renamed' => true, 'old_path' => $old_path, 'new_path' => $new_path);
	}

	public function get_stats(){
		$stats = array(
			'total_files' => 0,
			'total_size' => 0,
			'file_types' => array()
		);

		$this->count_files($this->project_path, $stats, 0, 3);
		return $stats;
	}

	private function count_files($path, array &$stats, $depth, $max_depth) {
		if ($depth >= $max_depth) return;

		$items = @scandir($path);
		if ($items === false) return;

		foreach ($items as $item) {
			if ($item === '.' || $item === '..') continue;

			$item_path = $path . '/' . $item;

			if (is_dir($item_path)) {
				$this->count_files($item_path, $stats, $depth + 1, $max_depth);
			} else {
				$stats['total_files']++;
				$size = filesize($item_path);
				$stats['total_size'] += $size;

				$ext = pathinfo($item, PATHINFO_EXTENSION);
				if (empty($ext)) $ext = 'no_extension';
				if (!isset($stats['file_types'][$ext])) {
					$stats['file_types'][$ext] = array('count' => 0, 'size' => 0);
				}
				$stats['file_types'][$ext]['count']++;
				$stats['file_types'][$ext]['size'] += $size;
			}
		}
	}

	private function format_size($bytes){
		$units = array('B', 'KB', 'MB', 'GB');
		$unit_idx = 0;
		while ($bytes >= 1024 && $unit_idx < count($units) - 1) {
			$bytes /= 1024;
			$unit_idx++;
		}
		return round($bytes, 2) . ' ' . $units[$unit_idx];
	}
}
