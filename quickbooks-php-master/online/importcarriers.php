<?php

// Set flag that this is a parent file
define( '_STS_INCLUDE', 1 );

// Setup Session
require_once( "../../include/sts_session_setup.php" );

require_once dirname(__FILE__) . '/config.php';

require_once( "include/sts_session_class.php" );
require_once( "include/sts_carrier_class.php" );
require_once( "include/sts_contact_info_class.php" );

$carrier_table = sts_carrier::getInstance($exspeedite_db, false);
$contact_info_table = new sts_contact_info($exspeedite_db, false);

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

function add_carrier( $name, $addr1, $addr2, $city, $state, $zip, $phone, $email ) {
	global $sts_debug, $carrier_table, $contact_info_table;
	$check = $carrier_table->fetch_rows("CARRIER_NAME = '".$carrier_table->real_escape_string(trim((string) $name))."' AND
		ISDELETED = false",$carrier_table->primary_key);
	$is_new = (is_array($check) && count($check) == 0)  || $check == false;
	if( false && $sts_debug ) echo "<p>add_carrier, ".($is_new ? "new" : "exists ".$check[0][$carrier_table->primary_key])."</p>";
	if( $is_new ) {
		$carrier_fields = array("CARRIER_NAME" => trim((string) $name), "CARRIER_TYPE" => "carrier",
			"EMAIL_NOTIFY" => $email, "NUM_TRUCKS" => 1 );

		$carrier_fields["ISDELETED"] = "FALSE";
		$carrier_code = $carrier_table->add($carrier_fields);
		
		if( $carrier_code != false ) {
			$contact_info_fields = array("CONTACT_CODE" => $carrier_code, "CONTACT_SOURCE" => 'carrier',
				"CONTACT_TYPE" => 'company', "LABEL" => $name, "ADDRESS" => $addr1,
				"ADDRESS2" => $addr2, "CITY" => $city, "STATE" => $state, "ZIP_CODE" => $zip,
				"PHONE_OFFICE" => $phone, "EMAIL" => $email,
				"ISDELETED" => "FALSE" );
			$contact_info_code = $contact_info_table->add($contact_info_fields);
		} else {
			log_event( "importcarriers: add_carrier: failed to add carrier $name - ".$carrier_table->error() );
		}
	} else {
		log_event( "importcarriers: add_carrier: failed to add carrier $name - already exists", EXT_ERROR_DEBUG );
		$carrier_code = false;
	}
	
	return $carrier_code;
}

if( $quickbooks_is_connected ) {
	echo '<center><img src="../../images/loading.gif" alt="loading" width="125" height="125" /></center>';
	ob_flush(); flush();
	log_event( "importcarriers: start", EXT_ERROR_DEBUG );
	
	$VendorService = new QuickBooks_IPP_Service_Vendor();

	$vendors = $VendorService->query($Context, $realm, "SELECT * FROM Vendor
		where Active = true MAXRESULTS 1000");

	if( 0 && $sts_debug ) {
		echo "<pre>";
		var_dump($vendors);
		echo "</pre>";
	}
	
	if( is_array($vendors) && count($vendors) > 0 ) {
		log_event( "importcarriers: ".count($vendors)." carriers", EXT_ERROR_NOTICE );
		foreach( $vendors as $vendor ) {
			// Use AlternatePhone to determine Carriers
			$number2 = $vendor->getAlternatePhone();
			$number2 = is_object($number2) ? $number2->getFreeFormNumber() : 'ERROR';
			if( strcasecmp($number2, 'Carrier') == 0 ) {
				$name = $vendor->getDisplayName();
	
				$number = $vendor->getPrimaryPhone();
				$number = is_object($number) ? $number->getFreeFormNumber() : 'ERROR';
				
				/*
				$mobile = $vendor->getMobile();
				$mobile = is_object($mobile) ? $mobile->getFreeFormNumber() : 'ERROR';
	
				$fax = $vendor->getFax();
				$fax = is_object($fax) ? $fax->getFreeFormNumber() : 'ERROR';
				*/
	
				$email = $vendor->getPrimaryEmailAddr();
				$email = is_object($email) ? $email->getAddress() : 'ERROR';
	
				$name2 = $vendor->getPrintOnCheckName();
	
				$addr = $vendor->getBillAddr();
				if( is_object($addr)) {
					$line1 = $addr->getLine1();
					$line2 = $addr->getLine2();
					$city = $addr->getCity();
					$state = $contact_info_table->get_state($addr->getCountrySubDivisionCode());
					$postal = $addr->getPostalCode();
				}
	
				// Only import complete carriers
				if( $number <> 'ERROR' && $email <> 'ERROR' && is_object($addr) ) {
					$res = add_carrier( $name, $line1, $line2, $city, $state, $postal, $number, $email );
					if( $sts_debug ) {
						echo "<pre>";
						var_dump($name, $name2, $line1, $line2, $city, $state, $postal, $number, $email, $res);
						echo "</pre>";
					}
				}
			}
		}
	}
	log_event( "importcarriers: end", EXT_ERROR_DEBUG );
}

if( ! $sts_debug ) {
	echo "<script type=\"text/javascript\">
	window.location = \"setup2.php\"
</script>";
}	

?>