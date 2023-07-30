<?php 

// $Id: exp_import_drivers_kt.php 4350 2021-03-02 19:14:52Z duncan $
// Import Drivers from KeepTruckin

// Set flag that this is a parent file
define( '_STS_INCLUDE', 1 );

// Set flag that this is session readonly
define( '_STS_SESSION_READONLY', 1 );

// Setup Session
require_once( "include/sts_session_setup.php" );
set_time_limit(120);
ini_set('memory_limit', '1024M');

require_once( "include/sts_config.php" );
$sts_debug = isset($_GET['debug']) && in_group( EXT_GROUP_DEBUG );
require_once( "include/sts_session_class.php" );

$my_session = sts_session::getInstance( $exspeedite_db, $sts_debug );
$my_session->access_check( EXT_GROUP_ADMIN );	// Make sure we should be here

$sts_subtitle = "Import Drivers";
require_once( "include/header_inc.php" );

require_once( "include/sts_ifta_log_class.php" );
require_once( "include/sts_driver_class.php" );

if( isset($_SERVER["HTTP_REFERER"]) ) {
	$path = explode('/', $_SERVER["HTTP_REFERER"]); 
	$referer = end($path);
} else
	$referer = 'unknown';
	
$kt = sts_keeptruckin::getInstance($exspeedite_db, $sts_debug);
$driver_table = sts_driver::getInstance($exspeedite_db, $sts_debug);

if( $kt->is_enabled() ) {
	// Turn off output buffering
	ini_set('output_buffering', 'off');
	// Implicitly flush the buffer(s)
	ini_set('implicit_flush', true);
	ob_implicit_flush(true);

	if (ob_get_level() == 0) ob_start();

	echo '<h1 class="text-center">Import Drivers From KeepTruckin</h1>
	<br><br><br>
	<p class="text-center"><img src="images/keeptruckin.png" alt="keeptruckin" height="50" /> <img src="images/animated-arrow-right.gif" alt="animated-arrow-right" height="50" /> <img src="images/EXSPEEDITEsmr.png" alt="EXSPEEDITEsmr" width="284" height="50" /></p>
	
	<div id="loading"><p class="text-center"><img src="images/loading.gif" alt="loading" width="125" height="125" /></p></div>';

	$data = $kt->active_drivers();
	
	if( false && $sts_debug ) {
		echo "<pre>users\n";
		var_dump($data);
		echo "</pre>";
	}
	
	if( is_array($data) && count($data) > 0 ) {
		foreach( $data as $row ) {
			if( $row["user"]["role"] == 'driver' && $row["user"]["status"] == 'active' ) {
				$kt->sync_driver($row["user"]);
				ob_flush(); flush();
			}
	
		}
	} else if($data === false) {
		update_message( 'loading', '' );
		echo '<div class="container">
			<h2>Error Connecting to KeepTrukin</h2>
			<p>Details: '.$kt->errmsg.'</p>
			<p>This may be because you need to 
			<a href="exp_kt_oauth2.php" class="btn btn-success">re-authenticate with OAuth 2.0</a> <a class="btn btn-default tip" title="Go back to home page" href="exp_listdriver.php"><span class="glyphicon glyphicon-remove"></span> Back</a></p>
		</div>';
		die;
	}
	
	
	sleep(2);
	update_message( 'loading', '' );
}

if( $sts_debug ) die;

if( isset($referer) && $referer <> 'unknown' ) {
	reload_page ( $referer );	// Back to referring page
} else {
	reload_page ( "exp_listdriver.php" );	// Back to list drivers page
}

require_once( "include/footer_inc.php" );
?>
