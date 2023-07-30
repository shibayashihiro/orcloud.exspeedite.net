<?php 
//! Part of the select shipments for a load page
// Called via ajax to load the left column with suitable shipments

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
$my_session->access_check( EXT_GROUP_USER );	// Make sure we should be here

require_once( "include/sts_carrier_class.php" );
require_once( "include/sts_driver_class.php" );
require_once( "include/sts_tractor_class.php" );
require_once( "include/sts_trailer_class.php" );

if( isset($_GET['code']) && $_GET['code'] == 'Headphone') {

	
	if( isset($_GET['carrier']) ) {
		$carrier_table = sts_carrier::getInstance($exspeedite_db, $sts_debug);
		
		echo $carrier_table->check_expired( $_GET['carrier'] );
	} else
	if( isset($_GET['driver']) ) {
		$driver_table = sts_driver::getInstance($exspeedite_db, $sts_debug);
		
		echo $driver_table->check_expired( $_GET['driver'] );
	} else
	if( isset($_GET['tractor']) ) {
		$tractor_table = sts_tractor::getInstance($exspeedite_db, $sts_debug);
		
		echo $tractor_table->check_expired( $_GET['tractor'] );
	} else
	if( isset($_GET['trailer']) ) {
		$trailer_table = sts_trailer::getInstance($exspeedite_db, $sts_debug);
		
		echo $trailer_table->check_expired( $_GET['trailer'] );
	}
}

?>