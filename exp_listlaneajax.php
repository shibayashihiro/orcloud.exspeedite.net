<?php 

// $Id: exp_listlaneajax.php 3435 2019-03-25 18:53:25Z duncan $
//! SCR# 514 - list lanes - ajax component

// Set flag that this is a parent file
define( '_STS_INCLUDE', 1 );

set_time_limit(0);

// Setup Session
require_once( "include/sts_session_setup.php" );

require_once( "include/sts_config.php" );
$sts_debug = isset($_GET['debug']) && in_group( EXT_GROUP_DEBUG );
require_once( "include/sts_session_class.php" );

$my_session = sts_session::getInstance( $exspeedite_db, $sts_debug );
$my_session->access_report( 'Lane Report' );	// Make sure we should be here

require_once( "include/sts_result_class.php" );
require_once( "include/sts_lane_class.php" );

if( $sts_debug ) {
		echo "<p>INPUT = </p><pre>";
		var_dump($_GET);
		echo "</pre>";
}

$lane_table = sts_lane::getInstance($exspeedite_db, $sts_debug);

// close the session here to avoid blocking
session_write_close();

$match = isset($_GET["match"]) && $_GET["match"] <> '' ? urldecode($_GET["match"]) : false;
if( isset($_GET["sort"]) && $_GET["sort"] <> '' )
	$sts_result_lane_edit['sort'] = urldecode($_GET["sort"]);

$rslt = new sts_result( $lane_table, $match, $sts_debug );

$response =  $rslt->render_ajax( $sts_result_lane_layout, $sts_result_lane_edit, $_GET );

if( $sts_debug ) {
		echo "<p>response = </p><pre>";
		// Issue with encoding of text fields from the database, likely a conversion issue.
		var_dump($response, json_encode($response), json_last_error_msg());
		echo "</pre>";
} else {
	echo json_encode( $response );
}
?>

