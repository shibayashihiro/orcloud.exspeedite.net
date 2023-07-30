<?php 

// $Id: exp_deleterm_class.php 4350 2021-03-02 19:14:52Z duncan $
// Delete RM class - Delete class for tractors/trailers

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

require_once( "include/sts_rm_class_class.php" );

$rm_class_table = sts_rm_class::getInstance($exspeedite_db, $sts_debug);

if( isset($_GET['CODE']) && $rm_class_table->can_delete( $_GET['CODE'] ) ) {
	$result = $rm_class_table->delete( $_GET['CODE'], 'permdel' );
	if( $sts_debug ) echo "<p>result = ".($result ? 'true' : 'false '.$rm_class_table->error())."</p>";
}

if( ! $sts_debug )
	reload_page ( "exp_listrm_class.php" );	// Back to list items page