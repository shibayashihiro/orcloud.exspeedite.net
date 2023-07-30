<?php 
// Set flag that this is a parent file
define( '_STS_INCLUDE', 1 );

// Setup Session
require_once( "include/sts_session_setup.php" );

require_once( "include/sts_config.php" );
$sts_debug = isset($_GET['debug']) && in_group( EXT_GROUP_DEBUG );
require_once( "include/sts_session_class.php" );

$my_session = sts_session::getInstance( $exspeedite_db, $sts_debug );
$my_session->access_check( $sts_table_access[DRIVER_TABLE] );	// Make sure we should be here

$sts_subtitle = "List Drivers";
require_once( "include/header_inc.php" );
require_once( "include/navbar_inc.php" );

?>
<div class="container-full" role="main">
<?php
require_once( "include/sts_result_class.php" );
require_once( "include/sts_driver_class.php" );
require_once( "include/sts_setting_class.php" );

$setting_table = sts_setting::getInstance($exspeedite_db, $sts_debug);
$sts_length_menu = $setting_table->get( 'option', 'LENGTH_MENU' );
$kt_api_key = $setting_table->get( 'api', 'KEEPTRUCKIN_KEY' );
$kt_enabled = ! empty($kt_api_key);
$driver_types = $setting_table->get("option", "DRIVER_TYPES") == 'true';
$default_active = $setting_table->get("option", "DEFAULT_ACTIVE") == 'true';

//! Check for multi-company
$multi_company = ($setting_table->get("option", "MULTI_COMPANY") == 'true');
if( ! $multi_company ) {
	unset($sts_result_drivers_layout["COMPANY_CODE"],
		$sts_result_drivers_layout["OFFICE_CODE"]);
}

if( ! isset($_SESSION['DRIVER_ACTIVE']) ) $_SESSION['DRIVER_ACTIVE'] = $default_active ? 'Active' : 'All';
if( isset($_POST['DRIVER_ACTIVE']) ) $_SESSION['DRIVER_ACTIVE'] = $_POST['DRIVER_ACTIVE'];

if( $multi_company ) {
	if( ! isset($_SESSION['DRIVER_OFFICE']) ) $_SESSION['DRIVER_OFFICE'] = 'all';
	if( isset($_POST['DRIVER_OFFICE']) ) $_SESSION['DRIVER_OFFICE'] = $_POST['DRIVER_OFFICE'];
}

$valid_actives = array(	'All' => 'All drivers',
						'Active' => 'Active drivers', 
						'Inactive' => 'Inactive drivers',
						'OOS' => 'OOS drivers' );

//! SCR# 427 - additional driver type
if( $driver_types ) {
	$valid_actives['staff'] = 'Staff (non-drivers)';
}

$filters_html = '<div class="btn-group">';


$filters_html = '<a class="btn btn-sm btn-success" href="exp_listdriver.php"><span class="glyphicon glyphicon-refresh"></span></a>';

$filters_html .= '<select class="form-control input-sm" name="DRIVER_ACTIVE" id="DRIVER_ACTIVE"   onchange="form.submit();">';
foreach( $valid_actives as $value => $label ) {
	$filters_html .= '<option value="'.$value.'" '.($_SESSION['DRIVER_ACTIVE'] == $value ? 'selected' : '').'>'.$label.'</option>
	';
}
$filters_html .= '</select>';

if( $multi_company && count($_SESSION['EXT_USER_OFFICES']) > 1 ) {
	$filters_html .= '<select class="form-control input-sm" name="DRIVER_OFFICE" id="DRIVER_OFFICE"   onchange="form.submit();">';
	$filters_html .= '<option value="all" '.($_SESSION['DRIVER_OFFICE'] == 'all' ? 'selected' : '').'>All Offices</option>
		';
	foreach( $_SESSION['EXT_USER_OFFICES'] as $value => $label ) {
		$filters_html .= '<option value="'.$value.'" '.($_SESSION['DRIVER_OFFICE'] == $value ? 'selected' : '').'>'.$label.'</option>
		';
	}
	$filters_html .= '</select>';
}

// close the session here to avoid blocking
session_write_close();

$sts_result_drivers_edit['filters_html'] = $filters_html;

if( $kt_enabled && $my_session->in_group( EXT_GROUP_ADMIN ) ) {
	$sts_result_drivers_edit['filters_html'] .=
	'<a class="btn btn-sm btn-default tip" onclick="confirmation(\'Confirm: IMPORT DRIVERS FROM KEEP TRUCKIN?<br><br>This will add or update drivers in Exspeedite.<br>It may create duplicate driver records if first and last names don\\\'t match\',\'exp_import_drivers_kt.php\')" title="Import drivers from KeepTruckin"><img src="images/keeptruckin.png" alt="keeptruckin" height="18"></a>';	
}
		
$driver_table = new sts_driver_lj($exspeedite_db, $sts_debug);
$rslt = new sts_result( $driver_table, false, $sts_debug );

$match = $rslt->get_match();

//! SCR# 427 - filter on staff
if( $driver_types ) {
	if( $_SESSION['DRIVER_ACTIVE'] == 'staff' ) {
		$match = ($match <> '' ? $match." AND " : "")."DRIVER_TYPE = 'staff'";
	} else {
		$match = ($match <> '' ? $match." AND " : "")."DRIVER_TYPE = 'driver'";
	}
}

if( ! in_array($_SESSION['DRIVER_ACTIVE'], array( 'All', 'staff') ) ) {
	$match = ($match <> '' ? $match." AND " : "")."ISACTIVE = '".$_SESSION['DRIVER_ACTIVE']."'";
}

if( $multi_company ) {
	if( $_SESSION['DRIVER_OFFICE'] != 'all' )
		$match = ($match <> '' ? $match." AND " : "")."OFFICE_CODE = ".$_SESSION['DRIVER_OFFICE'];
}

echo $rslt->render( $sts_result_drivers_layout, $sts_result_drivers_edit, false, false );


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
				"order": [[ 3, "asc" ], [ 1, "asc" ]],
				"processing": true,
				"serverSide": true,
				//"dom": "frtiS",
				"deferRender": true,
				"ajax": {
					"url": "exp_listdriverajax.php",
					"data": function( d ) {
						d.match = encodeURIComponent("<?php echo $match; ?>");
					}

				},
				"columns": [
					{ "searchable": false, "orderable": false },
					<?php
						foreach( $sts_result_drivers_layout as $key => $row ) {
							if( $row["format"] <> 'hidden')
								echo '{ "data": "'.$key.'", "searchable": '.
								(isset($row["searchable"]) && ! $row["searchable"] ? 'false' : 'true' ).
								', "orderable": '.
								(isset($row["sortable"]) && ! $row["sortable"] ? 'false' : 'true').
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
			
			var myTable = $('#EXP_DRIVER').dataTable(opts);
			$('#EXP_DRIVER').on( 'draw.dt', function () {
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

