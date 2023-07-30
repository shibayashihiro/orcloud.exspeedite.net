<?php

// no direct access
defined('_STS_INCLUDE') or die('Restricted access');

require_once( "sts_table_class.php" );

class sts_vacation extends sts_table {

	// Constructor does not need the table name
	public function __construct( $database, $debug = false ) {

		$this->debug = $debug;
		$this->primary_key = "VACATION_CODE";
		if( $this->debug ) echo "<p>Create sts_vacation</p>";
		parent::__construct( $database, VACATION_TABLE, $debug);
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

	public function working_days( $date1_string, $date2_string ) {
	
		if( $this->debug ) echo "<p>sts_vacation > working_days: $date1_string, $date2_string</p>";
		
		$date1 = strtotime($date1_string);
		$date2 = strtotime($date2_string);
		
		$days = ($date2 - $date1) / 86400 + 1;
		
		if( $this->debug ) echo "<p>sts_vacation > working_days: raw_diff = $days</p>";
	
	    $no_full_weeks = floor($days / 7);
	    $no_remaining_days = fmod($days, 7);
	
	    //It will return 1 if it's Monday,.. ,7 for Sunday
	    $the_first_day_of_week = date("N", $date1);
	    $the_last_day_of_week = date("N", $date2);

	    //---->The two can be equal in leap years when february has 29 days, the equal sign is added here
	    //In the first case the whole interval is within a week, in the second case the interval falls in two weeks.
	    if ($the_first_day_of_week <= $the_last_day_of_week) {
	        if ($the_first_day_of_week <= 6 && 6 <= $the_last_day_of_week) $no_remaining_days--;
	        if ($the_first_day_of_week <= 7 && 7 <= $the_last_day_of_week) $no_remaining_days--;
	    }
	    else {
	        // (edit by Tokes to fix an edge case where the start day was a Sunday
	        // and the end day was NOT a Saturday)
	
	        // the day of the week for start is later than the day of the week for end
	        if ($the_first_day_of_week == 7) {
	            // if the start date is a Sunday, then we definitely subtract 1 day
	            $no_remaining_days--;
	
	            if ($the_last_day_of_week == 6) {
	                // if the end date is a Saturday, then we subtract another day
	                $no_remaining_days--;
	            }
	        }
	        else {
	            // the start date was a Saturday (or earlier), and the end date was (Mon..Fri)
	            // so we skip an entire weekend and subtract 2 days
	            $no_remaining_days -= 2;
	        }
	    }
	
	    //The no. of business days is: (number of weeks between the two dates) * (5 working days) + the remainder
	//---->february in none leap years gave a remainder of 0 but still calculated weekends between first and last day, this is one way to fix it
	   $workingDays = $no_full_weeks * 5;
	    if ($no_remaining_days > 0 )
	    {
	      $workingDays += $no_remaining_days;
	    }
	
	    //We subtract the holidays
		$holidays = $this->database->get_multiple_rows("SELECT count(*) NUM_HOLIDAYS
			FROM forl1327_exspeed.exp_holidays
			WHERE HOLIDAY_DATE BETWEEN '".date("Y-m-d", $date1)."' 
				AND '".date("Y-m-d", $date2)."'");
				
		if( is_array($holidays) && count($holidays) == 1 && isset($holidays[0]['NUM_HOLIDAYS']) ) {
			if( $this->debug ) echo "<p>sts_vacation > working_days: ".$holidays[0]['NUM_HOLIDAYS']." holidays</p>";
			$workingDays -= $holidays[0]['NUM_HOLIDAYS'];
		}

		if( $this->debug ) echo "<p>sts_vacation > working_days: return $workingDays days.</p>";
	    return intval($workingDays);
	}

	public function check_available( $driver, $date1_string, $date2_string ) {

		$date1 = strtotime($date1_string);
		$date2 = strtotime($date2_string);
		$result = true;

		$matches = $this->fetch_rows( "STAFF_CODE = ".$driver." AND (
			START_DATE BETWEEN '".date("Y-m-d", $date1)."' AND '".date("Y-m-d", $date2)."' OR
			END_DATE BETWEEN '".date("Y-m-d", $date1)."' AND '".date("Y-m-d", $date2)."' OR
			'".date("Y-m-d", $date1)."' BETWEEN START_DATE AND END_DATE OR
			'".date("Y-m-d", $date2)."' BETWEEN START_DATE AND END_DATE
			)",
				"VACATION_CODE" );
		if( isset($matches) && is_array($matches) && count($matches) > 0 )
			$result = false;
		
		return $result;
	}

}

class sts_vacation_out extends sts_vacation {

	// Constructor does not need the table name
	public function __construct( $database, $debug = false ) {

		if( $debug ) echo "<p>Create sts_vacation_out</p>";
		parent::__construct( $database, $debug);
	}

	// Fetch one or more rows
	public function fetch_rows( $match = "", $fields = "*", $order = "", $limit = "", $groupby = "", $match2 = "" ) {
		if( $this->debug ) echo "<p>fetch_rows $match</p>";
		
		$result = $this->database->get_multiple_rows("SELECT $fields FROM
			(SELECT WE, STAFF_CODE, DRIVER_NAME, VACATION_TYPE, SUM(NUM_DAYS) AS WE_DAYS
			FROM
				(SELECT DATE(START_DATE + INTERVAL (7 - DAYOFWEEK(START_DATE)) DAY) AS WE,
				STAFF_CODE,
				(SELECT concat_ws( ' ', FIRST_NAME , LAST_NAME ) AS DRIVER_NAME
					FROM EXP_DRIVER
					WHERE STAFF_CODE = DRIVER_CODE) AS DRIVER_NAME,
				VACATION_TYPE, NUM_DAYS
				FROM EXP_VACATION
				WHERE YEAR(START_DATE) = YEAR(CURRENT_DATE)
				".($match <> "" ? "AND $match" : "").") AS VAC
			
			GROUP BY WE, STAFF_CODE, DRIVER_NAME, VACATION_TYPE
			ORDER BY WE, STAFF_CODE, DRIVER_NAME, VACATION_TYPE) AS VAC2
			" );

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

$sts_form_addvacation_form = array(	//! sts_form_addvacation_form
	'title' => '<img src="images/vacation_icon.png" alt="vacation_icon" height="24"> Add Vacation',
	'action' => 'exp_addvacation.php',
	//'actionextras' => 'disabled',
	'cancel' => 'exp_listvacation.php',
	'name' => 'addvacation',
	'okbutton' => 'Save Changes',
	'saveadd' => 'Add Another',
	'cancelbutton' => 'Cancel',
		'layout' => '
	<div class="form-group">
		<div class="col-sm-4">
			<div class="form-group">
				<label for="STAFF_CODE" class="col-sm-4 control-label">#STAFF_CODE#</label>
				<div class="col-sm-8">
					%STAFF_CODE%
				</div>
			</div>
			<div class="form-group">
				<label for="VACATION_TYPE" class="col-sm-4 control-label">#VACATION_TYPE#</label>
				<div class="col-sm-8">
					%VACATION_TYPE%
				</div>
			</div>
			<div class="form-group">
				<label for="PLANNED" class="col-sm-4 control-label">#PLANNED#</label>
				<div class="col-sm-8">
					%PLANNED%
				</div>
			</div>
			<div class="form-group">
				<label for="APPROVED" class="col-sm-4 control-label">#APPROVED#</label>
				<div class="col-sm-8">
					%APPROVED%
				</div>
			</div>
		</div>
		<div class="col-sm-4">
			<div class="form-group">
				<label for="START_DATE" class="col-sm-4 control-label">#START_DATE#</label>
				<div class="col-sm-8">
					%START_DATE%
				</div>
			</div>
			<div class="form-group">
				<label for="END_DATE" class="col-sm-4 control-label">#END_DATE#</label>
				<div class="col-sm-8">
					%END_DATE%
				</div>
			</div>
			<div class="form-group">
				<label for="NUM_DAYS" class="col-sm-4 control-label">#NUM_DAYS#</label>
				<div class="col-sm-8">
					%NUM_DAYS%
				</div>
			</div>
		</div>
	</div>
	<div class="form-group">
		<div class="col-sm-8">
			<div class="form-group">
				<label for="COMMENTS" class="col-sm-2 control-label">#COMMENTS#</label>
				<div class="col-sm-10">
					%COMMENTS%
				</div>
			</div>
		</div>
	</div>
	
	'
);

$sts_form_editvacation_form = array(
	'title' => '<img src="images/vacation_icon.png" alt="vacation_icon" height="24"> Edit Vacation',
	'action' => 'exp_editvacation.php',
	//'actionextras' => 'disabled',
	'cancel' => 'exp_listvacation.php',
	'name' => 'editvacation',
	'okbutton' => 'Save Changes to Vacation',
	'cancelbutton' => 'Back to Vacations',
		'layout' => '
		%VACATION_CODE%
	<div class="form-group">
		<div class="col-sm-4">
			<div class="form-group">
				<label for="STAFF_CODE" class="col-sm-4 control-label">#STAFF_CODE#</label>
				<div class="col-sm-8">
					%STAFF_CODE%
				</div>
			</div>
			<div class="form-group">
				<label for="VACATION_TYPE" class="col-sm-4 control-label">#VACATION_TYPE#</label>
				<div class="col-sm-8">
					%VACATION_TYPE%
				</div>
			</div>
			<div class="form-group">
				<label for="PLANNED" class="col-sm-4 control-label">#PLANNED#</label>
				<div class="col-sm-8">
					%PLANNED%
				</div>
			</div>
			<div class="form-group">
				<label for="APPROVED" class="col-sm-4 control-label">#APPROVED#</label>
				<div class="col-sm-8">
					%APPROVED%
				</div>
			</div>
		</div>
		<div class="col-sm-4">
			<div class="form-group">
				<label for="START_DATE" class="col-sm-4 control-label">#START_DATE#</label>
				<div class="col-sm-8">
					%START_DATE%
				</div>
			</div>
			<div class="form-group">
				<label for="END_DATE" class="col-sm-4 control-label">#END_DATE#</label>
				<div class="col-sm-8">
					%END_DATE%
				</div>
			</div>
			<div class="form-group">
				<label for="NUM_DAYS" class="col-sm-4 control-label">#NUM_DAYS#</label>
				<div class="col-sm-8">
					%NUM_DAYS%
				</div>
			</div>
		</div>
	</div>
	<div class="form-group">
		<div class="col-sm-8">
			<div class="form-group">
				<label for="COMMENTS" class="col-sm-2 control-label">#COMMENTS#</label>
				<div class="col-sm-10">
					%COMMENTS%
				</div>
			</div>
		</div>
	</div>
	
	'
);

//! Field Specifications - For use with sts_form

$sts_form_add_vacation_fields = array(
	//'VACATION_CODE' => array( 'format' => 'hidden' ),
	'STAFF_CODE' => array( 'label' => 'Driver', 'format' => 'table',
		'table' => DRIVER_TABLE, 'key' => 'DRIVER_CODE', 'fields' => 'FIRST_NAME,LAST_NAME',
		'condition' => 'ISACTIVE <> \'Inactive\'' ),
	'STAFF_SOURCE' => array( 'format' => 'hidden', 'value' => 'driver' ),
	'VACATION_TYPE' => array( 'label' => 'Type', 'format' => 'enum' ),
	'START_DATE' => array( 'label' => 'Start', 'format' => 'date', 'extras' => 'required' ),
	'END_DATE' => array( 'label' => 'End', 'format' => 'date', 'extras' => 'required' ),
	'NUM_DAYS' => array( 'label' => 'Days', 'format' => 'number', 'align' => 'right', 'extras' => 'readonly' ),
	'PLANNED' => array( 'label' => 'Planned', 'format' => 'bool2', 'value' => true ),
	'APPROVED' => array( 'label' => 'Approved', 'format' => 'bool2', 'value' => true ),
	'COMMENTS' => array( 'label' => 'Comments', 'format' => 'text' ),
);

$sts_form_edit_vacation_fields = array(
	'VACATION_CODE' => array( 'format' => 'hidden' ),
	'STAFF_CODE' => array( 'label' => 'Driver', 'format' => 'table',
		'table' => DRIVER_TABLE, 'key' => 'DRIVER_CODE', 'fields' => 'FIRST_NAME,LAST_NAME',
		'condition' => 'ISACTIVE <> \'Inactive\'' ),
	'STAFF_SOURCE' => array( 'format' => 'hidden', 'value' => 'driver' ),
	'VACATION_TYPE' => array( 'label' => 'Type', 'format' => 'enum' ),
	'START_DATE' => array( 'label' => 'Start', 'format' => 'date', 'extras' => 'required' ),
	'END_DATE' => array( 'label' => 'End', 'format' => 'date', 'extras' => 'required' ),
	'NUM_DAYS' => array( 'label' => 'Days', 'format' => 'number', 'align' => 'right', 'extras' => 'readonly' ),
	'PLANNED' => array( 'label' => 'Planned', 'format' => 'bool2' ),
	'APPROVED' => array( 'label' => 'Approved', 'format' => 'bool2' ),
	'COMMENTS' => array( 'label' => 'Comments', 'format' => 'text' ),
);

//! Layout Specifications - For use with sts_result

$sts_result_vacation_layout = array(
	'VACATION_CODE' => array( 'format' => 'hidden' ),
	'STAFF_CODE' => array( 'label' => 'Driver', 'format' => 'table',
		'table' => DRIVER_TABLE, 'key' => 'DRIVER_CODE', 'fields' => 'concat_ws(\' \',FIRST_NAME,LAST_NAME) NAME', 'condition' => 'ISACTIVE <> \'Inactive\'' ),
	'STAFF_SOURCE' => array( 'format' => 'hidden', 'value' => 'driver' ),
	'VACATION_TYPE' => array( 'label' => 'Type', 'format' => 'text' ),
	'START_DATE' => array( 'label' => 'Start', 'format' => 'date' ),
	'END_DATE' => array( 'label' => 'End', 'format' => 'date' ),
	'NUM_DAYS' => array( 'label' => 'Days', 'format' => 'num0', 'align' => 'right' ),
	'PLANNED' => array( 'label' => 'Planned', 'format' => 'bool' ),
	'APPROVED' => array( 'label' => 'Approved', 'format' => 'bool' ),
	//'COMMENTS' => array( 'label' => 'Comments', 'format' => 'text' ),
	
	'CREATED_DATE' => array( 'label' => 'Created', 'format' => 'datetime' ),
	'CREATED_BY' => array( 'label' => 'Created By', 'format' => 'table',
		'table' => USER_TABLE, 'key' => 'USER_CODE', 'fields' => 'USERNAME' ),
	//'CHANGED_DATE' => array( 'label' => 'Changed', 'format' => 'datetime' ),
	//'CHANGED_BY' => array( 'label' => 'Changed By', 'format' => 'table',
	//	'table' => USER_TABLE, 'key' => 'USER_CODE', 'fields' => 'USERNAME' )

);

//! Edit/Delete Button Specifications - For use with sts_result

$sts_result_vacation_edit = array(
	'title' => '<img src="images/vacation_icon.png" alt="vacation_icon" height="24"> Vacations',
	'sort' => 'CREATED_DATE asc',
	'cancel' => 'index.php',
	'add' => 'exp_addvacation.php',
	//'actionextras' => 'disabled',
	'addbutton' => 'Add Vacation',
	'addpaybutton' => 'Report',
	'addpaylink' => 'exp_listvacation_out.php',
	'cancelbutton' => 'Back',
	'rowbuttons' => array(
		array( 'url' => 'exp_editvacation.php?CODE=', 'key' => 'VACATION_CODE', 'label' => 'VACATION_CODE', 'tip' => 'Edit vacation ', 'icon' => 'glyphicon glyphicon-edit' ),
		array( 'url' => 'exp_deletevacation.php?CODE=', 'key' => 'VACATION_CODE', 'label' => 'VACATION_CODE', 'tip' => 'Delete vacation ', 'icon' => 'glyphicon glyphicon-trash', 'confirm' => 'yes' )

	)
);


?>
