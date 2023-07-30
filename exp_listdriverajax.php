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
$my_session->access_check( $sts_table_access[DRIVER_TABLE] );	// Make sure we should be here

require_once( "include/sts_result_class.php" );
require_once( "include/sts_driver_class.php" );
require_once( "include/sts_setting_class.php" );

$setting_table = sts_setting::getInstance($exspeedite_db, $sts_debug);
$sts_expire_drivers = $setting_table->get( 'option', 'EXPIRE_DRIVERS_ENABLED' ) == 'true';

//! SCR# 372 - disable shading of expired drivers
if( ! $sts_expire_drivers ) {
	$sts_result_drivers_layout['EXPIRED']['snippet'] = "'green'";
}

//! Check for multi-company
$multi_company = ($setting_table->get("option", "MULTI_COMPANY") == 'true');
if( ! $multi_company ) {
	unset($sts_result_drivers_layout["COMPANY_CODE"],
		$sts_result_drivers_layout["OFFICE_CODE"]);
}

if( $sts_debug ) {
		echo "<p>GET = </p><pre>";
		var_dump($_GET);
		echo "</pre>";
}

$driver_table = new sts_driver_lj($exspeedite_db, $sts_debug);

$match = isset($_GET["match"]) && $_GET["match"] <> '' ? urldecode($_GET["match"]) : false;

$rslt = new sts_result( $driver_table, $match, $sts_debug );

$response =  $rslt->render_ajax( $sts_result_drivers_layout, $sts_result_drivers_edit, $_GET );

if( $sts_debug ) {
		echo "<p>response = </p><pre>";
		var_dump($response);
		echo "</pre>";
} else {
	echo json_encode( $response );
}
?>

