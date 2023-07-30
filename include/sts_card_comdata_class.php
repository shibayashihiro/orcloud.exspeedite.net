<?php

// $Id: sts_card_comdata_class.php 4350 2021-03-02 19:14:52Z duncan $
// Comdata class - everything to do with decoding and importing

// no direct access
defined('_STS_INCLUDE') or die('Restricted access');
require_once( "sts_card_class.php" );

class sts_card_comdata extends sts_card {
	private $tokens;

		// FROM	TO	LEN	FMT	DESCRIPTION	COMMENT
	public $header_record = array(
		array(1, 6, 6, 'C', 'Record Identifer', 'Constant "000001"' ),
		array(7, 11, 5, 'C', 'CDN Company Accounting Code', 'CCNNN' ),
		array(12, 15, 4, 'C', 'Filler', 'Now refer to 324-333' ),
		array(16, 21, 6, 'N', 'Transaction Date', 'YYMMDD' )
	);	

		// FROM	TO	LEN	FMT	DESCRIPTION	COMMENT
	public $detail_record = array(
		array(1, 2, 2, 'C', 'Record Identifer', 'Constant "01"' ),
		array(3, 7, 5, 'C', 'CDN Company Accounting Code', 'CCNNN' ),
		array(8, 11, 4, 'C', 'Filler', 'Now refer to 324-333' ),
		array(12, 17, 6, 'N', 'Transaction Date', 'YYMMDD' ),
		array(18, 18, 1, 'C', 'Transaction Number Indicator', '0=less than 100,000
1=>100,000
2=>200,000' ),
		array(19, 20, 2, 'C', 'Transaction Date', 'DD' ),
		array(21, 25, 5, 'C', 'Transaction Number', 'Right most 5 digits of transaction #' ),
		array(26, 31, 6, 'C', 'Unit Number', 'right justified' ),
		array(32, 36, 5, 'C', 'Truck Stop Code', 'ST###' ),
		array(37, 51, 15, 'C', 'Truck Stop Name', 'Left Justified' ),
		array(52, 63, 12, 'C', 'Truck Stop City' ),
		array(64, 65, 2, 'C', 'Truck Stop State' ),
		array(66, 73, 8, 'C', 'Truck Stop Invoice Number' ),
		array(74, 77, 4, 'N', 'Transaction Time', 'HHMM' ),
		array(78, 83, 6, 'N', 'TOTAL AMOUNT DUE', '9999v99' ),
		array(84, 87, 4, 'N', 'FEES FOR FUEL & OIL & PRODUCTS', '99v99' ),
		array(88, 88, 1, 'C', 'Cheaper Fuel Availability Flag', '*=Cheaper Fuel Available' ),
		array(89, 89, 1, 'C', 'Service Used', 'F=Full Service       M=Mini Service
S=Self Service      B=Blended Fuel
T=Terminal Fuel     N=Not Applicable
W=Wet Hose' ),
		array(90, 94, 5, 'N', 'Number of Tractor Gallons', '999v99, Includes #2, #1 and other fuel' ),
		array(95, 99, 5, 'N', 'Tractor Fuel Price Per Gallon', '99v999, Includes #2, #1 and other fuel' ),
		array(100, 104, 5, 'N', 'Cost of Tractor Fuel, 999v99', 'Includes #2, #1 and other fuel' ),
		array(105, 109, 5, 'N', 'Number of Reefer Gallons', '999v99' ),
		array(110, 114, 5, 'N', 'Reefer Price Per Gallon', '99v999' ),
		array(115, 119, 5, 'N', 'Cost of Reefer Fuel', '999v99' ),
		array(120, 121, 2, 'N', 'Number of Quarts of Oil', '99' ),
		array(122, 125, 4, 'N', 'Total Cost of Oil', '99v99' ),
		array(126, 126, 1, 'C', 'Tractor Fuel Billing Flag', 'F=Funded          D=Direct Bill
T=Terminal' ),
		array(127, 127, 1, 'C', 'Reefer Fuel Billing Flag', 'F=Funded          D=Direct Bill
T=Terminal' ),
		array(128, 128, 1, 'C', 'Oil Billing Flag', 'F=Funded          D=Direct Bill
T=Terminal' ),
		array(129, 130, 2, 'C', 'Header Identifier', 'Constant "02"' ),
		array(131, 135, 5, 'N', 'Cash Advance Amount', '999v99' ),
		array(136, 139, 4, 'N', 'Charges for Cash Advance', '99v99' ),
		array(140, 151, 12, 'C', 'Driver\'s Name', 'Left Justified' ),
		array(152, 161, 10, 'C', 'Trip Number', 'Left Justified' ),
		array(162, 171, 10, 'C', 'Conversion Rate', '99v99999999' ),
		array(172, 177, 6, 'N', 'Hubometer Reading', '999999' ),
		array(178, 181, 4, 'N', 'Year To Date MPG', '99v99' ),
		array(182, 185, 4, 'N', 'MPG for this Fill Up', '99v99' ),

		array(186, 193, 8, 'C', 'Fuel Card ID Number', 'also known as FP Number' ),
		array(194, 194, 1, 'C', 'Billable Currency', 'U=US  C=Canadian' ),
		array(195, 204, 10, 'N', 'Comchek Card Number' ),
		array(205, 220, 16, 'C', 'Employee Number', 'Left Justified' ),
		array(221, 221, 1, 'C', 'Non-Billable Item', '* = Direct Bill Transaction
T = Terminal Fuel Transaction' ),
		array(222, 222, 1, 'C', 'Not Limited Ntwk Location Flag', '* = Not in Limited Network' ),
		array(223, 223, 1, 'C', 'Product Code', 'SEE KEY BELOW FOR TYPES' ),
		array(224, 230, 7, 'N', 'Product Amount', '99999v99' ),
		array(231, 231, 1, 'C', 'Product Code', 'SEE KEY BELOW FOR TYPES' ),
		array(232, 238, 7, 'N', 'Product Amount', '99999v99' ),
		array(239, 239, 1, 'C', 'Product Code', 'SEE KEY BELOW FOR TYPES' ),
		array(240, 246, 7, 'N', 'Product Amount', '99999v99' ),

		array(247, 251, 5, 'N', 'Alliance Select or Focus', '999v99' ),
			//"Rebate Amount"
		array(252, 252, 1, 'C', 'Alliance Location Flag', 'Y = Alliance Network Location' ),
		array(253, 253, 1, 'C', 'Cash Billing Flag', 'F=Funded          D=Direct Bill
T=Terminal,       or blank' ),
		array(254, 254, 1, 'C', 'Product 1 Billing Flag', 'F=Funded          D=Direct Bill
T=Terminal,       or blank' ),
		array(255, 255, 1, 'C', 'Product 2 Billing Flag', 'F=Funded          D=Direct Bill
T=Terminal,       or blank' ),
		array(256, 256, 1, 'C', 'Product 3 Billing Flag', 'F=Funded          D=Direct Bill
T=Terminal,       or blank' ),
		array(257, 258, 2, 'C', 'Header Indentifier', 'Constant "03"' ),
		array(259, 260, 2, 'C', 'Driver\'s License State' ),
		array(261, 280, 20, 'C', 'Driver\'s License Number', 'Left Justified' ),
		array(281, 290, 10, 'C', 'Purchase Order Number', 'Left Justified' ),
		array(291, 300, 10, 'C', 'Trailer Number', 'Left Justified' ),
		array(301, 306, 6, 'N', 'Previous Hub Reading' ),
		array(307, 307, 1, 'C', 'Cancel Flag', 'Y=Yes  N=No' ),
		array(308, 313, 6, 'C', 'Date of Original Transaction', 'MMDDYY' ),
		array(314, 323, 10, 'C', 'Service Center Chain Code' ),
		array(324, 333, 10, 'C', 'Expanded Fuel Code (Cust Id)', '-4 Digit Cust ID [Has Leading Zero]
Fuel Code/Cust ID Currently [5] Digit, Left Justified' ),
		array(334, 334, 1, 'C', 'Rebate Indicator', 'R=Rebate   N=Net' ),
		array(335, 341, 7, 'N', 'Trailer Hub Reading', '99999.9' ),
		array(342, 342, 1, 'C', 'Automated Transaction', 'Y=Yes  N=No' ),
		array(343, 343, 1, 'C', 'Bulk Fuel Flag', 'Y=Yes  N=No' ),
		array(344, 344, 1, 'C', 'Service Center Bridge Transaction', 'Y=Yes  N=No' ),
		array(345, 349, 5, 'N', 'Number 1 Fuel Gallons', '999v99' ),
		array(350, 354, 5, 'N', 'Number 1 Fuel PPG', '999v99' ),
		array(355, 359, 5, 'N', 'Number 1 Fuel Cost', '999v99' ),
		array(360, 364, 5, 'N', 'Other Fuel Gallons', '999v99' ),
		array(365, 369, 5, 'N', 'Other Fuel PPG', '999v99' ),
		array(370, 374, 5, 'N', 'Other Fuel Cost', '999v99' ),
		array(375, 375, 1, 'C', 'Focus or Select Discount', 'F=Focus  S=Select or Blank' ),
		array(376, 379, 4, 'N', 'Canadian Tax Amt (Canadian Dollars)', '99v99' ),
		array(380, 383, 4, 'N', 'Canadian Tax Amt (US Dollars)', '99v99' ),
		array(384, 384, 1, 'C', 'Canadian Tax Paid Flag', 'Y=Yes  N=No' ),
	);	

		// FROM	TO	LEN	FMT	DESCRIPTION	COMMENT
	public $product_codes = array(
		'0' => 'Additives',
		'1' => 'Tire Repair',
		'2' => 'Emergency Repair',
		'3' => 'Lubricants',
		'4' => 'Tire Purchase',
		'5' => 'Driver Expense',
		'6' => 'Truck Repair',
		'7' => 'Parts',
		'8' => 'Trailer Expense',
		'9' => 'Miscellaneous',
		'A' => 'Truck Wash',
		'B' => 'Scales',
		'C' => 'Parking Service',
		'D' => 'Hotel',
		'E' => 'Regulatory',
		'F' => 'Labor',
		'G' => 'Groceries',
		'H' => 'Shower',
		'I' => 'Trip Scan',
		'J' => 'Tolls',
		'K' => 'Aviation',
		'L' => 'Vehicle Maintenance',
		'M' => 'Aviation Maintenance',
		'N' => 'Set-up Fee',
		'O' => 'Diesel Exhaust Fluid',
		'P' => 'Wiper Fluid',
		'Q' => 'TS Trans Fee',
		'R' => 'Training',
		'S' => 'Trao;er Parking',
		'T' => 'Retail Lubricants',
		'U' => 'Solvent',
		'V' => 'Merch Surcharge',
		'W' => 'Anti-Frz / Coolant',
	);	
	
	// Use parent constructor
	
	// Allow re-use of objects - singleton function
	public static function getInstance( $database, $debug = false, $logger = null ) {
		static $instance = null;
		$myclass = get_class ();
		if( $debug ) echo "<p>Get instance of $myclass</p>";
		if (null === $instance) {
			$instance = new $myclass( $database, $debug, $logger );
		}
		return $instance;
    }
    
    public function parse( $file ) {
	    if( $this->debug ) echo "<p>".__METHOD__.": ".(isset($file) ? strlen($file)." characters" : "").".</p>";
		if( $this->logger )
			$this->logger->log_event( __METHOD__.": entry, ".(isset($file) ? strlen($file)." characters" : ""), EXT_ERROR_DEBUG);
		$lines = explode("\n", $file);
		$this->tokens = array();
		$line_number = 1;
		$num_lines = count($lines);
		
		foreach( $lines as $line ) {
			$line = trim($line);
			$length = strlen($line);
			
			if( $length > 0 ) {
				switch( substr($line, 0, 2)) {
					case '00':
						$record_type = 'header';
						$pattern = $this->header_record;
						break;
					case '01':
						$record_type = 'detail';
						$pattern = $this->detail_record;
						break;
					case '90':
						$record_type = 'totals';
						$pattern = false;
						break;
				}
	
				if( $this->debug ) echo "<p>".__METHOD__.": line = $line_number type = $record_type length = $length</p>";
				
				$token = array(
					'line' => $line_number,
					'type' => $record_type,
					'length' => $length,
					'raw' => $line
				);
				if( $pattern ) {
					$record = array();
					// FROM	TO	LEN	FMT	DESCRIPTION	COMMENT
					foreach( $pattern as $field ) {
						if( $field[1] > $length ) break;
						$rec = array( 
							'value' => substr($line, $field[0]-1, $field[2]),
							'fmt' => $field[3],
							'desc' => $field[4]
						);
						if( isset($field[5]))
							$rec['comm'] = $field[5];
						$record[] = $rec;
					}
					$token['fields'] = $record;
				}				
				$this->tokens[] = $token;
				$line_number++;
			}
		}
		if( $this->debug ) {
			echo "<pre>";
			var_dump($this->tokens);
			echo "</pre>";
		}
		if( $this->logger )
			$this->logger->log_event( __METHOD__.": exit, ".(isset($this->tokens) ? count($this->tokens)." tokens" : ""), EXT_ERROR_DEBUG);
		
    }

     private function get_fields_for_import( $token, $origin ) {
	    $trans_date = $token['fields'][3]['value'];
	    $trans_date = substr($trans_date, 2, 2).'/'.
		    substr($trans_date, 4, 2).'/'.
		    substr($trans_date, 0, 2);
	    $trans_num = $token['fields'][6]['value'];
	    $unit = trim($token['fields'][7]['value']);
	    $stop = trim($token['fields'][9]['value']);
	    $city = trim($token['fields'][10]['value']);
	    $state = trim($token['fields'][11]['value']);

	    $tractor_gal = $token['fields'][18]['value'];
	    $tractor_gal = (float) (substr($tractor_gal, 0, 3).'.'.substr($tractor_gal, 3, 2));
	    $tractor_ppg = $token['fields'][19]['value'];
	    $tractor_ppg = (float) (substr($tractor_ppg, 0, 2).'.'.substr($tractor_ppg, 2, 3));

	    $reefer_gal = $token['fields'][21]['value'];
	    $reefer_gal = (float) (substr($reefer_gal, 0, 3).'.'.substr($reefer_gal, 3, 2));
	    $reefer_ppg = $token['fields'][22]['value'];
	    $reefer_ppg = (float) (substr($reefer_ppg, 0, 2).'.'.substr($reefer_ppg, 2, 3));
	    
	    $driver_name = trim($token['fields'][32]['value']);
	    $hub = intval(trim($token['fields'][35]['value']));
	    $card_num = trim($token['fields'][40]['value']);
	    $employee_num = trim($token['fields'][41]['value']);
	    $trailer_num = trim($token['fields'][60]['value']);
	    $prev_hub = intval(trim($token['fields'][61]['value']));
	    
	   $result = array( 'IFTA_DATE' => date("Y-m-d", strtotime($trans_date)),
	    	'CD_TRANS' => $trans_num, 'CD_UNIT' => $unit, 'IFTA_TRACTOR' => 0,
	    	'CD_STOP_NAME' => $stop, 'CD_STOP_CITY' => $city, 'CD_ORIGIN' => $origin,
	    	'IFTA_JURISDICTION' => $state, 'FUEL_PURCHASED' => $tractor_gal,
	    	'CD_TRACTOR_PPG' => $tractor_ppg, 'CD_REEFER_GAL' => $reefer_gal,
	    	'CD_REEFER_PPG' => $reefer_ppg, 'CD_DRIVER' => $driver_name,
	    	'CD_EMPLOYEE_NUM' => $employee_num, 'CD_CARD_NUM' => $card_num,
	    	'CD_HUB' => $hub, 'CD_PREV_HUB' => $prev_hub, 'CD_TRAILER' => $trailer_num );
	    
	    // Look up card number
	    $check1 = $this->card_advance->database->get_one_row("
	    	SELECT t.TRACTOR_CODE
			FROM EXP_TRACTOR t, EXP_TRACTOR_CARD c
			WHERE c.CARD_SOURCE = '".$origin."' AND c.UNIT_NUMBER = '".$unit."'
			AND t.TRACTOR_CODE = c.TRACTOR_CODE
			AND t.LOG_IFTA = true
	    	LIMIT 1");
	    if( is_array($check1) && ! empty($check1["TRACTOR_CODE"])) {
		    if( $this->logger )
				$this->logger->log_event( __METHOD__.
					": entry, auto-resolve origin $origin unit# $unit to tractor_code ".
					$check1["TRACTOR_CODE"], EXT_ERROR_DEBUG);
		    $result['IFTA_TRACTOR'] = $check1["TRACTOR_CODE"];
		}
			    	
	    return $result;
    }
    
    //! Get the fields needed for a cash advance
    private function get_fields_for_advance( $token, $origin ) {
	    $result = false;
	    $cash_adv = $token['fields'][30]['value'];
	    $cash_adv = (float) (substr($cash_adv, 0, 3).'.'.substr($cash_adv, 3, 2));
	    if( $this->debug ) echo "<p>".__METHOD__.": entry, origin = $origin, cash_adv = $cash_adv</p>";
	    
	    if( $cash_adv > 0 ) {
		    $trans_date = $token['fields'][3]['value'];
		    $trans_date = substr($trans_date, 2, 2).'/'.
			    substr($trans_date, 4, 2).'/'.
			    substr($trans_date, 0, 2);
		    $trans_num = $token['fields'][6]['value'];
		    $stop = trim($token['fields'][9]['value']);
		    $city = trim($token['fields'][10]['value']);
		    $state = trim($token['fields'][11]['value']);
	
		    $cash_adv_fee = $token['fields'][31]['value'];
		    $cash_adv_fee = (float) (substr($cash_adv_fee, 0, 2).'.'.substr($cash_adv_fee, 2, 2));
		    
		    $driver_name = trim($token['fields'][32]['value']);
		    $trip_num = trim($token['fields'][33]['value']);
		    $fuel_card_id = trim($token['fields'][38]['value']);
		    $currency = $token['fields'][39]['value'] == 'C' ? 'CAD' : 'USD';
	
		    $card_num = trim($token['fields'][40]['value']);
		    
			$result = array( 'CARD_SOURCE' => $origin,
				'TRANS_DATE' => date("Y-m-d", strtotime($trans_date)),
		    	'TRANS_NUM' => $trans_num,
		    	'CARD_STOP' => $stop, 'CITY' => $city, 'STATE' => $state,
		    	'CASH_ADV' => $cash_adv, 'CASH_ADV_FEE' => $cash_adv_fee,
		    	'DRIVER_NAME' => $driver_name,
		    	'TRIP_NUM' => $trip_num, 
		    	'CURRENCY_CODE' => $currency,
		    	'FUEL_CARD_ID' => $fuel_card_id, 'CARD_NUM' => $card_num );
		    
		    // Look up card number
		    $check1 = $this->card_advance->database->get_one_row("
		    	SELECT DRIVER_CODE FROM EXP_DRIVER_CARD
		    	WHERE CARD_SOURCE = '".$origin."' AND CARD_NUM = '".$card_num."'
		    	LIMIT 1");
		    if( is_array($check1) && ! empty($check1["DRIVER_CODE"])) {
			    $result['DRIVER_CODE'] = $check1["DRIVER_CODE"];
		    } else if( strpos($driver_name, ',') !== false ) {
			    // Lookup driver code
			    list($last, $first) = explode(',', $driver_name);
			    $first = trim($first);
			    $check2 = $this->card_advance->database->get_multiple_rows("
			    	SELECT DRIVER_CODE FROM EXP_DRIVER
			    	WHERE (LAST_NAME = '".$last."' AND FIRST_NAME = '".$first."')
			    	OR (LAST_NAME = '".$last."' AND FIRST_NAME LIKE '".$first."%')
			    ");
			    if( is_array($check2) && count($check2) == 1 &&
			    	isset($check2[0]['DRIVER_CODE'])) {
			    	$result['DRIVER_CODE'] = intval($check2[0]['DRIVER_CODE']);
			    	if( $this->logger )
						$this->logger->log_event( __METHOD__.": map $card_num to driver ".
							$result['DRIVER_CODE'], EXT_ERROR_DEBUG);
			    	
			    	$this->card_advance->database->get_one_row("
				    	INSERT INTO EXP_DRIVER_CARD (CARD_SOURCE, CARD_NUM, DRIVER_CODE)
				    	VALUES ( '".$origin."', '".$card_num."', ".$result['DRIVER_CODE'].") ");
			    }
		    }
	    }
	    return $result;
    }
    
   public function dump( $origin, $back ) {
	    $result = '';
	    if( $this->debug ) echo "<p>".__METHOD__.": entry.</p>";
	    if( is_array($this->tokens) && count($this->tokens) > 0 ) {
		    $result .= '<div class="well well-sm">
			<h3>View Fuel Card Relevant Data <a class="btn btn-sm btn-default" href="'.$back.'"><span class="glyphicon glyphicon-remove"></span> Back</a></h3>
			<div class="table-responsive">
			<table class="display table table-striped table-condensed table-bordered table-hover" id="FUEL_CARDS">
			<thead><tr class="exspeedite-bg">
			<th>Line</th><th>Date<br>Trans#</th><th>Tractor<br>Trailer</th>
			<th>Address</th><th>Tractor Gal<br>Tractor PPG</th>
			<th>Reefer Gal<br>Reefer PPG</th><th>Hub<br>Prev Hub</th>
			<th>Driver<br>Emp num</th><th>Card<br>Advance</th>
			</tr>
			</thead>
			<tbody>';

		    foreach($this->tokens as $token) {
			    if( $token['type'] == 'detail' ) {
				    
				    if( $this->debug ) {
				    	echo "<pre>".__METHOD__.": token, origin\n";
				    	var_dump($token);
				    	var_dump($origin);
				    	echo "</pre>";
				    }
				    $fields = $this->get_fields_for_import( $token, $origin );
				    $fields2 = $this->get_fields_for_advance( $token, $origin );
				    if( $this->debug ) {
				    	echo "<pre>".__METHOD__.": fields, fields2\n";
				    	var_dump($fields);
				    	var_dump($fields2);
				    	echo "</pre>";
				    }

				    $result .= '<tr>
						<td class="text-right">'.$token['line'].'</td>
						<td><strong>'.date("m/d/Y", strtotime($fields['IFTA_DATE'])).'<br>'.$fields['CD_TRANS'].'</strong></td>
						<td><strong>'.$fields['CD_UNIT'].'</strong><br>'.$fields['CD_TRAILER'].'</td>
						<td>'.$fields['CD_STOP_NAME'].'<br>'.$fields['CD_STOP_CITY'].' <strong>'.$fields['IFTA_JURISDICTION'].'</strong></td>
						<td class="text-right"><strong>'.number_format($fields['FUEL_PURCHASED'], 2).'</strong><br>'.number_format($fields['CD_TRACTOR_PPG'], 3).'</td>
						<td class="text-right">'.number_format($fields['CD_REEFER_GAL'], 2).'<br>'.number_format($fields['CD_TRACTOR_PPG'], 3).'</td>
						<td class="text-right">'.$fields['CD_HUB'].'<br>'.$fields['CD_PREV_HUB'].'</td>
						<td>'.$fields['CD_DRIVER'].'<br>'.$fields['CD_EMPLOYEE_NUM'].'</td>
						<td>'.$fields['CD_CARD_NUM'].
							(is_array($fields2) ? '<br>'.$fields2['CASH_ADV'] : '').'</td>
					</tr>';
			    }
		    }
	   		$result .= '</tbody>
			</table>
			</div>
			</div>';

	    }
	    return $result;
	}

  public function import( $origin ) {
	    $result = true;
	    //! SCR# 554 - Add more visibility to import process
	    $this->count_ifta_import			= 0;
	    $this->count_ifta_import_error		= 0;
		$this->count_ifta_duplicate			= 0;
		$this->count_advance_import			= 0;
		$this->count_advance_import_error	= 0;
		$this->count_advance_duplicate		= 0;

	    if( $this->debug ) echo "<p>".__METHOD__.": entry, origin $origin ".
		    (is_array($this->tokens) ? count($this->tokens)." tokens" : "no tokens")."</p>";
		if( $this->logger )
			$this->logger->log_event( __METHOD__.": entry, origin $origin ".
		    (is_array($this->tokens) ? count($this->tokens)." tokens" : "no tokens"), EXT_ERROR_DEBUG);
	    if( is_array($this->tokens) && count($this->tokens) > 0 ) {
 		    foreach($this->tokens as $token) {
			    if( $this->logger )
					$this->logger->log_event( __METHOD__.": line=".(isset($token['line']) ? $token['line'] : 'unknown')." type=".(isset($token['type']) ? $token['type'] : 'unknown'), EXT_ERROR_DEBUG);
				$result = $result2 = true;	// initialize to true, no problems
			    if( $token['type'] == 'detail' ) {
				    $fields = $this->get_fields_for_import( $token, $origin );
				    if( is_array($fields) ) {
						// Check for duplicates
						$check = $this->ifta_log->fetch_rows( "IFTA_DATE = '".$fields['IFTA_DATE']."'
					    	AND CD_TRANS = '".$fields['CD_TRANS']."'", "IFTA_LOG_CODE");
					    
					    if( ! is_array($check) || count($check) == 0 ) {

						    // Add the log entry
						    if( $this->logger )
								$this->logger->log_event( __METHOD__.": add IFTA ".print_r($fields, true), EXT_ERROR_DEBUG);
						    $result = $this->ifta_log->add( $fields );
						    if( $result )
						    	$this->count_ifta_import++;
						    else
						    	$this->count_ifta_import_error++;
						    	
						    if( $this->logger && ! $result )
								$this->logger->log_event( __METHOD__.": Add IFTA_LOG error: ".$this->ifta_log->error(), EXT_ERROR_ERROR);
						} else {
						    $this->count_ifta_duplicate++;
						    if( $this->logger )
								$this->logger->log_event( __METHOD__.": DUPLICATE IFTA ".print_r($fields, true), EXT_ERROR_ERROR);
						}
					} else {
					    if( $this->logger )
							$this->logger->log_event( __METHOD__.": No fields found!", EXT_ERROR_ERROR);
					}

				    //!  Import cash advances
					if( $this->debug ) echo "<p>".__METHOD__.": Import cash advances.</p>";
				    if( $this->import_cash_adv ) {
					    $fields = $this->get_fields_for_advance( $token, $origin );
					    if( $this->debug ) {
					    	echo "<pre>after get_fields_for_advance\n";
					    	var_dump($fields);
					    	echo "</pre>";
					    }
					    if( is_array($fields) ) {
							// Check for duplicates
							$check = $this->card_advance->fetch_rows( "TRANS_DATE = '".$fields['TRANS_DATE']."'
						    	AND TRANS_NUM = '".$fields['TRANS_NUM']."'", "ADVANCE_CODE");
						    
						    if( ! is_array($check) || count($check) == 0 ) {
							    if( $this->logger )
									$this->logger->log_event( __METHOD__.": add advance ".print_r($fields, true), EXT_ERROR_DEBUG);
	
							    $result2 = $this->card_advance->add( $fields );
							    if( $result )
							    	$this->count_advance_import++;
							    else
							    	$this->count_advance_import_error++;
						    	
							    if( $this->logger && ! $result2 )
									$this->logger->log_event( __METHOD__.": Add CARD_ADVANCE error: ".$this->card_advance->error(), EXT_ERROR_ERROR);
							} else {
								$this->count_advance_duplicate++;
							    if( $this->logger )
									$this->logger->log_event( __METHOD__.": DUPLICATE advance ".print_r($fields, true), EXT_ERROR_ERROR);
									
							}
						}
					}
				}
				// If a DB operation failed...
				if( ! $result || ! $result2 ) {
					$result = false;
					break;
				}
			}
		}
	    if( $this->logger )
			$this->logger->log_event( __METHOD__.": exit, return ".($result ? "true" : "false").
				"\n\tIFTA import/error/duplicate = ".
				$this->count_ifta_import."/".
				$this->count_ifta_import_error."/".
				$this->count_ifta_duplicate." ADVANCE import/error/duplicate = ".
				$this->count_advance_import."/".
				$this->count_advance_import_error."/".
				$this->count_advance_duplicate, EXT_ERROR_ERROR);
		$this->log_import( $origin, $result );

		return $result;
	}

}

