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

if( $sts_qb_online ) {
	$CompanyInfoService = new QuickBooks_IPP_Service_CompanyInfo();
	$quickbooks_CompanyInfo = $CompanyInfoService->get($Context, $realm);
	
	$qb_country = $quickbooks_CompanyInfo ? $quickbooks_CompanyInfo->getCountry() : '';

	echo "<h2>".$sts_subtitle." Step 3 ".
		($multi_company && $sts_qb_multi ? '<span class="label label-default">'.
			$company_table->name($_SESSION['SETUP_COMPANY'], false).'</span> ' :'').
			'<div class="btn-group">'.
		($sts_qb_online && $quickbooks_is_connected ? '<a class="btn btn-md btn-default" href="setup.php"><span class="glyphicon glyphicon-arrow-left"></span> Step 1</a><a class="btn btn-md btn-default" href="setup2.php">2</a>' : '').
		'</div> '.
		($sts_qb_online && $quickbooks_is_connected &&
		$qb_country == 'CA' ? '<a class="btn btn-md btn-default" href="setup4.php">Step 4 (Canada) <span class="glyphicon glyphicon-arrow-right"></span></a>' : '').
		'</h2>';
	ob_flush(); flush();


	//! Setup Items
	$invoice_items = array();
	$bill_items = array();
	$item_comment = array();
	foreach( $sts_qb_settings as $row ) {
		if( strpos($row["SETTING_COMMENT"], "Invoice Item") !== false ) {
			$invoice_items[$row["SETTING"]] = $row["THE_VALUE"];
			$item_comment[$row["SETTING"]] = $row["SETTING_COMMENT"];
		} else if( strpos($row["SETTING_COMMENT"], "Bill Item") !== false ) {
			$bill_items[$row["SETTING"]] = $row["THE_VALUE"];
			$item_comment[$row["SETTING"]] = $row["SETTING_COMMENT"];
		}
	}
	
	$ItemService = new QuickBooks_IPP_Service_Term();
	$AccountService = new QuickBooks_IPP_Service_Account();
	
	echo '	<div class="panel panel-success">
		<div class="panel-heading">
			<h3 class="panel-title">There are ' . count($invoice_items) . ' invoice items
			and ' . count($bill_items) . ' bill items
			<span class="pull-right">See <strong>Admin -> Settings</strong> (Quickbooks Category)</span></h3>
		</div>
		<div class="panel-body">
			<div class="row well">
				<div class="col-sm-7">';
				
		echo '<div class="table-responsive">
		<table class="display table table-striped table-condensed table-bordered table-hover" id="QB_VENDORS">
		<thead><tr class="exspeedite-bg"><th>Invoice Item</th><th>Comment</th><th>QBOE</th><th>Account</th></tr>
		</thead>
		<tbody>';
		foreach( $invoice_items as $setting => $value ) {
			

			$check = $ItemService->query($Context, $realm, "SELECT * FROM Item WHERE Name = '".$value."'");
				
			$is_new = (is_array($check) && count($check) == 0) || $check == false;
			echo '<tr><td>'.$value.'</td>
			<td>'.$item_comment[$setting].'</td>
			<td>'.($is_new ? '<div class="text-center"><span class="text-muted"><span class="glyphicon glyphicon-unchecked"></span></span></div>' : '<div class="text-center"><span class="glyphicon glyphicon-check"></span></div>').'</td>
			<td>'.($is_new ? '' : $check[0]->getIncomeAccountRef_name() ).'</td>
			</tr>';
		}
		echo '</tbody>
		</table>
		</div>';
				
	ob_flush(); flush();
				
				
	echo '			</div>
				<div class="col-sm-5">
					<form role="form" action="export_items.php" 
					method="post" enctype="multipart/form-data" 
					name="EXPORT_ITEMS" id="EXPORT_ITEMS">
						<input name="ITEM_TYPE" type="hidden" value="invoice">';
							
	$accounts = $AccountService->query($Context, $realm, "SELECT * FROM Account
	where Classification = 'Revenue'
	and Active = true
	order by Id");

	echo '<div class="form-group">
    <label for="ACCOUNT">Quickbooks Revenue Account</label>
    <select class="form-control input-sm" name="ACCOUNT" id="ACCOUNT" >';
	foreach( $accounts as $account ) {
		echo '<option value="'.$account->getId().'" >'.$account->getName()." - ".$account->getAccountSubType().'</option>
		';
	}
	echo '</select>
	</div>
	
	<div class="form-group">
	<button class="btn btn-md btn-danger" name="save" type="submit" ><span class="glyphicon glyphicon-right-arrow"></span> Add Invoice Items</button>
	</div>
	<div class="form-group">
	<p class="help-block">This <strong>does not change existing invoice items</strong>, which the customer might not want changed.</p>
	<p class="help-block">This sets all created invoice items to use the same revenue account. It may or may not be what you want. You can change the revenue account for individual invoice items in QBOE later.</p>
	</div>';
						
						
	echo '			</form>			
				</div>
			</div>
			<div class="row well">
				<div class="col-sm-7">';

		echo '<div class="table-responsive">
		<table class="display table table-striped table-condensed table-bordered table-hover" id="QB_VENDORS">
		<thead><tr class="exspeedite-bg"><th>Bill Item</th><th>Comment</th><th>QBOE</th><th>Account</th></tr>
		</thead>
		<tbody>';
		foreach( $bill_items as $setting => $value ) {
			

			$check = $ItemService->query($Context, $realm, "SELECT * FROM Item WHERE Name = '".$value."'");

			$is_new = (is_array($check) && count($check) == 0) || $check == false;
			echo '<tr><td>'.$value.'</td>
			<td>'.$item_comment[$setting].'</td>
			<td>'.($is_new ? '<div class="text-center"><span class="text-muted"><span class="glyphicon glyphicon-unchecked"></span></span></div>' : '<div class="text-center"><span class="glyphicon glyphicon-check"></span></div>').'</td>
			<td>'.($is_new ? '' : $check[0]->getExpenseAccountRef_name() ).'</td>
			</tr>';
		}
		echo '</tbody>
		</table>
		</div>';
				
	ob_flush(); flush();

	echo '			</div>
				<div class="col-sm-5">
					<form role="form" action="export_items.php" 
					method="post" enctype="multipart/form-data" 
					name="EXPORT_ITEMS" id="EXPORT_ITEMS">
						<input name="ITEM_TYPE" type="hidden" value="bill">';
							
	$accounts = $AccountService->query($Context, $realm, "SELECT * FROM Account
	where Classification = 'Expense'
	and Active = true
	order by Id");

	echo '<div class="form-group">
    <label for="ACCOUNT">Quickbooks Expense Account</label>
	<select class="form-control input-sm" name="ACCOUNT" id="ACCOUNT" >';
	foreach( $accounts as $account ) {
		echo '<option value="'.$account->getId().'" >'.$account->getName()." - ".$account->getAccountSubType().'</option>
		';
	}
	echo '</select>
	</div>
	
	<div class="form-group">
	<button class="btn btn-md btn-danger" name="save" type="submit" ><span class="glyphicon glyphicon-right-arrow"></span> Add Bill Items</button>
	</div>
	<div class="form-group">
	<p class="help-block">This <strong>does not change existing bill items</strong>, which the customer might not want changed.</p>
	<p class="help-block">This sets all created bill items to use the same expense account. It may or may not be what you want. You can change the expense account for individual bill items in QBOE later.</p>
	</div>';
						
						
	echo '			</form>			
				</div>
			</div>
			';
			
	echo '</div>
	</div>
';



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
				//"bAutoWidth": false,
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
