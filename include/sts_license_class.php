<?php

// no direct access
defined('_STS_INCLUDE') or die('Restricted access');

require_once( "sts_table_class.php" );

class sts_license extends sts_table {

	// Constructor does not need the table name
	public function __construct( $database, $debug = false ) {

		$this->debug = $debug;
		$this->primary_key = "LICENSE_CODE";
		if( $this->debug ) echo "<p>Create sts_license</p>";
		parent::__construct( $database, LICENSE_TABLE, $debug);
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
$sts_form_add_license = array(
	'title' => 'Add License Info for %PARENT_NAME%',
	'action' => 'exp_addlicense.php',
	'cancel' => 'exp_editdriver.php?CODE=%CONTACT_CODE%',
	//'popup' => true,	// issue with the toggle switches
	'name' => 'add_license',
	'okbutton' => 'Save Changes',
	'cancelbutton' => 'Cancel',
	'layout' => '
	%CONTACT_CODE%
	%CONTACT_SOURCE%
	<div class="form-group">
		<div class="col-sm-5">
			<div class="form-group">
				<label for="LICENSE_TYPE" class="col-sm-4 control-label">#LICENSE_TYPE#</label>
				<div class="col-sm-8">
					%LICENSE_TYPE%
				</div>
			</div>
			<div class="form-group">
				<label for="LICENSE_ENDORSEMENTS" class="col-sm-4 control-label">#LICENSE_ENDORSEMENTS#</label>
				<div class="col-sm-8">
					%LICENSE_ENDORSEMENTS%
				</div>
			</div>
		</div>
		<div class="col-sm-5">
			<div class="form-group">
				<label for="LICENSE_NUMBER" class="col-sm-4 control-label">#LICENSE_NUMBER#</label>
				<div class="col-sm-8">
					%LICENSE_NUMBER%
				</div>
			</div>
			<div class="form-group">
				<label for="LICENSE_STATE" class="col-sm-4 control-label">#LICENSE_STATE#</label>
				<div class="col-sm-8">
					%LICENSE_STATE%
				</div>
			</div>
			<div class="form-group">
				<label for="LICENSE_EXPIRY_DATE" class="col-sm-4 control-label">#LICENSE_EXPIRY_DATE#</label>
				<div class="col-sm-8">
					%LICENSE_EXPIRY_DATE%
				</div>
			</div>
			<div class="form-group">
				<label for="LICENSE_NOTES" class="col-sm-4 control-label">#LICENSE_NOTES#</label>
				<div class="col-sm-8">
					%LICENSE_NOTES%
				</div>
			</div>
		</div>
	</div>
'
);

$sts_form_edit_license = array(
	'title' => 'License Info',
	'action' => 'exp_editlicense.php',
	'cancel' => 'exp_editdriver.php?CODE=%CONTACT_CODE%',
	'name' => 'edit_license',
	'okbutton' => 'Save Changes',
	'cancelbutton' => 'Cancel',
	'layout' => '
	%LICENSE_CODE%
	%CONTACT_CODE%
	%CONTACT_SOURCE%
	<div class="form-group">
		<div class="col-sm-5">
			<div class="form-group">
				<label for="LICENSE_TYPE" class="col-sm-4 control-label">#LICENSE_TYPE#</label>
				<div class="col-sm-8">
					%LICENSE_TYPE%
				</div>
			</div>
			<div class="form-group">
				<label for="LICENSE_ENDORSEMENTS" class="col-sm-4 control-label">#LICENSE_ENDORSEMENTS#</label>
				<div class="col-sm-8">
					%LICENSE_ENDORSEMENTS%
				</div>
			</div>
		</div>
		<div class="col-sm-5">
			<div class="form-group">
				<label for="LICENSE_NUMBER" class="col-sm-4 control-label">#LICENSE_NUMBER#</label>
				<div class="col-sm-8">
					%LICENSE_NUMBER%
				</div>
			</div>
			<div class="form-group">
				<label for="LICENSE_STATE" class="col-sm-4 control-label">#LICENSE_STATE#</label>
				<div class="col-sm-8">
					%LICENSE_STATE%
				</div>
			</div>
			<div class="form-group">
				<label for="LICENSE_EXPIRY_DATE" class="col-sm-4 control-label">#LICENSE_EXPIRY_DATE#</label>
				<div class="col-sm-8">
					%LICENSE_EXPIRY_DATE%
				</div>
			</div>
			<div class="form-group">
				<label for="LICENSE_NOTES" class="col-sm-4 control-label">#LICENSE_NOTES#</label>
				<div class="col-sm-8">
					%LICENSE_NOTES%
				</div>
			</div>
		</div>
	</div>
'
);

//! Field Specifications - For use with sts_form
$sts_form_add_license_fields = array(
	'CONTACT_CODE' => array( 'format' => 'hidden' ),
	'CONTACT_SOURCE' => array( 'format' => 'hidden' ),
	'LICENSE_TYPE' => array( 'label' => 'Type', 'format' => 'enum' ),
	'LICENSE_ENDORSEMENTS' => array( 'label' => 'Endorsements', 'format' => 'endorsements' ),
	'LICENSE_NUMBER' => array( 'label' => 'Number', 'format' => 'text' ),
	'LICENSE_STATE' => array( 'label' => 'State', 'format' => 'state' ),
	'LICENSE_EXPIRY_DATE' => array( 'label' => 'Expiry', 'format' => 'date' ),
	'LICENSE_NOTES' => array( 'label' => 'Notes', 'format' => 'text' )
);

$sts_form_edit_license_fields = array(
	'LICENSE_CODE' => array( 'format' => 'hidden' ),	// primary key must be first
	'CONTACT_CODE' => array( 'format' => 'hidden' ),
	'CONTACT_SOURCE' => array( 'format' => 'hidden' ),
	'LICENSE_TYPE' => array( 'label' => 'Type', 'format' => 'enum' ),
	'LICENSE_ENDORSEMENTS' => array( 'label' => 'Endorsements', 'format' => 'endorsements' ),
	'LICENSE_NUMBER' => array( 'label' => 'Number', 'format' => 'text' ),
	'LICENSE_STATE' => array( 'label' => 'State', 'format' => 'state' ),
	'LICENSE_EXPIRY_DATE' => array( 'label' => 'Expiry', 'format' => 'date' ),
	'LICENSE_NOTES' => array( 'label' => 'Notes', 'format' => 'text' )
);

//! Layout Specifications - For use with sts_result
$sts_result_license_layout = array(
	'LICENSE_CODE' => array( 'format' => 'hidden' ),
	'ISDELETED' => array( 'format' => 'hidden' ),
	'LICENSE_TYPE' => array( 'label' => 'Type', 'format' => 'text' ),
	'LICENSE_NUMBER' => array( 'label' => 'Number', 'format' => 'text' ),
	'LICENSE_ENDORSEMENTS' => array( 'label' => 'Endorsements', 'format' => 'text' ),
	'LICENSE_STATE' => array( 'label' => 'State', 'format' => 'text' ),
	'LICENSE_EXPIRY_DATE' => array( 'label' => 'Expiry', 'format' => 'date' ),
	'LICENSE_NOTES' => array( 'label' => 'Notes', 'format' => 'text' )
);

//! Edit/Delete Button Specifications - For use with sts_result
$sts_result_license_edit = array(
	'title' => 'Licenses',
	'add' => 'exp_addlicense.php',
	//'popup' => true,
	'addbutton' => 'Add License Info',
	'rowbuttons' => array(
		array( 'url' => 'exp_editlicense.php?CODE=', 'key' => 'LICENSE_CODE', 'label' => 'LICENSE_NUMBER', 'tip' => 'Edit license ', 'icon' => 'glyphicon glyphicon-edit', 'showif' => 'notdeleted' ),
		array( 'url' => 'exp_deletelicense.php?TYPE=del&CODE=', 'key' => 'LICENSE_CODE', 'label' => 'LICENSE_NUMBER', 'tip' => 'Delete license ', 'icon' => 'glyphicon glyphicon-trash', 'confirm' => 'yes', 'showif' => 'notdeleted' ),
		array( 'url' => 'exp_deletelicense.php?TYPE=undel&CODE=', 'key' => 'LICENSE_CODE', 'label' => 'LICENSE_NUMBER', 'tip' => 'Undelete license ', 'icon' => 'glyphicon glyphicon-arrow-left', 'showif' => 'deleted' ),
		array( 'url' => 'exp_deletelicense.php?TYPE=permdel&CODE=', 'key' => 'LICENSE_CODE', 'label' => 'LICENSE_NUMBER', 'tip' => 'Permanently Delete license ', 'icon' => 'glyphicon glyphicon-remove', 'confirm' => 'yes', 'showif' => 'deleted' )
	)
);
	


?>
