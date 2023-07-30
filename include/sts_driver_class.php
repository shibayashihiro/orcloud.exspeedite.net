<?php

// $Id: sts_driver_class.php 4697 2022-03-09 23:02:23Z duncan $
// Driver class, all activity related to drivers.

// no direct access
defined('_STS_INCLUDE') or die('Restricted access');

require_once( "sts_config.php" );
require_once( "sts_table_class.php" );
require_once( "sts_setting_class.php" );

class sts_driver extends sts_table {
	
	private $setting_table;
	private $multi_company;
	private $expire_drivers;
	private $expire_tractors;
	private $expire_trailers;
	private $default_active;
	
	// Constructor does not need the table name
	public function __construct( $database, $debug = false ) {

		$this->debug = $debug;
		$this->primary_key = "DRIVER_CODE";
		if( $this->debug ) echo "<p>Create sts_driver $this->table_name pk = $this->primary_key</p>";
		parent::__construct( $database, DRIVER_TABLE, $debug);
		$this->setting_table = sts_setting::getInstance($database, $debug);
		$this->multi_company = ($this->setting_table->get( 'option', 'MULTI_COMPANY' ) == 'true');
		$this->expire_drivers = $this->setting_table->get( 'option', 'EXPIRE_DRIVERS_ENABLED' ) == 'true';
		$this->expire_tractors = $this->setting_table->get( 'option', 'EXPIRE_TRACTORS_ENABLED' ) == 'true';
		$this->expire_trailers = $this->setting_table->get( 'option', 'EXPIRE_TRAILERS_ENABLED' ) == 'true';
		$this->default_active = $this->setting_table->get("option", "DEFAULT_ACTIVE") == 'true';
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

	public function suggest( $load, $query ) {
		require_once( "sts_office_class.php" );

		if( $this->debug ) echo "<p>suggest $query</p>";
		
		$this->office = sts_office::getInstance($this->database, $this->debug);
		
		$match = $this->office->office_code_match_driver( $load );
		
		$result = $this->database->get_multiple_rows("
			select RESOURCE_CODE, RESOURCE_NAME, RESOURCE_EXTRA, DEFAULT_TRACTOR, DEFAULT_TRAILER,
			TRACTOR_NUMBER, TRAILER_NUMBER, DRIVER_LABEL
			from
			(select FIRST_NAME, LAST_NAME, DRIVER_CODE AS RESOURCE_CODE, 
			CONCAT_WS(' ',FIRST_NAME,LAST_NAME) RESOURCE_NAME,
			CONCAT_WS(', ',PAY_TYPE,DRIVER_NUMBER) AS RESOURCE_EXTRA,
			CASE WHEN TRACTOR_EXPIRED(DEFAULT_TRACTOR) <> 'red' THEN
				DEFAULT_TRACTOR ELSE NULL END AS DEFAULT_TRACTOR,
			CASE WHEN TRAILER_EXPIRED(DEFAULT_TRAILER) <> 'red' THEN
				DEFAULT_TRAILER ELSE NULL END AS DEFAULT_TRAILER, 
			(SELECT UNIT_NUMBER from EXP_TRACTOR X 
				WHERE X.TRACTOR_CODE = EXP_DRIVER.DEFAULT_TRACTOR  
				AND TRACTOR_EXPIRED(TRACTOR_CODE) <> 'red'
				LIMIT 0 , 1) AS TRACTOR_NUMBER,
			(SELECT UNIT_NUMBER from EXP_TRAILER X
				WHERE X.TRAILER_CODE = EXP_DRIVER.DEFAULT_TRAILER
				AND TRAILER_EXPIRED(TRAILER_CODE) <> 'red'
				LIMIT 0 , 1) AS TRAILER_NUMBER,
			(SELECT LABEL AS DRIVER_LABEL
				FROM EXP_CONTACT_INFO
				WHERE CONTACT_CODE = DRIVER_CODE
				AND CONTACT_SOURCE = 'driver'
				AND CONTACT_TYPE = 'individual'
				AND ISDELETED = 0
				AND COALESCE(LABEL,'') <> ''
				LIMIT 1) AS DRIVER_LABEL
			
		from EXP_DRIVER 
		where ISACTIVE = 'Active' AND ISDELETED = false
		".($this->expire_drivers ? "AND DRIVER_EXPIRED(DRIVER_CODE, true) <> 'red'" : "")."
		".($match <> '' ? " AND ".$match : "").") DRIVER_TABLE
		where FIRST_NAME like '".$query."%'
			OR LAST_NAME like '".$query."%'
			OR DRIVER_LABEL like '".$query."%'	
		
		limit 0, 20");
		
		return $result;
	}
	
	//! Find matching drivers for a given load
	// Used for modal display on adding resources screen
	// See also exp_pick_asset.php
	public function matching( $load ) {
		require_once( "sts_office_class.php" );

		$output = '';
		if( $this->debug ) echo "<p>".__METHOD__.": load = $load</p>";
		
		$this->office = sts_office::getInstance($this->database, $this->debug);
		
		$match = $this->office->office_code_match_driver( $load );
		
		$drivers = $this->database->get_multiple_rows("
			select RESOURCE_CODE, ISEXPIRED, RESOURCE_NAME, PAY_TYPE, WORK_TYPE, DRIVER_NUMBER, DEFAULT_TRACTOR, DEFAULT_TRAILER,
			TRACTOR_NUMBER, TRAILER_NUMBER, DRIVER_LABEL, COMPANY, OFFICE
			from
			(select FIRST_NAME, LAST_NAME, DRIVER_CODE AS RESOURCE_CODE, 
			CONCAT_WS(' ',FIRST_NAME,LAST_NAME) RESOURCE_NAME,
			PAY_TYPE,WORK_TYPE,DRIVER_NUMBER,
			CASE WHEN ".($this->expire_tractors ? "TRACTOR_EXPIRED(DEFAULT_TRACTOR)" : "'green'")." <> 'red' THEN
				DEFAULT_TRACTOR ELSE NULL END AS DEFAULT_TRACTOR,
			CASE WHEN ".($this->expire_trailers ? "TRAILER_EXPIRED(DEFAULT_TRAILER)" : "'green'")." <> 'red' THEN
				DEFAULT_TRAILER ELSE NULL END AS DEFAULT_TRAILER, 
			(SELECT UNIT_NUMBER from EXP_TRACTOR X 
				WHERE X.TRACTOR_CODE = EXP_DRIVER.DEFAULT_TRACTOR  
				AND TRACTOR_EXPIRED(TRACTOR_CODE) <> 'red'
				LIMIT 0 , 1) AS TRACTOR_NUMBER,
			(SELECT UNIT_NUMBER from EXP_TRAILER X
				WHERE X.TRAILER_CODE = EXP_DRIVER.DEFAULT_TRAILER
				AND TRAILER_EXPIRED(TRAILER_CODE) <> 'red'
				LIMIT 0 , 1) AS TRAILER_NUMBER,
			(SELECT LABEL AS DRIVER_LABEL
				FROM EXP_CONTACT_INFO
				WHERE CONTACT_CODE = DRIVER_CODE
				AND CONTACT_SOURCE = 'driver'
				AND CONTACT_TYPE = 'individual'
				AND ISDELETED = 0
				AND COALESCE(LABEL,'') <> ''
				LIMIT 1) AS DRIVER_LABEL,
			".($this->expire_drivers ? "DRIVER_EXPIRED(DRIVER_CODE, true)" : "'green'")." AS ISEXPIRED,
			(SELECT COMPANY_NAME FROM EXP_COMPANY
				WHERE EXP_COMPANY.COMPANY_CODE = EXP_DRIVER.COMPANY_CODE) AS COMPANY,
			(SELECT OFFICE_NAME FROM EXP_OFFICE
				WHERE EXP_OFFICE.OFFICE_CODE = EXP_DRIVER.OFFICE_CODE) AS OFFICE
			
		from EXP_DRIVER 
		where ISACTIVE = 'Active' AND ISDELETED = false
		".($match <> '' ? " AND ".$match : "").") DRIVER_TABLE
		ORDER BY RESOURCE_NAME ASC");
		
		if( is_array($drivers) && count($drivers) > 0) {
			$output .= '<div class="table-responsive">
			<table class="display table table-striped table-condensed table-bordered table-hover" id="DRIVERS">
			<thead><tr class="exspeedite-bg"><th>Select</th><th>Driver</th>'.($this->multi_company ? '<th>Company</th><th>Office</th>' : '').'<th>Type</th><th>Work</th><th>Driver#</th><th>Tractor</th><th>Trailer</th></tr>
			</thead>
			<tbody>';
			foreach( $drivers as $driver ) {
				switch( $driver['ISEXPIRED'] ) {
					case 'red':
						$color = ' class="danger"';
						break;
					case 'orange':
						$color = ' class="inprogress2"';
						break;
					case 'yellow':
						$color = ' class="warning"';
						break;
					default:
						$color = '';
						break;
				}
				if( $driver['ISEXPIRED'] <> 'red' )
					$button = '<a class="btn btn-sm btn-success driver_pick" id="DRIVER_'.$driver['RESOURCE_CODE'].'"
					data-resource_code = "'.$driver['RESOURCE_CODE'].'"
					data-resource = "'.$driver['RESOURCE_NAME'].'"
					data-default_tractor = "'.$driver['DEFAULT_TRACTOR'].'"
					data-tractor_number = "'.$driver['TRACTOR_NUMBER'].'"
					data-default_trailer = "'.$driver['DEFAULT_TRAILER'].'"
					data-trailer_number = "'.$driver['TRAILER_NUMBER'].'"
					><span class="glyphicon glyphicon-plus"></span></a>';
				else
					$button = '<a class="btn btn-sm btn-danger disabled" name="disabled"><span class="glyphicon glyphicon-remove"></span></a>';
				$output .= '<tr'.$color.'><td>'.$button.'</td>
				<td>'.$driver['RESOURCE_NAME'].'</td>
				';
				if( $this->multi_company )
				$output .= '<td>'.$driver['COMPANY'].'</td>
				<td>'.$driver['OFFICE'].'</td>
				';
				$output .= '<td>'.$driver['PAY_TYPE'].'</td>
				<td>'.$driver['WORK_TYPE'].'</td>
				<td>'.$driver['DRIVER_NUMBER'].'</td>
				<td>'.$driver['TRACTOR_NUMBER'].'</td>
				<td>'.$driver['TRAILER_NUMBER'].'</td>
				</tr>';
			}
			$output .= '</tbody>
		</table>
		</div>
		';
		} else {
			$output .= '<h3><span class="glyphicon glyphicon-warning-sign"></span> No Available Drivers</h3>
			<h4>Go to <a href="exp_listdriver.php">List Drivers</a> screen and filter on office to confirm</h4>';
		}

		
		return $output;
	}
	
	//! Lookup mapped driver for user
	public function mapped( $user ) {
		$result = false;
		$driver = $this->database->get_one_row("	
			SELECT DRIVER,
			FROM EXP_USER
			WHERE DRIVER > 0
			AND USER_CODE = ".$user);

		if( is_array($driver) && ! empty($driver["DRIVER"]) ) {
			$result = intval($driver["DRIVER"]);
		}
		
		return $result;
	}
	
	//! Driver checkin, update the lat and lon
	public function checkin( $pk, $lat, $lon, $msg = "Driver check in" ) {
		require_once( "sts_status_class.php" );

		$result = false;
		if( $this->debug ) echo "<p>".__METHOD__.": lat = $lat, lon = $lon</p>";
		if( $pk > 0 ) {
			$result = $this->update( $pk, array( 'LAT' => $lat, 'LON' => $lon,
				'-LOC_ASOF' => 'NOW()'));
			
			// Is the driver on a current load?
			$check = $this->fetch_rows( "DRIVER_CODE = ".$pk.
				" AND CURRENT_LOAD > 0
				AND (SELECT BEHAVIOR
				FROM EXP_LOAD L, EXP_STATUS_CODES S
				WHERE L.LOAD_CODE = CURRENT_LOAD
				AND L.CURRENT_STATUS = S.STATUS_CODES_CODE) IN ('arrive cons', 'arrrecdock',
				'arrive shipper','arrshdock','arrive stop','depart cons','deprecdock',
				'depart shipper', 'depshdock', 'depart stop','dispatch')", "CURRENT_LOAD");
			// Add a log entry
			if( is_array($check) && count($check) == 1 && isset($check[0]["CURRENT_LOAD"])) {
				$status = sts_status::getInstance($this->database, $this->debug);
				$status->add_load_status($check[0]["CURRENT_LOAD"],
					$msg, $lat, $lon );
			}
			

		}
		
		return $result;
	}
	
    //! create a menu of available drivers
    public function driver_menu( $selected = false, $id = 'DRIVER_CODE', $match = '', $onchange = true ) {
		$select = false;

		$choices = $this->fetch_rows( "ISDELETED = false".
			($match <> '' ? ' AND '.$match : ''), 
			"DRIVER_CODE, FIRST_NAME, LAST_NAME", "LAST_NAME ASC" );

		if( is_array($choices) && count($choices) > 0) {
			
			$select = '<select class="form-control input-sm" name="'.$id.'" id="'.$id.'"'.($onchange ? ' onchange="form.submit();"' : '').'>
			';
			foreach( $choices as $row ) {
				$select .= '<option value="'.$row["DRIVER_CODE"].'"';
				if( $selected && $selected == $row["DRIVER_CODE"] )
					$select .= ' selected';
				$select .= '>'.$row["FIRST_NAME"].' '.$row["LAST_NAME"].'</option>
				';
			}
			$select .= '</select>';
		}
			
		return $select;
	}

	public function check_expired( $pk, $wrap = true ) {
		$response = '';
		//! SCR# 646 - Random feature
		$check = $this->fetch_rows( $this->primary_key." = ".$pk." AND ISDELETED = false",
			"PHYSICAL_DUE, DATEDIFF(PHYSICAL_DUE, CURRENT_DATE) PHYSICAL_DAYS,
			DRIVER_RANDOM, DATEDIFF(DRIVER_RANDOM, CURRENT_DATE) RANDOM_DAYS,
			(SELECT MAX(LICENSE_EXPIRY_DATE)
			FROM EXP_LICENSE
			WHERE CONTACT_CODE = DRIVER_CODE
			AND CONTACT_SOURCE = 'driver') LICENSE_DUE,
	DATEDIFF((SELECT MAX(LICENSE_EXPIRY_DATE)
			FROM EXP_LICENSE
			WHERE CONTACT_CODE = DRIVER_CODE
			AND CONTACT_SOURCE = 'driver'), CURRENT_DATE) LICENSE_DAYS" );
		if( is_array($check) && count($check) == 1 ) {
			$responses = array();
			
			//! SCR# 646 - Random feature
			if( in_group( EXT_GROUP_RANDOM ) && ! empty($check[0]["DRIVER_RANDOM"]) ) {
				if( $check[0]["RANDOM_DAYS"] <= 0 )
					$responses[] =  '<span class="text-danger"><strong><span class="glyphicon glyphicon-lock"></span><span class="glyphicon glyphicon-warning-sign"></span></strong> Driver random due date ('.date("m/d/Y", strtotime($check[0]["DRIVER_RANDOM"])).') expired.</span>';
				else if( $check[0]["RANDOM_DAYS"] < 15 )
					$responses[] =  '<span class="text-inprogress2"><strong><span class="glyphicon glyphicon-lock"></span><span class="glyphicon glyphicon-warning-sign"></span></strong> Driver random ('.
					date("m/d/Y", strtotime($check[0]["DRIVER_RANDOM"])).') due < 15 days.</span>';
				else if( $check[0]["RANDOM_DAYS"] >= 15 )
					$responses[] =  '<span class="text-warning"><strong><span class="glyphicon glyphicon-lock"></span><span class="glyphicon glyphicon-warning-sign"></span></strong> Driver random ('.date("m/d/Y", strtotime($check[0]["DRIVER_RANDOM"])).') due in '.$check[0]["RANDOM_DAYS"].' days.</span>';
			}


			if( ! isset($check[0]["PHYSICAL_DUE"]) || is_null($check[0]["PHYSICAL_DUE"]))
				$responses[] =  '<span class="text-danger"><strong><span class="glyphicon glyphicon-warning-sign"></span></strong> Missing driver physical due date.</span>';
			else if( $check[0]["PHYSICAL_DAYS"] <= 0 )
				$responses[] =  '<span class="text-danger"><strong><span class="glyphicon glyphicon-warning-sign"></span></strong> Driver physical due date ('.date("m/d/Y", strtotime($check[0]["PHYSICAL_DUE"])).') expired.</span>';
			else if( $check[0]["PHYSICAL_DAYS"] < 15 )
				$responses[] =  '<span class="text-inprogress2"><strong><span class="glyphicon glyphicon-warning-sign"></span></strong> Driver physical ('.
				date("m/d/Y", strtotime($check[0]["PHYSICAL_DUE"])).') due < 15 days.</span>';
			else if( $check[0]["PHYSICAL_DAYS"] < 30 )
				$responses[] =  '<span class="text-warning"><strong><span class="glyphicon glyphicon-warning-sign"></span></strong> Driver physical ('.date("m/d/Y", strtotime($check[0]["PHYSICAL_DUE"])).') due < 30 days.</span>';

			if( ! isset($check[0]["LICENSE_DUE"]) || is_null($check[0]["LICENSE_DUE"]))
				$responses[] =  '<span class="text-danger"><strong><span class="glyphicon glyphicon-warning-sign"></span></strong> Missing driver license due date.</span>';
			else if( $check[0]["LICENSE_DAYS"] <= 0 )
				$responses[] =  '<span class="text-danger"><strong><span class="glyphicon glyphicon-warning-sign"></span></strong> Driver license ('.date("m/d/Y", strtotime($check[0]["LICENSE_DUE"])).') expired.</span>';
			else if( $check[0]["LICENSE_DAYS"] < 15 )
				$responses[] =  '<span class="text-inprogress2"><strong><span class="glyphicon glyphicon-warning-sign"></span></strong> Driver license ('.date("m/d/Y", strtotime($check[0]["LICENSE_DUE"])).') expires < 15 days.</span>';
			else if( $check[0]["LICENSE_DAYS"] < 30 )
				$responses[] =  '<span class="text-warning"><strong><span class="glyphicon glyphicon-warning-sign"></span></strong> Driver license ('.date("m/d/Y", strtotime($check[0]["LICENSE_DUE"])).') expires < 30 days.</span>';
			
			if( count($responses) > 0 ) {
				$response = implode("<br>\n", $responses);
				if( $wrap )
					$response = '<div class="panel panel-default tighter">
  <div class="panel-body" style="padding: 5px;">'.$response.'</div></div>';
  			}
		}
		
		return $response;
	}
	
	//! Display expired drivers
	public function alert_expired() {
		$result = '';

		//! SCR# 544 - only show active if setting is true 
		if( $this->default_active )
			$df = " AND ISACTIVE = 'Active'";
		else
			$df = "";

		//! SCR# 646 - Random feature
		$random = in_group( EXT_GROUP_RANDOM ) ? 'true' : 'false';

		$expired = $this->database->get_multiple_rows("
			SELECT DRIVER_CODE, ANAME, ISEXPIRED
			FROM
			(SELECT DRIVER_CODE, CONCAT_WS(' ',FIRST_NAME,LAST_NAME) AS ANAME,
				DRIVER_EXPIRED(DRIVER_CODE, $random) ISEXPIRED
			FROM EXP_DRIVER
			WHERE ISDELETED = 0
			$df) X
			WHERE ISEXPIRED <> 'GREEN'
			ORDER BY FIELD(ISEXPIRED, 'red', 'orange', 'yellow'), DRIVER_CODE ASC");
		if( is_array($expired) && count($expired) > 0 ) {
			$actions = array();
			foreach($expired as $row) {
				switch( $row["ISEXPIRED"] ) {
					case 'red': $colour = 'danger'; break;
					case 'orange': $colour = 'warning'; break;
					case 'yellow': $colour = 'yellow'; break;
					default: $colour = 'default'; break;
				}
				//! SCR# 616 - Get details for popup
				$details = $this->check_expired( $row["DRIVER_CODE"], false );

				//! SCR# 646 - Random feature - hide if no other issues
				if( ! in_group( EXT_GROUP_RANDOM ) && $details == '' )
					continue;
				
				$actions[] = '<a class="btn btn-sm btn-'.$colour.' inform" href="exp_editdriver.php?CODE='.$row["DRIVER_CODE"].'" title="<strong>'.$row["ANAME"].'</strong>" data-content=\''.$details.'\'>'.$row["ANAME"].'</a>';
			}
			$result = '<p style="margin-bottom: 5px;"><a class="btn btn-sm btn-primary" data-toggle="collapse" href="#collapseDrivers" aria-expanded="false" aria-controls="collapseDrivers"><span class="glyphicon glyphicon-warning-sign"></span> '.plural(count($expired), ' Driver').' need attention</a> (red=expired, orange=under 15 days, yellow=needs attention soon)</p>
			<div class="collapse" id="collapseDrivers">
			<p>'.implode(" ", $actions).'</p>
			</div>';

		}		
		return $result;
	}

	public function fuel_cards( $pk ) {
		if( $this->debug ) echo "<p>".__METHOD__.": pk = $pk</p>";
		$output = '';
		$check = $this->database->get_multiple_rows("
			SELECT FUEL_CARD_CODE, CARD_SOURCE, CARD_NUM
			FROM EXP_DRIVER_CARD
			WHERE DRIVER_CODE = $pk");
		if( is_array($check) && count($check) > 0 ) {
			foreach( $check as $row ) {
				$line[] = '<a class="text-danger delcard" href="exp_driver_card.php?PW=Society&DEL_DRVER='.$row["FUEL_CARD_CODE"].'"><span class="glyphicon glyphicon-remove"></span></a> '.$row["CARD_SOURCE"].' / '.$row["CARD_NUM"];
			}
			$output = '<div class="panel panel-info">
			  <div class="panel-heading">
			    <h3 class="panel-title">Fuel Cards Assigned</h3>
			  </div>
			  <div class="panel-body">
				'.implode('<br>', $line).'
				</div>
				<div class="panel-footer"><small><span class="glyphicon glyphicon-warning-sign"></span> Fuel cards are assigned during fuel card import and used for fuel card cash advances. Removing a card here will only impact future fuel card imports.</small></div>
			</div>';
		}
		return $output;
	}
	
	public function del_fuel_card( $pk ) {
		require_once( "sts_card_ftp_class.php" );
		
		$ftp = sts_card_ftp::getInstance($this->database, $this->debug);
		if( $this->debug ) echo "<p>".__METHOD__.": pk = $pk</p>";
		$check = $this->database->get_one_row("SELECT d.FIRST_NAME, d.LAST_NAME, c.CARD_NUM 
			FROM EXP_DRIVER_CARD c, EXP_DRIVER d
			WHERE c.FUEL_CARD_CODE = $pk
			AND c.DRIVER_CODE = d.DRIVER_CODE");
		if( is_array($check) && isset($check["FIRST_NAME"]) && isset($check["LAST_NAME"])) {
			$result = $this->database->get_one_row("DELETE FROM EXP_DRIVER_CARD
				WHERE FUEL_CARD_CODE = $pk");
			$ftp->log_event( __METHOD__.": remove card ".$check["CARD_NUM"].
				" from ".$check["FIRST_NAME"]." ".$check["LAST_NAME"].
				", result = ".($result ? 'true' : 'false'), EXT_ERROR_DEBUG);
		}
	}
	
	//! SCR# 240 - get address of the last stop for this driver
	public function last_stop_address( $pk ) {
		if( $this->debug ) echo "<p>".__METHOD__.": pk = $pk</p>";
		
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
			FROM EXP_STOP S, EXP_DRIVER D
			WHERE D.DRIVER_CODE = $pk
            AND D.LAST_STOP = S.STOP_CODE) S
            LEFT JOIN
			EXP_SHIPMENT T
			ON T.SHIPMENT_CODE = S.SHIPMENT");

		return $result;		
	}
	
	//! SCR# 508 - check driver pay for a given period for missing data
	public function check_driver_pay( $driver, $from, $to ) {
		require_once( "sts_email_class.php" );

		$result = false;
		$check = $this->database->get_multiple_rows("SELECT DPM.LOAD_ID,DPM.TRIP_ID, DPM.TRIP_PAY,
			(CASE WHEN DPM.LOAD_ID > 0 THEN
			TRIP_PAY <> 0 AND NOT EXISTS (SELECT * FROM EXP_LOAD_PAY_MASTER LPM
				WHERE LPM.LOAD_ID=DPM.LOAD_ID
				AND LPM.DRIVER_ID=DPM.DRIVER_ID)
			ELSE
			TRIP_PAY <> 0 AND NOT EXISTS (SELECT * FROM EXP_LOAD_PAY_MASTER LPM
				WHERE LPM.TRIP_ID=DPM.TRIP_ID
				AND LPM.DRIVER_ID=DPM.DRIVER_ID)
			END) AS LPM_MISSING
			
			FROM EXP_DRIVER_PAY_MASTER DPM
				WHERE WEEKEND_FROM='".date('Y-m-d',strtotime($from))."'
				AND WEEKEND_TO='".date('Y-m-d',strtotime($to))."'
				AND DRIVER_ID=".$driver
		);
		
		if( is_array($check) && count($check) > 0 ) {
			foreach( $check as $row ) {
				if( $row['LPM_MISSING'] == 1 ) {
					$email = sts_email::getInstance($this->database, $this->debug);
					$email->send_alert(__METHOD__."($driver, $from, $to): Missing entry in EXP_LOAD_PAY_MASTER<br>".
					($row['LOAD_ID'] > 0 ? "load_id = ".$row['LOAD_ID'] : "trip_id = ".$row['TRIP_ID']).
					"<br>trip_pay = ".$row['TRIP_PAY'] );
					$result = true;
				}
			}
		}
		
		return $result;
	}

}

class sts_driver_lj extends sts_driver {

	// Constructor does not need the table name
	public function __construct( $database, $debug = false ) {

		if( $debug ) echo "<p>Create sts_driver_lj</p>";
		parent::__construct( $database, $debug);
	}

	// Fetch one or more rows
	public function fetch_rows( $match = "", $fields = "*", $order = "", $limit = "", $groupby = "", $match2 = "" ) {
		if( $this->debug ) echo "<p>fetch_rows $match</p>";
		
		$date = date('Y-m-d');
		$week = date('W', strtotime($date));
		$year = date('Y', strtotime($date));
		$start = date("Y-m-d", strtotime("{$year}-W{$week}+1")); //Returns the date of monday in week
		$end = date("Y-m-d", strtotime("{$year}-W{$week}-6"));
		
		//! SCR# 646 - Random feature
		$random = in_group( EXT_GROUP_RANDOM ) ? 'true' : 'false';

		//! SCR# 421 - Disable button if already done this week
		$result = $this->database->get_multiple_rows("SELECT $fields FROM
			(SELECT *,
			(SELECT MAX(ADDED_ON)
				FROM EXP_DRIVER_PAY_MASTER
				WHERE DRIVER_CODE = DRIVER_ID) ADDED_ON,
			DRIVER_EXPIRED(DRIVER_CODE, $random) AS EXPIRED,
			(COALESCE((SELECT count(*) AS NUM FROM ".DRIVER_PAY_MASTER."
				WHERE WEEKEND_FROM='".$start."'
				AND WEEKEND_TO='".$end."'
				AND DRIVER_ID=DRIVER_CODE
				AND FINALIZE_STATUS in ('finalized', 'paid')),0) > 0) AS DONE_THIS_WEEK
			FROM EXP_DRIVER
			".($match <> "" ? "WHERE $match" : "")."
			".($order <> "" ? "ORDER BY $order" : "")."
			".($limit <> "" ? "LIMIT $limit" : "").") EXP_DRIVER" );

		if( strpos($fields, 'SQL_CALC_FOUND_ROWS') !== false) {
			$result1 = $this->database->get_one_row( "SELECT COUNT(*) AS FOUND
				FROM EXP_DRIVER
				WHERE $match" );
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

//! Form Specifications - For use with sts_form

$sts_form_adddriver_form = array(
	'action' => 'exp_adddriver.php',
	//'actionextras' => 'disabled',
	'cancel' => 'exp_listdriver.php',
	'name' => 'adddriver',
	'okbutton' => 'Add Driver',
	'cancelbutton' => 'Cancel'
);

$sts_form_adddriver2_form = array(	//! sts_adddriver2_form
	'title' => '<img src="images/driver_icon.png" alt="driver_icon" height="24"> Add Driver',
	'action' => 'exp_adddriver.php',
	//'actionextras' => 'disabled',
	'cancel' => 'exp_listdriver.php',
	'name' => 'adddriver',
	'okbutton' => 'Save Changes',
	'cancelbutton' => 'Cancel',
		'layout' => '
	<div class="form-group">
		<div class="col-sm-4">
			<!-- DRIVER_TYPES1 -->
			<div class="form-group tighter">
				<label for="DRIVER_TYPE" class="col-sm-4 control-label">#DRIVER_TYPE#</label>
				<div class="col-sm-8">
					%DRIVER_TYPE%
				</div>
			</div>
			<!-- DRIVER_TYPES2 -->
			<div class="form-group tighter">
				<label for="FIRST_NAME" class="col-sm-4 control-label">#FIRST_NAME#</label>
				<div class="col-sm-8">
					%FIRST_NAME%
				</div>
			</div>
			<div class="form-group tighter">
				<label for="MIDDLE_NAME" class="col-sm-4 control-label">#MIDDLE_NAME#</label>
				<div class="col-sm-8">
					%MIDDLE_NAME%
				</div>
			</div>
			<div class="form-group tighter">
				<label for="LAST_NAME" class="col-sm-4 control-label">#LAST_NAME#</label>
				<div class="col-sm-8">
					%LAST_NAME%
				</div>
			</div>
			<!-- CHECK_NAME1 -->
			<div class="form-group tighter">
				<label for="CHECK_NAME" class="col-sm-4 control-label">#CHECK_NAME#</label>
				<div class="col-sm-8">
					%CHECK_NAME%
				</div>
			</div>
			<!-- CHECK_NAME2 -->
			<div class="form-group tighter">
				<label for="BIRTHDATE" class="col-sm-4 control-label">#BIRTHDATE#</label>
				<div class="col-sm-8">
					%BIRTHDATE%
				</div>
			</div>
			<div class="form-group tighter">
				<label for="START_DATE" class="col-sm-4 control-label">#START_DATE#</label>
				<div class="col-sm-8">
					%START_DATE%
				</div>
			</div>
			<div class="form-group tighter">
				<label for="EXIT_DATE" class="col-sm-4 control-label">#EXIT_DATE#</label>
				<div class="col-sm-8">
					%EXIT_DATE%
				</div>
			</div>
			<div class="form-group tighter">
				<label for="ELIGIBLE_VAC" class="col-sm-4 control-label">#ELIGIBLE_VAC#</label>
				<div class="col-sm-8">
					%ELIGIBLE_VAC%
				</div>
			</div>
			<div class="form-group tighter">
				<label for="ELIGIBLE_PERS" class="col-sm-4 control-label">#ELIGIBLE_PERS#</label>
				<div class="col-sm-8">
					%ELIGIBLE_PERS%
				</div>
			</div>
			<!-- SSN1 -->
			<div class="form-group tighter">
				<label for="SOCIAL_NUMBER" class="col-sm-4 control-label">#SOCIAL_NUMBER#</label>
				<div class="col-sm-8">
					%SOCIAL_NUMBER%
				</div>
			</div>
			<!-- SSN2 -->
			<div class="form-group tighter">
				<label for="EMAIL_NOTIFY" class="col-sm-4 control-label">#EMAIL_NOTIFY#</label>
				<div class="col-sm-8">
					%EMAIL_NOTIFY%
				</div>
			</div>
		</div>
		<div class="col-sm-4">
			<!-- CC01 -->
			<div class="form-group tighter">
				<label for="COMPANY_CODE" class="col-sm-4 control-label">#COMPANY_CODE#</label>
				<div class="col-sm-8">
					%COMPANY_CODE%
				</div>
			</div>
			<div class="form-group tighter">
				<label for="OFFICE_CODE" class="col-sm-4 control-label">#OFFICE_CODE#</label>
				<div class="col-sm-8">
					%OFFICE_CODE%
				</div>
			</div>
			<!-- CC02 -->
			<div class="form-group tighter driver_only">
				<label for="PAY_TYPE" class="col-sm-4 control-label">#PAY_TYPE#</label>
				<div class="col-sm-8">
					%PAY_TYPE%
				</div>
			</div>
			<div class="form-group tighter driver_only">
				<label for="WORK_TYPE" class="col-sm-4 control-label">#WORK_TYPE#</label>
				<div class="col-sm-8">
					%WORK_TYPE%
				</div>
			</div>
			<div class="form-group tighter driver_only">
				<label for="PART_382" class="col-sm-4 control-label">#PART_382#</label>
				<div class="col-sm-8">
					%PART_382%
				</div>
			</div>
			<div class="form-group tighter">
				<label for="EMPLOYEE_NUMBER" class="col-sm-4 control-label">#EMPLOYEE_NUMBER#</label>
				<div class="col-sm-8">
					%EMPLOYEE_NUMBER%
				</div>
			</div>
			<div class="form-group tighter driver_only">
				<label for="DRIVER_NUMBER" class="col-sm-4 control-label">#DRIVER_NUMBER#</label>
				<div class="col-sm-8">
					%DRIVER_NUMBER%
				</div>
			</div>
			<div class="form-group tighter driver_only">
				<label for="PHYSICAL_DUE" class="col-sm-4 control-label">#PHYSICAL_DUE#</label>
				<div class="col-sm-8">
					%PHYSICAL_DUE%
				</div>
			</div>
			<div class="form-group tighter driver_only">
				<label for="DEFAULT_TRACTOR" class="col-sm-4 control-label">#DEFAULT_TRACTOR#</label>
				<div class="col-sm-8">
					%DEFAULT_TRACTOR%
				</div>
			</div>
			<div class="form-group tighter driver_only">
				<label for="DEFAULT_TRAILER" class="col-sm-4 control-label">#DEFAULT_TRAILER#</label>
				<div class="col-sm-8">
					%DEFAULT_TRAILER%
				</div>
			</div>
			<div class="form-group tighter driver_only">
				<label for="TEAM_DRIVER_ID" class="col-sm-4 control-label">#TEAM_DRIVER_ID#</label>
				<div class="col-sm-8">
					%TEAM_DRIVER_ID%
				</div>
			</div>
		</div>
		<div class="col-sm-4">
			<div class="form-group tighter">
				<label for="DRIVER_BITMAP" class="col-sm-3 control-label">#DRIVER_BITMAP#</label>
				<div class="col-sm-9">
					%DRIVER_BITMAP%
				</div>
			</div>
			<div class="form-group tighter tip-bottom" data-toggle="tooltip" title="Some tooltip text!">
				<label for="DRIVER_NOTES" class="col-sm-3 control-label">#DRIVER_NOTES#</label>
				<div class="col-sm-9">
					%DRIVER_NOTES%
				</div>
			</div>
		</div>
	</div>
	
	'
);

$sts_form_editdriver_form = array( //! sts_form_editdriver_form
	'title' => '<img src="images/driver_icon.png" alt="driver_icon" height="24"> Edit Driver',
	'action' => 'exp_editdriver.php',
	//'actionextras' => 'disabled',
	'cancel' => 'exp_listdriver.php',
	'name' => 'editdriver',
	'okbutton' => 'Save Changes to Driver',
	'saveadd' => 'Add Another',
	'buttons' => array( 
		array( 'label' => 'Settlement History', 'link' => 'exp_billhistory.php?CODE=%DRIVER_CODE%',
		'button' => 'info', 'icon' => '<span class="glyphicon glyphicon-usd"></span>',
		'restrict' => EXT_GROUP_FINANCE ),
	),
	'cancelbutton' => 'Back to Drivers',
		'layout' => '
		%DRIVER_CODE%
	<div id="warnings"></div>
	<div class="form-group">
		<div class="col-sm-4">
			<!-- DRIVER_TYPES1 -->
			<div class="form-group tighter">
				<label for="DRIVER_TYPE" class="col-sm-4 control-label">#DRIVER_TYPE#</label>
				<div class="col-sm-8">
					%DRIVER_TYPE%
				</div>
			</div>
			<!-- DRIVER_TYPES2 -->
			<div class="form-group tighter driver_only">
				<label for="ISACTIVE" class="col-sm-4 control-label">#ISACTIVE#</label>
				<div class="col-sm-8">
					%ISACTIVE%
				</div>
			</div>
			<div class="form-group tighter driver_only" id="OOS" hidden>
				<label for="OOS_TYPE" class="col-sm-4 control-label">#OOS_TYPE#</label>
				<div class="col-sm-8">
					%OOS_TYPE%
				</div>
			</div>
			<div class="form-group tighter">
				<label for="FIRST_NAME" class="col-sm-4 control-label">#FIRST_NAME#</label>
				<div class="col-sm-8">
					%FIRST_NAME%
				</div>
			</div>
			<div class="form-group tighter">
				<label for="MIDDLE_NAME" class="col-sm-4 control-label">#MIDDLE_NAME#</label>
				<div class="col-sm-8">
					%MIDDLE_NAME%
				</div>
			</div>
			<div class="form-group tighter">
				<label for="LAST_NAME" class="col-sm-4 control-label">#LAST_NAME#</label>
				<div class="col-sm-8">
					%LAST_NAME%
				</div>
			</div>
			<!-- CHECK_NAME1 -->
			<div class="form-group tighter">
				<label for="CHECK_NAME" class="col-sm-4 control-label">#CHECK_NAME#</label>
				<div class="col-sm-8">
					%CHECK_NAME%
				</div>
			</div>
			<!-- CHECK_NAME2 -->
			<div class="form-group tighter">
				<label for="BIRTHDATE" class="col-sm-4 control-label">#BIRTHDATE#</label>
				<div class="col-sm-8">
					%BIRTHDATE%
				</div>
			</div>
			<div class="form-group tighter">
				<label for="START_DATE" class="col-sm-4 control-label">#START_DATE#</label>
				<div class="col-sm-8">
					%START_DATE%
				</div>
			</div>
			<!-- RANDOM1 -->
			<div class="form-group tighter bg-danger random">
				<label for="DRIVER_RANDOM" class="col-sm-4 control-label">#DRIVER_RANDOM#</label>
				<div class="col-sm-8">
					%DRIVER_RANDOM%
				</div>
			</div>
			<!-- RANDOM2 -->
			<div class="form-group tighter">
				<label for="EXIT_DATE" class="col-sm-4 control-label">#EXIT_DATE#</label>
				<div class="col-sm-8">
					%EXIT_DATE%
				</div>
			</div>
			<div class="form-group tighter">
				<label for="ELIGIBLE_VAC" class="col-sm-4 control-label">#ELIGIBLE_VAC#</label>
				<div class="col-sm-8">
					%ELIGIBLE_VAC%
				</div>
			</div>
			<div class="form-group tighter">
				<label for="ELIGIBLE_PERS" class="col-sm-4 control-label">#ELIGIBLE_PERS#</label>
				<div class="col-sm-8">
					%ELIGIBLE_PERS%
				</div>
			</div>
			<!-- SSN1 -->
			<div class="form-group tighter">
				<label for="SOCIAL_NUMBER" class="col-sm-4 control-label">#SOCIAL_NUMBER#</label>
				<div class="col-sm-8">
					%SOCIAL_NUMBER%
				</div>
			</div>
			<!-- SSN2 -->
			<div class="form-group tighter">
				<label for="EMAIL_NOTIFY" class="col-sm-4 control-label">#EMAIL_NOTIFY#</label>
				<div class="col-sm-8">
					%EMAIL_NOTIFY%
				</div>
			</div>
		</div>
		<div class="col-sm-4">
			<!-- CC01 -->
			<div class="form-group tighter">
				<label for="COMPANY_CODE" class="col-sm-4 control-label">#COMPANY_CODE#</label>
				<div class="col-sm-8">
					%COMPANY_CODE%
				</div>
			</div>
			<div class="form-group tighter">
				<label for="OFFICE_CODE" class="col-sm-4 control-label">#OFFICE_CODE#</label>
				<div class="col-sm-8">
					%OFFICE_CODE%
				</div>
			</div>
			<!-- CC02 -->
			<div class="form-group tighter driver_only">
				<label for="PAY_TYPE" class="col-sm-4 control-label">#PAY_TYPE#</label>
				<div class="col-sm-8">
					%PAY_TYPE%
				</div>
			</div>
			<div class="form-group tighter driver_only">
				<label for="WORK_TYPE" class="col-sm-4 control-label">#WORK_TYPE#</label>
				<div class="col-sm-8">
					%WORK_TYPE%
				</div>
			</div>
			<div class="form-group tighter driver_only">
				<label for="PART_382" class="col-sm-4 control-label">#PART_382#</label>
				<div class="col-sm-8">
					%PART_382%
				</div>
			</div>
			<div class="form-group tighter">
				<label for="EMPLOYEE_NUMBER" class="col-sm-4 control-label">#EMPLOYEE_NUMBER#</label>
				<div class="col-sm-8">
					%EMPLOYEE_NUMBER%
				</div>
			</div>
			<div class="form-group tighter driver_only">
				<label for="DRIVER_NUMBER" class="col-sm-4 control-label">#DRIVER_NUMBER#</label>
				<div class="col-sm-8">
					%DRIVER_NUMBER%
				</div>
			</div>
			<div class="form-group tighter driver_only">
				<label for="PHYSICAL_DUE" class="col-sm-4 control-label">#PHYSICAL_DUE#</label>
				<div class="col-sm-8">
					%PHYSICAL_DUE%
				</div>
			</div>
			<div class="form-group tighter driver_only">
				<label for="DEFAULT_TRACTOR" class="col-sm-4 control-label">#DEFAULT_TRACTOR#</label>
				<div class="col-sm-8">
					%DEFAULT_TRACTOR%
				</div>
			</div>
			<div class="form-group tighter driver_only">
				<label for="DEFAULT_TRAILER" class="col-sm-4 control-label">#DEFAULT_TRAILER#</label>
				<div class="col-sm-8">
					%DEFAULT_TRAILER%
				</div>
			</div>
			<div class="form-group tighter driver_only">
				<label for="TEAM_DRIVER_ID" class="col-sm-4 control-label">#TEAM_DRIVER_ID#</label>
				<div class="col-sm-8">
					%TEAM_DRIVER_ID%
				</div>
			</div>
		</div>
		<div class="col-sm-4">
			<div class="form-group tighter">
				<label for="DRIVER_BITMAP" class="col-sm-3 control-label">#DRIVER_BITMAP#</label>
				<div class="col-sm-9">
					%DRIVER_BITMAP%
				</div>
			</div>
			<div class="form-group tighter driver_only">
				<label for="CURRENT_LOAD" class="col-sm-3 control-label">#CURRENT_LOAD#</label>
				<div class="col-sm-3">
					%CURRENT_LOAD%
				</div>
				<label for="LAST_LOAD" class="col-sm-3 control-label">#LAST_LOAD#</label>
				<div class="col-sm-3">
					%LAST_LOAD%
				</div>
			</div>
			<div class="form-group tighter">
				<label for="DRIVER_NOTES" class="col-sm-3 control-label">#DRIVER_NOTES#</label>
				<div class="col-sm-9">
					%DRIVER_NOTES%
				</div>
			</div>
			<div class="driver_only" id="FUEL_CARDS">
			</div>
			<div class="form-group tighter driver_only">
				<label for="LAST_LOCATION" class="col-sm-3 control-label">Last Location</label>
				<div class="col-sm-9" id="LAST_LOCATION">
				</div>
			</div>
		</div>
	</div>
	
	'
);

//! Field Specifications - For use with sts_form

$sts_form_add_driver_fields = array( //! $sts_form_add_driver_fields
	'DRIVER_TYPE' => array( 'label' => 'Driver Type', 'format' => 'enum',
		'extras' => 'required' ),
	'FIRST_NAME' => array( 'label' => 'First', 'format' => 'text', 
		'extras' => 'required autofocus' ),
	'MIDDLE_NAME' => array( 'label' => 'Middle', 'format' => 'text' ),
	'LAST_NAME' => array( 'label' => 'Last', 'format' => 'text',
		'extras' => 'required' ),
	'PAY_TYPE' => array( 'label' => 'Emp Type', 'format' => 'enum',
		'extras' => 'required' ),
	'EMPLOYEE_NUMBER' => array( 'label' => 'Emp#', 'format' => 'text' ),
	'DRIVER_NUMBER' => array( 'label' => 'Driver#', 'format' => 'text' ),
	'SOCIAL_NUMBER' => array( 'label' => 'SSN', 'placeholder' => '###-##-####', 'format' => 'text', 'length' => '11' ), //Should be encrypted
	'DEFAULT_TRACTOR' => array( 'label' => 'Tractor', 'format' => 'table',
		'table' => TRACTOR_TABLE, 'key' => 'TRACTOR_CODE', 'fields' => 'UNIT_NUMBER',
		'back' => 'concat_ws( \' \', FIRST_NAME , LAST_NAME )' ),
	'DEFAULT_TRAILER' => array( 'label' => 'Trailer', 'format' => 'table',
		'table' => TRAILER_TABLE, 'key' => 'TRAILER_CODE', 'fields' => 'UNIT_NUMBER',
		'back' => 'concat_ws( \' \', FIRST_NAME , LAST_NAME )' ),
	'TEAM_DRIVER_ID' => array( 'label' => 'Team Driver', 'format' => 'table',
		'table' => DRIVER_TABLE, 'key' => 'DRIVER_CODE', 'fields' => 'FIRST_NAME,LAST_NAME' ),
	'CHECK_NAME' => array( 'label' => 'Check Name', 'format' => 'text' ),
	'BIRTHDATE' => array( 'label' => 'Birthdate', 'placeholder' => 'MM/DD/YYYY', 'format' => 'date' ),
	'START_DATE' => array( 'label' => 'Hire date', 'placeholder' => 'MM/DD/YYYY', 'format' => 'date', 'extras' => 'required' ),
	'EXIT_DATE' => array( 'label' => 'Exit date', 'placeholder' => 'MM/DD/YYYY', 'format' => 'date' ),
	'ELIGIBLE_VAC' => array( 'label' => 'Eligible Vacation', 'format' => 'number', 'align' => 'right' ),
	'ELIGIBLE_PERS' => array( 'label' => 'Eligible Personal', 'format' => 'number', 'align' => 'right' ),
	'PHYSICAL_DUE' => array( 'label' => 'Physical', 'placeholder' => 'MM/DD/YYYY', 'format' => 'date' ),
	'TERMINAL_ZONE' => array( 'label' => 'Terminal Zone', 'format' => 'text' ),
	'DRIVER_BITMAP' => array( 'label' => 'Image', 'format' => 'image' ),
	'DRIVER_NOTES' => array( 'label' => 'Notes', 'format' => 'textarea', 'extras' => 'rows="5"' ),
	'COMPANY_CODE' => array( 'label' => 'Company', 'format' => 'table',
		'table' => COMPANY_TABLE, 'key' => 'COMPANY_CODE', 'fields' => 'COMPANY_NAME' ),
	'OFFICE_CODE' => array( 'label' => 'Office', 'format' => 'table',
		'table' => OFFICE_TABLE, 'key' => 'OFFICE_CODE', 'fields' => 'OFFICE_NAME' ),
	'WORK_TYPE' => array( 'label' => 'Work Type', 'format' => 'enum',
		'extras' => 'required' ),
	'PART_382' => array( 'label' => 'Part 382', 'format' => 'bool' ),
	'EMAIL_NOTIFY' => array( 'label' => 'Email Notify', 'format' => 'email', 'extras' => 'multiple' ),
);

$sts_form_edit_driver_fields = array( //! $sts_form_edit_driver_fields
	'DRIVER_CODE' => array( 'format' => 'hidden' ),	// primary key must be first
	'DRIVER_TYPE' => array( 'label' => 'Driver Type', 'format' => 'enum',
		'extras' => 'required' ),
	'ISACTIVE' => array( 'label' => 'Active', 'format' => 'enum',
		'extras' => 'required' ),
	'OOS_TYPE' => array( 'label' => 'OOS Type', 'format' => 'enum' ),
	'FIRST_NAME' => array( 'label' => 'First', 'format' => 'text', 
		'extras' => 'required autofocus' ),
	'MIDDLE_NAME' => array( 'label' => 'Middle', 'format' => 'text' ),
	'LAST_NAME' => array( 'label' => 'Last', 'format' => 'text',
		'extras' => 'required' ),
	'PAY_TYPE' => array( 'label' => 'Emp Type', 'format' => 'enum',
		'extras' => 'required' ),
	'EMPLOYEE_NUMBER' => array( 'label' => 'Emp#', 'format' => 'text' ),
	'DRIVER_NUMBER' => array( 'label' => 'Driver#', 'format' => 'text' ),
	'SOCIAL_NUMBER' => array( 'label' => 'SSN', 'format' => 'text', 'length' => '11' ), //, 'process' => 'decrypt' (turned off, needs debugging)
	'DEFAULT_TRACTOR' => array( 'label' => 'Tractor', 'format' => 'table',
		'table' => TRACTOR_TABLE, 'key' => 'TRACTOR_CODE', 'fields' => 'UNIT_NUMBER',
		'order' => 'UNIT_NUMBER ASC',
		'back' => 'concat_ws( \' \', FIRST_NAME , LAST_NAME )' ),
	'DEFAULT_TRAILER' => array( 'label' => 'Trailer', 'format' => 'table',
		'table' => TRAILER_TABLE, 'key' => 'TRAILER_CODE', 'fields' => 'UNIT_NUMBER',
		'order' => 'UNIT_NUMBER ASC',
		'back' => 'concat_ws( \' \', FIRST_NAME , LAST_NAME )' ),
	'TEAM_DRIVER_ID' => array( 'label' => 'Team Driver', 'format' => 'table',
		'table' => DRIVER_TABLE, 'key' => 'DRIVER_CODE', 'fields' => 'FIRST_NAME,LAST_NAME' ),
	'CHECK_NAME' => array( 'label' => 'Check Name', 'format' => 'text' ),
	'BIRTHDATE' => array( 'label' => 'Birthdate', 'placeholder' => 'MM/DD/YYYY', 'format' => 'date' ),
	'START_DATE' => array( 'label' => 'Hire date', 'placeholder' => 'MM/DD/YYYY', 'format' => 'date', 'extras' => 'required' ),
	'DRIVER_RANDOM' => array( 'label' => '<span class="glyphicon glyphicon-lock"></span> Random', 'placeholder' => 'MM/DD/YYYY or blank', 'format' => 'date' ),
	'EXIT_DATE' => array( 'label' => 'Exit date', 'placeholder' => 'MM/DD/YYYY', 'format' => 'date' ),
	'ELIGIBLE_VAC' => array( 'label' => 'Eligible Vacation', 'format' => 'number', 'align' => 'right' ),
	'ELIGIBLE_PERS' => array( 'label' => 'Eligible Personal', 'format' => 'number', 'align' => 'right' ),
	'PHYSICAL_DUE' => array( 'label' => 'Physical Due', 'format' => 'date' ),
	'TERMINAL_ZONE' => array( 'label' => 'Terminal Zone', 'format' => 'text' ),
	'DRIVER_BITMAP' => array( 'label' => 'Image', 'format' => 'image' ),
	'DRIVER_NOTES' => array( 'label' => 'Notes', 'format' => 'textarea', 'extras' => 'rows="5"' ),
	'CURRENT_LOAD' => array( 'label' => 'Current', 'format' => 'static',
		'link' => 'exp_viewload.php?CODE=' ),
	'LAST_LOAD' => array( 'label' => 'Last', 'format' => 'static',
		'link' => 'exp_viewload.php?CODE=' ),
	'COMPANY_CODE' => array( 'label' => 'Company', 'format' => 'table',
		'table' => COMPANY_TABLE, 'key' => 'COMPANY_CODE', 'fields' => 'COMPANY_NAME' ),
	'OFFICE_CODE' => array( 'label' => 'Office', 'format' => 'table',
		'table' => OFFICE_TABLE, 'key' => 'OFFICE_CODE', 'fields' => 'OFFICE_NAME' ),
	'WORK_TYPE' => array( 'label' => 'Work Type', 'format' => 'enum',
		'extras' => 'required' ),
	'PART_382' => array( 'label' => 'Part 382', 'format' => 'bool' ),
	'EMAIL_NOTIFY' => array( 'label' => 'Email Notify', 'format' => 'email', 'extras' => 'multiple' ),
);

//! Layout Specifications - For use with sts_result

$sts_result_drivers_layout = array( //! $sts_result_drivers_layout
	'DRIVER_CODE' => array( 'format' => 'hidden' ),
	'EXPIRED' => array( 'format' => 'hidden' ),
	//! SCR# 421 - Disable button if already done this week
	'DONE_THIS_WEEK' => array( 'format' => 'hidden' ),
	'ISDELETED' => array( 'label' => 'Del', 'align' => 'center', 'format' => 'hidden' ),
	'FIRST_NAME' => array( 'label' => 'First', 'format' => 'text' ),
	'MIDDLE_NAME' => array( 'label' => 'Middle', 'format' => 'text' ),
	'LAST_NAME' => array( 'label' => 'Last', 'format' => 'text' ),
	'ISACTIVE' => array( 'label' => 'Active', 'align' => 'center', 'format' => 'text' ),
	'PAY_TYPE' => array( 'label' => 'Type', 'format' => 'text' ),
	'COMPANY_CODE' => array( 'label' => 'Company', 'format' => 'table',
		'table' => COMPANY_TABLE, 'key' => 'COMPANY_CODE', 'fields' => 'COMPANY_NAME' ),
	'OFFICE_CODE' => array( 'label' => 'Office', 'format' => 'table',
		'table' => OFFICE_TABLE, 'key' => 'OFFICE_CODE', 'fields' => 'OFFICE_NAME' ),
	'EMPLOYEE_NUMBER' => array( 'label' => 'Emp#', 'format' => 'text' ),
	'DRIVER_NUMBER' => array( 'label' => 'Driver#', 'format' => 'text' ),
	'SOCIAL_NUMBER' => array( 'label' => 'SSN', 'format' => 'text' ),
	'CURRENT_LOAD' => array( 'label' => 'Load', 'format' => 'num0',
		'link' => 'exp_viewload.php?CODE=' ),
	'DEFAULT_TRACTOR' => array( 'label' => 'Tractor', 'format' => 'table',
		'table' => TRACTOR_TABLE, 'key' => 'TRACTOR_CODE', 'fields' => 'UNIT_NUMBER' ),
	'DEFAULT_TRAILER' => array( 'label' => 'Trailer', 'format' => 'table',
		'table' => TRAILER_TABLE, 'key' => 'TRAILER_CODE', 'fields' => 'UNIT_NUMBER' ),
	'ADDED_ON' => array( 'label' => 'Pay&nbsp;Approved', 'format' => 'date-s', 'searchable' => false ),
	'HISTORY' => array( 'label' => 'Pay History', 'format' => 'subselect',
		'key' => 'DRIVER_CODE',
		'query' => "SELECT CONCAT(DATE_FORMAT(WEEKEND_FROM,'%m/%d'),
			'&nbsp;-&nbsp;',DATE_FORMAT(WEEKEND_TO,'%m/%d')) AS HISTORY
			FROM EXP_DRIVER_PAY_MASTER WHERE DRIVER_ID=%KEY% 
			GROUP BY WEEKEND_FROM,WEEKEND_TO
			ORDER BY WEEKEND_FROM DESC", 'searchable' => false, 'sortable' => false),
	//'TEAM_DRIVER_ID' => array( 'label' => 'Team&nbsp;Driver', 'format' => 'table',
	//	'table' => DRIVER_TABLE, 'key' => 'DRIVER_CODE', 'fields' => 'concat_ws(\' \',FIRST_NAME,LAST_NAME) name' ),
	'STATUS_STATE' => array( 'label' => 'Status', 'format' => 'subselect',
		'key' => 'CURRENT_LOAD',
		'query' => "SELECT STATUS_STATE
			FROM EXP_LOAD, EXP_STATUS_CODES
			WHERE LOAD_CODE=%KEY%
			AND CURRENT_STATUS = STATUS_CODES_CODE", 'searchable' => false, 'sortable' => false),
	'CURRENT_ZONE' => array( 'label' => 'Current&nbsp;Zone', 'align' => 'right', 'format' => 'subselect',
		'key' => 'CURRENT_LOAD',
		'query' => "SELECT CURRENT_ZONE
			FROM EXP_LOAD
			WHERE LOAD_CODE=%KEY%
			AND CURRENT_ZONE > 0"),
	//'LAT' => array( 'label' => 'Lat', 'format' => 'number', 'align' => 'right' ),
	//'LON' => array( 'label' => 'Lon', 'format' => 'number', 'align' => 'right' ),
	//'STATUS' => array( 'label' => 'Status', 'format' => 'text' ),
	//'CURRENT_ZONE' => array( 'label' => 'Current&nbsp;Zone', 'format' => 'text' ),
	'CHANGED_DATE' => array( 'label' => 'Changed', 'format' => 'timestamp-s', 'length' => 90 ),
	'CHANGED_BY' => array( 'label' => 'By', 'format' => 'table',
		'table' => USER_TABLE, 'key' => 'USER_CODE', 'fields' => 'USERNAME', 'link' => 'exp_edituser.php?CODE=' )
	//'TERMINAL_ZONE' => array( 'label' => 'Terminal&nbsp;Zone', 'format' => 'text' )
);

//! Edit/Delete Button Specifications - For use with sts_result

//! SCR# 468 - allow edit driver, even if deleted
$sts_result_drivers_edit = array( //! $sts_result_drivers_edit
	'title' => '<img src="images/driver_icon.png" alt="driver_icon" height="24"> Drivers',
	'sort' => 'LAST_NAME asc, FIRST_NAME asc',
	'cancel' => 'index.php',
	'add' => 'exp_adddriver.php',
	'addbutton' => 'Add Driver',
	'addratelink'=>'exp_driverrate.php', #----> BY MONA add for the button add rate fordriver page link
	'addratebutton' => 'Add Driver Rate', #----> BY MONA  add for the button add rate fordriver page link
	/*'addpaylink'=>'exp_adddriverpay.php', #----> BY MONA add for the button add rate fordriver page link
	'addpaybutton' => 'Driver Pay', #----> BY MONA  add for the button add rate fordriver page link*/
	'cancelbutton' => 'Back',
	'rowbuttons' => array(
		array( 'url' => 'exp_editdriver.php?CODE=', 'key' => 'DRIVER_CODE', 'label' => 'FIRST_NAME', 'tip' => 'Edit driver ', 'icon' => 'glyphicon glyphicon-edit' ),
		array( 'url' => 'exp_deletedriver.php?TYPE=del&CODE=', 'key' => 'DRIVER_CODE', 'label' => 'FIRST_NAME', 'tip' => 'Delete driver ', 'icon' => 'glyphicon glyphicon-trash', 'confirm' => 'yes', 'showif' => 'notdeleted' ),
		array( 'url' => 'exp_deletedriver.php?TYPE=undel&CODE=', 'key' => 'DRIVER_CODE', 'label' => 'FIRST_NAME', 'tip' => 'Undelete driver ', 'icon' => 'glyphicon glyphicon-arrow-left', 'showif' => 'deleted' ),
		array( 'url' => 'exp_deletedriver.php?TYPE=permdel&CODE=', 'key' => 'DRIVER_CODE', 'label' => 'FIRST_NAME', 'tip' => 'Permanently Delete driver ', 'icon' => 'glyphicon glyphicon-remove', 'confirm' => 'yes', 'showif' => 'deleted' ),
		//! SCR# 421 - Disable button if already done this week
		array( 'url' => 'exp_adddriverpay.php?id=', 'key' => 'DRIVER_CODE', 'label' => 'FIRST_NAME', 'tip' => 'Driver Billing this week for ', 'icon' => 'glyphicon glyphicon-usd', 'restrict' => EXT_GROUP_FINANCE, 'showif' => 'notdone' ),
 		array( 'url' => 'exp_billhistory.php?CODE=', 'key' => 'DRIVER_CODE', 'label' => 'FIRST_NAME', 'tip' => 'View Bill History ', 'icon' => 'glyphicon glyphicon-dashboard', 'restrict' => EXT_GROUP_FINANCE )

	),
);

//! Use this for parsing CSV files & importing drivers
$sts_csv_driver_parse = array(
	array( 'table' => DRIVER_TABLE, 'class' => 'sts_driver', 'primary' => 'DRIVER_CODE',
		'rows' => array(
			'FIRST_NAME' => array( 'label' => 'FirstName', 'format' => 'text',
				'required' => true ),
			'MIDDLE_NAME' => array( 'label' => 'MiddleName', 'format' => 'text' ),
			'LAST_NAME' => array( 'label' => 'LastName', 'format' => 'text',
				'required' => true ),
			'EMAIL_NOTIFY' => array( 'label' => 'EmailNotify', 'format' => 'text' ),
			'PAY_TYPE' => array( 'label' => 'PayType', 'format' => 'enum',
				'default' => 'company' ),
			'EMPLOYEE_NUMBER' => array( 'label' => 'EmployeeNum', 'format' => 'text' ),
			'DRIVER_NUMBER' => array( 'label' => 'DriverNum', 'format' => 'text' ),
			'ISACTIVE' => array( 'value' => 'Active' ),
			'DRIVER_NOTES' => array( 'label' => 'DriverNotes', 'format' => 'text' ),
			'SOCIAL_NUMBER' => array( 'label' => 'DriverSSN', 'format' => 'text' ),
			'CHECK_NAME' => array( 'label' => 'CheckName', 'format' => 'text' ),
			'BIRTHDATE' => array( 'label' => 'BirthDate', 'format' => 'date' ),
			'START_DATE' => array( 'label' => 'HireDate', 'format' => 'date',
				'required' => true ),
			'ELIGIBLE_VAC' => array( 'label' => 'EligibleVacation', 'format' => 'number',
				'default' => 0 ),
			'ELIGIBLE_PERS' => array( 'label' => 'EligiblePersonal', 'format' => 'number',
				'default' => 0 ),
			'PHYSICAL_DUE' => array( 'label' => 'PhysicalDue', 'format' => 'date' ),
			'ISDELETED' => array( 'value' => 0 ),
			'COMPANY_CODE' => array( 'value' => 0 ),
			'WORK_TYPE' => array( 'label' => 'DriverWorkType', 'format' => 'enum',
				'default' => 'Local' ),
			'PART_382' => array( 'label' => 'DriverPart382', 'format' => 'bool',
				'default' => false ),
		),
	),
	array( 'table' => CONTACT_INFO_TABLE, 'class' => 'sts_contact_info', 'primary' => 'CONTACT_INFO_CODE',
		'rows' => array(
			'CONTACT_CODE' => array( 'format' => 'link', 'value' => 'DRIVER_CODE',
				'required' => true ),
			'CONTACT_SOURCE' => array( 'format' => 'text', 'value' => 'driver' ),
			'CONTACT_TYPE' => array( 'format' => 'enum', 'value' => 'individual' ),
	
			'LABEL' => array( 'label' => 'Label', 'format' => 'text' ),
			'CONTACT_NAME' => array( 'label' => 'ContactName', 'format' => 'text' ),
			'JOB_TITLE' => array( 'label' => 'JobTitle', 'format' => 'text' ),
			'ADDRESS' => array( 'label' => 'Address', 'format' => 'text' ),
			'ADDRESS2' => array( 'label' => 'Address2', 'format' => 'text' ),
			'CITY' => array( 'label' => 'City', 'format' => 'text' ),
			'STATE' => array( 'label' => 'State', 'format' => 'state' ),
			'ZIP_CODE' => array( 'label' => 'Zip', 'format' => 'text' ),
			'COUNTRY' => array( 'label' => 'Country', 'format' => 'enum' ),
			'PHONE_OFFICE' => array( 'label' => 'OfficePhone', 'format' => 'text' ),
			'PHONE_EXT' => array( 'label' => 'PhoneExt', 'format' => 'text' ),
			'PHONE_FAX' => array( 'label' => 'PhoneFax', 'format' => 'text' ),
			'PHONE_HOME' => array( 'label' => 'PhoneHome', 'format' => 'text' ),
			'PHONE_CELL' => array( 'label' => 'PhoneCell', 'format' => 'text' ),
			'EMAIL' => array( 'label' => 'Email', 'format' => 'email' )
		),
	),
	array( 'table' => LICENSE_TABLE, 'class' => 'sts_license', 'primary' => 'LICENSE_CODE',
		'rows' => array(
			'CONTACT_CODE' => array( 'format' => 'link', 'value' => 'DRIVER_CODE',
				'required' => true ),
			'CONTACT_SOURCE' => array( 'format' => 'text', 'value' => 'driver' ),
	
			'LICENSE_TYPE' => array( 'label' => 'LicenseType', 'format' => 'enum',
				'default' => 'CDL Class A' ),
			'LICENSE_ENDORSEMENTS' => array( 'label' => 'Endorsements', 'format' => 'text' ),
			'LICENSE_NUMBER' => array( 'label' => 'LicenseNumber', 'format' => 'text',
				'required' => true ),
			'LICENSE_STATE' => array( 'label' => 'LicenseState', 'format' => 'state' ),
			'LICENSE_EXPIRY_DATE' => array( 'label' => 'LicenseExpiry', 'format' => 'date',
				'required' => true ),
			'LICENSE_NOTES' => array( 'label' => 'LicenseNotes', 'format' => 'text' )
		),
	),
);


?>
