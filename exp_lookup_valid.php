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
$my_session->access_check( $sts_table_access[SHIPMENT_TABLE] );	// Make sure we should be here

require_once( "include/sts_shipment_class.php" );

function create_valid_string( $name, $valid, $code, $descr, $source, $lat, $lon, $debug ) {
	if( isset($valid) && $valid <> false ) {
		if( $valid == 'valid' ) {
			$lat = isset($lat) && is_numeric($lat) ? floatval($lat) : 0;
			$lon = isset($lon) && is_numeric($lon) ? floatval($lon) : 0;
			$output = '<span  id="'.$name.'" class="text-success inform" data-content="Confirmed via '.
			(isset($source) ? $source : 'PC*Miler').
			($lat <> 0 && $lon <> 0 ? '<br>lat='.$lat.' lon='.$lon.' <a href=\'https://www.google.ca/maps/@'.$lat.','.$lon.',16z?hl=en\' target=\'_blank\'><span class=\'glyphicon glyphicon-new-window\'></span></a>' : '').
			'"><span class="glyphicon glyphicon-ok"></span></span>';
		} else if( in_array($valid, array('error','warning')) ) {
			$popup_text = (isset($code) ? $code : '').(isset($descr) && $descr <> '' ? '<br>'.$descr : '');
			
			$output = '<span  id="'.$name.'" class="text-'.($valid == 'error' ? 'danger' : 'muted').' inform" data-content="'.$popup_text.'"><span class="glyphicon glyphicon-'.($valid == 'error' ? 'remove' : 'warning-sign').'"></span></span>';
		}
	} else
		$output = '<span id="'.$name.'"></span>';
	if( $debug ) echo "<p>create_valid_string: ".htmlspecialchars($output)."</p>";
	return $output;
}

$shipment_table = sts_shipment::getInstance($exspeedite_db, $sts_debug);

if( isset($_GET['CODE']) && isset($_GET['PW']) && $_GET['PW'] == 'Zonko' ) {
	$values = $shipment_table->fetch_rows( $shipment_table->primary_key.' = '.$_GET['CODE'],
	"SHIPPER_VALID, SHIPPER_CODE, SHIPPER_DESCR, SHIPPER_VALID_SOURCE, SHIPPER_LAT, SHIPPER_LON,
	CONS_VALID, CONS_CODE, CONS_DESCR, CONS_VALID_SOURCE, CONS_LAT, CONS_LON,
	BILLTO_VALID, BILLTO_CODE, BILLTO_DESCR, BILLTO_VALID_SOURCE, BILLTO_LAT, BILLTO_LON,
	BROKER_VALID, BROKER_CODE, BROKER_DESCR, BROKER_VALID_SOURCE, BROKER_LAT, BROKER_LON" );
	
	$result = array();
	$result['SHIPPER_VALID'] = create_valid_string( 'SHIPPER_VALID', $values[0]['SHIPPER_VALID'],
		 $values[0]['SHIPPER_CODE'], $values[0]['SHIPPER_DESCR'],
		 $values[0]['SHIPPER_VALID_SOURCE'], $values[0]['SHIPPER_LAT'], $values[0]['SHIPPER_LON'],
		 $sts_debug );

	$result['CONS_VALID'] = create_valid_string( 'CONS_VALID', $values[0]['CONS_VALID'],
		 $values[0]['CONS_CODE'], $values[0]['CONS_DESCR'],
		 $values[0]['CONS_VALID_SOURCE'], $values[0]['CONS_LAT'], $values[0]['CONS_LON'],
		 $sts_debug );

	$result['BILLTO_VALID'] = create_valid_string( 'BILLTO_VALID', $values[0]['BILLTO_VALID'],
		 $values[0]['BILLTO_CODE'], $values[0]['BILLTO_DESCR'],
		 $values[0]['BILLTO_VALID_SOURCE'], $values[0]['BILLTO_LAT'], $values[0]['BILLTO_LON'],
		 $sts_debug );

	$result['BROKER_VALID'] = create_valid_string( 'BROKER_VALID', $values[0]['BROKER_VALID'],
		 $values[0]['BROKER_CODE'], $values[0]['BROKER_DESCR'],
		 $values[0]['BROKER_VALID_SOURCE'], $values[0]['BROKER_LAT'], $values[0]['BROKER_LON'],
		 $sts_debug );

	if( $sts_debug ) {
		echo "<p>result = </p>
		<pre>";
		var_dump($result);
		echo "</pre>";
	} else {
		echo json_encode( $result );
	}
}
