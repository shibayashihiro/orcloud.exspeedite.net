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
require_once( "include/sts_shipment_class.php" );
require_once( "include/sts_load_class.php" );
require_once( "include/sts_setting_class.php" );

$setting_table = sts_setting::getInstance($exspeedite_db, $sts_debug);
$sts_export_qb = $setting_table->get( 'api', 'EXPORT_QUICKBOOKS' ) == 'true';
$sts_qb_online = $setting_table->get( 'api', 'QUICKBOOKS_ONLINE' ) == 'true';
$sts_manual_spawn = $setting_table->get( 'api', 'QUICKBOOKS_MANUAL_SPAWN' ) == 'true';

// Require the framework
if( $sts_export_qb )
	require_once( $sts_crm_dir."quickbooks-php-master/QuickBooks.php" );

if( isset($_GET['CODE']) && isset($_GET['TYPE']) ) {
	
	if( $sts_export_qb ) {
		// Queue up the customer add 
		$Queue = new QuickBooks_WebConnector_Queue($sts_qb_dsn);
		if( $_GET['TYPE'] == 'load' ) {
			$load_table = sts_load::getInstance($exspeedite_db, $sts_debug);
	
			if( $_GET['CODE'] == 'ALL')
				$match = "CURRENT_STATUS = ".$load_table->behavior_state['approved'];
			else
				$match = $load_table->primary_key.' = '.$_GET['CODE'];
			$result = $load_table->update_row( $match,
				array(
					array( "field" => "quickbooks_status_message", "value" => "''"),
					array( "field" => "quickbooks_txnid_ap", "value" => "''"),
					array( "field" => "PAID_DATE", "value" => "NULL")
				 ) );
			if( $_GET['CODE'] == 'ALL') {
				$approved = $load_table->fetch_rows($match, "LOAD_CODE");
				if( is_array($approved) && count($approved) > 0) {
					foreach($approved as $row) {
						if( $sts_debug ) echo "<p>exp_qb_retry: enqueue(QUICKBOOKS_QUERY_VENDOR, ".$row['LOAD_CODE'].")</p>";
	
						$Queue->enqueue(QUICKBOOKS_QUERY_VENDOR, $row['LOAD_CODE'], 0, 
							array( 'vendortype' => QUICKBOOKS_VENDOR_CARRIER ));
					}
				}
			} else {
				if( $sts_debug ) echo "<p>exp_qb_retry: enqueue(QUICKBOOKS_QUERY_VENDOR, ".$_GET['CODE'].")</p>";
				$Queue->enqueue(QUICKBOOKS_QUERY_VENDOR, $_GET['CODE'], 0, 
							array( 'vendortype' => QUICKBOOKS_VENDOR_CARRIER ));
			}	
			if( $sts_qb_online && ! $sts_manual_spawn )
				require_once( $sts_crm_dir.DIRECTORY_SEPARATOR."quickbooks-php-master".
					DIRECTORY_SEPARATOR."online".DIRECTORY_SEPARATOR."spawn_process.php" );
				
			if( ! $sts_debug ) {
				if( $_GET['CODE'] == 'ALL')
					reload_page ( "exp_listload.php" );	// Back to list shipment page
				else
					reload_page ( "exp_viewload.php?CODE=".$_GET['CODE'] );	// Back to view load page
			}
	
		} else if( $_GET['TYPE'] == 'shipment' ) {
			$shipment_table = sts_shipment::getInstance($exspeedite_db, $sts_debug);
			if( $_GET['CODE'] == 'ALL')
				$match = "BILLING_STATUS = ".$shipment_table->billing_behavior_state['approved'];
			else
				$match = $shipment_table->primary_key.' = '.$_GET['CODE'];
			$result = $shipment_table->update_row( $match,
				array(
					array( "field" => "quickbooks_status_message", "value" => "''"),
					array( "field" => "quickbooks_txnid_invoice", "value" => "''")
				 ) );
			if( $_GET['CODE'] == 'ALL') {
				$approved = $shipment_table->fetch_rows($match, "SHIPMENT_CODE");
				if( is_array($approved) && count($approved) > 0) {
					foreach($approved as $row) {
						if( $sts_debug ) echo "<p>exp_qb_retry: enqueue(QUICKBOOKS_QUERY_CUSTOMER, ".$row['SHIPMENT_CODE'].")</p>";
						$Queue->enqueue(QUICKBOOKS_QUERY_CUSTOMER, $row['SHIPMENT_CODE']);
					}
				}
			} else {
				if( $sts_debug ) echo "<p>exp_qb_retry: enqueue(QUICKBOOKS_QUERY_CUSTOMER, ".$_GET['CODE'].")</p>";
				$Queue->enqueue(QUICKBOOKS_QUERY_CUSTOMER, $_GET['CODE']);
			}	
			if( $sts_qb_online && ! $sts_manual_spawn )
				require_once( $sts_crm_dir.DIRECTORY_SEPARATOR."quickbooks-php-master".
					DIRECTORY_SEPARATOR."online".DIRECTORY_SEPARATOR."spawn_process.php" );
				
			if( ! $sts_debug ) {
				if( $_GET['CODE'] == 'ALL')
					reload_page ( "exp_listshipment.php" );	// Back to list shipment page
				else
					reload_page ( "exp_addshipment.php?CODE=".$_GET['CODE'] );	// Back to shipment page
			}
		} else if( $_GET['TYPE'] == 'dataext' ) {
			$shipment_table = sts_shipment::getInstance($exspeedite_db, $sts_debug);
			if( $_GET['CODE'] == 'ALL')
				$match = "CURRENT_STATUS = ".$shipment_table->behavior_state['billed'].
					" AND COALESCE(quickbooks_dataext_retries,0) <> -1";
			else
				$match = $shipment_table->primary_key.' = '.$_GET['CODE'];
			if( $_GET['CODE'] == 'ALL') {
				$billed = $shipment_table->fetch_rows($match, "SHIPMENT_CODE");
				if( is_array($billed) && count($billed) > 0) {
					foreach($billed as $row) {
						if( $sts_debug ) echo "<p>exp_qb_retry: enqueue(QUICKBOOKS_MOD_DATAEXT, ".$row['SHIPMENT_CODE'].")</p>";
						$Queue->enqueue(QUICKBOOKS_MOD_DATAEXT, $row['SHIPMENT_CODE']);
					}
				}
			} else {
				if( $sts_debug ) echo "<p>exp_qb_retry: enqueue(QUICKBOOKS_MOD_DATAEXT, ".$_GET['CODE'].")</p>";
				$Queue->enqueue(QUICKBOOKS_MOD_DATAEXT, $_GET['CODE']);
			}	
				
			if( ! $sts_debug ) {
				if( $_GET['CODE'] == 'ALL')
					reload_page ( "exp_listshipment.php" );	// Back to list shipment page
				else
					reload_page ( "exp_addshipment.php?CODE=".$_GET['CODE'] );	// Back to shipment page
			}
		}
	} else {
		//! Error - quickbooks is not enabled for export
		reload_page ( "index.php" );	// Back to home page
	}
}

?>