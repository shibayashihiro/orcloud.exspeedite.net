<?php

// no direct access
defined('_STS_INCLUDE') or die('Restricted access');

require_once( "sts_table_class.php" );

class sts_manual_miles extends sts_table {

	// Constructor does not need the table name
	public function __construct( $database, $debug = false ) {

		$this->debug = $debug;
		$this->primary_key = "MANUAL_MILES_CODE";
		if( $this->debug ) echo "<p>Create sts_manual_miles</p>";
		parent::__construct( $database, MANUAL_MILES_TABLE, $debug);
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

	public function get_distance( $bill_to, $zip1, $zip2 ) {
		$distance = -1;
		$result = $this->fetch_rows("BILL_TO = (SELECT CLIENT_CODE
				FROM EXP_CLIENT WHERE CLIENT_NAME = '".$bill_to."'
				AND ISDELETED = 1
				LIMIT 1) AND
			(FROM_ZONE = '".$zip1."' OR MATCH_ZONE('".$zip1."', FROM_ZONE)) AND
			(TO_ZONE = '".$zip2."' OR MATCH_ZONE('".$zip2."', TO_ZONE))",
			"DISTANCE");
		if( $result && count($result) > 0 )
			$distance = $result[0]['DISTANCE'];
		return $distance;
	}
}

//! Form Specifications - For use with sts_form

$sts_form_addmanual_miles_form = array(	//! sts_form_addmanual_miles_form
	'title' => '<img src="images/manual_miles_icon.png" alt="manual_miles_icon" height="24"> Add Manual Miles',
	'action' => 'exp_addmanual_miles.php',
	//'actionextras' => 'disabled',
	'cancel' => 'exp_listmanual_miles.php',
	'name' => 'addmanual_miles',
	'okbutton' => 'Save Changes',
	'saveadd' => 'Add Another',
	'cancelbutton' => 'Cancel',
		'layout' => '
	<div class="form-group">
		<div class="col-sm-4">
			<div class="form-group">
				<label for="BILL_TO" class="col-sm-4 control-label">#BILL_TO#</label>
				<div class="col-sm-8">
					%BILL_TO%
				</div>
			</div>
			<div class="form-group">
				<label for="FROM_ZONE" class="col-sm-4 control-label">#FROM_ZONE#</label>
				<div class="col-sm-8">
					%FROM_ZONE%
				</div>
			</div>
			<div class="form-group">
				<label for="TO_ZONE" class="col-sm-4 control-label">#TO_ZONE#</label>
				<div class="col-sm-8">
					%TO_ZONE%
				</div>
			</div>
			<div class="form-group">
				<label for="DISTANCE" class="col-sm-4 control-label">#DISTANCE#</label>
				<div class="col-sm-8">
					%DISTANCE%
				</div>
			</div>
		</div>
		<div class="col-sm-4">
		</div>
		<div class="col-sm-4">
		</div>
	</div>
	
	'
);

$sts_form_editmanual_miles_form = array(
	'title' => '<img src="images/manual_miles_icon.png" alt="manual_miles_icon" height="24"> Edit Manual Miles',
	'action' => 'exp_editmanual_miles.php',
	//'actionextras' => 'disabled',
	'cancel' => 'exp_listmanual_miles.php',
	'name' => 'editmanual_miles',
	'okbutton' => 'Save Changes',
	'cancelbutton' => 'Back',
		'layout' => '
		%MANUAL_MILES_CODE%
	<div class="form-group">
		<div class="col-sm-4">
			<div class="form-group">
				<label for="BILL_TO" class="col-sm-4 control-label">#BILL_TO#</label>
				<div class="col-sm-8">
					%BILL_TO%
				</div>
			</div>
			<div class="form-group">
				<label for="FROM_ZONE" class="col-sm-4 control-label">#FROM_ZONE#</label>
				<div class="col-sm-8">
					%FROM_ZONE%
				</div>
			</div>
			<div class="form-group">
				<label for="TO_ZONE" class="col-sm-4 control-label">#TO_ZONE#</label>
				<div class="col-sm-8">
					%TO_ZONE%
				</div>
			</div>
			<div class="form-group">
				<label for="DISTANCE" class="col-sm-4 control-label">#DISTANCE#</label>
				<div class="col-sm-8">
					%DISTANCE%
				</div>
			</div>
		</div>
		<div class="col-sm-4">
		</div>
		<div class="col-sm-4">
		</div>
	</div>
	
	'
);

//! Field Specifications - For use with sts_form

$sts_form_add_manual_miles_fields = array(
	'BILL_TO' => array( 'label' => 'Bill To', 'format' => 'table',
		'table' => CLIENT_TABLE, 'key' => 'CLIENT_CODE', 'fields' => 'CLIENT_NAME',
		'condition' => 'BILL_TO = true', 'nolink' => true ),
	//'BILL_TO' => array( 'label' => 'Bill To', 'format' => 'client' ),
	'FROM_ZONE' => array( 'label' => 'From Zone', 'format' => 'zone', 'extras' => 'required' ),
	'TO_ZONE' => array( 'label' => 'To Zone', 'format' => 'zone', 'extras' => 'required' ),
	'DISTANCE' => array( 'label' => 'Distance', 'format' => 'number', 'align' => 'right', 'extras' => 'required' ),
);

$sts_form_edit_manual_miles_fields = array(
	'MANUAL_MILES_CODE' => array( 'format' => 'hidden' ),
	'BILL_TO' => array( 'label' => 'Bill To', 'format' => 'table',
		'table' => CLIENT_TABLE, 'key' => 'CLIENT_CODE', 'fields' => 'CLIENT_NAME',
		'condition' => 'BILL_TO = true', 'nolink' => true ),
	//'BILL_TO' => array( 'label' => 'Bill To', 'format' => 'client' ),
	'FROM_ZONE' => array( 'label' => 'From Zone', 'format' => 'zone', 'extras' => 'required' ),
	'TO_ZONE' => array( 'label' => 'To Zone', 'format' => 'zone', 'extras' => 'required' ),
	'DISTANCE' => array( 'label' => 'Distance', 'format' => 'number', 'align' => 'right', 'extras' => 'required' ),
);

//! Layout Specifications - For use with sts_result

$sts_result_manual_miless_layout = array(
	'MANUAL_MILES_CODE' => array( 'format' => 'hidden' ),
	'BILL_TO' => array( 'label' => 'Bill To', 'format' => 'table',
		'table' => CLIENT_TABLE, 'key' => 'CLIENT_CODE', 'fields' => 'CLIENT_NAME',
		'condition' => 'BILL_TO = true' ),
	//'BILL_TO' => array( 'label' => 'Bill To', 'format' => 'text' ),
	'FROM_ZONE' => array( 'label' => 'From Zone', 'format' => 'text' ),
	'TO_ZONE' => array( 'label' => 'To Zone', 'format' => 'text' ),
	'DISTANCE' => array( 'label' => 'Distance', 'format' => 'num0', 'align' => 'right' ),
	'CHANGED_DATE' => array( 'label' => 'Changed', 'format' => 'timestamp-s', 'length' => 90 ),
	'CHANGED_BY' => array( 'label' => 'By', 'format' => 'table',
		'table' => USER_TABLE, 'key' => 'USER_CODE', 'fields' => 'USERNAME', 'link' => 'exp_edituser.php?CODE=' )

);

//! Edit/Delete Button Specifications - For use with sts_result

$sts_result_manual_miless_edit = array(
	'title' => '<img src="images/manual_miles_icon.png" alt="manual_miles_icon" height="24"> Manual Miles',
	'sort' => 'MANUAL_MILES_CODE asc',
	'cancel' => 'index.php',
	'add' => 'exp_addmanual_miles.php',
	//'actionextras' => 'disabled',
	'addbutton' => 'Add Manual Miles',
	'cancelbutton' => 'Back',
	'rowbuttons' => array(
		array( 'url' => 'exp_editmanual_miles.php?CODE=', 'key' => 'MANUAL_MILES_CODE', 'label' => 'MANUAL_MILES_CODE', 'tip' => 'Edit manual_miles ', 'icon' => 'glyphicon glyphicon-edit' ),
		array( 'url' => 'exp_deletemanual_miles.php?CODE=', 'key' => 'MANUAL_MILES_CODE', 'label' => 'MANUAL_MILES_CODE', 'tip' => 'Delete manual_miles ', 'icon' => 'glyphicon glyphicon-trash', 'confirm' => 'yes' )
	)
);


?>
