<?php 
// Set flag that this is a parent file
define( '_STS_INCLUDE', 1 );

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
require_once( "include/sts_form_class.php" );
require_once( "include/sts_shipment_class.php" );
require_once( "include/sts_zip_class.php" );
require_once( $sts_crm_dir.DIRECTORY_SEPARATOR."PCMILER/exp_get_miles.php" );

echo '<div class="container theme-showcase" role="main">';

$shipment_table = sts_shipment::getInstance($exspeedite_db, $sts_debug);

$shipment_form = new sts_form( $sts_form_distance_form, $sts_form_distance_fields, $shipment_table, $sts_debug);

if( isset($_POST) && count($_POST) > 0 )
	echo $shipment_form->render($_POST);
else
	echo $shipment_form->render();

if( isset($_POST) && count($_POST) > 0 ) {		// Process completed form
	$zip_table = sts_zip::getInstance($exspeedite_db, $sts_debug);
	$pcm = sts_pcmiler_api::getInstance( $exspeedite_db, $sts_debug );
	
	$address1 = array();
	$address2 = array();
	
	if( isset($_POST['FROM_ADDR1']) && $_POST['FROM_ADDR1'] <> '' )
		$address1['ADDRESS'] = $_POST['FROM_ADDR1'];
	if( isset($_POST['FROM_ADDR2']) && $_POST['FROM_ADDR2'] <> '' )
		$address1['ADDRESS2'] = $_POST['FROM_ADDR2'];
	if( isset($_POST['FROM_CITY']) && $_POST['FROM_CITY'] <> '' )
		$address1['CITY'] = $_POST['FROM_CITY'];
	if( isset($_POST['FROM_STATE']) && $_POST['FROM_STATE'] <> '' )
		$address1['STATE'] = $_POST['FROM_STATE'];
	if( isset($_POST['FROM_ZIP']) && $_POST['FROM_ZIP'] <> '' )
		$address1['ZIP_CODE'] = $_POST['FROM_ZIP'];
	if( isset($_POST['FROM_COUNTRY']) && $_POST['FROM_COUNTRY'] <> '' )
		$address1['COUNTRY'] = $_POST['FROM_COUNTRY'];

	if( isset($_POST['TO_ADDR1']) && $_POST['TO_ADDR1'] <> '' )
		$address2['ADDRESS'] = $_POST['TO_ADDR1'];
	if( isset($_POST['TO_ADDR2']) && $_POST['TO_ADDR2'] <> '' )
		$address2['ADDRESS2'] = $_POST['TO_ADDR2'];
	if( isset($_POST['TO_CITY']) && $_POST['TO_CITY'] <> '' )
		$address2['CITY'] = $_POST['TO_CITY'];
	if( isset($_POST['TO_STATE']) && $_POST['TO_STATE'] <> '' )
		$address2['STATE'] = $_POST['TO_STATE'];
	if( isset($_POST['TO_ZIP']) && $_POST['TO_ZIP'] <> '' )
		$address2['ZIP_CODE'] = $_POST['TO_ZIP'];
	if( isset($_POST['TO_COUNTRY']) && $_POST['TO_COUNTRY'] <> '' )
		$address2['COUNTRY'] = $_POST['TO_COUNTRY'];
	
	$valid1 = $zip_table->validate_various($address1);
	if( $valid1 == 'valid' ) {
		$check1 = 'From is '.$valid1.': lat='.$zip_table->get_lat().' lon='.$zip_table->get_lon().'<br>by '.$zip_table->get_source().' @ '.$zip_table->get_last_at();
	} else {
		$check1 = 'From is '.$valid1.': code='.$zip_table->get_code().' desc='.$zip_table->get_description().'<br>by '.$zip_table->get_source().' @ '.$zip_table->get_last_at();
	}

	$valid2 = $zip_table->validate_various($address2);
	if( $valid1 == 'valid' ) {
		$check2 = 'To is '.$valid2.': lat='.$zip_table->get_lat().' lon='.$zip_table->get_lon().'<br>by '.$zip_table->get_source().' @ '.$zip_table->get_last_at();
	} else {
		$check2 = 'To is '.$valid2.': code='.$zip_table->get_code().' desc='.$zip_table->get_description().'<br>by '.$zip_table->get_source().' @ '.$zip_table->get_last_at();
	}

	
	$distance = $zip_table->get_distance_various( $address1, $address2 );
	
	echo '<div class="row">
		<div class="col-sm-10 well well-lg">
			<h2>'.$check1.'</h2>
			<h2>'.$check2.'</h2>
			<h2>Distance = '.$distance.' miles</h2>
			<h3>Source: '.$zip_table->get_source().' @ '.$zip_table->get_last_at().'</h3>
		</div>
		</div>
		';
}

echo '<br><br><br><br><h3><a class="btn btn-default" target="_blank" href="https://pcmilerweb.com/"><img src="images/pcmiler-web-logo.png" alt="pcmiler-web-logo" height="40" /></a> Visit PC*MILER Web</h3>';


echo '</div>';
?>
	<script language="JavaScript" type="text/javascript"><!--
		
		// Sets the client info
		$(document).ready( function () {
			$('#FROM_ZIP').bind('typeahead:selected', function(obj, datum, name) {
				$('input#FROM_CITY').val(datum.CityMixedCase);
				$('select#FROM_STATE').val(datum.State);
				$('select#FROM_COUNTRY').val(datum.Country);
			});
			$('#TO_ZIP').bind('typeahead:selected', function(obj, datum, name) {
				$('input#TO_CITY').val(datum.CityMixedCase);
				$('select#TO_STATE').val(datum.State);
				$('select#TO_COUNTRY').val(datum.Country);
			});

		});
	//--></script>


<?php

require_once( "include/footer_inc.php" );
?>
