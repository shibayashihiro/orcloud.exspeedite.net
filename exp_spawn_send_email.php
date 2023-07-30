<?php
	
// This is used to spawn a background PHP Script to send email
// It seems to take a long time to send an email
// To use, just do this:
// $email_type = 'activity';
// $email_code = 99;
// require_once( "exp_spawn_send_email.php" );

//! SCR# 712 - resend an email
// Do this:
// $email_type = 'resend';
// $email_code = 99;
// require_once( "exp_spawn_send_email.php" );

// no direct access
defined('_STS_INCLUDE') or die('Restricted access');

require_once('include/sts_config.php');
require_once('include/sts_setting_class.php');
require_once( "include/sts_email_class.php" );

global $exspeedite_db, $sts_debug, $sts_error_level_label, $sts_crm_dir, $sts_subdomain;

$sts_debug = isset($_GET['debug']);
set_time_limit(0);

$email = sts_email::getInstance($exspeedite_db, $sts_debug);
$email->log_email_error( "exp_spawn_send_email.php: $email_type $email_code" );

$setting_table = sts_setting::getInstance($exspeedite_db, $sts_debug);
$sts_php_exe = $setting_table->get( 'option', 'PHP_EXE' );

if( $email_type && isset($email_code) ) {
	
	if( strpos(php_uname(), 'Linux') !== false ) {
		$php_dir = '';
	} else {
		$php_arr = explode(DIRECTORY_SEPARATOR, $sts_php_exe);
		array_pop($php_arr);
		$php_dir = '-c '.implode(DIRECTORY_SEPARATOR,$php_arr);
	}
	$script_to_run = $sts_crm_dir.'exp_send_email.php subdomain='.$sts_subdomain.' pw=Reimer ';
	if( $email_type == 'resend' )
		$script_to_run .= 'resend='.$email_code;
	else
		$script_to_run .= 'type='.$email_type.' code='.$email_code;
	
	$background_cmd = "$sts_php_exe $php_dir -f ".$script_to_run;
	
	$email->log_email_error( "exp_spawn_send_email.php: $background_cmd" );

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
	
	if( $sts_debug ) echo "<p>SPAWN $background_cmd</p>";
		
	chdir(dirname(__FILE__));
	$process = proc_open($background_cmd, $descriptorspec2, $pipes);
	if( $sts_debug ) {
		echo "<pre>proc_open\n";
		var_dump($process);
		echo "</pre>";
	}
	fclose($pipes[0]);
	
	if( $sts_debug ) {
		echo "<pre>";
		var_dump(proc_get_status($process));
		echo "</pre>";
	}
	
	//$pstatus = proc_get_status($process);
	//$sts_queue_process = $pstatus["pid"];
	//$sstatus = "running=".($pstatus["running"] ? "true" : "false").
	//	" stopped=".($pstatus["stopped"] ? "true" : "false");
	//$email->log_email_error( "exp_spawn_send_email.php: SPAWNED $sts_queue_process $sstatus" );

}

?>