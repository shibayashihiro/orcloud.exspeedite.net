<?php

// no direct access
defined('_STS_INCLUDE') or die('Restricted access');

require_once( "sts_table_class.php" );

class sts_commodity extends sts_table {

	// Constructor does not need the table name
	public function __construct( $database, $debug = false ) {

		$this->debug = $debug;
		$this->primary_key = "COMMODITY_CODE";
		if( $this->debug ) echo "<p>Create sts_commodity</p>";
		parent::__construct( $database, COMMODITY_TABLE, $debug);
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

	public function lookup_class( $class ) {
		global $exspeedite_db;
		
		$result = $exspeedite_db->get_one_row("select
			TEMP_CONTROLLED, TEMPERATURE, TEMPERATURE_UNITS
			FROM EXP_COMMODITY_CLASS
			WHERE COMMODITY_CLASS_CODE = $class");
	
		if( $this->debug ) {
			echo "<p>result = </p>
			<pre>";
			var_dump($result);
			echo "</pre>";
		}
		return is_array($result) ? $result : 'null';
	}

}

//! Form Specifications - For use with sts_form

$sts_form_addcommodity_form = array(	//! sts_form_addcommodity_form
	'title' => '<img src="images/commodity_icon.png" alt="commodity_icon" height="24"> Add Commodity',
	'action' => 'exp_addcommodity.php',
	//'actionextras' => 'disabled',
	'cancel' => 'exp_listcommodity.php',
	'name' => 'addcommodity',
	'okbutton' => 'Save Changes',
	'saveadd' => 'Add Another',
	'cancelbutton' => 'Cancel',
		'layout' => '
	<div class="form-group">
		<div class="col-sm-5">
			<div class="form-group tighter">
				<label for="COMMODITY_NAME" class="col-sm-4 control-label">#COMMODITY_NAME#</label>
				<div class="col-sm-8">
					%COMMODITY_NAME%
				</div>
			</div>
			<div class="form-group tighter">
				<label for="COMMODITY_DESCRIPTION" class="col-sm-4 control-label">#COMMODITY_DESCRIPTION#</label>
				<div class="col-sm-8">
					%COMMODITY_DESCRIPTION%
				</div>
			</div>
			<div class="form-group tighter">
				<label for="CLASS" class="col-sm-4 control-label">#CLASS#</label>
				<div class="col-sm-8">
					%CLASS%
				</div>
			</div>
			<div class="form-group tighter">
				<label for="DANGEROUS" class="col-sm-4 control-label">#DANGEROUS#</label>
				<div class="col-sm-8">
					%DANGEROUS%
				</div>
			</div>
			<div class="form-group tighter">
				<label for="HIGH_VALUE" class="col-sm-4 control-label">#HIGH_VALUE#</label>
				<div class="col-sm-8">
					%HIGH_VALUE%
				</div>
			</div>
			<div class="form-group tighter">
				<label for="UN_NUMBER" class="col-sm-4 control-label">#UN_NUMBER#</label>
				<div class="col-sm-8">
					%UN_NUMBER%
				</div>
			</div>
		</div>
		<div class="col-sm-5">
			<div class="form-group tighter">
				<label for="TEMP_CONTROLLED" class="col-sm-4 control-label">#TEMP_CONTROLLED#</label>
				<div class="col-sm-6">
					%TEMP_CONTROLLED%
				</div>
			</div>
			<div class="form-group tighter temp" hidden>
				<label for="TEMPERATURE" class="col-sm-4 control-label">#TEMPERATURE#</label>
				<div class="col-sm-6">
					%TEMPERATURE%
				</div>
			</div>
			<div class="form-group temp" hidden>
				<label for="TEMPERATURE_UNITS" class="col-sm-4 control-label">#TEMPERATURE_UNITS#</label>
				<div class="col-sm-6">
					%TEMPERATURE_UNITS%
				</div>
			</div>
			<div class="form-group tighter">
				<label for="PIECES_UNITS" class="col-sm-4 control-label">#PIECES_UNITS#</label>
				<div class="col-sm-6">
					%PIECES_UNITS%
				</div>
			</div>
			<div class="form-group tighter">
				<label for="WEIGHT_CONV" class="col-sm-4 control-label">#WEIGHT_CONV#</label>
				<div class="col-sm-6">
					%WEIGHT_CONV%
				</div>
			</div>
		</div>
	</div>
	
	'
);

$sts_form_editcommodity_form = array( //! $sts_form_editcommodity_form
	'title' => '<img src="images/commodity_icon.png" alt="commodity_icon" height="24"> Edit Commodity',
	'action' => 'exp_editcommodity.php',
	//'actionextras' => 'disabled',
	'cancel' => 'exp_listcommodity.php',
	'name' => 'editcommodity',
	'okbutton' => 'Save Changes to Commodity',
	'cancelbutton' => 'Back to Commodities',
		'layout' => '
		%COMMODITY_CODE%
	<div class="form-group">
		<div class="col-sm-5">
			<div class="form-group tighter">
				<label for="COMMODITY_NAME" class="col-sm-4 control-label">#COMMODITY_NAME#</label>
				<div class="col-sm-8">
					%COMMODITY_NAME%
				</div>
			</div>
			<div class="form-group tighter">
				<label for="COMMODITY_DESCRIPTION" class="col-sm-4 control-label">#COMMODITY_DESCRIPTION#</label>
				<div class="col-sm-8">
					%COMMODITY_DESCRIPTION%
				</div>
			</div>
			<div class="form-group tighter">
				<label for="CLASS" class="col-sm-4 control-label">#CLASS#</label>
				<div class="col-sm-8">
					%CLASS%
				</div>
			</div>
			<div class="form-group tighter">
				<label for="DANGEROUS" class="col-sm-4 control-label">#DANGEROUS#</label>
				<div class="col-sm-8">
					%DANGEROUS%
				</div>
			</div>
			<div class="form-group tighter">
				<label for="HIGH_VALUE" class="col-sm-4 control-label">#HIGH_VALUE#</label>
				<div class="col-sm-8">
					%HIGH_VALUE%
				</div>
			</div>
			<div class="form-group tighter">
				<label for="UN_NUMBER" class="col-sm-4 control-label">#UN_NUMBER#</label>
				<div class="col-sm-8">
					%UN_NUMBER%
				</div>
			</div>
		</div>
		<div class="col-sm-5">
			<div class="form-group tighter">
				<label for="TEMP_CONTROLLED" class="col-sm-4 control-label">#TEMP_CONTROLLED#</label>
				<div class="col-sm-6">
					%TEMP_CONTROLLED%
				</div>
			</div>
			<div class="form-group tighter temp" hidden>
				<label for="TEMPERATURE" class="col-sm-4 control-label">#TEMPERATURE#</label>
				<div class="col-sm-6">
					%TEMPERATURE%
				</div>
			</div>
			<div class="form-group temp" hidden>
				<label for="TEMPERATURE_UNITS" class="col-sm-4 control-label">#TEMPERATURE_UNITS#</label>
				<div class="col-sm-6">
					%TEMPERATURE_UNITS%
				</div>
			</div>
			<div class="form-group tighter">
				<label for="PIECES_UNITS" class="col-sm-4 control-label">#PIECES_UNITS#</label>
				<div class="col-sm-6">
					%PIECES_UNITS%
				</div>
			</div>
			<div class="form-group tighter">
				<label for="WEIGHT_CONV" class="col-sm-4 control-label">#WEIGHT_CONV#</label>
				<div class="col-sm-6">
					%WEIGHT_CONV%
				</div>
			</div>
		</div>
	</div>
	
	'
);

//! Field Specifications - For use with sts_form

$sts_form_add_commodity_fields = array( //! $sts_form_add_commodity_fields
	'COMMODITY_NAME' => array( 'label' => 'Name', 'format' => 'text', 'extras' => 'required autofocus' ),
	'COMMODITY_DESCRIPTION' => array( 'label' => 'Description', 'format' => 'text' ),
	'DANGEROUS' => array( 'label' => 'Dangerous', 'format' => 'bool' ),
	'TEMP_CONTROLLED' => array( 'label' => 'Temp Controlled', 'format' => 'bool' ),
	'HIGH_VALUE' => array( 'label' => 'High Value', 'format' => 'bool' ),
	'CLASS' => array( 'label' => 'Class', 'format' => 'table',
		'table' => COMMODITY_CLASS_TABLE, 'key' => 'COMMODITY_CLASS_CODE',
		'fields' => 'CC_NAME', 'nolink' => true ),
	'TEMPERATURE' => array( 'label' => 'Temp', 'format' => 'number', 'align' => 'right',
		'extras' => 'allowneg' ),
	'TEMPERATURE_UNITS' => array( 'label' => 'Temp Units', 'format' => 'table',
		'table' => UNIT_TABLE, 'key' => 'UNIT_CODE', 'fields' => 'UNIT_NAME',
		'condition' => 'UNIT_TYPE = \'temperature\'', 'nolink' => true ),
	'PIECES_UNITS' => array( 'label' => 'Pieces Units', 'format' => 'table',
		'table' => UNIT_TABLE, 'key' => 'UNIT_CODE', 'fields' => 'UNIT_NAME',
		'condition' => 'UNIT_TYPE = \'item\'', 'nolink' => true ),
	'WEIGHT_CONV' => array( 'label' => 'Weight Conv', 'format' => 'number' ),
	'UN_NUMBER' => array( 'label' => 'UN#', 'format' => 'table',
		'table' => UN_NUMBER_TABLE, 'key' => 'UN_NUMBER_CODE',
		'fields' => 'UN_NUMBER,UN_DESCRIPTION', 'separator' => ' - ', 'nolink' => true,
		'order' => "UN_NUMBER asc" ),
);

$sts_form_edit_commodity_fields = array( //! $sts_form_edit_commodity_fields
	'COMMODITY_CODE' => array( 'format' => 'hidden' ),
	'COMMODITY_NAME' => array( 'label' => 'Name', 'format' => 'text', 'extras' => 'required autofocus' ),
	'COMMODITY_DESCRIPTION' => array( 'label' => 'Description', 'format' => 'text' ),
	'DANGEROUS' => array( 'label' => 'Dangerous', 'format' => 'bool' ),
	'TEMP_CONTROLLED' => array( 'label' => 'Temp Controlled', 'format' => 'bool' ),
	'HIGH_VALUE' => array( 'label' => 'High Value', 'format' => 'bool' ),
	'CLASS' => array( 'label' => 'Class', 'format' => 'table',
		'table' => COMMODITY_CLASS_TABLE, 'key' => 'COMMODITY_CLASS_CODE', 'fields' => 'CC_NAME' ),
	'TEMPERATURE' => array( 'label' => 'Temp', 'format' => 'number', 'align' => 'right',
		'extras' => 'allowneg' ),
	'TEMPERATURE_UNITS' => array( 'label' => 'Temp Units', 'format' => 'table',
		'table' => UNIT_TABLE, 'key' => 'UNIT_CODE', 'fields' => 'UNIT_NAME',
		'condition' => 'UNIT_TYPE = \'temperature\'', 'nolink' => true ),
	'PIECES_UNITS' => array( 'label' => 'Pieces Units', 'format' => 'table',
		'table' => UNIT_TABLE, 'key' => 'UNIT_CODE', 'fields' => 'UNIT_NAME',
		'condition' => 'UNIT_TYPE = \'item\'', 'nolink' => true ),
	'WEIGHT_CONV' => array( 'label' => 'Weight Conv', 'format' => 'number' ),
	'UN_NUMBER' => array( 'label' => 'UN#', 'format' => 'table',
		'table' => UN_NUMBER_TABLE, 'key' => 'UN_NUMBER_CODE',
		'fields' => 'UN_NUMBER,UN_DESCRIPTION', 'separator' => ' - ', 'nolink' => true,
		'order' => "UN_NUMBER asc" ),
);

//! Layout Specifications - For use with sts_result

$sts_result_commoditys_layout = array(
	'COMMODITY_CODE' => array( 'format' => 'hidden' ),
	'COMMODITY_NAME' => array( 'label' => 'Name', 'format' => 'text' ),
	'COMMODITY_DESCRIPTION' => array( 'label' => 'Description', 'format' => 'text' ),
	'DANGEROUS' => array( 'label' => 'Dangerous', 'format' => 'bool', 'align' => 'center' ),
	'HIGH_VALUE' => array( 'label' => 'High Value', 'format' => 'bool', 'align' => 'center' ),
	'CLASS' => array( 'label' => 'Class', 'format' => 'table',
		'table' => COMMODITY_CLASS_TABLE, 'key' => 'COMMODITY_CLASS_CODE', 'fields' => 'CC_NAME' ),
	'TEMP_CONTROLLED' => array( 'label' => 'Temp Controlled', 'format' => 'bool', 'align' => 'center' ),
	'TEMPERATURE' => array( 'label' => 'Temp', 'format' => 'num0', 'align' => 'right' ),
	'TEMPERATURE_UNITS' => array( 'label' => 'Temp Units', 'format' => 'table',
		'table' => UNIT_TABLE, 'key' => 'UNIT_CODE', 'fields' => 'UNIT_NAME',
		'condition' => 'UNIT_TYPE = \'temperature\'', 'nolink' => true ),
	'PIECES_UNITS' => array( 'label' => 'Pieces Units', 'format' => 'table',
		'table' => UNIT_TABLE, 'key' => 'UNIT_CODE', 'fields' => 'UNIT_NAME',
		'condition' => 'UNIT_TYPE = \'item\'', 'nolink' => true ),
	'WEIGHT_CONV' => array( 'label' => 'Weight Conv', 'format' => 'num0' ),
	'UN_NUMBER' => array( 'label' => 'UN#', 'format' => 'table',
		'table' => UN_NUMBER_TABLE, 'key' => 'UN_NUMBER_CODE',
		'fields' => 'UN_NUMBER' ),
	'CHANGED_DATE' => array( 'label' => 'Changed', 'format' => 'timestamp-s', 'length' => 90 ),
	'CHANGED_BY' => array( 'label' => 'By', 'format' => 'table',
		'table' => USER_TABLE, 'key' => 'USER_CODE', 'fields' => 'USERNAME', 'link' => 'exp_edituser.php?CODE=' )

);

//! Edit/Delete Button Specifications - For use with sts_result

$sts_result_commoditys_edit = array(
	'title' => '<img src="images/commodity_icon.png" alt="commodity_icon" height="24"> Commodities',
	'sort' => 'COMMODITY_NAME asc',
	'cancel' => 'index.php',
	'add' => 'exp_addcommodity.php',
	//'actionextras' => 'disabled',
	'addbutton' => 'Add Commodity',
	'cancelbutton' => 'Back',
	'filters_html' => '<a class="btn btn-sm btn-default" href="exp_listcommodity_class.php"><img src="images/commodity_class_icon.png" alt="commodity_class_icon" height="18"> Commodity Classes</a>',
	'rowbuttons' => array(
		array( 'url' => 'exp_editcommodity.php?CODE=', 'key' => 'COMMODITY_CODE', 'label' => 'COMMODITY_NAME', 'tip' => 'Edit commodity ', 'icon' => 'glyphicon glyphicon-edit' ),
		array( 'url' => 'exp_deletecommodity.php?CODE=', 'key' => 'COMMODITY_CODE', 'label' => 'COMMODITY_NAME', 'tip' => 'Delete commodity ', 'icon' => 'glyphicon glyphicon-trash', 'confirm' => 'yes' )
	)
);


?>
