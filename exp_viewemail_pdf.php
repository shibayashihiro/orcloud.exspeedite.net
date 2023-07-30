<?php 
// Set flag that this is a parent file
define( '_STS_INCLUDE', 1 );

// Setup Session
require_once( "include/sts_session_setup.php" );

require_once( "include/sts_config.php" );
$sts_debug = isset($_GET['debug']) && in_group( EXT_GROUP_DEBUG );
require_once( "include/sts_session_class.php" );

$my_session = sts_session::getInstance( $exspeedite_db, $sts_debug );

$table = new sts_table( DUMMY_DATABASE, "", $sts_debug );

if( isset($_GET['CODE']) ) {
	require_once( "include/sts_email_class.php" );

	$email = sts_email::getInstance($exspeedite_db, $sts_debug);
	
	$email->view_queued_pdf( $_GET['CODE'] );
}
?>