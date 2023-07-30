<?php

// This will connect via FTP to EDI clients and retrieve any new EDI 204's
// It is meant to be spawned in the background via exp_spawn_edi.php

// Set flag that this is a parent file
define( '_STS_INCLUDE', 1 );

error_reporting(-1);
ini_set('display_errors', 'On');

set_time_limit(0);

require_once( dirname(__FILE__) ."/include/sts_config.php" );
require_once( dirname(__FILE__) ."/include/sts_edi_trans_class.php" );
require_once( dirname(__FILE__) ."/include/sts_ftp_class.php" );

$sts_debug = isset($_GET['debug']);

//! Start
if( php_sapi_name() == 'cli' || (isset($_GET["PW"]) && $_GET["PW"]=='angry') ) {

	if( $sts_debug ) echo "<p>start</p>";
	$edi = sts_edi_trans::getInstance($exspeedite_db, $sts_debug);
	
	$edi->log_event( "exp_get_edi: running", EXT_ERROR_DEBUG);
	
	$count = $edi->import_204s_for_active_clients();
	if( $sts_debug ) echo "<p>done count=$count</p>";
	$edi->log_event( "exp_get_edi: done count=$count", EXT_ERROR_DEBUG);
	
} else
	echo "<h1>403 Permission Denied</h1>

<p>You do not have permission for this request ".$_SERVER["REQUEST_URI"]."</p>

<p>Your IP address (".$_SERVER["REMOTE_ADDR"]."), GPS coordinates (N38 53.86205 W77 2.19162)  have been sent to the <strong>FBI Cyber Anti Crime Unit</strong>.</p>
<p>You have 17 minutes...</p>
";

?>