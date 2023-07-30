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

require_once( "include/sts_load_class.php" );

if( isset($_GET['LOAD']) && isset($_GET['DRIVER']) && isset($_GET['CODE']) && $_GET['CODE'] == 'Autumn') {

	$load_table = sts_load::getInstance($exspeedite_db, $sts_debug);
	$result = $load_table->check_driver_available( $_GET['LOAD'], $_GET['DRIVER'] );

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

