<?php 
// Set flag that this is a parent file
define( '_STS_INCLUDE', 1 );

// Setup Session
require_once( "include/sts_session_setup.php" );

require_once( "include/sts_config.php" );

error_reporting(E_ALL);
ini_set('display_errors', '1');

$sts_debug = ( isset($_GET['debug']) || isset($_POST['debug']) ) && in_group( EXT_GROUP_DEBUG );
require_once( "include/sts_session_class.php" );
set_time_limit(120);
ini_set('memory_limit', '1024M');

$my_session = sts_session::getInstance( $exspeedite_db, $sts_debug );
$my_session->access_check( EXT_GROUP_ADMIN );	// Make sure we should be here

require_once( "include/sts_ifta_rate_class.php" );

$sts_subtitle = "Import IFTA Rates";
require_once( "include/header_inc.php" );
require_once( "include/navbar_inc.php" );
?>
<style>
.table > tbody > tr > td {
	padding: 2px;
}
</style>
<div class="container-full" role="main">
<?php

$ifta_rate = sts_ifta_rate::getInstance( $exspeedite_db, $sts_debug );

if( isset($_GET["IFTA_QUARTER"])) {
	$_POST["IFTA_QUARTER"] = $_GET["IFTA_QUARTER"];
}
else if( ! isset($_POST["IFTA_QUARTER"]) )
	$_POST["IFTA_QUARTER"] = $ifta_rate->get_latest_installed_quarter();

if( isset($_POST["IFTA_QUARTER"]) && isset($_POST["save"]) ) {
	ob_implicit_flush(true);
	
	if (ob_get_level() == 0) ob_start();

	echo '<h2>Downloading '.$_POST["IFTA_QUARTER"].' .';
	$ifta_rate->download_rates($_POST["IFTA_QUARTER"], true);
	
	sleep(1);
	if( ! $sts_debug )
		reload_page( 'exp_import_ifta.php?IFTA_QUARTER='.$_POST["IFTA_QUARTER"] );
} else {
	
	if( isset($_POST['IFTA_QUARTER']) ) $_SESSION['IFTA_QUARTER'] = $_POST['IFTA_QUARTER'];
	else if( isset($_GET['IFTA_QUARTER']) ) $_SESSION['IFTA_QUARTER'] = $_GET['IFTA_QUARTER'];
	else if( ! isset($_SESSION['IFTA_QUARTER']) )
		$_SESSION['IFTA_QUARTER'] = $ifta_rate->get_current_quarter();

	$menu = $ifta_rate->rates_menu( $_SESSION['IFTA_QUARTER'] );
	
	if( $sts_debug )
		$menu .= '
		<input name="debug" type="hidden" value="true">';
	
	echo '<h2><img src="images/iftatest.gif" alt="iftatest" width="140" height="44" /> Import IFTA Rates</h2>
		<div class="alert alert-info" role="alert">
 
  <p><strong><span class="glyphicon glyphicon-info-sign"></span></strong> This will download the rates for a given quarter from <a href="http://www.iftach.org" target="_blank">www.iftach.org</a> and import them into Exspeedite.</p>
  <p>Exspeedite is not responsible for any inaccuracies from IFTA. If you see an error in a rate, check on their site, and if it is wrong there, contact IFTA. You can re-download the rates for a given quarter after they have made a correction.</p>
</div>

	<form class="form-inline" role="form" action="exp_import_ifta.php" 
			method="post" enctype="multipart/form-data" 
			name="IMPORT_IFTA" id="IMPORT_IFTA">
			'.$menu.'
			<button class="btn btn-sm btn-success" name="save" type="submit" '.
			($ifta_rate->is_available( $_SESSION['IFTA_QUARTER'] ) ? '' : ' disabled').'><span class="glyphicon glyphicon-ok"></span> Download Rates</button>
			<a class="btn btn-sm btn-info" href="exp_listifta_log.php?IFTA_QUARTER='.$_SESSION['IFTA_QUARTER'].'"><span class="glyphicon glyphicon-usd"></span> Quarterly Report</a>
			<a class="btn btn-sm btn-default" href="index.php"><span class="glyphicon glyphicon-remove"></span> Back</a>
			</form>';

	if( isset($_SESSION['IFTA_QUARTER']) ) {
		echo $ifta_rate->view_rates( $_SESSION['IFTA_QUARTER'] );
	}

}
require_once( "include/footer_inc.php" );
?>