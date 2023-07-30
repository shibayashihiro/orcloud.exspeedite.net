<?php 

// $Id: exp_attachment_stats.php 5030 2023-04-12 20:31:34Z duncan $
//! SCR# 644 - Attachment Statistics Page

// Set flag that this is a parent file
define( '_STS_INCLUDE', 1 );

// Setup Session
require_once( "include/sts_session_setup.php" );

require_once( "include/sts_config.php" );
$sts_debug = isset($_GET['debug']) && in_group( EXT_GROUP_DEBUG );
require_once( "include/sts_session_class.php" );

$my_session = sts_session::getInstance( $exspeedite_db, $sts_debug );
$my_session->access_check( EXT_GROUP_ADMIN );	// Make sure we should be here

$sts_subtitle = "Attachment Stats";
require_once( "include/header_inc.php" );
require_once( "include/navbar_inc.php" );
require_once( "include/sts_attachment_class.php" );

?>
<div class="container" role="main">
<?php

function format_size($size) {
	$mod = 1024;
	$units = explode(' ','B KB MB GB TB PB');
	for ($i = 0; $size > $mod; $i++) {
		$size /= $mod;
	}
	return number_format(round($size, 2),2) . '&nbsp;' . $units[$i];
}


$check = $exspeedite_db->get_multiple_rows("
	SELECT COALESCE(ITEM, 'Unknown Item') as ITEM, SOURCE_TYPE, NUM, SIZE
	FROM
	(SELECT FILE_TYPE, SOURCE_TYPE, COUNT(*) NUM, SUM(SIZE) SIZE 
	
	FROM EXP_ATTACHMENT
	GROUP BY FILE_TYPE, SOURCE_TYPE
	ORDER BY 2 DESC) D
	left join EXP_ITEM_LIST
	on ITEM_CODE = FILE_TYPE");

if( $sts_debug ) {
	echo "<pre>raw data\n";
	var_dump($check);
	echo "</pre>";
}
	
if( is_array($check) && count($check) > 0 ) {
	$items_types = [];
	$source_types = [];
	$num = [];
	$size = [];
	$num_items_types = [];
	$size_items_types = [];
	$num_source_types = [];
	$size_source_types = [];
	$num_total = 0;
	$size_total = 0;
	
	foreach($check as $row) {
		if( ! in_array($row["ITEM"], $items_types) ) {
			$items_types[] = $row["ITEM"];
			$num[$row["ITEM"]] = [];
			$size[$row["ITEM"]] = [];
		}
		
		if( $sts_debug && $row["ITEM"] == 'Unknown Item') {
			echo "<pre>Unknown Item\n";
			var_dump($row);
			echo "</pre>";
		}

		if( ! in_array($row["SOURCE_TYPE"], $source_types) )
			$source_types[] = $row["SOURCE_TYPE"];
		
		$num[$row["ITEM"]][$row["SOURCE_TYPE"]] = $row["NUM"];
		$size[$row["ITEM"]][$row["SOURCE_TYPE"]] = $row["SIZE"];
		
		if( isset($num_items_types[$row["ITEM"]]))
			$num_items_types[$row["ITEM"]] += $row["NUM"];
		else
			$num_items_types[$row["ITEM"]] = $row["NUM"];
		
		if( isset($size_items_types[$row["ITEM"]]))
			$size_items_types[$row["ITEM"]] += $row["SIZE"];
		else
			$size_items_types[$row["ITEM"]] = $row["SIZE"];
			
		if( isset($num_source_types[$row["SOURCE_TYPE"]]))
			$num_source_types[$row["SOURCE_TYPE"]] += $row["NUM"];
		else
			$num_source_types[$row["SOURCE_TYPE"]] = $row["NUM"];

		if( isset($size_source_types[$row["SOURCE_TYPE"]]))
			$size_source_types[$row["SOURCE_TYPE"]] += $row["SIZE"];
		else
			$size_source_types[$row["SOURCE_TYPE"]] = $row["SIZE"];

		$num_total += $row["NUM"];
		$size_total += $row["SIZE"];
	}
	
	arsort( $num_items_types );
	arsort( $num_source_types );

	if( $sts_debug ) {
		echo "<pre>num_items_types, num_source_types\n";
		var_dump($num_items_types);
		var_dump($num_source_types);
		echo "</pre>";
	}

	
	echo '<h2><img src="images/image_icon.png" alt="image_icon" height="24"> Attachment Stats ('.number_format($num_total,0).' files, '.format_size($size_total).') <div class="btn-group"><a class="btn btn-md btn-default"  href="index.php"><span class="glyphicon glyphicon-remove"></span> Back</a>'.
( $my_session->superadmin() ? '<a class="btn btn-md btn-danger"  href="exp_compressattachment.php?MOVE=50"><span class="glyphicon glyphicon-arrow-right"></span> Move 50</a>' : '').'</div></h2>
		
	<div class="table-responsive well well-sm">
	<table class="display table table-condensed table-bordered table-hover" id="ATT1">
	<thead><tr class="exspeedite-bg"><th>Item Type</th>
	';
	
	foreach( $num_source_types as $st => $y ) {
		echo '<th class="text-right">'.$st.'</th>
		';
	}
	echo '<th class="text-right">Total</th>
	</tr>
	</thead>
	<tbody>';
	
	foreach( $num_items_types as $it => $x) {
		echo '<tr><th class="exspeedite-bg">'.$it.'</th>
		';
		foreach( $num_source_types as $st => $y ) {
			echo '<td class="text-right">'.(isset($num[$it][$st]) ?
				number_format($num[$it][$st],0) : '' ).'<br>'.
				(isset($size[$it][$st]) ?
				format_size($size[$it][$st],0) : '' ).'</td>
			';
		}
		echo '<td class="text-right"><strong>'.number_format($num_items_types[$it],0).'<br>'.
			format_size($size_items_types[$it]).'</strong></td>
		</tr>
		';
	}

	echo '</tbody>
	<tfoot>
	<tr><th class="exspeedite-bg"><h4>Total</h4></th>
	';
	foreach( $num_source_types as $st => $y ) {
		echo '<td class="text-right"><strong>'.
			number_format($num_source_types[$st],0).'<br>'.
			format_size($size_source_types[$st]) .'</strong></td>
		';
	}
	echo '<td class="text-right"><strong>'.number_format($num_total,0).'<br>'.
		format_size($size_total).'</strong></td>
	</tr>
	';

	echo '</tfoot>
	</table>
	</div>
	';



}

$check4 = $exspeedite_db->get_multiple_rows("
	SELECT STORED_WHERE, COUNT(*) NUM, SUM(SIZE) SIZE 
	FROM EXP_ATTACHMENT
	GROUP BY STORED_WHERE
	ORDER BY 2 DESC");

	echo '<div class="row">
	<div class="col-md-4">
	';	
if( $my_session->superadmin() && is_array($check4) && count($check4) > 0 ) {
	echo '<div class="table-responsive well well-sm">
	<table class="display table table-condensed table-bordered table-hover" id="DB_TABLES">
	<thead><tr class="exspeedite-bg"><th>Location</th><th class="text-right">Number</th><th class="text-right">Size</th>
	</thead>
	<tbody>';
	foreach( $check4 as $row ) {
		echo '<tr><td>'.$row["STORED_WHERE"].'</td>
		<td class="text-right">'.number_format($row["NUM"],0).'</td>
		<td class="text-right">'.format_size($row["SIZE"]).'&nbsp;('.number_format($row["SIZE"]/$size_total*100,0).'%)</td>
		</tr>';			
	}
	echo '</tbody>
	</table>
	</div>
	';
}
	
$check5 = $exspeedite_db->get_multiple_rows("
	SELECT MIN(SIZE) AS MINSIZE, AVG(SIZE) AS AVGSIZE, MAX(SIZE) AS MAXSIZE
	FROM EXP_ATTACHMENT");
	
if( is_array($check5) && count($check5) > 0 ) {
	echo '<div class="table-responsive well well-sm">
	<table class="display table table-condensed table-bordered table-hover" id="DB_TABLES">
	<thead><tr class="exspeedite-bg"><th class="text-right">Min Size</th><th class="text-right">Avg Size</th><th class="text-right">Max Size</th>
	</thead>
	<tbody>';
	foreach( $check5 as $row ) {
		echo '<tr>
		<td class="text-right">'.format_size($row["MINSIZE"]).'</td>
		<td class="text-right">'.format_size($row["AVGSIZE"]).'</td>
		<td class="text-right">'.format_size($row["MAXSIZE"]).'</td>
		</tr>';			
	}
	echo '</tbody>
	</table>
	</div>
	';
}
	
$check3 = $exspeedite_db->get_multiple_rows("
	SELECT YEAR(CREATED_DATE) YR, COUNT(*) NUM, SUM(SIZE) SIZE 
	FROM EXP_ATTACHMENT
	GROUP BY YEAR(CREATED_DATE)
	ORDER BY 1 ASC");
	
if( is_array($check3) && count($check3) > 0 ) {
	echo '
	<div class="table-responsive well well-sm">
	<table class="display table table-condensed table-bordered table-hover" id="DB_TABLES">
	<thead><tr class="exspeedite-bg"><th>Year</th><th class="text-right">Number</th><th class="text-right">Size</th>
	</thead>
	<tbody>';
	foreach( $check3 as $row ) {
		echo '<tr><td>'.$row["YR"].'</td>
		<td class="text-right">'.number_format($row["NUM"],0).'</td>
		<td class="text-right">'.format_size($row["SIZE"]).'&nbsp;('.number_format($row["SIZE"]/$size_total*100,0).'%)</td>
		</tr>';			
	}
	echo '</tbody>
	</table>
	</div>
	</div>
	';
}
	
$check2 = $exspeedite_db->get_multiple_rows("
	SELECT SUBSTRING_INDEX(FILE_NAME,'.',-1) AS EXT, COUNT(*) NUM, SUM(SIZE) SIZE 
	FROM EXP_ATTACHMENT
	WHERE LENGTH(SUBSTRING_INDEX(FILE_NAME,'.',-1)) <= 4
	GROUP BY SUBSTRING_INDEX(FILE_NAME,'.',-1)
	ORDER BY 2 DESC");
	
if( is_array($check2) && count($check2) > 0 ) {
	echo '
	<div class="col-md-8">
	<div class="table-responsive well well-sm">
	<table class="display table table-condensed table-bordered table-hover" id="EXT">
	<thead><tr class="exspeedite-bg"><th>Extension</th><th>Mime Type</th><th class="text-right">Number</th><th class="text-right">Size</th>
	</thead>
	<tbody>';
	foreach( $check2 as $row ) {
		echo '<tr><td>'.$row["EXT"].'</td>
		<td>'.sts_attachment::mime_type('a.'.$row["EXT"]).'</td>
		<td class="text-right">'.number_format($row["NUM"],0).'</td>
		<td class="text-right">'.format_size($row["SIZE"]).'&nbsp;('.number_format($row["SIZE"]/$size_total*100,0).'%)</td>
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

			$('#ATT1,#EXT').dataTable({
		        //"bLengthChange": false,
		        "bFilter": false,
		        "bSort": false,
		        "bInfo": false,
				"bAutoWidth": false,
				//"bProcessing": true, 
				"sScrollX": "100%",
				"sScrollY": "400px",
				//"sScrollXInner": "120%",
				"bPaginate": false,
				"bScrollCollapse": false,
				"bSortClasses": false,
				"order": [[ 2, "desc" ]],
				//"dom": "frtiS",
			});
						
		});
	//--></script>

<?php

require_once( "include/footer_inc.php" );
?>

