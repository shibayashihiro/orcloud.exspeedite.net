<?php 

// $Id: exp_addshipment.php 5132 2023-06-13 19:51:03Z dev $
// Add/Edit Shipment

// Set flag that this is a parent file
define( '_STS_INCLUDE', 1 );

set_time_limit(0);
ini_set('memory_limit', '1024M');

// Setup Session
require_once( "include/sts_session_setup.php" );

require_once( "include/sts_config.php" );
$sts_debug = (isset($_GET['debug'])  || isset($_POST['debug'])) && in_group( EXT_GROUP_DEBUG );
//$sts_debug = isset($_POST) && count($_POST) > 0 ;
require_once( "include/sts_session_class.php" );

$my_session = sts_session::getInstance( $exspeedite_db, $sts_debug );
$my_session->access_check( $sts_table_access[SHIPMENT_TABLE] );	// Make sure we should be here

ob_implicit_flush(true);

if (ob_get_level() == 0) ob_start();

$sts_subtitle = "Add Shipment";
require_once( "include/header_inc.php" );
require_once( "include/navbar_inc.php" );

echo '<div id="loading1"><h3 class="text-center"><img src="images/loading.gif" alt="loading" width="125" height="125" /><br>Loading shipment...</h3></div>';
ob_flush(); flush();

require_once( "include/sts_form_class.php" );
require_once( "include/sts_shipment_class.php" );
require_once( "include/sts_setting_class.php" );
require_once( "include/sts_business_code_class.php" );
require_once( "include/sts_attachment_class.php" );
require_once( "include/sts_item_list_class.php" );

if( $sts_debug ) echo "<h2>".__FILE__.": START</h2>";

$bc_table = sts_business_code::getInstance($exspeedite_db, $sts_debug);

$setting_table = sts_setting::getInstance($exspeedite_db, $sts_debug);
$client_label = ($setting_table->get( 'option', 'CLIENT_LABEL' ) == 'true');
$edit_unapprove = ($setting_table->get( 'option', 'EDIT_UNAPPROVED' ) == 'true');
$sts_export_qb = $setting_table->get( 'api', 'EXPORT_QUICKBOOKS' ) == 'true';
$sts_export_sage50 = $setting_table->get( 'api', 'EXPORT_SAGE50_CSV' ) == 'true';
$sts_attachments = $setting_table->get( 'option', 'ATTACHMENTS_ENABLED' ) == 'true';
$sts_po_rw = $setting_table->get( 'option', 'PO_NUMBERS_WRITABLE' ) == 'true';
$sts_find_carrier = $setting_table->get( 'option', 'FIND_CARRIER' ) == 'true';

//! SCR# 852 - Containers Feature
$sts_containers = $setting_table->get( 'option', 'CONTAINERS' ) == 'true';
if( ! $sts_containers ) {
	$match = preg_quote('<!-- INTERMODAL1 -->').'(.*)'.preg_quote('<!-- INTERMODAL2 -->');
	$sts_form_addshipment_form['layout'] = preg_replace('/'.$match.'/s', '',
		$sts_form_addshipment_form['layout'], 1);	
}

//! SCR# 712 - Email queue implementation
$sts_email_queueing = $setting_table->get( 'option', 'EMAIL_QUEUEING' ) == 'true';

$shipment_table = sts_shipment::getInstance($exspeedite_db, $sts_debug);
$item_list_table = sts_item_list::getInstance($exspeedite_db, $sts_debug);

$accounting = $sts_export_qb ? 'QuickBooks' : ($sts_export_sage50 ? 'Sage 50' : 'Accounting');

//! Initialize variables
$shipment_found = false;
$status = '';
$bstatus = '';

if( $client_label ) {
	$sts_form_add_shipment_fields['BILLTO_NAME']['CLIENT_LABEL'] = true;
	$sts_form_add_shipment_fields['SHIPPER_NAME']['CLIENT_LABEL'] = true;
	$sts_form_add_shipment_fields['CONS_NAME']['CLIENT_LABEL'] = true;
}

$preload_time = ($setting_table->get( 'option', 'PRELOAD_SHIPMENT_TIME' ) == 'true');
if( $preload_time ) {
	$sts_form_add_shipment_fields['PICKUP_TIME1']['value'] = date("Hi");
	$sts_form_add_shipment_fields['DELIVER_TIME1']['value'] = date("Hi");
}

//! SCR# 818 - restrict salesperson
if( $shipment_table->restrict_salesperson() &&
	! $my_session->in_group(EXT_GROUP_ADMIN, EXT_GROUP_MANAGER) ) {
	$sts_form_add_shipment_fields['SALES_PERSON']['extras'] = 'disabled readonly';
}

if( $sts_debug ) echo "<h2>".__FILE__.": Check for multi-company</h2>";

//! Check for multi-company
$multi_company = ($setting_table->get("option", "MULTI_COMPANY") == 'true');
if( ! $multi_company ) {
	$match = preg_quote('<!-- CC01 -->').'(.*)'.preg_quote('<!-- CC02 -->');
	$sts_form_addshipment_form['layout'] = preg_replace('/'.$match.'/s', '',
		$sts_form_addshipment_form['layout'], 1);	
}


if( $sts_debug ) echo "<h2>".__FILE__.": Check QUICKBOOKS</h2>";

if( $sts_export_qb ) {
	//! This removes some fields not used for QuickBooks Online
	$sts_qb_online = $setting_table->get( 'api', 'QUICKBOOKS_ONLINE' ) == 'true';
	if( $sts_qb_online ) {
		$match = preg_quote('<!-- DATAEXT1 -->').'(.*)'.preg_quote('<!-- DATAEXT2 -->');
		$sts_form_addshipment_form['layout'] = preg_replace('/'.$match.'/s', '',
			$sts_form_addshipment_form['layout'], 1);	
	}
} else {
	//! Remove all quickbooks fields
	$match = preg_quote('<!-- QUICKBOOKS1 -->').'(.*)'.preg_quote('<!-- QUICKBOOKS2 -->');
	$sts_form_addshipment_form['layout'] = preg_replace('/'.$match.'/s', '',
		$sts_form_addshipment_form['layout'], 1);	
}

if( ! $sts_export_sage50 ) {
	//! Remove all quickbooks fields
	$match = preg_quote('<!-- SAGE501 -->').'(.*)'.preg_quote('<!-- SAGE502 -->');
	$sts_form_addshipment_form['layout'] = preg_replace('/'.$match.'/s', '',
		$sts_form_addshipment_form['layout'], 1);	
}

//! This removes some fields speific to Pipco
$sts_intermodal_fields = $setting_table->get( 'option', 'INTERMODAL_FIELDS' ) == 'true';
if( ! $sts_intermodal_fields ) {
	$match = preg_quote('<!-- PIPCO1 -->').'(.*)'.preg_quote('<!-- PIPCO2 -->');
	$sts_form_addshipment_form['layout'] = preg_replace('/'.$match.'/s', '',
		$sts_form_addshipment_form['layout'], 1);	
}

//! SCR# 354 - mark PO fields R/W
if( $sts_po_rw ) {
	$sts_form_add_shipment_fields['PO_NUMBER']['rw'] = true;
	$sts_form_add_shipment_fields['PO_NUMBER2']['rw'] = true;
	$sts_form_add_shipment_fields['PO_NUMBER3']['rw'] = true;
	$sts_form_add_shipment_fields['PO_NUMBER4']['rw'] = true;
	$sts_form_add_shipment_fields['PO_NUMBER5']['rw'] = true;
}

//! If no business codes, don't show the menu
if( $bc_table->is_empty() ) {
	$match = preg_quote('<!-- BUSINESS_CODE1 -->').'(.*)'.preg_quote('<!-- BUSINESS_CODE2 -->');
	$sts_form_addshipment_form['layout'] = preg_replace('/'.$match.'/s', '',
		$sts_form_addshipment_form['layout'], 1);	
}

//! Hide PO# fields
$sts_po_fields = $setting_table->get( 'option', 'PO_FIELDS' ) == 'true';
if( ! $sts_po_fields ) {
	$match = preg_quote('<!-- PO1 -->').'(.*)'.preg_quote('<!-- PO2 -->');
	$sts_form_addshipment_form['layout'] = preg_replace('/'.$match.'/s', '',
		$sts_form_addshipment_form['layout'], 1);	
}

//! Configurable lable for CONSOLIDATE_NUM field
$sts_form_add_shipment_fields['CONSOLIDATE_NUM']['label'] =
	$setting_table->get( 'option', 'CONSOLIDATE_NUM' );

if( $sts_debug ) echo "<h2>".__FILE__.": Create Shipment Form</h2>";

$shipment_form = new sts_form( $sts_form_addshipment_form, $sts_form_add_shipment_fields, $shipment_table, $sts_debug);

if( isset($_POST) && count($_POST) > 0 ) {		// Process completed form
	if( $sts_debug ) echo "<h2>".__FILE__.": Process completed form</h2>";

	$result = $shipment_form->process_edit_form();
	
	//! SCR# 531 - log event
	$shipment_table->log_event( 'exp_addshipment: SAVED shipment '.$_POST[$shipment_table->primary_key].
		' result = '.($result ? 'true' : 'false').
		' by = '.$_SESSION['EXT_USERNAME'], EXT_ERROR_DEBUG );

	if( $result ) {
		$item_list_table->process_equipment_checkboxes('shipment', $_POST[$shipment_table->primary_key]);
		$shipment_table->check_new_client( $_POST[$shipment_table->primary_key] );
		if( $sts_debug ) die; // So we can see the results
		if( isset($_POST["saveadd"]) )
			reload_page ( "exp_addshipment.php" );
		else
			reload_page ( "exp_addshipment.php?CODE=".$_POST[$shipment_table->primary_key] );
	}
}

if( $sts_debug ) echo "<h2>".__FILE__.": before container-full</h2>";

?>
<div class="container-full theme-showcase" role="main">

<div class="well  well-md">
<?php
$detail_ro = '';
if( isset($_POST) && count($_POST) > 0 && $result == false ) {	// If error occured
	if( $sts_debug ) echo "<h2>".__FILE__.": If error occured</h2>";
	echo "<p><strong>Error:</strong> ".$shipment_table->error()."</p>";
	echo $shipment_form->render( $_POST );
} else if( isset($_GET['CODE']) ) {
	
	if( $sts_debug ) echo "<h2>".__FILE__.": Process shipment ".$_GET['CODE']."</h2>";
	// Fix the TOTAL_CHARGES column if needed.
	$exspeedite_db->get_one_row("
		UPDATE EXP_CLIENT_BILLING, EXP_SHIPMENT
		SET TOTAL_CHARGES = COALESCE(TOTAL, 0)
		WHERE SHIPMENT_ID = ".$_GET['CODE']."
		AND SHIPMENT_CODE = SHIPMENT_ID
		AND TOTAL_CHARGES != COALESCE(TOTAL, 0)
	");
	
	// So that it does not fill in fields with default values
	unset($sts_form_add_shipment_fields['PICKUP_DATE']['value']);
	unset($sts_form_add_shipment_fields['PICKUP_TIME1']['value']);
	unset($sts_form_add_shipment_fields['DELIVER_DATE']['value']);
	unset($sts_form_add_shipment_fields['DELIVER_TIME1']['value']);

	if( $sts_debug ) echo "<h2>".__FILE__.": Check user has access</h2>";
	//! Check user has access to shipment
	
	//! SCR# 371 - check if it is an office number
	$result = $shipment_table->fetch_rows( (is_numeric($_GET['CODE']) ? $shipment_table->primary_key." = ".$_GET['CODE'].
	($multi_company && ! $my_session->in_group(EXT_GROUP_ADMIN) ?
		" AND OFFICE_CODE IN (SELECT OFFICE_CODE FROM EXP_USER_OFFICE
		WHERE USER_CODE = ".$_SESSION["EXT_USER_CODE"].")" : "") :
		"SS_NUMBER = '".$_GET['CODE']."'" ),
	 "*, GET_TIMEZONE( SHIPPER_ZIP ) AS SHIPPER_TZONE, GET_TIMEZONE( CONS_ZIP ) AS CONS_TZONE");

	if( isset($result) && is_array($result) && count($result) == 1 ) {
		
		if( $sts_debug ) echo "<h2>".__FILE__.": add links to consolidated shipments</h2>";
		//! SCR# 454 - add links to consolidated shipments
		$sts_form_addshipment_form['layout'] = str_replace('<!-- CONS_SHIPMENTS -->',
			$shipment_table->consolidated_shipments($_GET['CODE']),
			$sts_form_addshipment_form['layout']);

		//! Display the 204 information if it exists
		if( isset($result[0]['EDI_204_GS04_OFFERED']) && $result[0]['EDI_204_GS04_OFFERED'] <> '' ) {

			$check_edi = $shipment_table->database->get_multiple_rows("select EDI_CODE
				FROM EXP_EDI
				WHERE EDI_204_PRIMARY = ".$result[0]['EDI_204_PRIMARY']);

			if( is_array($check_edi) && count($check_edi) > 0 &&
				$my_session->in_group(EXT_GROUP_ADMIN) )
				$info_link = '<br><span class="glyphicon glyphicon-transfer"></span> <a href="exp_view_edi.php?pw=ChessClub&code='.$result[0]['EDI_204_PRIMARY'].'" target="_blank">EDI Information</a>';
			else
				$info_link = '';
			$check_status = $shipment_table->state_behavior[$result[0]['CURRENT_STATUS']];
			if( $result[0]['EDI_990_STATUS'] == 'Pending' ) {
				if( $check_status == 'imported') {
					$sts_form_addshipment_204 = str_replace('<!-- 990_INFO_HERE -->', $sts_form_addshipment_990_info.$info_link, $sts_form_addshipment_204);
				} else {
					$load_code = $result[0]['LOAD_CODE'];
					$msg = 'Accept or Decline the whole <a class="btn btn-sm btn-danger" href="exp_viewload.php?CODE='.$load_code.'">load '.$load_code.'</a> rather than this shipment.';
					$sts_form_addshipment_204 = str_replace('<!-- 990_INFO_HERE -->', $msg.$info_link, $sts_form_addshipment_204);					
				}
			} else {
				$sts_form_addshipment_204 = str_replace('<!-- 990_INFO_HERE -->', $info_link, $sts_form_addshipment_204);					
				
			}
			
			$shipments = $shipment_table->fetch_rows("EDI_204_PRIMARY = ".$result[0]['EDI_204_PRIMARY']);
			$edi_shipments = array();
			if( is_array($shipments)) {
				foreach($shipments as $s) { 
					$edi_shipments[] = '<a href="exp_addshipment.php?CODE='.$s["SHIPMENT_CODE"].'" class="alert-link">'.$s["SHIPMENT_CODE"].'</a>';
				}
				$msg = plural(count($edi_shipments), 'Shipment').' ('.implode(', ', $edi_shipments).')';
				$sts_form_addshipment_204 = str_replace('##SHIPMENTS##', $msg, $sts_form_addshipment_204);
			} else
				$sts_form_addshipment_204 = str_replace('##SHIPMENTS##', '', $sts_form_addshipment_204);


			$sts_form_addshipment_form['layout'] = str_replace('<!-- 204_INFO_HERE -->', $sts_form_addshipment_204, $sts_form_addshipment_form['layout']);
			
			//! Display alternate shipment timestamps
			if( ! empty($result[0]['PICKUP_DATE2']) ) {
				$match = preg_quote('<!-- 204_PICKUP1 -->').'(.*)'.preg_quote('<!-- 204_PICKUP2 -->');
				
				$sts_form_addshipment_204_pickup = '<div class="col-sm-1"><label class="control-label text-right">Pickup</label></div>
				<div class="col-sm-7"><label class="control-label">between '.
					date("m/d/Y", strtotime($result[0]['PICKUP_DATE'])).' '.
					$result[0]['PICKUP_TIME1'].'&nbsp;&nbsp;&nbsp;and&nbsp;&nbsp;&nbsp;'.
					date("m/d/Y", strtotime($result[0]['PICKUP_DATE2'])).' '.
					$result[0]['PICKUP_TIME2'].'</label></div>';
				
				$sts_form_addshipment_form['layout'] = preg_replace('/'.$match.'/s',
					$sts_form_addshipment_204_pickup,
					$sts_form_addshipment_form['layout'], 1);
			}
			
			if( ! empty($result[0]['DELIVER_DATE2']) ) {
				$match = preg_quote('<!-- 204_DELIVER1 -->').'(.*)'.preg_quote('<!-- 204_DELIVER2 -->');
				
				$sts_form_addshipment_204_deliver = '<div class="col-sm-1"><label class="control-label text-right">Pickup</label></div>
				<div class="col-sm-7"><label class="control-label">between '.
					date("m/d/Y", strtotime($result[0]['DELIVER_DATE'])).' '.
					$result[0]['DELIVER_TIME1'].'&nbsp;&nbsp;&nbsp;and&nbsp;&nbsp;&nbsp;'.
					date("m/d/Y", strtotime($result[0]['DELIVER_DATE2'])).' '.
					$result[0]['DELIVER_TIME2'].'</label></div>';
				
				$sts_form_addshipment_form['layout'] = preg_replace('/'.$match.'/s',
					$sts_form_addshipment_204_deliver,
					$sts_form_addshipment_form['layout'], 1);
			}
			
			
			if( $sts_debug ) echo "<h2>".__FILE__.": new form</h2>";
			
			$shipment_form = new sts_form( $sts_form_addshipment_form, $sts_form_add_shipment_fields, $shipment_table, $sts_debug);
			
			$billing_link = 'exp_clientpay.php?id=%EDI_204_PRIMARY%';
			$unapprove_code = $result[0]['EDI_204_PRIMARY'];
			$billing_code = $unapprove_code;
		} else {
			$billing_link = 'exp_clientpay.php?id=%SHIPMENT_CODE%';
			$billing_code = $_GET['CODE'];
			$unapprove_code = is_null($result[0]['CONSOLIDATE_NUM']) ?
				$_GET['CODE'] :$result[0]['CONSOLIDATE_NUM'];
			
		}
		
		$status = $shipment_table->state_behavior[$result[0]['CURRENT_STATUS']];
		$bstatus = $shipment_table->billing_state_behavior[$result[0]['BILLING_STATUS']];

		//! Move this button up, show for all billing states
		//! SCR# 504 - if cancelled, don't show billing button
		if( $status <> 'cancel' )
			$sts_form_addshipment_form['buttons'][] =
				array( 'label' => 'Billing '.$billing_code, 'link' => $billing_link,
				'button' => 'info', 'icon' => '<span class="glyphicon glyphicon-th-list"></span>',
				'restrict' => EXT_GROUP_BILLING );
		
		if( $sts_containers && ($status == 'cancel' || $status == 'dropped') ) {
			$sts_form_add_shipment_fields['ST_NUMBER']['extras'] = 'readonly';
			$sts_form_add_shipment_fields['IM_EMPTY_DROP']['extras'] = 'readonly disabled';
			
		}
		
		if( $sts_debug ) echo "<h2>".__FILE__.": If approved/billed make read-only</h2>";

		//! If approved/billed make read-only
		if( $status == 'cancel' || $bstatus <> 'entry' ) {
			if( $edit_unapprove &&
				$bstatus == 'unapproved' &&
				$my_session->in_group(EXT_GROUP_FINANCE, EXT_GROUP_BILLING) ) {
			} else {
				$sts_form_addshipment_form['readonly'] = true;
				if( ! $sts_po_rw )
					unset($sts_form_addshipment_form['okbutton']);
				$detail_ro = '&readonly';
			}
			$sts_form_addshipment_form['buttons'] = array( 
				array( 'label' => 'Dup', 'modal' => 'dupshipment_modal',
				'button' => 'success', 'icon' => '<span class="glyphicon glyphicon-repeat"></span>' ),
			);
			//! SCR# 504 - if cancelled, don't show billing button
			if( $status <> 'cancel' )
				$sts_form_addshipment_form['buttons'][] =
				array( 'label' => 'Billing '.$billing_code, 'link' => $billing_link,
				'button' => 'info', 'icon' => '<span class="glyphicon glyphicon-th-list"></span>',
				'restrict' => EXT_GROUP_BILLING );
			if( $multi_company ) {
				$sts_form_addshipment_form['buttons'][] =  
					array( 'label' => 'Stopoff',
					'link' => 'exp_dupshipment.php?CODE=%SHIPMENT_CODE%&STOPOFF',
					'button' => 'default',
					'icon' => '<span class="glyphicon glyphicon-repeat"></span>' );
			}
			
			if( $my_session->in_group(EXT_GROUP_FINANCE) &&
				in_array($bstatus, array('approved','oapproved','billed','unapproved')) ) {
				$sts_form_addshipment_form['buttons'][] =  
					array( 'label' => 'Invoice', 'blank' => true,
						'link' => 'exp_viewinvoice.php?CODE='.
						$unapprove_code, 'button' => 'default',
						'icon' => '<span class="glyphicon glyphicon-list-alt"></span>' );
			}
			
			if( $sts_debug ) echo "<h2>".__FILE__.": include export button if billed, for re-export</h2>";
			
			//! SCR# 395 - include export button if billed, for re-export
			if( $sts_export_sage50 && $my_session->in_group(EXT_GROUP_SAGE50) &&
				in_array($bstatus, array('approved', 'billed')) ) {
				$sts_form_addshipment_form['buttons'][] =  
					array( 'label' => 'Sage50',
						'link' => 'exp_export_csv.php?pw=GoldUltimate&type=invoice&code='.
						$_GET['CODE'], 'button' => 'danger',
						'icon' => '<span class="glyphicon glyphicon-list-alt"></span> <span class="glyphicon glyphicon-arrow-right"></span>' );
			}
			
			if( $my_session->in_group(EXT_GROUP_FINANCE) &&
				in_array($bstatus, array('oapproved','approved','billed')) ) {
					$sts_form_addshipment_form['buttons'][] =  
						array( 'label' => 'Unapprove '.$unapprove_code, 'confirm' => true, 'tip' => 'This returns the shipment to the Unapproved state.<br>You can then edit &amp; make adjustments before you approve again.',
					'link' => 'exp_shipment_state.php?BILLING=true&PW=Soyo&STATE=unapproved&CODE='.
							$unapprove_code.'&CSTATE='.$result[0]['BILLING_STATUS'], 'button' => 'danger', 'icon' => '<span class="glyphicon glyphicon-backward"></span>' );
					if( $sts_export_qb ) 
						$sts_form_addshipment_form['buttons'][] =  
							array( 'label' => 'Resend to QB',
								'link' => 'exp_qb_retry.php?TYPE=shipment&CODE='.
								$_GET['CODE'], 'button' => 'danger',
								'icon' => '<span class="glyphicon glyphicon-forward"></span>' );
					
			}
			if( $my_session->in_group(EXT_GROUP_FINANCE) && $bstatus == 'billed' &&
				$sts_export_qb && ! $sts_qb_online &&
				(! isset($result[0]['quickbooks_dataext_retries']) ||
				$result[0]['quickbooks_dataext_retries'] <> -1) ) {
					$sts_form_addshipment_form['buttons'][] =  
						array( 'label' => 'Resend custom fields to QB', 'link' => 'exp_qb_retry.php?TYPE=dataext&CODE='.
							$_GET['CODE'], 'button' => 'danger', 'icon' => '<span class="glyphicon glyphicon-forward"></span>' );
			}
			
			$shipment_form = new sts_form( $sts_form_addshipment_form, $sts_form_add_shipment_fields, $shipment_table, $sts_debug);
		} else if( in_array($status, array('picked','dropped')) ) {
			$sts_form_addshipment_form['buttons'] = array(
			array( 'label' => 'Dup', 'modal' => 'dupshipment_modal',
			'button' => 'success', 'icon' => '<span class="glyphicon glyphicon-repeat"></span>' ),
			array( 'label' => 'Billing '.$billing_code, 'link' => $billing_link,
			'button' => 'info', 'icon' => '<span class="glyphicon glyphicon-th-list"></span>',
			'restrict' => EXT_GROUP_BILLING )
			);
			if( $multi_company ) {
				$sts_form_addshipment_form['buttons'][] =  
					array( 'label' => 'Stopoff',
					'link' => 'exp_dupshipment.php?CODE=%SHIPMENT_CODE%&STOPOFF',
					'button' => 'default',
					'icon' => '<span class="glyphicon glyphicon-repeat"></span>' );
			}
			$shipment_form = new sts_form( $sts_form_addshipment_form, $sts_form_add_shipment_fields, $shipment_table, $sts_debug);
		} else if( in_array($status, array('assign','dispatch')) ) {
			$sts_form_addshipment_form['buttons'] = array(
			array( 'label' => 'Dup', 'modal' => 'dupshipment_modal',
			'button' => 'success', 'icon' => '<span class="glyphicon glyphicon-repeat"></span>' ),
			array( 'label' => 'Billing '.$billing_code, 'link' => $billing_link,
			'button' => 'info', 'icon' => '<span class="glyphicon glyphicon-th-list"></span>',
			'restrict' => EXT_GROUP_BILLING ),
			array( 'label' => 'Cancel Shipment', 'link' => 'exp_cancelshipment.php?CODE=%SHIPMENT_CODE%',
			'button' => 'danger', 'icon' => '<span class="glyphicon glyphicon-remove"></span>',
			'confirm' => true )
			);
			if( $multi_company ) {
				$sts_form_addshipment_form['buttons'][] =  
					array( 'label' => 'Stopoff',
					'link' => 'exp_dupshipment.php?CODE=%SHIPMENT_CODE%&STOPOFF',
					'button' => 'default',
					'icon' => '<span class="glyphicon glyphicon-repeat"></span>' );
			}
			$shipment_form = new sts_form( $sts_form_addshipment_form, $sts_form_add_shipment_fields, $shipment_table, $sts_debug);
		} else if( $multi_company ) {
			$sts_form_addshipment_form['buttons'][] =  
				array( 'label' => 'Stopoff',
				'link' => 'exp_dupshipment.php?CODE=%SHIPMENT_CODE%&STOPOFF',
				'button' => 'default',
				'icon' => '<span class="glyphicon glyphicon-repeat"></span>' );

			$shipment_form = new sts_form( $sts_form_addshipment_form, $sts_form_add_shipment_fields, $shipment_table, $sts_debug);
		}
		
		if( $sts_debug ) echo "<h2>".__FILE__.": carrier & assign</h2>";
		
		if( $sts_find_carrier && $status == 'assign' ) {
			$sts_form_addshipment_form['buttons'][] = [ 'label' => 'Find Carrier', 'link' => 'exp_find_carrier.php?CODE=%SHIPMENT_CODE%',
			'button' => 'default', 'icon' => '<span class="glyphicon glyphicon-hand-right"></span>' ];
		}
		
		$new_form = false;
		if( $status == 'entry' ) {
			$rd_state = $shipment_table->behavior_state['assign'];
			if( $bstatus <> 'entry' &&
				$shipment_table->state_change_ok( $_GET['CODE'], $result[0]['CURRENT_STATUS'], $rd_state ) ) {
				if( $sts_debug ) echo "<p>Add Ready Dispatch button</p>";
				$code = array( 'label' => 'Ready Dispatch', 'link' => 'exp_assignshipment.php?CODE=%SHIPMENT_CODE%&CSTATE='.$result[0]['CURRENT_STATUS'],
			'button' => $status == 'entry' ? 'success' : 'warning',
			'icon' => '<span class="glyphicon glyphicon-'.($status == 'entry' ? 'arrow-right' : 'warning-sign').'"></span>',
			'confirm' => true );
				if( isset($sts_form_addshipment_form['buttons']) )
					$sts_form_addshipment_form['buttons'][] = $code;
				else
					$sts_form_addshipment_form['buttons'] = array( $code );
				$new_form = true;
			}
		}
		
		if( $sts_debug ) echo "<h2>".__FILE__.": Add UnReady button</h2>";

		//! SCR# 194 - Add UnReady button
		if( $status == 'assign' && $shipment_table->state_change_ok( $_GET['CODE'], $result[0]['CURRENT_STATUS'], $shipment_table->behavior_state['entry'] ) ) {
				if( $sts_debug ) echo "<p>Add UnReady button</p>";
				$code = array( 'label' => 'UnReady', 'tip' => 'Undo ready dispatch',
			'link' => 'exp_shipment_state.php?PW=Soyo&STATE=entry&CODE=%SHIPMENT_CODE%&CSTATE='.$result[0]['CURRENT_STATUS'],
			'button' => 'warning',
			'icon' => '<span class="glyphicon glyphicon-arrow-left"></span>',
			'confirm' => true );
				if( isset($sts_form_addshipment_form['buttons']) )
					$sts_form_addshipment_form['buttons'][] = $code;
				else
					$sts_form_addshipment_form['buttons'] = array( $code );
				$new_form = true;
		}
		
		if( $sts_debug ) echo "<h2>".__FILE__.": Back/forward buttons</h2>";

		//! SCR# 253 - Back/forward buttons
		$check = $shipment_table->database->get_one_row("
			select shipment_prev(".$_GET['CODE'].") S_PREV,
				shipment_next(".$_GET['CODE'].") S_NEXT");
		if( is_array($check)) {
			if( ! empty($check["S_PREV"])) {
				$code = array( 'label' => '', 'tip' => 'Previous Shipment',
					'link' => 'exp_addshipment.php?CODE='.$check["S_PREV"],
					'button' => 'default',
					'icon' => '<span class="glyphicon glyphicon-step-backward"></span>' );
				if( isset($sts_form_addshipment_form['buttons']) )
					$sts_form_addshipment_form['buttons'][] = $code;
				else
					$sts_form_addshipment_form['buttons'] = array( $code );
				$new_form = true;
			}
			if( ! empty($check["S_NEXT"])) {
				$code = array( 'label' => '', 'tip' => 'Next Shipment',
					'link' => 'exp_addshipment.php?CODE='.$check["S_NEXT"],
					'button' => 'default',
					'icon' => '<span class="glyphicon glyphicon-step-forward"></span>' );
				if( isset($sts_form_addshipment_form['buttons']) )
					$sts_form_addshipment_form['buttons'][] = $code;
				else
					$sts_form_addshipment_form['buttons'] = array( $code );
				$new_form = true;
			}
		}
		
		if( $sts_debug ) echo "<h2>".__FILE__.": equipment_checkboxes</h2>";

		$sts_form_addshipment_form = $item_list_table->equipment_checkboxes( $sts_form_addshipment_form,
			'shipment',
			$_GET['CODE'],
			(isset($sts_form_addshipment_form['readonly']) && $sts_form_addshipment_form['readonly']) );
			//$('#EQUIPMENT .panel-body .checkbox input').attr('disabled','disabled');

		if( $new_form )
			$shipment_form = new sts_form( $sts_form_addshipment_form, $sts_form_add_shipment_fields, $shipment_table, $sts_debug);

		//! Any click triggers a save
		$shipment_form->set_autosave();

		if( $sts_debug ) echo "<h2>".__FILE__.": RENDER</h2>";
		
		echo $shipment_form->render( $result[0] );
		update_message( 'loading1', '' );			
		ob_flush(); flush();
		
		$shipment_found = true;
	} else if( isset($result) && is_array($result) && count($result) > 1 ) {
		update_message( 'loading1', '' );			
		ob_flush(); flush();
		
		echo '<h2>Multiple matches found</h2>
		<h3>This may happen if an office number is re-used. Please select the one you want.</h3>
		<table width="60%" class="display table table-striped table-condensed table-bordered table-hover">
		<tr class="exspeedite-bg"><th>Shipment#</th><th>Office#</th><th>Status</th>
		</tr>
		';
		foreach( $result as $row ) {
			echo '<tr><td><a href="exp_addshipment.php?CODE='.$row['SHIPMENT_CODE'].'">'.
				$row['SHIPMENT_CODE'].'</a></td><td>'.
				(empty($row['SS_NUMBER']) ? '' : $row['SS_NUMBER']).
				'</td><td>'.$shipment_table->state_name[$row['CURRENT_STATUS']].'</td></tr>';
		}
		echo '</table>';

	} else { //! Not Found Message
		update_message( 'loading1', '' );			
		ob_flush(); flush();
		
		echo '<h2 class="text-danger"><span class="glyphicon glyphicon-warning-sign"></span> Shipment #'.$_GET['CODE'].' Not Found Or Access Not Allowed.</h2>
		<p>Perhaps the shipment has been removed. Please check with your administrator.</p>';
		$shipment_found = false;
	}

} else {
	if( $sts_debug ) echo "<h2>".__FILE__.": Create new shipment</h2>";

	//! Create new shipment
	$result = $shipment_table->create_empty( isset($_GET["CLIENT"]) ? $_GET["CLIENT"] : false );

	//! SCR# 531 - log event
	$shipment_table->log_event( 'exp_addshipment: CREATE EMPTY shipment '.
		' result = '.($result ? 'true' : 'false').
		' by = '.$_SESSION['EXT_USERNAME'], EXT_ERROR_DEBUG );

	$shipment_time = $setting_table->get( 'option', 'SHIPMENT_TIME_OPTION' );
	if( $result && isset($shipment_time) ) {
		if( $sts_debug ) echo "<p>exp_addshipment: shipment_time = $shipment_time</p>";
		switch( $shipment_time ) {
			case 'At':
			case 'By':
			case 'After':
			case 'Between':
			case 'ASAP':
			case 'FCFS':
				break;
			default:
				$shipment_time = 'At';
				break;
		}

		$shipment_table->update($result, array('PICKUP_TIME_OPTION' => $shipment_time,
			'DELIVER_TIME_OPTION' => $shipment_time), false);
	}
	
	if( $sts_debug )
		echo "<h2>".__FILE__.": reload_page</h2>";
	else
		reload_page ( "exp_addshipment.php?CODE=".$result);
}

if( $shipment_found ) {
	echo '<div id="loading2"><h3 class="text-center"><img src="images/loading.gif" alt="loading" width="125" height="125" /><br>Loading tabs...</h3></div>';
	ob_flush(); flush();

?>
<!-- Nav tabs -->
<ul class="nav nav-tabs">
  <li class="active"><a href="#details" data-toggle="tab">Commodities</a></li>
  <li><a href="#history" data-toggle="tab">History</a></li>
  <li><a href="#stops" data-toggle="tab">Stops</a></li>
<?php if( $sts_attachments ) { ?>
  <li><a href="#attach" data-toggle="tab">Attachments</a></li>
<?php } ?>
<?php if( $sts_email_queueing && $my_session->in_group(EXT_GROUP_ADMIN) ) { ?>
  <li><a href="#email" data-toggle="tab">Email</a></li>
<?php } ?>
</ul>

<!-- Tab panes -->
<div class="tab-content">
  <div role="tabpanel" class="tab-pane active" id="details">
	<div class="row well well-sm" id="COMMODITIES">
	</div>
  </div>
  <div role="tabpanel" class="tab-pane" id="history">
<?php

		require_once( "include/sts_result_class.php" );
		require_once( "include/sts_status_class.php" );

		echo '<div class="row well well-sm">';
		$status_table = sts_status::getInstance($exspeedite_db, $sts_debug);
		$rslt3 = new sts_result( $status_table, "ORDER_CODE = ".$_GET['CODE']." AND SOURCE_TYPE = 'shipment'", $sts_debug, $sts_profiling );
		echo $rslt3->render( $sts_result_shipment_status_layout, $sts_result_status_edit );
		echo '</div>';
?>
	</div>
   <div role="tabpanel" class="tab-pane" id="stops" style="position: relative">
<?php

		require_once( "include/sts_result_class.php" );
		require_once( "include/sts_shipment_load_class.php" );

		echo '<div class="row well well-sm">';
		$sl_lj = sts_shipment_load_left_join::getInstance($exspeedite_db, $sts_debug);
		//! SCR# 364 - hide reposition stop from this tab.
		$rslt3 = new sts_result( $sl_lj, "SHIPMENT = ".$_GET['CODE'], $sts_debug, $sts_profiling );
		echo $rslt3->render( $sts_result_sl_lj_layout, $sts_result_sl_view );
		echo '</div>';

if( $sts_attachments ) {
?>
  </div>
  <div role="tabpanel" class="tab-pane" id="attach">
<?php
	
	$attachment_table = sts_attachment::getInstance($exspeedite_db, $sts_debug);
	$rslt4 = new sts_result( $attachment_table, "(SOURCE_CODE = ".$_GET['CODE']." AND SOURCE_TYPE = 'shipment')
        OR ATTACHMENT_CODE IN (SELECT A.ATTACHMENT_CODE
		FROM EXP_SHIPMENT_LOAD SL, EXP_ATTACHMENT A
        WHERE SL.SHIPMENT_CODE = ".$_GET['CODE']." AND A.SOURCE_CODE = SL.LOAD_CODE
        AND A.SOURCE_TYPE = 'load')
        
        OR ATTACHMENT_CODE IN (SELECT A.ATTACHMENT_CODE
        FROM EXP_SHIPMENT S, EXP_ATTACHMENT A
        WHERE S.SHIPMENT_CODE = ".$_GET['CODE']." AND A.SOURCE_CODE = S.LOAD_CODE
        AND A.SOURCE_TYPE = 'load')", $sts_debug );
	echo $rslt4->render( $sts_result_attachment_layout, $sts_result_attachment_edit, '?SOURCE_TYPE=shipment&SOURCE_CODE='.$_GET['CODE'] );
	echo '</div>';
}

if( $sts_email_queueing && $my_session->in_group(EXT_GROUP_ADMIN) ) {
?>
	<div role="tabpanel" class="tab-pane" id="email">
<?php
	require_once( "include/sts_email_class.php" );
	
	$queue = sts_email_queue::getInstance($exspeedite_db, $sts_debug);
	$sts_result_queue_edit['title'] .= $queue->shipment_emails( $_GET['CODE'] );
	
	$rslt5 = new sts_result( $queue, "(SOURCE_TYPE='shipment' AND SOURCE_CODE=".$_GET['CODE'].")
		OR (EMAIL_TYPE='attachment' AND (SELECT A.SOURCE_CODE
		FROM EXP_ATTACHMENT A
		WHERE A.ATTACHMENT_CODE = EXP_EMAIL_QUEUE.SOURCE_CODE
		AND A.SOURCE_TYPE = 'shipment') = ".$_GET['CODE']." )", $sts_debug );
	echo $rslt5->render( $sts_result_queue_layout, $sts_result_queue_edit, '?SOURCE_TYPE=shipment&SOURCE_CODE='.$_GET['CODE'] );
	echo '</div>';
}
?>
 </div>
<?php
	update_message( 'loading2', '' );			
	ob_flush(); flush();

}
?>
</div>
</div>

	<div class="modal fade fuzzy bs-example-modal-sm" tabindex="-1" role="dialog" aria-labelledby="mySmallModalLabel" aria-hidden="true" id="addshipment_modal">
	  <div class="modal-dialog">
		<div class="modal-content">
		<div class="modal-header" id="addshipment_modal_header">
			<h4 class="modal-title" id="myModalLabel"><span class="text-success"><strong>Saving...</strong></span></h4>
		</div>
		<div class="modal-body" id="addshipment_modal_body">
			<p class="text-center"><img src="images/loading.gif" alt="loading" width="125" height="125" /></p>
		</div>
		</div>
		</div>
	</div>

	<div class="modal draggable fade fuzzy bs-example-modal-sm" tabindex="-1" role="dialog" aria-labelledby="mySmallModalLabel" aria-hidden="true" id="dupshipment_modal">
	  <div class="modal-dialog">
		<div class="modal-content">
		<div class="modal-header" id="dupshipment_modal_header">
			<h4 class="modal-title" id="myModalLabel"><span class="text-success"><strong>Duplicate Shipment</strong></span></h4>
		</div>
		<form role="form" class="form-horizontal" action="exp_dupshipment.php" 
					method="post" enctype="multipart/form-data" name="dup" id="dup">
			<div class="modal-body" id="dupshipment_modal_body">
				<input name="CODE" id="CODE" type="hidden" value="<?php echo (isset($_GET['CODE']) ? $_GET['CODE'] : 0); ?>">
				<?php
					if( $shipment_table->dup_reuse_officenum() &&
						$result[0]['CURRENT_STATUS'] == $shipment_table->behavior_state['cancel'] )
					
				echo '<div class="form-group">
					<div class="col-sm-11">
						<p class="text-info""><span class="glyphicon glyphicon-warning-sign"></span> As this shipment is cancelled, we will attempt to re-use the office number.</p>
					</div>
				</div>';	
				?>
				<div class="form-group">
					<label for="NUM" class="col-sm-6 control-label">Create how many duplicates?</label>
					<div class="col-sm-3">
						<input class="form-control text-right" name="NUM" 
							id="NUM" type="number" step="1" min="1" max="100" value="1" align="right" 
							autofocus required>
					</div>
					<!-- !SCR# 779 - Checkboxes for copy fields -->
					<div class="col-sm-6">
						<label for="POS" class="col-sm-10 control-label pad7">Copy PO#s</label>
						<div class="col-sm-2">
							<input class="form-control text-right" name="POS" 
								id="POS" type="checkbox" align="right" >
						</div>
						<label for="BOL" class="col-sm-10 control-label pad7">Copy BOL#</label>
						<div class="col-sm-2">
							<input class="form-control text-right" name="BOL" 
								id="BOL" type="checkbox" align="right" >
						</div>
						<label for="REF" class="col-sm-10 control-label pad7">Copy Reference#</label>
						<div class="col-sm-2">
							<input class="form-control text-right" name="REF" 
								id="REF" type="checkbox" align="right" >
						</div>
						<label for="PICK" class="col-sm-10 control-label pad7">Copy Pickup#</label>
						<div class="col-sm-2">
							<input class="form-control text-right" name="PICK" 
								id="PICK" type="checkbox" align="right" >
						</div>
						<label for="CUST" class="col-sm-10 control-label pad7">Copy Customer#</label>
						<div class="col-sm-2">
							<input class="form-control text-right" name="CUST" 
								id="CUST" type="checkbox" align="right" >
						</div>
						<label for="PCONF" class="col-sm-10 control-label pad7">Copy Pickup Conf#</label>
						<div class="col-sm-2">
							<input class="form-control text-right" name="PCONF" 
								id="PCONF" type="checkbox" align="right" >
						</div>
						<label for="DCONF" class="col-sm-10 control-label pad7">Copy Delivery Conf#</label>
						<div class="col-sm-2">
							<input class="form-control text-right" name="DCONF" 
								id="DCONF" type="checkbox" align="right" >
						</div>
					</div>
						<div class="col-sm-6">
						<label for="BROKER" class="col-sm-10 control-label pad7">Copy Customs Broker</label>
						<div class="col-sm-2">
							<input class="form-control text-right" name="BROKER" 
								id="BROKER" type="checkbox" align="right" >
						</div>
						<?php if( $sts_containers ) { ?>
						<label for="EMPTY_DROP" class="col-sm-10 control-label pad7">Copy Empty Drop</label>
						<div class="col-sm-2">
							<input class="form-control text-right" name="EMPTY_DROP" 
								id="EMPTY_DROP" type="checkbox" align="right" >
						</div>
						<?php } ?>
					</div>
				</div>
			<div class="modal-footer">
				<button class="btn btn-md btn-success" name="continue" id="continue" type="submit"><span class="glyphicon glyphicon-repeat"></span> Dup</button>
				<button class="btn btn-md btn-default" data-dismiss="modal"><span class="glyphicon glyphicon-remove"></span> Cancel</button>
			</div>
		</form>		
			
		</div>
		</div>
		</div>
	</div>

	<script language="JavaScript" type="text/javascript"><!--
		
		// Sets the client info
		$(document).ready( function () {
			var code = <?php echo (isset($_GET['CODE']) ? $_GET['CODE'] : 0); ?>;
			var username = '<?php echo (isset($_SESSION['EXT_USERNAME']) ? $_SESSION['EXT_USERNAME'] : 0); ?>';
			var profiles = '<?php echo (in_group( EXT_GROUP_PROFILES ) ? 'true' : 'false'); ?>';
			var bstatus = '<?php echo $bstatus; ?>';
			var client_label = <?php echo $client_label ? 'true' : 'false'; ?>;
			var no_updates = <?php echo isset($sts_form_addshipment_form['readonly']) && $sts_form_addshipment_form['readonly'] ? 'true' : 'false'; ?>;
			
			//console.log($("#addshipment :input disabled"));
			
			$('div.STOP_ETA').datetimepicker({
		      //language: 'en',
		      format: 'MM/DD/YYYY HH:mm',
		      widgetParent: 'div#stops',
		      widgetPositioning: {
			      horizontal: 'auto',
			      vertical: 'bottom'
		      },
		    //'div#table-responsive',
		      //autoclose: true,
		      //pickTime: false
		    }).on('dp.change', function(e) {
			//    console.log( $(this).children('input').val(), $(this).attr('code') );
			    $.ajax({
					url: 'exp_editstop_eta.php',
					data: {
						pw: 'Wrench7358',
						code: $(this).attr('code'),
						eta: encodeURIComponent($(this).children('input').val())
					}
				});
			});
			
			$('.modal.draggable>.modal-dialog').draggable({
			    cursor: 'move',
			    handle: '.modal-header'
			});
			$('.modal.draggable>.modal-dialog>.modal-content>.modal-header').css('cursor', 'move');
			
			// SCR# 719 - allow correct sort by date column
			$.fn.dataTable.moment( 'MM/DD/YYYY HH:mm A' );
			$.fn.dataTable.moment( 'MM/DD/YYYY HH:mm' );

			$('#EXP_STOP,#EXP_SHIPMENT,#EXP_STATUS,#EXP_EMAIL_QUEUE').dataTable({
		        //"bLengthChange": false,
		        "bFilter": true,
		        "bSort": true,
		        "bInfo": false,
				"bAutoWidth": false,
				//"bProcessing": true, 
				"sScrollX": "100%",
				"sScrollY": "400px",
				//"sScrollXInner": "120%",
		        //"lengthMenu": [[-1, 25, 50], ["All", 25, 50]],
				"bPaginate": false,
				"bScrollCollapse": false,
				"bSortClasses": false		
			});
			
			function update_pickup() {
				selector = no_updates ? $('p#PICKUP_TIME_OPTION_STATIC').text() : $('select#PICKUP_TIME_OPTION').val();
				
				switch ( selector ) {
					case 'ASAP':
					case 'FCFS':
						$('#PTIME1').prop('hidden', 'hidden');
						$('#PTIME2').prop('hidden', 'hidden');
						break;

					case 'At':
					case 'By':
					case 'After':
						$('#PTIME1').prop('hidden',false);
						$('#PTIME2').prop('hidden', 'hidden');
						break;
		
					case 'Between':
						$('#PTIME1').prop('hidden',false);
						$('#PTIME2').prop('hidden',false);
						break;
				}
			}
			
			function update_deliver() {
				selector = no_updates ? $('p#DELIVER_TIME_OPTION_STATIC').text() : $('select#DELIVER_TIME_OPTION').val();
				
				switch ( selector ) {
					case 'ASAP':
					case 'FCFS':
						$('#DTIME1').prop('hidden', 'hidden');
						$('#DTIME2').prop('hidden', 'hidden');
						break;

					case 'At':
					case 'By':
					case 'After':
						$('#DTIME1').prop('hidden',false);
						$('#DTIME2').prop('hidden', 'hidden');
						break;
		
					case 'Between':
						$('#DTIME1').prop('hidden',false);
						$('#DTIME2').prop('hidden',false);
						break;
				}
			}
			
			$('#COMMODITIES').load('exp_list_table.php?pw=Emmental&table=detail<?php echo $detail_ro; ?>&code=' + code );
		
			var saveadd_clicked = false;
			$("#saveadd").click(function() {
				saveadd_clicked = true;
			});
			
			//! SCR# 367 - moved most of submit to new function
			function do_submit() {
				var data = $("#addshipment :input").serializeArray();
				
				//console.log(data);
				$.post("exp_addshipment.php", data, function( data2 ) {
					//! SCR# 367 - this updates the LAST_CHANGED field
					var patt = /id="LAST_CHANGED" type="hidden" value="(.*)">/;
					var matches = data2.match(patt);
					
					//! SCR# 368 - update only if we get a match
					//! SCR# 531 - update logging for negative case
					if( matches[1] != '' ) {
						console.log('do_submit: updated ', matches[1]);
						$('#LAST_CHANGED').val(matches[1]);
					} else {
						console.log('do_submit: NOT updated ');
					}

					//! How we update the Valid info, LAT and LON
					// We call exp_lookup_valid.php, that pulls the data and we replace it
					// into SHIPPER_VALID, CONS_VALID, BILLTO_VALID etc.
					$.ajax({
						//async: false,
						url: 'exp_lookup_valid.php',
						data: {
							CODE: code,
							PW: 'Zonko'
						},
						dataType: "json",
						success: function(data) {
							$('#SHIPPER_VALID').replaceWith(data['SHIPPER_VALID']);
							$('#CONS_VALID').replaceWith(data['CONS_VALID']);
							$('#BILLTO_VALID').replaceWith(data['BILLTO_VALID']);
							if( $('#SHIPPER_COUNTRY').val() != $('#CONS_COUNTRY').val() )
								$('#BROKER_VALID').replaceWith(data['BROKER_VALID']);
							$('.inform').popover({ 
								placement: 'top',
								html: 'true',
								container: 'body',
								trigger: 'hover',
								delay: { show: 50, hide: 3000 },
								title: '<strong>Information</strong> <button type="button" class="close" data-hide="confirm" data-delay="0" aria-hidden="true">&times;</button>' 
							});

						}
					});
					
					//console.log( 'PO_NUMBER', $('#PO_NUMBER').val());
					var pos = '';
					if( typeof $('#PO_NUMBER').val() !== "undefined" &&
						$('#PO_NUMBER').val() != '' )
						pos = encodeURIComponent($('#PO_NUMBER').val());
					if( typeof $('#PO_NUMBER2').val() !== "undefined" &&
						$('#PO_NUMBER2').val() != '' )
						pos = pos + '+' + encodeURIComponent($('#PO_NUMBER2').val());
					if( typeof $('#PO_NUMBER3').val() !== "undefined" &&
						$('#PO_NUMBER3').val() != '' )
						pos = pos + '+' + encodeURIComponent($('#PO_NUMBER3').val());
					if( typeof $('#PO_NUMBER4').val() !== "undefined" &&
						$('#PO_NUMBER4').val() != '' )
						pos = pos + '+' + encodeURIComponent($('#PO_NUMBER4').val());
					if( typeof $('#PO_NUMBER5').val() !== "undefined" &&
						$('#PO_NUMBER5').val() != '' )
						pos = pos + '+' + encodeURIComponent($('#PO_NUMBER5').val());
					
					//console.log(pos);
					if( pos != '' ) {
						$.ajax({
							async: false,
							url: 'exp_po_lookup.php',
							data: {
								code: 'Fisherman',
								shipment: code,
								po: pos
							},
							dataType: "json",
							success: function(data) {
								if( data['RESPONSE'] ) {
									alert( data['TEXT']);
								}
							}
						});
					}
									
					$('#addshipment_modal').modal('hide');
					window.addshipment_HAS_CHANGED = false;	// depreciated
					$('a').off('click.addshipment');

				});
			}

			console.log('reload: LAST_CHANGED = ', $('#LAST_CHANGED').val());
			
			$( "#addshipment" ).submit(function( event ) {
				//console.log( saveadd_clicked );
				if( saveadd_clicked ) return;
				
				event.preventDefault();  //prevent form from submitting
				$('#addshipment_modal').modal({
					container: 'body'
				});
				
				console.log('check_expired: LAST_CHANGED = ', $('#LAST_CHANGED').val());
				//! SCR# 367 - check if the data is expired
				$.ajax({
					url: 'exp_check_expired.php',
					data: {
						CODE: code,
						PW: 'Hillock',
						TYPE: 'shipment',
						CHANGED: $('#LAST_CHANGED').val()
					},
					dataType: "json",
					success: function(data) {
						console.log('data ', data);
						if( data.STATUS == 'OK' ) {
							do_submit();
						} else {
							if( username == data.USERNAME ) {
								forcebutton = '<button id="forcesave" class="btn btn-md btn-danger"><span class="glyphicon glyphicon-warning-sign"></span> Save Anyway</button>';
							} else {
								forcebutton = '';
							}
							
							$('#addshipment_modal_header').html('<h3 class="text-danger"><span class="glyphicon glyphicon-warning-sign"></span> Unable to Save Changes</h3>');
							
							$('#addshipment_modal_body').html('<p>The data on shipment <strong>' +
							code + '</strong> is out of date. This happens when someone has updated it since you opened this screen. It is to protect changes that were saved from being reversed. The solution is to reload the shipment and then you can enter and save your changes.</p>' + 
							'<p>Shipment <strong>' + code + '</strong> last updated by <strong>' + data.USERNAME + '</strong> at <strong>' +
							data.CHANGED_DATE + '</strong></p>' +
							'<p>This screen last updated <strong>' + $('#LAST_CHANGED').val() + '</strong></p>' +
							'<br><p><a class="btn btn-md btn-success" id="reload_shipment" href="exp_addshipment.php?CODE=' + code + '"><span class="glyphicon glyphicon-repeat"></span> Reload Shipment</a>' + forcebutton + '</p>' );

							$("#forcesave").click(function() {
								console.log('forcesave');
								$('#addshipment_modal_header').html('<h4 class="modal-title" id="myModalLabel"><span class="text-success"><strong>Saving...</strong></span></h4>');
								$('#addshipment_modal_body').html('<p class="text-center"><img src="images/loading.gif" alt="loading" width="125" height="125" /></p>');

								do_submit();
							});
			
							return;
						}
					}
				});
			});
			
			function update_miles() {
				//console.log('update_miles: ', $('input#SHIPPER_ZIP').val(), $('input#SHIPPER_ZIP').val(), $('input#DISTANCE').val(), '<?php echo $status; ?>');
				if( $('input#SHIPPER_ZIP').val() != '' &&
					$('input#SHIPPER_ZIP').val() != '' &&
					! ($('input#DISTANCE').val() > 0 &&
					'<?php echo $detail_ro; ?>' != '') &&
					jQuery.inArray('<?php echo $status; ?>', ["approved", "billed", "cancel"]) == -1) {

					//console.log($('select#SHIPPER_STATE').val());
					var c1 = $('input#SHIPPER_COUNTRY').val();
					if( c1 != 'Canada' ) c1 = 'USA';
					var c2 = $('input#CONS_COUNTRY').val();
					if( c2 != 'Canada' ) c2 = 'USA';
					
					$.ajax({
						//async: false,
						url: 'exp_distance_grid.php',
						data: {
							code: 'Recycle',
							addr11: $('input#SHIPPER_ADDR1').val(),
							addr21: $('input#SHIPPER_ADDR2').val(),
							city1: $('input#SHIPPER_CITY').val(),
							state1: $('select#SHIPPER_STATE').val(),
							zip1: $('input#SHIPPER_ZIP').val(),
							country1: c1,
							addr12: $('input#CONS_ADDR1').val(),
							addr22: $('input#CONS_ADDR2').val(),
							city2: $('input#CONS_CITY').val(),
							state2: $('select#CONS_STATE').val(),
							zip2: $('input#CONS_ZIP').val(),
							country2: c2,
							bt: $('#BILLTO_NAME').val(),
							shipment: code,
							CHANGED: $('#LAST_CHANGED').val()
						},
						dataType: "json",
						success: function(data) {
							if( data[0] >= 0) {
								$('input#DISTANCE').val(data[0]);
								$('input#DISTANCE_SOURCE').val(data[2]);
								$('input#DISTANCE_LAST_AT').val(data[3]);
								//! SCR# 367 - update LAST_CHANGED
								//! SCR# 368 - update only if we get a match
								if( data[4] != '' ) {
									console.log('exp_distance_grid.php updated ', data[4]);
									$('#LAST_CHANGED').val(data[4]);
								} else {
									console.log('update_miles: NOT updated');
								}
							} else {
								$('input#DISTANCE').val('');
								$('input#DISTANCE_SOURCE').val('none');
								$('input#DISTANCE_LAST_AT').val('');
							}
						}
					});			
				}
			}
			
			//! Update NOTES field
			function update_note() {
				$.ajax({
					url: 'exp_client_notes.php',
					data: {
						PW: 'IceCream',
						SHIPPER: $('input#SHIPPER_CLIENT_CODE').val(),
						CONS: $('input#CONS_CLIENT_CODE').val(),
						BILLTO: $('input#BILLTO_CLIENT_CODE').val()
					},
					dataType: "text",
					success: function(data) {
						//console.log(data);
						if( data.length > 0 )
							$('textarea#NOTES').val(data);
					}
				});
			}

			/* Not working -need it to clear on typing, but not typeahead
			$('#BILLTO_NAME').bind('input', function () {
				console.log($(this).val(), $('input#BILLTO_NAME_SELECTED').val(), $(this));
				if( $(this).val() != $('input#BILLTO_NAME_SELECTED').val() ) {
					//$(this).val('');
					console.log('clearing fields');
					$('input#BILLTO_ADDR1').val('');
					$('input#BILLTO_ADDR2').val('');
					$('input#BILLTO_CITY').val('');
					$('input#BILLTO_STATE').val('');
					$('input#BILLTO_ZIP').val('');
					$('input#BILLTO_PHONE').val('');
					$('input#BILLTO_EXT').val('');
					$('input#BILLTO_FAX').val('');
					$('input#BILLTO_CELL').val('');
					$('input#BILLTO_CONTACT').val('');
					$('input#BILLTO_EMAIL').val('');
				}
   			});
   			*/
   			
   			//! SCR# 702 - Update Equipment Requirements
   			function update_equipment() {
   				$.ajax({
					url: 'exp_prop_eq_req.php',
					data: {
						pw: 'Saucony',
						client: $('input#BILLTO_CLIENT_CODE').val(),
						shipment: $('#SHIPMENT_CODE').val()
					},
					dataType: "text",
					success: function(data) {
						//console.log(data);
						if( data.length > 0 )
							$('#EQUIPMENT').replaceWith(data);
					}
				});	   			
   			}
			
			$('#BILLTO_NAME').on('typeahead:selected', function(obj, datum, name) {
				$('input#BILLTO_NAME_SELECTED').val(datum.NAME); //! was LABEL
				$('input#BILLTO_CLIENT_CODE').val(datum.CLIENT_CODE);
				//console.log('code ',$('input#BILLTO_CLIENT_CODE').val(), datum.CLIENT_CODE);
				$('input#BILLTO_ADDR1').val(datum.ADDRESS);
				$('input#BILLTO_ADDR2').val(datum.ADDRESS2);
				$('input#BILLTO_CITY').val(datum.CITY);
				$('input#BILLTO_STATE').val(datum.STATE);
				$('select#BILLTO_COUNTRY').val(datum.COUNTRY);
				//! SCR# 818 - copy over salesperson, even if NONE (0)
				if( datum.SALES_PERSON != $('select#SALES_PERSON').val() ) {
					$('select#SALES_PERSON').val(datum.SALES_PERSON);
					console.log('exp_update_sales_person', code, $('select#SALES_PERSON').val());
	   				$.ajax({
						url: 'exp_update_sales_person.php',
						data: {
							pw: 'HokaOneOne',
							CODE: code,
							sales: datum.SALES_PERSON,
						},
						dataType: "json",
						success: function(data) {
							console.log('exp_update_sales_person', data );
							if( data != '' )
								$('#LAST_CHANGED').val(data);
						}
					});	   			
				}
				$('input#BILLTO_ZIP').val(datum.ZIP_CODE);
				$('input#BILLTO_PHONE').val(datum.PHONE_OFFICE);
				$('input#BILLTO_EXT').val(datum.PHONE_EXT);
				$('input#BILLTO_FAX').val(datum.PHONE_FAX);
				$('input#BILLTO_CELL').val(datum.PHONE_CELL);
				$('input#BILLTO_CONTACT').val(datum.CONTACT_NAME);
				$('input#BILLTO_EMAIL').val(datum.EMAIL);
				//! SCR# 486 - update business code if one exists for the client
				if( datum.BUSINESS_CODE > 0 )
					$('select#BUSINESS_CODE').val(datum.BUSINESS_CODE);
				update_miles();
				broker_pane_visible();
				update_note();
				update_billto_label();
				update_equipment();
			});
						
			$('input#BILLTO_NAME_SELECTED').val($('#BILLTO_NAME').val());

			$('#BILLTO_NAME').on('typeahead:opened', function(obj, datum, name) {
				$('input#BILLTO_NAME_SELECTED').val($('#BILLTO_NAME').val());
			});
			
			$('#CALLER_NAME').on('typeahead:selected', function(obj, datum, name) {
				$('input#CALLER_PHONE').val(datum.PHONE_OFFICE);
			});
			
			$('#SHIPPER_NAME').on('typeahead:selected', function(obj, datum, name) {
				$('input#SHIPPER_CLIENT_CODE').val(datum.CLIENT_CODE);
				//console.log('code ',$('input#SHIPPER_CLIENT_CODE').val(), datum.CLIENT_CODE);
				$('input#SHIPPER_ADDR1').val(datum.ADDRESS);
				$('input#SHIPPER_ADDR2').val(datum.ADDRESS2);
				$('input#SHIPPER_CITY').val(datum.CITY);
				$('select#SHIPPER_STATE').val(datum.STATE);
				$('select#SHIPPER_COUNTRY').val(datum.COUNTRY).change();
				$('input#SHIPPER_ZIP').val(datum.ZIP_CODE);
				$('input#SHIPPER_PHONE').val(datum.PHONE_OFFICE);
				$('input#SHIPPER_EXT').val(datum.PHONE_EXT);
				$('input#SHIPPER_FAX').val(datum.PHONE_FAX);
				$('input#SHIPPER_CELL').val(datum.PHONE_CELL);
				$('input#SHIPPER_CONTACT').val(datum.CONTACT_NAME);
				$('input#SHIPPER_EMAIL').val(datum.EMAIL);
				$('input#SHIPPER_TERMINAL').val(datum.TERMINAL_ZONE);
				$('span#SHIPPER_TZONE').html('<span class="glyphicon glyphicon-time"></span> '+datum.TZONE);
				$('#ADD_SHIPPER_CLIENT').removeClass('btn-default')
					.addClass('btn-success')
					.addClass('disabled')
					.prop('title', 'Quick Add Shipper (disabled - existing client selected)')
					.removeAttr('data-original-title')
					.tooltip('fixTitle');
				update_miles();
				broker_pane_visible();
				update_note();
			});
			
			$('#SHIPPER_ZIP').on('typeahead:selected', function(obj, datum, name) {
				$('input#SHIPPER_CITY').val(datum.CityMixedCase);
				$('select#SHIPPER_STATE').val(datum.State);
				$('select#SHIPPER_COUNTRY').val(datum.Country).change();
				$('span#SHIPPER_TZONE').html('<span class="glyphicon glyphicon-time"></span> '+datum.TZONE);
				update_miles();
				broker_pane_visible();
			});
			
			$('#CONS_ZIP').on('typeahead:selected', function(obj, datum, name) {
				$('input#CONS_CITY').val(datum.CityMixedCase);
				$('select#CONS_STATE').val(datum.State);
				$('select#CONS_COUNTRY').val(datum.Country).change();
				$('span#CONS_TZONE').html('<span class="glyphicon glyphicon-time"></span> '+datum.TZONE);
				update_miles();
				broker_pane_visible();
			});
			
			$('#BILLTO_ZIP').on('typeahead:selected', function(obj, datum, name) {
				$('input#BILLTO_CITY').val(datum.CityMixedCase);
				$('select#BILLTO_STATE').val(datum.State);
				$('select#BILLTO_COUNTRY').val(datum.Country);
				update_miles();
			});
			
			$('#CONS_NAME').on('typeahead:selected', function(obj, datum, name) {
				$('input#CONS_CLIENT_CODE').val(datum.CLIENT_CODE);
				//console.log('code ',$('input#CONS_CLIENT_CODE').val(), datum.CLIENT_CODE);
				$('input#CONS_ADDR1').val(datum.ADDRESS);
				$('input#CONS_ADDR2').val(datum.ADDRESS2);
				$('input#CONS_CITY').val(datum.CITY);
				$('select#CONS_STATE').val(datum.STATE);
				$('input#CONS_ZIP').val(datum.ZIP_CODE);
				$('select#CONS_COUNTRY').val(datum.COUNTRY).change();
				$('input#CONS_PHONE').val(datum.PHONE_OFFICE);
				$('input#CONS_EXT').val(datum.PHONE_EXT);
				$('input#CONS_FAX').val(datum.PHONE_FAX);
				$('input#CONS_CELL').val(datum.PHONE_CELL);
				$('input#CONS_CONTACT').val(datum.CONTACT_NAME);
				$('input#CONS_EMAIL').val(datum.EMAIL);
				$('input#CONS_TERMINAL').val(datum.TERMINAL_ZONE);
				$('span#CONS_TZONE').html('<span class="glyphicon glyphicon-time"></span> '+datum.TZONE);
				$('#ADD_CONS_CLIENT').removeClass('btn-default')
					.addClass('btn-success')
					.addClass('disabled')
					.prop('title', 'Quick Add Consignee (disabled - existing client selected)')
					.removeAttr('data-original-title')
					.tooltip('fixTitle');
				update_miles();
				broker_pane_visible();
				update_note();
			});
			
			//! If we change shipper, consignee, or bill-to
			function update_broker_pane() {
				if( ($('#SHIPPER_COUNTRY').val() != $('#CONS_COUNTRY').val()) ||
					($('#SHIPPER_COUNTRY_STATIC').length && $('#CONS_COUNTRY_STATIC').length &&
					$('#SHIPPER_COUNTRY_STATIC').html() != $('#CONS_COUNTRY_STATIC').html()) &&
					$('#BILLTO_CLIENT_CODE').val() > 0 ) {
					//console.log( 'selected: ', $('select#BROKER_CHOICE').val() );
					$.ajax({
						//async: false,
						url: 'exp_get_customs_broker.php',
						data: {
							pw: 'Diabetics',
							code: $('#BILLTO_CLIENT_CODE').val(),
							choice: $('select#BROKER_CHOICE').val()
						},
						dataType: "json",
						success: function(data) {
							//console.log(data, data.SELECTED );

							if( ! $('#SHIPPER_COUNTRY_STATIC').length ) {
							//If a <p> change it to a <select>
							$('p#BROKER_CHOICE').replaceWith('<select id="BROKER_CHOICE" class="form-control" name="BROKER_CHOICE"></select>');
							
							// Update menu
							if( data.CHOICES ) {
								$('select#BROKER_CHOICE option').remove();
								$.each(data.CHOICES, function(key, value) {
									if( data.SELECTED && value.CONTACT_INFO_CODE == data.SELECTED.CONTACT_INFO_CODE)
										var option = new Option(value.LABEL, value.CONTACT_INFO_CODE, false, true);
									else 
										var option = new Option(value.LABEL, value.CONTACT_INFO_CODE);
									$('select#BROKER_CHOICE').append($(option)); 
								});
								$('select#BROKER_CHOICE').off('change');
								$('select#BROKER_CHOICE').on('change', function(event) {
									update_broker_pane();
								});
							} else {
								$('select#BROKER_CHOICE option').remove();
								var option = new Option('no choices', 0);
								$('select#BROKER_CHOICE').append($(option));
							}
							} else {
								$("label[for='BROKER_CHOICE']").remove();
								$("#BROKER_CHOICE_STATIC").parent().remove();
							}
							// Update Customs Broker info
							if( data.SELECTED ) {
								$('#BROKER_NAME').val(data.SELECTED.LABEL);
								$('#BROKER_ADDR1').val(data.SELECTED.ADDRESS);
								$('#BROKER_ADDR2').val(data.SELECTED.ADDRESS2);
								$('#BROKER_CITY').val(data.SELECTED.CITY);
								$('#BROKER_STATE').val(data.SELECTED.STATE);
								$('#BROKER_ZIP').val(data.SELECTED.ZIP_CODE);
								$('#BROKER_COUNTRY').val(data.SELECTED.COUNTRY);
								$('#BROKER_PHONE').val(data.SELECTED.PHONE_OFFICE);
								$('#BROKER_EXT').val(data.SELECTED.PHONE_EXT);
								$('#BROKER_FAX').val(data.SELECTED.PHONE_FAX);
								$('#BROKER_CELL').val(data.SELECTED.PHONE_CELL);
								$('#BROKER_CONTACT').val(data.SELECTED.CONTACT_NAME);
								$('#BROKER_EMAIL').val(data.SELECTED.EMAIL);
							} else {
								$('#BROKER_NAME').val('');
								$('#BROKER_ADDR1').val('');
								$('#BROKER_ADDR2').val('');
								$('#BROKER_CITY').val('');
								$('#BROKER_STATE').val('');
								$('#BROKER_ZIP').val('');
								$('#BROKER_COUNTRY').val('');
								$('#BROKER_PHONE').val('');
								$('#BROKER_EXT').val('');
								$('#BROKER_FAX').val('');
								$('#BROKER_CELL').val('');
								$('#BROKER_CONTACT').val('');
								$('#BROKER_EMAIL').val('');
								$.ajax({ // This clears out the shipment fields for broker
									//async: false,
									url: 'exp_get_customs_broker.php',
									data: {
										pw: 'Diabetics',
										clear: code,
									},
									dataType: "json",
									success: function(data) {
										console.log('get_customs_broker ', data);
										$('#LAST_CHANGED').val(data);
									}
								});
							}
						}
					});			
				} else {
					$('#BROKER_NAME').val('');
					$('#BROKER_ADDR1').val('');
					$('#BROKER_ADDR2').val('');
					$('#BROKER_CITY').val('');
					$('#BROKER_STATE').val('');
					$('#BROKER_ZIP').val('');
					$('#BROKER_COUNTRY').val('');
					$('#BROKER_PHONE').val('');
					$('#BROKER_EXT').val('');
					$('#BROKER_FAX').val('');
					$('#BROKER_CELL').val('');
					$('#BROKER_CONTACT').val('');
					$('#BROKER_EMAIL').val('');
					$.ajax({ // This clears out the shipment fields for broker
						//async: false,
						url: 'exp_get_customs_broker.php',
						data: {
							pw: 'Diabetics',
							clear: code,
						},
						dataType: "json",
						success: function(data) {
							console.log('get_customs_broker ', data);
							$('#LAST_CHANGED').val(data);
						}
					});
				}
			}
			
			//! Make the customs broker information visible
			function broker_pane_visible() {
				if( ($('#SHIPPER_COUNTRY').val() != $('#CONS_COUNTRY').val()) ||
					($('#SHIPPER_COUNTRY_STATIC').length && $('#CONS_COUNTRY_STATIC').length &&
					$('#SHIPPER_COUNTRY_STATIC').html() != $('#CONS_COUNTRY_STATIC').html()) ) {
					//console.log('broker_pane_visible: visible');
					$('#BROKER_PANE').prop('hidden',false);
					if( ! no_updates )
						update_broker_pane();
				} else {
					//console.log('broker_pane_visible: hidden');
					$('#BROKER_PANE').prop('hidden', 'hidden');
				}
			}
			
			broker_pane_visible();
			if( ! no_updates )
				update_broker_pane();
			
			$('#SHIPPER_COUNTRY, #CONS_COUNTRY, #BILLTO_CLIENT_CODE').on('change', function(event) {
				broker_pane_visible();
			});

			$('select#PICKUP_TIME_OPTION').on('change', function(event) {
				update_pickup('select#PICKUP_TIME_OPTION');
			});


			$('select#DELIVER_TIME_OPTION').on('change', function(event) {
				update_deliver();
			});

			//! ST_NUMBER / Container number handling
			$('input#ST_NUMBER').on('keyup', function(event) {
				console.log( 'ST_NUMBER changed to ', $('input#ST_NUMBER').val() );

				if( $('input#ST_NUMBER').val() ) {
					$.ajax({
						url: 'exp_check_cn.php',
						data: {
							shipment: code,
							container: encodeURIComponent($('input#ST_NUMBER').val()),
							pw: 'EnglishTea'
						},
						dataType: "json",
						success: function(data) {
							console.log('exp_check_cn ', data);
							if( data != false ) {
								alert(data);
							}
						}
					});
				}
			});

			//! IM_EMPTY_DROP handling
			$('select#IM_EMPTY_DROP').on('change', function(event) {
				console.log( 'IM_EMPTY_DROP changed to ', $('select#IM_EMPTY_DROP').val() );
				var load = <?php echo (isset($result[0]['LOAD_CODE']) ? $result[0]['LOAD_CODE'] : 0); ?>;
				console.log( 'load: ', load, ', code: ', code );
				if( code > 0 ) {
					$.ajax({
						url: 'exp_adddel_shipment_load.php',
						data: {
							load: load,
							empty: code,
							drop: encodeURIComponent($('select#IM_EMPTY_DROP').val()),
							pw: 'EggsToast'
						},
						dataType: "json",
						success: function(data) {
							console.log('exp_adddel_shipment_load ', data);
							$('#LAST_CHANGED').val(data);
						}
					});
				}
			});

			
			if( '<?php echo $detail_ro; ?>' == '' )
				$('.APPROVED_ONLY').prop('hidden', 'hidden');

			$('#EXP_SHIPMENT_LOAD').dataTable({
		        //"bLengthChange": false,
		        "bFilter": false,
		        "bSort": false,
		        "bInfo": false,
				//"bAutoWidth": false,
				//"bProcessing": true, 
				"sScrollX": "100%",
				"sScrollY": "300px",
				//"sScrollXInner": "120%",
		        //"lengthMenu": [[-1, 25, 50], ["All", 25, 50]],
				"bPaginate": false,
				"bScrollCollapse": true,
				"bSortClasses": false		
			});
			
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
				if( $('#SHIPPER_COUNTRY').val() == 'Canada' &&
					$('#CONS_COUNTRY').val() == 'Canada' ) {
					$('#CDN_TAX').prop('hidden',false);
					update_reason();
				} else {
					$('#CDN_TAX').prop('hidden', 'hidden');					
				}
			}

			$('#SHIPPER_COUNTRY, #CONS_COUNTRY').change(function () {
				//console.log('country changed');
				update_tax();
			});
			
			if( ! profiles ) {	// Disable buttons if you don't have permission
				$('#ADD_SHIPPER_CLIENT').addClass('disabled')
					.prop('title', 'Quick Add Shipper (disabled - profiles group needed)')
					.removeAttr('data-original-title')
					.tooltip('fixTitle');
				$('#ADD_CONS_CLIENT').addClass('disabled')
					.prop('title', 'Quick Add Shipper (disabled - profiles group needed)')
					.removeAttr('data-original-title')
					.tooltip('fixTitle');
			}
			
			function disable_asc() {
				if( $('#SHIPPER_NAME').val() != '' ) {
					$('#ADD_SHIPPER_CLIENT').removeClass('disabled')
						.prop('title', 'Quick Add Shipper')
						.removeAttr('data-original-title')
						.tooltip('fixTitle');
					console.log('disable_asc, enable');
				} else {
					$('#ADD_SHIPPER_CLIENT').addClass('disabled')
					.prop('title', 'Quick Add Shipper (disabled - no name)')
					.removeAttr('data-original-title')
					.tooltip('fixTitle');
					console.log('disable_asc, disable');
				}
			}
			
			$('#SHIPPER_NAME').on('keyup', function(event) {
				disable_asc();
			});
			disable_asc();
			
			function disable_acc() {
				if( $('#CONS_NAME').val() != '' ) {
					$('#ADD_CONS_CLIENT').removeClass('disabled')
						.prop('title', 'Quick Add Consignee')
						.removeAttr('data-original-title')
						.tooltip('fixTitle');
					console.log('disable_acc, enable');
				} else {
					$('#ADD_CONS_CLIENT').addClass('disabled')
						.prop('title', 'Quick Add Consignee (disabled - no name)')
						.removeAttr('data-original-title')
						.tooltip('fixTitle');
					console.log('disable_acc, disable');
				}
			}
			
			$('#CONS_NAME').on('keyup', function(event) {
				disable_acc();
			});
			disable_acc();
			
			$('#ADD_SHIPPER_CLIENT').on('click', function(event) {
				event.preventDefault();  //prevent form from submitting
				console.log('ADD_SHIPPER_CLIENT, diabled = ', $('#ADD_SHIPPER_CLIENT').hasClass('disabled') );
				if( ! $('#ADD_SHIPPER_CLIENT').hasClass('disabled') ) {
					$.ajax({
						//async: false,
						url: 'exp_ajax_addclient.php',
						data: {
							PW: 'DataOnFile',
							SHIPMENT: code,
							CTYPE: 'SHIPPER',
							NAME: $('input#SHIPPER_NAME').val(),
							ADDR1: $('input#SHIPPER_ADDR1').val(),
							ADDR2: $('input#SHIPPER_ADDR2').val(),
							CITY: $('input#SHIPPER_CITY').val(),
							STATE: $('select#SHIPPER_STATE').val(),
							ZIP: $('input#SHIPPER_ZIP').val(),
							COUNTRY: $('select#SHIPPER_COUNTRY').val(),
							PHONE: $('input#SHIPPER_PHONE').val(),
							EXT: $('input#SHIPPER_EXT').val(),
							FAX: $('input#SHIPPER_FAX').val(),
							CELL: $('input#SHIPPER_CELL').val(),
							CONTACT: $('input#SHIPPER_CONTACT').val(),
							EMAIL: $('input#SHIPPER_EMAIL').val(),
						},
						dataType: "json",
						success: function(data) {
							console.log( 'exp_ajax_addclient.php success, return ', data);
							$('#LAST_CHANGED').val(data.LAST_CHANGED);
							$('input#SHIPPER_CLIENT_CODE').val(data.CLIENT_CODE);
							if( data.CLIENT_CODE > 0 )
								$('#ADD_SHIPPER_CLIENT').removeClass('btn-default')
									.addClass('btn-success')
									.addClass('disabled')
									.prop('title', 'Quick Add Shipper (disabled - already saved)')
									.removeAttr('data-original-title')
									.tooltip('fixTitle');
							else
								$('#ADD_SHIPPER_CLIENT').removeClass('btn-default')
									.addClass('btn-danger')
									.addClass('disabled')
									.prop('title', 'Quick Add Shipper (disabled - error)')
									.removeAttr('data-original-title')
									.tooltip('fixTitle');
						}
					});
				}
			});

			$('#ADD_CONS_CLIENT').on('click', function(event) {
				event.preventDefault();  //prevent form from submitting
				console.log('ADD_CONS_CLIENT, diabled = ', $('#ADD_SHIPPER_CLIENT').hasClass('disabled') );
				if( ! $('#ADD_CONS_CLIENT').hasClass('disabled') ) {
					$.ajax({
						//async: false,
						url: 'exp_ajax_addclient.php',
						data: {
							PW: 'DataOnFile',
							SHIPMENT: code,
							CTYPE: 'CONSIGNEE',
							NAME: $('input#CONS_NAME').val(),
							ADDR1: $('input#CONS_ADDR1').val(),
							ADDR2: $('input#CONS_ADDR2').val(),
							CITY: $('input#CONS_CITY').val(),
							STATE: $('select#CONS_STATE').val(),
							ZIP: $('input#CONS_ZIP').val(),
							COUNTRY: $('select#CONS_COUNTRY').val(),
							PHONE: $('input#CONS_PHONE').val(),
							EXT: $('input#CONS_EXT').val(),
							FAX: $('input#CONS_FAX').val(),
							CELL: $('input#CONS_CELL').val(),
							CONTACT: $('input#CONS_CONTACT').val(),
							EMAIL: $('input#CONS_EMAIL').val(),
						},
						dataType: "json",
						success: function(data) {
							console.log( 'exp_ajax_addclient.php success, return ', data);
							$('#LAST_CHANGED').val(data.LAST_CHANGED);
							$('input#CONS_CLIENT_CODE').val(data.CLIENT_CODE);
							if( data.CLIENT_CODE > 0 )
								$('#ADD_CONS_CLIENT').removeClass('btn-default')
									.addClass('btn-success')
									.addClass('disabled')
									.prop('title', 'Quick Add Consignee (disabled - already saved)')
									.removeAttr('data-original-title')
									.tooltip('fixTitle');
							else
								$('#ADD_CONS_CLIENT').removeClass('btn-default')
									.addClass('btn-danger')
									.addClass('disabled')
									.prop('title', 'Quick Add Consignee (disabled - error)')
									.removeAttr('data-original-title')
									.tooltip('fixTitle');
						}
					});
				}
			});

			
			function update_billto_label() {
				if( $('#BILLTO_CLIENT_CODE').val() > 0 ) {
					$('#BILLTO_LABEL').html('<a href="exp_editclient.php?CODE=' + $('#BILLTO_CLIENT_CODE').val() + '" target="_blank">Bill-To</a>');
				} else {
					$('#BILLTO_LABEL').html('Bill-To');
				}
				console.log($('#BILLTO_LABEL').text());				
			}
			
			//! SCR# 606 - disable updates for billed shipments
			console.log('no_updates ', no_updates);
			if( ! no_updates ) {
				//update_miles();
				update_tax();
				update_billto_label();
			}
			update_pickup();
			update_deliver();
			
			function update_find_carrier() {
				$.ajax({
					//async: false,
					url: 'exp_find_carrier.php',
					data: {
						pw: 'Portable',
						sh: code,
						bc: $('#BUSINESS_CODE').val(),
					},
					dataType: "json",
					success: function(data) {
						//console.log( 'find_carrier success, return ', data);
						if( data ) {
							//console.log( 'find_carrier reveal' );
							$('#Find_Carrier').show();
						} else {
							//console.log( 'find_carrier hide' );
							$('#Find_Carrier').hide();
						}
					}
				});				
			}
			
			update_find_carrier();

			$('#BUSINESS_CODE').change(function () {
				update_find_carrier();
			});

			$('a[data-toggle="tab"]').on( 'shown.bs.tab', function (e) {
				$.fn.dataTable.tables( {visible: true, api: true} ).columns.adjust();
			} );

			$('a[data-toggle="tab"]').on("shown.bs.tab", function (e) {
			    var id = $(e.target).attr("href");
			    localStorage.setItem('shselectedTab', id)
			});
			
			var selectedTab = localStorage.getItem('shselectedTab');
			if (selectedTab != null) {
			    $('a[data-toggle="tab"][href="' + selectedTab + '"]').tab('show');
			}
			
			//! SCR# 466 - disable exempt if billed
			if( bstatus == 'billed' ) {
				$('#CDN_TAX_EXEMPT').bootstrapToggle('disable');
				$('#CDN_TAX_EXEMPT_REASON').prop('readonly', true);
			}

			if( false && window.HANDLE_RESIZE_EVENTS ) {
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
		

