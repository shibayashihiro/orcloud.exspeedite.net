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

$sts_subtitle = "List Commodity Classes";
require_once( "include/header_inc.php" );
require_once( "include/navbar_inc.php" );

?>
<div class="container-full" role="main">
<?php
require_once( "include/sts_result_class.php" );
require_once( "include/sts_commodity_class_class.php" );

$filters_html = ' <div class="btn-group"><a class="btn btn-sm btn-success" href="exp_listcommodity_class.php"><span class="glyphicon glyphicon-refresh"></span></a>';

// close the session here to avoid blocking
session_write_close();

$sts_result_commodity_classs_edit['filters_html'] .= $filters_html;

$commodity_class_table = new sts_commodity_class($exspeedite_db, $sts_debug);
$rslt = new sts_result( $commodity_class_table, false, $sts_debug );
echo $rslt->render( $sts_result_commodity_classs_layout, $sts_result_commodity_classs_edit );

?>
</div>
	<script language="JavaScript" type="text/javascript"><!--
		
		$(document).ready( function () {
			//alert(($(window).height() - 150) + "px");
			<?php if( ! $sts_debug ) { ?>
			document.documentElement.style.overflow = 'hidden';  // firefox, chrome
			document.body.scroll = "no"; // ie only
			<?php } ?>

			var opts = {
		        //"bLengthChange": false,
		        "bFilter": true,
		        stateSave: true,
		        "bSort": true,
		        "bInfo": true,
				//"bAutoWidth": false,
				//"bProcessing": true, 
				"sScrollX": "100%",
				"sScrollY": ($(window).height() - 275) + "px",
				"sScrollXInner": "100%",
		        "lengthMenu": [<?php echo isset($sts_length_menu) ? $sts_length_menu : '25, 50, 100, 250'; ?>],
				"bPaginate": true,
				"bScrollCollapse": false,
				"bSortClasses": false,
				"order": [[ 1, "asc" ]],
				"processing": true,
				"columns": [
					{ "searchable": false, "orderable": false },
					<?php
						foreach( $sts_result_commodity_classs_layout as $key => $row ) {
							if( $row["format"] <> 'hidden')
								echo '{ "data": "'.$key.'", "searchable": '.
								(isset($row["searchable"]) && ! $row["searchable"] ? 'false' : 'true' ).
								(isset($row["align"]) ? ', "className": "text-'.$row["align"].'"' : '').
									(isset($row["length"]) ? ', "width": "'.$row["length"].'px"' : '').
									(isset($row["format"]) && $row["format"] == 'hidden' ? ', "visible": false' : '').' },
						';
						}
					?>
				]
						
			};
			
			var myTable = $('#EXP_COMMODITY_CLASS').dataTable(opts);
			$('#EXP_COMMODITY_CLASS').on( 'draw.dt', function () {
				myTable.$('.inform').popover({ 
					placement: 'top',
					html: 'true',
					container: 'body',
					trigger: 'hover',
					delay: { show: 50, hide: 3000 },
					title: '<strong>Information</strong> <button type="button" class="close" data-hide="confirm" data-delay="0" aria-hidden="true">&times;</button>' 
				});

				myTable.$('.confirm').popover({ 
					placement: 'top',
					html: 'true',
					container: 'body',
					trigger: 'hover',
					delay: { show: 50, hide: 3000 },
					title: '<strong>Confirm Action</strong> <button type="button" class="close" data-hide="confirm" data-delay="0" aria-hidden="true">&times;</button>' 
				});
			});
			//myTable.$("a[rel=popover]").popover().click(function(e) {e.preventDefault();});
			
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

