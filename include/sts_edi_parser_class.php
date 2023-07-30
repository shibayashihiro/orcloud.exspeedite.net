<?php

// This is the EDI parser class, it contains all the tokenize and parse methods, plus dump_edi

// no direct access
defined('_STS_INCLUDE') or die('Restricted access');
require_once( dirname(__FILE__) ."/sts_edi_class.php" );

class sts_edi_parser extends sts_edi {
	private $tokens;
	private $current;
	private $remote_id	= false;
	private $our_id		= false;
	private $version	= false;

	// Constructor
	public function __construct( $database, $debug = false ) {
		$myclass = get_class ();
		if( $debug ) echo "<p>Create $myclass</p>";
		parent::__construct( $database, $debug );
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

	//! Read in the lines and parse into tokens
	public function tokenize( $edi ) {
		if( $this->debug ) echo "<p>".__METHOD__.": ".(isset($edi) ?strlen($edi)." characters" : "").".</p>";
		// Sometimes line endings are ~, so we change to \n
		if( strpos($edi, '~') !== false ) $edi = str_replace('~', "\n", $edi);
		if( strpos($edi, "\r") !== false ) $edi = str_replace("\r", "\n", $edi);
		//Weird character I find in word documents instead of ~
		if( strpos($edi, chr(152)) !== false ) $edi = str_replace(chr(152), "", $edi);
		if( $this->debug ) {
			echo "<pre>";
			var_dump($edi);
			echo "</pre>";
		}
		$lines = explode("\n", $edi);
		$this->tokens = array();
		
		//! Work out the data element separator, the first character after ISA on line 1
		$line1 = trim($lines[0]);
		$separator = '*'; // default value
		if( substr($line1, 0, 3) == 'ISA' && substr($line1, 3, 1) <> $separator ) {
			$separator = substr($line1, 3, 1);
			if( $this->debug ) echo "<p>".__METHOD__.": switching separator to $separator</p>";
			$this->log_event( __METHOD__.": switching separator to $separator", EXT_ERROR_NOTICE);
		}
			
		foreach( $lines as $line ) {
			$line = trim($line);
			//if( $this->debug ) echo "<p>".__METHOD__.": line ".$line." hex=.".bin2hex($line)."</p>";
			if( $line <> '') {
				$cols = explode($separator, $line);
				$id = array_shift($cols);
				$colnum = 1;
				$fields = array();
				foreach($cols as $col) {
					$label = $id.sprintf('%02d', $colnum++);
					$col = trim($col);
					if( $col <> '' ) $fields[$label] = $col;
				}
				$this->tokens[] = array('id' => $id, 'fields' => $fields);
			}
		}
		if( $this->debug ) echo "<p>".__METHOD__.": return ".count($this->tokens)." tokens.</p>";
		$this->log_event( __METHOD__.": return ".count($this->tokens)." tokens.", EXT_ERROR_NOTICE);
		return $this->tokens;
	}

	//! Validation
	private function validate_ISA( $fields ) {
		if( is_array($fields) ) {
			if( $this->remote_id &&
				(! isset($fields['ISA06']) || $fields['ISA06'] <> $this->remote_id) ) {
				throw new Exception(__METHOD__.': ISA06 should be '.$this->remote_id.', was '.$fields['ISA06']);
			}
			if( $this->our_id &&
				(! isset($fields['ISA08']) || $fields['ISA08'] <> $this->our_id) ) {
				throw new Exception(__METHOD__.': ISA08 should be '.$this->our_id.', was '.$fields['ISA08']);
			}
			if( $this->version &&
				(! isset($fields['ISA11']) || $fields['ISA11'] <> ISA11_ID) ) {
				throw new Exception(__METHOD__.': ISA11 should be '.ISA11_ID.', was '.$fields['ISA11']);
			}
			if( $this->version &&
				(! isset($fields['ISA12']) || $fields['ISA12'] <> ISA12_VERSION) ) {
				throw new Exception(__METHOD__.': ISA12 should be '.ISA12_VERSION.', was '.$fields['ISA12']);
			}
			if( ! isset($fields['ISA14']) || $fields['ISA14'] <> '0') {
				throw new Exception(__METHOD__.': ISA14 should be 0, was '.$fields['ISA14']);
			}
			if( ! isset($fields['ISA15']) || ! in_array($fields['ISA15'], array('T','P')) ) {
				throw new Exception(__METHOD__.': ISA15 should be T or P, was '.$fields['ISA15']);
			}
		} else {
			throw new Exception(__METHOD__.': missing fields');
		}
		if( $this->debug ) echo "<p>".__METHOD__.": exit ok.</p>";
	}
	
	private function validate_GS( $fields ) {
		if( is_array($fields) ) {
			if( ! isset($fields['GS01']) || ! in_array($fields['GS01'], array('SM', 'QM', 'GF')) ) {
				throw new Exception(__METHOD__.': GS01 should be SM, QM or GF, was '.$fields['GS01']);
			}
			if( $this->remote_id &&
				(! isset($fields['GS02']) || $fields['GS02'] <> $this->remote_id) ) {
				throw new Exception(__METHOD__.': GS02 should be '.$this->remote_id.', was '.$fields['GS02']);
			}
			if( $this->our_id &&
				(! isset($fields['GS03']) || $fields['GS03'] <> $this->our_id) ) {
				throw new Exception(__METHOD__.': GS03 should be '.$this->our_id.', was '.$fields['GS03']);
			}
			if( $this->version &&
				(! isset($fields['GS07']) || $fields['GS07'] <> GS07_AGENCY) ) {
				throw new Exception(__METHOD__.': GS07 should be '.GS07_AGENCY.', was '.$fields['GS07']);
			}
			if( $this->version &&
				(! isset($fields['GS08']) || $fields['GS08'] <> GS08_VERSION) ) {
				throw new Exception(__METHOD__.': GS08 should be '.GS08_VERSION.', was '.$fields['GS08']);
			}
		} else {
			throw new Exception(__METHOD__.': missing fields');
		}
		if( $this->debug ) echo "<p>".__METHOD__.": exit ok.</p>";
	}
	
	private function validate_ST( $fields ) {
		if( is_array($fields) ) {
			if( ! isset($fields['ST01']) || $fields['ST01'] <> '204') {
				throw new Exception(__METHOD__.': ST01 should be 204, was '.$fields['ST01']);
			}
		} else {
			throw new Exception(__METHOD__.': missing fields');
		}
		if( $this->debug ) echo "<p>".__METHOD__.": exit ok.</p>";
	}
	
	private function parse_notdef1() {
		$notdef1 = array();
		if( $this->tokens[$this->current]['id'] == 'ISA' ) {
			$this->validate_ISA($this->tokens[$this->current]['fields']);
			$notdef1[] = $this->tokens[$this->current++];
			if( $this->tokens[$this->current]['id'] == 'GS' ) {
				$this->validate_GS($this->tokens[$this->current]['fields']);
				$notdef1[] = $this->tokens[$this->current++];
			} else {
				throw new Exception(__METHOD__.': expected GS, got '.$this->tokens[$this->current]['id']);
			}
		} else {
			throw new Exception(__METHOD__.': expected ISA, got '.$this->tokens[$this->current]['id']);
		}
		if( $this->debug ) echo "<p>".__METHOD__.": exit ok.</p>";
		return array('section' => 'Not Defined', 'segments' => $notdef1);
	}
	
	private function parse_204_loop100() {
		$loop100 = array();
		if( $this->tokens[$this->current]['id'] == 'N1' ) {
			$loop100[] = $this->tokens[$this->current++];
			if( $this->tokens[$this->current]['id'] == 'N2' ) {
				$loop100[] = $this->tokens[$this->current++];
			}
			if( $this->tokens[$this->current]['id'] == 'N3' ) {
				while( $this->tokens[$this->current]['id'] == 'N3' ) {
					$loop100[] = $this->tokens[$this->current++];
				}
			}
			if( $this->tokens[$this->current]['id'] == 'N4' ) {
				$loop100[] = $this->tokens[$this->current++];
			}
			if( $this->tokens[$this->current]['id'] == 'G61' ) {
				while( $this->tokens[$this->current]['id'] == 'G61' ) {
					$loop100[] = $this->tokens[$this->current++];
				}
			}
		} else {
			throw new Exception(__METHOD__.': expected N1, got '.$this->tokens[$this->current]['id']);
		}
		if( $this->debug ) echo "<p>".__METHOD__.": exit ok.</p>";
		return array('section' => 'loop100', 'segments' => $loop100);
	}
	
	private function parse_204_loop200() {
		$loop200 = array();
		if( $this->tokens[$this->current]['id'] == 'N7' ) {
			$loop200[] = $this->tokens[$this->current++];
		} else {
			throw new Exception(__METHOD__.': expected N7, got '.$this->tokens[$this->current]['id']);
		}
		if( $this->debug ) echo "<p>".__METHOD__.": exit ok.</p>";
		return array('section' => 'loop200', 'segments' => $loop200);
	}
	
	private function parse_204_loop300() {
		$loop300 = array();
		if( $this->tokens[$this->current]['id'] == 'S5' ) {
			$loop300[] = $this->tokens[$this->current++];
			if( $this->tokens[$this->current]['id'] == 'L11' ) {
				while( $this->tokens[$this->current]['id'] == 'L11' ) {	// Could be multiple
					$loop300[] = $this->tokens[$this->current++];
				}
			}
			if( $this->tokens[$this->current]['id'] == 'G62' ) {
				while( $this->tokens[$this->current]['id'] == 'G62' ) {	// Could be multiple
					$loop300[] = $this->tokens[$this->current++];
				}
			}
			if( $this->tokens[$this->current]['id'] == 'AT8' ) {
				$loop300[] = $this->tokens[$this->current++];
			}
			if( $this->tokens[$this->current]['id'] == 'PLD' ) {
				$loop300[] = $this->tokens[$this->current++];
			}
			if( $this->tokens[$this->current]['id'] == 'NTE' ) {
				while( $this->tokens[$this->current]['id'] == 'NTE' ) {	// Could be multiple
					$loop300[] = $this->tokens[$this->current++];
				}
			}
			if( $this->tokens[$this->current]['id'] == 'N1' ) {
				while( $this->tokens[$this->current]['id'] == 'N1' ) {
					$loop300[] = $this->parse_204_loop310();
				}
			} else {
				throw new Exception(__METHOD__.': expected N1 (loop310), got '.$this->tokens[$this->current]['id']);
			}
			if( in_array($this->tokens[$this->current]['id'], array('L5', 'AT8', 'G61', 'OID', 'G62', 'LAD'))  ) {
				while( in_array($this->tokens[$this->current]['id'], array('L5', 'AT8', 'G61', 'OID', 'G62', 'LAD')) ) {
					$loop300[] = $this->parse_204_loop320();
				}
			} //else {
				//throw new Exception(__METHOD__.': expected L5 (loop320), got '.$this->tokens[$this->current]['id']);
			//}
		} else {
			throw new Exception(__METHOD__.': expected S5, got '.$this->tokens[$this->current]['id']);
		}
		if( $this->debug ) echo "<p>".__METHOD__.": exit ok.</p>";
		return array('section' => 'loop300', 'segments' => $loop300);
	}
	
	private function parse_204_loop310() {
		$loop310 = array();
		if( $this->tokens[$this->current]['id'] == 'N1' ) {
			$loop310[] = $this->tokens[$this->current++];
			if( $this->tokens[$this->current]['id'] == 'N3' ) {
				$loop310[] = $this->tokens[$this->current++];
				if( $this->tokens[$this->current]['id'] == 'N4' ) {
					$loop310[] = $this->tokens[$this->current++];
					if( $this->tokens[$this->current]['id'] == 'G61' ) {
						$loop310[] = $this->tokens[$this->current++];
					}
				} else {
					throw new Exception(__METHOD__.': expected N4, got '.$this->tokens[$this->current]['id']);
				}
			} else {
				throw new Exception(__METHOD__.': expected N3, got '.$this->tokens[$this->current]['id']);
			}
		} else {
			throw new Exception(__METHOD__.': expected N1, got '.$this->tokens[$this->current]['id']);
		}
		if( $this->debug ) echo "<p>".__METHOD__.": exit ok.</p>";
		return array('section' => 'loop310', 'segments' => $loop310);
	}
	
	private function parse_204_loop320() {
		$loop320 = array();
		if( $this->tokens[$this->current]['id'] == 'L5' ) {
			while( $this->tokens[$this->current]['id'] == 'L5' ) {
				$loop320[] = $this->tokens[$this->current++];
			}
		}
		if( $this->tokens[$this->current]['id'] == 'AT8' ) {
			$loop320[] = $this->tokens[$this->current++];
		}
		if( $this->tokens[$this->current]['id'] == 'G61' ) {
			while( $this->tokens[$this->current]['id'] == 'G61' ) {
				$loop320[] = $this->parse_204_loop325();
			}
		}
		if( in_array($this->tokens[$this->current]['id'], array('OID', 'G62', 'LAD')) ) {
			while( in_array($this->tokens[$this->current]['id'], array('OID', 'G62', 'LAD')) ) {
				$loop320[] = $this->parse_204_loop350();
			}
		}
		if( $this->debug ) echo "<p>".__METHOD__.": exit ok.</p>";
		return array('section' => 'loop320', 'segments' => $loop320);
	}
	
	private function parse_204_loop325() {
		$loop325 = array();
		if( $this->tokens[$this->current]['id'] == 'G61' ) {
			$loop325[] = $this->tokens[$this->current++];
			if( $this->tokens[$this->current]['id'] == 'LH1' ) {
				while( $this->tokens[$this->current]['id'] == 'LH1' ) {
					$loop325[] = $this->parse_204_loop330();
				}
			}
		} else {
			throw new Exception(__METHOD__.': expected G61, got '.$this->tokens[$this->current]['id']);
		}
		if( $this->debug ) echo "<p>".__METHOD__.": exit ok.</p>";
		return array('section' => 'loop325', 'segments' => $loop325);
	}
	
	private function parse_204_loop330() {
		$loop330 = array();
		if( $this->tokens[$this->current]['id'] == 'LH1' ) {
			$loop330[] = $this->tokens[$this->current++];
			if( $this->tokens[$this->current]['id'] == 'LH2' ) {
				$loop330[] = $this->tokens[$this->current++];
			}
			if( $this->tokens[$this->current]['id'] == 'LH3' ) {
				$loop330[] = $this->tokens[$this->current++];
			}
		} else {
			throw new Exception(__METHOD__.': expected LH1, got '.$this->tokens[$this->current]['id']);
		}
		if( $this->debug ) echo "<p>".__METHOD__.": exit ok.</p>";
		return array('section' => 'loop330', 'segments' => $loop330);
	}
	
	private function parse_204_loop350() {
		$loop350 = array();
		if( $this->tokens[$this->current]['id'] == 'OID' ) {
			$loop350[] = $this->tokens[$this->current++];
		}
		if( $this->tokens[$this->current]['id'] == 'G62' ) {
			$loop350[] = $this->tokens[$this->current++];
		}
		if( $this->tokens[$this->current]['id'] == 'LAD' ) {
			while( $this->tokens[$this->current]['id'] == 'LAD' ) {
				$loop350[] = $this->tokens[$this->current++];
			}
		}
		if( in_array($this->tokens[$this->current]['id'], array('L5', 'AT8', 'G61', 'L11')) ) {
			while( in_array($this->tokens[$this->current]['id'], array('L5', 'AT8', 'G61', 'L11')) ) {
				$loop350[] = $this->parse_204_loop360();
			}
		}
		if( in_array($this->tokens[$this->current]['id'], array('LH1', 'LH2', 'LH3', 'LFH')) ) {
			while( in_array($this->tokens[$this->current]['id'], array('LH1', 'LH2', 'LH3', 'LFH')) ) {
				$loop350[] = $this->parse_204_loop370();
			}
		}
		if( $this->debug ) echo "<p>".__METHOD__.": exit ok.</p>";
		return array('section' => 'loop350', 'segments' => $loop350);
	}
	
	private function parse_204_loop360() {
		$loop360 = array();
		if( $this->tokens[$this->current]['id'] == 'L5' ) {
			$loop360[] = $this->tokens[$this->current++];
		}
		if( $this->tokens[$this->current]['id'] == 'AT8' ) {
			$loop360[] = $this->tokens[$this->current++];
		}
		if( in_array($this->tokens[$this->current]['id'], array('G61', 'L11')) ) {
			while( in_array($this->tokens[$this->current]['id'], array('G61', 'L11')) ) {
				$loop360[] = $this->parse_204_loop365();
			}
		}
		if( $this->debug ) echo "<p>".__METHOD__.": exit ok.</p>";
		return array('section' => 'loop360', 'segments' => $loop360);
	}
	
	private function parse_204_loop365() {
		$loop365 = array();
		if( $this->tokens[$this->current]['id'] == 'G61' ) {
			$loop365[] = $this->tokens[$this->current++];
		}
		if( $this->tokens[$this->current]['id'] == 'L11' ) {
			while( $this->tokens[$this->current]['id'] == 'L11' ) {	// Could be multiple
				$loop365[] = $this->tokens[$this->current++];
			}
		}
		if( $this->debug ) echo "<p>".__METHOD__.": exit ok.</p>";
		return array('section' => 'loop365', 'segments' => $loop365);
	}
	
	private function parse_204_loop370() {
		$loop370 = array();
		if( $this->tokens[$this->current]['id'] == 'LH1' ) {
			$loop370[] = $this->tokens[$this->current++];
		}
		if( $this->tokens[$this->current]['id'] == 'LH2' ) {
			while( $this->tokens[$this->current]['id'] == 'LH2' ) {
				$loop370[] = $this->tokens[$this->current++];
			}
		}
		if( $this->tokens[$this->current]['id'] == 'LH3' ) {
			while( $this->tokens[$this->current]['id'] == 'LH3' ) {
				$loop370[] = $this->tokens[$this->current++];
			}
		}
		if( $this->tokens[$this->current]['id'] == 'LFH' ) {
			while( $this->tokens[$this->current]['id'] == 'LFH' ) {
				$loop370[] = $this->tokens[$this->current++];
			}
		}
		if( $this->debug ) echo "<p>".__METHOD__.": exit ok.</p>";
		return array('section' => 'loop370', 'segments' => $loop370);
	}
	
	private function parse_204_heading() {
		$heading = array();
		if( $this->tokens[$this->current]['id'] == 'ST' ) {
			$this->validate_ST($this->tokens[$this->current]['fields']);
			$heading[] = $this->tokens[$this->current++];
			if( $this->tokens[$this->current]['id'] == 'B2' ) {
				$heading[] = $this->tokens[$this->current++];
				if( $this->tokens[$this->current]['id'] == 'B2A' ) {
					$heading[] = $this->tokens[$this->current++];
					if( $this->tokens[$this->current]['id'] == 'L11' ) {
						while( $this->tokens[$this->current]['id'] == 'L11' ) {	// Could be multiple
							$heading[] = $this->tokens[$this->current++];
						}
						if( $this->tokens[$this->current]['id'] == 'G62' ) { // optional
							$heading[] = $this->tokens[$this->current++];
						}
						if( $this->tokens[$this->current]['id'] == 'PLD' ) {
							$heading[] = $this->tokens[$this->current++];
						}
						if( $this->tokens[$this->current]['id'] == 'AT5' ) {
							$heading[] = $this->tokens[$this->current++];
						}
						if( $this->tokens[$this->current]['id'] == 'NTE' ) {
							while( $this->tokens[$this->current]['id'] == 'NTE' ) {	// Could be multiple
								$heading[] = $this->tokens[$this->current++];
							}
						}
						if( $this->tokens[$this->current]['id'] == 'N1' ) {	// Need at least one
							while( $this->tokens[$this->current]['id'] == 'N1' ) {
								$heading[] = $this->parse_204_loop100();
							}
						} else {
							throw new Exception(__METHOD__.': expected N1 (loop100), got '.$this->tokens[$this->current]['id']);
						}
						while( $this->tokens[$this->current]['id'] == 'N7' ) {
							$heading[] = $this->parse_204_loop200();
						}
					} else {
						throw new Exception(__METHOD__.': expected L11, got '.$this->tokens[$this->current]['id']);
					}
				} else {
					throw new Exception(__METHOD__.': expected B2A, got '.$this->tokens[$this->current]['id']);
				}
			} else {
				throw new Exception(__METHOD__.': expected B2, got '.$this->tokens[$this->current]['id']);
			}
		} else {
			throw new Exception(__METHOD__.': expected ST, got '.$this->tokens[$this->current]['id']);
		}
		if( $this->debug ) echo "<p>".__METHOD__.": exit ok.</p>";
		return array('section' => 'Heading', 'segments' => $heading);
	}
	
	private function parse_204_detail() {
		$detail = array();
		if( $this->tokens[$this->current]['id'] == 'S5' ) {
			while( $this->tokens[$this->current]['id'] == 'S5' ) {
				$detail[] = $this->parse_204_loop300();
			}
			if( $this->tokens[$this->current]['id'] == 'N1' ) {
				while( $this->tokens[$this->current]['id'] == 'N1' ) {
					$detail[] = $this->parse_204_loop310();
				}
			}
			if( in_array($this->tokens[$this->current]['id'], array('L5', 'AT8', 'G61')) ) {
				while( in_array($this->tokens[$this->current]['id'], array('L5', 'AT8', 'G61')) ) {
					$detail[] = $this->parse_204_loop320();
				}
			}
		} else {
			throw new Exception(__METHOD__.': expected S5, got '.$this->tokens[$this->current]['id']);
		}
		if( $this->debug ) echo "<p>".__METHOD__.": exit ok.</p>";
		return array('section' => 'Detail', 'segments' => $detail);
	}
	
	private function parse_204_summary() {
		$summary = array();
		if( $this->tokens[$this->current]['id'] == 'L3' ) {
			$summary[] = $this->tokens[$this->current++];
		}
		if( $this->tokens[$this->current]['id'] == 'SE' ) {
			$summary[] = $this->tokens[$this->current++];
		} else {
			throw new Exception(__METHOD__.': expected SE, got '.$this->tokens[$this->current]['id']);
		}
		if( $this->debug ) echo "<p>".__METHOD__.": exit ok.</p>";
		return array('section' => 'Summary', 'segments' => $summary);
	}
	
	private function parse_204() {
		$output = array();
		$output[] = $this->parse_204_heading();
		$output[] = $this->parse_204_detail();
		$output[] = $this->parse_204_summary();
		if( $this->debug ) echo "<p>".__METHOD__.": exit ok.</p>";
		return array('section' => '204', 'segments' => $output);
	}
	
	private function parse_notdef2() {
		$notdef2 = array();
		if( $this->tokens[$this->current]['id'] == 'GE' ) {
			$notdef2[] = $this->tokens[$this->current++];
			if( $this->tokens[$this->current]['id'] == 'IEA' ) {
				$notdef2[] = $this->tokens[$this->current++];
			} else {
				throw new Exception(__METHOD__.': expected IEA, got '.$this->tokens[$this->current]['id']);
			}
		} else {
			throw new Exception(__METHOD__.': expected GE, got '.$this->tokens[$this->current]['id']);
		}
		if( $this->debug ) echo "<p>".__METHOD__.": exit ok.</p>";
		return array('section' => 'Not Defined2', 'segments' => $notdef2);
	}
	
	private function parse_990() {
		$output = array();
		if( $this->tokens[$this->current]['id'] == 'ST' ) {
			$output[] = $this->tokens[$this->current++];
			if( $this->tokens[$this->current]['id'] == 'B1' ) {
				$output[] = $this->tokens[$this->current++];
				if( $this->tokens[$this->current]['id'] == 'N9' ) {
					$output[] = $this->tokens[$this->current++];
				}
				if( $this->tokens[$this->current]['id'] == 'N7' ) {
					$output[] = $this->tokens[$this->current++];
				}
				if( $this->tokens[$this->current]['id'] == 'K1' ) {
					while( $this->tokens[$this->current]['id'] == 'K1' ) {	// Could be multiple
						$output[] = $this->tokens[$this->current++];
					}
				}
				if( $this->tokens[$this->current]['id'] == 'SE' ) {
					$output[] = $this->tokens[$this->current++];
				} else {
					throw new Exception(__METHOD__.': expected SE, got '.$this->tokens[$this->current]['id']);
				}
			} else {
				throw new Exception(__METHOD__.': expected B1, got '.$this->tokens[$this->current]['id']);
			}
		} else {
			throw new Exception(__METHOD__.': expected ST, got '.$this->tokens[$this->current]['id']);
		}
		if( $this->debug ) echo "<p>".__METHOD__.": exit ok.</p>";
		return array('section' => '990', 'segments' => $output);
	}
	
	private function parse_214_loop100() {
		$output = array();
		if( $this->tokens[$this->current]['id'] == 'N1' ) {
			$output[] = $this->tokens[$this->current++];
		}
		if( $this->tokens[$this->current]['id'] == 'N2' ) {
			$output[] = $this->tokens[$this->current++];
		}
		if( $this->tokens[$this->current]['id'] == 'N3' ) {
			$output[] = $this->tokens[$this->current++];
		}
		if( $this->tokens[$this->current]['id'] == 'N4' ) {
			$output[] = $this->tokens[$this->current++];
		}
		if( $this->tokens[$this->current]['id'] == 'G62' ) {
			$output[] = $this->tokens[$this->current++];
		}
		if( $this->tokens[$this->current]['id'] == 'L11' ) {
			$output[] = $this->tokens[$this->current++];
		}
		if( $this->tokens[$this->current]['id'] == 'MS3' ) {
			$output[] = $this->tokens[$this->current++];
		}
		if( $this->debug ) echo "<p>".__METHOD__.": exit ok.</p>";
		return array('section' => 'loop100', 'segments' => $output);
	}
	
	private function parse_214_loop200() {
		$output = array();
		if( $this->tokens[$this->current]['id'] == 'LX' ) {
			$output[] = $this->tokens[$this->current++];
			if( in_array($this->tokens[$this->current]['id'],
					array('AT7','MS1','MS2','L11','Q7','K1', 'AT8') ) ) {
				while( in_array($this->tokens[$this->current]['id'],
					array('AT7','MS1','MS2','L11','Q7','K1', 'AT8') ) ) {
					$output[] = $this->parse_214_loop205();
				}
			}
			if( $this->tokens[$this->current]['id'] == 'AT8' ) {
				$output[] = $this->tokens[$this->current++];
			}
		} else {
			throw new Exception(__METHOD__.': expected LX, got '.$this->tokens[$this->current]['id']);
		}
		if( $this->debug ) echo "<p>".__METHOD__.": exit ok.</p>";
		return array('section' => 'loop200', 'segments' => $output);
	}
	
	private function parse_214_loop205() {
		$output = array();
		if( $this->tokens[$this->current]['id'] == 'AT7' ) {
			$output[] = $this->tokens[$this->current++];
		}
		if( $this->tokens[$this->current]['id'] == 'MS1' ) {
			$output[] = $this->tokens[$this->current++];
		}
		if( $this->tokens[$this->current]['id'] == 'MS2' ) {
			$output[] = $this->tokens[$this->current++];
		}
		if( $this->tokens[$this->current]['id'] == 'L11' ) {
			while( $this->tokens[$this->current]['id'] == 'L11' ) {
				$output[] = $this->tokens[$this->current++];
			}
		}
		if( $this->tokens[$this->current]['id'] == 'Q7' ) {
			while( $this->tokens[$this->current]['id'] == 'Q7' ) {
				$output[] = $this->tokens[$this->current++];
			}
		}
		if( $this->tokens[$this->current]['id'] == 'K1' ) {
			while( $this->tokens[$this->current]['id'] == 'K1' ) {
				$output[] = $this->tokens[$this->current++];
			}
		}
		if( $this->tokens[$this->current]['id'] == 'AT8' ) {
			while( $this->tokens[$this->current]['id'] == 'AT8' ) {
				$output[] = $this->tokens[$this->current++];
			}
		}
		if( $this->debug ) echo "<p>".__METHOD__.": exit ok.</p>";
		return array('section' => 'loop205', 'segments' => $output);
	}
	
	private function parse_214() {
		$output = array();
		if( $this->tokens[$this->current]['id'] == 'ST' ) {
			$output[] = $this->tokens[$this->current++];
			if( $this->tokens[$this->current]['id'] == 'B10' ) {
				$output[] = $this->tokens[$this->current++];
				if( $this->tokens[$this->current]['id'] == 'L11' ) {
					while( $this->tokens[$this->current]['id'] == 'L11' ) {
						$output[] = $this->tokens[$this->current++];
					}
				}
				if( $this->tokens[$this->current]['id'] == 'MS3' ) {
					while( $this->tokens[$this->current]['id'] == 'MS3' ) {
						$output[] = $this->tokens[$this->current++];
					}
				}
				if( in_array($this->tokens[$this->current]['id'],
					array('N1','N2','N3','N4','G62','L11') ) ) {
					while( in_array($this->tokens[$this->current]['id'],
					array('N1','N2','N3','N4','G62','L11') ) ) {
						$output[] = $this->parse_214_loop100();
					}
				}
				if( $this->tokens[$this->current]['id'] == 'LX' ) {
					while( $this->tokens[$this->current]['id'] == 'LX' ) {
						$output[] = $this->parse_214_loop200();
					}
				}

				if( $this->tokens[$this->current]['id'] == 'SE' ) {
					$output[] = $this->tokens[$this->current++];
				} else {
					throw new Exception(__METHOD__.': expected SE, got '.$this->tokens[$this->current]['id']);
				}
			} else {
				throw new Exception(__METHOD__.': expected B10, got '.$this->tokens[$this->current]['id']);
			}
		} else {
			throw new Exception(__METHOD__.': expected ST, got '.$this->tokens[$this->current]['id']);
		}
		if( $this->debug ) echo "<p>".__METHOD__.": exit ok.</p>";
		return array('section' => '214', 'segments' => $output);
	}
	
	private function parse_824_loop300() {
		$output = array();
		if( $this->tokens[$this->current]['id'] == 'OTI' ) {
			$output[] = $this->tokens[$this->current++];
			if( $this->tokens[$this->current]['id'] == 'REF' ) {
				$output[] = $this->tokens[$this->current++];
			}
			if( $this->tokens[$this->current]['id'] == 'TED' ) {
				while( $this->tokens[$this->current]['id'] == 'TED' ) {
					$output[] = $this->parse_824_loop310();
				}
			}
		} else {
			throw new Exception(__METHOD__.': expected OTI, got '.$this->tokens[$this->current]['id']);
		}
		if( $this->debug ) echo "<p>".__METHOD__.": exit ok.</p>";
		return array('section' => 'loop300', 'segments' => $output);
	}
	
	private function parse_824_loop310() {
		$output = array();
		if( $this->tokens[$this->current]['id'] == 'TED' ) {
			$output[] = $this->tokens[$this->current++];
		} else {
			throw new Exception(__METHOD__.': expected TED, got '.$this->tokens[$this->current]['id']);
		}
		if( $this->debug ) echo "<p>".__METHOD__.": exit ok.</p>";
		return array('section' => 'loop310', 'segments' => $output);
	}
	
	private function parse_824() {
		$output = array();
		if( $this->tokens[$this->current]['id'] == 'ST' ) {
			$output[] = $this->tokens[$this->current++];
			if( $this->tokens[$this->current]['id'] == 'BGN' ) {
				$output[] = $this->tokens[$this->current++];
				if( $this->tokens[$this->current]['id'] == 'OTI' ) {
					while( $this->tokens[$this->current]['id'] == 'OTI' ) {
						$output[] = $this->parse_824_loop300();
					}
				}

				if( $this->tokens[$this->current]['id'] == 'SE' ) {
					$output[] = $this->tokens[$this->current++];
				} else {
					throw new Exception(__METHOD__.': expected SE, got '.$this->tokens[$this->current]['id']);
				}
			} else {
				throw new Exception(__METHOD__.': expected BGN, got '.$this->tokens[$this->current]['id']);
			}
		} else {
			throw new Exception(__METHOD__.': expected ST, got '.$this->tokens[$this->current]['id']);
		}
		if( $this->debug ) echo "<p>".__METHOD__.": exit ok.</p>";
		return array('section' => '824', 'segments' => $output);
	}
	
	public function parse_edi( $remote_id = false, $our_id = false, $version = false ) {
		if( $remote_id ) $this->remote_id = $remote_id;
		if( $our_id ) $this->our_id = $our_id;
		if( $version ) $this->version = $version;
		
		$edi = array();
		$this->current = 0;
		if( $this->enabled() ) {
			try {
				$edi[] = $this->parse_notdef1();
				$type = $this->lookup_edi_path( $edi, 'Not Defined/GS/GS01' );
				if( $this->tokens[$this->current]['id'] == 'ST' ) {
					while( $this->tokens[$this->current]['id'] == 'ST' ) {
						if( $this->debug ) echo "<p>".__METHOD__.": current=".$this->current." id=".$this->tokens[$this->current]['id']."</p>";
						if( $type ==  EDI_204 ) {
							$edi[] = $this->parse_204();
						} else if( $type ==  EDI_214 ) {
							$edi[] = $this->parse_214();
						} else if( $type ==  EDI_824 ) {
							$edi[] = $this->parse_824();
						} else if( $type ==  EDI_990 ) {
							$edi[] = $this->parse_990();
						}
					}
				}
				$edi[] = $this->parse_notdef2();
				if( $this->debug ) echo "<p>".__METHOD__.": exit ok.</p>";
				$this->log_event( __METHOD__.": exit ok.", EXT_ERROR_DEBUG);
			} catch (Exception $e) {
				$this->log_event( __METHOD__.": Caught exception: ".$e->getMessage(), EXT_ERROR_ERROR);
				if( $this->debug ) echo "<p>".__METHOD__.": <strong>Caught exception</strong>: ",  $e->getMessage(), "</p>";
				if( $this->debug ) echo $this->dump_edi( $edi );
			    $edi = false;
			}
		} else
			$edi = false;
		return $edi;
	}
	
	//! Dump an EDI parsed structure out.
	// Can dump in html for browser, or plain text for log files.
	// $edi - the structure to dump
	// $html - if true is in html
	// $indent - do not use, for internal use only.
	public function dump_edi( $edi, $html = true, $indent = '' ) {
	
		$output = '';
		if( $html ) {
			$b1 = '<strong>';
			$b2 = '</strong>';
			$ul1 = '<ul>';
			$ul2 = '</ul>';
			$br = '<br>';
			$li1 = '<li>';
			$li2 = '</li>';
			$indent = '';
		} else {
			$b1 = '';
			$b2 = '';
			$ul1 = "\n";
			$ul2 = "";
			$br = "\n";
			$li1 = '   ';
			$li2 = "\n";
		}
		if( is_array($edi)) {
			$section_num = 0;
			$previous_section = '';
			foreach( $edi as $section ) {
				if( isset($section['section'])) {
					if( $section['section'] <> $previous_section)
						$section_num = 0;
					$output .= $indent.$b1.'Begin '.$section['section'].' Section #'.++$section_num.$b2.$ul1;
					if( is_array($section['segments']))
						$output .= $this->dump_edi( $section['segments'], $html, $indent.'   ' );
					$output .= $ul2.$indent.$b1.'End '.$section['section'].' Section #'.$section_num.$b2.$br;
					$previous_section = $section['section'];
				} else {
					$id = $section['id'];
					$output .= $indent.$b1.$id.(isset($this->id_description[$id]) ? ' - '.$this->id_description[$id] : '').$b2.$ul1;
					foreach( $section['fields'] as $label => $value ) {
						$output .= $indent.$li1.$label.' = \''.$value.'\''.
							(isset($this->field_description[$label]) ? ' '.$b1.'('.$this->field_description[$label].
							(isset($this->field_value[$label][$value]) ? ' = '.$this->field_value[$label][$value] : '').
							')'.$b2 : '').$li2;			
					}
					$output .= $ul2;			
				}
			}
		}
		return $output;
	}
		
}

//! Layout Specifications - For use with sts_result

$sts_result_edi_layout = array(
	'EDI_CODE' => array( 'format' => 'hidden' ),
	'EDI_TIME' => array( 'label' => 'Date', 'format' => 'timestamp' ),
	'EDI_CLIENT' => array( 'label' => 'Client', 'format' => 'text' ),
	'EDI_TYPE' => array( 'label' => 'Type', 'format' => 'text' ),
	'DIRECTION' => array( 'label' => 'Dir', 'align' => 'center', 'format' => 'text' ),
	'B2A01_PURPOSE' => array( 'label' => 'Purpose', 'format' => 'text' ),
	'IDENTIFIER' => array( 'label' => 'ID', 'format' => 'text' ),
	'FILENAME' => array( 'label' => 'File', 'format' => 'text', 
		'link' => 'exp_view_edi.php?pw=ChessClub&code=%pk%&file=' ),
	'COMMENTS' => array( 'label' => 'Comments', 'format' => 'text' ),
);

$sts_result_edi_last10_layout = array(
	'EDI_CODE' => array( 'format' => 'hidden' ),
	'EDI_TIME' => array( 'label' => 'Date', 'format' => 'timestamp' ),
	'EDI_CLIENT' => array( 'label' => 'Client', 'format' => 'text' ),
	'EDI_TYPE' => array( 'label' => 'Type', 'format' => 'text' ),
	'DIRECTION' => array( 'label' => 'Dir', 'align' => 'center', 'format' => 'text' ),
	'B2A01_PURPOSE' => array( 'label' => 'Purpose', 'format' => 'text' ),
	'IDENTIFIER' => array( 'label' => 'ID', 'format' => 'text' ),
	'EDI_204_PRIMARY' => array( 'label' => 'Shipment#', 'format' => 'num0nc', 'link' => 'exp_addshipment.php?CODE=', 'align' => 'right' ),
	'FILENAME' => array( 'label' => 'File', 'format' => 'text', 
		'link' => 'exp_listftp.php?code=%pk%&file=' ),
	'COMMENTS' => array( 'label' => 'Comments', 'format' => 'text' ),
);

//! Edit/Delete Button Specifications - For use with sts_result

$sts_result_edi_edit = array(
	'title' => '<img src="images/edi_icon1.png" alt="setting_icon" height="40"> History',
	'sort' => 'EDI_CODE DESC',
	'cancelbutton' => 'Back',
	'rowbuttons' => array(
		array( 'url' => 'exp_edi_resend.php?PW=dapI&CODE=', 'key' => 'EDI_CODE', 'label' => 'FILENAME', 'tip' => 'Resend ', 'icon' => 'glyphicon glyphicon-repeat', 'showif' => 'edi', 'confirm' => 'yes' ),
	)	
);

$sts_result_edi_last10_edit = array(
	'title' => '<img src="images/edi_icon1.png" alt="setting_icon" height="40"> Recent Activity',
	'sort' => 'EDI_CODE DESC
		LIMIT 32',
	'filters' => false,
	'cancelbutton' => 'Back',
);


?>
