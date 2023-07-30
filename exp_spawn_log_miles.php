<?php
	
// This is used to spawn a background PHP Script to log IFTA miles based on PC*Miler
// Currently it is called when a load changes state to Completed
// To use, just do this:
// $ifta_load_code = <LOAD_CODE>
// require_once( "exp_spawn_log_miles.php" );

// no direct access
defined('_STS_INCLUDE') or die('Restricted access');

require_once('include/sts_config.php');
require_once('include/sts_setting_class.php');
global $exspeedite_db, $sts_debug, $sts_error_level_label;

$sts_debug = isset($_GET['debug']);
set_time_limit(0);

$setting_table = sts_setting::getInstance($exspeedite_db, $sts_debug);
$log_miles = ($setting_table->get("api", "PCM_LOG_IFTA_MILES") == 'true');
$sts_php_exe = $setting_table->get( 'option', 'PHP_EXE' );

if( $log_miles && isset($ifta_load_code) ) {
	
	$php_arr = explode(DIRECTORY_SEPARATOR, $sts_php_exe);
	array_pop($php_arr);
	$php_dir = implode(DIRECTORY_SEPARATOR,$php_arr);
	$script_to_run = $sts_crm_dir.'exp_log_miles.php -c'.$ifta_load_code.' -l';
	
	$background_cmd = "$sts_php_exe -c $php_dir ".$script_to_run;
	//var_dump($php_arr, $php_dir, $script_to_run, $background_cmd);
	//die;
	
	$descriptorspec1 = array(
	    0 => array("pipe", "r"),
	    1 => array("pipe", "w"),
	    2 => array("pipe", "w")
	);
	
	$descriptorspec2 = array(
	    0 => array("pipe", "r"),
	    1 => array("file", "log1.txt", "w"),
	    2 => array("file", "log1.txt", "w")
	);
	
	if( $sts_debug ) echo "<p>Trying $background_cmd</p>";
	chdir(dirname(__FILE__));
	$process = proc_open($background_cmd, $descriptorspec2, $pipes);
	fclose($pipes[0]);
	
	if( $sts_debug ) {
		echo "<pre>";
		var_dump(proc_get_status($process));
		echo "</pre>";
	}
	
	$sts_queue_process = proc_get_status($process)["pid"];
}

?>