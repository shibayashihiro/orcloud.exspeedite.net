<?php 
// Set flag that this is a parent file
define( '_STS_INCLUDE', 1 );

// Setup Session
require_once( "include/sts_session_setup.php" );

require_once( "include/sts_config.php" );
$sts_debug = isset($_GET['debug']) && in_group( EXT_GROUP_DEBUG );
require_once( "include/sts_session_class.php" );

$my_session = sts_session::getInstance( $exspeedite_db, $sts_debug );
$my_session->access_check( EXT_GROUP_USER );	// Make sure we should be here

$sts_subtitle = "List Pallets";
require_once( "include/header_inc.php" );
require_once( "include/navbar_inc.php" );

?>
<div class="container-full" role="main">
<?php
require_once( "include/sts_result_class.php" );
require_once( "include/sts_pallet_class.php" );

$pallet_table = new sts_pallet($exspeedite_db, $sts_debug);
$rslt = new sts_result( $pallet_table, false, $sts_debug );
echo $rslt->render( $sts_result_pallets_layout, $sts_result_pallets_edit );

?>
</div>
<?php

require_once( "include/footer_inc.php" );
?>

