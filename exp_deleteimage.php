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

require_once( "include/sts_image_class.php" );

$image_table = new sts_image($exspeedite_db, $sts_debug);

if( isset($_GET['CODE']) ) {
	$result = $image_table->fetch_rows($image_table->primary_key." = ".$_GET['CODE'], "PARENT_CODE");
	
	if( $result && count($result) == 1 && isset($result[0]['PARENT_CODE']) ) {
		$code = $result[0]['PARENT_CODE'];
		$result = $image_table->delete_row( $image_table->primary_key." = '".$_GET['CODE']."'" );
	}

	if( $sts_debug ) echo "<p>result = ".($result ? 'true' : 'false '.$image_table->error())."</p>";
}

?>

