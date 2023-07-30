<?php

/**
 * Example of OAuth authentication for an Intuit Anywhere application
 * 
 * 
 * 
 * @package QuickBooks
 * @subpackage Documentation
 */

// Set flag that this is a parent file
define( '_STS_INCLUDE', 1 );

/**
 * Require the QuickBooks library
 */
require_once dirname(__FILE__) . '/../new/QuickBooks.php';

/**
 * Require some IPP/OAuth configuration data
 */
require_once dirname(__FILE__) . '/config.php';
require_once dirname(__FILE__) . '/qb_online_api.php';

//$api = sts_quickbooks_online_API::getInstance($Context, $realm, $exspeedite_db, $sts_debug);

if( $multi_company && $sts_qb_multi ) {
	require_once( "include/sts_company_class.php" );
	$company_table = sts_company::getInstance($exspeedite_db, $sts_debug);
	$the_tenant = 1;
}

//! Uncomment this to log calls
$api = sts_quickbooks_online_API::getInstance(false, false, $exspeedite_db, $sts_debug);

$api->log_event( "oauth: referrer = ".
	(isset($_SERVER["HTTP_REFERER"]) ? $_SERVER["HTTP_REFERER"] : 'UNKNOWN_REFERER').
	"\nsts_url_prefix = ".$sts_url_prefix.
	"\nSERVER = ".print_r($_SERVER, true).
	"\nGET = ".print_r($_GET, true) );

//$api->log_event( "oauth: the_username = ".
//	(isset($_COOKIE["the_username"]) ? $_COOKIE["the_username"] : 'not set')." the_tenant = ".
//	(isset($_COOKIE["the_tenant"]) ? $_COOKIE["the_tenant"] : 'not set'), EXT_ERROR_DEBUG);

$the_tenant = $_COOKIE["the_tenant"];

	/*
	echo "<pre>";
	var_dump($_GET);
	var_dump($_POST);
	echo "</pre>";
	die;
	*/

$IntuitAnywhere = new QuickBooks_IPP_IntuitAnywhere(QuickBooks_IPP_IntuitAnywhere::OAUTH_V2,
	 $sts_qb_sandbox, $scope, $sts_qb_dsn, $encryption_key, $oauth2_client_id,
	 $oauth2_client_secret, $quickbooks_oauth_url, $quickbooks_success_url);

//! Set a prefix to identify which instance of Exspeedite this is
if( preg_match('#https://(.*)\.exspeedite\.net#', $sts_crm_root, $matches) ) {
	$client = $matches[1];
} else {
	$client = 'dev';
}
$IntuitAnywhere->setStatePrefix( $client );

			
// Try to handle the OAuth request 
if ($IntuitAnywhere->handle($the_tenant))
{
	; // The user has been connected, and will be redirected to $that_url automatically. 
}
else
{
	// If this happens, something went wrong with the OAuth handshake
	die('Oh no, something bad happened: ' . $IntuitAnywhere->errorNumber() . ': ' . $IntuitAnywhere->errorMessage());
}


