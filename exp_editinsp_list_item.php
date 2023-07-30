<?php 

// $Id: exp_editinsp_list_item.php 4350 2021-03-02 19:14:52Z duncan $
// Edit inspection list item

// Set flag that this is a parent file
define( '_STS_INCLUDE', 1 );

// Setup Session
require_once( "include/sts_session_setup.php" );

require_once( "include/sts_config.php" );
$sts_debug = (isset($_GET['debug'])  || isset($_POST['debug'])) && in_group( EXT_GROUP_DEBUG );
require_once( "include/sts_session_class.php" );

$my_session = sts_session::getInstance( $exspeedite_db, $sts_debug );
$my_session->access_check( EXT_GROUP_ADMIN );	// Make sure we should be here

$sts_subtitle = "Edit Item";
require_once( "include/header_inc.php" );
require_once( "include/navbar_inc.php" );

require_once( "include/sts_form_class.php" );
require_once( "include/sts_insp_list_item_class.php" );

$insp_list_item_table = sts_insp_list_item::getInstance($exspeedite_db, $sts_debug);
$insp_list_item_form = new sts_form($sts_form_edit_insp_list_item_form, $sts_form_edit_insp_list_item_fields, $insp_list_item_table, $sts_debug);

//! SCR# 647 - R&M Reports - show extra fields
require_once( "include/sts_setting_class.php" );
$setting_table = sts_setting::getInstance($exspeedite_db, $sts_debug);
$extra_fields = $setting_table->get( 'option', 'RM_REPORT_EXTRA_FIELDS' ) == 'true';


if( isset($_POST) && count($_POST) > 0 ) {		// Process completed form
	$result = $insp_list_item_form->process_edit_form();

	if( $result ) {
		if( $sts_debug ) die; // So we can see the results
		reload_page ( "exp_editrm_form.php?CODE=".$_POST["RM_FORM"] );
	}
}

?>
<div class="container-full theme-showcase" role="main">

<div class="well  well-lg">
<?php

if( isset($value) && is_array($value) && $result == false ) {	// If error occured
	echo "<p><strong>Error:</strong> ".$insp_list_item_table->error()."</p>";
	echo $insp_list_item_form->render( $value );
} else if( isset($_GET['CODE']) ) {
	$result = $insp_list_item_table->fetch_rows($insp_list_item_table->primary_key." = ".$_GET['CODE']);
	echo $insp_list_item_form->render( $result[0] );
}

?>
</div>
</div>

	<script language="JavaScript" type="text/javascript"><!--
		
		// Sets the client info
		$(document).ready( function () {

			function update_increment() {
				if( ($('#ITEM_TYPE').val() == 'hours' || $('#ITEM_TYPE').val() == 'odometer') && $('#ITEM_EXTRA').val() == 'increment' ) {
					$('#INCREMENT').prop('hidden',false);
				} else {
					$('#INCREMENT').prop('hidden', 'hidden');
				}
			}

			$('#ITEM_EXTRA').change(function () {
				update_increment();
			});
			
			update_increment();

			<?php if( $extra_fields ) { ?>
					$('#extra').prop('hidden',false);
			<?php } ?>

		});
	//--></script>


<?php

require_once( "include/footer_inc.php" );
?>

