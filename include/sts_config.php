<?php

// $Id: sts_config.php 5160 2023-07-12 19:07:00Z dev $
// Configuration file for Exspeedite - not for user modification

// no direct access
defined('_STS_INCLUDE') or die('Restricted access');

// Profiling
$sts_profiling = false;	// Keep this false, it can be turned on elsewhere as needed
require_once( "sts_timer_class.php" );

define( 'EXP_CONFIG_FILE', 'exspeedite_config.php' );

// Include path modifications (relative paths within library)
ini_set('include_path', ini_get('include_path') . PATH_SEPARATOR . dirname(__FILE__) . PATH_SEPARATOR . dirname(dirname(__FILE__)));

//! SCR# 788 - New config subdirectories
if( ! isset($sts_subdomain) ) {
	if(isset($_SERVER) && is_array($_SERVER) && isset($_SERVER["HTTP_HOST"]) )
		$sts_subdomain = explode('.', $_SERVER["HTTP_HOST"])[0];
	else
		$sts_subdomain = '';
}
if( !empty($sts_subdomain) &&
	file_exists( 'include'.$sts_subdomain.DIRECTORY_SEPARATOR.EXP_CONFIG_FILE ) ) {
	require_once( 'include'.$sts_subdomain.DIRECTORY_SEPARATOR.EXP_CONFIG_FILE );
} else if( !empty($sts_subdomain) &&
	file_exists( dirname(__FILE__).DIRECTORY_SEPARATOR.
		$sts_subdomain.DIRECTORY_SEPARATOR.EXP_CONFIG_FILE ) ) {
	require_once( dirname(__FILE__).DIRECTORY_SEPARATOR.
		$sts_subdomain.DIRECTORY_SEPARATOR.EXP_CONFIG_FILE );
} else if( file_exists( EXP_CONFIG_FILE ) ) {
	require_once( EXP_CONFIG_FILE );
} else if( file_exists( dirname(__FILE__).DIRECTORY_SEPARATOR.EXP_CONFIG_FILE ) ) {
	require_once( dirname(__FILE__).DIRECTORY_SEPARATOR.EXP_CONFIG_FILE );
} else if( file_exists( "include".DIRECTORY_SEPARATOR.EXP_CONFIG_FILE ) ) {
	require_once( "include".DIRECTORY_SEPARATOR.EXP_CONFIG_FILE );
} else if( isset($_SERVER) && isset($_SERVER["CONTEXT_DOCUMENT_ROOT"]) &&
file_exists( $_SERVER["CONTEXT_DOCUMENT_ROOT"].DIRECTORY_SEPARATOR."include".DIRECTORY_SEPARATOR.EXP_CONFIG_FILE ) ) {
	require_once( $_SERVER["CONTEXT_DOCUMENT_ROOT"].DIRECTORY_SEPARATOR.
		"include".DIRECTORY_SEPARATOR.EXP_CONFIG_FILE );
} else if( ! defined('_STS_INSTALL') ) {
	echo "\n\n<p>Include path: ".ini_get('include_path')."</p>\n";
	echo "<p> file <strong>".EXP_CONFIG_FILE."</strong> ".(file_exists(EXP_CONFIG_FILE) ? "exists" : "does not exist")." in ".getcwd()."</p>\n";
	echo "<p> file "."include".DIRECTORY_SEPARATOR.EXP_CONFIG_FILE." ".(file_exists("include".DIRECTORY_SEPARATOR.EXP_CONFIG_FILE) ? "exists" : "does not exist")." in ".getcwd()."</p>\n";
	die('<p>Unable to read config file '.EXP_CONFIG_FILE.' cwd='.getcwd().' dirname='.dirname(__FILE__) .'</p>
	<p>This file is a component of Exspeedite that is created during software installation & setup.</p>
	<p><strong>Please contact your admin or Espeedite support to re-run the installer.</strong></p>
	
');

}

date_default_timezone_set(isset($sts_crm_timezone) ? $sts_crm_timezone : 'America/Chicago');

define( 'DUMMY_DATABASE', 'dummy' );
define( 'TESTING_USER', 'duncan' );	// Enable debug for this user
define( 'SYNERGY_USER', 'synergy' );
define( 'EMAIL_USER', 'email_sender' );
define( 'EDI_USER', 'edi_import' );
define( 'QUICKBOOKS_USER', 'quickbooks' );
define( 'KT_USER', 'keeptruckin' );

//! User Groups
define('EXT_GROUP_ADMIN', 'admin');				// System admin
define('EXT_GROUP_SUPERADMIN', 'superadmin');	// Super admin
define('EXT_GROUP_USER', 'user');				// default user group
define('EXT_GROUP_SALES', 'sales');				// Clients
define('EXT_GROUP_SHIPMENTS', 'shipments');		// Shipments - SCR# 424
define('EXT_GROUP_DRIVER', 'driver');			// Driver portal only
define('EXT_GROUP_DISPATCH', 'dispatch');		// Loads
define('EXT_GROUP_BILLING', 'billing');			// Can enter bills
define('EXT_GROUP_FINANCE', 'finance');			// Quickbooks
define('EXT_GROUP_SAGE50', 'Sage50');			// Sage 50 Export
define('EXT_GROUP_HR', 'HR');					// Human Resources
define('EXT_GROUP_RANDOM', 'random');			// Random (testing) group
define('EXT_GROUP_PROFILES', 'profiles');		// Clients & Carriers
define('EXT_GROUP_FLEET', 'fleet');				// Drivers & Trucks
define('EXT_GROUP_MANAGER', 'manager');			// Is a manager, for financials
define('EXT_GROUP_MECHANIC', 'mechanic');		// Is a mechanic, for inspection reports
define('EXT_GROUP_INSPECTION', 'inspection');	//! SCR# 472 - Can edit inspection reports
define('EXT_GROUP_DEBUG', 'debug');				// Can add debug to a screen
$sts_opt_groups = array( EXT_GROUP_ADMIN, EXT_GROUP_USER,
	EXT_GROUP_SALES, EXT_GROUP_SHIPMENTS, EXT_GROUP_DRIVER, EXT_GROUP_DISPATCH,
	EXT_GROUP_BILLING, EXT_GROUP_FINANCE, EXT_GROUP_SAGE50,
	EXT_GROUP_HR, EXT_GROUP_RANDOM, EXT_GROUP_PROFILES, EXT_GROUP_FLEET,
	EXT_GROUP_MANAGER, EXT_GROUP_MECHANIC, EXT_GROUP_INSPECTION,
	EXT_GROUP_DEBUG );

//! Table names
define( 'ALERT_CACHE_TABLE',		'EXP_ALERT_CACHE' );
define( 'ARTICLE_TABLE',		'EXP_ARTICLE' );
define( 'ATTACHMENT_TABLE',		'EXP_ATTACHMENT' );
define( 'BATCH_INVOICE_TABLE',	'EXP_BATCH_INVOICE' );
define( 'BUSINESS_CODE_TABLE',	'EXP_BUSINESS_CODE' );
define( 'CANADA_POST_CODES_TABLE',	'CANADA_POST_CODES' );
define( 'CANADA_TAX_TABLE',		'EXP_CANADA_TAX' );
define( 'CARD_ADVANCE_TABLE',	'EXP_CARD_ADVANCE' );
define( 'CARD_FTP_TABLE',		'EXP_CARD_FTP' );
define( 'CARRIER_TABLE',		'EXP_CARRIER' );
define( 'CLIENT_ACTIVITY_TABLE', 'EXP_CLIENT_ACTIVITY' );
define( 'CLIENT_TABLE', 		'EXP_CLIENT' );
define( 'COMMODITY_CLASS_TABLE', 'EXP_COMMODITY_CLASS' );
define( 'COMMODITY_TABLE',		'EXP_COMMODITY' );
define( 'COMPANY_TABLE',		'EXP_COMPANY' );
define( 'COMPANY_TAX_TABLE',	'EXP_COMPANY_TAX' );
define( 'CONTACT_INFO_TABLE',	'EXP_CONTACT_INFO' );
define( 'DETAIL_TABLE',			'EXP_DETAIL' );
define( 'DRIVER_TABLE',			'EXP_DRIVER' );
define( 'DRIVER_CARD_TABLE',	'EXP_DRIVER_CARD' );	//! SCR# 551
define( 'EMAIL_ATTACH_TABLE',	'EXP_EMAIL_ATTACH' );
define( 'EMAIL_QUEUE_TABLE',	'EXP_EMAIL_QUEUE' );	//! SCR# 712 - email queuing
define( 'EQUIPMENT_REQ_TABLE',	'EXP_EQUIPMENT_REQ' ); //! SCR# 702
define( 'EXCHANGE_TABLE',		'EXP_EXCHANGE' );
define( 'EXCHANGE_MONTH_TABLE',	'EXP_EXCHANGE_MONTH' );
define( 'EDI_TABLE',			'EXP_EDI' );
define( 'FORMMAIL_TABLE',		'EXP_FORMMAIL' );
define( 'IFTA_RATE_TABLE',		'EXP_IFTA_RATE' );
define( 'IFTA_LOG_TABLE',		'EXP_IFTA_LOG' );
define( 'IFTA_FOOTNOTE_TABLE',	'EXP_IFTA_FOOTNOTE' );
define( 'INSP_LIST_ITEMS_TABLE','EXP_INSP_LIST_ITEMS' );		//! SCR# 259 - Inspection Reports
define( 'INSP_REPORT_TABLE',	'EXP_INSP_REPORT' );			//! SCR# 259 - Inspection Reports
define( 'INSP_REPORT_TIRES',	'EXP_INSP_REPORT_TIRES' );		//! SCR# 259 - Inspection Reports
define( 'INSP_REPORT_PART',		'EXP_INSP_REPORT_PART' );		//! SCR# 329 - Inspection Reports
define( 'INSP_REPORT_ITEM_TABLE','EXP_INSP_REPORT_ITEM' );		//! SCR# 259 - Inspection Reports
define( 'ITEM_LIST_TABLE',		'EXP_ITEM_LIST' );
define( 'FLEET_TABLE',			'EXP_FLEET' );
define( 'FTP_TABLE',			'EXP_EDI_FTP' );
define( 'IMAGE_TABLE',			'EXP_IMAGE' );
define( 'LICENSE_TABLE',		'EXP_LICENSE' );
define( 'LOAD_TABLE',			'EXP_LOAD' );
define( 'MANUAL_MILES_TABLE',	'EXP_MANUAL_MILES' );
define( 'MESSAGE_TABLE',		'EXP_MESSAGE' );
define( 'OFFICE_TABLE',			'EXP_OFFICE' );
define( 'OSD_TABLE',			'EXP_OSD' );
define( 'PALLET_TABLE',			'EXP_PALLET' );
define( 'PCM_DISTANCE_CACHE',	'EXP_PCM_DISTANCE_CACHE' );
define( 'PCM_CACHE',			'EXP_PCM_CACHE' );
define( 'REPORT_TABLE',			'EXP_REPORT' );
define( 'RM_CLASS_FORM_TABLE',	'EXP_RM_CLASS_FORM' );			//! SCR# 522 - R&M
define( 'RM_CLASS_TABLE',		'EXP_RM_CLASS' );				//! SCR# 522 - R&M
define( 'RM_FORM_TABLE',		'EXP_RM_FORM' );				//! SCR# 522 - R&M
define( 'SAGE50_GLMAP_TABLE',	'EXP_SAGE50_GLMAP' );
define( 'SCHEMA_TABLE',			'EXP_SCHEMA_VERSION' );
define( 'SCR_TABLE',			'EXP_SCR' );
define( 'SCR_HISTORY_TABLE',	'EXP_SCR_HISTORY' );
define( 'SETTING_TABLE',		'EXP_SETTING' );
define( 'SHIPMENT_TABLE',		'EXP_SHIPMENT' );
define( 'SHIPMENT_LOAD_TABLE',	'EXP_SHIPMENT_LOAD' );
define( 'STATUS_TABLE',			'EXP_STATUS' );
define( 'STATUS_CODES_TABLE',	'EXP_STATUS_CODES' );
define( 'STATES_TABLE',			'EXP_STATES' );
define( 'STOP_TABLE',			'EXP_STOP' );
define( 'TIMEZONE_TABLE',		'EXP_TIMEZONE' );
define( 'TRACTOR_TABLE',		'EXP_TRACTOR' );
define( 'TRACTOR_CARD_TABLE',	'EXP_TRACTOR_CARD' );	//! SCR# 551
define( 'TRAILER_TABLE',		'EXP_TRAILER' );
define( 'UNIT_TABLE',			'EXP_UNIT' );
define( 'UN_NUMBER_TABLE',		'EXP_UN_NUMBER' );
define( 'USER_TABLE',			'EXP_USER' );
define( 'USER_LOG_TABLE',		'EXP_USER_LOG' );
define( 'USER_MESSAGE_TABLE',	'EXP_USER_MESSAGE' );
define( 'USER_OFFICE_TABLE',	'EXP_USER_OFFICE' );
define( 'USER_REPORT_TABLE',	'EXP_USER_REPORT' );
define( 'VACATION_TABLE',		'EXP_VACATION' );
define( 'VIDEO_TABLE',			'EXP_VIDEO' );
define( 'ZIP_TABLE',			'ZIPCodes' );
define( 'YARD_CONTAINER_TABLE',	'EXP_YARD_CONTAINER' );
define( 'ZONE_FILTER_TABLE',	'EXP_ZONE_FILTER' );
define( 'HOLIDAY_TABLE',	'EXP_HOLIDAYS' );
##########
define('DRIVER_RATES','EXP_DRIVER_RATES');
define('CATEGORY_TABLE','EXP_CATEGORY');
define('MANUAL_RATES','EXP_DRIVER_MANUAL_RATES');
define('RANGE_RATES','EXP_DRIVER_RANGE_RATES');
define('DRIVER_ASSIGN_RATES','EXP_DRIVER_ASSIGN_RATES' );
define('PROFILE_MASTER','EXP_PROFILE_MASTER');
define('PROFILE_RATE','EXP_PROFILE_RATES');
define('PROFILE_MANUAL_RATE','EXP_PROFILE_MANUAL_RATES');
define('PROFILE_MANUAL','EXP_PROFILE_MANUAL');
define('PROFILE_RANGE_RATE','EXP_PROFILE_RANGE_RATE');
define('PROFILE_RANGE','EXP_PROFILE_RANGE');
define('CLIENT_RATE','EXP_CLIENTRATE');
define('CLIENT_CAT','EXP_CLIENT_CATEGORY');
define('CLIENT_CAT_RATE','EXP_CLIENT_CAT_RATE_MASTER');
define('CLIENT_ASSIGN_RATE','EXP_CLIENT_ASSIGN_RATE');
define('FSC_SCHEDULE','EXP_FSC_SCHEDULE');
define('HANDLING_TABLE' , 'EXP_CLIENT_HANDLING_CHARGES');
define('FRIGHT_RATE_TABLE','EXP_CLIENT_FRIGHT_RATE');
define('CLIENT_FSC','EXP_CLIENT_FSC');
define('CLIENT_RATE_MASTER','EXP_CLIENT_RATE_MASTER');
define('LOAD_PAY_MASTER','EXP_LOAD_PAY_MASTER');
define('LOAD_PAY_RATE','EXP_LOAD_PAY_RATE');
define('LOAD_MAN_RATE','EXP_LOAD_MANUAL_RATE');
define('LOAD_RANGE_RATE','EXP_LOAD_RANGE_RATE');
define('DRIVER_PAY_MASTER','EXP_DRIVER_PAY_MASTER');
define('CLIENT_BILL','EXP_CLIENT_BILLING');//exp_client_billing
define('CLIENT_BILL_RATES','EXP_CLIENT_BILLING_RATES');
define('MAN_CODE','EXP_MAN_CODE');
define('RAN_CODE','EXP_RANGE_CODE');
define('PALLET_MASTER','EXP_PALLET_MASTER');
define('PALLET_RATE','EXP_PALLET_RATE_MASTER');
define('DETENTION_MASTER','EXP_DETENTION_MASTER');
define('DETENTION_RATE','EXP_DETENTION_HOURS_RATE');
define('FSC_HISTORY','EXP_FSC_HISTORY');
define('UNLOADED_DETENTION_RATE','EXP_UNLOADED_DETENTION_RATE');


// Who can access what table
$sts_table_access = array(
	ATTACHMENT_TABLE		=> EXT_GROUP_USER,
	ARTICLE_TABLE			=> EXT_GROUP_USER,
	BUSINESS_CODE_TABLE		=> EXT_GROUP_ADMIN,
	CANADA_TAX_TABLE		=> EXT_GROUP_ADMIN,
	CARD_ADVANCE_TABLE		=> EXT_GROUP_ADMIN,
	CARRIER_TABLE			=> EXT_GROUP_PROFILES,
	CLIENT_ACTIVITY_TABLE	=> EXT_GROUP_SALES,
	CLIENT_TABLE			=> EXT_GROUP_PROFILES,
	COMMODITY_CLASS_TABLE	=> EXT_GROUP_PROFILES,
	COMMODITY_TABLE			=> EXT_GROUP_PROFILES,
	COMPANY_TABLE			=> EXT_GROUP_ADMIN,
	COMPANY_TAX_TABLE		=> EXT_GROUP_ADMIN,
	CONTACT_INFO_TABLE		=> EXT_GROUP_USER,
	DETAIL_TABLE			=> EXT_GROUP_USER,
	DRIVER_TABLE			=> EXT_GROUP_FLEET,
	EDI_TABLE				=> EXT_GROUP_USER,
	EXCHANGE_TABLE			=> EXT_GROUP_USER,
	FLEET_TABLE				=> EXT_GROUP_FLEET,
	FORMMAIL_TABLE			=> EXT_GROUP_USER,
	FTP_TABLE				=> EXT_GROUP_ADMIN,
	CARD_FTP_TABLE			=> EXT_GROUP_ADMIN,
	IMAGE_TABLE				=> EXT_GROUP_USER,
	LICENSE_TABLE			=> EXT_GROUP_USER,
	LOAD_TABLE				=> EXT_GROUP_DISPATCH,
	MANUAL_MILES_TABLE		=> EXT_GROUP_ADMIN,
	OFFICE_TABLE			=> EXT_GROUP_ADMIN,
	OSD_TABLE				=> EXT_GROUP_USER,
	PALLET_TABLE			=> EXT_GROUP_USER,
	REPORT_TABLE			=> EXT_GROUP_ADMIN,
	RM_CLASS_FORM_TABLE		=> EXT_GROUP_ADMIN,
	RM_CLASS_TABLE			=> EXT_GROUP_ADMIN,
	RM_FORM_TABLE			=> EXT_GROUP_ADMIN,
	SAGE50_GLMAP_TABLE		=> EXT_GROUP_ADMIN,
	SETTING_TABLE			=> EXT_GROUP_ADMIN,
	SHIPMENT_TABLE			=> EXT_GROUP_SHIPMENTS,
	STATUS_CODES_TABLE		=> EXT_GROUP_ADMIN,
	STATES_TABLE			=> EXT_GROUP_USER,
	STOP_TABLE				=> EXT_GROUP_USER,
	TRACTOR_TABLE			=> EXT_GROUP_FLEET,
	TRAILER_TABLE			=> EXT_GROUP_PROFILES,
	UNIT_TABLE				=> EXT_GROUP_ADMIN,
	USER_TABLE				=> EXT_GROUP_ADMIN,
	USER_LOG_TABLE			=> EXT_GROUP_ADMIN,
	USER_OFFICE_TABLE		=> EXT_GROUP_ADMIN,
	VACATION_TABLE			=> EXT_GROUP_ADMIN,
	YARD_CONTAINER_TABLE	=> EXT_GROUP_USER,
	ZIP_TABLE				=> EXT_GROUP_USER,
	ZONE_FILTER_TABLE		=> EXT_GROUP_USER,
	DRIVER_RATES => EXT_GROUP_USER,
	MANUAL_RATES => EXT_GROUP_USER,
	RANGE_RATES 	=>  EXT_GROUP_USER,
	CATEGORY_TABLE =>  EXT_GROUP_USER,
	PROFILE_RATE =>EXT_GROUP_USER,
    PROFILE_MANUAL_RATE=>EXT_GROUP_USER,
    PROFILE_MANUAL =>EXT_GROUP_USER,
    PROFILE_RANGE_RATE=>EXT_GROUP_USER,
    PROFILE_RANGE=>EXT_GROUP_USER,
    PROFILE_MASTER=>EXT_GROUP_USER,
    CLIENT_RATE=>EXT_GROUP_USER,
    CLIENT_CAT=>EXT_GROUP_USER,
    CLIENT_CAT_RATE=>EXT_GROUP_USER,
   CLIENT_ASSIGN_RATE=>EXT_GROUP_USER,
    FSC_SCHEDULE=>EXT_GROUP_USER,
	HANDLING_TABLE	=> EXT_GROUP_USER,
	FRIGHT_RATE_TABLE=>EXT_GROUP_USER,
	CLIENT_FSC=>EXT_GROUP_USER,
   CLIENT_RATE_MASTER=>EXT_GROUP_USER,
	LOAD_PAY_MASTER=>EXT_GROUP_USER,
	LOAD_PAY_RATE=>EXT_GROUP_USER,
	LOAD_MAN_RATE=>EXT_GROUP_USER,
	LOAD_RANGE_RATE=>EXT_GROUP_USER,
	DRIVER_PAY_MASTER=>EXT_GROUP_USER,
	CLIENT_BILL=>EXT_GROUP_USER,
	CLIENT_BILL_RATES=>EXT_GROUP_USER,
 	MAN_CODE=>EXT_GROUP_USER,
 	RAN_CODE=>EXT_GROUP_USER,
	PALLET_MASTER=>EXT_GROUP_USER,
	PALLET_RATE=>EXT_GROUP_USER,
	DETENTION_MASTER=>EXT_GROUP_USER,
	DETENTION_RATE=>EXT_GROUP_USER,
	FSC_HISTORY=>EXT_GROUP_USER,
	UNLOADED_DETENTION_RATE=>EXT_GROUP_USER
);

// Primary keys
$sts_primary_keys = array(
	BUSINESS_CODE_TABLE		=> 'BUSINESS_CODE',
	CARRIER_TABLE			=> 'CARRIER_CODE',
	CLIENT_TABLE			=> 'CLIENT_CODE',
	COMMODITY_TABLE			=> 'COMMODITY_CODE',
	COMMODITY_CLASS_TABLE	=> 'COMMODITY_CLASS_CODE',
	COMPANY_TABLE			=> 'COMPANY_CODE',
	CONTACT_INFO_TABLE		=> 'CONTACT_INFO_CODE',
	DETAIL_TABLE			=> 'DETAIL_CODE',
	DRIVER_TABLE			=> 'DRIVER_CODE',
	EDI_TABLE				=> 'EDI_CODE',
	FTP_TABLE				=> 'FTP_CODE',
	LICENSE_TABLE			=> 'LICENSE_CODE',
	LOAD_TABLE				=> 'LOAD_CODE',
	OFFICE_TABLE			=> 'OFFICE_CODE',
	OSD_TABLE				=> 'OSD_CODE',
	PALLET_TABLE			=> 'PALLET_CODE',
	SHIPMENT_TABLE			=> 'SHIPMENT_CODE',
	STATUS_CODES_TABLE		=> 'STATUS_CODES_CODE',
	STOP_TABLE				=> 'STOP_CODE',
	TRACTOR_TABLE			=> 'TRACTOR_CODE',
	TRAILER_TABLE			=> 'TRAILER_CODE',
	UNIT_TABLE 				=> 'UNIT_CODE',
	USER_TABLE				=> 'USER_CODE',
	USER_OFFICE_TABLE		=> 'USER_OFFICE_CODE',
	USER_REPORT_TABLE		=> 'USER_REPORT_CODE',
	CLIENT_RATE_MASTER =>'CLIENT_RATE_ID',
	CLIENT_BILL_RATES=>'BILL_RATE_ID',
	CLIENT_BILL=>'CLIENT_BILLING_ID',
	FSC_SCHEDULE=>'FSC_ID',
	PALLET_MASTER=>'PALLET_ID',
	DETENTION_MASTER=>'DETENTION_ID',
	DETENTION_RATE=>'DET_HR_ID',
	FSC_HISTORY=>'FSC_HIS_ID',
	PALLET_RATE=>'PALLET_RATE_ID',
	FRIGHT_RATE_TABLE=>'FRIGHT_ID',
	HANDLING_TABLE=>'HANDLING_ID',
	PROFILE_MASTER=>'PRO_ID',
	PROFILE_MANUAL_RATE=>'PRO_MANUAL_ID',
	PROFILE_RANGE_RATE=>'PRO_RANGE_ID',
	MANUAL_RATES=>'MANUAL_ID',
	RANGE_RATES=>'RANGE_ID',
	UNLOADED_DETENTION_RATE=>'UN_DET_HR_ID',
	CLIENT_CAT_RATE=>'CLIENT_RATE_ID'
);

// Edit pages
$sts_edit_pages = array(
	USER_TABLE => 'exp_edituser.php?CODE=',
	DRIVER_TABLE => 'exp_editdriver.php?CODE=',
	TRACTOR_TABLE => 'exp_edittractor.php?CODE=',
	TRAILER_TABLE => 'exp_edittrailer.php?CODE=',
	CARRIER_TABLE => 'exp_editcarrier.php?CODE=',
	UNIT_TABLE => 'exp_editunit.php?CODE=',
	COMMODITY_TABLE => 'exp_editcommodity.php?CODE=',
	COMMODITY_CLASS_TABLE => 'exp_editcommodity_class.php?CODE=',
	PALLET_TABLE => 'exp_editpallet.php?CODE=',
	STATUS_CODES_TABLE => 'exp_editstatus_codes.php?CODE=',
	CLIENT_TABLE => 'exp_editclient.php?CODE='
);

// Terminology - an idea for configurable terminology, not completed.
$sts_term_order			= 'shipment';
$sts_term_Order			= 'Shipment';
$sts_term_orders		= 'shipments';

// Time for cached data to remain - 20 minutes
define("SESSION_CACHE_TIME", 1200); 

// License Endorsements
$sts_license_endorsements = array(
	'H' => 'Hazardous Materials',
	'T' => 'Double / Triple Trailer',
	'N' => 'Tank',
	'P' => 'Passenger',
//	'S' => 'School Bus'
);

// Error levels - trace cannot be blocked.
define( 'EXT_ERROR_TRACE',			1 );	// User requested sending a trace
define( 'EXT_ERROR_ERROR',			2 );	// Serious error - needs looking into
define( 'EXT_ERROR_WARNING',		3 );	// Not serious
define( 'EXT_ERROR_NOTICE',			4 );	// Quite minor really
define( 'EXT_ERROR_DEBUG',			5 );	// Used for debugging, not meant for customer use

define( 'EXT_ERROR_ALL',			10 );	// Default value, meaning all errors

$sts_error_level_label = array(
	EXT_ERROR_TRACE =>		'trace',
	EXT_ERROR_ERROR =>		'error',
	EXT_ERROR_WARNING =>	'warning',
	EXT_ERROR_NOTICE =>		'notice',
	EXT_ERROR_DEBUG =>		'debug'
);

define( 'QUICKBOOKS_VENDOR_CARRIER',	1 );
define( 'QUICKBOOKS_VENDOR_DRIVER',		2 );


$sts_title = 'Exspeedite';

$sts_logo_image = 'EXSPEEDITEsmr.png';

$sts_log_to_file = true;

$sts_video_directory = 'videos/';
$sts_video_suffix = '.mp4';

// Name of invoice template;
$sts_qb_template = 'PIPCO';

$sts_fns_name = 'F&S';

// Version Information
$sts_release_name = '9.0.6';
$sts_release_year = '2023';
$sts_schema_release_name = '9_0_5';

//! Captcha - for more enhanced security
$sts_use_captcha = true;				// To enable/disable
$sts_use_captcha_after_fail = true;		// To only use it after a login failure

$sts_schema_directory = $sts_crm_dir.'schema/';
$sts_schema_suffix = '.json';

//--------------------------------------------------------DO NOT EDIT BELOW HERE

// No need to change this, it is derived.
if( isset($sts_username) )	// simple check to avoid pre-install errors
	$sts_qb_dsn = 'mysqli://'.$sts_username.':'.$sts_password.'@'.$sts_db_host.'/'.$sts_database;

// Some functions

function in_group( $group1, $group2 = false ) {
	return isset($_SESSION) && isset($_SESSION['EXT_GROUPS']) &&
		(in_array( $group1, explode(',',$_SESSION['EXT_GROUPS'])) ||
		(is_string($group2) && in_array( $group2, explode(',',$_SESSION['EXT_GROUPS']))));
}

//! Check if the date string is valid
function validateDateTime($format) {
    return function($dateStr) use ($format) {
        $date = DateTime::createFromFormat($format, $dateStr);
        return $date && $date->format($format) === $dateStr;
    };
}

$validDate = validateDateTime('m/d/Y');
$validMiltime = validateDateTime('Hi');
$validTimestamp = validateDateTime('m/d/Y H:i');

//! SCR# 587 - Less strict date checking
function checkIsAValidDate($myDateString){
    return (bool)strtotime($myDateString);
}


function reload_page ( $page ) {
	global $sts_crm_root;
	echo "<script language=\"JavaScript\" type=\"text/javascript\">
//console.log('page = ', '".$page."');
window.location = \"".$sts_crm_root."/".$page."\";

</script>";
	@ob_end_flush();
	//exit();
}

function update_message( $div, $msg ) {
	echo "<script type=\"text/javascript\">

document.getElementById(\"".$div."\").innerHTML=\"".$msg."\";

</script>";
}

function format_timestamp( $ts, $year = false, $invalid = '(no date)' ) {
	return isset($ts) && $ts <> '' && $ts <> '0000-00-00 00:00:00' ? date("m/d".($year ? "/Y" : "")." H:i", strtotime($ts)) : $invalid;
}

function text_error( $text, $condition ) {
	return $condition ? '<span class="text-danger"><strong>'.$text.'</strong></span>' : $text;
}

function plural( $count, $prefix="" ) {
	if( $count == 1 )
		$msg = '1 '.$prefix;
	else {
		if( $count <= 0 )
			$msg = 'no '.$prefix;
		else
			$msg = $count.' '.$prefix;
		if( preg_match( '/[aeiou]/', $prefix[strlen($prefix)-1] ) )
			$msg .= 'es';
		else
		if( $prefix[strlen($prefix)-1] == 'y' )
			$msg .= 'ies';
		else
			$msg .= 's';
	}
		
	return $msg;
}

function singular( $name ) {
	if( preg_match( '/^(.*)ies$/', $name, $matches ) )
		$msg = $matches[1].'y';
	else if( preg_match( '/^(.*[aeiou])es$/', $name, $matches ) )
		$msg = $matches[1];
	else if( preg_match( '/^(.*)s$/', $name, $matches ) )
		$msg = $matches[1];
	else
		$msg = $name;
	return $msg;
}

function startswith($string, $test) {
	$length = strlen($test);
	
	return (substr($string, 0, $length)) == $test;
}

function endswith($string, $test) {
    $strlen = strlen($string);
    $testlen = strlen($test);
    if ($testlen > $strlen) return false;
    return substr_compare($string, $test, $strlen - $testlen, $testlen) === 0;
}

// Active assert and make it quiet
assert_options(ASSERT_ACTIVE, 1);
//assert_options(ASSERT_WARNING, 0);
assert_options(ASSERT_BAIL, 1);
//assert_options(ASSERT_QUIET_EVAL, 1);


?>