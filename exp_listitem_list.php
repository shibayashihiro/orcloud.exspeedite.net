<?php 
// Set flag that this is a parent file
define( '_STS_INCLUDE', 1 );

// Setup Session
require_once( "include/sts_session_setup.php" );

require_once( "include/sts_config.php" );
$sts_debug = isset($_GET['debug']) && in_group( EXT_GROUP_DEBUG );
require_once( "include/sts_session_class.php" );

$my_session = sts_session::getInstance( $exspeedite_db, $sts_debug );
$my_session->access_check( EXT_GROUP_ADMIN );	// Make sure we should be here

$sts_subtitle = "List Items";
require_once( "include/header_inc.php" );
require_once( "include/navbar_inc.php" );

?>
<div class="container-full" role="main">
<?php
require_once( "include/sts_result_class.php" );
require_once( "include/sts_item_list_class.php" );

$item_list_table = sts_item_list::getInstance($exspeedite_db, $sts_debug);

if( isset($_GET) && isset($_GET["DEFAULTS"])) {
	$item_list_table->load_defaults( true );
	$item_list_table->write_cache();
}

if( ! isset($_SESSION['ITEM_TYPE']) ) $_SESSION['ITEM_TYPE'] = 'All';
if( isset($_POST['ITEM_TYPE']) ) $_SESSION['ITEM_TYPE'] = $_POST['ITEM_TYPE'];

$match = false;
if( $_SESSION['ITEM_TYPE'] <> 'All')
	$match = "ITEM_TYPE = '".$_SESSION['ITEM_TYPE']."'";

$filters_html = "";
$valid_sources = $item_list_table->get_types();

if( $valid_sources ) {
	$filters_html .= '<select class="form-control input-sm" name="ITEM_TYPE" id="ITEM_TYPE"   onchange="form.submit();">';
		$filters_html .= '<option value="All" '.($_SESSION['ITEM_TYPE'] == 'All' ? 'selected' : '').'>All Types</option>
		';
	foreach( $valid_sources as $source ) {
		$filters_html .= '<option value="'.$source.'" '.($_SESSION['ITEM_TYPE'] == $source ? 'selected' : '').'>'.$source.'</option>
		';
	}
	$filters_html .= '</select>';
}

if( $my_session->superadmin() ) {
	$filters_html .= '<a class="btn btn-sm btn-danger" href="exp_listitem_list.php?DEFAULTS"><span class="glyphicon glyphicon-refresh"></span> Reload Defaults</a>';
}

$sts_result_item_list_edit['filters_html'] = $filters_html;

$rslt = new sts_result( $item_list_table, $match, $sts_debug );
echo $rslt->render( $sts_result_item_list_layout, $sts_result_item_list_edit );

?>
</div>

	<script language="JavaScript" type="text/javascript"><!--
		
		$(document).ready( function () {
			//alert(($(window).height() - 150) + "px");
<?php if( ! isset($_GET["debug"]) ) { ?>
			document.documentElement.style.overflow = 'hidden';  // firefox, chrome
			document.body.scroll = "no"; // ie only
<?php } ?>

			$('#EXP_ITEM_LIST').dataTable({
		        //"bLengthChange": false,
		        "bFilter": true,
		        "bSort": true,
		        "bInfo": false,
				"bAutoWidth": false,
				//"bProcessing": true, 
				"sScrollX": "100%",
				"sScrollY": ($(window).height() - 255) + "px",
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

