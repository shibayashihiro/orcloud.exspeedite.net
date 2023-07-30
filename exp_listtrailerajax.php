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
set_time_limit(0);
ini_set('memory_limit', '1024M');

$sts_debug = isset($_GET['debug']) && in_group( EXT_GROUP_DEBUG );
require_once( "include/sts_session_class.php" );

$my_session = sts_session::getInstance( $exspeedite_db, $sts_debug );
$my_session->access_check( $sts_table_access[TRAILER_TABLE], EXT_GROUP_MECHANIC );	// Make sure we should be here

require_once( "include/sts_result_class.php" );
require_once( "include/sts_trailer_class.php" );
require_once( "include/sts_setting_class.php" );

$setting_table = sts_setting::getInstance($exspeedite_db, $sts_debug);
$sts_inspection_reports = $setting_table->get( 'option', 'INSPECTION_REPORTS' ) == 'true';
$insp_title = $setting_table->get( 'option', 'INSPECTION_REPORT_TITLE' );
$sts_expire_trailers = $setting_table->get( 'option', 'EXPIRE_TRAILERS_ENABLED' ) == 'true';

//! SCR# 372 - disable shading of expired trailers
if( ! $sts_expire_trailers ) {
	$sts_result_trailers_layout['EXPIRED']['snippet'] = "'green'";
}

if( $sts_inspection_reports ) {
	$sts_result_trailers_edit["rowbuttons"][] =
		array( 'url' => 'exp_email_3month_insp_report.php?UNIT_TYPE=trailer&CODE=', 'key' => 'TRAILER_CODE', 'label' => 'UNIT_NUMBER', 'tip' => 'Email 3 months '.$insp_title.'s for ', 'icon' => 'glyphicon glyphicon-envelope', 'showif' => 'notdeleted' );

}

$trailer_table = sts_trailer::getInstance($exspeedite_db, $sts_debug);

$match = isset($_GET["match"]) && $_GET["match"] <> '' ? urldecode($_GET["match"]) : false;

$rslt = new sts_result( $trailer_table, $match, $sts_debug );

$response =  $rslt->render_ajax( $sts_result_trailers_layout, $sts_result_trailers_edit, $_GET );

if( $sts_debug ) {
		echo "<p>response = </p><pre>";
		var_dump($response);
		echo "</pre>";
} else {
	echo json_encode( $response );
}
?>

