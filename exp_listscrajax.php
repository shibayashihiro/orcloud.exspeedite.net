<?php 
// Set flag that this is a parent file
define( '_STS_INCLUDE', 1 );

// Setup Session
require_once( "include/sts_session_setup.php" );

require_once( "include/sts_config.php" );
$sts_debug = isset($_GET['debug']) && in_group( EXT_GROUP_DEBUG );
require_once( "include/sts_session_class.php" );

$my_session = sts_session::getInstance( $exspeedite_db, $sts_debug );
$my_session->access_check( EXT_GROUP_ADMIN );	// Make sure we should be here

require_once( "include/sts_result_class.php" );
require_once( "include/sts_scr_class.php" );

if( $sts_debug ) {
		echo "<p>INPUT = </p><pre>";
		var_dump($_GET);
		echo "</pre>";
}

$scr_table = sts_scr::getInstance($exspeedite_db, $sts_debug);

// close the session here to avoid blocking
session_write_close();

$match = isset($_GET["match"]) && $_GET["match"] <> '' ? urldecode($_GET["match"]) : false;
if( isset($_GET["sort"]) && $_GET["sort"] <> '' )
	$sts_result_scr_edit['sort'] = urldecode($_GET["sort"]);

$rslt = new sts_result( $scr_table, $match, $sts_debug );

$response =  $rslt->render_ajax( $sts_result_scr_layout, $sts_result_scr_edit, $_GET );

if( $sts_debug ) {
		echo "<p>response = </p><pre>";
		// Issue with encoding of text fields from the database, likely a conversion issue.
		var_dump($response, json_encode($response), json_last_error_msg());
		//for( $c = 0; $c < count($response['data']); $c++) {
			$c = 22;
			$x = $response['data'][$c];
			var_dump($c, $x, json_encode($x), json_last_error_msg());
			var_dump($x['TITLE'], json_encode($x['TITLE']), json_last_error_msg());
			//var_dump($x['DT_RowAttr']['DESCRIPTION'], json_encode($x['DT_RowAttr']['DESCRIPTION']), json_last_error_msg());
		//}
		echo "</pre>";
} else {
	echo json_encode( $response );
}
?>

