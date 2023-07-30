<?php 
// Set flag that this is a parent file
define( '_STS_INCLUDE', 1 );

// Setup Session
require_once( "include/sts_session_setup.php" );
define( '_STS_JQUERY_FORM', 1 );	// To use JQuery.Form

require_once( "include/sts_config.php" );
$sts_debug = (isset($_GET['debug'])  || isset($_POST['debug'])) && in_group( EXT_GROUP_DEBUG );
require_once( "include/sts_session_class.php" );

$my_session = sts_session::getInstance( $exspeedite_db, $sts_debug );
$my_session->access_check( $sts_table_access[IMAGE_TABLE] );	// Make sure we should be here

if( isset($_POST) || ( isset($_GET['CODE']) && isset($_GET['CODE']) ) ) {
	$sts_subtitle = "Add Image";
	if( isset($_GET['standalone']) ) {
		require_once( "include/header_inc.php" );
		//require_once( "include/navbar_inc.php" );
	}
	
	require_once( "include/sts_form_class.php" );
	require_once( "include/sts_image_class.php" );
	
	$image_table = new sts_image($exspeedite_db, $sts_debug);
	$image_form = new sts_form( $sts_form_addimage_form, $sts_form_add_image_fields,
		$image_table, $sts_debug);
	
	if( isset($_POST) && count($_POST) > 0 ) {		// Process completed form
		$result = $image_form->process_add_form();
	
		if( $sts_debug ) die; // So we can see the results
		if( $result ) {
			if( isset($_POST["saveadd"]) )
				reload_page ( "exp_addimage.php?CODE=".$_POST["PARENT_CODE"] );
			else
				die;
			//	reload_page ( "exp_editshipment.php?CODE=".$_POST["PARENT_CODE"] );

		}
			
	}
	
	?>

<div class="modal-body" style="font-size: 14px; body:inherit;">
	<?php
	
	if( isset($value) && is_array($value) && $result == false ) {	// If error occured
		echo "<p><strong>Error:</strong> ".$image_table->error()."</p>";
		echo $image_form->render( $value );
	} else {
		$value = array('PARENT_CODE' => $_GET['CODE'] );
		echo $image_form->render( $value );
	}
}

?>
</div>
	
	<script language="JavaScript" type="text/javascript"><!--
		
		$(document).ready( function () {
			var code = <?php echo $_GET['CODE']; ?>;
			var standalone = <?php echo isset($_GET['standalone']) ? 'true' : 'false'; ?>;
			$("#addimage").ajaxForm({
				async: false,
				beforeSubmit: function() {
					$("#myModal_add div div div").html('<p class="text-center"><img src="images/loading.gif" alt="loading" width="125" height="125" /></p>');
				},
				success: function() {
					if( standalone ) {
						alert("ajaxForm success.");
					}
					$('a').off('click.addimage');
					$('#myModal_add').modal('hide');
					// 4-16-2014 - trying to close modal and not hang.
					$('body').removeClass('modal-open');
					$('.modal-backdrop').remove();
					
					$('#IMAGES').load('exp_list_table.php?pw=Emmental&table=image&code=' + code);
				
				}});
			
			$( "#addimage" ).submit(function( event ) {
				console.log("standalone " + standalone + " The image " + $("#THE_IMAGE").val());
				if( $("#THE_IMAGE").val() == '' ) {
					event.preventDefault();  //prevent form from submitting
					alert("You need to select a file.");
				} else {
					if( ! standalone ) {
						event.preventDefault();  //prevent form from submitting
						//$(this).ajaxSubmit();
						/*var data = $("#addimage :input").serializeArray();
						console.log($("#addimage :input"), data);
						$.post("exp_addimage.php", data, function( data ) {
							$('a').off('click.add_image');
							$('#myModal_add').modal('hide');
							// 4-16-2014 - trying to close modal and not hang.
							$('body').removeClass('modal-open');
							$('.modal-backdrop').remove();
							
							$('#IMAGES').load('exp_list_table.php?pw=Emmental&table=image&code=' + code);
							
						});
						*/
					}
				}
				return false;								
			});
		});
	//--></script>


<?php

	if( isset($_GET['standalone']) ) {
		require_once( "include/footer_inc.php" );
	}
?>
		

