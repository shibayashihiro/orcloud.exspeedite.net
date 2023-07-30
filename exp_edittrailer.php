<?php 

// $Id: exp_edittrailer.php 4350 2021-03-02 19:14:52Z duncan $
// Edit Trailer

// Set flag that this is a parent file
define( '_STS_INCLUDE', 1 );

// Setup Session
require_once( "include/sts_session_setup.php" );

require_once( "include/sts_config.php" );
$sts_debug = (isset($_GET['debug'])  || isset($_POST['debug'])) && in_group( EXT_GROUP_DEBUG );
require_once( "include/sts_session_class.php" );
error_reporting(E_ALL);
$my_session = sts_session::getInstance( $exspeedite_db, $sts_debug );
$my_session->access_check( $sts_table_access[TRAILER_TABLE], EXT_GROUP_MECHANIC );	// Make sure we should be here

require_once( "include/sts_result_class.php" );
require_once( "include/sts_form_class.php" );
require_once( "include/sts_trailer_class.php" );
require_once( "include/sts_setting_class.php" );
require_once( "include/sts_attachment_class.php" );
require_once( "include/sts_insp_report_class.php" );
require_once( "include/sts_rm_form_class.php" );

$trailer_table = sts_trailer::getInstance($exspeedite_db, $sts_debug);
$rm_form_table = sts_rm_form::getInstance($exspeedite_db, $sts_debug);

//! Check setting option/TRAILER_TANKER_FIELDS, and if false remove tanker fields
$setting_table = sts_setting::getInstance($exspeedite_db, $sts_debug);
$sts_attachments = $setting_table->get( 'option', 'ATTACHMENTS_ENABLED' ) == 'true';
$strip_tanker_fields = ($setting_table->get( 'option', 'TRAILER_TANKER_FIELDS' ) == 'false');
$strip_im_fields = ($setting_table->get( 'option', 'TRAILER_IM_FIELDS' ) == 'false');
$inspection_reports = $setting_table->get( 'option', 'INSPECTION_REPORTS' ) == 'true';
$insp_title = $setting_table->get( 'option', 'INSPECTION_REPORT_TITLE' );

//! SCR# 647 - R&M Reports - show extra fields
$extra_fields = $setting_table->get( 'option', 'RM_REPORT_EXTRA_FIELDS' ) == 'true';
$layout = $extra_fields ? $sts_result_insp_report_extra_layout :
	$sts_result_insp_report_all_layout;

if( $inspection_reports && isset($_GET) &&
	isset($_GET["CODE"]) && isset($_GET["EXPORTRM"]) ) {
		
	require_once( "include/sts_csv_class.php" );

	$insp_table = sts_insp_report::getInstance($exspeedite_db, $sts_debug);

	$match = "(UNIT = ".$_GET['CODE']." AND UNIT_TYPE = 'trailer') OR
		REPORT_CODE IN (SELECT REPORT_CODE FROM EXP_INSP_REPORT_ITEM WHERE ITEM_TYPE='trailer'
		AND TRAILER = ".$_GET['CODE'].")";

	$csv = new sts_csv($insp_table, $match, $sts_debug);
	
	$csv->header( "Exspeedite_RM_trailer" );

	$csv->render( $layout );

	die;
}

$sts_subtitle = "Edit Trailer";
require_once( "include/header_inc.php" );
require_once( "include/navbar_inc.php" );

if( $strip_tanker_fields ) {
	$match = preg_quote('<div id="TANKER_FIELDS"').'(.*)'.preg_quote('<!-- TANKER_FIELDS -->');
	$sts_form_edittrailer_form['layout'] = preg_replace('/'.$match.'/s', '',
		$sts_form_edittrailer_form['layout'], 1);
}

if( $strip_im_fields ) {
	$match = preg_quote('<div id="IM_FIELDS"').'(.*)'.preg_quote('<!-- IM_FIELDS -->');
	$sts_form_edittrailer_form['layout'] = preg_replace('/'.$match.'/s', '',
		$sts_form_edittrailer_form['layout'], 1);
}

//! SCR# 280 - Restrict certain fields
$edit_driver_restrictions = ($setting_table->get("option", "EDIT_DRIVER_RESTRICTIONS_ENABLED") == 'true');
if( $edit_driver_restrictions && ! in_group( EXT_GROUP_HR ) ) {
	$sts_form_edit_trailer_fields['INSPECTION_EXPIRY']['extras'] = 'readonly';
	$sts_form_edit_trailer_fields['INSURANCE_EXPIRY']['extras'] = 'readonly';
}

$trailer_form = new sts_form($sts_form_edittrailer_form, $sts_form_edit_trailer_fields, $trailer_table, $sts_debug);

if( isset($_POST) && count($_POST) > 0 ) {		// Process completed form
	$result = $trailer_form->process_edit_form();
	
	if( $result ) {
		if( $sts_debug ) die; // So we can see the results
		if( isset($_POST["saveadd"]) )
			reload_page ( "exp_addtrailer.php" );
	}
}

?>
<div class="container-full theme-showcase" role="main">

<div class="well  well-lg">
<?php

if( isset($value) && is_array($value) && $result == false ) {	// If error occured
	echo "<p><strong>Error:</strong> ".$trailer_table->error()."</p>";
	echo $trailer_form->render( $value );
} else if( isset($_GET['CODE']) ) {
	$result = $trailer_table->fetch_rows($trailer_table->primary_key." = ".$_GET['CODE']);
	echo $trailer_form->render( $result[0] );

	
}

//! SCR# 506 - let mechanic have access to inspection reports again
if( isset($_GET['CODE']) ) {
?>
<!-- Nav tabs -->
<ul class="nav nav-tabs" role="tablist">
  <li class="active"><a href="#history" role="tab" data-toggle="tab">History</a></li>
<?php if( $sts_attachments ) { ?>
  <li><a href="#attach" role="tab" data-toggle="tab">Attachments</a></li>
<?php } ?>
<?php if( in_group(EXT_GROUP_MECHANIC) || ($inspection_reports && in_group(EXT_GROUP_PROFILES)) ) { ?>
  <li><a href="#insp" role="tab" data-toggle="tab"><?php echo $insp_title; ?>s</a></li>
  <li><a href="#rm_summary" role="tab" data-toggle="tab">R&M Summary</a></li>
<?php } ?>
</ul>

<!-- Tab panes -->
<div class="tab-content">
  <div role="tabpanel" class="tab-pane active" id="history">
<?php
	require_once( "include/sts_office_class.php" );
	
	$office_table = sts_office::getInstance($exspeedite_db, $sts_debug);
	$multi_company = $office_table->multi_company();

	$match = "COALESCE(TRAILER, (SELECT TRAILER FROM EXP_LOAD 
					WHERE EXP_LOAD.LOAD_CODE = EXP_STOP.LOAD_CODE) ) =".$_GET['CODE'];

	//! SCR# 247 - restrict list to offices available
	if( $multi_company && ! in_group( EXT_GROUP_ADMIN ) ) {
		$match = ($match <> '' ? $match." AND " : "")."(SELECT OFFICE_CODE FROM EXP_LOAD 
					WHERE EXP_LOAD.LOAD_CODE = EXP_STOP.LOAD_CODE) IN (".implode(', ', array_keys($_SESSION['EXT_USER_OFFICES'])).")";
	}

	$history = new sts_trailer_history($exspeedite_db, $sts_debug);
	$rslt = new sts_result( $history, $match, $sts_debug );
	echo $rslt->render( $sts_result_trailer_history_layout, $sts_result_trailers_history_view );

if( $sts_attachments ) {
?>
  </div>
  <div role="tabpanel" class="tab-pane" id="attach">
<?php
	
	//! SCR# 193 - add attachments to tractor and trailer
	$attachment_table = sts_attachment::getInstance($exspeedite_db, $sts_debug);
	$rslt2 = new sts_result( $attachment_table, "SOURCE_CODE = ".$_GET['CODE']." AND SOURCE_TYPE = 'trailer'", $sts_debug );
	echo $rslt2->render( $sts_result_attachment_layout, $sts_result_attachment_edit, '?SOURCE_TYPE=trailer&SOURCE_CODE='.$_GET['CODE'] );
}

//! SCR# 472 - switch from admin to fleet group for viewing inspection reports
if( in_group(EXT_GROUP_MECHANIC) || ( $inspection_reports && in_group(EXT_GROUP_PROFILES)) ) { ?>
	</div>
	<div role="tabpanel" class="tab-pane" id="insp">
<?php

$filters = '<div class="form-group">';
//! SCR# 472 - hide add button if not in inspection group
if( in_group(EXT_GROUP_INSPECTION, EXT_GROUP_MECHANIC) ) {
	$filters .= $rm_form_table->render_form_menu( 'trailer', $_GET['CODE'] );
}

$sts_result_insp_report_edit['title'] = '<span class="glyphicon glyphicon-wrench"></span> '.$insp_title.'s';

$filters .= ' <a class="btn btn-sm btn-default" href="exp_email_3month_insp_report.php?UNIT_TYPE=trailer&CODE='.$_GET['CODE'].'"><span class="glyphicon glyphicon-envelope"></span> Email 3 months</a>';

$filters .= ' <a class="btn btn-sm btn-primary tip" title="Export to CSV" href="exp_edittrailer.php?CODE='.$_GET['CODE'].'&EXPORTRM"><span class="glyphicon glyphicon-th-list"></span></a>';

$filters .= '</div>';

$sts_result_insp_report_edit['filters_html'] = $filters;

$match = "(UNIT = ".$_GET['CODE']." AND UNIT_TYPE = 'trailer') OR
	REPORT_CODE IN (SELECT REPORT_CODE FROM EXP_INSP_REPORT_ITEM WHERE ITEM_TYPE='trailer'
	AND TRAILER = ".$_GET['CODE'].")";

	$insp_table = sts_insp_report::getInstance($exspeedite_db, $sts_debug);
	$rslt3 = new sts_result( $insp_table, $match, $sts_debug );
	echo $rslt3->render( $layout, $sts_result_insp_report_edit, '?TYPE=trailer&UNIT='.$_GET['CODE'] );
}

?>
	</div>
	<div role="tabpanel" class="tab-pane" id="rm_summary">
<?php		
	echo $insp_table->render_rm_report( 'trailer', $_GET['CODE'])
	
?>	
	</div>
 </div>

<?php
}
?>


</div>
</div>

	<script language="JavaScript" type="text/javascript"><!--
		$(document).ready( function () {
			var trailer = <?php echo $_GET['CODE']; ?>;

			function update_oos() {
				if( $('select#ISACTIVE').val() == 'OOS' )
					$('#OOS').prop('hidden',false);
				else
					$('#OOS').prop('hidden', 'hidden');
			}

			function check_trailer() {
				var url = 'exp_check_asset.php?code=Headphone&trailer='+trailer;
				
				$.get( url, function( data ) {
					$( "#warnings" ).html( data );
				});
			}
			
			$.fn.dataTable.moment( 'MM/D/YYYY' );
			
			$('#EXP_TRAILER').dataTable({
		        //"bLengthChange": false,
		        "bFilter": true,
		        "bSort": true,
		        order: [[0, 'desc']],
		        "bInfo": false,
				//"bAutoWidth": false,
				//"bProcessing": true, 
				//"sScrollX": "100%",
				"sScrollY": "400px",
				//"sScrollXInner": "120%",
		        //"lengthMenu": [[-1, 25, 50], ["All", 25, 50]],
				"bPaginate": false,
				//"bScrollCollapse": true,
				"bSortClasses": false		
			});

			$('#EXP_INSP_REPORT').dataTable({
		        //"bLengthChange": false,
		        "bFilter": false,
		        stateSave: true,
		        "bSort": true,	//! SCR# 579
		        "bInfo": true,
				"bAutoWidth": false,
				//"bProcessing": true, 
				"sScrollX": "100%",
				"sScrollY": "200px",
				<?php if( $extra_fields ) { ?>
				"sScrollXInner": "150%",
				<?php } ?>
				"bPaginate": false,
				"bScrollCollapse": false,
				"bSortClasses": false,
				"order": [[ 1, "desc" ]],
				//"dom": "frtiS",
			});				

			$('select#ISACTIVE').on('change', function(event) {
				update_oos();
			});
			
			update_oos();
			check_trailer();
		
			function update_location() {
				$.ajax({
					url: 'exp_asset_distance.php',
					data: {
						CODE: 'Switching',
						TRAILER: encodeURIComponent(trailer),
					},
					dataType: "json",
					success: function(data) {
						if( typeof(data.STRING) != "undefined" ) {
							$('#LAST_LOCATION').html('<input type="text" class="form-control" value="' + data.STRING + '" readonly>');
						} else {
							$('#LAST_LOCATION').html('<input type="text" class="form-control" value="Unknown" readonly>');
						}
					}
				});					
			}

			update_location();

			$('a[data-toggle="tab"]').on( 'shown.bs.tab', function (e) {
				$.fn.dataTable.tables( {visible: true, api: true} ).columns.adjust();
			} );

			$('a[data-toggle="tab"]').on("shown.bs.tab", function (e) {
			    var id = $(e.target).attr("href");
			    localStorage.setItem('ecaselectedTab', id)
			});
			
			var selectedTab = localStorage.getItem('ecaselectedTab');
			if (selectedTab != null) {
			    $('a[data-toggle="tab"][href="' + selectedTab + '"]').tab('show');
			}

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

