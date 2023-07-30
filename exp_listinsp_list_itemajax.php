<?php 

// $Id: exp_listinsp_list_itemajax.php 4350 2021-03-02 19:14:52Z duncan $
// AJAX back end for list inspection list items

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
$my_session->access_check( EXT_GROUP_USER );	// Make sure we should be here

require_once( "include/sts_result_class.php" );
require_once( "include/sts_insp_list_item_class.php" );

$insp_list_item = sts_insp_list_item::getInstance( $exspeedite_db, $sts_debug );

// close the session here to avoid blocking
session_write_close();

if( $sts_debug ) {
	echo "<pre>";
	var_dump(empty($_GET), $_GET);
	var_dump(empty($_POST), $_POST);
	echo "</pre>";
}

if( empty($_GET) && ! empty($_POST) ) {
	if( ! empty($_POST["action"]) && ! empty($_POST["data"])) {
		$response = array('error' => 'unspecified error');
		switch( $_POST["action"] ) {
			case 'create':
				break;
			case 'edit':
				foreach( $_POST["data"] as $key => $value ) {
					$insp_list_item->update($key, array("SEQUENCE_NO" => $value["SEQUENCE_NO"]));
					$keys[] = $key;
				}
				$match = "ITEM_CODE IN (".implode(', ', $keys).")";
				/*
				$res = $insp_list_item->fetch_rows($match, "SEQUENCE_NO, ITEM_CODE, ITEM_TARGET,
					ITEM_TYPE, ITEM_TEXT, ITEM_EXTRA, CHANGED_DATE, CHANGED_BY", "SEQUENCE_NO ASC");
				$data = array();
				foreach( $res as $row ) {
					$data[] = 
				}
				*/
				
				$rslt = new sts_result( $insp_list_item, $match, $sts_debug );
				
				$response =  $rslt->render_ajax( $sts_result_insp_list_item_layout, $sts_result_insp_list_item_edit );
				$response = array( "data" => $response["data"]);
				break;
			case 'remove':				
				break;
			default:
		}
	}
} else {
	$match = isset($_GET["match"]) && $_GET["match"] <> '' ? urldecode($_GET["match"]) : false;
	
	$rslt = new sts_result( $insp_list_item, $match, $sts_debug );
	
	$response =  $rslt->render_ajax( $sts_result_insp_list_item_layout, $sts_result_insp_list_item_edit, $_GET );
}

if( $sts_debug ) {
		echo "<p>response = </p><pre>";
		var_dump($response);
		echo "</pre>";
} else {
	echo json_encode( $response );
}
?>

