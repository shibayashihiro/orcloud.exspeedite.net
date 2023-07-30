<?php

// This is the EDI mapping class, it maps 204s into Exspeedite tables

// no direct access
defined('_STS_INCLUDE') or die('Restricted access');
require_once( dirname(__FILE__) ."/sts_edi_class.php" );
require_once( dirname(__FILE__) ."/sts_unit_class.php" );
require_once( dirname(__FILE__) ."/sts_user_class.php" );
require_once( dirname(__FILE__) ."/sts_load_class.php" );
require_once( dirname(__FILE__) ."/sts_stop_class.php" );
require_once( dirname(__FILE__) ."/sts_shipment_class.php" );
require_once( dirname(__FILE__) ."/sts_detail_class.php" );
require_once( dirname(__FILE__) ."/sts_commodity_class.php" );
require_once( dirname(__FILE__) ."/sts_client_class.php" );
require_once( dirname(__FILE__) ."/sts_contact_info_class.php" );
require_once( dirname(__FILE__) ."/sts_email_class.php" );

class sts_edi_map extends sts_edi {
	public $stop_table;
	public $load_table;
	public $shipment_table;
	public $detail_table;
	public $commodity_table;
	public $client_table;
	public $contact_info_table;
	public $unit_table;
	public $user_table;
	public $item_units_cache = array();
	public $edi_214_event_status = array();		// To be overridden by subclasses
	
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
    
    //! Lookup the unit for an item, using cache if possible
    public function lookup_item_units( $unit ) {
	    if( ! isset($this->item_units_cache[$unit])) {
	    	$this->item_units_cache[$unit] = intval($this->unit_table->lookup_unit_code( $unit, 'item' ));
	    }

	    return $this->item_units_cache[$unit];
    }

	//! remove detail1 from detail2
	// assumes detail1 is subset of detail2
	public function subtract_commodities( $detail1, &$detail2 ) {
		// Process into assoc arrays
		$d1 = array();
		$d2 = array();
		foreach( $detail1 as $detail ) {
			$d1[$detail["204_REFERENCE"]] = (int) $detail["PIECES"];
		}
		foreach( $detail2 as $detail ) {
			$d2[$detail["204_REFERENCE"]] = (int) $detail["PIECES"];
		}
		ksort($d1);
		ksort($d2);
		$diff11 = array_intersect_key($d1, $d2);	// overlapping details
		$diff12 = array_intersect_key($d2, $d1);
		foreach( $diff11 as $key => $value ) {
			if( $value == $diff12[$key] ) {
				for( $c=0; $c<count($detail2); $c++ ) {
					if( $detail2[$c]["204_REFERENCE"] == $key ) {
						unset($detail2[$c]);
						break;
					}
				}
			} else {
				for( $c=0; $c<count($detail2); $c++ ) {
					if( $detail2[$c]["204_REFERENCE"] == $key ) {
						$detail2[$c]["PIECES"] -= $value;
						break;
					}
				}
			}
		}		
	}
	
	//! Compare the detail info to find a match
	public function match_commodities( $detail1, $detail2 ) {
		$match = MATCH_NONE;
		
		// Process into assoc arrays
		$d1 = array();
		$d2 = array();
		foreach( $detail1 as $detail ) {
			$d1[$detail["204_REFERENCE"]] = (int) $detail["PIECES"];
		}
		foreach( $detail2 as $detail ) {
			$d2[$detail["204_REFERENCE"]] = (int) $detail["PIECES"];
		}
		ksort($d1);
		ksort($d2);
		$diff1 = array_diff($d1, $d2);
		$diff2 = array_diff($d2, $d1);
		$diff3 = array_diff_key($d1, $d2);
		$diff4 = array_diff_key($d2, $d1);
		$diff5 = array_diff_assoc($d1, $d2);
		$diff6 = array_diff_assoc($d2, $d1);
		$diff7 = array_intersect($d1, $d2);
		$diff8 = array_intersect($d2, $d1);
		$diff9 = array_intersect_assoc($d1, $d2);
		$diff10 = array_intersect_assoc($d2, $d1);
		$diff11 = array_intersect_key($d1, $d2);
		$diff12 = array_intersect_key($d2, $d1);

		/*
		echo "<p>".__METHOD__.": d1, d2, diff1, diff2...</p><pre>d1, d2\n";
		var_dump($d1, $d2);
		echo "array_diff\n";
		var_dump($diff1, $diff2);
		echo "array_diff_key\n";
		var_dump($diff3, $diff4);
		echo "array_diff_assoc\n";
		var_dump($diff5, $diff6);
		echo "array_intersect\n";
		var_dump($diff7, $diff8);
		echo "array_intersect_assoc\n";
		var_dump($diff9, $diff10);
		echo "array_intersect_key\n";
		var_dump($diff11, $diff12);
		echo "try1\n";
		var_dump(array_diff($d1, $diff11));
		echo "</pre>";
		*/
		
		if( empty($diff5) && empty($diff6) ) {
			if( $this->debug ) echo "<p>".__METHOD__.": Exact match!</p>";
			$match = MATCH_EXACT;
		} else if( empty($diff3) && ! empty($diff4) &&
			! empty($diff11) && empty(array_diff($d1, $diff11)) ) {
			//echo "<p>".__METHOD__.": Possible subset!</p>";
			$subset_match = true;
			foreach( $diff11 as $key => $value ) {
				//echo "<p>".__METHOD__.": key $key $value $diff12[$key]</p>";
				if( $value > $diff12[$key] ) {
					$subset_match = false;
					break;
				}
			}
			if( $subset_match ) {
				if( $this->debug ) echo "<p>".__METHOD__.": Subset confirmed!</p>";
				$match = MATCH_SUBSET;
			} else {
				if( $this->debug ) echo "<p>".__METHOD__.": Subset NOT confirmed!</p>";
			}
		} else if( empty($diff4) && ! empty($diff3) &&
			! empty($diff12) && empty(array_diff($d2, $diff12)) ) {
			//if( $this->debug ) echo "<p>".__METHOD__.": Possible superset!</p>";
			$superset_match = true;
			foreach( $diff12 as $key => $value ) {
				//if( $this->debug ) echo "<p>".__METHOD__.": key $key $value $diff12[$key]</p>";
				if( $value > $diff11[$key] ) {
					$superset_match = false;
					break;
				}
			}
			if( $superset_match ) {
				if( $this->debug ) echo "<p>".__METHOD__.": Superset confirmed!</p>";
				$match = MATCH_SUPERSET;
			} else {
				if( $this->debug ) echo "<p>".__METHOD__.": Superset NOT confirmed!</p>";
			}
		}
		
		return $match;
	}

	//! Return true if there details not yet unloaded
	public function got_details_left( $details ) {
		$count_remaining = is_array($details) ? count($details) : 0;
		if( $this->debug ) echo "<p>".__METHOD__.": $count_remaining remaining details.</p>";
		return is_array($details) && ! empty($details);
	}
	
	//! Return true if there are shipments not yet unloaded
	public function got_shipments_left( $shipments ) {
		$result = false;
		$count_remaining = 0;
		$count_shipments = 0;
		if( is_array($shipments) && ! empty($shipments) ) {
			$count_shipments = count($shipments);
			foreach($shipments as $shipment) {
				if( ! isset($shipment["DROPPED"]) ) {
					$count_remaining++;
					$result = true;
				}
			}
		}
		if( $this->debug ) echo "<p>".__METHOD__.": $count_remaining remaining out of $count_shipments shipments.</p>";
		return $result;
	}
	
	//! Merge in the heading and summary fields into the shipment fields
	public function merge_shipment_fields( $heading_fields,  $summary_fields, &$shipment_fields ) {
		$shipment_fields = array_merge($heading_fields, $shipment_fields);
		$shipment_fields = array_merge($summary_fields, $shipment_fields);
		/* Useful for checking if the DB has all the required fields
		foreach( $shipment_fields as $key => $value ) {
			if( $key <> 'DETAIL' && ! $this->shipment_table->column_exists( $key ) ) {
				if( $this->debug ) echo "<p>".__METHOD__.": shipment table missing column $key = $value</p>";
			}
		}
		*/
	}

	//! Add a client in Exspeedite, given the relevant info
	public function add_client( $name, $addr1, $addr2, $city, $state, $zip, $phone, $fax, $email, $contact, $type ) {

		$this->log_event( __METHOD__.": entry $name", EXT_ERROR_DEBUG);
		if( ! is_object($this->client_table))
			$this->client_table = sts_client::getInstance($this->database, $this->debug);
		$check = $this->client_table->fetch_rows("CLIENT_NAME = '".
			$this->client_table->real_escape_string(trim((string) $name))."' AND
			ISDELETED = false",$this->client_table->primary_key.", BILL_TO, CONSIGNEE");
		$is_new = is_array($check) && count($check) == 0;

		if( $is_new ) {
			$client_fields = array("CLIENT_NAME" => trim((string) $name), "COMMENTS" => "Added via EDI 204 import" );
			switch( $type ) {
				case 'BILLTO': $client_fields["BILL_TO"] = "TRUE";
				break;
				case 'CONS': $client_fields["CONSIGNEE"] = "TRUE";
				break;
			}
			$client_fields["DELETED"] = "FALSE";
			$client_code = $this->client_table->add($client_fields);
			// CONTACT_NAME
			$contact_info_fields = array("CONTACT_CODE" => $client_code, "CONTACT_SOURCE" => 'client',
				"CONTACT_TYPE" => ($type == 'BILLTO' ? 'bill_to' : 'consignee'), "LABEL" => $name, "ADDRESS" => $addr1,
				"ADDRESS2" => $addr2, "CITY" => $city, "STATE" => $state, "ZIP_CODE" => $zip,
				"PHONE_OFFICE" => $phone, "PHONE_FAX" => $fax, "EMAIL" => $email,
				"CONTACT_NAME" => $contact, "DELETED" => "FALSE" );
			$contact_info_code = $this->contact_info_table->add($contact_info_fields);
			$this->log_event( __METHOD__.": added ".trim((string) $name)." code = ".$client_code, EXT_ERROR_DEBUG);
		} else {
			$client_code = $check[0][$this->client_table->primary_key];
			if( $check[0]["BILL_TO"] == 0 && $type == "BILL_TO" )
				$this->client_table->update( $client_code, array("BILL_TO" => "TRUE") );
			if( $check[0]["CONSIGNEE"] == 0 && $type == "CONS" )
				$this->client_table->update( $client_code, array("CONSIGNEE" => "TRUE") );
			$this->log_event( __METHOD__.": found ".trim((string) $name)." code = ".$client_code, EXT_ERROR_DEBUG);
		}
		return $client_code;
	}

	//! Add a detail line to the shipment in Exspeedite
	public function add_detail( $shipment_code, $detail ) {

		if( ! is_object($this->detail_table))
			$this->detail_table = sts_detail::getInstance($this->database, $this->debug);
		if( ! is_object($this->commodity_table))
			$this->commodity_table = sts_commodity::getInstance($this->database, $this->debug);
		if( $this->debug ) {
			echo "<p>".__METHOD__.": shipment=$shipment_code, detail=</p><pre>";
			var_dump($detail);
			echo "</pre>";
		}
		$check = $this->commodity_table->fetch_rows("COMMODITY_NAME = '".(string) $detail["204_REFERENCE"]."'",
			$this->commodity_table->primary_key);
		$is_new = is_array($check) && count($check) == 0;
		if( $is_new ) {
			$commodity_fields = array("COMMODITY_NAME" => (string) $detail["204_REFERENCE"]  );
			if( isset($detail["DESCRIPTION"]) ) $commodity_fields["COMMODITY_DESCRIPTION"] =
				trim((string) $detail["DESCRIPTION"]);
			if( isset($detail["PIECES_UNITS"]) ) $commodity_fields["PIECES_UNITS"] =
				intval($detail["PIECES_UNITS"]);
			$commodity_code = $this->commodity_table->add($commodity_fields);
			$this->log_event( __METHOD__.": added ".trim((string) $detail["204_REFERENCE"])." code = ".$commodity_code, EXT_ERROR_DEBUG);
		} else {
			$commodity_code = $check[0][$this->commodity_table->primary_key];
			$this->log_event( __METHOD__.": found ".trim((string) $detail["204_REFERENCE"])." code = ".$commodity_code, EXT_ERROR_DEBUG);
		}
		
		$detail_fields = array("SHIPMENT_CODE" => $shipment_code,
			"COMMODITY" => $commodity_code );
		if( isset($detail["204_REFERENCE"]) ) $detail_fields["204_REFERENCE"] =
			trim((string) $detail["204_REFERENCE"]);
		if( isset($detail["PIECES"]) ) $detail_fields["PIECES"] =
			trim((string) $detail["PIECES"]);
		if( isset($detail["PIECES_UNITS"]) ) $detail_fields["PIECES_UNITS"] =
			intval($detail["PIECES_UNITS"]);
		if( isset($detail["PALLETS"]) ) $detail_fields["PALLETS"] =
			trim((string) $detail["PALLETS"]);
		if( isset($detail["WEIGHT"]) ) $detail_fields["WEIGHT"] =
			trim((string) $detail["WEIGHT"]);
		if( isset($detail["PO_NUMBER"]) ) $detail_fields["PO_NUMBER"] =
			trim((string) $detail["PO_NUMBER"]);
			
		$detail_code = $this->detail_table->add($detail_fields);
		$this->log_event( __METHOD__.": added detail ".trim((string) $detail["204_REFERENCE"])." code = ".$detail_code, EXT_ERROR_DEBUG);
	}

	//! Add one shipment in Exspeedite
	public function add_shipment( $shipment ) {
		$this->log_event( __METHOD__.": entry", EXT_ERROR_DEBUG);
		if( ! is_object($this->shipment_table))
			$this->shipment_table = sts_shipment::getInstance($this->database, $this->debug);
		$shipment_code = false;
		//$check = $this->shipment_table->fetch_rows(
		//	"CONSOLIDATE_NUM = '".trim($shipment["CONSOLIDATE_NUM"])."' AND
		//	CURRENT_STATUS != ".$this->shipment_table->behavior_state["cancel"],
		//	$this->shipment_table->primary_key.", (SELECT BEHAVIOR from EXP_STATUS_CODES X WHERE X.STATUS_CODES_CODE = EXP_SHIPMENT.CURRENT_STATUS  LIMIT 0 , 1) AS CURRENT_STATUS" );
		//$is_new = is_array($check) && count($check) == 0;
		//if( $is_new ) {
			$billto_code = $this->add_client( trim($shipment["BILLTO_NAME"]),
				trim($shipment["BILLTO_ADDR1"]), 
				isset($shipment["BILLTO_ADDR2"]) ? trim($shipment["BILLTO_ADDR2"]) : NULL,
				trim($shipment["BILLTO_CITY"]), 
				trim($shipment["BILLTO_STATE"]),
				trim($shipment["BILLTO_ZIP"]), 
				isset($shipment["BILLTO_PHONE"]) ? trim($shipment["BILLTO_PHONE"]) : NULL,
				isset($shipment["BILLTO_FAX"]) ? trim($shipment["BILLTO_FAX"]) : NULL, 
				isset($shipment["BILLTO_EMAIL"]) ? trim($shipment["BILLTO_EMAIL"]) : NULL,
				isset($shipment["BILLTO_CONTACT"]) ? trim($shipment["BILLTO_CONTACT"]) : NULL,
				'BILLTO' );
				
			$cons_code = $this->add_client( trim($shipment["CONS_NAME"]),
				trim($shipment["CONS_ADDR1"]), 
				isset($shipment["CONS_ADDR2"]) ? trim($shipment["CONS_ADDR2"]) : NULL,
				trim($shipment["CONS_CITY"]), 
				trim($shipment["CONS_STATE"]),
				trim($shipment["CONS_ZIP"]), 
				isset($shipment["CONS_PHONE"]) ? trim($shipment["CONS_PHONE"]) : NULL,
				isset($shipment["CONS_FAX"]) ? trim($shipment["CONS_FAX"]) : NULL, 
				isset($shipment["CONS_EMAIL"]) ? trim($shipment["CONS_EMAIL"]) : NULL,
				isset($shipment["CONS_CONTACT"]) ? trim($shipment["CONS_CONTACT"]) : NULL,
				'CONS' );
			
			//$shipment_code = add_shipment( $shipment, $billto_code );
			$shipment["BILLTO_CLIENT_CODE"] = $billto_code;
			$details = $shipment["DETAIL"];	// Extract detail info
			unset($shipment["DETAIL"]);		// Remove from shipment fields
			$shipment_code = $this->shipment_table->add($shipment);

			$this->log_event( __METHOD__.": added shipment code = ".$shipment_code, EXT_ERROR_DEBUG);
			
			$this->log_event( __METHOD__.": details = ".count($details), EXT_ERROR_DEBUG);
			if( $this->debug ) {
				echo "<p>".__METHOD__.": details = ".count($details)."</p><pre>";
				var_dump($details);
				echo "</pre>";
			}
			foreach( $details as $detail ) {
				$this->add_detail( $shipment_code, $detail );
			}

			
		//} else if( isset($check[0]['CURRENT_STATUS']) && 
		//	! in_array($check[0]['CURRENT_STATUS'], array('approved', 'billed', 'cancel')) ) {
		//	$shipment_code = $check[0][$this->shipment_table->primary_key];
		//	//update_shipment( $shipment_code, $shipment );
		//}
		return $shipment_code;

	}
	
	//! Add one load
	public function add_load() {
		$load_fields = array( 'CURRENT_STATUS' => $this->load_table->behavior_state["imported"],
			'CURRENT_STOP' => 0 );
		if( ! is_object($this->load_table))
			$this->load_table = sts_load::getInstance($this->database, $this->debug);
		$load_code = $this->load_table->add($load_fields);
		$this->log_event( __METHOD__.": create load_code = ".($load_code ? $load_code : 'false'), EXT_ERROR_DEBUG);
		return $load_code;
	}

	//! Add one stop
	public function add_stop( $stop_fields ) {
		if( ! is_object($this->stop_table))
			$this->stop_table = sts_stop::getInstance($this->database, $this->debug);
		$stop_code = $this->stop_table->add($stop_fields);
		$this->log_event( __METHOD__.": create stop_code = ".($stop_code ? $stop_code : 'false'), EXT_ERROR_DEBUG);
		return $stop_code;
	}

	//! Cancel 204 and all related records
	// $sid = unique identifier in exp_shipment.EDI_204_B204_SID
	public function cancel( $sid, $cancel_type = 'Cancelled' ) {
		$this->log_event( __METHOD__.": B204 sid=$sid", EXT_ERROR_DEBUG);
		$result = false;

		if( ! is_object($this->load_table))
			$this->load_table = sts_load::getInstance($this->database, $this->debug);
		if( ! is_object($this->shipment_table))
			$this->shipment_table = sts_shipment::getInstance($this->database, $this->debug);
		
		// Verify we have this, and get the EDI_204_PRIMARY
		// Order by SHIPMENT_CODE DESC, to get the most recent with the sid
		$check = $this->shipment_table->fetch_rows("EDI_204_B204_SID = '".$sid."'
			AND CURRENT_STATUS != ".$this->shipment_table->behavior_state["cancel"],
			"SHIPMENT_CODE, EDI_204_PRIMARY, CURRENT_STATUS", "SHIPMENT_CODE DESC");
		if( $this->debug ) {
			echo "<p>".__METHOD__.": check = </p><pre>";
			var_dump($check);
			echo "</pre>";
		}
		
		if( is_array($check) && count($check) > 0 &&
			! empty($check[0]["EDI_204_PRIMARY"]) &&
			! empty($check[0]["CURRENT_STATUS"]) &&
			in_array($check[0]["CURRENT_STATUS"], 
				array( $this->shipment_table->behavior_state["imported"],
					$this->shipment_table->behavior_state["assign"],
					$this->shipment_table->behavior_state["dispatch"] ))) {
			$shipment_code = $check[0]["SHIPMENT_CODE"];
			$primary = $check[0]["EDI_204_PRIMARY"];
			$current_status = $check[0]["CURRENT_STATUS"];
			$this->log_event( __METHOD__.": found shipment=$shipment_code, primary=$primary, status=".$this->shipment_table->state_name[$current_status], EXT_ERROR_DEBUG);
			
			// Set 990 status to cancelled, to avoid sending a 990
			$this->shipment_table->update("EDI_204_PRIMARY = ".$primary,
					array( "EDI_990_STATUS" => $cancel_type), false );

			// Is there a load as well?
			$check2 = $this->load_table->fetch_rows("EDI_204_PRIMARY = ".$primary.
				" AND CURRENT_STATUS != ".$this->load_table->behavior_state["cancel"],
				"LOAD_CODE" );
			if( is_array($check2) && count($check2) > 0 && ! empty($check2[0]["LOAD_CODE"])) {
				$load_code = $check2[0]["LOAD_CODE"];
				
				$this->load_table->change_state( $load_code, $this->load_table->behavior_state["cancel"] );
				$this->load_table->add_load_status( $load_code, 'EDI '.$sid.' '.$cancel_type );
			}

			// Cancel related shipments
			$this->shipment_table->update( 'EDI_204_PRIMARY = '.$primary,
				array( "CURRENT_STATUS" =>
				$this->shipment_table->behavior_state["cancel"] ), false );

			$result = $primary;
		}
		
		return $result;
		
	}
		
}

?>
