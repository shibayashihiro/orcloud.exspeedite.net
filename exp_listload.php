<?php 

// $Id: exp_listload.php 5030 2023-04-12 20:31:34Z duncan $
// List loads - client view.

// Set flag that this is a parent file
define( '_STS_INCLUDE', 1 );

// Setup Session
require_once( "include/sts_session_setup.php" );

// We want to use TableTools with DataTables
define( '_STS_TABLETOOLS', 1 );

require_once( "include/sts_config.php" );
$sts_debug = isset($_GET['debug']) && in_group( EXT_GROUP_DEBUG );
require_once( "include/sts_session_class.php" );

$my_session = sts_session::getInstance( $exspeedite_db, $sts_debug );
$my_session->access_check( $sts_table_access[LOAD_TABLE] );	// Make sure we should be here

require_once( "include/sts_setting_class.php" );
$setting_table = sts_setting::getInstance($exspeedite_db, $sts_debug);
//! SCR# 298 Set flag to include JQuery Location Picker
define( '_STS_LOCATION', 1 );
$sts_google_api_key = $setting_table->get( 'api', 'GOOGLE_API_KEY' );

$sts_subtitle = "Loads";
require_once( "include/header_inc.php" );
require_once( "include/navbar_inc.php" );

?>
<div class="container-hundred" role="main">

<!--
<div class="alert alert-warning alert-dismissable">
  <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
  <strong>Warning!</strong> Working on changes to multiple picks/drops same address. Some issues remain.
</div>
-->

<?php
require_once( "include/sts_result_class.php" );
require_once( "include/sts_load_class.php" );
require_once( "include/sts_office_class.php" );

$office_table = sts_office::getInstance($exspeedite_db, $sts_debug);

$sts_length_menu = $setting_table->get( 'option', 'LENGTH_MENU' );
$sts_refresh_rate = $setting_table->get( 'option', 'LOAD_SCREEN_REFRESH_RATE' );
$sts_po_fields = $setting_table->get( 'option', 'PO_FIELDS' ) == 'true';
$sts_refnum_fields = $setting_table->get( 'option', 'REFNUM_FIELDS' ) == 'true';
$multi_company = $office_table->multi_company();
$sts_export_qb = $setting_table->get( 'api', 'EXPORT_QUICKBOOKS' ) == 'true';
$sts_export_sage50 = $setting_table->get( 'api', 'EXPORT_SAGE50_CSV' ) == 'true';
//! SCR# 852 - Containers Feature
$sts_containers = $setting_table->get( 'option', 'CONTAINERS' ) == 'true';

$sts_colour_loads = $setting_table->get( 'option', 'COLOUR_LOAD_TIMES' ) == 'true';

$load_table = new sts_load_left_join($exspeedite_db, $sts_debug);

$check = $exspeedite_db->get_one_row("
	SELECT COUNT(*) AS LUMPERS
	FROM EXP_CARRIER
	WHERE CARRIER_TYPE IN ('lumper', 'shag')");
$has_lumpers = is_array($check) &&
	isset($check["LUMPERS"]) &&
	intval($check["LUMPERS"]) > 0;

//! Performance fix. Set a completed date for cancelled loads.	
$complete = $exspeedite_db->get_one_row("
	UPDATE EXP_LOAD
	SET COMPLETED_DATE = CHANGED_DATE
	WHERE CURRENT_STATUS = 13 AND COMPLETED_DATE IS NULL");

$codes = array($load_table->behavior_state['cancel'],
			$load_table->behavior_state['complete'],
			$load_table->behavior_state['oapproved'],
			$load_table->behavior_state['approved'],
			$load_table->behavior_state['billed'] );

$entry_code = $load_table->behavior_state['entry'];
$cancel_code = $load_table->behavior_state['cancel'];
$complete_code = $load_table->behavior_state['complete'];
$manifest_code = $load_table->behavior_state['manifest'];
$oapproved_code = $load_table->behavior_state['oapproved'];
$approved_code = $load_table->behavior_state['approved'];
$paid_code = $load_table->behavior_state['billed'];

if( ! isset($_SESSION['LOAD_MODE']) ) $_SESSION['LOAD_MODE'] = 'current';
if( isset($_POST['LOAD_MODE']) ) $_SESSION['LOAD_MODE'] = $_POST['LOAD_MODE'];

if( $has_lumpers ) {	
	if( ! isset($_SESSION['LOAD_LUMPER']) ) $_SESSION['LOAD_LUMPER'] = 0;
	if( isset($_POST['LOAD_LUMPER']) ) $_SESSION['LOAD_LUMPER'] = $_POST['LOAD_LUMPER'];		
	if( $_SESSION['LOAD_MODE'] != 'lumper' ) $_SESSION['LOAD_LUMPER'] = 0;
}
	
if( $multi_company ) {
	if( ! isset($_SESSION['LOAD_OFFICE']) ) $_SESSION['LOAD_OFFICE'] = 'all';
	if( isset($_POST['LOAD_OFFICE']) ) $_SESSION['LOAD_OFFICE'] = $_POST['LOAD_OFFICE'];
}
	
if( ! isset($_SESSION['LOAD_START']) ) $_SESSION['LOAD_START'] = date("m/d/Y");
if( isset($_POST['LOAD_START']) ) $_SESSION['LOAD_START'] = $_POST['LOAD_START'];
	
if( ! isset($_SESSION['LOAD_DURATION']) ) $_SESSION['LOAD_DURATION'] = '5';
if( isset($_POST['LOAD_DURATION']) ) $_SESSION['LOAD_DURATION'] = $_POST['LOAD_DURATION'];

if( ! isset($_SESSION['LOAD_ACTIVE']) ) $_SESSION['LOAD_ACTIVE'] = 'All';
if( isset($_POST['LOAD_ACTIVE']) ) $_SESSION['LOAD_ACTIVE'] = $_POST['LOAD_ACTIVE'];

//! SCR# 350 - Add date filters
if( ! isset($_SESSION['LPICKDROP']) ) $_SESSION['LPICKDROP'] = 'pick';
if( ! isset($_SESSION['LDUEACT']) ) $_SESSION['LDUEACT'] = 'due';
if( ! isset($_SESSION['LFROMDATE']) ) $_SESSION['LFROMDATE'] = '';
if( ! isset($_SESSION['LTODATE']) ) $_SESSION['LTODATE'] = '';
if( ! isset($_SESSION['LDFILTER']) ) $_SESSION['LDFILTER'] = 'default';

if( isset($_POST["dofilter"])) {
	if( isset($_POST['LPICKDROP']) ) $_SESSION['LPICKDROP'] = $_POST['LPICKDROP'];
	if( isset($_POST['LDUEACT']) ) $_SESSION['LDUEACT'] = $_POST['LDUEACT'];
	$_SESSION['LFROMDATE'] = empty($_POST['LFROMDATE']) ? '' :
		date("Y-m-d", strtotime($_POST['LFROMDATE']));
	$_SESSION['LTODATE'] = empty($_POST['LTODATE']) ? '' :
		date("Y-m-d", strtotime($_POST['LTODATE']));
} else if( isset($_POST["clearfilter"])) {
	$_SESSION['LPICKDROP'] = 'pick';
	$_SESSION['LDUEACT'] = 'due';
	$_SESSION['LFROMDATE'] = '';
	$_SESSION['LTODATE'] = '';
}

//! Initialize $match
$match = '';
$tip = 'No date filter set';

if( ! empty($_SESSION['LFROMDATE']) && ! empty($_SESSION['LTODATE']) &&
	strtotime($_SESSION['LTODATE']) >= strtotime($_SESSION['LFROMDATE']) ) {
	$_SESSION['LDFILTER'] = 'success';
	$range = "BETWEEN '".date("Y-m-d", strtotime($_SESSION['LFROMDATE']))."' AND '".date("Y-m-d", strtotime($_SESSION['LTODATE']))."'";
	$range2 = 'between '.date("m/d/Y", strtotime($_SESSION['LFROMDATE']))." and ".date("m/d/Y", strtotime($_SESSION['LTODATE']));
	if( $_SESSION['LPICKDROP'] == 'pick') {
		if( $_SESSION['LDUEACT'] == 'due' ) {
			$match = ($match <> '' ? $match." AND " : "")."LOAD_DATE(LOAD_CODE, 'pick', 'due') ".$range;
			$tip = 'Pickup due '.$range2;
		} else {
			$match = ($match <> '' ? $match." AND " : "")."LOAD_DATE(LOAD_CODE, 'pick', 'actual') ".$range;
			$tip = 'Actual Pickup '.$range2;
		}
	} else {
		if( $_SESSION['LDUEACT'] == 'due' ) {
			$match = ($match <> '' ? $match." AND " : "")."LOAD_DATE(LOAD_CODE, 'drop', 'due') ".$range;
			$tip = 'Delivery due '.$range2;
		} else {
			$match = ($match <> '' ? $match." AND " : "")."LOAD_DATE(LOAD_CODE, 'drop', 'actual') ".$range;
			$tip = 'Actual Delivery '.$range2;
		}
	}
	
} else {
	$_SESSION['LDFILTER'] = 'default';
}

// close the session here to avoid blocking
session_write_close();

//echo '<p>Filter '.$_SESSION['LPICKDROP'].' '.$_SESSION['LDUEACT'].' '.$_SESSION['LFROMDATE'].' - '.$_SESSION['LTODATE'];
	
$valid_actives = array(	'Active' => 'Active Stops',
						'Current' => 'Current Stop', 
						'All' => 'All Stops' );

$valid_modes = array(	'all' => 'All Loads',
						'current' => 'Current Loads (By next drop)', 
						'current3' => 'Current Loads (By next pick or drop)', 
						'current2' => 'Current Loads (By Trip/Load#)',
						//! SCR# 852 - Falcon stuff
						'disp' => 'Dispatched Loads (By next drop)', 
						'disp3' => 'Dispatched Loads (By next pick or drop)', 
						'disp2' => 'Dispatched Loads (By Trip/Load#)',
						
						'manifest' => 'Carrier Freight Agreement Sent',
						'complete' => 'Complete Loads - company',
						'complete2' => 'Complete Loads - carriers',
						'complete3' => 'Complete Loads - carriers - zero $',
						'oapproved' => 'Approved (Office) Loads',
						'approved' => 'Approved (Finance) Loads',
						'paid' => 'Paid Loads');

if( $has_lumpers ) {
	$valid_modes['lumper'] = 'Separately Paid Lumper';
}

//! SCR# 403 - remove Approved (Office) if not multi-company
if( ! $multi_company )
	unset( $valid_modes['oapproved']);

if( $_SESSION['LOAD_MODE'] == 'current' ) {
	$match = ($match <> '' ? $match." AND " : "").'COMPLETED_DATE IS NULL';
	$sts_result_loads_edit['sort'] = 'SORT_DROP_DUE(LOAD_CODE) ASC, LOAD_CODE ASC, SEQUENCE_NO ASC';
} else if( $_SESSION['LOAD_MODE'] == 'current2' ) {
	$match = ($match <> '' ? $match." AND " : "").'COMPLETED_DATE IS NULL';
	$sts_result_loads_edit['sort'] = 'LOAD_CODE DESC, SEQUENCE_NO ASC';
} else if( $_SESSION['LOAD_MODE'] == 'current3' ) {
	$match = ($match <> '' ? $match." AND " : "").'COMPLETED_DATE IS NULL';
	$sts_result_loads_edit['sort'] = 'SORT_PICKDROP_DUE(LOAD_CODE) ASC, LOAD_CODE ASC, SEQUENCE_NO ASC';

//! SCR# 852 - Falcon stuff
} else if( $_SESSION['LOAD_MODE'] == 'disp' ) {
	$match = ($match <> '' ? $match." AND " : "").'CURRENT_STATUS != '.$entry_code.
		' AND COMPLETED_DATE IS NULL';
	$sts_result_loads_edit['sort'] = 'SORT_DROP_DUE(LOAD_CODE) ASC, LOAD_CODE ASC, SEQUENCE_NO ASC';
} else if( $_SESSION['LOAD_MODE'] == 'disp2' ) {
	$match = ($match <> '' ? $match." AND " : "").'CURRENT_STATUS != '.$entry_code.
		' AND COMPLETED_DATE IS NULL';
	$sts_result_loads_edit['sort'] = 'LOAD_CODE DESC, SEQUENCE_NO ASC';
} else if( $_SESSION['LOAD_MODE'] == 'disp3' ) {
	$match = ($match <> '' ? $match." AND " : "").'CURRENT_STATUS != '.$entry_code.
		' AND COMPLETED_DATE IS NULL';
	$sts_result_loads_edit['sort'] = 'SORT_PICKDROP_DUE(LOAD_CODE) ASC, LOAD_CODE ASC, SEQUENCE_NO ASC';

} else if( $_SESSION['LOAD_MODE'] == 'all' ) {
	//$match = ($match <> '' ? $match." AND " : "").'1 = 1';
} else if( $_SESSION['LOAD_MODE'] == 'complete' ) {
	$match = ($match <> '' ? $match." AND " : "").'EXP_LOAD.CURRENT_STATUS = '.$complete_code.' AND COALESCE(CARRIER,0) = 0';
	$sts_result_loads_edit['sort'] = 'LOAD_CODE DESC, SEQUENCE_NO ASC';
} else if( $_SESSION['LOAD_MODE'] == 'complete2' ) {
	$match = ($match <> '' ? $match." AND " : "").'EXP_LOAD.CURRENT_STATUS = '.$complete_code.' AND COALESCE(CARRIER,0) > 0';
	$sts_result_loads_edit['sort'] = 'LOAD_CODE DESC, SEQUENCE_NO ASC';
} else if( $_SESSION['LOAD_MODE'] == 'complete3' ) {
	$match = ($match <> '' ? $match." AND " : "").'EXP_LOAD.CURRENT_STATUS = '.$complete_code.' AND COALESCE(CARRIER,0) > 0 AND COALESCE(CARRIER_TOTAL,0) = 0';
	$sts_result_loads_edit['sort'] = 'LOAD_CODE DESC, SEQUENCE_NO ASC';
} else if( $_SESSION['LOAD_MODE'] == 'manifest' )
	$match = ($match <> '' ? $match." AND " : "").'EXP_LOAD.CURRENT_STATUS = '.$manifest_code;
else if( $_SESSION['LOAD_MODE'] == 'oapproved' )
	$match = ($match <> '' ? $match." AND " : "").'EXP_LOAD.CURRENT_STATUS = '.$oapproved_code;
else if( $_SESSION['LOAD_MODE'] == 'approved' )
	$match = ($match <> '' ? $match." AND " : "").'EXP_LOAD.CURRENT_STATUS = '.$approved_code;
else if( $_SESSION['LOAD_MODE'] == 'paid' )
	$match = ($match <> '' ? $match." AND " : "").'EXP_LOAD.CURRENT_STATUS = '.$paid_code;
else if( $has_lumpers && $_SESSION['LOAD_MODE'] == 'lumper' )
	$match = ($match <> '' ? $match." AND " : "").'EXP_LOAD.LUMPER > 0';

if( $multi_company ) {
	if( $_SESSION['LOAD_OFFICE'] == 'all' )
		$match = ($match <> '' ? $match." AND " : "")."OFFICE_CODE IN (".implode(', ', array_keys($_SESSION['EXT_USER_OFFICES'])).")";
	else
		$match = ($match <> '' ? $match." AND " : "")."OFFICE_CODE = ".$_SESSION['LOAD_OFFICE'];
} else {
	if( $_SESSION['LOAD_MODE'] == 'all' )
		$match = '1 = 1';
}

$filters_html = '<div class="form-group"><a class="btn btn-sm btn-primary" href="exp_listsummary.php"><img src="images/load_icon.png" alt="load_icon" height="18"> Summary</a>
<a class="btn btn-sm btn-success" href="exp_listload.php"><span class="glyphicon glyphicon-refresh"></span></a>';

$filters_html .= '<select class="form-control input-sm" name="LOAD_ACTIVE" id="LOAD_ACTIVE"   onchange="form.submit();">';
foreach( $valid_actives as $value => $label ) {
	$filters_html .= '<option value="'.$value.'" '.($_SESSION['LOAD_ACTIVE'] == $value ? 'selected' : '').'>'.$label.'</option>
	';
}
$filters_html .= '</select>';

$filters_html .= '<select class="form-control input-sm" name="LOAD_MODE" id="LOAD_MODE"   onchange="$(\'#EXP_LOAD\').DataTable().state.clear(); form.submit();">';
foreach( $valid_modes as $value => $label ) {
	$filters_html .= '<option value="'.$value.'" '.($_SESSION['LOAD_MODE'] == $value ? 'selected' : '').'>'.$label.'</option>
	';
}
$filters_html .= '</select>';

if( $has_lumpers && $_SESSION['LOAD_MODE'] == 'lumper' ) {

	$check1 = $exspeedite_db->get_multiple_rows("
		SELECT CARRIER_CODE, CARRIER_NAME
		FROM EXP_CARRIER
		WHERE CARRIER_TYPE IN ('lumper', 'shag')");
	
	$filters_html .= '<select class="form-control input-sm" name="LOAD_LUMPER" id="LOAD_LUMPER"   onchange="$(\'#EXP_LOAD\').DataTable().state.clear(); form.submit();">
	<option value="0" '.($_SESSION['LOAD_LUMPER'] == 0 ? 'selected' : '').'>All Lumpers</option>
	';
	foreach( $check1 as $row ) {
		$filters_html .= '<option value="'.$row["CARRIER_CODE"].'" '.($_SESSION['LOAD_LUMPER'] == $row["CARRIER_CODE"] ? 'selected' : '').'>'.$row["CARRIER_NAME"].'</option>
		';
	}
	$filters_html .= '</select>';
	
	if( $_SESSION['LOAD_LUMPER'] > 0 ) {
		$match = ($match <> '' ? $match." AND " : "")."LUMPER = ".$_SESSION['LOAD_LUMPER'];
	}

} else {
	unset($sts_result_loads_lj_layout['LUMPER']);
	unset($sts_result_loads_lj_layout['LUMPER_NAME']);
	unset($sts_result_loads_lj_layout['LUMPER_AMT']);
}


if( $multi_company && count($_SESSION['EXT_USER_OFFICES']) > 1 ) {
	$filters_html .= '<select class="form-control input-sm" name="LOAD_OFFICE" id="LOAD_OFFICE"   onchange="form.submit();">';
	$filters_html .= '<option value="all" '.($_SESSION['LOAD_OFFICE'] == 'all' ? 'selected' : '').'>All Offices</option>
		';
	foreach( $_SESSION['EXT_USER_OFFICES'] as $value => $label ) {
		$filters_html .= '<option value="'.$value.'" '.($_SESSION['LOAD_OFFICE'] == $value ? 'selected' : '').'>'.$label.'</option>
		';
	}
	$filters_html .= '</select>';
}

$filters_html .= '<button type="button" class="btn btn-'.$_SESSION['LDFILTER'].' btn-sm'.(empty($tip) ? '"' : ' tip" title="'.$tip.'"').' data-toggle="modal" data-target="#ldatefilter_modal"><span class="glyphicon glyphicon-calendar"></span></button>';


if( $sts_export_qb && $my_session->in_group(EXT_GROUP_FINANCE) &&
	isset($_SESSION['LOAD_MODE']) &&
	$_SESSION['LOAD_MODE'] == 'approved' ) {
	$filters_html .= '<a class="btn btn-sm btn-danger" onclick="confirmation(\'Confirm: Resend ALL Approved Loads to QuickBooks?\n\nThis is helpful when QuickBooks was unavailable, and you want to clear the backlog.\n\nYou can examine the status by looking at each load.\', \'exp_qb_retry.php?TYPE=load&CODE=ALL\')"><span class="glyphicon glyphicon-ok"></span> Resend ALL to QB</a>';
}

if( $sts_export_sage50 && $my_session->in_group(EXT_GROUP_SAGE50) &&
	isset($_SESSION['LOAD_MODE']) &&
	$_SESSION['LOAD_MODE'] == 'approved' ) {
	$filters_html .= ' <a class="btn btn-sm btn-danger" onclick="confirmation(\'Confirm: Send ALL Approved Bills to Sage 50?<br>This creates a CSV file for import to Sage 50.<br>In Sage 50, select <strong>File -> Select Import/Export...</strong><br>Click on <strong>Accounts Payable</strong> and then <strong>Import</strong><br>Make sure all the columns are in the correct order in Sage 50 during import.<br>The approved bills will be marked as paid.\', \'exp_export_csv.php?pw=GoldUltimate&type=bill\')"><span class="glyphicon glyphicon-list-alt"></span> <span class="glyphicon glyphicon-arrow-right"></span> Sage 50</a>';
}

$filters_html .= '</div>';

$sts_result_loads_edit['filters_html'] = $filters_html;

if( ! $sts_po_fields ) {
	unset($sts_result_loads_lj_layout['PO_NUMBER']);
}
if( ! $sts_refnum_fields ) {
	unset($sts_result_loads_lj_layout['REF_NUMBER']);
}
if( ! $multi_company ) {
	unset($sts_result_loads_lj_layout['SS_NUMBER2']);
}

//! SCR# 852 - Containers Feature
if( ! $sts_containers ) {
	unset($sts_result_loads_lj_layout['ST_NUMBER']);
}

/*
	$test = $load_table->database->get_one_row("SELECT @@GLOBAL.time_zone, @@SESSION.time_zone;");
	echo "<pre>TIMEZONE\n";
	var_dump($test);
	echo "</pre>";
*/

$rslt = new sts_result( $load_table, $match, $sts_debug );
echo $rslt->render( $sts_result_loads_lj_layout, $sts_result_loads_edit, false, false );

//! SCR# 298 - Check Call functionality
require_once("exp_check_call.php");

//! SCR# 350 - Add date filters
if( $_SESSION['LPICKDROP'] == 'pick' ) {
	$pd1 = ' checked';
	$pd2 = '';
} else {
	$pd1 = '';
	$pd2 = ' checked';
}
if( $_SESSION['LDUEACT'] == 'due' ) {
	$da1 = ' checked';
	$da2 = '';
} else {
	$da1 = '';
	$da2 = ' checked';
}

$date_format = $_SESSION['ios'] == 'true' ? "Y-m-d" : "m/d/Y";
if( ! empty($_SESSION['LFROMDATE']) ) {
	$fd = ' value="'.date($date_format, strtotime($_SESSION['LFROMDATE'])).'"';
} else {
	$fd = '';
}
if( ! empty($_SESSION['LTODATE']) ) {
	$td = ' value="'.date($date_format, strtotime($_SESSION['LTODATE'])).'"';
} else {
	$td = '';
}

?>
</div>

	<div class="modal fade fuzzy bs-example-modal-sm" tabindex="-1" role="dialog" aria-labelledby="mySmallModalLabel" aria-hidden="true" id="ldatefilter_modal">
	  <div class="modal-dialog">
		<div class="modal-content">
		<div class="modal-header">
			<button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
			<h4 class="modal-title" id="myModalLabel"><span class="text-success"><strong><span class="glyphicon glyphicon-calendar"></span> Date Filter</strong></span></h4>
		</div>
		<div class="modal-body">
			<form role="form" class="form-horizontal" action="exp_listload.php" method="post" enctype="multipart/form-data" name="datefilter" id="datefilter">
					<div class="form-group tighter">
						<div class="col-sm-6 well well-md tighter">
							<div class="form-group tighter">
								<div class="col-sm-6 tighter">
									<label><input name="LPICKDROP" id="LPICKDROP1" type="radio" value="pick"<?php echo $pd1; ?>> Pickup</label>
								</div>
								<div class="col-sm-6 tighter">
									<label><input name="LPICKDROP" id="LPICKDROP2"  type="radio" value="drop"<?php echo $pd2; ?>> Delivery</label>
								</div>
							</div>
						</div>
						<div class="col-sm-6 well well-md tighter">
							<div class="form-group tighter">
								<div class="col-sm-6">
									<label><input name="LDUEACT" id="LDUEACT1" type="radio" value="due"<?php echo $da1; ?>> Due</label>
								</div>
								<div class="col-sm-6 tighter">
									<label><input name="LDUEACT" id="LDUEACT2"  type="radio" value="actual"<?php echo $da2; ?>> Actual</label>
								</div>
							</div>
						</div>
					</div>
					<div class="form-group tighter">
						<div class="col-sm-6 well well-md tighter">
							<input type="<?php echo ($_SESSION['ios'] == 'true' ? 'date' : 'text');?>" class="form-control input-sm<?php echo ($_SESSION['ios'] != 'true' ? ' date' : '');?>" style="width: auto;" name="LFROMDATE" placeholder="From Date"<?php echo $fd; ?>>
						</div>
						<div class="col-sm-6 well well-md tighter">
							<input type="<?php echo ($_SESSION['ios'] == 'true' ? 'date' : 'text');?>" class="form-control input-sm<?php echo ($_SESSION['ios'] != 'true' ? ' date' : '');?>" style="width: auto;" name="LTODATE" placeholder="To Date"<?php echo $td; ?>>
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

	<div class="modal fade bs-example-modal-sm" tabindex="-1" role="dialog" aria-labelledby="mySmallModalLabel" aria-hidden="true" id="listload_modal">
	  <div class="modal-dialog modal-lg">
		<div class="modal-content">
		<div class="modal-header">
			<h4 class="modal-title" id="myModalLabel"><span class="text-success"><strong>Updating...</strong></span></h4>
		</div>
		<div class="modal-body">
			<p class="text-center"><img src="images/loading.gif" alt="loading" width="125" height="125" /></p>
		</div>
		</div>
		</div>
	</div>

	<script language="JavaScript" type="text/javascript"><!--
		
		$(document).ready( function () {
			//alert(($(window).height() - 150) + "px");
			document.documentElement.style.overflow = 'hidden';  // firefox, chrome
			document.body.scroll = "no"; // ie only
			
			var opts = {
		        //"bLengthChange": false,
		        "bFilter": true,
		        stateSave: true,
		        "bSort": false,
		        "bInfo": true,
				//"bAutoWidth": false,
				//"bProcessing": true, 
				"sScrollX": "100%",
				"sScrollY": ($(window).height() - 260) + "px",
				//"sScrollXInner": "120%",
		        "lengthMenu": [<?php echo isset($sts_length_menu) ? $sts_length_menu : '25, 50, 100, 250'; ?>],
		        <?php if( isset($_POST["EXP_LOAD_length"]) )
			        echo '"pageLength": '.$_POST["EXP_LOAD_length"].','; ?>
				"bPaginate": true,
				"bScrollCollapse": false,
				"bSortClasses": false,
				"processing": true,
				"serverSide": true,
				//"dom": "frtiS",
				"deferRender": true,
				"ajax": {
					"url": "exp_listloadajax.php",
					timeout: 60000,
					"data": function( d ) {
						d.match = encodeURIComponent("<?php echo $match; ?>");
						d.sort = encodeURIComponent("<?php echo $sts_result_loads_edit['sort']; ?>");
					}

				},
				"columns": [
					//{ "searchable": false, "orderable": false },
					<?php
						foreach( $sts_result_loads_lj_layout as $key => $row ) {
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
				"rowCallback": function( row, data ) {
					var load_status = $(row).attr('load_status');
					if( typeof(load_status) !== 'undefined' && $(row).find("td:first").html() != '' ) {
						switch(parseInt(load_status)) {
							case <?php echo $load_table->behavior_state['arrive shipper']; ?>:
							case <?php echo $load_table->behavior_state['depart shipper']; ?>:
							case <?php echo $load_table->behavior_state['arrshdock']; ?>:
							case <?php echo $load_table->behavior_state['depshdock']; ?>:
								$(row).find("td:first").addClass("inprogress");
								break;

							case <?php echo $load_table->behavior_state['arrive cons']; ?>:
							case <?php echo $load_table->behavior_state['depart cons']; ?>:
							case <?php echo $load_table->behavior_state['arrrecdock']; ?>:
							case <?php echo $load_table->behavior_state['deprecdock']; ?>:
								$(row).find("td:first").addClass("inprogress2");
								break;

							case <?php echo $load_table->behavior_state['complete']; ?>:
								$(row).find("td:first").addClass("success2");
								break;

							case <?php echo $load_table->behavior_state['dispatch']; ?>:
								$(row).find("td:first").addClass("dispatched");
								break;
							
							case <?php echo $load_table->behavior_state['imported']; ?>:
							case <?php echo $load_table->behavior_state['accepted']; ?>:
								$(row).find("td:first").addClass("imported");
								break;
							
							default:
								break;
						}
					}
					var stop_status = $(row).attr('current_stop_status');
					if( typeof(load_status) !== 'undefined' ) {
						if( stop_status == 'complete' ) {
							$(row).find("td:nth-child(3)").addClass("success2");
						}
					}
					
					<?php if( $sts_colour_loads ) { ?>
					var stop_color = $(row).attr('stop_color');
					if( typeof(stop_color) !== 'undefined' ) {
						switch(stop_color) {
							case 'purple':
								$(row).find("td:nth-child(12)").addClass("purple");
								break;
							
							case 'pink':
								$(row).find("td:nth-child(12)").addClass("pink");
								break;
							
							case 'red':
								$(row).find("td:nth-child(12)").addClass("red");
								break;
							
							default:
								break;
						}
					}
					<?php } ?>
					
				},
				"infoCallback": function( settings, start, end, max, total, pre ) {
					var api = this.api();
					return pre + ' (' + api.ajax.json().timing + ' s)';
				},
				/*
				dom: 'T<"clear">lfrtip',
				"tableTools": {
		            "sSwfPath": "TableTools/swf/copy_csv_xls.swf",
		            "aButtons": [
		                {
		                    "sExtends":    "csv",
		                    "fnCellRender": function ( sValue, iColumn, nTr, iDataIndex ) {
		                        var output = sValue;
		                        if( output == "&nbsp;" )
		                        	output = "";
								
		                        if ( iColumn == 0 ) {
			                        var re = /View load (\d+)\</;
			                        var found = output.match(re);
			                        if(typeof(found) != "undefined" && found !== null) {
				                        output = found[1];
				                    } else {
					                    output = "";   
				                    }
		                        } else if ( iColumn == 1 ) {
			                        var str = '';
			                        var re = /\>(\d+)\</;
			                        var found = output.match(re);
			                        if(typeof(found) != "undefined" && found !== null) {
				                        str = found[1];
				                    }
			                        var re2 = /\>(Ps|Dc|Pd|Dd|S)\</;
			                        var found2 = output.match(re2);
			                        if(typeof(found2) != "undefined" && found2 !== null) {
				                        str = found2[1]+" "+str;
				                    }
				                    output = str;
		                        } else if ( iColumn == 2 ) {
			                        var re = /\>(.*)\</;
			                        var found = output.match(re);
			                        if(typeof(found) != "undefined" && found !== null) {
				                        output = found[1];
				                    }
		                        } else if ( iColumn == 3 ) {
			                        var re = /\>(\d+)\</;
			                        var found = output.match(re);
			                        if(typeof(found) != "undefined" && found !== null) {
				                        output = found[1];
				                    } else {
					                    output = "";   
				                    }
		                        } else if ( iColumn == 9 || iColumn == 10 ) {
			                        var re = /(.*)\<a href.*\<\/a\>/;
			                        var found = output.match(re);
			                        if(typeof(found) != "undefined" && found !== null) {
				                        output = found[1];
				                    } else {
					                    output = "";   
				                    }
		                        }
		                        
		                        if ( iColumn >= 4 ) {
			                        var re = /\<strong\>(.*)\<\/strong\>/;
			                        var found = output.match(re);
			                        if(typeof(found) != "undefined" && found !== null) {
				                        output = found[1];
				                    }
			                        var re2 = /\<span.*\>(.*)\<\/span\>/;
			                        var found2 = output.match(re2);
			                        if(typeof(found2) != "undefined" && found2 !== null) {
				                        output = found2[1];
				                    }
		                        }
			                    
			                    if ( iColumn == 12 ) {
			                        var re = /(\d+) \/ (\d+)/;
			                        var found = output.match(re);
			                        if(typeof(found) != "undefined" && found !== null) {
				                        output = found[1] + "h / " + found[2] + "b";
				                    }
		                        }
			                    output = output.replace("<br>", "\r");
			                    return output;
		                    }
		                }		            
		            ]
		        }
		        */
		
			};

			var myTable = $('#EXP_LOAD').DataTable(opts);
			
			// This takes us back to page 0, if we are past the number of pages available.
			// This can happen when you change a filter.
			var info = myTable.page.info();
			if( info.page > info.pages ) {
				myTable.page(0).draw( 'page' );
			}
			
			var refresh_rate = <?php echo intval($sts_refresh_rate); ?>;
			var refresh_interval;
			
			if( refresh_rate > 0 ) {
				//alert('rate = '+(refresh_rate * 1000));
				refresh_interval = setInterval( function () {
				    myTable.ajax.reload( null, false ); // user paging is not reset on reload
				}, (refresh_rate * 1000) );
				
				myTable.on( 'draw.dt', function () {
					clearInterval(refresh_interval);
					refresh_interval = setInterval( function () {
					    myTable.ajax.reload( null, false ); // user paging is not reset on reload
					}, (refresh_rate * 1000) );
				});
			}

			$('#EXP_LOAD').on( 'draw.dt', function () {
				$('ul.dropdown-menu li a#modal').on('click', function(event) {
					event.preventDefault();
					$('#listload_modal').modal({
						container: 'body',
						remote: $(this).attr("href")
					}).modal('show');
					return false;					
				});

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

