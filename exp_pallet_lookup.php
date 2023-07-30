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

if( isset($_GET['client']) && isset($_GET['code']) && $_GET['code'] == 'Frost') {

	$client_table = sts_client::getInstance($exspeedite_db, $sts_debug);
	$result = $client_table->get_pallet_count( $_GET['client'] );
	$result2 = is_array($result) && isset($result[0]['PALLET_COUNT']) ? intval($result[0]['PALLET_COUNT']) : 'none';

	if( $sts_debug ) {
		echo "<p>result = </p>
		<pre>";
		var_dump($result2);
		echo "</pre>";
	} else {
		echo json_encode( $result2 );
	}
}


?>

