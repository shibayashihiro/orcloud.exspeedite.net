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
$my_session->access_check( EXT_GROUP_ADMIN );	// Make sure we should be here

require_once( "include/sts_report_class.php" );

$video_table = sts_video::getInstance($exspeedite_db, $sts_debug);

if( isset($_GET['CODE']) ) {
	$result = $video_table->duplicate( $_GET['CODE'] );
	if( $sts_debug ) echo "<p>result = ".($result ? 'true' : 'false '.$shipment_table->error())."</p>";
}

if( ! $sts_debug )
	reload_page ( isset($result) ? "exp_editvideo.php?CODE=".$result : "exp_listvideo.php" );	// Back to list videos page