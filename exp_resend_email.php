<?php 

// $Id: exp_resend_email.php 5030 2023-04-12 20:31:34Z duncan $
// Resend Email - resend a previously sent email.

// Set flag that this is a parent file
define( '_STS_INCLUDE', 1 );

// Setup Session
require_once( "include/sts_session_setup.php" );

require_once( "include/sts_config.php" );
$sts_debug = isset($_GET['debug']) && in_group( EXT_GROUP_DEBUG );
require_once( "include/sts_session_class.php" );

$my_session = sts_session::getInstance( $exspeedite_db, $sts_debug );

$my_session->access_check( EXT_GROUP_ADMIN );	// Make sure we should be here

if( ! $sts_debug )
	$back = basename($_SERVER["HTTP_REFERER"]);

if( isset($_GET['CODE']) ) {

	$email_type = 'resend';
	$email_code = $_GET['CODE'];
	require_once( "exp_spawn_send_email.php" );
} else if( isset($_GET['ALLERRORS'])) { //! SCR# 825 - Resend all queued messages
	$email_type = 'resendall';
	$email_code = 0;
	require_once( "exp_spawn_send_email.php" );
}

if( ! $sts_debug )
	reload_page ( $back );
?>