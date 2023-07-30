<?php 

// $Id: exp_editcarrier.php 4350 2021-03-02 19:14:52Z duncan $
// Edit Carrier

// Set flag that this is a parent file
define( '_STS_INCLUDE', 1 );

// Setup Session
require_once( "include/sts_session_setup.php" );

require_once( "include/sts_config.php" );
$sts_debug = (isset($_GET['debug'])  || isset($_POST['debug'])) && in_group( EXT_GROUP_DEBUG );
require_once( "include/sts_session_class.php" );

$my_session = sts_session::getInstance( $exspeedite_db, $sts_debug );
$my_session->access_check( $sts_table_access[CARRIER_TABLE] );	// Make sure we should be here

$sts_subtitle = "Edit Carrier";
require_once( "include/header_inc.php" );
require_once( "include/navbar_inc.php" );

require_once( "include/sts_result_class.php" );
require_once( "include/sts_form_class.php" );
require_once( "include/sts_carrier_class.php" );
require_once( "include/sts_contact_info_class.php" );
require_once( "include/sts_attachment_class.php" );
require_once( "include/sts_license_class.php" );
require_once( "include/sts_setting_class.php" );
require_once( "include/sts_user_log_class.php" );

$setting_table = sts_setting::getInstance($exspeedite_db, $sts_debug);
$sts_export_sage50 = $setting_table->get( 'api', 'EXPORT_SAGE50_CSV' ) == 'true';
$sts_attachments = $setting_table->get( 'option', 'ATTACHMENTS_ENABLED' ) == 'true';
$sts_expire_carriers = $setting_table->get( 'option', 'EXPIRE_CARRIERS_ENABLED' ) == 'true';
$sts_edi_enabled = $setting_table->get( 'api', 'EDI_ENABLED' ) == 'true';

//! SCR# 639 - Restrict access to insurance
$sts_restrict_ins = $setting_table->get( 'option', 'RESTRICT_INSURANCE' ) == 'true';
$sts_restrict_currency = $setting_table->get( 'option', 'RESTRICT_CURRENCY' ) == 'true';

if( ! $sts_export_sage50 ) {
	$match = preg_quote('<!-- SAGE50_1 -->').'(.*)'.preg_quote('<!-- SAGE50_2 -->');
	$sts_form_editcarrier_form['layout'] = preg_replace('/'.$match.'/s', '',
		$sts_form_editcarrier_form['layout'], 1);	
} else {
		$sts_form_editcarrier_form['buttons'] = array( 
		array( 'label' => 'Sage 50', 'link' => 'exp_export_csv.php?pw=GoldUltimate&type=carrier&code=%CARRIER_CODE%',
		'button' => 'danger', 'icon' => '<span class="glyphicon glyphicon-arrow-right"></span>',
		'restrict' => EXT_GROUP_SAGE50 )
		);
}

//! SCR# 639 - Restrict access to insurance
if( $sts_restrict_ins && ! $my_session->in_group(EXT_GROUP_ADMIN) ) {
	$sts_form_edit_carrier_fields['GENERAL_LIAB_INS']['extras'] = 'disabled readonly';
	$sts_form_edit_carrier_fields['AUTO_LIAB_INS']['extras'] = 'disabled readonly';
	$sts_form_edit_carrier_fields['CARGO_LIAB_INS']['extras'] = 'disabled readonly';
	$sts_form_edit_carrier_fields['LIABILITY_DATE']['extras'] = 'disabled readonly';
	$sts_form_edit_carrier_fields['AUTO_LIAB_DATE']['extras'] = 'disabled readonly';
	$sts_form_edit_carrier_fields['CARGO_LIAB_DATE']['extras'] = 'disabled readonly';
}

if( $sts_restrict_currency && ! $my_session->in_group(EXT_GROUP_ADMIN) ) {
	$sts_form_edit_carrier_fields['CURRENCY_CODE']['extras'] = 'disabled readonly';
}


if( ! $sts_edi_enabled ) {
	$match = preg_quote('<!-- EDI_1 -->').'(.*)'.preg_quote('<!-- EDI_2 -->');
	$sts_form_editcarrier_form['layout'] = preg_replace('/'.$match.'/s', '',
		$sts_form_editcarrier_form['layout'], 1);	
}

$carrier_table = sts_carrier::getInstance($exspeedite_db, $sts_debug);
$carrier_form = new sts_form($sts_form_editcarrier_form, $sts_form_edit_carrier_fields, $carrier_table, $sts_debug);

if( isset($_POST) && isset($_POST['RESULT_SAVE_CODE']) )	// sts_result saved the code
	$_GET['CODE'] = $_POST['RESULT_SAVE_CODE'];
else if( isset($_POST) && count($_POST) > 0 ) {		// Process completed form
	$result = $carrier_form->process_edit_form();

	if( $result ) {
		if( $carrier_form->last_changes() != '' ) {
			$user_log_table = sts_user_log::getInstance($exspeedite_db, $sts_debug);
			if( isset($_POST['CARRIER_CODE']) && isset($_POST['CARRIER_NAME']))
				$carrier = '<a href="exp_editcarrier.php?CODE='.$_POST['CARRIER_CODE'].'">'.$_POST['CARRIER_NAME'].'</a>: ';
			else if( isset($_POST['CARRIER_CODE']) )
				$carrier = 'carrier# '.$_POST['CARRIER_CODE'].': ';
			else
				$carrier = 'unknown carrier: ';
			
			$entry = $user_log_table->log_event('profiles', 'Edit carrier: '.$carrier.$carrier_form->last_changes());
			if( $entry > 0 ) {
				$email_type = 'carrierins';
				$email_code = $entry;
				require_once( "exp_spawn_send_email.php" );
			}
		}

		if( $sts_debug ) die; // So we can see the results
		if( isset($_POST["saveadd"]) )
			reload_page ( "exp_addcarrier.php" );
	}
}

?>
<div class="container-full theme-showcase" role="main">

<div class="well  well-lg">
<?php

if( isset($value) && is_array($value) && $result == false ) {	// If error occured
	echo "<p><strong>Error:</strong> ".$carrier_table->error()."</p>";
	echo $carrier_form->render( $value );
} else if( isset($_GET['CODE']) ) {
	$result = $carrier_table->fetch_rows("CARRIER_CODE = ".$_GET['CODE']);
	echo $carrier_form->render( $result[0] );
}

if( isset($_GET['CODE']) ) {
?>
<!-- Nav tabs -->
<ul class="nav nav-tabs">
  <li class="active"><a href="#contact" data-toggle="tab">Contact Info</a></li>
<?php if( $sts_attachments ) { ?>
  <li><a href="#attach" data-toggle="tab">Attachments</a></li>
<?php } ?>
  <li><a href="#loads" data-toggle="tab">Loads</a></li>
</ul>

<!-- Tab panes -->
<div class="tab-content">
  <div role="tabpanel" class="tab-pane active" id="contact">
<?php
	$contact_info_table = new sts_contact_info($exspeedite_db, $sts_debug);
	$rslt = new sts_result( $contact_info_table, "CONTACT_CODE = ".$_GET['CODE']." AND CONTACT_SOURCE = 'carrier'", $sts_debug );
	echo $rslt->render( $sts_result_contact_info_layout, $sts_result_contact_info_edit, '?SOURCE=carrier&CODE='.$_GET['CODE'].'&PARENT_NAME='.urlencode($result[0]['CARRIER_NAME']) );

if( $sts_attachments ) {
?>
  </div>
  <div role="tabpanel" class="tab-pane" id="attach">
<?php
	
	$attachment_table = sts_attachment::getInstance($exspeedite_db, $sts_debug);
	$rslt2 = new sts_result( $attachment_table, "SOURCE_CODE = ".$_GET['CODE']." AND SOURCE_TYPE = 'carrier'", $sts_debug );
	echo $rslt2->render( $sts_result_attachment_layout, $sts_result_attachment_edit, '?SOURCE_TYPE=carrier&SOURCE_CODE='.$_GET['CODE'] );
}

?>

  </div>
  <div role="tabpanel" class="tab-pane" id="loads">
<?php

// !List Loads Section
echo '<a name="loads"></a>';
require_once( "include/sts_load_class.php" );
require_once( "include/sts_office_class.php" );

$office_table = sts_office::getInstance($exspeedite_db, $sts_debug);

$sts_length_menu = $setting_table->get( 'option', 'LENGTH_MENU' );
$sts_po_fields = $setting_table->get( 'option', 'PO_FIELDS' ) == 'true';
$multi_company = $office_table->multi_company();

//! Restrict list to this client
if( $result[0]["CARRIER_TYPE"] == 'lumper' ) {
	$match = 'LUMPER = '.$_GET['CODE'];
	$layout = $sts_result_loads_lumper_layout;
} else {
	$match = 'CARRIER = '.$_GET['CODE'];
	$layout = $sts_result_loads_carrier_layout;
}

//! SCR# 247 - restrict list to offices available
if( $multi_company && ! in_group( EXT_GROUP_ADMIN ) ) {
	$match = ($match <> '' ? $match." AND " : "")."OFFICE_CODE IN (".implode(', ', array_keys($_SESSION['EXT_USER_OFFICES'])).")";
}

$load_table = sts_load::getInstance($exspeedite_db, $sts_debug);
$rslt = new sts_result( $load_table, $match, $sts_debug );
echo $rslt->render( $layout, $sts_result_loads_carrier_view, false, false );
?>
	</div>
 </div>
<?php


}

?>
</div>
</div>

	<script language="JavaScript" type="text/javascript"><!--
		
		$(document).ready( function () {
			var carrier = <?php echo $_GET['CODE']; ?>;
			
			$('#EXP_CONTACT_INFO').dataTable({
		        //"bLengthChange": false,
		        "bFilter": true,
		        "bSort": true,
		        "bInfo": false,
				//"bAutoWidth": false,
				//"bProcessing": true, 
				"sScrollX": "100%",
				"sScrollY": "300px",
				//"sScrollXInner": "120%",
		        "lengthMenu": [<?php echo isset($sts_length_menu) ? $sts_length_menu : '25, 50, 100, 250'; ?>],
				"bPaginate": true,
				"bScrollCollapse": true,
				"bSortClasses": false		
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
						d.match = encodeURIComponent("<?php echo $match; ?>");
					}

				},
				order: [[0, 'desc']],
				"columns": [
					<?php
						foreach( $layout as $key => $row ) {
							if( $row["format"] <> 'hidden')
								echo '{ "data": "'.$key.'", "searchable": true, "orderable": true'.
								(isset($row["align"]) ? ',"className": "text-'.$row["align"].'"' : '').
									(isset($row["length"]) ? ', "width": "'.$row["length"].'px"' : '').
									(isset($row["format"]) && $row["format"] == 'hidden' ? ', "visible": false' : '').' },
						';
						}
					?>
				]
		
			};

			<?php if( $sts_expire_carriers ) { ?>
			function check_carrier() {
				var url = 'exp_check_asset.php?code=Headphone&carrier='+carrier;
				
				$.get( url, function( data ) {
					$( "#warnings" ).html( data );
				});
			}
			
			check_carrier();
			<?php } ?>

			function update_ctpat_svi() {
				console.log( $('#CTPAT_CERTIFIED').prop("checked") );
				if( $('#CTPAT_CERTIFIED').prop("checked") ) {
					$('#CTPAT_SVI').prop('hidden', false);
				} else {
					$('#CTPAT_SVI').prop('hidden', 'hidden');
				}
			}

		    $('#CTPAT_CERTIFIED').on('change', function() {
		    	update_ctpat_svi();
		    });
		    
		    update_ctpat_svi();
			$('a[data-toggle="tab"]').on( 'shown.bs.tab', function (e) {
				$.fn.dataTable.tables( {visible: true, api: true} ).columns.adjust();
			} );

			$('a[data-toggle="tab"]').on("shown.bs.tab", function (e) {
			    var id = $(e.target).attr("href");
			    localStorage.setItem('ecaselectedTab', id)
			});
			
			var selectedTab = localStorage.getItem('ecaselectedTab');
			if (selectedTab != null) {
			    $('a[data-toggle="tab"][href="' + selectedTab + '"]').tab('show');
			}

			var myTable = $('#EXP_LOAD').DataTable(opts);
		});
	//--></script>


<?php

require_once( "include/footer_inc.php" );
?>

