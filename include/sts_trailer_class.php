<?php

// $Id: sts_trailer_class.php 4350 2021-03-02 19:14:52Z duncan $
// Trailer class, all activity related to trailers.

// no direct access
defined('_STS_INCLUDE') or die('Restricted access');

require_once( "sts_config.php" );
require_once( "sts_table_class.php" );
require_once( "sts_setting_class.php" );
require_once( "sts_insp_report_class.php" );

class sts_trailer extends sts_table {

	private $setting_table;
	private $expire_trailers;
	private $inspection_reports;
	private $report_table;
	private $default_active;

	// Constructor does not need the table name
	public function __construct( $database, $debug = false ) {

		$this->debug = $debug;
		$this->primary_key = "TRAILER_CODE";
		if( $this->debug ) echo "<p>Create sts_trailer</p>";
		parent::__construct( $database, TRAILER_TABLE, $debug);
		$this->setting_table = sts_setting::getInstance($database, $debug);
		$this->expire_trailers = $this->setting_table->get( 'option', 'EXPIRE_TRAILERS_ENABLED' ) == 'true';
		$this->inspection_reports = $this->setting_table->get( 'option', 'INSPECTION_REPORTS' ) == 'true';
		if( $this->inspection_reports )
			$this->report_table = sts_insp_report::getInstance($database, $debug);
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
    
    //! SCR# 643 - Add trailer for a carrier
    public function add_carrier_trailer( $unit ) {
		if( $this->debug ) echo "<p>".__METHOD__.": unit = $unit</p>";

	    $year = date('Y');
	    $expire = date('Y-m-d',strtotime('+30 days'));
	    
	    $fields = array(
			'UNIT_NUMBER' => $unit,
			'OWNERSHIP_TYPE' => 'carrier',
			'ISACTIVE' => 'Active',
			'ISDELETED' => 'false',
			'TRAILER_YEAR' => $year,
			'INSPECTION_EXPIRY' => $expire,
			'INSURANCE_EXPIRY' => $expire,
			'TRAILER_TYPE' => 'Unknown',
			'MAKE' => 'Unknown',
			'TRAILER_DESCRIPTION' => 'Created by add_carrier_trailer'
	    );
	    
	    $result = $this->add( $fields );
	    
	    return $result;
    }

	public function suggest( $load, $query ) {

		if( $this->debug ) echo "<p>suggest $query</p>";
		$result = $this->fetch_rows( "UNIT_NUMBER like '".$query."%'
			AND ISACTIVE = 'Active' AND ISDELETED = false
			".($this->expire_trailers ? "AND TRAILER_EXPIRED(TRAILER_CODE) <> 'red'" : ""),
			"TRAILER_CODE AS RESOURCE_CODE, 
			UNIT_NUMBER AS RESOURCE_NAME,
			CONCAT_WS(', ',OWNERSHIP_TYPE,TRAILER_YEAR,MAKE) AS RESOURCE_EXTRA", "", "0, 20" );
		return $result;
	}
	
	//! Find matching trailers for a given load
	// Used for modal display on adding resources screen 
	// See also exp_pick_asset.php
	public function matching( $load ) {

		$output = '';
		if( $this->debug ) echo "<p>".__METHOD__.": load = $load</p>";
		
		$trailers = $this->fetch_rows( "ISACTIVE = 'Active' AND ISDELETED = false", 
			"TRAILER_CODE AS RESOURCE_CODE, 
			UNIT_NUMBER AS RESOURCE_NAME,
			OWNERSHIP_TYPE,TRAILER_OWNER,TRAILER_YEAR,MAKE, TRAILER_TYPE, OUTSIDE_LENGTH,
			".($this->expire_trailers ? "TRAILER_EXPIRED(TRAILER_CODE)" : "'green'")." AS ISEXPIRED", "RESOURCE_NAME ASC" );
		
		if( is_array($trailers) && count($trailers) > 0) {
			$output .= '<div class="table-responsive">
			<table class="display table table-striped table-condensed table-bordered table-hover" id="TRAILERS">
			<thead><tr class="exspeedite-bg"><th>Select</th><th>Trailer</th><th>Type</th><th>Owner</th><th>year</th><th>Make</th><th>Type</th><th>Length</th></tr>
			</thead>
			<tbody>';
			foreach( $trailers as $trailer ) {
				switch( $trailer['ISEXPIRED'] ) {
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
				if( $trailer['ISEXPIRED'] <> 'red' )
					$button = '<a class="btn btn-sm btn-success trailer_pick" id="TRAILER_'.$trailer['RESOURCE_CODE'].'"
					data-resource_code = "'.$trailer['RESOURCE_CODE'].'"
					data-resource = "'.$trailer['RESOURCE_NAME'].'"
					><span class="glyphicon glyphicon-plus"></span></a>';
				else
					$button = '<a class="btn btn-sm btn-danger disabled" name="disabled"><span class="glyphicon glyphicon-remove"></span></a>';
				$output .= '<tr'.$color.'><td>'.$button.'</td>
				<td>'.$trailer['RESOURCE_NAME'].'</td>
				<td>'.$trailer['OWNERSHIP_TYPE'].'</td>
				<td>'.$trailer['TRAILER_OWNER'].'</td>
				<td>'.$trailer['TRAILER_YEAR'].'</td>
				<td>'.$trailer['MAKE'].'</td>
				<td>'.$trailer['TRAILER_TYPE'].'</td>
				<td>'.$trailer['OUTSIDE_LENGTH'].'</td>
				</tr>';
			}
		}
		$output .= '</tbody>
		</table>
		</div>
		';

		
		return $output;
	}
	
	public function check_expired( $pk, $wrap = true ) {
		$response = '';
		$check = $this->fetch_rows( $this->primary_key." = ".$pk." AND ISDELETED = false",
			"INSPECTION_EXPIRY, DATEDIFF(INSPECTION_EXPIRY, CURRENT_DATE) INSPECTION_DAYS,
			INSURANCE_EXPIRY, DATEDIFF(INSURANCE_EXPIRY, CURRENT_DATE) INSURANCE_DAYS" );
		if( is_array($check) && count($check) == 1 ) {
			$responses = array();
			if( ! isset($check[0]["INSPECTION_EXPIRY"]) || is_null($check[0]["INSPECTION_EXPIRY"]))
				$responses[] =  '<span class="text-danger"><strong><span class="glyphicon glyphicon-warning-sign"></span></strong> Missing trailer annual inspection date.</span>';
			else if( $check[0]["INSPECTION_DAYS"] <= 0 )
				$responses[] =  '<span class="text-danger"><strong><span class="glyphicon glyphicon-warning-sign"></span></strong> Trailer annual inspection date ('.date("m/d/Y", strtotime($check[0]["INSPECTION_EXPIRY"])).') expired.</span>';
			else if( $check[0]["INSPECTION_DAYS"] < 15 )
				$responses[] =  '<span class="text-inprogress2"><strong><span class="glyphicon glyphicon-warning-sign"></span></strong> Trailer annual inspection date ('.
				date("m/d/Y", strtotime($check[0]["INSPECTION_EXPIRY"])).') due < 15 days.</span>';
			else if( $check[0]["INSPECTION_DAYS"] < 30 )
				$responses[] =  '<span class="text-warning"><strong><span class="glyphicon glyphicon-warning-sign"></span></strong> Trailer annual inspection date ('.date("m/d/Y", strtotime($check[0]["INSPECTION_EXPIRY"])).') due < 30 days.</span>';

			if( ! isset($check[0]["INSURANCE_EXPIRY"]) || is_null($check[0]["INSURANCE_EXPIRY"]))
				$responses[] =  '<span class="text-danger"><strong><span class="glyphicon glyphicon-warning-sign"></span></strong> Missing trailer insurance date.</span>';
			else if( $check[0]["INSURANCE_DAYS"] <= 0 )
				$responses[] =  '<span class="text-danger"><strong><span class="glyphicon glyphicon-warning-sign"></span></strong> Trailer insurance date ('.date("m/d/Y", strtotime($check[0]["INSURANCE_EXPIRY"])).') expired.</span>';
			else if( $check[0]["INSURANCE_DAYS"] < 15 )
				$responses[] =  '<span class="text-inprogress2"><strong><span class="glyphicon glyphicon-warning-sign"></span></strong> Trailer insurance date ('.date("m/d/Y", strtotime($check[0]["INSURANCE_EXPIRY"])).') expires < 15 days.</span>';
			else if( $check[0]["INSURANCE_DAYS"] < 30 )
				$responses[] =  '<span class="text-warning"><strong><span class="glyphicon glyphicon-warning-sign"></span></strong> Trailer insurance date ('.date("m/d/Y", strtotime($check[0]["INSURANCE_EXPIRY"])).') expires < 30 days.</span>';

			//! SCR# 522 - Include warnings for overdue reports
			if( $this->inspection_reports )
				$responses = array_merge($responses,
					$this->report_table->check_expired( 'trailer', $pk ) );
						
			if( count($responses) > 0 ) {
				$response = implode("<br>\n", $responses);
				if( $wrap )
					$response = '<div class="panel panel-default tighter">
  <div class="panel-body" style="padding: 5px;">'.$response.'</div></div>';
  			}
		}
		
		return $response;
	}

	//! Display expired trailers
	public function alert_expired() {
		$result = '';

		//! SCR# 544 - only show active if setting is true
		if( $this->default_active )
			$df = " AND ISACTIVE = 'Active'";
		else
			$df = "";

		$expired = $this->database->get_multiple_rows("
			SELECT TRAILER_CODE, ANAME, ISEXPIRED
			FROM
			(SELECT TRAILER_CODE, UNIT_NUMBER AS ANAME,
				TRAILER_EXPIRED(TRAILER_CODE) ISEXPIRED
			FROM EXP_TRAILER
			WHERE ISDELETED = 0
			$df) X
			WHERE ISEXPIRED <> 'green'
			ORDER BY FIELD(ISEXPIRED, 'red', 'orange', 'yellow'), TRAILER_CODE ASC");
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
				$details = $this->check_expired( $row["TRAILER_CODE"], false );
				$actions[] = '<a class="btn btn-sm btn-'.$colour.' inform" href="exp_edittrailer.php?CODE='.$row["TRAILER_CODE"].'" title="<strong>Trailer: '.$row["ANAME"].'</strong>" data-content=\''.$details.'\'>'.$row["ANAME"].'</a>';
			}
			$result = '<p style="margin-bottom: 5px;"><a class="btn btn-sm btn-primary" data-toggle="collapse" href="#collapseTrailers" aria-expanded="false" aria-controls="collapseTrailers"><span class="glyphicon glyphicon-warning-sign"></span> '.plural(count($expired), ' Trailer').' need attention</a> (red=expired, orange=under 15 days, yellow=needs attention soon)</p>
			<div class="collapse" id="collapseTrailers">
			<p>'.implode(" ", $actions).'</p>
			</div>';

		}		
		return $result;
	}

	//! SCR# 240 - get address of the last stop for this trailer
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
			FROM EXP_STOP S, EXP_TRAILER T
			WHERE T.TRAILER_CODE = $pk
            AND T.LAST_STOP = S.STOP_CODE) S
            LEFT JOIN
			EXP_SHIPMENT T
			ON T.SHIPMENT_CODE = S.SHIPMENT");

		return $result;		
	}

}

class sts_trailer_history extends sts_trailer {

	// Constructor does not need the table name
	public function __construct( $database, $debug = false ) {

		if( $debug ) echo "<p>Create sts_trailer_history</p>";
		parent::__construct( $database, $debug);
	}

	// Fetch one or more rows
	public function fetch_rows( $match = "", $fields = "*", $order = "", $limit = "", $groupby = "", $match2 = "" ) {
		if( $this->debug ) echo "<p>fetch_rows $match</p>";
		$result = $this->database->get_multiple_rows("
			SELECT TRAILER_CODE, STOP_LOAD_CODE, SEQUENCE_NO, STOP_TYPE, STOP_STATUS, (CASE STOP_TYPE
								WHEN 'stop' THEN NULL
								ELSE SHIPMENT END
								) AS SHIPMENT2, ACTUAL_ARRIVE, ACTUAL_DEPART, 
			(SELECT UNIT_NUMBER from EXP_TRAILER X WHERE X.TRAILER_CODE = EXP_STOP.TRAILER_CODE  LIMIT 0 , 1) AS TRAILER2, 
			NAME, CITY, STATE, ZIP_CODE, STOP_DISTANCE 
				FROM
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
					WHEN 'pick' THEN SHIPPER_CONTACT
					WHEN 'drop' THEN CONS_CONTACT
					ELSE STOP_CONTACT END
				) AS CONTACT,
				(CASE STOP_TYPE
					WHEN 'pick' THEN PICKUP_APPT
					WHEN 'drop' THEN DELIVERY_APPT
					ELSE '' END
				) AS APPT,
				COALESCE(TRAILER, (SELECT TRAILER FROM EXP_LOAD 
					WHERE EXP_LOAD.LOAD_CODE = STOP_LOAD_CODE) ) AS TRAILER_CODE,
				(CASE STOP_TYPE
					WHEN 'stop' THEN NULL
					ELSE (SELECT STATUS_STATE from EXP_STATUS_CODES X 
						WHERE X.STATUS_CODES_CODE = EXP_SHIPMENT.CURRENT_STATUS  LIMIT 0 , 1) 
				END) AS SHIPMENT_STATUS

			FROM
			
			(SELECT STOP_CODE, LOAD_CODE AS STOP_LOAD_CODE, SEQUENCE_NO, STOP_TYPE, SHIPMENT,
				CURRENT_STATUS AS STOP_CURRENT_STATUS,
				STOP_DISTANCE, ACTUAL_ARRIVE, ACTUAL_DEPART,
				STOP_NAME, STOP_ADDR1, STOP_ADDR2, STOP_CITY, STOP_STATE,
				STOP_ZIP, STOP_PHONE, STOP_EXT, STOP_FAX, STOP_CELL,
				STOP_CONTACT, STOP_EMAIL, TRAILER,
			(SELECT STATUS_STATE from EXP_STATUS_CODES X 
				WHERE X.STATUS_CODES_CODE = EXP_STOP.CURRENT_STATUS  LIMIT 0 , 1) AS STOP_STATUS
			FROM EXP_STOP 
			".($match <> "" ? "WHERE $match" : "")."
			".($order <> "" ? "ORDER BY $order" : "")."
			".($limit <> "" ? "LIMIT $limit" : "")."
			) TMP
			LEFT JOIN
			EXP_SHIPMENT
			ON SHIPMENT_CODE = SHIPMENT
			ORDER BY STOP_LOAD_CODE ASC, SEQUENCE_NO ASC ) EXP_STOP		
		");
		return $result;
	}
}

//! Form Specifications - For use with sts_form

$sts_form_addtrailer_form = array(	//! sts_form_addtrailer_form
	'title' => '<img src="images/trailer_icon.png" alt="trailer_icon" height="24"> Add Trailer',
	'action' => 'exp_addtrailer.php',
	//'actionextras' => 'disabled',
	'cancel' => 'exp_listtrailer.php',
	'name' => 'addtrailer',
	'okbutton' => 'Save Changes',
	'cancelbutton' => 'Cancel',
		'layout' => '
	<div class="form-group">
		<div class="col-sm-4">
			<div class="form-group">
				<label for="UNIT_NUMBER" class="col-sm-4 control-label">#UNIT_NUMBER#</label>
				<div class="col-sm-8">
					%UNIT_NUMBER%
				</div>
			</div>
			<div class="form-group">
				<label for="OWNERSHIP_TYPE" class="col-sm-4 control-label">#OWNERSHIP_TYPE#</label>
				<div class="col-sm-8">
					%OWNERSHIP_TYPE%
				</div>
			</div>
			<div class="form-group">
				<label for="TRAILER_OWNER" class="col-sm-4 control-label">#TRAILER_OWNER#</label>
				<div class="col-sm-8">
					%TRAILER_OWNER%
				</div>
			</div>
			<div class="form-group">
				<label for="INSPECTION_EXPIRY" class="col-sm-4 control-label">#INSPECTION_EXPIRY#</label>
				<div class="col-sm-8">
					%INSPECTION_EXPIRY%
				</div>
			</div>
			<div class="form-group">
				<label for="INSURANCE_EXPIRY" class="col-sm-4 control-label">#INSURANCE_EXPIRY#</label>
				<div class="col-sm-8">
					%INSURANCE_EXPIRY%
				</div>
			</div>
			<div class="form-group">
				<label for="TRAILER_BITMAP" class="col-sm-4 control-label">#TRAILER_BITMAP#</label>
				<div class="col-sm-8">
					%TRAILER_BITMAP%
				</div>
			</div>
			<div class="form-group">
				<label for="SAT_TRACKING" class="col-sm-4 control-label">#SAT_TRACKING#</label>
				<div class="col-sm-8">
					%SAT_TRACKING%
				</div>
			</div>
		</div>
		<div class="col-sm-8">
			<div class="form-group well well-sm">
				<div class="col-sm-6">
					<div class="form-group">
						<label for="OUTSIDE_LENGTH" class="col-sm-4 control-label">#OUTSIDE_LENGTH#</label>
						<div class="col-sm-8">
							%OUTSIDE_LENGTH%
						</div>
					</div>
					<div class="form-group">
						<label for="OUTSIDE_WIDTH" class="col-sm-4 control-label">#OUTSIDE_WIDTH#</label>
						<div class="col-sm-8">
							%OUTSIDE_WIDTH%
						</div>
					</div>
					<div class="form-group">
						<label for="OUTSIDE_HEIGHT" class="col-sm-4 control-label">#OUTSIDE_HEIGHT#</label>
						<div class="col-sm-8">
							%OUTSIDE_HEIGHT%
						</div>
					</div>
					<div class="form-group">
						<label for="MAXIUMUM_LENGTH" class="col-sm-4 control-label">#MAXIUMUM_LENGTH#</label>
						<div class="col-sm-8">
							%MAXIUMUM_LENGTH%
						</div>
					</div>
					<div class="form-group">
						<label for="TARE_WEIGHT" class="col-sm-4 control-label">#TARE_WEIGHT#</label>
						<div class="col-sm-8">
							%TARE_WEIGHT%
						</div>
					</div>
				</div>
				<div class="col-sm-6">
					<div class="form-group">
						<label for="MAXIMUM_WEIGHT" class="col-sm-4 control-label">#MAXIMUM_WEIGHT#</label>
						<div class="col-sm-8">
							%MAXIMUM_WEIGHT%
						</div>
					</div>
					<div class="form-group">
						<label for="INSIDE_LENGTH" class="col-sm-4 control-label">#INSIDE_LENGTH#</label>
						<div class="col-sm-8">
							%INSIDE_LENGTH%
						</div>
					</div>
					<div class="form-group">
						<label for="INSIDE_WIDTH" class="col-sm-4 control-label">#INSIDE_WIDTH#</label>
						<div class="col-sm-8">
							%INSIDE_WIDTH%
						</div>
					</div>
					<div class="form-group">
						<label for="INSIDE_HEIGHT" class="col-sm-4 control-label">#INSIDE_HEIGHT#</label>
						<div class="col-sm-8">
							%INSIDE_HEIGHT%
						</div>
					</div>
					<div class="form-group">
						<label for="MAXIMUM_CUBE" class="col-sm-4 control-label">#MAXIMUM_CUBE#</label>
						<div class="col-sm-8">
							%MAXIMUM_CUBE%
						</div>
					</div>
				</div>
			</div>
			<div id="TANKER_FIELDS" class="form-group panel panel-success">
				<div class="panel-heading">
					<h3 class="panel-title">Tanker Specific</h3>
				</div>
				<div class="col-sm-6 pad">
					<div class="form-group">
						<label for="TANK_COMPARTMENTS" class="col-sm-5 control-label">#TANK_COMPARTMENTS#</label>
						<div class="col-sm-7">
							%TANK_COMPARTMENTS%
						</div>
					</div>
					<div class="form-group">
						<label for="TANK_INSULATED" class="col-sm-5 control-label">#TANK_INSULATED#</label>
						<div class="col-sm-7">
							%TANK_INSULATED%
						</div>
					</div>
				</div>
				<div class="col-sm-6 pad">
					<div class="form-group">
						<label for="TANK_CAPACITY" class="col-sm-5 control-label">#TANK_CAPACITY#</label>
						<div class="col-sm-7">
							%TANK_CAPACITY%
						</div>
					</div>
					<div class="form-group">
						<label for="TANK_PUMP" class="col-sm-5 control-label">#TANK_PUMP#</label>
						<div class="col-sm-7">
							%TANK_PUMP%
						</div>
					</div>
				</div>
				<div class="col-sm-12">
					<div class="form-group">
						<label for="TANK_COMMODITY" class="col-sm-4 control-label">#TANK_COMMODITY#</label>
						<div class="col-sm-8">
							%TANK_COMMODITY%
						</div>
					</div>
					<div class="form-group">
						<label for="TANK_CUSTOMER" class="col-sm-4 control-label">#TANK_CUSTOMER#</label>
						<div class="col-sm-8">
							%TANK_CUSTOMER%
						</div>
					</div>
				</div>
			</div>
			<!-- TANKER_FIELDS -->
			<div id="IM_FIELDS" class="form-group panel panel-info">
				<div class="panel-heading">
					<h3 class="panel-title">Intermodal Specific</h3>
				</div>
				<div class="col-sm-6 pad">
					<div class="form-group">
						<label for="IM_CONTAINER" class="col-sm-4 control-label">#IM_CONTAINER#</label>
						<div class="col-sm-8">
							%IM_CONTAINER%
						</div>
					</div>
					<div class="form-group">
						<label for="IM_ISO" class="col-sm-4 control-label">#IM_ISO#</label>
						<div class="col-sm-8">
							%IM_ISO%
						</div>
					</div>
					<div class="form-group">
						<label for="IM_CHECK" class="col-sm-6 control-label">#IM_CHECK#</label>
						<div class="col-sm-6">
							%IM_CHECK%
						</div>
					</div>
				</div>
				<div class="col-sm-6 pad">
					<div class="form-group">
						<label for="IM_SIZE_CODE" class="col-sm-4 control-label">#IM_SIZE_CODE#</label>
						<div class="col-sm-8">
							%IM_SIZE_CODE%
						</div>
					</div>
					<div class="form-group">
						<label for="IM_CLASS" class="col-sm-4 control-label">#IM_CLASS#</label>
						<div class="col-sm-8">
							%IM_CLASS%
						</div>
					</div>
				</div>
			</div>
			<!-- IM_FIELDS -->
			<div class="form-group well well-sm">
				<div class="col-sm-6">
					<div class="form-group">
						<label for="VIN_NUMBER" class="col-sm-4 control-label">#VIN_NUMBER#</label>
						<div class="col-sm-8">
							%VIN_NUMBER%
						</div>
					</div>
					<div class="form-group">
						<label for="PLATE" class="col-sm-4 control-label">#PLATE#</label>
						<div class="col-sm-8">
							%PLATE%
						</div>
					</div>
					<div class="form-group">
						<label for="CLASS" class="col-sm-4 control-label">#CLASS#</label>
						<div class="col-sm-8">
							%CLASS%
						</div>
					</div>
					<div class="form-group">
						<label for="RM_CLASS" class="col-sm-4 control-label">#RM_CLASS#</label>
						<div class="col-sm-8">
							%RM_CLASS%
						</div>
					</div>
					<div class="form-group">
						<label for="TRAILER_YEAR" class="col-sm-4 control-label">#TRAILER_YEAR#</label>
						<div class="col-sm-8">
							%TRAILER_YEAR%
						</div>
					</div>
				</div>
				<div class="col-sm-6">
					<div class="form-group">
						<label for="AXLES" class="col-sm-4 control-label">#AXLES#</label>
						<div class="col-sm-8">
							%AXLES%
						</div>
					</div>
					<div class="form-group">
						<label for="SUSPENSION" class="col-sm-4 control-label">#SUSPENSION#</label>
						<div class="col-sm-8">
							%SUSPENSION%
						</div>
					</div>
					<div class="form-group">
						<label for="MAKE" class="col-sm-4 control-label">#MAKE#</label>
						<div class="col-sm-8">
							%MAKE%
						</div>
					</div>
					<div class="form-group">
						<label for="TRAILER_TYPE" class="col-sm-4 control-label">#TRAILER_TYPE#</label>
						<div class="col-sm-8">
							%TRAILER_TYPE%
						</div>
					</div>
				</div>
			</div>
				<div class="form-group">
					<label for="TRAILER_DESCRIPTION" class="col-sm-2 control-label">#TRAILER_DESCRIPTION#</label>
					<div class="col-sm-10">
						%TRAILER_DESCRIPTION%
					</div>
				</div>
		</div>
	</div>
	
	'
);

$sts_form_edittrailer_form = array(	//! sts_form_edittrailer_form
	'title' => '<img src="images/trailer_icon.png" alt="trailer_icon" height="24"> Edit Trailer',
	'action' => 'exp_edittrailer.php',
	//'actionextras' => 'disabled',
	'cancel' => 'exp_listtrailer.php',
	'name' => 'edittrailer',
	'okbutton' => 'Save Changes',
	'saveadd' => 'Add Another',
	'cancelbutton' => 'Back to Trailers',
		'layout' => '
		%TRAILER_CODE%
	<div id="warnings"></div>
	<div class="form-group">
		<div class="col-sm-4">
			<div class="form-group">
				<label for="UNIT_NUMBER" class="col-sm-4 control-label">#UNIT_NUMBER#</label>
				<div class="col-sm-8">
					%UNIT_NUMBER%
				</div>
			</div>
			<div class="form-group">
				<label for="ISACTIVE" class="col-sm-4 control-label">#ISACTIVE#</label>
				<div class="col-sm-8">
					%ISACTIVE%
				</div>
			</div>
			<div class="form-group" id="OOS" hidden>
				<label for="OOS_TYPE" class="col-sm-4 control-label">#OOS_TYPE#</label>
				<div class="col-sm-8">
					%OOS_TYPE%
				</div>
			</div>
			<div class="form-group">
				<label for="OWNERSHIP_TYPE" class="col-sm-4 control-label">#OWNERSHIP_TYPE#</label>
				<div class="col-sm-8">
					%OWNERSHIP_TYPE%
				</div>
			</div>
			<div class="form-group">
				<label for="TRAILER_OWNER" class="col-sm-4 control-label">#TRAILER_OWNER#</label>
				<div class="col-sm-8">
					%TRAILER_OWNER%
				</div>
			</div>
			<div class="form-group">
				<label for="INSPECTION_EXPIRY" class="col-sm-4 control-label">#INSPECTION_EXPIRY#</label>
				<div class="col-sm-8">
					%INSPECTION_EXPIRY%
				</div>
			</div>
			<div class="form-group">
				<label for="INSURANCE_EXPIRY" class="col-sm-4 control-label">#INSURANCE_EXPIRY#</label>
				<div class="col-sm-8">
					%INSURANCE_EXPIRY%
				</div>
			</div>
			<div class="form-group">
				<label for="DRIVER" class="col-sm-4 control-label">#DRIVER#</label>
				<div class="col-sm-8">
					%DRIVER%
				</div>
			</div>
			<div class="form-group">
				<label for="TRAILER_BITMAP" class="col-sm-4 control-label">#TRAILER_BITMAP#</label>
				<div class="col-sm-8">
					%TRAILER_BITMAP%
				</div>
			</div>
			<div class="form-group">
				<label for="SAT_TRACKING" class="col-sm-4 control-label">#SAT_TRACKING#</label>
				<div class="col-sm-8">
					%SAT_TRACKING%
				</div>
			</div>
			<div class="form-group tighter">
				<label for="LAST_LOCATION" class="col-sm-3 control-label">Last Location</label>
				<div class="col-sm-9" id="LAST_LOCATION">
				</div>
			</div>
		</div>
		<div class="col-sm-8">
			<div class="form-group well well-sm">
				<div class="col-sm-6">
					<div class="form-group">
						<label for="OUTSIDE_LENGTH" class="col-sm-4 control-label">#OUTSIDE_LENGTH#</label>
						<div class="col-sm-8">
							%OUTSIDE_LENGTH%
						</div>
					</div>
					<div class="form-group">
						<label for="OUTSIDE_WIDTH" class="col-sm-4 control-label">#OUTSIDE_WIDTH#</label>
						<div class="col-sm-8">
							%OUTSIDE_WIDTH%
						</div>
					</div>
					<div class="form-group">
						<label for="OUTSIDE_HEIGHT" class="col-sm-4 control-label">#OUTSIDE_HEIGHT#</label>
						<div class="col-sm-8">
							%OUTSIDE_HEIGHT%
						</div>
					</div>
					<div class="form-group">
						<label for="TARE_WEIGHT" class="col-sm-4 control-label">#TARE_WEIGHT#</label>
						<div class="col-sm-8">
							%TARE_WEIGHT%
						</div>
					</div>
					<div class="form-group">
						<label for="MAXIMUM_WEIGHT" class="col-sm-4 control-label">#MAXIMUM_WEIGHT#</label>
						<div class="col-sm-8">
							%MAXIMUM_WEIGHT%
						</div>
					</div>
				</div>
				<div class="col-sm-6">
					<div class="form-group">
						<label for="MAXIUMUM_LENGTH" class="col-sm-4 control-label">#MAXIUMUM_LENGTH#</label>
						<div class="col-sm-8">
							%MAXIUMUM_LENGTH%
						</div>
					</div>
					<div class="form-group">
						<label for="INSIDE_LENGTH" class="col-sm-4 control-label">#INSIDE_LENGTH#</label>
						<div class="col-sm-8">
							%INSIDE_LENGTH%
						</div>
					</div>
					<div class="form-group">
						<label for="INSIDE_WIDTH" class="col-sm-4 control-label">#INSIDE_WIDTH#</label>
						<div class="col-sm-8">
							%INSIDE_WIDTH%
						</div>
					</div>
					<div class="form-group">
						<label for="INSIDE_HEIGHT" class="col-sm-4 control-label">#INSIDE_HEIGHT#</label>
						<div class="col-sm-8">
							%INSIDE_HEIGHT%
						</div>
					</div>
					<div class="form-group">
						<label for="MAXIMUM_CUBE" class="col-sm-4 control-label">#MAXIMUM_CUBE#</label>
						<div class="col-sm-8">
							%MAXIMUM_CUBE%
						</div>
					</div>
				</div>
			</div>
			<div id="TANKER_FIELDS" class="form-group panel panel-success">
				<div class="panel-heading">
					<h3 class="panel-title">Tanker Specific</h3>
				</div>
				<div class="col-sm-6 pad">
					<div class="form-group">
						<label for="TANK_COMPARTMENTS" class="col-sm-5 control-label">#TANK_COMPARTMENTS#</label>
						<div class="col-sm-7">
							%TANK_COMPARTMENTS%
						</div>
					</div>
					<div class="form-group">
						<label for="TANK_INSULATED" class="col-sm-5 control-label">#TANK_INSULATED#</label>
						<div class="col-sm-7">
							%TANK_INSULATED%
						</div>
					</div>
				</div>
				<div class="col-sm-6 pad">
					<div class="form-group">
						<label for="TANK_CAPACITY" class="col-sm-5 control-label">#TANK_CAPACITY#</label>
						<div class="col-sm-7">
							%TANK_CAPACITY%
						</div>
					</div>
					<div class="form-group">
						<label for="TANK_PUMP" class="col-sm-5 control-label">#TANK_PUMP#</label>
						<div class="col-sm-7">
							%TANK_PUMP%
						</div>
					</div>
				</div>
				<div class="col-sm-12">
					<div class="form-group">
						<label for="TANK_COMMODITY" class="col-sm-4 control-label">#TANK_COMMODITY#</label>
						<div class="col-sm-8">
							%TANK_COMMODITY%
						</div>
					</div>
					<div class="form-group">
						<label for="TANK_CUSTOMER" class="col-sm-4 control-label">#TANK_CUSTOMER#</label>
						<div class="col-sm-8">
							%TANK_CUSTOMER%
						</div>
					</div>
				</div>
			</div>
			<!-- TANKER_FIELDS -->
			<div id="IM_FIELDS" class="form-group panel panel-info">
				<div class="panel-heading">
					<h3 class="panel-title">Intermodal Specific</h3>
				</div>
				<div class="col-sm-6 pad">
					<div class="form-group">
						<label for="IM_CONTAINER" class="col-sm-4 control-label">#IM_CONTAINER#</label>
						<div class="col-sm-8">
							%IM_CONTAINER%
						</div>
					</div>
					<div class="form-group">
						<label for="IM_ISO" class="col-sm-4 control-label">#IM_ISO#</label>
						<div class="col-sm-8">
							%IM_ISO%
						</div>
					</div>
					<div class="form-group">
						<label for="IM_CHECK" class="col-sm-6 control-label">#IM_CHECK#</label>
						<div class="col-sm-6">
							%IM_CHECK%
						</div>
					</div>
				</div>
				<div class="col-sm-6 pad">
					<div class="form-group">
						<label for="IM_SIZE_CODE" class="col-sm-4 control-label">#IM_SIZE_CODE#</label>
						<div class="col-sm-8">
							%IM_SIZE_CODE%
						</div>
					</div>
					<div class="form-group">
						<label for="IM_CLASS" class="col-sm-4 control-label">#IM_CLASS#</label>
						<div class="col-sm-8">
							%IM_CLASS%
						</div>
					</div>
				</div>
			</div>
			<!-- IM_FIELDS -->
			<div class="form-group well well-sm">
				<div class="col-sm-6">
					<div class="form-group">
						<label for="VIN_NUMBER" class="col-sm-4 control-label">#VIN_NUMBER#</label>
						<div class="col-sm-8">
							%VIN_NUMBER%
						</div>
					</div>
					<div class="form-group">
						<label for="PLATE" class="col-sm-4 control-label">#PLATE#</label>
						<div class="col-sm-8">
							%PLATE%
						</div>
					</div>
					<div class="form-group">
						<label for="CLASS" class="col-sm-4 control-label">#CLASS#</label>
						<div class="col-sm-8">
							%CLASS%
						</div>
					</div>
					<div class="form-group">
						<label for="RM_CLASS" class="col-sm-4 control-label">#RM_CLASS#</label>
						<div class="col-sm-8">
							%RM_CLASS%
						</div>
					</div>
					<div class="form-group">
						<label for="TRAILER_YEAR" class="col-sm-4 control-label">#TRAILER_YEAR#</label>
						<div class="col-sm-8">
							%TRAILER_YEAR%
						</div>
					</div>
				</div>
				<div class="col-sm-6">
					<div class="form-group">
						<label for="AXLES" class="col-sm-4 control-label">#AXLES#</label>
						<div class="col-sm-8">
							%AXLES%
						</div>
					</div>
					<div class="form-group">
						<label for="SUSPENSION" class="col-sm-4 control-label">#SUSPENSION#</label>
						<div class="col-sm-8">
							%SUSPENSION%
						</div>
					</div>
					<div class="form-group">
						<label for="MAKE" class="col-sm-4 control-label">#MAKE#</label>
						<div class="col-sm-8">
							%MAKE%
						</div>
					</div>
					<div class="form-group">
						<label for="TRAILER_TYPE" class="col-sm-4 control-label">#TRAILER_TYPE#</label>
						<div class="col-sm-8">
							%TRAILER_TYPE%
						</div>
					</div>
				</div>
			</div>
				<div class="form-group">
					<label for="LOCATION" class="col-sm-2 control-label">#LOCATION#</label>
					<div class="col-sm-10">
						%LOCATION%
					</div>
				</div>
				<div class="form-group">
					<label for="TRAILER_DESCRIPTION" class="col-sm-2 control-label">#TRAILER_DESCRIPTION#</label>
					<div class="col-sm-10">
						%TRAILER_DESCRIPTION%
					</div>
				</div>
		</div>
	</div>
	
	'
);

//! Field Specifications - For use with sts_form

$sts_form_add_trailer_fields = array(	//! $sts_form_add_trailer_fields
	'UNIT_NUMBER' => array( 'label' => 'Unit#', 'format' => 'text', 
		'extras' => 'required autofocus' ),
	'OWNERSHIP_TYPE' => array( 'label' => 'Own Type', 'format' => 'enum',
		'extras' => 'required' ),
	'TRAILER_OWNER' => array( 'label' => 'Owner', 'format' => 'text' ),
	'CLASS' => array( 'label' => 'Class', 'format' => 'text' ),
	'RM_CLASS' => array( 'label' => 'R&M Class', 'format' => 'table',
		'table' => RM_CLASS_TABLE, 'key' => 'CLASS_CODE', 'fields' => 'CLASS_NAME',
		'condition' => 'UNIT_TYPE = \'trailer\'' ),
	'TRAILER_YEAR' => array( 'label' => 'Year', 'format' => 'text' ),
	'MAKE' => array( 'label' => 'Make', 'format' => 'text' ),
	'TRAILER_TYPE' => array( 'label' => 'Type', 'format' => 'enum' ), 
	'OUTSIDE_LENGTH' => array( 'label' => 'Length', 'format' => 'number', 'align' => 'right' ),
	'OUTSIDE_WIDTH' => array( 'label' => 'Width', 'format' => 'number', 'align' => 'right' ),
	'OUTSIDE_HEIGHT' => array( 'label' => 'Height', 'format' => 'number', 'align' => 'right' ),
	'INSIDE_LENGTH' => array( 'label' => 'Ins Length', 'format' => 'number', 'align' => 'right' ),
	'INSIDE_WIDTH' => array( 'label' => 'Ins Width', 'format' => 'number', 'align' => 'right' ),
	'INSIDE_HEIGHT' => array( 'label' => 'Ins Heigth', 'format' => 'number', 'align' => 'right' ),
	'MAXIUMUM_LENGTH' => array( 'label' => 'Max Length', 'format' => 'number', 'align' => 'right' ),
	'MAXIMUM_CUBE' => array( 'label' => 'Max Cube', 'format' => 'number', 'align' => 'right' ),
	'TARE_WEIGHT' => array( 'label' => 'Tare Weight', 'format' => 'number', 'align' => 'right' ),
	'MAXIMUM_WEIGHT' => array( 'label' => 'Max Weight', 'format' => 'number', 'align' => 'right' ),
	'AXLES' => array( 'label' => 'Axles', 'format' => 'number', 'align' => 'right' ),
	'SUSPENSION' => array( 'label' => 'Suspension', 'format' => 'enum' ),
	'VIN_NUMBER' => array( 'label' => 'VIN', 'format' => 'text' ), 
	'PLATE' => array( 'label' => 'Plate#', 'format' => 'text' ),
	'TRAILER_DESCRIPTION' => array( 'label' => 'Description', 'format' => 'text' ),
	'TRAILER_BITMAP' => array( 'label' => 'Image', 'format' => 'image' ),
	'TANK_CAPACITY' => array( 'label' => 'Tank&nbsp;Capacity', 'format' => 'number', 'align' => 'right' ),
	'TANK_COMPARTMENTS' => array( 'label' => '#&nbsp;Compartments', 'format' => 'number', 'align' => 'right' ),
	'TANK_INSULATED' => array( 'label' => 'Insulated', 'format' => 'bool2', 'align' => 'right' ),
	'TANK_PUMP' => array( 'label' => 'Has&nbsp;Pump', 'format' => 'bool2', 'align' => 'right' ),
	'TANK_COMMODITY' => array( 'label' => 'Dedicated&nbsp;Commodity', 'format' => 'table',
		'table' => COMMODITY_TABLE, 'key' => 'COMMODITY_CODE', 'fields' => 'COMMODITY_NAME' ),
	'TANK_CUSTOMER' => array( 'label' => 'Dedicated&nbsp;Customer', 'format' => 'table',
		'table' => CLIENT_TABLE, 'key' => 'CLIENT_CODE', 'fields' => 'CLIENT_NAME' ),
	'INSURANCE_EXPIRY' => array( 'label' => 'Plate Expiry', 'format' => 'date' ),
	'INSPECTION_EXPIRY' => array( 'label' => 'Annual Inspection', 'format' => 'date' ),

	'IM_CONTAINER' => array( 'label' => 'Container', 'format' => 'text' ), 
	'IM_ISO' => array( 'label' => 'ISO', 'format' => 'text' ), 
	'IM_CHECK' => array( 'label' => 'Check Digit', 'format' => 'text' ), 
	'IM_SIZE_CODE' => array( 'label' => 'Size Code', 'format' => 'text' ), 
	'IM_CLASS' => array( 'label' => 'IM Class', 'format' => 'text' ), 
	'SAT_TRACKING' => array( 'label' => 'Satellite Tracking', 'format' => 'bool' ),

);

$sts_form_edit_trailer_fields = array(	//! $sts_form_edit_trailer_fields
	'TRAILER_CODE' => array( 'format' => 'hidden' ),	// primary key must be first
	'ISACTIVE' => array( 'label' => 'Active', 'format' => 'enum',
		'extras' => 'required' ),
	'OOS_TYPE' => array( 'label' => 'OOS Type', 'format' => 'enum' ),
	'UNIT_NUMBER' => array( 'label' => 'Unit#', 'format' => 'text', 
		'extras' => 'required autofocus' ),
	'OWNERSHIP_TYPE' => array( 'label' => 'Own Type', 'format' => 'enum',
		'extras' => 'required' ),
	'TRAILER_OWNER' => array( 'label' => 'Owner', 'format' => 'text' ),
	'DRIVER' => array( 'label' => 'Driver', 'format' => 'btable',
		'table' => DRIVER_TABLE, 'key' => 'DEFAULT_TRAILER', 
		'fields' => 'concat_ws( \' \', FIRST_NAME , LAST_NAME )',
		'pk' => 'TRAILER_CODE' ),
	'CLASS' => array( 'label' => 'Class', 'format' => 'text' ),
	'RM_CLASS' => array( 'label' => 'R&M Class', 'format' => 'table',
		'table' => RM_CLASS_TABLE, 'key' => 'CLASS_CODE', 'fields' => 'CLASS_NAME',
		'condition' => 'UNIT_TYPE = \'trailer\'' ),
	'TRAILER_YEAR' => array( 'label' => 'Year', 'format' => 'text' ),
	'MAKE' => array( 'label' => 'Make', 'format' => 'text' ),
	'TRAILER_TYPE' => array( 'label' => 'Type', 'format' => 'enum' ), 
	'OUTSIDE_LENGTH' => array( 'label' => 'Length', 'format' => 'number', 'align' => 'right' ),
	'OUTSIDE_WIDTH' => array( 'label' => 'Width', 'format' => 'number', 'align' => 'right' ),
	'OUTSIDE_HEIGHT' => array( 'label' => 'Height', 'format' => 'number', 'align' => 'right' ),
	'INSIDE_LENGTH' => array( 'label' => 'Ins Length', 'format' => 'number', 'align' => 'right' ),
	'INSIDE_WIDTH' => array( 'label' => 'Ins Width', 'format' => 'number', 'align' => 'right' ),
	'INSIDE_HEIGHT' => array( 'label' => 'Ins Heigth', 'format' => 'number', 'align' => 'right' ),
	'MAXIUMUM_LENGTH' => array( 'label' => 'Max Length', 'format' => 'number', 'align' => 'right' ),
	'MAXIMUM_CUBE' => array( 'label' => 'Max Cube', 'format' => 'number', 'align' => 'right' ),
	'TARE_WEIGHT' => array( 'label' => 'Tare Weight', 'format' => 'number', 'align' => 'right' ),
	'MAXIMUM_WEIGHT' => array( 'label' => 'Max Weight', 'format' => 'number', 'align' => 'right' ),
	'AXLES' => array( 'label' => 'Axles', 'format' => 'number', 'align' => 'right' ),
	'SUSPENSION' => array( 'label' => 'Suspension', 'format' => 'enum' ),
	'VIN_NUMBER' => array( 'label' => 'VIN', 'format' => 'text' ), 
	'PLATE' => array( 'label' => 'Plate#', 'format' => 'text' ),
	'TRAILER_DESCRIPTION' => array( 'label' => 'Description', 'format' => 'text' ),
	'LOCATION' => array( 'label' => 'Location', 'format' => 'text', 'extras' => 'readonly' ),
	'TRAILER_BITMAP' => array( 'label' => 'Image', 'format' => 'image' ),
	'TANK_CAPACITY' => array( 'label' => 'Tank&nbsp;Capacity', 'format' => 'number', 'align' => 'right' ),
	'TANK_COMPARTMENTS' => array( 'label' => '#&nbsp;Compartments', 'format' => 'number', 'align' => 'right' ),
	'TANK_INSULATED' => array( 'label' => 'Insulated', 'format' => 'bool2', 'align' => 'right' ),
	'TANK_PUMP' => array( 'label' => 'Has&nbsp;Pump', 'format' => 'bool2', 'align' => 'right' ),
	'TANK_COMMODITY' => array( 'label' => 'Dedicated&nbsp;Commodity', 'format' => 'table',
		'table' => COMMODITY_TABLE, 'key' => 'COMMODITY_CODE', 'fields' => 'COMMODITY_NAME' ),
	'TANK_CUSTOMER' => array( 'label' => 'Dedicated&nbsp;Customer', 'format' => 'table',
		'table' => CLIENT_TABLE, 'key' => 'CLIENT_CODE', 'fields' => 'CLIENT_NAME' ),
	'INSURANCE_EXPIRY' => array( 'label' => 'Plate Expiry', 'format' => 'date' ),
	'INSPECTION_EXPIRY' => array( 'label' => 'Annual Inspection', 'format' => 'date' ),

	'IM_CONTAINER' => array( 'label' => 'Container', 'format' => 'text' ), 
	'IM_ISO' => array( 'label' => 'ISO', 'format' => 'text' ), 
	'IM_CHECK' => array( 'label' => 'Check Digit', 'format' => 'text' ), 
	'IM_SIZE_CODE' => array( 'label' => 'Size Code', 'format' => 'text' ), 
	'IM_CLASS' => array( 'label' => 'IM Class', 'format' => 'text' ), 
	'SAT_TRACKING' => array( 'label' => 'Satellite Tracking', 'format' => 'bool' ),

);

//! Layout Specifications - For use with sts_result

$sts_result_trailers_layout = array(
	'TRAILER_CODE' => array( 'format' => 'hidden' ),
	'EXPIRED' => array( 'format' => 'hidden', 'snippet' => 'TRAILER_EXPIRED(TRAILER_CODE)' ),
	'UNIT_NUMBER' => array( 'label' => 'TRL#', 'format' => 'text', 'searchable' => true ),
	'ISACTIVE' => array( 'label' => 'Active', 'align' => 'center', 'format' => 'text' ),
	'ISDELETED' => array( 'label' => 'Del', 'align' => 'center', 'format' => 'hidden' ),
	'OWNERSHIP_TYPE' => array( 'label' => 'Ownership', 'format' => 'text' ),
	'TRAILER_OWNER' => array( 'label' => 'Owner', 'format' => 'text' ),
	//'CLASS' => array( 'label' => 'Class', 'format' => 'text' ),
	'TRAILER_YEAR' => array( 'label' => 'Year', 'format' => 'text' ),
	'MAKE' => array( 'label' => 'Make', 'format' => 'text', 'searchable' => true ),
	'TRAILER_TYPE' => array( 'label' => 'Type', 'format' => 'text', 'searchable' => true ),
	'OUTSIDE_LENGTH' => array( 'label' => 'Length', 'format' => 'num0' ),
	'OUTSIDE_WIDTH' => array( 'label' => 'Width', 'format' => 'num0' ),
	'INSIDE_WIDTH' => array( 'label' => 'Inside Width', 'format' => 'num0' ),
	'INSIDE_HEIGHT' => array( 'label' => 'Inside Height', 'format' => 'num0' ),
	'TARE_WEIGHT' => array( 'label' => 'Weight', 'format' => 'num0' ),
	'SUSPENSION' => array( 'label' => 'Suspension', 'format' => 'text' ),
	'VIN_NUMBER' => array( 'label' => 'VIN', 'format' => 'text' ),
	'PLATE' => array( 'label' => 'Plate#', 'format' => 'text', 'searchable' => true ),
	'LOCATION' => array( 'label' => 'Location', 'format' => 'text' ),
	'DRIVER' => array( 'label' => 'Driver', 'format' => 'btable',
		'table' => DRIVER_TABLE, 'key' => 'DEFAULT_TRAILER', 
		'fields' => 'concat_ws( \' \', FIRST_NAME , LAST_NAME )',
		'pk' => 'TRAILER_CODE' ),
	'TRAILER_STATUS' => array( 'label' => 'Status', 'format' => 'text' ),
	'CHANGED_DATE' => array( 'label' => 'Changed', 'format' => 'timestamp-s', 'length' => 90 ),
	'CHANGED_BY' => array( 'label' => 'By', 'format' => 'table',
		'table' => USER_TABLE, 'key' => 'USER_CODE', 'fields' => 'USERNAME', 'link' => 'exp_edituser.php?CODE=' )

);

$sts_result_trailer_history_layout = array(
	'STOP_LOAD_CODE' => array( 'label' => 'Load#', 'format' => 'num0nc', 'link' => 'exp_viewload.php?CODE=', 'align' => 'right' ),
	'SEQUENCE_NO' => array( 'label' => 'Stop', 'format' => 'num0stop', 'align' => 'right', 'length' => 40 ),
	'STOP_TYPE' => array( 'label' => 'Type', 'format' => 'hidden' ),
	'STOP_STATUS' => array( 'label' => 'Status', 'format' => 'text' ),
	'SHIPMENT2' => array( 'label' => 'Shipment', 'format' => 'case',
		'key' => 'STOP_TYPE', 'val1' => 'stop', 'choice1' => 'NULL', 
		'choice2' => 'SHIPMENT', 'format2' => 'num0nc', 'align' => 'right', 'link' => 'exp_addshipment.php?CODE=' ),
	'ACTUAL_ARRIVE' => array( 'label' => 'Actual Arr', 'format' => 'timestamp-s', 'length' => 100 ),
	'ACTUAL_DEPART' => array( 'label' => 'Actual Dep', 'format' => 'timestamp-s', 'length' => 100 ),
	'NAME' => array( 'label' => 'Shipper/Consignee', 'format' => 'text' ),
	'CITY' => array( 'label' => 'City', 'format' => 'text' ),
	'STATE' => array( 'label' => 'State', 'format' => 'text' ),
	'ZIP_CODE' => array( 'label' => 'Zip', 'format' => 'text' ),
	'STOP_DISTANCE' => array( 'label' => 'Distance', 'format' => 'num0', 'align' => 'right' ),
);

//! Edit/Delete Button Specifications - For use with sts_result

$sts_result_trailers_edit = array(
	'title' => '<img src="images/trailer_icon.png" alt="trailer_icon" height="24"> Trailers',
	'sort' => 'UNIT_NUMBER asc',
	'cancel' => 'index.php',
	'add' => 'exp_addtrailer.php',
	//'actionextras' => 'disabled',
	'addbutton' => 'Add Trailer',
	'cancelbutton' => 'Back',
	'rowbuttons' => array(
		array( 'url' => 'exp_edittrailer.php?CODE=', 'key' => 'TRAILER_CODE', 'label' => 'UNIT_NUMBER', 'tip' => 'Edit trailer ', 'icon' => 'glyphicon glyphicon-edit', 'showif' => 'notdeleted' ),
		array( 'url' => 'exp_deletetrailer.php?TYPE=del&CODE=', 'key' => 'TRAILER_CODE', 'label' => 'UNIT_NUMBER', 'tip' => 'Delete trailer ', 'icon' => 'glyphicon glyphicon-trash', 'confirm' => 'yes', 'showif' => 'notdeleted' ),
		array( 'url' => 'exp_deletetrailer.php?TYPE=undel&CODE=', 'key' => 'TRAILER_CODE', 'label' => 'UNIT_NUMBER', 'tip' => 'Undelete trailer ', 'icon' => 'glyphicon glyphicon-arrow-left', 'showif' => 'deleted' ),
		array( 'url' => 'exp_deletetrailer.php?TYPE=permdel&CODE=', 'key' => 'TRAILER_CODE', 'label' => 'UNIT_NUMBER', 'tip' => 'Permanently Delete trailer ', 'icon' => 'glyphicon glyphicon-remove', 'confirm' => 'yes', 'showif' => 'deleted' )
	),
);

$sts_result_trailers_history_view = array(
	'title' => '<img src="images/trailer_icon.png" alt="trailer_icon" height="24"> Trailer History',
	'filters' => false,
	//'cancel' => 'index.php',
	//'add' => 'exp_addshipment.php',
	//'actionextras' => 'disabled',
	//'addbutton' => 'Add Shipment',
	'cancelbutton' => 'Back',
);

//! Use this for parsing CSV files & importing trailers
$sts_csv_trailer_parse = array(
	array( 'table' => TRAILER_TABLE, 'class' => 'sts_trailer',
		'primary' => 'TRAILER_CODE',
		'rows' => array(
			'UNIT_NUMBER' => array( 'label' => 'UnitNumber', 'format' => 'text',
				'required' => true ),
			'ISACTIVE' => array( 'value' => 'Active' ),
			'OWNERSHIP_TYPE' => array( 'label' => 'Own Type', 'format' => 'enum',
				'required' => true ),
			'TRAILER_OWNER' => array( 'label' => 'Owner', 'format' => 'text' ),
			'CLASS' => array( 'label' => 'Class', 'format' => 'text' ),
			'TRAILER_YEAR' => array( 'label' => 'Year', 'format' => 'text' ),
			'MAKE' => array( 'label' => 'Make', 'format' => 'text' ),
			'TRAILER_TYPE' => array( 'label' => 'TrailerType', 'format' => 'enum' ), 
			'OUTSIDE_LENGTH' => array( 'label' => 'Length', 'format' => 'number' ),
			'OUTSIDE_WIDTH' => array( 'label' => 'Width', 'format' => 'number' ),
			'OUTSIDE_HEIGHT' => array( 'label' => 'Height', 'format' => 'number' ),
			'INSIDE_LENGTH' => array( 'label' => 'InsideLength', 'format' => 'number' ),
			'INSIDE_WIDTH' => array( 'label' => 'InsideWidth', 'format' => 'number' ),
			'INSIDE_HEIGHT' => array( 'label' => 'InsideHeigth', 'format' => 'number' ),
			'MAXIUMUM_LENGTH' => array( 'label' => 'MaxLength', 'format' => 'number' ),
			'MAXIMUM_CUBE' => array( 'label' => 'MaxCube', 'format' => 'number' ),
			'TARE_WEIGHT' => array( 'label' => 'TareWeight', 'format' => 'number' ),
			'MAXIMUM_WEIGHT' => array( 'label' => 'MaxWeight', 'format' => 'number' ),
			'AXLES' => array( 'label' => 'Axles', 'format' => 'number' ),
			'SUSPENSION' => array( 'label' => 'Suspension', 'format' => 'enum' ),
			'VIN_NUMBER' => array( 'label' => 'VIN', 'format' => 'text' ), 
			'PLATE' => array( 'label' => 'PlateNumber', 'format' => 'text' ),
			'TRAILER_DESCRIPTION' => array( 'label' => 'Description', 'format' => 'text' ),
			'TANK_CAPACITY' => array( 'label' => 'TankCapacity', 'format' => 'number' ),
			'TANK_COMPARTMENTS' => array( 'label' => 'TankCompartments',
				'format' => 'number' ),
			'TANK_INSULATED' => array( 'label' => 'TankInsulated', 'format' => 'bool' ),
			'TANK_PUMP' => array( 'label' => 'TankHasPump', 'format' => 'bool' ),
			'INSURANCE_EXPIRY' => array( 'label' => 'PlateExpiry', 'format' => 'date' ),
			'INSPECTION_EXPIRY' => array( 'label' => 'AnnualInspection',
				'format' => 'date' ),
		),
	),
);
?>
