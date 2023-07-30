<?php

// $Id: sts_card_bvd_class.php 5110 2023-05-22 17:36:50Z duncan $
// - Fuel card BVD class
// API Info: https://bvd-group.stoplight.io/docs/bvd-web-services/a4a68awrl6fun-bvd-web-services

// no direct access
defined('_STS_INCLUDE') or die('Restricted access');
require_once( "sts_card_transport_class.php" );
require_once( "sts_card_class.php" );
require_once( "sts_driver_class.php" );

define( '_STS_BVD_LIVE', 'http://bvdapiservices.bvdpetroleum.com' );
define( '_STS_BVD_MOCK', 'https://stoplight.io/mocks/bvd-group/bvd-web-services/142854584' );

define( '_STS_BVD_GETALLCARDS', '/api/cardp1.php' );
define( '_STS_BVD_GETALLTRANS', '/api/getAllTransactions.php' );


//! This class handles the transport to get data from BVD
class sts_card_bvd_transport extends sts_card_transport {
	private $servers = [
		'live' => _STS_BVD_LIVE,
		'mock' => _STS_BVD_MOCK
	];

	// Constructor does not need the table name
	public function __construct( $database, $debug = false ) {
		

		parent::__construct( $database, $debug);
		
		if( $this->debug ) echo "<p>Create sts_card_bvd</p>";
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

	//! Connect, login, chdir and get list of files in directory
	public function bvd_count( $client, $start, $end ) {
		
		if( $this->debug ) echo "<p>".__METHOD__.": <strong>Connect to client $client, $start, $end</strong></p>";
		$this->log_event( __METHOD__.": Connect to client $client, $start, $end", EXT_ERROR_DEBUG);
		$result = false;
		
		$info = $this->bvd_fetch( $client, $start, $end );

		if( is_array($info) )
			$result = count($info);
				
		if( $this->debug ) echo "<p><strong>".__METHOD__.": return ".(is_array($result) ? 'array('.count($result).')' : ($result ? 'true' : 'false') )."</strong></p>";
		return $result;
	}

	//! Fetch all transactions between two dates
	public function bvd_fetch( $client, $start, $end ) {
		
		if( $this->debug ) echo "<p>".__METHOD__.": Connect to client $client</p>";
		$this->log_event( __METHOD__.": <strong>Connect to client $client</strong>", EXT_ERROR_DEBUG);
		$result = false;

		if( ! (is_array($this->ftp_client) &&
			isset($this->ftp_client['FTP_REMOTE_ID']) &&
			$this->ftp_client['FTP_REMOTE_ID'] == $client ) ) {
			$this->client_open( $client );
		}
		
		if( $this->debug ) {
			echo "<pre>".__METHOD__.": ftp_client\n";
			var_dump($this->ftp_client, $this->transport( $client ),
				isset($this->servers[$this->ftp_client['BVD_SERVER']]) );
			echo "</pre>";
		}
		if( $this->transport( $client ) == 'BVD' ) {	//! Transport needs to be BVD
			if( is_array($this->ftp_client) &&
				isset($this->servers[$this->ftp_client['BVD_SERVER']]) ) {
				$this->url = $this->servers[$this->ftp_client['BVD_SERVER']];
				if( $this->debug ) echo "<p><strong>".__METHOD__.": Connect to ".$this->ftp_client['BVD_SERVER']." (".$this->url.")</strong></p>";
				
				$headers = [
					'Authorization: '.$this->ftp_client['BVD_API_KEY'],
					'Content-Type: application/json'
				];
			    $ch = curl_init();
			    
				curl_setopt( $ch, CURLOPT_URL, $this->url._STS_BVD_GETALLTRANS."?starttime=$start&endtime=$end&limit=1000" );
				
				curl_setopt($ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);				
			    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
			    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			    $data = curl_exec($ch);
				$http_response_header = curl_getinfo($ch, CURLINFO_HTTP_CODE);

				curl_close ($ch);
				
				if( ! empty($data)) {
					$result = json_decode($data, true);
				
					if( $this->debug ) {
						echo "<pre>".__METHOD__.": result\n";
						var_dump($result, $http_response_header);
						echo "</pre>";
					}
				}
			}
		}

		if( $this->debug ) echo "<p><strong>".__METHOD__.": return ".(is_array($result) ? 'array('.count($result).')' : ($result ? 'true' : 'false') )."</strong></p>";
		return $result;

	}
	
}

//! This class handles the FORMAT of BVD-JASON, the JASON formatted data from BVD
class sts_card_bvd extends sts_card {
	const BVD_LITRES_GALLONS = 3.785411784;
	const BVD_LITRE = 'L';
	const BVD_GALLON = 'G';
	const BVD_CANADA = 'Canada';
	const BVD_ORIGIN = 'BVD';
	const BVD_CURR_CAD = 'CAD';
	const BVD_CURR_USD = 'USD';
	
	// Constructor does not need the table name
	public function __construct( $database, $debug = false ) {
		
		$this->debug = $debug;
		$this->database = $database;

		parent::__construct( $database, $debug);
		
		if( $this->debug ) echo "<p>Create sts_card_bvd</p>";
	}
	
	// Allow re-use of objects - singleton function
	public static function getInstance( $database, $debug = false, $logger = null ) {
		static $instance = null;
		$myclass = get_class ();
		if( $debug ) echo "<p>Get instance of $myclass</p>";
		if (null === $instance) {
			$instance = new $myclass( $database, $debug );
		}
		return $instance;
    }

	private function get_fields_for_import( $row, $origin ) {
	    $trans_date = $row['date_time'];
	    $trans_num = $row['transactionId'];
	    $unit = $row["truck_number"];
	    $stop = $row["Site Name"];
	    $city = $row["Site City"];
	    $state = $row["Prov/St Abb"];
	    
    	$driver = sts_driver::getInstance($this->database, $this->debug);
		$driver_card = sts_driver_card::getInstance($this->database, $this->debug);

	    if( isset($row["Uom"])) {		// Unit of measure
		    $uom =  $row["Uom"];
	    } else {
		    if( is_array($row["location"]) && isset($row["location"]["country"]) &&
	    		$row["location"]["country"] == self::BVD_CANADA )
	    		$uom = self::BVD_LITRE;
	    	else
	    		$uom = self::BVD_GALLON;
	    }
	    	    
	    if( isset($row["First Fuel transaction"]) && is_array($row["First Fuel transaction"]) ) {
		    $tractor = $row["First Fuel transaction"];
	    	if( isset($tractor["quantity"]) && $tractor["quantity"] > 0 ) {
			    $tractor_gal = $uom == self::BVD_LITRE ?
			    	$tractor["quantity"] / self::BVD_LITRES_GALLONS :
			    	$tractor["quantity"];
			} else {
				$tractor_gal = 0;
			}
		    
	    	if( isset($tractor["pricePerUnit"]) ) {
			    $tractor_ppg = $uom == self::BVD_LITRE ?
			    	$tractor["pricePerUnit"] * self::BVD_LITRES_GALLONS :
		    		$tractor["pricePerUnit"];
			} else {
				$tractor_ppg = 0;
			}
	    }

	    if( isset($row["Trailer fuel Transaction"]) && is_array($row["Trailer fuel Transaction"]) ) {
		    $reefer = $row["Trailer fuel Transaction"];
	    	if( isset($reefer["quantity"]) && $reefer["quantity"] > 0 ) {
			    $reefer_gal = $uom == self::BVD_LITRE ?
			    	$reefer["quantity"] / self::BVD_LITRES_GALLONS :
			    	$reefer["quantity"];
			} else {
				$reefer_gal = 0;
			}
		    
	    	if( isset($reefer["pricePerUnit"]) ) {
			    $reefer_ppg = $uom == self::BVD_LITRE ?
			    	$reefer["pricePerUnit"] * self::BVD_LITRES_GALLONS :
		    		$reefer["pricePerUnit"];
			} else {
				$reefer_ppg = 0;
			}
	    }
	    
	    $driver_name = $row["driver_name"];
	    $card_num = $row["cardNumber"];
	    
		$result = array( 'IFTA_DATE' => date("Y-m-d", strtotime($trans_date)),
	    	'CD_TRANS' => $trans_num, 'IFTA_TRACTOR' => 0,
	    	'CD_STOP_NAME' => $stop, 'CD_STOP_CITY' => $city, 'CD_ORIGIN' => $origin,
	    	'IFTA_JURISDICTION' => $state, 'FUEL_PURCHASED' => $tractor_gal,
	    	'CD_TRACTOR_PPG' => $tractor_ppg, 'CD_REEFER_GAL' => $reefer_gal,
	    	'CD_REEFER_PPG' => $reefer_ppg, 'CD_DRIVER' => $driver_name,
	    	'CD_CARD_NUM' => $card_num );
	    
	    if( ! empty($unit) ) {
		    $result['CD_UNIT'] = $unit;

		    //! Look up unit number
		    $check1 = $this->database->get_one_row("
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
	    }
	    
	    //! Lookup driver card ($card_num, $origin)
	    if( ! empty($card_num) ) {
			$driver_code = $driver_card->lookup( $card_num, $origin );
			
			if( $driver_code > 0 ) {
				$result['DRIVER_CODE'] = $driver_code;
			} else {
			    //! Lookup driver name
			    if( ! empty($driver_name) ) {
				    $check1 = $this->database->get_one_row("SELECT LOOKUP_DRIVER('".$driver_name."') AS DRIVER_CODE");
				    if( is_array($check1) && ! empty($check['DRIVER_CODE']) ) {
					    $driver_code = $check['DRIVER_CODE'];
				    } else {	//! Insert driver
					    $name = explode(' ', $driver_name);
					    $fields = [
						    'DRIVER_TYPE' => 'staff',
						    'PAY_TYPE' => 'company',
						    'ISACTIVE' => 'Active',
						    'DRIVER_NOTES' => 'Added during BVD import',
						    'FIRST_NAME' => reset($name),
						    'LAST_NAME' => end($name)
					    ];
					    
					    $new_driver_code = $driver->add( $fields );
					    if( $new_driver_code )
					    	$driver_code = $new_driver_code;
				    }
			    }

				//! Insert driver card
				$check = $driver_card->add_driver_card( $driver_code, $card_num, $origin );
				$result['DRIVER_CODE'] = $driver_code;
			}
	    }
	    
	    
	    return $result;
    }

    //! Get the fields needed for a cash advance
    private function get_fields_for_advance( $row, $origin ) {
	    $result = false;
	    $cash_adv = $row['cash'];
	    if( $this->debug ) echo "<p>".__METHOD__.": entry, origin = $origin, cash_adv = $cash_adv</p>";
	    
	    if( $cash_adv > 0 ) {
		    $trans_date = $row['date_time'];
		    $trans_num = $row['transactionId'];
		    $stop = $row["Site Name"];
		    $city = $row["Site City"];
		    $state = $row["Prov/St Abb"];
	
		    $cash_adv_fee = $row["Fee"];
		    

		    $driver_name = $row["driver_name"];
		    $card_num = $row["cardNumber"];
		    
		//    $trip_num = trim($token['fields'][33]['value']);

		    if( is_array($row["location"]) && isset($row["location"]["country"]) &&
	    		$row["location"]["country"] == self::BVD_CANADA )
	    		$currency = self::BVD_CURR_CAD;
	    	else
	    		$currency = self::BVD_CURR_USD;
	
			$result = array( 'CARD_SOURCE' => $origin,
				'TRANS_DATE' => date("Y-m-d", strtotime($trans_date)),
		    	'TRANS_NUM' => $trans_num,
		    	'CARD_STOP' => $stop, 'CITY' => $city, 'STATE' => $state,
		    	'CASH_ADV' => $cash_adv, 'CASH_ADV_FEE' => $cash_adv_fee,
		    	'DRIVER_NAME' => $driver_name,
		    	'CURRENCY_CODE' => $currency,
		    	'CARD_NUM' => $card_num );
		    
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
    
	public function import( $info ) {
	    $result = true;
	    
	    //! SCR# 554 - Add more visibility to import process
	    $this->count_ifta_import			= 0;
	    $this->count_ifta_import_error		= 0;
		$this->count_ifta_duplicate			= 0;
		$this->count_advance_import			= 0;
		$this->count_advance_import_error	= 0;
		$this->count_advance_duplicate		= 0;

	    if( $this->debug ) echo "<p>".__METHOD__.": entry</p>";
	    
	    if( is_array($info) && count($info) > 0 ) {
		    foreach( $info as $row ) {
			    	echo "<pre>".__METHOD__." row:\n";
			    	var_dump($row);
			    	echo "</pre>";
			    
			    $fields = $this->get_fields_for_import( $row, self::BVD_ORIGIN );

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
				    $fields = $this->get_fields_for_advance( $row, self::BVD_ORIGIN );
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
	//	$this->log_import( $origin, $result );

		return $result;
	}

}

?>