<?php
	
// $Id: exp_listshipmentajax.php 5030 2023-04-12 20:31:34Z duncan $
// handle AJAX side of list shipment

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
$my_session->access_check( $sts_table_access[SHIPMENT_TABLE] );	// Make sure we should be here

require_once( "include/sts_result_class.php" );
require_once( "include/sts_shipment_class.php" );
require_once( "include/sts_setting_class.php" );

$setting_table = sts_setting::getInstance($exspeedite_db, $sts_debug);
$sts_intermodal_fields = $setting_table->get( 'option', 'INTERMODAL_FIELDS' ) == 'true';
$sts_po_fields = $setting_table->get( 'option', 'PO_FIELDS' ) == 'true';
$sts_refnum_fields = $setting_table->get( 'option', 'REFNUM_FIELDS' ) == 'true';
$multi_company = $setting_table->get("option", "MULTI_COMPANY") == 'true';
$sts_export_qb = $setting_table->get( 'api', 'EXPORT_QUICKBOOKS' ) == 'true';
$sts_export_sage50 = $setting_table->get( 'api', 'EXPORT_SAGE50_CSV' ) == 'true';

$accounting = $sts_export_qb ? 'QuickBooks' : ($sts_export_sage50 ? 'Sage 50' : 'Accounting');

if( $sts_debug ) {
		echo "<p>GET = </p><pre>";
		var_dump($_GET);
		echo "</pre>";
}

$shipment_table = sts_shipment::getInstance($exspeedite_db, $sts_debug);

if( isset($_SESSION['SHIPMENT_STATUS']) &&
	in_array($_SESSION['SHIPMENT_STATUS'],
		array('picked','dropped','approved','billed','cancel')) ) {
	unset($sts_result_shipments_edit['add']);
	$sts_result_shipments_edit['rowbuttons'] = array(
		array( 'url' => 'exp_addshipment.php?CODE=', 'key' => 'SHIPMENT_CODE', 'label' => 'SHIPMENT_CODE', 'tip' => 'Edit shipment ', 'icon' => 'glyphicon glyphicon-edit' ),
		array( 'url' => 'exp_dupshipment.php?CODE=', 'key' => 'SHIPMENT_CODE', 'label' => 'SHIPMENT_CODE', 'tip' => 'Duplicate shipment ', 'icon' => 'glyphicon glyphicon-repeat' ),
  		array( 'label' => 'BSTATE' ), //! For Billing state part of drop down
 		array( 'label' => 'Separator', 'restrict' => EXT_GROUP_FINANCE ),
		array( 'url' => 'exp_clientpay.php?id=', 'key' => 'EDI_204_PRIMARY', 'label' => 'SHIPMENT_CODE', 'tip' => 'Client billing ', 'icon' => 'glyphicon glyphicon-th-list', 'restrict' => EXT_GROUP_FINANCE,
		//! SCR# 504 - if cancelled, don't show billing button
		'showif' => 'notcancelled' ),
	);
	
	//$sts_result_shipments_layout['quickbooks_listid_customer'] = array( 'label' => 'QB&nbsp;Billto', 'format' => 'text' );
	//$sts_result_shipments_layout['quickbooks_txnid_invoice'] = array( 'label' => 'QB&nbsp;TxnID', 'format' => 'text' );
}

if( ! $sts_intermodal_fields ) {
	unset($sts_result_shipments_layout['FS_NUMBER'], $sts_result_shipments_layout['SYNERGY_IMPORT']);
}
if( ! $sts_po_fields ) {
	unset($sts_result_shipments_layout['PO_NUMBER']);
}
if( ! $sts_refnum_fields ) {
	unset($sts_result_loads_lj_layout['REF_NUMBER']);
}
if( ! $multi_company ) {
	unset($sts_result_loads_lj_layout['SS_NUMBER']);
}
$match = isset($_GET["match"]) && $_GET["match"] <> '' ? urldecode($_GET["match"]) : false;

$rslt = new sts_result( $shipment_table, $match, $sts_debug );

$response =  $rslt->render_ajax( $sts_result_shipments_layout, $sts_result_shipments_edit, $_GET );

if( $sts_debug ) {
		echo "<p>response = </p><pre>";
		var_dump($response);
		echo "</pre>";
} else {
	echo json_encode( $response );
}

?>
