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

if( isset($_GET['query']) && isset($_GET['type']) && isset($_GET['code']) && $_GET['code'] == 'Vinegar') {

	$client_table = sts_client::getInstance($exspeedite_db, $sts_debug);
	$result = $client_table->suggest( $_GET['query'], $_GET['type'] );

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

