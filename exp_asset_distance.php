<?php 
//! Part of the select shipments for a load page
// Called via ajax to load the left column with suitable shipments

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
$my_session->access_check( EXT_GROUP_USER );	// Make sure we should be here

require_once( "include/sts_stop_class.php" );
require_once( "include/sts_driver_class.php" );
require_once( "include/sts_tractor_class.php" );
require_once( "include/sts_trailer_class.php" );
require_once( "include/sts_zip_class.php" );
require_once( "include/sts_setting_class.php" );

$setting_table = sts_setting::getInstance($exspeedite_db, $sts_debug);

$distances = $setting_table->get("option", "RESOURCE_DISTANCES") == 'true';
$max_distance = $setting_table->get( 'option', 'RESOURCE_MAX_DISTANCE' );

if( isset($_GET['CODE']) && $_GET['CODE'] == 'Switching' && $distances ) {

	$result = array();
	$zip_table = sts_zip::getInstance($exspeedite_db, $sts_debug);
	$stop_table = sts_stop::getInstance($exspeedite_db, $sts_debug);


	if( ! empty($_GET['LOAD']) )
		$result['FIRST_STOP'] = $stop_table->first_stop_address( $_GET['LOAD'] );
	
	if( isset($_GET['DRIVER']) ) {
		$driver_table = sts_driver::getInstance($exspeedite_db, $sts_debug);
		
		$result['LAST_STOP'] = $driver_table->last_stop_address( $_GET['DRIVER'] );
	} else
	if( isset($_GET['TRACTOR']) ) {
		$tractor_table = sts_tractor::getInstance($exspeedite_db, $sts_debug);
		
		$result['LAST_STOP'] = $tractor_table->last_stop_address( $_GET['TRACTOR'] );
	} else
	if( isset($_GET['TRAILER']) ) {
		$trailer_table = sts_trailer::getInstance($exspeedite_db, $sts_debug);
		
		$result['LAST_STOP'] = $trailer_table->last_stop_address( $_GET['TRAILER'] );
	}
	
	if( isset($result['LAST_STOP']) && isset($result['FIRST_STOP']) &&
		is_array($result['LAST_STOP']) && is_array($result['FIRST_STOP']) ) {
		$result['DISTANCE'] = $zip_table->get_distance_various(
			$result['LAST_STOP'], $result['FIRST_STOP'] );
		
		$result['STATUS'] = $result['DISTANCE'] > $max_distance ? 'danger' : 'success';
		if( ! empty($result['LAST_STOP']['CITY']) && ! empty($result['LAST_STOP']['STATE']))
			$result['MESSAGE'] = $result['LAST_STOP']['CITY'].', '.$result['LAST_STOP']['STATE'].
				' ('.$result['DISTANCE'].' Miles away)';
		else
			$result['MESSAGE'] = $result['DISTANCE'].' Miles away';
	} else {
		if( isset($result['LAST_STOP']) && is_array($result['LAST_STOP']) )
			$result['STRING'] = $zip_table->address_tostring($result['LAST_STOP']);
		
		$result['STATUS'] = 'warning';
		$result['MESSAGE'] = 'Unknown location';
	}
	
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