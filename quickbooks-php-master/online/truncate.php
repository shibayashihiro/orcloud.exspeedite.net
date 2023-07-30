<?php

//! SCR# 429 - truncate quickbooks tables.

// Set flag that this is a parent file
define( '_STS_INCLUDE', 1 );

require_once dirname(__FILE__) . '/config.php';

require_once( "include/sts_session_class.php" );

$my_session = sts_session::getInstance( $exspeedite_db, $sts_debug );
$my_session->access_check( EXT_GROUP_ADMIN );	// Make sure we should be here

$exspeedite_db->get_one_row("TRUNCATE quickbooks_config");
$exspeedite_db->get_one_row("TRUNCATE quickbooks_log");
$exspeedite_db->get_one_row("TRUNCATE quickbooks_oauth2");
$exspeedite_db->get_one_row("TRUNCATE quickbooks_queue");
$exspeedite_db->get_one_row("TRUNCATE quickbooks_recur");
$exspeedite_db->get_one_row("TRUNCATE quickbooks_ticket");
$exspeedite_db->get_one_row("TRUNCATE quickbooks_user");

?>


		<div style="text-align: center; font-family: sans-serif; font-weight: bold;">
			TRUNCATING QB Tables! Please wait...
		</div>
		
		
		<script type="text/javascript">
			window.setTimeout('window.location = \'./setup.php\';', 2000);
		</script>
			