<?php

// no direct access
defined('_STS_INCLUDE') or die('Restricted access');

require_once( "sts_table_class.php" );

class sts_status_codes extends sts_table {

	// Constructor does not need the table name
	public function __construct( $database, $debug = false ) {

		$this->debug = $debug;
		$this->primary_key = "STATUS_CODES";
		if( $this->debug ) echo "<p>Create sts_status_codes</p>";
		parent::__construct( $database, STATUS_CODES_TABLE, $debug);
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

	//! SCR# 499 - cache following states
	public function cache_following_states( $source ) {
		$following = array();

		// Use cache rather than DB
		$check = $this->cache->get_state_table($source);
		if( $this->debug ) {
			echo "<pre>".__METHOD__.": check\n";
			var_dump($check);
			echo "</pre>";
		}
		if( is_array($check) && count($check) > 0 ) {
			foreach( $check as $row ) {
				if( $this->debug ) echo "<p>".__METHOD__.": ".$row['STATUS_CODES_CODE']." ".$row["PREVIOUS"]."</p>";
				if( ! empty( $row["PREVIOUS"] ) ) {
					foreach(  explode( ',', $row["PREVIOUS"]) as $prev) {
						if( $this->debug ) echo "<p>".__METHOD__.": prev = ".$prev."</p>";
				
						if( isset($following[$prev])) {
							$following[$prev][] = array( 'CODE' => $row['STATUS_CODES_CODE'],
								'BEHAVIOR' => $row['BEHAVIOR'],
								'STATUS' => $row['STATUS_STATE']);
						} else {
							$following[$prev] = array(
								array( 'CODE' => $row['STATUS_CODES_CODE'],
									'BEHAVIOR' => $row['BEHAVIOR'],
									'STATUS' => $row['STATUS_STATE'])
							);
						}
					}
				}
			}
			
			// Add cancel to every state
			foreach( $check as $row ) {
				if( $row['BEHAVIOR'] == 'cancel' ) {
					$others = array_keys($following);
					if( $this->debug ) echo "<p>".__METHOD__.": cancel=".$row['STATUS_CODES_CODE']." others=".implode(',', $others)."</p>";

					foreach( $others as $key ) {
						$following[$key][] = array( 'CODE' => $row['STATUS_CODES_CODE'],
							'BEHAVIOR' => $row['BEHAVIOR'],
							'STATUS' => $row['STATUS_STATE']);
					}
				}
			}

		}
	
		if( $this->debug ) {
			echo "<pre>".__METHOD__.": following\n";
			var_dump($following);
			echo "</pre>";
		}
		
		return $following;
	}
	
	public function lookup_previous( $source ) {
		return $this->fetch_rows("SOURCE_TYPE = '".$source."'", "STATUS_CODES_CODE AS CODE, STATUS_STATE", "STATUS_CODES_CODE ASC");
	}

	public function get_sources() {
		$source = false;
		$result = $this->fetch_rows("", "DISTINCT SOURCE_TYPE", "SOURCE_TYPE ASC");
		if( isset($result) && is_array($result) && count($result) > 0 ) {
			$category = array();
			foreach( $result as $row ) {
				$source[] = $row["SOURCE_TYPE"];
			}
		}
		return $source;
	}

}

//! Form Specifications - For use with sts_form

$sts_form_addstatus_codes_form = array(	//! sts_form_addstatus_codes_form
	'title' => '<img src="images/status_codes_icon.png" alt="status_codes_icon" height="24"> Add Status Code',
	'action' => 'exp_addstatus_codes.php',
	//'actionextras' => 'disabled',
	'cancel' => 'exp_liststatus_codes.php',
	'name' => 'addstatus_codes',
	'okbutton' => 'Save Changes',
	'saveadd' => 'Add Another',
	'cancelbutton' => 'Cancel',
		'layout' => '
	<div class="form-group">
		<div class="col-sm-4">
			<div class="form-group">
				<label for="SOURCE_TYPE" class="col-sm-4 control-label">#SOURCE_TYPE#</label>
				<div class="col-sm-8">
					%SOURCE_TYPE%
				</div>
			</div>
			<div class="form-group">
				<label for="STATUS_STATE" class="col-sm-4 control-label">#STATUS_STATE#</label>
				<div class="col-sm-8">
					%STATUS_STATE%
				</div>
			</div>
			<div class="form-group">
				<label for="STATUS_DESCRIPTION" class="col-sm-4 control-label">#STATUS_DESCRIPTION#</label>
				<div class="col-sm-8">
					%STATUS_DESCRIPTION%
				</div>
			</div>
		</div>
		<div class="col-sm-4">
			<div class="form-group">
				<label for="BEHAVIOR" class="col-sm-4 control-label">#BEHAVIOR#</label>
				<div class="col-sm-8">
					%BEHAVIOR%
				</div>
			</div>
			<div class="form-group">
				<label for="PREVIOUS" class="col-sm-4 control-label">#PREVIOUS#</label>
				<div class="col-sm-8">
					%PREVIOUS%
				</div>
			</div>
		</div>
		<div class="col-sm-4">
		</div>
	</div>
	
	'
);

$sts_form_editstatus_codes_form = array(
	'title' => '<img src="images/status_codes_icon.png" alt="status_codes_icon" height="24"> Edit Status Code',
	'action' => 'exp_editstatus_codes.php',
	//'actionextras' => 'disabled',
	'cancel' => 'exp_liststatus_codes.php',
	'name' => 'editstatus_codes',
	'okbutton' => 'Save Changes to Status Code',
	'cancelbutton' => 'Back to Status Codes',
		'layout' => '
		%STATUS_CODES_CODE%
	<div class="form-group">
		<div class="col-sm-4">
			<div class="form-group">
				<label for="SOURCE_TYPE" class="col-sm-4 control-label">#SOURCE_TYPE#</label>
				<div class="col-sm-8">
					%SOURCE_TYPE%
				</div>
			</div>
			<div class="form-group">
				<label for="STATUS_STATE" class="col-sm-4 control-label">#STATUS_STATE#</label>
				<div class="col-sm-8">
					%STATUS_STATE%
				</div>
			</div>
			<div class="form-group">
				<label for="STATUS_DESCRIPTION" class="col-sm-4 control-label">#STATUS_DESCRIPTION#</label>
				<div class="col-sm-8">
					%STATUS_DESCRIPTION%
				</div>
			</div>
		</div>
		<div class="col-sm-4">
			<div class="form-group">
				<label for="BEHAVIOR" class="col-sm-4 control-label">#BEHAVIOR#</label>
				<div class="col-sm-8">
					%BEHAVIOR%
				</div>
			</div>
			<div class="form-group">
				<label for="PREVIOUS" class="col-sm-4 control-label">#PREVIOUS#</label>
				<div class="col-sm-8">
					%PREVIOUS%
				</div>
			</div>
		</div>
		<div class="col-sm-4">
		</div>
	</div>
	
	'
);

//! Field Specifications - For use with sts_form

$sts_form_add_status_codes_fields = array(
	'SOURCE_TYPE' => array( 'label' => 'Source', 'format' => 'enum' ),
	'STATUS_STATE' => array( 'label' => 'Status', 'format' => 'text', 'extras' => 'required autofocus' ),
	'STATUS_DESCRIPTION' => array( 'label' => 'Description', 'format' => 'text' ),
	'BEHAVIOR' => array( 'label' => 'Behavior', 'format' => 'enum' ),
	'PREVIOUS' => array( 'label' => 'Previous', 'format' => 'mtable',
		'table' => STATUS_CODES_TABLE, 'key' => 'STATUS_CODES_CODE', 'fields' => 'STATUS_CODES_CODE,SOURCE_TYPE,STATUS_STATE' ),
);

$sts_form_edit_status_codes_fields = array(
	'STATUS_CODES_CODE' => array( 'format' => 'hidden' ),
	'SOURCE_TYPE' => array( 'label' => 'Source', 'format' => 'enum' ),
	'STATUS_STATE' => array( 'label' => 'Status', 'format' => 'text', 'extras' => 'required autofocus' ),
	'STATUS_DESCRIPTION' => array( 'label' => 'Description', 'format' => 'text' ),
	'BEHAVIOR' => array( 'label' => 'Behavior', 'format' => 'enum' ),
	'PREVIOUS' => array( 'label' => 'Previous', 'format' => 'mtable',
		'table' => STATUS_CODES_TABLE, 'key' => 'STATUS_CODES_CODE', 'fields' => 'STATUS_CODES_CODE,SOURCE_TYPE,STATUS_STATE' ),
);

//! Layout Specifications - For use with sts_result

$sts_result_status_codess_layout = array(
	'SOURCE_TYPE' => array( 'label' => 'Source', 'format' => 'text' ),
	'STATUS_CODES_CODE' => array( 'label' => 'Code', 'format' => 'num0' ),
	'STATUS_STATE' => array( 'label' => 'Status', 'format' => 'text' ),
	'STATUS_DESCRIPTION' => array( 'label' => 'Description', 'format' => 'text' ),
	'BEHAVIOR' => array( 'label' => 'Behavior', 'format' => 'text' ),
	'PREVIOUS' => array( 'label' => 'Previous', 'format' => 'text' ),
	'CHANGED_BY' => array( 'label' => 'Changed&nbsp;By', 'format' => 'table',
		'table' => USER_TABLE, 'key' => 'USER_CODE', 'fields' => 'USERNAME' ),
	'CHANGED_DATE' => array( 'label' => 'Date', 'format' => 'timestamp' ),
);

//! Edit/Delete Button Specifications - For use with sts_result

$sts_result_status_codess_edit = array(
	'title' => '<img src="images/status_codes_icon.png" alt="status_codes_icon" height="24"> Status Codes',
	'sort' => 'SOURCE_TYPE asc, STATUS_STATE asc',
	'cancel' => 'index.php',
	'add' => 'exp_addstatus_codes.php',
	//'actionextras' => 'disabled',
	'addbutton' => 'Add Status Code',
	'cancelbutton' => 'Back',
	'rowbuttons' => array(
		array( 'url' => 'exp_editstatus_codes.php?CODE=', 'key' => 'STATUS_CODES_CODE', 'label' => 'STATUS_STATE', 'tip' => 'Edit status_codes ', 'icon' => 'glyphicon glyphicon-edit' ),
		array( 'url' => 'exp_deletestatus_codes.php?CODE=', 'key' => 'STATUS_CODES_CODE', 'label' => 'STATUS_STATE', 'tip' => 'Delete status_codes ', 'icon' => 'glyphicon glyphicon-trash', 'confirm' => 'yes' )
	)
);


?>
