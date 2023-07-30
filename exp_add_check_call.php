<?php

// $Id: exp_add_check_call.php 3435 2019-03-25 18:53:25Z duncan $
//! SCR# 298 - For Check call, add check call to history

// Set flag that this is a parent file
define( '_STS_INCLUDE', 1 );

// Setup Session
require_once( "include/sts_session_setup.php" );

require_once( "include/sts_config.php" );
require_once( "include/sts_status_class.php" );


if( ! empty($_POST['ck-load']) && isset($_POST['ck-pw']) && $_POST['ck-pw'] == 'QwertyuioP') {
	$status_table = sts_status::getInstance($exspeedite_db, false);
	
	$comment = 'CHECKCALL: '.$_POST['ck-address'].(empty($_POST['ck-note']) ? '' : ' ('.$_POST['ck-note'].')');
	$comment = $status_table->trim_to_fit( 'COMMENTS', $comment );
	
	// Format lat/lon to 6DP
	$status_table->add_load_status( $_POST['ck-load'], $comment, 
		number_format((float) $_POST['ck-lat'],6,'.',''),
		number_format((float) $_POST['ck-lon'],6,'.','') );
	
	reload_page($_POST['ck-return']);
}

?>