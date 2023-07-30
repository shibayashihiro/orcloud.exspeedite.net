<?php

// no direct access
defined('_STS_INCLUDE') or die('Restricted access');

require_once( "sts_table_class.php" );

class sts_commodity_class extends sts_table {

	// Constructor does not need the table name
	public function __construct( $database, $debug = false ) {

		$this->debug = $debug;
		$this->primary_key = "COMMODITY_CLASS_CODE";
		if( $this->debug ) echo "<p>Create sts_commodity_class</p>";
		parent::__construct( $database, COMMODITY_CLASS_TABLE, $debug);
	}
	
}

//! Form Specifications - For use with sts_form

$sts_form_addcommodity_class_form = array(	//! $sts_form_addcommodity_class_form
	'title' => '<img src="images/commodity_class_icon.png" alt="commodity_class_icon" height="24"> Add Commodity Class',
	'action' => 'exp_addcommodity_class.php',
	//'actionextras' => 'disabled',
	'cancel' => 'exp_listcommodity_class.php',
	'name' => 'addcommodity_class',
	'okbutton' => 'Save Changes',
	'saveadd' => 'Add Another',
	'cancelbutton' => 'Cancel',
		'layout' => '
	<div class="form-group">
		<div class="col-sm-4">
			<div class="form-group">
				<label for="CC_NAME" class="col-sm-4 control-label">#CC_NAME#</label>
				<div class="col-sm-8">
					%CC_NAME%
				</div>
			</div>
			<div class="form-group">
				<label for="CC_DESCRIPTION" class="col-sm-4 control-label">#CC_DESCRIPTION#</label>
				<div class="col-sm-8">
					%CC_DESCRIPTION%
				</div>
			</div>
			<div class="form-group">
				<label for="TARIFF_VALUE" class="col-sm-4 control-label">#TARIFF_VALUE#</label>
				<div class="col-sm-8">
					%TARIFF_VALUE%
				</div>
			</div>
		</div>
		<div class="col-sm-4">
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
		</div>
		<div class="col-sm-4">
		</div>
	</div>
	
	'
);

$sts_form_editcommodity_class_form = array( //! $sts_form_editcommodity_class_form
	'title' => '<img src="images/commodity_class_icon.png" alt="commodity_class_icon" height="24"> Edit Commodity Class',
	'action' => 'exp_editcommodity_class.php',
	//'actionextras' => 'disabled',
	'cancel' => 'exp_listcommodity_class.php',
	'name' => 'editcommodity_class',
	'okbutton' => 'Save Changes to Commodity Class',
	'cancelbutton' => 'Back to Commodity Classes',
		'layout' => '
		%COMMODITY_CLASS_CODE%
	<div class="form-group">
		<div class="col-sm-4">
			<div class="form-group">
				<label for="CC_NAME" class="col-sm-4 control-label">#CC_NAME#</label>
				<div class="col-sm-8">
					%CC_NAME%
				</div>
			</div>
			<div class="form-group">
				<label for="CC_DESCRIPTION" class="col-sm-4 control-label">#CC_DESCRIPTION#</label>
				<div class="col-sm-8">
					%CC_DESCRIPTION%
				</div>
			</div>
			<div class="form-group">
				<label for="TARIFF_VALUE" class="col-sm-4 control-label">#TARIFF_VALUE#</label>
				<div class="col-sm-8">
					%TARIFF_VALUE%
				</div>
			</div>
		</div>
		<div class="col-sm-4">
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
		</div>
		<div class="col-sm-4">
		</div>
	</div>
	
	'
);

//! Field Specifications - For use with sts_form

$sts_form_add_commodity_class_fields = array( //! $sts_form_add_commodity_class_fields
	'CC_NAME' => array( 'label' => 'Name', 'format' => 'text', 'extras' => 'required' ),
	'CC_DESCRIPTION' => array( 'label' => 'Description', 'format' => 'text' ),
	'TARIFF_VALUE' => array( 'label' => 'Tariff', 'format' => 'number' ),
	'TEMP_CONTROLLED' => array( 'label' => 'Temp Controlled', 'format' => 'bool' ),
	'TEMPERATURE' => array( 'label' => 'Temp', 'format' => 'number', 'align' => 'right',
		'extras' => 'allowneg' ),
	'TEMPERATURE_UNITS' => array( 'label' => 'Temp Units', 'format' => 'table',
		'table' => UNIT_TABLE, 'key' => 'UNIT_CODE', 'fields' => 'UNIT_NAME',
		'condition' => 'UNIT_TYPE = \'temperature\'', 'nolink' => true ),
);

$sts_form_edit_commodity_class_fields = array( //! $sts_form_edit_commodity_class_fields
	'COMMODITY_CLASS_CODE' => array( 'format' => 'hidden' ),
	'CC_NAME' => array( 'label' => 'Name', 'format' => 'text', 'extras' => 'required' ),
	'CC_DESCRIPTION' => array( 'label' => 'Description', 'format' => 'text' ),
	'TARIFF_VALUE' => array( 'label' => 'Tariff', 'format' => 'number' ),
	'TEMP_CONTROLLED' => array( 'label' => 'Temp Controlled', 'format' => 'bool' ),
	'TEMPERATURE' => array( 'label' => 'Temp', 'format' => 'number', 'align' => 'right',
		'extras' => 'allowneg' ),
	'TEMPERATURE_UNITS' => array( 'label' => 'Temp Units', 'format' => 'table',
		'table' => UNIT_TABLE, 'key' => 'UNIT_CODE', 'fields' => 'UNIT_NAME',
		'condition' => 'UNIT_TYPE = \'temperature\'', 'nolink' => true ),
);

//! Layout Specifications - For use with sts_result

$sts_result_commodity_classs_layout = array(
	'COMMODITY_CLASS_CODE' => array( 'format' => 'hidden' ),
	'CC_NAME' => array( 'label' => 'Name', 'format' => 'text' ),
	'CC_DESCRIPTION' => array( 'label' => 'Description', 'format' => 'text' ),
	'TARIFF_VALUE' => array( 'label' => 'Tariff', 'format' => 'num0' ),
	'TEMP_CONTROLLED' => array( 'label' => 'Temp Controlled', 'format' => 'bool', 'align' => 'center' ),
	'TEMPERATURE' => array( 'label' => 'Temp', 'format' => 'num0', 'align' => 'right' ),
	'TEMPERATURE_UNITS' => array( 'label' => 'Temp Units', 'format' => 'table',
		'table' => UNIT_TABLE, 'key' => 'UNIT_CODE', 'fields' => 'UNIT_NAME',
		'condition' => 'UNIT_TYPE = \'temperature\'', 'nolink' => true ),

);

//! Edit/Delete Button Specifications - For use with sts_result

$sts_result_commodity_classs_edit = array(
	'title' => '<img src="images/commodity_class_icon.png" alt="commodity_class_icon" height="24"> Commodity Classes',
	'sort' => 'CC_NAME asc',
	'cancel' => 'index.php',
	'add' => 'exp_addcommodity_class.php',
	//'actionextras' => 'disabled',
	'addbutton' => 'Add Commodity Class',
	'cancelbutton' => 'Back',
	'filters_html' => '<a class="btn btn-sm btn-default" href="exp_listcommodity.php"><img src="images/commodity_icon.png" alt="commodity_icon" height="18"> Commodities</a>',
	'rowbuttons' => array(
		array( 'url' => 'exp_editcommodity_class.php?CODE=', 'key' => 'COMMODITY_CLASS_CODE', 'label' => 'CC_NAME', 'tip' => 'Edit commodity class ', 'icon' => 'glyphicon glyphicon-edit' ),
		array( 'url' => 'exp_deletecommodity_class.php?CODE=', 'key' => 'COMMODITY_CLASS_CODE', 'label' => 'CC_NAME', 'tip' => 'Delete commodity class ', 'icon' => 'glyphicon glyphicon-trash', 'confirm' => 'yes' )
	)
);


?>
