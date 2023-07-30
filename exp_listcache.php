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

$sts_subtitle = "List Cache";
require_once( "include/header_inc.php" );
require_once( "include/navbar_inc.php" );

?>
<div class="container-full" role="main">
<?php
require_once( "include/sts_cache_class.php" );
require_once( "include/sts_result_class.php" );

function do_table( $col ) {
	if( is_array($col) && count($col) > 0 ) {
		$keys = array_keys($col[0]);
		echo '<div class="table-responsive well well-sm">
	<table class="display table table-condensed table-bordered table-hover CACHE">
	<thead>
		<tr class="exspeedite-bg">
		';
		foreach($keys as $heading) {
			echo '<th>'.$heading.'</th>
			';
		}
		echo '</tr>
	</thead>
	<tbody>
	';
		foreach($col as $col_row) {
			echo '<tr>
			';
			foreach($keys as $heading) {
				echo '<td>'.$col_row[$heading].'</td>
				';
			}
			echo '</tr>
			';
		}
		echo '</tbody>
</table>
</div>
';

	} else {
		echo "<p>".$col."</p>";
		
	}
}

function do_settings_table( $col ) {
	if( is_array($col) && count($col) > 0 ) {
		echo '<div class="table-responsive well well-sm">
	<table class="display table table-condensed table-bordered table-hover CACHE">
	<thead>
		<tr class="exspeedite-bg">
			<th>GROUP/SETTING</th><th>VALUE</th>
		</tr>
	</thead>
	<tbody>
	';
		foreach($col as $s => $v) {
			echo '<tr>
				<td>'.$s.'</td><td>'.$v.'</td>
			</tr>
			';
		}
		echo '</tbody>
</table>
</div>
';

	} else {
		echo "<p>".$col."</p>";
		
	}
}

$cache = sts_cache::getInstance($exspeedite_db, $sts_debug);

if( isset($_GET["CACHE"])) {
	if (ob_get_level() == 0) ob_start();
	
	echo '<div class="container" role="main">
		<div id="loading"><h2 class="text-center"><img src="images/loading.gif" alt="loading" width="125" height="125" /><br>Updating Cached Static Information...</h2></div></div>';
	ob_flush(); flush();

	$cache->write_cache();
	update_message( 'loading', '' );
	ob_flush(); flush();
}



$everything = $cache->get_whole_cache();

echo '<h2>Database Cache  <div class="btn-group"><a class="btn btn-md btn-success" href="exp_listcache.php"><span class="glyphicon glyphicon-refresh"></span></a> <a class="btn btn-md btn-danger" href="exp_listcache.php?CACHE"><span class="glyphicon glyphicon-refresh"></span> Update Cache</a> <a class="btn btn-md btn-default"  href="index.php"><span class="glyphicon glyphicon-remove"></span> Back</a></div></h2>
<h3>Path to cache: <span class="label label-success">'.$cache->get_full_path().'</span> ('.sts_result::formatBytes($cache->get_file_size()).', cached '.$cache->date_cached() .')</h3>
<h3>Contents: (click on items to expand)</h3>

<div class="well well-lg tighter">
';

foreach( $everything as $key => $row) {
	echo '<h3 data-toggle="collapse" data-target="#'.$key.'" aria-expanded="false" aria-controls="'.$key.'"><span class="glyphicon glyphicon-play"></span> '.$key.'</h3>
	<div class="collapse" id="'.$key.'">
	';
	if( $key == 'SETTING_CACHE' ) {
		do_settings_table($row);
	} else if( $key == 'VIDEOS' ) {
		do_table($row);
	} else if( is_array($row)) {
		foreach( $row as $key2 => $col ) {
			echo '<ul>
			<h4 data-toggle="collapse" data-target="#'.$key2.'" aria-expanded="false" aria-controls="'.$key2.'"><span class="glyphicon glyphicon-play"></span> '.$key2.'</h4>
			</ul>
			<div class="collapse" id="'.$key2.'">
			';

			do_table($col);	
			echo '</div>
			';
		}
	} else {
		echo "<pre>";
		var_dump($row);
		echo "</pre>";
	}
	echo '</div>
	';
}
	echo '</div>
	';


?>
</div>

	<script language="JavaScript" type="text/javascript"><!--
		
		$(document).ready( function () {
			//alert(($(window).height() - 150) + "px");
			
			$('.CACHE').dataTable({
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
				"bScrollCollapse": true,
				"bSortClasses": false,
			});

			$('.collapse').on( 'shown.bs.collapse', function (e) {
				$.fn.dataTable.tables( {visible: true, api: true} ).columns.adjust();
			} );

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

