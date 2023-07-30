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

$sts_subtitle = "Disconnect QBOE";
require_once( "include/header_inc.php" );

if( $multi_company && $sts_qb_multi ) {
	require_once( "include/sts_company_class.php" );
	$company_table = sts_company::getInstance($exspeedite_db, $sts_debug);
	$the_tenant = 1;
}

$the_tenant = $_COOKIE["the_tenant"];

$IntuitAnywhere = new QuickBooks_IPP_IntuitAnywhere(QuickBooks_IPP_IntuitAnywhere::OAUTH_V2,
	 $sts_qb_sandbox, $scope, $sts_qb_dsn, $encryption_key, $oauth2_client_id,
	 $oauth2_client_secret, $quickbooks_oauth_url, $quickbooks_success_url);

echo '<div class="container-full" role="main">
';

if ($IntuitAnywhere->disconnectV2($the_tenant)) {
	echo '<h2 class="text-center text-success">
			<span class="glyphicon glyphicon-ok"></span> DISCONNECTED FROM QBOE! Please wait...
		</h2>
		
		
		<script type="text/javascript">
			window.setTimeout(\'window.location = \\\'./setup.php\\\';\', 2000);
		</script>
	';
} else {
		echo '<h2 class="text-danger"><span class="glyphicon glyphicon-warning-sign"></span> Error: '. http_error($IntuitAnywhere->errorNumber())." ".$IntuitAnywhere->errorMessage()."</h2>";
		if( $IntuitAnywhere->errorNumber() == 504 ) {
			echo '<br><p>You might be successful if you retry</p>
			<h2><a class="btn btn-default" href="disconnect.php">Retry</a> <a class="btn btn-default" href="setup.php">Abort</a></h2>';
		} else {
			echo '<br><br><h2><a class="btn btn-md btn-default" href="setup.php">Abort</a></h2>';
		}
}

echo '</div>';

?>
