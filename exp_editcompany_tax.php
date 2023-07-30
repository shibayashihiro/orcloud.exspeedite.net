<?php 
// Set flag that this is a parent file
define( '_STS_INCLUDE', 1 );

// Setup Session
require_once( "include/sts_session_setup.php" );

require_once( "include/sts_config.php" );
$sts_debug = (isset($_GET['debug'])  || isset($_POST['debug'])) && in_group( EXT_GROUP_DEBUG );
require_once( "include/sts_session_class.php" );

$my_session = sts_session::getInstance( $exspeedite_db, $sts_debug );
$my_session->access_check( $sts_table_access[COMPANY_TAX_TABLE] );	// Make sure we should be here

$sts_subtitle = "Edit Canadian Tax Info";
require_once( "include/header_inc.php" );
require_once( "include/navbar_inc.php" );

require_once( "include/sts_form_class.php" );
require_once( "include/sts_company_tax_class.php" );

$company_tax_table = new sts_company_tax($exspeedite_db, $sts_debug);
$company_tax_form = new sts_form( $sts_form_editcompany_tax_form,
	$sts_form_edit_company_tax_fields, $company_tax_table, $sts_debug);

if( isset($_POST) && count($_POST) > 0 ) {		// Process completed form
	$result = $company_tax_form->process_edit_form();

	if( $result ) {
		if( $sts_debug ) die; // So we can see the results
		reload_page ( "exp_editcompany.php?CODE=".$_POST["COMPANY_CODE"] );
	}
}

?>
<div class="container-full theme-showcase" role="main">

<div class="well  well-lg">
<?php

if( isset($value) && is_array($value) && $result == false ) {	// If error occured
	echo "<p><strong>Error:</strong> ".$company_tax_table->error()."</p>";
	echo $company_tax_form->render( $value );
} else if( isset($_GET['CODE']) ) {
	$result = $company_tax_table->fetch_rows($company_tax_table->primary_key." = ".$_GET['CODE']);
	echo $company_tax_form->render( $result[0] );
}

?>
</div>
</div>
<?php

require_once( "include/footer_inc.php" );
?>

