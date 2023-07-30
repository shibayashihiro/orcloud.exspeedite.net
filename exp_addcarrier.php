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
$my_session->access_check( $sts_table_access[CARRIER_TABLE] );	// Make sure we should be here

$sts_subtitle = "Add Carrier";
require_once( "include/header_inc.php" );
require_once( "include/navbar_inc.php" );

require_once( "include/sts_form_class.php" );
require_once( "include/sts_carrier_class.php" );
require_once( "include/sts_setting_class.php" );
require_once( "include/sts_email_class.php" );

$setting_table = sts_setting::getInstance($exspeedite_db, $sts_debug);
$sts_export_sage50 = $setting_table->get( 'api', 'EXPORT_SAGE50_CSV' ) == 'true';
$sts_edi_enabled = $setting_table->get( 'api', 'EDI_ENABLED' ) == 'true';

if( ! $sts_export_sage50 ) {
	$match = preg_quote('<!-- SAGE50_1 -->').'(.*)'.preg_quote('<!-- SAGE50_2 -->');
	$sts_form_addcarrier_form['layout'] = preg_replace('/'.$match.'/s', '',
		$sts_form_addcarrier_form['layout'], 1);	
}

if( ! $sts_edi_enabled ) {
	$match = preg_quote('<!-- EDI_1 -->').'(.*)'.preg_quote('<!-- EDI_2 -->');
	$sts_form_addcarrier_form['layout'] = preg_replace('/'.$match.'/s', '',
		$sts_form_addcarrier_form['layout'], 1);	
}

$carrier_table = sts_carrier::getInstance($exspeedite_db, $sts_debug);
$carrier_form = new sts_form( $sts_form_addcarrier_form, $sts_form_add_carrier_fields, $carrier_table, $sts_debug);

if( isset($_POST) && count($_POST) > 0 ) {		// Process completed form
	$result = $carrier_form->process_add_form();

	//! SCR# 197 - notify accounting
	if( $result ) {
		$email_type = 'newcarrier';
		$email_code = $result;
		require_once( "exp_spawn_send_email.php" );		// Background send
	}
	
	if( $sts_debug ) die; // So we can see the results
	if( $result ) 
		reload_page ( "exp_editcarrier.php?CODE=".$result );
		
}

?>
<div class="container-full theme-showcase" role="main">

<div class="well  well-md">
<?php

if( isset($value) && is_array($value) && $result == false ) {	// If error occured
	echo "<p><strong>Error:</strong> ".$carrier_table->error()."</p>";
	echo $carrier_form->render( $value );
} else {
	echo $carrier_form->render();
}

?>
</div>
</div>

	<script language="JavaScript" type="text/javascript"><!--
		
		$(document).ready( function () {
			function update_ctpat_svi() {
				console.log( $('#CTPAT_CERTIFIED').prop("checked") );
				if( $('#CTPAT_CERTIFIED').prop("checked") ) {
					$('#CTPAT_SVI').prop('hidden', false);
				} else {
					$('#CTPAT_SVI').prop('hidden', 'hidden');
				}
			}

		    $('#CTPAT_CERTIFIED').on('change', function() {
		    	update_ctpat_svi();
		    });
		    
		    update_ctpat_svi();
		});
	//--></script>


<?php

require_once( "include/footer_inc.php" );
?>
		

