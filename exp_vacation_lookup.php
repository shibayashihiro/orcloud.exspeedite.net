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

require_once( "include/sts_vacation_class.php" );

if( isset($_GET['date1']) && isset($_GET['date2']) && isset($_GET['code']) && $_GET['code'] == 'Paypal') {

	$vacation_table = new sts_vacation($exspeedite_db, $sts_debug);
	$result = $vacation_table->working_days( $_GET['date1'], $_GET['date2'] );

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

