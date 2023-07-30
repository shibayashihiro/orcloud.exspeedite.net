<?php 
// Set flag that this is a parent file
define( '_STS_INCLUDE', 1 );

require_once( "include/sts_config.php" );

error_reporting(E_ALL);
ini_set('display_errors', '1');

$sts_debug = isset($_GET['debug']) && in_group( EXT_GROUP_DEBUG );
require_once('include/sts_setting_class.php');

if( ! isset($_SESSION) ) {
	$_SESSION = array();
}

if( php_sapi_name() == 'cli' ) {		// run via CLI
	
	$setting_table = sts_setting::getInstance($exspeedite_db, $sts_debug);
	$log_miles = ($setting_table->get("api", "PCM_LOG_IFTA_MILES") == 'true');
	$options = getopt("c:ld");

	if( $log_miles &&					// enabled via setting
		isset($options["c"]) ) {		// LOAD_CODE
	
		if( isset($options["l"])) {		// Log the miles
			require_once( "PCMILER/exp_get_miles.php" );
			$pcm = sts_pcmiler_api::getInstance( $exspeedite_db, $sts_debug );
			$pcm->log_miles( $options["c"] );
		} else
	
		if( isset($options["d"])) {		// Delete miles from the log
			require_once( "include/sts_ifta_log_class.php" );
			$ifta = sts_ifta_log::getInstance( $exspeedite_db, $sts_debug );
			$ifta->delete_row( "CD_ORIGIN = '".$options["c"]."'");
		}
	}
} else {
	echo "<p>Run via CLI only</p>";
}
?>