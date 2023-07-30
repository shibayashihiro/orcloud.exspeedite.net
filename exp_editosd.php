<?php 
// Set flag that this is a parent file
define( '_STS_INCLUDE', 1 );

// Setup Session
require_once( "include/sts_session_setup.php" );
define( '_STS_JQUERY_FORM', 1 );	// To use JQuery.Form

require_once( "include/sts_config.php" );
$sts_debug = (isset($_GET['debug'])  || isset($_POST['debug'])) && in_group( EXT_GROUP_DEBUG );
//$sts_debug = isset($_POST) && count($_POST) > 0 ;
require_once( "include/sts_session_class.php" );

$my_session = sts_session::getInstance( $exspeedite_db, $sts_debug );
$my_session->access_check( $sts_table_access[OSD_TABLE] );	// Make sure we should be here

$sts_subtitle = "Edit OS&D Report";
require_once( "include/header_inc.php" );
require_once( "include/navbar_inc.php" );

require_once( "include/sts_form_class.php" );
require_once( "include/sts_osd_class.php" );

$osd_table = new sts_osd($exspeedite_db, $sts_debug);
$osd_form = new sts_form( $sts_form_editosd_form, $sts_form_edit_osd_fields, $osd_table, $sts_debug);

if( isset($_POST) && count($_POST) > 0 ) {		// Process completed form
	$result = $osd_form->process_edit_form();
	
	if( $result ) {
		if( $sts_debug ) die; // So we can see the results
		reload_page ( "exp_editosd.php?CODE=".$_POST[$osd_table->primary_key] );
	}
}

?>
<div class="container-full theme-showcase" role="main">

<div class="well  well-md">
<?php

if( isset($_POST) && count($_POST) > 0 && $result == false ) {	// If error occured
	echo "<p><strong>Error:</strong> ".$osd_table->error()."</p>";
	echo $osd_form->render( $_POST );
} else if( isset($_GET['CODE']) ) {
	$result = $osd_table->fetch_rows($osd_table->primary_key." = ".$_GET['CODE']);
	echo $osd_form->render( $result[0] );
} else {
	echo $osd_form->render();
}

?>
	<div class="row well well-sm" id="IMAGES">
	</div>
</div>
</div>

	<div class="modal fade bs-example-modal-sm" tabindex="-1" role="dialog" aria-labelledby="mySmallModalLabel" aria-hidden="true" id="editosd_modal">
	  <div class="modal-dialog">
		<div class="modal-content">
		<div class="modal-header">
			<h4 class="modal-title" id="myModalLabel"><span class="text-success"><strong>Saving...</strong></span></h4>
		</div>
		<div class="modal-body">
			<p class="text-center"><img src="images/loading.gif" alt="loading" width="125" height="125" /></p>
		</div>
		</div>
		</div>
	</div>

	<script language="JavaScript" type="text/javascript"><!--
		
		// Sets the client info
		$(document).ready( function () {
			var code = <?php echo $_GET['CODE']; ?>;
			var detail_units = new Array();
			//console.log($("#addosd :input disabled"));
			
			$('#IMAGES').load('exp_list_table.php?pw=Emmental&table=image&code=' + code);
		
			$( "#editosd" ).submit(function( event ) {
				event.preventDefault();  //prevent form from submitting
				$('#editosd_modal').modal({
					container: 'body'
				});
				var data = $("#editosd :input").serializeArray();
				
				console.log(data);
				$.post("exp_editosd.php", data, function( data ) {
					//alert('Saved changes');
					$('#editosd_modal').modal('hide');
					window.addosd_HAS_CHANGED = false;	// depreciated
					$('a').off('click.editosd');

				});
			});
			
			function update_accepted() {
				if( $('#OSD_TYPE').val() == 'over' ) {
					$('#ITEMS_ACCEPTED').val( parseInt($('#DETAIL_UNITS').html()));
				} else {
					if( parseInt($('#ITEMS_REJECTED').val()) > parseInt($('#DETAIL_UNITS').html()) )
						$('#ITEMS_REJECTED').val( $('#DETAIL_UNITS').html() );
					var rejected = 0;
					if( $('#ITEMS_REJECTED').val() != '' )
						rejected = parseInt($('#ITEMS_REJECTED').val());
					
					$('#ITEMS_ACCEPTED').val( parseInt($('#DETAIL_UNITS').html()) - rejected );
				}
			}
		
			function update_detail_units() {
				$('#DETAIL_UNITS').html(detail_units[$('#DETAIL').val()]);
				update_accepted();
			}
		
			function update_shipment() {
				$.ajax({
					async: false,
					url: 'exp_osd_lookup.php',
					data: { code: 'Smashed', shipment: $('#SHIPMENT').val() },
					dataType: "json",
					success: function(data) {
						$('input#LOAD_CODE').val(data[0].LOAD_CODE);
						$('#SHIP_FROM').html(data[0].SHIPPER_NAME + '<br>' + 
							data[0].SHIPPER_CITY + ', ' + data[0].SHIPPER_STATE);
						$('#SHIP_TO').html(data[0].CONS_NAME + '<br>' + 
							data[0].CONS_CITY + ', ' + data[0].CONS_STATE);
						$('#BILLTO').html(data[0].BILLTO_NAME + '<br>' + 
							data[0].BILLTO_CITY + ', ' + data[0].BILLTO_STATE);
						$('#TRAILER').html(data[0].TRAILER_NUMBER);
						$('#DRIVER').html(data[0].DRIVER_NAME);
						$('#CARRIER').html(data[0].CARRIER_NAME);
						
						//console.log(data.DETAIL);
						var selected_detail = $('input#DETAIL').val();
						var details = new Array();
						detail_units = new Array();
						$.each(data.DETAIL, function(index, value){
							var tmp = '<option value="' + value.DETAIL_CODE + '"';
							if( selected_detail == value.DETAIL_CODE )
								tmp += ' selected';
							tmp += '>' + value.COMMODITY_NAME +
							' (' + value.PALLETS + ' pallets, ' + value.PIECES + ' pieces)</option>';
							details.push( tmp );
							detail_units[value.DETAIL_CODE] = value.PIECES;
						});
						$('#DETAIL_MENU').html('<select class="form-control" name="DETAIL" id="DETAIL" >' +
							details.join() + '</select>');
						update_detail_units();
					}
				});
			}
			
			$('select#DETAIL').on('change', function(event) {
				update_detail_units();
			});
			
			$('#ITEMS_REJECTED').on('keyup', function(event) {
				update_accepted();
			});
			
			$('#OSD_TYPE').on('change', function(event) {
				update_accepted();
			});
			
			$('select#SHIPMENT').on('change', function(event) {
				update_shipment();
			});
			
			update_shipment();

		});
	//--></script>


<?php

require_once( "include/footer_inc.php" );
?>
		

