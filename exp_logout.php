<?php 
// Set flag that this is a parent file
define( '_STS_INCLUDE', 1 );

// Set flag that this is an ajax call
define( '_STS_SESSION_AJAX', 1 );

// Setup Session
require_once( "include/sts_session_setup.php" );
define( '_STS_SIGNIN_THEME', 1 );

require_once( "include/sts_config.php" );

// Used in header_inc.php
define( 'EXP_RELATIVE_PATH', '../' );

$sts_debug = isset($_GET['debug']) && in_group( EXT_GROUP_DEBUG );
require_once( "include/sts_session_class.php" );

$my_session = sts_session::getInstance( $exspeedite_db, $sts_debug );
	
if( isset($_GET["EXPIRED"])) {
	$my_session->logout( $_GET["EXPIRED"] );

	$sts_subtitle = "Session Timed Out";
	require_once( "include/header_inc.php" );

	echo '<br><br><br><br>
	
	<div class="container container-small well well-lg">
	<br>
		<div class="row text-center">
			<img class="img-responsive center-block animated rubberBand delay1" src="images/EXSPEEDITEmedr.png" alt="<?php echo $sts_title ?>">
			<h1 class="text-danger"><span class="glyphicon glyphicon-warning-sign"></span> Session Expired</h1>
		</div>
	<h4>Your session was inactive for more than '.($sts_activity_timeout/60).' minutes. Last activity was at '.$_GET["EXPIRED"].'<br>
	You have been logged out. Please login to continue.</h4>
	<h2 class="text-center"><a class="btn btn-default" href="exp_login.php"><span class="glyphicon glyphicon-arrow-right"></span> Return To Login</a></h2>
	</div>';

} else {
	$my_session->logout();
	
	if( ! $sts_debug )
		reload_page( "exp_login.php" );
}

require_once( "include/footer_inc.php" );
?>
