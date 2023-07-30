<?php

// Set flag that this is a parent file
define( '_STS_INCLUDE', 1 );

// Setup Session
require_once( "../../include/sts_session_setup.php" );

require_once dirname(__FILE__) . '/config.php';

require_once( "include/sts_session_class.php" );

$my_session = sts_session::getInstance( $exspeedite_db, $sts_debug );
$my_session->access_check( EXT_GROUP_ADMIN );	// Make sure we should be here

require_once dirname(__FILE__) . '/qb_online_api.php';

define( 'EXP_RELATIVE_PATH', '../../' );

//! SCR# 239 - multi-company, use multiple Quickbooks companies
require_once( "include/sts_company_class.php" );

// Turn off output buffering
ini_set('output_buffering', 'off');
// Implicitly flush the buffer(s)
ini_set('implicit_flush', true);
ob_implicit_flush(true);

if (ob_get_level() == 0) ob_start();

$company_table = sts_company::getInstance($exspeedite_db, $sts_debug);
if( $multi_company && $sts_qb_multi ) {

	if( ! isset($_SESSION['SETUP_COMPANY']) ) $_SESSION['SETUP_COMPANY'] =
		$company_table->default_company();
	if( isset($_POST['SETUP_COMPANY']) ) $_SESSION['SETUP_COMPANY'] = $_POST['SETUP_COMPANY'];

	list($quickbooks_is_connected, $realm, $Context) =
		connect_to_quickbooks( $_SESSION['SETUP_COMPANY'] );
	setcookie("the_tenant", $_SESSION['SETUP_COMPANY']);
} else {
	list($quickbooks_is_connected, $realm, $Context) = connect_to_quickbooks();
	setcookie("the_tenant", $the_tenant);
	if( $sts_debug ) {
		echo "<pre>Duncan0\n";
		var_dump($sts_qb_online);
		var_dump($quickbooks_is_connected, $realm, $Context);
		echo "</pre>";
	}
}

//! Uncomment this to log username/tenant
//$api = sts_quickbooks_online_API::getInstance(false, false, $exspeedite_db, $sts_debug);

//$api->log_event( "setup: the_username = ".
//	(isset($_COOKIE["the_username"]) ? $_COOKIE["the_username"] : 'not set')." the_tenant = ".
//	(isset($_COOKIE["the_tenant"]) ? $_COOKIE["the_tenant"] : 'not set'), EXT_ERROR_DEBUG);

$sts_subtitle = "Setup QBOE (OAuth 2.0)";
require_once( "include/header_inc.php" );
if( ! $sts_debug )
	require_once( "include/navbar_inc.php" );
require_once( "include/sts_client_class.php" );
require_once( "include/sts_carrier_class.php" );

if( isset($_GET["SPAWN"]) && $sts_qb_online ) {
	require_once( $sts_crm_dir.DIRECTORY_SEPARATOR."quickbooks-php-master".
		DIRECTORY_SEPARATOR."online".DIRECTORY_SEPARATOR."spawn_process.php" );
}

$login_url = $sts_qb_sandbox ? 'https://sandbox.qbo.intuit.com' : 'https://qbo.intuit.com';

$driver = QuickBooks_Utilities::driverFactory($sts_qb_dsn, $driver_options);

// Login, in order to get a ticket. We need that for queueStatus() calls
$wait = 5;
$min_run = 1;
$ticket = $driver->authLogin($user, $pass, $sts_qb_company_file, $wait, $min_run, true);
	if( $sts_debug ) {
		echo "<pre>Duncan0a\n";
		var_dump($user, $pass, $sts_qb_company_file, $wait, $min_run);
		var_dump($ticket);
		echo "</pre>";
	}

$queue_size = $driver->queueLeft($user);

/*
	<div class="alert alert-warning alert-dismissable">
  <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
  <h3><strong>Warning!</strong> OAuth 2.0 IN DEVELOPMENT - PLEASE LEAVE ALONE.</h3>
</div>
*/
echo '<div class="container-full" role="main">


	<form class="form-inline" role="form" action="setup.php" 
					method="post" enctype="multipart/form-data" 
					name="SETUP_QBOE" id="SETUP_QBOE">';
	if( $sts_debug ) {
		echo "<pre>Duncan1\n";
		var_dump($sts_qb_online);
		var_dump($quickbooks_is_connected);
		echo "</pre>";
	}

if( $sts_qb_online && $quickbooks_is_connected ) {
	$CompanyInfoService = new QuickBooks_IPP_Service_CompanyInfo();
	$quickbooks_CompanyInfo = $CompanyInfoService->get($Context, $realm);
	
	$qb_country = $quickbooks_CompanyInfo ? $quickbooks_CompanyInfo->getCountry() : '';
	
	if( $sts_debug ) {
		echo "<pre>Duncan2\n";
		var_dump($CompanyInfoService, $quickbooks_CompanyInfo);
		var_dump($qb_country, $CompanyInfoService->lastRequest(), $CompanyInfoService->lastResponse() );
		echo "</pre>";
	}
} else {
	$qb_country = '';
}


echo '<h2>'.$sts_subtitle.' Step 1 <div class="form-group">'.
	($multi_company && $sts_qb_multi ? $company_table->menu( $_SESSION['SETUP_COMPANY'], 'SETUP_COMPANY', '', true, false, 'md' ) : '').
	($sts_qb_online && $quickbooks_is_connected ? '<a class="btn btn-md btn-success" href="setup.php?SPAWN"'.($queue_size <= 0 ? ' disabled' : '').'><span class="glyphicon glyphicon-refresh"></span> Process Queue ('.plural( $queue_size, 'item' ).')</a> <a class="btn btn-md btn-info" href="'.$login_url.'" target="_blank"><img src="images/qb_icon.png" alt="qb_icon" width="24" height="24" style="margin-top: -4px; margin-bottom: -4px;" valign="middle"/> Quickbooks Online</a> 
	
	<div class="btn-group"><a class="btn btn-md btn-default" href="setup2.php">Step 2</a>'.
	'<a class="btn btn-md btn-default" href="setup3.php">3'.
	($qb_country == 'CA' ? '' : ' <span class="glyphicon glyphicon-arrow-right"></span>').'</a>'.
	($qb_country == 'CA' ? '<a class="btn btn-md btn-default" href="setup4.php">4 (Canada) <span class="glyphicon glyphicon-arrow-right"></span></a>' : '')
	 : '').'</div></h2></form>';

	ob_flush(); flush();

if( isset($sts_queue_process) && $sts_queue_process ) {
	echo '<p><span class="label label-info">Quickbooks queue process spawned, id='.$sts_queue_process.'</span></p>';
}

if( ! $sts_qb_online ) {
?>
	<div class="panel panel-danger">
		<div class="panel-heading">
			<h3 class="panel-title"><span class="glyphicon glyphicon glyphicon-remove-circle"></span> Quickbooks Online <b>NOT</b> Enabled</h3>
		</div>
		<div class="panel-body">
			Go to <a class="btn btn-success" href="../../exp_listsetting.php">Admin -> Settings</a> and change <strong>api/QUICKBOOKS_ONLINE</strong> to <strong>true</strong>
		</div>
	</div>
<?php

} else if( ! $quickbooks_is_connected ) {
	$prefix = $setting_table->url_prefix();

?>
		<!-- Every page of your app should have this snippet of Javascript in it, so that it can show the Blue Dot menu -->
		<script type="text/javascript" src="https://appcenter.intuit.com/Content/IA/intuit.ipp.anywhere.js"></script>
		<script type="text/javascript">
		intuit.ipp.anywhere.setup({
			menuProxy: '<?php print($quickbooks_oauth_url); ?>',
			grantUrl: '<?php print($quickbooks_oauth_url); ?>'
		});
		</script>

	<div class="panel panel-danger">
		<div class="panel-heading">
			<h3 class="panel-title"><span class="glyphicon glyphicon glyphicon-remove-circle"></span> <b>NOT</b> Connected To Quickbooks Online!&nbsp;&nbsp;<?php if( ! empty($reconnect_err) ) echo ' <span class="label label-warning">'.$reconnect_err.'</span>'; ?> 
			<div class="btn-group pull-right"><button class="btn btn-success btn-sm" style="margin: -5px;" disabled><?php echo plural($queue_size, 'item'); ?> in Queue </button>
				<a href="https://community.intuit.com/articles/1146033-outage-and-planned-maintenance-information" target="_blank" class="btn btn-success btn-sm tip" title="Articles from QuickBooks about outages" style="margin: -5px;"><span class="glyphicon glyphicon glyphicon-info-sign"></span> QB Outage Info</a> <a href="http://downdetector.com/status/quickbooks-intuit" target="_blank" class="btn btn-success btn-sm tip" title="Independant info about QuickBooks outages" style="margin: -5px;"><span class="glyphicon glyphicon glyphicon-info-sign"></span> QB Down Detector</a></div></h3>
		</div>
		<div class="panel-body">
			<br>
			<ipp:connectToIntuit></ipp:connectToIntuit>
			<br>
			<br>
			You must authenticate to QuickBooks <b>once</b> before you can exchange data with it. <br>
			<br>
			<strong>You only have to do this once!</strong> <br><br>
			<a href="truncate.php" class="btn btn-danger btn-sm tip pull-right" title="Warning: this earses all connection information! Invoices & bills will need to be re-queued. There be dragons, arr! Use as a last resort." style="margin: -5px;"><img src="images/skull-crossbones.png" alt="skull-crossbones" width="24" height="24"> Truncate QBOE tables</a>
<?php
	//! SCR# 429 - show if main/URL_PREFIX is not set correct
	if( $sts_url_prefix <> $prefix ) {
		echo '<div class="alert alert-danger" role="alert"><strong>Warning:</strong> setting main/URL_PREFIX ('.$sts_url_prefix.') does not match ('.$prefix.')<br>
		This may block proper authentication.</div>';
	}	
?>			
			After you've authenticated once, you should not have to go 
			through this connection process again. <br>
			Click the button above to 
			authenticate and connect.
		</div>
	</div>
<?php
} else {
	$CompanyInfoService = new QuickBooks_IPP_Service_CompanyInfo();
	$quickbooks_CompanyInfo = $CompanyInfoService->get($Context, $realm);
	
	if( $quickbooks_CompanyInfo ) {
		//! Setup Company			
		$qb_name = $quickbooks_CompanyInfo->getCompanyName();
		$qb_country = $quickbooks_CompanyInfo->getCountry();
		if( $qb_country == 'CA' ) $qb_country = 'Canada';
		else if( $qb_country == 'US' ) $qb_country = 'USA';
		else $qb_country = NULL;
		$qb_addr = format_qb_addr( $quickbooks_CompanyInfo->getCompanyAddr(), $qb_country );

		$qb_number = $quickbooks_CompanyInfo->getPrimaryPhone();
		$qb_number = is_object($qb_number) ? $qb_number->getFreeFormNumber() : '';
		
		$qb_email = $quickbooks_CompanyInfo->getEmail();
		$qb_email = is_object($qb_email) ? $qb_email->getAddress() : '';
	
		$qb_web = $quickbooks_CompanyInfo->getWebAddr();
		$qb_web = is_object($qb_web) ? $qb_web->getURI() : '';
		
		//! Check preferences for home currency
		$PreferencesService = new QuickBooks_IPP_Service_Preferences();
		$qb_prefs = $PreferencesService->get($Context, $realm);
		$qb_currency = $qb_prefs->getCurrencyPrefs()->getHomeCurrency();

		list($sts_company_name, $sts_company_line1, $sts_company_line2,
			$sts_company_city, $sts_company_state, $sts_company_postal, $sts_company_country,
			$sts_company_email, $sts_company_phone, $sts_company_web, $sts_company_currency) =
				$company_table->get_info(
					isset($_SESSION['SETUP_COMPANY']) ? $_SESSION['SETUP_COMPANY'] : 0);

		$sts_addr = format_addr( $sts_company_line1, $sts_company_line2, $sts_company_city,
			$sts_company_state, $sts_company_postal, $sts_company_country );
?>
	<div class="panel panel-success">
		<div class="panel-heading">
			<h3 class="panel-title"><span class="glyphicon glyphicon glyphicon-ok-circle"></span> Connected to Quickbooks Online!&nbsp;&nbsp;<?php 
				if( $reconnected ) echo ' <span class="label label-info">Reconnected</span>';
				if( ! empty($reconnect_err) ) echo ' <span class="label label-info">'.$reconnect_err.'</span>';
				if( $sts_qb_sandbox ) echo ' <span class="label label-info">Sandbox Version</span>'; ?>&nbsp;&nbsp;&nbsp;&nbsp;
			<a href="disconnect.php" class="btn btn-danger btn-sm pull-right" style="margin: -5px;"><span class="glyphicon glyphicon glyphicon-remove"></span> Disconnect</a></h3>
		</div>
		<div class="panel-body">
			<div class="row tighter">
				<div class="col-sm-6">
					<div class="alert alert-info tighter" role="alert">
						<div class="row">
							<div class="col-sm-12"><img src="images/qbo_logo.png" alt="qbo_logo" width="263" height="80" /><br><strong>Quickbooks Online Company Settings</strong></div>
						</div>
						<div class="row">
							<div class="col-sm-4">Company:</div>
							<div class="col-sm-8"><?php echo $qb_name; ?></div>
						</div>
						<div class="row">
							<div class="col-sm-4">Address:</div>
							<div class="col-sm-8"><?php echo $qb_addr; ?></div>
						</div>
						<div class="row">
							<div class="col-sm-4">Phone:</div>
							<div class="col-sm-8"><?php echo $qb_number; ?></div>
						</div>
						<div class="row">
							<div class="col-sm-4">Email:</div>
							<div class="col-sm-8"><?php echo $qb_email; ?></div>
						</div>
						
						<div class="row">
							<div class="col-sm-4">Web URL:</div>
							<div class="col-sm-8"><?php echo $qb_web; ?></div>
						</div>
			
						<div class="row">
							<div class="col-sm-4">Currency:</div>
							<div class="col-sm-8"><?php echo $qb_currency; ?></div>
						</div>
					</div>
				</div>
				<div class="col-sm-6">
					<div class="alert alert-success tighter" role="alert">
						<div class="row">
							<div class="col-sm-12"><img src="../../images/EXSPEEDITEsmr.png" alt="EXSPEEDITEsmr" width="284" height="50" style="margin-bottom: 30px;"><br><strong>Exspeedite Company Settings</strong></div>
						</div>
						<div class="row">
							<div class="col-sm-4">Company:</div>
							<div class="col-sm-8"><?php echo $sts_company_name; ?></div>
						</div>
						<div class="row">
							<div class="col-sm-4">Address:</div>
							<div class="col-sm-8"><?php echo $sts_addr; ?></div>
						</div>
						<div class="row">
							<div class="col-sm-4">Phone:</div>
							<div class="col-sm-8"><?php echo $sts_company_phone; ?></div>
						</div>
						<div class="row">
							<div class="col-sm-4">Email:</div>
							<div class="col-sm-8"><?php echo $sts_company_email; ?></div>
						</div>
						
						<div class="row">
							<div class="col-sm-4">Web URL:</div>
							<div class="col-sm-8"><?php echo $sts_company_web; ?></div>
						</div>
			
						<div class="row">
							<div class="col-sm-4">Currency:</div>
							<div class="col-sm-8"><?php echo $sts_company_currency; ?></div>
						</div>
					</div>
				</div>
			</div>
			<?php
			if( $qb_name <> $sts_company_name || $qb_addr <> $sts_addr ||
				$qb_number <> $sts_company_phone || $qb_email <> $sts_company_email ||
				$qb_web <> $sts_company_web || $qb_currency <> $sts_company_currency) {
				echo '<h3><span class="glyphicon glyphicon glyphicon-warning-sign text-danger"></span> Company information does not match. <a href="updatecompany.php" class="btn btn-md btn-danger"><img src="images/qb_icon.png" alt="qb_icon" width="24" height="24" style="margin-top: -4px; margin-bottom: -4px;" >&nbsp;&nbsp;<span class="glyphicon glyphicon glyphicon-arrow-right"></span>&nbsp;&nbsp;Update Exspeedite Company Settings</a></h3>';
			} else {
				echo '<h3 class="text-success tighter"><span class="glyphicon glyphicon glyphicon-ok"> </span> Company information matches.</h3>';
			}
			?>
		</div>
	</div>
<?php
	} else {
		echo '<div class="well well-md">
		<h3><span class="glyphicon glyphicon glyphicon-warning-sign text-danger"></span>  Company information unavailable. Possible issue with connecting to Quickbooks.<br>
		Please check with your administrator. <a href="disconnect.php" class="btn btn-danger btn-sm pull-right" style="margin: -5px;"><span class="glyphicon glyphicon glyphicon-remove"></span> Disconnect</a></h3>
		</div>';
	}
}

echo '</div>';

?>

	<script language="JavaScript" type="text/javascript"><!--
		
		$(document).ready( function () {

			$('#QB_CLIENTS, #QB_VENDORS').dataTable({
		        //"bLengthChange": false,
		        "bFilter": false,
		        "bSort": false,
		        "bInfo": false,
				//"bAutoWidth": false,
				//"bProcessing": true, 
				"sScrollX": "100%",
				"sScrollY": "200px",
				//"sScrollXInner": "200%",
		        //"lengthMenu": [[-1, 25, 50], ["All", 25, 50]],
				"bPaginate": false,
				"bScrollCollapse": true,
				"bSortClasses": false		
			});
		});
		
	//--></script>

<?php

require_once( "include/footer_inc.php" );
?>
