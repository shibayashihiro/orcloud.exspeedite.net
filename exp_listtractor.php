<?php 

// $Id: exp_listtractor.php 4350 2021-03-02 19:14:52Z duncan $
// List Tractors

// Set flag that this is a parent file
define( '_STS_INCLUDE', 1 );

// Setup Session
require_once( "include/sts_session_setup.php" );

require_once( "include/sts_config.php" );
$sts_debug = isset($_GET['debug']) && in_group( EXT_GROUP_DEBUG );
require_once( "include/sts_session_class.php" );

$my_session = sts_session::getInstance( $exspeedite_db, $sts_debug );
$my_session->access_check( $sts_table_access[TRACTOR_TABLE], EXT_GROUP_MECHANIC );	// Make sure we should be here

$sts_subtitle = "List Tractors";
require_once( "include/header_inc.php" );
require_once( "include/navbar_inc.php" );

?>
<div class="container-full" role="main">
<?php
require_once( "include/sts_result_class.php" );
require_once( "include/sts_tractor_class.php" );
require_once( "include/sts_setting_class.php" );
require_once( "include/sts_report_class.php" );

$setting_table = sts_setting::getInstance($exspeedite_db, $sts_debug);
$sts_length_menu = $setting_table->get( 'option', 'LENGTH_MENU' );
$multi_company = ($setting_table->get("option", "MULTI_COMPANY") == 'true');
$ifta_enabled = ($setting_table->get("api", "IFTA_ENABLED") == 'true');
$fleet_enabled = $setting_table->get( 'option', 'FLEET_ENABLED' ) == 'true';
$kt_api_key = $setting_table->get( 'api', 'KEEPTRUCKIN_KEY' );
$kt_enabled = ! empty($kt_api_key);
$sts_inspection_reports = $setting_table->get( 'option', 'INSPECTION_REPORTS' ) == 'true';
$default_active = $setting_table->get("option", "DEFAULT_ACTIVE") == 'true';
$pivot_rm_enabled = $setting_table->get("option", "PIVOT_RM_REPORT") == 'true';

$report_table = sts_report::getInstance( $exspeedite_db, $sts_debug );

if( ! $multi_company ) {
	unset($sts_result_tractors_layout["COMPANY_CODE"],
		$sts_result_tractors_layout["OFFICE_CODE"]);
}

if( ! $ifta_enabled ) {
	unset($sts_result_tractors_layout["LOG_IFTA"],
		$sts_result_tractors_layout["LOG_IFTA"]);
}

if( $sts_inspection_reports && $my_session->in_group( EXT_GROUP_MECHANIC ) ) {
	unset($sts_result_tractors_edit["add"]);
}

$filters_html = '<div class="btn-group"><a class="btn btn-sm btn-success" href="exp_listtractor.php"><span class="glyphicon glyphicon-refresh"></span></a>';

if( ! isset($_SESSION['TRACTOR_ACTIVE']) ) $_SESSION['TRACTOR_ACTIVE'] = $default_active ? 'Active' : 'All';
if( isset($_POST['TRACTOR_ACTIVE']) ) $_SESSION['TRACTOR_ACTIVE'] = $_POST['TRACTOR_ACTIVE'];

$valid_actives = array(	'All' => 'All tractors',
						'Active' => 'Active tractors', 
						'Inactive' => 'Inactive tractors',
						'OOS' => 'OOS tractors' );

$filters_html .= '<select class="form-control input-sm" name="TRACTOR_ACTIVE" id="TRACTOR_ACTIVE"   onchange="form.submit();">';
foreach( $valid_actives as $value => $label ) {
	$filters_html .= '<option value="'.$value.'" '.($_SESSION['TRACTOR_ACTIVE'] == $value ? 'selected' : '').'>'.$label.'</option>
	';
}
$filters_html .= '</select>';

if( $fleet_enabled && ! ($sts_inspection_reports && $my_session->in_group( EXT_GROUP_MECHANIC)))
	$filters_html .= ' <a class="btn btn-sm btn-default" href="exp_listfleet.php"><img src="images/tractor_icon.png" alt="tractor_icon" height="18"> Tractor Fleets</a>';
$filters_html .= '</div>';

// close the session here to avoid blocking
session_write_close();

$sts_result_tractors_edit['filters_html'] = $filters_html;

if( ! $fleet_enabled ) {
	unset($sts_result_tractors_layout['FLEET_CODE']);
}

if( $kt_enabled && $my_session->in_group( EXT_GROUP_ADMIN ) ) {
	$sts_result_tractors_edit['filters_html'] .=
	'<a class="btn btn-sm btn-default tip" href="exp_import_tractors_kt.php" title="Import tractors from KeepTruckin"><img src="images/keeptruckin.png" alt="keeptruckin" height="18"></a>';	
}

//! SCR# 617 - show R&M Report link	
$rmr =  is_array($report_table->reports) &&
	count($report_table->reports) > 0 &&
	array_search('R&M Report', array_column($report_table->reports, 'REPORT_NAME')) !== false; 

if( $pivot_rm_enabled && $rmr ) {
	$sts_result_tractors_edit['filters_html'] .=
	' <a class="btn btn-sm btn-info tip" href="exp_listinsp_report2.php" title="R&M Report"><span class="glyphicon glyphicon-wrench"></span> R&M Report</a>';	
}
		

$tractor_table = sts_tractor::getInstance($exspeedite_db, $sts_debug);
$rslt = new sts_result( $tractor_table, false, $sts_debug );

$match = $rslt->get_match();

if( $_SESSION['TRACTOR_ACTIVE'] <> 'All' ) {
	$match = ($match <> '' ? $match." AND " : "")."ISACTIVE = '".$_SESSION['TRACTOR_ACTIVE']."'";
}

echo $rslt->render( $sts_result_tractors_layout, $sts_result_tractors_edit, false, false );


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
				//"sScrollXInner": "120%",
		        "lengthMenu": [<?php echo isset($sts_length_menu) ? $sts_length_menu : '25, 50, 100, 250'; ?>],
				"bPaginate": true,
				"bScrollCollapse": false,
				"bSortClasses": false,
				"order": [[ 1, "asc" ]],
				"processing": true,
				"serverSide": true,
				//"dom": "frtiS",
				"deferRender": true,
				"ajax": {
					"url": "exp_listtractorajax.php",
					"data": function( d ) {
						d.match = encodeURIComponent("<?php echo $match; ?>");
					}

				},
				"columns": [
					{ "searchable": false, "orderable": false },
					<?php
						foreach( $sts_result_tractors_layout as $key => $row ) {
							if( $row["format"] <> 'hidden')
								echo '{ "data": "'.$key.'", "searchable": '.
								(isset($row["searchable"]) && $row["searchable"] ?  'true' : 'false' ).
								(isset($row["align"]) ? ', "className": "text-'.$row["align"].'"' : '').
									(isset($row["length"]) ? ', "width": "'.$row["length"].'px"' : '').
									(isset($row["format"]) && $row["format"] == 'hidden' ? ', "visible": false' : '').' },
						';						}
					?>
				],
				"rowCallback": function( row, data ) {
					var expired = $(row).attr('expired');
					switch(expired) {
						case 'red':
							$(row).addClass("danger");
							break;
						
						case 'orange':
							$(row).addClass("inprogress2");
							break;
						
						case 'yellow':
							$(row).addClass("warning");
							break;
						
						case 'green':
						default:
							break;
					}
				}
						
			};
			
			var myTable = $('#EXP_TRACTOR').dataTable(opts);
			$('#EXP_TRACTOR').on( 'draw.dt', function () {
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

