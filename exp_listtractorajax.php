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
$my_session->access_check( $sts_table_access[TRACTOR_TABLE], EXT_GROUP_MECHANIC );	// Make sure we should be here

require_once( "include/sts_result_class.php" );
require_once( "include/sts_tractor_class.php" );
require_once( "include/sts_setting_class.php" );

$setting_table = sts_setting::getInstance($exspeedite_db, $sts_debug);
$sts_length_menu = $setting_table->get( 'option', 'LENGTH_MENU' );
$multi_company = ($setting_table->get("option", "MULTI_COMPANY") == 'true');
$ifta_enabled = ($setting_table->get("api", "IFTA_ENABLED") == 'true');
$peoplenet_enabled = ($setting_table->get("option", "PEOPLENET_ENABLED") == 'true');
$elogs_enabled = ($setting_table->get("option", "ELOGS_ENABLED") == 'true');
$sts_inspection_reports = $setting_table->get( 'option', 'INSPECTION_REPORTS' ) == 'true';
$insp_title = $setting_table->get( 'option', 'INSPECTION_REPORT_TITLE' );
$sts_expire_tractors = $setting_table->get( 'option', 'EXPIRE_TRACTORS_ENABLED' ) == 'true';

//! SCR# 372 - disable shading of expired tractors
if( ! $sts_expire_tractors ) {
	$sts_result_tractors_layout['EXPIRED']['snippet'] = "'green'";
}

if( $sts_debug ) {
		echo "<p>GET = </p><pre>";
		var_dump($_GET);
		echo "</pre>";
}

if( ! $multi_company ) {
	unset($sts_result_tractors_layout["COMPANY_CODE"],
		$sts_result_tractors_layout["OFFICE_CODE"]);
}

if( ! $ifta_enabled ) { //! Remove IFTA dropdown
	$rb = array();
	foreach( $sts_result_tractors_edit["rowbuttons"] as $b ) {
		if( $b["url"] <> "exp_editifta_log.php?TRACTOR_CODE=")
			$rb[] = $b;
	}
	unset($sts_result_tractors_edit["rowbuttons"]);
	$sts_result_tractors_edit["rowbuttons"] = $rb;
	unset($sts_result_tractors_layout["LOG_IFTA"],
		$sts_result_tractors_layout["LOG_IFTA"]);
}

if( $peoplenet_enabled ) {
	$sts_result_tractors_edit["rowbuttons"][] =
		array( 'url' => 'exp_peoplenet.php?CODE=', 'key' => 'TRACTOR_CODE', 'label' => 'UNIT_NUMBER', 'tip' => 'Peoplenet with ', 'icon' => 'glyphicon glyphicon-transfer', 'showif' => 'notdeleted' );

}

if( $elogs_enabled ) {
	$sts_result_tractors_edit["rowbuttons"][] =
		array( 'url' => 'exp_elogs.php?CODE=', 'key' => 'TRACTOR_CODE', 'label' => 'UNIT_NUMBER', 'tip' => 'View E-logs for ', 'icon' => 'glyphicon glyphicon-list-alt', 'showif' => 'notdeleted' );

}

if( $sts_inspection_reports ) {
	$sts_result_tractors_edit["rowbuttons"][] =
		array( 'url' => 'exp_email_3month_insp_report.php?UNIT_TYPE=tractor&CODE=', 'key' => 'TRACTOR_CODE', 'label' => 'UNIT_NUMBER', 'tip' => 'Email 3 months '.$insp_title.'s for ', 'icon' => 'glyphicon glyphicon-envelope', 'showif' => 'notdeleted' );

}

$tractor_table = sts_tractor::getInstance($exspeedite_db, $sts_debug);

$match = isset($_GET["match"]) && $_GET["match"] <> '' ? urldecode($_GET["match"]) : false;

$rslt = new sts_result( $tractor_table, $match, $sts_debug );

$response =  $rslt->render_ajax( $sts_result_tractors_layout, $sts_result_tractors_edit, $_GET );

if( $sts_debug ) {
		echo "<p>response = </p><pre>";
		var_dump($response);
		echo "</pre>";
} else {
	echo json_encode( $response );
}
?>

