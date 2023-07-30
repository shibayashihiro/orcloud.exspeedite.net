<?php

// I always program in E_STRICT error mode... 
error_reporting(E_ALL | E_STRICT);
ini_set('display_errors', 1);


// Set flag that this is a parent file
define( '_STS_INCLUDE', 1 );

require_once('../../../include/sts_config.php');

// Require the framework
require_once '../../QuickBooks.php';

$dsn = 'mysqli://'.$sts_username.':'.$sts_password.'@'.$sts_db_host.'/'.$sts_database;

if( isset($_GET['code']) ) {
	// Queue up the customer add 
	$Queue = new QuickBooks_WebConnector_Queue($dsn);
	$Queue->enqueue(QUICKBOOKS_QUERY_CUSTOMER, $_GET['code']);
	echo "<p>Queued up ".$_GET['code']."</p>";
}

?>