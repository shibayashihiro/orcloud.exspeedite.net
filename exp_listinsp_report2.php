<?php 

// $Id: exp_listinsp_report2.php 4350 2021-03-02 19:14:52Z duncan $
//! SCR# 617 - R&M Report

// Set flag that this is a parent file
define( '_STS_INCLUDE', 1 );

// Setup Session
require_once( "include/sts_session_setup.php" );

require_once( "include/sts_config.php" );
$sts_debug = isset($_GET['debug']) && in_group( EXT_GROUP_DEBUG );
require_once( "include/sts_session_class.php" );

$my_session = sts_session::getInstance( $exspeedite_db, $sts_debug );
$my_session->access_report( 'R&M Report' );	// Make sure we should be here

function get_match( $pd = 'all' ) {
	global $tip;

	$match = false;
	if( $_SESSION['RM_UNIT_TYPE'] <> 'All')
		$match = "R.UNIT_TYPE = '".$_SESSION['RM_UNIT_TYPE']."'";
	
	if( $_SESSION['RM_ISACTIVE'] <> 'All')
		$match = ($match <> false ? $match." AND " : "")."(CASE WHEN R.UNIT_TYPE = 'tractor' THEN
					(SELECT ISACTIVE FROM EXP_TRACTOR WHERE TRACTOR_CODE = R.UNIT)
					ELSE
					(SELECT ISACTIVE FROM EXP_TRAILER WHERE TRAILER_CODE = R.UNIT)
					END) = '".$_SESSION['RM_ISACTIVE']."'";
	
	if( $_SESSION['RM_UNIT_NUMBER'] <> 'All')
		$match = ($match <> false ? $match." AND " : "")."R.UNIT = ".$_SESSION['RM_UNIT_NUMBER'];
	
	
	$tip = 'No date filter set';	
	if( ! empty($_SESSION['RM_FROMDATE']) && ! empty($_SESSION['RM_TODATE']) &&
			strtotime($_SESSION['RM_TODATE']) >= strtotime($_SESSION['RM_FROMDATE']) ) {
		$match = ($match <> false ? $match." AND " : "")."R.REPORT_DATE BETWEEN '".date("Y-m-d", strtotime($_SESSION['RM_FROMDATE']))."' AND '".date("Y-m-d", strtotime($_SESSION['RM_TODATE']))."'";
		$tip = 'between '.date("m/d/Y", strtotime($_SESSION['RM_FROMDATE']))." and ".date("m/d/Y", strtotime($_SESSION['RM_TODATE']));
		$_SESSION['RM_DFILTER'] = 'success';
	} else {
		$_SESSION['RM_DFILTER'] = 'default';
	}

	//echo "<pre>";
	//var_dump($match);
	//echo "</pre>";

	return $match;
}



require_once( "include/sts_result_class.php" );
require_once( "include/sts_insp_report_class.php" );
require_once( "include/sts_rm_form_class.php" );
require_once( "include/sts_setting_class.php" );
require_once( "include/sts_csv_class.php" );

$setting_table = sts_setting::getInstance($exspeedite_db, $sts_debug);
$default_active = $setting_table->get("option", "DEFAULT_ACTIVE") == 'true';
$pivot_rm_enabled = $setting_table->get("option", "PIVOT_RM_REPORT") == 'true';

if( ! $pivot_rm_enabled ) {
	reload_page("index.php");
}

$ir = sts_insp_report_grid::getInstance($exspeedite_db, $sts_debug);
$rm_form_table = sts_rm_form::getInstance($exspeedite_db, $sts_debug);

if( isset($_GET) && isset($_GET["EXPORT"])) {
	$match = get_match();
	$csv = new sts_csv($ir, $match, $sts_debug);
	
	$csv->header( "Exspeedite_RM" );

	$csv->render( $ir->get_layout($match), $ir->get_toprow() );

	die;
}

$sts_subtitle = "R&M Report";
require_once( "include/header_inc.php" );
require_once( "include/navbar_inc.php" );

?>
<div class="container-full" role="main">
<?php

if( ! isset($_SESSION['RM_UNIT_TYPE']) ) $_SESSION['RM_UNIT_TYPE'] = 'All';
if( ! isset($_SESSION['RM_UNIT_NUMBER']) ) $_SESSION['RM_UNIT_NUMBER'] = 'All';
if( ! isset($_SESSION['RM_ISACTIVE']) ) $_SESSION['RM_ISACTIVE'] = $default_active ? 'Active' : 'All';
if( isset($_POST['RM_ISACTIVE']) ) $_SESSION['RM_ISACTIVE'] = $_POST['RM_ISACTIVE'];
if( ! isset($_SESSION['RM_FROMDATE']) ) $_SESSION['RM_FROMDATE'] = '';
if( ! isset($_SESSION['RM_TODATE']) ) $_SESSION['RM_TODATE'] = '';

if( isset($_POST["dofilter"])) {
	$_SESSION['RM_FROMDATE'] = empty($_POST['RM_FROMDATE']) ? '' :
		date("Y-m-d", strtotime($_POST['RM_FROMDATE']));
	$_SESSION['RM_TODATE'] = empty($_POST['RM_TODATE']) ? '' :
		date("Y-m-d", strtotime($_POST['RM_TODATE']));
} else if( isset($_POST["clearfilter"])) {
	$_SESSION['RM_FROMDATE'] = '';
	$_SESSION['RM_TODATE'] = '';
}

if( isset($_POST['RM_UNIT_TYPE']) ) {
	if( $_SESSION['RM_UNIT_TYPE'] <> $_POST['RM_UNIT_TYPE'])
		$_SESSION['RM_UNIT_NUMBER'] = 'All';
	else if( isset($_POST['RM_UNIT_NUMBER']) )
		$_SESSION['RM_UNIT_NUMBER'] = $_POST['RM_UNIT_NUMBER'];
	$_SESSION['RM_UNIT_TYPE'] = $_POST['RM_UNIT_TYPE'];
}

$match = get_match();
	//echo "<pre>";
	//var_dump($match);
	//echo "</pre>";

$filters_html = '<div class="btn-group"><a class="btn btn-sm btn-success tip" title="Refresh this screen" href="exp_listinsp_report2.php"><span class="glyphicon glyphicon-refresh"></span></a>';
$valid_sources = $rm_form_table->get_types();

$filters_html .= ' <a class="btn btn-sm btn-primary tip" title="Export to CSV" href="exp_listinsp_report2.php?EXPORT"><span class="glyphicon glyphicon-th-list"></span></a>';


if( is_array($valid_sources) ) {
	$filters_html .= ' <select class="form-control input-sm tip" title="Filter on All/tractor/trailer" name="RM_UNIT_TYPE" id="RM_UNIT_TYPE"   onchange="form.submit();">';
		$filters_html .= '<option value="All" '.($_SESSION['RM_UNIT_TYPE'] == 'All' ? 'selected' : '').'>All Types</option>
		';
	foreach( $valid_sources as $source ) {
		$filters_html .= '<option value="'.$source.'" '.($_SESSION['RM_UNIT_TYPE'] == $source ? 'selected' : '').'>'.$source.'</option>
		';
	}
	$filters_html .= '</select>';
}

$valid_actives = array(	'All' => 'All',
						'Active' => 'Active', 
						'Inactive' => 'Inactive',
						'OOS' => 'OOS' );

$filters_html .= ' <select class="form-control input-sm tip" title="Filter on All/Active/Inactive/OOS" name="RM_ISACTIVE" id="RM_ISACTIVE"   onchange="form.submit();">';
foreach( $valid_actives as $value => $label ) {
	$filters_html .= '<option value="'.$value.'" '.($_SESSION['RM_ISACTIVE'] == $value ? 'selected' : '').'>'.$label.'</option>
	';
}
$filters_html .= '</select>';

if( $_SESSION['RM_UNIT_TYPE'] <> 'All' ) {
	$valid_sources = $rm_form_table->get_units($_SESSION['RM_UNIT_TYPE']);
	
	if( is_array($valid_sources) ) {
		$filters_html .= ' <select class="form-control input-sm tip" title="Filter on specific '.$_SESSION['RM_UNIT_TYPE'].'" name="RM_UNIT_NUMBER" id="RM_UNIT_NUMBER"   onchange="form.submit();">';
			$filters_html .= '<option value="All" '.($_SESSION['RM_UNIT_NUMBER'] == 'All' ? 'selected' : '').'>All '.$_SESSION['RM_UNIT_TYPE'].'s</option>
			';
		foreach( $valid_sources as $unit => $unit_number ) {
			$filters_html .= '<option value="'.$unit.'" '.($_SESSION['RM_UNIT_NUMBER'] == $unit ? 'selected' : '').'>'.$unit_number.'</option>
			';
		}
		$filters_html .= '</select>';
	}	
}

$filters_html .= ' <button type="button" class="btn btn-'.$_SESSION['RM_DFILTER'].' btn-sm'.(empty($tip) ? '"' : ' tip" title="'.$tip.'"').' data-toggle="modal" data-target="#datefilter_modal"><span class="glyphicon glyphicon-calendar"></span></button>';

$filters_html .= '</div>';



$edit = $ir->get_edit($match);
$edit['filters_html'] = $filters_html;
$edit['cancel'] = 'index.php';
$edit['cancelbutton'] = 'Back';



$rslt = new sts_result( $ir, $match, $sts_debug );
echo $rslt->render( $ir->get_layout($match), $edit );

$date_format = $_SESSION['ios'] == 'true' ? "Y-m-d" : "m/d/Y";
if( ! empty($_SESSION['RM_FROMDATE']) ) {
	$fd = ' value="'.date($date_format, strtotime($_SESSION['RM_FROMDATE'])).'"';
} else {
	$fd = '';
}
if( ! empty($_SESSION['RM_TODATE']) ) {
	$td = ' value="'.date($date_format, strtotime($_SESSION['RM_TODATE'])).'"';
} else {
	$td = '';
}

?>
</div>
	<div class="modal fade fuzzy bs-example-modal-sm" tabindex="-1" role="dialog" aria-labelledby="mySmallModalLabel" aria-hidden="true" id="datefilter_modal">
	  <div class="modal-dialog">
		<div class="modal-content">
		<div class="modal-header">
			<button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
			<h4 class="modal-title" id="myModalLabel"><span class="text-success"><strong><span class="glyphicon glyphicon-calendar"></span> Filter On Report Date</strong></span></h4>
		</div>
		<div class="modal-body">
			<form role="form" class="form-horizontal" action="exp_listinsp_report2.php" method="post" enctype="multipart/form-data" name="datefilter" id="datefilter">
					<div class="form-group tighter">
						<div class="col-sm-6 well well-md tighter">
							<input type="<?php echo ($_SESSION['ios'] == 'true' ? 'date' : 'text');?>" class="form-control input-sm<?php echo ($_SESSION['ios'] != 'true' ? ' date' : '');?>" style="width: auto;" name="RM_FROMDATE" placeholder="From Date"<?php echo $fd; ?>>
						</div>
						<div class="col-sm-6 well well-md tighter">
							<input type="<?php echo ($_SESSION['ios'] == 'true' ? 'date' : 'text');?>" class="form-control input-sm<?php echo ($_SESSION['ios'] != 'true' ? ' date' : '');?>" style="width: auto;" name="RM_TODATE" placeholder="To Date"<?php echo $td; ?>>
						</div>
					</div>
					<div class="form-group tighter">
						<div class="col-sm-4 col-sm-offset-2 tighter">
							<button class="btn btn-md btn-success" name="dofilter" type="submit"><span class="glyphicon glyphicon-search"></span> Filter</button>
						</div>
						<div class="col-sm-4 col-sm-offset-2 tighter">
							<button class="btn btn-md btn-default" name="clearfilter" type="submit"><span class="glyphicon glyphicon-remove"></span> Clear Filter</button>
						</div>
					</div>
					<div class="form-group">
						<br>
						<p class="text-center">Needs two dates with (To Date >= From Date) to function.</p>
					</div>
			</form>
				
		</div>
		</div>
		</div>
	</div>

	<script language="JavaScript" type="text/javascript"><!--
		
		$(document).ready( function () {
			//alert(($(window).height() - 150) + "px");
			<?php if( ! $sts_debug ) { ?>
			//document.documentElement.style.overflow = 'hidden';  // firefox, chrome
			//document.body.scroll = "no"; // ie only
			<?php } ?>

			
			var table = $('#EXP_INSP_REPORT').DataTable({
		        //"bLengthChange": false,
		        "bFilter": true,
		        "bSort": true,
		        "bInfo": true,
		        stateSave: true,
				"bAutoWidth": false,
				//"bProcessing": true, 
				"sScrollX": "100%",
				"sScrollY": ($(window).height() - 360) + "px",
				"sScrollXInner": "260%",
		        "lengthMenu": [[-1, 25, 50], ["All", 25, 50]],
				"bPaginate": true,
				//"bScrollCollapse": true,
				"bSortClasses": false,
				
				"rowCallback": function( row, data ) {
					// Some green vertical bars
					<?php echo $ir->get_bars( $match ); ?>
				},
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

