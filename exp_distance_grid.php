<?php 

// $Id: exp_distance_grid.php 4350 2021-03-02 19:14:52Z duncan $
// Update distance for a shipment

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

require_once( "include/sts_zip_class.php" );
require_once( "include/sts_manual_miles_class.php" );
require_once( "include/sts_setting_class.php" );
require_once( "include/sts_shipment_class.php" );

// close the session here to avoid blocking
session_write_close();

if( isset($_GET['zip1']) && isset($_GET['zip2']) && isset($_GET['code']) && $_GET['code'] == 'Recycle') {

	$zip_table = new sts_zip($exspeedite_db, $sts_debug);
	$manual_miles_table = new sts_manual_miles($exspeedite_db, $sts_debug);

	$setting_table = sts_setting::getInstance($exspeedite_db, $sts_debug);
	$mph = intval( $setting_table->get('main', 'Miles per hour' ) );
	$load_time = intval( $setting_table->get('main', 'Load Time' ) );
	
	$distance = -1;
	
	$got_mm = false;
	//! Use manual miles table
	if( isset($_GET['bt']) && $_GET['bt'] <> '' ) {
		$distance = intval($manual_miles_table->get_distance( urldecode($_GET['bt']), $_GET['zip1'], $_GET['zip2'] ) );
		if( $distance >= 0 ) {
			$got_mm = true;
			$last_at = date("Y-m-d H:i:s");
		}
	}
		
	if( $distance == -1 ) {
		$address1 = array();
		$address2 = array();
		
		if( isset($_GET['addr11']) && $_GET['addr11'] <> '' )
			$address1['ADDRESS'] = urldecode($_GET['addr11']);
		if( isset($_GET['addr21']) && $_GET['addr21'] <> '' )
			$address1['ADDRESS2'] = urldecode($_GET['addr21']);
		if( isset($_GET['city1']) && $_GET['city1'] <> '' )
			$address1['CITY'] = urldecode($_GET['city1']);
		if( isset($_GET['state1']) && $_GET['state1'] <> '' )
			$address1['STATE'] = urldecode($_GET['state1']);
		if( isset($_GET['zip1']) && $_GET['zip1'] <> '' )
			$address1['ZIP_CODE'] = urldecode($_GET['zip1']);
		if( isset($_GET['country1']) && $_GET['country1'] <> '' )
			$address1['COUNTRY'] = urldecode($_GET['country1']);
	
		if( isset($_GET['addr12']) && $_GET['addr12'] <> '' )
			$address2['ADDRESS'] = urldecode($_GET['addr12']);
		if( isset($_GET['addr22']) && $_GET['addr22'] <> '' )
			$address2['ADDRESS2'] = urldecode($_GET['addr22']);
		if( isset($_GET['city2']) && $_GET['city2'] <> '' )
			$address2['CITY'] = urldecode($_GET['city2']);
		if( isset($_GET['state2']) && $_GET['state2'] <> '' )
			$address2['STATE'] = urldecode($_GET['state2']);
		if( isset($_GET['zip2']) && $_GET['zip2'] <> '' )
			$address2['ZIP_CODE'] = urldecode($_GET['zip2']);
		if( isset($_GET['country2']) && $_GET['country2'] <> '' )
			$address2['COUNTRY'] = urldecode($_GET['country2']);


		$distance = $zip_table->get_distance_various( $address1, $address2 );
	}

	$hours = $distance > 0 ? (round( $distance / $mph, 0) + $load_time) : 0;
	$last_changed = '';
	
	if( $distance > 0 && isset($_GET['shipment']) && $_GET['shipment'] > 0 ) {
		$shipment_table = sts_shipment::getInstance($exspeedite_db, $sts_debug);
		$expired = false;
		
		//! SCR# 367 - make sure it is not expired
		if( isset($_GET['CHANGED'])) {
			$check = $shipment_table->fetch_rows( "SHIPMENT_CODE = ".$_GET['shipment'].
				" AND CHANGED_DATE > '".$_GET['CHANGED']."'",
				"SHIPMENT_CODE, CHANGED_DATE" );
			if( is_array($check) && count($check) == 1 &&
				! empty($check[0]["CHANGED_DATE"])) {
				$expired = true;
			}
		}
		
		if( ! $expired ) {
			$shipment_table->update( $_GET['shipment'], array('DISTANCE' => $distance,
				'DISTANCE_SOURCE' => ($got_mm ? 'Manual' : $zip_table->get_source() ),
				'DISTANCE_LAST_AT' => ($got_mm ? $last_at : $zip_table->get_last_at() )), false );

			$shipment_table->log_event( 'exp_distance_grid: UPDATED shipment '.$_GET['shipment'].
				' by = '.(isset($_SESSION['EXT_USERNAME']) ? $_SESSION['EXT_USERNAME'] : 'unknown'),
				EXT_ERROR_DEBUG );
			$check = $shipment_table->fetch_rows( "SHIPMENT_CODE = ".$_GET['shipment'],
				"SHIPMENT_CODE, CHANGED_DATE" );
			if( is_array($check) && count($check) == 1 &&
				! empty($check[0]["CHANGED_DATE"])) {
				$last_changed = $check[0]["CHANGED_DATE"];
			}
		} else {
			$shipment_table->log_event( 'exp_distance_grid: NOT UPDATED shipment '.$_GET['shipment'].
				' by = '.(isset($_SESSION['EXT_USERNAME']) ? $_SESSION['EXT_USERNAME'] : 'unknown'),
				EXT_ERROR_DEBUG );
		}
	}
	
	$result = array( $distance, $hours );
	if( $got_mm ) {
		$result[] = 'Manual';
		$result[] = $last_at;
	} else if( $distance >= 0 ) {
		$result[] = $zip_table->get_source();
		$result[] = $zip_table->get_last_at();
	}
	//! SCR# 367 - include last changed date/time
	$result[] = $last_changed;
	

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

