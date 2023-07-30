<?php 

// $Id: exp_editrm_class.php 3435 2019-03-25 18:53:25Z duncan $
// Edit RM class - Edit class for tractors/trailers

// Set flag that this is a parent file
define( '_STS_INCLUDE', 1 );

// Setup Session
require_once( "include/sts_session_setup.php" );

require_once( "include/sts_config.php" );
$sts_debug = (isset($_GET['debug'])  || isset($_POST['debug'])) && in_group( EXT_GROUP_DEBUG );
require_once( "include/sts_session_class.php" );

$my_session = sts_session::getInstance( $exspeedite_db, $sts_debug );
$my_session->access_check( EXT_GROUP_ADMIN );	// Make sure we should be here

$sts_subtitle = "Edit R&M Class";
require_once( "include/header_inc.php" );
require_once( "include/navbar_inc.php" );

require_once( "include/sts_form_class.php" );
require_once( "include/sts_rm_class_class.php" );

$rm_class_table = sts_rm_class::getInstance($exspeedite_db, $sts_debug);
$rm_class_form = new sts_form($sts_form_edit_rm_class_form, $sts_form_edit_rm_class_fields, $rm_class_table, $sts_debug);

if( isset($_POST) && count($_POST) > 0 ) {		// Process completed form
	$result = $rm_class_form->process_edit_form();

	if( $result ) {
		if( $sts_debug ) die; // So we can see the results
		reload_page ( "exp_listrm_class.php" );
	}
}

?>
<div class="container-full theme-showcase" role="main">

<div class="well  well-lg">
<?php

if( isset($value) && is_array($value) && $result == false ) {	// If error occured
	echo "<p><strong>Error:</strong> ".$rm_class_table->error()."</p>";
	echo $rm_class_form->render( $value );
} else if( isset($_GET['CODE']) ) {
	$result = $rm_class_table->fetch_rows($rm_class_table->primary_key." = ".$_GET['CODE']);
	echo $rm_class_form->render( $result[0] );
}

?>
</div>
</div>
<?php

require_once( "include/footer_inc.php" );
?>

