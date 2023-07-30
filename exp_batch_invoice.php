<?php 

// $Id: exp_batch_invoice.php 5030 2023-04-12 20:31:34Z duncan $
//! SCR# 789 - Batch Invoices

// Set flag that this is a parent file
define( '_STS_INCLUDE', 1 );

// Setup Session
require_once( "include/sts_session_setup.php" );

require_once( "include/sts_config.php" );
$sts_debug = (isset($_GET['debug'])  || isset($_POST['debug'])) && in_group( EXT_GROUP_DEBUG );
//$sts_debug = true;
require_once( "include/sts_session_class.php" );

$my_session = sts_session::getInstance( $exspeedite_db, $sts_debug );
$my_session->access_check( EXT_GROUP_FINANCE );	// Make sure we should be here

if( ! isset($_POST) || !isset($_POST["getpdf"])) {
	$sts_subtitle = "Send Batch Invoices";
	require_once( "include/header_inc.php" );
	require_once( "include/navbar_inc.php" );
}

require_once( "include/sts_batch_invoice_class.php" );
$batch = sts_batch_invoice::getInstance($exspeedite_db, $sts_debug);

if( ! isset($_POST['CLIENT_CODE']) ) {	//! Select client
	echo '<div class="container" role="main">
	<form class="form-inline" role="form" action="exp_batch_invoice.php" 
		method="post" enctype="multipart/form-data" 
		name="BATCH_INVOICE" id="BATCH_INVOICE">
		'.( $sts_debug ? '<input name="debug" type="hidden" value="on">' : '').'
	<h2>'.$sts_subtitle.' <a class="btn btn-lg btn-default" id="BATCH_INVOICE_CANCEL" href="exp_listshipment.php"><span class="glyphicon glyphicon-remove"></span> Return to Shipments</a> <a class="btn btn-lg btn-success" id="BATCH_INVOICE_REFRESH" href="exp_batch_invoice.php"><span class="glyphicon glyphicon-refresh"></span> Reset</a></h2>
	<h3>Client: '.$batch->client_menu().'</h3>
	</form>
	</div>
	';
} else if( ! isset($_POST['INVOICE_DATE']) ) {	//! Select date
	$today = date("m/d/Y");
	echo '<div class="container" role="main">
	<form class="form-inline" role="form" action="exp_batch_invoice.php" 
		method="post" enctype="multipart/form-data" 
		name="BATCH_INVOICE" id="BATCH_INVOICE">
		'.( $sts_debug ? '<input name="debug" type="hidden" value="on">' : '').'
	<input name="CLIENT_CODE" type="hidden" value="'.$_POST['CLIENT_CODE'].'">
	<h2>'.$sts_subtitle.' <a class="btn btn-lg btn-default" id="BATCH_INVOICE_CANCEL" href="exp_listshipment.php"><span class="glyphicon glyphicon-remove"></span> Return to Shipments</a> <a class="btn btn-lg btn-success" id="BATCH_INVOICE_REFRESH" href="exp_batch_invoice.php"><span class="glyphicon glyphicon-refresh"></span> Reset</a></h2>
	<h3>Client: '.$batch->client_name( $_POST['CLIENT_CODE'] ).'</h3>
	<h3>Invoice Date: <input type="date" name="INVOICE_DATE" value="'.$today.'" onchange="form.submit();"></h3>
	</form>
	</div>
	';
} else if( ! isset($_POST['SEND_INVOICES']) ) {	//! Select / Send Invoices
	echo '<div class="container" role="main">
	<form class="form-inline" role="form" action="exp_batch_invoice.php" 
		method="post" enctype="multipart/form-data" 
		name="BATCH_INVOICE" id="BATCH_INVOICE">
		'.( $sts_debug ? '<input name="debug" type="hidden" value="on">' : '').'
	<input name="CLIENT_CODE" type="hidden" value="'.$_POST['CLIENT_CODE'].'">
	<input name="INVOICE_DATE" type="hidden" value="'.$_POST['INVOICE_DATE'].'">
	<h2>'.$sts_subtitle.' <a class="btn btn-lg btn-default" id="BATCH_INVOICE_CANCEL" href="exp_listshipment.php"><span class="glyphicon glyphicon-remove"></span> Return to Shipments</a> <a class="btn btn-lg btn-success" id="BATCH_INVOICE_REFRESH" href="exp_batch_invoice.php"><span class="glyphicon glyphicon-refresh"></span> Reset</a></h2>
	<h3>Client: '.$batch->client_name( $_POST['CLIENT_CODE'] ).'</h3>
	<h3>Invoice Date: '.date("m/d/Y", strtotime($_POST['INVOICE_DATE'])).'
	<button class="btn btn-lg btn-default" id="clearall"><span class="glyphicon glyphicon-remove"></span> Clear All</button>
	<button class="btn btn-lg btn-default" id="setall"><span class="glyphicon glyphicon-ok"></span> Select All</button>
	</h3>
	'.$batch->list_invoices($_POST['CLIENT_CODE'], $_POST['INVOICE_DATE']).'
	
	'.($batch->missing_attachments() > 0 ? '<h4><span class="h2 text-danger"><span class="glyphicon glyphicon-remove"></span></span> means there is no attached invoice (attachment type = '.$batch->attachment_type().') to send. Update the shipment and hit refresh below.</h4>' : '').'

	'.($batch->attachment_too_big() > 0 ? '<h4><span class="h2 text-danger"><span class="glyphicon glyphicon-warning-sign"></span></span> '.$batch->attachment_too_big().' attachments are too big to send.</h4>' : '').'

	'.($batch->available_to_send() > 0 ? '' : '<h4><span class="h2 text-danger"><span class="glyphicon glyphicon-warning-sign"></span></span> No available invoices to send for this date.</h4>').'

	<h4><span class="glyphicon glyphicon-info-sign"></span> Maximum size per email is '.$batch->max_email_size().'MB. Batch may be split into multiple emails.</h4>
	
	'.($batch->available_to_send() > 0 && ! empty($batch->email_cc()) ? '<h4><span class="glyphicon glyphicon-info-sign"></span> CC: will be sent to '.$batch->email_cc().'</h4>' : '<h4><span class="glyphicon glyphicon-info-sign"></span> No CC: configured - update setting email/EMAIL_INVOICE_CC.</h4>').'

	<h3><button class="btn btn-lg btn-danger" name="SEND_INVOICES" id="SEND_INVOICES" type="submit"'.
	($batch->available_to_send() > 0 ? '' : ' disabled')
	.'><span class="glyphicon glyphicon-envelope"></span> Send '.$batch->available_to_send().' Invoices</button> <button class="btn btn-lg btn-success" name="REFRESH" type="submit"><span class="glyphicon glyphicon-refresh"></span> Refresh</button></h3>
	
	</form>
	</div>
	
	<script language="JavaScript" type="text/javascript"><!--
		$(document).ready( function () {
			
			$(\'#INVOICES\').dataTable({
		        //"bLengthChange": false,
		        "bSort": true,
		        "bInfo": false,
				//"bAutoWidth": false,
				//"bProcessing": true, 
			//	"sScrollX": "100%",
			//	"sScrollY": ($(window).height() - 260) + "px",
			//	"sScrollXInner": "150%",
			//	"bPaginate": true,
				"bScrollCollapse": false,
				"bSortClasses": false,
				"order": [[ 1, "asc" ]],
			});

			function update_button() {
				var num = $(\'input[id^="selected"]:checked\').length;
				$(\'#SEND_INVOICES\').html(\'<span class="glyphicon glyphicon-envelope"></span> Send \' + num + \' Invoices\');
				if( num == 0 ) {
					$(\'#SEND_INVOICES\').prop(\'disabled\', \'disabled\');
				} else {
					$(\'#SEND_INVOICES\').prop(\'disabled\', false );
				}
			}
			
			$(\'input[id^="selected"]\').on(\'change\', function(event) {
				update_button();
			});

			$(\'#setall\').on(\'click\', function(event) {
				event.preventDefault();
				$(\'input[id^="selected"]\').prop(\'checked\',\'checked\').change();
			});
			
			$(\'#clearall\').on(\'click\', function(event) {
				event.preventDefault();
				$(\'input[id^="selected"]\').prop(\'checked\', false).change();
			});
		});
	//--></script>

	';
} else {	//! Send batch
	if( is_array($_POST["selected"]) && count($_POST["selected"]) > 0 )
		$shipments_selected = array_keys($_POST["selected"]);
		
	$batch->prepare_batch( $_POST['CLIENT_CODE'], $_POST['INVOICE_DATE'], $shipments_selected );
	
	// Turn off output buffering
	ini_set('output_buffering', 'off');
	// Implicitly flush the buffer(s)
	ini_set('implicit_flush', true);
	ob_implicit_flush(true);

	if (ob_get_level() == 0) ob_start();
	
	echo '<div class="container" role="main">
	<div id="loading"><h3 class="text-center"><img src="images/loading.gif" alt="loading" width="125" height="125" /><br>Sending Batch Invoices...</h3></div>
	';
	ob_flush(); flush();
	sleep(2);
	
	update_message( 'loading', '' );

	echo '<h2>'.$sts_subtitle.' ... Complete</h2>
	<br>
	<h3><span class="glyphicon glyphicon-info-sign"></span> You can view the status of emails in the <a href="exp_listemail_queue.php">email queue (Admin->Setup->Email Queue)</a><br>or look on the <a href="exp_editclient.php?CODE='.$_POST['CLIENT_CODE'].'">client page of the Bill-to client</a> (click on th Email tab at the bottom).</h3>
	<br>	
	<h2><a class="btn btn-lg btn-default" id="BATCH_INVOICE_CANCEL" href="exp_listshipment.php"><span class="glyphicon glyphicon-remove"></span> Return to Shipments</a> <a class="btn btn-lg btn-success" id="BATCH_INVOICE_REFRESH" href="exp_batch_invoice.php"><span class="glyphicon glyphicon-arrow-right"></span> Next</a></h2>
	';

}



if( ! isset($_POST) || !isset($_POST["getpdf"]))
	require_once( "include/footer_inc.php" );
?>
