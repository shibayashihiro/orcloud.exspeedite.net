<?php 
// Set flag that this is a parent file
define( '_STS_INCLUDE', 1 );

// Setup Session
require_once( "include/sts_session_setup.php" );

require_once( "include/sts_config.php" );
$sts_debug = isset($_GET['debug']) && in_group( EXT_GROUP_DEBUG );
require_once( "include/sts_session_class.php" );

$my_session = sts_session::getInstance( $exspeedite_db, $sts_debug );
$my_session->access_check( EXT_GROUP_USER );	// Make sure we should be here

if( ! isset($_GET) || ! isset($_GET["EXPORT"])) {
	$sts_subtitle = "List Carriers";
	require_once( "include/header_inc.php" );
	require_once( "include/navbar_inc.php" );
}

?>
<div class="container-full" role="main">
<?php
require_once( "include/sts_result_class.php" );
require_once( "include/sts_carrier_class.php" );
require_once( "include/sts_office_class.php" );
require_once( "include/sts_csv_class.php" );

$carrier_table = sts_carrier::getInstance($exspeedite_db, $sts_debug);
$office_table = sts_office::getInstance($exspeedite_db, $sts_debug);

if( isset($_GET['INIT']) && $my_session->superadmin() ) {
	if (ob_get_level() == 0) ob_start();
	
	echo '<div id="loading"><h2 class="text-center">Initialize exp_carrier_load Cache
	<br><img src="images/loading.gif" alt="loading" width="" height=""></h2></div>';
	ob_flush(); flush();
	
	$exspeedite_db->get_one_row("
		TRUNCATE EXP_CARRIER_LOAD;
		");
		
	// Add entries
	$exspeedite_db->get_one_row("
		INSERT INTO EXP_CARRIER_LOAD (CARRIER_CODE, LOAD_CODE, OFFICE_CODE, COMPLETED_DATE)
		SELECT CARRIER, LOAD_CODE, OFFICE_CODE, COMPLETED_DATE
		FROM EXP_LOAD
		WHERE CARRIER > 0
		AND COMPLETED_DATE IS NOT NULL
		AND DATE(COMPLETED_DATE) > DATE_SUB(NOW(),INTERVAL 1 YEAR)
		LIMIT 10000;
		");
		
	// Remove duplicates
	$exspeedite_db->multi_query("
		DROP TEMPORARY TABLE IF EXISTS X;

		CREATE TEMPORARY TABLE X
		SELECT CARRIER_LOAD_CODE FROM EXP_CARRIER_LOAD C
		WHERE EXISTS (SELECT DISTINCT CARRIER_LOAD_CODE FROM EXP_CARRIER_LOAD CL
		WHERE CL.CARRIER_CODE = C.CARRIER_CODE
		AND CL.OFFICE_CODE = C.OFFICE_CODE
		AND CL.CARRIER_LOAD_CODE <> C.CARRIER_LOAD_CODE
		AND CL.COMPLETED_DATE > C.COMPLETED_DATE);

		DELETE FROM EXP_CARRIER_LOAD
		WHERE CARRIER_LOAD_CODE IN (SELECT CARRIER_LOAD_CODE FROM X);
	");
	
	update_message( 'loading', '' );
	ob_flush(); flush();
}

if( ! isset($_SESSION['OFFICE_CODE']) )
	$_SESSION['OFFICE_CODE'] = 'all';
if( isset($_POST['OFFICE_CODE']) ) $_SESSION['OFFICE_CODE'] = $_POST['OFFICE_CODE'];


$filters_html = '<a class="btn btn-sm btn-success" href="exp_listcarrier2.php"><span class="glyphicon glyphicon-refresh"></span></a>';

$filters_html .= $office_table->offices_menu2($_SESSION['OFFICE_CODE']);

$filters_html .= ' <a class="btn btn-sm btn-primary tip" title="Export to CSV" href="exp_listcarrier2.php?EXPORT"><span class="glyphicon glyphicon-th-list"></span></a>';

if( $my_session->superadmin() ) {
	$filters_html .= ' <a class="btn btn-sm btn-danger tip" title="Initialize cache table - only superadmin can do this" href="exp_listcarrier2.php?INIT"><span class="glyphicon glyphicon-warning-sign"></span> Init</a>';
}

//$filters_html .= '</div>';

$sts_result_carriers_edit2['filters_html'] = $filters_html;

$match = false;

if( $_SESSION['OFFICE_CODE'] == 'all' ) {
	$match = "EXISTS(SELECT LOAD_CODE FROM EXP_CARRIER_LOAD
		WHERE EXP_CARRIER.CARRIER_CODE = EXP_CARRIER_LOAD.CARRIER_CODE
		AND DATE(EXP_CARRIER_LOAD.COMPLETED_DATE) > DATE_SUB(NOW(),INTERVAL 1 YEAR))";
} else {
	$match = "EXISTS(SELECT LOAD_CODE FROM EXP_CARRIER_LOAD
		WHERE EXP_CARRIER.CARRIER_CODE = EXP_CARRIER_LOAD.CARRIER_CODE
		AND DATE(EXP_CARRIER_LOAD.COMPLETED_DATE) > DATE_SUB(NOW(),INTERVAL 1 YEAR)
		AND EXP_CARRIER_LOAD.OFFICE_CODE = ".$_SESSION['OFFICE_CODE'].")";
}

if( isset($_GET) && isset($_GET["EXPORT"])) {
	$csv = new sts_csv($carrier_table, $match, $sts_debug);
	
	$csv->header( "Exspeedite_Carrier" );

	$csv->render( $sts_result_carriers_office_layout2 );

	die;
}

$rslt = new sts_result( $carrier_table, $match, $sts_debug );
echo $rslt->render( $sts_result_carriers_office_layout2, $sts_result_carriers_edit2 );

?>
</div>

	<script language="JavaScript" type="text/javascript"><!--
		
		$(document).ready( function () {
			//alert(($(window).height() - 150) + "px");
		//	document.documentElement.style.overflow = 'hidden';  // firefox, chrome
		//	document.body.scroll = "no"; // ie only

			$('#EXP_CARRIER').dataTable({
		        //"bLengthChange": false,
		        "bFilter": true,
		        "bSort": true,
		        "bInfo": false,
				"bAutoWidth": false,
				//"bProcessing": true, 
				"sScrollX": "100%",
				"sScrollY": ($(window).height() - 275) + "px",
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

