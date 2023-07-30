<?php 
// Set flag that this is a parent file
define( '_STS_INCLUDE', 1 );

// Setup Session
require_once( "include/sts_session_setup.php" );

require_once( "include/sts_config.php" );
$sts_debug = (isset($_GET['debug'])  || isset($_POST['debug'])) && in_group( EXT_GROUP_DEBUG );
require_once( "include/sts_session_class.php" );

$my_session = sts_session::getInstance( $exspeedite_db, $sts_debug );
$my_session->access_check( $sts_table_access[MANUAL_MILES_TABLE] );	// Make sure we should be here

$sts_subtitle = "Edit Manual Miles";
require_once( "include/header_inc.php" );
require_once( "include/navbar_inc.php" );

require_once( "include/sts_result_class.php" );
require_once( "include/sts_form_class.php" );
require_once( "include/sts_manual_miles_class.php" );

$manual_miles_table = new sts_manual_miles($exspeedite_db, $sts_debug);
$manual_miles_form = new sts_form($sts_form_editmanual_miles_form, $sts_form_edit_manual_miles_fields, $manual_miles_table, $sts_debug);

if( isset($_POST) && count($_POST) > 0 ) {		// Process completed form
	$result = $manual_miles_form->process_edit_form();

	if( $result ) {
		if( $sts_debug ) die; // So we can see the results
		reload_page ( "exp_listmanual_miles.php" );
	}
}

?>
<div class="container-full theme-showcase" role="main">

<div class="well  well-lg">
<?php

if( isset($value) && is_array($value) && $result == false ) {	// If error occured
	echo "<p><strong>Error:</strong> ".$manual_miles_table->error()."</p>";
	echo $manual_miles_form->render( $value );
} else if( isset($_GET['CODE']) ) {
	$result = $manual_miles_table->fetch_rows($manual_miles_table->primary_key." = ".$_GET['CODE']);
	echo $manual_miles_form->render( $result[0] );
}

?>
</div>
</div>
<?php

require_once( "include/footer_inc.php" );
?>

