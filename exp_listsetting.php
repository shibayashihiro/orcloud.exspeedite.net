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

$sts_subtitle = "List Settings";
require_once( "include/header_inc.php" );
require_once( "include/navbar_inc.php" );

?>
<div class="container-full" role="main">
<?php
require_once( "include/sts_result_class.php" );
require_once( "include/sts_setting_class.php" );

$setting_table = sts_setting::getInstance($exspeedite_db, $sts_debug);

if( ! empty($_GET["category"]) && ! empty($_GET["setting"]) ) {
	echo '<p id="CATEGORY">'.$_GET["category"].'</p>
	<p id="SETTING">'.$_GET["setting"].'</p>
	<p id="THEVALUE">'.$setting_table->get($_GET["category"], $_GET["setting"]).'</p>';
	die;
}


if( ! isset($_SESSION['SETTING_CATEGORY']) ) $_SESSION['SETTING_CATEGORY'] = 'All';
if( isset($_POST['SETTING_CATEGORY']) ) $_SESSION['SETTING_CATEGORY'] = $_POST['SETTING_CATEGORY'];

$match = false;
if( $_SESSION['SETTING_CATEGORY'] <> 'All')
	$match = "CATEGORY = '".$_SESSION['SETTING_CATEGORY']."'";

$filters_html = '<div class="btn-group"><a class="btn btn-sm btn-success" href="exp_listsetting.php"><span class="glyphicon glyphicon-refresh"></span></a>';
$valid_categories = $setting_table->get_categories();

if( $valid_categories ) {
	$filters_html .= '<select class="form-control input-sm" name="SETTING_CATEGORY" id="SETTING_CATEGORY"   onchange="form.submit();">';
		$filters_html .= '<option value="All" '.($_SESSION['SETTING_CATEGORY'] == 'All' ? 'selected' : '').'>All Categories</option>
		';
	foreach( $valid_categories as $category ) {
		$filters_html .= '<option value="'.$category.'" '.($_SESSION['SETTING_CATEGORY'] == $category ? 'selected' : '').'>'.$category.' Category</option>
		';
	}
	$filters_html .= '</select>';
}

$sts_result_settings_edit['filters_html'] = $filters_html;

$sts_result_settings_edit['title'] .= ' (cached '.$setting_table->date_cached().')';

$rslt = new sts_result( $setting_table, $match, $sts_debug );
echo $rslt->render( $sts_result_settings_layout, $sts_result_settings_edit );

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

			
			var table = $('#EXP_SETTING').DataTable({
		        //"bLengthChange": false,
		        "bFilter": true,
		        "bSort": true,
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

			<?php if( isset($_GET["SEARCH"]) ) { ?>
			table.search("<?php echo $_GET["SEARCH"]; ?>").draw();
			<?php } ?>
			
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

