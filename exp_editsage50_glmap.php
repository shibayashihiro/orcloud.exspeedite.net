<?php 
// Set flag that this is a parent file
define( '_STS_INCLUDE', 1 );

// Setup Session
require_once( "include/sts_session_setup.php" );

require_once( "include/sts_config.php" );
$sts_debug = (isset($_GET['debug'])  || isset($_POST['debug'])) && in_group( EXT_GROUP_DEBUG );
require_once( "include/sts_session_class.php" );

$my_session = sts_session::getInstance( $exspeedite_db, $sts_debug );
$my_session->access_check( $sts_table_access[SAGE50_GLMAP_TABLE] );	// Make sure we should be here

$sts_subtitle = "Edit Sage 50 GL Mapping";
require_once( "include/header_inc.php" );
require_once( "include/navbar_inc.php" );

require_once( "include/sts_form_class.php" );
require_once( "include/sts_sage50_glmap_class.php" );

$sage50_glmap_table = new sts_sage50_glmap($exspeedite_db, $sts_debug);
$sage50_glmap_form = new sts_form( $sts_form_edit_sage50_glmap_form,
	$sts_form_edit_sage50_glmap_fields, $sage50_glmap_table, $sts_debug);

if( isset($_POST) && count($_POST) > 0 ) {		// Process completed form
	$result = $sage50_glmap_form->process_edit_form();

	if( $result ) {
		if( $sts_debug ) die; // So we can see the results
		reload_page ( "exp_editoffice.php?CODE=".$_POST["OFFICE_CODE"] );
	}
}

?>
<div class="container-full theme-showcase" role="main">

<div class="well  well-lg">
<?php

if( isset($value) && is_array($value) && $result == false ) {	// If error occured
	echo "<p><strong>Error:</strong> ".$sage50_glmap_table->error()."</p>";
	echo $sage50_glmap_form->render( $value );
} else if( isset($_GET['CODE']) ) {
	$result = $sage50_glmap_table->fetch_rows($sage50_glmap_table->primary_key." = ".$_GET['CODE']);
	echo $sage50_glmap_form->render( $result[0] );
}

?>
</div>
</div>

	<script language="JavaScript" type="text/javascript"><!--
		
		$(document).ready( function () {
			
			function update_item() {
				//console.log('update_item: ',$('select#GLTYPE').val());
				if( $('select#GLTYPE').val() == 'expense') {
					$('select#ITEM').val('default').change();
					readonly('select#ITEM', true);
				} else {
					readonly('select#ITEM', false);
				}
			}
			
			$('select#GLTYPE').on('change', function() {
				update_item();
			});

			update_item();
			readonly('select#OFFICE_CODE', true);
			
		});
	//--></script>

<?php

require_once( "include/footer_inc.php" );
?>

