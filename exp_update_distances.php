<?php 

if( ini_get('safe_mode') ){
   // safe mode is on
   ini_set('max_execution_time', 1200);		// Set timeout to 20 minutes
   ini_set('memory_limit', '1024M');
}else{
   // it's not
   set_time_limit(1200);
}

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
$my_session->access_check( $sts_table_access[STOP_TABLE] );	// Make sure we should be here

require_once( "include/sts_stop_class.php" );
require_once( "include/sts_load_class.php" );
require_once( "include/sts_email_class.php" );

$stop_table = sts_stop::getInstance($exspeedite_db, $sts_debug);
$load_table = sts_load::getInstance($exspeedite_db, $sts_debug);

// close the session here to avoid blocking
session_write_close();

if( isset($_GET['CODE']) && isset($_GET['PW']) && $_GET['PW'] == 'Buntzen' ) {
	$load_table->update_distances( $_GET['CODE'] );
	
	$result = $stop_table->fetch_rows( "LOAD_CODE = ".$_GET['CODE'], "SEQUENCE_NO, STOP_DISTANCE", "SEQUENCE_NO ASC" );
		
	if( $sts_debug ) {
		echo "<p>result = </p>
		<pre>";
		var_dump($result);
		echo "</pre>";
	} else {
		echo json_encode( $result );
	}

}