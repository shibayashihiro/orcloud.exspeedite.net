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
	$sts_subtitle = "Edit Stop";
	require_once( "include/header_inc.php" );
	require_once( "include/navbar_inc.php" );
	
	require_once( "include/sts_form_class.php" );
	require_once( "include/sts_stop_class.php" );
	
	$stop_table = sts_stop::getInstance($exspeedite_db, $sts_debug);
	if( endswith($_SERVER["HTTP_REFERER"], "exp_listload.php") ) {
		$sts_form_edit_stop["cancel"] = "exp_listload.php";
		$_SESSION["BACK"] = "exp_listload.php";
	}
	
	if( isset($_SESSION["BACK"]) && $_SESSION["BACK"] <> '' ) {
		$sts_form_edit_stop["cancel"] = $_SESSION["BACK"];
	}

	$stop_form = new sts_form($sts_form_edit_stop, $sts_form_edit_stop_fields,
		$stop_table, $sts_debug);
	
	if( isset($_POST) && count($_POST) > 0 ) {		// Process completed form
		$result = $stop_form->process_edit_form();

		if( $sts_debug ) die; // So we can see the results
		if( $result ) {
			if( isset($_SESSION["BACK"]) && $_SESSION["BACK"] <> '' ) {
				$tmp = $_SESSION["BACK"];
				unset($_SESSION["BACK"]);
				reload_page ( $tmp );
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
		$result = $stop_table->fetch_rows("stop_CODE = ".$_GET['CODE']);
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

