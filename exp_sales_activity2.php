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

$sts_subtitle = "Sales Activity Analysis";
require_once( "include/header_inc.php" );
require_once( "include/navbar_inc.php" );

?>
<div class="container-full" role="main">
<?php
require_once( "include/sts_result_class.php" );
require_once( "include/sts_client_activity_class.php" );

$match = "CREATED_DATE between date_sub(now(),INTERVAL 1 WEEK) and now()";

$cat = sts_client_activity::getInstance($exspeedite_db, $sts_debug);

$sales = $cat->database->get_multiple_rows("
	SELECT U.USERNAME, U.USER_CODE, S.STATUS_STATE, COUNT(*) NUM
	FROM EXP_CLIENT_ACTIVITY A, EXP_STATUS_CODES S, EXP_USER U
	WHERE A.ACTIVITY = S.STATUS_CODES_CODE
	AND COALESCE(A.SALES_PERSON, A.CREATED_BY) = U.USER_CODE
	AND A.CREATED_DATE BETWEEN DATE_SUB(NOW(),INTERVAL 1 WEEK) AND NOW()
	GROUP BY U.USERNAME, S.STATUS_STATE
	ORDER BY U.USERNAME ASC, U.USER_CODE ASC, S.STATUS_CODES_CODE ASC
");

if( $sts_debug ) {
	echo "<pre>";
	var_dump($sales);
	echo "</pre>";	
}

if( is_array($sales) && count($sales) > 0 ) {
	$username = array();
	$status = array();
	$cell = array();
	$user_total = array();
	$status_total = array();
	$grand_total = 0;
	
	foreach( $sales as $row ) {
		if( ! isset($username[$row["USER_CODE"]]))
			$username[$row["USER_CODE"]] = $row["USERNAME"];
		if( ! in_array($row["STATUS_STATE"], $status))
			$status[] = $row["STATUS_STATE"];
		if( ! isset($cell[$row["USER_CODE"]][$row["STATUS_STATE"]]))
			$cell[$row["USER_CODE"]][$row["STATUS_STATE"]] = $row["NUM"];

		if( ! isset($user_total[$row["USER_CODE"]]))
			$user_total[$row["USER_CODE"]] = $row["NUM"];
		else
			$user_total[$row["USER_CODE"]] += $row["NUM"];

		if( ! isset($status_total[$row["STATUS_STATE"]]))
			$status_total[$row["STATUS_STATE"]] = $row["NUM"];
		else
			$status_total[$row["STATUS_STATE"]] += $row["NUM"];
		$grand_total += $row["NUM"];
	}

	$sales_person = $cat->database->get_multiple_rows("
		SELECT U.USERNAME, U.USER_CODE
		FROM EXP_USER U
		WHERE USER_GROUPS like '%sales%'
	");
	
	if( is_array($sales_person) && count($sales_person) > 0 ) {
		$username2 = array();
		foreach( $sales_person as $row ) {
			if( ! isset($username2[$row["USER_CODE"]]))
				$username2[$row["USER_CODE"]] = $row["USERNAME"];
		}
	}

	$status_desc = $cat->database->get_multiple_rows("
		SELECT S.STATUS_STATE, S.STATUS_DESCRIPTION
		FROM EXP_STATUS_CODES S
		WHERE SOURCE_TYPE = 'client'
	");
	
	if( is_array($status_desc) && count($status_desc) > 0 ) {
		$description = array();
		foreach( $status_desc as $row ) {
			if( ! isset($description[$row["STATUS_STATE"]]))
				$description[$row["STATUS_STATE"]] = $row["STATUS_DESCRIPTION"];
		}
	}


	
	echo '<h3><span class="glyphicon glyphicon-user"></span> Sales Activity Summary In The Last 7 days
		<div class="btn-group"><a class="btn btn-sm btn-success" id="sales_activity" href="exp_sales_activity.php"><span class="glyphicon glyphicon-user"></span> Sales Activity</a><a class="btn btn-sm btn-default" id="EXP_CLIENT_ACTIVITY_cancel" href="index.php"><span class="glyphicon glyphicon-remove"></span> Back</a>
		</div>
		</h3>
	
	<div class="table-responsive">
		<table class="display table table-striped table-condensed table-bordered table-hover" id="EXP_CLIENT_ACTIVITY">
		<thead><tr class="exspeedite-bg">
		<th>Sales Person</th>
		';
	foreach( $status as $column ) {
		echo '<th class="text-right tip" title="'.$description[$column].'">'.$column.'</th>';
	}
	echo '<th class="text-right">Total</th>
	</tr>
		</thead>
		<tbody>
	';
	foreach( $username2 as $row => $name) {
		echo '<tr><td><a href="exp_edituser.php?CODE='.$row.'">'.$name.'</a></td>
		';
		foreach( $status as $column ) {
			echo '<td class="text-right">'.
				(isset($cell[$row][$column]) ? $cell[$row][$column] : '').'</td>
		';
		}
		echo '<td class="text-right">'.
				(isset($user_total[$row]) ? $user_total[$row] : 0).'</td>
		</tr>
		';
	}
	echo '</tbody>
	<tfoot>
	<tr class="exspeedite-bg">
	<th>Total</th>
	';
	foreach( $status as $column ) {
		echo '<th class="text-right">'.$status_total[$column].'</th>
		';
	}
	echo '<th class="text-right">'.$grand_total.'</th>
	</tr>
	</tfoot>
		</table>
	</div>';
	
} else {
		echo '<h3><span class="glyphicon glyphicon-user"></span> NO Sales Activity Summary In The Last 7 days
		<div class="btn-group"><a class="btn btn-sm btn-success" id="sales_activity" href="exp_sales_activity.php"><span class="glyphicon glyphicon-user"></span> Sales Activity</a><a class="btn btn-sm btn-default" id="EXP_CLIENT_ACTIVITY_cancel" href="index.php"><span class="glyphicon glyphicon-remove"></span> Back</a>
		</div>
		</h3>
';
}

?>
</div>

	<script language="JavaScript" type="text/javascript"><!--
		
		$(document).ready( function () {
			//alert(($(window).height() - 150) + "px");
			<?php if( ! $sts_debug ) { ?>
			document.documentElement.style.overflow = 'hidden';  // firefox, chrome
			document.body.scroll = "no"; // ie only
			<?php } ?>

			$('#EXP_CLIENT_ACTIVITY').dataTable({
		        //"bLengthChange": false,
		        "bFilter": false,
		        "bSort": true,
		        "bInfo": false,
				"bAutoWidth": false,
				//"bProcessing": true, 
				"sScrollX": "100%",
				"sScrollY": "200px",
				//"sScrollXInner": "150%",
		        "lengthMenu": [<?php echo isset($sts_length_menu) ? $sts_length_menu : '25, 50, 100, 250'; ?>],
				"bPaginate": true,
				"bScrollCollapse": true,
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

