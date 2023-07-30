<?php 
// Set flag that this is a parent file
define( '_STS_INCLUDE', 1 );

// Setup Session
require_once( "include/sts_session_setup.php" );

require_once( "include/sts_config.php" );
$sts_debug = (isset($_GET['debug'])  || isset($_POST['debug'])) && in_group( EXT_GROUP_DEBUG );
require_once( "include/sts_session_class.php" );

$my_session = sts_session::getInstance( $exspeedite_db, $sts_debug );
$my_session->access_check( $sts_table_access[VACATION_TABLE] );	// Make sure we should be here

$sts_subtitle = "Edit Vacation";
require_once( "include/header_inc.php" );
require_once( "include/navbar_inc.php" );

require_once( "include/sts_result_class.php" );
require_once( "include/sts_form_class.php" );
require_once( "include/sts_vacation_class.php" );

$vacation_table = new sts_vacation($exspeedite_db, $sts_debug);
$vacation_form = new sts_form($sts_form_editvacation_form, $sts_form_edit_vacation_fields, $vacation_table, $sts_debug);

if( isset($_POST) && count($_POST) > 0 ) {		// Process completed form
	$result = $vacation_form->process_edit_form();

	if( $result ) {
		if( $sts_debug ) die; // So we can see the results
		reload_page ( "exp_listvacation.php" );
	}
}

?>
<div class="container-full theme-showcase" role="main">

<div class="well  well-lg">
<?php

if( isset($value) && is_array($value) && $result == false ) {	// If error occured
	echo "<p><strong>Error:</strong> ".$vacation_table->error()."</p>";
	echo $vacation_form->render( $value );
} else if( isset($_GET['CODE']) ) {
	$result = $vacation_table->fetch_rows($vacation_table->primary_key." = ".$_GET['CODE']);
	echo $vacation_form->render( $result[0] );
}

?>
</div>
</div>

<?php

require_once( "include/footer_inc.php" );
?>

	<script language="JavaScript" type="text/javascript"><!--
	
		$(document).ready( function () {
		
			function update_days() {
				if( $('input#START_DATE').val() != '' &&
					$('input#END_DATE').val() != '' ) {
						$.ajax({
							async: false,
							url: 'exp_vacation_lookup.php',
							data: { code: 'Paypal',
								date1: $('input#START_DATE').val(),
								date2: $('input#END_DATE').val() },
							dataType: "json",
							success: function(data) {
								console.log(data);
								$('input#NUM_DAYS').val(data);
							}
						});
				}
			}
		
			$('#START_DATE').on('dp.change', function(event) {
				update_days();
			});
			
			$('#END_DATE').on('dp.change', function(event) {
				update_days();
			});
			
			update_days();
			
		});
	//--></script>