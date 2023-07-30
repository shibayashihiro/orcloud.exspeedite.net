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
$my_session->access_check( EXT_GROUP_ADMIN );	// Make sure we should be here

$sts_subtitle = "Add Software Change Request";
require_once( "include/header_inc.php" );
require_once( "include/navbar_inc.php" );

require_once( "include/sts_form_class.php" );
require_once( "include/sts_scr_class.php" );
require_once( "include/sts_attachment_class.php" );
require_once( "include/sts_item_list_class.php" );

$scr_table = sts_scr::getInstance($exspeedite_db, $sts_debug);
$attachment_table = sts_attachment::getInstance($exspeedite_db, $sts_debug);
$item_list_table = sts_item_list::getInstance($exspeedite_db, $sts_debug);

$scr_form = new sts_form( $sts_form_addscr_form, $sts_form_add_scr_fields, $scr_table, $sts_debug);

if( isset($_POST) && count($_POST) > 0 ) {		// Process completed form
	$result = $scr_form->process_add_form();
	if( $result ) {
		$file_type = $item_list_table->get_item_code( 'Attachment type', 'Document' );
		if( ! $file_type ) $file_type = 0;
		$attachment_table->add_attachment( $result, 'scr', 'ATTACHMENT', $file_type );

		require_once( "include/sts_user_log_class.php" );
		$user_log_table = sts_user_log::getInstance($exspeedite_db, $sts_debug);
		$user_log_table->log_event('scr', 'Add SCR# '.$result."<br>".$_POST["TITLE"]);

		$email_type = 'scr';
		$email_code = $result;
		require_once( "exp_spawn_send_email.php" ); // Announce SCR
	}

	if( $sts_debug ) die; // So we can see the results
	if( $result ) 
		if( isset($_POST["saveadd"]) )
			reload_page ( "exp_addscr.php" );
		else
			reload_page ( "exp_listscr.php" );
		
}

?>
<div class="container-full theme-showcase" role="main">

<div class="well  well-md">
<?php

if( isset($value) && is_array($value) && $result == false ) {	// If error occured
	echo "<p><strong>Error:</strong> ".$scr_table->error()."</p>";
	echo $scr_form->render( $value );
} else {
	$defaults = array( 'CURRENT_STATUS' => $scr_table->behavior_state['entry'],
		'ORIGINATOR' => $_SESSION['EXT_USER_CODE'] );
	echo $scr_form->render( $defaults );
}

?>
</div>
</div>

<?php

require_once( "include/footer_inc.php" );
?>
		

