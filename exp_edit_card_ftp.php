<?php 
// Set flag that this is a parent file
define( '_STS_INCLUDE', 1 );

// Setup Session
require_once( "include/sts_session_setup.php" );

require_once( "include/sts_config.php" );
$sts_debug = (isset($_GET['debug'])  || isset($_POST['debug'])) && in_group( EXT_GROUP_DEBUG );
require_once( "include/sts_session_class.php" );

$my_session = sts_session::getInstance( $exspeedite_db, $sts_debug );
$my_session->access_check( $sts_table_access[CARD_FTP_TABLE] );	// Make sure we should be here

$sts_subtitle = "Edit FTP Configuration";
require_once( "include/header_inc.php" );
require_once( "include/navbar_inc.php" );

require_once( "include/sts_result_class.php" );
require_once( "include/sts_form_class.php" );
require_once( "include/sts_card_ftp_class.php" );

$ftp_table = new sts_card_ftp($exspeedite_db, $sts_debug);
$ftp_form = new sts_form($sts_form_editftp_form, $sts_form_edit_ftp_fields, $ftp_table, $sts_debug);

if( isset($_POST) && count($_POST) > 0 ) {		// Process completed form
	$result = $ftp_form->process_edit_form();

	if( $result ) {
		if( $sts_debug ) die; // So we can see the results
		reload_page ( "exp_list_card_ftp.php" );
	}
}

?>
<div class="container-full theme-showcase" role="main">

<div class="well  well-lg">
<?php

if( isset($value) && is_array($value) && $result == false ) {	// If error occured
	echo "<p><strong>Error:</strong> ".$ftp_table->error()."</p>";
	echo $ftp_form->render( $value );
} else if( isset($_GET['CODE']) ) {
	$result = $ftp_table->fetch_rows($ftp_table->primary_key." = ".$_GET['CODE']);
	echo $ftp_form->render( $result[0] );
}

?>
</div>
</div>

	<script language="JavaScript" type="text/javascript"><!--
		
		$(document).ready( function () {
			
			function update_transport() {
				if( $('#FTP_TRANSPORT').val() == 'FTP') {
					$('.show-ftp').prop('hidden',false);
					$('.show-bvd').prop('hidden', 'hidden');
				} else if( $('#FTP_TRANSPORT').val() == 'BVD')  {
					$('.show-ftp').prop('hidden', 'hidden');
					$('.show-bvd').prop('hidden',false);
				} else {
					$('.show-ftp').prop('hidden', 'hidden');
					$('.show-bvd').prop('hidden', 'hidden');
				}
			}

			$('#FTP_TRANSPORT').change(function () {
				update_transport();
			});
			
			update_transport();
			
		});
	//--></script>


<?php

require_once( "include/footer_inc.php" );
?>

