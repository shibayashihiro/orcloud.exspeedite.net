<?php 
// Set flag that this is a parent file
define( '_STS_INCLUDE', 1 );

// Set flag that this is an ajax call
define( '_STS_SESSION_AJAX', 1 );

// Setup Session
require_once( "include/sts_session_setup.php" );

require_once( "include/sts_config.php" );
$sts_debug = isset($_GET['debug']) && in_group( EXT_GROUP_DEBUG );
require_once( "include/sts_session_class.php" );

$my_session = sts_session::getInstance( $exspeedite_db, $sts_debug );
$my_session->access_check( EXT_GROUP_USER );	// Make sure we should be here

require_once( "include/sts_message_class.php" );

if( isset($_GET['PW']) && $_GET['PW'] == 'AlJazeera' ) {
	$message_table = new sts_message($exspeedite_db, $sts_debug);
	
	if( isset($_GET['GET']) ) {
		$result = $message_table->my_messages( $_GET['GET'] );
		if( $sts_debug ) {
			echo "<p>result = </p>
			<pre>";
			var_dump($result);
			echo "</pre>";
		} else {
			echo json_encode( $result );
		}	
	} else if( isset($_GET['READ']) ) {
		$result = $message_table->mark_read( $_GET['READ'] );
		if( ! $sts_debug ) {
			if( in_group( EXT_GROUP_DRIVER ) )
				reload_page ( "exp_listload_driver.php" );
			else
				reload_page ( "index.php" );
		}
	}
}

?>