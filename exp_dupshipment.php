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
$my_session->access_check( $sts_table_access[SHIPMENT_TABLE] );	// Make sure we should be here

require_once( "include/sts_shipment_class.php" );
require_once( "include/sts_setting_class.php" );

$shipment_table = sts_shipment::getInstance($exspeedite_db, $sts_debug);

//! SCR# 413 - multiple duplicates
if( isset($_POST['CODE']) && intval($_POST['CODE']) > 0 &&
	isset($_POST['NUM']) && intval($_POST['NUM']) > 0 ) {
	//! SCR# 779 - Get checkbox values
	$pos = $bol = $ref = $pick = $cust = $pconf = $dconf = $broker = $empty_drop = false;
	if( isset($_POST['POS']) && $_POST['POS'] == 'on' ) $pos = true;
	if( isset($_POST['BOL']) && $_POST['BOL'] == 'on' ) $bol = true;
	if( isset($_POST['REF']) && $_POST['REF'] == 'on' ) $ref = true;
	if( isset($_POST['PICK']) && $_POST['PICK'] == 'on' ) $pick = true;
	if( isset($_POST['CUST']) && $_POST['CUST'] == 'on' ) $cust = true;
	if( isset($_POST['PCONF']) && $_POST['PCONF'] == 'on' ) $pconf = true;
	if( isset($_POST['DCONF']) && $_POST['DCONF'] == 'on' ) $dconf = true;
	if( isset($_POST['BROKER']) && $_POST['BROKER'] == 'on' ) $broker = true;
	if( isset($_POST['EMPTY_DROP']) && $_POST['EMPTY_DROP'] == 'on' ) $empty_drop = true;
	
	for( $c=1; $c<=intval($_POST['NUM']); $c++ ) {
		$result = $shipment_table->duplicate( $_POST['CODE'], false,
			$pos, $bol, $ref, $pick, $cust, $pconf, $dconf, $broker, $empty_drop );
		if( $sts_debug ) echo "<p>exp_dupshipment.php: result = ".($result ? 'true' : 'false '.$shipment_table->error())."</p>";
	}
} else if( isset($_GET['CODE']) && intval($_GET['CODE']) > 0 ) {
	$result = $shipment_table->duplicate( $_GET['CODE'], isset($_GET['STOPOFF']) );
	if( $sts_debug ) echo "<p>exp_dupshipment.php: result = ".($result ? 'true' : 'false '.$shipment_table->error())."</p>";
}

if( ! $sts_debug ) {
	reload_page ( (isset($_POST['CODE']) || isset($_GET['CODE'])) &&
		(! isset($_POST['NUM']) ||
			isset($_POST['NUM']) && intval($_POST['NUM']) == 1 ) &&
		isset($result) && is_integer($result) && $result > 0 ?
		"exp_addshipment.php?CODE=".$result : "exp_listshipment.php" );	// Back to list shipments page
} else {
	echo "<p>exp_dupshipment.php: chk = ".(isset($_POST['CODE']) &&
		(! isset($_POST['NUM']) ||
			isset($_POST['NUM']) && intval($_POST['NUM']) == 1 ) &&
		isset($result) && is_integer($result) && $result > 0 ? $result : "false")
		." result = ".$result."</p>";
}
?>