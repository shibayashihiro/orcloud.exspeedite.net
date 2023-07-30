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
$my_session->access_check( $sts_table_access[PALLET_TABLE] );	// Make sure we should be here

$sts_subtitle = "Add Pallet";
require_once( "include/header_inc.php" );
require_once( "include/navbar_inc.php" );

require_once( "include/sts_form_class.php" );
require_once( "include/sts_pallet_class.php" );

$pallet_table = new sts_pallet($exspeedite_db, $sts_debug);
$pallet_form = new sts_form( $sts_form_addpallet_form, $sts_form_add_pallet_fields, $pallet_table, $sts_debug);

if( isset($_POST) && count($_POST) > 0 ) {		// Process completed form
	$result = $pallet_form->process_add_form();

	if( $sts_debug ) die; // So we can see the results
	if( $result ) 
		if( isset($_POST["saveadd"]) )
			reload_page ( "exp_addpallet.php" );
		else
			reload_page ( "exp_listpallet.php" );
		
}

?>
<div class="container-full theme-showcase" role="main">

<div class="well  well-md">
<?php

if( isset($value) && is_array($value) && $result == false ) {	// If error occured
	echo "<p><strong>Error:</strong> ".$pallet_table->error()."</p>";
	echo $pallet_form->render( $value );
} else {
	if( isset($_GET['client']) ) 
		echo $pallet_form->render( array('CLIENT' => $_GET['client']) );
	else
		echo $pallet_form->render();
}

?>
</div>
</div>

	<script language="JavaScript" type="text/javascript"><!--
	
		$(document).ready( function () {
		
			function update_pallets() {
				$.ajax({
					async: false,
					url: 'exp_pallet_lookup.php',
					data: { code: 'Frost', client: $('#PALLET_CLIENT').val() },
					dataType: "json",
					success: function(data) {
						$('#CLIENT2').html(data);
					}
				});
			}
		
			$('select#CLIENT').on('change', function(event) {
				update_pallets();
			});
			
			update_pallets();
			
		});
	//--></script>

<?php

require_once( "include/footer_inc.php" );
?>
		

