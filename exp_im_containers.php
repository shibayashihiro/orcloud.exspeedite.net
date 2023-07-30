<?php 

// $Id: exp_im_containers.php 5030 2023-04-12 20:31:34Z duncan $

// Set flag that this is a parent file
define( '_STS_INCLUDE', 1 );

// Setup Session
require_once( "include/sts_session_setup.php" );

require_once( "include/sts_config.php" );
$sts_debug = isset($_GET['debug']) && in_group( EXT_GROUP_DEBUG );
require_once( "include/sts_session_class.php" );

$my_session = sts_session::getInstance( $exspeedite_db, $sts_debug );
$my_session->access_check( EXT_GROUP_ADMIN );	// Make sure we should be here

$sts_subtitle = "Intermodal Containers";
require_once( "include/header_inc.php" );
require_once( "include/navbar_inc.php" );
require_once( "include/sts_yard_container_class.php" );

$setting_table = sts_setting::getInstance( $exspeedite_db, $sts_debug );
//! SCR# 852 - Containers Feature
$sts_containers = $setting_table->get( 'option', 'CONTAINERS' ) == 'true';

$yc_table = sts_yard_container::getInstance( $exspeedite_db, $sts_debug );

if( !empty($_GET["DELETE"])) {
	$yc_table->delete_from_yard( $_GET["DELETE"] );
}

?>
<div class="container" role="main">
<?php
	$yc_table->container_info();

require_once( "include/footer_inc.php" );
?>