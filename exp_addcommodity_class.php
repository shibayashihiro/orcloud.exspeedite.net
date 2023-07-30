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
$my_session->access_check( $sts_table_access[COMMODITY_CLASS_TABLE] );	// Make sure we should be here

$sts_subtitle = "Add Commodity Class";
require_once( "include/header_inc.php" );
require_once( "include/navbar_inc.php" );

require_once( "include/sts_form_class.php" );
require_once( "include/sts_commodity_class_class.php" );

$commodity_class_table = new sts_commodity_class($exspeedite_db, $sts_debug);
$commodity_class_form = new sts_form( $sts_form_addcommodity_class_form, $sts_form_add_commodity_class_fields, $commodity_class_table, $sts_debug);

if( isset($_POST) && count($_POST) > 0 ) {		// Process completed form
	$result = $commodity_class_form->process_add_form();

	if( $sts_debug ) die; // So we can see the results
	if( $result ) 
		if( isset($_POST["saveadd"]) )
			reload_page ( "exp_addcommodity_class.php" );
		else
			reload_page ( "exp_listcommodity_class.php" );
		
}

?>
<div class="container-full theme-showcase" role="main">

<div class="well  well-md">
<?php

if( isset($value) && is_array($value) && $result == false ) {	// If error occured
	echo "<p><strong>Error:</strong> ".$commodity_class_table->error()."</p>";
	echo $commodity_class_form->render( $value );
} else {
	echo $commodity_class_form->render();
}

?>
</div>
</div>

	<script language="JavaScript" type="text/javascript"><!--
		
		$(document).ready( function () {
			
			function update_temp() {
				if( $('input#TEMP_CONTROLLED').prop('checked') ) {
					$('.temp').prop('hidden',false);
				} else {
					$('.temp').prop('hidden', 'hidden');
				}
			}


			$('input#TEMP_CONTROLLED').change(function () {
				update_temp();
			});
			
			update_temp();
			
		});
	//--></script>

<?php

require_once( "include/footer_inc.php" );
?>
		

