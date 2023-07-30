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
$my_session->access_check( EXT_GROUP_ADMIN );	// Make sure we should be here

$sts_subtitle = "Add Company";
require_once( "include/header_inc.php" );
require_once( "include/navbar_inc.php" );

require_once( "include/sts_form_class.php" );
require_once( "include/sts_company_class.php" );
require_once( "include/sts_office_class.php" );
require_once( "include/sts_setting_class.php" );

$setting_table = sts_setting::getInstance($exspeedite_db, $sts_debug);
$sts_export_sage50 = $setting_table->get( 'api', 'EXPORT_SAGE50_CSV' ) == 'true';

if( ! $sts_export_sage50 ) {
	$match = preg_quote('<!-- SAGE50_1 -->').'(.*)'.preg_quote('<!-- SAGE50_2 -->');
	$sts_form_addcompany_form['layout'] = preg_replace('/'.$match.'/s', '',
		$sts_form_addcompany_form['layout'], 1);	
}

$company_table = sts_company::getInstance($exspeedite_db, $sts_debug);
$office_table = sts_office::getInstance($exspeedite_db, $sts_debug);
$company_form = new sts_form( $sts_form_addcompany_form, $sts_form_add_company_fields, $company_table, $sts_debug);

if( isset($_POST) && count($_POST) > 0 ) {		// Process completed form
	$result = $company_form->process_add_form();

	if( $sts_debug ) die; // So we can see the results
	if( $result ) {
		$office_table->user_offices( true );
		if( isset($_POST["saveadd"]) )
			reload_page ( "exp_addcompany.php" );
		else
			reload_page ( "exp_listcompany.php" );
	}
}

?>
<div class="container-full theme-showcase" role="main">

<div class="well  well-md">
<?php

if( isset($value) && is_array($value) && $result == false ) {	// If error occured
	echo "<p><strong>Error:</strong> ".$bc_table->error()."</p>";
	echo $company_form->render( $value );
} else {
	echo $company_form->render();
}

?>
</div>
</div>

	<script language="JavaScript" type="text/javascript"><!--
		
		// Sets the CITY and STATE when you set the ZIP zip
		$(document).ready( function () {
			$('#ZIP').on('typeahead:selected', function(obj, datum, name) {
				$('input#CITY').val(datum.CityMixedCase);
				$('select#STATE').val(datum.State);
				$('select#COUNTRY').val(datum.Country);
			});

			$('select#STATE').on('change', function() {
				if( $('select#IFTA_BASE_JURISDICTION').val() == '' ) {
					$('select#IFTA_BASE_JURISDICTION').val($('select#STATE').val());
				}
			});

			$('select#COUNTRY').on('change', function() {
				if( $('select#COUNTRY').val() == 'USA' ) {
					$('select#HOME_CURRENCY').val('USD');
				} else
				if( $('select#COUNTRY').val() == 'Canada' ) {
					$('select#HOME_CURRENCY').val('CAD');
				}
			});

		});
	//--></script>

<?php

require_once( "include/footer_inc.php" );
?>
		

