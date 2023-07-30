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
$my_session->access_check( $sts_table_access[CONTACT_INFO_TABLE] );	// Make sure we should be here

require_once( "include/sts_contact_info_class.php" );

$contact_info_table = new sts_contact_info($exspeedite_db, $sts_debug);

$source = "";
if( isset($_GET['TYPE']) && isset($_GET['CODE']) ) {
	$result = $contact_info_table->fetch_rows("CONTACT_INFO_CODE = ".$_GET['CODE'], "CONTACT_CODE, CONTACT_SOURCE");
	
	if( $result && count($result) == 1 && isset($result[0]['CONTACT_SOURCE']) ) {
		$code = $result[0]['CONTACT_CODE'];
		$source = $result[0]['CONTACT_SOURCE'];
		$result = $contact_info_table->delete( $_GET['CODE'], $_GET['TYPE'] );
	}

	if( $sts_debug ) echo "<p>result = ".($result ? 'true' : 'false '.$contact_info_table->error())."</p>";
}

	if( $sts_debug ) {
		echo "<p>code, source = </p>
		<pre>";
		var_dump($code, $source);
		echo "</pre>";
		die;
	}

if( ! $sts_debug ) {
	switch( $source ) {
		case 'client':
			reload_page ( "exp_editclient.php?CODE=".$code );	// Back to edit client page
			
		case 'driver':
			reload_page ( "exp_editdriver.php?CODE=".$code );	// Back to edit drivers page
			
		case 'carrier':
			reload_page ( "exp_editcarrier.php?CODE=".$code );	// Back to edit carriers page
			
		default:
			reload_page ( "index.php");	// Go to home page as we don't know where else to go
	
	}
}
