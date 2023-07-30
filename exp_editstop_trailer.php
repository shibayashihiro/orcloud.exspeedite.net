<?php 
// Set flag that this is a parent file
define( '_STS_INCLUDE', 1 );

// Setup Session
require_once( "include/sts_session_setup.php" );

require_once( "include/sts_config.php" );
$sts_debug = (isset($_GET['debug'])  || isset($_POST['debug'])) && in_group( EXT_GROUP_DEBUG );
require_once( "include/sts_session_class.php" );

$my_session = sts_session::getInstance( $exspeedite_db, $sts_debug );
$my_session->access_check( $sts_table_access[STOP_TABLE] );	// Make sure we should be here

if( isset($_POST) || isset($_GET['CODE']) ) {
	$sts_subtitle = "Edit Stop Trailer";
	require_once( "include/header_inc.php" );
	require_once( "include/navbar_inc.php" );
	
	require_once( "include/sts_form_class.php" );
	require_once( "include/sts_stop_class.php" );
	
	$stop_table = sts_stop::getInstance($exspeedite_db, $sts_debug);
	$stop_table_lj = new sts_stop_left_join($exspeedite_db, $sts_debug);
	if( endswith($_SERVER["HTTP_REFERER"], "exp_listload.php") ) {
		$sts_form_edit_stop_trailer["cancel"] = "exp_listload.php";
		$_SESSION["BACK"] = "exp_listload.php";
	}
	
	$stop_form = new sts_form($sts_form_edit_stop_trailer, $sts_form_edit_stop_trailer_fields,
		$stop_table, $sts_debug);
	
	if( isset($_POST) && count($_POST) > 0 ) {		// Process completed form
		unset($_POST['SHIPMENT'], $_POST['SEQUENCE_NO'], $_POST['STOP_TYPE'], $_POST['CURRENT_STATUS']);
		$dummy = $stop_table->add_load_status( $_POST["LOAD_CODE"], 
			'editstop_trailer: update trailer, stop='.$_POST['STOP_CODE'] );
		$result = $stop_form->process_edit_form();
		$dummy = $stop_table->propagate_trailer($_POST['STOP_CODE']);
		
		$stop_status = $stop_table->database->get_multiple_rows(
			"SELECT STOP_CODE, STOP_TYPE, LOAD_CODE, SHIPMENT, CURRENT_STATUS,
			(SELECT STATUS
			FROM EXP_STATUS_CODES
			WHERE STATUS_CODES_CODE = CURRENT_STATUS) AS STATUS,
			(SELECT SOURCE
			FROM EXP_STATUS_CODES
			WHERE STATUS_CODES_CODE = CURRENT_STATUS) AS SOURCE
			FROM EXP_STOP
			WHERE STOP_CODE = ".$_POST['STOP_CODE']."
			AND (SELECT SOURCE
			FROM EXP_STATUS_CODES
			WHERE STATUS_CODES_CODE = CURRENT_STATUS) <> 'stop'" );
		
		if( is_array($stop_status) && count($stop_status) > 0 ) {
			$dummy = $stop_table->add_load_status( $_POST["LOAD_CODE"], 
				'editstop_trailer: got bad status, code='.
				$stop_status[0]['STOP_CODE'].' status = '.$stop_status[0]['CURRENT_STATUS'] );
		}

		if( $sts_debug ) die; // So we can see the results
		if( $result ) {
			if( isset($_SESSION["BACK"]) && $_SESSION["BACK"] <> '' ) {
				$tmp = $_SESSION["BACK"];
				unset($_SESSION["BACK"]);
				reload_page ( $tmp."?CODE=".$_POST["LOAD_CODE"] );
			} else
				reload_page ( "exp_viewload.php?CODE=".$_POST["LOAD_CODE"] );
		}
	}
	
	?>
	<div class="container theme-showcase" role="main">
	
	<div class="well  well-lg">
	<?php
	
	if( isset($value) && is_array($value) && $result == false ) {	// If error occured
		echo "<p><strong>Error:</strong> ".$stop_table->error()."</p>";
		echo $stop_form->render( $value );
	} else if( isset($_GET['CODE']) ) {
		$result = $stop_table_lj->fetch_rows("STOP_CODE = ".$_GET['CODE']);
		if( ! isset($result[0]['TRAILER']) || intval($result[0]['TRAILER']) <= 0 )
			$result[0]['TRAILER'] = $result[0]['TRAILER2'];
			$result[0]['LOAD_CODE'] = $result[0]['STOP_LOAD_CODE'];
		echo $stop_form->render( $result[0] );
	}
	
}
?>
</div>
</div>
	<script language="JavaScript" type="text/javascript"><!--
		
		$(document).ready( function () {
		});
	//--></script>


<?php

require_once( "include/footer_inc.php" );
?>

