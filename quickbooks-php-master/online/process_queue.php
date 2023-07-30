<?php

// Set flag that this is a parent file
define( '_STS_INCLUDE', 1 );

parse_str(implode('&', array_slice($argv, 1)), $_GET);

$arg = explode('=',$argv[1]);
if( is_array($arg) && $arg[0] == 'subdomain')
	$sts_subdomain = $arg[1];

$_SERVER['REMOTE_ADDR'] = 'unknown';

require_once dirname(__FILE__) . '/config.php';
require_once dirname(__FILE__) . '/qb_online_api.php';
require_once( "include/sts_company_class.php" );
$company_table = sts_company::getInstance($exspeedite_db, $sts_debug);

$sts_debug = isset($_GET['debug']);
set_time_limit(0);

$api = sts_quickbooks_online_API::getInstance(false, false, $exspeedite_db, $sts_debug);

$api->log_event( "process_queue: start", EXT_ERROR_DEBUG);
//$api->customer_query( 7108 );
//die('<p>testing</p>');

$driver = QuickBooks_Utilities::driverFactory($sts_qb_dsn, $driver_options);

// Login, in order to get a ticket. We need that for queueStatus() calls
$wait = 5;
$min_run = 1;
$ticket = $driver->authLogin($user, $pass, $sts_qb_company_file, $wait, $min_run, true);

if(isset($_GET['single'])) {
	$api->vendor_query( $_GET['single'] );
	die('<p>testing</p>');
}

if(isset($_GET['test1'])) {
	$VendorService = new QuickBooks_IPP_Service_Vendor();
	
	$vendors = $VendorService->query($Context, $realm, "SELECT * FROM Vendor where DisplayName = 'Fidelity'");
	echo "<pre>";
	var_dump($vendors);
	echo "</pre>";
	die;
}

if(isset($_GET['test2'])) {
	list($quickbooks_is_connected, $realm, $Context) =
		connect_to_quickbooks();

	$BillService = new QuickBooks_IPP_Service_Bill();
	
	$bills = $BillService->query($Context, $realm, "SELECT * FROM Bill ");
	echo "<pre>";
	var_dump($bills);
	echo "</pre>";
	die;
}

if(isset($_GET['test3'])) {
	//$company = $company_table->shipment_company( $next_item["ident"] );
	list($quickbooks_is_connected, $realm, $Context) =
		connect_to_quickbooks();

	$TaxCodeService = new QuickBooks_IPP_Service_TaxCode();
	
	$taxcodes = $TaxCodeService->query($Context, $realm, "SELECT * FROM TaxCode");
	foreach ($taxcodes as $TaxCode) {
		print('TaxCode Id=' . $TaxCode->getId() . ' is named: ' . $TaxCode->getName() . '<br>');
	}
	echo "<pre>";
	var_dump($taxcodes);
	echo "</pre>";
	die;
}

if( $sts_debug ) echo "<p>process_queue: ".$driver->queueLeft($user)." in the queue</p>";
$api->log_event( "process_queue: ".$driver->queueLeft($user)." in the queue", EXT_ERROR_DEBUG);

while( $driver->queueLeft($user) > 0 ) {
	$next_item = $driver->queueDequeue($user);
	
	if( $sts_debug ) {
		echo "<pre>";
		var_dump($next_item);
		echo "</pre>";
	}
	
	if( in_array($next_item["qb_action"], array(QUICKBOOKS_QUERY_CUSTOMER, QUICKBOOKS_MOD_DATAEXT, QUICKBOOKS_QUERY_VENDOR, QUICKBOOKS_QUERY_EMPLOYEE)) ) {
		$api->log_event( "process_queue: action=".$next_item["qb_action"]." id=".$next_item["ident"], EXT_ERROR_DEBUG);
		
		if (strlen($next_item['extra'])) {
			$extra = unserialize($next_item['extra']);
		} else {
			$extra = false;
		}

		$driver->queueStatus($ticket, $next_item["quickbooks_queue_id"], QUICKBOOKS_STATUS_PROCESSING, 'process_queue');
		
		// Do processing...
		// $next_item["qb_action"] = what
		// $next_item["ident"] = which
		switch( $next_item["qb_action"] ) {
			case QUICKBOOKS_QUERY_CUSTOMER:		// Prelude to sending an invoice
				if( $multi_company && $sts_qb_multi ) {
					$company = $company_table->shipment_company( $next_item["ident"] );
					list($quickbooks_is_connected, $realm, $Context) =
						connect_to_quickbooks( $company );
					$api->log_event( "process_queue: connect to QB ".$company." ".($quickbooks_is_connected ? 'successful' : 'failed'), EXT_ERROR_DEBUG);
				} else {
					list($quickbooks_is_connected, $realm, $Context) = connect_to_quickbooks();
					$api->log_event( "process_queue: connect to default QB ".($quickbooks_is_connected ? 'successful' : 'failed'), EXT_ERROR_DEBUG);
				}
				$api->change($Context, $realm);
				
				$result = $api->customer_query( $next_item["ident"] );
				break;
			
			case QUICKBOOKS_QUERY_VENDOR:		// Prelude to sending a bill
				// If $extra['vendortype'] == QUICKBOOKS_VENDOR_CARRIER --> carrier
				// If $extra['vendortype'] == QUICKBOOKS_VENDOR_DRIVER --> driver
				if( is_array($extra) && ! empty($extra['vendortype']) &&
					in_array($extra['vendortype'],
						array(QUICKBOOKS_VENDOR_CARRIER, QUICKBOOKS_VENDOR_DRIVER)) ) {
					if( $multi_company && $sts_qb_multi ) {
						$company = $company_table->load_company( $next_item["ident"] );
						list($quickbooks_is_connected, $realm, $Context) =
							connect_to_quickbooks( $company );
						$api->log_event( "process_queue: connect to QB ".$company." ".($quickbooks_is_connected ? 'successful' : 'failed'), EXT_ERROR_DEBUG);
					} else {
						list($quickbooks_is_connected, $realm, $Context) = connect_to_quickbooks();
						$api->log_event( "process_queue: connect to default QB ".($quickbooks_is_connected ? 'successful' : 'failed'), EXT_ERROR_DEBUG);
					}
					$api->change($Context, $realm);
				
					$result = $api->vendor_query(
						$next_item["ident"], $extra['vendortype'] );
				}
				
				break;
			
			default:							// All else, ignore
				$result = false;
				break;
		}
		
		$driver->queueStatus($ticket, $next_item["quickbooks_queue_id"], 
			$result == false ? QUICKBOOKS_STATUS_ERROR : QUICKBOOKS_STATUS_SUCCESS, 'process_queue');
	}
}
// Logout
$driver->authLogout($ticket);

if( $sts_debug ) echo "<p>process_queue: completed</p>";
$api->log_event( "process_queue: completed", EXT_ERROR_DEBUG);

exit(2375);

?>