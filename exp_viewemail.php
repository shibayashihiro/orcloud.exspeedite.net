<?php 

// $Id: exp_viewemail.php 4697 2022-03-09 23:02:23Z duncan $
// View Email - display a previously sent email.

// Set flag that this is a parent file
define( '_STS_INCLUDE', 1 );

// Setup Session
require_once( "include/sts_session_setup.php" );

require_once( "include/sts_config.php" );
$sts_debug = isset($_GET['debug']) && in_group( EXT_GROUP_DEBUG );
require_once( "include/sts_session_class.php" );

$my_session = sts_session::getInstance( $exspeedite_db, $sts_debug );

$table = new sts_table( DUMMY_DATABASE, "", $sts_debug );

$my_session->access_check( EXT_GROUP_ADMIN );	// Make sure we should be here

if( isset($_GET['CODE']) ) {
	$sts_subtitle = "View Email";
	require_once( "include/header_inc.php" );
	echo '<div class="container" role="main">
	';

	require_once( "include/sts_email_class.php" );

	$email = sts_email::getInstance($exspeedite_db, $sts_debug);
	
	$email->view_queued( $_GET['CODE'] );
}
?>