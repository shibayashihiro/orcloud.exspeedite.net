<?php 

// $Id: exp_listca.php 4350 2021-03-02 19:14:52Z duncan $
// List Fuel card advances not yet applied

// Set flag that this is a parent file
define( '_STS_INCLUDE', 1 );

// Setup Session
require_once( "include/sts_session_setup.php" );

require_once( "include/sts_config.php" );
$sts_debug = isset($_GET['debug']) && in_group( EXT_GROUP_DEBUG );
require_once( "include/sts_session_class.php" );

$my_session = sts_session::getInstance( $exspeedite_db, $sts_debug );
$my_session->access_check( EXT_GROUP_ADMIN );	// Make sure we should be here

$sts_subtitle = "List Fuel Card Advances";
require_once( "include/header_inc.php" );
require_once( "include/navbar_inc.php" );

?>
<div class="container-full" role="main">
<?php
require_once( "include/sts_result_class.php" );
require_once( "include/sts_card_advance_class.php" );

$match = 'NOT EXISTS (SELECT LOAD_PAY_RATE_ID
	FROM EXP_LOAD_PAY_RATE
	WHERE CARD_ADVANCE_CODE = ADVANCE_CODE)';

	$filters_html = '<a class="btn btn-sm btn-success" href="exp_listca.php"><span class="glyphicon glyphicon-refresh"></span></a>';

	$filters_html .= '<div class="form-group"><a class="btn btn-sm btn-default" href="exp_list_card_ftp.php"><span class="glyphicon glyphicon-import"></span> Configuration</a></div>';
	$filters_html .= '<div class="form-group"><a class="btn btn-sm btn-default" href="exp_list_fuel_card_mapping.php"><span class="glyphicon glyphicon-arrow-right"></span> Mapping</a></div>';
	$sts_result_card_advance_edit['filters_html'] = $filters_html;


$card_advance_table = sts_card_advance::getInstance($exspeedite_db, $sts_debug);
$rslt = new sts_result( $card_advance_table, $match, $sts_debug );
echo $rslt->render( $sts_result_card_advance_layout, $sts_result_card_advance_edit );

?>
</div>

	<script language="JavaScript" type="text/javascript"><!--
		
		$(document).ready( function () {
			//alert(($(window).height() - 150) + "px");
			<?php if( ! $sts_debug ) { ?>
			document.documentElement.style.overflow = 'hidden';  // firefox, chrome
			document.body.scroll = "no"; // ie only
			<?php } ?>

			$('#EXP_CARD_ADVANCE').dataTable({
		        //"bLengthChange": false,
		        "bFilter": true,
		        "bSort": true,
		        "bInfo": false,
				"bAutoWidth": false,
				//"bProcessing": true, 
				"sScrollX": "100%",
				"sScrollY": ($(window).height() - 265) + "px",
				//"sScrollXInner": "120%",
				"order": [[ 2, "desc" ]],
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

