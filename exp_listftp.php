<?php 
// Set flag that this is a parent file
define( '_STS_INCLUDE', 1 );

// Setup Session
require_once( "include/sts_session_setup.php" );

require_once( "include/sts_config.php" );
$sts_debug = isset($_GET['debug']) && in_group( EXT_GROUP_DEBUG );
require_once( "include/sts_session_class.php" );

$my_session = sts_session::getInstance( $exspeedite_db, $sts_debug );
$my_session->access_check( EXT_GROUP_ADMIN );	// Make sure we should be here

// Include highcharts, see http://www.highcharts.com
//define( '_STS_HIGHCHARTS', 1 );
// Use Google Charts, see https://developers.google.com/chart
define( '_STS_GOOGLE_CHARTS', 1 );
$sts_subtitle = "EDI FTP Configuration";
require_once( "include/header_inc.php" );
require_once( "include/navbar_inc.php" );

?>
<div class="container-full" role="main">
<?php
require_once( "include/sts_result_class.php" );
require_once( "include/sts_ftp_class.php" );
require_once( "include/sts_edi_parser_class.php" );

if( isset($_GET["SPAWN"])) {
	// Turn off output buffering
	ini_set('output_buffering', 'off');
	// Implicitly flush the buffer(s)
	ini_set('implicit_flush', true);
	ob_implicit_flush(true);

	if (ob_get_level() == 0) ob_start();

	$sts_override_timer = 1; // Cause it to spawn now.
	
	echo '<div class="alert alert-warning alert-dismissible" role="alert">
  <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>
  <strong><span class="glyphicon glyphicon-info-sign"></span></strong> Checking for EDI 204s...
</div>';
	ob_flush(); flush();

	//! This will check for incoming EDI 204s -default is every 10 minutes
	require_once( "exp_spawn_edi.php" );
	ob_flush(); flush();

	sleep(5);
	reload_page( 'exp_listftp.php' );
}

if( ! empty($_GET["flush"]) ) {
	// Turn off output buffering
	ini_set('output_buffering', 'off');
	// Implicitly flush the buffer(s)
	ini_set('implicit_flush', true);
	ob_implicit_flush(true);

	if (ob_get_level() == 0) ob_start();

	echo '<div class="alert alert-warning alert-dismissible" role="alert">
  <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>
  <strong><span class="glyphicon glyphicon-info-sign"></span></strong> Flushing '.$_GET["flush"].' ... ';
	ob_flush(); flush();
  
	$ftp = sts_ftp::getInstance($exspeedite_db, $sts_debug);
	$count = $ftp->ftp_flush( $_GET["flush"] );
	echo $count.' deleted.
	</div>';
	ob_flush(); flush();

	sleep(5);
	reload_page( 'exp_listftp.php' );
}

$ftp = sts_ftp::getInstance($exspeedite_db, $sts_debug);
$edi = sts_edi_parser::getInstance($exspeedite_db, $sts_debug);

$rslt = new sts_result( $ftp, false, $sts_debug );
echo '<div class="well well-sm">
	'.$rslt->render( $sts_result_ftp_layout, $sts_result_ftp_edit ).'
	</div>';

$chart = $edi->database->get_multiple_rows("
	SELECT date(edi_time) DT, concat(edi_client, ' ', edi_type, ' ', DIRECTION) TP, count(*) as NUM
	FROM exp_edi
	where DATE_SUB(CURDATE(),INTERVAL 60 DAY) <= edi_time
	group by date(edi_time), concat(edi_client, ' ', edi_type, ' ', DIRECTION)");

if( is_array($chart) && count($chart) > 0 ) {
	
	$cats = array();
	$cats2 = array();
	$series = array();
	$value = array();
	foreach( $chart as $row ) {
		$dt = substr($row["DT"],5);
		if( ! isset($cats2[$dt]) ){
			$cats[] = $dt;
			$cats2[$dt] = 1;
		}
		
		if( ! isset($series[$row["TP"]]))
			$series[$row["TP"]] = 1;
		
		$value[$row["TP"]][$dt] = $row["NUM"];
	}

if( defined('_STS_HIGHCHARTS') ) {
	echo '<div class="well well-sm">
	<div id="chart" style="min-width: 310px; height: 300px; margin: 0 auto"></div>
	</div>';
	echo "<script language=\"JavaScript\" type=\"text/javascript\"><!--
		
		$(document).ready( function () {
			$(function () {
			    $('#chart').highcharts({
			        chart: {
			            type: 'area'
			        },
			        title: {
			            text: 'EDI Activity In The Last 60 Days'
			        },
			        xAxis: {
			            categories: ['".implode('\', \'', $cats)."'],
			            tickmarkPlacement: 'on',
			            title: {
			                enabled: false
			            },
			            labels: {
				            rotation: -60
			            }
			        },
			        yAxis: {
			            title: {
			                text: 'EDI Messages'
			            },
			        },
			        tooltip: {
			            shared: true,
			            //valueSuffix: ' millions'
			        },
			        plotOptions: {
			            area: {
			                stacking: 'normal',
			                lineColor: '#666666',
			                lineWidth: 1,
			                marker: {
			                    enabled: false
			                }
			            }
			        },
			        series: [";
					foreach( $series as $s => $x ) {
						echo "{
			            name: '$s',
			            data: [";
			            foreach( $cats as $c ) {
				            echo (isset($value[$s][$c]) ? $value[$s][$c] : 0).", ";
				        }
			            echo "]
			        },";
					}
					echo "]
			    });
			});
		});
	//--></script>

	";
}
if( defined('_STS_GOOGLE_CHARTS') ) {
	echo '<div class="well well-sm">
	<div id="chart2" style="min-width: 310px; height: 300px; margin: 0 auto"></div>
	</div>';
	echo "<script language=\"JavaScript\" type=\"text/javascript\"><!--
		
		$(document).ready( function () {
			google.charts.setOnLoadCallback(drawChart);
			function drawChart() {
			var data = google.visualization.arrayToDataTable([
				['Date', '".implode('\', \'', array_keys($series))."'],
				";
			foreach( $cats as $c ) {
				echo "['".$c."', ";
				foreach( $series as $s => $x ) {
					echo (isset($value[$s][$c]) ? $value[$s][$c] : 0).", ";
				}
				echo "],
				";
			}
				
			echo "
			]);
			
			var options = {
				title: 'EDI Activity In The Last 60 Days',
				isStacked: true,
				height: 300,
				legend: {position: 'bottom', maxLines: 3},
				chartArea:{left:60,top:40,width:'90%',height:'60%'},
				hAxis: { 
					slantedText: true, 
					slantedTextAngle: 60 // here you can even use 180 
				},
				vAxis: {
					minValue: 0,
					title: 'EDI Messages'
				}
			};
			
			var chart = new google.visualization.AreaChart($('#chart2')[0]);
			chart.draw(data, options);
			}


		});
	//--></script>
	";
}
}

$sts_result_edi_last10_edit['filters_html'] = '<div class="btn-group"><a class="btn btn-sm btn-success" href="exp_listftp.php?SPAWN"><span class="glyphicon glyphicon-refresh"></span> Check for EDI 204s now.</a><a class="btn btn-sm btn-success" href="exp_listftp.php"><span class="glyphicon glyphicon-refresh"></span></a>
<a class="btn btn-sm btn-danger" href="exp_edi_parser.php" target="_blank"><span class="glyphicon glyphicon-import"></span> Parse / Inject EDI</a></div>';

$rslt = new sts_result( $edi, false, $sts_debug );
echo '<div class="well well-sm">
	'.$rslt->render( $sts_result_edi_last10_layout, $sts_result_edi_last10_edit ).'
	</div>';

	if( isset($_GET['code']) && isset($_GET['file'])) {
		$raw = $edi->fetch_rows("EDI_CODE = ".$_GET['code']." AND FILENAME = '".$_GET['file']."'","EDI_CLIENT, DIRECTION, CONTENT");
		if( is_array($raw) && count($raw) == 1 && isset($raw[0]["CONTENT"])) {
			$edi_raw = $raw[0]["CONTENT"];
			$client =  $raw[0]["EDI_CLIENT"];
			$direction =  $raw[0]["DIRECTION"];
			echo "<h3>Raw EDI</h3>
				<pre>".$edi_raw."</pre>";
			$edi->tokenize( $edi_raw );
			if( $ftp->edi_strict( $client ) ) {
				if( $direction == 'in' )
					$parsed = $edi->parse_edi( $client, $ftp->edi_our_id( $client ), true );
				else
					$parsed = $edi->parse_edi( $ftp->edi_our_id( $client ), $client, true );
			} else {
				$parsed = $edi->parse_edi();
			}
			
			if( $parsed )
				echo '<h3>Parsed EDI</h3>
					<div class="well well-sm">
					'.$edi->dump_edi( $parsed ).'
					</div>';
			else
				echo "<br><h2>Error: ",  $edi->getMessage(), "</h2>";
			
			
		}
		
	}


?>
</div>

	<div class="modal fade bs-example-modal-sm" tabindex="-1" role="dialog" aria-labelledby="mySmallModalLabel" aria-hidden="true" id="listftp_modal">
	  <div class="modal-dialog modal-lg">
		<div class="modal-content">
		<div class="modal-header">
			<button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
			<h4 class="modal-title" id="myModalLabel"><span class="text-success"><strong>FTP Browser</strong></span></h4>
		</div>
		<div class="modal-body" style="overflow: auto; max-height: 400px;">
			<p class="text-center"><img src="images/loading.gif" alt="loading" width="125" height="125" /></p>
		</div>
		</div>
		</div>
	</div>


	<script language="JavaScript" type="text/javascript"><!--
		
		$(document).ready( function () {
			//alert(($(window).height() - 150) + "px");

			$('#EXP_EDI').dataTable({
		        "bLengthChange": false,
		        "bFilter": false,
		        "bSort": false,
		        "bInfo": false,
				//"bAutoWidth": false,
				//"bProcessing": true, 
				"sScrollX": "100%",
				"sScrollY": "250px",
				//"sScrollXInner": "120%",
		        //"lengthMenu": [<?php echo isset($sts_length_menu) ? $sts_length_menu : '25, 50, 100, 250'; ?>],
		        //"order": [[ 0, "desc" ]],
				"bPaginate": false,
				"bScrollCollapse": false,
				"bSortClasses": false		
			});
			
			$('#EXP_EDI_FTP').dataTable({
		        "bLengthChange": false,
		        "bFilter": false,
		        "bSort": false,
		        "bInfo": false,
				//"bAutoWidth": false,
				//"bProcessing": true, 
				"sScrollX": "100%",
				"sScrollY": "250px",
				"sScrollXInner": "120%",
		        //"lengthMenu": [<?php echo isset($sts_length_menu) ? $sts_length_menu : '25, 50, 100, 250'; ?>],
		        //"order": [[ 0, "desc" ]],
				"bPaginate": false,
				"bScrollCollapse": false,
				"bSortClasses": false		
			});
			
			$('ul.dropdown-menu li a.browse_ftp').on('click', function(event) {
				console.log('in external');
				event.preventDefault();
				$('#listftp_modal').modal({
					container: 'body',
					//remote: $(this).attr("href")
				}).modal('show');
				$('div.modal-body').load($(this).attr("href"));

				
				return false;					
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

