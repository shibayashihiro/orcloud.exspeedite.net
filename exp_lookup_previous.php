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
$sts_debug = isset($_GET['debug']) && in_group( EXT_GROUP_DEBUG );
require_once( "include/sts_session_class.php" );

$my_session = sts_session::getInstance( $exspeedite_db, $sts_debug );
$my_session->access_check( $sts_table_access[STATUS_CODES_TABLE] );	// Make sure we should be here

require_once( "include/sts_status_codes_class.php" );

$status_codes_table = sts_status_codes::getInstance($exspeedite_db, $sts_debug);

if( isset($_GET['SOURCE']) && isset($_GET['PW']) && $_GET['PW'] == 'Chips' ) {
	$result = $status_codes_table->lookup_previous( $_GET['SOURCE'] );
	if( $sts_debug ) {
		echo "<p>result = </p>
		<pre>";
		var_dump($result);
		echo "</pre>";
	} else {
		echo json_encode( $result );
	}
}
