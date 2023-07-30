<?php 
// Set flag that this is a parent file
define( '_STS_INCLUDE', 1 );

// Setup Session
require_once( "include/sts_session_setup.php" );

require_once( "include/sts_config.php" );
$sts_debug = (isset($_GET['debug'])  || isset($_POST['debug'])) && in_group( EXT_GROUP_DEBUG );
require_once( "include/sts_session_class.php" );

$my_session = sts_session::getInstance( $exspeedite_db, $sts_debug );
$my_session->access_check( $sts_table_access[STATUS_CODES_TABLE] );	// Make sure we should be here

$sts_subtitle = "Edit Status Code";
require_once( "include/header_inc.php" );
require_once( "include/navbar_inc.php" );

require_once( "include/sts_result_class.php" );
require_once( "include/sts_form_class.php" );
require_once( "include/sts_status_codes_class.php" );
require_once( "include/sts_user_log_class.php" );

$status_codes_table = sts_status_codes::getInstance($exspeedite_db, $sts_debug);
$status_codes_form = new sts_form($sts_form_editstatus_codes_form, $sts_form_edit_status_codes_fields, $status_codes_table, $sts_debug);

if( isset($_POST) && count($_POST) > 0 ) {		// Process completed form
	$result = $status_codes_form->process_edit_form();

	if( $result ) {
		$status_codes_table->write_cache();
		//! SCR# 185 - log when we change a status code
		$user_log_table = sts_user_log::getInstance($exspeedite_db, $sts_debug);
		$user_log_table->log_event('admin', 'Edit status code '.$_POST['SOURCE_TYPE'].' '.$_POST['STATUS_STATE']);

		if( $sts_debug ) die; // So we can see the results
		reload_page ( "exp_liststatus_codes.php" );
	}
}

?>
<div class="container-full theme-showcase" role="main">

<div class="well  well-lg">
<?php

if( isset($value) && is_array($value) && $result == false ) {	// If error occured
	echo "<p><strong>Error:</strong> ".$status_codes_table->error()."</p>";
	echo $status_codes_form->render( $value );
} else if( isset($_GET['CODE']) ) {
	$result = $status_codes_table->fetch_rows($status_codes_table->primary_key." = ".$_GET['CODE']);
	echo $status_codes_form->render( $result[0] );
}

?>
</div>
</div>
<?php

require_once( "include/footer_inc.php" );
?>

