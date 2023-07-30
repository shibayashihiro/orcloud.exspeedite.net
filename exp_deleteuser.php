<?php 
// Set flag that this is a parent file
define( '_STS_INCLUDE', 1 );

// Set flag that this is session readonly
define( '_STS_SESSION_READONLY', 1 );

// Set flag that this is an ajax call
define( '_STS_SESSION_AJAX', 1 );

// Setup Session
require_once( "include/sts_session_setup.php" );

require_once( "include/sts_config.php" );
$sts_debug = isset($_GET['debug']) && in_group( EXT_GROUP_DEBUG );
require_once( "include/sts_session_class.php" );

$my_session = sts_session::getInstance( $exspeedite_db, $sts_debug );
$my_session->access_check( $sts_table_access[USER_TABLE] );	// Make sure we should be here

require_once( "include/sts_user_class.php" );
require_once( "include/sts_office_class.php" );
require_once( "include/sts_report_class.php" );
require_once( "include/sts_user_log_class.php" );

$user_table = new sts_user($exspeedite_db, $sts_debug);
$uo_table = sts_user_office::getInstance($exspeedite_db, $sts_debug);
$ur_table = sts_user_report::getInstance($exspeedite_db, $sts_debug);

if( isset($_GET['CODE']) ) {
	$check = $user_table->fetch_rows("USER_CODE = ".$_GET['CODE'], "USERNAME");
	
	$result = $user_table->delete( $_GET['CODE'], 'permdel' );
	if( $sts_debug ) echo "<p>result = ".($result ? 'true' : 'false '.$user_table->error())."</p>";
	$result2 = $uo_table->delete_row("USER_CODE = ".$_GET['CODE']);
	if( $sts_debug ) echo "<p>result2 = ".($result2 ? 'true' : 'false '.$uo_table->error())."</p>";
	$result23 = $ur_table->delete_row("USER_CODE = ".$_GET['CODE']);
	if( $sts_debug ) echo "<p>result3 = ".($result3 ? 'true' : 'false '.$ur_table->error())."</p>";
	
	//! SCR# 185 - log when we delete a user
	if( is_array($check) && count($check) == 1 && ! empty($check[0]["USERNAME"])) {
		$user_log_table = sts_user_log::getInstance($exspeedite_db, $sts_debug);
		$user_log_table->log_event('admin', 'Delete user '.$check[0]["USERNAME"]);
	}
}

if( ! $sts_debug )
	reload_page ( "exp_listuser.php" );	// Back to list users page