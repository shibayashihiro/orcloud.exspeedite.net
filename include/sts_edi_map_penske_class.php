<?php

// This is the EDI mapping class, it maps 204s into Exspeedite tables

// no direct access
defined('_STS_INCLUDE') or die('Restricted access');
require_once( dirname(__FILE__) ."/sts_edi_map_class.php" );

class sts_edi_map_penske extends sts_edi_map {
	private $PENSKE_id = 'PENSKE';

	private $edi_214_stop_event = array(
		'Schedule Pickup'	=> 'AA',
		'Schedule Delivery'	=> 'AB',
		'Arrive Shipper'	=> 'X3',
		'Depart Shipper'	=> 'AF',
		'Arrive Consignee'	=> 'X1',
		'Depart Consignee'	=> 'D1',
		'Estimated Delivery' => 'AG'	// Used for mandatory Delivery ETA 214
	);

	public $edi_214_event_status = array(
		'Accident'									=> 'AF',
		'Consignee Related'							=> 'AG',
		'Driver Related'							=> 'AH',
		'Mechanical Breakdown'						=> 'AI',
		'Previous Stop'								=> 'AL',
		'Shipper Related'							=> 'AM',
		'Weather or Natural Disaster Related'		=> 'AO',
		'Border Clearance'							=> 'BD',
		'Insufficient Time to Complete Delivery'	=> 'BH',
		'Railroad Failed to Meet Schedule'			=> 'BO',
		'Carrier Dispatch Error'					=> 'D1',
		'Normal Status'								=> 'NS',
	);
	
	// These are the codes used to determine it is truckload
	private $edi_PENSKE_TL_codes = array('SPEX','SPTL','TL','TLB');
	
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
    
    public function map_214_stop_event( $event ) {
	    return $this->edi_214_stop_event[$event];
    }
    
    public function map_214_event_status( $status ) {
	    return $this->edi_214_event_status[$status];
    }
    
	//! Extract needed information from the heading of the PENSKE 204 EDI
	public function map_penske_204_heading( $notdef, $heading ) {
		$heading_fields = array();
		
		if( ! $notdef )
			throw new Exception('map_penske_204_heading: missing Not Defined');
			
		//! Usage
		$isa = $this->lookup_edi_path( $notdef, 'ISA' );
		if( $isa && isset($isa["ISA15"]) ) {
			$heading_fields["EDI_204_ISA15_USAGE"] = $this->get_field_value( "ISA15", $isa["ISA15"] );
		} else
			throw new Exception(__METHOD__.': missing ISA/ISA15');

		//!
		
		$fgroup = $this->lookup_edi_path( $notdef, 'GS' );
		if( $fgroup ) {
			$fgroup_id = $this->lookup_edi_path( $fgroup, 'GS01' );
			if( $fgroup_id <> EDI_204 )
				throw new Exception(__METHOD__.': this is not a 204 ('.
				($fgroup_id ? 'GS01='.$fgroup_id.'/'.$this->get_field_value( "GS01", $fgroup_id ) : 'GS01 missing').
				', should be '.EDI_204.')');
			$fgroup_date = $this->lookup_edi_path( $fgroup, 'GS04' );
			$fgroup_time = $this->lookup_edi_path( $fgroup, 'GS05' );
			if( ! $fgroup_date )
				throw new Exception(__METHOD__.': missing date GS/GS04');
			if( ! $fgroup_time )
				throw new Exception(__METHOD__.': missing time GS/GS05');

			$heading_fields["EDI_204_GS04_OFFERED"] = substr($fgroup_date,0,4).'-'.
				substr($fgroup_date,4,2).'-'.substr($fgroup_date,6,2).' '.
				substr($fgroup_time,0,2).':'.substr($fgroup_time,2,2).':00';
		}

		if( ! $heading )
			throw new Exception(__METHOD__.': missing Heading');
		
		//! Shipment Information
		$shipment_info = $this->lookup_edi_path( $heading, 'B2' );
		if( $shipment_info && isset($shipment_info["B204"]) ) {
			$heading_fields["EDI_204_B204_SID"] = $shipment_info["B204"];
		} else
			throw new Exception(__METHOD__.': missing B2/B204');
		if( $shipment_info && isset($shipment_info["B206"]) ) {
			$heading_fields["EDI_204_B206_PAYMENT"] =
				$this->get_field_value( "B206", $shipment_info["B206"] );
		} else
			throw new Exception(__METHOD__.': missing B2/B206');

		$purpose = $this->lookup_edi_path( $heading, 'B2A' );
		if( $purpose && isset($purpose["B2A01"]) ) {
			$heading_fields["EDI_204_B2A01_TRANS"] = $this->get_field_value( "B2A01", $purpose["B2A01"] );
		} else
			throw new Exception(__METHOD__.': missing B2A/B2A01');
		//if( $purpose && isset($purpose["B2A02"]) ) {
		//	$heading_fields["204_TYPE"] = $this->get_field_value( "B2A02", $purpose["B2A02"] );
		//}

		//! Expiry date
		$expires = $this->lookup_edi_path( $heading, 'G62' );
		if( $expires ) {
			$expires_date_qualifier = $this->lookup_edi_path( $expires, 'G6201' );
			$expires_date = $this->lookup_edi_path( $expires, 'G6202' );
			$expires_time_qualifier = $this->lookup_edi_path( $expires, 'G6203' );
			$expires_time = $this->lookup_edi_path( $expires, 'G6204' );
			if( ! $expires_date_qualifier || $expires_date_qualifier <> '64' )
				throw new Exception(__METHOD__.': missing date qualifier or <> 64 G62/G6201');
			if( ! $expires_time_qualifier || $expires_time_qualifier <> '1' )
				throw new Exception(__METHOD__.': missing time qualifier or <> 1 G62/G6203');
			if( ! $expires_date )
				throw new Exception(__METHOD__.': missing date G62/G6202');
			if( ! $expires_time )
				throw new Exception(__METHOD__.': missing time G62/G6204');

			$heading_fields["EDI_204_G6202_EXPIRES"] = substr($expires_date,0,4).'-'.
				substr($expires_date,4,2).'-'.substr($expires_date,6,2).' '.
				substr($expires_time,0,2).':'.substr($expires_time,2,2).':00';

			//! Don't bother to go further if the 204 is expired.
			if( strtotime($heading_fields["EDI_204_G6202_EXPIRES"]) < time() )
				throw new Exception(__METHOD__.': This 204 has already expired '.
					$heading_fields["EDI_204_G6202_EXPIRES"]);
		}
		
		$payment = $this->lookup_edi_path( $heading, 'B2/B206' );
		$payment_mapping = array(
			false => 'prepaid',	// If not found
			'PP' => 'prepaid',
			'CC' => 'collect',
			'TP' => 'third party', //'Third Party',
			'DF' => 'other', //'Defined by Buyer & Seller',
		);
		$heading_fields["SHIPMENT_TYPE"] = $payment_mapping[$payment];
		
		//! Bill to name
		$name = $this->lookup_edi_path( $heading, 'loop100/N1/N102' );
		if( $name )
			$heading_fields["BILLTO_NAME"] = $name;
		else
			throw new Exception(__METHOD__.': missing BILLTO_NAME loop100/N1/N102');

		//! Bill to address
		$addr1 = $this->lookup_edi_path( $heading, 'loop100/N3/N301' );
		if( $addr1 )
			$heading_fields["BILLTO_ADDR1"] = $addr1;
		else
			throw new Exception(__METHOD__.': missing BILLTO_ADDR1 loop100/N3/N301');

		$addr2 = $this->lookup_edi_path( $heading, 'loop100/N3/N302' );
		if( $addr2 )
			$heading_fields["BILLTO_ADDR2"] = $addr2;
		$city = $this->lookup_edi_path( $heading, 'loop100/N4/N401' );
		if( $city )
			$heading_fields["BILLTO_CITY"] = $city;
		else
			throw new Exception(__METHOD__.': missing BILLTO_CITY loop100/N4/N401');
		$state = $this->lookup_edi_path( $heading, 'loop100/N4/N402' );
		if( $state )
			$heading_fields["BILLTO_STATE"] = $state;
		else
			throw new Exception(__METHOD__.': missing BILLTO_STATE loop100/N4/N402');
		$zip = $this->lookup_edi_path( $heading, 'loop100/N4/N403' );
		if( $zip )
			$heading_fields["BILLTO_ZIP"] = $zip;
		else
			throw new Exception(__METHOD__.': missing BILLTO_ZIP loop100/N4/N403');

		//! Bill to contact info
		$contacts = $this->lookup_edi_path( $heading, 'loop100/G61', EXPECT_MULTIPLE );
		if( is_array($contacts) && ! empty($contacts) ) {
			foreach( $contacts as $contact ) {
				if( isset($contact['G6102']) )
					$heading_fields["BILLTO_CONTACT"] = $contact['G6102'];
				else
					throw new Exception(__METHOD__.': missing BILLTO_CONTACT G6102');
		
				$ctype = $contact['G6103'];
				if( $ctype == 'TE' && isset($contact['G6104']) ){
					$heading_fields["BILLTO_PHONE"] = $contact['G6104'];
				} else if( $ctype == 'FX' && isset($contact['G6104']) ){
					$heading_fields["BILLTO_FAX"] = $contact['G6104'];
				} else if( $ctype == 'EM' && isset($contact['G6104']) ){
					$heading_fields["BILLTO_EMAIL"] = $contact['G6104'];
				}
			}
		}
		
		//! Combine multiple NTE fields to the notes
		$notes = $this->lookup_edi_path( $heading, 'NTE', EXPECT_MULTIPLE );
		if( is_array($notes) && ! empty($notes) ) {
			$note = array();
			foreach( $notes as $row ) {
				if( isset($row["NTE01"]) && $row["NTE01"] == 'BOL' && ! empty($row["NTE02"]) )
					$note[] = $row["NTE02"];
				if( isset($row["NTE01"]) &&
					in_array( $row["NTE01"], array('CAH', 'ZZZ')) &&
					! empty($row["NTE02"]) && is_numeric($row["NTE02"]) )
					$heading_fields["EDI_204_NTE_MILES"] = floatval($row["NTE02"]);
			}
			$heading_fields["NOTES"] = implode("\n", $note);
		}
		
		//! Shipment numbers
		$numbers = $this->lookup_edi_path( $heading, 'L11', EXPECT_MULTIPLE );
		foreach( $numbers as $row ) {
			if( isset($row["L1101"]) && isset($row["L1102"]) ) {
				if( $row["L1102"] == 'SI' )
					$heading_fields["REF_NUMBER"] = $row["L1101"];
				else if( $row["L1102"] == 'BM' )
					$heading_fields["BOL_NUMBER"] = $row["L1101"];
				if( $row["L1102"] == '11' )
					$heading_fields["EDI_204_L1101_ACCOUNT"] = $row["L1101"];
				if( $row["L1102"] == 'QY' )
					$heading_fields["EDI_204_L1101_SERVICE"] = $row["L1101"];
			}
		}

		return $heading_fields;
	}
	
	private function map_penske_details( $stop ) {
		$detail_lines = array();
		
		// For Penske, use the S5 totals and treat it as one detail line
		$s5 = $this->lookup_edi_path( $stop, 'S5' );
		
		$loop320 = $this->lookup_edi_path( $stop, 'loop320', EXPECT_MULTIPLE );
		if( $loop320 ) {
			foreach( $loop320 as $item ) {
				
				$loop350s = $this->lookup_edi_path( $item, 'loop350', EXPECT_MULTIPLE );
				$count350s = count($loop350s);
				if( $this->debug ) echo "<p>".__METHOD__.": loop320  loop350=$count350s</p>";

				foreach( $loop350s as $loop350) {
					$oid = $this->lookup_edi_path( $loop350, 'OID' );
					$detail_fields = array();

					if( $oid && isset($oid['OID01']) ) {
						$detail_fields["204_REFERENCE"] = $oid['OID01'];
					}
					if( $oid && isset($oid['OID02']) ) {
						$detail_fields["PO_NUMBER"] = $oid['OID02'];
					}
					if( $s5 && isset($s5['S506']) ) {
						$unit = $this->get_field_value( "S506", $s5['S506'] );
						$detail_fields["PIECES_UNITS"] = $this->lookup_item_units( $unit );
					}
					// $count350s == 1 means total count == detail count
					if( $s5 && isset($s5['S505']) && $count350s == 1 ) {
						$detail_fields["PIECES"] = intval($s5['S505']);
					} else {
						$detail_fields["PIECES"] = 1; //! Set PIECES to 1 if we don't have it.
					}
					if( $s5 && isset($s5['S503']) && $count350s == 1 ) {
						$detail_fields["WEIGHT"] = intval($s5['S503']);
					}
					if( count($detail_fields) > 0 )
						$detail_lines[] = $detail_fields;
				}
			}
		}
		if( $this->debug ) {
			if( is_array($detail_lines) && count($detail_lines) > 0 ) {
				echo "<h4>".__METHOD__.": details for stop ".$this->lookup_edi_path( $stop, 'S5/S501' )."</h4><ul>";
				$sum = 0;
				foreach($detail_lines as $row ){
					echo "<li>".$row["204_REFERENCE"]." x ".$row["PIECES"]."</li>";
					$sum += $row["PIECES"];
				}
				echo "Total items: $sum</ul>";
			}
		}

		return $detail_lines;
	}
	
	//! Get pickup/delivery dates
	private function map_penske_204_dates( $stop, $prefix, &$fields ) {

		$dt_prefix = $prefix == 'SHIPPER' ? 'PICKUP' : 'DELIVER';
		$datetimes = $this->lookup_edi_path( $stop, 'G62', EXPECT_MULTIPLE );
		if( count($datetimes) > 2 )
			throw new Exception(__METHOD__.': loop300 contains more than 2 G62 segments ('.count($datetimes).' found)');
			
		// Just validation
		foreach( $datetimes as $datetime ) {
			if( ! isset($datetime['G6201'] ) )
				throw new Exception(__METHOD__.': missing date qualifier G62/G6201');
			if( ! isset($datetime['G6202'] ) )
				throw new Exception(__METHOD__.': missing date G62/G6202');
			if( ! isset($datetime['G6203'] ) )
				throw new Exception(__METHOD__.': missing time qualifier G62/G6203');
			if( ! isset($datetime['G6204'] ) )
				throw new Exception(__METHOD__.': missing time G62/G6204');
		}
		
		// Handle cases that map to 'Between'
		if( count($datetimes) == 2 && $dt_prefix == 'PICKUP' &&
			$datetimes[0]['G6201'] == '37' && $datetimes[0]['G6203'] == 'I' &&
			$datetimes[1]['G6201'] == '38' && $datetimes[1]['G6203'] == 'K' ) {

			$fields[$dt_prefix."_DATE"] = substr($datetimes[0]['G6202'],0,4).'-'.
				substr($datetimes[0]['G6202'],4,2).'-'.
				substr($datetimes[0]['G6202'],6,2);
			$fields[$dt_prefix."_TIME_OPTION"] = "Between";
			$fields[$dt_prefix."_TIME1"] = $datetimes[0]['G6204'];
			if( $datetimes[0]['G6202'] <> $datetimes[1]['G6202'] )	// If date2 different
				$fields[$dt_prefix."_DATE2"] = substr($datetimes[1]['G6202'],0,4).'-'.
					substr($datetimes[1]['G6202'],4,2).'-'.
					substr($datetimes[1]['G6202'],6,2);
			$fields[$dt_prefix."_TIME2"] = $datetimes[1]['G6204'];
		} else
		if( count($datetimes) == 2 && $dt_prefix == 'DELIVER' &&
			$datetimes[0]['G6201'] == '53' && $datetimes[0]['G6203'] == 'G' &&
			$datetimes[1]['G6201'] == '54' && $datetimes[1]['G6203'] == 'L' ) {

			$fields[$dt_prefix."_DATE"] = substr($datetimes[0]['G6202'],0,4).'-'.
				substr($datetimes[0]['G6202'],4,2).'-'.
				substr($datetimes[0]['G6202'],6,2);
			$fields[$dt_prefix."_TIME_OPTION"] = "Between";
			$fields[$dt_prefix."_TIME1"] = $datetimes[0]['G6204'];
			if( $datetimes[0]['G6202'] <> $datetimes[1]['G6202'] )	// If date2 different
				$fields[$dt_prefix."_DATE2"] = substr($datetimes[1]['G6202'],0,4).'-'.
					substr($datetimes[1]['G6202'],4,2).'-'.
					substr($datetimes[1]['G6202'],6,2);
			$fields[$dt_prefix."_TIME2"] = $datetimes[1]['G6204'];
		} else
		// Handle single qualifiers
		if( count($datetimes) == 1 && (
			($datetimes[0]['G6201'] == '10' && $datetimes[0]['G6203'] == 'Y') ||
			($datetimes[0]['G6201'] == '68' && $datetimes[0]['G6203'] == 'Z') ||
			($datetimes[0]['G6201'] == '69' && $datetimes[0]['G6203'] == 'U') ||
			($datetimes[0]['G6201'] == '70' && $datetimes[0]['G6203'] == 'X') ) ) {
			$fields[$dt_prefix."_DATE"] = substr($datetimes[0]['G6202'],0,4).'-'.
				substr($datetimes[0]['G6202'],4,2).'-'.
				substr($datetimes[0]['G6202'],6,2);
			$fields[$dt_prefix."_TIME_OPTION"] = "At";
			$fields[$dt_prefix."_TIME1"] = $datetimes[0]['G6204'];
		} else {
			throw new Exception(__METHOD__.': G62 unrecognized qualifiers G6201='.$datetime['G6201'].' G6203='.$datetime['G6203'] );
		}
	}
	
	//! Extract information for a given stop
	private function map_penske_204_stop( $stop, $prefix, &$fields ) {
		if( $this->debug ) echo "<p>".__METHOD__.": stop=".$this->lookup_edi_path( $stop, 'S5/S501' ).", prefix=$prefix.</p>";
		//! Shipper/Consignee name
		$name = $this->lookup_edi_path( $stop, 'loop310/N1/N102' );
		if( $name )
			$fields[$prefix."_NAME"] = $name;
		else
			throw new Exception(__METHOD__.': missing '.$prefix."_NAME".' loop310/N1/N102');
		
		//! Shipper/Consignee address
		$line1 = $this->lookup_edi_path( $stop, 'loop310/N3/N301' );
		$line2 = $this->lookup_edi_path( $stop, 'loop310/N3/N302' );
		$geog = $this->lookup_edi_path( $stop, 'loop310/N4' );
		$city = isset($geog["N401"]) ? $geog["N401"] : false;
		$state = isset($geog["N402"]) ? $geog["N402"] : false;
		$zip = isset($geog["N403"]) ? $geog["N403"] : false;
		$country = isset($geog["N404"]) ? $geog["N404"] : false;
		
		if( $line1 )
			$fields[$prefix."_ADDR1"] = $line1;
		else
			throw new Exception(__METHOD__.': missing '.$prefix."_ADDR1".' loop310/N3/N301');
		
		if( $line2 )
			$fields[$prefix."_ADDR2"] = $line2;
		if( $city )
			$fields[$prefix."_CITY"] = $city;
		else
			throw new Exception(__METHOD__.': missing '.$prefix."_CITY".' loop310/N4/N401');
		if( $state )
			$fields[$prefix."_STATE"] = $state;
		else
			throw new Exception(__METHOD__.': missing '.$prefix."_STATE".' loop310/N4/N402');
		if( $zip )
			$fields[$prefix."_ZIP"] = $zip;
		else
			throw new Exception(__METHOD__.': missing '.$prefix."_ZIP".' loop310/N4/N403');
			
		//! Shipper/Consignee contact
		$contacts = $this->lookup_edi_path( $stop, 'loop310/G61', EXPECT_MULTIPLE );
		if( is_array($contacts) && ! empty($contacts) ) {
			foreach( $contacts as $contact ) {
				if( isset($contact['G6102']) )
					$fields[$prefix."_CONTACT"] = $contact['G6102'];
				else
					throw new Exception(__METHOD__.': missing '.$prefix."_CONTACT".' loop310/G61/G6102');
		
				$ctype = $contact['G6103'];
				if( $ctype == 'TE' && isset($contact['G6104']) ){
					$fields[$prefix."_PHONE"] = $contact['G6104'];
				} else if( $ctype == 'FX' && isset($contact['G6104']) ){
					$fields[$prefix."_FAX"] = $contact['G6104'];
				} else if( $ctype == 'EM' && isset($contact['G6104']) ){
					$fields[$prefix."_EMAIL"] = $contact['G6104'];
				}
			}
		}

		//! Pickup and delivery dates & times
		$this->map_penske_204_dates( $stop, $prefix, $fields );
		
		//! Combine multiple NTE fields to the notes
		$notes = $this->lookup_edi_path( $stop, 'NTE', EXPECT_MULTIPLE );
		if( $this->debug ) {
			echo "<pre>";
			var_dump($notes);
			echo "</pre>";
		}
		if( is_array($notes) && ! empty($notes) ) {
			$note = array();
			foreach( $notes as $row ) {
				if( isset($row["NTE01"]) && $row["NTE01"] == 'BOL' && ! empty($row["NTE02"]) )
					$note[] = $row["NTE02"];
			}
			$fields["NOTES"] = implode("\n", $note);
		}

		if( $prefix == 'SHIPPER') {
			$detail_lines = $this->map_penske_details( $stop );
		
			//! Weight
			$at8 = $this->lookup_edi_path( $stop, 'AT8' );
			if( $at8 && isset($at8['AT803']) ) {
				$fields["WEIGHT"] = $at8['AT803'];
			}
			
			$s5 = $this->lookup_edi_path( $stop, 'S5' );
			if( $s5 && isset($s5['S506']) ) {
				$unit = $this->get_field_value( "S506", $s5['S506'] );
				$fields["PIECES_UNITS"] = $this->lookup_item_units( $unit );
			}
			if( $s5 && isset($s5['S505']) ) {
				$fields["PIECES"] = intval($s5['S505']);
			}

			
			$fields["DETAIL"] = $detail_lines;
		}
	}
	
	//! Extract information for a given shipment
	// $detail - the detail section of the 204 EDI
	private function map_penske_204_shipment( $detail ) {
		$shipments = array();
		$stops = array();
		$stop_type_is_load = array(		// load (pick) vs unload (drop)
			'CL' => true,
			'CU' => false,
			'LD' => true,
			'PL' => true,
			'PU' => false,
			'UL' => false
		);
		
		//! Process info for each stop
		$loop300_stops = $this->lookup_edi_path( $detail, 'loop300', EXPECT_MULTIPLE );
		if( ! is_array($loop300_stops) )
			throw new Exception(__METHOD__.': missing stops loop300');
		foreach( $loop300_stops as $stop) {
			$stop_fields = array();
			$stop_number = $this->lookup_edi_path( $stop, 'S5/S501' );
			if( ! $stop_number )
				throw new Exception(__METHOD__.': missing stop_number loop300/S5/S501');
			$stop_fields["SEQUENCE_NO"] = $stop_number;
			$stop_fields["EDI_204_S501_STOP"] = $stop_number;
			$stop_type = $this->lookup_edi_path( $stop, 'S5/S502' );
			if( $stop_type )
				$stop_is_a_load = $stop_type_is_load[$stop_type];
			else
				throw new Exception(__METHOD__.': missing loop300/S5/S502');
			$name = $this->lookup_edi_path( $stop, 'loop310/N1/N102' );
			if( $this->debug ) echo "<h3>".__METHOD__.": stop $stop_number $stop_type @ $name</h3>";
			$this->log_event( __METHOD__.": stop $stop_number $stop_type.", EXT_ERROR_DEBUG);
			
			$stop_fields["STOP_TYPE"] = $stop_is_a_load ? 'pick' : 'drop';
			$prefix = $stop_is_a_load ? 'SHIPPER' : 'CONS';
			
			if( $stop_is_a_load ) {	//! Create a new shipment based on this stop
				if( $this->debug ) echo "<p>".__METHOD__.": Create a new shipment based on this stop.</p>";
				$this->log_event( __METHOD__.": Create a new shipment based on this stop $stop_number.", EXT_ERROR_NOTICE);
				$shipment_fields = array();
				
				$this->map_penske_204_stop( $stop, $prefix, $shipment_fields );
				// more fields tbd
				$shipments[] = $shipment_fields;
				$stop_fields["SHIPMENT"] = count($shipments)-1;
				$stops[] = $stop_fields;
			} else { //! This is an unload, look for a matching load/shipment
				$details = $this->map_penske_details( $stop ); // Details for this stop

				while( $this->got_details_left( $details ) &&
					$this->got_shipments_left( $shipments ) ) {
					$found_match = MATCH_NONE;
					for( $c=0; $c<count($stops); $c++ ) {	// Check each previous stop
						if( $stops[$c]["STOP_TYPE"] == 'pick') {
							$shipment_number = $stops[$c]["SHIPMENT"];
							if( ! isset($shipments[$shipment_number]["DROPPED"]) ) {
								$found_match = $this->match_commodities( $details, $shipments[$shipment_number]["DETAIL"] );
								if( $found_match <> MATCH_NONE ) {
									$pick_stop_number = $c;
									break;
								}
							}
								
						}
					}
					if( $found_match == MATCH_EXACT ) {	// assign drop stop to shipment
						if( $this->debug ) echo "<p>".__METHOD__.": Exact match, assign drop stop to shipment $shipment_number</p>";
						$this->log_event( __METHOD__.": Exact match, assign drop stop $stop_number to shipment $shipment_number", EXT_ERROR_NOTICE);
						// Update the CONS fields etc.
						$this->map_penske_204_stop( $stop, $prefix, $shipments[$shipment_number] );
						
						// Update this stop to point to this shipment
						$stop_fields["SHIPMENT"] = $shipment_number;
						$stops[] = $stop_fields;
						$shipments[$shipment_number]["DROPPED"] = true;
						break;
	
					} else if( $found_match == MATCH_SUBSET ) { // divide shipment
						if( $this->debug ) echo "<p>".__METHOD__.": Details match subset, divide shipment $shipment_number</p>";
						$this->log_event( __METHOD__.": Details match subset, divide shipment $shipment_number", EXT_ERROR_NOTICE);
						$new_shipment = $shipments[$shipment_number];
						$this->subtract_commodities( $details, $new_shipment["DETAIL"] );
						$shipments[] = $new_shipment;
						$new_stop = $stops[$pick_stop_number];	// Copy stop
						$new_stop["SHIPMENT"] = count($shipments)-1;
						array_splice($stops, $pick_stop_number+1, 0, array($new_stop));
						if( $this->debug ) echo "<p>".__METHOD__.": assign drop stop to shipment ".(count($shipments)-1)."</p>";
						
						unset($shipments[$shipment_number]["DETAIL"]);	// update details
						$shipments[$shipment_number]["DETAIL"] = $details;
	
						// Update the CONS fields etc.
						$this->map_penske_204_stop( $stop, $prefix, $shipments[$shipment_number] );
	
						$stop_fields["SHIPMENT"] = $shipment_number;
						$stops[] = $stop_fields;
						$shipments[$shipment_number]["DROPPED"] = true;
						break;
						
					}  else if( $found_match == MATCH_SUPERSET ) { // add multiple drop stops
						if( $this->debug ) echo "<p>".__METHOD__.": Details match superset, add multiple drop stops</p>";
						$this->log_event( __METHOD__.": Details match superset, add multiple drop stops", EXT_ERROR_NOTICE);
						// Update the CONS fields etc.
						$this->map_penske_204_stop( $stop, $prefix, $shipments[$shipment_number] );
						
						$this->subtract_commodities( $shipments[$shipment_number]["DETAIL"], $details );
						$shipments[$shipment_number]["DROPPED"] = true;
						// Update this stop to point to this shipment
						$stop_fields["SHIPMENT"] = $shipment_number;
						$stops[] = $stop_fields;
						if( $this->debug ) echo "<p>".__METHOD__.": assign drop stop to shipment $shipment_number</p>";
						$this->log_event( __METHOD__.": assign drop stop to shipment $shipment_number", EXT_ERROR_NOTICE);
						
					} else {
						if( $this->debug ) echo "<p>".__METHOD__.": No matching details!</p>";
						$this->log_event( __METHOD__.": Stop $stop_number has no matching details!", EXT_ERROR_ERROR);
						break;
					}
				}
			}
		}
		if( $this->got_shipments_left( $shipments ) )
			throw new Exception(__METHOD__.': still have shipments remaining');
			
		// Renumber stops, in case we added any extras
		$seq = 1;
		for( $c=0; $c<count($stops); $c++ ) {
			$stops[$c]["SEQUENCE_NO"] = $seq++;
		}
		//$this->log_event( __METHOD__.": stops:\n".print_r($stops, true), EXT_ERROR_DEBUG);
		for( $c=0; $c<count($shipments); $c++ ) {
			unset($shipments[$c]["DROPPED"]);
		}
		return array($shipments, $stops);
	}
	
	private function map_penske_204_summary( $summary ) {
		$summary_fields = array();
		
		if( ! $summary )
			throw new Exception('map_penske_204_summary: missing Summary');

		$totals = $this->lookup_edi_path( $summary, 'L3' );
		if( $totals && isset($totals["L301"]) ) {
			$summary_fields["EDI_204_L301_WEIGHT"] = intval($totals["L301"]);
		}
		if( $totals && isset($totals["L303"]) ) {
			$summary_fields["EDI_204_L303_RATE"] = intval($totals["L303"]);
		}
		if( $totals && isset($totals["L304"]) ) {
			$summary_fields["EDI_204_L304_RQUAL"] = $this->get_field_value( "L304", $totals["L304"] );
		}
		if( $totals && isset($totals["L305"]) ) {
			$summary_fields["EDI_204_L305_CHARGE"] = intval($totals["L305"]);
		}
		if( $totals && isset($totals["L311"]) ) {
			$summary_fields["PIECES"] = $totals["L311"];
		}
		if( $totals && isset($totals["L312"]) ) {
			$summary_fields["EDI_204_L312_WQUAL"] = $this->get_field_value( "L312", $totals["L312"] );
		}
		
		return $summary_fields;
	}

	//! Take a PENSKE 204 and work out shipments, stops etc.
	public function map_204( $client, $notdef, $edi_204 ) {
		$result = false;
		//! Initialize DB tables prior to importing 204s
		if( ! is_object($this->unit_table))
			$this->unit_table = sts_unit::getInstance($this->database, $this->debug);
		if( ! is_object($this->stop_table))
			$this->stop_table = sts_stop::getInstance($this->database, $this->debug);
		if( ! is_object($this->load_table))
			$this->load_table = sts_load::getInstance($this->database, $this->debug);
		if( ! is_object($this->shipment_table))
			$this->shipment_table = sts_shipment::getInstance($this->database, $this->debug);
		if( ! is_object($this->detail_table))
			$this->detail_table = sts_detail::getInstance($this->database, $this->debug);
		if( ! is_object($this->commodity_table))
			$this->commodity_table = sts_commodity::getInstance($this->database, $this->debug);
		if( ! is_object($this->client_table))
			$this->client_table = sts_client::getInstance($this->database, $this->debug);
		if( ! is_object($this->contact_info_table))
			$this->contact_info_table = sts_contact_info::getInstance($this->database, $this->debug);

		$heading_fields = $this->map_penske_204_heading( $notdef, $this->lookup_edi_path( $edi_204, 'Heading' ) );
		
		list($shipments, $stops) = $this->map_penske_204_shipment( $this->lookup_edi_path( $edi_204, 'Detail') );

		$summary_fields = $this->map_penske_204_summary( $this->lookup_edi_path( $edi_204, 'Summary' ) );

		if( $this->debug ) {
		echo "<hr><h3>Heading fields</h3>
		<pre>";
		var_dump($heading_fields);
		echo "</pre>";
		echo "</pre>";
		echo "<hr><h3>Shipments</h3>
		<pre>";
		var_dump($shipments);
		echo "</pre>";
		echo "<hr><h3>Stops</h3>
		<pre>";
		var_dump($stops);
		echo "<hr><h3>Summary fields</h3>
		<pre>";
		var_dump($summary_fields);
		echo "</pre>";
		}
		
		//! We do not support B2A01 in ('Add', 'Delete', 'Change')
		if( in_array($heading_fields["EDI_204_B2A01_TRANS"], array( 'Add', 'Delete', 'Change' ) ) ) {
			throw new Exception(__METHOD__.': unsupported B2A01='.$heading_fields["EDI_204_B2A01_TRANS"]);
		} else

		//! Deal with cancel 204
		if( $heading_fields["EDI_204_B2A01_TRANS"] == 'Cancellation' ) {
			$this->log_event( __METHOD__.": B204 EDI_204_B2A01_TRANS=".$heading_fields["EDI_204_B2A01_TRANS"], EXT_ERROR_DEBUG);
			// look for $heading_fields["EDI_204_B204_SID"] and cancel
			$result = $this->cancel( $heading_fields["EDI_204_B204_SID"], 'Cancelled' );
			
			//! Better report the cancel 204
			$email = sts_email::getInstance($this->database, $this->debug);
			$email->send_alert(__METHOD__.": Got a Cancellation 204 from $client sid=".$heading_fields["EDI_204_B204_SID"]." result=".($result ? 'True' : 'False') );
		} else
		
		//! Deal with Replacement 204
		if( $heading_fields["EDI_204_B2A01_TRANS"] == 'Replacement' ) {
			$sid = $heading_fields["EDI_204_B204_SID"];
			$result = $this->handle_PENSKE_replacement_204( $sid, $shipments );
		} else
		
		//! Just one shipment, do not create stops and a load
		if( ! in_array($heading_fields["EDI_204_L1101_SERVICE"], $this->edi_PENSKE_TL_codes)  
			&& count($stops) == 2 && count($shipments) == 1 ) {
			$shipment = $shipments[0];
			$this->merge_shipment_fields( $heading_fields,  $summary_fields, $shipment );
			
			$shipment["CURRENT_STATUS"] = $this->shipment_table->behavior_state["imported"];
			$shipment["EDI_990_STATUS"] = 'Pending';
			$shipment["EDI_204_ORIGIN"] = $client;
			
			// Kluge - populate the detail weight if we can.
			if( isset($shipment["EDI_204_L301_WEIGHT"]) && isset($shipment["DETAIL"]) &&
				count($shipment["DETAIL"]) == 1 && ! isset($shipment["DETAIL"][0]["WEIGHT"]))
				$shipment["DETAIL"][0]["WEIGHT"] = intval($shipment["EDI_204_L301_WEIGHT"]);
			
			$shipment_code = $this->add_shipment( $shipment );
			$this->shipment_table->update_row(
				$this->shipment_table->primary_key.' = '.$shipment_code,
				array( array( "field" => "EDI_204_PRIMARY",
					"value" => $shipment_code), ) );

			// Mark as ready for dispatch, this should stop them from sending 990 for each shipment.
			// This is for Penske, only TL get to send 990
			$this->shipment_table->update_row(
				$this->shipment_table->primary_key.' = '.$shipment_code,
				array( array( "field" => "CURRENT_STATUS",
					"value" => $this->shipment_table->behavior_state["assign"]),
					array( "field" => "EDI_990_STATUS",
					"value" => 'Accepted') ) );

			$result = $shipment_code;
		} else {	//! Multiple shipments, create stops, and a load as well
			// Create load
			$load_code = $this->add_load();
			
			// Create shipments, $key should be 0, 1, 2 etc.
			$shipment_code_map = array();
			foreach($shipments as $key => $shipment) {
				$this->merge_shipment_fields( $heading_fields,  $summary_fields, $shipment );
				
				$shipment["CURRENT_STATUS"] = $this->shipment_table->behavior_state["imported"];
				$shipment["EDI_990_STATUS"] = 'Pending';
				$shipment["EDI_204_ORIGIN"] = $client;
				$shipment["LOAD_CODE"] = $load_code;
				
				$shipment_code = $this->add_shipment( $shipment );
				$shipment_code_map[$key] = $shipment_code;
				
				// Mark as ready for dispatch, this should stop them from sending 990 for each shipment.
				$this->shipment_table->update_row(
					$this->shipment_table->primary_key.' = '.$shipment_code,
					array( array( "field" => "CURRENT_STATUS",
						"value" => $this->shipment_table->behavior_state["assign"]), ) );
			}
			
			//! Set pointers to primary (first) shipment
			$this->shipment_table->update_row(
				$this->shipment_table->primary_key.' in ('.
					implode(',',array_values($shipment_code_map)).')',
				array( array( "field" => "EDI_204_PRIMARY",
					"value" => $shipment_code_map[0]), ) );
			$result = $shipment_code_map[0];

			// Create stops
			foreach($stops as $stop) {
				$stop["LOAD_CODE"] = $load_code;
				$stop["SHIPMENT"] = $shipment_code_map[$stop["SHIPMENT"]];
				$stop["CURRENT_STATUS"] = $this->stop_table->behavior_state["entry"];
				$stop_code = $this->add_stop( $stop );
			}
			
			// EDI_204_PRIMARY in the load table points to the first shipment
			$this->load_table->update_row(
					$this->load_table->primary_key.' = '.$load_code,
					array( array( "field" => "EDI_204_PRIMARY",
						"value" => $shipment_code_map[0]), ) );

			
			// Update distances
			$this->load_table->update_distances( $load_code );
			
			// This is for Penske, only TL get to send 990
			// move the load state to accepted without sending 990
			if( ! in_array($heading_fields["EDI_204_L1101_SERVICE"], $this->edi_PENSKE_TL_codes) ) {
				$this->load_table->update_row( $this->load_table->primary_key.' = '.$load_code,
					array( array( "field" => "CURRENT_STATUS",
						"value" => $this->load_table->behavior_state["accepted"]), ) );
				$this->shipment_table->update_row(
					"EDI_204_PRIMARY".' = '.$shipment_code_map[0],
					array( array( "field" => "EDI_990_STATUS",
						"value" => 'Accepted') ) );
			}
		}
	
		/* TBD these fields?
    ["PO_NUMBER"]=>
    string(8) "04331812"
    ["PO_NUMBER2"]=>
    string(8) "04331800"
    ["PO_NUMBER3"]=>
    NULL
    ["PO_NUMBER4"]=>
    NULL
    ["PO_NUMBER5"]=>
    NULL
	*/
		$this->log_event( __METHOD__.": return result = ".($result ? (is_numeric($result) ? $result : 'true') : 'false'), EXT_ERROR_DEBUG);
		return $result;
	}
	
	//! Take care of the tricky updating of due dates.
	public function handle_PENSKE_replacement_204( $sid, $shipments ) {
		$this->log_event( __METHOD__.": B204 sid=$sid, ".count($shipments)." shipments", EXT_ERROR_DEBUG);
		$result = false;
		
		// Verify we have this, and get the EDI_204_PRIMARY
		// Order by SHIPMENT_CODE DESC, to get the most recent with the sid
		$check = $this->shipment_table->fetch_rows("EDI_204_B204_SID = '".$sid."'
			AND CURRENT_STATUS != ".$this->shipment_table->behavior_state["cancel"],
			"SHIPMENT_CODE, EDI_204_PRIMARY, CURRENT_STATUS", "SHIPMENT_CODE DESC");
		if( is_array($check) && count($check) > 0 &&
			! empty($check[0]["EDI_204_PRIMARY"]) ) {
			$shipment_code = $check[0]["SHIPMENT_CODE"];
			$primary = $check[0]["EDI_204_PRIMARY"];
			$current_status = $check[0]["CURRENT_STATUS"];
			$this->log_event( __METHOD__.": found shipment=$shipment_code, primary=$primary, status=".$this->shipment_table->state_name[$current_status], EXT_ERROR_DEBUG);
			
			// Use the primary to get shipments for this EDI, in ascending order
			$check2 = $this->shipment_table->fetch_rows("EDI_204_PRIMARY = ".$primary,
				"SHIPMENT_CODE", "SHIPMENT_CODE ASC");
			if( is_array($check2) && count($check2) > 0 &&
				count($check2) == count($shipments) ) {
				for( $c = 0; $c < count($shipments); $c++ ) {
					$pk = $check2[$c]["SHIPMENT_CODE"];
					if( isset($shipments[$c]["DELIVER_DATE"]) )
						$changes["DELIVER_DATE"] = $shipments[$c]["DELIVER_DATE"];
					if( isset($shipments[$c]["DELIVER_TIME_OPTION"]) )
						$changes["DELIVER_TIME_OPTION"] = $shipments[$c]["DELIVER_TIME_OPTION"];
					if( isset($shipments[$c]["DELIVER_TIME1"]) )
						$changes["DELIVER_TIME1"] = $shipments[$c]["DELIVER_TIME1"];
					
					$sr = $this->shipment_table->update( $pk, $changes, false );
				}
			
				$result = $primary;
			}
		}

		return $result;
	}

	// ----------------------------------------------------------------------------
		
    //! Any tests to confirm ok to send a 990
    public function can_send_990( $primary ) {
		$result = false;
		if( ! is_object($this->shipment_table))
			$this->shipment_table = sts_shipment::getInstance($this->database, $this->debug);

		// Penske says:
		// Only Truck Load (TL) Carriers send EDI 990 response to Penske (Accept or Reject)
		// Determined by L1101 when L1102='QY'
		// We copy this into EDI_204_L1101_SERVICE, so check here
		$check = $this->shipment_table->fetch_rows( $this->shipment_table->primary_key." = ".$primary,
			"EDI_204_L1101_SERVICE");

		$result = is_array($check) && count($check) == 1 &&
			! empty($check[0]["EDI_204_L1101_SERVICE"]) &&
			in_array($check[0]["EDI_204_L1101_SERVICE"], $this->edi_PENSKE_TL_codes);
		
		$this->log_event( __METHOD__.": primary = $primary, result = ".($result ? 'true' : 'false'), EXT_ERROR_DEBUG);
		return $result;
    }
    
	//! Create a 990 response to a load tender
	// $client_id is the client id
	// $our_id is our id
	// $terminator is the choice of terminator
	// $separator is the choice of separator
	// $shipment_id should match the B204 value in the 204 (in EDI_204_B204_SID shipment field)
	// $action is A = accept or D = decline
	// $primary is our primary SHIPMENT_CODE (EDI_204_PRIMARY shipment or load field)
	// $control is a sequential number per 990 if sent in a batch mode
	// $usage is P = production data or T = test data

	public function create_990( $client_id, $our_id, $terminator, $separator, $shipment_id, 
		$action, $primary, $control = 1, $usage = 'P' ) {

		if( ! is_object($this->shipment_table))
			$this->shipment_table = sts_shipment::getInstance($this->database, $this->debug);

		//! Look for EDI_204_ISA15_USAGE, and set usage appropriately
		$check = $this->shipment_table->fetch_rows("SHIPMENT_CODE = '".$primary."'
			AND CURRENT_STATUS != ".$this->shipment_table->behavior_state["cancel"],
			"SHIPMENT_CODE, EDI_204_ISA15_USAGE", "SHIPMENT_CODE DESC");
		if( is_array($check) && count($check) > 0 && ! empty($check[0]["EDI_204_ISA15_USAGE"])) {
			$usage = $check[0]["EDI_204_ISA15_USAGE"] == 'Production Data' ? 'P' : 'T';
		}

		$edi_990 = "ISA*00*".str_pad('',10)."*00*".str_pad('',10).
			"*02*".str_pad($our_id,15)."*ZZ*".
			str_pad($client_id,15)."*".date("ymd")."*".date("Hi")."*U*00401*".
			sprintf("%09d", $control)."*0*".$usage."*>\n";
		$edi_990 .= "GS*".EDI_990."*".$our_id."*".$client_id.
			"*".date("Ymd")."*".date("Hi")."*".sprintf("%04d", $control)."*X*004010\n";
		
		$edi_990 .= "ST*990*".sprintf("%04d", $control)."\n";
		$edi_990 .= "B1*".$our_id."*".$shipment_id."*".date("Ymd")."*".$action."\n";
		$edi_990 .= "N9*CN*".$primary."\n";
		$edi_990 .= "SE*4*".sprintf("%04d", $control)."\n";

		$edi_990 .= "GE*1*".sprintf("%04d", $control)."\n";
		$edi_990 .= "IEA*1*".sprintf("%09d", $control)."\n";
		
		if( $terminator == 'tilde' )
			$edi_990 = str_replace("\n", '~', $edi_990);
		else if( $terminator == 'both' )
			$edi_990 = str_replace("\n", "~\n", $edi_990);
		
		if( $separator == 'carat' )
			$edi_990 = str_replace('*', '^', $edi_990);
			
		return $edi_990;
	}

	//! Create a 214 carrier shipment status message
	// $client_id is the client id
	// $our_id is our id
	// $terminator is the choice of terminator
	// $separator is the choice of separator
	// $shipment_id should match the B204 value in the 204 (in EDI_204_B204_SID shipment field)
	// $stop_number should match the S501 value in the 204 (in EDI_204_S501_STOP stop field)
	// $stop_event is per $edi_214_stop_event
	// $event_status is per $edi_214_event_status
	// $event_timestamp - when
	// $city - city in question
	// $state - state
	// $primary is our primary SHIPMENT_CODE (EDI_204_PRIMARY shipment or load field)
	// $control is a sequential number per 214 if sent in a batch mode (not used so far)

	public function create_214( $client_id, $our_id, $terminator, $separator, $shipment_id, $stop_number,
		$stop_event, $event_status, $event_timestamp, $city, $state, $primary,
		$control = 1, $usage = 'P' ) {
		
		if( ! is_object($this->shipment_table))
			$this->shipment_table = sts_shipment::getInstance($this->database, $this->debug);
		if( ! is_object($this->stop_table))
			$this->stop_table = sts_stop::getInstance($this->database, $this->debug);

		//! Look for EDI_204_ISA15_USAGE, and set usage appropriately
		$check = $this->shipment_table->fetch_rows("SHIPMENT_CODE = '".$primary."'
			AND CURRENT_STATUS != ".$this->shipment_table->behavior_state["cancel"],
			"SHIPMENT_CODE, EDI_204_ISA15_USAGE", "SHIPMENT_CODE DESC");
		if( is_array($check) && count($check) > 0 && ! empty($check[0]["EDI_204_ISA15_USAGE"])) {
			$usage = $check[0]["EDI_204_ISA15_USAGE"] == 'Production Data' ? 'P' : 'T';
		}

		$edi_214 = "ISA*00*".str_pad('',10)."*00*".str_pad('',10).
			"*02*".str_pad($our_id,15)."*ZZ*".
			str_pad($client_id,15)."*".date("ymd")."*".date("Hi")."*U*00401*".
			sprintf("%09d", $control)."*0*".$usage."*>\n";
		$edi_214 .= "GS*".EDI_214."*".$our_id."*".$client_id.
			"*".date("Ymd")."*".date("Hi")."*".sprintf("%04d", $control)."*X*004010\n";
		
		$edi_214 .= "ST*214*".sprintf("%04d", $control)."\n";
		$edi_214 .= "B10*".$primary."*".$shipment_id."*".$our_id."\n";
		$included_segments = 2;
		$assigned_number = 1;
		
		//! Look for EDI_204_L1101_ACCOUNT which is the L1101 with 11 qualifier.
		$check = $this->shipment_table->fetch_rows("SHIPMENT_CODE = '".$primary."'
			AND CURRENT_STATUS != ".$this->shipment_table->behavior_state["cancel"],
			"SHIPMENT_CODE, EDI_204_L1101_ACCOUNT", "SHIPMENT_CODE DESC");
		if( is_array($check) && count($check) > 0 && ! empty($check[0]["EDI_204_L1101_ACCOUNT"])) {
			$edi_214 .= "L11*".$check[0]["EDI_204_L1101_ACCOUNT"]."*11\n";
			$included_segments++;
		}
		
		$edi_214 .= "LX*".$assigned_number++."\n";
		$included_segments++;

		if( in_array($stop_event, array('AA', 'AB') ) )
			$edi_214 .= "AT7***".$stop_event."*".$event_status;
		else
			$edi_214 .= "AT7*".$stop_event."*".$event_status."**";
		$edi_214 .= "*".date("Ymd", $event_timestamp)."*".date("Hi", $event_timestamp)."*LT\n";
		$included_segments++;

		$edi_214 .= "MS1*".$city."*".$state."\n";
		$included_segments++;
		
		//! Look for TRAILER_NUMBER
		$check2 = $this->stop_table->database->get_one_row("
		SELECT STOP_CODE, SEQUENCE_NO, EDI_204_S501_STOP,
			(SELECT UNIT_NUMBER AS TRAILER_NUMBER
			FROM EXP_TRAILER
			WHERE TRAILER_CODE = COALESCE(TRAILER,
				(SELECT TRAILER FROM EXP_LOAD 
				WHERE EXP_LOAD.LOAD_CODE = EXP_STOP.LOAD_CODE) )
			LIMIT 1) AS TRAILER_NUMBER
		FROM EXP_STOP
		WHERE EDI_204_S501_STOP = $stop_number
		AND LOAD_CODE = (SELECT LOAD_CODE FROM EXP_SHIPMENT
			WHERE SHIPMENT_CODE = $primary)
		LIMIT 1");
		if( is_array($check2) &&  ! empty($check2["TRAILER_NUMBER"])) {
			$edi_214 .= "MS2*".$our_id."*".$check2["TRAILER_NUMBER"]."\n";
			$included_segments++;
		}
		$edi_214 .= "L11*".$stop_number."*QN\n";
		$included_segments++;
		
		//! Add another loop for mandatory Delivery ETA
		if( $stop_event == 'AF' ) {	// Depart Shipper
			$check3 = $this->stop_table->database->get_one_row("
				SELECT s1.EDI_DELIVER_ETA,
					(SELECT UNIT_NUMBER AS TRAILER_NUMBER
					FROM EXP_TRAILER
					WHERE TRAILER_CODE = COALESCE(s2.TRAILER,
						(SELECT TRAILER FROM EXP_LOAD 
						WHERE EXP_LOAD.LOAD_CODE = s2.LOAD_CODE) )
						LIMIT 1) AS TRAILER_NUMBER,
				s2.STOP_CODE, s2.SEQUENCE_NO, s2.EDI_204_S501_STOP,
					(CASE s2.STOP_TYPE
						WHEN 'pick' THEN s3.SHIPPER_STATE
						WHEN 'drop' THEN s3.CONS_STATE
						ELSE s2.STOP_STATE END
					) AS STATE,
					(CASE s2.STOP_TYPE
						WHEN 'pick' THEN s3.SHIPPER_CITY
						WHEN 'drop' THEN s3.CONS_CITY
						ELSE s2.STOP_CITY END
					) AS CITY
				FROM EXP_STOP s1, EXP_STOP s2, EXP_SHIPMENT s3
				WHERE s1.EDI_204_S501_STOP = 1
				AND s1.LOAD_CODE = (SELECT LOAD_CODE FROM EXP_SHIPMENT
					WHERE SHIPMENT_CODE = 8764)
				AND s1.SHIPMENT = s2.SHIPMENT
				AND s1.SHIPMENT = s3.SHIPMENT_CODE
				AND s1.SEQUENCE_NO < s2.SEQUENCE_NO
				LIMIT 1");
			if( $this->debug ) {
				echo "<hr><h3>check3</h3>
				<pre>";
				var_dump($check3);
				echo "</pre>";
			}

			if( is_array($check3) &&
				! empty($check3["EDI_DELIVER_ETA"]) ) {
				// Another loop 200
				$edi_214 .= "LX*".$assigned_number++."\n";
				$included_segments++;
				
				$due = $this->stop_table->get_due($check3["STOP_CODE"]);
				if( $due && strtotime($check3["EDI_DELIVER_ETA"]) > strtotime($due) )
					$event_status = 'AL'; // Previous stop
				else
					$event_status = 'NS';

				$edi_214 .= "AT7*AG*".$event_status."**";
				$edi_214 .= "*".date("Ymd", strtotime($check3["EDI_DELIVER_ETA"]))."*".date("Hi", strtotime($check3["EDI_DELIVER_ETA"]))."*LT\n";
				$included_segments++;

				$edi_214 .= "MS1*".$check3["CITY"]."*".$check3["STATE"]."\n";
				$included_segments++;
		
				$edi_214 .= "MS2*".$our_id."*".$check3["TRAILER_NUMBER"]."\n";
				$included_segments++;

				$edi_214 .= "L11*".$check3["EDI_204_S501_STOP"]."*QN\n";
				$included_segments++;
			}
		}
				
		$edi_214 .= "SE*".($included_segments+1)."*".sprintf("%04d", $control)."\n";

		$edi_214 .= "GE*1*".sprintf("%04d", $control)."\n";
		$edi_214 .= "IEA*1*".sprintf("%09d", $control)."\n";
		
		if( $terminator == 'tilde' )
			$edi_214 = str_replace("\n", '~', $edi_214);
		else if( $terminator == 'both' )
			$edi_214 = str_replace("\n", "~\n", $edi_214);
		
		if( $separator == 'carat' )
			$edi_214 = str_replace('*', '^', $edi_214);
			
		return $edi_214;
	}

	//! Prepare a file name
	public function create_filename( $our_id ) {
		list($usec, $sec) = explode(" ", microtime());
		return $our_id.date('YmdHis').substr($usec, 5, 3).'.edi';
	}
	
}

?>
