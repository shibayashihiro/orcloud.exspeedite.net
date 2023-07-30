<?php
// Set flag that this is a parent file
define( '_STS_INCLUDE', 1 );

// Setup Session
require_once( "../../include/sts_session_setup.php" );

require_once dirname(__FILE__) . '/config.php';

/*
require_once( "sts_email_class.php" );

$email = sts_email::getInstance($exspeedite_db, $sts_debug);
$email->send_alert("Testing spawn process");
*/

require_once( "include/sts_session_class.php" );

$my_session = sts_session::getInstance( $exspeedite_db, $sts_debug );
$my_session->access_check( EXT_GROUP_ADMIN );	// Make sure we should be here

//! SCR# 239 - multi-company, use multiple Quickbooks companies
if( $multi_company && $sts_qb_multi && isset($_SESSION['SETUP_COMPANY']) ) {
	list($quickbooks_is_connected, $realm, $Context) =
		connect_to_quickbooks( $_SESSION['SETUP_COMPANY'] );
} else {
	list($quickbooks_is_connected, $realm, $Context) = connect_to_quickbooks();
}

if( $sts_debug ) {
	echo "<pre>After connect_to_quickbooks";
	var_dump($quickbooks_is_connected, $Context, $realm);
	echo "</pre>";
}

if( $quickbooks_is_connected ) {
	$TaxRateService = new QuickBooks_IPP_Service_TaxRate();

	$taxrates = $TaxRateService->query($Context, $realm, "SELECT * FROM TaxRate
		WHERE SpecialTaxType = 'ZERO_RATE' AND Active = true");

	if( $sts_debug ) {
		echo "<pre>taxrates";
		echo $TaxRateService->lastRequest(). "\n\n\n";
		echo $TaxRateService->lastResponse(). "\n\n\n";
		var_dump($taxrates);
		echo "</pre>";
	}

	if( is_array($taxrates) && count($taxrates) > 0 ) {
		foreach( $taxrates as $taxrate ) {
			echo "<p>ID = ".$taxrate->getId()." Name = ".$taxrate->getName().
				" Description = ".$taxrate->getDescription().
				" SpecialTaxType = ".$taxrate->getSpecialTaxType().
				" Rate = ".$taxrate->getRateValue()."</p>";
		}
	}

	$TaxCodeService = new QuickBooks_IPP_Service_TaxCode();

	$taxcodes = $TaxCodeService->query($Context, $realm, "SELECT * FROM TaxCode
		WHERE Name = 'GST BC' AND Active = true");
		// Name = 'Exempt'

	echo "<pre>";
	var_dump($taxcodes);
	echo "</pre>";
	
	/*
	$TaxAgencyService = new QuickBooks_IPP_Service_TaxAgency();

	$taxagencies = $TaxAgencyService->query($Context, $realm, "SELECT * FROM TaxAgency");
	echo "<pre>";
	var_dump($taxagencies);
	echo "</pre>";
	if( is_array($taxagencies) && count($taxagencies) > 0 ) {
		foreach( $taxagencies as $taxagency ) {
			echo "<p>ID = ".$taxagency->getId()." Name = ".$taxagency->getDisplayName().
				" Reg# = ".$taxagency->getTaxRegistrationNumber()."</p>";
		}
	}
	*/



} else {
	echo "<p>Quickbooks is NOT connected</p>";
}
	
?>