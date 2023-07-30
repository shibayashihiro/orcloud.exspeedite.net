<?php

// Set flag that this is a parent file
define( '_STS_INCLUDE', 1 );

// Setup Session
require_once( "../../include/sts_session_setup.php" );

require_once dirname(__FILE__) . '/config.php';

require_once( "include/sts_session_class.php" );

$my_session = sts_session::getInstance( $exspeedite_db, $sts_debug );
$my_session->access_check( EXT_GROUP_ADMIN );	// Make sure we should be here

define( 'EXP_RELATIVE_PATH', '../../' );
$sts_subtitle = "Setup QBOE";
require_once( "include/header_inc.php" );
require_once( "include/navbar_inc.php" );
require_once( "include/sts_client_class.php" );
require_once( "include/sts_carrier_class.php" );
require_once( "include/sts_company_class.php" );

// Turn off output buffering
ini_set('output_buffering', 'off');
// Implicitly flush the buffer(s)
ini_set('implicit_flush', true);
ob_implicit_flush(true);

if (ob_get_level() == 0) ob_start();

$company_table = sts_company::getInstance($exspeedite_db, $sts_debug);

if( $multi_company && $sts_qb_multi && isset($_SESSION['SETUP_COMPANY']) ) {
	list($quickbooks_is_connected, $realm, $Context) =
		connect_to_quickbooks( $_SESSION['SETUP_COMPANY'] );
} else {
	list($quickbooks_is_connected, $realm, $Context) = connect_to_quickbooks();
}

echo '<div class="container-full" role="main">';

if( $sts_qb_online && $quickbooks_is_connected ) {
	$CompanyInfoService = new QuickBooks_IPP_Service_CompanyInfo();
	$quickbooks_CompanyInfo = $CompanyInfoService->get($Context, $realm);
	
	$qb_country = $quickbooks_CompanyInfo ? $quickbooks_CompanyInfo->getCountry() : '';
} else {
	$qb_country = '';
}

echo "<h2>".$sts_subtitle." Step 2 ".
	($multi_company && $sts_qb_multi ? '<span class="label label-default">'.$company_table->name($_SESSION['SETUP_COMPANY'], false).'</span> ' :'').
	($sts_qb_online && $quickbooks_is_connected ? '<a class="btn btn-md btn-default" href="setup.php"><span class="glyphicon glyphicon-arrow-left"></span> Step 1</a>'.
	' <div class="btn-group"><a class="btn btn-md btn-default" href="setup3.php">Step 3'.
	($qb_country == 'CA' ? '' : ' <span class="glyphicon glyphicon-arrow-right"></span>').'</a>'.
	($qb_country == 'CA' ? '<a class="btn btn-md btn-default" href="setup4.php">4 (Canada) <span class="glyphicon glyphicon-arrow-right"></span></a>' : '').'</div>' : '')."</h2>";

	ob_flush(); flush();

if( $sts_qb_online ) {

	//! Setup Clients
	$client_table = sts_client::getInstance($exspeedite_db, false);

	$CustomerService = new QuickBooks_IPP_Service_Customer();

	$count = $CustomerService->query($Context, $realm, "SELECT COUNT(*) FROM Customer where Active = true ");
	
	echo '	<div class="panel panel-success">
		<div class="panel-heading">
			<h3 class="panel-title">There are a total of ' . $count . ' customers!
			'.($count > 1000 ? ' - <span class="text-danger">WARNING LIMIT OF 1000 imported</span>' :'').'
			<a href="importclients.php" class="btn btn-danger btn-sm pull-right" style="margin: -5px;"><span class="glyphicon glyphicon glyphicon-arrow-right"></span> Import To Exspeedite</a></h3>
		</div>
		<div class="panel-body">';

	ob_flush(); flush();

	$customers = $CustomerService->query($Context, $realm, "SELECT * FROM Customer 
		where Active = true MAXRESULTS 1000");
		
	if( is_array($customers)) {
		echo '<div class="table-responsive">
		<table class="display table table-striped table-condensed table-bordered table-hover" id="QB_CLIENTS">
		<thead><tr class="exspeedite-bg"><th>Name</th><th>Phone</th><th>Email</th><th>Address</th><th>Exists</th></tr>
		</thead>
		<tbody>';
		foreach( $customers as $customer ) {
			$name = $customer->getDisplayName();
			$number = $customer->getPrimaryPhone();
			$number = isset($number) ? $number->getFreeFormNumber() : 'NULL'; //print_r($customer, true);
			$email = $customer->getPrimaryEmailAddr();
			$email = isset($email) ? $email->getAddress() : 'NULL'; //print_r($customer, true);
			$check = $client_table->fetch_rows("CLIENT_NAME = '".$client_table->real_escape_string(trim((string) $name))."' AND
				ISDELETED = false",$client_table->primary_key.
				", BILL_TO, CONSIGNEE");
			$is_new = is_array($check) && count($check) == 0;


			echo '<tr><td>'.$name.'</td>
			<td>'.$number.'</td>
			<td>'.$email.'</td>
			<td>'.format_qb_addr( $customer->getBillAddr() ).'</td>
			<td>'.($is_new ? '<div class="text-center"><span class="text-muted"><span class="glyphicon glyphicon-unchecked"></span></span></div>' : '<div class="text-center"><span class="glyphicon glyphicon-check"></span></div>').'</td>
			</tr>';			
		}
		echo '</tbody>
		</table>
		</div>
				</div>
	</div>
';
	}

	ob_flush(); flush();

	//! Setup Carriers
	$carrier_table = sts_carrier::getInstance($exspeedite_db, false);

	$VendorService = new QuickBooks_IPP_Service_Vendor();
	
	$count = $VendorService->query($Context, $realm, "SELECT COUNT(*) FROM Vendor where Active = true MAXRESULTS 1000");

	
	echo '	<div class="panel panel-success">
		<div class="panel-heading">
			<h3 class="panel-title">There are a total of ' . $count . ' vendors!&nbsp;&nbsp;&nbsp;
			Set AlternatePhone="Carrier" to flag for import
			<a href="importcarriers.php" class="btn btn-danger btn-sm pull-right" style="margin: -5px;"><span class="glyphicon glyphicon glyphicon-arrow-right"></span> Import To Exspeedite</a></h3>
		</div>
		<div class="panel-body">';

	$vendors = $VendorService->query($Context, $realm, "SELECT * FROM Vendor
		where Active = true");
		
	if( is_array($vendors)) {
		echo '<div class="table-responsive">
		<table class="display table table-striped table-condensed table-bordered table-hover" id="QB_VENDORS">
		<thead><tr class="exspeedite-bg"><th>Name</th><th>Phone</th><th>Email</th><th>Address</th><th>Exists</th></tr>
		</thead>
		<tbody>';
		foreach( $vendors as $vendor ) {
			if( 0 && $vendor->getDisplayName() == 'Books by Bessie' ) {
				echo "<pre>";
				var_dump($vendor);
				echo "</pre>";
			}
			// Use AlternatePhone to determine Carriers
			$number2 = $vendor->getAlternatePhone();
			$number2 = is_object($number2) ? $number2->getFreeFormNumber() : 'ERROR';
			if( strcasecmp($number2, 'Carrier') == 0 ) {
				$name = $vendor->getDisplayName();
				$number = $vendor->getPrimaryPhone();
				$number = is_object($number) ? $number->getFreeFormNumber() : 'ERROR';
				$email = $vendor->getPrimaryEmailAddr();
				$email = is_object($email) ? $email->getAddress() : 'ERROR';
				
				$check = $carrier_table->fetch_rows("CARRIER_NAME = '".$carrier_table->real_escape_string(trim((string) $name))."' AND
					ISDELETED = false",$carrier_table->primary_key);
				$is_new = (is_array($check) && count($check) == 0) || $check == false;
					
				echo '<tr><td>'.$name.'</td>
				<td>'.$number.'</td>
				<td>'.$email.'</td>
				<td>'.format_qb_addr( $vendor->getBillAddr() ).'</td>
				<td>'.($is_new ? '<div class="text-center"><span class="text-muted"><span class="glyphicon glyphicon-unchecked"></span></span></div>' : '<div class="text-center"><span class="glyphicon glyphicon-check"></span></div>').'</td>
				</tr>';
			}		
		}
		echo '</tbody>
		</table>
		</div>
				</div>
	</div>
';
	}


}

echo '</div>';

?>

	<script language="JavaScript" type="text/javascript"><!--
		
		$(document).ready( function () {

			$('#QB_CLIENTS, #QB_VENDORS').dataTable({
		        //"bLengthChange": false,
		        "bFilter": false,
		        "bSort": false,
		        "bInfo": false,
				"bAutoWidth": false,
				//"bProcessing": true, 
				"sScrollX": "100%",
				"sScrollY": "200px",
				//"sScrollXInner": "200%",
		        //"lengthMenu": [[-1, 25, 50], ["All", 25, 50]],
				"bPaginate": false,
				"bScrollCollapse": true,
				"bSortClasses": false		
			});
		});
		
	//--></script>

<?php

require_once( "include/footer_inc.php" );
?>
