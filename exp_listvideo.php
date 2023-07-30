<?php 

// $Id: exp_listvideo.php 4697 2022-03-09 23:02:23Z duncan $
// List Videos

// Set flag that this is a parent file
define( '_STS_INCLUDE', 1 );

// Setup Session
require_once( "include/sts_session_setup.php" );

require_once( "include/sts_config.php" );
$sts_debug = isset($_GET['debug']) && in_group( EXT_GROUP_DEBUG );
require_once( "include/sts_session_class.php" );

$my_session = sts_session::getInstance( $exspeedite_db, $sts_debug );
$my_session->access_check( EXT_GROUP_ADMIN );	// Make sure we should be here

$sts_subtitle = "List Videos";
require_once( "include/header_inc.php" );
require_once( "include/navbar_inc.php" );

?>
<div class="container-full" role="main">
<?php
require_once( "include/sts_result_class.php" );
require_once( "include/sts_report_class.php" );

$video_table = sts_video::getInstance($exspeedite_db, $sts_debug);

if( isset($_GET["CACHE"])) {
	if (ob_get_level() == 0) ob_start();
	
	echo '<div class="container" role="main">
		<div id="loading"><h2 class="text-center"><img src="images/loading.gif" alt="loading" width="125" height="125" /><br>Updating Cached Static Information...</h2></div></div>';
	ob_flush(); flush();

	$video_table->write_cache();
	update_message( 'loading', '' );
	ob_flush(); flush();
}

$filters_html = '<div class="btn-group"><a class="btn btn-sm btn-success" href="exp_listvideo.php"><span class="glyphicon glyphicon-refresh"></span></a></div>';

$filters_html .= ' <div class="btn-group"><a class="btn btn-sm btn-danger" href="exp_listvideo.php?CACHE"><span class="glyphicon glyphicon-refresh"></span> Update Cache</a>';

$sts_result_videos_edit['filters_html'] = $filters_html;

$sts_result_videos_edit['title'] .= ' (cached '.$video_table->date_cached().')';

$rslt = new sts_result( $video_table, false, $sts_debug );
echo $rslt->render( $sts_result_videos_layout, $sts_result_videos_edit );

?>
</div>

	<script language="JavaScript" type="text/javascript"><!--
		
		$(document).ready( function () {
			//alert(($(window).height() - 150) + "px");
			<?php if( ! $sts_debug ) { ?>
			document.documentElement.style.overflow = 'hidden';  // firefox, chrome
			document.body.scroll = "no"; // ie only
			<?php } ?>

			$('#EXP_VIDEO').dataTable({
		        //"bLengthChange": false,
		        "bFilter": true,
		        "bSort": true,
		        "bInfo": true,
				"bAutoWidth": false,
				//"bProcessing": true, 
				"sScrollX": "100%",
				"sScrollY": ($(window).height() - 256) + "px",
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

