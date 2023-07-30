<?php 
	
// $Id: exp_shipment_state.php 5030 2023-04-12 20:31:34Z duncan $
// Shipment state transitions.

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

require_once( "include/sts_shipment_class.php" );
require_once( "include/sts_email_class.php" );
require_once( "include/sts_setting_class.php" );

$setting_table = sts_setting::getInstance($exspeedite_db, $sts_debug);
$sts_export_qb = $setting_table->get( 'api', 'EXPORT_QUICKBOOKS' ) == 'true';
$sts_destination = $sts_export_qb ? 'Quickbooks' : 'Finance';
$multi_company = $setting_table->get("option", "MULTI_COMPANY") == 'true';
$sts_approve_operations = $setting_table->get( 'option', 'SHIPMENT_APPROVE_OPERATIONS' ) == 'true';

if( isset($_GET['REFERER']) )
	$referer = base64_decode($_GET['REFERER']);
else if( isset($_POST['REFERER']) )
	$referer = $_POST['REFERER'];
else if( isset($_SERVER["HTTP_REFERER"]) ) {
	$path = explode('/', $_SERVER["HTTP_REFERER"]); 
	$referer = end($path);
} else
	$referer = 'unknown';
	
if( isset($_GET['CODE']) && isset($_GET['STATE'])  && isset($_GET['PW']) && $_GET['PW'] == 'Soyo' ) {
	$shipment_table = sts_shipment::getInstance($exspeedite_db, $sts_debug);
	$email = sts_email::getInstance($exspeedite_db, $sts_debug);
	$is_billing = isset($_GET['BILLING']);

	if( $sts_debug ) echo "<p>shipment_state: code = ".$_GET['CODE']." state = ".$_GET['STATE']." cstate = ".(isset($_GET['CSTATE']) ? $_GET['CSTATE'] : -1 )." billing = ".($is_billing ? 'true' : 'false')."</p>";
	
	//! Possble consolidation of shipments
	//! SCR# 340 - allow for Approved (Office)
	if( $is_billing && in_array($_GET['STATE'], array('approved', 'oapproved', 'oapproved2')) &&
		isset($_GET['CSTATE']) &&
		in_array( $shipment_table->billing_state_behavior[$_GET['CSTATE']],
			array('entry', 'oapproved', 'unapproved')) &&
		! isset($_GET['CONTINUE'])) {
		if( $sts_debug ) echo "<p>shipment_state: Possble consolidation</p>";

		if( isset($_GET['ADD'])) {
			$shipment_table->consolidate_shipment( $_GET['CODE'], $_GET['ADD'] );
		} else
		if( isset($_GET['DEL'])) {
			$shipment_table->unconsolidate_shipment( $_GET['CODE'], $_GET['DEL'] );
		}
		
		$cons = $shipment_table->check_consolidate_shipments( $_GET['CODE'] );
		if( $sts_debug ) {
			echo "<pre>Possbles\n";
			var_dump($cons);
			echo "</pre>";
		}
		if( is_array($cons) && count($cons) > 1 ) {	// We have some possibles
			$sts_subtitle = "Consolidate Shipments";
			require_once( "include/header_inc.php" );
			require_once( "include/navbar_inc.php" );
	
			echo '<div class="container theme-showcase" role="main">
	
			<div class="well  well-md">
				<h2>Consolidate Shipments With '.$_GET['CODE'].' For Billing
				<a style="margin-bottom: 8px;" class="btn btn-md btn-default" href="exp_addshipment.php?CODE='.$_GET['CODE'].'"><span class="glyphicon glyphicon-remove"></span> Back</a></h2>';
			
			echo '<div class="table-responsive">
			<table class="display table table-striped table-condensed table-bordered table-hover" id="SHIP">
			<thead><tr class="exspeedite-bg"><th>Shipment</th><th>Combined</th><th>Bill-To</th><th>Ref#</th><th>Shipper</th><th>Consignee</th><th>Delivered</th><th class="text-right">Amount</th></tr>
			</thead>
			<tbody>';
			foreach( $cons as $row ) {	//! Above
				if( $row["SHIPMENT_CODE"] == $_GET['CODE'] ||
					 (isset($row["CONSOLIDATE_NUM"]) && $row["CONSOLIDATE_NUM"] == $_GET['CODE']) ) {
					$actual = isset($row["ACTUAL_DELIVERY"]) && $row["ACTUAL_DELIVERY"] <> '0000-00-00 00:00:00' && $row["ACTUAL_DELIVERY"] <> '' ? date("m/d/Y_h:i A", strtotime($row["ACTUAL_DELIVERY"])) : '';
					$actual = str_replace('_', '<br>', $actual);
	
					echo '<tr><td><a style="font-size: 24px;" href="exp_addshipment.php?CODE='.$row["SHIPMENT_CODE"].'">'.$row["SHIPMENT_CODE"].
					($multi_company ? '&nbsp;/&nbsp;'.$row["SS_NUMBER"] : '').
					'</a>&nbsp;';
					
					echo '<a style="margin-bottom: 8px;" class="btn btn-xs btn-info" id="Billing_'.$row["SHIPMENT_CODE"].'" href="exp_clientpay.php?id='.$row["SHIPMENT_CODE"].'"><span class="glyphicon glyphicon-th-list"></span> Billing</a></td>
					<td>';
					
					if( $row["SHIPMENT_CODE"] != $_GET['CODE'] &&
						(isset($row["CONSOLIDATE_NUM"]) && $row["CONSOLIDATE_NUM"] == $_GET['CODE']) )
						echo '<p style="margin-top: 6px;"><span class="glyphicon glyphicon-ok"></span> <a class="btn btn-xs btn-danger" id="Billing_'.$row["SHIPMENT_CODE"].'" href="exp_shipment_state.php?BILLING=true&PW=Soyo&STATE='.$_GET['STATE'].'&CODE='.$_GET['CODE'].'&CSTATE='.$_GET['CSTATE'].'&DEL='.$row["SHIPMENT_CODE"].'"><span class="glyphicon glyphicon-minus"></span> Del</a></p>';
						
					echo '</td>
					<td><a href="exp_editclient.php?CODE='.$row["BILLTO_CLIENT_CODE"].'">'.$row["BILLTO_NAME"].'</a></td>
					<td>'.$row["REF_NUMBER"].'</td>
					<td>'.$row["SHIPPER_NAME"].'<br>'.$row["SHIPPER_CITY"].', '.$row["SHIPPER_STATE"].'</td>
					<td>'.$row["CONS_NAME"].'<br>'.$row["CONS_CITY"].', '.$row["CONS_STATE"].'</td>
					<td>'.$actual.'</td>
					<td class="text-right">'.$row["TOTAL"].'</td>
					</tr>';
				}
			}
			echo '</tbody>
			</table>
			</div>';
			
			
			echo '<h4>Select any other shipment to be combined with '.$_GET['CODE'].' into one bill, and then press <a class="btn btn-md btn-danger" id="continue" onclick="confirmation(\'Do you really want to approve this for billing?\n\nThis sends the bill to '.$sts_destination.'.\', \'exp_shipment_state.php?BILLING=true&PW=Soyo&STATE='.$_GET['STATE'].'&CODE='.
			$_GET['CODE'].'&CSTATE='.$_GET['CSTATE'].'&CONTINUE\')"><span class="glyphicon glyphicon-arrow-right"></span> Approve '.($_GET['STATE']=='oapproved2'? '(Operations)':
			($_GET['STATE']=='oapproved'? '(Office)':'(Finance)')).'</a></h4>
			';
			echo '<div class="table-responsive">
			<table class="display table table-striped table-condensed table-bordered table-hover" id="CONS">
			<thead><tr class="exspeedite-bg"><th>Shipment</th><th>Combined</th><th>Bill-To</th><th>Ref#</th><th>Shipper</th><th>Consignee</th><th>Delivered</th><th class="text-right">Amount</th></tr>
			</thead>
			<tbody>';
			foreach( $cons as $row ) {	//! Below
				if( $row["SHIPMENT_CODE"] <> $_GET['CODE'] &&
					! (isset($row["CONSOLIDATE_NUM"]) && $row["CONSOLIDATE_NUM"] == $_GET['CODE']) ) {
					$actual = isset($row["ACTUAL_DELIVERY"]) && $row["ACTUAL_DELIVERY"] <> '0000-00-00 00:00:00' && $row["ACTUAL_DELIVERY"] <> '' ? date("m/d/Y_h:i A", strtotime($row["ACTUAL_DELIVERY"])) : '';
					$actual = str_replace('_', '<br>', $actual);
	
					echo '<tr><td><a style="font-size: 24px;" href="exp_addshipment.php?CODE='.$row["SHIPMENT_CODE"].'">'.$row["SHIPMENT_CODE"].
					($multi_company ? '&nbsp;/&nbsp;'.$row["SS_NUMBER"] : '').
					'</a>&nbsp;';
					
					echo '<a style="margin-bottom: 8px;" class="btn btn-xs btn-info" id="Billing_'.$row["SHIPMENT_CODE"].'" href="exp_clientpay.php?id='.$row["SHIPMENT_CODE"].'"><span class="glyphicon glyphicon-th-list"></span> Billing</a></td>
					<td>';
	
					if( isset($row["CONSOLIDATE_NUM"]) && $row["CONSOLIDATE_NUM"] == $_GET['CODE'] )
						echo '<span class="glyphicon glyphicon-ok"></span> <a class="btn btn-xs btn-danger" id="Billing_'.$row["SHIPMENT_CODE"].'" href="exp_shipment_state.php?BILLING=true&PW=Soyo&STATE='.$_GET['STATE'].'&CODE='.$_GET['CODE'].'&CSTATE='.$_GET['CSTATE'].'&DEL='.$row["SHIPMENT_CODE"].'"><span class="glyphicon glyphicon-minus"></span> Del</a>';
					else
						echo '<p style="margin-top: 6px;"><a class="btn btn-xs btn-success" id="Billing_'.$row["SHIPMENT_CODE"].'" href="exp_shipment_state.php?BILLING=true&PW=Soyo&STATE='.$_GET['STATE'].'&CODE='.$_GET['CODE'].'&CSTATE='.$_GET['CSTATE'].'&ADD='.$row["SHIPMENT_CODE"].'"><span class="glyphicon glyphicon-plus"></span> Add</a></p>';
	
					echo '</td>
					<td><a href="exp_editclient.php?CODE='.$row["BILLTO_CLIENT_CODE"].'">'.$row["BILLTO_NAME"].'</a></td>
					<td>'.$row["REF_NUMBER"].'</td>
					<td>'.$row["SHIPPER_NAME"].'<br>'.$row["SHIPPER_CITY"].', '.$row["SHIPPER_STATE"].'</td>
					<td>'.$row["CONS_NAME"].'<br>'.$row["CONS_CITY"].', '.$row["CONS_STATE"].'</td>
					<td>'.$actual.'</td>
					<td class="text-right">'.$row["TOTAL"].'</td>
					</tr>';
				}
			}
			echo '</tbody>
			</table>
			</div>';

			//! SCR# 550 - look for shipments that failed the consolidation
			if( is_array($shipment_table->could_not_consolidate) && count($shipment_table->could_not_consolidate) > 0 ) {
				echo '<h4>The following shipments could not be consolidated because:</h4>
				';
				echo '<div class="table-responsive">
				<table class="display table table-striped table-condensed table-bordered table-hover" id="CONS">
				<thead><tr class="exspeedite-bg"><th>Shipment</th><th>Reason</th></tr>
				</thead>
				<tbody>';
				foreach( $shipment_table->could_not_consolidate as $row ) {	//! Could not consolidate
					echo '<tr><td><a style="font-size: 24px;" href="exp_addshipment.php?CODE='.$row[0].'">'.$row[0].'&nbsp;/&nbsp;'.$row[1].
					'</a>&nbsp;<a style="margin-bottom: 8px;" class="btn btn-xs btn-info" id="Billing_'.$row["0"].'" href="exp_clientpay.php?id='.$row["0"].'"><span class="glyphicon glyphicon-th-list"></span> Billing</a></td><td>'.$row[2].'</td>';
				}
			echo '</tbody>
			</table>
			</div>';
				
			}
			
			echo '</div>';
			
?>

	<script language="JavaScript" type="text/javascript"><!--
		
		$(document).ready( function () {

			$('#SHIP, #CONS').dataTable({
		        //"bLengthChange": false,
		        "bFilter": false,
		        "bSort": false,
		        "bInfo": false,
				"bAutoWidth": false,
				//"bProcessing": true, 
				"sScrollX": "100%",
				"sScrollY": "250px",
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
		} else {
			//! No possibles, so move forward
			if( ! $sts_debug ) {
				//! SCR# 358 - fix passing of correct STATE
				reload_page ( 'exp_shipment_state.php?BILLING=true&PW=Soyo&STATE='.$_GET['STATE'].'&CODE='.
					$_GET['CODE'].'&CSTATE='.$_GET['CSTATE'].'&REFERER='.base64_encode($referer).'&CONTINUE' );
			}
		}
		
	} else {	//! NOT consolidation of shipments
		if( $sts_debug ) echo "<p>shipment_state: NOT consolidation</p>";

		$result = $shipment_table->change_state_behavior( $_GET['CODE'], $_GET['STATE'],
			$is_billing, isset($_GET['CSTATE']) ? $_GET['CSTATE'] : -1 );
		if( $sts_debug ) echo "<p>result = ".($result ? 'true' : 'false '.$shipment_table->error())."</p>";
		
		if( ! $result ) {
			$sts_subtitle = "Change Shipment State";
			define('_STS_SKIP_THIS', 1 );
			require_once( "include/header_inc.php" );
			require_once( "include/navbar_inc.php" );
	
			echo '<div class="container theme-showcase" role="main">
	
			<div class="well  well-md">
			<h3 class="text-danger"><span class="glyphicon glyphicon-warning-sign"></span> Unable to change Shipment #'.$_GET['CODE'].' to '.$_GET['STATE'].' state</h3>
			
			<p>'.$shipment_table->state_change_error.'</p>
			
			<p><a class="btn btn-md btn-default" href="exp_addshipment.php?CODE='.$_GET['CODE'].'"><span class="glyphicon glyphicon-edit"></span> Edit Shipment #'.$_GET['CODE'].'</a>
			<a class="btn btn-md btn-default" href="exp_clientpay.php?id='.$_GET['CODE'].'"><span class="glyphicon glyphicon-th-list"></span> Shipment Billing For #'.$_GET['CODE'].'</a>
			<a class="btn btn-md btn-default" href="exp_listshipment.php"><span class="glyphicon glyphicon-arrow-up"></span> List Shipments</a>
			</p>
			
			</div>
			
			</div>' ;
			$save_error = $shipment_table->error();
			//! SCR# 362 - don't send email unless needed
			if( $sts_debug ) echo '<p>exp_shipmentstate: Error diag_level = '.$email->diag_level().' level = '.$load_table->state_change_level.'</p>';
			if( $email->diag_level() >= $shipment_table->state_change_level ) {
				$email->send_alert('exp_shipment_state: Unable to change Shipment #'.$_GET['CODE'].' to '.$_GET['STATE'].' state<br>'.$shipment_table->state_change_error.
				'<br>'.$save_error.
				'<br>'.$email->shipment_history($_GET['CODE']), $shipment_table->state_change_level );
			}
	
			require_once( "include/footer_inc.php" );
				
		} else { //! Post change interaction
			
			//! Possibly send invoice by email
			if( $is_billing && $_GET['STATE'] == "approved" &&
				$email->enabled() ) {
				
				$sts_subtitle = "Email invoice";
				require_once( "include/header_inc.php" );
				require_once( "include/navbar_inc.php" );

				echo '<div class="container theme-showcase" role="main">
	
				<div class="well  well-md">
				<form role="form" class="form-horizontal" action="exp_shipment_state.php" 
						method="post" enctype="multipart/form-data" 
						name="invoice" id="invoice">
				'.(isset($_GET['debug']) ? '<input name="debug" id="debug" type="hidden" value="true">
' : '').'
				<input name="CODE" id="CODE" type="hidden" value="'.$_GET['CODE'].'">
				<input name="STATE" id="STATE" type="hidden" value="'.$_GET['STATE'].'">
				<input name="REFERER" id="REFERER" type="hidden" value="'.$referer.'">
				<h2><img src="images/order_icon.png" alt="order_icon" height="24"> Shipment '.$email->reference( $_GET['CODE'], 'shipment').' Send invoice</h2>
				';
				$to = $email->shipment_invoice_recipient( $_GET['CODE'] );
				if( ! $email->send_invoices() ) {
					//! SCR# 293 - Add print button here
					echo '<p>Emailing of invoices disabled at this point (email/EMAIL_SEND_INVOICES = false)</p>
					<div class="form-group">
					<div class="btn-group col-sm-8">
						<a class="btn btn-md btn-success" name="print" id="print" onclick="window.open(\'exp_viewinvoice.php?CODE='.$_GET['CODE'].'&PRINT\', \'newwindow\', \'width=\'+ ($(window).width()*2/3) + \',height=\' + ($(window).height()*2/3)); return false;"><span class="glyphicon glyphicon-print"></span> Print (in a new window)</a>
						<button class="btn btn-md btn-default" name="cancel" id="cancel" type="submit" formnovalidate><span class="glyphicon glyphicon-remove"></span> Continue</button>
					</div>
				</div>
				';
				} else if( $to == false ) {
					echo '<p class="text-warning"><span class="glyphicon glyphicon-warning-sign"></span> Bill-to client does not have an e-mail address.</p>

<div class="form-group">
					<div class="btn-group col-sm-8">
						<button class="btn btn-md btn-default" name="cancel" id="cancel" type="submit" formnovalidate><span class="glyphicon glyphicon-remove"></span> Continue</button>
					</div>
					</div>
				</div>';
				} else {
					//! SCR# 293 - Add print button here
					echo '<p>To: '.$to.(empty($email->email_invoice_cc) ? '' : '<br>Cc: '.$email->email_invoice_cc).'</p>
					<p>Subject: '.$email->email_invoice_subject.$email->reference( $_GET['CODE'], 'shipment').'</p>
					<div class="form-group">
					<div class="btn-group col-sm-8">
						<a class="btn btn-md btn-success" name="print" id="print" onclick="window.open(\'exp_viewinvoice.php?CODE='.$_GET['CODE'].'&PRINT\', \'newwindow\', \'width=\'+ ($(window).width()*2/3) + \',height=\' + ($(window).height()*2/3)); return false;"><span class="glyphicon glyphicon-print"></span> Print (in a new window)</a>
						<button class="btn btn-md btn-success" name="sendemail" id="sendemail" type="submit" ><span class="glyphicon glyphicon-envelope"></span> Send Email (& continue)</button>
						<button class="btn btn-md btn-default" name="cancel" id="cancel" type="submit" formnovalidate><span class="glyphicon glyphicon-remove"></span> Don\'t send (& continue)</button>
					</div>
				</div>
				';
				}
			} else {
				
				if( ! $sts_debug ) {
					//! Add a delay so I can see any error messages.
					if( $_SESSION['EXT_USERNAME'] == 'duncan' ) sleep(5);
					
					if( $is_billing && $_GET['STATE'] == 'unapproved' ) {
						reload_page ( "exp_addshipment.php?CODE=".$_GET['CODE'] );	// Back to add shipment page
					} else if( ! $is_billing && isset($referer) && $referer <> 'unknown' ) {
						reload_page ( $referer );	// Back to referring page
					} else {
						reload_page ( "exp_listshipment.php" );	// Back to list shipments page
					}
				}
			}
		}
	}
	if( $sts_debug ) echo "<p>exp_shipment_state: end</p>";
} else {
	if( $sts_debug ) {
		echo "<p>_POST = </p>
		<pre>";
		var_dump($_POST);
		echo "</pre>";
	}
	if( isset($_POST['sendemail']) && ! empty($_POST['CODE'])) { //! Send email
		$email_type = 'invoice';
		$email_code = $_POST['CODE'];
		require_once( "exp_spawn_send_email.php" );		// Background send
	}

	if( ! $sts_debug ) {		
		if( $_POST['STATE'] == 'approved' ) {
			reload_page ( "exp_addshipment.php?CODE=".$_POST['CODE'] );	// Back to add shipment page
		} else if( isset($_POST['REFERER']) && $_POST['REFERER'] <> 'unknown' ) {
			reload_page ( $_POST['REFERER'] );	// Back to referring page
		} else {
			reload_page ( "exp_listshipment.php" );	// Back to list shipments page
		}
	}
}

?>
