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

$sts_subtitle = "List Status Codes";
require_once( "include/header_inc.php" );
require_once( "include/navbar_inc.php" );

?>
<div class="container-full" role="main">
<?php
require_once( "include/sts_result_class.php" );
require_once( "include/sts_status_codes_class.php" );

$status_codes_table = sts_status_codes::getInstance($exspeedite_db, $sts_debug);

if( isset($_GET["CACHE"])) {
	if (ob_get_level() == 0) ob_start();
	
	echo '<div class="container" role="main">
		<div id="loading"><h2 class="text-center"><img src="images/loading.gif" alt="loading" width="125" height="125" /><br>Updating Cached Static Information...</h2></div></div>';
	ob_flush(); flush();

	$status_codes_table->write_cache();
	update_message( 'loading', '' );
	ob_flush(); flush();
}

if( ! isset($_SESSION['STATUS__CODES_SOURCE']) ) $_SESSION['STATUS__CODES_SOURCE'] = 'All';
if( isset($_POST['STATUS__CODES_SOURCE']) ) $_SESSION['STATUS__CODES_SOURCE'] = $_POST['STATUS__CODES_SOURCE'];

$match = false;
if( $_SESSION['STATUS__CODES_SOURCE'] <> 'All')
	$match = "SOURCE_TYPE = '".$_SESSION['STATUS__CODES_SOURCE']."'";

$filters_html = '<div class="btn-group"><a class="btn btn-sm btn-success" href="exp_liststatus_codes.php"><span class="glyphicon glyphicon-refresh"></span></a>';
	
$valid_sources = $status_codes_table->get_sources();

if( $valid_sources ) {
	$filters_html .= '<select class="form-control input-sm" name="STATUS__CODES_SOURCE" id="STATUS__CODES_SOURCE"   onchange="form.submit();">';
		$filters_html .= '<option value="All" '.($_SESSION['STATUS__CODES_SOURCE'] == 'All' ? 'selected' : '').'>All Sources</option>
		';
	foreach( $valid_sources as $source ) {
		$filters_html .= '<option value="'.$source.'" '.($_SESSION['STATUS__CODES_SOURCE'] == $source ? 'selected' : '').'>'.$source.'</option>
		';
	}
	$filters_html .= '</select>';
}

$filters_html .= ' <div class="btn-group"><a class="btn btn-sm btn-danger" href="exp_liststatus_codes.php?CACHE"><span class="glyphicon glyphicon-refresh"></span> Update Cache</a>';

$sts_result_status_codess_edit['filters_html'] = $filters_html;

$sts_result_status_codess_edit['title'] .= ' (cached '.$status_codes_table->date_cached().')';

$rslt = new sts_result( $status_codes_table, $match, $sts_debug );
if( ! $my_session->superadmin() ) {
	unset($sts_result_status_codess_edit['add'], $sts_result_status_codess_edit['rowbuttons']);
	echo '
<div class="alert alert-warning alert-dismissable">
  <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
  <strong>Warning!</strong> This screen is read-only, because it is not meant to be user-changable. Any changes could break the state machine and interfere with the working of Exspeedite.
</div>
';
}
echo $rslt->render( $sts_result_status_codess_layout, $sts_result_status_codess_edit );

?>
</div>

	<script language="JavaScript" type="text/javascript"><!--
		
		$(document).ready( function () {
			//alert(($(window).height() - 150) + "px");

			$('ul.dropdown-menu li a#modal').on('click', function(event) {
				event.preventDefault();
				$('#listload_modal').modal({
					container: 'body',
					remote: $(this).attr("href")
				}).modal('show');
				return false;					
			});

			
			$('#EXP_STATUS_CODES').dataTable({
		        //"bLengthChange": false,
		        "bFilter": true,
		        "bSort": false,
		        "bInfo": false,
				//"bAutoWidth": false,
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

