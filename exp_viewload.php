<?php 

// $Id: exp_viewload.php 5142 2023-06-20 15:56:43Z dev $
// View Load screen, also various activities done on loads.
// LOAD_COMPLETE	- complete a load
// RECALC_MILES		- recalculate miles
// SEND_TRACE1		- send trace email with diags to expeedite support
// RENUMBER			- renumber stops
// MANIFEST			- resend manifest

// Set flag that this is a parent file
define( '_STS_INCLUDE', 1 );

// Setup Session
require_once( "include/sts_session_setup.php" );

require_once( "include/sts_config.php" );
if( isset($_POST['SEND_TRACE2']) ) {
	set_time_limit(0);
	ini_set('memory_limit', '1024M');
}
$sts_debug = (isset($_GET['debug'])  || isset($_POST['debug']))  && in_group( EXT_GROUP_DEBUG );
require_once( "include/sts_session_class.php" );

if( $sts_debug ) {
	// If you have debug on, it will reload the page if it does not see the character
	// set information within the first 1024 chars. This fixes that.
	echo '<html lang="en">
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
	</head>
	<body>
	';
}

$my_session = sts_session::getInstance( $exspeedite_db, $sts_debug );
$my_session->access_check( $sts_table_access[LOAD_TABLE], EXT_GROUP_DRIVER );	// Make sure we should be here

$sts_subtitle = "View Load";
require_once( "include/sts_setting_class.php" );
$setting_table = sts_setting::getInstance($exspeedite_db, $sts_debug);

//! SCR# 849 - Team Driver Feature
$sts_team_driver = $setting_table->get( 'option', 'TEAM_DRIVER' ) == 'true';

if( ! isset($_GET['LOAD_COMPLETE']) ) {
	//! SCR# 298 Set flag to include JQuery Location Picker
	define( '_STS_LOCATION', 1 );
	$sts_google_api_key = $setting_table->get( 'api', 'GOOGLE_API_KEY' );
	require_once( "include/header_inc.php" );
	
	if( ! ($sts_debug && isset($_GET['RECALC_MILES'])) )
		require_once( "include/navbar_inc.php" );
}
?>

<div class="container-full" role="main">

<!--
<div class="alert alert-warning alert-dismissable">
  <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
  <strong>Warning!</strong> Work in progress.
</div>
-->

<div class="well  well-lg">

<?php

require_once( "include/sts_result_class.php" );
require_once( "include/sts_form_class.php" );

require_once( "include/sts_stop_class.php" );
require_once( "include/sts_shipment_class.php" );
require_once( "include/sts_load_class.php" );
require_once( "include/sts_carrier_class.php" );
require_once( "include/sts_status_class.php" );
require_once( "include/sts_repair_class.php" );
require_once( "include/sts_attachment_class.php" );

unset($_SESSION["BACK"]);

// close the session here to avoid blocking
session_write_close();

//! SCR# 712 - Email queue implementation
$sts_email_queueing = $setting_table->get( 'option', 'EMAIL_QUEUEING' ) == 'true';

$shipment_table = sts_shipment::getInstance($exspeedite_db, $sts_debug);
$sts_multi_currency = $setting_table->get( 'option', 'MULTI_CURRENCY' ) == 'true';

//! SCR# 371 - check if it is an office number
if( $sts_multi_currency && isset($_GET['CODE']) && ! is_numeric($_GET['CODE'])) {
	$chk = $shipment_table->fetch_rows("SS_NUMBER = '".$_GET['CODE']."'", "LOAD_CODE");
	if( is_array($chk) && count($chk) == 1 &&
		 isset($chk[0]["LOAD_CODE"]) && $chk[0]["LOAD_CODE"] > 0 &&
		 $chk[0]["LOAD_CODE"] <> $_GET['CODE'] )
		reload_page ( "exp_viewload.php?CODE=".$chk[0]["LOAD_CODE"] );
}

if( (isset($_POST['CODE']) && intval($_POST['CODE']) > 0 && isset($_POST['SEND_TRACE2']) )
	|| ( isset($_GET['CODE']) && intval($_GET['CODE']) > 0 ) ) {

	//! Testing debug
	if( isset($_GET['TAIL']) ) {
		require_once( "include/sts_email_class.php" );
		require_once( "include/sts_setting_class.php" );
		
		$setting_table = sts_setting::getInstance($exspeedite_db, $sts_debug);
		$sts_qb_log_file = $setting_table->get( 'api', 'QUICKBOOKS_LOG_FILE' );
		
		$email = sts_email::getInstance($exspeedite_db, $sts_debug);
		//echo "<pre>".htmlentities($email->tailCustom($sts_qb_log_file, 200))."</pre>";
		echo $email->load_history($_GET['CODE']);


	} else
	//! Testing list stops
	if( isset($_GET['STOPS']) ) {
		$stop_table = sts_stop::getInstance($exspeedite_db, $sts_debug);
		$line = $stop_table->list_stops( $_GET['CODE'] );
		echo $line;
		$email = sts_email::getInstance($exspeedite_db, $sts_debug);
		echo $email->load_stops( $_GET['CODE'] );
	} else
	//! Testing link
	if( isset($_GET['LINK']) ) {
		echo '<p><a href="exp_viewload.php?CODE=">here</a></p>';
	} else
	
	//! Testing sts_shipment_load class
	if( isset($_GET['SLC']) ) {
		require_once( "include/sts_shipment_load_class.php" );
		$shipment_load_table = sts_shipment_load::getInstance($exspeedite_db, $sts_debug);
		//$result = $shipment_load_table->add_link( 177, 52 );
		$result = $shipment_load_table->get_links( $_GET['SLC'] );
	echo "<pre>";
	var_dump($result);
	echo "</pre>";

		$result2 = $shipment_load_table->last_load( $_GET['SLC'] );
	echo "<pre>";
	var_dump($result2);
	echo "</pre>";



	} else
	//! Test
	if( isset($_GET['MULTI']) ) {
		$stop_table = sts_stop::getInstance($exspeedite_db, $sts_debug);
		$result = $stop_table->multi_stop_update_actuals($_GET['CODE']);
		if( ! $sts_debug )
			reload_page ( "exp_viewload.php?CODE=".$_GET['CODE'] );


	} else
	//! Complete a load
	if( isset($_GET['LOAD_COMPLETE']) ) {
		if( false && isset($_GET['RETURN']) ) {
				echo "<pre>";
				var_dump($_GET);
				echo "</pre>";
				die;
		}
		echo '<p class="text-center"><img src="images/loading.gif" alt="loading" width="125" height="125" /></p>';
		$load_table = sts_load::getInstance($exspeedite_db, $sts_debug);
		$result = $load_table->load_complete($_GET['CODE']);
		// die; // remove this once fixed.
		if( ! $sts_debug ) {
			if( isset($_GET['RETURN']) )
				reload_page ( $_GET['RETURN']."?CODE=".$_GET['CODE'] );
			else
				reload_page ( "exp_viewload.php?CODE=".$_GET['CODE'] );
		} else {
			die;
		}

	} else
	//! Send Trace 1
	if( isset($_GET['SEND_TRACE1']) ) {
				echo '<div class="container-full">
				<form role="form" class="form-horizontal" action="exp_viewload.php" 
						method="post" enctype="multipart/form-data" 
						name="editstop" id="editstop">
				'.($sts_debug ? '<input name="debug" type="hidden" value="true">' : '').'
				<input name="CODE" type="hidden" value="'.$_GET['CODE'].'">
				<input name="SEND_TRACE2" type="hidden" value="true">
				<h2><img src="images/load_icon.png" alt="load_icon" height="24"> Load '.$_GET['CODE'].' - Send Trace To Support</h2>
				<h3>Please enter description of the issue with this load. Include any steps taken, expected results.</h3>
				
				<div class="form-group">
					<div class="col-sm-7">
						<textarea class="form-control" name="DESCRIPTION" id="DESCRIPTION" rows="5" autofocus></textarea>
					</div>
					<div class="col-sm-5">
					<div class="btn-group">
						<button class="btn btn-md btn-danger" name="save" type="submit" ><span class="glyphicon glyphicon-ok"></span> Send To Exspeedite Support</button>
						<a class="btn btn-md btn-default"  href="exp_viewload.php?CODE='.$_GET['CODE'].'"><span class="glyphicon glyphicon-remove"></span> Cancel</a>
					</div>
					</div>
				</div>
				</form>
				</div>
			
					';

	} else
	//! Send Trace 2
	if( isset($_POST['SEND_TRACE2']) ) {
		require_once( "include/sts_email_class.php" );

		$email = sts_email::getInstance($exspeedite_db, $sts_debug);

		$email->send_alert('viewload: Trace of Load #'.$_POST['CODE'].
		'<br>Description: '.$_POST['DESCRIPTION'].
		'<br>'.$email->load_stops($_POST['CODE']).
		'<br>'.$email->load_history($_POST['CODE']).
		'<br>'.$email->shipment_histories( $_POST['CODE'] ), 
		EXT_ERROR_TRACE, $email->trace_subject.$_POST['CODE'] );

		if( ! $sts_debug )
			reload_page ( "exp_viewload.php?CODE=".$_POST['CODE'] );
	} else
	//! Renumber stops
	if( isset($_GET['RENUMBER']) ) {
		$stop_table = sts_stop::getInstance($exspeedite_db, $sts_debug);
		$result = $stop_table->renumber($_GET['CODE']);
		if( ! $sts_debug )
			reload_page ( "exp_viewload.php?CODE=".$_GET['CODE'] );


	} else
	//! Recalc miles
	if( isset($_GET['RECALC_MILES']) ) {
		$load_table = sts_load::getInstance($exspeedite_db, $sts_debug);
		set_time_limit(0);
		echo '<p class="text-center"><img src="images/loading.gif" alt="loading" width="125" height="125" /><br>Recalculating Miles...</p>';
		flush();
		ob_flush();

		$load_table->update_distances( $_GET['CODE'] );
		if( ! $sts_debug )
			reload_page ( "exp_viewload.php?CODE=".$_GET['CODE'] );

	} else
	//! Re-send manifest
	if( isset($_GET['MANIFEST']) ) {

		$load_table = sts_load::getInstance($exspeedite_db, $sts_debug);
		$changes = array( 'EMAIL_CC_USER' => 0 );
		$result = $load_table->update( $_GET['CODE'], $changes );

		$email_type = 'manifestr';
		$email_code = $_GET['CODE'];
		require_once( "exp_spawn_send_email.php" );		// Background send

		if( ! $sts_debug )
			reload_page ( "exp_viewload.php?CODE=".$_GET['CODE'] );

	} else {

	$sts_intermodal_fields = $setting_table->get( 'option', 'INTERMODAL_FIELDS' ) == 'true';
	$sts_export_qb = $setting_table->get( 'api', 'EXPORT_QUICKBOOKS' ) == 'true';
	$sts_export_sage50 = $setting_table->get( 'api', 'EXPORT_SAGE50_CSV' ) == 'true';
	$sts_attachments = $setting_table->get( 'option', 'ATTACHMENTS_ENABLED' ) == 'true';
	$multi_company = ($setting_table->get("option", "MULTI_COMPANY") == 'true');
	
	//! Fix for SCR# 92 - work our where we came from and go back there.
	//! Fix for SCR# 269 - remove restriction
	if( ! empty($_SERVER["HTTP_REFERER"]) ) {
		$path = explode('/', $_SERVER["HTTP_REFERER"]); 
		$referer = end($path);
		//! SCR# 469 - don't use referrer if it was check call
		if( $referer <> 'exp_add_check_call.php' && $referer <> 'exp_editcarrier_amt.php' )
			$sts_form_viewload_form['cancel'] = $referer;
	}

	//! If not using multi-currency, hide Currency
	if( ! $sts_multi_currency ) {
		$match = preg_quote('<!-- CURRENCY1 -->').'(.*)'.preg_quote('<!-- CURRENCY2 -->');
		$sts_form_viewload_form['layout'] = preg_replace('/'.$match.'/s', '',
			$sts_form_viewload_form['layout'], 1);	
		$match = preg_quote('<!-- CURRENCY3 -->').'(.*)'.preg_quote('<!-- CURRENCY4 -->');
		$sts_form_viewload_form['layout'] = preg_replace('/'.$match.'/s', '',
			$sts_form_viewload_form['layout'], 1);	
	}
	
	//! SCR# 849 - Team Driver Feature
	if( ! $sts_team_driver ) {
		$match = preg_quote('<!-- DRIVER21 -->').'(.*)'.preg_quote('<!-- DRIVER22 -->');
		$sts_form_viewload_form['layout'] = preg_replace('/'.$match.'/s', '',
			$sts_form_viewload_form['layout'], 1);	
	}

	//! If not using Sage 50, hide Export Status
	if( ! $sts_export_sage50 ) {
		//! Remove all quickbooks fields
		$match = preg_quote('<!-- SAGE501 -->').'(.*)'.preg_quote('<!-- SAGE502 -->');
		$sts_form_viewload_form['layout'] = preg_replace('/'.$match.'/s', '',
			$sts_form_viewload_form['layout'], 1);	
	}

	//! If not using quickbooks, hide Export Status
	if( ! $sts_export_qb ) {
		$match = preg_quote('<!-- QUICKBOOKS1 -->').'(.*)'.preg_quote('<!-- QUICKBOOKS2 -->');
		$sts_form_viewload_form['layout'] = preg_replace('/'.$match.'/s', '',
			$sts_form_viewload_form['layout'], 1);	

	}

	// Turn off output buffering
	ini_set('output_buffering', 'off');
	// Implicitly flush the buffer(s)
	ini_set('implicit_flush', true);
	ob_implicit_flush(true);

	if (ob_get_level() == 0) ob_start();

	//! New Repair class - perform repairs
	$repair = sts_repair::getInstance( $exspeedite_db, $sts_debug );
	$repair->repair_issue();

	$load_table = sts_load::getInstance($exspeedite_db, $sts_debug);
	
	// To test sts_load::carrier_transaction_date()
	//echo "<p>DT = ".$load_table->carrier_transaction_date($_GET['CODE'])."</p>";
	
	//! Check user has access to shipment

	$stat = $load_table->fetch_rows($load_table->primary_key.' = '.$_GET['CODE'].
	($multi_company && ! $my_session->in_group(EXT_GROUP_ADMIN) ?
		" AND OFFICE_CODE IN (SELECT OFFICE_CODE FROM EXP_USER_OFFICE
		WHERE USER_CODE = ".$_SESSION["EXT_USER_CODE"].")" : ""), "CURRENT_STATUS, 
		CARRIER, CURRENT_STOP, quickbooks_status_message, DRIVER2,
		LOAD_OFFICE_NUM(LOAD_CODE) AS SS_NUMBER2,
		(SELECT COUNT(*) AS NUM_STOPS
			FROM EXP_STOP
			WHERE EXP_LOAD.LOAD_CODE = EXP_STOP.LOAD_CODE) AS NUM_STOPS,
		(SELECT STOP_TYPE AS CURRENT_STOP_TYPE
			FROM EXP_STOP
			WHERE EXP_LOAD.LOAD_CODE = EXP_STOP.LOAD_CODE
			AND SEQUENCE_NO = CURRENT_STOP) AS CURRENT_STOP_TYPE,
		(SELECT CURRENT_STATUS
			FROM EXP_STOP
			WHERE EXP_LOAD.LOAD_CODE = EXP_STOP.LOAD_CODE
			AND SEQUENCE_NO = CURRENT_STOP) AS STOP_STATUS,
		EDI_204_PRIMARY,
		(SELECT EDI_204_B204_SID 
			FROM EXP_SHIPMENT
			WHERE SHIPMENT_CODE = EXP_LOAD.EDI_204_PRIMARY) AS EDI_204_B204_SID,
		(SELECT EDI_990_STATUS 
			FROM EXP_SHIPMENT
			WHERE SHIPMENT_CODE = EXP_LOAD.EDI_204_PRIMARY) AS EDI_990_STATUS,
		(SELECT EDI_204_G6202_EXPIRES 
			FROM EXP_SHIPMENT
			WHERE SHIPMENT_CODE = EXP_LOAD.EDI_204_PRIMARY) AS EDI_204_G6202_EXPIRES");
	
	//! Check for multi-company
	if( ! $multi_company ) {
		$match = preg_quote('<!-- CC01 -->').'(.*)'.preg_quote('<!-- CC02 -->');
		$sts_form_viewload_form['layout'] = preg_replace('/'.$match.'/s', '',
			$sts_form_viewload_form['layout'], 1);
		unset($sts_result_stops_lj_layout['SS_NUMBER']);
		unset($sts_result_shipments_layout['SS_NUMBER']);
			
	}
	
	//! SCR# 849 - Team Driver Feature
	if( ! $sts_team_driver || ! isset($stat[0]['DRIVER2'])) {
		$match = preg_quote('<!-- DRIVER21 -->').'(.*)'.preg_quote('<!-- DRIVER22 -->');
		$sts_form_viewload_form['layout'] = preg_replace('/'.$match.'/s', '',
			$sts_form_viewload_form['layout'], 1);	
	}

	if( is_array($stat) && count($stat) > 0 && isset($stat[0]['CURRENT_STATUS']) &&
		in_array($load_table->state_behavior[$stat[0]['CURRENT_STATUS']], 
			array('entry', 'manifest', 'dispatch', 'arrive shipper', 'depart shipper',
				'arrive cons', 'depart cons', 'complete')) )
		$sts_form_viewload_form['layout'] = str_replace( '+EDIT_AMOUNTS+', 
			'<a class="btn btn-sm btn-default" href="exp_editcarrier_amt.php?CODE='.$_GET['CODE'].'"><span class="glyphicon glyphicon-edit"></span></a>&nbsp;&nbsp;&nbsp;', $sts_form_viewload_form['layout']);
	else
		$sts_form_viewload_form['layout'] = str_replace( '+EDIT_AMOUNTS+','',$sts_form_viewload_form['layout']);

	/* Try later
	if( is_array($stat) && isset($stat[0]['CARRIER']) && $stat[0]['CARRIER'] > 0 &&
		$load_table->state_name[$stat[0]['CURRENT_STATUS']] == 'Entered' ) {
		$manifest_state = 0;
		foreach( $load_table->state_name as $state => $name ) {
			if( $name == 'Send Freight Agreement' ) {
				$manifest_state = $state;
				break;
			}
		}
		if( $manifest_state > 0 && $load_table->state_change_ok( $_GET['CODE'], $stat[0]['CURRENT_STATUS'],
			$manifest_state, $stat[0]['CURRENT_STOP'], $stat[0]['NUM_STOPS'],
			$stat[0]['CURRENT_STOP_TYPE'] )) {
			$sts_form_viewload_form['buttons'] = array( 
				array( 'label' => 'Send Freight Agreement', 'link' => 'exp_load_state.php?CODE='.
					$_GET['CODE'].'&STATE='.$manifest_state,
				'button' => 'danger', 'icon' => '<span class="glyphicon glyphicon-ok"></span>' )
				);
		} else if( $manifest_state > 0 ) {
		}
	} else
	*/
	if( $my_session->in_group( EXT_GROUP_MANAGER ) ) {
		if( is_array($stat) && count($stat) > 0 &&
			! in_array($load_table->state_behavior[$stat[0]['CURRENT_STATUS']],
				array('cancel', 'oapproved', 'approved', 'billed'))) {

			$sts_form_viewload_form['buttons'][] = 
				array( 'label' => 'Recalc', 
				'link' => 'exp_viewload.php?CODE='.$_GET['CODE'].'&RECALC_MILES',
				'button' => 'danger', 'icon' => '<span class="glyphicon glyphicon-road"></span>' );
			$sts_form_viewload_form['buttons'][] = 
				array( 'label' => 'Back Up', 
				'link' => 'exp_backup.php?CODE='.$_GET['CODE'],
				'button' => 'danger', 'icon' => '<span class="glyphicon glyphicon-backward"></span>' );
		}
		if( $load_table->can_load_complete( $_GET['CODE'] ) ) {
			$sts_form_viewload_form['buttons'][] = 
				array( 'label' => 'Complete', 'confirm' => true,
				'link' => 'exp_viewload.php?CODE='.$_GET['CODE'].'&LOAD_COMPLETE',
				'tip' => 'Promote this load to complete status.<br>Skip all intermediate steps.<br>Does not record actual arrive/depart dates.<br>Will free up driver and update trailer location to final destination.<br><br><strong>Issues:</strong> Backing up may not undo all of this.<br><br><strong>Use with caution</strong>',
				'button' => 'danger', 'icon' => '<span class="glyphicon glyphicon-forward"></span>' );
		}
		
		$setting_table = sts_setting::getInstance($exspeedite_db, $sts_debug);
		$send_trace = ($setting_table->get( 'option', 'ENABLE_SEND_TRACE' ) == 'true');

		if( $send_trace )
			$sts_form_viewload_form['buttons'][] = 
				array( 'label' => 'Support', 
				'link' => 'exp_viewload.php?CODE='.$_GET['CODE'].'&SEND_TRACE1',
				//'confirm' => true,
				'tip' => 'Send a trace of this load to Strongtower to examine.',
				'button' => 'danger', 'icon' => '<span class="glyphicon glyphicon-envelope"></span>' );

		$stop_table = sts_stop::getInstance($exspeedite_db, $sts_debug);
		if( $stop_table->check_sequence( $_GET['CODE'] ) ) {
			$sts_form_viewload_form['buttons'][] = 
				array( 'label' => 'Renumber', 
				'link' => 'exp_viewload.php?CODE='.$_GET['CODE'].'&RENUMBER',
				'button' => 'danger', 'icon' => '<span class="glyphicon glyphicon-sort-by-attributes"></span>' );			
		}
	}

	if( is_array($stat) && isset($stat[0]['EDI_204_PRIMARY']) && $stat[0]['EDI_204_PRIMARY'] > 0 ) {

		if( $sts_debug ) {
				echo "<p>XYZZY</p><pre>";
				var_dump($stat[0]['EDI_990_STATUS']);
				var_dump($stat[0]['EDI_204_B204_SID']);
				var_dump($stat[0]['EDI_204_G6202_EXPIRES']);
				var_dump(time() > strtotime($stat[0]['EDI_204_G6202_EXPIRES']));
				echo "</pre>";
			
		}
		if( isset($stat[0]['EDI_990_STATUS']) && $stat[0]['EDI_990_STATUS'] == 'Pending' &&
			isset($stat[0]['EDI_204_B204_SID']) &&
			isset($stat[0]['EDI_204_G6202_EXPIRES']) && 
			time() > strtotime($stat[0]['EDI_204_G6202_EXPIRES']) ) {
			//! Cancel the load & shipments
			require_once( "sts_edi_map_class.php" );
			$edi = sts_edi_map::getInstance($exspeedite_db, $sts_debug);
			$edi->cancel( $stat[0]['EDI_204_B204_SID'], 'Expired' );
		}
		
		$check_edi = $load_table->database->get_multiple_rows("select EDI_CODE
			FROM EXP_EDI
			WHERE EDI_204_PRIMARY = ".$stat[0]['EDI_204_PRIMARY']);

		if( is_array($check_edi) && count($check_edi) > 0 &&
			$my_session->in_group(EXT_GROUP_ADMIN) )
			$info_link = '<br><span class="glyphicon glyphicon-transfer"></span> <a href="exp_view_edi.php?pw=ChessClub&code='.$stat[0]['EDI_204_PRIMARY'].'" target="_blank">EDI Information</a>';
		else
			$info_link = '';
			
		$check_status = $load_table->state_behavior[$stat[0]['CURRENT_STATUS']];
		if( $stat[0]['EDI_990_STATUS'] == 'Pending' && $check_status == 'imported' ) {
					
			$msg = '<a class="btn btn-sm btn-danger" 
			onclick="confirmation(\'Confirm: Cancel Load '.$_GET['CODE'].'<br><br>This will also decline the 204 load offer\', \'exp_load_state.php?STATE='.$load_table->behavior_state['cancel'].'&CODE='.$_GET['CODE'].'&CSTOP='.$stat[0]['CURRENT_STOP'].'&CSTATE='.$stat[0]['CURRENT_STATUS'].'\')"><span class="glyphicon glyphicon-remove"></span> Cancel load '.$_GET['CODE'].'</a>&nbsp;=&nbsp;Decline 204, 
			<a class="btn btn-sm btn-success" href="exp_load_state.php?STATE='.$load_table->behavior_state['accepted'].'&CODE='.$_GET['CODE'].'&CSTOP='.$stat[0]['CURRENT_STOP'].'&CSTATE='.$stat[0]['CURRENT_STATUS'].'">Accept 204</a>&nbsp;=&nbsp;Accept 204';

			
			$sts_form_addshipment_204 = str_replace('<!-- 990_INFO_HERE -->', $msg.$info_link, $sts_form_addshipment_204);
		} else {
			$sts_form_addshipment_204 = str_replace('<!-- 990_INFO_HERE -->', $info_link, $sts_form_addshipment_204);					
			
		}

		$shipments = $shipment_table->fetch_rows("EDI_204_PRIMARY = ".$stat[0]['EDI_204_PRIMARY']);
		$edi_shipments = array();
		if( is_array($shipments)) {
			foreach($shipments as $s) { 
				$edi_shipments[] = '<a href="exp_addshipment.php?CODE='.$s["SHIPMENT_CODE"].'" class="alert-link">'.$s["SHIPMENT_CODE"].'</a>';
			}
			$msg = plural(count($edi_shipments), 'Shipment').' ('.implode(', ', $edi_shipments).')';
			$sts_form_addshipment_204 = str_replace('##SHIPMENTS##', $msg, $sts_form_addshipment_204);
		} else
			$sts_form_addshipment_204 = str_replace('##SHIPMENTS##', '', $sts_form_addshipment_204);

		$sts_form_viewload_form['layout'] = str_replace('<!-- 204_INFO_HERE -->', $sts_form_addshipment_204, $sts_form_viewload_form['layout']);
	}
	
	if( is_array($stat) && isset($stat[0]['CARRIER']) && $stat[0]['CARRIER'] > 0 ) {
		
		//! Hide the non-carrier details
		$match = preg_quote('<!-- NOTCARRIER1 -->').'(.*)'.preg_quote('<!-- NOTCARRIER2 -->');
		$sts_form_viewload_form['layout'] = preg_replace('/'.$match.'/s', '',
			$sts_form_viewload_form['layout'], 1);	
		
		//! Double check this
		if( $my_session->in_group( EXT_GROUP_FINANCE ) &&
			is_array($stat) && count($stat) > 0 &&
			in_array($load_table->state_behavior[$stat[0]['CURRENT_STATUS']],
				array('oapproved', 'approved', 'billed'))) {
			$sts_form_viewload_form['buttons'][] =  
				array( 'label' => 'Unapprove', 'confirm' => true, 'tip' => 'This returns the load to the Completed state.<br>You can then edit &amp; make adjustments before you approve again.',
				'tip' => 'Return the load to the Completed state.',
				'link' => 'exp_load_state.php?STATE='.$load_table->behavior_state['complete'].'&CODE='.
					$_GET['CODE'].'&CSTOP='.$stat[0]['CURRENT_STOP'].'&CSTATE='.$stat[0]['CURRENT_STATUS'], 'button' => 'danger', 'icon' => '<span class="glyphicon glyphicon-backward"></span>' );
		}

		$carrier_table = sts_carrier::getInstance($exspeedite_db, $sts_debug);
		$warnings = $carrier_table->check_suitable( $_GET['CODE'], $stat[0]['CARRIER'] );
		if( $warnings <> '' )
			$sts_form_viewload_form['layout'] = str_replace('<!-- CARRIER1E -->', $warnings, $sts_form_viewload_form['layout']);

		$sts_form_viewload_form['buttons'][] =  
			array( 'label' => 'View', 'link' => 'exp_viewmanifest.php?CODE='.
				$_GET['CODE'], 'blank' => true, 'button' => 'default', 
				'tip' => 'View driver/carrier manifest.',
				'icon' => '<span class="glyphicon glyphicon-list-alt"></span>' );

		$sts_form_viewload_form['buttons'][] =  
			array( 'label' => 'Resend',
			'link' => 'exp_viewload.php?CODE='.$_GET['CODE'].'&MANIFEST', 
			'tip' => 'Resend driver/carrier manifest via email.',
			'button' => 'default', 'icon' => '<span class="glyphicon glyphicon-envelope"></span>' );

		$status = $load_table->state_behavior[$stat[0]['CURRENT_STATUS']];
		if( in_array($status, array('complete') ) &&
			$multi_company && $my_session->in_group(EXT_GROUP_BILLING) ) {
			$sts_form_viewload_form['buttons'][] =  
				array( 'label' => 'Approve (Office)', 'link' => 'exp_load_state.php?CODE='.
					$_GET['CODE'].'&STATE='.$load_table->behavior_state['oapproved'].
					'&CSTOP='.$stat[0]['CURRENT_STOP'].'&CSTATE='.$stat[0]['CURRENT_STATUS'],
				'tip' => 'Approve load at the office level.',
				'button' => 'danger', 'icon' => '<span class="glyphicon glyphicon-ok"></span>' );
		}
		if( in_array($status, array('complete', 'oapproved') ) &&
			$my_session->in_group(EXT_GROUP_FINANCE) ) {
			$sts_form_viewload_form['buttons'][] =  
				array( 'label' => 'Approve (Finance)', 'link' => 'exp_load_state.php?CODE='.
					$_GET['CODE'].'&STATE='.$load_table->behavior_state['approved'].
					'&CSTOP='.$stat[0]['CURRENT_STOP'].'&CSTATE='.$stat[0]['CURRENT_STATUS'],
				'tip' => 'Approve load at the finance level.',
				'button' => 'danger', 'icon' => '<span class="glyphicon glyphicon-ok"></span>' );
		}
		if( $load_table->state_behavior[$stat[0]['CURRENT_STATUS']] == 'approved' &&
			$my_session->in_group(EXT_GROUP_SAGE50) && $sts_export_sage50 ) {
			$sts_form_viewload_form['buttons'][] =  
				array( 'label' => 'Sage 50',
				'link' => 'exp_export_csv.php?pw=GoldUltimate&type=bill&code='.$_GET['CODE'],
				'button' => 'danger', 'icon' => '<span class="glyphicon glyphicon-list-alt"></span> <span class="glyphicon glyphicon-arrow-right"></span>',
				'confirm' => true,
				'tip' => 'This creates a CSV file for import to Sage 50.<br>In Sage 50, select <strong>File -> Select Import/Export...</strong><br>Click on <strong>Accounts Payable</strong> and then <strong>Import</strong><br>Make sure all the columns are in the correct order in Sage 50 during import.<br>The approved bills will be marked as paid in Exspeedite.'  );
		}
		
		if( in_array($load_table->state_behavior[$stat[0]['CURRENT_STATUS']], array('approved', 'billed')) &&
			$sts_export_qb ) {
				$sts_form_viewload_form['buttons'][] =  
					array( 'label' => 'Resend to QB', 'link' => 'exp_qb_retry.php?TYPE=load&CODE='.
						$_GET['CODE'], 'button' => 'danger', 'icon' => '<span class="glyphicon glyphicon-ok"></span>' );
		}
	} else {
		$driver_manifest = $setting_table->get( 'option', 'DRMANIFEST_ENABLED' ) == 'true';

		if($driver_manifest) {
			$sts_form_viewload_form['buttons'][] =  
				array( 'label' => 'View', 'link' => 'exp_viewmanifest.php?CODE='.
					$_GET['CODE'], 'blank' => true, 'button' => 'default', 'icon' => '<span class="glyphicon glyphicon-list-alt"></span>' );
	
			$sts_form_viewload_form['buttons'][] =  
				array( 'label' => 'Resend',
				'link' => 'exp_viewload.php?CODE='.$_GET['CODE'].'&MANIFEST', 
				'button' => 'default', 'icon' => '<span class="glyphicon glyphicon-envelope"></span>' );
			//! Button for edit driver notes
			$sts_form_viewload_form['layout'] = str_replace( '#CARRIER_NOTE#', 
			'<a class="btn btn-sm btn-default" href="exp_editcarrier_amt.php?CODE='.$_GET['CODE'].'"><span class="glyphicon glyphicon-edit"></span></a>&nbsp;#CARRIER_NOTE#', $sts_form_viewload_form['layout']);
		}

	//! Hide the carrier details
		$match = preg_quote('<!-- CARRIER1A -->').'(.*)'.preg_quote('<!-- CARRIER2A -->');
		$sts_form_viewload_form['layout'] = preg_replace('/'.$match.'/s', '',
			$sts_form_viewload_form['layout'], 1);	
		$match = preg_quote('<!-- CARRIER1B -->').'(.*)'.preg_quote('<!-- CARRIER2B -->');
		$sts_form_viewload_form['layout'] = preg_replace('/'.$match.'/s', '',
			$sts_form_viewload_form['layout'], 1);	
		$match = preg_quote('<!-- CARRIER1C -->').'(.*)'.preg_quote('<!-- CARRIER2C -->');
		$sts_form_viewload_form['layout'] = preg_replace('/'.$match.'/s', '',
			$sts_form_viewload_form['layout'], 1);	
		$match = preg_quote('<!-- CARRIER1D -->').'(.*)'.preg_quote('<!-- CARRIER2D -->');
		$sts_form_viewload_form['layout'] = preg_replace('/'.$match.'/s', '',
			$sts_form_viewload_form['layout'], 1);	
	}

	//! SCR# 639 - show/hide C-TPAT logo
	$ctpat = $exspeedite_db->get_one_row("
		SELECT SUM(COALESCE(CTPAT_REQUIRED,0)) AS CTPAT_REQUIRED
		FROM EXP_SHIPMENT, EXP_CLIENT
		WHERE BILLTO_CLIENT_CODE = CLIENT_CODE
		AND LOAD_CODE = ".$_GET['CODE']);
	
	if( is_array($ctpat) && isset($ctpat["CTPAT_REQUIRED"]) && ! $ctpat["CTPAT_REQUIRED"] ) {
		$match = preg_quote('<!-- CTPAT01 -->').'(.*)'.preg_quote('<!-- CTPAT02 -->');
		$sts_form_viewload_form['layout'] = preg_replace('/'.$match.'/s', '',
			$sts_form_viewload_form['layout'], 1);	
	}

	//! Replace Load number with dropdown menu, like in list load screen		
	if( is_array($stat) && count($stat) > 0 && isset($stat[0]['CURRENT_STATUS']) ) {
		$rslt1 = new sts_result( $load_table, "LOAD_CODE = ".$_GET['CODE'], $sts_debug, false );
	
		//! SCR# 302 - include office number
		$dd_menu = $rslt1->dropdown_menu($_GET['CODE'], $sts_result_loads_edit['rowbuttons'],
			$stat[0]['CURRENT_STATUS'], $stat[0]['CURRENT_STOP'],
			$stat[0]['STOP_STATUS'], $stat[0]['NUM_STOPS'], $stat[0]['CURRENT_STOP_TYPE'], $stat[0], false);
			
		$sts_form_viewload_form['title'] = preg_replace('/\%LOAD_CODE\%/', $dd_menu, $sts_form_viewload_form['title'], 1);
	}
	
	if( $my_session->in_group(EXT_GROUP_DRIVER) ) { //! DRIVER REMOVE STUFF
		$sts_form_viewload_form['cancel'] = 'exp_listload_driver.php';
		unset($sts_result_stops_lj_layout['SHIPMENT2']['link'],
			$sts_result_shipments_layout['SHIPMENT_CODE']['link'],
			$sts_result_status_layout['SHIPMENT']['link'],
			$sts_result_shipments_layout['TOTAL1'],
			$sts_result_shipments_layout['CONSOLIDATE_NUM']['link']);
	}

	$load_form = new sts_form( $sts_form_viewload_form, $sts_form_view_load_fields, $load_table, $sts_debug);
	$result = $load_table->fetch_rows("LOAD_CODE = ".$_GET['CODE'].
	($multi_company && ! $my_session->in_group(EXT_GROUP_ADMIN) ?
		" AND OFFICE_CODE IN (SELECT OFFICE_CODE FROM EXP_USER_OFFICE
		WHERE USER_CODE = ".$_SESSION["EXT_USER_CODE"].")" : ""), "*,
	(SELECT CASE WHEN CTPAT_CERTIFIED THEN
	'<span class=\"badge\" title=\"C-TPAT Certified\"><span class=\"glyphicon glyphicon-ok\"></span> C-TPAT</span>'
	ELSE '' END FROM EXP_CARRIER WHERE CARRIER = CARRIER_CODE) AS CTPAT_CERTIFIED");
	
	if( isset($result) && isset($result[0]) && isset($result[0]["DRIVER"]) && $result[0]["DRIVER"] > 0 ) {
		//! Get driver label
		$result2 = $load_table->database->get_one_row(
			"SELECT LABEL AS DRIVER_LABEL
			FROM ".CONTACT_INFO_TABLE."
			WHERE CONTACT_CODE = ".$result[0]["DRIVER"]."
			AND CONTACT_SOURCE = 'driver'
			AND CONTACT_TYPE = 'individual'
			AND ISDELETED = 0
			AND COALESCE(LABEL,'') <> ''
			LIMIT 1");
		if( isset($result2) && isset($result2["DRIVER_LABEL"]) && $result2["DRIVER_LABEL"] <> '') {
			if( $sts_debug ) echo "<p>DRIVER_LABEL = ".$result2["DRIVER_LABEL"]."</p>";
			$result[0]["DRIVER_LABEL"] = '(Known as '.$result2["DRIVER_LABEL"].')';
		}
	}
	if( isset($result) && is_array($result) && count($result) > 0 ) {
		
		//! Get 204/990 info from primary shipment
		if( isset($result[0]["EDI_204_PRIMARY"]) && $result[0]["EDI_204_PRIMARY"] > 0 ) {
			$result3 = $load_table->database->get_one_row(
				"SELECT EDI_204_ISA15_USAGE, EDI_204_GS04_OFFERED, EDI_204_G6202_EXPIRES,
				EDI_204_L301_WEIGHT, EDI_204_L303_RATE, EDI_204_L304_RQUAL,
				EDI_204_L305_CHARGE, EDI_204_NTE_MILES, EDI_204_L312_WQUAL,
				CONSOLIDATE_NUM, EDI_990_STATUS, EDI_990_SENT, EDI_204_ORIGIN, EDI_204_B204_SID,
				EDI_204_B206_PAYMENT, EDI_204_L1101_SERVICE
				FROM EXP_SHIPMENT 
				WHERE SHIPMENT_CODE = ".$result[0]["EDI_204_PRIMARY"] );
			if( is_array($result3))
				$result[0] = array_merge($result[0], $result3);
		}

		echo $load_form->render( $result[0] );
		ob_flush(); flush();
		
		//! New Repair class - check for issues
		$repair->check_issue_inline( $sts_issue_inline_load, $_GET['CODE'] );

		//! New mileage report
		//! SCR# 273 - include EXT_GROUP_BILLING
		if( in_array($load_table->state_behavior[$stat[0]['CURRENT_STATUS']],
				array('complete', 'opproved', 'approved', 'billed')) &&
				$my_session->in_group(EXT_GROUP_FINANCE, EXT_GROUP_BILLING) ) {
			require_once( "include/sts_mileage_class.php" );
			
			$mileage_table = sts_mileage::getInstance($exspeedite_db, $sts_debug);
			$mileage = $mileage_table->fetch_rows("LOAD_CODE = ".$_GET['CODE']);

			echo '<div class="well well-sm">
				<h3 style="margin-top: 5px;">Revenue/Expense/Margin For '.$_GET['CODE'].'</h3>
				'.$mileage_table->load_revenue($_GET['CODE']).'
				'.$mileage_table->load_expense($_GET['CODE']);
			
			if( is_array($mileage) && count($mileage) == 1 ) {
				if( $mileage[0]["SHIPMENTS"] > $mileage[0]["BILLED"] ) {
					$mileage[0]["BILLED"] = '<span class="label label-danger">'.
						$mileage[0]["BILLED"].'</span>';
				}

				if( $mileage[0]["REVENUE"] <= 0 ) {
					$mileage[0]["REVENUE"] = '<span class="label label-danger">'.
						$mileage[0]["REVENUE"].'</span>';
				}

				if( $mileage[0]["TOTAL_COST"] <= 0 ) {
					$mileage[0]["TOTAL_COST"] = '<span class="label label-warning">'.
						$mileage[0]["TOTAL_COST"].'</span>';
				}

				if( $mileage[0]["MARGIN"] <= 0 ) {
					$mileage[0]["MARGIN"] = '<span class="label label-danger">'.
						$mileage[0]["MARGIN"].'</span>';
				}
				
				if( $sts_multi_currency && isset($mileage[0]["HOME_CURRENCY"])) {
					$sts_form_viewmileage_form['layout'] = str_replace('###', ' ('.$mileage[0]["HOME_CURRENCY"].')',
						$sts_form_viewmileage_form['layout']);
				} else {
					$sts_form_viewmileage_form['layout']  = str_replace('###', '',
						$sts_form_viewmileage_form['layout'] );
				}

				$mileage_form = new sts_form( $sts_form_viewmileage_form,
					$sts_result_view_mileage_fields, $mileage_table, $sts_debug);
	
				echo $mileage_form->render( $mileage[0] ).'
				</div>';
			}
		}

		echo '</div>

<!-- Nav tabs -->
<ul class="nav nav-tabs">
	<li class="active"><a href="#stops" data-toggle="tab">Stops</a></li>'.
	($sts_attachments ? '<li><a href="#attach" data-toggle="tab">Attachments</a></li>
	' : '').'	
	<li><a href="#shipments" data-toggle="tab">Shipments</a></li>
	<li><a href="#history" data-toggle="tab">History</a></li>'.
( $sts_email_queueing && $my_session->in_group(EXT_GROUP_ADMIN) ? '
  <li><a href="#email" data-toggle="tab">Email</a></li>
': '').'
</ul>

<!-- Tab panes -->
<div class="tab-content">
  <div role="tabpanel" class="tab-pane active" id="stops" style="position: relative">
	  <div class="row well well-sm">
	';
		//! List Stops
		if( $my_session->in_group(EXT_GROUP_MANAGER) && //! SCR# 687 - Recalc distances
			is_array($stat) && count($stat) > 0 &&
			! in_array($load_table->state_behavior[$stat[0]['CURRENT_STATUS']],
				array('cancel', 'oapproved', 'approved', 'billed'))) {

			$sts_result_stops_edit['title'] .= ' <a class="btn btn-sm btn-danger"  href="exp_viewload.php?RECALC_MILES&CODE='.$_GET['CODE'].'"><span class="glyphicon glyphicon-road"></span> Recalc Miles</a>';
		}
		$stop_table_lj = new sts_stop_left_join($exspeedite_db, $sts_debug);
		$rslt = new sts_result( $stop_table_lj, "LOAD_CODE = ".$_GET['CODE'], $sts_debug, $sts_profiling );
		echo $rslt->render( $sts_result_stops_lj_layout, $sts_result_stops_edit );

	if( $sts_attachments ) {
		echo '</div>
	</div>
	<div role="tabpanel" class="tab-pane" id="attach">
	  <div class="row well well-sm">
	';
		ob_flush(); flush();

	//! List Attachments
	$attachment_table = sts_attachment::getInstance($exspeedite_db, $sts_debug);
	$rslt2 = new sts_result( $attachment_table, 
		"(SOURCE_CODE = ".$_GET['CODE']." AND SOURCE_TYPE = 'load')
		OR ATTACHMENT_CODE IN (SELECT A.ATTACHMENT_CODE
		FROM EXP_SHIPMENT S, EXP_ATTACHMENT A
		WHERE S.LOAD_CODE = ".$_GET['CODE']."
		AND A.SOURCE_CODE = S.SHIPMENT_CODE AND SOURCE_TYPE = 'shipment')
  
        OR ATTACHMENT_CODE IN (SELECT A.ATTACHMENT_CODE
        FROM EXP_SHIPMENT_LOAD SL, EXP_ATTACHMENT A
        WHERE SL.LOAD_CODE = ".$_GET['CODE']." AND A.SOURCE_CODE = SL.SHIPMENT_CODE
       AND A.SOURCE_CODE = SL.SHIPMENT_CODE AND SOURCE_TYPE = 'shipment')",
		 $sts_debug );
	echo $rslt2->render( $sts_result_attachment_layout, $sts_result_attachment_edit, '?SOURCE_TYPE=load&SOURCE_CODE='.$_GET['CODE'] );
	}

		echo '</div>
	</div>
	<div role="tabpanel" class="tab-pane" id="shipments">
	  <div class="row well well-sm">
	';
		ob_flush(); flush();
		
		//! List Shipments
		//! SCR# 555 - don't show shipments for cancelled loads
		$shipment_table = sts_shipment::getInstance($exspeedite_db, $sts_debug);
		$rslt2 = new sts_result( $shipment_table, "LOAD_CODE = ".$_GET['CODE'].
		($load_table->state_behavior[$stat[0]["CURRENT_STATUS"]] == 'cancel' ? '' : " OR
			SHIPMENT_CODE IN (SELECT SHIPMENT_CODE FROM EXP_SHIPMENT_LOAD
			WHERE EXP_SHIPMENT_LOAD.LOAD_CODE = ".$_GET['CODE'].")"),
			$sts_debug, $sts_profiling );

		if( ! $sts_intermodal_fields ) {
			unset($sts_result_shipments_layout['FS_NUMBER'],
				$sts_result_shipments_layout['SYNERGY_IMPORT']);
		}
		echo $rslt2->render( $sts_result_shipments_layout, $sts_result_shipments_view );
		echo '</div>
  </div>
  <div role="tabpanel" class="tab-pane" id="history">
  <div class="row well well-sm">
	';
		ob_flush(); flush();
		
		//! List Status History
		$status_table = sts_status::getInstance($exspeedite_db, $sts_debug);
		$rslt3 = new sts_result( $status_table, "ORDER_CODE = ".$_GET['CODE']." AND SOURCE_TYPE = 'load'", $sts_debug, $sts_profiling );
		echo $rslt3->render( $sts_result_status_layout, $sts_result_status_edit );
		echo '</div>
  </div>
  ';
 if( $sts_email_queueing && $my_session->in_group(EXT_GROUP_ADMIN) ) {
?>
	<div role="tabpanel" class="tab-pane" id="email">
	<div class="row well well-sm">
<?php
	require_once( "include/sts_email_class.php" );
	
	$queue = sts_email_queue::getInstance($exspeedite_db, $sts_debug);
	$rslt5 = new sts_result( $queue, "(SOURCE_TYPE='load' AND SOURCE_CODE=".$_GET['CODE'].")
		OR (EMAIL_TYPE='attachment' AND (SELECT A.SOURCE_CODE
		FROM EXP_ATTACHMENT A
		WHERE A.ATTACHMENT_CODE = EXP_EMAIL_QUEUE.SOURCE_CODE
		AND A.SOURCE_TYPE = 'load') = ".$_GET['CODE']." )", $sts_debug );
	echo $rslt5->render( $sts_result_queue_layout, $sts_result_queue_edit, '?SOURCE_TYPE=load&SOURCE_CODE='.$_GET['CODE'] );
	echo '</div>
	</div>
	';
}
  
  echo '</div>
	';
		
	} else { //! Not Found Message
		echo '<h2 class="text-danger"><span class="glyphicon glyphicon-warning-sign"></span> Load #'.$_GET['CODE'].' Not Found Or Access Not Allowed.</h2>
		<p>Perhaps the load has been removed. Please check with your administrator.</p>';
		require_once( "include/sts_email_class.php" );
		$email = sts_email::getInstance($exspeedite_db, $sts_debug);
		$email->send_alert('Load #'.$_GET['CODE'].' is missing', EXT_ERROR_WARNING);
	}
	}
} else {
	echo "<h3>Load # is missing or invalid</p>";
	require_once( "include/sts_email_class.php" );
	$email = sts_email::getInstance($exspeedite_db, $sts_debug);
	$email->send_alert('Load # is missing or invalid', EXT_ERROR_WARNING);
}

?>
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

<?php
//! SCR# 298 - Check Call functionality
require_once("exp_check_call.php");
?>

	<script language="JavaScript" type="text/javascript"><!--
		
		$(document).ready( function () {

			var load_code = <?php echo (isset($_GET['CODE']) ? $_GET['CODE'] : 0); ?>;

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
			
			$('ul.dropdown-menu li a#modal').on('click', function(event) {
				event.preventDefault();
				$('#listload_modal').modal({
					container: 'body',
					remote: $(this).attr("href")
				}).modal('show');
				return false;					
			});

			$('a[data-toggle="tab"]').on( 'shown.bs.tab', function (e) {
				$.fn.dataTable.tables( {visible: true, api: true} ).columns.adjust();
			} );
			
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
				"bScrollCollapse": true,
				"bSortClasses": false		
			});
			
			//if( $('#CARRIER_STATIC').text() != '' ) {
			//	$('.NOT_CARRIER').prop('hidden', 'hidden');
			//} else {
			//	$('.CARRIER_ONLY').prop('hidden', 'hidden');
			//}

			function IsNumeric(input)
			{
			    return (input - 0) == input && (''+input).replace(/^\s+|\s+$/g, "").length > 0;
			}

			$.ajax({
				url: 'exp_get_lumper_tax.php',
				data: {
					pw: 'Warranty',
					code: load_code
				},
				dataType: "json",
				success: function(data) {
					if( data.rates == false ) {
						$('.cdn-tax').prop('hidden', 'hidden');
					} else {
						$('#CDN_TAX').text(data.rates[0].province+' '+data.rates[0].tax+' ('+data.rates[0].rate+'%)');
					}

				}
			});
	
			$('a[data-toggle="tab"]').on( 'shown.bs.tab', function (e) {
				$.fn.dataTable.tables( {visible: true, api: true} ).columns.adjust();
			} );

			$('a[data-toggle="tab"]').on("shown.bs.tab", function (e) {
			    var id = $(e.target).attr("href");
			    localStorage.setItem('vlselectedTab', id)
			});
			
			var selectedTab = localStorage.getItem('vlselectedTab');
			if (selectedTab != null) {
			    $('a[data-toggle="tab"][href="' + selectedTab + '"]').tab('show');
			}


		});
		
	//--></script>

<?php

require_once( "include/footer_inc.php" );
?>
