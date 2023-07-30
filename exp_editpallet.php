<?php 
// Set flag that this is a parent file
define( '_STS_INCLUDE', 1 );

// Setup Session
require_once( "include/sts_session_setup.php" );

require_once( "include/sts_config.php" );
$sts_debug = (isset($_GET['debug'])  || isset($_POST['debug'])) && in_group( EXT_GROUP_DEBUG );
require_once( "include/sts_session_class.php" );

$my_session = sts_session::getInstance( $exspeedite_db, $sts_debug );
$my_session->access_check( $sts_table_access[PALLET_TABLE] );	// Make sure we should be here

$sts_subtitle = "Edit Pallet";
require_once( "include/header_inc.php" );
require_once( "include/navbar_inc.php" );

require_once( "include/sts_result_class.php" );
require_once( "include/sts_form_class.php" );
require_once( "include/sts_pallet_class.php" );

$pallet_table = new sts_pallet($exspeedite_db, $sts_debug);
$pallet_form = new sts_form($sts_form_editpallet_form, $sts_form_edit_pallet_fields, $pallet_table, $sts_debug);

if( isset($_POST) && count($_POST) > 0 ) {		// Process completed form
	$result = $pallet_form->process_edit_form();

	if( $result ) {
		if( $sts_debug ) die; // So we can see the results
	}
}

?>
<div class="container-full theme-showcase" role="main">

<div class="well  well-lg">
<?php

if( isset($value) && is_array($value) && $result == false ) {	// If error occured
	echo "<p><strong>Error:</strong> ".$pallet_table->error()."</p>";
	echo $pallet_form->render( $value );
} else if( isset($_GET['CODE']) ) {
	$result = $pallet_table->fetch_rows($pallet_table->primary_key." = ".$_GET['CODE']);
	echo $pallet_form->render( $result[0] );
}

?>
</div>
</div>
<?php

require_once( "include/footer_inc.php" );
?>

