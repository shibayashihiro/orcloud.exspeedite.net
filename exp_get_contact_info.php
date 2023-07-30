<?php 

// $Id: exp_get_contact_info.php 4350 2021-03-02 19:14:52Z duncan $
// Get Client Info

// Set flag that this is a parent file
define( '_STS_INCLUDE', 1 );

// Set flag that this is session readonly
define( '_STS_SESSION_READONLY', 1 );

// Set flag that this is an ajax call
define( '_STS_SESSION_AJAX', 1 );

// Setup Session
require_once( "include/sts_session_setup.php" );

require_once( "include/sts_config.php" );
$sts_debug = (isset($_GET['debug'])  || isset($_POST['debug'])) && in_group( EXT_GROUP_DEBUG );
require_once( "include/sts_session_class.php" );

$my_session = sts_session::getInstance( $exspeedite_db, $sts_debug );
$my_session->access_check( $sts_table_access[CLIENT_TABLE], EXT_GROUP_SALES );	// Make sure we should be here

require_once( "include/sts_contact_info_class.php" );

if( isset($_GET["PW"]) && $_GET["PW"] = 'Bisodol'
	&& isset($_GET["CODE"]) && $_GET["CODE"] > 0 ) {
	
	$contact_info_table = new sts_contact_info($exspeedite_db, $sts_debug);
	
	$result = $contact_info_table->fetch_rows( "CONTACT_INFO_CODE = ".$_GET["CODE"],
		"*, COALESCE((SELECT CLIENT_URL FROM EXP_CLIENT
			WHERE CLIENT_CODE = CONTACT_CODE),'') AS CLIENT_URL"  );
	
	$output = '';
	
	if( is_array($result) && count($result) == 1 ) {
		$c = $result[0];
		$output = empty($c["CONTACT_NAME"]) ? '' : '<span class="glyphicon glyphicon-user"></span> <strong>'.$c["CONTACT_NAME"].'</strong>';
		
		if( ! empty($c["JOB_TITLE"]))
			$output .= ' ('.$c["JOB_TITLE"].')';

		if( ! empty($c["PHONE_OFFICE"])) {
			$output .= '<br><span class="glyphicon glyphicon-phone-alt"></span> <a href="tel:'.$c["PHONE_OFFICE"].'">'.$c["PHONE_OFFICE"].'</a>';
			if( ! empty($c["PHONE_EXT"]))
				$output .= ' ext. '.$c["PHONE_EXT"];

			if( ! empty($c["PHONE_CELL"]))
				$output .= '<br><span class="glyphicon glyphicon-phone"></span> <a href="tel:'.$c["PHONE_CELL"].'">'.$c["PHONE_CELL"].'</a>';
		}
		if( ! empty($c["EMAIL"]))
			$output .= '<br><span class="glyphicon glyphicon-envelope"></span> <a href="mailto:'.$c["EMAIL"].'">'.$c["EMAIL"].'</a>';

		if( ! empty($c["CLIENT_URL"])) {
			$link = strpos($c["CLIENT_URL"], 'http://') !== false ?
				$c["CLIENT_URL"] : 'http://'.$c["CLIENT_URL"];
			$output .= '<br><span class="glyphicon glyphicon-link"></span> <a href="'.$link.'" target="_blank">'.$c["CLIENT_URL"].'</a>';
		}
		$output .= '<br>';
		//! SCR# 420 - fix for missing address
		$address = '';
		$addr = '';
		
		if( ! empty($c["ADDRESS"])) {
			$address .= $c["ADDRESS"];
			$addr .= $c["ADDRESS"];
		}
		if( ! empty($c["ADDRESS2"])) {
			$address .= ', '.$c["ADDRESS2"];
			$addr .= ','.$c["ADDRESS2"];
		}
		if( ! empty($c["CITY"])) {
			$address .= ', '.$c["CITY"];
			$addr .= ','.$c["CITY"];
		}
		if( ! empty($c["STATE"])) {
			$address .= ', '.$c["STATE"];
			$addr .= ','.$c["STATE"];
		}
		if( ! empty($c["ZIP_CODE"])) {
			$address .= ', '.$c["ZIP_CODE"];
			$addr .= ','.$c["ZIP_CODE"];
		}
		if( ! empty($address)) {
			$output .= '<span class="glyphicon glyphicon-globe"></span> <a href="http://maps.google.com/?q='.$addr.'" target="_blank">'.$address.'</a>';
		}
		//Possibly link to exp_editcontact_info.php?CODE=
		
		$output = '<p>'.$output.'</p>';
	}
	
	echo $output;
}

?>