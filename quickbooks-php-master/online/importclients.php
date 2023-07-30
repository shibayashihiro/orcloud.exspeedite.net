<?php

// Set flag that this is a parent file
define( '_STS_INCLUDE', 1 );

// Setup Session
require_once( "../../include/sts_session_setup.php" );

require_once dirname(__FILE__) . '/config.php';

require_once( "include/sts_session_class.php" );
require_once( "include/sts_client_class.php" );
require_once( "include/sts_contact_info_class.php" );

$client_table = sts_client::getInstance($exspeedite_db, $sts_debug);
$contact_info_table = new sts_contact_info($exspeedite_db, $sts_debug);

$my_session = sts_session::getInstance( $exspeedite_db, $sts_debug );
$my_session->access_check( EXT_GROUP_ADMIN );	// Make sure we should be here

//! SCR# 239 - multi-company, use multiple Quickbooks companies
if( $multi_company && $sts_qb_multi && isset($_SESSION['SETUP_COMPANY']) ) {
	list($quickbooks_is_connected, $realm, $Context) =
		connect_to_quickbooks( $_SESSION['SETUP_COMPANY'] );
} else {
	list($quickbooks_is_connected, $realm, $Context) = connect_to_quickbooks();
}

//! SCR# 451 - log issues with import
if( isset($sts_qb_log_file) && $sts_qb_log_file <> '' && 
	$sts_qb_log_file[0] <> '/' && $sts_qb_log_file[0] <> '\\' 
	&& $sts_qb_log_file[1] <> ':' )
	$sts_qb_log_file = $sts_crm_dir.$sts_qb_log_file;

function log_event( $message, $level = EXT_ERROR_ERROR ) {
	global $sts_qb_log_file, $sts_qb_diag_level;
	
	if( $sts_qb_diag_level >= $level ) {
		if( (file_exists($sts_qb_log_file) && is_writable($sts_qb_log_file)) ||
			is_writable(dirname($sts_qb_log_file)) ) 
			file_put_contents($sts_qb_log_file, date('m/d/Y h:i:s A')." pid=".getmypid().
				" msg=".$message."\n\n", FILE_APPEND);
	}
}



function add_client( $name, $addr1, $addr2, $city, $state, $zip, $phone, $email, $type ) {
	global $sts_debug, $client_table, $contact_info_table;
	$check = $client_table->fetch_rows("CLIENT_NAME = '".$client_table->real_escape_string(trim((string) $name))."' AND
		ISDELETED = false",$client_table->primary_key.
		", BILL_TO, CONSIGNEE");
	$is_new = (is_array($check) && count($check) == 0)  || $check == false;
	if( $sts_debug ) echo "<p>add_client, ".($is_new ? "new" : "exists ".$check[0][$client_table->primary_key])."</p>";
	if( $is_new ) {
		$client_fields = array("CLIENT_NAME" => trim((string) $name), "COMMENTS" => "Import from Quickbooks Online" );
		switch( $type ) {
			case 'BILLTO': $client_fields["BILL_TO"] = "TRUE";
			break;
			case 'CONS': $client_fields["CONSIGNEE"] = "TRUE";
			break;
		}
		$client_fields["ISDELETED"] = "FALSE";
		$client_code = $client_table->add($client_fields);
		if( $sts_debug ) echo "<p>add_client, client_code = ".($client_code == false ? 'false' : $client_code)."</p>";
		
		if( $client_code != false ) {
			$contact_info_fields = array("CONTACT_CODE" => $client_code, "CONTACT_SOURCE" => 'client',
				"CONTACT_TYPE" => ($type == 'BILLTO' ? 'bill_to' : 'consignee'), "LABEL" => $name, "ADDRESS" => $addr1,
				"ADDRESS2" => $addr2, "CITY" => $city, "STATE" => $state, "ZIP_CODE" => $zip,
				"PHONE_OFFICE" => $phone, "EMAIL" => $email,
				"ISDELETED" => "FALSE" );
			$contact_info_code = $contact_info_table->add($contact_info_fields);
			if( $sts_debug ) echo "<p>add_client, contact_info_code = ".($contact_info_code == false ? 'false' : $contact_info_code)."</p>";
		} else {
			log_event( "importclients: add_client: failed to add client $name - ".$client_table->error() );
		}
	} else {
		log_event( "importclients: add_client: failed to add client $name - already exists", EXT_ERROR_DEBUG );
		$client_code = false;
	}
	
	return $client_code;
}


if( $quickbooks_is_connected ) {
	echo '<center><img src="../../images/loading.gif" alt="loading" width="125" height="125" /></center>';
	ob_flush(); flush();
	log_event( "importclients: start", EXT_ERROR_DEBUG );
	
	$CustomerService = new QuickBooks_IPP_Service_Customer();

	$customers = $CustomerService->query($Context, $realm, "SELECT * FROM Customer 
		where Active = true MAXRESULTS 1000");

	if( 0 && $sts_debug ) {
		echo "<pre>";
		var_dump($customers);
		echo "</pre>";
	}
	
	if( is_array($customers) && count($customers) > 0 ) {
		log_event( "importclients: ".count($customers)." customers", EXT_ERROR_NOTICE );
		foreach( $customers as $customer ) {
			$name = $customer->getDisplayName();

			$number = $customer->getPrimaryPhone();
			$number = is_object($number) ? $number->getFreeFormNumber() : 'NULL';
			
			/*
			$mobile = $customer->getMobile();
			$mobile = is_object($mobile) ? $mobile->getFreeFormNumber() : 'ERROR';

			$fax = $customer->getFax();
			$fax = is_object($fax) ? $fax->getFreeFormNumber() : 'ERROR';
			*/

			$email = $customer->getPrimaryEmailAddr();
			$email = is_object($email) ? $email->getAddress() : 'NULL';

			$name2 = $customer->getPrintOnCheckName();

			$line1 = 'NULL';
			$line2 = 'NULL';
			$city = 'NULL';
			$state = 'NULL';
			$postal = 'NULL';
			$addr = $customer->getBillAddr();
			if( is_object($addr)) {
				$line1 = $addr->getLine1();
				$line2 = $addr->getLine2();
				$city = $addr->getCity();
				$state = $contact_info_table->get_state($addr->getCountrySubDivisionCode());
				$postal = $addr->getPostalCode();
			}

			// Only import complete clients
			//if( $number <> 'ERROR' && $email <> 'ERROR' && is_object($addr) ) {
				$res = add_client( $name, $line1, $line2, $city, $state, $postal, $number, $email, 'BILLTO' );
				if( $sts_debug ) {
					echo "<pre>";
					var_dump($name, $name2, $line1, $line2, $city, $state, $postal, $number, $email, $res);
					echo "</pre>";
				}
			//}
		}
	}
	log_event( "importclients: end", EXT_ERROR_DEBUG );
}

if( ! $sts_debug ) {
	echo "<script type=\"text/javascript\">
	window.location = \"setup2.php\"
</script>";
}	

?>