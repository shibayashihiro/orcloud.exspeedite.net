<?php 
// Set flag that this is a parent file
define( '_STS_INCLUDE', 1 );

if( php_sapi_name() <> 'cli' ) {
	// Setup Session
	require_once( "include/sts_session_setup.php" );
} else {
	// Set up include path. Change this for different installations
	set_include_path('\var\www\domains\orcloud.exspeedite.net\htdocs:/usr/share/pear:/usr/share/php:/var/www/domains/orcloud\var\www\domains\orcloud.exspeedite.net\htdocsexspeedite\var\www\domains\orcloud.exspeedite.net\htdocsnet/htdocs/include:/var/www/domains/orcloud\var\www\domains\orcloud.exspeedite.net\htdocsexspeedite\var\www\domains\orcloud.exspeedite.net\htdocsnet/htdocs');
	$_SESSION = array();
}
set_time_limit(0);

require_once( "include/sts_config.php" );
$sts_debug = isset($_GET['debug']);
require_once( "include/sts_setting_class.php" );
if( php_sapi_name() <> 'cli' ) {
	require_once( "include/sts_session_class.php" );
	$sts_debug = isset($_GET['debug']) && in_group( EXT_GROUP_DEBUG );
	
	$my_session = sts_session::getInstance( $exspeedite_db, $sts_debug );
	$my_session->access_check( EXT_GROUP_ADMIN );	// Make sure we should be here
}

if( $sts_debug ) {
	$sts_subtitle = "Import Synergy";
	require_once( "include/header_inc.php" );
}

//echo "<p>".get_include_path()." ".getcwd()."</p>";

require_once( "include/sts_shipment_class.php" );
require_once( "include/sts_detail_class.php" );
require_once( "include/sts_commodity_class.php" );
require_once( "include/sts_unit_class.php" );
require_once( "include/sts_client_class.php" );
require_once( "include/sts_user_class.php" );
require_once( "include/sts_contact_info_class.php" );

$shipment_table = sts_shipment::getInstance($exspeedite_db, $sts_debug);
$detail_table = sts_detail::getInstance($exspeedite_db, false);
$commodity_table = new sts_commodity($exspeedite_db, false);
$unit_table = new sts_unit($exspeedite_db, false);
$client_table = sts_client::getInstance($exspeedite_db, false);
$user_table = new sts_user($exspeedite_db, false);
$contact_info_table = new sts_contact_info($exspeedite_db, false);

function log_event( $event ) {
	global $sts_log_directory, $sts_debug;
	
	if( isset($sts_log_directory) ) {
		//if( $sts_debug ) echo "<p>log_event: sts_log_directory = $sts_log_directory</p>";
		if(is_writable($sts_log_directory) ) {
			file_put_contents($sts_log_directory, date('m/d/Y h:i:s A')." ".$event.PHP_EOL, FILE_APPEND);
		} else {
			if( $sts_debug ) echo "<p>log_event: $sts_log_directory not writeable</p>";
		}
	}
}

function add_client( $name, $addr1, $addr2, $city, $state, $zip, $phone, $fax, $email, $type ) {
	global $sts_debug, $client_table, $contact_info_table;
	$check = $client_table->fetch_rows("CLIENT_NAME = '".trim((string) $name)."' AND
		ISDELETED = false",$client_table->primary_key.
		", BILL_TO, CONSIGNEE");
	$is_new = is_array($check) && count($check) == 0;
	if( false && $sts_debug ) echo "<p>add_client, ".($is_new ? "new" : "exists ".$check[0][$client_table->primary_key])."</p>";
	if( $is_new ) {
		$client_fields = array("CLIENT_NAME" => trim((string) $name), "COMMENTS" => "Added via Synergy import",
			"SYNERGY_IMPORT" => "TRUE" );
		switch( $type ) {
			case 'BILLTO': $client_fields["BILL_TO"] = "TRUE";
			break;
			case 'CONS': $client_fields["CONSIGNEE"] = "TRUE";
			break;
		}
		$client_fields["DELETED"] = "FALSE";
		$client_code = $client_table->add($client_fields);
		
		$contact_info_fields = array("CONTACT_CODE" => $client_code, "CONTACT_SOURCE" => 'client',
			"CONTACT_TYPE" => ($type == 'BILLTO' ? 'bill_to' : 'consignee'), "LABEL" => $name, "ADDRESS" => $addr1,
			"ADDRESS2" => $addr2, "CITY" => $city, "STATE" => $state, "ZIP_CODE" => $zip,
			"PHONE_OFFICE" => $phone, "PHONE_FAX" => $fax, "EMAIL" => $email, "SYNERGY_IMPORT" => "TRUE",
			"DELETED" => "FALSE" );
		$contact_info_code = $contact_info_table->add($contact_info_fields);
		log_event( "add_client: added ".trim((string) $name)." code = ".$client_code );
	} else {
		$client_code = $check[0][$client_table->primary_key];
		if( $check[0]["BILL_TO"] == 0 && $type == "BILL_TO" )
			$client_table->update( $client_code, array("BILL_TO" => "TRUE") );
		if( $check[0]["CONSIGNEE"] == 0 && $type == "CONS" )
			$client_table->update( $client_code, array("CONSIGNEE" => "TRUE") );
		log_event( "add_client: found ".trim((string) $name)." code = ".$client_code );
	}
	return $client_code;
}

function add_detail( $shipment_code, $detail ) {
	global $sts_debug, $commodity_table, $detail_table, $unit_table;
	
	if( false && $sts_debug ) {
		echo "<pre>";
		var_dump($detail);
		echo "</pre>";
	}
	
	$check = $commodity_table->fetch_rows("NAME = '".(string) $detail->COMMODITY."'",$commodity_table->primary_key);
	$is_new = is_array($check) && count($check) == 0;
	if( false && $sts_debug ) echo "<p>add_detail = ".$detail->COMMODITY." (".($is_new ? "new" : "exists").")</p>";
	
	if( $is_new ) {
		$commodity_fields = array("COMMODITY_NAME" => (string) $detail->COMMODITY, 
			"SYNERGY_IMPORT" => "TRUE" );
		if( isset($detail->COMMODITY_DESC) ) $commodity_fields["COMMODITY_DESCRIPTION"] = trim((string) $detail->COMMODITY_DESC);
		if( isset($detail->DANGEROUS_GOODS) ) $commodity_fields["DANGEROUS"] = intval( $detail->DANGEROUS_GOODS );
		if( isset($detail->TEMP_CONTROLLED) ) $commodity_fields["TEMP_CONTROLLED"] = intval( $detail->TEMP_CONTROLLED );
		if( isset($detail->HIGH_VALUE) ) $commodity_fields["HIGH_VALUE"] = intval( $detail->HIGH_VALUE );
		if( isset($detail->TEMP_REQ) ) $commodity_fields["TEMPERATURE"] = intval( $detail->TEMP_REQ );

		$piece_unit = $unit_table->fetch_rows("SYMBOL = '".trim((string) $detail->PIECES_UNITS)."'
			AND UNIT_TYPE = 'item'",$unit_table->primary_key);
		if( is_array($piece_unit) && count($piece_unit) > 0 )
			$commodity_fields["PIECES_UNITS"] = $piece_unit[0]["UNIT_CODE"];
		else if( $sts_debug )
			echo "<p><stong>add_detail: PIECES_UNITS ".(string) $detail->PIECES_UNITS." unknown.</stong></p>";

		// Fixed to Fahrenheit
		$temp_unit = $unit_table->fetch_rows("SYMBOL = 'F'
			AND UNIT_TYPE = 'temperature'",$unit_table->primary_key);
		if( is_array($temp_unit) && count($temp_unit) > 0 )
			$commodity_fields["TEMPERATURE_UNITS"] = $temp_unit[0]["UNIT_CODE"];

		$commodity_code = $commodity_table->add($commodity_fields);
		log_event( "add_detail: added commodity ".trim((string) $detail->COMMODITY)." code = ".$commodity_code );
	} else {
		$commodity_code = $check[0][$commodity_table->primary_key];
	}
	
	$detail_fields = array("SHIPMENT_CODE" => $shipment_code,
		"COMMODITY" => $commodity_code, 
		"SYNERGY_IMPORT" => "TRUE" );
	if( isset($detail->PALLETS) ) $detail_fields["PALLETS"] = intval( $detail->PALLETS );
	if( isset($detail->PIECES) ) $detail_fields["PIECES"] = intval( $detail->PIECES );

	$piece_unit = $commodity_table->fetch_rows($commodity_table->primary_key." = ".$commodity_code,"PIECES_UNITS");
	if( is_array($piece_unit) && count($piece_unit) > 0 && isset($piece_unit[0]["PIECES_UNITS"]) )
		$detail_fields["PIECES_UNITS"] = $piece_unit[0]["PIECES_UNITS"];

	// If the unit is LB, we use the amount for the weight
	if( isset($detail->PIECES_UNITS) && trim((string) $detail->PIECES_UNITS) == 'LB' && isset($detail->PIECES) )
		$detail_fields["WEIGHT"] = intval( $detail->PIECES );
	else if( isset($detail->WEIGHT) ) $detail_fields["WEIGHT"] = intval( $detail->WEIGHT );
	
	if( isset($detail->AMOUNT) ) $detail_fields["AMOUNT"] = intval( $detail->AMOUNT );
	if( isset($detail->HIGH_VALUE) ) $detail_fields["HIGH_VALUE"] = intval( $detail->HIGH_VALUE );
	if( isset($detail->DANGEROUS_GOODS) ) $detail_fields["DANGEROUS_GOODS"] = intval( $detail->DANGEROUS_GOODS );
	if( isset($detail->TEMP_CONTROLLED) ) $detail_fields["TEMP_CONTROLLED"] = intval( $detail->TEMP_CONTROLLED );
	if( isset($detail->BILLABLE) ) $detail_fields["BILLABLE"] = intval( $detail->BILLABLE );
	if( isset($detail->TEMP_REQ) ) $detail_fields["TEMP_REQ"] = trim((string) $detail->TEMP_REQ);

	$detail_code = $detail_table->add($detail_fields);
	if( ! $detail_code )
		log_event( "add_detail: added detail ".trim((string) $detail->COMMODITY)." code = ".$commodity_code );
	
}

function add_shipment( $shipment, $billto_code ) {
	global $sts_debug, $client_table, $shipment_table, $sts_fns_name;
	
	if( $sts_debug ) {
		echo "<pre>";
		var_dump($shipment);
		echo "</pre>";
	}
	
	$shipment_code = $shipment_table->create_empty();
	
	$shipment_fields = array( "SHIPMENT_TYPE" => 'prepaid', "PRIORITY" => 'regular',
		"SYNERGY_IMPORT" => 'TRUE' );
	
	//Check every field, they might not be there
	if( isset($shipment->BILLTO_NAME) ) $shipment_fields["BILLTO_NAME"] = trim((string) $shipment->BILLTO_NAME);
	if( isset($shipment->BILLTO_ADDR1) ) $shipment_fields["BILLTO_ADDR1"] = trim((string) $shipment->BILLTO_ADDR1);
	if( isset($shipment->BILLTO_ADDR2) ) $shipment_fields["BILLTO_ADDR2"] = trim((string) $shipment->BILLTO_ADDR2);
	if( isset($shipment->BILLTO_CITY) ) $shipment_fields["BILLTO_CITY"] = trim((string) $shipment->BILLTO_CITY);
	if( isset($shipment->BILLTO_STATE) ) $shipment_fields["BILLTO_STATE"] = trim((string) $shipment->BILLTO_STATE);
	if( isset($shipment->BILLTO_ZIP) ) $shipment_fields["BILLTO_ZIP"] = trim((string) $shipment->BILLTO_ZIP);
	if( isset($shipment->BILLTO_PHONE) ) $shipment_fields["BILLTO_PHONE"] = trim((string) $shipment->BILLTO_PHONE);
	if( isset($shipment->BILLTO_EXT) ) $shipment_fields["BILLTO_EXT"] = trim((string) $shipment->BILLTO_EXT);
	if( isset($shipment->BILLTO_FAX) ) $shipment_fields["BILLTO_FAX"] = trim((string) $shipment->BILLTO_FAX);
	if( isset($shipment->BILLTO_CELL) ) $shipment_fields["BILLTO_CELL"] = trim((string) $shipment->BILLTO_CELL);
	if( isset($shipment->BILLTO_EMAIL) ) $shipment_fields["BILLTO_EMAIL"] = trim((string) $shipment->BILLTO_EMAIL);
	if( isset($shipment->BILLTO_CONTACT) ) $shipment_fields["BILLTO_CONTACT"] = trim((string) $shipment->BILLTO_CONTACT);
	
	if( isset($shipment->BILLTO_NAME) ) {
		$billto = $client_table->suggest($shipment_fields["BILLTO_NAME"], 'bill_to');
		if( is_array($billto) && count($billto) > 0 ) {
			foreach( $billto as $row ) {
				// Get customized bill_to name from contact info record if it exists
				if( isset($row['LABEL']) && isset($row['CITY']) && isset($row['STATE']) &&
					isset($shipment_fields["BILLTO_CITY"]) && isset($shipment_fields["BILLTO_STATE"]) &&
					strtoupper($shipment_fields["BILLTO_CITY"]) == strtoupper($row['CITY']) &&
					strtoupper($shipment_fields["BILLTO_STATE"]) == strtoupper($row['STATE']) &&
					strtoupper($shipment_fields["BILLTO_NAME"]) <> strtoupper($row['LABEL']) ) {

					log_event( "add_shipment: replace BILLTO_NAME = ".$shipment_fields["BILLTO_NAME"]." with ".$row['LABEL'] );
					$shipment_fields["BILLTO_NAME"] = $row['LABEL'];
					break;
				} else 
				// Replace bill_to information completely
				if( count($billto) == 1 && isset($row['LABEL']) && 
					isset($row['ADDRESS']) &&
					isset($row['CITY']) && isset($row['STATE']) && isset($row['ZIP_CODE']) &&
					isset($row['ADDR_VALID']) && $row['ADDR_VALID'] == 'valid' ) {
						
					log_event( "add_shipment: replace all fields for BILLTO_NAME = ".$shipment_fields["BILLTO_NAME"] );
					$shipment_fields["BILLTO_NAME"] = $row['LABEL'];
					$shipment_fields["BILLTO_ADDR1"] = $row['ADDRESS'];
					$shipment_fields["BILLTO_ADDR2"] = $row['ADDRESS2'];
					$shipment_fields["BILLTO_CITY"] = $row['CITY'];
					$shipment_fields["BILLTO_STATE"] = $row['STATE'];
					$shipment_fields["BILLTO_ZIP"] = $row['ZIP_CODE'];
					if( isset($row['PHONE_OFFICE']) ) $shipment_fields["BILLTO_PHONE"] =
						trim((string) $row['PHONE_OFFICE']);
					if( isset($row['PHONE_EXT']) ) $shipment_fields["BILLTO_EXT"] =
						trim((string) $row['PHONE_EXT']);
					if( isset($row['PHONE_FAX']) ) $shipment_fields["BILLTO_FAX"] =
						trim((string) $row['PHONE_FAX']);
					if( isset($row['PHONE_CELL']) ) $shipment_fields["BILLTO_CELL"] =
						trim((string) $row['PHONE_CELL']);
					if( isset($row['EMAIL']) ) $shipment_fields["BILLTO_EMAIL"] =
						trim((string) $row['EMAIL']);
					if( isset($row['CONTACT_NAME']) ) $shipment_fields["BILLTO_CONTACT"] =
						trim((string) $row['CONTACT_NAME']);
					break;
				}
			}
		}
	}
	$shipment_fields["BILLTO_CLIENT_CODE"] = $billto_code;

	// Lookup F&S to get shipper info
	$shipper = $client_table->suggest($sts_fns_name, 'shipper');
	if( is_array($shipper) && count($shipper) > 0 ) {
		$fns = $shipper[0];
		if( isset($fns["NAME"]) )			$shipment_fields["SHIPPER_NAME"] =	trim($fns["NAME"]);
		if( isset($fns["ADDRESS"]) )		$shipment_fields["SHIPPER_ADDR1"] =	trim($fns["ADDRESS"]);
		if( isset($fns["ADDRESS2"]) )		$shipment_fields["SHIPPER_ADDR2"] =	trim($fns["ADDRESS2"]);
		if( isset($fns["CITY"]) )			$shipment_fields["SHIPPER_CITY"] =	trim($fns["CITY"]);
		if( isset($fns["STATE"]) )			$shipment_fields["SHIPPER_STATE"] =	trim($fns["STATE"]);
		if( isset($fns["ZIP_CODE"]) )		$shipment_fields["SHIPPER_ZIP"] =	trim($fns["ZIP_CODE"]);
		if( isset($fns["PHONE_OFFICE"]) )	$shipment_fields["SHIPPER_PHONE"] = trim($fns["PHONE_OFFICE"]);
		if( isset($fns["PHONE_EXT"]) )		$shipment_fields["SHIPPER_EXT"] = 	trim($fns["PHONE_EXT"]);
		if( isset($fns["PHONE_CELL"]) )		$shipment_fields["SHIPPER_CELL"] = 	trim($fns["PHONE_CELL"]);
		if( isset($fns["PHONE_FAX"]) )		$shipment_fields["SHIPPER_FAX"] = 	trim($fns["PHONE_FAX"]);
		if( isset($fns["EMAIL"]) )			$shipment_fields["SHIPPER_EMAIL"] = trim($fns["EMAIL"]);
		
		if( isset($fns["SALES_PERSON"]) )	$shipment_fields["SALES_PERSON"] = trim($fns["SALES_PERSON"]);
		if( isset($fns["TERMINAL_ZONE"]) )	$shipment_fields["SHIPPER_TERMINAL"] = trim($fns["TERMINAL_ZONE"]);
	} else
		log_event( "add_shipment: unable to lookup F&S" );

	if( isset($shipment->CONS_NAME) ) $shipment_fields["CONS_NAME"] = trim((string) $shipment->CONS_NAME);
	if( isset($shipment->CONS_ADDR1) ) $shipment_fields["CONS_ADDR1"] = trim((string) $shipment->CONS_ADDR1);
	if( isset($shipment->CONS_ADDR2) ) $shipment_fields["CONS_ADDR2"] = trim((string) $shipment->CONS_ADDR2);
	if( isset($shipment->CONS_CITY) ) $shipment_fields["CONS_CITY"] = trim((string) $shipment->CONS_CITY);
	if( isset($shipment->CONS_STATE) ) $shipment_fields["CONS_STATE"] = trim((string) $shipment->CONS_STATE);
	if( isset($shipment->CONS_ZIP) ) $shipment_fields["CONS_ZIP"] = trim((string) $shipment->CONS_ZIP);
	if( isset($shipment->CONS_PHONE) ) $shipment_fields["CONS_PHONE"] = trim((string) $shipment->CONS_PHONE);
	if( isset($shipment->CONS_EXT) ) $shipment_fields["CONS_EXT"] = trim((string) $shipment->CONS_EXT);
	if( isset($shipment->CONS_FAX) ) $shipment_fields["CONS_FAX"] = trim((string) $shipment->CONS_FAX);
	if( isset($shipment->CONS_CELL) ) $shipment_fields["CONS_CELL"] = trim((string) $shipment->CONS_CELL);
	if( isset($shipment->CONS_EMAIL) ) $shipment_fields["CONS_EMAIL"] = trim((string) $shipment->CONS_EMAIL);
	if( isset($shipment->CONS_CONTACT) ) $shipment_fields["CONS_CONTACT"] = trim((string) $shipment->CONS_CONTACT);
	
	//log_event( "add_shipment: CONS_NAME = ".$shipment->CONS_NAME." CONS_CITY = ".$shipment->CONS_CITY." CONS_STATE = ".$shipment->CONS_STATE );
	
	if( isset($shipment->CONS_NAME) ) {
		$consignee = $client_table->suggest($shipment_fields["CONS_NAME"], 'consignee');
		if( is_array($consignee) && count($consignee) > 0 ) {
			foreach( $consignee as $row ) {
				// Get customized consignee name from contact info record if it exists
				if( isset($row['LABEL']) && isset($row['CITY']) && isset($row['STATE']) &&
					isset($shipment_fields["CONS_CITY"]) && isset($shipment_fields["CONS_STATE"]) &&
					strtoupper($shipment_fields["CONS_CITY"]) == strtoupper($row['CITY']) &&
					strtoupper($shipment_fields["CONS_STATE"]) == strtoupper($row['STATE']) &&
					strtoupper($shipment_fields["CONS_NAME"]) <> strtoupper($row['LABEL']) ) {

					log_event( "add_shipment: replace CONS_NAME = ".$shipment_fields["CONS_NAME"]." with ".$row['LABEL'] );
					$shipment_fields["CONS_NAME"] = $row['LABEL'];
					break;
				} else 
				// Replace consignee information completely
				if( count($consignee) == 1 && isset($row['LABEL']) && 
					isset($row['ADDRESS']) &&
					isset($row['CITY']) && isset($row['STATE']) && isset($row['ZIP_CODE']) &&
					isset($row['ADDR_VALID']) && $row['ADDR_VALID'] == 'valid' ) {
						
					log_event( "add_shipment: replace all fields for CONS_NAME = ".$shipment_fields["CONS_NAME"] );
					$shipment_fields["CONS_NAME"] = $row['LABEL'];
					$shipment_fields["CONS_ADDR1"] = $row['ADDRESS'];
					$shipment_fields["CONS_ADDR2"] = $row['ADDRESS2'];
					$shipment_fields["CONS_CITY"] = $row['CITY'];
					$shipment_fields["CONS_STATE"] = $row['STATE'];
					$shipment_fields["CONS_ZIP"] = $row['ZIP_CODE'];
					if( isset($row['PHONE_OFFICE']) ) $shipment_fields["CONS_PHONE"] =
						trim((string) $row['PHONE_OFFICE']);
					if( isset($row['PHONE_EXT']) ) $shipment_fields["CONS_EXT"] =
						trim((string) $row['PHONE_EXT']);
					if( isset($row['PHONE_FAX']) ) $shipment_fields["CONS_FAX"] =
						trim((string) $row['PHONE_FAX']);
					if( isset($row['PHONE_CELL']) ) $shipment_fields["CONS_CELL"] =
						trim((string) $row['PHONE_CELL']);
					if( isset($row['EMAIL']) ) $shipment_fields["CONS_EMAIL"] =
						trim((string) $row['EMAIL']);
					if( isset($row['CONTACT_NAME']) ) $shipment_fields["CONS_CONTACT"] =
						trim((string) $row['CONTACT_NAME']);
					break;
				}
			}
		}
	}

	if( isset($shipment->PALLETS) ) $shipment_fields["PALLETS"] = intval($shipment->PALLETS);
	if( isset($shipment->PIECES) ) $shipment_fields["PIECES"] = intval($shipment->PIECES);
	if( isset($shipment->LENGTH) ) $shipment_fields["LENGTH"] = floatval($shipment->LENGTH);
	if( isset($shipment->WEIGHT) ) $shipment_fields["WEIGHT"] = floatval($shipment->WEIGHT);
	if( isset($shipment->DANGEROUS_GOODS) ) $shipment_fields["DANGEROUS_GOODS"] = intval($shipment->DANGEROUS_GOODS);
	if( isset($shipment->TOTAL_CHARGES) ) $shipment_fields["TOTAL_CHARGES"] = floatval($shipment->TOTAL_CHARGES);

	if( isset($shipment->PICKUP_BY) ) {
		$shipment_fields["PICKUP_DATE"] = date("Y-m-d", strtotime((string) $shipment->PICKUP_BY));
		$shipment_fields["PICKUP_TIME1"] = date("Hi", strtotime((string) $shipment->PICKUP_BY));
		if( isset($shipment->PICKUP_BY_END) && $shipment->PICKUP_BY_END <> $shipment->PICKUP_BY ) {
			$shipment_fields["PICKUP_TIME2"] = date("Hi", strtotime((string) $shipment->PICKUP_BY_END));
			$shipment_fields["PICKUP_TIME_OPTION"] = 'Between';
		} else {
			$shipment_fields["PICKUP_TIME_OPTION"] = 'At';
		}
		$shipment_fields["DELIVER_DATE"] = date("Y-m-d", strtotime((string) $shipment->PICKUP_BY. ' + 1 days'));
		$shipment_fields["DELIVER_TIME1"] = date("Hi", strtotime((string) $shipment->PICKUP_BY));
		$shipment_fields["DELIVER_TIME_OPTION"] = 'At';
	}

	/*
	if( isset($shipment->DELIVER_BY) ) {
		$shipment_fields["DELIVER_DATE"] = date("Y-m-d", strtotime((string) $shipment->DELIVER_BY));
		$shipment_fields["DELIVER_TIME1"] = date("Hi", strtotime((string) $shipment->DELIVER_BY));
		if( isset($shipment->DELIVER_BY_END) && $shipment->DELIVER_BY_END <> $shipment->DELIVER_BY ) {
			$shipment_fields["DELIVER_TIME2"] = date("Hi", strtotime((string) $shipment->DELIVER_BY_END));
			$shipment_fields["DELIVER_TIME_OPTION"] = 'Between';
		} else {
			$shipment_fields["DELIVER_TIME_OPTION"] = 'At';
		}
	}
	*/

	if( isset($shipment->PO_NUMBER) ) $shipment_fields["PO_NUMBER"] = trim((string) $shipment->PO_NUMBER);
	if( isset($shipment->BOL_NUMBER) ) $shipment_fields["BOL_NUMBER"] = trim((string) $shipment->BOL_NUMBER);
	if( isset($shipment->ST_NUMBER) ) $shipment_fields["ST_NUMBER"] = trim((string) $shipment->ST_NUMBER);
	if( isset($shipment->FS_NUMBER) ) $shipment_fields["FS_NUMBER"] = trim((string) $shipment->FS_NUMBER);
	if( isset($shipment->REF_NUMBER) ) $shipment_fields["REF_NUMBER"] = trim((string) $shipment->REF_NUMBER);
	if( isset($shipment->PICKUP_NUMBER) ) $shipment_fields["PICKUP_NUMBER"] = trim((string) $shipment->PICKUP_NUMBER);
	if( isset($shipment->CUSTOMER_NUMBER) ) $shipment_fields["CUSTOMER_NUMBER"] = trim((string) $shipment->CUSTOMER_NUMBER);
	if( isset($shipment->PICKUP_APPT) ) $shipment_fields["PICKUP_APPT"] = trim((string) $shipment->PICKUP_APPT);
	if( isset($shipment->DIRECTION) ) $shipment_fields["DIRECTION"] = trim((string) $shipment->DIRECTION);
	
	$result = $shipment_table->update($shipment_code,  $shipment_fields);
	log_event( "add_shipment: added shipment ".$shipment_code." result = ".$result." SYNERGY_USER = ".$_SESSION['EXT_USER_CODE'] );

	if( $sts_debug ) {
		echo "<p>add_shipment: DD shipment_fields = </p>
		<pre>";
		var_dump($shipment_fields);
		echo "</pre>";
	}
	
	$details = $shipment->DETAILS->DETAIL;
	if( $sts_debug ) echo "<p>add_shipment: details = ".count($details)."</p>";
	
	foreach( $details as $detail ) {
		add_detail( $shipment_code, $detail );
	}
	
	// These fields get rolled up from the details, this is to restore them
	$shipment_fields = array();
	if( isset($shipment->PALLETS) ) $shipment_fields["PALLETS"] = intval($shipment->PALLETS);
	if( isset($shipment->PIECES) ) $shipment_fields["PIECES"] = intval($shipment->PIECES);
	if( isset($shipment->LENGTH) ) $shipment_fields["LENGTH"] = floatval($shipment->LENGTH);
	if( isset($shipment->WEIGHT) ) $shipment_fields["WEIGHT"] = floatval($shipment->WEIGHT);
	$result = $shipment_table->update($shipment_code,  $shipment_fields);

	$check = $shipment_table->fetch_rows("SHIPMENT_CODE = '".$shipment_code."'",
		"(SELECT STATUS_STATE from EXP_STATUS_CODES X 
		WHERE X.STATUS_CODES_CODE = EXP_SHIPMENT.CURRENT_STATUS 
		LIMIT 0 , 1) AS CURRENT_STATUS");
	
	// Make it ready for dispatch
	if( is_array($check) && count($check) > 0 && $check[0]["CURRENT_STATUS"] == 'Entered' ) {
		$result = $shipment_table->ready_dispatch( $shipment_code );

		if( ! $result ) {
			if( $sts_debug ) echo "<p>add_shipment: ready_dispatch failed for shipment $shipment_code (F&S# ".trim((string) $shipment->FS_NUMBER).")</p>";
			log_event( "add_shipment: ready_dispatch failed for shipment $shipment_code (F&S# ".trim((string) $shipment->FS_NUMBER).") status = ".$check[0]["CURRENT_STATUS"] );
			echo "<pre>";
			var_dump($shipment);
			echo "</pre>";
		} else {
			if( $sts_debug ) echo "<p>add_shipment: ready_dispatch done for shipment $shipment_code</p>";
			log_event( "add_shipment: ready_dispatch done for shipment $shipment_code" );
		}
		
	} else {
		if( $sts_debug ) echo "<p>add_shipment: ready_dispatch not done for shipment $shipment_code, status = ".$check[0]["CURRENT_STATUS"]."</p>";
		log_event( "add_shipment: ready_dispatch not done for shipment $shipment_code, status = ".$check[0]["CURRENT_STATUS"] );
	}
}

function update_shipment( $shipment_code, $shipment ) {
	global $sts_debug, $client_table, $detail_table, $shipment_table;
	
	if( $sts_debug ) echo "<p>update_shipment: shipment $shipment_code</p>";
	
	if( $sts_debug ) {
		echo "<pre>";
		var_dump($shipment);
		echo "</pre>";
	}
		
	$shipment_fields = array( "SHIPMENT_TYPE" => 'prepaid', "PRIORITY" => 'regular',
		"SYNERGY_IMPORT" => 'TRUE' );
	
	//Check every field, they might not be there
	if( isset($shipment->BILLTO_NAME) ) $shipment_fields["BILLTO_NAME"] = trim((string) $shipment->BILLTO_NAME);
	if( isset($shipment->BILLTO_ADDR1) ) $shipment_fields["BILLTO_ADDR1"] = trim((string) $shipment->BILLTO_ADDR1);
	if( isset($shipment->BILLTO_ADDR2) ) $shipment_fields["BILLTO_ADDR2"] = trim((string) $shipment->BILLTO_ADDR2);
	if( isset($shipment->BILLTO_CITY) ) $shipment_fields["BILLTO_CITY"] = trim((string) $shipment->BILLTO_CITY);
	if( isset($shipment->BILLTO_STATE) ) $shipment_fields["BILLTO_STATE"] = trim((string) $shipment->BILLTO_STATE);
	if( isset($shipment->BILLTO_ZIP) ) $shipment_fields["BILLTO_ZIP"] = trim((string) $shipment->BILLTO_ZIP);
	if( isset($shipment->BILLTO_PHONE) ) $shipment_fields["BILLTO_PHONE"] = trim((string) $shipment->BILLTO_PHONE);
	if( isset($shipment->BILLTO_EXT) ) $shipment_fields["BILLTO_EXT"] = trim((string) $shipment->BILLTO_EXT);
	if( isset($shipment->BILLTO_FAX) ) $shipment_fields["BILLTO_FAX"] = trim((string) $shipment->BILLTO_FAX);
	if( isset($shipment->BILLTO_CELL) ) $shipment_fields["BILLTO_CELL"] = trim((string) $shipment->BILLTO_CELL);
	if( isset($shipment->BILLTO_EMAIL) ) $shipment_fields["BILLTO_EMAIL"] = trim((string) $shipment->BILLTO_EMAIL);
	if( isset($shipment->BILLTO_CONTACT) ) $shipment_fields["BILLTO_CONTACT"] = trim((string) $shipment->BILLTO_CONTACT);
	
	if( isset($shipment->BILLTO_NAME) ) {
		$billto = $client_table->suggest($shipment_fields["BILLTO_NAME"], 'bill_to');
		if( is_array($billto) && count($billto) > 0 ) {
			foreach( $billto as $row ) {
				// Get customized bill_to name from contact info record if it exists
				if( isset($row['LABEL']) && isset($row['CITY']) && isset($row['STATE']) &&
					isset($shipment_fields["BILLTO_CITY"]) && isset($shipment_fields["BILLTO_STATE"]) &&
					strtoupper($shipment_fields["BILLTO_CITY"]) == strtoupper($row['CITY']) &&
					strtoupper($shipment_fields["BILLTO_STATE"]) == strtoupper($row['STATE']) &&
					strtoupper($shipment_fields["BILLTO_NAME"]) <> strtoupper($row['LABEL']) ) {

					log_event( "add_shipment: replace BILLTO_NAME = ".$shipment_fields["BILLTO_NAME"]." with ".$row['LABEL'] );
					$shipment_fields["BILLTO_NAME"] = $row['LABEL'];
					break;
				} else 
				// Replace bill_to information completely
				if( count($billto) == 1 && isset($row['LABEL']) && 
					isset($row['ADDRESS']) &&
					isset($row['CITY']) && isset($row['STATE']) && isset($row['ZIP_CODE']) &&
					isset($row['ADDR_VALID']) && $row['ADDR_VALID'] == 'valid' ) {
						
					log_event( "add_shipment: replace all fields for BILLTO_NAME = ".$shipment_fields["BILLTO_NAME"] );
					$shipment_fields["BILLTO_NAME"] = $row['LABEL'];
					$shipment_fields["BILLTO_ADDR1"] = $row['ADDRESS'];
					$shipment_fields["BILLTO_ADDR2"] = $row['ADDRESS2'];
					$shipment_fields["BILLTO_CITY"] = $row['CITY'];
					$shipment_fields["BILLTO_STATE"] = $row['STATE'];
					$shipment_fields["BILLTO_ZIP"] = $row['ZIP_CODE'];
					if( isset($row['PHONE_OFFICE']) ) $shipment_fields["BILLTO_PHONE"] =
						trim((string) $row['PHONE_OFFICE']);
					if( isset($row['PHONE_EXT']) ) $shipment_fields["BILLTO_EXT"] =
						trim((string) $row['PHONE_EXT']);
					if( isset($row['PHONE_FAX']) ) $shipment_fields["BILLTO_FAX"] =
						trim((string) $row['PHONE_FAX']);
					if( isset($row['PHONE_CELL']) ) $shipment_fields["BILLTO_CELL"] =
						trim((string) $row['PHONE_CELL']);
					if( isset($row['EMAIL']) ) $shipment_fields["BILLTO_EMAIL"] =
						trim((string) $row['EMAIL']);
					if( isset($row['CONTACT_NAME']) ) $shipment_fields["BILLTO_CONTACT"] =
						trim((string) $row['CONTACT_NAME']);
					break;
				}
			}
		}
	}

	// Lookup F&S to get shipper info
	$shipper = $client_table->suggest('F&S', 'shipper');
	if( is_array($shipper) && count($shipper) > 0 ) {
		$fns = $shipper[0];
		if( isset($fns["NAME"]) )			$shipment_fields["SHIPPER_NAME"] =	trim($fns["NAME"]);
		if( isset($fns["ADDRESS"]) )		$shipment_fields["SHIPPER_ADDR1"] =	trim($fns["ADDRESS"]);
		if( isset($fns["ADDRESS2"]) )		$shipment_fields["SHIPPER_ADDR2"] =	trim($fns["ADDRESS2"]);
		if( isset($fns["CITY"]) )			$shipment_fields["SHIPPER_CITY"] =	trim($fns["CITY"]);
		if( isset($fns["STATE"]) )			$shipment_fields["SHIPPER_STATE"] =	trim($fns["STATE"]);
		if( isset($fns["ZIP_CODE"]) )		$shipment_fields["SHIPPER_ZIP"] =	trim($fns["ZIP_CODE"]);
		if( isset($fns["PHONE_OFFICE"]) )	$shipment_fields["SHIPPER_PHONE"] = trim($fns["PHONE_OFFICE"]);
		if( isset($fns["PHONE_EXT"]) )		$shipment_fields["SHIPPER_EXT"] = 	trim($fns["PHONE_EXT"]);
		if( isset($fns["PHONE_CELL"]) )		$shipment_fields["SHIPPER_CELL"] = 	trim($fns["PHONE_CELL"]);
		if( isset($fns["PHONE_FAX"]) )		$shipment_fields["SHIPPER_FAX"] = 	trim($fns["PHONE_FAX"]);
		if( isset($fns["EMAIL"]) )			$shipment_fields["SHIPPER_EMAIL"] = trim($fns["EMAIL"]);
		
		if( isset($fns["SALES_PERSON"]) )	$shipment_fields["SALES_PERSON"] = trim($fns["SALES_PERSON"]);
		if( isset($fns["TERMINAL_ZONE"]) )	$shipment_fields["SHIPPER_TERMINAL"] = trim($fns["TERMINAL_ZONE"]);
	} else
		log_event( "update_shipment: unable to lookup F&S" );

	if( isset($shipment->CONS_NAME) ) $shipment_fields["CONS_NAME"] = trim((string) $shipment->CONS_NAME);
	if( isset($shipment->CONS_ADDR1) ) $shipment_fields["CONS_ADDR1"] = trim((string) $shipment->CONS_ADDR1);
	if( isset($shipment->CONS_ADDR2) ) $shipment_fields["CONS_ADDR2"] = trim((string) $shipment->CONS_ADDR2);
	if( isset($shipment->CONS_CITY) ) $shipment_fields["CONS_CITY"] = trim((string) $shipment->CONS_CITY);
	if( isset($shipment->CONS_STATE) ) $shipment_fields["CONS_STATE"] = trim((string) $shipment->CONS_STATE);
	if( isset($shipment->CONS_ZIP) ) $shipment_fields["CONS_ZIP"] = trim((string) $shipment->CONS_ZIP);
	if( isset($shipment->CONS_PHONE) ) $shipment_fields["CONS_PHONE"] = trim((string) $shipment->CONS_PHONE);
	if( isset($shipment->CONS_EXT) ) $shipment_fields["CONS_EXT"] = trim((string) $shipment->CONS_EXT);
	if( isset($shipment->CONS_FAX) ) $shipment_fields["CONS_FAX"] = trim((string) $shipment->CONS_FAX);
	if( isset($shipment->CONS_CELL) ) $shipment_fields["CONS_CELL"] = trim((string) $shipment->CONS_CELL);
	if( isset($shipment->CONS_EMAIL) ) $shipment_fields["CONS_EMAIL"] = trim((string) $shipment->CONS_EMAIL);
	if( isset($shipment->CONS_CONTACT) ) $shipment_fields["CONS_CONTACT"] = trim((string) $shipment->CONS_CONTACT);

	if( isset($shipment->CONS_NAME) ) {
		$consignee = $client_table->suggest($shipment_fields["CONS_NAME"], 'consignee');
		if( is_array($consignee) && count($consignee) > 0 ) {
			foreach( $consignee as $row ) {
				// Get customized consignee name from contact info record if it exists
				if( isset($row['LABEL']) && isset($row['CITY']) && isset($row['STATE']) &&
					isset($shipment_fields["CONS_CITY"]) && isset($shipment_fields["CONS_STATE"]) &&
					strtoupper($shipment_fields["CONS_CITY"]) == strtoupper($row['CITY']) &&
					strtoupper($shipment_fields["CONS_STATE"]) == strtoupper($row['STATE']) &&
					strtoupper($shipment_fields["CONS_NAME"]) <> strtoupper($row['LABEL']) ) {

					log_event( "add_shipment: replace CONS_NAME = ".$shipment_fields["CONS_NAME"]." with ".$row['LABEL'] );
					$shipment_fields["CONS_NAME"] = $row['LABEL'];
					break;
				} else 
				// Replace consignee information completely
				if( count($consignee) == 1 && isset($row['LABEL']) && 
					isset($row['ADDRESS']) &&
					isset($row['CITY']) && isset($row['STATE']) && isset($row['ZIP_CODE']) &&
					isset($row['ADDR_VALID']) && $row['ADDR_VALID'] == 'valid' ) {
						
					log_event( "add_shipment: replace all fields for CONS_NAME = ".$shipment_fields["CONS_NAME"] );
					$shipment_fields["CONS_NAME"] = $row['LABEL'];
					$shipment_fields["CONS_ADDR1"] = $row['ADDRESS'];
					$shipment_fields["CONS_ADDR2"] = $row['ADDRESS2'];
					$shipment_fields["CONS_CITY"] = $row['CITY'];
					$shipment_fields["CONS_STATE"] = $row['STATE'];
					$shipment_fields["CONS_ZIP"] = $row['ZIP_CODE'];
					if( isset($row['PHONE_OFFICE']) ) $shipment_fields["CONS_PHONE"] =
						trim((string) $row['PHONE_OFFICE']);
					if( isset($row['PHONE_EXT']) ) $shipment_fields["CONS_EXT"] =
						trim((string) $row['PHONE_EXT']);
					if( isset($row['PHONE_FAX']) ) $shipment_fields["CONS_FAX"] =
						trim((string) $row['PHONE_FAX']);
					if( isset($row['PHONE_CELL']) ) $shipment_fields["CONS_CELL"] =
						trim((string) $row['PHONE_CELL']);
					if( isset($row['EMAIL']) ) $shipment_fields["CONS_EMAIL"] =
						trim((string) $row['EMAIL']);
					if( isset($row['CONTACT_NAME']) ) $shipment_fields["CONS_CONTACT"] =
						trim((string) $row['CONTACT_NAME']);
					break;
				}
			}
		}
	}

	if( isset($shipment->PALLETS) ) $shipment_fields["PALLETS"] = intval($shipment->PALLETS);
	if( isset($shipment->PIECES) ) $shipment_fields["PIECES"] = intval($shipment->PIECES);
	if( isset($shipment->LENGTH) ) $shipment_fields["LENGTH"] = floatval($shipment->LENGTH);
	if( isset($shipment->WEIGHT) ) $shipment_fields["WEIGHT"] = floatval($shipment->WEIGHT);
	if( isset($shipment->DANGEROUS_GOODS) ) $shipment_fields["DANGEROUS_GOODS"] = intval($shipment->DANGEROUS_GOODS);
	if( isset($shipment->TOTAL_CHARGES) ) $shipment_fields["TOTAL_CHARGES"] = floatval($shipment->TOTAL_CHARGES);

	if( isset($shipment->PICKUP_BY) ) {
		$shipment_fields["PICKUP_DATE"] = date("Y-m-d", strtotime((string) $shipment->PICKUP_BY));
		$shipment_fields["PICKUP_TIME1"] = date("Hi", strtotime((string) $shipment->PICKUP_BY));
		if( isset($shipment->PICKUP_BY_END) && $shipment->PICKUP_BY_END <> $shipment->PICKUP_BY ) {
			$shipment_fields["PICKUP_TIME2"] = date("Hi", strtotime((string) $shipment->PICKUP_BY_END));
			$shipment_fields["PICKUP_TIME_OPTION"] = 'Between';
		} else {
			$shipment_fields["PICKUP_TIME_OPTION"] = 'At';
		}
		$shipment_fields["DELIVER_DATE"] = date("Y-m-d", strtotime((string) $shipment->PICKUP_BY. ' + 1 days'));
		$shipment_fields["DELIVER_TIME1"] = date("Hi", strtotime((string) $shipment->PICKUP_BY));
		$shipment_fields["DELIVER_TIME_OPTION"] = 'At';
	}

	if( isset($shipment->DELIVER_BY) ) {
		$shipment_fields["DELIVER_DATE"] = date("Y-m-d", strtotime((string) $shipment->DELIVER_BY));
		$shipment_fields["DELIVER_TIME1"] = date("Hi", strtotime((string) $shipment->DELIVER_BY));
		if( isset($shipment->DELIVER_BY_END) && $shipment->DELIVER_BY_END <> $shipment->DELIVER_BY ) {
			$shipment_fields["DELIVER_TIME2"] = date("Hi", strtotime((string) $shipment->DELIVER_BY_END));
			$shipment_fields["DELIVER_TIME_OPTION"] = 'Between';
		} else {
			$shipment_fields["DELIVER_TIME_OPTION"] = 'At';
		}
	}

	if( isset($shipment->PO_NUMBER) ) $shipment_fields["PO_NUMBER"] = trim((string) $shipment->PO_NUMBER);
	if( isset($shipment->BOL_NUMBER) ) $shipment_fields["BOL_NUMBER"] = trim((string) $shipment->BOL_NUMBER);
	if( isset($shipment->ST_NUMBER) ) $shipment_fields["ST_NUMBER"] = trim((string) $shipment->ST_NUMBER);
	if( isset($shipment->FS_NUMBER) ) $shipment_fields["FS_NUMBER"] = trim((string) $shipment->FS_NUMBER);
	if( isset($shipment->REF_NUMBER) ) $shipment_fields["REF_NUMBER"] = trim((string) $shipment->REF_NUMBER);
	if( isset($shipment->PICKUP_NUMBER) ) $shipment_fields["PICKUP_NUMBER"] = trim((string) $shipment->PICKUP_NUMBER);
	if( isset($shipment->CUSTOMER_NUMBER) ) $shipment_fields["CUSTOMER_NUMBER"] = trim((string) $shipment->CUSTOMER_NUMBER);
	if( isset($shipment->PICKUP_APPT) ) $shipment_fields["PICKUP_APPT"] = trim((string) $shipment->PICKUP_APPT);
	if( isset($shipment->DIRECTION) ) $shipment_fields["DIRECTION"] = trim((string) $shipment->DIRECTION);
	

	$result = $shipment_table->update($shipment_code,  $shipment_fields);
	if( $sts_debug ) echo "<p>update_shipment: update shipment $shipment_code returns ".
		($result ? 'true' : 'false')."</p>";
	log_event( "update_shipment: update shipment $shipment_code returns ".
		($result ? 'true' : 'false')." SYNERGY_USER = ".$_SESSION['EXT_USER_CODE'] );

	if( $sts_debug ) {
		echo "<pre>";
		var_dump($shipment_fields);
		echo "</pre>";
	}
	
	$details = $shipment->DETAILS->DETAIL;
	if( $sts_debug ) echo "<p>update_shipment: details = ".count($details)."</p>";
	
	$result = $detail_table->delete_row( "SHIPMENT_CODE = ".$shipment_code );
	if( ! $result ) {
		if( $sts_debug ) echo "<p>update_shipment: delete_row failed for shipment $shipment_code</p>";
	}
	
	foreach( $details as $detail ) {
		add_detail( $shipment_code, $detail );
	}
	
	// These fields get rolled up from the details, this is to restore them
	$shipment_fields = array();
	if( isset($shipment->PALLETS) ) $shipment_fields["PALLETS"] = intval($shipment->PALLETS);
	if( isset($shipment->PIECES) ) $shipment_fields["PIECES"] = intval($shipment->PIECES);
	if( isset($shipment->LENGTH) ) $shipment_fields["LENGTH"] = floatval($shipment->LENGTH);
	if( isset($shipment->WEIGHT) ) $shipment_fields["WEIGHT"] = floatval($shipment->WEIGHT);
	$result = $shipment_table->update($shipment_code,  $shipment_fields);

	$check = $shipment_table->fetch_rows("SHIPMENT_CODE = '".$shipment_code."'",
		"(SELECT STATUS_STATE from EXP_STATUS_CODES X 
		WHERE X.STATUS_CODES_CODE = EXP_SHIPMENT.CURRENT_STATUS 
		LIMIT 0 , 1) AS CURRENT_STATUS");
	
	// Make it ready for dispatch
	if( is_array($check) && count($check) > 0 && $check[0]["CURRENT_STATUS"] == 'Entered' ) {
		$result = $shipment_table->ready_dispatch( $shipment_code );
	
		if( ! $result ) {
			log_event( "update_shipment: ready_dispatch failed for shipment $shipment_code (F&S# ".trim((string) $shipment->FS_NUMBER).") status = ".$check[0]["CURRENT_STATUS"] );
			if( $sts_debug ) {
				echo "<p>update_shipment: ready_dispatch failed for shipment $shipment_code</p>";
				echo "<pre>";
				var_dump($shipment);
				echo "</pre>";
			}
		} else {
			if( $sts_debug ) echo "<p>update_shipment: ready_dispatch done for shipment $shipment_code</p>";
			log_event( "update_shipment: ready_dispatch done for shipment $shipment_code" );
		}
	} else {
		if( $sts_debug ) echo "<p>update_shipment: ready_dispatch not done for shipment $shipment_code, status = ".$check[0]["CURRENT_STATUS"]."</p>";
		log_event( "update_shipment: ready_dispatch not done for shipment $shipment_code, status = ".$check[0]["CURRENT_STATUS"] );
	}
}


if( (isset($_GET['PW']) && $_GET['PW'] == 'Robert') || php_sapi_name() == 'cli' ) {

	$_SESSION['EXT_USER_CODE'] = $user_table->special_user( SYNERGY_USER );
	if( $sts_debug ) echo "<p><stong>SYNERGY_USER = ".$_SESSION['EXT_USER_CODE']."</stong></p>";
		
	$setting_table = sts_setting::getInstance( $exspeedite_db, $sts_debug );
	$sts_import_directory = $setting_table->get( 'api', 'SYNERGY_IMPORT_DIR' );
	if( substr($sts_import_directory, -1) <> '/' )	// Make sure it ends with a slash
		$sts_import_directory .= '/';
	
	// allow log file to be absolute or relative
	// absolute could be /path or C:/path, relative could be log/path
	$sts_log_directory = $setting_table->get( 'api', 'SYNERGY_LOG_FILE' );
	if( isset($sts_log_directory) && $sts_log_directory <> '' && 
		$sts_log_directory[0] <> '/' && $sts_log_directory[0] <> '\\' 
		&& $sts_log_directory[1] <> ':' )
		$sts_log_directory = $sts_crm_dir.$sts_log_directory;
	
	if( substr($sts_import_directory, -1) <> '/' )	// Make sure it ends with a slash
		$sts_import_directory .= '/';
	$files = scandir( $sts_import_directory );
	foreach( $files as $file_name ) {
		if( $file_name <> '' && ! preg_match('/^.*\.xml$/i', $file_name)) continue;
		if( $sts_debug ) echo "<p><stong>file = $file_name</stong></p>";
		
		$file = $sts_import_directory.$file_name;
		$xml = simplexml_load_file($file);
		
		if( $xml ) {
			$shipments = $xml->SHIPMENTS->SHIPMENT;
			
			if( $sts_debug ) echo "<p>Shipments: ".count($shipments)."</p>";
			log_event( "import_synergy: File: $file_name Shipments: ".count($shipments)." SYNERGY_USER = ".$_SESSION['EXT_USER_CODE'] );
			
			$count_shipments = 0;
			
			foreach( $shipments as $shipment ) {
				$check = $shipment_table->fetch_rows("FS_NUMBER = '".trim($shipment->FS_NUMBER)."'",$shipment_table->primary_key.", (SELECT BEHAVIOR from EXP_STATUS_CODES X WHERE X.STATUS_CODES_CODE = EXP_SHIPMENT.CURRENT_STATUS  LIMIT 0 , 1) AS CURRENT_STATUS" );
				$is_new = is_array($check) && count($check) == 0;
				log_event( "import_synergy: ".++$count_shipments.": shipment = ".$shipment->FS_NUMBER." (".($is_new ? "new" : "update").")" );
				if( $sts_debug ) echo "<p>".$count_shipments.": shipment = ".$shipment->FS_NUMBER." (".($is_new ? "new" : "update").")</p>";
				if( $is_new ) {
					$billto_code = add_client( trim($shipment->BILLTO_NAME), trim($shipment->BILLTO_ADDR1), 
						trim($shipment->BILLTO_ADDR2), trim($shipment->BILLTO_CITY), 
						trim($shipment->BILLTO_STATE), trim($shipment->BILLTO_ZIP), 
						trim($shipment->BILLTO_PHONE), trim($shipment->BILLTO_FAX), 
						trim($shipment->BILLTO_EMAIL), 'BILLTO' );
						
					$cons_code = add_client( trim($shipment->CONS_NAME), trim($shipment->CONS_ADDR1), 
						trim($shipment->CONS_ADDR2), trim($shipment->CONS_CITY), 
						trim($shipment->CONS_STATE), trim($shipment->CONS_ZIP), 
						trim($shipment->CONS_PHONE), trim($shipment->CONS_FAX), 
						trim($shipment->CONS_EMAIL), 'CONS' );
					
					$shipment_code = add_shipment( $shipment, $billto_code );
					
				} else if( isset($check[0]['CURRENT_STATUS']) && 
					! in_array($check[0]['CURRENT_STATUS'], array('approved', 'billed', 'cancel')) ) {
					$shipment_code = $check[0][$shipment_table->primary_key];
					update_shipment( $shipment_code, $shipment );
				}
				
				//echo "<pre>";
				//print_r($shipment);
				//echo "</pre>";
				//if( $count_shipments > 10 ) break;
	
			}
			
			// Option A: Delete file after import.
			//if( ! $sts_debug ) unlink($file);
			
			// Option B: Archive the file after import.
			if( ! $sts_debug ) {
				$new_file = $sts_import_directory."archive/".date('B').$file_name;
				//echo "<p>$new_file</p>";
				$ans = rename($file, $new_file);
				log_event( "import_synergy: file $file_name moved to $new_file" );
			}
		} else {
			log_event( "import_synergy: file $file_name failed XML parse." );			
			if( $sts_debug ) echo "<p>import_synergy: file $file_name failed XML parse.</p>";
		}
	}

	log_event( "import_synergy: Import completed.\n" );
	
	//if( $sts_debug ) echo "<p>result = ".($result ? 'true' : 'false '.$shipment_table->error())."</p>";
}
