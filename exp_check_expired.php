<?php 

// $Id: exp_check_expired.php 4697 2022-03-09 23:02:23Z duncan $
//! SCR# 367 - Check if the data from a shipment is out of date

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
$my_session->access_check( EXT_GROUP_USER );	// Make sure we should be here

require_once( "include/sts_shipment_class.php" );

$shipment_table = sts_shipment::getInstance($exspeedite_db, $sts_debug);

if( $sts_debug ) {
	echo "<pre>";
	var_dump($_GET);
	echo "</pre>";
}

// Allow for expansion to check other types of records later
if( isset($_GET['CODE']) && isset($_GET['CHANGED']) &&
	isset($_GET['PW']) && $_GET['PW'] == 'Hillock' &&
	isset($_GET['TYPE']) && in_array($_GET['TYPE'], array('shipment'))) {
			
	$changed = date("Y-m-d H:i:s", strtotime(urldecode($_GET['CHANGED'])));
	
	if( $_GET['TYPE'] == 'shipment' ) {
		$check = $shipment_table->fetch_rows( "SHIPMENT_CODE = ".$_GET['CODE'].
			" AND CHANGED_DATE > '".$changed."'",
			"SHIPMENT_CODE, CHANGED_DATE, (SELECT USERNAME
				FROM EXP_USER
				WHERE USER_CODE = EXP_SHIPMENT.CHANGED_BY) AS USERNAME" );
		if( is_array($check) && count($check) == 1 &&
			! empty($check[0]["CHANGED_DATE"])) {
			//! SCR# 531 - log the event
			$shipment_table->log_event( 'exp_check_expired: EXPIRED shipment '.$_GET['CODE'].
				' changed = '.date("m/d/Y H:i:s", strtotime($check[0]["CHANGED_DATE"])).
				' by = '.$check[0]["USERNAME"].
				' vs = '.date("m/d/Y H:i:s", strtotime($_GET['CHANGED'])).
				' by = '.$_SESSION['EXT_USERNAME'], EXT_ERROR_DEBUG );
			$result = array( 'STATUS' => 'EXPIRED',
				'SHIPMENT_CODE' => $_GET['CODE'],
				'CHANGED_DATE' => date("m/d/Y H:i:s", strtotime($check[0]["CHANGED_DATE"])),
				'USERNAME' => $check[0]["USERNAME"]
				);
		} else {
			$shipment_table->log_event( 'exp_check_expired: NOT EXPIRED shipment '.$_GET['CODE'].
				' checked at = '.date("m/d/Y H:i:s", strtotime($_GET['CHANGED'])).
				' by = '.$_SESSION['EXT_USERNAME'], EXT_ERROR_DEBUG );
			$result = array( 'STATUS' => 'OK',
				'SHIPMENT_CODE' => $_GET['CODE'] );
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
}
