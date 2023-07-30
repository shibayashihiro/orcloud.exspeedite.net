<?php 

// $Id: exp_list_client_shipcons.php 4350 2021-03-02 19:14:52Z duncan $
// Find Shipper & Consignee Leads

// Set flag that this is a parent file
define( '_STS_INCLUDE', 1 );

// Setup Session
require_once( "include/sts_session_setup.php" );

require_once( "include/sts_config.php" );
$sts_debug = isset($_GET['debug']) && in_group( EXT_GROUP_DEBUG );
require_once( "include/sts_session_class.php" );

$my_session = sts_session::getInstance( $exspeedite_db, $sts_debug );
$my_session->access_check( EXT_GROUP_USER );	// Make sure we should be here

$sts_subtitle = "Find Shipper & Consignee Leads";
require_once( "include/header_inc.php" );
require_once( "include/navbar_inc.php" );

?>
<div class="container" role="main">
<?php
require_once( "include/sts_client_class.php" );
require_once( "include/sts_setting_class.php" );
require_once( "include/sts_office_class.php" );


$setting_table = sts_setting::getInstance($exspeedite_db, $sts_debug);
$office_table = sts_office::getInstance($exspeedite_db, $sts_debug);
$sts_cms_enabled = $setting_table->get("option", "CMS_ENABLED") == 'true';
$multi_company = $setting_table->get("option", "MULTI_COMPANY") == 'true';

function tf( $v ) {
	return $v ? '<span class="glyphicon glyphicon-check" hidden><span class="hidden">1</span></span>' : '<span class="text-muted"><span class="glyphicon glyphicon-unchecked"></span>';
}

if( $sts_cms_enabled ) {
	$leads = $exspeedite_db->get_multiple_rows("SELECT X.CLIENT_CODE, X.CLIENT_NAME, X.SHIPMENTS,
	C.SHIPPER, C.CONSIGNEE, C.CREATED_DATE
	FROM (
	SELECT distinct CLIENT_CODE, CLIENT_NAME,
		GROUP_CONCAT(SHIPMENT_CODE ORDER BY SHIPMENT_CODE ASC SEPARATOR ', ') SHIPMENTS
		FROM exp_shipment, exp_client
		where ".($multi_company ? "OFFICE_CODE = ". $_SESSION['EXT_USER_OFFICE']." AND " : "" )."
		(SHIPPER_CLIENT_CODE = CLIENT_CODE OR CONS_CLIENT_CODE = CLIENT_CODE)
		AND (SHIPPER = TRUE OR CONSIGNEE = true)
		and BILL_TO = false
		and not exists (select c.CLIENT_CODE
		from exp_client c
		where exp_client.CLIENT_GROUP_CODE = c.CLIENT_CODE
		and c.BILL_TO = true)
		GROUP BY CLIENT_CODE, CLIENT_NAME
		order by CLIENT_CODE asc) X, EXP_CLIENT C
		WHERE X.CLIENT_CODE = C.CLIENT_CODE");
		
	if( count($leads) > 0 ) {
		echo '<h3 class="tip" title="These companies have been shipper or consignee for a shipment, but are not billable clients."><span class="glyphicon glyphicon-user"></span> Find Shipper & Consignee Leads'.($multi_company ? ' For '.$office_table->office_name() : '').' <a class="btn btn-sm btn-default" href="index.php"><span class="glyphicon glyphicon-arrow-left"></span> Back</a></h3>
		
		<div class="table-responsive  bg-white">
		<table class="display table table-striped table-condensed table-bordered table-hover table-nobm" id="LEADS">
		<thead><tr class="exspeedite-bg">
		<th>Lead</th>
		<th>Since</th>
		<th class="text-center">Ship</th>
		<th class="text-center">Cons</th>
		<th>Shipments</th>
		</tr>
		</thead>
		<tbody>';

		foreach($leads as $row) {
			$shipments = explode(', ', $row['SHIPMENTS']);
			$linked = array();
			foreach( $shipments as $shipment ) {
				$linked[] = '<a href="exp_addshipment.php?CODE='.$shipment.'">'.$shipment.'</a>';
			}
			
			echo '<tr>
					<td><a href="exp_editclient.php?CODE='.$row['CLIENT_CODE'].'">'.$row['CLIENT_NAME'].'</a></td>
					<td>'.date("m/d/Y h:i A", strtotime($row['CREATED_DATE'])).'</td>
					<td class="text-center">'.tf($row['SHIPPER']).'</td>
					<td class="text-center">'.tf($row['CONSIGNEE']).'</td>
					<td>'.implode(', ', $linked).'</td>
				</tr>';
		}

		echo '
	   		</tbody>
			</table>
		</div>';
	}

		
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

			$('#LEADS').dataTable({
		        //"bLengthChange": false,
		        "bFilter": true,
		        "bSort": true,
		        "bInfo": true,
				//"bAutoWidth": false,
				//"bProcessing": true, 
				"sScrollX": "100%",
				"sScrollY": ($(window).height() - 254) + "px",
				"sScrollXInner": "120%",
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
