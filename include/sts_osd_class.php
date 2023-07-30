<?php

// no direct access
defined('_STS_INCLUDE') or die('Restricted access');

require_once( "sts_table_class.php" );

class sts_osd extends sts_table {

	// Constructor does not need the table name
	public function __construct( $database, $debug = false ) {

		$this->debug = $debug;
		$this->primary_key = "OSD_CODE";
		if( $this->debug ) echo "<p>Create sts_osd</p>";
		parent::__construct( $database, OSD_TABLE, $debug);
	}
	

}

//! Form Specifications - For use with sts_form

$sts_form_addosd_form = array(	//! sts_form_addosd_form
	'title' => '<img src="images/osd_icon.png" alt="osd_icon" height="24"> Add OS&D Report',
	'action' => 'exp_addosd.php',
	//'actionextras' => 'disabled',
	'cancel' => 'exp_listosd.php',
	'name' => 'addosd',
	'okbutton' => 'Save Changes',
	//'saveadd' => 'Add Another',
	'cancelbutton' => 'Cancel',
		'layout' => '
	<div class="form-group">
	<div class="col-sm-10">To attach images, click <strong>Save changes</strong> first.</div>
	</div>
	<div class="form-group">
		<div class="col-sm-8">
				<div class="col-sm-6">
					<div class="form-group">
						<label for="OSD_TYPE" class="col-sm-4 control-label">#OSD_TYPE#</label>
						<div class="col-sm-8">
							%OSD_TYPE%
						</div>
					</div>
				</div>
				<div class="col-sm-6">
					<div class="form-group">
						<label for="REPORTED" class="col-sm-4 control-label">#REPORTED#</label>
						<div class="col-sm-8">
							%REPORTED%
						</div>
					</div>
				</div>
		
			<div class="form-group">
				<label for="OSD_DESCRIPTION" class="col-sm-2 control-label">#OSD_DESCRIPTION#</label>
				<div class="col-sm-10">
					%OSD_DESCRIPTION%
				</div>
			</div>
			<div class="form-group well well-sm">
				<div class="col-sm-6">
					<div class="form-group">
						<label for="SHIPMENT" class="col-sm-4 control-label">#SHIPMENT#</label>
						<div class="col-sm-8">
							%SHIPMENT%
						</div>
					</div>
					<div class="form-group">
						<label for="FROM" class="col-sm-4 control-label">From</label>
						<div class="col-sm-8" id="SHIP_FROM">
						</div>
					</div>
					<div class="form-group">
						<label for="TO" class="col-sm-4 control-label">To</label>
						<div class="col-sm-8" id="SHIP_TO">
						</div>
					</div>
					<div class="form-group">
						<label for="BILLTO" class="col-sm-4 control-label">Bill To</label>
						<div class="col-sm-8" id="BILLTO">
						</div>
					</div>
				</div>
				<div class="col-sm-6">
					<div class="form-group">
						<label for="LOAD_CODE" class="col-sm-4 control-label">#LOAD_CODE#</label>
						<div class="col-sm-8" id="LOAD_CODE">
							%LOAD_CODE%
						</div>
					</div>
					<div class="form-group">
						<label for="TRAILER" class="col-sm-4 control-label">Trailer</label>
						<div class="col-sm-8" id="TRAILER">
						</div>
					</div>
					<div class="form-group">
						<label for="DRIVER" class="col-sm-4 control-label">Driver</label>
						<div class="col-sm-8" id="DRIVER">
						</div>
					</div>
					<div class="form-group">
						<label for="CARRIER" class="col-sm-4 control-label">Carrier</label>
						<div class="col-sm-8" id="CARRIER">
						</div>
					</div>
				</div>
			</div><!-- form group -->
		</div><!-- col8 -->
		<div class="col-sm-4">
			<div class="form-group">
				<label for="REPORTED_BY" class="col-sm-4 control-label">#REPORTED_BY#</label>
				<div class="col-sm-8">
					%REPORTED_BY%
				</div>
			</div>
			<div class="form-group">
				<label for="DETAIL" class="col-sm-4 control-label">#DETAIL#</label>
				<div class="col-sm-8"  id="DETAIL_MENU">
					%DETAIL%
				</div>
			</div>
			<div class="form-group">
				<label for="DETAIL_UNITS" class="col-sm-4 control-label">Total Items</label>
				<div class="col-sm-8">
					<p class="text-right" style="padding-right: 8px;" id="DETAIL_UNITS"></p>
				</div>
			</div>
			<div class="form-group">
				<label for="ITEMS_REJECTED" class="col-sm-4 control-label">#ITEMS_REJECTED#</label>
				<div class="col-sm-8">
					%ITEMS_REJECTED%
				</div>
			</div>
			<div class="form-group">
				<label for="ITEMS_ACCEPTED" class="col-sm-4 control-label">#ITEMS_ACCEPTED#</label>
				<div class="col-sm-8">
					%ITEMS_ACCEPTED%
				</div>
			</div>
			<div class="form-group">
			<p style="padding-left: 20px; padding-right: 20px;">For <strong>over</strong> reports, CS/Delivered = Total Items, and CS/Rejected will be the extra items.</p>
			<p style="padding-left: 20px; padding-right: 20px;">For all others, CS/Delivered +  CS/Rejected = Total Items</p>
			</div>
		</div><!-- col4 -->
	</div><!-- form group -->
	<div class="form-group">
		<div class="col-sm-8">
			<div class="form-group">
				<label for="NOTES" class="col-sm-2 control-label">#NOTES#</label>
				<div class="col-sm-10">
					%NOTES%
				</div>
			</div>
		</div>
	</div>
	
	'
);

$sts_form_editosd_form = array(
	'title' => '<img src="images/osd_icon.png" alt="osd_icon" height="24"> Edit OS&D Report',
	'action' => 'exp_editosd.php',
	//'actionextras' => 'disabled',
	'cancel' => 'exp_listosd.php',
	'name' => 'editosd',
	'okbutton' => 'Save Changes to OS&D Report',
	'cancelbutton' => 'Back to OS&D Reports',
		'layout' => '
	%OSD_CODE%
	<div class="form-group">
		<div class="col-sm-8">
				<div class="col-sm-6">
					<div class="form-group">
						<label for="OSD_TYPE" class="col-sm-4 control-label">#OSD_TYPE#</label>
						<div class="col-sm-8">
							%OSD_TYPE%
						</div>
					</div>
				</div>
				<div class="col-sm-6">
					<div class="form-group">
						<label for="REPORTED" class="col-sm-4 control-label">#REPORTED#</label>
						<div class="col-sm-8">
							%REPORTED%
						</div>
					</div>
				</div>
		
			<div class="form-group">
				<label for="OSD_DESCRIPTION" class="col-sm-2 control-label">#OSD_DESCRIPTION#</label>
				<div class="col-sm-10">
					%OSD_DESCRIPTION%
				</div>
			</div>
			<div class="form-group well well-sm">
				<div class="col-sm-6">
					<div class="form-group">
						<label for="SHIPMENT" class="col-sm-4 control-label">#SHIPMENT#</label>
						<div class="col-sm-8">
							%SHIPMENT%
						</div>
					</div>
					<div class="form-group">
						<label for="FROM" class="col-sm-4 control-label">From</label>
						<div class="col-sm-8" id="SHIP_FROM">
						</div>
					</div>
					<div class="form-group">
						<label for="TO" class="col-sm-4 control-label">To</label>
						<div class="col-sm-8" id="SHIP_TO">
						</div>
					</div>
					<div class="form-group">
						<label for="BILLTO" class="col-sm-4 control-label">Bill To</label>
						<div class="col-sm-8" id="BILLTO">
						</div>
					</div>
				</div>
				<div class="col-sm-6">
					<div class="form-group">
						<label for="LOAD_CODE" class="col-sm-4 control-label">#LOAD_CODE#</label>
						<div class="col-sm-8" id="LOAD_CODE">
							%LOAD_CODE%
						</div>
					</div>
					<div class="form-group">
						<label for="TRAILER" class="col-sm-4 control-label">Trailer</label>
						<div class="col-sm-8" id="TRAILER">
						</div>
					</div>
					<div class="form-group">
						<label for="DRIVER" class="col-sm-4 control-label">Driver</label>
						<div class="col-sm-8" id="DRIVER">
						</div>
					</div>
					<div class="form-group">
						<label for="CARRIER" class="col-sm-4 control-label">Carrier</label>
						<div class="col-sm-8" id="CARRIER">
						</div>
					</div>
				</div>
			</div><!-- form group -->
		</div><!-- col8 -->
		<div class="col-sm-4">
			<div class="form-group">
				<label for="REPORTED_BY" class="col-sm-4 control-label">#REPORTED_BY#</label>
				<div class="col-sm-8">
					%REPORTED_BY%
				</div>
			</div>
			<div class="form-group">
				<label for="DETAIL" class="col-sm-4 control-label">#DETAIL#</label>
				<div class="col-sm-8"  id="DETAIL_MENU">
					%DETAIL%
				</div>
			</div>
			<div class="form-group">
				<label for="DETAIL_UNITS" class="col-sm-4 control-label">Total Items</label>
				<div class="col-sm-8">
					<p class="text-right" style="padding-right: 8px;" id="DETAIL_UNITS"></p>
				</div>
			</div>
			<div class="form-group">
				<label for="ITEMS_REJECTED" class="col-sm-4 control-label">#ITEMS_REJECTED#</label>
				<div class="col-sm-8">
					%ITEMS_REJECTED%
				</div>
			</div>
			<div class="form-group">
				<label for="ITEMS_ACCEPTED" class="col-sm-4 control-label">#ITEMS_ACCEPTED#</label>
				<div class="col-sm-8">
					%ITEMS_ACCEPTED%
				</div>
			</div>
			<div class="form-group">
			<p style="padding-left: 20px; padding-right: 20px;">For <strong>over</strong> reports, CS/Delivered = Total Items, and CS/Rejected will be the extra items.</p>
			<p style="padding-left: 20px; padding-right: 20px;">For all others, CS/Delivered +  CS/Rejected = Total Items</p>
			</div>
		</div><!-- col4 -->
	</div><!-- form group -->
	<div class="form-group">
		<div class="col-sm-8">
			<div class="form-group">
				<label for="NOTES" class="col-sm-2 control-label">#NOTES#</label>
				<div class="col-sm-10">
					%NOTES%
				</div>
			</div>
		</div>
	</div>

	
	'
);

//! Field Specifications - For use with sts_form

$sts_form_add_osd_fields = array(
	'OSD_CODE' => array( 'format' => 'hidden' ),
	'SHIPMENT' => array( 'label' => 'Shipment#', 'format' => 'table',
		'table' => SHIPMENT_TABLE, 'key' => 'SHIPMENT_CODE',
		'fields' => 'SHIPMENT_CODE,BILLTO_NAME',
		'condition' => 'CURRENT_STATUS IN (SELECT STATUS_CODES_CODE FROM EXP_STATUS_CODES
			WHERE SOURCE_TYPE = \'shipment\' AND BEHAVIOR IN (\'dispatched\',\'picked\',\'dropped\') )' ),
	'LOAD_CODE' => array( 'label' => 'Load#', 'format' => 'number', 'align' => 'right', 'extras' => 'readonly' ),
	'OSD_TYPE' => array( 'label' => 'Type', 'format' => 'enum' ),
	'OSD_DESCRIPTION' => array( 'label' => 'Description', 'format' => 'text' ),
	'REPORTED' => array( 'label' => 'Reported', 'format' => 'datetime' ),
	'REPORTED_BY' => array( 'label' => 'Reported By', 'format' => 'text' ),
	'ITEMS_REJECTED' => array( 'label' => 'CS/Rejected', 'format' => 'number', 'align' => 'right' ),
	'ITEMS_ACCEPTED' => array( 'label' => 'CS/Delivered', 'format' => 'number', 'align' => 'right', 'extras' => 'readonly' ),
	'DETAIL' => array( 'label' => 'Commodity', 'format' => 'table',
		'table' => DETAIL_TABLE, 'key' => 'DETAIL_CODE', 'fields' => 'COMMODITY' ),
	'NOTES' => array( 'label' => 'Notes', 'format' => 'textarea' ),
);

$sts_form_edit_osd_fields = array(
	'OSD_CODE' => array( 'label' => 'Report#', 'format' => 'hidden', 'align' => 'right' ),
	'SHIPMENT' => array( 'label' => 'Shipment#', 'format' => 'table',
		'table' => SHIPMENT_TABLE, 'key' => 'SHIPMENT_CODE',
		'fields' => 'SHIPMENT_CODE,BILLTO_NAME',
		'condition' => 'CURRENT_STATUS IN (SELECT STATUS_CODES_CODE FROM EXP_STATUS_CODES
			WHERE SOURCE_TYPE = \'shipment\' AND BEHAVIOR IN (\'dispatched\',\'picked\',\'dropped\') )' ),
	'LOAD_CODE' => array( 'label' => 'Load#', 'format' => 'number', 'align' => 'right', 'extras' => 'readonly' ),
	'OSD_TYPE' => array( 'label' => 'Type', 'format' => 'enum' ),
	'OSD_DESCRIPTION' => array( 'label' => 'Description', 'format' => 'text' ),
	'REPORTED' => array( 'label' => 'Reported', 'format' => 'datetime' ),
	'REPORTED_BY' => array( 'label' => 'By', 'format' => 'text' ),
	'ITEMS_REJECTED' => array( 'label' => 'CS/Rejected', 'format' => 'number', 'align' => 'right' ),
	'ITEMS_ACCEPTED' => array( 'label' => 'CS/Delivered', 'format' => 'number', 'align' => 'right', 'extras' => 'readonly' ),
	'DETAIL' => array( 'label' => 'Commodity', 'format' => 'number' ),
	'NOTES' => array( 'label' => 'Notes', 'format' => 'textarea' ),
);

//! Layout Specifications - For use with sts_result

$sts_result_osd_layout = array(
	'OSD_CODE' => array( 'label' => 'Report#', 'format' => 'num0' ),
	'SHIPMENT' => array( 'label' => 'Shipment#', 'format' => 'num0',
		'link' => 'exp_addshipment.php?CODE=' ),
	'LOAD_CODE' => array( 'label' => 'Load#', 'format' => 'num0',
	'link' => 'exp_viewload.php?CODE=' ),
	'OSD_TYPE' => array( 'label' => 'Type', 'format' => 'text' ),
	'OSD_DESCRIPTION' => array( 'label' => 'Description', 'format' => 'text' ),
	'IMAGES' => array( 'label' => '#Images', 'format' => 'count',
		'table' => IMAGE_TABLE, 'key' => 'PARENT_CODE','pk' => 'OSD_CODE',
		'align' => 'right' ),
	'REPORTED' => array( 'label' => 'Reported', 'format' => 'datetime' ),
	'REPORTED_BY' => array( 'label' => 'By', 'format' => 'text' ),
	'ITEMS_REJECTED' => array( 'label' => 'CS/Rejected', 'format' => 'num0' ),
	'ITEMS_ACCEPTED' => array( 'label' => 'CS/Delivered', 'format' => 'num0' ),
	'DETAIL' => array( 'label' => 'Commodity', 'format' => 'num0' ),
	//'NOTES' => array( 'label' => 'Notes', 'format' => 'textarea' ),
	//'PHOTO' => array( 'label' => 'Photo', 'format' => 'image' ),

	//'CREATED_DATE' => array( 'label' => 'Created', 'format' => 'datetime' ),
	//'CREATED_BY' => array( 'label' => 'Created By', 'format' => 'table',
	//	'table' => USER_TABLE, 'key' => 'USER_CODE', 'fields' => 'USERNAME' ),
	//'CHANGED_DATE' => array( 'label' => 'Changed', 'format' => 'datetime' ),
	//'CHANGED_BY' => array( 'label' => 'Changed By', 'format' => 'table',
	//	'table' => USER_TABLE, 'key' => 'USER_CODE', 'fields' => 'USERNAME' )

);

//! Edit/Delete Button Specifications - For use with sts_result

$sts_result_osd_edit = array(
	'title' => '<img src="images/osd_icon.png" alt="osd_icon" height="24"> OS&D Reports',
	'sort' => 'CREATED_DATE asc',
	'cancel' => 'index.php',
	'add' => 'exp_addosd.php',
	//'actionextras' => 'disabled',
	'addbutton' => 'Add OS&D Report',
	'cancelbutton' => 'Back',
	'rowbuttons' => array(
		array( 'url' => 'exp_editosd.php?CODE=', 'key' => 'OSD_CODE', 'label' => 'OSD_CODE', 'tip' => 'Edit OS&D ', 'icon' => 'glyphicon glyphicon-edit' )
	)
);


?>
