<?php 

// $Id: exp_listrm_form.php 4350 2021-03-02 19:14:52Z duncan $
// List RM form - list forms for tractors/trailers

// Set flag that this is a parent file
define( '_STS_INCLUDE', 1 );

// Setup Session
require_once( "include/sts_session_setup.php" );

require_once( "include/sts_config.php" );
$sts_debug = isset($_GET['debug']) && in_group( EXT_GROUP_DEBUG );
require_once( "include/sts_session_class.php" );

$my_session = sts_session::getInstance( $exspeedite_db, $sts_debug );
$my_session->access_check( EXT_GROUP_ADMIN );	// Make sure we should be here

$sts_subtitle = "List R&M Forms";
require_once( "include/header_inc.php" );
require_once( "include/navbar_inc.php" );

?>
<div class="container-full" role="main">
<?php
require_once( "include/sts_result_class.php" );
require_once( "include/sts_rm_form_class.php" );

$rm_form_table = sts_rm_form::getInstance($exspeedite_db, $sts_debug);

if( ! isset($_SESSION['RM_UNIT_TYPE']) ) $_SESSION['RM_UNIT_TYPE'] = 'All';
if( isset($_POST['RM_UNIT_TYPE']) ) $_SESSION['RM_UNIT_TYPE'] = $_POST['RM_UNIT_TYPE'];

$match = false;
if( $_SESSION['RM_UNIT_TYPE'] <> 'All')
	$match = "UNIT_TYPE = '".$_SESSION['RM_UNIT_TYPE']."'";

$filters_html = '<div class="btn-group"><a class="btn btn-sm btn-success" href="exp_listrm_form.php"><span class="glyphicon glyphicon-refresh"></span></a>';
$valid_sources = $rm_form_table->get_types();

if( $valid_sources ) {
	$filters_html .= '<select class="form-control input-sm" name="RM_UNIT_TYPE" id="RM_UNIT_TYPE"   onchange="form.submit();">';
		$filters_html .= '<option value="All" '.($_SESSION['RM_UNIT_TYPE'] == 'All' ? 'selected' : '').'>All Types</option>
		';
	foreach( $valid_sources as $source ) {
		$filters_html .= '<option value="'.$source.'" '.($_SESSION['RM_UNIT_TYPE'] == $source ? 'selected' : '').'>'.$source.'</option>
		';
	}
	$filters_html .= '</select>';
}

$filters_html .= '</div>';

$filters_html .= ' <div class="btn-group"><a class="btn btn-sm btn-default" href="exp_listrm_class.php"><span class="glyphicon glyphicon-th-list"></span> Classes</a>';

$sts_result_rm_form_edit['filters_html'] = $filters_html;

$rslt = new sts_result( $rm_form_table, $match, $sts_debug );
echo $rslt->render( $sts_result_rm_form_layout, $sts_result_rm_form_edit );

?>
</div>

	<script language="JavaScript" type="text/javascript"><!--
		
		$(document).ready( function () {
			//alert(($(window).height() - 150) + "px");
			if( <?php echo ($sts_debug ? 'true' : 'false'); ?> == 'false' ) {
				document.documentElement.style.overflow = 'hidden';  // firefox, chrome
				document.body.scroll = "no"; // ie only
			}

			$('#EXP_RM_FORM').dataTable({
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

