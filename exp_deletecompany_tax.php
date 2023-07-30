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

require_once( "include/sts_company_tax_class.php" );

$company_tax_table = sts_company_tax::getInstance($exspeedite_db, $sts_debug);

if( isset($_GET['CODE']) ) {
	$check = $company_tax_table->fetch_rows("COMPANY_TAX_CODE = ".$_GET['CODE'], "COMPANY_CODE");
	$company_code = 0;
	if( is_array($check) && count($check) > 0 && isset($check[0]["COMPANY_CODE"]))
		$company_code = $check[0]["COMPANY_CODE"];
	
	$result = $company_tax_table->delete( $_GET['CODE'], 'permdel' );
	if( $sts_debug ) echo "<p>result = ".($result ? 'true' : 'false '.$company_tax_table->error())."</p>";
}

if( ! $sts_debug ) {
	if( $company_code > 0 )
		reload_page ( "exp_editcompany.php?CODE=".$company_code );
	else
		reload_page ( "exp_listcompany.php" );
}

?>