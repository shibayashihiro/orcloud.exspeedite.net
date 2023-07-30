<?php 

// $Id:$
// Update the ETA for the stop via AJAX.

// Set flag that this is a parent file
define( '_STS_INCLUDE', 1 );

// Set flag that this is an ajax call
define( '_STS_SESSION_AJAX', 1 );

// Setup Session
require_once( "include/sts_session_setup.php" );

require_once( "include/sts_config.php" );
$sts_debug = (isset($_GET['debug'])  || isset($_POST['debug'])) && in_group( EXT_GROUP_DEBUG );
require_once( "include/sts_session_class.php" );

$my_session = sts_session::getInstance( $exspeedite_db, $sts_debug );
$my_session->access_check( $sts_table_access[STOP_TABLE] );	// Make sure we should be here

if( isset($_GET['code']) && isset($_GET['eta']) && isset($_GET['pw']) && $_GET['pw'] == 'Wrench7358') {
	require_once( "include/sts_stop_class.php" );
	
	$stop_table = sts_stop::getInstance($exspeedite_db, $sts_debug);
	
	if( ! empty($_GET['eta']) && checkIsAValidDate($_GET['eta']) ) {
		$eta = date("Y-m-d\TH:i", strtotime(urldecode($_GET['eta'])));
	
		$stop_table->update($_GET['code'], ['STOP_ETA' => $eta ] );
		echo 'stop = '.$_GET['code'].' ETA = '.urldecode($_GET['eta']);
	} else {
		echo 'stop = '.$_GET['code'].' ETA = INVALID';
	}
}
?>

