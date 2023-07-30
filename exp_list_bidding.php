<?php 

// $Id: exp_list_bidding.php 5030 2023-04-12 20:31:34Z duncan $
//! SCR# 887 - Bidding Report

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

function get_match() {
	global $multi_company;
	$match = "";
	
		
	if( $_SESSION['BIDDING_RANGE'] == '1mo' )
		$match = ($match <> '' ? $match." AND " : "")."COMPLETED_DATE > '".date("Y-m-d", strtotime("-1 months"))."'";
	else if( $_SESSION['BIDDING_RANGE'] == '2mo' )
		$match = ($match <> '' ? $match." AND " : "")."COMPLETED_DATE > '".date("Y-m-d", strtotime("-2 months"))."'";
	else if( $_SESSION['BIDDING_RANGE'] == '6mo' )
		$match = ($match <> '' ? $match." AND " : "")."COMPLETED_DATE > '".date("Y-m-d", strtotime("-6 months"))."'";
	else if( $_SESSION['BIDDING_RANGE'] == '12mo' )
		$match = ($match <> '' ? $match." AND " : "")."COMPLETED_DATE > '".date("Y-m-d", strtotime("-12 months"))."'";

	return $match;
}

require_once( "include/sts_csv_class.php" );
require_once( "include/sts_carrier_class.php" );
require_once( "include/sts_setting_class.php" );

$setting_table = sts_setting::getInstance($exspeedite_db, $sts_debug);
$sts_length_menu = $setting_table->get( 'option', 'LENGTH_MENU' );

$sts_states = load_states();

if( isset($_GET) && isset($_GET["EXPORT"])) {
	$bidding_table = sts_bidding::getInstance($exspeedite_db, $sts_debug);
	$csv = new sts_csv($bidding_table, get_match(), $sts_debug);
	
	$csv->header( "Bidding_Report" );

	$csv->render( $sts_result_bidding_layout, false, $sts_result_bidding_edit['sort'] );

	die;
}

$sts_subtitle = "Bidding Report";
require_once( "include/header_inc.php" );
require_once( "include/navbar_inc.php" );

?>
<div class="container-full" role="main">
<?php
require_once( "include/sts_result_class.php" );
require_once( "include/sts_carrier_class.php" );
require_once( "include/sts_office_class.php" );

$bidding_table = sts_bidding::getInstance($exspeedite_db, $sts_debug);

// Refresh button
$filters_html = '<a class="btn btn-sm btn-success tip" title="refresh" href="exp_list_bidding.php"><span class="glyphicon glyphicon-refresh"></span></a>';

// CSV Export
$filters_html .= ' <a class="btn btn-sm btn-primary tip" title="Export to CSV" href="exp_list_bidding.php?EXPORT"><span class="glyphicon glyphicon-th-list"></span></a>';


//! Filter on Office

$reset = false;

//! Filter on Pickup date range
//if( $reset ) $_SESSION['BIDDING_RANGE'] = '1mo';
//else {
	if( ! isset($_SESSION['BIDDING_RANGE']) ) $_SESSION['BIDDING_RANGE'] = '1mo';
	if( isset($_POST['BIDDING_RANGE']) ) $_SESSION['BIDDING_RANGE'] = $_POST['BIDDING_RANGE'];
//}

$valid_ranges = array(	'1mo' => 'Last month',
						'2mo' => 'Last 2 months', 
						'6mo' => 'Last 6 months', 
						'12mo' => 'Last 12 months' );

$filters_html .= '<select class="form-control input-sm" style="width: 120px;" name="BIDDING_RANGE" id="BIDDING_RANGE"   onchange="form.submit();">';

foreach( $valid_ranges as $value => $label ) {
	$filters_html .= '<option value="'.$value.'" '.($_SESSION['BIDDING_RANGE'] == $value ? 'selected' : '').'>'.$label.'</option>
	';
}
$filters_html .= '</select>';

// Reload button
$filters_html .= '<button class="btn btn-sm btn-success tip" title="Hint: click Select All twice to clear states" type="submit" id="btn-submit"><span class="glyphicon glyphicon-search"></span></button>';
//$filters_html .= '<button class="btn btn-sm btn-success tip" title="Load data based upon filter" id="btn-reload"><span class="glyphicon glyphicon-search"></span></button>';

$filters_html .= '</div>';

$sts_result_bidding_edit['filters_html'] = $filters_html;

$rslt = new sts_result( $bidding_table, get_match(), $sts_debug );
	
echo $rslt->render( $sts_result_bidding_layout, $sts_result_bidding_edit, false );

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
			$('#BIDDING_PICKUP_STATE').multiselect({
				includeSelectAllOption: true
			});
			$('#BIDDING_DELIVER_STATE').multiselect({
				includeSelectAllOption: true
			});
			
			var mytable = $('#EXP_CARRIER').dataTable({
		        //"bLengthChange": false,
		        "bFilter": true,
		        //stateSave: true,
		        "bSort": true,
		        "bInfo": true,
				"bAutoWidth": false,
				//"bProcessing": true, 
				"sScrollX": "100%",
				"sScrollY": ($(window).height() - 280) + "px",
				"sScrollXInner": "120%",
		        "lengthMenu": [<?php echo isset($sts_length_menu) ? $sts_length_menu : '25, 50, 100, 250'; ?>],
				"bPaginate": true,
				"bScrollCollapse": false,
				"bSortClasses": false,	
			//	"processing": true,
			//	"serverSide": true,
				//"deferRender": true,
				//"deferLoading": 0,
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

