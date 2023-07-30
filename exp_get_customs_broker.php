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

require_once( "include/sts_client_class.php" );
require_once( "include/sts_shipment_class.php" );

// Get broker for a given bill-to client
if( isset($_GET['code']) && isset($_GET['pw']) && $_GET['pw'] == 'Diabetics' ) {
	$client_table = sts_client::getInstance($exspeedite_db, $sts_debug);
	
	$result = $client_table->get_customs_broker( $_GET['code'], isset($_GET['choice']) ? $_GET['choice'] : 0 );

	if( $sts_debug ) {
		echo "<p>result = </p>
		<pre>";
		var_dump($result);
		echo "</pre>";
	} else {
		echo json_encode( $result );
	}

// OR clear broker fields for shipment
} else if( isset($_GET['clear']) && isset($_GET['pw']) && $_GET['pw'] == 'Diabetics' ) {
	$shipment_table = sts_shipment::getInstance($exspeedite_db, $sts_debug);
	
	$result = $shipment_table->update($_GET['clear'], [
		'BROKER_NAME' => 'NULL',
		'BROKER_ADDR1' => 'NULL',
		'BROKER_ADDR2' => 'NULL',
		'BROKER_STATE' => 'NULL',
		'BROKER_CITY' => 'NULL',
		'BROKER_ZIP' => 'NULL',
		'BROKER_PHONE' => 'NULL',
		'BROKER_COUNTRY' => 'NULL',
		'BROKER_EXT' => 'NULL',
		'BROKER_FAX' => 'NULL',
		'BROKER_CELL' => 'NULL',
		'BROKER_CONTACT' => 'NULL',
		'BROKER_EMAIL' => 'NULL',
		
		'BROKER_CHOICE' => 0,
		'BROKER_VALID' => 'NULL',
		'BROKER_CODE' => 'NULL',
		'BROKER_DESCR' => 'NULL',
		'BROKER_VALID_SOURCE' => 'NULL',
		'BROKER_LAT' => 'NULL',
		'BROKER_LON' => 'NULL',
		
	], false);
	
	$result = $shipment_table->fetch_rows( "SHIPMENT_CODE = ".$_GET['clear'], "CHANGED_DATE" );

	if( $sts_debug ) {
		echo "<p>result = </p>
		<pre>";
		var_dump($result[0]["CHANGED_DATE"]);
		echo "</pre>";
	} else {
		echo json_encode( $result[0]["CHANGED_DATE"] );
	}
}


?>

