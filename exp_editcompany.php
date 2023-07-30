<?php 
// Set flag that this is a parent file
define( '_STS_INCLUDE', 1 );

// Setup Session
require_once( "include/sts_session_setup.php" );

require_once( "include/sts_config.php" );
$sts_debug = (isset($_GET['debug'])  || isset($_POST['debug'])) && in_group( EXT_GROUP_DEBUG );
require_once( "include/sts_session_class.php" );

$my_session = sts_session::getInstance( $exspeedite_db, $sts_debug );
$my_session->access_check( EXT_GROUP_ADMIN );	// Make sure we should be here

$sts_subtitle = "Edit Company";
require_once( "include/header_inc.php" );
require_once( "include/navbar_inc.php" );

require_once( "include/sts_form_class.php" );
require_once( "include/sts_company_class.php" );
require_once( "include/sts_setting_class.php" );
require_once( "include/sts_result_class.php" );
require_once( "include/sts_company_tax_class.php" );
require_once( "include/sts_attachment_class.php" );

$setting_table = sts_setting::getInstance($exspeedite_db, $sts_debug);
$sts_export_sage50 = $setting_table->get( 'api', 'EXPORT_SAGE50_CSV' ) == 'true';
$sts_attachments = $setting_table->get( 'option', 'ATTACHMENTS_ENABLED' ) == 'true';

if( ! $sts_export_sage50 ) {
	$match = preg_quote('<!-- SAGE50_1 -->').'(.*)'.preg_quote('<!-- SAGE50_2 -->');
	$sts_form_editcompany_form['layout'] = preg_replace('/'.$match.'/s', '',
		$sts_form_editcompany_form['layout'], 1);	
}

$company_table = sts_company::getInstance($exspeedite_db, $sts_debug);
$company_form = new sts_form( $sts_form_editcompany_form, $sts_form_edit_company_fields, $company_table, $sts_debug);

if( isset($_POST) && count($_POST) > 0 ) {		// Process completed form
	$result = $company_form->process_edit_form();

	if( $result ) {
		if( $sts_debug ) die; // So we can see the results
		reload_page ( "exp_listcompany.php" );
	}
}

?>
<div class="container-full theme-showcase" role="main">

<div class="well  well-lg">
<?php

if( isset($value) && is_array($value) && $result == false ) {	// If error occured
	echo "<p><strong>Error:</strong> ".$company_table->error()."</p>";
	echo $company_form->render( $value );
} else if( isset($_GET['CODE']) ) {
	$result = $company_table->fetch_rows($company_table->primary_key." = ".$_GET['CODE']);
	echo $company_form->render( $result[0] );
}

if( isset($_GET['CODE']) ) {
?>
<!-- Nav tabs -->
<ul class="nav nav-tabs">
<?php if( $sts_export_sage50 ) { ?>
  <li class="active"><a href="#cdntax" data-toggle="tab">Canadian Tax Info</a></li>
<?php } ?>
<?php if( $sts_attachments ) { ?>
  <li><a href="#attach" data-toggle="tab">Attachments</a></li>
<?php } ?>
</ul>

<!-- Tab panes -->
<div class="tab-content">
<?php if( $sts_export_sage50 ) { ?>
  <div role="tabpanel" class="tab-pane active" id="cdntax">
<?php
	$company_tax_table = new sts_company_tax($exspeedite_db, $sts_debug);
	$rslt = new sts_result( $company_tax_table, "COMPANY_CODE = ".$_GET['CODE'], $sts_debug );
	echo $rslt->render( $sts_result_company_tax_layout, $sts_result_company_tax_edit, '?COMPANY_CODE='.$_GET['CODE'] );
?>
  </div>
<?php } ?>

<?php if( $sts_attachments ) { ?>
  <div role="tabpanel" class="tab-pane" id="attach">
<?php
	
	$attachment_table = sts_attachment::getInstance($exspeedite_db, $sts_debug);
	$rslt2 = new sts_result( $attachment_table, "SOURCE_CODE = ".$_GET['CODE']." AND SOURCE_TYPE = 'company'", $sts_debug );
	echo $rslt2->render( $sts_result_attachment_layout, $sts_result_attachment_edit, '?SOURCE_TYPE=company&SOURCE_CODE='.$_GET['CODE'] );
?>
  </div>
<?php } ?>

 </div>

<?php
}
?>

</div>
</div>
	<script language="JavaScript" type="text/javascript"><!--
		
		// Sets the CITY and STATE when you set the ZIP zip
		$(document).ready( function () {
			$('#ZIP').on('typeahead:selected', function(obj, datum, name) {
				$('input#CITY').val(datum.CityMixedCase);
				$('select#STATE').val(datum.State);
				$('select#COUNTRY').val(datum.Country);
			});

			$('select#STATE').on('change', function() {
				if( $('select#IFTA_BASE_JURISDICTION').val() == '' ) {
					$('select#IFTA_BASE_JURISDICTION').val($('select#STATE').val());
				}
			});

			$('select#COUNTRY').on('change', function() {
				if( $('select#COUNTRY').val() == 'USA' ) {
					$('select#HOME_CURRENCY').val('USD');
				} else
				if( $('select#COUNTRY').val() == 'Canada' ) {
					$('select#HOME_CURRENCY').val('CAD');
				}
			});

			$('#EXP_COMPANY_TAX').dataTable({
		        //"bLengthChange": false,
		        "bFilter": true,
		        "bSort": true,
		        "bInfo": false,
				"bAutoWidth": false,
				//"bProcessing": true, 
				"sScrollX": "100%",
				"sScrollY": "300px",
				//"sScrollXInner": "120%",
		        "lengthMenu": [<?php echo isset($sts_length_menu) ? $sts_length_menu : '25, 50, 100, 250'; ?>],
				"bPaginate": false,
				"bScrollCollapse": false,
				"bSortClasses": false		
			});

			$('#EXP_ATTACHMENT').dataTable({
		        //"bLengthChange": false,
		        "bFilter": true,
		        "bSort": true,
		        "bInfo": false,
				"bAutoWidth": false,
				//"bProcessing": true, 
				"sScrollX": "100%",
				"sScrollY": "300px",
				//"sScrollXInner": "120%",
		        "lengthMenu": [<?php echo isset($sts_length_menu) ? $sts_length_menu : '25, 50, 100, 250'; ?>],
				"bPaginate": false,
				"bScrollCollapse": false,
				"bSortClasses": false		
			});

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

		});
	//--></script>

<?php

require_once( "include/footer_inc.php" );
?>

