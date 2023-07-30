<?php 
// Set flag that this is a parent file
define( '_STS_INCLUDE', 1 );

// Setup Session
require_once( "include/sts_session_setup.php" );

require_once( "include/sts_config.php" );
$sts_debug = isset($_GET['debug']) && in_group( EXT_GROUP_DEBUG );
require_once( "include/sts_session_class.php" );

$my_session = sts_session::getInstance( $exspeedite_db, $sts_debug );

$table = new sts_table( DUMMY_DATABASE, "", $sts_debug );

if( isset($_GET['CODE']) && isset($_GET['PW']) &&
	$_GET['PW'] == $table->encryptData($_GET['CODE'].'Fuzzy') ) {
	// nothing	
} else {
	$my_session->access_check( $sts_table_access[ATTACHMENT_TABLE] );	// Make sure we should be here
}

if( isset($_GET['CODE']) ) {
	require_once( "include/sts_attachment_class.php" );

	$attachment_table = sts_attachment::getInstance($exspeedite_db, $sts_debug);
	
	$attachment_table->view( $_GET['CODE'] );
}
?>