<?php 
// Set flag that this is a parent file
define( '_STS_INCLUDE', 1 );

// Setup Session
require_once( "include/sts_session_setup.php" );

require_once( "include/sts_config.php" );
$sts_debug = isset($_GET['debug']) && in_group( EXT_GROUP_DEBUG );
require_once( "include/sts_session_class.php" );

$my_session = sts_session::getInstance( $exspeedite_db, $sts_debug );
$my_session->access_check( $sts_table_access[UNIT_TABLE] );	// Make sure we should be here

$sts_subtitle = "List Units";
require_once( "include/header_inc.php" );
require_once( "include/navbar_inc.php" );

?>
<div class="container-full" role="main">
<?php
require_once( "include/sts_result_class.php" );
require_once( "include/sts_unit_class.php" );

$unit_table = new sts_unit($exspeedite_db, $sts_debug);
$rslt = new sts_result( $unit_table, false, $sts_debug );
echo $rslt->render( $sts_result_units_layout, $sts_result_units_edit );

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

			
			$('#EXP_UNIT').dataTable({
		        //"bLengthChange": false,
		        "bFilter": true,
		        "bSort": false,
		        "bInfo": false,
		        stateSave: true,
				"bAutoWidth": false,
				//"bProcessing": true, 
				"sScrollX": "100%",
				"sScrollY": ($(window).height() - 260) + "px",
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

