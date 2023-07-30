<?php

// This is the EDI transaction class, it contains methods for importing and exporting EDI

// no direct access
defined('_STS_INCLUDE') or die('Restricted access');
require_once( dirname(__FILE__) ."/sts_edi_class.php" );
require_once( dirname(__FILE__) ."/sts_edi_parser_class.php" );
require_once( dirname(__FILE__) ."/sts_edi_map_ruan_class.php" );
require_once( dirname(__FILE__) ."/sts_edi_map_penske_class.php" );
require_once( dirname(__FILE__) ."/sts_email_class.php" );
require_once( dirname(__FILE__) ."/sts_user_class.php" );
require_once( dirname(__FILE__) ."/sts_shipment_class.php" );
require_once( dirname(__FILE__) ."/sts_ftp_class.php" );

class sts_edi_trans extends sts_edi {
	private $shipment_table;
	private $user_table;
	private $parser;
	private $map = false;
	private $ftp = false;
	
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
    
    //! select which mapping class to use for a given client.
    public function select_map_class( $client ) {
		$this->log_event( __METHOD__.": client=$client", EXT_ERROR_DEBUG);
		$this->map = false;
		
		if( ! is_object($this->ftp) )
			$this->ftp = sts_ftp::getInstance($this->database, $this->debug);

		if( ! empty($client) ) {
			switch( $this->ftp->edi_format( $client ) ) {
				case EDI_MAPPING_RUAN:
					$this->map = sts_edi_map_ruan::getInstance($this->database, $this->debug);
					break;
				
				case EDI_MAPPING_PENSKE:
					$this->map = sts_edi_map_penske::getInstance($this->database, $this->debug);
					break;
				
				default:
					$this->map = false;
			}
	   }
	   return $this->map;
    }

    //! create a menu for event status
    // If we have a map class, base it on that or else use a default
    public function event_status_menu( $id = 'STATUS' ) {
		$select = false;
		if( $this->map == false ) {
			$choices = array_flip($this->field_value['AT702']);
		} else {
			$choices = $this->map->edi_214_event_status;
		}
		
		if( is_array($choices) && ksort($choices) ) {
			
			$select = '<select class="form-control input-sm" name="'.$id.'" id="'.$id.'">
			<option value="NS">Reason for being late</option>';
			foreach( $choices as $label => $value ) {
				if( $value <> 'NS' ) {
					$select .= '<option value="'.$value.'">'.$label.'</option>
				';
				}
			}
			$select .= '</select>';
		}
		
		return $select;
	}
 
	// ----------------------------------------------------------------------------
	
	//! Call this to import all 204s from a client
	// $client - which client
	public function import_204s( $client ) {
		global $_SESSION;
		$count = 0;
		$this->log_event( __METHOD__.": client=$client", EXT_ERROR_DEBUG);
		if( $this->enabled() ) {
			if( ! is_object($this->parser) )
				$this->parser = sts_edi_parser::getInstance($this->database, $this->debug);
			if( ! is_object($this->ftp) )
				$this->ftp = sts_ftp::getInstance($this->database, $this->debug);
			if( ! is_object($this->user_table) )
				$this->user_table = sts_user::getInstance($this->database, $this->debug);

			//! Select which mapping class to use.
			$this->select_map_class( $client );
			if( $this->map == false ) {
				if( $this->debug ) echo "<p>".__METHOD__.": unknown EDI format for $client.</p>";
				$this->log_event( __METHOD__.": unknown EDI format for $client", EXT_ERROR_ERROR);
				$email = sts_email::getInstance($this->database, $this->debug);
				$email->send_alert(__METHOD__.": unknown EDI format for $client" );
						
			} else {
				$save_user = isset($_SESSION['EXT_USER_CODE']) ? $_SESSION['EXT_USER_CODE'] : NULL;
				$_SESSION['EXT_USER_CODE'] = $this->user_table->special_user( EDI_USER );
		
				$contents = $this->new_filenames( $client, $this->ftp->ftp_connect($client) );
				if( is_array($contents) && count($contents) > 0 ) {
					foreach( $contents as $filename ) {
						$this->log_event( __METHOD__.": import $filename", EXT_ERROR_TRACE);
						$edi_raw = $this->ftp->ftp_get_contents( $filename );	// Import file
						
						if( $edi_raw ) {
							$this->parser->tokenize( $edi_raw );	// Convert the text file to tokens
							// Parse the tokens according to 204 schema
							if( $this->ftp->edi_strict( $client ) )
								$edi_parsed = $this->parser->parse_edi( $client, $this->ftp->edi_our_id( $client ), true );
							else
								$edi_parsed = $this->parser->parse_edi();
							if( $edi_parsed ) {
								try {
									list($type, $sid) = $this->edi_get_type_sid( $edi_parsed );
		
									$this->log_event( __METHOD__.": store file=$filename, type=$type, sid=$sid", EXT_ERROR_NOTICE);
									$changes = array( 'DIRECTION' => 'in',
										'EDI_TIME' => date("Y-m-d H:i:s"), 'CONTENT' => $edi_raw,
										'EDI_CLIENT' => $client, 'FILENAME' => $filename,
										'COMMENTS' => 'Imported via '.__METHOD__,
										'EDI_TYPE' => $type, 'IDENTIFIER' => $sid );
									$purpose = $this->lookup_edi_path( $edi_parsed, '204/Heading/B2A/B2A01' );
									if( $purpose ) 
										$changes["B2A01_PURPOSE"] = $this->get_field_value( "B2A01", $purpose );
									
									$notdef = $this->lookup_edi_path( $edi_parsed, 'Not Defined');
		
									if( $type == '204' ) {
										// Import to Exspeedite
										// If multiple 204s in an X12, we get multiple entries
										$save = $this->add($changes);
										$edi_204s = $this->lookup_edi_path( $edi_parsed, '204', EXPECT_MULTIPLE );
										if( is_array($edi_204s) && ! empty($edi_204s) ) {
											foreach($edi_204s as $edi_204) {
												$result = $this->map->map_204( $client, $notdef, $edi_204 );
												if( $result && is_numeric($result) )
													$this->update($save,
														array('EDI_204_PRIMARY' => $result));
											}
										}
	
									} else {
										$email = sts_email::getInstance($this->database, $this->debug);
										$email->send_alert(__METHOD__.": Incoming EDI not a 204<br>
											file=$filename, type=$type, sid=$sid<br>
											Contents<br><br>
											<pre>".str_replace("\r","\n", $edi_raw)."</pre><br>" );
									}
									if( ! $this->ftp->ftp_delete_file( $filename ) ) {	// remove file
									    if( $this->debug ) echo "<p>".__METHOD__.": Failed to delete file $filename</p>";
									    $this->log_event( __METHOD__.": Failed to delete file $filename", EXT_ERROR_ERROR);
										
									}
									$count++;
								} catch (Exception $e) {
								    if( $this->debug ) echo "<p>".__METHOD__.": Caught exception: ",  $e->getMessage(), "</p>";
								    $this->log_event( __METHOD__.": Caught exception: ".$e->getMessage(), EXT_ERROR_ERROR);
									$email = sts_email::getInstance($this->database, $this->debug);
									$email->send_alert(__METHOD__.": Caught exception: ".  $e->getMessage().(isset($filename) ? "<br>
										file=$filename" : "filename not set").
										(isset($edi_raw) ? "<br>
										Contents<br><br>
										<pre>".str_replace("\r","\n", $edi_raw)."</pre><br>" : "raw EDI not available" ) );
								}
							}
						} else {
							$email = sts_email::getInstance($this->database, $this->debug);
							$email->send_alert(__METHOD__.": ftp_get_contents failed<br>
								file=$filename" );
						}
					}
				}
				
				$this->ftp->ftp_close();
				$_SESSION['EXT_USER_CODE'] = $save_user;
				$this->log_event( __METHOD__.": imported $count files from $client.", EXT_ERROR_NOTICE);
			}
		}
		
		return $count;
	}
	
	public function import_204s_for_active_clients() {
		$count = 0;
		if( $this->enabled() ) {
			if( ! is_object($this->ftp) )
				$this->ftp = sts_ftp::getInstance($this->database, $this->debug);
			$clients = $this->ftp->active_clients();
			if( is_array($clients) && count($clients) > 0 ) {
				foreach( $clients as $client ) {
					$count += $this->import_204s( $client );
				}
			} else {
				$this->log_event( __METHOD__.": no active clients configured.", EXT_ERROR_WARNING);
			}
		}
		$this->log_event( __METHOD__.": imported $count files.", EXT_ERROR_NOTICE);
		return $count;
	}


	//! Call this to send a 990 to a client
	private function send_990( $shipment_code, $action ) {
		$result = false;
		if( $this->debug ) echo "<p>".__METHOD__.": shipment=$shipment_code, action=$action</p>";
		$this->log_event( __METHOD__.": shipment=$shipment_code, action=$action", EXT_ERROR_DEBUG);

		if( $this->enabled() ) {
			if( ! is_object($this->ftp) )
				$this->ftp = sts_ftp::getInstance($this->database, $this->debug);
			if( ! is_object($this->shipment_table))
				$this->shipment_table = sts_shipment::getInstance($this->database, $this->debug);
			$shipment_fields = $this->shipment_table->fetch_rows(
				$this->shipment_table->primary_key." = ".$shipment_code,
				"EDI_204_B204_SID, LOAD_CODE, EDI_204_ORIGIN, 
				EDI_204_G6202_EXPIRES, EDI_204_ISA15_USAGE, EDI_990_STATUS");
			
			if( is_array($shipment_fields) && count($shipment_fields) == 1 ) {
				$row = $shipment_fields[0];
				
				if( ! isset($row["EDI_204_ORIGIN"]) || ! isset($row["EDI_204_B204_SID"]) ||
					! isset($row["LOAD_CODE"]) || ! isset($row["EDI_204_G6202_EXPIRES"]) ||
					! isset($row["EDI_204_ISA15_USAGE"]) || ! isset($row["EDI_990_STATUS"]) ) {
					if( $this->debug ) echo "<p>".__METHOD__.": missing required fields.</p>";
					$this->log_event( __METHOD__.": missing required fields.", EXT_ERROR_ERROR);
				} else {
					$client			= $row["EDI_204_ORIGIN"];
					$shipment_id	= $row["EDI_204_B204_SID"];
					$load_code		= $row["LOAD_CODE"];
					$expires		= strtotime($row["EDI_204_G6202_EXPIRES"]);
					$usage			= $row["EDI_204_ISA15_USAGE"];
					$status			= $row["EDI_990_STATUS"];
					
					//! Select which mapping class to use.
					$this->select_map_class( $client );
					
					if( ! $this->map ) {
						if( $this->debug ) echo "<p>".__METHOD__.": unknown EDI format for $client.</p>";
						$this->log_event( __METHOD__.": unknown EDI format for $client", EXT_ERROR_ERROR);
						
					} else if( ! $this->map->can_send_990( $shipment_code ) ) {
						if( $this->debug ) echo "<p>".__METHOD__.": unable to send 990.</p>";
						$this->log_event( __METHOD__.": unable to send 990", EXT_ERROR_ERROR);
					} else if( time() > $expires ) {
						if( $this->debug ) echo "<p>".__METHOD__.": offer expired.</p>";
						$this->log_event( __METHOD__.": offer expired.", EXT_ERROR_ERROR);
						if( $status <> 'Expired' ) {
							$this->shipment_table->update( 'EDI_204_PRIMARY = '.$shipment_code,
								array( "EDI_990_STATUS" => 'Expired' ), false );
						}
					} else if( false && $usage <> 'Production Data') {
						if( $this->debug ) echo "<p>".__METHOD__.": not Production Data.</p>";
						$this->log_event( __METHOD__.": not Production Data.", EXT_ERROR_ERROR);
					} else if( $status <> 'Pending' ) {
						if( $this->debug ) echo "<p>".__METHOD__.": not in Pending status.</p>";
						$this->log_event( __METHOD__.": not in Pending status.", EXT_ERROR_ERROR);
					} else {
						if( $this->debug ) echo "<p>".__METHOD__.": connecting.</p>";
						$contents = $this->ftp->ftp_connect($client);
						if( is_array($contents) ) {
							$filename = $this->map->create_filename( $this->ftp->edi_our_id( $client ) );
							$edi_raw = $this->map->create_990( $client,
								$this->ftp->edi_our_id( $client ),
								$this->ftp->edi_terminator( $client ),
								$this->ftp->edi_separator( $client ),
								$shipment_id,
								$action, $shipment_code );

							$result = $this->ftp->ftp_put_contents( $filename, $edi_raw );

							if( $this->debug ) echo "<p>".__METHOD__.": $filename sent, id=$shipment_id, action=$action, load=$load_code, result=".($result ? "True":"False")."</p>";
							$this->log_event( __METHOD__.": $filename sent, id=$shipment_id, action=$action, load=$load_code, result=".($result ? "True":"False"), EXT_ERROR_TRACE);
						}
						
						if( ! empty($edi_raw) )
							$save = $this->add( array( 'DIRECTION' => 'out',
								'EDI_TIME' => date("Y-m-d H:i:s"), 'CONTENT' => $edi_raw,
								'EDI_CLIENT' => $client, 'EDI_TYPE' => '990', 'FILENAME' => $filename,
								'IDENTIFIER' => $shipment_id, 'EDI_204_PRIMARY' => $shipment_code,
								'COMMENTS' => 'Exported via '.__METHOD__.', ftp='.
								($result ? 'true' : 'false') ) );
	
						$this->ftp->ftp_close();
						
						if( $result ) {			// Update status field, date sent
							$this->shipment_table->update( 'EDI_204_PRIMARY = '.$shipment_code,
								array( "EDI_990_STATUS" => ($action == 'A' ? 'Accepted' : 'Declined'),
									"EDI_990_SENT" => date("Y-m-d H:i:s") ), false );
						}
					}
				}
			}
		}
		
		return $result;
	}
	
	public function accept_204( $shipment_code ) {
		return $this->send_990( $shipment_code, 'A' );
	}
	
	public function decline_204( $shipment_code ) {
		return $this->send_990( $shipment_code, 'D' );
	}
	
	// ----------------------------------------------------------------------------
	
	// Map load states to 214 events, used in exp_load_state.php
	public function load_state_event( $state ) {
		$result = false;
		switch( $state ) {
			case 'arrive shipper':
				$result = 'Arrive Shipper';
				break;
			case 'depart shipper':
				$result = 'Depart Shipper';
				break;
			case 'arrive cons':
				$result = 'Arrive Consignee';
				break;
			case 'depart cons':
			case 'complete':	// Handle a case where last stop triggers complete status
				$result = 'Depart Consignee';
				break;
			
			default:
				break;
		}
		
		return $result;
	}

	public function send_214( $load_code, $stop, $event ) {
		$result = false;
		if( $this->debug ) echo "<p>".__METHOD__.": load=$load_code, stop=$stop, event=$event</p>";
		$this->log_event( __METHOD__.": load=$load_code, stop=$stop, event=$event", EXT_ERROR_DEBUG);

		if( $this->enabled() ) {
			if( ! is_object($this->ftp) )
				$this->ftp = sts_ftp::getInstance($this->database, $this->debug);
			if( ! is_object($this->shipment_table))
				$this->shipment_table = sts_shipment::getInstance($this->database, $this->debug);
			
			$check = $this->shipment_table->database->get_one_row(
				"SELECT EXP_LOAD.LOAD_CODE, EXP_LOAD.CURRENT_STATUS, CURRENT_STOP,
					COALESCE(EXP_LOAD.EDI_204_PRIMARY, EXP_SHIPMENT.EDI_204_PRIMARY) AS EDI_204_PRIMARY,
					EXP_SHIPMENT.EDI_204_B204_SID,
					EXP_SHIPMENT.EDI_204_ORIGIN, EXP_SHIPMENT.EDI_204_ISA15_USAGE,
					COALESCE(EDI_204_S501_STOP, SEQUENCE_NO) AS STOP_NUMBER,
					ACTUAL_ARRIVE, ACTUAL_DEPART, STOP_TYPE,
					EDI_ARRIVE_STATUS, EDI_DEPART_STATUS, EDI_DELIVER_ETA,
					(CASE STOP_TYPE
						WHEN 'pick' THEN SHIPPER_STATE
						WHEN 'drop' THEN CONS_STATE
						ELSE STOP_STATE END
					) AS STATE,
					(CASE STOP_TYPE
						WHEN 'pick' THEN SHIPPER_CITY
						WHEN 'drop' THEN CONS_CITY
						ELSE STOP_CITY END
					) AS CITY
				FROM EXP_LOAD, EXP_SHIPMENT, EXP_STOP
				WHERE EXP_LOAD.LOAD_CODE = ".$load_code."
				AND EXP_LOAD.LOAD_CODE = EXP_STOP.LOAD_CODE
				AND EXP_LOAD.LOAD_CODE = EXP_SHIPMENT.LOAD_CODE
				AND EXP_SHIPMENT.SHIPMENT_CODE = EXP_STOP.SHIPMENT
				AND EXP_STOP.SEQUENCE_NO = ".$stop );

			if( $this->debug ) {
				echo "<pre>";
				var_dump($check);
				echo "</pre>";
			}
			
			if( ! is_array($check) ) {
				if( $this->debug ) echo "<p>".__METHOD__.": missing required fields.</p>";
				$this->log_event( __METHOD__.": missing required fields.", EXT_ERROR_ERROR);
			} else if( ! isset($check["EDI_204_PRIMARY"]) || $check["EDI_204_PRIMARY"] == '') {
				if( $this->debug ) echo "<p>".__METHOD__.": missing EDI_204_PRIMARY.</p>";
				$this->log_event( __METHOD__.": missing EDI_204_PRIMARY.", EXT_ERROR_ERROR);
			} else if( ! isset($check["EDI_204_B204_SID"]) ) {
				if( $this->debug ) echo "<p>".__METHOD__.": missing EDI_204_B204_SID.</p>";
				$this->log_event( __METHOD__.": missing EDI_204_B204_SID.", EXT_ERROR_ERROR);
			} else if( ! isset($check["EDI_204_ORIGIN"]) ) {
				if( $this->debug ) echo "<p>".__METHOD__.": missing EDI_204_ORIGIN.</p>";
				$this->log_event( __METHOD__.": missing EDI_204_ORIGIN.", EXT_ERROR_ERROR);
			} else if( strpos($event, 'Arrive') !== false && (! isset($check["ACTUAL_ARRIVE"]) || is_null($check["ACTUAL_ARRIVE"]) ) ) {
				if( $this->debug ) echo "<p>".__METHOD__.": missing ACTUAL_ARRIVE.</p>";
				$this->log_event( __METHOD__.": missing ACTUAL_ARRIVE.", EXT_ERROR_ERROR);
			} else if( strpos($event, 'Depart') !== false && (! isset($check["ACTUAL_DEPART"]) || is_null($check["ACTUAL_DEPART"]) ) ) {
				if( $this->debug ) echo "<p>".__METHOD__.": missing ACTUAL_DEPART.</p>";
				$this->log_event( __METHOD__.": missing ACTUAL_DEPART.", EXT_ERROR_ERROR);

			} else if( strpos($event, 'Arrive') !== false && (! isset($check["EDI_ARRIVE_STATUS"]) || is_null($check["EDI_ARRIVE_STATUS"]) ) ) {
				if( $this->debug ) echo "<p>".__METHOD__.": missing EDI_ARRIVE_STATUS.</p>";
				$this->log_event( __METHOD__.": missing EDI_ARRIVE_STATUS.", EXT_ERROR_ERROR);
			} else if( strpos($event, 'Depart') !== false && (! isset($check["EDI_DEPART_STATUS"]) || is_null($check["EDI_DEPART_STATUS"]) ) ) {
				if( $this->debug ) echo "<p>".__METHOD__.": missing EDI_DEPART_STATUS.</p>";
				$this->log_event( __METHOD__.": missing EDI_DEPART_STATUS.", EXT_ERROR_ERROR);

			} else if( ! isset($check["CITY"]) || ! isset($check["STATE"]) ) {
				if( $this->debug ) echo "<p>".__METHOD__.": missing CITY or STATE.</p>";
				$this->log_event( __METHOD__.": missing CITY or STATE.", EXT_ERROR_ERROR);
			} else {
				$shipment_code	= $check["EDI_204_PRIMARY"];
				$shipment_id	= $check["EDI_204_B204_SID"];
				$stop_number	= $check["STOP_NUMBER"];
				$city			= $check["CITY"];
				$state			= $check["STATE"];
				$client			= $check["EDI_204_ORIGIN"];
				$usage			= $check["EDI_204_ISA15_USAGE"];

				//! Select which mapping class to use.
				$this->select_map_class( $client );

				switch( $event ) {
					case 'Arrive Shipper':
					case 'Arrive Consignee':
						$event_timestamp = strtotime($check["ACTUAL_ARRIVE"]);
						$event_status = $check["EDI_ARRIVE_STATUS"];
						break;

					case 'Depart Shipper':
					case 'Depart Consignee':
						$event_timestamp = strtotime($check["ACTUAL_DEPART"]);
						$event_status = $check["EDI_DEPART_STATUS"];
						break;
						
					default:
						$event_timestamp = time();
						$event_status = 'NS'; // Just so nothing breaks
				}
				
				if( $this->debug ) echo "<p>".__METHOD__.": connecting.</p>";
				$contents = $this->ftp->ftp_connect($client);
				
				if( ! $this->map ) {
					if( $this->debug ) echo "<p>".__METHOD__.": unknown EDI format for $client.</p>";
					$this->log_event( __METHOD__.": unknown EDI format for $client", EXT_ERROR_ERROR);
						
				} else if( is_array($contents) ) {

					$stop_event = $this->map->map_214_stop_event( $event );
					$filename = $this->map->create_filename( $this->ftp->edi_our_id( $client ) );
					$edi_raw = $this->map->create_214( $client,
								$this->ftp->edi_our_id( $client ),
								$this->ftp->edi_terminator( $client ),
								$this->ftp->edi_separator( $client ), $shipment_id,
						$stop_number, $stop_event, $event_status,
						$event_timestamp, $city, $state, $shipment_code );

					$result = $this->ftp->ftp_put_contents( $filename, $edi_raw );

					if( $this->debug ) echo "<p>".__METHOD__.": $filename sent, id=$shipment_id, event=$stop_event, status=$event_status, load=$load_code, result=".($result ? "True":"False")."</p>";
					$this->log_event( __METHOD__.": $filename sent, id=$shipment_id, event=$stop_event, status=$event_status, load=$load_code, result=".($result ? "True":"False"), EXT_ERROR_TRACE);
				}
				
				if( ! empty($edi_raw) )
					$save = $this->add( array( 'DIRECTION' => 'out',
						'EDI_TIME' => date("Y-m-d H:i:s"), 'CONTENT' => $edi_raw,
						'EDI_CLIENT' => $client, 'EDI_TYPE' => '214', 'FILENAME' => $filename,
						'IDENTIFIER' => $shipment_id, 'EDI_204_PRIMARY' => $shipment_code,
						'COMMENTS' => 'Exported via '.__METHOD__.', ftp='.
						($result ? 'true' : 'false') ) );

				$this->ftp->ftp_close();
				
			}

		}
		
		return $result;
	}
	
	
}

?>
