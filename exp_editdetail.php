<?php 
// Set flag that this is a parent file
define( '_STS_INCLUDE', 1 );

// Setup Session
require_once( "include/sts_session_setup.php" );

require_once( "include/sts_config.php" );
$sts_debug = (isset($_GET['debug'])  || isset($_POST['debug'])) && in_group( EXT_GROUP_DEBUG );
require_once( "include/sts_session_class.php" );

$my_session = sts_session::getInstance( $exspeedite_db, $sts_debug );
$my_session->access_check( $sts_table_access[DETAIL_TABLE] );	// Make sure we should be here

if( isset($_POST) || isset($_GET['CODE']) ) {
	if( isset($_GET['standalone']) ) {
		$sts_subtitle = "Edit Commodity";
		require_once( "include/header_inc.php" );
	}
	
	require_once( "include/sts_form_class.php" );
	require_once( "include/sts_detail_class.php" );
	
	$detail_table = sts_detail::getInstance($exspeedite_db, $sts_debug);
	$detail_form = new sts_form($sts_form_edit_detail, $sts_form_edit_detail_fields,
		$detail_table, $sts_debug);
	$detail_form->set_noconfirm();
	
	if( isset($_POST) && count($_POST) > 0 ) {		// Process completed form
		$result = $detail_form->process_edit_form();

		if( $sts_debug ) die; // So we can see the results
		if( $result ) {
			if( isset($_POST["saveadd"]) )
				reload_page ( "exp_adddetail.php?CODE=".$_POST["PICK_CODE"] );
			else
				die;
				//reload_page ( "exp_editorder_stop.php?CODE=".$_POST["PICK_CODE"] );
		}
	}
		
	?>

<div class="modal-body" style="font-size: 14px; body:inherit;">
	<?php
	if( isset($value) && is_array($value) && $result == false ) {	// If error occured
		echo "<p><strong>Error:</strong> ".$detail_table->error()."</p>";
		echo $detail_form->render( $value );
	} else if( isset($_GET['CODE']) ) {
		$result = $detail_table->fetch_rows($detail_table->primary_key." = ".$_GET['CODE']);
		echo $detail_form->render( $result[0] );
	}
	
}
?>
</div>

	<script language="JavaScript" type="text/javascript"><!--
		/*
		function process_edit() {
			var code = $('#SHIPMENT_CODE').val();
			//console.log('code = ', code);

			$('.my-switch').bootstrapToggle({
				on: 'on',
				off: 'off',
				onstyle: 'success'
			});
			
			function update_commodity() {
				$.ajax({
					url: 'exp_lookup_pu.php',
					data: {
						CODE: $('#COMMODITY').val(),
						PW: 'Mashed'
					},
					dataType: "json",
					success: function(data) {
						$('select#PIECES_UNITS').val(data['PIECES_UNITS']).change();
						$('input#UN_NUMBER').val(data['UN_NUMBER']).change();
						$('input#TEMP_CONTROLLED').val(data['TEMP_CONTROLLED']).change();

						console.log('update_commodity', data['TEMP_CONTROLLED'], typeof(data['TEMP_CONTROLLED']));
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
			
			$('select#COMMODITY').on('change', function(event) {
				update_commodity();
			});
			
			console.log($('input#TEMP_CONTROLLED').val(), typeof($('input#TEMP_CONTROLLED').val()));
			if( $('input#TEMP_CONTROLLED').val() == "1" ) {
				$('.temp').prop('hidden',false);
			} else {
				$('.temp').prop('hidden', 'hidden');
			}

			$( "#edit_detail" ).submit(function( event ) {
				event.preventDefault();  //prevent form from submitting
				var data = $("#edit_detail :input").serializeArray();
				$.post("exp_editdetail.php", data, function( data ) {
					$('a').off('click.edit_detail');
					$('#myModal_add').modal('hide');
					// 4-16-2014 - trying to close modal and not hang.
					$('body').removeClass('modal-open');
					$('.modal-backdrop').remove();
					$('#COMMODITIES').load('exp_list_table.php?pw=Emmental&table=detail&code=' + code);
					
					$.ajax({
						//async: false,
						url: 'exp_shipment_totals.php',
						data: { pw: 'Velocity', code: code },
						dataType: "json",
						success: function(data) {
							$('input#PALLETS').val(data[0].PALLETS);
							$('input#PIECES').val(data[0].PIECES);
							$('input#WEIGHT').val(data[0].WEIGHT);
						}
					});
				});
			});
		
		}
		$(document).ready( function () {
			process_edit();
		});
		*/
	//--></script>


<?php

	if( isset($_GET['standalone']) ) {
		require_once( "include/footer_inc.php" );
	}
?>

