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

// Use Google Charts, see https://developers.google.com/chart
// also https://developers.google.com/chart/interactive/docs/gallery/geochart
define( '_STS_GOOGLE_CHARTS', 1 );
$sts_subtitle = "Map IFTA Log";
require_once( "include/header_inc.php" );
require_once( "include/navbar_inc.php" );

	// Turn off output buffering
	ini_set('output_buffering', 'off');
	// Implicitly flush the buffer(s)
	ini_set('implicit_flush', true);
	ob_implicit_flush(true);

	if (ob_get_level() == 0) ob_start();

?>
<div class="container theme-showcase" role="main">
<?php
require_once( "include/sts_result_class.php" );
require_once( "include/sts_ifta_log_class.php" );
require_once( "include/sts_ifta_rate_class.php" );
require_once( "include/sts_company_class.php" );
require_once( "include/sts_setting_class.php" );
require_once( "include/sts_fleet_class.php" );

$company_table = sts_company::getInstance($exspeedite_db, $sts_debug);

$setting_table = sts_setting::getInstance($exspeedite_db, $sts_debug);
$sts_length_menu = $setting_table->get( 'option', 'LENGTH_MENU' );
$sts_ifta_base = $company_table->ifta_base();
$ifta_enabled = ($setting_table->get("api", "IFTA_ENABLED") == 'true');
$multi_company = ($setting_table->get("option", "MULTI_COMPANY") == 'true');
$fleet_enabled = $setting_table->get( 'option', 'FLEET_ENABLED' ) == 'true';

if( $ifta_enabled ) {
	$ifta_log = sts_ifta_log_calc::getInstance( $exspeedite_db, $sts_debug );
	$ifta_rate = sts_ifta_rate::getInstance( $exspeedite_db, $sts_debug );
	
	if( isset($_POST['IFTA_QUARTER']) ) $_SESSION['IFTA_QUARTER'] = $_POST['IFTA_QUARTER'];
	else if( isset($_GET['IFTA_QUARTER']) ) $_SESSION['IFTA_QUARTER'] = $_GET['IFTA_QUARTER'];
	else if( ! isset($_SESSION['IFTA_QUARTER']) )
		$_SESSION['IFTA_QUARTER'] = $ifta_rate->get_current_quarter();
	
	if( $multi_company ) {
		if( isset($_POST['IFTA_COMPANY']) ) $_SESSION['IFTA_COMPANY'] = $_POST['IFTA_COMPANY'];
		if( ! isset($_SESSION['IFTA_COMPANY']) ) $_SESSION['IFTA_COMPANY'] = 0;
	}

	if( $fleet_enabled ) {
		if( isset($_POST['IFTA_FLEET']) ) $_SESSION['IFTA_FLEET'] = $_POST['IFTA_FLEET'];
		if( ! isset($_SESSION['IFTA_FLEET']) ) $_SESSION['IFTA_FLEET'] = 0;
	}

	echo '<form class="form-inline" role="form" action="exp_map_ifta_log.php" 
					method="post" enctype="multipart/form-data" 
					name="RESULT_FILTERS_EXP_IFTA_LOG" id="RESULT_FILTERS_EXP_IFTA_LOG">
			<input name="FILTERS_EXP_IFTA_LOG" type="hidden" value="on">
			<h3><img src="images/iftatest.gif" alt="iftatest" height="40"> IFTA Map for 
		<div class="btn-group">
		</div>&nbsp; <div class="form-group" style="display: inline-block; margin-bottom: 0; vertical-align: middle;">'.
		$ifta_rate->rates_menu( $_SESSION['IFTA_QUARTER'], false, 'IFTA_QUARTER' ).
		($multi_company ? $company_table->menu( $_SESSION['IFTA_COMPANY'], 'IFTA_COMPANY', '', true, true ) : '');
		
	if( $fleet_enabled ) {
		$fleet_table = sts_fleet::getInstance($exspeedite_db, $sts_debug);
		$sts_ifta_base = $fleet_table->ifta_base();
		echo $fleet_table->menu( $_SESSION['IFTA_FLEET'], 'IFTA_FLEET', '', true, true );
	}
		
	echo '<a class="btn btn-sm btn-info" href="exp_listifta_log.php"><span class="glyphicon glyphicon-usd"></span> Quarterly Report</a></div>
		</h3>
		</form>
		<div id="loading"><p class="text-center"><img src="images/loading.gif" alt="loading" width="125" height="125" /></p>
		<h3 class="text-center">Fetching IFTA Data...</h3></div>';
				ob_flush(); flush();
		
	$match = "YEAR(IFTA_DATE) = ".substr($_SESSION['IFTA_QUARTER'],2,4)." AND QUARTER(IFTA_DATE) = ".substr($_SESSION['IFTA_QUARTER'],0,1);
		
	if( $multi_company && $_SESSION['IFTA_COMPANY'] > 0 ) {
		$match .= " AND (SELECT COMPANY_CODE FROM EXP_TRACTOR
			WHERE TRACTOR_CODE = IFTA_TRACTOR) = ".$_SESSION['IFTA_COMPANY'];
	}

	if( $fleet_enabled && $_SESSION['IFTA_FLEET'] > 0 ) {
		$match .= " AND (SELECT FLEET_CODE FROM EXP_TRACTOR
			WHERE TRACTOR_CODE = IFTA_TRACTOR) = ".$_SESSION['IFTA_FLEET'];
	}

	$result = $ifta_log->fetch_rows( $match );

	//echo "<pre>";
	//var_dump($result);
	//echo "</pre>";
	
	$state = array();
	foreach( $result as $row ) {
		if( ! isset($state[$row["IFTA_JURISDICTION"]]) ) {
			$state[$row["IFTA_JURISDICTION"]]["DIST_TRAVELED"] = 0;
			$state[$row["IFTA_JURISDICTION"]]["FUEL_PURCHASED"] = 0;
			$state[$row["IFTA_JURISDICTION"]]["FUEL_USED"] = 0;
			$state[$row["IFTA_JURISDICTION"]]["TOTAL_TAX"] = 0;
		}
		$state[$row["IFTA_JURISDICTION"]]["DIST_TRAVELED"] += $row["DIST_TRAVELED"];
		$state[$row["IFTA_JURISDICTION"]]["FUEL_PURCHASED"] += $row["FUEL_PURCHASED"];
		$state[$row["IFTA_JURISDICTION"]]["FUEL_USED"] += $row["FUEL_USED"];
		$state[$row["IFTA_JURISDICTION"]]["TOTAL_TAX"] += $row["TOTAL_TAX"];

	}

	update_message( 'loading', '' );
	if( defined('_STS_GOOGLE_CHARTS') && count($state) > 0 ) {
			echo '
<!-- Nav tabs -->
<ul class="nav nav-tabs">
    <li role="presentation" class="active"><a href="#distance" aria-controls="distance" role="tab" data-toggle="tab">Distance</a></li>
    <li role="presentation"><a href="#fuel" aria-controls="fuel" role="tab" data-toggle="tab">Fuel</a></li>
</ul>

<!-- Tab panes -->
<div class="tab-content">
	<div class="tab-pane active" id="distance">
		<div class="well well-sm">
			<div id="chart1" style="width: 80%; margin: 0 auto"></div>
		</div>
	</div>
	';
	echo "<script language=\"JavaScript\" type=\"text/javascript\"><!--
		
		$(document).ready( function () {
			google.charts.setOnLoadCallback(drawRegionsMap);

			function drawRegionsMap() {
			var data = google.visualization.arrayToDataTable([
				['State', 'Distance'],
				";
			foreach( $state as $name => $row ) {
				echo "['".$name."', ".
					$row["DIST_TRAVELED"]." ],
					";
			}
				
			echo "
			]);
			
			var options = {
		        region: 'US',
		        resolution: 'provinces',
		        keepAspectRatio: true,
		        width: $('#chart1').width()
			};
			
			var chart = new google.visualization.GeoChart($('#chart1')[0]);
			chart.draw(data, options);
			}


		});
	//--></script>
	";

			echo '
	<div class="tab-pane" id="fuel">			
		<div class="well well-sm">
			<div id="chart2" style="width: 80%; margin: 0 auto"></div>
		</div>
	</div>
</div>
	';
	echo "<script language=\"JavaScript\" type=\"text/javascript\"><!--
		
		$(document).ready( function () {
			google.charts.setOnLoadCallback(drawRegionsMap2);

			function drawRegionsMap2() {
			var data = google.visualization.arrayToDataTable([
				['State', 'Fuel Purchased', 'Fuel Used'],
				";
			foreach( $state as $name => $row ) {
				echo "['".$name."', ".
					$row["FUEL_PURCHASED"].", ".
					$row["FUEL_USED"]." ],
					";
			}
				
			echo "
			]);
			
			var options = {
		        region: 'US',
		        resolution: 'provinces',
		        keepAspectRatio: true,
		        width: $('#chart1').width()
			};
			
			var chart2 = new google.visualization.GeoChart($('#chart2')[0]);
			chart2.draw(data, options);
			}


		});
	//--></script>
	";

	} else {
		echo "<h3>No data to draw a map with.</h3>
		<p>Try a different quarter, company or fleet</p>";
	}
	

?>
</div>

<?php
}
require_once( "include/footer_inc.php" );
?>

