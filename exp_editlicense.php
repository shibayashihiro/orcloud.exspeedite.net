<?php 
// Set flag that this is a parent file
define( '_STS_INCLUDE', 1 );

// Setup Session
require_once( "include/sts_session_setup.php" );

require_once( "include/sts_config.php" );
$sts_debug = (isset($_GET['debug'])  || isset($_POST['debug'])) && in_group( EXT_GROUP_DEBUG );
require_once( "include/sts_session_class.php" );

$my_session = sts_session::getInstance( $exspeedite_db, $sts_debug );
$my_session->access_check( $sts_table_access[LICENSE_TABLE] );	// Make sure we should be here

if( isset($_POST) || isset($_GET['CODE']) ) {
	$sts_subtitle = "Edit License Info";
	require_once( "include/header_inc.php" );
	require_once( "include/navbar_inc.php" );
	
	require_once( "include/sts_form_class.php" );
	require_once( "include/sts_license_class.php" );
	require_once( "include/sts_setting_class.php" );
	
	$setting_table = sts_setting::getInstance($exspeedite_db, $sts_debug);
		
	//! SCR# 280 - Restrict certain fields
	$edit_driver_restrictions = ($setting_table->get("option", "EDIT_DRIVER_RESTRICTIONS_ENABLED") == 'true');
	if( $edit_driver_restrictions && ! in_group( EXT_GROUP_HR ) ) {
		$sts_form_edit_license_fields['LICENSE_EXPIRY_DATE']['extras'] = 'readonly';
	}

	$license_table = new sts_license($exspeedite_db, $sts_debug);
	$license_form = new sts_form($sts_form_edit_license, $sts_form_edit_license_fields,
		$license_table, $sts_debug);
	
	if( isset($_POST) && count($_POST) > 0 ) {		// Process completed form
		$result = $license_form->process_edit_form();

		if( $sts_debug ) die; // So we can see the results
		if( $result ) {
			reload_page ( "exp_editdriver.php?CODE=".$_POST["CONTACT_CODE"] );
		}
	}
	
	?>
	<div class="container theme-showcase" role="main">
	
	<div class="well  well-lg">
	<?php
	
	if( isset($value) && is_array($value) && $result == false ) {	// If error occured
		echo "<p><strong>Error:</strong> ".$license_table->error()."</p>";
		echo $license_form->render( $value );
	} else if( isset($_GET['CODE']) ) {
		$result = $license_table->fetch_rows("LICENSE_CODE = ".$_GET['CODE']);
		echo $license_form->render( $result[0] );
	}
	
}
?>
</div>
</div>
	<script language="JavaScript" type="text/javascript"><!--
		
		$(document).ready( function () {
			//cache the second field to make the event handler perform faster
			var field2 = document.getElementById('LICENSE_ENDORSEMENTS');
		    if ($('#LICENSE_TYPE').val().substring(0,2) == 'CDL') {
		        field2.disabled = false;
		    } else {
		        field2.disabled = true;
		    }
			$('#LICENSE_TYPE').on('change', function () {
			    if (this.value.substring(0,2) == 'CDL') {
			        field2.disabled = false;
			    } else {
			        field2.disabled = true;
			    }
			});
		});
	//--></script>


<?php

require_once( "include/footer_inc.php" );
?>

