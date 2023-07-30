<?php 

// $Id: exp_editfleet.php 2634 2017-10-13 16:05:14Z duncan $
// Edit Tractor Fleet

// Set flag that this is a parent file
define( '_STS_INCLUDE', 1 );

// Setup Session
require_once( "include/sts_session_setup.php" );

require_once( "include/sts_config.php" );
$sts_debug = (isset($_GET['debug'])  || isset($_POST['debug'])) && in_group( EXT_GROUP_DEBUG );
require_once( "include/sts_session_class.php" );

$my_session = sts_session::getInstance( $exspeedite_db, $sts_debug );
$my_session->access_check( EXT_GROUP_ADMIN );	// Make sure we should be here

$sts_subtitle = "Edit Tractor Fleet";
require_once( "include/header_inc.php" );
require_once( "include/navbar_inc.php" );

require_once( "include/sts_form_class.php" );
require_once( "include/sts_fleet_class.php" );
require_once( "include/sts_setting_class.php" );

$setting_table = sts_setting::getInstance($exspeedite_db, $sts_debug);
$multi_company = $setting_table->get("option", "MULTI_COMPANY") == 'true';

if( ! $multi_company ) {
	unset($sts_form_edit_fleet_fields['COMPANY_CODE']);
	$match = preg_quote('<!-- CC01 -->').'(.*)'.preg_quote('<!-- CC02 -->');
	$sts_form_edit_fleet_form['layout'] = preg_replace('/'.$match.'/s', '',
		$sts_form_edit_fleet_form['layout'], 1);	
}

$fleet_table = sts_fleet::getInstance($exspeedite_db, $sts_debug);
$fleet_form = new sts_form($sts_form_edit_fleet_form, $sts_form_edit_fleet_fields, $fleet_table, $sts_debug);

if( isset($_POST) && count($_POST) > 0 ) {		// Process completed form
	$result = $fleet_form->process_edit_form();

	if( $result ) {
		if( $sts_debug ) die; // So we can see the results
		reload_page ( "exp_listfleet.php" );
	}
}

?>
<div class="container-full theme-showcase" role="main">

<div class="well  well-lg">
<?php

if( isset($value) && is_array($value) && $result == false ) {	// If error occured
	echo "<p><strong>Error:</strong> ".$fleet_table->error()."</p>";
	echo $fleet_form->render( $value );
} else if( isset($_GET['CODE']) ) {
	$result = $fleet_table->fetch_rows($fleet_table->primary_key." = ".$_GET['CODE']);
	echo $fleet_form->render( $result[0] );
}

?>
</div>
</div>
	<script language="JavaScript" type="text/javascript"><!--
		
		// Sets the HOME_CITY and HOME_STATE when you set the ZIP_CODE zip
		$(document).ready( function () {
			$('#ZIP_CODE').on('typeahead:selected', function(obj, datum, name) {
				$('input#CITY').val(datum.CityMixedCase);
				$('select#STATE').val(datum.State);
				$('select#COUNTRY').val(datum.Country);
			});
		});
	//--></script>

<?php

require_once( "include/footer_inc.php" );
?>

