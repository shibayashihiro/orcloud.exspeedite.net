<?php 

// $Id: exp_db_tables.php 4350 2021-03-02 19:14:52Z duncan $
// View/optimize DB tables

// Set flag that this is a parent file
define( '_STS_INCLUDE', 1 );

// Setup Session
require_once( "include/sts_session_setup.php" );

require_once( "include/sts_config.php" );
$sts_debug = (isset($_GET['debug'])  || isset($_POST['debug']))  && in_group( EXT_GROUP_DEBUG );
require_once( "include/sts_session_class.php" );

$my_session = sts_session::getInstance( $exspeedite_db, $sts_debug );
$my_session->access_check( EXT_GROUP_ADMIN );	// Make sure we should be here

$sts_subtitle = "MySQL DB Tables";
require_once( "include/header_inc.php" );
require_once( "include/navbar_inc.php" );
set_time_limit(0);
ini_set('memory_limit', '1024M');

if( ! empty($_GET["OPT"]) ) {
	// Turn off output buffering
	ini_set('output_buffering', 'off');
	// Implicitly flush the buffer(s)
	ini_set('implicit_flush', true);
	ob_implicit_flush(true);
	
	if (ob_get_level() == 0) ob_start();
	
	echo '<div class="container" role="main">
		<div id="loading"><h2 class="text-center"><img src="images/loading.gif" alt="loading" width="125" height="125" /><br>Optimizing '.$_GET["OPT"].'...</h2></div>';
	ob_flush(); flush();

	$opt = $exspeedite_db->get_multiple_rows("OPTIMIZE TABLE ".$_GET["OPT"]);
	reload_page ( "exp_db_tables.php" );
}
?>

<div class="container" role="main">

<?php
	$tables = $exspeedite_db->get_multiple_rows("
		SELECT TABLE_NAME, ENGINE, TABLE_ROWS, 
			round((DATA_LENGTH / 1024 / 1024),2) DATA_LENGTH,
			round((INDEX_LENGTH / 1024 / 1024),2) INDEX_LENGTH, 
			round(((data_length + index_length) / 1024 / 1024),2) SIZE
		FROM information_schema.TABLES WHERE table_schema = '".$sts_database."'
		ORDER BY (data_length + index_length) DESC");
		
	if( is_array($tables) && count($tables) > 0 ) {
		$total_size = 0.0;
		foreach( $tables as $table ) {
			$total_size += $table["SIZE"];
		}
		$total_size = round($total_size,2);
		
		echo '<h2><span class="glyphicon glyphicon-wrench"></span> Exspeedite MySQL DB Tables ('.$total_size.' MB) <div class="btn-group"><a class="btn btn-md btn-success" href="exp_db_tables.php"><span class="glyphicon glyphicon-refresh"></span></a><a class="btn btn-md btn-default"  href="index.php"><span class="glyphicon glyphicon-remove"></span> Back</a></div></h2>
		
		<div class="table-responsive well well-sm">
		<table class="display table table-striped table-condensed table-bordered table-hover" id="DB_TABLES">
		<thead><tr class="exspeedite-bg"><th>Table</th><th>Engine</th><th class="text-right"># Rows</th>
		<th class="text-right">Data Size (MB)</th>
		<th class="text-right">Index Size (MB)</th>
		<th class="text-right">Total Size (MB)</th>
		<th class="text-right">%</th>
		<th>Optimize</th></tr>
		</thead>
		<tbody>';
		foreach( $tables as $table ) {
			echo '<tr><td>'.$table["TABLE_NAME"].'</td>
			<td>'.$table["ENGINE"].'</td>
			<td class="text-right">'.$table["TABLE_ROWS"].'</td>
			<td class="text-right">'.$table["DATA_LENGTH"].'</td>
			<td class="text-right">'.$table["INDEX_LENGTH"].'</td>
			<td class="text-right">'.$table["SIZE"].'</td>
			<td class="text-right">'.number_format($table["SIZE"]/$total_size*100,0).'</td>
			<td class="text-center"><a class="btn btn-md btn-danger" href="exp_db_tables.php?OPT='.$table["TABLE_NAME"].'"><span class="glyphicon glyphicon-wrench"></span></a></td>
			</tr>';			
		}
		echo '</tbody>
		</table>
		</div>
				</div>
	</div>
	';
	
	}
	
?>
</div>


	<script language="JavaScript" type="text/javascript"><!--
		
		$(document).ready( function () {

			$('#DB_TABLES').dataTable({
		        //"bLengthChange": false,
		        "bFilter": false,
		        "bSort": false,
		        "bInfo": false,
				"bAutoWidth": false,
				//"bProcessing": true, 
				"sScrollX": "100%",
				"sScrollY": ($(window).height() - 255) + "px",
				//"sScrollXInner": "200%",
		        //"lengthMenu": [[-1, 25, 50], ["All", 25, 50]],
				"bPaginate": false,
				"bScrollCollapse": true,
				"bSortClasses": false		
			});
		});
		
	//--></script>

<?php

require_once( "include/footer_inc.php" );
?>
