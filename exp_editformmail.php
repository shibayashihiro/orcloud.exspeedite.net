<?php 

// $Id:$
// Add Form Mail Template

// Set flag that this is a parent file
define( '_STS_INCLUDE', 1 );

// Setup Session
require_once( "include/sts_session_setup.php" );

require_once( "include/sts_config.php" );
$sts_debug = (isset($_GET['debug'])  || isset($_POST['debug'])) && in_group( EXT_GROUP_DEBUG );
require_once( "include/sts_session_class.php" );

$my_session = sts_session::getInstance( $exspeedite_db, $sts_debug );
$my_session->access_check( $sts_table_access[FORMMAIL_TABLE] );	// Make sure we should be here

$sts_subtitle = "Edit Form Mail Template";
require_once( "include/header_inc.php" );
require_once( "include/navbar_inc.php" );

require_once( "include/sts_formmail_class.php" );
require_once( "include/sts_form_class.php" );

$formmail_table = sts_formmail::getInstance($exspeedite_db, $sts_debug);
$formmail_form = new sts_form( $sts_form_editformmail_form, $sts_form_edit_formmail_fields, $formmail_table, $sts_debug);

if( isset($_POST) && count($_POST) > 0 ) {		// Process completed form
	$result = $formmail_form->process_edit_form();

	if( $result ) {
		if( $sts_debug ) die; // So we can see the results
		reload_page ( "exp_listformmail.php" );
	}
}

?>
<div class="container-full theme-showcase" role="main">

<div class="well  well-lg">
<?php

if( isset($value) && is_array($value) && $result == false ) {	// If error occured
	echo "<p><strong>Error:</strong> ".$formmail_table->error()."</p>";
	echo $formmail_form->render( $value );
} else if( isset($_GET['CODE']) ) {
	$result = $formmail_table->fetch_rows($formmail_table->primary_key." = ".$_GET['CODE']);
	echo $formmail_form->render( $result[0] );
}

?>
</div>
</div>
<?php

require_once( "include/footer_inc.php" );
?>

