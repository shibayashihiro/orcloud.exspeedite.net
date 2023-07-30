<?php

// $Id: sts_setting_class.php 5150 2023-07-05 20:13:37Z dev $
// Settings class - all the customizable settings for Exspeedite.

// no direct access
defined('_STS_INCLUDE') or die('Restricted access');

//! Work around to avoid multiple includes.
if( ! defined('_STS_SETTING_CONFIG') ) {
	define('_STS_SETTING_CONFIG', 1);

require_once( "sts_table_class.php" );

class sts_setting extends sts_table {

	private $default_settings = array(
		array( 'CATEGORY' => 'main', 'SETTING' => 'Miles per hour', 
			'THE_VALUE' => '50', 'SETTING_COMMENT' => 'Average speed for calculating distance -> time' ),
		//! SCR# 683 - Building more in "Check Distance" screen
		array( 'CATEGORY' => 'main', 'SETTING' => 'Cost per mile', 
			'THE_VALUE' => '50', 'SETTING_COMMENT' => 'Average cost for calculating distance -> cost' ),
			
		array( 'CATEGORY' => 'main', 'SETTING' => 'SERVER_TZ', 
			'THE_VALUE' => 'Central', 'SETTING_COMMENT' => 'Server Time Zone (Atlantic / Eastern / Central / Mountain / Pacific / Alaska)', 'RESTRICTED' => true ),
			
		//! SCR# 695 - Shortcut buttons
		array( 'CATEGORY' => 'main', 'SETTING' => 'SHORTCUT1', 
			'THE_VALUE' => 'None', 'SETTING_COMMENT' => 'Shortcut button at top (None / Shipments / Loads / Summary / Drivers / Tractors / Trailers / Carriers / Clients)' ),
		array( 'CATEGORY' => 'main', 'SETTING' => 'SHORTCUT2', 
			'THE_VALUE' => 'None', 'SETTING_COMMENT' => 'Shortcut button at top (None / Shipments / Loads / Summary / Drivers / Tractors / Trailers / Carriers / Clients)' ),
		array( 'CATEGORY' => 'main', 'SETTING' => 'SHORTCUT3', 
			'THE_VALUE' => 'None', 'SETTING_COMMENT' => 'Shortcut button at top (None / Shipments / Loads / Summary / Drivers / Tractors / Trailers / Carriers / Clients)' ),

		array( 'CATEGORY' => 'main', 'SETTING' => 'Load Time', 
			'THE_VALUE' => '2', 'SETTING_COMMENT' => 'Average time for loading (hours)' ),
		array( 'CATEGORY' => 'option', 'SETTING' => 'DEFAULT_RETURN_STOP', 
			'THE_VALUE' => 'F&S', 'SETTING_COMMENT' => 'Default return stop destination (shipper name)' ),
		array( 'CATEGORY' => 'option', 'SETTING' => 'CLIENT_LABEL', 
			'THE_VALUE' => 'true', 'SETTING_COMMENT' => 'Replace client name with label (true / false)' ),
		array( 'CATEGORY' => 'option', 'SETTING' => 'CLIENT_MATCHES', 
			'THE_VALUE' => '20', 'SETTING_COMMENT' => 'Number of matches to a client name (default 20)' ),
		array( 'CATEGORY' => 'option', 'SETTING' => 'PIPCO_FIELDS', 
			'THE_VALUE' => '**REMOVE**', 'SETTING_COMMENT' => 'obsolete',
			'RESTRICTED' => true ),
		array( 'CATEGORY' => 'option', 'SETTING' => 'INTERMODAL_FIELDS', 
			'THE_VALUE' => 'true', 'SETTING_COMMENT' => 'Display fields specific to Intermodal (true / false)',
			'RESTRICTED' => true ),
		array( 'CATEGORY' => 'option', 'SETTING' => 'PO_FIELDS', 
			'THE_VALUE' => 'true', 'SETTING_COMMENT' => 'Display PO# fields (true / false)' ),
		array( 'CATEGORY' => 'option', 'SETTING' => 'CARRIER_WARNINGS', 
			'THE_VALUE' => 'false', 'SETTING_COMMENT' => 'Display carrier warnings (true / false)' ),
		array( 'CATEGORY' => 'option', 'SETTING' => 'EDIT_UNAPPROVED', 
			'THE_VALUE' => 'false', 'SETTING_COMMENT' => 'Allow editing of unapproved shipments (true / false)' ),
		array( 'CATEGORY' => 'option', 'SETTING' => 'REFNUM_FIELDS', 
			'THE_VALUE' => 'true', 'SETTING_COMMENT' => 'Display Ref# in list loads / shipments (true / false)' ),
		array( 'CATEGORY' => 'option', 'SETTING' => 'CONSOLIDATE_SHIPMENTS', 
			'THE_VALUE' => 'true', 'SETTING_COMMENT' => 'Allow combining shipments for billing (true / false)' ),
		array( 'CATEGORY' => 'option', 'SETTING' => 'CONSOLIDATE_NUM', 
			'THE_VALUE' => 'Cons #', 'SETTING_COMMENT' => 'Label for CONSOLIDATE_NUM field' ),
		array( 'CATEGORY' => 'option', 'SETTING' => 'LOAD_COMPLETE', 
			'THE_VALUE' => 'false', 'SETTING_COMMENT' => 'Enable jumping a load to complete status (true / false)' ),
		array( 'CATEGORY' => 'option', 'SETTING' => 'REQUIRE_LATE_REASON', 
			'THE_VALUE' => 'true', 'SETTING_COMMENT' => 'Require a reason when arriving / departing late (true / false)' ),

		array( 'CATEGORY' => 'option', 'SETTING' => 'Skip freight agreement', 
			'THE_VALUE' => 'true', 'SETTING_COMMENT' => 'Can dispatch with a carrier without freight agreement first (true / false)' ),
		array( 'CATEGORY' => 'option', 'SETTING' => 'Add Repositioning Stop', 
			'THE_VALUE' => 'true', 'SETTING_COMMENT' => 'Add a stop to move Driver / Tractor to first pick (true / false)' ),
		array( 'CATEGORY' => 'option', 'SETTING' => 'SHIPMENT_TIME_OPTION', 
			'THE_VALUE' => 'ASAP', 'SETTING_COMMENT' => 'Initial option (At / By / After / Between / ASAP)' ),
		array( 'CATEGORY' => 'option', 'SETTING' => 'PRELOAD_SHIPMENT_TIME', 
			'THE_VALUE' => 'false', 'SETTING_COMMENT' => 'Preload pickup and delivery times (true / false)' ),
		array( 'CATEGORY' => 'option', 'SETTING' => 'DISPATCH_DRIVER_ONLY', 
			'THE_VALUE' => 'true', 'SETTING_COMMENT' => 'Allow dispatch without tractor / trailer (true / false)' ),
		array( 'CATEGORY' => 'option', 'SETTING' => 'DUPLICATE_READY_DISPATCH', 
			'THE_VALUE' => 'true', 'SETTING_COMMENT' => 'Duplicated shipments set to ready dispatch state (true / false)' ),
		array( 'CATEGORY' => 'option', 'SETTING' => 'FSC_TRANSACTION_DATE', 
			'THE_VALUE' => 'delivered', 'SETTING_COMMENT' => 'Match FSC on which date (shipped / delivered).' ),

		array( 'CATEGORY' => 'option', 'SETTING' => 'TRAILER_TANKER_FIELDS', 
			'THE_VALUE' => 'false', 'SETTING_COMMENT' => 'Display tanker fields for trailers (true / false)' ),
		array( 'CATEGORY' => 'option', 'SETTING' => 'TRAILER_IM_FIELDS', 
			'THE_VALUE' => 'false', 'SETTING_COMMENT' => 'Display IM fields for trailers (true / false)' ),
		array( 'CATEGORY' => 'option', 'SETTING' => 'FIND_CARRIER', 
			'THE_VALUE' => 'true', 'SETTING_COMMENT' => 'Enable Find Carrier feature (true / false)' ),

		//! SCR# 852 - Containers Feature
		array( 'CATEGORY' => 'option', 'SETTING' => 'CONTAINERS', 
			'THE_VALUE' => 'false', 'SETTING_COMMENT' => 'Enable Containers feature (true / false)', 'RESTRICTED' => true ),

		//! SCR# 875 - Allow dispatch driver on multiple loads
		array( 'CATEGORY' => 'option', 'SETTING' => 'DISPATCH_DRIVER_MULTIPLE', 
			'THE_VALUE' => 'false', 'SETTING_COMMENT' => 'Allow dispatch driver on multiple loads (true / false)' ),

		//! SCR# 849 - Team Driver Feature
		array( 'CATEGORY' => 'option', 'SETTING' => 'TEAM_DRIVER', 
			'THE_VALUE' => 'false', 'SETTING_COMMENT' => 'Enable Team Driver feature (true / false)', 'RESTRICTED' => true ),
		array( 'CATEGORY' => 'option', 'SETTING' => 'FIND_CARRIER_YEARS', 
			'THE_VALUE' => '2', 'SETTING_COMMENT' => 'How far back to look at loads in years' ),

		array( 'CATEGORY' => 'option', 'SETTING' => 'FIND_CARRIER_BC', 
			'THE_VALUE' => '', 'SETTING_COMMENT' => 'Business codes for Find Carrier feature (Comma separated)' ),

		array( 'CATEGORY' => 'option', 'SETTING' => 'DEBUG_LOG_FILE', 
			'THE_VALUE' => 'log/debug.txt', 'SETTING_COMMENT' => 'Debug log file.', 'RESTRICTED' => true ),

		array( 'CATEGORY' => 'option', 'SETTING' => 'PHP_EXE', 
			'THE_VALUE' => 'd:\xampp\php\php.exe', 'SETTING_COMMENT' => 'Where to find php.exe.',
			'RESTRICTED' => true ),

		array( 'CATEGORY' => 'option', 'SETTING' => 'ENABLE_SEND_TRACE', 
			'THE_VALUE' => 'true', 'SETTING_COMMENT' => 'Can send a trace of a load to STS (true / false)' ),

		array( 'CATEGORY' => 'option', 'SETTING' => 'LOAD_SCREEN_REFRESH_RATE', 
			'THE_VALUE' => '0', 'SETTING_COMMENT' => 'How often to refresh the screen in seconds (0 = disabled)' ),
		array( 'CATEGORY' => 'option', 'SETTING' => 'SUMMARY_SCREEN_REFRESH_RATE', 
			'THE_VALUE' => '0', 'SETTING_COMMENT' => 'How often to refresh the screen in seconds (0 = disabled)' ),
		array( 'CATEGORY' => 'option', 'SETTING' => 'SHIPMENT_SCREEN_REFRESH_RATE', 
			'THE_VALUE' => '0', 'SETTING_COMMENT' => 'How often to refresh the screen in seconds (0 = disabled)' ),

		array( 'CATEGORY' => 'option', 'SETTING' => 'VIDEO_DIR', 
			'THE_VALUE' => 'http:COMMexspeeditelearningcenter.com / videos / ', 'SETTING_COMMENT' => 'Videos directory. Must be visible on the web, such as a subdirectory of Exspeedite', 'RESTRICTED' => true ),
		array( 'CATEGORY' => 'option', 'SETTING' => 'ALERT_SESSION_EXPIRY', 
			'THE_VALUE' => 'false', 'SETTING_COMMENT' => 'Send email alert when sessions expire prematurely (true / false)' ),
		array( 'CATEGORY' => 'option', 'SETTING' => 'ALERT_TAX_ISSUE', 
			'THE_VALUE' => 'false', 'SETTING_COMMENT' => 'Send email alert when there is a Canadian tax issue (true / false)' ),
		array( 'CATEGORY' => 'option', 'SETTING' => 'LENGTH_MENU', 
			'THE_VALUE' => '250, 25, 50, 100', 'SETTING_COMMENT' => 'List of length options (first is default)' ),
		array( 'CATEGORY' => 'option', 'SETTING' => 'SUMMARY_PALLETS_WEIGHT', 
			'THE_VALUE' => 'head', 'SETTING_COMMENT' => 'In Summary Pall &amp; Wt (all or head)' ),

		array( 'CATEGORY' => 'main', 'SETTING' => 'URL_PREFIX', 
			'THE_VALUE' => '', 'SETTING_COMMENT' => 'Should be set automatically to the prefix of the URL to Exspeedite installation. Should NOT be blank. Essential for QuickBooks online.', 'RESTRICTED' => true ),
		array( 'CATEGORY' => 'main', 'SETTING' => 'LAST_CHECKED', 
			'THE_VALUE' => 'dummy', 'SETTING_COMMENT' => 'Last release this was checked.', 'RESTRICTED' => true ),

		array( 'CATEGORY' => 'option', 'SETTING' => 'DOCK_TOGGLE_DIRECTION', 
			'THE_VALUE' => 'true', 'SETTING_COMMENT' => 'When docking backhaul shipment, change direction to head (true / false)' ),
		array( 'CATEGORY' => 'option', 'SETTING' => 'PEOPLENET_ENABLED', 
			'THE_VALUE' => 'false', 'SETTING_COMMENT' => 'Enable PeopleNet Mobile Comms (true / false)', 'RESTRICTED' => true ),

		array( 'CATEGORY' => 'option', 'SETTING' => 'FLEET_ENABLED', 
			'THE_VALUE' => 'false', 'SETTING_COMMENT' => 'Enable Tractor Fleet feature (true / false)', 'RESTRICTED' => true ),
		array( 'CATEGORY' => 'option', 'SETTING' => 'MAX_TRACTORS', 
			'THE_VALUE' => '0', 'SETTING_COMMENT' => 'The maximum number of tractors allowed in Exspeedite (0 = unlimited)', 'RESTRICTED' => true ),

		//! CMS settings
		array( 'CATEGORY' => 'option', 'SETTING' => 'CMS_ENABLED', 
			'THE_VALUE' => 'false', 'SETTING_COMMENT' => 'Enable CMS lead tracking (true / false)', 'RESTRICTED' => true ),
		array( 'CATEGORY' => 'option', 'SETTING' => 'CMS_APPT_MINUTES', 
			'THE_VALUE' => '30', 'SETTING_COMMENT' => 'How long (minutes) for calendar appointment' ),
		array( 'CATEGORY' => 'option', 'SETTING' => 'CMS_RESTRICT_SALESPEOPLE', 
			'THE_VALUE' => 'false', 'SETTING_COMMENT' => 'Restrict salespeople (true / false)', 'RESTRICTED' => true ),
		//! SCR# 420 - Allow sales people to grab leads
		array( 'CATEGORY' => 'option', 'SETTING' => 'CMS_SALESPEOPLE_LEADS', 
			'THE_VALUE' => 'true', 'SETTING_COMMENT' => 'Allow salespeople to access unassigned leads (true / false)' ),

		//! B&M settings
		array( 'CATEGORY' => 'option', 'SETTING' => 'CLIENT_EXTRA_STOPS', 
			'THE_VALUE' => 'false', 'SETTING_COMMENT' => 'Allow to bill extra stops for some clients (true / false)', 'RESTRICTED' => true ),
		array( 'CATEGORY' => 'option', 'SETTING' => 'INSPECTION_REPORTS', 
			'THE_VALUE' => 'false', 'SETTING_COMMENT' => 'Tractor / trailer inspection reports (true / false)', 'RESTRICTED' => true ),
		array( 'CATEGORY' => 'option', 'SETTING' => 'TEST_LOGIN', 
			'THE_VALUE' => 'false', 'SETTING_COMMENT' => 'Debug the login screen (true / false)', 'RESTRICTED' => true ),
		array( 'CATEGORY' => 'option', 'SETTING' => 'INSPECTION_REPORT_TITLE', 
			'THE_VALUE' => 'Inspection Report', 'SETTING_COMMENT' => 'Title for tractor / trailer inspection report (singular)' ),
		array( 'CATEGORY' => 'option', 'SETTING' => 'DRIVER_TYPES', 
			'THE_VALUE' => 'false', 'SETTING_COMMENT' => 'Allow different driver types (true / false)', 'RESTRICTED' => true ),

		//! SCR# 456 - Select Shipments For Load - default sort for shipments in left column
		array( 'CATEGORY' => 'option', 'SETTING' => 'SELSHIPMENT_DEFSHIPCODE', 
			'THE_VALUE' => 'false', 'SETTING_COMMENT' => 'Select Shipments For Load - default sort by shipment code (true / false)' ),

		//! SCR# 457 - Driver pay - default distance for load based on shipment client billing
		array( 'CATEGORY' => 'option', 'SETTING' => 'DPAY_DEFDISTANCE_CBILL', 
			'THE_VALUE' => 'false', 'SETTING_COMMENT' => 'Driver pay - default distance for load is based on shipment client billing, if 1 shipment per load (true / false)' ),


		//! SCR# 438 - New KPI - profitability by office
		array( 'CATEGORY' => 'option', 'SETTING' => 'KPI_PROFIT', 
			'THE_VALUE' => 'false', 'SETTING_COMMENT' => 'Enable profitability KPI (true / false)', 'RESTRICTED' => true ),

		//! SCR# 525 - default to active drivers
		array( 'CATEGORY' => 'option', 'SETTING' => 'DEFAULT_ACTIVE', 
			'THE_VALUE' => 'false', 'SETTING_COMMENT' => 'Default to active drivers / tractors / trailers (true / false)' ),

		//! SCR# 470 - Email attachment - need alternate From: address
		array( 'CATEGORY' => 'option', 'SETTING' => 'ATTACHMENTS_ALT_EMAILS', 
			'THE_VALUE' => 'false', 'SETTING_COMMENT' => 'Enable alternate From: address for sending attachments (true / false)', 'RESTRICTED' => true ),

		array( 'CATEGORY' => 'option', 'SETTING' => 'ATTACHMENT_STATS', 
			'THE_VALUE' => 'disabled', 'SETTING_COMMENT' => 'Enable attachments statistics (disabled / admin / superadmin)', 'RESTRICTED' => true ),

		array( 'CATEGORY' => 'email', 'SETTING' => 'EMAIL_INVOICE_OVERRIDE', 
			'THE_VALUE' => '', 'SETTING_COMMENT' => 'If set, send all invoices to this address' ),

		//! Slingshot settings
		
		//! SCR# 915 - office approval stage
		array( 'CATEGORY' => 'option', 'SETTING' => 'DEFAULT_ASOF_DATE', 
			'THE_VALUE' => 'none', 'SETTING_COMMENT' => 'What to use if a shipment has no date set. Has an impact when sorting or filtering shipments. (none / current)' ),

		//! SCR# 910 - Reuse office numbers from cancelled shipment on duplicate
		array( 'CATEGORY' => 'option', 'SETTING' => 'DUP_RESUSE_OFFICENUM', 
			'THE_VALUE' => 'false', 'SETTING_COMMENT' => 'Duplicate cancelled shipment, reuse the office number (true / false)' ),
		//! SCR# 906 - send notifications
		array( 'CATEGORY' => 'option', 'SETTING' => 'SEND_NOTIFICATIONS', 
			'THE_VALUE' => 'false', 'SETTING_COMMENT' => 'Send email notifications for picks and drops (true / false)' ),
		array( 'CATEGORY' => 'company', 'SETTING' => 'CONTACT_NAME', 
			'THE_VALUE' => '', 'SETTING_COMMENT' => 'Contact Name - for reports' ),
		array( 'CATEGORY' => 'email', 'SETTING' => 'EMAIL_NOTIFY_TEMPLATE', 
			'THE_VALUE' => 'templates/notify.txt', 'SETTING_COMMENT' => 'path to text file, the notify  template.',
			'RESTRICTED' => true ),


		//! SCR# 900 - list carrier screen
		array( 'CATEGORY' => 'option', 'SETTING' => 'LIST_CARRIERS_BOTH', 
			'THE_VALUE' => 'false', 'SETTING_COMMENT' => 'On list carriers screen, default to show both deleted and not deleted (true / false)' ),

		//! SCR# 715 - Enable/disable Pipedrive import
		array( 'CATEGORY' => 'option', 'SETTING' => 'PIPEDRIVE_ENABLED', 
			'THE_VALUE' => 'false', 'SETTING_COMMENT' => 'Enable import of leads from Pipedrive (true / false)', 'RESTRICTED' => true ),

		//! SCR# 789 - Bulk e-mail of invoices
		array( 'CATEGORY' => 'option', 'SETTING' => 'BATCH_INV_MAX_SIZE', 
			'THE_VALUE' => '10', 'SETTING_COMMENT' => 'Bulk email max size in MB' ),

		array( 'CATEGORY' => 'option', 'SETTING' => 'MARGIN_LUMPER_NOTAX', 
			'THE_VALUE' => 'false', 'SETTING_COMMENT' => 'Margin report format (true / false)' ),

		//! SCR# 803 - Bulk e-mail date based on attachment
		array( 'CATEGORY' => 'option', 'SETTING' => 'BATCH_INV_DATE_ATTACHMENT', 
			'THE_VALUE' => 'false', 'SETTING_COMMENT' => 'Bulk email based on attachment date (true / false)' ),

		array( 'CATEGORY' => 'option', 'SETTING' => 'EMAIL_BATCH_SUBJECT', 
			'THE_VALUE' => 'Batch Invoices', 'SETTING_COMMENT' => 'Subject for batch emails' ),

		array( 'CATEGORY' => 'option', 'SETTING' => 'BATCH_INV_ATTACHMENT', 
			'THE_VALUE' => 'Billing', 'SETTING_COMMENT' => 'The attachment type to send' ),

		//! SCR# 715 - Pipedrive URL
		array( 'CATEGORY' => 'api', 'SETTING' => 'PIPEDRIVE_URL', 
			'THE_VALUE' => 'https://DOMAIN.pipedrive.com/api/v1', 'SETTING_COMMENT' => 'Pipedrive API URL' ),

		array( 'CATEGORY' => 'api', 'SETTING' => 'PIPEDRIVE_LABEL', 
			'THE_VALUE' => '', 'SETTING_COMMENT' => 'Label to apply to imported leads (not duplicates), leave blank for none' ),

		array( 'CATEGORY' => 'api', 'SETTING' => 'PIPEDRIVE_LOG_FILE', 
			'THE_VALUE' => 'log/pipedrive.txt', 'SETTING_COMMENT' => 'Pipedrive log file.', 'RESTRICTED' => true ),
		array( 'CATEGORY' => 'api', 'SETTING' => 'PIPEDRIVE_DIAG_LEVEL', 
			'THE_VALUE' => 'error', 'SETTING_COMMENT' => 'What level of diagnostics to send (trace / error / warning / notice / debug)', 'RESTRICTED' => true ),

		//! SCR# 588 - Add setting to NOT carry over dates when duplicating shipments
		//! SCR# 603 - Duplicate shipment - current month copy over dates
		array( 'CATEGORY' => 'option', 'SETTING' => 'DUP_SHIPMENT_NO_DATES', 
			'THE_VALUE' => 'false', 'SETTING_COMMENT' => 'Do NOT copy over dates when duplicating shipment (true / cmonth / false) cmonth=only copy if current month' ),

		//! SCR# 616 - allow to hide carrier alerts
		array( 'CATEGORY' => 'option', 'SETTING' => 'ALERT_CARRIERS_ENABLED', 
			'THE_VALUE' => 'true', 'SETTING_COMMENT' => 'Display alerts on home page for carriers (true / false)' ),

		//! SCR# 696 - List Load Screen - Color code pick up and delivery times
		array( 'CATEGORY' => 'option', 'SETTING' => 'COLOUR_LOAD_TIMES', 
			'THE_VALUE' => 'false', 'SETTING_COMMENT' => 'Color code pick up and delivery times (true / false)' ),

		//! SCR# 779 - Stopoff, copy PO#s, Pickups
		array( 'CATEGORY' => 'option', 'SETTING' => 'STOPOFF_COPY_PO', 
			'THE_VALUE' => 'false', 'SETTING_COMMENT' => 'Copy the PO# when creating a stopoff (true / false)' ),
		array( 'CATEGORY' => 'option', 'SETTING' => 'STOPOFF_COPY_PICKUP', 
			'THE_VALUE' => 'false', 'SETTING_COMMENT' => 'Copy the Pickup# and Customer#  when creating a stopoff (true / false)' ),

		//! SCR# 712 - Email queue implementation
		array( 'CATEGORY' => 'option', 'SETTING' => 'EMAIL_QUEUEING', 
			'THE_VALUE' => 'false', 'SETTING_COMMENT' => 'Enable email queueuing and resend (true / false)' ),

		array( 'CATEGORY' => 'option', 'SETTING' => 'EMAIL_QUEUE_EXPIRE', 
			'THE_VALUE' => '7', 'SETTING_COMMENT' => 'Number of days to expire queued email (7 / 14 / 30 / 60)', 'RESTRICTED' => true ),
			
		//! SCR# TBD - Email return request
		
		array( 'CATEGORY' => 'email', 'SETTING' => 'REQUEST_DELIVERY_RECEIPT', 
			'THE_VALUE' => 'false', 'SETTING_COMMENT' => 'Include Return-Receipt-To (true / false)' ),	
			
		array( 'CATEGORY' => 'email', 'SETTING' => 'REQUEST_READ_RECEIPT', 
			'THE_VALUE' => 'false', 'SETTING_COMMENT' => 'Include Disposition-Notification-To (true / false)' ),	

		//! SCR# 722 - Email notification of new shipper / consignee
		array( 'CATEGORY' => 'email', 'SETTING' => 'NOTIFY_NEW_SHIPCONS', 
			'THE_VALUE' => 'false', 'SETTING_COMMENT' => 'Send email to user that created a new shipper / consignee (true / false)' ),

		//! SCR# 652 - Send email to accounting when a user changes insurance $ and / or dates
		array( 'CATEGORY' => 'email', 'SETTING' => 'INS_NOTIFY_ACCOUNTING', 
			'THE_VALUE' => 'false', 'SETTING_COMMENT' => 'Send email to accounting when a user changes insurance $ and / or dates. See email / EMAIL_NOTIFY_ACCOUNTING (true / false)' ),

		//! SCR# 650 - Restrict "Complete Load" to admin
		array( 'CATEGORY' => 'option', 'SETTING' => 'ADMIN_COMPLETE_LOAD', 
			'THE_VALUE' => 'false', 'SETTING_COMMENT' => 'Restrict "Complete Load" to admin if not enough insurance (true / false)' ),

		//! SCR# 617 - Include pivot R&M report
		array( 'CATEGORY' => 'option', 'SETTING' => 'PIVOT_RM_REPORT', 
			'THE_VALUE' => 'false', 'SETTING_COMMENT' => 'Include pivot R&M report (true / false)',
			'RESTRICTED' => true ),

		array( 'CATEGORY' => 'option', 'SETTING' => 'EDIT_DRIVER_RESTRICTIONS_ENABLED', 
			'THE_VALUE' => 'false', 'SETTING_COMMENT' => 'Restrict editing certain driver fields (true / false)' ),
		//! SCR# 381 - optionally restrict all but manager from changing sales person
		array( 'CATEGORY' => 'option', 'SETTING' => 'EDIT_SALESPERSON_RESTRICTED', 
			'THE_VALUE' => 'false', 'SETTING_COMMENT' => 'Restrict changing the salesperson to admin / managers (true / false)' ),

		//! SCR# 581 - optionally update Bill-to information when duplicating a shipment
		array( 'CATEGORY' => 'option', 'SETTING' => 'SHIPMENT_DUP_UPDT_BILLTO', 
			'THE_VALUE' => 'false', 'SETTING_COMMENT' => 'update Bill-to information when duplicating a shipment (true / false)' ),

		//! SCR# 721 - optionally update sales person when duplicating a shipment
		array( 'CATEGORY' => 'option', 'SETTING' => 'SHIPMENT_DUP_UPDT_SALES', 
			'THE_VALUE' => 'false', 'SETTING_COMMENT' => 'update sales person when duplicating a shipment (true / false)' ),

		//! SCR# 546 - Make messages available to managers group, move to Operations menu
		array( 'CATEGORY' => 'option', 'SETTING' => 'MESSAGES_MANGERS', 
			'THE_VALUE' => 'false', 'SETTING_COMMENT' => 'Messages available to managers group, move to Operations menu (true / false)' ),

		//! SCR# 547 - Make messages expire after so many days
		array( 'CATEGORY' => 'option', 'SETTING' => 'MESSAGES_EXPIRY', 
			'THE_VALUE' => '30', 'SETTING_COMMENT' => 'Messages deleted after N days, 0 = do not expire (integer #days)' ),

		//! SCR# 437 - optionally remove billing for cancelled shipments
		array( 'CATEGORY' => 'option', 'SETTING' => 'CLEAR_CANCELLED_BILLING', 
			'THE_VALUE' => 'false', 'SETTING_COMMENT' => 'Clear out billing information for cancelled shipments (true / false)' ),

		//! SCR# 403 - optionally restrict access to filters in list shipment screen
		array( 'CATEGORY' => 'option', 'SETTING' => 'RESTRICT_SHIPMENT_BFILTERS', 
			'THE_VALUE' => 'false', 'SETTING_COMMENT' => 'Restrict use of billing filters in list shipment screen to billing / finance (true / false)' ),

		array( 'CATEGORY' => 'option', 'SETTING' => 'RESTRICT_SHIPMENT_EMAIL_FILTERS', 
			'THE_VALUE' => 'false', 'SETTING_COMMENT' => 'Restrict use of email filters in list shipment screen to admin (true / false)' ),

		//! SCR# 405 - cutoff date for shipments
		array( 'CATEGORY' => 'option', 'SETTING' => 'SHIPMENT_CUTOFF', 
			'THE_VALUE' => '', 'SETTING_COMMENT' => 'Date to cutoff adding shipments in loads, based on CREATED_DATE (mm/dd/yyyy)' ),


		//! SCR# 397 - optionally restrict carrier with insufficient insurance
		array( 'CATEGORY' => 'option', 'SETTING' => 'CARRIER_INSUFFCIENT_INS', 
			'THE_VALUE' => 'false', 'SETTING_COMMENT' => 'Restrict carriers for insufficient insurance (true / false)' ),

		//! SCR# 498 - pagination for driver pay billing history
		array( 'CATEGORY' => 'option', 'SETTING' => 'DRIVER_PAY_PAGESIZE', 
			'THE_VALUE' => '12', 'SETTING_COMMENT' => 'Number of weeks to show in billing history screen (# weeks)' ),

		array( 'CATEGORY' => 'option', 'SETTING' => 'EXPIRE_CARRIERS_ENABLED', 
			'THE_VALUE' => 'false', 'SETTING_COMMENT' => 'Mark carriers expired (true / false)' ),
		array( 'CATEGORY' => 'option', 'SETTING' => 'EXPIRE_DRIVERS_ENABLED', 
			'THE_VALUE' => 'false', 'SETTING_COMMENT' => 'Mark drivers expired (true / false)' ),
		array( 'CATEGORY' => 'option', 'SETTING' => 'EXPIRE_TRACTORS_ENABLED', 
			'THE_VALUE' => 'false', 'SETTING_COMMENT' => 'Mark tractor expired (true / false)' ),
		array( 'CATEGORY' => 'option', 'SETTING' => 'EXPIRE_TRAILERS_ENABLED', 
			'THE_VALUE' => 'false', 'SETTING_COMMENT' => 'Mark trailers expired (true / false)' ),

		array( 'CATEGORY' => 'option', 'SETTING' => 'IMPORT_CASH_ADVANCES', 
			'THE_VALUE' => 'false', 'SETTING_COMMENT' => 'Import cash advances from fuel cards (true / false)' ),
		array( 'CATEGORY' => 'email', 'SETTING' => 'EMAIL_NOTIFY_ACCOUNTING', 
			'THE_VALUE' => '', 'SETTING_COMMENT' => 'Email to accounting on new client. Blank = disabled.' ),
		array( 'CATEGORY' => 'email', 'SETTING' => 'EMAIL_ENABLED', 
			'THE_VALUE' => 'true', 'SETTING_COMMENT' => 'Email is enabled (true / false)' ),
		array( 'CATEGORY' => 'email', 'SETTING' => 'EMAIL_ATT_DEFAULT_CLIENT', 
			'THE_VALUE' => 'false', 'SETTING_COMMENT' => 'When emailing an attachment, try to default to the client email (true / false)' ),

		array( 'CATEGORY' => 'option', 'SETTING' => 'OVERRIDE_CARRIER_EXPIRY', 
			'THE_VALUE' => 'false', 'SETTING_COMMENT' => 'Allow assigning a carrier that is expired (true / false)' ),
		array( 'CATEGORY' => 'option', 'SETTING' => 'REQUIRE_BUSINESS_CODE', 
			'THE_VALUE' => 'false', 'SETTING_COMMENT' => 'Require a business code for a shipment to be ready to dispatch (true / false)' ),
		array( 'CATEGORY' => 'api', 'SETTING' => 'CARRIER_TRANSACTION_DATE', 
			'THE_VALUE' => 'completed', 'SETTING_COMMENT' => 'Which date for carrier billing (completed / shipped / delivered)' ),
		array( 'CATEGORY' => 'option', 'SETTING' => 'FSC_PERCENT_FREIGHT_ONLY', 
			'THE_VALUE' => 'everything', 'SETTING_COMMENT' => 'Calculate FSC as percentage of freight charges only (justfreight / everything / freightmiles)' ),
		array( 'CATEGORY' => 'option', 'SETTING' => 'DRIVER_MANRATES_BONUSABLE', 
			'THE_VALUE' => 'false', 'SETTING_COMMENT' => 'Are driver pay manual rates bonusable (true / false)' ),
		array( 'CATEGORY' => 'option', 'SETTING' => 'HIDE_CHARGES_MANIFEST', 
			'THE_VALUE' => 'false', 'SETTING_COMMENT' => 'Hide charges on Send Freight Agreement popup (true / false)' ),
		array( 'CATEGORY' => 'option', 'SETTING' => 'HIDE_STOPS_MANIFEST', 
			'THE_VALUE' => 'false', 'SETTING_COMMENT' => 'Hide stops on Send Freight Agreement (true / false)' ),
		array( 'CATEGORY' => 'option', 'SETTING' => 'HIDE_SHIPMENT_MANIFEST', 
			'THE_VALUE' => 'false', 'SETTING_COMMENT' => 'Hide shipment numbers on Send Freight Agreement (true / false)' ),
		array( 'CATEGORY' => 'option', 'SETTING' => 'BILLING_LOG_HOURS', 
			'THE_VALUE' => 'false', 'SETTING_COMMENT' => 'Allow entry of hours in the billing screen. These hours DO NOT go into any invoice. (true / false)' ),
		array( 'CATEGORY' => 'option', 'SETTING' => 'RESOURCE_DISTANCES', 
			'THE_VALUE' => 'false', 'SETTING_COMMENT' => 'Calculate distance from last known stop for driver / tractor / trailer (true / false)' ),
		array( 'CATEGORY' => 'option', 'SETTING' => 'RESOURCE_MAX_DISTANCE', 
			'THE_VALUE' => '300', 'SETTING_COMMENT' => 'Alert if over this distance (in miles)' ),

		array( 'CATEGORY' => 'option', 'SETTING' => 'ALERT_EXPIRED', 
			'THE_VALUE' => 'false', 'SETTING_COMMENT' => 'Alert on the home screen when carriers / drivers / tractors / trailers need attention (true / false / expanded) expanded = do not collapse alerts to save screen space' ),
		array( 'CATEGORY' => 'api', 'SETTING' => 'BOC_LAST_DL', 
			'THE_VALUE' => '0', 'SETTING_COMMENT' => 'Timestamp of last DL from Bank of Canada.' ),
		array( 'CATEGORY' => 'option', 'SETTING' => 'MANIFEST_STOPS', 
			'THE_VALUE' => 'templates/manifest_stops.php', 'SETTING_COMMENT' => 'Template for stops in carrier / driver manifest.' ),
		array( 'CATEGORY' => 'option', 'SETTING' => 'FORM_EMAIL_TEMPLATE', 
			'THE_VALUE' => 'templates/form_email_template.html', 'SETTING_COMMENT' => 'Template for form email.' ),
		array( 'CATEGORY' => 'option', 'SETTING' => 'DUPLICATE_SHIPMENT_FREIGHT', 
			'THE_VALUE' => 'false', 'SETTING_COMMENT' => 'When you duplicate the shipment, duplicate the freight charges (true / false)' ),
		array( 'CATEGORY' => 'option', 'SETTING' => 'PRINT_MANIFEST_AFTER_SEND', 
			'THE_VALUE' => 'false', 'SETTING_COMMENT' => 'Enable the option to print the manifest after sending (true / false)' ),
		array( 'CATEGORY' => 'option', 'SETTING' => 'USE_DUE_DATE_OVER_ACTUAL', 
			'THE_VALUE' => 'false', 'SETTING_COMMENT' => 'Choose the Due date before the actual date for selecting the transaction date (true / false)' ),
		array( 'CATEGORY' => 'option', 'SETTING' => 'PO_NUMBERS_WRITABLE', 
			'THE_VALUE' => 'false', 'SETTING_COMMENT' => 'Allow editing PO numbers after approved / billed (true / false)' ),
		array( 'CATEGORY' => 'option', 'SETTING' => 'MANIFEST_HIDE_REPO_STOP', 
			'THE_VALUE' => 'false', 'SETTING_COMMENT' => 'Hide reposition stop on Send Freight Agreement (true / false)' ),

		array( 'CATEGORY' => 'option', 'SETTING' => 'SEND_PDF_EMAILS', 
			'THE_VALUE' => 'false', 'SETTING_COMMENT' => 'Send invoice and manifest as PDF (true / false)', 'RESTRICTED' => true ),
		array( 'CATEGORY' => 'api', 'SETTING' => 'HTML2PDFROCKET_API_KEY', 
			'THE_VALUE' => '', 'SETTING_COMMENT' => 'HTML2PDFROCKET API Key - for PDF conversion, maps.', 'HIDDEN' => true ),
		array( 'CATEGORY' => 'api', 'SETTING' => 'HTML2PDFROCKET_URL', 
			'THE_VALUE' => 'https://api.html2pdfrocket.com/pdf', 'SETTING_COMMENT' => 'HTML2PDFROCKET URL (https://api.html2pdfrocket.com/pdf / https://legacy.html2pdfrocket.com/pdf)', 'RESTRICTED' => true ),


		array( 'CATEGORY' => 'option', 'SETTING' => 'TRACK_SHIPMENT_DISP_READY', 
			'THE_VALUE' => 'false', 'SETTING_COMMENT' => 'Send diagnostic email when shipment changes back to Ready (true / false)', 'RESTRICTED' => true ),

		//! SCR# 514 - Lanes report
		array( 'CATEGORY' => 'option', 'SETTING' => 'LANES_REPORT', 
			'THE_VALUE' => 'false', 'SETTING_COMMENT' => 'Enable lanes report (true / false)', 'RESTRICTED' => true ),

		//! SCR# 519 - log exports to Sage50
		array( 'CATEGORY' => 'option', 'SETTING' => 'SAGE50_LOG_FILE', 
			'THE_VALUE' => 'log/sage50.txt', 'SETTING_COMMENT' => 'Sage50 export log file.', 'RESTRICTED' => true ),

		//! SCR# 531 - debug log level
		array( 'CATEGORY' => 'option', 'SETTING' => 'DEBUG_DIAG_LEVEL', 
			'THE_VALUE' => 'debug', 'SETTING_COMMENT' => 'What level of diagnostics to send (trace / error / warning / notice / debug)', 'RESTRICTED' => true ),

		//! SCR# 584 - New coulum Client_ID
		array( 'CATEGORY' => 'option', 'SETTING' => 'CLIENT_ID', 
			'THE_VALUE' => 'false', 'SETTING_COMMENT' => 'Enable useless CLIENT_ID in client table for backwards compatibilty with backwards products (true / false)', 'RESTRICTED' => true ),

		//! SCR# 647 - R&M Reports - show extra fields
		array( 'CATEGORY' => 'option', 'SETTING' => 'RM_REPORT_EXTRA_FIELDS', 
			'THE_VALUE' => 'false', 'SETTING_COMMENT' => 'Show extra columns in R&M reports, edit tractor / trailer screens (true / false)' ),

		//! SCR# 639 - Restrict access to insurance
		array( 'CATEGORY' => 'option', 'SETTING' => 'RESTRICT_INSURANCE', 
			'THE_VALUE' => 'false', 'SETTING_COMMENT' => 'Restrict access to client / carrier insurance setttings to admin group (true / false)' ),

		array( 'CATEGORY' => 'option', 'SETTING' => 'RESTRICT_CREDIT_LIMIT', 
			'THE_VALUE' => 'false', 'SETTING_COMMENT' => 'Restrict access to client credit limit to admin group (true / false)' ),

		array( 'CATEGORY' => 'option', 'SETTING' => 'RESTRICT_CURRENCY', 
			'THE_VALUE' => 'false', 'SETTING_COMMENT' => 'Restrict access to client / carrier currency to admin group (true / false)' ),

		//! SCR# 644 - FilesAnywhere API Key
		array( 'CATEGORY' => 'api', 'SETTING' => 'FA_API_KEY', 
			'THE_VALUE' => '590E34A4-3CA4-43ED-B026-76C3A1F24EEC',
			'SETTING_COMMENT' => 'FilesAnywhere API Key', 'RESTRICTED' => true, 'HIDDEN' => true ),

		array( 'CATEGORY' => 'api', 'SETTING' => 'FA_API_WSDL', 
			'THE_VALUE' => 'https:COMMapi.filesanywhere.com / v2 / fawapi.asmx?WSDL', 'SETTING_COMMENT' => 'FilesAnywhere API WSDL', 'RESTRICTED' => true ),

		array( 'CATEGORY' => 'api', 'SETTING' => 'FA_API_PATH', 
			'THE_VALUE' => '', 'SETTING_COMMENT' => 'FilesAnywhere path to store attachments. It should end in a backslash [you need to enter a double backslash to get this]', 'RESTRICTED' => true ),

		array( 'CATEGORY' => 'api', 'SETTING' => 'FA_API_CLIENTID', 
			'THE_VALUE' => '50', 'SETTING_COMMENT' => 'FilesAnywhere Client ID', 'RESTRICTED' => true ),

		array( 'CATEGORY' => 'api', 'SETTING' => 'FA_API_USERNAME', 
			'THE_VALUE' => '', 'SETTING_COMMENT' => 'FilesAnywhere username', 'RESTRICTED' => true ),

		array( 'CATEGORY' => 'api', 'SETTING' => 'FA_API_PASSWORD', 
			'THE_VALUE' => '', 'SETTING_COMMENT' => 'FilesAnywhere password', 'RESTRICTED' => true, 'HIDDEN' => true ),

		array( 'CATEGORY' => 'option', 'SETTING' => 'ATTACHMENTS_WHERE', 
			'THE_VALUE' => 'local',
			'SETTING_COMMENT' => 'Where to store new attachments. If filesanywhere, consult settings api / FA_* (local / filesanywhere)', 'RESTRICTED' => true ),

		//! SCR settings
		array( 'CATEGORY' => 'option', 'SETTING' => 'SCR_ENABLED', 
			'THE_VALUE' => 'false', 'SETTING_COMMENT' => 'Enable SCR tracking (true / false)', 'RESTRICTED' => true ),

		array( 'CATEGORY' => 'option', 'SETTING' => 'HTTP_PID_FILE', 
			'THE_VALUE' => 'D:\xampp\apache\logs\httpd.pid', 'SETTING_COMMENT' => 'Path to Apache pid file.', 'RESTRICTED' => true ),

		array( 'CATEGORY' => 'option', 'SETTING' => 'MULTI_COMPANY', 
			'THE_VALUE' => 'false', 'SETTING_COMMENT' => 'Enable multi company (true / false)', 'RESTRICTED' => true ),
		array( 'CATEGORY' => 'option', 'SETTING' => 'CANADA_POSTCODES', 
			'THE_VALUE' => 'false', 'SETTING_COMMENT' => 'Enable canada post codes (true / false)', 'RESTRICTED' => true ),
		array( 'CATEGORY' => 'option', 'SETTING' => 'MULTI_CURRENCY', 
			'THE_VALUE' => 'false', 'SETTING_COMMENT' => 'Enable multi currency (true / false)', 'RESTRICTED' => true ),
		array( 'CATEGORY' => 'option', 'SETTING' => 'HOME_CURRENCY', 
			'THE_VALUE' => 'USD', 'SETTING_COMMENT' => 'Set home currency (USD / CAD)', 'RESTRICTED' => true ),
		array( 'CATEGORY' => 'option', 'SETTING' => 'CURRENCY_LIST', 
			'THE_VALUE' => 'USD', 'SETTING_COMMENT' => 'Comma separated list (example USD,CAD)', 'RESTRICTED' => true ),
		array( 'CATEGORY' => 'option', 'SETTING' => 'ELOGS_ENABLED', 
			'THE_VALUE' => 'false', 'SETTING_COMMENT' => 'Enable E-Logs (true / false)', 'RESTRICTED' => true ),
		array( 'CATEGORY' => 'option', 'SETTING' => 'FSC_4DIGIT', 
			'THE_VALUE' => 'false', 'SETTING_COMMENT' => 'Enable FSC to 4 decimal places (true / false)', 'RESTRICTED' => true ),
		
		//! Zoominfo Feature
		array( 'CATEGORY' => 'option', 'SETTING' => 'ZOOMINFO_ENABLED', 
			'THE_VALUE' => 'false', 'SETTING_COMMENT' => 'Enable Zoominfo interface (true / false)', 'RESTRICTED' => true ),
		array( 'CATEGORY' => 'option', 'SETTING' => 'ZOOMINFO_IMPORT_DUP', 
			'THE_VALUE' => 'false', 'SETTING_COMMENT' => 'Import duplicates vs discard (true / false)' ),

		//! Attachments Feature
		array( 'CATEGORY' => 'option', 'SETTING' => 'ATTACHMENTS_ENABLED', 
			'THE_VALUE' => 'false', 'SETTING_COMMENT' => 'Enable Attachments (true / false)', 'RESTRICTED' => true ),
		array( 'CATEGORY' => 'option', 'SETTING' => 'ATTACHMENT_DIR', 
			'THE_VALUE' => 'c: / exspeedite / attachments / ', 'SETTING_COMMENT' => 'Directory where attachments are stored. Should end with a  / ', 'RESTRICTED' => true ),
		
		//! SCR# 606 - Directory to store archives
		array( 'CATEGORY' => 'option', 'SETTING' => 'ARCHIVE_DIR', 
			'THE_VALUE' => 'c: / exspeedite / archive / ', 'SETTING_COMMENT' => 'Directory where archives are stored. Should end with a  / ', 'RESTRICTED' => true ),
		array( 'CATEGORY' => 'option', 'SETTING' => 'ARCHIVE_LOG_FILE', 
			'THE_VALUE' => 'log/archive.txt', 'SETTING_COMMENT' => 'Archive log file.', 'RESTRICTED' => true ),
		array( 'CATEGORY' => 'option', 'SETTING' => 'ARCHIVE_DIAG_LEVEL', 
			'THE_VALUE' => 'debug', 'SETTING_COMMENT' => 'What level of diagnostics to send (trace / error / warning / notice / debug)', 'RESTRICTED' => true ),

		array( 'CATEGORY' => 'option', 'SETTING' => 'ATTACHMENT_LOG_FILE', 
			'THE_VALUE' => 'log/attachment.txt', 'SETTING_COMMENT' => 'Attachments log file.', 'RESTRICTED' => true ),
		array( 'CATEGORY' => 'option', 'SETTING' => 'ATTACHMENT_DIAG_LEVEL', 
			'THE_VALUE' => 'debug', 'SETTING_COMMENT' => 'What level of diagnostics to send (trace / error / warning / notice / debug)', 'RESTRICTED' => true ),
		
		//! SCR# 459 - Attachments enhancements
		array( 'CATEGORY' => 'option', 'SETTING' => 'ATTACHMENTS_MAX_SIZE', 
			'THE_VALUE' => '20000000', 'SETTING_COMMENT' => 'Max Size For Attachments (Bytes)', 'RESTRICTED' => true ),
		array( 'CATEGORY' => 'option', 'SETTING' => 'ATTACHMENTS_COMPRESS_PDF', 
			'THE_VALUE' => 'true', 'SETTING_COMMENT' => 'Use Ghostscript to compress PDF - see also GHOSTSCRIPT_PATH and GHOSTSCRIPT_SETTINGS (true / false)', 'RESTRICTED' => true ),
		array( 'CATEGORY' => 'option', 'SETTING' => 'GHOSTSCRIPT_PATH', 
			'THE_VALUE' => 'C:\Program Files\gs\gs9.23\bin\gswin64.exe', 'SETTING_COMMENT' => 'Full path to Ghostscript executable, to compress attachments', 'RESTRICTED' => true ),
		array( 'CATEGORY' => 'option', 'SETTING' => 'GHOSTSCRIPT_SETTINGS', 
			'THE_VALUE' => '-q -dNOPAUSE -dBATCH -dSAFER -sDEVICE=pdfwrite -dCompatibilityLevel=1.3 -dPDFSETTINGS= / ebook -dEmbedAllFonts=true -dSubsetFonts=true -dColorImageDownsampleType= / Bicubic -dColorImageResolution=144 -dGrayImageDownsampleType= / Bicubic -dGrayImageResolution=144 -dMonoImageDownsampleType= / Bicubic -dMonoImageResolution=144', 'SETTING_COMMENT' => 'Settings for Ghostscript to compress attachments', 'RESTRICTED' => true ),
		array( 'CATEGORY' => 'option', 'SETTING' => 'EMAIL_ATTACHMENT_AS_LINK', 
			'THE_VALUE' => 'true', 'SETTING_COMMENT' => 'Send links instead of attaching files (true / false)', 'RESTRICTED' => true ),
		
		

		array( 'CATEGORY' => 'portal', 'SETTING' => 'PORTAL_TITLE', 
			'THE_VALUE' => 'Client Portal', 'SETTING_COMMENT' => 'Title For the login screen' ),
		array( 'CATEGORY' => 'portal', 'SETTING' => 'URL_MAIN', 
			'THE_VALUE' => 'http:COMMwww.pipcotrans.com / ', 'SETTING_COMMENT' => 'URL to the main website' ),
		array( 'CATEGORY' => 'portal', 'SETTING' => 'URL_CONTACT', 
			'THE_VALUE' => 'http:COMMwww.pipcotrans.com / contact_us.html', 'SETTING_COMMENT' => 'URL to the contact us page' ),

		array( 'CATEGORY' => 'email', 'SETTING' => 'EMAIL_LOG_FILE', 
			'THE_VALUE' => 'log/email.txt', 'SETTING_COMMENT' => 'Email log file.', 'RESTRICTED' => true ),
		array( 'CATEGORY' => 'email', 'SETTING' => 'EMAIL_MANIFEST_CC', 
			'THE_VALUE' => '', 'SETTING_COMMENT' => 'Cc: recipient for carrier manifest.' ),
		array( 'CATEGORY' => 'email', 'SETTING' => 'EMAIL_MANIFEST_SUBJECT', 
			'THE_VALUE' => 'Load Confirmation Agreement', 'SETTING_COMMENT' => 'Email subject for for carrier manifest.' ),
		array( 'CATEGORY' => 'email', 'SETTING' => 'EMAIL_MANIFEST_TEMPLATE', 
			'THE_VALUE' => 'templates/manifest1.txt', 'SETTING_COMMENT' => 'path to text file, the carrier manifest template.',
			'RESTRICTED' => true ),
		array( 'CATEGORY' => 'email', 'SETTING' => 'EMAIL_MANIFEST_SIG', 
			'THE_VALUE' => 'images / Bob.gif', 'SETTING_COMMENT' => 'path to signature image, for the carrier manifest.' ),
		array( 'CATEGORY' => 'email', 'SETTING' => 'EMAIL_MANIFEST_CONTACT', 
			'THE_VALUE' => 'Bob Carlton Oper Mgr Ext 102<br>Harry Jackson Safety Mgr Ext 105', 'SETTING_COMMENT' => 'text below signature, for the carrier manifest.' ),
		array( 'CATEGORY' => 'email', 'SETTING' => 'EMAIL_FROM_NAME', 
			'THE_VALUE' => 'Transportation Inc.', 'SETTING_COMMENT' => 'Email is sent from this name.' ),
		array( 'CATEGORY' => 'email', 'SETTING' => 'EMAIL_FROM_ADDRESS', 
			'THE_VALUE' => 'exspeedite@strongtco.com', 'SETTING_COMMENT' => 'Email is sent from this address.' ),

		array( 'CATEGORY' => 'email', 'SETTING' => 'EMAIL_REPLY_TO', 
			'THE_VALUE' => '', 'SETTING_COMMENT' => 'Reply-to: this address. If blank, do not include.' ),

		//! Invoice
		array( 'CATEGORY' => 'email', 'SETTING' => 'EMAIL_SEND_INVOICES', 
			'THE_VALUE' => 'false', 'SETTING_COMMENT' => 'Send invoices to clients via email when shipment is approved (true / false)' ),
			
		//! Enable Approved (Operations)
		array( 'CATEGORY' => 'option', 'SETTING' => 'SHIPMENT_APPROVE_OPERATIONS', 
			'THE_VALUE' => 'false', 'SETTING_COMMENT' => 'Allow shipments to be Approved-Operations (true / false)' ),
		array( 'CATEGORY' => 'email', 'SETTING' => 'EMAIL_INVOICE_CC', 
			'THE_VALUE' => '', 'SETTING_COMMENT' => 'Cc: recipient for invoice.' ),
		array( 'CATEGORY' => 'email', 'SETTING' => 'EMAIL_INVOICE_SUBJECT', 
			'THE_VALUE' => 'Invoice# ', 'SETTING_COMMENT' => 'Email subject for for invoice (# will be appended).' ),
		array( 'CATEGORY' => 'email', 'SETTING' => 'EMAIL_INVOICE_TEMPLATE', 
			'THE_VALUE' => 'templates/invoice1.txt', 'SETTING_COMMENT' => 'path to text file, the invoice template.',
			'RESTRICTED' => true ),
		array( 'CATEGORY' => 'email', 'SETTING' => 'EMAIL_INVOICE_TEMPLATE2', 
			'THE_VALUE' => 'templates/invoice2.txt', 'SETTING_COMMENT' => 'path to text file, the invoice template #2.',
			'RESTRICTED' => true ),
		array( 'CATEGORY' => 'email', 'SETTING' => 'EMAIL_INVOICE_ATTACHMENTS', 
			'THE_VALUE' => '', 'SETTING_COMMENT' => 'Comma separated, case sensitive list of attachment types to include.' ),
		array( 'CATEGORY' => 'email', 'SETTING' => 'EMAIL_MANIFEST_ATTACHMENTS', 
			'THE_VALUE' => '', 'SETTING_COMMENT' => 'Comma separated, case sensitive list of attachment types to include.' ),

		//! SCR# 780 - Setting for billing emails are sent as attachments
		array( 'CATEGORY' => 'option', 'SETTING' => 'SHIPMENT_FBILLING_ATTACHMENT', 
			'THE_VALUE' => 'false', 'SETTING_COMMENT' => 'List Shipment, billing emails are sent as attachments (true / false)' ),

		array( 'CATEGORY' => 'api', 'SETTING' => 'FUELCARD_DIAG_LEVEL', 
			'THE_VALUE' => 'error', 'SETTING_COMMENT' => 'What level of diagnostics to send (trace / error / warning / notice / debug)', 'RESTRICTED' => true ),
		array( 'CATEGORY' => 'api', 'SETTING' => 'FUELCARD_LOG_FILE', 
			'THE_VALUE' => 'log/fuel_card.txt', 'SETTING_COMMENT' => 'Fuel card log file.', 'RESTRICTED' => true ),

		//! Driver Manifest
		array( 'CATEGORY' => 'option', 'SETTING' => 'DRMANIFEST_ENABLED', 
			'THE_VALUE' => 'false', 'SETTING_COMMENT' => 'Enable driver manifest (true / false)', 'RESTRICTED' => true ),
		array( 'CATEGORY' => 'email', 'SETTING' => 'EMAIL_DRMANIFEST_CC', 
			'THE_VALUE' => '', 'SETTING_COMMENT' => 'Cc: recipient for driver manifest.' ),
		array( 'CATEGORY' => 'email', 'SETTING' => 'EMAIL_DRMANIFEST_TEMPLATE', 
			'THE_VALUE' => 'templates/manifest2.txt', 'SETTING_COMMENT' => 'path to text file, the driver manifest template.',
			'RESTRICTED' => true ),

		array( 'CATEGORY' => 'email', 'SETTING' => 'EMAIL_DIAG_ADDRESS', 
			'THE_VALUE' => 'support@exspeedite.com', 'SETTING_COMMENT' => 'Email to send diagnostics to.',
			'RESTRICTED' => true ),
		array( 'CATEGORY' => 'email', 'SETTING' => 'EMAIL_DIAG_LEVEL', 
			'THE_VALUE' => 'error', 'SETTING_COMMENT' => 'What level of diagnostics to send (trace / error / warning / notice / debug)', 'RESTRICTED' => true ),
		array( 'CATEGORY' => 'email', 'SETTING' => 'EMAIL_QUICKBOOKS_ALERT', 
			'THE_VALUE' => 'support@exspeedite.com', 'SETTING_COMMENT' => 'Email to alert for quickbooks connection issues.', 'RESTRICTED' => true ),

		array( 'CATEGORY' => 'api', 'SETTING' => 'QUICKBOOKS_DIAG_LEVEL', 
			'THE_VALUE' => 'error', 'SETTING_COMMENT' => 'What level of diagnostics to send (trace / error / warning / notice / debug)', 'RESTRICTED' => true ),
		array( 'CATEGORY' => 'api', 'SETTING' => 'EDI_ENABLED', 
			'THE_VALUE' => 'false', 'SETTING_COMMENT' => 'Choose to enable EDI (true / false).',
			'RESTRICTED' => true ),
		array( 'CATEGORY' => 'api', 'SETTING' => 'EDI_DIAG_LEVEL', 
			'THE_VALUE' => 'error', 'SETTING_COMMENT' => 'What level of diagnostics to send (trace / error / warning / notice / debug)', 'RESTRICTED' => true ),
		array( 'CATEGORY' => 'api', 'SETTING' => 'EDI_LOG_FILE', 
			'THE_VALUE' => 'log/edi.txt', 'SETTING_COMMENT' => 'EDI log file.', 'RESTRICTED' => true ),
		array( 'CATEGORY' => 'api', 'SETTING' => 'IFTA_ENABLED', 
			'THE_VALUE' => 'false', 'SETTING_COMMENT' => 'Choose to enable IFTA Fuel Reporting (true / false).',
			'RESTRICTED' => true ),
			
		//! SCR# 683 - PC*Miler routing options
		array( 'CATEGORY' => 'api', 'SETTING' => 'PCM_ROUTE_OPTIMIZE', 
			'THE_VALUE' => 'None', 'SETTING_COMMENT' => 'PC*Miler RouteOptimizeType (None / ThruAll / DestinationFixed).' ),
		array( 'CATEGORY' => 'api', 'SETTING' => 'PCM_ROUTING', 
			'THE_VALUE' => 'Practical', 'SETTING_COMMENT' => 'PC*Miler RoutingType (Practical / Shortest / Fastest).' ),
		array( 'CATEGORY' => 'api', 'SETTING' => 'PCM_CLASS_OVERRIDE', 
			'THE_VALUE' => 'NationalNetwork', 'SETTING_COMMENT' => 'PC*Miler ClassOverrideType (None / FiftyThreeFoot / NationalNetwork).' ),
		array( 'CATEGORY' => 'api', 'SETTING' => 'PCM_BORDERS_OPEN', 
			'THE_VALUE' => 'true', 'SETTING_COMMENT' => 'PC*Miler borders open (true / false).' ),
		array( 'CATEGORY' => 'api', 'SETTING' => 'PCM_HIGHWAY_ONLY', 
			'THE_VALUE' => 'true', 'SETTING_COMMENT' => 'PC*Miler highway only (true / false).' ),
			
			
		array( 'CATEGORY' => 'api', 'SETTING' => 'PCM_LOG_IFTA_MILES', 
			'THE_VALUE' => 'false', 'SETTING_COMMENT' => 'Use PC*Miler to log miles for completed loads (true / false).', 'RESTRICTED' => true ),
		array( 'CATEGORY' => 'api', 'SETTING' => 'IFTA_LOG_FILE', 
			'THE_VALUE' => 'log/ifta.txt', 'SETTING_COMMENT' => 'IFTA log file.', 'RESTRICTED' => true ),
		array( 'CATEGORY' => 'api', 'SETTING' => 'IFTA_DIAG_LEVEL', 
			'THE_VALUE' => 'error', 'SETTING_COMMENT' => 'What level of diagnostics to send (trace / error / warning / notice / debug)', 'RESTRICTED' => true ),
		array( 'CATEGORY' => 'api', 'SETTING' => 'IFTA_BASE_JURISDICTION', 
			'THE_VALUE' => 'MN', 'SETTING_COMMENT' => 'Two letter state / province for IFTA base jurisdiction.' ),
		array( 'CATEGORY' => 'api', 'SETTING' => 'GOOGLE_API_KEY', 
			'THE_VALUE' => '', 'SETTING_COMMENT' => 'Google API Key - for distance calc, maps.', 'HIDDEN' => true ),
		array( 'CATEGORY' => 'api', 'SETTING' => 'KEEPTRUCKIN_KEY', 
			'THE_VALUE' => '', 'SETTING_COMMENT' => 'API key for keeptruckin.com, blank = disabled.' ),

		array( 'CATEGORY' => 'api', 'SETTING' => 'KEEPTRUCKIN_CLIENT_ID', 
			'THE_VALUE' => '1ad655a14d398205d4ad460770047367fca6a3ffae0b7d94b36ac9f2350043dc',
			'SETTING_COMMENT' => 'CLient ID for keeptruckin.com OAuth 2.0, do not change.',
			'RESTRICTED' => true ),
		array( 'CATEGORY' => 'api', 'SETTING' => 'KEEPTRUCKIN_SECRET', 
			'THE_VALUE' => '31f9d640a86fc51e9dd32a6ee683c34939188488f2d6509ed68776fe8b79ffe1',
			'SETTING_COMMENT' => 'Secret for keeptruckin.com OAuth 2.0, do not change.',
			'RESTRICTED' => true ),
		array( 'CATEGORY' => 'api', 'SETTING' => 'KEEPTRUCKIN_AUTHCODE', 
			'THE_VALUE' => '',
			'SETTING_COMMENT' => 'Authentication code for keeptruckin.com OAuth 2.0, set automatically.',
			'RESTRICTED' => true ),

		array( 'CATEGORY' => 'api', 'SETTING' => 'KEEPTRUCKIN_TOKENTYPE', 
			'THE_VALUE' => '',
			'SETTING_COMMENT' => 'Authentication code for keeptruckin.com OAuth 2.0, set automatically.',
			'RESTRICTED' => true ),
		array( 'CATEGORY' => 'api', 'SETTING' => 'KEEPTRUCKIN_ACCESSTOKEN', 
			'THE_VALUE' => '',
			'SETTING_COMMENT' => 'Authentication code for keeptruckin.com OAuth 2.0, set automatically.',
			'RESTRICTED' => true ),
		array( 'CATEGORY' => 'api', 'SETTING' => 'KEEPTRUCKIN_REFRESHTOKEN', 
			'THE_VALUE' => '',
			'SETTING_COMMENT' => 'Authentication code for keeptruckin.com OAuth 2.0, set automatically.',
			'RESTRICTED' => true ),

		//! Obsolete PCM
		array( 'CATEGORY' => 'api', 'SETTING' => 'PCM_USER', 
			'THE_VALUE' => '**REMOVE**', 'SETTING_COMMENT' => 'User name for PC*Miler.' ),
		array( 'CATEGORY' => 'api', 'SETTING' => 'PCM_PASSWORD', 
			'THE_VALUE' => '**REMOVE**', 'SETTING_COMMENT' => 'Password for PC*Miler.' ),
		array( 'CATEGORY' => 'api', 'SETTING' => 'PCM_ACCOUNT', 
			'THE_VALUE' => '**REMOVE**', 'SETTING_COMMENT' => 'Account for PC*Miler.' ),

		//! New PCM API Key
		array( 'CATEGORY' => 'api', 'SETTING' => 'PCM_API_KEY', 
			'THE_VALUE' => '', 'SETTING_COMMENT' => 'API Key for PC*Miler.', 'HIDDEN' => true ),
			
		array( 'CATEGORY' => 'api', 'SETTING' => 'GEO_CACHE_DURATION', 
			'THE_VALUE' => '30', 'SETTING_COMMENT' => 'How many days to cache results from Google or PC*Miler, in days', 'RESTRICTED' => true ),


		array( 'CATEGORY' => 'api', 'SETTING' => 'SYNERGY_IMPORT_DIR', 
			'THE_VALUE' => 'c: / exspeedite / import / ', 'SETTING_COMMENT' => 'Directory where Synergy files are to be imported from.', 'RESTRICTED' => true ),
		array( 'CATEGORY' => 'api', 'SETTING' => 'SYNERGY_LOG_FILE', 
			'THE_VALUE' => 'log/synergy.txt', 'SETTING_COMMENT' => 'Synergy log file.', 'RESTRICTED' => true ),
		array( 'CATEGORY' => 'api', 'SETTING' => 'QUICKBOOKS_COMPANY_FILE', 
			'THE_VALUE' => 'C:\Users\Public\Documents\Intuit\Quickbooks\Company Files\Exspeedite.qbw', 'SETTING_COMMENT' => 'The path to where your company file is stored.' ),
		array( 'CATEGORY' => 'api', 'SETTING' => 'QUICKBOOKS_LOG_FILE', 
			'THE_VALUE' => 'c: / exspeedite / quickbooks-php-master / data / duncan.txt', 'SETTING_COMMENT' => 'The path to a log file.<br>Set to empty to turn off logging.', 'RESTRICTED' => true ),
		array( 'CATEGORY' => 'api', 'SETTING' => 'QUICKBOOKS_INVOICE_TERMS', 
			'THE_VALUE' => 'NET 30', 'SETTING_COMMENT' => 'Default terms for customer invoices.' ),
		array( 'CATEGORY' => 'api', 'SETTING' => 'QUICKBOOKS_INVOICE_PREFIX', 
			'THE_VALUE' => 'Ex', 'SETTING_COMMENT' => 'Prefix added to invoice numbers.' ),
		array( 'CATEGORY' => 'api', 'SETTING' => 'QUICKBOOKS_DRIVER_SUFFIX', 
			'THE_VALUE' => '(driver)', 'SETTING_COMMENT' => 'Suffix added to driver names.' ),
		array( 'CATEGORY' => 'api', 'SETTING' => 'QUICKBOOKS_CARRIER_SUFFIX', 
			'THE_VALUE' => '(carrier)', 'SETTING_COMMENT' => 'Suffix added to carrier names.' ),
		array( 'CATEGORY' => 'api', 'SETTING' => 'QUICKBOOKS_BILL_PREFIX', 
			'THE_VALUE' => 'EXB', 'SETTING_COMMENT' => 'Prefix added to bill numbers.' ),
		array( 'CATEGORY' => 'api', 'SETTING' => 'QUICKBOOKS_DRIVER_BILL_PREFIX', 
			'THE_VALUE' => 'EXD', 'SETTING_COMMENT' => 'Prefix added to driver bill numbers.' ),
		array( 'CATEGORY' => 'api', 'SETTING' => 'QUICKBOOKS_BILL_TERMS', 
			'THE_VALUE' => 'NET 30', 'SETTING_COMMENT' => 'Default terms for carrier bills.' ),
		array( 'CATEGORY' => 'api', 'SETTING' => 'QUICKBOOKS_MAX_RETRIES', 
			'THE_VALUE' => '3', 'SETTING_COMMENT' => 'How many times to retry sending custom fields.' ),
		array( 'CATEGORY' => 'api', 'SETTING' => 'QUICKBOOKS_CLASS', 
			'THE_VALUE' => '', 'SETTING_COMMENT' => 'Which class to use (empty = no class).' ),
		array( 'CATEGORY' => 'api', 'SETTING' => 'QUICKBOOKS_DETAIL', 
			'THE_VALUE' => 'false', 'SETTING_COMMENT' => 'Choose to export invoice detail lines or not to QuickBooks or Sage50 (true / false).' ),
		array( 'CATEGORY' => 'api', 'SETTING' => 'QUICKBOOKS_TRANSACTION_DATE', 
			'THE_VALUE' => 'shipped', 'SETTING_COMMENT' => 'Which date for the transaction on invoices and billing (shipped / delivered).' ),
		array( 'CATEGORY' => 'api', 'SETTING' => 'CONSOLIDATED_TRANSACTION_DATE', 
			'THE_VALUE' => 'earliest', 'SETTING_COMMENT' => 'Which date for the transaction on invoices and billing (earliest / latest / parent).' ),
		array( 'CATEGORY' => 'api', 'SETTING' => 'EXPORT_QUICKBOOKS', 
			'THE_VALUE' => 'true', 'SETTING_COMMENT' => 'Export to Quickbooks (true / false).',
			'RESTRICTED' => true ),

		//! Sage 50 Settings
		array( 'CATEGORY' => 'api', 'SETTING' => 'EXPORT_SAGE50_CSV', 
			'THE_VALUE' => 'false', 'SETTING_COMMENT' => 'Export to Sage 50 via CSV (true / false).',
			'RESTRICTED' => true ),
		array( 'CATEGORY' => 'api', 'SETTING' => 'SAGE50_TAX_TYPE', 
			'THE_VALUE' => '2', 'SETTING_COMMENT' => 'Tax type (exempt) used for invoices' ),
		array( 'CATEGORY' => 'api', 'SETTING' => 'SAGE50_INVOICE_TERMS', 
			'THE_VALUE' => 'NET 30', 'SETTING_COMMENT' => 'Default terms for customer invoices.' ),
		array( 'CATEGORY' => 'api', 'SETTING' => 'SAGE50_BILL_TERMS', 
			'THE_VALUE' => 'NET 30', 'SETTING_COMMENT' => 'Default terms for carrier bills.' ),
		array( 'CATEGORY' => 'api', 'SETTING' => 'SAGE50_DEFAULT_EXP', 
			'THE_VALUE' => '57500-00', 'SETTING_COMMENT' => 'Default Expense G / L account for carriers' ),


		array( 'CATEGORY' => 'api', 'SETTING' => 'QUICKBOOKS_ONLINE', 
			'THE_VALUE' => 'false', 'SETTING_COMMENT' => 'Use Quickbooks Online Edition (true / false).',
			'RESTRICTED' => true ),
		array( 'CATEGORY' => 'api', 'SETTING' => 'QUICKBOOKS_MULTI_COMPANY', 
			'THE_VALUE' => 'false', 'SETTING_COMMENT' => 'Use multiple QBOE in MULTI_COMPANY mode (true / false).',
			'RESTRICTED' => true ),
		array( 'CATEGORY' => 'api', 'SETTING' => 'QUICKBOOKS_MANUAL_SPAWN', 
			'THE_VALUE' => 'false', 'SETTING_COMMENT' => 'Quickbooks Online disable transfer (true / false)<br>Then use quickbooks-php-master / online / process_queue.php?debug.',
			'RESTRICTED' => true ),
		array( 'CATEGORY' => 'api', 'SETTING' => 'EDI_POLLING', 
			'THE_VALUE' => '10', 'SETTING_COMMENT' => 'Frequency in minutes.' ),
		array( 'CATEGORY' => 'api', 'SETTING' => 'EDI_LAST_CHECKED', 
			'THE_VALUE' => '0', 'SETTING_COMMENT' => 'Timestamp of last check.' ),
		array( 'CATEGORY' => 'api', 'SETTING' => 'QUICKBOOKS_SANDBOX', 
			'THE_VALUE' => 'false', 'SETTING_COMMENT' => 'Development only, for Quickbooks Online Edition (true / false).' ),

		array( 'CATEGORY' => 'api', 'SETTING' => 'BIRT_REPORTS_PATH', 
			'THE_VALUE' => 'http:COMMstrongtco.dyndns.org:2380 / Birt / frameset?__report=report / ', 'SETTING_COMMENT' => 'Path to BIRT reports.', 'RESTRICTED' => true ),

		//! Quickbooks Items & Custom Fields
		array( 'CATEGORY' => 'QuickBooks', 'SETTING' => 'EXP_COD_ITEM', 
			'THE_VALUE' => 'COD', 'SETTING_COMMENT' => 'QuickBooks Invoice Item for COD charge.' ),
		array( 'CATEGORY' => 'QuickBooks', 'SETTING' => 'EXP_EXTRA_ITEM', 
			'THE_VALUE' => 'Extra', 'SETTING_COMMENT' => 'QuickBooks Invoice Item for Extra charge.' ),
		array( 'CATEGORY' => 'QuickBooks', 'SETTING' => 'EXP_FREIGHT_ITEM', 
			'THE_VALUE' => 'Freight', 'SETTING_COMMENT' => 'QuickBooks Invoice Item for Freight charge.' ),
		array( 'CATEGORY' => 'QuickBooks', 'SETTING' => 'EXP_SURCHARGE_ITEM', 
			'THE_VALUE' => 'Fuel_Surcharge', 'SETTING_COMMENT' => 'QuickBooks Invoice Item for Fuel Surcharge.' ),
		array( 'CATEGORY' => 'QuickBooks', 'SETTING' => 'EXP_HANDLING_ITEM', 
			'THE_VALUE' => 'Handling', 'SETTING_COMMENT' => 'QuickBooks Invoice Item for Handling charge.' ),
		array( 'CATEGORY' => 'QuickBooks', 'SETTING' => 'EXP_HANDLING_PALLET_ITEM', 
			'THE_VALUE' => 'Handling', 'SETTING_COMMENT' => 'QuickBooks Invoice Item for Pallet Handling charge.' ),
		array( 'CATEGORY' => 'QuickBooks', 'SETTING' => 'EXP_LOADING_ITEM', 
			'THE_VALUE' => 'Loading', 'SETTING_COMMENT' => 'QuickBooks Invoice Item for Loading charge.' ),
		array( 'CATEGORY' => 'QuickBooks', 'SETTING' => 'EXP_MILEAGE_ITEM', 
			'THE_VALUE' => 'Mileage', 'SETTING_COMMENT' => 'QuickBooks Invoice Item for Mileage charge.' ),
		array( 'CATEGORY' => 'QuickBooks', 'SETTING' => 'EXP_OTHER_ITEM', 
			'THE_VALUE' => 'Other', 'SETTING_COMMENT' => 'QuickBooks Invoice Item for Other charge.' ),
		array( 'CATEGORY' => 'QuickBooks', 'SETTING' => 'EXP_PALLETS_ITEM', 
			'THE_VALUE' => 'Freight', 'SETTING_COMMENT' => 'QuickBooks Invoice Item for Pallets charge.' ),
		array( 'CATEGORY' => 'QuickBooks', 'SETTING' => 'EXP_STOPOFF_ITEM', 
			'THE_VALUE' => 'Stopoff', 'SETTING_COMMENT' => 'QuickBooks Invoice Item for Stopoff charge.' ),
		array( 'CATEGORY' => 'QuickBooks', 'SETTING' => 'EXP_UNLOADING_ITEM', 
			'THE_VALUE' => 'Unloading', 'SETTING_COMMENT' => 'QuickBooks Invoice Item for Unloading charge.' ),
		array( 'CATEGORY' => 'QuickBooks', 'SETTING' => 'EXP_SELECTION_FEE_ITEM', 
			'THE_VALUE' => 'Selection Fee', 'SETTING_COMMENT' => 'QuickBooks Invoice Item for Selection Fee.' ),
		array( 'CATEGORY' => 'QuickBooks', 'SETTING' => 'EXP_DISCOUNT_ITEM', 
			'THE_VALUE' => 'Discount', 'SETTING_COMMENT' => 'QuickBooks Invoice Item for Discount.' ),

		array( 'CATEGORY' => 'QuickBooks', 'SETTING' => 'EXP_CARRIER_FREIGHT_ITEM', 
			'THE_VALUE' => 'Carrier Freight', 'SETTING_COMMENT' => 'QuickBooks Bill Item for Carrier Freight.' ),
		array( 'CATEGORY' => 'QuickBooks', 'SETTING' => 'EXP_CARRIER_FSC_ITEM', 
			'THE_VALUE' => 'Carrier FSC', 'SETTING_COMMENT' => 'QuickBooks Bill Item for Carrier FSC.' ),
		array( 'CATEGORY' => 'QuickBooks', 'SETTING' => 'EXP_CARRIER_HANDLING_ITEM', 
			'THE_VALUE' => 'Carrier Handling', 'SETTING_COMMENT' => 'QuickBooks Bill Item for Carrier Handling.' ),

		array( 'CATEGORY' => 'QuickBooks', 'SETTING' => 'EXP_DRIVER_TRIP_PAY_ITEM', 
			'THE_VALUE' => 'Driver Trip Pay', 'SETTING_COMMENT' => 'QuickBooks Bill Item for Driver Trip Pay.' ),
		array( 'CATEGORY' => 'QuickBooks', 'SETTING' => 'EXP_DRIVER_BONUS_ITEM', 
			'THE_VALUE' => 'Driver Bonus', 'SETTING_COMMENT' => 'QuickBooks Bill Item for Driver Bonus.' ),
		array( 'CATEGORY' => 'QuickBooks', 'SETTING' => 'EXP_DRIVER_HANDLING_ITEM', 
			'THE_VALUE' => 'Driver Handling', 'SETTING_COMMENT' => 'QuickBooks Bill Item for Driver Handling.' ),

		array( 'CATEGORY' => 'QuickBooks', 'SETTING' => 'EXP_SHIPPER_NAME', 
			'THE_VALUE' => 'SHIPPER NAME', 'SETTING_COMMENT' => 'QuickBooks Invoice Custom Field for Shipper Name.' ),
		array( 'CATEGORY' => 'QuickBooks', 'SETTING' => 'EXP_SHIPPER_ADDR1', 
			'THE_VALUE' => 'ADDRESS', 'SETTING_COMMENT' => 'QuickBooks Invoice Custom Field for Shipper Address line 1.' ),
		array( 'CATEGORY' => 'QuickBooks', 'SETTING' => 'EXP_SHIPPER_CITY', 
			'THE_VALUE' => 'CITY STATE ZIP', 'SETTING_COMMENT' => 'QuickBooks Invoice Custom Field for Shipper City, State, Zip.' ),
		array( 'CATEGORY' => 'QuickBooks', 'SETTING' => 'EXP_BOL_NUMBER', 
			'THE_VALUE' => 'BOL', 'SETTING_COMMENT' => 'QuickBooks Invoice Custom Field for BOL.' ),
		array( 'CATEGORY' => 'QuickBooks', 'SETTING' => 'EXP_TRIP_NUMBER', 
			'THE_VALUE' => 'TRIP', 'SETTING_COMMENT' => 'QuickBooks Invoice Custom Field for Load / Trip#.' ),
		array( 'CATEGORY' => 'QuickBooks', 'SETTING' => 'EXP_FS_NUMBER', 
			'THE_VALUE' => 'FS NUMBER', 'SETTING_COMMENT' => 'QuickBooks Invoice Custom Field for FS#.' ),

		//! SCR# 257 - Handle exempt tax code
		array( 'CATEGORY' => 'QuickBooks', 'SETTING' => 'QBOE_EXEMPT_TAX', 
			'THE_VALUE' => 'Exempt', 'SETTING_COMMENT' => 'QuickBooks tax code for Tax-exempt.' ),

		array( 'CATEGORY' => 'company', 'SETTING' => 'NAME', 
			'THE_VALUE' => '', 'SETTING_COMMENT' => 'Company Name - for reports' ),
		array( 'CATEGORY' => 'company', 'SETTING' => 'LOGO', 
			'THE_VALUE' => '', 'SETTING_COMMENT' => 'Company Logo - for reports' ),
		array( 'CATEGORY' => 'company', 'SETTING' => 'ADDRESS_1', 
			'THE_VALUE' => '', 'SETTING_COMMENT' => 'Address line 1 - for reports' ),
		array( 'CATEGORY' => 'company', 'SETTING' => 'ADDRESS_2', 
			'THE_VALUE' => '', 'SETTING_COMMENT' => 'Address line 2 - for reports' ),
		array( 'CATEGORY' => 'company', 'SETTING' => 'CITY', 
			'THE_VALUE' => '', 'SETTING_COMMENT' => 'City - for reports' ),
		array( 'CATEGORY' => 'company', 'SETTING' => 'STATE', 
			'THE_VALUE' => '', 'SETTING_COMMENT' => 'State - for reports' ),
		array( 'CATEGORY' => 'company', 'SETTING' => 'ZIP', 
			'THE_VALUE' => '', 'SETTING_COMMENT' => 'ZIP - for reports' ),
		array( 'CATEGORY' => 'company', 'SETTING' => 'COUNTRY', 
			'THE_VALUE' => '', 'SETTING_COMMENT' => 'COUNTRY - for reports' ),
		array( 'CATEGORY' => 'company', 'SETTING' => 'BUSINESS_PHONE', 
			'THE_VALUE' => '', 'SETTING_COMMENT' => 'Business Phone - for reports' ),
		array( 'CATEGORY' => 'company', 'SETTING' => 'EMERGENCY_PHONE', 
			'THE_VALUE' => '', 'SETTING_COMMENT' => 'Emergency / After Hours Phone - for reports' ),
		array( 'CATEGORY' => 'company', 'SETTING' => 'DUNS_ID', 
			'THE_VALUE' => '', 'SETTING_COMMENT' => 'DUNS_ID - for reports' ),
		array( 'CATEGORY' => 'company', 'SETTING' => 'EMAIL', 
			'THE_VALUE' => '', 'SETTING_COMMENT' => 'EMAIL - for reports' ),
		array( 'CATEGORY' => 'company', 'SETTING' => 'WEB_URL', 
			'THE_VALUE' => '', 'SETTING_COMMENT' => 'WEB_URL - for reports' ),
		array( 'CATEGORY' => 'company', 'SETTING' => 'FAX_PHONE', 
			'THE_VALUE' => '', 'SETTING_COMMENT' => 'FAX_PHONE - for reports' ),
		array( 'CATEGORY' => 'company', 'SETTING' => 'FED_ID_NUM', 
			'THE_VALUE' => '', 'SETTING_COMMENT' => 'FED_ID_NUM - for reports' ),

		array( 'CATEGORY' => 'company', 'SETTING' => 'REMIT_NAME', 
			'THE_VALUE' => '', 'SETTING_COMMENT' => 'Remit-to Name - for invoices' ),
		array( 'CATEGORY' => 'company', 'SETTING' => 'REMIT_ADDRESS_1', 
			'THE_VALUE' => '', 'SETTING_COMMENT' => 'Remit-to Address line 1 - for invoices' ),
		array( 'CATEGORY' => 'company', 'SETTING' => 'REMIT_ADDRESS_2', 
			'THE_VALUE' => '', 'SETTING_COMMENT' => 'Remit-to Address line 2 - for invoices' ),
		array( 'CATEGORY' => 'company', 'SETTING' => 'REMIT_CITY', 
			'THE_VALUE' => '', 'SETTING_COMMENT' => 'Remit-to City - for invoices' ),
		array( 'CATEGORY' => 'company', 'SETTING' => 'REMIT_STATE', 
			'THE_VALUE' => '', 'SETTING_COMMENT' => 'Remit-to State - for invoices' ),
		array( 'CATEGORY' => 'company', 'SETTING' => 'REMIT_ZIP', 
			'THE_VALUE' => '', 'SETTING_COMMENT' => 'Remit-to ZIP - for invoices' ),
		array( 'CATEGORY' => 'company', 'SETTING' => 'REMIT_COUNTRY', 
			'THE_VALUE' => '', 'SETTING_COMMENT' => 'Remit-to COUNTRY - for invoices' ),

		array( 'CATEGORY' => 'option', 'SETTING' => 'BACKUP_SESSION_FILES', 
			'THE_VALUE' => 'true', 'SETTING_COMMENT' => 'Backup session files in case they are lost (true / false)' ),

		array( 'CATEGORY' => 'option', 'SETTING' => 'LOGIN_BLOCK_AUTOCOMPLETE', 
			'THE_VALUE' => 'false', 'SETTING_COMMENT' => 'Block the login form from filling in usernames (true / false)' ),
			
		array( 'CATEGORY' => 'option', 'SETTING' => 'DB_LOGGING', 
			'THE_VALUE' => 'false', 'SETTING_COMMENT' => 'Enable logging of database calls (true / false)', 'RESTRICTED' => true ),

		//! SCR# 634 - Reuse empty loads
		array( 'CATEGORY' => 'option', 'SETTING' => 'REUSE_EMPTY_LOADS', 
			'THE_VALUE' => 'false', 'SETTING_COMMENT' => 'Reuse unused / empty loads rather than create an empty one where possible (true / false)' ),
			
	);
	
	// Constructor does not need the table name
	public function __construct( $database, $debug = false ) {

		$this->debug = $debug;
		$this->primary_key = "SETTING_CODE";
		if( $this->debug ) echo "<p>".__METHOD__.": Create sts_setting</p>";
		parent::__construct( $database, SETTING_TABLE, $debug);
		$this->set_defaults();
	}
	
	// Allow re-use of objects - singleton function
	public static function getInstance( $database, $debug = false ) {
		static $instance = null;
		$myclass = get_class ();
		if( $debug ) echo "<p>Get instance of $myclass</p>";
		if (null === $instance) {
			$instance = new $myclass( $database, $debug );
		}
		return $instance;
    }
    
    //! Deal with duplicate entries, although not sure how they got in there.
    // Should not be needed.
    public function remove_duplicates() {
		if( $this->debug ) echo "<p>".__METHOD__.": entry</p>";	
	    $this->database->get_multiple_rows("
	    	DELETE
			FROM EXP_SETTING USING EXP_SETTING,
			    EXP_SETTING S1
			WHERE EXP_SETTING.SETTING_CODE > S1.SETTING_CODE
			    AND EXP_SETTING.CATEGORY = S1.CATEGORY  
			    AND EXP_SETTING.SETTING = S1.SETTING");
    }

	public function get( $category, $setting = "" ) {
		if( $this->debug ) echo "<p>".__METHOD__.": $category, $setting</p>";
		$result = false;
		
		if( $setting <> '' && ($cached = $this->cache->get_setting( $category, $setting )) !== false ) {
			$result = $cached;
		} else {
			$check = $this->fetch_rows( "CATEGORY = '".$category."'".
				($setting <> "" ? " AND SETTING = '".$setting."'" : ""),
				"SETTING, THE_VALUE, SETTING_COMMENT, SETTING_CODE", "SETTING_CODE DESC" );
	
			if( is_array($check) && count($check) > 0 ) {
				if( $setting == "" ) {
					$result = $check;
				} else {
					$result = $check[0]['THE_VALUE'];
				}
				
			} else
			// If not found, check if there is a new setting, not yet added.
			if( $setting <> "" && ! (is_array($check) && count($check) == 1) ) {
				if( $this->debug ) echo "<p>".__METHOD__.": check for missing</p>";
				foreach( $this->default_settings as $default_setting ) {
					if( $category == $default_setting['CATEGORY'] &&
						$setting == $default_setting['SETTING']  &&
						$default_setting['THE_VALUE'] <> '**REMOVE**') {
						if( $this->debug ) echo "<p>".__METHOD__.": found missing</p>";
						$add_result = $this->add( $default_setting );
						$result = $default_setting['THE_VALUE'];
						break;			
					}
				}
			}
		}
		if( $this->debug ) echo "<p>".__METHOD__.": return <pre>".print_r($result,true)."</pre></p>";
		return $result;
	}
	
	public function set( $category, $setting, $value ) {
		if( $this->debug ) echo "<p>".__METHOD__.": $category, $setting, $value</p>";
		$result =  $this->update( "CATEGORY = '".$category."' AND SETTING = '".$setting."'",
			array("THE_VALUE" => $value) );
			
		if( $result )
			$this->write_cache();
			
		if( $category == 'main' AND $setting == 'LAST_CHECKED')
			unset($_SESSION["LAST_CHECKED"]);
						
		if( $this->debug ) echo "<p>".__METHOD__.": return ".($result ? 'OK' : 'false')."</p>";
		return $result;
	}
	
	public function url_prefix() {
		$prefix = '';
		if( isset($_SERVER) && isset($_SERVER["HTTP_HOST"]) && $_SERVER["HTTP_HOST"] <> '' &&
			isset($_SERVER['CONTEXT_PREFIX'])) {
			$prefix = (empty($_SERVER['HTTPS']) ? 'http://' : 'https://').$_SERVER["HTTP_HOST"].$_SERVER['CONTEXT_PREFIX'];
			if( substr($prefix, -1) <> '/' )	// Make sure it ends with a slash
				$prefix .= '/';
		}
		return $prefix;
	}
	
	//! SCR# 429 - only set it if not already set
	public function set_url_prefix() {
		$url_prefix = $this->get('main', 'URL_PREFIX');	// What is currently set
		$prefix = $this->url_prefix();					// Value to set it to
		if( empty($url_prefix) && ! empty($prefix) ) {
			$this->update_row( "CATEGORY = 'main' AND SETTING = 'URL_PREFIX'",
				array( 
					array("field" => "THE_VALUE", "value" => $this->enquote_string( "THE_VALUE", $this->real_escape_string( (string) $prefix) ) )
				) );

		}
	}
	
	private function set_defaults() {
		global $sts_release_name, $sts_schema_release_name;
		
		if( ! isset($_SESSION["LAST_CHECKED"]) ||
			$_SESSION["LAST_CHECKED"] == false ) {
			//! Only do a full check once per release
			$check = $this->fetch_rows("CATEGORY = 'main' AND SETTING = 'LAST_CHECKED'", "THE_VALUE");
			if( isset($check) && is_array($check) && ( count($check) == 0 ||
				( count($check) > 0 && isset($check[0]["THE_VALUE"]) &&
					$check[0]["THE_VALUE"] <> $sts_release_name ) )
			) {
				
				$schema = sts_schema_version::getInstance($this->database, $this->debug);
				
				if( $schema->version_ok( $sts_schema_release_name ) ) {
		
					$this->delete_row( "CATEGORY = 'main' AND SETTING = 'LAST_CHECKED'" );
					$this->add( array(
						'CATEGORY' => 'main', 'SETTING' => 'LAST_CHECKED',
						'THE_VALUE' => $sts_release_name, 'SETTING_COMMENT' => 'Last release this was checked.',
						'RESTRICTED' => true
					));
					
					if( $this->debug ) echo "<p>".__METHOD__.": need to check/update settings</p>";
					$result = $this->fetch_rows("", "CATEGORY, SETTING, SETTING_COMMENT, RESTRICTED, HIDDEN");
					
					
					foreach( $this->default_settings as $setting ) {
						$found = false;
						if( $result && is_array($result) ) {
							foreach( $result as $row ) {
								if( $row['CATEGORY'] == $setting['CATEGORY'] &&
									$row['SETTING'] == $setting['SETTING'] ) {
									$found = true;
									break;
								}
							}
						}
								
						if( $found && ($row['SETTING_COMMENT'] <> $setting['SETTING_COMMENT'] ||
							(isset($setting['RESTRICTED']) && $row['RESTRICTED'] <> $setting['RESTRICTED']) ||
							(isset($setting['HIDDEN']) && $row['HIDDEN'] <> $setting['HIDDEN']) )
							) {
								
							$changes = array( 
									array("field" => "SETTING_COMMENT", "value" => $this->enquote_string( "SETTING_COMMENT", $this->real_escape_string( (string) $setting['SETTING_COMMENT']) ) ) );
							if( isset($setting['RESTRICTED']) )
								$changes[] = array("field" => "RESTRICTED", "value" => $setting['RESTRICTED'] );
							if( isset($setting['HIDDEN']) )
								$changes[] = array("field" => "HIDDEN", "value" => $setting['HIDDEN'] );
							$this->update_row( "CATEGORY = '".$setting['CATEGORY']."'".
								" AND SETTING = '".$setting['SETTING']."'", $changes );
						}
						
						//! Remove obsolete settings
						if( $found && $setting['THE_VALUE'] == '**REMOVE**' ) {
							//echo '<p>delete '."CATEGORY = '".$setting['CATEGORY']."'".
							//	" AND SETTING = '".$setting['SETTING']."'".'</p>';
							$this->delete_row("CATEGORY = '".$setting['CATEGORY']."'".
								" AND SETTING = '".$setting['SETTING']."'");
						}
	
						if( ! $found && $setting['THE_VALUE'] <> '**REMOVE**' ) {
							//echo '<p>add '."CATEGORY = '".$setting['CATEGORY']."'".
							//	" AND SETTING = '".$setting['SETTING']."'".'</p>';
							$add_result = $this->add( $setting );				
						}
	
					}
					sleep(15);
					$this->set_url_prefix();
					
					if( $this->debug ) echo "<p>".__METHOD__.": need to refresh cache</p>";
					$this->write_cache();
					
				} else {
					if( $this->debug ) echo "<p>".__METHOD__.": need to CHECK_SCHEMA.</p>";
					
					if( ! defined('_STS_CHECK_SCHEMA') ) {
						echo '<h2><img src="images/EXSPEEDITEsmr.png" alt="EXSPEEDITEsmr" width="284" height="50" /> Schema version is out of date.</h2>
						<p>Database version = '.$schema->get_version().'</p>
						<p>It should be '.$sts_schema_release_name.'</p>
						<p>This may be due to an update in progress. If so, please wait a couple of minutes and try again.</p>
						<p>Otherwise, please have an admin run CHECK_SCHEMA before continuing.</p>';
						die;
					}
				}
				$this->write_cache();
			} else {
				if( $this->debug ) echo "<p>".__METHOD__.": no need to check further.</p>";
			}
			$_SESSION["LAST_CHECKED"] = true;
		}
	}
	
	public function get_categories() {
		$category = false;
		$result = $this->fetch_rows("", "DISTINCT CATEGORY", "CATEGORY ASC");
		if( isset($result) && is_array($result) && count($result) > 0 ) {
			$category = array();
			foreach( $result as $row ) {
				$category[] = $row["CATEGORY"];
			}
		}
		return $category;
	}

	public function setting_menu( $category, $setting, $value = false ) {
		if( $this->debug ) echo "<p>".__METHOD__.": entry, $category, $setting</p>";
		
		$output = '';

		$check = $this->fetch_rows( "CATEGORY = '".$category."'".
			" AND SETTING = '".$setting."'",
			"SETTING, THE_VALUE, SETTING_COMMENT" );

		if( is_array($check) && count($check) == 1 ) {
			if( isset($check[0]["SETTING_COMMENT"]) &&
				preg_match('/\(([^\)]+)\)/', $check[0]["SETTING_COMMENT"], $matches) ) {
					 
				if( $matches[0] != '(mm/dd/yyyy)') {
					$choices = explode(' / ', $matches[1]);
					if( $this->debug ) {
						echo "<pre>".__METHOD__.": match, choices\n";
						var_dump($matches[1], $choices);
						echo "</pre>";
					}

					if(count($choices) > 1 ) {
						$selection = $check[0]["THE_VALUE"];
						if( $value != false && in_array($value, $choices) )
							$selection = $value;

						$output .= '<select class="form-control input-sm" name="'.$setting.'"
							id="'.$setting.'" >';
						foreach( $choices as $choice ) {
							$output .= '
								<option value="'.$choice.'" '.
									($choice==$selection ? 'selected' : '').
									'>'.$choice.'</option>';
						}
						$output .= '
						</select>';
					}
				}
			}
		}
			
		return $output;
	}
		
}

//! Schema Version Number class
//- This table is updated by repair_db / CHECK_SCHEMA as a static table.
// Here we only check the value
class sts_schema_version extends sts_table {

	// Constructor does not need the table name
	public function __construct( $database, $debug = false ) {

		$this->debug = $debug;
		$this->primary_key = "VERSION_NUMBER";
		if( $this->debug ) echo "<p>".__METHOD__.": Create sts_schema_version</p>";
		parent::__construct( $database, SCHEMA_TABLE, $debug);
	}
	
	// Allow re-use of objects - singleton function
	public static function getInstance( $database, $debug = false ) {
		static $instance = null;
		$myclass = get_class ();
		if( $debug ) echo "<p>".__METHOD__.": Get instance of $myclass</p>";
		if (null === $instance) {
			$instance = new $myclass( $database, $debug );
		}
		return $instance;
    }
    
	public function version_ok( $version ) {
	    $result = false;
	    $check = $this->fetch_rows();
	    if( is_array($check) && count($check) == 1 &&
	    	isset($check[0]["VERSION_NUMBER"]) && $check[0]["VERSION_NUMBER"] == $version ) {
	    	$result = true;
    	}
    	return $result;
    }
    
    public function get_version() {
	    $result = false;
	    $check = $this->fetch_rows();
	     if( is_array($check) && count($check) == 1 &&
	    	isset($check[0]["VERSION_NUMBER"]) )
	    	$result = $check[0]["VERSION_NUMBER"];
	    
    	return $result;
    }

	public function set_version( $version ) {
		$this->delete_row("1 = 1"); // delete all rows
		$this->add( array( 'VERSION_NUMBER' => $version ) );
	}

}

//! Form Specifications - For use with sts_form

$sts_form_addsetting_form = array(	//! sts_form_addsetting_form
	'title' => '<img src="images/setting_icon.png" alt="setting_icon" height="24"> Add Setting',
	'action' => 'exp_addsetting.php',
	//'actionextras' => 'disabled',
	'cancel' => 'exp_listsetting.php',
	'name' => 'addsetting',
	'okbutton' => 'Save Changes',
	'saveadd' => 'Add Another',
	'cancelbutton' => 'Cancel',
		'layout' => '
	<div class="form-group">
		<div class="col-sm-6">
			<div class="form-group">
				<label for="CATEGORY" class="col-sm-4 control-label">#CATEGORY#</label>
				<div class="col-sm-8">
					%CATEGORY%
				</div>
			</div>
			<div class="form-group">
				<label for="SETTING" class="col-sm-4 control-label">#SETTING#</label>
				<div class="col-sm-8">
					%SETTING%
				</div>
			</div>
			<div class="form-group">
				<label for="THE_VALUE" class="col-sm-4 control-label">#THE_VALUE#</label>
				<div class="col-sm-8">
					%THE_VALUE%
				</div>
			</div>
			<div class="form-group">
				<label for="SETTING_COMMENT" class="col-sm-4 control-label">#SETTING_COMMENT#</label>
				<div class="col-sm-8">
					%SETTING_COMMENT%
				</div>
			</div>
		</div>
		<div class="col-sm-6">
		</div>
	</div>
	
	'
);

$sts_form_editsetting_form = array(
	'title' => '<img src="images/setting_icon.png" alt="setting_icon" height="24"> Edit Setting',
	'action' => 'exp_editsetting.php',
	//'actionextras' => 'disabled',
	'cancel' => 'exp_listsetting.php',
	'name' => 'editsetting',
	'okbutton' => 'Save Changes to Setting',
	'cancelbutton' => 'Back to Settings',
		'layout' => '
		%SETTING_CODE%
	<div class="form-group">
		<div class="col-sm-8">
			<div class="form-group">
				<label for="CATEGORY" class="col-sm-4 control-label">#CATEGORY#</label>
				<div class="col-sm-4">
					%CATEGORY%
				</div>
			</div>
			<div class="form-group">
				<label for="SETTING" class="col-sm-4 control-label"><!-- RESTRICTED -->#SETTING#</label>
				<div class="col-sm-4">
					%SETTING%
				</div>
			</div>
			<div class="form-group">
				<label for="THE_VALUE" class="col-sm-4 control-label">#THE_VALUE#</label>
				<div class="col-sm-8">
					%THE_VALUE%
				</div>
			</div>
			<div class="form-group">
				<label for="SETTING_COMMENT" class="col-sm-4 control-label">#SETTING_COMMENT#</label>
				<div class="col-sm-8">
					%SETTING_COMMENT%
				</div>
			</div>
		</div>
		<div class="col-sm-6">
		</div>
	</div>
	
	'
);

//! Field Specifications - For use with sts_form

$sts_form_add_setting_fields = array(
	'CATEGORY' => array( 'label' => 'Category', 'format' => 'text' ),
	'SETTING' => array( 'label' => 'Setting', 'format' => 'text', 'extras' => 'autofocus' ),
	'THE_VALUE' => array( 'label' => 'Value', 'format' => 'text' ),
	'SETTING_COMMENT' => array( 'label' => 'Comment', 'format' => 'text' ),
);

$sts_form_edit_setting_fields = array(
	'SETTING_CODE' => array( 'format' => 'hidden' ),
	'CATEGORY' => array( 'label' => 'Category', 'format' => 'text', 'extras' => 'disabled' ),
	'SETTING' => array( 'label' => 'Setting', 'format' => 'text', 'extras' => 'disabled' ),
	'THE_VALUE' => array( 'label' => 'Value', 'format' => 'text', 'extras' => 'autofocus' ),
	'SETTING_COMMENT' => array( 'label' => 'Comment', 'format' => 'textarea', 'extras' => 'disabled' ),
	'RESTRICTED' => array( 'format' => 'hidden' ),
);

//! Layout Specifications - For use with sts_result

$sts_result_settings_layout = array(
	'SETTING_CODE' => array( 'format' => 'hidden' ),
	'CATEGORY' => array( 'label' => 'Category', 'format' => 'text' ),
	'RESTRICTED' => array( 'label' => 'R', 'format' => 'bool', 'align' => 'center' ),
	'SETTING' => array( 'label' => 'Setting', 'format' => 'text' ),
	'THE_VALUE' => array( 'label' => 'Value', 'format' => 'text',
		'snippet' => "(CASE WHEN HIDDEN THEN '*** HIDDEN ***' ELSE THE_VALUE END)"
	 ),
	'SETTING_COMMENT' => array( 'label' => 'Comment', 'format' => 'text' ),
	'CHANGED_DATE' => array( 'label' => 'Changed', 'format' => 'timestamp', 'length' => 120 ),
	'CHANGED_BY' => array( 'label' => 'By', 'format' => 'table',
		'table' => USER_TABLE, 'key' => 'USER_CODE', 'fields' => 'USERNAME', 'link' => 'exp_edituser.php?CODE=' )

);

//! Edit/Delete Button Specifications - For use with sts_result

$sts_result_settings_edit = array(
	'title' => '<img src="images/setting_icon.png" alt="setting_icon" height="24"> Settings',
	'sort' => 'CATEGORY asc, SETTING asc',
	'cancel' => 'index.php',
	//'add' => 'exp_addsetting.php',
	//'actionextras' => 'disabled',
	//'addbutton' => 'Add Setting',
	'cancelbutton' => 'Back',
	'rowbuttons' => array(
		array( 'url' => 'exp_editsetting.php?CODE=', 'key' => 'SETTING_CODE',
		'label' => 'SETTING', 'tip' => 'Edit setting ', 'icon' => 'glyphicon glyphicon-edit',
		'showif' => 'notrestricted' ),
	)
);

} else { //! Workaround - why did we include this more than once?
	if( isset($sts_debug) && $sts_debug ) {
		echo "<p>sts_setting_class.php: why did we include this more than once?</p>
		<p>List of included files:</p><pre>";
		var_dump(get_included_files());
		echo "</pre>";
	}	
	
}

?>
