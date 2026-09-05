<?php

if(!defined('SOFTACULOUS')){
	die('Hacking Attempt');
}

class ToolExecutor {
	private $file_manager;
	private $project_path;
	private $user_home_dir;
	private $mode = 'build';
	private $jail_script;
	private $use_jail = null;
	private $restricted_dir = null;

	public function __construct(AIFileManager $file_manager, $project_path, $user_home_dir = null, $mode = 'build'){
		global $globals;

		if(!empty($globals['path'])){
			$this->jail_script = $globals['path'].'/bin/ai_jail.sh';
		}
		
		$this->file_manager = $file_manager;
		$this->project_path = rtrim($project_path, '/');
		$this->user_home_dir = $user_home_dir ? rtrim($user_home_dir, '/') : $this->project_path;
		$this->mode = $mode;
		
		// Setting directory boundry for bash for WordPress.
		if(defined('ABSPATH')){
			$abspath = rtrim(ABSPATH, '/');
			$ph_pos = strpos($abspath, '/public_html');
			if($ph_pos !== false){
				$this->restricted_dir = substr($abspath, 0, $ph_pos + strlen('/public_html'));
			}else{
				$this->restricted_dir = $abspath;
			}
		}

		if($this->use_jail === null){
			$this->use_jail = false;
			$this->check_jail_availability();
		}
	}

	private function check_jail_availability(){
		if(!file_exists($this->jail_script) || !is_executable($this->jail_script)){
			return;
		}

		$test_cmd = sprintf('%s %s -- echo JAIL_TEST_OK 2>/dev/null',
			escapeshellarg($this->jail_script),
			escapeshellarg($this->user_home_dir)
		);

		$output = shell_exec($test_cmd);
		if(strpos($output, 'JAIL_TEST_OK') !== false){
			$this->use_jail = true;
		}
	}

	public function execute($tool_name, array $params){
		$read_only_tools = array('read_file', 'glob', 'grep', 'list_directory', 'web_fetch', 'todo_write');
		if($this->mode === 'plan' && !in_array($tool_name, $read_only_tools)){
			return array('output' => 'Permission denied: write operations are not allowed in Plan mode.', 'is_error' => true);
		}

		switch($tool_name){
			case 'read_file': return $this->tool_read_file($params);
			case 'write_file': return $this->tool_write_file($params);
			case 'edit_file': return $this->tool_edit_file($params);
			case 'bash': return $this->tool_bash($params);
			case 'glob': return $this->tool_glob($params);
			case 'grep': return $this->tool_grep($params);
			case 'list_directory': return $this->tool_list_directory($params);
			case 'web_fetch': return $this->tool_web_fetch($params);
			case 'todo_write': return $this->tool_todo_write($params);
			case 'file_download': return $this->tool_file_download($params);
			case 'archive': return $this->tool_archive($params);
			case 'search_replace': return $this->tool_search_replace($params);
			case 'php_eval': return $this->tool_php_eval($params);
			default: return array('output' => "Unknown tool: {$tool_name}", 'is_error' => true);
		}
	}

	private function tool_read_file(array $params){
		$path = isset($params['path']) ? $params['path'] : '';
		$offset = isset($params['offset']) ? intval($params['offset']) : 1;
		$limit = isset($params['limit']) ? intval($params['limit']) : 2000;
		
		// We do not allow it to read the settings file.
		if(strpos($path, 'ai/settings.json.php') != 0){
			return array('output' => 'Access denied', 'is_error' => true);
		}

		$result = $this->file_manager->read_file($path);
		if(!empty($result['error'])){
			return array('output' => $result['error'], 'is_error' => true);
		}

		$content = $result['content'];
		$lines = explode("\n", $content);

		if($offset > 1 || $limit < count($lines)){
			$lines = array_slice($lines, max(0, $offset - 1), $limit);
		}

		$output = '';
		$line_no = max(1, $offset);
		foreach($lines as $line){
			$output .= str_pad($line_no, 5, ' ', STR_PAD_LEFT) . ' | ' . $line . "\n";
			$line_no++;
		}

		return array('output' => rtrim($output), 'is_error' => false);
	}

	private function tool_write_file(array $params){
		$path = isset($params['path']) ? $params['path'] : '';
		$content = isset($params['content']) ? $params['content'] : '';

		if(empty($path)){
			return array('output' => 'Path is required', 'is_error' => true);
		}

		$original = '';
		$existing = $this->file_manager->read_file($path);
		if(!empty($existing['content'])) $original = $existing['content'];

		$result = $this->file_manager->write_file($path, $content, true);
		if(!empty($result['error'])){
			return array('output' => $result['error'], 'is_error' => true);
		}

		$lines = substr_count($content, "\n") + 1;
		$return = array('output' => "Successfully wrote {$result['size']} bytes ({$lines} lines) to {$path}", 'is_error' => false);
		if($original !== '' && $original !== $content){
			$return['diff'] = $this->compute_unified_diff($original, $content);
		}
		return $return;
	}

	private function tool_edit_file(array $params){
		$path = isset($params['path']) ? $params['path'] : '';
		$old_string = isset($params['old_string']) ? $params['old_string'] : '';
		$new_string = isset($params['new_string']) ? $params['new_string'] : '';
		$replace_all = !empty($params['replace_all']);

		if(empty($path) || empty($old_string)){
			return array('output' => 'Path and old_string are required', 'is_error' => true);
		}

		$result = $this->file_manager->read_file($path);
		if(!empty($result['error'])){
			return array('output' => $result['error'], 'is_error' => true);
		}

		$content = $result['content'];

		if(strpos($content, $old_string) === false){
			$fuzzy_result = $this->fuzzy_find($content, $old_string);
			if($fuzzy_result !== false){
				$old_string = $fuzzy_result;
			}else{
				$snippet = mb_substr($old_string, 0, 100);
				return array('output' => "Could not find the specified text in {$path}. Searched for: \"{$snippet}...\"", 'is_error' => true);
			}
		}

		$count = 0;
		if($replace_all){
			$new_content = str_replace($old_string, $new_string, $content, $count);
		}else{
			$pos = strpos($content, $old_string);
			if($pos !== false){
				$new_content = substr($content, 0, $pos) . $new_string . substr($content, $pos + strlen($old_string));
				$count = 1;
			}else{
				$new_content = $content;
			}
		}

		if($count === 0){
			return array('output' => "No replacements made in {$path}", 'is_error' => true);
		}

		$result2 = $this->file_manager->write_file($path, $new_content, true);
		if(!empty($result2['error'])){
			return array('output' => $result2['error'], 'is_error' => true);
		}

		$diff = $this->make_mini_diff($old_string, $new_string);
		$return = array('output' => "Edited {$path} ({$count} replacement".($count > 1 ? 's' : '').")\n{$diff}", 'is_error' => false);
		$return['diff'] = $this->compute_unified_diff($content, $new_content);
		return $return;
	}

	private function tool_bash(array $params){
		$command = isset($params['command']) ? $params['command'] : '';
		$timeout = isset($params['timeout']) ? intval($params['timeout']) : 30;
		$timeout = max(5, min($timeout, 120));

		if(empty($command)){
			return array('output' => 'Command is required', 'is_error' => true);
		}

		if($this->is_dangerous_command($command)){
			return array('output' => 'This command is blocked for safety reasons.', 'is_error' => true);
		}

		if($this->restricted_dir !== null && !$this->validate_bash_command_paths($command)){
			return array('output' => 'Access denied: command targets a path outside the allowed directory.', 'is_error' => true);
		}

		$env = array();
		if(!empty($this->user_home_dir)){
			$env['HOME'] = $this->user_home_dir;
		}
		$env['PATH'] = '/usr/local/bin:/usr/bin:/bin';
		$env['TERM'] = 'dumb';

		if($this->use_jail){
			return $this->execute_via_jail($command, $timeout, $env);
		}

		$descriptors = array(
			0 => array('pipe', 'r'),
			1 => array('pipe', 'w'),
			2 => array('pipe', 'w')
		);

		$process = proc_open('cd ' . escapeshellarg($this->project_path) . ' && ' . $command, $descriptors, $pipes, null, $env);

		if(!is_resource($process)){
			return array('output' => 'Failed to execute command', 'is_error' => true);
		}

		fclose($pipes[0]);
		stream_set_blocking($pipes[1], false);
		stream_set_blocking($pipes[2], false);

		$stdout = '';
		$stderr = '';
		$start = microtime(true);

		while(true){
			$status = proc_get_status($process);
			if(!$status['running']){
				while(($buf = fread($pipes[1], 8192)) !== '') $stdout .= $buf;
				while(($buf = fread($pipes[2], 8192)) !== '') $stderr .= $buf;
				break;
			}

			$elapsed = microtime(true) - $start;
			if($elapsed >= $timeout){
				proc_terminate($process, 9);
				fclose($pipes[1]);
				fclose($pipes[2]);
				proc_close($process);
				$output = $stdout . ($stdout && $stderr ? "\n" : '') . $stderr;
				return array('output' => "Command timed out after {$timeout}s\n{$output}", 'is_error' => true);
			}

			while(($buf = fread($pipes[1], 8192)) !== '') $stdout .= $buf;
			while(($buf = fread($pipes[2], 8192)) !== '') $stderr .= $buf;
			usleep(20000);
		}

		fclose($pipes[1]);
		fclose($pipes[2]);
		$return_code = proc_close($process);

		$output = '';
		if(!empty($stdout)) $output .= $stdout;
		if(!empty($stderr)) $output .= ($output ? "\n" : '') . $stderr;

		if(strlen($output) > 30000){
			$output = mb_substr($output, 0, 30000) . "\n... [output truncated]";
		}

		if($return_code !== 0){
			$output .= "\n[Exit code: {$return_code}]";
			return array('output' => $output, 'is_error' => true);
		}

		return array('output' => $output ? $output : "[Command completed successfully with no output]", 'is_error' => false);
	}

	private function execute_via_jail($command, $timeout, $env){

		$jail_cmd = sprintf('%s %s -- bash -c %s',
			escapeshellarg($this->jail_script),
			escapeshellarg($this->user_home_dir),
			escapeshellarg('cd ' . escapeshellarg($this->project_path) . ' && ' . $command)
		);

		$descriptors = array(
			0 => array('pipe', 'r'),
			1 => array('pipe', 'w'),
			2 => array('pipe', 'w')
		);

		$process = proc_open($jail_cmd, $descriptors, $pipes, null, $env);

		if(!is_resource($process)){
			return array('output' => 'Failed to execute jailed command', 'is_error' => true);
		}

		fclose($pipes[0]);
		stream_set_blocking($pipes[1], false);
		stream_set_blocking($pipes[2], false);

		$stdout = '';
		$stderr = '';
		$start = microtime(true);

		while(true){
			$status = proc_get_status($process);
			if(!$status['running']){
				while(($buf = fread($pipes[1], 8192)) !== '') $stdout .= $buf;
				while(($buf = fread($pipes[2], 8192)) !== '') $stderr .= $buf;
				break;
			}

			$elapsed = microtime(true) - $start;
			if($elapsed >= $timeout){
				proc_terminate($process, 9);
				fclose($pipes[1]);
				fclose($pipes[2]);
				proc_close($process);
				$output = $stdout . ($stdout && $stderr ? "\n" : '') . $stderr;
				return array('output' => "Command timed out after {$timeout}s\n{$output}", 'is_error' => true);
			}

			while(($buf = fread($pipes[1], 8192)) !== '') $stdout .= $buf;
			while(($buf = fread($pipes[2], 8192)) !== '') $stderr .= $buf;
			usleep(20000);
		}

		fclose($pipes[1]);
		fclose($pipes[2]);
		$return_code = proc_close($process);

		$output = '';
		if(!empty($stdout)) $output .= $stdout;
		if(!empty($stderr)) $output .= ($output ? "\n" : '') . $stderr;

		if(strlen($output) > 30000){
			$output = mb_substr($output, 0, 30000) . "\n... [output truncated]";
		}

		if($return_code !== 0){
			$output .= "\n[Exit code: {$return_code}]";
			return array('output' => $output, 'is_error' => true);
		}

		return array('output' => $output ? $output : "[Command completed successfully with no output]", 'is_error' => false);
	}

	private function tool_glob(array $params){
		$pattern = isset($params['pattern']) ? $params['pattern'] : '**/*';
		$base_path = isset($params['path']) ? $params['path'] : '';

		$full_base = $this->project_path;
		if(!empty($base_path)){
			$resolved = $this->file_manager->resolve_path($base_path);
			if(strpos($resolved, $this->user_home_dir) === 0){
				$full_base = $resolved;
			}
		}

		$pattern = escapeshellarg($pattern);
		$cmd = 'cd ' . escapeshellarg($full_base) . ' && find . -path ' . $pattern . ' -type f 2>/dev/null | head -200';

		if($this->use_jail){
			$cmd = escapeshellarg($this->jail_script) . ' ' . escapeshellarg($this->user_home_dir) . ' -- bash -c ' . escapeshellarg($cmd);
		}

		$output = trim(shell_exec($cmd));

		if(empty($output)){
			$_pattern = isset($params['pattern']) ? $params['pattern'] : $pattern;
			return array('output' => 'No files found matching pattern: '.$_pattern, 'is_error' => false);
		}

		$files = explode("\n", $output);
		$files = array_map(function($f){ return ltrim($f, './'); }, $files);

		return array('output' => implode("\n", $files), 'is_error' => false);
	}

	private function tool_grep(array $params){
		$pattern = isset($params['pattern']) ? $params['pattern'] : '';
		$base_path = isset($params['path']) ? $params['path'] : '.';
		$include = isset($params['include']) ? $params['include'] : '';
		$max_results = isset($params['max_results']) ? intval($params['max_results']) : 50;

		if(empty($pattern)){
			return array('output' => 'Pattern is required', 'is_error' => true);
		}

		$full_base = $this->file_manager->resolve_path($base_path);
		if(strpos($full_base, $this->user_home_dir) !== 0){
			return array('output' => 'Path outside allowed directory', 'is_error' => true);
		}

		$cmd = 'cd ' . escapeshellarg($this->project_path) . ' && grep -rn --binary-files=without-match';
		if(!empty($include)){
			$cmd .= ' --include=' . escapeshellarg($include);
		}
		$cmd .= ' ' . escapeshellarg($pattern) . ' ' . escapeshellarg($full_base) . ' 2>/dev/null | head -' . $max_results;

		if($this->use_jail){
			$cmd = escapeshellarg($this->jail_script) . ' ' . escapeshellarg($this->user_home_dir) . ' -- bash -c ' . escapeshellarg($cmd);
		}

		$output = trim(shell_exec($cmd));

		if(empty($output)){
			return array('output' => 'No matches found for pattern: '.$pattern, 'is_error' => false);
		}

		$output = preg_replace('/^' . preg_quote($this->project_path, '/') . '\//', '', $output);
		return array('output' => $output, 'is_error' => false);
	}

	private function tool_list_directory(array $params){
		$path = isset($params['path']) ? $params['path'] : '/';
		$depth = isset($params['depth']) ? intval($params['depth']) : 2;

		$result = $this->file_manager->list_directory($path, $depth);
		if(!empty($result['error'])){
			return array('output' => $result['error'], 'is_error' => true);
		}

		return array('output' => json_encode($result, JSON_PRETTY_PRINT), 'is_error' => false);
	}

	private function tool_web_fetch(array $params){
		$url = isset($params['url']) ? $params['url'] : '';
		if(empty($url)){
			return array('output' => 'URL is required', 'is_error' => true);
		}

		if(!filter_var($url, FILTER_VALIDATE_URL)){
			return array('output' => 'Invalid URL', 'is_error' => true);
		}

		$ch = curl_init($url);
		curl_setopt_array($ch, array(
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_FOLLOWLOCATION => true,
			CURLOPT_TIMEOUT => 15,
			CURLOPT_SSL_VERIFYPEER => false,
			CURLOPT_USERAGENT => 'Mozilla/5.0 (compatible; Softaculous AI/1.0)'
		));
		$response = curl_exec($ch);
		$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
		curl_close($ch);

		if($response === false){
			return array('output' => 'Failed to fetch URL', 'is_error' => true);
		}

		$response = strip_tags($response);
		$response = preg_replace('/\s+/', ' ', $response);
		$response = trim($response);

		if(strlen($response) > 30000){
			$response = substr($response, 0, 30000) . '... [truncated]';
		}

		return array('output' => $response, 'is_error' => false);
	}

	private function tool_todo_write(array $params){
		$todos = isset($params['todos']) ? $params['todos'] : array();
		return array('output' => 'Todo list updated with '.count($todos).' items.', 'is_error' => false, 'todos' => $todos);
	}

	private function fuzzy_find($content, $search){
		$search_trimmed = trim($search);
		$search_lines = explode("\n", $search_trimmed);

		if(count($search_lines) < 2) return null;

		$first_line = trim($search_lines[0]);
		$last_line = trim($search_lines[count($search_lines) - 1]);

		$content_lines = explode("\n", $content);
		$first_positions = array();
		foreach($content_lines as $i => $line){
			if(trim($line) === $first_line) $first_positions[] = $i;
		}

		foreach($first_positions as $start){
			$end = $start + count($search_lines) - 1;
			if($end >= count($content_lines)) continue;
			if(trim($content_lines[$end]) === $last_line){
				$match = implode("\n", array_slice($content_lines, $start, count($search_lines)));
				return $match;
			}
		}

		foreach($content_lines as $i => $line){
			if(strpos(trim($line), $first_line) !== false || strpos($first_line, trim($line)) !== false){
				$end = min($i + count($search_lines), count($content_lines));
				$candidate = implode("\n", array_slice($content_lines, $i, $end - $i));
				similar_text($search_trimmed, $candidate, $percent);
				if($percent > 70) return $candidate;
			}
		}

		return null;
	}

	private function make_mini_diff($old, $new){
		$diff = "";
		$old_lines = explode("\n", $old);
		$new_lines = explode("\n", $new);
		foreach($old_lines as $l) $diff .= "- " . $l . "\n";
		foreach($new_lines as $l) $diff .= "+ " . $l . "\n";
		return trim($diff);
	}

	private function compute_unified_diff($old, $new){
		$old_lines = explode("\n", $old);
		$new_lines = explode("\n", $new);
		$max = max(count($old_lines), count($new_lines));
		$diff = array();
		$ctx = 3;
		$buffer = array();
		$in_change = false;

		for($i = 0; $i < $max || !empty($buffer); $i++){
			$o = isset($old_lines[$i]) ? $old_lines[$i] : null;
			$n = isset($new_lines[$i]) ? $new_lines[$i] : null;

			if($o !== null && $n !== null && $o === $n){
				if($in_change){
					$buffer[] = ' ' . $o;
					if(count($buffer) > $ctx * 2 + 10){
						$diff = array_merge($diff, array_slice($buffer, 0, count($buffer) - $ctx));
						$buffer = array_slice($buffer, -$ctx);
						$in_change = false;
					}
				}else{
					$buffer[] = ' ' . $o;
					if(count($buffer) > $ctx){
						$buffer = array_slice($buffer, -$ctx);
					}
				}
			}else{
				if(!$in_change && !empty($buffer)){
					$keep = min(count($buffer), $ctx);
					$diff = array_merge($diff, array_slice($buffer, -$keep));
					$buffer = array();
				}
				$in_change = true;
				if($o !== null) $diff[] = '- ' . $o;
				if($n !== null) $diff[] = '+ ' . $n;
			}
		}

		if(!empty($buffer)){
			$keep = min(count($buffer), $ctx);
			$diff = array_merge($diff, array_slice($buffer, -$keep));
		}

		return implode("\n", $diff);
	}

	private function tool_file_download(array $params){
		$url = isset($params['url']) ? $params['url'] : '';
		$path = isset($params['path']) ? $params['path'] : '';
		$overwrite = !empty($params['overwrite']);

		if(empty($url) || empty($path)){
			return array('output' => 'URL and path are required', 'is_error' => true);
		}

		if(!filter_var($url, FILTER_VALIDATE_URL)){
			return array('output' => 'Invalid URL: '.$url, 'is_error' => true);
		}

		$scheme = parse_url($url, PHP_URL_SCHEME);
		if(!in_array($scheme, array('http', 'https'))){
			return array('output' => 'Only http/https URLs are allowed', 'is_error' => true);
		}

		$resolved = $this->file_manager->resolve_path($path);
		if(strpos($resolved, $this->project_path) !== 0){
			return array('output' => 'Destination path is outside the project directory', 'is_error' => true);
		}

		if(file_exists($resolved) && !$overwrite){
			return array('output' => "File already exists: {$path}. Set overwrite=true to replace.", 'is_error' => true);
		}

		$dir = dirname($resolved);
		if(!is_dir($dir)){
			@mkdir($dir, 0755, true);
		}

		$fp = @fopen($resolved, 'w');
		if(!$fp){
			return array('output' => "Cannot write to: {$path}", 'is_error' => true);
		}

		$ch = curl_init($url);
		curl_setopt_array($ch, array(
			CURLOPT_FILE => $fp,
			CURLOPT_FOLLOWLOCATION => true,
			CURLOPT_TIMEOUT => 120,
			CURLOPT_SSL_VERIFYPEER => false,
			CURLOPT_USERAGENT => 'Mozilla/5.0 (compatible; Softaculous AI/1.0)',
			CURLOPT_MAXFILESIZE => 52428800
		));

		$result = curl_exec($ch);
		$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
		$size = curl_getinfo($ch, CURLINFO_SIZE_DOWNLOAD);
		$error = curl_error($ch);
		curl_close($ch);
		fclose($fp);

		if(!$result){
			@unlink($resolved);
			return array('output' => 'Download failed: '.($error ?: 'Unknown error'), 'is_error' => true);
		}

		if($http_code >= 400){
			@unlink($resolved);
			return array('output' => "Download failed: HTTP {$http_code}", 'is_error' => true);
		}

		$size_str = $this->format_bytes($size);
		return array('output' => "Downloaded {$size_str} to {$path} (HTTP {$http_code})", 'is_error' => false);
	}

	private function tool_archive(array $params){
		$action = isset($params['action']) ? $params['action'] : '';
		$source = isset($params['source']) ? $params['source'] : '';
		$destination = isset($params['destination']) ? $params['destination'] : '';

		if(empty($action) || empty($source)){
			return array('output' => 'action and source are required', 'is_error' => true);
		}

		if(!in_array($action, array('zip', 'unzip'))){
			return array('output' => 'Action must be "zip" or "unzip"', 'is_error' => true);
		}

		if($action === 'zip'){
			return $this->archive_zip($source, $destination);
		}else{
			return $this->archive_unzip($source, $destination);
		}
	}

	private function archive_zip($source, $destination){
		$src_resolved = $this->file_manager->resolve_path($source);
		if(strpos($src_resolved, $this->user_home_dir) !== 0){
			return array('output' => 'Source path is outside the allowed directory', 'is_error' => true);
		}
		if(!file_exists($src_resolved)){
			return array('output' => "Source not found: {$source}", 'is_error' => true);
		}

		if(empty($destination)){
			$destination = $source . '.zip';
		}
		if(substr($destination, -4) !== '.zip') $destination .= '.zip';

		$dst_resolved = $this->file_manager->resolve_path($destination);
		if(strpos($dst_resolved, $this->user_home_dir) !== 0){
			return array('output' => 'Destination path is outside the allowed directory', 'is_error' => true);
		}

		$zip = new ZipArchive();
		if($zip->open($dst_resolved, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true){
			return array('output' => 'Failed to create zip archive', 'is_error' => true);
		}

		$count = 0;
		if(is_dir($src_resolved)){
			$base = basename($src_resolved);
			$files = new RecursiveIteratorIterator(
				new RecursiveDirectoryIterator($src_resolved, RecursiveDirectoryIterator::SKIP_DOTS),
				RecursiveIteratorIterator::SELF_FIRST
			);
			foreach($files as $file){
				$local = $base . '/' . substr($file->getPathname(), strlen($src_resolved) + 1);
				if($file->isDir()){
					$zip->addEmptyDir($local);
				}else{
					if($file->getSize() > 52428800){
						continue;
					}
					$zip->addFile($file->getPathname(), $local);
					$count++;
				}
			}
		}else{
			$zip->addFile($src_resolved, basename($src_resolved));
			$count = 1;
		}

		$zip->close();

		$zip_size = $this->format_bytes(filesize($dst_resolved));
		return array('output' => "Created archive {$destination} ({$count} files, {$zip_size})", 'is_error' => false);
	}

	private function archive_unzip($source, $destination){
		$src_resolved = $this->file_manager->resolve_path($source);
		if(strpos($src_resolved, $this->user_home_dir) !== 0){
			return array('output' => 'Source path is outside the allowed directory', 'is_error' => true);
		}
		if(!file_exists($src_resolved)){
			return array('output' => "Source not found: {$source}", 'is_error' => true);
		}

		if(empty($destination)){
			$destination = dirname($source) ?: '.';
		}
		$dst_resolved = $this->file_manager->resolve_path($destination);
		if(strpos($dst_resolved, $this->user_home_dir) !== 0){
			return array('output' => 'Destination path is outside the allowed directory', 'is_error' => true);
		}

		$zip = new ZipArchive();
		if($zip->open($src_resolved) !== true){
			return array('output' => 'Failed to open zip archive', 'is_error' => true);
		}

		$count = $zip->numFiles;
		$zip->extractTo($dst_resolved);
		$zip->close();

		return array('output' => "Extracted {$count} files to {$destination}", 'is_error' => false);
	}

	private function tool_search_replace(array $params){
		$search = isset($params['search']) ? $params['search'] : '';
		$replace = isset($params['replace']) ? $params['replace'] : '';
		$include = isset($params['include']) ? $params['include'] : '';
		$base_path = isset($params['path']) ? $params['path'] : '.';
		$max_files = isset($params['max_files']) ? intval($params['max_files']) : 50;
		$is_regex = !empty($params['regex']);

		$max_files = max(1, min($max_files, 100));

		if(empty($search)){
			return array('output' => 'search pattern is required', 'is_error' => true);
		}

		$full_base = $this->file_manager->resolve_path($base_path);
		if(strpos($full_base, $this->user_home_dir) !== 0){
			return array('output' => 'Path outside allowed directory', 'is_error' => true);
		}

		if($is_regex){
			set_error_handler(function(){});
			$test = preg_match($search, '');
			restore_error_handler();
			if($test === false){
				return array('output' => 'Invalid regex pattern: '.$search, 'is_error' => true);
			}
		}

		$cmd = 'cd '.escapeshellarg($this->project_path).' && grep -rl --binary-files=without-match';
		if(!empty($include)){
			$cmd .= ' --include='.escapeshellarg($include);
		}
		$cmd .= ' '.escapeshellarg($search).' '.escapeshellarg($full_base).' 2>/dev/null | head -'.($max_files + 1);

		if($this->use_jail){
			$cmd = escapeshellarg($this->jail_script) . ' ' . escapeshellarg($this->user_home_dir) . ' -- bash -c ' . escapeshellarg($cmd);
		}

		$output = trim(shell_exec($cmd));
		if(empty($output)){
			return array('output' => 'No files found matching: '.$search, 'is_error' => false);
		}

		$files = explode("\n", $output);
		$truncated = false;
		if(count($files) > $max_files){
			$files = array_slice($files, 0, $max_files);
			$truncated = true;
		}

		$results = array();
		$total_replacements = 0;

		foreach($files as $file){
			$filepath = trim($file);
			if(!file_exists($filepath) || !is_readable($filepath) || !is_writable($filepath)) continue;

			$rel_path = str_replace($this->project_path . '/', '', $filepath);
			$read_result = $this->file_manager->read_file($rel_path);
			if(!empty($read_result['error'])) continue;
			$content = $read_result['content'];

			$count = 0;
			if($is_regex){
				$new_content = preg_replace($search, $replace, $content, -1, $count);
			}else{
				$new_content = str_replace($search, $replace, $content, $count);
			}

			if($count > 0 && $new_content !== null){
				$write_result = $this->file_manager->write_file($rel_path, $new_content, true);
				if(empty($write_result['error'])){
					$results[] = "{$rel_path}: {$count} replacement(s)";
					$total_replacements += $count;
				}
			}
		}

		$output = "Replaced \"{$search}\" in ".count($results)." file(s) ({$total_replacements} total replacements)\n";
		$output .= implode("\n", $results);
		if($truncated){
			$output .= "\n[Only processed first {$max_files} files. Use max_files to increase.]";
		}

		return array('output' => $output, 'is_error' => false);
	}

	private function tool_php_eval(array $params){
		$code = isset($params['code']) ? $params['code'] : '';

		if(empty($code)){
			return array('output' => 'code is required', 'is_error' => true);
		}

		$forbidden = array('exec(', 'shell_exec(', 'system(', 'passthru(', 'popen(', 'proc_open(',
			'pcntl_', 'putenv(', 'apache_', 'ini_set(', 'ini_restore(',
			'mail(', 'header(', 'setcookie(', 'move_uploaded_file(',
			'chmod(', 'chown(', 'chgrp(', 'unlink(', 'rmdir(',
			'file_put_contents(', 'fwrite(', 'fputs(', 'rename(',
			'eval(', 'assert(', 'preg_replace(', 'create_function(',
			'call_user_func(', 'call_user_func_array(');
		$code_lower = strtolower($code);
		foreach($forbidden as $f){
			if(strpos($code_lower, strtolower($f)) !== false){
				return array('output' => "Function/statement not allowed: {$f}", 'is_error' => true);
			}
		}

		$tmp = tempnam(sys_get_temp_dir(), 'ai_php_');
		$wrapped = "<?php\nchdir(".var_export($this->project_path, true).");\n";
		if(file_exists($this->project_path.'/wp-load.php')){
			$wrapped .= "define('ABSPATH', ".var_export($this->project_path.'/', true).");\n";
			$wrapped .= "@include_once(".var_export($this->project_path.'/wp-load.php', true).");\n";
		}
		$wrapped .= "\n".$code."\n";
		file_put_contents($tmp, $wrapped);

		$env = array();
		if(!empty($this->user_home_dir)){
			$env['HOME'] = $this->user_home_dir;
		}
		$env['PATH'] = '/usr/local/bin:/usr/bin:/bin';

		$open_basedir = $this->user_home_dir . ':' . sys_get_temp_dir();
		$disable_functions = 'exec,shell_exec,system,passthru,popen,proc_open,pcntl_fork,pcntl_exec,putenv,ini_set,ini_restore,dl';

		if($this->use_jail){
			$jail_cmd = sprintf(
				'%s %s -- php -d open_basedir=%s -d disable_functions=%s %s',
				escapeshellarg($this->jail_script),
				escapeshellarg($this->user_home_dir),
				escapeshellarg($open_basedir),
				escapeshellarg($disable_functions),
				escapeshellarg($tmp)
			);

			$descriptors = array(
				0 => array('pipe', 'r'),
				1 => array('pipe', 'w'),
				2 => array('pipe', 'w')
			);

			$process = proc_open($jail_cmd, $descriptors, $pipes, null, $env);

			if(!is_resource($process)){
				@unlink($tmp);
				return array('output' => 'Failed to execute jailed PHP', 'is_error' => true);
			}

			fclose($pipes[0]);
			stream_set_blocking($pipes[1], false);
			stream_set_blocking($pipes[2], false);

			$stdout = '';
			$stderr = '';
			$start = microtime(true);
			$timeout = 10;

			while(true){
				$status = proc_get_status($process);
				if(!$status['running']){
					while(($buf = fread($pipes[1], 8192)) !== '') $stdout .= $buf;
					while(($buf = fread($pipes[2], 8192)) !== '') $stderr .= $buf;
					break;
				}
				if(microtime(true) - $start >= $timeout){
					proc_terminate($process, 9);
					fclose($pipes[1]);
					fclose($pipes[2]);
					proc_close($process);
					@unlink($tmp);
					return array('output' => "PHP execution timed out after {$timeout}s", 'is_error' => true);
				}
				while(($buf = fread($pipes[1], 8192)) !== '') $stdout .= $buf;
				while(($buf = fread($pipes[2], 8192)) !== '') $stderr .= $buf;
				usleep(20000);
			}

			fclose($pipes[1]);
			fclose($pipes[2]);
			$return_code = proc_close($process);
			@unlink($tmp);

			$output = trim($stdout);
			if(!empty($stderr)){
				$output .= ($output ? "\n" : '') . trim($stderr);
			}

			if(strlen($output) > 20000){
				$output = substr($output, 0, 20000) . "\n... [output truncated]";
			}

			if($return_code !== 0){
				return array('output' => $output."\n[Exit code: {$return_code}]", 'is_error' => true);
			}

			return array('output' => $output ?: "[PHP executed successfully with no output]", 'is_error' => false);
		}

		$php_cmd = sprintf('php -d open_basedir=%s -d disable_functions=%s %s',
			escapeshellarg($open_basedir),
			escapeshellarg($disable_functions),
			escapeshellarg($tmp)
		);

		$descriptors = array(
			0 => array('pipe', 'r'),
			1 => array('pipe', 'w'),
			2 => array('pipe', 'w')
		);

		$process = proc_open($php_cmd, $descriptors, $pipes, $this->project_path, $env);

		if(!is_resource($process)){
			@unlink($tmp);
			return array('output' => 'Failed to execute PHP', 'is_error' => true);
		}

		fclose($pipes[0]);
		stream_set_blocking($pipes[1], false);
		stream_set_blocking($pipes[2], false);

		$stdout = '';
		$stderr = '';
		$start = microtime(true);
		$timeout = 10;

		while(true){
			$status = proc_get_status($process);
			if(!$status['running']){
				while(($buf = fread($pipes[1], 8192)) !== '') $stdout .= $buf;
				while(($buf = fread($pipes[2], 8192)) !== '') $stderr .= $buf;
				break;
			}
			if(microtime(true) - $start >= $timeout){
				proc_terminate($process, 9);
				fclose($pipes[1]);
				fclose($pipes[2]);
				proc_close($process);
				@unlink($tmp);
				return array('output' => "PHP execution timed out after {$timeout}s", 'is_error' => true);
			}
			while(($buf = fread($pipes[1], 8192)) !== '') $stdout .= $buf;
			while(($buf = fread($pipes[2], 8192)) !== '') $stderr .= $buf;
			usleep(20000);
		}

		fclose($pipes[1]);
		fclose($pipes[2]);
		$return_code = proc_close($process);
		@unlink($tmp);

		$output = trim($stdout);
		if(!empty($stderr)){
			$output .= ($output ? "\n" : '') . trim($stderr);
		}

		if(strlen($output) > 20000){
			$output = substr($output, 0, 20000) . "\n... [output truncated]";
		}

		if($return_code !== 0){
			return array('output' => $output."\n[Exit code: {$return_code}]", 'is_error' => true);
		}

		return array('output' => $output ?: "[PHP executed successfully with no output]", 'is_error' => false);
	}

	private function format_bytes($bytes){
		if($bytes >= 1048576) return round($bytes / 1048576, 1).'MB';
		if($bytes >= 1024) return round($bytes / 1024, 1).'KB';
		return $bytes.'B';
	}

	private function validate_bash_command_paths($command){
		if($this->restricted_dir === null){
			return true;
		}

		$resolved_project = realpath($this->project_path);
		if($resolved_project === false){
			$resolved_project = $this->project_path;
		}

		// Match absolute paths preceded by a shell token boundary so we catch
		// arguments (e.g. "cat /home/user/file") but not flags like "-p/foo".
		preg_match_all('#(?:^|\s|=|;|\||&|`|\$\(|<|>)(/(?:[a-zA-Z0-9_.+\-]+/)*[a-zA-Z0-9_.+\-]*)#', $command, $matches);
		$paths = !empty($matches[1]) ? $matches[1] : array();

		foreach($paths as $path){
			// System and temp paths are legitimate command references, not user data.
			if(strpos($path, '/dev/') === 0 || strpos($path, '/proc/') === 0 || strpos($path, '/tmp/') === 0){
				continue;
			}
			if(strpos($path, '/usr/') === 0 || strpos($path, '/bin/') === 0 || strpos($path, '/lib/') === 0 || strpos($path, '/etc/') === 0){
				continue;
			}

			// realpath resolves symlinks; if the file doesn't exist yet use the
			// raw path so we still block writes to locations outside restricted_dir.
			$real = realpath($path);
			if($real === false){
				$real = $path;
			}

			if(strpos($real, $this->restricted_dir) !== 0){
				return false;
			}
		}

		// Simulate "../" traversal from the project directory and block if it
		// would escape restricted_dir (e.g. "cat ../../.ssh/id_rsa").
		if(preg_match_all('#(?:^|\s|=|;|\||&|`|\$\(|<|>)(\.\.(?:/\.\.)*)#', $command, $dot_matches)){
			$test_path = $resolved_project;
			foreach($dot_matches[1] as $traversal){
				$levels = substr_count($traversal, '..');
				for($i = 0; $i < $levels; $i++){
					$test_path = dirname($test_path);
				}
			}
			if(strpos($test_path, $this->restricted_dir) !== 0){
				return false;
			}
		}

		return true;
	}

	private function is_dangerous_command($command){
		$protected_dirs = array('.softaculous', '.ssh', '.gnupg', '.softaculous-pro', 'softaculous-pro');
		foreach($protected_dirs as $dir){
			if(preg_match('#/(?:' . preg_quote($dir, '#') . ')(?:/|$)#i', $command)){
				if(preg_match('/\b(rm|rmdir|mv|chmod|chown|chgrp|ln|unlink)\b/', $command)){
					return true;
				}
			}
		}

		$dangerous = array(
			'/\brm\s+-[a-zA-Z]*r[a-zA-Z]*f\s+\/\s*$/',
			'/\brm\s+-[a-zA-Z]*f[a-zA-Z]*r\s+\/\s*$/',
			'/\brm\s+-rf\s+~\s*$/',
			'/\brm\s+-fr\s+~\s*$/',
			'/:\(\)\{.*;\}\s*;&/',
			'/\bdd\s+if=/',
			'/\bmkfs\b/',
			'/\bformat\s+[a-z]:/i',
			'/\bshutdown\b/',
			'/\breboot\b/',
			'/\bhalt\b/',
			'/\binit\s+[06]\b/',
			'/>\s*\/dev\/sda/',
			'/\bchmod\s+-R\s+777\s+\//',
			'/\bchown\s+-R\s+.*\s+\//',
			'/\bcurl\b.*\|\s*(ba)?sh/',
			'/\bwget\b.*\|\s*(ba)?sh/',
			'/\bsudo\b/',
			'/\bsu\s+/',
			'/\bssh\b/',
			'/\bscp\b/',
			'/\brsync\b/',
			'/\bnc\s/',
			'/\bncat\b/',
			'/\bsocat\b/',
			'/\bcrontab\b/',
			'/\bat\s+[a-z]+\s/',
			'/\bsystemctl\b/',
			'/\bservice\b/',
			'/\biptables\b/',
			'/\bip\s+route\b/',
			'/\bmysql\s/',
			'/\bpsql\s/',
			'/\bmongosh\b/',
			'/\bpython\d?\s+-c\s/',
			'/\bperl\s+-e\s/',
			'/\bruby\s+-e\s/',
			'/\bkill\s+-9\s+1\b/',
			'/\bkillall\b/',
			'/\bpkill\b/',
			'/\bshutdown\b/',
			'/\bpoweroff\b/',
		);
		foreach($dangerous as $pattern){
			if(preg_match($pattern, $command)) return true;
		}
		return false;
	}
}
