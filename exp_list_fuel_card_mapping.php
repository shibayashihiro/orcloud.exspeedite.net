<?php 

// $Id: exp_list_fuel_card_mapping.php 4350 2021-03-02 19:14:52Z duncan $
// Fuel Card Mapping - admin screen for viewing

// Set flag that this is a parent file
define( '_STS_INCLUDE', 1 );

// Setup Session
require_once( "include/sts_session_setup.php" );

require_once( "include/sts_config.php" );
$sts_debug = isset($_GET['debug']) && in_group( EXT_GROUP_DEBUG );
require_once( "include/sts_session_class.php" );

$my_session = sts_session::getInstance( $exspeedite_db, $sts_debug );
$my_session->access_check( EXT_GROUP_ADMIN );	// Make sure we should be here

$sts_subtitle = "Fuel Card Mapping";
require_once( "include/header_inc.php" );
require_once( "include/navbar_inc.php" );

?>
<div class="container-full tighter" role="main">
<?php
require_once( "include/sts_result_class.php" );
require_once( "include/sts_card_class.php" );

	$filters_html = '<div class="form-group"><a class="btn btn-sm btn-default" href="exp_list_card_ftp.php"><span class="glyphicon glyphicon-import"></span> Configuration</a></div>';
	$filters_html .= '<div class="form-group"><a class="btn btn-sm btn-default" href="exp_listca.php"><span class="glyphicon glyphicon-usd"></span> Advances</a></div>';
	$sts_result_tractor_card_edit['filters_html'] = $filters_html;

$tractor_card_table = new sts_tractor_card($exspeedite_db, $sts_debug);
$rslt2 = new sts_result( $tractor_card_table, false, $sts_debug );
echo '<div class="well well-sm tighter tighter-top">'.$rslt2->render( $sts_result_tractor_card_layout, $sts_result_tractor_card_edit ).'</div>';

$driver_card_table = new sts_driver_card($exspeedite_db, $sts_debug);
$rslt = new sts_result( $driver_card_table, false, $sts_debug );
echo '<div class="well well-sm tighter tighter-top">'.$rslt->render( $sts_result_driver_card_layout, $sts_result_driver_card_edit ).'</div>';

?>
</div>

	<script language="JavaScript" type="text/javascript"><!--
		
		$(document).ready( function () {
			//alert(($(window).height() - 150) + "px");
			<?php if( ! $sts_debug ) { ?>
			document.documentElement.style.overflow = 'hidden';  // firefox, chrome
			document.body.scroll = "no"; // ie only
			<?php } ?>

			$('ul.dropdown-menu li a#modal').on('click', function(event) {
				event.preventDefault();
				$('#listload_modal').modal({
					container: 'body',
					remote: $(this).attr("href")
				}).modal('show');
				return false;					
			});

			
			$('#EXP_DRIVER_CARD, #EXP_TRACTOR_CARD').dataTable({
		        //"bLengthChange": false,
		        "bFilter": true,
		        "bSort": false,
		        "bInfo": false,
		        stateSave: true,
				"bAutoWidth": false,
				//"bProcessing": true, 
				"sScrollX": "100%",
				"sScrollY": (($(window).height() - 488)/2) + "px",
				//"sScrollXInner": "120%",
		        "lengthMenu": [[-1, 25, 50], ["All", 25, 50]],
				"bPaginate": true,
				//"bScrollCollapse": true,
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

