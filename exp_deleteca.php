<?php 

// $Id: exp_deleteca.php 4350 2021-03-02 19:14:52Z duncan $
// Delete Fuel card advance

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
$my_session->access_check( EXT_GROUP_ADMIN );	// Make sure we should be here

require_once( "include/sts_card_advance_class.php" );

$card_advance_table = sts_card_advance::getInstance($exspeedite_db, $sts_debug);

if( isset($_GET['CODE']) ) {
	$result = $card_advance_table->delete( $_GET['CODE'], 'permdel' );
	if( $sts_debug ) echo "<p>result = ".($result ? 'true' : 'false '.$card_advance_table->error())."</p>";
}

if( ! $sts_debug )
	reload_page ( "exp_listca.php" );	// Back to list reports page