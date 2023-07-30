<?php 
	
// $Id: exp_export_csv.php 4350 2021-03-02 19:14:52Z duncan $
// Export to CSV, for Sage 50.

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
$my_session->access_check( EXT_GROUP_ADMIN, EXT_GROUP_SAGE50 );	// Make sure we should be here

require_once( "include/sts_sage50_class.php" );
require_once( "include/sts_csv_class.php" );
require_once( "include/sts_email_class.php" );

if( isset($_GET['type']) && isset($_GET['pw']) && $_GET['pw'] == 'GoldUltimate') {
	if( $sts_debug ) echo '<p>exp_export_csv: type='.$_GET['type'].'</p>';

	if( $_GET['type'] == 'zoominfo') { //! zoominfo
		require_once( "include/sts_client_class.php" );
		
		$client_table = sts_client::getInstance($exspeedite_db, $sts_debug);
		$filter = "BILL_TO = true AND CLIENT_TYPE IN ('prospect', 'client') AND ISDELETED = 0";
		if( ! empty($_GET['code']))
			$filter .= " AND CLIENT_CODE = ".$_GET['code'];
		$csv = new sts_csv($client_table, $filter, $sts_debug);
		
		$csv->header( "Exspeedite_client" );
		$csv->render( $sts_result_clients_zoominfo_layout );
	} else if( $_GET['type'] == 'client') { //! client
		require_once( "include/sts_client_class.php" );
		
		$client_table = sts_client::getInstance($exspeedite_db, $sts_debug);
		$filter = "BILL_TO = true AND COALESCE(SAGE50_CLIENTID,'') <> ''
			AND CLIENT_TYPE = 'client' AND ISDELETED = 0";
		if( ! empty($_GET['code']))
			$filter .= " AND CLIENT_CODE = ".$_GET['code'];
		$csv = new sts_csv($client_table, $filter, $sts_debug);
		
		$csv->header( "Exspeedite_client" );
		$csv->render( $sts_result_clients_sage50_layout );
	} else if( $_GET['type'] == 'carrier') { //! carrier
		require_once( "include/sts_carrier_class.php" );
		require_once( "include/sts_setting_class.php" );
		
		$carrier_table = sts_carrier::getInstance($exspeedite_db, $sts_debug);
		$setting_table = sts_setting::getInstance($exspeedite_db, $sts_debug);
		$sts_default_expense = $setting_table->get( 'api', 'SAGE50_DEFAULT_EXP' );
		$sts_result_carriers_sage50_layout['EXPENSE']['snippet'] = "'$sts_default_expense'";

		$filter = "COALESCE(SAGE50_VENDORID,'') <> ''";
		if( ! empty($_GET['code']))
			$filter .= " AND CARRIER_CODE = ".$_GET['code'];
		$csv = new sts_csv($carrier_table, $filter, $sts_debug);
		
		$csv->header( "Exspeedite_vendor" );
		$csv->render( $sts_result_carriers_sage50_layout );
	} else if( $_GET['type'] == 'invoice') { //! invoice
		require_once( "include/sts_shipment_class.php" );
		
		$shipment_table = sts_shipment::getInstance($exspeedite_db, $sts_debug);
		$sage50 = sts_sage50_invoice::getInstance($exspeedite_db, $sts_debug);

		//! SCR# 286 - only match approved for multiple shipments
		//! SCR# 309 - fix logic here
		if( empty($_GET['code'])) {
			//! SCR# 504 - if cancelled, don't export
			$filter = "CURRENT_STATUS <> ".$shipment_table->behavior_state['cancel'].
				" AND BILLING_STATUS = ".$shipment_table->billing_behavior_state['approved'].
				" AND OFFICE_CODE > 0";
			if( isset($_SESSION['SHIPMENT_OFFICE']) && $_SESSION['SHIPMENT_OFFICE'] <> 'all' )
				$filter .= " AND OFFICE_CODE = ".$_SESSION['SHIPMENT_OFFICE'];
		}
		else //! SCR# 315 - fix error
			$filter = "(SHIPMENT_CODE = ".$_GET['code']." OR CONSOLIDATE_NUM = ".$_GET['code'].") AND BILLING_STATUS IN (".$shipment_table->billing_behavior_state['approved'].",
				".$shipment_table->billing_behavior_state['billed'].") AND OFFICE_CODE > 0";

		//! SCR# 395 - try and catch any error
		try {
			$csv = new sts_csv($sage50, $filter, $sts_debug);
			
			$csv->header( "Exspeedite_invoice" );
			$csv->render( $sts_csv_sage50_invoice_layout );
		} catch (Exception $e) {
			$email = sts_email::getInstance($exspeedite_db, $sts_debug);
			$email->send_alert("Export CSV: Invoice Error - ".$e->getMessage() );
		}
	} else if( $_GET['type'] == 'bill') { //! bill
		require_once( "include/sts_load_class.php" );
		
		$load_table = sts_load::getInstance($exspeedite_db, $sts_debug);
		$sage50 = sts_sage50_bill::getInstance($exspeedite_db, $sts_debug);
		$filter = "CURRENT_STATUS = ".$load_table->behavior_state['approved']." AND OFFICE_CODE > 0";
		if( ! empty($_GET['code']))
			$filter .= " AND LOAD_CODE = ".$_GET['code'];
		else if( isset($_SESSION['LOAD_OFFICE']) && $_SESSION['LOAD_OFFICE'] <> 'all' )
			$filter .= " AND OFFICE_CODE = ".$_SESSION['LOAD_OFFICE'];
			
		//! SCR# 395 - try and catch any error
		try {
			$csv = new sts_csv($sage50, $filter, $sts_debug);
			
			$csv->header( "Exspeedite_bill" );
			$csv->render( $sts_csv_sage50_bill_layout );
		} catch (Exception $e) {
			$email = sts_email::getInstance($exspeedite_db, $sts_debug);
			$email->send_alert("Export CSV: Bill Error - ".$e->getMessage() );
		}
	} else {
		$email = sts_email::getInstance($exspeedite_db, $sts_debug);
		$email->send_alert("Export CSV: Unknown type" );
	}
}
?>
