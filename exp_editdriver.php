<?php 

// $Id: exp_editdriver.php 4350 2021-03-02 19:14:52Z duncan $
// Edit Driver

// Set flag that this is a parent file
define( '_STS_INCLUDE', 1 );

// Setup Session
require_once( "include/sts_session_setup.php" );

require_once( "include/sts_config.php" );
$sts_debug = (isset($_GET['debug'])  || isset($_POST['debug'])) && in_group( EXT_GROUP_DEBUG );
require_once( "include/sts_session_class.php" );
if( isset($_GET['bang']) ) unset( $_SESSION['EXT_USERNAME'] );

$my_session = sts_session::getInstance( $exspeedite_db, $sts_debug );
$my_session->access_check( $sts_table_access[DRIVER_TABLE] );	// Make sure we should be here

$sts_subtitle = "Edit Driver";
require_once( "include/header_inc.php" );
require_once( "include/navbar_inc.php" );

require_once( "include/sts_result_class.php" );
require_once( "include/sts_form_class.php" );
require_once( "include/sts_driver_class.php" );
require_once( "include/sts_contact_info_class.php" );
require_once( "include/sts_license_class.php" );
require_once( "include/sts_setting_class.php" );
require_once( "include/sts_attachment_class.php" );
require_once( "include/sts_load_class.php" );
require_once( "include/sts_office_class.php" );
require_once( "include/sts_user_log_class.php" );

$setting_table = sts_setting::getInstance($exspeedite_db, $sts_debug);
$sts_attachments = $setting_table->get( 'option', 'ATTACHMENTS_ENABLED' ) == 'true';
$kt_api_key = $setting_table->get( 'api', 'KEEPTRUCKIN_KEY' );
$kt_enabled = ! empty($kt_api_key);
$insp_title = $setting_table->get( 'option', 'INSPECTION_REPORT_TITLE' );

//! This removes some fields not used for QuickBooks Online
$sts_qb_online = $setting_table->get( 'api', 'QUICKBOOKS_ONLINE' ) == 'true';
if( ! $sts_qb_online ) {
	$match = preg_quote('<!-- CHECK_NAME1 -->').'(.*)'.preg_quote('<!-- CHECK_NAME2 -->');
	$sts_form_editdriver_form['layout'] = preg_replace('/'.$match.'/s', '',
		$sts_form_editdriver_form['layout'], 1);	
}

//! SCR# 427 - hide driver type
$driver_types = $setting_table->get("option", "DRIVER_TYPES") == 'true';
if( ! $driver_types ) {
	$match = preg_quote('<!-- DRIVER_TYPES1 -->').'(.*)'.preg_quote('<!-- DRIVER_TYPES2 -->');
	$sts_form_editdriver_form['layout'] = preg_replace('/'.$match.'/s', '',
		$sts_form_editdriver_form['layout'], 1);	
}

//! Check for multi-company
$multi_company = ($setting_table->get("option", "MULTI_COMPANY") == 'true');
if( ! $multi_company ) {
	$match = preg_quote('<!-- CC01 -->').'(.*)'.preg_quote('<!-- CC02 -->');
	$sts_form_editdriver_form['layout'] = preg_replace('/'.$match.'/s', '',
		$sts_form_editdriver_form['layout'], 1);	
}

//! SCR# 646 - Random feature
if( ! in_group( EXT_GROUP_RANDOM ) ) {
	$match = preg_quote('<!-- RANDOM1 -->').'(.*)'.preg_quote('<!-- RANDOM2 -->');
	$sts_form_editdriver_form['layout'] = preg_replace('/'.$match.'/s', '%DRIVER_RANDOM%',
		$sts_form_editdriver_form['layout'], 1);
	$sts_form_edit_driver_fields['DRIVER_RANDOM']['format'] = 'hidden';
} else {
	//! SCR# 651 - need HR + Random to edit
	if( ! in_group( EXT_GROUP_HR ) ) {
		$sts_form_edit_driver_fields['DRIVER_RANDOM']['extras'] = 'readonly disabled';
	}
}


//! Restrict certain fields
$edit_driver_restrictions = ($setting_table->get("option", "EDIT_DRIVER_RESTRICTIONS_ENABLED") == 'true');
if( $edit_driver_restrictions && ! in_group( EXT_GROUP_HR ) ) {
	$sts_form_edit_driver_fields['PHYSICAL_DUE']['extras'] = 'readonly';
	//! SCR# 280 - hide SSN
	$match = preg_quote('<!-- SSN1 -->').'(.*)'.preg_quote('<!-- SSN2 -->');
	$sts_form_editdriver_form['layout'] = preg_replace('/'.$match.'/s', '',
		$sts_form_editdriver_form['layout'], 1);	
}

if( $kt_enabled ) {
	$sts_form_editdriver_form['buttons'][] = array( 'label' => 'Violations', 'link' => 'exp_kt_hos_violations.php?pw=PalaFer&code=%DRIVER_CODE%',
		'button' => 'default', 'icon' => '<img src="images/keeptruckin2.png" alt="keeptruckin" height="18">' );
}

$driver_table = sts_driver::getInstance($exspeedite_db, $sts_debug);
$driver_form = new sts_form($sts_form_editdriver_form, $sts_form_edit_driver_fields, $driver_table, $sts_debug);

if( isset($_POST) && isset($_POST['RESULT_SAVE_CODE']) )	// sts_result saved the code
	$_GET['CODE'] = $_POST['RESULT_SAVE_CODE'];
else if( isset($_POST) && count($_POST) > 0 ) {		// Process completed form
	$check = $driver_table->fetch_rows("DRIVER_CODE = ".$_POST['DRIVER_CODE'], "DRIVER_RANDOM");
	$result = $driver_form->process_edit_form();
	
	if( in_group( EXT_GROUP_RANDOM ) && is_array($check)
		&& count($check) == 1 && isset($check[0]["DRIVER_RANDOM"] ) ) {
		$previous = date("m/d/Y", strtotime($check[0]["DRIVER_RANDOM"]));
		if( $previous != $_POST['DRIVER_RANDOM'] ) {
			$user_log_table = sts_user_log::getInstance($exspeedite_db, $sts_debug);
			$user_log_table->log_event('admin', 'Edit driver '.$_POST['FIRST_NAME'].' '.$_POST['LAST_NAME'].'('.$_POST['DRIVER_CODE'].') change random from '.$previous.' to '.$_POST['DRIVER_RANDOM']);
		}

	}
	
	if( $result ) {
		if( $sts_debug ) die; // So we can see the results
		if( isset($_POST["saveadd"]) )
			reload_page ( "exp_adddriver.php" );
	}
}

?>
<div class="container-full theme-showcase" role="main">

<div class="well  well-lg">
<?php

if( isset($value) && is_array($value) && $result == false ) {	// If error occured
	echo "<p><strong>Error:</strong> ".$driver_table->error()."</p>";
	echo $driver_form->render( $value );
} else if( isset($_GET['CODE']) ) {
	$result = $driver_table->fetch_rows("DRIVER_CODE = ".$_GET['CODE']);
	if( isset($result) && is_array($result) && count($result) > 0 ) {
		echo $driver_form->render( $result[0] );

if( isset($_GET['CODE']) ) {
?>
<!-- Nav tabs -->
<ul class="nav nav-tabs">
  <li class="active"><a href="#contact" data-toggle="tab">Contact Info</a></li>
  <li id="license_tab"><a href="#license" data-toggle="tab">Licenses</a></li>
<?php if( $sts_attachments ) { ?>
  <li><a href="#attach" data-toggle="tab">Attachments</a></li>
<?php } ?>
  <li id="loads_tab"><a href="#loads" data-toggle="tab">Loads</a></li>
  <li><a href="#insp" role="tab" data-toggle="tab"><?php echo $insp_title; ?>s</a></li>
</ul>

<!-- Tab panes -->
<div class="tab-content">
  <div role="tabpanel" class="tab-pane active" id="contact">
<?php
$contact_info_table = new sts_contact_info($exspeedite_db, $sts_debug);
$rslt = new sts_result( $contact_info_table, "CONTACT_CODE = ".$_GET['CODE']." AND CONTACT_SOURCE = 'driver'", $sts_debug );
echo $rslt->render( $sts_result_contact_info_layout, $sts_result_contact_info_edit, '?SOURCE=driver&CODE='.$_GET['CODE'].'&PARENT_NAME='.urlencode($result[0]['FIRST_NAME'].' '.$result[0]['LAST_NAME']) );

?>
  </div>
  <div role="tabpanel" class="tab-pane" id="license">
<?php
	
$license_table = new sts_license($exspeedite_db, $sts_debug);
$rslt2 = new sts_result( $license_table, "CONTACT_CODE = ".$_GET['CODE']." AND CONTACT_SOURCE = 'driver'", $sts_debug );
echo $rslt2->render( $sts_result_license_layout, $sts_result_license_edit, '?SOURCE=driver&CODE='.$_GET['CODE'].'&PARENT_NAME='.urlencode($result[0]['FIRST_NAME'].' '.$result[0]['LAST_NAME']) );

if( $sts_attachments ) {
?>
  </div>
  <div role="tabpanel" class="tab-pane" id="attach">
<?php
	
	$attachment_table = sts_attachment::getInstance($exspeedite_db, $sts_debug);
	$rslt2 = new sts_result( $attachment_table, "SOURCE_CODE = ".$_GET['CODE']." AND SOURCE_TYPE = 'driver'", $sts_debug );
	echo $rslt2->render( $sts_result_attachment_layout, $sts_result_attachment_edit, '?SOURCE_TYPE=driver&SOURCE_CODE='.$_GET['CODE'] );
}
?>

  </div>
  <div role="tabpanel" class="tab-pane" id="loads">
<?php

// !List Loads Section
echo '<a name="loads"></a>';

$office_table = sts_office::getInstance($exspeedite_db, $sts_debug);

$setting_table = sts_setting::getInstance($exspeedite_db, $sts_debug);
$sts_length_menu = $setting_table->get( 'option', 'LENGTH_MENU' );
$sts_po_fields = $setting_table->get( 'option', 'PO_FIELDS' ) == 'true';
$multi_company = $office_table->multi_company();

//! Restrict list to this client
//! SCR# 557 - Edit Driver - drivers’ loads are not listed any more
$match1 = 'DRIVER = '.$_GET['CODE'];

//! SCR# 247 - restrict list to offices available
if( $multi_company && ! in_group( EXT_GROUP_ADMIN ) ) {
	$match1 = ($match <> '' ? $match." AND " : "")."OFFICE_CODE IN (".implode(', ', array_keys($_SESSION['EXT_USER_OFFICES'])).")";
}

$load_table = sts_load::getInstance($exspeedite_db, $sts_debug);
$rslt = new sts_result( $load_table, $match1, $sts_debug );
echo $rslt->render( $sts_result_loads_driver2_layout, $sts_result_loads_carrier_view, false, false );
?>
	</div>
 	<div role="tabpanel" class="tab-pane" id="insp">
<?php


$sts_result_insp_report_edit['title'] = '<span class="glyphicon glyphicon-wrench"></span> '.$insp_title.'s';

$sts_result_insp_report_edit['rowbuttons'] = array(
		array( 'url' => 'exp_viewinsp_report.php?REPORT=', 'key' => 'REPORT_CODE', 'label' => 'REPORT_DATE', 'tip' => 'View report ', 'icon' => 'glyphicon glyphicon-list-alt', 'target' => 'blank' ) );

$match = "REPORT_CODE IN (SELECT REPORT_CODE FROM EXP_INSP_REPORT_ITEM WHERE ITEM_TYPE='driver' AND DRIVER = ".$_GET['CODE'].")";


	$insp_table = sts_insp_report::getInstance($exspeedite_db, $sts_debug);
	$rslt3 = new sts_result( $insp_table, $match, $sts_debug );
	echo $rslt3->render( $sts_result_insp_report_all_layout, $sts_result_insp_report_edit );
?>
	</div>
</div>
<?php

}

	} else {
		echo '<h2 id="NOTFOUND">Driver #'.$_GET['CODE'].' Not Found</h2>';
		$shipment_found = false;
	}
}


?>
</div>
</div>

	<div class="modal fade bs-example-modal-lg" tabindex="-1" role="dialog" aria-labelledby="mySmallModalLabel" aria-hidden="true" id="kt_violations_modal">
	  <div class="modal-dialog modal-lg">
		<div class="modal-content">
		<div class="modal-header">
			 <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
			<h4 class="modal-title" id="myModalLabel"><span class="text-success"><strong><img src="images/keeptruckin.png" alt="keeptruckin" height="40"> HOS Violations</strong></span></h4>
		</div>
		<div class="modal-body">
			<p class="text-center"><img src="images/loading.gif" alt="loading" width="125" height="125" /></p>
		</div>
		</div>
		</div>
	</div>

	<script language="JavaScript" type="text/javascript"><!--
		$(document).ready( function () {
			var driver = <?php echo $_GET['CODE']; ?>;

			function hos_violations() {
				$('#kt_violations_modal').modal({
					container: 'body'
				});
				$('#kt_violations_modal .modal-body').html('<p class="text-center"><img src="images/loading.gif" alt="loading" width="125" height="125" /></p>');
				var url = 'exp_kt_hos_violations.php?pw=PalaFer&code='+driver;
				
				$.get( url, function( data ) {
					$('#kt_violations_modal .modal-body').html( data );
					$('#KT_VIOLATIONS').dataTable({
				        //"bLengthChange": false,
				        "bFilter": false,
				        stateSave: true,
				        "bSort": false,
				        "bInfo": true,
						"bAutoWidth": false,
						//"bProcessing": true, 
						"sScrollX": "100%",
						"sScrollY": ($(window).height() - 275) + "px",
						//"sScrollXInner": "120%",
						"bPaginate": false,
						"bScrollCollapse": true,
						"bSortClasses": false,
						//"order": [[ 1, "desc" ]],
						//"dom": "frtiS",
					});				
				});

			};

			$('#Violations').click(function(event) {
				event.preventDefault();
				if( ! ( $('select#DRIVER_TYPE').length && $('select#DRIVER_TYPE').val() != 'driver' ) ) {
					hos_violations();
				}
			});
			
			var opts = {
		        //"bLengthChange": false,
		        "bFilter": true,
		        stateSave: true,
		        "bSort": true,
		        "bInfo": true,
				//"bAutoWidth": false,
				//"bProcessing": true, 
				"sScrollX": "100%",
				"sScrollY": "400px",
				//"sScrollXInner": "120%",
		        "lengthMenu": [<?php echo isset($sts_length_menu) ? $sts_length_menu : '25, 50, 100, 250'; ?>],
		        <?php if( isset($_POST["EXP_LOAD_length"]) )
			        echo '"pageLength": '.$_POST["EXP_LOAD_length"].','; ?>
				"bPaginate": true,
				"bScrollCollapse": true,
				"bSortClasses": false,
				"processing": true,
				"serverSide": true,
				//"dom": "frtiS",
				"deferRender": true,
				"ajax": {
					"url": "exp_listloadcarrierajax.php",
					"data": function( d ) {
						d.match = encodeURIComponent("<?php echo isset($match1) ? $match1 : ''; ?>");
					}

				},
				order: [[0, 'desc']],
				"columns": [
					<?php
						foreach( $sts_result_loads_driver2_layout as $key => $row ) {
							if( $row["format"] <> 'hidden')
								echo '{ "data": "'.$key.'", "searchable": true, "orderable": true },
						';
						}
					?>
				]
		
			};

			var myTable = $('#EXP_LOAD').DataTable(opts);

			function update_oos() {
				//console.log('update_oos: ',$('select#DRIVER_TYPE').length, $('select#DRIVER_TYPE').val(), $('select#ISACTIVE').val());
				if( ! ( $('select#DRIVER_TYPE').length && $('select#DRIVER_TYPE').val() != 'driver' ) ) {
					if( $('select#ISACTIVE').val() == 'OOS' )
						$('#OOS').prop('hidden',false);
					else
						$('#OOS').prop('hidden', 'hidden');
				} else
					$('#OOS').prop('hidden', 'hidden');
			}
			
			function update_office( choice ) {
				if( $('select#OFFICE_CODE').length ) {
					var url = 'exp_office_menu.php?code=GoldBond&company='+$('select#COMPANY_CODE').val()+'&choice='+choice;
					$.get( url, function( data ) {
						$( "select#OFFICE_CODE" ).html( data );
					});
				}
			}

			function check_driver() {
				if( ! ( $('select#DRIVER_TYPE').length && $('select#DRIVER_TYPE').val() != 'driver' ) ) {
					var url = 'exp_check_asset.php?code=Headphone&driver='+driver;
					
					$.get( url, function( data ) {
						$( "#warnings" ).html( data );
					});
				}
			}
			
			$('select#ISACTIVE').on('change', function(event) {
				update_oos();
			});
			
			$('select#COMPANY_CODE').on('change', function(event) {
				update_office('NULL');
			});
			
			$('a[data-toggle="tab"]').on("shown.bs.tab", function (e) {
			    var id = $(e.target).attr("href");
			    localStorage.setItem('ecselectedTab', id)
			});
			
			var selectedTab = localStorage.getItem('ecselectedTab');
			if (selectedTab != null) {
			    $('a[data-toggle="tab"][href="' + selectedTab + '"]').tab('show');
			}

			$('a[data-toggle="tab"]').on( 'shown.bs.tab', function (e) {
				$.fn.dataTable.tables( {visible: true, api: true} ).columns.adjust();
			} );

			//update_oos();
			update_office($('select#OFFICE_CODE').val());
			check_driver();
			
			function update_fuel_cards() {
				if( ! ( $('select#DRIVER_TYPE').length && $('select#DRIVER_TYPE').val() != 'driver' ) ) {
					var url = "exp_driver_card.php?PW=Society&DRIVER=" + driver;
					$("#FUEL_CARDS").load(url, function() {
						$('a.delcard').on('click', function(event) {
							event.preventDefault();  //prevent form from submitting
							$.get($(this).attr('href'), function(data) {
								update_fuel_cards();
							});
						});
					});
				}

			}
			
			update_fuel_cards();

			function update_location() {
				if( ! ( $('select#DRIVER_TYPE').length && $('select#DRIVER_TYPE').val() != 'driver' ) ) {
					$.ajax({
						url: 'exp_asset_distance.php',
						data: {
							CODE: 'Switching',
							DRIVER: encodeURIComponent(driver),
						},
						dataType: "json",
						success: function(data) {
							if( typeof(data.STRING) != "undefined" ) {
								$('#LAST_LOCATION').html('<input type="text" class="form-control" value="' + data.STRING + '" readonly>');
							} else {
								$('#LAST_LOCATION').html('<input type="text" class="form-control" value="Unknown" readonly>');
							}
						}
					});
				}					
			}

			update_location();
		
			function update_type() {
				//console.log( 'update_type: ', $('select#DRIVER_TYPE').val() );
				if( $('select#DRIVER_TYPE').length && $('select#DRIVER_TYPE').val() != 'driver' ) {
					$('.driver_only').prop('hidden', 'hidden').change();					

					$('ul.nav-tabs a:first').tab('show');
					$('#license_tab').addClass('disabled').change();				
					$('#license_tab a').removeAttr('data-toggle').change();				
					$('#loads_tab').addClass('disabled').change();				
					$('#loads_tab a').removeAttr('data-toggle').change();
					$('#OOS').prop('hidden', 'hidden').change();			

					$('#Violations').addClass('disabled').change();
				} else {
					$('.driver_only').prop('hidden',false).change();

					$('#license_tab a').data('toggle','tab').change();
					$('#license_tab').removeClass('disabled').change();				
					$('#loads_tab a').data('toggle','tab').change();
					$('#loads_tab').removeClass('disabled').change();
					update_oos();			

					$('#Violations').removeClass('disabled').change();
				}
			}

			$(".disabled").click(function (e) {
		        e.preventDefault();
		        return false;
			});

			$('select#DRIVER_TYPE').change(function () {
				update_type();
			});
			
			update_type();
		});
	//--></script>

<?php

require_once( "include/footer_inc.php" );
?>

