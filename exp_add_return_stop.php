<?php 

// $Id: exp_add_return_stop.php 5030 2023-04-12 20:31:34Z duncan $
// Insert a stop into a load

// Set flag that this is a parent file
define( '_STS_INCLUDE', 1 );

// Set flag that this is session readonly
define( '_STS_SESSION_READONLY', 1 );

// Setup Session
require_once( "include/sts_session_setup.php" );

require_once( "include/sts_config.php" );
$sts_debug = (isset($_GET['debug'])  || isset($_POST['debug'])) && in_group( EXT_GROUP_DEBUG );
require_once( "include/sts_session_class.php" );

$my_session = sts_session::getInstance( $exspeedite_db, $sts_debug );
$my_session->access_check( EXT_GROUP_DISPATCH );	// Make sure we should be here

$sts_subtitle = "Add Return Stop";
require_once( "include/header_inc.php" );
require_once( "include/navbar_inc.php" );

require_once( "include/sts_stop_class.php" );
require_once( "include/sts_load_class.php" );
require_once( "include/sts_shipment_class.php" );
require_once( "include/sts_client_class.php" );
require_once( "include/sts_setting_class.php" );
require_once( "include/sts_yard_container_class.php" );

if( $sts_debug ) echo "<p>add_return_stop</p>";

$stop_table = sts_stop::getInstance($exspeedite_db, $sts_debug);
$shipment_table = sts_shipment::getInstance($exspeedite_db, $sts_debug);
$load_table = sts_load::getInstance($exspeedite_db, $sts_debug);
$client_table = sts_client::getInstance($exspeedite_db, $sts_debug);
$setting_table = sts_setting::getInstance( $exspeedite_db, $sts_debug );
$yc_table = sts_yard_container::getInstance( $exspeedite_db, $sts_debug );

$sts_default_return_stop = $setting_table->get( 'option', 'DEFAULT_RETURN_STOP' );
$sts_canada_postcodes = $setting_table->get( 'option', 'CANADA_POSTCODES' ) == 'true';

//! SCR# 852 - Containers Feature
$sts_containers = $setting_table->get( 'option', 'CONTAINERS' ) == 'true';

$client_matches = (int) $setting_table->get( 'option', 'CLIENT_MATCHES' );
if( $client_matches <= 0 ) $client_matches = 20;

$postcode = $sts_canada_postcodes ? 'Zip / Postal Code' : 'Zip Code';

function show_stop( $stop ) {
	return $stop['STOP_NAME'].'<br>
				'.$stop['STOP_ADDR1'].'<br>
				'.(isset($stop['STOP_ADDR2']) && $stop['STOP_ADDR2'] <> '' ? $stop['STOP_ADDR2'].'<br>' : '').
				$stop['STOP_CITY'].', '.$stop['STOP_STATE'].
				(isset($stop['STOP_COUNTRY']) ? '<br>'.$stop['STOP_COUNTRY'] : '');
}

function get_shipper( $name ) {
	global $client_table;
	$fns = false;
	$result = $client_table->suggest( $client_table->real_escape_string($name), 'shipper');
	if( isset($result) && is_array($result) && count($result) > 0 ) {
		$fns = array();
		$fns['STOP_NAME'] = $result[0]['CLIENT_NAME'];
		$fns['STOP_ADDR1'] = $result[0]['ADDRESS'];
		$fns['STOP_ADDR2'] = $result[0]['ADDRESS2'];
		$fns['STOP_CITY'] = $result[0]['CITY'];
		$fns['STOP_STATE'] = $result[0]['STATE'];
		$fns['STOP_ZIP'] = $result[0]['ZIP_CODE'];
		$fns['STOP_COUNTRY'] = $result[0]['COUNTRY'];
		$fns['STOP_PHONE'] = $result[0]['PHONE_OFFICE'];
		$fns['STOP_EXT'] = $result[0]['PHONE_EXT'];
		$fns['STOP_FAX'] = $result[0]['PHONE_FAX'];
		$fns['STOP_CELL'] = $result[0]['PHONE_CELL'];
		$fns['STOP_EMAIL'] = $result[0]['EMAIL'];
		$fns['STOP_CONTACT'] = $result[0]['CONTACT_NAME'];
		$fns['STOP_LAT'] = isset($result[0]['LAT']) ? floatval($result[0]['LAT']) : 0;
		$fns['STOP_LON'] = isset($result[0]['LON']) ? floatval($result[0]['LON']) : 0;
	}
	return $fns;
}

$checked = true;
function do_checked() {
	global $checked;
	if( $checked ) {
		$c = ' checked';
		$checked = false;
	} else {
		$c = '';
	}
	
	return $c;
}

if( isset($_POST['returnstop']) ) { //! Process form

	if( $sts_debug ) {
		echo "<p>add_return_stop POST = </p>
		<pre>";
		var_dump($_POST);
		echo "</pre>";
	}

	if( ! isset($_POST['stopchoice']) ) {
		echo "<p>stopchoice NOT SET! Please contact support.</p>
		<pre>";
		var_dump($_POST);
		echo "</pre>";
		die;
	}

	//! SCR# 852 - Containers Feature - drop to yard
	if( $_POST['stopchoice'] == 'dyard' ) {
		if( $sts_debug ) {
			echo "<p>add_return_stop: dyard, POST = </p>
			<pre>";
			var_dump($_POST);
			echo "</pre>";
		}

		$lookup = $client_table->im_dock( $_POST['YARD'] );
		
		$check = $yc_table->shipment_num( $_POST['CODE'] );
		if( ! empty($check) )
			$stop['SHIPMENT'] = $check;
		
		if( ! empty($_POST['COMMENTY']) )
			$stop['STOP_COMMENT'] = $_POST['COMMENTY'];
			
		$stop['YARD_CODE'] = $_POST['YARD'];
		$stop['IM_STOP_TYPE'] = 'dropyard';
		
		if( is_array($lookup) ) {
			$stop['STOP_NAME'] = $lookup['CLIENT_NAME'];
			$stop['STOP_ADDR1'] = $lookup['ADDRESS'];
			$stop['STOP_ADDR2'] = $lookup['ADDRESS2'];
			$stop['STOP_CITY'] = $lookup['CITY'];
			$stop['STOP_STATE'] = $lookup['STATE'];
			$stop['STOP_ZIP'] = $lookup['ZIP_CODE'];
			$stop['STOP_COUNTRY'] = $lookup['COUNTRY'];
			$stop['STOP_PHONE'] = $lookup['PHONE_OFFICE'];
			$stop['STOP_EXT'] = $lookup['PHONE_EXT'];
			$stop['STOP_FAX'] = $lookup['PHONE_FAX'];
			$stop['STOP_CELL'] = $lookup['PHONE_CELL'];
			$stop['STOP_EMAIL'] = $lookup['EMAIL'];
			$stop['STOP_CONTACT'] = $lookup['CONTACT_NAME'];
			$stop['STOP_LAT'] = isset($lookup['LAT']) ? floatval($lookup['LAT']) : 0;
			$stop['STOP_LON'] = isset($lookup['LON']) ? floatval($lookup['LON']) : 0;
		}
		
	} else
	//! SCR# 852 - Containers Feature - drop to intermodal
	if( $_POST['stopchoice'] == 'dintermodal' ) {
		if( $sts_debug ) {
			echo "<p>add_return_stop: dintermodal, POST = </p>
			<pre>";
			var_dump($_POST);
			echo "</pre>";
		}

		$lookup = $client_table->im_dock( $_POST['YARD'] );
		
		$check = $yc_table->shipment_num( $_POST['CODE'] );
		if( ! empty($check) )
			$stop['SHIPMENT'] = $check;
		
		if( ! empty($_POST['COMMENTI']) )
			$stop['STOP_COMMENT'] = $_POST['COMMENTI'];
			
	//	$stop['YARD_CODE'] = $_POST['YARD'];
		$stop['IM_STOP_TYPE'] = 'dropdepot';
		
		if( is_array($lookup) ) {
			$stop['STOP_NAME'] = $lookup['CLIENT_NAME'];
			$stop['STOP_ADDR1'] = $lookup['ADDRESS'];
			$stop['STOP_ADDR2'] = $lookup['ADDRESS2'];
			$stop['STOP_CITY'] = $lookup['CITY'];
			$stop['STOP_STATE'] = $lookup['STATE'];
			$stop['STOP_ZIP'] = $lookup['ZIP_CODE'];
			$stop['STOP_COUNTRY'] = $lookup['COUNTRY'];
			$stop['STOP_PHONE'] = $lookup['PHONE_OFFICE'];
			$stop['STOP_EXT'] = $lookup['PHONE_EXT'];
			$stop['STOP_FAX'] = $lookup['PHONE_FAX'];
			$stop['STOP_CELL'] = $lookup['PHONE_CELL'];
			$stop['STOP_EMAIL'] = $lookup['EMAIL'];
			$stop['STOP_CONTACT'] = $lookup['CONTACT_NAME'];
			$stop['STOP_LAT'] = isset($lookup['LAT']) ? floatval($lookup['LAT']) : 0;
			$stop['STOP_LON'] = isset($lookup['LON']) ? floatval($lookup['LON']) : 0;
		}
		
	} else
	//! SCR# 852 - pyard - Containers Feature - Pick from yard
	if( $_POST['stopchoice'] == 'pyard' ) {
		if( $sts_debug ) {
			echo "<p>add_return_stop: pyard, POST = </p>
			<pre>";
			var_dump($_POST);
			echo "</pre>";
		}

		$info = $yc_table->yard_info( $_POST['YARD_CONTAINER'] );

		$lookup = $client_table->im_dock( $info['YARD_CODE'] );
		
		$stop['SHIPMENT'] = $info['SHIPMENT_CODE'];
		
		$stop['STOP_COMMENT'] = 'Pickup Empty Container# '.$info['ST_NUMBER'].' / Trailer# '.$info['UNIT_NUMBER'];
			
		$stop['YARD_CODE'] = $info['YARD_CODE'];
		$stop['IM_STOP_TYPE'] = 'pickyard';
		
		if( is_array($lookup) ) {
			$stop['STOP_NAME'] = $lookup['CLIENT_NAME'];
			$stop['STOP_ADDR1'] = $lookup['ADDRESS'];
			$stop['STOP_ADDR2'] = $lookup['ADDRESS2'];
			$stop['STOP_CITY'] = $lookup['CITY'];
			$stop['STOP_STATE'] = $lookup['STATE'];
			$stop['STOP_ZIP'] = $lookup['ZIP_CODE'];
			$stop['STOP_COUNTRY'] = $lookup['COUNTRY'];
			$stop['STOP_PHONE'] = $lookup['PHONE_OFFICE'];
			$stop['STOP_EXT'] = $lookup['PHONE_EXT'];
			$stop['STOP_FAX'] = $lookup['PHONE_FAX'];
			$stop['STOP_CELL'] = $lookup['PHONE_CELL'];
			$stop['STOP_EMAIL'] = $lookup['EMAIL'];
			$stop['STOP_CONTACT'] = $lookup['CONTACT_NAME'];
			$stop['STOP_LAT'] = isset($lookup['LAT']) ? floatval($lookup['LAT']) : 0;
			$stop['STOP_LON'] = isset($lookup['LON']) ? floatval($lookup['LON']) : 0;
		}

		//! populate trailer in load
		$load_table->update($_POST['CODE'], ['TRAILER' => $info['TRAILER_CODE']]);			
		
		$check = $shipment_table->fetch_rows('SHIPMENT_CODE = '.$info['SHIPMENT_CODE'], 'IM_EMPTY_DROP');
		
		if( is_array($check) && count($check) == 1 && isset($check[0]['IM_EMPTY_DROP'])) {
			$stop_table->add_return_stop( $_POST['CODE'], $stop ); // save first stop
			
		
			$depot = $check[0]['IM_EMPTY_DROP'];
			
			$lookup = $client_table->im_dock( $depot );

			$stop = [
				"SHIPMENT" => $info['SHIPMENT_CODE'], 
				"CURRENT_STATUS" => $stop_table->behavior_state['entry'],
				"STOP_COMMENT" => 'Drop Container# '.$info['ST_NUMBER'].' at Intermodal depot.',
				"IM_STOP_TYPE" => 'dropdepot'
			];
		
			if( is_array($lookup) ) {
				$stop['STOP_NAME'] = $lookup['CLIENT_NAME'];
				$stop['STOP_ADDR1'] = $lookup['ADDRESS'];
				$stop['STOP_ADDR2'] = $lookup['ADDRESS2'];
				$stop['STOP_CITY'] = $lookup['CITY'];
				$stop['STOP_STATE'] = $lookup['STATE'];
				$stop['STOP_ZIP'] = $lookup['ZIP_CODE'];
				$stop['STOP_COUNTRY'] = $lookup['COUNTRY'];
				$stop['STOP_PHONE'] = $lookup['PHONE_OFFICE'];
				$stop['STOP_EXT'] = $lookup['PHONE_EXT'];
				$stop['STOP_FAX'] = $lookup['PHONE_FAX'];
				$stop['STOP_CELL'] = $lookup['PHONE_CELL'];
				$stop['STOP_EMAIL'] = $lookup['EMAIL'];
				$stop['STOP_CONTACT'] = $lookup['CONTACT_NAME'];
				$stop['STOP_LAT'] = isset($lookup['LAT']) ? floatval($lookup['LAT']) : 0;
				$stop['STOP_LON'] = isset($lookup['LON']) ? floatval($lookup['LON']) : 0;
			}
		}
	} else 
	if( $_POST['stopchoice'] == 'firstpick' ) {
		$stop = $stop_table->get_first_pick( $_POST['CODE'] );
	} else 
	if( $_POST['stopchoice'] == 'fns' ) {
		$stop = get_shipper( $sts_default_return_stop );
	} else 
	if( $_POST['stopchoice'] == 'other' ) {
		$stop = get_shipper( $_POST['SHIPPER_NAME'] );
	} else 
	if( $_POST['stopchoice'] == 'zip' ) {
		$stop = array(
			'STOP_NAME' => 'Zip '.$_POST['ZIP_CODE'],
			'STOP_CITY' => $_POST['ZIP_CITY'],
			'STOP_STATE' => $_POST['ZIP_STATE'],
			'STOP_COUNTRY' => $_POST['ZIP_COUNTRY'],
			'STOP_ZIP' => $_POST['ZIP_CODE'],
		);
		//! SCR# 233 - only include lat/lon if numeric
		if( is_numeric($_POST['ZIP_LAT']) && is_numeric($_POST['ZIP_LON'])) {
			$stop['STOP_LAT'] = $_POST['ZIP_LAT'];
			$stop['STOP_LON'] = $_POST['ZIP_LON'];
		}
	}
	
	if( isset($_POST['STOP_DUE']) && $_POST['STOP_DUE'] <> '' &&
		strtotime($_POST['STOP_DUE']) !== false ) {		// Make sure we can read it

		$stop['STOP_DUE'] = date("Y-m-d H:i:s", strtotime($_POST['STOP_DUE']));
	}

	if( isset($stop) && is_array($stop) ) {
		$stop_table->add_return_stop( $_POST['CODE'], $stop );
		//$load_table->update_distances( $_POST['CODE'] );
	}

	if( ! $sts_debug )
		reload_page ( 'exp_dispatch2.php?CODE='.$_POST['CODE'] );
} else if( isset($_GET['CODE']) ) { //! Form
	//! SCR# 233 - clear the default return stop if no shipper matches
	if( ! empty($sts_default_return_stop) && ! get_shipper( $sts_default_return_stop ) )
		$sts_default_return_stop = '';
	
	$first_pick = $stop_table->get_first_pick( $_GET['CODE'] );
	$fns = get_shipper($sts_default_return_stop);
	if( $sts_debug ) {
		echo "<p>add_return_stop fns = </p>
		<pre>";
		var_dump($first_pick, $fns);
		echo "</pre>";
	}
	
	echo '<div class="container theme-showcase" role="main">

	<div class="well well-md">
	<h2><img src="images/load_icon.png" alt="load_icon" height="24"> Add Return Stop For Load #'.$_GET['CODE'].'</h2>
	
	<h3>This is to add an empty leg to return the Tractor/Trailer/Driver to the start.</h3>
	
	<h3>Choices:</h3>
	<form role="form" class="form-horizontal" action="exp_add_return_stop.php" 
				method="post" enctype="multipart/form-data" 
				name="addstop" id="addstop" novalidate>
';
	if( $sts_debug ) {
		echo '
	<input name="debug" type="hidden" value="on">';
	}

	echo '<input name="CODE" type="hidden" value="'.$_GET['CODE'].'">
	';
	if( ! empty($sts_default_return_stop) )
		echo '<div class="form-group">
		<div class="col-sm-4 col-sm-offset-1">
			<div class="radio">
				<label>
				<h4><input type="radio" name="stopchoice" id="fns" value="fns"'.do_checked().'>
				Return to '.htmlspecialchars($sts_default_return_stop).'</h4>
				</label>
			</div>
		</div>
		<div class="col-sm-1">
			<h3><span class="glyphicon glyphicon-arrow-right"></span></h3>
		</div>
		<div class="col-sm-4 well well-sm">
				<p class="form-control-static">'.show_stop($fns).'</p>
		</div>
	</div>
	';

	if( $sts_containers && $container = $yc_table->container_num( $_GET['CODE'] ) ) {
		$trailer = $yc_table->trailer_num( $_GET['CODE'] );
		echo '<div class="form-group">
		<div class="col-sm-4 col-sm-offset-1">
			<div class="radio">
				<label>
				<h4><input type="radio" name="stopchoice" id="dyard" value="dyard"'.do_checked().'>
				Drop '.$trailer.' container# '.$container.' in <span class="bg-success">yard</span></h4>
				<input type="hidden" name="COMMENTY" value="Drop '.$trailer.' container# '.$container.' in yard">
				</label>
			</div>
		</div>
		<div class="col-sm-1">
			<h3><span class="glyphicon glyphicon-arrow-right"></span></h3>
		</div>
		<div class="col-sm-4 well well-sm">
				<p class="form-control-static">'.$yc_table->yard_menu().'</p>
		</div>
	</div>
	';
	}
	
	if( $sts_containers && $container = $yc_table->container_num( $_GET['CODE'] ) ) {
		$trailer = $yc_table->trailer_num( $_GET['CODE'] );
		if( ! empty($trailer) )
			$trailer = 'trailer# '.$trailer.' and ';
		echo '<div class="form-group">
		<div class="col-sm-4 col-sm-offset-1">
			<div class="radio">
				<label>
				<h4><input type="radio" name="stopchoice" id="dintermodal" value="dintermodal"'.do_checked().'>
				Drop '.$trailer.' container# '.$container.' at <span class="bg-success">intermodal depot</span></h4>
				<input type="hidden" name="COMMENTI" value="Drop '.$trailer.' container# '.$container.' at intermodal depot">
				</label>
			</div>
		</div>
		<div class="col-sm-1">
			<h3><span class="glyphicon glyphicon-arrow-right"></span></h3>
		</div>
		<div class="col-sm-4 well well-sm">
				<p class="form-control-static">'.$yc_table->yard_menu(false, 'YARD', 'intermodal').'</p>
		</div>
	</div>
	';
	}
	
	if( $sts_containers && $yc_table->in_yard() > 0 ) {
		echo '<div class="form-group">
		<div class="col-sm-4 col-sm-offset-1">
			<div class="radio">
				<label>
				<h4><input type="radio" name="stopchoice" id="dyard" value="pyard"'.do_checked().'>
				Pickup empty trailer / container from <span class="bg-success">yard/dock/client</span>. Deliver to <span class="bg-success">intermodal depot</span>.</h4>
				</label>
			</div>
		</div>
		<div class="col-sm-1">
			<h3><span class="glyphicon glyphicon-arrow-right"></span></h3>
		</div>
		<div class="col-sm-4 well well-sm">
				<p class="form-control-static">'.$yc_table->in_yard_menu().'</p>
		</div>
	</div>
	';
	}
	
	//! SCR# 233 - do not show this choice if the default return stop is missing
	if( $first_pick )
	echo '<div class="form-group">
		<div class="col-sm-4 col-sm-offset-1">
			<div class="radio">
				<label>
				<h4><input type="radio" name="stopchoice" id="firstpick" value="firstpick"'.do_checked().'>
				Return to the first pick</h4>
				</label>
			</div>
		</div>
		<div class="col-sm-1">
			<h3><span class="glyphicon glyphicon-arrow-right"></span></h3>
		</div>
		<div class="col-sm-4 well well-sm">
				<p class="form-control-static">'.show_stop($first_pick).'</p>
		</div>
	</div>
	';
	
	echo '<div class="form-group">
		<div class="col-sm-4 col-sm-offset-1">
			<div class="radio">
				<label>
				<h4><input type="radio" name="stopchoice" id="other" value="other"'.do_checked().'>
				Go to a shipper</h4>
				</label>
			</div>
		</div>
		<div class="col-sm-1">
			<h3><span class="glyphicon glyphicon-arrow-right"></span></h3>
		</div>
		<div class="col-sm-4 well well-sm">
			<input class="form-control" name="SHIPPER_NAME" id="SHIPPER_NAME" type="text"  
					placeholder="Shipper" maxlength="50" >
		</div>
	</div>
	
	<div class="form-group">
		<div class="col-sm-4 col-sm-offset-1">
			<div class="radio">
				<label>
				<h4><input type="radio" name="stopchoice" id="other" value="zip"'.do_checked().'>
				'.$postcode.'</h4>
				</label>
			</div>
		</div>
		<div class="col-sm-1">
			<h3><span class="glyphicon glyphicon-arrow-right"></span></h3>
		</div>
		<div class="col-sm-4 well well-sm">
			<input class="form-control" name="ZIP_CODE" id="ZIP_CODE" type="text"  
					placeholder="'.$postcode.'" maxlength="10" >
			<input class="form-control" name="ZIP_CITY" id="ZIP_CITY" type="text"  
					readonly >
			<input class="form-control" name="ZIP_STATE" id="ZIP_STATE" type="text"  
					readonly >
			<input class="form-control" name="ZIP_COUNTRY" id="ZIP_COUNTRY" type="text"  
					readonly >
			<input class="form-control" name="ZIP_LAT" id="ZIP_LAT" type="text"  
					readonly >
			<input class="form-control" name="ZIP_LON" id="ZIP_LON" type="text"  
					readonly >
		</div>
	</div>
	<div class="form-group">
		<div class="col-sm-5 col-sm-offset-1">
			<h4>(Optional) Due date/time</h4>
		</div>
		<div class="col-sm-4">
			<input class="form-control'.($_SESSION['ios'] == 'true' ? '' : ' timestamp').'" name="STOP_DUE" id="STOP_DUE" type="'.($_SESSION['ios'] == 'true' ? 'datetime-local' : 'text').'"  
					placeholder="Due" formnovalidate>
		</div>

	</div>
	
	<div class="form-group">
		<div class="col-sm-6 col-sm-offset-1">
			<button class="btn btn-md btn-success" name="returnstop" type="submit" ><span class="glyphicon glyphicon-ok"></span> Save Changes</button>
			<a class="btn btn-md btn-default" href="exp_dispatch2.php?CODE='.$_GET['CODE'].'"><span class="glyphicon glyphicon-remove"></span> Cancel</a>
		</div>
	</div>

</form>		
		
';

}
?>

	<script language="JavaScript" type="text/javascript"><!--
		// Comment out so it is global scope
		$(document).ready( function () {
		
			var SHIPPER_NAME_clients = new Bloodhound({
			  name: 'SHIPPER_NAME',
			  remote: {
				  url: 'exp_suggest_client.php?code=Vinegar&type=shipper&query=%QUERY',
				  wildcard: '%QUERY'
				  },
			  datumTokenizer: Bloodhound.tokenizers.obj.whitespace('LABEL'),
			  queryTokenizer: Bloodhound.tokenizers.whitespace
			});
						
			SHIPPER_NAME_clients.initialize();

			$('#SHIPPER_NAME').typeahead(null, {
			  name: 'SHIPPER_NAME',
			  minLength: 2,
			  limit: <?php echo $client_matches; ?>,
			  highlight: true,
			  display: 'CLIENT_NAME',
			  source: SHIPPER_NAME_clients,
			    templates: {
			    suggestion: Handlebars.compile(
			      '<p><strong>{{CLIENT_NAME}}</strong> – {{CONTACT_TYPE}}, {{CONTACT_NAME}}, {{PHONE_OFFICE}}</p>'
			    )
			  }
			});
			
			$('#SHIPPER_NAME').on('click', function() {
				$('input:radio[name="stopchoice"]').filter('[value="other"]').attr('checked', true);
			});
			
			var ZIP_CODE_zips = new Bloodhound({
			  name: 'ZIP_CODE',
			  remote : {
				  url: 'exp_suggest_zip.php?code=Balsamic&query=%QUERY',
				  wildcard: '%QUERY'
			  },
			  datumTokenizer: Bloodhound.tokenizers.obj.whitespace('ZipCode'),
			  queryTokenizer: Bloodhound.tokenizers.whitespace
			});
			
			ZIP_CODE_zips.initialize();

			$('#ZIP_CODE').typeahead(null, {
			  name: 'ZIP_CODE',
			  minLength: 2,
			  limit: 10,
			  highlight: true,
			  display: 'ZipCode',
			  source: ZIP_CODE_zips,
			    templates: {
			    suggestion: Handlebars.compile(
			      '<p><strong>{{ZipCode}}</strong> – {{CityMixedCase}}, {{State}}</p>'
			    )
			  }
			});

			$('#ZIP_CODE').on('typeahead:selected', function(obj, datum, name) {
				$('input#ZIP_CITY').val(datum.CityMixedCase);
				$('input#ZIP_STATE').val(datum.State);
				$('input#ZIP_COUNTRY').val(datum.Country);
				$('input#ZIP_LAT').val(datum.Latitude);
				$('input#ZIP_LON').val(datum.Longitude);
			});
			
			$('#ZIP_CODE').on('click', function() {
				$('input:radio[name="stopchoice"]').filter('[value="zip"]').attr('checked', true);
			});
			
		});
	//--></script>

<?php

require_once( "include/footer_inc.php" );
?>
