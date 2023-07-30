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
$my_session->access_check( EXT_GROUP_USER );	// Make sure we should be here

require_once( "include/sts_result_class.php" );
require_once( "include/sts_client_class.php" );
require_once( "include/sts_setting_class.php" );

$setting_table = sts_setting::getInstance($exspeedite_db, $sts_debug);
$sts_cms_enabled = $setting_table->get("option", "CMS_ENABLED") == 'true';
$sts_cms_restrict_salespeople = $my_session->cms_restricted_salesperson();

if( ! $sts_cms_enabled ) {
	unset($sts_result_clients_layout['CLIENT_TYPE']);
	unset($sts_result_clients_layout['CURRENT_STATUS']);
}

if( $sts_cms_restrict_salespeople ) {
	$sts_result_clients_edit['rowbuttons'] = array(
		array( 'url' => 'exp_editclient.php?CODE=', 'key' => 'CLIENT_CODE', 'label' => 'CLIENT_NAME', 'tip' => 'View/edit client ', 'icon' => 'glyphicon glyphicon-edit', 'showif' => 'notdeleted' )
	);
}

//! SCR# 420 - change dropdown menu labels for lead and prospect
if( $sts_cms_enabled && ! in_array($_SESSION['CLIENT_TYPE'], array('all','client'))) {
	for( $c = 0; $c < count($sts_result_clients_edit['rowbuttons']); $c++ ) {
		$sts_result_clients_edit['rowbuttons'][$c]['tip'] = str_replace( 'client', $_SESSION['CLIENT_TYPE'], $sts_result_clients_edit['rowbuttons'][$c]['tip']);
	}
}

if( $sts_debug ) {
		echo "<p>GET = </p><pre>";
		var_dump($_GET);
		echo "</pre>";
}

$client_table = sts_client_lj::getInstance($exspeedite_db, $sts_debug);

$match = isset($_GET["match"]) && $_GET["match"] <> '' ? urldecode($_GET["match"]) : false;

$rslt = new sts_result( $client_table, $match, $sts_debug );

$response =  $rslt->render_ajax( $sts_result_clients_layout, $sts_result_clients_edit, $_GET );

if( $sts_debug ) {
		echo "<p>response = </p><pre>";
		var_dump($response);
		echo "</pre>";
} else {
	echo json_encode( $response );
}
?>

