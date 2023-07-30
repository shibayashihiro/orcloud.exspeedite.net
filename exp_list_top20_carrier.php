<?php 
// Set flag that this is a parent file
define( '_STS_INCLUDE', 1 );

// Setup Session
require_once( "include/sts_session_setup.php" );

require_once( "include/sts_config.php" );
$sts_debug = isset($_GET['debug']) && in_group( EXT_GROUP_DEBUG );
require_once( "include/sts_session_class.php" );

$my_session = sts_session::getInstance( $exspeedite_db, $sts_debug );
$my_session->access_report( 'Top 20 Carriers' );	// Make sure we should be here

// Use Google Charts, see https://developers.google.com/chart
define( '_STS_GOOGLE_CHARTS', 1 );
$sts_subtitle = "Top 20 Carriers";
require_once( "include/header_inc.php" );
require_once( "include/navbar_inc.php" );

?>
<div class="container-full" role="main">
<?php
require_once( "include/sts_result_class.php" );
require_once( "include/sts_carrier_class.php" );

$top20_table = sts_top20_carrier::getInstance($exspeedite_db, $sts_debug);
$rslt = new sts_result( $top20_table, false, $sts_debug );
echo $rslt->render( $sts_result_top20_carrier_layout, $sts_result_top20_carrier_view );

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
				['Carrier', 'Cost', 'Revenue', 'Margin'],
				";
			foreach( $result as $row ) {
				echo "['".addslashes($row["CARRIER_CODE"])."', ".$row["TOTAL_COST"].", ".$row["REVENUE"].", ".$row["MARGIN"]."],
				";
			}
				
			echo "
			]);
			
			var options = {
				title: 'Carrier Cost/Revenue/Margin Last 12 Months',
				height: 400,
				chartArea:{left:80,top:40,width:'90%',height:'60%'},
				legend: { position: 'none' },
				vAxis: {
					minValue: 0,
				},
				colors: ['#FF0000', '#4d8e31', '#FFBF00']
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
			function addslashes(string) {
			    return string.replace(/\\/g, '\\\\').
			        replace(/\u0008/g, '\\b').
			        replace(/\t/g, '\\t').
			        replace(/\n/g, '\\n').
			        replace(/\f/g, '\\f').
			        replace(/\r/g, '\\r').
			        replace(/'/g, '\\\'').
			        replace(/"/g, '\\"');
			}

			var myTable = $('#EXP_CARRIER').DataTable({
		        //"bLengthChange": false,
		        "bFilter": false,
		        "bSort": true,
		         "order": [[ 5, "desc" ]],
		        "bInfo": false,
				//"bAutoWidth": false,
				//"bProcessing": true, 
				//"sScrollX": "100%",
				"sScrollY": ($(window).height() - 300) + "px",
				//"sScrollXInner": "120%",
				"bPaginate": false,
				"bScrollCollapse": true,
				"rowCallback": function( row, data ) {
					//console.log(data);
					var cost = parseFloat(data[2].replace(",", ""));
					var revenue = parseFloat(data[4].replace(",", ""));
					var margin = parseFloat(data[5].replace(",", ""));
					var amargin = parseFloat(data[6].replace(",", ""));
					if( cost = 0 ) {
						$('td:eq(2)', row).addClass("danger");	// cost
					}
					if( revenue <= 0 ) {
						$('td:eq(4)', row).addClass("danger"); // No revenue
					}
					if( margin <= 0 ) {
						$('td:eq(5)', row).addClass("danger"); // No margin
					}
					if( amargin <= 0 ) {
						$('td:eq(6)', row).addClass("danger"); // No avg margin
					}
				},

			});
			$('#EXP_CARRIER').on( 'draw.dt', function () {
				var updatedData = new Array();
				updatedData.push(['Carrier', 'Cost', 'Revenue', 'Margin']);
				myTable.rows().every( function ( rowIdx, tableLoop, rowLoop ) {
				    var data = this.data();
				    //console.log(data);
				    var cname = data[0];
				    var a = cname.indexOf('">');
				    var b = cname.indexOf('</');
					var cost = parseFloat(data[2].replace(",", ""));
					var revenue = parseFloat(data[4].replace(",", ""));
					var margin = parseFloat(data[5].replace(",", ""));
				    updatedData.push([addslashes(cname.substr(a+2, b-a-2)), cost, revenue, margin]);
				} );
				//console.log(updatedData);
				var data2 = google.visualization.arrayToDataTable(updatedData);
				var options = {
					title: 'Carrier Cost/Revenue/Margin Last 12 Months',
					height: 400,
					chartArea:{left:80,top:40,width:'90%',height:'60%'},
					legend: { position: 'none' },
					vAxis: {
						minValue: 0,
					},
					colors: ['#FF0000', '#4d8e31', '#FFBF00']
				};
				
				var chart = new google.visualization.ColumnChart($('#chart1')[0]);
				chart.draw(data2, options);
			} );
			
		});
	//--></script>

<?php

require_once( "include/footer_inc.php" );
?>

