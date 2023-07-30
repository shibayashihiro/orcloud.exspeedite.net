<?php 

// $Id: exp_driver_card.php 4350 2021-03-02 19:14:52Z duncan $
// Fuel Card Mapping - add/remove card/unit mapping

// Set flag that this is a parent file
define( '_STS_INCLUDE', 1 );

// Set flag that this is session readonly
define( '_STS_SESSION_READONLY', 1 );

// Set flag that this is an ajax call
define( '_STS_SESSION_AJAX', 1 );

// Setup Session
require_once( "include/sts_session_setup.php" );

require_once( "include/sts_config.php" );
$sts_debug = isset($_GET['debug']); // && in_group( EXT_GROUP_DEBUG );
require_once( "include/sts_session_class.php" );

$my_session = sts_session::getInstance( $exspeedite_db, $sts_debug );
//! SCR# 593 - include EXT_GROUP_MECHANIC
$my_session->access_check( $sts_table_access[DRIVER_TABLE], EXT_GROUP_DRIVER, EXT_GROUP_MECHANIC );	// Make sure we should be here

//! SCR# 551 - expand this to handle fuel cards for tractors
require_once( "include/sts_driver_class.php" );
require_once( "include/sts_tractor_class.php" );

$driver_table = sts_driver::getInstance($exspeedite_db, $sts_debug);
$tractor_table = sts_tractor::getInstance($exspeedite_db, $sts_debug);

if( isset($_GET['PW']) && $_GET['PW'] == 'Society' ) {
	if( isset($_GET['DRIVER']) ) {
		echo $driver_table->fuel_cards( $_GET['DRIVER'] );
	} else if( isset($_GET['DEL_DRIVER']) ) {
		$driver_table->del_fuel_card( $_GET['DEL_DRIVER'] );
	} else if( isset($_GET['TRACTOR']) ) {
		echo $tractor_table->fuel_cards( $_GET['TRACTOR'] );
	} else if( isset($_GET['DEL_TRACTOR']) ) {
		$tractor_table->del_fuel_card( $_GET['DEL_TRACTOR'] );
	}
	
	if( isset($_GET['BACK']) )
		reload_page($_GET['BACK']);
}
?>