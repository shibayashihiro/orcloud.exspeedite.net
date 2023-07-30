<?php 

// $Id: exp_listinsp_reportajax.php 4350 2021-03-02 19:14:52Z duncan $
// list inspection reports AJAX back end

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
$my_session->access_check( EXT_GROUP_MECHANIC, EXT_GROUP_ADMIN );	// Make sure we should be here

require_once( "include/sts_result_class.php" );
require_once( "include/sts_insp_report_class.php" );

if( $sts_debug ) {
		echo "<p>GET = </p><pre>";
		var_dump($_GET);
		echo "</pre>";
}

$report_table = sts_insp_report::getInstance($exspeedite_db, $sts_debug);

$match = isset($_GET["match"]) && $_GET["match"] <> '' ? urldecode($_GET["match"]) : false;
if( isset($_GET["sort"]) && $_GET["sort"] <> '' )
	$sts_result_insp_report_edit['sort'] = urldecode($_GET["sort"]);

$rslt = new sts_result( $report_table, $match, $sts_debug );
$response =  $rslt->render_ajax( $sts_result_insp_report_all_layout, $sts_result_insp_report_edit, $_GET );

if( $sts_debug ) {
		echo "<p>response = </p><pre>";
		var_dump($response);
		echo "</pre>";
} else {
	echo json_encode( $response );
}
?>

