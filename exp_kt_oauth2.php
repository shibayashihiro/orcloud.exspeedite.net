<?php 

// $Id: exp_kt_oauth2.php 4697 2022-03-09 23:02:23Z duncan $
// KeepTruckin OAuth 2.0

// Set flag that this is a parent file
define( '_STS_INCLUDE', 1 );

// Setup Session
require_once( "include/sts_session_setup.php" );

require_once( "include/sts_config.php" );

$sts_debug = isset($_GET['debug']) && in_group( EXT_GROUP_DEBUG );
require_once( "include/sts_session_class.php" );

$my_session = sts_session::getInstance( $exspeedite_db, $sts_debug );
$my_session->access_check( EXT_GROUP_ADMIN );	// Make sure we should be here

$sts_subtitle = "KeepTruckin OAuth 2.0";
if( ! $sts_debug ) {
	require_once( "include/header_inc.php" );
	require_once( "include/navbar_inc.php" );
}

?>
<div class="container-full" role="main">
	<div class="well well-lg">
	<h2><img src="images/keeptruckin.png" alt="keeptruckin" height="40" /> Authentication Via OAuth 2.0
		<a class="btn btn-default tip" title="Login to KeepTruckin Dashboard" href="https://web.keeptruckin.com/#/overview" target="_blank">KT Dashboard <span class="glyphicon glyphicon-new-window"></span></a></h2>
	<br>
<?php
	
require_once( "include/sts_ifta_log_class.php" );

$kt = sts_keeptruckin::getInstance($exspeedite_db, $sts_debug);

if( isset($_GET) && isset($_GET["FORGET"]) ) {
	$kt->forget();
	reload_page( 'exp_kt_oauth2.php' );
} else

if( isset($_GET) && isset($_GET["code"]) ) {
	$authorization_code = $_GET["code"];
	$kt->log_event( "exp_kt_oauth2.php: Got Authorization Code ".$authorization_code, EXT_ERROR_DEBUG);
	
	echo '<h3><span class="glyphicon glyphicon-ok"></span> Got Authorization Code</h3>
	';

	if( $kt->fetch_token( $authorization_code ) ) {
		$kt->log_event( "exp_kt_oauth2.php: Got Token", EXT_ERROR_DEBUG);
		echo '<h3><span class="glyphicon glyphicon-ok"></span> Got Token</h3>
		<h2><span class="glyphicon glyphicon-ok"></span> Authenticated</h2>
		<p>Exspeedite was able to get a new access token.</p>
		<h3>Company: '.$kt->company_name().'</h3>
			<p><a class="btn btn-danger tip" title="Forget Authentication with KeepTruckin" href="exp_kt_oauth2.php?FORGET"><span class="glyphicon glyphicon-remove"></span> Forget Authentication</a> <a class="btn btn-default tip" title="Refresh this screen" href="exp_kt_oauth2.php"><span class="glyphicon glyphicon-refresh"></span> Refresh</a> <a class="btn btn-default tip" title="Go back to home page" href="index.php"><span class="glyphicon glyphicon-remove"></span> Back</a></p>
		';

	} else {
		$kt->log_event( "exp_kt_oauth2.php: Failed To Get Token ".$kt->errmsg, EXT_ERROR_DEBUG);
		
		echo '<h2><span class="glyphicon glyphicon-warning-sign"></span> Failed To Get Token</h2>
		<p>'.$kt->errmsg.'</p>
		';	
		echo '<a class="btn btn-success" href="'.$kt->authorization_url().'">Authorize To Connect Via OAuth 2.0</a> <a class="btn btn-default tip" title="Refresh this screen" href="exp_kt_oauth2.php"><span class="glyphicon glyphicon-refresh"></span> Refresh</a> <a class="btn btn-default tip" title="Go back to home page" href="index.php"><span class="glyphicon glyphicon-remove"></span> Back</a>';
	}
	
} else if( ! empty( $kt->refresh_token ) ) {
	if( $kt->fetch_token() ) {
		$kt->log_event( "exp_kt_oauth2.php: Already Authenticated", EXT_ERROR_DEBUG);
		
		echo '<h2><span class="glyphicon glyphicon-ok"></span> Already Authenticated</h2>
		<p>Exspeedite was able to get a new access token.</p>
		<h3>Company: '.$kt->company_name().'</h3>
		<p><a class="btn btn-danger tip" title="Forget Authentication with KeepTruckin" href="exp_kt_oauth2.php?FORGET"><span class="glyphicon glyphicon-remove"></span> Forget Authentication</a> <a class="btn btn-default tip" title="Refresh this screen" href="exp_kt_oauth2.php"><span class="glyphicon glyphicon-refresh"></span> Refresh</a> <a class="btn btn-default tip" title="Go back to home page" href="index.php"><span class="glyphicon glyphicon-remove"></span> Back</a></p>
		';
	} else {
		$kt->log_event( "exp_kt_oauth2.php: Failed To Get New Token ".$kt->errmsg, EXT_ERROR_DEBUG);

		echo '<h2><span class="glyphicon glyphicon-warning-sign"></span> Failed To Get New Token</h2>
		<p>'.$kt->errmsg.'</p>
		';	
		echo '<a class="btn btn-success" href="'.$kt->authorization_url().'">Authorize To Connect Via OAuth 2.0</a> <a class="btn btn-default tip" title="Refresh this screen" href="exp_kt_oauth2.php"><span class="glyphicon glyphicon-refresh"></span> Refresh</a> <a class="btn btn-default tip" title="Go back to home page" href="index.php"><span class="glyphicon glyphicon-remove"></span> Back</a>';
	}	
} else {
		$kt->log_event( "exp_kt_oauth2.php: Not Yet Authenticated", EXT_ERROR_DEBUG);

		echo '<h2><span class="glyphicon glyphicon-warning-sign"></span> Not Yet Authenticated</h2>
		<p>Plesase click on the green button to connect with KeepTruckin. When asked, click Install.</p>
		';	
	
	echo '<a class="btn btn-success tip" title="This will take you to KeepTruckin where you can permit Exspeedite to access your data" href="'.$kt->authorization_url().'">Authorize To Connect Via OAuth 2.0</a> <a class="btn btn-default tip" title="Refresh this screen" href="exp_kt_oauth2.php"><span class="glyphicon glyphicon-refresh"></span> Refresh</a> <a class="btn btn-default tip" title="Go back to home page" href="index.php"><span class="glyphicon glyphicon-remove"></span> Back</a>';
}


if( ! $sts_debug )
	require_once( "include/footer_inc.php" );
?>

