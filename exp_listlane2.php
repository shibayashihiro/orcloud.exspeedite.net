<?php 

// $Id: exp_listlane2.php 5090 2023-04-27 18:23:45Z duncan $
//! SCR# 535 - list lanes 2

// Set flag that this is a parent file
define( '_STS_INCLUDE', 1 );
define( '_STS_MULTI_SELECT', 1 );		//! SCR# 539 - include Bootstrap multi-select

// Setup Session
require_once( "include/sts_session_setup.php" );

require_once( "include/sts_config.php" );
$sts_debug = isset($_GET['debug']) && in_group( EXT_GROUP_DEBUG );
require_once( "include/sts_session_class.php" );

$my_session = sts_session::getInstance( $exspeedite_db, $sts_debug );
$my_session->access_report( 'Lane Report II' );	// Make sure we should be here

function load_states() {
	global $exspeedite_db;
	
	$states_table = new sts_table($exspeedite_db, STATES_TABLE, false );
	$states = array();
	
	foreach( $states_table->fetch_rows() as $row ) {
		$states[$row['abbrev']] = $row['STATE_NAME'];
	}
	
	return $states;
}

function get_match( $pd = 'all' ) {
	global $multi_company;
	$match = "";
	
	if( $multi_company ) {
		if( $_SESSION['LANE_OFFICE'] == 'all' )
			$match = ($match <> '' ? $match." AND " : "")."s.OFFICE_CODE IN (".implode(', ', array_keys($_SESSION['EXT_USER_OFFICES'])).")";
		else
			$match = ($match <> '' ? $match." AND " : "")."s.OFFICE_CODE = ".$_SESSION['LANE_OFFICE'];
	}
	
	if( $_SESSION['LANE_RANGE'] == '1mo' )
		$match = ($match <> '' ? $match." AND " : "")."s.PICKUP_DATE > '".date("Y-m-d", strtotime("-1 months"))."'";
	else if( $_SESSION['LANE_RANGE'] == '2mo' )
		$match = ($match <> '' ? $match." AND " : "")."s.PICKUP_DATE > '".date("Y-m-d", strtotime("-2 months"))."'";
	else if( $_SESSION['LANE_RANGE'] == '6mo' )
		$match = ($match <> '' ? $match." AND " : "")."s.PICKUP_DATE > '".date("Y-m-d", strtotime("-6 months"))."'";
	else if( $_SESSION['LANE_RANGE'] == '12mo' )
		$match = ($match <> '' ? $match." AND " : "")."s.PICKUP_DATE > '".date("Y-m-d", strtotime("-12 months"))."'";

	if( $pd != 'client' ) {
		if( $_SESSION['LANE_CLIENT'] != 'all' )
			$match = ($match <> '' ? $match." AND " : "")."c.CLIENT_CODE = ".$_SESSION['LANE_CLIENT'];
	
		if( $pd != 'pickup' ) {
			if( is_array($_SESSION['LANE_PICKUP_STATE']) && count($_SESSION['LANE_PICKUP_STATE']) > 0 ) {
				$match = ($match <> '' ? $match." AND " : "")."s.SHIPPER_STATE IN ('".implode("', '", $_SESSION['LANE_PICKUP_STATE'])."')";
			}
		
			if( $pd != 'deliver' ) {
				if( is_array($_SESSION['LANE_DELIVER_STATE']) && count($_SESSION['LANE_DELIVER_STATE']) > 0 ) {
					$match = ($match <> '' ? $match." AND " : "")."s.CONS_STATE IN ('".implode("', '", $_SESSION['LANE_DELIVER_STATE'])."')";
				}
			}
		}
	}

	return $match;
}

require_once( "include/sts_csv_class.php" );
require_once( "include/sts_lane_class.php" );
require_once( "include/sts_setting_class.php" );

$setting_table = sts_setting::getInstance($exspeedite_db, $sts_debug);
$sts_length_menu = $setting_table->get( 'option', 'LENGTH_MENU' );
$multi_company = $setting_table->get("option", "MULTI_COMPANY") == 'true';

$sts_states = load_states();

if( isset($_GET) && isset($_GET["EXPORT"])) {
	$lane_table = sts_lane::getInstance($exspeedite_db, $sts_debug);
	$csv = new sts_csv($lane_table, get_match(), $sts_debug);
	$lane_table->nolink();
	
	$csv->header( "Exspeedite_lane" );
	if( ! $multi_company )
		unset($sts_result_lane_layout['SS_NUMBER']);
	else
		$sts_result_lane_layout['SHIPMENT_CODE']['format'] = 'hidden';

	$csv->render( $sts_result_lane_layout );

	die;
}

$sts_subtitle = "Lane Report II";
require_once( "include/header_inc.php" );
require_once( "include/navbar_inc.php" );

?>
<div class="container-full" role="main">
<?php
require_once( "include/sts_result_class.php" );
require_once( "include/sts_lane_class.php" );
require_once( "include/sts_office_class.php" );

$lane_table = sts_lane::getInstance($exspeedite_db, $sts_debug);

// Refresh button
$filters_html = '<a class="btn btn-sm btn-success tip" title="refresh" href="exp_listlane2.php"><span class="glyphicon glyphicon-refresh"></span></a>';

// CSV Export
$filters_html .= ' <a class="btn btn-sm btn-primary tip" title="Export to CSV" href="exp_listlane2.php?EXPORT"><span class="glyphicon glyphicon-th-list"></span></a>';


//! Filter on Office

$reset = false;
if( $multi_company ) {
	if( ! isset($_SESSION['LANE_OFFICE']) ) $_SESSION['LANE_OFFICE'] = $_SESSION['EXT_USER_OFFICE'];
	if( isset($_POST['LANE_OFFICE']) ) {
		// If changing office, reset other filters.
		if( $_POST['LANE_OFFICE'] <> $_SESSION['LANE_OFFICE'] ) {
			$reset = true;
		}
		$_SESSION['LANE_OFFICE'] = $_POST['LANE_OFFICE'];
	}
}

if( $multi_company && count($_SESSION['EXT_USER_OFFICES']) > 1 ) {
	$filters_html .= '<select class="form-control input-sm" style="width: 150px;" name="LANE_OFFICE" id="LANE_OFFICE"   onchange="form.submit();">';
	$filters_html .= '<option value="all" '.($_SESSION['LANE_OFFICE'] == 'all' ? 'selected' : '').'>All Offices</option>
		';
	foreach( $_SESSION['EXT_USER_OFFICES'] as $value => $label ) {
		$filters_html .= '<option value="'.$value.'" '.($_SESSION['LANE_OFFICE'] == $value ? 'selected' : '').'>'.$label.'</option>
		';
	}
	$filters_html .= '</select>';
}

//! Filter on Pickup date range
//if( $reset ) $_SESSION['LANE_RANGE'] = '1mo';
//else {
	if( ! isset($_SESSION['LANE_RANGE']) ) $_SESSION['LANE_RANGE'] = '1mo';
	if( isset($_POST['LANE_RANGE']) ) $_SESSION['LANE_RANGE'] = $_POST['LANE_RANGE'];
//}

$valid_ranges = array(	'1mo' => 'Last month',
						'2mo' => 'Last 2 months', 
						'6mo' => 'Last 6 months', 
						'12mo' => 'Last 12 months', 
						'all' => 'All data (slow)');

$filters_html .= '<select class="form-control input-sm" style="width: 120px;" name="LANE_RANGE" id="LANE_RANGE"   onchange="form.submit();">';
foreach( $valid_ranges as $value => $label ) {
	$filters_html .= '<option value="'.$value.'" '.($_SESSION['LANE_RANGE'] == $value ? 'selected' : '').'>'.$label.'</option>
	';
}
$filters_html .= '</select>';

//! Filter on client
if( $reset ) $_SESSION['LANE_CLIENT'] = 'all';
else {
	if( ! isset($_SESSION['LANE_CLIENT']) ) $_SESSION['LANE_CLIENT'] = 'all';
	if( isset($_POST['LANE_CLIENT']) ) $_SESSION['LANE_CLIENT'] = $_POST['LANE_CLIENT'];
}

$filters_html .= '<select class="form-control input-sm" style="width: 120px;" name="LANE_CLIENT" id="LANE_CLIENT"   onchange="form.submit();">';
$filters_html .= '<option value="all" '.($_SESSION['LANE_CLIENT'] == 'all' ? 'selected' : '').'>All Clients</option>
	';
$clients = $lane_table->get_clients( get_match('client') );
if( is_array($clients) && count($clients) > 0 ) {
	foreach( $clients as $value => $label ) {
		$filters_html .= '<option value="'.$value.'" '.($_SESSION['LANE_CLIENT'] == $value ? 'selected' : '').'>'.$label.'</option>
		';
	}
}
$filters_html .= '</select>';
	//echo "<pre>";
	//var_dump($_POST);
	//echo "</pre>";

//! Filter on pickup
//! SCR# 539 - use multi-select plugin
//! SCR# 541 - clear the selection if $_POST['LANE_PICKUP_STATE'] not set
if( ! isset($_SESSION['LANE_PICKUP_STATE']) ) $_SESSION['LANE_PICKUP_STATE'] = array();
if( isset($_POST['LANE_PICKUP_STATE']) ) $_SESSION['LANE_PICKUP_STATE'] = $_POST['LANE_PICKUP_STATE'];
else $_SESSION['LANE_PICKUP_STATE'] = array();

$filters_html .= '<select class="form-control input-sm" style="width: 120px;" name="LANE_PICKUP_STATE[]" id="LANE_PICKUP_STATE" multiple="multiple">';
if( is_array($sts_states) && count($sts_states) > 0 ) {
	foreach( $sts_states as $value => $label ) {
		$filters_html .= '<option value="'.$value.'" '.(in_array($value, $_SESSION['LANE_PICKUP_STATE']) ? 'selected="selected"' : '').'>'.$value.' - '.$label.'</option>
		';
	}
}
$filters_html .= '</select>';

//! Filter on deliver
//! SCR# 541 - clear the selection if $_POST['LANE_PICKUP_STATE'] not set
if( $reset ) $_SESSION['LANE_DELIVER_STATE'] = array();
else {
	if( ! isset($_SESSION['LANE_DELIVER_STATE']) ) $_SESSION['LANE_DELIVER_STATE'] = array();
	if( isset($_POST['LANE_DELIVER_STATE']) ) $_SESSION['LANE_DELIVER_STATE'] = $_POST['LANE_DELIVER_STATE'];
	else $_SESSION['LANE_DELIVER_STATE'] = array();
}

$filters_html .= '<select class="form-control input-sm" style="width: 120px;" name="LANE_DELIVER_STATE[]" id="LANE_DELIVER_STATE" multiple="multiple">';
if( is_array($sts_states) && count($sts_states) > 0 ) {
	foreach( $sts_states as $value => $label ) {
		$filters_html .= '<option value="'.$value.'" '.(in_array($value, $_SESSION['LANE_DELIVER_STATE']) ? 'selected' : '').'>'.$value.' - '.$label.'</option>
		';
	}
}
$filters_html .= '</select>';

// Reload button
$filters_html .= '<button class="btn btn-sm btn-success tip" title="Hint: click Select All twice to clear states" type="submit" id="btn-submit"><span class="glyphicon glyphicon-search"></span></button>';
//$filters_html .= '<button class="btn btn-sm btn-success tip" title="Load data based upon filter" id="btn-reload"><span class="glyphicon glyphicon-search"></span></button>';

$filters_html .= '</div>';

$sts_result_lane_edit['filters_html'] = $filters_html;
$sts_result_lane_edit['title'] .= ' II';

$rslt = new sts_result( $lane_table, get_match(), $sts_debug );
if( ! $multi_company )
	unset($sts_result_lane_layout['SS_NUMBER']);
else
	$sts_result_lane_layout['SHIPMENT_CODE']['format'] = 'hidden';
	
echo $rslt->render( $sts_result_lane_layout, $sts_result_lane_edit, false, false );

?>
</div>

	<script language="JavaScript" type="text/javascript"><!--
		
		$(document).ready( function () {
			//alert(($(window).height() - 150) + "px");
			if( <?php echo ($sts_debug ? 'true' : 'false'); ?> == 'false' ) {
				document.documentElement.style.overflow = 'hidden';  // firefox, chrome
				document.body.scroll = "no"; // ie only
			}
			
			//! SCR# 539 - use multi-select plugin
			$('#LANE_PICKUP_STATE').multiselect({
				includeSelectAllOption: true
			});
			$('#LANE_DELIVER_STATE').multiselect({
				includeSelectAllOption: true
			});
			
			var mytable = $('#EXP_SHIPMENT').dataTable({
		        //"bLengthChange": false,
		        "bFilter": true,
		        //stateSave: true,
		        "bSort": true,
		        "bInfo": true,
				"bAutoWidth": false,
				//"bProcessing": true, 
				"sScrollX": "100%",
				"sScrollY": ($(window).height() - 270) + "px",
				"sScrollXInner": "150%",
		        "lengthMenu": [<?php echo isset($sts_length_menu) ? $sts_length_menu : '25, 50, 100, 250'; ?>],
				"bPaginate": true,
				"bScrollCollapse": false,
				"bSortClasses": false,	
				"processing": true,
				"serverSide": true,
				//"deferRender": true,
				//"deferLoading": 0,
				"ajax": {
					"url": "exp_listlaneajax.php",
					"data": function( d ) {
						d.match = encodeURIComponent("<?php echo get_match(); ?>");
					}

				},
				"columns": [
					//{ "searchable": false, "orderable": false },
					//! SCR# 541 - allow sort by column
					<?php
						foreach( $sts_result_lane_layout as $key => $row ) {
							if( $row["format"] <> 'hidden')
								echo '{ "data": "'.$key.'", "searchable": '.
								(isset($row["searchable"]) && $row["searchable"] ? 'true' : 'false').',
								 "orderable": '.(isset($row["snippet"]) ? 'false' : 'true' ).
								(isset($row["align"]) ? ',"className": "text-'.$row["align"].'"' : '').
									(isset($row["length"]) ? ', "width": "'.$row["length"].'px"' : '').
									(isset($row["format"]) && $row["format"] == 'hidden' ? ', "visible": false' : '').' },
						';
						}
					?>
				],
				"infoCallback": function( settings, start, end, max, total, pre ) {
					var api = this.api();
					//console.log(api.ajax.json());
					if( typeof api.ajax.json() !== 'undefined') {
						//console.log(api.ajax.json().timing);
						return pre + ' (' + api.ajax.json().timing + ' s)';
					}
				},
			});
			
			$('#btn-reload').on('click', function(e){
				e.preventDefault();
				//console.log(mytable);
				//console.log($('#EXP_SHIPMENT').DataTable);
				//$('#EXP_SHIPMENT').DataTable.draw();
				mytable.fnDraw();
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

