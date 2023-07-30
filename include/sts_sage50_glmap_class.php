<?php

// $Id: sts_sage50_glmap_class.php 3884 2020-01-30 00:21:42Z duncan $
// Sage50 GL Map class, Maps office + currency + business code = GL account.

// no direct access
defined('_STS_INCLUDE') or die('Restricted access');

require_once( "sts_table_class.php" );
require_once( "sts_setting_class.php" );

class sts_sage50_glmap extends sts_table {

	// Constructor does not need the table name
	public function __construct( $database, $debug = false ) {

		$this->debug = $debug;
		$this->primary_key = "MAP_CODE";
		if( $this->debug ) echo "<p>Create sts_sage50_glmap</p>";
		parent::__construct( $database, SAGE50_GLMAP_TABLE, $debug);
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

}

//! Form Specifications - For use with sts_form

$sts_form_add_sage50_glmap_form = array(	//! $sts_form_add_sage50_glmap_form
	'title' => ' Add Sage 50 GL Mapping',
	'action' => 'exp_addsage50_glmap.php',
	//'actionextras' => 'disabled',
	'cancel' => 'exp_editoffice.php?CODE=%OFFICE_CODE%',
	'name' => 'addsage50_glmap',
	'okbutton' => 'Save Changes',
	'cancelbutton' => 'Cancel',
		'layout' => '
	<div class="form-group">
		<div class="col-sm-6">
			<div class="form-group tighter">
				<label for="OFFICE_CODE" class="col-sm-4 control-label">#OFFICE_CODE#</label>
				<div class="col-sm-8">
					%OFFICE_CODE%
				</div>
			</div>
			<div class="form-group tighter">
				<label for="CURRENCY" class="col-sm-4 control-label">#CURRENCY#</label>
				<div class="col-sm-8">
					%CURRENCY%
				</div>
			</div>
			<div class="form-group tighter">
				<label for="BUSINESS_CODE" class="col-sm-4 control-label">#BUSINESS_CODE#</label>
				<div class="col-sm-8">
					%BUSINESS_CODE%
				</div>
			</div>
			<div class="form-group tighter">
				<label for="ITEM" class="col-sm-4 control-label">#ITEM#</label>
				<div class="col-sm-8">
					%ITEM%
				</div>
			</div>
			<div class="form-group tighter">
				<label for="GLTYPE" class="col-sm-4 control-label">#GLTYPE#</label>
				<div class="col-sm-8">
					%GLTYPE%
				</div>
			</div>
			<div class="form-group tighter">
				<label for="ADDRESS_2" class="col-sm-4 control-label">#SAGE50_GL#</label>
				<div class="col-sm-8">
					%SAGE50_GL%
				</div>
			</div>
		</div>
		<div class="col-sm-6">
			<div class="alert alert-info">
			<p>Office + Currency + Business Code + Item => Sage 50 GL account</p>
			<p>Note: Item only applies to income (invoice) types</p>
			<p>If Item = default, it matches all items that are not covered by a specific GL</p>
			</div>
		</div>
	</div>
	
	'
);

$sts_form_edit_sage50_glmap_form = array( //!$sts_form_edit_sage50_glmap_form
	'title' => 'Edit Sage 50 GL Mapping',
	'action' => 'exp_editsage50_glmap.php',
	//'actionextras' => 'disabled',
	'cancel' => 'exp_editoffice.php?CODE=%OFFICE_CODE%',
	'name' => 'editsage50_glmap',
	'okbutton' => 'Save Changes',
	'cancelbutton' => 'Cancel',
		'layout' => '
		%MAP_CODE%
	<div class="form-group">
		<div class="col-sm-6">
			<div class="form-group tighter">
				<label for="OFFICE_CODE" class="col-sm-4 control-label">#OFFICE_CODE#</label>
				<div class="col-sm-8">
					%OFFICE_CODE%
				</div>
			</div>
			<div class="form-group tighter">
				<label for="CURRENCY" class="col-sm-4 control-label">#CURRENCY#</label>
				<div class="col-sm-8">
					%CURRENCY%
				</div>
			</div>
			<div class="form-group tighter">
				<label for="BUSINESS_CODE" class="col-sm-4 control-label">#BUSINESS_CODE#</label>
				<div class="col-sm-8">
					%BUSINESS_CODE%
				</div>
			</div>
			<div class="form-group tighter">
				<label for="ITEM" class="col-sm-4 control-label">#ITEM#</label>
				<div class="col-sm-8">
					%ITEM%
				</div>
			</div>
			<div class="form-group tighter">
				<label for="GLTYPE" class="col-sm-4 control-label">#GLTYPE#</label>
				<div class="col-sm-8">
					%GLTYPE%
				</div>
			</div>
			<div class="form-group tighter">
				<label for="SAGE50_GL" class="col-sm-4 control-label">#SAGE50_GL#</label>
				<div class="col-sm-8">
					%SAGE50_GL%
				</div>
			</div>
		</div>
		<div class="col-sm-6">
			<div class="alert alert-info">
			<p>Office + Currency + Business Code + Item => Sage 50 GL account</p>
			<p>Note: Item only applies to income (invoice) types</p>
			<p>If Item = default, it matches all items that are not covered by a specific GL</p>
			</div>
		</div>
	</div>
	
	'
);

//! Field Specifications - For use with sts_form

$sts_form_add_sage50_glmap_fields = array(	//! $sts_form_add_sage50_glmap_fields
	//'OFFICE_CODE' => array( 'format' => 'hidden' ),
	'OFFICE_CODE' => array( 'label' => 'Office', 'format' => 'table',
		'table' => OFFICE_TABLE, 'key' => 'OFFICE_CODE', 'fields' => 'OFFICE_NAME',
		'extras' => 'readonly' ),
	'CURRENCY' => array( 'label' => 'Currency', 'format' => 'enum' ),
	'BUSINESS_CODE' => array( 'label' => 'Business Code', 'format' => 'table',
		'table' => BUSINESS_CODE_TABLE, 'key' => 'BUSINESS_CODE', 'fields' => 'BC_NAME',
		'condition' => 'APPLIES_TO = \'shipment\'' ),
	'ITEM' => array( 'label' => 'Item', 'format' => 'enum' ),
	'GLTYPE' => array( 'label' => 'Type', 'format' => 'enum' ),
	'SAGE50_GL' => array( 'label' => 'Sage GL', 'format' => 'text' ),
);

$sts_form_edit_sage50_glmap_fields = array(	//! $sts_form_edit_sage50_glmap_fields
	'MAP_CODE' => array( 'format' => 'hidden' ),
	//'OFFICE_CODE' => array( 'format' => 'hidden' ),
	'OFFICE_CODE' => array( 'label' => 'Office', 'format' => 'table',
		'table' => OFFICE_TABLE, 'key' => 'OFFICE_CODE', 'fields' => 'OFFICE_NAME',
		'extras' => 'readonly' ),
	'CURRENCY' => array( 'label' => 'Currency', 'format' => 'enum' ),
	'BUSINESS_CODE' => array( 'label' => 'Business Code', 'format' => 'table',
		'table' => BUSINESS_CODE_TABLE, 'key' => 'BUSINESS_CODE', 'fields' => 'BC_NAME',
		'condition' => 'APPLIES_TO = \'shipment\'' ),
	'ITEM' => array( 'label' => 'Item', 'format' => 'enum' ),
	'GLTYPE' => array( 'label' => 'Type', 'format' => 'enum' ),
	'SAGE50_GL' => array( 'label' => 'Sage GL', 'format' => 'text' ),
);

//! Layout Specifications - For use with sts_result

$sts_result_sage50_glmap_layout = array(
	'MAP_CODE' => array( 'format' => 'hidden' ),
	//'OFFICE_CODE' => array( 'label' => 'Office', 'format' => 'table',
	//	'table' => OFFICE_TABLE, 'key' => 'OFFICE_CODE', 'fields' => 'OFFICE_NAME' ),
	'CURRENCY' => array( 'label' => 'Currency', 'format' => 'text' ),
	'BUSINESS_CODE' => array( 'label' => 'Business Code', 'format' => 'table',
		'table' => BUSINESS_CODE_TABLE, 'key' => 'BUSINESS_CODE', 'fields' => 'BC_NAME' ),
	'ITEM' => array( 'label' => 'ITEM', 'format' => 'enum' ),
	'GLTYPE' => array( 'label' => 'Type', 'format' => 'text' ),
	'SAGE50_GL' => array( 'label' => 'Sage GL', 'format' => 'text' ),
	'CHANGED_DATE' => array( 'label' => 'Changed', 'format' => 'timestamp-s', 'length' => 90 ),
	'CHANGED_BY' => array( 'label' => 'By', 'format' => 'table',
		'table' => USER_TABLE, 'key' => 'USER_CODE', 'fields' => 'USERNAME', 'link' => 'exp_edituser.php?CODE=' )

);

//! Edit/Delete Button Specifications - For use with sts_result

$sts_result_sage50_glmap_edit = array(
	'title' => 'Sage 50 GL Mapping',
	'sort' => 'CURRENCY asc, BUSINESS_CODE asc, GLTYPE asc, ITEM asc',
	'add' => 'exp_addsage50_glmap.php',
	'addbutton' => 'Add Mapping',
	'rowbuttons' => array(
		array( 'url' => 'exp_editsage50_glmap.php?CODE=', 'key' => 'MAP_CODE', 'label' => 'SAGE50_GL', 'tip' => 'Edit Sage 50 GL Mapping ', 'icon' => 'glyphicon glyphicon-edit' ),
		array( 'url' => 'exp_deletesage50_glmap.php?CODE=', 'key' => 'MAP_CODE', 'label' => 'SAGE50_GL', 'tip' => 'Delete Sage 50 GL Mapping ', 'icon' => 'glyphicon glyphicon-trash', 'confirm' => 'yes' )
	)
);

?>