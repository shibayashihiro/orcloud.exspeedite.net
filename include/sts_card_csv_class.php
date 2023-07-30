<?php

// $Id: sts_card_csv_class.php 4350 2021-03-02 19:14:52Z duncan $
// sts_card_csv class - everything to do with decoding and importing CSV based fuel card data

// no direct access
defined('_STS_INCLUDE') or die('Restricted access');
require_once( "sts_card_class.php" );

class sts_card_csv extends sts_card {
	private $parsed_csv;
	private $format = 'BVD';
	
	//! Use this for mapping CSV files
	private $sts_import = array(	//! $sts_import
		'BVD' => array(
			'ifgtzero' => array('Tractor QTY', 'Reefer QTY'),
			'fields' => array(
				'IFTA_DATE' => array( 'label' => 'Date', 'format' => 'date', 'required' => true ),
				'CD_TRANS' => array( 'label' => 'Auth Code', 'format' => 'text', 'required' => true ),
				'CD_UNIT' => array( 'label' => 'Unit #', 'format' => 'text', 'required' => true ),
				
				//! To map units
				'IFTA_TRACTOR' => array( 'format' => 'unit','src' => 'Unit #' ),
				
				'CD_STOP_NAME' => array( 'label' => 'Site #', 'format' => 'text' ),
				'CD_ORIGIN' => array( 'format' => 'origin' ),
				'CD_STOP_CITY' => array( 'label' => 'Site City', 'format' => 'text' ),
				'IFTA_JURISDICTION' => array( 'label' => 'Prov/ST', 'format' => 'state' ),
				
				'FUEL_PURCHASED' => array( 'label' => 'Tractor QTY', 'format' => 'number', 'ifgtzero' => 'Tractor QTY', 'default' => 0  ),
				'CD_TRACTOR_PPG' => array( 'label' => 'Retail Price', 'format' => 'number', 'ifgtzero' => 'Tractor QTY', 'default' => 0  ),
				'CD_REEFER_GAL' => array( 'label' => 'Reefer QTY', 'format' => 'number', 'ifgtzero' => 'Reefer QTY', 'default' => 0 ),
				'CD_REEFER_PPG' => array( 'label' => 'Retail Price', 'format' => 'number', 'ifgtzero' => 'Reefer QTY', 'default' => 0  ),
				
				'CD_DRIVER' => array( 'label' => 'Driver Name', 'format' => 'text' ),
				'CD_CARD_NUM' => array( 'label' => 'Card #', 'format' => 'text' ),
			)
		)
	);

	private $sts_cash_adv = array(	//! $sts_cash_adv
		'BVD' => array(
			'ifgtzero' => array('Cash'),
			'fields' => array(
				'CARD_SOURCE' => array( 'format' => 'origin' ), // $origin
				'TRANS_DATE' => array( 'label' => 'Date', 'format' => 'date', 'required' => true ),
				'TRANS_NUM' => array( 'label' => 'Auth Code', 'format' => 'text', 'required' => true ),
				'CARD_STOP' => array( 'label' => 'Site #', 'format' => 'text' ),
				'CITY' => array( 'label' => 'Site City', 'format' => 'text' ),
				'STATE' => array( 'label' => 'Prov/ST', 'format' => 'state' ), // long form
			
				'CASH_ADV' => array( 'label' => 'Cash', 'format' => 'number' ),
				'DRIVER_NAME' => array( 'label' => 'Driver Name', 'format' => 'text' ),
				'CURRENCY_CODE' => array( 'label' => 'Currency', 'format' => 'currency' ), // US or CN
				'CARD_NUM' => array( 'label' => 'Card #', 'format' => 'text', 'required' => true ),
				
				//! To map drivers
				'DRIVER_CODE' => array( 'format' => 'driver','src' => 'Card #', 'name' => 'Driver Name' ),
				
			)
		)
	);

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
    
	public function parse( $file, $format = 'BVD' ) {
		$this->format = $format;
		$this->parsed_csv = false;
		$this->line = 0;
		
		$lines = preg_split("/\\r\\n|\\r|\\n/", $file);
		
		if( $this->debug ) echo "<p>".__METHOD__.": file contains ".count($lines)." lines</p>";
		if( $this->logger )
			$this->logger->log_event( __METHOD__.": file contains ".count($lines)." lines", EXT_ERROR_DEBUG);
		
		if (count($lines) < 2) {
			if( $this->debug ) echo "<p>".__METHOD__.":Error: needs at least two lines</p>";
		} else {

			set_time_limit(0);
			$header = str_getcsv($lines[$this->line++]);
			
			if( $header !== FALSE ) {
				$num_columns = count($header);
				if( $this->debug ) echo "<p>".__METHOD__.": header contains $num_columns columns</p>";
				$this->parsed_csv = array();
				while($this->line < count($lines)) {
					//if( $this->debug ) echo "<p>".__METHOD__.": line $this->line of ".count($lines)."</p>";
					$data = str_getcsv($lines[$this->line++]);
					if(count($data) <> $num_columns) {
						if( $this->debug ) echo "<p>".__METHOD__.": $this->line - ".count($data)." columns <> ".$num_columns." in header </p>"; 
						break;
					}
					$row = array();
					for( $c=0; $c<$num_columns; $c++) {
						$row[$header[$c]] = $data[$c];
					}
					$this->parsed_csv[] = $row;
					unset($row);
				}
			}
		}
		if( false && $this->debug ) {
			echo "<p>".__METHOD__.": parsed CSV</p>";
			echo "<pre>";
			var_dump($this->parsed_csv);
			echo "</pre>";
		}
		if( $this->logger )
			$this->logger->log_event( __METHOD__.": exit, ".(isset($this->parsed_csv) ? count($this->parsed_csv)." CSV rows" : ""), EXT_ERROR_DEBUG);
	}

	//! Lookup tractor based on unit number, make sure the field exists!!!
	private function lookup_unit( $row, $src, $origin ) {
	    $result = 0;
	    if( isset($row) && ! empty($row[$src]) ) {
		    $unit = $row[$src];
		
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

			    $result = $check1["TRACTOR_CODE"];
			}
			    	
	    }
		return $result;
	}

	//! Lookup driver based on card number or name, make sure the field exists!!!
	private function lookup_driver( $row, $src, $name, $origin ) {
	    $result = 0;
	    if( isset($row) && ! empty($row[$src]) && ! empty($row[$name]) ) {
		    $card_num = $row[$src];
		    $driver_name = $row[$name];
		    // Look up card number
		    $check1 = $this->card_advance->database->get_one_row("
		    	SELECT DRIVER_CODE FROM EXP_DRIVER_CARD
		    	WHERE CARD_SOURCE = '".$origin."' AND CARD_NUM = '".$card_num."'
		    	LIMIT 1");
		    if( is_array($check1) && ! empty($check1["DRIVER_CODE"])) {
			    $result = $check1["DRIVER_CODE"];
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
			    	$result = intval($check2[0]['DRIVER_CODE']);
			    	if( $this->logger )
						$this->logger->log_event( __METHOD__.": map $card_num to driver ".
							$result, EXT_ERROR_DEBUG);
			    	
			    	$this->card_advance->database->get_one_row("
				    	INSERT INTO EXP_DRIVER_CARD (CARD_SOURCE, CARD_NUM, DRIVER_CODE)
				    	VALUES ( '".$origin."', '".$card_num."', ".$result.") ");
			    }
		    }
	    }
		return $result;
	}

    private function get_fields( $row, $template, $origin ) {
		if( $this->debug ) echo "<p>".__METHOD__.": entry, origin = $origin</p>";
		$result = false;
		$match = false;
		foreach( $template['ifgtzero'] as $label ) {
			if( $row[$label] > 0 )
				$match = true;
		}
	     
		if( $match ) {
			$result = array();
			foreach( $template['fields'] as $column => $field ) {
 				if( isset($field['value']) ) {
					$result[$column] = $field['value'];
				} else if( isset($field['format']) && $field['format'] == 'origin' ) {
					$result[$column] = $origin;
				} else if( isset($field['format']) && $field['format'] == 'unit' ) {
				    // Lookup tractor based on unit number
				    $result[$column] = $this->lookup_unit( $row, $field['src'], $origin );
				} else if( isset($field['format']) && $field['format'] == 'driver' ) {
					// Lookup driver based on card number or name
					$result[$column] = $this->lookup_driver( $row, $field['src'], $field['name'], $origin );					
				} else if( isset($field['label']) && isset($row[$field['label']]) ) {
					if (! isset($field['ifgtzero']) || $row[$field['ifgtzero']] > 0) {
						if( isset($field['format']) && $field['format'] == 'date' ) {
							$result[$column] = date("Y-m-d", strtotime($row[$field['label']]));
						} else if( isset($field['format']) && $field['format'] == 'state' ) {
							$result[$column] = $this->lookup_state($row[$field['label']]);
						} else if( isset($field['format']) && $field['format'] == 'currency' ) {
							$result[$column] = $this->lookup_currency($row[$field['label']]);
						} else {
							$result[$column] = $row[$field['label']];
						}
					} else if( isset($field['default']) ) {
						$result[$column] = $field['default'];
					}
				}
			}
		}

		if( $this->debug ) {
			echo "<pre>".__METHOD__."\n";
			var_dump($result);
			echo "</pre>";
		}
	     
	    return $result;
	 }

	//! Public interface for displaying fuel card data
    public function dump( $origin, $back ) {
	    $result = '';
	    if( $this->debug ) echo "<p>".__METHOD__.": entry.</p>";
	    if( is_array($this->parsed_csv) && count($this->parsed_csv) > 0 ) {
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

		    foreach($this->parsed_csv as $line => $row) {
				if( $this->debug ) echo "<p>".__METHOD__.": line ".($line+1)."</p>";
			    $fields = $this->get_fields( $row, $this->sts_import[$this->format], $origin );
			    $fields2 = $this->get_fields( $row, $this->sts_cash_adv[$this->format], $origin );
			    
			    if( ! is_array($fields) ) continue;

			    $result .= '<tr>
					<td class="text-right">'.($line+2).'</td>
					<td><strong>'.date("m/d/Y", strtotime($fields['IFTA_DATE'])).'<br>'.$fields['CD_TRANS'].'</strong></td>
					<td><strong>'.$fields['CD_UNIT'].'</strong><br>'.(isset($fields['CD_TRAILER']) ? $fields['CD_TRAILER'] : '').'</td>
					<td>'.$fields['CD_STOP_NAME'].'<br>'.$fields['CD_STOP_CITY'].' <strong>'.$fields['IFTA_JURISDICTION'].'</strong></td>
					<td class="text-right"><strong>'.number_format($fields['FUEL_PURCHASED'], 2).'</strong><br>'.number_format($fields['CD_TRACTOR_PPG'], 3).'</td>
					<td class="text-right">'.(isset($fields['CD_REEFER_GAL']) ?number_format($fields['CD_REEFER_GAL'], 2) : '').'<br>'.(isset($fields['CD_REEFER_PPG']) ?number_format($fields['CD_REEFER_PPG'], 3) : '').'</td>
					<td class="text-right">'.(isset($fields['CD_HUB']) ? $fields['CD_HUB'] : '').'<br>'.(isset($fields['CD_PREV_HUB']) ? $fields['CD_PREV_HUB'] : '').'</td>
					<td>'.$fields['CD_DRIVER'].'<br>'.(isset($fields['CD_EMPLOYEE_NUM']) ? $fields['CD_EMPLOYEE_NUM'] : '').'</td>
					<td>'.$fields['CD_CARD_NUM'].'<br>'.($fields2 & isset($fields2['CASH_ADV']) ? $fields2['CASH_ADV'] : '').'</td>
				</tr>';
		    }
	    }
   		$result .= '</tbody>
		</table>
		</div>
		</div>';

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

	    if( $this->debug ) echo "<p>".__METHOD__.": entry, origin $origin</p>";
		if( $this->logger )
			$this->logger->log_event( __METHOD__.": entry, origin $origin", EXT_ERROR_DEBUG);
	    if( is_array($this->parsed_csv) && count($this->parsed_csv) > 0 ) {
 		    foreach($this->parsed_csv as $line => $row) {
				$result = $result2 = true;	// initialize to true, no problems
				$fields = $this->get_fields( $row, $this->sts_import[$this->format], $origin );
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
					$fields = $this->get_fields( $row, $this->sts_cash_adv[$this->format], $origin );
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

?>	