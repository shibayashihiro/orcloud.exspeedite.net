<?php 

// $Id: exp_margin_report.php 5135 2023-06-14 00:47:49Z dev $
// Perform Margin Report

// Set flag that this is a parent file
define( '_STS_INCLUDE', 1 );

// Setup Session
require_once( "include/sts_session_setup.php" );

require_once( "include/sts_config.php" );
set_time_limit(0);
ini_set('memory_limit', '1024M');

$sts_debug = (isset($_GET['debug'])  || isset($_POST['debug'])) && in_group( EXT_GROUP_DEBUG );
//$sts_debug = true;
require_once( "include/sts_session_class.php" );

$my_session = sts_session::getInstance( $exspeedite_db, $sts_debug );
$my_session->access_check( EXT_GROUP_FINANCE, EXT_GROUP_SALES );	// Make sure we should be here

if( ! isset($_POST) || !isset($_POST["getreport"])) {
	$sts_subtitle = "Margin Report";
	require_once( "include/header_inc.php" );
	require_once( "include/navbar_inc.php" );
}

require_once( "include/sts_margin_report_class.php" );
require_once( "include/sts_email_class.php" );

$mr = sts_margin_report::getInstance($exspeedite_db, $sts_debug);
$email = sts_email::getInstance($exspeedite_db, $sts_debug);

//! Roll up revenue and expense data.
if( $my_session->superadmin() && isset($_GET['ROLLUP']) ) {

	echo '<div class="container" role="main">
	<form class="form-horizontal" role="form" action="exp_margin_report.php" 
		method="post" enctype="multipart/form-data" 
		name="ROLLUP" id="ROLLUP">
		'.( $sts_debug ? '<input name="debug" type="hidden" value="on">' : '').'
		
	<h1 class="text-danger"><span class="glyphicon glyphicon-warning-sign"></span> Update Rollup Values</h1>
	<h4>This should hopefully be unneccesary. It updates LOAD_REVENUE and LOAD_EXPENSE columns.
	It\'s likely to take a while.</h4>

	<div class="form-group h3">
		<label class="col-sm-2" for="YEAR">Year: </label>
		<div class="col-sm-2">
		<input class="form-control input-lg text-right" type="number" name="YEAR" value="'.date("Y").'">
		</div>
	</div>
	<div class="form-group h3">
		<label class="col-sm-2" for="MONTH">To: </label>
		<div class="col-sm-2">
		<input class="form-control input-lg text-right" type="number" name="MONTH" value="'.date("m").'">
		</div>
	</div>

	<a class="btn btn-lg btn-default" href="exp_margin_report.php" name="BACK" type="submit"><span class="glyphicon glyphicon-arrow-left"></span> Back</a>
	<button class="btn btn-lg btn-danger" name="ROLLUP" type="submit"><span class="glyphicon glyphicon-warning-sign"></span> Continue</button></h3>
	</form>
	</div>
	';
	die;
} else if( $my_session->superadmin() && isset($_POST['ROLLUP']) ) {
	//! Process the rollup
	$year = $_POST["YEAR"];
	$month = $_POST["MONTH"];
	$days = cal_days_in_month(CAL_GREGORIAN, $month, $year);
	echo '<div class="container" role="main">
	<h1 class="text-danger"><span class="glyphicon glyphicon-warning-sign"></span> Update Rollup Values</h1>
	<h3>
	';
	$total_updated = 0;
	for( $day = 1; $day<=$days; $day++ ) {
		$result = $mr->database->get_one_row("SELECT COUNT(L.LOAD_CODE) NUM
			FROM EXP_LOAD L, EXP_SHIPMENT SH
			WHERE L.LOAD_CODE = SH.LOAD_CODE
			AND YEAR(SH.PICKUP_DATE) = $year
			AND MONTH(SH.PICKUP_DATE) = $month
			AND DAY(SH.PICKUP_DATE) = $day
		");
		echo '&nbsp;&nbsp;&nbsp;'.$month.'/'.$day.'/'.$year.' : '.plural( $result['NUM'], 'load' );
		ob_flush(); flush();
		
		if( $result['NUM'] > 0 ) {
			echo '<br><div id="loading'.$day.'"><h2 class="text-center"><img src="images/loading.gif" alt="loading" width="125" height="125" /><br>Updating '.$result['NUM'].' Loads...</h2></div>';
		ob_flush(); flush();
		
			$result2 = $mr->database->get_one_row("UPDATE EXP_LOAD
		        SET LOAD_REVENUE = LOAD_REVENUE_CUR( LOAD_CODE, CURRENCY ),
					LOAD_EXPENSE = LOAD_EXPENSE_CUR( LOAD_CODE, CURRENCY )
				WHERE LOAD_CODE IN (SELECT SH.LOAD_CODE
				FROM EXP_SHIPMENT SH
				WHERE YEAR(SH.PICKUP_DATE) = $year
				AND MONTH(SH.PICKUP_DATE) = $month
				AND DAY(SH.PICKUP_DATE) = $day
				AND COALESCE(SH.LOAD_CODE,0) > 0
			");
			sleep(1);
			update_message( 'loading'.$day, '' );
			ob_flush(); flush();
			$total_updated += $result['NUM'];
		}
	}
	echo '<h1>Updated '.$total_updated.' Loads <a class="btn btn-lg btn-default" id="DONE" href="exp_margin_report.php"><span class="glyphicon glyphicon-arrow-left"></span> Back</a></h1>
	</div>';
	
	die;
}

$go_back_to = 'exp_listshipment.php';

function nm0( $x ) {
	return isset($x) && is_numeric($x) && $x != 0 ? number_format($x,0):'';
}

function nm1( $x ) {
	return isset($x) && is_numeric($x) && $x != 0 ? number_format($x,1):'';
}

function curr( $x ) {
	return isset($x) && is_numeric($x) && $x != 0 ? '$'.number_format($x,2):'';
}

function currz( $x ) {
	return isset($x) && is_numeric($x) ? '$'.number_format($x,2):'';
}

function nm4( $x ) {
	return isset($x) && is_numeric($x) ? number_format($x,4):'';
}

function dt( $x ) {
	return isset($x) ? str_replace(' ', '&nbsp;', date("m/d/Y", strtotime($x))):'';
}

function en( $val ) {
	return '"'.$val.'"';
}

//! CSS to include
$my_css = '
<style>
html {
    font-family: sans-serif;
    -webkit-text-size-adjust: 100%;
}

body {
	margin: 8mm 8mm 8mm 8mm;
}

.container-full {
  margin: 0 auto;
  width: 80%;
}

td, th {
    padding: 0;
}

table {
    border-spacing: 0;
    border-collapse: collapse;
    width: 100%;
    max-width: 100%;
    margin-bottom: 0px;
}

.table-bordered {
    border: 1px solid #ddd;
	padding: 10px;
}

.table-striped > tbody > tr:nth-child(odd) > td,
.table-striped > tbody > tr:nth-child(odd) > th {
  background-color: #f9f9f9;
}

.table-condensed > thead > tr > th,
.table-condensed > tbody > tr > th,
.table-condensed > tfoot > tr > th,
.table-condensed > thead > tr > td,
.table-condensed > tbody > tr > td,
.table-condensed > tfoot > tr > td {
  padding: 2px;
  font-size: 14px;
}

.table-condensed > tbody > tr > td.br {
	border-right: 1px solid #ddd;
}

.table-bordered {
  border: 1px solid #ddd;
}
.table-bordered > thead > tr > th,
.table-bordered > tbody > tr > th,
.table-bordered > tfoot > tr > th,
.table-bordered > thead > tr > td,
.table-bordered > tbody > tr > td,
.table-bordered > tfoot > tr > td {
  border: 1px solid #ddd;
}
.table-bordered > thead > tr > th,
.table-bordered > thead > tr > td {
  border-bottom-width: 2px;
  font-size: 14px;
}

h2,
h3,
h4 {
  font-family: inherit;
  line-height: 1.1;
  color: inherit;
  margin-top: 5px;
  margin-bottom: 2px;
}

h1,
.h1,
h2,
.h2,
h3,
.h3,
h4,
.h4,
h5,
.h5 {
  margin-top: 5px;
  margin-bottom: 2px;
}

h1,
.h1 {
  font-size: 36px;
}
h2,
.h2 {
  font-size: 30px;
}
h3,
.h3 {
  font-size: 24px;
}
h4,
.h4 {
  font-size: 18px;
}

h1, .h1, h2, .h2, h3, .h3 {
    margin-top: 20px;
    margin-bottom: 10px;
}

table.border {
	width: 98%;
	margin-left: auto;
	margin-right: auto;
	border: 2px solid LightGray;
	border-spacing: 0px;
    border-collapse: collapse;
    background-color: transparent;
}

table tr {
	vertical-align: text-top;
}

table.border>tr>td {
	width: 100%;
}

table.noborder {
	width: 98%;
	margin-left: auto;
	margin-right: auto;
	border: 0px;
	border-spacing: 0px;
    border-collapse: collapse;
    background-color: transparent;
}

table.noborder thead tr {
	background-color: #4d8e31;
	color: #fff;
}

table tr th.w33,
table tr td.w33 {
	width: 33%;
	table-layout: fixed;
	padding: 5px;
}

table.noborder tr th.w50,
table.noborder tr td.w50 {
	width: 50%;
	table-layout: fixed;
	padding: 5px;
}

table tr th.w25,
table tr td.w25 {
	width: 25%;
	table-layout: fixed;
	padding: 5px;
}

table.noborder tr th.w15,
table.noborder tr td.w15 {
	width: 15%;
	table-layout: fixed;
	padding: 5px;
}

table.noborder tr th.w10,
table.noborder tr td.w10 {
	width: 10%;
	table-layout: fixed;
	padding: 5px;
}

th.maxwidth {
	width: 100%;
}

h3.invoice {
	text-align: right;
	margin-bottom: 0px;
	margin-top: 10px;
	margin-right: 5px;
}

h3.text-right,
th.text-right,
td.text-right {
	text-align: right;
}

p.invoice_date {
	text-align: right;
	padding: 5px;
}

table.smallmargin {
	margin: 2px;
}

@media print {
    footer {page-break-after: always;}
    table {page-break-before avoid; page-break-after: avoid;}
}

@media print {
    @page {size: Letter landscape;}
}

.exspeedite-bg,
.table-hover > tbody > tr.exspeedite-bg > td,
.table-striped > tbody > tr.exspeedite-bg > th,
.table-hover > tbody > tr.exspeedite-bg,
.table-striped > tbody > tr.exspeedite-bg:nth-child(odd) > td,
.table-striped > tbody > tr.exspeedite-bg:nth-child(odd) > th  > th 
{
	background-color: #4d8e31;
	color: #fff;
}

.table-striped > tbody > tr.exspeedite-shade > td,
.table-striped > tbody > tr.exspeedite-shade:nth-child(odd) > td
{
	background-color: #CCC;
	font-weight: bold;
}

</style>
';

class margin_report {
	private		$user_code = false;
	private		$load_code = 0;
	private		$load_revenue = 0;
	private		$load_expense = 0;
	private		$lines = 0;
	private		$max_lines = 16;
	private		$currency = 'USD';

	public function __construct( $currency ) {
		$this->currency = $currency;
	}
	
	private function report_header($row, $start, $end) {
	
		$this->output .= '<!-- report_header '.$row["FULLNAME"].' -->
		<table class="border" style="width: 100%; page-break-inside: auto;  page-break-before: always;">
				<tr>
					<td>
					<table class="noborder">
			<tr>
				<td class="w33">'.$row["companyName"].'</td>
				<td class="w33 h2">Margin Report</td>
				<td class="w33 text-right">Sale Person: '.$row["FULLNAME"].'<br>
				Pickup Date: '.date("m/d/Y", strtotime($start)).' to '.date("m/d/Y", strtotime($end)).'</td>
			</tr>
		</table>
	
		';
	}
	
	private function table_header( $row ) {
		$this->output .= '<!-- table_header '.$row["FULLNAME"].' -->
		<table class="border" style="width: 100%; page-break-inside: auto; page-break-after: avoid;">
				<tr>
					<td>
				<h5 class="text-right">Converted Funds To: '.$this->currency.'</h5>
				<table class="display table table-striped table-condensed smallmargin" id="MARGIN_REPORT" style="page-break-inside: auto;">
			<thead>
				<tr class="exspeedite-bg">
					<th>Shipment</th>
					<th>Date</th>
					<th>Business</th>
					<th>Status</th>
					<th class="text-center">Client Currency</th>
					<th class="text-center">Truck Currency</th>
					<th class="text-right">Exchange</th>
					<th>Client</th>
					<th class="text-right">Revenue</th>
					<th class="text-right">Expense</th>
					<th class="text-right">Margin</th>
					<th class="text-right">Margin%</th>
				</tr>
			</thead>
			<tbody>
			';
	}
	
	private function load_header() {
	}
	
	private function load_footer() {
		if( $this->load_code > 0 ) {
			$this->output .= '<!-- load_footer '.$this->load_code.' -->
			<tr class="exspeedite-shade">
				<td class="br"><b>Load Code:</b></td>
				<td class="br">'.$this->load_code.'</td>
				<td class="br"></td>
				<td class="br"></td>
	
				<td class="br"></td>
				<td class="br"></td>
				<td class="br"></td>
				
				<td class="br">Totals:</td>
				<td class="text-right br"><b>'.curr($this->load_revenue).'</b></td>
				<td class="text-right br"><b>'.curr($this->load_expense).'</b></td>
				<td class="text-right br"><b>'.curr($this->load_revenue - $this->load_expense).'</b></td>
				<td class="text-right br"><b>'.($this->load_revenue > 0 ? nm0(($this->load_revenue - $this->load_expense) / $this->load_revenue * 100).'%' : '').'</b></td>
				
			</tr>
			<tr>
				<td colspan="13">&nbsp;</td>
			</tr>
			';
			$this->lines++;
			$this->load_code = 0;
		}
	}
	
	private function table_footer() {
		$this->load_footer();
		$this->output .= '<!-- table_footer -->
						</tbody>
					</table>
				</td>
			</tr>
		</table>
		';
	//	<br style="page-break-after: no;">
	}
	
	private function report_footer() {
		$this->table_footer();
		$this->output .= '<!-- report_footer -->
					<br>	
				</td>
			</tr>
		</table>
		<br style="page-break-after: yes;">
		<footer>&nbsp;</footer>
		';
		$this->lines = 0;
	}
	
	private function check( $var ) {
		return '<span class="glyphicon glyphicon-'.($var ? 'ok' : 'remove').'"></span>';
	}
	
	public function do_report( $report ) {
		$this->user_code = -2;
		$this->load_code = 0;
		$this->lines = 0;
		$this->output =	'<div class="container" role="main">
';

		if( is_array($report) && count($report) > 0 ) {
		
			foreach( $report as $row ) {
				if( $this->lines > $this->max_lines ||
					$this->user_code != $row["USER_CODE"] ) {		// Header
					
					if(  $this->lines > $this->max_lines ||
						$this->user_code != $row["USER_CODE"] ) {
						if( $this->user_code != -2 ) {
							$this->report_footer();
						}
						
						$this->report_header( $row, $_POST["REPORT_FROM"], $_POST["REPORT_TO"] );
					}
		
					$this->user_code = $row["USER_CODE"];
					$this->table_header( $row );
				}
				
				if( $this->load_code != $row["LOAD_CODE"] ) {
					$this->load_footer();		
					$this->load_header($row["LOAD_CODE"]);
					
					$this->load_code = $row["LOAD_CODE"];
					$this->load_revenue = $row["LOAD_REVENUE"];
					$this->load_expense = $row["LOAD_EXPENSE"];
				}
				
				$this->output .=  '<tr>
					<td class="br">'.$row["SHIPMENT_CODE"].' / '.$row["ss_number"].'</td>
					<td class="br">'.dt($row["PICKUP_DATE"]).'</td>
					<td class="br">'.$row["BC_NAME"].'</td>
					<td class="br">'.$row["STATUS_STATE"].'</td>
		
					<td class="text-center br">'.$row["CLIENT_CURRENCY"].'</td>
					<td class="text-center br">'.$row["CARRIER_CURRENCY"].'</td>
					<td class="text-right br">'.nm4($row["exchange_rate"]).'</td>
					
					<td class="br">'.$row["BILLTO_NAME"].'</td>
					<td class="text-right br">'.curr($row["TOTAL_CHARGES"]).'</td>
					<td class="text-right br"></td>
					<td class="text-right br"></td>
					<td class="text-right br"></td>
					<td class="text-right br"></td>
					
				</tr>
				';
				$this->lines++;
			}
			
			$this->report_footer();
		
		}
		$this->output .=  '</div>
		';
		return $this->output;
	}
	
	public function stupid_report( $report ) {
		if( is_array($report) && count($report) > 0 ) {
			// Header
			$this->output =	'<div class="container" role="main">
				<table class="display table table-striped table-condensed smallmargin" id="MARGIN_REPORT" style="page-break-inside: auto;">
			<thead>
				<tr class="exspeedite-bg">
					<th>Office</th>
					<th>Sales Person</th>
					<th>Business</th>
					<th>Shipment</th>
					
					<th>Date</th>
					<th>Load</th>
					<th>Client</th>
					
					<th>Status</th>
					<th>Client Currency</th>
					<th>Carrier Currency</th>
					<th>Lumper Currency</th>
					
					<th class="text-right">Total Charges</th>
					<th class="text-right">Carrier</th>
					<th class="text-right">Lumper Base</th>
					
					<th class="text-right">Exchange Rate</th>

					<th class="text-right">Revenue Change</th>
					<th class="text-right">Expense Change</th>
					<th class="text-right">Lumper Change</th>
					<th class="text-right">Diff</th>
				</tr>
			</thead>
			<tbody>
			';

			$previous_load = -1;
			foreach( $report as $row ) {
				$this->output .=  '<tr>
					<td class="br">'.$row["office"].'</td>
					<td class="br">'.$row["FULLNAME"].'</td>
					<td class="br">'.$row["BC_NAME"].'</td>
					<td class="br">'.$row["ss_number"].'</td>
		
					<td class="br">'.dt($row["PICKUP_DATE"]).'</td>
					<td class="br">'.$row["LOAD_CODE"].'</td>
					<td class="br">'.$row["BILLTO_NAME"].'</td>

					<td class="br">'.$row["STATUS_STATE"].'</td>
					<td class="text-center br">'.$row["CLIENT_CURRENCY"].'</td>
					<td class="text-center br">'.$row["CARRIER_CURRENCY"].'</td>
					<td class="text-center br">'.$row["LUMPER_CURRENCY"].'</td>

					<td class="text-right br">'.curr($row["TOTAL_CHARGES"]).'</td>
					<td class="text-right br">'.($row["LOAD_CODE"] == $previous_load ? '' : curr($row["CARRIER_TOTAL"])).'</td>
					<td class="text-right br">'.($row["LOAD_CODE"] == $previous_load ? '' : curr($row["LUMPER_BASE"])).'</td>

					<td class="text-right br">'.nm4($row["exchange_rate"]).'</td>
					
					<td class="text-right br">'.
						($_POST["CURRENCY"] == $row["CLIENT_CURRENCY"] ? '' : curr($row["LOAD_REVENUE"])).'</td>
					<td class="text-right br">'.
						($_POST["CURRENCY"] == $row["CARRIER_CURRENCY"] ? '' : ($row["LOAD_CODE"] == $previous_load ? '' : curr($row["LOAD_EXPENSE"]))).'</td>
					<td class="text-right br">'.
						($_POST["CURRENCY"] == $row["LUMPER_CURRENCY"] ? '' : ($row["LOAD_CODE"] == $previous_load ? '' : curr($row["LUMPER_CHANGE"]))).'</td>
					<td class="text-right br">'.
						($row["LOAD_CODE"] == $previous_load ? curr($row["LOAD_REVENUE"]) : curr($row["LOAD_REVENUE"] - ($row["LOAD_EXPENSE"] + $row["LUMPER_CHANGE"]))).'</td>
				</tr>
				';
				$previous_load = $row["LOAD_CODE"];
			}
			
			$this->output .= '
						</tbody>
					</table>
				</div>
				';
		}
				
		return $this->output;		

	}
}
	//echo "<pre>";
	//var_dump($_POST);
	//echo "</pre>";

if( ! empty($_GET['CODE']))
	$_POST['USER_CODE'] = $_GET['CODE'];

if( ! isset($_POST['USER_CODE']) ) {
	echo '<div class="container" role="main">
	<form class="form-horizontal" role="form" action="exp_margin_report.php" 
		method="post" enctype="multipart/form-data" 
		name="MARGIN_REPORT" id="MARGIN_REPORT">
		'.( $sts_debug ? '<input name="debug" type="hidden" value="on">' : '').'
	<h2>Margin Report <a class="btn btn-lg btn-default" id="MARGIN_REPORT_CANCEL" href="'.$go_back_to.'"><span class="glyphicon glyphicon-remove"></span> Back</a> <a class="btn btn-lg btn-success" id="MARGIN_REPORT_REFRESH" href="exp_margin_report.php"><span class="glyphicon glyphicon-refresh"></span> Reset</a>
	'.($my_session->superadmin()  ?
	'<a class="btn btn-lg btn-danger" id="SECRET" href="exp_margin_report.php?ROLLUP"><span class="glyphicon glyphicon-warning-sign"></span> Rollup Month</a>' : '').'
	</h2>
	<div class="form-group h3">
		<label class="col-sm-3" for="USER_CODE">Sales Person: </label>
		<div class="col-sm-4">
			'.$mr->user_menu().'
		</div>
	</div>
	
	</form>
	</div>
	';

} else if( ! isset($_POST['OFFICE_CODE']) ) {
	echo '<div class="container" role="main">
	<form class="form-horizontal" role="form" action="exp_margin_report.php" 
		method="post" enctype="multipart/form-data" 
		name="MARGIN_REPORT" id="MARGIN_REPORT">
		'.( $sts_debug ? '<input name="debug" type="hidden" value="on">' : '').'
	<input name="USER_CODE" type="hidden" value="'.$_POST['USER_CODE'].'">
	<h2>Margin Report <a class="btn btn-lg btn-default" id="MARGIN_REPORT_CANCEL" href="'.$go_back_to.'"><span class="glyphicon glyphicon-remove"></span> Back</a> <a class="btn btn-lg btn-success" id="DRIVER_PAY_REFRESH" href="exp_margin_report.php'.(empty($_GET['CODE']) ? '' : '?CODE='.$_GET['CODE']).'"><span class="glyphicon glyphicon-refresh"></span> Reset</a></h2>
	<div class="form-group h3">
		<label class="col-sm-3" for="USER_CODE">Sales Person: </label>
		<div class="col-sm-4">
			'.$mr->user_name( $_POST['USER_CODE'] ).'
		</div>
	</div>
		'.$mr->has_subs( $_POST['USER_CODE'] ).'

	<div class="form-group h3">
		<label class="col-sm-3" for="OFFICE_CODE">Office: </label>
		<div class="col-sm-4">
		'.$mr->office_menu( false, $_POST['USER_CODE'] ).'
		</div>
	</div>
	<div class="form-group h3">
		<label class="col-sm-3" for="BUSINESS_CODE">Business Code: </label>
		<div class="col-sm-4">
			'.$mr->business_code_menu( ).'
		</div>
	</div>
	'.$mr->client_field( ).'
	
	<div class="form-group h3">
		<label class="col-sm-3" for="REPORT_FROM">From: </label>
		<div class="col-sm-4">
		<input class="form-control input-lg date" type="text" name="REPORT_FROM" value="'.date("m/d/Y").'">
		</div>
	</div>
	<div class="form-group h3">
		<label class="col-sm-3" for="REPORT_TO">To: </label>
		<div class="col-sm-4">
		<input class="form-control input-lg date" type="text" name="REPORT_TO" value="'.date("m/d/Y").'">
		</div>
	</div>

	<div class="form-group h3">
		<label class="col-sm-3" for="CURRENCY">Currency: </label>
		<div class="col-sm-4">
			'.$mr->currency_menu().'
		</div>
	</div>

	<div class="form-group h3">
		<label class="col-sm-3" for="OUTPUT">Output Format: </label>
		<div class="col-sm-4">
		<input type="checkbox" class="output-choice" name="OUTPUT">
		</div>
	</div>
	
	<script language="JavaScript" type="text/javascript"><!--
		$(document).ready( function () {
			$(\'.output-choice\').bootstrapToggle({
				on: \'CSV\',
				off: \'PDF\',
				offstyle: \'success\'
			});
		});
			
	
	//--></script>
	<button class="btn btn-lg btn-default" name="getreport" type="submit">Select And Continue</button></h3>
	</form>
	</div>
	';
} else {
	//! Fetch the report data
	$report = $mr->margin_report( $_POST["USER_CODE"], isset($_POST["HAS_SUBS"]), $_POST["OFFICE_CODE"],
		$_POST["BUSINESS_CODE"], $_POST["CLIENT_CODE"],
		$_POST["REPORT_FROM"], $_POST["REPORT_TO"], $_POST["CURRENCY"] );
		
	if($sts_debug) {
		echo "<pre>REPORT DATA\n";
		var_dump($report);
		echo "</pre>";
	}
		
	if( ! isset($_POST["OUTPUT"])) {	//! PDF output format
		$rep = new margin_report( $_POST["CURRENCY"] );
			
		$report_html = $mr->ass_backwards() ?
			$rep->stupid_report( $report ) : 
			$rep->do_report( $report );
	
		if($sts_debug) {
			$html_string = '<!DOCTYPE html>
		<html lang="en">
			<head>
			<meta charset="UTF-8">
			<title>Email</title>
		
				'.$my_css.'
		
			</head>
			<body>
			'.$report_html.'
			</body>
		</html>';
			echo $html_string;
			die;
		}
		
		$file_name = 'MR_'.$_POST["REPORT_FROM"].'_'.$_POST["REPORT_TO"].'pdf';
	
		$my_css = '<!-- Latest compiled and minified CSS -->
<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css" integrity="sha384-BVYiiSIFeK1dGmJRAkycuHAHRg32OmUcww7on3RYdg4Va+PmSTsz/K68vbdEjh4u" crossorigin="anonymous">

<!-- Optional theme -->
<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap-theme.min.css" integrity="sha384-rHyoN1iRsVXV4nD0JutlnGaslCJuC7uwjduW9SVrLvRYooPp2bWYgmgJQIXwl/Sp" crossorigin="anonymous">
	'. $my_css;
	
		//! Convert to PDF
		$report_pdf = $email->convert_html_to_pdf( $report_html, $my_css, true, 'Margin Report' );
		
		if( $report_pdf != false ) {
			if($sts_debug) {
				echo "<pre>pdf_error, report_pdf:\n";
				var_dump($email->pdf_error, $report_pdf);
				echo "</pre>";
				die;
			}
				
			// Output headers so that the file is downloaded rather than displayed
			// Remember that header() must be called before any actual output is sent
			header('Content-Description: File Transfer');
			header('Content-Type: application/pdf');
			header('Expires: 0');
			header('Cache-Control: must-revalidate');
			header('Pragma: public');
			header('Content-Length: ' . strlen($report_pdf));
			 
			// Make the file a downloadable attachment - comment this out to show it directly inside the
			// web browser.  Note that you can give the file any name you want, e.g. alias-name.pdf below:
			header('Content-Disposition: attachment; filename=' . $file_name );
			 
			//! Stream PDF to user
			echo $report_pdf;
		} else {
			echo '<h2>PDF Conversion failed</h2>
			'.$html_string;
		}
	} else {		//! CSV output format
		require_once( "include/sts_csv_class.php" );
		
		$csv = new sts_csv(false, false, $sts_debug);
		
		//$csv->result = $report;
		
		$csv->header( "Margin Report" );
	
		//$csv->render( false, false );
		// Header
		echo en("Office").','.
			en("Sales Person").','.
			en("Business").','.
			en("Shipment").','.
			
			en("Date").','.
			en("Load").','.
			en("Client").','.
			
			en("Status").','.
			en("Client Currency").','.
			en("Carrier Currency").','.
			en("Lumper Currency").','.
			
			en("Total Charges").','.
			en("Carrier").','.
			en("Lumper Base").','.
			($mr->ass_backwards() ? '' : en("Lumper Tax").','.
			en("Lumper Total").','.
			
			en("Revenue").','.
			en("Expense").',').
			en("Exchange Rate").','.

			en("Revenue Change").','.
			en("Expense Change").','.
			($mr->ass_backwards() ? en("Lumper Change").',' : '').
			en("Diff").PHP_EOL;

		if( count($report) > 0 ) {
			$previous_load = -1;
			foreach( $report as $row ) {
				
				echo en($row["office"]).','.
					en($row["FULLNAME"]).','.
					en($row["BC_NAME"]).','.
					en($row["ss_number"]).','.
					
					en(dt($row["PICKUP_DATE"])).','.
					en($row["LOAD_CODE"]).','.
					en($row["BILLTO_NAME"]).','.
					
					en($row["STATUS_STATE"]).','.
					en($row["CLIENT_CURRENCY"]).','.
					en($row["CARRIER_CURRENCY"]).','.
					en($row["LUMPER_CURRENCY"]).','.
					
					en(curr($row["TOTAL_CHARGES"])).','.
					en($row["LOAD_CODE"] == $previous_load ? '' : curr($row["CARRIER_TOTAL"])).','.
					en($row["LOAD_CODE"] == $previous_load ? '' : curr($row["LUMPER_BASE"])).','.
					($mr->ass_backwards() ? '' : en($row["LOAD_CODE"] == $previous_load ? '' : curr($row["LUMPER_TAX"])).','.
					en($row["LOAD_CODE"] == $previous_load ? '' : curr($row["LUMPER_TOTAL"])).','.

					en(curr($row["LOAD_REVENUE_ORIG"])).','.
					en($row["LOAD_CODE"] == $previous_load ? '' : curr($row["LOAD_EXPENSE_ORIG"])).',').
					en($row["exchange_rate"]).','.
					
					en(( ($mr->ass_backwards() && $_POST["CURRENCY"] == $row["CLIENT_CURRENCY"]) ||
						(!$mr->ass_backwards() && $_POST["CURRENCY"] == $row["CARRIER_CURRENCY"]) ? '' :
						curr($row["LOAD_REVENUE"]))).','.
					
					en($row["LOAD_CODE"] == $previous_load ? '' :
						($_POST["CURRENCY"] == $row["CARRIER_CURRENCY"] ? '' :
						curr($row["LOAD_EXPENSE"]))).','.
					
					($mr->ass_backwards() ? 
						(en($row["LOAD_CODE"] == $previous_load ? '' : 
							($_POST["CURRENCY"] == $row["LUMPER_CURRENCY"] ? '' :
							curr($row["LUMPER_CHANGE"]))).',') : '').
					
					en($row["LOAD_CODE"] == $previous_load ? curr($row["LOAD_REVENUE"]) : 
						curr($row["LOAD_REVENUE"] - ($row["LOAD_EXPENSE"] + ($mr->ass_backwards() ?$row["LUMPER_CHANGE"] : 0)))).','.
					
					PHP_EOL;
				$previous_load = $row["LOAD_CODE"];
			}
		}
		
		
		

		die;
	}
}

if( ! isset($_POST) || !isset($_POST["getreport"]))
	require_once( "include/footer_inc.php" );
?>
