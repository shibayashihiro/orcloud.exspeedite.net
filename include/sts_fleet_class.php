<?php

// $Id: sts_fleet_class.php 4350 2021-03-02 19:14:52Z duncan $
// Fleet class - new fleet feature

// no direct access
defined('_STS_INCLUDE') or die('Restricted access');

require_once( "sts_table_class.php" );
require_once( "sts_setting_class.php" );

class sts_fleet extends sts_table {

	private $setting;

	// Constructor does not need the table name
	public function __construct( $database, $debug = false ) {

		$this->debug = $debug;
		$this->primary_key = "FLEET_CODE";
		if( $this->debug ) echo "<p>Create sts_fleet</p>";
		parent::__construct( $database, FLEET_TABLE, $debug);
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

    //! create a menu of available fleets
    public function menu( $selected = false, $id = 'IFTA_FLEET', $match = '', $onchange = true, $any = false ) {
		if( $this->debug ) echo "<p>".__METHOD__.": entry</p>";
		$select = false;
	
		$choices = $this->fetch_rows( $match, "FLEET_CODE, FLEET_NAME", "FLEET_NAME ASC" );

		if( is_array($choices) && count($choices) > 0) {
			
			$select = '<select class="form-control input-sm" name="'.$id.'" id="'.$id.'"'.($onchange ? ' onchange="form.submit();"' : '').'>
			';
			if( $any ) {
				$select .= '<option value="0"';
				if( $selected && $selected == 0 )
					$select .= ' selected';
				$select .= '>All Fleets</option>
				';
			}
			foreach( $choices as $row ) {
				$select .= '<option value="'.$row["FLEET_CODE"].'"';
				if( $selected && $selected == $row["FLEET_CODE"] )
					$select .= ' selected';
				$select .= '>'.$row["FLEET_NAME"].'</option>
				';
			}
			$select .= '</select>';
		}
			
		return $select;
	}

    //! Get the IFTA base jurisdiction
    public function ifta_base() {
	    global $_SESSION;
		if( $this->debug ) echo "<p>".__METHOD__.": entry</p>";
	    
		if( isset($_SESSION['IFTA_FLEET']) && $_SESSION['IFTA_FLEET'] > 0 ) {
			$check = $this->fetch_rows($this->primary_key." = ".$_SESSION['IFTA_FLEET'],
				"IFTA_BASE_JURISDICTION" );
			if( is_array($check) && count($check) == 1 &&
				isset($check[0]["IFTA_BASE_JURISDICTION"]))
				$base = $check[0]["IFTA_BASE_JURISDICTION"];
			else
				$base = $this->setting->get( 'api', 'IFTA_BASE_JURISDICTION' );
		} else {
			$base = $this->setting->get( 'api', 'IFTA_BASE_JURISDICTION' );
		}
		
		if( $this->debug ) echo "<p>".__METHOD__.": base = $base</p>";
		return $base;
    }

}

//! Form Specifications - For use with sts_form

$sts_form_add_fleet_form = array(	//! $sts_form_add_fleet_form
	'title' => '<img src="images/tractor_icon.png" alt="setting_icon" height="24"> Add Tractor Fleet',
	'action' => 'exp_addfleet.php',
	//'actionextras' => 'disabled',
	'cancel' => 'exp_listfleet.php',
	'name' => 'addfleet',
	'okbutton' => 'Save Changes',
	'saveadd' => 'Add Another',
	'cancelbutton' => 'Cancel',
		'layout' => '
	<div class="form-group">
		<div class="col-sm-6">
			<!-- CC01 -->
			<div class="form-group tighter">
				<label for="COMPANY_CODE" class="col-sm-4 control-label">#COMPANY_CODE#</label>
				<div class="col-sm-6">
					%COMPANY_CODE%
				</div>
			</div>
			<!-- CC02 -->
			<div class="form-group tighter">
				<label for="FLEET_NAME" class="col-sm-4 control-label">#FLEET_NAME#</label>
				<div class="col-sm-6">
					%FLEET_NAME%
				</div>
			</div>
			<div class="form-group tighter">
				<label for="TAX_ID" class="col-sm-4 control-label">#TAX_ID#</label>
				<div class="col-sm-6">
					%TAX_ID%
				</div>
			</div>
			<div class="form-group tighter">
				<label for="IFTA_BASE_JURISDICTION" class="col-sm-4 control-label">#IFTA_BASE_JURISDICTION#</label>
				<div class="col-sm-6">
					%IFTA_BASE_JURISDICTION%
				</div>
			</div>
		</div>
		<div class="col-sm-6">
			<div class="form-group tighter">
				<label for="ADDRESS" class="col-sm-4 control-label">#ADDRESS#</label>
				<div class="col-sm-6">
					%ADDRESS%
				</div>
			</div>
			<div class="form-group tighter">
				<label for="ADDRESS2" class="col-sm-4 control-label">#ADDRESS2#</label>
				<div class="col-sm-6">
					%ADDRESS2%
				</div>
			</div>
			<div class="form-group tighter">
				<label for="CITY" class="col-sm-4 control-label">#CITY#</label>
				<div class="col-sm-6">
					%CITY%
				</div>
			</div>
			<div class="form-group tighter">
				<label for="STATE" class="col-sm-4 control-label">#STATE#</label>
				<div class="col-sm-6">
					%STATE%
				</div>
			</div>
			<div class="form-group tighter">
				<label for="ZIP_CODE" class="col-sm-4 control-label">#ZIP_CODE#</label>
				<div class="col-sm-6">
					%ZIP_CODE%
				</div>
			</div>
			<div class="form-group tighter">
				<label for="COUNTRY" class="col-sm-4 control-label">#COUNTRY#</label>
				<div class="col-sm-6">
					%COUNTRY%
				</div>
			</div>
		</div>
		<div class="col-sm-10 col-sm-offset-1">
			<div class="form-group well well-md tighter">
				<h3>#IFTA_REG_JURISDICTION#</h3>
					%IFTA_REG_JURISDICTION%
			</div>
		</div>
	</div>
	
	'
);

$sts_form_edit_fleet_form = array( //! $sts_form_edit_fleet_form
	'title' => '<img src="images/tractor_icon.png" alt="setting_icon" height="24"> Edit Tractor Fleet',
	'action' => 'exp_editfleet.php',
	//'actionextras' => 'disabled',
	'cancel' => 'exp_listfleet.php',
	'name' => 'editfleet',
	'okbutton' => 'Save Changes',
	'cancelbutton' => 'Back',
	'layout' => '
		%FLEET_CODE%
	<div class="form-group">
		<div class="col-sm-6">
			<!-- CC01 -->
			<div class="form-group tighter">
				<label for="COMPANY_CODE" class="col-sm-4 control-label">#COMPANY_CODE#</label>
				<div class="col-sm-6">
					%COMPANY_CODE%
				</div>
			</div>
			<!-- CC02 -->
			<div class="form-group tighter">
				<label for="FLEET_NAME" class="col-sm-4 control-label">#FLEET_NAME#</label>
				<div class="col-sm-6">
					%FLEET_NAME%
				</div>
			</div>
			<div class="form-group tighter">
				<label for="TAX_ID" class="col-sm-4 control-label">#TAX_ID#</label>
				<div class="col-sm-6">
					%TAX_ID%
				</div>
			</div>
			<div class="form-group tighter">
				<label for="IFTA_BASE_JURISDICTION" class="col-sm-4 control-label">#IFTA_BASE_JURISDICTION#</label>
				<div class="col-sm-6">
					%IFTA_BASE_JURISDICTION%
				</div>
			</div>
		</div>
		<div class="col-sm-6">
			<div class="form-group tighter">
				<label for="ADDRESS" class="col-sm-4 control-label">#ADDRESS#</label>
				<div class="col-sm-6">
					%ADDRESS%
				</div>
			</div>
			<div class="form-group tighter">
				<label for="ADDRESS2" class="col-sm-4 control-label">#ADDRESS2#</label>
				<div class="col-sm-6">
					%ADDRESS2%
				</div>
			</div>
			<div class="form-group tighter">
				<label for="CITY" class="col-sm-4 control-label">#CITY#</label>
				<div class="col-sm-6">
					%CITY%
				</div>
			</div>
			<div class="form-group tighter">
				<label for="STATE" class="col-sm-4 control-label">#STATE#</label>
				<div class="col-sm-6">
					%STATE%
				</div>
			</div>
			<div class="form-group tighter">
				<label for="ZIP_CODE" class="col-sm-4 control-label">#ZIP_CODE#</label>
				<div class="col-sm-6">
					%ZIP_CODE%
				</div>
			</div>
			<div class="form-group tighter">
				<label for="COUNTRY" class="col-sm-4 control-label">#COUNTRY#</label>
				<div class="col-sm-6">
					%COUNTRY%
				</div>
			</div>
		</div>
		<div class="col-sm-10 col-sm-offset-1">
			<div class="form-group well well-md tighter">
				<h3>#IFTA_REG_JURISDICTION#</h3>
					%IFTA_REG_JURISDICTION%
			</div>
		</div>
	</div>
	
	'
);

//! Field Specifications - For use with sts_form

$sts_form_add_fleet_fields = array( //! $sts_form_add_fleet_fields
	'COMPANY_CODE' => array( 'label' => 'Company', 'format' => 'table',
		'table' => COMPANY_TABLE, 'key' => 'COMPANY_CODE', 'fields' => 'COMPANY_NAME' ),
	'FLEET_NAME' => array( 'label' => 'Name', 'format' => 'text' ),
	'IFTA_BASE_JURISDICTION' => array( 'label' => 'IFTA Base', 'format' => 'state' ),
	'ADDRESS' => array( 'label' => 'Addr', 'format' => 'text' ),
	'ADDRESS2' => array( 'label' => 'Addr2', 'format' => 'text' ),
	'CITY' => array( 'label' => 'City', 'format' => 'text' ),
	'STATE' => array( 'label' => 'State', 'format' => 'state' ),
	'ZIP_CODE' => array( 'label' => 'Zip', 'format' => 'zip' ),
	'COUNTRY' => array( 'label' => 'Country', 'format' => 'enum' ),
	'IFTA_REG_JURISDICTION' => array( 'label' => 'Registered In', 'format' => 'states' ),
	'TAX_ID' => array( 'label' => 'Tax ID', 'format' => 'text' ),
);

$sts_form_edit_fleet_fields = array( //! $sts_form_edit_fleet_fields
	'FLEET_CODE' => array( 'format' => 'hidden' ),
	'COMPANY_CODE' => array( 'label' => 'Company', 'format' => 'table',
		'table' => COMPANY_TABLE, 'key' => 'COMPANY_CODE', 'fields' => 'COMPANY_NAME' ),
	'FLEET_NAME' => array( 'label' => 'Name', 'format' => 'text' ),
	'IFTA_BASE_JURISDICTION' => array( 'label' => 'IFTA Base', 'format' => 'state' ),
	'ADDRESS' => array( 'label' => 'Addr', 'format' => 'text' ),
	'ADDRESS2' => array( 'label' => 'Addr2', 'format' => 'text' ),
	'CITY' => array( 'label' => 'City', 'format' => 'text' ),
	'STATE' => array( 'label' => 'State', 'format' => 'state' ),
	'ZIP_CODE' => array( 'label' => 'Zip', 'format' => 'zip' ),
	'COUNTRY' => array( 'label' => 'Country', 'format' => 'enum' ),
	'IFTA_REG_JURISDICTION' => array( 'label' => 'Registered In', 'format' => 'states' ),
	'TAX_ID' => array( 'label' => 'Tax ID', 'format' => 'text' ),
);

//! Layout Specifications - For use with sts_result

$sts_result_fleet_layout = array( //! $sts_result_fleet_layout
	'FLEET_CODE' => array( 'format' => 'hidden' ),
	'COMPANY_CODE' => array( 'label' => 'Company', 'format' => 'table',
		'table' => COMPANY_TABLE, 'key' => 'COMPANY_CODE', 'fields' => 'COMPANY_NAME' ),
	'FLEET_NAME' => array( 'label' => 'Name', 'format' => 'text' ),
	'UNITS' => array( 'label' => '#&nbsp;Units', 'format' => 'number', 'align' => 'right',
		'snippet' => "COALESCE((SELECT COUNT(*) AS UNITS
		FROM EXP_TRACTOR WHERE EXP_TRACTOR.FLEET_CODE = EXP_FLEET.FLEET_CODE), 0)" ),
	'IFTA_BASE_JURISDICTION' => array( 'label' => 'IFTA&nbsp;Base', 'format' => 'state' ),
	'ADDR' => array( 'label' => 'Address', 'format' => 'text',
		'snippet' => "concat(COALESCE(ADDRESS,''),
		(CASE WHEN ADDRESS2 IS NOT NULL THEN concat('<br>',ADDRESS2) ELSE '' END),
		'<br>',COALESCE(CITY,''),', ',COALESCE(STATE,''),', ',COALESCE(ZIP_CODE,''))" ),
	'IFTA_REG_JURISDICTION' => array( 'label' => 'Registered In', 'format' => 'states' ),
	'TAX_ID' => array( 'label' => 'Tax&nbsp;ID', 'format' => 'text' ),
);

//! Edit/Delete Button Specifications - For use with sts_result

$sts_result_fleet_edit = array( //! $sts_result_fleet_edit
	'title' => '<img src="images/tractor_icon.png" alt="tractor_icon" height="24"> Tractor Fleets',
	'sort' => 'FLEET_NAME asc',
	'cancel' => 'index.php',
	'add' => 'exp_addfleet.php',
	//'actionextras' => 'disabled',
	'addbutton' => 'Add Tractor Fleet',
	'cancelbutton' => 'Back',
	'filters_html' => '<a class="btn btn-sm btn-default" href="exp_listtractor.php"><img src="images/tractor_icon.png" alt="tractor_icon" height="18"> Tractors</a>',
	'rowbuttons' => array(
		array( 'url' => 'exp_editfleet.php?CODE=', 'key' => 'FLEET_CODE', 'label' => 'FLEET_NAME', 'tip' => 'Edit Tractor Fleet ', 'icon' => 'glyphicon glyphicon-edit' ),
		array( 'url' => 'exp_deletefleet.php?CODE=', 'key' => 'FLEET_CODE', 'label' => 'FLEET_NAME', 'tip' => 'Delete Tractor Fleet ', 'tip2' => 'DO NOT DO THIS IF YOU ARE USING THIS FLEET',
		'icon' => 'glyphicon glyphicon-trash', 'confirm' => 'yes' )
	)
);


?>
