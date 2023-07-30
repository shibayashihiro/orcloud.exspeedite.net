<?php 

// $Id: exp_addinsp_report.php 4350 2021-03-02 19:14:52Z duncan $
// Add/edit report

// Set flag that this is a parent file
define( '_STS_INCLUDE', 1 );

// Setup Session
require_once( "include/sts_session_setup.php" );

require_once( "include/sts_config.php" );
$sts_debug = (isset($_GET['debug'])  || isset($_POST['debug'])) && in_group( EXT_GROUP_DEBUG );
//$sts_debug = isset($_POST) && count($_POST) > 0 ;
require_once( "include/sts_session_class.php" );

$my_session = sts_session::getInstance( $exspeedite_db, $sts_debug );
$my_session->access_check( EXT_GROUP_MECHANIC, EXT_GROUP_INSPECTION );	// Make sure we should be here

$sts_subtitle = "Add Inspection Report";
require_once( "include/header_inc.php" );
require_once( "include/navbar_inc.php" );

require_once( "include/sts_form_class.php" );
require_once( "include/sts_insp_report_class.php" );
require_once( "include/sts_attachment_class.php" );
require_once( "include/sts_setting_class.php" );
require_once( "include/sts_alert_class.php" );

$setting_table = sts_setting::getInstance($exspeedite_db, $sts_debug);
$sts_attachments = $setting_table->get( 'option', 'ATTACHMENTS_ENABLED' ) == 'true';
$insp_title = $setting_table->get( 'option', 'INSPECTION_REPORT_TITLE' );

$report_table = sts_insp_report::getInstance($exspeedite_db, $sts_debug);
$alert_table = sts_alert::getInstance($exspeedite_db, $sts_debug);

if( isset($_POST) && count($_POST) > 0 ) {		// Process completed form
	//$sts_debug = true;
	if( $sts_debug ) {
			echo "<pre>";
			var_dump($_POST);
			echo "</pre>";
			//die;
	}
	$report_table->process_form();
	
	//! SCR# 700  - clear the cache of alerts
	$alert_table->clear_cache();

	if( $sts_debug ) die; // So we can see the results
	
	reload_page ( "exp_addinsp_report.php?REPORT=".$_POST["REPORT_CODE"].
		(isset($_POST['REFERER']) ? '&REFERER='.$report_table->encryptData($_POST['REFERER']) : '') );

} else {
	if( isset($_GET["UNIT"]) && isset($_GET["TYPE"]) && isset($_GET["FORM"])) {		//! Add new
		$report = $report_table->create_empty($_GET["TYPE"], $_GET["UNIT"], $_GET["FORM"] );
	} else if( isset($_GET["REPORT"])) {					//! Edit existing
		$report = $_GET["REPORT"];
	}
	
	if( isset($_GET["REFERER"])) {
		$referer = $report_table->decryptData($_GET['REFERER']);
	} else if( isset($_GET["REPORT"]) ) {
		if( isset($_SERVER["HTTP_REFERER"]) ) {
			$path = explode('/', $_SERVER["HTTP_REFERER"]);
			$referer = end($path);
		} else {
			$referer = 'index.php';
		}
	} else if( isset($_GET["UNIT"]) && isset($_GET["TYPE"]) ) {
		$referer = 'exp_edit'.$_GET["TYPE"].'.php?CODE='.$_GET["UNIT"];
	} else {
		$referer = 'index.php';
	}
	
	// Kluge for exp_emailattachment.php
	$_SESSION["REPORT_REFERRER"] = $report_table->encryptData($referer);
	
	list($report_form, $report_fields, $report_values) = $report_table->create_form( $report, $referer );
	//echo "<pre>";
	//var_dump($report_form['layout']);
	//var_dump($report_fields);
	//echo "</pre>";
	//die;
	
	if( ! (isset($report_form['buttons']) && is_array($report_form['buttons'])))
		$report_form['buttons'] = array();
	
	$report_form['buttons'][] = array( 'label' => 'View', 'blank' => true,
		'link' => 'exp_viewinsp_report.php?REPORT='.$report, 'button' => 'default',
		'icon' => '<span class="glyphicon glyphicon-list-alt"></span>' );

	$report_form['buttons'][] = array( 'label' => 'Print', 'blank' => true,
		'onclick' => 'window.open(\'exp_viewinsp_report.php?REPORT='.$report.'&PRINT\', \'newwindow\', \'width=\'+ ($(window).width()*2/3) + \',height=\' + ($(window).height()*2/3)); return false;',
		'button' => 'default', 'icon' => '<span class="glyphicon glyphicon-print"></span>' );

	$report_form['buttons'][] = array( 'label' => 'Email',
		'link' => 'exp_emailinsp_report.php?REPORT='.$report.'&REFERER='.$report_table->encryptData($referer), 'button' => 'default',
		'icon' => '<span class="glyphicon glyphicon-envelope"></span>' );

	
	
	$report_form = new sts_form( $report_form, $report_fields, $report_table, $sts_debug);
	
	?>
<div class="container-full theme-showcase" role="main">
	
	<div class="well  well-md">
	<?php
	
	if( isset($value) && is_array($value) && $result == false ) {	// If error occured
		echo "<p><strong>Error:</strong> ".$report_table->error()."</p>";
		echo $report_form->render( $value );
	} else {
		echo $report_form->render( $report_values );
	}
	

	if( $sts_attachments ) {
		$attachment_table = sts_attachment::getInstance($exspeedite_db, $sts_debug);
		$rslt2 = new sts_result( $attachment_table, "SOURCE_CODE = ".$report." AND SOURCE_TYPE = 'insp_report'", $sts_debug );
		echo '<div class="well well-sm">'.
			$rslt2->render( $sts_result_attachment_layout, $sts_result_attachment_edit,
				'?SOURCE_TYPE=insp_report&SOURCE_CODE='.$report ).
			'</div>';
	}


	?>
	</div>
</div>

	<script language="JavaScript" type="text/javascript"><!--
		$(document).ready( function () {
			
			function toggle_dualies() {
				if( $("[id^=TIRES_DUALIES_]").prop('checked') ) {
					$('.dually').prop('hidden',false);
					$('.single').prop('hidden', 'hidden');
				} else {
					$('.dually').prop('hidden', 'hidden');
					$('.single').prop('hidden',false);
				}
			}

			$("[id^=TIRES_DUALIES_]").on('change', function() {
				toggle_dualies();
			});
			
			toggle_dualies();
			
			function showAsFloat(num, n){
				return !isNaN(+num) ? (+num).toFixed(n || 2) : num;
			}

			
			function update_part_total() {
				var grandTotal = 0;
			    $(".subtot").each(function () {
			        var stval = parseFloat($(this).val());
			        grandTotal += isNaN(stval) ? 0 : stval;
			    });
			 
			    $('.grdtot').val(showAsFloat(grandTotal));				
			}
			
			$("[id^=PART_QTY_], [id^=PART_COST_]").on('keyup', function( e ) {
				var id = e.currentTarget.id;
				var p = id.split("_").pop();
				var qty = parseFloat($("#PART_QTY_"+p).val());
				var cost = parseFloat($("#PART_COST_"+p).val());
				var total = parseFloat(qty * cost);
				if( isNaN(total) ) total = 0;
				
				$("#PART_TOTAL_"+p).val( showAsFloat(total) );
								
				update_part_total();
			});
			
			update_part_total();
			
			$("#addpart").on('click', function( e ) {
				//console.log("#addpart", $(this).attr("data.item") );
				
				$.ajax({
					url: 'exp_insp_report_part_ajax.php',
					data: {
						pw: 'GoFish',
						item: $(this).attr("data.item"),
						add: true
					},
					dataType: "json",
					success: function(data) {
						//console.log(data);
						$("#part_table tbody tr:last").after(data);
						update_delpart();
					}
				});
				
			});
			
			function update_delpart() {
				$(".delpart").off('click').on('click', function( e ) {
					//console.log("#addpart", $(this).attr("data.tr"), $(this).attr("data.item"), $(this).attr("data.code") );
					var tr = $(this).attr("data.tr");
					$.ajax({
						url: 'exp_insp_report_part_ajax.php',
						data: {
							pw: 'GoFish',
							item: $(this).attr("data.item"),
							del: $(this).attr("data.code")
						},
						dataType: "json",
						success: function(data) {
							//console.log(data, tr );
							if( data == 'OK' ) {
								$('#part_table tbody #'+tr).remove();
								update_part_total();
							}
						}
					});
				});
			}
			
			update_delpart();

		});
	//--></script>

<?php
}

require_once( "include/footer_inc.php" );
?>
		

