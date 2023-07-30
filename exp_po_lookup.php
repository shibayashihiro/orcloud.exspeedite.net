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
$sts_debug = isset($_GET['debug']);

require_once( "include/sts_shipment_class.php" );

if( isset($_GET['po']) && isset($_GET['shipment']) && isset($_GET['code']) && $_GET['code'] == 'Fisherman') {

	$shipment_table = sts_shipment::getInstance($exspeedite_db, $sts_debug);
	$check = $shipment_table->check_duplicate_po_numbers( $_GET['shipment'], explode('+', $_GET['po']) );
	
	if( count($check) > 0 ) {
		$result = array( "RESPONSE" => true );
		$result["TEXT"] = "Duplicate PO# found in the following shipments:\n";
		foreach( $check as $row ) {
			$result["TEXT"] .= "#".$row["SHIPMENT_CODE"]." (".
				$shipment_table->state_name[$row["CURRENT_STATUS"]].") ".
				date("m/d", strtotime($row["PICKUP_DATE"]))." - ".
				date("m/d", strtotime($row["DELIVER_DATE"]));
			
			$pos = array();
			if( isset($row["PO_NUMBER"]) && $row["PO_NUMBER"] <> '')
				$pos[] = $row["PO_NUMBER"];
			if( isset($row["PO_NUMBER2"]) && $row["PO_NUMBER2"] <> '')
				$pos[] = $row["PO_NUMBER2"];
			if( isset($row["PO_NUMBER3"]) && $row["PO_NUMBER3"] <> '')
				$pos[] = $row["PO_NUMBER3"];
			if( isset($row["PO_NUMBER4"]) && $row["PO_NUMBER4"] <> '')
				$pos[] = $row["PO_NUMBER4"];
			if( isset($row["PO_NUMBER5"]) && $row["PO_NUMBER5"] <> '')
				$pos[] = $row["PO_NUMBER5"];
			
			if( count($pos) > 0 ) {
				$result["TEXT"] .= " POs: ".implode(', ', $pos);
			}	
				
			$result["TEXT"] .= "\n";
		}
		
	} else
		$result = array( "RESPONSE" => false );
	

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

