<?php

if(!defined('SOFTACULOUS')){
	die('Hacking Attempt');
}

class ToolDefinitions {

	private static $tools = null;

	public static function get_all(){
		if(self::$tools !== null) return self::$tools;

		self::$tools = array(
			'read_file' => array(
				'name' => 'read_file',
				'description' => 'Read the contents of a file. Returns the file content with line numbers. Use offset and limit for large files.',
				'parameters' => array(
					'type' => 'object',
					'properties' => array(
						'path' => array(
							'type' => 'string',
							'description' => 'Path to the file relative to the project root'
						),
						'offset' => array(
							'type' => 'integer',
							'description' => 'Line number to start reading from (1-indexed). Default: 1'
						),
						'limit' => array(
							'type' => 'integer',
							'description' => 'Maximum number of lines to read. Default: 2000'
						)
					),
					'required' => array('path')
				)
			),
			'write_file' => array(
				'name' => 'write_file',
				'description' => 'Create a new file or completely overwrite an existing file with the given content. Use this for creating new files or when changes are too large for edit_file.',
				'parameters' => array(
					'type' => 'object',
					'properties' => array(
						'path' => array(
							'type' => 'string',
							'description' => 'Path to the file relative to the project root'
						),
						'content' => array(
							'type' => 'string',
							'description' => 'The complete content to write to the file'
						)
					),
					'required' => array('path', 'content')
				)
			),
			'edit_file' => array(
				'name' => 'edit_file',
				'description' => 'Make a targeted find-and-replace edit to a file. Provide the exact old_string to find and the new_string to replace it with. Prefer this over write_file for small, targeted changes.',
				'parameters' => array(
					'type' => 'object',
					'properties' => array(
						'path' => array(
							'type' => 'string',
							'description' => 'Path to the file relative to the project root'
						),
						'old_string' => array(
							'type' => 'string',
							'description' => 'The exact text to find in the file'
						),
						'new_string' => array(
							'type' => 'string',
							'description' => 'The text to replace old_string with'
						),
						'replace_all' => array(
							'type' => 'boolean',
							'description' => 'Replace all occurrences of old_string. Default: false (replace only the first occurrence)'
						)
					),
					'required' => array('path', 'old_string', 'new_string')
				)
			),
			'bash' => array(
				'name' => 'bash',
				'description' => 'Execute a shell command in the project directory. Use for running tests, build commands, git operations, installing packages, etc.',
				'parameters' => array(
					'type' => 'object',
					'properties' => array(
						'command' => array(
							'type' => 'string',
							'description' => 'The shell command to execute'
						),
						'timeout' => array(
							'type' => 'integer',
							'description' => 'Timeout in seconds. Default: 30'
						)
					),
					'required' => array('command')
				)
			),
			'glob' => array(
				'name' => 'glob',
				'description' => 'Find files matching a glob pattern. Returns list of matching file paths.',
				'parameters' => array(
					'type' => 'object',
					'properties' => array(
						'pattern' => array(
							'type' => 'string',
							'description' => 'Glob pattern (e.g. "**/*.php", "src/**/index.js")'
						),
						'path' => array(
							'type' => 'string',
							'description' => 'Base directory to search in. Default: project root'
						)
					),
					'required' => array('pattern')
				)
			),
			'grep' => array(
				'name' => 'grep',
				'description' => 'Search file contents using a regular expression pattern. Returns matching lines with file paths and line numbers.',
				'parameters' => array(
					'type' => 'object',
					'properties' => array(
						'pattern' => array(
							'type' => 'string',
							'description' => 'Regular expression pattern to search for'
						),
						'path' => array(
							'type' => 'string',
							'description' => 'Directory to search in. Default: project root'
						),
						'include' => array(
							'type' => 'string',
							'description' => 'File pattern to include (e.g. "*.php", "*.js"). Default: all files'
						),
						'max_results' => array(
							'type' => 'integer',
							'description' => 'Maximum number of results. Default: 50'
						)
					),
					'required' => array('pattern')
				)
			),
			'list_directory' => array(
				'name' => 'list_directory',
				'description' => 'List files and directories in a directory tree. Returns a structured tree.',
				'parameters' => array(
					'type' => 'object',
					'properties' => array(
						'path' => array(
							'type' => 'string',
							'description' => 'Directory path relative to project root. Default: /'
						),
						'depth' => array(
							'type' => 'integer',
							'description' => 'Maximum depth to traverse. Default: 2'
						)
					),
					'required' => array()
				)
			),
			'web_fetch' => array(
				'name' => 'web_fetch',
				'description' => 'Fetch content from a URL. Returns the page content as text.',
				'parameters' => array(
					'type' => 'object',
					'properties' => array(
						'url' => array(
							'type' => 'string',
							'description' => 'The URL to fetch'
						)
					),
					'required' => array('url')
				)
			),
			'todo_write' => array(
				'name' => 'todo_write',
				'description' => 'Update the task list. Use this to track progress on complex multi-step tasks.',
				'parameters' => array(
					'type' => 'object',
					'properties' => array(
						'todos' => array(
							'type' => 'array',
							'description' => 'List of todo items',
							'items' => array(
								'type' => 'object',
								'properties' => array(
									'content' => array('type' => 'string', 'description' => 'Task description'),
									'status' => array('type' => 'string', 'enum' => array('pending', 'in_progress', 'completed'), 'description' => 'Task status'),
									'priority' => array('type' => 'string', 'enum' => array('high', 'medium', 'low'), 'description' => 'Priority level')
								),
								'required' => array('content', 'status')
							)
						)
					),
					'required' => array('todos')
				)
			),
			'file_download' => array(
				'name' => 'file_download',
				'description' => 'Download a file from a URL to a local path in the project. Useful for installing plugins, themes, or fetching assets. Maximum file size: 50MB.',
				'parameters' => array(
					'type' => 'object',
					'properties' => array(
						'url' => array(
							'type' => 'string',
							'description' => 'The URL to download from'
						),
						'path' => array(
							'type' => 'string',
							'description' => 'Destination path relative to project root'
						),
						'overwrite' => array(
							'type' => 'boolean',
							'description' => 'Overwrite if file exists. Default: false'
						)
					),
					'required' => array('url', 'path')
				)
			),
			'archive' => array(
				'name' => 'archive',
				'description' => 'Create or extract zip archives. Use for backing up files, extracting plugins/themes, or packaging code.',
				'parameters' => array(
					'type' => 'object',
					'properties' => array(
						'action' => array(
							'type' => 'string',
							'enum' => array('zip', 'unzip'),
							'description' => 'Action to perform: "zip" to create archive, "unzip" to extract'
						),
						'source' => array(
							'type' => 'string',
							'description' => 'Source path. For zip: directory or file to archive. For unzip: the zip file path'
						),
						'destination' => array(
							'type' => 'string',
							'description' => 'Destination path. For zip: the output .zip file path. For unzip: directory to extract into. Default: project root'
						)
					),
					'required' => array('action', 'source')
				)
			),
			'search_replace' => array(
				'name' => 'search_replace',
				'description' => 'Search and replace text across multiple files in the project. Returns a summary of changes made. Use for bulk updates, migrations, renaming. Maximum 100 files per operation.',
				'parameters' => array(
					'type' => 'object',
					'properties' => array(
						'search' => array(
							'type' => 'string',
							'description' => 'The text or regex pattern to search for'
						),
						'replace' => array(
							'type' => 'string',
							'description' => 'The replacement text'
						),
						'include' => array(
							'type' => 'string',
							'description' => 'File pattern to include (e.g. "*.php", "*.js", "*.css"). Default: all text files'
						),
						'path' => array(
							'type' => 'string',
							'description' => 'Directory to search in. Default: project root'
						),
						'max_files' => array(
							'type' => 'integer',
							'description' => 'Maximum number of files to modify. Default: 50'
						),
						'regex' => array(
							'type' => 'boolean',
							'description' => 'Treat search as a regex pattern. Default: false (literal match)'
						)
					),
					'required' => array('search', 'replace')
				)
			),
			'php_eval' => array(
				'name' => 'php_eval',
				'description' => 'Execute PHP code and return the output. Useful for WordPress operations like get_option(), wp_remote_get(), checking constants, running wp-cli style checks. The code runs in the project context. Output is captured via echo/print. Maximum execution time: 10 seconds.',
				'parameters' => array(
					'type' => 'object',
					'properties' => array(
						'code' => array(
							'type' => 'string',
							'description' => 'PHP code to execute (without <?php tags). Use echo/print to return output.'
						)
					),
					'required' => array('code')
				)
			)
		);

		return self::$tools;
	}

	public static function get_by_name($name){
		$all = self::get_all();
		return isset($all[$name]) ? $all[$name] : null;
	}

	public static function get_for_mode($mode){
		$all = self::get_all();
		if($mode === 'plan'){
			unset($all['write_file'], $all['edit_file'], $all['bash'], $all['file_download'], $all['archive'], $all['search_replace'], $all['php_eval']);
			return $all;
		}
		return $all;
	}

	public static function get_names(){
		return array_keys(self::get_all());
	}
}
