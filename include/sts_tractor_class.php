<?php

// $Id: sts_tractor_class.php 5030 2023-04-12 20:31:34Z duncan $
// Tractor class, all activity related to tractors.

// no direct access
defined('_STS_INCLUDE') or die('Restricted access');

require_once( "sts_table_class.php" );
require_once( "sts_setting_class.php" );
require_once( "sts_insp_report_class.php" );

class sts_tractor extends sts_table {

	private $setting_table;
	private $expire_tractors;
	private $inspection_reports;
	private $report_table;
	private $default_active;
	private $pivot;

	// Constructor does not need the table name
	public function __construct( $database, $debug = false ) {

		$this->debug = $debug;
		$this->primary_key = "TRACTOR_CODE";
		if( $this->debug ) echo "<p>Create sts_tractor</p>";
		parent::__construct( $database, TRACTOR_TABLE, $debug);
		$this->setting_table = sts_setting::getInstance($database, $debug);
		$this->expire_tractors = $this->setting_table->get( 'option', 'EXPIRE_TRACTORS_ENABLED' ) == 'true';
		$this->inspection_reports = $this->setting_table->get( 'option', 'INSPECTION_REPORTS' ) == 'true';
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

		if( $this->debug ) echo "<p>suggest $query</p>";
		$result = $this->fetch_rows( "UNIT_NUMBER like '".$query."%'
			AND ISACTIVE = 'Active' AND ISDELETED = false
			".($this->expire_tractors ? "AND TRACTOR_EXPIRED(TRACTOR_CODE) <> 'red'" : ""),
			"TRACTOR_CODE AS RESOURCE_CODE, 
			UNIT_NUMBER AS RESOURCE_NAME,
			CONCAT_WS(', ',OWNERSHIP_TYPE,TRACTOR_OWNER,TRACTOR_YEAR,MAKE) AS RESOURCE_EXTRA", "", "0, 20" );
		return $result;
	}

	//! Find matching tractors for a given load
	// Used for modal display on adding resources screen 
	// See also exp_pick_asset.php
	public function matching( $load ) {

		$output = '';
		if( $this->debug ) echo "<p>".__METHOD__.": load = $load</p>";
		
		$tractors = $this->fetch_rows( "ISACTIVE = 'Active' AND ISDELETED = false", 
			"TRACTOR_CODE AS RESOURCE_CODE, 
			UNIT_NUMBER AS RESOURCE_NAME,
			OWNERSHIP_TYPE,TRACTOR_OWNER,TRACTOR_YEAR,MAKE, MODEL,
			".($this->expire_tractors ? "TRACTOR_EXPIRED(TRACTOR_CODE)" : "'green'")." AS ISEXPIRED", "RESOURCE_NAME ASC" );
		
		if( is_array($tractors) && count($tractors) > 0) {
			$output .= '<div class="table-responsive">
			<table class="display table table-striped table-condensed table-bordered table-hover" id="TRACTORS">
			<thead><tr class="exspeedite-bg"><th>Select</th><th>Tractor</th><th>Type</th><th>Owner</th><th>year</th><th>Make</th><th>Model</th></tr>
			</thead>
			<tbody>';
			foreach( $tractors as $tractor ) {
				switch( $tractor['ISEXPIRED'] ) {
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
				if( $tractor['ISEXPIRED'] <> 'red' )
					$button = '<a class="btn btn-sm btn-success tractor_pick" id="TRACTOR_'.$tractor['RESOURCE_CODE'].'"
					data-resource_code = "'.$tractor['RESOURCE_CODE'].'"
					data-resource = "'.$tractor['RESOURCE_NAME'].'"
					><span class="glyphicon glyphicon-plus"></span></a>';
				else
					$button = '<a class="btn btn-sm btn-danger disabled" name="disabled"><span class="glyphicon glyphicon-remove"></span></a>';
				$output .= '<tr'.$color.'><td>'.$button.'</td>
				<td>'.$tractor['RESOURCE_NAME'].'</td>
				<td>'.$tractor['OWNERSHIP_TYPE'].'</td>
				<td>'.$tractor['TRACTOR_OWNER'].'</td>
				<td>'.$tractor['TRACTOR_YEAR'].'</td>
				<td>'.$tractor['MAKE'].'</td>
				<td>'.$tractor['MODEL'].'</td>
				</tr>';
			}
		}
		$output .= '</tbody>
		</table>
		</div>
		';

		
		return $output;
	}
	
    //! create a menu of available tractors, for IFTA logging
    public function tractor_menu( $selected = false, $id = 'TRACTOR_CODE', $match = '', $onchange = true ) {
		$select = false;

		$choices = $this->fetch_rows( "ISDELETED = false
			AND LOG_IFTA = true".
			($match <> '' ? ' AND '.$match : ''), 
			"TRACTOR_CODE, UNIT_NUMBER, ISACTIVE, FUEL_TYPE", "UNIT_NUMBER ASC" );

		if( is_array($choices) && count($choices) > 0) {
			
			$select = '<select class="form-control input-sm" name="'.$id.'" id="'.$id.'"'.($onchange ? ' onchange="form.submit();"' : '').'>
			';
			foreach( $choices as $row ) {
				$select .= '<option value="'.$row["TRACTOR_CODE"].'"';
				if( $selected && $selected == $row["TRACTOR_CODE"] )
					$select .= ' selected';
				$select .= '>'.$row["UNIT_NUMBER"].' ('.$row["ISACTIVE"].', '.$row["FUEL_TYPE"].')</option>
				';
			}
			$select .= '</select>';
		}
			
		return $select;
	}

	public function check_expired( $pk, $wrap = true ) {
		$response = '';
		$check = $this->fetch_rows( $this->primary_key." = ".$pk." AND ISDELETED = false",
			"INSPECTION_EXPIRY, DATEDIFF(INSPECTION_EXPIRY, CURRENT_DATE) INSPECTION_DAYS,
			INSURANCE_EXPIRY, DATEDIFF(INSURANCE_EXPIRY, CURRENT_DATE) INSURANCE_DAYS" );
		if( is_array($check) && count($check) == 1 ) {
			$responses = array();
			if( ! isset($check[0]["INSPECTION_EXPIRY"]) || is_null($check[0]["INSPECTION_EXPIRY"]))
				$responses[] =  '<span class="text-danger"><strong><span class="glyphicon glyphicon-warning-sign"></span></strong> Missing tractor annual inspection date.</span>';
			else if( $check[0]["INSPECTION_DAYS"] <= 0 )
				$responses[] =  '<span class="text-danger"><strong><span class="glyphicon glyphicon-warning-sign"></span></strong> Tractor annual inspection date ('.date("m/d/Y", strtotime($check[0]["INSPECTION_EXPIRY"])).') expired.</span>';
			else if( $check[0]["INSPECTION_DAYS"] < 15 )
				$responses[] =  '<span class="text-inprogress2"><strong><span class="glyphicon glyphicon-warning-sign"></span></strong> Tractor annual inspection date ('.
				date("m/d/Y", strtotime($check[0]["INSPECTION_EXPIRY"])).') due < 15 days.</span>';
			else if( $check[0]["INSPECTION_DAYS"] < 30 )
				$responses[] =  '<span class="text-warning"><strong><span class="glyphicon glyphicon-warning-sign"></span></strong> Tractor annual inspection date ('.date("m/d/Y", strtotime($check[0]["INSPECTION_EXPIRY"])).') due < 30 days.</span>';

			if( ! isset($check[0]["INSURANCE_EXPIRY"]) || is_null($check[0]["INSURANCE_EXPIRY"]))
				$responses[] =  '<span class="text-danger"><strong><span class="glyphicon glyphicon-warning-sign"></span></strong> Missing tractor insurance date.</span>';
			else if( $check[0]["INSURANCE_DAYS"] <= 0 )
				$responses[] =  '<span class="text-danger"><strong><span class="glyphicon glyphicon-warning-sign"></span></strong> Tractor insurance date ('.date("m/d/Y", strtotime($check[0]["INSURANCE_EXPIRY"])).') expired.</span>';
			else if( $check[0]["INSURANCE_DAYS"] < 15 )
				$responses[] =  '<span class="text-inprogress2"><strong><span class="glyphicon glyphicon-warning-sign"></span></strong> Tractor insurance date ('.date("m/d/Y", strtotime($check[0]["INSURANCE_EXPIRY"])).') expires < 15 days.</span>';
			else if( $check[0]["INSURANCE_DAYS"] < 30 )
				$responses[] =  '<span class="text-warning"><strong><span class="glyphicon glyphicon-warning-sign"></span></strong> Tractor insurance date ('.date("m/d/Y", strtotime($check[0]["INSURANCE_EXPIRY"])).') expires < 30 days.</span>';
			
			//! SCR# 522 - Include warnings for overdue reports
			if( $this->inspection_reports ) {
				$this->report_table = sts_insp_report::getInstance($this->database, $this->debug);

				$responses = array_merge($responses,
					$this->report_table->check_expired( 'tractor', $pk ) );
			}
						
			if( count($responses) > 0 ) {
				$response = implode("<br>\n", $responses);
				if( $wrap )
					$response = '<div class="panel panel-default tighter">
  <div class="panel-body" style="padding: 5px;">'.$response.'</div></div>';
  			}
		}
		
		return $response;
	}

	//! Display expired tractors
	public function alert_expired() {
		$result = '';
		
		//! SCR# 544 - only show active if setting is true
		if( $this->default_active )
			$df = " AND ISACTIVE = 'Active'";
		else
			$df = "";

		$expired = $this->database->get_multiple_rows("
			SELECT TRACTOR_CODE, ANAME, ISEXPIRED
			FROM
			(SELECT TRACTOR_CODE, UNIT_NUMBER AS ANAME,
				TRACTOR_EXPIRED(TRACTOR_CODE) ISEXPIRED
			FROM EXP_TRACTOR
			WHERE ISDELETED = 0
			$df) X
			WHERE ISEXPIRED <> 'green'
			ORDER BY FIELD(ISEXPIRED, 'red', 'orange', 'yellow'), TRACTOR_CODE ASC");
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
				$details = $this->check_expired( $row["TRACTOR_CODE"], false );
				$actions[] = '<a class="btn btn-sm btn-'.$colour.' inform" href="exp_edittractor.php?CODE='.$row["TRACTOR_CODE"].'" title="<strong>Tractor: '.$row["ANAME"].'</strong>" data-content=\''.$details.'\'>'.$row["ANAME"].'</a>';
			}
			
			$result = '<p style="margin-bottom: 5px;"><a class="btn btn-sm btn-primary" data-toggle="collapse" href="#collapseTractors" aria-expanded="false" aria-controls="collapseTractors"><span class="glyphicon glyphicon-warning-sign"></span> '.plural(count($expired), ' Tractor').' need attention</a> (red=expired, orange=under 15 days, yellow=needs attention soon)<div id="RMR"></div></p>
			<div class="collapse" id="collapseTractors">
			<p>'.implode(" ", $actions).'</p>
			</div>';

		}		
		return $result;
	}

	//! SCR# 551 - handle fuel cards for tractors
	public function fuel_cards( $pk ) {
		if( $this->debug ) echo "<p>".__METHOD__.": pk = $pk</p>";
		$output = '';
		$check = $this->database->get_multiple_rows("
			SELECT FUEL_CARD_CODE, CARD_SOURCE, UNIT_NUMBER
			FROM EXP_TRACTOR_CARD
			WHERE TRACTOR_CODE = $pk");
		if( is_array($check) && count($check) > 0 ) {
			foreach( $check as $row ) {
				$line[] = '<a class="text-danger delcard" href="exp_driver_card.php?PW=Society&DEL_TRACTOR='.$row["FUEL_CARD_CODE"].'"><span class="glyphicon glyphicon-remove"></span></a> '.$row["CARD_SOURCE"].' / '.$row["UNIT_NUMBER"];
			}
			$output = '<div class="panel panel-info">
			  <div class="panel-heading">
			    <h3 class="panel-title">Fuel Cards Import Mapping</h3>
			  </div>
			  <div class="panel-body">
				'.implode('<br>', $line).'
				</div>
				<div class="panel-footer"><small><span class="glyphicon glyphicon-warning-sign"></span> The above units are mapped during fuel card import and used for IFTA reporting. Removing a mapping here will only impact future fuel card imports.</small></div>
			</div>';
		}
		return $output;
	}
	
	public function del_fuel_card( $pk ) {
		require_once( "sts_card_ftp_class.php" );
		
		$ftp = sts_card_ftp::getInstance($this->database, $this->debug);
		if( $this->debug ) echo "<p>".__METHOD__.": pk = $pk</p>";
		$check = $this->database->get_one_row("SELECT d.UNIT_NUMBER, c.UNIT_NUMBER 
			FROM EXP_TRACTOR_CARD c, EXP_TRACTOR d
			WHERE c.FUEL_CARD_CODE = $pk
			AND c.TRACTOR_CODE = d.TRACTOR_CODE");
		if( is_array($check) && isset($check["UNIT_NUMBER"]) ) {
			$result = $this->database->get_one_row("DELETE FROM EXP_TRACTOR_CARD
				WHERE FUEL_CARD_CODE = $pk");
			$ftp->log_event( __METHOD__.": remove card ".$check["UNIT_NUMBER"].
				" from tractor ".$check["UNIT_NUMBER"].
				", result = ".($result ? 'true' : 'false'), EXT_ERROR_DEBUG);
		}
	}
	
	//! SCR# 240 - get address of the last stop for this tractor
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
			FROM EXP_STOP S, EXP_TRACTOR T
			WHERE T.TRACTOR_CODE = $pk
            AND T.LAST_STOP = S.STOP_CODE) S
            LEFT JOIN
			EXP_SHIPMENT T
			ON T.SHIPMENT_CODE = S.SHIPMENT");

		return $result;		
	}

}

//! Form Specifications - For use with sts_form

$sts_form_addtractor_form = array(	//! sts_form_addtractor_form
	'title' => '<img src="images/tractor_icon.png" alt="tractor_icon" height="24"> Add Tractor',
	'action' => 'exp_addtractor.php',
	//'actionextras' => 'disabled',
	'cancel' => 'exp_listtractor.php',
	'name' => 'addtractor',
	'okbutton' => 'Save Changes',
	'saveadd' => 'Add Another',
	'cancelbutton' => 'Cancel',
		'layout' => '
	<div class="form-group">
		<div class="col-sm-4">
			<div class="form-group tighter">
				<label for="UNIT_NUMBER" class="col-sm-4 control-label">#UNIT_NUMBER#</label>
				<div class="col-sm-8">
					%UNIT_NUMBER%
				</div>
			</div>
			<div class="form-group tighter">
				<label for="TRACTOR_OWNER" class="col-sm-4 control-label">#TRACTOR_OWNER#</label>
				<div class="col-sm-8">
					%TRACTOR_OWNER%
				</div>
			</div>
			<div class="form-group tighter">
				<label for="OWNERSHIP_TYPE" class="col-sm-4 control-label">#OWNERSHIP_TYPE#</label>
				<div class="col-sm-8">
					%OWNERSHIP_TYPE%
				</div>
			</div>
			<!-- CC01 -->
			<div class="form-group tighter">
				<label for="COMPANY_CODE" class="col-sm-4 control-label">#COMPANY_CODE#</label>
				<div class="col-sm-8">
					%COMPANY_CODE%
				</div>
			</div>
			<!-- CC02 -->
			<!-- FL01 -->
			<div class="form-group tighter">
				<label for="FLEET_CODE" class="col-sm-4 control-label">#FLEET_CODE#</label>
				<div class="col-sm-8">
					%FLEET_CODE%
				</div>
			</div>
			<!-- FL02 -->
			<!-- IFTA01 -->
			<div class="form-group tighter">
				<label for="LOG_IFTA" class="col-sm-4 control-label">#LOG_IFTA#</label>
				<div class="col-sm-8">
					%LOG_IFTA%
				</div>
			</div>
			<!-- IFTA02 -->
			<div class="form-group tighter">
				<label for="TRACTOR_CLASS" class="col-sm-4 control-label">#TRACTOR_CLASS#</label>
				<div class="col-sm-8">
					%TRACTOR_CLASS%
				</div>
			</div>
			<div class="form-group tighter">
				<label for="RM_CLASS" class="col-sm-4 control-label">#RM_CLASS#</label>
				<div class="col-sm-8">
					%RM_CLASS%
				</div>
			</div>
			<div class="form-group tighter">
				<label for="INSPECTION_EXPIRY" class="col-sm-4 control-label">#INSPECTION_EXPIRY#</label>
				<div class="col-sm-8">
					%INSPECTION_EXPIRY%
				</div>
			</div>
			<div class="form-group tighter">
				<label for="INSURANCE_EXPIRY" class="col-sm-4 control-label">#INSURANCE_EXPIRY#</label>
				<div class="col-sm-8">
					%INSURANCE_EXPIRY%
				</div>
			</div>
		</div>
		<div class="col-sm-4">
			<div class="form-group tighter">
				<label for="TRACTOR_YEAR" class="col-sm-4 control-label">#TRACTOR_YEAR#</label>
				<div class="col-sm-8">
					%TRACTOR_YEAR%
				</div>
			</div>
			<div class="form-group tighter">
				<label for="MAKE" class="col-sm-4 control-label">#MAKE#</label>
				<div class="col-sm-8">
					%MAKE%
				</div>
			</div>
			<div class="form-group tighter">
				<label for="MODEL" class="col-sm-4 control-label">#MODEL#</label>
				<div class="col-sm-8">
					%MODEL%
				</div>
			</div>
			<div class="form-group tighter">
				<label for="VIN_NUMBER" class="col-sm-4 control-label">#VIN_NUMBER#</label>
				<div class="col-sm-8">
					%VIN_NUMBER%
				</div>
			</div>
			<div class="form-group tighter">
				<label for="PLATE" class="col-sm-4 control-label">#PLATE#</label>
				<div class="col-sm-8">
					%PLATE%
				</div>
			</div>
			<div class="form-group tighter">
				<label for="GVWR" class="col-sm-4 control-label">#GVWR#</label>
				<div class="col-sm-8">
					%GVWR%
				</div>
			</div>
			<div class="form-group tighter">
				<label for="WEIGHT" class="col-sm-4 control-label">#WEIGHT#</label>
				<div class="col-sm-8">
					%WEIGHT%
				</div>
			</div>
			<div class="form-group tighter">
				<label for="TRACTOR_ENGINE" class="col-sm-4 control-label">#TRACTOR_ENGINE#</label>
				<div class="col-sm-8">
					%TRACTOR_ENGINE%
				</div>
			</div>
			<div class="form-group tighter">
				<label for="FUEL_TYPE" class="col-sm-4 control-label">#FUEL_TYPE#</label>
				<div class="col-sm-8">
					%FUEL_TYPE%
				</div>
			</div>
			<div class="form-group tighter">
				<label for="APU" class="col-sm-4 control-label">#APU#</label>
				<div class="col-sm-8">
					%APU%
				</div>
			</div>
		</div>
		<div class="col-sm-4">
			<div class="form-group tighter">
				<label for="TRACTOR_BITMAP" class="col-sm-3 control-label">#TRACTOR_BITMAP#</label>
				<div class="col-sm-9">
					%TRACTOR_BITMAP%
				</div>
			</div>
			<div class="alert alert-info">
				<div class="form-group tighter">
					<label for="UNITS" class="col-sm-6 control-label">#UNITS#</label>
					<div class="col-sm-6">
						%UNITS%
					</div>
				</div>
				<p>imperial = miles/gallons<br>metric = kilmetres/litres</p>
			</div>
		</div>
	</div>
	
	'
);

$sts_form_edittractor_form = array( //! sts_form_edittractor_form
	'title' => '<img src="images/tractor_icon.png" alt="tractor_icon" height="24"> Edit Tractor',
	'action' => 'exp_edittractor.php',
	//'actionextras' => 'disabled',
	'cancel' => 'exp_listtractor.php',
	'name' => 'edittractor',
	'okbutton' => 'Save Changes to Tractor',
	'saveadd' => 'Add Another',
	'buttons' => array( 
		array( 'label' => 'Fuel Log', 'link' => 'exp_editifta_log.php?TRACTOR_CODE=%TRACTOR_CODE%',
		'button' => 'default', 'icon' => '<span class="glyphicon glyphicon-save"></span>' ),
	),
	'cancelbutton' => 'Back to Tractors',
		'layout' => '
		%TRACTOR_CODE%
	<div id="warnings"></div>
	<div class="form-group">
		<div class="col-sm-4">
			<div class="form-group tighter">
				<label for="UNIT_NUMBER" class="col-sm-4 control-label">#UNIT_NUMBER#</label>
				<div class="col-sm-8">
					%UNIT_NUMBER%
				</div>
			</div>
			<div class="form-group tighter">
				<label for="TRACTOR_OWNER" class="col-sm-4 control-label">#TRACTOR_OWNER#</label>
				<div class="col-sm-8">
					%TRACTOR_OWNER%
				</div>
			</div>
			<div class="form-group tighter">
				<label for="OWNERSHIP_TYPE" class="col-sm-4 control-label">#OWNERSHIP_TYPE#</label>
				<div class="col-sm-8">
					%OWNERSHIP_TYPE%
				</div>
			</div>
			<!-- CC01 -->
			<div class="form-group tighter">
				<label for="COMPANY_CODE" class="col-sm-4 control-label">#COMPANY_CODE#</label>
				<div class="col-sm-8">
					%COMPANY_CODE%
				</div>
			</div>
			<!-- CC02 -->
			<!-- FL01 -->
			<div class="form-group tighter">
				<label for="FLEET_CODE" class="col-sm-4 control-label">#FLEET_CODE#</label>
				<div class="col-sm-8">
					%FLEET_CODE%
				</div>
			</div>
			<!-- FL02 -->
			<!-- IFTA01 -->
			<div class="form-group tighter">
				<label for="LOG_IFTA" class="col-sm-4 control-label">#LOG_IFTA#</label>
				<div class="col-sm-8">
					%LOG_IFTA%
				</div>
			</div>
			<!-- IFTA02 -->
			<div class="form-group tighter">
				<label for="DRIVER" class="col-sm-4 control-label">#DRIVER#</label>
				<div class="col-sm-8">
					%DRIVER%
				</div>
			</div>
			<div class="form-group tighter">
				<label for="TRACTOR_CLASS" class="col-sm-4 control-label">#TRACTOR_CLASS#</label>
				<div class="col-sm-8">
					%TRACTOR_CLASS%
				</div>
			</div>
			<div class="form-group tighter">
				<label for="RM_CLASS" class="col-sm-4 control-label">#RM_CLASS#</label>
				<div class="col-sm-8">
					%RM_CLASS%
				</div>
			</div>
			<div class="form-group tighter">
				<label for="ISACTIVE" class="col-sm-4 control-label">#ISACTIVE#</label>
				<div class="col-sm-8">
					%ISACTIVE%
				</div>
			</div>
			<div class="form-group tighter" id="OOS" hidden>
				<label for="OOS_TYPE" class="col-sm-4 control-label">#OOS_TYPE#</label>
				<div class="col-sm-8">
					%OOS_TYPE%
				</div>
			</div>
			<div class="form-group tighter">
				<label for="INSPECTION_EXPIRY" class="col-sm-4 control-label">#INSPECTION_EXPIRY#</label>
				<div class="col-sm-8">
					%INSPECTION_EXPIRY%
				</div>
			</div>
			<div class="form-group tighter">
				<label for="INSURANCE_EXPIRY" class="col-sm-4 control-label">#INSURANCE_EXPIRY#</label>
				<div class="col-sm-8">
					%INSURANCE_EXPIRY%
				</div>
			</div>
		</div>
		<div class="col-sm-4">
			<div class="form-group tighter">
				<label for="TRACTOR_YEAR" class="col-sm-4 control-label">#TRACTOR_YEAR#</label>
				<div class="col-sm-8">
					%TRACTOR_YEAR%
				</div>
			</div>
			<div class="form-group tighter">
				<label for="MAKE" class="col-sm-4 control-label">#MAKE#</label>
				<div class="col-sm-8">
					%MAKE%
				</div>
			</div>
			<div class="form-group tighter">
				<label for="MODEL" class="col-sm-4 control-label">#MODEL#</label>
				<div class="col-sm-8">
					%MODEL%
				</div>
			</div>
			<div class="form-group tighter">
				<label for="VIN_NUMBER" class="col-sm-4 control-label">#VIN_NUMBER#</label>
				<div class="col-sm-8">
					%VIN_NUMBER%
				</div>
			</div>
			<div class="form-group tighter">
				<label for="PLATE" class="col-sm-4 control-label">#PLATE#</label>
				<div class="col-sm-8">
					%PLATE%
				</div>
			</div>
			<div class="form-group tighter">
				<label for="GVWR" class="col-sm-4 control-label">#GVWR#</label>
				<div class="col-sm-8">
					%GVWR%
				</div>
			</div>
			<div class="form-group tighter">
				<label for="WEIGHT" class="col-sm-4 control-label">#WEIGHT#</label>
				<div class="col-sm-8">
					%WEIGHT%
				</div>
			</div>
			<div class="form-group tighter">
				<label for="TRACTOR_ENGINE" class="col-sm-4 control-label">#TRACTOR_ENGINE#</label>
				<div class="col-sm-8">
					%TRACTOR_ENGINE%
				</div>
			</div>
			<div class="form-group tighter">
				<label for="FUEL_TYPE" class="col-sm-4 control-label">#FUEL_TYPE#</label>
				<div class="col-sm-8">
					%FUEL_TYPE%
				</div>
			</div>
			<div class="form-group tighter">
				<label for="APU" class="col-sm-4 control-label">#APU#</label>
				<div class="col-sm-8">
					%APU%
				</div>
			</div>
			<div class="driver_only" id="FUEL_CARDS">
			</div>
		</div>
		<div class="col-sm-4">
			<div class="form-group tighter">
				<label for="TRACTOR_BITMAP" class="col-sm-3 control-label">#TRACTOR_BITMAP#</label>
				<div class="col-sm-9">
					%TRACTOR_BITMAP%
				</div>
			</div>
			
			<div class="form-group">
				<label for="MOBILE_LOCATION" class="col-sm-4 control-label">Location</label>
				<div class="col-sm-8">
					<a href="https://www.google.com/maps/@%MOBILE_LATITUDE%,%MOBILE_LONGITUDE%,16z?hl=en" target="_blank">%MOBILE_LOCATION% <span class="glyphicon glyphicon-new-window"></span></a>
				</div>
			</div>
			<div class="form-group">
				<label for="MOBILE_TIME" class="col-sm-4 control-label">#MOBILE_TIME#</label>
				<div class="col-sm-8">
					%MOBILE_TIME%
				</div>
			</div>

			<div class="alert alert-info">
				<div class="form-group tighter">
					<label for="UNITS" class="col-sm-6 control-label">#UNITS#</label>
					<div class="col-sm-6">
						%UNITS%
					</div>
				</div>
				<p>imperial = miles/gallons<br>metric = kilmetres/litres</p>
			</div>
		</div>
	</div>
	
	'
);

//! Field Specifications - For use with sts_form

$sts_form_add_tractor_fields = array( //! $sts_form_add_tractor_fields
	'UNIT_NUMBER' => array( 'label' => 'Unit#', 'format' => 'text', 'extras' => 'required' ),
	'TRACTOR_OWNER' => array( 'label' => 'Owner', 'format' => 'text' ),
	'OWNERSHIP_TYPE' => array( 'label' => 'Type', 'format' => 'enum' ),
	'UNITS' => array( 'label' => 'Units', 'format' => 'enum' ),
	'COMPANY_CODE' => array( 'label' => 'Company', 'format' => 'table',
		'table' => COMPANY_TABLE, 'key' => 'COMPANY_CODE', 'fields' => 'COMPANY_NAME' ),
	'FLEET_CODE' => array( 'label' => 'Fleet', 'format' => 'table',
		'table' => FLEET_TABLE, 'key' => 'FLEET_CODE', 'fields' => 'FLEET_NAME' ),
	'LOG_IFTA' => array( 'label' => 'IFTA', 'format' => 'bool' ),
	'FUEL_TYPE' => array( 'label' => 'Fuel', 'format' => 'enum' ),
	'TRACTOR_CLASS' => array( 'label' => 'Class', 'format' => 'text' ),
	'RM_CLASS' => array( 'label' => 'R&M Class', 'format' => 'table',
		'table' => RM_CLASS_TABLE, 'key' => 'CLASS_CODE', 'fields' => 'CLASS_NAME',
		'condition' => 'UNIT_TYPE = \'tractor\'' ),
	'TRACTOR_YEAR' => array( 'label' => 'Year', 'format' => 'text' ),
	'MAKE' => array( 'label' => 'Make', 'format' => 'text' ),
	'MODEL' => array( 'label' => 'Model', 'format' => 'text' ),
	'VIN_NUMBER' => array( 'label' => 'VIN', 'format' => 'text' ),
	'GVWR' => array( 'label' => 'GVWR', 'format' => 'number' ),
	'PLATE' => array( 'label' => 'Plate', 'format' => 'text' ),
	//'MOBILE_VEHICLE_ID' => array( 'label' => 'Mobile#', 'format' => 'text' ),
	'INSURANCE_EXPIRY' => array( 'label' => 'Plate Expiry', 'format' => 'date' ),
	'INSPECTION_EXPIRY' => array( 'label' => 'Annual Inspection', 'format' => 'date' ),
	'WEIGHT' => array( 'label' => 'Weight', 'format' => 'number' ),
	'TRACTOR_ENGINE' => array( 'label' => 'Engine', 'format' => 'text' ),
	'APU' => array( 'label' => 'APU', 'format' => 'enum' ),
	'TRACTOR_BITMAP' => array( 'label' => 'Image', 'format' => 'image' )
);

$sts_form_edit_tractor_fields = array( //! $sts_form_edit_tractor_fields
	'TRACTOR_CODE' => array( 'format' => 'hidden' ),	// primary key must be first
	'UNIT_NUMBER' => array( 'label' => 'Unit#', 'format' => 'text', 'extras' => 'required' ),
	'ISACTIVE' => array( 'label' => 'Active', 'format' => 'enum',
		'extras' => 'required' ),
	'OOS_TYPE' => array( 'label' => 'OOS Type', 'format' => 'enum' ),
	'TRACTOR_OWNER' => array( 'label' => 'Owner', 'format' => 'text' ),
	'OWNERSHIP_TYPE' => array( 'label' => 'Type', 'format' => 'enum' ),
	'UNITS' => array( 'label' => 'Units', 'format' => 'enum' ),
	'COMPANY_CODE' => array( 'label' => 'Company', 'format' => 'table',
		'table' => COMPANY_TABLE, 'key' => 'COMPANY_CODE', 'fields' => 'COMPANY_NAME' ),
	'FLEET_CODE' => array( 'label' => 'Fleet', 'format' => 'table',
		'table' => FLEET_TABLE, 'key' => 'FLEET_CODE', 'fields' => 'FLEET_NAME' ),
	'LOG_IFTA' => array( 'label' => 'IFTA', 'format' => 'bool' ),
	'FUEL_TYPE' => array( 'label' => 'Fuel', 'format' => 'enum' ),
	'TRACTOR_CLASS' => array( 'label' => 'Class', 'format' => 'text' ),
	'RM_CLASS' => array( 'label' => 'R&M Class', 'format' => 'table',
		'table' => RM_CLASS_TABLE, 'key' => 'CLASS_CODE', 'fields' => 'CLASS_NAME',
		'condition' => 'UNIT_TYPE = \'tractor\'' ),
	'TRACTOR_YEAR' => array( 'label' => 'Year', 'format' => 'text' ),
	'MAKE' => array( 'label' => 'Make', 'format' => 'text' ),
	'MODEL' => array( 'label' => 'Model', 'format' => 'text' ),
	'VIN_NUMBER' => array( 'label' => 'VIN', 'format' => 'text' ),
	'GVWR' => array( 'label' => 'GVWR', 'format' => 'text' ),
	'PLATE' => array( 'label' => 'Plate', 'format' => 'text' ),
	//'MOBILE_VEHICLE_ID' => array( 'label' => 'Mobile#', 'format' => 'text' ),
	'DRIVER' => array( 'label' => 'Driver', 'format' => 'btable',
		'table' => DRIVER_TABLE, 'key' => 'DEFAULT_TRACTOR', 
		'fields' => 'concat_ws( \' \', FIRST_NAME , LAST_NAME )',
		'pk' => 'TRACTOR_CODE' ),
	//'LOCATION' => array( 'label' => 'Location', 'format' => 'text' ),
	//'ZONE' => array( 'label' => 'Zone', 'format' => 'text' ),
	//'TRACTOR_STATUS' => array( 'label' => 'Status', 'format' => 'text' ),
	'INSURANCE_EXPIRY' => array( 'label' => 'Plate Expiry', 'format' => 'date' ),
	'INSPECTION_EXPIRY' => array( 'label' => 'Annual Inspection', 'format' => 'date' ),
	'WEIGHT' => array( 'label' => 'Weight', 'format' => 'text' ),
	'TRACTOR_ENGINE' => array( 'label' => 'Engine', 'format' => 'text' ),
	'MOBILE_TIME' => array( 'label' => 'As Of', 'format' => 'datetime', 'extras' => 'readonly' ),
	'MOBILE_LOCATION' => array( 'label' => 'Year', 'format' => 'inline' ),
	'MOBILE_LATITUDE' => array( 'label' => 'Year', 'format' => 'inline' ),
	'MOBILE_LONGITUDE' => array( 'label' => 'Year', 'format' => 'inline' ),
	'APU' => array( 'label' => 'APU', 'format' => 'enum' ),
	'TRACTOR_BITMAP' => array( 'label' => 'Image', 'format' => 'image' )

);

//! Layout Specifications - For use with sts_result

$sts_result_tractors_layout = array(
	'TRACTOR_CODE' => array( 'format' => 'hidden' ),
	'EXPIRED' => array( 'format' => 'hidden', 'snippet' => 'TRACTOR_EXPIRED(TRACTOR_CODE)' ),
	'UNIT_NUMBER' => array( 'label' => 'Unit#', 'format' => 'text', 'searchable' => true ),
	'ISACTIVE' => array( 'label' => 'Active', 'align' => 'center', 'format' => 'text' ),
	'ISDELETED' => array( 'label' => 'Del', 'align' => 'center', 'format' => 'hidden' ),
	'OWNERSHIP_TYPE' => array( 'label' => 'Type', 'format' => 'text' ),
	'TRACTOR_OWNER' => array( 'label' => 'Owner', 'format' => 'text' ),
	'COMPANY_CODE' => array( 'label' => 'Company', 'format' => 'table',
		'table' => COMPANY_TABLE, 'key' => 'COMPANY_CODE', 'fields' => 'COMPANY_NAME' ),
	'FLEET_CODE' => array( 'label' => 'Fleet', 'format' => 'table',
		'table' => FLEET_TABLE, 'key' => 'FLEET_CODE', 'fields' => 'FLEET_NAME' ),
	'LOG_IFTA' => array( 'label' => 'IFTA', 'format' => 'bool' ),
	'RM_CLASS' => array( 'label' => 'Class', 'format' => 'table',
		'table' => RM_CLASS_TABLE, 'key' => 'CLASS_CODE', 'fields' => 'CLASS_NAME',
		'condition' => 'UNIT_TYPE = \'tractor\'' ),
	'TRACTOR_YEAR' => array( 'label' => 'Year', 'format' => 'text' ),
	'MAKE' => array( 'label' => 'Make', 'format' => 'text', 'searchable' => true ),
	'MODEL' => array( 'label' => 'Model', 'format' => 'text', 'searchable' => true ),
	'VIN_NUMBER' => array( 'label' => 'VIN', 'format' => 'text', 'searchable' => true ),
	//'GVWR' => array( 'label' => 'GVWR', 'format' => 'text' ),
	'DRIVER' => array( 'label' => 'Driver', 'format' => 'btable',
		'table' => DRIVER_TABLE, 'key' => 'DEFAULT_TRACTOR', 
		'fields' => 'concat_ws( \' \', FIRST_NAME , LAST_NAME )',
		'pk' => 'TRACTOR_CODE' ),
	//'LOCATION' => array( 'label' => 'Location', 'format' => 'text' ),
	//'ZONE' => array( 'label' => 'Zone', 'format' => 'text' ),
	//'TRACTOR_STATUS' => array( 'label' => 'Status', 'format' => 'text' ),
	'MOBILE_TIME' => array( 'label' => 'Mobile Time', 'format' => 'datetime' ),
	'MOBILE_LOCATION' => array( 'label' => 'Mobile Location', 'format' => 'text' ),
	//'INSURANCE_EXPIRY' => array( 'label' => 'Ins Expiry', 'format' => 'date' ),
	'WEIGHT' => array( 'label' => 'Weight', 'format' => 'text' ),
	'TRACTOR_ENGINE' => array( 'label' => 'Engine', 'format' => 'text' ),
	//'APU' => array( 'label' => 'APU', 'format' => 'text' ),
	'CHANGED_DATE' => array( 'label' => 'Changed', 'format' => 'timestamp-s', 'length' => 90 ),
	'CHANGED_BY' => array( 'label' => 'By', 'format' => 'table',
		'table' => USER_TABLE, 'key' => 'USER_CODE', 'fields' => 'USERNAME', 'link' => 'exp_edituser.php?CODE=' )

);

//! Edit/Delete Button Specifications - For use with sts_result

$sts_result_tractors_edit = array(
	'title' => '<img src="images/tractor_icon.png" alt="tractor_icon" height="24"> Tractors',
	'sort' => 'UNIT_NUMBER asc',
	'cancel' => 'index.php',
	'add' => 'exp_addtractor.php',
	//'actionextras' => 'disabled',
	'addbutton' => 'Add Tractor',
	'cancelbutton' => 'Back',
	'rowbuttons' => array(
		array( 'url' => 'exp_edittractor.php?CODE=', 'key' => 'TRACTOR_CODE', 'label' => 'UNIT_NUMBER', 'tip' => 'Edit tractor ', 'icon' => 'glyphicon glyphicon-edit', 'showif' => 'notdeleted' ),
		array( 'url' => 'exp_editifta_log.php?TRACTOR_CODE=', 'key' => 'TRACTOR_CODE', 'label' => 'UNIT_NUMBER', 'tip' => 'Fuel log for ', 'icon' => 'glyphicon glyphicon-save', 'showif' => 'notdeleted' ),
		array( 'url' => 'exp_deletetractor.php?TYPE=del&CODE=', 'key' => 'TRACTOR_CODE', 'label' => 'UNIT_NUMBER', 'tip' => 'Delete tractor ', 'icon' => 'glyphicon glyphicon-trash', 'confirm' => 'yes', 'showif' => 'notdeleted' ),
		array( 'url' => 'exp_deletetractor.php?TYPE=undel&CODE=', 'key' => 'TRACTOR_CODE', 'label' => 'UNIT_NUMBER', 'tip' => 'Undelete tractor ', 'icon' => 'glyphicon glyphicon-arrow-left', 'showif' => 'deleted' ),
		array( 'url' => 'exp_deletetractor.php?TYPE=permdel&CODE=', 'key' => 'TRACTOR_CODE', 'label' => 'UNIT_NUMBER', 'tip' => 'Permanently Delete tractor ', 'icon' => 'glyphicon glyphicon-remove', 'confirm' => 'yes', 'showif' => 'deleted' )
	),
);

//! Use this for parsing CSV files & importing tractors
$sts_csv_tractor_parse = array(
	array( 'table' => TRACTOR_TABLE, 'class' => 'sts_tractor', 'primary' => 'TRACTOR_CODE',
		'rows' => array(
			'UNIT_NUMBER' => array( 'label' => 'UnitNumber', 'format' => 'text',
				'required' => true ),
			'ISACTIVE' => array( 'value' => 'Active' ),
			'TRACTOR_OWNER' => array( 'label' => 'Owner', 'format' => 'text' ),
			'OWNERSHIP_TYPE' => array( 'label' => 'OwnershipType',
				'format' => 'enum' ),
			'UNITS' => array( 'label' => 'Units', 'format' => 'enum' ),
			'COMPANY_CODE' => array( 'value' => 0 ),
			'LOG_IFTA' => array( 'label' => 'IFTA', 'format' => 'bool' ),
			'FUEL_TYPE' => array( 'label' => 'Fuel', 'format' => 'enum' ),
			'TRACTOR_CLASS' => array( 'label' => 'Class', 'format' => 'text' ),
			'TRACTOR_YEAR' => array( 'label' => 'Year', 'format' => 'text' ),
			'MAKE' => array( 'label' => 'Make', 'format' => 'text' ),
			'MODEL' => array( 'label' => 'Model', 'format' => 'text' ),
			'VIN_NUMBER' => array( 'label' => 'VIN', 'format' => 'text' ),
			'GVWR' => array( 'label' => 'GVWR', 'format' => 'text' ),
			'PLATE' => array( 'label' => 'Plate', 'format' => 'text' ),
			'MOBILE_VEHICLE_ID' => array( 'label' => 'MobileID', 'format' => 'text' ),
			'INSURANCE_EXPIRY' => array( 'label' => 'PlateExpiry', 'format' => 'date' ),
			'INSPECTION_EXPIRY' => array( 'label' => 'AnnualInspection',
				'format' => 'date' ),
			'WEIGHT' => array( 'label' => 'Weight', 'format' => 'text' ),
			'TRACTOR_ENGINE' => array( 'label' => 'Engine', 'format' => 'text' ),
			'COMDATA_UNIT' => array( 'label' => 'ComdataUnit', 'format' => 'text' ),
			'BVD_UNIT' => array( 'label' => 'BVDUnit', 'format' => 'text' ),
			'APU' => array( 'label' => 'APU', 'format' => 'enum' ),
		),
	),
);
?>
