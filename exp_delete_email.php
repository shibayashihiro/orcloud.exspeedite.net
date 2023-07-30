<?php 

// $Id: exp_delete_email.php 4697 2022-03-09 23:02:23Z duncan $
// Delete Email - resend a previously sent email.

// Set flag that this is a parent file
define( '_STS_INCLUDE', 1 );

// Setup Session
require_once( "include/sts_session_setup.php" );

require_once( "include/sts_config.php" );
$sts_debug = isset($_GET['debug']) && in_group( EXT_GROUP_DEBUG );
require_once( "include/sts_session_class.php" );

$my_session = sts_session::getInstance( $exspeedite_db, $sts_debug );

$my_session->access_check( EXT_GROUP_ADMIN );	// Make sure we should be here

$back = basename($_SERVER["HTTP_REFERER"]);

if( isset($_GET['CODE']) ) {
	require_once( "include/sts_email_class.php" );

	$queue = sts_email_queue::getInstance($exspeedite_db, $sts_debug);
	
	$queue->delete( $_GET['CODE'], 'permdel' );
}

if( ! $sts_debug )
	reload_page ( $back );
?>