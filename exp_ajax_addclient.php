<?php 

// $Id: exp_ajax_addclient.php 4697 2022-03-09 23:02:23Z duncan $
// AJAX add client (shipper/consignee)

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
$my_session->access_check( $sts_table_access[CLIENT_TABLE] );	// Make sure we should be here

require_once( "include/sts_client_class.php" );
require_once( "include/sts_contact_info_class.php" );
require_once( "include/sts_shipment_class.php" );
require_once( "include/sts_user_log_class.php" );

$client_table = sts_client::getInstance($exspeedite_db, $sts_debug);
$contact_info_table = sts_contact_info::getInstance($exspeedite_db, $sts_debug);
$shipment_table = sts_shipment::getInstance($exspeedite_db, $sts_debug);

$result = array( "CLIENT_CODE" => 0 );

// Only proceed if we have some parameters
if( isset($_GET['SHIPMENT']) && isset($_GET['CTYPE']) && isset($_GET['NAME']) &&
	isset($_GET['PW']) && $_GET['PW'] == 'DataOnFile' ) {
	
	$name = empty($_GET["NAME"]) ? '' : $_GET["NAME"];
	$addr1 = empty($_GET["ADDR1"]) ? '' : $_GET["ADDR1"];
	$addr2 = empty($_GET["ADDR2"]) ? '' : $_GET["ADDR2"];
	$city = empty($_GET["CITY"]) ? '' : $_GET["CITY"];
	$state = empty($_GET["STATE"]) ? '' : $_GET["STATE"];
	$zip_code = empty($_GET["ZIP"]) ? '' : $_GET["ZIP"];
	$country = empty($row["COUNTRY"]) ? 'USA' : $row["COUNTRY"];
	
	// Need a company name
	if( ! empty($name) ) {
		$result["CLIENT_NAME"] = $name;
		
		$duplicate = $client_table->check_match( $name, $addr1,
			$city, $state, $zip_code, $country );
		
		if( is_array($duplicate) && count($duplicate) > 0 ) {
			$result["CLIENT_CODE"] = $duplicate[0]["CLIENT_CODE"];		// Pick first one
			$result["ACTION"] = 'duplicate';
		} else {
			//! Add client entry
			$client_fields = array( 'CLIENT_NAME' => $name,
				'CLIENT_TYPE' => 'client',
				'CURRENT_STATUS' =>  $client_table->behavior_state['entry'] );
			
			if( ! empty($_GET["CONTACT"]) )
				$client_fields["CONTACT"] = $_GET["CONTACT"];
			
			if( $_GET['CTYPE'] == 'SHIPPER' )
				$client_fields["SHIPPER"] = true;
			else
				$client_fields["CONSIGNEE"] = true;
			
			$client_fields["CLIENT_NOTES"] = "Manually added by ".$_SESSION['EXT_USERNAME'];
			
			$client_code = $client_table->add( $client_fields );

			$contact_info_fields = array( 'CONTACT_CODE' => $client_code,
				'CONTACT_SOURCE' => 'client',
				'CONTACT_TYPE' => ($_GET['CTYPE'] == 'SHIPPER' ? 'shipper' : 'consignee') );

			if( ! empty($_GET["CONTACT"]) )
				$contact_info_fields["CONTACT_NAME"] = $_GET["CONTACT"];
			if( ! empty($_GET["ADDR1"]) )
				$contact_info_fields["ADDRESS"] = $_GET["ADDR1"];
			if( ! empty($_GET["ADDR2"]) )
				$contact_info_fields["ADDRESS2"] = $_GET["ADDR2"];
			if( ! empty($_GET["CITY"]) )
				$contact_info_fields["CITY"] = $_GET["CITY"];
			if( ! empty($_GET["STATE"]) )
				$contact_info_fields["STATE"] = $_GET["STATE"];
			if( ! empty($_GET["ZIP"]) )
				$contact_info_fields["ZIP_CODE"] = $_GET["ZIP"];
			if( ! empty($_GET["COUNTRY"]) )
				$contact_info_fields["COUNTRY"] = $_GET["COUNTRY"];
			if( ! empty($_GET["PHONE"]) )
				$contact_info_fields["PHONE_OFFICE"] = $_GET["PHONE"];
			if( ! empty($_GET["EXT"]) )
				$contact_info_fields["PHONE_EXT"] = $_GET["EXT"];
			if( ! empty($_GET["FAX"]) )
				$contact_info_fields["PHONE_FAX"] = $_GET["FAX"];
			if( ! empty($_GET["CELL"]) )
				$contact_info_fields["PHONE_CELL"] = $_GET["CELL"];
			if( ! empty($_GET["EMAIL"]) )
				$contact_info_fields["EMAIL"] = $_GET["EMAIL"];

			$contact_info_table->add( $contact_info_fields );
			
			// Add to $_GET['SHIPMENT']
			if( $_GET['CTYPE'] == 'SHIPPER' )
				$shipment_table->update( $_GET['SHIPMENT'],
					['SHIPPER_CLIENT_CODE' => $client_code], false );
			else
				$shipment_table->update( $_GET['SHIPMENT'],
					['CONS_CLIENT_CODE' => $client_code], false );

			$result["CLIENT_CODE"] = $client_code;
			$result["ACTION"] = 'added';
			$result["LAST_CHANGED"] = date("Y-m-d H:i:s");
			
			$user_log_table = sts_user_log::getInstance($exspeedite_db, $sts_debug);
			$client_link = '<a href="exp_editclient.php?CODE='.$client_code.'">'.$name.'</a>: ';
			$entry = $user_log_table->log_event('profiles', 'AJAX Add '.$_GET['CTYPE'].': '.$client_link);
			
			//! SCR# 722 - Email notification of new shipper/consignee
			$email_type = 'newclient';
			$email_code = $client_code;
			require_once( "exp_spawn_send_email.php" );
		}
	}
}

if( $sts_debug ) {
	echo "<p>result = </p>
	<pre>";
	var_dump($result);
	echo "</pre>";
} else {
	echo json_encode( $result );
}

?>