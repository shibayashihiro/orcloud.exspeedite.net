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
$my_session->access_check( $sts_table_access[STOP_TABLE] );	// Make sure we should be here

require_once( "include/sts_stop_class.php" );
require_once( "include/sts_load_class.php" );

if( $sts_debug ) echo "<p>del_return_stop</p>";

if( isset($_GET['load']) && isset($_GET['stop']) ) {
	$stop_table = sts_stop::getInstance($exspeedite_db, $sts_debug);
	$load_table = sts_load::getInstance($exspeedite_db, $sts_debug);
	
	$stop_table->delete( $_GET['stop'] );		// Remove stop
	$stop_table->renumber( $_GET['load'] );		// Renumber remaining stops

	if( ! $sts_debug )
		reload_page ( 'exp_dispatch2.php?CODE='.$_GET['load'] );
}


