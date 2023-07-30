<?php 

// $Id: exp_editifta_ajax.php 2634 2017-10-13 16:05:14Z duncan $
// AJAX back end for edit IFTA screen

// Set flag that this is a parent file
define( '_STS_INCLUDE', 1 );

// Setup Session
require_once( "include/sts_session_setup.php" );

require_once( "include/sts_config.php" );
$sts_debug = isset($_GET['debug']) && in_group( EXT_GROUP_DEBUG );
require_once( "include/sts_session_class.php" );

$my_session = sts_session::getInstance( $exspeedite_db, $sts_debug );
$my_session->access_check( EXT_GROUP_USER );	// Make sure we should be here

require_once( "include/sts_result_class.php" );
require_once( "include/sts_ifta_log_class.php" );

$ifta_log = sts_ifta_log::getInstance( $exspeedite_db, $sts_debug );

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
				if( ! isset($_POST["data"][0]) ) {
					$response = array('error' => 'missing data');
				} else {
					$fielderrors = array();
					foreach( $sts_result_ifta_log_layout as $key => $row ) {
						if( isset($row['extras']) && $row['extras'] == 'required' ) {
							if( ! isset($_POST["data"][0][$key]) ||
								$_POST["data"][0][$key] == '' ) {
								$fielderrors[] = array( 'name' => $key, 'status' => 'This field is required');
							}
						} else if( isset($_POST["data"][0][$key]) && $_POST["data"][0][$key] == '' )
							unset( $_POST["data"][0][$key] );
					}
					if( count($fielderrors) > 0 ) {
						$response = array('fieldErrors' => $fielderrors);
					} else {
						$new = $ifta_log->add( $_POST["data"][0] );
						if( $new )
							$response = array( "data" => $ifta_log->fetch_rows( $ifta_log->primary_key.' = '.$new ) );
						else
							$response = array( "error" =>$ifta_log->error() );
					}
				}
				
				break;
			case 'edit':
				foreach( $_POST["data"] as $pk => $changes ) {
					$fielderrors = array();
					foreach( $sts_result_ifta_log_layout as $key => $row ) {
						if( isset($row['extras']) && $row['extras'] == 'required' ) {
							if( isset($changes[$key]) && $changes[$key] == '' ) {
								$fielderrors[] = array( 'name' => $key, 'status' => 'This field is required');
							}
						} else if( isset($changes[$key]) && $changes[$key] == '' ) {
							$changes[$key] = 'NULL';
						}
					}
					if( count($fielderrors) > 0 ) {
						$response = array('fieldErrors' => $fielderrors);
						break;
					}
					$ifta_log->update($pk, $changes);
				}
				$match = "IFTA_LOG_CODE IN (".implode(', ', array_keys($_POST["data"])).")";
				$rslt = new sts_result( $ifta_log, $match, $sts_debug );
				
				unset($sts_result_ifta_log_layout['NULL']);
				$response =  $rslt->render_ajax( $sts_result_ifta_log_layout, $sts_result_ifta_log_edit );
				unset( $response['draw'], $response['recordsFiltered'], 
					$response['recordsTotal']);
				break;
			case 'remove':
				foreach( $_POST["data"] as $row ) {
					$ifta_log->delete( $row["DT_RowAttr"]["IFTA_LOG_CODE"] );
				}
				$response = array( "data" => array() );
				
				break;
			default:
		}
	}
} else {
	$match = isset($_GET["match"]) && $_GET["match"] <> '' ? urldecode($_GET["match"]) : false;
	
	$rslt = new sts_result( $ifta_log, $match, $sts_debug );
	
	$response =  $rslt->render_ajax( $sts_result_ifta_log_layout, $sts_result_ifta_log_edit, $_GET );
}

if( $sts_debug ) {
		echo "<p>response = </p><pre>";
		var_dump($response);
		echo "</pre>";
} else {
	echo json_encode( $response );
}
?>

