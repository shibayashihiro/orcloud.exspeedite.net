<?php 

// $Id: exp_zip_archive.php 3884 2020-01-30 00:21:42Z duncan $
// Download/Zip Archive Files

// Set flag that this is a parent file
define( '_STS_INCLUDE', 1 );

// Setup Session
require_once( "include/sts_session_setup.php" );

require_once( "include/sts_config.php" );
$sts_debug = (isset($_GET['debug'])  || isset($_POST['debug'])) && in_group( EXT_GROUP_DEBUG );
//$sts_debug = isset($_POST) && count($_POST) > 0 ;
require_once( "include/sts_session_class.php" );

$my_session = sts_session::getInstance( $exspeedite_db, $sts_debug );
$my_session->access_check( EXT_GROUP_ADMIN );	// Make sure we should be here
require_once( "include/sts_archive_class.php" );

if( isset($_GET['DIR']) && isset($_GET['PW']) && $_GET['PW'] = 'Gober' ) {
	$archive = sts_archive::getInstance($exspeedite_db, $sts_debug);
	$archive->zip_archive($_GET['DIR']);
}
?>