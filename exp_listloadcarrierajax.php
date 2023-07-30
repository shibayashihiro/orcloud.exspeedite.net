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
$my_session->access_check( $sts_table_access[LOAD_TABLE] );	// Make sure we should be here

require_once( "include/sts_result_class.php" );
require_once( "include/sts_load_class.php" );
require_once( "include/sts_setting_class.php" );

$setting_table = sts_setting::getInstance($exspeedite_db, $sts_debug);
$sts_po_fields = $setting_table->get( 'option', 'PO_FIELDS' ) == 'true';

if( $sts_debug ) {
		echo "<p>INPUT = </p><pre>";
		var_dump($_GET);
		echo "</pre>";
}

$load_table = sts_load::getInstance($exspeedite_db, $sts_debug);

$match = isset($_GET["match"]) && $_GET["match"] <> '' ? urldecode($_GET["match"]) : false;
if( isset($_GET["sort"]) && $_GET["sort"] <> '' )
	$sts_result_loads_edit['sort'] = urldecode($_GET["sort"]);

$rslt = new sts_result( $load_table, $match, $sts_debug );

$response =  $rslt->render_ajax( (strpos($match, 'DRIVER') !== false ?
	$sts_result_loads_driver2_layout :
		(strpos($match, 'LUMPER') !== false ? $sts_result_loads_lumper_layout :$sts_result_loads_carrier_layout) ),
	$sts_result_loads_carrier_view, $_GET );

if( $sts_debug ) {
		echo "<p>response = </p><pre>";
		var_dump($response);
		echo "</pre>";
} else {
	echo json_encode( $response );
}
?>

