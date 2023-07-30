<?php

// $Id: sts_rm_class_class.php 3884 2020-01-30 00:21:42Z duncan $
// RM class - deal with classes for tractors/trailers

// no direct access
defined('_STS_INCLUDE') or die('Restricted access');

require_once( "sts_table_class.php" );
//require_once( "sts_setting_class.php" );

class sts_rm_class extends sts_table {

	private $default_tractor_class = array(
		'Sleeper',
		'Sleeper with APU',
		'Daycab',
		'Daycab with APU',
		'Straight Truck',
		'Straight Truck with Reefer'
	);

	private $default_trailer_class = array(
		'Chassis',
		'Dry Van',
		'Tri Axle Dry Van',
		'Reefer',
		'Tri Axle Reefer',
		'Multi Temp Reefer',
		'Tri Axle Multi Temp Reefer'
	);

	//private $setting_table;

	// Constructor does not need the table name
	public function __construct( $database, $debug = false ) {

		$this->debug = $debug;
		$this->primary_key = "CLASS_CODE";
		if( $this->debug ) echo "<p>Create sts_rm_class</p>";

		//$this->setting_table = sts_setting::getInstance($database, $debug);
		//$this->export_sage50 = ($this->setting_table->get( 'api', 'EXPORT_SAGE50_CSV' ) == 'true');

		parent::__construct( $database, RM_CLASS_TABLE, $debug);
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
		if( $this->debug ) echo "<p>".__METHOD__.": session v = ".(isset($_SESSION["DEFAULT_RMCITEMS_LOADED"]) ? 'set' : 'unset')."</p>";
	    if( ! isset($_SESSION["DEFAULT_RMCITEMS_LOADED"])) {
		    $_SESSION["DEFAULT_RMCITEMS_LOADED"] = true;
		    $check = $this->database->get_multiple_rows("
			    SELECT UNIT_TYPE, COUNT(*) AS NUM
				FROM EXP_RM_CLASS
				GROUP BY UNIT_TYPE
				ORDER BY UNIT_TYPE ASC
		    ");

		    $num = array();
		    if( is_array($check) && count($check) > 0 ) {
			    foreach( $check as $row ) {
				    $num[$row["UNIT_TYPE"]] = $row["NUM"];
			    }
			}
			
			if( ! isset($num['tractor']) || $num['tractor'] == 0 ) {
			    foreach( $this->default_tractor_class as $item ) {
				    $this->add( array('UNIT_TYPE' => 'tractor',
				    	'CLASS_NAME' => $item ));
			    }
			}

			if( ! isset($num['trailer']) || $num['trailer'] == 0 ) {
			    foreach( $this->default_trailer_class as $item ) {
				    $this->add( array('UNIT_TYPE' => 'trailer',
				    	'CLASS_NAME' => $item ));
			    }
			}
	    }
    }

	public function get_types() {
		$types = false;
		$result = $this->fetch_rows("", "DISTINCT UNIT_TYPE", "UNIT_TYPE ASC");
		if( isset($result) && is_array($result) && count($result) > 0 ) {
			$category = array();
			foreach( $result as $row ) {
				$types[] = $row["UNIT_TYPE"];
			}
		}
		return $types;
	}
	
	public function get_class_code( $item_type, $name ) {
		$code = false;
		$result = $this->fetch_rows("UNIT_TYPE = '".$item_type."'
			AND CLASS_NAME = '".$name."'", "CLASS_CODE");
		if( isset($result) && is_array($result) && count($result) == 1 ) {
			$code = $result[0]["CLASS_CODE"];
		}
		return $code;
	}

	public function render_terms_menu( $selected = 0, $type = 'Client Terms' ) {
		if( $this->debug ) echo "<p>".__METHOD__.": selected = ".($selected === false ? 'false' : $selected).", type = $type</p>";
		$output = '';
		if( $selected == 0 ) {
			$default = $this->fetch_rows("ITEM_TYPE = '".$type."'
				AND ITEM = '".($type == 'Client Terms' ? $this->invoice_terms : $this->bill_terms)."'");
			if( is_array($default) && count($default) == 1 )
				$selected = $default[0]["ITEM_CODE"];
			else
				$selected = 0;
		}
		$choices = $this->fetch_rows("ITEM_TYPE = '".$type."'");
		if( is_array($choices) && count($choices) > 0 ) {
			$output =  '<select class="form-control" style="display: inline-block; margin-bottom: 0; vertical-align: middle; width: auto;" name="TERMS" id="TERMS" >
			';
			foreach($choices as $row) {
				$output .= '<option value="'.$row["ITEM_CODE"].'"'.($selected == $row["ITEM_CODE"] || ($selected == 0 && $row["ITEM"] == "NET 30") ? ' selected' : '').'>'.$row["ITEM"].'</option>
				';
			}
			$output .= '</select>
			';
		}
		return $output;
	}

	public function render_terms( $selected  = 0, $type = 'Client Terms', $brackets = true  ) {
		if( $this->debug ) echo "<p>".__METHOD__.": selected = $selected, type = $type</p>";
		$output = '';
		if( $selected > 0 ) {
			$terms = $this->fetch_rows("ITEM_TYPE = '".$type."'
				AND ITEM_CODE = ".$selected);
			if( is_array($terms) && count($terms) == 1 )
				$output .= ($brackets ? ' [' : '').$terms[0]["ITEM"].($brackets ? ']' : '');
			else
				$selected = 0;
		}
		
		if( $selected == 0 ) {
			$default = $this->fetch_rows("ITEM_TYPE = '".$type."'
				AND ITEM = '".($type == 'Client Terms' ? $this->invoice_terms : $this->bill_terms)."'");
			if( is_array($default) && count($default) == 1 )
				$output .= ($brackets ? ' [' : '').$default[0]["ITEM"].($brackets ? ']' : '');
			else
				$output .= ' [NET 30]';
		}
		return $output;
	}
	
	//! Check if we can delete a class.
	// Only possible if class has not been used yet.
	public function can_delete( $code ) {
		$result = false;
		$check = $this->database->get_one_row(
			"SELECT CASE WHEN UNIT_TYPE = 'tractor' THEN
				(SELECT COUNT(*) FROM EXP_TRACTOR
				WHERE RM_CLASS = CLASS_CODE)
			ELSE
				(SELECT COUNT(*) FROM EXP_TRAILER
				WHERE RM_CLASS = CLASS_CODE)
			END AS INUSE
			FROM EXP_RM_CLASS
			WHERE CLASS_CODE = $code" );
		if( is_array($check) && isset($check["INUSE"]))
			$result = intval($check["INUSE"]) == 0;
		return $result;	
	}
	
}

//! Form Specifications - For use with sts_form

$sts_form_add_rm_class_form = array(	//! $sts_form_add_rm_class_form
	'title' => '<span class="glyphicon glyphicon-th-list"></span> Add R&M Class',
	'action' => 'exp_addrm_class.php',
	//'actionextras' => 'disabled',
	'cancel' => 'exp_listrm_class.php',
	'name' => 'addrm_class',
	'okbutton' => 'Save Changes',
	'saveadd' => 'Add Another',
	'cancelbutton' => 'Cancel',
		'layout' => '
	<div class="form-group">
		<div class="col-sm-12">
			<div class="form-group">
				<label for="UNIT_TYPE" class="col-sm-2 control-label">#UNIT_TYPE#</label>
				<div class="col-sm-4">
					%UNIT_TYPE%
				</div>
				<div class="col-sm-4">
					<label>What type of class</label>
				</div>
			</div>
			<div class="form-group">
				<label for="CLASS_NAME" class="col-sm-2 control-label">#CLASS_NAME#</label>
				<div class="col-sm-4">
					%CLASS_NAME%
				</div>
				<div class="col-sm-4">
					<label>Name of class (required)</label>
				</div>
			</div>
			<div class="form-group">
				<label for="CLASS_DESCRIPTION" class="col-sm-2 control-label">#CLASS_DESCRIPTION#</label>
				<div class="col-sm-8">
					%CLASS_DESCRIPTION%
				</div>
			</div>
		</div>
		<div class="col-sm-6">
		</div>
	</div>
	
	'
);

$sts_form_edit_rm_class_form = array(	//! $sts_form_edit_rm_class_form
	'title' => '<span class="glyphicon glyphicon-th-list"></span> Edit R&M Class',
	'action' => 'exp_editrm_class.php',
	//'actionextras' => 'disabled',
	'cancel' => 'exp_listrm_class.php',
	'name' => 'editrm_class',
	'okbutton' => 'Save Changes',
	'cancelbutton' => 'Back',
		'layout' => '
		%CLASS_CODE%
	<div class="form-group">
		<div class="col-sm-12">
			<div class="form-group">
				<label for="UNIT_TYPE" class="col-sm-2 control-label">#UNIT_TYPE#</label>
				<div class="col-sm-4">
					%UNIT_TYPE%
				</div>
				<div class="col-sm-4">
					<label>What type of class</label>
				</div>
			</div>
			<div class="form-group">
				<label for="CLASS_NAME" class="col-sm-2 control-label">#CLASS_NAME#</label>
				<div class="col-sm-4">
					%CLASS_NAME%
				</div>
				<div class="col-sm-4">
					<label>Name of class (required)</label>
				</div>
			</div>
			<div class="form-group">
				<label for="CLASS_DESCRIPTION" class="col-sm-2 control-label">#CLASS_DESCRIPTION#</label>
				<div class="col-sm-8">
					%CLASS_DESCRIPTION%
				</div>
			</div>
		</div>
		<div class="col-sm-6">
		</div>
	</div>
	
	'
);

//! Field Specifications - For use with sts_form

$sts_form_add_rm_class_fields = array(	//! $sts_form_add_rm_class_fields
	'UNIT_TYPE' => array( 'label' => 'Applies To', 'format' => 'enum' ),
	'CLASS_NAME' => array( 'label' => 'Name', 'format' => 'text', 'extras' => 'required' ),
	'CLASS_DESCRIPTION' => array( 'label' => 'Description', 'format' => 'text' ),
);

$sts_form_edit_rm_class_fields = array(	//! $sts_form_edit_rm_class_fields
	'CLASS_CODE' => array( 'format' => 'hidden' ),
	'UNIT_TYPE' => array( 'label' => 'Applies To', 'format' => 'enum' ),
	'CLASS_NAME' => array( 'label' => 'Name', 'format' => 'text', 'extras' => 'required' ),
	'CLASS_DESCRIPTION' => array( 'label' => 'Description', 'format' => 'text' ),
);

//! Layout Specifications - For use with sts_result

$sts_result_rm_class_layout = array(	//! $sts_result_rm_class_layout
	'CLASS_CODE' => array( 'format' => 'hidden' ),
	'UNIT_TYPE' => array( 'label' => 'Type', 'format' => 'text' ),
	'CLASS_NAME' => array( 'label' => 'Name', 'format' => 'text' ),
	'CLASS_DESCRIPTION' => array( 'label' => 'Description', 'format' => 'text' ),
	'FORMS' => array( 'label' => '# Forms', 'format' => 'number', 'align' => 'right',
		'snippet' => "COALESCE((SELECT COUNT(*) FROM EXP_RM_FORM
				WHERE EXP_RM_FORM.FORM_NAME = 'Default'
                AND EXP_RM_FORM.UNIT_TYPE = EXP_RM_CLASS.UNIT_TYPE), 0) +
                COALESCE((SELECT COUNT(*) AS FORMS
				FROM EXP_RM_CLASS_FORM
				WHERE RM_CLASS = CLASS_CODE), 0)" ),
	'INSTANCES' => array( 'label' => '# Instances', 'format' => 'number', 'align' => 'right',
		'snippet' => "COALESCE((SELECT COUNT(*) AS TRACTORS
				FROM EXP_TRACTOR WHERE RM_CLASS = CLASS_CODE),
				(SELECT COUNT(*) FROM EXP_TRAILER
				WHERE RM_CLASS = CLASS_CODE), 0)" ),
	'CREATED_DATE' => array( 'label' => 'Created', 'format' => 'timestamp-s' ),
	'CHANGED_BY' => array( 'label' => 'By', 'format' => 'table',
		'table' => USER_TABLE, 'key' => 'USER_CODE', 'fields' => 'USERNAME', 'link' => 'exp_edituser.php?CODE=' )
);

//! Edit/Delete Button Specifications - For use with sts_result

$sts_result_rm_class_edit = array(	//! $sts_result_rm_class_edit
	'title' => '<span class="glyphicon glyphicon-th-list"></span> R&M Classes',
	'sort' => 'UNIT_TYPE asc, CLASS_NAME asc',
	'cancel' => 'index.php',
	'add' => 'exp_addrm_class.php',
	//'actionextras' => 'disabled',
	'addbutton' => 'Add R&M Class',
	'cancelbutton' => 'Back',
	'rowbuttons' => array(
		array( 'url' => 'exp_editrm_class.php?CODE=', 'key' => 'CLASS_CODE', 'label' => 'CLASS_NAME', 'tip' => 'Edit R&M class ', 'icon' => 'glyphicon glyphicon-edit' ),
		array( 'url' => 'exp_deleterm_class.php?CODE=', 'key' => 'CLASS_CODE', 'label' => 'CLASS_NAME', 'tip' => 'Delete R&M class ', 'icon' => 'glyphicon glyphicon-trash', 'confirm' => 'yes', 'showif' => 'can_delete' )
	)
);


?>
