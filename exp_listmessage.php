<?php 

// $Id: exp_listmessage.php 4350 2021-03-02 19:14:52Z duncan $
// List message

// Set flag that this is a parent file
define( '_STS_INCLUDE', 1 );

// Setup Session
require_once( "include/sts_session_setup.php" );

require_once( "include/sts_config.php" );
$sts_debug = isset($_GET['debug']) && in_group( EXT_GROUP_DEBUG );
require_once( "include/sts_session_class.php" );

$my_session = sts_session::getInstance( $exspeedite_db, $sts_debug );
$my_session->access_check( EXT_GROUP_ADMIN, EXT_GROUP_MANAGER );	// Make sure we should be here

$sts_subtitle = "List Messages";
require_once( "include/header_inc.php" );
require_once( "include/navbar_inc.php" );

?>
<div class="container-full" role="main">
<?php
require_once( "include/sts_result_class.php" );
require_once( "include/sts_message_class.php" );

$filters_html = '<div class="btn-group"><a class="btn btn-sm btn-success" href="exp_listmessage.php"><span class="glyphicon glyphicon-refresh"></span></a></div>';

$sts_result_message_edit['filters_html'] = $filters_html;

$message_table = new sts_message($exspeedite_db, $sts_debug);

//! SCR# 547 - Make messages expire after so many days
$expired = $message_table->expire();
if( $expired > 0 )
	$sts_result_message_edit['title'] .= ' ('.$expired.' expired)';

if( ! $message_table->multi_company() )
	unset($sts_result_message_layout['OFFICE_CODE']);
$rslt = new sts_result( $message_table, false, $sts_debug );
echo $rslt->render( $sts_result_message_layout, $sts_result_message_edit );

?>
</div>

	<script language="JavaScript" type="text/javascript"><!--
		
		$(document).ready( function () {
			//alert(($(window).height() - 150) + "px");
			<?php if( ! $sts_debug ) { ?>
			document.documentElement.style.overflow = 'hidden';  // firefox, chrome
			document.body.scroll = "no"; // ie only
			<?php } ?>

			$('#EXP_MESSAGE').dataTable({
		        //"bLengthChange": false,
		        "bFilter": true,
		        "bSort": true,
		        "bInfo": false,
				//"bAutoWidth": false,
				//"bProcessing": true, 
				"sScrollX": "100%",
				"sScrollY": ($(window).height() - 260) + "px",
				"sScrollXInner": "150%",
		        "lengthMenu": [<?php echo isset($sts_length_menu) ? $sts_length_menu : '25, 50, 100, 250'; ?>],
				"bPaginate": true,
				"bScrollCollapse": false,
				"bSortClasses": false,
				"order": [[ 1, "asc" ]],
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

