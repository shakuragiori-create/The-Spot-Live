<?php

if(!defined('SOFTACULOUS')){
	die('Hacking Attempt');
}

class AISnapshotManager {
	private $project_path;
	private $enabled = false;
	private $git_dir;
	private $remote_enabled = false;
	private $remote_url = '';
	private $user_home_dir;
	private $jail_script;
	private $use_jail = null;

	public function __construct($project_path, $enabled = false, $user_home_dir = null) {
		global $globals;

		$this->project_path = rtrim($project_path, '/');
		$this->git_dir = $this->project_path . '/.ai_git';
		$this->enabled = $enabled;
		$this->user_home_dir = $user_home_dir ? rtrim($user_home_dir, '/') : $this->project_path;

		if(!empty($globals['path'])){
			$this->jail_script = $globals['path'].'/bin/ai_jail.sh';
		}

		$this->check_jail_availability();

		$pw = function_exists('posix_getpwuid') ? @posix_getpwuid(posix_getuid()) : null;
		if(!empty($pw['dir'])){
			putenv('HOME=' . $pw['dir']);
		}

		if(!empty($pw['name'])){
			$git_config_result = $this->exec_git('git config --global user.name 2>/dev/null');
			$git_user = trim(implode('', $git_config_result['output']));
			if(empty($git_user)){
				$home = $pw['dir'];
				@file_put_contents($home . '/.gitconfig', "[user]\n\temail = {$pw['name']}@webuzo.loc\n\tname = {$pw['name']}\n");
			}
		}

		if ($enabled) {
			$this->init();
		}
	}

	private function check_jail_availability(){
		if(empty($this->jail_script) || !file_exists($this->jail_script) || !is_executable($this->jail_script)){
			$this->use_jail = false;
			return;
		}

		$test_cmd = sprintf('%s %s -- echo JAIL_TEST_OK 2>/dev/null',
			escapeshellarg($this->jail_script),
			escapeshellarg($this->user_home_dir)
		);

		$output = shell_exec($test_cmd);
		if(strpos($output, 'JAIL_TEST_OK') !== false){
			$this->use_jail = true;
		}else{
			$this->use_jail = false;
		}
	}

	private function exec_git($command){
		$full_cmd = 'cd ' . escapeshellarg($this->project_path) . ' && ' . $command;

		if($this->use_jail){
			$full_cmd = sprintf('%s %s -- bash -c %s',
				escapeshellarg($this->jail_script),
				escapeshellarg($this->user_home_dir),
				escapeshellarg($full_cmd)
			);
		}

		$output = array();
		$return_var = 0;
		exec($full_cmd . ' 2>&1', $output, $return_var);

		return array('output' => $output, 'return_var' => $return_var);
	}

	public function is_enabled() {
		return $this->enabled;
	}

	public function init() {
		if (!is_dir($this->git_dir)) {
			$result = $this->exec_git('git init 2>&1');
			if ($result['return_var'] !== 0) {
				return false;
			}

			$gitignore_content = ".ai_git/\n.ai_backups/\n";
			@file_put_contents($this->project_path . '/.gitignore', $gitignore_content);

			$this->exec_git('git add .gitignore && git commit -m "Initial AI snapshot setup" 2>&1');
		}
		return true;
	}

	public function create_snapshot($message){
		if (!$this->enabled) {
			return array('error' => 'Snapshots are not enabled');
		}

		$this->exec_git('git add -A 2>&1');

		$status_result = $this->exec_git('git status --porcelain 2>&1');

		$status_combined = implode('', $status_result['output']);
		if (empty($status_combined)) {
			return array('error' => 'No changes to snapshot');
		}

		$commit_message = escapeshellarg($message);
		$result = $this->exec_git('git commit -m ' . $commit_message . ' 2>&1');

		if ($result['return_var'] !== 0) {
			return array('error' => 'Failed to create snapshot: ' . implode("\n", $result['output']));
		}

		$hash = trim(implode('', $result['output']));
		if (preg_match('/\[([a-f0-9]+)/', $hash, $matches)) {
			$hash = $matches[1];
		} else {
			$hash_result = $this->exec_git('git rev-parse HEAD 2>&1');
			$hash = !empty($hash_result['output'][0]) ? trim($hash_result['output'][0]) : '';
		}

		return array(
			'id' => $hash,
			'message' => $message,
			'timestamp' => time()
		);
	}

	public function restore_snapshot($commit_hash){
		if (!$this->enabled) {
			return array('error' => 'Snapshots are not enabled');
		}

		$result = $this->exec_git('git checkout ' . escapeshellarg($commit_hash) . ' -- . 2>&1');

		if ($result['return_var'] !== 0) {
			return array('error' => 'Failed to restore snapshot: ' . implode("\n", $result['output']));
		}

		return array(
			'restored' => true,
			'commit' => $commit_hash
		);
	}

	public function list_snapshots($limit = 50){
		if (!$this->enabled) {
			return array();
		}

		$result = $this->exec_git('git log --format="%h %ct %s" -' . intval($limit) . ' 2>&1');

		$snapshots = array();
		foreach ($result['output'] as $line) {
			if (preg_match('/^([a-f0-9]+)\s+(\d+)\s+(.*)$/', $line, $matches)) {
				$snapshots[] = array(
					'id' => $matches[1],
					'message' => $matches[3],
					'time' => (int)$matches[2]
				);
			}
		}

		return $snapshots;
	}

	public function get_snapshot_diff($commit_hash){
		if (!$this->enabled) {
			return array('error' => 'Snapshots are not enabled');
		}

		$result = $this->exec_git('git show --stat ' . escapeshellarg($commit_hash) . ' 2>&1');

		return array(
			'commit' => $commit_hash,
			'diff' => implode("\n", $result['output'])
		);
	}

	public function get_working_diff() {
		if (!$this->enabled) {
			return array('error' => 'Snapshots are not enabled');
		}

		$result = $this->exec_git('git diff HEAD 2>&1');

		return array(
			'diff' => implode("\n", $result['output'])
		);
	}

	public function push_to_remote($remote = 'origin'){
		if (!$this->enabled) {
			return array('error' => 'Snapshots are not enabled');
		}

		if (empty($this->remote_url)) {
			return array('error' => 'No remote configured');
		}

		$result = $this->exec_git('git push ' . escapeshellarg($remote) . ' master 2>&1');

		if ($result['return_var'] !== 0) {
			return array('error' => 'Failed to push: ' . implode("\n", $result['output']));
		}

		return array('pushed' => true, 'remote' => $remote);
	}

	public function pull_from_remote($remote = 'origin'){
		if (!$this->enabled) {
			return array('error' => 'Snapshots are not enabled');
		}

		$result = $this->exec_git('git pull ' . escapeshellarg($remote) . ' master 2>&1');

		if ($result['return_var'] !== 0) {
			return array('error' => 'Failed to pull: ' . implode("\n", $result['output']));
		}

		return array('pulled' => true, 'remote' => $remote);
	}

	public function set_remote($url, $name = 'origin'){
		$this->remote_url = $url;
		$this->remote_enabled = true;

		if ($this->enabled && !empty($url)) {
			$this->exec_git('git remote add ' . escapeshellarg($name) . ' ' . escapeshellarg($url) . ' 2>&1');
		}
	}

	public function get_current_branch(){
		if (!$this->enabled) {
			return '';
		}

		$result = $this->exec_git('git rev-parse --abbrev-ref HEAD 2>&1');

		return !empty($result['output'][0]) ? trim($result['output'][0]) : '';
	}

	public function get_uncommitted_changes(){
		if (!$this->enabled) {
			return array();
		}

		$result = $this->exec_git('git status --porcelain 2>&1');

		$changes = array();
		foreach ($result['output'] as $line) {
			if (preg_match('/^([ADMRQ?])\s+(.*)$/', trim($line), $matches)) {
				$changes[] = array(
					'status' => $matches[1],
					'file' => $matches[2]
				);
			}
		}

		return $changes;
	}

	public function discard_changes($file_path = ''){
		if (!$this->enabled) {
			return array('error' => 'Snapshots are not enabled');
		}

		if (empty($file_path)) {
			$result = $this->exec_git('git checkout -- . 2>&1');
		} else {
			$result = $this->exec_git('git checkout -- ' . escapeshellarg($file_path) . ' 2>&1');
		}

		if ($result['return_var'] !== 0) {
			return array('error' => 'Failed to discard changes');
		}

		return array('discarded' => true);
	}
}
