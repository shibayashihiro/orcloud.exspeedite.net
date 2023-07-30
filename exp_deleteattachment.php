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
$my_session->access_check( $sts_table_access[ATTACHMENT_TABLE] );	// Make sure we should be here

if( $sts_debug ) echo "<p>".__FILE__.": start</p>";
if( isset($_GET['CODE']) ) {
	require_once( "include/sts_attachment_class.php" );
	require_once( "include/sts_email_class.php" );

	$attachment_table = sts_attachment::getInstance($exspeedite_db, $sts_debug);
	
	$check = $attachment_table->fetch_rows( $attachment_table->primary_key." = ".$_GET['CODE'] );
	$source_type = 'unknown';
	if( isset($check) && is_array($check) && count($check) == 1 && 
		isset($check[0]["SOURCE_TYPE"]) && isset($check[0]["SOURCE_CODE"]) ) {
		$source_type = $check[0]["SOURCE_TYPE"];
		$source_code = $check[0]["SOURCE_CODE"];
	}
	$result = $attachment_table->delete_attachment( $_GET['CODE'] );

	if( $sts_debug ) echo "<p>result = ".($result ? 'true' : 'false '.$attachment_table->error())."</p>";
}

if( ! $sts_debug ) {
	switch( $source_type ) {
		case 'client':
			reload_page ( "exp_editclient.php?CODE=".$source_code );	// Back to edit client page
			
		case 'driver':
			reload_page ( "exp_editdriver.php?CODE=".$source_code );	// Back to edit drivers page
			
		case 'carrier':
			reload_page ( "exp_editcarrier.php?CODE=".$source_code );	// Back to edit carriers page
			
		//! SCR# 193 - add attachments to tractor and trailer
		case 'tractor':
			reload_page ( "exp_edittractor.php?CODE=".$source_code );

		case 'trailer':
			reload_page ( "exp_edittrailer.php?CODE=".$source_code );

		case 'company':
			reload_page ( "exp_editcompany.php?CODE=".$source_code );

		case 'office':
			reload_page ( "exp_editoffice.php?CODE=".$source_code );

		case 'shipment':
			reload_page ( "exp_addshipment.php?CODE=".$source_code );	// Back to add shipment page
			
		case 'load':
			reload_page ( "exp_viewload.php?CODE=".$source_code );	// Back to view load page
			
		case 'scr':
			reload_page ( "exp_editscr.php?CODE=".$source_code );	// Back to edit client page
			
		case 'insp_report':
			reload_page ( "exp_addinsp_report.php?REPORT=".$source_code );

		default:
			reload_page ( "index.php");	// Go to home page as we don't know where else to go
	
	}
}
?>