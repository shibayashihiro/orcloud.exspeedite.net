<?php 

// $Id: index.php 5126 2023-06-08 03:23:24Z dev $
//! Home page

// Set flag that this is a parent file
define( '_STS_INCLUDE', 1 );
define( '_STS_ANIMATE', 1 );

// Setup Session
require_once( "include/sts_session_setup.php" );

require_once( "include/sts_config.php" );
$duncan_ip = '69.50.171.189';
$sts_debug = isset($_GET['debug']); // && in_group( EXT_GROUP_DEBUG );
//if( false && $_SERVER["REMOTE_ADDR"] == $duncan_ip ) {
if( isset($_GET['office'])) {
	$_SESSION['EXT_USER_OFFICE'] = $_GET['office'];
	reload_page ( 'index.php' );
}

if( isset($_GET['xyzzy'])) {
	$sts_debug = true;
	ob_implicit_flush(true);
	
	if (ob_get_level() == 0) ob_start();
}
require_once( "include/sts_session_class.php" );
if( $sts_debug ) {
	echo "<p>1. after include session_class</p>";
	ob_flush(); flush();
}
$my_session = sts_session::getInstance( $exspeedite_db, $sts_debug );
if( $sts_debug ) {
	echo "<p>2. after sts_session::getInstance</p>";
	ob_flush(); flush();
}
$my_session->access_check( EXT_GROUP_USER );	// Make sure we should be here
if( $sts_debug ) {
	echo "<p>3. after access_check</p>";
	ob_flush(); flush();
}

//! If true display only my clients
$my_sales_clients =  $my_session->cms_restricted_salesperson();

$sts_subtitle = "Welcome to Exspeedite";
require_once( "include/header_inc.php" );
require_once( "include/navbar_inc.php" );
require_once( "include/sts_db_class.php" );
require_once( "include/sts_shipment_class.php" );
require_once( "include/sts_load_class.php" );
require_once( "include/sts_result_class.php" );
require_once( "include/sts_setting_class.php" );
require_once( "include/sts_office_class.php" );
require_once( "include/sts_client_activity_class.php" );
require_once( "include/sts_exchange_class.php" );
require_once( "include/sts_message_class.php" );
require_once( "include/sts_alert_class.php" );
require_once( "include/sts_email_class.php" );
if( $sts_debug ) {
	echo "<p>4. after requires</p>";
	ob_flush(); flush();
}

$setting_table = sts_setting::getInstance( $exspeedite_db, false );
$office_table = sts_office::getInstance($exspeedite_db, $sts_debug);
$message_table = sts_message::getInstance($exspeedite_db, $sts_debug);
$alert_table = sts_alert::getInstance($exspeedite_db, $sts_debug);
$email_queue = sts_email_queue::getInstance($exspeedite_db, $sts_debug);

if( $sts_debug ) {
	echo "<p>5. after sts_setting::getInstance</p>";
	ob_flush(); flush();
}
$sts_export_qb = $setting_table->get( 'api', 'EXPORT_QUICKBOOKS' ) == 'true';
$sts_export_sage50 = $setting_table->get( 'api', 'EXPORT_SAGE50_CSV' ) == 'true';
$sts_qb_online = $setting_table->get( 'api', 'QUICKBOOKS_ONLINE' ) == 'true';
$edi_enabled = ($setting_table->get("api", "EDI_ENABLED") == 'true');
$sts_pcm_enabled = $setting_table->get( 'api', 'PCM_API_KEY' ) <> '';
$ifta_enabled = ($setting_table->get("api", "IFTA_ENABLED") == 'true');
$cms_enabled = ($setting_table->get("option", "CMS_ENABLED") == 'true');
$cms_salespeople_leads = ($setting_table->get("option", "CMS_SALESPEOPLE_LEADS") == 'true');
$multi_company = ($setting_table->get("option", "MULTI_COMPANY") == 'true');
$multi_currency = ($setting_table->get("option", "MULTI_CURRENCY") == 'true');
$sts_company_name = $office_table->office_name();
$sts_po_fields = $setting_table->get( 'option', 'PO_FIELDS' ) == 'true';
$sts_alert_expired = $setting_table->get( 'option', 'ALERT_EXPIRED' );
$sts_inspection_reports = $setting_table->get( 'option', 'INSPECTION_REPORTS' ) == 'true';
$scr_enabled = ($setting_table->get("option", "SCR_ENABLED") == 'true');
$default_active = ($setting_table->get("option", "DEFAULT_ACTIVE") == 'true');	//! SCR# 533

ob_flush(); flush();

if( ! isset($_SESSION['EMAIL_QUEUE_EXPIRE']) && in_array(date('N'), [1, 3, 5, 7])  ) {
	$_SESSION['EMAIL_QUEUE_EXPIRE'] = true;
	$email_queue->expire();
}

if( $sts_alert_expired != 'false' && isset($_GET['CLEAR_CACHE'])) {
	$alert_table->clear_cache();
	unset($_SESSION["HOME_CACHE"]);
}

if( $sts_debug ) {
	echo "<p>6. after getting settings</p>";
	ob_flush(); flush();
}
if( ! $multi_company ) {
	unset($sts_result_shipments_last5_layout['SS_NUMBER']);
}

if( isset($_GET['USER_REPORTS'])) {
	echo "<pre>";
	var_dump($_SESSION['EXT_USER_REPORTS']);
	echo "</pre>";
} else if( isset($_GET['MSG'])) {
	echo "<pre>";
	var_dump($message_table->my_messages($_SESSION['EXT_USER_CODE']) );
	echo "</pre>";
}


//! This will check for incoming EDI 204's -default is every 10 minutes
require_once( "exp_spawn_edi.php" );
if( $sts_debug ) {
	echo "<p>7. after exp_spawn_edi.php</p>";
	ob_flush(); flush();
}

function buttons( $id, $expired_id = false, $link = false ) {
	return '<h4 class="text-center tighter">'.
	($link ? '<a href="'.$link.'">' : '').
	'<span class="label label-success" id="'.$id.'"><img src="images/loading.gif" alt="loading" width="20" height=""></span>'.
	($expired_id ? '<span class="label label-danger" id="'.$expired_id.'"><img src="images/loading.gif" alt="loading" width="20" height=""></span>' : '').($link ? '</a>' : '').'</h4>';
}

?>
<div class="container-full theme-showcase" role="main">
	<!--
	<div class="alert alert-danger alert-dismissable">
	  <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
	  <strong><span class="glyphicon glyphicon-warning-sign"></span> Development Server</strong><br>
	  Work in progress. Seriously Broken. Very likely to crash on you.<br>
	  Do not use for demos, or make any changes.
	</div>
	-->
<?php
//echo "<p> ios ".$_SESSION['ios']." chrome ".$_SESSION['chrome']."</p>";	

ob_flush(); flush();

$cat = sts_client_activity::getInstance( $exspeedite_db, $sts_debug );
$overdue = $cat->overdue($_SESSION['EXT_USER_CODE']);
if( ! empty($overdue))
	echo $overdue;

//! SCR 159 - show alerts when various dates are expired or expiring soon
echo '<div id="ALERTS_GO_HERE"></div>
';
// Look to exp_get_home_alerts.php

if( $my_session->in_group( EXT_GROUP_ADMIN ) &&
	! isset($_SESSION['EXT_WHO'])) {
	$who = $my_session->who();
	if( $who <> '')
		echo '<p class="text-right">Online: '.$who.'</p>';
	$_SESSION['EXT_WHO'] = true;
}

ob_flush(); flush();

if( $multi_company && ($sts_debug || ! isset($_SESSION['EXT_EC'])) ) {	// Once per session
	$em_table = sts_exchange::getInstance($exspeedite_db, $sts_debug);
	$em_table->get_boc_rates(); // Check exchange rates
	$_SESSION['EXT_EC'] = true;
}

?>

<!-- Nav tabs -->
<ul class="nav nav-tabs">
<?php if( $my_session->in_group( EXT_GROUP_ADMIN ) ) { ?>
  <li><a href="#admin" data-toggle="tab">Admin</a></li>
<?php } ?>
  <li class="active"><a href="#profile" data-toggle="tab"><?php echo $sts_company_name; ?></a></li>
</ul>

<!-- Tab panes -->
<div class="tab-content">
<?php if( $my_session->in_group( EXT_GROUP_ADMIN ) ) { ?>
  <div class="tab-pane" id="admin">
 <div class="row well well-sm">
 <?php if( $multi_company ) { ?>
    <div class="col-xs-4 col-sm-3">
		<a href="exp_listcompany.php"><img class="center-block" src="images/company_icon.png"  height="80" border="0"></a>
      <h4 class="text-center"><span class="label label-success" id="COMPANIES"></span> Companies</h4>
    </div>
    <div class="col-xs-4 col-sm-3">
		<a href="exp_listoffice.php"><img class="center-block" src="images/company_icon.png"  height="80" border="0"></a>
      <h4 class="text-center"><span class="label label-success" id="OFFICES"></span> Offices</h4>
    </div>
<?php } ?>
 <?php if( $scr_enabled ) { ?>
    <div class="col-xs-4 col-sm-3">
		<a href="exp_listscr.php"><img class="center-block" src="images/bug_icon.png"  height="80" border="0"></a>
      <h4 class="text-center">SCRs</h4>
    </div>
<?php } ?>
    <div class="col-xs-4 col-sm-3">
		<a href="exp_listuser.php"><img class="center-block" src="images/user_icon.png"  height="80" border="0"></a>
      <h4 class="text-center"><span class="label label-success" id="USERS"></span> Users</h4>
    </div>
    <div class="col-xs-4 col-sm-3">
		<a href="exp_liststatus_codes.php"><img class="center-block" src="images/status_codes_icon.png"  height="80" border="0"></a>
      <h4 class="text-center">Status Codes</h4>
    </div>
    <div class="col-xs-4 col-sm-3">
		<a href="exp_listbc.php"><img class="center-block" src="images/setting_icon.png"  height="80" border="0"></a>
      <h4 class="text-center">Business Codes</h4>
    </div>

     <div class="col-xs-4 col-sm-3">
		<a href="exp_listitem_list.php"><img class="center-block" src="images/status_codes_icon.png"  height="80" border="0"></a>
      <h4 class="text-center">Item Lists</h4>
    </div>

<?php if( $sts_export_sage50 ) { ?>
     <div class="col-xs-4 col-sm-3">
		<a href="exp_listtax.php"><img class="center-block" src="images/tax_icon.png"  height="80" border="0"></a>
      <h4 class="text-center">CDN Tax Rates</h4>
    </div>
<?php } ?>

<?php if( $multi_currency ) { ?>
     <div class="col-xs-4 col-sm-3">
		<a href="exp_listem.php"><img class="center-block" src="images/exch_icon.png"  height="80" border="0"></a>
      <h4 class="text-center">Exchange Rates</h4>
    </div>
<?php } ?>

   <div class="col-xs-4 col-sm-3">
		<a href="exp_listunit.php"><img class="center-block" src="images/unit_icon.png"  height="80" border="0"></a>
      <h4 class="text-center">Units</h4>
    </div>
    <div class="col-xs-4 col-sm-3">
		<a href="exp_listsetting.php"><img class="center-block" src="images/setting_icon.png"  height="80" border="0"></a>
      <h4 class="text-center">Settings</h4>
    </div>
    <div class="col-xs-4 col-sm-3">
		<a href="exp_listreport.php"><img class="center-block" src="images/setting_icon.png"  height="80" border="0"></a>
      <h4 class="text-center">Reports</h4>
    </div>

<?php if( $sts_export_qb && $sts_qb_online ) { ?>
   <div class="col-xs-4 col-sm-3">
		<a href="quickbooks-php-master/online/setup.php"><img class="center-block" src="quickbooks-php-master/online/images/qb_icon.png"  height="80" border="0"></a>
      <h4 class="text-center">Setup QBOE</h4>
    </div>
 <?php } ?>

 <?php if( $edi_enabled ) { ?>
    <div class="col-xs-4 col-sm-3">
		<a href="exp_listftp.php"><img class="center-block" src="images/edi_icon1.png"  height="80" border="0"></a>
      <h4 class="text-center">EDI FTP Configuration</h4>
    </div>
 <?php } ?>

 <?php if( $ifta_enabled ) { ?>
    <div class="col-xs-4 col-sm-3">
		<a href="exp_list_card_ftp.php"><img class="center-block" src="images/card_ftp_icon.png"  height="80" border="0"></a>
      <h4 class="text-center">Fuel Card Import</h4>
      
    </div>

    <div class="col-xs-4 col-sm-3">
		<a href="exp_import_ifta.php"><img class="center-block" src="images/iftatest.gif"  border="0"></a>
      <h4 class="text-center"><br><br>IFTA Rates<br></h4>
    </div>
 <?php } ?>


    <div class="col-xs-4 col-sm-3">
		<a href="exp_listcommodity_class.php"><img class="center-block" src="images/commodity_class_icon.png"  height="80" border="0"></a>
      <h4 class="text-center">Commodity Classes</h4>
    </div>
    <div class="col-xs-4 col-sm-3">
 		<a href="exp_listcommodity.php"><img class="center-block" src="images/commodity_icon.png"  height="80" border="0"></a>
      <h4 class="text-center">Commodities</h4>
    </div>
    <div class="col-xs-4 col-sm-3">
 		<a href="exp_listzone_filter.php"><img class="center-block" src="images/zone_icon.png"  height="80" border="0"></a>
      <h4 class="text-center">Zone Filters</h4>
    </div>
    <div class="col-xs-4 col-sm-3">
 		<a href="exp_listmanual_miles.php"><img class="center-block" src="images/manual_miles_icon.png"  height="80" border="0"></a>
      <h4 class="text-center">Manual Miles</h4>
    </div>
    <div class="col-xs-4 col-sm-3">
 		<a href="exp_listosd.php"><img class="center-block" src="images/osd_icon.png"  height="80" border="0"></a>
      <h4 class="text-center">OS&D</h4>
    </div>
    <div class="col-xs-4 col-sm-3">
 		<a href="exp_listvacation.php"><img class="center-block" src="images/vacation_icon.png"  height="80" border="0"></a>
      <h4 class="text-center">Vacations</h4>
    </div>
    <div class="col-xs-4 col-sm-3">
		<a href="exp_repair_db.php"><img class="center-block" src="images/setting_icon.png"  height="80" border="0"></a>
      <h4 class="text-center">Repair DB</h4>
    </div>

<?php if( $sts_pcm_enabled ) { ?>
     <div class="col-xs-4 col-sm-3">
		<a href="exp_pcm_cache.php"><img class="center-block" src="images/setting_icon.png"  height="80" border="0"></a>
      <h4 class="text-center">Cache Info</h4>
    </div>
  <?php } ?>

  <div class="col-xs-4 col-sm-3">
 		<a href="exp_listpallet.php"><img class="center-block" src="images/pallet_icon.png"  height="80" border="0"></a>
      <h4 class="text-center"><span class="label label-success" id="PALLETS"></span> Pallet Adjustments</h4>
    </div>
    <div class="col-xs-4 col-sm-3">
 		<a href="exp_view_fsc.php?TYPE=all"><img class="center-block" src="images/searchdate.png"  height="80" border="0"></a>
      <h4 class="text-center">FSC Schedule</h4>
    </div>
    
    <div class="col-xs-4 col-sm-3">
 		<a href="exp_listdriverrates.php"><img class="center-block" src="images/money_bag.png"  height="80" border="0"></a>
      <h4 class="text-center">Driver Rates</h4>
    </div>
    <div class="col-xs-4 col-sm-3">
 		<a href="exp_rate_profile.php"><img class="center-block" src="images/rate-profile.png"  height="80" border="0"></a>
      <h4 class="text-center">Manage Drivers Rate Profile</h4>
    </div>
 	<div class="col-xs-4 col-sm-3">
 		<a href="exp_driverrate.php"><img class="center-block" src="images/118-512.png"  height="80" border="0"></a>
      <h4 class="text-center">Add Driver Rate</h4>
    </div>
   
    <div class="col-xs-4 col-sm-3">
 		<a href="exp_listclientrates.php"><img class="center-block" src="images/money_bag.png"  height="80" border="0"></a>
      <h4 class="text-center">Client Rates</h4>
    </div>
<?php if(0) { ?>
    <div class="col-xs-4 col-sm-3">
  		<a href="exp_fuel_log_display.php"><img class="center-block" src="images/fuel-tax-icon.png"  height="80" border="0"></a>
      <h4 class="text-center">Fuel Tax</h4>
    </div>
<?php } ?>
  </div>
 <br>
  </div>
<?php } ?>
<?php if( $sts_inspection_reports && $my_session->in_group( EXT_GROUP_MECHANIC ) ) { ?>
	<div class="tab-pane active" id="profile">
		<div class="row">
				<div class="col-sm-12 well well-lg">
				<h1 class="text-center">Mechanic</h1>
				<p class="text-center">Please use menu above</p>
			</div>
		</div>
<?php } else { ?>
	<div class="tab-pane active well well-lg" id="profile">
		<div class="row tighter">

<?php 
ob_flush(); flush();

		//! SCR# 424 - New panels
		if( $my_session->in_group( EXT_GROUP_PROFILES ) ) { ?>
			<div class="col-sm-6 squished2">
				<div class="panel panel-info">
					<div class="panel-heading">
						<h3 class="panel-title">Profiles</h3>
					</div>
					<div class="panel-body">

	<?php if( $cms_enabled ) { ?>
						<div class="col-xs-6 col-sm-4">
							<div class="imageAndText">
							<?php echo $my_session->access_link( EXT_GROUP_PROFILES, 'exp_listclient.php?CLIENT_TYPE=lead',  'images/user_icon.png', 'images/folder_blocked.png' ); ?>
							    <div class="col">
								    <?php echo buttons('CLIENT_LEADS', false, 'CLIENT_LEADS', 'exp_listclient.php?CLIENT_TYPE=lead'); ?>
							    </div>
							</div>
							<h4 class="text-center">Leads</h4>
						</div>
						<div class="col-xs-6 col-sm-4">
							<div class="imageAndText">
							<?php echo $my_session->access_link( EXT_GROUP_PROFILES, 'exp_listclient.php?CLIENT_TYPE=prospect',  'images/user_icon.png', 'images/folder_blocked.png' ); ?>
							    <div class="col">
								    <?php echo buttons('CLIENT_PROSPECTS', false, 'exp_listclient.php?CLIENT_TYPE=prospect'); ?>
							    </div>
							</div>
							<h4 class="text-center">Prospects</h4>
						</div>
						<div class="col-xs-6 col-sm-4">
							<div class="imageAndText">
							<?php echo $my_session->access_link( EXT_GROUP_PROFILES, 'exp_listclient.php?CLIENT_TYPE=client',  'images/user_icon.png', 'images/folder_blocked.png' ); ?>
							    <div class="col">
								    <?php echo buttons('CLIENT_CLIENTS', false, 'exp_listclient.php?CLIENT_TYPE=client'); ?>

							    </div>
							</div>
							<h4 class="text-center">Clients</h4>
						</div>
	<?php } else { ?>
						<div class="col-xs-6 col-sm-4">
							<div class="imageAndText">
							<?php echo $my_session->access_link( EXT_GROUP_PROFILES, 'exp_listclient.php?CLIENT_TYPE=client',  'images/user_icon.png', 'images/folder_blocked.png' ); ?>
							    <div class="col">
								    <?php echo buttons('CLIENTS', false, 'exp_listclient.php?CLIENT_TYPE=client'); ?>
							    </div>
							</div>
							<h4 class="text-center">Clients</h4>
						</div>
	<?php } ?>

						<div class="col-xs-6 col-sm-6">
							<div class="imageAndText">
							<?php echo $my_session->access_link( EXT_GROUP_PROFILES, 'exp_listtrailer.php',  'images/trailer_icon.png', 'images/folder_blocked.png' ); ?>
							    <div class="col">
								    <?php echo buttons('TRAILERS', 'TRAILERS_EXPIRED', 'exp_listtrailer.php'); ?>
							    </div>
							</div>
							<h4 class="text-center">Trailers</h4>
						</div>

						<div class="col-xs-6 col-sm-6">
							<div class="imageAndText">
							<?php echo $my_session->access_link( EXT_GROUP_PROFILES, 'exp_listcarrier.php',  'images/carrier_icon.png', 'images/folder_blocked.png' ); ?>
							    <div class="col">
								    <?php echo buttons('CARRIERS', 'CARRIERS_EXPIRED', 'exp_listcarrier.php'); ?>
							    </div>
							</div>
							<h4 class="text-center">Carriers</h4>
						</div>

					</div>
				</div>
			</div>
<?php } ?>

<?php 
ob_flush(); flush();
	
	if( $my_session->in_group( EXT_GROUP_FLEET ) ) { ?>
			<div class="col-sm-6 squished2">
				<div class="panel panel-info">
					<div class="panel-heading">
						<h3 class="panel-title">Fleet</h3>
					</div>
					<div class="panel-body">
						<div class="col-xs-6 col-sm-4">
							<div class="imageAndText">
							<?php echo $my_session->access_link( EXT_GROUP_FLEET, 'exp_listdriver.php',  'images/driver_icon.png', 'images/folder_blocked.png' ); ?>
							    <div class="col">
								    <?php echo buttons('DRIVERS', 'DRIVERS_EXPIRED', 'exp_listdriver.php'); ?>
							    </div>
							</div>
							<h4 class="text-center">Drivers</h4>
						</div>
						<div class="col-xs-6 col-sm-4">
							<div class="imageAndText">
							<?php echo $my_session->access_link( EXT_GROUP_FLEET, 'exp_listtractor.php',  'images/tractor_icon.png', 'images/folder_blocked.png' ); ?>
							    <div class="col">
								    <?php echo buttons('TRACTORS', 'TRACTORS_EXPIRED', 'exp_listtractor.php'); ?>
							    </div>
							</div>
							<h4 class="text-center">Tractors</h4>
						</div>
					</div>
				</div>
			</div>
<?php } ?>

<?php if( $my_session->in_group( EXT_GROUP_SALES ) && ! $my_session->in_group( EXT_GROUP_PROFILES ) ) { ?>
			<div class="col-sm-6 squished2">
				<div class="panel panel-success">
					<div class="panel-heading">
						<h3 class="panel-title">Sales</h3>
					</div>
					<div class="panel-body">
<?php if( $cms_enabled ) { ?>
	<?php if( $cms_salespeople_leads ) { ?>
						<div class="col-xs-6 col-sm-4">
							<div class="imageAndText">
							<?php echo $my_session->access_link( EXT_GROUP_SALES, 'exp_listclient.php?CLIENT_TYPE=lead',  'images/user_icon.png', 'images/folder_blocked.png' ); ?>
							    <div class="col">
								    <?php echo buttons('CLIENT_LEADS', false, 'exp_listclient.php?CLIENT_TYPE=lead'); ?>
							    </div>
							</div>
							<h4 class="text-center">Leads</h4>
						</div>
	<?php } ?>
						<div class="col-xs-6 col-sm-4">
							<div class="imageAndText">
							<?php echo $my_session->access_link( EXT_GROUP_SALES, 'exp_listclient.php?CLIENT_TYPE=prospect',  'images/user_icon.png', 'images/folder_blocked.png' ); ?>
							    <div class="col">
								    <?php echo buttons('CLIENT_PROSPECTS', false, 'exp_listclient.php?CLIENT_TYPE=prospect'); ?>
							    </div>
							</div>
							<h4 class="text-center"><?php if( $my_sales_clients ) echo 'My '; ?> Prospects</h4>
						</div>
						<div class="col-xs-6 col-sm-4">
							<div class="imageAndText">
							<?php echo $my_session->access_link( EXT_GROUP_SALES, 'exp_listclient.php?CLIENT_TYPE=client',  'images/user_icon.png', 'images/folder_blocked.png' ); ?>
							    <div class="col">
								    <?php echo buttons('CLIENT_CLIENTS', false, 'exp_listclient.php?CLIENT_TYPE=client'); ?>
							    </div>
							</div>
							<h4 class="text-center"><?php if( $my_sales_clients ) echo 'My '; ?> Clients</h4>
						</div>
<?php } ?>
					</div>
				</div>
			</div>
<?php } ?>

		</div> <!-- end row -->

		<div class="row tighter">

<?php 
ob_flush(); flush();	
	
	if( $my_session->in_group( EXT_GROUP_SHIPMENTS ) ) { ?>
			<div class="col-sm-<?php echo $my_session->in_group( EXT_GROUP_DISPATCH ) ? '6' : '12'; ?> squished2">
				<div class="panel panel-warning">
					<div class="panel-heading">
							<form role="form" class="form-horizontal" action="exp_addshipment.php" 
								method="get" enctype="multipart/form-data" 
								name="shipment" id="shipment">

								<div class="form-group tighter">
									<div class="col-sm-6">
										<h3 class="panel-title">Shipments</h3>
									</div>
									<div class="col-sm-6">
										<input class="form-control pad2" type=text name="CODE" placeholder="Jump To Shipment#">
									</div>
								</div>
							</form>
					</div>
					<div class="panel-body">
						<div class="col-xs-6 col-sm-6">
							<?php echo $my_session->access_link( EXT_GROUP_SHIPMENTS, 'exp_listshipment.php',  'images/order_icon.png', 'images/folder_blocked.png' ); ?>
							<h4 class="text-center">Shipments</h4>
						</div>
						<div class="col-xs-6 col-sm-6">
							<?php echo $my_session->access_link( EXT_GROUP_SHIPMENTS, 'exp_addshipment.php',  'images/order_icon_plus.png', 'images/folder_blocked.png' ); ?>
							<h4 class="text-center">Add Shipment</h4>
						</div>

						<div class="col-sm-12">
<?php
	if( $multi_company && isset($_SESSION['EXT_USER_OFFICE']) ) {
		$match = "OFFICE_CODE = ".$_SESSION['EXT_USER_OFFICE'];
	} else {
		$match = false;
	}

if( ! $sts_po_fields ) {
	unset($sts_result_shipments_last5_layout['PO_NUMBER']);
}
if( ! $multi_company ) {
	unset($sts_result_shipments_last5_layout['SS_NUMBER']);
}
$match = ($match <> '' ? $match." AND " : "").'SHIPMENT_CODE > ((select max(SHIPMENT_CODE) from EXP_SHIPMENT) - 200)';

	$shipment_table = sts_shipment::getInstance($exspeedite_db, $sts_debug);
	$rslt2 = new sts_result( $shipment_table, $match, $sts_debug, $sts_profiling );
	echo $rslt2->render( $sts_result_shipments_last5_layout, $sts_result_shipments_last5_view );
?>
						</div>
					</div>
				</div>
			</div>
<?php } ?>

<?php 
ob_flush(); flush();	
	
	if( $my_session->in_group( EXT_GROUP_DISPATCH ) ) { ?>
			<div class="col-sm-<?php echo $my_session->in_group( EXT_GROUP_SHIPMENTS ) ? '6' : '12'; ?> squished2">
				<div class="panel panel-warning">
					<div class="panel-heading">
	 					<form role="form" class="form-horizontal" action="exp_viewload.php" 
							method="get" enctype="multipart/form-data" 
							name="load" id="load">
							<div class="form-group tighter">
								<div class="col-sm-6">
									<h3 class="panel-title">Dispatch</h3>
								</div>
								<div class="col-sm-6">
									<input class="form-control pad2" type=text name="CODE" placeholder="Jump To Trip/Load#">
								</div>
							</div>
						</form>
					</div>
					<div class="panel-body">

						<div class="col-xs-6 col-sm-6">
							<?php echo $my_session->access_link( EXT_GROUP_DISPATCH, 'exp_listload.php',  'images/load_icon.png', 'images/folder_blocked.png' ); ?>
							<h4 class="text-center">Loads</h4>
						</div>
						<div class="col-xs-6 col-sm-6">
							<?php echo $my_session->access_link( EXT_GROUP_DISPATCH, 'exp_addload.php',  'images/load_icon_plus.png', 'images/folder_blocked.png' ); ?>
							<h4 class="text-center">Add Load</h4>
						</div>

						<div class="col-sm-12">
<?php
	if( $multi_company && isset($_SESSION['EXT_USER_OFFICE']) ) {
		$match = "OFFICE_CODE = ".$_SESSION['EXT_USER_OFFICE'];
	} else {
		$match = false;
	}
	$match = ($match <> '' ? $match." AND " : "").'load_code > ((select max(LOAD_CODE) from exp_load) - 200)';

	$load_table = sts_load::getInstance($exspeedite_db, $sts_debug);
	$rslt3 = new sts_result( $load_table, $match, $sts_debug, $sts_profiling );
	echo $rslt3->render( $sts_result_loads_last5_layout, $sts_result_loads_last5_view );
?>
						</div>
					</div>
				</div>
			</div>
<?php } ?>

		</div> <!-- end row -->

<?php } 
	
ob_flush(); flush();	
?>
		</div>
	</div>
</div>

<style>
.modal .modal-content{
overflow:hidden;
}
.modal-body{
max-height: 60vh;
overflow-y:scroll; // to get scrollbar only for y axis
}
</style>

<p class="text-center"><a href="exp_about.php">Copyright Information</a></p>

	<div class="modal fade fuzzy bs-example-modal-sm" tabindex="-1" role="dialog" aria-labelledby="mySmallModalLabel" aria-hidden="true" id="motd_modal">
	  <div class="modal-dialog">
		<div class="modal-content">
			<div class="modal-header" id="motd_modal_header">
				<h2 class="modal-title" id="myModalLabel"><span class="text-success"><img src="images/EXSPEEDITEsmr.png" alt="EXSPEEDITEsmr" width="283" height="50" /> Messages</h2>
			</div>
			<div class="modal-body" id="motd_modal_body">
				<p class="text-center"><img src="images/loading.gif" alt="loading" width="125" height="125" /></p>
			</div>
			<div class="modal-footer" id="motd_modal_footer">
			</div>
		</div>
		</div>
	</div>

	<script language="JavaScript" type="text/javascript"><!--
		
		$(document).ready( function () {
			var code = <?php echo (isset($_SESSION['EXT_USER_CODE']) ? $_SESSION['EXT_USER_CODE'] : 0); ?>;

			$('#EXP_LOAD, #EXP_SHIPMENT').dataTable({
		        //"bLengthChange": false,
		        "bFilter": false,
		        "bSort": false,
		        "bInfo": false,
				//"bAutoWidth": false,
				//"bProcessing": true, 
				"sScrollX": "100%",
				//"sScrollY": ($(window).height() - 240) + "px",
				//"sScrollXInner": "200%",
		        //"lengthMenu": [[-1, 25, 50], ["All", 25, 50]],
				"bPaginate": false,
				//"bScrollCollapse": true,
				"bSortClasses": false		
			});
			
			var xha = $.ajax({
				url: 'exp_get_home_alerts.php',
				async: true,
				data: {
					pw: 'Worstershire'
				},
				dataType: "text",
				success: function(data) {
					$('#ALERTS_GO_HERE').html(data);
				}
			});

			var xhr = $.ajax({
				url: 'exp_get_homedata.php',
				async: true,
				data: {
					pw: 'William'
				},
				dataType: "json",
				success: function(data) {
					if( data != false ) {
						console.log(data);
						if (typeof data.COMPANIES !== 'undefined' && $('#COMPANIES').length > 0) {
							$('#COMPANIES').html(data.COMPANIES).change();
						}
						if (typeof data.OFFICES !== 'undefined' && $('#OFFICES').length > 0) {
							$('#OFFICES').html(data.OFFICES).change();
						}
						if (typeof data.USERS !== 'undefined' && $('#USERS').length > 0) {
							$('#USERS').html(data.USERS).change();
						}
						if (typeof data.PALLETS !== 'undefined' && $('#PALLETS').length > 0) {
							$('#PALLETS').html(data.PALLETS).change();
						}

						if (typeof data.CLIENT_LEADS !== 'undefined' && $('#CLIENT_LEADS').length > 0) {
							$('#CLIENT_LEADS').html(data.CLIENT_LEADS).change();
						}
						if (typeof data.CLIENT_PROSPECTS !== 'undefined' && $('#CLIENT_PROSPECTS').length > 0) {
							$('#CLIENT_PROSPECTS').html(data.CLIENT_PROSPECTS).change();
						}
						if (typeof data.CLIENT_CLIENTS !== 'undefined' && $('#CLIENT_CLIENTS').length > 0) {
							$('#CLIENT_CLIENTS').html(data.CLIENT_CLIENTS).change();
						}
						if (typeof data.CLIENTS !== 'undefined' && $('#CLIENTS').length > 0) {
							$('#CLIENTS').html(data.CLIENTS).change();
						}

						if (typeof data.TRAILERS !== 'undefined' && $('#TRAILERS').length > 0) {
							$('#TRAILERS').html(data.TRAILERS).change();
						}
						if (typeof data.TRAILERS_EXPIRED !== 'undefined' && $('#TRAILERS_EXPIRED').length > 0) {
							$('#TRAILERS_EXPIRED').html(data.TRAILERS_EXPIRED).change();
						}

						if (typeof data.CARRIERS !== 'undefined' && $('#CARRIERS').length > 0) {
							$('#CARRIERS').html(data.CARRIERS).change();
						}
						if (typeof data.CARRIERS_EXPIRED !== 'undefined' && $('#CARRIERS_EXPIRED').length > 0) {
							$('#CARRIERS_EXPIRED').html(data.CARRIERS_EXPIRED).change();
						}

						if (typeof data.DRIVERS !== 'undefined' && $('#DRIVERS').length > 0) {
							$('#DRIVERS').html(data.DRIVERS).change();
						}
						if (typeof data.DRIVERS_EXPIRED !== 'undefined' && $('#DRIVERS_EXPIRED').length > 0) {
							$('#DRIVERS_EXPIRED').html(data.DRIVERS_EXPIRED).change();
						}

						if (typeof data.TRACTORS !== 'undefined' && $('#TRACTORS').length > 0) {
							$('#TRACTORS').html(data.TRACTORS).change();
						}
						if (typeof data.TRACTORS_EXPIRED !== 'undefined' && $('#TRACTORS_EXPIRED').length > 0) {
							$('#TRACTORS_EXPIRED').html(data.TRACTORS_EXPIRED).change();
						}

					}
				}
			});
			
			$('a').on('click', function() {
				xha.abort();
				xhr.abort();
				return true;
			});
			
			function check_messages() {
				if( code > 0 ) {
					$.ajax({
						url: 'exp_my_messages.php',
						data: {
							GET: code,
							PW: 'AlJazeera'
						},
						dataType: "json",
						success: function(data) {
							//console.log(data);
							if( data != false ) {
								//console.log('we have messages');
								$('#motd_modal').modal({
									container: 'body'
								});
								var messagetext = '';
								for( var x in data ) {
									//console.log(x, data[x].DATE, data[x].FROM, data[x].MESSAGE );
									messagetext += '<div class="row tighter"><div class="col-sm-4">Date: ' + data[x].DATE + ' ' + (data[x].STICKY == 1 ? '<span class="glyphicon glyphicon-pushpin lead tip" title="Sticky - cannot mark read"></span>' : '') +
										'<br>From: ' + data[x].FROM +
										'<br></div><div class="col-sm-8"><strong>' +
										data[x].MESSAGE + '</strong></div></div><hr>';
								}
								$('#motd_modal_body').html(messagetext);
								$('#motd_modal_footer').html('<div class="row tighter"><div class="col-sm-4"></div>' +
									'<div class="col-sm-8"><a class="btn btn-md btn-success" id="mark_read" href="exp_my_messages.php?PW=AlJazeera&READ=' + code + '"><span class="glyphicon glyphicon-remove"></span> Mark Read</a> ' +
									'<a class="btn btn-md btn-default" id="keep" href="index.php"><span class="glyphicon glyphicon-time"></span> Keep for later</a></div></div>');
							}
						}
					});
				}
			}
			
			check_messages();
			
			
		});
		
	//--></script>

<?php

require_once( "include/footer_inc.php" );
?>

