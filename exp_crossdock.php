<?php 
// Set flag that this is a parent file
define( '_STS_INCLUDE', 1 );

// Setup Session
require_once( "include/sts_session_setup.php" );

require_once( "include/sts_config.php" );
$sts_debug = (isset($_GET['debug'])  || isset($_POST['debug'])) && in_group( EXT_GROUP_DEBUG );
require_once( "include/sts_session_class.php" );

$my_session = sts_session::getInstance( $exspeedite_db, $sts_debug );
$my_session->access_check( EXT_GROUP_DISPATCH );	// Make sure we should be here

$sts_subtitle = "Crossdock";
require_once( "include/header_inc.php" );
require_once( "include/navbar_inc.php" );

require_once( "include/sts_stop_class.php" );
require_once( "include/sts_load_class.php" );
require_once( "include/sts_setting_class.php" );

$setting_table = sts_setting::getInstance($exspeedite_db, $sts_debug);
$client_matches = (int) $setting_table->get( 'option', 'CLIENT_MATCHES' );
if( $client_matches <= 0 ) $client_matches = 20;

$stop_table = sts_stop::getInstance($exspeedite_db, $sts_debug);
$load_table = sts_load::getInstance($exspeedite_db, $sts_debug);

if( $sts_debug ) echo "<p>exp_crossdock</p>";

if( isset($_GET['LOAD']) && isset($_GET['REMOVE']) ) { //! Remove dropdock, revert back to drop (consignee)

	echo '<p class="text-center"><img src="images/loading.gif" alt="loading" width="125" height="125" /></p>';
	$result = $stop_table->update( $_GET['REMOVE'], array(
		"STOP_TYPE" => "drop", 
		"STOP_NAME" => "NULL",
		"STOP_ADDR1" => "NULL",
		"STOP_ADDR2" => "NULL",
		"STOP_CITY" => "NULL",
		"STOP_STATE" => "NULL",
		"STOP_ZIP" => "NULL",
		"STOP_COUNTRY" => "NULL",
		
		"STOP_CONTACT" => "NULL",
		"STOP_PHONE" => "NULL",
		"STOP_EXT" => "NULL",
		"STOP_FAX" => "NULL",
		"STOP_CELL" => "NULL",
		"STOP_EMAIL" => "NULL",
		"STOP_LAT" => "NULL",
		"STOP_LON" => "NULL",
		"YARD_CODE" => "NULL"
	) );
	$dummy = $stop_table->add_load_status( $_GET['LOAD'], 'remove crossdock '.($result ? "OK" : "Failed") );
	$dummy = $load_table->update_distances( $_GET['LOAD'], false, $_GET['REMOVE'] );
	
	if( $result && ! $sts_debug )
		reload_page ( 'exp_dispatch2.php?CODE='.$_GET['LOAD'] );
	
} else if( isset($_POST['crossdock']) ) { //! Add dropdock, ship to dock
	
	if( $sts_debug ) {
		echo "<p>post = </p>
		<pre>";
		var_dump($_POST);
		echo "</pre>";
	}
	echo '<p class="text-center"><img src="images/loading.gif" alt="loading" width="125" height="125" /></p>';
	
	// process
	$changes = array( "STOP_TYPE" => "dropdock" );
	if( isset($_POST["DOCK_NAME"]) && $_POST["DOCK_NAME"] <> "" )	$changes["STOP_NAME"] = $_POST["DOCK_NAME"];
	if( isset($_POST["DOCK_ADDR1"]) && $_POST["DOCK_ADDR1"] <> "" )	$changes["STOP_ADDR1"] = $_POST["DOCK_ADDR1"];
	if( isset($_POST["DOCK_ADDR2"]) && $_POST["DOCK_ADDR2"] <> "" )	$changes["STOP_ADDR2"] = $_POST["DOCK_ADDR2"];
	if( isset($_POST["DOCK_CITY"]) && $_POST["DOCK_CITY"] <> "" )	$changes["STOP_CITY"] = $_POST["DOCK_CITY"];
	if( isset($_POST["DOCK_STATE"]) && $_POST["DOCK_STATE"] <> "" )	$changes["STOP_STATE"] = $_POST["DOCK_STATE"];
	if( isset($_POST["DOCK_ZIP"]) && $_POST["DOCK_ZIP"] <> "" )		$changes["STOP_ZIP"] = $_POST["DOCK_ZIP"];
	if( isset($_POST["DOCK_COUNTRY"]) && $_POST["DOCK_COUNTRY"] <> "" )		$changes["STOP_COUNTRY"] = $_POST["DOCK_COUNTRY"];

	if( isset($_POST["DOCK_CONTACT"]) && $_POST["DOCK_CONTACT"] <> "" )	$changes["STOP_CONTACT"] = $_POST["DOCK_CONTACT"];
	if( isset($_POST["DOCK_PHONE"]) && $_POST["DOCK_PHONE"] <> "" )	$changes["STOP_PHONE"] = $_POST["DOCK_PHONE"];
	if( isset($_POST["DOCK_EXT"]) && $_POST["DOCK_EXT"] <> "" )		$changes["STOP_EXT"] = $_POST["DOCK_EXT"];
	if( isset($_POST["DOCK_FAX"]) && $_POST["DOCK_FAX"] <> "" )		$changes["STOP_FAX"] = $_POST["DOCK_FAX"];
	if( isset($_POST["DOCK_CELL"]) && $_POST["DOCK_CELL"] <> "" )	$changes["STOP_CELL"] = $_POST["DOCK_CELL"];
	if( isset($_POST["DOCK_EMAIL"]) && $_POST["DOCK_EMAIL"] <> "" )	$changes["STOP_EMAIL"] = $_POST["DOCK_EMAIL"];
	if( isset($_POST["DOCK_LAT"]) && $_POST["DOCK_LAT"] <> "" )	$changes["STOP_LAT"] = $_POST["DOCK_LAT"];
	if( isset($_POST["DOCK_LON"]) && $_POST["DOCK_LON"] <> "" )	$changes["STOP_LON"] = $_POST["DOCK_LON"];
	if( isset($_POST["YARD_CODE"]) && $_POST["YARD_CODE"] <> "" )	$changes["YARD_CODE"] = $_POST["YARD_CODE"];

	
	$result = $stop_table->update( $_POST['STOP'], $changes );
	$dummy = $stop_table->add_load_status( $_POST['LOAD'], 'add crossdock '.$_POST["DOCK_NAME"].' '.($result ? "OK" : "Failed")." ".$stop_table->list_stops( $_POST['LOAD'] ) );
	$dummy = $load_table->update_distances( $_POST['LOAD'], false, $_POST['STOP'] );
	
	if( $result && ! $sts_debug )
		reload_page ( 'exp_dispatch2.php?CODE='.$_POST['LOAD'] );

} else if( isset($_GET['LOAD']) && isset($_GET['SHIPMENT']) ) {
	
	$result = $stop_table->fetch_rows( "LOAD_CODE = ".$_GET['LOAD'].
		" AND  SHIPMENT = ".$_GET['SHIPMENT'].
		" AND STOP_TYPE = 'drop'", "STOP_CODE,
		(SELECT COUNT(*) FROM EXP_SHIPMENT
		WHERE LOAD_CODE = ".$_GET['LOAD'].") AS SHIPMENTS" );
	
	if( is_array($result) && count($result) > 0 ) {
		$stop_code = $result[0]["STOP_CODE"];
		$num_shipments = $result[0]["SHIPMENTS"];
		
		echo '<div class="container theme-showcase" role="main">

	<div class="well well-md">
	<h2><img src="images/load_icon.png" alt="load_icon" height="24"> Drop and Hook (TL) / Crossdock (LTL) Shipment #'.$_GET['SHIPMENT'].'</h2>
	
	<h4>This is to deliver the shipment to a dock rather than the consignee. Stop = '.$stop_code.'</h4>
';
	if( $num_shipments == 1 ) {
		echo '<h4>So far load # '.$_GET['LOAD'].' has only 1 shipment, so this is Drop and Hook (TL)</h4>
';
	} else {
		echo '<h4>As load # '.$_GET['LOAD'].' has '.$num_shipments.' shipments, this is Crossdock (LTL)</h4>
';		
	}
	echo '<form role="form" class="form-horizontal" action="exp_crossdock.php" 
				method="post" enctype="multipart/form-data" 
				name="crossdock" id="crossdock">
';
	if( $sts_debug ) {
		echo '
	<input name="debug" type="hidden" value="on">';
	}

	echo '<input name="LOAD" type="hidden" value="'.$_GET['LOAD'].'">
		<input name="SHIPMENT" type="hidden" value="'.$_GET['SHIPMENT'].'">
		<input name="STOP" type="hidden" value="'.$stop_code.'">
		<input name="YARD_CODE" id="YARD_CODE" type="hidden" value="">
		<div class="form-group">
			<div class="col-sm-6">
				<div class="form-group">
					<label for="DOCK_NAME" class="col-sm-4 control-label">Dock</label>
					<div class="col-sm-8">
						<input class="form-control" name="DOCK_NAME" id="DOCK_NAME" type="text"  
								placeholder="Dock" maxlength="50" autofocus>
					</div>
	
					<label for="DOCK_ADDR1" class="col-sm-4 control-label" id="VALID">Addr1</label>
					<div class="col-sm-8">
						<input class="form-control" name="DOCK_ADDR1" id="DOCK_ADDR1" type="text"  
						placeholder="Addr1" maxlength="50" readonly>
					</div>
	
					<label for="DOCK_ADDR2" class="col-sm-4 control-label">Addr2</label>
					<div class="col-sm-8">
						<input class="form-control" name="DOCK_ADDR2" id="DOCK_ADDR2" type="text"  
						placeholder="Addr2" maxlength="50" readonly>
					</div>
					
					<label for="DOCK_CITY" class="col-sm-4 control-label">City</label>
					<div class="col-sm-8">
						<input class="form-control" name="DOCK_CITY" id="DOCK_CITY" type="text"  
						placeholder="City" maxlength="40" readonly>
					</div>
					
					<label for="DOCK_STATE" class="col-sm-4 control-label">State</label>
					<div class="col-sm-8">
						<input class="form-control" name="DOCK_STATE" id="DOCK_STATE" type="text"  
						placeholder="State" maxlength="2" readonly>
					</div>
						
					<label for="DOCK_ZIP" class="col-sm-4 control-label">Zip</label>
					<div class="col-sm-8">
						<input class="form-control" name="DOCK_ZIP" id="DOCK_ZIP" type="text"  
						placeholder="Zip" maxlength="11" readonly>
					</div>
					<label for="DOCK_COUNTRY" class="col-sm-4 control-label">Country</label>
					<div class="col-sm-8">
						<input class="form-control" name="DOCK_COUNTRY" id="DOCK_COUNTRY" type="text"  
						placeholder="Country" maxlength="11" readonly>
					</div>
			
					<label for="DOCK_CONTACT" class="col-sm-4 control-label">Contact</label>
					<div class="col-sm-8">
						<input class="form-control" name="DOCK_CONTACT" id="DOCK_CONTACT" type="text"  
						placeholder="Contact" maxlength="50"  readonly>
					</div>
					
					<label for="DOCK_PHONE" class="col-sm-4 control-label">Phone</label>
					<div class="col-sm-5">
						<input class="form-control" name="DOCK_PHONE" id="DOCK_PHONE" type="text"  
						placeholder="Phone" maxlength="20"  readonly>
					</div>
					
					<div class="col-sm-3">
						<input class="form-control" name="DOCK_EXT" id="DOCK_EXT" type="text"  
						placeholder="Ext" maxlength="5"  readonly>
					</div>
					
					<label for="DOCK_FAX" class="col-sm-4 control-label">Fax</label>
					<div class="col-sm-8">
						<input class="form-control" name="DOCK_FAX" id="DOCK_FAX" type="text"  
						placeholder="Fax" maxlength="20"  readonly>
					</div>
					
					<label for="DOCK_CELL" class="col-sm-4 control-label">Cell</label>
					<div class="col-sm-8">
						<input class="form-control" name="DOCK_CELL" id="DOCK_CELL" type="text"  
						placeholder="Cell" maxlength="20"  readonly>
					</div>
					
					<label for="DOCK_EMAIL" class="col-sm-4 control-label">Email</label>
					<div class="col-sm-8">
						<input class="form-control" name="DOCK_EMAIL" id="DOCK_EMAIL" type="text"  
						placeholder="Email" maxlength="128"  readonly>
					</div>
				
					<label for="DOCK_LAT" class="col-sm-4 control-label">Lat</label>
					<div class="col-sm-8">
						<input class="form-control" name="DOCK_LAT" id="DOCK_LAT" type="text"  
						placeholder="Lat" maxlength="128"  readonly>
					</div>
				
					<label for="DOCK_LON" class="col-sm-4 control-label">Lon</label>
					<div class="col-sm-8">
						<input class="form-control" name="DOCK_LON" id="DOCK_LON" type="text"  
						placeholder="Lon" maxlength="128"  readonly>
					</div>
				
				</div>
			</div>
		</div>
		<div class="form-group">
			<div class="col-sm-6 col-sm-offset-1">
				<button class="btn btn-md btn-success" name="crossdock" type="submit" ><span class="glyphicon glyphicon-ok"></span> Save Changes</button>
				<a class="btn btn-md btn-default" href="exp_dispatch2.php?CODE='.$_GET['LOAD'].'"><span class="glyphicon glyphicon-remove"></span> Cancel</a>
			</div>
					
		</div>

</form>		
		
';
		
	}
	
	
	

}
?>

	<script language="JavaScript" type="text/javascript"><!--
		// Comment out so it is global scope
		$(document).ready( function () {
		
			var DOCK_NAME_clients = new Bloodhound({
			  name: 'DOCK_NAME',
			  remote: {
				  url: 'exp_suggest_client.php?code=Vinegar&type=dock&query=%QUERY',
				  wildcard: '%QUERY'
				  },
			  datumTokenizer: Bloodhound.tokenizers.obj.whitespace('LABEL'),
			  queryTokenizer: Bloodhound.tokenizers.whitespace

			});
						
			DOCK_NAME_clients.initialize();

			$('#DOCK_NAME').typeahead(null, {
			  name: 'DOCK_NAME',
			  minLength: 2,
			  limit: <?php echo $client_matches; ?>,
			  highlight: true,
			  display: 'CLIENT_NAME',
			  source: DOCK_NAME_clients,
			    templates: {
			    suggestion: Handlebars.compile(
			      '<p><strong>{{CLIENT_NAME}}</strong> – {{CONTACT_TYPE}}, {{CONTACT_NAME}}, {{PHONE_OFFICE}}</p>'
			    )
			  }
			});

			$('#DOCK_NAME').on('typeahead:selected', function(obj, datum, name) {
				$('input#DOCK_ADDR1').val(datum.ADDRESS);
				$('input#DOCK_ADDR2').val(datum.ADDRESS2);
				$('input#DOCK_CITY').val(datum.CITY);
				$('input#DOCK_STATE').val(datum.STATE);
				$('input#DOCK_ZIP').val(datum.ZIP_CODE);
				$('input#DOCK_COUNTRY').val(datum.COUNTRY);
				$('input#DOCK_PHONE').val(datum.PHONE_OFFICE);
				$('input#DOCK_EXT').val(datum.PHONE_EXT);
				$('input#DOCK_FAX').val(datum.PHONE_FAX);
				$('input#DOCK_CELL').val(datum.PHONE_CELL);
				$('input#DOCK_CONTACT').val(datum.CONTACT_NAME);
				$('input#DOCK_EMAIL').val(datum.EMAIL);
				$('input#DOCK_LAT').val(datum.LAT);
				$('input#DOCK_LON').val(datum.LON);
				if( datum.ADDR_VALID == 'valid' )
					$('#VALID').html('<span class="lead text-success"><span class="glyphicon glyphicon-ok"></span></span>&nbsp;Addr1');
				else if( datum.ADDR_VALID == 'warning' )
					$('#VALID').html('<span class="lead text-muted"><span class="glyphicon glyphicon-warning-sign"></span>&nbsp;Addr1');
				else
					$('#VALID').html('<span class="lead text-danger"><span class="glyphicon glyphicon-remove"></span>&nbsp;Addr1');
				$('input#DOCK_NAME').val(datum.CLIENT_NAME).change();
				$('input#YARD_CODE').val(datum.CLIENT_CODE).change();
			});
			
		});
	//--></script>

<?php

require_once( "include/footer_inc.php" );
?>

