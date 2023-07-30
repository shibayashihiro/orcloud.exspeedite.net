<?php 

// $Id: exp_kt_position_admin.php 5107 2023-05-22 17:35:38Z duncan $
// KeepTruckin position updates admin interface

// Set flag that this is a parent file
define( '_STS_INCLUDE', 1 );

// Setup Session
require_once( "include/sts_session_setup.php" );

require_once( "include/sts_config.php" );

$sts_debug = isset($_GET['debug']) && in_group( EXT_GROUP_DEBUG );
require_once( "include/sts_session_class.php" );

$my_session = sts_session::getInstance( $exspeedite_db, $sts_debug );
$my_session->access_check( EXT_GROUP_ADMIN );	// Make sure we should be here

$sts_subtitle = "KeepTruckin Position Updates";
if( ! $sts_debug ) {
	require_once( "include/header_inc.php" );
	require_once( "include/navbar_inc.php" );
}

?>
<div class="container-full" role="main">
	<div class="well well-lg">
	<h2><img src="images/keeptruckin.png" alt="keeptruckin" height="40" /> Position Updates</h2>
	<br>
<?php

require_once( "include/sts_ifta_log_class.php" );

$kt = sts_keeptruckin::getInstance($exspeedite_db, $sts_debug);

$listurl = 'https://api.keeptruckin.com/v1/company_webhooks';

//! IMPORTANT - I have not been able to get this to work over https to the
// satisfaction of KeepTruckin server. This reverts back to http
$myurl = str_replace('https', 'http', $sts_crm_root) .'/exp_kt_vehicle_location_updated.php';
//$myurl = $sts_crm_root.'/exp_kt_vehicle_location_updated.php';

if( isset($_GET["DISABLE"])) {
	// Turn off output buffering
	ini_set('output_buffering', 'off');
	// Implicitly flush the buffer(s)
	ini_set('implicit_flush', true);
	ob_implicit_flush(true);

	if (ob_get_level() == 0) ob_start();

	echo '<p id="disable" class="text-center"><img src="images/EXSPEEDITEsmr.png" alt="EXSPEEDITEsmr" width="284" height="50" /> <img src="images/animated-arrow-right.gif" alt="animated-arrow-right" height="50" /> <img src="images/keeptruckin.png" alt="keeptruckin" height="50" /><br>Removing webhook from KeepTruckin</p>';
	
	ob_flush(); flush();

	//! Log when we do disable KeepTruckin
	require_once( "include/sts_user_log_class.php" );
	$user_log_table = sts_user_log::getInstance($exspeedite_db, $sts_debug);
	$user_log_table->log_event('admin', 'disable KeepTruckin posiion updates');
	
	$users = $kt->admin_users();
	$admin_user_id = 0;
	if( is_array($users) && count($users) > 0 && 
		is_array($users[0]) && is_array($users[0]["user"]) &&
		isset($users[0]["user"]["id"]))
		$admin_user_id = $users[0]["user"]["id"];
	

	$hooks = explode(',', $_GET["DISABLE"]);
	
	foreach( $hooks as $hook ) {
		$delurl = 'https://api.keeptruckin.com/v1/company_webhooks/'.$hook;
		
		$response = $kt->curl_del( $delurl, true, $admin_user_id );
		
		if( ! (is_object($response) && isset($response->success)) ) {
			echo "<pre>";
			var_dump($response);
			echo "</pre>";
		}
	}
	sleep(2);
	update_message( 'disable', '' );
} else if( isset($_GET["ENABLE"])) {
	// Turn off output buffering
	ini_set('output_buffering', 'off');
	// Implicitly flush the buffer(s)
	ini_set('implicit_flush', true);
	ob_implicit_flush(true);

	if (ob_get_level() == 0) ob_start();

	echo '<p id="enable" class="text-center"><img src="images/EXSPEEDITEsmr.png" alt="EXSPEEDITEsmr" width="284" height="50" /> <img src="images/animated-arrow-right.gif" alt="animated-arrow-right" height="50" /> <img src="images/keeptruckin.png" alt="keeptruckin" height="50" /><br>Adding webhook to KeepTruckin</p>';
	
	ob_flush(); flush();

	//! Log when we do enable KeepTruckin
	require_once( "include/sts_user_log_class.php" );
	$user_log_table = sts_user_log::getInstance($exspeedite_db, $sts_debug);
	$user_log_table->log_event('admin', 'enable KeepTruckin posiion updates');

	$addurl = 'https://api.keeptruckin.com/v1/company_webhooks';
	$actions = array();
	$actions[] = 'vehicle_location_updated';
	
	$users = $kt->admin_users();
	$admin_user_id = 0;
	if( is_array($users) && count($users) > 0 && 
		is_array($users[0]) && is_array($users[0]["user"]) &&
		isset($users[0]["user"]["id"]))
		$admin_user_id = $users[0]["user"]["id"];

	$data = array('url' => $myurl,
		'secret' => '67298a4636cb6e07ceabf750bb63e99d',
		'enabled' => 'true',
		'format' => 'json',
		'actions' => $actions);
	if( $sts_debug ) {
		echo "<pre>Webhook data:\n";
		var_dump($data);
		echo "</pre>";
	}
	
	//$args = http_build_query(array('data' => $data));
	$args = http_build_query($data);
	
	//$args = preg_replace('/%5B[0-9]+%5D/simU', '%5B%5D', $args);
	
	$response = $kt->post( $addurl.'?'.$args, true, $admin_user_id );

	if( strpos($response, 'company_webhook') === false ) {
		echo '<p>Enable failed. Unless it says invalid API key, you might want to try again.</p>
		<pre>';
		var_dump($response);
		echo "</pre>";
	}
	sleep(2);
	update_message( 'enable', '' );
}


$data = $kt->fetch( $listurl, true );	// true = use old method
if( $sts_debug ) {
	echo "<pre>List webhooks\n";
	var_dump($data);
	echo "</pre>";
}
$enabled = array();
$hooks = array();
$urls = array();
if( is_array($data) ) {
	foreach( $data as $webhook ) {
if( $sts_debug ) {
	echo "<pre>webhook\n";
	var_dump($webhook);
	echo "</pre>";
}
		if( is_array($webhook["company_webhook"]) &&
			isset($webhook["company_webhook"]["url"]) ) {
			$hooks[] = $id = $webhook["company_webhook"]["id"];
			$urls[$id] = $webhook["company_webhook"]["url"];
			$enabled[$id] = $webhook["company_webhook"]["enabled"];
		}
	}
}
echo '<div class="panel panel-default">
 <div class="panel-body">';
if( count($hooks) > 0 ) {
	echo '<p>Webhooks found for this account:</p><ol>';
	foreach( $hooks as $id ) {
		echo '<li>id='.$id.' '.$urls[$id].' <strong>'.($enabled[$id] ? 'enabled' : 'disabled').'</strong></li>';
	}
	echo '</ol>';
} else {
	echo '<p>No webhooks found for this account.</p>';
}
echo '</div>
</div>';
	

if( $enabled ) {
	echo '<h3>Position updates are ENABLED. <a class="btn btn-md btn-danger tip" href="exp_kt_position_admin.php?DISABLE='.implode(',', $hooks).'" title="Stop sending all those position updates"><span class="glyphicon glyphicon-remove"></span> Disable</a></h3>
	<p>KeepTruckin will be sending us position updates for tractors, as many as several per minute, depending upon how many tractors there are.</p>
	<p>You will be able to see updated tractor positions if you look at the <a href="exp_listtractor.php">tractors</a> screen. Look for "Mobile Location".</p>
	<p>If you have not done so, you can import/update tractors from KeepTruckin here <a class="btn btn-sm btn-default tip" href="exp_import_tractors_kt.php" title="Import tractors from KeepTruckin"><img src="images/keeptruckin.png" alt="keeptruckin" height="18"></a> Based on the VIN numbers, it correlates KeepTruckin vehicle ids with tractors in Exspeedite. If a tractor is not found with a matching VIN number, it is added. You can then edit it to add further information.</p>
	<p>You can also import/update drivers from KeepTruckin here <a class="btn btn-sm btn-default tip" href="exp_import_drivers_kt.php" title="Import drivers from KeepTruckin"><img src="images/keeptruckin.png" alt="keeptruckin" height="18"></a> Based on the first and last name, it correlates KeepTruckin user ids with drivers in Exspeedite. If a driver is not found with a matching first and last name, it is added. You can then edit it to add further information.</p>
	';
} else {
	echo '<h3>Position updates are DISABLED. <a class="btn btn-md btn-danger tip" href="exp_kt_position_admin.php?ENABLE" title="Send lots of position updates"><span class="glyphicon glyphicon-plus"></span> Enable</a></h3>
	<p>KeepTruckin is not sending us position updates for tractors.</p>';
}

echo '<br>
<h4>You can login to KeepTruckin here --> <a class="btn btn-md btn-default tip" href="https://keeptruckin.com/log-in" title="Login to KeepTruckin" target="_blank"><img src="images/keeptruckin.png" alt="keeptruckin" height="18"> <strong>Login</strong></a></h4>
</div>';

if( ! $sts_debug )
	require_once( "include/footer_inc.php" );
?>
