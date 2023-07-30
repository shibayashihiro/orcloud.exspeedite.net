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
require_once( "include/sts_commodity_class.php" );

$detail_table = sts_detail::getInstance($exspeedite_db, $sts_debug);
$commodity_table = sts_commodity::getInstance($exspeedite_db, $sts_debug);

if( isset($_GET['CODE']) && isset($_GET['TYPE']) && isset($_GET['PW']) && $_GET['PW'] == 'Mashed' ) {
	if( $_GET['TYPE'] == 'commodity' )
		$result = $detail_table->lookup_commodity( $_GET['CODE'] );
	else if( $_GET['TYPE'] == 'class' )
		$result = $commodity_table->lookup_class( $_GET['CODE'] );

	if( $sts_debug ) {
		echo "<p>result = </p>
		<pre>";
		var_dump($result);
		echo "</pre>";
	} else {
		echo json_encode( $result );
	}
}
