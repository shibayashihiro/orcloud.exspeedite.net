<?php

// Set flag that this is a parent file
define( '_STS_INCLUDE', 1 );

// Setup Session
require_once( "../../include/sts_session_setup.php" );

require_once dirname(__FILE__) . '/config.php';

require_once( "include/sts_session_class.php" );

$my_session = sts_session::getInstance( $exspeedite_db, $sts_debug );
$my_session->access_check( EXT_GROUP_ADMIN );	// Make sure we should be here

//$sts_debug = true;

if( $multi_company && $sts_qb_multi && isset($_SESSION['SETUP_COMPANY']) ) {
	list($quickbooks_is_connected, $realm, $Context) =
		connect_to_quickbooks( $_SESSION['SETUP_COMPANY'] );
} else {
	list($quickbooks_is_connected, $realm, $Context) = connect_to_quickbooks();
}

if( $quickbooks_is_connected && isset($_POST) && isset($_POST["ITEM_TYPE"]) &&
	isset($_POST["ACCOUNT"]) ) {
	echo '<center><img src="../../images/loading.gif" alt="loading" width="125" height="125" /></center>';
	ob_flush(); flush();

	if( $sts_debug ) {
		echo "<pre>";
		var_dump($_POST);
		echo "</pre>";
	}

	$invoice_items = array();
	$bill_items = array();
	$item_comment = array();
	foreach( $sts_qb_settings as $row ) {
		if( strpos($row["SETTING_COMMENT"], "Invoice Item") !== false ) {
			$invoice_items[$row["SETTING"]] = $row["THE_VALUE"];
			$item_comment[$row["SETTING"]] = $row["SETTING_COMMENT"];
		} else if( strpos($row["SETTING_COMMENT"], "Bill Item") !== false ) {
			$bill_items[$row["SETTING"]] = $row["THE_VALUE"];
			$item_comment[$row["SETTING"]] = $row["SETTING_COMMENT"];
		}
	}
	
	$ItemService = new QuickBooks_IPP_Service_Item();
	
	foreach( ($_POST["ITEM_TYPE"] == "invoice" ? $invoice_items : $bill_items)
		as $setting => $value ) {
			
		$check = $ItemService->query($Context, $realm, "SELECT * FROM Item WHERE Name = '".$value."'");
				
		// If item exists, delete it.
		if(is_array($check) && count($check) == 1) {
			$Item = $check[0];
			$update = true;
		} else {
			$Item = new QuickBooks_IPP_Object_Item();
			$update = false;
		}

		$Item->setName($value);
		$Item->setType('Service');
		if( $_POST["ITEM_TYPE"] == "invoice" ) {
			$Item->setIncomeAccountRef($_POST["ACCOUNT"]);
			$Item->setDescription('Exspeedite: '.$item_comment[$setting]);
		} else {
			$Item->setExpenseAccountRef($_POST["ACCOUNT"]);
			$Item->setPurchaseDesc('Exspeedite: '.$item_comment[$setting]);
		}
		$Item->setTaxable(false);
		
		if( $sts_debug ) {
			echo "<pre>";
			var_dump($Item);
			echo "</pre>";
		}
		
		if( $update ) {
			//! Do not update an existing item, leave it as is.
			// After a discussion with Scott. Where we are working with customer's existing
			// items, we do not want to change which accounts they are set to.
			//$resp = $ItemService->update($Context, $realm, $Item->getId(), $Item);
			$resp = true;
			if( $sts_debug ) echo "<p>Existing item $value - NOT updated.</p>";
		} else {
			$resp = $ItemService->add($Context, $realm, $Item);
		}
		if( $sts_debug ) {
			echo "<pre>";
			var_dump($resp);
			echo "</pre>";
		}
		if ($resp) {
			if( $sts_debug ) echo '<p>Item ID is: [' . $resp . ']</p>';
		} else {
			if( $sts_debug ) {
				echo "<pre>";
				echo $ItemService->lastRequest(). "\n\n\n";
				echo $ItemService->lastResponse(). "\n\n\n";
				echo "</pre>";
				die($ItemService->lastError($Context));
			}
		}
	}
}

if( ! $sts_debug ) {
	echo "<script type=\"text/javascript\">
	window.location = \"setup3.php\"
</script>";
}	

?>