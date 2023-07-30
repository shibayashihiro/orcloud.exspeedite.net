<?php

// no direct access
defined('_STS_INCLUDE') or die('Restricted access');

require_once( "sts_table_class.php" );

class sts_unit extends sts_table {

	// Constructor does not need the table name
	public function __construct( $database, $debug = false ) {

		$this->debug = $debug;
		$this->primary_key = "UNIT_CODE";
		if( $this->debug ) echo "<p>Create sts_unit</p>";
		parent::__construct( $database, UNIT_TABLE, $debug);
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

	// Given a name and type, return the unit_code, false if not found
	public function lookup_unit_code( $name, $type ) {
		$code = false;
		$results = $this->fetch_rows("UNIT_NAME = '".singular( trim((string) $name) )."'
			AND UNIT_TYPE = '".trim((string) $type)."'",$this->primary_key);
		if( is_array($results) && count($results) > 0 )
			$code = $results[0]["UNIT_CODE"];
		return $code;
	}
	
}

//! Form Specifications - For use with sts_form

$sts_form_addunit_form = array(	//! sts_form_addunit_form
	'title' => '<img src="images/unit_icon.png" alt="unit_icon" height="24"> Add Unit',
	'action' => 'exp_addunit.php',
	//'actionextras' => 'disabled',
	'cancel' => 'exp_listunit.php',
	'name' => 'addunit',
	'okbutton' => 'Save Changes',
	'saveadd' => 'Add Another',
	'cancelbutton' => 'Cancel',
		'layout' => '
	<div class="form-group">
		<div class="col-sm-4">
			<div class="form-group">
				<label for="UNIT_NAME" class="col-sm-4 control-label">#UNIT_NAME#</label>
				<div class="col-sm-8">
					%UNIT_NAME%
				</div>
			</div>
			<div class="form-group">
				<label for="UNIT_TYPE" class="col-sm-4 control-label">#UNIT_TYPE#</label>
				<div class="col-sm-8">
					%UNIT_TYPE%
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

$sts_form_editunit_form = array(
	'title' => '<img src="images/unit_icon.png" alt="unit_icon" height="24"> Edit Unit',
	'action' => 'exp_editunit.php',
	//'actionextras' => 'disabled',
	'cancel' => 'exp_listunit.php',
	'name' => 'editunit',
	'okbutton' => 'Save Changes to Unit',
	'cancelbutton' => 'Back to Units',
		'layout' => '
		%UNIT_CODE%
	<div class="form-group">
		<div class="col-sm-4">
			<div class="form-group">
				<label for="UNIT_NAME" class="col-sm-4 control-label">#UNIT_NAME#</label>
				<div class="col-sm-8">
					%UNIT_NAME%
				</div>
			</div>
			<div class="form-group">
				<label for="UNIT_TYPE" class="col-sm-4 control-label">#UNIT_TYPE#</label>
				<div class="col-sm-8">
					%UNIT_TYPE%
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

$sts_form_add_unit_fields = array(
	'UNIT_NAME' => array( 'label' => 'Name', 'format' => 'text', 'extras' => 'required autofocus' ),
	'UNIT_TYPE' => array( 'label' => 'Type', 'format' => 'enum', 'extras' => 'required' ),
	'SYMBOL' => array( 'label' => 'Symbol', 'format' => 'text', 'extras' => 'required' ),
	'BASE' => array( 'label' => 'Base', 'format' => 'table',
		'table' => UNIT_TABLE, 'key' => 'UNIT_CODE', 'fields' => 'UNIT_NAME' ),
	'CONV_FACTOR' => array( 'label' => 'Conversion', 'format' => 'number', 'extras' => 'required' ),
);

$sts_form_edit_unit_fields = array(
	'UNIT_CODE' => array( 'format' => 'hidden' ),
	'UNIT_NAME' => array( 'label' => 'Name', 'format' => 'text', 'extras' => 'required autofocus' ),
	'UNIT_TYPE' => array( 'label' => 'Type', 'format' => 'enum', 'extras' => 'required' ),
	'SYMBOL' => array( 'label' => 'Symbol', 'format' => 'text', 'extras' => 'required' ),
	'BASE' => array( 'label' => 'Base', 'format' => 'table',
		'table' => UNIT_TABLE, 'key' => 'UNIT_CODE', 'fields' => 'UNIT_NAME' ),
	'CONV_FACTOR' => array( 'label' => 'Conversion', 'format' => 'number', 'extras' => 'required' ),
);

//! Layout Specifications - For use with sts_result

$sts_result_units_layout = array(
	'UNIT_CODE' => array( 'format' => 'hidden' ),
	'UNIT_NAME' => array( 'label' => 'Name', 'format' => 'text' ),
	'UNIT_TYPE' => array( 'label' => 'Type', 'format' => 'text' ),
	'SYMBOL' => array( 'label' => 'Symbol', 'format' => 'text' ),
	'BASE' => array( 'label' => 'Base', 'format' => 'table',
		'table' => UNIT_TABLE, 'key' => 'UNIT_CODE', 'fields' => 'UNIT_NAME' ),
	'CONV_FACTOR' => array( 'label' => 'Conversion', 'format' => 'num2' ),

);

//! Edit/Delete Button Specifications - For use with sts_result

$sts_result_units_edit = array(
	'title' => '<img src="images/unit_icon.png" alt="unit_icon" height="24"> Units',
	'sort' => 'UNIT_NAME asc',
	'cancel' => 'index.php',
	'add' => 'exp_addunit.php',
	//'actionextras' => 'disabled',
	'addbutton' => 'Add Unit',
	'cancelbutton' => 'Back',
	'rowbuttons' => array(
		array( 'url' => 'exp_editunit.php?CODE=', 'key' => 'UNIT_CODE', 'label' => 'UNIT_NAME', 'tip' => 'Edit unit ', 'icon' => 'glyphicon glyphicon-edit' ),
		array( 'url' => 'exp_deleteunit.php?CODE=', 'key' => 'UNIT_CODE', 'label' => 'UNIT_NAME', 'tip' => 'Delete unit ', 'icon' => 'glyphicon glyphicon-trash', 'confirm' => 'yes' )
	)
);


?>
