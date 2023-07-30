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
$sts_debug = isset($_GET['debug']);

// close the session here to avoid blocking
session_write_close();

if( isset($_GET['pw']) && $_GET['pw'] == 'William') {
	
	require_once( "include/sts_session_class.php" );
	require_once( "include/sts_setting_class.php" );

	$my_session = sts_session::getInstance( $exspeedite_db, $sts_debug );
	
	//! If true display only my clients
	$my_sales_clients =  $my_session->cms_restricted_salesperson();

	$setting_table = sts_setting::getInstance( $exspeedite_db, false );
	$default_active = ($setting_table->get("option", "DEFAULT_ACTIVE") == 'true');	//! SCR# 533

	//! SCR# 533 - only show active if setting is true
	if( $default_active ) 
		$df = " AND ISACTIVE = 'Active'";
	else
		$df = "";
	
	$result = $exspeedite_db->get_one_row("
		select *
		from (select count(*) as USERS
			from ".USER_TABLE." ) UT,
		(select count(*) as COMPANIES
			from ".COMPANY_TABLE." ) CT,
		(select count(*) as OFFICES
			from ".OFFICE_TABLE." ) OT,
		(select count(*) as DRIVERS
			from ".DRIVER_TABLE." 
			where ISDELETED = false ".$df.") DT,
		(select count(*) as DRIVERS_EXPIRED
			from ".DRIVER_TABLE." 
			where ISDELETED = false
			AND DRIVER_EXPIRED(DRIVER_CODE, true) <> 'GREEN' ".$df.") DTE,
		(select count(*) as CARRIERS_EXPIRED
			from ".CARRIER_TABLE." 
			where ISDELETED = false
			AND CARRIER_EXPIRED(CARRIER_CODE) <> 'GREEN') CTE,
		(select count(*) as TRACTORS
			from ".TRACTOR_TABLE." 
			where ISDELETED = false ".$df.") TT,
		(select count(*) as TRACTORS_EXPIRED
			from ".TRACTOR_TABLE." 
			where ISDELETED = false
			AND TRACTOR_EXPIRED(TRACTOR_CODE) <> 'GREEN' ".$df.") TTE,
		(select count(*) as TRAILERS
			from ".TRAILER_TABLE." 
			where ISDELETED = false ".$df.") TT2,
		(select count(*) as TRAILERS_EXPIRED
			from ".TRAILER_TABLE." 
			where ISDELETED = false
			AND TRAILER_EXPIRED(TRAILER_CODE) <> 'GREEN' ".$df.") TT2E,
		(select count(*) as CARRIERS
			from ".CARRIER_TABLE." 
			where ISDELETED = false ) TT3,
		(select count(*) as CLIENTS
			from ".CLIENT_TABLE." 
			where ISDELETED = false
			".($my_sales_clients ? "AND SALES_PERSON = ".$_SESSION['EXT_USER_CODE'] : "")." ) TT4,
		(select count(*) as CLIENT_LEADS
			from ".CLIENT_TABLE." 
			where ISDELETED = false
			AND CLIENT_TYPE = 'lead' ) TT4a,
		(select count(*) as CLIENT_PROSPECTS
			from ".CLIENT_TABLE." 
			where ISDELETED = false
			AND CLIENT_TYPE = 'prospect'
			".($my_sales_clients ? "AND SALES_PERSON = ".$_SESSION['EXT_USER_CODE'] : "")." ) TT4b,
		(select count(*) as CLIENT_CLIENTS
			from ".CLIENT_TABLE." 
			where ISDELETED = false
			AND CLIENT_TYPE = 'client'
			".($my_sales_clients ? "AND SALES_PERSON = ".$_SESSION['EXT_USER_CODE'] : "")." ) TT4c,
	
		(select count(*) as PALLETS
			from ".PALLET_TABLE." ) TT5" );
	
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
