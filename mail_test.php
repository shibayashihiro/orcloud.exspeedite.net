<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
<title>Mail Test</title>
</head>
<?php
error_reporting(E_ALL);
set_time_limit(0);

//echo "<pre>";
//var_dump($_REQUEST, $_SERVER);
//echo "</pre>";
echo "<p>". $_SERVER["HTTP_USER_AGENT"]."</p>";
if(strpos($_SERVER["HTTP_USER_AGENT"],"iPod") <> false)
	echo "<p>iPod detected</p>";
else if(strpos($_SERVER["HTTP_USER_AGENT"],"iPhone") <> false)
	echo "<p>iPhone detected</p>";
else if(strpos($_SERVER["HTTP_USER_AGENT"],"iPad") <> false)
	echo "<p>iPad detected</p>";
else
	echo "<p>other detected</p>";
	
if(class_exists("PEAR")) {
	echo "<p>PEAR detected</p>";
	require_once "Mail.php";
} else
	echo "<p>PEAR not detected</p>";

if( function_exists( "mail") ) {
	echo "<p>Sending e-mail</p>";
	$message="Test email.
	
This is a test.

";
	
	$to = "duncan@strongtco.com";
	//$to = "scott.elliott@hutt.com";
	
	$subject = "Exspeedite - Test";
	
	$headers = "From: duncan@strongtco.com\r\n" ;
	
	
	// Set flag that this is a parent file
	define( '_STS_INCLUDE', 1 );
	require_once( "include/sts_config.php" );
	require_once( "include/sts_setting_class.php" );
	$sts_debug = false;
	
	require_once( "include/sts_email_class.php" );
	
	$setting_table = sts_setting::getInstance( $exspeedite_db, $sts_debug );
	
	$message .= "<br>".$setting_table->get( 'company', 'NAME' )."<br>
".$sts_crm_root."<br>
".$sts_crm_dir."<br>
";
	
	$email = sts_email::getInstance($exspeedite_db, $sts_debug);
	
	//$email->send_email( $to, '', $subject, $message );
	$email->send_alert('Testing');
	$email->send_alert('Testing2', EXT_ERROR_WARNING);

} else {
	echo "<p>mail() does not exist</p>";
}
	
?>

<body>
</body>
</html>