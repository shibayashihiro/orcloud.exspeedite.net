<?php

/**
 * Example QuickBooks SOAP Server / Web Service
 * 
 * This is an example Web Service which adds customers to QuickBooks desktop 
 * editions via the QuickBooks Web Connector. 
 * 
 * MAKE SURE YOU READ OUR QUICK-START GUIDE:
 * 	http://wiki.consolibyte.com/wiki/doku.php/quickbooks_integration_php_consolibyte_webconnector_quickstart
 * 	http://wiki.consolibyte.com/wiki/doku.php/quickbooks
 * 
 * You should copy this file and use this file as a reference for when you are 
 * creating your own Web Service to add, modify, query, or delete data from 
 * desktop versions of QuickBooks software. 
 * 
 * The basic idea behind this method of integration with QuickBooks desktop 
 * editions is to host this web service on your server and have the QuickBooks 
 * Web Connector connect to it and pass messages to QuickBooks. So, every time 
 * that an action occurs on your website which you wish to communicate to 
 * QuickBooks, you'll queue up a request (shown below, using the 
 * QuickBooks_Queue class). 
 * 
 * You'll write request handlers which generate qbXML requests for each type of 
 * action you queue up. Those qbXML requests will be passed by the Web 
 * Connector to QuickBooks, which will then process the requests and send back 
 * the responses. Your response handler will then process the response (you'll 
 * probably want to at least store the returned ListID or TxnID of anything you 
 * create within QuickBooks) and this pattern will continue until there are no 
 * more requests in the queue for QuickBooks to process. 
 * 
 * @author Keith Palmer <keith@consolibyte.com>
 * 
 * @package QuickBooks
 * @subpackage Documentation
 */

// I always program in E_STRICT error mode... 
error_reporting(E_ERROR | E_WARNING | E_PARSE);
ini_set('display_errors', 1);

chdir('..');

// Set flag that this is a parent file
define( '_STS_INCLUDE', 1 );

// Setup Session
require_once( dirname(__FILE__) ."/../include/sts_session_setup.php" );

if( isset($_GET) && isset($_GET['debug'])) {
	echo "session_path=$session_path\ninclude path=".
		ini_get('include_path').
		"\nconfig_dir=$config_dir\n\n";
}

require_once('include/sts_config.php');
if( isset($_GET) && isset($_GET['debug'])) {
	echo "include path=".
		ini_get('include_path').
		"\nsession save path=".ini_get('session.save_path')."\n\n";
}

require_once( "include/sts_table_class.php" );
require_once( "include/sts_shipment_class.php" );
require_once( "include/sts_load_class.php" );
require_once( "include/sts_driver_pay_master_class.php" );
require_once( "include/sts_driver_class.php" );
require_once( "include/sts_setting_class.php" );
require_once( "include/sts_email_class.php" );
require_once( "include/sts_user_class.php" );

// Require the framework
require_once('quickbooks-php-master/QuickBooks.php');

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

$sts_qb_company_file = $setting_table->get( 'api', 'QUICKBOOKS_COMPANY_FILE' );
if( isset($_GET) && isset($_GET['debug'])) {
	echo "\nQUICKBOOKS_COMPANY_FILE=$sts_qb_company_file\n\n";
}
$sts_qb_log_file = $setting_table->get( 'api', 'QUICKBOOKS_LOG_FILE' );
$sts_qb_bill_terms = $setting_table->get( 'api', 'QUICKBOOKS_BILL_TERMS' );
$sts_qb_invoice_terms = $setting_table->get( 'api', 'QUICKBOOKS_INVOICE_TERMS' );
$sts_qb_max_retries = $setting_table->get( 'api', 'QUICKBOOKS_MAX_RETRIES' );
$sts_qb_class = $setting_table->get( 'api', 'QUICKBOOKS_CLASS' );
$sts_qb_detail = $setting_table->get( 'api', 'QUICKBOOKS_DETAIL' );
$sts_qb_trans_date = $setting_table->get( 'api', 'QUICKBOOKS_TRANSACTION_DATE' );
$sts_qb_bill_prefix = $setting_table->get( 'api', 'QUICKBOOKS_BILL_PREFIX' );
$sts_qb_driver_bill_prefix = $setting_table->get( 'api', 'QUICKBOOKS_DRIVER_BILL_PREFIX' );

$sts_qb_driver_suffix = $setting_table->get( 'api', 'QUICKBOOKS_DRIVER_SUFFIX' );
$sts_qb_carrier_suffix = $setting_table->get( 'api', 'QUICKBOOKS_CARRIER_SUFFIX' );

//! Invoice Item Types - These need to be defined in Quickbooks
//! Custom Field Names Definitions - These need to be defined in Quickbooks
// These have migrated to settings in the QuickBooks category.

$sts_qb_settings = $setting_table->get( 'QuickBooks' );
assert( count($sts_qb_settings)." > 0", "Unable to load Quickbooks settings" );

foreach( $sts_qb_settings as $row ) {
	define($row["SETTING"], $row["THE_VALUE"]);
}

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



// A username and password you'll use in: 
//	a) Your .QWC file
//	b) The Web Connector
//	c) The QuickBooks framework
//
// 	NOTE: This has *no relationship* with QuickBooks usernames, Windows usernames, etc. 
// 		It is *only* used for the Web Connector and SOAP server! 
$user = 'quickbooks';
$pass = 'Exspeedite';

// The next three parameters, $map, $errmap, and $hooks, are callbacks which 
//	will be called when certain actions/events/requests/responses occur within 
//	the framework. The examples below show how to register callback 
//	*functions*, but you can actually register any of the following, using 
//	these formats:

//! Map QuickBooks actions to handler functions
$map = array(
	QUICKBOOKS_QUERY_CUSTOMER => array( '_quickbooks_customer_query_request', '_quickbooks_customer_query_response' ),
	QUICKBOOKS_ADD_CUSTOMER => array( '_quickbooks_customer_add_request', '_quickbooks_customer_add_response' ),
	QUICKBOOKS_ADD_INVOICE => array( '_quickbooks_invoice_add_request', '_quickbooks_invoice_add_response' ),
	QUICKBOOKS_MOD_DATAEXT => array( '_quickbooks_dataext_mod_request', '_quickbooks_dataext_mod_response' ),
	QUICKBOOKS_QUERY_VENDOR => array( '_quickbooks_vendor_query_request', '_quickbooks_vendor_query_response' ),
	QUICKBOOKS_ADD_VENDOR => array( '_quickbooks_vendor_add_request', '_quickbooks_vendor_add_response' ),
	QUICKBOOKS_ADD_BILL => array( '_quickbooks_bill_add_request', '_quickbooks_bill_add_response' ),
	);

// This is entirely optional, use it to trigger actions when an error is returned by QuickBooks
$errmap = array(
	500 => '_quickbooks_error_handler',
	'*' => '_quickbooks_error_catchall', 				// Using a key value of '*' will catch any errors which were not caught by another error handler
	// ... more error handlers here ...
	);

// An array of callback hooks
$hooks = array(
	// There are many hooks defined which allow you to run your own functions/methods when certain events happen within the framework
	// QuickBooks_WebConnector_Handlers::HOOK_LOGINSUCCESS => '_quickbooks_hook_loginsuccess', 	// Run this function whenever a successful login occurs
	);

/*
function _quickbooks_hook_loginsuccess($requestID, $user, $hook, &$err, $hook_data, $callback_config)
{
	// Do something whenever a successful login occurs...
}
*/

// Logging level
//$log_level = QUICKBOOKS_LOG_NORMAL;
$log_level = QUICKBOOKS_LOG_VERBOSE;
//$log_level = QUICKBOOKS_LOG_DEBUG;				
//$log_level = QUICKBOOKS_LOG_DEVELOP;		// Use this level until you're sure everything works!!!

// What SOAP server you're using 
//$soapserver = QUICKBOOKS_SOAPSERVER_PHP;			// The PHP SOAP extension, see: www.php.net/soap
$soapserver = QUICKBOOKS_SOAPSERVER_BUILTIN;		// A pure-PHP SOAP server (no PHP ext/soap extension required, also makes debugging easier)

$soap_options = array(		// See http://www.php.net/soap
	);

$handler_options = array(
	//'authenticate' => ' *** YOU DO NOT NEED TO PROVIDE THIS CONFIGURATION VARIABLE TO USE THE DEFAULT AUTHENTICATION METHOD FOR THE DRIVER YOU'RE USING (I.E.: MYSQL) *** '
	//'authenticate' => 'your_function_name_here', 
	//'authenticate' => array( 'YourClassName', 'YourStaticMethod' ),
	'deny_concurrent_logins' => false, 
	'deny_reallyfast_logins' => false,
	);		// See the comments in the QuickBooks/Server/Handlers.php file

$driver_options = array(		// See the comments in the QuickBooks/Driver/<YOUR DRIVER HERE>.php file ( i.e. 'Mysql.php', etc. )
	//'max_log_history' => 1024,	// Limit the number of quickbooks_log entries to 1024
	//'max_queue_history' => 64, 	// Limit the number of *successfully processed* quickbooks_queue entries to 64
	);

$callback_options = array(
	);


if (!QuickBooks_Utilities::initialized($sts_qb_dsn))
{
	// Initialize creates the neccessary database schema for queueing up requests and logging
	QuickBooks_Utilities::initialize($sts_qb_dsn);
	
	// This creates a username and password which is used by the Web Connector to authenticate
	QuickBooks_Utilities::createUser($sts_qb_dsn, $user, $pass, $sts_qb_company_file);
	
	// Queueing up a test request
	// 
	// You can instantiate and use the QuickBooks_Queue class to queue up 
	//	actions whenever you want to queue something up to be sent to 
	//	QuickBooks. So, for instance, a new customer is created in your 
	//	database, and you want to add them to QuickBooks: 
	//	
	//	Queue up a request to add a new customer to QuickBooks
	//	$Queue = new QuickBooks_Queue($sts_qb_dsn);
	//	$Queue->enqueue(QUICKBOOKS_ADD_CUSTOMER, $primary_key_of_new_customer);
	//	
	// Oh, and that new customer placed an order, so we want to create an 
	//	invoice for them in QuickBooks too: 
	// 
	//	Queue up a request to add a new invoice to QuickBooks
	//	$Queue->enqueue(QUICKBOOKS_ADD_INVOICE, $primary_key_of_new_order);
	// 
	// Remember that for each action type you queue up, you should have a 
	//	request and a response function registered by using the $map parameter 
	//	to the QuickBooks_Server class. The request function will accept a list 
	//	of parameters (one of them is $ID, which will be passed the value of 
	//	$primary_key_of_new_customer/order that you passed to the ->enqueue() 
	//	method and return a qbXML request. So, your request handler for adding 
	//	customers might do something like this: 
	// 
	//	$arr = mysql_fetch_array(mysql_query("SELECT * FROM my_customer_table WHERE ID = " . (int) $ID));
	//	// build the qbXML CustomerAddRq here
	//	return $qbxml;
	// 
	// We're going to queue up a request to add a customer, just as a test...
	// 
	// NOTE: You would normally *never* want to do this in this file! This is 
	//	meant as an initial test ONLY. See example_web_connector_queueing.php for more 
	//	details!
	// 
	// IMPORTANT NOTE: This particular example of queueing something up will 
	//	only ever happen *once* when these scripts are first run/used. After 
	//	this initial test, you MUST do your queueing in another script. DO NOT 
	//	DO YOUR OWN QUEUEING IN THIS FILE! See 
	//	docs/example_web_connector_queueing.php for more details and examples 
	//	of queueing things up.
	
	//$primary_key_of_your_customer = 5;

	//$Queue = new QuickBooks_WebConnector_Queue($sts_qb_dsn);
	//$Queue->enqueue(QUICKBOOKS_ADD_CUSTOMER, $primary_key_of_your_customer);
	
	// Also note the that ->enqueue() method supports some other parameters: 
	// 	string $action				The type of action to queue up
	//	mixed $ident = null			Pass in the unique primary key of your record here, so you can pull the data from your application to build a qbXML request in your request handler
	//	$priority = 0				You can assign priorities to requests, higher priorities get run first
	//	$extra = null				Any extra data you want to pass to the request/response handler
	//	$user = null				If you're using multiple usernames, you can pass the username of the user to queue this up for here
	//	$qbxml = null				
	//	$replace = true				
	// 
	// Of particular importance and use is the $priority parameter. Say a new 
	//	customer is created and places an order on your website. You'll want to 
	//	send both the customer *and* the sales receipt to QuickBooks, but you 
	//	need to ensure that the customer is created *before* the sales receipt, 
	//	right? So, you'll queue up both requests, but you'll assign the 
	//	customer a higher priority to ensure that the customer is added before 
	//	the sales receipt. 
	// 
	//	Queue up the customer with a priority of 10
	// 	$Queue->enqueue(QUICKBOOKS_ADD_CUSTOMER, $primary_key_of_your_customer, 10);
	//	
	//	Queue up the invoice with a priority of 0, to make sure it doesn't run until after the customer is created
	//	$Queue->enqueue(QUICKBOOKS_ADD_SALESRECEIPT, $primary_key_of_your_order, 0);
}

// Create a new server and tell it to handle the requests
$Server = new QuickBooks_WebConnector_Server($sts_qb_dsn, $map, $errmap, $hooks, $log_level, $soapserver, QUICKBOOKS_WSDL, $soap_options, $handler_options, $driver_options, $callback_options);
$response = $Server->handle(true, true);

/*
// If you wanted, you could do something with $response here for debugging

$fp = fopen('/path/to/file.log', 'a+');
fwrite($fp, $response);
fclose($fp);
*/

function log_content( $content ) {
	global $sts_qb_log_file;
	
	if( isset($sts_qb_log_file) && $sts_qb_log_file <> '' ) {
		$fp = fopen($sts_qb_log_file,"a");
		fwrite($fp,$content);
		fclose($fp);
	}
}

function hsc( $string, $max_length = 0 ) {
	return htmlspecialchars(
		($max_length > 0 ? substr($string,0,ADDR_FIELD_MAX_LENGTH) : $string),
		ENT_QUOTES|ENT_HTML401);
}

function hsc30( $string ) {
	return htmlspecialchars(substr($string,0,CUSTOM_FIELD_MAX_LENGTH),ENT_QUOTES|ENT_HTML401);
}

// We start by checking if the customer exists in Quickbooks.
// The $ID parameter is the shipment code, and use the BILLTO_NAME field to search for a customer.
function _quickbooks_customer_query_request($requestID , $user, $action, $ID, $extra, &$err,
	$last_action_time, $last_actionident_time, $version, $locale){
	
	global $shipment_table;

	$result = $shipment_table->fetch_rows("SHIPMENT_CODE = ".$ID);
	
	if( is_array($result) && count($result) == 1 && isset($result[0]['BILLTO_NAME']) &&
		isset($result[0]['BILLING_STATUS']) && $shipment_table->billing_state_behavior[$result[0]['BILLING_STATUS']] == 'approved' ) {
		$full_name = $result[0]['BILLTO_NAME'];


        $xml = '<?xml version="1.0" encoding="utf-8"?>
                <?qbxml version="2.0"?>
                <QBXML>
                    <QBXMLMsgsRq onError="stopOnError">
                        <CustomerQueryRq requestID="' . $requestID . '">
                        	<FullName>' . hsc($full_name, FULLNAME_FIELD_MAX_LENGTH) . '</FullName>
                        </CustomerQueryRq>
                    </QBXMLMsgsRq>
                </QBXML>';

		$content = "\n".date("m/d/Y h:i A")." _quickbooks_customer_query_request\n";
		$content .= $xml;
		$content .= "\n##########################################################\n";
		$content .= "requestID = $requestID, user = $user, action = $action, ID = $ID";
		log_content($content);
	} else {
		$result = $shipment_table->update($ID, array("quickbooks_status_message" => 
			"Error - not approved or not found $ID"), false );
		log_content("\n_quickbooks_customer_query_request not approved or not found $ID\n");
	}

    return $xml;
}

// This function is called when the customer already exists in Quickbooks.
// $ID should match the shipment code.
// $ListID in the XML will be the reference to the customer
function _quickbooks_customer_query_response($requestID, $user, $action, $ID, $extra, &$err,
	$last_action_time, $last_actionident_time, $xml, $idents){ 
    
   	global $shipment_table, $sts_qb_dsn;
   
    $content = "\n".date("m/d/Y h:i A")." _quickbooks_customer_query_response\n";
    $content .= $xml;
    $content .= "\n##########################################################\n";
    $content .= "requestID = $requestID, user = $user, action = $action, ID = $ID";
	log_content($content);
	
	if( $parsed = simplexml_load_string( $xml ) ) {
	
		//$content = print_r($parsed, true);
        //log_content($content);
		if ($ListID = $parsed->QBXMLMsgsRs->CustomerQueryRs->CustomerRet->ListID)
		{
			$content = "\nListID is $ListID\n";
			log_content($content);
			try {
				//ob_start();
				//$shipment_table->set_debug( true );
				$content = "Update shipment\n";
				$result = $shipment_table->update($ID, array("quickbooks_listid_customer" => ((string)$ListID), "quickbooks_status_message" => "Found Customer in QB"), false );
				//$shipment_table->set_debug( $sts_debug );
				//$content = ob_get_flush();
				if( $result ) {
		        	$Queue = new QuickBooks_WebConnector_Queue($sts_qb_dsn);
					$Queue->enqueue(QUICKBOOKS_ADD_INVOICE, $ID);
				} else
					$content .= "shipment_table->update($ID, $ListID) returned false\n";
			} catch (Exception $e) {
				$result = $shipment_table->update($ID, array("quickbooks_status_message" => 
					"Error - CQ exception ".$e->getMessage() ), false );
			    $content = 'Caught exception: '.  $e->getMessage(). "\n";
			}
			log_content($content);
		}
	}
}

/**
 * Generate a qbXML response to add a particular customer to QuickBooks
 * 
 * So, you've queued up a QUICKBOOKS_ADD_CUSTOMER request with the 
 * QuickBooks_Queue class like this: 
 * 	$Queue = new QuickBooks_Queue('mysql://user:pass@host/database');
 * 	$Queue->enqueue(QUICKBOOKS_ADD_CUSTOMER, $primary_key_of_your_customer);
 * 
 * And you're registered a request and a response function with your $map 
 * parameter like this:
 * 	$map = array( 
 * 		QUICKBOOKS_ADD_CUSTOMER => array( '_quickbooks_customer_add_request', '_quickbooks_customer_add_response' ),
 * 	 );
 * 
 * This means that every time QuickBooks tries to process a 
 * QUICKBOOKS_ADD_CUSTOMER action, it will call the 
 * '_quickbooks_customer_add_request' function, expecting that function to 
 * generate a valid qbXML request which can be processed. So, this function 
 * will generate a qbXML CustomerAddRq which tells QuickBooks to add a 
 * customer. 
 * 
 * Our response function will in turn receive a qbXML response from QuickBooks 
 * which contains all of the data stored for that customer within QuickBooks. 
 * 
 * @param string $requestID					You should include this in your qbXML request (it helps with debugging later)
 * @param string $action					The QuickBooks action being performed (CustomerAdd in this case)
 * @param mixed $ID							The unique identifier for the record (maybe a customer ID number in your database or something)
 * @param array $extra						Any extra data you included with the queued item when you queued it up
 * @param string $err						An error message, assign a value to $err if you want to report an error
 * @param integer $last_action_time			A unix timestamp (seconds) indicating when the last action of this type was dequeued (i.e.: for CustomerAdd, the last time a customer was added, for CustomerQuery, the last time a CustomerQuery ran, etc.)
 * @param integer $last_actionident_time	A unix timestamp (seconds) indicating when the combination of this action and ident was dequeued (i.e.: when the last time a CustomerQuery with ident of get-new-customers was dequeued)
 * @param float $version					The max qbXML version your QuickBooks version supports
 * @param string $locale					
 * @return string							A valid qbXML request
 */
function _quickbooks_customer_add_request($requestID, $user, $action, $ID, $extra, &$err, $last_action_time, $last_actionident_time, $version, $locale)
{
	global $shipment_table;
	
	$xml = '';
    $content = "\n".date("m/d/Y h:i A")." _quickbooks_customer_add_request\n";
    $content .= "requestID = $requestID, user = $user, action = $action, ID = $ID";
	log_content($content);

	$result = $shipment_table->fetch_rows("SHIPMENT_CODE = ".$ID);
	if( is_array($result) && count($result) == 1 && isset($result[0]['BILLTO_NAME']) ) {
		$customer = $result[0];
		log_content("\nGoing to add ".$result[0]['BILLTO_NAME']."\n");

	 
		$xml = '<?xml version="1.0" encoding="utf-8"?>
			<?qbxml version="2.0"?>
			<QBXML>
				<QBXMLMsgsRq onError="stopOnError">
					<CustomerAddRq requestID="' . $requestID . '">
						<CustomerAdd>
							<Name>'.hsc($customer['BILLTO_NAME'], NAME_FIELD_MAX_LENGTH).'</Name>
							<CompanyName>'.hsc($customer['BILLTO_NAME'], NAME_FIELD_MAX_LENGTH).'</CompanyName>
							<BillAddress>
								<Addr1>'.hsc(isset($customer['BILLTO_NAME']) ? $customer['BILLTO_NAME'] : '').'</Addr1>
								<Addr2>'.hsc(isset($customer['BILLTO_ADDR1']) ? $customer['BILLTO_ADDR1'] : '', ADDR_FIELD_MAX_LENGTH).'</Addr2>
								<Addr3>'.hsc(isset($customer['BILLTO_ADDR2']) ? $customer['BILLTO_ADDR2'] : '', ADDR_FIELD_MAX_LENGTH).'</Addr3>
								<City>'.hsc(isset($customer['BILLTO_CITY']) ? $customer['BILLTO_CITY'] : '', CITY_FIELD_MAX_LENGTH).'</City>
								<State>'.hsc(isset($customer['BILLTO_STATE']) ? $customer['BILLTO_STATE'] : '', STATE_FIELD_MAX_LENGTH).'</State>
								<PostalCode>'.hsc(isset($customer['BILLTO_ZIP']) ? $customer['BILLTO_ZIP'] : '', POSTAL_FIELD_MAX_LENGTH).'</PostalCode>
								<Country>USA</Country>
							</BillAddress>
							<Phone>'.hsc( ((! empty($customer['BILLTO_PHONE']) ? $customer['BILLTO_PHONE'] : '').
							(! empty($customer['BILLTO_EXT']) ? ' x '.$customer['BILLTO_EXT'] : '')), PHONE_FIELD_MAX_LENGTH).
							'</Phone>
							<Fax>'.hsc(isset($customer['BILLTO_FAX']) ? $customer['BILLTO_FAX'] : '', PHONE_FIELD_MAX_LENGTH).'</Fax>
							<Email>'.hsc(isset($customer['BILLTO_EMAIL']) ? $customer['BILLTO_EMAIL'] : '', EMAIL_FIELD_MAX_LENGTH).'</Email>
							<Contact>'.hsc(isset($customer['BILLTO_CONTACT']) ? $customer['BILLTO_CONTACT'] : '', NAME_FIELD_MAX_LENGTH).'</Contact>
						</CustomerAdd>
					</CustomerAddRq>
				</QBXMLMsgsRq>
			</QBXML>';
    $content = "\nXML##########################################################\n";
    $content .= $xml;
    $content .= "\n##########################################################\n";
	log_content($content);
	} else {
		$result = $shipment_table->update($ID, array("quickbooks_status_message" => 
			"Error - CA customer info not found." ), false );
		log_content("\nContact info not found\n");
	}
	
	return $xml;
}

/**
 * Receive a response from QuickBooks 
 * 
 * @param string $requestID					The requestID you passed to QuickBooks previously
 * @param string $action					The action that was performed (CustomerAdd in this case)
 * @param mixed $ID							The unique identifier of the record
 * @param array $extra			
 * @param string $err						An error message, assign a valid to $err if you want to report an error
 * @param integer $last_action_time			A unix timestamp (seconds) indicating when the last action of this type was dequeued (i.e.: for CustomerAdd, the last time a customer was added, for CustomerQuery, the last time a CustomerQuery ran, etc.)
 * @param integer $last_actionident_time	A unix timestamp (seconds) indicating when the combination of this action and ident was dequeued (i.e.: when the last time a CustomerQuery with ident of get-new-customers was dequeued)
 * @param string $xml						The complete qbXML response
 * @param array $idents						An array of identifiers that are contained in the qbXML response
 * @return void
 */
function _quickbooks_customer_add_response($requestID, $user, $action, $ID, $extra, &$err, $last_action_time, $last_actionident_time, $xml, $idents)
{	
   	global $shipment_table, $sts_qb_dsn;

    $content = "\n".date("m/d/Y h:i A")." _quickbooks_customer_add_response\n";
    $content .= "requestID = $requestID, user = $user, action = $action, ID = $ID, errnum = $errnum";
    $content .= "\n##########################################################\n";
    $content .= $xml;
    $content .= "\n##########################################################\n";
	log_content($content);

	if( $parsed = simplexml_load_string( $xml ) ) {
	
		//$content = print_r($parsed, true);
        //log_content($content);
		if ($ListID = $parsed->QBXMLMsgsRs->CustomerAddRs->CustomerRet->ListID)
		{
			$content = "\nListID is $ListID\n";
			log_content($content);
			try {
				//ob_start();
				//$shipment_table->set_debug( true );
				$content = "Update shipment\n";
				$result = $shipment_table->update($ID, array("quickbooks_listid_customer" => ((string)$ListID), "quickbooks_status_message" => "Customer Added"), false );
				//$shipment_table->set_debug( $sts_debug );
				//$content = ob_get_flush();
				if( $result ) {
		        	$Queue = new QuickBooks_WebConnector_Queue($sts_qb_dsn);
					$Queue->enqueue(QUICKBOOKS_ADD_INVOICE, $ID);
				} else
					$content .= "shipment_table->update($ID, $ListID) returned false\n";
			} catch (Exception $e) {
				$result = $shipment_table->update($ID, array("quickbooks_status_message" => 
					"Error - CA exception ".$e->getMessage() ), false );
			    $content = 'Caught exception: '.  $e->getMessage(). "\n";
			}
			log_content($content);
		}
	}

}

        function _quickbooks_error_handler($requestID, $user, $action, $ID, $extra, &$err, $xml, $errnum, $errmsg){
        	global $sts_qb_dsn;
        	
            $content = "\n".date("m/d/Y h:i A")." _quickbooks_error_handler\n";
            $content .= "requestID = $requestID, user = $user, action = $action, ID = $ID, errnum = $errnum";
            $content .= "\n##########################################################\n";
            $content .= $xml;
            $content .= "\n##########################################################\n";
            $content .= $errmsg."\nSo we need to create a customer/vendor for ID $ID\n";
			log_content($content);
			
			if( $action == 'CustomerQuery' ) {
	        	$Queue = new QuickBooks_WebConnector_Queue($sts_qb_dsn);
				$Queue->enqueue(QUICKBOOKS_ADD_CUSTOMER, $ID);
			} else if( $action = 'VendorQuery' ) {
	        	$Queue = new QuickBooks_WebConnector_Queue($sts_qb_dsn);
				$Queue->enqueue(QUICKBOOKS_ADD_VENDOR, $ID, 0, $extra);
			}


            return true;
        }

	function _quickbooks_error_catchall($requestID, $user, $action, $ID, $extra, &$err, $xml, $errnum, $errmsg)
	{
   		global $shipment_table, $load_table, $sts_qb_dsn, $sts_qb_log_file;

        $content = "\n".date("m/d/Y h:i A")." _quickbooks_error_catchall\n";
        $content .= "requestID = $requestID, user = $user, action = $action, ID = $ID, errnum = $errnum";
        $content .= "\n##########################################################\n";
        $content .= $xml;
        $content .= "\n##########################################################\n";
        $content .= $errmsg;

		//if( substr_compare($errnum, '0x800404',0) )
		//	$content .= "\nCould not start QuickBooks.\n";

		if( $errnum == '0x80040408' )
			$content .= "\n0x80040408 Could not start QuickBooks.\n";

		if( $errnum == '0x80040414' )
			$content .= "\n0x80040414 A modal dialog box is showing in the QuickBooks user interface. Your application cannot access QuickBooks until the user dismisses the dialog box.\n";

		if( $errnum == '0x80040422' )
			$content .= "\n0x80040422 This application requires Single User file access mode and there is already another application sharing data with this QuickBooks company data file.\n";

		if( $errnum == '0x80040423' )
			$content .= "\n0x80040423 The version of qbXML that was requested is not supported or is unknown.\n";

		if( $errnum == '0x80040424' )
			$content .= "\n0x80040424 QuickBooks did not finish its initialization. Please try again later.\n";

		if( $errnum == '0x80040427' )
			$content .= "\n0x80040427 Unregistered QuickBooks.\n";

		if( $errnum == '0x8004042A' )
			$content .= "\n0x8004042A Remote access is not allowed.\n";

		if( $errnum == '0x8004042C' )
			$content .= "\n0x8004042C Certificate has been revoked. Something is wrong with the SSL certificate issued to your domain name.\n";

		if( $errnum == '0x8004040A' )
			$content .= "\n0x8004040A QuickBooks company data file is already open and it is different from the one requested.\n";

		if( $errnum == '0x80040410' )
			$content .= "\n0x80040410 The QuickBooks company data file is currently open in a mode other than the one specified by your application.\n";

		if( $errnum == '0x80040418' )
			$content .= "\n0x80040418 This application has not accessed this QuickBooks company data file before. Only the QuickBooks administrator can grant an application permission to access a QuickBooks company data file for the first time.\n";

		if( $errnum == '0x8004041A' )
			$content .= "\n0x8004041A This application does not have permission to access this QuickBooks company data file. The QuickBooks administrator can grant access permission through the Integrated Application preferences.\n";

		if( $errnum == '0x8004041B' )
			$content .= "\n0x8004041B Unable to lock the necessary information to allow this application to access this company data file. Try again later.\n";

		if( $errnum == '0x8004041C' )
			$content .= "\n0x8004041C An internal QuickBooks error occurred while trying to access the QuickBooks company data file.\n";

		if( $errnum == '0x8004041D' )
			$content .= "\n0x8004041D This application is not allowed to log into this QuickBooks company data file automatically. The QuickBooks administrator can grant permission for automatic login through the Integrated Application preferences.\n";

		if( $errnum == '0x8004041E' )
			$content .= "\n0x8004041E This applications certificate is expired. If you want to allow the application to log into QuickBooks automatically, log into QuickBooks and try again. Then click Allow Always when you are notified that the certificate has expired.\n";

		if( $errnum == '0x8004041F' )
			$content .= "\n0x8004041F QuickBooks Basic cannot accept XML requests. Another product in the QuickBooks line, such as QuickBooks Pro or Premier, 2002 or later, is required.\n";

		if( $errnum == '0x80040420' )
			$content .= "\n0x8004041E The QuickBooks user has denied access.\n";

		log_content($content);
		
		if( in_array($action, array('InvoiceAdd', 'DataExtMod')) ) {
			$result = $shipment_table->update($ID, array("quickbooks_status_message" =>
				$shipment_table->trim_to_fit( "quickbooks_status_message", $errmsg ) ), false );
		}
		if( $action == 'BillAdd' ) {
			$result = $load_table->update($ID, array("quickbooks_status_message" =>
				$load_table->trim_to_fit( "quickbooks_status_message", $errmsg ) ) );
		}
			
		if( $action == 'DataExtMod' ) {
			//! Code for retrying sending custom fields.
			$result = $shipment_table->fetch_rows("SHIPMENT_CODE = ".$ID, "quickbooks_dataext_retries");	
			if( is_array($result) && count($result) == 1 ) {
				log_content("quickbooks_dataext_retries = ".(isset($result['quickbooks_dataext_retries']) ? $result['quickbooks_dataext_retries'] : 'NULL').'\n');
				$retries = isset($result['quickbooks_dataext_retries']) ? $result['quickbooks_dataext_retries'] : 0;
				if( $retries < $sts_qb_max_retries ) {
					$retries++;
					$result = $shipment_table->update($ID, array("quickbooks_dataext_retries" => ((string)$retries) ), false );
					sleep(1);
			        // Retry custom code
		        	$Queue = new QuickBooks_WebConnector_Queue($sts_qb_dsn);
					$Queue->enqueue(QUICKBOOKS_MOD_DATAEXT, $ID);
				}
			}
		}
			
		$email = sts_email::getInstance($exspeedite_db, $sts_debug);
		$message = "<pre>".$content."</pre>";
		if( strpos($errmsg, 'found an error when parsing the provided XML') !== false &&
			isset($sts_qb_log_file) && $sts_qb_log_file <> '' ) {
			$message .= "<br>Last 200 lines from the log:</br>
			<pre>".htmlentities($email->tailCustom($sts_qb_log_file, 200))."</pre>";
		}
		$email->send_alert($message, EXT_ERROR_ERROR);
		
		if( in_array($errnum, array('0x800404', '0x80040408', '0x80040414',
			'0x80040422', '0x80040427', '0x8004040A', '0x80040410', '0x80040420' ))  )
		$email->send_qb_alert($message);
		

		return true;
	}

// We add an invoice to Quickbooks, based on a shipment.
// The $ID parameter is the shipment code.
function _quickbooks_invoice_add_request($requestID , $user, $action, $ID, $extra, &$err,
	$last_action_time, $last_actionident_time, $version, $locale){
	
	global $shipment_table, $billing_table, $rates_table, $detail_table, $sts_qb_template, $sts_qb_invoice_terms,
		$sts_qb_class, $sts_qb_detail, $sts_qb_trans_date;

	log_content("\n".date("m/d/Y h:i A")." InvoiceAddRq\n");
	$result = $shipment_table->fetch_rows("SHIPMENT_CODE = ".$ID, "*,
		(SELECT DATE(ACTUAL_ARRIVE) FROM EXP_STOP
		WHERE SHIPMENT = SHIPMENT_CODE
		AND STOP_TYPE = 'drop') AS ACTUAL_DELIVERY,
		(SELECT DATE(ACTUAL_DEPART) FROM EXP_STOP
		WHERE SHIPMENT = SHIPMENT_CODE
		AND STOP_TYPE = 'pick') AS ACTUAL_DEPART,
		
		(SELECT MIN(DATE(CREATED_DATE)) FROM EXP_STATUS
		WHERE ORDER_CODE = SHIPMENT_CODE AND SOURCE_TYPE = 'shipment'
		AND STATUS_STATE = (SELECT STATUS_CODES_CODE FROM EXP_STATUS_CODES
			WHERE SOURCE_TYPE = 'shipment' AND behavior = 'picked') ) AS PICKED_DATE,

		(SELECT MIN(DATE(CREATED_DATE)) FROM EXP_STATUS
		WHERE ORDER_CODE = SHIPMENT_CODE AND SOURCE_TYPE = 'shipment'
		AND STATUS_STATE = (SELECT STATUS_CODES_CODE FROM EXP_STATUS_CODES
			WHERE SOURCE_TYPE = 'shipment' AND behavior = 'dropped') ) AS DROPPED_DATE");
			
	$details = $shipment_table->database->get_multiple_rows(
		"select DETAIL_CODE, SHIPMENT_CODE, (SELECT COMMODITY_NAME from EXP_COMMODITY X WHERE X.COMMODITY_CODE = EXP_DETAIL.COMMODITY  LIMIT 0 , 1) AS COMMODITY_NAME, (SELECT COMMODITY_DESCRIPTION from EXP_COMMODITY X WHERE X.COMMODITY_CODE = EXP_DETAIL.COMMODITY  LIMIT 0 , 1) AS COMMODITY_DESCRIPTION, PALLETS, PIECES, (SELECT UNIT_NAME from EXP_UNIT X WHERE X.UNIT_CODE = EXP_DETAIL.PIECES_UNITS AND X.UNIT_TYPE = 'item' LIMIT 0 , 1) AS PIECES_UNITS, WEIGHT, AMOUNT, SYNERGY_IMPORT, DANGEROUS_GOODS, TEMP_CONTROLLED, BILLABLE
		from EXP_DETAIL 
		where SHIPMENT_CODE = ".$ID);
	//log_content(print_r($details, true));
	
	if( is_array($result) && count($result) == 1 && isset($result[0]['quickbooks_listid_customer']) ) {
		$full_name = $result[0]['BILLTO_NAME'];
		$listid_customer = $result[0]['quickbooks_listid_customer'];
		$actual_shipment = $result[0]['ACTUAL_DEPART'];
		$actual_delivery = $result[0]['ACTUAL_DELIVERY'];
		$picked_date = $result[0]['PICKED_DATE'];
		$dropped_date = $result[0]['DROPPED_DATE'];
		
		log_content("\nInvoiceAddRq - got shipment data\n");
		$result2 = $billing_table->fetch_rows("SHIPMENT_ID = ".$ID);
		if( is_array($result2) && count($result2) == 1 && isset($result2[0]['TOTAL']) ) {
			$billing_pallets		= intval($result2[0]['PALLETS']);
			$billing_per_pallets	= floatval($result2[0]['PER_PALLETS']);
			$billing_amount_pallets	= floatval($result2[0]['PALLETS_RATE']);
			$billing_hand_pallet	= floatval($result2[0]['HAND_PALLET']);
			$billing_handling		= floatval($result2[0]['HAND_CHARGES']);
			$billing_freight		= floatval($result2[0]['FREIGHT_CHARGES']);
			$billing_extra			= floatval($result2[0]['EXTRA_CHARGES']);
			
			$billing_loading_free_hrs	= floatval($result2[0]['FREE_DETENTION_HOUR']);
			$billing_loading_hrs		= floatval($result2[0]['DETENTION_HOUR']);
			$billing_loading_rate		= floatval($result2[0]['RATE_PER_HOUR']);
			$billing_loading			= floatval($result2[0]['DETENTION_RATE']);
			
			$billing_unloading_free_hrs	= floatval($result2[0]['FREE_UN_DETENTION_HOUR']);
			$billing_unloading_hrs		= floatval($result2[0]['UNLOADED_DETENTION_HOUR']);
			$billing_unloading_rate		= floatval($result2[0]['UN_RATE_PER_HOUR']);
			$billing_unloading			= floatval($result2[0]['UNLOADED_DETENTION_RATE']);
			
			$billing_cod			= floatval($result2[0]['COD']);
			$billing_mileage		= floatval($result2[0]['MILLEAGE']);
			$billing_fsc_rate		= floatval($result2[0]['FSC_AVERAGE_RATE']);
			$billing_mileage_rate	= floatval($result2[0]['RPM']);
			$billing_surcharge		= floatval($result2[0]['FUEL_COST']);

			$billing_stopoff		= floatval($result2[0]['STOP_OFF']);
			$billing_stopoff_note	= $result2[0]['STOP_OFF_NOTE'];
			$billing_weekend		= floatval($result2[0]['WEEKEND']);
			
			$billing_adjustment_title	= $result2[0]['ADJUSTMENT_CHARGE_TITLE'];
			$billing_adjustment_charge	= floatval($result2[0]['ADJUSTMENT_CHARGE']);

			$selection_fee	= floatval($result2[0]['SELECTION_FEE']);
			$discount		= floatval($result2[0]['DISCOUNT']);
			
			$billing_id				= $result2[0]['CLIENT_BILLING_ID'];
			
			$has_value = false;

			log_content("\nInvoiceAddRq - got billing data billing_mileage = $billing_mileage, billing_mileage_rate = $billing_mileage_rate\n");
	        $xml = '<?xml version="1.0" encoding="utf-8"?>
	                <?qbxml version="13.0"?>
	                <QBXML>
	                    <QBXMLMsgsRq onError="stopOnError">
							<InvoiceAddRq>
								<InvoiceAdd defMacro="TxnID:Invoice'.$ID.'">
									<CustomerRef>
										<ListID>'.$listid_customer.'</ListID>
									</CustomerRef>
';
			//! Class
			if( isset($sts_qb_class) && $sts_qb_class <> '') {
				$xml .= '									<ClassRef>
										<FullName>'.hsc($sts_qb_class, CLASS_FIELD_MAX_LENGTH).'</FullName>
									</ClassRef>
';
			}

			//! Template Information - don't know why this does not work
			if( false && isset($sts_qb_template) && $sts_qb_template <> '') {
				$xml .= '									<TemplateRef>
										<FullName>'.hsc($sts_qb_template, FULLNAME_FIELD_MAX_LENGTH).'</FullName>
									</TemplateRef>
';
			}

			//! TxnDate
			if( isset($sts_qb_trans_date) && $sts_qb_trans_date == 'delivered' )
				$xml .= '									<TxnDate>'.(isset($actual_delivery) && $actual_delivery <> '' ? $actual_delivery : $dropped_date).'</TxnDate>
';
			else
				$xml .= '									<TxnDate>'.(isset($actual_shipment) && $actual_shipment ? $actual_shipment : $picked_date).'</TxnDate>
';

			$xml .= '									<RefNumber>'.$ID.'</RefNumber>
';

			//! Billing Information
			if( isset($result[0]['BILLTO_NAME']) ) {
					$xml .= '									<BillAddress>
										<Addr1>' . hsc($result[0]['BILLTO_NAME'], ADDR_FIELD_MAX_LENGTH) . '</Addr1>
										<Addr2>' . hsc($result[0]['BILLTO_ADDR1'], ADDR_FIELD_MAX_LENGTH) . '</Addr2>
';
				if( isset($result[0]['BILLTO_ADDR2']) )
					$xml .= '										<Addr3>' . hsc($result[0]['BILLTO_ADDR2'], ADDR_FIELD_MAX_LENGTH) . '</Addr3>
';
					
					$xml .= '										<City>' . hsc($result[0]['BILLTO_CITY'], CITY_FIELD_MAX_LENGTH) . '</City>
										<State>' . hsc($result[0]['BILLTO_STATE'], STATE_FIELD_MAX_LENGTH) . '</State>
										<PostalCode>' . hsc($result[0]['BILLTO_ZIP'], POSTAL_FIELD_MAX_LENGTH) . '</PostalCode>
										<Country>USA</Country>
									</BillAddress>
';
			}
			
			//! Shipping Information
			if( isset($result[0]['CONS_NAME']) ) {
					$xml .= '									<ShipAddress>
										<Addr1>' . hsc($result[0]['CONS_NAME'], ADDR_FIELD_MAX_LENGTH) . '</Addr1>
										<Addr2>' . hsc($result[0]['CONS_ADDR1'], ADDR_FIELD_MAX_LENGTH) . '</Addr2>
';
				if( isset($result[0]['CONS_ADDR2']) )
					$xml .= '										<Addr3>' . hsc($result[0]['CONS_ADDR2'], ADDR_FIELD_MAX_LENGTH) . '</Addr3>
';
					
					$xml .= '										<City>' . hsc($result[0]['CONS_CITY'], CITY_FIELD_MAX_LENGTH) . '</City>
										<State>' . hsc($result[0]['CONS_STATE'], STATE_FIELD_MAX_LENGTH) . '</State>
										<PostalCode>' . hsc($result[0]['CONS_ZIP'], POSTAL_FIELD_MAX_LENGTH) . '</PostalCode>
										<Country>USA</Country>
									</ShipAddress>
';
			}
			
			//! PO Number
			if( isset($result[0]['PO_NUMBER']) && $result[0]['PO_NUMBER'] <> '') {
				$xml .= '<PONumber>'.hsc($result[0]['PO_NUMBER'], PONUMBER_FIELD_MAX_LENGTH).'</PONumber>
';
			}

		$xml .= '								<TermsRef>
									<FullName>'.hsc($sts_qb_invoice_terms, FULLNAME_FIELD_MAX_LENGTH).'</FullName>
								</TermsRef>
';
			//! ShipDate
			$xml .= '									<ShipDate>'.(isset($actual_shipment) && $actual_shipment ? $actual_shipment : $picked_date).'</ShipDate>
';

			$xml .= '									<Memo>From Exspeedite Shipment #'.$ID.'</Memo>
';

			//! DueDate
			$xml .= '									<Other>'.date("m/d/Y", strtotime(isset($actual_delivery) && $actual_delivery <> '' ? $actual_delivery : $dropped_date)).'</Other>
';


			//! Commodities
			if( isset($sts_qb_detail) && $sts_qb_detail == 'true' && 
				is_array($details) && count($details) > 0 ) {
				log_content("\nInvoiceAddRq - got ".count($details)." details\n");
					$xml .= '									<InvoiceLineAdd>
										<Desc>COMMODITIES</Desc>
									</InvoiceLineAdd>
									<InvoiceLineAdd>
										<Desc>-----------</Desc>
									</InvoiceLineAdd>
';

				foreach( $details as $detail ) {
					$desc = $detail['COMMODITY_DESCRIPTION'].' ('.$detail['COMMODITY_NAME'].') ';
					if( isset($detail['PALLETS']) && $detail['PALLETS'] > 0 )
						$desc .= $detail['PALLETS'].' pallets ';
					if( isset($detail['PIECES']) && $detail['PIECES'] > 0 )
						$desc .= $detail['PIECES'].' items ';
					
					$xml .= '									<InvoiceLineAdd>
										<Desc>'.hsc($desc).'</Desc>
';
					if( isset($result[0]['DISTANCE']) && $result[0]['DISTANCE'] > 0 )
						$xml .= '										<Other1>'.$result[0]['DISTANCE'].' miles</Other1>
';
					if( isset($detail['WEIGHT']) && $detail['WEIGHT'] > 0 )
						$xml .= '										<Other2>'.$detail['WEIGHT'].' LBs </Other2>
';
$xml .= '									</InvoiceLineAdd>
';
				}
			}



			if( $billing_pallets > 0 && $billing_per_pallets > 0 ) {	//! Pallets
				$has_value = true;
				if( $billing_amount_pallets <> $billing_pallets * $billing_per_pallets )
					$xml .= '									<InvoiceLineAdd>
										<ItemRef>
											<FullName>'.EXP_PALLETS_ITEM.'</FullName>
										</ItemRef>
										<Desc>'.$billing_pallets.' Pallets</Desc>
										<Quantity>1</Quantity>
										<Amount>'.number_format((float) $billing_amount_pallets, 2,".","").'</Amount>
									</InvoiceLineAdd>
';
				else
					$xml .= '									<InvoiceLineAdd>
										<ItemRef>
											<FullName>'.EXP_PALLETS_ITEM.'</FullName>
										</ItemRef>
										<Desc>Pallets</Desc>
										<Quantity>'.$billing_pallets.'</Quantity>
										<Rate>'.number_format((float) $billing_per_pallets, 2,".","").'</Rate>
									</InvoiceLineAdd>
';
			} else if( $billing_amount_pallets > 0 ) {
				$has_value = true;
				$xml .= '									<InvoiceLineAdd>
										<ItemRef>
											<FullName>'.EXP_PALLETS_ITEM.'</FullName>
										</ItemRef>
										<Desc>Pallets</Desc>
										<Quantity>'.($billing_pallets > 0  ? $billing_pallets : '1').'</Quantity>
										<Amount>'.number_format((float) $billing_amount_pallets, 2,".","").'</Amount>
									</InvoiceLineAdd>
';
			}
			
			if( $billing_hand_pallet > 0 ) {	//! Pallet Handling charges
				$has_value = true;
				$xml .= '									<InvoiceLineAdd>
										<ItemRef>
											<FullName>'.EXP_HANDLING_PALLET_ITEM.'</FullName>
										</ItemRef>
										<Desc>Pallet Handling charges</Desc>
										<Quantity>1</Quantity>
										<Amount>'.number_format((float) $billing_hand_pallet, 2,".","").'</Amount>
									</InvoiceLineAdd>
';
			}

			if( $billing_freight > 0 ) {	//! Freight charges
				$has_value = true;
				$xml .= '									<InvoiceLineAdd>
										<ItemRef>
											<FullName>'.EXP_FREIGHT_ITEM.'</FullName>
										</ItemRef>
										<Desc>Freight charges</Desc>
										<Quantity>1</Quantity>
										<Amount>'.number_format((float) $billing_freight, 2,".","").'</Amount>
									</InvoiceLineAdd>
';
			}
			
			if( $billing_handling > 0 ) {	//! Handling charges
				$has_value = true;
				$xml .= '									<InvoiceLineAdd>
										<ItemRef>
											<FullName>'.EXP_HANDLING_ITEM.'</FullName>
										</ItemRef>
										<Desc>Handling charges</Desc>
										<Quantity>1</Quantity>
										<Amount>'.number_format((float) $billing_handling, 2,".","").'</Amount>
									</InvoiceLineAdd>
';
			}

			if( $billing_adjustment_charge <> 0 ) {	//! Billing adjustment
				$has_value = true;
				$xml .= '									<InvoiceLineAdd>
										<ItemRef>
											<FullName>'.EXP_FREIGHT_ITEM.'</FullName>
										</ItemRef>
										<Desc>'.(isset($billing_adjustment_title) && hsc($billing_adjustment_title) <> '' ? $billing_adjustment_title : 'Adjustment').'</Desc>
										<Quantity>1</Quantity>
										<Amount>'.number_format((float) $billing_adjustment_charge, 2,".","").'</Amount>
									</InvoiceLineAdd>
';
			}
			
			if( $billing_extra > 0 ) {	//! Extra charges
				$has_value = true;
				$xml .= '									<InvoiceLineAdd>
										<ItemRef>
											<FullName>'.EXP_EXTRA_ITEM.'</FullName>
										</ItemRef>
										<Desc>Extra charges</Desc>
										<Quantity>1</Quantity>
										<Amount>'.number_format((float) $billing_extra, 2,".","").'</Amount>
									</InvoiceLineAdd>
';
			}
			
			//! Loading / Unloading detention
			// Assume IF $billing_loading > 0 THEN $billing_loading_hrs > $billing_loading_free_hrs
			if( $billing_loading > 0 ) {
				$has_value = true;
				$billable_hrs = $billing_loading_hrs - $billing_loading_free_hrs;
				if( $billable_hrs < 1 ) $billable_hrs = 1;
				$xml .= '									<InvoiceLineAdd>
										<ItemRef>
											<FullName>'.EXP_LOADING_ITEM.'</FullName>
										</ItemRef>
										<Desc>Loading Detention</Desc>
										<Quantity>'.$billable_hrs.'</Quantity>
										<Amount>'.number_format((float) $billing_loading, 2,".","").'</Amount>
									</InvoiceLineAdd>
';
			}
			
			// Assume IF $billing_unloading > 0 THEN $billing_unloading_hrs > $billing_unloading_free_hrs
			if( $billing_unloading > 0 ) {
				$has_value = true;
				$billable_hrs = $billing_unloading_hrs - $billing_unloading_free_hrs;
				if( $billable_hrs < 1 ) $billable_hrs = 1;
				$xml .= '									<InvoiceLineAdd>
										<ItemRef>
											<FullName>'.EXP_UNLOADING_ITEM.'</FullName>
										</ItemRef>
										<Desc>Unloading Detention</Desc>
										<Quantity>'.$billable_hrs.'</Quantity>
										<Amount>'.number_format((float) $billing_unloading, 2,".","").'</Amount>
									</InvoiceLineAdd>
';
			}
			
			if( $billing_cod > 0 ) {	//! COD
				$has_value = true;
				$xml .= '									<InvoiceLineAdd>
										<ItemRef>
											<FullName>'.EXP_COD_ITEM.'</FullName>
										</ItemRef>
										<Desc>COD charges</Desc>
										<Quantity>1</Quantity>
										<Amount>'.number_format((float) $billing_cod, 2,".","").'</Amount>
									</InvoiceLineAdd>
';
			}
			
			if( $billing_mileage > 0 && $billing_mileage_rate > 0 )	{	//! Mileage
				$has_value = true;
				$xml .= '									<InvoiceLineAdd>
										<ItemRef>
											<FullName>'.EXP_MILEAGE_ITEM.'</FullName>
										</ItemRef>
										<Desc>Mileage</Desc>
										<Quantity>'.$billing_mileage.'</Quantity>
										<Rate>'.number_format((float) $billing_mileage_rate, 2,".","").'</Rate>
										<Other1>'.$billing_mileage.'</Other1>
									</InvoiceLineAdd>
';
			}
			
			if( $billing_surcharge > 0 ) {	//! Fuel surcharge $billing_mileage * $billing_fsc_rate = $billing_surcharge
				$has_value = true;
				if( $billing_mileage > 0 && $billing_fsc_rate > 0 ) {
					$xml .= '									<InvoiceLineAdd>
										<ItemRef>
											<FullName>'.EXP_SURCHARGE_ITEM.'</FullName>
										</ItemRef>
										<Desc>Fuel Surcharge</Desc>
										<Quantity>'.$billing_mileage.'</Quantity>
										<Rate>'.number_format((float) $billing_fsc_rate, 2,".","").'</Rate>'.($billing_mileage > 0 ? '
										<Other1>'.$billing_mileage.'</Other1>' :'').'
									</InvoiceLineAdd>
';
				} else {
					$xml .= '									<InvoiceLineAdd>
										<ItemRef>
											<FullName>'.EXP_SURCHARGE_ITEM.'</FullName>
										</ItemRef>
										<Desc>Fuel Surcharge</Desc>
										<Quantity>1</Quantity>
										<Amount>'.number_format((float) $billing_surcharge, 2,".","").'</Amount>'.($billing_mileage > 0 ? '
										<Other1>'.$billing_mileage.'</Other1>' :'').'
									</InvoiceLineAdd>
';
				}
			}
			
			if( $billing_stopoff > 0 ) {	//! Stopoff charges
				$has_value = true;
				$xml .= '									<InvoiceLineAdd>
										<ItemRef>
											<FullName>'.EXP_STOPOFF_ITEM.'</FullName>
										</ItemRef>
										<Desc>'.(isset($billing_stopoff_note) && $billing_stopoff_note <> '' ? hsc($billing_stopoff_note) : 'Stopoff charges').'</Desc>
										<Quantity>1</Quantity>
										<Amount>'.number_format((float) $billing_stopoff, 2,".","").'</Amount>
									</InvoiceLineAdd>
';
			}

			if( $billing_weekend > 0 ) {	//! Weekend
				$has_value = true;
				$xml .= '									<InvoiceLineAdd>
										<ItemRef>
											<FullName>'.EXP_EXTRA_ITEM.'</FullName>
										</ItemRef>
										<Desc>Weekend/Holiday</Desc>
										<Quantity>1</Quantity>
										<Amount>'.number_format((float) $billing_weekend, 2,".","").'</Amount>
									</InvoiceLineAdd>
';
			}

			$result3 = $rates_table->fetch_rows("BILLING_ID = ".$billing_id);
			if( is_array($result3) && count($result3) > 0 ) {
				log_content("\nInvoiceAddRq - got ".count($result3)." rates\n");
				foreach( $result3 as $rate ) {
					if( $rate['RATES'] > 0 ) {
						$has_value = true;
						$xml .= '									<InvoiceLineAdd>
										<ItemRef>
											<FullName>'.EXP_OTHER_ITEM.'</FullName>
										</ItemRef>
										<Desc>'.hsc($rate['RATE_CODE'].' - '.$rate['RATE_NAME'].' - '.$rate['CATEGORY']).'</Desc>
										<Quantity>1</Quantity>
										<Amount>'.number_format((float) $rate['RATES'], 2,".","").'</Amount>
									</InvoiceLineAdd>
';
					}
				}
			}

			if( $selection_fee > 0 ) {	//! Selection fee
				$has_value = true;
				$xml .= '									<InvoiceLineAdd>
										<ItemRef>
											<FullName>'.EXP_SELECTION_FEE_ITEM.'</FullName>
										</ItemRef>
										<Desc>Selection fee</Desc>
										<Quantity>1</Quantity>
										<Amount>'.number_format((float) $selection_fee, 2,".","").'</Amount>
									</InvoiceLineAdd>
';
			}
			
			if( $discount > 0 ) {	//! Discount
				$has_value = true;
				$xml .= '									<InvoiceLineAdd>
										<ItemRef>
											<FullName>'.EXP_DISCOUNT_ITEM.'</FullName>
										</ItemRef>
										<Desc>Discount</Desc>
										<Quantity>1</Quantity>
										<Amount>-'.number_format((float) $discount, 2,".","").'</Amount>
									</InvoiceLineAdd>
';
			}
			
			if( ! $has_value ) {	//! Empty Invoice
				$xml .= '									<InvoiceLineAdd>
										<ItemRef>
											<FullName>'.EXP_EXTRA_ITEM.'</FullName>
										</ItemRef>
										<Desc>Zero Rate</Desc>
										<Quantity>1</Quantity>
										<Amount>0.00</Amount>
									</InvoiceLineAdd>
';
			}

	$xml .= '							</InvoiceAdd>
							</InvoiceAddRq>
';



	$xml .= '						</QBXMLMsgsRq>
	                </QBXML>';
	
			$content = "\nOUTGOING XML##########################################################\n";
			$content .= $xml;
			$content .= "\n##########################################################\n";
			$content .= "requestID = $requestID, user = $user, action = $action, ID = $ID";
			log_content($content);
		} else {
			$result = $shipment_table->update($ID, array("quickbooks_status_message" => 
				"Error - IA can't get billing data" ), false );
			log_content("\nInvoiceAddRq - can't get billing data\n");
		}
	} else {
		log_content("\nInvoiceAddRq - can't get shipment data\n");
		$email = sts_email::getInstance($exspeedite_db, $sts_debug);
		$email->send_alert("InvoiceAddRq - can't get billing data<br>".
			"requestID = $requestID, user = $user, action = $action, ID = $ID", EXT_ERROR_ERROR);
	}

    return $xml;
}

// This function is called when the customer already exists in Quickbooks.
// $ID should match the shipment code.
// $ListID in the XML will be the reference to the customer
function _quickbooks_invoice_add_response($requestID, $user, $action, $ID, $extra, &$err,
	$last_action_time, $last_actionident_time, $xml, $idents){ 
    
   	global $shipment_table, $sts_qb_dsn;
    
    $content = "\n".date("m/d/Y h:i A")." _quickbooks_invoice_add_response\n";
    $content .= $xml;
    $content .= "\n##########################################################\n";
    $content .= "requestID = $requestID, user = $user, action = $action, ID = $ID";
	log_content($content);
	
	if( $parsed = simplexml_load_string( $xml ) ) {
	
		//$content = print_r($parsed, true);
        //log_content($content);
        // quickbooks_listid_invoice
		if ($TxnID = $parsed->QBXMLMsgsRs->InvoiceAddRs->InvoiceRet->TxnID)
		{
			$content = "\nTxnID is $TxnID\n";
			log_content($content);
			try {
				//ob_start();
				//$shipment_table->set_debug( true );
				$content = "Update shipment\n";
				$result = $shipment_table->update($ID, array("quickbooks_txnid_invoice" => ((string)$TxnID), "quickbooks_status_message" => "OK" ), false );
				//$shipment_table->set_debug( $sts_debug );
				//$content = ob_get_flush();
				if( $result ) {
		        	// Update status to billed
		        	$result2 = $shipment_table->change_state_behavior( $ID, 'billed', true );
		        	if( ! $result2 )
		        		$content .= "shipment_table->change_state_behavior( $ID, 'billed' (".$shipment_table->billing_behavior_state['billed']."), true ) returned false\n".$shipment_table->state_change_error;
		        		
		        	// Do custom code separately
		        	$Queue = new QuickBooks_WebConnector_Queue($sts_qb_dsn);
					$Queue->enqueue(QUICKBOOKS_MOD_DATAEXT, $ID);

				} else
					$content .= "shipment_table->update($ID, $TxnID) returned false\n";
			} catch (Exception $e) {
				$result = $shipment_table->update($ID, array("quickbooks_status_message" => 
					"Error - VA exception ".$e->getMessage() ), false );
			    $content = 'Caught exception: '.  $e->getMessage(). "\n";
			}
			log_content($content);
		}
		
	}
}

// Re-try Custom fields
function _quickbooks_dataext_mod_request($requestID , $user, $action, $ID, $extra, &$err,
	$last_action_time, $last_actionident_time, $version, $locale){
	
	global $shipment_table, $billing_table, $rates_table, $detail_table, $sts_qb_template, $sts_qb_invoice_terms;

	log_content("\n".date("m/d/Y h:i A")." _quickbooks_dataext_mod_request\n");
	$result = $shipment_table->fetch_rows("SHIPMENT_CODE = ".$ID);
	
	if( is_array($result) && count($result) == 1 && isset($result[0]['quickbooks_txnid_invoice']) ) {
		$txnid = $result[0]['quickbooks_txnid_invoice'];
		
		log_content("\n_quickbooks_dataext_mod_request - got shipment data\n");

        $xml = '<?xml version="1.0" encoding="utf-8"?>
                <?qbxml version="13.0"?>
                <QBXML>
                    <QBXMLMsgsRq onError="stopOnError">
';
		//! Custom fields
		if( isset($result[0]['SHIPPER_NAME']) )
			$xml .= '							<DataExtModRq>
						  <DataExtMod>
						    <OwnerID>0</OwnerID>
						    <DataExtName>'.EXP_SHIPPER_NAME.'</DataExtName>
						    <TxnDataExtType>Invoice</TxnDataExtType>
						    <TxnID>'.$txnid.'</TxnID>
						    <DataExtValue>'.hsc30($result[0]['SHIPPER_NAME']).'</DataExtValue>
						  </DataExtMod>
						</DataExtModRq>
';

		if( isset($result[0]['SHIPPER_ADDR1']) )
			$xml .= '							<DataExtModRq>
						  <DataExtMod>
						    <OwnerID>0</OwnerID>
						    <DataExtName>'.EXP_SHIPPER_ADDR1.'</DataExtName>
						    <TxnDataExtType>Invoice</TxnDataExtType>
						    <TxnID>'.$txnid.'</TxnID>
						    <DataExtValue>'.hsc30($result[0]['SHIPPER_ADDR1']).'</DataExtValue>
						  </DataExtMod>
						</DataExtModRq>
';

		if( isset($result[0]['SHIPPER_CITY']) && isset($result[0]['SHIPPER_STATE']) && isset($result[0]['SHIPPER_ZIP']) )
			$xml .= '							<DataExtModRq>
						  <DataExtMod>
						    <OwnerID>0</OwnerID>
						    <DataExtName>'.EXP_SHIPPER_CITY.'</DataExtName>
						    <TxnDataExtType>Invoice</TxnDataExtType>
						    <TxnID>'.$txnid.'</TxnID>
						    <DataExtValue>'.hsc30($result[0]['SHIPPER_CITY'].' '.
						    $result[0]['SHIPPER_STATE'].' '.$result[0]['SHIPPER_ZIP']).'
USA</DataExtValue>
						  </DataExtMod>
						</DataExtModRq>
';

		if( isset($result[0]['BOL_NUMBER']) && $result[0]['BOL_NUMBER'] <> '' )
			$xml .= '							<DataExtModRq>
						  <DataExtMod>
						    <OwnerID>0</OwnerID>
						    <DataExtName>'.EXP_BOL_NUMBER.'</DataExtName>
						    <TxnDataExtType>Invoice</TxnDataExtType>
						    <TxnID>'.$txnid.'</TxnID>
						    <DataExtValue>'.hsc30($result[0]['BOL_NUMBER']).'</DataExtValue>
						  </DataExtMod>
						</DataExtModRq>
';

		if( isset($result[0]['LOAD_CODE']) && intval($result[0]['LOAD_CODE']) > 0 )
			$xml .= '							<DataExtModRq>
						  <DataExtMod>
						    <OwnerID>0</OwnerID>
						    <DataExtName>'.EXP_TRIP_NUMBER.'</DataExtName>
						    <TxnDataExtType>Invoice</TxnDataExtType>
						    <TxnID>'.$txnid.'</TxnID>
						    <DataExtValue>'.hsc30($result[0]['LOAD_CODE']).'</DataExtValue>
						  </DataExtMod>
						</DataExtModRq>
';

		if( isset($result[0]['FS_NUMBER']) && $result[0]['FS_NUMBER'] <> '' )
			$xml .= '							<DataExtModRq>
						  <DataExtMod>
						    <OwnerID>0</OwnerID>
						    <DataExtName>'.EXP_FS_NUMBER.'</DataExtName>
						    <TxnDataExtType>Invoice</TxnDataExtType>
						    <TxnID>'.$txnid.'</TxnID>
						    <DataExtValue>'.hsc30($result[0]['FS_NUMBER']).'</DataExtValue>
						  </DataExtMod>
						</DataExtModRq>
';



$xml .= '						</QBXMLMsgsRq>
                </QBXML>';

		$content = "\nOUTGOING XML##########################################################\n";
		$content .= $xml;
		$content .= "\n##########################################################\n";
		$content .= "requestID = $requestID, user = $user, action = $action, ID = $ID";
		log_content($content);
	} else {
		log_content("\n_quickbooks_dataext_mod_request - missing transaction id\n");
	}

    return $xml;
}

// This function is called when the customer already exists in Quickbooks.
// $ID should match the shipment code.
// $ListID in the XML will be the reference to the customer
function _quickbooks_dataext_mod_response($requestID, $user, $action, $ID, $extra, &$err,
	$last_action_time, $last_actionident_time, $xml, $idents){ 
    
   	global $shipment_table, $sts_qb_dsn;
    
    $content = "\n".date("m/d/Y h:i A")." _quickbooks_dataext_mod_response\n";
    $content .= $xml;
    $content .= "\n##########################################################\n";
    $content .= "requestID = $requestID, user = $user, action = $action, ID = $ID";
	log_content($content);
	
	$result = $shipment_table->update($ID, array("quickbooks_dataext_retries" => -1,
		"quickbooks_status_message" => "OK - Including custom fields" ), false );
}

//! lookup carrier name, given a LOAD_CODE for EXP_LOAD
// The load also needs to be Approved
function lookup_carrier_name( $ID ) {
	global $load_table;
	
	$result = false;
	$check = $load_table->fetch_rows("LOAD_CODE = ".$ID,"CURRENT_STATUS,
		(SELECT CARRIER_NAME FROM EXP_CARRIER WHERE CARRIER_CODE = CARRIER) AS CARRIER_NAME" );
	
	if( is_array($check) && count($check) == 1 && isset($check[0]['CARRIER_NAME']) &&
		isset($check[0]['CURRENT_STATUS']) && $load_table->state_behavior[$check[0]['CURRENT_STATUS']] == 'approved' ) {
		$result = $check[0]['CARRIER_NAME'];
		if( $sts_debug ) echo "<p>".__METHOD__.": look for $result</p>";
	} else {
		log_content( "\n".__FUNCTION__.": not approved or not found $ID\n".
		print_r($check, true)."\n".
		print_r($load_table->state_behavior[$check[0]['CURRENT_STATUS']], true)."\n"
		, EXT_ERROR_ERROR);
	}

    return $result;
}

//! lookup driver name, given a DRIVER_PAY_ID for EXP_DRIVER_PAY_MASTER
// Use FIRST_NAME, MIDDLE_NAME, LAST_NAME, CHECK_NAME from EXP_DRIVER to get name
// The driver pay also needs to be finalized (approved)
function lookup_driver_name( $ID ) {
	global $driver_table;
	log_content("\nlookup_driver_name: entry\n");
	$result = false;
	$check = $driver_table->fetch_rows("DRIVER_CODE = (SELECT DRIVER_ID 
		FROM EXP_DRIVER_PAY_MASTER
		WHERE DRIVER_PAY_ID=$ID
		AND FINALIZE_STATUS = 'finalized')",
		"FIRST_NAME, MIDDLE_NAME, LAST_NAME, CHECK_NAME" );
	
	log_content("lookup_driver_name: after fetch_rows\n");
	if( is_array($check) && count($check) == 1 ) {
		if( ! empty($check[0]['CHECK_NAME']) )
			$result = $check[0]['CHECK_NAME'];
		else {
			$parts = array();
			if(! empty($check[0]['FIRST_NAME']) )	$parts[] = $check[0]['FIRST_NAME'];
			if(! empty($check[0]['MIDDLE_NAME']) )	$parts[] = $check[0]['MIDDLE_NAME'];
			if(! empty($check[0]['LAST_NAME']) )	$parts[] = $check[0]['LAST_NAME'];
			if( count($parts) > 0 ) $result = implode(' ', $parts);
		}
		log_content("lookup_driver_name: result = $result\n");
	} else {
		log_content( __FUNCTION__.": not approved or not found $ID");
	}
    return $result;
}

// We start by checking if the vendor exists in Quickbooks.
// The $ID parameter is the shipment code, and use the BILLTO_NAME field to search for a customer.
function _quickbooks_vendor_query_request($requestID , $user, $action, $ID, $extra, &$err,
	$last_action_time, $last_actionident_time, $version, $locale){
	
	global $load_table, $driver_pay_master_table, $sts_qb_driver_suffix, $sts_qb_carrier_suffix;

    if( is_array($extra) && ! empty($extra['vendortype'])) {
	    log_content("\n_quickbooks_vendor_query_request: vendortype = ".$extra['vendortype'].
	    	" / $sts_qb_driver_suffix, $sts_qb_carrier_suffix");

		switch( $extra['vendortype'] ) {
			case QUICKBOOKS_VENDOR_CARRIER:
				$full_name = lookup_carrier_name( $ID ).
					(isset($sts_qb_carrier_suffix) && $sts_qb_carrier_suffix <> '' ? ' '.$sts_qb_carrier_suffix : '');
				$vendor_table = $load_table;
				break;
				
			case QUICKBOOKS_VENDOR_DRIVER:
				$full_name = lookup_driver_name( $ID ).
					(isset($sts_qb_driver_suffix) && $sts_qb_driver_suffix <> '' ? ' '.$sts_qb_driver_suffix : '');
				$vendor_table = $driver_pay_master_table;
				break;
				
			default:
				$full_name = false;
		}
	}
		
	if( $full_name ) {

        $xml = '<?xml version="1.0" encoding="utf-8"?>
                <?qbxml version="2.0"?>
                <QBXML>
                    <QBXMLMsgsRq onError="stopOnError">
                        <VendorQueryRq requestID="' . $requestID . '">
                        	<FullName>' . hsc($full_name, NAME_FIELD_MAX_LENGTH) . '</FullName>
                        </VendorQueryRq>
                    </QBXMLMsgsRq>
                </QBXML>';

		$content = "\n".date("m/d/Y h:i A")." _quickbooks_vendor_query_request\n";
		$content .= $xml;
		$content .= "\n##########################################################\n";
		$content .= "requestID = $requestID, user = $user, action = $action, ID = $ID";
		log_content($content);
	} else {
		$result = $vendor_table->update($ID, array("quickbooks_status_message" => 
			"Error - not approved or not found $ID") );
		log_content("\n_quickbooks_vendor_query_request not approved or not found $ID\n");
	}

    return $xml;
}

// This function is called when the customer already exists in Quickbooks.
// $ID should match the shipment code.
// $ListID in the XML will be the reference to the customer
function _quickbooks_vendor_query_response($requestID, $user, $action, $ID, $extra, &$err,
	$last_action_time, $last_actionident_time, $xml, $idents){ 
    
   	global $load_table, $driver_pay_master_table, $sts_qb_dsn;
   
    $content = "\n".date("m/d/Y h:i A")." _quickbooks_vendor_query_response\n";
    $content .= $xml;
    $content .= "\n##########################################################\n";
    $content .= "requestID = $requestID, user = $user, action = $action, ID = $ID";
	log_content($content);

    if( is_array($extra) && ! empty($extra['vendortype'])) {
	    log_content("\n_quickbooks_vendor_query_request: vendortype = ".$extra['vendortype']);

		switch( $extra['vendortype'] ) {
			case QUICKBOOKS_VENDOR_CARRIER:
				$vendor_table = $load_table;
				break;
				
			case QUICKBOOKS_VENDOR_DRIVER:
				$vendor_table = $driver_pay_master_table;
				break;
				
			default:
				break;
		}
	}

	if( $parsed = simplexml_load_string( $xml ) ) {
	
		//$content = print_r($parsed, true);
        //log_content($content);
		if ($ListID = $parsed->QBXMLMsgsRs->VendorQueryRs->VendorRet->ListID)
		{
			$content = "\nListID is $ListID\n";
			log_content($content);
			try {
				$content = "Update load\n";
				$result = $vendor_table->update($ID, array("quickbooks_listid_carrier" => ((string)$ListID), "quickbooks_status_message" => "Found Vendor in QB") );
				if( $result ) {
		        	$Queue = new QuickBooks_WebConnector_Queue($sts_qb_dsn);
					$Queue->enqueue(QUICKBOOKS_ADD_BILL, $ID, 0, $extra);
				} else
					$content .= "vendor_table->update($ID, $ListID) returned false\n";
			} catch (Exception $e) {
				$result = $vendor_table->update($ID, array("quickbooks_status_message" => 
					"Error - VQ exception ".$e->getMessage() ) );
			    $content = 'Caught exception: '.  $e->getMessage(). "\n";
			}
			log_content($content);
		}
	}
}

function _quickbooks_vendor_add_request($requestID, $user, $action, $ID, $extra, &$err, $last_action_time, $last_actionident_time, $version, $locale)
{
	$xml = '';
	if( is_array($extra) && ! empty($extra['vendortype'])) {
	    log_content("\n_quickbooks_vendor_add_request: vendortype = ".$extra['vendortype']);
	
		switch( $extra['vendortype'] ) {
			case QUICKBOOKS_VENDOR_CARRIER:
				$xml = _carrier_vendor_add_request( $ID, $requestID );
				break;
				
			case QUICKBOOKS_VENDOR_DRIVER:
				$xml = _driver_vendor_add_request( $ID, $requestID );
				break;
				
			default:
				break;
		}
	}
	
	return $xml;
}

function _carrier_vendor_add_request($ID, $requestID)
{
	global $load_table, $sts_qb_carrier_suffix;
	
	$xml = '';
    $content = "\n".date("m/d/Y h:i A")." _carrier_vendor_add_request ID = $ID";
	log_content($content);

	$result = $load_table->fetch_rows("LOAD_CODE = ".$ID, "CARRIER,
		(SELECT CARRIER_NAME FROM EXP_CARRIER WHERE CARRIER_CODE = CARRIER) CARRIER_NAME,
		(SELECT EMAIL_NOTIFY FROM EXP_CARRIER WHERE CARRIER_CODE = CARRIER) EMAIL_NOTIFY");
	if( is_array($result) && count($result) == 1 && isset($result[0]['CARRIER_NAME']) ) {

		log_content("\nGoing to add ".$result[0]['CARRIER_NAME']."\n");
		$contact = $load_table->database->get_multiple_rows(
			"SELECT ADDRESS, ADDRESS2, CITY, STATE, ZIP_CODE, PHONE_OFFICE, PHONE_FAX, EMAIL
				FROM EXP_CONTACT_INFO
				WHERE CONTACT_CODE = ".$result[0]['CARRIER']."
				AND CONTACT_SOURCE = 'carrier'
				AND CONTACT_TYPE in ('company', 'carrier')
				LIMIT 1" );
		if( is_array($contact) && count($contact) > 0 ) {
			log_content("\nGot contact info\n");
			$vendor = $contact[0];
	 
			$xml = '<?xml version="1.0" encoding="utf-8"?>
				<?qbxml version="2.0"?>
				<QBXML>
					<QBXMLMsgsRq onError="stopOnError">
						<VendorAddRq requestID="' . $requestID . '">
							<VendorAdd>
								<Name>'.hsc($result[0]['CARRIER_NAME'].
								(isset($sts_qb_carrier_suffix) && $sts_qb_carrier_suffix <> '' ? ' '.$sts_qb_carrier_suffix : ''), NAME_FIELD_MAX_LENGTH).'</Name>
								<CompanyName>'.hsc($result[0]['CARRIER_NAME'], NAME_FIELD_MAX_LENGTH).'</CompanyName>
								<VendorAddress>
									<Addr1>'.hsc(isset($vendor['ADDRESS']) ? $vendor['ADDRESS'] : '', ADDR_FIELD_MAX_LENGTH).'</Addr1>
									<Addr2>'.hsc(isset($vendor['ADDRESS2']) ? $vendor['ADDRESS2'] : '', ADDR_FIELD_MAX_LENGTH).'</Addr2>
									<City>'.hsc(isset($vendor['CITY']) ? $vendor['CITY'] : '', CITY_FIELD_MAX_LENGTH).'</City>
									<State>'.hsc(isset($vendor['STATE']) ? $vendor['STATE'] : '', STATE_FIELD_MAX_LENGTH).'</State>
									<PostalCode>'.hsc(isset($vendor['ZIP_CODE']) ? $vendor['ZIP_CODE'] : '', POSTAL_FIELD_MAX_LENGTH).'</PostalCode>
									<Country>USA</Country>
								</VendorAddress>
								<Phone>'.hsc(isset($vendor['PHONE_OFFICE']) ? $vendor['PHONE_OFFICE'] : '', PHONE_FIELD_MAX_LENGTH).
								'</Phone>
								<Fax>'.hsc(isset($vendor['PHONE_FAX']) ? $vendor['PHONE_FAX'] : '', PHONE_FIELD_MAX_LENGTH).'</Fax>
								<Email>'.hsc(isset($result[0]['EMAIL_NOTIFY']) ? $customer['EMAIL_NOTIFY'] : (isset($vendor['EMAIL']) ? $vendor['EMAIL'] : ''), EMAIL_FIELD_MAX_LENGTH).'</Email>
								<Contact>'.hsc(isset($vendor['CONTACT_NAME']) ? $vendor['CONTACT_NAME'] : '', NAME_FIELD_MAX_LENGTH).'</Contact>
								<NameOnCheck>'.hsc($result[0]['NAME'], NAME_FIELD_MAX_LENGTH).'</NameOnCheck>
							</VendorAdd>
						</VendorAddRq>
					</QBXMLMsgsRq>
				</QBXML>';
		    $content = "\nXML##########################################################\n";
		    $content .= $xml;
		    $content .= "\n##########################################################\n";
			log_content($content);
		} else {
			$result = $load_table->update($ID, array("quickbooks_status_message" => 
				"Error - Carrier contact info of type='company' or 'carrier' not found." ) );
			log_content("\nContact info not found\n");
		}
	} else
		log_content("\nLoad not found\n".
			print_r($result, true)."\n" );

	
	return $xml;
}

function _driver_vendor_add_request($ID, $requestID)
{
	global $driver_table, $driver_pay_master_table, $sts_qb_driver_suffix;
	
	log_content("\n_driver_vendor_add_request: entry, ID = $ID");
	$xml = '';
    $content = "\n".date("m/d/Y h:i A")." _driver_vendor_add_request ID = $ID";
	log_content($content);

	$check = $driver_table->fetch_rows("DRIVER_CODE = (SELECT DRIVER_ID 
		FROM EXP_DRIVER_PAY_MASTER
		WHERE DRIVER_PAY_ID=$ID
		AND FINALIZE_STATUS = 'finalized')" );
	log_content("\n_driver_vendor_add_request: after fetch_rows");

	if( is_array($check) && count($check) == 1 && isset($check[0]['FIRST_NAME']) ) {

		log_content("\n_driver_vendor_add_request: before get_multiple_rows");
		$contact = $driver_table->database->get_multiple_rows(
			"SELECT ADDRESS, ADDRESS2, CITY, STATE, ZIP_CODE, PHONE_OFFICE, PHONE_CELL, PHONE_FAX, EMAIL
				FROM EXP_CONTACT_INFO
				WHERE CONTACT_CODE = ".$check[0]['DRIVER_CODE']."
				AND CONTACT_SOURCE = 'driver'
				AND CONTACT_TYPE IN ('individual', 'company')
				LIMIT 1" );

		if( is_array($contact) && count($contact) > 0 ) {
			log_content("\nGot contact info\n");
			$vendor = $contact[0];
	 		
	 		$vendor_name = lookup_driver_name( $ID );

			$xml = '<?xml version="1.0" encoding="utf-8"?>
				<?qbxml version="2.0"?>
				<QBXML>
					<QBXMLMsgsRq onError="stopOnError">
						<VendorAddRq requestID="' . $requestID . '">
							<VendorAdd>
								<Name>'.hsc($vendor_name.
								(isset($sts_qb_driver_suffix) && $sts_qb_driver_suffix <> '' ? ' '.$sts_qb_driver_suffix : ''), NAME_FIELD_MAX_LENGTH).'</Name>
								';
			
			if( ! empty($check[0]['FIRST_NAME']))
				$xml .= '<FirstName>'.hsc($check[0]['FIRST_NAME'], NAME_FIELD_MAX_LENGTH).'</FirstName>
								';
			if( ! empty($check[0]['MIDDLE_NAME']))
				$xml .= '<MiddleName>'.hsc($check[0]['MIDDLE_NAME'], NAME_FIELD_MAX_LENGTH).'</MiddleName>
								';
			if( ! empty($check[0]['LAST_NAME']))
				$xml .= '<LastName>'.hsc($check[0]['LAST_NAME'], NAME_FIELD_MAX_LENGTH).'</LastName>
								';			
			
			$xml .= '<VendorAddress>
									<Addr1>'.hsc(isset($vendor['ADDRESS']) ? $vendor['ADDRESS'] : '', ADDR_FIELD_MAX_LENGTH).'</Addr1>'.(isset($vendor['ADDRESS2']) ? '
									<Addr2>'.hsc($vendor['ADDRESS2'], ADDR_FIELD_MAX_LENGTH).'</Addr2>' : '').(isset($vendor['CITY']) ? '
									<City>'.hsc($vendor['CITY'], ADDR_FIELD_MAX_LENGTH).'</City>' : '').(isset($vendor['STATE']) ? '
									<State>'.hsc($vendor['STATE'], ADDR_FIELD_MAX_LENGTH).'</State>' : '').(isset($vendor['ZIP_CODE']) ? '
									<PostalCode>'.hsc($vendor['ZIP_CODE'], ADDR_FIELD_MAX_LENGTH).'</PostalCode>' : '').'
									<Country>USA</Country>
								</VendorAddress>'.(isset($vendor['PHONE_OFFICE']) ? '
									<Phone>'.hsc($vendor['PHONE_OFFICE'], ADDR_FIELD_MAX_LENGTH).'</Phone>' : '').(isset($vendor['PHONE_FAX']) ? '
									<Fax>'.hsc($vendor['PHONE_FAX'], ADDR_FIELD_MAX_LENGTH).'</Fax>' : '').(isset($check[0]['EMAIL_NOTIFY']) || isset($vendor['EMAIL']) ? '
									<Email>'.hsc(isset($check[0]['EMAIL_NOTIFY']) ? $check[0]['EMAIL_NOTIFY'] : $vendor['EMAIL'] , ADDR_FIELD_MAX_LENGTH).'</Email>' : '').(isset($vendor['CONTACT_NAME']) ? '
									<Contact>'.hsc($vendor['CONTACT_NAME'], ADDR_FIELD_MAX_LENGTH).'</Contact>' : '').'
								<NameOnCheck>'.hsc($vendor_name, NAME_FIELD_MAX_LENGTH).'</NameOnCheck>
							</VendorAdd>
						</VendorAddRq>
					</QBXMLMsgsRq>
				</QBXML>';
		    $content = "\nXML##########################################################\n";
		    $content .= $xml;
		    $content .= "\n##########################################################\n";
			log_content($content);
		} else {
			$result = $driver_pay_master_table->update($ID, array("quickbooks_status_message" => 
				"Error - Driver contact info of type='individual' or 'company' not found." ) );
			log_content("\nDriver contact info of type='individual' or 'company' not found\n");
		}
	} else
		log_content("\nDriver not found ID = $ID\n");
	
	return $xml;
}

function _quickbooks_vendor_add_response($requestID, $user, $action, $ID, $extra, &$err, $last_action_time, $last_actionident_time, $xml, $idents)
{	
   	global $load_table, $driver_pay_master_table, $sts_qb_dsn;

    $content = "\n".date("m/d/Y h:i A")." _quickbooks_vendor_add_response\n";
    $content .= "requestID = $requestID, user = $user, action = $action, ID = $ID, errnum = $errnum";
    $content .= "\n##########################################################\n";
    $content .= $xml;
    $content .= "\n##########################################################\n";
	log_content($content);

    if( is_array($extra) && ! empty($extra['vendortype'])) {
	    log_content("\n_quickbooks_vendor_add_response: vendortype = ".$extra['vendortype']);

		switch( $extra['vendortype'] ) {
			case QUICKBOOKS_VENDOR_CARRIER:
				$vendor_table = $load_table;
				break;
				
			case QUICKBOOKS_VENDOR_DRIVER:
				$vendor_table = $driver_pay_master_table;
				break;
				
			default:
				break;
		}
	}

	if( $parsed = simplexml_load_string( $xml ) ) {
	
		//$content = print_r($parsed, true);
        //log_content($content);
		if ($ListID = $parsed->QBXMLMsgsRs->VendorAddRs->VendorRet->ListID)
		{
			$content = "\nListID is $ListID\n";
			log_content($content);
			try {
				$content = "Update load\n";
				$result = $vendor_table->update($ID, array("quickbooks_listid_carrier" => ((string)$ListID), "quickbooks_status_message" => "Vendor Added for carrier/driver" ) );
				if( $result ) {
		        	$Queue = new QuickBooks_WebConnector_Queue($sts_qb_dsn);
					$Queue->enqueue(QUICKBOOKS_ADD_BILL, $ID, 0, $extra);
				} else
					$content .= "vendor_table->update($ID, $ListID) returned false\n";
			} catch (Exception $e) {
				$result = $vendor_table->update($ID, array("quickbooks_status_message" => 
					"Error - VA exception ".$e->getMessage() ) );
			    $content = 'Caught exception: '.  $e->getMessage(). "\n";
			}
			log_content($content);
		}
	}
}

//! Return a bill number, based on the relevant prefix and the ID
function bill_number( $ID, $vendor_type = QUICKBOOKS_VENDOR_CARRIER ) {
	global $sts_qb_bill_prefix, $sts_qb_driver_bill_prefix;
	
	$result = false;
	switch( $vendor_type ) {
		case QUICKBOOKS_VENDOR_CARRIER:
			$prefix = $sts_qb_bill_prefix;
			break;
			
		case QUICKBOOKS_VENDOR_DRIVER:
			$prefix = $sts_qb_driver_bill_prefix;
			break;
			
		default:
			$prefix = '';
	}
	
	log_content( "\nbill_number: return ".$prefix.$ID."\n");
	return $prefix.$ID;
}

// We add an bill to Quickbooks, either for carrier or driver
function _quickbooks_bill_add_request($requestID , $user, $action, $ID, $extra, &$err,
	$last_action_time, $last_actionident_time, $version, $locale){
	
	$xml = '';
	if( is_array($extra) && ! empty($extra['vendortype'])) {
	    log_content("\n_quickbooks_bill_add_request: vendortype = ".$extra['vendortype']);
	
		switch( $extra['vendortype'] ) {
			case QUICKBOOKS_VENDOR_CARRIER:
				$xml = _carrier_bill_add_request( $ID, $requestID );
				break;
				
			case QUICKBOOKS_VENDOR_DRIVER:
				$xml = _driver_bill_add_request( $ID, $requestID );
				break;
				
			default:
				break;
		}
	}

    return $xml;
}

// We add an bill to Quickbooks, based on a load.
// The $ID parameter is the load code.
function _carrier_bill_add_request($ID, $requestID) {
	
   	global $load_table, $sts_qb_dsn, $sts_qb_bill_terms;

	log_content("\n".date("m/d/Y h:i A")." _carrier_bill_add_request\n");
	$result = $load_table->fetch_rows("LOAD_CODE = ".$ID);
	
	if( is_array($result) && count($result) == 1 && isset($result[0]['quickbooks_listid_carrier']) ) {
		$listid_carrier = $result[0]['quickbooks_listid_carrier'];
		$carrier_base = $result[0]['CARRIER_BASE'];
		$carrier_fsc = $result[0]['CARRIER_FSC'];
		$carrier_handling = $result[0]['CARRIER_HANDLING'];
		
		$bill_number = bill_number( $ID, QUICKBOOKS_VENDOR_CARRIER );
		
		log_content("\n_carrier_bill_add_request - got load data\n");

        $xml = '<?xml version="1.0" encoding="utf-8"?>
                <?qbxml version="13.0"?>
                <QBXML>
                    <QBXMLMsgsRq onError="continueOnError">
						<BillAddRq requestID="' . $requestID . '">
							<BillAdd>
								<VendorRef>
									<ListID>'.$listid_carrier.'</ListID>
								</VendorRef>
';

		$xml .= '								<RefNumber>'.hsc($bill_number, REFNUM_FIELD_MAX_LENGTH).'</RefNumber>
';
		$xml .= '								<TermsRef>
									<FullName>'.hsc($sts_qb_bill_terms, FULLNAME_FIELD_MAX_LENGTH).'</FullName>
								</TermsRef>
';

		
		//! PO Number - not used currently
		if( isset($result[0]['PO_NUMBER']) && $result[0]['PO_NUMBER'] <> '') {
			$xml .= '<PONumber>'.hsc($result[0]['PO_NUMBER'], PONUMBER_FIELD_MAX_LENGTH).'</PONumber>
';
		}

		$xml .= '								<Memo>From Exspeedite Load #'.$ID.'</Memo>
';


		if( $carrier_base > 0 )
			$xml .= '								<ItemLineAdd>
									<ItemRef>
										<FullName>'.EXP_CARRIER_FREIGHT_ITEM.'</FullName>
									</ItemRef>
									<Desc>Freight charges</Desc>
									<Quantity>1</Quantity>
									<Amount>'.number_format((float) $carrier_base, 2,".","").'</Amount>
								</ItemLineAdd>
';


		if( $carrier_fsc > 0 )
			$xml .= '								<ItemLineAdd>
									<ItemRef>
										<FullName>'.EXP_CARRIER_FSC_ITEM.'</FullName>
									</ItemRef>
									<Desc>Fuel surcharge</Desc>
									<Quantity>1</Quantity>
									<Amount>'.number_format((float) $carrier_fsc, 2,".","").'</Amount>
								</ItemLineAdd>
';
		
		if( $carrier_handling > 0 )
			$xml .= '								<ItemLineAdd>
									<ItemRef>
										<FullName>'.EXP_CARRIER_HANDLING_ITEM.'</FullName>
									</ItemRef>
									<Desc>Handling charges</Desc>
									<Quantity>1</Quantity>
									<Amount>'.number_format((float) $carrier_handling, 2,".","").'</Amount>
								</ItemLineAdd>
';


$xml .= '							</BillAdd>
						</BillAddRq>
';



$xml .= '						</QBXMLMsgsRq>
                </QBXML>';

		$content = "\nOUTGOING XML##########################################################\n";
		$content .= $xml;
		$content .= "\n##########################################################\n";
		$content .= "ID = $ID";
		log_content($content);
	} else {
		log_content("\n_carrier_bill_add_request - can't get load data\n");
	}

    return $xml;
}

// We add an bill to Quickbooks, for a driver
function _driver_bill_add_request($ID, $requestID) {
	
	global $driver_pay_master_table, $sts_qb_bill_terms;

	log_content("\n".date("m/d/Y h:i A")." _driver_bill_add_request\n");
	
	$check = $driver_pay_master_table->fetch_rows("DRIVER_PAY_ID = ".$ID);
	
	if( is_array($check) && count($check) == 1 && isset($check[0]['quickbooks_listid_carrier']) ) {
		$listid_carrier = $check[0]['quickbooks_listid_carrier'];
		$from = isset($check[0]['WEEKEND_FROM']) ? $check[0]['WEEKEND_FROM'] : 0;
		$to = isset($check[0]['WEEKEND_TO']) ? $check[0]['WEEKEND_TO'] : 0;
		$trip_pay = isset($check[0]['TRIP_PAY']) ? $check[0]['TRIP_PAY'] : 0;
		$bonus = isset($check[0]['BONUS']) ? $check[0]['BONUS'] : 0;
		$handling = isset($check[0]['HANDLING']) ? $check[0]['HANDLING'] : 0;
		$gross = isset($check[0]['GROSS_EARNING']) ? $check[0]['GROSS_EARNING'] : 0;

		$bill_number = bill_number( $ID, QUICKBOOKS_VENDOR_DRIVER );
		
		log_content("\n_driver_bill_add_request - got load data\n");

        $xml = '<?xml version="1.0" encoding="utf-8"?>
                <?qbxml version="13.0"?>
                <QBXML>
                    <QBXMLMsgsRq onError="continueOnError">
						<BillAddRq requestID="' . $requestID . '">
							<BillAdd>
								<VendorRef>
									<ListID>'.$listid_carrier.'</ListID>
								</VendorRef>
';

		$xml .= '								<RefNumber>'.hsc($bill_number, REFNUM_FIELD_MAX_LENGTH).'</RefNumber>
';
		$xml .= '								<TermsRef>
									<FullName>'.hsc($sts_qb_bill_terms, FULLNAME_FIELD_MAX_LENGTH).'</FullName>
								</TermsRef>
';

			//! ------------------- Bill lines
			$line_number = 1;
			
			$msg = 'Driver Pay for week '.date('m/d/Y',strtotime($from)).
				' to '.date('m/d/Y',strtotime($to));
			
		$xml .= '								<Memo>'.$msg.'</Memo>
';

			$loads = $driver_pay_master_table->get_loads( $ID );
			if( is_array($loads) && count($loads) > 0 )
				$msg .= "\n\nLoads: ".implode(', ', $loads);
			
			//! Give this a go, may fail...
			$xml .= '								<ItemLineAdd>
									<Desc>'.$msg.'</Desc>
								</ItemLineAdd>
';

			
			//! Trip Pay
			if( $trip_pay > 0 ) {
				$xml .= '								<ItemLineAdd>
									<ItemRef>
										<FullName>'.EXP_DRIVER_TRIP_PAY_ITEM.'</FullName>
									</ItemRef>
									<Desc>Driver Trip Pay</Desc>
									<Quantity>1</Quantity>
									<Amount>'.number_format((float) $trip_pay, 2,".","").'</Amount>
								</ItemLineAdd>
';
			}

			//! Bonus
			if( $bonus > 0 ) {
				$xml .= '								<ItemLineAdd>
									<ItemRef>
										<FullName>'.EXP_DRIVER_BONUS_ITEM.'</FullName>
									</ItemRef>
									<Desc>Driver Bonus</Desc>
									<Quantity>1</Quantity>
									<Amount>'.number_format((float) $bonus, 2,".","").'</Amount>
								</ItemLineAdd>
';
			}

			//! Handling charges
			if( $handling > 0 ) {
				$xml .= '								<ItemLineAdd>
									<ItemRef>
										<FullName>'.EXP_DRIVER_HANDLING_ITEM.'</FullName>
									</ItemRef>
									<Desc>Driver Handling</Desc>
									<Quantity>1</Quantity>
									<Amount>'.number_format((float) $handling, 2,".","").'</Amount>
								</ItemLineAdd>
';
			}
		
$xml .= '							</BillAdd>
						</BillAddRq>
';



$xml .= '						</QBXMLMsgsRq>
                </QBXML>';

		$content = "\nOUTGOING XML##########################################################\n";
		$content .= $xml;
		$content .= "\n##########################################################\n";
		$content .= "ID = $ID";
		log_content($content);
	} else {
		log_content("\n_driver_bill_add_request - can't get load data\n");
	}

    return $xml;
}

function _quickbooks_bill_add_response($requestID, $user, $action, $ID, $extra, &$err,
	$last_action_time, $last_actionident_time, $xml, $idents){ 
    
   	global $load_table, $driver_pay_master_table;
    
    $content = "\n".date("m/d/Y h:i A")." _quickbooks_bill_add_response\n";
    $content .= $xml;
    $content .= "\n##########################################################\n";
    $content .= "requestID = $requestID, user = $user, action = $action, ID = $ID";
	log_content($content);
	
    if( is_array($extra) && ! empty($extra['vendortype'])) {
	    log_content("\n_quickbooks_vendor_add_response: vendortype = ".$extra['vendortype']);

		$vendor_type = $extra['vendortype'];
		switch( $extra['vendortype'] ) {
			case QUICKBOOKS_VENDOR_CARRIER:
				$vendor_table = $load_table;
				break;
				
			case QUICKBOOKS_VENDOR_DRIVER:
				$vendor_table = $driver_pay_master_table;
				break;
				
			default:
				break;
		}
	}

	if( $parsed = simplexml_load_string( $xml ) ) {
	
		//$content = print_r($parsed, true);
        //log_content($content);
        // quickbooks_listid_invoice
		if ($TxnID = $parsed->QBXMLMsgsRs->BillAddRs->BillRet->TxnID)
		{
			$content = "\nTxnID is $TxnID\n";
			log_content($content);
			try {
				$content = "Update vendor table\n";
				$changes = array("quickbooks_txnid_ap" => ((string)$TxnID),
					'PAID_DATE' => date("Y-m-d H:i:s"),
					"quickbooks_status_message" => "OK" );
				if( $vendor_type == QUICKBOOKS_VENDOR_DRIVER ) {
					$changes['FINALIZE_STATUS'] = 'paid';
					//! update each load to 'Paid' status
					$loads = $driver_pay_master_table->get_loads( $ID );
					if( is_array($loads) && count($loads) > 0 ) {
						foreach($loads as $load) {
							$load_table->update($load, array( 'CURRENT_STATUS' => $load_table->behavior_state['billed'] ));
						}
					}
				}
				$result = $vendor_table->update($ID, $changes );
				if( $result ) {
					if( $vendor_type == QUICKBOOKS_VENDOR_CARRIER ) {
			        	// Update status to billed
			        	$result2 = $vendor_table->change_state_behavior( $ID, 'billed' );
			        	if( ! $result2 )
			        		$content .= "vendor_table->change_state_behavior( $ID, 'billed' ) returned false\n";
		        	}
				} else
					$content .= "vendor_table->update($ID, $TxnID) returned false\n";
			} catch (Exception $e) {
				$result = $vendor_table->update($ID, array("quickbooks_status_message" => 
					"Error - VA exception ".$e->getMessage() ) );
			    $content = 'Caught exception: '.  $e->getMessage(). "\n";
			}
			log_content($content);
		}
		
	}
}




