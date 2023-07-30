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
$my_session->access_check( $sts_table_access[COMMODITY_TABLE] );	// Make sure we should be here

require_once( "include/sts_result_class.php" );
require_once( "include/sts_commodity_class.php" );

$commodity_table = sts_commodity::getInstance($exspeedite_db, $sts_debug);

$match = isset($_GET["match"]) && $_GET["match"] <> '' ? urldecode($_GET["match"]) : false;

$rslt = new sts_result( $commodity_table, $match, $sts_debug );

$response =  $rslt->render_ajax( $sts_result_commoditys_layout, $sts_result_commoditys_edit, $_GET );

if( $sts_debug ) {
		echo "<p>response = </p><pre>";
		var_dump($response);
		echo "</pre>";
} else {
	echo json_encode( $response );
}
?>

