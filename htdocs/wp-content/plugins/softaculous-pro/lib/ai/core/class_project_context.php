<?php

if(!defined('SOFTACULOUS')){
	die('Hacking Attempt');
}

class ProjectContext {
	private $project_path;

	public function __construct($project_path){
		$this->project_path = rtrim($project_path, '/');
	}

	public function detect_type(){
		if(file_exists($this->project_path.'/wp-config.php') || file_exists($this->project_path.'/wp-load.php')){
			return 'wordpress';
		}
		if(file_exists($this->project_path.'/artisan')){
			return 'laravel';
		}
		if(file_exists($this->project_path.'/config/core.php') && is_dir($this->project_path.'/web')){
			return 'concrete5';
		}
		if(file_exists($this->project_path.'/app/etc/env.php') || file_exists($this->project_path.'/app/Mage.php')){
			return 'magento';
		}
		if(file_exists($this->project_path.'/composer.json')){
			$composer = @json_decode(@file_get_contents($this->project_path.'/composer.json'), true);
			if(!empty($composer['require']['laravel/framework'])) return 'laravel';
			if(!empty($composer['require']['symfony/symfony'])) return 'symfony';
			if(!empty($composer['require']['drupal/core'])) return 'drupal';
			return 'php-composer';
		}
		if(file_exists($this->project_path.'/package.json')){
			$pkg = @json_decode(@file_get_contents($this->project_path.'/package.json'), true);
			if(!empty($pkg['dependencies']['next'])) return 'nextjs';
			if(!empty($pkg['dependencies']['react'])) return 'react';
			if(!empty($pkg['dependencies']['vue'])) return 'vue';
			return 'node';
		}
		if(is_dir($this->project_path.'/public_html') || is_dir($this->project_path.'/public')){
			return 'php';
		}
		$files = glob($this->project_path.'/*.php');
		if(!empty($files)) return 'php';
		$files = glob($this->project_path.'/*.html');
		if(!empty($files)) return 'static';
		return 'unknown';
	}

	public function get_overview(){
		$type = $this->detect_type();
		$overview = "Project type: {$type}\n";
		$overview .= "Project path: {$this->project_path}\n";
		$overview .= "NOTE: Outside the Project path - access is strictly limited to read and list operations only.\n";
		$overview .= "Do not use bash tool, for reading, or writing a file or directory in any case\n\n";

		$structure = $this->get_structure_summary(2);
		if(!empty($structure)){
			$overview .= "Project structure:\n{$structure}\n\n";
		}

		$stats = $this->get_file_stats();
		if(!empty($stats)){
			$overview .= "File statistics:\n";
			foreach($stats as $ext => $count){
				$overview .= "  .{$ext}: {$count} files\n";
			}
		}

		return $overview;
	}

	public function get_structure_summary($max_depth = 2){
		$result = $this->scan_structure($this->project_path, $max_depth, 0);
		return $result;
	}

	private function scan_structure($path, $max_depth, $current_depth){
		if($current_depth >= $max_depth) return '';

		$skip = array('.', '..', '.git', 'node_modules', 'vendor', '.svn', '.ai_git', '.ai_backups', 'cache', 'tmp', 'temp');
		$items = @scandir($path);
		if(!$items) return '';

		$output = '';
		$indent = str_repeat('  ', $current_depth);
		$basename = $current_depth === 0 ? basename($path) : basename($path);

		$dirs = array();
		$files = array();

		foreach($items as $item){
			if(in_array($item, $skip)) continue;
			if(strpos($item, '.') === 0 && strlen($item) > 1) continue;
			$full = $path.'/'.$item;
			if(is_dir($full)){
				$dirs[] = $item;
			}else{
				$files[] = $item;
			}
		}

		foreach($dirs as $d){
			$output .= $indent . '📁 ' . $d . "/\n";
			$output .= $this->scan_structure($path.'/'.$d, $max_depth, $current_depth + 1);
		}

		sort($files);
		foreach(array_slice($files, 0, 20) as $f){
			$output .= $indent . '📄 ' . $f . "\n";
		}
		if(count($files) > 20){
			$output .= $indent . '  ... and ' . (count($files) - 20) . ' more files\n';
		}

		return $output;
	}

	public function get_file_stats(){
		$stats = array();
		$this->count_by_ext($this->project_path, $stats, 0, 4);
		arsort($stats);
		return array_slice($stats, 0, 15);
	}

	private function count_by_ext($path, array &$stats, $depth, $max_depth){
		if($depth >= $max_depth) return;
		$skip = array('.', '..', '.git', 'node_modules', 'vendor', '.svn', '.ai_git', '.ai_backups');
		$items = @scandir($path);
		if(!$items) return;

		foreach($items as $item){
			if(in_array($item, $skip)) continue;
			if(strpos($item, '.') === 0 && strlen($item) > 1) continue;
			$full = $path.'/'.$item;
			if(is_dir($full)){
				$this->count_by_ext($full, $stats, $depth + 1, $max_depth);
			}else{
				$ext = strtolower(pathinfo($item, PATHINFO_EXTENSION));
				if(empty($ext)) $ext = 'other';
				if(!isset($stats[$ext])) $stats[$ext] = 0;
				$stats[$ext]++;
			}
		}
	}

	public function get_system_prompt_additions(){
		$type = $this->detect_type();
		$prompts = array(
			'wordpress' => "This is a WordPress installation. Follow WordPress coding standards. Use WordPress functions and APIs. The main files are in wp-content/ (themes, plugins).",
			'laravel' => "This is a Laravel application. Follow PSR standards. Routes are in routes/, controllers in app/Http/Controllers/, models in app/Models/, views in resources/views/.",
			'php' => "This is a PHP application. Follow PSR coding standards.",
			'php-composer' => "This is a PHP application using Composer for dependency management.",
			'react' => "This is a React application. Components typically use JSX. State management via hooks or external libraries.",
			'nextjs' => "This is a Next.js application. Uses file-based routing in the app/ or pages/ directory.",
			'vue' => "This is a Vue.js application. Components use .vue single-file components.",
			'node' => "This is a Node.js application.",
			'static' => "This is a static HTML/CSS/JS site.",
		);

		if(isset($prompts[$type])){
			return $prompts[$type];
		}
		return "Analyze the codebase structure to understand the project before making changes.";
	}
}

