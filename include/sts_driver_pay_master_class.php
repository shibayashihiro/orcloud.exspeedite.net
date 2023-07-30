<?php

// no direct access
defined('_STS_INCLUDE') or die('Restricted access');

require_once( "sts_table_class.php" );
require_once( "sts_setting_class.php" );

class sts_driver_pay_master extends sts_table {
	private $setting;

	// Constructor does not need the table name
	public function __construct( $database, $debug = false ) {

		$this->debug = $debug;
		$this->primary_key = "DRIVER_PAY_ID";
		if( $this->debug ) echo "<p>Create sts_commodity</p>";
		parent::__construct( $database, DRIVER_PAY_MASTER, $debug);
		$this->setting = sts_setting::getInstance( $this->database, $this->debug );
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
    
    //! Based on a DRIVER_PAY_ID, return a list of LOAD_ID for a pay period
    public function get_loads( $ID ) {
	    
		$result = false;
		$keys = $this->fetch_rows( $this->primary_key.' = '.$ID, 
			"DRIVER_ID, WEEKEND_FROM, WEEKEND_TO" );

		if( is_array($keys) && count($keys) == 1 ) {
			$row = $keys[0];
			$keys2 = $this->fetch_rows( "DRIVER_ID='".$row["DRIVER_ID"]."'
				AND WEEKEND_FROM='".$row["WEEKEND_FROM"]."'
				AND WEEKEND_TO='".$row["WEEKEND_TO"]."'", 
				"LOAD_ID" );
			if( is_array($keys2) && count($keys2) > 0 ) {
				$result = array();
				foreach( $keys2 as $row ) {
					$result[] = $row["LOAD_ID"];
				}
			}
		}
		return $result;
    }
    
    //! Based on a DRIVER_PAY_ID, update fields for matching driver/week
    public function update_week( $ID, $fields ) {
		$result = false;
		$keys = $this->fetch_rows( $this->primary_key.' = '.$ID, 
			"DRIVER_ID, WEEKEND_FROM, WEEKEND_TO" );

		if( is_array($keys) && count($keys) == 1 ) {
			$row = $keys[0];
			
			$result =$this->update( "DRIVER_ID='".$row["DRIVER_ID"]."'
				AND WEEKEND_FROM='".$row["WEEKEND_FROM"]."'
				AND WEEKEND_TO='".$row["WEEKEND_TO"]."'", $fields );
		}

		return $result;
    }
    
    //! SCR# 757 - create a menu of available drivers
	public function driver_office_logo( $driver_code ) {
	    $logo = "";
		$check1 = $this->database->get_one_row("
				SELECT GET_LOGO( LOAD_OFFICE( 
					(SELECT OFFICE_CODE FROM EXP_DRIVER
						WHERE DRIVER_CODE = $driver_code ) ) ) AS LOGO");
		
		if( is_array($check1) && isset($check1["LOGO"]) ) {
			if( $this->debug ) {
				echo "<pre>".__METHOD__.": check1\n";
				var_dump($check1);
				echo "</pre>";
			}
			$stored_as = str_replace('/', '\\', $check1['LOGO']);
			if( file_exists($stored_as) )
				$logo = $stored_as;
			else {
				if( $this->debug ) echo "<p>".__METHOD__.": file missing! $stored_as</p>";
				// fall back to setting
				$logo = $this->setting->get( 'company', 'LOGO' );
			}
		}

		if( $this->debug ) echo "<p>".__METHOD__.": driver_code = $driver_code, logo = ".$logo."</p>";
	    return str_replace('\\', '/', $logo);
	}
	
    //! SCR# 757 - create a menu of available drivers
    public function driver_menu( $selected = false, $id = 'DRIVER_CODE', $match = '', $onchange = true ) {
		$select = false;

		$choices = $this->database->get_multiple_rows("
			SELECT  DRIVER_CODE, FIRST_NAME, LAST_NAME,
				CONCAT(LAST_NAME,', ',FIRST_NAME,' ',IFNULL(MIDDLE_NAME,''),' ',' (',
				IF(COALESCE(DRIVER_NUMBER,'') <> '',CONCAT('#',DRIVER_NUMBER,', '),''),
					'code=',DRIVER_CODE,', ',
			        IF(NUM=0,'No',NUM), ' Pay Periods)') DRIVER
			FROM
			(SELECT  DRIVER_CODE, FIRST_NAME, LAST_NAME, MIDDLE_NAME, DRIVER_NUMBER,
				(SELECT COUNT(DISTINCT WEEKEND_FROM)
				FROM EXP_DRIVER_PAY_MASTER
				WHERE FINALIZE_STATUS in('finalized', 'paid')
				AND DRIVER_ID = DRIVER_CODE) AS NUM
					FROM EXP_DRIVER WHERE ISDELETED = FALSE
					AND ISACTIVE != 'Inactive'
					ORDER BY LAST_NAME) x");

		if( is_array($choices) && count($choices) > 0) {
			
			$select = '<select class="form-control input-lg" name="'.$id.'" id="'.$id.'"'.($onchange ? ' onchange="form.submit();"' : '').'>
			';
			$select .= '<option value="None">Select Driver</option>
			';
			$select .= '<option value="All"';
				if( $selected && $selected == 'All' )
					$select .= ' selected';
				$select .= '>All Drivers</option>
				';

			foreach( $choices as $row ) {
				$select .= '<option value="'.$row["DRIVER_CODE"].'"';
				if( $selected && $selected == $row["DRIVER_CODE"] )
					$select .= ' selected';
				$select .= '>'.$row["DRIVER"].'</option>
				';
			}
			$select .= '</select>';
		}
			
		return $select;
	}

    //! SCR# 757 - create a menu of from dates
    public function pay_from_menu( $selected = false, $driver = false, $id = 'WEEKEND_FROM', $match = '', $onchange = false ) {
		$select = false;

		$choices = $this->database->get_multiple_rows("
			SELECT WEEKEND_FROM
			FROM EXP_DRIVER_PAY_MASTER
			WHERE FINALIZE_STATUS in('finalized', 'paid')
			".($driver != 'All' ? "AND DRIVER_ID = $driver" : "")."
			GROUP BY WEEKEND_FROM
			ORDER BY WEEKEND_FROM DESC");

		if( is_array($choices) && count($choices) > 0) {
			
			$select = '<select class="form-control input-lg" name="'.$id.'" id="'.$id.'"'.($onchange ? ' onchange="form.submit();"' : '').'>
			';
			foreach( $choices as $row ) {
				$select .= '<option value="'.$row["WEEKEND_FROM"].'"';
				if( $selected && $selected == $row["WEEKEND_FROM"] )
					$select .= ' selected';
				$select .= '>'.date("m/d/Y", strtotime($row["WEEKEND_FROM"])).'</option>
				';
			}
			$select .= '</select> <button class="btn btn-lg btn-default" name="submit" type="submit">Select And Continue</button>';
		} else {
			$select = '<span class="text-danger"><span class="glyphicon glyphicon-warning-sign"></span> No Approved/Paid Pay Periods For This Driver</span>';
		}
			
		return $select;
	}
	
    //! SCR# 757 - create a menu of from dates
    public function pay_to_menu( $from, $selected = false, $driver = false, $id = 'WEEKEND_TO', $match = '', $onchange = false ) {
		$select = false;

		$choices = $this->database->get_multiple_rows("
			SELECT WEEKEND_TO
			FROM EXP_DRIVER_PAY_MASTER
			WHERE FINALIZE_STATUS in('finalized', 'paid')
			".($driver != 'All' ? "AND DRIVER_ID = $driver" : "")."
			AND WEEKEND_TO > DATE('".$from."')
			GROUP BY WEEKEND_TO
			ORDER BY WEEKEND_TO DESC");

		if( is_array($choices) && count($choices) > 0) {
			
			$select = '<select class="form-control input-lg" name="'.$id.'" id="'.$id.'"'.($onchange ? ' onchange="form.submit();"' : '').'>
			';
			foreach( $choices as $row ) {
				$select .= '<option value="'.$row["WEEKEND_TO"].'"';
				if( $selected && $selected == $row["WEEKEND_TO"] )
					$select .= ' selected';
				$select .= '>'.date("m/d/Y", strtotime($row["WEEKEND_TO"])).'</option>
				';
			}
			$select .= '</select>';
		} else {
			$select = '<span class="text-danger"><span class="glyphicon glyphicon-warning-sign"></span> No Approved/Paid Pay Periods For This Driver</span>';
		}
			
		return $select;
	}
	
    public function driver_name( $driver = 'All' ) {
		$result = 'ERROR: No driver';
		
		if( $driver != 'All' ) {
			$check = $this->database->get_one_row("
				SELECT  DRIVER_CODE, FIRST_NAME, LAST_NAME,
					CONCAT(LAST_NAME,', ',FIRST_NAME,' ',IFNULL(MIDDLE_NAME,''),
						' ',' (',
						IF(COALESCE(DRIVER_NUMBER,'') <> '',CONCAT('#',DRIVER_NUMBER,', '),''),
						'code=',DRIVER_CODE,')') DRIVER
				FROM EXP_DRIVER WHERE ISDELETED = FALSE
				AND DRIVER_CODE = $driver
				LIMIT 1");
				
			if( is_array($check) && isset($check['DRIVER']) )
				$result = $check['DRIVER'];
		} else {
			$result = 'All Drivers';
		}
		
		return $result;
	}
	
	//! SCR# 757 - create a menu of available drivers
    public function driver_pay( $weekend_from, $driver = 'All' ) {
    
		$pay = $this->database->get_multiple_rows("
		WITH DriverName AS
			(SELECT DRIVER_CODE, FIRST_NAME, MIDDLE_NAME, LAST_NAME,
		        CONCAT(FIRST_NAME, ' ', IFNULL(MIDDLE_NAME, ''), ' ', LAST_NAME,
		        	' (', IFNULL(DRIVER_NUMBER, CONCAT('Code:', driver_code)), ')') DRIVER
		    FROM exp_driver
		    WHERE
		        LAST_NAME IS NOT NULL
		        ".($driver != 'All' ? "AND DRIVER_CODE = $driver" : "")."
		        AND EXISTS (SELECT LOAD_PAY_ID FROM EXP_LOAD_PAY_MASTER
		        	WHERE DRIVER_ID = DRIVER_CODE
		        	LIMIT 1)),
		        	
		DPM AS
			(SELECT DRIVER_PAY_ID, LOAD_ID, TRIP_ID, DRIVER_ID, WEEKEND_FROM, WEEKEND_TO,
				TRIP_PAY, BONUS AS DBONUS, HANDLING, GROSS_EARNING, ADDED_ON,
		    DriverName.*
			FROM EXP_DRIVER_PAY_MASTER, DriverName
			WHERE DRIVER_ID = DriverName.DRIVER_CODE
		    AND FINALIZE_STATUS IN ('finalized' , 'paid')
			and WEEKEND_FROM >= '".$weekend_from."'
			AND WEEKEND_TO <= DATE_ADD('".$weekend_from."', INTERVAL 7 DAY)),
		
		LPM AS
			(SELECT DPM.*, APPROVED_DATE, ODOMETER_FROM, ODOMETER_TO, TOTAL_MILES, BONUS,
		    APPLY_BONUS, BONUS_AMOUNT, TOTAL_TRIP_PAY, HANDLING_PALLET, HANDLING_PAY,
		    TOTAL_SETTLEMENT, LOADED_DET_HR, UNLOADED_DET_HR
		    FROM EXP_LOAD_PAY_MASTER, DPM
		    WHERE EXP_LOAD_PAY_MASTER.DRIVER_ID = DPM.DRIVER_CODE
		    AND DPM.DRIVER_PAY_ID = LOAD_PAY_ID)
		
		
		SELECT 
		    LPM.*,
		    `TOTAL MILES` AS 'TOTAL MILES MASTER',
		    IF(LPM.TRIP_ID = 0,
		        IF(Details.DRIVER_ID IS NULL,
		                `TOTAL PAY` - `EXTRA STOP` - `CASH ADVANCES` - `TROLLS` - `MISC` - `BONUS`,
		                `TRUCK PAY`),
		            PAY) AS 'TRUCK PAY MASTER',
		    IF(LPM.TRIP_ID = 0, `EXTRA STOP`, 0) AS 'EXTRA STOP MASTER',
		    IF(LPM.TRIP_ID = 0, `CASH ADVANCES`, 0) AS 'CASH ADVANCES MASTER',
		    IF(LPM.TRIP_ID = 0,`TROLLS`, 0) AS 'TOLLS MASTER',
		    `UPLOADING` AS 'UPLOADING MASTER',
		    IF(LPM.TRIP_ID = 0, `MISC`, 0) AS 'MISC MASTER',
		    IF(LPM.TRIP_ID = 0, `BONUS`, 0) AS 'BONUS MASTER',
		    IF(LPM.TRIP_ID = 0, `TOTAL MILES`, '') AS 'TOTAL MILES DETAIL',
		    IF(`TRUCK PAY` = 0, PAY, `TRUCK PAY`) AS 'TRUCK PAY DETAIL',
		    IF(LPM.TRIP_ID = 0, `TOTAL PAY`, `PAY`) AS `TOTAL PAY ROW`,
		    IF(LPM.TRIP_ID != 0, '', `EXTRA STOP`) AS 'EXTRA STOP DETAIL',
		    IF(LPM.TRIP_ID = 0, '', IF(TITLE IN ('Advances'), PAY, 0)) AS 'CASH ADVANCES DETAIL',
		    IF(LPM.TRIP_ID != 0, '', IF(TITLE IN ('Charges'), PAY, 0)) AS 'TOLLS DETAIL',
		    `UPLOADING` AS 'UPLOADING DETAIL',
		    IF(LPM.TRIP_ID != 0, '', IF(TITLE IN ('Other') AND LOAD_RATE_NAME NOT LIKE ('%BONUS%'),
		                PAY, 0)) AS 'MISC DETAIL',
		    IF(LPM.TRIP_ID != 0, '', IF(TITLE IN ('Other') AND LOAD_RATE_NAME LIKE ('%BONUS%'),
		                PAY, 0)) AS 'BONUS DETAIL',
		    Details.TITLE,
		    Details.QUANTITY,
		    Details.RATE,
		    Details.PAY,
		    Details.LOAD_RATE_NAME,
		    Details.DRIVER_ID,
		    AllHours.QHOURS,
		    AllHours.PHOURS
		FROM
		    (SELECT 
		        LPM.DRIVER_ID,
		            LPM.DRIVER_CODE,
		            LPM.DRIVER,
		            LPM.WEEKEND_FROM,
		            LPM.WEEKEND_TO,
		            GET_LOAD_ASOF(LPM.LOAD_ID) DATE,
		            LPM.TRIP_ID,
		            LOAD_OFFICE_NUM(LPM.LOAD_ID) AS SS_NUMBER,
		            IF(LPM.LOAD_ID,
		            	CONCAT(LPM.LOAD_ID, '/', LOAD_OFFICE_NUM(LPM.LOAD_ID)), '') AS LOAD_TRIP,
		            LPM.LOAD_ID 'LOAD',
		            TOTAL_MILES 'TOTAL MILES',
		            TOTAL_TRIP_PAY 'TOTAL PAY',
		            (IF((SELECT 
		                    IFNULL(SUM(exp_load_pay_rate.pay), 0)
		                FROM
		                    exp_load_pay_rate
		                WHERE
		                    LPM.load_id = exp_load_pay_rate.LOAD_ID
		                        AND LPM.DRIVER_ID = exp_load_pay_rate.DRIVER_ID
		                        AND exp_load_pay_rate.title IN ('Per Mile' , 'Hourly')) = 0, (SELECT 
		                    IFNULL(SUM(exp_load_pay_rate.pay), 0)
		                FROM
		                    exp_load_pay_rate
		                WHERE
		                    LPM.load_id = 0
		                        AND LPM.TRIP_ID = exp_load_pay_rate.TRIP_ID
		                        AND LPM.DRIVER_ID = exp_load_pay_rate.DRIVER_ID
		                        AND exp_load_pay_rate.title IN ('Per Mile' , 'Hourly')), (SELECT 
		                    IFNULL(SUM(exp_load_pay_rate.pay), 0)
		                FROM
		                    exp_load_pay_rate
		                WHERE
		                    LPM.load_id = exp_load_pay_rate.LOAD_ID
		                        AND LPM.DRIVER_ID = exp_load_pay_rate.DRIVER_ID
		                        AND exp_load_pay_rate.title IN ('Per Mile' , 'Hourly')))) AS 'TRUCK PAY',
		            (SELECT 
		                    IFNULL(SUM(exp_load_pay_rate.pay), 0)
		                FROM
		                    exp_load_pay_rate
		                WHERE
		                    IF(LPM.TRIP_ID = 0, LPM.LOAD_ID, LPM.TRIP_ID) = IF(LPM.TRIP_ID = 0, exp_load_pay_rate.LOAD_ID, exp_load_pay_rate.TRIP_ID)
		                        AND LPM.DRIVER_ID = exp_load_pay_rate.DRIVER_ID
		                        AND exp_load_pay_rate.title = 'Per Stop') AS 'EXTRA STOP',
		            (SELECT 
		                    IFNULL(SUM(exp_load_pay_rate.pay), 0)
		                FROM
		                    exp_load_pay_rate
		                WHERE
		                    LPM.load_id = exp_load_pay_rate.LOAD_ID
		                        AND LPM.TRIP_ID = exp_load_pay_rate.TRIP_ID
		                        AND LPM.DRIVER_ID = exp_load_pay_rate.DRIVER_ID
		                        AND exp_load_pay_rate.title = 'Advances') AS 'CASH ADVANCES',
		            (SELECT 
		                    IFNULL(SUM(exp_load_pay_rate.pay), 0)
		                FROM
		                    exp_load_pay_rate
		                WHERE
		                    IF(LPM.TRIP_ID = 0, LPM.LOAD_ID, LPM.TRIP_ID) = IF(exp_load_pay_rate.TRIP_ID = 0, exp_load_pay_rate.LOAD_ID, exp_load_pay_rate.TRIP_ID)
		                        AND LPM.DRIVER_ID = exp_load_pay_rate.DRIVER_ID
		                        AND exp_load_pay_rate.title = 'Charges') AS TROLLS,
		            0 UPLOADING,
		            (SELECT 
		                    IFNULL(SUM(exp_load_pay_rate.pay), 0)
		                FROM
		                    exp_load_pay_rate
		                WHERE
		                    IF(LPM.TRIP_ID = 0, LPM.LOAD_ID, LPM.TRIP_ID) = IF(exp_load_pay_rate.TRIP_ID = 0, exp_load_pay_rate.LOAD_ID, exp_load_pay_rate.TRIP_ID)
		                        AND LPM.DRIVER_ID = exp_load_pay_rate.DRIVER_ID
		                        AND exp_load_pay_rate.title IN ('Other')
		                        AND exp_load_pay_rate.LOAD_RATE_NAME NOT LIKE ('%BONUS%')) AS MISC,
		            (SELECT 
		                    IFNULL(SUM(exp_load_pay_rate.pay), 0)
		                FROM
		                    exp_load_pay_rate
		                WHERE
		                    IF(LPM.TRIP_ID = 0, LPM.LOAD_ID, LPM.TRIP_ID) = IF(exp_load_pay_rate.TRIP_ID = 0, exp_load_pay_rate.LOAD_ID, exp_load_pay_rate.TRIP_ID)
		                        AND LPM.DRIVER_ID = exp_load_pay_rate.DRIVER_ID
		                        AND exp_load_pay_rate.title IN ('Other')
		                        AND exp_load_pay_rate.LOAD_RATE_NAME LIKE ('%BONUS%')) AS BONUS,
		            (select min(ACTUAL_ARRIVE) from exp_stop
					where load_code = load_id) as 'Fist Arrive',
					(select max(ACTUAL_DEPART) from exp_stop
					where load_code = load_id) as 'Last Depart',
		            IFNULL(LPM.TRIP_ID, (SELECT 
		                    IFNULL(SUM(exp_load_pay_rate.pay), 0)
		                FROM
		                    exp_load_pay_rate
		                WHERE
		                    LPM.load_id = 0
		                        AND LPM.TRIP_ID = exp_load_pay_rate.TRIP_ID
		                        AND LPM.DRIVER_ID = exp_load_pay_rate.DRIVER_ID)) AS TRIP,
		            APPROVED_DATE Approved,
		            CONCAT(DATE_FORMAT(LPM.WEEKEND_FROM, '%m/%d/%Y'), ' TO ', DATE_FORMAT(LPM.WEEKEND_TO, '%m/%d/%Y')) NWEEK,
		            LOAD_ROUTE(LPM.LOAD_ID) AS description
		            
		    FROM LPM   -- NEW
		    
			LEFT JOIN exp_load ON LPM.LOAD_ID = exp_load.LOAD_CODE
		
		    WHERE
		        INSTR(IFNULL(LOAD_OFFICE_NUM(LPM.LOAD_ID), 9), '-') = 0
		        ".($driver != 'All' ? "AND LPM.DRIVER_ID = $driver" : "").") LPM
		        
		    LEFT JOIN (SELECT  LOAD_PAY_RATE_ID, TITLE, QUANTITY, RATE, PAY, LOAD_RATE_NAME,
				DRIVER_ID, LOAD_ID, TRIP_ID
		    FROM EXP_LOAD_PAY_RATE
		    WHERE PAY <> 0
			AND TRIP_ID <> 0
			".($driver != 'All' ? "AND DRIVER_ID = $driver" : "")."

			UNION SELECT  LOAD_PAY_RATE_ID, TITLE, QUANTITY, RATE, PAY, LOAD_RATE_NAME,
				DRIVER_ID, LOAD_ID, TRIP_ID
		    FROM EXP_LOAD_PAY_RATE
		    WHERE PAY <> 0
		    AND EXP_LOAD_PAY_RATE.TITLE = 'Advances'
			AND TRIP_ID <> 0
			".($driver != 'All' ? "AND DRIVER_ID = $driver" : "")."
				
		    UNION SELECT NULL LOAD_PAY_RATE_ID, NULL TITLE, NULL QUANTITY, NULL RATE,
		            MANUAL_RATE PAY, CONCAT(MANUAL_CODE, ' ', MANUAL_DESC) LOAD_RATE_NAME,
		            DRIVER_ID, LOAD_ID, TRIP_ID
		    FROM EXP_LOAD_MANUAL_RATE
		    WHERE TRIP_ID <> 0 AND MANUAL_RATE <> 0
		        ".($driver != 'All' ? "AND DRIVER_ID = $driver" : "").") Details
			ON LPM.DRIVER_ID = Details.DRIVER_ID
		        AND IF(LPM.TRIP_ID = 0, LPM.LOAD, LPM.TRIP_ID) =
		        	IF(LPM.TRIP_ID = 0, Details.LOAD_ID, Details.TRIP_ID)
			LEFT JOIN
		    (SELECT SUM(QUANTITY) AS QHOURS, SUM(PAY) PHOURS, DRIVER_ID, LOAD_ID, TRIP_ID
		    FROM EXP_LOAD_PAY_RATE
		    WHERE TRIP_ID = 0 AND PAY <> 0 AND TITLE = 'Hourly'
		    ".($driver != 'All' ? "AND DRIVER_ID = $driver" : "")."
		    
		    GROUP BY DRIVER_ID , LOAD_ID , TRIP_ID) AS AllHours ON LPM.DRIVER_ID = AllHours.DRIVER_ID
		        AND IF(LPM.TRIP_ID = 0,
		        LPM.LOAD,
		        LPM.TRIP_ID) = IF(LPM.TRIP_ID = 0,
		        AllHours.LOAD_ID,
		        AllHours.TRIP_ID)
		ORDER BY `LOAD` DESC");

		if( $this->debug ) {
			echo "<pre>";
			var_dump($pay);
			echo "</pre>";
		}
		
		return $pay;
	}
	
	//! Find out which version of MySQL
	private function is_mysql_ver8() {
		$version_check = $this->database->get_one_row("SELECT VERSION() AS VER");
		
		if($this->debug) {
			echo "<pre>is_mysql_ver8:\n";
			var_dump($version_check);
			echo "</pre>";
		}
		return is_array($version_check) && isset($version_check["VER"]) &&
			version_compare($version_check["VER"],"8.0.0") > 0;
	}

	//! SCR# 757 - Generate the report
    public function driver_pay2( $weekend_from, $weekend_to, $driver = 'All' ) {
    
		if( $this->is_mysql_ver8() ) {
			$pay = $this->database->get_multiple_rows("
		WITH DriverName AS
			(SELECT DRIVER_CODE, FIRST_NAME, MIDDLE_NAME, LAST_NAME,
		        CONCAT(FIRST_NAME, ' ', IFNULL(MIDDLE_NAME, ''), ' ', LAST_NAME,
		        	' (', IFNULL(DRIVER_NUMBER, CONCAT('Code:', driver_code)), ')') DRIVER
		    FROM exp_driver
		    WHERE
		        LAST_NAME IS NOT NULL
		        ".($driver != 'All' ? "AND DRIVER_CODE = $driver" : "")."
		        AND EXISTS (SELECT LOAD_PAY_ID FROM EXP_LOAD_PAY_MASTER
		        	WHERE DRIVER_ID = DRIVER_CODE
		        	LIMIT 1)
		),
		        	
		DPM AS
			(SELECT DRIVER_PAY_ID, LOAD_ID, TRIP_ID, DRIVER_ID, WEEKEND_FROM, WEEKEND_TO,
				TRIP_PAY, BONUS AS DBONUS, HANDLING, GROSS_EARNING, ADDED_ON,
		    DriverName.*
			FROM EXP_DRIVER_PAY_MASTER, DriverName
			WHERE DRIVER_ID = DriverName.DRIVER_CODE
		    AND FINALIZE_STATUS IN ('finalized' , 'paid')
			AND WEEKEND_FROM >= DATE('".$weekend_from."')
			AND WEEKEND_TO <= DATE('".$weekend_to."')
		),
		
		LPM AS
			(SELECT DPM.*, APPROVED_DATE, ODOMETER_FROM, ODOMETER_TO, TOTAL_MILES, BONUS,
		    APPLY_BONUS, BONUS_AMOUNT, TOTAL_TRIP_PAY, HANDLING_PALLET, HANDLING_PAY,
		    TOTAL_SETTLEMENT, LOADED_DET_HR, UNLOADED_DET_HR
		    FROM EXP_LOAD_PAY_MASTER, DPM
		    WHERE EXP_LOAD_PAY_MASTER.DRIVER_ID = DPM.DRIVER_CODE
		    AND DPM.DRIVER_PAY_ID = LOAD_PAY_ID
		),
		
		DETAIL AS
			(SELECT LPM.*, LOAD_PAY_RATE_ID AS RATE_ID, TITLE, QUANTITY, RATE, PAY, LOAD_RATE_NAME
		    FROM EXP_LOAD_PAY_RATE LPR, LPM
		    WHERE TRIP_PAY != 0
		    AND LPR.DRIVER_ID = LPM.DRIVER_ID
            AND IF(LPM.TRIP_ID = 0, LPM.LOAD_ID = LPR.LOAD_ID, LPM.TRIP_ID = LPR.TRIP_ID)
				
		    UNION SELECT LPM.*, LOAD_MANUAL_ID AS RATE_ID, 'Manual' TITLE, NULL QUANTITY, MANUAL_RATE RATE,
		            MANUAL_RATE PAY, CONCAT(MANUAL_CODE, ' ', MANUAL_DESC) LOAD_RATE_NAME
		    FROM EXP_LOAD_MANUAL_RATE LMR, LPM
		    WHERE MANUAL_RATE != 0
		    AND LMR.DRIVER_ID = LPM.DRIVER_ID
            AND IF(LPM.TRIP_ID = 0, LPM.LOAD_ID = LMR.LOAD_ID, LPM.TRIP_ID = LMR.TRIP_ID)
 
 		    UNION SELECT LPM.*, LOAD_RANGE_ID AS RATE_ID, 'Range' TITLE, 1 QUANTITY, RANGE_RATE RATE,
		            TOTAL_RATE PAY, CONCAT(RANGE_CODE, ' ', RANGE_NAME) LOAD_RATE_NAME
		    FROM EXP_LOAD_RANGE_RATE LRR, LPM
		    WHERE TOTAL_RATE != 0
		    AND LRR.DRIVER_ID = LPM.DRIVER_ID
            AND IF(LPM.TRIP_ID = 0, LPM.LOAD_ID = LRR.LOAD_ID, LPM.TRIP_ID = LRR.TRIP_ID)
		),
		                
		FILTERED AS
        (SELECT DRIVER_ID, LOAD_ID, TRIP_ID, DRIVER, WEEKEND_FROM, WEEKEND_TO, RATE_ID,
			GET_LOAD_ASOF(LOAD_ID) LOAD_DATE,
			LOAD_OFFICE_NUM(LOAD_ID) AS SS_NUMBER,
			IF(LOAD_ID,
            	CONCAT(LOAD_ID, '/', LOAD_OFFICE_NUM(LOAD_ID)),
            	IF(TITLE = 'Advances', 'Advances', '')) AS LOAD_TRIP,
            IF(TITLE = 'Hourly', QUANTITY, 0) AS HOURS,
            IF(TITLE IN ('Per Mile', 'Per Mile + Rate', 'Line haul percentage', 'Hourly', 'Range', 'Empty Miles', 'Loaded Miles'), TOTAL_MILES, 0) AS MILES,
            IF(TITLE IN ('Per Mile', 'Per Mile + Rate', 'Line haul percentage', 'Hourly', 'Range', 'Empty Miles', 'Loaded Miles', 'Per Pick Up', 'Per Drop', 'Shipper total percentage', 'Client Rate Percentage', 'Flat Rate'), PAY, 0) AS TRUCK_PAY,
            IF(TITLE = 'Per Stop', PAY, 0) AS EXTRA_STOP,
            IF(TITLE = 'Advances', PAY, 0) AS ADVANCES,
            IF(TRIP_ID = 0 && TITLE = 'Charges',PAY, 0) AS TOLLS,
		    IF((TITLE IN ('Other', 'Manual', 'Reimbursement', 'Holiday/Weekend', 'Per Pallet', 'Layover', 'Fuel shipper percentage', 'Extra charge percentage', 'Fuel Surcharge Percentage', 'Fuel Surcharge Per Mile') OR (TRIP_ID > 0 AND TITLE = 'Charges'))
            	AND LOAD_RATE_NAME NOT LIKE '%BONUS%', PAY, 0) AS MISC,
            HANDLING_PAY AS HANDLING,
            IF(TITLE='Other' AND LOAD_RATE_NAME LIKE '%BONUS%', PAY, 0) AS BONUS,
            IF(TRIP_ID = 0, DBONUS, 0) AS DBONUS,
            TOTAL_TRIP_PAY, TITLE, LOAD_RATE_NAME,
            IF(LOAD_ID,LOAD_ROUTE(LOAD_ID),'') AS ROUTE_DESCRIPTION
                        
        FROM DETAIL
        WHERE PAY != 0)
        
        SELECT DRIVER_ID, LOAD_ID, TRIP_ID, DRIVER, WEEKEND_FROM, WEEKEND_TO,
        	LOAD_DATE, SS_NUMBER, LOAD_TRIP, ROUTE_DESCRIPTION, LOAD_RATE_NAME, NULL RATE_ID,
			SUM(HOURS) AS HOURS, SUM(MILES) AS MILES,
			SUM(TRUCK_PAY) AS TRUCK_PAY, SUM(EXTRA_STOP) AS EXTRA_STOP,
            SUM(ADVANCES) AS ADVANCES, SUM(TOLLS) AS TOLLS, SUM(MISC) + HANDLING AS MISC,
            SUM(BONUS) + DBONUS AS BONUS,
            SUM(TRUCK_PAY + EXTRA_STOP + ADVANCES + TOLLS + MISC + BONUS) + HANDLING + DBONUS AS ROW_TOTAL
        FROM FILTERED
        WHERE LOAD_ID > 0
        GROUP BY DRIVER_ID, LOAD_ID, TRIP_ID, DRIVER, WEEKEND_FROM, WEEKEND_TO,
        	LOAD_DATE, SS_NUMBER, LOAD_TRIP, ROUTE_DESCRIPTION, LOAD_RATE_NAME
        
        UNION SELECT DRIVER_ID, LOAD_ID, TRIP_ID, DRIVER, WEEKEND_FROM, WEEKEND_TO, LOAD_DATE, SS_NUMBER, LOAD_TRIP, ROUTE_DESCRIPTION, LOAD_RATE_NAME, RATE_ID,
			HOURS,  MILES, TRUCK_PAY, EXTRA_STOP, ADVANCES,
			TOLLS, MISC + HANDLING AS MISC, BONUS + DBONUS AS BONUS,
            TRUCK_PAY + EXTRA_STOP + ADVANCES + TOLLS + MISC + HANDLING + BONUS AS ROW_TOTAL
        FROM FILTERED
        WHERE TRIP_ID > 0
        ORDER BY DRIVER_ID ASC, WEEKEND_FROM ASC, LOAD_DATE DESC
		");
		} else {
			$pay = $this->database->multi_query("
		DROP TEMPORARY TABLE IF EXISTS DriverName;
		DROP TEMPORARY TABLE IF EXISTS DPM;
		DROP TEMPORARY TABLE IF EXISTS LPM;
		DROP TEMPORARY TABLE IF EXISTS LPM2;
		DROP TEMPORARY TABLE IF EXISTS LPM3;
		DROP TEMPORARY TABLE IF EXISTS DETAIL;
		DROP TEMPORARY TABLE IF EXISTS FILTERED;
		DROP TEMPORARY TABLE IF EXISTS FILTERED2;
		
		CREATE TEMPORARY TABLE DriverName
			SELECT DRIVER_CODE, FIRST_NAME, MIDDLE_NAME, LAST_NAME,
		        CONCAT(FIRST_NAME, ' ', IFNULL(MIDDLE_NAME, ''), ' ', LAST_NAME,
		        	' (', IFNULL(DRIVER_NUMBER, CONCAT('Code:', driver_code)), ')') DRIVER
		    FROM exp_driver
		    WHERE
		        LAST_NAME IS NOT NULL
		        ".($driver != 'All' ? "AND DRIVER_CODE = $driver" : "")."
		        AND EXISTS (SELECT LOAD_PAY_ID FROM EXP_LOAD_PAY_MASTER
		        	WHERE DRIVER_ID = DRIVER_CODE
		        	LIMIT 1);
		
		CREATE TEMPORARY TABLE DPM
			SELECT DRIVER_PAY_ID, LOAD_ID, TRIP_ID, DRIVER_ID, WEEKEND_FROM, WEEKEND_TO,
						TRIP_PAY, BONUS AS DBONUS, HANDLING, GROSS_EARNING, ADDED_ON,
				    DriverName.*
			FROM EXP_DRIVER_PAY_MASTER, DriverName
			WHERE DRIVER_ID = DriverName.DRIVER_CODE
		    AND FINALIZE_STATUS IN ('finalized' , 'paid')
			AND WEEKEND_FROM >= DATE('".$weekend_from."')
			AND WEEKEND_TO <= DATE('".$weekend_to."');
		
		CREATE TEMPORARY TABLE LPM
			SELECT DPM.*, APPROVED_DATE, ODOMETER_FROM, ODOMETER_TO, TOTAL_MILES, BONUS,
			    APPLY_BONUS, BONUS_AMOUNT, TOTAL_TRIP_PAY, HANDLING_PALLET, HANDLING_PAY,
			    TOTAL_SETTLEMENT, LOADED_DET_HR, UNLOADED_DET_HR
		    FROM EXP_LOAD_PAY_MASTER, DPM
		    WHERE EXP_LOAD_PAY_MASTER.DRIVER_ID = DPM.DRIVER_CODE
		    AND DPM.DRIVER_PAY_ID = LOAD_PAY_ID;
		
		CREATE TEMPORARY TABLE LPM2
			SELECT * FROM LPM;
			
		CREATE TEMPORARY TABLE LPM3
			SELECT * FROM LPM;
			
		CREATE TEMPORARY TABLE DETAIL
			SELECT LPM.*, LOAD_PAY_RATE_ID AS RATE_ID, TITLE, QUANTITY, RATE, PAY, LOAD_RATE_NAME
		    FROM EXP_LOAD_PAY_RATE LPR, LPM
		    WHERE TRIP_PAY != 0
		    AND LPR.DRIVER_ID = LPM.DRIVER_ID
		    AND IF(LPM.TRIP_ID = 0, LPM.LOAD_ID = LPR.LOAD_ID, LPM.TRIP_ID = LPR.TRIP_ID)
				
		    UNION SELECT LPM2.*, LOAD_MANUAL_ID AS RATE_ID, 'Manual' TITLE, NULL QUANTITY, MANUAL_RATE RATE,
		            MANUAL_RATE PAY, CONCAT(MANUAL_CODE, ' ', MANUAL_DESC) LOAD_RATE_NAME
		    FROM EXP_LOAD_MANUAL_RATE LMR, LPM2
		    WHERE MANUAL_RATE != 0
		    AND LMR.DRIVER_ID = LPM2.DRIVER_ID
		    AND IF(LPM2.TRIP_ID = 0, LPM2.LOAD_ID = LMR.LOAD_ID, LPM2.TRIP_ID = LMR.TRIP_ID)
		
			    UNION SELECT LPM3.*, LOAD_RANGE_ID AS RATE_ID, 'Range' TITLE, 1 QUANTITY, RANGE_RATE RATE,
		            TOTAL_RATE PAY, CONCAT(RANGE_CODE, ' ', RANGE_NAME) LOAD_RATE_NAME
		    FROM EXP_LOAD_RANGE_RATE LRR, LPM3
		    WHERE TOTAL_RATE != 0
		    AND LRR.DRIVER_ID = LPM3.DRIVER_ID
		    AND IF(LPM3.TRIP_ID = 0, LPM3.LOAD_ID = LRR.LOAD_ID, LPM3.TRIP_ID = LRR.TRIP_ID);
		
		CREATE TEMPORARY TABLE FILTERED
			SELECT DRIVER_ID, LOAD_ID, TRIP_ID, DRIVER, WEEKEND_FROM, WEEKEND_TO, RATE_ID,
				GET_LOAD_ASOF(LOAD_ID) LOAD_DATE,
				LOAD_OFFICE_NUM(LOAD_ID) AS SS_NUMBER,
				IF(LOAD_ID,
		        	CONCAT(LOAD_ID, '/', LOAD_OFFICE_NUM(LOAD_ID)),
		        	IF(TITLE = 'Advances', 'Advances', '')) AS LOAD_TRIP,
		        IF(TITLE = 'Hourly', QUANTITY, 0) AS HOURS,
	            IF(TITLE IN ('Per Mile', 'Per Mile + Rate', 'Line haul percentage', 'Hourly', 'Range', 'Empty Miles', 'Loaded Miles'), TOTAL_MILES, 0) AS MILES,
	            IF(TITLE IN ('Per Mile', 'Per Mile + Rate', 'Line haul percentage', 'Hourly', 'Range', 'Empty Miles', 'Loaded Miles', 'Per Pick Up', 'Per Drop', 'Shipper total percentage', 'Client Rate Percentage', 'Flat Rate'), PAY, 0) AS TRUCK_PAY,
		        IF(TITLE = 'Per Stop', PAY, 0) AS EXTRA_STOP,
		        IF(TITLE = 'Advances', PAY, 0) AS ADVANCES,
		        IF(TRIP_ID = 0 && TITLE = 'Charges',PAY, 0) AS TOLLS,
		        IF((TITLE IN ('Other', 'Manual', 'Reimbursement', 'Holiday/Weekend', 'Per Pallet', 'Layover', 'Fuel shipper percentage', 'Extra charge percentage', 'Fuel Surcharge Percentage', 'Fuel Surcharge Per Mile') OR (TRIP_ID > 0 AND TITLE = 'Charges'))
		        	AND LOAD_RATE_NAME NOT LIKE '%BONUS%', PAY, 0) AS MISC,
		        HANDLING_PAY AS HANDLING,
		        IF(TITLE='Other' AND LOAD_RATE_NAME LIKE '%BONUS%', PAY, 0) AS BONUS,
		        IF(TRIP_ID = 0, DBONUS, 0) AS DBONUS,
		        TOTAL_TRIP_PAY, TITLE, LOAD_RATE_NAME,
		        IF(LOAD_ID,LOAD_ROUTE(LOAD_ID),'') AS ROUTE_DESCRIPTION
		                        
			FROM DETAIL
			WHERE PAY != 0;
		
		CREATE TEMPORARY TABLE FILTERED2
			SELECT * FROM FILTERED;
		
		SELECT DRIVER_ID, LOAD_ID, TRIP_ID, DRIVER, WEEKEND_FROM, WEEKEND_TO,
			LOAD_DATE, SS_NUMBER, LOAD_TRIP, ROUTE_DESCRIPTION, LOAD_RATE_NAME, NULL RATE_ID,
			SUM(HOURS) AS HOURS, SUM(MILES) AS MILES,
			SUM(TRUCK_PAY) AS TRUCK_PAY, SUM(EXTRA_STOP) AS EXTRA_STOP,
		    SUM(ADVANCES) AS ADVANCES, SUM(TOLLS) AS TOLLS, SUM(MISC) + HANDLING AS MISC,
		    SUM(BONUS) + DBONUS AS BONUS,
		    SUM(TRUCK_PAY + EXTRA_STOP + ADVANCES + TOLLS + MISC + BONUS) + HANDLING + DBONUS AS ROW_TOTAL
		FROM FILTERED
		WHERE LOAD_ID > 0
		GROUP BY DRIVER_ID, LOAD_ID, TRIP_ID, DRIVER, WEEKEND_FROM, WEEKEND_TO,
			LOAD_DATE, SS_NUMBER, LOAD_TRIP, ROUTE_DESCRIPTION, LOAD_RATE_NAME
		
		UNION SELECT DRIVER_ID, LOAD_ID, TRIP_ID, DRIVER, WEEKEND_FROM, WEEKEND_TO, LOAD_DATE, SS_NUMBER, LOAD_TRIP, ROUTE_DESCRIPTION, LOAD_RATE_NAME, RATE_ID,
			HOURS,  MILES, TRUCK_PAY, EXTRA_STOP, ADVANCES,
			TOLLS, MISC + HANDLING AS MISC, BONUS + DBONUS AS BONUS,
		    TRUCK_PAY + EXTRA_STOP + ADVANCES + TOLLS + MISC + HANDLING + BONUS AS ROW_TOTAL
		FROM FILTERED2
		WHERE TRIP_ID > 0
		ORDER BY DRIVER_ID ASC, WEEKEND_FROM ASC, LOAD_DATE DESC;
		");
		}

		if( $this->debug ) {
			echo "<pre>";
			var_dump($pay);
			echo "</pre>";
		}
		
		return $pay;
	}


}



?>
