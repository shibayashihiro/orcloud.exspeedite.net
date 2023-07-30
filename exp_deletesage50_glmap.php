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
$my_session->access_check( EXT_GROUP_ADMIN );	// Make sure we should be here

require_once( "include/sts_sage50_glmap_class.php" );

$sage50_glmap_table = sts_sage50_glmap::getInstance($exspeedite_db, $sts_debug);

if( isset($_GET['CODE']) ) {
	$check = $sage50_glmap_table->fetch_rows("MAP_CODE = ".$_GET['CODE'], "OFFICE_CODE");
	$office_code = 0;
	if( is_array($check) && count($check) > 0 && isset($check[0]["OFFICE_CODE"]))
		$office_code = $check[0]["OFFICE_CODE"];
	
	$result = $sage50_glmap_table->delete( $_GET['CODE'], 'permdel' );
	if( $sts_debug ) echo "<p>result = ".($result ? 'true' : 'false '.$sage50_glmap_table->error())."</p>";
}

if( ! $sts_debug ) {
	if( $office_code > 0 )
		reload_page ( "exp_editoffice.php?CODE=".$office_code );
	else
		reload_page ( "exp_listoffice.php" );
}

?>