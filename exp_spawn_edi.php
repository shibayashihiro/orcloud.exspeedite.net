<?php
	
// This is used to spawn a background PHP Script to check for EDI 204s
// Currently it is called from home screen and list shipments screen
// To use, just do this:
// require_once( "exp_spawn_edi.php" );

// no direct access
defined('_STS_INCLUDE') or die('Restricted access');

require_once('include/sts_config.php');
require_once('include/sts_setting_class.php');
global $exspeedite_db, $sts_debug, $sts_error_level_label;

$sts_debug = isset($_GET['debug']) && in_group( EXT_GROUP_DEBUG );
set_time_limit(0);

$setting_table = sts_setting::getInstance($exspeedite_db, $sts_debug);
$edi_enabled = ($setting_table->get("api", "EDI_ENABLED") == 'true');
$sts_php_exe = $setting_table->get( 'option', 'PHP_EXE' );

if( $edi_enabled ) {
	$last_checked = intval($setting_table->get("api", "EDI_LAST_CHECKED"));
	$freq = intval($setting_table->get("api", "EDI_POLLING"))*60;
	$now = time();
	
	// We only spawn a process if it has been more than the interval since last time.
	// You can override this if you set $sts_override_timer
	if( isset($sts_override_timer) || ($now > $last_checked + $freq) ) {
		$setting_table->set("api", "EDI_LAST_CHECKED", $now);
	
		$php_arr = explode(DIRECTORY_SEPARATOR, $sts_php_exe);
		array_pop($php_arr);
		$php_dir = implode(DIRECTORY_SEPARATOR,$php_arr);
		$script_to_run = $sts_crm_dir.'exp_get_edi.php';
		
		$background_cmd = "$sts_php_exe -c $php_dir ".$script_to_run;
		
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
}

?>