<?php 

// $Id: exp_dispatch3.php 5030 2023-04-12 20:31:34Z duncan $
// Assign resouces screen

// Set flag that this is a parent file
define( '_STS_INCLUDE', 1 );

// Setup Session
require_once( "include/sts_session_setup.php" );

require_once( "include/sts_config.php" );

if( ini_get('safe_mode') ){
   // safe mode is on
   ini_set('max_execution_time', 1200);		// Set timeout to 20 minutes
   ini_set('memory_limit', '1024M');
}else{
   // it's not
   set_time_limit(1200);
}
$sts_debug = (isset($_GET['debug'])  || isset($_POST['debug']))  && in_group( EXT_GROUP_DEBUG );
require_once( "include/sts_session_class.php" );

$my_session = sts_session::getInstance( $exspeedite_db, $sts_debug );
$my_session->access_check( $sts_table_access[SHIPMENT_TABLE] );	// Make sure we should be here

$sts_subtitle = "Assign Resources";
require_once( "include/header_inc.php" );

//! SCR# 849 - Team Driver Feature
require_once( "include/sts_setting_class.php" );
$setting_table = sts_setting::getInstance($exspeedite_db, $sts_debug);
$sts_team_driver = $setting_table->get( 'option', 'TEAM_DRIVER' ) == 'true';

//! Initialize variables
$load_str = 0;
$dist_column = 15;
$distances = 'false';

if( isset($_POST['CONTINUE']) && isset($_POST['CODE']) ) {
	require_once( "include/sts_load_class.php" );
	require_once( "include/sts_driver_class.php" );
	if( $sts_debug ) {
		echo "<p>dispatch3 POST = </p>
		<pre>";
		var_dump($_POST);
		echo "</pre>";
	}
	$load_table = sts_load::getInstance($exspeedite_db, $sts_debug);
	
	$check_driver = $load_table->fetch_rows("LOAD_CODE = ".$_POST['CODE'],
		"DRIVER, DRIVER2, CURRENT_STATUS, CARRIER" );
	
	// Pete fix - for when he switches drivers on a load.
	//! SCR# 387 - only of there was a driver before
	if( is_array($check_driver) && count($check_driver) > 0 && 
		isset($check_driver[0]['DRIVER']) &&
		$check_driver[0]['DRIVER'] > 0 &&
		$check_driver[0]['DRIVER'] <> $_POST['DRIVER_CODE'] ) {
		$driver_table = sts_driver::getInstance($exspeedite_db, $sts_debug);
		$dummy = $driver_table->update( $check_driver[0]['DRIVER'], 
			array( 'CURRENT_LOAD' => 0 ) );
		
		//! For Pete, if reset the driver, change state back to entry.
		if( $_POST['DRIVER_CODE'] == 0 && 
			$load_table->state_behavior[$check_driver[0]['CURRENT_STATUS']] == 'dispatch' )
			$load_table->change_state_behavior($_POST['CODE'], 'entry');
	}
	
	//! SCR# 849 - Team Driver Feature
	if( is_array($check_driver) && count($check_driver) > 0 && 
		isset($check_driver[0]['DRIVER2']) &&
		$check_driver[0]['DRIVER2'] > 0 &&
		$check_driver[0]['DRIVER2'] <> $_POST['DRIVER2_CODE'] ) {
		$driver_table = sts_driver::getInstance($exspeedite_db, $sts_debug);
		$dummy = $driver_table->update( $check_driver[0]['DRIVER2'], 
			array( 'CURRENT_LOAD' => 0 ) );
		
		//! For Pete, if reset the driver, change state back to entry.
		if( $_POST['DRIVER2_CODE'] == 0 && 
			$load_table->state_behavior[$check_driver[0]['CURRENT_STATUS']] == 'dispatch' )
			$load_table->change_state_behavior($_POST['CODE'], 'entry');
	}
	
	//! SCR# 380 - if a new carrier is addigned, update default terms
	if( is_array($check_driver) && count($check_driver) > 0 && 
		$_POST['CARRIER_CODE'] > 0 &&
		$check_driver[0]['CARRIER'] <> $_POST['CARRIER_CODE'] ) {
		$load_table->database->get_one_row("
			UPDATE EXP_LOAD
			SET CARRIER_BASE = NULL, CARRIER_FSC = NULL, CARRIER_HANDLING = NULL,
			TERMS = COALESCE((SELECT TERMS FROM EXP_CARRIER
			WHERE CARRIER_CODE = ".$_POST['CARRIER_CODE']."), 0)
			WHERE LOAD_CODE = ".$_POST['CODE'] );
	}
	
	//! Log a message about the changes
	$names = $load_table->fetch_rows("LOAD_CODE = ".$_POST['CODE'],
		"(SELECT CONCAT_WS(' ',FIRST_NAME,LAST_NAME) FROM EXP_DRIVER WHERE DRIVER_CODE = ".(isset($_POST['DRIVER_CODE']) && $_POST['DRIVER_CODE'] > 0 ? $_POST['DRIVER_CODE'] : 0)." LIMIT 1) AS DRIVER_NAME,
		(SELECT CONCAT_WS(' ',FIRST_NAME,LAST_NAME) FROM EXP_DRIVER WHERE DRIVER_CODE = ".(isset($_POST['DRIVER2_CODE']) && $_POST['DRIVER2_CODE'] > 0 ? $_POST['DRIVER2_CODE'] : 0)." LIMIT 1) AS DRIVER2_NAME,
		(SELECT UNIT_NUMBER
		FROM EXP_TRACTOR WHERE TRACTOR_CODE = ".(isset($_POST['TRACTOR_CODE']) && $_POST['TRACTOR_CODE'] > 0 ? $_POST['TRACTOR_CODE'] : 0)." LIMIT 1) AS TRACTOR_NAME,
		(SELECT UNIT_NUMBER
		FROM EXP_TRAILER WHERE TRAILER_CODE = ".(isset($_POST['TRAILER_CODE']) && $_POST['TRAILER_CODE'] > 0 ? $_POST['TRAILER_CODE'] : 0)." LIMIT 1) AS TRAILER_NAME,
		(SELECT CARRIER_NAME
		FROM EXP_CARRIER WHERE CARRIER_CODE = ".(isset($_POST['CARRIER_CODE']) && $_POST['CARRIER_CODE'] > 0 ? $_POST['CARRIER_CODE'] : 0)." LIMIT 1) AS CARRIER_NAME" );
	if( is_array($names) && count($names) == 1 ) {
		$load_table->add_load_status( $_POST['CODE'], 
			'Assign driver='.$names[0]['DRIVER_NAME'].' ('.$_POST['DRIVER_CODE'].')'.
		($sts_team_driver ? 	
			' driver2='.$names[0]['DRIVER2_NAME'].' ('.$_POST['DRIVER2_CODE'].')' : '').
			' tractor='.$names[0]['TRACTOR_NAME'].' ('.$_POST['TRACTOR_CODE'].')'.
			' trailer='.$names[0]['TRAILER_NAME'].' ('.$_POST['TRAILER_CODE'].')'.
			' carrier='.$names[0]['CARRIER_NAME'].' ('.$_POST['CARRIER_CODE'].')' );
	}
	
	$changes = array(
		"DRIVER" => $_POST['DRIVER_CODE'] == "" ? 0 : $_POST['DRIVER_CODE'],
		"TRACTOR" => $_POST['TRACTOR_CODE'] == "" ? 0 : $_POST['TRACTOR_CODE'],
		"TRAILER" => $_POST['TRAILER_CODE'] == "" ? 0 : $_POST['TRAILER_CODE'],
		"CARRIER" => $_POST['CARRIER_CODE'] == "" ? 0 : $_POST['CARRIER_CODE'] );
	
	if( $sts_team_driver ) {
		$changes["DRIVER2"] = (isset($_POST['DRIVER2_CODE']) && $_POST['DRIVER2_CODE'] > 0 ? $_POST['DRIVER2_CODE'] : 0);
	}
		
	//! SCR# 283 - Clear out emails if you change carrier or driver
	if( is_array($check_driver) && count($check_driver) > 0 && 
		((isset($check_driver[0]['DRIVER']) &&
			$check_driver[0]['DRIVER'] <> $_POST['DRIVER_CODE']) ||
		(isset($check_driver[0]['DRIVER2']) &&
			$check_driver[0]['DRIVER2'] <> $_POST['DRIVER2_CODE']) ||
		(isset($check_driver[0]['CARRIER']) &&
			$check_driver[0]['CARRIER'] <> $_POST['CARRIER_CODE']))
		 ) {
		$changes["CARRIER_EMAIL"] = 'NULL';
		$changes["CARRIER_MANIFEST_SENT"] = 'NULL';
	}
	
	$load_table->update($_POST['CODE'], $changes );

	if( $sts_debug ) die;
	reload_page ( "exp_listload.php" );
} else if( isset($_GET['CODE'])) {

	require_once( "include/navbar_inc.php" );

	?>
	
	<div class="container-full" role="main">
	
<?php
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
				<h3>You can no longer change resources for this load.</h3>
				
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

	<form role="form" class="form-horizontal" action="exp_dispatch3.php" 
					method="post" enctype="multipart/form-data" 
					name="dispatch3" id="dispatch3">
	<h2>Assign Resources For Load <span id="LOADCODE"><?php echo $_GET['CODE']; ?></span> 
	<div class="btn-group">
	<a class="btn btn-sm btn-default" href="exp_dispatch2.php?CODE=<?php echo $_GET['CODE']; ?>" ><span class="glyphicon glyphicon-arrow-left"></span> Back</a>
	<a class="btn btn-sm btn-default" href="exp_listload.php"><span class="glyphicon glyphicon-arrow-up"></span> Loads</a>
	<a class="btn btn-sm btn-success" id="LOADS" href="exp_viewload.php?CODE=<?php echo $_GET['CODE']; ?>"><span class="glyphicon glyphicon-eye-open"></span> View</a>
	<button class="btn btn-sm btn-success" type="submit" name="CONTINUE" id="CONTINUE"><span class="glyphicon glyphicon-arrow-right"></span> Save & Continue</button>
	</div>
	</h2>
	
	<?php
	require_once( "include/sts_stop_class.php" );
	require_once( "include/sts_result_class.php" );
	require_once( "include/sts_shipment_class.php" );
	require_once( "include/sts_shipment_load_class.php" );
	require_once( "include/sts_setting_class.php" );
	require_once( "include/sts_carrier_class.php" );


	$setting_table = sts_setting::getInstance($exspeedite_db, $sts_debug);
	$multi_company = $setting_table->get("option", "MULTI_COMPANY") == 'true';
	$distances = $setting_table->get("option", "RESOURCE_DISTANCES") == 'true' ? 'true' : 'false';

	$dist_column = 15;
	if( ! $multi_company ) {
		unset($sts_result_stops_lj_layout['SS_NUMBER']);
		$dist_column--;
	}

	$stop_table = sts_stop::getInstance($exspeedite_db, $sts_debug);
	$shipment_table = sts_shipment::getInstance($exspeedite_db, $sts_debug);
	$carrier_table = sts_carrier::getInstance($exspeedite_db, $sts_debug);
	$ins_req = $carrier_table->get_ins_req( $_GET['CODE'] );
	
	$pallets_weight = $shipment_table->fetch_rows("LOAD_CODE = ".$_GET['CODE'], "SHIPMENT_CODE, PALLETS, WEIGHT" );
	$num_shipments = count($pallets_weight);
	$shipments = array();
	$shipment_pallets = array();
	$shipment_weight = array();
	if( $pallets_weight ) {
		foreach( $pallets_weight as $row ) {
			$shipments[$row['SHIPMENT_CODE']] = $row['SHIPMENT_CODE'];
			$shipment_pallets[$row['SHIPMENT_CODE']] = $row['PALLETS'];
			$shipment_weight[$row['SHIPMENT_CODE']] = $row['WEIGHT'];
		}
	}

	$stops = $stop_table->fetch_rows("LOAD_CODE = ".$_GET['CODE'], "STOP_CODE, STOP_TYPE, SHIPMENT" );
	if( $stops && is_array($stops) ) {
		$current_pallets	= 0;
		$current_weight		= 0;
		$max_pallets		= 0;
		$max_weight			= 0;
		foreach( $stops as $stop ) {
			if( in_array($stop['STOP_TYPE'], array('pick', 'drop', 'pickdock', 'dropdock')) &&
				! isset($shipments[$stop['SHIPMENT']]) ) {
				// Error, stop set, but shipment not in load. Delete stop.
				$result = $stop_table->delete_row( "STOP_CODE = ".$stop['STOP_CODE'] );
			} else
			if( $stop['STOP_TYPE'] == 'pick' ) {
				$current_pallets += isset($shipment_pallets[$stop['SHIPMENT']]) ? $shipment_pallets[$stop['SHIPMENT']] : 0;
				$current_weight += isset($shipment_weight[$stop['SHIPMENT']]) ? $shipment_weight[$stop['SHIPMENT']] : 0;
			} else if( $stop['STOP_TYPE'] == 'drop' ) {
				$current_pallets -= isset($shipment_pallets[$stop['SHIPMENT']]) ? $shipment_pallets[$stop['SHIPMENT']] : 0;
				$current_weight -= isset($shipment_weight[$stop['SHIPMENT']]) ? $shipment_weight[$stop['SHIPMENT']] : 0;
			}
			$max_pallets = max( $max_pallets, $current_pallets );
			$max_weight = max( $max_weight, $current_weight );
		}

		if( true || $sts_debug ) {
			$stop_table_lj = new sts_stop_left_join($exspeedite_db, $sts_debug);
			$rslt = new sts_result( $stop_table_lj, "LOAD_CODE = ".$_GET['CODE'], $sts_debug, $sts_profiling );

			// If not yet got a return stop, you can add one.
			/*
			if( ! $stop_table->got_return_stop( $_GET['CODE'] ) ) {
				$sts_result_stops_edit['add'] = 'exp_add_return_stop.php?CODE='.$_GET['CODE'];
				$sts_result_stops_edit['addbutton'] = 'Add Return Stop';
			} else {
				$sts_result_stops_edit['cancel'] = 'exp_del_return_stop.php?CODE='.$_GET['CODE'];
				$sts_result_stops_edit['cancelbutton'] = 'Delete Return Stop';
			}
			*/
			$_SESSION["BACK"] = 'exp_dispatch3.php?CODE='.$_GET['CODE'];
			//! SCR# 639 - Warn about C-TPAT
			$ctpat = $exspeedite_db->get_one_row("
				SELECT INSURANCE_CURRENCY, HOME_CURRENCY() AS HC,
					SUM(COALESCE(CTPAT_REQUIRED,0)) AS CTPAT_REQUIRED
				FROM EXP_SHIPMENT, EXP_CLIENT
				WHERE BILLTO_CLIENT_CODE = CLIENT_CODE
				AND LOAD_CODE = ".$_GET['CODE']."
				GROUP BY INSURANCE_CURRENCY, HOME_CURRENCY()");
			
			$ctpat_required = false;
			$reqs = [];
			if( is_array($ctpat) && isset($ctpat["CTPAT_REQUIRED"]) && $ctpat["CTPAT_REQUIRED"] ) {
				$ctpat_required = true;
				$reqs[] = ' <a href="https://en.wikipedia.org/wiki/Customs-Trade_Partnership_Against_Terrorism" target="_blank" class="tip" title="Customs-Trade Partnership Against Terrorism"><img src="images/logo-ctpat.jpg" alt="logo-ctpat" height="32px" /></a> Required';
			}
			
			$ic = '';
			if( is_array($ctpat) && !empty($ctpat["INSURANCE_CURRENCY"]) ) {
				$ic = $ctpat["INSURANCE_CURRENCY"].' ';
				$hc = $ctpat["HC"].' ';
			}
			
			// Display insurance requirements
			$ir = explode(', ', $ins_req);
			//echo "<pre>ins_req\n";
			//var_dump($ir);
			//echo "</pre>";
			$asof = trim($ir[0], "'");
			$reqs[] = date("m/d/Y", strtotime($asof) );
			if( is_array($ir) && count($ir) > 3 && $ir[1] > 0 )
				$reqs[] = 'general='.$hc.'$'.number_format($ir[1], 0);
			if( is_array($ir) && count($ir) > 3 && $ir[2] > 0 )
				$reqs[] = 'auto='.$hc.'$'.number_format($ir[2], 0);
			if( is_array($ir) && count($ir) > 3 && $ir[3] > 0 )
				$reqs[] = 'cargo='.$hc.'$'.number_format($ir[3], 0);
			
			if( count($reqs) > 0 )
				$sts_result_stops_edit['title'] .= ' ('.implode(', ', $reqs).')';
				
			$sts_result_stops_lj_layout['STOP_ETA']['format'] = 'timestamp-s';
			$sts_result_stops_lj_layout['STOP_ETA']['length'] = 100;
			
			echo $rslt->render( $sts_result_stops_lj_layout, $sts_result_stops_edit );
			
			// For drop and hook (TL) we can fill in the trailer
			if( $num_shipments == 1 && $stops[0]["STOP_TYPE"] == 'pickdock' ) {
				$shipment_load_table = sts_shipment_load::getInstance($exspeedite_db, $sts_debug);
				$last_load = $shipment_load_table->last_load( $stops[0]["SHIPMENT"] );
				
				// Get trailer from last load
				$trailer = $stop_table->fetch_rows( "LOAD_CODE = ".$last_load[0]['LOAD_CODE']."
						AND STOP_TYPE = 'dropdock'", "COALESCE(TRAILER, (SELECT TRAILER FROM EXP_LOAD 
						WHERE EXP_LOAD.LOAD_CODE = EXP_STOP.LOAD_CODE) ) AS PREV_TRAILER" );
				if( isset($trailer)	&& is_array($trailer) && count($trailer) == 1 &&
					! empty($trailer[0]["PREV_TRAILER"]) ) {
					// update trailer for this load
					$load_table->update_row("LOAD_CODE = ".$_GET['CODE']." AND
						COALESCE( TRAILER, 0 ) = 0", array( 
						array("field" => "TRAILER", 
						"value" => $trailer[0]["PREV_TRAILER"] )
					) );
				}
			}
		}
		
		echo "<h4>".count($pallets_weight)." shipments, ".count($stops)." stops, max_pallets = $max_pallets, max_weight = $max_weight LB</h4>";
	} else {
		echo "<h4>No stops!</h4>";
	}
	echo '<div id="warnings"></div>';
	
	$resources = $load_table->fetch_rows("LOAD_CODE = ".$_GET['CODE'], 
		"DRIVER, DRIVER2, TRACTOR, TRAILER, CARRIER, 
		(SELECT CONCAT_WS(' ',FIRST_NAME,LAST_NAME)
		FROM EXP_DRIVER WHERE DRIVER = DRIVER_CODE) AS DRIVER_NAME,
		(SELECT CONCAT_WS(' ',FIRST_NAME,LAST_NAME)
		FROM EXP_DRIVER WHERE DRIVER2 = DRIVER_CODE) AS DRIVER2_NAME,
		(SELECT UNIT_NUMBER
		FROM EXP_TRACTOR WHERE TRACTOR = TRACTOR_CODE) AS TRACTOR_NAME,
		(SELECT UNIT_NUMBER
		FROM EXP_TRAILER WHERE TRAILER = TRAILER_CODE) AS TRAILER_NAME,
		(SELECT CARRIER_NAME
		FROM EXP_CARRIER WHERE CARRIER = CARRIER_CODE) AS CARRIER_NAME" );
	if( $resources && is_array($resources) )
		$resource = $resources[0];
	else
		$resource = false;

	if( $sts_debug ) echo '
	<input name="debug" type="hidden" value="on">';
	echo '
	<input name="CODE" type="hidden" value="'.$_GET['CODE'].'">
	<div class="form-group">


		<div class="col-sm-4 col-sm-offset-1 well well-sm" id="DRIVER_WELL">
			<div class="form-group tighter">
				<div class="col-sm-4">
					<img class="center-block" src="images/driver_icon.png"  height="80" border="0">
				</div>
				<div class="col-sm-8">
					<h4><button class="btn btn-sm btn-default" id="PICK_DRIVER" type="button"><strong><span class="glyphicon glyphicon-plus"></span> Driver</strong></button> <button id="CLEAR_DRIVER" class="close" type="button" hidden>&times;</button></h4>
					<input class="form-control" name="DRIVER" id="DRIVER" type="text" placeholder="Driver"
					'.($resource && isset($resource['DRIVER_NAME']) ? ' value="'.$resource['DRIVER_NAME'].'"' : '').'>
					<input name="DRIVER_CODE" id="DRIVER_CODE" type="hidden" value="'.($resource && isset($resource['DRIVER']) ? $resource['DRIVER'] : '').'">
				</div>
			</div>
			<div id="DRIVER_MESSAGE"></div>
		</div>


		<div class="col-sm-4 col-sm-offset-2 well well-sm" id="TRACTOR_WELL">
			<div class="form-group tighter">
				<div class="col-sm-4">
					<img class="center-block" src="images/tractor_icon.png"  height="80" border="0">
				</div>
				<div class="col-sm-8">
					<h4><button class="btn btn-sm btn-default" id="PICK_TRACTOR" type="button"><strong><span class="glyphicon glyphicon-plus"></span> Tractor</strong></button> <button id="CLEAR_TRACTOR" class="close" type="button" hidden>&times;</button></h4>
					<input class="form-control" name="TRACTOR" id="TRACTOR" type="text" placeholder="Tractor"
					'.($resource && isset($resource['TRACTOR_NAME']) ? ' value="'.$resource['TRACTOR_NAME'].'"' : '').'>
					<input name="TRACTOR_CODE" id="TRACTOR_CODE" type="hidden" value="'.($resource && isset($resource['TRACTOR']) ? $resource['TRACTOR'] : '').'">
				</div>
			</div>
			<div id="TRACTOR_MESSAGE"></div>
		</div>
	</div>
	<div class="form-group">


		<div class="col-sm-4 well col-sm-offset-1 well-sm" id="TRAILER_WELL">
			<div class="form-group tighter">
				<div class="col-sm-4">
					<img class="center-block" src="images/trailer2_icon.png"  height="80" border="0">
				</div>
				<div class="col-sm-8">
					<h4><button class="btn btn-sm btn-default" id="PICK_TRAILER" type="button"><strong><span class="glyphicon glyphicon-plus"></span> Trailer</strong></button> <button id="CLEAR_TRAILER" class="close" type="button" hidden>&times;</button></h4>
					<input class="form-control" name="TRAILER" id="TRAILER" type="text" placeholder="Trailer"
					'.($resource && isset($resource['TRAILER_NAME']) ? ' value="'.$resource['TRAILER_NAME'].'"' : '').'>
					<input name="TRAILER_CODE" id="TRAILER_CODE" type="hidden" value="'.($resource && isset($resource['TRAILER']) ? $resource['TRAILER'] : '').'">
				</div>
			</div>
			<div id="TRAILER_MESSAGE"></div>
		</div>


		<div class="col-sm-4 col-sm-offset-2 well well-sm" id="CARRIER_WELL">
		<div class="col-sm-4">
		<img class="center-block" src="images/carrier2_icon.png"  height="80" border="0">
		</div>
		<div class="col-sm-8">
			<h4><button class="btn btn-sm btn-default" id="PICK_CARRIER" type="button"><strong><span class="glyphicon glyphicon-plus"></span> Carrier</strong></button> <button id="CLEAR_CARRIER" class="close" type="button" hidden>&times;</button></h4>
			<input class="form-control" name="CARRIER" id="CARRIER" type="text" placeholder="Carrier"
			'.($resource && isset($resource['CARRIER_NAME']) ? ' value="'.$resource['CARRIER_NAME'].'"' : '').'>
			<input name="CARRIER_CODE" id="CARRIER_CODE" type="hidden" value="'.($resource && isset($resource['CARRIER']) ? $resource['CARRIER'] : '').'">
		</div>
		</div>
	</div>
	
	';
	
//! SCR# 849 - Team Driver Feature
if( $sts_team_driver ) {
	echo '
	<div class="form-group">
		
		<div class="col-sm-4 col-sm-offset-1 well well-sm" id="DRIVER2_WELL">
			<div class="form-group tighter">
				<div class="col-sm-4">
					<img class="center-block" src="images/driver_icon.png"  height="80" border="0">
				</div>
				<div class="col-sm-8">
					<h4><button class="btn btn-sm btn-default" id="PICK_DRIVER2" type="button"><strong><span class="glyphicon glyphicon-plus"></span> Driver 2</strong></button> <button id="CLEAR_DRIVER2" class="close" type="button" hidden>&times;</button></h4>
					<input class="form-control" name="DRIVER2" id="DRIVER2" type="text" placeholder="Driver 2"
					'.($resource && isset($resource['DRIVER2_NAME']) ? ' value="'.$resource['DRIVER2_NAME'].'"' : '').'>
					<input name="DRIVER2_CODE" id="DRIVER2_CODE" type="hidden" value="'.($resource && isset($resource['DRIVER2']) ? $resource['DRIVER2'] : '').'">
				</div>
			</div>
			<div id="DRIVER2_MESSAGE"></div>
		</div>
	</div>
	';
}

echo '		</form>
';
	
	$load_str = $_GET['CODE'];
	if( $multi_company ) {
		$office = $load_table->fetch_rows("LOAD_CODE = ".$_GET['CODE'],
			"(SELECT OFFICE_NAME FROM EXP_OFFICE
				WHERE EXP_OFFICE.OFFICE_CODE = EXP_LOAD.OFFICE_CODE) AS OFFICE" );
		if( is_array($office) and count($office) == 1 ) {
			$load_str .= ', '.$office[0]["OFFICE"];
		}
	}
	}

} else {
	require_once( "include/sts_email_class.php" );
	require_once( "include/navbar_inc.php" );

	echo '<div class="container-full" role="main">
	<h1>How did I get here?</h1>
	<p>Something strange happened. Sending diagnostic to support...</p>
	<p>HTTP_REFERER = '.(isset($_SERVER["HTTP_REFERER"]) ? $_SERVER["HTTP_REFERER"] : '').'</p>
	<br>
	<p><a class="btn btn-default" href="index.php">Go back</a></p>
	</div>';
	$email = sts_email::getInstance($exspeedite_db, $sts_debug);
	$email->send_alert('exp_dispatch3: How did I get here? - no CODE= parameter');

}

?>
	<div class="modal fade bs-example-modal-lg" tabindex="-1" role="dialog" aria-labelledby="mySmallModalLabel" aria-hidden="true" id="carrier_modal">
	  <div class="modal-dialog modal-lg">
		<div class="modal-content">
		<div class="modal-header">
			 <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
			<h4 class="modal-title" id="myModalLabel"><span class="text-success"><strong>Select Carrier For Load <?php echo $load_str; ?>...</strong></span></h4>
		</div>
		<div class="modal-body">
			<p class="text-center"><img src="images/loading.gif" alt="loading" width="125" height="125" /></p>
		</div>
		</div>
		</div>
	</div>

	<div class="modal fade bs-example-modal-lg" tabindex="-1" role="dialog" aria-labelledby="mySmallModalLabel" aria-hidden="true" id="driver_modal">
	  <div class="modal-dialog modal-lg">
		<div class="modal-content">
		<div class="modal-header">
			 <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
			<h4 class="modal-title" id="myModalLabel"><span class="text-success"><strong>Select Driver For Load <?php echo $load_str; ?>...</strong></span></h4>
		</div>
		<div class="modal-body">
			<p class="text-center"><img src="images/loading.gif" alt="loading" width="125" height="125" /></p>
		</div>
		</div>
		</div>
	</div>

	<div class="modal fade bs-example-modal-lg" tabindex="-1" role="dialog" aria-labelledby="mySmallModalLabel" aria-hidden="true" id="tractor_modal">
	  <div class="modal-dialog modal-lg">
		<div class="modal-content">
		<div class="modal-header">
			 <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
			<h4 class="modal-title" id="myModalLabel"><span class="text-success"><strong>Select Tractor For Load <?php echo $load_str; ?>...</strong></span></h4>
		</div>
		<div class="modal-body">
			<p class="text-center"><img src="images/loading.gif" alt="loading" width="125" height="125" /></p>
		</div>
		</div>
		</div>
	</div>

	<div class="modal fade bs-example-modal-lg" tabindex="-1" role="dialog" aria-labelledby="mySmallModalLabel" aria-hidden="true" id="trailer_modal">
	  <div class="modal-dialog modal-lg">
		<div class="modal-content">
		<div class="modal-header">
			 <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
			<h4 class="modal-title" id="myModalLabel"><span class="text-success"><strong>Select Trailer For Load <?php echo $load_str; ?>...</strong></span></h4>
		</div>
		<div class="modal-body">
			<p class="text-center"><img src="images/loading.gif" alt="loading" width="125" height="125" /></p>
		</div>
		</div>
		</div>
	</div>


</div>

	<script language="JavaScript" type="text/javascript"><!--
		$(document).ready( function () {
		
			var load = <?php echo (isset($_GET['CODE']) ? $_GET['CODE'] : 0); ?>;

			function check_carrier() {
				var url = 'exp_check_carrier_load.php?code=Watch&load='+load+'&carrier=';
				
				if( $('input#CARRIER_CODE').val() > 0 ) {
					$.get( url+$('input#CARRIER_CODE').val(), function( data ) {
						$( "#warnings" ).html( data );
					});
				} else {
					$( "#warnings" ).html('');
				}
			}
			
			var table = $('.display').dataTable({
		        //"bLengthChange": false,
		        "bFilter": true,
		        "bSort": false,
		        "bInfo": false,
				"bAutoWidth": false,
				//"bProcessing": true, 
				"sScrollX": "100%",
				"sScrollY": "300px",
				//"sScrollXInner": "120%",
		        //"lengthMenu": [[-1, 25, 50], ["All", 25, 50]],
				"bPaginate": false,
				"bScrollCollapse": true,
				"bSortClasses": false		
			});
			
			$.ajax({
				url: 'exp_update_distances.php',
				data: {
					CODE: encodeURIComponent(<?php echo (isset($_GET['CODE']) ? $_GET['CODE'] : 0); ?>),
					PW: "Buntzen",
				},
				dataType: "json",
				success: function(data) {
					//console.log(data);
					$.each(data, function(row) {
						if( $(this)[0]["STOP_DISTANCE"] ) {
							//console.log($(this)[0]["SEQUENCE_NO"], $(this)[0]["STOP_DISTANCE"] );
							table.fnUpdate( $(this)[0]["STOP_DISTANCE"], $(this)[0]["SEQUENCE_NO"] - 1, <?php echo $dist_column; ?>);
						}
					});
				}
			});					


			
			//----------------------------------------------------------------------
			var DRIVER = new Bloodhound({
			  name: 'DRIVER',
			  remote: {
				  url: 'exp_suggest_resource.php?code=Staples&load=<?php echo (isset($_GET['CODE']) ? $_GET['CODE'] : 0); ?>&resource=driver&query=%QUERY',
				  wildcard: '%QUERY'
				  },
			  datumTokenizer: Bloodhound.tokenizers.obj.whitespace('RESOURCE_NAME'),
			  queryTokenizer: Bloodhound.tokenizers.whitespace
			});
			
			DRIVER.initialize();

			$('#DRIVER').typeahead(null, {
			  name: 'DRIVER',
			  minLength: 2,
			  limit: 10,
			  highlight: true,
			  display: 'RESOURCE_NAME',
			  source: DRIVER,
			    templates: {
			    suggestion: Handlebars.compile(
			      '<p><strong>{{RESOURCE_NAME}}</strong> ({{DRIVER_LABEL}}) – {{RESOURCE_EXTRA}}</p>'
			    )
			  }

			});
			
			function check_driver() {
				$('.DRIVER_WARN').remove();
				if( <?php echo $distances; ?> && $('input#DRIVER_CODE').val() != '') {
					$.ajax({
						url: 'exp_driver_available.php',
						data: {
							CODE: 'Autumn',
							DRIVER: encodeURIComponent($('input#DRIVER_CODE').val()),
							LOAD: encodeURIComponent(<?php echo (isset($_GET['CODE']) ? $_GET['CODE'] : 0); ?>),
						},
						dataType: "json",
						success: function(data) {
							if( data == false ) {
								$( '<div class="alert alert-danger tighter tighter2 DRIVER_WARN" role="alert">' +
								'<span class="glyphicon glyphicon-warning-sign"></span>  is <a href="exp_listvacation.php" target="_blank">on vacation</a></div>' ).insertAfter( $('#DRIVER_MESSAGE') );
							} else {
								$.ajax({
									url: 'exp_asset_distance.php',
									data: {
										CODE: 'Switching',
										LOAD: encodeURIComponent(<?php echo (isset($_GET['CODE']) ? $_GET['CODE'] : 0); ?>),
										DRIVER: encodeURIComponent($('input#DRIVER_CODE').val()),
									},
									dataType: "json",
									success: function(data) {
										if( typeof(data.STATUS) != "undefined" ) {
											$( '<div class="alert alert-' +
											data.STATUS + ' tighter tighter2 DRIVER_WARN" role="alert">' +
											'<span class="glyphicon glyphicon-warning-sign"></span> ' +
											data.MESSAGE + '</div>' ).insertAfter( $('#DRIVER_MESSAGE') );
										}
									}
								});					
							}
						}
					});
				}					
				
			}
	
	<?php if($sts_team_driver) { ?>
	//! SCR# 849 - Team Driver Feature
			//----------------------------------------------------------------------
			var DRIVER2 = new Bloodhound({
			  name: 'DRIVER2',
			  remote: {
				  url: 'exp_suggest_resource.php?code=Staples&load=<?php echo (isset($_GET['CODE']) ? $_GET['CODE'] : 0); ?>&resource=driver&query=%QUERY',
				  wildcard: '%QUERY'
				  },
			  datumTokenizer: Bloodhound.tokenizers.obj.whitespace('RESOURCE_NAME'),
			  queryTokenizer: Bloodhound.tokenizers.whitespace
			});
			
			DRIVER2.initialize();

			$('#DRIVER2').typeahead(null, {
			  name: 'DRIVER2',
			  minLength: 2,
			  limit: 10,
			  highlight: true,
			  display: 'RESOURCE_NAME',
			  source: DRIVER2,
			    templates: {
			    suggestion: Handlebars.compile(
			      '<p><strong>{{RESOURCE_NAME}}</strong> ({{DRIVER_LABEL}}) – {{RESOURCE_EXTRA}}</p>'
			    )
			  }

			});
			
			function check_driver2() {
				$('.DRIVER2_WARN').remove();
				if( <?php echo $distances; ?> && $('input#DRIVER2_CODE').val() != '') {
					$.ajax({
						url: 'exp_driver_available.php',
						data: {
							CODE: 'Autumn',
							DRIVER: encodeURIComponent($('input#DRIVER2_CODE').val()),
							LOAD: encodeURIComponent(<?php echo (isset($_GET['CODE']) ? $_GET['CODE'] : 0); ?>),
						},
						dataType: "json",
						success: function(data) {
							if( data == false ) {
								$( '<div class="alert alert-danger tighter tighter2 DRIVER2_WARN" role="alert">' +
								'<span class="glyphicon glyphicon-warning-sign"></span>  is <a href="exp_listvacation.php" target="_blank">on vacation</a></div>' ).insertAfter( $('#DRIVER_MESSAGE') );
							} else {
								$.ajax({
									url: 'exp_asset_distance.php',
									data: {
										CODE: 'Switching',
										LOAD: encodeURIComponent(<?php echo (isset($_GET['CODE']) ? $_GET['CODE'] : 0); ?>),
										DRIVER: encodeURIComponent($('input#DRIVER2_CODE').val()),
									},
									dataType: "json",
									success: function(data) {
										if( typeof(data.STATUS) != "undefined" ) {
											$( '<div class="alert alert-' +
											data.STATUS + ' tighter tighter2 DRIVER2_WARN" role="alert">' +
											'<span class="glyphicon glyphicon-warning-sign"></span> ' +
											data.MESSAGE + '</div>' ).insertAfter( $('#DRIVER2_MESSAGE') );
										}
									}
								});					
							}
						}
					});
				}					
				
			}
	
	<?php } ?>
			
			function check_tractor() {
				$('.TRACTOR_WARN').remove();
				if( <?php echo $distances; ?> && $('input#TRACTOR_CODE').val() != '') {
					$.ajax({
						url: 'exp_asset_distance.php',
						data: {
							CODE: 'Switching',
							LOAD: encodeURIComponent(<?php echo (isset($_GET['CODE']) ? $_GET['CODE'] : 0); ?>),
							TRACTOR: encodeURIComponent($('input#TRACTOR_CODE').val()),
						},
						dataType: "json",
						success: function(data) {
							if( typeof(data.STATUS) != "undefined" ) {
								$( '<div class="alert alert-' +
								data.STATUS + ' tighter tighter2 TRACTOR_WARN" role="alert">' +
								'<span class="glyphicon glyphicon-warning-sign"></span> ' +
								data.MESSAGE + '</div>' ).insertAfter( $('#TRACTOR_MESSAGE') );
							}
						}
					});
				}				
				
			}
			
			function check_trailer() {
				$('.TRAILER_WARN').remove();
				if( <?php echo $distances; ?> && $('input#TRAILER_CODE').val() != '') {
					$.ajax({
						url: 'exp_asset_distance.php',
						data: {
							CODE: 'Switching',
							LOAD: encodeURIComponent(<?php echo (isset($_GET['CODE']) ? $_GET['CODE'] : 0); ?>),
							TRAILER: encodeURIComponent($('input#TRAILER_CODE').val()),
						},
						dataType: "json",
						success: function(data) {
							if( typeof(data.STATUS) != "undefined" ) {
								$( '<div class="alert alert-' +
								data.STATUS + ' tighter tighter2 TRAILER_WARN" role="alert">' +
								'<span class="glyphicon glyphicon-warning-sign"></span> ' +
								data.MESSAGE + '</div>' ).insertAfter( $('#TRAILER_MESSAGE') );
							}
						}
					});
				}				
				
			}
			
			$('#DRIVER').bind('typeahead:selected', function(obj, datum, name) {
				$('input#DRIVER_CODE').val(datum.RESOURCE_CODE);
				if(datum.DEFAULT_TRACTOR != '') {
					$('input#TRACTOR').val(datum.TRACTOR_NUMBER).change();
					$('input#TRACTOR_CODE').val(datum.DEFAULT_TRACTOR).change();
					$('#CLEAR_TRACTOR').prop('hidden',false);
					check_tractor();
				}
				// May need to remove this.
				if(datum.DEFAULT_TRAILER && datum.DEFAULT_TRAILER != '' && $('input#TRAILER').val() == '') {
					//console.log("DT:", datum.DEFAULT_TRAILER );
					$('input#TRAILER').val(datum.TRAILER_NUMBER).change();
					$('input#TRAILER_CODE').val(datum.DEFAULT_TRAILER).change();
					$('#CLEAR_TRAILER').prop('hidden',false);
				}
				$('#CLEAR_DRIVER').prop('hidden',false);
				$('#CARRIER_WELL').prop('hidden', 'hidden');
				
				check_driver();
			});

			$('#CLEAR_DRIVER').click(function() {
				$('#DRIVER').val('').change();
				$('#DRIVER_WARN').remove();
				$('input#DRIVER_CODE').val('').change();
				$('#CLEAR_DRIVER').prop('hidden', 'hidden');
				if( $('#TRACTOR').val() == '' )
					$('#CARRIER_WELL').prop('hidden',false);
				check_driver();
			});

	<?php if($sts_team_driver) { ?>
	//! SCR# 849 - Team Driver Feature
			$('#DRIVER2').bind('typeahead:selected', function(obj, datum, name) {
				$('input#DRIVER2_CODE').val(datum.RESOURCE_CODE);
				$('#CLEAR_DRIVER2').prop('hidden',false);
				$('#CARRIER_WELL').prop('hidden', 'hidden');
				
				check_driver2();
			});

			$('#CLEAR_DRIVER2').click(function() {
				$('#DRIVER2').val('').change();
				$('#DRIVER_WARN2').remove();
				$('input#DRIVER_CODE2').val('').change();
				$('#CLEAR_DRIVER2').prop('hidden', 'hidden');
				if( $('#TRACTOR').val() == '' )
					$('#CARRIER_WELL').prop('hidden',false);
				check_driver2();
			});

	<?php } ?>			
			//----------------------------------------------------------------------
			var TRACTOR = new Bloodhound({
			  name: 'TRACTOR',
			  remote: {
				  url: 'exp_suggest_resource.php?code=Staples&load=<?php echo (isset($_GET['CODE']) ? $_GET['CODE'] : 0); ?>&resource=tractor&query=%QUERY',
				  wildcard: '%QUERY'
				  },
			  datumTokenizer: Bloodhound.tokenizers.obj.whitespace('RESOURCE_NAME'),
			  queryTokenizer: Bloodhound.tokenizers.whitespace
			});
			
			TRACTOR.initialize();

			$('#TRACTOR').typeahead(null, {
			  name: 'TRACTOR',
			  minLength: 2,
			  limit: 10,
			  highlight: true,
			  display: 'RESOURCE_NAME',
			  source: TRACTOR,
			    templates: {
			    suggestion: Handlebars.compile(
			      '<p><strong>{{RESOURCE_NAME}}</strong> – {{RESOURCE_EXTRA}}</p>'
			    )
			  }

			});
			
			$('#TRACTOR').bind('typeahead:selected', function(obj, datum, name) {
				$('input#TRACTOR_CODE').val(datum.RESOURCE_CODE).change();;
				$('#CLEAR_TRACTOR').prop('hidden',false);
				$('#CARRIER_WELL').prop('hidden', 'hidden');
				check_tractor();
			});
			
			$('#CLEAR_TRACTOR').click(function() {
				$('#TRACTOR').val('').change();
				$('input#TRACTOR_CODE').val('').change();
				$('#CLEAR_TRACTOR').prop('hidden', 'hidden');
				if( $('#DRIVER').val() == '' )
					$('#CARRIER_WELL').prop('hidden',false);
				check_tractor();
			});
			
			//----------------------------------------------------------------------
			var TRAILER = new Bloodhound({
			  name: 'TRAILER',
			  remote: {
				  url: 'exp_suggest_resource.php?code=Staples&load=<?php echo (isset($_GET['CODE']) ? $_GET['CODE'] : 0); ?>&resource=trailer&query=%QUERY',
				  wildcard: '%QUERY'
				  },
			  datumTokenizer: Bloodhound.tokenizers.obj.whitespace('RESOURCE_NAME'),
			  queryTokenizer: Bloodhound.tokenizers.whitespace

			});
			
			TRAILER.initialize();

			$('#TRAILER').typeahead(null, {
			  name: 'TRAILER',
			  minLength: 2,
			  limit: 10,
			  highlight: true,
			  display: 'RESOURCE_NAME',
			  source: TRAILER,
			    templates: {
				empty: function(val){
					return '<p class="bg-danger text-danger"><strong>Unrecognized Trailer</strong> <a id="ACT" onclick="return $.doACT('+val.query+')" class="btn btn-sm btn-danger" target="blank"><span class="glyphicon glyphicon-plus"></span> Add Trailer</a></p>';
			    },
			    suggestion: Handlebars.compile(
			      '<p><strong>{{RESOURCE_NAME}}</strong> – {{RESOURCE_EXTRA}}</p>'
			    )
			  }
			});

			$.doACT = function( code ) {
				console.log('doACT ', code );

				$.ajax({
					url: 'exp_add_carrier_trailer.php',
					data: {
						CODE: encodeURIComponent(code),
						PW: "Gardening",
					},
					dataType: "json",
					success: function(data) {
						console.log('doACT, success: ', data);
						$('#TRAILER').typeahead('close');
						$('#TRAILER_CODE').val(data);
					}
				});
			}

			
			
			$('#TRAILER').bind('typeahead:selected', function(obj, datum, name) {
				$('input#TRAILER_CODE').val(datum.RESOURCE_CODE).change();
				$('#CLEAR_TRAILER').prop('hidden',false);
				check_trailer();
			});
			
			$('#CLEAR_TRAILER').click(function() {
				$('#TRAILER').val('').change();
				$('input#TRAILER_CODE').val('').change();
				$('#CLEAR_TRAILER').prop('hidden', 'hidden');
				check_trailer();
			});
			
			//----------------------------------------------------------------------
			var CARRIER = new Bloodhound({
			  name: 'CARRIER',
			  remote: {
				  url: 'exp_suggest_resource.php?code=Staples&load=<?php echo (isset($_GET['CODE']) ? $_GET['CODE'] : 0); ?>&resource=carrier&query=%QUERY',
				  wildcard: '%QUERY'
				  },
			  datumTokenizer: Bloodhound.tokenizers.obj.whitespace('RESOURCE_NAME'),
			  queryTokenizer: Bloodhound.tokenizers.whitespace
			});
			
			CARRIER.initialize();

			$('#CARRIER').typeahead(null, {
			  name: 'CARRIER',
			  minLength: 2,
			  limit: 10,
			  highlight: true,
			  display: 'RESOURCE_NAME',
			  source: CARRIER,
			    templates: {
			    suggestion: Handlebars.compile(
			      '<p><strong>{{RESOURCE_NAME}}</strong> – {{RESOURCE_EXTRA}}</p>'
			    )
			  }

			});
			
			$('#CARRIER').bind('typeahead:selected', function(obj, datum, name) {
				$('input#CARRIER_CODE').val(datum.RESOURCE_CODE);
				$('#CLEAR_CARRIER').prop('hidden',false);
				$('#DRIVER_WELL').prop('hidden', 'hidden');
				$('#DRIVER2_WELL').prop('hidden', 'hidden');
				$('#TRACTOR_WELL').prop('hidden', 'hidden');
				check_carrier();
			});
			
			$('#CLEAR_CARRIER').click(function() {
				$('#CARRIER').val('');
				$('input#CARRIER_CODE').val('');
				$('#CLEAR_CARRIER').prop('hidden', 'hidden');
				$('#DRIVER_WELL').prop('hidden',false);
				$('#DRIVER2_WELL').prop('hidden',false);
				$('#TRACTOR_WELL').prop('hidden',false);
				check_carrier();
			});
			
			if( $('#DRIVER').val() != '' ) {
				$('#CLEAR_DRIVER').prop('hidden',false);
				$('#CARRIER_WELL').prop('hidden', 'hidden');
			}
			
	<?php if($sts_team_driver) { ?>
	//! SCR# 849 - Team Driver Feature
			if( $('#DRIVER2').val() != '' ) {
				$('#CLEAR_DRIVER2').prop('hidden',false);
			}
	<?php } ?>			
			
			if( $('#TRACTOR').val() != '' ) {
				$('#CLEAR_TRACTOR').prop('hidden',false);
				$('#CARRIER_WELL').prop('hidden', 'hidden');
			}
			
			if( $('#TRAILER').val() != '' ) {
				$('#CLEAR_TRAILER').prop('hidden',false);
			}
			
			if( $('#CARRIER').val() != '' ) {
				$('#CLEAR_CARRIER').prop('hidden',false);
				$('#DRIVER_WELL').prop('hidden', 'hidden');
				$('#TRACTOR_WELL').prop('hidden', 'hidden');
			}
			
			function pick_carrier() {
				shipments = <?php echo $num_shipments ?>;
				$('#carrier_modal').modal({
					container: 'body'
				});
				$('#carrier_modal .modal-body').html('<p class="text-center"><img src="images/loading.gif" alt="loading" width="125" height="125" /></p>');
				var url = 'exp_pick_asset.php?code=Comfort&carrier='+load;
				
				$.get( url, function( data ) {
					$('#carrier_modal .modal-body').html( data );
					$('#EXP_CARRIER').dataTable({
				        //"bLengthChange": false,
				        "bFilter": true,
				        stateSave: true,
				        "bSort": true,
				        "bInfo": true,
						"bAutoWidth": false,
						//"bProcessing": true, 
						"sScrollX": "100%",
						"sScrollY": ($(window).height() - 275) + "px",
						//"sScrollXInner": "120%",
				        "lengthMenu": [<?php echo isset($sts_length_menu) ? $sts_length_menu : '25, 50, 100, 250'; ?>],
						"bPaginate": true,
						"bScrollCollapse": false,
						"bSortClasses": false,
						"order": [[ 1, "asc" ]],
						"processing": true,
						"serverSide": true,
						//"dom": "frtiS",
						"deferRender": true,
						"ajax": {
							"url": "exp_listcarrierajax.php",
							"data": function( d ) {
								if( shipments > 0 )
									d.match = encodeURIComponent('CHECK_CARRIER_INS3(CARRIER_CODE, <?php echo addslashes($ins_req); ?>) AND CHECK_CTPAT('+load+', CARRIER_CODE)');
								d.rtype = 'pick';
							}
		
						},
						"columns": [
							<?php
								foreach( $sts_result_carriers_pick_layout as $key => $row ) {
									if( $row["format"] <> 'hidden')
										echo '{ "data": "'.$key.'", "searchable": '.
										(isset($row["searchable"]) && $row["searchable"] ? 'true' : 'false' ).
										(isset($row["align"]) ? ', "className": "text-'.$row["align"].'"' : '').
											(isset($row["length"]) ? ', "width": "'.$row["length"].'px"' : '').
											(isset($row["format"]) && $row["format"] == 'hidden' ? ', "visible": false' : '').' },
								';
								}
							?>
						],
						"rowCallback": function( row, data ) {
							var expired = $(row).attr('expired');
							switch(expired) {
								case 'red':
									$(row).addClass("danger");
									break;
								
								case 'orange':
									$(row).addClass("inprogress2");
									break;
								
								case 'yellow':
									$(row).addClass("warning");
									break;
								
								case 'green':
								default:
									break;
							}
						}
					});

					$('#EXP_CARRIER').on( 'draw.dt', function () {
						$('.carrier_pick').click(function( data ) {
							//console.log($(this).data().resource);
	
							$('input#CARRIER').val($(this).data().resource)
							$('input#CARRIER_CODE').val($(this).data().resource_code);
							$('#CLEAR_CARRIER').prop('hidden',false);
							$('#DRIVER_WELL').prop('hidden', 'hidden');
							$('#DRIVER2_WELL').prop('hidden', 'hidden');
							$('#TRACTOR_WELL').prop('hidden', 'hidden');
							check_carrier();
						    $('#carrier_modal').modal('hide');
						});
					});
				
				});

			};

			$('#PICK_CARRIER').click(function(event) {
				event.preventDefault();
				pick_carrier();
			});

			function pick_driver() {
				$('#driver_modal').modal({
					container: 'body'
				});
				$('#driver_modal .modal-body').html('<p class="text-center"><img src="images/loading.gif" alt="loading" width="125" height="125" /></p>');
				var url = 'exp_pick_asset.php?code=Comfort&driver='+load;
				
				$.get( url, function( data ) {
					$('#driver_modal .modal-body').html( data );

					$('#DRIVERS').dataTable({
				        "bLengthChange": false,
				        "bFilter": true,
				        "bSort": true,
				        "bInfo": false,
						"bAutoWidth": false,
						//"bProcessing": true, 
						"sScrollX": "100%",
						"sScrollY": ($(window).height()*.65)+"px",
						//"sScrollXInner": "120%",
				        //"lengthMenu": [<?php echo isset($sts_length_menu) ? $sts_length_menu : '25, 50, 100, 250'; ?>],
				        //"order": [[ 0, "desc" ]],
						"bPaginate": false,
						"bScrollCollapse": false,
						"bSortClasses": false		
					});

					$('.driver_pick').click(function( data ) {
						//console.log($(this).data().resource);

						$('input#DRIVER').val($(this).data().resource).change();
						$('input#DRIVER_CODE').val($(this).data().resource_code).change();

						if($(this).data().default_tractor != '') {
							$('input#TRACTOR').val($(this).data().tractor_number).change();
							$('input#TRACTOR_CODE').val($(this).data().default_tractor).change();
							$('#CLEAR_TRACTOR').prop('hidden',false);
						    check_tractor();
						} else {
							$('#TRACTOR').val('').change();
							$('input#TRACTOR_CODE').val('').change();
							$('#CLEAR_TRACTOR').prop('hidden', 'hidden');
							if( $('#DRIVER').val() == '' )
								$('#CARRIER_WELL').prop('hidden',false);
						    check_tractor();
						}
						
						check_driver();

						// May need to remove this.
						if($(this).data().default_trailer && $(this).data().default_trailer != '' && $('input#TRAILER').val() == '') {
							$('input#TRAILER').val($(this).data().trailer_number);
							$('input#TRAILER_CODE').val($(this).data().default_trailer);
							$('#CLEAR_TRAILER').prop('hidden',false);
						}
						$('#CLEAR_DRIVER').prop('hidden',false);
						$('#CARRIER_WELL').prop('hidden', 'hidden');

					    $('#driver_modal').modal('hide');
					});
				
				});

			};

			$('#PICK_DRIVER').click(function(event) {
				event.preventDefault();
				pick_driver();
			});

	<?php if($sts_team_driver) { ?>
	//! SCR# 849 - Team Driver Feature

			function pick_driver2() {
				$('#driver_modal').modal({
					container: 'body'
				});
				$('#driver_modal .modal-body').html('<p class="text-center"><img src="images/loading.gif" alt="loading" width="125" height="125" /></p>');
				var url = 'exp_pick_asset.php?code=Comfort&driver='+load;
				
				$.get( url, function( data ) {
					$('#driver_modal .modal-body').html( data );

					$('#DRIVERS').dataTable({
				        "bLengthChange": false,
				        "bFilter": true,
				        "bSort": true,
				        "bInfo": false,
						"bAutoWidth": false,
						//"bProcessing": true, 
						"sScrollX": "100%",
						"sScrollY": ($(window).height()*.65)+"px",
						//"sScrollXInner": "120%",
				        //"lengthMenu": [<?php echo isset($sts_length_menu) ? $sts_length_menu : '25, 50, 100, 250'; ?>],
				        //"order": [[ 0, "desc" ]],
						"bPaginate": false,
						"bScrollCollapse": false,
						"bSortClasses": false		
					});

					$('.driver_pick').click(function( data ) {
						//console.log($(this).data().resource);

						$('input#DRIVER2').val($(this).data().resource).change();
						$('input#DRIVER2_CODE').val($(this).data().resource_code).change();

						check_driver2();

						// May need to remove this.
						if($(this).data().default_trailer && $(this).data().default_trailer != '' && $('input#TRAILER').val() == '') {
							$('input#TRAILER').val($(this).data().trailer_number);
							$('input#TRAILER_CODE').val($(this).data().default_trailer);
							$('#CLEAR_TRAILER').prop('hidden',false);
						}
						$('#CLEAR_DRIVER2').prop('hidden',false);
						$('#CARRIER_WELL').prop('hidden', 'hidden');

					    $('#driver_modal').modal('hide');
					});
				
				});

			};

			$('#PICK_DRIVER2').click(function(event) {
				event.preventDefault();
				pick_driver2();
			});

	<?php } ?>

			function pick_tractor() {
				$('#tractor_modal').modal({
					container: 'body'
				});
				$('#tractor_modal .modal-body').html('<p class="text-center"><img src="images/loading.gif" alt="loading" width="125" height="125" /></p>');
				var url = 'exp_pick_asset.php?code=Comfort&tractor='+load;
				
				$.get( url, function( data ) {
					$('#tractor_modal .modal-body').html( data );

					$('#TRACTORS').dataTable({
				        "bLengthChange": false,
				        "bFilter": true,
				        "bSort": true,
				        "bInfo": false,
						"bAutoWidth": false,
						//"bProcessing": true, 
						"sScrollX": "100%",
						"sScrollY": ($(window).height()*.65)+"px",
						//"sScrollXInner": "120%",
				        //"lengthMenu": [<?php echo isset($sts_length_menu) ? $sts_length_menu : '25, 50, 100, 250'; ?>],
				        //"order": [[ 0, "desc" ]],
						"bPaginate": false,
						"bScrollCollapse": false,
						"bSortClasses": false		
					});

					$('.tractor_pick').click(function( data ) {
						//console.log($(this).data().resource);

						$('input#TRACTOR').val($(this).data().resource).change();
						$('input#TRACTOR_CODE').val($(this).data().resource_code).change();
						$('#CLEAR_TRACTOR').prop('hidden',false);
						$('#CARRIER_WELL').prop('hidden', 'hidden');
					    $('#tractor_modal').modal('hide');
					    
					    check_tractor();
					});
				
				});

			};

			$('#PICK_TRACTOR').click(function(event) {
				event.preventDefault();
				pick_tractor();
			});

			function pick_trailer() {
				$('#trailer_modal').modal({
					container: 'body'
				});
				$('#trailer_modal .modal-body').html('<p class="text-center"><img src="images/loading.gif" alt="loading" width="125" height="125" /></p>');
				var url = 'exp_pick_asset.php?code=Comfort&trailer='+load;
				
				$.get( url, function( data ) {
					$('#trailer_modal .modal-body').html( data );
					$('#TRAILERS').dataTable({
				        "bLengthChange": false,
				        "bFilter": true,
				        "bSort": true,
				        "bInfo": false,
						"bAutoWidth": false,
						//"bProcessing": true, 
						"sScrollX": "100%",
						"sScrollY": ($(window).height()*.65)+"px",
						//"sScrollXInner": "120%",
				        //"lengthMenu": [<?php echo isset($sts_length_menu) ? $sts_length_menu : '25, 50, 100, 250'; ?>],
				        //"order": [[ 0, "desc" ]],
						"bPaginate": false,
						"bScrollCollapse": false,
						"bSortClasses": false		
					});

					$('.trailer_pick').click(function( data ) {
						//console.log($(this).data().resource);

						$('input#TRAILER').val($(this).data().resource).change();
						$('input#TRAILER_CODE').val($(this).data().resource_code).change();
						$('#CLEAR_TRAILER').prop('hidden',false);
					    $('#trailer_modal').modal('hide');
					    check_trailer();
					});
				
				});

			};

			$('#PICK_TRAILER').click(function(event) {
				event.preventDefault();
				pick_trailer();
			});

			check_carrier();
			check_driver();
	<?php if($sts_team_driver) { ?>
	//! SCR# 849 - Team Driver Feature
			check_driver2();
	<?php } ?>
			check_tractor();
			check_trailer();
		});
	//--></script>

<?php

require_once( "include/footer_inc.php" );
?>
