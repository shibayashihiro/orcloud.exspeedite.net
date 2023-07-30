<?php

// $Id: sts_ifta_rate_class.php 5030 2023-04-12 20:31:34Z duncan $
// IFTA rates

// no direct access
defined('_STS_INCLUDE') or die('Restricted access');

class sts_ifta_rate extends sts_table {
	//! This is the path to the IFTA server, it may change from time to time.
	private $ifta_server = 'https://www.iftach.org/taxmatrix/charts/';
	private $num_years = 2;	// How far back to look for rates
	private $setting_table;
	private $ifta_log;
	private $ifta_diag_level;	// Text
	private $diag_level;		// numeric version
	private $message = "";
	private $available_rates;
	private $installed_quarters;
	private $footnote;
	private $available = array(
		1 => 'March 2', 
		2 => 'June 2',
		3 => 'September 2',
		4 => 'December 2'
	);

	// Constructor does not need the table name
	public function __construct( $database, $debug = false ) {
		global $sts_error_level_label, $sts_crm_dir;

		$this->debug = $debug;
		$this->primary_key = "IFTA_RATE_CODE";
		if( ! function_exists('simplexml_load_file') ) {
			echo "<h2>Function simplexml_load_file() is undefined.</h2>
			<p>It appears your PHP installation does not have the SimpleXML extension enabled. This is needed for downloading IFTA rates.</p>
			<p>You can confirm this via the installer. Run the installer and click on <strong>Check PHP</strong> (search for SimpleXML, also check for libxml).</p>
			<p>If SimpleXML or libxml is not enabled, you have to take steps to enable it or re-install PHP.</p>";
			die;
		}
		$this->database = $database;
		$this->setting_table = sts_setting::getInstance($database, $debug);
		$this->ifta_log =				$this->setting_table->get( 'api', 'IFTA_LOG_FILE' );
		if( isset($this->ifta_log) && $this->ifta_log <> '' && 
			$this->ifta_log[0] <> '/' && $this->ifta_log[0] <> '\\' 
			&& $this->ifta_log[1] <> ':' )
			$this->ifta_log = $sts_crm_dir.$this->ifta_log;

		$this->ifta_diag_level =		$this->setting_table->get( 'api', 'IFTA_DIAG_LEVEL' );
		$this->diag_level =  array_search(strtolower($this->ifta_diag_level), $sts_error_level_label);
		if( $this->diag_level === false ) $this->diag_level = EXT_ERROR_ALL;

		$this->footnote = sts_ifta_footnote::getInstance( $database, $debug );
		$myclass = get_class ();
		if( $debug ) echo "<p>Create $myclass</p>";
		parent::__construct( $database, IFTA_RATE_TABLE, $debug);
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

	public function log_event( $message, $level = EXT_ERROR_ERROR ) {
		//if( $this->debug ) echo "<p>log_event: $this->ifta_log, $message, level=$level</p>";
		$this->message = $message;
		if( $this->diag_level >= $level ) {
			if( (file_exists($this->ifta_log) && is_writable($this->ifta_log)) ||
				is_writable(dirname($this->ifta_log)) ) 
				file_put_contents($this->ifta_log, date('m/d/Y h:i:s A')." pid=".getmypid().
					" msg=".$message."\n\n", FILE_APPEND);
		}
	}
	
	public function getMessage() {
		return $this->message;
	}
	
	//! file_exists() does not work with IFTA, this is an alternative
	private function check_exists( $file ) {

		$file_headers = @get_headers($file);
			//echo "<pre>get_headers\n";
			//var_dump($file, $file_headers);
			//echo "</pre>";
		
		//$this->log_event( __METHOD__.": file = $file, headers = ".$file_headers[0], EXT_ERROR_DEBUG);
		if($file_headers[0] == 'HTTP/1.1 404 Not Found') {
		    $exists = false;
		}
		else {
		    $exists = true;
		}
		return $exists;
	}

	//! Get available rates from IFTA
	// This may break in the future if they change their site.
	private function get_available_rates() {
		
		if( ! isset($this->available_rates) ) {
			if( ! isset($_SESSION["AVAILABLE_IFTA_RATES"]) ) {
				// Turn off output buffering
				ini_set('output_buffering', 'off');
				// Implicitly flush the buffer(s)
				ini_set('implicit_flush', true);
				ob_implicit_flush(true);
			
				if (ob_get_level() == 0) ob_start();

				echo '<div id="getrates"><h3 class="text-center"><img src="images/loading.gif" alt="loading" width="125" height="125" /><br>Checking For Available IFTA Rates</h3></div>';
				ob_flush(); flush();
				
				$this->get_installed_quarters(); // Find out what we already have
				$current_year = date('Y');
				$current_quarter = ceil(date("m")/3);
				for( $y = $current_year; $y >= $current_year-$this->num_years; $y-- ) {
					for( $q = 4; $q >=1; $q-- ) {
						$rate = $q.'Q'.$y;
						$path = $this->ifta_server.$rate.'.xml';
						if( $this->debug ) echo "<p>".__METHOD__.":  y = $y q = $q rate = $rate file = ".$path." exists = ".(file_exists($path) ? 'true' : 'false')." / ".($this->check_exists($path) ? 'true' : 'false')."</p>";
						if( $this->is_installed( $rate ) ||
							$this->check_exists($this->ifta_server.$rate.'.xml'))
							$this->available_rates[] = $rate;
					}
				}
				$_SESSION["AVAILABLE_IFTA_RATES"] = implode(',', $this->available_rates);
				update_message( 'getrates', '' );
			} else {
				$this->available_rates = explode(',', $_SESSION["AVAILABLE_IFTA_RATES"]);
			}
		}

		if( $this->debug ) echo "<p>".__METHOD__.": ".count($this->available_rates)." available rates</p>";
		//$this->log_event( __METHOD__.": ".count($this->available_rates)." available rates", EXT_ERROR_DEBUG);
		return $this->available_rates;
	}

	//! Is this rate available to download?
	public function is_available( $rate ) {
		$this->get_available_rates();
		return is_array($this->available_rates) && in_array($rate, $this->available_rates);
	}	
	
	//! Get list of quarters we already have downloaded
	private function get_installed_quarters() {
		if( ! isset($this->installed_quarters) ) {
			$check = $this->fetch_rows("", "distinct IFTA_QUARTER",
				"CONCAT(SUBSTRING(IFTA_QUARTER,3,4),SUBSTRING(IFTA_QUARTER,1,1)) DESC");
			if( is_array($check) && count($check) > 0 ) {
				$this->installed_quarters = array();
				foreach($check as $row) {
					if( isset($row["IFTA_QUARTER"]) )
						$this->installed_quarters[] = $row["IFTA_QUARTER"];
				}
			}
		}

		//$this->log_event( __METHOD__.": ".count($this->installed_quarters)." installed quarters", EXT_ERROR_DEBUG);
		return $this->installed_quarters;
	}
	
	//! Is this rate already downloaded?
	public function is_installed( $rate ) {
		$this->get_installed_quarters();
		
		return is_array($this->installed_quarters) && in_array($rate, $this->installed_quarters);
	}	
	
	//! Get the most recent quarter that we have installed.
	public function get_latest_installed_quarter() {
		$result = false;
		if( ! isset($this->installed_quarters) )
			$this->get_installed_quarters();
		if( is_array($this->installed_quarters) && count($this->installed_quarters) > 0 )
			$result = $this->installed_quarters[0];
		return $result;
	}

	//! Get the current quarter.
	public function get_current_quarter() {
		$current_year = date('Y');
		$current_quarter = ceil(date("m")/3);
		
		return $current_quarter.'Q'.$current_year;
	}
	
    //! create a menu to select from available rates
    // go back 5 years
    public function rates_menu( $selected = false, $show_select = false, $id = 'IFTA_QUARTER' ) {
		$select = false;
		$choices = array();
		$this->get_available_rates();
		$this->get_installed_quarters();

		if( is_array($this->available_rates)) {		// If rates available
			$current_year = date('Y');
			$current_quarter = ceil(date("m")/3);
			
			for( $y = $current_year; $y >= $current_year-$this->num_years; $y-- ) {
				for( $q = 4; $q >=1; $q-- ) {
					$rate = $q.'Q'.$y;

					if( $this->debug ) echo "<p>".__METHOD__.":  y = $y q = $q rate = $rate</p>";
					if( $y < $current_year || ($y == $current_year && $q <= $current_quarter) ) {
						$choices[$rate] = $q.'Q '.$y;
						if( ! $this->is_available( $rate ) )
							$choices[$rate] .= ' (rates not available yet)';
						else if( $this->is_installed( $rate ) ) {
							$choices[$rate] .= ' (rates downloaded)';
						}
					}
				}
			}
		}
			
		if( count($choices) > 0) {
			
			$select = '<select class="form-control input-sm" name="'.$id.'" id="'.$id.'" onchange="form.submit();">
			';
			if( $show_select )
				$select .= '<option value="">Select a Quarter</option>';
			foreach( $choices as $key => $value ) {
				$select .= '<option value="'.$key.'"';
				if( $selected && $selected == $key )
					$select .= ' selected';
				$select .= '>'.$value.'</option>
				';
			}
			$select .= '</select>';
		}
			
		return $select;
	}
	
	//! For use with datatables, menu of states and provinces
	public function jurisdiction_menu() {
		$menu = '';
		//$check = $this->fetch_rows("", "DISTINCT IFTA_JURISDICTION", "1 ASC");
		$check = $this->database->get_multiple_rows("SELECT abbrev as IFTA_JURISDICTION
			FROM EXP_STATES
			ORDER BY 1 ASC");
		
		if( is_array($check) && count($check) > 0 ) {
			$menu .= 'type:  "select",
                options: [
	                ';		
		
			foreach( $check as $row) {
				$menu .= '{ label: "'.$row["IFTA_JURISDICTION"].'", value: "'.$row["IFTA_JURISDICTION"].'" },
				';
			}
			$menu .= '],';
		}
		return $menu;
	}

    //! Download the IFTA rates for a quarter, in XML format and inject into the DB
    public function download_rates( $quarter_year, $progress = false ) {
	    $result = false;
	    if( $this->debug ) {
		    echo "<p>".__METHOD__.": quarter_year = $quarter_year</p>";
		}
		$this->log_event( __METHOD__.": quarter_year = $quarter_year", EXT_ERROR_DEBUG);
		$this->get_available_rates();
		
		if( in_array($quarter_year, $this->available_rates) ) {

			$uri = $this->ifta_server.$quarter_year.'.xml';
			$xml = simplexml_load_file($uri);
			if( $xml ) {
				if( $this->debug ) {
					echo '<p>QUARTER = '.$xml->QUARTER.'</p>';
				}
				$this->delete_row("IFTA_QUARTER = '".$xml->QUARTER."'");
				$this->footnote->delete_row("IFTA_QUARTER = '".$xml->QUARTER."'");
				
				$footnote_link = array();
				foreach( $xml->FOOTNOTES->FOOTNOTE as $footnote ) {
					if( $this->debug ) {
						echo '<p>FOOTNOTE = '.$footnote.'</p>';
					}
					if( $progress ) {
						echo '.';
						ob_flush(); flush();
					}
					$changes = array( 'IFTA_QUARTER' => $xml->QUARTER,
						'IFTA_TEXT' => $footnote );
					foreach($footnote->attributes() as $a => $b) {
						if( $a == "ID" ) {
							$changes["IFTA_FOOTNOTE"] = (string) $b;
						}
					}
					
					// Add footnote via subclass sts_ifta_footnote
					$footnote_link[$changes["IFTA_FOOTNOTE"]] = $this->footnote->add($changes); 
				}
	
				$count = 0;
				foreach( $xml->RECORD as $record ) {
					if( $progress ) {
						echo '.';
						ob_flush(); flush();
					}
					
					if( $this->debug ) {
						echo '<p>JURISDICTION = '.$record->JURISDICTION.'</p>';
						echo "<pre>";
						var_dump($record);
						echo "</pre>";
					}
					$type = $record->FUEL_TYPE;
					$rate = $record->RATE;
					for( $c=0; $c < count($type); $c++) {
						if( floatval($rate[$c*2]) > 0.0 ) {
							if( $this->debug ) {
								echo '<p>'.strval($type[$c]).' = '.floatval($rate[$c*2]).' / '.floatval($rate[$c*2+1]).'</p>';
							}
		
							$changes = array( 'IFTA_QUARTER' => $xml->QUARTER, 
								'IFTA_JURISDICTION' => $record->JURISDICTION,
								'IFTA_FUEL_TYPE' => strval($type[$c]),
								'IFTA_US_RATE' => floatval($rate[$c*2]),
								'IFTA_CAN_RATE' => floatval($rate[$c*2+1]) );
							
							foreach($record->JURISDICTION->attributes() as $a => $b) {
								if( $a == "ID" ) {
									if( isset($footnote_link[(string) $b]))
										$changes["IFTA_FOOTNOTE"] = $footnote_link[(string) $b];
								} else if( $a == "SURCHARGE" ) {
									$changes["IFTA_SURCHARGE"] = true;
								} else if( $a == "EFFECTIVE_DATE" ) {
									$changes["EFFECTIVE_DATE"] = date("Y-m-d", strtotime((string) $b));
								}
							}
							
							if( $this->debug ) {
								echo "<pre>Changes\n";
								var_dump($changes);
								echo "</pre>";
							}
							
							if( $this->add($changes) )
								$count++;
						}
					}
				}
			    $result = $count;
	
				//echo "<pre>";
				//var_dump($xml);
				//echo "</pre>";
			}
		}
		if( $this->debug ) {
			echo "<p>".__METHOD__.": return ".($result ? $result : 'false')."</p>";
		}
		$this->log_event( __METHOD__.": return ".($result ? $result : 'false'), EXT_ERROR_DEBUG);
		return $result;
    }

	//! View the IFTA rates for a quarter
	public function view_rates( $quarter_year ) {
	    $result = false;
		$this->log_event( __METHOD__.": quarter_year = $quarter_year", EXT_ERROR_DEBUG);

		if( $this->is_installed( $quarter_year ) ) {
			$types = $this->get_enum_choices( 'IFTA_FUEL_TYPE' );
			$check = $this->fetch_rows("IFTA_QUARTER = '".$quarter_year."'",
				"IFTA_JURISDICTION, IFTA_SURCHARGE, EFFECTIVE_DATE, IFTA_FOOTNOTE, IFTA_FUEL_TYPE,
				IFTA_US_RATE, IFTA_CAN_RATE",
				"IFTA_JURISDICTION ASC, IFTA_SURCHARGE ASC, EFFECTIVE_DATE ASC, IFTA_FUEL_TYPE ASC");
			
			if( is_array($check) && count($check) > 0 ) {
				$footnotes = $this->footnote->get_footnotes( $quarter_year );
				$j = array();
				$table = array();
				foreach( $check as $row ) {
					$key = $row["IFTA_JURISDICTION"].$row["IFTA_SURCHARGE"].
						(isset($row["EFFECTIVE_DATE"]) ? $row["EFFECTIVE_DATE"] : '');
					if( ! isset($j[$key])) {
						$j[$key] = true;
						if( ! empty($row2) )
							$table[] = $row2;
						$row2 = array();
						$row2["IFTA_JURISDICTION"] = $row["IFTA_JURISDICTION"];
						$row2["IFTA_FOOTNOTE"] = $row["IFTA_FOOTNOTE"];
						$row2["IFTA_SURCHARGE"] = $row["IFTA_SURCHARGE"];
						$row2["EFFECTIVE_DATE"] = (isset($row["EFFECTIVE_DATE"]) ? date("m/d/Y", strtotime($row["EFFECTIVE_DATE"])) : '');
						$row2["IFTA_US_RATE"] = array();
						$row2["IFTA_CAN_RATE"] = array();
					}
					$row2["IFTA_US_RATE"][$row["IFTA_FUEL_TYPE"]] = $row["IFTA_US_RATE"];
					$row2["IFTA_CAN_RATE"][$row["IFTA_FUEL_TYPE"]] = $row["IFTA_CAN_RATE"];
				}
				if( ! empty($row2) )
					$table[] = $row2;
				//echo "<pre>";
				//var_dump($table);
				//echo "</pre>";
				
				// Build the table
				$result = '<div class="table-responsive">
		<table class="display table table-striped table-condensed table-bordered table-hover" id="EXP_EDI">
		<thead><tr class="exspeedite-bg"><th colspan="2">Jurisdiction</th><th>'.implode('</th><th>', $types).'</th>
		</tr>
		</thead>
		<tbody>
		';
				foreach( $table as $row2 ) {
					$j = $row2["IFTA_JURISDICTION"];
					if( isset($row2["IFTA_SURCHARGE"]) && $row2["IFTA_SURCHARGE"] )
						$j .= '-S';
					if( isset($row2["IFTA_FOOTNOTE"]))
						$j = '<span class="informr" data-placement="bottom" data-toggle="popover" data-content="'.$footnotes[$row2["IFTA_FOOTNOTE"]].'"><strong>'.$j.'</strong></span>';
					if( isset($row2["EFFECTIVE_DATE"]) && $row2["EFFECTIVE_DATE"] <> '' )
						$j .= '<br>'.$row2["EFFECTIVE_DATE"];
					
					$result .= '<tr><td>'.$j.'</td><td>US<br>Can</td>';
					foreach( $types as $type ) {
						$result .= '<td class="text-right">'.(isset($row2["IFTA_US_RATE"][$type]) ? number_format((float) $row2["IFTA_US_RATE"][$type], 4) : '').
						(isset($row2["IFTA_CAN_RATE"][$type]) ? '<br>'.number_format((float) $row2["IFTA_CAN_RATE"][$type], 4) : '').'</td>';
					}
					$result .= '</tr>';
				}
				
				$result .= '</tbody>
			</table>
		</div>
		';
			}


		} else if( $this->is_available( $quarter_year ) ) {
			$result = '<h3>Rates for this quarter is not yet downloaded.<br>
			Click the green button to download rates.</h3>';			
		} else if( $quarter_year <> '' ) {
			$result = '<h3>Rates for this quarter is not yet available for download.<br>
			Check back sometime after '.$this->available[$quarter_year[0]].'</h3>';
		}
		return $result;		
	}

}

class sts_ifta_footnote extends sts_table {
	private $setting_table;

	// Constructor does not need the table name
	public function __construct( $database, $debug = false ) {

		$this->debug = $debug;
		$this->primary_key = "IFTA_FOOTNOTE_CODE";
		$this->database = $database;

		$myclass = get_class ();
		if( $debug ) echo "<p>Create $myclass</p>";
		parent::__construct( $database, IFTA_FOOTNOTE_TABLE, $debug);
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
    
    public function get_footnotes( $quarter_year ) {
		$check = $this->fetch_rows("IFTA_QUARTER = '".$quarter_year."'",
			"IFTA_FOOTNOTE_CODE, IFTA_TEXT",
			"IFTA_FOOTNOTE_CODE ASC");
		
		if( is_array($check) && count($check) > 0 ) {
			$result = array();
			foreach( $check as $row ) {
				$result[$row["IFTA_FOOTNOTE_CODE"]] = $row["IFTA_TEXT"];
			}
		}
		
		return $result;		
    }

}
