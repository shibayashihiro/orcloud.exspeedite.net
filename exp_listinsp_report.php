<?php 

// $Id: exp_listinsp_report.php 4350 2021-03-02 19:14:52Z duncan $
// list inspection reports front end

// Set flag that this is a parent file
define( '_STS_INCLUDE', 1 );

// Setup Session
require_once( "include/sts_session_setup.php" );

require_once( "include/sts_config.php" );
$sts_debug = isset($_GET['debug']) && in_group( EXT_GROUP_DEBUG );
require_once( "include/sts_session_class.php" );

$my_session = sts_session::getInstance( $exspeedite_db, $sts_debug );
$my_session->access_check( EXT_GROUP_MECHANIC, EXT_GROUP_FLEET );	// Make sure we should be here

$sts_subtitle = "List Inspection Reports";
require_once( "include/header_inc.php" );
require_once( "include/navbar_inc.php" );

?>
<div class="container" role="main">
<?php
require_once( "include/sts_result_class.php" );
require_once( "include/sts_insp_report_class.php" );
require_once( "include/sts_setting_class.php" );
require_once( "include/sts_rm_form_class.php" );

$setting_table = sts_setting::getInstance($exspeedite_db, $sts_debug);
$insp_title = $setting_table->get( 'option', 'INSPECTION_REPORT_TITLE' );

$report_table = sts_insp_report::getInstance($exspeedite_db, $sts_debug);
$rm_form_table = sts_rm_form::getInstance($exspeedite_db, $sts_debug);

unset($sts_result_insp_report_edit['add']);

if( ! isset($_SESSION['RM_UNIT_TYPE']) ) $_SESSION['RM_UNIT_TYPE'] = 'All';
if( ! isset($_SESSION['RM_UNIT_NUMBER']) ) $_SESSION['RM_UNIT_NUMBER'] = 'All';

if( isset($_POST['RM_UNIT_TYPE']) ) {
	if( $_SESSION['RM_UNIT_TYPE'] <> $_POST['RM_UNIT_TYPE'])
		$_SESSION['RM_UNIT_NUMBER'] = 'All';
	else if( isset($_POST['RM_UNIT_NUMBER']) )
		$_SESSION['RM_UNIT_NUMBER'] = $_POST['RM_UNIT_NUMBER'];
	$_SESSION['RM_UNIT_TYPE'] = $_POST['RM_UNIT_TYPE'];
}

$match = false;
if( $_SESSION['RM_UNIT_TYPE'] <> 'All')
	$match = "UNIT_TYPE = '".$_SESSION['RM_UNIT_TYPE']."'";

if( $_SESSION['RM_UNIT_NUMBER'] <> 'All')
	$match = ($match <> '' ? $match." AND " : "")."UNIT = ".$_SESSION['RM_UNIT_NUMBER'];

$filters_html = '<div class="btn-group"><a class="btn btn-sm btn-success" href="exp_listinsp_report.php"><span class="glyphicon glyphicon-refresh"></span></a>';
$valid_sources = $rm_form_table->get_types();

if( is_array($valid_sources) ) {
	$filters_html .= '<select class="form-control input-sm" name="RM_UNIT_TYPE" id="RM_UNIT_TYPE"   onchange="form.submit();">';
		$filters_html .= '<option value="All" '.($_SESSION['RM_UNIT_TYPE'] == 'All' ? 'selected' : '').'>All Types</option>
		';
	foreach( $valid_sources as $source ) {
		$filters_html .= '<option value="'.$source.'" '.($_SESSION['RM_UNIT_TYPE'] == $source ? 'selected' : '').'>'.$source.'</option>
		';
	}
	$filters_html .= '</select>';
}

if( $_SESSION['RM_UNIT_TYPE'] <> 'All' ) {
	$valid_sources = $rm_form_table->get_units($_SESSION['RM_UNIT_TYPE']);
	
	if( is_array($valid_sources) ) {
		$filters_html .= '<select class="form-control input-sm" name="RM_UNIT_NUMBER" id="RM_UNIT_NUMBER"   onchange="form.submit();">';
			$filters_html .= '<option value="All" '.($_SESSION['RM_UNIT_NUMBER'] == 'All' ? 'selected' : '').'>All '.$_SESSION['RM_UNIT_TYPE'].'s</option>
			';
		foreach( $valid_sources as $unit => $unit_number ) {
			$filters_html .= '<option value="'.$unit.'" '.($_SESSION['RM_UNIT_NUMBER'] == $unit ? 'selected' : '').'>'.$unit_number.'</option>
			';
		}
		$filters_html .= '</select>';
	}	
}

$filters_html .= '</div>';

$sts_result_insp_report_edit['filters_html'] = $filters_html;
$sts_result_insp_report_edit['cancel'] = 'index.php';
$sts_result_insp_report_edit['cancelbutton'] = 'Back';

$sts_result_insp_report_edit['title'] = '<span class="glyphicon glyphicon-wrench"></span> '.$insp_title.'s';

$rslt = new sts_result( $report_table, $match, $sts_debug );
echo $rslt->render( $sts_result_insp_report_all_layout, $sts_result_insp_report_edit, false, false );

?>
</div>

	<script language="JavaScript" type="text/javascript"><!--
		
		$(document).ready( function () {
			//alert(($(window).height() - 150) + "px");
			<?php if( ! $sts_debug ) { ?>
			document.documentElement.style.overflow = 'hidden';  // firefox, chrome
			document.body.scroll = "no"; // ie only
			<?php } ?>

			$('#EXP_INSP_REPORT').dataTable({
		        //"bLengthChange": false,
		        "bFilter": true,
		        "bSort": true,
		        "bInfo": false,
				"bAutoWidth": false,
				//"bProcessing": true, 
				"sScrollX": "100%",
				"sScrollY": ($(window).height() - 276) + "px",
				"sScrollXInner": "120%",
		        "lengthMenu": [<?php echo isset($sts_length_menu) ? $sts_length_menu : '25, 50, 100, 250'; ?>],
				"bPaginate": true,
				"bScrollCollapse": false,
				"bSortClasses": false,
				"order": [[ 1, "desc" ]],
				"processing": true,
				"serverSide": true,
				//"dom": "frtiS",
				"deferRender": true,
				"ajax": {
					"url": "exp_listinsp_reportajax.php",
					"data": function( d ) {
						d.match = encodeURIComponent("<?php echo $match; ?>");
					}
				},
				"columns": [
					{ "searchable": false, "orderable": false },
					<?php
						foreach( $sts_result_insp_report_all_layout as $key => $row ) {
							if( $row["format"] <> 'hidden')
								echo '{ "data": "'.$key.'", "searchable": '.
								(isset($row["searchable"]) && ! $row["searchable"] ? 'false' : 'true' ).
								(isset($row["align"]) ? ', "className": "text-'.$row["align"].'"' : '').
									(isset($row["length"]) ? ', "width": "'.$row["length"].'px"' : '').
									(isset($row["format"]) && $row["format"] == 'hidden' ? ', "visible": false' : '').' },
						';
						}
					?>
				],
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

