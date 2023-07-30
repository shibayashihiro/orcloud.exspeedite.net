<?php 
// Set flag that this is a parent file
define( '_STS_INCLUDE', 1 );

// Setup Session
require_once( "include/sts_session_setup.php" );

require_once( "include/sts_config.php" );
$sts_debug = isset($_GET['debug']) && in_group( EXT_GROUP_DEBUG );
require_once( "include/sts_session_class.php" );

$my_session = sts_session::getInstance( $exspeedite_db, $sts_debug );
$my_session->access_report( 'Top 20 Clients' );	// Make sure we should be here

// Use Google Charts, see https://developers.google.com/chart
define( '_STS_GOOGLE_CHARTS', 1 );
$sts_subtitle = "Top 20 Clients";
require_once( "include/header_inc.php" );
require_once( "include/navbar_inc.php" );

?>
<div class="container-full" role="main">
<?php
require_once( "include/sts_result_class.php" );
require_once( "include/sts_shipment_kpi_class.php" );

$top20_table = sts_top20::getInstance($exspeedite_db, $sts_debug);
$rslt = new sts_result( $top20_table, false, $sts_debug );
echo $rslt->render( $sts_result_top20_layout, $sts_result_top20_view );

$result = $rslt->last_result();
if( defined('_STS_GOOGLE_CHARTS') && count($result) > 0 ) {
	
	echo '<div class="well well-sm">
	<div id="chart1" style="min-width: 310px; height: 400px; margin: 0 auto"></div>
	</div>';
	echo "<script language=\"JavaScript\" type=\"text/javascript\"><!--
		
		$(document).ready( function () {
			google.charts.setOnLoadCallback(drawChart);
			function drawChart() {
			var data = google.visualization.arrayToDataTable([
				['Client', 'Revenue'],
				";
			foreach( $result as $row ) {
				echo "['".addslashes($row["CLIENT_CODE"])."', ".$row["REVENUE"]."],
				";
			}
				
			echo "
			]);
			
			var options = {
				title: 'Client Revenue Last 12 Months',
				height: 400,
				chartArea:{left:80,top:40,width:'90%',height:'60%'},
				legend: { position: 'none' },
				//hAxis: { 
				//	slantedText: true, 
				//	slantedTextAngle: 60 // here you can even use 180 
				//},
				vAxis: {
					minValue: 0,
					title: 'Revenue'
				}
			};
			
			var chart = new google.visualization.ColumnChart($('#chart1')[0]);
			chart.draw(data, options);
			}


		});
	//--></script>
	";
}

?>
</div>
	<script language="JavaScript" type="text/javascript"><!--
		
		$(document).ready( function () {

			$('#EXP_SHIPMENT').dataTable({
		        //"bLengthChange": false,
		        "bFilter": false,
		        "bSort": false,
		        "bInfo": false,
				//"bAutoWidth": false,
				//"bProcessing": true, 
				//"sScrollX": "100%",
				"sScrollY": ($(window).height() - 300) + "px",
				//"sScrollXInner": "120%",
				"bPaginate": false,
			});
			
		});
	//--></script>

<?php

require_once( "include/footer_inc.php" );
?>

