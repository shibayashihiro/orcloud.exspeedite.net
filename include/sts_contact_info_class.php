<?php

// $Id: sts_contact_info_class.php 5103 2023-05-17 22:32:02Z duncan $
//! Contact info class, all things contact info...

// no direct access
defined('_STS_INCLUDE') or die('Restricted access');

require_once( "sts_table_class.php" );
require_once( "sts_zip_class.php" );
require_once( __DIR__."/../PCMILER/exp_get_miles.php" );

class sts_contact_info extends sts_table {

	private $pcm;
	private $zip;
	private $name_state;
	private $state_name;
	
	// Constructor does not need the table name
	public function __construct( $database, $debug = false ) {

		$this->debug = $debug;
		$this->primary_key = "CONTACT_INFO_CODE";
		$this->pcm = sts_pcmiler_api::getInstance( $database, $debug );
		$this->zip = sts_zip::getInstance( $database, $debug );
		if( $this->debug ) echo "<p>Create sts_contact_info</p>";
		parent::__construct( $database, CONTACT_INFO_TABLE, $debug);
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

	private function load_states() {
		$whole = $this->cache->get_whole_cache();
		$this->name_state = $whole["NAME_STATE"];
		$this->state_name = $whole["STATE_NAME"];
	}
	
	public function get_state( $name ) {
		return isset($this->name_state[$name]) ? $this->name_state[$name] : 
			(isset($this->state_name[$name]) ? $name : NULL);
	}

	//! Override parent to include checking address with PC*Miler
	public function add( $values ) {

		$valid = $this->zip->validate_various($values);
		$values['ADDR_VALID'] = $valid;
		$values['ADDR_CODE'] = $valid == 'valid' ? '' : $this->zip->get_code();
		$values['ADDR_DESCR'] = $valid == 'valid' ? '' : $this->trim_to_fit( 'ADDR_DESCR', $this->zip->get_description() );
		$values['VALID_SOURCE'] = $this->zip->get_source();
		if( $valid == 'valid' ) {
			$values['LAT'] = $this->zip->get_lat();
			$values['LON'] = $this->zip->get_lon();
		}
		// Guard against sending invalid value
		if( $values['VALID_SOURCE'] == '' ) $values['VALID_SOURCE'] = 'none';
		
		$result = parent::add( $values );
		
		return $result;
	}
	
	//! Override parent to include checking address with PC*Miler
	public function update( $code, $values ) {

		if( is_array($values) && count($values) > 0 )
			$result = parent::update( $code, $values );
		
		$newvalues = $this->fetch_rows($this->primary_key." = ".$code);
		$valid = $this->zip->validate_various($newvalues[0]);
		unset($newvalues);
		$newvalues = array();
		$newvalues['ADDR_VALID'] = $valid;
		$newvalues['ADDR_CODE'] = $valid == 'valid' ? '' : $this->zip->get_code();
		$newvalues['ADDR_DESCR'] = $valid == 'valid' ? '' : $this->trim_to_fit( 'ADDR_DESCR', $this->zip->get_description() );
		$newvalues['VALID_SOURCE'] = $this->zip->get_source();
		if( $valid == 'valid' ) {
			$newvalues['LAT'] = $this->zip->get_lat();
			$newvalues['LON'] = $this->zip->get_lon();
		}
		$result = parent::update( $code, $newvalues );

		return $result;
	}
	
	//! This will update all contact info, forcing validation of all rows
	public function update_all() {
		$count = 0;
		$check = $this->fetch_rows("", "ADDR_VALID, LAT, LON, CONTACT_INFO_CODE");
		
		if( is_array($check) && count($check) > 0 ) {
			foreach( $check as $row ) {
				if( empty($row["LAT"]) && empty($row["LON"])) {
					$result = $this->update( $row["CONTACT_INFO_CODE"], false );
					if( $result ) $count++;
					echo ($count % 10 == 0 ? '+' : '.').($count % 100 == 0 ? ' '.$count.'<br>' : '');
					ob_flush(); flush();
				}
			}
		}
		
		return $count;
	}
	
	//! Get the address validation info
	public function get_validation( $code, $source, $type ) {
		if( $this->debug ) echo "<p>sts_contact_info > get_validation( $code, $source, $type )</p>";
		return $this->fetch_rows( "CONTACT_CODE = ".$code." AND 
			CONTACT_SOURCE = '".$source."' AND
			CONTACT_TYPE ='".$type."'",
			"ADDR_VALID, ADDR_CODE, ADDR_DESCR, VALID_SOURCE, LAT, LON", "", "1" );
	}
	
	//! Duplicate contact info row.
	public function duplicate( $code ) {
		$result = false;
		$values = $this->fetch_rows($this->primary_key." = ".$code,
			"CONTACT_CODE, CONTACT_SOURCE, CONTACT_TYPE, LABEL, CONTACT_NAME,
			ADDRESS, ADDRESS2, CITY, STATE, ZIP_CODE, COUNTRY, ADDR_VALID,
			ADDR_CODE, ADDR_DESCR, PHONE_OFFICE, PHONE_EXT, PHONE_FAX,
			PHONE_HOME, PHONE_CELL, EMAIL, ISDELETED, SYNERGY_IMPORT,
			VALID_SOURCE, LAT, LON");
		if( is_array($values) && count($values) == 1 ) {
			$values[0]["ISDELETED"] = 0;	// Not deleted
			$result = $this->add( $values[0] );
		}
		
		return $result;
	}
	
}

//! Form Specifications - For use with sts_form
$sts_form_add_contact_info = array( //! $sts_form_add_contact_info
	'title' => 'Add Contact Info for %PARENT_NAME%',
	'action' => 'exp_addcontact_info.php',
	'cancel' => 'exp_edit%CONTACT_SOURCE%.php?CODE=%CONTACT_CODE%',
	//'popup' => true,	// issue with the toggle switches
	'name' => 'add_contact_info',
	'okbutton' => 'Save Changes',
	'cancelbutton' => 'Back',
	'layout' => '
	%CONTACT_CODE%
	%CONTACT_SOURCE%
	<div class="form-group">
		<div class="col-sm-5">
			<div class="form-group">
				<label for="CONTACT_TYPE" class="col-sm-4 control-label">#CONTACT_TYPE#</label>
				<div class="col-sm-8">
					%CONTACT_TYPE%
				</div>
			</div>
			<div class="form-group">
				<label for="LABEL" class="col-sm-4 control-label">#LABEL#</label>
				<div class="col-sm-8">
					%LABEL%
				</div>
			</div>
			<div class="form-group">
				<label for="CONTACT_NAME" class="col-sm-4 control-label">#CONTACT_NAME#</label>
				<div class="col-sm-8">
					%CONTACT_NAME%
				</div>
			</div>
			<div class="form-group">
				<label for="JOB_TITLE" class="col-sm-4 control-label">#JOB_TITLE#</label>
				<div class="col-sm-8">
					%JOB_TITLE%
				</div>
			</div>
			<div class="form-group">
				<label for="PHONE_OFFICE" class="col-sm-4 control-label">#PHONE_OFFICE#</label>
				<div class="col-sm-5">
					%PHONE_OFFICE%
				</div>
				<div class="col-sm-3">
					%PHONE_EXT%
				</div>
			</div>
			<div class="form-group">
				<label for="PHONE_FAX" class="col-sm-4 control-label">#PHONE_FAX#</label>
				<div class="col-sm-8">
					%PHONE_FAX%
				</div>
			</div>
			<div class="form-group">
				<label for="PHONE_HOME" class="col-sm-4 control-label">#PHONE_HOME#</label>
				<div class="col-sm-8">
					%PHONE_HOME%
				</div>
			</div>
			<div class="form-group">
				<label for="PHONE_CELL" class="col-sm-4 control-label">#PHONE_CELL#</label>
				<div class="col-sm-8">
					%PHONE_CELL%
				</div>
			</div>
		</div>
		<div class="col-sm-5">
			<div class="form-group">
				<label for="EMAIL" class="col-sm-4 control-label">#EMAIL#</label>
				<div class="col-sm-8">
					%EMAIL%
				</div>
			</div>
			<div class="form-group">
				<label for="ADDRESS" class="col-sm-4 control-label">#ADDRESS#</label>
				<div class="col-sm-8">
					%ADDRESS%
				</div>
			</div>
			<div class="form-group">
				<label for="ADDRESS2" class="col-sm-4 control-label">#ADDRESS2#</label>
				<div class="col-sm-8">
					%ADDRESS2%
				</div>
			</div>
			<div class="form-group">
				<label for="ZIP_CODE" class="col-sm-4 control-label">#ZIP_CODE#</label>
				<div class="col-sm-8">
					%ZIP_CODE%
				</div>
			</div>
			<div class="form-group">
				<label for="CITY" class="col-sm-4 control-label">#CITY#</label>
				<div class="col-sm-8">
					%CITY%
				</div>
			</div>
			<div class="form-group">
				<label for="STATE" class="col-sm-4 control-label">#STATE#</label>
				<div class="col-sm-8">
					%STATE%
				</div>
			</div>
			<div class="form-group">
				<label for="COUNTRY" class="col-sm-4 control-label">#COUNTRY#</label>
				<div class="col-sm-8">
					%COUNTRY%
				</div>
			</div>
		</div>
	</div>
'
);

$sts_form_edit_contact_info = array( //! $sts_form_edit_contact_info
	'title' => 'Edit Contact Info',
	'action' => 'exp_editcontact_info.php',
	'cancel' => 'exp_edit%CONTACT_SOURCE%.php?CODE=%CONTACT_CODE%',
	'name' => 'edit_contact_info',
	'okbutton' => 'Save Changes',
	'cancelbutton' => 'Back',
	'layout' => '
	%CONTACT_INFO_CODE%
	%CONTACT_CODE%
	%CONTACT_SOURCE%
	<div class="form-group">
		<div class="col-sm-6">
			<div class="form-group">
				<label for="CONTACT_TYPE" class="col-sm-4 control-label">#CONTACT_TYPE#</label>
				<div class="col-sm-8">
					%CONTACT_TYPE%
				</div>
			</div>
			<div class="form-group">
				<label for="LABEL" class="col-sm-4 control-label">#LABEL#</label>
				<div class="col-sm-8">
					%LABEL%
				</div>
			</div>
			<div class="form-group">
				<label for="CONTACT_NAME" class="col-sm-4 control-label">#CONTACT_NAME#</label>
				<div class="col-sm-8">
					%CONTACT_NAME%
				</div>
			</div>
			<div class="form-group">
				<label for="JOB_TITLE" class="col-sm-4 control-label">#JOB_TITLE#</label>
				<div class="col-sm-8">
					%JOB_TITLE%
				</div>
			</div>
			<div class="form-group">
				<label for="PHONE_OFFICE" class="col-sm-4 control-label">#PHONE_OFFICE#</label>
				<div class="col-sm-5">
					%PHONE_OFFICE%
				</div>
				<div class="col-sm-3">
					%PHONE_EXT%
				</div>
			</div>
			<div class="form-group">
				<label for="PHONE_FAX" class="col-sm-4 control-label">#PHONE_FAX#</label>
				<div class="col-sm-8">
					%PHONE_FAX%
				</div>
			</div>
			<div class="form-group">
				<label for="PHONE_HOME" class="col-sm-4 control-label">#PHONE_HOME#</label>
				<div class="col-sm-8">
					%PHONE_HOME%
				</div>
			</div>
			<div class="form-group">
				<label for="PHONE_CELL" class="col-sm-4 control-label">#PHONE_CELL#</label>
				<div class="col-sm-8">
					%PHONE_CELL%
				</div>
			</div>
		</div>
		<div class="col-sm-6">
			<div class="form-group">
				<label for="EMAIL" class="col-sm-4 control-label">#EMAIL#</label>
				<div class="col-sm-8">
					%EMAIL%
				</div>
			</div>
			<div class="form-group">
				<label for="ADDRESS" class="col-sm-4 control-label">#ADDRESS#</label>
				<div class="col-sm-8">
					%ADDRESS%
				</div>
			</div>
			<div class="form-group">
				<label for="ADDRESS2" class="col-sm-4 control-label">#ADDRESS2#</label>
				<div class="col-sm-8">
					%ADDRESS2%
				</div>
			</div>
			<div class="form-group">
				<label for="ZIP_CODE" class="col-sm-4 control-label">#ZIP_CODE#</label>
				<div class="col-sm-8">
					%ZIP_CODE%
				</div>
			</div>
			<div class="form-group">
				<label for="CITY" class="col-sm-4 control-label">#CITY#</label>
				<div class="col-sm-8">
					%CITY%
				</div>
			</div>
			<div class="form-group">
				<label for="STATE" class="col-sm-4 control-label">#STATE#</label>
				<div class="col-sm-8">
					%STATE%
				</div>
			</div>
			<div class="form-group">
				<label for="COUNTRY" class="col-sm-4 control-label">#COUNTRY#</label>
				<div class="col-sm-8">
					%COUNTRY%
				</div>
			</div>
		</div>
	</div>
'
);

//! Field Specifications - For use with sts_form
$sts_form_add_contact_info_fields = array(
	'CONTACT_CODE' => array( 'format' => 'hidden' ),
	'CONTACT_SOURCE' => array( 'format' => 'hidden' ),
	'CONTACT_TYPE' => array( 'label' => 'Type', 'format' => 'enum', 'extras' => 'required' ),
	'LABEL' => array( 'label' => 'Label', 'format' => 'text' ),
	'CONTACT_NAME' => array( 'label' => 'Contact Name', 'format' => 'text' ),
	'JOB_TITLE' => array( 'label' => 'Job Title', 'format' => 'text' ),
	'ADDRESS' => array( 'label' => 'Addr', 'format' => 'text' ),
	'ADDRESS2' => array( 'label' => 'Addr2', 'format' => 'text' ),
	'CITY' => array( 'label' => 'City', 'format' => 'text' ),
	'STATE' => array( 'label' => 'State', 'format' => 'state' ),
	'ZIP_CODE' => array( 'label' => 'Zip', 'format' => 'zip' ),
	'COUNTRY' => array( 'label' => 'Country', 'format' => 'enum' ),
	'PHONE_OFFICE' => array( 'label' => 'Office', 'format' => 'text' ),
	'PHONE_EXT' => array( 'label' => 'Ext', 'format' => 'text' ),
	'PHONE_FAX' => array( 'label' => 'Fax', 'format' => 'text' ),
	'PHONE_HOME' => array( 'label' => 'Home', 'format' => 'text' ),
	'PHONE_CELL' => array( 'label' => 'Cell', 'format' => 'text' ),
	'EMAIL' => array( 'label' => 'Email', 'format' => 'email', 'extras' => 'multiple' )
);

$sts_form_edit_contact_info_fields = array(
	'CONTACT_INFO_CODE' => array( 'format' => 'hidden' ),
	'CONTACT_CODE' => array( 'format' => 'hidden' ),
	'CONTACT_SOURCE' => array( 'format' => 'hidden' ),
	'CONTACT_TYPE' => array( 'label' => 'Type', 'format' => 'enum', 'extras' => 'required' ),
	'LABEL' => array( 'label' => 'Label', 'format' => 'text' ),
	'CONTACT_NAME' => array( 'label' => 'Contact Name', 'format' => 'text' ),
	'JOB_TITLE' => array( 'label' => 'Job Title', 'format' => 'text' ),
	'ADDRESS' => array( 'label' => 'Addr', 'format' => 'text' ),
	'ADDRESS2' => array( 'label' => 'Addr2', 'format' => 'text' ),
	'CITY' => array( 'label' => 'City', 'format' => 'text' ),
	'STATE' => array( 'label' => 'State', 'format' => 'state' ),
	'ZIP_CODE' => array( 'label' => 'Zip', 'format' => 'zip' ),
	'COUNTRY' => array( 'label' => 'Country', 'format' => 'enum' ),
	'PHONE_OFFICE' => array( 'label' => 'Office', 'format' => 'text' ),
	'PHONE_EXT' => array( 'label' => 'Ext', 'format' => 'text' ),
	'PHONE_FAX' => array( 'label' => 'Fax', 'format' => 'text' ),
	'PHONE_HOME' => array( 'label' => 'Home', 'format' => 'text' ),
	'PHONE_CELL' => array( 'label' => 'Cell', 'format' => 'text' ),
	'EMAIL' => array( 'label' => 'Email', 'format' => 'email', 'extras' => 'multiple' )
);

//! Layout Specifications - For use with sts_result
$sts_result_contact_info_layout = array(
	'CONTACT_INFO_CODE' => array( 'format' => 'hidden' ),
	'ISDELETED' => array( 'format' => 'hidden' ),
	'CONTACT_TYPE' => array( 'label' => 'Type', 'format' => 'text' ),
	'LABEL' => array( 'label' => 'Label', 'format' => 'text' ),
	'CONTACT_NAME' => array( 'label' => 'Contact Name', 'format' => 'text' ),
	'ADDR' => array( 'label' => 'Address', 'format' => 'text',
		'snippet' => "concat(COALESCE(ADDRESS,''),
		(CASE WHEN ADDRESS2 IS NOT NULL THEN concat('<br>',ADDRESS2) ELSE '' END),
		'<br>',COALESCE(CITY,''),', ',COALESCE(STATE,''),', ',COALESCE(ZIP_CODE,''))" ),
	//'ADDRESS' => array( 'label' => 'Addr', 'format' => 'text' ),
	//'ADDRESS2' => array( 'label' => 'Addr2', 'format' => 'text' ),
	//'CITY' => array( 'label' => 'City', 'format' => 'text' ),
	//'STATE' => array( 'label' => 'State', 'format' => 'text' ),
	//'ZIP_CODE' => array( 'label' => 'Zip', 'format' => 'text' ),
	//'COUNTRY' => array( 'label' => 'Country', 'format' => 'text' ),
	'ADDR_VALID' => array( 'label' => 'Valid', 'format' => 'valid', 'align' => 'center', 'code' => 'ADDR_CODE', 'descr' => 'ADDR_DESCR', 'source' => 'VALID_SOURCE', 'lat' => 'LAT', 'lon' => 'LON' ),
	'ADDR_CODE' => array( 'format' => 'hidden' ),
	'ADDR_DESCR' => array( 'format' => 'hidden' ),
	'VALID_SOURCE' => array( 'format' => 'hidden' ),
	'LAT' => array( 'format' => 'hidden' ),
	'LON' => array( 'format' => 'hidden' ),
	'PHONE_OFFICE' => array( 'label' => 'Office', 'format' => 'phone' ),
	'PHONE_FAX' => array( 'label' => 'Fax', 'format' => 'phone' ),
	'PHONE_HOME' => array( 'label' => 'Home', 'format' => 'phone' ),
	'PHONE_CELL' => array( 'label' => 'Cell', 'format' => 'phone' ),
	'EMAIL' => array( 'label' => 'Email', 'format' => 'email' )
);

//! Edit/Delete Button Specifications - For use with sts_result
$sts_result_contact_info_edit = array(
	'title' => 'Contact Info',
	'add' => 'exp_addcontact_info.php',
	//'popup' => true,
	'addbutton' => 'Add Contact Info',
	'rowbuttons' => array(
		array( 'url' => 'exp_editcontact_info.php?CODE=', 'key' => 'CONTACT_INFO_CODE', 'label' => 'CONTACT_NAME', 'tip' => 'Edit contact info ', 'icon' => 'glyphicon glyphicon-edit', 'showif' => 'notdeleted' ),
		array( 'url' => 'exp_deletecontact_info.php?TYPE=del&CODE=', 'key' => 'CONTACT_INFO_CODE', 'label' => 'CONTACT_NAME', 'tip' => 'Delete contact info ', 'icon' => 'glyphicon glyphicon-trash', 'confirm' => 'yes', 'showif' => 'notdeleted' ),
		array( 'url' => 'exp_dupcontact_info.php?&CODE=', 'key' => 'CONTACT_INFO_CODE', 'label' => 'CONTACT_NAME', 'tip' => 'Duplicate contact info ', 'icon' => 'glyphicon glyphicon-plus' ),
		array( 'url' => 'exp_deletecontact_info.php?TYPE=undel&CODE=', 'key' => 'CONTACT_INFO_CODE', 'label' => 'CONTACT_NAME', 'tip' => 'Undelete contact info ', 'icon' => 'glyphicon glyphicon-arrow-left', 'showif' => 'deleted' ),
		array( 'url' => 'exp_deletecontact_info.php?TYPE=permdel&CODE=', 'key' => 'CONTACT_INFO_CODE', 'label' => 'CONTACT_NAME', 'tip' => 'Permanently Delete contact info ', 'icon' => 'glyphicon glyphicon-remove', 'confirm' => 'yes', 'showif' => 'deleted' )
	)
);
	


?>
