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

require_once( "include/sts_driver_class.php" );
require_once( "include/sts_tractor_class.php" );
require_once( "include/sts_trailer_class.php" );
require_once( "include/sts_carrier_class.php" );

if( isset($_GET['query']) && isset($_GET['resource']) && isset($_GET['load']) &&
	isset($_GET['code']) && $_GET['code'] == 'Staples') {

	switch( $_GET['resource'] ) {
		case 'driver':
			$resource_table = sts_driver::getInstance($exspeedite_db, $sts_debug);
			break;

		case 'tractor':
			$resource_table = sts_tractor::getInstance($exspeedite_db, $sts_debug);
			break;

		case 'trailer':
			$resource_table = sts_trailer::getInstance($exspeedite_db, $sts_debug);
			break;

		case 'carrier':
			$resource_table = sts_carrier::getInstance($exspeedite_db, $sts_debug);
			break;

		default:
			$resource_table = false;
			break;
	}

	if( $resource_table ) {
		$result = $resource_table->suggest( $_GET['load'], $_GET['query'] );
	
		if( $sts_debug ) {
			echo "<p>result = </p>
			<pre>";
			var_dump($result);
			echo "</pre>";
		} else {
			echo json_encode( $result );
		}
	}
}


?>

