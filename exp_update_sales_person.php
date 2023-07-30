<?php 

// $Id: exp_update_sales_person.php 5097 2023-05-15 16:48:27Z duncan $
// Update the sales person for a shipment

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
$my_session->access_check( $sts_table_access[SHIPMENT_TABLE] );	// Make sure we should be here

require_once( "include/sts_shipment_class.php" );

if( isset($_GET) && isset($_GET['pw']) && $_GET['pw'] = 'HokaOneOne' &&
	! empty($_GET['CODE']) ) {
		
	$last_changed = false;

	$shipment_table = sts_shipment::getInstance($exspeedite_db, $sts_debug);
	
	$shipment_table->update($_GET['CODE'], ['SALES_PERSON' => 
		(empty($_GET['sales']) ? 'NULL' : $_GET['sales'])], false);
	$check = $shipment_table->fetch_rows( "SHIPMENT_CODE = ".$_GET['CODE'],
		"SHIPMENT_CODE, CHANGED_DATE" );
	if( is_array($check) && count($check) == 1 &&
		! empty($check[0]["CHANGED_DATE"])) {
		$last_changed = $check[0]["CHANGED_DATE"];
	}
	if( $sts_debug ) {
		echo "<p>result = </p>
		<pre>";
		var_dump($last_changed);
		echo "</pre>";
	} else {
		echo json_encode( $last_changed );
	}

}


?>