<?php

// $Id: navbar_inc.php 5030 2023-04-12 20:31:34Z duncan $
// Navigation controls

// no direct access
defined('_STS_INCLUDE') or die('Restricted access');

// Used for paths to css, js etc. Redifine if you are not in the normal directory
if( ! defined( 'EXP_RELATIVE_PATH') )
	define( 'EXP_RELATIVE_PATH', '' );

	// Don't enable debug for the header, it fills the screen
	$save_dbg = $exspeedite_db->set_debug( false );
	$sts_debug = false;

	require_once( "include/sts_setting_class.php" );
	require_once( "include/sts_report_class.php" );
	require_once( "include/sts_office_class.php" );

	$report_table = sts_report::getInstance($exspeedite_db, $sts_debug);
	$office_table = sts_office::getInstance($exspeedite_db, $sts_debug);
	
	$setting_table = sts_setting::getInstance( $exspeedite_db, false );
	$setting_table->set_debug( $sts_debug );
	$sts_export_qb = $setting_table->get( 'api', 'EXPORT_QUICKBOOKS' ) == 'true';
	$sts_export_sage50 = $setting_table->get( 'api', 'EXPORT_SAGE50_CSV' ) == 'true';
	$sts_qb_online = $setting_table->get( 'api', 'QUICKBOOKS_ONLINE' ) == 'true';
	$sts_pcm_enabled = $setting_table->get( 'api', 'PCM_API_KEY' ) <> '';
	//! SCR# 594 - include caching Google distance results
	$sts_google_enabled = $setting_table->get( 'api', 'GOOGLE_API_KEY' ) <> '';
	$edi_enabled = ($setting_table->get("api", "EDI_ENABLED") == 'true');
	$ifta_enabled = ($setting_table->get("api", "IFTA_ENABLED") == 'true');
	$cms_enabled = ($setting_table->get("option", "CMS_ENABLED") == 'true');
	$cms_salespeople_leads = ($setting_table->get("option", "CMS_SALESPEOPLE_LEADS") == 'true');
	$multi_company = ($setting_table->get("option", "MULTI_COMPANY") == 'true');
	$multi_currency = ($setting_table->get("option", "MULTI_CURRENCY") == 'true');
	$scr_enabled = ($setting_table->get("option", "SCR_ENABLED") == 'true');
	$sts_fleet_enabled = $setting_table->get( 'option', 'FLEET_ENABLED' ) == 'true';
	$sts_inspection_reports = $setting_table->get( 'option', 'INSPECTION_REPORTS' ) == 'true';
	$insp_title = $setting_table->get( 'option', 'INSPECTION_REPORT_TITLE' );
	$kpi_profit_enabled = $setting_table->get("option", "KPI_PROFIT") == 'true';
	$attach_alt_emails = $setting_table->get("option", "ATTACHMENTS_ALT_EMAILS") == 'true';
	$lanes_report = $setting_table->get("option", "LANES_REPORT") == 'true';
	$messages_managers = $setting_table->get("option", "MESSAGES_MANGERS") == 'true';
	$attachment_stats = $setting_table->get("option", "ATTACHMENT_STATS");
	$sts_email_queueing = $setting_table->get( 'option', 'EMAIL_QUEUEING' ) == 'true';
	$sts_test_server = strpos($setting_table->get( 'main', 'URL_PREFIX' ), 'test') !== false;
	$sts_containers = $setting_table->get( 'option', 'CONTAINERS' ) == 'true';
	
	$kt_api_key = $setting_table->get( 'api', 'KEEPTRUCKIN_KEY' );
	$kt_enabled = ! empty($kt_api_key);

	//! If true display only my clients
	$my_sales_clients =  $my_session->cms_restricted_salesperson();

	if( ! isset($_SESSION['EXT_USER_OFFICES']) || isset($_GET["FORCE"]))
		$office_table->user_offices();

?>
      <!-- Fixed navbar -->
      <div class="navbar navbar-default <?php if( $sts_test_server) echo 'navbar-test '; ?>navbar-fixed-top" role="navigation">
        <div class="container-full">
          <div class="navbar-header">
            <button type="button" class="navbar-toggle" data-toggle="collapse" data-target=".navbar-collapse">
              <span class="sr-only">Toggle navigation</span>
              <span class="icon-bar"></span>
              <span class="icon-bar"></span>
              <span class="icon-bar"></span>
            </button>
            <a id="logo-home" class="navbar-brand" style="padding: 0px;" href="<?php echo EXP_RELATIVE_PATH.($my_session->in_group(EXT_GROUP_DRIVER) ? 'exp_listload_driver.php' : 'index.php'); ?>"><img src="<?php echo EXP_RELATIVE_PATH; ?>images/EXSPEEDITEsmr.png" alt="<?php echo $sts_title ?>" <?php if( $my_session->in_group(EXT_GROUP_ADMIN) ) echo 'title="USER='.$_SESSION['EXT_USERNAME']." (".$_SESSION['EXT_USER_CODE'].")\n".'Exspeedite '.$sts_release_year.' REL='.$sts_release_name." (".'SCHEMA='.$sts_schema_release_name.")\n".'DB='.$sts_database."\n".'TZ='.$sts_crm_timezone.
	            
	            '"' ?>></a>&nbsp;
          </div>
          <div class="collapse navbar-collapse">
            <ul class="nav navbar-nav">
<?php if( ! $my_session->in_group(EXT_GROUP_DRIVER) ) {
	if( $multi_company && isset($_SESSION['EXT_USER_OFFICES']) && is_array($_SESSION['EXT_USER_OFFICES']) && count($_SESSION['EXT_USER_OFFICES']) > 1 ) { ?>
               <li class="dropdown">
                <a id="home" href="#" data-toggle="dropdown"><span class="glyphicon glyphicon-home"></span> <b class="caret"></b></a>
                <ul class="dropdown-menu">
 <?php               	
                foreach( $_SESSION['EXT_USER_OFFICES'] as $o => $name) {
	                echo '<li'.($_SESSION['EXT_USER_OFFICE'] == $o ? ' class="active"' : '').'><a id="office_'.$o.'" href="'.EXP_RELATIVE_PATH.'index.php?office='.$o.'">'.$name.
	                (! empty($_SESSION['EXT_USER_COMPANIES'][$o]) ? ' ('.$_SESSION['EXT_USER_COMPANIES'][$o].')' : '' ).'</a></li>';
                }
                
?>
               </ul>
              </li>

 <?php } else { ?>
               <li class="active"><a id="home" href="<?php echo EXP_RELATIVE_PATH; ?>index.php"><span class="glyphicon glyphicon-home"></span></a></li>
<?php }
	} ?>
 
<?php if( $my_session->in_group(EXT_GROUP_ADMIN) ) { ?>
              <li class="dropdown">
                <a id="adminmenu" href="#" data-toggle="dropdown">Admin <b class="caret"></b></a>
                <ul class="dropdown-menu">
 
                  <li class="dropdown-submenu">
                <a id="setupmenu"  href="#" class="dropdown-toggle" data-toggle="dropdown"><span class="menu-lable">Setup</span><b class="caret"></b></a>
 
                <ul class="dropdown-menu">
                  <li><a href="<?php echo EXP_RELATIVE_PATH; ?>exp_liststatus_codes.php"<?php echo $my_session->access_enabled( EXT_GROUP_ADMIN ); ?>><span class="menu-lable">Status Codes</span><img src="<?php echo EXP_RELATIVE_PATH; ?>images/status_codes_icon.png" alt="status_codes_icon" height="16" /></a></li>
                  <li><a href="<?php echo EXP_RELATIVE_PATH; ?>exp_listbc.php"<?php echo $my_session->access_enabled( EXT_GROUP_ADMIN ); ?>><span class="menu-lable">Business Codes</span><img src="<?php echo EXP_RELATIVE_PATH; ?>images/setting_icon.png" alt="setting_icon" height="16" /></a></li>

                  <li><a href="<?php echo EXP_RELATIVE_PATH; ?>exp_listitem_list.php"<?php echo $my_session->access_enabled( EXT_GROUP_ADMIN ); ?>><span class="menu-lable">Item Lists</span><img src="<?php echo EXP_RELATIVE_PATH; ?>images/status_codes_icon.png" alt="status_codes_icon" height="16" /></a></li>

<?php if( $attach_alt_emails ) { ?>
                   <li><a href="<?php echo EXP_RELATIVE_PATH; ?>exp_listemail_attach.php"<?php echo $my_session->access_enabled( EXT_GROUP_ADMIN ); ?>><span class="menu-lable">Email Attachments</span><span class="glyphicon glyphicon-envelope"></span></a></li>
<?php } ?>

<?php if( ($attachment_stats == 'admin' && in_group(EXT_GROUP_ADMIN)) ||
			($attachment_stats == 'superadmin' && $my_session->superadmin()) )	{ ?>
                  <li><a href="<?php echo EXP_RELATIVE_PATH; ?>exp_attachment_stats.php"<?php echo $my_session->access_enabled( EXT_GROUP_ADMIN ); ?>><span class="menu-lable">Attachment Stats</span><img src="<?php echo EXP_RELATIVE_PATH; ?>images/status_codes_icon.png" alt="status_codes_icon" height="16" /></a></li>
<?php } ?>

                <li><a href="<?php echo EXP_RELATIVE_PATH; ?>exp_listunit.php"<?php echo $my_session->access_enabled( EXT_GROUP_USER ); ?>><span class="menu-lable">Units</span><img src="<?php echo EXP_RELATIVE_PATH; ?>images/unit_icon.png" alt="unit_icon" height="16" /></a></li>
                  <li><a href="<?php echo EXP_RELATIVE_PATH; ?>exp_listsetting.php"<?php echo $my_session->access_enabled( EXT_GROUP_ADMIN ); ?>><span class="menu-lable">Settings</span><img src="<?php echo EXP_RELATIVE_PATH; ?>images/setting_icon.png" alt="setting_icon" height="16" /></a></li>
                   <li><a href="<?php echo EXP_RELATIVE_PATH; ?>exp_listreport.php"<?php echo $my_session->access_enabled( EXT_GROUP_ADMIN ); ?>><span class="menu-lable">Reports</span><img src="<?php echo EXP_RELATIVE_PATH; ?>images/setting_icon.png" alt="setting_icon" height="16" /></a></li>
                   <li><a href="<?php echo EXP_RELATIVE_PATH; ?>exp_listvideo.php"<?php echo $my_session->access_enabled( EXT_GROUP_ADMIN ); ?>><span class="menu-lable">Videos</span><img src="<?php echo EXP_RELATIVE_PATH; ?>images/setting_icon.png" alt="setting_icon" height="16" /></a></li>
                   <li><a href="<?php echo EXP_RELATIVE_PATH; ?>exp_listarticle.php"<?php echo $my_session->access_enabled( EXT_GROUP_ADMIN ); ?>><span class="menu-lable">Articles</span><span class="glyphicon glyphicon-link"></span></a></li>
                   
 <?php if( ! $messages_managers ) { ?>
                   <li><a href="<?php echo EXP_RELATIVE_PATH; ?>exp_listmessage.php"<?php echo $my_session->access_enabled( EXT_GROUP_ADMIN ); ?>><span class="menu-lable">Messages</span><span class="glyphicon glyphicon-envelope"></span></a></li>
<?php } ?>

<?php if( $sts_pcm_enabled || $sts_google_enabled ) { ?>
                 <li><a href="<?php echo EXP_RELATIVE_PATH; ?>exp_pcm_cache.php"<?php echo $my_session->access_enabled( EXT_GROUP_ADMIN ); ?>><span class="menu-lable">Miles Cache Info</span> <span class="glyphicon glyphicon-wrench"></span></a></li>
<?php } ?>

<?php if( $sts_email_queueing ) { ?>
                 <li><a href="<?php echo EXP_RELATIVE_PATH; ?>exp_listemail_queue.php"<?php echo $my_session->access_enabled( EXT_GROUP_ADMIN ); ?>><span class="menu-lable">Email Queue</span> <span class="glyphicon glyphicon-envelope"></span></a></li>
<?php } ?>

    <li><a href="<?php echo EXP_RELATIVE_PATH; ?>exp_view_archive.php"<?php echo $my_session->access_enabled( EXT_GROUP_ADMIN ); ?>><span class="menu-lable text-danger">Archives</span> <span class="text-danger"><span class="glyphicon glyphicon-export"></span></span></a></li>


<?php if( $my_session->superadmin() ) { ?>
                  <li class="adminbg"><a href="<?php echo EXP_RELATIVE_PATH; ?>exp_db_tables.php"<?php echo $my_session->access_enabled( EXT_GROUP_ADMIN ); ?>><span class="menu-lable text-danger">DB Tables</span> <span class="text-danger"><span class="glyphicon glyphicon-wrench"></span></span></a></li>
                  
                  <li class="adminbg"><a href="<?php echo EXP_RELATIVE_PATH; ?>exp_repair_db.php"<?php echo $my_session->access_enabled( EXT_GROUP_ADMIN ); ?>><span class="menu-lable text-danger">Repair DB</span> <span class="text-danger"><span class="glyphicon glyphicon-wrench"></span></span></a></li>

                  <li class="adminbg"><a href="<?php echo EXP_RELATIVE_PATH; ?>exp_listdb_log.php"<?php echo $my_session->access_enabled( EXT_GROUP_ADMIN ); ?>><span class="menu-lable text-danger">DB Log</span> <span class="text-danger"><span class="glyphicon glyphicon-th-list"></span></span></a></li>

                   <li class="adminbg"><a href="<?php echo EXP_RELATIVE_PATH; ?>exp_listcache.php"<?php echo $my_session->access_enabled( EXT_GROUP_ADMIN ); ?>><span class="menu-lable text-danger">Database Cache</span> <span class="text-danger"><span class="glyphicon glyphicon-th-list"></span></span></a></li>

               <li class="adminbg"><a href="<?php echo EXP_RELATIVE_PATH; ?>exp_import_profile.php"<?php echo $my_session->access_enabled( EXT_GROUP_ADMIN ); ?>><span class="menu-lable text-danger">Import Profiles</span> <span class="text-danger"><span class="glyphicon glyphicon-import"></span></span></a></li>

                 <li class="adminbg"><a href="<?php echo EXP_RELATIVE_PATH; ?>exp_addr_validate_all.php?PW=VerySlow"<?php echo $my_session->access_enabled( EXT_GROUP_ADMIN ); ?>><span class="menu-lable text-danger">Validate Contacts</span> <span class="text-danger"><span class="glyphicon glyphicon-search"></span></span></a></li>


<?php } ?>

               </ul>
	         </li>
 
<?php if( $scr_enabled ) { ?>
                 <li class="adminbg"><a href="<?php echo EXP_RELATIVE_PATH; ?>exp_listscr.php"<?php echo $my_session->access_enabled( EXT_GROUP_ADMIN ); ?>><span class="menu-lable">SCRs</span> <img src="<?php echo EXP_RELATIVE_PATH; ?>images/bug_icon.png" alt="bug_icon" height="16" /></a></li>
<?php } ?>

 <?php if( $multi_company ) { ?>
                  <li><a href="<?php echo EXP_RELATIVE_PATH; ?>exp_listcompany.php"<?php echo $my_session->access_enabled( EXT_GROUP_ADMIN ); ?>><span class="menu-lable">Companies</span> <img src="<?php echo EXP_RELATIVE_PATH; ?>images/company_icon.png" alt="company_icon" width="22" height="22" valign="middle"/></a></li>
                  <li><a href="<?php echo EXP_RELATIVE_PATH; ?>exp_listoffice.php"<?php echo $my_session->access_enabled( EXT_GROUP_ADMIN ); ?>><span class="menu-lable">Offices</span> <img src="<?php echo EXP_RELATIVE_PATH; ?>images/company_icon.png" alt="company_icon" width="22" height="22" valign="middle"/></a></li>
 <?php } ?>
                  <li><a href="<?php echo EXP_RELATIVE_PATH; ?>exp_listuser.php"<?php echo $my_session->access_enabled( EXT_GROUP_ADMIN ); ?>><span class="menu-lable">Users</span> <span class="glyphicon glyphicon-user"></span></a></li>

                  <li><a href="<?php echo EXP_RELATIVE_PATH; ?>exp_listul.php"<?php echo $my_session->access_enabled( EXT_GROUP_ADMIN ); ?>><span class="menu-lable">User Events</span> <span class="glyphicon glyphicon-user"></span></a></li>

<?php if( $multi_currency ) { ?>
                  <li><a href="<?php echo EXP_RELATIVE_PATH; ?>exp_listtax.php"<?php echo $my_session->access_enabled( EXT_GROUP_ADMIN ); ?>><span class="menu-lable">CDN Tax Rates</span><img src="<?php echo EXP_RELATIVE_PATH; ?>images/tax_icon.png" alt="tax_icon" height="16" /></a></li>
<?php } ?>

<?php if( $multi_currency ) { ?>
                  <li><a href="<?php echo EXP_RELATIVE_PATH; ?>exp_listem.php"<?php echo $my_session->access_enabled( EXT_GROUP_ADMIN ); ?>><span class="menu-lable">Exchange Rates</span><img src="<?php echo EXP_RELATIVE_PATH; ?>images/exch_icon.png" alt="tax_icon" height="24" /></a></li>
<?php } ?>

 <?php if( $sts_export_qb && $sts_qb_online ) { ?>
                  <li><a href="<?php echo EXP_RELATIVE_PATH; ?>quickbooks-php-master/online/setup.php"<?php echo $my_session->access_enabled( EXT_GROUP_ADMIN ); ?>><span class="menu-lable">Setup QBOE</span> <img src="<?php echo EXP_RELATIVE_PATH; ?>quickbooks-php-master/online/images/qb_icon.png" alt="qb_icon" width="22" height="22" valign="middle"/></a></li>
 <?php } ?>

 <?php if( $kt_enabled ) { ?>
                  <li><a href="<?php echo EXP_RELATIVE_PATH; ?>exp_kt_oauth2.php"<?php echo $my_session->access_enabled( EXT_GROUP_ADMIN ); ?>><span class="menu-lable">Authentication</span> <img src="<?php echo EXP_RELATIVE_PATH; ?>images/keeptruckin2.png" alt="keeptruckin" height="22" valign="middle"/></a></li>
                  <li><a href="<?php echo EXP_RELATIVE_PATH; ?>exp_kt_position_admin.php"<?php echo $my_session->access_enabled( EXT_GROUP_ADMIN ); ?>><span class="menu-lable">Position Updates</span> <img src="<?php echo EXP_RELATIVE_PATH; ?>images/keeptruckin2.png" alt="keeptruckin" height="22" valign="middle"/></a></li>
 <?php } ?>

<?php if( $edi_enabled ) { ?>
                  <li><a href="<?php echo EXP_RELATIVE_PATH; ?>exp_listftp.php"<?php echo $my_session->access_enabled( EXT_GROUP_ADMIN ); ?>><span class="menu-lable">EDI FTP Configuration</span> <img src="<?php echo EXP_RELATIVE_PATH; ?>images/edi_icon1.png" alt="edi_icon" height="22" valign="middle"/></a></li>
 <?php } ?>

<?php if( $cms_enabled ) { ?>
                <li class="dropdown-submenu">
                <a id="salesmenu"  href="#" class="dropdown-toggle" data-toggle="dropdown"><span class="menu-lable">Sales Management</span><b class="caret"></b></a>
 
                <ul class="dropdown-menu">
                  <li><a href="<?php echo EXP_RELATIVE_PATH; ?>exp_sales_activity2.php"<?php echo $my_session->access_enabled( EXT_GROUP_ADMIN ); ?>><span class="menu-lable">Summary</span> <span class="glyphicon glyphicon-user"></span></a></li>

                  <li><a href="<?php echo EXP_RELATIVE_PATH; ?>exp_sales_activity.php"<?php echo $my_session->access_enabled( EXT_GROUP_ADMIN ); ?>><span class="menu-lable">Sales Activity</span> <span class="glyphicon glyphicon-user"></span></a></li>

                </ul>
	         </li>
 <?php } ?>
<?php if( $sts_inspection_reports ) { ?>
                <li class="dropdown-submenu">
                <a id="rm_menu"  href="#" class="dropdown-toggle" data-toggle="dropdown"><span class="menu-lable">R&M</span><b class="caret"></b></a>
 
                <ul class="dropdown-menu">
                   <li><a href="<?php echo EXP_RELATIVE_PATH; ?>exp_listrm_class.php"<?php echo $my_session->access_enabled( EXT_GROUP_ADMIN ); ?>><span class="menu-lable">R&M Classes</span><img src="<?php echo EXP_RELATIVE_PATH; ?>images/status_codes_icon.png" alt="status_codes_icon" height="16" /></a></li>

                   <li><a href="<?php echo EXP_RELATIVE_PATH; ?>exp_listrm_form.php"<?php echo $my_session->access_enabled( EXT_GROUP_ADMIN ); ?>><span class="menu-lable">R&M Forms</span><img src="<?php echo EXP_RELATIVE_PATH; ?>images/status_codes_icon.png" alt="status_codes_icon" height="16" /></a></li>

                </ul>
	         </li>
 <?php } ?>

                 <li class="dropdown-submenu">
                <a id="fuelmenu"  href="#" class="dropdown-toggle" data-toggle="dropdown"><span class="menu-lable">Fuel Management</span><b class="caret"></b></a>
 
                <ul class="dropdown-menu">
  <?php if( $ifta_enabled ) { ?>
                 <li><a href="<?php echo EXP_RELATIVE_PATH; ?>exp_list_card_ftp.php"<?php echo $my_session->access_enabled( EXT_GROUP_ADMIN ); ?>><span class="menu-lable">Fuel Card Import</span> <img src="<?php echo EXP_RELATIVE_PATH; ?>images/card_ftp_icon.png" alt="card_ftp_icon" height="22" valign="middle"/></a></li>

                  <li><a href="<?php echo EXP_RELATIVE_PATH; ?>exp_list_fuel_card_mapping.php"<?php echo $my_session->access_enabled( EXT_GROUP_ADMIN ); ?>><span class="menu-lable">Fuel Card Mapping</span> <img src="<?php echo EXP_RELATIVE_PATH; ?>images/card_ftp_icon.png" alt="card_ftp_icon" height="22" valign="middle"/></a></li>

                <li><a href="<?php echo EXP_RELATIVE_PATH; ?>exp_listca.php"<?php echo $my_session->access_enabled( EXT_GROUP_ADMIN ); ?>><span class="menu-lable">Fuel Card Advances</span> <img src="<?php echo EXP_RELATIVE_PATH; ?>images/card_ftp_icon.png" alt="card_ftp_icon" height="22" valign="middle"/></a></li>

                 <li><a href="<?php echo EXP_RELATIVE_PATH; ?>exp_import_ifta.php"<?php echo $my_session->access_enabled( EXT_GROUP_ADMIN ); ?>><span class="menu-lable">IFTA Rates</span> <img src="<?php echo EXP_RELATIVE_PATH; ?>images/iftatest.gif" alt="ifta_icon" height="22" valign="middle"/></a></li>

                 <li><a href="<?php echo EXP_RELATIVE_PATH; ?>exp_listifta_log.php"<?php echo $my_session->access_enabled( EXT_GROUP_ADMIN ); ?>><span class="menu-lable">IFTA Report</span> <img src="<?php echo EXP_RELATIVE_PATH; ?>images/iftatest.gif" alt="ifta_icon" height="22" valign="middle"/></a></li>
 <?php } ?>

                  <li><a href="<?php echo EXP_RELATIVE_PATH; ?>exp_view_fsc.php?TYPE=all"<?php echo $my_session->access_enabled( EXT_GROUP_USER ); ?>><span class="menu-lable">FSC Schedule</span><img src="<?php echo EXP_RELATIVE_PATH; ?>images/searchdate.png" alt="searchdate" height="16" /></a></li>                 
                </ul>
	         </li>

                  <li><a href="<?php echo EXP_RELATIVE_PATH; ?>exp_listvacation.php"<?php echo $my_session->access_enabled( $sts_table_access[VACATION_TABLE] ); ?>><span class="menu-lable">Vacations</span> <img src="<?php echo EXP_RELATIVE_PATH; ?>images/vacation_icon.png" alt="order_icon" height="16" /></a></li>
                 
                  <li><a href="<?php echo EXP_RELATIVE_PATH; ?>exp_listpallet.php"<?php echo $my_session->access_enabled( EXT_GROUP_USER ); ?>><span class="menu-lable">Pallet Adjustments</span><img src="<?php echo EXP_RELATIVE_PATH; ?>images/pallet_icon.png" alt="pallet_icon" width="39" height="16" /></a></li>

                <li class="dropdown-submenu">
                <a id="ratesmenu" href="#" class="dropdown-toggle" data-toggle="dropdown"><span class="menu-lable">Driver Rates</span><b class="caret"></b></a>
 
                <ul class="dropdown-menu">
                  <li><a href="<?php echo EXP_RELATIVE_PATH; ?>exp_listdriverrates.php"<?php echo $my_session->access_enabled( EXT_GROUP_USER ); ?>><span class="menu-lable">Driver Rates List</span><img src="<?php echo EXP_RELATIVE_PATH; ?>images/money_bag.png" alt="money_bag" height="16" /></a></li>
                  <li><a href="<?php echo EXP_RELATIVE_PATH; ?>exp_rate_profile.php"<?php echo $my_session->access_enabled( EXT_GROUP_USER ); ?>><span class="menu-lable">Driver Rates Profile</span><img src="<?php echo EXP_RELATIVE_PATH; ?>images/rate-profile.png" alt="rate-profile" height="16" /></a></li>
                  <li><a href="<?php echo EXP_RELATIVE_PATH; ?>exp_driverrate.php"<?php echo $my_session->access_enabled( EXT_GROUP_USER ); ?>><span class="menu-lable">Add Driver Rate</span><img src="<?php echo EXP_RELATIVE_PATH; ?>images/118-512.png" alt="118-512" height="16" /></a></li>
                </ul>
                </li>
                  
                  <li><a href="<?php echo EXP_RELATIVE_PATH; ?>exp_listclientrates.php"<?php echo $my_session->access_enabled( EXT_GROUP_USER ); ?>><span class="menu-lable">Client Rates</span><img src="<?php echo EXP_RELATIVE_PATH; ?>images/money_bag.png" alt="money_bag" height="16" /></a></li>


                </ul>
              </li>
<?php } ?>
              
<?php if( $sts_inspection_reports && $my_session->in_group( EXT_GROUP_MECHANIC ) ) { ?>
             <li class="dropdown">
                <a href="#" id="profilesmenu" class="dropdown-toggle" data-toggle="dropdown">Mechanic <b class="caret"></b></a>
                <ul class="dropdown-menu">
                  <li><a href="<?php echo EXP_RELATIVE_PATH; ?>exp_listtractor.php"<?php echo $my_session->access_enabled( EXT_GROUP_MECHANIC ); ?>><span class="menu-lable">Tractors</span><img src="<?php echo EXP_RELATIVE_PATH; ?>images/tractor_icon.png" alt="tractor_icon" width="20" height="16" /></a></li>
                  <li><a href="<?php echo EXP_RELATIVE_PATH; ?>exp_listtrailer.php"<?php echo $my_session->access_enabled( EXT_GROUP_MECHANIC ); ?>><span class="menu-lable">Trailers</span><img src="<?php echo EXP_RELATIVE_PATH; ?>images/trailer_icon.png" alt="trailer_icon" width="35" height="16" /></a></li>
                   <li><a href="<?php echo EXP_RELATIVE_PATH; ?>exp_listinsp_report.php"<?php echo $my_session->access_enabled( EXT_GROUP_MECHANIC, EXT_GROUP_FLEET ); ?>><span class="menu-lable"><?php echo $insp_title; ?>s</span><span class="glyphicon glyphicon-wrench"></span></a></li>

                </ul>
              </li>
<?php } ?>

<?php if( $my_session->in_group( EXT_GROUP_PROFILES, EXT_GROUP_FLEET ) ) { ?>
             <li class="dropdown">
                <a href="#" id="profilesmenu" class="dropdown-toggle" data-toggle="dropdown">Profiles <b class="caret"></b></a>
                <ul class="dropdown-menu">
<?php if( $my_session->in_group( EXT_GROUP_FLEET ) ) { ?>
                  <li><a href="<?php echo EXP_RELATIVE_PATH; ?>exp_listdriver.php"<?php echo $my_session->access_enabled( EXT_GROUP_FLEET ); ?>><span class="menu-lable">Drivers</span><img src="<?php echo EXP_RELATIVE_PATH; ?>images/driver_icon.png" alt="driver_icon" width="16" height="16" /></a></li>
	<?php if( $sts_fleet_enabled ) { ?>
	            <li><a href="<?php echo EXP_RELATIVE_PATH; ?>exp_listfleet.php"<?php echo $my_session->access_enabled( EXT_GROUP_FLEET ); ?>><span class="menu-lable">Tractor Fleets</span><img src="<?php echo EXP_RELATIVE_PATH; ?>images/tractor_icon.png" alt="tractor_icon" width="20" height="16" /></a></li>
	<?php } ?>
                  <li><a href="<?php echo EXP_RELATIVE_PATH; ?>exp_listtractor.php"<?php echo $my_session->access_enabled( EXT_GROUP_FLEET ); ?>><span class="menu-lable">Tractors</span><img src="<?php echo EXP_RELATIVE_PATH; ?>images/tractor_icon.png" alt="tractor_icon" width="20" height="16" /></a></li>
<?php } ?>
                  <li><a href="<?php echo EXP_RELATIVE_PATH; ?>exp_listtrailer.php"<?php echo $my_session->access_enabled( EXT_GROUP_PROFILES ); ?>><span class="menu-lable">Trailers</span><img src="<?php echo EXP_RELATIVE_PATH; ?>images/trailer_icon.png" alt="trailer_icon" width="35" height="16" /></a></li>
                  <li><a href="<?php echo EXP_RELATIVE_PATH; ?>exp_listcarrier.php"<?php echo $my_session->access_enabled( EXT_GROUP_PROFILES ); ?>><span class="menu-lable">Carriers</span><img src="<?php echo EXP_RELATIVE_PATH; ?>images/carrier_icon.png" alt="carrier_icon" width="39" height="16" /></a></li>
<?php if( $cms_enabled ) { ?>
                  <li><a href="<?php echo EXP_RELATIVE_PATH; ?>exp_listclient.php?CLIENT_TYPE=lead"<?php echo $my_session->access_enabled( EXT_GROUP_PROFILES ); ?>><span class="menu-lable">Leads</span><span class="glyphicon glyphicon-user"></span></a></li>
                  <li><a href="<?php echo EXP_RELATIVE_PATH; ?>exp_list_client_shipcons.php"<?php echo $my_session->access_enabled( EXT_GROUP_SALES ); ?>><span class="menu-lable">Ship/Cons Leads</span><span class="glyphicon glyphicon-user"></span></a></li>
                  <li><a href="<?php echo EXP_RELATIVE_PATH; ?>exp_listclient.php?CLIENT_TYPE=prospect"<?php echo $my_session->access_enabled( EXT_GROUP_PROFILES ); ?>><span class="menu-lable">Prospects</span><span class="glyphicon glyphicon-user"></span></a></li>
                  <li><a href="<?php echo EXP_RELATIVE_PATH; ?>exp_listclient.php?CLIENT_TYPE=client"<?php echo $my_session->access_enabled( EXT_GROUP_PROFILES ); ?>><span class="menu-lable">Clients</span><span class="glyphicon glyphicon-user"></span></a></li>
<?php } else { ?>
                  <li><a href="<?php echo EXP_RELATIVE_PATH; ?>exp_listclient.php?CLIENT_TYPE=client"<?php echo $my_session->access_enabled( EXT_GROUP_PROFILES ); ?>><span class="menu-lable">Clients</span><span class="glyphicon glyphicon-user"></span></a></li>
<?php } ?>

<?php if( $sts_containers) {  ?>
                  <li><a href="<?php echo EXP_RELATIVE_PATH; ?>exp_im_containers.php"<?php echo $my_session->access_enabled( EXT_GROUP_PROFILES ); ?>><span class="menu-lable">Containers</span><img src="<?php echo EXP_RELATIVE_PATH; ?>images/container_icon.png" alt="container_icon" height="16" /></a></li>
<?php } ?>
                  <li><a href="<?php echo EXP_RELATIVE_PATH; ?>exp_listcommodity.php"<?php echo $my_session->access_enabled( EXT_GROUP_PROFILES ); ?>><span class="menu-lable">Commodities</span><img src="<?php echo EXP_RELATIVE_PATH; ?>images/commodity_icon.png" alt="commodity_icon" height="16" /></a></li>
                  <li><a href="<?php echo EXP_RELATIVE_PATH; ?>exp_listcommodity_class.php"<?php echo $my_session->access_enabled( EXT_GROUP_PROFILES ); ?>><span class="menu-lable">Commodity classes</span><img src="<?php echo EXP_RELATIVE_PATH; ?>images/commodity_class_icon.png" alt="commodity_class_icon" height="16" /></a></li>
                  <li><a href="<?php echo EXP_RELATIVE_PATH; ?>exp_listmanual_miles.php"<?php echo $my_session->access_enabled( $sts_table_access[MANUAL_MILES_TABLE] ); ?>><span class="menu-lable">Manual Miles</span><img src="<?php echo EXP_RELATIVE_PATH; ?>images/manual_miles_icon.png" alt="manual_miles_icon" height="16" /></a></li>
                  <li><a href="<?php echo EXP_RELATIVE_PATH; ?>exp_listzone_filter.php"<?php echo $my_session->access_enabled( EXT_GROUP_USER ); ?>><span class="menu-lable">Zone Filters</span><img src="<?php echo EXP_RELATIVE_PATH; ?>images/zone_icon.png" alt="zone_icon" height="16" /></a></li>
                  <li><a href="<?php echo EXP_RELATIVE_PATH; ?>exp_listosd.php"<?php echo $my_session->access_enabled( $sts_table_access[OSD_TABLE] ); ?>><span class="menu-lable">OS&D</span> <img src="<?php echo EXP_RELATIVE_PATH; ?>images/osd_icon.png" alt="order_icon" height="16" /></a></li>
                  
	<?php if( $sts_inspection_reports && $my_session->in_group( EXT_GROUP_MECHANIC, EXT_GROUP_FLEET ) ) { ?>
                   <li><a href="<?php echo EXP_RELATIVE_PATH; ?>exp_listinsp_report.php"<?php echo $my_session->access_enabled( EXT_GROUP_MECHANIC, EXT_GROUP_FLEET ); ?>><span class="menu-lable"><?php echo $insp_title; ?>s</span><span class="glyphicon glyphicon-wrench"></span></a></li>
	<?php } ?>
 <?php if( $cms_enabled ) { ?>
                  <li><a href="<?php echo EXP_RELATIVE_PATH; ?>exp_listformmail.php?CLIENT_TYPE=lead"<?php echo $my_session->access_enabled( EXT_GROUP_PROFILES ); ?>><span class="menu-lable">Form Mail Templates</span><span class="glyphicon glyphicon-envelope"></span></a></li>
	<?php } ?>
               
                </ul>
              </li>
<?php } ?>

<?php 
	//! SCR# 424 - Sales menu
	if( $my_session->in_group( EXT_GROUP_SALES ) && ! $my_session->in_group( EXT_GROUP_PROFILES ) ) { ?>
              <li class="dropdown">
                <a href="#" id="salesmenu" class="dropdown-toggle" data-toggle="dropdown">Sales <b class="caret"></b></a>
                <ul class="dropdown-menu">
<?php if( $cms_enabled ) { ?>
	<?php if( $cms_salespeople_leads ) { ?>
                  <li><a href="<?php echo EXP_RELATIVE_PATH; ?>exp_listclient.php?CLIENT_TYPE=lead"<?php echo $my_session->access_enabled( EXT_GROUP_SALES ); ?>><span class="menu-lable">Leads</span><span class="glyphicon glyphicon-user"></span></a></li>
                  <li><a href="<?php echo EXP_RELATIVE_PATH; ?>exp_list_client_shipcons.php"<?php echo $my_session->access_enabled( EXT_GROUP_SALES ); ?>><span class="menu-lable">Ship/Cons Leads</span><span class="glyphicon glyphicon-user"></span></a></li>
	<?php } ?>
                  <li><a href="<?php echo EXP_RELATIVE_PATH; ?>exp_listclient.php?CLIENT_TYPE=prospect"<?php echo $my_session->access_enabled( EXT_GROUP_SALES ); ?>><span class="menu-lable"><?php if( $my_sales_clients ) echo 'My '; ?> Prospects</span><span class="glyphicon glyphicon-user"></span></a></li>
                  <li><a href="<?php echo EXP_RELATIVE_PATH; ?>exp_listclient.php?CLIENT_TYPE=client"<?php echo $my_session->access_enabled( EXT_GROUP_SALES ); ?>><span class="menu-lable"><?php if( $my_sales_clients ) echo 'My '; ?> Clients</span><span class="glyphicon glyphicon-user"></span></a></li>
<?php } else { ?>
                  <li><a href="<?php echo EXP_RELATIVE_PATH; ?>exp_listclient.php?CLIENT_TYPE=client"<?php echo $my_session->access_enabled( EXT_GROUP_SALES ); ?>><span class="menu-lable">Clients</span><span class="glyphicon glyphicon-user"></span></a></li>
<?php } ?>

                </ul>
              </li>
<?php } ?>



<?php if( $my_session->in_group( EXT_GROUP_SHIPMENTS, EXT_GROUP_DISPATCH ) ) { ?>
              <li class="dropdown">
                <a href="#" id="operationsmenu" class="dropdown-toggle" data-toggle="dropdown">Operations <b class="caret"></b></a>
                <ul class="dropdown-menu">
<?php if( $my_session->in_group( EXT_GROUP_SHIPMENTS ) ) { ?>
                 <li><a href="<?php echo EXP_RELATIVE_PATH; ?>exp_listshipment.php"<?php echo $my_session->access_enabled( $sts_table_access[SHIPMENT_TABLE] ); ?>><span class="menu-lable">Shipments</span> <img src="<?php echo EXP_RELATIVE_PATH; ?>images/order_icon.png" alt="order_icon" height="16" /></a></li>
                  <li><a href="<?php echo EXP_RELATIVE_PATH; ?>exp_addshipment.php"<?php echo $my_session->access_enabled( $sts_table_access[SHIPMENT_TABLE] ); ?>><span class="menu-lable">Add Shipment</span> <img src="<?php echo EXP_RELATIVE_PATH; ?>images/order_icon.png" alt="order_icon" height="16" />&nbsp;<span class="glyphicon glyphicon-plus"></span></a></li>
<?php } ?>

<?php if( $my_session->in_group( EXT_GROUP_DISPATCH ) ) { ?>
                  <li><a href="<?php echo EXP_RELATIVE_PATH; ?>exp_listsummary.php"<?php echo $my_session->access_enabled( $sts_table_access[LOAD_TABLE] ); ?>><span class="menu-lable">Summary View</span><img src="<?php echo EXP_RELATIVE_PATH; ?>images/load_icon.png" alt="order_icon" height="16" /></a></li>
                  <li><a href="<?php echo EXP_RELATIVE_PATH; ?>exp_listload.php"<?php echo $my_session->access_enabled( $sts_table_access[LOAD_TABLE] ); ?>><span class="menu-lable">Trips/Loads</span><img src="<?php echo EXP_RELATIVE_PATH; ?>images/load_icon.png" alt="order_icon" height="16" /></a></li>
                  <!--
                  <li><a href="<?php echo EXP_RELATIVE_PATH; ?>exp_listload_old.php"<?php echo $my_session->access_enabled( $sts_table_access[LOAD_TABLE] ); ?>><span class="menu-lable text-muted">OLD Trips/Loads</span><img src="<?php echo EXP_RELATIVE_PATH; ?>images/load_icon2.png" alt="order_icon" height="16" /></a></li>
                  -->
                   <li><a href="<?php echo EXP_RELATIVE_PATH; ?>exp_addload.php"<?php echo $my_session->access_enabled( $sts_table_access[LOAD_TABLE] ); ?>><span class="menu-lable">Add Trip/Load</span><img src="<?php echo EXP_RELATIVE_PATH; ?>images/load_icon.png" alt="order_icon" height="16" />&nbsp;<span class="glyphicon glyphicon-plus"></span></a></li>
<?php } ?>
                   <li><a href="<?php echo EXP_RELATIVE_PATH; ?>exp_distance.php"<?php echo $my_session->access_enabled( EXT_GROUP_USER ); ?>><span class="menu-lable">Check Distance</span><img src="<?php echo EXP_RELATIVE_PATH; ?>images/order_icon.png" alt="order_icon" height="16" /></a></li>
                   <li><a href="<?php echo EXP_RELATIVE_PATH; ?>exp_distance2.php"<?php echo $my_session->access_enabled( EXT_GROUP_USER ); ?>><span class="menu-lable">Check Distance II</span><img src="<?php echo EXP_RELATIVE_PATH; ?>images/order_icon.png" alt="order_icon" height="16" /></a></li>

<?php if( $messages_managers ) { ?>
                   <li><a href="<?php echo EXP_RELATIVE_PATH; ?>exp_listmessage.php"<?php echo $my_session->access_enabled( EXT_GROUP_MANAGER ); ?>><span class="menu-lable">Messages</span><span class="glyphicon glyphicon-envelope"></span></a></li>
<?php } ?>

               </ul>
              </li>
<?php } ?>
                    <?php
	//! Reports code
if( ! $my_session->in_group(EXT_GROUP_DRIVER) ) {
	list($output, $modals) = $report_table->menu( $_SERVER['QUERY_STRING'] );
	echo '
'.$output;
}

//! SCR# 695 - Shortcuts buttons
echo $report_table->shortcuts();
	?>
              
              
              <li><a id="logout"  style="padding: 15px 5px 15px 5px;" href="<?php echo EXP_RELATIVE_PATH; ?>exp_logout.php"><span class="glyphicon glyphicon-lock"></span></a></li>
<?php if( false ) { ?>
              <li><span id="incfont"><span class="glyphicon glyphicon-plus"></span></span>/<span id="decfont"><span class="glyphicon glyphicon-minus"></span></span></li>
<?php } ?>
           </ul>
	        <?php if( $sts_test_server) echo '<div style="padding-top: 15px;"><strong>**TEST SERVER**</strong></div>'; ?>
          </div><!--/.nav-collapse -->
        </div>
      </div>
                   <?php
	if( ! $my_session->in_group(EXT_GROUP_DRIVER) && $modals <> '' )
		echo $modals;
		
	$exspeedite_db->set_debug( $save_dbg );
	$sts_debug = $save_dbg;
              ?>
