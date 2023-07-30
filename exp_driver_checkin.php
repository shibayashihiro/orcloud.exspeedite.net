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
$sts_debug = isset($_GET['debug']); // && in_group( EXT_GROUP_DEBUG );
require_once( "include/sts_session_class.php" );

$my_session = sts_session::getInstance( $exspeedite_db, $sts_debug );
$my_session->access_check( $sts_table_access[DRIVER_TABLE], EXT_GROUP_DRIVER );	// Make sure we should be here

require_once( "include/sts_driver_class.php" );

$driver_table = sts_driver::getInstance($exspeedite_db, $sts_debug);

if( isset($_GET['DRIVER']) && isset($_GET['LAT']) && isset($_GET['LON']) &&
	isset($_GET['PW']) && $_GET['PW'] == 'Mikos' ) {
	$result = $driver_table->checkin( $_GET['DRIVER'], $_GET['LAT'], $_GET['LON'] );
}

?>