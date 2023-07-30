<?php 
// Set flag that this is a parent file
define( '_STS_INCLUDE', 1 );

// Setup Session
require_once( "include/sts_session_setup.php" );

require_once( "include/sts_config.php" );
$sts_debug = isset($_GET['debug']) && in_group( EXT_GROUP_DEBUG );
require_once( "include/sts_session_class.php" );

$my_session = sts_session::getInstance( $exspeedite_db, $sts_debug );
$my_session->access_report( 'Key Accounts' );	// Make sure we should be here

// Use Google Charts, see https://developers.google.com/chart
define( '_STS_GOOGLE_CHARTS', 1 );
$sts_subtitle = "Key Accounts";
require_once( "include/header_inc.php" );
require_once( "include/navbar_inc.php" );

?>
<div class="container-full" role="main">
<?php
require_once( "include/sts_result_class.php" );
require_once( "include/sts_shipment_kpi_class.php" );

$key_acct = sts_key_acct::getInstance($exspeedite_db, $sts_debug);
$rslt = new sts_result( $key_acct, false, $sts_debug );
echo $rslt->render( $sts_result_key_acct_layout, $sts_result_key_acct_view );

$result = $rslt->last_result();
if( defined('_STS_GOOGLE_CHARTS') && count($result) > 0 ) {
	
	$client = array();
	$month = array();
	$shipments = array();
	$revenue = array();
	foreach( $result as $row ) {
		if( ! in_array(addslashes($row["CLIENT_CODE"]), $client) )
			$client[] = addslashes($row["CLIENT_CODE"]);
			
		if( ! in_array($row["MN"], $month) )
			$month[] = $row["MN"];
			
		if( ! isset($shipments[$row["MN"]]) )
			$shipments[$row["MN"]] = array();
		
		$shipments[$row["MN"]][$row["CLIENT_CODE"]] = $row["SHIPMENTS"];
		
		if( ! isset($revenue[$row["MN"]]) )
			$revenue[$row["MN"]] = array();
		
		$revenue[$row["MN"]][$row["CLIENT_CODE"]] = $row["REVENUE"];
		
	}
	
	
	echo '<div class="well well-sm">
	<div id="chart1" style="min-width: 310px; height: 400px; margin: 0 auto"></div>
	<br>';
	echo "<script language=\"JavaScript\" type=\"text/javascript\"><!--
		
		$(document).ready( function () {
			google.charts.setOnLoadCallback(drawChart);
			function drawChart() {
			var data = google.visualization.arrayToDataTable([
				['Month', '".implode("', '", $client)."'],
				";
			foreach( $month as $mn ) {
				echo "['".$mn."', ";
				
				foreach( $client as $c ) {
					echo (isset($shipments[$mn][$c]) ? $shipments[$mn][$c] : 0).", ";
				}
				echo "],
				";
			}
				
			echo "
			]);
			
			var options = {
				title: 'Key Accounts Shipments Over The Past 24 Months',
				height: 400,
				//chartArea:{left:80,top:40,width:'90%',height:'60%'},
				vAxis: {
					minValue: 0,
				},
				legend: { position: 'bottom' },
				pointSize: 6,
				pointShape: 'square',
				//colors: ['#4d8e31', '#d95f02', '#FFBF00']
			};
			
			var chart = new google.visualization.LineChart($('#chart1')[0]);
			chart.draw(data, options);
			}


		});
	//--></script>
	";

	echo '
	<div id="chart2" style="min-width: 310px; height: 400px; margin: 0 auto"></div>
	</div>';
	echo "<script language=\"JavaScript\" type=\"text/javascript\"><!--
		
		$(document).ready( function () {
			google.charts.setOnLoadCallback(drawChart);
			function drawChart() {
			var data = google.visualization.arrayToDataTable([
				['Month', '".implode("', '", $client)."'],
				";
			foreach( $month as $mn ) {
				echo "['".$mn."', ";
				
				foreach( $client as $c ) {
					echo (isset($revenue[$mn][$c]) ? $revenue[$mn][$c] : 0).", ";
				}
				echo "],
				";
			}
				
			echo "
			]);
			
			var options = {
				title: 'Key Accounts Revenue Over The Past 24 Months',
				height: 400,
				//chartArea:{left:80,top:40,width:'90%',height:'60%'},
				vAxis: {
					minValue: 0,
				},
				legend: { position: 'bottom' },
				pointSize: 6,
				pointShape: 'square',
				//colors: ['#4d8e31', '#d95f02', '#FFBF00']
			};
			
			var chart2 = new google.visualization.LineChart($('#chart2')[0]);
			chart2.draw(data, options);
			}


		});
	//--></script>
	";
}

?>
</div>
	<script language="JavaScript" type="text/javascript"><!--
		
		$(document).ready( function () {
			var myTable = $('#EXP_SHIPMENT').DataTable({
		        //"bLengthChange": false,
		        "bFilter": false,
		        "bSort": false,
		        "bInfo": false,
				//"bAutoWidth": false,
				//"bProcessing": true, 
				//"sScrollX": "100%",
				"sScrollY": "300px",
				//"sScrollXInner": "120%",
				"bPaginate": false,
				"bScrollCollapse": true,
			});
			
		});
	//--></script>

<?php

require_once( "include/footer_inc.php" );
?>

