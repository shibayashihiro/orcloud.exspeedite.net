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
$my_session->access_check( $sts_table_access[COMMODITY_TABLE] );	// Make sure we should be here

$sts_subtitle = "Add Commodity";
require_once( "include/header_inc.php" );
require_once( "include/navbar_inc.php" );

require_once( "include/sts_form_class.php" );
require_once( "include/sts_commodity_class.php" );

$commodity_table = new sts_commodity($exspeedite_db, $sts_debug);
$commodity_form = new sts_form( $sts_form_addcommodity_form, $sts_form_add_commodity_fields, $commodity_table, $sts_debug);

if( isset($_POST) && count($_POST) > 0 ) {		// Process completed form
	$result = $commodity_form->process_add_form();

	if( $sts_debug ) die; // So we can see the results
	if( $result ) 
		if( isset($_POST["saveadd"]) )
			reload_page ( "exp_addcommodity.php" );
		else
			reload_page ( "exp_listcommodity.php" );
		
}

?>
<div class="container-full theme-showcase" role="main">

<div class="well  well-md">
<?php

if( isset($value) && is_array($value) && $result == false ) {	// If error occured
	echo "<p><strong>Error:</strong> ".$commodity_table->error()."</p>";
	echo $commodity_form->render( $value );
} else {
	echo $commodity_form->render();
}

?>
</div>
</div>

	<script language="JavaScript" type="text/javascript"><!--
		
		$(document).ready( function () {
			
			function update_class() {
				$.ajax({
					url: 'exp_lookup_pu.php',
					data: {
						CODE: $('#CLASS').val(),
						TYPE: 'class',
						PW: 'Mashed'
					},
					dataType: "json",
					success: function(data) {
						console.log('update_class', data['TEMP_CONTROLLED'], typeof(data['TEMP_CONTROLLED']));
						$('input#TEMP_CONTROLLED').prop('checked', (data['TEMP_CONTROLLED'] == 1)).change();

						if( data['TEMP_CONTROLLED'] == 1 ) {
							$('input#TEMPERATURE').val(data['TEMPERATURE']).change();
							$('select#TEMPERATURE_UNITS').val(data['TEMPERATURE_UNITS']).change();
							$('.temp').prop('hidden',false);
						} else {
							$('input#TEMPERATURE').val('null').change();
							$('select#TEMPERATURE_UNITS').val('null').change();
							$('.temp').prop('hidden', 'hidden');
						}
					}
				});
			}
			
			$('select#CLASS').on('change', function(event) {
				update_class();
			});

			update_class();

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
		

