<?php 

// $Id: exp_dispatch2.php 5165 2023-07-23 01:27:14Z dev $
//! Picks / Drops / Stops Page
// 
// Switched to embedded map
// See this when you click on Miles
// You need to enable the Maps Embed API for your key
// https://developers.google.com/maps/documentation/embed/guide

// Set flag that this is a parent file
define( '_STS_INCLUDE', 1 );
set_time_limit(0);
ini_set('memory_limit', '1024M');

// Setup Session
require_once( "include/sts_session_setup.php" );

require_once( "include/sts_config.php" );
$sts_debug = isset($_GET['debug']) && in_group( EXT_GROUP_DEBUG );
require_once( "include/sts_session_class.php" );

$my_session = sts_session::getInstance( $exspeedite_db, $sts_debug );
$my_session->access_check( $sts_table_access[SHIPMENT_TABLE] );	// Make sure we should be here

$sts_subtitle = "Picks, Drops & Stops";
require_once( "include/header_inc.php" );
require_once( "include/navbar_inc.php" );
require_once( "include/sts_email_class.php" );

?>
<style>
.placeholder {
    border: 1px solid green;
    background-color: white;
    -webkit-box-shadow: 0px 0px 10px #888;
    -moz-box-shadow: 0px 0px 10px #888;
    box-shadow: 0px 0px 10px #888;
}
.tile {
    height: 100px;
    margin-bottom: 20px;
}
</style>

<div class="container" role="main">

<?php

//! SCR# 918 - Load Optimization Feature
if( is_array($_GET) && isset($_GET['OPTIMIZE']) && ! empty($_GET['CODE']) ) {
	require_once( "include/sts_optimize_class.php" );
	if (ob_get_level() == 0) ob_start();
	
	echo '<div id="loading"><h2 class="text-center">Optimizing Load
	<br><img src="images/loading.gif" alt="loading" width="" height=""><br>
	<div id="loading_count"></div></h2></div>';
	ob_flush(); flush();
	
	$optim = sts_optimize::getInstance($exspeedite_db, $sts_debug);
	$optim->optimize( $_GET['CODE'] );
	
	update_message( 'loading', '' );
	ob_flush(); flush();

	if( $sts_debug ) die;
	// Fall through to continue
}

require_once( "include/sts_load_class.php" );
$load_table = sts_load::getInstance($exspeedite_db, $sts_debug);

//! SCR# 344 - Make sure if the load is already dispatched
$check = $load_table->fetch_rows("LOAD_CODE = ".$_GET['CODE'], "CURRENT_STATUS" );
$ok_to_continue = true;
if( is_array($check) && count($check) == 1 && isset($check[0]["CURRENT_STATUS"])) {
	$load_status = $load_table->state_behavior[$check[0]["CURRENT_STATUS"]];
	if( in_array($load_status, array('complete', 'approved', 'billed', 'cancel'))) {
		echo '<div class="well well-md">
			<h1><span class="text-danger glyphicon glyphicon-warning-sign"></span> Load '.$_GET['CODE'].' is '.$load_table->state_name[$check[0]["CURRENT_STATUS"]].'</h1>
			<h3>You can no longer change stops for this load.</h3>
			
			<p>'.($load_status == 'cancel' ? '' : '<a class="btn btn-sm btn-default" href="exp_viewload.php?CODE='.$_GET['CODE'].'"><span class="glyphicon glyphicon-eye-open"></span> View Load '.$_GET['CODE'].'</a> ').'<a class="btn btn-sm btn-default" href="exp_listload.php"><span class="glyphicon glyphicon-arrow-up"></span> Back to Loads</a>
		</div>
	</div>
		';
		$ok_to_continue = false;
	} else if( in_array($load_status, array('dispatch', 'depart stop', 'depshdock',
		'depart shipper', 'deprecdock', 'depart cons', 'arrive stop', 'arrshdock',
		'arrive shipper', 'arrrecdock', 'arrive cons'))) {
		echo '<div class="alert alert-danger tighter tighter2">
			<h4 style="margin: 0;"><span class="text-danger glyphicon glyphicon-warning-sign"></span> Load '.$_GET['CODE'].' is already dispatched!</h4>
			</div>
			';
	}
}

if( $ok_to_continue ) {

?>

<h3>Picks, Drops & Stops For Load <span id="LOADCODE"><?php echo $_GET['CODE']; ?></span> 
<div class="btn-group">
<a class="btn btn-sm btn-default" href="exp_addload.php?CODE=<?php echo $_GET['CODE']; ?>" ><span class="glyphicon glyphicon-arrow-left"></span> Shipments</a>
<a class="btn btn-sm btn-warning" id="ADDSTOP" href="exp_add_return_stop.php?CODE=<?php echo $_GET['CODE']; ?>" title="Add extra stop"><span class="glyphicon glyphicon-plus"></span> Stop</a>
<a class="btn btn-sm btn-default" href="" id="UPDATE_MILES"><span class="glyphicon glyphicon-road"></span> Miles</a>
<a class="btn btn-sm btn-default" id="LOADS" href="exp_listload.php"><span class="glyphicon glyphicon-arrow-up"></span> Loads</a>
<a class="btn btn-sm btn-success" id="LOADS" href="exp_viewload.php?CODE=<?php echo $_GET['CODE']; ?>"><span class="glyphicon glyphicon-eye-open"></span> View</a>
<a class="btn btn-sm btn-success" id="CONTINUE" href="exp_dispatch3.php?CODE=<?php echo $_GET['CODE']; ?>"><span class="glyphicon glyphicon-arrow-right"></span> Resources</a>
<a class="btn btn-sm btn-danger tip-bottom" title="Reorder the stops to minimise distance/fuel" id="OPTIMIZE" href="exp_dispatch2.php?OPTIMIZE&CODE=<?php echo $_GET['CODE']; ?>"><span class="glyphicon glyphicon-random"></span> Optimize</a>
</div>
</h3>

<!--
<div class="alert alert-danger alert-dismissable">
  <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
  <h3><strong><span class="glyphicon glyphicon-warning-sign"></span> Warning!</strong> Work in progress. Do not use.</h3>
</div>
-->

<?php
function geocode_address($zip, $api_key, $debug = false) {
	if( $debug ) {
			echo "<pre>geocode_address: entry\n";
			var_dump($zip);
			var_dump($api_key);
			echo "</pre>";
	}
	
	$curl = curl_init();
    // Trimble Maps API URL for geocoding
    $url = "https://pcmiler.alk.com/apis/rest/v1.0/Service.svc/locations?postcode=".urlencode($zip)."&region=NA&dataset=Current&authToken=".$api_key;

    curl_setopt_array($curl, array(
		CURLOPT_URL => $url,
		CURLOPT_RETURNTRANSFER => true,
		CURLOPT_ENCODING => '',
		CURLOPT_MAXREDIRS => 10,
		CURLOPT_TIMEOUT => 0,
		CURLOPT_FOLLOWLOCATION => true,
		CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
		CURLOPT_CUSTOMREQUEST => 'GET',
	  ));
	  
	$response = curl_exec($curl);
	if(curl_errno($curl)) {
		echo 'Error:' . curl_error($curl);
	}
	curl_close($curl);
    // If the response is not empty, decode it
    if (!empty($response)) {
        $decoded_response = json_decode($response, true);
 		if( $debug ) {
		   	echo "<pre>geocode_address: decoded_response\n";
		   	var_dump($decoded_response);
		   	echo "</pre>";
	   	}
       return $decoded_response[0];
    }
    return null;
}
require_once( "include/sts_result_class.php" );
require_once( "include/sts_shipment_class.php" );
require_once( "include/sts_shipment_load_class.php" );
require_once( "include/sts_stop_class.php" );
require_once( "include/sts_setting_class.php" );

if( isset($_GET['CODE']) && intval($_GET['CODE']) > 0 ) {	//! Existing load
	
	$setting_table = sts_setting::getInstance($exspeedite_db, $sts_debug);
	$load_time = intval( $setting_table->get('main', 'Load Time' ) );
	$multi_company = ($setting_table->get("option", "MULTI_COMPANY") == 'true');
	$sts_maps_key = $setting_table->get( 'api', 'GOOGLE_API_KEY' );
	$sts_pcmaps_key = $setting_table->get( 'api', 'PCM_API_KEY' );

	// Get the current status of the load. It could be already dispatched.
	$result = $load_table->fetch_rows("LOAD_CODE = ".$_GET['CODE'], "CURRENT_STATUS" );
	$load_status = is_array($result) && count($result) > 0 ? $result[0]['CURRENT_STATUS'] : 0;
	if( $sts_debug ) echo "<p>Load status ".$load_table->state_name[$load_status].
		" behavior ".$load_table->state_behavior[$load_status]."</p>";
	
	$shipment_table = sts_shipment::getInstance($exspeedite_db, $sts_debug);
	$shipment_load_table = sts_shipment_load::getInstance($exspeedite_db, $sts_debug);
	$stop_table = sts_stop_left_join::getInstance($exspeedite_db, $sts_debug);
			
	//! Get list of shipments for picks, pickdocks and drops
	$recorded_stops = $stop_table->fetch_rows( "LOAD_CODE = ".$_GET['CODE'],
		"STOP_CODE, LOAD_CODE, SEQUENCE_NO, STOP_TYPE, IM_STOP_TYPE, STOP_COMMENT,
		STOP_CURRENT_STATUS, STOP_STATUS, DUE_TS2,
		(CASE STOP_TYPE WHEN 'stop' AND IM_STOP_TYPE IS NULL THEN NULL ELSE SHIPMENT END) AS SHIPMENT2,
		(SELECT SS_NUMBER FROM EXP_SHIPMENT WHERE SHIPMENT_CODE = SHIPMENT) AS SS_NUMBER,
		PICKUP_DATE, PICKUP_TIME_OPTION, PICKUP_TIME1, PICKUP_TIME2, DELIVER_DATE,
		DELIVER_TIME_OPTION, DELIVER_TIME1, DELIVER_TIME2, ACTUAL_ARRIVE,
		ACTUAL_DEPART, EDI_ARRIVE_STATUS, EDI_DEPART_STATUS,
		TRAILER_NUMBER, NAME, ADDRESS, ADDRESS2, CITY, STATE, ZIP_CODE, COUNTRY, STOP_DISTANCE,
		(CASE STOP_TYPE WHEN 'stop' THEN NULL ELSE PIECES END) AS PIECES2,
		(CASE STOP_TYPE WHEN 'stop' THEN NULL ELSE PALLETS END) AS PALLETS2,
		(CASE STOP_TYPE WHEN 'stop' THEN NULL ELSE WEIGHT END) AS WEIGHT2,
		(CASE STOP_TYPE WHEN 'pickdock' THEN LAST_DOCKED_AT ELSE 0 END) AS DOCKED_AT,
		(CASE STOP_TYPE WHEN 'pickdock' THEN 
		(SELECT LOAD_CODE FROM EXP_STOP
				WHERE STOP_CODE = LAST_DOCKED_AT) ELSE NULL END ) AS PREV_LOAD,
		(CASE WHEN STOP_TYPE = 'stop' AND SHIPMENT > 0 THEN
			(SELECT ST_NUMBER FROM EXP_SHIPMENT WHERE SHIPMENT_CODE = SHIPMENT)
			ELSE NULL END) AS CONTAINER",
		"SEQUENCE_NO ASC" );
	
	$num_recorded_stops = count($recorded_stops);
	
	$stop_type_label = array(
		'pick' => '<img height="32" alt="arrows-up-icon" src="images/arrows-up-icon.png"> Pick',
		'drop' => '<img height="32" alt="arrows-up-icon" src="images/arrows-down-icon.png"> Drop',
		'pickdock' => '<img height="32" alt="arrows-up-icon" src="images/arrows-up-icon.png"> Pick',
		'dropdock' => '<img height="32" alt="arrows-up-icon" src="images/arrows-down-icon.png"> Drop',
		'stop' => '<img height="32" alt="arrows-up-icon" src="images/stop-sign-icon.png"> Stop'
	);
	// Make MapRoutesRequestBody from zipcode.
	$pin = array();
	$Stops = array();
	
	for( $i=0; $i < $num_recorded_stops; $i++ ) {
		
		$Stops[$i]['Address']['Zip'] = $recorded_stops[$i]["ZIP_CODE"];
		$geocode[$i] = geocode_address($recorded_stops[$i]["ZIP_CODE"], $sts_pcmaps_key, $sts_debug);
		if($i == 0 && isset($geocode[$i]['Coords'])){
			$pin[0]['Point']['Lat'] = (float)$geocode[$i]['Coords']['Lat'];
			$pin[0]['Point']['Lon'] = (float)$geocode[$i]['Coords']['Lon'];
			$pin[0]['Image'] = "ltruck_r";
		}else if($i == $num_recorded_stops-1 && isset($geocode[$i]['Coords'])){
			$pin[1]['Point']['Lat'] = (float)$geocode[$i]['Coords']['Lat'];
			$pin[1]['Point']['Lon'] = (float)$geocode[$i]['Coords']['Lon'];
			$pin[1]['Image'] = "lbldg_bl";
		}
	}		

	echo '<div class="pickdrop">';

	foreach( $recorded_stops as $row ) {
		$stop_type = $row['STOP_TYPE'];
		$stop_type_id = strtolower($stop_type_label[$stop_type]);
		$cdlabel = '';
		if( $stop_type == 'drop' ) {
			$cdpath = 'exp_crossdock.php?LOAD='.$_GET['CODE'].'&SHIPMENT='.$row['SHIPMENT2'];
			$cdtext = 'drop and hook (TL) or crossdock (LTL) shipment '.$row['SHIPMENT2'];
			$cdicon = '<span class="glyphicon glyphicon-resize-horizontal"></span> Xdock';
		} else if( $stop_type == 'dropdock' ){
			$cdpath = 'exp_crossdock.php?LOAD='.$_GET['CODE'].'&REMOVE='.$row['STOP_CODE'];
			$cdtext = 'remove drop and hook (TL) or crossdock (LTL)';
			$cdlabel = ' (dock)';
			$cdicon = '<span class="glyphicon glyphicon-remove"></span> Undock';
		}
		
		$is_complete = ($stop_table->state_behavior[$row['STOP_CURRENT_STATUS']] == 'complete');
		$is_pick = in_array($stop_type, array('pick','pickdock'));
		$is_drop = in_array($stop_type, array('drop','dropdock'));
		$is_stop = $stop_type == 'stop';
		$is_im_empty_drop = $stop_type == 'stop' && ! empty($row['CONTAINER']);
		
		echo '<div class="row well well-sm'.($is_complete ? ' fixed' : '').'" 
				data-shipment="'.$row['SHIPMENT2'].'" 
				data-stop="'.$row['STOP_CODE'].'" 
				data-im_stop_type="'.$row['IM_STOP_TYPE'].'" 
				data-sequence="'.$row['SEQUENCE_NO'].'" 
				data-action="'.$stop_type.'" 
				data-addr1="'.(isset($row['ADDRESS']) && $row['ADDRESS'] <> '' ? $row['ADDRESS'] : '').'" 
				data-addr2="'.(isset($row['ADDRESS2']) && $row['ADDRESS2'] <> '' ? $row['ADDRESS2'] : '').'" 
				data-city="'.(isset($row['CITY']) && $row['CITY'] <> '' ? $row['CITY'] : '').'" 
				data-state="'.(isset($row['STATE']) && $row['STATE'] <> '' ? $row['STATE'] : '').'" 
				data-zip="'.(isset($row['ZIP_CODE']) && $row['ZIP_CODE'] <> '' ? $row['ZIP_CODE'] : '').'" 
				data-country="'.(isset($row['COUNTRY']) && $row['COUNTRY'] <> '' ? $row['COUNTRY'] : '').'" 
				data-pallets="'.(isset($row['PALLETS2']) ? $row['PALLETS2'] : '0').'" 
				data-weight="'.(isset($row['WEIGHT2']) ? $row['WEIGHT2'] : '0').'"
				data-by="'.strip_tags($row['DUE_TS2']).'">
				<div class="col-sm-3"><h4><span id="SEQ_'.$row['STOP_CODE'].'">'.$row['SEQUENCE_NO'].'</span> '.$stop_type_label[$stop_type].' '.
				
				($is_stop ? ($is_complete ? '' : '<a href="exp_del_return_stop.php?load='.$_GET['CODE'].'&stop='.$row['STOP_CODE'].'" title="Delete stop"><span class="text-danger"><span class="glyphicon glyphicon-remove"></span></span></a>') : '<a href="exp_addshipment.php?CODE='.$row['SHIPMENT2'].'" target="_blank" title="edit shipment '.$row['SHIPMENT2'].' in new window">#'.$row['SHIPMENT2'].'</a>'.
				($multi_company && isset($row['SS_NUMBER']) && $row['SS_NUMBER'] <> '' ? ' ('.$row['SS_NUMBER'].')' : '')
				.' ').
				
				'</h4>'.($is_complete ? 'Completed' : '').'</div>
				<div class="col-sm-4"><strong>'.$row['NAME'].$cdlabel.'</strong>'.($row['DOCKED_AT'] > 0 ? ' <span class="badge" title="Docked from load #'.$row['PREV_LOAD'].'">D</span>' : '').
				($is_drop ? '&nbsp;&nbsp;<a class="btn btn-xs btn-primary crossdock" id="CROSSDOCK_'.$row['SHIPMENT2'].'" 
				href="'.$cdpath.'"
				title="'.$cdtext.'" '.($is_complete ? 'disabled' : '').'>'.$cdicon.'</a>' : '').
				'<br>
				'.$row['ADDRESS'].'<br>
				'.(isset($row['ADDRESS2']) && $row['ADDRESS2'] <> '' ? $row['ADDRESS2'].'<br>
				' : '').$row['CITY'].', '.$row['STATE'].', '.$row['ZIP_CODE'].
				(isset($row['COUNTRY']) && $row['COUNTRY'] <> '' ? '<br>
				'.$row['COUNTRY'] : '').'
				</div>
				<div class="col-sm-3">
					<div class="row">
						'.(! empty($row['STOP_COMMENT']) ? '<div class="col-sm-12">
							'.$row['STOP_COMMENT'].'
						</div>' : '
						<div class="col-sm-6">'.($is_pick ? '+ '.$row['PALLETS2'].' Pallets<br>
						+ '.$row['WEIGHT2'].' lb
						' : ($is_drop ? '- '.$row['PALLETS2'].' Pallets<br>
						- '.$row['WEIGHT2'].' lb
						' : '<br><br>' )).'</div>').'
						
						<div class="col-sm-6" id="cargo_'.$stop_type.'_'.$row['SHIPMENT2'].'">
						</div>
					</div>
					<div class="row">
						<div class="col-sm-12">
						<span id="dates_ship_'.$stop_type.'_'.$row['SHIPMENT2'].'">'.
							str_replace('<br>', ' ', $row['DUE_TS2']).'</span><br>
						<span id="dates_'.$stop_type.'_'.$row['SHIPMENT2'].'"></span>
						</div>
					</div>
				</div>
				<div class="col-sm-2" id="miles_'.$stop_type.'_'.($is_stop ? $row['STOP_CODE'] : $row['SHIPMENT2']).'">
				</div>
			</div>';
	}
	
	//! OLD CODE============================================================
	/*
	$dropdock = array();
	foreach( $recorded_stops as $recorded ) {
		if( $recorded["STOP_TYPE"] == 'dropdock' )
			$dropdock[$recorded["SHIPMENT"]] = $recorded;
	}
	
	$stop = array();
	foreach( $result as $row) {
		$picktype = "pick";
		if( $row['DOCKED_AT'] > 0 ) {
			$picktype = "pickdock";
		}
		
		$stop[$picktype.$row['SHIPMENT_CODE']] =
			'<div class="row well well-sm" 
				data-shipment="'.$row['SHIPMENT_CODE'].'" 
				data-action="'.$picktype.'" 
				data-addr1="'.(isset($row['SHIPPER_ADDR1']) && $row['SHIPPER_ADDR1'] <> '' ? $row['SHIPPER_ADDR1'] : '').'" 
				data-addr2="'.(isset($row['SHIPPER_ADDR2']) && $row['SHIPPER_ADDR2'] <> '' ? $row['SHIPPER_ADDR2'] : '').'" 
				data-city="'.(isset($row['SHIPPER_CITY']) && $row['SHIPPER_CITY'] <> '' ? $row['SHIPPER_CITY'] : '').'" 
				data-state="'.(isset($row['SHIPPER_STATE']) && $row['SHIPPER_STATE'] <> '' ? $row['SHIPPER_STATE'] : '').'" 
				data-zip="'.(isset($row['SHIPPER_ZIP']) && $row['SHIPPER_ZIP'] <> '' ? $row['SHIPPER_ZIP'] : '').'" 
				data-pallets="'.(isset($row['PALLETS']) ? $row['PALLETS'] : '0').'" 
				data-weight="'.(isset($row['WEIGHT']) ? $row['WEIGHT'] : '0').'"
				data-by="'.format_timestamp($row['PICKUP_DATE'],true,'').'">
				<div class="col-sm-3"><h3><a href="exp_addshipment.php?CODE='.$row['SHIPMENT_CODE'].'" target="_blank" title="edit shipment '.$row['SHIPMENT_CODE'].' in new window">#'.$row['SHIPMENT_CODE'].'</a> Pick</h3></div>
				<div class="col-sm-4"><strong>'.$row['SHIPPER_NAME'].'</strong>'.($row['DOCKED_AT'] > 0 ? ' <span class="badge" title="Docked from load #'.$row['PREV_LOAD'].'">D</span>' : '').'<br>
				'.$row['SHIPPER_ADDR1'].'<br>
				'.(isset($row['SHIPPER_ADDR2']) && $row['SHIPPER_ADDR2'] <> '' ? $row['SHIPPER_ADDR2'].'<br>
				' : '').$row['SHIPPER_CITY'].', '.$row['SHIPPER_STATE'].', '.$row['SHIPPER_ZIP'].'
				</div>
				<div class="col-sm-3">
					<div class="row">
						<div class="col-sm-6">+ '.$row['PALLETS'].' Pallets<br>
						+ '.$row['WEIGHT'].' lb
						</div>
						<div class="col-sm-6" id="cargo_pick_'.$row['SHIPMENT_CODE'].'">
						</div>
					</div>
					<div class="row">
						<div class="col-sm-12">
						<span id="dates_ship_pick_'.$row['SHIPMENT_CODE'].'">'.
							format_timestamp($row['PICKUP_DATE']).'</span><br>
						<span id="dates_pick_'.$row['SHIPMENT_CODE'].'"></span>
						</div>
					</div>
				</div>
				<div class="col-sm-2" id="miles_pick_'.$row['SHIPMENT_CODE'].'">
				</div>
			</div>';

		$droptype = "drop";
		$droplabel = "Drop";
		$cdpath = 'exp_crossdock.php?LOAD='.$_GET['CODE'].'&SHIPMENT='.$row['SHIPMENT_CODE'];
		$cdtext = 'drop and hook (TL) or crossdock (LTL) shipment '.$row['SHIPMENT_CODE'];
		if( isset($dropdock[$row['SHIPMENT_CODE']]) ) {	//! drop is really a dropdock
			$droptype = "dropdock";
			$droplabel = "Dock";
			$dd = $dropdock[$row['SHIPMENT_CODE']];
			$row['CONS_NAME'] = $dd["STOP_NAME"];
			$row['CONS_ADDR1'] = $dd["STOP_ADDR1"];
			$row['CONS_ADDR2'] = $dd["STOP_ADDR2"];
			$row['CONS_CITY'] = $dd["STOP_CITY"];
			$row['CONS_STATE'] = $dd["STOP_STATE"];
			$row['CONS_ZIP'] = $dd["STOP_ZIP"];
			$cdpath = 'exp_crossdock.php?LOAD='.$_GET['CODE'].'&REMOVE='.$dd['STOP_CODE'];
			$cdtext = 'remove drop and hook (TL) or crossdock (LTL)';
		}

		$stop[$droptype.$row['SHIPMENT_CODE']] =
			'<div class="row well well-sm" 
				data-shipment="'.$row['SHIPMENT_CODE'].'" 
				data-action="'.$droptype.'" 
				data-addr1="'.(isset($row['CONS_ADDR1']) && $row['CONS_ADDR1'] <> '' ? $row['CONS_ADDR1'] : '').'" 
				data-addr2="'.(isset($row['CONS_ADDR2']) && $row['CONS_ADDR2'] <> '' ? $row['CONS_ADDR2'] : '').'" 
				data-city="'.(isset($row['CONS_CITY']) && $row['CONS_CITY'] <> '' ? $row['CONS_CITY'] : '').'" 
				data-state="'.(isset($row['CONS_STATE']) && $row['CONS_STATE'] <> '' ? $row['CONS_STATE'] : '').'" 
				data-zip="'.(isset($row['CONS_ZIP']) && $row['CONS_ZIP'] <> '' ? $row['CONS_ZIP'] : '').'" 
				data-pallets="'.(isset($row['PALLETS']) ? $row['PALLETS'] : '0').'" 
				data-weight="'.(isset($row['WEIGHT']) ? $row['WEIGHT'] : '0').'"
				data-by="'.format_timestamp($row['DELIVER_DATE'],true,'').'"
				data-crossdock="'.$shipment_table->encryptData($cdpath).'">
				<div class="col-sm-3"><h3><a href="exp_addshipment.php?CODE='.$row['SHIPMENT_CODE'].'" target="_blank" title="edit shipment '.$row['SHIPMENT_CODE'].' in new window">#'.$row['SHIPMENT_CODE'].'</a> '.$droplabel.'</h3></div>
				<div class="col-sm-4"><strong>'.$row['CONS_NAME'].'</strong>
				&nbsp;&nbsp;<a class="btn btn-xs btn-primary crossdock" id="CROSSDOCK_'.$row['SHIPMENT_CODE'].'" 
				href="#"
				title="'.$cdtext.'" disabled><span class="glyphicon glyphicon-resize-horizontal"></span></a>
				<br>
				'.$row['CONS_ADDR1'].'<br>
				'.(isset($row['CONS_ADDR2']) && $row['CONS_ADDR2'] <> '' ? $row['CONS_ADDR2'].'<br>
				' : '').$row['CONS_CITY'].', '.$row['CONS_STATE'].', '.$row['CONS_ZIP'].'
				</div>
				<div class="col-sm-3">
					<div class="row">
						<div class="col-sm-6">- '.$row['PALLETS'].' Pallets<br>
						- '.$row['WEIGHT'].' lb
						</div>
						<div class="col-sm-6" id="cargo_drop_'.$row['SHIPMENT_CODE'].'">
						</div>
					</div>
					<div class="row">
						<div class="col-sm-12">
						<span id="dates_ship_drop_'.$row['SHIPMENT_CODE'].'">'.
							format_timestamp($row['DELIVER_DATE']).'</span><br>
						<span id="dates_drop_'.$row['SHIPMENT_CODE'].'"></span>
						</div>
					</div>
				</div>
				<div class="col-sm-2" id="miles_'.$droptype.'_'.$row['SHIPMENT_CODE'].'">
				</div>
			</div>';
	}
	
	echo '<div class="pickdrop">
				';
	
	if( $sts_debug ) {
		echo "<p>stop = </p>
		<pre>";
		var_dump($stop);
		echo "</pre>";
	}

	if( $sts_debug ) {
		echo "<p>recorded_stops = </p>
		<pre>";
		var_dump($recorded_stops);
		echo "</pre>";
	}

	$final_stop = 0;
	foreach( $recorded_stops as $recorded ) {
		$key = $recorded['STOP_TYPE'].$recorded['SHIPMENT'];
		if( isset($stop[$key]) ) { //! Disable completed stops
			if( $stop_table->state_behavior[$recorded['CURRENT_STATUS']] == 'complete' ) {
				$stop[$key] = preg_replace('/well-sm/', 'well-sm fixed', $stop[$key], 1);
				$stop[$key] = preg_replace('#</h2>#', '</h2>Completed', $stop[$key], 1);
			}
			echo $stop[$key];
			$stop[$key] = "DONE";
		} else if( $recorded['STOP_TYPE'] == 'stop' ) { // fixed
			$stop_code = '<div class="row well well-sm"
				data-action="stop" 
				data-sequence="'.$recorded['SEQUENCE_NO'].'" 
				data-stop="'.$recorded['STOP_CODE'].'" 
				data-addr1="'.(isset($recorded['STOP_ADDR1']) && $recorded['STOP_ADDR1'] <> '' ? $recorded['STOP_ADDR1'] : '').'" 
				data-addr2="'.(isset($recorded['STOP_ADDR2']) && $recorded['STOP_ADDR2'] <> '' ? $recorded['STOP_ADDR2'] : '').'" 
				data-city="'.(isset($recorded['STOP_CITY']) && $recorded['STOP_CITY'] <> '' ? $recorded['STOP_CITY'] : '').'" 
				data-state="'.(isset($recorded['STOP_STATE']) && $recorded['STOP_STATE'] <> '' ? $recorded['STOP_STATE'] : '').'" 
				data-zip="'.(isset($recorded['STOP_ZIP']) && $recorded['STOP_ZIP'] <> '' ? $recorded['STOP_ZIP'] : '').'">
				<div class="col-sm-3"><h3>Stop</h3></div>
				<div class="col-sm-4"><strong>'.$recorded['STOP_NAME'].'</strong><br>
				'.$recorded['STOP_ADDR1'].'<br>
				'.(isset($recorded['STOP_ADDR2']) && $recorded['STOP_ADDR2'] <> '' ? $recorded['STOP_ADDR2'].'<br>
				' : '').$recorded['STOP_CITY'].', '.$recorded['STOP_STATE'].', '.$recorded['STOP_ZIP'].'
				</div>
				<div class="col-sm-3">
					<div class="row">
						<div class="col-sm-6">
						</div>
						<div class="col-sm-6" id="cargo_stop_'.$recorded['STOP_CODE'].'">
						</div>
					</div>
					<div class="row">
						<div class="col-sm-12">
						<span id="dates_ship_stop_'.$recorded['STOP_CODE'].'">'.
							'</span><br>
						<span id="dates_stop_'.$recorded['STOP_CODE'].'"></span>
						</div>
					</div>
				</div>
				<div class="col-sm-2" id="miles_stop_'.$recorded['STOP_CODE'].'">
				</div>
			</div>';
			if( $recorded['SEQUENCE_NO'] == 1 ) echo $stop_code;
			else $final_stop = $recorded['STOP_CODE'];
		}
	}
	
	$num_stops = $num_recorded_stops;
	// Flush remaining rows
	foreach( $stop as $key => $row ) {
		if( $row <> "DONE" && strcmp(substr($key, 0,4),'pick') == 0 ) {
			echo $row;
			$num_stops++;
		}
	}
	foreach( $stop as $key => $row ) {
		if( $row <> "DONE" && strcmp(substr($key, 0,4),'drop') == 0 ) {
			echo $row;
			$num_stops++;
		}
	}
	if( $final_stop > 0) {	// Is there a return stop?
		echo $stop_code;
		// Update the sequence number to make it the last one.
		$dummy = $stop_table->update( $final_stop, array("SEQUENCE_NO" => $num_stops ) );
	}
	*/
	
	echo '        </div>
			<div class="row">
				<div class="col-sm-2 col-sm-offset-10" id="TOTALS">
				</div>
			</div>
			<br>
			<div class="row">
				<p>Google Map representation of the route is based on zipcodes only.</p>
				<div id="MAP"></div>
			</div>
			<div class="row">
				<p>Trimble Map representation of the route is based on full address only.</p>
				<div id="PCMAP"></div>
			</div>
	';

} else {
	echo "<p><strong>Error:</strong> missing load number.</p>";
}
?>

	<div class="modal fade bs-example-modal-sm" tabindex="-1" role="dialog" aria-labelledby="mySmallModalLabel" aria-hidden="true" id="update_miles_modal">
	  <div class="modal-dialog">
		<div class="modal-content">
		<div class="modal-header">
			<h4 class="modal-title" id="myModalLabel"><span class="text-success"><strong>Updating Miles...</strong></span></h4>
		</div>
		<div class="modal-body">
			<p class="text-center"><img src="images/loading.gif" alt="loading" width="125" height="125" /></p>
		</div>
		</div>
		</div>
	</div>

	<div class="modal fade bs-example-modal-sm" tabindex="-1" role="dialog" aria-labelledby="mySmallModalLabel" aria-hidden="true" id="savestops_modal">
	  <div class="modal-dialog">
		<div class="modal-content">
		<div class="modal-header">
			<h4 class="modal-title" id="myModalLabel"><span class="text-success"><strong>Updating Stops...</strong></span></h4>
		</div>
		<div class="modal-body">
			<p class="text-center"><img src="images/loading.gif" alt="loading" width="125" height="125" /></p>
		</div>
		</div>
		</div>
	</div>


</div>

	<script language="JavaScript" type="text/javascript"><!--

		Date.prototype.addHours = function(h) {    
			var copiedDate = new Date(this.getTime());
			copiedDate.setTime(this.getTime() + (h*60*60*1000));
			return copiedDate;
		}

		$(document).ready( function () {

			var key = '<?php echo $sts_maps_key; ?>';
			var pckey = '<?php echo $sts_pcmaps_key; ?>';
			var stops = <?php echo json_encode($Stops); ?>;
			var pins = <?php echo json_encode($pin); ?>;
			function update_miles() {
				$('#update_miles_modal').modal({
					container: 'body'
				});
	
				var previousZip = 'START';
				var previousAddr1 = 'START';
				var previousAddr2 = 'START';
				var previousCity = 'START';
				var previousState = 'START';
				var totalMiles = 0;
				var totalHours = 0;
				var totalPallets = 0;
				var totalWeight = 0;
				var by = 0;
				var by_end = 0;
				var last_by = 0;
				var date_format = 'MM/dd HH:mm';
				var locations = new Array();
				var cell_name;
				$('#update_miles_modal .modal-body').html('<p class="text-center"><img src="images/loading.gif" alt="loading" width="125" height="125" /></p>');
				$(".pickdrop").children().each(function() {
					var thisRow = $(this);
					
					// Calculate the current pallets / weight
					if( thisRow.data("action") == 'pick' ) {
						totalPallets += thisRow.data("pallets");
						totalWeight += thisRow.data("weight");
					} else {
						totalPallets -= thisRow.data("pallets");
						totalWeight -= thisRow.data("weight");
					}
					$('#cargo_'+thisRow.data("action") + '_' + thisRow.data("shipment") ).
						html('= ' + totalPallets + ' Pallets<br>= ' + totalWeight + ' lb');

					if( thisRow.data("by") != '' ) {
						//console.log('Raw: ' + thisRow.data("by"));
						by = new Date(thisRow.data("by"));
						//console.log('Processed: ' + $.format.date(by, date_format) );
					}
					if( thisRow.data("by-end") != '' ) 
						by_end = new Date(thisRow.data("by-end"));

					if( previousZip == 'START' || // Started - no miles
						(previousZip == thisRow.data("zip") && previousAddr1 == thisRow.data("addr1") ) ) {	// Same address
						previousZip = thisRow.data("zip");
						previousAddr1 = thisRow.data("addr1");
						previousAddr2 = thisRow.data("addr2");
						previousCity = thisRow.data("city");
						previousState = thisRow.data("state");
						previousCountry = thisRow.data("country");
						loadTime = <?php echo $load_time; ?>;
						totalHours += loadTime;
						cell_name = '#miles_'+thisRow.data("action") + '_' +
							( thisRow.data("action") == 'stop' ? thisRow.data("stop") : thisRow.data("shipment") );
						$(cell_name).html(0 + ' miles<br>' + 
							loadTime  + ' hours<br>' + 
							Math.round((loadTime / 24)*10)/10 + ' days');
					} else {
						$('#update_miles_modal .modal-body').prepend('<p>'+previousCity+','+previousState+' -> '+$(this).data("city")+','+$(this).data("state")+'</p>');
						$.ajax({
							async: false,
							url: 'exp_distance_grid.php',
							data: {
								code: 'Recycle',
								addr11: encodeURIComponent(previousAddr1),
								addr21: encodeURIComponent(previousAddr2),
								city1: encodeURIComponent(previousCity),
								state1: encodeURIComponent(previousState),
								zip1: encodeURIComponent(previousZip),
								country1: encodeURIComponent(previousCountry),
								addr12: encodeURIComponent($(this).data("addr1")),
								addr22: encodeURIComponent($(this).data("addr2")),
								city2: encodeURIComponent($(this).data("city")),
								state2: encodeURIComponent($(this).data("state")),
								zip2: encodeURIComponent($(this).data("zip")),
								country2: encodeURIComponent($(this).data("country")),
							},
							dataType: "json",
							success: function(data) {
								if( data[0] >= 0) {
									cell_name = '#miles_'+thisRow.data("action") + '_' +
										( thisRow.data("action") == 'stop' ? thisRow.data("stop") : thisRow.data("shipment") );
										
									$(cell_name).html(data[0] + ' miles <span class="text-success" title="Confirmed via ' + data[2] + '"><span class="glyphicon glyphicon-ok"></span></span><br>' + 
										data[1]  + ' hours<br>' + 
										Math.round((data[1] / 24)*10)/10 + ' days');
									totalMiles += data[0];
									totalHours += data[1];
								} else {
									cell_name = '#miles_'+thisRow.data("action") + '_' +
										( thisRow.data("action") == 'stop' ? thisRow.data("stop") : thisRow.data("shipment") );
									$(cell_name).html('<span class="text-danger" title="Invalid address">Invalid <span class="glyphicon glyphicon-remove"></span></span>');
								}
								
								if( last_by != 0 ) {
									last_by = last_by.addHours(data[1]);
									var est_date = '<strong>Est: ' + $.format.date(last_by, date_format) + '</strong>';
									//console.log( by, last_by, by != 0, by < last_by );
									if( by != 0 && by < last_by ) {
										est_date = '<span class="text-danger">' + est_date + '</span>';
									}
									//console.log(est_date);
									$('#dates_'+thisRow.data("action") + '_' + thisRow.data("shipment") ).html( est_date );
						
								} else {
									$('#dates_'+thisRow.data("action") + '_' + thisRow.data("shipment") ).html( '' );
								}
							}
						});
					}
					previousZip = thisRow.data("zip");
					previousAddr1 = thisRow.data("addr1");
					previousAddr2 = thisRow.data("addr2");
					previousCity = thisRow.data("city");
					previousState = thisRow.data("state");

					if( by != 0 && by > last_by )
						last_by = by;

					locations.push( encodeURIComponent($(this).data("zip")) );

				});
				$('#TOTALS').html('<span id="TOTALMILES">' + totalMiles + '</span> miles total<br>' + totalHours + 
					' hours<br>' + Math.round((totalHours / 24)*10)/10 + ' days');
				
				var origin = '&origin=' + locations.shift();
				var destination = '&destination=' + locations.pop();
				if( locations.length > 0 ) {
					var waypoints = '&waypoints=' + locations.join('|');
				} else {
					var waypoints = '';
				}
				
				// Draw a map
				$('#MAP').replaceWith('<div class="text-right" id="MAP"><iframe src="https://www.google.com/maps/embed/v1/directions?key=' + key + origin + destination + waypoints + '" width="600" height="450" frameborder="0" style="border:0;" allowfullscreen=""></iframe></div>');
				
				// Clear the popup
				$('#update_miles_modal').modal('hide');
			}
			
		    $(".pickdrop .fixed").each(function () {
		         $(this).attr("id", "fixed-" + $(this).index());
		    });
		    
			$(function () {
				$(".pickdrop").sortable({
					tolerance: 'pointer',
					cursor: 'move',
					//revert: "invalid",
					cancel: ".fixed",
					revert: true,
					placeholder: 'row placeholder tile',
					forceHelperSize: true,
					update: function (event, ui) {
						console.log('moved ', ui.item.data("stop"), ' from ', ui.item.data("sequence"), ' to ',$(this).index() + 1);
						//console.log('shipment', ui.item.data('shipment'));

				        $(".pickdrop .fixed").each(function() {
				           var desiredLocation = $(this).attr("id").replace("fixed-","");
				           var currentLocation = $(this).index();
				           while(currentLocation < desiredLocation) {
				             $(this).next().insertBefore(this);
				              currentLocation++;  
				            }
				            while(currentLocation > desiredLocation) {
				             $(this).prev().insertAfter(this);
				              currentLocation--;  
				            }
				        });

						var shipments = new Array();
						var shipments2 = new Array();
						var cancelled = false;
						$(this).children().each(function() {
							var myclass = $(this).attr("class");
						//	console.log('index',$(this).index(), 'stop', $(this).data("stop"), 'shipment', $(this).data("shipment"), 'sequence', $(this).data("sequence"), 'action', $(this).data("action"), ' im_stop_type ', $(this).data("im_stop_type") );
							//, ' id ', $(this).attr("id")
							if( $(this).data("action") == 'drop' || $(this).data("action") == 'dropdock' ) {
								shipments.push($(this).data("shipment"));
							} else if( $(this).data("action") == 'pick' || $(this).data("action") == 'pickdock' ) {
								if( shipments.indexOf($(this).data("shipment")) > -1 ) {
									sadness( "<h4>Can't have drop before a pick for shipment# " + $(this).data("shipment") + "</h4>" );
									$(".pickdrop").sortable('cancel');
									cancelled = true;
								}
							}
							
							if( $(this).data("im_stop_type") == 'dropyard' || $(this).data("im_stop_type") == 'dropdepot' ) {
								shipments2.push($(this).data("shipment"));
							} else if( $(this).data("action") == 'drop' || $(this).data("action") == 'dropdock' ) {
								if( shipments2.indexOf($(this).data("shipment")) > -1 ) {
									sadness("<h4>Can't have <strong>drop in yard</strong> or <strong>drop in depot</strong> before a <strong>drop</strong><br>for shipment# " + $(this).data("shipment") + "</h4>");
								//	alert( "Can't have 'drop in yard' or 'drop in depot' before a drop for shipment " + $(this).data("shipment") );
									$(".pickdrop").sortable('cancel');
									cancelled = true;
								}
								
							}
							
						});
						
						if( ! cancelled ) {
							
							//! Update stops position (SEQUENCE_NO) via ajax
							var stops = new Array();
							$(this).children().each(function() {
								console.log('stop=',$(this).data("stop"), ' seq=', $(this).data("sequence"), ' index=', $(this).index() + 1);
								if( $(this).data("sequence") != $(this).index() + 1 ) {
									// Update DOM data
									$(this).data("sequence", $(this).index() + 1);
									$('#SEQ_' + $(this).data("stop")).text($(this).index() + 1);
									// Add to list of stops to update STOP_CODE-SEQUENCE_NO
									stops.push( $(this).data("stop") + '-' + ($(this).index() + 1) );
								}
							});
							console.log('stops = ', stops);
							if( stops.length > 0 ) {
								// Call the ajax here
								$.ajax({
									url: 'exp_update_stops.php',
									data: {
										load: $("#LOADCODE").text(),	// load
										renumber: stops.join(),			// List of stops
										pw: 'Benedict'
									},
									dataType: "json",
									success: function(data) {
										//console.log(data);
									}
								});
							}
							
							//update_miles();
						}
					}
				});
				function GetPCMAP(){
					$.ajax({
						url: "exp_get_pcmap.php",
						method: 'POST',
						data: {
							getPCMAP : true,
							apikey : pckey,
							stops : stops,
							pin : pins,
						},
						success: function(response) {
							// Handle the response
							$('#PCMAP').replaceWith('<div class="text-right" id="MAP"><iframe src="'+response+'" width="1000" height="800" frameborder="0" style="border:0;" allowfullscreen=""></iframe></div>');
				
						},
						error: function(xhr, status, error) {
							// Handle errors
						}
					});
				}
				
				$('#UPDATE_MILES').click(function(event) {
					event.preventDefault();
					update_miles();
					GetPCMAP();
				});
				
				//update_miles();

			});
		
		});
	//--></script>

<?php
}

require_once( "include/footer_inc.php" );
?>
