<?php 

// $Id: exp_editscr.php 4697 2022-03-09 23:02:23Z duncan $
// Edit SCR

// Set flag that this is a parent file
define( '_STS_INCLUDE', 1 );

// Setup Session
require_once( "include/sts_session_setup.php" );

require_once( "include/sts_config.php" );
$sts_debug = (isset($_GET['debug'])  || isset($_POST['debug'])) && in_group( EXT_GROUP_DEBUG );
require_once( "include/sts_session_class.php" );

$my_session = sts_session::getInstance( $exspeedite_db, $sts_debug );
$my_session->access_check( EXT_GROUP_ADMIN );	// Make sure we should be here

$sts_subtitle = "Edit Software Change Request";
require_once( "include/header_inc.php" );
require_once( "include/navbar_inc.php" );

require_once( "include/sts_result_class.php" );
require_once( "include/sts_form_class.php" );
require_once( "include/sts_scr_class.php" );
require_once( "include/sts_attachment_class.php" );
require_once( "include/sts_setting_class.php" );

$setting_table = sts_setting::getInstance($exspeedite_db, $sts_debug);

$scr_table = sts_scr::getInstance($exspeedite_db, $sts_debug);

if( isset($_POST) && isset($_POST['SCR_CODE']) ) $_GET['CODE'] = $_POST['SCR_CODE'];

$check = $scr_table->fetch_rows("SCR_CODE = ".$_GET['CODE'], "CURRENT_STATUS, FORM_LEVEL");
$status = $check[0]["CURRENT_STATUS"];
$form_level = empty($check[0]["FORM_LEVEL"]) ? 1 : $check[0]["FORM_LEVEL"];

if( isset($_GET['EMAIL'])) {
	$email_type = 'scr';
	$email_code = $_GET['CODE'];
	require_once( "exp_spawn_send_email.php" ); // Send email
}

switch( $scr_table->state_behavior[$status] ) {
	case 'entry':
	case 'dead':
	case 'unapproved':
	case 'approved':
		$form_level = max(array(1, $form_level));
		break;

	case 'docked':
	case 'late':
		$form_level = 1;
		break;
	
	case 'assign':
	case 'inprogress':
		$form_level = max(array(2, $form_level));
		break;
	
	default:
		$form_level = max(array(3, $form_level));
		break;
}

$scr_table->update($_GET['CODE'], array('FORM_LEVEL' => $form_level) );

switch( $form_level ) {
	case 1:
		$edit_form = $sts_form_editscr_form1;
		break;
	
	case 2:
		$edit_form = $sts_form_editscr_form2;
		break;
	
	default:
		$edit_form = $sts_form_editscr_form3;
		break;
}


//! SCR - add buttons for state changes
if( ! isset($edit_form['buttons']) || ! is_array($edit_form['buttons']))
	$edit_form['buttons'] = array();
	
$edit_form['buttons'][] = 
		array( 'label' => '',
		'link' => 'exp_editscr.php?CODE='.$_GET['CODE'],
		'button' => 'success',
		'tip' => 'Refresh this screen.',
		'icon' => '<span class="glyphicon glyphicon-refresh"></span>' );

$edit_form['buttons'][] = 
		array( 'label' => '',
		'link' => 'exp_editscr.php?EMAIL&CODE='.$_GET['CODE'],
		'button' => 'success',
		'tip' => 'Send an email update to interested parties. Does not change status of SCR.',
		'icon' => '<span class="glyphicon glyphicon-envelope"></span>' );

$following = $scr_table->following_states( $status );

foreach( $following as $row ) {
	if( $scr_table->state_change_ok( $_GET['CODE'], $status, $row['CODE'] ) ) {
			$edit_form['buttons'][] = 
			array( 'label' => $scr_table->state_name[$row['CODE']],
			'link' => 'exp_scr_state.php?CODE='.$_GET['CODE'].
			'&STATE='.$row['CODE'].'&CSTATE='.$status,
			'button' => 'primary', 'tip' => $row['DESCRIPTION'],
			'icon' => '<span class="glyphicon glyphicon-arrow-right"></span>',
			'restrict' => EXT_GROUP_ADMIN );
	} else {
			$edit_form['buttons'][] = 
			array( 'label' => $scr_table->state_name[$row['CODE']],
			'link' => 'exp_scr_state.php?CODE='.$_GET['CODE'].
			'&STATE='.$row['CODE'].'&CSTATE='.$status,
			'button' => 'primary', 'tip' => $scr_table->state_change_error,
			'disabled' => true,
			'icon' => '<span class="glyphicon glyphicon-remove"></span>',
			'restrict' => EXT_GROUP_ADMIN );
	}
}

$scr_form = new sts_form($edit_form, $sts_form_edit_scr_fields, $scr_table, $sts_debug);

//! Any click triggers a save
$scr_form->set_autosave();


if( isset($_POST) && isset($_POST['RESULT_SAVE_CODE']) )	// sts_result saved the code
	$_GET['CODE'] = $_POST['RESULT_SAVE_CODE'];
else if( isset($_POST) && count($_POST) > 0 ) {		// Process completed form
	$result = $scr_form->process_edit_form();

	if( $result ) {
		if( $sts_debug ) die; // So we can see the results

		//reload_page ( "exp_editscr.php?CODE=".$_POST['SCR_CODE'] );
	}
}

?>
<div class="container-full theme-showcase" role="main">

<div class="well  well-lg">
<?php

if( isset($_POST) && count($_POST) > 0 && isset($result) && $result == false ) {	// If error occured
	echo "<p><strong>Error:</strong> ".$scr_table->error()."</p>";
	echo $scr_form->render( $_POST );
} else if( isset($_GET['CODE']) ) {
	$result = $scr_table->fetch_rows("scr_CODE = ".$_GET['CODE']);
	echo $scr_form->render( $result[0] );
}

if( isset($_GET['CODE']) ) {
?>
<!-- Nav tabs -->
<ul class="nav nav-tabs">
  <li class="active"><a href="#attach" data-toggle="tab">Attachments</a></li>
  <li><a href="#history" data-toggle="tab">History</a></li>
</ul>

<!-- Tab panes -->
<div class="tab-content">
  <div role="tabpanel" class="tab-pane active" id="attach">
<?php
	$attachment_table = sts_attachment::getInstance($exspeedite_db, $sts_debug);
	$rslt2 = new sts_result( $attachment_table, "SOURCE_CODE = ".$_GET['CODE']." AND SOURCE_TYPE = 'scr'", $sts_debug );
	echo $rslt2->render( $sts_result_attachment_layout, $sts_result_attachment_edit, '?SOURCE_TYPE=scr&SOURCE_CODE='.$_GET['CODE'] );
?>
  </div>
  <div role="tabpanel" class="tab-pane" id="history">
<?php
	$scr_history = sts_scr_history::getInstance($exspeedite_db, $sts_debug);
	$rslt3 = new sts_result( $scr_history, "SCR_CODE = ".$_GET['CODE'], $sts_debug );
	echo $rslt3->render( $sts_result_scr_history_layout, $sts_result_scr_history_edit, '?SCR_CODE='.$_GET['CODE'] );
}
?>
	</div>
 </div>

</div>
</div>

	<div class="modal fade fuzzy bs-example-modal-sm" tabindex="-1" role="dialog" aria-labelledby="mySmallModalLabel" aria-hidden="true" id="editscr_modal">
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

			$('a[data-toggle="tab"]').on( 'shown.bs.tab', function (e) {
				$.fn.dataTable.tables( {visible: true, api: true} ).columns.adjust();
			} );

			$( "#editscr" ).submit(function( event ) {
				event.preventDefault();  //prevent form from submitting
				$('#editscr_modal').modal({
					container: 'body'
				});
				var data = $("#editscr :input").serializeArray();
				
				//console.log(data);
				$.post("exp_editscr.php", data, function( data ) {
					//alert('Saved changes');

					$('#editscr_modal').modal('hide');
					window.editscr_HAS_CHANGED = false;	// depreciated
					//$('a').off('click.editclient');
					if( $( "#editscr" ).data('reload') == true )
						window.location.href = window.location.href;
				});
			});
			
			function copy_text( reference ) {
				var copyText = $(reference);
				
				/* Select the text field */
				copyText.select();
				//copyText.setSelectionRange(0, 99999); /* For mobile devices */
				
				/* Copy the text inside the text field */
				document.execCommand("copy");
			}
			
			$('label[for="TITLE"]').on('click', function(event) {
				copy_text( 'input#TITLE' );
			});
			
			$('label[for="SCR_DESCRIPTION"]').on('click', function(event) {
				copy_text( 'textarea#SCR_DESCRIPTION' );
			});
			
			$('label[for="ANALYSIS"]').on('click', function(event) {
				copy_text( 'textarea#ANALYSIS' );
			});
			
			$('label[for="DEV_NOTES"]').on('click', function(event) {
				copy_text( 'textarea#DEV_NOTES' );
			});
			
			$('label[for="QA_NOTES"]').on('click', function(event) {
				copy_text( 'textarea#QA_NOTES' );
			});
			
			$('#EXP_ATTACHMENT, #EXP_SCR_HISTORY').dataTable({
		        //"bLengthChange": false,
		        "bFilter": false,
		        "bSort": false,
		        "bInfo": false,
				"bAutoWidth": false,
				//"bProcessing": true, 
				"sScrollX": "100%",
				"sScrollY": "300px",
				"sScrollXInner": "120%",
				"bPaginate": true,
				"bScrollCollapse": false,
				"bSortClasses": false		
			});
			
			$('#RESOLUTION, #ASSIGNED_DEV, #ASSIGNED_QA, #FIXED_IN_RELEASE').change(function () {
			    $( "#editscr" ).data('reload', true);	// Reload on next save
			    $( "#editscr" ).submit();
			 });
			 			
			if (/PhantomJS/.test(window.navigator.userAgent)) {
				console.log("exp_editclient.php: PhantomJS environment detected.");
			}

			$('a[data-toggle="tab"]').on("shown.bs.tab", function (e) {
			    var id = $(e.target).attr("href");
			    localStorage.setItem('scr_selectedTab', id)
			});
			
			var selectedTab = localStorage.getItem('scr_selectedTab');
			if (selectedTab != null) {
			    $('a[data-toggle="tab"][href="' + selectedTab + '"]').tab('show');
			}

		});
	//--></script>


<?php

require_once( "include/footer_inc.php" );
?>

