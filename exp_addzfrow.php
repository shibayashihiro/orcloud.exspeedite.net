<?php 

// Set flag that this is a parent file
define( '_STS_INCLUDE', 1 );

// Setup Session
require_once( "include/sts_session_setup.php" );

require_once( "include/sts_config.php" );
$sts_debug = isset($_GET['debug']);

require_once( "include/sts_zone_filter_class.php" );

if( isset($_GET['zname']) && isset($_GET['ztype']) && isset($_GET['zvalue']) && isset($_GET['code']) && $_GET['code'] == 'VistaSux') {

	$zone_filter_table = new sts_zone_filter($exspeedite_db, $sts_debug);

	$result = $zone_filter_table->add( array( "ZF_NAME" => $_GET['zname'], "ZF_TYPE" =>  $_GET['ztype'],  
		"ZF_VALUE" => $_GET['zvalue'] ) );

	if( $sts_debug ) {
		echo "<p>result = </p>
		<pre>";
		var_dump($result);
		echo "</pre>";
	} else {
		echo json_encode( $result );
	}
}


?>

