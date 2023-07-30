<?php 

// $Id: exp_view_card.php 5110 2023-05-22 17:36:50Z duncan $
// - View/import Fuel card data

// Set flag that this is a parent file
define( '_STS_INCLUDE', 1 );

// Setup Session
require_once( "include/sts_session_setup.php" );

require_once( "include/sts_config.php" ); // true; // 
$sts_debug = (isset($_GET['debug'])  || isset($_POST['debug'])) && in_group( EXT_GROUP_DEBUG );
require_once( "include/sts_session_class.php" );

set_time_limit(120);
ini_set('memory_limit', '1024M');

$my_session = sts_session::getInstance( $exspeedite_db, $sts_debug );
$my_session->access_check( $sts_table_access[CARD_FTP_TABLE] );	// Make sure we should be here

if( $sts_debug ) {
	echo "<pre>POST, GET, FILES variables\n";
	var_dump($_POST);
	var_dump($_GET);
	var_dump($_FILES);
	echo "</pre>";
}

//! SCR# 554 - Add more visibility to import process
function show_stats( $file, $vendor, $result, $stats ) {
					
	echo '<h2>Import Of '.$file.' from '.$vendor.' '.($result ? 'Completed' : 'Failed').'</h2>';
	echo '
		<div class="row">
			<div class="col-sm-6 well well-sm"><h3 class="text-center">IFTA Imports</h3>
				<div class="row">
					<div class="col-sm-4 text-right"><h4>Imports</h4></div>
					<div class="col-sm-4 text-right"><h4>Errors</h4></div>
					<div class="col-sm-4 text-right"><h4>Duplicates</h4></div>
				</div>
				<div class="row">
					<div class="col-sm-4 text-right"><h4>'.$stats['ifta_import'].'</h4></div>
					<div class="col-sm-4 text-right"><h4>'.$stats['ifta_import_error'].'</h4></div>
					<div class="col-sm-4 text-right"><h4>'.$stats['ifta_duplicate'].'</h4></div>
				</div>
			</div>
			<div class="col-sm-6 well well-sm"><h3 class="text-center">Cash Advance Imports</h3>
				<div class="row">
					<div class="col-sm-4 text-right"><h4>Imports</h4></div>
					<div class="col-sm-4 text-right"><h4>Errors</h4></div>
					<div class="col-sm-4 text-right"><h4>Duplicates</h4></div>
				</div>
				<div class="row">
					<div class="col-sm-4 text-right"><h4>'.$stats['advance_import'].'</h4></div>
					<div class="col-sm-4 text-right"><h4>'.$stats['advance_import_error'].'</h4></div>
					<div class="col-sm-4 text-right"><h4>'.$stats['advance_duplicate'].'</h4></div>
				</div>
			</div>
		</div>
	';
					
	if( $stats['ifta_import_error'] > 0 || $stats['advance_import_error'] > 0 ) {
		echo '<h3>There were issues importing into Exspeedite.<br>
		Please contact Exspeedite support.<br>
		Include a screen capture or reference file '.$file.' from '.$vendor.'</h3>';
	} else if( ($stats['ifta_import'] == 0 && $stats['ifta_duplicate'] > 0) ||
		($stats['advance_import'] == 0 && $stats['advance_duplicate'] > 0) ) {
		echo '<h3>It looks like you already imported this file.<br>
		All duplicates means the data is already in Exspeedite.</h3>';
	}
}

if( isset($_POST['IFTA_LOG_CODE']) && isset($_POST['SOURCE']) && isset($_POST['CD_UNIT']) ) {
	echo '<br><br><center><p class="text-center"><img src="images/loading.gif" alt="loading" width="125" height="125" /></p></center>';
	require_once( "include/sts_card_ftp_class.php" );
	require_once( "include/sts_card_class.php" );

	$ftp = sts_card_ftp::getInstance($exspeedite_db, $sts_debug);
	$card = sts_card::getInstance($exspeedite_db, $sts_debug, $ftp );

	if( isset($_POST['save']) && isset($_POST['TRACTOR_CODE']) ) {
		$card->resolve( $_POST['IFTA_LOG_CODE'], $_POST['SOURCE'], $_POST['CD_UNIT'], $_POST['TRACTOR_CODE'] );
	} else if( isset($_POST['discard']) ) {
		$card->discard( $_POST['IFTA_LOG_CODE'], $_POST['CD_UNIT'] );
	} else if( isset($_POST['discard_all']) ) {
		$card->discard_all( $_POST['CD_UNIT'] );
 	}
	if( ! $sts_debug )
		reload_page ( "exp_view_card.php?RESOLVE" );

} else if( isset($_GET['RESOLVE']) ) { //! RESOLVE - Resolve tractor
	require_once( "include/sts_card_ftp_class.php" );
	require_once( "include/sts_card_class.php" );

	$ftp = sts_card_ftp::getInstance($exspeedite_db, $sts_debug);
	$card = sts_card::getInstance($exspeedite_db, $sts_debug, $ftp );

	if( $check = $card->find_log_to_resolve() ) {
		$sts_subtitle = "Resolve Unit ".$check['CD_UNIT']." to Tractor";
		require_once( "include/header_inc.php" );
		require_once( "include/navbar_inc.php" );
		
		?>
		<div class="container" role="main">
		<?php
		
		echo '<div class="well well-md">
				<h3>Data To Resolve From '.$check['CD_ORIGIN'].'</h3>
				'.$card->dump_row( $check ).'
				<form role="form" class="form-horizontal" action="exp_view_card.php" 
				method="post" enctype="multipart/form-data" 
				name="carrier" id="resolve">
				<input name="IFTA_LOG_CODE" id="IFTA_LOG_CODE" type="hidden" value="'.$check['IFTA_LOG_CODE'].'">
				<input name="SOURCE" id="CD_ORIGIN" type="hidden" value="'.$check['CD_ORIGIN'].'">
				<input name="CD_UNIT" id="CD_UNIT" type="hidden" value="'.$check['CD_UNIT'].'">
				'.($sts_debug ? '<input name="debug" type="hidden" value="on">' : '').'
			';
		if( $check['MENU'] === false ) {
			echo '<h3 class="text-danger"><span class="glyphicon glyphicon-warning-sign"></span> No Available Tractors To Match This Data</h3>
				<div class="row">
				<div class="col-sm-12">
					<h4>This is likely because you have not enabled IFTA logging for various tractors, or the tractor does not exist in Exspeedite. <br>Go to <a href="exp_listtractor.php" class="btn btn-sm btn-success">Tractor Profiles</a> and enable IFTA for relevant tractors. Then come back to resolve this data.</h4>
					<div class="btn-group" role="group">
						<button class="btn btn-md btn-danger" name="discard" type="submit" ><span class="glyphicon glyphicon-remove"></span> Discard Data</button>
						<a class="btn btn-md btn-default" href="exp_list_card_ftp.php?DL='.$check['CD_ORIGIN'].'"><span class="glyphicon glyphicon-remove"></span> Back</a>
				</div>
				</div>
			';
		} else {	
				
			echo '<h3>Which tractor matches '.$check['CD_ORIGIN'].' unit '.$check['CD_UNIT'].'</h3>
			<div class="form-group">
				<div class="col-sm-4">
			'.$check['MENU'].'
				</div>
				<div class="col-sm-8">
					<div class="btn-group" role="group">
						<button class="btn btn-md btn-success" name="save" id="save" type="submit" ><span class="glyphicon glyphicon-ok"></span> Assign Tractor</button>
						<button class="btn btn-md btn-danger" name="discard" id="discard"
						type="submit"><span class="glyphicon glyphicon-remove"></span> Discard Data</button>
						<button class="btn btn-md btn-danger" name="discard_all" id="discard_all" type="submit" ><span class="glyphicon glyphicon-remove"></span> Discard ALL Data for '.$check['CD_UNIT'].'</button>
						<a class="btn btn-md btn-default" href="exp_list_card_ftp.php?DL='.$check['CD_ORIGIN'].'"><span class="glyphicon glyphicon-remove"></span> Back</a>
					</div>
				</div>
				';
			}
			echo '</div>
			</form>
			</div>
			
	<script language="JavaScript" type="text/javascript"><!--
		$(document).ready( function () {

		    $("#discard, #discard_all").click(function (e) {
		    	e.preventDefault();
		    	var missing = this.id;
		        bootbox.confirm({
					title: \'<h3><img src="images/EXSPEEDITEsmr.png" height="32" alt="<?php echo $sts_title ?>"> Confirm Action</h3>\',
					message: "Are you sure you want to discard fuel card data?<br>There is no undo.",
					closeButton: false,
					buttons: {
						confirm: {
							label: \'<span class="glyphicon glyphicon-ok"></span> Confirm\',
							className: \'btn-danger\',
						},
						cancel: {
							label: \'<span class="glyphicon glyphicon-remove"></span> Cancel\',
							className: \'btn-default\'
						}
					},
					callback: function(result) {
			            if (result) {
				            $("<input />").attr("type", "hidden")
						        .attr("name", missing)
						        .attr("value", missing)
						        .appendTo("#resolve");
				            $("#resolve").submit();
			            }
					}
		        });
		    });


			$("#resolve2").submit(function() {
				var btn = $(this).find("button[type=submit]:focus" );
				console.log( btn );
				//console.log( btn.context.attributes.id );
				//if( btn.context.attributes.id == "save" ) return true;
			    var c = confirm("Click OK to continue?\nNO UNDO");
			    return c; //you can just return c because it will be true or false
			});
		});
	//--></script>


			';
		
	} else {
		// Nothing to resolve, try cash advances
		if( ! $sts_debug )
			reload_page ( "exp_resolve_advance.php?RESOLVE" );
	}

} else if( isset($_POST['CLIENT']) && isset($_FILES["MANUAL_FILE"]) ) { //! MANUAL import file
	$sts_subtitle = "Fuel Card ".(isset($_FILES["FILE_NAME"]) ? $_FILES["FILE_NAME"] : '');
	require_once( "include/header_inc.php" );
	require_once( "include/navbar_inc.php" );
	
	require_once( "include/sts_card_ftp_class.php" );		// FTP Transport
	require_once( "include/sts_card_manual_class.php" );	// Manual Transport

	// Turn off output buffering
	ini_set('output_buffering', 'off');
	// Implicitly flush the buffer(s)
	ini_set('implicit_flush', true);
	ob_implicit_flush(true);

	if (ob_get_level() == 0) ob_start();

	$ftp = sts_card_ftp::getInstance($exspeedite_db, $sts_debug);			// FTP Transport
	$manual = sts_card_manual::getInstance($exspeedite_db, $sts_debug);		// Manual Transport

	$ftp->client_open( $_POST['CLIENT'] );	// Get client info
	$transport = $ftp->transport( $_POST['CLIENT'] );	// Get transport
	
	if( $transport == 'MANUAL' ) {
		if( isset($_POST['IMPORT_FILE']) ) {	//! IMPORT_FILE - Manual Import fuel card file
	
			echo '<div class="container" role="main">
			<div id="loading"><h2 class="text-center"><img src="images/loading.gif" alt="loading" width="125" height="125" /><br>Importing '.$_FILES["MANUAL_FILE"]["name"].' from '.$_POST['CLIENT'].'...</h2></div>';
		
			ob_flush(); flush();
	
			// Suck into memory
			$raw = $manual->manual_get_contents($_FILES["MANUAL_FILE"]["tmp_name"]);
			if( $sts_debug ) echo '<p><b>raw = '.strlen($raw).' bytes</b></p>';
			if( ! empty($raw)) {
				$ftp->parse( $raw );
				$result = $ftp->import( $_POST['CLIENT'] );
				if( $sts_debug ) echo "<p>".__FILE__.": import returned ".($result ? "true" :"false")."</p>";
				//! SCR# 554 - Add more visibility to import process
				$stats = $ftp->get_import_stats();
				update_message( 'loading', '' );
				show_stats( $_FILES["MANUAL_FILE"]["name"], $_POST['CLIENT'], $result, $stats );
					
			}		
			
			// Now resolve unit numbers to Tractors
			echo '<div class="row">
				<div class="col-sm-4 col-sm-offset-4">
					<a href="exp_view_card.php?RESOLVE" class="btn btn-md btn-success"><span class="glyphicon glyphicon-arrow-right"></span> Continue</a>
					<a class="btn btn-md btn-default" href="exp_list_card_ftp.php?DL='.$_POST['CLIENT'].'"><span class="glyphicon glyphicon-remove"></span> Back</a>
				</div>
			</div>
			';
			//if( ! $sts_debug )
			//	reload_page ( "exp_view_card.php?RESOLVE" );
	
		} else if( isset($_POST['VIEW_FILE']) ) {	//! VIEW - Manual View fuel card file
			echo '<div class="container-full" role="main">';
			// Suck into memory
			$raw = $manual->manual_get_contents($_FILES["MANUAL_FILE"]["tmp_name"]);
			if( $sts_debug ) echo '<p><b>raw = '.strlen($raw).' bytes</b></p>';
			if( ! empty($raw)) {
				$ftp->parse( $raw );
				echo $ftp->dump( $_POST['CLIENT'], 'exp_list_card_ftp.php?DL='.$_POST['CLIENT'] );
			}
		}
	}
	
} else if( isset($_GET['IMPORT']) && isset($_GET['BVD']) &&
	isset($_GET['START']) && isset($_GET['END']) ) {
	//! BVD Import from BVD START, END
	$sts_subtitle = "BVD Fuel Card import ";
	require_once( "include/header_inc.php" );
	require_once( "include/navbar_inc.php" );

	require_once( "include/sts_card_bvd_class.php" );

	// Turn off output buffering
	ini_set('output_buffering', 'off');
	// Implicitly flush the buffer(s)
	ini_set('implicit_flush', true);
	ob_implicit_flush(true);

	if (ob_get_level() == 0) ob_start();

		echo '<div class="container" role="main">
		<div id="loading"><h2 class="text-center"><img src="images/loading.gif" alt="loading" width="125" height="125" /><br>BVD Importing '.$_GET['BVD'].' from '.$_GET['START'].' to '.$_GET['END'].'...</h2></div>';
	
		ob_flush(); flush();
	
	$bvd_transport = sts_card_bvd_transport::getInstance($exspeedite_db, $sts_debug);
	$bvd = sts_card_bvd::getInstance($exspeedite_db, $sts_debug);
	$info = $bvd_transport->bvd_fetch( $_GET['BVD'], $_GET['START'], $_GET['END'] );

	$result = $bvd->import( $info );

	$stats = $bvd->get_import_stats();
	update_message( 'loading', '' );
	show_stats( $_GET['BVD'], 'BVD', $result, $stats );

	// Now resolve unit numbers to Tractors
	echo '<div class="row">
		<div class="col-sm-4 col-sm-offset-4">
			<a href="exp_view_card.php?RESOLVE" class="btn btn-md btn-success"><span class="glyphicon glyphicon-arrow-right"></span> Continue</a>
				<a class="btn btn-md btn-default" href="exp_list_card_ftp.php?BVD='.$_GET['BVD'].'"><span class="glyphicon glyphicon-remove"></span> Back</a>
		</div>
	</div>
	';
	
} else if( isset($_GET['CODE']) && isset($_GET['FILE']) ) {
	$sts_subtitle = "Fuel Card ".(isset($_GET['FILE']) ? $_GET['FILE'] : '');
	require_once( "include/header_inc.php" );
	require_once( "include/navbar_inc.php" );
	
	require_once( "include/sts_card_ftp_class.php" );
	
	// Turn off output buffering
	ini_set('output_buffering', 'off');
	// Implicitly flush the buffer(s)
	ini_set('implicit_flush', true);
	ob_implicit_flush(true);

	if (ob_get_level() == 0) ob_start();

	$ftp = sts_card_ftp::getInstance($exspeedite_db, $sts_debug);

	if( isset($_GET['IMPORT']) ) {	//! IMPORT - Import fuel card file
	
		echo '<div class="container" role="main">
		<div id="loading"><h2 class="text-center"><img src="images/loading.gif" alt="loading" width="125" height="125" /><br>Importing '.$_GET['FILE'].' from '.$_GET['CODE'].'...</h2></div>';
	
		ob_flush(); flush();
	
		$ftp->client_open( $_GET['CODE'] );	// Get client info
		$transport = $ftp->transport( $_GET['CODE'] );	// Get transport
		
		if( $transport == 'FTP' ) {
			$files = $ftp->ftp_connect( $_GET['CODE'] );
			if( is_array($files) && in_array($_GET['FILE'], $files)) {
				$raw = $ftp->ftp_get_contents( $_GET['FILE'] );
				if( ! empty($raw)) {
					$ftp->parse( $raw );
					$result = $ftp->import( $_GET['CODE'] );
					if( $sts_debug ) echo "<p>".__FILE__.": import returned ".($result ? "true" :"false")."</p>";
					
					//! SCR# 554 - Add more visibility to import process
					$stats = $ftp->get_import_stats();
					update_message( 'loading', '' );
					show_stats( $_GET['FILE'], $_GET['CODE'], $result, $stats );
					
					$delete_enabled = $ftp->delete_after_import( $_GET['CODE'] );
					if( $delete_enabled && $result ) {
						$result = $ftp->ftp_delete_file( $_GET['FILE'] );
						echo '<h3>File '.$_GET['FILE'].' '.($result ? 'deleted' : 'delete failed').'.</h3>';
						
						if( $sts_debug ) echo "<p>".__FILE__.": ftp_delete_file returned ".($result ? "true" :"false")."</p>";
						if( ! $result ) {
							echo "<pre>";
							var_dump($result, error_get_last());
							echo "</pre>";
							die;
						}
					} else {
						echo '<h3>File '.$_GET['FILE'].' NOT deleted. (delete after import is '.($delete_enabled ? 'enabled' : 'not enabled').')</h3>
		';
						if( ($stats['ifta_import'] == 0 && $stats['ifta_duplicate'] > 0) ||
							($stats['advance_import'] == 0 && $stats['advance_duplicate'] > 0) ) {
							echo '<h3>If you want to delete this file, you can do so here:
<a class="btn btn-md btn-danger" onclick="confirmation(\'Confirm: Delete import file '.$_GET['FILE'].'?<br>Exspeedite will FTP into the remote server and delete it.<br>There is no undo.\',
									\'exp_view_card.php?DELETE&CODE='.$_GET['CODE'].'&FILE='.$_GET['FILE'].'\')"><span class="glyphicon glyphicon-trash"></span> Delete '.$_GET['FILE'].'</a></h3>';
						}
									
					}
				} else {
					echo '<h2><strong>Error:</strong> empty file '.$_GET['FILE'].' or download error '.$ftp->last_error.'</h2>';
					$ftp->log_event(__FILE__.': Error: empty file '.$_GET['FILE'].' or download error '.$ftp->last_error);
				}
			} else {
				echo '<h2><strong>Error:</strong> file '.$_GET['FILE'].' not found</h2>';
				$ftp->log_event(__FILE__.': Error: file '.$_GET['FILE'].' not found');
			}
		} else {
			echo '<h2><strong>Error:</strong> transport is not FTP</h2>';
			$ftp->log_event(__FILE__.': Error: transport is not FTP');
		}
		echo '</div>';
		
		// Now resolve unit numbers to Tractors
		echo '<div class="row">
			<div class="col-sm-4 col-sm-offset-4">
				<a href="exp_view_card.php?RESOLVE" class="btn btn-md btn-success"><span class="glyphicon glyphicon-arrow-right"></span> Continue</a>
					<a class="btn btn-md btn-default" href="exp_list_card_ftp.php?DL='.$_GET['CODE'].'"><span class="glyphicon glyphicon-remove"></span> Back</a>
			</div>
		</div>
		';
		//if( ! $sts_debug )
		//	reload_page ( "exp_view_card.php?RESOLVE" );
		
	//! SCR# 430 - Fuel card import enhancements, delete functionality
	} else if( isset($_GET['DELETE']) ) {	//! DELETE - Delete fuel card file
		$files = $ftp->ftp_connect( $_GET['CODE'] );
		if( is_array($files) && in_array($_GET['FILE'], $files)) {
			$result = $ftp->ftp_delete_file( $_GET['FILE'] );
		}
		reload_page ( "exp_list_card_ftp.php?DL=".$_GET['CODE'] );
		
	} else if( isset($_GET['VIEW']) ) {	//! VIEW - View fuel card file
		echo '<div class="container-full" role="main">
		<div id="loading"><h2 class="text-center"><img src="images/loading.gif" alt="loading" width="125" height="125" /><br>Fetching '.$_GET['FILE'].' from '.$_GET['CODE'].'...</h2></div>';
		ob_flush(); flush();
		
		$ftp->client_open( $_GET['CODE'] );	// Get client info
		$transport = $ftp->transport( $_GET['CODE'] );	// Get transport
		
		if( $transport == 'FTP' ) {
			$files = $ftp->ftp_connect( $_GET['CODE'] );
			if( is_array($files) && in_array($_GET['FILE'], $files)) {
				$raw = $ftp->ftp_get_contents( $_GET['FILE'] );
				update_message( 'loading', '' );
				ob_flush(); flush();

				if( $sts_debug ) {
					echo "<pre>raw file:\n";
					var_dump($raw);
					echo "</pre>";
				}
				if( ! empty($raw)) {
					echo "<h3>View Raw File ".$_GET['FILE']." From ".$_GET['CODE']."</h3>
					<pre>\n".$raw."\n</pre>";
					
					$ftp->parse( $raw );
					echo $ftp->dump( $_GET['CODE'], 'exp_list_card_ftp.php?DL='.$_GET['CODE'] );
				} else {
					echo '<h2><strong>Error:</strong> empty file '.$_GET['FILE'].' or download error '.$ftp->last_error.'</h2>';
					$ftp->log_event(__FILE__.': Error: empty file '.$_GET['FILE'].' or download error '.$ftp->last_error);
				}
			} else {
				echo '<h2><strong>Error:</strong> file '.$_GET['FILE'].' not found</h2>';
				$ftp->log_event(__FILE__.': Error: file '.$_GET['FILE'].' not found');
			}
		} else {
			echo '<h2><strong>Error:</strong> transport is not FTP</h2>';
			$ftp->log_event(__FILE__.': Error: transport is not FTP');
		}
		
	}
}


require_once( "include/footer_inc.php" );
?>



