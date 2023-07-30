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
$my_session->access_check( $sts_table_access[PALLET_TABLE] );	// Make sure we should be here

require_once( "include/sts_pallet_class.php" );

$pallet_table = new sts_pallet($exspeedite_db, $sts_debug);

if( isset($_GET['CODE']) ) {
	$result = $pallet_table->delete( $_GET['CODE'], 'permdel' );
	if( $sts_debug ) echo "<p>result = ".($result ? 'true' : 'false '.$pallet_table->error())."</p>";
}

if( ! $sts_debug )
	reload_page ( "exp_listpallet.php" );	// Back to list pallets page