<?php 

// $Id: exp_compressattachment.php 5030 2023-04-12 20:31:34Z duncan $
// Compress attachment
// CODE is the ATTACHMENT_CODE of the attachment
// BATCH is for batch compression
// BATCH=n size of batch

// Set flag that this is a parent file
define( '_STS_INCLUDE', 1 );

// Set flag that this is session readonly
define( '_STS_SESSION_READONLY', 1 );

// Set flag that this is an ajax call
define( '_STS_SESSION_AJAX', 1 );

// Setup Session
require_once( "include/sts_session_setup.php" );

require_once( "include/sts_config.php" );
$sts_debug = isset($_GET['debug']) && in_group( EXT_GROUP_DEBUG );
require_once( "include/sts_session_class.php" );

$my_session = sts_session::getInstance( $exspeedite_db, $sts_debug );

$my_session->access_check( $sts_table_access[ATTACHMENT_TABLE] );	// Make sure we should be here

require_once( "include/sts_attachment_class.php" );

$attachment_table = sts_attachment::getInstance($exspeedite_db, $sts_debug);

if( isset($_GET['AUDIT']) ) {
	$sts_subtitle = "Audit Attachments";
	require_once( "include/header_inc.php" );
	echo '<div class="container" role="main">
		<h2>Audit Attachements</h2>
		';
	
	$attachment_table->audit();
	
	echo '</div>
	';
} else
if( isset($_GET['ORPHANS']) ) {
	$my_session->access_check( EXT_GROUP_ADMIN );	// Only for Admin
	set_time_limit(0);
	ini_set('memory_limit', '1024M');
	die('<p>ORPHANS no longer works</p>');

	$sts_subtitle = "Find Orphan Attachments";
	require_once( "include/header_inc.php" );

	$orphans = $attachment_table->orphans();

	echo '<div class="container" role="main">
		<h2>There Are '.count($orphans).' Orphan attachments</h2>';

	if( count($orphans) > 0 ) {
		$total = 0;
		foreach($orphans as $row) {
			echo '<p>'.$row[0].' '.sts_result::formatBytes($row[1]).'</p>';
			$total += $row[1];
			if( isset($_GET['DEL']) )
				unlink($row[0]);
		}
		echo '<p>Total size: '.sts_result::formatBytes($total).'</p>';
		if( isset($_GET['DEL']) )
			echo '<p>Removed.</p>';
	}
	
} else if( isset($_GET['MOVE']) ) {
	$my_session->access_check( EXT_GROUP_ADMIN );	// Only for Admin
	set_time_limit(0);
	ini_set('memory_limit', '1024M');

	$sts_subtitle = "Batch Move To FilesAnywhere";
	require_once( "include/header_inc.php" );

	$size = is_numeric($_GET['MOVE']) ? intval($_GET['MOVE']) : 0;
	list($total, $candidates) = $attachment_table->unmoved_attachment( $size );

	echo '<div class="container" role="main">
		<h2>There Are '.$total.' Possible Attachments To Move To FilesAnywhere</h2>';
	
	if( $size > 0 ) {
		echo '<h4>Batch size '.$size.', returned '.count($candidates).' records.</h4>';
		
		// Turn off output buffering
		ini_set('output_buffering', 'off');
		// Implicitly flush the buffer(s)
		ini_set('implicit_flush', true);
		ob_implicit_flush(true);
	
		if (ob_get_level() == 0) ob_start();

		foreach($candidates as $row) {
			echo '<p>Moving attachment '.$row.' ... ';
			ob_flush(); flush();
			
			if( $attachment_table->moveto_filesanywhere( $row ) ) 
				echo ' moved</p>';
			else
				echo ' NOT moved</p>';
		}
		
		echo '<h4>Done.</h4>';

	}

	require_once( "include/footer_inc.php" );
} else if( isset($_GET['BATCH']) ) {
	$my_session->access_check( EXT_GROUP_ADMIN );	// Only for Admin
	set_time_limit(0);
	ini_set('memory_limit', '1024M');

	$sts_subtitle = "Batch Compress PDFs";
	require_once( "include/header_inc.php" );

	$size = is_numeric($_GET['BATCH']) ? intval($_GET['BATCH']) : 0;
	list($total, $candidates) = $attachment_table->uncompressed_pdf( $size );

	echo '<div class="container" role="main">
		<h2>There Are '.$total.' Possible PDFs To Compress</h2>';
	
	if( $size > 0 ) {
		echo '<h4>Batch size '.$size.', returned '.count($candidates).' records.</h4>';
		
		// Turn off output buffering
		ini_set('output_buffering', 'off');
		// Implicitly flush the buffer(s)
		ini_set('implicit_flush', true);
		ob_implicit_flush(true);
	
		if (ob_get_level() == 0) ob_start();

		foreach($candidates as $row) {
			echo '<p>Compressing attachment '.$row.' ... ';
			ob_flush(); flush();
			
			$saved = $attachment_table->compress( $row );
			
			echo ' saved '.sts_result::formatBytes($saved).'</p>';
		}
		
		echo '<h4>Done.</h4>';

	}

	require_once( "include/footer_inc.php" );
} else if( isset($_GET['CODE']) ) {
	
	$attachment_table->compress( $_GET['CODE'] );
	
	$check = $attachment_table->fetch_rows( "ATTACHMENT_CODE = ".$_GET['CODE'],
		"SOURCE_CODE, SOURCE_TYPE" );
	
	if( ! $sts_debug &&
		is_array($check) &&
		count($check) == 1 &&
		isset($check[0]["SOURCE_CODE"])) {
		switch( $check[0]["SOURCE_TYPE"] ) {
			case 'driver':
				reload_page ( "exp_editdriver.php?CODE=".$check[0]["SOURCE_CODE"] );

			case 'carrier':
				reload_page ( "exp_editcarrier.php?CODE=".$check[0]["SOURCE_CODE"] );

			//! SCR# 193 - add attachments to tractor and trailer
			case 'tractor':
				reload_page ( "exp_edittractor.php?CODE=".$check[0]["SOURCE_CODE"] );

			case 'trailer':
				reload_page ( "exp_edittrailer.php?CODE=".$check[0]["SOURCE_CODE"] );

			case 'client':
				reload_page ( "exp_editclient.php?CODE=".$check[0]["SOURCE_CODE"] );

			case 'company':
				reload_page ( "exp_editcompany.php?CODE=".$check[0]["SOURCE_CODE"] );

			case 'office':
				reload_page ( "exp_editoffice.php?CODE=".$check[0]["SOURCE_CODE"] );

			case 'shipment':
				reload_page ( "exp_addshipment.php?CODE=".$check[0]["SOURCE_CODE"] );
								
			case 'load':
				reload_page ( "exp_viewload.php?CODE=".$check[0]["SOURCE_CODE"] );

			case 'scr':
				reload_page ( "exp_editscr.php?CODE=".$check[0]["SOURCE_CODE"] );

			case 'insp_report':
				$referer = "exp_addinsp_report.php?REPORT=".$check[0]["SOURCE_CODE"];
				if( isset($_SESSION["REPORT_REFERRER"])) {
					$referer .= '&REFERER='.$_SESSION["REPORT_REFERRER"];
					unset($_SESSION["REPORT_REFERRER"]);
				}
				reload_page ( $referer );
		}
		
	}
}
?>