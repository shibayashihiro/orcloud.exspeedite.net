<?php

if( ini_get('safe_mode') ){
   // safe mode is on
   ini_set('max_execution_time', 1200);		// Set timeout to 20 minutes
   ini_set('memory_limit', '1024M');
}else{
   // it's not
   set_time_limit(1200);
}

// Set flag that this is a parent file
define( '_STS_INCLUDE', 1 );

// Set flag that this is session readonly
define( '_STS_SESSION_READONLY', 1 );

// Set flag that this is an ajax call
define( '_STS_SESSION_AJAX', 1 );

// Setup Session
require_once( "include/sts_session_setup.php" );

// I always program in E_STRICT error mode... 
error_reporting(E_ALL | E_STRICT);
ini_set('display_errors', 1);

require_once('include/sts_config.php');
$sts_debug = isset($_GET['debug']) && in_group( EXT_GROUP_DEBUG );

$sts_subtitle = "Update Shipments";
require_once( "include/header_inc.php" );

?>
<div class="container-full" role="main">
<?php

require_once( "include/sts_shipment_class.php" );
require_once( "include/sts_manual_miles_class.php" );
require_once( "include/sts_zip_class.php" );

// close the session here to avoid blocking
session_write_close();

if( isset($_GET["PW"]) && $_GET["PW"] == "Wildcat" ) {
	$shipment_table = sts_shipment::getInstance($exspeedite_db, $sts_debug);
	$manual_miles_table = new sts_manual_miles($exspeedite_db, $sts_debug);
	$zip_table = new sts_zip($exspeedite_db, $sts_debug);
	
	// Turn off output buffering
	ini_set('output_buffering', 'off');
	// Turn off PHP output compression
	ini_set('zlib.output_compression', false);
	// Implicitly flush the buffer(s)
	ini_set('implicit_flush', true);
	ob_implicit_flush(true);

	if (ob_get_level() == 0) ob_start();
	
	$matches = $shipment_table->fetch_rows("CURRENT_STATUS = ".$shipment_table->behavior_state["dropped"],
		"SHIPMENT_CODE, BILLTO_NAME, SHIPPER_ADDR1, SHIPPER_ADDR2, SHIPPER_CITY,
		SHIPPER_STATE, SHIPPER_ZIP, SHIPPER_COUNTRY,
		CONS_ADDR1, CONS_ADDR2, CONS_CITY, CONS_STATE, CONS_ZIP, CONS_COUNTRY");
	
	if( is_array($matches) && count($matches) > 0 ) {
		foreach($matches as $row) {

			if( $sts_debug ) echo "<h2>exp_update_shipment_miles.php: update shipment ".$row["SHIPMENT_CODE"]."</h2>";

			echo "<p>Update shipment ".$row["SHIPMENT_CODE"]."</p>";
			ob_flush(); flush();

			$got_mm = false;
			$distance = intval($manual_miles_table->get_distance( 
				$row["BILLTO_NAME"], $row["SHIPPER_ZIP"], $row["CONS_ZIP"] ) );

			if( $distance >= 0 ) {
				$got_mm = true;
				$last_at = date("Y-m-d H:i:s");
			}
			
			if( $distance == -1 ) {
				$address1 = array();
				$address2 = array();
				
				if( isset($row['SHIPPER_ADDR1']) && $row['SHIPPER_ADDR1'] <> '' )
					$address1['ADDRESS'] = $row['SHIPPER_ADDR1'];
				if( isset($row['SHIPPER_ADDR2']) && $row['SHIPPER_ADDR2'] <> '' )
					$address1['ADDRESS2'] = $row['SHIPPER_ADDR2'];
				if( isset($row['SHIPPER_CITY']) && $row['SHIPPER_CITY'] <> '' )
					$address1['CITY'] = $row['SHIPPER_CITY'];
				if( isset($row['SHIPPER_STATE']) && $row['SHIPPER_STATE'] <> '' )
					$address1['STATE'] = $row['SHIPPER_STATE'];
				if( isset($row['SHIPPER_ZIP']) && $row['SHIPPER_ZIP'] <> '' )
					$address1['ZIP_CODE'] = $row['SHIPPER_ZIP'];
				if( isset($row['SHIPPER_COUNTRY']) && $row['SHIPPER_COUNTRY'] <> '' )
					$address1['COUNTRY'] = $row['SHIPPER_COUNTRY'];
			
				if( isset($row['CONS_ADDR1']) && $row['CONS_ADDR1'] <> '' )
					$address2['ADDRESS'] = $row['CONS_ADDR1'];
				if( isset($row['CONS_ADDR2']) && $row['CONS_ADDR2'] <> '' )
					$address2['ADDRESS2'] = $row['CONS_ADDR2'];
				if( isset($row['CONS_CITY']) && $row['CONS_CITY'] <> '' )
					$address2['CITY'] = $row['CONS_CITY'];
				if( isset($row['CONS_STATE']) && $row['CONS_STATE'] <> '' )
					$address2['STATE'] = $row['CONS_STATE'];
				if( isset($row['CONS_ZIP']) && $row['CONS_ZIP'] <> '' )
					$address2['ZIP_CODE'] = $row['CONS_ZIP'];
				if( isset($row['CONS_COUNTRY']) && $row['CONS_COUNTRY'] <> '' )
					$address2['COUNTRY'] = $row['CONS_COUNTRY'];

				$distance = $zip_table->get_distance_various( $address1, $address2 );
			}
					
			if( $distance > 0 ) {
				$shipment_table->update( $row["SHIPMENT_CODE"], array('DISTANCE' => $distance,
					'DISTANCE_SOURCE' => ($got_mm ? 'Manual' : $zip_table->get_source() ),
					'DISTANCE_LAST_AT' => ($got_mm ? $last_at : $zip_table->get_last_at() )), false );
			}
		}
	}
}

if( ! $sts_debug ) {
	reload_page ( "exp_listshipment.php" );	// Back to list shipment page
}