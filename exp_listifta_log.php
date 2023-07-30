<?php 

// $Id: exp_listifta_log.php 4697 2022-03-09 23:02:23Z duncan $
// List IFTA log screen

// Set flag that this is a parent file
define( '_STS_INCLUDE', 1 );

// Enable datatables buttons
define( '_STS_BUTTONS', 1 );

set_time_limit(0);
ini_set('memory_limit', '1024M');

// Setup Session
require_once( "include/sts_session_setup.php" );

require_once( "include/sts_config.php" );
$sts_debug = isset($_GET['debug']) && in_group( EXT_GROUP_DEBUG );
require_once( "include/sts_session_class.php" );
ini_set('memory_limit', '1024M');

$my_session = sts_session::getInstance( $exspeedite_db, $sts_debug );
$my_session->access_check( EXT_GROUP_ADMIN );	// Make sure we should be here

// Include highcharts, see http://www.highcharts.com
define( '_STS_HIGHCHARTS', 1 );
$sts_subtitle = "IFTA Log";
require_once( "include/header_inc.php" );
require_once( "include/navbar_inc.php" );

?>
<div class="container-full" role="main">
<?php
require_once( "include/sts_result_class.php" );
require_once( "include/sts_ifta_log_class.php" );
require_once( "include/sts_ifta_rate_class.php" );
require_once( "include/sts_tractor_class.php" );
require_once( "include/sts_company_class.php" );
require_once( "include/sts_setting_class.php" );
require_once( "include/sts_fleet_class.php" );

$company_table = sts_company::getInstance($exspeedite_db, $sts_debug);

$setting_table = sts_setting::getInstance($exspeedite_db, $sts_debug);
$sts_length_menu = $setting_table->get( 'option', 'LENGTH_MENU' );
$sts_ifta_base = $company_table->ifta_base();
$ifta_enabled = ($setting_table->get("api", "IFTA_ENABLED") == 'true');
$kt_api_key = $setting_table->get( 'api', 'KEEPTRUCKIN_KEY' );
$kt_enabled = ! empty($kt_api_key);
$multi_company = ($setting_table->get("option", "MULTI_COMPANY") == 'true');
$fleet_enabled = $setting_table->get( 'option', 'FLEET_ENABLED' ) == 'true';

if( isset($_GET["IMPORT_KT"])) {
		// Turn off output buffering
		ini_set('output_buffering', 'off');
		// Implicitly flush the buffer(s)
		ini_set('implicit_flush', true);
		ob_implicit_flush(true);
	
		if (ob_get_level() == 0) ob_start();

		echo '<h1>Import IFTA Mileage From KeepTruckin</h1>
		<br><br><br>
		<p class="text-center"><img src="images/keeptruckin.png" alt="keeptruckin" height="50" /> <img src="images/animated-arrow-right.gif" alt="animated-arrow-right" height="50" /> <img src="images/EXSPEEDITEsmr.png" alt="EXSPEEDITEsmr" width="284" height="50" /></p>
		
		<div id="loading"><p class="text-center"><img src="images/loading.gif" alt="loading" width="125" height="125" /></p></div>';
		ob_flush(); flush();
		
		$kt = sts_keeptruckin::getInstance($exspeedite_db, $sts_debug);

		$result = $kt->import_ifta( substr($_SESSION['IFTA_QUARTER'],2,4), substr($_SESSION['IFTA_QUARTER'],0,1) );
		ob_flush(); flush();
		
		sleep(2);
		update_message( 'loading', '' );
		if( $result === false ) {
			echo '<div class="container">
				<h2>Error Connecting to KeepTrukin</h2>
				<p>Details: '.$kt->errmsg.'</p>
				<p>This may be because you need to 
				<a href="exp_kt_oauth2.php" class="btn btn-success">re-authenticate with OAuth 2.0</a> <a class="btn btn-default tip" title="Go back to home page" href="exp_listtractor.php"><span class="glyphicon glyphicon-remove"></span> Back</a></p>
			</div>';
			
		} else if( $result > 0 ) {
			echo '<br>
			<p>You need to have matching VIN numbers in Exspeedite for this feature to work.</p>
			<br>
			<p class="text-center"><a class="btn btn-md btn-success" href="exp_listtractor.php"><img src="images/tractor_icon.png" alt="tractor_icon" height="20"> Tractor Profiles</a><a class="btn btn-md btn-info" href="exp_listifta_log.php"><span class="glyphicon glyphicon-ok"></span> Continue</a></p>';
		} else {
			if( $sts_debug ) die;
			reload_page ( 'exp_listifta_log.php' );
		}
		
	
} else

if( $ifta_enabled ) {
	// Turn off output buffering
	ini_set('output_buffering', 'off');
	// Implicitly flush the buffer(s)
	ini_set('implicit_flush', true);
	ob_implicit_flush(true);

	if (ob_get_level() == 0) ob_start();

	$ifta_log = sts_ifta_log_calc::getInstance( $exspeedite_db, $sts_debug );
	$ifta_rate = sts_ifta_rate::getInstance( $exspeedite_db, $sts_debug );
	$tractor_table = sts_tractor::getInstance($exspeedite_db, $sts_debug);
	
	if( isset($_POST['IFTA_QUARTER']) ) $_SESSION['IFTA_QUARTER'] = $_POST['IFTA_QUARTER'];
	else if( isset($_GET['IFTA_QUARTER']) ) $_SESSION['IFTA_QUARTER'] = $_GET['IFTA_QUARTER'];
	else if( ! isset($_SESSION['IFTA_QUARTER']) )
		$_SESSION['IFTA_QUARTER'] = $ifta_rate->get_current_quarter();
	
	echo '<div id="loading"><h3 class="text-center"><img src="images/loading.gif" alt="loading" width="125" height="125" /><br>
	Calculating IFTA Report For '.$_SESSION['IFTA_QUARTER'].'</h3></div>';
	ob_flush(); flush();

	if( $multi_company ) {
		if( isset($_POST['IFTA_COMPANY']) ) $_SESSION['IFTA_COMPANY'] = $_POST['IFTA_COMPANY'];
		if( ! isset($_SESSION['IFTA_COMPANY']) ) $_SESSION['IFTA_COMPANY'] = 0;
	}
	
	if( $fleet_enabled ) {
		if( isset($_POST['IFTA_FLEET']) ) $_SESSION['IFTA_FLEET'] = $_POST['IFTA_FLEET'];
		if( ! isset($_SESSION['IFTA_FLEET']) ) $_SESSION['IFTA_FLEET'] = 0;
	}
	
	$sts_result_ifta_log_calc_edit['filters_html'] = 
		$ifta_rate->rates_menu( $_SESSION['IFTA_QUARTER'], false, 'IFTA_QUARTER' );

	if( $multi_company ) {
		$sts_result_ifta_log_calc_edit['filters_html'] .=
			$company_table->menu( $_SESSION['IFTA_COMPANY'], 'IFTA_COMPANY', '', true, true );
	}
		
	if( $fleet_enabled ) {
		$fleet_table = sts_fleet::getInstance($exspeedite_db, $sts_debug);
		$sts_ifta_base = $fleet_table->ifta_base();
		$sts_result_ifta_log_calc_edit['filters_html'] .=
			$fleet_table->menu( $_SESSION['IFTA_FLEET'], 'IFTA_FLEET', '', true, true );
	}
		
	$sts_result_ifta_log_calc_edit['filters_html'] .=
		'<a class="btn btn-sm btn-info" href="exp_import_ifta.php?IFTA_QUARTER='.$_SESSION['IFTA_QUARTER'].'&view=on"><span class="glyphicon glyphicon-th-list"></span> IFTA Rates</a><a class="btn btn-sm btn-info" href="exp_map_ifta_log.php"><span class="glyphicon glyphicon-picture"></span> IFTA Map</a>';

	if( $kt_enabled ) {
		$sts_result_ifta_log_calc_edit['filters_html'] .=
		'<a class="btn btn-sm btn-default" href="exp_listifta_log.php?IMPORT_KT"><img src="images/keeptruckin.png" alt="keeptruckin" height="18" /></a>';	
	}
		
	$match = "YEAR(IFTA_DATE) = ".substr($_SESSION['IFTA_QUARTER'],2,4)." AND QUARTER(IFTA_DATE) = ".substr($_SESSION['IFTA_QUARTER'],0,1);
	
	if( $multi_company && $_SESSION['IFTA_COMPANY'] > 0 ) {
		$match .= " AND (SELECT COMPANY_CODE FROM EXP_TRACTOR
			WHERE TRACTOR_CODE = IFTA_TRACTOR) = ".$_SESSION['IFTA_COMPANY'];
	}

	if( $fleet_enabled && $_SESSION['IFTA_FLEET'] > 0 ) {
		$match .= " AND (SELECT FLEET_CODE FROM EXP_TRACTOR
			WHERE TRACTOR_CODE = IFTA_TRACTOR) = ".$_SESSION['IFTA_FLEET'];
	}

	$rslt = new sts_result( $ifta_log, $match, $sts_debug );
	echo $rslt->render( $sts_result_ifta_log_calc_layout, $sts_result_ifta_log_calc_edit );
	update_message( 'loading', '' );
	
	$result = $rslt->last_result();
	$mpg1 = isset($result[0]["MPG"]) ? $result[0]["MPG"] : 'unknown';
	$tot_dist = 0.0;
	$tot_purchased = 0.0;
	$tot_tax = 0.0;
	if( is_array($result) && count($result) > 0 ) {
		foreach($result as $row) {
			$tot_dist += $row["DIST_TRAVELED"];
			$tot_purchased += $row["FUEL_PURCHASED"];
			$tot_tax += $row["TOTAL_TAX"];
		}
		
		$trucks = $ifta_log->database->get_multiple_rows("
			SELECT COALESCE(L.IFTA_TRACTOR, 'Total') TRACTOR_CODE, T.UNIT_NUMBER, T.FUEL_TYPE,
			L.ENTRIES, L.DIST, L.FUEL, L.MPG
			FROM
			(SELECT IFTA_TRACTOR, COUNT(*) ENTRIES,
				SUM(DIST_TRAVELED) DIST, SUM(FUEL_PURCHASED) FUEL,
				ROUND(SUM(DIST_TRAVELED) / SUM(FUEL_PURCHASED), 2) MPG
			FROM EXP_IFTA_LOG L
			WHERE ".$match."
			AND IFTA_TRACTOR > 0
			GROUP BY IFTA_TRACTOR WITH ROLLUP) L
			LEFT JOIN EXP_TRACTOR T
			ON L.IFTA_TRACTOR = T.TRACTOR_CODE");
		
		$left = '<h4>Active Tractors This Quarter</h4>
		<div class="table-responsive">
				<table class="display table table-striped table-condensed table-bordered table-hover" id="SUMMARY" style="width: auto;">
				<thead><tr class="exspeedite-bg"><th>Tractor</th><th>Fuel Type</th><th>Distance</th><th>Fuel</th><th>MPG</th></tr>
				</thead>
				<tbody>';
		$miles = array();
		$gallons = array();
		$mpg = array();
		foreach( $trucks as $row ) {
			//! SCR# 223 - do not show or use totals from this query
			if( $row["TRACTOR_CODE"] != 'Total' ) {
				$left .= '<tr>'.($row["TRACTOR_CODE"] == 'Total' ? '<td class="h4">Total</td>' : '<td><a href="exp_editifta_log.php?TRACTOR_CODE='.$row["TRACTOR_CODE"].'">'.$row["UNIT_NUMBER"].'</a></td>').'
					<td class="text-right'.($row["TRACTOR_CODE"] == 'Total' ? ' h4' : '').'">'.$row["FUEL_TYPE"].'</td>
					<td class="text-right'.($row["TRACTOR_CODE"] == 'Total' ? ' h4' : '').'">'.number_format($row["DIST"],0).' mi</td>
					<td class="text-right'.($row["TRACTOR_CODE"] == 'Total' ? ' h4' : '').'">'.number_format($row["FUEL"],0).' gal</td>
					<td class="text-right'.($row["TRACTOR_CODE"] == 'Total' ? ' h4' : '').'">'.$row["MPG"].'</td><tr>';
				$miles[$row["UNIT_NUMBER"]] = $row["DIST"];
				$gallons[$row["UNIT_NUMBER"]] = $row["FUEL"];
				$mpg[$row["UNIT_NUMBER"]] = $row["MPG"];
			}
		}		
			$left .= '</tbody>
			</table>
			</div>';
		
		$right = '<h4>Totals</h4>
		<div class="table-responsive">
				<table class="display table table-striped table-condensed table-bordered table-hover" id="SUMMARY" style="width: auto;">
				<thead>
				<tr class="exspeedite-bg"><th>Base Jurisdiction</th><th>Total Distance</th><th>Total Fuel</th><th>MPG</th><th>Total Tax</th></tr>
				</thead>
				<tbody>';
		$right .= '<tr><td class="text-center h4">'.$sts_ifta_base.'</td>
					<td class="text-right h4">'.number_format($tot_dist,0).' mi</td>
					<td class="text-right h4">'.number_format($tot_purchased,0).' gal</td>
					<td class="text-right h4">'.$mpg1.'</td>
					<td class="text-right h4">$ '.$tot_tax.'</td><tr>
					</tbody>
					</table>
					</div>';
		
		echo '<div class="row">
				<div class="col-sm-6">
					'.$left.'
				</div>
				<div class="col-sm-6">
					'.$right.'
					<div id="mpg_chart"></div>
				</div>
			</div>
			<div class="row">
				<div class="col-sm-6">
					<div id="miles_chart"></div>
				</div>
				<div class="col-sm-6">
					<div id="gallons_chart"></div>
				</div>
			</div>';
		
		arsort( $mpg );
		
		$miles_series = array();
		foreach($miles as $tr_unit => $tr_miles) {
			$miles_series[] = "[ '".$tr_unit."', ".$tr_miles." ]";
		}
		
		$gallons_series = array();
		foreach($gallons as $tr_unit => $tr_gallons) {
			$gallons_series[] = "[ '".$tr_unit."', ".$tr_gallons." ]";
		}
		
		echo "<script language=\"JavaScript\" type=\"text/javascript\"><!--
			
			$(document).ready( function () {
				$(function () {
				    $('#mpg_chart').highcharts({
						chart: {
				            type: 'column',
				            options3d: {
				                enabled: true,
				                alpha: 15,
				                beta: 15,
				                depth: 50,
				                viewDistance: 25
				            }
				        },
				        title: {
				            text: 'MPG for ".$_SESSION['IFTA_QUARTER']."'
				        },
				        xAxis: {
				            categories: ['".implode('\', \'', array_keys($mpg))."'],
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
				                text: 'MPG'
				            },
				        },
				        legend: {
				            enabled: false
				        },
				        plotOptions: {
				            column: {
				                depth: 25
				            }
				        },
				        series: [{
							name: 'MPG',
							data: [".implode(',', $mpg)."]
				        }]
				    });
	
				    $('#miles_chart').highcharts({
						chart: {
				            type: 'pie',
				            options3d: {
				                enabled: true,
				                alpha: 45,
				                beta: 0
				            }
	    		        },
				        title: {
				            text: 'Miles By Tractor'
				        },
						tooltip: {
							pointFormat: '{series.name}: <b>{point.percentage:.1f}%</b>'
						},
						plotOptions: {
						    pie: {
						        allowPointSelect: true,
						        cursor: 'pointer',
						        depth: 35,
						        dataLabels: {
						            enabled: true,
						            format: '{point.name}'
						        }
						    }
						},
						series: [{
						    type: 'pie',
						    name: 'Miles',
						    data: [
						        ".implode(',', $miles_series)."
						    ]
						}]
				    });
	
				    $('#gallons_chart').highcharts({
						chart: {
				            type: 'pie',
				            options3d: {
				                enabled: true,
				                alpha: 45,
				                beta: 0
				            }
	    		        },
				        title: {
				            text: 'Gallons By Tractor'
				        },
						tooltip: {
							pointFormat: '{series.name}: <b>{point.percentage:.1f}%</b>'
						},
						plotOptions: {
						    pie: {
						        allowPointSelect: true,
						        cursor: 'pointer',
						        depth: 35,
						        dataLabels: {
						            enabled: true,
						            format: '{point.name}'
						        }
						    }
						},
						series: [{
						    type: 'pie',
						    name: 'Gallons',
						    data: [
						        ".implode(',', $gallons_series)."
						    ]
						}]
				    });
				});
			});
		//--></script>
	
		";
	}


?>
</div>
	<script language="JavaScript" type="text/javascript"><!--
		
		$(document).ready( function () {
			//alert(($(window).height() - 150) + "px");
			<?php if( ! $sts_debug ) { ?>
			//document.documentElement.style.overflow = 'hidden';  // firefox, chrome
			//document.body.scroll = "no"; // ie only
			<?php } ?>

			$('#EXP_IFTA_LOG').dataTable({
		        //"bLengthChange": false,
		        "bFilter": true,
		        "bSort": true,
		        "bInfo": false,
				"bAutoWidth": false,
				//"bProcessing": true, 
				"sScrollX": "100%",
				"sScrollY": ($(window).height() - 300) + "px",
				//"sScrollXInner": "120%",
		        "lengthMenu": [<?php echo isset($sts_length_menu) ? $sts_length_menu : '25, 50, 100, 250'; ?>],
				"bPaginate": true,
				"bScrollCollapse": true,
				"bSortClasses": false,
				"dom": "Bfrtip",
				buttons: [
					'csv', 'print'
				],
			});
			
			if( false && window.HANDLE_RESIZE_EVENTS ) {
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

