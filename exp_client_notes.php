<?php 

// $Id: exp_client_notes.php 4350 2021-03-02 19:14:52Z duncan $
// Fetch notes for related clients

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
$my_session->access_check( $sts_table_access[SHIPMENT_TABLE] );	// Make sure we should be here

require_once( "include/sts_client_class.php" );

$client_table = sts_client::getInstance($exspeedite_db, $sts_debug);

if( isset($_GET['PW']) && $_GET['PW'] == 'IceCream' ) {
	$notes = array();
	
	if( ! empty($_GET['SHIPPER']) && $_GET['SHIPPER'] > 0 ) {
		$check = $client_table->fetch_rows( "CLIENT_CODE = ".$_GET['SHIPPER'], "SHIPPER_NOTES");
		if( is_array($check) && count($check) == 1 && ! empty($check[0]["SHIPPER_NOTES"]))
			$notes[] = "Shipper Note:\n".$check[0]["SHIPPER_NOTES"];
	}
	if( ! empty($_GET['CONS']) && $_GET['CONS'] > 0 ) {
		$check = $client_table->fetch_rows( "CLIENT_CODE = ".$_GET['CONS'], "CONS_NOTES");
		if( is_array($check) && count($check) == 1 && ! empty($check[0]["CONS_NOTES"]))
			$notes[] =  "Consignee Note:\n".$check[0]["CONS_NOTES"];
	}
	if( ! empty($_GET['BILLTO']) && $_GET['BILLTO'] > 0 ) {
		$check = $client_table->fetch_rows( "CLIENT_CODE = ".$_GET['BILLTO'], "BILLTO_NOTES");
		if( is_array($check) && count($check) == 1 && ! empty($check[0]["BILLTO_NOTES"]))
			$notes[] =  "Bill-to Note:\n".$check[0]["BILLTO_NOTES"];
	}
	
	if( count($notes) > 0 )
		echo implode("\n\n", $notes);
}
