<?php 
// Set flag that this is a parent file
define( '_STS_INCLUDE', 1 );

// Set flag that this is session readonly
define( '_STS_SESSION_READONLY', 1 );

// Set flag that this is an ajax call
define( '_STS_SESSION_AJAX', 1 );

// Setup Session
require_once( "include/sts_session_setup.php" );

require_once( "include/sts_config.php" );
if( ini_get('safe_mode') ){
   // safe mode is on
   ini_set('max_execution_time', 1200);		// Set timeout to 20 minutes
   ini_set('memory_limit', '1024M');
}else{
   // it's not
   set_time_limit(1200);
}

$sts_debug = isset($_GET['debug']) && in_group( EXT_GROUP_DEBUG );
require_once( "include/sts_session_class.php" );

$my_session = sts_session::getInstance( $exspeedite_db, $sts_debug );
$my_session->access_check( $sts_table_access[STOP_TABLE] );	// Make sure we should be here

require_once( "include/sts_stop_class.php" );
require_once( "include/sts_load_class.php" );
require_once( "include/sts_shipment_load_class.php" );
require_once( "include/sts_email_class.php" );
if( isset($_GET['SHOW']) ) {
require_once( "include/sts_timer_class.php" );
$timer = new sts_timer();
$timer->start();
}

function restore_trailer( $trailers, $shipment, $stop_type, $load_code, $sequence_number, $debug ) {
	global $stop_table;
	
	if( $debug ) echo "<p>restore_trailer( trailers, $shipment, $stop_type, $load_code, $sequence_number, debug )</p>";

	$trailer = $arrive = $depart = '';
	if( is_array($trailers) ) {
		foreach( $trailers as $row) {
			if( $row["SHIPMENT"] == $shipment && $row["STOP_TYPE"] == $stop_type ) {
				if( $row["TRAILER"] <> '')
					$trailer = $row["TRAILER"];
				if( $row["ACTUAL_ARRIVE"] <> '')
					$arrive = $row["ACTUAL_ARRIVE"];
				if( $row["ACTUAL_DEPART"] <> '')
					$depart = $row["ACTUAL_DEPART"];
			}
		}
	}
		
	// If not found, check previous stop for trailer.
	if( $trailer == '' && $sequence_number > 1 ) {
		if( $debug ) echo "<p>restore_trailer: not found, sequence_number = $sequence_number</p>";
		$prev = $stop_table->fetch_rows("LOAD_CODE = ".$load_code." AND SEQUENCE_NO = ".($sequence_number-1), "TRAILER");
		if( is_array($prev) && count($prev) > 0 && isset($prev[0]["TRAILER"]) && $prev[0]["TRAILER"] <> '')
			$trailer = $prev[0]["TRAILER"];
	}

	return array($trailer, $arrive, $depart);
}

$stop_table = sts_stop::getInstance($exspeedite_db, $sts_debug);
$load_table = sts_load::getInstance($exspeedite_db, $sts_debug);
$shipment_load_table = sts_shipment_load::getInstance($exspeedite_db, $sts_debug);

if( isset($_GET['CODE']) && isset($_GET['STOPS']) && isset($_GET['PW']) && $_GET['PW'] == 'Sound' ) {
	
	$dummy = $stop_table->add_load_status( $_GET['CODE'], 'savestops' );
	
	if( isset($_GET['SHOW']) ) {
		// Turn off output buffering
		ini_set('output_buffering', 'off');
		// Turn off PHP output compression
		ini_set('zlib.output_compression', false);
		// Implicitly flush the buffer(s)
		ini_set('implicit_flush', true);
		ob_implicit_flush(true);

		if (ob_get_level() == 0) ob_start();
		
		echo "<p>".number_format((float) $timer->split(),4)." Checking trailers assigned to stops</p>";
		ob_flush(); flush();
	}
	// Check for trailers assigned to stops
	$trailers = $stop_table->fetch_rows( "LOAD_CODE = ".$_GET['CODE'], "SEQUENCE_NO, STOP_TYPE, SHIPMENT, TRAILER, ACTUAL_ARRIVE, ACTUAL_DEPART", 
		"SEQUENCE_NO ASC" );
	
	$dropdock_stops = $stop_table->fetch_rows( "LOAD_CODE = ".$_GET['CODE'].
		" AND STOP_TYPE = 'dropdock'", "STOP_CODE, LOAD_CODE, STOP_TYPE, SHIPMENT, 
		CURRENT_STATUS, STOP_NAME, STOP_ADDR1, STOP_ADDR2, STOP_CITY, STOP_STATE, STOP_ZIP,
		STOP_CONTACT, STOP_PHONE, STOP_EXT, STOP_FAX, STOP_CELL, STOP_EMAIL, TRAILER", 
		"SHIPMENT ASC" );

	if( isset($_GET['SHOW']) ) {
		echo "<p>".number_format((float) $timer->split(),4)." Clear previous stops</p>";
		ob_flush(); flush();
	}
	// Clear previous stops
	$result = $stop_table->delete_row( "LOAD_CODE = ".$_GET['CODE'].
		" AND STOP_TYPE in ('pick','pickdock','drop') AND CURRENT_STATUS = ".$stop_table->behavior_state['entry'] );

	if( $sts_debug ) echo "<h3>stops list = ".$_GET['STOPS']."</h3>";
	$stops = explode(',', $_GET['STOPS']);
	$num_stops = count($stops);
	$sequence_number = 1;

	if( isset($_GET['SHOW']) ) {
		echo "<p>".number_format((float) $timer->split(),4)." Getting locked stops</p>";
		ob_flush(); flush();
	}
	//!WIP now what?
	// stops should contain all stops.
	// Locked ones should be any stop, and starting from the beginning.
	$locked_stops = $stop_table->fetch_rows( "LOAD_CODE = ".$_GET['CODE'], "STOP_TYPE, SHIPMENT,
		CURRENT_STATUS, STOP_NAME, STOP_ADDR1, STOP_ADDR2, STOP_CITY, STOP_CODE, STOP_STATE, STOP_ZIP, SEQUENCE_NO, TRAILER", 
		"SEQUENCE_NO ASC" );

	if( $sts_debug ) {
		echo "<p>locked_stops = </p>
		<pre>";
		var_dump($locked_stops);
		echo "</pre>";
	}
	
	if( is_array($locked_stops) && count($locked_stops) > 0 ) {
		foreach( $locked_stops as $locked ) {
			// Stop #1 of type stop. Remove from list.
			if( $locked['STOP_TYPE'] == 'stop' && $locked['SEQUENCE_NO'] == 1 &&  count($stops) > 0 &&
				$stops[0] == 'stop-'.$locked['STOP_CODE'] ) {
				array_shift($stops);
				$sequence_number++;
			} else
			// pick, locked. Remove from list.
			if( $locked['STOP_TYPE'] == 'pick' &&
				$stops[0] == 'pick-'.$locked['SHIPMENT'] ) {
				array_shift($stops);
				$sequence_number++;
			} else
			// pickdock, locked. Remove from list.
			if( $locked['STOP_TYPE'] == 'pickdock' &&
				$stops[0] == 'pickdock-'.$locked['SHIPMENT'] ) {
				array_shift($stops);
				$sequence_number++;
			} else
			// drop, locked. Remove from list.
			if( $locked['STOP_TYPE'] == 'drop' &&
				$stops[0] == 'drop-'.$locked['SHIPMENT'] ) {
				array_shift($stops);
				$sequence_number++;
			} else
			// Stop at the end. Move the number way out to allow for new stops before it.
			if( $locked['STOP_TYPE'] == 'stop' && count($stops) > 0 &&
				$stops[count($stops)-1] == 'stop-'.$locked['STOP_CODE'] ) {
				//$dummy = $stop_table->update( $locked['STOP_CODE'],
				//		array("SEQUENCE_NO" => 999 ) );
				array_pop($stops);
			} else
			// Keep dropdock
			if( $locked['STOP_TYPE'] == 'dropdock' ) {
				
			} else { // Unknown issue
				echo "<p>savestops: Existing stop, type=".$locked['STOP_TYPE']." seq=".$locked['SEQUENCE_NO'].
					" code=".$locked['STOP_CODE']."</p>";
				$email = sts_email::getInstance($exspeedite_db, $sts_debug);
				$email->send_alert("<p>savestops: Existing stop, type=".$locked['STOP_TYPE']." seq=".$locked['SEQUENCE_NO'].
					" code=".$locked['STOP_CODE']."<br>".
					"load = ".$_GET['CODE']."<br>".
					"stops = ".$_GET['STOPS'].
					'<br>'.$email->load_stops($_GET['CODE']).
					'<br>'.$email->load_history($_GET['CODE']).
					'<br>'.$email->shipment_histories( $_GET['CODE'] )."</p>", EXT_ERROR_ERROR);
				die;
			}
			
		}
	}
	
	if( $sts_debug ) {
		echo "<h3>stops = </h3>
		<pre>";
		var_dump($stops);
		echo "</pre>";
	}
	
	if( isset($_GET['SHOW']) ) {
		echo "<p>".number_format((float) $timer->split(),4)." Updating stops</p>";
		ob_flush(); flush();
	}
	foreach( $stops as $stop ) {
		list($stop_type, $shipment) = explode('-', $stop);
		if( $stop_type == 'pickdock' ) { //! add pickdock
			$ll = $shipment_load_table->last_load( $shipment );
			$stop_details = $stop_table->fetch_rows( "STOP_CODE = ".$ll[0]["DOCKED_AT"],
				"STOP_NAME, STOP_ADDR1, STOP_ADDR2, STOP_CITY, STOP_STATE, STOP_ZIP,
				STOP_CONTACT, STOP_PHONE, STOP_EXT, STOP_FAX, STOP_CELL,STOP_EMAIL" );
			$new_stop = $stop_details[0];
			$new_stop["LOAD_CODE"] = $_GET['CODE'];
			$new_stop["SEQUENCE_NO"] = $sequence_number++;
			$new_stop["STOP_TYPE"] = $stop_type;
			$new_stop["SHIPMENT"] = $shipment;
			$new_stop["CURRENT_STATUS"] = $stop_table->behavior_state['entry'];
			list($trailer, $arrive, $depart) = restore_trailer( $trailers, $shipment, $stop_type, $_GET['CODE'], $new_stop["SEQUENCE_NO"], $sts_debug );
			if( $sts_debug ) {
				echo "<pre>";
				var_dump($trailer, $arrive, $depart);
				echo "</pre>";
			}
			if( $trailer <> '' )
				$new_stop["TRAILER"] = $trailer;
			if( $arrive <> '' )
				$new_stop["ACTUAL_ARRIVE"] = $arrive;
			if( $depart <> '' )
				$new_stop["ACTUAL_DEPART"] = $depart;
			
			if( $sts_debug ) {
				echo "<p>new_stop = </p>
				<pre>";
				var_dump($new_stop);
				echo "</pre>";
			}
	
			$result = $stop_table->add( $new_stop );
		} else if( $stop_type == 'dropdock' ) { //! Re-add dropdock
			$found_dd = false;
			foreach( $dropdock_stops as $dropdock ) {
				if( $dropdock["SHIPMENT"] == $shipment ) {
					$result = $stop_table->update( $dropdock["STOP_CODE"], array( "SEQUENCE_NO" => $sequence_number++ ) );
					$found_dd = true;
				}
			}
			if( ! $found_dd ) {
				echo "<p>savestops: missing dropdock for shipment # ".$shipment."</p>";
				die;
			}
		} else		
		if( $stop_type <> 'stop' ) {
			$new_stop = array( "LOAD_CODE" => $_GET['CODE'], 
				"SEQUENCE_NO" => $sequence_number++,
				"STOP_TYPE" => $stop_type, 
				"SHIPMENT" => $shipment, 
				"CURRENT_STATUS" => $stop_table->behavior_state['entry'] );
			list($trailer, $arrive, $depart) = restore_trailer( $trailers, $shipment, $stop_type, $_GET['CODE'], $new_stop["SEQUENCE_NO"], $sts_debug );
			if( $trailer <> '' )
				$new_stop["TRAILER"] = $trailer;
			if( $arrive <> '' )
				$new_stop["ACTUAL_ARRIVE"] = $arrive;
			if( $depart <> '' )
				$new_stop["ACTUAL_DEPART"] = $depart;

			$result = $stop_table->add( $new_stop );
		}
	}
	
	//$stop_table->renumber( $_GET['CODE'] );
	/*
	if( $_GET['RETURN'] == "exp_listload.php" ) {
		if( isset($_GET['SHOW']) ) {
			echo "<p>".number_format((float) $timer->split(),4)." Updating distances for $num_stops stops: ";
			ob_flush(); flush();
		}
		?>
		
	<script language="JavaScript" type="text/javascript"><!--
		$(document).ready( function () {

			$.ajax({
				url: 'exp_update_distances.php',
				data: {
					CODE: encodeURIComponent(<?php echo $_GET['CODE']; ?>),
					PW: "Buntzen",
				},
				dataType: "json"
			});					
		
		});
	//--></script>
		
		<?php
	}
	*/
	
	if( isset($_GET['SHOW']) ) {
		echo "</p><p>".number_format((float) $timer->split(),4)." Validate statuses</p>";
		ob_flush(); flush();
	}
	// This is from exp_repair_db, it fixes the status if by switching around the stops has
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
		WHERE LOAD_CODE = ".$_GET['CODE']."
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

	if( $sts_debug ) echo "<p>result = ".($result ? 'true' : 'false '.$load_table->error())."</p>";
	
	if( isset($_GET['DIST']) && $_GET['DIST'] <> '' ) {
		$load_table = sts_load::getInstance($exspeedite_db, $sts_debug);
		$result = $load_table->update( $_GET['CODE'], 
					array( 'TOTAL_DISTANCE' => $_GET['DIST'] ) );
	}
	$dummy = $stop_table->add_load_status( $_GET['CODE'], 'savestops: '.$stop_table->list_stops( $_GET['CODE'] ) );

	if( isset($_GET['SHOW']) ) {
		echo "<p>".number_format((float) $timer->split(),4)." Finished.</p>";
		ob_flush(); flush(); usleep(500000);
	}

}

if( ! $sts_debug && isset($_GET['RETURN']) ) {
	if( $_GET['RETURN'] == "exp_dispatch3.php" )
		reload_page ( $_GET['RETURN']."?CODE=". $_GET['CODE']);
	else if( $_GET['RETURN'] == "exp_listload.php" )
		reload_page ( $_GET['RETURN'] );
	else
		reload_page ( $load_table->decryptData($_GET['RETURN']) );
}