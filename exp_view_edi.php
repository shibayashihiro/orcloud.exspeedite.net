<?php

// Set flag that this is a parent file
define( '_STS_INCLUDE', 1 );

error_reporting(E_ALL);
ini_set('display_errors', 1);

// Setup Session
require_once( "include/sts_session_setup.php" );

require_once( "include/sts_config.php" );
$sts_debug = isset($_GET['debug']) && in_group( EXT_GROUP_DEBUG );
require_once( "include/sts_session_class.php" );

$my_session = sts_session::getInstance( $exspeedite_db, $sts_debug );
$my_session->access_check( EXT_GROUP_ADMIN );	// Make sure we should be here

require_once( "include/sts_shipment_class.php" );
require_once( "include/sts_edi_parser_class.php" );
require_once( "include/sts_ftp_class.php" );
require_once( "include/sts_result_class.php" );

$sts_debug = isset($_GET['debug']) || isset($_POST['debug']);

if( isset($_GET['code']) && isset($_GET['pw']) && $_GET['pw'] == 'ChessClub') {
	$sts_subtitle = "View EDI Information";
	require_once( "include/header_inc.php" );
	require_once( "include/navbar_inc.php" );

	$edi = sts_edi_parser::getInstance($exspeedite_db, $sts_debug);
	$ftp = sts_ftp::getInstance($exspeedite_db, $sts_debug);
	$shipment_table = sts_shipment::getInstance($exspeedite_db, $sts_debug);
	
	$shipments = $shipment_table->fetch_rows("EDI_204_PRIMARY = ".$_GET['code'],
		"SHIPMENT_CODE, EDI_204_ORIGIN, EDI_204_B204_SID");
	$edi_shipments = array();
	if( is_array($shipments)) {
		foreach($shipments as $s) { 
			$edi_shipments[] = '<a href="exp_addshipment.php?CODE='.$s["SHIPMENT_CODE"].'" class="alert-link">'.$s["SHIPMENT_CODE"].'</a>';
		}
		$msg = $shipments[0]["EDI_204_B204_SID"].', '.plural(count($edi_shipments), 'Shipment').' ('.implode(', ', $edi_shipments).')';
	}
	
	echo '<div class="container" role="main">';
	
	$sts_result_edi_layout['FILENAME']['link'] = 'exp_view_edi.php?pw=ChessClub&code='.$_GET['code'].'&code2=%pk%&file=';
	$sts_result_edi_edit['title'] .= ' For '.$msg;
	$rslt = new sts_result( $edi, "EDI_204_PRIMARY = ".$_GET['code'], $sts_debug );
	echo $rslt->render( $sts_result_edi_layout, $sts_result_edi_edit );
		
	if( isset($_GET['file'])) {
		$raw = $edi->fetch_rows("EDI_204_PRIMARY = ".$_GET['code']." AND
			EDI_CODE = '".$_GET['code2']."'","EDI_CLIENT, DIRECTION, CONTENT");
		if( is_array($raw) && count($raw) == 1 && isset($raw[0]["CONTENT"])) {
			$edi_raw	= $raw[0]["CONTENT"];
			$client		= $raw[0]["EDI_CLIENT"];
			$direction	= $raw[0]["DIRECTION"];
			echo "<h3>Raw EDI</h3>
				<pre>".$edi_raw."</pre>";
			$edi->tokenize( $edi_raw );
			if( $ftp->edi_strict( $client ) ) {
				if( $direction == 'in' )
					$parsed = $edi->parse_edi( $client, $ftp->edi_our_id( $client ), true );
				else
					$parsed = $edi->parse_edi( $ftp->edi_our_id( $client ), $client, true );
			} else {
				$parsed = $edi->parse_edi();
			}
			if( $parsed )
				echo '<h3>Parsed EDI</h3>
					<div class="well well-sm">
					'.$edi->dump_edi( $parsed ).'
					</div>';
			else
				echo "<br><h2>Error: ",  $edi->getMessage(), "</h2>";
			
			
		}
		
	}
		
	echo '</div>';	
}
?>

	<script language="JavaScript" type="text/javascript"><!--
		
		$(document).ready( function () {
			//alert(($(window).height() - 150) + "px");

			$('#EXP_EDI').dataTable({
		        "bLengthChange": false,
		        "bFilter": false,
		        "bSort": false,
		        "bInfo": false,
				//"bAutoWidth": false,
				//"bProcessing": true, 
				"sScrollX": "100%",
				"sScrollY": "250px",
				//"sScrollXInner": "120%",
		        //"lengthMenu": [<?php echo isset($sts_length_menu) ? $sts_length_menu : '25, 50, 100, 250'; ?>],
				"bPaginate": false,
				"bScrollCollapse": false,
				"bSortClasses": false		
			});
			
		});
	//--></script>

<?php
require_once( "include/footer_inc.php" );

?>