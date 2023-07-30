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
define( '_STS_GOOGLE_CHARTS', 1 );
$sts_subtitle = "SCR KPIs";
require_once( "include/header_inc.php" );
require_once( "include/navbar_inc.php" );

?>
<div class="container" role="main">
<?php
require_once( "include/sts_result_class.php" );
require_once( "include/sts_scr_class.php" );

$scr_table = sts_scr::getInstance($exspeedite_db, $sts_debug);

$result = $scr_table->database->get_multiple_rows("
	SELECT D.DT, COALESCE(NEWSCRS,0) NEWSCRS, COALESCE(DEADSCRS,0) DEADSCRS, COALESCE(FIXEDSCRS, 0) FIXEDSCRS,
		COALESCE(BUILD, 0) BUILD
	FROM
	(SELECT DATE(H.CREATED_DATE) DT
	FROM EXP_SCR_HISTORY H, EXP_STATUS_CODES C
	WHERE H.SCR_STATUS = C.STATUS_CODES_CODE
	AND DATE(H.CREATED_DATE) > DATE_SUB(CURDATE(), INTERVAL 1 MONTH)
	GROUP BY DATE(H.CREATED_DATE)
	ORDER BY DATE(H.CREATED_DATE) ASC) D
	
	LEFT OUTER JOIN
	
	(SELECT DATE(H.CREATED_DATE) DT, COUNT(*) NEWSCRS
	FROM EXP_SCR_HISTORY H, EXP_STATUS_CODES C
	WHERE H.SCR_STATUS = C.STATUS_CODES_CODE
	AND C.BEHAVIOR = 'entry'
	AND DATE(H.CREATED_DATE) > DATE_SUB(CURDATE(), INTERVAL 1 MONTH)
	GROUP BY DATE(H.CREATED_DATE)
	ORDER BY DATE(H.CREATED_DATE) ASC) N
	ON D.DT = N.DT
	
	LEFT OUTER JOIN
	
	(SELECT DATE(H.CREATED_DATE) DT, COUNT(*) DEADSCRS
	FROM EXP_SCR_HISTORY H, EXP_STATUS_CODES C
	WHERE H.SCR_STATUS = C.STATUS_CODES_CODE
	AND C.BEHAVIOR = 'dead'
	AND DATE(H.CREATED_DATE) > DATE_SUB(CURDATE(), INTERVAL 1 MONTH)
	GROUP BY DATE(H.CREATED_DATE)
	ORDER BY DATE(H.CREATED_DATE) ASC) X
	ON D.DT = X.DT
	
	LEFT OUTER JOIN
	(SELECT DATE(H.CREATED_DATE) DT, COUNT(*) FIXEDSCRS
	FROM EXP_SCR_HISTORY H, EXP_STATUS_CODES C
	WHERE H.SCR_STATUS = C.STATUS_CODES_CODE
	AND C.BEHAVIOR = 'fixed'
	AND DATE(H.CREATED_DATE) > DATE_SUB(CURDATE(), INTERVAL 1 MONTH)
	GROUP BY DATE(H.CREATED_DATE)
	ORDER BY DATE(H.CREATED_DATE) ASC) F
	ON D.DT = F.DT
	
	LEFT OUTER JOIN
	(SELECT DATE(H.CREATED_DATE) DT, COUNT(*) BUILD
	FROM EXP_SCR_HISTORY H, EXP_STATUS_CODES C
	WHERE H.SCR_STATUS = C.STATUS_CODES_CODE
	AND C.BEHAVIOR = 'build'
	AND DATE(H.CREATED_DATE) > DATE_SUB(CURDATE(), INTERVAL 1 MONTH)
	GROUP BY DATE(H.CREATED_DATE)
	ORDER BY DATE(H.CREATED_DATE) ASC) R
	ON D.DT = R.DT");

if( defined('_STS_GOOGLE_CHARTS') && count($result) > 0 ) {
	
	echo '<h3><img src="images/bug_icon.png" alt="bug_icon" height="32"> SCR KPIs <a class="btn btn-sm btn-default" href="exp_listscr.php"><span class="glyphicon glyphicon-arrow-left"></span> Back</a></h3>
	
	<div class="well well-lg tighter">
	<div id="chart1" style="min-width: 310px; height: 300px; margin: 0 auto"></div>
	</div>';
	echo "<script language=\"JavaScript\" type=\"text/javascript\"><!--
		
		$(document).ready( function () {
			/*  @param {string} s - an ISO 8001 format date and time string
			**                      with all components, e.g. 2015-11-24T19:40:00
			**  @returns {Date} - Date instance from parsing the string. May be NaN.
			*/
			function parseISOLocal(s) {
			  var b = s.split(/\\D/);
			  return new Date(b[0], b[1]-1, b[2]);
			}

			google.charts.setOnLoadCallback(drawChart);
			function drawChart() {
			var data = google.visualization.arrayToDataTable([
				['Date', 'New SCRs', 'Dead SCRs', 'Fixed SCRs', 'In Build'],
				";
			foreach( $result as $row ) {
				echo "[parseISOLocal('".$row["DT"]."'), ".$row["NEWSCRS"].", ".$row["DEADSCRS"].", ".$row["FIXEDSCRS"].", ".$row["BUILD"]."],
				";
			}
				
			echo "
			]);
			console.log(data);
			
			var options = {
				title: 'New/Dead/Fixed/In Build SCRs In Last Month',
				height: 300,
				chartArea:{left:80,top:40,width:'90%',height:'60%'},
				legend: { position: 'bottom', maxLines: 2 },
				isStacked: true,
				bar: { groupWidth: '90%' },
				hAxis: {
					format: 'M/d/yy',
					gridlines: {count: 15},
					slantedText: true, 
					slantedTextAngle: 60 // here you can even use 180 
				},
				vAxis: {
					minValue: 0,
					title: 'SCRs'
				}
			};
			
			var chart = new google.visualization.ColumnChart($('#chart1')[0]);
			chart.draw(data, options);
			}


		});
	//--></script>
	";
}

$result2 = $scr_table->database->get_multiple_rows("
	SELECT C.STATUS_STATE, COUNT(*) NUM,
	ROUND(COUNT(*) / (SELECT COUNT(*) FROM EXP_SCR
		WHERE  DATE(CREATED_DATE) > DATE_SUB(CURDATE(), INTERVAL 1 MONTH)) * 100,1) AS PCT,
	GROUP_CONCAT(S.SCR_CODE ORDER BY S.SCR_CODE ASC SEPARATOR ', ') SCRS
	FROM EXP_SCR S, EXP_STATUS_CODES C
	WHERE S.CURRENT_STATUS = C.STATUS_CODES_CODE
	AND DATE(S.CREATED_DATE) > DATE_SUB(CURDATE(), INTERVAL 1 MONTH)
	GROUP BY C.STATUS_STATE
	ORDER BY 2 DESC");

if( defined('_STS_GOOGLE_CHARTS') && count($result2) > 0 ) {
	echo '
	
	<div class="well well-lg tighter">
		<div class="row">
			<div class="col-sm-5">
				<div id="chart2" style="height: 400px; margin: 0 auto"></div>
			</div>
			<div class="col-sm-7">
			<div class="table-responsive  bg-white">
			<table class="display table table-striped table-condensed table-bordered table-hover table-nobm" id="REVENUE">
			<thead><tr class="exspeedite-bg">
			<th>Status</th>
			<th class="text-right">SCRs</th>
			<th class="text-right">%</th>
			<th>SCRs</th>
			</tr>
			</thead>
			<tbody>';

			foreach($result2 as $row) {
				$scrs = explode(', ', $row['SCRS']);
				$linked = array();
				foreach( $scrs as $scr) {
					$linked[] = '<a href="exp_editscr.php?CODE='.$scr.'">'.$scr.'</a>';
				}
				
				echo '<tr>
						<td>'.$row['STATUS_STATE'].'</td>
						<td class="text-right">'.$row['NUM'].'</td>
						<td class="text-right">'.$row['PCT'].'</td>
						<td>'.implode(', ', $linked).'</td>
					</tr>';
			}

	echo '
	   		</tbody>
			</table>
			</div>

			</div>
		</div>
	</div>';
	echo "<script language=\"JavaScript\" type=\"text/javascript\"><!--
		
		$(document).ready( function () {

			google.charts.setOnLoadCallback(drawChart2);
			function drawChart2() {
			var data = google.visualization.arrayToDataTable([
				['Status', 'SCRs'],
				";
			foreach( $result2 as $row ) {
				echo "['".$row["STATUS"]."', ".$row["NUM"]."],
				";
			}
				
			echo "
			]);

		        var options = {
		          title: 'SCRs In Last 30 Days',
				  legend: { position: 'bottom', maxLines: 2 },
				  chartArea:{left:20,top:40,width:'90%',height:'80%'},
		        };
		
		        var chart = new google.visualization.PieChart($('#chart2')[0]);
		
		        chart.draw(data, options);
			}


		});
	//--></script>
	";
}
	
$result2a = $scr_table->database->get_multiple_rows("
	SELECT COALESCE(S.SCR_CLIENT, '') AS SCR_CLIENT, COUNT(*) NUM,
		ROUND(COUNT(*) / (SELECT COUNT(*) FROM EXP_SCR 
		WHERE  DATE(CREATED_DATE) > DATE_SUB(CURDATE(), INTERVAL 1 MONTH)) * 100,1) AS PCT,
		GROUP_CONCAT(S.SCR_CODE ORDER BY S.SCR_CODE ASC SEPARATOR ', ') SCRS
	FROM EXP_SCR S
	WHERE DATE(S.CREATED_DATE) > DATE_SUB(CURDATE(), INTERVAL 1 MONTH)
	GROUP BY S.SCR_CLIENT
    ORDER BY 2 DESC");

if( defined('_STS_GOOGLE_CHARTS') && count($result2a) > 0 ) {
	echo '
	
	<div class="well well-lg tighter">
		<div class="row">
			<div class="col-sm-5">
				<div id="chart2a" style="height: 400px; margin: 0 auto"></div>
			</div>
			<div class="col-sm-7">
			<div class="table-responsive  bg-white">
			<table class="display table table-striped table-condensed table-bordered table-hover table-nobm" id="REVENUE">
			<thead><tr class="exspeedite-bg">
			<th>Client</th>
			<th class="text-right">SCRs</th>
			<th class="text-right">%</th>
			<th>SCRs</th>
			</tr>
			</thead>
			<tbody>';

			foreach($result2a as $row) {
				$scrs = explode(', ', $row['SCRS']);
				$linked = array();
				foreach( $scrs as $scr) {
					$linked[] = '<a href="exp_editscr.php?CODE='.$scr.'">'.$scr.'</a>';
				}
				
				echo '<tr>
						<td>'.$row['SCR_CLIENT'].'</td>
						<td class="text-right">'.$row['NUM'].'</td>
						<td class="text-right">'.$row['PCT'].'</td>
						<td>'.implode(', ', $linked).'</td>
					</tr>';
			}

	echo '
	   		</tbody>
			</table>
			</div>

			</div>
		</div>
	</div>';
	echo "<script language=\"JavaScript\" type=\"text/javascript\"><!--
		
		$(document).ready( function () {

			google.charts.setOnLoadCallback(drawChart2);
			function drawChart2() {
			var data = google.visualization.arrayToDataTable([
				['Client', 'SCRs'],
				";
			foreach( $result2a as $row ) {
				echo "['".$row["SCR_CLIENT"]."', ".$row["NUM"]."],
				";
			}
				
			echo "
			]);

		        var options = {
		          title: 'SCRs By Client In Last 30 Days',
				  legend: { position: 'bottom', maxLines: 2 },
				  chartArea:{left:20,top:40,width:'90%',height:'80%'},
		        };
		
		        var chart2a = new google.visualization.PieChart($('#chart2a')[0]);
		
		        chart2a.draw(data, options);
			}


		});
	//--></script>
	";
}
	
$result2b = $scr_table->database->get_multiple_rows("
	SELECT COALESCE(S.FOUND_IN_RELEASE, '') AS FOUND_IN_RELEASE, COUNT(*) NUM,
		ROUND(COUNT(*) / (SELECT COUNT(*) FROM EXP_SCR 
		WHERE  DATE(CREATED_DATE) > DATE_SUB(CURDATE(), INTERVAL 1 MONTH)) * 100,1) AS PCT,
		GROUP_CONCAT(S.SCR_CODE ORDER BY S.SCR_CODE ASC SEPARATOR ', ') SCRS
	FROM EXP_SCR S
	WHERE DATE(S.CREATED_DATE) > DATE_SUB(CURDATE(), INTERVAL 1 MONTH)
	GROUP BY S.FOUND_IN_RELEASE
    ORDER BY 2 DESC");

if( defined('_STS_GOOGLE_CHARTS') && count($result2b) > 0 ) {
	echo '
	
	<div class="well well-lg tighter">
		<div class="row">
			<div class="col-sm-5">
				<div id="chart2b" style="height: 400px; margin: 0 auto"></div>
			</div>
			<div class="col-sm-7">
			<div class="table-responsive  bg-white">
			<table class="display table table-striped table-condensed table-bordered table-hover table-nobm" id="REVENUE">
			<thead><tr class="exspeedite-bg">
			<th>Found&nbsp;in</th>
			<th class="text-right">SCRs</th>
			<th class="text-right">%</th>
			<th>SCRs</th>
			</tr>
			</thead>
			<tbody>';

			foreach($result2b as $row) {
				$scrs = explode(', ', $row['SCRS']);
				$linked = array();
				foreach( $scrs as $scr) {
					$linked[] = '<a href="exp_editscr.php?CODE='.$scr.'">'.$scr.'</a>';
				}
				
				echo '<tr>
						<td>'.$row['FOUND_IN_RELEASE'].'</td>
						<td class="text-right">'.$row['NUM'].'</td>
						<td class="text-right">'.$row['PCT'].'</td>
						<td>'.implode(', ', $linked).'</td>
					</tr>';
			}

	echo '
	   		</tbody>
			</table>
			</div>

			</div>
		</div>
	</div>';
	echo "<script language=\"JavaScript\" type=\"text/javascript\"><!--
		
		$(document).ready( function () {

			google.charts.setOnLoadCallback(drawChart2);
			function drawChart2() {
			var data = google.visualization.arrayToDataTable([
				['Found in', 'SCRs'],
				";
			foreach( $result2b as $row ) {
				echo "['".$row["FOUND_IN_RELEASE"]."', ".$row["NUM"]."],
				";
			}
				
			echo "
			]);

		        var options = {
		          title: 'SCRs Found in Release In Last 30 Days',
				  legend: { position: 'bottom', maxLines: 2 },
				  chartArea:{left:20,top:40,width:'90%',height:'80%'},
		        };
		
		        var chart2b = new google.visualization.PieChart($('#chart2b')[0]);
		
		        chart2b.draw(data, options);
			}


		});
	//--></script>
	";
}
	
$result3 = $scr_table->database->get_multiple_rows("
	SELECT S.SCR_TYPE, COUNT(*) NUM,
	ROUND(COUNT(*) / (SELECT COUNT(*) FROM EXP_SCR
		WHERE  DATE(CREATED_DATE) > DATE_SUB(CURDATE(), INTERVAL 1 MONTH)) * 100,1) AS PCT,
	GROUP_CONCAT(S.SCR_CODE ORDER BY S.SCR_CODE ASC SEPARATOR ', ') SCRS
	FROM EXP_SCR S
	WHERE DATE(S.CREATED_DATE) > DATE_SUB(CURDATE(), INTERVAL 1 MONTH)
	GROUP BY S.SCR_TYPE
	ORDER BY 2 DESC");

$result4 = $scr_table->database->get_multiple_rows("
	SELECT S.SEVERITY, COUNT(*) NUM,
	ROUND(COUNT(*) / (SELECT COUNT(*) FROM EXP_SCR
		WHERE  DATE(CREATED_DATE) > DATE_SUB(CURDATE(), INTERVAL 1 MONTH)) * 100,1) AS PCT,
	GROUP_CONCAT(S.SCR_CODE ORDER BY S.SCR_CODE ASC SEPARATOR ', ') SCRS
	FROM EXP_SCR S
	WHERE DATE(S.CREATED_DATE) > DATE_SUB(CURDATE(), INTERVAL 1 MONTH)
	GROUP BY S.SEVERITY
	ORDER BY 1 ASC");

$result5 = $scr_table->database->get_multiple_rows("
	SELECT S.FIXED_IN_RELEASE, COUNT(*) NUM,
	ROUND(COUNT(*) / (SELECT COUNT(*) FROM EXP_SCR
		WHERE  DATE(CREATED_DATE) > DATE_SUB(CURDATE(), INTERVAL 1 MONTH)) * 100,1) AS PCT,
	GROUP_CONCAT(S.SCR_CODE ORDER BY S.SCR_CODE ASC SEPARATOR ', ') SCRS
	FROM EXP_SCR S
	WHERE DATE(S.CREATED_DATE) > DATE_SUB(CURDATE(), INTERVAL 1 MONTH)
	AND CURRENT_STATUS = (SELECT STATUS_CODES_CODE
	FROM EXP_STATUS_CODES
	WHERE BEHAVIOR = 'build'
	AND SOURCE_TYPE = 'scr')
	GROUP BY S.FIXED_IN_RELEASE
	ORDER BY 1 ASC");

if( defined('_STS_GOOGLE_CHARTS') && count($result3) > 0 && count($result4) > 0
	&& count($result5) > 0) {
	echo '
	
	<div class="well well-lg tighter">
		<div class="row">
			<div class="col-sm-4">
				<div id="chart3" style="height: 360px; margin: 0 auto"></div>
			</div>
			<div class="col-sm-4">
				<div id="chart4" style="height: 360px; margin: 0 auto"></div>
			</div>
			<div class="col-sm-4">
				<div id="chart5" style="height: 360px; margin: 0 auto"></div>
			</div>
		</div>
	</div>';
	echo "<script language=\"JavaScript\" type=\"text/javascript\"><!--
		
		$(document).ready( function () {

			google.charts.setOnLoadCallback(drawChart3);
			function drawChart3() {
			var data3 = google.visualization.arrayToDataTable([
				['Type', 'SCRs'],
				";
			foreach( $result3 as $row ) {
				echo "['".$row["SCR_TYPE"]."', ".$row["NUM"]."],
				";
			}
				
			echo "
			]);

			var data4 = google.visualization.arrayToDataTable([
				['Severity', 'SCRs'],
				";
			foreach( $result4 as $row ) {
				echo "['".$row["SEVERITY"]."', ".$row["NUM"]."],
				";
			}
				
			echo "
			]);

			var data5 = google.visualization.arrayToDataTable([
				['Release', 'SCRs'],
				";
			foreach( $result5 as $row ) {
				echo "['".$row["FIXED_IN_RELEASE"]."', ".$row["NUM"]."],
				";
			}
				
			echo "
			]);

		        var options = {
		          title: 'SCRs By Type In Last 30 Days',
				  legend: { position: 'bottom', maxLines: 2 },
				  chartArea:{left:20,top:40,width:'90%',height:'80%'},
		        };
		
		        var chart3 = new google.visualization.PieChart($('#chart3')[0]);
		
		        chart3.draw(data3, options);
		        
		        options.title = 'SCRs By Severity In Last 30 Days'

		        var chart4 = new google.visualization.PieChart($('#chart4')[0]);
		
		        chart4.draw(data4, options);

		        options.title = 'SCRs Fixed in Release In Last 30 Days'

		        var chart5 = new google.visualization.PieChart($('#chart5')[0]);
		
		        chart5.draw(data5, options);
			}



		});
	//--></script>
	";
}
	

?>
</div>
	<script language="JavaScript" type="text/javascript"><!--
		
		$(document).ready( function () {

		});
	//--></script>

<?php

require_once( "include/footer_inc.php" );
?>

