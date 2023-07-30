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
$my_session->access_check( $sts_table_access[DRIVER_TABLE] );	// Make sure we should be here

$sts_subtitle = "Add Driver";
require_once( "include/header_inc.php" );
require_once( "include/navbar_inc.php" );

require_once( "include/sts_form_class.php" );
require_once( "include/sts_driver_class.php" );
require_once( "include/sts_setting_class.php" );

$setting_table = sts_setting::getInstance($exspeedite_db, $sts_debug);

//! This removes some fields not used for QuickBooks Online
$sts_qb_online = $setting_table->get( 'api', 'QUICKBOOKS_ONLINE' ) == 'true';
if( ! $sts_qb_online ) {
	$match = preg_quote('<!-- CHECK_NAME1 -->').'(.*)'.preg_quote('<!-- CHECK_NAME2 -->');
	$sts_form_adddriver2_form['layout'] = preg_replace('/'.$match.'/s', '',
		$sts_form_adddriver2_form['layout'], 1);	
}

//! SCR# 427 - hide driver type
$driver_types = $setting_table->get("option", "DRIVER_TYPES") == 'true';
if( ! $driver_types ) {
	$match = preg_quote('<!-- DRIVER_TYPES1 -->').'(.*)'.preg_quote('<!-- DRIVER_TYPES2 -->');
	$sts_form_adddriver2_form['layout'] = preg_replace('/'.$match.'/s', '',
		$sts_form_adddriver2_form['layout'], 1);	
}

//! Check for multi-company
$multi_company = ($setting_table->get("option", "MULTI_COMPANY") == 'true');
if( ! $multi_company ) {
	$match = preg_quote('<!-- CC01 -->').'(.*)'.preg_quote('<!-- CC02 -->');
	$sts_form_adddriver2_form['layout'] = preg_replace('/'.$match.'/s', '',
		$sts_form_adddriver2_form['layout'], 1);	
}

//! Restrict certain fields
$edit_driver_restrictions = ($setting_table->get("option", "EDIT_DRIVER_RESTRICTIONS_ENABLED") == 'true');
if( $edit_driver_restrictions && ! in_group( EXT_GROUP_HR ) ) {
	$sts_form_add_driver_fields['PHYSICAL_DUE']['extras'] = 'readonly';
	//! SCR# 280 - hide SSN
	$match = preg_quote('<!-- SSN1 -->').'(.*)'.preg_quote('<!-- SSN2 -->');
	$sts_form_adddriver2_form['layout'] = preg_replace('/'.$match.'/s', '',
		$sts_form_adddriver2_form['layout'], 1);	
}

$driver_table = sts_driver::getInstance($exspeedite_db, $sts_debug);
$driver_form = new sts_form( $sts_form_adddriver2_form, $sts_form_add_driver_fields, $driver_table, $sts_debug);

if( isset($_POST) && count($_POST) > 0 ) {		// Process completed form
	$result = $driver_form->process_add_form();
	
	if( $sts_debug ) die; // So we can see the results
	if( $result ) 
		reload_page ( "exp_editdriver.php?CODE=".$result );
		
}

?>
<div class="container-full theme-showcase" role="main">

<div class="well  well-md">
<?php

if( isset($value) && is_array($value) && $result == false ) {	// If error occured
	echo "<p><strong>Error:</strong> ".$driver_table->error()."</p>";
	echo $driver_form->render( $value );
} else {
	echo $driver_form->render();
}

if( false ) {
echo '
<div class="well  well-md">';

$result = $contact_info_table->fetch_rows();
$rslt = new sts_result( $result, $contact_info_table, $sts_debug );
echo $rslt->render( $sts_result_contact_info_layout, $sts_result_contact_info_edit );

?>
</div>
<?php
}
?>
</div>
</div>

	<script language="JavaScript" type="text/javascript"><!--
		$(document).ready( function () {
			function update_office( choice ) {
				var url = 'exp_office_menu.php?code=GoldBond&company='+$('select#COMPANY_CODE').val()+'&choice='+choice;
				$.get( url, function( data ) {
					$( "select#OFFICE_CODE" ).html( data );
				});
			}

			$('select#COMPANY_CODE').on('change', function(event) {
				update_office('NULL');
			});
			
			update_office('NULL');
		
			function update_type() {
				//console.log( 'update_type: ', $('select#DRIVER_TYPE').val() );
				if( $('select#DRIVER_TYPE').val() == 'driver' ) {
					$('.driver_only').prop('hidden',false).change();
				} else {
					$('.driver_only').prop('hidden', 'hidden').change();					
				}
			}

			$('select#DRIVER_TYPE').change(function () {
				update_type();
			});
			
			update_type();
		});
	//--></script>

<?php

require_once( "include/footer_inc.php" );
?>
		

