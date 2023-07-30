<?php

// $Id: sts_load_class.php 5146 2023-06-29 19:57:45Z dev $
// Load class, all activity related to loads.

// no direct access
defined('_STS_INCLUDE') or die('Restricted access');

require_once( "sts_table_class.php" );

// The following are used in some of the methods, and need to be included at a global scope.
require_once( "sts_stop_class.php" );
require_once( "sts_shipment_class.php" );
require_once( "sts_shipment_load_class.php" );
require_once( "sts_zip_class.php" );
require_once( "sts_driver_class.php" );
require_once( "sts_carrier_class.php" );
require_once( "sts_vacation_class.php" );
require_once( "sts_tractor_class.php" );
require_once( "sts_trailer_class.php" );
require_once( "sts_status_class.php" );
require_once( "sts_setting_class.php" );
require_once( "sts_email_class.php" );
require_once( "sts_user_log_class.php" );
require_once( "sts_status_codes_class.php" );


class sts_load extends sts_table {

	private $states;
	private $status_table;
	private $status_codes_table;
	public $setting_table;
	private $can_skip_manifest;
	private $driver_manifest;
	private $repositioning_stop;
	private $dispatch_driver_only;
	private $export_quickbooks;
	private $sts_qb_online;
	private $log_file;
	public $state_name;
	public $state_behavior;
	public $name_state;
	public $behavior_state;
	public $state_change_error;
	public $state_change_level;
	public $state_change_post;
	public $found_rows;
	private $ctd;
	private $multi_company;
	public $export_sage50;
	private $carrier_insufficient_ins;
	private $reuse_empty_loads;
	private $dispatch_driver_multiple;

	//! SCR# 499 - cache following states
	private $cache_following;
	
	// Constructor does not need the table name
	public function __construct( $database, $debug = false ) {

		$this->debug = $debug;
		$this->primary_key = "LOAD_CODE";
		if( $this->debug ) echo "<p>Create sts_load</p>";
		$this->status_table = sts_status::getInstance($database, $debug);
		$this->setting_table = sts_setting::getInstance($database, $debug);
		$this->can_skip_manifest = ($this->setting_table->get( 'option', 'Skip freight agreement' ) == 'true');
		$this->driver_manifest = ($this->setting_table->get( 'option', 'DRMANIFEST_ENABLED' ) == 'true');
		$this->repositioning_stop = ($this->setting_table->get( 'option', 'Add Repositioning Stop' ) == 'true');
		$this->dispatch_driver_only = ($this->setting_table->get( 'option', 'DISPATCH_DRIVER_ONLY' ) == 'true');
		$this->log_file = $this->setting_table->get( 'option', 'DEBUG_LOG_FILE' );
		$this->export_quickbooks = 
			($this->setting_table->get( 'api', 'EXPORT_QUICKBOOKS' ) == 'true');
		$this->sts_qb_online = $this->setting_table->get( 'api', 'QUICKBOOKS_ONLINE' ) == 'true';
		$this->multi_company = 
			($this->setting_table->get( 'option', 'MULTI_COMPANY' ) == 'true');
		$this->export_sage50 = 
			($this->setting_table->get( 'api', 'EXPORT_SAGE50_CSV' ) == 'true');
		$this->carrier_insufficient_ins = 
			($this->setting_table->get( 'option', 'CARRIER_INSUFFCIENT_INS' ) == 'true');
		$this->reuse_empty_loads = 
			$this->setting_table->get( 'option', 'REUSE_EMPTY_LOADS' ) == 'true';
		$this->dispatch_driver_multiple = 
			$this->setting_table->get( 'option', 'DISPATCH_DRIVER_MULTIPLE' ) == 'true';

		parent::__construct( $database, LOAD_TABLE, $debug);
		$this->load_states();
		$this->status_codes_table = sts_status_codes::getInstance($database, $debug);
		$this->cache_following = $this->status_codes_table->cache_following_states( 'load' );
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

	private function load_states() {
		
		$cached = $this->cache->get_state_table( 'load' );
		
		if( is_array($cached) && count($cached) > 0 ) {
			$this->states = $cached;
		} else {
			$this->states = $this->database->get_multiple_rows("select STATUS_CODES_CODE, STATUS_STATE, BEHAVIOR, PREVIOUS
				FROM EXP_STATUS_CODES
				WHERE SOURCE_TYPE = 'load'
				ORDER BY STATUS_CODES_CODE ASC");
			assert( count($this->states)." > 0", "Unable to load states for loads" );
		}
		
		$this->state_name = array();
		$this->state_behavior = array();
		foreach( $this->states as $row ) {
			$this->state_name[$row['STATUS_CODES_CODE']] = $row['STATUS_STATE'];
			$this->state_behavior[$row['STATUS_CODES_CODE']] = $row['BEHAVIOR'];
			$this->name_state[$row['STATUS_STATE']] = $row['STATUS_CODES_CODE'];
			$this->behavior_state[$row['BEHAVIOR']] = $row['STATUS_CODES_CODE'];
		}
		
		//if( $this->debug ) {
		//	echo "<p>sts_load > load_states: states = </p>
		//	<pre>";
		//	var_dump($this->states);
		//	echo "</pre>";
		//}
	}
	
	private function log_event( $event ) {		
		if( isset($this->log_file) && $this->log_file <> '' &&
			is_writable($this->log_file) && ! is_dir($this->log_file)  )
		
		file_put_contents($this->log_file, date('m/d/Y h:i:s A')." ".$event.PHP_EOL, FILE_APPEND);
	}

	//! SCR# 499 - use cached following states
	public function following_states( $this_state ) {
	
		$cache = $this->cache_following;
	
		$following = is_array($cache) && isset($cache[$this_state]) ? $cache[$this_state] : array();
		
		return $following;
	}
	
	//! SCR# 109 - Based on api/CARRIER_TRANSACTION_DATE determine transaction date
	//! SCR# 357 - updated to call GET_LOAD_ASOF()
	public function carrier_transaction_date( $pk ) {
		$check = false;
		if( ! isset($this->ctd) )
			$this->ctd = $this->setting_table->get( 'api', 'CARRIER_TRANSACTION_DATE' );
			
		$check = $this->database->get_one_row("
			SELECT GET_LOAD_ASOF($pk) DT");

		if( is_array($check) && ! empty($check["DT"]) ) {
			$result = $check["DT"];
		} else {
			$check = $this->database->get_one_row("
				SELECT DATE(COMPLETED_DATE) AS DT FROM EXP_LOAD
				WHERE LOAD_CODE = $pk");
			$result = is_array($check) && ! empty($check["DT"]) ? $check["DT"] : false;
		}
		
		return $result;
	}
	
	//! SCR# 634 - Reuse empty loads
	private function reuse_empty() {
		
		$result = false;
		$check = $this->database->get_one_row("
			SELECT LOAD_CODE, CREATED_DATE
			FROM EXP_LOAD
			WHERE CREATED_BY=".$_SESSION['EXT_USER_CODE']."
			AND CURRENT_STATUS=".$this->behavior_state['entry']."
			".($this->multi_company ? "AND OFFICE_CODE=".$_SESSION['EXT_USER_OFFICE'] : '')."
			AND NOT EXISTS (SELECT SHIPMENT_CODE FROM EXP_SHIPMENT
			WHERE EXP_SHIPMENT.LOAD_CODE = EXP_LOAD.LOAD_CODE)
			AND DRIVER IS NULL
			AND CARRIER IS NULL
			ORDER BY 2 DESC
			LIMIT 1");

		if( is_array($check) && ! empty($check["LOAD_CODE"])) {
			$result = $check["LOAD_CODE"];
		}
		
		return $result;
	}
	
	public function create_empty() {
		
		$result = false;
		if( $this->reuse_empty_loads )
			$result = $this->reuse_empty();
		
		if( ! $result ) {
			$entry_code = $this->behavior_state['entry'];
			assert( $entry_code > 0, "Unable to find entry status code" );
		
			$result = $this->add( array( 'CURRENT_STATUS' => $entry_code,
				'OFFICE_CODE' => $this->multi_company ? $_SESSION['EXT_USER_OFFICE'] : 0 ) );
		}
		
		return $result;
	}
	
	//! Check if we can use the special load complete feature
	// Can override the setting
	public function can_load_complete( $pk, $override = false ) {
		$result = false;
		
		if( $override || ($this->setting_table->get( 'option', 'LOAD_COMPLETE' ) == 'true') ) {
			if( $this->debug ) echo "<p>".__METHOD__.": load = $pk</p>";
			$check = $this->fetch_rows( "LOAD_CODE = ".$pk, "CURRENT_STATUS" );
			
			if( $this->debug ) {
				echo "<p>".__METHOD__.": check = </p><pre>";
				var_dump($check);
				echo "</pre>";				
			}			
			$result = is_array($check) && count($check) == 1 &&
				! empty($check[0]["CURRENT_STATUS"]) &&
				! in_array($this->state_behavior[$check[0]["CURRENT_STATUS"]], 
					array('entry','complete','oapproved','oapproved2','approved','billed', 'cancelled', 'imported'));
		}

		if( $this->debug ) echo "<p>".__METHOD__.": return ".($result ? 'true' : 'false').".</p>";
		return $result;
	}


	//! Special load complete feature, see option/LOAD_COMPLETE
	public function load_complete( $pk, $override = false ) {
		global $sts_crm_dir, $ifta_load_code;
		$result = false;
		
		$can_complete = $this->can_load_complete( $pk, $override );
		if( $this->debug ) echo "<p>".__METHOD__.": load = $pk can_complete = ".($can_complete ? 'true' : 'false').".</p>";
		if( $can_complete ) {

			$shipment_table = sts_shipment::getInstance($this->database, $this->debug);
			$stop_table = sts_stop::getInstance($this->database, $this->debug);
			$shipment_load_table = sts_shipment_load::getInstance($this->database, $this->debug);
	
			if( $this->debug ) echo "<p>".__METHOD__.": before shipment_table::update</p>";
			
			$shipments = $shipment_table->fetch_rows( "LOAD_CODE = ".$pk,
				"SHIPMENT_CODE, (SELECT STOP_TYPE FROM EXP_STOP s
					WHERE s.SHIPMENT = SHIPMENT_CODE
					AND s.LOAD_CODE = ".$pk."
					AND STOP_TYPE in ('drop', 'dropdock')) AS STOP_TYPE,
					(SELECT STOP_CODE FROM EXP_STOP s
					WHERE s.SHIPMENT = SHIPMENT_CODE
					AND s.LOAD_CODE = ".$pk."
					AND STOP_TYPE in ('drop', 'dropdock')) AS STOP_CODE");
			
			if( is_array($shipments) && count($shipments) > 0 ) {
				foreach($shipments as $s) {
					if( $s["STOP_TYPE"] == 'dropdock') {
						$dummy = $shipment_table->update(
							"SHIPMENT_CODE = ".$s["SHIPMENT_CODE"], 
							array( 'CURRENT_STATUS' =>
								$shipment_table->behavior_state['docked'],
								"DOCKED_AT" => $s["STOP_CODE"],
								"LOAD_CODE" => 0 ), false );
						$shipment_load_table->add_link( $s["SHIPMENT_CODE"], $pk );
					} else {
						$dummy = $shipment_table->update(
							"SHIPMENT_CODE = ".$s["SHIPMENT_CODE"], 
							array( 'CURRENT_STATUS' =>
								$shipment_table->behavior_state['dropped'] ), false );
						
						$shipment_load_table->add_link( $s["SHIPMENT_CODE"], $pk );
						
					}
				}
			}
	
			if( $this->debug ) echo "<p>".__METHOD__.": before stop_table::all_completed</p>";
			$dummy = $stop_table->all_completed( $pk );
			
			if( $this->debug ) echo "<p>".__METHOD__.": before fetch_rows</p>";
			$check = $this->fetch_rows( "LOAD_CODE = ".$pk, "DRIVER, TRAILER,
				(SELECT D.CURRENT_LOAD
						FROM EXP_DRIVER D
						WHERE DRIVER = D.DRIVER_CODE) AS DRIVER_LOAD,
				(SELECT COUNT(*) AS NUM_STOPS
						FROM EXP_STOP s
						WHERE s.LOAD_CODE = EXP_LOAD.LOAD_CODE) AS NUM_STOPS,
				(select cons_zip
						from exp_stop, exp_shipment
						where EXP_STOP.LOAD_CODE = EXP_LOAD.LOAD_CODE
						and EXP_STOP.sequence_no = (SELECT COUNT(*) AS NUM_STOPS
						FROM EXP_STOP s
						WHERE s.LOAD_CODE = EXP_STOP.LOAD_CODE)
						and shipment = shipment_code) AS CURRENT_ZONE" );
			if( $this->debug ) {
				echo "<p>".__METHOD__.": check = </p><pre>";
				var_dump($check);
				echo "</pre>";				
			}			
						
			if( is_array($check) && count($check) == 1 ) {
				$driver = isset($check[0]["DRIVER"]) ? $check[0]["DRIVER"] : 0;
				$driver_load = isset($check[0]["DRIVER_LOAD"])  ? $check[0]["DRIVER_LOAD"] : 0;
				$trailer = isset($check[0]["TRAILER"]) ? $check[0]["TRAILER"] : 0;
				$num_stops = $check[0]["NUM_STOPS"];
				$current_zone = $check[0]["CURRENT_ZONE"];
				$this->add_load_status( $pk, 'load_complete: driver = '.$driver.' driver_load = '.$driver_load );

				if( $this->debug ) {
					echo "<p>".__METHOD__.": vars = </p><pre>";
					var_dump($driver, $driver_load, $trailer, $num_stops, $current_zone);
					echo "</pre>";				
				}			
						
				if( $this->debug ) echo "<p>".__METHOD__.": before update</p>";
				$result = $this->update( $pk, 
					array( 'CURRENT_STATUS' => $this->behavior_state['complete'],
						'CURRENT_STOP' => $num_stops ) );

				if( ! $result ) {
					$email = sts_email::getInstance($this->database, $this->debug);
					$email->send_alert(__METHOD__.": update CURRENT_STATUS failed for $pk" );
				}
				
				// Reset the driver current load to 0, last load to $pk
				//! SCR# 324 - issue with repositioning
				//! SCR# 327 - driver = 0 means carrier, not an error
				if( $driver > 0 ) {
					$driver_table = sts_driver::getInstance($this->database, $this->debug);
					$dummy = $driver_table->update( $driver, 
						array( 'CURRENT_LOAD' => 0, 'LAST_LOAD' => $pk ) );
					//$this->add_load_status( $pk, 'load_complete: update d = '.$driver.' dll = '.$pk.' ret = '.$dummy);
					
				}

				if( $trailer > 0 ) {
					$trailer_table = sts_trailer::getInstance($this->database, $this->debug);
					$dummy = $trailer_table->update( $trailer, 
						array( 'LOCATION' => $current_zone ) );
				}

				// import IFTA logs from PC*Miler
				$ifta_load_code = $pk;
				require_once( "exp_spawn_log_miles.php" );
			} else {
				$this->add_load_status( $pk, 'load_complete: error 1');
				$email = sts_email::getInstance($this->database, $this->debug);
				$email->send_alert(__METHOD__.": could not retreve load information for $pk" );
			}
				
			$this->add_load_status( $pk, 'via load complete feature' );
		} else {
			// Unable to complete
			// This could be because it is called more than once. Not really an issue.
			
			//$email = sts_email::getInstance($this->database, $this->debug);
			//$email->send_alert(__METHOD__.": Unable to complete load<br>".
			//'<br>'.$email->load_history($pk) );
		}
		return $result;
	}
	
	public function add_load_status( $pk, $comment ) {
			
			$dummy = $this->status_table->add_load_status( $pk, $comment );
	}
	
	public function check_driver_available( $pk, $driver = 0 ) {
		$result = true;
		$dates = $this->database->get_multiple_rows(
			"SELECT DRIVER, MIN(DUE) EARLIEST_DATE, MAX(DUE) LATEST_DATE
			FROM EXP_LOAD, (
			SELECT SHIPMENT, 
			 TIMESTAMP(DELIVER_DATE,
			(case when coalesce(DELIVER_TIME1,'') = '' then '00:00'
			else
			concat_ws(':',substr(DELIVER_TIME1,1,2),substr(DELIVER_TIME1,3,2)) end)) AS DUE
			FROM EXP_STOP, EXP_SHIPMENT
			WHERE EXP_STOP.LOAD_CODE = ".$pk."
			AND SHIPMENT = SHIPMENT_CODE ) X
			WHERE LOAD_CODE = ".$pk."
			GROUP BY LOAD_CODE" );
			
		if( isset($dates) && is_array($dates) && count($dates) > 0 ) {
			if( $driver == 0 ) $driver = $dates[0]['DRIVER'];
			$vacation_table = sts_vacation::getInstance($this->database, $this->debug);
			$result = $vacation_table->check_available( $driver,
				$dates[0]['EARLIEST_DATE'], $dates[0]['LATEST_DATE'] );
		}
		return $result;
	}
	
	//! SCR# 240 - update last known stop for driver/tractor/trailer
	public function update_last_stop( $current_stop_code, $driver, $tractor, $trailer ) {
		if( $current_stop_code > 0 ) {
			if( $driver > 0 ) {
				$driver_table = sts_driver::getInstance($this->database, $this->debug);
				$dummy = $driver_table->update( $driver, 
					array( 'LAST_STOP' => $current_stop_code ) );
			}
			if( $tractor > 0 ) {
				$tractor_table = sts_tractor::getInstance($this->database, $this->debug);
				$dummy = $tractor_table->update( $tractor, 
					array( 'LAST_STOP' => $current_stop_code ) );
			}
			if( $trailer > 0 ) {
				$trailer_table = sts_trailer::getInstance($this->database, $this->debug);
				$dummy = $trailer_table->update( $trailer, 
					array( 'LAST_STOP' => $current_stop_code ) );
			}
		}		
	}
	
	public function state_change_ok( $pk, $current_state, $state, $current_stop, $num_stops, $current_stop_type ) {

		if( $this->debug ) echo "<p>sts_load > state_change_ok: pk = $pk, current_state = $current_state (".$this->state_behavior[$current_state]."), state = $state (".$this->state_behavior[$state]."), current_stop = $current_stop, num_stops = $num_stops, current_stop_type = $current_stop_type</p>";

		$this->state_change_error = '';
		$this->state_change_level = EXT_ERROR_WARNING;	// Default to warning
		$ok_to_continue = false;
		
		// Preconditions for changing state
		switch( $this->state_behavior[$state] ) {
			case 'entry': //! entry
				$ok_to_continue =  ! in_group( EXT_GROUP_DRIVER ); //! SCR# 342 - not drivers			
				break;
				
			case 'accepted': //! 204 accepted
				$shipment_table = sts_shipment::getInstance($this->database, $this->debug);
				
				$edi_info = $this->database->get_one_row(
					"SELECT  EXP_LOAD.EDI_204_PRIMARY, EDI_204_B204_SID, EDI_204_ORIGIN, 
					EDI_204_G6202_EXPIRES, EDI_204_ISA15_USAGE, EDI_990_STATUS
					FROM EXP_LOAD, EXP_SHIPMENT
					WHERE SHIPMENT_CODE = EXP_LOAD.EDI_204_PRIMARY
					AND EXP_LOAD.LOAD_CODE = ".$pk );
				$ok_to_continue =  is_array($edi_info) &&
					isset($edi_info['EDI_204_PRIMARY']) &&
					$edi_info['EDI_204_PRIMARY'] <> '';
				if( ! $ok_to_continue ) {
					$this->state_change_error = "missing 204 primary";
					break;
				}
				$ok_to_continue =  isset($edi_info['EDI_204_B204_SID']) &&
					$edi_info['EDI_204_B204_SID'] <> '';
				if( ! $ok_to_continue ) {
					$this->state_change_error = "missing 204 shipment ID";
					break;
				}
				$ok_to_continue =  isset($edi_info['EDI_204_ORIGIN']) &&
					$edi_info['EDI_204_ORIGIN'] <> '';
				if( ! $ok_to_continue ) {
					$this->state_change_error = "missing 204 origin";
					break;
				}
				$ok_to_continue =  isset($edi_info['EDI_204_G6202_EXPIRES']) &&
					$edi_info['EDI_204_G6202_EXPIRES'] <> '';
				if( ! $ok_to_continue ) {
					$this->state_change_error = "missing 204 expiry";
					break;
				}
				$ok_to_continue =  isset($edi_info['EDI_990_STATUS']) &&
					$edi_info['EDI_990_STATUS'] == 'Pending';
				if( ! $ok_to_continue ) {
					$this->state_change_error = "990 not in Pending status (".$edi_info['EDI_990_STATUS'].")";
					break;
				}
				$ok_to_continue = time() < strtotime($edi_info["EDI_204_G6202_EXPIRES"]);
				if( ! $ok_to_continue ) {
					$this->state_change_error = "204 expired, load cancelled";
					
					//! Cancel the load & shipments
					require_once( "sts_edi_map_class.php" );
					$edi = sts_edi_map::getInstance($this->database, $this->debug);
					$edi->cancel( $edi_info['EDI_204_B204_SID'], 'Expired' );
					
					break;
				}
				break;
				
			case 'manifest':	//! manifest
				$resources = $this->database->get_one_row("SELECT DRIVER, TRACTOR, TRAILER, CARRIER, CARRIER_BASE
					FROM EXP_LOAD
					WHERE ".$this->primary_key." = ".$pk );
				$carrier_assigned = $resources && 
					(isset($resources['CARRIER']) && $resources['CARRIER'] > 0) &&
					! (isset($resources['DRIVER']) && $resources['DRIVER'] > 0) &&
					! (isset($resources['TRACTOR']) && $resources['TRACTOR'] > 0);
				$ok_to_continue =  $num_stops > 0 && $carrier_assigned;
				
				if( ! $ok_to_continue ) {
					if( $num_stops <= 0 )
						$this->state_change_error = "#stops = $num_stops";
					else if( ! $carrier_assigned )
						$this->state_change_error = "no carrier";
				}
				if( $ok_to_continue ) {
					$contact = $this->database->get_multiple_rows(
						"SELECT ADDRESS, CITY, STATE, ZIP_CODE
						FROM EXP_CONTACT_INFO
						WHERE CONTACT_CODE = ".$resources['CARRIER']."
						AND ISDELETED = false
						AND CONTACT_SOURCE = 'carrier'
						AND CONTACT_TYPE IN ('company', 'carrier')" );
					$ok_to_continue = is_array($contact) &&
						isset($contact[0]['ADDRESS']) && $contact[0]['ADDRESS'] <> '' &&
						isset($contact[0]['CITY']) && $contact[0]['CITY'] <> '' &&
						isset($contact[0]['STATE']) && $contact[0]['STATE'] <> '' &&
						isset($contact[0]['ZIP_CODE']) && $contact[0]['ZIP_CODE'] <> '';
					if( $ok_to_continue ) {
						//! SCR# 397 - check if they have enough insurance
						if( $this->carrier_insufficient_ins && $carrier_assigned ) {
							$carrier_table = sts_carrier::getInstance($this->database, $this->debug);
							$check = $carrier_table->check_suitable( $pk, $resources['CARRIER'] );
							if( $check <> '' ) {
								$ok_to_continue = false;
								$this->state_change_error = "<strong>Carrier does not have enough insurance</strong>";
							}

						}
						
					} else {
						$this->state_change_error = "missing carrier address";
					}
				}
				
				break;
			
			case 'dispatch':	//! dispatch
				$resources = $this->database->get_one_row("SELECT DRIVER, TRACTOR, TRAILER, CARRIER,
					(SELECT D.CURRENT_LOAD
						FROM EXP_DRIVER D
						WHERE EXP_LOAD.DRIVER = D.DRIVER_CODE) AS DRIVER_LOAD,
					(SELECT L.CURRENT_STATUS 
						FROM EXP_LOAD L, EXP_DRIVER D
						WHERE EXP_LOAD.DRIVER = D.DRIVER_CODE
						AND L.LOAD_CODE = D.CURRENT_LOAD) AS CURRENT_STATUS
					FROM EXP_LOAD
					WHERE ".$this->primary_key." = ".$pk );
					
				$driver_assigned = $resources && isset($resources['DRIVER']) && $resources['DRIVER'] > 0;
				$tractor_assigned = $resources && isset($resources['TRACTOR']) && $resources['TRACTOR'] > 0;
				$trailer_assigned = $resources && isset($resources['TRAILER']) && $resources['TRAILER'] > 0;
				$carrier_assigned = $resources && 
					(isset($resources['CARRIER']) && $resources['CARRIER'] > 0) &&
					! (isset($resources['DRIVER']) && $resources['DRIVER'] > 0) &&
					! (isset($resources['TRACTOR']) && $resources['TRACTOR'] > 0);
				
				if( $this->dispatch_driver_only )
					$company = $driver_assigned && ! $carrier_assigned;
				else
					$company = $driver_assigned && $tractor_assigned && $trailer_assigned && ! $carrier_assigned;
				
				// the status of the current load for the driver or 0
				$current_status = $resources && isset($resources['CURRENT_STATUS']) ?
					$resources['CURRENT_STATUS'] : 0;
				
				$driver_available = $this->dispatch_driver_multiple ||
					$current_status == 0 ||
					(isset($this->state_behavior[$current_status]) &&
					in_array($this->state_behavior[$current_status], array('complete', 'cancel', 'oapproved', 'approved', 'billed') ) );
				
				$ok_to_continue =  $num_stops > 0 && ( ( $company && $driver_available ) ||
					($carrier_assigned && ($this->state_behavior[$current_state] == 'manifest' || $this->can_skip_manifest) ) );

				if( ! $ok_to_continue ) {
					if( $num_stops <= 0 )
						$this->state_change_error = "#stops = $num_stops";
					else {
						$issues = array();
						if( ! $driver_assigned && ! $carrier_assigned )
							$issues[] = "no carrier";
						if( ! $carrier_assigned && ! $driver_assigned )
							$issues[] = "no driver";
						if( ! $carrier_assigned && ! $tractor_assigned &&
							! $this->dispatch_driver_only )
							$issues[] = "no tractor";
						if( ! $carrier_assigned && ! $trailer_assigned &&
							! $this->dispatch_driver_only )
							$issues[] = "no trailer";
						if( $driver_assigned && ! $driver_available )
							$issues[] = "driver out on load ".(isset($resources['DRIVER_LOAD']) ? $resources['DRIVER_LOAD'] : 'unknown');
						if( $carrier_assigned && ! $this->state_behavior[$current_state] <> 'manifest' &&
							! $this->can_skip_manifest )
							$issues[] = "need to send freight agreement first (".$this->state_name[$current_state].")";
							
						$this->state_change_error = implode(', ', $issues);
					}
				} else {
					if( $this->carrier_insufficient_ins && $carrier_assigned ) {
						$carrier_table = sts_carrier::getInstance($this->database, $this->debug);
						$check = $carrier_table->check_suitable( $pk, $resources['CARRIER'] );
						if( $check <> '' ) {
							$ok_to_continue = false;
							$this->state_change_error = "<strong>Carrier does not have enough insurance</strong>";
						}

					}
				}
				break;
			
			case 'arrive shipper':	//! arrive shipper/depart shipper
			case 'depart shipper':
				$ok_to_continue =  $current_stop > 0 && 
					$current_stop < $num_stops &&
					$current_stop_type == 'pick';
				if( ! $ok_to_continue ) {
					$this->state_change_level = EXT_ERROR_ERROR;
					if( $current_stop <= 0 || $current_stop > $num_stops)
						$this->state_change_error = "current_stop = $current_stop";
					else if( $current_stop_type <> 'pick' )
						$this->state_change_error = "not a pick";
				}
				break;
			
			case 'arrshdock':	//! arrshdock/depshdock
			case 'depshdock':
				$ok_to_continue =  $current_stop > 0 && 
					$current_stop < $num_stops &&
					$current_stop_type == 'pickdock';
				if( ! $ok_to_continue ) {
					$this->state_change_level = EXT_ERROR_ERROR;
					if( $current_stop <= 0 || $current_stop > $num_stops)
						$this->state_change_error = "current_stop = $current_stop";
					else if( $current_stop_type <> 'pickdock' )
						$this->state_change_error = "not a pickdock";
				}
				break;
			
			case 'arrive cons':	//! arrive cons/depart cons
			case 'depart cons':
				$ok_to_continue =  $current_stop > 1 && 
					$current_stop <= $num_stops &&
					$current_stop_type == 'drop';
				if( $ok_to_continue ) {
					$stop_table = sts_stop::getInstance($this->database, $this->debug);
					$stop_status = $stop_table->fetch_rows( "LOAD_CODE = ".$pk." AND SEQUENCE_NO = ".$current_stop, "CURRENT_STATUS" );
					$ok_to_continue = isset($stop_status) && is_array($stop_status) && 
						count($stop_status) > 0 && 
						$stop_table->state_behavior[$stop_status[0]["CURRENT_STATUS"]] <> "complete";
					if( ! $ok_to_continue ) {
						$this->state_change_level = EXT_ERROR_ERROR;
						$this->state_change_error = "stop completed";
					}
				} else {
					$this->state_change_level = EXT_ERROR_ERROR;
					if( $current_stop <= 1 || $current_stop > $num_stops)
						$this->state_change_error = "current_stop = $current_stop";
					else if( $current_stop_type <> 'drop' )
						$this->state_change_error = "stop $current_stop ($current_stop_type) not a drop";
				}
				break;

			case 'arrrecdock':	//! arrrecdock/deprecdock
			case 'deprecdock':
				$ok_to_continue =  $current_stop > 1 && 
					$current_stop <= $num_stops &&
					$current_stop_type == 'dropdock';
				if( $ok_to_continue ) {
					$stop_table = sts_stop::getInstance($this->database, $this->debug);
					$stop_status = $stop_table->fetch_rows( "LOAD_CODE = ".$pk." AND SEQUENCE_NO = ".$current_stop, "CURRENT_STATUS" );
					$ok_to_continue = isset($stop_status) && is_array($stop_status) && 
						count($stop_status) > 0 &&
						$stop_table->state_behavior[$stop_status[0]["CURRENT_STATUS"]] <> "complete";
					if( ! $ok_to_continue ) {
						$this->state_change_level = EXT_ERROR_ERROR;
						$this->state_change_error = "stop completed";
					}
				} else {
					$this->state_change_level = EXT_ERROR_ERROR;
					if( $current_stop <= 1 || $current_stop > $num_stops)
						$this->state_change_error = "current_stop = $current_stop";
					else if( $current_stop_type <> 'dropdock' )
						$this->state_change_error = "not a drop";
				}
				break;

			case 'arrive stop':	//! arrive stop
				$ok_to_continue =  $current_stop >= 1 && 
					$current_stop <= $num_stops &&
					$current_stop_type == 'stop';
				if( ! $ok_to_continue ) {
					$this->state_change_level = EXT_ERROR_ERROR;
					if( $current_stop < 1 || $current_stop > $num_stops)
						$this->state_change_error = "current_stop = $current_stop";
					else if( $current_stop_type <> 'stop' )
						$this->state_change_error = "not a stop";
				}
				break;

			case 'depart stop':	//! depart stop
				$ok_to_continue =  $current_stop > 0 && 
					$current_stop <= $num_stops &&
					$current_stop_type == 'stop';
				if( ! $ok_to_continue ) {
					$this->state_change_level = EXT_ERROR_ERROR;
					$this->state_change_error = "no initial stop";
				}
				break;
				
			case 'cancel':	//! cancel
				$ok_to_continue = ! in_group( EXT_GROUP_DRIVER );
				if( ! $ok_to_continue ) {
					$this->state_change_error = "driver can't cancel loads";
				} else {
					$ok_to_continue = $this->state_behavior[$current_state] <> 'cancel';
					if( ! $ok_to_continue ) {
						$this->state_change_error = "already cancelled";
					} else {
						$ok_to_continue = ! in_array($this->state_behavior[$current_state],
							array('complete','oapproved','approved','billed'));
						if( ! $ok_to_continue ) {
							$this->state_change_error = "complete, approved or paid";
						} else {
							$check = $this->database->get_multiple_rows("SELECT COUNT(*) AS CNT
								FROM EXP_LOAD_PAY_RATE
								WHERE LOAD_ID = ".$pk );
							if( is_array($check) && count($check) == 1 && isset($check[0]) &&
								isset($check[0]["CNT"]) && $check[0]["CNT"] > 0)
								$ok_to_continue = false;
							if( ! $ok_to_continue ) {
								$this->state_change_error = "driver pay records exist for this load";
							}
						}				
					}
				}
				break;
				
			case 'complete':	//! complete
				if( in_array($this->state_behavior[$current_state],
					array('oapproved','approved', 'billed') ) ) {
					$ok_to_continue = in_group(EXT_GROUP_FINANCE);
					if( ! $ok_to_continue )
						$this->state_change_error = 'Your account to be in the finance group.';

				} else {
					$check = $this->database->get_multiple_rows("SELECT SHIPMENT_CODE
						FROM EXP_SHIPMENT
						WHERE LOAD_CODE = $pk
						AND (SELECT BEHAVIOR FROM EXP_STATUS_CODES
						WHERE CURRENT_STATUS = STATUS_CODES_CODE) 
							NOT IN ('dropped', 'docked', 'oapproved', 'approved', 'billed')");
					$ok_to_continue =  ! (is_array($check) && count($check) > 0 );

					if( $ok_to_continue ) {
						//! Check for a stop at the end
						$check = $this->database->get_multiple_rows("SELECT STOP_CODE, STOP_TYPE, SEQUENCE_NO
							FROM EXP_STOP
							WHERE LOAD_CODE = $pk
							AND SEQUENCE_NO = $current_stop
							AND (SELECT BEHAVIOR FROM EXP_STATUS_CODES
							WHERE CURRENT_STATUS = STATUS_CODES_CODE) 
								NOT IN ('complete')");
						
						if( is_array($check) && count($check) > 0 ) {
							$ok_to_continue = false;
							$this->state_change_level = EXT_ERROR_ERROR;
							$this->state_change_error = "stop not completed";
						}
					} else {
						$this->state_change_level = EXT_ERROR_ERROR;
						$this->state_change_error = "shipments not delivered";
					}
				}
				break;

			case 'oapproved':	//! oapproved
				$ok_to_continue = $this->multi_company && in_group(EXT_GROUP_BILLING);
				
				if( $ok_to_continue ) {
					//! SCR# 635 - allow zero carrier if lumper
					$check1 = $this->fetch_rows("CURRENT_STATUS <> ".$state." AND
						COALESCE(CARRIER,0) > 0 AND 
						(COALESCE(CARRIER_TOTAL,0) > 0.0 OR
						COALESCE(LUMPER_TOTAL,0) > 0.0) AND 
						EXP_LOAD.".$this->primary_key." = ".$pk, "EXP_LOAD.".$this->primary_key);
					$ok_to_continue =  is_array($check1) && count($check1) == 1 &&
						$check1[0][$this->primary_key] == $pk;
					if( ! $ok_to_continue )
						$this->state_change_error = "carrier missing or billing incomplete.";
					
					else if( $this->export_sage50 ) {
						$check2 = $this->fetch_rows("CURRENT_STATUS <> ".$state." AND
							COALESCE((SELECT SAGE50_VENDORID FROM EXP_CARRIER
							WHERE CARRIER = CARRIER_CODE),'') <> '' AND ".
							$this->primary_key." = ".$pk, $this->primary_key.
								", COALESCE(LUMPER,0) AS LUMPER");
						$ok_to_continue =  is_array($check2) && count($check2) == 1 &&
							$check2[0][$this->primary_key] == $pk;
				
						if( ! $ok_to_continue )				
							$this->state_change_error = 'The carrier needs to have a Sage Vendor ID (edit carrier profile) before you can export to Sage 50.';
						//! SCR# 635 - handle separate lumper bill payment	
						else if( isset($check2[0]["LUMPER"]) && $check2[0]["LUMPER"] > 0 ) {
							$check3 = $this->fetch_rows("CURRENT_STATUS <> ".$state." AND
								COALESCE((SELECT SAGE50_VENDORID FROM EXP_CARRIER
								WHERE LUMPER = CARRIER_CODE AND CARRIER_TYPE IN ('lumper', 'shag')),'') <> '' AND ".
								$this->primary_key." = ".$pk, $this->primary_key);
							$ok_to_continue =  is_array($check3) && count($check3) == 1 &&
								$check3[0][$this->primary_key] == $pk;

							if( ! $ok_to_continue )				
								$this->state_change_error = 'The lumper needs to have a Sage Vendor ID (edit carrier profile) before you can export to Sage 50.';

						}
					}
				} else
					$this->state_change_error = 'Needs multi-company enabled and your account to be in the billing group.';
				break;

			case 'approved':	//! approved
				$ok_to_continue = in_group(EXT_GROUP_FINANCE);
				
				if( $ok_to_continue ) {
					//! SCR# 635 - allow zero carrier if lumper
					$check1 = $this->fetch_rows("CURRENT_STATUS <> ".$state." AND
						COALESCE(CARRIER,0) > 0 AND 
						(COALESCE(CARRIER_TOTAL,0) > 0.0 OR
						COALESCE(LUMPER_TOTAL,0) > 0.0) AND 
						EXP_LOAD.".$this->primary_key." = ".$pk, "EXP_LOAD.".$this->primary_key);
					$ok_to_continue =  is_array($check1) && count($check1) == 1 &&
						$check1[0][$this->primary_key] == $pk;
					if( ! $ok_to_continue )
						$this->state_change_error = "carrier missing or billing incomplete.";
					else if( $this->export_sage50 ) {
						$check2 = $this->fetch_rows("CURRENT_STATUS <> ".$state." AND
							COALESCE((SELECT SAGE50_VENDORID FROM EXP_CARRIER
							WHERE CARRIER = CARRIER_CODE),'') <> '' AND ".
							$this->primary_key." = ".$pk, $this->primary_key.
								", COALESCE(LUMPER,0) AS LUMPER");
						$ok_to_continue =  is_array($check2) && count($check2) == 1 &&
							$check2[0][$this->primary_key] == $pk;
				
						if( ! $ok_to_continue )				
							$this->state_change_error = 'The carrier needs to have a Sage Vendor ID (edit carrier profile) before you can export to Sage 50.';
						//! SCR# 635 - handle separate lumper bill payment	
						else if( isset($check2[0]["LUMPER"]) && $check2[0]["LUMPER"] > 0 ) {
							$check3 = $this->fetch_rows("CURRENT_STATUS <> ".$state." AND
								COALESCE((SELECT SAGE50_VENDORID FROM EXP_CARRIER
								WHERE LUMPER = CARRIER_CODE AND CARRIER_TYPE IN ('lumper', 'shag')),'') <> '' AND ".
								$this->primary_key." = ".$pk, $this->primary_key);
							$ok_to_continue =  is_array($check3) && count($check3) == 1 &&
								$check3[0][$this->primary_key] == $pk;

							if( ! $ok_to_continue )				
								$this->state_change_error = 'The lumper needs to have a Sage Vendor ID (edit carrier profile) before you can export to Sage 50.';
						}
					}
				} else
					$this->state_change_error = 'Your account needs to be in the finance group.';
				break;

			case 'billed':	//! billed
				if( $this->export_quickbooks ) {
					$check = $this->fetch_rows("CURRENT_STATUS <> ".$state." AND
						quickbooks_listid_carrier <> '' AND 
						quickbooks_txnid_ap <> '' AND 
						EXP_LOAD.".$this->primary_key." = ".$pk, "EXP_LOAD.".$this->primary_key);
					$ok_to_continue =  $check && $check[0][$this->primary_key] == $pk;
					if( ! $ok_to_continue ) {
						$this->state_change_level = EXT_ERROR_ERROR;
						$this->state_change_error = 'Unable to set to billed state, Quickbooks issue.';
					}
				} else
					$ok_to_continue = true;
				break;

			default:
				$ok_to_continue = true;
				break;
		}
		if( $this->debug ) echo "<p>sts_load > state_change_ok: return ".($ok_to_continue ? "true" : "false : ".$this->state_change_error)."</p>";
		return $ok_to_continue;
	}
		
	// If the CURRENT_STOP is wrong, fix it
	public function fix_current_stop( $pk ) {

		if( $this->debug ) echo "<p>sts_load > fix_current_stop: $pk</p>";
		$new_current_stop = -1;
		$current_stop = -1;
		
		$stop_table = sts_stop::getInstance($this->database, $this->debug);
		$result = $this->fetch_rows( $this->primary_key.' = '.$pk, "CURRENT_STOP, CURRENT_STATUS,
			(SELECT COUNT(*) AS NUM_STOPS
				FROM EXP_STOP
				WHERE EXP_LOAD.LOAD_CODE = EXP_STOP.LOAD_CODE) AS NUM_STOPS" );
		if( isset($result) && is_array($result) && count($result) > 0 ) {
			$current_stop = $result[0]['CURRENT_STOP'];
			$current_status = $result[0]['CURRENT_STATUS'];
			$num_stops = $result[0]['NUM_STOPS'];
			
			if( $num_stops == 0 ||
				in_array( $this->state_behavior[$current_status],
					array('entry', 'manifest', 'imported', 'accepted' ) ) ) {
																				// CURRENT_STOP should be 0
				if( $current_stop <> 0 )
					$dummy = $this->update( $pk, array( 'CURRENT_STOP' => 0 ) );
				$new_current_stop = 0;

			} else if( $this->state_behavior[$current_status] == 'dispatch' ) { // CURRENT_STOP should be 1
				if( $current_stop <> 1 )
					$dummy = $this->update( $pk, array( 'CURRENT_STOP' => 1 ) );				
				$new_current_stop = 1;

			} else {	// Look at stops to work out CURRENT_STOP, look for first incomplete stop
				$stops = $stop_table->fetch_rows( "LOAD_CODE = ".$pk.
					" AND CURRENT_STATUS = ".$stop_table->behavior_state['entry'],
					"SEQUENCE_NO, STOP_TYPE, CURRENT_STATUS", "SEQUENCE_NO ASC");
				if( isset($stops) && is_array($stops) && count($stops) > 0 ) {
					$first_incomplete = $stops[0]['SEQUENCE_NO'];
					if( $current_stop <> $first_incomplete )
						$dummy = $this->update( $pk, array( 'CURRENT_STOP' => $first_incomplete ) );				
					$new_current_stop = $first_incomplete;

				} else {	// No remaining stops, then set CURRENT_STOP to $num_stops
					if( $current_stop <> $num_stops )
						$dummy = $this->update( $pk, array( 'CURRENT_STOP' => $num_stops ) );				
					$new_current_stop = $num_stops;
				}
			}
			if( $current_stop <> $new_current_stop )
				$dummy = $this->add_load_status( $pk, 'fix_current_stop: original = '.$current_stop.' new = '.$new_current_stop );
		}
		return $current_stop <> $new_current_stop;
	}
	
	//! Backup a load to a given stop
	//! TBD - update current zone, driver's current load and trailer location
	public function backup( $pk, $to_stop ) {
		
		if( $this->debug ) echo "<p>sts_load > backup( $pk, $to_stop )</p>";
		$dummy = $this->add_load_status( $pk, 'backup to stop '.$to_stop );
		$result = false;
		
		/* Testing $email->load_history
		$email = sts_email::getInstance($this->database, $this->debug);
		$email->send_alert("sts_load > backup($pk, $to_stop): TESTING - NOT AN ERROR<br>".
		'<br>'.$email->load_history($pk) );
		*/
		
		// Get load info
		$load_info = $this->fetch_rows( $this->primary_key.' = '.$pk, "CURRENT_STOP, CURRENT_STATUS, CARRIER, DRIVER");
		
		if( isset($load_info) && is_array($load_info) && count($load_info) > 0 ) {
			$current_stop = $load_info[0]['CURRENT_STOP'];
			$load_status = $load_info[0]['CURRENT_STATUS'];
			$driver = $load_info[0]['DRIVER'];
			$carrier = $load_info[0]['CARRIER'];
			
			if( in_array($this->state_behavior[$load_status], 
					array('arrive cons','arrive shipper','depart cons','depart shipper','depart stop', 'complete', 'arrrecdock', 'arrshdock', 'deprecdock', 'depshdock') ) && 
				$to_stop > 0 && $to_stop < $current_stop ) {
					
				// Get stops info
				$stops = $this->database->get_multiple_rows("
					SELECT STOP_CODE, SEQUENCE_NO, STOP_TYPE, SHIPMENT, CURRENT_STATUS,
					(SELECT CURRENT_STATUS FROM EXP_SHIPMENT
						WHERE SHIPMENT_CODE = SHIPMENT) AS SHIPMENT_STATUS
					FROM EXP_STOP
					WHERE LOAD_CODE = $pk
					ORDER BY SEQUENCE_NO ASC");
					
				if( isset($stops) && is_array($stops) && count($stops) > 0 ) {
					
					$stop_table = sts_stop::getInstance($this->database, $this->debug);
					$shipment_table = sts_shipment::getInstance($this->database, $this->debug);
					
					// Walk backwards through the stops, update shipments and stops
					for( $c = $current_stop; $c >= $to_stop; $c-- ) {
						if( $this->debug ) echo "<p>sts_load > backup: stop $c</p>";
						$this_stop = $stops[$c-1];
						if( in_array($this_stop['STOP_TYPE'], array('pick', 'pickdock') ) ) {
							if( $this->debug ) echo "<p>sts_load > update shipment ".$this_stop['SHIPMENT']." -> dispatch</p>";
							$shipment_result = $shipment_table->update( $this_stop['SHIPMENT'], 
								array( 'CURRENT_STATUS' => $shipment_table->behavior_state['dispatch'] ), false );
							if( ! $shipment_result ) {
								$save_error = $shipment_table->error();
								$email = sts_email::getInstance($this->database, $this->debug);
								$email->send_alert("sts_load > backup($pk, $to_stop): update shipment failed<br>".
								$shipment_table->primary_key.' = '.$this_stop['SHIPMENT']."<br>".
								$save_error.
								'<br>'.$email->load_history($pk)  );
							}
							
							if( $this->debug ) echo "<p>sts_load > update stop ".$this_stop['STOP_CODE']." -> entry</p>";
							$stop_result = $stop_table->update( $this_stop['STOP_CODE'],
								array( 'CURRENT_STATUS' => $stop_table->behavior_state['entry'],
								'ACTUAL_ARRIVE' => 'NULL', 'ACTUAL_DEPART'=> 'NULL' ) );
							if( ! $stop_result ) {
								$save_error = $stop_table->error();
								$email = sts_email::getInstance($this->database, $this->debug);
								$email->send_alert("sts_load > backup($pk, $to_stop): update stop failed<br>".
								$stop_table->primary_key.' = '.$this_stop['STOP_CODE']."<br>".
								$save_error.
								'<br>'.$email->load_history($pk) );
							}
							
							
						} else if( in_array($this_stop['STOP_TYPE'], array('drop', 'dropdock') ) ) {
							if( $this->debug ) echo "<p>sts_load > update shipment ".$this_stop['SHIPMENT']." -> picked</p>";
							$changes = array( 'CURRENT_STATUS' => $shipment_table->behavior_state['picked'] );

							//! For dropdock, need to restore the load_code
							// use sts_shipment_load::last_load to get the last load_code
							// and be sure it matches this load
							if( $this_stop['STOP_TYPE'] == 'dropdock' ) {
								$shipment_load_table = sts_shipment_load::getInstance($this->database, $this->debug);
								$last_load = $shipment_load_table->last_load( $this_stop['SHIPMENT'] );
								if( is_array($last_load) && isset($last_load[0]) &&
									isset($last_load[0]['LOAD_CODE']) && $last_load[0]['LOAD_CODE'] == $pk ) {
									$changes['LOAD_CODE'] =  $pk;
								} else {
									$email = sts_email::getInstance($this->database, $this->debug);
									$email->send_alert("sts_load > backup($pk, $to_stop): stop# ".$this_stop['SEQUENCE_NO'].
										" shipment# ".$this_stop['SHIPMENT'].
										" type=dropdock last_load=".(is_array($last_load) && isset($last_load[0]) &&
									isset($last_load[0]['LOAD_CODE']) ? $last_load[0]['LOAD_CODE'] : 'unknown').
									'<br>last_load unknown or does not match this load.'.
									'<br>'.$email->shipment_history($this_stop['SHIPMENT']).
									'<br>'.$email->load_history($pk) );
								}
							}
									
							$shipment_result = $shipment_table->update( $this_stop['SHIPMENT'], $changes, false );
							if( ! $shipment_result ) {
								$save_error = $shipment_table->error();
								$email = sts_email::getInstance($this->database, $this->debug);
								$email->send_alert("sts_load > backup($pk, $to_stop): update shipment failed<br>".
								$shipment_table->primary_key.' = '.$this_stop['SHIPMENT']."<br>".
								$save_error.
								'<br>'.$email->load_history($pk) );
							}
							
							
							if( $this->debug ) echo "<p>sts_load > update stop ".$this_stop['STOP_CODE']." -> entry</p>";
							$stop_result = $stop_table->update( $this_stop['STOP_CODE'],
								array( 'CURRENT_STATUS' => $stop_table->behavior_state['entry'],
								'ACTUAL_ARRIVE' => 'NULL', 'ACTUAL_DEPART'=> 'NULL' ) );
							if( ! $stop_result ) {
								$save_error = $stop_table->error();
								$email = sts_email::getInstance($this->database, $this->debug);
								$email->send_alert("sts_load > backup($pk, $to_stop): update stop failed<br>".
								$stop_table->primary_key.' = '.$this_stop['STOP_CODE']."<br>".
								$save_error.
								'<br>'.$email->load_history($pk) );
							}
							
						} else if( isset($this_stop['STOP_CODE']) ) {
							$stop_result = $stop_table->update( $this_stop['STOP_CODE'],
								array( 'CURRENT_STATUS' => $stop_table->behavior_state['entry'],
								'ACTUAL_ARRIVE' => 'NULL', 'ACTUAL_DEPART'=> 'NULL' ) );
							if( ! $stop_result ) {
								$save_error = $stop_table->error();
								$email = sts_email::getInstance($this->database, $this->debug);
								$email->send_alert("sts_load > backup($pk, $to_stop): update stop failed<br>".
								$stop_table->primary_key.' = '.$this_stop['STOP_CODE']."<br>".
								$save_error.
								'<br>'.$email->load_history($pk) );
							}
							
						} else {
								$email = sts_email::getInstance($this->database, $this->debug);
								$email->send_alert("sts_load > backup($pk, $to_stop): failed,".
								" this_stop['STOP_CODE'] not set <br>".
								"c = <pre>".print_r($c, true)."</pre><br>".
								"this_stop = <pre>".print_r($this_stop, true)."</pre><br>".
								"Stops = <pre>".print_r($stops, true)."</pre><br>".
								$email->load_history($pk) );
						}
					}
					
					// Update load info
					if( $to_stop == 1 ) {
						$new_status = $this->behavior_state['dispatch'];
					} else if( $stops[$to_stop - 2]['STOP_TYPE'] == 'pick' ) {
						$new_status = $this->behavior_state['depart shipper'];
					} else if( $stops[$to_stop - 2]['STOP_TYPE'] == 'drop' ) {
						$new_status = $this->behavior_state['depart cons'];

					} else if( $stops[$to_stop - 2]['STOP_TYPE'] == 'pickdock' ) {
						$new_status = $this->behavior_state['depshdock'];
					} else if( $stops[$to_stop - 2]['STOP_TYPE'] == 'dropdock' ) {
						$new_status = $this->behavior_state['deprecdock'];

					} else if( $stops[$to_stop - 2]['STOP_TYPE'] == 'stop' ) {
						$new_status = $this->behavior_state['depart stop'];
					}
					
					if( $this->debug ) echo "<p>sts_load > update load $pk, current_stop = $to_stop status = $new_status / ".
						$this->state_behavior[$new_status]."</p>";
					$result = $this->update( $pk, array( 'CURRENT_STOP' => $to_stop,
						'CURRENT_STATUS' => $new_status,
						'COMPLETED_DATE' => 'NULL' ) );
					
					//! SCR# 324 - reset driver current load, last load is lost.
					//! SCR# 327 - does not apply to carriers
					if( $driver > 0 ) {
						$driver_table = sts_driver::getInstance($this->database, $this->debug);
						$dummy = $driver_table->update( $driver, 
							array( 'CURRENT_LOAD' => $pk, 'LAST_LOAD' => 0 ) );
					}

					
					$dummy = $stop_table->add_load_status( $pk, "backup: current_stop = $to_stop status = $new_status / ".
						$this->state_behavior[$new_status]." ".$stop_table->list_stops( $pk ) );
				}
			}
		}
		return $result;
	}
	
	//! SCR# 328 - check for and remove duplicate stops
	public function remove_duplicate_stops( $pk ) {
		$result = false;
		if( $this->debug ) echo "<p>".__METHOD__.": Check load $pk for duplicate stops</p>";
		$stop_table = sts_stop::getInstance($this->database, $this->debug);

		$check_dup_stops = $stop_table->fetch_rows("LOAD_CODE = $pk
			AND COALESCE(SHIPMENT, 0) > 0
			AND STOP_TYPE <> 'STOP'
			AND STOP_CODE < (SELECT MAX(STOP_CODE)
				FROM EXP_STOP T
				WHERE T.LOAD_CODE = EXP_STOP.LOAD_CODE
				AND T.STOP_TYPE = EXP_STOP.STOP_TYPE
				AND T.SHIPMENT = EXP_STOP.SHIPMENT)", "STOP_CODE");
		
		if( is_array($check_dup_stops) && count($check_dup_stops) > 0 ) {	// Found some!
			$found_dup = array();
			foreach($check_dup_stops as $row) {
				$found_dup[] = $row["STOP_CODE"];
			}
			$result = $stop_table->delete_row( "STOP_CODE IN (".implode(', ', $found_dup).")");
			$dummy = $this->add_load_status( $pk, 'Found & removed '.count($check_dup_stops).' duplicate stops' );
			$stop_table->renumber( $pk );
		}
		return $result;
	}
	
	public function update_distances( $pk, $verbose = false, $stop_changed = 0 ) {

		if( $this->debug ) echo "<p>".__METHOD__.": $pk</p>";

		$stop_table = sts_stop::getInstance($this->database, $this->debug);
		$zip_table = sts_zip::getInstance($this->database, $this->debug);
		
		//! SCR# 328 - check for and remove duplicate stops
		$this->remove_duplicate_stops( $pk );
		
		if( $stop_changed == 0 )
			$stop_table->renumber( $pk );	//! Try to reduce chance of missing stop numbers.
		
		$result = $this->database->get_multiple_rows("
			SELECT STOP_CODE, SEQUENCE_NO, STOP_TYPE,
				(CASE STOP_TYPE
					WHEN 'pick' THEN SHIPPER_NAME
					WHEN 'drop' THEN CONS_NAME
					ELSE STOP_NAME END
				) AS NAME, 
				(CASE STOP_TYPE
					WHEN 'pick' THEN SHIPPER_ADDR1
					WHEN 'drop' THEN CONS_ADDR1
					ELSE STOP_ADDR1 END
				) AS ADDRESS, 
				(CASE STOP_TYPE
					WHEN 'pick' THEN SHIPPER_ADDR2
					WHEN 'drop' THEN CONS_ADDR2
					ELSE STOP_ADDR2 END
				) AS ADDRESS2, 
				(CASE STOP_TYPE
					WHEN 'pick' THEN SHIPPER_CITY
					WHEN 'drop' THEN CONS_CITY
					ELSE STOP_CITY END
				) AS CITY, 
				(CASE STOP_TYPE
					WHEN 'pick' THEN SHIPPER_STATE
					WHEN 'drop' THEN CONS_STATE
					ELSE STOP_STATE END
				) AS STATE, 
				(CASE STOP_TYPE
					WHEN 'pick' THEN SHIPPER_ZIP
					WHEN 'drop' THEN CONS_ZIP
					ELSE STOP_ZIP END
				) AS ZIP_CODE,
				(CASE STOP_TYPE
					WHEN 'pick' THEN SHIPPER_COUNTRY
					WHEN 'drop' THEN CONS_COUNTRY
					ELSE STOP_COUNTRY END
				) AS COUNTRY

			FROM EXP_STOP
			LEFT JOIN EXP_SHIPMENT
			ON SHIPMENT = SHIPMENT_CODE
			WHERE EXP_STOP.LOAD_CODE = ".$pk."
			ORDER BY SEQUENCE_NO ASC" );
		
		if( is_array($result) && count($result) > 0 ) {
			$previous_zip = '';
			$prev_stop = 0;
			$empty_miles = 0;
			$total_distance = 0;
			$count_shipments_on_board = 0;
			foreach( $result as $stop ) {
				if( $verbose ) {
					echo $stop['SEQUENCE_NO']." ";
					ob_flush(); flush();
				}
				if( $stop['SEQUENCE_NO'] > 1 && is_array($prev_stop) &&
					($stop_changed == 0 ||						// update all stops
					$stop['STOP_CODE'] == $stop_changed ||		// update current
					$prev_stop['STOP_CODE'] == $stop_changed )	// update previous
				) {
					$address1 = array();
					$address2 = array();
					
					if( isset($prev_stop['ADDRESS']) && $prev_stop['ADDRESS'] <> '' )
						$address1['ADDRESS'] = $prev_stop['ADDRESS'];
					if( isset($prev_stop['ADDRESS2']) && $prev_stop['ADDRESS2'] <> '' )
						$address1['ADDRESS2'] = $prev_stop['ADDRESS2'];
					if( isset($prev_stop['CITY']) && $prev_stop['CITY'] <> '' )
						$address1['CITY'] = $prev_stop['CITY'];
					if( isset($prev_stop['STATE']) && $prev_stop['STATE'] <> '' )
						$address1['STATE'] = $prev_stop['STATE'];
					if( isset($prev_stop['ZIP_CODE']) && $prev_stop['ZIP_CODE'] <> '' )
						$address1['ZIP_CODE'] = $prev_stop['ZIP_CODE'];
					if( isset($prev_stop['COUNTRY']) && $prev_stop['COUNTRY'] <> '' )
						$address1['COUNTRY'] = $prev_stop['COUNTRY'];
				
					if( isset($stop['ADDRESS']) && $stop['ADDRESS'] <> '' )
						$address2['ADDRESS'] = $stop['ADDRESS'];
					if( isset($stop['ADDRESS2']) && $stop['ADDRESS2'] <> '' )
						$address2['ADDRESS2'] = $stop['ADDRESS2'];
					if( isset($stop['CITY']) && $stop['CITY'] <> '' )
						$address2['CITY'] = $stop['CITY'];
					if( isset($stop['STATE']) && $stop['STATE'] <> '' )
						$address2['STATE'] = $stop['STATE'];
					if( isset($stop['ZIP_CODE']) && $stop['ZIP_CODE'] <> '' )
						$address2['ZIP_CODE'] = $stop['ZIP_CODE'];
					if( isset($stop['COUNTRY']) && $stop['COUNTRY'] <> '' )
						$address2['COUNTRY'] = $stop['COUNTRY'];


					$distance = intval( $zip_table->get_distance_various(	
						$address1, $address2 ) );
					if( $this->debug ) echo "<p>sts_load > update_distances: stop ".
					$stop['SEQUENCE_NO']." distance ".$distance."</p>";
					if( $distance >= 0 )
						$result = $stop_table->update( $stop['STOP_CODE'],
							array( "STOP_DISTANCE" => $distance,
							'DISTANCE_SOURCE' => $zip_table->get_source(),
							'DISTANCE_LAST_AT' => $zip_table->get_last_at() ) );
				} else
					$distance = 0;
				
				if( $this->debug ) echo "<p>sts_load > update_distances: stop ".$stop['SEQUENCE_NO']." distance ".$distance." shipments ".$count_shipments_on_board."</p>";
				if( $stop['SEQUENCE_NO'] > 1 &&  $count_shipments_on_board == 0 && $distance >= 0 )
					$empty_miles += $distance;

				if( $stop['STOP_TYPE'] == 'pick' ) $count_shipments_on_board++;
				else if( $stop['STOP_TYPE'] == 'drop' ) $count_shipments_on_board--;
					
				if( $distance >= 0 )
					$total_distance += $distance;
				$prev_stop = $stop;
			}
			
			$result = $this->update( $pk, array( "TOTAL_DISTANCE" => $total_distance,
				"EMPTY_DISTANCE" => $empty_miles ) );
			
			$dummy = $this->add_load_status( $pk, 'update_distances: '.$total_distance.' '.$empty_miles );
		}
		
		$this->fix_current_stop( $pk );
		if( $this->debug ) echo "<p>sts_load > update_distances: exit.</p>";
	}
	
	// Add a stop to reposition the driver to the first stop
	//! SCR# 324 - updated to allow last stop to be drop or stop
	private function reposition_driver( $pk, $driver_load ) {
		
		// Get shipment info from last stop of previous load
		$prev_address = $this->database->get_one_row("SELECT
				(CASE STOP_TYPE WHEN 'drop' THEN SHIPMENT
					ELSE NULL END
				) AS SHIPMENT, 
				(CASE STOP_TYPE WHEN 'drop' THEN CONS_NAME
					ELSE STOP_NAME END
				) AS NAME, 
				(CASE STOP_TYPE WHEN 'drop' THEN CONS_ADDR1
					ELSE STOP_ADDR1 END
				) AS ADDR1, 
				(CASE STOP_TYPE WHEN 'drop' THEN CONS_ADDR2
					ELSE STOP_ADDR2 END
				) AS ADDR2, 
				(CASE STOP_TYPE WHEN 'drop' THEN CONS_CITY
					ELSE STOP_CITY END
				) AS CITY, 
				(CASE STOP_TYPE WHEN 'drop' THEN CONS_STATE
					ELSE STOP_STATE END
				) AS STATE, 
				(CASE STOP_TYPE WHEN 'drop' THEN CONS_ZIP
					ELSE STOP_ZIP END
				) AS ZIP,
				(CASE STOP_TYPE WHEN 'drop' THEN CONS_COUNTRY
					ELSE STOP_COUNTRY END
				) AS COUNTRY,
				(CASE STOP_TYPE WHEN 'drop' THEN CONS_PHONE
					ELSE STOP_PHONE END
				) AS PHONE,
				(CASE STOP_TYPE WHEN 'drop' THEN CONS_EXT
					ELSE STOP_EXT END
				) AS EXT,
				(CASE STOP_TYPE WHEN 'drop' THEN CONS_FAX
					ELSE STOP_FAX END
				) AS FAX,
				(CASE STOP_TYPE WHEN 'drop' THEN CONS_CELL
					ELSE STOP_CELL END
				) AS CELL,
				(CASE STOP_TYPE WHEN 'drop' THEN CONS_CONTACT
					ELSE STOP_CONTACT END
				) AS CONTACT,
				(CASE STOP_TYPE WHEN 'drop' THEN CONS_EMAIL
					ELSE STOP_EMAIL END
				) AS EMAIL
			
			FROM EXP_STOP
			LEFT JOIN EXP_SHIPMENT
			ON EXP_STOP.SHIPMENT = EXP_SHIPMENT.SHIPMENT_CODE
            AND STOP_TYPE IN ('stop', 'drop')
			
            WHERE EXP_STOP.LOAD_CODE = $driver_load
			AND SEQUENCE_NO = (SELECT MAX(S.SEQUENCE_NO)
				FROM EXP_STOP S
				WHERE S.LOAD_CODE = EXP_STOP.LOAD_CODE)");
			
		if( $prev_address ) {
			// Add an initial stop from where the driver was last
			$stop_table = sts_stop::getInstance($this->database, $this->debug);
			
			$stop_fields = [ "LOAD_CODE" => $pk, "SEQUENCE_NO" => 0,
				"STOP_TYPE" => 'stop',
				"SHIPMENT" => (isset($prev_address['SHIPMENT']) ?
					$prev_address['SHIPMENT'] : 0),
				"STOP_NAME" => $prev_address['NAME'],
				"STOP_ADDR1" => $prev_address['ADDR1'],
				"STOP_ADDR2" => $prev_address['ADDR2'],
				"STOP_CITY" => $prev_address['CITY'],
				"STOP_STATE" => $prev_address['STATE'],
				"STOP_ZIP" => $prev_address['ZIP'],
				"STOP_PHONE" => $prev_address['PHONE'],
				"STOP_EXT" => $prev_address['EXT'],
				"STOP_FAX" => $prev_address['FAX'],
				"STOP_CELL" => $prev_address['CELL'],
				"STOP_CONTACT" => $prev_address['CONTACT'],
				"STOP_EMAIL" => $prev_address['EMAIL'],
				"CURRENT_STATUS" => $stop_table->behavior_state['entry'],
				"IM_STOP_TYPE" => 'reposition' ];
				
			$dummy = $stop_table->add( $stop_fields );
			$this->add_load_status( $pk, 'Add repositioning stop from load '.$driver_load );
			
			$stop_table->renumber( $pk );

			// Update the distances in the stops
			$this->update_distances( $pk );
			
			// Immediately bump the state to Depart Stop
			$this->change_state( $pk, $this->behavior_state['depart stop'] );
		}
	}
	
	// Remove the repositioning stop
	private function undo_reposition_driver( $pk ) {
		$stop_table = sts_stop::getInstance($this->database, $this->debug);
		
		$check = $stop_table->fetch_rows( "LOAD_CODE = ".$pk."
				AND SEQUENCE_NO = 1
				AND STOP_TYPE = 'stop'", "STOP_CODE" );
				
		if( is_array($check) && count($check) > 0 ) {
			$stop_table->delete_row( "LOAD_CODE = ".$pk."
				AND SEQUENCE_NO = 1
				AND STOP_TYPE = 'stop'" );
			
			$this->add_load_status( $pk, 'Remove repositioning stop' );

			$stop_table->renumber( $pk );

			// Update the distances in the stops
			$this->update_distances( $pk );
		}
	}
	
	// Change state for load $pk to state $state
	// Optional params $cstop = current stop and $cstate = current state
	public function change_state( $pk, $state, $cstop = -1, $cstate = -1 ) {
		global $sts_qb_dsn, $sts_crm_dir, $sts_error_level_label, $ifta_load_code;
		
		if( $this->debug ) echo "<p>sts_load > change_state: $pk, $state ".
			(isset($this->state_name[$state]) ? $this->state_name[$state] : 'unknown')." / ".
			(isset($this->state_behavior[$state]) ? $this->state_behavior[$state] : 'unknown')."</p>";
		
		$this->state_change_post = '';

		//! Fetch current state etc.
		$result = $this->database->get_multiple_rows(
			"SELECT CURRENT_STATUS, CURRENT_STOP, NUM_STOPS, CURRENT_SHIPMENT, CURRENT_STOP_TYPE,
				CURRENT_STOP_CODE, DRIVER, TRACTOR, TRAILER, TOTAL_DISTANCE, CARRIER,
				CHANGED_BY, CHANGED_DATE, SS_NUMBER,
			(SELECT D.CURRENT_LOAD
						FROM EXP_DRIVER D
						WHERE DRIVER = D.DRIVER_CODE) AS DRIVER_LOAD,
			(SELECT D.LAST_LOAD
						FROM EXP_DRIVER D
						WHERE DRIVER = D.DRIVER_CODE) AS DRIVER_LAST_LOAD,
			(SELECT CASE CURRENT_STOP_TYPE
					WHEN 'pick' THEN SHIPPER_ZIP
					ELSE CONS_ZIP
				END AS CURRENT_ZONE
				FROM EXP_SHIPMENT
				WHERE SHIPMENT_CODE = CURRENT_SHIPMENT) AS CURRENT_ZONE
			FROM(
			select CURRENT_STATUS, CURRENT_STOP, DRIVER, TRACTOR, TRAILER, TOTAL_DISTANCE,
			CARRIER,
			(SELECT COUNT(*) AS NUM_STOPS
				FROM EXP_STOP
				WHERE EXP_LOAD.LOAD_CODE = EXP_STOP.LOAD_CODE) AS NUM_STOPS,
			(SELECT SHIPMENT
				FROM EXP_STOP
				WHERE EXP_LOAD.LOAD_CODE = EXP_STOP.LOAD_CODE
				AND SEQUENCE_NO = CURRENT_STOP) AS CURRENT_SHIPMENT,
			(SELECT STOP_TYPE AS CURRENT_STOP_TYPE
				FROM EXP_STOP
				WHERE EXP_LOAD.LOAD_CODE = EXP_STOP.LOAD_CODE
				AND SEQUENCE_NO = CURRENT_STOP) AS CURRENT_STOP_TYPE,
			(SELECT STOP_CODE AS CURRENT_STOP_CODE
				FROM EXP_STOP
				WHERE EXP_LOAD.LOAD_CODE = EXP_STOP.LOAD_CODE
				AND SEQUENCE_NO = CURRENT_STOP) AS CURRENT_STOP_CODE,
			(SELECT USERNAME from EXP_USER X
				WHERE X.USER_CODE = EXP_LOAD.CHANGED_BY LIMIT 0 , 1) AS CHANGED_BY,
			CHANGED_DATE, LOAD_OFFICE_NUM(LOAD_CODE) AS SS_NUMBER
			FROM EXP_LOAD
			WHERE ".$this->primary_key." = ".$pk.") X" );
		
		if( is_array($result) && isset($result[0]) && isset($result[0]['CURRENT_STATUS']) ) {
			$current_state = $result[0]['CURRENT_STATUS'];
			$current_stop = isset($result[0]['CURRENT_STOP']) ? $result[0]['CURRENT_STOP'] : 0;
			$num_stops = isset($result[0]['NUM_STOPS']) ? $result[0]['NUM_STOPS'] : 0;
			$current_stop_type = isset($result[0]['CURRENT_STOP_TYPE']) ? $result[0]['CURRENT_STOP_TYPE'] : '';
			$current_stop_code = isset($result[0]['CURRENT_STOP_CODE']) ? $result[0]['CURRENT_STOP_CODE'] : '';
			$current_shipment = isset($result[0]['CURRENT_SHIPMENT']) ? $result[0]['CURRENT_SHIPMENT'] : 0;
			$current_zone = isset($result[0]['CURRENT_ZONE']) ? $result[0]['CURRENT_ZONE'] : 0;
			$driver = isset($result[0]['DRIVER']) ? $result[0]['DRIVER'] : 0;
			$carrier = isset($result[0]['CARRIER']) ? $result[0]['CARRIER'] : 0;
			$driver_load = isset($result[0]['DRIVER_LOAD']) ? $result[0]['DRIVER_LOAD'] : 0;
			$driver_last_load = isset($result[0]['DRIVER_LAST_LOAD']) ? $result[0]['DRIVER_LAST_LOAD'] : 0;
			$tractor = isset($result[0]['TRACTOR']) ? $result[0]['TRACTOR'] : 0;
			$trailer = isset($result[0]['TRAILER']) ? $result[0]['TRAILER'] : 0;
			$total_distance = isset($result[0]['TOTAL_DISTANCE']) ? $result[0]['TOTAL_DISTANCE'] : 0;
			$changed_by = isset($result[0]['CHANGED_BY']) ? $result[0]['CHANGED_BY'] : "unknown";
			$changed_date = isset($result[0]['CHANGED_DATE']) ? $result[0]['CHANGED_DATE'] : 0;
			$office_num = isset($result[0]['SS_NUMBER']) ? $result[0]['SS_NUMBER'] : 0;
			
			if( $this->debug ) echo "<p>sts_load > change_state: current_state = $current_state, 
				current_stop = $current_stop, num_stops = $num_stops, 
				current_stop_type = $current_stop_type, current_shipment = $current_shipment,
				current_zone = $current_zone, cstop = $cstop, cstate = $cstate</p>";
			
			// !Check to see if the current stop or the current state do not match.
			// This likely means a screen is out of date, and someone already updated the load.
			if( ($cstop > 0 && $cstop <> $current_stop) ||
				($cstate > 0 && $cstate <> $current_state) ) {
				$ok_to_continue = false;
				$this->state_change_level = EXT_ERROR_WARNING;
				$this->state_change_error = "Load information is out of date. Likely someone updated it before you could.<br>".
					" Load <strong>".$pk."</strong> was last updated by <strong>".$changed_by."</strong>".
					($changed_date <> 0 ? " on <strong>".date("m/d H:i", strtotime($changed_date))."</strong>" : "")."<br>
					This is not an error. You can view load $pk and check the history tab for full details.<br><br>".
					" (current_stop = $current_stop, given = $cstop,".
					" current_state = $current_state, given = $cstate)<br>"
					;
			} else {
				// Check the new state is a valid transition
				$following = $this->following_states( $current_state );
				$match = false;
				if( is_array($following) && count($following) > 0 ) {
					foreach( $following as $row ) {
						if( $row['CODE'] == $state ) $match = true;
					}
				}
				
				if( ! $match ) {
					$this->state_change_level = EXT_ERROR_ERROR;
					$this->state_change_error = "(stop $current_stop of $num_stops) Load $pk cannot transition from ".$this->state_name[$current_state]." to ".$this->state_name[$state];
					$ok_to_continue = false;
				} else {
					// Preconditions for changing state
					$ok_to_continue = $this->state_change_ok( $pk, $current_state, $state, 
						$current_stop, $num_stops, $current_stop_type );
				}
			}

			if( $ok_to_continue ) {
				// Update the state to the new state
				$changes = array( 'CURRENT_STATUS' => $state );
				if( in_array($this->state_behavior[$state],
					array('arrive shipper', 'depart shipper', 'arrive cons', 'depart cons', 
						'arrive stop', 'depart stop', 
						'arrshdock', 'depshdock', 'arrrecdock', 'deprecdock')) )
					$changes['CURRENT_ZONE'] = $current_zone;
				$result = $this->update( $pk."
					AND CURRENT_STATUS <> ".$state, $changes );
				
				if( $result ) {
					// Post state change triggers
					$user_log = sts_user_log::getInstance($this->database, $this->debug);
					switch( $this->state_behavior[$state] ) {
						case 'entry': //! entry
							$dummy = $this->update( $pk, 
								array( 'CURRENT_STOP' => 0,
									'DISPATCHED_DATE' => 'NULL',	// Clear out dates
									'COMPLETED_DATE' => 'NULL' ) );
							
							// Reset the driver current load to the last load (before this one)
							if( $driver > 0 && $driver_load == $pk ) {
								$driver_table = sts_driver::getInstance($this->database, $this->debug);
								$dummy = $driver_table->update( $driver, 
									array( 'CURRENT_LOAD' => $driver_last_load ),
									array( 'LAST_LOAD' => 0 ) );
	
								if( $this->repositioning_stop && $driver_load > 0 ) {
									$this->undo_reposition_driver( $pk );
								}
							}
						
							break;
							
						case 'imported': //! 204 imported
							$shipment_table = sts_shipment::getInstance($this->database, $this->debug);
							$check = $this->fetch_rows( $this->primary_key.' = '.$pk, "EDI_204_PRIMARY");
							if( is_array($check) && isset($check[0]) && isset($check[0]["EDI_204_PRIMARY"])) {
								$shipment_table->update( 'EDI_204_PRIMARY = '.$check[0]['EDI_204_PRIMARY'],
									array( "EDI_990_STATUS" => 'Pending' ), false );

							}
							
							break;

						case 'accepted': //! 204 accepted
							$check = $this->fetch_rows( $this->primary_key.' = '.$pk, "EDI_204_PRIMARY");
							if( is_array($check) && isset($check[0]) && isset($check[0]["EDI_204_PRIMARY"])) {
								require_once( "sts_edi_trans_class.php" );
	
								$edi = sts_edi_trans::getInstance($this->database, $this->debug);
								$edi->accept_204( intval($check[0]["EDI_204_PRIMARY"]) );
							}
							break;

						case 'dispatch':	//! Dispatched ++CURRENT_STOP
							$this->add_load_status( $pk, 'dispatch '.$pk.' dl = '.$driver_load.' dll = '.$driver_last_load );
							$dummy = $this->update( $pk, 
								array( 'CURRENT_STOP' => ($current_stop + 1) ) );
							
							// Change states for shipments
							$shipment_table = sts_shipment::getInstance($this->database, $this->debug);
							$dummy = $shipment_table->load_change_state( $pk, 'dispatch' );
							
							// Update for driver
							if( $driver > 0  ) {
								$driver_table = sts_driver::getInstance($this->database, $this->debug);
								// Change current_load for driver
								$dummy = $driver_table->update( $driver, 
									array( 'CURRENT_LOAD' => $pk ) );
								
								if( $this->repositioning_stop && $driver_last_load > 0 ) {
									$this->reposition_driver( $pk, $driver_last_load );
								}
								
								//! Trigger send driver manifest
								if( $this->driver_manifest )
									$this->state_change_post =
										$this->state_behavior[$state].','.$current_stop_code;
							}
	
							break;
	
						case 'manifest':		//! Manifest - Set amount, send manifest
												// See exp_load_state.php
							$this->state_change_post = $this->state_behavior[$state].','.$current_stop_code;
							//! SCR# 774 - Set terms to carrier default terms
							if( $carrier > 0 ) {
								$carrier_table = sts_carrier::getInstance($this->database, $this->debug);
								$checkc = $carrier_table->fetch_rows( "CARRIER_CODE = ".$carrier,
									"TERMS");
								if( is_array($checkc) && count($checkc) == 1 &&
									isset($checkc[0]["TERMS"]) && $checkc[0]["TERMS"] > 0 ) {
									
									$dummy = $this->update( $pk, 
										array( 'TERMS' => $checkc[0]["TERMS"] ) );
								}
							}

							break;
						
						
						case 'cancel':		//! Cancelled, free up shipments, delete stops
							$shipment_table = sts_shipment::getInstance($this->database, $this->debug);
							$shipment_load_table = sts_shipment_load::getInstance($this->database, $this->debug);
							$check = $this->fetch_rows( $this->primary_key.' = '.$pk, "EDI_204_PRIMARY");
							
							if( is_array($check) && isset($check[0]) && 
								isset($check[0]["EDI_204_PRIMARY"])) {
								require_once( "sts_edi_trans_class.php" );
								$edi = sts_edi_trans::getInstance($this->database, $this->debug);
								// Decline the 204 via a 990
								$edi_primary = intval($check[0]["EDI_204_PRIMARY"]);
								$is_declined =  $edi->decline_204( $edi_primary );
							}

							$dispzero = $shipment_table->fetch_rows( "LOAD_CODE = ".$pk, "SHIPMENT_CODE" );
	
							if( is_array($dispzero) && count($dispzero) > 0 ) {
								foreach( $dispzero as $row ) {
									$remove_shipment = $row['SHIPMENT_CODE'];
									$check_docked = $shipment_load_table->last_load( $remove_shipment );
									if( isset($check_docked) && count($check_docked) > 0 &&
										isset($check_docked[0]["DOCKED_AT"]) && $check_docked[0]["DOCKED_AT"] > 0 ) {	// Docked
										$dummy = $shipment_table->update( $remove_shipment,
											array(
												"LOAD_CODE" => 0,
												"DOCKED_AT" => $check_docked[0]["DOCKED_AT"],
												"CURRENT_STATUS" => $shipment_table->behavior_state['docked']
											 ), false );
										$shipment_table->add_shipment_status( $remove_shipment, "cancel load: set LOAD_CODE=0 DOCKED_AT=".$check_docked[0]["DOCKED_AT"]." STATUS=".$shipment_table->behavior_state['docked'] );

										
									} else {	// Ready Dispatch
										$dummy = $shipment_table->update( $remove_shipment,
											array(
												"LOAD_CODE" => 0,
												"CURRENT_STATUS" => $shipment_table->behavior_state['assign']
											 ), false );
										$shipment_table->add_shipment_status( $remove_shipment, "cancel load: set LOAD_CODE=0 STATUS=".$shipment_table->behavior_state['assign'] );
									}
								}
							}
							
							//! SCR# 577 - remove entries from exp_shipment_load
							$shipment_load_table = sts_shipment_load::getInstance($this->database, $this->debug);
							$dummy = $shipment_load_table->delete_row( "LOAD_CODE = ".$pk );
						
							$stop_table = sts_stop::getInstance($this->database, $this->debug);
							$dummy = $stop_table->delete_row( "LOAD_CODE = ".$pk );
							
							//! SCR# 555 - zero numbers for cancelled loads
							$dummy = $this->update( $pk, 
								array( 'CURRENT_STOP' => 0,
									'CARRIER_BASE' => 0,
									'CARRIER_FSC' => 0,
									'CARRIER_HANDLING' => 0 ) );
	
							// Reset the driver current load
							if( $driver > 0 && $driver_load == $pk ) {
								$driver_table = sts_driver::getInstance($this->database, $this->debug);
								$dummy = $driver_table->update( $driver, 
									array( 'CURRENT_LOAD' => 0 ) );
							}
							
							if( is_array($check) && isset($check[0]) &&
								isset($edi_primary) && $is_declined ) {
								// Cancel related shipments
								$shipment_table->update( 'EDI_204_PRIMARY = '.$edi_primary,
									array( "CURRENT_STATUS" =>
									$shipment_table->behavior_state["cancel"] ), false );
							}
							break;
	
						case 'complete':	//! complete
							if( in_array($this->state_behavior[$current_state], array('oapproved', 'approved', 'billed') ) ) {
								$changes = array( "PAID_DATE" => "NULL" );
								if( $this->export_quickbooks ) {
									$changes["quickbooks_status_message"] = "NULL";
									$changes["quickbooks_txnid_ap"] = "NULL";
								}
								
								$result = $this->update( $pk, $changes );
								$user_log->log_event('finance', 'Unapproved Load '.$pk.
									($this->multi_company ? ' / '.$office_num : ''));
							} else {
								// Reset the driver current load to 0
								//! SCR# 324 - CURRENT_LOAD should be $pk
								if( $driver > 0 ) {
									$driver_table = sts_driver::getInstance($this->database, $this->debug);
									$dummy = $driver_table->update( $driver, 
										array( 'CURRENT_LOAD' => 0, 'LAST_LOAD' => $pk ) );
								}
								
								// import IFTA logs from PC*Miler
								$ifta_load_code = $pk;
								require_once( "exp_spawn_log_miles.php" );

							}

							//! Update the columns LOAD_REVENUE, LOAD_EXPENSE
							$this->database->get_one_row("
								UPDATE EXP_LOAD    
								SET LOAD_REVENUE = LOAD_REVENUE_CUR( LOAD_CODE, CURRENCY ),
									LOAD_EXPENSE = LOAD_EXPENSE_CUR( LOAD_CODE, CURRENCY )
								WHERE LOAD_CODE = $pk");

							break;
	
						case 'arrive stop':	//! arrive stop - possible complete
							$this->update_last_stop( $current_stop_code, $driver, $tractor, $trailer );
							// Is the load complete?
							if( $current_stop == $num_stops ) {
								// Change state for stop
								$stop_table = sts_stop::getInstance($this->database, $this->debug);
								$dummy = $stop_table->completed( $pk, $current_stop );
								
								$dummy = $this->change_state_behavior( $pk, 'complete' );
							}
							$this->state_change_post = 'arrive stop,'.$current_stop_code;
							break;
	
						case 'depart stop':	//! depart stop
							// Change state for stop
							$stop_table = sts_stop::getInstance($this->database, $this->debug);
							$dummy = $stop_table->completed( $pk, $current_stop );
	
							// Is the load complete?
							if( $current_stop == $num_stops )
								$dummy = $this->change_state_behavior( $pk, 'complete' );
							else
								$dummy = $this->update( $pk, 
									array( 'CURRENT_STOP' => ($current_stop + 1) ) );
							//$this->state_change_post = 'depart stop,'.$current_stop_code;
							break;
	
						case 'arrive shipper':	//! arrive shipper/arrive cons/arrive dock
							if( $this->state_behavior[$state] == 'arrive shipper' ) {
								$email_type = 'arrship';
								$email_code = $current_shipment;
								require( "exp_spawn_send_email.php" ); // Announce arrival
								sleep(2);
							}
							
						case 'arrive cons':
							if( $this->state_behavior[$state] == 'arrive cons' ) {
								$email_type = 'arrcons';
								$email_code = $current_shipment;
								require( "exp_spawn_send_email.php" ); // Announce arrival
								sleep(2);
							}
							
						case 'arrshdock':
						case 'arrrecdock':
							$this->update_last_stop( $current_stop_code, $driver, $tractor, $trailer );
							
							//!WIP - this could be revised to work for docks too
							// But for now, we restrict it to shipper/consignee only
							if( $current_stop < $num_stops && 
								in_array($this->state_behavior[$state], array('arrive shipper', 'arrive cons')) ) {
								$loc = $this->state_behavior[$state] == 'arrive shipper' ? 'SHIPPER' : 'CONS';
								// See if the next stop is the same location.
								// Returns 1 if true, 0 if false
								
								$check_query = "SELECT COUNT(*) MATCHES
									FROM
									(SELECT '".$current_stop_type."' AS STOP_TYPE,
										".$loc."_NAME, ".$loc."_ADDR1, ".$loc."_ADDR2,
										".$loc."_CITY, ".$loc."_STATE, ".$loc."_ZIP
										FROM EXP_SHIPMENT
										WHERE EXP_SHIPMENT.SHIPMENT_CODE = $current_shipment) AS CURRENT_STOP
									JOIN
									(SELECT STOP_TYPE, ".$loc."_NAME, ".$loc."_ADDR1, ".$loc."_ADDR2,
										".$loc."_CITY, ".$loc."_STATE, ".$loc."_ZIP
										FROM EXP_STOP, EXP_SHIPMENT
										WHERE EXP_STOP.LOAD_CODE = $pk
										AND SEQUENCE_NO = ".($current_stop + 1)."
										AND EXP_SHIPMENT.SHIPMENT_CODE = EXP_STOP.SHIPMENT) AS NEXT_STOP
									ON CURRENT_STOP.STOP_TYPE = NEXT_STOP.STOP_TYPE
									AND CURRENT_STOP.".$loc."_ADDR1 = NEXT_STOP.".$loc."_ADDR1
									AND COALESCE(CURRENT_STOP.".$loc."_ADDR2,'') = COALESCE(NEXT_STOP.".$loc."_ADDR2,'')
									AND CURRENT_STOP.".$loc."_CITY = NEXT_STOP.".$loc."_CITY
									AND CURRENT_STOP.".$loc."_STATE = NEXT_STOP.".$loc."_STATE
									AND CURRENT_STOP.".$loc."_ZIP = NEXT_STOP.".$loc."_ZIP
									";
								$check = $this->database->get_multiple_rows( $check_query );
									
								if( $this->debug ) {
									echo "<p>sts_load > change_state: check = </p>
									<pre>";
									var_dump($check);
									echo "</pre>";
								}
								
								if( is_array($check) && isset($check[0]) && isset($check[0]['MATCHES']) &&
									$check[0]['MATCHES'] == 1 ) {
									if( $this->debug ) echo "<p>sts_load > change_state: ".$this->state_behavior[$state]." Next stop is the same address.</p>";
									$this->add_load_status( $pk, "change_state: ".$this->state_behavior[$state]." Next stop is same addr" );
	
									// First depart the current stop
									$dummy1 = $this->change_state_behavior( $pk, $this->state_behavior[$state] == 'arrive shipper' ? 'depart shipper' : 'depart cons' );
									if( $this->debug ) echo "<p>sts_load > change_state: change_state_behavior( $pk, ".($this->state_behavior[$state] == 'arrive shipper' ? 'depart shipper' : 'depart cons')." ) returned ".($dummy1 ? "true" : "false")."</p>";
									if( ! $dummy1 ) {
										$email = sts_email::getInstance($this->database, $this->debug);
										$email->send_alert("sts_load > change_state($pk, $state): change_state_behavior failed<br>".
										"current_stop = <pre>".print_r($current_stop, true)."</pre><br>".
										"current_stop_code = <pre>".print_r($current_stop_code, true)."</pre><br>".
										"check_query = <pre>".print_r($check_query, true)."</pre><br>".
										"check = <pre>".print_r($check, true)."</pre><br>".
										$email->load_history($pk) );
									}
	
									// Then arrive / repeat the next stop.
									if( $dummy1 ) {
										if( $this->debug ) echo "<p>sts_load > change_state: dummy1 = true before change_state( $pk, $state )</p>";
										$dummy2 = $this->change_state( $pk, $state );
										if( $this->debug ) echo "<p>sts_load > change_state: after change_state( $pk, $state ) returned ".($dummy2 ? "true" : "false")."</p>";
									}
									if( $this->debug ) echo "<p>sts_load > change_state: change_state( $pk, ".$this->state_name[$state]." ) returned ".($dummy2 ? "true" : "false")."</p>";
								}
							}
							$this->state_change_post = $this->state_behavior[$state].','.$current_stop_code;
							break;
							
						case 'depart shipper':	//! depart shipper
							// Change states for shipment
							$shipment_table = sts_shipment::getInstance($this->database, $this->debug);
							$dummy = $shipment_table->change_state_behavior( $current_shipment, 'picked', false );
	
							// Change state for stop
							$stop_table = sts_stop::getInstance($this->database, $this->debug);
							$dummy = $stop_table->completed( $pk, $current_stop );
	
							$dummy = $this->update( $pk, 
								array( 'CURRENT_STOP' => ($current_stop + 1) ) );
							$this->state_change_post = 'depart shipper,'.$current_stop_code;
							break;
	
						case 'depshdock':	//! depshdock
							// Change states for shipment
							$shipment_table = sts_shipment::getInstance($this->database, $this->debug);
							$dummy = $shipment_table->change_state_behavior( $current_shipment, 'picked', false );
	
							// Change state for stop
							$stop_table = sts_stop::getInstance($this->database, $this->debug);
							$dummy = $stop_table->completed( $pk, $current_stop );
	
							$dummy = $this->update( $pk, 
								array( 'CURRENT_STOP' => ($current_stop + 1) ) );
							$this->state_change_post = 'depshdock,'.$current_stop_code;
							break;
	
						case 'depart cons':	//! depart cons
							// Change states for shipment
							$shipment_table = sts_shipment::getInstance($this->database, $this->debug);
							$dummy = $shipment_table->change_state_behavior( $current_shipment, 'dropped', false );
	
							// Change state for stop
							$stop_table = sts_stop::getInstance($this->database, $this->debug);
							$dummy = $stop_table->completed( $pk, $current_stop );
	
							if( $this->debug ) echo "<p>sts_load > Is the load complete? Stop $current_stop of $num_stops</p>";
							// Is the load complete?
							if( $current_stop == $num_stops )
								$dummy = $this->change_state_behavior( $pk, 'complete' );
							else
								$dummy = $this->update( $pk, 
									array( 'CURRENT_STOP' => ($current_stop + 1) ) );
							$this->state_change_post = 'depart cons,'.$current_stop_code;
							break;
	
						case 'deprecdock':	//! deprecdock
							// Change states for shipment
							$shipment_table = sts_shipment::getInstance($this->database, $this->debug);
							$dummy = $shipment_table->change_state_behavior( $current_shipment, 'docked', false );
	
							// Change state for stop
							$stop_table = sts_stop::getInstance($this->database, $this->debug);
							$dummy = $stop_table->completed( $pk, $current_stop );
	
							// Is the load complete?
							if( $current_stop == $num_stops )
								$dummy = $this->change_state_behavior( $pk, 'complete' );
							else
								$dummy = $this->update( $pk, 
									array( 'CURRENT_STOP' => ($current_stop + 1) ) );
							$this->state_change_post = 'deprecdock,'.$current_stop_code;
							break;
	
						case 'oapproved':	//! oapproved
							$user_log->log_event('finance', 'Approved (Office) Load '.$pk.
								($this->multi_company ? ' / '.$office_num : ''));
							break;
						
						case 'approved':	//! approved
							if( $this->export_quickbooks ) {
								require_once( $sts_crm_dir."quickbooks-php-master/QuickBooks.php" );
								// Queue up the Quickbooks API request
								$Queue = new QuickBooks_WebConnector_Queue($sts_qb_dsn);
								$Queue->enqueue(QUICKBOOKS_QUERY_VENDOR, $pk, 0, 
									array( 'vendortype' => QUICKBOOKS_VENDOR_CARRIER ));
	
								$manual_spawn = $this->setting_table->get( 'api',
									'QUICKBOOKS_MANUAL_SPAWN' ) == 'true';
								if( $this->sts_qb_online && ! $manual_spawn )
									require_once( __DIR__."/../quickbooks-php-master".
										DIRECTORY_SEPARATOR."online".
										DIRECTORY_SEPARATOR."spawn_process.php" );
							}

							//! Update the columns LOAD_REVENUE, LOAD_EXPENSE
							$this->database->get_one_row("
								UPDATE EXP_LOAD    
								SET LOAD_REVENUE = LOAD_REVENUE_CUR( LOAD_CODE, CURRENCY ),
									LOAD_EXPENSE = LOAD_EXPENSE_CUR( LOAD_CODE, CURRENCY )
								WHERE LOAD_CODE = $pk");

							$user_log->log_event('finance', 'Approved (Finance) Load '.$pk.
								($this->multi_company ? ' / '.$office_num : ''));
							break;
	
						case 'billed':	//! billed
							//! Update the columns LOAD_REVENUE, LOAD_EXPENSE
							$this->database->get_one_row("
								UPDATE EXP_LOAD    
								SET LOAD_REVENUE = LOAD_REVENUE_CUR( LOAD_CODE, CURRENCY ),
									LOAD_EXPENSE = LOAD_EXPENSE_CUR( LOAD_CODE, CURRENCY )
								WHERE LOAD_CODE = $pk");

							$user_log->log_event('finance', 'Paid Load '.$pk.
								($this->multi_company ? ' / '.$office_num : ''));
							break;
					}
				} else {
					$save_error = $this->error();
					$email = sts_email::getInstance($this->database, $this->debug);
					$email->send_alert('sts_load > change_state($pk, $state): update state failed.'.
					'<br>'.$save_error.
					'<br>'.$email->load_stops($_GET['CODE']).
					'<br>'.$email->load_history($_GET['CODE']) );
				}

			} else {
				if( $this->debug ) echo "<p>sts_load > change_state: Not a valid state change. $current_state (".
					(isset($this->state_name[$current_state]) ? $this->state_name[$current_state] : 'unknown').") -> $state (".
					(isset($this->state_name[$state]) ? $this->state_name[$state] : 'unknown').")<br>
					$this->state_change_error<br>
					level = ".$sts_error_level_label[$this->state_change_level]."</p>";
				return false;
			}
		}
		return $result;
	}
	
	// Call change_state() after looking up behavior
	public function change_state_behavior( $pk, $behavior ) {

		return isset($this->behavior_state[$behavior]) ? $this->change_state( $pk, $this->behavior_state[$behavior] ) : false;
	}
	
	/*public function cancel( $pk ) {
	
		$result = $this->change_state( $pk, 'cancel' );
			
		$dummy = $this->database->get_one_row("UPDATE EXP_SHIPMENT SET LOAD_CODE = 0
			WHERE LOAD_CODE = ".$pk );

		return $result;
	}
	
	public function dispatch( $pk ) {

		return $this->change_state( $pk, 'dispatch' );
	}*/
	
	//! SCR# 702 - Get equipment required for a load
	public function get_equipment_req( $load, $as_table = false ) {
		if( $this->debug ) echo "<p>".__METHOD__.": $load</p>";
		$result = '';
		$check = $this->database->get_multiple_rows("
			SELECT S.SHIPMENT_CODE, S.SS_NUMBER, GROUP_CONCAT(L.ITEM ORDER BY 1 ASC SEPARATOR ', ') AS REQ
			FROM EXP_SHIPMENT S, EXP_ITEM_LIST L, EXP_EQUIPMENT_REQ R
			WHERE R.SOURCE_TYPE = 'shipment'
			AND R.SOURCE_CODE = S.SHIPMENT_CODE
			AND R.ITEM_CODE = L.ITEM_CODE
			AND S.LOAD_CODE = $load
			GROUP BY S.SHIPMENT_CODE, S.SS_NUMBER
		");
		
		if( is_array($check) && count($check) > 0) {
			if( $as_table ) {
				$result = '<table class="noborder">
				<tbody>
				';
				foreach( $check as $row ) {
					$result .= '<tr>
					<td>'.($this->multi_company ? $row["SS_NUMBER"] : $row["SHIPMENT_CODE"]).'</td>
					<td>'.$row["REQ"].'</td>
					</tr>
					';
				}
				$result .= '</tbody>
				</table>
				';
			} else {
				$result = $check;
			}
		}
			
		return $result;
	}
		
}

class sts_load_left_join extends sts_load {

	private $maps_key;
	
	// Constructor does not need the table name
	public function __construct( $database, $debug = false ) {

		if( $debug ) echo "<p>Create sts_load_left_join</p>";
		parent::__construct( $database, $debug);
		$this->maps_key = $this->setting_table->get( 'api', 'GOOGLE_API_KEY' );

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

	// Fetch one or more rows
	public function fetch_rows( $match = "", $fields = "*", $order = "", $limit = "", $groupby = "", $match2 = "" ) {
		if( $this->debug ) echo "<p>sts_load_left_join > fetch_rows $match<br>$match2</p>";
		
		$order2 = str_replace(', SEQUENCE_NO ASC', '', $order);
		
		if( $match2 <> '' ) {
			if( preg_match('/LIKE \'\%[^\%]*\%\'/', $match2, $matches) ) {
				if( $this->debug ) {
					echo "<pre>";
					var_dump($matches);
					echo "</pre>";
				}
				$like = $matches[0];
				
				$match .= "
			AND (
				LOAD_CODE $like
				OR
				EXISTS (SELECT UNIT_NUMBER
				FROM EXP_TRACTOR
				WHERE TRACTOR = TRACTOR_CODE AND
				UNIT_NUMBER $like)
				OR
				EXISTS (SELECT UNIT_NUMBER
				FROM EXP_TRAILER
				WHERE TRAILER = TRAILER_CODE AND
				UNIT_NUMBER $like)
				OR
				EXISTS (SELECT UNIT_NUMBER
				FROM EXP_STOP, EXP_TRAILER
				WHERE EXP_STOP.LOAD_CODE = EXP_LOAD.LOAD_CODE AND
				EXP_STOP.TRAILER = TRAILER_CODE AND
				UNIT_NUMBER $like)
				OR
				EXISTS (SELECT DRIVER_CODE
				FROM EXP_DRIVER
				WHERE DRIVER = DRIVER_CODE AND
				concat_ws( ' ', FIRST_NAME , LAST_NAME ) $like)
				OR
				EXISTS (SELECT LABEL AS DRIVER_LABEL
				FROM EXP_CONTACT_INFO
				WHERE CONTACT_CODE = DRIVER
				AND CONTACT_SOURCE = 'driver'
				AND CONTACT_TYPE = 'individual'
				AND ISDELETED = false
				AND COALESCE(LABEL,'') $like
				LIMIT 1)
				OR
				EXISTS (SELECT CARRIER_CODE
				FROM EXP_CARRIER
				WHERE CARRIER = CARRIER_CODE AND
				CARRIER_NAME $like)
				OR
				EXISTS (SELECT CARRIER_CODE
				FROM EXP_CARRIER
				WHERE LUMPER = CARRIER_CODE AND
				CARRIER_NAME $like)
				OR
				EXISTS (SELECT EXP_SHIPMENT.LOAD_CODE
				FROM EXP_SHIPMENT
				WHERE EXP_SHIPMENT.LOAD_CODE = EXP_LOAD.LOAD_CODE AND (
					SHIPMENT_CODE $like OR
					PO_NUMBER $like OR
					PO_NUMBER2 $like OR
					PO_NUMBER3 $like OR
					PO_NUMBER4 $like OR
					PO_NUMBER5 $like OR
					FS_NUMBER $like OR
					REF_NUMBER $like OR
					BOL_NUMBER $like OR
					SHIPPER_NAME $like OR
					SHIPPER_CITY $like OR
					SHIPPER_STATE $like OR
					CONS_NAME $like OR
					CONS_CITY $like OR
					CONS_STATE $like OR
					BILLTO_NAME $like OR
					SS_NUMBER $like
				))
				
			)
				";
			}
		}
		
		//! SCR# 696 Color due/overdue
		
		$result = $this->database->multi_query("-- start transaction read only;
			set time_zone = 'SYSTEM';
			SELECT $fields FROM
			(SELECT EXP_LOAD.*, EXP_STOP.STOP_CODE, EXP_STOP.SEQUENCE_NO, EXP_STOP.STOP_TYPE,
            EXP_STOP.STOP_ETA, EXP_STOP.ACTUAL_ARRIVE, EXP_STOP.ACTUAL_DEPART,
            
 			 (CASE WHEN (SELECT BEHAVIOR from EXP_STATUS_CODES X 
				WHERE X.STATUS_CODES_CODE = EXP_STOP.CURRENT_STATUS  LIMIT 0 , 1) = 'complete' THEN 'black'
			WHEN STOP_TYPE = 'pick' THEN DUE_COLOR(PICKUP_DATE, PICKUP_TIME_OPTION, PICKUP_TIME1, PICKUP_TIME2, SHIPPER_ZIP )
			WHEN STOP_TYPE = 'drop' THEN DUE_COLOR(DELIVER_DATE, DELIVER_TIME_OPTION, DELIVER_TIME1, DELIVER_TIME2, CONS_ZIP )
			ELSE 'black' END) AS STOP_COLOR,
    
           Z.*,
           
           COALESCE( CASE WHEN SEQUENCE_NO = CURRENT_STOP THEN
           (SELECT SUBSTRING(COMMENTS, 11) FROM EXP_STATUS
				WHERE ORDER_CODE=EXP_LOAD.LOAD_CODE AND SOURCE_TYPE = 'load'
				AND COMMENTS LIKE 'CHECKCALL%'
				ORDER BY CREATED_DATE DESC
				LIMIT 1) ELSE '' END,'') AS CHECKCALL,
           
			SORT_DROP_DUE(EXP_LOAD.LOAD_CODE) AS SDD,
			(CASE 
				WHEN SHIPMENT > 0 AND STOP_TYPE != 'stop' THEN SHIPMENT
				ELSE NULL END ) AS SHIPMENT2,
			(CASE 
				WHEN SHIPMENT > 0 AND STOP_TYPE != 'stop' THEN SS_NUMBER 
				ELSE NULL END ) AS SS_NUMBER2,
			(CASE
				WHEN STOP_TYPE = 'pick' AND COALESCE(BOL_NUMBER,'') <> '' THEN BOL_NUMBER
				WHEN STOP_TYPE = 'pick' AND COALESCE(ST_NUMBER,'') <> '' THEN ST_NUMBER
				ELSE NULL END ) AS STBOL,
			(CASE STOP_TYPE 
				WHEN 'pick' THEN PICKUP_APPT
				WHEN 'drop' THEN DELIVERY_APPT 
				ELSE NULL END ) AS APPT,
			(SELECT BEHAVIOR from EXP_STATUS_CODES X 
				WHERE X.STATUS_CODES_CODE = EXP_STOP.CURRENT_STATUS  LIMIT 0 , 1) AS CURRENT_STOP_STATUS,
			(SELECT STATUS_STATE from EXP_STATUS_CODES X 
				WHERE X.STATUS_CODES_CODE = EXP_STOP.CURRENT_STATUS  LIMIT 0 , 1) AS STOP_STATUS,
			
			(CASE STOP_TYPE 
				WHEN 'pick' THEN concat(COALESCE(SHIPPER_NAME,''),'<br>',COALESCE(SHIPPER_CITY,''),', ',COALESCE(SHIPPER_STATE,''),', ',COALESCE(SHIPPER_COUNTRY,''))
				WHEN 'drop' THEN concat(COALESCE(CONS_NAME,''),'<br>',COALESCE(CONS_CITY,''),', ',COALESCE(CONS_STATE,''),', ',COALESCE(CONS_COUNTRY,'')) 
				ELSE concat(COALESCE(STOP_NAME,''),'<br>',COALESCE(STOP_CITY,''),', ',COALESCE(STOP_STATE,''),', ',COALESCE(STOP_COUNTRY,'')) END ) AS NAME,
				
			(CASE WHEN STOP_TYPE = 'stop' AND SEQUENCE_NO = NUM_STOPS THEN
				'back'
			ELSE
				DIRECTION
			END) AS DIRECTION2,
			(CASE STOP_TYPE 
				WHEN 'pick' THEN
					FMT_DUE(PICKUP_DATE, PICKUP_TIME_OPTION, PICKUP_TIME1, PICKUP_TIME2, '<br>', SHIPPER_ZIP)
				WHEN 'drop' THEN
					FMT_DUE(DELIVER_DATE, DELIVER_TIME_OPTION, DELIVER_TIME1, DELIVER_TIME2, '<br>', CONS_ZIP)
				ELSE DATE_FORMAT(STOP_DUE, '%m/%d<br>At %H:%i') END ) AS DUE_TS2,
			COALESCE(EXP_STOP.TRAILER, EXP_LOAD.TRAILER ) AS TRAILER2,
			(SELECT UNIT_NUMBER AS TRAILER_NUMBER
				FROM ".TRAILER_TABLE."
				WHERE TRAILER_CODE = COALESCE(EXP_STOP.TRAILER, EXP_LOAD.TRAILER )
				LIMIT 1) AS TRAILER_NUMBER,
			COALESCE(DRIVER_LABEL, DRIVER_NAME) AS DRIVER_NAME2,
			COALESCE(DRIVER2_LABEL, DRIVER2_NAME) AS DRIVER2_NAME2,
			CASE WHEN SHIPMENT_CTPAT(SHIPMENT) THEN
				CONCAT( COALESCE(REF_NUMBER, ''), '<br><span class=\"badge\" title=\"Customs-Trade Partnership Against Terrorism\">C-TPAT</span>' )
			ELSE REF_NUMBER END AS REF_NUMBER2


			FROM
			(SELECT LOAD_CODE, CURRENT_STATUS AS LOAD_STATUS, CURRENT_STOP, TOTAL_DISTANCE,
			(SELECT COUNT(*) AS NUM_STOPS
				FROM ".STOP_TABLE."
				WHERE ".LOAD_TABLE.".LOAD_CODE = ".STOP_TABLE.".LOAD_CODE) AS NUM_STOPS,
			(SELECT SUM(PALLETS) AS SUM_PALLETS
				FROM ".SHIPMENT_TABLE."
				WHERE ".LOAD_TABLE.".LOAD_CODE = ".SHIPMENT_TABLE.".LOAD_CODE) AS SUM_PALLETS,
			(SELECT SUM(PALLETS) AS SUM_PALLETS_HEAD
				FROM ".SHIPMENT_TABLE."
				WHERE ".LOAD_TABLE.".LOAD_CODE = ".SHIPMENT_TABLE.".LOAD_CODE
				AND ".SHIPMENT_TABLE.".DIRECTION = 'head') AS SUM_PALLETS_HEAD,
			(SELECT SUM(PALLETS) AS SUM_PALLETS_BACK
				FROM ".SHIPMENT_TABLE."
				WHERE ".LOAD_TABLE.".LOAD_CODE = ".SHIPMENT_TABLE.".LOAD_CODE
				AND ".SHIPMENT_TABLE.".DIRECTION = 'back') AS SUM_PALLETS_BACK,

			COALESCE((SELECT SUM(WEIGHT) AS SUM_WEIGHT_HEAD
				FROM ".SHIPMENT_TABLE."
				WHERE ".LOAD_TABLE.".LOAD_CODE = ".SHIPMENT_TABLE.".LOAD_CODE
				AND ".SHIPMENT_TABLE.".DIRECTION = 'head'), 0) AS SUM_WEIGHT_HEAD,
			COALESCE((SELECT SUM(WEIGHT) AS SUM_WEIGHT_BACK
				FROM ".SHIPMENT_TABLE."
				WHERE ".LOAD_TABLE.".LOAD_CODE = ".SHIPMENT_TABLE.".LOAD_CODE
				AND ".SHIPMENT_TABLE.".DIRECTION = 'back'), 0) AS SUM_WEIGHT_BACK,

			(SELECT STOP_TYPE AS CURRENT_STOP_TYPE
				FROM ".STOP_TABLE."
				WHERE ".LOAD_TABLE.".LOAD_CODE = ".STOP_TABLE.".LOAD_CODE
				AND SEQUENCE_NO = CURRENT_STOP
				LIMIT 1) AS CURRENT_STOP_TYPE,
			DRIVER, DRIVER2,
			(SELECT concat_ws( ' ', FIRST_NAME , LAST_NAME ) AS DRIVER_NAME
				FROM ".DRIVER_TABLE."
				WHERE DRIVER = DRIVER_CODE
				LIMIT 1) AS DRIVER_NAME,
			(SELECT concat_ws( ' ', FIRST_NAME , LAST_NAME ) AS DRIVER2_NAME
				FROM ".DRIVER_TABLE."
				WHERE DRIVER2 = DRIVER_CODE
				LIMIT 1) AS DRIVER2_NAME,
			(SELECT LABEL AS DRIVER_LABEL
				FROM ".CONTACT_INFO_TABLE."
				WHERE CONTACT_CODE = DRIVER
				AND CONTACT_SOURCE = 'driver'
				AND CONTACT_TYPE = 'individual'
				AND ISDELETED = 0
				AND COALESCE(LABEL,'') <> ''
				LIMIT 1) AS DRIVER_LABEL,
			(SELECT LABEL AS DRIVER_LABEL
				FROM ".CONTACT_INFO_TABLE."
				WHERE CONTACT_CODE = DRIVER2
				AND CONTACT_SOURCE = 'driver'
				AND CONTACT_TYPE = 'individual'
				AND ISDELETED = 0
				AND COALESCE(LABEL,'') <> ''
				LIMIT 1) AS DRIVER2_LABEL,
			(SELECT UNIT_NUMBER AS TRACTOR_NUMBER
				FROM ".TRACTOR_TABLE."
				WHERE TRACTOR = TRACTOR_CODE
				LIMIT 1) AS TRACTOR_NUMBER,
			(SELECT MOBILE_TIME
				FROM ".TRACTOR_TABLE."
				WHERE TRACTOR = TRACTOR_CODE
				LIMIT 1) AS MOBILE_TIME,
			(SELECT CONCAT('<a href=\"https://www.google.com/maps/@',
				MOBILE_LATITUDE, ',', MOBILE_LONGITUDE, ',16z?hl=en\" target=\"_blank\">',
				MOBILE_LOCATION, ' <span class=\"glyphicon glyphicon-new-window\"></span></a>')
				FROM ".TRACTOR_TABLE."
				WHERE TRACTOR = TRACTOR_CODE
				LIMIT 1) AS MOBILE_LOCATION,
			TRAILER,
			CARRIER, CARRIER_NOTE,
			(SELECT CARRIER_NAME
				FROM ".CARRIER_TABLE."
				WHERE CARRIER = CARRIER_CODE
				LIMIT 1) AS CARRIER_NAME,
			LUMPER, LUMPER_TAX,
			LUMPER_TOTAL, LUMPER_CURRENCY,
			(SELECT CARRIER_NAME
				FROM ".CARRIER_TABLE."
				WHERE LUMPER = CARRIER_CODE
				LIMIT 1) AS LUMPER_NAME,
			OFFICE_CODE,
			(SELECT OFFICE_NAME
				FROM ".OFFICE_TABLE."
				WHERE ".OFFICE_TABLE.".OFFICE_CODE = EXP_LOAD.OFFICE_CODE
				LIMIT 1) AS OFFICE_NAME
			FROM EXP_LOAD 
			".($match <> "" ? "WHERE $match" : "")."
			".($order2 <> "" ? "ORDER BY $order2" : "")."
			".($limit <> "" ? "LIMIT $limit" : "").") EXP_LOAD
			left join EXP_STOP
			ON EXP_LOAD.LOAD_CODE = EXP_STOP.LOAD_CODE
			left join
			(SELECT PO_NUMBER,PO_NUMBER2,PO_NUMBER3,PO_NUMBER4,PO_NUMBER5,
				DIRECTION,PICKUP_NUMBER,PICKUP_TIME_OPTION,PICKUP_DATE,PICKUP_TIME1,PICKUP_TIME2,
				DELIVER_DATE,DELIVER_TIME_OPTION,DELIVER_TIME1,DELIVER_TIME2,BOL_NUMBER,
				ST_NUMBER,PALLETS,BILLTO_NAME,NOTES,SHIPPER_NAME,CONS_NAME,
				SHIPPER_CITY,SHIPPER_STATE, SHIPPER_ZIP, SHIPPER_COUNTRY, CONS_CITY,
				CONS_STATE, CONS_ZIP, CONS_COUNTRY, SHIPMENT_CODE,
				PICKUP_APPT, DELIVERY_APPT, WEIGHT, REF_NUMBER, SS_NUMBER
			FROM EXP_SHIPMENT) Z
			ON SHIPMENT_CODE = SHIPMENT ) EXP_LOAD
			".($order <> "" ? "ORDER BY $order" : "") );
			//EXP_LOAD.LOAD_CODE ASC, EXP_STOP.SEQUENCE_NO
		
		if( $this->debug ) {
			echo "<h3>After query / get_multiple_rows</h3>";
		}
		
		if( strpos($fields, 'SQL_CALC_FOUND_ROWS') !== false) {
			if( $this->debug ) {
				echo "<h3>Before query2 / get_one_row</h3>";
			}
		
			$result1 = $this->database->get_one_row( "SELECT COUNT(*) AS FOUND
				FROM EXP_LOAD
				WHERE $match" );
			if( $this->debug ) {
				echo "<h3>After query2 / get_one_row</h3>";
			}
		
			$this->found_rows = is_array($result1) && isset($result1["FOUND"]) ? $result1["FOUND"] : 0;
			if( $this->debug ) echo "<p>found_rows = $this->found_rows</p>";
		}

		if( $this->debug ) {
			echo "<p>result for $this->table_name = </p>
			<pre>";
			var_dump($result);
			echo "</pre>";
		}
		return $result;
	}


}

class sts_load_summary extends sts_load {
	
	private $pallets_weight;

	// Constructor does not need the table name
	public function __construct( $database, $debug = false ) {

		if( $debug ) echo "<p>Create sts_load_summary</p>";
		parent::__construct( $database, $debug);
		$this->pallets_weight = $this->setting_table->get( 'option', 'SUMMARY_PALLETS_WEIGHT' );
	}

	// Fetch one or more rows
	public function fetch_rows( $match = "", $fields = "*", $order = "", $limit = "", $groupby = "", $match2 = "" ) {
		if( $this->debug ) echo "<p>sts_load_summary > fetch_rows $match<br>$match2</p>";
		
		if( $this->pallets_weight == 'all' )
			$pw = "
			(SELECT SUM(PALLETS) AS SUM_PALLETS
				FROM ".SHIPMENT_TABLE."
				WHERE ".LOAD_TABLE.".LOAD_CODE = ".SHIPMENT_TABLE.".LOAD_CODE) AS SUM_PALLETS,
			COALESCE((SELECT SUM(WEIGHT) AS SUM_WEIGHT
				FROM ".SHIPMENT_TABLE."
				WHERE ".LOAD_TABLE.".LOAD_CODE = ".SHIPMENT_TABLE.".LOAD_CODE), 0) AS SUM_WEIGHT
			";
		else
			$pw = "
			(SELECT SUM(PALLETS) AS SUM_PALLETS
				FROM ".SHIPMENT_TABLE."
				WHERE ".LOAD_TABLE.".LOAD_CODE = ".SHIPMENT_TABLE.".LOAD_CODE
				AND ".SHIPMENT_TABLE.".DIRECTION = 'head') AS SUM_PALLETS,
			COALESCE((SELECT SUM(WEIGHT) AS SUM_WEIGHT
				FROM ".SHIPMENT_TABLE."
				WHERE ".LOAD_TABLE.".LOAD_CODE = ".SHIPMENT_TABLE.".LOAD_CODE
				AND ".SHIPMENT_TABLE.".DIRECTION = 'head'), 0) AS SUM_WEIGHT
			";

		$result = $this->database->get_multiple_rows("
			SELECT * FROM (
			SELECT LOAD_CODE, LOAD_OFFICE_NUM(LOAD_CODE) AS OFFICE_NUM, LOAD_STATUS,
			CURRENT_STOP,
			(SELECT STOP_TYPE AS CURRENT_STOP_TYPE
				FROM EXP_STOP
				WHERE EXP_LOAD.LOAD_CODE = EXP_STOP.LOAD_CODE
				AND SEQUENCE_NO = CURRENT_STOP) AS CURRENT_STOP_TYPE,
			(SELECT EXP_STOP.CURRENT_STATUS AS CURRENT_STOP_STATUS
				FROM EXP_STOP
				WHERE EXP_LOAD.LOAD_CODE = EXP_STOP.LOAD_CODE
				AND SEQUENCE_NO = CURRENT_STOP) AS CURRENT_STOP_STATUS,
			(SELECT COUNT(*) AS NUM_STOPS
				FROM EXP_STOP
				WHERE EXP_LOAD.LOAD_CODE = EXP_STOP.LOAD_CODE) AS NUM_STOPS,

			TRACTOR, TRAILER, DRIVER, CARRIER,
			TRACTOR_NUMBER, TRAILER_NUMBER,
			COALESCE(CARRIER_NAME, DRIVER_LABEL, DRIVER_NAME) AS DRIVER_CARRIER,
			SUM_PALLETS, SUM_WEIGHT,
			(SELECT PICKUP_DATE
			FROM EXP_SHIPMENT SH, EXP_STOP S
			WHERE S.LOAD_CODE = EXP_LOAD.LOAD_CODE
			AND S.SHIPMENT = SH.SHIPMENT_CODE
			AND DIRECTION = 'head'
			ORDER BY S.SEQUENCE_NO ASC
			LIMIT 1) AS PICKUP_DATE,
			SORT_DROP_DUE(LOAD_CODE) AS DELIVER_DATE,
			OFFICE_CODE, OFFICE_NAME
			
			FROM
			(SELECT LOAD_CODE, CURRENT_STATUS AS LOAD_STATUS, CURRENT_STOP,
			TRACTOR, TRAILER, DRIVER, CARRIER,

			(SELECT UNIT_NUMBER AS TRACTOR_NUMBER
				FROM ".TRACTOR_TABLE."
				WHERE TRACTOR = TRACTOR_CODE
				LIMIT 1) AS TRACTOR_NUMBER,
			(SELECT UNIT_NUMBER AS TRAILER_NUMBER
				FROM ".TRAILER_TABLE."
				WHERE TRAILER_CODE = TRAILER
				LIMIT 1) AS TRAILER_NUMBER,
			(SELECT concat_ws( ' ', FIRST_NAME , LAST_NAME ) AS DRIVER_NAME
				FROM ".DRIVER_TABLE."
				WHERE DRIVER = DRIVER_CODE
				LIMIT 1) AS DRIVER_NAME,
			(SELECT LABEL AS DRIVER_LABEL
				FROM ".CONTACT_INFO_TABLE."
				WHERE CONTACT_CODE = DRIVER
				AND CONTACT_SOURCE = 'driver'
				AND CONTACT_TYPE = 'individual'
				AND ISDELETED = 0
				AND COALESCE(LABEL,'') <> ''
				LIMIT 1) AS DRIVER_LABEL,
			(SELECT LABEL AS DRIVER_LABEL
				FROM ".CONTACT_INFO_TABLE."
				WHERE CONTACT_CODE = DRIVER2
				AND CONTACT_SOURCE = 'driver'
				AND CONTACT_TYPE = 'individual'
				AND ISDELETED = 0
				AND COALESCE(LABEL,'') <> ''
				LIMIT 1) AS DRIVER2_LABEL,
			(SELECT CARRIER_NAME
				FROM ".CARRIER_TABLE."
				WHERE CARRIER = CARRIER_CODE
				LIMIT 1) AS CARRIER_NAME,
			OFFICE_CODE,
			(SELECT OFFICE_NAME
				FROM ".OFFICE_TABLE."
				WHERE ".OFFICE_TABLE.".OFFICE_CODE = EXP_LOAD.OFFICE_CODE
				LIMIT 1) AS OFFICE_NAME,				
			".$pw."

			FROM EXP_LOAD
			".($match <> "" ? "WHERE $match" : "")."
			".($limit <> "" ? "LIMIT $limit" : "").") EXP_LOAD ) X
			".($match2 <> "" ? "WHERE $match2" : "")."
			".($order <> "" ? "ORDER BY $order" : "") );
			//ORDER BY PICKUP_DATE ASC, DELIVER_DATE ASC
			
		if( strpos($fields, 'SQL_CALC_FOUND_ROWS') !== false) {
			$result1 = $this->database->get_one_row( "SELECT FOUND_ROWS() AS FOUND" );
			$this->found_rows = is_array($result1) && isset($result1["FOUND"]) ? $result1["FOUND"] : 0;
			if( $this->debug ) echo "<p>found_rows = $this->found_rows</p>";
		}

		if( is_array($result) && count($result) > 0 ) {
			for($c=0; $c< count($result); $c++) {
				$current_stop = $result[$c]["CURRENT_STOP"];
				$load_status = $result[$c]["LOAD_STATUS"];
				
				$result2 = $this->database->get_multiple_rows("
					SELECT S.SEQUENCE_NO AS SSEQ, 
					CASE S.STOP_TYPE WHEN 'pick' THEN SHIPPER_CITY
					ELSE S.STOP_CITY END AS SCITY,
					CASE S.STOP_TYPE WHEN 'pick' THEN SHIPPER_STATE
					ELSE S.STOP_STATE END AS SSTATE
					FROM EXP_SHIPMENT SH, EXP_STOP S
					WHERE S.LOAD_CODE = ".$result[$c]["LOAD_CODE"]."
					AND S.SHIPMENT = SH.SHIPMENT_CODE
					AND DIRECTION = 'head'
					ORDER BY S.SEQUENCE_NO ASC
					LIMIT 1
				");
				if( is_array($result2) && count($result2) > 0 ) {
					$stop = $result2[0]["SSEQ"];
					$sstat = 0;
					if( $current_stop < $stop ) {
						$sstat = 0;
					} else if( $current_stop > $stop ) {
						$sstat = 2;
					} else if( $current_stop == $stop &&
						in_array($load_status, array($this->behavior_state["arrive shipper"], $this->behavior_state["arrshdock"]))) {
						$sstat = 1;
					}
					$result[$c] += array("SCITY" => $result2[0]["SCITY"], "SSTATE" => $result2[0]["SSTATE"], "SSTAT" => $sstat);
				} else {
					$result[$c] += array("SCITY" => null, "SSTATE" => null, "SSTAT" => 0);
				}
				
				$result3 = $this->database->get_multiple_rows("
					SELECT S.SEQUENCE_NO AS CSEQ, S.CURRENT_STATUS AS CSTAT,
					CASE S.STOP_TYPE WHEN 'drop' THEN CONS_NAME
					ELSE S.STOP_NAME END AS CNAME, 
					CASE S.STOP_TYPE WHEN 'drop' THEN CONS_CITY
					ELSE S.STOP_CITY END AS CCITY,
					CASE S.STOP_TYPE WHEN 'drop' THEN CONS_STATE
					ELSE S.STOP_STATE END AS CSTATE
					FROM EXP_SHIPMENT SH, EXP_STOP S
					WHERE S.LOAD_CODE = ".$result[$c]["LOAD_CODE"]."
					AND S.SHIPMENT = SH.SHIPMENT_CODE
					AND DIRECTION = 'head'
					AND S.STOP_TYPE in ('drop','dropdock')
					ORDER BY S.SEQUENCE_NO ASC
				");
if( $this->debug ) {
	echo "<pre>";
	var_dump($result3);
	echo "</pre>";
}
				for($d=0, $e=0; $d< 4; $d++, $e++) {
				if( is_array($result3) && count($result3) > $e ) {
					$first_e = isset($result3[$e]) && isset($result3[$e]["CSEQ"]) ? $result3[$e]["CSEQ"] : 0;
					while( isset($result3[$e]) && isset($result3[$e+1]) &&
						isset($result3[$e+1]["CNAME"]) && 
						$result3[$e]["CNAME"] == $result3[$e+1]["CNAME"] &&
						$result3[$e]["CCITY"] == $result3[$e+1]["CCITY"] &&
						$result3[$e]["CSTATE"] == $result3[$e+1]["CSTATE"] ) {
							$e++;
					}
					if( $this->debug ) echo "<p>sts_load_summary > fetch_rows cs = $current_stop, ls = $load_status, d = $d, e = $e, first_e = $first_e, cseq = ".(isset($result3[$e]["CSEQ"]) ? $result3[$e]["CSEQ"] : "").", cname = ".(isset($result3[$e]["CNAME"]) ? $result3[$e]["CNAME"] : "")."</p>";
					
					if( is_array($result3) && isset($result3[$e]) && isset($result3[$e]["CSEQ"])) {
						$stop = $result3[$e]["CSEQ"];
						$cstat = 0;
						
						if( $current_stop < $first_e ) {
							$cstat = 0;
						} else if( $current_stop > $stop ) {
							$cstat = 2;
						} else if( $current_stop > $first_e  || ( $current_stop == $first_e &&
							in_array($load_status, array($this->behavior_state["arrive cons"], $this->behavior_state["arrrecdock"]))) ) {
							$cstat = 1;
						}

						if( $this->debug ) echo "<p>sts_load_summary > fetch_rows stop = $stop, cstat = $cstat</p>";
						$result[$c]["CSTAT".$d] = $cstat;
					} else
						$result[$c]["CSTAT".$d] = null;

					$result[$c]["CNAME".$d] = is_array($result3) && isset($result3[$e]) && isset($result3[$e]["CNAME"]) ? $result3[$e]["CNAME"] : null;
					$result[$c]["CCITY".$d] = is_array($result3) && isset($result3[$e]) && isset($result3[$e]["CCITY"]) ? $result3[$e]["CCITY"] : null;
					$result[$c]["CSTATE".$d] = is_array($result3) && isset($result3[$e]) && isset($result3[$e]["CSTATE"]) ? $result3[$e]["CSTATE"] : null;
					}
				}
				
				$result4 = $this->database->get_multiple_rows("
					SELECT S.SEQUENCE_NO AS BSSEQ, S.CURRENT_STATUS AS BSSTAT,
					CASE S.STOP_TYPE WHEN 'drop' THEN SHIPPER_NAME
					ELSE S.STOP_NAME END AS BSNAME, 
					CASE S.STOP_TYPE WHEN 'pick' THEN SHIPPER_CITY
					ELSE S.STOP_CITY END AS BSCITY,
					CASE S.STOP_TYPE WHEN 'pick' THEN SHIPPER_STATE
					ELSE S.STOP_STATE END AS BSSTATE
					FROM EXP_SHIPMENT SH, EXP_STOP S
					WHERE S.LOAD_CODE = ".$result[$c]["LOAD_CODE"]."
					AND S.SHIPMENT = SH.SHIPMENT_CODE
					AND DIRECTION = 'back'
					ORDER BY S.SEQUENCE_NO ASC
					LIMIT 1
				");
				if( is_array($result4) && count($result4) > 0 ) {
					$stop = $result4[0]["BSSEQ"];
					$bsstat = 0;
					if( $current_stop < $stop ) {
						$bsstat = 0;
					} else if( $current_stop > $stop ) {
						$bsstat = 2;
					} else if( $current_stop == $stop &&
						in_array($load_status, array($this->behavior_state["arrive shipper"], $this->behavior_state["arrshdock"]))) {
						$bsstat = 1;
					}
					$result[$c] += array("BSNAME" => $result4[0]["BSNAME"], "BSCITY" => $result4[0]["BSCITY"], "BSSTATE" => $result4[0]["BSSTATE"], "BSSTAT" => $bsstat);
				} else {
					$result[$c] += array("BSNAME" => null, "BSCITY" => null, "BSSTATE" => null, "BSSTAT" => null);
				}
				
				$result5 = $this->database->get_multiple_rows("
					SELECT S.SEQUENCE_NO AS BCSEQ, S.CURRENT_STATUS AS BCSTAT,
					CASE S.STOP_TYPE WHEN 'drop' THEN CONS_CITY
					ELSE S.STOP_CITY END AS BCCITY,
					CASE S.STOP_TYPE WHEN 'drop' THEN CONS_STATE
					ELSE S.STOP_STATE END AS BCSTATE
					FROM EXP_SHIPMENT SH, EXP_STOP S
					WHERE S.LOAD_CODE = ".$result[$c]["LOAD_CODE"]."
					AND S.SHIPMENT = SH.SHIPMENT_CODE
					AND DIRECTION = 'back'
					AND S.STOP_TYPE in ('drop','dropdock')
					ORDER BY S.SEQUENCE_NO ASC
				");
if( $this->debug ) {
	echo "<pre>";
	var_dump($result5);
	echo "</pre>";
}
				for($d=0, $e=0; $d< 2; $d++, $e++) {
				if( is_array($result5) && count($result5) > $e ) {
					$first_e = isset($result5[$e]) && isset($result5[$e]["BCSEQ"]) ? $result5[$e]["BCSEQ"] : 0;
					while( isset($result5[$e]) && isset($result5[$e+1]) &&
						isset($result5[$e+1]["BCCITY"]) && 
						$result5[$e]["BCCITY"] == $result5[$e+1]["BCCITY"] &&
						$result5[$e]["BCSTATE"] == $result5[$e+1]["BCSTATE"] ) {
							$e++;
					}
					if( $this->debug ) echo "<p>sts_load_summary > fetch_rows cs = $current_stop, ls = $load_status, d = $d, e = $e, first_e = $first_e, cseq = ".(isset($result5[$e]["BCSEQ"]) ? $result5[$e]["BCSEQ"] : "").", bccity = ".(isset($result5[$e]["BCCITY"]) ? $result5[$e]["BCCITY"] : "")."</p>";
					if( is_array($result5) && isset($result5[$e]) && isset($result5[$e]["BCSEQ"])) {
						$stop = $result5[$e]["BCSEQ"];
						$bcstat = 0;

						if( $current_stop < $first_e ) {
							$bcstat = 0;
						} else if( $current_stop > $stop ) {
							$bcstat = 2;
						} else if( $current_stop > $first_e  || ( $current_stop == $first_e &&
							in_array($load_status, array($this->behavior_state["arrive cons"], $this->behavior_state["arrrecdock"]))) ) {
							$bcstat = 1;
						}

						if( $this->debug ) echo "<p>sts_load_summary > fetch_rows stop = $stop, bcstat = $bcstat</p>";
						$result[$c]["BCSTAT".$d] = $bcstat;
					} else
						$result[$c]["BCSTAT".$d] = null;
					
					$result[$c]["BCCITY".$d] = is_array($result5) && isset($result5[$e]) && isset($result5[$e]["BCCITY"]) ? $result5[$e]["BCCITY"] : null;
					$result[$c]["BCSTATE".$d] = is_array($result5) && isset($result5[$e]) && isset($result5[$e]["BCSTATE"]) ? $result5[$e]["BCSTATE"] : null;
					}
				}
				
			}
		}
			
		if( $this->debug ) {
			echo "<p>result for $this->table_name = </p>
			<pre>";
			var_dump($result);
			echo "</pre>";
		}
		return $result;
	}


}

class sts_load_manifest extends sts_load {

	// Constructor does not need the table name
	public function __construct( $database, $debug = false ) {

		if( $debug ) echo "<p>Create sts_load_manifest</p>";
		parent::__construct( $database, $debug);
	}

	// Fetch one or more rows
	public function fetch_rows( $match = "", $fields = "*", $order = "", $limit = "", $groupby = "", $match2 = "" ) {
		if( $this->debug ) echo "<p>fetch_rows $match</p>";
		
		//! SCR# 389 - add TERMS
		//! SCR# 449 - add CARRIER_BASE, CARRIER_FSC, CARRIER_HANDLING, CARRIER_TOTAL
		$result = $this->database->get_multiple_rows("SELECT $fields FROM
			(SELECT LOAD_CODE, CURRENT_STATUS AS LOAD_STATUS, CURRENT_STOP, TOTAL_DISTANCE, TERMS,
			CARRIER, CURRENT_DATE, CURRENCY,
			COALESCE(CARRIER_BASE,0) AS CARRIER_BASE,
			COALESCE(CARRIER_FSC,0) AS CARRIER_FSC,
			COALESCE(CARRIER_HANDLING,0) AS CARRIER_HANDLING,
			COALESCE(CARRIER_TOTAL,0) AS CARRIER_TOTAL,
			
			(SELECT COUNT(*) AS NUM_STOPS
				FROM EXP_STOP
				WHERE EXP_LOAD.LOAD_CODE = EXP_STOP.LOAD_CODE) AS NUM_STOPS,
			(SELECT SUM(PALLETS) AS SUM_PALLETS
				FROM EXP_SHIPMENT
				WHERE EXP_LOAD.LOAD_CODE = EXP_SHIPMENT.LOAD_CODE) AS SUM_PALLETS,
			(SELECT STOP_TYPE AS CURRENT_STOP_TYPE
				FROM EXP_STOP
				WHERE EXP_LOAD.LOAD_CODE = EXP_STOP.LOAD_CODE
				AND SEQUENCE_NO = CURRENT_STOP) AS CURRENT_STOP_TYPE,
			(SELECT CARRIER_NAME
				FROM EXP_CARRIER
				WHERE CARRIER = CARRIER_CODE) AS CARRIER_NAME,
			(SELECT CONCAT_WS(' ',FIRST_NAME,LAST_NAME) FROM EXP_DRIVER WHERE EXP_DRIVER.DRIVER_CODE = EXP_LOAD.DRIVER LIMIT 1) AS DRIVER_NAME,
			(SELECT EMAIL_NOTIFY AS CARRIER_EMAIL_NOTIFY
				FROM EXP_CARRIER
				WHERE CARRIER = CARRIER_CODE) AS CARRIER_EMAIL_NOTIFY
			FROM EXP_LOAD 
			".($match <> "" ? "WHERE $match" : "").") EXP_LOAD
			left join
			EXP_CONTACT_INFO
			ON CONTACT_SOURCE = 'carrier'
			AND CONTACT_TYPE IN ('carrier', 'company')
			AND CONTACT_CODE = CARRIER
			AND ISDELETED = false
			LIMIT 1" );

		if( $this->debug ) {
			echo "<p>result for $this->table_name = </p>
			<pre>";
			var_dump($result);
			echo "</pre>";
		}
		return $result;
	}


}

$sts_email_carrier_manifest = array(	//! sts_email_carrier_manifest
	'headerx' => '
	<h3 style="text-align: center;"><img src="%COMPANY_LOGO%" align="left">%COMPANY_NAME%</h3>
	<h4 style="text-align: center;">Load Confirmation Agreement<br>
	(Please sign & fax back to us at %COMPANY_FAX%)</h4>
	<br>',
	'layout' => '
	<table width="90%" align="center" border="0" cellspacing="0">
	<tr valign="top">
		<td width="30%">
			Date: %CURRENT_DATE%
		</td>
		<td width="30%">
		</td>
		<td width="30%">
			Our Reference #: <font size="+2"><strong>%LOAD_CODE%</strong></font>
		</td>
	</tr>
	<tr valign="top">
		<td colspan="3">
			<hr>
		</td>
	</tr>
	<tr valign="top">
		<td width="30%">
			Bill To<br>
			<strong>%COMPANY_NAME%</strong><br>
			%COMPANY_ADDR%
		</td>
		<td width="30%">
		</td>
		<td width="30%">
			Accepted By<br>
			<strong>%CARRIER_NAME%</strong><br>
			%ADDRESS%<br>
			#ADDRESS2#%ADDRESS2%<br>#
			%CITY%, %STATE%, %ZIP_CODE%<br>
			Phone: %PHONE_OFFICE%
		</td>
	</tr>
	<tr valign="top">
		<td width="30%">
			<br>
			<img src="%EMAIL_MANIFEST_SIG%"><br>
			%EMAIL_MANIFEST_CONTACT%
		</td>
		<td width="30%">
		</td>
		<td width="30%">
			<br>
			<hr>
			Carrier Signature
			<br>
			<br>
			<hr>
			Print Name
			<br>
			<br>
			<hr>
			Date
		</td>
	</tr>
	</table>
	' );

$sts_email_driver_manifest = array(	//! $sts_email_driver_manifest
	'layout' => '
	<table width="90%" align="center" border="0" cellspacing="0">
	<tr valign="top">
		<td width="30%">
			Date: %CURRENT_DATE%
		</td>
		<td width="30%">
		</td>
		<td width="30%">
			Our Reference #: <font size="+2"><strong>%LOAD_CODE%</strong></font>
		</td>
	</tr>
	<tr valign="top">
		<td colspan="3">
			<hr>
		</td>
	</tr>
	<tr valign="top">
		<td width="30%">
			Office<br>
			<strong>%COMPANY_NAME%</strong><br>
			%COMPANY_ADDR%
		</td>
		<td width="30%">
		</td>
		<td width="30%">
			Driver<br>
			<strong>%DRIVER_NAME%</strong>
		</td>
	</tr>
	</table>
	' );


//! Form Specifications - For use with sts_form

$sts_form_viewload_form = array(	//! $sts_form_viewload_form
	'title' => '<img src="images/load_icon.png" alt="load_icon" height="24"> Load %LOAD_CODE%',
	'action' => 'exp_viewload.php',
	'readonly' => true,
	//'actionextras' => 'disabled',
	'cancel' => 'exp_listload.php',
	'name' => 'viewload',
	//'okbutton' => 'Save Changes',
	//'saveadd' => 'Add Another',
	'cancelbutton' => 'Back',
	'buttons' => array( 
		),
		'layout' => '
	<!-- 204_INFO_HERE -->
	<div class="form-group">
		<div class="col-sm-4">
			<div class="well well-sm">
				<!-- CC01 -->
				<div class="form-group">
					<div class="col-sm-12 text-right">
						%OFFICE_CODE%
					</div>
				</div>
				<!-- CC02 -->
				<div class="form-group">
					<label for="LOAD_CODE" class="col-sm-6 control-label">#LOAD_CODE#</label>
					<div class="col-sm-6">
						%LOAD_CODE%
					</div>
				</div>
				<div class="form-group">
					<label for="CURRENT_STATUS" class="col-sm-6 control-label">#CURRENT_STATUS#</label>
					<div class="col-sm-6">
						%CURRENT_STATUS%
					</div>
				</div>
				<div class="form-group">
					<label for="CURRENT_STOP" class="col-sm-6 control-label">#CURRENT_STOP#</label>
					<div class="col-sm-6">
						%CURRENT_STOP%
					</div>
				</div>
				<div class="form-group">
					<label for="TOTAL_DISTANCE" class="col-sm-6 control-label">#TOTAL_DISTANCE#</label>
					<div class="col-sm-6">
						%TOTAL_DISTANCE%
					</div>
				</div>
				<div class="form-group">
					<label for="EMPTY_DISTANCE" class="col-sm-6 control-label">#EMPTY_DISTANCE#</label>
					<div class="col-sm-6">
						%EMPTY_DISTANCE%
					</div>
				</div>
				<div class="form-group">
					<label for="ODOMETER_FROM" class="col-sm-6 control-label">Odometer</label>
					<div class="col-sm-3">
						%ODOMETER_FROM%
					</div>
					<div class="col-sm-3">
						%ODOMETER_TO%
					</div>
				</div>
				<div class="form-group">
					<label for="ACTUAL_DISTANCE" class="col-sm-6 control-label">#ACTUAL_DISTANCE#</label>
					<div class="col-sm-6">
						%ACTUAL_DISTANCE%
					</div>
				</div>
				<!-- CTPAT01 -->
				<div class="form-group">
					<div class="col-sm-offset-6 col-sm-6 text-right">
						<a href="https://en.wikipedia.org/wiki/Customs-Trade_Partnership_Against_Terrorism" target="_blank" class="tip" title="Customs-Trade Partnership Against Terrorism"><img src="images/logo-ctpat.jpg" alt="logo-ctpat" height="48px" /></a><br>
						C-TPAT Required
					</div>
				</div>
				<!-- CTPAT02 -->
			</div>
			<div class="well well-sm">
				<div class="form-group">
					<label for="CREATED_DATE" class="col-sm-5 control-label">#CREATED_DATE#</label>
					<div class="col-sm-7">
						%CREATED_DATE%
					</div>
				</div>
				<!-- CARRIER1A -->
				<div class="form-group CARRIER_ONLY">
					<label for="CARRIER_MANIFEST_SENT" class="col-sm-5 control-label">#CARRIER_MANIFEST_SENT#</label>
					<div class="col-sm-7">
						%CARRIER_MANIFEST_SENT%
					</div>
				</div>
				<!-- CARRIER2A -->
				<div class="form-group">
					<label for="DISPATCHED_DATE" class="col-sm-5 control-label">#DISPATCHED_DATE#</label>
					<div class="col-sm-7">
						%DISPATCHED_DATE%
					</div>
				</div>
				<div class="form-group">
					<label for="COMPLETED_DATE" class="col-sm-5 control-label">#COMPLETED_DATE#</label>
					<div class="col-sm-7">
						%COMPLETED_DATE%
					</div>
				</div>
				<!-- CARRIER1B -->
				<div class="form-group CARRIER_ONLY">
					<label for="PAID_DATE" class="col-sm-5 control-label">#PAID_DATE#</label>
					<div class="col-sm-7">
						%PAID_DATE%
					</div>
				</div>
				<!-- CARRIER2B -->
			</div>
		</div>
		<div class="col-sm-8">
			<div class="well well-sm">
				<!-- NOTCARRIER1 -->
				<div class="NOT_CARRIER">
					<div class="form-group">
						<label for="DRIVER" class="col-sm-4 control-label">#DRIVER#</label>
						<div class="col-sm-8">
							%DRIVER% %DRIVER_LABEL%
						</div>
					</div>
					<!-- DRIVER21 -->
					<div class="form-group">
						<label for="DRIVER2" class="col-sm-4 control-label">#DRIVER2#</label>
						<div class="col-sm-8">
							%DRIVER2% %DRIVER2_LABEL%
						</div>
					</div>
					<!-- DRIVER22 -->
					<div class="form-group">
						<label for="TRACTOR" class="col-sm-4 control-label">#TRACTOR#</label>
						<div class="col-sm-8">
							%TRACTOR%
						</div>
					</div>
					<div class="form-group">
						<label for="MOBILE_TIME" class="col-sm-4 control-label">#MOBILE_TIME#</label>
						<div class="col-sm-8">
							%MOBILE_TIME%
						</div>
					</div>
					<div class="form-group">
						<label for="MOBILE_LOCATION" class="col-sm-4 control-label">#MOBILE_LOCATION#</label>
						<div class="col-sm-8">
							<a href="https://www.google.com/maps/@%MOBILE_LATITUDE%,%MOBILE_LONGITUDE%,16z?hl=en" target="_blank">%MOBILE_LOCATION% <span class="glyphicon glyphicon-new-window"></span></a>
						</div>
					</div>
				</div>
				<!-- NOTCARRIER2 -->
				<div class="form-group">
					<label for="TRAILER" class="col-sm-4 control-label">#TRAILER#</label>
					<div class="col-sm-8">
						%TRAILER%
					</div>
				</div>
				<!-- CARRIER1C -->
				<div class="CARRIER_ONLY">
					<div class="form-group">
						<label for="CARRIER" class="col-sm-4 control-label">#CARRIER#</label>
						<div class="col-sm-8">
							%CARRIER% %CTPAT_CERTIFIED%
						</div>
					</div>
					<div class="form-group">
						<label for="CARRIER_EMAIL" class="col-sm-4 control-label">#CARRIER_EMAIL#</label>
						<div class="col-sm-8">
							%CARRIER_EMAIL%
						</div>
					</div>
		
				</div>
				<!-- CARRIER2C -->
					<div class="form-group">
						<label for="CARRIER_NOTE" class="col-sm-4 control-label">#CARRIER_NOTE#</label>
						<div class="col-sm-8">
							%CARRIER_NOTE%
						</div>
					</div>
					<div class="form-group">
						<label for="LOAD_NOTE" class="col-sm-4 control-label">#LOAD_NOTE#</label>
						<div class="col-sm-8 bg-success">
							%LOAD_NOTE%
						</div>
					</div>
			</div>
			<!-- CARRIER1D -->
			<!-- CARRIER1E -->
			<div class="row panel panel-warning CARRIER_ONLY">
				<div class="panel-heading"><h3 class="panel-title">Carrier Pay</h3></div>
				<div class="panel-body">
				<div class="col-sm-1">
					+EDIT_AMOUNTS+
				</div>
				<div class="col-sm-11">
					<div class="form-group">
						<label for="TERMS" class="col-sm-3 control-label">#TERMS#</label>
						<div class="col-sm-2 text-right">
							%TERMS%
						</div>
					</div>
					<div class="form-group">
						<label for="CARRIER_BASE" class="col-sm-3 control-label">#CARRIER_BASE#</label>
						<div class="col-sm-2">
							%CARRIER_BASE%
						</div>
					</div>
					<div class="form-group">
						<label for="CARRIER_FSC" class="col-sm-3 control-label">#CARRIER_FSC#</label>
						<div class="col-sm-2">
							%CARRIER_FSC%
						</div>
					</div>
					<div class="panel panel-default">
						<div class="panel-body">
					<div class="form-group">
						<label for="CARRIER_HANDLING" class="col-sm-3 control-label">#CARRIER_HANDLING#</label>
						<div class="col-sm-2">
							%CARRIER_HANDLING%
						</div>
					</div>
					<div class="form-group cdn-tax">
						<label for="LUMPER_TAX" class="col-sm-3 control-label">#LUMPER_TAX#</label>
						<div class="col-sm-2">
							%LUMPER_TAX%
						</div>
						<div class="col-sm-4" id="CDN_TAX">
						</div>
					</div>
					<div class="form-group">
						<label for="LUMPER_TOTAL" class="col-sm-3 control-label">#LUMPER_TOTAL#</label>
						<div class="col-sm-2">
							<strong>%LUMPER_TOTAL%</strong>
						</div>
						<div class="col-sm-1">
					<!-- CURRENCY1 -->
							<strong>%LUMPER_CURRENCY%</strong>
					<!-- CURRENCY2 -->
						</div>
						<div class="col-sm-6">
							%LUMPER%
						</div>
					</div>
						</div>
					</div>

					<!-- XYZZY -->
					<div class="form-group">
					<label for="CARRIER_TOTAL" class="col-sm-3 control-label">#CARRIER_TOTAL#</label>
						<div class="col-sm-2">
							<strong>%CARRIER_TOTAL%</strong>
						</div>
						<div class="col-sm-1">
					<!-- CURRENCY3 -->
							<strong>%CURRENCY%</strong>
					<!-- CURRENCY4 -->
						</div>
						<div class="col-sm-6">
							%CARRIER%
						</div>
					</div>
				</div>
			</div>
			</div>
			<!-- SAGE501 -->
			<div class="panel panel-success APPROVED_ONLY">
				<div class="panel-heading">
					<h3 class="panel-title">Sage 50 Export Status</h3>
				</div>
				<div class="panel-body">
					<div class="form-group">
						<label for="quickbooks_status_message" class="col-sm-4 control-label">#quickbooks_status_message#</label>
						<div class="col-sm-8">
							%quickbooks_status_message%
						</div>
					</div>
				</div>
			</div>
			<!-- SAGE502 -->
			<!-- QUICKBOOKS1 -->
			<div class="panel panel-success CARRIER_ONLY">
				<div class="panel-heading">
					<h3 class="panel-title">Quickbooks Export Status</h3>
				</div>
				<div class="panel-body">
					<div class="form-group">
						<label for="quickbooks_listid_carrier" class="col-sm-4 control-label">#quickbooks_listid_carrier#</label>
						<div class="col-sm-4">
							%quickbooks_listid_carrier%
						</div>
					</div>
					<div class="form-group">
						<label for="quickbooks_txnid_ap" class="col-sm-4 control-label">#quickbooks_txnid_ap#</label>
						<div class="col-sm-4">
							%quickbooks_txnid_ap%
						</div>
					</div>
					<div class="form-group">
						<label for="quickbooks_status_message" class="col-sm-4 control-label">#quickbooks_status_message#</label>
						<div class="col-sm-8">
							%quickbooks_status_message%
						</div>
					</div>
				</div>
			</div>
			<!-- QUICKBOOKS2 -->
			<!-- CARRIER2D -->
		</div>
	</div>
	
	'
);

$sts_form_edit_carrier_amt_form = array(	//! $sts_form_edit_carrier_amt_form
	'title' => '<img src="images/load_icon.png" alt="load_icon" height="24"> Edit Carrier Amounts For Load %LOAD_CODE%',
	'action' => 'exp_editcarrier_amt.php',
	//'actionextras' => 'disabled',
	'cancel' => 'exp_viewload.php?CODE=%LOAD_CODE%',
	'name' => 'edit_carrier_amounts',
	'okbutton' => 'Save Changes',
	//'saveadd' => 'Add Another',
	'cancelbutton' => 'Back',
		'layout' => '
	%LOAD_CODE%
	%CONS_COUNTRY%
	<div class="form-group">
		<div class="col-sm-5">
				<!-- CURRENCY1 -->
				<div class="form-group">
					<label for="CURRENCY" class="col-sm-5 control-label">#CURRENCY#</label>
					<div class="col-sm-4">
						%CURRENCY%
					</div>
				</div>
				<!-- CURRENCY2 -->
				<div class="form-group">
					<label for="TERMS" class="col-sm-5 control-label">#TERMS#</label>
					<div class="col-sm-4">
						%TERMS%
					</div>
				</div>
				<div class="form-group">
					<label for="CARRIER_BASE" class="col-sm-5 control-label">#CARRIER_BASE#</label>
					<div class="col-sm-4">
						%CARRIER_BASE%
					</div>
				</div>
				<div class="form-group">
					<label for="CARRIER_FSC" class="col-sm-5 control-label">#CARRIER_FSC#</label>
					<div class="col-sm-4">
						%CARRIER_FSC%
					</div>
				</div>
				<div class="row panel panel-warning">
					<div class="panel-heading"><h3 class="panel-title">Handling/Lumper</h3></div>
					<div class="panel-body">
				<div class="form-group nolumper">
					<label for="LUMPER" class="col-sm-5 control-label">#LUMPER#</label>
					<div class="col-sm-5">
						%LUMPER%
					</div>
					<div class="col-sm-1"><h3 class="text-info">*</h3></div>
				</div>
				<div class="form-group">
					<label for="CARRIER_HANDLING" class="col-sm-5 control-label">#CARRIER_HANDLING#</label>
					<div class="col-sm-4">
						%CARRIER_HANDLING%
					</div>
					<div class="col-sm-3 lumper">
						%LUMPER_CURRENCY%
					</div>
				</div>
				<div class="form-group cdn-tax2">
					<label for="CDN_TAX_EXEMPT" class="col-sm-5 control-label"><img src="images/flag-ca.png" alt="flag-ca" width="48" height="24"> #CDN_TAX_EXEMPT#</label>
					<div class="col-sm-4">
						%CDN_TAX_EXEMPT%
					</div>
				</div>
				<div class="form-group cdn-tax">
					<label for="LUMPER_TAX" id="CDN_TAX" class="col-sm-5 control-label">CDN Tax</label>
					<div class="col-sm-4">
						%LUMPER_TAX%
					</div>
				</div>
				<div class="form-group">
					<label for="LUMPER_TOTAL" class="col-sm-5 control-label"><h4>#LUMPER_TOTAL#</h4></label>
					<div class="col-sm-4">
						<h4 id="LUMPER_TOTAL" class="form-control-static text-right" style="margin-right: 10px;"></h4>
					</div>
					<div class="col-sm-3">
						<h4 id="LUMPER_CURRENCY_STATIC"></h4>
					</div>
				</div>

				<div id="LUMPER_ISSUE_STATIC" class="cdn-tax"></div>

				</div>
				</div>
				<div class="form-group">
					<label for="CARRIER_TOTAL" class="col-sm-5 control-label"><h3>Total</h3></label>
					<div class="col-sm-4">
						
	<h3 id="CARRIER_TOTAL_STATIC" class="form-control-static text-right" style="margin-right: 10px;"></h3>
					</div>
					<div class="col-sm-3">
						<h3 id="CURRENCYT" class="form-control-static"></h3>
					</div>
				</div>

		</div>
		<div class="col-sm-7">
				<div class="form-group">
					<label for="CARRIER_NOTE" class="col-sm-3 control-label">#CARRIER_NOTE#</label>
					<div class="col-sm-9">
						%CARRIER_NOTE%
					</div>
				</div>
				<div class="form-group bg-success">
					<label for="LOAD_NOTE" class="col-sm-3 control-label">#LOAD_NOTE#</label>
					<div class="col-sm-9">
						%LOAD_NOTE%
					</div>
				</div>
				<div>
					<p class="text-info">* - Lumpers will not appear if the date for the General Insurance is expired.</p>
				</div>
		</div>
	</div>
	
	'
);

$sts_form_edit_driver_note_form = array(	//! $sts_form_edit_driver_note_form
	'title' => '<img src="images/load_icon.png" alt="load_icon" height="24"> Edit Driver Note For Load %LOAD_CODE%',
	'action' => 'exp_editcarrier_amt.php',
	//'actionextras' => 'disabled',
	'cancel' => 'exp_viewload.php?CODE=%LOAD_CODE%',
	'name' => 'edit carrier amounts',
	'okbutton' => 'Save Changes',
	//'saveadd' => 'Add Another',
	'cancelbutton' => 'Back',
		'layout' => '
	%LOAD_CODE%
	<div class="form-group">
		<div class="col-sm-11">
			<div class="form-group">
				<label for="CARRIER_NOTE" class="col-sm-3 control-label">#CARRIER_NOTE#</label>
				<div class="col-sm-9">
					%CARRIER_NOTE%
				</div>
			</div>
			<div class="form-group bg-success">
				<label for="LOAD_NOTE" class="col-sm-3 control-label">#LOAD_NOTE#</label>
				<div class="col-sm-9">
					%LOAD_NOTE%
				</div>
			</div>
		</div>
	</div>
	
	'
);

$sts_form_editload_form = array(
	'title' => '<img src="images/load_icon.png" alt="load_icon" height="24"> Edit Unit',
	'action' => 'exp_editload.php',
	//'actionextras' => 'disabled',
	'cancel' => 'exp_listload.php',
	'name' => 'editload',
	'okbutton' => 'Save Changes to Unit',
	'cancelbutton' => 'Back to Units',
		'layout' => '
		%LOAD_CODE%
	<div class="form-group">
		<div class="col-sm-4">
			<div class="form-group">
				<label for="NAME" class="col-sm-4 control-label">#NAME#</label>
				<div class="col-sm-8">
					%NAME%
				</div>
			</div>
			<div class="form-group">
				<label for="LOAD_TYPE" class="col-sm-4 control-label">#LOAD_TYPE#</label>
				<div class="col-sm-8">
					%LOAD_TYPE%
				</div>
			</div>
			<div class="form-group">
				<label for="SYMBOL" class="col-sm-4 control-label">#SYMBOL#</label>
				<div class="col-sm-8">
					%SYMBOL%
				</div>
			</div>
		</div>
		<div class="col-sm-4">
			<div class="form-group">
				<label for="BASE" class="col-sm-4 control-label">#BASE#</label>
				<div class="col-sm-8">
					%BASE%
				</div>
			</div>
			<div class="form-group">
				<label for="CONV_FACTOR" class="col-sm-4 control-label">#CONV_FACTOR#</label>
				<div class="col-sm-8">
					%CONV_FACTOR%
				</div>
			</div>
		</div>
		<div class="col-sm-4">
		</div>
	</div>
	
	'
);

//! Field Specifications - For use with sts_form

$sts_form_add_load_fields = array( //! $sts_form_add_load_fields
	'LOAD_CODE' => array( 'label' => 'Load#', 'format' => 'static' ),
	'CURRENT_STATUS' => array( 'label' => 'Status', 'format' => 'table',
		'table' => STATUS_CODES_TABLE, 'key' => 'STATUS_CODES_CODE', 'fields' => 'STATUS_STATE',
		'static' => true ),
	'CURRENT_STOP' => array( 'label' => 'Current Stop', 'format' => 'static' ),
	'DRIVER' => array( 'label' => 'Driver', 'format' => 'table',
		'table' => DRIVER_TABLE, 'key' => 'DRIVER_CODE', 'fields' => 'FIRST_NAME,LAST_NAME',
		'static' => true ),
	'TRACTOR' => array( 'label' => 'Tractor', 'format' => 'table',
		'table' => TRACTOR_TABLE, 'key' => 'TRACTOR_CODE', 'fields' => 'UNIT_NUMBER',
		'static' => true ),
	'TRAILER' => array( 'label' => 'Trailer', 'format' => 'table',
		'table' => TRAILER_TABLE, 'key' => 'TRAILER_CODE', 'fields' => 'UNIT_NUMBER',
		'static' => true ),
	'CARRIER' => array( 'label' => 'Carrier', 'format' => 'table',
		'table' => CARRIER_TABLE, 'key' => 'CARRIER_CODE', 'fields' => 'CARRIER_NAME',
		'static' => true )
);

$sts_form_view_load_fields = array(	//! sts_form_view_load_fields
	'LOAD_CODE' => array( 'label' => 'Load#', 'format' => 'static', 'align' => 'right' ),
	'CURRENT_STATUS' => array( 'label' => 'Status', 'format' => 'table',
		'table' => STATUS_CODES_TABLE, 'key' => 'STATUS_CODES_CODE', 'fields' => 'STATUS_STATE',
		'static' => true, 'align' => 'right' ),
	'CURRENT_STOP' => array( 'label' => 'Current Stop', 'format' => 'static', 'align' => 'right' ),
	'TOTAL_DISTANCE' => array( 'label' => 'Total Distance', 'format' => 'static', 'align' => 'right' ),
	'EMPTY_DISTANCE' => array( 'label' => 'Empty Distance', 'format' => 'static', 'align' => 'right' ),
	'ODOMETER_FROM' => array( 'label' => 'Odometer From', 'format' => 'static', 'align' => 'right' ),
	'ODOMETER_TO' => array( 'label' => 'Odometer To', 'format' => 'static', 'align' => 'right' ),
	'ACTUAL_DISTANCE' => array( 'label' => 'Actual Distance', 'format' => 'static', 'align' => 'right' ),
	'DRIVER' => array( 'label' => 'Driver', 'format' => 'table',
		'table' => DRIVER_TABLE, 'key' => 'DRIVER_CODE', 'fields' => 'FIRST_NAME,LAST_NAME',
		'static' => true, 'link' => 'exp_editdriver.php?CODE=' ),
	'DRIVER2' => array( 'label' => 'Driver 2', 'format' => 'table',
		'table' => DRIVER_TABLE, 'key' => 'DRIVER_CODE', 'fields' => 'FIRST_NAME,LAST_NAME',
		'static' => true, 'link' => 'exp_editdriver.php?CODE=' ),
	'DRIVER_LABEL' => array( 'label' => 'Label', 'format' => 'static' ),
	'DRIVER2_LABEL' => array( 'label' => 'Label', 'format' => 'static' ),
	'TRACTOR' => array( 'label' => 'Tractor', 'format' => 'table',
		'table' => TRACTOR_TABLE, 'key' => 'TRACTOR_CODE', 'fields' => 'UNIT_NUMBER',
		'static' => true, 'link' => 'exp_edittractor.php?CODE=' ),
	//! I can't combine format=table and format=date, so I use fields2 to format in MySQL
	'MOBILE_TIME' => array( 'label' => 'Mobile Time', 'format' => 'table',
		'table' => TRACTOR_TABLE, 'key' => 'TRACTOR_CODE',
		'fields' => "MOBILE_TIME",
		'fields2' => "DATE_FORMAT(MOBILE_TIME,'%m/%d/%Y %H:%i') AS MOBILE_TIME",
		'pk' => 'TRACTOR', 'static' => true ),
	// Need to link to map of MOBILE_LATITUDE/MOBILE_LONGITUDE
	'MOBILE_LOCATION' => array( 'label' => 'Mobile Location', 'format' => 'table',
		'table' => TRACTOR_TABLE, 'key' => 'TRACTOR_CODE', 'fields' => 'MOBILE_LOCATION',
		'pk' => 'TRACTOR', 'raw' => true ),
	'MOBILE_LATITUDE' => array( 'label' => 'Mobile Lat', 'format' => 'table',
		'table' => TRACTOR_TABLE, 'key' => 'TRACTOR_CODE', 'fields' => 'MOBILE_LATITUDE',
		'pk' => 'TRACTOR', 'raw' => true ),
	'MOBILE_LONGITUDE' => array( 'label' => 'Mobile Lon', 'format' => 'table',
		'table' => TRACTOR_TABLE, 'key' => 'TRACTOR_CODE', 'fields' => 'MOBILE_LONGITUDE',
		'pk' => 'TRACTOR', 'raw' => true ),
	'TRAILER' => array( 'label' => 'Trailer', 'format' => 'table',
		'table' => TRAILER_TABLE, 'key' => 'TRAILER_CODE', 'fields' => 'UNIT_NUMBER',
		'static' => true, 'link' => 'exp_edittrailer.php?CODE=' ),
	'CARRIER' => array( 'label' => 'Carrier', 'format' => 'table',
		'table' => CARRIER_TABLE, 'key' => 'CARRIER_CODE', 'fields' => 'CARRIER_NAME',
		'inline' => true, 'link' => 'exp_editcarrier.php?CODE=' ),
	'CTPAT_CERTIFIED' => array( 'label' => 'C-TPAT', 'format' => 'inline' ),
	'CURRENCY' => array( 'label' => 'Currency', 'format' => 'static', 'align' => 'right' ),
	'CARRIER_BASE' => array( 'label' => 'Base Pay', 'format' => 'static', 'align' => 'right', 'decimal' => 2 ),
	'CARRIER_FSC' => array( 'label' => '+ Fuel Surcharge', 'format' => 'static', 'align' => 'right', 'decimal' => 2 ),
	'CARRIER_HANDLING' => array( 'label' => 'Handling/Lumper', 'format' => 'static', 'align' => 'right', 'decimal' => 2 ),
	'LUMPER_TAX' => array( 'label' => '+ Lumper tax', 'format' => 'static', 'align' => 'right', 'decimal' => 2 ),
	'LUMPER' => array( 'label' => 'Pay Separtate Lumper', 'format' => 'table',
		'table' => CARRIER_TABLE, 'key' => 'CARRIER_CODE', 'fields' => 'CARRIER_NAME',
		'condition' => "CARRIER_TYPE IN ('lumper', 'shag')", 'link' => 'exp_editcarrier.php?CODE=', 'static' => true,  ),
	'LUMPER_CURRENCY' => array( 'label' => 'Lumper Currency', 'format' => 'static', 'align' => 'right' ),
	'LUMPER_TOTAL' => array( 'label' => '= Lumper Total', 'format' => 'static', 'align' => 'right', 'extras' => 'readonly', 'decimal' => 2 ),

	'CARRIER_TOTAL' => array( 'label' => 'Total Due To Carrier', 'format' => 'static', 'align' => 'right', 'decimal' => 2 ),
	
//XYZZY
	'CARRIER_EMAIL' => array( 'label' => 'Email', 'format' => 'static' ),
	'CREATED_DATE' => array( 'label' => 'Created', 'format' => 'datetime',
		'extras' => 'readonly', 'align' => 'right' ),
	'CARRIER_MANIFEST_SENT' => array( 'label' => 'Manifest Sent', 'format' => 'datetime',
		'extras' => 'readonly', 'align' => 'right' ),
	'DISPATCHED_DATE' => array( 'label' => 'Dispatched', 'format' => 'datetime',
		'extras' => 'readonly', 'align' => 'right' ),
	'COMPLETED_DATE' => array( 'label' => 'Completed', 'format' => 'datetime',
		'extras' => 'readonly', 'align' => 'right' ),
	'PAID_DATE' => array( 'label' => 'Paid', 'format' => 'datetime',
		'extras' => 'readonly', 'align' => 'right' ),
	'CARRIER_NOTE' => array( 'label' => 'Note', 'format' => 'textarea', 'extras' => 'readonly' ),
	'LOAD_NOTE' => array( 'label' => 'Load Note<br>(not in manifest)', 'format' => 'textarea', 'extras' => 'readonly' ),
	'quickbooks_listid_carrier' => array( 'label' => 'Vendor ID', 'format' => 'static', 'align' => 'right' ),
	'quickbooks_txnid_ap' => array( 'label' => 'Transaction ID', 'format' => 'static', 'align' => 'right' ),
	'quickbooks_status_message' => array( 'label' => 'Transfer Status', 'format' => 'static' ),
	
	//! New EDI fields, grab from prime shipment
	'CONSOLIDATE_NUM' => array( 'label' => 'Cons&nbsp;#', 'format' => 'text' ),
	'EDI_204_L301_WEIGHT' => array( 'label' => 'Weight', 'format' => 'numberc', 'align' => 'right', 'extras' => 'readonly' ),
	'EDI_204_L303_RATE' => array( 'label' => 'Rate', 'format' => 'numberc', 'align' => 'right', 'extras' => 'readonly' ),
	'EDI_204_L304_RQUAL' => array( 'label' => 'Rate&nbsp;Qual', 'format' => 'inline' ),
	'EDI_204_L305_CHARGE' => array( 'label' => 'Charge', 'format' => 'numberc', 'align' => 'right', 'extras' => 'readonly' ),
	'EDI_204_NTE_MILES' => array( 'label' => 'Distance', 'format' => 'numberc', 'align' => 'right', 'extras' => 'readonly' ),
	'EDI_204_L312_WQUAL' => array( 'label' => 'Weight&nbsp;Units', 'format' => 'static', 'align' => 'left' ),
	'EDI_204_ISA15_USAGE' => array( 'label' => '204&nbsp;Usage', 'format' => 'inline', 'align' => 'right' ),
	'EDI_204_GS04_OFFERED' => array( 'label' => '204&nbsp;Offered&nbsp;on', 'format' => 'timestamp', 'align' => 'right', 'extras' => 'readonly' ),
	'EDI_204_G6202_EXPIRES' => array( 'label' => '204&nbsp;Expires&nbsp;on', 'format' => 'timestamp', 'align' => 'right', 'extras' => 'readonly' ),
	'EDI_990_SENT' => array( 'label' => '990&nbsp;Sent', 'format' => 'timestamp', 'align' => 'right', 'placeholder' => 'Not Yet Sent', 'extras' => 'readonly' ),
	'EDI_990_STATUS' => array( 'label' => '990&nbsp;Status', 'format' => 'inline' ),
	'EDI_204_ORIGIN' => array( 'label' => '204&nbsp;Origin', 'format' => 'inline' ),
	'EDI_204_B204_SID' => array( 'label' => '204&nbsp;Shipment&nbsp;ID', 'format' => 'inline' ),
	'EDI_204_B206_PAYMENT' => array( 'label' => '204&nbsp;Payment', 'format' => 'inline' ),
	'EDI_204_L1101_SERVICE' => array( 'label' => 'Service', 'format' => 'text', 'align' => 'right', 'extras' => 'readonly' ),
	'OFFICE_CODE' => array( 'label' => 'Office', 'format' => 'table',
		'table' => OFFICE_TABLE, 'key' => 'OFFICE_CODE', 'fields' => 'OFFICE_NAME',
		'static' => true ),
	'TERMS' => array( 'label' => 'Terms', 'format' => 'table',
		'table' => ITEM_LIST_TABLE, 'key' => 'ITEM_CODE', 'fields' => 'ITEM',
		'condition' => 'ITEM_TYPE = \'Vendor Terms\'', 'inline' => true ),
);

$sts_form_edit_carrier_amt_fields = array( //! $sts_form_edit_carrier_amt_fields
	'LOAD_CODE' => array( 'label' => 'Load#', 'format' => 'hidden' ),
	'CONS_COUNTRY' => array( 'label' => 'Cons Country', 'format' => 'btable',
		'table' => SHIPMENT_TABLE, 'key' => 'LOAD_CODE', 'fields' => 'CONS_COUNTRY',
		'condition' => 'EXP_SHIPMENT.LOAD_CODE = EXP_LOAD.LOAD_CODE LIMIT 1',
		'pk' => 'LOAD_CODE', 'hidden' => true ),
	'CURRENCY' => array( 'label' => 'Currency', 'format' => 'enum', 'align' => 'right' ),
	'CARRIER_BASE' => array( 'label' => 'Carrier Base', 'format' => 'number', 'align' => 'right' ),
	'CARRIER_FSC' => array( 'label' => 'Fuel Surcharge', 'format' => 'number', 'align' => 'right' ),
	'CARRIER_HANDLING' => array( 'label' => 'Handling/Lumper Fee', 'format' => 'number', 'align' => 'right' ),
	'CDN_TAX_EXEMPT' => array( 'label' => 'CDN Tax Exempt', 'format' => 'bool', 'align' => 'right' ),
	'LUMPER_TAX' => array( 'label' => 'Lumper tax', 'format' => 'number', 'align' => 'right', 'extras' => 'readonly' ),
	'LUMPER_TOTAL' => array( 'label' => 'Lumper Total', 'format' => 'number', 'align' => 'right', 'extras' => 'readonly' ),
	//! SCR# 763 - Filter lumpers based on LIABILITY_DATE being current
	'LUMPER' => array( 'label' => 'Pay Separtate Lumper', 'format' => 'table',
		'table' => CARRIER_TABLE, 'key' => 'CARRIER_CODE', 'fields' => 'CARRIER_NAME',
		'condition' => "CARRIER_TYPE IN ('lumper', 'shag') AND ISDELETED = false AND COALESCE(DATEDIFF(LIABILITY_DATE, CURRENT_DATE), 0) > 0", 'nolink' => true ),
	'LUMPER_CURRENCY' => array( 'label' => 'Lumper Currency', 'format' => 'enum' ),
	'CARRIER_NOTE' => array( 'label' => 'Note', 'format' => 'textarea', 'extras' => 'rows="6"' ),
	'LOAD_NOTE' => array( 'label' => 'Load Note<br>(not in manifest)', 'format' => 'textarea', 'extras' => 'rows="6"' ),
	'TERMS' => array( 'label' => 'Terms', 'format' => 'table',
		'table' => ITEM_LIST_TABLE, 'key' => 'ITEM_CODE', 'fields' => 'ITEM',
		'condition' => 'ITEM_TYPE = \'Vendor Terms\'' ),
);

$sts_form_edit_driver_note_fields = array( //! $sts_form_edit_driver_note_fields
	'LOAD_CODE' => array( 'label' => 'Load#', 'format' => 'hidden' ),
	'CARRIER_NOTE' => array( 'label' => 'Note', 'format' => 'textarea', 'extras' => 'rows="6"' ),
	'LOAD_NOTE' => array( 'label' => 'Load Note<br>(not in manifest)', 'format' => 'textarea', 'extras' => 'rows="6"' ),
);


$sts_form_edit_load_fields = array( //! $sts_form_edit_load_fields
	'LOAD_CODE' => array( 'label' => 'Load#', 'format' => 'static' ),
	'CURRENT_STATUS' => array( 'label' => 'Status', 'format' => 'table',
		'table' => STATUS_CODES_TABLE, 'key' => 'STATUS_CODES_CODE', 'fields' => 'STATUS_STATE' ),
	'DRIVER' => array( 'label' => 'Driver', 'format' => 'table',
		'table' => DRIVER_TABLE, 'key' => 'DRIVER_CODE', 'fields' => 'FIRST_NAME,LAST_NAME',
		'static' => true ),
	'TRACTOR' => array( 'label' => 'Tractor', 'format' => 'table',
		'table' => TRACTOR_TABLE, 'key' => 'TRACTOR_CODE', 'fields' => 'UNIT_NUMBER',
		'static' => true ),
	'TRAILER' => array( 'label' => 'Trailer', 'format' => 'table',
		'table' => TRAILER_TABLE, 'key' => 'TRAILER_CODE', 'fields' => 'UNIT_NUMBER',
		'static' => true ),
	'CARRIER' => array( 'label' => 'Carrier', 'format' => 'table',
		'table' => CARRIER_TABLE, 'key' => 'CARRIER_CODE', 'fields' => 'CARRIER_NAME',
		'static' => true )
);

//! Layout Specifications - For use with sts_result

$sts_result_loads_layout = array( //! $sts_result_loads_layout
	'LOAD_CODE' => array( 'label' => 'Load#', 'format' => 'static' ),
	'CURRENT_STATUS' => array( 'label' => 'Status', 'format' => 'table',
		'table' => STATUS_CODES_TABLE, 'key' => 'STATUS_CODES_CODE', 'fields' => 'STATUS_STATE' ),
	'SHIPMENTS' => array( 'label' => 'Shipments', 'format' => 'count',
		'table' => SHIPMENT_TABLE, 'key' => 'LOAD_CODE','pk' => 'LOAD_CODE',
		'align' => 'right' ),
	'STOPS' => array( 'label' => 'Stops', 'format' => 'count',
		'table' => STOP_TABLE, 'key' => 'LOAD_CODE','pk' => 'LOAD_CODE',
		'align' => 'right' ),
	'PALLETS' => array( 'label' => 'Pallets', 'format' => 'sum', 'field' => 'PALLETS',
		'table' => SHIPMENT_TABLE, 'key' => 'LOAD_CODE','pk' => 'LOAD_CODE',
		'align' => 'right' ),
	'WEIGHT' => array( 'label' => 'Weight', 'format' => 'sum', 'field' => 'WEIGHT',
		'table' => SHIPMENT_TABLE, 'key' => 'LOAD_CODE','pk' => 'LOAD_CODE',
		'align' => 'right' ),
	'DRIVER' => array( 'label' => 'Driver', 'format' => 'table',
		'table' => DRIVER_TABLE, 'key' => 'DRIVER_CODE', 'fields' => 'concat_ws( \' \', FIRST_NAME , LAST_NAME )' ),
	'TRACTOR' => array( 'label' => 'Tractor', 'format' => 'table',
		'table' => TRACTOR_TABLE, 'key' => 'TRACTOR_CODE', 'fields' => 'UNIT_NUMBER' ),
	'TRAILER' => array( 'label' => 'Trailer', 'format' => 'table',
		'table' => TRAILER_TABLE, 'key' => 'TRAILER_CODE', 'fields' => 'UNIT_NUMBER' ),
	'CARRIER' => array( 'label' => 'Carrier', 'format' => 'table',
		'table' => CARRIER_TABLE, 'key' => 'CARRIER_CODE', 'fields' => 'CARRIER_NAME' )
);

$sts_result_loads_last5_layout = array( //! $sts_result_loads_last5_layout
	'LOAD_CODE' => array( 'label' => 'Load#', 'format' => 'num0nc', 'link' => 'exp_viewload.php?CODE=', 'align' => 'right' ),
	'CURRENT_STATUS' => array( 'label' => 'Status', 'format' => 'table',
		'table' => STATUS_CODES_TABLE, 'key' => 'STATUS_CODES_CODE', 'fields' => 'STATUS_STATE', 'length' => 90 ),
	'SHIPMENTS' => array( 'label' => 'Shipments', 'format' => 'count',
		'table' => SHIPMENT_TABLE, 'key' => 'LOAD_CODE','pk' => 'LOAD_CODE',
		'align' => 'right' ),
	'STOPS' => array( 'label' => 'Stops', 'format' => 'count',
		'table' => STOP_TABLE, 'key' => 'LOAD_CODE','pk' => 'LOAD_CODE',
		'align' => 'right' ),
	'PALLETS' => array( 'label' => 'Pallets', 'format' => 'sum', 'field' => 'PALLETS',
		'table' => SHIPMENT_TABLE, 'key' => 'LOAD_CODE','pk' => 'LOAD_CODE',
		'align' => 'right' ),
	'WEIGHT' => array( 'label' => 'Weight', 'format' => 'sum', 'format2' => 'num2nc', 'field' => 'WEIGHT',
		'table' => SHIPMENT_TABLE, 'key' => 'LOAD_CODE','pk' => 'LOAD_CODE',
		'align' => 'right' ),
	'CHANGED_DATE' => array( 'label' => 'Changed', 'format' => 'timestamp-s', 'length' => 90 ),
	'CHANGED_BY' => array( 'label' => 'By', 'format' => 'table',
		'table' => USER_TABLE, 'key' => 'USER_CODE', 'fields' => 'USERNAME', 'link' => 'exp_edituser.php?CODE=' )
);

$sts_result_loads_carrier_layout = array( //! $sts_result_loads_carrier_layout
	'LOAD_CODE' => array( 'label' => 'Load#', 'format' => 'num0nc', 'link' => 'exp_viewload.php?CODE=', 'align' => 'right' ),
	'CARRIER' => array( 'label' => 'Carrier', 'format' => 'table',
		'table' => CARRIER_TABLE, 'key' => 'CARRIER_CODE', 'fields' => 'CARRIER_NAME' ),
	'CURRENT_STATUS' => array( 'label' => 'Status', 'format' => 'table',
		'table' => STATUS_CODES_TABLE, 'key' => 'STATUS_CODES_CODE', 'fields' => 'STATUS_STATE', 'length' => 90 ),
	'SHIPMENTS' => array( 'label' => 'Shipments', 'format' => 'count',
		'table' => SHIPMENT_TABLE, 'key' => 'LOAD_CODE','pk' => 'LOAD_CODE',
		'align' => 'right' ),
	'STOPS' => array( 'label' => 'Stops', 'format' => 'count',
		'table' => STOP_TABLE, 'key' => 'LOAD_CODE','pk' => 'LOAD_CODE',
		'align' => 'right' ),
	'PALLETS' => array( 'label' => 'Pallets', 'format' => 'sum', 'field' => 'PALLETS',
		'table' => SHIPMENT_TABLE, 'key' => 'LOAD_CODE','pk' => 'LOAD_CODE',
		'align' => 'right' ),
	'WEIGHT' => array( 'label' => 'Weight', 'format' => 'sum', 'format2' => 'num2nc', 'field' => 'WEIGHT',
		'table' => SHIPMENT_TABLE, 'key' => 'LOAD_CODE','pk' => 'LOAD_CODE',
		'align' => 'right' ),
	'TOTAL_DISTANCE' => array( 'label' => 'Distance', 'format' => 'num2nc' ),
	'CARRIER_MANIFEST_SENT' => array( 'label' => 'Manifest', 'format' => 'date', 'length' => 90 ),
	'DISPATCHED_DATE' => array( 'label' => 'Dispatched', 'format' => 'date', 'length' => 90 ),
	'COMPLETED_DATE' => array( 'label' => 'Completed', 'format' => 'date', 'length' => 90 ),
	'CHANGED_DATE' => array( 'label' => 'Changed', 'format' => 'timestamp-s', 'length' => 90 ),
	'CHANGED_BY' => array( 'label' => 'By', 'format' => 'table',
		'table' => USER_TABLE, 'key' => 'USER_CODE', 'fields' => 'USERNAME', 'link' => 'exp_edituser.php?CODE=' )
);

$sts_result_loads_lumper_layout = array( //! $sts_result_loads_lumper_layout
	'LOAD_CODE' => array( 'label' => 'Load#', 'format' => 'num0nc', 'link' => 'exp_viewload.php?CODE=', 'align' => 'right' ),
	'CARRIER' => array( 'label' => 'Carrier', 'format' => 'table',
		'table' => CARRIER_TABLE, 'key' => 'CARRIER_CODE', 'fields' => 'CARRIER_NAME', 'length' => 140 ),
	'CURRENT_STATUS' => array( 'label' => 'Status', 'format' => 'table',
		'table' => STATUS_CODES_TABLE, 'key' => 'STATUS_CODES_CODE', 'fields' => 'STATUS_STATE', 'length' => 90 ),
	'SHIPMENTS' => array( 'label' => 'Shipments', 'format' => 'count',
		'table' => SHIPMENT_TABLE, 'key' => 'LOAD_CODE','pk' => 'LOAD_CODE',
		'align' => 'right' ),
	'STOPS' => array( 'label' => 'Stops', 'format' => 'count',
		'table' => STOP_TABLE, 'key' => 'LOAD_CODE','pk' => 'LOAD_CODE',
		'align' => 'right' ),
	'PALLETS' => array( 'label' => 'Pallets', 'format' => 'sum', 'field' => 'PALLETS',
		'table' => SHIPMENT_TABLE, 'key' => 'LOAD_CODE','pk' => 'LOAD_CODE',
		'align' => 'right' ),
	'WEIGHT' => array( 'label' => 'Weight', 'format' => 'sum', 'format2' => 'num2nc', 'field' => 'WEIGHT',
		'table' => SHIPMENT_TABLE, 'key' => 'LOAD_CODE','pk' => 'LOAD_CODE',
		'align' => 'right' ),
	'LUMPER' => array( 'label' => 'Lumper', 'format' => 'table',
		'table' => CARRIER_TABLE, 'key' => 'CARRIER_CODE', 'fields' => 'CARRIER_NAME', 'length' => 140 ),
	'LUMPER_AMT' => array( 'label' => 'Lumper Fee', 'format' => 'table', 'align' => 'right',
		'snippet' => "CONCAT(ROUND(LUMPER_TOTAL,2), ' ', LUMPER_CURRENCY)", 'length' => 80 ),

	'CHANGED_DATE' => array( 'label' => 'Changed', 'format' => 'timestamp-s', 'length' => 90 ),
	'CHANGED_BY' => array( 'label' => 'By', 'format' => 'table',
		'table' => USER_TABLE, 'key' => 'USER_CODE', 'fields' => 'USERNAME', 'link' => 'exp_edituser.php?CODE=' )
);

$sts_result_loads_driver2_layout = array( //! $sts_result_loads_driver2_layout
	'LOAD_CODE' => array( 'label' => 'Load#', 'format' => 'num0nc', 'link' => 'exp_viewload.php?CODE=', 'align' => 'right' ),
	'DRIVER' => array( 'label' => 'Driver', 'format' => 'table',
		'table' => DRIVER_TABLE, 'key' => 'DRIVER_CODE',
		'fields' => 'concat_ws(\'&nbsp;\',FIRST_NAME,LAST_NAME) name' ),
	'CURRENT_STATUS' => array( 'label' => 'Status', 'format' => 'table',
		'table' => STATUS_CODES_TABLE, 'key' => 'STATUS_CODES_CODE', 'fields' => 'STATUS_STATE', 'length' => 90 ),
	'SHIPMENTS' => array( 'label' => 'Shipments', 'format' => 'count',
		'table' => SHIPMENT_TABLE, 'key' => 'LOAD_CODE','pk' => 'LOAD_CODE',
		'align' => 'right' ),
	'STOPS' => array( 'label' => 'Stops', 'format' => 'count',
		'table' => STOP_TABLE, 'key' => 'LOAD_CODE','pk' => 'LOAD_CODE',
		'align' => 'right' ),
	'PALLETS' => array( 'label' => 'Pallets', 'format' => 'sum', 'field' => 'PALLETS',
		'table' => SHIPMENT_TABLE, 'key' => 'LOAD_CODE','pk' => 'LOAD_CODE',
		'align' => 'right' ),
	'WEIGHT' => array( 'label' => 'Weight', 'format' => 'sum', 'format2' => 'num2nc', 'field' => 'WEIGHT',
		'table' => SHIPMENT_TABLE, 'key' => 'LOAD_CODE','pk' => 'LOAD_CODE',
		'align' => 'right' ),
	'TOTAL_DISTANCE' => array( 'label' => 'Distance', 'format' => 'num2nc' ),
	'DISPATCHED_DATE' => array( 'label' => 'Dispatched', 'format' => 'date', 'length' => 90 ),
	'COMPLETED_DATE' => array( 'label' => 'Completed', 'format' => 'date', 'length' => 90 ),
	'CHANGED_DATE' => array( 'label' => 'Changed', 'format' => 'timestamp-s', 'length' => 90 ),
	'CHANGED_BY' => array( 'label' => 'By', 'format' => 'table',
		'table' => USER_TABLE, 'key' => 'USER_CODE', 'fields' => 'USERNAME', 'link' => 'exp_edituser.php?CODE=' )
);

$sts_result_loads_lj_layout = array( //! $sts_result_loads_lj_layout
	// To resolve ambiguity
	'LOAD_CODE' => array( 'label' => 'Trip/Load#', 'format' => 'static', 'length' => 120, 'searchable' => true ),
	'LOAD_STATUS' => array( 'label' => 'Load Status', 'format' => 'hidden' ),
	
	//! SCR# 696 Color due/overdue
	'STOP_COLOR' => array( 'label' => 'Color', 'format' => 'hidden' ),

	'SDD' => array( 'label' => 'SDD', 'format' => 'hidden' ),
	'CURRENT_STOP' => array( 'label' => 'Current Stop', 'format' => 'hidden' ),
	'TOTAL_DISTANCE' => array( 'label' => 'Distance', 'format' => 'hidden' ),
	'MOBILE_TIME' => array( 'label' => 'Mobile time', 'format' => 'hidden' ),
	//'MOBILE_LOCATION' => array( 'label' => 'Mobile location', 'format' => 'hidden' ),
	'NUM_STOPS' => array( 'label' => '#Stops', 'format' => 'hidden' ),
	'STOP_CODE' => array( 'label' => 'Stop#', 'format' => 'hidden' ),
	'SUM_PALLETS' => array( 'label' => '#Pallets', 'format' => 'hidden' ),
	'SUM_PALLETS_HEAD' => array( 'label' => '#Pallets', 'format' => 'hidden' ),
	'SUM_PALLETS_BACK' => array( 'label' => '#Pallets', 'format' => 'hidden' ),
	'SUM_WEIGHT_HEAD' => array( 'label' => 'Weight', 'format' => 'hidden' ),
	'SUM_WEIGHT_BACK' => array( 'label' => 'Weight', 'format' => 'hidden' ),
	'CURRENT_STOP_TYPE' => array( 'label' => 'Current Stop Type', 'format' => 'hidden' ),
	'SEQUENCE_NO' => array( 'label' => 'Stop', 'format' => 'num0stop', 'align' => 'right', 'length' => 50 ),
	'STOP_TYPE' => array( 'label' => 'Type', 'format' => 'hidden' ),
	'CURRENT_STOP_STATUS' => array( 'label' => 'CSSTATUS', 'format' => 'hidden' ),
	'STOP_STATUS' => array( 'label' => 'Status', 'format' => 'text' ),
	'SHIPMENT2' => array( 'label' => 'Ship#', 'format' => 'num0nc', 'align' => 'right',
		'link' => 'exp_addshipment.php?CODE=', 'searchable' => true ),
	'SS_NUMBER2' => array( 'label' => 'Office#', 'format' => 'text', 
	'align' => 'right' ),
	'PO_NUMBER' => array( 'label' => 'PO#', 'format' => 'text', 'length' => 90,
		'group' => array('PO_NUMBER', 'PO_NUMBER2', 'PO_NUMBER3', 'PO_NUMBER4', 'PO_NUMBER5'),
		'glue' => ', ', 'searchable' => true ),
	//'PO_NUMBER' => array( 'label' => 'PO#', 'format' => 'case',
	//	'key' => 'STOP_TYPE', 'val1' => 'pick', 'choice1' => 'PO_NUMBER',
	//	'choice2' => '\'\'' ),
	'REF_NUMBER2' => array( 'label' => 'Ref&nbsp;#', 'format' => 'text', 'searchable' => true ),
	'NAME' => array( 'label' => 'Shipper/Consignee', 'format' => 'text', 'length' => 130, 'searchable' => true ),
	//'NAME' => array( 'label' => 'Shipper/Consignee', 'format' => 'case',
	//	'key' => 'STOP_TYPE', 'val1' => 'pick', 'choice1' => 'SHIPPER_NAME',
	//	'choice2' => 'CONS_NAME', 'length' => 130, 'maxlen' => 15 ),
	'DIRECTION2' => array( 'label' => 'Dir', 'format' => 'text' ),
	'APPT' => array( 'label' => 'Appt#', 'format' => 'text' ),
	//'PICKUP_NUMBER' => array( 'label' => 'Appt#', 'format' => 'case',
	//	'key' => 'STOP_TYPE', 'val1' => 'pick', 'choice1' => 'PICKUP_APPT',
	//	'choice2' => '\'\'' ),
	'STOP_ETA' => array( 'label' => 'ETA', 'format' => 'timestamp-s', 'length' => 100 ),	
	'DUE_TS2' => array( 'label' => 'Due', 'format' => 'text', 'length' => 90 ),
	'PICKUP_DATE' => array( 'label' => 'Due', 'format' => 'hidden', 'length' => 90 ),
	'PICKUP_TIME_OPTION' => array( 'label' => 'Type', 'format' => 'hidden' ),
	'PICKUP_TIME1' => array( 'label' => 'Type', 'format' => 'hidden' ),
	'PICKUP_TIME2' => array( 'label' => 'Type', 'format' => 'hidden' ),
	'DELIVER_DATE' => array( 'label' => 'Type', 'format' => 'hidden' ),
	'DELIVER_TIME_OPTION' => array( 'label' => 'Type', 'format' => 'hidden' ),
	'DELIVER_TIME1' => array( 'label' => 'Type', 'format' => 'hidden' ),
	'DELIVER_TIME2' => array( 'label' => 'Type', 'format' => 'hidden' ),
	//'PICKUP_DATE' => array( 'label' => 'Due', 'format' => 'case',
	//	'key' => 'STOP_TYPE', 'val1' => 'pick', 'choice1' => 'PICKUP_DATE',
	//	'choice2' => 'DELIVER_DATE', 'format2' => 'date-s', 'length' => 90 ),
	//'PICKUP_BY_END' => array( 'label' => 'Until', 'format' => 'case',
	//	'key' => 'STOP_TYPE', 'val1' => 'pick', 'choice1' => 'PICKUP_BY_END',
	//	'choice2' => '\'\'', 'format2' => 'timestamp-s', 'length' => 90 ),
	//'ACTUAL_ARRIVE' => array( 'label' => 'Actual', 'format' => 'case',
	//	'key' => 'STOP_TYPE', 'val1' => 'pick', 'choice1' => 'ACTUAL_DEPART',
	//	'choice2' => 'ACTUAL_ARRIVE', 'format2' => 'timestamp-s', 'length' => 90 ),
	'ACTUAL_ARRIVE' => array( 'label' => 'Actual Arr', 'format' => 'timestamp-s-actual', 'length' => 96 ),
	'ACTUAL_DEPART' => array( 'label' => 'Actual Dep', 'format' => 'timestamp', 'length' => 96 ),
	//'CONS_NAME' => array( 'label' => 'Consignee', 'format' => 'text' ),
	'CHECKCALL' => array( 'label' => 'CheckCall', 'format' => 'text' ),
	//'STBOL' => array( 'label' => 'ST/BOL#', 'format' => 'text', 'searchable' => true ),
	'BOL_NUMBER' => array( 'label' => 'BOL#', 'format' => 'case',
		'key' => 'STOP_TYPE', 'val1' => 'pick', 'choice1' => 'BOL_NUMBER',
		'choice2' => '\'\'' ),
	'ST_NUMBER' => array( 'label' => 'Container#', 'format' => 'text', 'align' => 'right' ),
	//'DELIVER_BY' => array( 'label' => 'Time', 'format' => 'time' ),
	'PALLETS' => array( 'label' => 'Pallets', 'format' => 'num0', 'align' => 'right' ),
	'TRACTOR_NUMBER' => array( 'label' => 'Tractor', 'format' => 'text',
	'group' => array('TRACTOR_NUMBER', 'MOBILE_LOCATION'),
		'glue' => '<br>', 'searchable' => true, 'length' => 100 ),
	//'TRACTOR_NUMBER' => array( 'label' => 'Tractor', 'format' => 'case',
	//	'key' => 'SEQUENCE_NO', 'val1' => '1', 'choice1' => 'TRACTOR_NUMBER',
	//	'choice2' => '\'\'' ),
	'TRAILER_NUMBER' => array( 'label' => 'Trailer', 'format' => 'text', 'searchable' => true ),
	//'TRAILER2' => array( 'label' => 'Trailer', 'format' => 'table',
	//	'table' => TRAILER_TABLE, 'key' => 'TRAILER_CODE', 'fields' => 'UNIT_NUMBER',
	//	'length' => 50, 'searchable' => true ),
	//'link' => 'exp_edittrailer.php?CODE=', 
	//'TRAILER_NUMBER' => array( 'label' => 'Trailer', 'format' => 'case',
	//	'key' => 'SEQUENCE_NO', 'val1' => '1', 'choice1' => 'TRAILER_NUMBER',
	//	'choice2' => '\'\'' ),
	'DRIVER' => array( 'label' => 'Driver', 'format' => 'hidden' ),
	'DRIVER_NAME2' => array( 'label' => 'Driver', 'format' => 'text',
		'link' => 'exp_editdriver.php?CODE=', 'key' => 'DRIVER', 'searchable' => true ),
	'DRIVER2' => array( 'label' => 'Driver', 'format' => 'hidden' ),
	'DRIVER2_NAME2' => array( 'label' => 'Driver2', 'format' => 'text',
		'link' => 'exp_editdriver.php?CODE=', 'key' => 'DRIVER2', 'searchable' => true ),
	//'DRIVER_NAME' => array( 'label' => 'Driver', 'format' => 'case',
	//	'key' => 'SEQUENCE_NO', 'val1' => '1', 'choice1' => 'DRIVER_NAME',
	//	'choice2' => '\'\'', 'length' => 130, 'maxlen' => 15 ),
	'CARRIER' => array( 'label' => 'Carrier', 'format' => 'hidden' ),
	'CARRIER_NAME' => array( 'label' => 'Carrier', 'format' => 'text',
		'link' => 'exp_editcarrier.php?CODE=', 'key' => 'CARRIER', 'length' => 100, 'searchable' => true ),
	'LUMPER' => array( 'label' => 'Lumper', 'format' => 'hidden' ),
	'LUMPER_NAME' => array( 'label' => 'Lumper', 'format' => 'table',
		'snippet' => "CASE WHEN SEQUENCE_NO = NUM_STOPS THEN LUMPER_NAME ELSE NULL END",
		'link' => 'exp_editcarrier.php?CODE=', 'key' => 'LUMPER',
		'length' => 100, 'searchable' => true ),
	'LUMPER_AMT' => array( 'label' => 'Lumper Fee', 'format' => 'table', 'align' => 'right',
		'snippet' => "CASE WHEN SEQUENCE_NO = NUM_STOPS THEN CONCAT(ROUND(LUMPER_TOTAL,2), ' ', LUMPER_CURRENCY) ELSE NULL END", 'length' => 80 ),
	//'LUMPER' => array( 'label' => 'Lumper', 'format' => 'table',
	//	'table' => CARRIER_TABLE, 'key' => 'CARRIER_CODE', 'fields' => 'NAME',
	//	'link' => 'exp_editcarrier.php?CODE=', 'searchable' => true ),

	//'CARRIER_NAME' => array( 'label' => 'Carrier', 'format' => 'case',
	//	'key' => 'SEQUENCE_NO', 'val1' => '1', 'choice1' => 'CARRIER_NAME',
	//	'choice2' => '\'\'', 'length' => 130, 'maxlen' => 15 ),
	//'BILLTO_NAME' => array( 'label' => 'Bill to', 'format' => 'text' ),
	'BILLTO_NAME' => array( 'label' => 'Bill to', 'format' => 'case',
		'key' => 'STOP_TYPE', 'val1' => 'pick', 'choice1' => 'BILLTO_NAME',
		'choice2' => '\'\'', 'length' => 130, 'maxlen' => 15, 'searchable' => true ),
	//'NOTES' => array( 'label' => 'Notes', 'format' => 'text' ),
	'NOTES' => array( 'label' => 'Disp&nbsp;Notes', 'format' => 'text', 'length' => 130,
		'maxlen' => 30  ),
	'OFFICE_NAME' => array( 'label' => 'Company', 'format' => 'hidden' ),
);

$sts_result_loads_driver_layout = array( //! $sts_result_loads_driver_layout
	// To resolve ambiguity
	'LOAD_CODE' => array( 'label' => 'Trip/Load#', 'format' => 'static', 'length' => 120, 'searchable' => true ),
	'LOAD_STATUS' => array( 'label' => 'Load Status', 'format' => 'hidden' ),
	'SDD' => array( 'label' => 'SDD', 'format' => 'hidden' ),
	'CURRENT_STOP' => array( 'label' => 'Current Stop', 'format' => 'hidden' ),
	'TOTAL_DISTANCE' => array( 'label' => 'Distance', 'format' => 'hidden' ),
	'MOBILE_TIME' => array( 'label' => 'Mobile time', 'format' => 'hidden' ),
	'MOBILE_LOCATION' => array( 'label' => 'Mobile location', 'format' => 'hidden' ),
	'NUM_STOPS' => array( 'label' => '#Stops', 'format' => 'hidden' ),
	'STOP_CODE' => array( 'label' => 'Stop#', 'format' => 'hidden' ),
	'SUM_PALLETS' => array( 'label' => '#Pallets', 'format' => 'hidden' ),
	'SUM_PALLETS_HEAD' => array( 'label' => '#Pallets', 'format' => 'hidden' ),
	'SUM_PALLETS_BACK' => array( 'label' => '#Pallets', 'format' => 'hidden' ),
	'SUM_WEIGHT_HEAD' => array( 'label' => 'Weight', 'format' => 'hidden' ),
	'SUM_WEIGHT_BACK' => array( 'label' => 'Weight', 'format' => 'hidden' ),
	'CURRENT_STOP_TYPE' => array( 'label' => 'Current Stop Type', 'format' => 'hidden' ),
	'SEQUENCE_NO' => array( 'label' => 'Stop', 'format' => 'num0stop', 'align' => 'right', 'length' => 50 ),
	'STOP_TYPE' => array( 'label' => 'Type', 'format' => 'hidden' ),
	'CURRENT_STOP_STATUS' => array( 'label' => 'CSSTATUS', 'format' => 'hidden' ),
	'STOP_STATUS' => array( 'label' => 'Status', 'format' => 'text' ),
	'SHIPMENT2' => array( 'label' => 'Ship#', 'format' => 'num0nc', 'align' => 'right',
		'searchable' => true ),
	//! SCR# 658 - changed to REF_NUMBER2 for C-TPAT
	'SS_NUMBER2' => array( 'label' => 'Office#', 'format' => 'text', 'align' => 'right' ),
	'PO_NUMBER' => array( 'label' => 'PO#', 'format' => 'text', 'length' => 90,
		'group' => array('PO_NUMBER', 'PO_NUMBER2', 'PO_NUMBER3', 'PO_NUMBER4', 'PO_NUMBER5'),
		'glue' => ', ', 'searchable' => true ),
	//'PO_NUMBER' => array( 'label' => 'PO#', 'format' => 'case',
	//	'key' => 'STOP_TYPE', 'val1' => 'pick', 'choice1' => 'PO_NUMBER',
	//	'choice2' => '\'\'' ),
	'REF_NUMBER2' => array( 'label' => 'Ref&nbsp;#', 'format' => 'text' ),
	'NAME' => array( 'label' => 'Shipper/Consignee', 'format' => 'text', 'length' => 130, 'searchable' => true ),
	'CARRIER_NOTE' => array( 'label' => 'Disp&nbsp;Notes', 'format' => 'text', 'length' => 130,
		'maxlen' => 30  ),
);

$sts_result_loads_summary_layout = array( //! sts_result_loads_summary_layout
	'LOAD_CODE' => array( 'label' => 'Trip/Load#', 'format' => 'static', 'length' => 120, 'searchable' => true, 'sortable' => true ),
	'LOAD_STATUS' => array( 'label' => 'Load Status', 'format' => 'hidden' ),
	'SDD' => array( 'label' => 'SDD', 'format' => 'hidden' ),
	'CURRENT_STOP' => array( 'label' => 'Current Stop', 'format' => 'hidden' ),
	'NUM_STOPS' => array( 'label' => '#Stops', 'format' => 'hidden' ),
	'CURRENT_STOP_TYPE' => array( 'label' => 'Current Stop Type', 'format' => 'hidden' ),
	'CURRENT_STOP_STATUS' => array( 'label' => 'CSSTATUS', 'format' => 'hidden' ),

	'OFFICE_NUM' => array( 'label' => 'Office#', 'format' => 'text', 'searchable' => true, 'sortable' => true ),
	'TRACTOR_NUMBER' => array( 'label' => 'Tractor', 'format' => 'text', 'searchable' => true, 'sortable' => true ),
	'TRAILER_NUMBER' => array( 'label' => 'Trailer', 'format' => 'text', 'searchable' => true, 'sortable' => true ),
	'DRIVER_CARRIER' => array( 'label' => 'Driver/Carrier', 'format' => 'text', 'searchable' => true, 'sortable' => true ),
	'SUM_PALLETS' => array( 'label' => 'Pall', 'format' => 'num0', 'align' => 'right', 'sortable' => true ),
	'SUM_WEIGHT' => array( 'label' => 'Wt', 'format' => 'num0', 'align' => 'right', 'sortable' => true ),
	'PICKUP_DATE' => array( 'label' => 'Ship', 'format' => 'date-s', 'sortable' => true ),
	'DELIVER_DATE' => array( 'label' => 'Del', 'format' => 'date-s', 'sortable' => true ),

	'SCITY' => array( 'label' => 'City', 'format' => 'text'  ),
	'SSTATE' => array( 'label' => 'ST', 'format' => 'text'  ),
	'SSTAT' => array( 'label' => 'SSTAT', 'format' => 'hidden' ),

	'CNAME0' => array( 'label' => '1', 'format' => 'text'  ),
	'CCITY0' => array( 'label' => 'City', 'format' => 'text'  ),
	'CSTATE0' => array( 'label' => 'ST', 'format' => 'text'  ),
	'CSTAT0' => array( 'label' => 'CSTAT0', 'format' => 'hidden' ),
	'CNAME1' => array( 'label' => '2', 'format' => 'text'  ),
	'CCITY1' => array( 'label' => 'City', 'format' => 'text'  ),
	'CSTATE1' => array( 'label' => 'ST', 'format' => 'text'  ),
	'CSTAT1' => array( 'label' => 'CSTAT1', 'format' => 'hidden' ),
	'CNAME2' => array( 'label' => '3', 'format' => 'text'  ),
	'CCITY2' => array( 'label' => 'City', 'format' => 'text'  ),
	'CSTATE2' => array( 'label' => 'ST', 'format' => 'text'  ),
	'CSTAT2' => array( 'label' => 'CSTAT2', 'format' => 'hidden' ),
	'CNAME3' => array( 'label' => '4', 'format' => 'text'  ),
	'CCITY3' => array( 'label' => 'City', 'format' => 'text'  ),
	'CSTATE3' => array( 'label' => 'ST', 'format' => 'text'  ),
	'CSTAT3' => array( 'label' => 'CSTAT3', 'format' => 'hidden' ),

	'BSNAME' => array( 'label' => 'Company', 'format' => 'text'  ),
	'BSCITY' => array( 'label' => 'City', 'format' => 'text'  ),
	'BSSTATE' => array( 'label' => 'ST', 'format' => 'text'  ),
	'BSSTAT' => array( 'label' => 'BSSTAT', 'format' => 'hidden' ),

	'BCCITY0' => array( 'label' => 'City', 'format' => 'text'  ),
	'BCSTATE0' => array( 'label' => 'ST', 'format' => 'text'  ),
	'BCSTAT0' => array( 'label' => 'BCSTAT0', 'format' => 'hidden' ),
	'BCCITY1' => array( 'label' => 'City', 'format' => 'text'  ),
	'BCSTATE1' => array( 'label' => 'ST', 'format' => 'text'  ),
	'BCSTAT1' => array( 'label' => 'BCSTAT1', 'format' => 'hidden' ),
	'OFFICE_NAME' => array( 'label' => 'Office', 'format' => 'hidden' ),
);

//! Edit/Delete Button Specifications - For use with sts_result

$sts_result_loads_edit = array( //! $sts_result_loads_edit
	'title' => '<img src="images/load_icon.png" alt="load_icon" height="24"> ## Trips/Loads',
	'sort' => 'LOAD_CODE DESC, SEQUENCE_NO ASC',	// 'LOAD_STATUS DESC, '.
	'group' => 'LOAD_CODE',
	'cancel' => 'index.php',
	'add' => 'exp_addload.php',
	//'actionextras' => 'disabled',
	'addbutton' => 'Add Load',
	'cancelbutton' => 'Back',
	'rowbuttons' => array(
		array( 'url' => 'exp_addload.php?CODE=', 'key' => 'LOAD_CODE', 'label' => 'LOAD_CODE', 'tip' => ' Select Shipments for load ', 'icon' => 'glyphicon glyphicon-edit' ),
		array( 'url' => 'exp_dispatch2.php?CODE=', 'key' => 'LOAD_CODE', 'label' => 'LOAD_CODE', 'tip' => 'Picks, Drops & Stops for load ', 'icon' => 'glyphicon glyphicon-sort' ),
		array( 'url' => 'exp_dispatch3.php?CODE=', 'key' => 'LOAD_CODE', 'label' => 'LOAD_CODE', 'tip' => 'Assign Resources for load ', 'icon' => 'glyphicon glyphicon-plus' ),
		//array( 'url' => 'exp_dispatch_load.php?CODE=', 'key' => 'LOAD_CODE', 'label' => 'LOAD_CODE', 'tip' => 'Dispatch load ', 'icon' => 'glyphicon glyphicon-plus-sign' ),
		//array( 'url' => 'exp_cancelload.php?CODE=', 'key' => 'LOAD_CODE', 'label' => 'LOAD_CODE', 'tip' => 'Cancel load ', 'icon' => 'glyphicon glyphicon-trash', 'confirm' => 'yes' )
	),
);

$sts_result_loads_driver_edit = array( //! $sts_result_loads_driver_edit
	'title' => '<img src="images/load_icon.png" alt="load_icon" height="24"> My Trips/Loads',
	'sort' => 'LOAD_CODE DESC, SEQUENCE_NO ASC',	// 'LOAD_STATUS DESC, '.
	'group' => 'LOAD_CODE',
	'cancel' => 'exp_logout.php',
	//'add' => 'exp_addload.php',
	//'actionextras' => 'disabled',
	//'addbutton' => 'Add Load',
	'cancelbutton' => 'Log out',
	'rowbuttons' => array(
		//array( 'url' => 'exp_addload.php?CODE=', 'key' => 'LOAD_CODE', 'label' => 'LOAD_CODE', 'tip' => ' Select Shipments for load ', 'icon' => 'glyphicon glyphicon-edit' ),
		//array( 'url' => 'exp_dispatch2.php?CODE=', 'key' => 'LOAD_CODE', 'label' => 'LOAD_CODE', 'tip' => 'Picks, Drops & Stops for load ', 'icon' => 'glyphicon glyphicon-sort' ),
		//array( 'url' => 'exp_dispatch3.php?CODE=', 'key' => 'LOAD_CODE', 'label' => 'LOAD_CODE', 'tip' => 'Assign Resources for load ', 'icon' => 'glyphicon glyphicon-plus' ),
		//array( 'url' => 'exp_dispatch_load.php?CODE=', 'key' => 'LOAD_CODE', 'label' => 'LOAD_CODE', 'tip' => 'Dispatch load ', 'icon' => 'glyphicon glyphicon-plus-sign' ),
		//array( 'url' => 'exp_cancelload.php?CODE=', 'key' => 'LOAD_CODE', 'label' => 'LOAD_CODE', 'tip' => 'Cancel load ', 'icon' => 'glyphicon glyphicon-trash', 'confirm' => 'yes' )
	),
);

$sts_result_summary_edit = array( //! $sts_result_summary_edit
	'title' => '<img src="images/load_icon.png" alt="load_icon" height="24"> ## Summary View',
	'sort' => 'LOAD_CODE DESC',	
	'cancel' => 'index.php',
	'add' => 'exp_addload.php',
	'toprow' => '<tr>
		<th colspan="9" style="border: 0px;">&nbsp;</th>
		<th class="exspeedite-bg text-center" colspan="14">Headhaul</th>
		<th class="exspeedite-bg text-center"colspan="7">Backhaul</th>
	</tr>
	<tr>
		<th colspan="9" style="border: 0px;">&nbsp;</th>
		<th class="exspeedite-bg text-center" colspan="2">Shipping</th>
		<th class="exspeedite-bg text-center" colspan="12">Deliveries</th>
		<th class="exspeedite-bg text-center" colspan="3">Shipping</th>
		<th class="exspeedite-bg text-center" colspan="4">Deliveries</th>
	</tr>
	',
	//'actionextras' => 'disabled',
	'addbutton' => 'Add Load',
	'cancelbutton' => 'Back',
	'rowbuttons' => array(
		array( 'url' => 'exp_addload.php?CODE=', 'key' => 'LOAD_CODE', 'label' => 'LOAD_CODE', 'tip' => ' Select Shipments for load ', 'icon' => 'glyphicon glyphicon-edit' ),
		array( 'url' => 'exp_dispatch2.php?CODE=', 'key' => 'LOAD_CODE', 'label' => 'LOAD_CODE', 'tip' => 'Sort Picks & Drops for load ', 'icon' => 'glyphicon glyphicon-sort' ),
		array( 'url' => 'exp_dispatch3.php?CODE=', 'key' => 'LOAD_CODE', 'label' => 'LOAD_CODE', 'tip' => 'Assign Resources for load ', 'icon' => 'glyphicon glyphicon-plus' ),
	),
);


$sts_result_loads_last5_view = array( //! $sts_result_loads_last5_view
	'title' => '<img src="images/load_icon.png" alt="load_icon" height="24"> Last 5 Updated Loads',
	'sort' => 'changed_date desc
		limit 5',
	'filters' => false,
	//'cancel' => 'index.php',
	//'add' => 'exp_addshipment.php',
	//'actionextras' => 'disabled',
	//'addbutton' => 'Add Shipment',
	//'cancelbutton' => 'Back',
);

$sts_result_loads_carrier_view = array( //! $sts_result_loads_carrier_view
	'title' => '<img src="images/load_icon.png" alt="load_icon" height="24"> Loads',
	'filters' => false,
	//'cancel' => 'index.php',
	//'add' => 'exp_addshipment.php',
	//'actionextras' => 'disabled',
	//'addbutton' => 'Add Shipment',
	//'cancelbutton' => 'Back',
);


?>
