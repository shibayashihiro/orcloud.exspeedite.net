<?php

// $Id: sts_rm_form_class.php 3884 2020-01-30 00:21:42Z duncan $
// RM form - deal with multiple forms for tractors/trailers

// no direct access
defined('_STS_INCLUDE') or die('Restricted access');

require_once( "sts_table_class.php" );
require_once( "sts_rm_class_class.php" );
require_once( "sts_insp_list_item_class.php" );
//require_once( "sts_setting_class.php" );

class sts_rm_form extends sts_table {

	//private $setting_table;
	private $rm_class_table;
	private $rm_class_form_table;
	private $insp_list_item_table;

	// Constructor does not need the table name
	public function __construct( $database, $debug = false ) {

		$this->debug = $debug;
		$this->primary_key = "FORM_CODE";
		if( $this->debug ) echo "<p>Create sts_rm_form</p>";

		//$this->setting_table = sts_setting::getInstance($database, $debug);
		//$this->export_sage50 = ($this->setting_table->get( 'api', 'EXPORT_SAGE50_CSV' ) == 'true');

		$this->rm_class_table = sts_rm_class::getInstance($database, $debug);
		$this->rm_class_form_table = sts_rm_class_form::getInstance($database, $debug);
		$this->insp_list_item_table = sts_insp_list_item::getInstance($database, $debug);
		
		parent::__construct( $database, RM_FORM_TABLE, $debug);
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
		if( $this->debug ) echo "<p>".__METHOD__.": session v = ".(isset($_SESSION["DEFAULT_RMFITEMS_LOADED"]) ? 'set' : 'unset')."</p>";
	    if( ! isset($_SESSION["DEFAULT_RMFITEMS_LOADED"])) {
		    $_SESSION["DEFAULT_RMFITEMS_LOADED"] = true;
		    $check = $this->database->get_multiple_rows("
			    SELECT FORM_NAME, UNIT_TYPE, COUNT(*) AS NUM
				FROM EXP_RM_FORM
				WHERE FORM_NAME = 'Default'
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
			    $default = $this->add( array('UNIT_TYPE' => 'tractor',
			    	'FORM_NAME' => 'Default' ));
			    
			    // Need to migrate list items to default
			    $this->database->get_one_row("UPDATE EXP_INSP_LIST_ITEMS
			    	SET RM_FORM = $default
			    	WHERE ITEM_TARGET = 'tractor'
			    	AND RM_FORM IS NULL");

			    // Need to migrate inspection reports to default
			    $this->database->get_one_row("UPDATE EXP_INSP_REPORT
			    	SET RM_FORM = $default, REPORT_NAME = 'Default'
			    	WHERE UNIT_TYPE = 'tractor'
			    	AND RM_FORM IS NULL");
			}

			if( ! isset($num['trailer']) || $num['trailer'] == 0 ) {
			    $default = $this->add( array('UNIT_TYPE' => 'trailer',
			    	'FORM_NAME' => 'Default' ));
			    
			    // Need to migrate list items to default
			    $this->database->get_one_row("UPDATE EXP_INSP_LIST_ITEMS
			    	SET RM_FORM = $default
			    	WHERE ITEM_TARGET = 'trailer'
			    	AND RM_FORM IS NULL");

			    // Need to migrate inspection reports to default
			    $this->database->get_one_row("UPDATE EXP_INSP_REPORT
			    	SET RM_FORM = $default, REPORT_NAME = 'Default'
			    	WHERE UNIT_TYPE = 'trailer'
			    	AND RM_FORM IS NULL");
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
	
	public function get_units( $item_type = 'tractor' ) {
		$units = false;
		$result = $this->database->get_multiple_rows("SELECT DISTINCT UNIT,
			CASE WHEN UNIT_TYPE = 'tractor' THEN
				(SELECT UNIT_NUMBER FROM EXP_TRACTOR
					WHERE TRACTOR_CODE = EXP_INSP_REPORT.UNIT)
			ELSE
				(SELECT UNIT_NUMBER FROM EXP_TRAILER
					WHERE TRAILER_CODE = EXP_INSP_REPORT.UNIT)
			END AS UNIT_NUMBER
			FROM EXP_INSP_REPORT
			WHERE UNIT_TYPE = '".$item_type."'
			ORDER BY 1 ASC");
		if( isset($result) && is_array($result) && count($result) > 0 ) {
			$units = array();
			foreach( $result as $row ) {
				$units[$row["UNIT"]] = $row["UNIT_NUMBER"];
			}
		}
		return $units;
	}
	
	public function get_form_code( $item_type, $name ) {
		$code = false;
		$result = $this->fetch_rows("UNIT_TYPE = '".$item_type."'
			AND FORM_NAME = '".$name."'", "FORM_CODE");
		if( isset($result) && is_array($result) && count($result) == 1 ) {
			$code = $result[0]["FORM_CODE"];
		}
		return $code;
	}

	public function render_form_menu( $type, $unit ) {
		if( $this->debug ) echo "<p>".__METHOD__.": type = $type, unit = $unit </p>";
		$output = '';
		$choices = $this->database->get_multiple_rows("
			SELECT DISTINCT F.FORM_CODE, F.FORM_NAME
			FROM EXP_RM_FORM F
			LEFT JOIN EXP_RM_CLASS_FORM CF
			ON F.FORM_CODE = CF.RM_FORM
			WHERE F.UNIT_TYPE ='".$type."'
			AND (F.FORM_NAME = 'Default'
			OR(CF.RM_CLASS = (SELECT RM_CLASS
				FROM EXP_".strtoupper($type)." WHERE ".strtoupper($type)."_CODE = $unit))
			)");

		if( is_array($choices) && count($choices) == 1 ) {			//! Only one choice
			$output =  '<a class="btn btn-sm btn-success" id="EXP_INSP_REPORT_add" href="exp_addinsp_report.php?TYPE='.$type.'&UNIT='.$unit.'&FORM='.$choices[0]['FORM_CODE'].'"><span class="glyphicon glyphicon-plus"></span> Add Report</a>';
			
		} else if( is_array($choices) && count($choices) > 1 ) {	//! Multiple choices
			
			$output = '<select class="form-control input-sm" name="rm_forms" id="rm_forms" onchange="location = this.value;">
			<option value="#">Add Report</option>
			';
			foreach($choices as $row) {
				$output .= '<option value="exp_addinsp_report.php?TYPE='.$type.'&UNIT='.$unit.'&FORM='.$row["FORM_CODE"].'">'.$row["FORM_NAME"].'</option>
				';
			}
			$output .= '</select>
			';
		}
		return $output;
	}

	//! Check if we can delete a form.
	// Allow deletion of a form even if it was used.
	public function can_delete( $code ) {
		return true;	
	}

	//! Delete an R&M Form, also remove form-class links
	public function delete( $code, $type = "" ) {
		if( $this->can_delete( $code ) ) {
			parent::delete( $code, $type );
			$this->rm_class_form_table->delete_row("RM_FORM = ".$code);
			$this->insp_list_item_table->delete_row("RM_FORM = ".$code);
		}
	}
	
	//! Create checkboxes for classes
	public function class_checkboxes( $form, $form_code = false ) {
		if( $this->debug ) echo "<p>".__METHOD__.": form_code = $form_code</p>";
		
		$check_default = $this->fetch_rows( "FORM_CODE = ".$form_code." AND FORM_NAME = 'Default'",
			"FORM_NAME, UNIT_TYPE");
		if( is_array($check_default) && count($check_default) == 1 ) {
			$classes_str = '<div id="CLASSES" class="panel panel-default">
			  <div class="panel-heading">
			    <h3 class="panel-title">Form Applies To These <a href="exp_listrm_class.php">Classes</a></h3>
			  </div>
			  <div class="panel-body">
			  <p>This form is for backwards compatibility and applies to all classes of '.$check_default[0]["UNIT_TYPE"].'.</p>
			  <p>If you no longer use this form, rename it and uncheck all the classes, or delete all the items below and then you can delete the R&M form.</p>
			  <p>If you delete this form, instances of the reports previously made should still be viewable and editable.</p>
			  </div>
			</div>
			';		
		
			$form = str_replace('<!-- CLASSES -->', $classes_str, $form);
		} else {
			$classes = $this->rm_class_table->fetch_rows("UNIT_TYPE = (select UNIT_TYPE FROM EXP_RM_FORM WHERE FORM_CODE = $form_code)", "CLASS_CODE, CLASS_NAME", "CLASS_NAME ASC");
		
			$classes_str = '';
			if( is_array($classes) && count( $classes ) > 0 ) {
				foreach( $classes as $row ) {
	
					$check = $form_code ?
						$this->rm_class_form_table->fetch_rows("RM_FORM = ".$form_code.
							" AND RM_CLASS = ".$row["CLASS_CODE"]) : false;
					if( $this->debug ) {
						echo "<pre>";
						var_dump($check);
						echo "</pre>";
					}
					$exists = is_array($check) && count($check) > 0;
					if( $this->debug ) echo "<p>".__METHOD__.": exists = ".($exists ? 'true' : 'false')."</p>";
					
					$classes_str .= '<div class="checkbox">
					    <label>
					      <input type="checkbox" class="classes" name="CLASS_'.$row["CLASS_CODE"].'" id="CLASS_'.$row["CLASS_CODE"].'" value="'.$row["CLASS_CODE"].'"'.($exists ? ' checked' : '').'> '.$row["CLASS_NAME"].'
					    </label>
					    </div>
					    ';
				}
				if( ! empty($classes_str) ) {
					$classes_str = '<div id="CLASSES" class="panel panel-default">
					  <div class="panel-heading">
					    <h3 class="panel-title">Form Applies To These <a href="exp_listrm_class.php">Classes</a></h3>
					  </div>
					  <div class="panel-body">
					  <div class="btn-group" role="group" style="float: right;"><button type="button" id="checkall" class="btn btn-link" href="exp_listrm_class.php"><span class="glyphicon glyphicon-ok"></span></button><button type="button" id="checknone" class="btn btn-link" href="exp_listrm_class.php"><span class="glyphicon glyphicon-remove"></span></button></div>
					'.$classes_str . '</div>
					</div>
					';		
				
					$form = str_replace('<!-- CLASSES -->', $classes_str, $form);
				}
			}
		}
		return $form;
	}
	
	//! Process checkboxes for reports
	public function process_class_checkboxes( $form_code ) {
		if( $this->debug ) echo "<p>".__METHOD__.": form_code = $form_code</p>";

		$classes = $this->rm_class_table->fetch_rows("UNIT_TYPE = (select UNIT_TYPE FROM EXP_RM_FORM WHERE FORM_CODE = $form_code)", "CLASS_CODE, CLASS_NAME", "CLASS_NAME ASC");
		
		if( is_array($classes) && count( $classes ) > 0 ) {
			foreach( $classes as $row ) {
				$check = $form_code ?
					$this->rm_class_form_table->fetch_rows("RM_FORM = ".$form_code.
						" AND RM_CLASS = ".$row["CLASS_CODE"]) : false;
				
				$exists = is_array($check) && count($check) > 0;
				if( $this->debug ) echo "<p>".__METHOD__.": exists = ".($exists ? 'true' : 'false')."</p>";
				
				if( is_array($_POST) &&
					isset($_POST['CLASS_'.$row["CLASS_CODE"]])) {
					
					if( ! $exists )
						$this->rm_class_form_table->add( array( 'RM_FORM' => $form_code, 'RM_CLASS' => $row["CLASS_CODE"]) );
				} else {
					if( $exists )
						$this->rm_class_form_table->delete_row("RM_FORM = ".$form_code."
					AND RM_CLASS = ".$row["CLASS_CODE"]);
				}
			}
		}
	}
	
}

class sts_rm_class_form extends sts_table {

	// Constructor does not need the table name
	public function __construct( $database, $debug = false ) {

		$this->debug = $debug;
		$this->primary_key = "CLASS_FORM_CODE";
		if( $this->debug ) echo "<p>Create sts_class_form</p>";
		parent::__construct( $database, RM_CLASS_FORM_TABLE, $debug);
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

$sts_form_add_rm_form_form = array(	//! $sts_form_add_rm_form_form
	'title' => '<span class="glyphicon glyphicon-th-list"></span> Add R&M Form',
	'action' => 'exp_addrm_form.php',
	//'actionextras' => 'disabled',
	'cancel' => 'exp_listrm_form.php',
	'name' => 'addrm_form',
	'okbutton' => 'Save Changes',
	'saveadd' => 'Add Another',
	'cancelbutton' => 'Cancel',
		'layout' => '
	<div class="form-group well well-sm">
		<div class="col-sm-8">
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
					<label for="FORM_NAME" class="col-sm-2 control-label">#FORM_NAME#</label>
					<div class="col-sm-4">
						%FORM_NAME%
					</div>
					<div class="col-sm-4">
						<label>Name of form (required)</label>
					</div>
				</div>
				<div class="form-group">
					<label for="FORM_DESCRIPTION" class="col-sm-2 control-label">#FORM_DESCRIPTION#</label>
					<div class="col-sm-8">
						%FORM_DESCRIPTION%
					</div>
				</div>
				<div class="form-group">
					<label for="RECURRING" class="col-sm-2 control-label">#RECURRING#</label>
					<div class="col-sm-4">
						%RECURRING%
					</div>
					<div class="col-sm-4">
						<label>How Often Needed</label>
					</div>
				</div>
				<div class="form-group">
					<label for="SS_REPORT" class="col-sm-2 control-label">#SS_REPORT#</label>
					<div class="col-sm-4">
						%SS_REPORT%
					</div>
					<div class="col-sm-4">
						<label>Include in various reports</label>
					</div>
				</div>
			</div>
			<div class="col-sm-6">
			</div>
		</div>
		<div class="col-sm-4">
			<!-- CLASSES -->
		</div>
	</div>
	
	'
);

$sts_form_edit_rm_form_form = array(	//! $sts_form_edit_rm_form_form
	'title' => '<span class="glyphicon glyphicon-th-list"></span> Edit R&M Form',
	'action' => 'exp_editrm_form.php',
	//'actionextras' => 'disabled',
	'cancel' => 'exp_listrm_form.php',
	'name' => 'editrm_form',
	'okbutton' => 'Save Changes',
	'cancelbutton' => 'Back',
		'layout' => '
		%FORM_CODE%
	<div class="form-group well well-sm">
		<div class="col-sm-8">
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
					<label for="FORM_NAME" class="col-sm-2 control-label">#FORM_NAME#</label>
					<div class="col-sm-4">
						%FORM_NAME%
					</div>
					<div class="col-sm-4">
						<label>Name of form (required)</label>
					</div>
				</div>
				<div class="form-group">
					<label for="FORM_DESCRIPTION" class="col-sm-2 control-label">#FORM_DESCRIPTION#</label>
					<div class="col-sm-8">
						%FORM_DESCRIPTION%
					</div>
				</div>
				<div class="form-group">
					<label for="RECURRING" class="col-sm-2 control-label">#RECURRING#</label>
					<div class="col-sm-4">
						%RECURRING%
					</div>
					<div class="col-sm-4">
						<label>How Often Needed</label>
					</div>
				</div>
				<div class="form-group">
					<label for="SS_REPORT" class="col-sm-2 control-label">#SS_REPORT#</label>
					<div class="col-sm-4">
						%SS_REPORT%
					</div>
					<div class="col-sm-4">
						<label>Include in various reports</label>
					</div>
				</div>
			</div>
			<div class="col-sm-6">
			</div>
			<div class="col-sm-12">
				<div class="form-group">
					<br>
					<p>Hint: any changes here, click save before working on items below.<br>
					Hint: any changes made to the form, including items below, apply to new reports.</p>
				</div>
			</div>
		</div>
		<div class="col-sm-4">
			<!-- CLASSES -->
		</div>
	</div>
	
	'
);

//! Field Specifications - For use with sts_form

$sts_form_add_rm_form_fields = array(	//! $sts_form_add_rm_form_fields
	'UNIT_TYPE' => array( 'label' => 'Applies To', 'format' => 'enum' ),
	'FORM_NAME' => array( 'label' => 'Name', 'format' => 'text', 'extras' => 'required' ),
	'FORM_DESCRIPTION' => array( 'label' => 'Description', 'format' => 'text' ),
	'RECURRING' => array( 'label' => 'Recurring', 'format' => 'enum' ),
	'SS_REPORT' => array( 'label' => 'Reporting', 'format' => 'bool' ),
);

$sts_form_edit_rm_form_fields = array(	//! $sts_form_edit_rm_form_fields
	'FORM_CODE' => array( 'format' => 'hidden' ),
	'UNIT_TYPE' => array( 'label' => 'Applies To', 'format' => 'enum', 'extras' => 'disabled' ),
	'FORM_NAME' => array( 'label' => 'Name', 'format' => 'text', 'extras' => 'required' ),
	'FORM_DESCRIPTION' => array( 'label' => 'Description', 'format' => 'text' ),
	'RECURRING' => array( 'label' => 'Recurring', 'format' => 'enum' ),
	'SS_REPORT' => array( 'label' => 'Reporting', 'format' => 'bool' ),
);

//! Layout Specifications - For use with sts_result

$sts_result_rm_form_layout = array(	//! $sts_result_rm_form_layout
	'FORM_CODE' => array( 'format' => 'hidden' ),
	'UNIT_TYPE' => array( 'label' => 'Type', 'format' => 'text' ),
	'FORM_NAME' => array( 'label' => 'Name', 'format' => 'text' ),
	'FORM_DESCRIPTION' => array( 'label' => 'Description', 'format' => 'text' ),
	'RECURRING' => array( 'label' => 'Recurring', 'format' => 'enum' ),
	'ITEMS' => array( 'label' => '# Items', 'format' => 'number', 'align' => 'right',
		'snippet' => "COALESCE((SELECT COUNT(*) AS ITEMS
				FROM EXP_INSP_LIST_ITEMS
				WHERE RM_FORM = FORM_CODE), 0)" ),
	'CLASSES' => array( 'label' => '# Classes', 'format' => 'number', 'align' => 'right',
		'snippet' => "COALESCE(CASE WHEN FORM_NAME = 'Default' THEN
					(SELECT COUNT(*) FROM EXP_RM_CLASS
					WHERE EXP_RM_CLASS.UNIT_TYPE = EXP_RM_FORM.UNIT_TYPE)
                ELSE
					(SELECT COUNT(*) FROM EXP_RM_CLASS_FORM
					WHERE RM_FORM = FORM_CODE)
				END , 0)" ),
	'REPORTS' => array( 'label' => '# Reports', 'format' => 'number', 'align' => 'right',
		'snippet' => "COALESCE((SELECT COUNT(*) AS REPORTS
				FROM EXP_INSP_REPORT
				WHERE RM_FORM = FORM_CODE), 0)" ),
	'SS_REPORT' => array( 'label' => 'Reporting', 'format' => 'bool', 'align' => 'center' ),
	'CREATED_DATE' => array( 'label' => 'Created', 'format' => 'timestamp-s' ),
	'CHANGED_BY' => array( 'label' => 'By', 'format' => 'table',
		'table' => USER_TABLE, 'key' => 'USER_CODE', 'fields' => 'USERNAME', 'link' => 'exp_edituser.php?CODE=' )
);

//! Edit/Delete Button Specifications - For use with sts_result

$sts_result_rm_form_edit = array(	//! $sts_result_rm_form_edit
	'title' => '<span class="glyphicon glyphicon-th-list"></span> R&M Forms',
	'sort' => 'UNIT_TYPE asc, FORM_NAME asc',
	'cancel' => 'index.php',
	'add' => 'exp_addrm_form.php',
	//'actionextras' => 'disabled',
	'addbutton' => 'Add R&M Form',
	'cancelbutton' => 'Back',
	'rowbuttons' => array(
		array( 'url' => 'exp_editrm_form.php?CODE=', 'key' => 'FORM_CODE', 'label' => 'FORM_NAME', 'tip' => 'Edit R&M form ', 'icon' => 'glyphicon glyphicon-edit' ),
		array( 'url' => 'exp_deleterm_form.php?CODE=', 'key' => 'FORM_CODE', 'label' => 'FORM_NAME',
		'tip' => 'Delete R&M form ', 'tip2' => "<br><b>Important:</b><br><ol><li>This will remove the form and all the form items and class mappings.</li><li>Any reports based upon this form will still exist.</li><li>You won't be able to make more reports based upon this form once it is deleted.</li></ol>",
		'icon' => 'glyphicon glyphicon-trash', 'confirm' => 'yes', 'showif' => 'can_delete' )
	)
);


?>
