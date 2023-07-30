<?php 
// Set flag that this is a parent file
define( '_STS_INCLUDE', 1 );

// Setup Session
require_once( "include/sts_session_setup.php" );

require_once( "include/sts_config.php" );
$sts_debug = isset($_GET['debug']) && in_group( EXT_GROUP_DEBUG );
require_once( "include/sts_session_class.php" );

$my_session = sts_session::getInstance( $exspeedite_db, $sts_debug );
$my_session->access_check( $sts_table_access[TRACTOR_TABLE] );	// Make sure we should be here

$sts_subtitle = "PeopleNet Mobile Comms";
require_once( "include/header_inc.php" );
require_once( "include/navbar_inc.php" );
?>
<div class="container-full" role="main">
	<div class="well well-sm">
		<div class="row">
			<div class="col-sm-3">
				<img src="images/peoplenet_logo.png" alt="peoplenet_logo" height="80"> 
			</div>
			<div class="col-sm-9">
				<h2>Unable to Connect</h2>
				<p>Check your PeopleNet settings and try again, or contact your administrator.</p>
			</div>
	</div>
</div>
<?php
require_once( "include/footer_inc.php" );
?>
