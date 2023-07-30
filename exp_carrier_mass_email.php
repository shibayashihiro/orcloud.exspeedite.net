<?php 

// $Id: exp_carrier_mass_email.php 5030 2023-04-12 20:31:34Z duncan $
//! SCR# 887 - Mass Email List

// Set flag that this is a parent file
define( '_STS_INCLUDE', 1 );

// Setup Session
require_once( "include/sts_session_setup.php" );

require_once( "include/sts_config.php" );
$sts_debug = isset($_GET['debug']) && in_group( EXT_GROUP_DEBUG );
require_once( "include/sts_session_class.php" );

$my_session = sts_session::getInstance( $exspeedite_db, $sts_debug );
$my_session->access_check( $sts_table_access[CARRIER_TABLE] );	// Make sure we should be here

require_once( "include/sts_carrier_class.php" );
require_once( "include/sts_csv_class.php" );

function en( $val ) {
	return '"'.$val.'"';
}

$carrier_table = sts_carrier::getInstance($exspeedite_db, $sts_debug);

$list = $carrier_table->mass_email();

if( is_array($list) && count($list) > 0 ) {

	$csv = new sts_csv(false, false, $sts_debug);
	
	$csv->header( "Carrier Mass Email" );

	echo en("Name").','.
		en("Bulk email").','.
		en("Office Phone #").','.
		en("Office Phone Ext").','.
		en("State").PHP_EOL;
		
	foreach( $list as $row ) {
		echo en($row["CARRIER_NAME"]).','.
			en($row["MASS_EMAIL"]).','.
			en($row["PHONE_OFFICE"]).','.
			en($row["PHONE_EXT"]).','.
			en($row["STATE"]).PHP_EOL;
	}
} else {
	$sts_subtitle = "Carrier Mass Email";
	require_once( "include/header_inc.php" );
	require_once( "include/navbar_inc.php" );
	echo '<div class="container-full theme-showcase" role="main">

	<h2>No Matching Records With Email Addresses Found</h2>
	';

	require_once( "include/footer_inc.php" );
}

?>