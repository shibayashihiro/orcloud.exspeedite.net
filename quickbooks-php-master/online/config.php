<?php

// Turn on some error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// no direct access
defined('_STS_INCLUDE') or die('Restricted access');

//! Work around to avoid multiple includes.
if( ! defined('_STS_QBOE_CONFIG') ) {
	define('_STS_QBOE_CONFIG', 1);

	require_once(dirname(__FILE__) .'/../../include/sts_config.php');
	$sts_debug = (isset($_GET['debug'])  || isset($_POST['debug']));

	// Set flag that this is session readonly
	define( '_STS_SESSION_READONLY', 1 );
	
	// Set flag that this is an ajax call
	define( '_STS_SESSION_AJAX', 1 );
	
	// Setup Session
	require_once( "include/sts_session_setup.php" );
	
	require_once( "include/sts_table_class.php" );
	require_once( "include/sts_shipment_class.php" );
	require_once( "include/sts_load_class.php" );
	require_once( "include/sts_driver_pay_master_class.php" );
	require_once( "include/sts_driver_class.php" );
	require_once( "include/sts_setting_class.php" );
	require_once( "include/sts_email_class.php" );
	require_once( "include/sts_user_class.php" );
	require_once( "include/sts_company_tax_class.php" );
	
	function http_error( $code ) {
		$sts_php_error = array(
		    100 => 'Continue',
		    101 => 'Switching Protocols',
		    102 => 'Processing', // WebDAV; RFC 2518
		    200 => 'OK',
		    201 => 'Created',
		    202 => 'Accepted',
		    203 => 'Non-Authoritative Information', // since HTTP/1.1
		    204 => 'No Content',
		    205 => 'Reset Content',
		    206 => 'Partial Content',
		    207 => 'Multi-Status', // WebDAV; RFC 4918
		    208 => 'Already Reported', // WebDAV; RFC 5842
		    226 => 'IM Used', // RFC 3229
		    300 => 'Multiple Choices',
		    301 => 'Moved Permanently',
		    302 => 'Found',
		    303 => 'See Other', // since HTTP/1.1
		    304 => 'Not Modified',
		    305 => 'Use Proxy', // since HTTP/1.1
		    306 => 'Switch Proxy',
		    307 => 'Temporary Redirect', // since HTTP/1.1
		    308 => 'Permanent Redirect', // approved as experimental RFC
		    400 => 'Bad Request',
		    401 => 'Unauthorized',
		    402 => 'Payment Required',
		    403 => 'Forbidden',
		    404 => 'Not Found',
		    405 => 'Method Not Allowed',
		    406 => 'Not Acceptable',
		    407 => 'Proxy Authentication Required',
		    408 => 'Request Timeout',
		    409 => 'Conflict',
		    410 => 'Gone',
		    411 => 'Length Required',
		    412 => 'Precondition Failed',
		    413 => 'Request Entity Too Large',
		    414 => 'Request-URI Too Long',
		    415 => 'Unsupported Media Type',
		    416 => 'Requested Range Not Satisfiable',
		    417 => 'Expectation Failed',
		    418 => 'I\'m a teapot', // RFC 2324
		    419 => 'Authentication Timeout', // not in RFC 2616
		    420 => 'Enhance Your Calm', // Twitter
		    420 => 'Method Failure', // Spring Framework
		    422 => 'Unprocessable Entity', // WebDAV; RFC 4918
		    423 => 'Locked', // WebDAV; RFC 4918
		    424 => 'Failed Dependency', // WebDAV; RFC 4918
		    424 => 'Method Failure', // WebDAV)
		    425 => 'Unordered Collection', // Internet draft
		    426 => 'Upgrade Required', // RFC 2817
		    428 => 'Precondition Required', // RFC 6585
		    429 => 'Too Many Requests', // RFC 6585
		    431 => 'Request Header Fields Too Large', // RFC 6585
		    444 => 'No Response', // Nginx
		    449 => 'Retry With', // Microsoft
		    450 => 'Blocked by Windows Parental Controls', // Microsoft
		    451 => 'Redirect', // Microsoft
		    451 => 'Unavailable For Legal Reasons', // Internet draft
		    494 => 'Request Header Too Large', // Nginx
		    495 => 'Cert Error', // Nginx
		    496 => 'No Cert', // Nginx
		    497 => 'HTTP to HTTPS', // Nginx
		    499 => 'Client Closed Request', // Nginx
		    500 => 'Internal Server Error',
		    501 => 'Not Implemented',
		    502 => 'Bad Gateway',
		    503 => 'Service Unavailable',
		    504 => 'Gateway Timeout',
		    505 => 'HTTP Version Not Supported',
		    506 => 'Variant Also Negotiates', // RFC 2295
		    507 => 'Insufficient Storage', // WebDAV; RFC 4918
		    508 => 'Loop Detected', // WebDAV; RFC 5842
		    509 => 'Bandwidth Limit Exceeded', // Apache bw/limited extension
		    510 => 'Not Extended', // RFC 2774
		    511 => 'Network Authentication Required', // RFC 6585
		    598 => 'Network read timeout error', // Unknown
		    599 => 'Network connect timeout error', // Unknown
		);
		return $code. (isset($sts_php_error[$code]) ? " (".$sts_php_error[$code].")" : "");
	}

	function format_addr( $line1, $line2, $city, $state, $postal, $country, $newline = '<br>' ) {
		return (isset($line1) && $line1 <> '' ? $line1.$newline : '').
			(isset($line2) && $line2 <> '' ? $line2.$newline : '').
			(isset($city) && $city <> '' ? $city : '').
			(isset($state) && $state <> '' ? ', '.$state : '').
			(isset($postal) && $postal <> '' ? ', '.$postal : '').
			(isset($country) && $country <> '' ? $newline.$country : '');
	}
	
	function format_qb_addr( $addr, $country = false ) {
		if( is_object($addr)) {
			$line1 = $addr->getLine1();
			$line2 = $addr->getLine2();
			$city = $addr->getCity();
			$state = $addr->getCountrySubDivisionCode();
			$postal = $addr->getPostalCode();
			if( ! $country ) {
				$country = $addr->getCountry();
				if( $country == 'CA' ) $country = 'Canada';
				else if( $country == 'US' ) $country = 'USA';
				else $country = NULL;
			}
			$ret = format_addr( $line1, $line2, $city, $state, $postal, $country );
		} else {
			$ret = 'MISSING';
		}
		return $ret;
	}
	
	
	// Require the framework
	if( $sts_debug )
		echo "<p>config.php: require_once ".$sts_crm_dir."quickbooks-php-master/QuickBooks.php</p>";
	require_once($sts_crm_dir.'quickbooks-php-master/new/QuickBooks.php');
	
	if( ! isset($_SESSION) ) {
		$_SESSION = array();
		$user_table = new sts_user($exspeedite_db, false);
		$_SESSION['EXT_USER_CODE'] = $user_table->special_user( QUICKBOOKS_USER );
	}
	
	$load_table = sts_load::getInstance($exspeedite_db, $sts_debug);
	$driver_pay_master_table = sts_driver_pay_master::getInstance($exspeedite_db, $sts_debug);
	$driver_table = sts_driver::getInstance($exspeedite_db, $sts_debug);
	$shipment_table = sts_shipment::getInstance($exspeedite_db, $sts_debug);
	$billing_table = new sts_table($exspeedite_db, CLIENT_BILL, $sts_debug);
	$rates_table = new sts_table($exspeedite_db, CLIENT_BILL_RATES, $sts_debug);
	$setting_table = sts_setting::getInstance($exspeedite_db, $sts_debug);
	
	$sts_url_prefix = $setting_table->get( 'main', 'URL_PREFIX' );
	$sts_qb_company_file = $setting_table->get( 'api', 'QUICKBOOKS_COMPANY_FILE' );
	$sts_qb_log_file = $setting_table->get( 'api', 'QUICKBOOKS_LOG_FILE' );
	
	//! SCR# 451 - Get diag level
	$sts_api_diag_level = $setting_table->get( 'api', 'QUICKBOOKS_DIAG_LEVEL' );
	$sts_qb_diag_level =  array_search(strtolower($sts_api_diag_level), $sts_error_level_label);
	if( $sts_qb_diag_level === false ) $sts_qb_diag_level = EXT_ERROR_ALL;
	
	$sts_qb_bill_terms = $setting_table->get( 'api', 'QUICKBOOKS_BILL_TERMS' );
	$sts_qb_invoice_terms = $setting_table->get( 'api', 'QUICKBOOKS_INVOICE_TERMS' );
	$sts_qb_invoice_prefix = $setting_table->get( 'api', 'QUICKBOOKS_INVOICE_PREFIX' );
	$sts_qb_bill_prefix = $setting_table->get( 'api', 'QUICKBOOKS_BILL_PREFIX' );
	$sts_qb_driver_bill_prefix = $setting_table->get( 'api', 'QUICKBOOKS_DRIVER_BILL_PREFIX' );
	$sts_qb_max_retries = $setting_table->get( 'api', 'QUICKBOOKS_MAX_RETRIES' );
	$sts_qb_class = $setting_table->get( 'api', 'QUICKBOOKS_CLASS' );
	$sts_qb_detail = $setting_table->get( 'api', 'QUICKBOOKS_DETAIL' );
	$sts_qb_trans_date = $setting_table->get( 'api', 'QUICKBOOKS_TRANSACTION_DATE' );
	$sts_can_consolidate = $setting_table->get( 'option', 'CONSOLIDATE_SHIPMENTS' ) == 'true';
	
	$sts_qb_driver_suffix = $setting_table->get( 'api', 'QUICKBOOKS_DRIVER_SUFFIX' );
	$sts_qb_carrier_suffix = $setting_table->get( 'api', 'QUICKBOOKS_CARRIER_SUFFIX' );
	
	$sts_qb_online = $setting_table->get( 'api', 'QUICKBOOKS_ONLINE' ) == 'true';
	$sts_qb_sandbox = $setting_table->get( 'api', 'QUICKBOOKS_SANDBOX' ) == 'true';

	//! SCR# 239 - multi-company, use multiple Quickbooks companies
	$multi_company = $setting_table->get("option", "MULTI_COMPANY") == 'true';

	//! SCR# 257 - Multiple currencies
	$multi_currency = $setting_table->get("option", "MULTI_CURRENCY") == 'true';
	$sts_qb_multi = $setting_table->get( 'api', 'QUICKBOOKS_MULTI_COMPANY' ) == 'true';
	
	if( $sts_debug ) echo "<p>config.php: after setting->get, before if( online )</p>";
	
	if( $sts_qb_online ) {
		//! Invoice Item Types - These need to be defined in Quickbooks
		//! Custom Field Names Definitions - These need to be defined in Quickbooks
		// These have migrated to settings in the QuickBooks category.
		
		//echo "<p>config 2</p>";
		
		$sts_qb_settings = $setting_table->get( 'QuickBooks' );
		//assert( count($sts_qb_settings)." > 0", "Unable to load Quickbooks settings" );
		
		foreach( $sts_qb_settings as $row ) {
			define($row["SETTING"], $row["THE_VALUE"]);
		}
		
		if( $sts_debug ) echo "<p>config.php: before custom defines</p>";
	
		// Custom fields max 30 chars, or you get an error.
		define( 'CUSTOM_FIELD_MAX_LENGTH',		30 );
		define( 'ADDR_FIELD_MAX_LENGTH',		41 );
		define( 'CITY_FIELD_MAX_LENGTH',		31 );
		define( 'STATE_FIELD_MAX_LENGTH',		21 );
		define( 'POSTAL_FIELD_MAX_LENGTH',		13 );
		define( 'COUNTRY_FIELD_MAX_LENGTH',		31 );
		define( 'PONUMBER_FIELD_MAX_LENGTH',	25 );
		define( 'FULLNAME_FIELD_MAX_LENGTH',	31 );
		define( 'NAME_FIELD_MAX_LENGTH',		41 );
		define( 'PHONE_FIELD_MAX_LENGTH',		21 );
		define( 'EMAIL_FIELD_MAX_LENGTH',		1023 );
		define( 'CLASS_FIELD_MAX_LENGTH',		159 );
		define( 'REFNUM_FIELD_MAX_LENGTH',		20 );
				
		$user = 'quickbooks';
		$pass = 'Exspeedite';

		$driver_options = array(		// See the comments in the QuickBooks/Driver/<YOUR DRIVER HERE>.php file ( i.e. 'Mysql.php', etc. )
			//'max_log_history' => 1024,	// Limit the number of quickbooks_log entries to 1024
			//'max_queue_history' => 64, 	// Limit the number of *successfully processed* quickbooks_queue entries to 64
			);
		
		// This is the URL of your OAuth auth handler page
		//$quickbooks_oauth_url = $sts_url_prefix.'quickbooks-php-master/online/oauth.php';
		
		// NEW version - this location routes it to the instances of Exspeedite
		// based upon the state parameter, before the '+'
		$quickbooks_oauth_url = 'https://exspeedite.com/exp_handle_qboe.php';
		
		// This is the URL to forward the user to after they have connected to IPP/IDS via OAuth
		$quickbooks_success_url = $sts_url_prefix.'quickbooks-php-master/online/success.php';
		//$quickbooks_success_url = 'https://exspeedite.com/exp_handle_qboe.php';
		
		// This is the menu URL script
		$quickbooks_menu_url = $sts_url_prefix.'quickbooks-php-master/online/menu.php';
			
		// Your application token (Intuit will give you this when you register an Intuit Anywhere app)
		if( $sts_qb_sandbox ) {
			$oauth2_client_id = 'ABnNYoMr2uEwAIwNPboKtbT3KlgXhmFt4ymWzSFOTsPX0aiiJK';
			$oauth2_client_secret = 'LxnGR8R3JG4SFJHF0rfJViotQIUOkWlhbCBcS3F8';
		} else {
			$oauth2_client_id = 'ABqRFWq5ny8GfHoUWhBXMFLIyMqKb7AaPaL2vnK9e0T7JlOk8F';
			$oauth2_client_secret = 'COxLQcWtbMGSNcLptrrkBSwasu5mDMUbQuWAd5XN';
		}

		// You should set this to an encryption key specific to your app
		$encryption_key = 'bcde1234';
		
		if( $sts_debug ) echo "<p>config.php: before driverFactory</p>";
		// This is used to get stuff from the queue
		$driver = QuickBooks_Utilities::driverFactory($sts_qb_dsn, $driver_options);
		
		// The tenant that user is accessing within your own app
		$the_tenant = 123456;
		
		$reconnect_err = '';
		$reconnected = false;
		
		// Scope required
		$scope = 'com.intuit.quickbooks.accounting ';
		
		function connect_to_quickbooks( $the_tenant = 123456 ) {
			global $sts_qb_dsn, $sts_qb_sandbox, $sts_url_prefix, $sts_debug,
				$quickbooks_oauth_url, $quickbooks_success_url,
				$oauth2_client_id, $oauth2_client_secret, $encryption_key,
				$reconnect_err, $reconnected, $scope;
			
			$realm = $Context = false;
		
			if( $sts_debug ) echo "<p>".__FUNCTION__.": before initialized</p>";
			// Initialize the database tables for storing OAuth information
			if (!QuickBooks_Utilities::initialized($sts_qb_dsn))
			{
				// Initialize creates the neccessary database schema for queueing up requests and logging
				if( $sts_debug ) echo "<p>".__FUNCTION__.": before initialize</p>";
				QuickBooks_Utilities::initialize($sts_qb_dsn);
			}
			
			// Instantiate our Intuit Anywhere auth handler 
			// 
			// The parameters passed to the constructor are:
			//	$dsn					
			//	$oauth_consumer_key		Intuit will give this to you when you create a new Intuit Anywhere application at AppCenter.Intuit.com
			//	$oauth_consumer_secret	Intuit will give this to you too
			//	$this_url				This is the full URL (e.g. http://path/to/this/file.php) of THIS SCRIPT
			//	$that_url				After the user authenticates, they will be forwarded to this URL
			// 
			if( $sts_debug ) echo "<p>".__FUNCTION__.": before QuickBooks_IPP_IntuitAnywhere</p>";
			$IntuitAnywhere = new QuickBooks_IPP_IntuitAnywhere(QuickBooks_IPP_IntuitAnywhere::OAUTH_V2,
				 $sts_qb_sandbox, $scope, $sts_qb_dsn, $encryption_key, $oauth2_client_id,
				 $oauth2_client_secret, $quickbooks_oauth_url, $quickbooks_success_url);
			
			// Are they connected to QuickBooks right now? 
			if ($IntuitAnywhere->check($the_tenant) and 
				$IntuitAnywhere->test($the_tenant))
			{
				if( $sts_debug ) echo "<p>".__FUNCTION__.": after check & test $the_tenant</p>";
				// Yes, they are 
				$quickbooks_is_connected = true;
			
				//if( $sts_debug ) echo "<p>".__FUNCTION__.": before QuickBooks_IPP</p>";
				// Set up the IPP instance
				$IPP = new QuickBooks_IPP($sts_qb_dsn, $encryption_key);
			
				// Get our OAuth credentials from the database
				$creds = $IntuitAnywhere->load($the_tenant);
			
				// Tell the framework to load some data from the OAuth store
				$IPP->authMode(
					QuickBooks_IPP::AUTHMODE_OAUTHV2, 
					$creds);
			
				if ($sts_qb_sandbox)
				{
					// Turn on sandbox mode/URLs 
					$IPP->sandbox(true);
				}
			
				// Print the credentials we're using
				//print_r($creds);
			
				// This is our current realm
				$realm = $creds['qb_realm'];
			
				// Load the OAuth information from the database
				$Context = $IPP->context();
			
				// Get some company info
				//$CompanyInfoService = new QuickBooks_IPP_Service_CompanyInfo();
				//$quickbooks_CompanyInfo = $CompanyInfoService->get($Context, $realm);

//Dunc1
				$reconnect_err = '';
				$reconnected = false;
				
				/*
				$expiry = $IntuitAnywhere->expiry($the_tenant);
				
				if ($expiry == QuickBooks_IPP_IntuitAnywhere::EXPIRY_SOON) {
					if ($IntuitAnywhere->reconnect($the_tenant)) {
						$reconnected = true;
					} else {
						$reconnected = false;
						$quickbooks_is_connected = false;
						$reconnect_err = $IntuitAnywhere->errorNumber() . ': ' . $IntuitAnywhere->errorMessage();
					}
				
					//print_r($IntuitAnywhere->load($the_tenant));
					//print("\n\n\n");
					//print($IntuitAnywhere->lastRequest());
					//print("\n\n\n");
					//print($IntuitAnywhere->lastResponse());
				} else if ($expiry == QuickBooks_IPP_IntuitAnywhere::EXPIRY_NOTYET) {
					$reconnect_err = 'Not old enough to require reconnect/refresh.';
				} else if ($expiry == QuickBooks_IPP_IntuitAnywhere::EXPIRY_EXPIRED) {
					$reconnect_err = 'This connection has already expired. You\'ll have to go through the initial connection process again.';
					$quickbooks_is_connected = false;
				} else if ($expiry == QuickBooks_IPP_IntuitAnywhere::EXPIRY_UNKNOWN) {
					$reconnect_err = 'Are you sure you\'re connected? No connection information was found for this user/tenant...';
					$quickbooks_is_connected = false;
				}
				*/
//Dunc2
			}
			else
			{
				if( $sts_debug ) echo "<p>".__FUNCTION__.": after check & test $the_tenant - FAILED</p>";
				// No, they are not
				$quickbooks_is_connected = false;
			}
			return array($quickbooks_is_connected, $realm, $Context);
		}
	}
//echo "<p>config 4</p>";
if( $sts_debug ) echo "<p>config.php: end</p>";
} else {
	echo "<p>config.php included more than once!</p>";
}

?>