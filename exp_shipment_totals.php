<?php 

// $Id: exp_shipment_totals.php 4697 2022-03-09 23:02:23Z duncan $
// Populate data from commodities to shipment table

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
require_once( "include/sts_detail_class.php" );

if( isset($_GET['code']) && isset($_GET['pw']) && $_GET['pw'] == 'Velocity') {

	$shipment_table = sts_shipment::getInstance($exspeedite_db, $sts_debug);
	$result1 = $shipment_table->totals( $_GET['code'] );

	$detail = sts_detail::getInstance($exspeedite_db, $sts_debug);
	
	$result2 = $detail->update_hazmat( $_GET['code'] );
	
	$result = array_merge($result1[0], $result2);

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

