<?php 
// Set flag that this is a parent file
define( '_STS_INCLUDE', 1 );

// Set flag that this is session readonly
define( '_STS_SESSION_READONLY', 1 );

// Set flag that this is an ajax call
define( '_STS_SESSION_AJAX', 1 );

// Setup Session
require_once( "include/sts_session_setup.php" );

require_once( "include/sts_config.php" );
$sts_debug = isset($_GET['debug']);

require_once( "include/sts_canada_tax_class.php" );
require_once( "include/sts_company_tax_class.php" );
require_once( "include/sts_load_class.php" );

if( isset($_GET['code']) && isset($_GET['pw']) && $_GET['pw'] == 'Warranty') {
	$canada_tax = sts_canada_tax::getInstance($exspeedite_db, $sts_debug);
	$company_tax = sts_company_tax::getInstance($exspeedite_db, $sts_debug);
	$load_table = sts_load::getInstance($exspeedite_db, $sts_debug);
	
	if( $sts_debug ) {
		echo "<pre>";
		var_dump($_GET);
		var_dump(isset($_GET['lumper']));
		echo "</pre>";
	}

	$result = array();
	
	// Update EXP_LOAD.CURRENCY
	if( isset($_GET['code']) && isset($_GET['currency']) ) {
		$load_table->update( $_GET['code'], array('CURRENCY' => $_GET['currency']) );
	}
		
	// Find and set the lumper currency
	if( isset($_GET['code']) && isset($_GET['lumper']) && intval($_GET['lumper']) > 0 ) {
		$check = $load_table->database->get_one_row("
			SELECT CURRENCY_CODE, CDN_TAX_EXEMPT FROM EXP_CARRIER
			WHERE CARRIER_CODE = ".intval($_GET['lumper'])."
			AND CARRIER_TYPE IN ('lumper', 'shag')");
			
		if( is_array($check) && isset($check["CURRENCY_CODE"]) ) {
			$changes = [ 'LUMPER' => $_GET['lumper'],
				'LUMPER_CURRENCY' => $check["CURRENCY_CODE"] ];
			
			if( isset($check["CDN_TAX_EXEMPT"]) && $check["CDN_TAX_EXEMPT"] ) {
				$changes['CDN_TAX_EXEMPT'] = 1;
			} else if( isset($_GET['exempt']) ) {
				$changes['CDN_TAX_EXEMPT'] = $_GET['exempt'] == 'true' ? 1 : 0;
			}

			$load_table->update( $_GET['code'], $changes );
			
			$result['LUMPER_CURRENCY'] = $check["CURRENCY_CODE"];
		}
	} else if( isset($_GET['lumper']) && $_GET['lumper'] == 'null' ) {
		$load_table->database->get_one_row("UPDATE EXP_LOAD
			SET LUMPER_CURRENCY = CURRENCY
			WHERE LOAD_CODE = ".$_GET['code'] );
	}
		
	// Get the rates
	$result['rates'] = $canada_tax->lumper_tax_rates( $_GET['code'] );
	$result['exempt'] = isset($changes['CDN_TAX_EXEMPT']) && $changes['CDN_TAX_EXEMPT'];

	// Get the company info and issues
	if( is_array($result) ) {
		$result['company'] = $company_tax->lumper_tax_info( $_GET['code'] );
		$result['issue'] = $company_tax->get_issue();
	}

	if( $sts_debug ) {
		echo "<p>result = </p>
		<pre>";
		var_dump($result);
		echo "</pre>";
	} else {
		echo json_encode( $result );
	}
}


?>

