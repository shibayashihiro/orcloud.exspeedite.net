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

if( isset($_GET['load']) && isset($_GET['carrier']) &&
	isset($_GET['code']) && $_GET['code'] == 'Watch') {

	$carrier_table = sts_carrier::getInstance($exspeedite_db, $sts_debug);
	
	echo $carrier_table->check_suitable( $_GET['load'], $_GET['carrier'] );
}

?>