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
$my_session->access_check( $sts_table_access[STATUS_CODES_TABLE] );	// Make sure we should be here

$sts_subtitle = "Add Status Code";
require_once( "include/header_inc.php" );
require_once( "include/navbar_inc.php" );

require_once( "include/sts_form_class.php" );
require_once( "include/sts_status_codes_class.php" );

$status_codes_table = sts_status_codes::getInstance($exspeedite_db, $sts_debug);
$status_codes_form = new sts_form( $sts_form_addstatus_codes_form, $sts_form_add_status_codes_fields, $status_codes_table, $sts_debug);

if( isset($_POST) && count($_POST) > 0 ) {		// Process completed form
	$result = $status_codes_form->process_add_form();

	if( $sts_debug ) die; // So we can see the results
	if( $result ) {
		$status_codes_table->write_cache();
		if( isset($_POST["saveadd"]) )
			reload_page ( "exp_addstatus_codes.php" );
		else
			reload_page ( "exp_liststatus_codes.php" );
	}
		
}

?>
<div class="container-full theme-showcase" role="main">

<div class="well  well-md">
<?php

if( isset($value) && is_array($value) && $result == false ) {	// If error occured
	echo "<p><strong>Error:</strong> ".$status_codes_table->error()."</p>";
	echo $status_codes_form->render( $value );
} else {
	echo $status_codes_form->render();
}

?>
</div>
</div>

<?php

require_once( "include/footer_inc.php" );
?>
		

