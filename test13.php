<?php 
// Set flag that this is a parent file
define( '_STS_INCLUDE', 1 );
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);

error_reporting(E_ALL);

// Setup Session
require_once( "include/sts_session_setup.php" );

require_once( "include/sts_config.php" );

$sts_debug = isset($_GET['debug']) && in_group( EXT_GROUP_DEBUG );
require_once( "include/sts_session_class.php" );

$my_session = sts_session::getInstance( $exspeedite_db, $sts_debug );
$my_session->access_check( EXT_GROUP_ADMIN );	// Make sure we should be here

$sts_subtitle = "Test11 - Inspection Report";
require_once( "include/header_inc.php" );
require_once( "include/sts_optimize_class.php" );

if( isset($_GET['OPTIM'])) {
	
	$test = [ 1,2,3,4 ];
	//$test = [ 99,1,2,3,4 ];

	echo '<h2>OPTIM Test 2</h2>';
	
	$optim = sts_optimize::getInstance($exspeedite_db, $sts_debug);
	
	$optim->optimize( $_GET['OPTIM'] );
	/*
	$optim->get_permutations( $test, [] );
	
	echo '<h2>All Permutations</h2>';
	foreach( $optim->get_patterns() as $pattern ) {
		echo implode(', ', $pattern ).'<br>';
	}
	
	$optim->must_be_before(1, 2);
	$optim->must_be_before(3, 4);
	//$optim->must_be_first(99);

	echo '<h2>Without Invalid</h2>';
	foreach( $optim->get_patterns() as $pattern ) {
		echo implode(', ', $pattern ).'<br>';
	}
	*/
}

require_once( "include/footer_inc.php" );

?>