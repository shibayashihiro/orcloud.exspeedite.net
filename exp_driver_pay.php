<?php 
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
	$sts_subtitle = "Driver Pay Report";
	require_once( "include/header_inc.php" );
	require_once( "include/navbar_inc.php" );
}

require_once( "include/sts_driver_pay_master_class.php" );
require_once( "include/sts_email_class.php" );

$dp = sts_driver_pay_master::getInstance($exspeedite_db, $sts_debug);
$email = sts_email::getInstance($exspeedite_db, $sts_debug);

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

function dt( $x ) {
	return isset($x) ? str_replace(' ', '&nbsp;', date("m/d/Y", strtotime($x))):'';
}

//! CSS to include
$my_css = '
<style>
html {
    font-family: sans-serif;
    -webkit-text-size-adjust: 100%;
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
	width: 100%;
	margin-left: auto;
	margin-right: auto;
	border: 2px solid DarkGray;
	border-spacing: 0px;
    border-collapse: collapse;
    background-color: transparent;
}

table tr {
	vertical-align: text-top;
}

table.border>tbody>tr>td
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

</style>
';

function report_header($dp, $email, $driver_code, $driver_name, $statement_dates) {
//<p><a class="btn btn-lg btn-success" id="DRIVER_PAY_REFRESH" href="exp_driver_pay.php"><span class="glyphicon glyphicon-refresh"></span> Reset</a></p>

	$logo = $dp->driver_office_logo( $driver_code );
	
	$out = '<table class="border" style="page-break-inside: auto;">
			<tbody>
			<tr>
				<td>
				<table class="noborder">
				<tbody>
					<tr>
						<td class="w33">
							<img src="'.$email->mime_encocde_logo($logo).'" alt="logo" height="80" style="height: 80px;">
						</td>
						<td class="w33"></td>
						<td class="w33">
							<h3 class="text-right">Driver Pay Statement</h3>
						</td>
					</tr>
				</tbody>
				</table>

	<h2 class="exspeedite-bg">Driver: '.$driver_name.'</h2>

	<h3 class="text-right">Statement dates: '.$statement_dates.'</h3>
	';
	
	return $out;		
}

function table_header( $date_range ) {
	$out = '<table class="border" style="page-break-inside: avoid; page-break-after: avoid;">
			<tr>
				<td>
			<h5>Week: '.$date_range.'</h5>
			<table class="display table table-striped table-condensed smallmargin htmlonly" id="DRIVER_REPORT'.substr($date_range, -10).'" style="page-break-inside: auto;">
		<thead>
			<tr class="exspeedite-bg">
				<th>DATE</th>
				<th>TRIP</th>
				<th>LOAD</th>
				<th class="w25">ROUTE</th>
				<th class="w25">DESCRIPTION</th>
				<th class="text-right">HOURS</th>
				<th class="text-right">TOTAL MILES</th>

				<th class="text-right">TRUCK PAY</th>
				<th class="text-right">EXTRA STOP</th>
				<th class="text-right">CASH ADVANCES</th>
				<th class="text-right">TOLLS</th>
				<th class="text-right">MISC</th>
				<th class="text-right">BONUS</th>
				<th class="text-right">TOTAL</th>
			</tr>
		</thead>
		<tbody>
		';
	return $out;
}

function table_footer($total_hours, $total_miles, $total_truck_pay, $total_extra_stop,
	$total_advance, $total_tolls, $total_misc, $total_handling, $total_bonus, $total_total) {
	$out = '</tbody>
	<tfoot>
		<tr class="exspeedite-bg">
			<th></th>
			<th></th>
			<th></th>
			<th></th>
			<th></th>
			<th class="text-right">'.nm1($total_hours).'</th>
			<th class="text-right">'.nm1($total_miles).'</th>

			<th class="text-right">'.curr($total_truck_pay).'</th>
			<th class="text-right">'.curr($total_extra_stop).'</th>
			<th class="text-right">'.curr($total_advance).'</th>

			<th class="text-right">'.curr($total_tolls).'</th>
			<th class="text-right">'.curr($total_misc).'</th>
			<th class="text-right">'.curr($total_bonus).'</th>
			<th class="text-right">'.currz($total_total).'</th>
		</tr>
	</tfoot>
	</table>
	</td>
	</tr>
	</table>
	';
//	<br style="page-break-after: no;">
	
	return $out;
}

function report_footer( $load, $reimbursements, $advances, $misc, $bonus, $grand_total ) {
	$out = '<br>
	<table class="border">
	<tbody>
			<tr>
				<td>
	<table class="display table table-striped table-condensed table-bordered smallmargin" id="DRIVER_SUMMARY">
		<thead>
			<tr>
				<th></th>
				<th class="exspeedite-bg text-right">GRAND TOTAL LOAD</th>
				<th class="exspeedite-bg text-right">GRAND TOTAL CASH ADVANCES</th>
				<th class="exspeedite-bg text-right">GRAND TOTAL MISC</th>
				<th class="exspeedite-bg text-right">GRAND TOTAL BONUS</th>
				<th class="exspeedite-bg text-right">GRAND TOTAL</th>
			</tr>
		</thead>
		<tbody>
			<tr>
				<th>SETTLEMENT TOTALS</th>
				<th class="text-right br">'.curr($load).'</th>
				<th class="text-right br">'.curr($advances).'</th>
				<th class="text-right br">'.curr($misc).'</th>
				<th class="text-right br">'.curr($bonus).'</th>
				<th class="text-right">'.curr($grand_total).'</th>
			</tr>
		</tbody>
	</table>
	</td>
	</tr>
	</tbody>
	</table>
	<br>
	
	</td>
	</tr>
	</tbody>
	</table>
	<footer>&nbsp;</footer>
	';
	
	return $out;
}

if( ! isset($_POST['DRIVER_CODE']) ) {
	echo '<div class="container" role="main">
	<form class="form-inline" role="form" action="exp_driver_pay.php" 
		method="post" enctype="multipart/form-data" 
		name="DRIVER_PAY" id="DRIVER_PAY">
		'.( $sts_debug ? '<input name="debug" type="hidden" value="on">' : '').'
	<h2>Driver Pay Report <a class="btn btn-lg btn-default" id="DRIVER_PAY_CANCEL" href="exp_listdriver.php"><span class="glyphicon glyphicon-remove"></span> Return to Drivers</a> <a class="btn btn-lg btn-success" id="DRIVER_PAY_REFRESH" href="exp_driver_pay.php"><span class="glyphicon glyphicon-refresh"></span> Reset</a></h2>
	<h3>Driver: '.$dp->driver_menu().'</h3>
	</form>
	</div>
	';

} else if( ! isset($_POST['WEEKEND_FROM']) ) {
	echo '<div class="container" role="main">
	<form class="form-inline" role="form" action="exp_driver_pay.php" 
		method="post" enctype="multipart/form-data" 
		name="DRIVER_PAY" id="DRIVER_PAY">
		'.( $sts_debug ? '<input name="debug" type="hidden" value="on">' : '').'
	<input name="DRIVER_CODE" type="hidden" value="'.$_POST['DRIVER_CODE'].'">
	<h2>Driver Pay Report <a class="btn btn-lg btn-default" id="DRIVER_PAY_CANCEL" href="exp_listdriver.php"><span class="glyphicon glyphicon-remove"></span> Back</a> <a class="btn btn-lg btn-success" id="DRIVER_PAY_REFRESH" href="exp_driver_pay.php"><span class="glyphicon glyphicon-refresh"></span> Reset</a></h2>
	<h3>Driver: '.$dp->driver_name( $_POST['DRIVER_CODE'] ).'</h3>
	<h3>From: '.$dp->pay_from_menu( false, $_POST['DRIVER_CODE'] ).'</h3>
	</form>
	</div>
	';
} else if( ! isset($_POST['WEEKEND_TO']) ) {
	echo '<div class="container" role="main">
	<form class="form-inline" role="form" action="exp_driver_pay.php" 
		method="post" enctype="multipart/form-data" 
		name="DRIVER_PAY" id="DRIVER_PAY">
		'.( $sts_debug ? '<input name="debug" type="hidden" value="on">' : '').'
	<input name="DRIVER_CODE" type="hidden" value="'.$_POST['DRIVER_CODE'].'">
	<input name="WEEKEND_FROM" type="hidden" value="'.$_POST['WEEKEND_FROM'].'">
	<h2>Driver Pay Report <a class="btn btn-lg btn-default" id="DRIVER_PAY_CANCEL" href="exp_listdriver.php"><span class="glyphicon glyphicon-remove"></span> Back</a> <a class="btn btn-lg btn-success" id="DRIVER_PAY_REFRESH" href="exp_driver_pay.php"><span class="glyphicon glyphicon-refresh"></span> Reset</a></h2>
	<h3>Driver: '.$dp->driver_name( $_POST['DRIVER_CODE'] ).'</h3>
	<h3>From: '.$_POST['WEEKEND_FROM'].'</h3>
	<h3>To: '.$dp->pay_to_menu( $_POST['WEEKEND_FROM'], false, $_POST['DRIVER_CODE']  ).' <button class="btn btn-lg btn-default" name="submit" type="submit">Select And Continue</button></h3>
	</form>
	</div>
	';
} else if( ! isset($_POST['STATEMENT_FROM']) ) {
	echo '<div class="container" role="main">
	<form class="form-inline" role="form" action="exp_driver_pay.php" 
		method="post" enctype="multipart/form-data" 
		name="DRIVER_PAY" id="DRIVER_PAY">
		'.( $sts_debug ? '<input name="debug" type="hidden" value="on">' : '').'
	<input name="DRIVER_CODE" type="hidden" value="'.$_POST['DRIVER_CODE'].'">
	<input name="WEEKEND_FROM" type="hidden" value="'.$_POST['WEEKEND_FROM'].'">
	<input name="WEEKEND_TO" type="hidden" value="'.$_POST['WEEKEND_TO'].'">
	<h2>Driver Pay Report <a class="btn btn-lg btn-default" id="DRIVER_PAY_CANCEL" href="exp_listdriver.php"><span class="glyphicon glyphicon-remove"></span> Back</a> <a class="btn btn-lg btn-success" id="DRIVER_PAY_REFRESH" href="exp_driver_pay.php"><span class="glyphicon glyphicon-refresh"></span> Reset</a></h2>
	<h3>Driver: '.$dp->driver_name( $_POST['DRIVER_CODE'] ).'</h3>
	<h3>From: '.dt($_POST['WEEKEND_FROM']).'</h3>
	<h3>To: '.dt($_POST['WEEKEND_TO']).'</h3>
	<h3>Statement FROM: <input type="date" value="'.$_POST['WEEKEND_FROM'].'" name="STATEMENT_FROM"></h3>
	<h3>Statement TO: <input type="date" value="'.$_POST['WEEKEND_TO'].'" name="STATEMENT_TO"></h3>
 <button class="btn btn-lg btn-default" name="getpdf" type="submit">Select And Continue</button></h3>
	</form>
	</div>
	';
	
	
} else {
	
	//! Fetch the report data
	$report = $dp->driver_pay2($_POST['WEEKEND_FROM'], $_POST['WEEKEND_TO'], $_POST['DRIVER_CODE']);
	
	$driver_code = false;
	$weekend_from = false;
	$grand_load = $grand_total = $grand_misc = $grand_advances = $grand_bonus = $grand_total_driver = 0;
	$report_html =  '
<div class="container-full" role="main">
';


if( is_array($report) && count($report) > 0 ) {

	foreach( $report as $row ) {
		if( $driver_code != $row["DRIVER_ID"] ||
			$weekend_from != $row["WEEKEND_FROM"] ) {		// Header
			
			if( $driver_code != $row["DRIVER_ID"] ) {
				if( $driver_code != false ) {
					$report_html .= table_footer($total_hours, $total_miles, $total_truck_pay,
						$total_extra_stop,
						$total_advance, $total_tolls, $total_misc, $total_handling, $total_bonus,
						$total_total);
					
					$grand_load += $total_truck_pay + $total_extra_stop;
					$grand_total += $total_tolls;
					$grand_advances += $total_advance;
					$grand_misc += $total_misc;
					$grand_bonus += $total_bonus;
					$grand_total_driver += $total_total;
					
					$weekend_from = false;

					$report_html .= report_footer( $grand_load, $grand_total, $grand_advances, $grand_misc, $grand_bonus, $grand_total_driver );
					$grand_load = $grand_total = $grand_advances = $grand_misc = $grand_bonus = $grand_total_driver = 0;
				}
				
				$report_html .= report_header($dp, $email, $row["DRIVER_ID"], $row["DRIVER"], date("m/d/Y", strtotime($_POST['STATEMENT_FROM'])).' to '.date("m/d/Y", strtotime($_POST['STATEMENT_TO'])) );
			}

			if( $weekend_from != false ) {
				$report_html .= table_footer($total_hours, $total_miles, $total_truck_pay, $total_extra_stop,
					$total_advance, $total_tolls, $total_misc, $total_handling, $total_bonus, $total_total);
				$grand_load += $total_truck_pay + $total_extra_stop;
				$grand_total += $total_tolls;
				$grand_advances += $total_advance;
				$grand_misc += $total_misc;
				$grand_bonus += $total_bonus;
				$grand_total_driver += $total_total;

			}	
		
			$driver_code = $row["DRIVER_ID"];
			$weekend_from = $row["WEEKEND_FROM"];
			
			$report_html .= table_header( date("m/d/Y", strtotime($row["WEEKEND_FROM"])).' to '.date("m/d/Y", strtotime($row["WEEKEND_TO"])) );
			
			$total_hours = $total_miles = $total_truck_pay =
				$total_extra_stop = $total_advance = $total_tolls =
				$total_misc = $total_bonus = $total_handling = $total_total = 0;
		}
		
		$report_html .=  '<tr>
			<td class="br">'.dt($row["LOAD_DATE"]).'</td>
			<td>'.$row["SS_NUMBER"].'</td>
			<td class="br">'.$row["LOAD_ID"].'</td>
			<td class="br">'.$row["ROUTE_DESCRIPTION"].'</td>
			<td class="br">'.$row["LOAD_RATE_NAME"].'</td>
			<td class="text-right br">'.nm1($row["HOURS"]).'</td>
			<td class="text-right br">'.nm1($row["MILES"]).'</td>

			<td class="text-right br">'.curr($row["TRUCK_PAY"]).'</td>
			<td class="text-right br">'.curr($row["EXTRA_STOP"]).'</td>
			<td class="text-right br">'.curr($row["ADVANCES"]).'</td>

			<td class="text-right br">'.curr($row["TOLLS"]).'</td>
			<td class="text-right br">'.curr($row["MISC"]).'</td>
			<td class="text-right br">'.curr($row["BONUS"]).'</td>
			<td class="text-right">'.curr($row["ROW_TOTAL"]).'</td>
		</tr>
		';		
			$total_hours += floatval($row["HOURS"]);
			$total_miles += floatval($row["MILES"]);
			$total_truck_pay += floatval($row["TRUCK_PAY"]);
			$total_extra_stop += floatval($row["EXTRA_STOP"]);
			$total_advance += floatval($row["ADVANCES"]);
			$total_tolls += floatval($row["TOLLS"]);
			$total_misc += floatval($row["MISC"]);
			$total_bonus += floatval($row["BONUS"]);
			$total_total += floatval($row["ROW_TOTAL"]);
	}
	$report_html .= table_footer($total_hours, $total_miles, $total_truck_pay, $total_extra_stop,
		$total_advance, $total_tolls, $total_misc, $total_handling, $total_bonus, $total_total);
	
	$grand_load += $total_truck_pay + $total_extra_stop;
	$grand_total += $total_tolls;
	$grand_advances += $total_advance;
	$grand_misc += $total_misc;
	$grand_bonus += $total_bonus;
	$grand_total_driver += $total_total;

	$report_html .= report_footer( $grand_load, $grand_total, $grand_advances, $grand_misc, $grand_bonus, $grand_total_driver );

}
	$report_html .=  '</div>
	';

if( $email->pdf_enabled() ) {	
	$file_name = 'DP_'.$_POST['DRIVER_CODE'].'_'.$_POST['STATEMENT_FROM'].'_'.$_POST['STATEMENT_TO'].'pdf';
	
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
	}
/*
		<!-- Latest compiled and minified CSS -->
<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css" integrity="sha384-BVYiiSIFeK1dGmJRAkycuHAHRg32OmUcww7on3RYdg4Va+PmSTsz/K68vbdEjh4u" crossorigin="anonymous">

<!-- Optional theme -->
<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap-theme.min.css" integrity="sha384-rHyoN1iRsVXV4nD0JutlnGaslCJuC7uwjduW9SVrLvRYooPp2bWYgmgJQIXwl/Sp" crossorigin="anonymous">
*/
	
	//! Convert to PDF
	$report_pdf = $email->convert_html_to_pdf( $report_html, $my_css, true, 'Driver Pay' );
	
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
	$sts_subtitle = "Driver Pay Report";
	require_once( "include/header_inc.php" );
	require_once( "include/navbar_inc.php" );
	echo $report_html;
	
}
}

if( ! isset($_POST) || !isset($_POST["getpdf"]))
	require_once( "include/footer_inc.php" );
?>
