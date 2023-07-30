<?php 
// Set flag that this is a parent file
define( '_STS_INCLUDE', 1 );

// Setup Session
require_once( "include/sts_session_setup.php" );

require_once( "include/sts_config.php" );
$sts_debug = (isset($_GET['debug'])  || isset($_POST['debug']))  && in_group( EXT_GROUP_DEBUG );
require_once( "include/sts_session_class.php" );

$my_session = sts_session::getInstance( $exspeedite_db, $sts_debug );
$my_session->access_check( EXT_GROUP_ADMIN );	// Make sure we should be here

$sts_subtitle = "PC*Miler / Google Cache Info";
require_once( "include/header_inc.php" );
require_once( "include/navbar_inc.php" );

?>

<div class="container" role="main">

<h2><span class="glyphicon glyphicon-wrench"></span> PC*Miler / Google Cache Info <div class="btn-group"><a class="btn btn-md btn-success" href="exp_pcm_cache.php"><span class="glyphicon glyphicon-refresh"></span></a><a class="btn btn-md btn-danger"  href="exp_pcm_cache.php?TIDYCACHE"><span class="glyphicon glyphicon-wrench"></span> Remove expired entries</a><a class="btn btn-md btn-danger"  href="exp_pcm_cache.php?EMPTYCACHE"><span class="glyphicon glyphicon-wrench"></span> Remove ALL entries</a></div></h2>

<?php
require_once( "include/sts_zip_class.php" );

$cache = sts_validate_cache::getInstance($exspeedite_db, $sts_debug);
$dist_cache = sts_distance_cache::getInstance($exspeedite_db, $sts_debug);

if( isset($_GET["TIDYCACHE"])) {
	$result = $cache->tidy();
	$result = $dist_cache->tidy();
} else if( isset($_GET["EMPTYCACHE"])) {
	$exspeedite_db->get_one_row("TRUNCATE EXP_PCM_CACHE");
	$exspeedite_db->get_one_row("TRUNCATE EXP_PCM_DISTANCE_CACHE");
}


$result = $cache->database->get_multiple_rows( 
	"select AVG(PCM_TIMING) AS AVG_PCM_TIMING, MAX(PCM_TIMING) AS MAX_PCM_TIMING, 
		COUNT(PCM_TIMING) AS CNT_PCM_TIMING, AVG(ACCESS_TIMING) AS AVG_ACCESS_TIMING, 
		MAX(ACCESS_TIMING) AS MAX_ACCESS_TIMING, COUNT(ACCESS_TIMING) AS CNT_ACCESS_TIMING,
		SUM(ACCESS_COUNT) AS SUM_ACCESS_COUNT
	from ( select COALESCE(PCM_TIMING,0) AS PCM_TIMING, 
	COALESCE(ACCESS_TIMING, 0) AS ACCESS_TIMING,
	COALESCE(ACCESS_COUNT, 0) AS ACCESS_COUNT
	from EXP_PCM_CACHE) x" );

$result2 = $cache->fetch_rows("", "COUNT(*) AS CNT_ENTRIES, AVG(DATEDIFF( NOW(), CREATED_DATE)) AVG_AGE");
	
if( is_array($result) && count($result) == 1 ) {
	$savings = ($result[0]["AVG_PCM_TIMING"] - $result[0]["AVG_ACCESS_TIMING"]) * $result[0]["SUM_ACCESS_COUNT"];
	$factor = $result[0]["AVG_ACCESS_TIMING"] > 0  ? $result[0]["AVG_PCM_TIMING"] / $result[0]["AVG_ACCESS_TIMING"] : 0;
	
	$count_entries = $avg_age = 0;
	if( is_array($result2) && count($result2) == 1 ) {
		$count_entries = $result2[0]["CNT_ENTRIES"];
		$avg_age = $result2[0]["AVG_AGE"];
	}
	$max_time = max(0.25, $result[0]["AVG_PCM_TIMING"], $result[0]["AVG_ACCESS_TIMING"]);
	$bar_pcm = $result[0]["AVG_PCM_TIMING"] / $max_time * 100;
	$bar_cache = $result[0]["AVG_ACCESS_TIMING"] / $max_time * 100;

	echo '<div class="row">
	<div class="col-sm-5">
		<div class="panel panel-primary">
			<div class="panel-heading">
		    	<h3 class="panel-title">PC*Miler / Google Validation Cache</h3>
		    </div>
			<div class="panel-body">
			
				<div class="row">
					<div class="col-sm-2">PCM</div>
					<div class="col-sm-10">
						<div class="progress">
							<div class="progress-bar progress-bar-danger" role="progressbar" aria-valuenow="'.number_format((float) $bar_pcm,0).'" aria-valuemin="0" aria-valuemax="100" style="width: '.number_format((float) $bar_pcm,0).'%;">
								'.number_format((float) $result[0]["AVG_PCM_TIMING"],4).'s
							</div>
						</div>
					</div>
				</div>		
				<div class="row">
					<div class="col-sm-2">Cache</div>
					<div class="col-sm-10">
						<div class="progress">
							<div class="progress-bar progress-bar-success" role="progressbar" aria-valuenow="'.number_format((float) $bar_cache,0).'" aria-valuemin="0" aria-valuemax="100" style="width: '.number_format((float) $bar_cache,0).'%;">
								<span class="text-danger">'.number_format((float) $result[0]["AVG_ACCESS_TIMING"],4).'s</span>
							</div>
						</div>			
					</div>
				</div>		
			
				<div class="row">
					<div class="col-sm-8">Average time to access PC*Miler / Google</div>
					<div class="col-sm-4 text-right">'.number_format((float) $result[0]["AVG_PCM_TIMING"],4).'s</div>
				</div>
				<div class="row">
					<div class="col-sm-8">Max time to access PC*Miler / Google</div>
					<div class="col-sm-4 text-right">'.number_format((float) $result[0]["MAX_PCM_TIMING"],4).'s</div>
				</div>
				<div class="row">
					<div class="col-sm-8">Average time to access DB cache</div>
					<div class="col-sm-4 text-right">'.number_format((float) $result[0]["AVG_ACCESS_TIMING"],4).'s</div>
				</div>
				<div class="row">
					<div class="col-sm-8">Max time to access DB cache</div>
					<div class="col-sm-4 text-right">'.number_format((float) $result[0]["MAX_ACCESS_TIMING"],4).'s</div>
				</div>
				<div class="row">
					<div class="col-sm-8">Speed improvement</div>
					<div class="col-sm-4 text-right">'.number_format((float) $factor,0).' times</div>
				</div>
				<div class="row">
					<div class="col-sm-8"># of accesses to DB cache</div>
					<div class="col-sm-4 text-right">'.number_format((float) $result[0]["SUM_ACCESS_COUNT"],0).'&nbsp;</div>
				</div>
				<div class="row">
					<div class="col-sm-8">Total time savings</div>
					<div class="col-sm-4 text-right">'.number_format((float) $savings,4).'s</div>
				</div>
				<div class="row">
					<div class="col-sm-8">Number of entries cached</div>
					<div class="col-sm-4 text-right">'.number_format((float) $count_entries,0).'</div>
				</div>
				<div class="row">
					<div class="col-sm-8">Average age of entries</div>
					<div class="col-sm-4 text-right">'.number_format((float) $avg_age,1).' days</div>
				</div>
			</div>
		</div>	
	</div>';
}

$result = $dist_cache->database->get_multiple_rows( 
	"select AVG(PCM_TIMING) AS AVG_PCM_TIMING, MAX(PCM_TIMING) AS MAX_PCM_TIMING, 
		COUNT(PCM_TIMING) AS CNT_PCM_TIMING, AVG(ACCESS_TIMING) AS AVG_ACCESS_TIMING, 
		MAX(ACCESS_TIMING) AS MAX_ACCESS_TIMING, COUNT(ACCESS_TIMING) AS CNT_ACCESS_TIMING,
		SUM(ACCESS_COUNT) AS SUM_ACCESS_COUNT
	from ( select COALESCE(PCM_TIMING,0) AS PCM_TIMING, 
	COALESCE(ACCESS_TIMING, 0) AS ACCESS_TIMING,
	COALESCE(ACCESS_COUNT, 0) AS ACCESS_COUNT
	from EXP_PCM_DISTANCE_CACHE) x" );
	
$result2 = $dist_cache->fetch_rows("", "COUNT(*) AS CNT_ENTRIES, AVG(DATEDIFF( NOW(), CREATED_DATE)) AVG_AGE");
	
if( is_array($result) && count($result) == 1 ) {
	$savings = ($result[0]["AVG_PCM_TIMING"] - $result[0]["AVG_ACCESS_TIMING"]) * $result[0]["SUM_ACCESS_COUNT"];
	$factor = $result[0]["AVG_ACCESS_TIMING"] > 0  ? $result[0]["AVG_PCM_TIMING"] / $result[0]["AVG_ACCESS_TIMING"] : 0;

	$count_entries = $avg_age = 0;
	if( is_array($result2) && count($result2) == 1 ) {
		$count_entries = $result2[0]["CNT_ENTRIES"];
		$avg_age = $result2[0]["AVG_AGE"];
	}
	$max_time = max(0.25, $result[0]["AVG_PCM_TIMING"], $result[0]["AVG_ACCESS_TIMING"]);
	$bar_pcm = $result[0]["AVG_PCM_TIMING"] / $max_time * 100;
	$bar_cache = $result[0]["AVG_ACCESS_TIMING"] / $max_time * 100;

	echo '<div class="row">
	<div class="col-sm-5">
		<div class="panel panel-primary">
			<div class="panel-heading">
		    	<h3 class="panel-title">PC*Miler / Google Distance Cache</h3>
		    </div>
			<div class="panel-body">

				<div class="row">
					<div class="col-sm-2">PCM</div>
					<div class="col-sm-10">
						<div class="progress">
							<div class="progress-bar progress-bar-danger" role="progressbar" aria-valuenow="'.number_format((float) $bar_pcm,0).'" aria-valuemin="0" aria-valuemax="100" style="width: '.number_format((float) $bar_pcm,0).'%;">
								'.number_format((float) $result[0]["AVG_PCM_TIMING"],4).'s
							</div>
						</div>
					</div>
				</div>		
				<div class="row">
					<div class="col-sm-2">Cache</div>
					<div class="col-sm-10">
						<div class="progress">
							<div class="progress-bar progress-bar-success" role="progressbar" aria-valuenow="'.number_format((float) $bar_cache,0).'" aria-valuemin="0" aria-valuemax="100" style="width: '.number_format((float) $bar_cache,0).'%;">
								<span class="text-danger">'.number_format((float) $result[0]["AVG_ACCESS_TIMING"],4).'s</span>
							</div>
						</div>			
					</div>
				</div>		
			
				<div class="row">
					<div class="col-sm-8">Average time to access PC*Miler / Google</div>
					<div class="col-sm-4 text-right">'.number_format((float) $result[0]["AVG_PCM_TIMING"],4).'s</div>
				</div>
				<div class="row">
					<div class="col-sm-8">Max time to access PC*Miler / Google</div>
					<div class="col-sm-4 text-right">'.number_format((float) $result[0]["MAX_PCM_TIMING"],4).'s</div>
				</div>
				<div class="row">
					<div class="col-sm-8">Average time to access DB cache</div>
					<div class="col-sm-4 text-right">'.number_format((float) $result[0]["AVG_ACCESS_TIMING"],4).'s</div>
				</div>
				<div class="row">
					<div class="col-sm-8">Max time to access DB cache</div>
					<div class="col-sm-4 text-right">'.number_format((float) $result[0]["MAX_ACCESS_TIMING"],4).'s</div>
				</div>
				<div class="row">
					<div class="col-sm-8">Speed improvement</div>
					<div class="col-sm-4 text-right">'.number_format((float) $factor,0).' times</div>
				</div>
				<div class="row">
					<div class="col-sm-8"># of accesses to DB cache</div>
					<div class="col-sm-4 text-right">'.number_format((float) $result[0]["SUM_ACCESS_COUNT"],0).'&nbsp;</div>
				</div>
				<div class="row">
					<div class="col-sm-8">Total time savings</div>
					<div class="col-sm-4 text-right">'.number_format((float) $savings,4).'s</div>
				</div>
				<div class="row">
					<div class="col-sm-8">Number of entries cached</div>
					<div class="col-sm-4 text-right">'.number_format((float) $count_entries,0).'</div>
				</div>
				<div class="row">
					<div class="col-sm-8">Average age of entries</div>
					<div class="col-sm-4 text-right">'.number_format((float) $avg_age,1).' days</div>
				</div>
			</div>
		</div>	
	</div>
	</div>';
}

?>
<p>The validation cache keeps information on the validity of street addreses.</p>
<p>The distance cache keeps information on the distance between two street addreses.</p>
<p>Cache data is considered valid for <?php echo $cache->get_duration(); ?> days.</p>
<p>PC*MILER is a trademark of <a href="http://www.pcmiler.com/" rel="nofollow" target="_blank">ALK Technologies, Inc.</a></p>

<?php
	$valid_top_10 = $cache->top_10();
	$distance_top_10 = $dist_cache->top_10();
	if( is_array($valid_top_10) && count($valid_top_10) > 0 ) {
		echo '<div class="well well-sm">
		<h3>Top 10 Validation Cache Entries</h3>
	
	<div class="table-responsive">
		<table class="display table table-striped table-condensed table-bordered table-hover" id="EXP_VALIDATION">
			<thead><tr class="exspeedite-bg">
				<th>Address</th>
				<th>Valid</th>
				<th>Code</th>
				<th>Source</th>
				<th>Access</th>
			</thead>
			<tbody>

		';
	foreach( $valid_top_10 as $row ) {
		echo '<tr>
			<td>'.$row["addr"].'</td>';
		echo '<td>'.$row["ADDR_VALID"].'</td>';
		echo '<td>'.$row["ADDR_CODE"].'<br>'.$row["ADDR_DESCR"].'</td>';
		echo '<td>'.$row["VALID_SOURCE"].'</td>';
		echo '<td class="text-right">'.$row["access_count"].' ('.$row["pct"].'%)</td>
		</tr>';
	}
	echo '
		</tbody>
		</table>
	</div>';
		
	}

	if( is_array($distance_top_10) && count($distance_top_10) > 0 ) {
		echo '<h3>Top 10 Distance Cache Entries</h3>
	
	<div class="table-responsive">
		<table class="display table table-striped table-condensed table-bordered table-hover" id="EXP_DISTANCE">
			<thead><tr class="exspeedite-bg">
				<th>Origin</th>
				<th>Destination</th>
				<th>Distance</th>
				<th>Source</th>
				<th>Access</th>
			</thead>
			<tbody>

		';
	foreach( $distance_top_10 as $row ) {
		echo '<tr>
			<td>'.$row["origin"].'</td>';
		echo '<td>'.$row["destination"].'</td>';
		echo '<td>'.$row["distance"].'</td>';
		echo '<td>'.$row["DISTANCE_SOURCE"].'</td>';
		echo '<td class="text-right">'.$row["access_count"].' ('.$row["pct"].'%)</td>
		</tr>';
	}
	echo '
		</tbody>
		</table>
	</div>
	</div>';
		
	}
?>
</div>

	<script language="JavaScript" type="text/javascript"><!--
		
		$(document).ready( function () {
			$('#EXP_VALIDATION, #EXP_DISTANCE').dataTable({
		        //"bLengthChange": false,
		        "bFilter": false,
		        "bSort": false,
		        "bInfo": false,
				"bAutoWidth": false,
				//"bProcessing": true,
				"bPaginate": false,
				"sScrollX": "100%",
				"sScrollY": "200px",
				//"sScrollXInner": "150%",
				"bScrollCollapse": true,
				"bSortClasses": false		
			});
					
		});
	//--></script>

<?php

require_once( "include/footer_inc.php" );
?>
