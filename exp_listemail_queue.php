<?php 
	
// $Id: exp_listemail_queue.php 5030 2023-04-12 20:31:34Z duncan $
// List queued/sent emails.

// Set flag that this is a parent file
define( '_STS_INCLUDE', 1 );

// Setup Session
require_once( "include/sts_session_setup.php" );

require_once( "include/sts_config.php" );
$sts_debug = isset($_GET['debug']) && in_group( EXT_GROUP_DEBUG );
require_once( "include/sts_session_class.php" );

$my_session = sts_session::getInstance( $exspeedite_db, $sts_debug );
$my_session->access_check( EXT_GROUP_ADMIN );	// Make sure we should be here

$sts_subtitle = "List Email Queue";
require_once( "include/header_inc.php" );
require_once( "include/navbar_inc.php" );

?>
<div class="container-full" role="main">
<?php
require_once( "include/sts_result_class.php" );
require_once( "include/sts_email_class.php" );
require_once( "include/sts_setting_class.php" );

$setting_table = sts_setting::getInstance($exspeedite_db, $sts_debug);
$multi_company = ($setting_table->get("option", "MULTI_COMPANY") == 'true');

if( !$multi_company ) {
	unset($sts_result_full_queue_layout['REFERENCE']);
}

$queue = sts_email_queue::getInstance($exspeedite_db, $sts_debug);

if( ! isset($_SESSION['EQ_SOURCE_TYPE']) ) $_SESSION['EQ_SOURCE_TYPE'] = 'All';
if( isset($_POST['EQ_SOURCE_TYPE']) ) $_SESSION['EQ_SOURCE_TYPE'] = $_POST['EQ_SOURCE_TYPE'];

if( isset($_GET['errors'])) $_SESSION['EQ_EMAIL_STATUS'] = 'errors';
else if( ! isset($_SESSION['EQ_EMAIL_STATUS']) ) $_SESSION['EQ_EMAIL_STATUS'] = 'All';
if( isset($_POST['EQ_EMAIL_STATUS']) ) $_SESSION['EQ_EMAIL_STATUS'] = $_POST['EQ_EMAIL_STATUS'];

$match = false;
if( $_SESSION['EQ_SOURCE_TYPE'] <> 'All')
	$match = "SOURCE_TYPE = '".$_SESSION['EQ_SOURCE_TYPE']."'";

if(  $_SESSION['EQ_EMAIL_STATUS'] == 'errors' )
	$match = ($match <> '' ? $match." AND " : "")."EMAIL_STATUS IN ('pdf-error', 'send-error')";
else if( $_SESSION['EQ_EMAIL_STATUS'] <> 'All')
	$match = ($match <> '' ? $match." AND " : "")."EMAIL_STATUS = '".$_SESSION['EQ_EMAIL_STATUS']."'";

$filters_html = '<div class="btn-group"><a class="btn btn-sm btn-success" href="exp_listemail_queue.php"><span class="glyphicon glyphicon-refresh"></span></a>';

// menus here

$valid_sources = $queue->get_enum_choices( 'SOURCE_TYPE' );

if( $valid_sources ) {
	$filters_html .= '<select class="form-control input-sm" name="EQ_SOURCE_TYPE" id="EQ_SOURCE_TYPE"   onchange="form.submit();">';
	$filters_html .= '<option value="All"';
	if( $_SESSION['EQ_SOURCE_TYPE'] == 'All' )
		$filters_html .= ' selected';
	$filters_html .= '>All Sources</option>
	';

	foreach( $valid_sources as $source ) {
		$filters_html .= '<option value="'.$source.'" '.($_SESSION['EQ_SOURCE_TYPE'] == $source ? 'selected' : '').'>'.$source.'</option>
		';
	}
	$filters_html .= '</select>';
}

$valid_sources = $queue->get_enum_choices( 'EMAIL_STATUS' );

if( $valid_sources ) {
	$filters_html .= '<select class="form-control input-sm" name="EQ_EMAIL_STATUS" id="EQ_EMAIL_STATUS"   onchange="form.submit();">';
	$filters_html .= '<option value="All"';
	if( $_SESSION['EQ_EMAIL_STATUS'] == 'All' )
		$filters_html .= ' selected';
	$filters_html .= '>All Statuses</option>
	';
	$filters_html .= '<option value="errors"';
	if( $_SESSION['EQ_EMAIL_STATUS'] == 'errors' )
		$filters_html .= ' selected';
	$filters_html .= '>All Errors</option>
	';

	foreach( $valid_sources as $source ) {
		$filters_html .= '<option value="'.$source.'" '.($_SESSION['EQ_EMAIL_STATUS'] == $source ? 'selected' : '').'>'.$source.'</option>
		';
	}
	$filters_html .= '</select>';
}

//! SCR# 825 - Resend all queued messages
$filters_html .= '<a href="exp_resend_email.php?ALLERRORS" class="btn btn-sm btn-danger tip" title="This will work in the background to resend emails. Check the log or click refresh for progress."><span class="glyphicon glyphicon-warning-sign"></span> Resend All Errors</a>';

$filters_html .= '</div>';
$sts_result_queue_edit['filters_html'] = $filters_html;

$check = $queue->fetch_rows("", "MAX(CREATED_DATE) CREATED_DATE");
if( is_array($check) && isset($check[0]["CREATED_DATE"])) {
	$sts_result_queue_edit["title"] .= ' as of '.date("m/d H:i", strtotime($check[0]["CREATED_DATE"]));
}

$rslt = new sts_result( $queue, false, $sts_debug );
echo $rslt->render( $sts_result_full_queue_layout, $sts_result_queue_edit, false, false );

?>
</div>

	<script language="JavaScript" type="text/javascript"><!--
		
		$(document).ready( function () {
			//alert(($(window).height() - 150) + "px");
			<?php if( ! $sts_debug ) { ?>
			document.documentElement.style.overflow = 'hidden';  // firefox, chrome
			document.body.scroll = "no"; // ie only
			<?php } ?>

			$('#EXP_EMAIL_QUEUE').dataTable({
		        //"bLengthChange": false,
		        "bFilter": false,
		        "bSort": true,
		        "bInfo": false,
				"bAutoWidth": false,
				//"bProcessing": true, 
				"sScrollX": "100%",
				"sScrollY": ($(window).height() - 276) + "px",
				//"sScrollXInner": "120%",
		        "lengthMenu": [<?php echo isset($sts_length_menu) ? $sts_length_menu : '25, 50, 100, 250'; ?>],
				"bPaginate": true,
				"bScrollCollapse": false,
				"bSortClasses": false,
				"order": [[ 1, "desc" ]],
				"processing": true,
				"serverSide": true,
				//"dom": "frtiS",
				"deferRender": true,
				"ajax": {
					"url": "exp_listemail_queueajax.php",
					"data": function( d ) {
						d.match = encodeURIComponent("<?php echo $match; ?>");
					}
				},
				"columns": [
					{ "searchable": false, "orderable": false },
					<?php
						foreach( $sts_result_full_queue_layout as $key => $row ) {
							if( $row["format"] <> 'hidden')
								echo '{ "data": "'.$key.'", "searchable": '.
								(isset($row["searchable"]) && ! $row["searchable"] ? 'false' : 'true' ).
								(isset($row["align"]) ? ', "className": "text-'.$row["align"].'"' : '').
									(isset($row["length"]) ? ', "width": "'.$row["length"].'px"' : '').
									(isset($row["format"]) && $row["format"] == 'hidden' ? ', "visible": false' : '').' },
						';
						}
					?>
				],
			});
			
			if( window.HANDLE_RESIZE_EVENTS ) {
				$(window).bind('resize', function(e) {
					console.log('resize event triggered');
					if (window.RT) clearTimeout(window.RT);
					window.RT = setTimeout(function() {
						this.location.reload(false); /* false to get page from cache */
					}, 100);
				});
			}
			
		});
	//--></script>

<?php

require_once( "include/footer_inc.php" );
?>

