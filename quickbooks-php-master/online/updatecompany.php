<?php

// Set flag that this is a parent file
define( '_STS_INCLUDE', 1 );

// Setup Session
require_once( "../../include/sts_session_setup.php" );

require_once dirname(__FILE__) . '/config.php';

require_once( "include/sts_session_class.php" );

$my_session = sts_session::getInstance( $exspeedite_db, $sts_debug );
$my_session->access_check( EXT_GROUP_ADMIN );	// Make sure we should be here

function update_company_setting( $setting, $name, $value ) {
	return $setting->update_row( "CATEGORY = 'company' AND SETTING = '".$name."'", 
		array(
			array('field' => 'THE_VALUE', 'value' => $setting->enquote_string( 'THE_VALUE', 
			$setting->real_escape_string( (string) $value) ))
		)
	);	
}

//! SCR# 239 - multi-company, use multiple Quickbooks companies
require_once( "include/sts_company_class.php" );
require_once( "include/sts_office_class.php" );
$company_table = sts_company::getInstance($exspeedite_db, $sts_debug);
$office_table = sts_office::getInstance($exspeedite_db, $sts_debug);

if( $multi_company && $sts_qb_multi && isset($_SESSION['SETUP_COMPANY']) ) {
	list($quickbooks_is_connected, $realm, $Context) =
		connect_to_quickbooks( $_SESSION['SETUP_COMPANY'] );
} else {
	list($quickbooks_is_connected, $realm, $Context) = connect_to_quickbooks();
}

if( $quickbooks_is_connected ) {
	
	echo '<center><img src="../../images/loading.gif" alt="loading" width="125" height="125" /></center>';
	$CompanyInfoService = new QuickBooks_IPP_Service_CompanyInfo();
	$quickbooks_CompanyInfo = $CompanyInfoService->get($Context, $realm);
	$name = $quickbooks_CompanyInfo->getCompanyName();
	$addr = $quickbooks_CompanyInfo->getCompanyAddr();
	$country = $quickbooks_CompanyInfo->getCountry();
	if( $country == 'CA' ) $country = 'Canada';
	else if( $country == 'US' ) $country = 'USA';
	else $country = NULL;
	
	if( ! is_object($addr))
		$addr = $quickbooks_CompanyInfo->getCustomerCommunicationAddr();
	if( ! is_object($addr))
		$addr = $quickbooks_CompanyInfo->getLegalAddr();
	
	$line1 = $addr->getLine1();
	$line2 = $addr->getLine2();
	$city = $addr->getCity();
	$state = $addr->getCountrySubDivisionCode();
	$postal = $addr->getPostalCode();

	$number = $quickbooks_CompanyInfo->getPrimaryPhone();
	$number = is_object($number) ? $number->getFreeFormNumber() : '';
	
	$email = $quickbooks_CompanyInfo->getEmail();
	$email = is_object($email) ? $email->getAddress() : '';

	$web = $quickbooks_CompanyInfo->getWebAddr();
	$web = is_object($web) ? $web->getURI() : '';

	//! Check preferences for home currency
	$PreferencesService = new QuickBooks_IPP_Service_Preferences();
	$qb_prefs = $PreferencesService->get($Context, $realm);
	$currency = $qb_prefs->getCurrencyPrefs()->getHomeCurrency();

	if( $sts_debug ) {
		echo "<pre>";
		var_dump($name, $line1, $line2, $city, $state, $postal, $country, $number, $email, $web, $currency);
		echo "</pre>";
	}
	
	$company_table->set_info( isset($_SESSION['SETUP_COMPANY']) ? $_SESSION['SETUP_COMPANY'] : 0,
		$name, $line1, $line2, $city,
    	$state, $postal, $country, $email, $number, $web, $currency );

	$office_table->user_offices( true );
}

if( ! $sts_debug ) {
	echo "<script type=\"text/javascript\">
	window.location = \"setup.php\"
</script>";
}	

?>