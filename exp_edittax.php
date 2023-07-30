<?php 
// Set flag that this is a parent file
define( '_STS_INCLUDE', 1 );

// Setup Session
require_once( "include/sts_session_setup.php" );

require_once( "include/sts_config.php" );
$sts_debug = (isset($_GET['debug'])  || isset($_POST['debug'])) && in_group( EXT_GROUP_DEBUG );
require_once( "include/sts_session_class.php" );

$my_session = sts_session::getInstance( $exspeedite_db, $sts_debug );
$my_session->access_check( EXT_GROUP_ADMIN );	// Make sure we should be here

$sts_subtitle = "Edit Canadian Tax Rate";
require_once( "include/header_inc.php" );
require_once( "include/navbar_inc.php" );

require_once( "include/sts_form_class.php" );
require_once( "include/sts_canada_tax_class.php" );
require_once( "include/sts_setting_class.php" );

$tax_table = sts_canada_tax::getInstance($exspeedite_db, $sts_debug);
$setting_table = sts_setting::getInstance($exspeedite_db, $sts_debug);
$sts_qb_online = $setting_table->get( 'api', 'QUICKBOOKS_ONLINE' ) == 'true';

//! SCR# 257 - extra column for QBOE
if( ! $sts_qb_online ) {
	$match = preg_quote('<!-- QB01 -->').'(.*)'.preg_quote('<!-- QB02 -->');
	$sts_form_editcanada_tax_form['layout'] = preg_replace('/'.$match.'/s', '',
		$sts_form_editcanada_tax_form['layout'], 1);	
}

$tax_form = new sts_form($sts_form_editcanada_tax_form, $sts_form_edit_canada_tax_fields, $tax_table, $sts_debug);

if( isset($_POST) && count($_POST) > 0 ) {		// Process completed form
	$result = $tax_form->process_edit_form();

	if( $result ) {
		if( $sts_debug ) die; // So we can see the results
		reload_page ( "exp_listtax.php" );
	}
}

?>
<div class="container-full theme-showcase" role="main">

<div class="well  well-lg">
<?php

if( isset($value) && is_array($value) && $result == false ) {	// If error occured
	echo "<p><strong>Error:</strong> ".$tax_table->error()."</p>";
	echo $tax_form->render( $value );
} else if( isset($_GET['CODE']) ) {
	$result = $tax_table->fetch_rows($tax_table->primary_key." = ".$_GET['CODE']);
	echo $tax_form->render( $result[0] );
}

?>
</div>
</div>

	<script language="JavaScript" type="text/javascript"><!--
		$(document).ready( function () {
		
			function hide_columns() {
				var applic = $('#APPLICABLE_TAX').val().split('+');
				var taxes = new Array('GST','HST','QST');
				
				taxes = taxes.filter( function( el ) {
				  return !applic.includes( el );
				} );
				console.log(applic, taxes);
				$.each(taxes, function( index, value ) {
					$('#'+value+'_RATE').attr("disabled", "disabled");
				});
				
			}
			
			hide_columns();
		});
	//--></script>

<?php

require_once( "include/footer_inc.php" );
?>

