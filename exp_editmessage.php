<?php 

// $Id: exp_editmessage.php 3884 2020-01-30 00:21:42Z duncan $
// Edit message

// Set flag that this is a parent file
define( '_STS_INCLUDE', 1 );

// Setup Session
require_once( "include/sts_session_setup.php" );

require_once( "include/sts_config.php" );
$sts_debug = (isset($_GET['debug'])  || isset($_POST['debug'])) && in_group( EXT_GROUP_DEBUG );
require_once( "include/sts_session_class.php" );

$my_session = sts_session::getInstance( $exspeedite_db, $sts_debug );
$my_session->access_check( EXT_GROUP_ADMIN, EXT_GROUP_MANAGER );	// Make sure we should be here

$sts_subtitle = "Edit Message";
require_once( "include/header_inc.php" );
require_once( "include/navbar_inc.php" );

require_once( "include/sts_form_class.php" );
require_once( "include/sts_message_class.php" );

$message_table = new sts_message($exspeedite_db, $sts_debug);
if( ! $message_table->multi_company() ) {
	$match = preg_quote('<!-- OFFICE1 -->').'(.*)'.preg_quote('<!-- OFFICE2 -->');
	$sts_form_editmessage_form['layout'] = preg_replace('/'.$match.'/s', '',
		$sts_form_editmessage_form['layout'], 1);	
}

$message_form = new sts_form($sts_form_editmessage_form, $sts_form_edit_message_fields, $message_table, $sts_debug);

if( isset($_POST) && count($_POST) > 0 ) {		// Process completed form
	$result = $message_form->process_edit_form();

	if( $result ) {
		unset($_SESSION['EXT_USER_messageS']);
		if( $sts_debug ) die; // So we can see the results
		reload_page ( "exp_listmessage.php" );
	}
}

?>
<div class="container-full theme-showcase" role="main">

<div class="well  well-lg">
<?php

if( isset($value) && is_array($value) && $result == false ) {	// If error occured
	echo "<p><strong>Error:</strong> ".$message_table->error()."</p>";
	echo $message_form->render( $value );
} else if( isset($_GET['CODE']) ) {
	$result = $message_table->fetch_rows($message_table->primary_key." = ".$_GET['CODE']);
	echo $message_form->render( $result[0] );
}

?>
</div>
</div>

<?php

require_once( "include/footer_inc.php" );
?>

