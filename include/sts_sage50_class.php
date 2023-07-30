<?php

// $Id: sts_sage50_class.php 5030 2023-04-12 20:31:34Z duncan $
// Sage 50 classes - all things to do with export to Sage 50

// no direct access
defined('_STS_INCLUDE') or die('Restricted access');

// Make sure we don't include twice
if( ! defined('_STS_SAGE50_CLASS') ) {
define( '_STS_SAGE50_CLASS', '1' );

require_once( "sts_table_class.php" );
require_once( "sts_setting_class.php" );
require_once( "sts_company_tax_class.php" );
require_once( "sts_shipment_class.php" );
require_once( "sts_load_class.php" );

//! Define constants for invoice item types
// Make sure we don't define them twice
if( ! defined('INVOICE_ITEM_DEFAULT') ) {
	define( 'INVOICE_ITEM_DEFAULT', 'default' );
	define( 'INVOICE_ITEM_PALLETS', 'pallets' );
	define( 'INVOICE_ITEM_PALLET_HANDLING', 'pallet handling' );
	define( 'INVOICE_ITEM_HANDLING', 'handling' );
	define( 'INVOICE_ITEM_FRIEGHT', 'freight' );
	define( 'INVOICE_ITEM_EXTRA', 'extra' );
	define( 'INVOICE_ITEM_STOPOFF', 'stop-off' );
	define( 'INVOICE_ITEM_WEEKEND', 'weekend' );
	define( 'INVOICE_ITEM_LOADING', 'loading' );
	define( 'INVOICE_ITEM_UNLOADING', 'unloading' );
	define( 'INVOICE_ITEM_COD', 'COD' );
	define( 'INVOICE_ITEM_MILEAGE', 'mileage' );
	define( 'INVOICE_ITEM_SURCHARGE', 'surcharge' );
	define( 'INVOICE_ITEM_OTHER', 'other' );
	define( 'INVOICE_ITEM_SELECTION', 'selection' );
	define( 'INVOICE_ITEM_DISCOUNT', 'discount' );
}

class sts_sage50_invoice extends sts_table {
	private $setting_table;
	private $multi_company;
	private $enabled;
	private $can_consolidate;
	private $invoice_terms;
	private $tax_type = 2;	// This could change per Sage 50 setup
	private $log_file;		//! SCR# 519 - log exports to Sage50
	
	private $invoice_lines;
	private $fixed = false;
	private $invoices;
	private $shipment_table;
	private $tax;
	private $tax_id = '';
	private $gl_code;
	private $ss_number;
	private $is_consolidated;
	private $show_detail;
	//! SCR# 279 - strings to remove before export
	private $eol_strings = array("\r\n", "\r", "\n", '\r\n', '\r', '\n');

	// Constructor does not need the table name
	public function __construct( $database, $debug = false ) {
		global $sts_log_to_file;

		$this->debug = $debug;
		$this->primary_key = "SHIPMENT_CODE";
		if( $this->debug ) echo "<p>Create sts_sage50_invoice</p>";
		$this->setting_table = sts_setting::getInstance($database, $debug);
		$this->shipment_table = sts_shipment::getInstance($database, $debug);
		$this->tax = sts_company_tax::getInstance($database, $debug);
		$this->multi_company = ($this->setting_table->get( 'option', 'MULTI_COMPANY' ) == 'true');
		$this->invoice_terms = $this->setting_table->get( 'api', 'SAGE50_INVOICE_TERMS' );
		$this->tax_type = $this->setting_table->get( 'api', 'SAGE50_TAX_TYPE' );
		$this->enabled = ($this->setting_table->get( 'api', 'EXPORT_SAGE50_CSV' ) == 'true');
		$this->can_consolidate = $this->setting_table->get( 'option', 'CONSOLIDATE_SHIPMENTS' ) == 'true';
		$this->show_detail = $this->setting_table->get( 'api', 'QUICKBOOKS_DETAIL' ) == 'true';
		if( $sts_log_to_file )
			$this->log_file = $this->setting_table->get( 'option', 'SAGE50_LOG_FILE' );
		parent::__construct( $database, SHIPMENT_TABLE, $debug);
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
    
	//! SCR# 519 - log exports to Sage50
	public function log_event( $event ) {		
		if( isset($this->log_file) && $this->log_file <> '' &&
			 ! is_dir($this->log_file) ) {
			file_put_contents($this->log_file, date('m/d/Y h:i:s A')." ".$event.PHP_EOL,
				(file_exists($this->log_file) ? FILE_APPEND : 0) );
		}
	}

    //! Fetch a list of GL codes
    public function get_gl_codes( $shipment ) {
	    $this->gl_code = array();
	    $raw = $this->shipment_table->database->get_multiple_rows("
	    	SELECT M.ITEM, M.SAGE50_GL
			FROM EXP_SHIPMENT S, EXP_CLIENT_BILLING B, EXP_SAGE50_GLMAP M
			WHERE S.OFFICE_CODE = M.OFFICE_CODE
			AND M.CURRENCY = B.CURRENCY
			AND M.BUSINESS_CODE = S.BUSINESS_CODE
		    AND M.GLTYPE = 'income'
			AND COALESCE(S.CONSOLIDATE_NUM, S.SHIPMENT_CODE) = B.SHIPMENT_ID
			AND S.SHIPMENT_CODE = $shipment
		    ORDER by M.ITEM ASC");
		if( is_array($raw) && count($raw) > 0 ) {
			$log = [];
			foreach($raw as $row) {
				$this->gl_code[$row["ITEM"]] = $row["SAGE50_GL"];
				$log[] = $row["ITEM"]." => ".$row["SAGE50_GL"];
			}
			$this->log_event( "   ".__METHOD__.": shipment ".$shipment." (".
				implode(", ", $log).")" );
		} else {
			$this->log_event( "   ".__METHOD__.": shipment ".$shipment." NO GL CODES FOUND" );
		}
		
		if( $this->debug ) {
			echo "<p>".__METHOD__.": GL codes = </p>
			<pre>";
			var_dump($this->gl_code);
			echo "</pre>";
		}

		return $this->gl_code;
    }
    
	//! Lookup a GL code for an item
	public function get_gl_code( $item ) {
		if( $this->debug ) echo "<p>".__METHOD__.": entry, item = ".$item."</p>";
	    return !empty($this->gl_code[$item]) ? $this->gl_code[$item] :
	    	(!empty($this->gl_code['default']) ? $this->gl_code['default'] : 'MISSING' );
    }
    
    //! SCR# 380 - map terms to days offset
    private function map_terms( $terms ) {
	    $result = '';
	    
	    if( preg_match( '/^net\s(\d+)$/', strtolower($terms), $matches ) )
			$result = ' +'.$matches[1].' days';
			
		return $result;
	}

    //! Initialize gathering data for invoice
    private function invoice_initialize( $row ) {
		if( $this->debug ) echo "<p>".__METHOD__.": entry, shipment = ".$row['SHIPMENT_CODE']."</p>";
		$this->invoice_lines = array();
		
		$terms = empty($row['TERMS']) ? $this->invoice_terms : $row['TERMS'];
				
		$this->fixed = array(	//! Fixed columns, same for each row for the invoice
			'CUSTOMER_ID'		=> $row['CUSTOMER_ID'],
			'SS_NUMBER'				=> empty($row['SS_NUMBER2']) ? $row['SS_NUMBER'] : $row['SS_NUMBER2'],
			'AIN'					=> '',
			'ICM'					=> 'FALSE',
			'IPBI'					=> 'FALSE',
			'ASOF'					=> date("n/j/y", strtotime($row['ASOF'])),
			'ISB'					=> '',
			'IQUOTE'				=> 'FALSE',
			'IQN'					=> '',
			'IQG'					=> '',
			'IDS'					=> 'FALSE',
			
			'CONS_NAME'				=> $row['CONS_NAME'],
			'CONS_ADDR1'			=> $row['CONS_ADDR1'],
			'CONS_ADDR2'			=> $row['CONS_ADDR2'],
			'CONS_CITY'				=> $row['CONS_CITY'],
			'CONS_STATE'			=> $row['CONS_STATE'],
			'CONS_ZIP'				=> $row['CONS_ZIP'],
			'CONS_COUNTRY'			=> $row['CONS_COUNTRY'],
			
			'CUSTOMER_NUMBER'		=> (string) $row['CUSTOMER_NUMBER'],
			'ISV'					=> 'None',
			'ISD'					=> date("n/j/y", strtotime($row['ASOF'])),
			'INV_NOTE'				=> '',
			'INP'					=> 'FALSE',
			'STM_NOTE'				=> '',
			'SNPB'					=> 'FALSE',
			'INT_NOTE'				=> '',
			'BEG_BAL'				=> 'FALSE',
			'IASO'					=> 'FALSE',
			'IAP'					=> 'FALSE',

			//! SCR# 380 - handle terms
			'TERMS'					=> $terms,
			'IDUE'					=> date("n/j/y", strtotime($row['ASOF'].
											$this->map_terms($terms) ) ),
			'DISCOUNT'				=> 0,

			'SAGE50_AR'				=> $row['SAGE50_AR'],
			'SAGE50_GL'				=> $row['SAGE50_GL'],
			'ITAX_ID'				=> '',
			'ITAX_AGENCY_ID'		=> '',
		);
		$this->get_gl_codes( $row['SHIPMENT_CODE'] );
		
	}

    //! Return true if there is sufficient data
    // Also, make a note of the reason for later use.
    private function invoice_is_valid( $row ) {
	    
	    //! SCR# 400 - Check if there is no billing info
	    $result = empty($row['SHIPMENT_ID']) && ! empty($row['CONSOLIDATE_NUM']) ||
			( ! empty($row['CUSTOMER_ID']) &&
				! empty($row['SAGE50_AR']) &&
				! empty($row['SAGE50_GL']) );
	    	
	    if( ! $result ) {
		    if( empty($row['BUSINESS_CODE']))
		    	$msg = 'business code not set';
		    else if( empty($row['CUSTOMER_ID']) )
		    	$msg = 'Sage customer ID missing';
		    else if( empty($row['SAGE50_AR']) )
		    	$msg = 'Sage AR account missing';
		    else if( empty($row['SAGE50_GL']) )
		    	$msg = 'Sage GL account unknown';
		    else
		    	$msg = 'unknown issue';
			$this->shipment_table->update($row['SHIPMENT_CODE'],
				array("quickbooks_status_message" => "Blocked: ".$msg ), false );
				$this->log_event( "   ".__METHOD__.": ".$row['SHIPMENT_CODE']." / ".$row['SS_NUMBER']." Blocked: ".$msg );
	    } else {
			$this->shipment_table->update($row['SHIPMENT_CODE'],
				array("quickbooks_status_message" => "OK" ), false );
			//! SCR# 395 - only change state if not already billed
			if( $row['BILLING_STATUS'] == $this->shipment_table->billing_behavior_state['approved'] ) {
				$this->log_event( "   ".__METHOD__.": ".$row['SHIPMENT_CODE']." / ".$row['SS_NUMBER']." Update status to billed" );
				$this->shipment_table->change_state_behavior( $row['SHIPMENT_CODE'], 'billed', true );
			} else {
				$this->log_event( "   ".__METHOD__.": ".$row['SHIPMENT_CODE']." / ".$row['SS_NUMBER']." DO NOT Update status to billed" );
			}
	    }	
	    
		if( $this->debug ) echo "<p>".__METHOD__.": return ".($result ? "true" : "false")."</p>";
		return $result;
	}
	
    //! Return true if the invoice is initialized
    private function invoice_is_initialized() {
		$result = is_array($this->fixed) && count($this->fixed) > 0;
		if( $this->debug ) echo "<p>".__METHOD__.": return ".($result ? "true" : "false")."</p>";
	    return $result;
	}
	
    //! Add a comment line to an invoice
    private function invoice_add_comment( $comment ) {
		if( $this->debug ) echo "<p>".__METHOD__.": entry $comment</p>";
	    $invoice_line = $this->fixed;
	    $invoice_line['IDESCRIPTION']	= ($this->is_consolidated ? $this->ss_number.', ' : '').str_replace($this->eol_strings, ' ', $comment);
	    $invoice_line['UNIT_PRICE']		= 0;
	    $invoice_line['ITAX_TYPE']		= $this->tax_type;
	    $invoice_line['IQUANTITY']		= 0;
	    $invoice_line['IAMOUNT']		= 0;
	    
	    $this->invoice_lines[] = $invoice_line;
    }	

    //! Add a sales amount line
    private function invoice_add_sales_line( $item, $description, $amount, $qty = 1 ) {
		if( $this->debug ) echo "<p>".__METHOD__.": entry $item $description $amount $qty</p>";
	    
	    if( $amount <> 0 ) {
		    $invoice_line = $this->fixed;
		    $invoice_line['SAGE50_GL']		= $this->get_gl_code( $item );
		    $invoice_line['IDESCRIPTION']	= ($this->is_consolidated ? $this->ss_number.', ' : '').str_replace($this->eol_strings, ' ', $description);
		    $invoice_line['UNIT_PRICE']		= $amount;
		    $invoice_line['ITAX_TYPE']		= $this->tax_type;
		    $invoice_line['IQUANTITY']		= $qty;
		    $invoice_line['IAMOUNT']		= $amount * $qty * -1;
		    
		    $this->invoice_lines[] = $invoice_line;
	    }
    }	

     //! Add tax lines
    private function invoice_add_tax_lines( $row ) {
		if( $this->debug ) echo "<p>".__METHOD__.": entry</p>";
	    
	    $tax_info = $this->tax->tax_info( $row['SHIPMENT_CODE'] );
	    if( is_array($tax_info) && count($tax_info) > 0 ) {
		    foreach($tax_info as $row) {
			    $invoice_line = $this->fixed;
			    $invoice_line['IDESCRIPTION']	= ($this->is_consolidated ? $this->ss_number.', ' : '').$row['tax'].' ('.$row['rate'].'% Reg# '.$row['TAX_REG_NUMBER'].')';
			    $invoice_line['UNIT_PRICE']		= 0;
			    $invoice_line['ITAX_TYPE']		= 0;
			    $invoice_line['IQUANTITY']		= 0;
			    $invoice_line['IAMOUNT']		= $row['AMOUNT'] * -1;
			    $invoice_line['SAGE50_AR']		= $row['SAGE50_'.$row['CURRENCY'].'_AR'];
			    $invoice_line['SAGE50_GL']		= $row['SAGE50_GL'];
			    $invoice_line['ITAX_ID']		= $row['SAGE50_SALES_TAX_ID'];
			    $invoice_line['ITAX_AGENCY_ID']	= $row['SAGE50_TAX_AGENCY_ID'];
			    
			    $this->tax_id = $row['SAGE50_SALES_TAX_ID'];
			    
			    $this->invoice_lines[] = $invoice_line;
		    }
	    }
    }	

   //! Add invoice lines for shipment
    private function invoice_shipment_add( $row ) {
		if( $this->debug ) echo "<p>".__METHOD__.": entry, shipment = ".$row['SHIPMENT_CODE']."</p>";

		$billing_pallets		= intval($row['PALLETS']);
		$billing_per_pallets	= floatval($row['PER_PALLETS']);
		$billing_amount_pallets	= floatval($row['PALLETS_RATE']);
		$billing_hand_pallet	= floatval($row['HAND_PALLET']);
		$billing_handling		= floatval($row['HAND_CHARGES']);
		$billing_freight		= floatval($row['FREIGHT_CHARGES']);
		$billing_extra			= floatval($row['EXTRA_CHARGES']);
		$billing_extra_note		= $row['EXTRA_CHARGES_NOTE'];
		
		$billing_loading_free_hrs	= floatval($row['FREE_DETENTION_HOUR']);
		$billing_loading_hrs		= floatval($row['DETENTION_HOUR']);
		$billing_loading_rate		= floatval($row['RATE_PER_HOUR']);
		$billing_loading			= floatval($row['DETENTION_RATE']);
		
		$billing_unloading_free_hrs	= floatval($row['FREE_UN_DETENTION_HOUR']);
		$billing_unloading_hrs		= floatval($row['UNLOADED_DETENTION_HOUR']);
		$billing_unloading_rate		= floatval($row['UN_RATE_PER_HOUR']);
		$billing_unloading			= floatval($row['UNLOADED_DETENTION_RATE']);
		
		$billing_cod			= floatval($row['COD']);
		$billing_mileage		= floatval($row['MILLEAGE']);
		//! SCR# 409 - FSC_COLUMN is text, not float
		$billing_fsc_column		= $row['FSC_COLUMN'];
		$billing_fsc_rate		= floatval($row['FSC_AVERAGE_RATE']);
		$billing_mileage_rate	= floatval($row['RPM']);
		$billing_mileage_total	= floatval($row['RPM_RATE']);
		$billing_surcharge		= floatval($row['FUEL_COST']);

		$billing_stopoff		= floatval($row['STOP_OFF']);
		$billing_stopoff_note	= $row['STOP_OFF_NOTE'];
		$billing_weekend		= floatval($row['WEEKEND']);
		
		$billing_adjustment_title	= $row['ADJUSTMENT_CHARGE_TITLE'];
		$billing_adjustment_charge	= floatval($row['ADJUSTMENT_CHARGE']);

		$selection_fee	= floatval($row['SELECTION_FEE']);
		$discount		= floatval($row['DISCOUNT']);
		$this->ss_number	= $row['SS_NUMBER'];

		//! SCR# 301 - add shipper and cons city
		$this->invoice_add_comment( 'Ship from: '.$row['SHIPPER_NAME'].
			' / '.$row['SHIPPER_CITY'].', '.$row['SHIPPER_STATE'] );
		$this->invoice_add_comment( 'Ship to: '.$row['CONS_NAME'].
			' / '.$row['CONS_CITY'].', '.$row['CONS_STATE'] );
		
		//! PO Numbers
		/*! SCR# 316 - Requested changes
		$pos = array('PO_NUMBER', 'PO_NUMBER2', 'PO_NUMBER3',
			'PO_NUMBER4', 'PO_NUMBER5');
		$got_po = false;
		$notes = '';
		foreach($pos as $po) {
			if( isset($row[$po]) && $row[$po] <> '' ) {
				if( ! $got_po ) {
					$got_po = true;
					$notes .= "PO#s: ";
				} else {
					$notes .= ", ";
				}
				$notes .= $row[$po];
			}
		}
		if( $got_po )
			$this->invoice_add_comment( $notes );
		*/

		//! REF_NUMBER
		if( isset($row['REF_NUMBER']) && $row['REF_NUMBER'] <> '' ) {
			$this->invoice_add_comment( "Reference#: ".$row['REF_NUMBER']);
		}

		//! PICKUP_NUMBER
		if( isset($row['PICKUP_NUMBER']) && $row['PICKUP_NUMBER'] <> '' ) {
			$this->invoice_add_comment( "Pickup#: ".$row['PICKUP_NUMBER']);
		}

		//! CONSOLIDATE_NUM
		/*! SCR# 866 - Requested changes
		if( isset($row['CONSOLIDATE_NUM']) && $row['CONSOLIDATE_NUM'] <> '' ) {
			$this->invoice_add_comment( $this->setting_table->get( 'option', 'CONSOLIDATE_NUM' ).": ".$row['CONSOLIDATE_NUM']);
		}
		*/

		//! BOL
		if( isset($row['BOL_NUMBER']) && $row['BOL_NUMBER'] <> '' )
			$this->invoice_add_comment( "BOL#: ".$row['BOL_NUMBER']);
		
		//! Load/Trip#
		/*! SCR# 316 - Requested changes
		if( isset($row['LOAD_CODE']) && intval($row['LOAD_CODE']) > 0 )
			$this->invoice_add_comment( "Load/Trip#: ".$row['LOAD_CODE']);
		*/
		
		//! SCR# 702 - Get equipment required for a shipment
		if( ! empty($row['EQUIPMENT']))
			$this->invoice_add_comment( "Equipment: ".$row['EQUIPMENT']);
			
		//! SCR# 275 - show details
		if( $this->show_detail ) {
			$details = $this->shipment_table->database->get_multiple_rows(
				"select DETAIL_CODE, SHIPMENT_CODE,
					(SELECT COMMODITY_NAME from EXP_COMMODITY X 
					WHERE X.COMMODITY_CODE = EXP_DETAIL.COMMODITY  LIMIT 0 , 1) AS COMMODITY_NAME,
					(SELECT COMMODITY_DESCRIPTION from EXP_COMMODITY X 
					WHERE X.COMMODITY_CODE = EXP_DETAIL.COMMODITY  LIMIT 0 , 1) AS COMMODITY_DESCRIPTION,
					NOTES, PALLETS, PIECES, 
					(SELECT UNIT_NAME from EXP_UNIT X 
					WHERE X.UNIT_CODE = EXP_DETAIL.PIECES_UNITS AND X.UNIT_TYPE = 'item' LIMIT 0 , 1) AS PIECES_UNITS,
					WEIGHT, DANGEROUS_GOODS, TEMP_CONTROLLED, BILLABLE
				from EXP_DETAIL 
				where SHIPMENT_CODE = ".$row['SHIPMENT_CODE']);

			if( is_array($details) && count($details) > 0 ) {
				/*! SCR# 866 - Requested changes
				$this->invoice_add_comment('Items Shipped:');
				*/
				foreach( $details as $detail ) {
					//! SCR# 316 - Requested changes
					$desc = $detail['COMMODITY_DESCRIPTION'].' ';
					if( ! empty($detail['NOTES']) )
						$desc .= $detail['NOTES'].' ';
					if( isset($detail['PALLETS']) && $detail['PALLETS'] > 0 )
						$desc .= $detail['PALLETS'].' pallets ';
					//! SCR# 300 - use PIECES_UNITS
					if( isset($detail['PIECES']) && $detail['PIECES'] > 0 )
						$desc .= $detail['PIECES'].' '.$detail['PIECES_UNITS'].' ';
					
					if( isset($check[0]['WEIGHT']) && $check[0]['WEIGHT'] > 0 )
						$desc .= $check[0]['WEIGHT'].' LBs ';

					$this->invoice_add_comment($desc);
				}
				
			}
		}

		$this->invoice_add_sales_line( INVOICE_ITEM_PALLETS, 'Pallets', $billing_amount_pallets );
		
		$this->invoice_add_sales_line( INVOICE_ITEM_PALLET_HANDLING, 'Pallet Handling', $billing_hand_pallet );
		
		$this->invoice_add_sales_line( INVOICE_ITEM_FRIEGHT, 'Freight charges', $billing_freight );
		
		$this->invoice_add_sales_line( INVOICE_ITEM_HANDLING, 'Handling charges', $billing_handling );
		
		$this->invoice_add_sales_line( INVOICE_ITEM_FRIEGHT, isset($billing_adjustment_title) && $billing_adjustment_title <> '' ? $billing_adjustment_title : 'Adjustment', $billing_adjustment_charge );
		
		$this->invoice_add_sales_line( INVOICE_ITEM_EXTRA, 'Extra charges: '.$billing_extra_note, $billing_extra );
		
		$this->invoice_add_sales_line( INVOICE_ITEM_LOADING, 'Loading Detention', $billing_loading );
			
		$this->invoice_add_sales_line( INVOICE_ITEM_UNLOADING, 'Unloading Detention', $billing_unloading );
			
		//! SCR 162 - adjust text
		//! SCR# 168 - if it does not match, use total only
		//! SCR# 300 - format the number
		if( $billing_mileage * $billing_mileage_rate == $billing_mileage_total )
			$this->invoice_add_sales_line( INVOICE_ITEM_MILEAGE, 'Mileage: '.$billing_mileage.' miles @ '.number_format($billing_mileage_rate,2), $billing_mileage_rate, $billing_mileage );
		else
			$this->invoice_add_sales_line( INVOICE_ITEM_MILEAGE, 'Mileage: '.$billing_mileage.' miles', $billing_mileage_total );
			
		if( $this->debug ) echo "<p>".__METHOD__.": fsc_column = $billing_fsc_column</p>";
		if( $billing_fsc_rate > 0 ) {
			//! SCR# 316 - Requested changes
			if( $billing_fsc_column == 'Percent' )
				$this->invoice_add_sales_line( INVOICE_ITEM_SURCHARGE, 'FSC: '.number_format($billing_fsc_rate,2).'%', $billing_surcharge );
			else if( $billing_mileage * $billing_fsc_rate == $billing_surcharge )
				$this->invoice_add_sales_line( INVOICE_ITEM_SURCHARGE, 'FSC: '.$billing_mileage.' miles @ '.number_format($billing_fsc_rate,2), $billing_fsc_rate, $billing_mileage );
			else
				$this->invoice_add_sales_line( INVOICE_ITEM_SURCHARGE, 'FSC: '.$billing_mileage.' miles @ '.number_format($billing_fsc_rate,2), $billing_surcharge );
		} else {
			$this->invoice_add_sales_line( INVOICE_ITEM_SURCHARGE, 'FSC: '.$billing_mileage.' miles', $billing_surcharge );
		}
			
		$this->invoice_add_sales_line( INVOICE_ITEM_STOPOFF, 'Stopoff charges: '.$billing_stopoff_note, $billing_stopoff );
			
		$this->invoice_add_sales_line( INVOICE_ITEM_WEEKEND, 'Weekend/Holiday', $billing_weekend );
			
		$this->invoice_add_sales_line( INVOICE_ITEM_SELECTION, 'Selection fee', $selection_fee );
		
		$this->invoice_add_sales_line( INVOICE_ITEM_DISCOUNT, 'Discount', -1 * $discount );
		
		//! Add client rates
		$rates = $this->database->get_multiple_rows("
			SELECT RATE_CODE, RATE_NAME, CATEGORY, RATES
			FROM EXP_CLIENT_BILLING_RATES
			WHERE BILLING_ID = (SELECT CLIENT_BILLING_ID FROM EXP_CLIENT_BILLING
			WHERE SHIPMENT_ID = ".$row['SHIPMENT_CODE'].")");

		if( is_array($rates) && count($rates) > 0 ) {
			foreach($rates as $r) {
				$this->invoice_add_sales_line( INVOICE_ITEM_OTHER, $r["RATE_CODE"].' '.$r["RATE_NAME"].' ('.$r["CATEGORY"].')', $r["RATES"] );
			}
		}
		
		$this->invoice_add_tax_lines( $row );
	}
	
    //! Add distribution numbers
    private function invoice_add_numbers() {
		if( $this->debug ) echo "<p>".__METHOD__.": entry</p>";
	    $num_lines = count($this->invoice_lines);
	    for( $line = 0; $line < $num_lines; $line++) {
		    $this->invoice_lines[$line]['INUM_DIST'] = $num_lines;
		    if( $this->invoice_lines[$line]['ITAX_TYPE'] > 0 )
		    	$this->invoice_lines[$line]['INUM_DIST2'] = $line+1;
		    else
		    	$this->invoice_lines[$line]['INUM_DIST2'] = 0;
		    $this->invoice_lines[$line]['INUM_DIST3'] = 0;
		    if( ! empty($this->tax_id) && empty($this->invoice_lines[$line]['ITAX_ID']))
		    	$this->invoice_lines[$line]['ITAX_ID'] = $this->tax_id;
	    }
	}

    //! Complete an invoice
    private function invoice_complete() {
		if( $this->debug ) echo "<p>".__METHOD__.": entry</p>";
	    $this->log_event( "   ".__METHOD__.": Added ".$this->invoice_lines[0]['SS_NUMBER']);
		$this->invoice_add_numbers();
		$this->invoices = array_merge( $this->invoices, $this->invoice_lines );
		unset( $this->fixed, $this->invoice_lines );
		$this->fixed = false;
	}

	// Fetch one or more rows
	public function fetch_rows( $match = "", $fields = "*", $order = "", $limit = "", $groupby = "", $match2 = "" ) {
		if( $this->debug ) echo "<p>fetch_rows $match</p>";
		
		$result = $this->database->get_multiple_rows("
			SELECT SHIPMENT_CODE, REF_NUMBER, SS_NUMBER, PICKUP_NUMBER, CUSTOMER_NUMBER,
			BILLING_STATUS,
			(SELECT SS_NUMBER FROM EXP_SHIPMENT S
			WHERE S.SHIPMENT_CODE = EXP_SHIPMENT.CONSOLIDATE_NUM) SS_NUMBER2,
			BILLTO_CLIENT_CODE, PO_NUMBER, PO_NUMBER2, PO_NUMBER3,
			PO_NUMBER4, PO_NUMBER5, SHIPPER_NAME, BUSINESS_CODE, SHIPPER_CITY,
			SHIPPER_STATE, CONS_NAME, CONSOLIDATE_NUM, BOL_NUMBER, LOAD_CODE,
			CONS_ADDR1, CONS_ADDR2, CONS_CITY, CONS_STATE, CONS_ZIP,
			CONS_COUNTRY, GET_ASOF( SHIPMENT_CODE ) AS ASOF,
			(SELECT SAGE50_CLIENTID FROM EXP_CLIENT
			WHERE BILLTO_CLIENT_CODE = CLIENT_CODE) AS CUSTOMER_ID,
			SAGE50_AR(SHIPMENT_CODE) AS SAGE50_AR,
			SAGE50_INV_GL(SHIPMENT_CODE, 'default') AS SAGE50_GL,
			(SELECT GROUP_CONCAT(L.ITEM ORDER BY 1 ASC SEPARATOR ', ')
			FROM EXP_ITEM_LIST L, EXP_EQUIPMENT_REQ R
			WHERE R.SOURCE_TYPE = 'shipment'
			AND R.SOURCE_CODE = SHIPMENT_CODE
			AND R.ITEM_CODE = L.ITEM_CODE) AS EQUIPMENT,
			EXP_CLIENT_BILLING.*,
			(SELECT ITEM FROM EXP_ITEM_LIST WHERE ITEM_CODE = EXP_CLIENT_BILLING.TERMS) AS TERMS
			FROM EXP_SHIPMENT
			LEFT JOIN EXP_CLIENT_BILLING
			ON SHIPMENT_CODE = SHIPMENT_ID
			WHERE $match
			
			ORDER BY CONSOLIDATE_NUM ASC, SHIPMENT_CODE ASC" );
		
		if( $this->debug ) {
			echo "<p>".__METHOD__.": result for $this->table_name = </p>
			<pre>";
			var_dump($result);
			echo "</pre>";
		}

		if( is_array($result) && count($result) > 0 ) {
			$this->invoices = array();
			$previous_consolidate_number = '';
			$this->is_consolidated = false;
			foreach($result as $row) {
				if( ! $this->invoice_is_valid( $row ) ) continue;
				
				if( isset($row['CONSOLIDATE_NUM']) && $row['CONSOLIDATE_NUM'] <> '' ) {
					if( $this->debug ) echo "<p>".__METHOD__.": consolidated # = ".$row['CONSOLIDATE_NUM']." prev # = $previous_consolidate_number</p>";
					$this->is_consolidated = true;
					if( $row['CONSOLIDATE_NUM'] <> $previous_consolidate_number ) {
						if( $this->invoice_is_initialized() )
							$this->invoice_complete();
						$this->invoice_initialize( $row );
					}
				} else {
					if( $this->debug ) echo "<p>".__METHOD__.": NOT consolidated</p>";
					$this->is_consolidated = false;
					$this->invoice_initialize( $row );
				}

				$this->invoice_shipment_add( $row );
				
				if( isset($row['CONSOLIDATE_NUM']) && $row['CONSOLIDATE_NUM'] <> '' )
					$previous_consolidate_number = $row['CONSOLIDATE_NUM'];
				else
					$this->invoice_complete();
			}
			if( $this->invoice_is_initialized() )
				$this->invoice_complete();
		}

		if( $this->debug ) {
			echo "<p>invoices for $this->table_name = </p>
			<pre>";
			var_dump($this->invoices);
			echo "</pre>";
		}
		return $this->invoices;
	}
}

class sts_sage50_bill extends sts_table {
	private $setting_table;
	private $multi_company;
	private $enabled;
	private $bill_terms;
	private $log_file;		//! SCR# 519 - log exports to Sage50
	private $tax;
	
	private $bill_lines;
	private $fixed = false;
	private $bills;
	private $load_table;
	//! SCR# 279 - strings to remove before export
	private $eol_strings = array("\r\n", "\r", "\n", '\r\n', '\r', '\n');

	// Constructor does not need the table name
	public function __construct( $database, $debug = false ) {
		global $sts_log_to_file;

		$this->debug = $debug;
		$this->primary_key = "LOAD_CODE";
		if( $this->debug ) echo "<p>Create sts_sage50_bill</p>";
		$this->setting_table = sts_setting::getInstance($database, $debug);
		$this->load_table = sts_load::getInstance($database, $debug);
		$this->multi_company = ($this->setting_table->get( 'option', 'MULTI_COMPANY' ) == 'true');
		$this->bill_terms = $this->setting_table->get( 'api', 'SAGE50_BILL_TERMS' );
		$this->enabled = ($this->setting_table->get( 'api', 'EXPORT_SAGE50_CSV' ) == 'true');

		$this->tax = sts_company_tax::getInstance($database, $debug);

		if( $sts_log_to_file )
			$this->log_file = $this->setting_table->get( 'option', 'SAGE50_LOG_FILE' );
		parent::__construct( $database, LOAD_TABLE, $debug);
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

	//! SCR# 519 - log exports to Sage50
	public function log_event( $event ) {		
		if( isset($this->log_file) && $this->log_file <> '' &&
			 ! is_dir($this->log_file) ) {
			file_put_contents($this->log_file, date('m/d/Y h:i:s A')." ".$event.PHP_EOL,
				(file_exists($this->log_file) ? FILE_APPEND : 0) );
		}
	}

    //! SCR# 380 - map terms to days offset
    private function map_terms( $terms ) {
	    $result = '';
	    
	    if( preg_match( '/^net\s(\d+)$/', strtolower($terms), $matches ) )
			$result = ' +'.$matches[1].' days';
			
		return $result;
	}

    //! Initialize gathering data for bill
    //! SCR# 635 -handle lumper bill payment
    private function bill_initialize( $row, $lumper = false ) {
		$this->bill_lines = array();

		$terms = empty($row['TERMS']) ? $this->bill_terms : $row['TERMS'];

		$this->fixed = array(	//! Fixed columns, same for each row for the bill
			'VENDOR_ID'				=> $lumper ? $row['LUMPER_ID'] : $row['VENDOR_ID'],
			'SS_NUMBER'				=> $row['SS_NUMBER'],
			'BAI'					=> '',
			'BCM'					=> 'FALSE',
			'ASOF'					=> date("n/j/y", strtotime($row['ASOF'])),
			'BDS'					=> 'FALSE',
			'BCSO'					=> '',
			'BWOB'					=> 'TRUE',
			'BCID'					=> '',
			'BCIN'					=> '',

			'CONS_NAME'				=> '',
			'CONS_ADDR1'			=> '',
			'CONS_ADDR1'			=> '',
			'CONS_CITY'				=> '',
			'CONS_STATE'			=> '',
			'CONS_ZIP'				=> '',
			'CONS_COUNTRY'			=> '',

			'BDD'					=> date("n/j/y", strtotime($row['ASOF'])),

			//! SCR# 380 - handle terms
			'TERMS'					=> $terms,
			'BDUE'					=> date("n/j/y", strtotime($row['ASOF'].
											$this->map_terms($terms) ) ),
			'BDA'					=> 0,

			'SAGE50_AP'				=> $lumper ? $row['LUMPER_AP'] : $row['SAGE50_AP'],
			'BSV'					=> 'None',
			'BPON'					=> '',
			'BNPA'					=> 'FALSE',
			'BBBT'					=> 'FALSE',
			'BATPO'					=> 'FALSE',
			
			'PO_NUMBER'				=> '',
			'PO_DIST'				=> 0,
			'BSQ'					=> 0,
			'BID'					=> '',
			'BSN'					=> '',
			'BUM'					=> 'Each',

			'SAGE50_GL'				=> $lumper ? $row['LUMPER_GL'] : $row['SAGE50_GL'],
		);
	}

    //! Return true if there is sufficient data
    // Also, make a note of the reason for later use.
    private function bill_is_valid( $row ) {
	    $result = ! empty($row['VENDOR_ID']) && ! empty($row['SAGE50_AP']) &&
	    	! empty($row['SAGE50_GL']);
	    	
	    if( ! $result ) {
		    if( empty($row['BUSINESS_CODE']))
		    	$msg = 'business code not set';
		    else if( empty($row['VENDOR_ID']) )
		    	$msg = 'Sage carrier/vendor ID missing';
		    else if( empty($row['SAGE50_AP']) )
		    	$msg = 'Sage AP account missing';
		    else if( empty($row['SAGE50_GL']) )
		    	$msg = 'Sage GL account unknown';
		    else
		    	$msg = 'unknown issue';
			$this->load_table->update($row['LOAD_CODE'],
				array("quickbooks_status_message" => "Blocked: ".$msg ), false );
			$this->log_event( "   ".__METHOD__.": ".$row['LOAD_CODE']." Blocked: ".$msg );
	    } else {
		    // Don't update if in debug
		    if( ! $this->debug ) {
				$this->load_table->update($row['LOAD_CODE'],
					array("quickbooks_status_message" => "OK" ), false );
				$this->load_table->change_state_behavior( $row['LOAD_CODE'], 'billed' );
			}
	    }	
	    
		return $result;
	}
	
    //! Return true if the invoice is initialized
    private function bill_is_initialized() {
	    return is_array($this->fixed) && count($this->fixed) > 0;
	}
	
    //! Add a comment line to an bill
    private function bill_add_comment( $comment ) {
	    $bill_line = $this->fixed;
	    $bill_line['BDESCRIPTION']	= str_replace($this->eol_strings, ' ', $comment);
	    $bill_line['UNIT_PRICE']	= 0;
	    $bill_line['BQUANTITY']		= 0;
	    $bill_line['BAMOUNT']		= 0;
	    
	    $this->bill_lines[] = $bill_line;
    }	

    //! Add a bill amount line
    private function bill_add_line( $description, $amount ) {
	    
	    if( $amount <> 0 ) {
		    $bill_line = $this->fixed;
		    $bill_line['BDESCRIPTION']	= str_replace($this->eol_strings, ' ', $description);
		    $bill_line['UNIT_PRICE']	= $amount;
		    $bill_line['BQUANTITY']		= 1;
		    $bill_line['BAMOUNT']		= $amount;
		    
		    $this->bill_lines[] = $bill_line;
	    }
    }	

     //! Add tax lines
    private function bill_add_lumper_tax_lines( $row ) {
		if( $this->debug ) echo "<p>".__METHOD__.": entry</p>";
	    
	    $tax_info = $this->tax->lumper_tax_info( $row['LOAD_CODE'] );
	    
	    if( is_array($tax_info) && count($tax_info) > 0 ) {
		    foreach($tax_info as $row) {
			    $bill_line = $this->fixed;
			    $bill_line['BDESCRIPTION']		= $row['tax'].' ('.$row['rate'].'% Reg# '.$row['TAX_REG_NUMBER'].')';
			    $bill_line['UNIT_PRICE']		= $row['AMOUNT'];
			    $bill_line['ITAX_TYPE']			= 0;
			    $bill_line['BQUANTITY']			= 1;
			    $bill_line['BAMOUNT']			= $row['AMOUNT'];
			    $bill_line['SAGE50_AP']			= $row['SAGE50_'.$row['CURRENCY'].'_AP'];
			    $bill_line['SAGE50_GL']			= $row['SAGE50_GL'];
			    $bill_line['ITAX_ID']			= $row['SAGE50_SALES_TAX_ID'];
			    $bill_line['ITAX_AGENCY_ID']	= $row['SAGE50_TAX_AGENCY_ID'];
			    
			    //$this->tax_id = $row['SAGE50_SALES_TAX_ID'];
			    
			    $this->bill_lines[] = $bill_line;
		    }
	    }
    }	

    //! Add bill lines for load
    private function bill_load_add( $row, $lumper = false ) {
		if( $this->debug ) echo "<p>".__METHOD__.": entry".($lumper ? '/lumper' : '')."</p>";

		/*
			$this->bill_add_comment( 'Ship from: '.$row['SHIPPER_NAME'].
			' / '.$row['SHIPPER_STATE'] );
		$this->bill_add_comment( 'Ship to: '.$row['CONS_NAME'].
			' / '.$row['CONS_STATE'] );
		*/
		
		//! Load/Trip#
		if( isset($row['LOAD_CODE']) && $row['LOAD_CODE'] <> '' )
			$this->bill_add_comment( "Load/Trip#: ".$row['LOAD_CODE']);

		if( $lumper) {
			$this->bill_add_line( 'Lumper Fee', $row['CARRIER_HANDLING'] );

			$this->bill_add_lumper_tax_lines( $row );
		} else {
			$this->bill_add_line( 'Base', $row['CARRIER_BASE'] );
			
			$this->bill_add_line( 'Fuel Surcharge', $row['CARRIER_FSC'] );
			
			//! SCR# 635 - if using lumper, omit Handling charges
			if( ! (isset($row['LUMPER']) && $row['LUMPER'] > 0) )
				$this->bill_add_line( 'Handling charges', $row['CARRIER_HANDLING'] );
		}
	}
	
    //! Add distribution numbers
    private function bill_add_numbers() {
	    $num_lines = count($this->bill_lines);
	    for( $line = 0; $line < $num_lines; $line++) {
		    $this->bill_lines[$line]['INUM_DIST'] = $num_lines;
		    $this->bill_lines[$line]['INUM_DIST2'] = $line+1;
		    $this->bill_lines[$line]['INUM_DIST3'] = 0;
	    }
	}

    //! Complete a bill
    private function bill_complete() {
	    $this->log_event( "   ".__METHOD__.": Added ".$this->bill_lines[0]['SS_NUMBER']);
		$this->bill_add_numbers();
		$this->bills = array_merge( $this->bills, $this->bill_lines );
		unset( $this->fixed, $this->bill_lines );
		$this->fixed = false;
	}

	// Fetch one or more rows
	public function fetch_rows( $match = "", $fields = "*", $order = "", $limit = "", $groupby = "", $match2 = "" ) {
		if( $this->debug ) echo "<p>fetch_rows $match</p>";
		
		//! SCR# 635 - handle separate lumper bill payment
		$result = $this->database->get_multiple_rows("
			SELECT L.*, GET_LOAD_ASOF(L.LOAD_CODE) AS ASOF,
			(SELECT SAGE50_VENDORID FROM EXP_CARRIER
			WHERE CARRIER = CARRIER_CODE) AS VENDOR_ID,

			(SELECT SAGE50_VENDORID FROM EXP_CARRIER
			WHERE LUMPER = CARRIER_CODE
			AND CARRIER_TYPE IN ('lumper', 'shag')) AS LUMPER_ID,
			SAGE50_LUMPER_AP(LOAD_CODE) AS LUMPER_AP,
			SAGE50_LUMPER_GL(LOAD_CODE) AS LUMPER_GL,
			
			SAGE50_AP(LOAD_CODE) AS SAGE50_AP,
			SAGE50_BILL_GL(LOAD_CODE) AS SAGE50_GL,
			
			(SELECT BUSINESS_CODE FROM EXP_SHIPMENT S
				WHERE S.LOAD_CODE = L.LOAD_CODE
		        AND COALESCE(BUSINESS_CODE, 0) > 0
				ORDER BY S.SHIPMENT_CODE ASC
				LIMIT 1) AS BUSINESS_CODE,
			(SELECT SS_NUMBER FROM EXP_SHIPMENT S
				WHERE S.LOAD_CODE = L.LOAD_CODE
				ORDER BY S.SS_NUMBER ASC
				LIMIT 1) AS SS_NUMBER,
			(SELECT ITEM FROM EXP_ITEM_LIST WHERE ITEM_CODE = L.TERMS) AS TERMS

			FROM EXP_LOAD L
			WHERE $match
			
			ORDER BY L.LOAD_CODE ASC" );
		
		if( $this->debug ) {
			echo "<p>result for $this->table_name = </p>
			<pre>";
			var_dump($result);
			echo "</pre>";
		}

		if( is_array($result) && count($result) > 0 ) {
			$this->bills = array();
			foreach($result as $row) {
				if( ! $this->bill_is_valid( $row ) ) continue;
				
				//! SCR# 635 - If CARRIER_TOTAL is zero, skip carrier bill
				if( isset($row['CARRIER_TOTAL']) && $row['CARRIER_TOTAL'] > 0 ) {
					$this->bill_initialize( $row );
					$this->bill_load_add( $row );
					$this->bill_complete();
				}
				
				if( $this->debug ) echo "<p>".__METHOD__.": #lines = ".count($this->bills)."</p>";
				
				//! SCR# 635 - handle separate lumper bill payment
				if( isset($row['LUMPER']) && $row['LUMPER'] > 0 ) {
					$this->bill_initialize( $row, true );
					$this->bill_load_add( $row, true );
					$this->bill_complete();
					if( $this->debug ) echo "<p>".__METHOD__.": #lines = ".count($this->bills)."</p>";
				}
			}
		}

		if( $this->debug ) {
			echo "<p>bills for $this->table_name = </p>
			<pre>";
			var_dump($this->bills);
			echo "</pre>";
		}
		return $this->bills;
	}
}

//! Layout of columns for Sage 50 Invoice
$sts_csv_sage50_invoice_layout = array(
	'CUSTOMER_ID' => array( 'label' => 'Customer ID', 'format' => 'text' ),
	'SS_NUMBER' =>	array( 'label' => 'Invoice/CM #', 'format' => 'text' ),
	'AIN' =>	array( 'label' => 'Apply to Invoice Number', 'format' => 'text' ),
	'ICM' =>	array( 'label' => 'Credit Memo', 'format' => 'text' ),
	'IPBI' =>	array( 'label' => 'Progress Billing Invoice', 'format' => 'text' ),
	'ASOF' => array( 'label' => 'Date', 'format' => 'date' ),
	'ISB' =>	array( 'label' => 'Ship By', 'format' => 'text' ),
	'IQUOTE' =>	array( 'label' => 'Quote', 'format' => 'text' ),
	'IQN' =>	array( 'label' => 'Quote #', 'format' => 'text' ),
	'IQG' =>	array( 'label' => 'Quote Good Thru Date', 'format' => 'text' ),
	'IDS' =>	array( 'label' => 'Drop Ship', 'format' => 'text' ),

	'CONS_NAME' =>	array( 'label' => 'Ship to Name', 'format' => 'text' ),
	'CONS_ADDR1' => array( 'label' => 'Ship to Address-Line One', 'format' => 'text' ),
	'CONS_ADDR2' => array( 'label' => 'Ship to Address-Line Two', 'format' => 'text' ),
	'CONS_CITY' => array( 'label' => 'Ship to City', 'format' => 'text' ),
	'CONS_STATE' => array( 'label' => 'Ship to State', 'format' => 'text' ),
	'CONS_ZIP' => array( 'label' => 'Ship to Zipcode', 'format' => 'text' ),
	'CONS_COUNTRY' => array( 'label' => 'Ship to Country', 'format' => 'text' ),

	'CUSTOMER_NUMBER' => array( 'label' => 'Customer PO', 'format' => 'text' ),
	'ISV' => array( 'label' => 'Ship Via', 'format' => 'text' ),
	'ISD' => array( 'label' => 'Ship Date', 'format' => 'text' ),

	'IDUE' => array( 'label' => 'Date Due', 'format' => 'date' ),
	'DISCOUNT' => array( 'label' => 'Discount Amount', 'format' => 'text' ),
	'TERMS' => array( 'label' => 'Displayed Terms', 'format' => 'text' ),

	'SAGE50_AR' => array( 'label' => 'Accounts Receivable Account', 'format' => 'text' ),
	'ITAX_ID' => array( 'label' => 'Sales Tax ID', 'format' => 'text' ),
	'INV_NOTE' => array( 'label' => 'Invoice Note', 'format' => 'text' ),
	'INP' => array( 'label' => 'Note Prints After Line Items', 'format' => 'text' ),

	'STM_NOTE' => array( 'label' => 'Statement Note', 'format' => 'text' ),
	'SNPB' => array( 'label' => 'Stmt Note Prints Before Ref', 'format' => 'text' ),
	'INT_NOTE' => array( 'label' => 'Internal Note', 'format' => 'text' ),
	'BEG_BAL' => array( 'label' => 'Beginning Balance Transaction', 'format' => 'text' ),

	'INUM_DIST' => array( 'label' => 'Number of Distributions', 'format' => 'number' ),
	'INUM_DIST2' => array( 'label' => 'Invoice/CM Distribution', 'format' => 'number' ),
	'INUM_DIST3' => array( 'label' => 'Apply to Invoice Distribution', 'format' => 'number' ),
	'IASO' => array( 'label' => 'Apply To Sales Order', 'format' => 'text' ),
	'IAP' => array( 'label' => 'Apply to Proposal', 'format' => 'text' ),
	'IQUANTITY' => array( 'label' => 'Quantity', 'format' => 'number' ),

	'IDESCRIPTION' => array( 'label' => 'Description', 'format' => 'number' ),
	'SAGE50_GL' => array( 'label' => 'G/L Account', 'format' => 'text' ),
	
	'UNIT_PRICE' => array( 'label' => 'Unit Price', 'format' => 'number' ),
	'ITAX_TYPE' => array( 'label' => 'Tax Type', 'format' => 'number' ),
	'IAMOUNT' => array( 'label' => 'Amount', 'format' => 'number' ),
	'ITAX_AGENCY_ID' => array( 'label' => 'Sales Tax Agency ID', 'format' => 'text' ),
);

//! Layout of columns for Sage 50 bill/purchase transaction
$sts_csv_sage50_bill_layout = array(
	'VENDOR_ID' => array( 'label' => 'Vendor ID', 'format' => 'text' ),
	'SS_NUMBER' =>	array( 'label' => 'Invoice/CM #', 'format' => 'text' ),
	'BAI' =>	array( 'label' => 'Apply to Invoice Number', 'format' => 'text' ),
	'BCM' =>	array( 'label' => 'Credit Memo', 'format' => 'text' ),
	'ASOF' => array( 'label' => 'Date', 'format' => 'date' ),
	'BDS' =>	array( 'label' => 'Drop Ship', 'format' => 'text' ),
	'BCSO' =>	array( 'label' => 'Customer SO #', 'format' => 'text' ),
	'BWOB' =>	array( 'label' => 'Waiting on Bill', 'format' => 'text' ),
	'BCID' =>	array( 'label' => 'Customer ID', 'format' => 'text' ),
	'BCIN' =>	array( 'label' => 'Customer Invoice #', 'format' => 'text' ),
	'CONS_NAME' =>	array( 'label' => 'Ship to Name', 'format' => 'text' ),
	'CONS_ADDR1' => array( 'label' => 'Ship to Address-Line One', 'format' => 'text' ),
	'CONS_ADDR1' => array( 'label' => 'Ship to Address-Line Two', 'format' => 'text' ),
	'CONS_CITY' => array( 'label' => 'Ship to City', 'format' => 'text' ),
	'CONS_STATE' => array( 'label' => 'Ship to State', 'format' => 'text' ),
	'CONS_ZIP' => array( 'label' => 'Ship to Zipcode', 'format' => 'text' ),
	'CONS_COUNTRY' => array( 'label' => 'Ship to Country', 'format' => 'text' ),
	'BDUE' => array( 'label' => 'Date Due', 'format' => 'date' ),
	'BDD' => array( 'label' => 'Discount Date', 'format' => 'date' ),
	'BDA' => array( 'label' => 'Discount Amount', 'format' => 'text' ),
	'SAGE50_AP' => array( 'label' => 'Accounts Payable Account', 'format' => 'text' ),
	'BSV' => array( 'label' => 'Ship Via', 'format' => 'text' ),

	'BPON' => array( 'label' => 'P.O. Note', 'format' => 'text' ),
	'BNPA' => array( 'label' => 'Note Prints After Line Items', 'format' => 'text' ),
	'BBBT' => array( 'label' => 'Beginning Balance Transaction', 'format' => 'text' ),
	'BATPO' => array( 'label' => 'Applied To Purchase Order', 'format' => 'text' ),

	'INUM_DIST' => array( 'label' => 'Number of Distributions', 'format' => 'number' ),
	'INUM_DIST2' => array( 'label' => 'Invoice/CM Distribution', 'format' => 'number' ),
	'INUM_DIST3' => array( 'label' => 'Apply to Invoice Distribution', 'format' => 'number' ),
	'PO_NUMBER' => array( 'label' => 'PO Number', 'format' => 'text' ),
	'PO_DIST' => array( 'label' => 'PO Distribution', 'format' => 'number' ),
	'BQUANTITY' => array( 'label' => 'Quantity', 'format' => 'number' ),
	'BSQ' => array( 'label' => 'Stocking Quantity', 'format' => 'number' ),
	'BID' => array( 'label' => 'Item ID', 'format' => 'text' ),
	'BID' => array( 'label' => 'Serial Number', 'format' => 'text' ),
	//'BUM' => array( 'label' => 'U/M ID', 'format' => 'text' ),

	'BDESCRIPTION' => array( 'label' => 'Description', 'format' => 'number' ),
	'SAGE50_GL' => array( 'label' => 'G/L Account', 'format' => 'text' ),
	'UNIT_PRICE' => array( 'label' => 'Unit Price', 'format' => 'number' ),
	'BAMOUNT' => array( 'label' => 'Amount', 'format' => 'number' ),
	'TERMS' => array( 'label' => 'Displayed Terms', 'format' => 'text' ),
);

}
?>