<?php 
// Set flag that this is a parent file
define( '_STS_INCLUDE', 1 );

function create_row( $num, $zip = '', $city = '', $state = '', $country = '' ) {
	return '<div class="form-group well well-sm" data-row="'.$num.'" id="row'.$num.'">
	<div class="col-md-1 handle">
		<h4><span class="glyphicon glyphicon-sort"></span></h4>
	</div>
	<div class="col-md-1">
		<a class="btn btn-md btn-danger delrow" onclick="delrow('.$num.');"><span class="glyphicon glyphicon-remove"></span></a>
	</div>
	<div class="col-md-2">
		<input class="form-control" name="ZIP[]'.$num.'" id="ZIP'.$num.'" type="text"
		pattern="(\d{5}([\-]\d{4})?)|([A-Za-z][0-9][A-Za-z] [0-9][A-Za-z][0-9])"
		placeholder="Zip" value="'.$zip.'">
	</div>
	<div class="col-md-2">
		<input class="form-control" name="CITY[]'.$num.'" id="CITY'.$num.'" type="text"  
				placeholder="City" value="'.$city.'" readonly>
	</div>
	<div class="col-md-1">
		<input class="form-control" name="STATE[]'.$num.'" id="STATE'.$num.'" type="text"  
				placeholder="State" value="'.$state.'" readonly>
	</div>
	<div class="col-md-1">
		<input class="form-control" name="COUNTRY[]'.$num.'" id="COUNTRY'.$num.'" type="text"  
				placeholder="Country" value="'.$country.'" readonly>
	</div>
	
	<script language="JavaScript" type="text/javascript"><!--
	'."			
		$(document).ready( function () {
		
			var ZIP".$num."_zips = new Bloodhound({
			  name: 'ZIP".$num."',
			  remote : {
				  url: 'exp_suggest_zip.php?code=Balsamic&query=%QUERY',
				  wildcard: '%QUERY'
			  },
			  datumTokenizer: Bloodhound.tokenizers.obj.whitespace('ZipCode'),
			  queryTokenizer: Bloodhound.tokenizers.whitespace
			});
			
			ZIP".$num."_zips.initialize();

			$('#ZIP".$num."').typeahead(null, {
			  name: 'ZIP".$num."',
			  minLength: 2,
			  limit: 10,
			  highlight: true,
			  display: 'ZipCode',
			  source: ZIP".$num."_zips,
			    templates: {
				empty: function(val){
					return '<p class=\"bg-danger text-danger\"><strong>Unrecognized ZIP/Postal code</strong> <a href=\"exp_suggest_zip.php?missing_zip='+val.query+'\" target=\"blank\">Please check!</a></p>';
			    },
				
			    suggestion: Handlebars.compile(
			      '<p><strong>{{ZipCode}}</strong> – {{CityMixedCase}}, {{State}}</p>'
			    )
			  }
			}).on('typeahead:asyncrequest', function(e) {
				$(e.target).addClass('loading');
			})
			.on('typeahead:asynccancel typeahead:asyncreceive', function(e) {
				$(e.target).removeClass('loading');
			});
			
			$('#ZIP".$num."').bind('typeahead:selected', function(obj, datum, name) {
				$('input#CITY".$num."').val(datum.CityMixedCase);
				$('input#STATE".$num."').val(datum.State);
				$('input#COUNTRY".$num."').val(datum.Country);
				//console.log('Just updated row ',\$(this).parents('.well').attr('data-row'));
				update_check();
			});

		});
	//--></script>".'

</div>
';
}

function make_address( $city, $state, $zip, $country ) {
	$address = [];
	$address['CITY'] = $city;
	$address['STATE'] = $state;
	$address['ZIP_CODE'] = $zip;
	$address['COUNTRY'] = $country;
	if( ! in_array($address['COUNTRY'], ['USA', 'Canada']))
		$address['COUNTRY'] = 'USA';
	
	return $address;
}

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

if( isset($_GET) && isset($_GET["addrow"])) { //! AJAX - generate a new row
	echo create_row( $_GET["addrow"] );
} else { //! Setup

	// Setup Session
	require_once( "include/sts_session_setup.php" );
	require_once( "include/sts_config.php" );
	$sts_debug = (isset($_GET['debug'])  || isset($_POST['debug'])) && in_group( EXT_GROUP_DEBUG );
	require_once( "include/sts_session_class.php" );
	
	$my_session = sts_session::getInstance( $exspeedite_db, $sts_debug );
	$my_session->access_check( EXT_GROUP_USER );	// Make sure we should be here
	
	$sts_subtitle = "Check Distance";
	require_once( "include/header_inc.php" );
	require_once( "include/navbar_inc.php" );
		
	require_once( "include/sts_setting_class.php" );
	require_once( "include/sts_zip_class.php" );
	require_once( $sts_crm_dir.DIRECTORY_SEPARATOR."PCMILER/exp_get_miles.php" );
	
	echo '<div class="container theme-showcase" role="main">
	';
	
	 if( isset($_POST) && isset($_POST['check']) ) { //! Perform check
		//echo '<pre>';
		//var_dump($_POST);
		//echo '</pre>';
		//die;
	
		$num_rows = count($_POST["ZIP"]);
		
		echo '<div id="loading"><h2 class="text-center"><img src="images/loading.gif" alt="loading" width="125" height="125" /><br>Checking Distances...</h2>
			<div class="progress">
				<div class="progress-bar progress-bar-success" id="progress" role="progressbar" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100" style="width: 0%">
			  </div>
			</div>
		</div>';
		ob_flush(); flush();
	
		$zip_table = sts_zip::getInstance($exspeedite_db, $sts_debug);
		$pcm = sts_pcmiler_api::getInstance( $exspeedite_db, $sts_debug );
		$pcm->set_routing_parameters( $_POST ); // Override parameters
		
		$setting_table = sts_setting::getInstance($exspeedite_db, $sts_debug);

		$sts_maps_key = $setting_table->get( 'api', 'PCM_API_KEY' );
		$cpm = isset($_POST['CPM']) ? $_POST['CPM'] :
			$setting_table->get( 'main', 'Cost per mile' );
		$mph = isset($_POST['MPH']) ? $_POST['MPH'] :
			$setting_table->get( 'main', 'Miles per hour' );
		
		$table = '<div class="table-responsive  bg-white">
				<table class="display table table-striped table-condensed table-bordered table-hover table-nobm" id="DISTANCE">
				<thead><tr class="exspeedite-bg">
				<th>Stop</th>
				<th>City, State, Country</th>
				<th>Source</th>
				<th class="text-right">Leg Miles</th>
				<th class="text-right">Total Miles</th>
				<th class="text-right">Leg Cost</th>
				<th class="text-right">Total Cost</th>
				<th class="text-right">Leg Hours</th>
				<th class="text-right">Total Hours</th>
				</tr>
			</thead>
		<tbody>';

		// Make MapRoutesRequestBody from zipcode.
		$pin = array();
		$Stops = array();
		
		for( $i=0; $i < $num_rows; $i++ ) {
			
			$Stops[$i]['Address']['Zip'] = $_POST["ZIP"][$i];
			$geocode[$i] = geocode_address($_POST["ZIP"][$i], $sts_maps_key, $sts_debug);
			if($i == 0 && isset($geocode[$i]['Coords'])){
				$pin[0]['Point']['Lat'] = (float)$geocode[$i]['Coords']['Lat'];
				$pin[0]['Point']['Lon'] = (float)$geocode[$i]['Coords']['Lon'];
				$pin[0]['Image'] = "ltruck_r";
			}else if($i == $num_rows-1 && isset($geocode[$i]['Coords'])){
				$pin[1]['Point']['Lat'] = (float)$geocode[$i]['Coords']['Lat'];
				$pin[1]['Point']['Lon'] = (float)$geocode[$i]['Coords']['Lon'];
				$pin[1]['Image'] = "lbldg_bl";
			}
			
		}		
		// var_dump($pin);
		// Build the request body for the MapRoutes API
		$ch = curl_init();
		$MapRoutesRequestBody = array(
			"Map" => [
				"Viewport" => [
					"Center" => null,
					"ScreenCenter" => null,
					"ZoomRadius" => 0,
					"CornerA" => null,
					"CornerB" => null,
					"Region" => 0,
				],
				"Projection" => 0,
				"Style" => 11,
				"ImageOption" => 0,
				"Width" => 1028,
				"Height" => 450,
				"Drawers" => [0, 2, 4, 6, 7,8,9,10, 11, 16,17,18,20,21,22,23,24,26],
				"LegendDrawer" => [["Type" => 0, "DrawOnMap" => true]],
				"GeometryDrawer" => null,
				"PinDrawer" => [
					"Pins" => $pin
				],
				"PinCategories" => null,
				"TrafficDrawer" => null,
				"MapLayering" => 0,
				"Language" => null,
				"ImageSource" => null,
			],
			"Routes" => [
				[
					"RouteId" => null,
					"Stops" => $Stops,
					"Options" => [
						"AFSetIDs" => null,
						"BordersOpen" => null,
						"ClassOverrides" => 0,
						"DistanceUnits" => 0,
						"ElevLimit" => null,
						"FerryDiscourage" => false,
						"FuelRoute" => false,
						"HazMatType" => 0,
						"HighwayOnly" => true,
						"HubRouting" => false,
						"OverrideRestrict" => false,
						"RouteOptimization" => 0,
						"RoutingType" => 0,
						"TollDiscourage" => false,
						"TruckCfg" => null,
						"VehicleType" => 0,
					],
					"DrawLeastCost" => false,
					"RouteLegOptions" => null,
					"StopLabelDrawer" => 0,
					"routeType" => $_POST['PCM_ROUTING'],
					"mode" => "Trucking",
				],
			],
		);
		// var_dump($MapRoutesRequestBody);
		curl_setopt_array($ch, array(
				CURLOPT_URL => 'https://pcmiler.alk.com/apis/rest/v1.0/service.svc/mapRoutes?authToken='.$sts_maps_key,
				CURLOPT_RETURNTRANSFER => true,
				CURLOPT_ENCODING => '',
				CURLOPT_MAXREDIRS => 10,
				CURLOPT_TIMEOUT => 0,
				CURLOPT_FOLLOWLOCATION => true,
				CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
				CURLOPT_CUSTOMREQUEST => 'POST',
				CURLOPT_POSTFIELDS =>json_encode($MapRoutesRequestBody),
				CURLOPT_HTTPHEADER => array(
					'Content-Type: application/json'
				),
		));

		$response = curl_exec($ch);
	//	$file_path = "map.jpg";
	//	file_put_contents($file_path, $response);
		curl_close($ch);
		$base64   = base64_encode($response);
        $file_path = "data:image/jpg;base64,".$base64;

		$map = '<div class="col-md-12" id="PCMAP" style="margin-top: 10px;"><iframe src="'.$file_path.'" width="100%" height="450" frameborder="0" style="border:0;" allowfullscreen=""></iframe></div>';
		
		$tot_miles = 0;
		$tot_cost = 0;
		$tot_hours = 0;
		
		for( $c=0; $c < $num_rows; $c++ ) {
			echo '<script language="JavaScript" type="text/javascript"><!--
				var v = '.($c/$num_rows*100).';
				$("#progress").attr("aria-valuenow",v).css("width", v+"%");
			//--></script>';
			ob_flush(); flush();
			
			if( $c > 0 ) {
				$addr1 = make_address( $_POST["CITY"][$c-1], $_POST["STATE"][$c-1], $_POST["ZIP"][$c-1], $_POST["COUNTRY"][$c-1] );
				$addr2 = make_address( $_POST["CITY"][$c], $_POST["STATE"][$c], $_POST["ZIP"][$c], $_POST["COUNTRY"][$c] );
				
				$miles = $zip_table->get_distance_various( $addr1, $addr2, false );
				$source = $zip_table->get_source().' @ '.$zip_table->get_last_at();
			} else {
				$miles = 0;
				$source = '';
			}
			$cost = $miles * $cpm;
			$hours = $miles / $mph;
	
			$tot_miles += $miles;
			$tot_cost += $cost;
			$tot_hours += $hours;
	
			$table .= '<tr>
					<td>'.$_POST["ZIP"][$c].'</td>
					<td>'.$_POST["CITY"][$c].', '.$_POST["STATE"][$c].', '.$_POST["COUNTRY"][$c].'</td>
					<td>'.$source.'</td>
					<td class="text-right">'.number_format($miles,1).'</td>
					<td class="text-right">'.number_format($tot_miles,1).'</td>
					<td class="text-right">'.number_format($cost,2).'</td>
					<td class="text-right">'.number_format($tot_cost,2).'</td>
					<td class="text-right">'.number_format($hours,2).'</td>
					<td class="text-right">'.number_format($tot_hours,2).'</td>
				</tr>';
		}
		$table .= '
	   		</tbody>
			</table>
			</div>
			';
		
		update_message( 'loading', '' );
		echo '<form role="form" class="form-horizontal" action="exp_distance2.php" 
						method="post" enctype="multipart/form-data" 
						name="back" id="back">
		';
		foreach( $_POST["ZIP"] as $c => $val ) {
			echo '<input type=hidden name="ZIP['.$c.']" value="'.$val.'">
			<input type=hidden name="CITY['.$c.']" value="'.$_POST["CITY"][$c].'">
			<input type=hidden name="STATE['.$c.']" value="'.$_POST["STATE"][$c].'">
			<input type=hidden name="COUNTRY['.$c.']" value="'.$_POST["COUNTRY"][$c].'">
			';
		}
		
		if( $sts_debug ) {
			echo "<pre>POST\n";
			var_dump($_POST);
			echo "</pre>";
		}

		echo '<h2>Trip Summary
			<button class="btn btn-sm btn-default" name="goback" type="submit" ><span class="glyphicon arrow-left"></span> Go Back</button> 
			<a class="btn btn-sm btn-default" name="print" onclick="window.print();return false;"><span class="glyphicon glyphicon-print"></span> Print</a>
		</h2>
		</form>
		<h4>Routing: <span class="text-info">'.$_POST["PCM_ROUTING"].'</span>'.
			'&nbsp;&nbsp;&nbsp;Override: <span class="text-info">'.$_POST["PCM_CLASS_OVERRIDE"].'</span>'.
			'&nbsp;&nbsp;&nbsp;Borders Open: <span class="text-info">'.$_POST["PCM_BORDERS_OPEN"].'</span>'.
			'&nbsp;&nbsp;&nbsp;Highway Only: <span class="text-info">'.$_POST["PCM_HIGHWAY_ONLY"].'</span>'.
			'&nbsp;&nbsp;&nbsp;Optimize: <span class="text-info">'.$_POST["PCM_ROUTE_OPTIMIZE"].'</span>
		</h4>
		
		<h3>Miles '.number_format($tot_miles,1) .
			'&nbsp;&nbsp;&nbsp;Time '.number_format($tot_hours,2).
			'&nbsp;&nbsp;&nbsp;Cost '.number_format($tot_cost,2).
			'&nbsp;&nbsp;&nbsp;<span class="text-info tip" title="You can change this in the settings">(Based on '.$mph.' miles per hour and '.$cpm.' cost per mile)</span>'.
			'</h3>
		'.$table.$map;
		
		
	} else { //! Input rows of zipcodes
		$cpm = $setting_table->get( 'main', 'Cost per mile' );
		$mph = $setting_table->get( 'main', 'Miles per hour' );

		echo '<form role="form" class="form-inline" action="exp_distance2.php" 
						method="post" enctype="multipart/form-data" 
						name="distance" id="distance">
						
		'.($sts_debug ? '<input name="debug" type="hidden" value="true">' : '').'
		<h2 class="tighter-top"><img src="images/order_icon.png" alt="order_icon" height="24"> Check Distances / Plan Route
					<button class="btn btn-sm btn-success" name="check" id="check" type="submit" ><span class="glyphicon glyphicon-ok"></span> Check Distances</button> 
					<a class="btn btn-sm btn-success" id="addrow"><span class="glyphicon glyphicon-plus"></span> Add Stop</a>
					<a class="btn btn-sm btn-danger" id="reset" href="exp_distance2.php"><span class="glyphicon glyphicon-remove"></span> Reset</a>
					<a class="btn btn-md btn-default" id="distance_cancel" href="index.php"><span class="glyphicon glyphicon-remove"></span> Back</a>
		</h2>
		<div class="row">
			<div class="col-md-2">
				<label for="PCM_ROUTE_OPTIMIZE" class="control-label">Routing</label>
				'.$setting_table->setting_menu('api', 'PCM_ROUTING').'
			</div>
			<div class="col-md-3">
				<label for="PCM_CLASS_OVERRIDE" class="control-label">Override</label>
				'.$setting_table->setting_menu('api', 'PCM_CLASS_OVERRIDE').'
			</div>
			<div class="col-md-2">
				<label for="PCM_BORDERS_OPEN" class="control-label">Border</label>
				'.$setting_table->setting_menu('api', 'PCM_BORDERS_OPEN').'
			</div>
			<div class="col-md-2">
				<label for="PCM_HIGHWAY_ONLY" class="control-label">Hwy</label>
				'.$setting_table->setting_menu('api', 'PCM_HIGHWAY_ONLY').'
			</div>
			<div class="col-md-2">
				<label for="PCM_ROUTE_OPTIMIZE" class="control-label">Opt</label>
				'.$setting_table->setting_menu('api', 'PCM_ROUTE_OPTIMIZE').'
			</div>
		</div>		
		<hr>
		<div class="row">
			<div class="col-md-5">
				<label for="MPH" class="col-md-4 control-label" style="margin-top: 10px;">Average Speed</label>
				<div class="col-md-1">
					<input id="MPH" name="MPH" type="text" class="form-control text-right tip" title="This defaults to the system setting (main/Miles per hour)" value="'.$mph.'">
				</div>
			</div>
			<div class="col-md-5">
				<label for="CPM" class="col-md-4 control-label" style="margin-top: 10px;">Cost Per Mile</label>
				<div class="col-md-1">
					<input id="CPM" name="CPM" type="text" class="form-control text-right tip" title="This defaults to the system setting (main/Cost per mile)" value="'.$cpm.'">
				</div>
			</div>
		</div>		
		<hr>
		<div id="sortable">
		';
		if( isset($_POST) && isset($_POST['goback']) ) { //! goback - restore rows
			foreach($_POST["ZIP"] as $c => $val) {
				$zip = $val;
				$city = $_POST["CITY"][$c];
				$state = $_POST["STATE"][$c];
				$country = $_POST["COUNTRY"][$c];
				echo create_row( $c, $zip, $city, $state, $country );
			}
			
		} else { //! create two empty rows
			for( $c=1; $c<=2; $c++ ) {
				echo create_row( $c );
			}
		}
		
		echo '
		</div>
		</form>
		';
	}
	
	echo '</div>
	';
?>
	<script language="JavaScript" type="text/javascript"><!--
		
		// Sets the client info
		$(document).ready( function () {
			$("#sortable").sortable({
					tolerance: 'pointer',
					handle: ".handle",
					cursor: 'move',
					revert: true,
			});
		});
		
		function get_next() {
			var myarray = [];
			$("#sortable").children().each(function( index ) {
			  myarray.push(parseInt($(this).attr('data-row')));
			});
			return Math.max.apply(Math, myarray) + 1;
		}
				
		function update_delrow() {
			//console.log('update_delrow', $("#sortable").children().length);
			if( $("#sortable").children().length < 3 ) {
				$("#sortable .delrow").attr('disabled','disabled').change();
			} else {
				$("#sortable .delrow").removeAttr('disabled').change();
			}
		}

		function update_check() {
			var filled = true;
			//console.log($('input[name^="ZIP"').length);
			$('input[name^="ZIP"').each(function( index ) {
				if( $(this).val().length == 0 ) filled = false;
			});
			if( filled ) {
				$("#check").removeAttr('disabled').change();
			} else {
				$("#check").attr('disabled','disabled').change();
			}
		}
		
		$('#addrow').on('click', function(event) {
			$.get("exp_distance2.php?addrow="+get_next(), function(data) {
				//$("#sortable").sortable('destroy');
				$("#sortable").append(data).change();
				//$("#sortable").sortable();
				//console.log('addrow', $("#sortable").length, $("#sortable").children().length );
				update_delrow();
				update_check();
			});
		});
		
		function delrow( num ) {
			//console.log('delrow', num, $("#sortable").children().length );
			//$('#row'+num).css('background', 'yellow');
			$('#row'+num).remove();
			update_delrow();
			update_check();
		}
		
		update_delrow();
		
		update_check();
		
	//--></script>


<?php

	require_once( "include/footer_inc.php" );
}
?>
