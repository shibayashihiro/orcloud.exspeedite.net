<?php 
// Set flag that this is a parent file
define( '_STS_INCLUDE', 1 );

// Setup Session
require_once( "include/sts_session_setup.php" );

require_once( "include/sts_config.php" );
$sts_debug = (isset($_GET['debug'])  || isset($_POST['debug'])) && in_group( EXT_GROUP_DEBUG );
//$sts_debug = isset($_POST) && count($_POST) > 0 ;
require_once( "include/sts_session_class.php" );

$my_session = sts_session::getInstance( $exspeedite_db, $sts_debug );
$my_session->access_check( $sts_table_access[TRACTOR_TABLE] );	// Make sure we should be here

$sts_subtitle = "Add Tractor";
require_once( "include/header_inc.php" );
require_once( "include/navbar_inc.php" );

require_once( "include/sts_form_class.php" );
require_once( "include/sts_tractor_class.php" );
require_once( "include/sts_setting_class.php" );

$setting_table = sts_setting::getInstance($exspeedite_db, $sts_debug);
$multi_company = $setting_table->get("option", "MULTI_COMPANY") == 'true';
$ifta_enabled = $setting_table->get("api", "IFTA_ENABLED") == 'true';
$fleet_enabled = $setting_table->get( 'option', 'FLEET_ENABLED' ) == 'true';
$max_tractors = intval($setting_table->get( 'option', 'MAX_TRACTORS' ));
$client = $setting_table->get( 'company', 'NAME' );

if( ! $fleet_enabled ) {
	unset($sts_form_add_tractor_fields['FLEET_CODE']);
	$match = preg_quote('<!-- FL01 -->').'(.*)'.preg_quote('<!-- FL02 -->');
	$sts_form_addtractor_form['layout'] = preg_replace('/'.$match.'/s', '',
		$sts_form_addtractor_form['layout'], 1);	
}

$tractor_table = sts_tractor::getInstance($exspeedite_db, $sts_debug);

//! SCR# 333 - restrict max number of tractors
$abort = false;
if( $max_tractors > 0 ) {
	$check = $tractor_table->fetch_rows("", "COUNT(*) AS NUM_TRACTORS");
	
	if( is_array($check) && count($check) == 1 &&
		isset($check[0]["NUM_TRACTORS"]) && $check[0]["NUM_TRACTORS"] > $max_tractors ) {
		$abort = true;
		echo '<div class="container-full theme-showcase" role="main">

		<div class="well  well-md">
		<h1><span class="text-danger glyphicon glyphicon-warning-sign"></span> Unable To Add More Tractors</h1>
		<h3>You have exceeded the limit of <strong>'.$max_tractors.'</strong> tractors (you have '.$check[0]["NUM_TRACTORS"].') on this version of Exspeedite.</h3> 
		<h3>Please contact Exspeedite support for an upgrade.</h3>
		<p><a class="btn btn-md btn-default" href="exp_listtractor.php"><span class="glyphicon glyphicon-remove"></span> Back To Tractors</a> <a class="btn btn-md btn-success" href="mailto:support@exspeedite.net?subject=I need more tractors for '.$client.'"><span class="glyphicon glyphicon-envelope"></span> Contact Support</a></p>
		';
	}
}

if( ! $abort ) {
	if( ! $multi_company ) {
		$match = preg_quote('<!-- CC01 -->').'(.*)'.preg_quote('<!-- CC02 -->');
		$sts_form_addtractor_form['layout'] = preg_replace('/'.$match.'/s', '',
			$sts_form_addtractor_form['layout'], 1);	
	}
	
	if( ! $ifta_enabled ) {
		$match = preg_quote('<!-- IFTA01 -->').'(.*)'.preg_quote('<!-- IFTA02 -->');
		$sts_form_addtractor_form['layout'] = preg_replace('/'.$match.'/s', '',
			$sts_form_addtractor_form['layout'], 1);	
	}
	
	//! SCR# 280 - Restrict certain fields
	$edit_driver_restrictions = ($setting_table->get("option", "EDIT_DRIVER_RESTRICTIONS_ENABLED") == 'true');
	if( $edit_driver_restrictions && ! in_group( EXT_GROUP_HR ) ) {
		$sts_form_add_tractor_fields['INSPECTION_EXPIRY']['extras'] = 'readonly';
		$sts_form_add_tractor_fields['INSURANCE_EXPIRY']['extras'] = 'readonly';
	}
	
	$tractor_form = new sts_form( $sts_form_addtractor_form, $sts_form_add_tractor_fields, $tractor_table, $sts_debug);
	
	if( isset($_POST) && count($_POST) > 0 ) {		// Process completed form
		$result = $tractor_form->process_add_form();
	
		if( $sts_debug ) die; // So we can see the results
		if( $result ) 
			if( isset($_POST["saveadd"]) )
				reload_page ( "exp_addtractor.php" );
			else
				reload_page ( "exp_listtractor.php" );
			
	}
	
	?>
	<div class="container-full theme-showcase" role="main">
	
	<div class="well  well-md">
	<?php
	
	if( isset($value) && is_array($value) && $result == false ) {	// If error occured
		echo "<p><strong>Error:</strong> ".$tractor_table->error()."</p>";
		echo $tractor_form->render( $value );
	} else {
		echo $tractor_form->render();
	}
}
?>
</div>
</div>

<?php

require_once( "include/footer_inc.php" );
?>
		

