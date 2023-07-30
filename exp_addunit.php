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
$my_session->access_check( $sts_table_access[UNIT_TABLE] );	// Make sure we should be here

$sts_subtitle = "Add Unit";
require_once( "include/header_inc.php" );
require_once( "include/navbar_inc.php" );

require_once( "include/sts_form_class.php" );
require_once( "include/sts_unit_class.php" );

$unit_table = new sts_unit($exspeedite_db, $sts_debug);
$unit_form = new sts_form( $sts_form_addunit_form, $sts_form_add_unit_fields, $unit_table, $sts_debug);

if( isset($_POST) && count($_POST) > 0 ) {		// Process completed form
	$result = $unit_form->process_add_form();

	if( $sts_debug ) die; // So we can see the results
	if( $result ) 
		if( isset($_POST["saveadd"]) )
			reload_page ( "exp_addunit.php" );
		else
			reload_page ( "exp_listunit.php" );
		
}

?>
<div class="container-full theme-showcase" role="main">

<div class="well  well-md">
<?php

if( isset($value) && is_array($value) && $result == false ) {	// If error occured
	echo "<p><strong>Error:</strong> ".$unit_table->error()."</p>";
	echo $unit_form->render( $value );
} else {
	echo $unit_form->render();
}

?>
</div>
</div>

<?php

require_once( "include/footer_inc.php" );
?>
		

