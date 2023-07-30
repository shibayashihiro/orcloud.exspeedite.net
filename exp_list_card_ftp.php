<?php 

// $Id: exp_list_card_ftp.php 5030 2023-04-12 20:31:34Z duncan $
// - Fuel Card FTP Configuration 

// Set flag that this is a parent file
define( '_STS_INCLUDE', 1 );

// Setup Session
require_once( "include/sts_session_setup.php" );

require_once( "include/sts_config.php" );
$sts_debug = isset($_GET['debug']) && in_group( EXT_GROUP_DEBUG );
require_once( "include/sts_session_class.php" );

set_time_limit(120);
ini_set('memory_limit', '1024M');

$my_session = sts_session::getInstance( $exspeedite_db, $sts_debug );
$my_session->access_check( EXT_GROUP_ADMIN );	// Make sure we should be here

// Use Google Charts, see https://developers.google.com/chart
define( '_STS_GOOGLE_CHARTS', 1 );
$sts_subtitle = "Fuel Card FTP Configuration";
require_once( "include/header_inc.php" );
require_once( "include/navbar_inc.php" );

?>
<div class="container-full" role="main">
<?php
require_once( "include/sts_result_class.php" );
require_once( "include/sts_card_ftp_class.php" );
require_once( "include/sts_card_bvd_class.php" );
require_once( "include/sts_card_class.php" );
require_once( "include/sts_setting_class.php" );

$setting_table = sts_setting::getInstance($exspeedite_db, $sts_debug);
$sts_ifta_base = $setting_table->get( 'api', 'IFTA_BASE_JURISDICTION' );

$ftp = sts_card_ftp::getInstance($exspeedite_db, $sts_debug);
$bvd_transport = sts_card_bvd_transport::getInstance($exspeedite_db, $sts_debug);
$bvd = sts_card_bvd::getInstance($exspeedite_db, $sts_debug);
$card = sts_card::getInstance($exspeedite_db, $sts_debug);

$max_size = 1000000;

//! Process form for import CSV
if( isset($_POST['TRUCKS_SUBMIT']) || isset($_POST['TRAILERS_SUBMIT']) ) {
	if ( isset($_POST['TRUCKS_SUBMIT']) && isset($_FILES["TRUCKS"])) {
		$card->import_csv( $_FILES["TRUCKS"], "TRUCKS" );
		reload_page ( "exp_view_card.php?RESOLVE" );
	} else if ( isset($_POST['TRAILERS_SUBMIT']) && isset($_FILES["TRAILERS"])) {
		$card->import_csv( $_FILES["TRAILERS"], "TRAILERS" );
		reload_page ( "exp_list_card_ftp.php?CSV" );
	}
} else {
	if (ob_get_level() == 0) ob_start();
	ob_flush(); flush();

	$filters_html = '<div class="form-group"><a class="btn btn-sm btn-success" href="exp_list_card_ftp.php?CSV"><span class="glyphicon glyphicon-import"></span> CSV Import</a></div>';
	$filters_html .= '<div class="form-group"><a class="btn btn-sm btn-default" href="exp_list_fuel_card_mapping.php"><span class="glyphicon glyphicon-arrow-right"></span> Mapping</a></div>';
	$filters_html .= '<div class="form-group"><a class="btn btn-sm btn-default" href="exp_listca.php"><span class="glyphicon glyphicon-usd"></span> Advances</a></div>';
	$sts_result_ftp_edit['filters_html'] = $filters_html;
	
	$rslt = new sts_result( $ftp, false, $sts_debug );
	echo '<div class="well well-sm">
		'.$rslt->render( $sts_result_ftp_layout, $sts_result_ftp_edit ).'
		</div>';
	ob_flush(); flush();
	
	if( isset($_GET['DL']) ) {	//! DL - Show list of files to download
		echo '<div id="loading"><h2 class="text-center"><img src="images/loading.gif" alt="loading" width="125" height="125" /><br>Connecting to '.$_GET['DL'].'...</h2></div>';
		ob_flush(); flush();
		
		$ftp->client_open( $_GET['DL'] );	// Get client info
		$transport = $ftp->transport( $_GET['DL'] );	// Get transport
		$format = $ftp->card_format( $_GET['DL'] );	// Get format
		if( $sts_debug ) {
			echo "<pre>exp_list_card_ftp.php: transport, format\n";
			var_dump($transport);
			var_dump($format);
			echo "</pre>";
			ob_flush(); flush();
		}
		
		$files = array();
		echo "<pre>";
		var_dump($_GET['DL']);
		var_dump($transport, $format);
		echo "</pre>";
		
		if( $transport == 'FTP' ) {
			$files = $ftp->ftp_connect( $_GET['DL'] );
			if( $sts_debug ) {
				echo "<pre>exp_list_card_ftp.php: after ftp_connect\n";
				var_dump($files);
				echo "</pre>";
				ob_flush(); flush();
			}
		} else if( $transport == 'MANUAL' ) {	//! MANUAL - form for entering file name
		} else {
			echo '<h2><strong>Error:</strong> transport is not FTP</h2>';
			$ftp->log_event(__FILE__.': Error: transport is not FTP');
		}
		$unresolved = $card->count_unresolved();
		$unresolved_advances = $card->count_unresolved_advances();
		
		update_message( 'loading', '' );
		if( $files === false ) {
			echo "<p><strong>Error:</strong> ".$ftp->last_error."<br>
			Please check your FTP settings and try again.</p>";
		} else
		if( is_array($files) ) {
		
			echo '<div class="well well-sm">
				<h3><img src="images/'.($format=='COMDATA' ? 'comdata' : 'csv').'.png" alt="'.($format=='COMDATA' ? 'comdata' : 'csv').'" height="40" /> Fuel Card Files For '.$_GET['DL'].' ('.$transport.', '.$format.') <a class="btn btn-sm btn-default" href="exp_list_card_ftp.php"><span class="glyphicon glyphicon-remove"></span> Back</a>
				'.($unresolved > 0 ? '<a class="btn btn-sm btn-danger" href="exp_view_card.php?RESOLVE"><span class="glyphicon glyphicon-saved"></span> Resolve '.$unresolved.' Tractors</a>' : '').($unresolved_advances > 0 ? ' <a class="btn btn-sm btn-danger" href="exp_resolve_advance.php?RESOLVE"><span class="glyphicon glyphicon-saved"></span> Resolve '.$unresolved_advances.' Drivers</a>' : '').'</h3>
				';
			ob_flush(); flush();
			
			if( $transport == 'FTP' ) {
				echo '<div class="table-responsive">
				<table class="display table table-striped table-condensed table-bordered table-hover" id="FUEL_CARDS" style="width: auto;">
				<thead><tr class="exspeedite-bg"><th>Actions</th><th>File</th><th>Size</th><th>Modified</th></tr>
				</thead>
				<tbody>';
				ob_flush(); flush();
				//! SCR# 430 - Fuel card import enhancements
				if( count($files) > 0 ) {
					foreach($files as $card) {
						echo '<tr>
							<td>
								<a class="btn btn-sm btn-default" href="exp_view_card.php?VIEW&CODE='.$_GET['DL'].'&FILE='.$card.'"><span class="glyphicon glyphicon-search"></span> View</a>
								<a class="btn btn-sm btn-success" href="exp_view_card.php?IMPORT&CODE='.$_GET['DL'].'&FILE='.$card.'"><span class="glyphicon glyphicon-import"></span> Import</a>
								<a class="btn btn-sm btn-danger" onclick="confirmation(\'Confirm: Delete import file '.$card.'?<br>Exspeedite will FTP into the remote server and delete it.<br>There is no undo.\',
									\'exp_view_card.php?DELETE&CODE='.$_GET['DL'].'&FILE='.$card.'\')"><span class="glyphicon glyphicon-trash"></span></a>
								</td>
							<td><strong>'.$card.'</strong></td>
							<td>'.$ftp->ftp_file_size( $card ).' bytes</td>
							<td>'.$ftp->ftp_mdtm( $card ).'</td>
						</tr>';
					}
				} else {
					echo '<tr>
						<td colspan="4">No files available to download.</td>
					</tr>';
				}
				
				echo '</tbody>
					</table>
					</div>';
				ob_flush(); flush();
				
			} else if( $transport == 'MANUAL' ) {	//! MANUAL - form for entering file name
				//$sts_debug = true; // testing
				echo '<form action="exp_view_card.php" method="post" enctype="multipart/form-data">
					<input name="CLIENT" id="CLIENT" type="hidden" value="'.$_GET['DL'].'">
					'.($sts_debug ? '<input name="debug" id="debug" type="hidden" value="on">' : '').'
					<div class="row">
						<div class="col-sm-offset-1 col-sm-8">
							<div class="panel">
									<br>
								<div class="form-group">
									<div class="col-sm-8"><input type="file" name="MANUAL_FILE" id="MANUAL_FILE"></div>
									<div class="col-sm-2"><button class="btn btn-sm btn-default" type="submit" id="VIEW_FILE" name="VIEW_FILE"><span class="glyphicon glyphicon-search"></span> View</button></div>
									<div class="col-sm-2"><button class="btn btn-sm btn-success" type="submit" id="IMPORT_FILE" name="IMPORT_FILE"><span class="glyphicon glyphicon-import"></span> Import</button></div>
				<div class="col-sm-offset-1 col-sm-8" id="TOO_BIG" hidden>
				</div>
								</div>
									<br>
									<br>
							</div>
						</div>
					</div>
				</form>';
			}
				
				
			echo '</div>';
		}
	}
	else if( isset($_GET['BVD']) ) {	//! BVD - Show list of months to import
		$bvd_transport->client_open( $_GET['BVD'] );	// Get client info
		$transport = $bvd_transport->transport( $_GET['BVD'] );	// Get transport
		$format = $bvd_transport->card_format( $_GET['BVD'] );	// Get format
		if( $sts_debug ) {
			echo "<pre>exp_list_card_ftp.php: transport, format\n";
			var_dump($transport);
			var_dump($format);
			echo "</pre>";
			ob_flush(); flush();
		}
		
		echo '<div id="loading"><h2 class="text-center"><img src="images/loading.gif" alt="loading" width="125" height="125" /><br>Connecting to '.$_GET['BVD'].' ('.$transport.' format) to retrieve data...</h2></div>';
		ob_flush(); flush();
		
		$unresolved = $card->count_unresolved();
		$unresolved_advances = $card->count_unresolved_advances();
		
		$output = '<div class="well well-sm">
				<h3><img src="images/'.($format=='COMDATA' ? 'comdata' : 'csv').'.png" alt="'.($format=='COMDATA' ? 'comdata' : 'csv').'" height="40" /> Fuel Card Import From '.$_GET['BVD'].' ('.$transport.', '.$format.') <a class="btn btn-sm btn-default" href="exp_list_card_ftp.php"><span class="glyphicon glyphicon-remove"></span> Back</a>
				'.($unresolved > 0 ? '<a class="btn btn-sm btn-danger" href="exp_view_card.php?RESOLVE"><span class="glyphicon glyphicon-saved"></span> Resolve '.$unresolved.' Tractors</a>' : '').($unresolved_advances > 0 ? ' <a class="btn btn-sm btn-danger" href="exp_resolve_advance.php?RESOLVE"><span class="glyphicon glyphicon-saved"></span> Resolve '.$unresolved_advances.' Drivers</a>' : '').'</h3>
				';		
		$output .= '<div class="table-responsive">
		<table class="display table table-striped table-condensed table-bordered table-hover" id="FUEL_CARDS" style="width: auto;">
		<thead><tr class="exspeedite-bg"><th>Action</th><th>Month</th><th>Transactions</th></tr>
		</thead>
		<tbody>';

		for( $c = 5; $c >= 0; $c-- ) {
			$month = date('F Y', strtotime("first day of this month - $c months"));
			$start = date('Y-m-d', strtotime("first day of this month - $c months"));
			$end = date('Y-m-d', strtotime("last day of this month - $c months"));
			$count = $bvd_transport->bvd_count( $_GET['BVD'], $start, $end );
			$output .= '<tr>
				<td><a class="btn btn-success btn-md" href="exp_view_card.php?IMPORT&BVD='.$_GET['BVD'].'&START='.$start.'&END='.$end.'""><span class="glyphicon glyphicon-import"></span> Import</a></td>
				<td>'.$month.'</td>
				<td class="text-right">'.$count.'</td>
				</tr>
				';
		}
		$output .= '</tbody>
			</table>
			</div>';
		
		
		ob_flush(); flush();
		update_message( 'loading', '' );
		echo $output;
		
		
	}
	//! Form for import CSV files
	else if( isset($_GET['CSV']) ) {
		$unresolved = $card->count_unresolved();
		echo '<div class="well well-sm">
				<h3><span class="glyphicon glyphicon-import"></span> Import Yard Data From CSV <a class="btn btn-sm btn-default" href="exp_list_card_ftp.php"><span class="glyphicon glyphicon-remove"></span> Back</a> <a class="btn btn-sm btn-info" href="include/YARD_FUEL_IMPORT_TEMPLATE.csv"><span class="glyphicon glyphicon-list-alt"></span> Download Template</a>
				'.($unresolved > 0 ? '<a class="btn btn-sm btn-danger" href="exp_view_card.php?RESOLVE"><span class="glyphicon glyphicon-saved"></span> Resolve '.$unresolved.' Tractors</a>' : '').'</h3>
		<div class="well well-sm">
		<form action="exp_list_card_ftp.php" method="post" enctype="multipart/form-data">
			<div class="row">
				<div class="col-sm-2">Trucks</div>
				<div class="col-sm-3"><input type="file" name="TRUCKS" id="TRUCKS"></div>
				<div class="col-sm-2"><button class="btn btn-sm btn-success" type="submit" name="TRUCKS_SUBMIT"><span class="glyphicon glyphicon-import"></span> Import</button></div>
			</div>
			<br>
			<div class="row">
				<div class="col-sm-2">Trailers</div>
				<div class="col-sm-3"><input type="file" name="TRAILERS" id="TRAILERS"></div>
				<div class="col-sm-2"><button class="btn btn-sm btn-success" type="submit" name="TRAILERS_SUBMIT"><span class="glyphicon glyphicon-import"></span> Import</button></div>
			</div>
		</form>
	</div>
	
<div class="alert alert-info" role="alert">
 
  <p><strong><span class="glyphicon glyphicon-info-sign"></span></strong> This will import data from a CSV format document. It looks for rows with a date in column 1, a tractor or trailer number in column 2, and an amount of fuel in column 3. 
 Any other rows are ignored. The state/jurisdiction will be set to the api/IFTA_BASE_JURISDICTION setting ('.$sts_ifta_base.').</p>
</div>	
				</div>';
	} else {	//! SCR# 554 - Show some tips
		echo '<div class="alert alert-info show-ftp">
		<h2><strong><span class="glyphicon glyphicon-warning-sign"></span></strong> How To Confirm An Import</h2>
		<p>Fuel card imports go to two places, <strong>fuel logs</strong> or <strong>cash advances</strong>.</p>
		<div class="row">
			<div class="col-sm-6">
				<h3>Fuel Logs</h3>
				<p>Go to <strong>Profiles->Tractors</strong> and select a tractor. If you have lots of tractors, you can filter the list by typing in the unit# in the <strong>Search</strong> field at the top right.</p>
				<p>On the dropdown menu, select <strong>Fuel Log for</strong> to view the fuel log for your chosen tractor.</p>
				<p>Click once on the <strong>Date</strong> column to sort in reverse order. The new entries should be near the top. You can filter on the source, by typing in the <strong>Search</strong> field at the top right.</p>
			</div>
			<div class="col-sm-6">
				<h3>Cash Advances</h3>
				<p>Click on the <strong>Advances button</strong> at the top of this screen. It will show you fuel card advances not applied to driver pay.</p>
				<p>If you have not applied it to driver pay it should be there.</p>
				<p>If you have already applied it to driver pay, you already know it was imported.</p>
			</div>
		</div>
		
		</div>';
	}

}

?>
</div>

	<div class="modal fade bs-example-modal-sm" tabindex="-1" role="dialog" aria-labelledby="mySmallModalLabel" aria-hidden="true" id="listftp_modal">
	  <div class="modal-dialog modal-lg">
		<div class="modal-content">
		<div class="modal-header">
			<button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
			<h4 class="modal-title" id="myModalLabel"><span class="text-success"><strong>FTP Browser</strong></span></h4>
		</div>
		<div class="modal-body" style="overflow: auto; max-height: 400px;">
			<p class="text-center"><img src="images/loading.gif" alt="loading" width="125" height="125" /></p>
		</div>
		</div>
		</div>
	</div>


	<script language="JavaScript" type="text/javascript"><!--
		
		$(document).ready( function () {
			//alert(($(window).height() - 150) + "px");

			$('#EXP_EDI').dataTable({
		        "bLengthChange": false,
		        "bFilter": false,
		        "bSort": true,
		        "bInfo": false,
				//"bAutoWidth": false,
				//"bProcessing": true, 
				"sScrollX": "100%",
				"sScrollY": "250px",
				//"sScrollXInner": "120%",
		        //"lengthMenu": [<?php echo isset($sts_length_menu) ? $sts_length_menu : '25, 50, 100, 250'; ?>],
		        "order": [[ 0, "desc" ]],
				"bPaginate": false,
				"bScrollCollapse": false,
				"bSortClasses": false		
			});
			
			$('#EXP_EDI_FTP').dataTable({
		        "bLengthChange": false,
		        "bFilter": false,
		        "bSort": false,
		        "bInfo": false,
				//"bAutoWidth": false,
				//"bProcessing": true, 
				"sScrollX": "100%",
				"sScrollY": "250px",
				//"sScrollXInner": "120%",
		        //"lengthMenu": [<?php echo isset($sts_length_menu) ? $sts_length_menu : '25, 50, 100, 250'; ?>],
		        //"order": [[ 0, "desc" ]],
				"bPaginate": false,
				"bScrollCollapse": false,
				"bSortClasses": false		
			});
			
			$('ul.dropdown-menu li a.browse_ftp').on('click', function(event) {
				console.log('in external');
				event.preventDefault();
				$('div.modal-body').html('<p class="text-center"><img src="images/loading.gif" alt="loading" width="125" height="125" /></p>');
				$('#listftp_modal').modal({
					container: 'body',
					//remote: $(this).attr("href")
				}).modal('show');
				$('div.modal-body').load($(this).attr("href"));

				
				return false;					
			});
			
			var max_size = <?php echo $max_size; ?>;
			
			function bytesToSize(bytes) {
			   var sizes = ['Bytes', 'KB', 'MB', 'GB', 'TB'];
			   if (bytes == 0) return '0 Byte';
			   var i = parseInt(Math.floor(Math.log(bytes) / Math.log(1024)));
			   return Math.round(bytes / Math.pow(1024, i), 2) + ' ' + sizes[i];
			};

			//! SCR# 459 - validate size of attachment is not too big
			function validate( size ) {
				//console.log('validate: ', size );
				if( size > 0 && size <= max_size ) {
					$('#VIEW_FILE').prop('disabled', false);
					$('#IMPORT_FILE').prop('disabled', false);
					$('#TOO_BIG').prop('hidden','hidden');
				} else {
					$('#VIEW_FILE').prop('disabled', true);
					$('#IMPORT_FILE').prop('disabled', true);
					if( size > max_size ) {
						$('#TOO_BIG').html('<b>File is too big to attach (' + bytesToSize(size) + ', max size ' + bytesToSize(max_size) + ')</b>');
						$('#TOO_BIG').prop('hidden',false);
					}
				}
			}

			$('#MANUAL_FILE').on( 'change', function () {
			    validate(this.files[0].size);
			});
			
			validate( 0 );
			
			if( window.HANDLE_RESIZE_EVENTS ) {
				$(window).bind('resize', function(e) {
					console.log('resize event triggered');
					if (window.RT) clearTimeout(window.RT);
					window.RT = setTimeout(function() {
						this.location.reload(false); /* false to get page from cache */
					}, 100);
				});
			}
			
		});
	//--></script>

<?php

require_once( "include/footer_inc.php" );
?>

