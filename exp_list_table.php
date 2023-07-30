<?php 

// $Id: exp_list_table.php 5030 2023-04-12 20:31:34Z duncan $
// Insert table

// Set flag that this is a parent file
define( '_STS_INCLUDE', 1 );

// Set flag that this is session readonly
define( '_STS_SESSION_READONLY', 1 );

// Set flag that this is an ajax call
define( '_STS_SESSION_AJAX', 1 );

// Setup Session
require_once( "include/sts_session_setup.php" );

require_once( "include/sts_config.php" );
require_once( "include/sts_setting_class.php" );
$setting_table = sts_setting::getInstance($exspeedite_db, isset($sts_debug) ? $sts_debug : false);
$edi = $setting_table->get( 'api', 'EDI_ENABLED' ) == 'true';

$sts_debug = isset($_GET['debug']) && in_group( EXT_GROUP_DEBUG );

if( isset($_GET['table']) && isset($_GET['code']) && isset($_GET['pw']) && $_GET['pw'] == 'Emmental' ) {

	switch( $_GET['table'] ) {
		case 'detail':
			$t_table = DETAIL_TABLE;
			$t_class = 'sts_detail';
			$t_short = 'detail';
			$t_key = 'SHIPMENT_CODE';
			$t_cond = '';
			$t_result = 'sts_result_detail_layout';
			$t_edit = 'sts_result_detail_edit';
			break;
		
		case 'image':
			$t_table = IMAGE_TABLE;
			$t_class = 'sts_image';
			$t_short = 'image';
			$t_key = 'PARENT_CODE';
			$t_cond = " AND IMAGE_SOURCE = 'OSD'";
			$t_result = 'sts_result_image_layout';
			$t_edit = 'sts_result_image_edit';
			break;
		
		default:
			die;
	}
	
	require_once( "include/sts_session_class.php" );
	
	$my_session = sts_session::getInstance( $exspeedite_db, $sts_debug );
	$my_session->access_check( $sts_table_access[$t_table] );	// Make sure we should be here
	
	if( isset($_GET['standalone']) ) {
		$sts_subtitle = "List Table";
		require_once( "include/header_inc.php" );
	}
	?>
	<?php
	require_once( "include/sts_result_class.php" );
	require_once( "include/".$t_class."_class.php" );
	
	$the_table = new $t_class($exspeedite_db, $sts_debug);
	
	//! SCR# 303 - fix issue that broke when readonly
	if( isset($_GET['readonly']) ) {
		if( $_GET['table'] == 'detail' ) {
			unset($sts_result_detail_edit['add']);
			unset($sts_result_detail_edit['rowbuttons']);
		} else {
			unset($sts_result_image_edit['add']);
			unset($sts_result_image_edit['rowbuttons']);
		}
	}

	if( ! $edi && $_GET['table'] == 'detail' ) {
		unset($sts_result_detail_layout["204_REFERENCE"]);
	}
	
	$rslt = new sts_result( $the_table, $t_key." = ".$_GET['code'].$t_cond, $sts_debug );
	echo $rslt->render( $$t_result, $$t_edit, "?CODE=".$_GET['code'] );
	
	?>

					<div id="myModal_add_EXP_DETAIL" class="modal fade" tabindex="-1" role="dialog" aria-labelledby="myModal_add" aria-hidden="true">
						<div class="modal-dialog modal-lg">
							<div class="modal-content form-horizontal" role="main">
								<div class="modal-body">
									<p class="text-center"><img src="images/loading.gif" alt="loading" width="125" height="125" /></p>
								</div>
							</div>
						</div>
					</div>
					
					<div id="myModal_edit_EXP_DETAIL" class="modal fade" tabindex="-1" role="dialog" aria-labelledby="myModal_edit" aria-hidden="true">
						<div class="modal-dialog modal-lg">
							<div class="modal-content form-horizontal" role="main">
								<div class="modal-body">
									<p class="text-center"><img src="images/loading.gif" alt="loading" width="125" height="125" /></p>
								</div>
							</div>
						</div>
					</div>

	<?php
	
	if( isset($_GET['standalone']) ) {
		require_once( "include/footer_inc.php" );
	}
}
?>
	<script language="JavaScript" type="text/javascript"><!--
		$(document).ready( function () {
			var code = <?php echo $_GET['code']; ?>;
			var table = '<?php echo $_GET['table']; ?>';
			
			//! SCR# 204 - add sorting and filtering for details
			$('#EXP_DETAIL').dataTable({
		        //"bLengthChange": false,
		        "bFilter": true,
		        "bSort": true,
		        "bInfo": true,
				//"bAutoWidth": false,
				//"bProcessing": true, 
				"sScrollX": "100%",
				"sScrollY": "200px",
				"sScrollXInner": "120%",
				"bPaginate": false,
				"bScrollCollapse": false,
				"bSortClasses": false,
				"columns": [
					<?php echo isset($_GET['readonly']) ? '' : '{ "searchable": false, "orderable": false },'; ?>
					<?php
						foreach( $sts_result_detail_layout as $key => $row ) {
							if( $row["format"] <> 'hidden')
								echo '{ "data": "'.$key.'", "searchable": true, "orderable": true },
						';
						}
					?>
				]
		
			});

			if( table == 'detail' ) {
				$('a.btn.btn-success, a.btn.inform, a.btn.confirm').on('click', function( event ) {
					//console.log('submit2', event);
					//$( '#addshipment' ).submit();
				});
			}

			$('.inform').popover({ 
				placement: 'right',
				html: 'true',
				container: 'body',
				trigger: 'hover',
				delay: { show: 50, hide: 3000 },
				title: '<strong>Information</strong> <button type="button" class="close" data-hide="confirm" data-delay="0" aria-hidden="true">&times;</button>' 
			});
			
			$('.confirm').popover({ 
				placement: 'right',
				html: 'true',
				container: 'body',
				trigger: 'hover',
				delay: { show: 50, hide: 3000 },
				title: '<strong>Confirm Action</strong> <button type="button" class="close" data-hide="confirm" data-delay="0" aria-hidden="true">&times;</button>' 
			});
			
			$('a.inform').on('click', function(event) {
				event.preventDefault();  //prevent form from submitting
				//console.log($(this).attr("href"));
				$('#myModal_add').modal({
					container: 'body',
					//content: 'boo'
					remote: $(this).attr("href")
				}).modal('show');
				//$('#myModal_add').modal('show');
				return false;
			});
			
			$("#myModal_add").on('hidden.bs.modal', function () {
				//console.log($(this).data());
			    $(this).data('bs.modal', null);
			    $("#myModal_add div div div").html('<p class="text-center"><img src="images/loading.gif" alt="loading" width="125" height="125" /></p>');
			    //$(this).modal({
			    //	container: 'body',
				//	content: '<div class="modal-body"><p class="text-center"><img src="images/loading.gif" alt="loading" width="125" height="125"></p></div>'
				//});
			});
			
			//$('#myModal_add_EXP_DETAIL').on('hidden.bs.modal', '.modal', function () {
			//    $(this).removeData('bs.modal');
			//});

			$('a.confirm').on('shown.bs.popover', function () {
				alert('confirm popover');
				$('a.btn-danger').on('click', function(event) {
					event.preventDefault();  //prevent form from submitting
					console.log('a',$(this).attr("href"));
					//alert($(this).attr("href"));
					$.get($(this).attr("href"), function () {
						$('#' + (table == 'detail' ? 'COMMODITIES' : 'IMAGES' ) ).load('exp_list_table.php?pw=Emmental&table=' + table + '&code=' + code);
						
						if( table == 'detail' ) {
							$.ajax({
								async: false,
								url: 'exp_shipment_totals.php',
								data: { pw: 'Velocity', code: code },
								dataType: "json",
								success: function(data) {
									console.log('exp_list_table.php update1', data.DANGEROUS_GOODS);
									$('input#PALLETS').val(data.PALLETS);
									$('input#PIECES').val(data.PIECES);
									$('input#WEIGHT').val(data.WEIGHT);
									$('input#DANGEROUS_GOODS').prop('checked', data.DANGEROUS_GOODS);
									$('input#UN_NUMBERS').val(data.UN_NUMBERS);
								}
							});
						}
					});
					return false;
				});
			});

			// This is for the add detail button
			$('a#EXP_DETAIL_add').on('click', function(event) {
				event.preventDefault();  //prevent form from submitting
				console.log($(this).attr("href"));
				
				$('#myModal_add_EXP_DETAIL').modal({
					container: 'body'
				});

				$('#myModal_add_EXP_DETAIL .modal-body').load( $(this).attr("href"), function (data) {
					process_add();
					$('a.btn').click(function( data ) {
						if( table == 'detail' ) {
							//$('#myModal_add_EXP_DETAIL').modal('hide');
						}
					});	
				});

				return false;
			});
			
			// This is for the edit detail button. The popup class is set via 
			// the detail class, $sts_result_detail_edit
			$('ul li a.popup').on('click', function(event) {
				event.preventDefault();  //prevent form from submitting
				//console.log($(this).attr("href"));
				//alert($(this).attr("href"));
				$('#myModal_edit_EXP_DETAIL').modal({
					container: 'body'
				});
				
				$('#myModal_edit_EXP_DETAIL .modal-body').load( $(this).attr("href"), function (data) {
					process_edit();
					$('a.btn').click(function( data ) {
						if( table == 'detail' ) {
							//$('#myModal_edit_EXP_DETAIL').modal('hide');
						}
					});	
				});

				return false;
			});
			
			//$('#myModal_add_EXP_DETAIL').on('shown.bs.modal', function (e) {
				//alert('there2');
			//	process_edit();
			//});

			// This clears the modal for adding detail, if you cancelled
			// Then when you re-add, the data is gone
			//$(document).on('hidden.bs.modal','#myModal_add_EXP_DETAIL', function () {
			//	$(this).removeData('bs.modal');
			//});
			
			//$('#myModal_add_EXP_DETAIL').removeData('bs.modal');	

			$(document).on("hidden.bs.modal", function( e ) {
				if( e.target.className == 'bootbox modal fade') {
					$('#' + (table == 'detail' ? 'COMMODITIES' : 'IMAGES' ) ).load('exp_list_table.php?pw=Emmental&table=' + table + '&code=' + code, function() {
						
						$.ajax({
							async: false,
							url: 'exp_shipment_totals.php',
							data: { pw: 'Velocity', code: code },
							dataType: "json",
							success: function(data) {
								console.log('exp_list_table.php update2', data.DANGEROUS_GOODS);
								$('input#PALLETS').val(data.PALLETS);
								$('input#PIECES').val(data.PIECES);
								$('input#WEIGHT').val(data.WEIGHT);
								$('input#DANGEROUS_GOODS').prop('checked', data.DANGEROUS_GOODS);
								$('input#UN_NUMBERS').val(data.UN_NUMBERS);
							}
						});
					});
					
				}
			});
			
		function process_edit() {
			//var code = $('#SHIPMENT_CODE').val();
			console.log('process_edit, code = ', code);

			$('.my-switch').bootstrapToggle({
				on: 'on',
				off: 'off',
				onstyle: 'success'
			});
			
			$('input[type=number]:not([allowneg])').numericInput({ allowFloat: true });
			$('input[type=number][allowneg]').numericInput({ allowFloat: true, allowNegative: true });

			function update_commodity_edit() {
				$.ajax({
					url: 'exp_lookup_pu.php',
					data: {
						CODE: $('#COMMODITY').val(),
						TYPE: 'commodity',
						PW: 'Mashed'
					},
					dataType: "json",
					success: function(data) {
						$('select#PIECES_UNITS').val(data['PIECES_UNITS']).change();
						$('input#UN_NUMBER').val(data['UN_NUMBER']).change();
						$('input#TEMP_CONTROLLED').val(data['TEMP_CONTROLLED']).change();
						console.log('update_commodity_edit, DANGEROUS = ', data['DANGEROUS']==1 );
						$('input#DANGEROUS_GOODS').prop('checked', (data['DANGEROUS'] == 1)).change();

						console.log('update_commodity_edit: TEMP_CONTROLLED = ', data['TEMP_CONTROLLED'], typeof(data['TEMP_CONTROLLED']));
						if( data['TEMP_CONTROLLED'] == 1 ) {
							console.log('update_commodity_edit: set TEMPERATURE = ', data['TEMPERATURE']);
							$('input#TEMPERATURE').val(data['TEMPERATURE']).change();
							$('select#TEMPERATURE_UNITS').val(data['TEMPERATURE_UNITS']).change();
							$('.temp').prop('hidden',false);
						} else {
							console.log('update_commodity_edit: clear TEMPERATURE');
							$('input#TEMPERATURE').val('null').change();
							$('select#TEMPERATURE_UNITS').val('null').change();
							$('.temp').prop('hidden', 'hidden');
						}
					}
				});
			}
			
			$('select#COMMODITY').off(); //!important - remove previous event handlers
			$('select#COMMODITY').on('change', function(event) {
				update_commodity_edit();
			});
			
			console.log($('input#TEMP_CONTROLLED').val(), typeof($('input#TEMP_CONTROLLED').val()));
			if( $('input#TEMP_CONTROLLED').val() == "1" ) {
				$('.temp').prop('hidden',false);
			} else {
				$('.temp').prop('hidden', 'hidden');
			}

			$('#edit_detail').off(); //!important - remove previous event handlers
			$( "#edit_detail" ).submit(function( event ) {
				event.preventDefault();  //prevent form from submitting
				console.log('edit_detail submit'); 
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
							console.log('exp_list_table.php update3', data.DANGEROUS_GOODS);
							$('input#PALLETS').val(data.PALLETS);
							$('input#PIECES').val(data.PIECES);
							$('input#WEIGHT').val(data.WEIGHT);
							$('input#DANGEROUS_GOODS').prop('checked', data.DANGEROUS_GOODS);
							$('input#UN_NUMBERS').val(data.UN_NUMBERS);
						}
					});
				});
			});
		
		}
		
		function process_add() {
			console.log('process_add, code = ', code);
			
			$('.my-switch').bootstrapToggle({
				on: 'on',
				off: 'off',
				onstyle: 'success'
			});
			
			$('input[type=number]:not([allowneg])').numericInput({ allowFloat: true });
			$('input[type=number][allowneg]').numericInput({ allowFloat: true, allowNegative: true });

			function update_commodity_add() {
				$.ajax({
					url: 'exp_lookup_pu.php',
					data: {
						CODE: $('#COMMODITY').val(),
						TYPE: 'commodity',
						PW: 'Mashed'
					},
					dataType: "json",
					success: function(data) {
						$('select#PIECES_UNITS').val(data['PIECES_UNITS']).change();
						$('input#UN_NUMBER').val(data['UN_NUMBER']).change();
						$('input#TEMP_CONTROLLED').val(data['TEMP_CONTROLLED']).change();
						//$('input#DANGEROUS_GOODS').val(data['DANGEROUS']).change();
						console.log('update_commodity_add, DANGEROUS = ', data['DANGEROUS']==1 );
						$('input#DANGEROUS_GOODS').prop('checked', (data['DANGEROUS'] == 1)).change();

						console.log('update_commodity_add: TEMP_CONTROLLED = ', data['TEMP_CONTROLLED'], typeof(data['TEMP_CONTROLLED']));
						if( data['TEMP_CONTROLLED'] == 1 ) {
							console.log('update_commodity_add: set TEMPERATURE = ', data['TEMPERATURE']);
							$('input#TEMPERATURE').val(data['TEMPERATURE']).change();
							$('select#TEMPERATURE_UNITS').val(data['TEMPERATURE_UNITS']).change();
							$('.temp').prop('hidden',false);
						} else {
							console.log('update_commodity_add: clear TEMPERATURE');
							$('input#TEMPERATURE').val('null').change();
							$('select#TEMPERATURE_UNITS').val('null').change();
							$('.temp').prop('hidden', 'hidden');
						}
					}
				});
			}
			
			$('select#COMMODITY').off(); //!important - remove previous event handlers
			$('select#COMMODITY').on('change', function(event) {
				update_commodity_add();
			});

			$('#add_detail').off(); //!important - remove previous event handlers
			$("#add_detail").submit(function( event ) {
				event.preventDefault();  //prevent form from submitting
				
				console.log('add_detail submit'); 
				var data = $("#add_detail :input").serializeArray();
				$.post("exp_adddetail.php", data, function( data ) {
					$('a').off('click.add_detail');
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
							console.log('exp_list_table.php update4', data.DANGEROUS_GOODS);
							$('input#PALLETS').val(data.PALLETS);
							$('input#PIECES').val(data.PIECES);
							$('input#WEIGHT').val(data.WEIGHT);
							$('input#DANGEROUS_GOODS').prop('checked', data.DANGEROUS_GOODS);
							$('input#UN_NUMBERS').val(data.UN_NUMBERS);
						}
					});
				});
				
			});
			
			update_commodity_add();
				
		}


			//alert('exp_list_table');
		});
	//--></script>

