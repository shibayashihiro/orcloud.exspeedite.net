<?php 
// $Id: exp_state_dot.php 4350 2021-03-02 19:14:52Z duncan $
// Create DOT format files for Exspeedite state transition tables.

// Set flag that this is a parent file
define( '_STS_INCLUDE', 1 );

// Setup Session
require_once( "include/sts_session_setup.php" );

require_once( "include/sts_config.php" );
$sts_debug = isset($_GET['debug']) && in_group( EXT_GROUP_DEBUG );
require_once( "include/sts_session_class.php" );

$my_session = sts_session::getInstance( $exspeedite_db, $sts_debug );
$my_session->access_check( EXT_GROUP_ADMIN );	// Make sure we should be here

require_once( "include/sts_status_codes_class.php" );

$sc = sts_status_codes::getInstance($exspeedite_db, $sts_debug);

$source = 'load';
if( isset($_GET["source"]))
	$source = $_GET["source"];

$result = $sc->fetch_rows( "SOURCE_TYPE = '".$source."'", "STATUS_CODES_CODE, STATUS_STATE, PREVIOUS" );
	
echo "digraph G {<br>";
foreach( $result as $row ) {
	echo "   ".$row["STATUS_CODES_CODE"].' [label="'.$row["STATUS_CODES_CODE"].': '.$row["STATUS_STATE"].'"];<br>';
}
foreach( $result as $row ) {
	if( $row["PREVIOUS"] <> '' ) {
		$previous = explode(',', $row["PREVIOUS"]);
		foreach( $previous as $prev) {
			echo "   ".$prev.' -> '.$row["STATUS_CODES_CODE"].';<br>';
		}
	}
}
echo "}<br>";

?>