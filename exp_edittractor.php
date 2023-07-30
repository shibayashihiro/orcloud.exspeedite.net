<?php 

// $Id: exp_edittractor.php 5157 2023-07-12 18:25:57Z dev $
// Edit Tractor

// Set flag that this is a parent file
define( '_STS_INCLUDE', 1 );

// Setup Session
require_once( "include/sts_session_setup.php" );

require_once( "include/sts_config.php" );
$sts_debug = (isset($_GET['debug'])  || isset($_POST['debug'])) && in_group( EXT_GROUP_DEBUG );
require_once( "include/sts_session_class.php" );

$my_session = sts_session::getInstance( $exspeedite_db, $sts_debug );
$my_session->access_check( $sts_table_access[TRACTOR_TABLE], EXT_GROUP_MECHANIC );	// Make sure we should be here

require_once( "include/sts_result_class.php" );
require_once( "include/sts_form_class.php" );
require_once( "include/sts_tractor_class.php" );
require_once( "include/sts_setting_class.php" );
require_once( "include/sts_ifta_log_class.php" );
require_once( "include/sts_attachment_class.php" );
require_once( "include/sts_insp_report_class.php" );
require_once( "include/sts_rm_form_class.php" );

$setting_table = sts_setting::getInstance($exspeedite_db, $sts_debug);
$sts_attachments = $setting_table->get( 'option', 'ATTACHMENTS_ENABLED' ) == 'true';
$multi_company = $setting_table->get("option", "MULTI_COMPANY") == 'true';
$ifta_enabled = $setting_table->get("api", "IFTA_ENABLED") == 'true';
$fleet_enabled = $setting_table->get( 'option', 'FLEET_ENABLED' ) == 'true';
$inspection_reports = $setting_table->get( 'option', 'INSPECTION_REPORTS' ) == 'true';
$insp_title = $setting_table->get( 'option', 'INSPECTION_REPORT_TITLE' );
$kt_api_key = $setting_table->get( 'api', 'KEEPTRUCKIN_KEY' );
$kt_enabled = ! empty($kt_api_key);

//! SCR# 647 - R&M Reports - show extra fields
$extra_fields = $setting_table->get( 'option', 'RM_REPORT_EXTRA_FIELDS' ) == 'true';
$layout = $extra_fields ? $sts_result_insp_report_extra_layout :
	$sts_result_insp_report_all_layout;

if( $inspection_reports && isset($_GET) &&
	isset($_GET["CODE"]) && isset($_GET["EXPORTRM"]) ) {
		
	require_once( "include/sts_csv_class.php" );

	$insp_table = sts_insp_report::getInstance($exspeedite_db, $sts_debug);

	$match = "(UNIT = ".$_GET['CODE']." AND UNIT_TYPE = 'tractor') OR
		REPORT_CODE IN (SELECT REPORT_CODE FROM EXP_INSP_REPORT_ITEM WHERE ITEM_TYPE='tractor'
		AND TRACTOR = ".$_GET['CODE'].")";

	$csv = new sts_csv($insp_table, $match, $sts_debug);
	
	$csv->header( "Exspeedite_RM_tractor" );

	$csv->render( $layout );

	die;
}

$sts_subtitle = "Edit Tractor";
require_once( "include/header_inc.php" );
require_once( "include/navbar_inc.php" );

?>
<div class="container-full theme-showcase" role="main">

<div class="well  well-lg">
<?php

if( ! isset($_GET['CODE']) && ! isset($_POST["TRACTOR_CODE"]) ) {
	echo '<h2>Tractor # is missing or invalid</h2>
	<h3>Please report this to Exspeedite support, including what happened before this step.</h3>
	<h3><a class="btn btn-success" href="exp_listtractor.php">Click here</a> return to list of tractors.</h3>';
	require_once( "include/sts_email_class.php" );
	$email = sts_email::getInstance($exspeedite_db, $sts_debug);
	$email->send_alert('Tractor # is missing or invalid', EXT_ERROR_WARNING);
} else {

if( ! $fleet_enabled ) {
	unset($sts_form_edit_tractor_fields['FLEET_CODE']);
	$match = preg_quote('<!-- FL01 -->').'(.*)'.preg_quote('<!-- FL02 -->');
	$sts_form_edittractor_form['layout'] = preg_replace('/'.$match.'/s', '',
		$sts_form_edittractor_form['layout'], 1);	
}

$tractor_table = sts_tractor::getInstance($exspeedite_db, $sts_debug);
$rm_form_table = sts_rm_form::getInstance($exspeedite_db, $sts_debug);

if( ! $multi_company ) {
	$match = preg_quote('<!-- CC01 -->').'(.*)'.preg_quote('<!-- CC02 -->');
	$sts_form_edittractor_form['layout'] = preg_replace('/'.$match.'/s', '',
		$sts_form_edittractor_form['layout'], 1);	
}

if( ! $ifta_enabled ) {
	$match = preg_quote('<!-- IFTA01 -->').'(.*)'.preg_quote('<!-- IFTA02 -->');
	$sts_form_edittractor_form['layout'] = preg_replace('/'.$match.'/s', '',
		$sts_form_edittractor_form['layout'], 1);	
}

//! SCR# 280 - Restrict certain fields
$edit_driver_restrictions = ($setting_table->get("option", "EDIT_DRIVER_RESTRICTIONS_ENABLED") == 'true');
if( $edit_driver_restrictions && ! in_group( EXT_GROUP_HR ) ) {
	$sts_form_edit_tractor_fields['INSPECTION_EXPIRY']['extras'] = 'readonly';
	$sts_form_edit_tractor_fields['INSURANCE_EXPIRY']['extras'] = 'readonly';
}

//! If not LOG_IFTA, remove IFTA log button
$check = $tractor_table->fetch_rows($tractor_table->primary_key." = ".(isset($_GET['CODE']) ? $_GET['CODE'] : $_POST["TRACTOR_CODE"]), "LOG_IFTA");

if( (is_array($check) && count($check) == 1 &&
	isset($check[0]["LOG_IFTA"]) && ! $check[0]["LOG_IFTA"]) ||
	! $ifta_enabled  ) {
	unset($sts_form_edittractor_form['buttons']);
	$tractor_form = new sts_form($sts_form_edittractor_form,
		$sts_form_edit_tractor_fields, $tractor_table, $sts_debug);
}

if( $kt_enabled ) {
	$b = array( 'label' => 'Reports', 'link' => 'exp_kt_inspection_reports.php?pw=WestMinster&code=%TRACTOR_CODE%',
		'button' => 'default', 'icon' => '<img src="images/keeptruckin2.png" alt="keeptruckin" height="18">' );
	if( isset($sts_form_edittractor_form['buttons']) &&
		is_array($sts_form_edittractor_form['buttons']) )
		$sts_form_edittractor_form['buttons'][] = $b;
	else
		$sts_form_edittractor_form['buttons'] = array($b);
}

$tractor_form = new sts_form($sts_form_edittractor_form, $sts_form_edit_tractor_fields, $tractor_table, $sts_debug);

if( isset($_POST) && count($_POST) > 0 ) {		// Process completed form
	
	//! Update IFTA log country if change tractor country
	//! SCR# 170 - COMPANY_CODE no longer in EXP_IFTA_LOG, removed old code
	
	$result = $tractor_form->process_edit_form();
	
	if( $result ) {
		if( $sts_debug ) die; // So we can see the results
		if( isset($_POST["saveadd"]) )
			reload_page ( "exp_addtractor.php" );
	}
}

if( isset($value) && is_array($value) && $result == false ) {	// If error occured
	echo "<p><strong>Error:</strong> ".$tractor_table->error()."</p>";
	echo $tractor_form->render( $value );
} else if( isset($_GET['CODE']) ) {
	$result = $tractor_table->fetch_rows($tractor_table->primary_key." = ".$_GET['CODE']);
	
	echo $tractor_form->render( $result[0] );
}

//! SCR# 193 - add attachments to tractor and trailer
//! SCR# 506 - let mechanic have access to inspection reports again
if( isset($_GET['CODE']) ) {
?>
<!-- Nav tabs -->
<ul class="nav nav-tabs">
<?php if( $sts_attachments ) { ?>
  <li class="active"><a href="#attach" role="tab" data-toggle="tab">Attachments</a></li>
<?php } ?>
<?php if( in_group(EXT_GROUP_MECHANIC) || ($inspection_reports && in_group(EXT_GROUP_FLEET)) ) { ?>
  <li><a href="#insp" role="tab" data-toggle="tab"><?php echo $insp_title; ?>s</a></li>
  <li><a href="#rm_summary" role="tab" data-toggle="tab">R&M Summary</a></li>
<?php } ?>
</ul>

<!-- Tab panes -->
<div class="tab-content">
<?php if( $sts_attachments ) { ?>
	<div role="tabpanel" class="tab-pane active" id="attach">
<?php

	$attachment_table = sts_attachment::getInstance($exspeedite_db, $sts_debug);
	$rslt2 = new sts_result( $attachment_table, "SOURCE_CODE = ".$_GET['CODE']." AND SOURCE_TYPE = 'tractor'", $sts_debug );
	echo $rslt2->render( $sts_result_attachment_layout, $sts_result_attachment_edit, '?SOURCE_TYPE=tractor&SOURCE_CODE='.$_GET['CODE'] );
?>
	</div>
<?php }

//! SCR# 472 - switch from admin to fleet group for viewing inspection reports
if( in_group(EXT_GROUP_MECHANIC) || ( $inspection_reports && in_group(EXT_GROUP_FLEET)) ) { ?>
	<div role="tabpanel" class="tab-pane" id="insp">
<?php

$filters = '<div class="form-group">';
//! SCR# 472 - hide add button if not in inspection group
if( in_group(EXT_GROUP_INSPECTION, EXT_GROUP_MECHANIC) ) {
	$filters .= $rm_form_table->render_form_menu( 'tractor', $_GET['CODE'] );
}

$sts_result_insp_report_edit['title'] = '<span class="glyphicon glyphicon-wrench"></span> '.$insp_title.'s';

$filters .= ' <a class="btn btn-sm btn-default" href="exp_email_3month_insp_report.php?UNIT_TYPE=tractor&CODE='.$_GET['CODE'].'"><span class="glyphicon glyphicon-envelope"></span> Email 3 months</a>';

$filters .= ' <a class="btn btn-sm btn-primary tip" title="Export to CSV" href="exp_edittractor.php?CODE='.$_GET['CODE'].'&EXPORTRM"><span class="glyphicon glyphicon-th-list"></span></a>';


$filters .= '</div>';

$sts_result_insp_report_edit['filters_html'] = $filters;

$match = "(UNIT = ".$_GET['CODE']." AND UNIT_TYPE = 'tractor') OR
	REPORT_CODE IN (SELECT REPORT_CODE FROM EXP_INSP_REPORT_ITEM WHERE ITEM_TYPE='tractor'
	AND TRACTOR = ".$_GET['CODE'].")";

	$insp_table = sts_insp_report::getInstance($exspeedite_db, $sts_debug);
	$rslt3 = new sts_result( $insp_table, $match, $sts_debug );
	echo $rslt3->render( $layout, $sts_result_insp_report_edit, '?TYPE=tractor&UNIT='.$_GET['CODE'] );
?>
	</div>
	<div role="tabpanel" class="tab-pane" id="rm_summary">
<?php		
	echo $insp_table->render_rm_report( 'tractor', $_GET['CODE'])
	
?>	
	</div>
<?php } ?>
</div>

<?php } ?>
</div>
</div>

	<div class="modal fade bs-example-modal-lg" tabindex="-1" role="dialog" aria-labelledby="mySmallModalLabel" aria-hidden="true" id="kt_reports_modal">
	  <div class="modal-dialog modal-lg">
		<div class="modal-content">
		<div class="modal-header">
			 <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
			<h4 class="modal-title" id="myModalLabel"><span class="text-success"><strong><img src="images/keeptruckin.png" alt="keeptruckin" height="40"> Inspection Reports</strong></span></h4>
		</div>
		<div class="modal-body">
			<p class="text-center"><img src="images/loading.gif" alt="loading" width="125" height="125" /></p>
		</div>
		</div>
		</div>
	</div>

	<script language="JavaScript" type="text/javascript"><!--
		$(document).ready( function () {
			var tractor = <?php echo $_GET['CODE']; ?>;

			function inspection_reports() {
				$('#kt_reports_modal').modal({
					container: 'body'
				});
				$('#kt_reports_modal .modal-body').html('<p class="text-center"><img src="images/loading.gif" alt="loading" width="125" height="125" /></p>');
				var url = 'exp_kt_inspection_reports.php?pw=WestMinster&code='+tractor;
				
				$.get( url, function( data ) {
					$('#kt_reports_modal .modal-body').html( data );
					$('#KT_REPORTS').dataTable({
				        //"bLengthChange": false,
				        "bFilter": false,
				        stateSave: true,
				        "bSort": false,
				        "bInfo": true,
						"bAutoWidth": false,
						//"bProcessing": true, 
						"sScrollX": "100%",
						"sScrollY": ($(window).height() - 275) + "px",
						//"sScrollXInner": "150%",
						"bPaginate": false,
						"bScrollCollapse": true,
						"bSortClasses": false,
						//"order": [[ 1, "desc" ]],
						//"dom": "frtiS",
					});				
				});

			};

			$.fn.dataTable.moment( 'MM/D/YYYY' );
			
			$('#EXP_INSP_REPORT').dataTable({
		        //"bLengthChange": false,
		        "bFilter": false,
		        stateSave: true,
		        "bSort": true,	//! SCR# 579
		        "bInfo": true,
				"bAutoWidth": false,
				//"bProcessing": true, 
				"sScrollX": "100%",
				"sScrollY": "400px",
				<?php if( $extra_fields ) { ?>
				"sScrollXInner": "150%",
				<?php } ?>
				"bPaginate": false,
				"bScrollCollapse": false,
				"bSortClasses": false,
				"order": [[ 1, "desc" ]],
				//"dom": "frtiS",
			});				

			$('#Reports').click(function(event) {
				event.preventDefault();
				inspection_reports();
			});

			function update_oos() {
				if( $('select#ACTIVE').val() == 'OOS' )
					$('#OOS').prop('hidden',false);
				else
					$('#OOS').prop('hidden', 'hidden');
			}

			function check_tractor() {
				var url = 'exp_check_asset.php?code=Headphone&tractor='+tractor;
				
				$.get( url, function( data ) {
					$( "#warnings" ).html( data );
				});
			}
			
			$('select#ACTIVE').on('change', function(event) {
				update_oos();
			});
			
			update_oos();
			check_tractor();
		
			function update_fuel_cards() {
				var url = "exp_driver_card.php?PW=Society&TRACTOR=" + tractor;
				$("#FUEL_CARDS").load(url, function() {
					$('a.delcard').on('click', function(event) {
						event.preventDefault();  //prevent form from submitting
						$.get($(this).attr('href'), function(data) {
							update_fuel_cards();
						});
					});
				});

			}
			
			update_fuel_cards();

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
						
			//console.log('is_touch_device ', is_touch_device());
			//console.log('HANDLE_RESIZE_EVENTS ', window.HANDLE_RESIZE_EVENTS);
			//console.log('navigator.platform ', navigator.platform);
			//$('.nav-tabs').prepend('<p>is_touch_device: '+is_touch_device()+'</p>');
			//$('.nav-tabs').prepend('<p>navigator.platform: '+navigator.platform+'</p>');
			
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
}
require_once( "include/footer_inc.php" );
?>

