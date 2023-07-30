<?php

// no direct access
defined('_STS_INCLUDE') or die('Restricted access');

require_once( "sts_table_class.php" );

class sts_image extends sts_table {

	// Constructor does not need the table name
	public function __construct( $database, $debug = false ) {
		global $sts_result_image_layout, $sts_result_image_edit;

		$this->debug = $debug;
		$this->primary_key = "IMAGE_CODE";
		$this->layout_fields = $sts_result_image_layout;
		$this->edit_fields = $sts_result_image_edit;
		if( $this->debug ) echo "<p>Create sts_image</p>";
		parent::__construct( $database, IMAGE_TABLE, $debug);
	}
	
}

//! Form Specifications - For use with sts_form

$sts_form_addimage_form = array(	//! sts_form_addimage_form
	'title' => '<img src="images/image_icon.png" alt="image_icon" height="24"> Add Image',
	'action' => 'exp_addimage.php',
	//'actionextras' => 'disabled',
	'popup' => true,	// issue with the toggle switches
	'cancel' => 'exp_listimage.php',
	'name' => 'addimage',
	'okbutton' => 'Save Changes',
	//'saveadd' => 'Add Another',
	'cancelbutton' => 'Cancel',
		'layout' => '
		%PARENT_CODE%
	<div class="form-group">
		<div class="col-sm-6">
			<div class="form-group">
				<label for="THE_IMAGE" class="col-sm-4 control-label">#THE_IMAGE#</label>
				<div class="col-sm-8">
					%THE_IMAGE%
				</div>
			</div>
		</div>
	</div>
	
	'
);

$sts_form_editimage_form = array(
	'title' => '<img src="images/image_icon.png" alt="image_icon" height="24"> Edit Unit',
	'action' => 'exp_editimage.php',
	//'actionextras' => 'disabled',
	'cancel' => 'exp_listimage.php',
	'name' => 'editimage',
	'okbutton' => 'Save Changes to Unit',
	'cancelbutton' => 'Back to Units',
		'layout' => '
		%UNIT_CODE%
	<div class="form-group">
		<div class="col-sm-4">
			<div class="form-group">
				<label for="NAME" class="col-sm-4 control-label">#NAME#</label>
				<div class="col-sm-8">
					%NAME%
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

$sts_form_add_image_fields = array(
	'PARENT_CODE' => array( 'format' => 'hidden' ),
	'THE_IMAGE' => array( 'label' => 'Photo', 'format' => 'image' ),
);

$sts_form_edit_image_fields = array(
	'IMAGE_CODE' => array( 'format' => 'hidden' ),
	'PARENT_CODE' => array( 'format' => 'hidden' ),
	'THE_IMAGE' => array( 'label' => 'Photo', 'format' => 'image' ),
);

//! Layout Specifications - For use with sts_result

$sts_result_image_layout = array(
	'IMAGE_CODE' => array( 'format' => 'hidden' ),
	'PARENT_CODE' => array( 'format' => 'hidden' ),
	'THE_IMAGE' => array( 'label' => 'Image (Click to view)', 'format' => 'image', 'extras' => 'required' ),
	'CREATED_DATE' => array( 'label' => 'Created', 'format' => 'datetime' ),
	'CREATED_BY' => array( 'label' => 'Created By', 'format' => 'table',
		'table' => USER_TABLE, 'key' => 'USER_CODE', 'fields' => 'USERNAME' ),

);

//! Edit/Delete Button Specifications - For use with sts_result

$sts_result_image_edit = array(
	'title' => '<img src="images/image_icon.png" alt="image_icon" height="24"> Images',
	'sort' => 'CREATED_DATE asc',
	'popup' => true,
	//'cancel' => 'index.php',
	'add' => 'exp_addimage.php',
	//'actionextras' => 'disabled',
	'addbutton' => 'Add Image',
	//'cancelbutton' => 'Back',
	'rowbuttons' => array(
		//array( 'url' => 'exp_editimage.php?CODE=', 'key' => 'UNIT_CODE', 'label' => 'NAME', 'tip' => 'Edit image ', 'icon' => 'glyphicon glyphicon-edit' ),
		array( 'url' => 'exp_deleteimage.php?CODE=', 'key' => 'IMAGE_CODE', 'label' => 'IMAGE_CODE', 'tip' => 'Delete image ', 'icon' => 'glyphicon glyphicon-trash', 'confirm' => 'yes' )
	)
);


?>
