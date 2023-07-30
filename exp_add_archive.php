<?php 

// $Id: exp_add_archive.php 4697 2022-03-09 23:02:23Z duncan $
// Archive loads and shipments

// Set flag that this is a parent file
define( '_STS_INCLUDE', 1 );

// Setup Session
require_once( "include/sts_session_setup.php" );

require_once( "include/sts_config.php" );
set_time_limit(0);
ini_set('memory_limit', '1024M');

$sts_debug = (isset($_GET['debug'])  || isset($_POST['debug'])) && in_group( EXT_GROUP_DEBUG );
//$sts_debug = isset($_POST) && count($_POST) > 0 ;
require_once( "include/sts_session_class.php" );

$my_session = sts_session::getInstance( $exspeedite_db, $sts_debug );
$my_session->access_check( EXT_GROUP_ADMIN );	// Make sure we should be here
// maybe use $my_session->superadmin()

$sts_subtitle = "Create Archive";
require_once( "include/header_inc.php" );
require_once( "include/navbar_inc.php" );

require_once( "include/sts_form_class.php" );
require_once( "include/sts_archive_class.php" );

$archive = sts_archive::getInstance($exspeedite_db, $sts_debug);

$archive_form = new sts_form( $sts_form_archive_form, $sts_form_archive_fields, $archive, $sts_debug);

?>
<div class="container-full theme-showcase" role="main">

<div class="well  well-md">
<?php

if( isset($_POST) && count($_POST) > 0 ) {		// Process completed form
	if( isset($_POST["archive"]) ) {
		//echo "<pre>";
		//var_dump($_POST);
		//echo "</pre>";
		//die;
		
		// Turn off output buffering
		ini_set('output_buffering', 'off');
		// Implicitly flush the buffer(s)
		ini_set('implicit_flush', true);
		ob_implicit_flush(true);
	
		if (ob_get_level() == 0) ob_start();
		
		$subdir = $archive->set_archive_subdir( $_POST["ARCHIVE_FROM"], $_POST["ARCHIVE_TO"] );
		if( $subdir != false ) {
			echo '<h2 class="tighter-top"><span class="glyphicon glyphicon-export"></span> Create Archive: Archiving to '.$subdir.'</h2>
			<p>You will find all the files in '.$archive->get_subdir_path().'</p>
			';
			ob_flush(); flush();
			
			$loads = [];
			if( ! empty($_POST["LOADS"]) )
				$loads = explode(',', $_POST["LOADS"]);
			echo '<h3><span class="glyphicon glyphicon-export"></span> Archive '.count($loads).' Loads</h3>
			';
			ob_flush(); flush();
			foreach( $loads as $load) {
				echo $load.' ';
				ob_flush(); flush();
				$archive->archive_load( $load );
			}
			
			$shipments = [];
			if( ! empty($_POST["SHIPMENTS"]) )
			$shipments = explode(',', $_POST["SHIPMENTS"]);
			echo '<br><br><h3><span class="glyphicon glyphicon-export"></span> Archive '.count($shipments).' Shipments</h3>
			';
			ob_flush(); flush();
			foreach( $shipments as $shipment) {
				echo $load.' ';
				ob_flush(); flush();
				$archive->archive_shipment( $shipment );
			}
			
			echo '<br><br><h3 class="text-success"><span class="glyphicon glyphicon-ok"></span> Done <a class="btn btn-sm btn-default" id="archive_cancel" href="exp_view_archive.php"><span class="glyphicon glyphicon-arrow-left"></span> Back</a></h3>';
		} else {
			echo '<h2 class="tighter-top text-danger"><span class="glyphicon glyphicon-warning-sign"></span> Unable To Create Subdirectory</h2>
			<h3>Please check permissions for creating '.$archive->get_subdir_path().'</h3>
			';
		}
		
		
	} else {
		$cancel = isset($_POST["ARCHIVE_CANCEL"]);
		
		// Get list of shipments and loads
		list($loads, $shipments) = $archive->get_loads_shipments( $_POST["ARCHIVE_FROM"], $_POST["ARCHIVE_TO"], $cancel );
		
		echo '<form role="form" class="form-horizontal" action="exp_add_archive.php" 
				method="post" enctype="multipart/form-data" name="archive" id="archive">
		<input name="ARCHIVE_FROM" type="hidden" value="'.$_POST["ARCHIVE_FROM"].'">
		<input name="ARCHIVE_TO" type="hidden" value="'.$_POST["ARCHIVE_TO"].'">
		'.(isset($_POST["ARCHIVE_CANCEL"]) ? '<input name="ARCHIVE_CANCEL" type="hidden" value="true">
		' : '').'<input name="LOADS" type="hidden" value="'.implode(',', $loads).'">
		<input name="SHIPMENTS" type="hidden" value="'.implode(',', $shipments).'">
		<h2 class="tighter-top"><span class="glyphicon glyphicon-export"></span> Create Archive: Selected Loads & Shipments <a class="btn btn-sm btn-default" id="archive_cancel" href="exp_add_archive.php"><span class="glyphicon glyphicon-arrow-left"></span> Back</a>
		<button class="btn btn-sm btn-success" name="refresh" type="submit" ><span class="glyphicon glyphicon-refresh"></span> Refresh</button>
		'.(count($loads) + count($shipments) > 0 ? '<button class="btn btn-sm btn-danger tip" title="No undo!" name="archive" type="submit" ><span class="glyphicon glyphicon-warning-sign"></span> Go For It</button>' : '').'
	</h2>
		<p>These loads & shipments fall within the dates (or are included for integrity reasons such as crossdock or consolidated billing)</p>
		</form>
		';
		
		$match1 = count($loads) > 0 ? "LOAD_CODE IN (".implode(', ', $loads).")" : "FALSE";
	
		$load_table = sts_load::getInstance($exspeedite_db, $sts_debug);
		$rslt1 = new sts_result( $load_table, $match1, $sts_debug );
		echo $rslt1->render( $sts_result_archive_load_layout, $sts_result_archive_load_edit );
	
		$match2 = count($shipments) > 0 ? "SHIPMENT_CODE IN (".implode(', ', $shipments).")" : "FALSE";
	
		$shipment_table = sts_shipment::getInstance($exspeedite_db, $sts_debug);
		$rslt2 = new sts_result( $shipment_table, $match2, $sts_debug );
		echo $rslt2->render( $sts_result_archive_shipment_layout, $sts_result_archive_shipment_edit );
	}
		
} else {


	if( isset($value) && is_array($value) && $result == false ) {	// If error occured
		echo "<p><strong>Error:</strong> ".$archive_form->error()."</p>";
		echo $archive_form->render( $value );
	} else {
		echo $archive_form->render();
	}

}
?>
</div>
</div>

	<script language="JavaScript" type="text/javascript"><!--
		
		$(document).ready( function () {
			//alert(($(window).height() - 150) + "px");
			//document.documentElement.style.overflow = 'hidden';  // firefox, chrome
			//document.body.scroll = "no"; // ie only

			$('#EXP_LOAD, #EXP_SHIPMENT').dataTable({
		        //"bLengthChange": false,
		        "bFilter": false,
		        "bSort": false,
		        "bInfo": false,
				"bAutoWidth": false,
				//"bProcessing": true, 
				"sScrollX": "100%",
				"sScrollY": (($(window).height() - 400)/2) + "px",
				//"sScrollXInner": "120%",
				"bPaginate": false,
				"bScrollCollapse": false,
				"bSortClasses": false		
			});
						
		});
	//--></script>

<?php

require_once( "include/footer_inc.php" );
?>
		

