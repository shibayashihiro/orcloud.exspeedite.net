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
$my_session->access_check( $sts_table_access[TRAILER_TABLE] );	// Make sure we should be here

$sts_subtitle = "Add Trailer";
require_once( "include/header_inc.php" );
require_once( "include/navbar_inc.php" );

require_once( "include/sts_form_class.php" );
require_once( "include/sts_trailer_class.php" );
require_once( "include/sts_setting_class.php" );

$trailer_table = sts_trailer::getInstance($exspeedite_db, $sts_debug);

//! Check setting option/TRAILER_TANKER_FIELDS, and if false remove tanker fields
$setting_table = sts_setting::getInstance($exspeedite_db, $sts_debug);
$strip_tanker_fields = ($setting_table->get( 'option', 'TRAILER_TANKER_FIELDS' ) == 'false');
$strip_im_fields = ($setting_table->get( 'option', 'TRAILER_IM_FIELDS' ) == 'false');
if( $strip_tanker_fields ) {
	$match = preg_quote('<div id="TANKER_FIELDS"').'(.*)'.preg_quote('<!-- TANKER_FIELDS -->');
	$sts_form_addtrailer_form['layout'] = preg_replace('/'.$match.'/s', '',
		$sts_form_addtrailer_form['layout'], 1);
}

if( $strip_im_fields ) {
	$match = preg_quote('<div id="IM_FIELDS"').'(.*)'.preg_quote('<!-- IM_FIELDS -->');
	$sts_form_addtrailer_form['layout'] = preg_replace('/'.$match.'/s', '',
		$sts_form_addtrailer_form['layout'], 1);
}

//! SCR# 280 - Restrict certain fields
$edit_driver_restrictions = ($setting_table->get("option", "EDIT_DRIVER_RESTRICTIONS_ENABLED") == 'true');
if( $edit_driver_restrictions && ! in_group( EXT_GROUP_HR ) ) {
	$sts_form_add_trailer_fields['INSPECTION_EXPIRY']['extras'] = 'readonly';
	$sts_form_add_trailer_fields['INSURANCE_EXPIRY']['extras'] = 'readonly';
}

$trailer_form = new sts_form( $sts_form_addtrailer_form, $sts_form_add_trailer_fields, $trailer_table, $sts_debug);

if( isset($_POST) && count($_POST) > 0 ) {		// Process completed form
	$result = $trailer_form->process_add_form();

	if( $sts_debug ) die; // So we can see the results
	if( $result ) 
		reload_page ( "exp_listtrailer.php" );
		
}

?>
<div class="container-full theme-showcase" role="main">

<div class="well  well-md">
<?php

if( isset($value) && is_array($value) && $result == false ) {	// If error occured
	echo "<p><strong>Error:</strong> ".$trailer_table->error()."</p>";
	echo $trailer_form->render( $value );
} else {
	echo $trailer_form->render();
}

?>
</div>
</div>

<?php

require_once( "include/footer_inc.php" );
?>
		

