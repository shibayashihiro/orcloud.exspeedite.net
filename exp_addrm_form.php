<?php 

// $Id: exp_addrm_form.php 3435 2019-03-25 18:53:25Z duncan $
// Add RM class - Add class for tractors/trailers

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

$sts_subtitle = "Add R&M Form";
require_once( "include/header_inc.php" );
require_once( "include/navbar_inc.php" );

require_once( "include/sts_form_class.php" );
require_once( "include/sts_rm_form_class.php" );

$rm_form_table = sts_rm_form::getInstance($exspeedite_db, $sts_debug);
$rm_form_form = new sts_form( $sts_form_add_rm_form_form, $sts_form_add_rm_form_fields, $rm_form_table, $sts_debug);

if( isset($_POST) && count($_POST) > 0 ) {		// Process completed form
	$result = $rm_form_form->process_add_form();

	if( $sts_debug ) die; // So we can see the results
	
	if( $result ) 
		if( isset($_POST["saveadd"]) )
			reload_page ( "exp_addrm_form.php" );
		else
			reload_page ( "exp_editrm_form.php?CODE=".$result );
		
}

?>
<div class="container-full theme-showcase" role="main">

<div class="well  well-md">
<?php

if( isset($value) && is_array($value) && $result == false ) {	// If error occured
	echo "<p><strong>Error:</strong> ".$rm_form_table->error()."</p>";
	echo $rm_form_form->render( $value );
} else {
	echo $rm_form_form->render();
}

?>
</div>
</div>

<?php

require_once( "include/footer_inc.php" );
?>
		

