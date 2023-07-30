<?php 

// $Id: exp_load_state.php 4697 2022-03-09 23:02:23Z duncan $
// Change the state of a load

// Set flag that this is a parent file
define( '_STS_INCLUDE', 1 );

// Setup Session
require_once( "include/sts_session_setup.php" );

require_once( "include/sts_config.php" );
$sts_debug = (isset($_GET['debug'])  || isset($_POST['debug']))  && in_group( EXT_GROUP_DEBUG );
//$sts_debug = true;
require_once( "include/sts_session_class.php" );
require_once( "include/sts_stop_class.php" );

$my_session = sts_session::getInstance( $exspeedite_db, $sts_debug );
$my_session->access_check( $sts_table_access[LOAD_TABLE], EXT_GROUP_DRIVER );	// Make sure we should be here

require_once( "include/sts_load_class.php" );
require_once( "include/sts_shipment_class.php" );
require_once( "include/sts_edi_trans_class.php" );
require_once( "include/sts_email_class.php" );
require_once( "include/sts_setting_class.php" );
require_once( "include/sts_item_list_class.php" );

$item_list_table = sts_item_list::getInstance($exspeedite_db, $sts_debug);

$setting_table = sts_setting::getInstance($exspeedite_db, $sts_debug);
$sts_require_late_reason = $setting_table->get( 'option', 'REQUIRE_LATE_REASON' ) == 'true';
$sts_multi_currency = $setting_table->get( 'option', 'MULTI_CURRENCY' ) == 'true';
//! SCR# 198 - Hide charges info
$sts_hide_charges = $setting_table->get( 'option', 'HIDE_CHARGES_MANIFEST' ) == 'true';
$sts_print_manifest = $setting_table->get( 'option', 'PRINT_MANIFEST_AFTER_SEND' ) == 'true';

if( $sts_debug ) {
	echo "<pre>GET, POST\n";
	var_dump($_GET);
	var_dump($_POST);
	echo "</pre>";
}

$referer = 'unknown';
if( isset($_POST['REFERER']) )
	$referer = $_POST['REFERER'];
else if( isset($_SERVER["HTTP_REFERER"]) ) {
	$path = explode('/', $_SERVER["HTTP_REFERER"]);
	//! Make sure we don't come around twice?
	$check = explode('?', end($path) );
	if( $check[0] <> 'exp_load_state.php' )
		$referer = end($path);
}
	

if( isset($_GET['CODE']) && isset($_GET['STATE']) ) {
	if( $sts_debug ) echo "<p>exp_load_state: load = ".$_GET['CODE']." state = ".$_GET['STATE']."</p>";
	$load_table = sts_load::getInstance($exspeedite_db, $sts_debug);
	$result = $load_table->change_state( $_GET['CODE'], $_GET['STATE'],
		isset($_GET['CSTOP']) ? $_GET['CSTOP'] : -1, 
		isset($_GET['CSTATE']) ? $_GET['CSTATE'] : -1 );
	if( $sts_debug ) echo "<p>result = ".($result ? 'true' : 'false '.$load_table->error())."</p>";

	if( ! $result ) {
		$sts_subtitle = "Change Load State";
		require_once( "include/header_inc.php" );
		require_once( "include/navbar_inc.php" );

		echo '<div class="container theme-showcase" role="main">

		<div class="well  well-md">
		<h3 class="text-danger"><span class="glyphicon glyphicon-warning-sign"></span> Unable to change Load #'.$_GET['CODE'].' to '.$load_table->state_name[$_GET['STATE']].' state</h3>
		
		<p>'.$load_table->state_change_error.'</p>
		
		<p><a class="btn btn-md btn-default" href="exp_viewload.php?CODE='.$_GET['CODE'].'"><span class="glyphicon glyphicon-edit"></span> View Load #'.$_GET['CODE'].'</a>
		<a class="btn btn-md btn-default" href="exp_listload.php"><span class="glyphicon glyphicon-arrow-up"></span> List Loads</a>
		</p>
		
		</div>
		
		</div>' ;
		$save_error = $load_table->error();
		$email = sts_email::getInstance($exspeedite_db, $sts_debug);
		//! SCR# 362 - don't send email unless needed
		if( $sts_debug ) echo '<p>exp_load_state: Error diag_level = '.$email->diag_level().' level = '.$load_table->state_change_level.'</p>';
		if( $email->diag_level() >= $load_table->state_change_level ) {
			$email->send_alert('exp_load_state: Unable to change Load #'.$_GET['CODE'].' to '.$load_table->state_name[$_GET['STATE']].' state<br>'.$load_table->state_change_error.
			'<br>'.$save_error.
			'<br>'.$email->load_stops($_GET['CODE']).
			'<br>'.$email->load_history($_GET['CODE']).
			'<br>'.$email->shipment_histories( $_GET['CODE'] ), $load_table->state_change_level );
		}

			
	} else { //! Post change interaction
	
		if( $load_table->state_change_post <> '' ) {	//! Need to prompt user
			list($state,$stop) = explode(',', $load_table->state_change_post);
			if( $sts_debug ) echo "<p>exp_load_state: Post change, state = $state, stop = $stop</p>";
			
			$result = $load_table->fetch_rows( $load_table->primary_key." = ".$_GET['CODE'],
				"(SELECT CARRIER_NAME FROM EXP_CARRIER WHERE EXP_CARRIER.CARRIER_CODE = EXP_LOAD.CARRIER ) AS CARRIER_NAME,
				(SELECT EMAIL_NOTIFY FROM EXP_CARRIER WHERE EXP_CARRIER.CARRIER_CODE = EXP_LOAD.CARRIER ) AS CR_EMAIL_NOTIFY,
				(SELECT EMAIL FROM EXP_CONTACT_INFO
			WHERE CONTACT_SOURCE = 'carrier'
			AND CONTACT_TYPE = 'company'
			AND CONTACT_CODE = EXP_LOAD.CARRIER
			LIMIT 1) AS CR_EMAIL_CONTACT_INFO,

		(SELECT CONCAT_WS(' ',FIRST_NAME,LAST_NAME) FROM EXP_DRIVER WHERE EXP_DRIVER.DRIVER_CODE = EXP_LOAD.DRIVER LIMIT 1) AS DRIVER_NAME,
		(SELECT EMAIL_NOTIFY FROM EXP_DRIVER WHERE EXP_DRIVER.DRIVER_CODE = EXP_LOAD.DRIVER LIMIT 1) AS DR_EMAIL_NOTIFY,
		(SELECT EMAIL FROM EXP_CONTACT_INFO
			WHERE CONTACT_SOURCE = 'driver'
			AND CONTACT_CODE = EXP_LOAD.DRIVER
			LIMIT 1) AS DR_EMAIL_CONTACT_INFO,
			
	(SELECT COUNT(*) FROM EXP_SHIPMENT WHERE EXP_SHIPMENT.LOAD_CODE = EXP_LOAD.LOAD_CODE) SHIPMENTS,
	(SELECT SUM(PALLETS) FROM EXP_SHIPMENT WHERE EXP_SHIPMENT.LOAD_CODE = EXP_LOAD.LOAD_CODE) PALLETS,
	(SELECT SUM(WEIGHT) FROM EXP_SHIPMENT WHERE EXP_SHIPMENT.LOAD_CODE = EXP_LOAD.LOAD_CODE) WEIGHT, CURRENCY,
	(SELECT CURRENCY_CODE FROM EXP_CARRIER WHERE EXP_CARRIER.CARRIER_CODE = EXP_LOAD.CARRIER ) AS CURRENCY_CODE,
	(SELECT COALESCE(DEFAULT_PERCENT,0) FROM EXP_CARRIER WHERE EXP_CARRIER.CARRIER_CODE = EXP_LOAD.CARRIER ) AS DEFAULT_PERCENT,

	(SELECT SUM(B.TOTAL) AS TOTAL
		FROM EXP_CLIENT_BILLING B, EXP_SHIPMENT S
		WHERE B.SHIPMENT_ID = S.SHIPMENT_CODE
		AND S.LOAD_CODE = EXP_LOAD.LOAD_CODE) AS TOTAL,

	(SELECT SUM(B.HAND_CHARGES) AS HAND_CHARGES
		FROM EXP_CLIENT_BILLING B, EXP_SHIPMENT S
		WHERE B.SHIPMENT_ID = S.SHIPMENT_CODE
		AND S.LOAD_CODE = EXP_LOAD.LOAD_CODE) AS HAND_CHARGES,

	(SELECT SUM(B.TOTAL - B.HAND_CHARGES) AS GROSS
		FROM EXP_CLIENT_BILLING B, EXP_SHIPMENT S
		WHERE B.SHIPMENT_ID = S.SHIPMENT_CODE
		AND S.LOAD_CODE = EXP_LOAD.LOAD_CODE) AS GROSS,

	TERMS, TOTAL_DISTANCE, CARRIER_BASE, CARRIER_NOTE, EDI_204_PRIMARY" );

			$shipments = $result[0]['SHIPMENTS'];
			$pallets = $result[0]['PALLETS'];
			$weight = $result[0]['WEIGHT'];
			$distance = $result[0]['TOTAL_DISTANCE'];
			$currency = $result[0]['CURRENCY_CODE'];
			$base = $result[0]['CARRIER_BASE'];
			$total = empty($result[0]['TOTAL']) ? 0 : $result[0]['TOTAL'];
			$hand = empty($result[0]['HAND_CHARGES']) ? 0 : $result[0]['HAND_CHARGES'];
			$gross = empty($result[0]['GROSS']) ? 0 : $result[0]['GROSS'];
			$note = trim($result[0]['CARRIER_NOTE']);
			$primary = ! empty($result[0]['EDI_204_PRIMARY']) ? $result[0]['EDI_204_PRIMARY'] : false;
			$def_pct = $gross90 = 0;
			$terms = empty($result[0]['TERMS']) ? 0 : $result[0]['TERMS'];

			if( $state == 'dispatch') {
				$manifest = 'Driver Manifest';
				$manifest2 = 'driver manifest';
				$name = $result[0]['DRIVER_NAME'];
				$email = isset($result[0]['DR_EMAIL_NOTIFY']) ?
					$result[0]['DR_EMAIL_NOTIFY'] : (
					isset($result[0]['DR_EMAIL_CONTACT_INFO']) ?
						$result[0]['DR_EMAIL_CONTACT_INFO'] : '' );
			} else {
				$manifest = 'Freight Agreement';
				$manifest2 = 'carrier manifest';
				$name = $result[0]['CARRIER_NAME'];
				$email = isset($result[0]['CR_EMAIL_NOTIFY']) ?
					$result[0]['CR_EMAIL_NOTIFY'] : (
					isset($result[0]['CR_EMAIL_CONTACT_INFO']) ?
						$result[0]['CR_EMAIL_CONTACT_INFO'] : '' );
				$def_pct = empty($result[0]['DEFAULT_PERCENT']) ? 0 : $result[0]['DEFAULT_PERCENT'];
				if( $gross > 0 && $def_pct > 0 )
					$gross90 = $gross * $def_pct / 100.0; //! WIP
			}
			
			
			//! Driver/carrier manifest form
			if( in_array($state, array('manifest','dispatch') ) ) {
				echo '<div class="container-full" id="load_state">
				<form role="form" class="form-horizontal" action="exp_load_state.php" 
						method="post" enctype="multipart/form-data" 
						name="manifest" id="manifest">
				'.(isset($_GET['debug']) ? '<input name="debug" id="debug" type="hidden" value="true">
' : '').'
				<input name="CODE" id="CODE" type="hidden" value="'.$_GET['CODE'].'">
				<input name="STATE" id="STATE" type="hidden" value="'.$state.'">
				<input name="REFERER" id="REFERER" type="hidden" value="'.$referer.'">
				<h2><img src="images/load_icon.png" alt="load_icon" height="24"> Load '.$_GET['CODE'].' Send '.$manifest.'</h2>
				<p>Please enter amounts for '.$manifest2.' to send to <strong>'.$name.'</strong>.<br>
				Includes '.plural($shipments,'shipment').', '.plural($pallets,'pallet').', weighing '.
				plural($weight,'pound').', '.plural($distance,'mile').
				($my_session->in_group(EXT_GROUP_DRIVER) || $sts_hide_charges ?
					'' : '<br>Total: '.number_format($total,2).
					' - Handling: '.number_format($hand,2).
					' = Gross: '.number_format($gross,2)).'</p>
				';
			if( $state == 'manifest')
				echo ($sts_multi_currency ? '
				<div class="form-group">
					<label for="CURRENCY" class="col-sm-3 control-label">Currency</label>
					<div class="col-sm-4">
						<select class="form-control" name="CURRENCY" id="CURRENCY" >
							<option value="USD" '.($currency == 'USD' ? 'selected' : '').'>USD</option>
							<option value="CAD" '.($currency == 'CAD' ? 'selected' : '').'>CAD</option>
						</select>
					</div>
				</div>
				' : '').'
				<div class="form-group">
					<label for="TERMS" class="col-sm-3 control-label">Terms</label>
					<div class="col-sm-4">
						'.$item_list_table->render_terms_menu( $terms, $type = 'Vendor Terms' ).'
					</div>
				</div>

				<div class="form-group">
					<label for="CARRIER_BASE" class="col-sm-3 control-label">Base Amount</label>
					<div class="col-sm-4">
						<div class="input-group">
							<span class="input-group-addon">$</span>
							<input class="form-control text-right" name="CARRIER_BASE" 
								id="CARRIER_BASE" type="number" step="0.01" align="right" 
								value="'.($base <> '' ? $base : '0').'"
								autofocus required>
						</div>
					</div>
					<div class="col-sm-4">
						'.($gross > 0 && $def_pct > 0 && ! $sts_hide_charges ? '<a class="btn btn-md btn-success" onclick="$(\'input#CARRIER_BASE\').val(\''.number_format($gross90,2,'.','').'\')"><span class="glyphicon glyphicon-arrow-left"></span> '.number_format($def_pct,1,'.','').'% Gross ('.number_format($gross90,2,'.','').')</a>' : '').'
					</div>
				</div>
				';
				
				echo '<div class="form-group">
					<label for="TO_EMAIL" class="col-sm-3 control-label"><span class="glyphicon glyphicon-envelope"></span> Email To:</label>
					<div class="col-sm-8">
						<input class="form-control" name="TO_EMAIL" 
							id="TO_EMAIL" type="email" value="'.$email.'" multiple required>
					</div>
				</div>
				<div class="form-group">
					<label for="CC_YOU" class="text-left col-sm-3 control-label"><span class="glyphicon glyphicon-envelope"></span> Copy To:</label>
					<div class="col-sm-8">
						<input class="control-label" name="CC_YOU" 
								id="CC_YOU" type="checkbox" checked> to '.htmlspecialchars($_SESSION['EXT_FULLNAME'].' <'.$_SESSION['EXT_EMAIL'].'>').'					
					</div>
				</div>
				<div class="form-group">
					<label for="CARRIER_NOTE" class="col-sm-3 control-label"><span class="glyphicon glyphicon-pencil"></span> Note</label>
					<div class="col-sm-8">
						<textarea class="form-control" name="CARRIER_NOTE" 
							id="CARRIER_NOTE" rows="6" placeholder="Note to carrier.">'.($note <> '' ? $note : '').' </textarea>
					</div>
				</div>
				<div class="form-group">
					<div class="btn-group">
						<button class="btn btn-md btn-success" name="saveemail" id="saveemail" type="submit" ><span class="glyphicon glyphicon-envelope"></span> Save & Email</button>
						<button class="btn btn-md btn-success" name="save" id="save" type="submit" ><span class="glyphicon glyphicon-ok"></span> Save</button>
						<button class="btn btn-md btn-default" name="cancel" id="cancel" type="submit" formnovalidate><span class="glyphicon glyphicon-remove"></span> Cancel</button>
					</div>
				</div>
				</form>
				</div>
			
					';
		if( $sts_print_manifest )			
			echo "
	<script language=\"JavaScript\" type=\"text/javascript\"><!--
		
		//! Deal with Driver/carrier manifest form / jquery
		$(document).ready( function () {
			$('#saveemail,#save,#cancel').on('click', function(event) {
				event.preventDefault();
				$('#listload_modal').modal({
					container: 'body',
				});
				
				// Combine inputs + pressed button
				var button = $(event.target);                 
			    var data = button.parents('form').serialize() 
			        + '&' 
			        + encodeURI(button.attr('name'))
			        + '='
			        + encodeURI(button.attr('value'));
			
				//console.log('data ', data);
			
				//console.log(data);
				$.post('exp_load_state.php', data, function( response, status ) {
					//console.log('response ', response);
					$('#load_state').html(response);
				});
			});


		});
	//--></script>					


";
/* not working, try again later					
					';
*/
			} else {	//! Deal with actual arrive/depart date
				$stop_table = sts_stop::getInstance($exspeedite_db, $sts_debug);
				$shipment_table = sts_shipment::getInstance($exspeedite_db, $sts_debug);
				$edi = sts_edi_trans::getInstance($exspeedite_db, $sts_debug);
			
				$arrival = strncmp($state, 'arr', 3) === 0 ? true : false;

				// Due timestamp for stop
				$due = $stop_table->get_due($stop);

				// Due timestamp for stop
				$check = $stop_table->database->get_one_row("
					select STOP_TYPE, GET_TIMEZONE( CASE STOP_TYPE
							WHEN 'pick' THEN SHIPPER_ZIP
							WHEN 'drop' THEN CONS_ZIP
							ELSE STOP_ZIP END ) AS TZONE,
						NOW_TIMEZONE( CASE STOP_TYPE
							WHEN 'pick' THEN SHIPPER_ZIP
							WHEN 'drop' THEN CONS_ZIP
							ELSE STOP_ZIP END ) AS NZONE
					FROM EXP_STOP
					LEFT JOIN EXP_SHIPMENT
					ON EXP_STOP.SHIPMENT=EXP_SHIPMENT.SHIPMENT_CODE
					WHERE EXP_STOP.STOP_CODE=$stop");
				
				$pickup = is_array($check) &&
					isset($check["STOP_TYPE"]) &&
					in_array($check["STOP_TYPE"], array('pick', 'pickdock'));
					
				$tzone = is_array($check) &&
					! empty($check["TZONE"]) ? $check["TZONE"] : '';
					
				$nzone = is_array($check) &&
					! empty($check["NZONE"]) ? strtotime($check["NZONE"]) : null;

				//! EDI stuff
				if( $primary ) {
					// Get the origin, to determine the mapping
					$check = $shipment_table->fetch_rows($shipment_table->primary_key.' = '.$primary,
						"EDI_204_ORIGIN");
					$origin = false;
					if( is_array($check) && count($check) == 1 &&
						! empty($check[0]["EDI_204_ORIGIN"])) {
						$origin = $check[0]["EDI_204_ORIGIN"];
						if( $sts_debug ) echo "<p>exp_load_state: EDI stuff, origin = $origin</p>";
			
						$map = $edi->select_map_class( $origin );		// Get map for origin
					}
				}
				
				// Get a select menu for late reasons
				$select = $edi->event_status_menu('EDI_'.($arrival ? 'ARRIVE' : 'DEPART').'_STATUS');

			
				if( $sts_debug ) echo "<p>exp_load_state: Before form, state = $state, arrival = ".($arrival ? "true" : "false")."</p>";
				$date_format = $_SESSION['ios'] == 'true' ? "Y-m-d\TH:i" : "m/d/Y H:i";
				
				echo '<div class="container-full">
				<form role="form" class="form-horizontal" action="exp_load_state.php" 
						method="post" enctype="multipart/form-data" 
						name="editstop" id="editstop">
				'.($sts_debug ? '<input name="debug" type="hidden" value="true">' : '').'
				<input name="STOP" type="hidden" value="'.$stop.'">
				<input name="LOAD" type="hidden" value="'.$_GET['CODE'].'">
				<input name="REFERER" id="REFERER" type="hidden" value="'.$referer.'">
				<h2><img src="images/load_icon.png" alt="load_icon" height="24"> Load '.$_GET['CODE'].'</h2>
				';

				echo '<p>Please enter actual '.($arrival ? 'arrival' : 'departure').' date & time.</p>
				'.($due ? '
				<div class="form-group">
					<label class="col-sm-2 control-label">Due</label>
					<div class="col-sm-3">
						<strong>'.date("m/d/Y H:i", strtotime($due)).'</strong>
						<div id="due" hidden>'.$due.'</div>
					</div>
					<div class="col-sm-2">
						<span class="text-primary"><span class="glyphicon glyphicon-time"></span> '.$tzone.'</span>
					</div>
				</div>
				' : '').'
				<div class="form-group">
					<label for="ACTUAL_'.($arrival ? 'ARRIVE' : 'DEPART').'" class="col-sm-2 control-label">Actual '.($arrival ? 'Arr' : 'Dep').'</label>
					<div class="col-sm-3">
						<input class="form-control'.($_SESSION['ios'] == 'true' ? '' : ' timestamp').'" name="ACTUAL_'.($arrival ? 'ARRIVE' : 'DEPART').'" 
							id="ACTUAL_'.($arrival ? 'ARRIVE' : 'DEPART').'" type="'.($_SESSION['ios'] == 'true' ? 'datetime-local' : 'text').'"  
							value="'.date($date_format, $nzone).'" >
					</div>
					<div class="col-sm-2">
						<span class="text-primary"><span class="glyphicon glyphicon-time"></span> '.$tzone.'</span>
					</div>
					<div ID="excuse" hidden>
						<label for="EDI_'.($arrival ? 'ARRIVE' : 'DEPART').'_STATUS" class="col-sm-2 control-label">Late Because</label>
						<div class="col-sm-3">
							'.$select.'
						</div>
					</div>
				</div>
				';
				
				// Departing pickup, ask for EDI_DELIVER_ETA
				if( $pickup && ! $arrival )
					echo '<div class="form-group">
					<label for="EDI_DELIVER_ETA" class="col-sm-2 control-label">Delivery ETA</label>
					<div class="col-sm-3">
						<input class="form-control'.($_SESSION['ios'] == 'true' ? '' : ' timestamp').'" name="EDI_DELIVER_ETA" 
							id="EDI_DELIVER_ETA" type="'.($_SESSION['ios'] == 'true' ? 'datetime-local' : 'text').'"  
							value="'.date($date_format, $nzone).'" >
					</div>
					<div class="col-sm-2">
						<span class="text-primary"><span class="glyphicon glyphicon-time"></span> '.$tzone.'</span>
					</div>
				</div>
				';

				echo '<div class="form-group">
					<div class="col-sm-3 col-sm-offset-2">
						<button class="btn btn-md btn-success" name="save" type="submit" id="submit"><span class="glyphicon glyphicon-ok"></span> Save Changes</button>
					</div>
				</div>
				</form>
				</div>
			
					'."
	<script language=\"JavaScript\" type=\"text/javascript\"><!--
		
		$(document).ready( function () {
			
			function update_due() {
				var due = new Date($('#due').text()).getTime();
				var actual = new Date($('#".'ACTUAL_'.($arrival ? 'ARRIVE' : 'DEPART')."').val()).getTime();
				console.log('due ',due,'actual ',actual);
				if( ".($sts_require_late_reason ? 'actual > due' : 'false').") {
					//$('#result').text('late');
					$('#excuse').prop('hidden',false);
					update_excuse();
				} else {
					//$('#result').text('On schedule');
					$('#excuse').prop('hidden', 'hidden');
					$('#STATUS').val($('#".'EDI_'.($arrival ? 'ARRIVE' : 'DEPART').'_STATUS'." option:first').val());
					$('button#submit').prop('disabled', false);
				}
			}

			function update_excuse() {
				if( $('select#".'EDI_'.($arrival ? 'ARRIVE' : 'DEPART').'_STATUS'."').val() == 'NS' ) {
					$('button#submit').prop('disabled', true);
				} else {
					$('button#submit').prop('disabled', false);
				}
			}
			
			$('input#".'ACTUAL_'.($arrival ? 'ARRIVE' : 'DEPART')."').on('change', function(event) {
				update_due();
			});

			$('input#".'ACTUAL_'.($arrival ? 'ARRIVE' : 'DEPART')."').on('dp.change', function(event) {
				update_due();
			});

			$('select#".'EDI_'.($arrival ? 'ARRIVE' : 'DEPART').'_STATUS'."').on('change', function(event) {
				update_excuse();
			});

			$('select#".'EDI_'.($arrival ? 'ARRIVE' : 'DEPART').'_STATUS'."').on('dp.change', function(event) {
				update_excuse();
			});

			update_due();			
			
		});
	//--></script>					
					";
			}
	require_once( "include/footer_inc.php" );
			
		} else {
			if( ! $sts_debug ) {
				//! Add a delay so I can see any error messages.
				if( $_SESSION['EXT_USERNAME'] == 'duncan' ) sleep(5);
				
				if( isset($referer) && $referer <> 'unknown' )
					reload_page ( $referer );	// Back to referring page
				else if( $my_session->in_group(EXT_GROUP_DRIVER) )
					reload_page ( "exp_listload_driver.php" );	// Back to driver loads page
				else
					reload_page ( "exp_listload.php" );	// Back to list loads page
			}
		}
	}
	
} else if( isset($_POST['STOP']) &&
	(isset($_POST['ACTUAL_ARRIVE']) || isset($_POST['ACTUAL_DEPART'])) ) {	//! Process form
	
	if( $sts_debug ) {
		echo "<p>_POST = </p>
		<pre>";
		var_dump($_POST);
		echo "</pre>";
	}

	$stop_table = sts_stop::getInstance($exspeedite_db, $sts_debug);
	$shipment_table = sts_shipment::getInstance($exspeedite_db, $sts_debug);
	$load_table = sts_load::getInstance($exspeedite_db, $sts_debug);

	$changes = array();
	if( $_SESSION['ios'] == 'true' )
		$validTimestamp = validateDateTime('Y-m-d\TH:i');

	if( isset($_POST['ACTUAL_ARRIVE']) && $_POST['ACTUAL_ARRIVE'] <> '' &&
		$validTimestamp($_POST['ACTUAL_ARRIVE']) &&
		date("Y", strtotime($_POST['ACTUAL_ARRIVE'])) <> "1969" ) {
		$changes['ACTUAL_ARRIVE'] = date("Y-m-d H:i:s", strtotime($_POST['ACTUAL_ARRIVE']));
	} else if( isset($_POST['ACTUAL_DEPART']) && $_POST['ACTUAL_DEPART'] <> '' &&
		$validTimestamp($_POST['ACTUAL_DEPART']) &&
		date("Y", strtotime($_POST['ACTUAL_DEPART'])) <> "1969" ) {
		$changes['ACTUAL_DEPART'] = date("Y-m-d H:i:s", strtotime($_POST['ACTUAL_DEPART']));
	}
	
	if( isset($_POST['EDI_ARRIVE_STATUS']) && $_POST['EDI_ARRIVE_STATUS'] <> '' )
		$changes['EDI_ARRIVE_STATUS'] = $_POST['EDI_ARRIVE_STATUS'];

	if( isset($_POST['EDI_DEPART_STATUS']) && $_POST['EDI_DEPART_STATUS'] <> '' )
		$changes['EDI_DEPART_STATUS'] = $_POST['EDI_DEPART_STATUS'];

	if( isset($_POST['EDI_DELIVER_ETA']) && $_POST['EDI_DELIVER_ETA'] <> '' &&
		$validTimestamp($_POST['EDI_DELIVER_ETA']) &&
		date("Y", strtotime($_POST['EDI_DELIVER_ETA'])) <> "1969" )
		$changes['EDI_DELIVER_ETA'] = date("Y-m-d H:i:s", strtotime($_POST['EDI_DELIVER_ETA']));
	
	if( count($changes) > 0 )
		$result = $stop_table->update( $_POST['STOP'], $changes );
	
	if( isset($_POST['ACTUAL_DEPART']) )
		$dummy = $stop_table->multi_stop_update_actuals( $_POST['LOAD'] );
		

	//! EDI - send 214
	$result = $load_table->fetch_rows( $load_table->primary_key." = ".$_POST['LOAD'],
		"CURRENT_STATUS, EDI_204_PRIMARY");
	$result2 = $stop_table->fetch_rows( $stop_table->primary_key." = ".$_POST['STOP'],
		"SEQUENCE_NO, SHIPMENT,
		(SELECT EDI_204_PRIMARY FROM EXP_SHIPMENT
		WHERE SHIPMENT_CODE = SHIPMENT) AS EDI_204_PRIMARY");
	if( is_array($result) && count($result) == 1 &&
		is_array($result2) && count($result2) == 1 ) {
		$state = $load_table->state_behavior[$result[0]["CURRENT_STATUS"]];
		$stop = $result2[0]["SEQUENCE_NO"];
		$primary = (!empty($result[0]["EDI_204_PRIMARY"]) ? $result[0]["EDI_204_PRIMARY"] :
			(!empty($result2[0]["EDI_204_PRIMARY"]) ? $result2[0]["EDI_204_PRIMARY"] : false));

		if( $sts_debug ) {
			echo "<p>EDI Check</p><pre>";
			var_dump($primary, $_POST['LOAD'], $_POST['STOP'], $stop, $state );
			echo "</pre>";
		}
		if( isset($primary) && intval($primary) > 0 ) {
			$edi = sts_edi_trans::getInstance($exspeedite_db, $sts_debug);
			$event = $edi->load_state_event( $state );
			$dummy = $edi->send_214( $_POST['LOAD'], $stop, $event ); 
		}
	}
	
	if( ! $sts_debug ) {
		//! Add a delay so I can see any error messages.
		if( $_SESSION['EXT_USERNAME'] == 'duncan' ) sleep(5);
		
		if( isset($referer) && $referer <> 'exp_load_state.php' )
			reload_page ( $referer );	// Back to referring page
		else if( $my_session->in_group(EXT_GROUP_DRIVER) )
			reload_page ( "exp_listload_driver.php" );	// Back to driver loads page
		else
			reload_page ( "exp_listload.php" );	// Back to list loads page
	}
} else if( isset($_POST['CODE'])  ) {	//! Process driver/carrier form

	$state = isset($_POST['STATE']) ? $_POST['STATE'] : 'manifest';
		
	if( isset($_POST['cancel']) ) {	// Go back to Entered state
		$load_table = sts_load::getInstance($exspeedite_db, $sts_debug);
		$result = $load_table->update( $_POST['CODE'], array( 'CURRENT_STATUS' => $load_table->behavior_state['entry'] ) );	// Go back for now.
		if( $state == 'dispatch') {
			$load_table->database->get_one_row("
				UPDATE EXP_DRIVER
				SET CURRENT_LOAD = COALESCE(LAST_LOAD,0), LAST_LOAD = 0
				WHERE DRIVER_CODE = (SELECT DRIVER FROM EXP_LOAD
					WHERE LOAD_CODE = ".$_POST['CODE'].")");
		}
		
	} else {	// Send email to driver/carrier		
		$load_table = sts_load::getInstance($exspeedite_db, $sts_debug);
		
		if( $state == 'manifest') { // Carrier
			
			$changes = array( 'CARRIER_BASE' => str_replace( ',', '', $_POST['CARRIER_BASE']),
				'CARRIER_EMAIL' => $_POST['TO_EMAIL'],
				'CARRIER_MANIFEST_SENT' => date("Y-m-d H:i:s") );
				
			if( isset($_POST['CURRENCY']) && $_POST['CURRENCY'] <> '' )
				$changes['CURRENCY'] = $_POST['CURRENCY'];
				
			if( isset($_POST['TERMS']) && $_POST['TERMS'] <> '' )
				$changes['TERMS'] = $_POST['TERMS'];
				
			if( isset($_POST['CARRIER_NOTE']) && $_POST['CARRIER_NOTE'] <> '' )
				$changes['CARRIER_NOTE'] = $_POST['CARRIER_NOTE'];
				
			$result = $load_table->update( $_POST['CODE'], $changes );
		} else if( ! empty($_POST['CARRIER_NOTE']) ) { // Driver
			$changes = array( 'CARRIER_NOTE' => $_POST['CARRIER_NOTE'] );
			$result = $load_table->update( $_POST['CODE'], $changes );
		}

		$email = sts_email::getInstance($exspeedite_db, $sts_debug);
		
		//! SCR# 198 - only send email if they pressed the save & email
		if( isset($_POST['saveemail']) && $email->enabled() ) {
			if( isset($_POST['CC_YOU']) && $_POST['CC_YOU'] == 'on' )
				$changes = array( 'EMAIL_CC_USER' => $_SESSION['EXT_USER_CODE'] );
			else
				$changes = array( 'EMAIL_CC_USER' => 0 );
			$result = $load_table->update( $_POST['CODE'], $changes );
		
			$email_type = 'manifest';
			$email_code = $_POST['CODE'];
			require_once( "exp_spawn_send_email.php" );		// Background send
		}
		
		//! SCR# 281 - print manifest
		if( $sts_print_manifest ) {
			if( isset($referer) && $referer <> 'unknown' )
				$continue = $referer;	// Back to referring page
			else if( $my_session->in_group(EXT_GROUP_DRIVER) )
				$continue = "exp_listload_driver.php";	// Back to driver loads page
			else
				$continue = "exp_listload.php";	// Back to list loads page

			echo '<div class="container-full">
			<h2><img src="images/load_icon.png" alt="load_icon" height="24"> Load '.$_POST['CODE'].' Sent '.$_POST['STATE'].'</h2>
				<p>The '.$_POST['STATE'].' has been '.(isset($_POST['save']) ? 'saved.' : 'saved & e-mailed.').' Would you like to print a copy?</p>
				<br>
				<p><a class="btn btn-md btn-success" name="print" id="print" onclick="window.open(\'exp_viewmanifest.php?CODE='.$_POST['CODE'].'&PRINT\', \'newwindow\', \'width=\'+ ($(window).width()*2/3) + \',height=\' + ($(window).height()*2/3)); return false;"><span class="glyphicon glyphicon-print"></span> Print</a>
				<a class="btn btn-md btn-default" name="continue" id="continue" href="'.$continue.'"><span class="glyphicon glyphicon-arrow-right"></span> Continue</a></p>
				<br>
				<br>
			
			</div>
			';
			die;
		}
	}
	

	if( ! $sts_debug ) {
		//! Add a delay so I can see any error messages.
		if( $_SESSION['EXT_USERNAME'] == 'duncan' ) sleep(5);
		
		if( isset($referer) && $referer <> 'unknown' )
			reload_page ( $referer );	// Back to referring page
		else if( $my_session->in_group(EXT_GROUP_DRIVER) )
			reload_page ( "exp_listload_driver.php" );	// Back to driver loads page
		else
			reload_page ( "exp_listload.php" );	// Back to list loads page
	}
}

?>