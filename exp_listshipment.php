<?php 

// $Id: exp_listshipment.php 5084 2023-04-17 17:16:25Z duncan $
// List shipments screen - client side

// Set flag that this is a parent file
define( '_STS_INCLUDE', 1 );

// Setup Session
require_once( "include/sts_session_setup.php" );

// Include the Scroller extension for datatables
//define( '_STS_SCROLLER', 1 );

require_once( "include/sts_config.php" );
$sts_debug = isset($_GET['debug']) && in_group( EXT_GROUP_DEBUG );
require_once( "include/sts_session_class.php" );

$my_session = sts_session::getInstance( $exspeedite_db, $sts_debug );
$my_session->access_check( $sts_table_access[SHIPMENT_TABLE] );	// Make sure we should be here

$sts_subtitle = "List Shipments";
require_once( "include/header_inc.php" );
require_once( "include/navbar_inc.php" );

?>
<div class="container-hundred" role="main">
<?php
require_once( "include/sts_result_class.php" );
require_once( "include/sts_shipment_class.php" );
require_once( "include/sts_setting_class.php" );
require_once( "include/sts_office_class.php" );

//! This will check for incoming EDI 204's -default is every 10 minutes
require_once( "exp_spawn_edi.php" );

$setting_table = sts_setting::getInstance($exspeedite_db, $sts_debug);
$sts_length_menu = $setting_table->get( 'option', 'LENGTH_MENU' );
$sts_refresh_rate = $setting_table->get( 'option', 'SHIPMENT_SCREEN_REFRESH_RATE' );
$sts_intermodal_fields = $setting_table->get( 'option', 'INTERMODAL_FIELDS' ) == 'true';
$sts_po_fields = $setting_table->get( 'option', 'PO_FIELDS' ) == 'true';
$sts_refnum_fields = $setting_table->get( 'option', 'REFNUM_FIELDS' ) == 'true';
$multi_company = $setting_table->get("option", "MULTI_COMPANY") == 'true';
$sts_export_qb = $setting_table->get( 'api', 'EXPORT_QUICKBOOKS' ) == 'true';
$sts_export_sage50 = $setting_table->get( 'api', 'EXPORT_SAGE50_CSV' ) == 'true';
$sts_restrict_filters = $setting_table->get( 'option', 'RESTRICT_SHIPMENT_BFILTERS' ) == 'true';
$sts_restrict_efilters = $setting_table->get( 'option', 'RESTRICT_SHIPMENT_EMAIL_FILTERS' ) == 'true';
$sts_cutoff = $setting_table->get( 'option', 'SHIPMENT_CUTOFF' );
$sts_approve_operations = $setting_table->get( 'option', 'SHIPMENT_APPROVE_OPERATIONS' ) == 'true';

$office_table = sts_office::getInstance($exspeedite_db, $sts_debug);

$shipment_table = sts_shipment::getInstance($exspeedite_db, $sts_debug);

//! SCR# 780 - Setting for billing emails are sent as attachments
// Needs attachments enabled to work
$sts_attachments = $setting_table->get( 'option', 'ATTACHMENTS_ENABLED' ) == 'true';
$sts_fbilling_attachment = $setting_table->get( 'option', 'SHIPMENT_FBILLING_ATTACHMENT' ) == 'true';

if( $sts_attachments && $sts_fbilling_attachment ) { // billing emails are sent as attachments
	$billde = "EXP_SHIPMENT.BILLING_STATUS = ".$shipment_table->billing_behavior_state['billed']." AND EXISTS (SELECT Q.EMAIL_QUEUE_CODE FROM EXP_EMAIL_QUEUE Q, EXP_ATTACHMENT A, EXP_ITEM_LIST L WHERE Q.EMAIL_TYPE='attachment' AND Q.EMAIL_STATUS='sent' AND A.SOURCE_CODE = SHIPMENT_CODE AND A.ATTACHMENT_CODE = Q.SOURCE_CODE AND A.SOURCE_TYPE = 'shipment' AND L.ITEM_CODE = A.FILE_TYPE AND L.ITEM_TYPE = 'Attachment type' AND L.ITEM = 'Billing' LIMIT 1)";
	
	$billdef = "EXP_SHIPMENT.BILLING_STATUS = ".$shipment_table->billing_behavior_state['billed']." AND EXISTS (SELECT Q.EMAIL_QUEUE_CODE FROM EXP_EMAIL_QUEUE Q, EXP_ATTACHMENT A, EXP_ITEM_LIST L WHERE Q.EMAIL_TYPE='attachment' AND Q.EMAIL_STATUS IN ('pdf-error', 'send-error') AND A.SOURCE_CODE = SHIPMENT_CODE AND A.ATTACHMENT_CODE = Q.SOURCE_CODE AND A.SOURCE_TYPE = 'shipment' AND L.ITEM_CODE = A.FILE_TYPE AND L.ITEM_TYPE = 'Attachment type' AND L.ITEM = 'Billing' LIMIT 1)";
	
	$billdnoe = "EXP_SHIPMENT.BILLING_STATUS = ".$shipment_table->billing_behavior_state['billed']." AND NOT EXISTS (SELECT Q.EMAIL_QUEUE_CODE FROM EXP_EMAIL_QUEUE Q, EXP_ATTACHMENT A, EXP_ITEM_LIST L WHERE Q.EMAIL_TYPE='attachment' AND Q.EMAIL_STATUS='sent' AND A.SOURCE_CODE = SHIPMENT_CODE AND A.ATTACHMENT_CODE = Q.SOURCE_CODE AND A.SOURCE_TYPE = 'shipment' AND L.ITEM_CODE = A.FILE_TYPE AND L.ITEM_TYPE = 'Attachment type' AND L.ITEM = 'Billing' LIMIT 1)";	
} else { // billing emails are sent as invoices
	$billde = "EXP_SHIPMENT.BILLING_STATUS = ".$shipment_table->billing_behavior_state['billed']." AND (INVOICE_EMAIL_STATUS='sent' OR EXISTS (SELECT EMAIL_QUEUE_CODE FROM EXP_EMAIL_QUEUE WHERE SOURCE_TYPE='shipment' AND SOURCE_CODE=SHIPMENT_CODE AND EMAIL_STATUS='sent' LIMIT 1))";
	
	$billdef = "EXP_SHIPMENT.BILLING_STATUS = ".$shipment_table->billing_behavior_state['billed']." AND (INVOICE_EMAIL_STATUS IN ('pdf-error', 'send-error') OR EXISTS (SELECT EMAIL_QUEUE_CODE FROM EXP_EMAIL_QUEUE WHERE SOURCE_TYPE='shipment' AND SOURCE_CODE=SHIPMENT_CODE AND EMAIL_STATUS IN ('pdf-error', 'send-error') LIMIT 1))";
	
	$billdnoe = "EXP_SHIPMENT.BILLING_STATUS = ".$shipment_table->billing_behavior_state['billed']." AND (INVOICE_EMAIL_STATUS='unsent' OR NOT EXISTS (SELECT EMAIL_QUEUE_CODE FROM EXP_EMAIL_QUEUE WHERE SOURCE_TYPE='shipment' AND SOURCE_CODE=SHIPMENT_CODE AND EMAIL_STATUS='sent' LIMIT 1))";	
}

if( isset($_GET['SHOW_STATUS_'.SHIPMENT_TABLE]) ) {
	$_POST['SHIPMENT_STATUS'] = $_GET['SHOW_STATUS_'.SHIPMENT_TABLE];
}

if( $multi_company ) {
	if( ! isset($_SESSION['SHIPMENT_OFFICE']) ) $_SESSION['SHIPMENT_OFFICE'] = $_SESSION['EXT_USER_OFFICE'];
	if( isset($_POST['SHIPMENT_OFFICE']) ) $_SESSION['SHIPMENT_OFFICE'] = $_POST['SHIPMENT_OFFICE'];
}
	
if( ! isset($_SESSION['SHIPMENT_STATUS']) ) $_SESSION['SHIPMENT_STATUS'] = 'eandrd';
if( isset($_POST['SHIPMENT_STATUS']) ) $_SESSION['SHIPMENT_STATUS'] = $_POST['SHIPMENT_STATUS'];


//! SCR# 350 - Add date filters
if( ! isset($_SESSION['PICKDROP']) ) $_SESSION['PICKDROP'] = 'pick';
if( ! isset($_SESSION['DUEACT']) ) $_SESSION['DUEACT'] = 'due';
if( ! isset($_SESSION['FROMDATE']) ) $_SESSION['FROMDATE'] = '';
if( ! isset($_SESSION['TODATE']) ) $_SESSION['TODATE'] = '';
if( ! isset($_SESSION['DFILTER']) ) $_SESSION['DFILTER'] = 'default';

if( isset($_POST["dofilter"])) {
	if( isset($_POST['PICKDROP']) ) $_SESSION['PICKDROP'] = $_POST['PICKDROP'];
	if( isset($_POST['DUEACT']) ) $_SESSION['DUEACT'] = $_POST['DUEACT'];
	$_SESSION['FROMDATE'] = empty($_POST['FROMDATE']) ? '' :
		date("Y-m-d", strtotime($_POST['FROMDATE']));
	$_SESSION['TODATE'] = empty($_POST['TODATE']) ? '' :
		date("Y-m-d", strtotime($_POST['TODATE']));
} else if( isset($_POST["clearfilter"])) {
	$_SESSION['PICKDROP'] = 'pick';
	$_SESSION['DUEACT'] = 'due';
	$_SESSION['FROMDATE'] = '';
	$_SESSION['TODATE'] = '';
}

//! Initialize $match
$match = '';
$tip = 'No date filter set';

if( ! empty($_SESSION['FROMDATE']) && ! empty($_SESSION['TODATE']) &&
	strtotime($_SESSION['TODATE']) >= strtotime($_SESSION['FROMDATE']) ) {
	$_SESSION['DFILTER'] = 'success';
	$range = "BETWEEN '".date("Y-m-d", strtotime($_SESSION['FROMDATE']))."' AND '".date("Y-m-d", strtotime($_SESSION['TODATE']))."'";
	$range2 = 'between '.date("m/d/Y", strtotime($_SESSION['FROMDATE']))." and ".date("m/d/Y", strtotime($_SESSION['TODATE']));
	if( $_SESSION['PICKDROP'] == 'pick') {
		if( $_SESSION['DUEACT'] == 'due' ) {
			$match = ($match <> '' ? $match." AND " : "")."PICKUP_DATE ".$range;
			$tip = 'Pickup due '.$range2;
		} else {
			$match = ($match <> '' ? $match." AND " : "")."(SELECT DATE(ACTUAL_DEPART) FROM EXP_STOP WHERE EXP_STOP.SHIPMENT = EXP_SHIPMENT.SHIPMENT_CODE AND STOP_TYPE='pick' LIMIT 1) ".$range;
			$tip = 'Actual Pickup '.$range2;
		}
	} else {
		if( $_SESSION['DUEACT'] == 'due' ) {
			$match = ($match <> '' ? $match." AND " : "")."DELIVER_DATE ".$range;
			$tip = 'Delivery due '.$range2;
		} else {
			$match = ($match <> '' ? $match." AND " : "")."(SELECT DATE(COALESCE(ACTUAL_ARRIVE, ACTUAL_DEPART)) FROM EXP_STOP WHERE EXP_STOP.SHIPMENT = EXP_SHIPMENT.SHIPMENT_CODE AND STOP_TYPE='drop' LIMIT 1) ".$range;
			$tip = 'Actual Delivery '.$range2;
		}
	}
	
} else {
	$_SESSION['DFILTER'] = 'default';
}

// close the session here to avoid blocking
session_write_close();

//echo '<p>Filter '.$_SESSION['PICKDROP'].' '.$_SESSION['DUEACT'].' '.$_SESSION['FROMDATE'].' - '.$_SESSION['TODATE'];

$filters_html = '<div class="form-group"><a class="btn btn-sm btn-success" href="exp_listshipment.php"><span class="glyphicon glyphicon-refresh"></span></a>';

$filters_html .= '<select class="form-control input-sm" name="SHIPMENT_STATUS" id="SHIPMENT_STATUS"   onchange="form.submit();">';

// Special cases
$filters_html .= '<option value="eandrd" '.($_SESSION['SHIPMENT_STATUS'] == 'eandrd' ? 'selected' : '').'>Entered or Ready (but NOT assigned)</option>
	';
$filters_html .= '<option value="inprog" '.($_SESSION['SHIPMENT_STATUS'] == 'inprog' ? 'selected' : '').'>In Progress (Assigned, Dispatched, Picked, Docked)</option>
	';
$filters_html .= '<option value="undel" '.($_SESSION['SHIPMENT_STATUS'] == 'undel' ? 'selected' : '').'>Undelivered (Entered, Ready, Dispatched, Picked, Docked)</option>
	';
//$filters_html .= '<option value="completed" '.($_SESSION['SHIPMENT_STATUS'] == 'completed' ? 'selected' : '').'>Completed (Delivered,Approved,Billed)</option>
//	';
$filters_html .= '<option value="all" '.($_SESSION['SHIPMENT_STATUS'] == 'all' ? 'selected' : '').'>All shipments (used to take a while)</option>
	';

$filters_html .= '<option class="select-dash" disabled="disabled">----</option>
';

//! SCR# 405 - Shipments that are no longer able to be added to a load.
if( $my_session->in_group(EXT_GROUP_ADMIN) &&
	! empty($sts_cutoff) &&
	//! SCR# 587 - Less strict date checking
	checkIsAValidDate($sts_cutoff) ) {
	$filters_html .= '<option value="expired" '.($_SESSION['SHIPMENT_STATUS'] == 'expired' ? 'selected' : '').'>Expired shipments (no longer able to add to a load)</option>
	';
}	

$filters_html .= '<option value="empty" '.($_SESSION['SHIPMENT_STATUS'] == 'empty' ? 'selected' : '').'>Empty shipments (no shipper, cons, bill-to, details)</option>
	';
	
$filters_html .= '<option value="nopickup" '.($_SESSION['SHIPMENT_STATUS'] == 'nopickup' ? 'selected' : '').'>No pickup due date</option>
	';
	
$filters_html .= '<option value="nodeliver" '.($_SESSION['SHIPMENT_STATUS'] == 'nodeliver' ? 'selected' : '').'>No deliver due date</option>
	';
	
$filters_html .= '<option value="nosp" '.($_SESSION['SHIPMENT_STATUS'] == 'nosp' ? 'selected' : '').'>Missing sales person</option>
	';
	
$filters_html .= '<option value="nobt" '.($_SESSION['SHIPMENT_STATUS'] == 'nobt' ? 'selected' : '').'>Missing Bill-to</option>
	';
	
$filters_html .= '<option class="select-dash" disabled="disabled">----</option>
';

//! SCR# 396 - Add EXT_GROUP_BILLING
//! SCR# 403 - optionally restrict access
if( ! $sts_restrict_filters ||
	$my_session->in_group(EXT_GROUP_FINANCE, EXT_GROUP_BILLING) ) {
	$filters_html .= '<option value="dandb" '.($_SESSION['SHIPMENT_STATUS'] == 'dandb' ? 'selected' : '').'>Delivered and Billed</option>
	';
	$filters_html .= '<option value="dnotb" '.($_SESSION['SHIPMENT_STATUS'] == 'dnotb' ? 'selected' : '').'>Delivered and NOT Billed</option>
	';
	if( $multi_company )
		$filters_html .= '<option value="oapproved" '.($_SESSION['SHIPMENT_STATUS'] == 'oapproved' ? 'selected' : '').'>Approved (office)</option>
	';
	if( $sts_approve_operations )
		$filters_html .= '<option value="oapproved2" '.($_SESSION['SHIPMENT_STATUS'] == 'oapproved2' ? 'selected' : '').'>Approved (Operations)</option>
	';
	
	$filters_html .= '<option value="approved" '.($_SESSION['SHIPMENT_STATUS'] == 'approved' ? 'selected' : '').'>Approved (finance)</option>
	';
	$filters_html .= '<option value="billed" '.($_SESSION['SHIPMENT_STATUS'] == 'billed' ? 'selected' : '').'>Billed (sent to accounting)</option>
	';
	$filters_html .= '<option value="unapproved" '.($_SESSION['SHIPMENT_STATUS'] == 'unapproved' ? 'selected' : '').'>Unapproved</option>
	';
	if( $sts_export_qb )
		$filters_html .= '<option value="incomp" '.($_SESSION['SHIPMENT_STATUS'] == 'incomp' ? 'selected' : '').'>Billed &amp; Incomplete (custom fields not transferred to QB)</option>
	';
}
if( ! $sts_restrict_efilters ||
	$my_session->in_group(EXT_GROUP_ADMIN) ) {

	$filters_html .= '<option value="billede" '.($_SESSION['SHIPMENT_STATUS'] == 'billede' ? 'selected' : '').'>Billed (Email sent)</option>
	';
	$filters_html .= '<option value="billedef" '.($_SESSION['SHIPMENT_STATUS'] == 'billedef' ? 'selected' : '').'>Billed (Email sent ERROR)</option>
	';
	$filters_html .= '<option value="billednoe" '.($_SESSION['SHIPMENT_STATUS'] == 'billednoe' ? 'selected' : '').'>Billed (Email NOT sent)</option>
	';
}

$filters_html .= '<option class="select-dash" disabled="disabled">----</option>
';

// Single statuses
foreach( $shipment_table->state_name as $state => $name ) {
	$filters_html .= '<option value="'.$shipment_table->state_behavior[$state].'" '.(isset($shipment_table->behavior_state[$_SESSION['SHIPMENT_STATUS']]) && $shipment_table->behavior_state[$_SESSION['SHIPMENT_STATUS']] == $state ? 'selected' : '').'>'.$name.'</option>
	';
}
$filters_html .= '</select>';

if( $multi_company && count($_SESSION['EXT_USER_OFFICES']) > 1 ) {
	$filters_html .= '<select class="form-control input-sm" name="SHIPMENT_OFFICE" id="SHIPMENT_OFFICE"   onchange="form.submit();">';
	$filters_html .= '<option value="all" '.($_SESSION['SHIPMENT_OFFICE'] == 'all' ? 'selected' : '').'>All Offices</option>
		';
	foreach( $_SESSION['EXT_USER_OFFICES'] as $value => $label ) {
		$filters_html .= '<option value="'.$value.'" '.($_SESSION['SHIPMENT_OFFICE'] == $value ? 'selected' : '').'>'.$label.'</option>
		';
	}
	$filters_html .= '</select>';
}

$filters_html .= '<button type="button" class="btn btn-'.$_SESSION['DFILTER'].' btn-sm'.(empty($tip) ? '"' : ' tip" title="'.$tip.'"').' data-toggle="modal" data-target="#datefilter_modal"><span class="glyphicon glyphicon-calendar"></span></button>';


if( $sts_export_qb && $my_session->in_group(EXT_GROUP_FINANCE) &&
	isset($_SESSION['SHIPMENT_STATUS']) &&
	$_SESSION['SHIPMENT_STATUS'] == 'incomp' ) {
	$filters_html .= '<a class="btn btn-sm btn-danger" onclick="confirmation(\'Confirm: Resend ALL custom fields to QuickBooks?<br>This is helpful when QuickBooks transfer was incomplete.<br>You can examine the status by looking at each shipment.\', \'exp_qb_retry.php?TYPE=dataext&CODE=ALL\')"><span class="glyphicon glyphicon-ok"></span> Resend ALL custom fields to QB</a>';
}

if( $sts_export_qb && $my_session->in_group(EXT_GROUP_FINANCE) &&
	isset($_SESSION['SHIPMENT_STATUS']) &&
	$_SESSION['SHIPMENT_STATUS'] == 'approved' ) {
	$filters_html .= '<a class="btn btn-sm btn-danger" onclick="confirmation(\'Confirm: Resend ALL Approved Invoices to QuickBooks?<br>This is helpful when QuickBooks was unavailable, and you want to clear the backlog.<br>You can examine the status by looking at each shipment.\', \'exp_qb_retry.php?TYPE=shipment&CODE=ALL\')"><span class="glyphicon glyphicon-ok"></span> Resend ALL to QB</a>';
} else

if( $sts_export_sage50 && $my_session->in_group(EXT_GROUP_SAGE50) &&
	isset($_SESSION['SHIPMENT_STATUS']) &&
	$_SESSION['SHIPMENT_STATUS'] == 'approved' ) {
	$filters_html .= ' <a class="btn btn-sm btn-danger" onclick="confirmation(\'Confirm: Send ALL Approved Invoices to Sage 50?<br>This creates a CSV file for import to Sage 50.<br>In Sage 50, select <strong>File -> Select Import/Export...</strong><br>Click on <strong>Accounts Receivable</strong> and then <strong>Import</strong><br>Make sure all the columns are in the correct order in Sage 50 during import.<br>The approved invoices will be marked as billed.<br><br>Be sure to <strong>print out all approved invoices</strong> first! (report menu)\', \'exp_export_csv.php?pw=GoldUltimate&type=invoice\')"><span class="glyphicon glyphicon-list-alt"></span> <span class="glyphicon glyphicon-arrow-right"></span> Sage 50</a>';
}

if( $my_session->in_group(EXT_GROUP_ADMIN) && isset($_SESSION['SHIPMENT_STATUS']) &&
	isset($shipment_table->state_behavior[$_SESSION['SHIPMENT_STATUS']]) &&
	$shipment_table->state_behavior[$_SESSION['SHIPMENT_STATUS']] == 'dropped' ) {
	$filters_html .= '<a class="btn btn-sm btn-danger" onclick="confirmation(\'Confirm: Update miles for all delivered shipments?<br>This may be helpful to make sure the miles are correct before billing. Note that after you have saved the billing information, it will not be so helpful.<br>You can also update a single shipment by viewing it.<br>It may also take a long time to run.\', \'exp_update_shipment_miles.php?PW=Wildcat\')"><span class="glyphicon glyphicon-road"></span> Update Miles</a>';
}

$filters_html .= '</div>';

//! Removed 
//COALESCE(EXP_SHIPMENT.BILLTO_NAME,'') <> ''

if( $_SESSION['SHIPMENT_STATUS'] == 'all' ) {
} else if( $_SESSION['SHIPMENT_STATUS'] == 'eandrd' ) {
	$states = array($shipment_table->behavior_state['entry'],
		$shipment_table->behavior_state['assign']);
	$match = ($match <> '' ? $match." AND " : "")."EXP_SHIPMENT.CURRENT_STATUS IN (".implode(', ', $states).")".
	' AND COALESCE(LOAD_CODE, 0) = 0'; //! SCR# 879 - don't include if load > 0
} else if( $_SESSION['SHIPMENT_STATUS'] == 'inprog' ) {
	$states = array($shipment_table->behavior_state['dispatch'],
		$shipment_table->behavior_state['picked'],
		$shipment_table->behavior_state['docked']);
	$match = ($match <> '' ? $match." AND " : "").'(EXP_SHIPMENT.CURRENT_STATUS IN ('.implode(', ', $states).')'.' OR (EXP_SHIPMENT.CURRENT_STATUS = '.$shipment_table->behavior_state['assign'].
	' AND COALESCE(LOAD_CODE, 0) > 0))'; //! SCR# 879 - add load > 0
} else if( $_SESSION['SHIPMENT_STATUS'] == 'dandb' ) {
	$match = ($match <> '' ? $match." AND " : "").'EXP_SHIPMENT.CURRENT_STATUS = '.$shipment_table->behavior_state['dropped'].' AND	EXP_SHIPMENT.BILLING_STATUS = '.$shipment_table->billing_behavior_state['billed'];
} else if( $_SESSION['SHIPMENT_STATUS'] == 'dnotb' ) {
	$match = ($match <> '' ? $match." AND " : "").'EXP_SHIPMENT.CURRENT_STATUS = '.$shipment_table->behavior_state['dropped'].' AND	EXP_SHIPMENT.BILLING_STATUS != '.$shipment_table->billing_behavior_state['billed'];
} else if( $_SESSION['SHIPMENT_STATUS'] == 'oapproved' ) {
	$match = ($match <> '' ? $match." AND " : "").'EXP_SHIPMENT.BILLING_STATUS = '.$shipment_table->billing_behavior_state['oapproved'];
} else if( $_SESSION['SHIPMENT_STATUS'] == 'oapproved2' ) {
	$match = ($match <> '' ? $match." AND " : "").'EXP_SHIPMENT.BILLING_STATUS = '.$shipment_table->billing_behavior_state['oapproved2'].' AND EXP_SHIPMENT.CURRENT_STATUS != '.$shipment_table->behavior_state['cancel'];
} else if( $_SESSION['SHIPMENT_STATUS'] == 'approved' ) {
	$match = ($match <> '' ? $match." AND " : "").'EXP_SHIPMENT.BILLING_STATUS = '.$shipment_table->billing_behavior_state['approved'];
} else if( $_SESSION['SHIPMENT_STATUS'] == 'billed' ) {
	$match = ($match <> '' ? $match." AND " : "").'EXP_SHIPMENT.BILLING_STATUS = '.$shipment_table->billing_behavior_state['billed'];
} else if( $_SESSION['SHIPMENT_STATUS'] == 'billede' ) {
	$match = ($match <> '' ? $match." AND " : "").$billde;
} else if( $_SESSION['SHIPMENT_STATUS'] == 'billedef' ) {
	$match = ($match <> '' ? $match." AND " : "").$billdef;
} else if( $_SESSION['SHIPMENT_STATUS'] == 'billednoe' ) {
	$match = ($match <> '' ? $match." AND " : "").$billdnoe;
} else if( $_SESSION['SHIPMENT_STATUS'] == 'unapproved' ) {
	$match = ($match <> '' ? $match." AND " : "").'EXP_SHIPMENT.BILLING_STATUS = '.$shipment_table->billing_behavior_state['unapproved'];
} else if( $_SESSION['SHIPMENT_STATUS'] == 'undel' ) {
	$states = array($shipment_table->behavior_state['entry'],
		$shipment_table->behavior_state['assign'],
		$shipment_table->behavior_state['dispatch'],
		$shipment_table->behavior_state['picked'],
		$shipment_table->behavior_state['docked']);
	$match = ($match <> '' ? $match." AND " : "").'EXP_SHIPMENT.CURRENT_STATUS IN ('.implode(', ', $states).')';
} else if( $_SESSION['SHIPMENT_STATUS'] == 'completed' ) {
	$states = array($shipment_table->behavior_state['dropped'],
		$shipment_table->behavior_state['approved']);
	$match = ($match <> '' ? $match." AND " : "").'EXP_SHIPMENT.CURRENT_STATUS IN ('.implode(', ', $states).')';
} else if( $_SESSION['SHIPMENT_STATUS'] == 'incomp' && $my_session->in_group(EXT_GROUP_FINANCE) ) {
	$match = ($match <> '' ? $match." AND " : "").'EXP_SHIPMENT.BILLING_STATUS = '.$shipment_table->billing_behavior_state['billed'].
		" AND COALESCE(quickbooks_dataext_retries,0) <> -1";
} else if( $_SESSION['SHIPMENT_STATUS'] == 'expired' && $my_session->in_group(EXT_GROUP_ADMIN) ) {
	//! SCR# 419 - use GET_ASOF rather than created date
	$match = ($match <> '' ? $match." AND " : "")."EXP_SHIPMENT.LOAD_CODE = 0 AND DATE(GET_ASOF(EXP_SHIPMENT.SHIPMENT_CODE)) < DATE('".date("Y-m-d", strtotime($sts_cutoff))."')";
	
} else if( $_SESSION['SHIPMENT_STATUS'] == 'empty' ) {
	$match = ($match <> '' ? $match." AND " : "").
		"EXP_SHIPMENT.CURRENT_STATUS != ".$shipment_table->behavior_state['cancel']." AND ".
		"EXP_SHIPMENT.SHIPPER_NAME IS NULL AND EXP_SHIPMENT.SHIPPER_CITY IS NULL AND ".
		"EXP_SHIPMENT.CONS_NAME IS NULL AND EXP_SHIPMENT.CONS_CITY IS NULL AND ".
		"COALESCE(EXP_SHIPMENT.BILLTO_CLIENT_CODE, 0) = 0 AND ".
		"(SELECT COUNT(*) FROM EXP_DETAIL WHERE EXP_DETAIL.SHIPMENT_CODE = EXP_SHIPMENT.SHIPMENT_CODE) = 0";

} else if( $_SESSION['SHIPMENT_STATUS'] == 'nopickup' ) {
	$match = ($match <> '' ? $match." AND " : "").
		"EXP_SHIPMENT.CURRENT_STATUS != ".$shipment_table->behavior_state['cancel']." AND ".
		"COALESCE(EXP_SHIPMENT.PICKUP_DATE, '') = ''";

} else if( $_SESSION['SHIPMENT_STATUS'] == 'nodeliver' ) {
	$match = ($match <> '' ? $match." AND " : "").
		"EXP_SHIPMENT.CURRENT_STATUS != ".$shipment_table->behavior_state['cancel']." AND ".
		"COALESCE(EXP_SHIPMENT.DELIVER_DATE, '') = ''";

} else if( $_SESSION['SHIPMENT_STATUS'] == 'nosp' ) {
	$match = ($match <> '' ? $match." AND " : "").
		"EXP_SHIPMENT.CURRENT_STATUS != ".$shipment_table->behavior_state['cancel']." AND ".
		"COALESCE(EXP_SHIPMENT.SALES_PERSON, 0) = 0";

} else if( $_SESSION['SHIPMENT_STATUS'] == 'nobt' ) {
	$match = ($match <> '' ? $match." AND " : "").
		"EXP_SHIPMENT.CURRENT_STATUS != ".$shipment_table->behavior_state['cancel']." AND ".
		"COALESCE(EXP_SHIPMENT.BILLTO_CLIENT_CODE, 0) = 0";

} else {
	$match = ($match <> '' ? $match." AND " : "").'EXP_SHIPMENT.CURRENT_STATUS = '.$shipment_table->behavior_state[$_SESSION['SHIPMENT_STATUS']];
}

if( $multi_company ) {
	if( $_SESSION['SHIPMENT_OFFICE'] == 'all' )
		$match = ($match <> '' ? $match." AND " : "")."OFFICE_CODE IN (".implode(', ', array_keys($_SESSION['EXT_USER_OFFICES'])).")";
	else
		$match = ($match <> '' ? $match." AND " : "")."OFFICE_CODE = ".$_SESSION['SHIPMENT_OFFICE'];
}


$sts_result_shipments_edit['filters_html'] = $filters_html;

$rslt = new sts_result( $shipment_table, $match, $sts_debug );

if( isset($_SESSION['SHIPMENT_STATUS']) &&
	in_array($_SESSION['SHIPMENT_STATUS'],
		array('picked','dropped','approved','billed','cancel')) ) {
	unset($sts_result_shipments_edit['add']);
	
	//$sts_result_shipments_layout['quickbooks_listid_customer'] = array( 'label' => 'QB&nbsp;Billto', 'format' => 'text' );
	//$sts_result_shipments_layout['quickbooks_txnid_invoice'] = array( 'label' => 'QB&nbsp;TxnID', 'format' => 'text' );
}

//! SCR# 880 - fix default sort column
$ship_column = 8;
if( ! $sts_intermodal_fields ) {
	unset($sts_result_shipments_layout['FS_NUMBER'], $sts_result_shipments_layout['SYNERGY_IMPORT']);
	//$ship_column--;
}

if( ! $sts_po_fields ) {
	unset($sts_result_shipments_layout['PO_NUMBER']);
	//$ship_column--;
}
if( ! $sts_refnum_fields ) {
	unset($sts_result_shipments_layout['REF_NUMBER']);
	//$ship_column--;
}
if( ! $multi_company ) {
	unset($sts_result_shipments_layout['SS_NUMBER']);
	$ship_column--;
}

echo $rslt->render( $sts_result_shipments_layout, $sts_result_shipments_edit, false, false );

//! SCR# 350 - Add date filters
if( $_SESSION['PICKDROP'] == 'pick' ) {
	$pd1 = ' checked';
	$pd2 = '';
} else {
	$pd1 = '';
	$pd2 = ' checked';
}
if( $_SESSION['DUEACT'] == 'due' ) {
	$da1 = ' checked';
	$da2 = '';
} else {
	$da1 = '';
	$da2 = ' checked';
}

$date_format = $_SESSION['ios'] == 'true' ? "Y-m-d" : "m/d/Y";
if( ! empty($_SESSION['FROMDATE']) ) {
	$fd = ' value="'.date($date_format, strtotime($_SESSION['FROMDATE'])).'"';
} else {
	$fd = '';
}
if( ! empty($_SESSION['TODATE']) ) {
	$td = ' value="'.date($date_format, strtotime($_SESSION['TODATE'])).'"';
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
			<h4 class="modal-title" id="myModalLabel"><span class="text-success"><strong><span class="glyphicon glyphicon-calendar"></span> Date Filter</strong></span></h4>
		</div>
		<div class="modal-body">
			<form role="form" class="form-horizontal" action="exp_listshipment.php" method="post" enctype="multipart/form-data" name="datefilter" id="datefilter">
					<div class="form-group tighter">
						<div class="col-sm-6 well well-md tighter">
							<div class="form-group tighter">
								<div class="col-sm-6 tighter">
									<label><input name="PICKDROP" id="PICKDROP1" type="radio" value="pick"<?php echo $pd1; ?>> Pickup</label>
								</div>
								<div class="col-sm-6 tighter">
									<label><input name="PICKDROP" id="PICKDROP2"  type="radio" value="drop"<?php echo $pd2; ?>> Delivery</label>
								</div>
							</div>
						</div>
						<div class="col-sm-6 well well-md tighter">
							<div class="form-group tighter">
								<div class="col-sm-6">
									<label><input name="DUEACT" id="DUEACT1" type="radio" value="due"<?php echo $da1; ?>> Due</label>
								</div>
								<div class="col-sm-6 tighter">
									<label><input name="DUEACT" id="DUEACT2"  type="radio" value="actual"<?php echo $da2; ?>> Actual</label>
								</div>
							</div>
						</div>
					</div>
					<div class="form-group tighter">
						<div class="col-sm-6 well well-md tighter">
							<input type="<?php echo ($_SESSION['ios'] == 'true' ? 'date' : 'text');?>" class="form-control input-sm<?php echo ($_SESSION['ios'] != 'true' ? ' date' : '');?>" style="width: auto;" name="FROMDATE" placeholder="From Date"<?php echo $fd; ?>>
						</div>
						<div class="col-sm-6 well well-md tighter">
							<input type="<?php echo ($_SESSION['ios'] == 'true' ? 'date' : 'text');?>" class="form-control input-sm<?php echo ($_SESSION['ios'] != 'true' ? ' date' : '');?>" style="width: auto;" name="TODATE" placeholder="To Date"<?php echo $td; ?>>
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
			document.documentElement.style.overflow = 'hidden';  // firefox, chrome
			document.body.scroll = "no"; // ie only
			<?php } ?>
			
			console.log( 'Sort: ', '<?php echo $_SESSION['SHIPMENT_STATUS'].', '.$ship_column; ?>');
			
			var opts = {
		        //"bLengthChange": false,
		        "bFilter": true,
		        stateSave: true,
		    //    "stateLoadParams": function (settings, data) {
			//        data.order = [[ <?php echo $_SESSION['SHIPMENT_STATUS'] == 'completed' ? ($ship_column+1).', "desc"' : $ship_column.', "asc"'; ?> ]];
			//    },
		        "bSort": true,
		        "bInfo": true,
				//"bAutoWidth": false,
				//"bProcessing": true, 
				"sScrollX": "100%",
				"sScrollY": ($(window).height() - 270) + "px",
				//"sScrollXInner": "120%",
		        "lengthMenu": [<?php echo isset($sts_length_menu) ? $sts_length_menu : '25, 50, 100, 250'; ?>],
				"bPaginate": true,
				"bScrollCollapse": false,
				"bSortClasses": false,
				"order": [[ <?php echo $_SESSION['SHIPMENT_STATUS'] == 'completed' ? ($ship_column+1).', "desc"' : $ship_column.', "asc"'; ?> ]],
				"processing": true,
				"serverSide": true,
				//"dom": "frtiS",
				"deferRender": true,
				"ajax": {
					"url": "exp_listshipmentajax.php",
					"data": function( d ) {
						d.match = encodeURIComponent("<?php echo $match; ?>");
					}
				},
				"columns": [
					{ "searchable": false, "orderable": false },
					<?php
						foreach( $sts_result_shipments_layout as $key => $row ) {
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
				"infoCallback": function( settings, start, end, max, total, pre ) {
					var api = this.api();
					return pre + ' (' + api.ajax.json().timing + ' s)';
				}
			};
			
			var myTable = $('#EXP_SHIPMENT').DataTable(opts);
			//! SCR# 880 - fix default sort column
			// Use this to forget the default column issue
			//myTable.state.clear();
			
			$('#EXP_SHIPMENT').on( 'draw.dt', function () {
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

