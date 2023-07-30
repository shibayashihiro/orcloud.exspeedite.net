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

$sts_subtitle = "Edit Item";
require_once( "include/header_inc.php" );
require_once( "include/navbar_inc.php" );

require_once( "include/sts_form_class.php" );
require_once( "include/sts_email_attach_class.php" );
require_once( "include/sts_user_log_class.php" );

$email_attach_table = sts_email_attach::getInstance($exspeedite_db, $sts_debug);
$email_attach_form = new sts_form($sts_form_edit_email_attach_form, $sts_form_edit_email_attach_fields, $email_attach_table, $sts_debug);

if( isset($_POST) && count($_POST) > 0 ) {		// Process completed form
	$result = $email_attach_form->process_edit_form();

	if( $result ) {
		$user_log_table = sts_user_log::getInstance($exspeedite_db, $sts_debug);
		$user_log_table->log_event('admin', 'Edit email_attach '.$_POST['SOURCE_TYPE'].'/'.$email_attach_table->attachment_type( $_POST['ATTACHMENT_CODE'] ).' -> '.htmlspecialchars($_POST['ATTACH_FROM']) );

		if( $sts_debug ) die; // So we can see the results
		reload_page ( "exp_listemail_attach.php" );
	}
}

?>
<div class="container-full theme-showcase" role="main">

<div class="well  well-lg">
<?php

if( isset($value) && is_array($value) && $result == false ) {	// If error occured
	echo "<p><strong>Error:</strong> ".$email_attach_table->error()."</p>";
	echo $email_attach_form->render( $value );
} else if( isset($_GET['CODE']) ) {
	$result = $email_attach_table->fetch_rows($email_attach_table->primary_key." = ".$_GET['CODE']);
	echo $email_attach_form->render( $result[0] );
}

?>
</div>
</div>
<?php

require_once( "include/footer_inc.php" );
?>

