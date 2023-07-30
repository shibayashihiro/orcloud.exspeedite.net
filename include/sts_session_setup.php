<?php

// no direct access
defined('_STS_INCLUDE') or die('Restricted access');
//error_reporting(E_ALL);

//! Centralize all session setup. If something is broken, you can fix it here.
//! Look in the bottom of header_inc.php - there is code to log out after 

// 5/27/2020 Totally disable garbae collection
ini_set('zend.enable_gc', 0);
set_time_limit(300);
ini_set('memory_limit', '1024M');


if( php_sapi_name() == 'cli' ) {
	// Include path modifications (relative paths within library)
	ini_set('include_path', ini_get('include_path') . PATH_SEPARATOR . dirname(__FILE__) );
	parse_str(implode('&', array_slice($argv, 1)), $tmp);
	if( isset($tmp['subdomain']) ) {
		$sts_subdomain = $tmp['subdomain'];
		$sts_config_path = $sts_subdomain.DIRECTORY_SEPARATOR.'exspeedite_config.php';
		if( file_exists($sts_config_path) )	// Check it exists
			require_once($sts_config_path);
		else if( file_exists('exspeedite_config.php') )	// fallback to original location
			require_once('exspeedite_config.php');
	} else {	// no subdomain, use original location
		require_once('exspeedite_config.php');
	}
	
	session_name('EX'.$sts_database);
	
	$session_path = str_replace('\\', '/', ini_get('session.save_path')) . '/ex_'.$sts_database;
	session_save_path($session_path);

} else {
	//! Session lifetime in seconds 24 * 60 * 60
	$sts_session_lifetime = 86400;
	//! Activity timeout in seconds 4 * 60 * 60
	$sts_activity_timeout = 14400;
	
	
	$subdomain = explode('.', $_SERVER["HTTP_HOST"])[0];
	$config_dir = dirname(__FILE__);
	if( file_exists($config_dir.DIRECTORY_SEPARATOR.$subdomain ) ) {
		$config_dir = $config_dir.DIRECTORY_SEPARATOR.$subdomain;
	}
	require_once( $config_dir.DIRECTORY_SEPARATOR.'exspeedite_config.php');
	
	session_name('EX'.$sts_database);
	
	$session_path = str_replace('\\', '/', ini_get('session.save_path')) . '/ex_'.$sts_database;
	
	//! Create session directory if not exists
	$create_directory_failed = false;
	if( ! is_dir($session_path) ) {
		try {
			$result = mkdir($session_path);
		}  catch (Exception $exception) {
			// If this fails, it could be a permissions issue.
			echo '<p><strong>Exspeedite:</strong> could not create session directory '.$session_path.
			'<br>error message = '.$exception->getMessage().'</p>';
			$create_directory_failed = true;
		}
	}
	if( 0 ) {	// For testing - it breaks ajax
		echo "<p><strong>Exspeedite Session Data:</strong>
			<br>session_path = $session_path
			<br>session_name = ".session_name() ."
			<br>sts_session_lifetime = $sts_session_lifetime</p>";
	}

	if( ! $create_directory_failed ) {
		session_save_path($session_path);
	}
	// See line 10 above, should not be needed.
	//ini_set('session.gc_probability', 0);		// Disable GC
	//ini_set('session.gc_maxlifetime', $sts_session_lifetime * 2);	// Twice cookie lifetime for safety
	
	session_set_cookie_params ($sts_session_lifetime);
	$lifetime = ini_get('session.cookie_lifetime');

	if( defined('_STS_SESSION_READONLY') ) {
		session_start([
			'read_and_close' => true,
		]);	
	} else {
		session_start();
		
		// Try to ensure we save the session when we shutdown
		session_register_shutdown();
	}

	if ( ! defined('_STS_SESSION_AJAX') ) {	// Don't reject ajax calls
		if( isset($_SESSION['LAST_ACTIVITY']) &&
			(time() - $_SESSION['LAST_ACTIVITY'] > $sts_activity_timeout)) {
		    // last request was more than 30 minutes ago
		   date_default_timezone_set(isset($sts_crm_timezone) ? $sts_crm_timezone : 'America/Chicago');

		    $formatted = date('Y-m-d H:i:s', $_SESSION['LAST_ACTIVITY']);
			
			require_once( "include/sts_config.php" );
			reload_page( "exp_logout.php?EXPIRED=".urlencode($formatted) );
			die;
		}
		$_SESSION['LAST_ACTIVITY'] = time(); // update last activity time stamp
	}
}

?>