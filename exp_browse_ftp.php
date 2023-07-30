<?php 
// Set flag that this is a parent file
define( '_STS_INCLUDE', 1 );

// Set flag that this is session readonly
define( '_STS_SESSION_READONLY', 1 );

// Set flag that this is an ajax call
define( '_STS_SESSION_AJAX', 1 );

// Setup Session
require_once( "include/sts_session_setup.php" );

require_once( "include/sts_config.php" );

error_reporting(E_ALL);
ini_set('display_errors', '1');

$sts_debug = isset($_GET['debug']) && in_group( EXT_GROUP_DEBUG );
require_once( "include/sts_session_class.php" );

$my_session = sts_session::getInstance( $exspeedite_db, $sts_debug );
$my_session->access_check( EXT_GROUP_ADMIN );	// Make sure we should be here

if( isset($_GET["CLIENT"]) ) {
	
	require_once( "include/sts_ftp_class.php" );
	$ftp = sts_ftp::getInstance($exspeedite_db, $sts_debug);
	
	$cwd = '/';
	if( isset($_GET["CWD"]) ) {
		$cwd = $_GET["CWD"];
	}
	echo $ftp->ftp_browse( $_GET["CLIENT"], 'exp_browse_ftp.php', $cwd );
}

?>

	<script language="JavaScript" type="text/javascript"><!--
		
		$(document).ready( function () {
			$('div.modal-content a.browse_ftp').on('click', function(event) {
				console.log('in modal', $(this).attr("href") );
				event.preventDefault();
				$('div.modal-body').html('<p class="text-center"><img src="images/loading.gif" alt="loading" width="125" height="125" /></p>');
				$('div.modal-body').load($(this).attr("href"));
				return false;					
			});

		});
	//--></script>
