<?php 

// $Id: exp_deleteinsp_list_item.php 4350 2021-03-02 19:14:52Z duncan $
// Delete inspection list item

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

require_once( "include/sts_insp_list_item_class.php" );

$insp_list_item_table = sts_insp_list_item::getInstance($exspeedite_db, $sts_debug);

$code = false;
if( isset($_GET['CODE']) ) {
	$result = $insp_list_item_table->fetch_rows("ITEM_CODE = ".$_GET['CODE'], "RM_FORM");
	
	if( $result && count($result) == 1 && isset($result[0]['RM_FORM']) ) {
		$code = $result[0]['RM_FORM'];

		$result = $insp_list_item_table->delete( $_GET['CODE'], 'permdel' );
	}
	
	if( $sts_debug ) echo "<p>result = ".($result ? 'true' : 'false '.$il_table->error())."</p>";
}

if( ! $sts_debug ) {
	if( $code ) 
		reload_page ( "exp_editrm_form.php?CODE=".$code );	// Back to list items page
	else
		reload_page ( "index.php");	// Go to home page as we don't know where else to go
}
?>