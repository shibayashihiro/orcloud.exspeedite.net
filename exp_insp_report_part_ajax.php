<?php 

// $Id: exp_insp_report_part_ajax.php 4350 2021-03-02 19:14:52Z duncan $
// AJAX back end for parts item

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
$my_session->access_check( EXT_GROUP_MECHANIC, EXT_GROUP_ADMIN );	// Make sure we should be here

require_once( "include/sts_insp_report_class.php" );

$insp_report = sts_insp_report::getInstance( $exspeedite_db, $sts_debug );
$insp_part = sts_insp_report_part::getInstance( $exspeedite_db, $sts_debug );

// close the session here to avoid blocking
session_write_close();

if( isset($_GET["pw"]) && $_GET["pw"] == "GoFish" && ! empty($_GET["item"])) {
	if( isset($_GET["add"]) ) {
		$part = $insp_part->add(array('ITEM_CODE' => $_GET["item"]));		// Add new part
		$new = $insp_part->fetch_rows("PART_CODE = ".$part);
		if( is_array($new) )
			$response = $insp_report->get_part_html( $_GET["item"], $new[0] );	// HTML for part
		else
			$response = '<tr><td colspan="6">Error: exp_insp_report_part_ajax</td></tr>';
	} else
	if( isset($_GET["del"]) ) {
		$check = $insp_part->delete_row("PART_CODE = ".$_GET["del"] );
		$response = $check ? 'OK' : 'ERROR';
	}
	
	
	if( $sts_debug ) {
			echo "<p>response = </p><pre>";
			var_dump($response);
			echo "</pre>";
	} else {
		echo json_encode( $response );
	}
}

?>

