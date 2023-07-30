<?php

// $Id: sts_stop_class.php 5126 2023-06-08 03:23:24Z dev $
// Stop class - all things to do with stops

// no direct access
defined('_STS_INCLUDE') or die('Restricted access');

require_once( "sts_table_class.php" );
//require_once( "sts_zip_class.php" );
require_once( __DIR__."/../PCMILER/exp_get_miles.php" );
require_once( "sts_status_class.php" );
require_once( "sts_setting_class.php" );
require_once( "sts_yard_container_class.php" );

class sts_stop extends sts_table {

	private $states;
	private $status_table;
	private $setting_table;
	public $state_name;
	public $state_behavior;
	public $name_state;
	public $behavior_state;
	private $send_notifications;

	// Constructor does not need the table name
	public function __construct( $database, $debug = false ) {
		global $sts_log_to_file;

		$this->debug = $debug;
		$this->primary_key = "STOP_CODE";
		if( $this->debug ) echo "<p>Create sts_stop</p>";
		$this->status_table = sts_status::getInstance($database, $debug);
		$this->setting_table = sts_setting::getInstance($database, $debug);
		if( $sts_log_to_file )
			$this->log_file = $this->setting_table->get( 'option', 'DEBUG_LOG_FILE' );

		//! SCR# 906 - send notifications
		$this->send_notifications = $this->setting_table->get( 'option', 'SEND_NOTIFICATIONS' ) == 'true';

		parent::__construct( $database, STOP_TABLE, $debug);
		$this->load_states();
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

	private function log_event( $event ) {		
		if( isset($this->log_file) && $this->log_file <> '' &&
			is_writable($this->log_file) && ! is_dir($this->log_file) ) 
		
		file_put_contents($this->log_file, date('m/d/Y h:i:s A')." ".$event.PHP_EOL, FILE_APPEND);
	}

	private function generateCallTrace()
	{
	    $e = new Exception();
	    $trace = explode("\n", $e->getTraceAsString());
	    // reverse array to make steps line up chronologically
	    $trace = array_reverse($trace);
	    array_shift($trace); // remove {main}
	    array_pop($trace); // remove call to this method
	    $length = count($trace);
	    $result = array();
	   
	    for ($i = 0; $i < $length; $i++)
	    {
	        $result[] = ($i + 1)  . ')' . substr($trace[$i], strpos($trace[$i], ' ')); // replace '#someNum' with '$i)', set the right ordering
	    }
	   
	    return "\t" . implode("\n\t", $result);
	}

	private function load_states() {
		
		$cached = $this->cache->get_state_table( 'stop' );
		
		if( is_array($cached) && count($cached) > 0 ) {
			$this->states = $cached;
		} else {
			$this->states = $this->database->get_multiple_rows("select STATUS_CODES_CODE, STATUS_STATE, BEHAVIOR, PREVIOUS
				FROM EXP_STATUS_CODES
				WHERE SOURCE_TYPE = 'stop'
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
		
		if( $this->debug ) {
			echo "<p>sts_load > load_states: states = </p>
			<pre>";
			var_dump($this->states);
			echo "</pre>";
		}
	}
	
	public function completed( $load_code, $seq_number ) {
		// Pick or drop ?
		$check = $this->fetch_rows( "LOAD_CODE = ".$load_code." AND SEQUENCE_NO = ".$seq_number,
			"STOP_CODE, STOP_TYPE, SHIPMENT, COALESCE(YARD_CODE, (SELECT CONS_CLIENT_CODE FROM EXP_SHIPMENT
			WHERE SHIPMENT = SHIPMENT_CODE)) AS YARD_CODE, IM_STOP_TYPE,
			(SELECT ST_NUMBER FROM EXP_SHIPMENT WHERE SHIPMENT = SHIPMENT_CODE) as ST_NUMBER
			" );
		
		$check2 = $this->fetch_rows( "LOAD_CODE = ".$load_code, "COUNT(*) AS NUM" );
			
		if( $this->debug ) echo"<p>".__METHOD__.": entry load = $load_code, seq = $seq_number type = ".$check[0]['STOP_TYPE']."</p>";
		
		$status = 'Complete';
		if( isset($check[0]) ) {
			if( isset($check[0]['STOP_TYPE']) && in_array($check[0]['STOP_TYPE'], array('pick','pickdock') ) ) {
				$status = 'Picked';
			} else if( isset($check[0]['STOP_TYPE']) && in_array($check[0]['STOP_TYPE'], array('drop','dropdock') ) ) {
				$status = 'Dropped';
			} else if( isset($check[0]['STOP_TYPE']) && $check[0]['STOP_TYPE'] == 'stop' ) {
				$status = 'Complete';
			}

			if( $this->send_notifications && isset($check[0]['SHIPMENT']) ) {	//! SCR# 906 - send notifications
				if( $this->debug ) echo"<p>".__METHOD__.": send notifications shipment = ".$check[0]['SHIPMENT']." type = ".$check[0]['STOP_TYPE']."</p>";

				if( isset($check[0]['STOP_TYPE']) && $check[0]['STOP_TYPE'] == 'pick' ) {
					$email_type = 'picked';
					$email_code = $check[0]['SHIPMENT'];
					require( "exp_spawn_send_email.php" ); // Announce pick
					sleep(2);
				}

				if( isset($check[0]['STOP_TYPE']) && $check[0]['STOP_TYPE'] == 'drop' ) {
					$email_type = 'dropped';
					$email_code = $check[0]['SHIPMENT'];
					require( "exp_spawn_send_email.php" ); // Announce drop
					sleep(2);
				}
			}
			
			//! SCR# 853 - Yard status
			if( ! empty($check[0]['ST_NUMBER']) ) {	// we have a container
				$yc_table = sts_yard_container::getInstance( $this->database, $this->debug );
				
				if( isset($check[0]['IM_STOP_TYPE']) &&
					$check[0]['IM_STOP_TYPE'] == 'pickyard' ||
					(in_array($check[0]['STOP_TYPE'], ['pick','pickdock'] ) ) ) {
					$yc_table->remove_from_yard( $check[0]['STOP_CODE'] );
				} else if( isset($check[0]['IM_STOP_TYPE']) &&
					$check[0]['IM_STOP_TYPE'] == 'dropyard' ||
					(in_array($check[0]['STOP_TYPE'], ['drop','dropdock'] ) &&
					// Is this the last stop? Then container stays here
					is_array($check2) && count($check2) == 1 &&
					isset($check2[0]["NUM"]) && $check2[0]["NUM"] == $seq_number) ){
					$yc_table->add_into_yard( $check[0]['STOP_CODE'], $check[0]['YARD_CODE'] );
				}
			}

		}

		$result = $this->update( $check[0]['STOP_CODE']."
					AND CURRENT_STATUS <> ".$this->name_state[$status],
					array( 'CURRENT_STATUS' => $this->name_state[$status] ) );
		
		return $result;
	}
	
	//! Complete all stops for a given load
	public function all_completed( $load_code ) {
		if( $this->debug ) echo"<p>".__METHOD__.": entry load = $load_code</p>";
		$result = true;
		$check = $this->fetch_rows('LOAD_CODE = '.$load_code.
			' AND SEQUENCE_NO >= (SELECT CURRENT_STOP
			FROM EXP_LOAD WHERE LOAD_CODE = '.$load_code.')',
			'SEQUENCE_NO', 'SEQUENCE_NO ASC' );
		if( is_array($check) && count($check) > 0 ) {
			foreach($check as $row) {
				$result = $this->completed( $load_code, $row['SEQUENCE_NO'] ) && $result;
			}
		}

		return $result;
	}
	
	public function add_load_status( $pk, $comment ) {
			
			$dummy = $this->status_table->add_load_status( $pk, $comment );
	}

	// Currently not supporting pickdock, dropdock
	public function multi_stop_update_actuals( $load_code ) {
		$stops = $this->database->get_multiple_rows(
			"SELECT STOP_CODE, STOP_TYPE, SEQUENCE_NO, 
			(CASE
				WHEN STOP_TYPE = 'pick' THEN SHIPPER_ADDR1
				WHEN STOP_TYPE = 'drop' THEN CONS_ADDR1
				ELSE NULL END ) AS ADDR1,
			(CASE
				WHEN STOP_TYPE = 'pick' THEN SHIPPER_ADDR2
				WHEN STOP_TYPE = 'drop' THEN CONS_ADDR2
				ELSE NULL END ) AS ADDR2,
			(CASE
				WHEN STOP_TYPE = 'pick' THEN SHIPPER_CITY
				WHEN STOP_TYPE = 'drop' THEN CONS_CITY
				ELSE NULL END ) AS CITY,
			(CASE
				WHEN STOP_TYPE = 'pick' THEN SHIPPER_STATE
				WHEN STOP_TYPE = 'drop' THEN CONS_STATE
				ELSE NULL END ) AS STATE,
			(CASE
				WHEN STOP_TYPE = 'pick' THEN SHIPPER_ZIP
				WHEN STOP_TYPE = 'drop' THEN CONS_ZIP
				ELSE NULL END ) AS ZIP,
			ACTUAL_ARRIVE, ACTUAL_DEPART
			
			FROM EXP_STOP
			LEFT JOIN EXP_SHIPMENT
			ON EXP_SHIPMENT.SHIPMENT_CODE = EXP_STOP.SHIPMENT
			
			WHERE EXP_STOP.LOAD_CODE = ".$load_code."
			AND STOP_TYPE IN ('pick','drop')
			AND SEQUENCE_NO <= (SELECT 
			(CASE WHEN CURRENT_STOP = (SELECT COUNT(*) AS NUM_STOPS
				FROM EXP_STOP
				WHERE EXP_LOAD.LOAD_CODE = EXP_STOP.LOAD_CODE) THEN CURRENT_STOP
			ELSE CURRENT_STOP - 1 END) AS CURRENT_STOP
			FROM EXP_LOAD 
			WHERE EXP_LOAD.LOAD_CODE = EXP_STOP.LOAD_CODE)
			order by SEQUENCE_NO DESC
			" );
		if( $this->debug ) {
			echo "<p>stops = </p>
			<pre>";
			var_dump($stops);
			echo "</pre>";
		}


		if( isset($stops) && is_array($stops) && count($stops) > 1 ) {
			$stop_type	= $stops[0]['STOP_TYPE'];
			$seq		= $stops[0]['SEQUENCE_NO'];
			$addr1		= $stops[0]['ADDR1'];
			$addr2		= $stops[0]['ADDR2'];
			$city		= $stops[0]['CITY'];
			$state		= $stops[0]['STATE'];
			$zip		= $stops[0]['ZIP'];
			$depart		= $stops[0]['ACTUAL_DEPART'];
			$matched = false;

			for( $c=1; ($c < count($stops) &&
				$addr1 == $stops[$c]['ADDR1'] && $addr2 == $stops[$c]['ADDR2'] &&
				$city == $stops[$c]['CITY'] && $state == $stops[$c]['STATE'] &&
				$zip == $stops[$c]['ZIP']); $c++ ) {
				if( ! isset($stops[$c]['ACTUAL_DEPART']) || $stops[$c]['ACTUAL_DEPART'] == '' ) {
				if( $this->debug ) echo "<p>sts_stop > multi_stop_update_actuals: depart $c => $depart</p>";
					$dummy = $this->update( $stops[$c]['STOP_CODE'], array("ACTUAL_DEPART" => $depart ) );
					$matched = true;
				}
			}
			if( $matched ) {
				$arrive = $stops[--$c]['ACTUAL_ARRIVE'];
				if( $this->debug ) echo "<p>sts_stop > multi_stop_update_actuals: c = $c , arrive = $arrive</p>";
				for( $c--; $c >= 0; $c-- ) {
					if( ! isset($stops[$c]['ACTUAL_ARRIVE']) || $stops[$c]['ACTUAL_ARRIVE'] == '' ) {
						if( $this->debug ) echo "<p>sts_stop > multi_stop_update_actuals: arrive $c => $arrive</p>";
						$dummy = $this->update( $stops[$c]['STOP_CODE'], array("ACTUAL_ARRIVE" => $arrive ) );
					}
				}
			} 
		}
	}
	
	public function check_sequence( $load_code ) {
		$result = false;
		$stop_num = $this->database->get_multiple_rows(
			"SELECT LOAD_CODE
			FROM (
			SELECT LOAD_CODE, MIN(SEQUENCE_NO) MYMIN, MAX(SEQUENCE_NO) MYMAX,
			(SELECT COUNT(*) AS NUM_STOPS
							FROM EXP_STOP
							WHERE MYSTOPS.LOAD_CODE = EXP_STOP.LOAD_CODE) AS NUM_STOPS
			FROM (SELECT DISTINCT SEQUENCE_NO, LOAD_CODE
			FROM EXP_STOP
			WHERE LOAD_CODE = ".$load_code."
			ORDER BY LOAD_CODE ASC, SEQUENCE_NO ASC) MYSTOPS
			GROUP BY LOAD_CODE) NUMBERS
			WHERE MYMIN > 1 OR MYMAX < NUM_STOPS" );
		if( isset($stop_num) && is_array($stop_num) && count($stop_num) > 0 && 
			$stop_num[0]['LOAD_CODE'] == $load_code ) {
			$result = true;
		}
		return $result;
	}
	
	public function renumber( $load_code ) {
	
		if( $load_code > 0 ) {
			$stops = $this->fetch_rows( "LOAD_CODE = ".$load_code,
					"STOP_CODE, SEQUENCE_NO", "SEQUENCE_NO ASC, STOP_CODE ASC" );
			$this->log_event( 'sts_stop > renumber: load = '.$load_code.' renumber '.
				( is_array($stops) ? count($stops).' stops' : 'no stops' ) );
			$dummy = $this->add_load_status( $load_code, 'renumber '.
				( is_array($stops) ? count($stops).' stops' : 'no stops' ) );
			if( is_array($stops) && count($stops) > 0 ) {
				$count = 1;
				foreach( $stops as $stop ) {
					$dummy = $this->update( $stop['STOP_CODE'],
							array("SEQUENCE_NO" => $count ) );
					$count++;
				}
			}
		} else {
			$this->log_event( 'sts_stop > renumber: load = 0' );
			$this->log_event( $this->generateCallTrace() );
		}
	}

	public function got_return_stop( $load_code ) {
		$result = $this->fetch_rows( "LOAD_CODE = ".$load_code,
				"STOP_CODE, SEQUENCE_NO, STOP_TYPE", "SEQUENCE_NO DESC", "1" );
				
		return is_array($result) && count($result) == 1 &&
			$result[0]['STOP_TYPE'] == 'stop';
	}

	public function get_first_pick( $load_code ) {
		
		if( $this->debug ) echo "<p>sts_stop > get_first_pick: $load_code</p>";
		$result = false;
		$stops = $this->fetch_rows( "LOAD_CODE = ".$load_code,
				"STOP_CODE, SHIPMENT, SEQUENCE_NO, STOP_TYPE", "SEQUENCE_NO ASC" );
				
		if( is_array($stops) && count($stops) > 1 ) {	// Need 2 or more stops
			$first_pick = in_array($stops[0]['STOP_TYPE'], array('pick','pickdock')) ? 0 : 1;	// index of first pick/pickdock
			$shipment = $stops[$first_pick]['SHIPMENT'];
			$result = $this->database->get_one_row(
				"SELECT SHIPPER_NAME,SHIPPER_ADDR1,SHIPPER_ADDR2,SHIPPER_CITY,
				SHIPPER_STATE,SHIPPER_ZIP,SHIPPER_COUNTRY,SHIPPER_PHONE,
				SHIPPER_EXT,SHIPPER_FAX, SHIPPER_CELL,SHIPPER_CONTACT
				SHIPPER_EMAIL, SHIPPER_LAT, SHIPPER_LON
				FROM EXP_SHIPMENT
				WHERE SHIPMENT_CODE = $shipment");
			if( isset($result) && is_array($result) ) {
				$newresult = array();
				foreach( $result as $key => $value) {
					$newresult[str_replace('SHIPPER', 'STOP', $key)] = $value;
				}
				unset($result);
				$result = $newresult;
			}
		}
		if( $this->debug ) echo "<p>sts_stop > get_first_pick: return ".($result ? "info for ".$result['STOP_NAME'] : "false")."</p>";
		return $result;
	}
	
	//! Given a stop code, return the due timestamp
	public function get_due( $stop ) {
		if( $this->debug ) echo "<p>sts_stop > get_due: $stop</p>";
		$result = false;
		$check = $this->database->get_one_row( 
			"SELECT (CASE WHEN STOP_TYPE = 'stop' THEN STOP_DUE

			WHEN STOP_TYPE IN ('pick','pickdock') THEN
				(SELECT COALESCE( EDI_204_NOT_LATER, TIMESTAMP(PICKUP_DATE,
					(case when coalesce(PICKUP_TIME1,'') = '' then '00:00'
					else
					concat_ws(':',substr(PICKUP_TIME1,1,2),substr(PICKUP_TIME1,3,2)) end)))
				FROM EXP_SHIPMENT
				WHERE EXP_SHIPMENT.SHIPMENT_CODE = EXP_STOP.SHIPMENT)

			WHEN STOP_TYPE IN ('drop','dropdock') THEN
				(SELECT COALESCE( EDI_204_NOT_LATER, TIMESTAMP(DELIVER_DATE,
					(case when coalesce(DELIVER_TIME1,'') = '' then '00:00'
					else
					concat_ws(':',substr(DELIVER_TIME1,1,2),substr(DELIVER_TIME1,3,2)) end)))
				FROM EXP_SHIPMENT
				WHERE EXP_SHIPMENT.SHIPMENT_CODE = EXP_STOP.SHIPMENT)
			
			ELSE NULL END) AS DUE_TS
			
			FROM EXP_STOP
			WHERE STOP_CODE = ".$stop );
		if( is_array($check) && ! empty($check["DUE_TS"]))
			$result = date("Y-m-d\TH:i:s", strtotime($check["DUE_TS"]));
		return $result;
	}
	
	//! Add an extra stop to a load
	// Recently modified to allow multiple extra stops
	public function add_return_stop( $load_code, $stop ) {
		
		if( $this->debug ) echo "<p>sts_stop > add_return_stop: $load_code</p>";
		$result = false;
		$stops = $this->fetch_rows( "LOAD_CODE = ".$load_code,
				"STOP_CODE, SHIPMENT, SEQUENCE_NO, STOP_TYPE", "SEQUENCE_NO ASC" );
				
		if( is_array($stop) && count($stops) > 0 ) {
			$last_stop = count($stops)-1;	// index of last stop

			$sequence_number = $stops[$last_stop]['SEQUENCE_NO'] + 1;
		} else {
			$sequence_number = 1;
		}
			
		$fields = $stop;
		$fields["LOAD_CODE"] = $load_code;
		$fields["SEQUENCE_NO"] = $sequence_number;
		$fields["STOP_TYPE"] = 'stop';
		$fields["CURRENT_STATUS"] = $this->behavior_state['entry'];
		
		//! Strict MySQL - make sure STOP_COUNTRY has a valid value
		if( ! isset($fields["STOP_COUNTRY"]) ||
			! in_array($fields["STOP_COUNTRY"], array('USA', 'Canada')) )
			$fields["STOP_COUNTRY"] = 'USA';

		$result = $this->add( $fields );
			
		$dummy = $this->add_load_status( $load_code, 'add_return_stop: return '.($result ? "true" : "false") );
		if( $this->debug ) echo "<p>sts_stop > add_return_stop: return ".($result ? "true" : "false")."</p>";
		return $result;
	}

	public function delete_return_stop( $load_code ) {
		if( $this->debug ) echo "<p>sts_stop > delete_return_stop: $load_code</p>";
		$result = false;
		$check = $this->fetch_rows( "LOAD_CODE = ".$load_code,
				"STOP_CODE, SEQUENCE_NO, STOP_TYPE, CURRENT_STATUS", "SEQUENCE_NO DESC", "1" );
		if( is_array($check) && count($check) == 1 &&
			$check[0]['STOP_TYPE'] == 'stop' && 
			$this->state_behavior[$check[0]['CURRENT_STATUS']] == 'entry') {
			$result = $this->delete_row( "STOP_CODE = ".$check[0]['STOP_CODE'] );
		}
		
		$dummy = $this->add_load_status( $load_code, 'delete_return_stop: return '.($result ? "true" : "false") );
		if( $this->debug ) echo "<p>sts_stop > delete_return_stop: return ".($result ? "true" : "false")."</p>";
		return $result;
	}
	
	public function propagate_trailer( $stop_code ) {
		if( $this->debug ) echo "<p>sts_stop > propagate_trailer: stop_code ".$stop_code."</p>";
		$check = $this->fetch_rows( "STOP_CODE = ".$stop_code,
				"STOP_CODE, SEQUENCE_NO, LOAD_CODE, TRAILER,
				(SELECT COUNT(*) AS NUM_STOPS
				FROM EXP_STOP X
				WHERE X.LOAD_CODE = EXP_STOP.LOAD_CODE) AS NUM_STOPS" );
		if( is_array($check) && count($check) == 1 && 
			isset($check[0]['TRAILER']) && $check[0]['TRAILER'] <> '') {
			if( $check[0]['SEQUENCE_NO'] < $check[0]['NUM_STOPS'] ) {
				$dummy = $this->update_row( "LOAD_CODE = ".$check[0]['LOAD_CODE'].
					" AND SEQUENCE_NO > ".$check[0]['SEQUENCE_NO'],
						array( array("field" => "TRAILER", "value" => $check[0]['TRAILER'] ) ) );
			}
		}
		
	}
	
	public function list_stops( $load_code ) {
		$line = '';
		$stops = $this->fetch_rows( "LOAD_CODE = ".$load_code, "*", "SEQUENCE_NO ASC" );

		if( $this->debug ) {
			echo "<p>stops = </p>
			<pre>";
			var_dump($stops);
			echo "</pre>";
		}
		
		if( is_array($stops) && count($stops) > 0 ) {
			$stops_data = array();
			foreach( $stops as $row ) {
				$stops_data[] = $row["SEQUENCE_NO"]."-".$row["STOP_TYPE"]."-".
					$row["SHIPMENT"]."-".$this->state_behavior[$row["CURRENT_STATUS"]];
			}
			$line = "[".implode(", ", $stops_data)."]";
		}
		
		return $line;
	}

	public function first_stop_address( $load ) {
		if( $this->debug ) echo "<p>".__METHOD__.": load = $load</p>";
		
		$result = $this->database->get_one_row("SELECT
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
			FROM 
			(SELECT STOP_CODE, S.LOAD_CODE AS STOP_LOAD_CODE, STOP_TYPE, SHIPMENT,
				STOP_ADDR1, STOP_ADDR2, STOP_CITY, STOP_STATE,
				STOP_ZIP, STOP_COUNTRY
			FROM EXP_STOP S
			WHERE S.LOAD_CODE = $load
            AND SEQUENCE_NO = 1) S
            LEFT JOIN
			EXP_SHIPMENT T
			ON T.SHIPMENT_CODE = S.SHIPMENT");

		return $result;		
	}
}

class sts_stop_left_join extends sts_stop {

	// Constructor does not need the table name
	public function __construct( $database, $debug = false ) {

		if( $debug ) echo "<p>Create sts_stop_left_join</p>";
		parent::__construct( $database, $debug);
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
		if( $this->debug ) echo "<p>".__METHOD__.": $match</p>";
		
		$result = $this->database->get_multiple_rows("SELECT $fields FROM
			(SELECT *,
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
				) AS COUNTRY,
				(CASE STOP_TYPE
					WHEN 'pick' THEN SHIPPER_CONTACT
					WHEN 'drop' THEN CONS_CONTACT
					ELSE STOP_CONTACT END
				) AS CONTACT,
				(CASE STOP_TYPE
					WHEN 'pick' THEN PICKUP_APPT
					WHEN 'drop' THEN DELIVERY_APPT
					ELSE '' END
				) AS APPT,
				(CASE STOP_TYPE 
					WHEN 'pick' THEN
						FMT_DUE(PICKUP_DATE, PICKUP_TIME_OPTION, PICKUP_TIME1, PICKUP_TIME2, '<br>', SHIPPER_ZIP)
					WHEN 'drop' THEN
						FMT_DUE(DELIVER_DATE, DELIVER_TIME_OPTION, DELIVER_TIME1, DELIVER_TIME2, '<br>', CONS_ZIP)
					ELSE DATE_FORMAT(STOP_DUE, '%m/%d/%Y<br>At %H:%i') END ) AS DUE_TS2,
				COALESCE(TRAILER, (SELECT TRAILER FROM EXP_LOAD 
					WHERE EXP_LOAD.LOAD_CODE = STOP_LOAD_CODE) ) AS TRAILER2,
				(SELECT UNIT_NUMBER AS TRAILER_NUMBER
					FROM ".TRAILER_TABLE."
					WHERE TRAILER_CODE = COALESCE(TRAILER, (SELECT TRAILER FROM EXP_LOAD 
					WHERE EXP_LOAD.LOAD_CODE = STOP_LOAD_CODE) )
					LIMIT 1) AS TRAILER_NUMBER,
				(CASE STOP_TYPE
					WHEN 'stop' THEN NULL
					ELSE (SELECT STATUS_STATE from EXP_STATUS_CODES X 
						WHERE X.STATUS_CODES_CODE = EXP_SHIPMENT.CURRENT_STATUS  LIMIT 0 , 1) 
				END) AS SHIPMENT_STATUS

			FROM
			
			(SELECT STOP_CODE, LOAD_CODE AS STOP_LOAD_CODE, SEQUENCE_NO, STOP_TYPE, IM_STOP_TYPE, SHIPMENT,
				CURRENT_STATUS AS STOP_CURRENT_STATUS, STOP_ETA, STOP_COMMENT,
				STOP_DISTANCE, ACTUAL_ARRIVE, ACTUAL_DEPART,
				STOP_NAME, STOP_ADDR1, STOP_ADDR2, STOP_CITY, STOP_STATE,
				STOP_ZIP, STOP_COUNTRY, STOP_PHONE, STOP_EXT, STOP_FAX, STOP_CELL,
				STOP_CONTACT, STOP_EMAIL, TRAILER, EDI_ARRIVE_STATUS, EDI_DEPART_STATUS,
				STOP_LAT, STOP_LON, STOP_DUE,
			(SELECT STATUS_STATE from EXP_STATUS_CODES X 
				WHERE X.STATUS_CODES_CODE = EXP_STOP.CURRENT_STATUS  LIMIT 0 , 1) AS STOP_STATUS,
			LAST_DOCKED_AT(SHIPMENT) AS LAST_DOCKED_AT
			FROM EXP_STOP 
			WHERE $match) TMPLJ
			LEFT JOIN
			EXP_SHIPMENT
			ON SHIPMENT_CODE = SHIPMENT ) EXP_STOP
			 ".($order <> "" ? "ORDER BY $order" : "") );

		if( $this->debug ) {
			echo "<p>result for $this->table_name = </p>
			<pre>";
			var_dump($result);
			echo "</pre>";
		}
		return $result;
	}


}

class sts_stop_carrier extends sts_stop {

	// Constructor does not need the table name
	public function __construct( $database, $debug = false ) {

		if( $debug ) echo "<p>Create sts_stop_carrier</p>";
		parent::__construct( $database, $debug);
	}

	// Fetch one or more rows
	public function fetch_rows( $match = "", $fields = "*", $order = "", $limit = "", $groupby = "", $match2 = "" ) {
		if( $this->debug ) echo "<p>".__METHOD__.": $match</p>";
		
		//! SCR# 303 - added NOTES column
		$result = $this->database->get_multiple_rows("
			SELECT STOP_CODE, SEQUENCE_NO, STOP_TYPE, STOP_DISTANCE, SHIPMENT, STOP_COMMENT,

				(CASE STOP_TYPE 
					WHEN 'pick' THEN
						FMT_DUE(PICKUP_DATE, PICKUP_TIME_OPTION, PICKUP_TIME1, PICKUP_TIME2, '<br>', SHIPPER_ZIP)
					WHEN 'drop' THEN
						FMT_DUE(DELIVER_DATE, DELIVER_TIME_OPTION, DELIVER_TIME1, DELIVER_TIME2, '<br>', CONS_ZIP)
					ELSE DATE_FORMAT(STOP_DUE, '%m/%d/%Y<br>At %H:%i') END ) AS DUE,

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
						WHEN 'pick' THEN SHIPPER_CONTACT
						WHEN 'drop' THEN CONS_CONTACT
						ELSE STOP_CONTACT END
					) AS CONTACT,
					(CASE STOP_TYPE
						WHEN 'pick' THEN CONCAT_WS(' x',SHIPPER_PHONE, SHIPPER_EXT)
						WHEN 'drop' THEN CONCAT_WS(' x',CONS_PHONE, CONS_EXT)
						ELSE NULL END
					) AS PHONE,
					(CASE STOP_TYPE
						WHEN 'pick' THEN SHIPPER_EMAIL
						WHEN 'drop' THEN CONS_EMAIL
						ELSE STOP_CONTACT END
					) AS EMAIL,
					(CASE STOP_TYPE
						WHEN 'pick' THEN PICKUP_APPT
						WHEN 'drop' THEN DELIVERY_APPT
						ELSE '' END
					) AS APPT,
					(CASE STOP_TYPE
						WHEN 'pick' THEN PICKUP_NUMBER
						ELSE '' END
					) AS PICKUP_NUMBER,
				PIECES, PALLETS, WEIGHT,
				CONCAT_WS(', ', PO_NUMBER, PO_NUMBER2, PO_NUMBER3, PO_NUMBER4, PO_NUMBER5) AS POS,
				COALESCE(REF_NUMBER,'') REF_NUMBER, SS_NUMBER,
				BOL_NUMBER, CUSTOMER_NUMBER,
                (SELECT GROUP_CONCAT(C.COMMODITY_DESCRIPTION ORDER BY D.DETAIL_CODE ASC SEPARATOR '<br>') COMM
				FROM EXP_DETAIL D, EXP_COMMODITY C
				WHERE D.SHIPMENT_CODE = SHIPMENT
				AND D.COMMODITY = C.COMMODITY_CODE) AS COMM,
                
				(SELECT GROUP_CONCAT(D.NOTES ORDER BY D.DETAIL_CODE ASC SEPARATOR '<br>') NOTES
				FROM EXP_DETAIL D
				WHERE D.SHIPMENT_CODE = SHIPMENT) AS NOTES,
				
				(SELECT GROUP_CONCAT(D.PIECES ORDER BY D.DETAIL_CODE ASC SEPARATOR '<br>') PIECES2
				FROM EXP_DETAIL D
				WHERE D.SHIPMENT_CODE = SHIPMENT) AS PIECES2,
				
				(SELECT GROUP_CONCAT(D.PALLETS ORDER BY D.DETAIL_CODE ASC SEPARATOR '<br>') PALLETS2
				FROM EXP_DETAIL D
				WHERE D.SHIPMENT_CODE = SHIPMENT) AS PALLETS2,

				(SELECT GROUP_CONCAT(D.WEIGHT ORDER BY D.DETAIL_CODE ASC SEPARATOR '<br>') WEIGHT2
				FROM EXP_DETAIL D
				WHERE D.SHIPMENT_CODE = SHIPMENT) AS WEIGHT2,
				
				(CASE WHEN DANGEROUS_GOODS THEN 'HAZMAT' ELSE '' END) AS HAZMAT, UN_NUMBERS,

				(SELECT GROUP_CONCAT(CONCAT(D.TEMPERATURE,'&deg;',(
					SELECT SUBSTR(UNIT_NAME,1,1) FROM EXP_UNIT WHERE UNIT_CODE = D.TEMPERATURE_UNITS
				)) ORDER BY D.DETAIL_CODE ASC SEPARATOR '<br>') TEMP2
				FROM EXP_DETAIL D
				WHERE D.SHIPMENT_CODE = SHIPMENT) AS TEMP2
			FROM
				(SELECT SEQUENCE_NO, STOP_CODE, STOP_TYPE, STOP_DISTANCE, SHIPMENT,
				STOP_NAME, STOP_ADDR1, STOP_ADDR2, STOP_CITY, STOP_STATE, STOP_ZIP,
				STOP_CONTACT, STOP_DUE, STOP_COMMENT
				FROM EXP_STOP 
				WHERE $match) TMP
				LEFT JOIN
				EXP_SHIPMENT
				ON SHIPMENT_CODE = SHIPMENT
				ORDER BY SEQUENCE_NO ASC
			" );

		if( $this->debug ) {
			echo "<p>result for $this->table_name = </p>
			<pre>";
			var_dump($result);
			echo "</pre>";
		}
		return $result;
	}

	public function extra_stops_template() {
		return array(	//! $sts_email_extra_stops
	'header' => '
			<table class="border">
				<tr>
					<td>
						<h3  class="invoice">EXTRA STOPS</h3>
	<table class="noborder">
	<thead>
	<tr>
		<th class="w10 text-center">
			#
		</th>
		<th class="w10">
			Type
		</th>
		<th class="w25">
			Shipper/Consignee
		</th>
		<th class="w15">
			Address
		</th>
		<th class="w15">
			City
		</th>
		<th class="w10">
			State
		</th>
		<th  class="w10">
			Zip
		</th>
	</tr>
	</thead>
	<tbody>',
	'layout' => '
	<tr>
		<td class="w10 text-center">
			%SEQUENCE_NO%
		</td>
		<td class="w10">
			%STOP_TYPE%
		</td>
		<td class="w25">
			<strong>%NAME%</strong><br>
		</td>
		<td class="w15">
			%ADDRESS%
			#ADDRESS2#<br>%ADDRESS2%#
		</td>
		<td class="w15">
			%CITY%
		</td>
		<td class="w10">
			%STATE%
		</td>
		<td class="w10">
			%ZIP_CODE%
		</td>
	</tr>
	',
	'footer' => '</tbody>
					</table>
					</td>
				</tr>
			</table>
			<br>
		' );
	}
	
	public function carrier_stops_template() {
		return array(	//! $sts_email_carrier_stops
	'header' => '
	<table class="noborder">
	<tr>
		<th>
			#
			<hr>
		</th>
		<th>
			Type
			<hr>
		</th>
		<th>
			Shipper/Consignee
			<hr>
		</th>
		<th>
			Shipment#
			<hr>
		</th>
		<th>
			When
			<hr>
		</th>
		<th>
			Cmdty
			<hr>
		</th>
		<th class="text-right">
			Pcs
			<hr>
		</th>
		<th class="text-right">
			Pallets
			<hr>
		</th>
		<th class="text-right">
			Weight
			<hr>
		</th>
		<th class="text-right">
			Temp
			<hr>
		</th>
	</tr>',
	'layout' => '
	<tr>
		<td class="text-center">
			%SEQUENCE_NO%
		</td>
		<td>
			%STOP_TYPE%
		</td>
		<td>
			<strong>%NAME%</strong><br>
			%ADDRESS%<br>
			#ADDRESS2#%ADDRESS2%<br>#
			#CITY#%CITY%, ##STATE#%STATE%, ##ZIP_CODE#%ZIP_CODE%<br>#
			#CONTACT#Contact: %CONTACT%<br>#
			#POS#PO@\'s: %POS%<br>#
			#APPT#Conf@: %APPT%<br>#
			#STOP_DISTANCE#%STOP_DISTANCE% miles<br>#
			<br>
		</td>
		<td>
			<strong>%SHIPMENT%#SS_NUMBER# / %SS_NUMBER%#</strong><br>
			#REF_NUMBER#REF@: %REF_NUMBER%<br>#
			#BOL_NUMBER#BOL@: %BOL_NUMBER%<br>#
			#PICKUP_NUMBER#Pickup@: %PICKUP_NUMBER%<br>#
			#CUSTOMER_NUMBER#Customer@: %CUSTOMER_NUMBER%<br>#
		</td>
		<td>
			%DUE%
		</td>
		<td>
			%COMM%
		</td>
		<td class="text-right">
			%PIECES2%
		</td>
		<td class="text-right">
			%PALLETS2%
		</td>
		<td class="text-right">
			%WEIGHT2%
		</td>
		<td class="text-right">
			%TEMP2%
		</td>
	</tr>
	',
	'footer' => '</tbody>
	</table>
		' );
	}
}

//! Form Specifications - For use with sts_form

$sts_form_edit_stop = array(	//! sts_form_editstop_form
	'title' => '<img src="images/stop_icon.png" alt="stop_icon" height="24"> Edit Stop Actuals',
	'action' => 'exp_editstop.php',
	//'actionextras' => 'disabled',
	'cancel' => 'exp_viewload.php?CODE=%LOAD_CODE%',
	'name' => 'editstop',
	'okbutton' => 'Save Changes',
	'cancelbutton' => 'Back',
		'layout' => '
	%STOP_CODE%
	<div class="form-group">
		<div class="col-sm-3">
			<div class="form-group">
				<label for="LOAD_CODE" class="col-sm-5 control-label">#LOAD_CODE#</label>
				<div class="col-sm-7">
					%LOAD_CODE%
				</div>
			</div>
			<div class="form-group">
				<label for="SHIPMENT" class="col-sm-5 control-label">#SHIPMENT#</label>
				<div class="col-sm-7">
					%SHIPMENT%
				</div>
			</div>
			<div class="form-group">
				<label for="SEQUENCE_NO" class="col-sm-5 control-label">#SEQUENCE_NO#</label>
				<div class="col-sm-7">
					%SEQUENCE_NO%
				</div>
			</div>
		</div>
		<div class="col-sm-3">
			<div class="form-group">
				<label for="STOP_TYPE" class="col-sm-5 control-label">#STOP_TYPE#</label>
				<div class="col-sm-7">
					%STOP_TYPE%
				</div>
			</div>
			<div class="form-group">
				<label for="CURRENT_STATUS" class="col-sm-5 control-label">#CURRENT_STATUS#</label>
				<div class="col-sm-7">
					%CURRENT_STATUS%
				</div>
			</div>
		</div>
		<div class="col-sm-6">
			<div class="form-group">
				<label for="SHIPPER_NAME" class="col-sm-4 control-label">#SHIPPER_NAME#</label>
				<div class="col-sm-8">
					%SHIPPER_NAME%
				</div>
			</div>
			<div class="form-group">
				<label for="CONS_NAME" class="col-sm-4 control-label">#CONS_NAME#</label>
				<div class="col-sm-8">
					%CONS_NAME%
				</div>
			</div>
		</div>
	</div>
	<div class="form-group">
		<div class="col-sm-6">
			<div class="form-group">
				<label for="ACTUAL_ARRIVE" class="col-sm-4 control-label">#ACTUAL_ARRIVE#</label>
				<div class="col-sm-8">
					%ACTUAL_ARRIVE%
				</div>
			</div>
			<div class="form-group">
				<label for="ACTUAL_DEPART" class="col-sm-4 control-label">#ACTUAL_DEPART#</label>
				<div class="col-sm-8">
					%ACTUAL_DEPART%
				</div>
			</div>
		</div>
	</div>
	
	'
);

//! Form Specifications - For use with sts_form

$sts_form_edit_stop_trailer = array(	//! sts_form_editstop_form_trailer
	'title' => '<img src="images/stop_icon.png" alt="stop_icon" height="24"> <img src="images/trailer_icon.png" alt="trailer_icon" height="24"> Edit Stop Trailer',
	'action' => 'exp_editstop_trailer.php',
	//'actionextras' => 'disabled',
	'cancel' => 'exp_viewload.php?CODE=%LOAD_CODE%',
	'name' => 'editstop_trailer',
	'okbutton' => 'Save Changes',
	'cancelbutton' => 'Back',
		'layout' => '
	%STOP_CODE%
	<div class="form-group">
		<div class="col-sm-3">
			<div class="form-group">
				<label for="LOAD_CODE" class="col-sm-5 control-label">#LOAD_CODE#</label>
				<div class="col-sm-7">
					%LOAD_CODE%
				</div>
			</div>
			<div class="form-group">
				<label for="SHIPMENT" class="col-sm-5 control-label">#SHIPMENT#</label>
				<div class="col-sm-7">
					%SHIPMENT%
				</div>
			</div>
			<div class="form-group">
				<label for="SEQUENCE_NO" class="col-sm-5 control-label">#SEQUENCE_NO#</label>
				<div class="col-sm-7">
					%SEQUENCE_NO%
				</div>
			</div>
		</div>
		<div class="col-sm-3">
			<div class="form-group">
				<label for="STOP_TYPE" class="col-sm-5 control-label">#STOP_TYPE#</label>
				<div class="col-sm-7">
					%STOP_TYPE%
				</div>
			</div>
			<div class="form-group">
				<label for="CURRENT_STATUS" class="col-sm-5 control-label">#CURRENT_STATUS#</label>
				<div class="col-sm-7">
					%CURRENT_STATUS%
				</div>
			</div>
		</div>
		<div class="col-sm-6">
			<div class="form-group">
				<label for="SHIPPER_NAME" class="col-sm-4 control-label">#SHIPPER_NAME#</label>
				<div class="col-sm-8">
					%SHIPPER_NAME%
				</div>
			</div>
			<div class="form-group">
				<label for="CONS_NAME" class="col-sm-4 control-label">#CONS_NAME#</label>
				<div class="col-sm-8">
					%CONS_NAME%
				</div>
			</div>
		</div>
	</div>
	<div class="form-group">
		<div class="col-sm-6">
			<div class="form-group">
				<label for="TRAILER" class="col-sm-4 control-label">#TRAILER#</label>
				<div class="col-sm-8">
					%TRAILER%
				</div>
			</div>
		</div>
	</div>
	
	'
);

$sts_form_edit_stop_fields = array(	//! sts_form_edit_stop_fields
	'STOP_CODE' => array( 'label' => 'Stop#', 'format' => 'hidden' ),
	'LOAD_CODE' => array( 'label' => 'Load#', 'format' => 'static', 'align' => 'right',
		'link' => 'exp_viewload.php?CODE=' ),
	'SEQUENCE_NO' => array( 'label' => 'Stop#', 'format' => 'static', 'align' => 'right' ),
	'STOP_TYPE' => array( 'label' => 'Type', 'format' => 'static', 'align' => 'right' ),
	'SHIPMENT' => array( 'label' => 'Shipment', 'format' => 'static', 'align' => 'right',
		'link' => 'exp_addshipment.php?CODE=' ),
	'SHIPPER_NAME' => array( 'label' => 'Shipper', 'format' => 'btable',
		'table' => SHIPMENT_TABLE, 'pk' => 'SHIPMENT', 'key' => 'SHIPMENT_CODE', 'fields' => 'SHIPPER_NAME',
		'static' => true ),
	'CONS_NAME' => array( 'label' => 'Consignee', 'format' => 'btable',
		'table' => SHIPMENT_TABLE, 'pk' => 'SHIPMENT', 'key' => 'SHIPMENT_CODE', 'fields' => 'CONS_NAME',
		'static' => true ),
	'CURRENT_STATUS' => array( 'label' => 'Status', 'format' => 'table',
		'table' => STATUS_CODES_TABLE, 'key' => 'STATUS_CODES_CODE', 'fields' => 'STATUS_STATE',
		'static' => true, 'align' => 'right' ),
	'ACTUAL_ARRIVE' => array( 'label' => 'Actual Arr', 'format' => 'timestamp', 'extras' => 'formnovalidate' ),
	'ACTUAL_DEPART' => array( 'label' => 'Actual Dep', 'format' => 'timestamp', 'extras' => 'formnovalidate' ),
);

$sts_form_edit_stop_trailer_fields = array(	//! sts_form_edit_stop_trailer_fields
	'STOP_CODE' => array( 'label' => 'Stop#', 'format' => 'hidden' ),
	'LOAD_CODE' => array( 'label' => 'Load#', 'format' => 'static', 'align' => 'right',
		'link' => 'exp_viewload.php?CODE=' ),
	'SEQUENCE_NO' => array( 'label' => 'Stop#', 'format' => 'static', 'align' => 'right' ),
	'STOP_TYPE' => array( 'label' => 'Type', 'format' => 'static', 'align' => 'right' ),
	'SHIPMENT' => array( 'label' => 'Shipment', 'format' => 'static', 'align' => 'right',
		'link' => 'exp_addshipment.php?CODE=' ),
	'SHIPPER_NAME' => array( 'label' => 'Shipper', 'format' => 'btable',
		'table' => SHIPMENT_TABLE, 'pk' => 'SHIPMENT', 'key' => 'SHIPMENT_CODE', 'fields' => 'SHIPPER_NAME',
		'static' => true ),
	'CONS_NAME' => array( 'label' => 'Consignee', 'format' => 'btable',
		'table' => SHIPMENT_TABLE, 'pk' => 'SHIPMENT', 'key' => 'SHIPMENT_CODE', 'fields' => 'CONS_NAME',
		'static' => true ),
	'CURRENT_STATUS' => array( 'label' => 'Status', 'format' => 'table',
		'table' => STATUS_CODES_TABLE, 'key' => 'STATUS_CODES_CODE', 'fields' => 'STATUS_STATE',
		'static' => true, 'align' => 'right' ),
	'TRAILER' => array( 'label' => 'Trailer', 'format' => 'table',
		'table' => TRAILER_TABLE, 'key' => 'TRAILER_CODE', 'fields' => 'UNIT_NUMBER' ),
);

//! Layout Specifications - For use with sts_result

$sts_result_stops_layout = array(
	//'STOP_CODE' => array( 'label' => 'Stop Code', 'format' => 'number' ),
	//'LOAD_CODE' => array( 'label' => 'Load#', 'format' => 'number' ),
	'SEQUENCE_NO' => array( 'label' => 'Stop#', 'format' => 'num0' ),
	'STOP_TYPE' => array( 'label' => 'Type', 'format' => 'text' ),
	'SHIPMENT' => array( 'label' => 'Shipment', 'format' => 'table',
		'table' => SHIPMENT_TABLE, 'key' => 'SHIPMENT_CODE', 'fields' => 'SHIPMENT_CODE' ),
	//'CURRENT_STATUS' => array( 'label' => 'Status', 'format' => 'table',
	//	'table' => STATUS_CODES_TABLE, 'key' => 'STATUS_CODES_CODE', 'fields' => 'STATUS_STATE' ),
	'SHIPPER_NAME' => array( 'label' => 'Shipper', 'format' => 'table',
		'table' => SHIPMENT_TABLE, 'key' => 'SHIPMENT_CODE', 'fields' => 'SHIPPER_NAME',
		'pk' => 'SHIPMENT' ),
	'CONS_NAME' => array( 'label' => 'Consignee', 'format' => 'table',
		'table' => SHIPMENT_TABLE, 'key' => 'SHIPMENT_CODE', 'fields' => 'CONS_NAME',
		'pk' => 'SHIPMENT' ),
	'PALLETS' => array( 'label' => 'Pallets', 'format' => 'table',
		'table' => SHIPMENT_TABLE, 'key' => 'SHIPMENT_CODE', 'fields' => 'PALLETS',
		'pk' => 'SHIPMENT' ),
	'WEIGHT' => array( 'label' => 'Weight', 'format' => 'table',
		'table' => SHIPMENT_TABLE, 'key' => 'SHIPMENT_CODE', 'fields' => 'WEIGHT',
		'pk' => 'SHIPMENT' ),
);

$sts_result_stops_lj_layout = array(
	'STOP_CODE' => array( 'label' => 'Stop Code', 'format' => 'hidden' ),
	'LOAD_CODE' => array( 'label' => 'Load Code', 'format' => 'hidden' ),
	'SEQUENCE_NO' => array( 'label' => 'Stop', 'format' => 'num0stop', 'align' => 'right', 'length' => 40 ),
	'STOP_TYPE' => array( 'label' => 'Type', 'format' => 'hidden' ),
	'STOP_STATUS' => array( 'label' => 'Status', 'format' => 'text' ),
	//'STOP_CURRENT_STATUS' => array( 'label' => 'Status2', 'format' => 'text' ),
	//'SHIPMENT' => array( 'label' => 'Shipment', 'format' => 'num0nc', 'link' => 'exp_addshipment.php?CODE=' ),
	'SHIPMENT2' => array( 'label' => 'Shipment', 'format' => 'case',
		'key' => 'STOP_TYPE', 'val1' => 'stop', 'choice1' => 'NULL', 
		'choice2' => 'SHIPMENT', 'format2' => 'num0nc', 'align' => 'right', 'link' => 'exp_addshipment.php?CODE=' ),
	'SS_NUMBER' => array( 'label' => 'Office#', 'format' => 'text', 'align' => 'right' ),
	//'SHIPMENT_STATUS' => array( 'label' => 'Status', 'format' => 'text' ),
	// case format maps to (CASE STOP_TYPE
	//				WHEN 'pick' THEN PICKUP_DATE
	//				ELSE DELIVERY_DATE END) AS DUE
	//'DUE' => array( 'label' => 'Due By', 'format' => 'case',
	//	'key' => 'STOP_TYPE', 'val1' => 'pick', 'choice1' => 'PICKUP_DATE',
	//	'choice2' => 'DELIVER_DATE' ),
	'DUE_TS2' => array( 'label' => 'Due', 'format' => 'text', 'length' => 120 ),
	'PICKUP_DATE' => array( 'label' => 'Due', 'format' => 'hidden', 'length' => 90 ),
	'PICKUP_TIME_OPTION' => array( 'label' => 'Type', 'format' => 'hidden' ),
	'PICKUP_TIME1' => array( 'label' => 'Type', 'format' => 'hidden' ),
	'PICKUP_TIME2' => array( 'label' => 'Type', 'format' => 'hidden' ),
	'DELIVER_DATE' => array( 'label' => 'Type', 'format' => 'hidden' ),
	'DELIVER_TIME_OPTION' => array( 'label' => 'Type', 'format' => 'hidden' ),
	'DELIVER_TIME1' => array( 'label' => 'Type', 'format' => 'hidden' ),
	'DELIVER_TIME2' => array( 'label' => 'Type', 'format' => 'hidden' ),
	//'DUE_END' => array( 'label' => 'Due End', 'format' => 'case',
	//	'key' => 'STOP_TYPE', 'val1' => 'pick', 'choice1' => 'PICKUP_BY_END',
	//	'choice2' => 'DELIVER_BY_END' ),
	'STOP_ETA' => array( 'label' => 'ETA', 'format' => 'timestamp-s-eta', 'length' => 200 ),
	'STOP_COMMENT' => array( 'label' => 'Comment', 'format' => 'text', 'length' => 200 ),
	'ACTUAL_ARRIVE' => array( 'label' => 'Actual Arr', 'format' => 'timestamp-s-actual', 'length' => 100 ),
	'ACTUAL_DEPART' => array( 'label' => 'Actual Dep', 'format' => 'timestamp', 'length' => 100 ),
	'EDI_ARRIVE_STATUS' => array( 'label' => 'AS', 'format' => 'edistatus' ),
	'EDI_DEPART_STATUS' => array( 'label' => 'DS', 'format' => 'edistatus' ),
	'TRAILER_NUMBER' => array( 'label' => 'Trailer', 'format' => 'text', 'searchable' => true ),
	//'TRAILER_NUMBER' => array( 'label' => 'Trailer', 'format' => 'table',
	//	'table' => TRAILER_TABLE, 'key' => 'TRAILER_CODE', 'fields' => 'UNIT_NUMBER',
	//	'length' => 80, 'searchable' => true ),
	//'link' => 'exp_edittrailer.php?CODE=', 
	'NAME' => array( 'label' => 'Shipper/Consignee', 'format' => 'text' ),
	'CITY' => array( 'label' => 'City', 'format' => 'text' ),
	'STATE' => array( 'label' => 'State', 'format' => 'text' ),
	'ZIP_CODE' => array( 'label' => 'Zip', 'format' => 'text' ),
	'COUNTRY' => array( 'label' => 'Country', 'format' => 'text' ),
	'STOP_DISTANCE' => array( 'label' => 'Distance', 'format' => 'num0', 'align' => 'right' ),
	'PIECES2' => array( 'label' => 'Items', 'format' => 'case',
		'key' => 'STOP_TYPE', 'val1' => 'stop', 'choice1' => 'NULL', 
		'choice2' => 'PIECES', 'format2' => 'num0', 'align' => 'right' ),
	//'PIECES' => array( 'label' => 'Items', 'format' => 'num0', 'align' => 'right' ),
	'PALLETS2' => array( 'label' => 'Pallets', 'format' => 'case',
		'key' => 'STOP_TYPE', 'val1' => 'stop', 'choice1' => 'NULL', 
		'choice2' => 'PALLETS', 'format2' => 'num0', 'align' => 'right' ),
	'WEIGHT2' => array( 'label' => 'Weight', 'format' => 'case',
		'key' => 'STOP_TYPE', 'val1' => 'stop', 'choice1' => 'NULL', 
		'choice2' => 'WEIGHT', 'format2' => 'num0', 'align' => 'right' ),
);


//! Edit/Delete Button Specifications - For use with sts_result

$sts_result_stops_edit = array(
	'title' => '<img src="images/stop_icon.png" alt="stop_icon" height="24"> ## Stops',
	'sort' => 'SEQUENCE_NO ASC',
	'filters' => false,
	//'cancel' => 'index.php',
	//'add' => 'exp_addstop.php',
	//'actionextras' => 'disabled',
	'addbutton' => 'Add Stop',
	'cancelbutton' => 'Back',
	/*'rowbuttons' => array(
		array( 'url' => 'exp_editstop.php?CODE=', 'key' => 'STOP_CODE', 'label' => 'SEQUENCE_NO', 'tip' => 'Edit stop ', 'icon' => 'glyphicon glyphicon-edit' ),
	) */
);


?>
