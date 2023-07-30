<?php 
// Set flag that this is a parent file
define( '_STS_INCLUDE', 1 );

// Setup Session
require_once( "include/sts_session_setup.php" );

require_once( "include/sts_config.php" );
$sts_debug = (isset($_GET['debug'])  || isset($_POST['debug'])) && in_group( EXT_GROUP_DEBUG );
require_once( "include/sts_session_class.php" );

$my_session = sts_session::getInstance( $exspeedite_db, $sts_debug );
$my_session->access_check( $sts_table_access[STOP_DETAIL_TABLE] );	// Make sure we should be here

if( isset($_POST) || ( isset($_GET['CODE']) && isset($_GET['CODE']) ) ) {
	$sts_subtitle = "Add Detail";
	require_once( "include/header_inc.php" );
	require_once( "include/navbar_inc.php" );
	
	require_once( "include/sts_form_class.php" );
	require_once( "include/sts_stop_detail_class.php" );
	
	$stop_detail_table = new sts_stop_detail($exspeedite_db, $sts_debug);
	$stop_detail_form = new sts_form( $sts_form_add_stop_detail, $sts_form_add_stop_detail_fields,
		$stop_detail_table, $sts_debug);
	
	if( isset($_POST) && count($_POST) > 0 ) {		// Process completed form
		$result = $stop_detail_form->process_add_form();
	
		if( $sts_debug ) die; // So we can see the results
		if( $result ) {
			if( isset($_POST["saveadd"]) )
				reload_page ( "exp_addstop_detail.php?CODE=".$_POST["PICK_CODE"] );
			else
				reload_page ( "exp_editorder_stop.php?CODE=".$_POST["PICK_CODE"] );

		}
			
	}
	
	?>
<div class="container theme-showcase" role="main">

<div class="well  well-lg">	
	<?php
	
	if( isset($value) && is_array($value) && $result == false ) {	// If error occured
		echo "<p><strong>Error:</strong> ".$stop_detail_table->error()."</p>";
		echo $stop_detail_form->render( $value );
	} else {
		$value = array('PICK_CODE' => $_GET['CODE'] );
		echo $stop_detail_form->render( $value );
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
		

