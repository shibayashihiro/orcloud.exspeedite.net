<?php

// $Id: sts_insp_list_item_class.php 4350 2021-03-02 19:14:52Z duncan $
// Inspection list items

// no direct access
defined('_STS_INCLUDE') or die('Restricted access');

require_once( "sts_table_class.php" );

class sts_insp_list_item extends sts_table {

	private $default_tractor = array(
		array("ITEM_TARGET" => 'tractor', "ITEM_TYPE" => 'group', "SEQUENCE_NO" => 1,
			"ITEM_TEXT" => 'Initial Checks',
			"ITEM_HELP" => 'You can create help text to explain items in the inspection checklist' ),
		array("ITEM_TARGET" => 'tractor', "ITEM_TYPE" => 'check', "SEQUENCE_NO" => 2,
			"ITEM_TEXT" => 'Check / Instrument Gauges (visual) / Low Air Warning Device' ),
		array("ITEM_TARGET" => 'tractor', "ITEM_TYPE" => 'check', "SEQUENCE_NO" => 3,
			"ITEM_TEXT" => 'Check Windshield Wiper & Washer Operation' ),
		array("ITEM_TARGET" => 'tractor', "ITEM_TYPE" => 'check', "SEQUENCE_NO" => 4,
			"ITEM_TEXT" => 'Inspect Glass, Mirrors & Horns' ),
		array("ITEM_TARGET" => 'tractor', "ITEM_TYPE" => 'group', "SEQUENCE_NO" => 5,
			"ITEM_TEXT" => 'Check Fluids' ),
		array("ITEM_TARGET" => 'tractor', "ITEM_TYPE" => 'action', "SEQUENCE_NO" => 6,
			"ITEM_TEXT" => 'Remove Pan Plug & Drain Oil' ),

		array("ITEM_TARGET" => 'tractor', "ITEM_TYPE" => 'tires', "SEQUENCE_NO" => 7 ),
		array("ITEM_TARGET" => 'tractor', "ITEM_TYPE" => 'parts', "SEQUENCE_NO" => 8 ),
		array("ITEM_TARGET" => 'tractor', "ITEM_TYPE" => 'Notes', "SEQUENCE_NO" => 9 ),
	);
	
	private $default_trailer = array(
		array("ITEM_TARGET" => 'trailer', "ITEM_TYPE" => 'group', "SEQUENCE_NO" => 20,
			"ITEM_TEXT" => 'Lights',
			"ITEM_HELP" => 'You can create help text to explain items in the inspection checklist' ),
		array("ITEM_TARGET" => 'trailer', "ITEM_TYPE" => 'check', "SEQUENCE_NO" => 21,
			"ITEM_TEXT" => 'Clearance lights' ),
		array("ITEM_TARGET" => 'trailer', "ITEM_TYPE" => 'check', "SEQUENCE_NO" => 22,
			"ITEM_TEXT" => 'Side marker lights' ),
		array("ITEM_TARGET" => 'trailer', "ITEM_TYPE" => 'check', "SEQUENCE_NO" => 23,
			"ITEM_TEXT" => 'Tail lights' ),
		array("ITEM_TARGET" => 'trailer', "ITEM_TYPE" => 'check', "SEQUENCE_NO" => 24,
			"ITEM_TEXT" => 'Stop lights' ),

		array("ITEM_TARGET" => 'trailer', "ITEM_TYPE" => 'group', "SEQUENCE_NO" => 25,
			"ITEM_TEXT" => 'Brakes',
			"ITEM_HELP" => 'You can create help text to explain items in the inspection checklist' ),
		array("ITEM_TARGET" => 'trailer', "ITEM_TYPE" => 'check', "SEQUENCE_NO" => 26,
			"ITEM_TEXT" => 'Brake Operation' ),
		array("ITEM_TARGET" => 'trailer', "ITEM_TYPE" => 'check', "SEQUENCE_NO" => 27,
			"ITEM_TEXT" => 'Air Tanks' ),
		array("ITEM_TARGET" => 'trailer', "ITEM_TYPE" => 'check', "SEQUENCE_NO" => 28,
			"ITEM_TEXT" => 'Brake Lining', "ITEM_EXTRA" => 'brakes',
			"ITEM_HELP" => 'This item has an extra check for the brake lining' ),
		array("ITEM_TARGET" => 'trailer', "ITEM_TYPE" => 'check', "SEQUENCE_NO" => 29,
			"ITEM_TEXT" => 'Tubing & Hose' ),

		array("ITEM_TARGET" => 'trailer', "ITEM_TYPE" => 'damage', "SEQUENCE_NO" => 30,
			"ITEM_TEXT" => 'Click to show location of damage and describe below ' ),
		array("ITEM_TARGET" => 'trailer', "ITEM_TYPE" => 'tires', "SEQUENCE_NO" => 31 ),
		array("ITEM_TARGET" => 'trailer', "ITEM_TYPE" => 'parts', "SEQUENCE_NO" => 32 ),
		array("ITEM_TARGET" => 'trailer', "ITEM_TYPE" => 'Notes', "SEQUENCE_NO" => 33 ),
	);

	// Constructor does not need the table name
	public function __construct( $database, $debug = false ) {

		$this->debug = $debug;
		$this->primary_key = "ITEM_CODE";
		if( $this->debug ) echo "<p>Create sts_insp_list_item</p>";
		parent::__construct( $database, INSP_LIST_ITEMS_TABLE, $debug);
		$this->load_defaults();
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
        
    //! Load default items into table.
    // Check once per session.
    private function load_defaults() {
		if( $this->debug ) echo "<p>".__METHOD__.": session v = ".(isset($_SESSION["DEFAULT_ITEMS_LOADED2"]) ? 'set' : 'unset')."</p>";
	    if( ! isset($_SESSION["DEFAULT_ITEMS_LOADED2"])) {
		    $_SESSION["DEFAULT_ITEMS_LOADED2"] = true;
		    $check = $this->fetch_rows("", "COUNT(*) AS NUM");
		    
		    if( is_array($check) && count($check) == 1 && $check[0]["NUM"] == 0 ) {
			    foreach( $this->default_tractor as $row ) {
				    $this->add($row);
			    }
			    foreach( $this->default_trailer as $row ) {
				    $this->add($row);
			    }
		    }
		}
	}

    public function next_seq() {
	    $result = '';
	    $check = $this->fetch_rows("", "MAX(SEQUENCE_NO) + 1 AS NEXT_SEQ");
	    if( is_array($check) && count($check) == 1 && isset($check[0]["NEXT_SEQ"]))
	    	$result = $check[0]["NEXT_SEQ"];
	    return $result;
    }

}

//! Form Specifications - For use with sts_form

$sts_form_add_insp_list_item_form = array(	//! $sts_form_add_insp_list_item_form
	'title' => '<span class="glyphicon glyphicon-th-list"></span> Add Inspection List Item',
	'action' => 'exp_addinsp_list_item.php',
	//'actionextras' => 'disabled',
	'cancel' => 'exp_editrm_form.php?CODE=%RM_FORM%',
	'name' => 'addbc',
	'okbutton' => 'Save Changes',
	'saveadd' => 'Add Another',
	'cancelbutton' => 'Cancel',
		'layout' => '
	%SEQUENCE_NO%
	<div class="form-group">
		<div class="col-sm-12">
			<div class="form-group">
				<label for="ITEM_TARGET" class="col-sm-2 control-label">#ITEM_TARGET#</label>
				<div class="col-sm-2">
					%ITEM_TARGET%
				</div>
				<div class="col-sm-4">
					<label>What does this apply to</label>
				</div>
			</div>
			<div class="form-group">
				<label for="RM_FORM" class="col-sm-2 control-label">#RM_FORM#</label>
				<div class="col-sm-2">
					%RM_FORM%
				</div>
				<div class="col-sm-4">
					<label>Which form is this in</label>
				</div>
			</div>
			<div class="form-group">
				<label for="ITEM_TYPE" class="col-sm-2 control-label">#ITEM_TYPE#</label>
				<div class="col-sm-2">
					%ITEM_TYPE%
				</div>
				<div class="col-sm-4">
					<label>check or do something</label>
				</div>
			</div>
			<div class="form-group">
				<label for="ITEM_EXTRA" class="col-sm-2 control-label">#ITEM_EXTRA#</label>
				<div class="col-sm-2">
					%ITEM_EXTRA%
				</div>
				<div class="col-sm-4">
					<label>extra checks</label>
				</div>
			</div>
			<div id="INCREMENT" class="form-group" hidden>
				<label for="INCREMENT" class="col-sm-2 control-label">#INCREMENT#</label>
				<div class="col-sm-2">
					%INCREMENT%
				</div>
				<div class="col-sm-4">
					<label>Auto-increment hours by this much</label>
				</div>
			</div>
			<div class="form-group">
				<label for="ITEM_TEXT" class="col-sm-2 control-label">#ITEM_TEXT#</label>
				<div class="col-sm-6">
					%ITEM_TEXT%
				</div>
				<div class="col-sm-4">
					<label>Text on the form</label>
				</div>
			</div>
			<div class="form-group">
				<label for="ITEM_HELP" class="col-sm-2 control-label">#ITEM_HELP#</label>
				<div class="col-sm-6">
					%ITEM_HELP%
				</div>
				<div class="col-sm-4">
					<label>Extra help to explain</label>
				</div>
			</div>
		</div>
	</div>
	<div class="alert alert-info" role="alert" id="extra" hidden>
		<h4>The following fields will appear on reports in edit tractor/trailer screens:</h4>
		<table class="table table-condensed table-bordered">
			<tr>
				<th>Use This Text (comma=alternatives)</th><th>Use This Type</th><th>Will Appear As</th>
			</tr>
			<tr>
				<td>PO NUMBER, PO #</td><td>serial</td><td>PO#</td>
			</tr>
			<tr>
				<td>INVOICE #</td><td>serial</td><td>Invoice#</td>
			</tr>
			<tr>
				<td>VENDOR</td><td>text</td><td>Vendor</td>
			</tr>
			<tr>
				<td>REASON FOR REPAIR</td><td>text</td><td>Reason</td>
			</tr>
			<tr>
				<td>COST</td><td>cost</td><td>Cost</td>
			</tr>
			<tr>
				<td>ODOMETER MONTH BEGIN</td><td>odometer</td><td>ODO Begin</td>
			</tr>
			<tr>
				<td>ODOMETER MONTH END</td><td>odometer</td><td>ODO End</td>
			</tr>
			<tr>
				<td>HOURS MONTH BEGIN</td><td>hours</td><td>Hrs Begin</td>
			</tr>
			<tr>
				<td>HOURS MONTH END</td><td>hours</td><td>Hrs End</td>
			</tr>
			<tr>
				<td>Driver Name, Driver</td><td>driver</td><td>Driver</td>
			</tr>
		</table>
	</div>
	
	'
);

$sts_form_edit_insp_list_item_form = array( //! $sts_form_edit_insp_list_item_form
	'title' => '<span class="glyphicon glyphicon-th-list"></span> Edit Inspection List Item',
	'action' => 'exp_editinsp_list_item.php',
	//'actionextras' => 'disabled',
	'cancel' => 'exp_editrm_form.php?CODE=%RM_FORM%',
	'name' => 'editinsp_list_item',
	'okbutton' => 'Save Changes',
	'cancelbutton' => 'Back',
		'layout' => '
		%ITEM_CODE%
		%SEQUENCE_NO%
	<div class="form-group">
		<div class="col-sm-12">
			<div class="form-group">
				<label for="ITEM_TARGET" class="col-sm-2 control-label">#ITEM_TARGET#</label>
				<div class="col-sm-2">
					%ITEM_TARGET%
				</div>
				<div class="col-sm-4">
					<label>What does this apply to</label>
				</div>
			</div>
			<div class="form-group">
				<label for="RM_FORM" class="col-sm-2 control-label">#RM_FORM#</label>
				<div class="col-sm-2">
					%RM_FORM%
				</div>
				<div class="col-sm-4">
					<label>Which form is this in</label>
				</div>
			</div>
			<div class="form-group">
				<label for="ITEM_TYPE" class="col-sm-2 control-label">#ITEM_TYPE#</label>
				<div class="col-sm-2">
					%ITEM_TYPE%
				</div>
				<div class="col-sm-4">
					<label>check or do something</label>
				</div>
			</div>
			<div class="form-group">
				<label for="ITEM_EXTRA" class="col-sm-2 control-label">#ITEM_EXTRA#</label>
				<div class="col-sm-2">
					%ITEM_EXTRA%
				</div>
				<div class="col-sm-4">
					<label>extra checks</label>
				</div>
			</div>
			<div id="INCREMENT" class="form-group" hidden>
				<label for="INCREMENT" class="col-sm-2 control-label">#INCREMENT#</label>
				<div class="col-sm-2">
					%INCREMENT%
				</div>
				<div class="col-sm-4">
					<label>Auto-increment hours by this much</label>
				</div>
			</div>
			<div class="form-group">
				<label for="ITEM_TEXT" class="col-sm-2 control-label">#ITEM_TEXT#</label>
				<div class="col-sm-6">
					%ITEM_TEXT%
				</div>
				<div class="col-sm-4">
					<label>Text on the form</label>
				</div>
			</div>
			<div class="form-group">
				<label for="ITEM_HELP" class="col-sm-2 control-label">#ITEM_HELP#</label>
				<div class="col-sm-6">
					%ITEM_HELP%
				</div>
				<div class="col-sm-4">
					<label>Extra help to explain</label>
				</div>
			</div>
		</div>
	</div>
	<div class="alert alert-info" role="alert" id="extra" hidden>
		<h4>The following fields will appear on reports in edit tractor/trailer screens:</h4>
		<table class="table table-condensed table-bordered">
			<tr>
				<th>Use This Text (comma=alternatives)</th><th>Use This Type</th><th>Will Appear As</th>
			</tr>
			<tr>
				<td>PO NUMBER, PO #</td><td>serial</td><td>PO#</td>
			</tr>
			<tr>
				<td>INVOICE #</td><td>serial</td><td>Invoice#</td>
			</tr>
			<tr>
				<td>VENDOR</td><td>text</td><td>Vendor</td>
			</tr>
			<tr>
				<td>REASON FOR REPAIR</td><td>text</td><td>Reason</td>
			</tr>
			<tr>
				<td>COST</td><td>cost</td><td>Cost</td>
			</tr>
			<tr>
				<td>ODOMETER MONTH BEGIN</td><td>odometer</td><td>ODO Begin</td>
			</tr>
			<tr>
				<td>ODOMETER MONTH END</td><td>odometer</td><td>ODO End</td>
			</tr>
			<tr>
				<td>HOURS MONTH BEGIN</td><td>hours</td><td>Hrs Begin</td>
			</tr>
			<tr>
				<td>HOURS MONTH END</td><td>hours</td><td>Hrs End</td>
			</tr>
			<tr>
				<td>Driver Name, Driver</td><td>driver</td><td>Driver</td>
			</tr>
		</table>
	</div>
	
	'
);

//! Field Specifications - For use with sts_form

$sts_form_add_insp_list_item_fields = array( //! $sts_form_add_insp_list_item_fields
	'SEQUENCE_NO' => array( 'label' => 'Seq#', 'format' => 'hidden', 'align' => 'right' ),
	'ITEM_TARGET' => array( 'label' => 'Target', 'format' => 'table', 'static' => true,
		'table' => RM_FORM_TABLE, 'key' => 'FORM_CODE', 'pk' => 'RM_FORM', 'fields' => 'UNIT_TYPE' ),
	'RM_FORM' => array( 'label' => 'Form', 'format' => 'table', 'static' => true,
		'table' => RM_FORM_TABLE, 'key' => 'FORM_CODE', 'fields' => 'FORM_NAME' ),
	'ITEM_TYPE' => array( 'label' => 'Type', 'format' => 'enum' ),
	'ITEM_TEXT' => array( 'label' => 'Text', 'format' => 'text' ),
	'ITEM_HELP' => array( 'label' => 'Help', 'format' => 'textarea' ),
	'ITEM_EXTRA' => array( 'label' => 'Extra', 'format' => 'enum' ),
	'INCREMENT' => array( 'label' => 'Increment', 'format' => 'number', 'align' => 'right' ),
);

$sts_form_edit_insp_list_item_fields = array( //! $sts_form_edit_insp_list_item_fields
	'ITEM_CODE' => array( 'format' => 'hidden' ),
	'SEQUENCE_NO' => array( 'label' => 'Seq#', 'format' => 'hidden', 'align' => 'right' ),
	'ITEM_TARGET' => array( 'label' => 'Target', 'format' => 'table', 'static' => true,
		'table' => RM_FORM_TABLE, 'key' => 'FORM_CODE', 'pk' => 'RM_FORM', 'fields' => 'UNIT_TYPE' ),
	'RM_FORM' => array( 'label' => 'Form', 'format' => 'table', 'static' => true,
		'table' => RM_FORM_TABLE, 'key' => 'FORM_CODE', 'fields' => 'FORM_NAME' ),
	'ITEM_TYPE' => array( 'label' => 'Type', 'format' => 'enum' ),
	'ITEM_TEXT' => array( 'label' => 'Text', 'format' => 'text' ),
	'ITEM_HELP' => array( 'label' => 'Help', 'format' => 'textarea' ),
	'ITEM_EXTRA' => array( 'label' => 'Extra', 'format' => 'enum' ),
	'INCREMENT' => array( 'label' => 'Increment', 'format' => 'number', 'align' => 'right' ),
);

//! Layout Specifications - For use with sts_result

$sts_result_insp_list_item_layout = array( //! $sts_result_insp_list_item_layout
	'SEQUENCE_NO' => array( 'label' => 'Order', 'format' => 'number', 'align' => 'right',
		'orderable' => true ),
	'ITEM_CODE' => array( 'label' => 'Item#', 'format' => 'hidden', 'align' => 'right' ),
	'ITEM_TARGET' => array( 'label' => 'Target', 'format' => 'hidden' ),
	'ITEM_TYPE' => array( 'label' => 'Type', 'format' => 'text' ),
	'ITEM_TEXT' => array( 'label' => 'Text', 'format' => 'text' ),
	'ITEM_EXTRA' => array( 'label' => 'Extra', 'format' => 'text' ),
	'CHANGED_DATE' => array( 'label' => 'Changed', 'format' => 'timestamp-s' ),
	'CHANGED_BY' => array( 'label' => 'By', 'format' => 'table',
		'table' => USER_TABLE, 'key' => 'USER_CODE', 'fields' => 'USERNAME',
	//	'link' => 'exp_edituser.php?CODE='
		)
);

//! Edit/Delete Button Specifications - For use with sts_result

$sts_result_insp_list_item_edit = array( //! $sts_result_insp_list_item_edit
	'title' => '<span class="glyphicon glyphicon-th-list"></span> Inspection List Items',
	'sort' => 'SEQUENCE_NO asc',
	//'cancel' => 'index.php',
	'add' => 'exp_addinsp_list_item.php',
	//'actionextras' => 'disabled',
	'addbutton' => 'Add Item',
	//'cancelbutton' => 'Back',
	'rowbuttons' => array(
		array( 'url' => 'exp_editinsp_list_item.php?CODE=', 'key' => 'ITEM_CODE', 'label' => 'ITEM_TEXT', 'tip' => 'Edit item ', 'icon' => 'glyphicon glyphicon-edit' ),
		array( 'url' => 'exp_deleteinsp_list_item.php?CODE=', 'key' => 'ITEM_CODE', 'label' => 'ITEM_TEXT', 'tip' => 'Delete item ', 'icon' => 'glyphicon glyphicon-trash', 'confirm' => 'yes' )
	)
);


?>
