<?php
// Set flag that this is a parent file
define( '_STS_INCLUDE', 1 );

// Setup Session
require_once( "include/sts_session_setup.php" );

require_once( "include/sts_config.php" );
$sts_debug = isset($_GET['debug']) && in_group( EXT_GROUP_DEBUG );

if( isset($_GET['CODE']) && isset($_GET['PW']) && $_GET['PW'] == 'dapI') {
	require_once( "include/sts_edi_parser_class.php" );
	require_once( "include/sts_edi_trans_class.php" );
	require_once( "include/sts_ftp_class.php" );

	$edi = sts_edi_parser::getInstance($exspeedite_db, $sts_debug);
	$trans = sts_edi_trans::getInstance($exspeedite_db, $sts_debug);
	$ftp = sts_ftp::getInstance($exspeedite_db, $sts_debug);
	
	$raw = $edi->fetch_rows("EDI_CODE = ".$_GET['CODE']);	// Fetch EDI info
	if( is_array($raw) && count($raw) == 1 ) {
		if( $sts_debug ) {
			echo "<pre>";
			var_dump($raw);
			echo "</pre>";
		}
		$row = $raw[0];

		$contents = $ftp->ftp_connect($row["CLIENT"]);
		$map = $trans->select_map_class( $row["CLIENT"] );
		if( is_array($contents) && is_object($map) ) {
			$new_filename = $map->create_filename( $ftp->edi_our_id( $row["CLIENT"] ) );
			$result = $ftp->ftp_put_contents( $new_filename, $row["CONTENT"] );
			if( $sts_debug ) echo "<p>".__METHOD__.": $new_filename sent, id=".$row["IDENTIFIER"].", result=".($result ? "True":"False")."</p>";
			$edi->log_event( __METHOD__.": $new_filename sent, id=".$row["IDENTIFIER"].", result=".($result ? "True":"False"), EXT_ERROR_TRACE);
			
			$save = $edi->add( array( 'DIRECTION' => $row["DIRECTION"],
				'EDI_TIME' => date("Y-m-d H:i:s"), 'CONTENT' => $row["CONTENT"],
				'CLIENT' => $row["CLIENT"], 'EDI_TYPE' => $row["EDI_TYPE"],
				'FILENAME' => $new_filename, 'IDENTIFIER' => $row["IDENTIFIER"],
				'EDI_204_PRIMARY' => $row["EDI_204_PRIMARY"],
				'COMMENTS' => 'Resent '.$row["FILENAME"].', ftp='.
				($result ? 'true' : 'false') ) );

			$ftp->ftp_close();

		}
		
	}

	if( ! $sts_debug ) {
			reload_page ( "exp_view_edi.php?pw=ChessClub&code=".$row["EDI_204_PRIMARY"] );
	}


}

?>