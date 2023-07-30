<?php 

// $Id: exp_editclient.php 5124 2023-06-05 19:05:42Z dev $
// Edit Client

// Set flag that this is a parent file
define( '_STS_INCLUDE', 1 );

// Setup Session
require_once( "include/sts_session_setup.php" );

require_once( "include/sts_config.php" );
$sts_debug = (isset($_GET['debug'])  || isset($_POST['debug'])) && in_group( EXT_GROUP_DEBUG );
require_once( "include/sts_session_class.php" );

$my_session = sts_session::getInstance( $exspeedite_db, $sts_debug );
$my_session->access_check( $sts_table_access[CLIENT_TABLE], EXT_GROUP_SALES );	// Make sure we should be here

$sts_subtitle = "Edit Client";
require_once( "include/header_inc.php" );
require_once( "include/navbar_inc.php" );

require_once( "include/sts_result_class.php" );
require_once( "include/sts_form_class.php" );
require_once( "include/sts_client_class.php" );
require_once( "include/sts_shipment_class.php" );
require_once( "include/sts_contact_info_class.php" );
require_once( "include/sts_setting_class.php" );
require_once( "include/sts_attachment_class.php" );
require_once( "include/sts_user_log_class.php" );
require_once( "include/sts_item_list_class.php" );

//! SCR# 613 - check we have a CODE parameter
if( isset($_POST) && isset($_POST['CLIENT_CODE']) ) $_GET['CODE'] = $_POST['CLIENT_CODE'];
else if( isset($_POST) && isset($_POST['RESULT_SAVE_CODE']) )	// sts_result saved the code
	$_GET['CODE'] = $_POST['RESULT_SAVE_CODE'];
if( isset($_POST) && isset($_POST['CSTATE']) ) //! SCR# 613 - Get the state
	$_GET['CSTATE'] = $_POST['CSTATE'];
if( isset($_GET) && isset($_GET['CODE']) ) {

$setting_table = sts_setting::getInstance($exspeedite_db, $sts_debug);
$sts_length_menu = $setting_table->get( 'option', 'LENGTH_MENU' );
$sts_refresh_rate = $setting_table->get( 'option', 'SHIPMENT_SCREEN_REFRESH_RATE' );
$sts_pipco_fields = $setting_table->get( 'option', 'PIPCO_FIELDS' ) == 'true';
$sts_po_fields = $setting_table->get( 'option', 'PO_FIELDS' ) == 'true';
$sts_refnum_fields = $setting_table->get( 'option', 'REFNUM_FIELDS' ) == 'true';
$sts_export_sage50 = $setting_table->get( 'api', 'EXPORT_SAGE50_CSV' ) == 'true';
$sts_attachments = $setting_table->get( 'option', 'ATTACHMENTS_ENABLED' ) == 'true';
$sts_cms_enabled = $setting_table->get("option", "CMS_ENABLED") == 'true';
$cms_salespeople_leads = ($setting_table->get("option", "CMS_SALESPEOPLE_LEADS") == 'true');
$sts_extra_stops = $setting_table->get( 'option', 'CLIENT_EXTRA_STOPS' ) == 'true';
$sts_restrict_salesperson = $setting_table->get( 'option', 'EDIT_SALESPERSON_RESTRICTED' ) == 'true';

//! SCR# 906 - send notifications
$sts_send_notifications = $setting_table->get( 'option', 'SEND_NOTIFICATIONS' ) == 'true';
if( ! $sts_send_notifications ) {
	$match = preg_quote('<!-- NOTIFY_1 -->').'(.*)'.preg_quote('<!-- NOTIFY_2 -->');
	$sts_form_editclient_form['layout'] = preg_replace('/'.$match.'/s', '',
		$sts_form_editclient_form['layout'], 1);	
}

//! SCR# 852 - Containers Feature
$sts_containers = $setting_table->get( 'option', 'CONTAINERS' ) == 'true';
if( ! $sts_containers ) {
	$match = preg_quote('<!-- INTERMODAL1 -->').'(.*)'.preg_quote('<!-- INTERMODAL2 -->');
	$sts_form_editclient_form['layout'] = preg_replace('/'.$match.'/s', '',
		$sts_form_editclient_form['layout'], 1);	
}

//! SCR# 639 - Restrict access to insurance / credit limit
$sts_restrict_ins = $setting_table->get( 'option', 'RESTRICT_INSURANCE' ) == 'true';
$sts_restrict_credit = $setting_table->get( 'option', 'RESTRICT_CREDIT_LIMIT' ) == 'true';
$sts_restrict_currency = $setting_table->get( 'option', 'RESTRICT_CURRENCY' ) == 'true';


//! SCR# 789 - Email queue - show batch invoices
$sts_email_queueing = $setting_table->get( 'option', 'EMAIL_QUEUEING' ) == 'true';

$client_table = sts_client::getInstance($exspeedite_db, $sts_debug);
$item_list_table = sts_item_list::getInstance($exspeedite_db, $sts_debug);

$sts_form_editclient_form = $item_list_table->equipment_checkboxes( $sts_form_editclient_form, 'client', $_GET['CODE'] );


//! SCR# 584 - New coulum Client_ID
if( ! $client_table->client_id() ) {
	$match = preg_quote('<!-- CLIENT_ID_1 -->').'(.*)'.preg_quote('<!-- CLIENT_ID_2 -->');
	$sts_form_editclient_form['layout'] = preg_replace('/'.$match.'/s', '',
		$sts_form_editclient_form['layout'], 1);	
}

if( ! $sts_export_sage50 ) {
	$match = preg_quote('<!-- SAGE50_1 -->').'(.*)'.preg_quote('<!-- SAGE50_2 -->');
	$sts_form_editclient_form['layout'] = preg_replace('/'.$match.'/s', '',
		$sts_form_editclient_form['layout'], 1);	
} else {
		$sts_form_editclient_form['buttons'] = array( 
		array( 'label' => 'Sage 50', 'link' => 'exp_export_csv.php?pw=GoldUltimate&type=client&code=%CLIENT_CODE%',
		'button' => 'danger', 'icon' => '<span class="glyphicon glyphicon-arrow-right"></span>',
		'restrict' => EXT_GROUP_SAGE50 )
		);
}

if( ! $sts_extra_stops ) {
	$match = preg_quote('<!-- EXTRA_1 -->').'(.*)'.preg_quote('<!-- EXTRA_2 -->');
	$sts_form_editclient_form['layout'] = preg_replace('/'.$match.'/s', '',
		$sts_form_editclient_form['layout'], 1);	
}

$client_type = 'client';
$sales_person = 0;
if( ! $sts_cms_enabled ) {
	$match = preg_quote('<!-- CMS01 -->').'(.*)'.preg_quote('<!-- CMS02 -->');
	$sts_form_editclient_form['layout'] = preg_replace('/'.$match.'/s', '',
		$sts_form_editclient_form['layout'], 1);	
} else {
	$check = $client_table->fetch_rows("CLIENT_CODE = ".$_GET['CODE'],
		"CLIENT_TYPE, COALESCE(SALES_PERSON, 0) AS SALES_PERSON");
	if( is_array($check) && count($check) == 1) {
		if( isset($check[0]["CLIENT_TYPE"]) )
			$client_type = $check[0]["CLIENT_TYPE"];
		if( isset($check[0]["SALES_PERSON"]) )
			$sales_person = $check[0]["SALES_PERSON"];
	}
}

//! SCR# 420 - add disabled to truly block it
if( $sts_cms_enabled && $sts_restrict_salesperson &&
	! ( $cms_salespeople_leads && $client_type == 'lead' &&
		($sales_person == 0 || $sales_person == $_SESSION['EXT_USER_CODE']) ) &&
	! $my_session->in_group(EXT_GROUP_ADMIN, EXT_GROUP_MANAGER) ) {
	$sts_form_edit_client_fields['SALES_PERSON']['extras'] = 'disabled readonly';
}

//! SCR# 639 - Restrict access to insurance / credit limit
if( $sts_restrict_ins && ! $my_session->in_group(EXT_GROUP_ADMIN) ) {
	$sts_form_edit_client_fields['GENERAL_LIAB_INS']['extras'] = 'disabled readonly';
	$sts_form_edit_client_fields['AUTO_LIAB_INS']['extras'] = 'disabled readonly';
	$sts_form_edit_client_fields['CARGO_LIAB_INS']['extras'] = 'disabled readonly';
}

if( $sts_restrict_credit && ! $my_session->in_group(EXT_GROUP_ADMIN) ) {
	$sts_form_edit_client_fields['CREDIT_LIMIT']['extras'] = 'disabled readonly';
}

if( $sts_restrict_currency && ! $my_session->in_group(EXT_GROUP_ADMIN) ) {
	$sts_form_edit_client_fields['CURRENCY_CODE']['extras'] = 'disabled readonly';
}


switch( $client_type ) {
	case 'lead':
		$edit_form = $sts_form_editlead_form;
		break;
	
	case 'prospect':
		$edit_form = $sts_form_editprospect_form;
		break;
	
	case 'client':
		$edit_form = $sts_form_editclient_form;
		break;
}

//! SCR# 97 - button to rates
if( ! isset($edit_form['buttons']) || ! is_array($edit_form['buttons']))
	$edit_form['buttons'] = array();

//! SCR# 435 - only visible for a client
if( $client_type == 'client' )
	$edit_form['buttons'][] = 
		array( 'label' => 'Rates',
		'link' => 'exp_addclientrate.php?TYPE=client&CODE='.$_GET['CODE'],
		'button' => 'info', 'tip' => 'Client Rates',
		'icon' => '<span class="glyphicon glyphicon-usd"></span>' );

//! CMS - add buttons for state changes
if( $sts_cms_enabled ) {
	if( ! isset($edit_form['buttons']) || ! is_array($edit_form['buttons']))
		$edit_form['buttons'] = array();
	
	$check = $client_table->fetch_rows("CLIENT_CODE = ".$_GET['CODE'],
		"CURRENT_STATUS");
	if( is_array($check) && count($check) == 1 ) {
		$status = $check[0]["CURRENT_STATUS"];
		$following = $client_table->following_states( $status );
		if( is_array($following) && count($following) > 0 ) {
			foreach( $following as $row ) {
				if( $client_table->state_change_ok( $_GET['CODE'], $status, $row['CODE'] ) ) {
		  			$edit_form['buttons'][] = 
		  			array( 'label' => $client_table->state_name[$row['CODE']],
		  			'link' => 'exp_client_state.php?CODE='.$_GET['CODE'].
		  			'&STATE='.$row['CODE'].'&CSTATE='.$status,
		  			'button' => 'primary', 'tip' => $row['DESCRIPTION'],
		  			'icon' => '<span class="glyphicon glyphicon-arrow-right"></span>',
		  			'restrict' => EXT_GROUP_SALES );
				} else {
		  			$edit_form['buttons'][] = 
		  			array( 'label' => $client_table->state_name[$row['CODE']],
		  			'link' => 'exp_client_state.php?CODE='.$_GET['CODE'].
		  			'&STATE='.$row['CODE'].'&CSTATE='.$status,
		  			'button' => 'primary', 'tip' => $client_table->state_change_error,
		  			'disabled' => true,
		  			'icon' => '<span class="glyphicon glyphicon-remove"></span>',
		  			'restrict' => EXT_GROUP_SALES );
				}
			}
		}
		
		if( $client_table->state_behavior[$status] == 'dead' ) {
			$edit_form['buttons'][] = 
				array( 'label' => 'Delete '.$client_type,
					'link' => 'exp_deleteclient.php?TYPE=del&CODE='.$_GET['CODE'],
					'button' => 'danger',
					'icon' => '<span class="glyphicon glyphicon-trash"></span>',
					'restrict' => EXT_GROUP_ADMIN );
		}
	}
	
	//$edit_form['buttons_menu'] = 'test';
	
}

$client_form = new sts_form($edit_form, $sts_form_edit_client_fields, $client_table, $sts_debug);

//! Any click triggers a save
$client_form->set_autosave();


if( isset($_POST) && count($_POST) > 0 && ! isset($_POST['RESULT_SAVE_CODE']) ) {		// Process completed form
	$result = $client_form->process_edit_form();

	if( $result ) {
		$item_list_table->process_equipment_checkboxes('client', $_POST['CLIENT_CODE']);
		if( $client_form->last_changes() != '' ) {
			$user_log_table = sts_user_log::getInstance($exspeedite_db, $sts_debug);
			if( isset($_POST['CLIENT_CODE']) && isset($_POST['CLIENT_NAME']))
				$client = '<a href="exp_editclient.php?CODE='.$_POST['CLIENT_CODE'].'">'.$_POST['CLIENT_NAME'].'</a>: ';
			else if( isset($_POST['CLIENT_CODE']) )
				$client = 'client# '.$_POST['CLIENT_CODE'].': ';
			else
				$client = 'unknown client: ';
			
			$entry = $user_log_table->log_event('profiles', 'Edit '.$client_type.': '.$client.$client_form->last_changes());
			if( $entry > 0 ) {
				$email_type = 'carrierins';
				$email_code = $entry;
				require_once( "exp_spawn_send_email.php" );
			}
		}

		if( $sts_debug ) die; // So we can see the results
		if( isset($_POST["saveadd"]) ) {
			if( $client_type == 'client')
				reload_page ( "exp_addclient.php" );
			else
				reload_page ( "exp_addlead.php" );
		} //else
			//reload_page ( "exp_editclient.php?CODE=".$_POST['CLIENT_CODE'] );
	}
}

?>
<div class="container-full theme-showcase" role="main">

<div class="well  well-lg">
<?php

if( isset($_POST) && count($_POST) > 0 && isset($result) && $result == false ) {	// If error occured
	echo "<p><strong>Error:</strong> ".$client_table->error()."</p>";
	echo $client_form->render( $_POST );
} else if( isset($_GET['CODE']) ) {
	$result = $client_table->fetch_rows("CLIENT_CODE = ".$_GET['CODE']);
	if( isset($result) && is_array($result) && count($result) > 0 ) {
		echo $client_form->render( $result[0] );

if( isset($_GET['CODE']) ) {
?>
<!-- Nav tabs -->
<ul class="nav nav-tabs">
  <li class="active"><a href="#contact" data-toggle="tab">Contact Info</a></li>
<?php if( $sts_attachments ) { ?>
  <li><a href="#attach" data-toggle="tab">Attachments</a></li>
<?php } ?>
<?php if( $sts_cms_enabled ) { ?>
  <li><a href="#history" data-toggle="tab">History</a></li>
<?php } ?>
<?php if( $sts_cms_enabled && $status == $client_table->behavior_state['admin'] ) { ?>
  <li><a href="#dup" data-toggle="tab">Duplicates</a></li>
<?php } ?>
<?php if( ! $sts_cms_enabled ) { ?>
  <li><a href="#shipment" data-toggle="tab">Shipments</a></li>
<?php } ?>
<?php if( $sts_email_queueing && $my_session->in_group(EXT_GROUP_ADMIN) ) { ?>
  <li><a href="#email" data-toggle="tab">Email</a></li>
<?php } ?>
</ul>

<!-- Tab panes -->
<div class="tab-content">
  <div role="tabpanel" class="tab-pane active" id="contact">
<?php
$contact_info_table = new sts_contact_info($exspeedite_db, $sts_debug);
//! SCR# 613 - save the client state
if( isset($_GET['CSTATE']) )
	$sts_result_contact_info_edit['filters_html'] = '<input name="CSTATE" type="hidden" value="'.$_GET['CSTATE'].'">';
$rslt = new sts_result( $contact_info_table, "CONTACT_CODE = ".$_GET['CODE']." AND CONTACT_SOURCE = 'client'", $sts_debug );
echo $rslt->render( $sts_result_contact_info_layout, $sts_result_contact_info_edit, '?SOURCE=client&CODE='.$_GET['CODE'].'&PARENT_NAME='.urlencode($result[0]['CLIENT_NAME']) );

if( $sts_attachments ) {
?>
  </div>
  <div role="tabpanel" class="tab-pane" id="attach">
<?php
	
	$attachment_table = sts_attachment::getInstance($exspeedite_db, $sts_debug);
	$rslt2 = new sts_result( $attachment_table, "SOURCE_CODE = ".$_GET['CODE']." AND SOURCE_TYPE = 'client'", $sts_debug );
	echo $rslt2->render( $sts_result_attachment_layout, $sts_result_attachment_edit, '?SOURCE_TYPE=client&SOURCE_CODE='.$_GET['CODE'] );
}

if( $sts_cms_enabled ) {
?>
  </div>
  <div role="tabpanel" class="tab-pane" id="history">
<?php
	// If not in sales group, disable edit history
	if( ! in_group(EXT_GROUP_SALES) )
		unset($sts_result_client_activity_edit['rowbuttons']);
	$cat = sts_client_activity::getInstance($exspeedite_db, $sts_debug);
	$rslt3 = new sts_result( $cat, "CLIENT_CODE = ".$_GET['CODE'], $sts_debug );
	echo $rslt3->render( $sts_result_client_activity_layout, $sts_result_client_activity_edit, '?CLIENT_CODE='.$_GET['CODE'] );
}

//! SCR# 378 - Possible Duplicates
if( $sts_cms_enabled && $status == $client_table->behavior_state['admin'] ) {
?>
  </div>
  <div role="tabpanel" class="tab-pane" id="dup">
<?php
	$cmatch = $client_table->database->get_one_row("
		SELECT CLIENT_NAME, C.ADDRESS, C.CITY, C.STATE, C.ZIP_CODE
		FROM EXP_CLIENT
		LEFT JOIN EXP_CONTACT_INFO C
		ON C.CONTACT_CODE = CLIENT_CODE
		AND C.CONTACT_SOURCE = 'client'
		AND C.CONTACT_TYPE IN ('bill_to', 'company')
		AND EXP_CLIENT.ISDELETED = 0
		WHERE CLIENT_CODE = ".$_GET['CODE']."
		LIMIT 1");
	if( is_array($cmatch) ) {
		$name = empty($cmatch["CLIENT_NAME"]) ? '' : $cmatch["CLIENT_NAME"];
		$address = empty($cmatch["ADDRESS"]) ? '' : $cmatch["ADDRESS"];
		$city = empty($cmatch["CITY"]) ? '' : $cmatch["CITY"];
		$state = empty($cmatch["STATE"]) ? '' : $cmatch["STATE"];
		$zip_code = empty($cmatch["ZIP_CODE"]) ? '' : $cmatch["ZIP_CODE"];

		$client_table_lj = sts_client_lj::getInstance($exspeedite_db, $sts_debug);
		if( ! $sts_export_sage50 ) {
			unset($sts_result_clients_layout['SAGE50_CLIENTID']);
		}


		$rslt4 = new sts_result( $client_table_lj,
			"((COALESCE(CLIENT_NAME, '') != '' AND COALESCE(CITY, '') != '' AND
				COALESCE(STATE, '') != ''
				AND UPPER(TRIM(CLIENT_NAME)) = UPPER(TRIM('".$client_table->real_escape_string($name)."'))
				AND UPPER(TRIM(CITY)) = UPPER(TRIM('".$client_table->real_escape_string($city)."'))
				AND UPPER(TRIM(STATE)) = UPPER(TRIM('".$client_table->real_escape_string($state)."')))
			OR (COALESCE(CLIENT_NAME, '') != '' AND COALESCE(ZIP_CODE, '') != ''
				AND UPPER(TRIM(CLIENT_NAME)) = UPPER(TRIM('".$client_table->real_escape_string($name)."'))
				AND ZIP_CODE = '".$client_table->real_escape_string($zip_code)."')
			OR (COALESCE(ADDRESS, '') != '' AND COALESCE(CITY, '') != ''
				AND UPPER(TRIM(ADDRESS)) = UPPER(TRIM('".$client_table->real_escape_string($address)."'))
				AND UPPER(TRIM(CITY)) = UPPER(TRIM('".$client_table->real_escape_string($city)."')))
				
			OR (UPPER(TRIM(COALESCE(CLIENT_NAME, ''))) = UPPER(TRIM('".$client_table->real_escape_string($name)."'))
				AND UPPER(TRIM(COALESCE(CITY, ''))) = UPPER(TRIM('".$client_table->real_escape_string($city)."'))
				AND UPPER(TRIM(COALESCE(STATE, ''))) = UPPER(TRIM('".$client_table->real_escape_string($state)."')))
			OR (COALESCE(CLIENT_NAME, '') != ''
				AND UPPER(TRIM(CLIENT_NAME)) = UPPER(TRIM('".$client_table->real_escape_string($name)."')))".
		
		
		
		
	/*		"((COALESCE(CLIENT_NAME, '') != '' AND CLIENT_NAME = '".$name."') ".
			"OR (COALESCE(CLIENT_NAME, '') != '' AND COALESCE(CITY, '') != '' AND ".
			"	COALESCE(STATE, '') != '' AND CLIENT_NAME = '".$name."' AND ".
			"	CITY = '".$city."' AND STATE = '".$state."') ".
			"OR (COALESCE(CLIENT_NAME, '') != '' AND COALESCE(ZIP_CODE, '') != '' AND ".
			"	CLIENT_NAME = '".$name."' AND ZIP_CODE = '".$zip."') ".
			"OR (COALESCE(ADDRESS, '') != '' AND COALESCE(CITY, '') != '' AND ".
			"	ADDRESS = '".$address."' AND CITY = '".$city."')) AND ".
	*/
			") AND CLIENT_CODE != ".$_GET['CODE'], $sts_debug );
		echo $rslt4->render( $sts_result_clients_layout, $sts_result_dup_edit, '?CLIENT_CODE='.$_GET['CODE'] );		
	}
	
	

}

if( ! $sts_cms_enabled ) {
?>
  </div>
  <div role="tabpanel" class="tab-pane" id="shipment">
<?php

// !List Shipments Section
echo '<a name="shipments"></a>';
require_once( "include/sts_shipment_class.php" );

$shipment_table = sts_shipment::getInstance($exspeedite_db, $sts_debug);

if( ! isset($_SESSION['CL_SHIPMENT_STATUS']) ) $_SESSION['CL_SHIPMENT_STATUS'] = 'billed';
if( isset($_POST['CL_SHIPMENT_STATUS']) ) $_SESSION['CL_SHIPMENT_STATUS'] = $_POST['CL_SHIPMENT_STATUS'];

$filters_html = '<div class="form-group">
';

$filters_html .= '<select class="form-control input-sm" name="CL_SHIPMENT_STATUS" id="CL_SHIPMENT_STATUS"   onchange="form.submit();">';

// Special cases
$filters_html .= '<option value="eandrd" '.($_SESSION['CL_SHIPMENT_STATUS'] == 'eandrd' ? 'selected' : '').'>Entered or Ready</option>
	';
$filters_html .= '<option value="inprog" '.($_SESSION['CL_SHIPMENT_STATUS'] == 'inprog' ? 'selected' : '').'>In Progress (Dispatched, Picked, Docked)</option>
	';
$filters_html .= '<option value="undel" '.($_SESSION['CL_SHIPMENT_STATUS'] == 'undel' ? 'selected' : '').'>Undelivered (Entered, Ready, Dispatched, Picked, Docked)</option>
	';
$filters_html .= '<option value="completed" '.($_SESSION['CL_SHIPMENT_STATUS'] == 'completed' ? 'selected' : '').'>Completed (Delivered,Approved,Billed)</option>
	';
$filters_html .= '<option value="all" '.($_SESSION['CL_SHIPMENT_STATUS'] == 'all' ? 'selected' : '').'>All shipments (used to take a while)</option>
	';
if( $my_session->in_group(EXT_GROUP_ADMIN) && $my_session->in_group(EXT_GROUP_FINANCE) ) {
	$filters_html .= '<option value="incomp" '.($_SESSION['CL_SHIPMENT_STATUS'] == 'incomp' ? 'selected' : '').'>Billed &amp; Incomplete (custom fields not transferred to QB)</option>
	';
}
$filters_html .= '<option value="billed" '.($_SESSION['CL_SHIPMENT_STATUS'] == 'billed' ? 'selected' : '').'>Billed</option>
	';

$filters_html .= '<option class="select-dash" disabled="disabled">----</option>
';

// Single statuses
foreach( $shipment_table->state_name as $state => $name ) {
	$filters_html .= '<option value="'.$shipment_table->state_behavior[$state].'" '.(isset($shipment_table->behavior_state[$_SESSION['CL_SHIPMENT_STATUS']]) && $shipment_table->behavior_state[$_SESSION['CL_SHIPMENT_STATUS']] == $state ? 'selected' : '').'>'.$name.'</option>
	';
}
$filters_html .= '</select>';

$filters_html .= '</div>';


if( $_SESSION['CL_SHIPMENT_STATUS'] == 'all' ) {
	$match = "COALESCE(EXP_SHIPMENT.BILLTO_NAME,'') <> ''";
} else if( $_SESSION['CL_SHIPMENT_STATUS'] == 'eandrd' ) {
	$states = array($shipment_table->behavior_state['entry'],
		$shipment_table->behavior_state['assign']);
	$match = "COALESCE(EXP_SHIPMENT.BILLTO_NAME,'') <> '' AND EXP_SHIPMENT.CURRENT_STATUS IN (".implode(', ', $states).") AND LOAD_CODE = 0";
} else if( $_SESSION['CL_SHIPMENT_STATUS'] == 'inprog' ) {
	$states = array($shipment_table->behavior_state['dispatch'],
		$shipment_table->behavior_state['picked'],
		$shipment_table->behavior_state['docked']);
	$match = 'EXP_SHIPMENT.CURRENT_STATUS IN ('.implode(', ', $states).')';
} else if( $_SESSION['CL_SHIPMENT_STATUS'] == 'undel' ) {
	$states = array($shipment_table->behavior_state['entry'],
		$shipment_table->behavior_state['assign'],
		$shipment_table->behavior_state['dispatch'],
		$shipment_table->behavior_state['picked'],
		$shipment_table->behavior_state['docked']);
	$match = 'EXP_SHIPMENT.CURRENT_STATUS IN ('.implode(', ', $states).')';
} else if( $_SESSION['CL_SHIPMENT_STATUS'] == 'completed' ) {
	$states = array($shipment_table->behavior_state['dropped'],
		$shipment_table->behavior_state['approved']);
	$match = 'EXP_SHIPMENT.CURRENT_STATUS IN ('.implode(', ', $states).')';
} else if( $_SESSION['CL_SHIPMENT_STATUS'] == 'incomp' && $my_session->in_group(EXT_GROUP_FINANCE) ) {
	$match = 'EXP_SHIPMENT.BILLING_STATUS = '.$shipment_table->billing_behavior_state['billed'].
		" AND COALESCE(quickbooks_dataext_retries,0) <> -1";
} else if( $_SESSION['CL_SHIPMENT_STATUS'] == 'billed' ) {
	$match = 'EXP_SHIPMENT.BILLING_STATUS = '.$shipment_table->billing_behavior_state['billed'];
} else {
	$match = 'EXP_SHIPMENT.CURRENT_STATUS = '.$shipment_table->behavior_state[$_SESSION['CL_SHIPMENT_STATUS']];
}
//! Restrict list to this client
$match .= ($match <> '' ? ' and ' : '') . 'BILLTO_CLIENT_CODE = '.$_GET['CODE'];

$sts_result_shipments_edit['filters_html'] = $filters_html;

$rslt = new sts_result( $shipment_table, $match, $sts_debug );

unset($sts_result_shipments_edit['add']);		// Remove button
unset($sts_result_shipments_edit['cancel']);	// Remove button
$sts_result_shipments_edit['form_action'] = $_SERVER['REQUEST_URI'];	// Form action link
if( strpos($sts_result_shipments_edit['form_action'], '#shipments') === false )
	$sts_result_shipments_edit['form_action'] .= '#shipments';
	
if( isset($_SESSION['CL_SHIPMENT_STATUS']) &&
	isset($shipment_table->state_behavior[$_SESSION['CL_SHIPMENT_STATUS']]) &&
	in_array($shipment_table->state_behavior[$_SESSION['CL_SHIPMENT_STATUS']],
		array('picked','dropped','approved','billed','cancel')) ) {
	unset($sts_result_shipments_edit['add']);
	$sts_result_shipments_edit['rowbuttons'] = array(
		array( 'url' => 'exp_addshipment.php?CODE=', 'key' => 'SHIPMENT_CODE', 'label' => 'SHIPMENT_CODE', 'tip' => 'Edit shipment ', 'icon' => 'glyphicon glyphicon-edit' ),
		array( 'url' => 'exp_dupshipment.php?CODE=', 'key' => 'SHIPMENT_CODE', 'label' => 'SHIPMENT_CODE', 'tip' => 'Duplicate shipment ', 'icon' => 'glyphicon glyphicon-repeat' ),
 		array( 'url' => 'exp_clientpay.php?id=', 'key' => 'SHIPMENT_CODE', 'label' => 'SHIPMENT_CODE', 'tip' => 'Client billing ', 'icon' => 'glyphicon glyphicon-th-list', 'restrict' => EXT_GROUP_BILLING )
	);
	
	if( $shipment_table->state_behavior[$_SESSION['CL_SHIPMENT_STATUS']] == 'billed' )
		$sts_result_shipments_edit['rowbuttons'][] = 
			array( 'url' => 'exp_shipment_state.php?BILLING=true&PW=Soyo&STATE=unapproved&CODE=', 'key' => 'SHIPMENT_CODE',
			'label' => 'SHIPMENT_CODE', 'tip' => 'Unapprove shipment ', 'tip2' => 'Make sure to delete the  invoice from QuickBooks before you approve again.',
			'icon' => 'glyphicon glyphicon-arrow-left', 'confirm' => 'yes', 'restrict' => EXT_GROUP_FINANCE );

	//$sts_result_shipments_layout['quickbooks_listid_customer'] = array( 'label' => 'QB&nbsp;Billto', 'format' => 'text' );
	//$sts_result_shipments_layout['quickbooks_txnid_invoice'] = array( 'label' => 'QB&nbsp;TxnID', 'format' => 'text' );
}

if( ! $sts_pipco_fields ) {
	unset($sts_result_shipments_layout['FS_NUMBER'], $sts_result_shipments_layout['SYNERGY_IMPORT']);
}

if( ! $sts_po_fields ) {
	unset($sts_result_shipments_layout['PO_NUMBER']);
}
if( ! $sts_refnum_fields ) {
	unset($sts_result_loads_lj_layout['REF_NUMBER']);
}


echo $rslt->render( $sts_result_shipments_layout, $sts_result_shipments_edit, false, false );
}
if( $sts_email_queueing && $my_session->in_group(EXT_GROUP_ADMIN) ) {
?>
  </div>
	<div role="tabpanel" class="tab-pane" id="email">
<?php
	require_once( "include/sts_email_class.php" );
	
	$queue = sts_email_queue::getInstance($exspeedite_db, $sts_debug);
	$sts_result_queue_edit['title'] .= $queue->shipment_emails( $_GET['CODE'] );
	
	$rslt5 = new sts_result( $queue, "(SOURCE_TYPE='client' AND (SELECT CLIENT_CODE
        FROM EXP_BATCH_INVOICE WHERE BATCH_CODE = SOURCE_CODE) =".$_GET['CODE'].")
		OR (EMAIL_TYPE='attachment' AND (SELECT A.SOURCE_CODE
		FROM EXP_ATTACHMENT A
		WHERE A.ATTACHMENT_CODE = EXP_EMAIL_QUEUE.SOURCE_CODE
		AND A.SOURCE_TYPE = 'client') = ".$_GET['CODE']." )", $sts_debug );
	echo $rslt5->render( $sts_result_queue_layout, $sts_result_queue_edit, '?SOURCE_TYPE=client&SOURCE_CODE='.$_GET['CODE'] );
}
?>
   </div>
</div>
<?php
}
	} else {
	$back = isset($_SERVER["HTTP_REFERER"]) ? $_SERVER["HTTP_REFERER"] : 'index.php';
		
		echo '<h2 id="NOTFOUND">Client #'.$_GET['CODE'].' Not Found <a class="btn btn-sm btn-default" id="no_code" href="exp_listclient.php"><span class="glyphicon glyphicon-arrow-left"></span> Get me out of here</a></h2>
				<p>The edit client screen tried to edit a client code that does not exist.</p>
				<p>If this happens often, make a note of the client code and contact support.</p> 
			';
	}

}
} else {
	$back = isset($_SERVER["HTTP_REFERER"]) ? $_SERVER["HTTP_REFERER"] : 'exp_listclient.php';
		
	echo '<div class="container-full theme-showcase" role="main">
			<div class="well  well-lg">
				<h2 id="MISSING">No client specified <a class="btn btn-sm btn-default" id="no_code" href="'.$back.'"><span class="glyphicon glyphicon-arrow-left"></span> Get me out of here</a></h2>
				<p>The edit client screen is missing a client code.</p>
				<p>If this happens often, make a note of the page you were on that got you here and contact support.</p> 
			</div>
		</div>';
}
?>
</div>
</div>

	<div class="modal fade fuzzy bs-example-modal-sm" tabindex="-1" role="dialog" aria-labelledby="mySmallModalLabel" aria-hidden="true" id="editclient_modal">
	  <div class="modal-dialog">
		<div class="modal-content">
		<div class="modal-header">
			<h4 class="modal-title" id="myModalLabel"><span class="text-success"><strong>Saving...</strong></span></h4>
		</div>
		<div class="modal-body">
			<p class="text-center"><img src="images/loading.gif" alt="loading" width="125" height="125" /></p>
		</div>
		</div>
		</div>
	</div>


	<script language="JavaScript" type="text/javascript"><!--
		
		// Sets the client info
		$(document).ready( function () {

			$('a[data-toggle="tab"]').on( 'shown.bs.tab', function (e) {
				$.fn.dataTable.tables( {visible: true, api: true} ).columns.adjust();
			} );

			var saveadd_clicked = false;
			$("#saveadd").click(function() {
				saveadd_clicked = true;
			});

			$( "#editclient" ).submit(function( event ) {
				//console.log( saveadd_clicked );
				if( saveadd_clicked ) return;
				
				event.preventDefault();  //prevent form from submitting
				$('#editclient_modal').modal({
					container: 'body'
				});
				var data = $("#editclient :input").serializeArray();
				
				//console.log(data);
				$.post("exp_editclient.php", data, function( data ) {
					//alert('Saved changes');

					$('#editclient_modal').modal('hide');
					window.editclient_HAS_CHANGED = false;	// depreciated
					//$('a').off('click.editclient');
					if( $( "#editclient" ).data('reload') == true )
						window.location.href = window.location.href;
				});
			});
			
			$('select#SALES_PERSON').on('focusin', function(){
			    $(this).data('val', $(this).val());
			});
			
			//! If in entry state, change sales person trigger redraw.
			$('select#SALES_PERSON').change(function () {
			    var prev = $(this).data('val');
			    var current = $(this).val();
			    //console.log(prev, current, $('input#CURRENT_STATUS').val());
			    var states = [ '<?php echo $client_table->behavior_state['entry']; ?>',
			    	'<?php echo $client_table->behavior_state['admin']; ?>',
			    	'<?php echo $client_table->behavior_state['dead']; ?>',
			    	'<?php echo $client_table->behavior_state['admin']; ?>'];
			    $(this).data('val', $(this).val());
    			if( jQuery.inArray( $('input#CURRENT_STATUS').val(), states) >= 0 &&
			    	((prev == 'NULL' && current != 'NULL') || (prev != 'NULL' && current == 'NULL'))
			    	) {
			    	$( "#editclient" ).data('reload', true);
				    $( "#editclient" ).submit();
				    console.log(window.location, window.location.href);
			    }
			});

			$('#EXP_CONTACT_INFO').dataTable({
		        //"bLengthChange": false,
		        "bFilter": true,
		        "bSort": true,
		        "bInfo": false,
				//"bAutoWidth": false,
				//"bProcessing": true, 
				"sScrollX": "100%",
				"sScrollY": "300px",
				//"sScrollXInner": "120%",
		        "lengthMenu": [<?php echo isset($sts_length_menu) ? $sts_length_menu : '25, 50, 100, 250'; ?>],
				"bPaginate": true,
				"bScrollCollapse": false,
				"bSortClasses": false		
			});

			$('#EXP_ATTACHMENT, #EXP_CLIENT_ACTIVITY, #EXP_CLIENT, #EXP_EMAIL_QUEUE').dataTable({
		        //"bLengthChange": false,
		        "bFilter": false,
		        "bSort": false,
		        "bInfo": false,
				"bAutoWidth": false,
				//"bProcessing": true, 
				"sScrollX": "100%",
				"sScrollY": "300px",
				"sScrollXInner": "120%",
				"bPaginate": true,
				"bScrollCollapse": false,
				"bSortClasses": false		
			});

			function update_warnings() {
				var got_shipper = false,
					got_consignee = false,
					got_dock = false,
					got_bill_to = false,
					warning_str = '';

				$('.TAG_CONTACT_TYPE').each(function(i){
					switch( $(this).text()  ) {
						case 'shipper':
							got_shipper = true;
							break;
						case 'consignee':
							got_consignee = true;
							break;
						case 'dock':
							got_dock = true;
							break;
						case 'bill_to':
							got_bill_to = true;
							break;
						default:
							break;
					}
				});
				
				//console.log(got_shipper, got_consignee, got_bill_to);
				
				if( $('input#SHIPPER').is(':checked') && ! got_shipper )
					warning_str += 'Shipper selected, you need to add a contact info of type <strong>shipper</strong>.<br>';
				if( $('input#CONSIGNEE').is(':checked') && ! got_consignee )
					warning_str += 'Consignee selected, you need to add a contact info of type <strong>consignee</strong>.<br>';
				if( $('input#DOCK').is(':checked') && ! got_dock )
					warning_str += 'Dock selected, you need to add a contact info of type <strong>dock</strong>.<br>';
				if( $('input#BILL_TO').is(':checked') && ! got_bill_to )
					warning_str += 'Bill-to selected, you need to add a contact info of type <strong>bill_to</strong>.<br>';
					
				if( warning_str == '' )
					$('#CLIENT_WARNINGS').replaceWith('<div id="CLIENT_WARNINGS"></div>');
				else
					$('#CLIENT_WARNINGS').replaceWith('<div id="CLIENT_WARNINGS" class="form-group alert alert-warning" role="alert"><strong><span class="glyphicon glyphicon-warning-sign"></span> Warning:</strong> ' + warning_str + '</div>');
			}
			
			$('input#SHIPPER').change(function () {
			    update_warnings();
			 });
			$('input#CONSIGNEE').change(function () {
			    update_warnings();
			 });
			$('input#DOCK').change(function () {
			    update_warnings();
			 });
			$('input#BILL_TO').change(function () {
			    update_warnings();
			    $( "#editclient" ).data('reload', true);	// Reload on next save
			    $( "#editclient" ).submit();
			 });
			 
			update_warnings();

			//! SCR# 426 - update to CLIENT_GROUP_CODE
			$('#CLIENT_GROUP').on('typeahead:selected', function(obj, datum, name) {
				$('input#CLIENT_GROUP_CODE').val(datum.CLIENT_CODE).change();
				//console.log('client group ',$('input#CLIENT_GROUP_CODE').val(), datum.CLIENT_CODE);
			});
			 
			$('#CLIENT_GROUP').on('typeahead:idle', function(obj, datum, name) {
				if( $('#CLIENT_GROUP').val() == '' )
					$('input#CLIENT_GROUP_CODE').val('').change();
				//console.log('client group3 ',$('input#CLIENT_GROUP_CODE').val());
			});
			 
			var opts = {
		        //"bLengthChange": false,
		        "bFilter": true,
		        stateSave: true,
		        "stateLoadParams": function (settings, data) {
			        data.order = [[ <?php echo (isset($_SESSION['CL_SHIPMENT_STATUS']) && $_SESSION['CL_SHIPMENT_STATUS'] == 'completed' ? '7, "desc"' : '6, "asc"'); ?> ]];
			    },
		        "bSort": true,
		        "bInfo": true,
				//"bAutoWidth": false,
				//"bProcessing": true, 
				"sScrollX": "100%",
				"sScrollY": "400px",
				//"sScrollXInner": "120%",
		        "lengthMenu": [<?php echo isset($sts_length_menu) ? $sts_length_menu : '25, 50, 100, 250'; ?>],
				"bPaginate": true,
				"bScrollCollapse": true,
				"bSortClasses": false,
				"order": [[ <?php echo (isset($_SESSION['CL_SHIPMENT_STATUS']) && $_SESSION['CL_SHIPMENT_STATUS'] == 'completed' ? '7, "desc"' : '6, "asc"'); ?> ]],
				"processing": true,
				"serverSide": true,
				//"dom": "frtiS",
				"deferRender": true,
				"ajax": {
					"url": "exp_listshipmentajax.php",
					"data": function( d ) {
						d.match = encodeURIComponent("<?php echo (isset($match) ? $match : ''); ?>");
					}
				},
				"columns": [
					{ "searchable": false, "orderable": false },
					<?php
						foreach( $sts_result_shipments_layout as $key => $row ) {
							if( $row["format"] <> 'hidden')
								echo '{ "data": "'.$key.'", "searchable": true, "orderable": true },
						';
						}
					?>
				]
			};
			
			var myTable = $('#EXP_SHIPMENT').DataTable(opts);
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
			
			if (/PhantomJS/.test(window.navigator.userAgent)) {
				console.log("exp_editclient.php: PhantomJS environment detected.");
			} else {
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
			}

			function update_reason() {
				if( $('#CDN_TAX_EXEMPT').prop('checked') ) {
					$('#EXEMPT_REASON').prop('hidden',false);
				} else {
					$('#EXEMPT_REASON').prop('hidden', 'hidden');					
				}
			}

			$('#CDN_TAX_EXEMPT').change(function () {
				update_reason();
			});
			
			function update_tax() {
				if( $('#BILL_TO').prop('checked') ) {
					$('#CDN_TAX').prop('hidden',false);
					update_reason();
				} else {
					$('#CDN_TAX').prop('hidden', 'hidden');					
				}
			}

			function update_notes() {
				if( $('#SHIPPER').prop('checked') ) {
					$('#snotes').prop('hidden',false);
				} else {
					$('#snotes').prop('hidden', 'hidden');					
				}
				if( $('#CONSIGNEE').prop('checked') ) {
					$('#cnotes').prop('hidden',false);
				} else {
					$('#cnotes').prop('hidden', 'hidden');					
				}
				if( $('#BILL_TO').prop('checked') ) {
					$('#bnotes').prop('hidden',false);
				} else {
					$('#bnotes').prop('hidden', 'hidden');					
				}
			}

			$('#SHIPPER, #CONSIGNEE, #BILL_TO').change(function () {
				update_tax();
				update_notes();
			});
			
			update_tax();
			update_notes();

			/*
			// store the currently selected tab in the hash value
			$("ul.nav-tabs > li > a").on("shown.bs.tab", function(e) {
			  var id = $(e.target).attr("href").substr(1);
			  console.log('id = ',id);
			  window.location.hash = id;
			});
			
			// on load of the page: switch to the currently selected tab
			var hash = window.location.hash;
			console.log('hash = ',hash);
			$('#myTab a[href="' + hash + '"]').tab('show');
			*/

			$('a[data-toggle="tab"]').on("shown.bs.tab", function (e) {
			    var id = $(e.target).attr("href");
			    localStorage.setItem('ecselectedTab', id)
			});
			
			var selectedTab = localStorage.getItem('ecselectedTab');
			if (selectedTab != null) {
			    $('a[data-toggle="tab"][href="' + selectedTab + '"]').tab('show');
			}

			if( $('#CURRENT_STATUS_STATIC').text() == 'Probable Duplicate' ) {
				$('#CURRENT_STATUS_STATIC').replaceWith($('<h4 id="CURRENT_STATUS_STATIC" class="bg-warning text-danger tighter2" style="margin: 0;"><img src="images/copy_icon.png" height="40"> Probable Duplicate</h4>'));
			}
			else if( $('#CURRENT_STATUS_STATIC').text() == 'Dead' ) {
				$('#CURRENT_STATUS_STATIC').replaceWith($('<h4 id="CURRENT_STATUS_STATIC" class="bg-warning text-danger tighter2" style="margin: 0;"><img src="images/skull-crossbones.png" height="40"> Dead</h4>'));
			}
			
		});
	//--></script>


<?php

require_once( "include/footer_inc.php" );
?>

