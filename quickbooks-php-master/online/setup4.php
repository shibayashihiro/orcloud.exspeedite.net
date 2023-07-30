<?php

// Set flag that this is a parent file
define( '_STS_INCLUDE', 1 );

// Setup Session
require_once( "../../include/sts_session_setup.php" );

require_once dirname(__FILE__) . '/config.php';

require_once( "include/sts_session_class.php" );

$my_session = sts_session::getInstance( $exspeedite_db, $sts_debug );
$my_session->access_check( EXT_GROUP_ADMIN );	// Make sure we should be here

define( 'EXP_RELATIVE_PATH', '../../' );
$sts_subtitle = "Setup QBOE";
require_once( "include/header_inc.php" );
require_once( "include/navbar_inc.php" );
require_once( "include/sts_company_class.php" );
require_once dirname(__FILE__) . '/qb_online_api.php';

$needed_tax_agencies = array( 'Canada Revenue Agency', 'Revenu Quebec' );

// Turn off output buffering
ini_set('output_buffering', 'off');
// Implicitly flush the buffer(s)
ini_set('implicit_flush', true);
ob_implicit_flush(true);

if (ob_get_level() == 0) ob_start();

$company_table = sts_company::getInstance($exspeedite_db, $sts_debug);

if( $multi_company && $sts_qb_multi && isset($_SESSION['SETUP_COMPANY']) ) {
	list($quickbooks_is_connected, $realm, $Context) =
		connect_to_quickbooks( $_SESSION['SETUP_COMPANY'] );
} else {
	list($quickbooks_is_connected, $realm, $Context) = connect_to_quickbooks();
}

echo '<div class="container-full" role="main">';
echo "<h2>".$sts_subtitle." Step 4 (Canada) ".
	($multi_company && $sts_qb_multi ? '<span class="label label-default">'.$company_table->name($_SESSION['SETUP_COMPANY'], false).'</span> ' :'').
	($sts_qb_online && $quickbooks_is_connected ? '<div class="btn-group"><a class="btn btn-md btn-default" href="setup.php"><span class="glyphicon glyphicon-arrow-left"></span> Step 1</a><a class="btn btn-md btn-default" href="setup2.php">2</a><a class="btn btn-md btn-default" href="setup3.php">3</a></div>' : '')."</h2>";
	ob_flush(); flush();

if( $sts_qb_online && $quickbooks_is_connected ) {
	$api = sts_quickbooks_online_API::getInstance($Context, $realm, $exspeedite_db, $sts_debug);

	foreach($needed_tax_agencies as $agency) {
		$id = $api->tax_agency_query( $agency );
		echo "<h4>".$agency.($id ? ' exists <span class="glyphicon glyphicon-ok"></span>' : ' does not exist <span class="glyphicon glyphicon-remove"></span>')."</h4>";
	}
}

echo '</div>';

?>

	<script language="JavaScript" type="text/javascript"><!--
		
		$(document).ready( function () {

			$('#QB_CLIENTS, #QB_VENDORS').dataTable({
		        //"bLengthChange": false,
		        "bFilter": false,
		        "bSort": false,
		        "bInfo": false,
				//"bAutoWidth": false,
				//"bProcessing": true, 
				"sScrollX": "100%",
				"sScrollY": "200px",
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
