<?php 

// $Id: exp_listulajax.php 4350 2021-03-02 19:14:52Z duncan $
// List user log (ajax part)

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
$my_session->access_check( $sts_table_access[CARRIER_TABLE] );	// Make sure we should be here

require_once( "include/sts_result_class.php" );
require_once( "include/sts_user_log_class.php" );
require_once( "include/sts_setting_class.php" );

$setting_table = sts_setting::getInstance($exspeedite_db, $sts_debug);

if( $sts_debug ) {
		echo "<p>GET = </p><pre>";
		var_dump($_GET);
		echo "</pre>";
}

$user_log_table = sts_user_log::getInstance($exspeedite_db, $sts_debug);

$match = isset($_GET["match"]) && $_GET["match"] <> '' ? urldecode($_GET["match"]) : false;

$pick = isset($_GET["rtype"]) && $_GET["rtype"] == 'pick';

$rslt = new sts_result( $user_log_table, $match, $sts_debug );
$response =  $rslt->render_ajax( $sts_result_user_log_layout,
	$sts_result_user_log_edit, $_GET );

if( $sts_debug ) {
		echo "<p>response = </p><pre>";
		var_dump($response);
		echo "</pre>";
} else {
	echo json_encode( $response );
}
?>

