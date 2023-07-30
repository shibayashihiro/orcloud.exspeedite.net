<?php

// $Id: qb_online_api.php 5096 2023-05-14 23:07:01Z duncan $
// Quickbooks online API

// no direct access
defined('_STS_INCLUDE') or die('Restricted access');

require_once dirname(__FILE__) . '/config.php';
require_once( "sts_email_class.php" );
require_once( "sts_company_class.php" );

class sts_quickbooks_online_API
{
	private $debug;
	private $context;
	private $realm;
	private $setting_table;
	private $api_log;
	private $api_diag_level;	// Text
	private $diag_level;		// numeric version
	private $item_cache = array();
	private $term_cache = array();
	private $customer_cache = array();
	private $vendor_cache = array();
	private $tax_code_cache = array();
	private $account_cache;
	private $has_value;
	private $Invoice;
	private $line_number;
	private $extra_stops;
	private $company_table;
	private $detail;
	private $tax;
	private $tax_code_ref;
	
	public function __construct( $context, $realm, $database, $debug = false ) {
		global $sts_error_level_label, $sts_crm_dir;
		
		$this->debug = $debug;
		$this->database = $database;

		$this->setting_table = sts_setting::getInstance( $this->database, $this->debug );
		$this->api_log = $this->setting_table->get( 'api', 'QUICKBOOKS_LOG_FILE' );
		if( isset($this->api_log) && $this->api_log <> '' && 
			$this->api_log[0] <> '/' && $this->api_log[0] <> '\\' 
			&& $this->api_log[1] <> ':' )
			$this->api_log = $sts_crm_dir.$this->api_log;

		$this->api_diag_level =		$this->setting_table->get( 'api', 'QUICKBOOKS_DIAG_LEVEL' );
		$this->diag_level =  array_search(strtolower($this->api_diag_level), $sts_error_level_label);
		if( $this->diag_level === false ) $this->diag_level = EXT_ERROR_ALL;
		$this->extra_stops = $this->setting_table->get( 'option', 'CLIENT_EXTRA_STOPS' ) == 'true';
		$this->detail = $this->setting_table->get( 'api', 'QUICKBOOKS_DETAIL' ) == 'true';

		if( $this->debug ) echo "<p>Create sts_quickbooks_online_API<br>
			log=$this->api_log level=$this->diag_level ($this->api_diag_level)</p>";
		$this->context = $context;
		$this->realm = $realm;
		$this->company_table = sts_company::getInstance($this->database, $this->debug);
		$this->tax = sts_company_tax::getInstance($database, $debug);

	}

	// Allow re-use of objects - singleton function
	public static function getInstance( $context, $realm, $database, $debug = false ) {
		static $instance = null;
		$myclass = get_class ();
		if( $debug ) echo "<p>Get instance of $myclass</p>";
		if (null === $instance) {
			$instance = new $myclass( $context, $realm, $database, $debug );
		}
		return $instance;
    }
    
    public function change( $context, $realm ) {
		$this->context = $context;
		$this->realm = $realm;
    }

	private function hsc( $string, $max_length = 0 ) {
		return ($max_length > 0 ? substr($string,0,$max_length) : $string);
	}
	
	private function hsc30( $string ) {
		return substr($string,0,CUSTOM_FIELD_MAX_LENGTH);
	}
	
	public function log_event( $message, $level = EXT_ERROR_ERROR ) {
		if( $this->debug ) echo "<p>".__METHOD__.": $this->api_log, $message, level=$level</p>";
		
		if( $this->diag_level >= $level ) {
			if( (file_exists($this->api_log) && is_writable($this->api_log)) ||
				is_writable(dirname($this->api_log)) ) 
				file_put_contents($this->api_log, date('m/d/Y h:i:s A')." pid=".getmypid().
					" msg=".$message."\n\n", FILE_APPEND);
		}
	}

	// Check if a item exists, return the reference ID
	// $item_name = name of the item, see Quickbooks/*_ITEM settings
	public function item_query( $item_name ) {
	
		if( $this->debug ) echo "<p>".__METHOD__.": $item_name</p>";
		$result = false;
		
		if( is_array($this->item_cache) && isset($this->item_cache[$item_name])) {
			$result = $this->item_cache[$item_name];	// Found in the cache
			//$this->log_event( __METHOD__.": found item in cache $item_name = $result", EXT_ERROR_DEBUG);
		} else {
			$ItemService = new QuickBooks_IPP_Service_Item();
			
			$items = $ItemService->query($this->context, $this->realm, "SELECT * FROM Item WHERE Name = '".addslashes($item_name)."'");
		
			if( is_array($items) && count($items) == 1 ) {	// Item found
				$result = $items[0]->getId();
				$this->item_cache[$item_name] = $result; // Cache the results
				//$this->log_event( __METHOD__.": found item in QBOE $item_name = $result", EXT_ERROR_DEBUG);
			} else {
				if( $this->debug ) echo "<p>".__METHOD__.": failure: ".$ItemService->lastError($this->context)."</p>";
				$this->log_event( __METHOD__.": failure: ".$ItemService->lastError($this->context), EXT_ERROR_ERROR);
			}
		}
		
		if( $result == false ) {
			if( $this->debug ) echo "<p>".__METHOD__.": item $item_name found = false</p>";	
			$this->log_event( __METHOD__.": item $item_name found = false", EXT_ERROR_DEBUG);
			$email = sts_email::getInstance($this->database, $this->debug);
			$email->send_alert(__METHOD__.": item $item_name is missing in QBOE<br>".
				"Please run Setup Quickbooks Online Interface Step 3", EXT_ERROR_ERROR);
		} else {
			if( $this->debug ) echo "<p>".__METHOD__.": found item $item_name = $result</p>";
			$this->log_event( __METHOD__.": found item $item_name = $result", EXT_ERROR_DEBUG);
		}
	    return $result;
	}

	// Check if a term exists, return the reference ID
	// $term_name = name of the term, see api/QUICKBOOKS_INVOICE_TERMS setting
	public function term_query( $term_name ) {
	
		if( $this->debug ) echo "<p>".__METHOD__.": $term_name</p>";
		$result = false;
		
		if( is_array($this->term_cache) && isset($this->term_cache[$term_name])) {
			$result = $this->term_cache[$term_name];	// Found in the cache
			//$this->log_event( __METHOD__.": found item in cache $item_name = $result", EXT_ERROR_DEBUG);
		} else {
			$TermService = new QuickBooks_IPP_Service_Term();
			
			$terms = $TermService->query($this->context, $this->realm, "SELECT * FROM Term where Name = '".addslashes($term_name)."'");
	
			if( is_array($terms) ) {
				if( count($terms) == 0 ) {	// Term NOT found
					$result = $this->term_add( trim($term_name) );
				} else {
					$result = $terms[0]->getId();
				}
				if( $result != false )
					$this->term_cache[$term_name] = $result; // Cache the results
			} else {
				if( $this->debug ) echo "<p>".__METHOD__.": failure: ".$TermService->lastError($this->context)."</p>";
				$this->log_event( __METHOD__.": failure: ".$TermService->lastError($this->context), EXT_ERROR_ERROR);
			}
		}
			
		if( $result == false ) {
			if( $this->debug ) echo "<p>".__METHOD__.": term $term_name found = false</p>";	
			$this->log_event( __METHOD__.": term $term_name found = false", EXT_ERROR_DEBUG);
			$email = sts_email::getInstance($this->database, $this->debug);
			$email->send_alert(__METHOD__.": term $term_name is missing in QBOE<br>".
				"Please add this to Quickbooks Online<br>".
				(isset($TermService) ? $TermService->lastError($this->context) : ''), EXT_ERROR_ERROR);
		} else {
			if( $this->debug ) echo "<p>".__METHOD__.": found term $term_name = $result</p>";
			$this->log_event( __METHOD__.": found term $term_name = $result", EXT_ERROR_DEBUG);
		}
		
	    return $result;
	}

	// Add a term
	// $term_name = name of the term, see api/QUICKBOOKS_INVOICE_TERMS setting
	public function term_add( $term_name ) {
		global $shipment_table;

		if( $this->debug ) echo "<p>".__METHOD__.": $term_name</p>";
		$result = false;
		
		if( preg_match('/net\s(\d+)/', strtolower($term_name), $matches) ) {
			$days = (int) $matches[1];
		} else
			$days = 0;

		$TermService = new QuickBooks_IPP_Service_Term();
		
		$Term = new QuickBooks_IPP_Object_Term();
		$Term->setName($term_name);
		$Term->setDueDays($days);

		if ($result = $TermService->add($this->context, $this->realm, $Term))
		{
			if( $this->debug ) echo "<p>".__METHOD__.": success term $term_name = $result</p>";
			$this->log_event( __METHOD__.": success term $term_name = $result", EXT_ERROR_DEBUG);
		}
		else
		{
			if( $this->debug ) echo "<p>".__METHOD__.": failure: ".$TermService->lastError($this->context)."</p>";
			$this->log_event( __METHOD__.": failure: ".$TermService->lastError($this->context), EXT_ERROR_ERROR);
		}
		
	    return $result;
	}

	// Check if a tax agency exists, return the reference ID
	// $tax_agency_name = name of the tax agency
	public function tax_agency_query( $tax_agency_name ) {
	
		if( $this->debug ) echo "<p>".__METHOD__.": $tax_agency_name</p>";
		$result = false;
		
		$TaxAgencyService = new QuickBooks_IPP_Service_TaxAgency();
		
		if( $this->debug ) echo "<p>".__METHOD__.": query = "."SELECT * FROM TaxAgency WHERE Name = '".addslashes($tax_agency_name)."'"."</p>";
		$taxagencies = $TaxAgencyService->query($this->context, $this->realm,
			"SELECT * FROM TaxAgency WHERE Name = '".addslashes($tax_agency_name)."'");

		if( is_array($taxagencies) ) {
			if( count($taxagencies) == 0 ) {	// Tax agency NOT found
				$result = $this->tax_agency_add( trim($tax_agency_name) );
			} else {
				$result = $taxagencies[0]->getId();
			}
		} else {
			if( $this->debug ) echo "<p>".__METHOD__.": failure: ".$TaxAgencyService->lastError($this->context)."</p>";
		echo "<pre>";
		echo urldecode($TaxAgencyService->lastRequest());
		echo urldecode($TaxAgencyService->lastResponse());
		echo "</pre>";

			$this->log_event( __METHOD__.": failure: ".$TaxAgencyService->lastError($this->context), EXT_ERROR_ERROR);
		}
			
		if( $result == false ) {
			if( $this->debug ) echo "<p>".__METHOD__.": Tax agency $tax_agency_name found = false</p>";	
			$this->log_event( __METHOD__.": Tax agency $tax_agency_name found = false", EXT_ERROR_DEBUG);
		} else {
			if( $this->debug ) echo "<p>".__METHOD__.": found tax agency $tax_agency_name = $result</p>";
			$this->log_event( __METHOD__.": found tax agency $tax_agency_name = $result", EXT_ERROR_DEBUG);
		}
		
	    return $result;
	}

	// Add a tax_agency
	// $tax_agency_name = name of the tax agency
	public function tax_agency_add( $tax_agency_name ) {

		if( $this->debug ) echo "<p>".__METHOD__.": $tax_agency_name</p>";
		$result = false;
		
		$TaxAgencyService = new QuickBooks_IPP_Service_TaxAgency();
		
		$TaxAgency = new QuickBooks_IPP_Object_TaxAgency();
		$TaxAgency->setDisplayName($tax_agency_name);
		$TaxAgency->setTaxRegistrationNumber("12345678");
		$TaxAgency->setTaxTrackedOnSales(true);

		if ($result = $TaxAgencyService->add($this->context, $this->realm, $TaxAgency)) {
			if( $this->debug ) echo "<p>".__METHOD__.": success term $tax_agency_name = $result</p>";
			$this->log_event( __METHOD__.": success term $tax_agency_name = $result", EXT_ERROR_DEBUG);
		} else {
			if( $this->debug ) echo "<p>".__METHOD__.": failure: ".$TaxAgencyService->lastError($this->context)."</p>";
			$this->log_event( __METHOD__.": failure: ".$TaxAgencyService->lastError($this->context), EXT_ERROR_ERROR);
		}
		
	    return $result;
	}

	//! SCR# 257 - Check if a tax_code exists, return the reference ID
	// $tax_code_name = name of the tax_code, see:
	// quickbooks-php-master/online/process_queue.php?debug&test3
	public function tax_code_query( $tax_code_name ) {
	
		if( $this->debug ) echo "<p>".__METHOD__.": $tax_code_name</p>";
		$result = false;
		
		if( is_array($this->tax_code_cache) && isset($this->tax_code_cache[$tax_code_name])) {
			$result = $this->tax_code_cache[$tax_code_name];	// Found in the cache
			//$this->log_event( __METHOD__.": found item in cache $item_name = $result", EXT_ERROR_DEBUG);
		} else {
			$TaxCodeService = new QuickBooks_IPP_Service_TaxCode();
	
			$taxcodes = $TaxCodeService->query($this->context, $this->realm,
				"SELECT * FROM TaxCode WHERE Name = '".addslashes($tax_code_name)."' and Active = true");

			if( is_array($taxcodes) && count($taxcodes) == 1 ) {	// tax_code found
				$result = $taxcodes[0]->getId();
				$this->tax_code_cache[$tax_code_name] = $result; // Cache the results
				//$this->log_event( __METHOD__.": found tax_code in QBOE $tax_code_name = $result", EXT_ERROR_DEBUG);
			} else {
				if( $this->debug ) echo "<p>".__METHOD__.": failure: ".$TaxCodeService->lastError($this->context)."</p>";
				$this->log_event( __METHOD__.": failure: ".$TaxCodeService->lastError($this->context), EXT_ERROR_ERROR);
			}
		}
		
		if( $result == false ) {
			if( $this->debug ) echo "<p>".__METHOD__.": tax_code $tax_code_name found = false</p>";	
			$this->log_event( __METHOD__.": tax_code $tax_code_name found = false", EXT_ERROR_DEBUG);
			$email = sts_email::getInstance($this->database, $this->debug);
			$email->send_alert(__METHOD__.": tax_code $tax_code_name is missing or inactive in QBOE<br>".
				"Please go to Quickbooks Online, click 'Taxes' on the left,<br>
				then click 'Manage sales tax' on top right, then add the tax rate", EXT_ERROR_ERROR);
		} else {
			if( $this->debug ) echo "<p>".__METHOD__.": found tax_code $tax_code_name = $result</p>";
			$this->log_event( __METHOD__.": found tax_code $tax_code_name = $result", EXT_ERROR_DEBUG);
		}
	    return $result;
	}

	// Find the discount account ref, return the reference ID
	public function discount_account_query() {
	
		if( $this->debug ) echo "<p>".__METHOD__.": entry</p>";
		$result = false;
		
		if( is_array($this->account_cache) ) {
			$result = $this->account_cache;	// Found in the cache
			//$this->log_event( __METHOD__.": found item in cache $item_name = $result", EXT_ERROR_DEBUG);
		} else {
			$AccountService = new QuickBooks_IPP_Service_Account();
					
			$accounts = $AccountService->query($this->context, $this->realm, 
				"select * from Account where Name Like '%discount%'
				and Classification = 'Revenue'
				and Active = true
				order by Id");
		
			if( is_array($accounts) && count($accounts) > 0 ) {	// Account found
				$result = $accounts[0]->getId();
				$this->account_cache = $result;  // Cache the results
			} else {
				if( $this->debug ) echo "<p>".__METHOD__.": failure: ".$AccountService->lastError($this->context)."</p>";
				$this->log_event( __METHOD__.": failure: ".$AccountService->lastError($this->context), EXT_ERROR_ERROR);
			}
		}
		
		if( $result == false ) {
			if( $this->debug ) echo "<p>".__METHOD__.": found = false</p>";	
			$this->log_event( __METHOD__.": found = false", EXT_ERROR_DEBUG);
		} else {
			if( $this->debug ) echo "<p>".__METHOD__.": found account $result</p>";
			$this->log_event( __METHOD__.": found account $result", EXT_ERROR_DEBUG);
		}
	    return $result;
	}

	// Check if a customer exists, prior to adding an invoice
	// $ID = SHIPMENT_CODE of the invoice
	public function customer_query( $ID ) {
		global $shipment_table;
	
		if( $this->debug ) echo "<p>".__METHOD__.": $ID</p>";
		$result = false;
		$check = $shipment_table->fetch_rows("SHIPMENT_CODE = ".$ID);
		
		if( is_array($check) && count($check) == 1 && isset($check[0]['BILLTO_NAME']) &&
			isset($check[0]['BILLING_STATUS']) && in_array(
				$shipment_table->billing_state_behavior[$check[0]['BILLING_STATUS']],
				array('approved', 'billed') ) ) {
			
			$full_name = $this->hsc($check[0]['BILLTO_NAME'], NAME_FIELD_MAX_LENGTH);
			if( $this->debug ) echo "<p>".__METHOD__.": look for $full_name</p>";
			$this->log_event( __METHOD__.": look for $full_name", EXT_ERROR_DEBUG);
	
			if( is_array($this->customer_cache) && isset($this->customer_cache[$full_name])) {
				$result = $this->customer_cache[$full_name];	// Found in the cache
			} else {
				$CustomerService = new QuickBooks_IPP_Service_Customer();
				
				$customers = $CustomerService->query($this->context, $this->realm,
					"SELECT * FROM Customer WHERE DisplayName = '".addslashes($full_name)."'");
	
				if( is_array($customers) ) {
					if( count($customers) == 0 ) {	// Customer NOT found
						$result = $this->customer_add( $ID );
					} else {
						$result = $customers[0]->getId();
					}
					if( $result != false )
						$this->customer_cache[$full_name] = $result; // Cache the results
				} else {
					if( $this->debug ) echo "<p>".__METHOD__.": failure: ".$CustomerService->lastError($this->context)."</p>";
					$this->log_event( __METHOD__.": failure: ".$CustomerService->lastError($this->context), EXT_ERROR_ERROR);
				}
			}
				
			if( $result == false ) {
				if( $this->debug ) echo "<p>".__METHOD__.": customer $full_name found = false</p>";	
				$this->log_event( __METHOD__.": customer $full_name found = false", EXT_ERROR_DEBUG);
			} else {
				if( $this->debug ) echo "<p>".__METHOD__.": found customer $full_name = $result</p>";
				$this->log_event( __METHOD__.": found customer $full_name = $result", EXT_ERROR_NOTICE);
				$shipment_table->update($ID, array("quickbooks_listid_customer" => ((string)$result), "quickbooks_status_message" => "Found Customer in QB"), false );
				// Add invoice here
				if( $existing = $this->invoice_query( $ID ) ) {
					if( $this->debug ) echo "<p>".__METHOD__.": invoice for $ID already exists $existing</p>";	
					$shipment_table->update($ID, array("quickbooks_status_message" => 
						"Error - invoice for $ID already exists in QuickBooks",
						"quickbooks_txnid_invoice" => $existing), false );
					$this->log_event( __METHOD__.": invoice for $ID already exists $existing", EXT_ERROR_ERROR);
					$result3 = $shipment_table->change_state_behavior( $ID, 'billed', true );
				} else {
					$result = $this->invoice_add( $ID );
					if( ! $result ) {
						$this->log_event( __METHOD__.": unable to add invoice $ID", EXT_ERROR_ERROR);
					}
				}
			}
	
		} else {
			$shipment_table->update($ID, array("quickbooks_status_message" => 
				"Error - not approved/billed or not found $ID"), false );
			$this->log_event( __METHOD__.": not approved or not found $ID", EXT_ERROR_ERROR);
		}
	
	    return $result;
	}
	
	// Add a customer
	// $ID = SHIPMENT_CODE of the invoice
	public function customer_add( $ID ) {
		global $shipment_table;

		if( $this->debug ) echo "<p>".__METHOD__.": $ID</p>";
		$this->log_event( __METHOD__.": entry, $ID", EXT_ERROR_DEBUG);
		$result = false;
		$check = $shipment_table->fetch_rows("SHIPMENT_CODE = ".$ID);
		if( is_array($check) && count($check) == 1 && isset($check[0]['BILLTO_NAME']) ) {
			$customer = $check[0];
			if( $this->debug ) echo "<p>".__METHOD__.": Going to add ".$check[0]['BILLTO_NAME']."</p>";
			$this->log_event( __METHOD__.": Going to add ".$check[0]['BILLTO_NAME'], EXT_ERROR_DEBUG);
	 
			$CustomerService = new QuickBooks_IPP_Service_Customer();
			
			$Customer = new QuickBooks_IPP_Object_Customer();
			$Customer->setDisplayName($this->hsc($customer['BILLTO_NAME'], NAME_FIELD_MAX_LENGTH));
			$Customer->setFullyQualifiedName($this->hsc($customer['BILLTO_NAME'], NAME_FIELD_MAX_LENGTH));
			$Customer->setCompanyName($this->hsc($customer['BILLTO_NAME'], NAME_FIELD_MAX_LENGTH));
			$Customer->setPrintOnCheckName($this->hsc($customer['BILLTO_NAME'], NAME_FIELD_MAX_LENGTH));
			
			// Terms (e.g. Net 30, etc.)
			//$Customer->setSalesTermRef(4);
			
			// Phone #
			if( isset($customer['BILLTO_PHONE']) && $customer['BILLTO_PHONE'] <> '') {
				$PrimaryPhone = new QuickBooks_IPP_Object_PrimaryPhone();
				$PrimaryPhone->setFreeFormNumber($this->hsc(
					(isset($customer['BILLTO_PHONE']) ? $customer['BILLTO_PHONE'] :
					''. (isset($customer['BILLTO_EXT']) && $customer['BILLTO_EXT'] <> '' ?
						' x '.$customer['BILLTO_EXT'] : '')), PHONE_FIELD_MAX_LENGTH));
				$Customer->setPrimaryPhone($PrimaryPhone);
			}
			
			// Mobile #
			if( isset($customer['BILLTO_CELL']) && $customer['BILLTO_CELL'] <> '') {
				$Mobile = new QuickBooks_IPP_Object_Mobile();
				$Mobile->setFreeFormNumber($this->hsc($customer['BILLTO_CELL'], PHONE_FIELD_MAX_LENGTH));
				$Customer->setMobile($Mobile);
			}
			
			// Fax #
			if( isset($customer['BILLTO_FAX']) && $customer['BILLTO_FAX'] <> '') {
				$Fax = new QuickBooks_IPP_Object_Fax();
				$Fax->setFreeFormNumber($this->hsc($customer['BILLTO_FAX'], PHONE_FIELD_MAX_LENGTH));
				$Customer->setFax($Fax);
			}
			
			// Bill address
			$BillAddr = new QuickBooks_IPP_Object_BillAddr();
			$BillAddr->setLine1($this->hsc(isset($customer['BILLTO_ADDR1']) ? $customer['BILLTO_ADDR1'] : '', ADDR_FIELD_MAX_LENGTH));
			if( isset($customer['BILLTO_ADDR2']) )
				$BillAddr->setLine2($this->hsc($customer['BILLTO_ADDR2'], ADDR_FIELD_MAX_LENGTH));
			$BillAddr->setCity($this->hsc(isset($customer['BILLTO_CITY']) ? $customer['BILLTO_CITY'] : '', CITY_FIELD_MAX_LENGTH));
			$BillAddr->setCountrySubDivisionCode($this->hsc(isset($customer['BILLTO_STATE']) ? $customer['BILLTO_STATE'] : '', STATE_FIELD_MAX_LENGTH));
			$BillAddr->setPostalCode($this->hsc(isset($customer['BILLTO_ZIP']) ? $customer['BILLTO_ZIP'] : '', POSTAL_FIELD_MAX_LENGTH));
			$BillAddr->setCountry('US');
			$Customer->setBillAddr($BillAddr);
			
			// Email
			if( isset($customer['BILLTO_EMAIL']) ) {
				$PrimaryEmailAddr = new QuickBooks_IPP_Object_PrimaryEmailAddr();
				$PrimaryEmailAddr->setAddress($this->hsc($customer['BILLTO_EMAIL'], EMAIL_FIELD_MAX_LENGTH));
				$Customer->setPrimaryEmailAddr($PrimaryEmailAddr);
			}
			
			// Others
			$Customer->setPreferredDeliveryMethod('Print');
			
			if ($result = $CustomerService->add($this->context, $this->realm, $Customer))
			{
				if( $this->debug ) echo "<p>".__METHOD__.": ".$customer['BILLTO_NAME']." success $result</p>";
				$this->log_event( __METHOD__.": ".$customer['BILLTO_NAME']." success $result", EXT_ERROR_NOTICE);
			}
			else
			{
				$shipment_table->update($ID, array("quickbooks_status_message" => 
					$shipment_table->trim_to_fit( "quickbooks_status_message", 
					"failure: ".$CustomerService->lastError($this->context) ) ), false );
				if( $this->debug ) echo "<p>".__METHOD__.": failure: ".$CustomerService->lastError($this->context)."</p>";
			$this->log_event( __METHOD__.": failure: ".$CustomerService->lastError($this->context), EXT_ERROR_ERROR);
			}


		} else {
			$shipment_table->update($ID, array("quickbooks_status_message" => 
				$shipment_table->trim_to_fit( "quickbooks_status_message", 
				"Error - CA customer info not found." ) ), false );
			if( $this->debug ) echo "<p>".__METHOD__.": failure: Contact info not found</p>";
			$this->log_event( __METHOD__.": failure: Contact info not found", EXT_ERROR_ERROR);
		}
		
		return $result;
	}	
	
	// Check if a invoice exists, return the reference ID
	// $ID = name of the invoice / Exspeedite SHIPMENT_CODE
	public function invoice_query( $ID ) {
		
		if( $this->debug ) echo "<p>".__METHOD__.": $ID</p>";
		$result = false;
		$prefix = $this->company_table->prefix( 'invoice', $ID );
		
		$InvoiceService = new QuickBooks_IPP_Service_Invoice();
		
		$invoices = $InvoiceService->query($this->context, $this->realm, "SELECT * FROM Invoice WHERE DocNumber = '".$prefix.$ID."'");
	
		if( is_array($invoices) && count($invoices) == 1 ) {	// Invoice found
			$result = $invoices[0]->getId();
			if( $this->debug ) {
				echo "<pre>";
				var_dump($invoices[0]);
				echo "</pre>";
			}
		}
		
		if( $result == false ) {
			if( $this->debug ) echo "<p>".__METHOD__.": invoice ".$prefix.$ID." found = false</p>";	
			$this->log_event( __METHOD__.": invoice ".$prefix.$ID." found = false", EXT_ERROR_DEBUG);
		} else {
			if( $this->debug ) echo "<p>".__METHOD__.": found invoice ".$prefix.$ID." = $result</p>";
			$this->log_event( __METHOD__.": found invoice ".$prefix.$ID." = $result", EXT_ERROR_NOTICE);
		}
	    return $result;
	}

	// Add notes for an EDI invoice
	// $ID = SHIPMENT_CODE of the invoice
	public function invoice_add_edi_notes( $ID ) {
		global $shipment_table;
		$result = false;
		
		$check = $shipment_table->fetch_rows("SHIPMENT_CODE = ".$ID, 
			"EDI_204_PRIMARY, EDI_204_ORIGIN, EDI_204_B204_SID,
			EDI_204_B206_PAYMENT, LOAD_CODE" );
			
		//! Find out if we have an EDI shipment.
		if( is_array($check) && count($check) == 1 && isset($check[0]["EDI_204_PRIMARY"]) &&
			intval($check[0]["EDI_204_PRIMARY"]) == intval($ID) && 
			isset($check[0]["LOAD_CODE"])) {
				
			$stops = $shipment_table->database->get_multiple_rows("
				SELECT SEQUENCE_NO, STOP_TYPE, STOP_DISTANCE, ACTUAL_ARRIVE, ACTUAL_DEPART,
					(CASE STOP_TYPE
						WHEN 'PICK' THEN SHIPPER_NAME
						WHEN 'DROP' THEN CONS_NAME
						ELSE STOP_NAME END) AS NAME, 
					(CASE STOP_TYPE
						WHEN 'pick' THEN SHIPPER_CITY
						WHEN 'drop' THEN CONS_CITY
						ELSE STOP_CITY END) AS CITY, 
					(CASE STOP_TYPE
						WHEN 'pick' THEN SHIPPER_STATE
						WHEN 'drop' THEN CONS_STATE
						ELSE STOP_STATE END) AS STATE
				FROM EXP_STOP, EXP_SHIPMENT
				WHERE EXP_STOP.LOAD_CODE = ".$check[0]["LOAD_CODE"]."
				AND COALESCE(STOP_DISTANCE, 1) > 0
				AND STOP_TYPE <> 'stop'
				AND SHIPMENT_CODE = SHIPMENT
				ORDER BY SEQUENCE_NO ASC" );
			
			if( is_array($stops) && count($stops) > 0 ) {
				$result = '';
				$items = array();
				if( isset($check[0]["EDI_204_B204_SID"]))
					$items[] = 'EDI Shipment ID: '.$check[0]["EDI_204_B204_SID"];
				if( isset($check[0]["EDI_204_ORIGIN"]))
					$items[] = '204 From: '.$check[0]["EDI_204_ORIGIN"];
				if( isset($check[0]["EDI_204_B206_PAYMENT"]))
					$items[] = $check[0]["EDI_204_B206_PAYMENT"];
				if( count($items))
					$result .= implode(', ', $items)."\n\n";
				
				foreach($stops as $stop) {
					$result .= 'Stop '.$stop["SEQUENCE_NO"].': '.$stop["STOP_TYPE"].', '.
						$stop["NAME"].', '.$stop["CITY"].', '.$stop["STATE"]."\n";
				}
				$result .= "\n";
			}
		}
		
		return $result;
	}

	private function invoice_add_comment( $comment ) {
		if( $this->debug ) echo "<p>".__METHOD__.": $comment</p>";
		$this->log_event( __METHOD__.": $comment", EXT_ERROR_DEBUG);

		$Line = new QuickBooks_IPP_Object_Line();
		$Line->setDetailType('DescriptionOnly');
		$Line->setDescription($comment);
		$Line->setLineNum($this->line_number++);
		$this->Invoice->addLine($Line);
	}

	private function invoice_add_sales_line( $type, $description, $amount,
		$quantity = 1, $unit_price = 0 ) {
		if( $this->debug ) echo "<p>".__METHOD__.": $description $amount $quantity $unit_price</p>";
		$this->log_event( __METHOD__.": $description $amount $quantity $unit_price", EXT_ERROR_DEBUG);

		$billing_item = $this->item_query( $type );
		if( $billing_item && $amount <> 0 ) {
			$this->has_value = true;
			$Line = new QuickBooks_IPP_Object_Line();
			$Line->setDetailType('SalesItemLineDetail');
			$Line->setAmount(number_format((float) $amount, 2,".",""));
			$Line->setDescription($description);
			$Line->setLineNum($this->line_number++);
			
			$SalesItemLineDetail = new QuickBooks_IPP_Object_SalesItemLineDetail();
			$SalesItemLineDetail->setItemRef($billing_item);
			if( $unit_price > 0 )
				$SalesItemLineDetail->setUnitPrice(number_format((float) $unit_price, 2,".",""));
			else if( $unit_price == 0 )
				$SalesItemLineDetail->setUnitPrice((float) $amount / $quantity);
			$SalesItemLineDetail->setQty($quantity);
			
			//! SCR# 257 - add tax ref
			if( $this->tax_code_ref != false )
				$SalesItemLineDetail->setTaxCodeRef($this->tax_code_ref);
			
			$Line->addSalesItemLineDetail($SalesItemLineDetail);
			
			$this->Invoice->addLine($Line);
		}
	}

	
	// Add one shipment to an existing invoice.
	// Add description lines and billing lines.
	public function invoice_shipment_add( $ID ) {
		global $shipment_table, $billing_table, $rates_table, $detail_table;

		if( $this->debug ) echo "<p>".__METHOD__.": $ID</p>";
		$this->log_event( __METHOD__.": ID = $ID", EXT_ERROR_DEBUG);
		$result = false;

		$check = $shipment_table->fetch_rows("SHIPMENT_CODE = ".$ID, "*,
			(SELECT DATE(ACTUAL_ARRIVE) FROM EXP_STOP
			WHERE SHIPMENT = SHIPMENT_CODE
			AND STOP_TYPE = 'drop') AS ACTUAL_DELIVERY,
			(SELECT DATE(ACTUAL_DEPART) FROM EXP_STOP
			WHERE SHIPMENT = SHIPMENT_CODE
			AND STOP_TYPE = 'pick') AS ACTUAL_DEPART,
			
			(SELECT MIN(DATE(CREATED_DATE)) FROM EXP_STATUS
			WHERE ORDER_CODE = SHIPMENT_CODE AND SOURCE_TYPE = 'shipment'
			AND STATUS_STATE = (SELECT STATUS_CODES_CODE FROM EXP_STATUS_CODES
				WHERE SOURCE_TYPE = 'shipment' AND behavior = 'picked') ) AS PICKED_DATE,
	
			(SELECT MIN(DATE(CREATED_DATE)) FROM EXP_STATUS
			WHERE ORDER_CODE = SHIPMENT_CODE AND SOURCE_TYPE = 'shipment'
			AND STATUS_STATE = (SELECT STATUS_CODES_CODE FROM EXP_STATUS_CODES
				WHERE SOURCE_TYPE = 'shipment' AND behavior = 'dropped') ) AS DROPPED_DATE");
				
		$details = $shipment_table->database->get_multiple_rows(
			"select DETAIL_CODE, SHIPMENT_CODE,
				(SELECT COMMODITY_NAME from EXP_COMMODITY X 
				WHERE X.COMMODITY_CODE = EXP_DETAIL.COMMODITY  LIMIT 0 , 1) AS COMMODITY_NAME,
				(SELECT COMMODITY_DESCRIPTION from EXP_COMMODITY X 
				WHERE X.COMMODITY_CODE = EXP_DETAIL.COMMODITY  LIMIT 0 , 1) AS COMMODITY_DESCRIPTION,
				PALLETS, PIECES, 
				(SELECT UNIT_NAME from EXP_UNIT X 
				WHERE X.UNIT_CODE = EXP_DETAIL.PIECES_UNITS AND X.UNIT_TYPE = 'item' LIMIT 0 , 1) AS PIECES_UNITS,
				WEIGHT, DANGEROUS_GOODS, TEMP_CONTROLLED, BILLABLE
			from EXP_DETAIL 
			where SHIPMENT_CODE = ".$ID);
		
		if( is_array($check) && count($check) == 1 ) {

			if( $this->debug ) echo "<p>".__METHOD__.": $ID before invoice_add_edi_notes</p>";
			if( $edi_notes = $this->invoice_add_edi_notes( $ID ) ) {
				$notes = $edi_notes;
			} else {
				//! Shipper info
				$notes = "SHIPMENT# ".$ID."\n".
					"Shipped from:\n".$check[0]['SHIPPER_NAME'].", ".
				format_addr( $check[0]['SHIPPER_ADDR1'], $check[0]['SHIPPER_ADDR2'], $check[0]['SHIPPER_CITY'], $check[0]['SHIPPER_STATE'], $check[0]['SHIPPER_ZIP'], 'US', ", " )."\n";
				
				//! Repeat of shipping info for CSPH 
				$notes .= "Shipped to:\n".$check[0]['CONS_NAME'].", ".
				format_addr( $check[0]['CONS_ADDR1'], $check[0]['CONS_ADDR2'], $check[0]['CONS_CITY'], $check[0]['CONS_STATE'], $check[0]['CONS_ZIP'], 'US', ", " )."\n";
			}
			
			//! PO Numbers
			$pos = array('PO_NUMBER', 'PO_NUMBER2', 'PO_NUMBER3', 'PO_NUMBER4', 'PO_NUMBER5');
			$got_po = false;
			foreach($pos as $po) {
				if( isset($check[0][$po]) && $check[0][$po] <> '' ) {
					if( ! $got_po ) {
						$got_po = true;
						$notes .= "PO#s: ";
					} else {
						$notes .= ", ";
					}
					$notes .= $check[0][$po]."\n";
				}
			}
			
			$rc = false;
			//! REF_NUMBER
			if( isset($check[0]['REF_NUMBER']) && $check[0]['REF_NUMBER'] <> '' ){
				$notes .= "Reference#: ".$check[0]['REF_NUMBER'];
				$rc = true;
			}
			
			//! CONSOLIDATE_NUM
			if( isset($check[0]['CONSOLIDATE_NUM']) && $check[0]['CONSOLIDATE_NUM'] <> '' ) {
				$notes .= ' '.$this->setting_table->get( 'option', 'CONSOLIDATE_NUM' ).": ".$check[0]['CONSOLIDATE_NUM'];
				$rc = true;
			}
			
			if( $rc )
				$notes .= "\n";
			
			//! BOL
			if( isset($check[0]['BOL_NUMBER']) && $check[0]['BOL_NUMBER'] <> '' )
				$notes .= "BOL#: ".$check[0]['BOL_NUMBER'];
			
			//! Load/Trip#
			if( isset($check[0]['LOAD_CODE']) && intval($check[0]['LOAD_CODE']) > 0 )
				$notes .= "\nLoad/Trip#: ".$check[0]['LOAD_CODE'];

			//! SCR# 702 - Get equipment required for a shipment
			$equipment = $shipment_table->get_equipment_req( $ID );
			if( ! empty($equipment))
				$notes .= "\nEquipment: ".$equipment;
			
			//! F&S#
			if( isset($check[0]['FS_NUMBER']) && $check[0]['FS_NUMBER'] <> '' )
				$notes .= "\nF&S#: ".$check[0]['FS_NUMBER'];
				
			//$this->log_event( __METHOD__.": ID = $ID, Notes\n".$notes, EXT_ERROR_DEBUG);
			$this->invoice_add_comment( $notes );
			
			//! SCR# 225 - list extra stops
			if( $this->extra_stops ) {
				$extras = $shipment_table->database->get_multiple_rows(
					"SELECT T.SEQUENCE_NO, T.STOP_NAME, T.STOP_ADDR1, T.STOP_CITY, T.STOP_STATE, T.STOP_ZIP
					FROM EXP_SHIPMENT S, EXP_STOP T
					WHERE S.SHIPMENT_CODE = $ID
					AND (SELECT COUNT(T.SHIPMENT_CODE) FROM EXP_SHIPMENT T
					WHERE T.LOAD_CODE = S.LOAD_CODE) = 1
					AND (SELECT COUNT(T.STOP_CODE) FROM EXP_STOP T
					WHERE T.LOAD_CODE = S.LOAD_CODE) > 2
					AND (SELECT C.CLIENT_EXTRA_STOPS FROM EXP_CLIENT C
					WHERE C.CLIENT_CODE = S.BILLTO_CLIENT_CODE)
		            AND T.LOAD_CODE = S.LOAD_CODE
		            AND T.STOP_TYPE NOT IN ('pick', 'drop')
		            ORDER BY T.SEQUENCE_NO ASC" );
				if( is_array($extras) && count($extras) > 0 ) {
					foreach( $extras as $extra_row ) {
						$this->invoice_add_comment( "Stop: ".$extra_row["SEQUENCE_NO"].": ".
							 $extra_row["STOP_NAME"].", ".$extra_row["STOP_ADDR1"].", ".
							 $extra_row["STOP_CITY"].", ".$extra_row["STOP_STATE"].", ".
							 $extra_row["STOP_ZIP"]);
					}
			
				}
			}


			$items_shipped = "Items Shipped:";
			
			//$this->log_event( __METHOD__.": ID = $ID, detail = ".($this->detail ? "true" : "false")." (".count($details)." details)", EXT_ERROR_DEBUG);
			if( $this->detail && 
				is_array($details) && count($details) > 0 ) {
				//$this->log_event( __METHOD__.": Details:\n".print_r($details, true), EXT_ERROR_DEBUG);

				foreach( $details as $detail ) {
					$desc = $detail['COMMODITY_DESCRIPTION'].' ('.$detail['COMMODITY_NAME'].') ';
					if( isset($detail['PALLETS']) && $detail['PALLETS'] > 0 )
						$desc .= $detail['PALLETS'].' pallets ';
					if( isset($detail['PIECES']) && $detail['PIECES'] > 0 )
						$desc .= $detail['PIECES'].' items ';
					
					if( isset($check[0]['DISTANCE']) && $check[0]['DISTANCE'] > 0 )
						$desc .= $check[0]['DISTANCE'].' miles ';

					if( isset($check[0]['WEIGHT']) && $check[0]['WEIGHT'] > 0 )
						$desc .= $check[0]['WEIGHT'].' LBs ';

					$items_shipped .= "\n".$desc;
				}
				$this->log_event( __METHOD__.": detail $items_shipped", EXT_ERROR_DEBUG);
				$this->invoice_add_comment( $this->hsc($items_shipped) );
			}
				
			$result2 = $billing_table->fetch_rows("SHIPMENT_ID = ".$ID);
			if( is_array($result2) && count($result2) == 1 && isset($result2[0]['TOTAL']) ) {
				$this->log_event( __METHOD__.": got result2", EXT_ERROR_DEBUG);
				$billing_pallets		= intval($result2[0]['PALLETS']);
				$billing_per_pallets	= floatval($result2[0]['PER_PALLETS']);
				$billing_amount_pallets	= floatval($result2[0]['PALLETS_RATE']);
				$billing_hand_pallet	= floatval($result2[0]['HAND_PALLET']);
				$billing_handling		= floatval($result2[0]['HAND_CHARGES']);
				$billing_freight		= floatval($result2[0]['FREIGHT_CHARGES']);
				$billing_extra			= floatval($result2[0]['EXTRA_CHARGES']);
				
				$billing_loading_free_hrs	= floatval($result2[0]['FREE_DETENTION_HOUR']);
				$billing_loading_hrs		= floatval($result2[0]['DETENTION_HOUR']);
				$billing_loading_rate		= floatval($result2[0]['RATE_PER_HOUR']);
				$billing_loading			= floatval($result2[0]['DETENTION_RATE']);
				
				$billing_unloading_free_hrs	= floatval($result2[0]['FREE_UN_DETENTION_HOUR']);
				$billing_unloading_hrs		= floatval($result2[0]['UNLOADED_DETENTION_HOUR']);
				$billing_unloading_rate		= floatval($result2[0]['UN_RATE_PER_HOUR']);
				$billing_unloading			= floatval($result2[0]['UNLOADED_DETENTION_RATE']);
				
				$billing_cod			= floatval($result2[0]['COD']);
				$billing_mileage		= floatval($result2[0]['MILLEAGE']);
				$billing_fsc_rate		= floatval($result2[0]['FSC_AVERAGE_RATE']);
				$billing_mileage_rate	= floatval($result2[0]['RPM']);
				$billing_mileage_total	= floatval($result2[0]['RPM_RATE']);
				$billing_surcharge		= floatval($result2[0]['FUEL_COST']);
	
				$billing_stopoff		= floatval($result2[0]['STOP_OFF']);
				$billing_stopoff_note	= $result2[0]['STOP_OFF_NOTE'];
				$billing_weekend		= floatval($result2[0]['WEEKEND']);
				
				$billing_adjustment_title	= $result2[0]['ADJUSTMENT_CHARGE_TITLE'];
				$billing_adjustment_charge	= floatval($result2[0]['ADJUSTMENT_CHARGE']);
	
				$selection_fee	= floatval($result2[0]['SELECTION_FEE']);
				$discount		= floatval($result2[0]['DISCOUNT']);
				
				$billing_id				= $result2[0]['CLIENT_BILLING_ID'];
				

				
				if( $billing_pallets > 0 && $billing_per_pallets > 0 ) {
					$has_value = true;
					if( $billing_amount_pallets <> $billing_pallets * $billing_per_pallets ) {
						$this->invoice_add_sales_line( EXP_PALLETS_ITEM, $billing_pallets.' Pallets', $billing_amount_pallets );
					} else {
						$this->invoice_add_sales_line( EXP_PALLETS_ITEM, 'Pallets', $billing_amount_pallets,
							$billing_pallets, $billing_per_pallets );
					}
				} else if( $billing_amount_pallets > 0 ) {
					$this->invoice_add_sales_line( EXP_PALLETS_ITEM, 'Pallets', $billing_amount_pallets,
						$billing_pallets > 0  ? $billing_pallets : '1', -1 );
				}

				//! Pallet Handling charges
				$this->invoice_add_sales_line( EXP_HANDLING_PALLET_ITEM, 'Pallet Handling charges',
					$billing_hand_pallet );
				
				//! Freight charges
				$this->invoice_add_sales_line( EXP_FREIGHT_ITEM, 'Freight charges', $billing_freight );
								
				//! Handling charges
				$this->invoice_add_sales_line( EXP_HANDLING_ITEM, 'Handling charges', $billing_handling );
				
				//! Billing adjustment				
				$this->invoice_add_sales_line( EXP_FREIGHT_ITEM, isset($billing_adjustment_title) && $this->hsc($billing_adjustment_title) <> '' ? $billing_adjustment_title : 'Adjustment',
					$billing_adjustment_charge );
				
				//! Extra charges
				$this->invoice_add_sales_line( EXP_EXTRA_ITEM, 'Extra charges', $billing_extra );
								
				//! Loading / Unloading detention
				// Assume IF $billing_loading > 0 THEN $billing_loading_hrs > $billing_loading_free_hrs
				$billable_hrs = $billing_loading_hrs - $billing_loading_free_hrs;
				if( $billable_hrs < 1 ) $billable_hrs = 1;
				
				if( $billing_loading == $billable_hrs * ($billing_loading_rate > 0 ? $billing_loading_rate : 0) )
					$this->invoice_add_sales_line( EXP_LOADING_ITEM, 'Loading Detention', $billing_loading, $billable_hrs, $billing_loading_rate > 0 ? $billing_loading_rate : 0 );
				else
					$this->invoice_add_sales_line( EXP_LOADING_ITEM, 'Loading Detention', $billing_loading );
								
				$billable_hrs = $billing_unloading_hrs - $billing_unloading_free_hrs;
				if( $billable_hrs < 1 ) $billable_hrs = 1;
				if( $billing_unloading == $billable_hrs * ($billing_unloading_rate > 0 ? $billing_loading_rate : 0) )
					$this->invoice_add_sales_line( EXP_UNLOADING_ITEM, 'Unloading Detention', $billing_unloading, $billable_hrs, $billing_unloading_rate > 0 ? $billing_unloading_rate : 0 );
				else
					$this->invoice_add_sales_line( EXP_UNLOADING_ITEM, 'Unloading Detention', $billing_unloading );
				
				//! COD - disabled currently
				//$this->invoice_add_sales_line( $Invoice, $line_number++,
				//	EXP_COD_ITEM, 'COD charges', $billing_cod );
								
				//! Mileage
				//! SCR 162 - adjust text
				//! SCR# 168 - if it does not match, use total only
				$this->log_event( __METHOD__.": $ID total = $billing_mileage_total mileage = $billing_mileage rate = $billing_mileage_rate", EXT_ERROR_DEBUG);

				if( $billing_mileage_total > 0 ) {
					if( $billing_mileage * $billing_mileage_rate == $billing_mileage_total )
						$this->invoice_add_sales_line( EXP_MILEAGE_ITEM, 'Mileage: '.$billing_mileage.' miles @ '.$billing_mileage_rate, $billing_mileage_total, $billing_mileage, $billing_mileage_rate );
					else
						$this->invoice_add_sales_line( EXP_MILEAGE_ITEM, 'Mileage: '.$billing_mileage.' miles', $billing_mileage_total );
				}

								
				//! Fuel surcharge
				if( $billing_fsc_rate > 0 ) {
					if( $billing_mileage * $billing_fsc_rate == $billing_surcharge )
						$this->invoice_add_sales_line( EXP_SURCHARGE_ITEM, 'FSC: '.$billing_mileage.' miles @ '.$billing_surcharge, $billing_mileage );
					else
						$this->invoice_add_sales_line( EXP_SURCHARGE_ITEM, 'FSC: '.$billing_mileage.' miles @ '.$billing_fsc_rate, $billing_surcharge );
				} else {
					$this->invoice_add_sales_line( EXP_SURCHARGE_ITEM, 'FSC: '.$billing_mileage.' miles', $billing_surcharge );
				}
								
				//! Stopoff charges
				$this->invoice_add_sales_line( EXP_STOPOFF_ITEM, isset($billing_stopoff_note) && $billing_stopoff_note <> '' ? $this->hsc($billing_stopoff_note) : 'Stopoff charges', $billing_stopoff );
	
				//! Weekend
				$this->invoice_add_sales_line( EXP_STOPOFF_ITEM, 'Weekend/Holiday', $billing_weekend );
		
				$result3 = $rates_table->fetch_rows("BILLING_ID = ".$billing_id);
				if( is_array($result3) && count($result3) > 0 ) {
					foreach( $result3 as $rate ) {
						if( $rate['RATES'] > 0 ) {
							
							$this->invoice_add_sales_line( EXP_OTHER_ITEM, $this->hsc($rate['RATE_CODE'].' - '.$rate['RATE_NAME'].' - '.$rate['CATEGORY']), $rate['RATES'] );
							
							
						}
					}
				}
	
				//! Selection fee
				$this->invoice_add_sales_line( EXP_SELECTION_FEE_ITEM, 'Selection fee', $selection_fee );
				
				$discount_account = $this->discount_account_query();	//! Discount
				if( $discount_account && $discount <> 0 ) {
					$this->has_value = true;
					$Line = new QuickBooks_IPP_Object_Line();
					$Line->setDetailType('DiscountLineDetail');
					$Line->setAmount(number_format((float) $discount, 2,".",""));
					//$Line->setLineNum($this->line_number++);
					
					$DiscountLineDetail = new QuickBooks_IPP_Object_DiscountLineDetail();
					$DiscountLineDetail->setDiscountAccountRef($discount_account);
					
					$Line->addDiscountLineDetail($DiscountLineDetail);
					
					$this->Invoice->addLine($Line);
				}

			}
		}

	}

	// Add an invoice
	// $ID = SHIPMENT_CODE of the invoice
	public function invoice_add( $ID ) {
		global $shipment_table, $billing_table, $rates_table, $detail_table,
			$sts_qb_template, $sts_qb_invoice_terms,
			$sts_qb_class, $sts_qb_trans_date,
			$sts_can_consolidate, $multi_currency;

		if( $this->debug ) echo "<p>".__METHOD__.": $ID</p>";
		$result = false;
		$prefix = $this->company_table->prefix( 'invoice', $ID );

		//log_content("\n".date("m/d/Y h:i A")." InvoiceAddRq\n");
		$check = $shipment_table->fetch_rows("SHIPMENT_CODE = ".$ID);
				
		if( is_array($check) && count($check) == 1 && isset($check[0]['quickbooks_listid_customer']) ) {
			$full_name = $check[0]['BILLTO_NAME'];
			$listid_customer = $check[0]['quickbooks_listid_customer'];
			
			//log_content("\nInvoiceAddRq - got shipment data\n");
			$result2 = $billing_table->fetch_rows("SHIPMENT_ID = ".$ID, "*,
				(SELECT ITEM FROM EXP_ITEM_LIST WHERE ITEM_CODE = EXP_CLIENT_BILLING.TERMS) AS TERMS");
			if( is_array($result2) && count($result2) == 1 && isset($result2[0]['TOTAL']) ) {
				//! SCR# 257 - tax info
				if( $multi_currency ) {
					$tax_info = $this->tax->qb_tax_info( $ID );
					$this->tax_code_ref = $tax_info ? $this->tax_code_query($tax_info) : false;
					if( $tax_info && ! $this->tax_code_ref ) {
						$result = $shipment_table->update($ID, array("quickbooks_status_message" => 
						$shipment_table->trim_to_fit( "quickbooks_status_message",
						"Error - tax_code ".$tax_info." is missing or inactive in QBOE" ) ), false );
						$this->log_event( __METHOD__.": failure: tax_code ".$tax_info." is missing or inactive in QBOE for shipment $ID", EXT_ERROR_ERROR);
						return false;
					}
				} else {
					$this->tax_code_ref = 'NON';
				}

				$billing_currency		= isset($result2[0]['CURRENCY']) ? $result2[0]['CURRENCY'] : 'USD';
				$billing_pallets		= intval($result2[0]['PALLETS']);
				$billing_per_pallets	= floatval($result2[0]['PER_PALLETS']);
				$billing_amount_pallets	= floatval($result2[0]['PALLETS_RATE']);
				$billing_hand_pallet	= floatval($result2[0]['HAND_PALLET']);
				$billing_handling		= floatval($result2[0]['HAND_CHARGES']);
				$billing_freight		= floatval($result2[0]['FREIGHT_CHARGES']);
				$billing_extra			= floatval($result2[0]['EXTRA_CHARGES']);
				
				$billing_loading_free_hrs	= floatval($result2[0]['FREE_DETENTION_HOUR']);
				$billing_loading_hrs		= floatval($result2[0]['DETENTION_HOUR']);
				$billing_loading_rate		= floatval($result2[0]['RATE_PER_HOUR']);
				$billing_loading			= floatval($result2[0]['DETENTION_RATE']);
				
				$billing_unloading_free_hrs	= floatval($result2[0]['FREE_UN_DETENTION_HOUR']);
				$billing_unloading_hrs		= floatval($result2[0]['UNLOADED_DETENTION_HOUR']);
				$billing_unloading_rate		= floatval($result2[0]['UN_RATE_PER_HOUR']);
				$billing_unloading			= floatval($result2[0]['UNLOADED_DETENTION_RATE']);
				
				$billing_cod			= floatval($result2[0]['COD']);
				$billing_mileage		= floatval($result2[0]['MILLEAGE']);
				$billing_fsc_rate		= floatval($result2[0]['FSC_AVERAGE_RATE']);
				$billing_mileage_rate	= floatval($result2[0]['RPM']);
				$billing_surcharge		= floatval($result2[0]['FUEL_COST']);
	
				$billing_stopoff		= floatval($result2[0]['STOP_OFF']);
				$billing_stopoff_note	= $result2[0]['STOP_OFF_NOTE'];
				$billing_weekend		= floatval($result2[0]['WEEKEND']);
				
				$billing_adjustment_title	= $result2[0]['ADJUSTMENT_CHARGE_TITLE'];
				$billing_adjustment_charge	= floatval($result2[0]['ADJUSTMENT_CHARGE']);
	
				$selection_fee	= floatval($result2[0]['SELECTION_FEE']);
				$discount		= floatval($result2[0]['DISCOUNT']);
				
				$billing_id		= $result2[0]['CLIENT_BILLING_ID'];
				$terms = empty($result2[0]['TERMS']) ? $sts_qb_invoice_terms : $result2[0]['TERMS'];
				
				$this->has_value = false;
					
				$InvoiceService = new QuickBooks_IPP_Service_Invoice();
				
				$this->Invoice = new QuickBooks_IPP_Object_Invoice();
				
				$this->Invoice->setCustomerRef($listid_customer);

				//! SCR# 257 - Multiple currencies
				if( $multi_currency )
					$this->Invoice->setCurrencyFullName($billing_currency);
				
				//! Class - not in QBOE
				//if( isset($sts_qb_class) && $sts_qb_class <> '') {
				//}

				//! Template Information - don't know why this does not work
				//if( false && isset($sts_qb_template) && $sts_qb_template <> '') {
				//}
				
				//! TxnDate
			    $asof = $this->database->get_one_row("SELECT GET_ASOF( $ID ) AS ASOF");
			    if( is_array($asof) && isset($asof["ASOF"]) )
				    $this->Invoice->setTxnDate($asof["ASOF"]);
				else
					$this->Invoice->setTxnDate(date("Y-m-d"));

				//! Invoice number
				$this->Invoice->setDocNumber($prefix.$ID);
				
				//! Tracking number - CSPH did not want this.
				//$this->Invoice->setTrackingNum($ID);
				
				//! Billing Information
				$BillAddr = new QuickBooks_IPP_Object_BillAddr();
				$BillAddr->setLine1($this->hsc(isset($check[0]['BILLTO_NAME']) ? $check[0]['BILLTO_NAME'] : '', ADDR_FIELD_MAX_LENGTH));
				$BillAddr->setLine2($this->hsc(isset($check[0]['BILLTO_ADDR1']) ? $check[0]['BILLTO_ADDR1'] : '', ADDR_FIELD_MAX_LENGTH));
				if( isset($check[0]['BILLTO_ADDR2']) )
					$BillAddr->setLine3($this->hsc($check[0]['BILLTO_ADDR2'], ADDR_FIELD_MAX_LENGTH));
				$BillAddr->setCity($this->hsc(isset($check[0]['BILLTO_CITY']) ? $check[0]['BILLTO_CITY'] : '', CITY_FIELD_MAX_LENGTH));
				$BillAddr->setCountrySubDivisionCode($this->hsc(isset($check[0]['BILLTO_STATE']) ? $check[0]['BILLTO_STATE'] : '', STATE_FIELD_MAX_LENGTH));
				$BillAddr->setPostalCode($this->hsc(isset($check[0]['BILLTO_ZIP']) ? $check[0]['BILLTO_ZIP'] : '', POSTAL_FIELD_MAX_LENGTH));
				$BillAddr->setCountry('US');
				$this->Invoice->setBillAddr($BillAddr);
			
				//! Shipping Information - CSPH did not want this.
				/*
				$ShipAddr = new QuickBooks_IPP_Object_ShipAddr();
				$ShipAddr->setLine1($this->hsc(isset($check[0]['CONS_NAME']) ? $check[0]['CONS_NAME'] : '', ADDR_FIELD_MAX_LENGTH));
				$ShipAddr->setLine2($this->hsc(isset($check[0]['CONS_ADDR1']) ? $check[0]['CONS_ADDR1'] : '', ADDR_FIELD_MAX_LENGTH));
				if( isset($check[0]['CONS_ADDR2']) )
					$ShipAddr->setLine3($this->hsc($check[0]['CONS_ADDR2'], ADDR_FIELD_MAX_LENGTH));
				$ShipAddr->setCity($this->hsc(isset($check[0]['CONS_CITY']) ? $check[0]['CONS_CITY'] : '', CITY_FIELD_MAX_LENGTH));
				$ShipAddr->setCountrySubDivisionCode($this->hsc(isset($check[0]['CONS_STATE']) ? $check[0]['CONS_STATE'] : '', STATE_FIELD_MAX_LENGTH));
				$ShipAddr->setPostalCode($this->hsc(isset($check[0]['CONS_ZIP']) ? $check[0]['CONS_ZIP'] : '', POSTAL_FIELD_MAX_LENGTH));
				$ShipAddr->setCountry('US');
				$this->Invoice->setShipAddr($ShipAddr);
				*/
			
				//! ShipDate - CSPH did not want this.
				//$this->Invoice->setShipDate(isset($actual_shipment) && $actual_shipment ? $actual_shipment : $picked_date);

				//! Terms - Can fail!!!
				if( $TermRef =$this->term_query( trim($terms) ) ) {
					$this->Invoice->setSalesTermRef($TermRef);					
				} else {
					$shipment_table->update($ID, array("quickbooks_status_message" => 
						"Error - unable to get terms from QuickBooks"), false );
					$this->log_event( __METHOD__.": unable to get terms from QuickBooks", EXT_ERROR_ERROR);
					return false;
				}
				
				//! Memo (could be CustomerMemo)
				//$xml .= '<Memo>From Exspeedite Shipment #'.$ID.'</Memo>';
	
				//! DueDate
				//$xml .= '									<Other>'.date("m/d/Y", strtotime(isset($actual_delivery) && $actual_delivery <> '' ? $actual_delivery : $dropped_date)).'</Other>';

				//! ------------------- Invoice lines
				$this->line_number = 1;
				
				// $sts_can_consolidate - indicates if we consolidate shipments
				// If so, check for shipments with CONSOLIDATE_NUM that match $ID
				if( $sts_can_consolidate )
					$cons = $shipment_table->fetch_rows("CONSOLIDATE_NUM = ".$ID,
						"SHIPMENT_CODE, CONSOLIDATE_NUM");
					
				if( $sts_can_consolidate && is_array($cons) && count($cons) > 0 ) {
					foreach($cons as $row) {
						$this->invoice_shipment_add( $row["SHIPMENT_CODE"] );
					}
				} else {
					$this->invoice_shipment_add( $ID );
				}
				
				if( ! $this->has_value ) {	//! Empty Invoice
					$this->invoice_add_comment( 'Zero Rate' );
				}
				
				if( $this->debug ) {
					echo "<pre>";
					var_dump($this->Invoice);
					echo "</pre>";
				}
				$this->log_event( __METHOD__.": Invoice:\n".print_r($this->Invoice, true), EXT_ERROR_DEBUG);

				//! Send the invoice to QBOE
				if ($result = $InvoiceService->add($this->context, $this->realm, $this->Invoice))
				{
					if( $this->debug ) echo "<p>".__METHOD__.": invoice $ID success $result</p>";
					$this->log_event( __METHOD__.": invoice $ID success $result", EXT_ERROR_NOTICE);
					$result2 = $shipment_table->update($ID, array("quickbooks_txnid_invoice" => ((string)$result), "quickbooks_status_message" => "OK" ), false );
					if( $result2 ) {
						$result3 = $shipment_table->change_state_behavior( $ID, 'billed', true );
			        	//if( ! $result3 ) {
			        		//$content .= "shipment_table->change_state_behavior( $ID, 'billed' (".$shipment_table->billing_behavior_state['billed']."), true ) returned false\n".$shipment_table->state_change_error;
						//}
					} else
						$this->log_event( __METHOD__.": shipment_table->update($ID, $result) returned false", EXT_ERROR_ERROR);
				}
				else
				{
					if( $this->debug ) echo "<p>".__METHOD__.": failure: ".$InvoiceService->lastError($this->context)."</p>";
			$this->log_event( __METHOD__.": failure: ".$InvoiceService->lastError($this->context), EXT_ERROR_ERROR);
					$result = $shipment_table->update($ID, array("quickbooks_status_message" => 
						$shipment_table->trim_to_fit( "quickbooks_status_message", 
						"failure: ".$InvoiceService->lastError($this->context) ) ), false );
				}
			} else {
				$result = $shipment_table->update($ID, array("quickbooks_status_message" => 
					$shipment_table->trim_to_fit( "quickbooks_status_message",
					"Error - IA can't get billing data" ) ), false );
				$this->log_event( __METHOD__.": failure: can't get billing data for shipment $ID", EXT_ERROR_ERROR);
				//log_content("\nInvoiceAddRq - can't get billing data\n");
			}
		} else {
			$errmsg = $shipment_table->error();
			//! WORK ON FAILURE TO GET SHIPMENT INFO
			$this->log_event( __METHOD__.": failure: can't get shipment data for shipment $ID err=".$errmsg.
				"\ncheck = \n".print_r($check, true) , EXT_ERROR_ERROR);
			$email = sts_email::getInstance($this->database, $this->debug);
			$email->send_alert("invoice_add - can't get shipment data<br>".
				"ID = $ID err=".$errmsg, EXT_ERROR_ERROR);
		}

		return $result;
	}	

	//! lookup carrier name, given a LOAD_CODE for EXP_LOAD
	// The load also needs to be Approved
	private function lookup_carrier_name( $ID ) {
		global $load_table;
		
		if( $this->debug ) echo "<p>".__METHOD__.": $ID</p>";
		$result = false;
		$check = $load_table->fetch_rows("LOAD_CODE = ".$ID,"CURRENT_STATUS,
			(SELECT CARRIER_NAME FROM EXP_CARRIER WHERE CARRIER_CODE = CARRIER) CARRIER_NAME" );
		
		//! SCR# 407 - name of state changed, use behavior is safer
		if( is_array($check) && count($check) == 1 && isset($check[0]['CARRIER_NAME']) &&
			isset($check[0]['CURRENT_STATUS']) && in_array($load_table->state_behavior[$check[0]['CURRENT_STATUS']], array('approved', 'billed')) ) {
			$result = $check[0]['CARRIER_NAME'];
			if( $this->debug ) echo "<p>".__METHOD__.": look for $result</p>";
		} else {
			$this->log_event( __METHOD__.": not approved or not found $ID", EXT_ERROR_ERROR);
		}
	
	    return $result;
	}

	//! lookup driver name, given a DRIVER_PAY_ID for EXP_DRIVER_PAY_MASTER
	// Use FIRST_NAME, MIDDLE_NAME, LAST_NAME, CHECK_NAME from EXP_DRIVER to get name
	// The driver pay also needs to be finalized (approved)
	private function lookup_driver_name( $ID ) {
		global $driver_table;
		if( $this->debug ) echo "<p>".__METHOD__.": $ID</p>";
		$result = false;
		$check = $driver_table->fetch_rows("DRIVER_CODE = (SELECT DRIVER_ID 
			FROM EXP_DRIVER_PAY_MASTER
			WHERE DRIVER_PAY_ID=$ID
			AND FINALIZE_STATUS = 'finalized')",
			"FIRST_NAME, MIDDLE_NAME, LAST_NAME, CHECK_NAME" );
		
		if( is_array($check) && count($check) == 1 ) {
			if( ! empty($check[0]['CHECK_NAME']) )
				$result = $check[0]['CHECK_NAME'];
			else {
				$parts = array();
				if(! empty($check[0]['FIRST_NAME']) )	$parts[] = $check[0]['FIRST_NAME'];
				if(! empty($check[0]['MIDDLE_NAME']) )	$parts[] = $check[0]['MIDDLE_NAME'];
				if(! empty($check[0]['LAST_NAME']) )	$parts[] = $check[0]['LAST_NAME'];
				if( count($parts) > 0 ) $result = implode(' ', $parts);
			}
		} else {
			$this->log_event( __METHOD__.": not approved or not found $ID", EXT_ERROR_ERROR);
		}
	    return $result;
	}

	// Check if a vendor exists, prior to adding a bill
	// $ID = LOAD_CODE of the load/trip
	// $vendor_type = QUICKBOOKS_VENDOR_CARRIER or QUICKBOOKS_VENDOR_DRIVER
	public function vendor_query( $ID, $vendor_type = QUICKBOOKS_VENDOR_CARRIER ) {
		global $load_table, $driver_pay_master_table, $sts_qb_driver_suffix, $sts_qb_carrier_suffix;

		if( $this->debug ) echo "<p>".__METHOD__.": id=$ID, type=".
			($vendor_type==QUICKBOOKS_VENDOR_CARRIER ? 'carrier' : 
			($vendor_type==QUICKBOOKS_VENDOR_DRIVER ? 'driver' : 'unknown ('.$vendor_type.')'))."</p>";
		$result = false;

		switch( $vendor_type ) {
			case QUICKBOOKS_VENDOR_CARRIER:
				$full_name = $this->lookup_carrier_name( $ID ).
					(isset($sts_qb_carrier_suffix) && $sts_qb_carrier_suffix <> '' ? ' '.$sts_qb_carrier_suffix : '');
				$vendor_table = $load_table;
				break;
				
			case QUICKBOOKS_VENDOR_DRIVER:
				$full_name = $this->lookup_driver_name( $ID ).
					(isset($sts_qb_driver_suffix) && $sts_qb_driver_suffix <> '' ? ' '.$sts_qb_driver_suffix : '');
				$vendor_table = $driver_pay_master_table;
				break;
				
			default:
				$full_name = false;
		}
		
		if( $full_name ) {
			if( $this->debug ) echo "<p>".__METHOD__.": look for $full_name</p>";
			$this->log_event( __METHOD__.": look for $full_name", EXT_ERROR_DEBUG);
	
			if( is_array($this->vendor_cache) && isset($this->vendor_cache[$full_name])) {
				$result = $this->vendor_cache[$full_name];	// Found in the cache
			} else {
				$VendorService = new QuickBooks_IPP_Service_Vendor();
				
				$vendors = $VendorService->query($this->context, $this->realm,
					"SELECT * FROM Vendor WHERE DisplayName = '".addslashes($full_name)."'");
	
				if( is_array($vendors) ) {
					if( count($vendors) == 0 ) {	// Vendor NOT found
						$result = $this->vendor_add( $ID, $vendor_type );
					} else {
						$result = $vendors[0]->getId();
					}
					if( $result != false )
						$this->vendor_cache[$full_name] = $result; // Cache the results
				} else {
					if( $this->debug ) echo "<p>".__METHOD__.": failure: ".$VendorService->lastError($this->context)."</p>";
					$this->log_event( __METHOD__.": failure: ".$VendorService->lastError($this->context), EXT_ERROR_ERROR);
				}
			}
				
			if( $result == false ) {
				if( $this->debug ) echo "<p>".__METHOD__.": vendor $full_name found = false</p>";	
				$this->log_event( __METHOD__.": vendor $full_name found = false", EXT_ERROR_DEBUG);
			} else {
				if( $this->debug ) echo "<p>".__METHOD__.": found vendor $full_name = $result</p>";
				$this->log_event( __METHOD__.": found vendor $full_name = $result", EXT_ERROR_NOTICE);
				$result = $vendor_table->update($ID, array("quickbooks_listid_carrier" => ((string)$result), "quickbooks_status_message" => "Found Vendor in QB") );
				// Add bill here
				if( $existing = $this->bill_query( $ID, $vendor_type ) ) {
					if( $this->debug ) echo "<p>".__METHOD__.": bill for $ID already exists $existing</p>";	
					$vendor_table->update($ID, array("quickbooks_status_message" => 
							"Error - bill for $ID already exists in QuickBooks") );
					$this->log_event( __METHOD__.": bill for $ID already exists $existing", EXT_ERROR_ERROR);
				} else {
					$result = $this->bill_add( $ID, $vendor_type );
					if( ! $result ) {
						$this->log_event( __METHOD__.": unable to add bill $ID $vendor_type", EXT_ERROR_ERROR);
					}
				}
			}
	
		} else {
			$result = $vendor_table->update($ID, array("quickbooks_status_message" => 
				"Error - not approved or not found $ID") );
			$this->log_event( __METHOD__.": not approved or not found $ID", EXT_ERROR_ERROR);
		}
	
	    return $result;
	}

	// Add a carrier or driver as a vendor
	// $ID = LOAD_CODE of the load/trip or DRIVER_PAY_ID for EXP_DRIVER_PAY_MASTER
	// $vendor_type = QUICKBOOKS_VENDOR_CARRIER or QUICKBOOKS_VENDOR_DRIVER
	public function vendor_add( $ID, $vendor_type = QUICKBOOKS_VENDOR_CARRIER ) {
		$result = false;
		if( $this->debug ) echo "<p>".__METHOD__.": id=$ID, type=".
			($vendor_type==QUICKBOOKS_VENDOR_CARRIER ? 'carrier' : 
			($vendor_type==QUICKBOOKS_VENDOR_DRIVER ? 'driver' : 'unknown ('.$vendor_type.')'))."</p>";
		switch( $vendor_type ) {
			case QUICKBOOKS_VENDOR_CARRIER:
				$result = $this->vendor_add_carrier( $ID );
				break;
				
			case QUICKBOOKS_VENDOR_DRIVER:
				$result = $this->vendor_add_driver( $ID );
				break;
				
			default:
				$result = false;
		}
	    return $result;		
	}
	
	// Add a carrier as a vendor
	// $ID = LOAD_CODE of the load/trip
	public function vendor_add_carrier( $ID ) {
		global $load_table, $sts_qb_carrier_suffix, $multi_currency;

		if( $this->debug ) echo "<p>".__METHOD__.": $ID</p>";
		$result = false;

		$check = $load_table->fetch_rows("LOAD_CODE = ".$ID, "CARRIER,
			(SELECT CARRIER_NAME FROM EXP_CARRIER WHERE CARRIER_CODE = CARRIER) CARRIER_NAME,
			(SELECT EMAIL_NOTIFY FROM EXP_CARRIER WHERE CARRIER_CODE = CARRIER) EMAIL_NOTIFY,
			(SELECT CURRENCY_CODE FROM EXP_CARRIER WHERE CARRIER_CODE = CARRIER) CURRENCY_CODE");
		if( is_array($check) && count($check) == 1 && isset($check[0]['CARRIER_NAME']) ) {
	
			$contact = $load_table->database->get_multiple_rows(
				"SELECT ADDRESS, ADDRESS2, CITY, STATE, ZIP_CODE, PHONE_OFFICE, PHONE_CELL, PHONE_FAX, EMAIL
					FROM EXP_CONTACT_INFO
					WHERE CONTACT_CODE = ".$check[0]['CARRIER']."
					AND CONTACT_SOURCE = 'carrier'
					AND CONTACT_TYPE in ('company', 'carrier')
					LIMIT 1" );
			if( is_array($contact) && count($contact) > 0 ) {
				$carrier = $contact[0];

				$VendorService = new QuickBooks_IPP_Service_Vendor();
				
				$Vendor = new QuickBooks_IPP_Object_Vendor();
				$Vendor->setDisplayName($this->hsc($check[0]['CARRIER_NAME'].
					(isset($sts_qb_carrier_suffix) && $sts_qb_carrier_suffix <> '' ? ' '.$sts_qb_carrier_suffix : ''), NAME_FIELD_MAX_LENGTH));
				$Vendor->setCompanyName($this->hsc($check[0]['CARRIER_NAME'], NAME_FIELD_MAX_LENGTH));
				$Vendor->setPrintOnCheckName($this->hsc($check[0]['CARRIER_NAME'], NAME_FIELD_MAX_LENGTH));

				//! SCR# 257 - Multiple currencies
				if( $multi_currency ) {
					$currency = isset($check[0]['CURRENCY_CODE']) ? $check[0]['CURRENCY_CODE'] : 'USD';
					$Vendor->setCurrencyRef($currency);
				}
				
				// Phone #
				if( isset($carrier['PHONE_OFFICE']) && $carrier['PHONE_OFFICE'] <> '') {
					$PrimaryPhone = new QuickBooks_IPP_Object_PrimaryPhone();
					$PrimaryPhone->setFreeFormNumber($this->hsc(isset($carrier['PHONE_OFFICE']) ? $carrier['PHONE_OFFICE'] : '', PHONE_FIELD_MAX_LENGTH));
					$Vendor->setPrimaryPhone($PrimaryPhone);
				}

				// Mobile #
				if( isset($carrier['PHONE_CELL']) && $carrier['PHONE_CELL'] <> '') {
					$Mobile = new QuickBooks_IPP_Object_Mobile();
					$Mobile->setFreeFormNumber($this->hsc($carrier['PHONE_CELL'], PHONE_FIELD_MAX_LENGTH));
					$Vendor->setMobile($Mobile);
				}
				
				// Fax #
				if( isset($carrier['PHONE_FAX']) && $carrier['PHONE_FAX'] <> '') {
					$Fax = new QuickBooks_IPP_Object_Fax();
					$Fax->setFreeFormNumber($this->hsc($carrier['PHONE_FAX'], PHONE_FIELD_MAX_LENGTH));
					$Vendor->setFax($Fax);
				}
			
				// Bill address
				$BillAddr = new QuickBooks_IPP_Object_BillAddr();
				$BillAddr->setLine1($this->hsc(isset($carrier['ADDRESS']) ? $carrier['ADDRESS'] : '', ADDR_FIELD_MAX_LENGTH));
				if( isset($carrier['ADDRESS2']) )
					$BillAddr->setLine2($this->hsc($carrier['ADDRESS2'], ADDR_FIELD_MAX_LENGTH));
				$BillAddr->setCity($this->hsc(isset($carrier['CITY']) ? $carrier['CITY'] : '', CITY_FIELD_MAX_LENGTH));
				$BillAddr->setCountrySubDivisionCode($this->hsc(isset($carrier['STATE']) ? $carrier['STATE'] : '', STATE_FIELD_MAX_LENGTH));
				$BillAddr->setPostalCode($this->hsc(isset($carrier['ZIP_CODE']) ? $carrier['ZIP_CODE'] : '', POSTAL_FIELD_MAX_LENGTH));
				$BillAddr->setCountry('US');
				$Vendor->setBillAddr($BillAddr);
			
				// Email
				if( isset($carrier['EMAIL']) ) {
					$PrimaryEmailAddr = new QuickBooks_IPP_Object_PrimaryEmailAddr();
					$PrimaryEmailAddr->setAddress($this->hsc($carrier['EMAIL'], EMAIL_FIELD_MAX_LENGTH));
					$Vendor->setPrimaryEmailAddr($PrimaryEmailAddr);
				}
			
				if ($result = $VendorService->add($this->context, $this->realm, $Vendor)) {
					if( $this->debug ) echo "<p>".__METHOD__.": ".$check[0]['CARRIER_NAME']." success $result</p>";
					$this->log_event( __METHOD__.": ".$check[0]['CARRIER_NAME']." success $result", EXT_ERROR_NOTICE);
				} else {
					$load_table->update($ID, array("quickbooks_status_message" => 
						$load_table->trim_to_fit( "quickbooks_status_message", 
						"failure: ".$VendorService->lastError($this->context) ) ) );
					if( $this->debug ) echo "<p>".__METHOD__.": failure: ".$VendorService->lastError($this->context)."</p>";
				$this->log_event( __METHOD__.": failure: ".$VendorService->lastError($this->context), EXT_ERROR_ERROR);
				}

			} else {
				$load_table->update($ID, array("quickbooks_status_message" => 
					$load_table->trim_to_fit( "quickbooks_status_message",
					"Error - Carrier contact info of type='company' or 'carrier' not found." ) ) );
				if( $this->debug ) echo "<p>".__METHOD__.": failure: Contact info not found</p>";
				$this->log_event( __METHOD__.": failure: Contact info not found", EXT_ERROR_ERROR);
			}
		} else {
			if( $this->debug ) echo "<p>".__METHOD__.": failure: Load $ID not found</p>";
			$this->log_event( __METHOD__.": failure: Load $ID not found", EXT_ERROR_ERROR);
		}
		
		return $result;
	}	
	
	// Add a driver as a vendor
	// $ID = DRIVER_PAY_ID for EXP_DRIVER_PAY_MASTER
	public function vendor_add_driver( $ID ) {
		global $load_table, $driver_table, $sts_qb_driver_suffix;

		if( $this->debug ) echo "<p>".__METHOD__.": $ID</p>";
		$result = false;

		$check = $driver_table->fetch_rows("DRIVER_CODE = (SELECT DRIVER_ID 
			FROM EXP_DRIVER_PAY_MASTER
			WHERE DRIVER_PAY_ID=$ID
			AND FINALIZE_STATUS = 'finalized')" );

		if( is_array($check) && count($check) == 1 && isset($check[0]['FIRST_NAME']) ) {
	
			$contact = $driver_table->database->get_multiple_rows(
				"SELECT ADDRESS, ADDRESS2, CITY, STATE, ZIP_CODE, PHONE_OFFICE, PHONE_CELL, PHONE_FAX, EMAIL
					FROM EXP_CONTACT_INFO
					WHERE CONTACT_CODE = ".$check[0]['DRIVER_CODE']."
					AND CONTACT_SOURCE = 'driver'
					AND CONTACT_TYPE IN ('individual', 'company')" );
			if( is_array($contact) && count($contact) > 0 ) {
				$driver = $contact[0];
				
				$vendor_name = $this->lookup_driver_name( $ID );

				$VendorService = new QuickBooks_IPP_Service_Vendor();
				
				$Vendor = new QuickBooks_IPP_Object_Vendor();

				if( ! empty($check[0]['FIRST_NAME']))
					$Vendor->setGivenName($this->hsc($check[0]['FIRST_NAME'], NAME_FIELD_MAX_LENGTH));
				if( ! empty($check[0]['MIDDLE_NAME']))
					$Vendor->setMiddleName($this->hsc($check[0]['MIDDLE_NAME'], NAME_FIELD_MAX_LENGTH));
				if( ! empty($check[0]['LAST_NAME']))
					$Vendor->setFamilyName($this->hsc($check[0]['LAST_NAME'], NAME_FIELD_MAX_LENGTH));

				$Vendor->setDisplayName($this->hsc($vendor_name.
								(isset($sts_qb_driver_suffix) && $sts_qb_driver_suffix <> '' ? ' '.$sts_qb_driver_suffix : ''), NAME_FIELD_MAX_LENGTH));
				//$Vendor->setCompanyName($this->hsc($vendor_name, NAME_FIELD_MAX_LENGTH));
				$Vendor->setPrintOnCheckName($this->hsc($vendor_name, NAME_FIELD_MAX_LENGTH));

				// Phone #
				if( isset($driver['PHONE_OFFICE']) && $driver['PHONE_OFFICE'] <> '') {
					$PrimaryPhone = new QuickBooks_IPP_Object_PrimaryPhone();
					$PrimaryPhone->setFreeFormNumber($this->hsc(isset($driver['PHONE_OFFICE']) ? $driver['PHONE_OFFICE'] : '', PHONE_FIELD_MAX_LENGTH));
					$Vendor->setPrimaryPhone($PrimaryPhone);
				}

				// Mobile #
				if( isset($driver['PHONE_CELL']) && $driver['PHONE_CELL'] <> '') {
					$Mobile = new QuickBooks_IPP_Object_Mobile();
					$Mobile->setFreeFormNumber($this->hsc($driver['PHONE_CELL'], PHONE_FIELD_MAX_LENGTH));
					$Vendor->setMobile($Mobile);
				}
				
				// Fax #
				if( isset($driver['PHONE_FAX']) && $driver['PHONE_FAX'] <> '') {
					$Fax = new QuickBooks_IPP_Object_Fax();
					$Fax->setFreeFormNumber($this->hsc($driver['PHONE_FAX'], PHONE_FIELD_MAX_LENGTH));
					$Vendor->setFax($Fax);
				}
			
				// Bill address
				$BillAddr = new QuickBooks_IPP_Object_BillAddr();
				$BillAddr->setLine1($this->hsc(isset($driver['ADDRESS']) ? $driver['ADDRESS'] : '', ADDR_FIELD_MAX_LENGTH));
				if( isset($driver['ADDRESS2']) )
					$BillAddr->setLine2($this->hsc($driver['ADDRESS2'], ADDR_FIELD_MAX_LENGTH));
				$BillAddr->setCity($this->hsc(isset($driver['CITY']) ? $driver['CITY'] : '', CITY_FIELD_MAX_LENGTH));
				$BillAddr->setCountrySubDivisionCode($this->hsc(isset($driver['STATE']) ? $driver['STATE'] : '', STATE_FIELD_MAX_LENGTH));
				$BillAddr->setPostalCode($this->hsc(isset($driver['ZIP_CODE']) ? $driver['ZIP_CODE'] : '', POSTAL_FIELD_MAX_LENGTH));
				$BillAddr->setCountry('US');
				$Vendor->setBillAddr($BillAddr);
			
				// Email
				if( isset($driver['EMAIL']) ) {
					$PrimaryEmailAddr = new QuickBooks_IPP_Object_PrimaryEmailAddr();
					$PrimaryEmailAddr->setAddress($this->hsc($driver['EMAIL'], EMAIL_FIELD_MAX_LENGTH));
					$Vendor->setPrimaryEmailAddr($PrimaryEmailAddr);
				}
				
				// Notes
				// DRIVER_NOTES
				// Not possible at present, api does not handle Attachable entity
				// https://developer.intuit.com/docs/api/accounting/Attachable
				// https://github.com/consolibyte/quickbooks-php
			
				if ($result = $VendorService->add($this->context, $this->realm, $Vendor)) {
					if( $this->debug ) echo "<p>".__METHOD__.": ".$vendor_name." success $result</p>";
					$this->log_event( __METHOD__.": ".$vendor_name." success $result", EXT_ERROR_NOTICE);
				} else {
					if( $this->debug ) echo "<p>".__METHOD__.": failure: ".$VendorService->lastError($this->context)."</p>";
				$this->log_event( __METHOD__.": failure: ".$VendorService->lastError($this->context), EXT_ERROR_ERROR);
				}

			} else {
				//$load_table->update($ID, array("quickbooks_status_message" => 
				//	"Error - Carrier contact info of type='company' not found." ) );
				if( $this->debug ) echo "<p>".__METHOD__.": failure: Contact info not found</p>";
				$this->log_event( __METHOD__.": failure: Contact info not found", EXT_ERROR_ERROR);
			}
		} else {
			if( $this->debug ) echo "<p>".__METHOD__.": failure: Load $ID not found</p>";
			$this->log_event( __METHOD__.": failure: Load $ID not found", EXT_ERROR_ERROR);
		}
		
		return $result;
	}	
	
	//! Return a bill number, based on the relevant prefix and the ID
	private function bill_number( $ID, $vendor_type = QUICKBOOKS_VENDOR_CARRIER ) {
		if( $this->debug ) echo "<p>".__METHOD__.": $ID</p>";
		$result = false;
		switch( $vendor_type ) {
			case QUICKBOOKS_VENDOR_CARRIER:
				$prefix = $this->company_table->prefix( 'carrier', $ID );
				break;
				
			case QUICKBOOKS_VENDOR_DRIVER:
				$prefix = $this->company_table->prefix( 'driver', $ID );
				break;
				
			default:
				$prefix = '';
		}
		
		if( $this->debug ) echo "<p>".__METHOD__.": return ".$prefix.$ID."</p>";	
		$this->log_event( __METHOD__.": return ".$prefix.$ID, EXT_ERROR_DEBUG);
		return $prefix.$ID;
	}

	// Check if a bill exists, return the reference ID
	// $ID = LOAD_CODE of the load/trip
	public function bill_query( $ID, $vendor_type = QUICKBOOKS_VENDOR_CARRIER ) {
		
		if( $this->debug ) echo "<p>".__METHOD__.": $ID</p>";
		$result = false;
		$bill_number = $this->bill_number( $ID, $vendor_type );
		
		$BillService = new QuickBooks_IPP_Service_Bill();
		
		$bills = $BillService->query($this->context, $this->realm, "SELECT * FROM Bill WHERE DocNumber = '".$bill_number."'");
	
		if( is_array($bills) && count($bills) == 1 ) {	// Invoice found
			$result = $bills[0]->getId();
			if( $this->debug ) {
				echo "<pre>";
				var_dump($bills[0]);
				echo "</pre>";
			}
		}
		
		if( $result == false ) {
			if( $this->debug ) echo "<p>".__METHOD__.": invoice ".$bill_number." found = false</p>";	
			$this->log_event( __METHOD__.": invoice ".$bill_number." found = false", EXT_ERROR_DEBUG);
		} else {
			if( $this->debug ) echo "<p>".__METHOD__.": found invoice ".$bill_number." = $result</p>";
			$this->log_event( __METHOD__.": found invoice ".$bill_number." = $result", EXT_ERROR_NOTICE);
		}
	    return $result;
	}

	// Add a bill for a carrier or driver
	// $ID = LOAD_CODE of the load/trip or DRIVER_PAY_ID for EXP_DRIVER_PAY_MASTER
	// $vendor_type = QUICKBOOKS_VENDOR_CARRIER or QUICKBOOKS_VENDOR_DRIVER
	public function bill_add( $ID, $vendor_type = QUICKBOOKS_VENDOR_CARRIER ) {
		$result = false;
		if( $this->debug ) echo "<p>".__METHOD__.": id=$ID, type=".
			($vendor_type==QUICKBOOKS_VENDOR_CARRIER ? 'carrier' : 
			($vendor_type==QUICKBOOKS_VENDOR_DRIVER ? 'driver' : 'unknown ('.$vendor_type.')'))."</p>";
		switch( $vendor_type ) {
			case QUICKBOOKS_VENDOR_CARRIER:
				$result = $this->bill_add_carrier( $ID );
				break;
				
			case QUICKBOOKS_VENDOR_DRIVER:
				$result = $this->bill_add_driver( $ID );
				break;
				
			default:
				$result = false;
		}
	    return $result;		
	}
	
	// Add a bill for a carrier
	// $ID = LOAD_CODE of the load/trip
	public function bill_add_carrier( $ID ) {
		global $load_table, $sts_qb_bill_terms, $multi_currency;

		if( $this->debug ) echo "<p>".__METHOD__.": $ID</p>";
		$result = false;
		$bill_number = $this->bill_number( $ID, QUICKBOOKS_VENDOR_CARRIER );

		$check = $load_table->fetch_rows("LOAD_CODE = ".$ID, "*,
			(SELECT ITEM FROM EXP_ITEM_LIST WHERE ITEM_CODE = EXP_LOAD.TERMS) AS TERMS");
		
		if( is_array($check) && count($check) == 1 && isset($check[0]['quickbooks_listid_carrier']) ) {
			$listid_carrier = $check[0]['quickbooks_listid_carrier'];
			$carrier_base = isset($check[0]['CARRIER_BASE']) ? $check[0]['CARRIER_BASE'] : 0;
			$carrier_fsc = isset($check[0]['CARRIER_FSC']) ? $check[0]['CARRIER_FSC'] : 0;
			$carrier_handling = isset($check[0]['CARRIER_HANDLING']) ? $check[0]['CARRIER_HANDLING'] : 0;
			$terms = empty($check[0]['TERMS']) ? $sts_qb_bill_terms : $check[0]['TERMS'];

			//! SCR# 257 - Multiple currencies
			$billing_currency = isset($check[0]['CURRENCY']) ? $check[0]['CURRENCY'] : 'USD';
			$this->log_event( __METHOD__.": billing_currency = $billing_currency", EXT_ERROR_ERROR);
			
			$BillService = new QuickBooks_IPP_Service_Bill();
			
			$Bill = new QuickBooks_IPP_Object_Bill();
			
			$Bill->setDocNumber($bill_number);
			
			//! SCR# 257 - Multiple currencies
			if( $multi_currency ) {
				$Bill->setCurrencyRef($billing_currency);
			}
				
			$transaction_date = $load_table->carrier_transaction_date($ID);
			if( $transaction_date != false ) {
				$Bill->setTxnDate(date("Y-m-d", strtotime($transaction_date)));
			}
			$Bill->setVendorRef($listid_carrier);
			$Bill->setPrivateNote('(From Exspeedite Load #'.$ID.')'.
				(isset($check[0]['CARRIER_NOTE']) && $check[0]['CARRIER_NOTE'] <> '' ?
				"\n".$check[0]['CARRIER_NOTE'] : ''));
			
			//! Terms - Can fail!!!
			if( $TermRef =$this->term_query( $terms ) ) {
				$Bill->setSalesTermRef($TermRef);					
			} else {
				$result2 = $load_table->update($ID, array("quickbooks_status_message" => 
						"Error - unable to get terms from QuickBooks" ) );
				$this->log_event( __METHOD__.": unable to get terms from QuickBooks", EXT_ERROR_ERROR);
				return false;
			}
				
			//! ------------------- Bill lines
			$line_number = 1;
			
			//! Freight charges
			$carrier_freight_item = $this->item_query( EXP_CARRIER_FREIGHT_ITEM );
			if( $carrier_freight_item && $carrier_base > 0 ) {
				$Line = new QuickBooks_IPP_Object_Line();
				$Line->setAmount(number_format((float) $carrier_base, 2,".",""));
				$Line->setDetailType('ItemBasedExpenseLineDetail');
				$Line->setDescription('Freight charges');
				$Line->setLineNum($line_number++);
				
				$ItemBasedExpenseLineDetail = new QuickBooks_IPP_Object_ItemBasedExpenseLineDetail();
				$ItemBasedExpenseLineDetail->setItemRef($carrier_freight_item);
				$ItemBasedExpenseLineDetail->setUnitPrice(number_format((float) $carrier_base, 2,".",""));
				$ItemBasedExpenseLineDetail->setQty(1);
				
				$Line->setItemBasedExpenseLineDetail($ItemBasedExpenseLineDetail);
				$Bill->addLine($Line);
			}

			//! Fuel surcharge
			$carrier_fsc_item = $this->item_query( EXP_CARRIER_FSC_ITEM );
			if( $carrier_fsc_item && $carrier_fsc > 0 ) {
				$Line = new QuickBooks_IPP_Object_Line();
				$Line->setAmount(number_format((float) $carrier_fsc, 2,".",""));
				$Line->setDetailType('ItemBasedExpenseLineDetail');
				$Line->setDescription('Fuel surcharge');
				$Line->setLineNum($line_number++);
				
				$ItemBasedExpenseLineDetail = new QuickBooks_IPP_Object_ItemBasedExpenseLineDetail();
				$ItemBasedExpenseLineDetail->setItemRef($carrier_fsc_item);
				$ItemBasedExpenseLineDetail->setUnitPrice(number_format((float) $carrier_fsc, 2,".",""));
				$ItemBasedExpenseLineDetail->setQty(1);
				
				$Line->setItemBasedExpenseLineDetail($ItemBasedExpenseLineDetail);
				$Bill->addLine($Line);
			}

			//! Handling charges
			$carrier_handling_item = $this->item_query( EXP_CARRIER_HANDLING_ITEM );
			if( $carrier_handling_item && $carrier_handling > 0 ) {
				$Line = new QuickBooks_IPP_Object_Line();
				$Line->setAmount(number_format((float) $carrier_handling, 2,".",""));
				$Line->setDetailType('ItemBasedExpenseLineDetail');
				$Line->setDescription('Handling charges');
				$Line->setLineNum($line_number++);
				
				$ItemBasedExpenseLineDetail = new QuickBooks_IPP_Object_ItemBasedExpenseLineDetail();
				$ItemBasedExpenseLineDetail->setItemRef($carrier_handling_item);
				$ItemBasedExpenseLineDetail->setUnitPrice(number_format((float) $carrier_handling, 2,".",""));
				$ItemBasedExpenseLineDetail->setQty(1);
				
				$Line->setItemBasedExpenseLineDetail($ItemBasedExpenseLineDetail);
				$Bill->addLine($Line);
			}

			if( $this->debug ) {
				echo "<pre>";
				var_dump($Bill);
				echo "</pre>";
			}
//die('<p>testing</p>');
			$this->log_event( __METHOD__.": Bill:\n".print_r($Bill, true), EXT_ERROR_DEBUG);


			//! Send the bill to QBOE
			if ($result = $BillService->add($this->context, $this->realm, $Bill)) {
				$this->log_event( __METHOD__.": request: ".$BillService->lastRequest(), EXT_ERROR_DEBUG);
				$this->log_event( __METHOD__.": response: ".$BillService->lastResponse(), EXT_ERROR_DEBUG);
				if( $this->debug ) echo "<p>".__METHOD__.": invoice $ID success $result</p>";
				$this->log_event( __METHOD__.": invoice $ID success $result", EXT_ERROR_NOTICE);
				$result2 = $load_table->update($ID, array("quickbooks_txnid_ap" => ((string)$result),
					'PAID_DATE' => date("Y-m-d H:i:s"),
					"quickbooks_status_message" => "OK" ) );
				if( $result2 ) {
		        	$result3 = $load_table->change_state_behavior( $ID, 'billed' );
				}
			} else {
				$this->log_event( __METHOD__.": request: ".$BillService->lastRequest(), EXT_ERROR_DEBUG);
				$this->log_event( __METHOD__.": response: ".$BillService->lastResponse(), EXT_ERROR_DEBUG);
				if( $this->debug ) echo "<p>".__METHOD__.": failure: ".$BillService->lastError($this->context)."</p>";
				$this->log_event( __METHOD__.": failure: ".$BillService->lastError($this->context), EXT_ERROR_ERROR);
				$result2 = $load_table->update($ID, array("quickbooks_status_message" => 
						$load_table->trim_to_fit( "quickbooks_status_message", 
						"failure: ".$BillService->lastError($this->context) ) ) );
			}
		} else {
			$this->log_event( __METHOD__.": failure: can't get load data", EXT_ERROR_ERROR);
			$email = sts_email::getInstance($this->database, $this->debug);
			$email->send_alert("bill_add - can't get load data<br>".
				"ID = $ID", EXT_ERROR_ERROR);
		}
		return $result;
	}	

	// Add a bill for a driver
	// $ID = LOAD_CODE of the load/trip
	public function bill_add_driver( $ID ) {
		global $driver_pay_master_table, $load_table, $sts_qb_bill_terms;

		if( $this->debug ) echo "<p>".__METHOD__.": $ID</p>";
		$result = false;
		$bill_number = $this->bill_number( $ID, QUICKBOOKS_VENDOR_DRIVER );

		$check = $driver_pay_master_table->fetch_rows("DRIVER_PAY_ID = ".$ID);
		
		if( is_array($check) && count($check) == 1 && isset($check[0]['quickbooks_listid_carrier']) ) {
			$listid_carrier = $check[0]['quickbooks_listid_carrier'];
			$from = isset($check[0]['WEEKEND_FROM']) ? $check[0]['WEEKEND_FROM'] : 0;
			$to = isset($check[0]['WEEKEND_TO']) ? $check[0]['WEEKEND_TO'] : 0;
			//$trip_pay = isset($check[0]['TRIP_PAY']) ? $check[0]['TRIP_PAY'] : 0;
			//$bonus = isset($check[0]['BONUS']) ? $check[0]['BONUS'] : 0;
			//$handling = isset($check[0]['HANDLING']) ? $check[0]['HANDLING'] : 0;
			$gross = isset($check[0]['GROSS_EARNING']) ? $check[0]['GROSS_EARNING'] : 0;
			$driver_id = isset($check[0]['DRIVER_ID']) ? $check[0]['DRIVER_ID'] : 0;
			
			$BillService = new QuickBooks_IPP_Service_Bill();
			
			$Bill = new QuickBooks_IPP_Object_Bill();
			
			$Bill->setDocNumber($bill_number);
			if( isset($check[0]['ADDED_ON']) ) {
				$Bill->setTxnDate(date("Y-m-d", strtotime($check[0]['ADDED_ON'])));
			}
			$Bill->setVendorRef($listid_carrier);
			
			//! Terms - Can fail!!!
			if( $TermRef =$this->term_query( trim($sts_qb_bill_terms) ) ) {
				$Bill->setSalesTermRef($TermRef);					
			} else {
				$result2 = $driver_pay_master_table->update($ID, array("quickbooks_status_message" => 
					"Error - unable to get terms from QuickBooks" ), false );
				$this->log_event( __METHOD__.": unable to get terms from QuickBooks", EXT_ERROR_ERROR);
				return false;
			}
				
			//! ------------------- Bill lines
			$line_number = 1;
			
			$msg = 'Driver Pay for week '.date('m/d/Y',strtotime($from)).
				' to '.date('m/d/Y',strtotime($to));
			
			$loads = $driver_pay_master_table->get_loads( $ID );
			if( is_array($loads) && count($loads) > 0 )
				$msg .= "\n\nLoads: ".implode(', ', $loads);
			
			//! Description only line
			$Line = new QuickBooks_IPP_Object_Line();
			$Line->setDetailType('DescriptionOnly');
			$Line->setDescription( $msg );
			$Line->setLineNum($line_number++);
			$Bill->addLine($Line);
			
			foreach( $loads as $load ) {
			
				if( $this->debug ) echo "<p>".__METHOD__.": "."DRIVER_ID = ".$driver_id.
					" AND WEEKEND_FROM = '".$from."' AND WEEKEND_TO = '".$to."'".
					" AND LOAD_ID = ".$load."</p>";
				$check2 = $driver_pay_master_table->fetch_rows("DRIVER_ID = ".$driver_id.
					" AND WEEKEND_FROM = '".$from."' AND WEEKEND_TO = '".$to."'".
					" AND LOAD_ID = ".$load, "TRIP_PAY, BONUS, HANDLING");
				
				if( is_array($check2) && count($check2) == 1) {
					$trip_pay = isset($check2[0]['TRIP_PAY']) ? $check2[0]['TRIP_PAY'] : 0;
					$bonus = isset($check2[0]['BONUS']) ? $check2[0]['BONUS'] : 0;
					$handling = isset($check2[0]['HANDLING']) ? $check2[0]['HANDLING'] : 0;
				}
				
				//! Trip Pay
				$driver_trip_pay_item = $this->item_query( EXP_DRIVER_TRIP_PAY_ITEM );
				if( $driver_trip_pay_item ) {
					$Line = new QuickBooks_IPP_Object_Line();
					$Line->setAmount(number_format((float) $trip_pay, 2,".",""));
					$Line->setDetailType('ItemBasedExpenseLineDetail');
					$Line->setDescription('Trip Pay For load '.$load);
					$Line->setLineNum($line_number++);
					
					$ItemBasedExpenseLineDetail = new QuickBooks_IPP_Object_ItemBasedExpenseLineDetail();
					$ItemBasedExpenseLineDetail->setItemRef($driver_trip_pay_item);
					$ItemBasedExpenseLineDetail->setUnitPrice(number_format((float) $trip_pay, 2,".",""));
					$ItemBasedExpenseLineDetail->setQty(1);
					
					$Line->setItemBasedExpenseLineDetail($ItemBasedExpenseLineDetail);
					$Bill->addLine($Line);
				}
	
				//! Bonus
				$driver_bonus_item = $this->item_query( EXP_DRIVER_BONUS_ITEM );
				if( $driver_bonus_item && $bonus != 0 ) {
					$Line = new QuickBooks_IPP_Object_Line();
					$Line->setAmount(number_format((float) $bonus, 2,".",""));
					$Line->setDetailType('ItemBasedExpenseLineDetail');
					$Line->setDescription('Bonus For load '.$load);
					$Line->setLineNum($line_number++);
					
					$ItemBasedExpenseLineDetail = new QuickBooks_IPP_Object_ItemBasedExpenseLineDetail();
					$ItemBasedExpenseLineDetail->setItemRef($driver_bonus_item);
					$ItemBasedExpenseLineDetail->setUnitPrice(number_format((float) $bonus, 2,".",""));
					$ItemBasedExpenseLineDetail->setQty(1);
					
					$Line->setItemBasedExpenseLineDetail($ItemBasedExpenseLineDetail);
					$Bill->addLine($Line);
				}
	
				//! Handling charges
				$driver_handling_item = $this->item_query( EXP_DRIVER_HANDLING_ITEM );
				if( $driver_handling_item && $handling != 0 ) {
					$Line = new QuickBooks_IPP_Object_Line();
					$Line->setAmount(number_format((float) $handling, 2,".",""));
					$Line->setDetailType('ItemBasedExpenseLineDetail');
					$Line->setDescription('Handling charges For load '.$load);
					$Line->setLineNum($line_number++);
					
					$ItemBasedExpenseLineDetail = new QuickBooks_IPP_Object_ItemBasedExpenseLineDetail();
					$ItemBasedExpenseLineDetail->setItemRef($driver_handling_item);
					$ItemBasedExpenseLineDetail->setUnitPrice(number_format((float) $handling, 2,".",""));
					$ItemBasedExpenseLineDetail->setQty(1);
					
					$Line->setItemBasedExpenseLineDetail($ItemBasedExpenseLineDetail);
					$Bill->addLine($Line);
				}
			
			}

			if( $this->debug ) {
				echo "<pre>";
				var_dump($Bill);
				echo "</pre>";
			}
//die('<p>testing</p>');

			//! Send the bill to QBOE
			if ($result = $BillService->add($this->context, $this->realm, $Bill)) {
				if( $this->debug ) echo "<p>".__METHOD__.": bill $ID success $result</p>";
				$this->log_event( __METHOD__.": bill $ID success $result", EXT_ERROR_NOTICE);


				$result2 = $driver_pay_master_table->update_week( $ID, 
					array("quickbooks_txnid_ap" => ((string)$result),
					'PAID_DATE' => date("Y-m-d H:i:s"),
					"quickbooks_status_message" => "OK",
					"FINALIZE_STATUS" => "paid" ) );

				//! update each load to 'Paid' status
				$loads = $driver_pay_master_table->get_loads( $ID );
				if( is_array($loads) && count($loads) > 0 ) {
					foreach($loads as $load) {
						$load_table->update($load, array( 'CURRENT_STATUS' => $load_table->behavior_state['billed'] ));
					}
				}

			} else {
				if( $this->debug ) echo "<p>".__METHOD__.": failure: ".$BillService->lastError($this->context)."</p>";
				$this->log_event( __METHOD__.": failure: ".$BillService->lastError($this->context), EXT_ERROR_ERROR);
				$this->log_event( __METHOD__.": request: ".$IPP->lastRequest(), EXT_ERROR_ERROR);
				$this->log_event( __METHOD__.": response: ".$IPP->lastResponse(), EXT_ERROR_ERROR);
				$result2 = $driver_pay_master_table->update($ID, array("quickbooks_status_message" => 
					"failure: ".$BillService->lastError($this->context) ), false );
			}
		} else {
			$this->log_event( __METHOD__.": failure: can't get driver pay data", EXT_ERROR_ERROR);
			$email = sts_email::getInstance($this->database, $this->debug);
			$email->send_alert("bill_add - can't get driver pay data<br>".
				"ID = $ID", EXT_ERROR_ERROR);
		}
		return $result;
	}	

}

?>