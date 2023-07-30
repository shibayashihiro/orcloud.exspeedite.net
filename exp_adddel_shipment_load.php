<?php 
//! Part of the select shipments for a load page
// Called via ajax to add or remove a shipment from a load

// Set flag that this is a parent file
define( '_STS_INCLUDE', 1 );

// Setup Session
require_once( "include/sts_session_setup.php" );

require_once( "include/sts_config.php" );
$sts_debug = isset($_GET['debug']) && in_group( EXT_GROUP_DEBUG );
require_once( "include/sts_session_class.php" );

$my_session = sts_session::getInstance( $exspeedite_db, $sts_debug );
$my_session->access_check( $sts_table_access[SHIPMENT_TABLE] );	// Make sure we should be here

$response = false;

if( isset($_GET['pw']) && $_GET['pw'] == 'EggsToast' &&			// Password
	isset($_GET['load']) &&
	(intval($_GET['load']) > 0 || isset($_GET['empty'])) ) {		// load code
	$load = $_GET['load'];

	require_once( "include/sts_load_class.php" );
	require_once( "include/sts_shipment_class.php" );
	require_once( "include/sts_stop_class.php" );
	require_once( "include/sts_shipment_load_class.php" );
	
	$load_table = sts_load::getInstance($exspeedite_db, $sts_debug);
	$shipment_table = sts_shipment::getInstance($exspeedite_db, $sts_debug);
	$shipment_load_table = sts_shipment_load::getInstance($exspeedite_db, $sts_debug);
	$stop_table = sts_stop::getInstance($exspeedite_db, $sts_debug);

	//! SCR# 852 - Containers Feature
	require_once( "include/sts_client_class.php" );
	require_once( "include/sts_setting_class.php" );
	$setting_table = sts_setting::getInstance($exspeedite_db, $sts_debug);
	$sts_containers = $setting_table->get( 'option', 'CONTAINERS' ) == 'true';
	$sts_restrict_salesperson = $setting_table->get( 'option', 'EDIT_SALESPERSON_RESTRICTED' ) == 'true';
	
	// Get the current status of the load. It could be already dispatched.
	// This also confirms the load exists
	$result = $load_table->fetch_rows("LOAD_CODE = ".$load, "CURRENT_STATUS,
		(SELECT COUNT(*) AS NUM_STOPS
			FROM EXP_STOP
			WHERE EXP_LOAD.LOAD_CODE = EXP_STOP.LOAD_CODE) AS NUM_STOPS" );
	if( is_array($result) && count($result) == 1 || isset($_GET['empty']) ) {
		
		if( is_array($result) && count($result) > 0 ) {
			$load_status = $result[0]['CURRENT_STATUS'];
			$num_stops = $result[0]['NUM_STOPS'];
			if( $sts_debug ) echo "<p>Load status ".$load_table->state_name[$load_status]."</p>";
		}
	
		if( isset($_GET['add']) && intval($_GET['add']) > 0 ) {
			//! ------------------------ add shipment code
			$shipment = $_GET['add'];
			
			//! Check shipment exists, not on a load, and not cancelled
			$result = $shipment_table->fetch_rows($shipment_table->primary_key.' = '.$shipment, "LOAD_CODE, CURRENT_STATUS, IM_EMPTY_DROP, ST_NUMBER" );
			if( is_array($result) && count($result) == 1 &&
				$result[0]["LOAD_CODE"] == 0 &&
				$result[0]["CURRENT_STATUS"] <> $shipment_table->behavior_state['cancel']) {

				//! SCR# 607 - There should be no stops at this point!
				$check = $stop_table->fetch_rows( "SHIPMENT = ".$shipment );
				if( is_array($check) && count($check) > 0 ) {
					$shipment_table->add_shipment_status( $shipment, "WARNING Add shipment# $shipment to load# $load, found ".count($check)." stops for load# ".$check[0]["LOAD_CODE"] );					
				}
				
				//! Update shipment
				//! SCR# 344 - Make sure if the load is already dispatched
				$changes = array( "LOAD_CODE" => $load );
				if( in_array($load_table->state_behavior[$load_status],
					array('dispatch', 'depart stop', 'depshdock', 'depart shipper',
					'deprecdock', 'depart cons', 'arrive stop', 'arrshdock',
					'arrive shipper', 'arrrecdock', 'arrive cons')))
					$changes["CURRENT_STATUS"] = $shipment_table->behavior_state['dispatch'];
				$shipment_result = $shipment_table->update( $shipment, $changes, false );
				
				//! SCR# 344 - Make sure the stops are not already there
				$stop_result = $stop_table->delete_row( "LOAD_CODE = ".$load.
					" AND SHIPMENT = ".$shipment );
				
				//! Add stops at the end of the list of stops
				$new_stop = array( "LOAD_CODE" => $load, 
					"SEQUENCE_NO" => ++$num_stops,
					"STOP_TYPE" => 'pick', 
					"SHIPMENT" => $shipment, 
					"CURRENT_STATUS" => $stop_table->behavior_state['entry'] );
	
				$check_docked = $shipment_load_table->last_load( $shipment );
				if( isset($check_docked) && count($check_docked) > 0 &&
					isset($check_docked[0]["DOCKED_AT"]) &&
					$check_docked[0]["DOCKED_AT"] > 0 ) {	// Docked
					$new_stop["STOP_TYPE"] = 'pickdock';
					$dd = $stop_table->fetch_rows("STOP_CODE = ".$check_docked[0]["DOCKED_AT"]);
					$new_stop['STOP_NAME']	= $dd[0]["STOP_NAME"];
					$new_stop['STOP_ADDR1']	= $dd[0]["STOP_ADDR1"];
					$new_stop['STOP_ADDR2']	= $dd[0]["STOP_ADDR2"];
					$new_stop['STOP_CITY']	= $dd[0]["STOP_CITY"];
					$new_stop['STOP_STATE']	= $dd[0]["STOP_STATE"];
					$new_stop['STOP_ZIP']	= $dd[0]["STOP_ZIP"];
				}
				
				$stop_result1 = $stop_table->add( $new_stop );	// Pick

				$new_stop = array( "LOAD_CODE" => $load, 
					"SEQUENCE_NO" => ++$num_stops,
					"STOP_TYPE" => 'drop', 
					"SHIPMENT" => $shipment, 
					"CURRENT_STATUS" => $stop_table->behavior_state['entry'] );
				$stop_result2 = $stop_table->add( $new_stop );	// Drop
				
				//! SCR# 852 - Containers Feature
				if( $sts_containers && is_array($result) && count($result) == 1 &&
					isset($result[0]["IM_EMPTY_DROP"]) && $result[0]["IM_EMPTY_DROP"] > 0 ) {

					$empty_stop = array( "LOAD_CODE" => $load, 
						"SEQUENCE_NO" => ++$num_stops,
						"STOP_TYPE" => 'stop', 
						"SHIPMENT" => $shipment, 
						"CURRENT_STATUS" => $stop_table->behavior_state['entry'],
						"IM_STOP_TYPE" => 'dropdepot' );
						
					if( ! empty($result[0]["ST_NUMBER"]) )
						$empty_stop['STOP_COMMENT'] = 'Drop Container# '.$result[0]["ST_NUMBER"].' at Intermodal depot.';
						
					$client_table = sts_client::getInstance($exspeedite_db, $sts_debug);
					$lookup = $client_table->im_dock( $result[0]["IM_EMPTY_DROP"] );
					
					if( is_array($lookup) ) {
						$empty_stop['STOP_NAME'] = $lookup['CLIENT_NAME'];
						$empty_stop['STOP_ADDR1'] = $lookup['ADDRESS'];
						$empty_stop['STOP_ADDR2'] = $lookup['ADDRESS2'];
						$empty_stop['STOP_CITY'] = $lookup['CITY'];
						$empty_stop['STOP_STATE'] = $lookup['STATE'];
						$empty_stop['STOP_ZIP'] = $lookup['ZIP_CODE'];
						$empty_stop['STOP_COUNTRY'] = $lookup['COUNTRY'];
						$empty_stop['STOP_PHONE'] = $lookup['PHONE_OFFICE'];
						$empty_stop['STOP_EXT'] = $lookup['PHONE_EXT'];
						$empty_stop['STOP_FAX'] = $lookup['PHONE_FAX'];
						$empty_stop['STOP_CELL'] = $lookup['PHONE_CELL'];
						$empty_stop['STOP_EMAIL'] = $lookup['EMAIL'];
						$empty_stop['STOP_CONTACT'] = $lookup['CONTACT_NAME'];
						$empty_stop['STOP_LAT'] = isset($lookup['LAT']) ? floatval($lookup['LAT']) : 0;
						$empty_stop['STOP_LON'] = isset($lookup['LON']) ? floatval($lookup['LON']) : 0;
					}
					
					$stop_result2 = $stop_table->add( $empty_stop );	// Stop
					
				}

				sleep(1);
				$stop_table->renumber( $load );
				
				// Fix the SALES_PERSON
				if( $sts_restrict_salesperson ) {
					$shipment_table->update( $shipment.' AND SALES_PERSON IS NULL', [
						'-SALES_PERSON' => '(SELECT SALES_PERSON FROM EXP_CLIENT WHERE CLIENT_CODE = BILLTO_CLIENT_CODE)' ], false );
				}
				
				$response = $shipment_result && $stop_result1 && $stop_result2;
				$load_table->add_load_status( $load, "Add shipment# $shipment to load# $load ".($response ? "OK" : "FAIL") );
				$shipment_table->add_shipment_status( $shipment, "Add shipment# $shipment to load# $load ".($response ? "OK" : "FAIL"), false, false, $load );
			} else {
				//! SCR# 607 - log diagnostic if unable to add to a load
				$load_table->add_load_status( $load, "FAILED to Add shipment# $shipment to load# $load " );
				$shipment_table->add_shipment_status( $shipment, "FAILED to Add shipment# $shipment to load# $load " );
				
			}
			
		}
		else if( isset($_GET['del']) && intval($_GET['del']) > 0 ) {
			//! ------------------------ del shipment code
			$shipment = $_GET['del'];

			$check_docked = $shipment_load_table->last_load( $shipment );
			if( isset($check_docked) && count($check_docked) > 0 &&
				isset($check_docked[0]["DOCKED_AT"]) && $check_docked[0]["DOCKED_AT"] > 0 ) {	// Docked
				if( $sts_debug ) echo "<p>re-dock shipment ".$shipment.
					" DOCKED_AT = ".$check_docked[0]["DOCKED_AT"].
					" CURRENT_STATUS = ".$shipment_table->behavior_state['docked']."</p>";

				$shipment_result = $shipment_table->update( $shipment,
					array( "LOAD_CODE" => 0,
						"DOCKED_AT" => $check_docked[0]["DOCKED_AT"],
						"CURRENT_STATUS" => $shipment_table->behavior_state['docked']
					 ), false );
				
			} else {	// Ready Dispatch
				if( $sts_debug ) echo "<p>Ready Dispatch shipment ".$shipment.
					" CURRENT_STATUS = ".$shipment_table->behavior_state['assign']."</p>";

				$shipment_result = $shipment_table->update( $shipment,
					array(
						"LOAD_CODE" => 0,
						"CURRENT_STATUS" => $shipment_table->behavior_state['assign']
					 ), false );
			}

			if( $shipment_result ) {
				$stop_result = $stop_table->delete_row( "LOAD_CODE = ".$load.
					" AND SHIPMENT = ".$shipment );
				
				// Remove entry from the shipment_load_table
				$sl_result = $shipment_load_table->delete_row( "LOAD_CODE = ".$load.
					" AND SHIPMENT_CODE = ".$shipment );
					
				sleep(1);
				$stop_table->renumber( $load ); 
	
				// This is from exp_repair_db, and was in exp_savestops
				// it fixes the status if by switching around the stops has
				// caused it to incorrect. Limit it to just this load.
				$load_stop_state = $load_table->database->get_multiple_rows(
					"SELECT LOAD_CODE, CURRENT_STOP, LOAD_STATUS, STOP_TYPE
					FROM (SELECT LOAD_CODE, CURRENT_STOP,
					(SELECT BEHAVIOR FROM EXP_STATUS_CODES
					WHERE CURRENT_STATUS = STATUS_CODES_CODE) AS LOAD_STATUS,
					(SELECT STOP_TYPE FROM EXP_STOP
					WHERE EXP_STOP.LOAD_CODE = EXP_LOAD.LOAD_CODE
					AND SEQUENCE_NO = CURRENT_STOP) AS STOP_TYPE
					FROM EXP_LOAD
					WHERE LOAD_CODE = ".$load."
					AND CURRENT_STATUS IN (".$load_table->behavior_state['arrive cons'].", ".
						$load_table->behavior_state['arrive shipper'].", ".
						$load_table->behavior_state['arrshdock'].", ".
						$load_table->behavior_state['arrrecdock'].", ".
						$load_table->behavior_state['arrive stop'].") ) X
					WHERE (LOAD_STATUS = 'arrive cons' AND STOP_TYPE <> 'drop')
					OR (LOAD_STATUS = 'arrive shipper' AND STOP_TYPE <> 'pick')
					OR (LOAD_STATUS = 'arrshdock' AND STOP_TYPE <> 'pickdock')
					OR (LOAD_STATUS = 'arrrecdock' AND STOP_TYPE <> 'dropdock')
					OR (LOAD_STATUS = 'arrive stop' AND STOP_TYPE <> 'stop')" );
			
				if( is_array($load_stop_state) && count($load_stop_state) > 0 ) {
					foreach( $load_stop_state as $row ) {
						if( $row['STOP_TYPE'] == 'pick' ) 
							$new_status = $load_table->behavior_state['arrive shipper'];
						else if( $row['STOP_TYPE'] == 'drop' ) 
							$new_status = $load_table->behavior_state['arrive cons'];
						else if( $row['STOP_TYPE'] == 'pickdock' ) 
							$new_status = $load_table->behavior_state['arrshdock'];
						else if( $row['STOP_TYPE'] == 'dropdock' ) 
							$new_status = $load_table->behavior_state['arrrecdock'];
						else if( $row['STOP_TYPE'] == 'stop' ) 
							$new_status = $load_table->behavior_state['arrive stop'];
						
						if( isset($new_status) && $new_status > 0 )
							$result = $load_table->update_row("LOAD_CODE = ".$row['LOAD_CODE'],
								array( array("field" => "CURRENT_STATUS", "value" => $new_status) ) );
					}
				}

				$response = $shipment_result && $stop_result;
			}
			$load_table->add_load_status( $load, "Delete shipment# $shipment from load# $load ".($response ? "OK" : "FAIL") );
			$shipment_table->add_shipment_status( $shipment, "Delete# shipment $shipment from load# $load ".($response ? "OK" : "FAIL"), false, false, 0 );
		}
		
		else if( isset($_GET['empty']) && intval($_GET['empty']) > 0 ) {
			if( $sts_debug ) echo "<p>empty: shipment = ".$_GET['empty'].
				" load = ".$load." drop = ".$_GET['drop']."</p>";

			//! ------------------------ empty shipment code
			$shipment = $_GET['empty'];
			
			// Have to update the EXP_SHIPMENT.IM_EMPTY_DROP field.
			if( isset($_GET['drop']) ) {
				$shipment_table->update($shipment, ['IM_EMPTY_DROP' => $_GET['drop']]);
			}
			
			// First remove previous stop if any.
			$stop_result = $stop_table->delete_row( "LOAD_CODE = ".$load.
				" AND SHIPMENT = ".$shipment.
				" AND IM_STOP_TYPE = 'dropdepot'".
				" AND CURRENT_STATUS = ".$stop_table->behavior_state['entry'] );

			$result = $shipment_table->fetch_rows($shipment_table->primary_key.' = '.$shipment, "LOAD_CODE, CURRENT_STATUS, IM_EMPTY_DROP, ST_NUMBER" );
			
			if( isset($_GET['drop']) && $_GET['drop'] > 0 &&
				is_array($result) && count($result) == 1 &&
				$result[0]["LOAD_CODE"] != 0 &&
				$result[0]["CURRENT_STATUS"] <> $shipment_table->behavior_state['cancel']) {

				if( $sts_containers && is_array($result) && count($result) == 1 &&
					isset($result[0]["IM_EMPTY_DROP"]) && $result[0]["IM_EMPTY_DROP"] > 0 ) {

					$empty_stop = array( "LOAD_CODE" => $load, 
						"SEQUENCE_NO" => ++$num_stops,
						"STOP_TYPE" => 'stop', 
						"SHIPMENT" => $shipment, 
						"CURRENT_STATUS" => $stop_table->behavior_state['entry'],
						"IM_STOP_TYPE" => 'dropdepot' );
						
					if( ! empty($result[0]["ST_NUMBER"]) )
						$empty_stop['STOP_COMMENT'] = 'Drop Container# '.$result[0]["ST_NUMBER"].' at Intermodal depot.';
						
					$client_table = sts_client::getInstance($exspeedite_db, $sts_debug);
					$lookup = $client_table->im_dock( $result[0]["IM_EMPTY_DROP"] );
					
					if( is_array($lookup) ) {
						$empty_stop['STOP_NAME'] = $lookup['CLIENT_NAME'];
						$empty_stop['STOP_ADDR1'] = $lookup['ADDRESS'];
						$empty_stop['STOP_ADDR2'] = $lookup['ADDRESS2'];
						$empty_stop['STOP_CITY'] = $lookup['CITY'];
						$empty_stop['STOP_STATE'] = $lookup['STATE'];
						$empty_stop['STOP_ZIP'] = $lookup['ZIP_CODE'];
						$empty_stop['STOP_COUNTRY'] = $lookup['COUNTRY'];
						$empty_stop['STOP_PHONE'] = $lookup['PHONE_OFFICE'];
						$empty_stop['STOP_EXT'] = $lookup['PHONE_EXT'];
						$empty_stop['STOP_FAX'] = $lookup['PHONE_FAX'];
						$empty_stop['STOP_CELL'] = $lookup['PHONE_CELL'];
						$empty_stop['STOP_EMAIL'] = $lookup['EMAIL'];
						$empty_stop['STOP_CONTACT'] = $lookup['CONTACT_NAME'];
						$empty_stop['STOP_LAT'] = isset($lookup['LAT']) ? floatval($lookup['LAT']) : 0;
						$empty_stop['STOP_LON'] = isset($lookup['LON']) ? floatval($lookup['LON']) : 0;
					}
					
					$stop_result2 = $stop_table->add( $empty_stop );	// Stop
				}
				sleep(1);
				$stop_table->renumber( $load );
			}
			
			$result = $shipment_table->fetch_rows( "SHIPMENT_CODE = $shipment", "CHANGED_DATE" );

			$response = $result[0]["CHANGED_DATE"];
		}
	}
	
}

//! Send back a response - true/false
if( $sts_debug ) {
		echo "<p>response = </p><pre>";
		var_dump($response);
		echo "</pre>";
} else {
	echo json_encode( $response );
}

?>
