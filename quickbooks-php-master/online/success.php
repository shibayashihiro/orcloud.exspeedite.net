<?php

// Set flag that this is a parent file
define( '_STS_INCLUDE', 1 );

// Setup Session
require_once( "../../include/sts_session_setup.php" );

require_once dirname(__FILE__) . '/config.php';

require_once( "include/sts_session_class.php" );

$my_session = sts_session::getInstance( $exspeedite_db, $sts_debug );
$my_session->access_check( EXT_GROUP_ADMIN );	// Make sure we should be here

define( 'EXP_RELATIVE_PATH', '../../' );

$sts_subtitle = "Connect QBOE";
require_once( "include/header_inc.php" );
?>
	<div class="container-full" role="main">
		<h2 class="text-center text-success"><span class="glyphicon glyphicon-ok"></span> You're connected! Please wait...</h2>
		
		<br>
		<br>
		<p class="text-center">(If this hangs, please check setting main/URL_PREFIX matches the start of the URL)</p>
	
		<script type="text/javascript">
			window.opener.location.reload(false);
			window.setTimeout('window.close()', 2000);
		</script>
		
	</div>
<?php

require_once( "include/footer_inc.php" );
?>
