<?php 
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
$my_session->access_check( $sts_table_access[DETAIL_TABLE] );	// Make sure we should be here

require_once( "include/sts_detail_class.php" );
require_once( "include/sts_shipment_class.php" );

$detail_table = sts_detail::getInstance($exspeedite_db, $sts_debug);
$shipment_table = sts_shipment::getInstance($exspeedite_db, $sts_debug);

if( isset($_GET['CODE']) ) {
	$result = $detail_table->fetch_rows($detail_table->primary_key." = ".$_GET['CODE'], "SHIPMENT_CODE");
	
	if( $result && count($result) == 1 && isset($result[0]['SHIPMENT_CODE']) ) {
		$code = $result[0]['SHIPMENT_CODE'];
		$result = $detail_table->delete_row( $detail_table->primary_key." = '".$_GET['CODE']."'" );
		if( $result ) $result = $shipment_table->rollup_totals( $code );
	
		//if( ! $sts_debug )
		//	reload_page ( "exp_addshipment.php?CODE=".$code );	// Back to add shipment page
	}

	if( $sts_debug ) echo "<p>result = ".($result ? 'true' : 'false '.$detail_table->error())."</p>";

}

?>

