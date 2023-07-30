<?php
	
// This is used to spawn a background PHP Script

// no direct access
defined('_STS_INCLUDE') or die('Restricted access');

require_once(dirname(__FILE__) .'/../../include/sts_config.php');
require_once( "include/sts_setting_class.php" );
global $exspeedite_db, $sts_debug, $sts_error_level_label;

$sts_debug = isset($sts_debug) ? $sts_debug : false; // make sure initialized

$setting_table = sts_setting::getInstance($exspeedite_db, $sts_debug);
$sts_qb_log_file = $setting_table->get( 'api', 'QUICKBOOKS_LOG_FILE' );
$sts_php_exe = $setting_table->get( 'option', 'PHP_EXE' );
$sts_diag_level = $setting_table->get( 'api', 'QUICKBOOKS_DIAG_LEVEL' );
$sts_diag_level =  array_search(strtolower($sts_diag_level), $sts_error_level_label);
if( $sts_diag_level === false ) $sts_diag_level = EXT_ERROR_ALL;

	function spawn_log_event( $message, $level = EXT_ERROR_ERROR ) {
		global $sts_qb_log_file, $sts_diag_level;
		
		echo "</pre>";

		if( $sts_diag_level >= $level ) {
			if( (file_exists($sts_qb_log_file) && is_writable($sts_qb_log_file)) ||
				is_writable(dirname($sts_qb_log_file)) ) 
				file_put_contents($sts_qb_log_file, date('m/d/Y h:i:s A')." pid=".getmypid().
					" msg=".$message."\n\n", FILE_APPEND);
		}
	}

spawn_log_event('SPAWN_PROCESS: start', EXT_ERROR_DEBUG);
$sts_debug = isset($_GET['debug']);
set_time_limit(0);

$php_arr = explode(DIRECTORY_SEPARATOR, $sts_php_exe);
array_pop($php_arr);
$php_dir = implode(DIRECTORY_SEPARATOR,$php_arr);

chdir( '../..' ); 
$script_to_run = str_replace('/', DIRECTORY_SEPARATOR, $sts_crm_dir).'quickbooks-php-master'.DIRECTORY_SEPARATOR.'online'.DIRECTORY_SEPARATOR.'process_queue.php subdomain='.$sts_subdomain;

$background_cmd = "$sts_php_exe -c $php_dir ".$script_to_run;
spawn_log_event('spawn_process: background_cmd = '.$background_cmd, EXT_ERROR_DEBUG);

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
$process = proc_open($background_cmd, $descriptorspec1, $pipes);
fclose($pipes[0]);

stream_set_blocking($pipes[2], 0);
if ($err = stream_get_contents($pipes[2])) {
	spawn_log_event("SPAWN_PROCESS: proc_open, error = \n".print_r($err, true), EXT_ERROR_DEBUG);
} else {
	spawn_log_event("SPAWN_PROCESS: proc_open, process = \n".print_r($process, true), EXT_ERROR_DEBUG);

	if( $sts_debug ) {
		echo "<pre>";
		var_dump(proc_get_status($process));
		echo "</pre>";
	}
	spawn_log_event("SPAWN_PROCESS: proc_get_status = \n".print_r(proc_get_status($process), true), EXT_ERROR_DEBUG);
	
	$sts_queue_process = proc_get_status($process)["pid"];
	
	spawn_log_event('SPAWN_PROCESS: end', EXT_ERROR_DEBUG);
}

?>