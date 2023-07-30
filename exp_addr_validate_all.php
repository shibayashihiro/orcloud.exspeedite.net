<?php 
// Set flag that this is a parent file
define( '_STS_INCLUDE', 1 );

// Setup Session
require_once( "include/sts_session_setup.php" );

require_once( "include/sts_config.php" );
set_time_limit(0);
ini_set('memory_limit', '1024M');

$sts_debug = isset($_GET['debug']); // && in_group( EXT_GROUP_DEBUG );
require_once( "include/sts_session_class.php" );

$my_session = sts_session::getInstance( $exspeedite_db, $sts_debug );
$my_session->access_check( EXT_GROUP_ADMIN );	// Make sure we should be here

if( isset($_GET['PW']) && $_GET['PW'] == 'VerySlow' ) {
	$sts_subtitle = "Validate All Contact Info";
	require_once( "include/header_inc.php" );
	require_once( "include/navbar_inc.php" );
	require_once( "include/sts_contact_info_class.php" );
	
	$ci_table = sts_contact_info::getInstance($exspeedite_db, $sts_debug);
	
	$check = $ci_table->fetch_rows("", "COUNT(*) NUM");
	$check2 = $ci_table->fetch_rows("ADDR_VALID = 'valid' AND
		LAT IS NOT NULL AND LON IS NOT NULL", "COUNT(*) NUM");
	
	if( is_array($check) && count($check) == 1 && isset($check[0]["NUM"]) ) {

		echo '<div class="container" role="main">
		
		<h3>Validate & get location for all '.$check[0]["NUM"].' Contact Info'.(is_array($check2) && count($check2) == 1 && isset($check2[0]["NUM"]) ? ' ('.$check2[0]["NUM"].' already done)' : '').'</h3>
		<p>This may take a while.</p>';
	
		if (ob_get_level() == 0) ob_start();
	
		$result = $ci_table->update_all();
	
		echo "<p>Updated $result entries. (These might be invalid addresses)</p>
			</div>";
	}
	
	require_once( "include/footer_inc.php" );
}
?>
