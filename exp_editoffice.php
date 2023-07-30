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

$sts_subtitle = "Edit Office";
require_once( "include/header_inc.php" );
require_once( "include/navbar_inc.php" );

require_once( "include/sts_result_class.php" );
require_once( "include/sts_form_class.php" );
require_once( "include/sts_office_class.php" );
require_once( "include/sts_sage50_glmap_class.php" );
require_once( "include/sts_setting_class.php" );
require_once( "include/sts_attachment_class.php" );

$setting_table = sts_setting::getInstance($exspeedite_db, $sts_debug);
$sts_export_sage50 = $setting_table->get( 'api', 'EXPORT_SAGE50_CSV' ) == 'true';
$sts_attachments = $setting_table->get( 'option', 'ATTACHMENTS_ENABLED' ) == 'true';

$office_table = sts_office::getInstance($exspeedite_db, $sts_debug);
$office_form = new sts_form( $sts_form_editoffice_form, $sts_form_edit_office_fields, $office_table, $sts_debug);

if( isset($_POST) && count($_POST) > 0 ) {		// Process completed form
	$result = $office_form->process_edit_form();

	if( $result ) {
		if( $sts_debug ) die; // So we can see the results
		reload_page ( "exp_listoffice.php" );
	}
}

?>
<div class="container-full theme-showcase" role="main">

<div class="well  well-lg">
<?php

if( isset($value) && is_array($value) && $result == false ) {	// If error occured
	echo "<p><strong>Error:</strong> ".$office_table->error()."</p>";
	echo $office_form->render( $value );
} else if( isset($_GET['CODE']) ) {
	$result = $office_table->fetch_rows($office_table->primary_key." = ".$_GET['CODE']);
	echo $office_form->render( $result[0] );
}

if( isset($_GET['CODE']) ) {
?>
<!-- Nav tabs -->
<ul class="nav nav-tabs">
<?php if( $sts_export_sage50 ) { ?>
  <li class="active"><a href="#sagegl" data-toggle="tab">Sage 50 GL Mapping</a></li>
<?php } ?>
<?php if( $sts_attachments ) { ?>
  <li><a href="#attach" data-toggle="tab">Attachments</a></li>
<?php } ?>
</ul>

<!-- Tab panes -->
<div class="tab-content">
<?php if( $sts_export_sage50 ) { ?>
	<div role="tabpanel" class="tab-pane active" id="sagegl">
<?php
	$sage50_glmap_table = new sts_sage50_glmap($exspeedite_db, $sts_debug);
	$rslt = new sts_result( $sage50_glmap_table, "OFFICE_CODE = ".$_GET['CODE'], $sts_debug );
	echo $rslt->render( $sts_result_sage50_glmap_layout, $sts_result_sage50_glmap_edit, '?OFFICE_CODE='.$_GET['CODE'] );
?>
  </div>
<?php } ?>

<?php if( $sts_attachments ) { ?>
  <div role="tabpanel" class="tab-pane" id="attach">
<?php
	
	$attachment_table = sts_attachment::getInstance($exspeedite_db, $sts_debug);
	$rslt2 = new sts_result( $attachment_table, "SOURCE_CODE = ".$_GET['CODE']." AND SOURCE_TYPE = 'office'", $sts_debug );
	echo $rslt2->render( $sts_result_attachment_layout, $sts_result_attachment_edit, '?SOURCE_TYPE=office&SOURCE_CODE='.$_GET['CODE'] );
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

