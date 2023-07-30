<?php

// no direct access
defined('_STS_INCLUDE') or die('Restricted access');

require_once( "sts_table_class.php" );

class sts_pallet extends sts_table {

	// Constructor does not need the table name
	public function __construct( $database, $debug = false ) {

		$this->debug = $debug;
		$this->primary_key = "PALLET_CODE";
		if( $this->debug ) echo "<p>Create sts_pallet</p>";
		parent::__construct( $database, PALLET_TABLE, $debug);
	}
	

}

//! Form Specifications - For use with sts_form

$sts_form_addpallet_form = array(	//! sts_form_addpallet_form
	'title' => '<img src="images/pallet_icon.png" alt="pallet_icon" height="24"> Add Pallet Adjustment',
	'action' => 'exp_addpallet.php',
	//'actionextras' => 'disabled',
	'cancel' => 'exp_listpallet.php',
	'name' => 'addpallet',
	'okbutton' => 'Save Changes',
	//'saveadd' => 'Add Another',
	'cancelbutton' => 'Cancel',
		'layout' => '
	<div class="form-group">
		<div class="col-sm-4">
			<div class="form-group">
				<label for="EXECUTION_DATE" class="col-sm-4 control-label">#EXECUTION_DATE#</label>
				<div class="col-sm-8">
					%EXECUTION_DATE%
				</div>
			</div>
			<div class="form-group">
				<label for="PALLET_CLIENT" class="col-sm-4 control-label">#PALLET_CLIENT#</label>
				<div class="col-sm-8">
					%PALLET_CLIENT%
				</div>
			</div>
			<div class="form-group">
				<label for="CLIENT2" class="col-sm-4 control-label">Pallet Count</label>
				<div class="col-sm-8" id="CLIENT2">
				</div>
			</div>
		</div>
		<div class="col-sm-4">
			<div class="form-group">
				<label for="EXECUTION_TYPE" class="col-sm-4 control-label">#EXECUTION_TYPE#</label>
				<div class="col-sm-8">
					%EXECUTION_TYPE%
				</div>
			</div>
			<div class="form-group">
				<label for="QUANTITY" class="col-sm-4 control-label">#QUANTITY#</label>
				<div class="col-sm-8">
					%QUANTITY%
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

$sts_form_editpallet_form = array(
	'title' => '<img src="images/pallet_icon.png" alt="pallet_icon" height="24"> Edit Pallet',
	'action' => 'exp_editpallet.php',
	//'actionextras' => 'disabled',
	'cancel' => 'exp_listpallet.php',
	'name' => 'editpallet',
	'okbutton' => 'Save Changes to Pallet',
	'cancelbutton' => 'Back to Pallets',
		'layout' => '
		%PALLET_CODE%
	<div class="form-group">
		<div class="col-sm-4">
			<div class="form-group">
				<label for="SLIP_NUMBER" class="col-sm-4 control-label">#SLIP_NUMBER#</label>
				<div class="col-sm-8">
					%SLIP_NUMBER%
				</div>
			</div>
			<div class="form-group">
				<label for="EXECUTION_DATE" class="col-sm-4 control-label">#EXECUTION_DATE#</label>
				<div class="col-sm-8">
					%EXECUTION_DATE%
				</div>
			</div>
			<div class="form-group">
				<label for="EXECUTION_TYPE" class="col-sm-4 control-label">#EXECUTION_TYPE#</label>
				<div class="col-sm-8">
					%EXECUTION_TYPE%
				</div>
			</div>
			<div class="form-group">
				<label for="QUANTITY" class="col-sm-4 control-label">#QUANTITY#</label>
				<div class="col-sm-8">
					%QUANTITY%
				</div>
			</div>
			<div class="form-group">
				<label for="PALLET_TYPE" class="col-sm-4 control-label">#PALLET_TYPE#</label>
				<div class="col-sm-8">
					%PALLET_TYPE%
				</div>
			</div>
		</div>
		<div class="col-sm-4">
			<div class="form-group">
				<label for="PALLET_CLIENT" class="col-sm-4 control-label">#PALLET_CLIENT#</label>
				<div class="col-sm-8">
					%PALLET_CLIENT%
				</div>
			</div>
			<div class="form-group">
				<label for="TRACTOR" class="col-sm-4 control-label">#TRACTOR#</label>
				<div class="col-sm-8">
					%TRACTOR%
				</div>
			</div>
			<div class="form-group">
				<label for="TRAILER" class="col-sm-4 control-label">#TRAILER#</label>
				<div class="col-sm-8">
					%TRAILER%
				</div>
			</div>
			<div class="form-group">
				<label for="PALLET_LOC" class="col-sm-4 control-label">#PALLET_LOC#</label>
				<div class="col-sm-8">
					%PALLET_LOC%
				</div>
			</div>
			<div class="form-group">
				<label for="BILL_NUMBER" class="col-sm-4 control-label">#BILL_NUMBER#</label>
				<div class="col-sm-8">
					%BILL_NUMBER%
				</div>
			</div>
		</div>
		<div class="col-sm-4">
		</div>
	</div>
	
	'
);

//! Field Specifications - For use with sts_form

$sts_form_add_pallet_fields = array(
	'PALLET_CODE' => array( 'format' => 'hidden' ),
	'EXECUTION_DATE' => array( 'label' => 'Date', 'format' => 'date' ),
	'EXECUTION_TYPE' => array( 'label' => 'Type', 'format' => 'enum' ),
	'PALLET_CLIENT' => array( 'label' => 'Client', 'format' => 'table',
		'table' => CLIENT_TABLE, 'key' => 'CLIENT_CODE', 'fields' => 'CLIENT_NAME',
		'condition' => 'ISDELETED = false' ),	// , 'link' => 'exp_editclient.php?CODE=' future?
	'QUANTITY' => array( 'label' => 'Qty', 'format' => 'number', 'align' => 'right' ),
	'COMMENTS' => array( 'label' => 'Comments', 'format' => 'text' ),
);

$sts_form_edit_pallet_fields = array(
	'PALLET_CODE' => array( 'format' => 'hidden' ),
	'EXECUTION_DATE' => array( 'label' => 'Date', 'format' => 'date' ),
	'EXECUTION_TYPE' => array( 'label' => 'Type', 'format' => 'enum' ),
	'PALLET_CLIENT' => array( 'label' => 'Client', 'format' => 'table',
		'table' => CLIENT_TABLE, 'key' => 'CLIENT_CODE', 'fields' => 'CLIENT_NAME',
		'condition' => 'ISDELETED = false' ),
	'QUANTITY' => array( 'label' => 'Qty', 'format' => 'number', 'align' => 'right' ),
	'COMMENTS' => array( 'label' => 'Comments', 'format' => 'text' ),
);

//! Layout Specifications - For use with sts_result

$sts_result_pallets_layout = array(
	'PALLET_CODE' => array( 'format' => 'hidden' ),
	'EXECUTION_DATE' => array( 'label' => 'Date', 'format' => 'date' ),
	'PALLET_CLIENT' => array( 'label' => 'Client', 'format' => 'table',
		'table' => CLIENT_TABLE, 'key' => 'CLIENT_CODE', 'fields' => 'CLIENT_NAME' ),
	'EXECUTION_TYPE' => array( 'label' => 'Type', 'format' => 'text' ),
	'QUANTITY' => array( 'label' => 'Qty', 'format' => 'num0', 'align' => 'right' ),
	'COMMENTS' => array( 'label' => 'Comments', 'format' => 'text' ),
	'CREATED_DATE' => array( 'label' => 'Created', 'format' => 'datetime' ),
	'CREATED_BY' => array( 'label' => 'Created By', 'format' => 'table',
		'table' => USER_TABLE, 'key' => 'USER_CODE', 'fields' => 'USERNAME' ),

);

//! Edit/Delete Button Specifications - For use with sts_result

$sts_result_pallets_edit = array(
	'title' => '<img src="images/pallet_icon.png" alt="pallet_icon" height="24"> Pallet Adjustments',
	'sort' => 'PALLET_CODE asc',
	'cancel' => 'index.php',
	'add' => 'exp_addpallet.php',
	//'actionextras' => 'disabled',
	'addbutton' => 'Add Pallet',
	'cancelbutton' => 'Back',
	//'rowbuttons' => array(
		//array( 'url' => 'exp_editpallet.php?CODE=', 'key' => 'PALLET_CODE', 'label' => 'PALLET_CODE', 'tip' => 'Edit pallet ', 'icon' => 'glyphicon glyphicon-edit' ),
		//array( 'url' => 'exp_deletepallet.php?CODE=', 'key' => 'PALLET_CODE', 'label' => 'PALLET_CODE', 'tip' => 'Delete pallet ', 'icon' => 'glyphicon glyphicon-trash', 'confirm' => 'yes' )
	//)
);


?>
