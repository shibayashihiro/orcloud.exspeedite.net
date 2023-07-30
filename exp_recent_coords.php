<?php

// $Id: exp_recent_coords.php 4350 2021-03-02 19:14:52Z duncan $
//! SCR# 298 - For Check call, find the most recent coordinates for a load
// Use this for a starting point.

// Set flag that this is a parent file
define( '_STS_INCLUDE', 1 );

// Set flag that this is session readonly
define( '_STS_SESSION_READONLY', 1 );

// Set flag that this is an ajax call
define( '_STS_SESSION_AJAX', 1 );

// Setup Session
require_once( "include/sts_session_setup.php" );

require_once( "include/sts_config.php" );
$sts_debug = isset($_GET['debug']);
require_once( "include/sts_db_class.php" );

if( ! empty($_GET['CODE']) && isset($_GET['PW']) && $_GET['PW'] == 'Soya42') {

	$result = false;
	//! Attempt 1 - search the load history for latest coordinates
	$check1 = $exspeedite_db->get_one_row("
		SELECT CREATED_DATE, LAT, LON
		FROM EXP_STATUS 
		WHERE ORDER_CODE = ".$_GET['CODE']."
		AND SOURCE_TYPE = 'LOAD'
        AND LAT IS NOT NULL AND LON IS NOT NULL
		ORDER BY CREATED_DATE DESC
        LIMIT 1");
    
    if( is_array($check1) && ! empty($check1["LAT"]) && ! empty($check1["LON"]) ) {
    	$result = $check1;
    } else {
	    //! Attempt 2 - find the coordinates of the first stop
	    $check2 = $exspeedite_db->get_one_row("
	    	SELECT (CASE S.STOP_TYPE
					WHEN 'pick' THEN SH.SHIPPER_LAT
					WHEN 'drop' THEN SH.CONS_LAT
					ELSE S.STOP_LAT END
				) AS LAT, 
				(CASE S.STOP_TYPE
					WHEN 'pick' THEN SH.SHIPPER_LON
					WHEN 'drop' THEN SH.CONS_LON
					ELSE S.STOP_LON END
				) AS LON
			FROM EXP_STOP S, EXP_LOAD L, EXP_SHIPMENT SH
			WHERE L.LOAD_CODE = ".$_GET['CODE']."
			AND S.LOAD_CODE = L.LOAD_CODE
			AND SH.LOAD_CODE = L.LOAD_CODE
			AND S.SEQUENCE_NO = L.CURRENT_STOP
			AND S.SHIPMENT = SH.SHIPMENT_CODE
			LIMIT 1");
		
	    if( is_array($check2) && ! empty($check2["LAT"]) && ! empty($check2["LON"]) ) {
	    	$result = $check2;
	    } else {
		    //! Fallback - Coordinates of Isanti, MN
		    $result = array("LAT" => 45.4894008, "LON" => -93.2476091);
		}
    }

	if( $sts_debug ) {
		echo "<p>result = </p>
		<pre>";
		var_dump($result);
		echo "</pre>";
	} else {
		echo json_encode( $result );
	}
}


?>

