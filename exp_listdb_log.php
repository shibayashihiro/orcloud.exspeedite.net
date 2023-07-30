<?php 
// Set flag that this is a parent file
define( '_STS_INCLUDE', 1 );

// Setup Session
require_once( "include/sts_session_setup.php" );

require_once( "include/sts_config.php" );
set_time_limit(0);
ini_set('memory_limit', '1024M');

$sts_debug = isset($_GET['debug']) && in_group( EXT_GROUP_DEBUG );
require_once( "include/sts_session_class.php" );

$my_session = sts_session::getInstance( $exspeedite_db, $sts_debug );
$my_session->access_check( EXT_GROUP_ADMIN );	// Make sure we should be here

$sts_subtitle = "List DB Log";
require_once( "include/header_inc.php" );
require_once( "include/navbar_inc.php" );
require_once( "include/SqlFormatter.php" );		

?>
<style>
	pre { max-width: 600px; }
</style>
<div class="container-full" role="main">
<?php

$exspeedite_db->set_logging( false );	// Don't log stuff we do here

if( isset($_GET["TRUNCATE"])) {
	$exspeedite_db->get_one_row("TRUNCATE EXP_DB_LOG");
}

function fmt_sql( $sql ) {
	// Using web API from https://sqlformat.org/api/
	global $sts_debug;
	
	$ch = curl_init();
	
	curl_setopt($ch, CURLOPT_URL,"https://sqlformat.org/api/v1/format");
	curl_setopt($ch, CURLOPT_POST, 1);
	curl_setopt($ch, CURLOPT_POSTFIELDS,
		"reindent=1&keyword_case=upper&sql=".urlencode($sql));
	
	// Receive server response ...
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

	//Disable CURLOPT_SSL_VERIFYHOST and CURLOPT_SSL_VERIFYPEER by
	//setting them to false.
	curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);	

	$server_output = curl_exec($ch);
	if( $sts_debug && $server_output === false ) {
		echo "<pre>".__METHOD__.": CURL failed\n";
		var_dump(curl_error($ch));
		echo "</pre>";		
	}
	$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
	
	curl_close ($ch);
	
	$info = json_decode($server_output);
	if( $sts_debug ) {
		echo "<pre>".__METHOD__.": httpcode, sql, output, info\n";
		var_dump($httpCode, $sql);
		var_dump($server_output, $info);
		echo "</pre>";		
	}
	
	return SqlFormatter::highlight($info->result);
}

if( ! isset($_SESSION['DB_LOG_CHOICE']) ) $_SESSION['DB_LOG_CHOICE'] = 'most';
if( isset($_POST['DB_LOG_CHOICE']) ) $_SESSION['DB_LOG_CHOICE'] = $_POST['DB_LOG_CHOICE'];

$valid_logs = array(	'most' => 'Most Used SQL Queries',
						'recent' => 'Recent SQL Queries',
						'slow' => 'Slow SQL Queries',
						'errors' => 'SQL Queries With Errors' );

$filters_html = '<div class="form-group">
	<select class="form-control input-lg" name="DB_LOG_CHOICE" id="DB_LOG_CHOICE"   onchange="form.submit();">';
foreach( $valid_logs as $value => $label ) {
	$filters_html .= '<option style="font-size: 30px;" value="'.$value.'" '.($_SESSION['DB_LOG_CHOICE'] == $value ? 'selected' : '').'>'.$label.'</option>
	';
}
$filters_html .= '</select>
	</div>';

if( $_SESSION['DB_LOG_CHOICE'] == 'most') {
	$top_used = $exspeedite_db->get_multiple_rows("SELECT DB_QUERY, NUM, TIME_QUERY, URI
		FROM(
		SELECT DB_QUERY, COUNT(*) NUM, SUM(DB_TIME_QUERY) TIME_QUERY,
		GROUP_CONCAT(DISTINCT LEFT(REQUEST_URI, 64) ORDER BY REQUEST_URI ASC SEPARATOR '<br>') URI
		FROM EXP_DB_LOG
		GROUP BY MD5(DB_QUERY)
		ORDER BY 2 DESC) X
		WHERE NUM > 1
		LIMIT 20");
} else if( $_SESSION['DB_LOG_CHOICE'] == 'recent') {
	$top_used = $exspeedite_db->get_multiple_rows("SELECT CREATED_DATE, 
		(SELECT USERNAME FROM EXP_USER WHERE USER_CODE = EXP_DB_LOG.CREATED_BY) AS CREATED_BY,
		DB_QUERY, DB_TIME_QUERY, LEFT(REQUEST_URI, 64) AS REQUEST_URI, STACK_TRACE
		FROM EXP_DB_LOG
		ORDER BY CREATED_DATE DESC
		LIMIT 25");
} else if( $_SESSION['DB_LOG_CHOICE'] == 'slow') {
	$top_used = $exspeedite_db->get_multiple_rows("SELECT CREATED_DATE, 
		(SELECT USERNAME FROM EXP_USER WHERE USER_CODE = EXP_DB_LOG.CREATED_BY) AS CREATED_BY,
		DB_QUERY, DB_TIME_QUERY, LEFT(REQUEST_URI, 64) AS REQUEST_URI, STACK_TRACE
		FROM EXP_DB_LOG
		ORDER BY DB_TIME_QUERY DESC
		LIMIT 25");
} else if( $_SESSION['DB_LOG_CHOICE'] == 'errors') {
	$top_used = $exspeedite_db->get_multiple_rows("SELECT CREATED_DATE, 
		(SELECT USERNAME FROM EXP_USER WHERE USER_CODE = EXP_DB_LOG.CREATED_BY) AS CREATED_BY,
		DB_QUERY, DB_ERROR, DB_TIME_QUERY, LEFT(REQUEST_URI, 64) AS REQUEST_URI, STACK_TRACE
		FROM EXP_DB_LOG
		WHERE COALESCE(DB_ERROR,'') <> ''
		ORDER BY DB_TIME_QUERY DESC
		LIMIT 25");
}

	
echo '<form class="form-inline" role="form" action="exp_listdb_log.php" method="post" enctype="multipart/form-data" name="RESULT_DB_LOG" id="RESULT_DB_LOG">
	<h2>'.$filters_html.' <a class="btn btn-lg btn-default" id="DB_LOG_cancel" href="index.php"><span class="glyphicon glyphicon-remove"></span> Back</a> <a class="btn btn-lg btn-success" href="exp_listdb_log.php"><span class="glyphicon glyphicon-refresh"></span></a> <a class="btn btn-lg btn-danger" onclick="confirmation(\'Confirm: TRUNCATE the DB log table?\', \'exp_listdb_log.php?TRUNCATE\')"><span class="glyphicon glyphicon-remove"></span> Truncate</a></h2>
		</form>

';

if( is_array($top_used) && count($top_used) > 0 ) {
	echo '<div class="table-responsive  bg-white">
			<table class="display table table-striped table-condensed table-bordered table-hover table-nobm" id="DB_LOG">
			<thead><tr class="exspeedite-bg">
			';
			
			if( $_SESSION['DB_LOG_CHOICE'] == 'most') {
				echo'<th>Query</th>
				<th class="text-right">Frequency</th>
				<th class="text-right">Total Time</th>
				<th>URI</th>
				';
			} else if( in_array($_SESSION['DB_LOG_CHOICE'], array('recent','slow', 'errors') ) ) {
				echo'<th>Created</th><th>By</th><th>Query</th>
				<th class="text-right">Time</th>
				<th>URI / Stack Trace</th>
				';
			}
			
			echo'</tr>
			</thead>
			<tbody>';
			foreach($top_used as $row) {
				if( $_SESSION['DB_LOG_CHOICE'] == 'most') {
					echo '<tr>
							<td>'.fmt_sql($row['DB_QUERY']).'</td>
							<td class="text-right">'.$row['NUM'].'</td>
							<td class="text-right">'.$row['TIME_QUERY'].'</td>
							<td>'.$row['URI'].'</td>
						</tr>';
				} else if( in_array($_SESSION['DB_LOG_CHOICE'], array('recent','slow') ) ) {
					echo '<tr>
							<td>'.$row['CREATED_DATE'].'</td>
							<td>'.$row['CREATED_BY'].'</td>
							<td>'.fmt_sql($row['DB_QUERY']).'</td>
							<td class="text-right">'.$row['DB_TIME_QUERY'].'</td>
							<td>'.$row['REQUEST_URI'].'<br><br><pre>'.$row['STACK_TRACE'].'</pre></td>
						</tr>';
				} else if( $_SESSION['DB_LOG_CHOICE'] == 'errors') {
					echo '<tr>
							<td>'.$row['CREATED_DATE'].'</td>
							<td>'.$row['CREATED_BY'].'</td>
							<td>'.fmt_sql($row['DB_QUERY']).'<br><br><b>Error:</b> '.$row['DB_ERROR'].'</td>
							<td class="text-right">'.$row['DB_TIME_QUERY'].'</td>
							<td>'.$row['REQUEST_URI'].'<br><br><pre>'.$row['STACK_TRACE'].'</pre></td>
						</tr>';
				}
			}

	echo '</tbody>
			</table>
			</div>
			';
} else {
	echo '<p>No entries found</p>';
}

?>
</div>

	<script language="JavaScript" type="text/javascript"><!--
		
		$(document).ready( function () {
			//alert(($(window).height() - 150) + "px");
			//document.documentElement.style.overflow = 'hidden';  // firefox, chrome
			//document.body.scroll = "no"; // ie only

			$('#DB_LOG').dataTable({
		        //"bLengthChange": false,
		        "bFilter": false,
		        "bSort": false,
		        "bInfo": false,
				"bAutoWidth": false,
				//"bProcessing": true, 
				"sScrollX": "100%",
				"sScrollY": ($(window).height() - 265) + "px",
				//"sScrollXInner": "120%",
		        "lengthMenu": [<?php echo isset($sts_length_menu) ? $sts_length_menu : '25, 50, 100, 250'; ?>],
				"bPaginate": true,
				"bScrollCollapse": false,
				"bSortClasses": false
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

