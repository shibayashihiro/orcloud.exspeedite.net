<?php

// no direct access
defined('_STS_INCLUDE') or die('Restricted access');

require_once( "sts_table_class.php" );

class sts_business_code extends sts_table {

	private $default_reports = array(
		array( 'CATEGORY' => 'main', 'report' => 'Miles per hour', 
			'THE_VALUE' => '50', 'COMMENT' => 'Average speed for calculating distance -> time' ),
		array( 'CATEGORY' => 'main', 'report' => 'Load Time', 
			'THE_VALUE' => '2', 'COMMENT' => 'Average time for loading (hours)' ),
	);
	
	// Constructor does not need the table name
	public function __construct( $database, $debug = false ) {

		$this->debug = $debug;
		$this->primary_key = "BUSINESS_CODE";
		if( $this->debug ) echo "<p>Create sts_business_code</p>";
		parent::__construct( $database, BUSINESS_CODE_TABLE, $debug);
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

$sts_form_add_bc_form = array(	//! $sts_form_add_bc_form
	'title' => '<img src="images/setting_icon.png" alt="setting_icon" height="24"> Add Business Code',
	'action' => 'exp_addbc.php',
	//'actionextras' => 'disabled',
	'cancel' => 'exp_listbc.php',
	'name' => 'addbc',
	'okbutton' => 'Save Changes',
	'saveadd' => 'Add Another',
	'cancelbutton' => 'Cancel',
		'layout' => '
	<div class="form-group">
		<div class="col-sm-12">
			<div class="form-group">
				<label for="BC_NAME" class="col-sm-2 control-label">#BC_NAME#</label>
				<div class="col-sm-4">
					%BC_NAME%
				</div>
				<div class="col-sm-4">
					<label>Name to be seen on screen</label>
				</div>
			</div>
			<div class="form-group">
				<label for="APPLIES_TO" class="col-sm-2 control-label">#APPLIES_TO#</label>
				<div class="col-sm-4">
					%APPLIES_TO%
				</div>
				<div class="col-sm-4">
					<label>What screens to be seen on</label>
				</div>
			</div>
			<div class="form-group">
				<label for="BC_DESCRIPTION" class="col-sm-2 control-label">#BC_DESCRIPTION#</label>
				<div class="col-sm-8">
					%BC_DESCRIPTION%
				</div>
			</div>
		</div>
		<div class="col-sm-6">
		</div>
	</div>
	
	'
);

$sts_form_edit_bc_form = array(
	'title' => '<img src="images/setting_icon.png" alt="setting_icon" height="24"> Edit Business Code',
	'action' => 'exp_editbc.php',
	//'actionextras' => 'disabled',
	'cancel' => 'exp_listbc.php',
	'name' => 'editbc',
	'okbutton' => 'Save Changes',
	'cancelbutton' => 'Back to business codes',
		'layout' => '
		%BUSINESS_CODE%
	<div class="form-group">
		<div class="col-sm-12">
			<div class="form-group">
				<label for="BC_NAME" class="col-sm-2 control-label">#BC_NAME#</label>
				<div class="col-sm-4">
					%BC_NAME%
				</div>
				<div class="col-sm-4">
					<label>Name to be seen on menus</label>
				</div>
			</div>
			<div class="form-group">
				<label for="APPLIES_TO" class="col-sm-2 control-label">#APPLIES_TO#</label>
				<div class="col-sm-4">
					%APPLIES_TO%
				</div>
				<div class="col-sm-4">
					<label>What screens to be seen on</label>
				</div>
			</div>
			<div class="form-group">
				<label for="BC_DESCRIPTION" class="col-sm-2 control-label">#BC_DESCRIPTION#</label>
				<div class="col-sm-8">
					%BC_DESCRIPTION%
				</div>
			</div>
		</div>
		<div class="col-sm-6">
		</div>
	</div>
	
	'
);

//! Field Specifications - For use with sts_form

$sts_form_add_bc_fields = array(
	'BC_NAME' => array( 'label' => 'Name', 'format' => 'text' ),
	'APPLIES_TO' => array( 'label' => 'Applies To', 'format' => 'enum' ),
	'BC_DESCRIPTION' => array( 'label' => 'Description', 'format' => 'text' ),
);

$sts_form_edit_bc_fields = array(
	'BUSINESS_CODE' => array( 'format' => 'hidden' ),
	'BC_NAME' => array( 'label' => 'Name', 'format' => 'text' ),
	'APPLIES_TO' => array( 'label' => 'Applies To', 'format' => 'enum' ),
	'BC_DESCRIPTION' => array( 'label' => 'Description', 'format' => 'text' ),
);

//! Layout Specifications - For use with sts_result

$sts_result_bc_layout = array(
	'BUSINESS_CODE' => array( 'format' => 'hidden' ),
	'BC_NAME' => array( 'label' => 'Name', 'format' => 'text' ),
	'APPLIES_TO' => array( 'label' => 'Applies To', 'format' => 'enum' ),
	'BC_DESCRIPTION' => array( 'label' => 'Description', 'format' => 'text' ),
);

//! Edit/Delete Button Specifications - For use with sts_result

$sts_result_bc_edit = array(
	'title' => '<img src="images/setting_icon.png" alt="setting_icon" height="24"> Business Codes',
	'sort' => 'BC_NAME asc',
	'cancel' => 'index.php',
	'add' => 'exp_addbc.php',
	//'actionextras' => 'disabled',
	'addbutton' => 'Add Business Code',
	'cancelbutton' => 'Back',
	'rowbuttons' => array(
		array( 'url' => 'exp_editbc.php?CODE=', 'key' => 'BUSINESS_CODE', 'label' => 'BC_NAME', 'tip' => 'Edit business code ', 'icon' => 'glyphicon glyphicon-edit' ),
		array( 'url' => 'exp_deletebc.php?CODE=', 'key' => 'BUSINESS_CODE', 'label' => 'BC_NAME', 'tip' => 'Delete business code ', 'tip2' => 'DO NOT DO THIS IF YOU ARE USING THIS CODE',
		'icon' => 'glyphicon glyphicon-trash', 'confirm' => 'yes' )
	)
);


?>
