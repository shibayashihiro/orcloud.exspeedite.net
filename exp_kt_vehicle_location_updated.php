<?php 

// $Id: exp_kt_vehicle_location_updated.php 5030 2023-04-12 20:31:34Z duncan $
// Import position updates from KeepTruckin API

// Test https with this in terminal window:
// curl -i -X POST -H 'Content-Type: application/json' -H 'X-Kt-Webhook-Signature: f8c560795ed4454d8f7ebea50428834b99399d96' -d '{"action":"test"}' https://exp1.exspeedite.net/exp_kt_vehicle_location_updated.php

$sts_debug = false;

// Set flag that this is a parent file
define( '_STS_INCLUDE', 1 );
require_once( "include/sts_config.php" );
$timer = new sts_timer();
$timer->start();

//! SCR# 276 - intruduce a random delay
sleep ( rand ( 0, 3) );

$session_path = str_replace('\\', '/', ini_get('session.save_path')) . '/ex_'.$sts_database;
session_save_path($session_path);

require_once( "include/sts_ifta_log_class.php" );
require_once( "include/sts_tractor_class.php" );
require_once( "include/sts_user_class.php" );
require_once( "include/sts_driver_class.php" );

$kt = sts_keeptruckin::getInstance($exspeedite_db, $sts_debug);

$il = sts_ifta_log::getInstance($exspeedite_db, $sts_debug);
$user_table = new sts_user($exspeedite_db, false);
$driver_table = sts_driver::getInstance($exspeedite_db, $sts_debug);

$headers = apache_request_headers();
$sig = empty($headers['X-Kt-Webhook-Signature']) ? '' : $headers['X-Kt-Webhook-Signature'];

$w = stream_get_wrappers();
//$il->log_event( "exp_kt_vehicle_location_updated.php\nopenssl: ".
//	(extension_loaded('openssl') ? 'yes':'no').
//	", http wrapper: ". (in_array('http', $w) ? 'yes':'no').
//	", https wrapper: ".(in_array('https', $w) ? 'yes':'no').
//	(isset($_SERVER['REQUEST_METHOD']) ? "\nREQUEST_METHOD: ".$_SERVER['REQUEST_METHOD']: '').
//	(isset($_SERVER['REQUEST_URI']) ? ", REQUEST_URI: ".$_SERVER['REQUEST_URI']: '').
//	(isset($_SERVER['SERVER_PROTOCOL']) ? ", SERVER_PROTOCOL: ".$_SERVER['SERVER_PROTOCOL']: ''), EXT_ERROR_DEBUG );

$content = file_get_contents( 'php://input' );

$secret = '67298a4636cb6e07ceabf750bb63e99d';

$hash = hash_hmac('sha1', $content, $secret);

if( $sig <> $hash ) {
	sleep( 2 );
	$il->log_event( "exp_kt_vehicle_location_updated.php\nERROR 403\nheaders ".print_r($headers, true).
		"\ncontents ".print_r($content, true).
		"\nsig = $sig hash = $hash", EXT_ERROR_DEBUG );
	header('HTTP/1.0 403 Forbidden');
} else {
	header("HTTP/1.1 200 OK");
	$time_connect = $timer->split();

	//$il->log_event( "exp_kt_vehicle_location_updated.php\ncontents ".print_r($content, true) );
	
	$data = json_decode($content);
	
	//$il->log_event( "exp_kt_vehicle_location_updated.php\ndata ".print_r($data, true) );
	$_SESSION['EXT_USER_CODE'] = $user_table->special_user( KT_USER );
	$_SESSION['EXT_USERNAME'] = KT_USER;

	if( isset($data->vehicle_id)) {
		$tractor_code = $kt->tractor_code($data->vehicle_id, true);
		$il->log_event( "exp_kt_vehicle_location_updated.php\nat = ".date("m/d/Y H:i", strtotime($data->located_at))." type = ".$data->type.
			" lat ".$data->lat." lon = ".$data->lon." speed = ".$data->speed.
			"\nVehicle id = ".$data->vehicle_id." (tractor_code = ".($tractor_code ? $tractor_code : 'not found').
			") description= ".$data->description, EXT_ERROR_DEBUG );
	
		if( $tractor_code && $tractor_code > 0 && ! empty($data->located_at) &&
			! empty($data->lat) && ! empty($data->lon) &&
			! empty($data->description)) {
			$tractor_table = sts_tractor::getInstance($exspeedite_db, $sts_debug);
			
			$check = $tractor_table->fetch_rows("TRACTOR_CODE = ".$tractor_code,
				"MOBILE_LAST_STOPPED");
			$last_stopped = '-2 hour';
			if( is_array($check) && count($check) > 0 && ! empty($check[0]["MOBILE_LAST_STOPPED"]))
				$last_stopped = $check[0]["MOBILE_LAST_STOPPED"];
				
			$tractor_table->update($tractor_code, array(
				"MOBILE_TIME" => date("Y-m-d H:i:s", strtotime($data->located_at)),
				"MOBILE_LATITUDE" => $data->lat,
				"MOBILE_LONGITUDE" => $data->lon,
				"MOBILE_SPEED" => empty($data->speed) ? 0 : $data->speed,
				"MOBILE_EVENT_TYPE" => empty($data->type) ? '' : $data->type,
				"MOBILE_LOCATION" => $data->description
			));
			
			//! Try and update current load history, if more than 1 hour ago
			if( $data->type == 'vehicle_stopped' &&
				strtotime($last_stopped) < strtotime('-30 min') ) {
				sleep ( rand ( 1, 6) );

				$tractor_table->update($tractor_code, array(
					"MOBILE_LAST_STOPPED" => date("Y-m-d H:i:s", strtotime($data->located_at))
					));
				
				$driver = $kt->current_driver_code( $data->vehicle_id );
				//$il->log_event( "exp_kt_vehicle_location_updated.php driver_code = ".$driver, EXT_ERROR_DEBUG );
	
				if( $driver > 0 ) {
					$driver_table->checkin( $driver, $data->lat, $data->lon, $data->description );
				}
			}
			
		} else {
			//$il->log_event( "exp_kt_vehicle_location_updated.php\ncontents ".print_r($content, true), EXT_ERROR_DEBUG );
		}
	} else {
		$il->log_event( "exp_kt_vehicle_location_updated.php\ncontents ".print_r($content, true), EXT_ERROR_DEBUG );
	}
	$timer->stop();
	$time_total = $timer->result();
	$il->log_event( "exp_kt_vehicle_location_updated.php: connect = ".number_format((float) $time_connect,4)."s, total =".number_format((float) $time_total,4)."s", EXT_ERROR_DEBUG );

}

?>