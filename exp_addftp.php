<?php 
// Set flag that this is a parent file
define( '_STS_INCLUDE', 1 );

// Setup Session
require_once( "include/sts_session_setup.php" );

require_once( "include/sts_config.php" );
$sts_debug = (isset($_GET['debug'])  || isset($_POST['debug'])) && in_group( EXT_GROUP_DEBUG );
require_once( "include/sts_session_class.php" );

$my_session = sts_session::getInstance( $exspeedite_db, $sts_debug );
$my_session->access_check( $sts_table_access[FTP_TABLE] );	// Make sure we should be here

$sts_subtitle = "Add FTP Configuration";
require_once( "include/header_inc.php" );
require_once( "include/navbar_inc.php" );

require_once( "include/sts_form_class.php" );
require_once( "include/sts_ftp_class.php" );
require_once( "include/sts_setting_class.php" );

$ftp_table = sts_ftp::getInstance($exspeedite_db, $sts_debug);
$setting_table = sts_setting::getInstance($exspeedite_db, $sts_debug);
$multi_company = ($setting_table->get("option", "MULTI_COMPANY") == 'true');

if( ! $multi_company ) {
	$match = preg_quote('<!-- CC01 -->').'(.*)'.preg_quote('<!-- CC02 -->');
	$sts_form_addftp_form['layout'] = preg_replace('/'.$match.'/s', '',
		$sts_form_addftp_form['layout'], 1);	
}

$ftp_form = new sts_form( $sts_form_addftp_form, $sts_form_add_ftp_fields, $ftp_table, $sts_debug);

if( isset($_POST) && count($_POST) > 0 ) {		// Process completed form
	$result = $ftp_form->process_add_form();

	if( $sts_debug ) die; // So we can see the results
	if( $result ) 
		reload_page ( "exp_listftp.php" );
		
}

?>
<div class="container-full theme-showcase" role="main">

<div class="well  well-md">
<?php

if( isset($value) && is_array($value) && $result == false ) {	// If error occured
	echo "<p><strong>Error:</strong> ".$ftp_table->error()."</p>";
	echo $ftp_form->render( $value );
} else {
	if( isset($_GET['client']) ) 
		echo $ftp_form->render( array('CLIENT' => $_GET['client']) );
	else
		echo $ftp_form->render();
}

?>
</div>
</div>

	<script language="JavaScript" type="text/javascript"><!--
		
		$(document).ready( function () {
			
			function update_ftp() {
				if( $('select#TRANSPORT').val() == 'FTP Transfer' ) {
					$('.FTP').prop('hidden',false);
				} else {
					$('.FTP').prop('hidden', 'hidden');
				}
			};
			
			$('select#TRANSPORT').on('change', function() {
				update_ftp();
			});
			
			update_ftp();
		});
	//--></script>

<?php
require_once( "include/footer_inc.php" );
?>
		

