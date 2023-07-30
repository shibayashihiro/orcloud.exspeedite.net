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
require_once( "include/sts_detail_class.php" );

if( isset($_GET['shipment']) && isset($_GET['code']) && $_GET['code'] == 'Smashed') {

	$shipment_table = sts_shipment::getInstance($exspeedite_db, $sts_debug);
	$detail_table = sts_detail::getInstance($exspeedite_db, $sts_debug);
	$result = $shipment_table->get_osd_info( $_GET['shipment'] );
	if( is_array($result) )
		$result['DETAIL'] = $detail_table->get_osd_info( $_GET['shipment'] );

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

