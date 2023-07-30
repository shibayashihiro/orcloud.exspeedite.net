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
$my_session->access_check( EXT_GROUP_MECHANIC, EXT_GROUP_ADMIN );	// Make sure we should be here

require_once( "include/sts_insp_report_class.php" );
require_once( "include/sts_alert_class.php" );

if( isset($_SERVER["HTTP_REFERER"]) ) {
	$path = explode('/', $_SERVER["HTTP_REFERER"]); 
	$referer = end($path);
} else
	$referer = 'index.php';

$report_table = sts_insp_report::getInstance($exspeedite_db, $sts_debug);
$alert_table = sts_alert::getInstance($exspeedite_db, $sts_debug);

if( isset($_GET['REPORT']) ) {
	$result = $report_table->delete( $_GET['REPORT'] );

	//! SCR# 700  - clear the cache of alerts
	$alert_table->clear_cache();

	if( $sts_debug ) echo "<p>result = ".($result ? 'true' : 'false '.$report_table->error())."</p>";
}

if( ! $sts_debug )
	reload_page ( $referer );	// Back to previous page