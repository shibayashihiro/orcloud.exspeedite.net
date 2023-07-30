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
$my_session->access_check( $sts_table_access[LOAD_TABLE] );	// Make sure we should be here

require_once( "include/sts_result_class.php" );
require_once( "include/sts_load_class.php" );
require_once( "include/sts_office_class.php" );

$office_table = sts_office::getInstance($exspeedite_db, $sts_debug);
$multi_company = $office_table->multi_company();

if( ! $multi_company ) {
	unset($sts_result_loads_summary_layout["OFFICE_NUM"]);
}

if( $sts_debug ) {
		echo "<p>INPUT = </p><pre>";
		var_dump($_GET);
		echo "</pre>";
}

$load_table = new sts_load_summary($exspeedite_db, $sts_debug);
$load_table2 = sts_load::getInstance($exspeedite_db, $sts_debug);

$match = isset($_GET["match"]) && $_GET["match"] <> '' ? urldecode($_GET["match"]) : false;
if( isset($_GET["sort"]) && $_GET["sort"] <> '' )
	$sts_result_loads_edit['sort'] = urldecode($_GET["sort"]);

$rslt = new sts_result( $load_table, $match, $sts_debug );

$rslt->set_alt_table( $load_table2 );

$response =  $rslt->render_ajax( $sts_result_loads_summary_layout, $sts_result_summary_edit, $_GET );

if( $sts_debug ) {
		echo "<p>response = </p><pre>";
		var_dump($response);
		echo "</pre>";
} else {
	echo json_encode( $response );
}
?>

