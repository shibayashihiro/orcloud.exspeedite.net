<?php 

// $Id: exp_addattachment.php 5167 2023-07-23 01:28:15Z dev $
// Add attachment

// Set flag that this is a parent file
define( '_STS_INCLUDE', 1 );

// Setup Session
require_once( "include/sts_session_setup.php" );

require_once( "include/sts_config.php" );
$sts_debug = (isset($_GET['debug'])  || isset($_POST['debug'])) && in_group( EXT_GROUP_DEBUG );
require_once( "include/sts_session_class.php" );

// The next 3 lines to try and deal with Getting UPLOAD_ERR_PARTIAL when uploading files
ini_set('max_execution_time', 300);		// Set timeout to 5 minutes
//ini_set('post_max_size', '64M');
//ini_set('upload_max_filesize', '40M');
ini_set('memory_limit', '1024M');
set_time_limit(300);

$my_session = sts_session::getInstance( $exspeedite_db, $sts_debug );
$my_session->access_check( $sts_table_access[ATTACHMENT_TABLE] );	// Make sure we should be here

require_once( "include/sts_setting_class.php" );
require_once( "include/sts_email_class.php" );

$setting_table = sts_setting::getInstance($exspeedite_db, $sts_debug);
$max_size = $setting_table->get( 'option', 'ATTACHMENTS_MAX_SIZE' );

if( isset($_POST) || ( isset($_GET['SOURCE_CODE']) && isset($_GET['SOURCE_TYPE']) ) ) {
	//$sts_subtitle = "Add Attachment";
	//require_once( "include/header_inc.php" );
	//require_once( "include/navbar_inc.php" );

	require_once( "include/sts_form_class.php" );
	require_once( "include/sts_attachment_class.php" );
	require_once( "include/sts_item_list_class.php" );
	
	// This triggers loading the default items, including attachment types.
	$item_list_table = sts_item_list::getInstance($exspeedite_db, $sts_debug);
	
	$attachment_table = sts_attachment::getInstance($exspeedite_db, $sts_debug);
	$sts_form_add_attachment_fields['STORED_AS']['value'] =
		$attachment_table->stored_as();

	$attachment_form = new sts_form( $sts_form_add_attachment_form, $sts_form_add_attachment_fields, $attachment_table, $sts_debug );
	
	if( isset($_POST) && count($_POST) > 0 ) {		// Process completed form
		$result = $attachment_form->process_add_form();
		
		//! SCR# 459 - compress PDF attachment
		if( $result )
			$attachment_table->compress( $result );
		else {
			echo '<p>Failed to add attachment: '.$attachment_table->error().'<br>
				A message has been sent to Exspeedite support.<br><br>
				<a href="index.php">Click to return to home page</a></p>';
			$email = sts_email::getInstance($exspeedite_db, $sts_debug);
			$email->send_alert('Failed to add attachment: '.$attachment_table->error, EXT_ERROR_ERROR);
		}
	
		if( ! $result || $sts_debug ) die; // So we can see the results
		switch( $_POST["SOURCE_TYPE"] ) {
			case 'driver':
				reload_page ( "exp_editdriver.php?CODE=".$_POST["SOURCE_CODE"] );

			case 'carrier':
				reload_page ( "exp_editcarrier.php?CODE=".$_POST["SOURCE_CODE"] );

			//! SCR# 193 - add attachments to tractor and trailer
			case 'tractor':
				reload_page ( "exp_edittractor.php?CODE=".$_POST["SOURCE_CODE"] );

			case 'trailer':
				reload_page ( "exp_edittrailer.php?CODE=".$_POST["SOURCE_CODE"] );

			case 'client':
				reload_page ( "exp_editclient.php?CODE=".$_POST["SOURCE_CODE"] );

			case 'company':
				reload_page ( "exp_editcompany.php?CODE=".$_POST["SOURCE_CODE"] );

			case 'office':
				reload_page ( "exp_editoffice.php?CODE=".$_POST["SOURCE_CODE"] );

			case 'shipment':
				reload_page ( "exp_addshipment.php?CODE=".$_POST["SOURCE_CODE"] );
								
			case 'load':
				reload_page ( "exp_viewload.php?CODE=".$_POST["SOURCE_CODE"] );

			case 'scr':
				reload_page ( "exp_editscr.php?CODE=".$_POST["SOURCE_CODE"] );

			case 'insp_report':
				$referer = "exp_addinsp_report.php?REPORT=".$_POST["SOURCE_CODE"];
				if( isset($_SESSION["REPORT_REFERRER"])) {
					$referer .= '&REFERER='.$_SESSION["REPORT_REFERRER"];
					unset($_SESSION["REPORT_REFERRER"]);
				}
				reload_page ( $referer );
		}
		die; // So we don't fall through.
			
	}
	
	?>
	<div class="modal-body" style="font-size: 14px; body:inherit;">
	<?php
	
	if( isset($value) && is_array($value) && $result == false ) {	// If error occured
		echo "<p><strong>Error:</strong> ".$attachment_table->error()."</p>";
		echo $attachment_form->render( $value );
	} else {
		if( isset($_GET['SOURCE_CODE']) && isset($_GET['SOURCE_TYPE']) ) {
			$value = array('SOURCE_CODE' => $_GET['SOURCE_CODE'],
				'SOURCE_TYPE' => $_GET['SOURCE_TYPE'] );
			echo $attachment_form->render( $value );
		} else {
			echo "<p>Error: unexpected event. Send a copy of this to Exspeedite support. Make a note of what you clicked on to get here.</p>";
				echo "<pre>GET, POST";
				var_dump($_GET);
				var_dump($_POST);
				echo "</pre>";
			$email = sts_email::getInstance($exspeedite_db, $sts_debug);
			$email->send_alert('Called without parameters: exp_addattachment.php'.
				'<br>Normally you should see SOURCE_CODE and SOURCE_TYPE'.
				'<br>Check HTTP_REFERER for clues', EXT_ERROR_ERROR);
		}
	}
}

?>
</div>
	<script language="JavaScript" type="text/javascript"><!--
		
		$(document).ready( function () {

			var max_size = <?php echo $max_size; ?>;
			
			function bytesToSize(bytes) {
			   var sizes = ['Bytes', 'KB', 'MB', 'GB', 'TB'];
			   if (bytes == 0) return '0 Byte';
			   var i = parseInt(Math.floor(Math.log(bytes) / Math.log(1024)));
			   return Math.round(bytes / Math.pow(1024, i), 2) + ' ' + sizes[i];
			};

			//! SCR# 459 - validate size of attachment is not too big
			function validate( size ) {
				if( size > 0 && size <= max_size ) {
					$('button[type="submit"]').prop('disabled', false);
					$('#FILE_GROUP').removeClass('bg-danger');
					$('#TOO_BIG').prop('hidden','hidden');
				} else {
					$('button[type="submit"]').prop('disabled', true);
					$('#FILE_GROUP').addClass('bg-danger');
					if( size > max_size ) {
						$('#TOO_BIG').text('File is too big to attach (' + bytesToSize(size) + ', max size ' + bytesToSize(max_size) + ')');
						$('#TOO_BIG').prop('hidden',false);
					}
					window.addattachment_HAS_CHANGED = false;
				}
			}

			$(document).ready( function () {
				$('#FILE_NAME').on( 'change', function () {
				    validate(this.files[0].size);
				});
			});
			
			validate( 0 );

		});
	//--></script>
<?php

require_once( "include/footer_inc.php" );
?>
		

