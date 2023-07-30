<?php 
// Set flag that this is a parent file
define( '_STS_INCLUDE', 1 );

// Setup Session
require_once( "include/sts_session_setup.php" );

require_once( "include/sts_config.php" );
$sts_debug = isset($_GET['debug']) && in_group( EXT_GROUP_DEBUG );
require_once( "include/sts_session_class.php" );

$my_session = sts_session::getInstance( $exspeedite_db, $sts_debug );
$my_session->access_report( 'Mileage Report' );	// Make sure we should be here

$sts_subtitle = "Mileage Report";
require_once( "include/header_inc.php" );
require_once( "include/navbar_inc.php" );

?>
<div class="container-full" role="main">
<?php
require_once( "include/sts_result_class.php" );
require_once( "include/sts_mileage_class.php" );
require_once( "include/sts_company_class.php" );
require_once( "include/sts_setting_class.php" );
require_once( "include/sts_fleet_class.php" );

$company_table = sts_company::getInstance($exspeedite_db, $sts_debug);

$setting_table = sts_setting::getInstance($exspeedite_db, $sts_debug);
$sts_length_menu	= $setting_table->get( 'option', 'LENGTH_MENU' );
$multi_company		= $setting_table->get( 'option', 'MULTI_COMPANY' ) == 'true';
$sts_multi_currency	= $setting_table->get( 'option', 'MULTI_CURRENCY' ) == 'true';
$fleet_enabled = $setting_table->get( 'option', 'FLEET_ENABLED' ) == 'true';

$valid_durations = array(	'3' => 'Last 3 Months',
							'6' => 'Last 6 Months', 
							'12' => 'Last 12 Months',
							'all' => 'All');

$match_durations = array(	'3' => 'L.COMPLETED_DATE > DATE_SUB(NOW(), INTERVAL 3 MONTH)',
							'6' => 'L.COMPLETED_DATE > DATE_SUB(NOW(), INTERVAL 6 MONTH)', 
							'12' => 'L.COMPLETED_DATE > DATE_SUB(NOW(), INTERVAL 12 MONTH)',
							'all' => '');

$valid_filters = array(	'all' => 'All',
						'unbilled' => 'Unbilled Shipments', 
						'revenue' => 'No Revenue',
						'expense' => 'No Expense');

if( ! isset($_SESSION['MILEAGE_DURATION']) ) $_SESSION['MILEAGE_DURATION'] = '3';
if( isset($_POST['MILEAGE_DURATION']) ) $_SESSION['MILEAGE_DURATION'] = $_POST['MILEAGE_DURATION'];

if( ! isset($_SESSION['MILEAGE_FILTER']) ) $_SESSION['MILEAGE_FILTER'] = 'all';
if( isset($_POST['MILEAGE_FILTER']) ) $_SESSION['MILEAGE_FILTER'] = $_POST['MILEAGE_FILTER'];

if( $multi_company ) {
	if( ! isset($_SESSION['MILEAGE_COMPANY']) ) $_SESSION['MILEAGE_COMPANY'] =
		$company_table->default_company();
	if( isset($_POST['MILEAGE_COMPANY']) ) $_SESSION['MILEAGE_COMPANY'] = $_POST['MILEAGE_COMPANY'];
}

if( $fleet_enabled ) {
	if( isset($_POST['MILEAGE_FLEET']) ) $_SESSION['MILEAGE_FLEET'] = $_POST['MILEAGE_FLEET'];
	if( ! isset($_SESSION['MILEAGE_FLEET']) ) $_SESSION['MILEAGE_FLEET'] = 0;
}

$filters_html = '<div class="form-group"><a class="btn btn-sm btn-success" href="exp_listmileage.php"><span class="glyphicon glyphicon-refresh"></span></a>';

$filters_html .= '<select class="form-control input-sm" name="MILEAGE_DURATION" id="MILEAGE_DURATION"   onchange="form.submit();">';
foreach( $valid_durations as $value => $label ) {
	$filters_html .= '<option value="'.$value.'" '.($_SESSION['MILEAGE_DURATION'] == $value ? 'selected' : '').'>'.$label.'</option>
	';
}
$filters_html .= '</select>';

$filters_html .= '<select class="form-control input-sm" name="MILEAGE_FILTER" id="MILEAGE_FILTER"   onchange="form.submit();">';
foreach( $valid_filters as $value => $label ) {
	$filters_html .= '<option value="'.$value.'" '.($_SESSION['MILEAGE_FILTER'] == $value ? 'selected' : '').'>'.$label.'</option>
	';
}
$filters_html .= '</select>';

if( $multi_company ) {
	$filters_html .= $company_table->menu( $_SESSION['MILEAGE_COMPANY'], 'MILEAGE_COMPANY', '', true, true );
}

if( $fleet_enabled ) {
	$fleet_table = sts_fleet::getInstance($exspeedite_db, $sts_debug);
	$filters_html .=
		$fleet_table->menu( $_SESSION['MILEAGE_FLEET'], 'MILEAGE_FLEET', '', true, true );
}
		
$filters_html .= '</div>';

$sts_result_mileage_view['filters_html'] = $filters_html;

$match = $match_durations[$_SESSION['MILEAGE_DURATION']];
if( $multi_company && $_SESSION['MILEAGE_COMPANY'] > 0 ) {
	$match .= ' AND LOAD_COMPANY(L.LOAD_CODE) = '.$_SESSION['MILEAGE_COMPANY'];
}

if( $fleet_enabled && $_SESSION['MILEAGE_FLEET'] > 0 ) {
	$match .= " AND (SELECT FLEET_CODE FROM EXP_TRACTOR WHERE TRACTOR_CODE = L.TRACTOR) = ".$_SESSION['MILEAGE_FLEET'];
}

$mileage_table = sts_mileage::getInstance($exspeedite_db, $sts_debug);
$rslt = new sts_result( $mileage_table, $match, $sts_debug );
echo $rslt->render( $sts_result_mileage_layout, $sts_result_mileage_view, false, false );

?>
</div>

	<script language="JavaScript" type="text/javascript"><!--
		
		$(document).ready( function () {
			//alert(($(window).height() - 150) + "px");
			<?php if( ! $sts_debug ) { ?>
			document.documentElement.style.overflow = 'hidden';  // firefox, chrome
			document.body.scroll = "no"; // ie only
			<?php } ?>

			dt = $('#EXP_LOAD').DataTable({
		        //"bLengthChange": false,
		        //"bFilter": true,
		        //stateSave: true,
		        "bSort": true,
		        "bInfo": true,
				//"bAutoWidth": false,
				//"bProcessing": true, 
				"sScrollX": "100%",
				"sScrollY": ($(window).height() - 300) + "px",
				//"sScrollXInner": "120%",
		        "lengthMenu": [<?php echo isset($sts_length_menu) ? $sts_length_menu : '25, 50, 100, 250'; ?>],
				"bPaginate": true,
				"bScrollCollapse": false,
				"bSortClasses": false,
				"processing": true,
				"serverSide": true,
				//"dom": "frtiS",
				"deferRender": true,
				"ajax": {
					"url": "exp_listmileageajax.php",
					"data": function( d ) {
						d.match = encodeURIComponent("<?php echo $match; ?>");
						d.filter = encodeURIComponent("<?php echo $_SESSION['MILEAGE_FILTER']; ?>");
					}

				},
				"columns": [
					//{ "searchable": false, "orderable": false },
					<?php
						foreach( $sts_result_mileage_layout as $key => $row ) {
							if( $row["format"] <> 'hidden')
								echo '{ "data": "'.$key.'", "searchable": '.
								(isset($row["searchable"]) && $row["searchable"] ? 'true' : 'false').', "orderable": false'.
								(isset($row["align"]) ? ',"className": "text-'.$row["align"].'"' : '').
									(isset($row["length"]) ? ', "width": "'.$row["length"].'px"' : '').
									(isset($row["format"]) && $row["format"] == 'hidden' ? ', "visible": false' : '').' },
						';
						}
					?>
				],
				//"dom": "Bfrtip",
				buttons: [
					'csv', 'print'
				],
				"rowCallback": function( row, data ) {
					//console.log(data);
					if( data.BILLED < data.SHIPMENTS ) {
						$('td:eq(12)', row).addClass("danger");	// Shipments not billed
					}
					if( data.REVENUE == 0 ) {
						$('td:eq(13)', row).addClass("danger"); // No revenue
					}
					if( data.TOTAL_COST == 0.00 ) {
						$('td:eq(16)', row).addClass("danger"); // No expense
					}
					if( data.MARGIN <= 0 ) {
						$('td:eq(18)', row).addClass("danger"); // No margin
					}
				},
				"infoCallback": function( settings, start, end, max, total, pre ) {
					var api = this.api();
					return pre + ' (' + api.ajax.json().timing + ' s)';
				}
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

