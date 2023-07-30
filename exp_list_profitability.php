<?php 

// $Id: exp_list_profitability.php 4350 2021-03-02 19:14:52Z duncan $
// KPI - profitability by office

// Set flag that this is a parent file
define( '_STS_INCLUDE', 1 );

// Setup Session
require_once( "include/sts_session_setup.php" );

require_once( "include/sts_config.php" );
$sts_debug = isset($_GET['debug']) && in_group( EXT_GROUP_DEBUG );
require_once( "include/sts_session_class.php" );

$my_session = sts_session::getInstance( $exspeedite_db, $sts_debug );
$my_session->access_report( 'Profitability' );	// Make sure we should be here

$sts_subtitle = "Profitability By Office";
require_once( "include/header_inc.php" );
require_once( "include/navbar_inc.php" );

// Turn off output buffering
ini_set('output_buffering', 'off');
// Implicitly flush the buffer(s)
ini_set('implicit_flush', true);
ob_implicit_flush(true);

if (ob_get_level() == 0) ob_start();

?>
<div class="container-full" role="main">
<?php
require_once( "include/sts_setting_class.php" );


$setting_table = sts_setting::getInstance($exspeedite_db, $sts_debug);

// If not enabled, exit to home page
$kpi_profit_enabled = $setting_table->get("option", "KPI_PROFIT") == 'true';
if( ! $kpi_profit_enabled )
	reload_page( "index.php" );
$multi_company = $setting_table->get("option", "MULTI_COMPANY") == 'true';

echo '<form class="form-inline" role="form" action="exp_list_profitability.php" 
					method="post" enctype="multipart/form-data" 
					name="RESULT_FILTERS_PROFIT" id="RESULT_FILTERS_PROFIT">
	<h3><span class="glyphicon glyphicon-usd"></span> Profitability By Office <div class="form-group">';

if( ! isset($_SESSION['PERIOD']) ) $_SESSION['PERIOD'] = 'thisw';

if( isset($_POST['PERIOD']) ) $_SESSION['PERIOD'] = $_POST['PERIOD'];

$curMonth = date("m", time());
$curQuarter = ceil($curMonth/3);
$cq = 'Q'.$curQuarter.date(" Y");
if( $curQuarter > 1 )
	$lq = 'Q'.($curQuarter-1).date(" Y");
else
	$lq = 'Q4'.date(" Y", strtotime("-1 year +1 day"));

$valid_periods = array(	'thisw' => 'This Week ('.date("\wW o").')',
						'lastw' => 'Last Week ('.date("\wW o", strtotime("-1 week +1 day")).')', 
						'thism' => 'This Month ('.date("M Y").' - 4x slow)',
						'lastm' => 'Last Month ('.date("M Y", strtotime("-1 month +1 day")).' - 4x slow)', 
						'thisq' => 'This Quarter ('.$cq.' - 12x slower)',
						'lastq' => 'Last Quarter ('.$lq.' - 12x slower)' );
	
echo '<select class="form-control input-sm" name="PERIOD" id="PERIOD"   onchange="form.submit();">';
foreach( $valid_periods as $value => $label ) {
	echo '<option value="'.$value.'" '.($_SESSION['PERIOD'] == $value ? 'selected' : '').'>'.$label.'</option>
	';
}
echo '</select>';
	
echo '<a class="btn btn-sm btn-success" href="exp_list_profitability.php"><span class="glyphicon glyphicon-refresh"></span></a><a class="btn btn-sm btn-default" href="index.php"><span class="glyphicon glyphicon-arrow-left"></span> Back</a></div></h3></form>
<div id="loading"><center><h2 class="text-center"><img src="images/loading.gif" alt="loading" width="125" height="125" /><br>Preparing Report...</h2></center></div>';
ob_flush(); flush();

		
//$period = "YEAR(L.COMPLETED_DATE) = 2017";
if( $_SESSION['PERIOD'] == 'thisw')
	$period = "YEARWEEK(L.COMPLETED_DATE) = YEARWEEK(CURDATE())";
else if( $_SESSION['PERIOD'] == 'lastw')
	$period = "YEARWEEK(L.COMPLETED_DATE) = YEARWEEK(CURDATE() - INTERVAL 1 WEEK)";
else if( $_SESSION['PERIOD'] == 'thism')
	$period = "YEAR(L.COMPLETED_DATE) = YEAR(CURDATE())
	AND MONTH(L.COMPLETED_DATE) = MONTH(CURDATE())";
else if( $_SESSION['PERIOD'] == 'lastm')
	$period = "((MONTH(CURDATE()) > 1
	AND YEAR(L.COMPLETED_DATE) = YEAR(CURDATE())
	AND MONTH(L.COMPLETED_DATE) = MONTH(CURDATE()) -1)
OR (MONTH(CURDATE()) = 1
	AND YEAR(L.COMPLETED_DATE) = YEAR(CURDATE()) - 1
	AND MONTH(L.COMPLETED_DATE) = 12))";
else if( $_SESSION['PERIOD'] == 'thisq')
	$period = "YEAR(L.COMPLETED_DATE) = YEAR(CURDATE())
	AND QUARTER(L.COMPLETED_DATE) = QUARTER(CURDATE())";
else if( $_SESSION['PERIOD'] == 'lastq')
	$period = "((QUARTER(CURDATE()) > 1
	AND YEAR(L.COMPLETED_DATE) = YEAR(CURDATE())
	AND QUARTER(L.COMPLETED_DATE) = QUARTER(CURDATE()) -1)
OR (QUARTER(CURDATE()) = 1
	AND YEAR(L.COMPLETED_DATE) = YEAR(CURDATE()) - 1
	AND QUARTER(L.COMPLETED_DATE) = 4))";

if( $multi_company ) {
	$result = $exspeedite_db->get_multiple_rows("SELECT COMPANY_CODE, OFFICE_CODE, COMPANY_NAME,
		OFFICE_NAME, HOME_CURRENCY, NUM_LOADS, LOADS,
		LOAD_REVENUE, LOAD_EXPENSE,
		ROUND(LOAD_REVENUE/NUM_LOADS, 2) REVENUE_PER_LOAD,
		ROUND(LOAD_EXPENSE/NUM_LOADS, 2) EXPENSE_PER_LOAD,
		ROUND((LOAD_REVENUE - LOAD_EXPENSE), 2) MARGIN,
		ROUND((LOAD_REVENUE - LOAD_EXPENSE) * 100.0/LOAD_REVENUE, 0) MARGINP,
		ROUND((LOAD_REVENUE - LOAD_EXPENSE)/NUM_LOADS, 2) MARGIN_PER_LOAD
		FROM (
		SELECT O.COMPANY_CODE, O.OFFICE_CODE, C.COMPANY_NAME, O.OFFICE_NAME, C.HOME_CURRENCY,
		COUNT(L.LOAD_CODE) NUM_LOADS,
		GROUP_CONCAT(L.LOAD_CODE ORDER BY L.LOAD_CODE ASC SEPARATOR ', ') LOADS,
		ROUND(SUM(LOAD_REVENUE(L.LOAD_CODE)),2) LOAD_REVENUE,
		ROUND(SUM(LOAD_EXPENSE(L.LOAD_CODE)),2) LOAD_EXPENSE
		
		FROM EXP_OFFICE O, EXP_COMPANY C,
			EXP_LOAD L, exp_status_codes st
		WHERE O.OFFICE_CODE = L.OFFICE_CODE
		AND O.COMPANY_CODE = C.COMPANY_CODE
		AND ".$period."

		and L.current_status = st.STATUS_CODES_CODE
		and st.SOURCE_TYPE = 'load'
		and st.behavior <> 'cancel'
		
		GROUP BY O.OFFICE_CODE, C.COMPANY_NAME, O.OFFICE_NAME, C.HOME_CURRENCY
		ORDER BY 4 DESC) X
		ORDER BY COMPANY_CODE ASC, OFFICE_CODE ASC");
} else {
	//! SCR# 500 - Fix SQL for single company clients
	$result = $exspeedite_db->get_multiple_rows("SELECT 0 AS OFFICE_CODE, '' AS OFFICE_NAME, NUM_LOADS, LOADS,
		LOAD_REVENUE, LOAD_EXPENSE,
		ROUND(LOAD_REVENUE/NUM_LOADS, 2) REVENUE_PER_LOAD,
		ROUND(LOAD_EXPENSE/NUM_LOADS, 2) EXPENSE_PER_LOAD,
		ROUND((LOAD_REVENUE - LOAD_EXPENSE), 2) MARGIN,
		ROUND((LOAD_REVENUE - LOAD_EXPENSE) * 100.0/LOAD_REVENUE, 0) MARGINP,
		ROUND((LOAD_REVENUE - LOAD_EXPENSE)/NUM_LOADS, 2) MARGIN_PER_LOAD
		FROM (
		SELECT COUNT(L.LOAD_CODE) NUM_LOADS,
		GROUP_CONCAT(L.LOAD_CODE ORDER BY L.LOAD_CODE ASC SEPARATOR ', ') LOADS,
		ROUND(SUM(LOAD_REVENUE(L.LOAD_CODE)),2) LOAD_REVENUE,
		ROUND(SUM(LOAD_EXPENSE(L.LOAD_CODE)),2) LOAD_EXPENSE
		
		FROM EXP_LOAD L, exp_status_codes st
		WHERE ".$period."
		and L.current_status = st.STATUS_CODES_CODE
		and st.SOURCE_TYPE = 'load'
		and st.behavior <> 'cancel'
		
		ORDER BY 1 DESC) X");
}

if( is_array($result) && count($result) > 0 ) {
	$tbl = '<div class="table-responsive  bg-white">
	<table class="display table table-striped table-condensed table-bordered table-hover table-nobm" id="PROFIT">
	<thead><tr>
		'.($multi_company ? '<th colspan="2" style="border: 0px;">&nbsp;</th>
		' : '').'<th class="exspeedite-bg text-center h4" colspan="5">Total</th>
		<th class="exspeedite-bg text-center h4" colspan="3">Per Load</th>
		</tr>
		<tr class="exspeedite-bg">
	'.($multi_company ? '<th>Company/Office</th>
	<th>Curr</th>
	' : '').'<th class="text-right">Loads</th>
	<th class="text-right">Revenue</th>
	<th class="text-right">Expense</th>
	<th class="text-right">Margin</th>
	<th class="text-right">%</th>
	<th class="text-right">Revenue</th>
	<th class="text-right">Expense</th>
	<th class="text-right">Margin</th>
	</tr>
	</thead>
	<tbody>';

	foreach($result as $row) {
		$loads = explode(', ', $row['LOADS']);
		$linked = array();
		foreach( $loads as $load) {
			$linked[] = '<a href="exp_viewload.php?CODE='.$load.'">'.$load.'</a>';
		}
		
		$tbl .= '<tr>
				'.($multi_company ? '<td><a href="exp_editcompany.php?CODE='.$row['COMPANY_CODE'].'">'.$row['COMPANY_NAME'].'</a><br>
				&nbsp;&nbsp;&nbsp;<a href="exp_editoffice.php?CODE='.$row['OFFICE_CODE'].'">'.$row['OFFICE_NAME'].'</a></td>
				<td>'.$row['HOME_CURRENCY'].'</td>
				' : '').'<td class="text-right"><a data-toggle="collapse" href="#collapse'.$row['OFFICE_CODE'].'" aria-expanded="false" aria-controls="collapse'.$row['OFFICE_CODE'].'">'.$row['NUM_LOADS'].'</a></td>
				<td class="text-right">'.$row['LOAD_REVENUE'].'</td>
				<td class="text-right">'.$row['LOAD_EXPENSE'].'</td>
				<td class="text-right">'.$row['MARGIN'].'</td>
				<td class="text-right">'.$row['MARGINP'].'</td>
				<td class="text-right">'.$row['REVENUE_PER_LOAD'].'</td>
				<td class="text-right">'.$row['EXPENSE_PER_LOAD'].'</td>
				<td class="text-right">'.$row['MARGIN_PER_LOAD'].'</td>
			</tr>';
		$tbl = '<div class="alert alert-info collapse" id="collapse'.$row['OFFICE_CODE'].'"><button type="button" class="close" data-toggle="collapse" href="#collapse'.$row['OFFICE_CODE'].'" aria-label="Close"><span aria-hidden="true">&times;</span></button><strong>'.$row['OFFICE_NAME'].' Loads:</strong> '.
			implode(', ', $linked).'</div>'.$tbl;
	}

	update_message( 'loading', '' );
	echo $tbl.'
   		</tbody>
		</table>
	</div>';
} else {
	update_message( 'loading', '' );
	echo '<br>
	<p><span class="glyphicon glyphicon-remove"></span> No data available for the selected period.</p>';
}

		
?>
</div>

	<script language="JavaScript" type="text/javascript"><!--
		
		$(document).ready( function () {
			//alert(($(window).height() - 150) + "px");
			<?php if( ! $sts_debug ) { ?>
			document.documentElement.style.overflow = 'hidden';  // firefox, chrome
			document.body.scroll = "no"; // ie only
			<?php } ?>

			$('#PROFIT').dataTable({
		        //"bLengthChange": false,
		        "bFilter": false,
		        "bSort": true,
		        "bInfo": true,
				//"bAutoWidth": false,
				//"bProcessing": true, 
				"sScrollX": "100%",
				"sScrollY": ($(window).height() - 260) + "px",
				"sScrollXInner": "100%",
		        //"lengthMenu": [<?php echo isset($sts_length_menu) ? $sts_length_menu : '25, 50, 100, 250'; ?>],
				"bPaginate": false,
				"bScrollCollapse": false,
				"bSortClasses": false		
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
