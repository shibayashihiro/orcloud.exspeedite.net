<?php 
//! Part of the Picks, Drops & Stops page
// Called via ajax to update stops, revising their SEQUENCE_NO column

// Set flag that this is a parent file
define( '_STS_INCLUDE', 1 );

// Set flag that this is session readonly
define( '_STS_SESSION_READONLY', 1 );

// Set flag that this is an ajax call
define( '_STS_SESSION_AJAX', 1 );

// Setup Session
require_once( "include/sts_session_setup.php" );

require_once( "include/sts_config.php" );
$sts_debug = isset($_GET['debug']) && in_group( EXT_GROUP_DEBUG );
require_once( "include/sts_session_class.php" );

$my_session = sts_session::getInstance( $exspeedite_db, $sts_debug );
$my_session->access_check( $sts_table_access[STOP_TABLE] );	// Make sure we should be here

$response = false;

if( isset($_GET['pw']) && $_GET['pw'] == 'Benedict' &&	// Password
	isset($_GET['load']) && $_GET['load'] > 0 &&		// load
	isset($_GET['renumber']) ) {						// changes

	require_once( "include/sts_stop_class.php" );
	require_once( "include/sts_load_class.php" );
	$stop_table = sts_stop::getInstance($exspeedite_db, $sts_debug);
	$load_table = sts_load::getInstance($exspeedite_db, $sts_debug);

	$changes = explode(',', $_GET['renumber']);
	if( $sts_debug ) {
		echo "<pre>renumber-changes\n";
		var_dump($_GET['renumber']);
		var_dump($changes);
		echo "</pre>";		
	}
	
	$stop_result = true;
	foreach( $changes as $change ) {
		$stop_renum = explode('-', $change);
		if( $stop_result ) {
			if( $sts_debug ) echo "<p><strong>exp_update_stops: move ".$stop_renum[0]." to ".$stop_renum[1]."</strong></p>";
			$stop_result = $stop_table->update($stop_renum[0],
				array("SEQUENCE_NO" => $stop_renum[1]));
		}
	}
		
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
		WHERE LOAD_CODE = ".$_GET['load']."
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
	
	$response = $stop_result;

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
