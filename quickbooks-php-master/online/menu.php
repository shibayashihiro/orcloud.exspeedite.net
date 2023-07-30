<?php

require_once dirname(__FILE__) . '/config.php';

if( $multi_company && $sts_qb_multi ) {
	require_once( "include/sts_company_class.php" );
	$company_table = sts_company::getInstance($exspeedite_db, $sts_debug);
	$the_tenant = $_SESSION['SETUP_COMPANY'];
}
// Display the menu
die($IntuitAnywhere->widgetMenu($user, $the_tenant));