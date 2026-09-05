<?php

/*
 * SoftWP AI Chat - Standalone Entry Point
 * 
 * This file does NOT load WordPress. It validates the user's WordPress
 * login cookie directly against the database, making it resilient to
 * WordPress PHP-level breakage. If the AI agent breaks any WordPress
 * file, this entry point continues to function for self-healing.
 */


// ===== 1. PATH CONSTANTS =====

$abspath = '';
for($i = 4; $i <= 10; $i++){
	$dir = dirname(__FILE__, $i);
	if(file_exists($dir . '/wp-load.php')){
		$abspath = $dir . '/';
		break;
	}
}
if(empty($abspath)){
	echo '<!DOCTYPE html><html><head><meta charset="utf-8"><title>Error</title></head><body>';
	echo '<p>Could not locate WordPress root directory.</p>';
	echo '</body></html>';
	die();
}

define('ABSPATH', $abspath);
define('WP_CONTENT_DIR', ABSPATH . 'wp-content');
define('SOFTACULOUS_PRO_DIR', dirname(__FILE__, 2));
define('SOFTACULOUS_PRO_AI_DIR', SOFTACULOUS_PRO_DIR . '/lib/ai');
define('SOFTACULOUS_PRO_AI_NONCE', 'softaculous_pro_ai_chat');
define('SOFTACULOUS_PRO_AI_STORAGE', WP_CONTENT_DIR . '/softaculous-pro/ai');

if(!defined('SOFTACULOUS_PRO_PLUGIN_URL')){
	$host = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : 'localhost';
	$script_path = isset($_SERVER['SCRIPT_NAME']) ? $_SERVER['SCRIPT_NAME'] : '';
	$plugin_url_path = dirname($script_path, 2) . '/main';
	$proto = 'http://';
	if(isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on'){
		$proto = 'https://';
	}elseif(isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https'){
		$proto = 'https://';
	}
	define('SOFTACULOUS_PRO_PLUGIN_URL', $proto . $host . $plugin_url_path);
	unset($proto, $host, $script_path, $plugin_url_path);
}

define('SOFTACULOUS', true);
define('APP', 'SoftWP AI');

// ===== 2. WP_STANDALONE_AUTH CLASS =====

class WP_Standalone_Auth {

	private $db = null;
	private $config = array();
	private $table_prefix = 'wp_';
	private $auth_keys = array();
	private $siteurl = null;
	private $cookie_hash = null;

	public function __construct(){
		$this->parse_wp_config();
		$this->connect_db();
	}

	private function parse_wp_config(){
		$config_paths = ABSPATH . 'wp-config.php';

		$config_content = null;
		if(file_exists($config_paths)){
			$config_content = file_get_contents($config_paths);
		}

		if(empty($config_content)){
			die('Could not find wp-config.php. Please ensure WordPress is installed.');
		}

		$this->config = array();

		$string_constants = array('DB_NAME', 'DB_USER', 'DB_PASSWORD', 'DB_HOST', 'DB_CHARSET', 'DB_COLLATE');
		foreach($string_constants as $d){
			if(preg_match("/define\s*\(\s*['\"]" . preg_quote($d, '/') . "['\"]\s*,\s*'((?:[^'\\\\]|\\\\.)*)'\s*\)/s", $config_content, $m)){
				$this->config[$d] = stripslashes($m[1]);
			}elseif(preg_match("/define\s*\(\s*['\"]" . preg_quote($d, '/') . "['\"]\s*,\s*\"((?:[^\"\\\\]|\\\\.)*)\"\s*\)/s", $config_content, $m)){
				$this->config[$d] = stripslashes($m[1]);
			}
		}

		if(preg_match('/\$table_prefix\s*=\s*[\'"]([^\'"]*?)[\'"]/', $config_content, $m)){
			$this->table_prefix = $m[1];
		}

		if(!preg_match('/^[a-zA-Z0-9_]+$/', $this->table_prefix)){
			$this->table_prefix = 'wp_';
		}

		$salt_keys = array('LOGGED_IN_KEY', 'LOGGED_IN_SALT', 'NONCE_KEY', 'NONCE_SALT', 'AUTH_KEY', 'AUTH_SALT', 'SECURE_AUTH_KEY', 'SECURE_AUTH_SALT');
		foreach($salt_keys as $k){
			if(preg_match("/define\s*\(\s*['\"]" . preg_quote($k, '/') . "['\"]\s*,\s*'((?:[^'\\\\]|\\\\.)*)'\s*\)/s", $config_content, $m)){
				$this->auth_keys[$k] = stripslashes($m[1]);
			}elseif(preg_match("/define\s*\(\s*['\"]" . preg_quote($k, '/') . "['\"]\s*,\s*\"((?:[^\"\\\\]|\\\\.)*)\"\s*\)/s", $config_content, $m)){
				$this->auth_keys[$k] = stripslashes($m[1]);
			}
		}

		if(preg_match("/define\s*\(\s*['\"]MULTISITE['\"]\s*,\s*(true|1|false|0)\s*\)/s", $config_content, $m)){
			$this->config['MULTISITE'] = ($m[1] === 'true' || $m[1] === '1');
		}
	}

	private function connect_db(){
		$host = isset($this->config['DB_HOST']) ? $this->config['DB_HOST'] : 'localhost';
		$user = isset($this->config['DB_USER']) ? $this->config['DB_USER'] : '';
		$pass = isset($this->config['DB_PASSWORD']) ? $this->config['DB_PASSWORD'] : '';
		$name = isset($this->config['DB_NAME']) ? $this->config['DB_NAME'] : '';
		$port = 3306;
		$socket = null;

		if(strpos($host, ':/') === 0 || strpos($host, '/') === 0){
			$socket = $host;
			if(strpos($socket, ':') === 0) $socket = substr($socket, 1);
			$host = 'localhost';
		}elseif(preg_match('/^\[([0-9a-fA-F:]+)\](?::(\d+))?$/', $host, $m)){
			$host = $m[1];
			if(!empty($m[2])) $port = (int)$m[2];
		}elseif(strpos($host, ':') !== false){
			$parts = explode(':', $host);
			if(count($parts) === 2 && is_numeric($parts[1])){
				$host = $parts[0];
				$port = (int)$parts[1];
			}
		}

		$this->db = new \mysqli($host, $user, $pass, $name, $port, $socket);
		if($this->db->connect_error){
			die('Database connection failed. Please ensure the database is running.');
		}

		$charset = isset($this->config['DB_CHARSET']) ? $this->config['DB_CHARSET'] : 'utf8mb4';
		if(empty($charset)) $charset = 'utf8mb4';
		$this->db->set_charset($charset);
	}

	public function get_option($key){
		$stmt = $this->db->prepare("SELECT option_value FROM `{$this->table_prefix}options` WHERE option_name = ? LIMIT 1");
		if(!$stmt) return false;
		$stmt->bind_param('s', $key);
		$stmt->execute();
		$result = $stmt->get_result();
		$row = $result->fetch_assoc();
		$stmt->close();

		if(!$row) return false;
		return $this->maybe_unserialize($row['option_value']);
	}

	private function maybe_unserialize($data){
		if(!is_string($data)) return $data;
		if($data === '' || !preg_match('/^[aAbBoOsSiINn]:/', $data)) return $data;
		$val = @unserialize($data);
		if($val !== false || $data === 'b:0;') return $val;
		return $data;
	}

	public function get_cookie_hash(){
		if($this->cookie_hash !== null) return $this->cookie_hash;
		$siteurl = $this->get_option('siteurl');
		if(!$siteurl) $siteurl = '';
		$this->siteurl = $siteurl;
		$this->cookie_hash = md5($siteurl);
		return $this->cookie_hash;
	}

	public function validate_cookie(){
		$result = $this->validate_auth_cookie('logged_in');
		if($result) return $result;

		$result = $this->validate_auth_cookie('auth');
		if($result) return $result;

		$result = $this->validate_auth_cookie('secure_auth');
		return $result;
	}

	private function validate_auth_cookie($scheme = 'logged_in'){
		$hash = $this->get_cookie_hash();

		if($scheme === 'logged_in'){
			$cookie_name = 'wordpress_logged_in_' . $hash;
		}elseif($scheme === 'secure_auth'){
			$cookie_name = 'wordpress_sec_' . $hash;
		}else{
			$cookie_name = 'wordpress_' . $hash;
		}

		if(!isset($_COOKIE[$cookie_name])) return false;

		$cookie_value = $_COOKIE[$cookie_name];
		$elements = explode('|', $cookie_value);
		if(count($elements) !== 4) return false;

		list($username, $expiration, $token, $hmac) = $elements;

		if($expiration < time()) return false;

		$user = $this->get_user_by_login($username);
		if(!$user) return false;

		// Compute pass_frag based on hash type
		$user_pass = $user['user_pass'];
		if(strpos($user_pass, '$P$') === 0 || strpos($user_pass, '$2y$') === 0){
			$pass_frag = substr($user_pass, 8, 4);
		}else{
			$pass_frag = substr($user_pass, -4);
		}

		// Determine salt based on scheme
		if($scheme === 'logged_in'){
			$salt = (isset($this->auth_keys['LOGGED_IN_KEY']) ? $this->auth_keys['LOGGED_IN_KEY'] : '') . 
					(isset($this->auth_keys['LOGGED_IN_SALT']) ? $this->auth_keys['LOGGED_IN_SALT'] : '');
		}elseif($scheme === 'secure_auth'){
			$salt = (isset($this->auth_keys['SECURE_AUTH_KEY']) ? $this->auth_keys['SECURE_AUTH_KEY'] : '') . 
					(isset($this->auth_keys['SECURE_AUTH_SALT']) ? $this->auth_keys['SECURE_AUTH_SALT'] : '');
		}else{
			$salt = (isset($this->auth_keys['AUTH_KEY']) ? $this->auth_keys['AUTH_KEY'] : '') . 
					(isset($this->auth_keys['AUTH_SALT']) ? $this->auth_keys['AUTH_SALT'] : '');
		}

		$key = hash_hmac('md5', $username . '|' . $pass_frag . '|' . $expiration . '|' . $token, $salt);
		$expected_hmac = hash_hmac('sha256', $username . '|' . $expiration . '|' . $token, $key);

		if(!hash_equals($expected_hmac, $hmac)) return false;

		// Verify session token exists in usermeta
		if(!$this->verify_session_token((int)$user['ID'], $token)) return false;

		return array(
			'user_id' => (int)$user['ID'],
			'username' => $user['user_login'],
			'token' => $token,
		);
	}

	private function get_user_by_login($username){
		$stmt = $this->db->prepare("SELECT ID, user_login, user_pass FROM `{$this->table_prefix}users` WHERE user_login = ? LIMIT 1");
		if(!$stmt) return false;
		$stmt->bind_param('s', $username);
		$stmt->execute();
		$result = $stmt->get_result();
		$user = $result->fetch_assoc();
		$stmt->close();
		return $user;
	}

	public function verify_session_token($user_id, $token){
		$meta_key = 'session_tokens';
		$stmt = $this->db->prepare("SELECT meta_value FROM `{$this->table_prefix}usermeta` WHERE user_id = ? AND meta_key = ? LIMIT 1");
		if(!$stmt) return false;
		$stmt->bind_param('is', $user_id, $meta_key);
		$stmt->execute();
		$result = $stmt->get_result();
		$row = $result->fetch_assoc();
		$stmt->close();

		if(!$row) return false;

		$sessions = $this->maybe_unserialize($row['meta_value']);
		if(!is_array($sessions)) return false;

		$verifier = hash('sha256', $token);
		if(!isset($sessions[$verifier])) return false;

		if(isset($sessions[$verifier]['expiration']) && $sessions[$verifier]['expiration'] < time()) return false;

		return true;
	}

	public function user_can($user_id, $capability){
		$meta_key = $this->table_prefix . 'capabilities';
		$stmt = $this->db->prepare("SELECT meta_value FROM `{$this->table_prefix}usermeta` WHERE user_id = ? AND meta_key = ? LIMIT 1");
		if(!$stmt) return false;
		$stmt->bind_param('is', $user_id, $meta_key);
		$stmt->execute();
		$result = $stmt->get_result();
		$row = $result->fetch_assoc();
		$stmt->close();

		if(!$row) return false;

		$caps = $this->maybe_unserialize($row['meta_value']);

		if(!is_array($caps)) return false;


		return !empty($caps[$capability]);
	}

	public function create_nonce($action, $user_id, $token){
		$tick = ceil(time() / 43200);
		$salt = (isset($this->auth_keys['NONCE_KEY']) ? $this->auth_keys['NONCE_KEY'] : '') . 
				(isset($this->auth_keys['NONCE_SALT']) ? $this->auth_keys['NONCE_SALT'] : '');
		$hash = hash_hmac('md5', $tick . '|' . $action . '|' . $user_id . '|' . $token, $salt);
		return substr($hash, -12, 10);
	}

	public function verify_nonce($nonce, $action, $user_id, $token){
		if(empty($nonce)) return false;
		$nonce = (string)$nonce;
		$tick = ceil(time() / 43200);
		$salt = (isset($this->auth_keys['NONCE_KEY']) ? $this->auth_keys['NONCE_KEY'] : '') . 
				(isset($this->auth_keys['NONCE_SALT']) ? $this->auth_keys['NONCE_SALT'] : '');

		$expected = substr(hash_hmac('md5', $tick . '|' . $action . '|' . $user_id . '|' . $token, $salt), -12, 10);
		if(hash_equals($expected, $nonce)) return true;

		$expected = substr(hash_hmac('md5', ($tick - 1) . '|' . $action . '|' . $user_id . '|' . $token, $salt), -12, 10);
		if(hash_equals($expected, $nonce)) return true;

		return false;
	}

	public function is_multisite(){
		if(isset($this->config['MULTISITE']) && $this->config['MULTISITE']) return true;

		// Also check wp-config for WP_ALLOW_MULTISITE or MULTISITE define
		// and check if wp_blogs table exists
		$result = $this->db->query("SHOW TABLES LIKE '{$this->table_prefix}blogs'");
		if($result && $result->num_rows > 0) return true;

		return false;
	}

	public function get_wp_version(){
		$version_file = ABSPATH . 'wp-includes/version.php';
		if(file_exists($version_file)){
			$content = file_get_contents($version_file);
			if(preg_match('/\$wp_version\s*=\s*[\'"]([^\'"]+)[\'"]/', $content, $m)){
				return $m[1];
			}
		}
		return '';
	}

	public function get_siteurl(){
		if($this->siteurl !== null) return $this->siteurl;
		$this->siteurl = $this->get_option('siteurl');
		return $this->siteurl;
	}
}


// ===== 3. STANDALONE REPLACEMENT FUNCTIONS =====

function esc_html($s){
	return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');
}

function esc_attr($s){
	return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');
}

function sanitize_text_field($s){
	if(!is_string($s)) return '';
	$s = stripslashes($s);
	$s = strip_tags($s);
	$s = preg_replace('|%[0-9a-fA-F]{2}|', '', $s);
	$s = preg_replace('/[\x00-\x1F\x7F]/', '', $s);
	$s = preg_replace('/\s+/', ' ', $s);
	return trim($s);
}

function stripslashes_deep($value){
	if(is_array($value)){
		return array_map('stripslashes_deep', $value);
	}
	if(is_object($value)){
		$vars = get_object_vars($value);
		foreach($vars as $key => $data){
			$value->$key = stripslashes_deep($data);
		}
		return $value;
	}
	return stripslashes($value);
}

function send_json_error($data, $status_code = 400){
	$response = array('success' => false);
	if(is_array($data)){
		$response['data'] = $data;
	}else{
		$response['data'] = array('message' => $data);
	}
	http_response_code($status_code);
	header('Content-Type: application/json; charset=UTF-8');
	echo json_encode($response);
	die();
}


// ===== 4. PLUGIN HELPER FUNCTIONS =====

function spro_ai_protect_storage(){
	$dir = rtrim(SOFTACULOUS_PRO_AI_STORAGE, '/');
	if(!is_dir($dir)){
		return;
	}

	$htaccess = $dir . '/.htaccess';
	if(!file_exists($htaccess)){
		$htaccess_content = 'deny from all';
		@file_put_contents($htaccess, $htaccess_content);
	}

	$webconfig = $dir . '/web.config';
	if(!file_exists($webconfig)){
		$webconfig_content = '<configuration>
<system.webServer>
<authorization>
<deny users="*" />
</authorization>
</system.webServer>
</configuration>';
		@file_put_contents($webconfig, $webconfig_content);
	}
}

spro_ai_protect_storage();

function GET($key, $default = ''){
	if(isset($_GET[$key])){
		return sanitize_text_field(stripslashes_deep($_GET[$key]));
	}
	return $default;
}

function optGET($key){
	if(isset($_GET[$key])){
		return sanitize_text_field(stripslashes_deep($_GET[$key]));
	}
	return '';
}

function cleanpath($path){
	if(empty($path)) return '';
	$path = str_replace('\\', '/', $path);
	while(strpos($path, '//') !== false){
		$path = str_replace('//', '/', $path);
	}
	while(strpos($path, '/./') !== false){
		$path = str_replace('/./', '/', $path);
	}
	$parts = explode('/', $path);
	$normalized = array();
	foreach($parts as $part){
		if($part === '..'){
			if(!empty($normalized)){
				array_pop($normalized);
			}
		}elseif($part !== '' && $part !== '.'){
			$normalized[] = $part;
		}
	}
	return '/' . implode('/', $normalized);
}

function reporterror($title, $msg){
	if(!empty($_GET['ai_php_api']) || !empty($_GET['ai_chat_stream'])){
		send_json_error(array('error' => $msg, 'title' => $title), 403);
	}

	softheader($title);

	echo '<div style="max-width:600px;margin:80px auto;padding:40px;font-family:-apple-system,BlinkMacSystemFont,Segoe UI,Roboto,sans-serif;text-align:center;">';
	echo '<div style="font-size:48px;margin-bottom:20px;">&#9888;</div>';
	echo '<h2 style="color:#dc3232;margin:0 0 10px;">' . esc_html($title) . '</h2>';
	echo '<p style="color:#666;line-height:1.6;">' . esc_html($msg) . '</p>';
	echo '</div>';

	softfooter();
	die();
}

function softheader($title = ''){
	echo '<!DOCTYPE html><html><head><meta charset="utf-8"><title>' . esc_html($title) . '</title></head><body>';
}

function softfooter(){
	echo '</body></html>';
}

function csrf_display(){
	global $softaculous_auth, $auth_result;
	$nonce = $softaculous_auth->create_nonce(SOFTACULOUS_PRO_AI_NONCE, $auth_result['user_id'], $auth_result['token']);
	echo '<input type="hidden" name="csrf_token" value="' . esc_attr($nonce) . '">';
}

function __($str, $args = array()){
	if(is_array($args)){
		foreach($args as $k => $v){
			$str = str_replace('$' . $k, $v, $str);
		}
	}
	return $str;
}

function get_sid_by_version($ver, $sid){
	return $sid;
}

function validate_path_within_home($path, $home_dir){
	if(empty($path)) return false;
	$resolved = realpath($path);
	if($resolved === false) return false;
	$home_dir = rtrim($home_dir, '/');
	$pos = strpos($resolved . '/', $home_dir . '/');
	if($pos !== 0){
		return false;
	}
	return $resolved;
}

function has_active_license(){
	global $softaculous_auth;
	$license = $softaculous_auth->get_option('softaculous_pro_license');
	return !empty($license['active']);
}


// ===== 5. AUTHENTICATION =====

$softaculous_auth = new WP_Standalone_Auth();

$auth_result = $softaculous_auth->validate_cookie();
if(!$auth_result){
	reporterror('Authentication Required', 'Please log in to WordPress to access the AI Assistant.');
}

if(!$softaculous_auth->user_can($auth_result['user_id'], 'administrator')){
	reporterror('Access Denied', 'You do not have permission to access the AI Assistant.');
}

if($softaculous_auth->is_multisite()){
	reporterror('Access Denied', 'Multisite setups are not supported.');
}

$softaculous_pro_settings = $softaculous_auth->get_option('softaculous_pro_settings');

if(!empty($softaculous_pro_settings) && !empty($softaculous_pro_settings['disable_ai_coder'])){
	reporterror('Access Denied', 'Softaculous AI is disabled.');
}

include_once(SOFTACULOUS_PRO_AI_DIR . '/ai_launcher.php');

// ===== 6. BOOTSTRAP =====

function bootstrap(){
	global $softaculous_auth, $auth_result;

	if(!defined('SOFTACULOUS')){
		define('SOFTACULOUS', true);
	}

	if(!defined('APP')){
		define('APP', 'SoftWP AI');
	}

	$current_user = $auth_result['username'];
	$home_dir = rtrim(ABSPATH, '/');

	$custom_path_get = isset($_GET['path']) ? stripslashes_deep($_GET['path']) : '';
	$custom_path_get = sanitize_text_field($custom_path_get);
	$base_url = SOFTACULOUS_PRO_PLUGIN_URL . '/ai-chat.php?';

	global $user, $globals, $l, $theme, $softpanel, $error, $insid, $software, $soft;
	global $edited, $settings, $iscripts, $catwise, $scripts, $noheader;
	global $custom_path;

	$softpanel = new \stdClass();
	$softpanel->user = array(
		'name' => $current_user,
		'homedir' => $home_dir,
	);

	$user = array(
		'username' => $current_user,
		'ins' => array(),
	);
	
	$wp_version = $softaculous_auth->get_wp_version();

	$globals = array(
		'charset' => 'UTF-8',
		'index' => $base_url,
		'ind' => $base_url,
		'lictype' => 'plugin',
		'licexpired' => false,
	);

	$iscripts = array(
		'wp' => array(
			'name' => 'WordPress',
			'ver' => $wp_version,
		),
	);

	$software = array('name' => 'WordPress');
	$soft = 'wp';
	$insid = '';
	$custom_path = $custom_path_get;
	$l = array();
	$error = '';
	$edited = '';
	$settings = '';
	$catwise = '';
	$scripts = '';
	$noheader = '';
	$theme = array();
}


// ===== 7. NONCE VERIFICATION =====

function verify_nonce(){
	global $softaculous_auth, $auth_result;

	$csrf_token = isset($_GET['csrf_token']) ? $_GET['csrf_token'] : (isset($_POST['csrf_token']) ? $_POST['csrf_token'] : '');
	$result = $softaculous_auth->verify_nonce($csrf_token, SOFTACULOUS_PRO_AI_NONCE, $auth_result['user_id'], $auth_result['token']);
	if(!$result){
		send_json_error(array('error' => 'Security check failed. Please refresh the page.'), 403);
	}
}


// ===== 8. REQUEST HANDLING =====

function handle_request(){
	global $softaculous_auth, $auth_result, $globals;

	if(!has_active_license()){
		reporterror('License Required', 'An active Softaculous Pro license is required to use the AI Assistant.');
		return;
	}

	$home_dir = rtrim(ABSPATH, '/');
	$username = $auth_result['username'];

	$incoming_path = isset($_GET['path']) ? sanitize_text_field(stripslashes_deep($_GET['path'])) : '';
	$incoming_path_post = isset($_POST['path']) ? sanitize_text_field(stripslashes_deep($_POST['path'])) : '';
	if(!empty($incoming_path_post) && empty($incoming_path)){
		verify_nonce();
		$incoming_path = $incoming_path_post;
	}

	$project_id = isset($_GET['project_id']) ? sanitize_text_field(stripslashes_deep($_GET['project_id'])) : (isset($_POST['project_id']) ? sanitize_text_field(stripslashes_deep($_POST['project_id'])) : '');
	if(!empty($project_id)){
		ai_php_init_classes();

		require_once(SOFTACULOUS_PRO_AI_DIR . '/core/class_project.php');

		$_proj = AIProject::load($username, $project_id);
		if(empty($_proj) || empty($_proj['path'])){
			reporterror('Invalid Project', 'Project not found.');
			return;
		}

		$proj_path = $_proj['path'];
		if(strpos($proj_path, '/') !== 0){
			$proj_path = $home_dir . '/' . ltrim($proj_path, '/');
		}

		$resolved_proj = validate_path_within_home($proj_path, $home_dir);
		if($resolved_proj === false){
			reporterror('Invalid Project', 'Project path is outside your home directory.');
			return;
		}
		$incoming_path = $proj_path;
	}

	if(empty($incoming_path) && empty($project_id)){
		$_GET['path'] = $home_dir;
	}

	$api_action = isset($_GET['ai_php_api']) ? sanitize_text_field(stripslashes_deep($_GET['ai_php_api'])) : '';
	$stream = isset($_GET['ai_chat_stream']) ? sanitize_text_field(stripslashes_deep($_GET['ai_chat_stream'])) : '';

	if(!empty($stream)){
		verify_nonce();
		bootstrap();
		while(ob_get_level()){
			ob_end_clean();
		}
		include_once(SOFTACULOUS_PRO_AI_DIR . '/ai.php');
		ai();
		return;
	}

	if(!empty($api_action)){
		verify_nonce();
		bootstrap();
		include_once(SOFTACULOUS_PRO_AI_DIR . '/ai.php');
		ai();
		return;
	}

	bootstrap();
	include_once(SOFTACULOUS_PRO_AI_DIR . '/ai.php');
	ai();

	include_once(SOFTACULOUS_PRO_AI_DIR . '/theme/ai_theme.php');
	ai_theme();
}

function is_safe_file($file_path, $base_dir = ''){
	if(empty($base_dir)){
		$base_dir = rtrim(ABSPATH, '/');
	}

	if(empty($base_dir) || !is_dir($base_dir)) {
		return false;
	}

	$real_base = realpath($base_dir);
	
	if($real_base === false) return false;

	$real_file = realpath(dirname($file_path));
	if($real_file === false) {			
		return false;
	}

	return strpos($real_file, $real_base . '/') === 0 || $real_file === $real_base;
}


// ===== 9. ENTRY POINT =====

handle_request();