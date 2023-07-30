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

require_once( "include/sts_yard_container_class.php" );

if( ! empty($_GET['shipment']) && ! empty($_GET['container']) &&
	$_GET['pw'] == 'EnglishTea') {

	$yc_table = sts_yard_container::getInstance( $exspeedite_db, $sts_debug );
	$result = $yc_table->in_use( $_GET['container'], $_GET['shipment'] );

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

