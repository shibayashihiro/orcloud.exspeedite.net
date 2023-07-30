<?php

// $Id: sts_insp_report_class.php 5030 2023-04-12 20:31:34Z duncan $
// Inspection Report

// no direct access
defined('_STS_INCLUDE') or die('Restricted access');

require_once( "sts_table_class.php" );
require_once( "sts_insp_list_item_class.php" );
require_once( "sts_setting_class.php" );
require_once( "sts_rm_form_class.php" );
require_once( "sts_attachment_class.php" );
require_once( "sts_carrier_class.php" );
require_once( "sts_user_log_class.php" );

class sts_insp_report extends sts_table {

	private		$insp_list_item_table;
	private		$insp_report_item_table;
	private		$insp_report_tires_table;
	private		$insp_report_part_table;
	private		$report_form;
	private		$report_fields;
	private		$report_values;
	private		$tires_values;
	private		$part_values;
	private		$setting_table;
	private		$title;
	private		$form_name;
	private		$rm_form_table;
	private		$attachment_table;
	private		$has_attachments;
	private		$carrier_table;
	private		$user_log;	// SCR# 792 - log events
	
	// Constructor does not need the table name
	public function __construct( $database, $debug = false ) {

		$this->debug = $debug;
		$this->primary_key = "REPORT_CODE";
		$this->insp_list_item_table = sts_insp_list_item::getInstance($database, $debug);
		$this->insp_report_item_table = sts_insp_report_item::getInstance($database, $debug);
		$this->insp_report_tires_table = sts_insp_report_tires::getInstance($database, $debug);
		$this->insp_report_part_table = sts_insp_report_part::getInstance($database, $debug);
		$this->rm_form_table = sts_rm_form::getInstance($database, $debug);
		$this->setting_table = sts_setting::getInstance($database, $debug);
		$this->title = $this->setting_table->get( 'option', 'INSPECTION_REPORT_TITLE' );
		$this->has_attachments = $this->setting_table->get( 'option', 'ATTACHMENTS_ENABLED' ) == 'true';

		if( $this->has_attachments )
			$this->attachment_table = sts_attachment::getInstance($database, $debug);
		
		//! SCR# 748 - use carrier table for part vendors
		$this->carrier_table = sts_carrier::getInstance($database, $debug);

		$this->user_log = sts_user_log::getInstance($database, $debug);

		if( $this->debug ) echo "<p>Create sts_insp_report</p>";
		parent::__construct( $database, INSP_REPORT_TABLE, $debug);

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
    
    //! Create an initial empty report with report items
    //! SCR# 522 - pass in form parameter. unit_type is depreciated
    public function create_empty( $unit_type, $unit, $form ) {
		global $_SESSION;
		$result = false;

	    if( $this->debug ) echo "<p>".__METHOD__.": entry, type = $unit_type, unit = $unit</p>";
	    
	    //! SCR# 617 - Fetch previous miles/hours end.
	    $carry_over = $this->database->get_multiple_rows( "select
            (SELECT I.ODO_NOW
            FROM EXP_INSP_REPORT_ITEM I
			WHERE I.REPORT_CODE = R.REPORT_CODE
			AND ITEM_TYPE = 'odometer'
            AND I.ITEM_TEXT = 'ODOMETER MONTH END'
            LIMIT 1) AS ODOMETER_END,
            
            (SELECT I.HOURS_NOW
            FROM EXP_INSP_REPORT_ITEM I
			WHERE I.REPORT_CODE = R.REPORT_CODE
			AND ITEM_TYPE = 'hours'
            AND I.ITEM_TEXT = 'HOURS MONTH END'
            LIMIT 1) AS HOURS_END

			FROM EXP_INSP_REPORT R,
			(SELECT REPORT_CODE, MAX(CREATED_DATE) DT
			FROM EXP_INSP_REPORT
			WHERE UNIT_TYPE='".$unit_type."'
			AND UNIT=".$unit."
			AND RM_FORM=".$form."
			GROUP BY REPORT_CODE
			ORDER BY 2 DESC
			LIMIT 1) R2
			WHERE R.REPORT_CODE = R2.REPORT_CODE" );

	    if( $this->debug ) {
	    	echo "<pre>carry_over:\n";
	    	var_dump($carry_over);
	    	echo "</pre>";		    
	    }
	    
		$fields = array(
			'UNIT_TYPE' => $unit_type,
			'UNIT' => $unit,
			'RM_FORM' => $form,
			'REPORT_DATE' => date("Y-m-d"),
			'MECHANIC' => $_SESSION['EXT_USER_CODE'],
		);
			
	    //! SCR# 522 - populate over the NAME and RECURRING fields
	    $check = $this->rm_form_table->fetch_rows("FORM_CODE = ".$form, "FORM_NAME, RECURRING, SS_REPORT");
	    if( is_array($check) && count($check) == 1 && isset($check[0]["RECURRING"]) ) {
		    $fields['REPORT_NAME'] = $check[0]["FORM_NAME"];
		    $fields['RECURRING'] = $check[0]["RECURRING"];
		    $fields['SS_REPORT'] = $check[0]["SS_REPORT"];
		    switch( $check[0]["RECURRING"] ) {
			    case 'monthly':
			    	$fields["NEXT_DUE"] = date("Y-m-d", strtotime(" +1 months"));
			    	break;
			    
			    case 'quarterly':
			    	$fields["NEXT_DUE"] = date("Y-m-d", strtotime(" +3 months"));
			    	break;
			    
			    case 'annually':
			    	$fields["NEXT_DUE"] = date("Y-m-d", strtotime(" +1 years"));
			    	break;
			    
			    default:
			    	break;
		    }
	    }
	    
		$result = $this->add( $fields );
		
		$this->user_log->log_event( 'inspection', 'CREATE: '.
			$unit_type.'# '.$unit.' -> '. $fields['REPORT_NAME'].
			' report# '.($result ? $result : "false") );

		if( $result > 0 ) {
			$items = $this->insp_list_item_table->fetch_rows("RM_FORM = '".$form."'",
				"*", "SEQUENCE_NO ASC");
			
			if( is_array($items) && count($items) > 0 ) {
				foreach( $items as $item ) {
					$item_fields = array(
						'REPORT_CODE'	=> $result,
						'ITEM_TYPE'		=> $item['ITEM_TYPE'],
						'SEQUENCE_NO'	=> $item['SEQUENCE_NO'],
						'ITEM_TEXT'		=> $item['ITEM_TEXT'],
						'ITEM_HELP'		=> $item['ITEM_HELP'],
						'ITEM_EXTRA'	=> $item['ITEM_EXTRA'],
						'INCREMENT'		=> $item['INCREMENT']
					);
					
					// Populate driver field with default driver
					if( $item['ITEM_TYPE'] == 'driver' ) {
				    	$check = $this->database->get_one_row("
				    		SELECT DRIVER_CODE FROM EXP_DRIVER
							WHERE DEFAULT_".strtoupper($unit_type)." = ".$unit );
			    	
				    	if( is_array($check) && isset($check["DRIVER_CODE"]) )
				    		$item_fields['DRIVER'] = $check["DRIVER_CODE"];
					}
					
					//! SCR# 617 - Carry over previous miles/hours
					if( $item['ITEM_TYPE'] == 'odometer' &&
						$item['ITEM_TEXT'] == 'ODOMETER MONTH BEGIN' &&
						is_array($carry_over) && count($carry_over) == 1 &&
						! empty($carry_over[0]['ODOMETER_END']) )
						$item_fields['ODO_NOW'] = $carry_over[0]['ODOMETER_END'];
					
					if( $item['ITEM_TYPE'] == 'hours' &&
						$item['ITEM_TEXT'] == 'HOURS MONTH BEGIN' &&
						is_array($carry_over) && count($carry_over) == 1 &&
						! empty($carry_over[0]['HOURS_END']) )
						$item_fields['HOURS_NOW'] = $carry_over[0]['HOURS_END'];
					
					$item_code = $this->insp_report_item_table->add( $item_fields );
					
					if( $item_code && $item['ITEM_TYPE'] == 'parts' )
						$this->insp_report_part_table->add(array('ITEM_CODE' => $item_code));
				}
			}
		}
		
	    if( $this->debug ) echo "<p>".__METHOD__.": return $result</p>";
		return $result;
    }
    
	//! Delete a report and all secondary tables
	public function delete( $report, $type = "" ) {
		$result = false;
		
	    if( $this->debug ) echo "<p>".__METHOD__.": entry, report = $report</p>";
    	$check = $this->fetch_rows("REPORT_CODE = ".$report);
    	if( is_array($check) && count($check) == 1 ) {
	    	$unit_type = $check[0]["UNIT_TYPE"];
	    	$unit = $check[0]["UNIT"];
	    	$report_name = $check[0]["REPORT_NAME"];
    		$check2 = $this->insp_report_item_table->fetch_rows(
    			"REPORT_CODE = ".$report." AND ITEM_TYPE = 'tires'", "ITEM_CODE");
			if( is_array($check2) && count($check2) == 1 && ! empty($check2[0]["ITEM_CODE"])) {
				$result = $this->insp_report_tires_table->delete_row(
					"ITEM_CODE = ".$check2[0]["ITEM_CODE"] );
			}
			$result = $this->insp_report_item_table->delete_row( "REPORT_CODE = ".$report );
			
			$this->user_log->log_event( 'inspection', 'DELETE: '.
				$unit_type.'# '.$unit.' -> '. $report_name.
				' report# '.$report);

    	}
    	
		//! SCR# 522 - delete attachments if any exist for this report
		if( $this->has_attachments )
			$result = $this->attachment_table->delete_row( "SOURCE_CODE = ".$report.
				" AND SOURCE_TYPE = 'report'" );

    	$result = $this->delete_row("REPORT_CODE = ".$report);
    	
	    if( $this->debug ) echo "<p>".__METHOD__.": return $result</p>";
		return $result;
	}
	
	//! override parent
	public function get_enum_choices( $column ) {
		if( substr($column, 0, strlen('CHECK_')) === 'CHECK_') {
			return array('OK','Future Repair','Needs Repair');
		} else if( substr($column, 0, strlen('ACTION_')) === 'ACTION_') {
			return array('Not Done','Done');
		} else if( substr($column, 0, strlen('BRAKES_')) === 'BRAKES_') {
			return array('OK', '< 5/16&quot; / 8mm');
		} else if( substr($column, 0, strlen('TREAD_')) === 'TREAD_') {
			$tread = array();
			for( $c=26; $c>=1; $c-- ) $tread[] = $c.'/32&quot;';
			return $tread;
		} else if( substr($column, 0, strlen('TLIFE_')) === 'TLIFE_') {
			$tlife = array();
			for( $c=10; $c<=90; $c+=10 ) $tlife[] = $c.'%';
			return $tlife;
		} else {
			return parent::get_enum_choices( $column );
		}
	}
	
	private function get_tire_values( $item ) {
    	// Fetch tires values
    	$this->tires_values = $this->insp_report_tires_table->fetch_rows("ITEM_CODE = ".$item);
    	
		if( is_array($this->tires_values) && count($this->tires_values) > 0 ) {
			foreach( $this->tires_values as $tire ) {
				$location = $tire["TIRE_LOCATION"];
				if( isset($tire["PSI"]))
					$this->report_values['PSI_'.$location.$item] = $tire["PSI"];
				if( isset($tire["PSI2"]))
					$this->report_values['PSI2_'.$location.$item] = $tire["PSI2"];
				if( isset($tire["TREAD"]))
					$this->report_values['TREAD_'.$location.$item] = str_replace('"', '&quot;', $tire["TREAD"]);
				if( isset($tire["TLIFE"]))
					$this->report_values['TLIFE_'.$location.$item] = $tire["TLIFE"];
				if( ! empty($tire["TNOTE"]))
					$this->report_values['TNOTE_'.$location.$item] = $tire["TNOTE"];
			}
		}
	}
	
	private function layout_tires( $item_code, $location ) {
    	$this->report_fields['PSI_'.$location.$item_code] = array( 'label' => 'PSI', 'format' => 'number', 'align' => 'right' );
    	$this->report_fields['PSI2_'.$location.$item_code] = array( 'label' => 'After', 'format' => 'number', 'align' => 'right' );
    	$this->report_fields['TREAD_'.$location.$item_code] = array( 'label' => 'Tread', 'format' => 'enum', 'align' => 'right' );
    	$this->report_fields['TLIFE_'.$location.$item_code] = array( 'label' => 'Life', 'format' => 'enum', 'align' => 'right' );
    	$this->report_fields['TNOTE_'.$location.$item_code] = array( 'label' => 'Note', 'format' => 'text' );
	}

	private function fields_tires( $item_code, $location ) {
		return '
			<div class="col-sm-6 tleft">
				%PSI_'.$location.$item_code.'%
			</div>
			<div class="col-sm-6 tright">
				%PSI2_'.$location.$item_code.'%
			</div>
			<div class="col-sm-12">
				%TREAD_'.$location.$item_code.'%
			</div>
			<div class="col-sm-12">
				%TLIFE_'.$location.$item_code.'%
			</div>
			<div class="col-sm-12">
				%TNOTE_'.$location.$item_code.'%
			</div>
		';
	}


	public function get_part_html( $item, $part, $print = false ) {
		$p = $item.$part["PART_CODE"];
		
		if( $part["TOTAL"] <> $part["QUANTITY"] * $part["COST"] )
			$part["TOTAL"] = $part["QUANTITY"] * $part["COST"];
		
		//! SCR# 748 - get a list of part vendors
		if( $print ) {
			$vendor_column = '<td>'.(isset($part["VENDOR_NAME"]) ? $part["VENDOR_NAME"] : '').'</td>
			';
		} else {
			$vendors = $this->carrier_table->fetch_rows("CARRIER_TYPE = 'vendor'", "CARRIER_CODE, CARRIER_NAME", "CARRIER_NAME ASC");
			if( count($vendors) > 0 ) {
				$vendor_choices = '<select class="form-control" name="PART_VENDOR_'.$p.'" id="PART_VENDOR_'.$p.'">
				';
				$vendor_choices .= '<option value="NULL"'.(isset($part["VENDOR"]) && $part["VENDOR"] > 0 ? '' :' selected').'>No Vendor</option>
				';
				foreach( $vendors as $row ) {
					$vendor_choices .= '<option value="'.$row["CARRIER_CODE"].'"'.(isset($part["VENDOR"]) && $part["VENDOR"] == $row["CARRIER_CODE"] ? ' selected' : '').'>'.$row["CARRIER_NAME"].'</option>
					';
				}
				$vendor_choices .= '</select>
				';
			} else {
				$vendor_choices = 'No Vendors Available';
			}
			$vendor_column = '<td>'.$vendor_choices.'</td>
			';
		}
		
		return '<tr id="PART_'.$p.'">
			'.($print ? '' : '<td><a class="btn btn-sm btn-danger delpart" data.tr="PART_'.$p.'" data.item="'.$item.'" data.code="'.$part["PART_CODE"].'"><span class="glyphicon glyphicon-remove"></span></a></td>').'
			'.$vendor_column.'
			<td><input class="form-control" name="PART_NAME_'.$p.'" id="PART_NAME_'.$p.'" type="text"  
		'.($print ? '' : 'placeholder="Name"').'
		'.(! empty($part["PART_NAME"]) ? ' value="'.$part["PART_NAME"].'"' : '').
		($print ? ' readonly' : '').'></td>
			<td><input class="form-control" name="PART_DESC_'.$p.'" id="PART_DESC_'.$p.'" type="text"  
		'.($print ? '' : 'placeholder="Description"').'
		'.(! empty($part["PART_DESCRIPTION"]) ? ' value="'.$part["PART_DESCRIPTION"].'"' : '').
			($print ? ' readonly' : '').'></td>
			<td class="text-right">
			<input class="form-control text-right" name="PART_QTY_'.$p.'" id="PART_QTY_'.$p.'"
				type="'.($print ? 'text' : 'number').'" '.($print ? '' : 'placeholder="Quantity"').' 
				'.(! empty($part["QUANTITY"]) ? ' value="'.$part["QUANTITY"].'"' : '').
				($print ? ' readonly' : '').'></td>
			<td class="text-right">
			<input class="form-control text-right" name="PART_COST_'.$p.'" id="PART_COST_'.$p.'"
				type="'.($print ? 'text' : 'number').'" '.($print ? '' : 'placeholder="Cost"').' step="0.01"
				'.(! empty($part["COST"]) ? ' value="'.$part["COST"].'"' : '').
				($print ? ' readonly' : '').'></td>
			<td class="text-right">
			<input class="form-control text-right subtot" name="PART_TOTAL_'.$p.'" id="PART_TOTAL_'.$p.'"
				type="text" '.($print ? '' : 'placeholder="Total"').'
				value="'.(! empty($part["TOTAL"]) ? number_format((float)$part["TOTAL"],2,'.','') : '0.00').'" readonly></td>
		</tr>
		';
	}
	
	private function get_part_values( $item, $print = false ) {
    	$output = '';
    	// Fetch part values, in order
    	//! SCR# 748 - include vendor name
    	$this->part_values = $this->insp_report_part_table->fetch_rows("ITEM_CODE = ".$item,
    		"*, (SELECT CARRIER_NAME FROM EXP_CARRIER WHERE CARRIER_CODE = VENDOR) AS VENDOR_NAME", "PART_CODE ASC");
    	
		if( is_array($this->part_values) && count($this->part_values) > 0 ) {
			foreach( $this->part_values as $part ) {
				$output .= $this->get_part_html( $item, $part, $print );
			}
		}
		return $output;
	}
	
	// Can this column be null?
	public function is_nullable( $column ) {
		if( $this->debug ) echo "<p>".__METHOD__.": $column in table $this->table_name</p>";
		$x = explode('_', $column);
		return is_array($x) && count($x) > 0 && in_array($x[0], array('DRIVER','TRACTOR','TRAILER')) ? true : parent::is_nullable( $column );
	}

    //! For a given item, add form layout and fields info
    private function layout( $item ) {
    	$this->report_form['layout'] .= '<input type="hidden" name="ITEM_CODE[]" value="'.$item['ITEM_CODE'].'">
    	<input type="hidden" name="ITEM_TYPE[]" value="'.$item['ITEM_TYPE'].'">
	    ';
	    if( isset($item['ITEM_TYPE'])) {
		    switch( $item['ITEM_TYPE'] ) {
			    case 'check': //! check
			    	$this->report_fields['CHECK_'.$item['ITEM_CODE']] = array( 'label' => 'Status', 'format' => 'enum2' );
			    	$this->report_fields['COMMENTS_'.$item['ITEM_CODE']] = array( 'label' => 'Note', 'format' => 'text' );
			    	$this->report_values['CHECK_'.$item['ITEM_CODE']] = $item['CHECK_STATUS'];
			    	$this->report_values['COMMENTS_'.$item['ITEM_CODE']] = $item['COMMENTS'];
			    	if( $item['ITEM_EXTRA'] == 'brakes' ) {
				    	$this->report_fields['BRAKES_'.$item['ITEM_CODE']] = array( 'label' => 'Brakes', 'format' => 'enum2' );
						$this->report_values['BRAKES_'.$item['ITEM_CODE']] = str_replace('"', '&quot;',$item['BRAKES_STATUS']);
			    	} else if( $item['ITEM_EXTRA'] == 'next due' ) {
				    	$this->report_fields['NEXT_DUE_'.$item['ITEM_CODE']] = array( 'label' => 'Next Due', 'format' => 'date' );
						$this->report_values['NEXT_DUE_'.$item['ITEM_CODE']] = $item['NEXT_DUE'];
				    }

			    	
			    	$this->report_form['layout'] .= '
			    		<div class="form-group">
							<label for="CHECK_'.$item['ITEM_CODE'].'" class="col-sm-6 control-label">'.$item['ITEM_TEXT'].(empty($item['ITEM_HELP']) ? '' : ' <span class="glyphicon glyphicon-info-sign inform" data-content="'.$item['ITEM_HELP'].'"></span>').'</label>
							<div class="col-sm-6">
								<div class="form-group tighter">
									<div class="col-sm-12">
										%CHECK_'.$item['ITEM_CODE'].'%
									</div>
									<div class="col-sm-12">
										%COMMENTS_'.$item['ITEM_CODE'].'%'.($item['ITEM_EXTRA'] == 'brakes' ? '
										%BRAKES_'.$item['ITEM_CODE'].'%' : '').($item['ITEM_EXTRA'] == 'next due' ? '
									</div>
									<label for="NEXT_DUE_'.$item['ITEM_CODE'].'" class="col-sm-6 control-label">Next Due</label>
									<div class="col-sm-6">	
										%NEXT_DUE_'.$item['ITEM_CODE'].'%
									' : '').'
									</div>
								</div>
				    		</div>
				    	</div>
			    	';
			    	break;

			    case 'action': //! action
			    	$this->report_fields['ACTION_'.$item['ITEM_CODE']] = array( 'label' => 'Action', 'format' => 'enum2' );
			    	$this->report_values['ACTION_'.$item['ITEM_CODE']] = $item['ACTION_STATUS'];
			    	if( $item['ITEM_EXTRA'] == 'next due' ) {
				    	$this->report_fields['NEXT_DUE_'.$item['ITEM_CODE']] = array( 'label' => 'Next Due', 'format' => 'date' );
						$this->report_values['NEXT_DUE_'.$item['ITEM_CODE']] = $item['NEXT_DUE'];
				    }
				    
			    	$this->report_form['layout'] .= '
			    		<div class="form-group">
							<label for="ACTION_'.$item['ITEM_CODE'].'" class="col-sm-6 control-label">'.$item['ITEM_TEXT'].(empty($item['ITEM_HELP']) ? '' : ' <span class="glyphicon glyphicon-info-sign inform" data-content="'.$item['ITEM_HELP'].'"></span>').'</label>
							<div class="col-sm-6">
								<div class="form-group tighter">
									<div class="col-sm-12">
										%ACTION_'.$item['ITEM_CODE'].'%
									</div>'.($item['ITEM_EXTRA'] == 'next due' ? '
									<label for="NEXT_DUE_'.$item['ITEM_CODE'].'" class="col-sm-6 control-label">Next Due</label>
									<div class="col-sm-6">	
										%NEXT_DUE_'.$item['ITEM_CODE'].'%
									</div>' : '').'
								</div>
							</div>
			    		</div>
			    	';
			    	break;

			    case 'group': //! group
			    	$this->report_form['layout'] .= '
			    		<div class="col-sm-12 tighter">
				    		<h3 class="bg-success">'.$item['ITEM_TEXT'].(empty($item['ITEM_HELP']) ? '' : ' <span class="glyphicon glyphicon-info-sign inform" data-content="'.$item['ITEM_HELP'].'"></span>').'</h3>
			    		</div>
			    	';
			    	break;

			    case 'damage': //! damage
			    	//! SCR# 537 - damage for tractors too
				    $tp = $this->report_values['UNIT_TYPE'];
				    	$this->report_fields['COMMENTS_'.$item['ITEM_CODE']] = array(
				    		'label' => 'Details of damage',
				    		'format' => 'textarea', 'extras' => 'rows="3"' );
				    	$this->report_values['COMMENTS_'.$item['ITEM_CODE']] = $item['COMMENTS'];
				    	
				    	$damage = explode(',', $item['DAMAGE']);
				    	
	
				    	$this->report_form['layout'] .= '
						<style>
						.check {
						    opacity:0.5;
							color: #996;
							border: 4px solid #c12e2a !important;
							
						}
						.img-trailer1 {
							background-color: #fff;
							border: 4px solid transparent;
							height: 100px;
						}
						.img-trailer2 {
							background-color: #fff;
							border: 4px solid transparent;
							height: 87px;
						}
						</style>
			    		<div class="col-sm-12 tighter">
				    		<h3 class="bg-success">Body Condition'.(empty($item['ITEM_HELP']) ? '' : ' <span class="glyphicon glyphicon-info-sign inform" data-content="'.$item['ITEM_HELP'].'"></span>').'</h3>
			    		</div>
						<div class="form-group tighter">
							<div class="col-sm-10 col-sm-offset-1 text-center">
								<label for="'.$tp.'_ls"><img src="images/'.$tp.'_ls.png" alt="'.$tp.'_ls" class="img-responsive img-trailer1 img-check'.(in_array($tp.'_ls', $damage) ? ' check' : '').'"><input type="checkbox" name="DAMAGE_'.$item['ITEM_CODE'].'[]" id="'.$tp.'_ls" value="'.$tp.'_ls" class="hidden" autocomplete="off"'.(in_array($tp.'_ls', $damage) ? ' checked' : '').'></label>
								<label for="'.$tp.'_fr"><img src="images/'.$tp.'_fr.png" alt="'.$tp.'_fr" class="img-responsive img-trailer1 img-check'.(in_array($tp.'_fr', $damage) ? ' check' : '').'"><input type="checkbox" name="DAMAGE_'.$item['ITEM_CODE'].'[]" id="'.$tp.'_fr" value="'.$tp.'_fr" class="hidden" autocomplete="off"'.(in_array($tp.'_fr', $damage) ? ' checked' : '').'></label>
								<label for="'.$tp.'_rs"><img src="images/'.$tp.'_rs.png" alt="'.$tp.'_rs" class="img-responsive img-trailer1 img-check'.(in_array($tp.'_rs', $damage) ? ' check' : '').'"><input type="checkbox" name="DAMAGE_'.$item['ITEM_CODE'].'[]" id="'.$tp.'_rs" value="'.$tp.'_rs" class="hidden" autocomplete="off"'.(in_array($tp.'_rs', $damage) ? ' checked' : '').'></label>
							</div>
						</div>
						<div class="form-group tighter">	
							<div class="col-sm-10 col-sm-offset-1 text-center">
								<label for="'.$tp.'_tp"><img src="images/'.$tp.'_tp.png" alt="'.$tp.'r_tp" class="img-trailer2 img-check'.(in_array($tp.'_tp', $damage) ? ' check' : '').'"><input type="checkbox" name="DAMAGE_'.$item['ITEM_CODE'].'[]" id="'.$tp.'_tp" value="'.$tp.'_tp" class="hidden" autocomplete="off"'.(in_array($tp.'_tp', $damage) ? ' checked' : '').'></label>
								<label for="'.$tp.'_re"><img src="images/'.$tp.'_re.png" alt="'.$tp.'_re" class="img-trailer2 img-check'.(in_array($tp.'_re', $damage) ? ' check' : '').'"><input type="checkbox" name="DAMAGE_'.$item['ITEM_CODE'].'[]" id="'.$tp.'_re" value="'.$tp.'_re" class="hidden" autocomplete="off"'.(in_array($tp.'_re', $damage) ? ' checked' : '').'></label>
								'.($tp == 'trailer' ? '<label for="trailer_fl"><img src="images/trailer_fl.png" alt="trailer_fl" class="img-trailer2 img-check'.(in_array('trailer_fl', $damage) ? ' check' : '').'"><input type="checkbox" name="DAMAGE_'.$item['ITEM_CODE'].'[]" id="trailer_fl" value="trailer_fl" class="hidden" autocomplete="off"'.(in_array('trailer_fl', $damage) ? ' checked' : '').'></label>' : '').'
							</div>
						</div>
						<div class="form-group tighter">	
							<div class="col-sm-10 col-sm-offset-1">
								<p>'.$item['ITEM_TEXT'].'</p>
								%COMMENTS_'.$item['ITEM_CODE'].'%
							</div>
						</div>

	<script language="JavaScript" type="text/javascript"><!--
		
		$(document).ready( function () {
			$(".img-check").click(function(){
				$(this).toggleClass("check");
			});
		});	
	//--></script>
			    	';
			    	break;

			    case 'tires': //! tires
			    	$this->get_tire_values( $item['ITEM_CODE'] );
			    	
			    	//TIRES_DUALIES
			    	$this->report_fields['TIRES_DUALIES_'.$item['ITEM_CODE']] = array(
			    		'label' => 'Dually', 'format' => 'bool2' );
			    	$this->report_values['TIRES_DUALIES_'.$item['ITEM_CODE']] = $item['TIRES_DUALIES'];

			    	if( $this->report_values['UNIT_TYPE'] == 'tractor' ) { //! Tractor tires
				    	$this->layout_tires( $item['ITEM_CODE'], 'STL_' );
				    	$this->layout_tires( $item['ITEM_CODE'], 'STR_' );
				    	
				    	$this->layout_tires( $item['ITEM_CODE'], 'OML_' );
				    	$this->layout_tires( $item['ITEM_CODE'], 'IML_' );
				    	$this->layout_tires( $item['ITEM_CODE'], 'IMR_' );
				    	$this->layout_tires( $item['ITEM_CODE'], 'OMR_' );
				    	
				    	$this->layout_tires( $item['ITEM_CODE'], 'ORL_' );
				    	$this->layout_tires( $item['ITEM_CODE'], 'IRL_' );
				    	$this->layout_tires( $item['ITEM_CODE'], 'IRR_' );
				    	$this->layout_tires( $item['ITEM_CODE'], 'ORR_' );
				    	
						// How to do values?
					    $this->report_form['layout'] .= '
			    		<div class="col-sm-12 tighter">
				    		<h3 class="bg-success">'.ucfirst($this->report_values['UNIT_TYPE']).' Tires'.(empty($item['ITEM_HELP']) ? '' : ' <span class="glyphicon glyphicon-info-sign inform" data-content="'.$item['ITEM_HELP'].'"></span>').'&nbsp;(<label for="TIRES_DUALIES_'.$item['ITEM_CODE'].'" class="control-label">#TIRES_DUALIES_'.$item['ITEM_CODE'].'#</label>
								%TIRES_DUALIES_'.$item['ITEM_CODE'].'%) <small>(be sure to <span class="label label-success">Save Changes</span> )</small></h3>
			    		</div>
			    		<div class="form-group">
				    		<div class="col-sm-2">
								<label class="col-sm-12 control-label">Front Left</label>
								'.$this->fields_tires( $item['ITEM_CODE'], 'STL_' ).'
				    		</div>
				    		<div class="col-sm-1">
				    			<img src="images/tire1L.png" alt="tire1L">
				    		</div>
				    		<div class="col-sm-5 text-center">
				    			<img src="images/bar.png" alt="bar" style="padding-left: 25px">
				    		</div>
				    		<div class="col-sm-1 text-right" style="padding-right: 0px;">
				    			<img src="images/tire1R.png" alt="tire1R">
				    		</div>
				    		<div class="col-sm-2">
								<label class="col-sm-12 control-label">Front Right</label>
								'.$this->fields_tires( $item['ITEM_CODE'], 'STR_' ).'
				    		</div>

						</div>
			    		<div class="clearfix form-group">
				    		<div class="col-sm-2">
								<label class="col-sm-12 control-label">Out Mid Left</label>
								'.$this->fields_tires( $item['ITEM_CODE'], 'OML_' ).'
				    		</div>
				    		<div class="col-sm-1 single">
				    			<img src="images/tire1L.png" alt="tire1L">
				    		</div>
				    		<div class="col-sm-1 dually">
				    			<img src="images/tire2.png" alt="tire2">
				    		</div>
				    		<div class="col-sm-2 dually">
								<label class="col-sm-12 control-label">Ins Mid Left</label>
								'.$this->fields_tires( $item['ITEM_CODE'], 'IML_' ).'
				    		</div>
				    		<div class="col-sm-2 single">
				    		</div>

				    		<div class="col-sm-1">
				    		<img src="images/bar.png" alt="bar">
				    		</div>

				    		<div class="col-sm-2 single">
				    		</div>
				    		<div class="col-sm-2 dually">
								<label class="col-sm-12 control-label">Ins Mid Right</label>
								'.$this->fields_tires( $item['ITEM_CODE'], 'IMR_' ).'
				    		</div>
				    		<div class="col-sm-1 single">
				    			<img src="images/tire1R.png" alt="tire1R">
				    		</div>
				    		<div class="col-sm-1 dually">
				    			<img src="images/tire2.png" alt="tire2">
				    		</div>
				    		<div class="col-sm-2">
								<label class="col-sm-12 control-label">Out Mid Right</label>
								'.$this->fields_tires( $item['ITEM_CODE'], 'OMR_' ).'
				    		</div>

			    		</div>

			    		<div class="clearfix form-group">
				    		<div class="col-sm-2">
								<label class="col-sm-12 control-label">Out Bk Left</label>
								'.$this->fields_tires( $item['ITEM_CODE'], 'ORL_' ).'
				    		</div>
				    		<div class="col-sm-1 single">
				    			<img src="images/tire1L.png" alt="tire1L">
				    		</div>
				    		<div class="col-sm-1 dually">
				    			<img src="images/tire2.png" alt="tire2">
				    		</div>
				    		<div class="col-sm-2 dually">
								<label class="col-sm-12 control-label">Ins Bk Left</label>
								'.$this->fields_tires( $item['ITEM_CODE'], 'IRL_' ).'
				    		</div>
				    		<div class="col-sm-2 single">
				    		</div>

				    		<div class="col-sm-1">
				    		<img src="images/bar.png" alt="bar">
				    		</div>

				    		<div class="col-sm-2 single">
				    		</div>
				    		<div class="col-sm-2 dually">
								<label class="col-sm-12 control-label">Ins Bk Right</label>
								'.$this->fields_tires( $item['ITEM_CODE'], 'IRR_' ).'
				    		</div>
				    		<div class="col-sm-1 single">
				    			<img src="images/tire1R.png" alt="tire1R">
				    		</div>
				    		<div class="col-sm-1 dually">
				    			<img src="images/tire2.png" alt="tire2">
				    		</div>
				    		<div class="col-sm-2">
								<label class="col-sm-12 control-label">Out Bk Right</label>
								'.$this->fields_tires( $item['ITEM_CODE'], 'ORR_' ).'
				    		</div>

			    		</div>
						';

				    } else { //! Trailer tires
				    	$this->layout_tires( $item['ITEM_CODE'], 'OFL_' );
				    	$this->layout_tires( $item['ITEM_CODE'], 'IFL_' );
				    	$this->layout_tires( $item['ITEM_CODE'], 'IFR_' );
				    	$this->layout_tires( $item['ITEM_CODE'], 'OFR_' );
				    	
				    	$this->layout_tires( $item['ITEM_CODE'], 'ORL_' );
				    	$this->layout_tires( $item['ITEM_CODE'], 'IRL_' );
				    	$this->layout_tires( $item['ITEM_CODE'], 'IRR_' );
				    	$this->layout_tires( $item['ITEM_CODE'], 'ORR_' );					    

					    $this->report_form['layout'] .= '
			    		<div class="col-sm-12 tighter">
				    		<h3 class="bg-success">'.ucfirst($this->report_values['UNIT_TYPE']).' Tires'.(empty($item['ITEM_HELP']) ? '' : ' <span class="glyphicon glyphicon-info-sign inform" data-content="'.$item['ITEM_HELP'].'"></span>').
				    		'&nbsp;(<label for="TIRES_DUALIES_'.$item['ITEM_CODE'].'" class="control-label">#TIRES_DUALIES_'.$item['ITEM_CODE'].'#</label>
								%TIRES_DUALIES_'.$item['ITEM_CODE'].'%) <small>(be sure to <span class="label label-success">Save Changes</span> )</small></h3>
			    		</div>
			    		<div class="form-group tighter">
				    		<div class="col-sm-2">
								<label class="col-sm-12 control-label">Out Front Left</label>
								'.$this->fields_tires( $item['ITEM_CODE'], 'OFL_' ).'
				    		</div>
				    		<div class="col-sm-1 single">
				    			<img src="images/tire1L.png" alt="tire1L">
				    		</div>
				    		<div class="col-sm-1 dually">
				    			<img src="images/tire2.png" alt="tire2">
				    		</div>
				    		<div class="col-sm-2 dually">
								<label class="col-sm-12 control-label">Ins Front Left</label>
								'.$this->fields_tires( $item['ITEM_CODE'], 'IFL_' ).'
				    		</div>
				    		<div class="col-sm-2 single">
				    		</div>

				    		<div class="col-sm-1">
				    		<img src="images/bar.png" alt="bar">
				    		</div>

				    		<div class="col-sm-2 single">
				    		</div>
				    		<div class="col-sm-2 dually">
								<label class="col-sm-12 control-label">Ins Front Right</label>
								'.$this->fields_tires( $item['ITEM_CODE'], 'IFR_' ).'
				    		</div>
				    		<div class="col-sm-1 single">
				    			<img src="images/tire1R.png" alt="tire1R">
				    		</div>
				    		<div class="col-sm-1 dually">
				    			<img src="images/tire2.png" alt="tire2">
				    		</div>
				    		<div class="col-sm-2">
								<label class="col-sm-12 control-label">Out Front Right</label>
								'.$this->fields_tires( $item['ITEM_CODE'], 'OFR_' ).'
				    		</div>

			    		</div>

			    		<div class="form-group tighter">
				    		<div class="col-sm-2">
								<label class="col-sm-12 control-label">Out Bk Left</label>
								'.$this->fields_tires( $item['ITEM_CODE'], 'ORL_' ).'
				    		</div>
				    		<div class="col-sm-1 single">
				    			<img src="images/tire1L.png" alt="tire1L">
				    		</div>
				    		<div class="col-sm-1 dually">
				    		<img src="images/tire2.png" alt="tire2">
				    		</div>
				    		<div class="col-sm-2 dually">
								<label class="col-sm-12 control-label">Ins Bk Left</label>
								'.$this->fields_tires( $item['ITEM_CODE'], 'IRL_' ).'
				    		</div>
				    		<div class="col-sm-2 single">
				    		</div>

				    		<div class="col-sm-1">
				    		<img src="images/bar.png" alt="bar">
				    		</div>

				    		<div class="col-sm-2 single">
				    		</div>
				    		<div class="col-sm-2 dually">
								<label class="col-sm-12 control-label">Ins Bk Right</label>
								'.$this->fields_tires( $item['ITEM_CODE'], 'IRR_' ).'
				    		</div>
				    		<div class="col-sm-1 single">
				    			<img src="images/tire1R.png" alt="tire1R">
				    		</div>
				    		<div class="col-sm-1 dually">
				    			<img src="images/tire2.png" alt="tire2">
				    		</div>
				    		<div class="col-sm-2">
								<label class="col-sm-12 control-label">Out Rear Right</label>
								'.$this->fields_tires( $item['ITEM_CODE'], 'ORR_' ).'
				    		</div>

			    		</div>
						';
						}
					

			    	break;
			    case 'parts': //! parts
				    
				    //! SCR# 596 - R&M - Repair notes - title does not appear
				    $this->report_form['layout'] .= '
			    		<div class="col-sm-12 tighter">
				    		<h3 class="bg-success">'.(empty($item['ITEM_TEXT']) ? 'Parts' : $item['ITEM_TEXT']).(empty($item['ITEM_HELP']) ? '' : ' <span class="glyphicon glyphicon-info-sign inform" data-content="'.$item['ITEM_HELP'].'"></span>').' <small>(be sure to <span class="label label-success">Save Changes</span> )</small></h3>
			    		</div>
			    		<div class="col-sm-12 tighter">
							<table id="part_table" width="100%" align="center" border="2" cellspacing="0">
							<thead>
							<tr valign="top" style="background-color: #4d8e31; color: #fff;">
								<th align="center" class="text-center" style="padding: 5px;"></th>
								<th align="center" class="text-center" style="padding: 5px;" width="10%">Vendor</th>
								<th align="center" class="text-center" style="padding: 5px;" width="10%">Name</th>
								<th align="center" class="text-center" style="padding: 5px;" width="50%">Description</th>
								<th align="center" class="text-center" style="padding: 5px;" width="10%">Quantity</th>
								<th align="center" class="text-center" style="padding: 5px;" width="10%">Cost</th>
								<th align="center" class="text-center" style="padding: 5px;" width="10%">Total</th>
							</tr>
							</thead><tbody>
							'.$this->get_part_values( $item['ITEM_CODE'] ).'
							</tbody><tfoot>
							<tr>
								<td colspan="5"><a class="btn btn-sm btn-success" id="addpart" data.item="'.$item['ITEM_CODE'].'"><span class="glyphicon glyphicon-plus"></span></a><span style="float: right; padding: 5px;"><strong>Total:</strong></span></td>
								<td class="text-right"><input class="form-control text-right grdtot"
									type="text" readonly></td>
							</tr>
							</tfoot>
							</table>

			    		</div>
			    	';
			    	break;
			    case 'notes': //! notes
			    	//! SCR# 596 - R&M - Repair notes - title does not appear
			    	$this->report_fields['NOTES_'.$item['ITEM_CODE']] = array(
			    		'label' => (empty($item['ITEM_TEXT']) ? 'Notes' : $item['ITEM_TEXT']),
			    		'format' => 'textarea', 'extras' => 'rows="3"' );
			    	$this->report_values['NOTES_'.$item['ITEM_CODE']] = $item['COMMENTS'];
			    	
				    $this->report_form['layout'] .= '
			    		<div class="col-sm-12 tighter">
				    		<h3 class="bg-success">'.(empty($item['ITEM_TEXT']) ? 'Notes' : $item['ITEM_TEXT']).(empty($item['ITEM_HELP']) ? '' : ' <span class="glyphicon glyphicon-info-sign inform" data-content="'.$item['ITEM_HELP'].'"></span>').' <small>(be sure to <span class="label label-success">Save Changes</span> )</small></h3>
			    		</div>
			    		<div class="form-group tighter">
							<div class="col-sm-12" style="padding-left: 30px; padding-right: 30px;">
								%NOTES_'.$item['ITEM_CODE'].'%
							</div>
			    		</div>
			    	';
			    	break;

			    case 'odometer': //! SCR# 522 - odometer
			    	//! SCR# 556 - restrict length to column size
			    	$this->report_fields['ODO_NOW_'.$item['ITEM_CODE']] = array( 'label' => 'odometer', 'format' => 'text', 'align' => 'right', 'length' => $this->insp_report_item_table->get_max_length( 'ODO_NOW' ) );
			    	$this->report_values['ODO_NOW_'.$item['ITEM_CODE']] = $item['ODO_NOW'];
			    	
			    	$this->report_fields['ODO_NEXT_'.$item['ITEM_CODE']] = array( 'label' => 'odometer', 'format' => 'text', 'align' => 'right', 'length' => $this->insp_report_item_table->get_max_length( 'ODO_NEXT' ) );
			    	$this->report_values['ODO_NEXT_'.$item['ITEM_CODE']] = $item['ODO_NEXT'];
			    	
			    	$this->report_form['layout'] .= '
			    		<div class="form-group">
							<label for="ODO_NOW_'.$item['ITEM_CODE'].'" class="col-sm-3 control-label">'.$item['ITEM_TEXT'].(empty($item['ITEM_HELP']) ? '' : ' <span class="glyphicon glyphicon-info-sign inform" data-content="'.$item['ITEM_HELP'].'"></span>').'</label>
							<div class="col-sm-3">
								%ODO_NOW_'.$item['ITEM_CODE'].'%
							</div>
							<label for="ODO_NEXT_'.$item['ITEM_CODE'].'" class="col-sm-3 control-label">Next '.$item['ITEM_TEXT'].(empty($item['ITEM_HELP']) ? '' : ' <span class="glyphicon glyphicon-info-sign inform" data-content="'.$item['ITEM_HELP'].'"></span>').'</label>
							<div class="col-sm-3">
								%ODO_NEXT_'.$item['ITEM_CODE'].'%
							</div>
			    		</div>
			    	';
			    	if( $item['ITEM_EXTRA'] == 'increment' && intval($item['INCREMENT']) > 0 ) {
			    		$this->report_form['layout'] .= '
	<script language="JavaScript" type="text/javascript"><!--
		$(document).ready( function () {
			$("#ODO_NOW_'.$item['ITEM_CODE'].'").on( "keyup", function () {
				var num = parseInt($("#ODO_NOW_'.$item['ITEM_CODE'].'").val());
				if( isNaN(num) ) num = 0;
				$("#ODO_NEXT_'.$item['ITEM_CODE'].'").val(num + '.intval($item['INCREMENT']).');
			});

		});
	//--></script>
			    		';
			    	}
			    	break;

			    case 'hours': //! SCR# 522 - hours
			    	//! SCR# 556 - restrict length to column size
			    	$this->report_fields['HOURS_NOW_'.$item['ITEM_CODE']] = array( 'label' => 'hours', 'format' => 'text', 'align' => 'right', 'length' => $this->insp_report_item_table->get_max_length( 'HOURS_NOW' ) );
			    	$this->report_values['HOURS_NOW_'.$item['ITEM_CODE']] = $item['HOURS_NOW'];
			    	
			    	$this->report_fields['HOURS_NEXT_'.$item['ITEM_CODE']] = array( 'label' => 'hours', 'format' => 'text', 'align' => 'right', 'length' => $this->insp_report_item_table->get_max_length( 'HOURS_NEXT' ) );
			    	$this->report_values['HOURS_NEXT_'.$item['ITEM_CODE']] = $item['HOURS_NEXT'];
			    	
			    	$this->report_form['layout'] .= '
			    		<div class="col-sm-12 tighter">
				    		<div class="form-group">
								<label for="HOURS_NOW_'.$item['ITEM_CODE'].'" class="col-sm-3 control-label">'.$item['ITEM_TEXT'].(empty($item['ITEM_HELP']) ? '' : ' <span class="glyphicon glyphicon-info-sign inform" data-content="'.$item['ITEM_HELP'].'"></span>').'</label>
								<div class="col-sm-3">
									%HOURS_NOW_'.$item['ITEM_CODE'].'%
								</div>
								<label for="HOURS_NEXT_'.$item['ITEM_CODE'].'" class="col-sm-3 control-label">Next '.$item['ITEM_TEXT'].(empty($item['ITEM_HELP']) ? '' : ' <span class="glyphicon glyphicon-info-sign inform" data-content="'.$item['ITEM_HELP'].'"></span>').'</label>
								<div class="col-sm-3">
									%HOURS_NEXT_'.$item['ITEM_CODE'].'%
								</div>
				    		</div>
			    		</div>
			    	';
			    	if( $item['ITEM_EXTRA'] == 'increment' && intval($item['INCREMENT']) > 0 ) {
			    		$this->report_form['layout'] .= '
	<script language="JavaScript" type="text/javascript"><!--
		$(document).ready( function () {
			$("#HOURS_NOW_'.$item['ITEM_CODE'].'").on( "keyup", function () {
				var num = parseInt($("#HOURS_NOW_'.$item['ITEM_CODE'].'").val());
				if( isNaN(num) ) num = 0;
				$("#HOURS_NEXT_'.$item['ITEM_CODE'].'").val(num + '.intval($item['INCREMENT']).');
			});

		});
	//--></script>
			    		';
			    	}
			    	break;

			    case 'cost': //! SCR# 522 - cost
			    	$this->report_fields['COST_'.$item['ITEM_CODE']] = array( 'label' => $item['ITEM_TEXT'], 'format' => 'text', 'align' => 'right' );
			    	$this->report_values['COST_'.$item['ITEM_CODE']] = $item['COST'];
			    	
			    	$this->report_form['layout'] .= '
			    		<div class="form-group">
							<label for="COST_'.$item['ITEM_CODE'].'" class="col-sm-3 control-label">'.$item['ITEM_TEXT'].(empty($item['ITEM_HELP']) ? '' : ' <span class="glyphicon glyphicon-info-sign inform" data-content="'.$item['ITEM_HELP'].'"></span>').'</label>
							<div class="col-sm-3">
								<div class="input-group">
									<span class="input-group-addon">$</span>
									<input class="form-control text-right" name="COST_'.$item['ITEM_CODE'].'" id="COST_'.$item['ITEM_CODE'].'"" type="text" value="'.$item['COST'].'">
								</div>
				
							</div>
			    		</div>
			    	';
			    	break;

			    case 'serial': //! SCR# 522 - serial
			    	//! SCR# 556 - restrict length to column size
			    	$this->report_fields['SERIAL_'.$item['ITEM_CODE']] = array( 'label' => $item['ITEM_TEXT'], 'format' => 'text', 'align' => 'right', 'length' => $this->insp_report_item_table->get_max_length( 'SERIAL_NUM' ) );
			    	$this->report_values['SERIAL_'.$item['ITEM_CODE']] = $item['SERIAL_NUM'];
			    	
			    	$this->report_form['layout'] .= '
			    		<div class="form-group">
							<label for="SERIAL_'.$item['ITEM_CODE'].'" class="col-sm-3 control-label">'.$item['ITEM_TEXT'].(empty($item['ITEM_HELP']) ? '' : ' <span class="glyphicon glyphicon-info-sign inform" data-content="'.$item['ITEM_HELP'].'"></span>').'</label>
							<div class="col-sm-3">
								%SERIAL_'.$item['ITEM_CODE'].'%
							</div>
			    		</div>
			    	';
			    	break;

			    case 'text': //! SCR# 522 - text
			    	//! SCR# 556 - restrict length to column size
			    	$this->report_fields['COMMENTS_'.$item['ITEM_CODE']] = array( 'label' => 'Text', 'format' => 'text', 'length' => $this->insp_report_item_table->get_max_length( 'COMMENTS' ) );
			    	$this->report_values['COMMENTS_'.$item['ITEM_CODE']] = $item['COMMENTS'];
			    	
			    	$this->report_form['layout'] .= '
			    		<div class="form-group">
							<label for="CHECK_'.$item['ITEM_CODE'].'" class="col-sm-6 control-label">'.$item['ITEM_TEXT'].(empty($item['ITEM_HELP']) ? '' : ' <span class="glyphicon glyphicon-info-sign inform" data-content="'.$item['ITEM_HELP'].'"></span>').'</label>
							<div class="col-sm-6">
								<div class="form-group tighter">
									<div class="col-sm-12">
										%COMMENTS_'.$item['ITEM_CODE'].'%
									</div>
								</div>
				    		</div>
				    	</div>
			    	';
			    	break;

			    case 'driver': //! SCR# 522 - driver
			    	$this->report_fields['DRIVER_'.$item['ITEM_CODE']] = array( 'label' => 'Driver',
				    	'format' => 'table', 'table' => DRIVER_TABLE, 'key' => 'DRIVER_CODE',
				    	'fields' => 'FIRST_NAME,LAST_NAME', 'nolink' => true );
			    	$this->report_values['DRIVER_'.$item['ITEM_CODE']] = $item['DRIVER'];
			    	
			    	$this->report_form['layout'] .= '
			    		<div class="form-group">
							<label for="CHECK_'.$item['ITEM_CODE'].'" class="col-sm-6 control-label">'.$item['ITEM_TEXT'].(empty($item['ITEM_HELP']) ? '' : ' <span class="glyphicon glyphicon-info-sign inform" data-content="'.$item['ITEM_HELP'].'"></span>').'</label>
							<div class="col-sm-6">
								<div class="form-group tighter">
									<div class="col-sm-12">
										%DRIVER_'.$item['ITEM_CODE'].'%
									</div>
								</div>
				    		</div>
				    	</div>
			    	';
			    	break;

			    case 'tractor': //! SCR# 522 - tractor
			    	$this->report_fields['TRACTOR_'.$item['ITEM_CODE']] = array( 'label' => 'Tractor',
				    	'format' => 'table', 'table' => TRACTOR_TABLE, 'key' => 'TRACTOR_CODE',
				    	'fields' => 'UNIT_NUMBER', 'nolink' => true );
			    	$this->report_values['TRACTOR_'.$item['ITEM_CODE']] = $item['TRACTOR'];
			    	
			    	$this->report_form['layout'] .= '
			    		<div class="form-group">
							<label for="CHECK_'.$item['ITEM_CODE'].'" class="col-sm-6 control-label">'.$item['ITEM_TEXT'].(empty($item['ITEM_HELP']) ? '' : ' <span class="glyphicon glyphicon-info-sign inform" data-content="'.$item['ITEM_HELP'].'"></span>').'</label>
							<div class="col-sm-6">
								<div class="form-group tighter">
									<div class="col-sm-12">
										%TRACTOR_'.$item['ITEM_CODE'].'%
									</div>
								</div>
				    		</div>
				    	</div>
			    	';
			    	break;

			    case 'trailer': //! SCR# 522 - trailer
			    	$this->report_fields['TRAILER_'.$item['ITEM_CODE']] = array( 'label' => 'Trailer',
				    	'format' => 'table', 'table' => TRAILER_TABLE, 'key' => 'TRAILER_CODE',
				    	'fields' => 'UNIT_NUMBER', 'nolink' => true );
			    	$this->report_values['TRAILER_'.$item['ITEM_CODE']] = $item['TRAILER'];
			    	
			    	$this->report_form['layout'] .= '
			    		<div class="form-group">
							<label for="CHECK_'.$item['ITEM_CODE'].'" class="col-sm-6 control-label">'.$item['ITEM_TEXT'].(empty($item['ITEM_HELP']) ? '' : ' <span class="glyphicon glyphicon-info-sign inform" data-content="'.$item['ITEM_HELP'].'"></span>').'</label>
							<div class="col-sm-6">
								<div class="form-group tighter">
									<div class="col-sm-12">
										%TRAILER_'.$item['ITEM_CODE'].'%
									</div>
								</div>
				    		</div>
				    	</div>
			    	';
			    	break;

			    default:
			    	break;
		    }
	    }
    }
    
    private function process_form_tires( $item_code, $location ) {
		global $_POST;
	    
	    $check = $this->insp_report_tires_table->fetch_rows(
	    	"ITEM_CODE = ".$item_code." AND TIRE_LOCATION = '".$location."'");
	    	
	    if( is_array($check) && count($check) == 1 ) {	// Exists, update
	    	$changes = array("TREAD" => $_POST['TREAD_'.$location.$item_code],
	    		"TLIFE" => $_POST['TLIFE_'.$location.$item_code]);
	    	if( ! empty($_POST['PSI_'.$location.$item_code]))
	    		$changes["PSI"] = $_POST['PSI_'.$location.$item_code];
	    	if( ! empty($_POST['PSI2_'.$location.$item_code]))
	    		$changes["PSI2"] = $_POST['PSI2_'.$location.$item_code];
	    	if( ! empty($_POST['TNOTE_'.$location.$item_code]))
	    		$changes["TNOTE"] = $_POST['TNOTE_'.$location.$item_code];
		    $this->insp_report_tires_table->update($check[0]["TIRE_CODE"], $changes);
	    } else {	// not exists, add
		    $changes = array("ITEM_CODE" => $item_code,
		    	"TIRE_LOCATION" => $location,
		    	"TREAD" => $_POST['TREAD_'.$location.$item_code],
	    		"TLIFE" => $_POST['TLIFE_'.$location.$item_code]);
	    	if( ! empty($_POST['PSI_'.$location.$item_code]))
	    		$changes["PSI"] = $_POST['PSI_'.$location.$item_code];
	    	if( ! empty($_POST['PSI2_'.$location.$item_code]))
	    		$changes["PSI2"] = $_POST['PSI2_'.$location.$item_code];
	    	if( ! empty($_POST['TNOTE_'.$location.$item_code]))
	    		$changes["TNOTE"] = $_POST['TNOTE_'.$location.$item_code];
		    $this->insp_report_tires_table->add($changes);
	    }
    }
    
    //! This processes an item for an inspection report form
    private function process_form_item( $item_code, $item_type, $unit_type ) {
		global $_POST;
	    
	    if( $this->debug ) echo "<p>".__METHOD__.": $item_code, $item_type, $unit_type</p>";

	    switch( $item_type ) {
		    case 'check': //! check
		    	$changes = array("CHECK_STATUS" => $_POST['CHECK_'.$item_code]);
		    	if( ! empty($_POST['COMMENTS_'.$item_code]))
		    		$changes["COMMENTS"] = $_POST['COMMENTS_'.$item_code];
	    		if( isset($_POST['BRAKES_'.$item_code]) ) {
		    		$changes["BRAKES_STATUS"] = $_POST['BRAKES_'.$item_code];
		    	}
	    		if( isset($_POST['NEXT_DUE_'.$item_code]) ) {
		    		$changes["NEXT_DUE"] = date("Y-m-d", strtotime($_POST['NEXT_DUE_'.$item_code]));
		    	}
		    		
		    	$this->insp_report_item_table->update($item_code, $changes );
		    	break;

		    case 'action': //! action
		    	$changes = array("ACTION_STATUS" => $_POST['ACTION_'.$item_code]);
	    		if( isset($_POST['NEXT_DUE_'.$item_code]) ) {
		    		$changes["NEXT_DUE"] = date("Y-m-d", strtotime($_POST['NEXT_DUE_'.$item_code]));
		    	}
		    	$this->insp_report_item_table->update($item_code, $changes );
		    	break;

			case 'damage': //! damage
				$changes = array();
				if( isset($_POST['DAMAGE_'.$item_code]) &&
					is_array($_POST['DAMAGE_'.$item_code]) &&
					count($_POST['DAMAGE_'.$item_code]) > 0 ) {
					$changes["DAMAGE"] = implode(',', $_POST['DAMAGE_'.$item_code]);
				} else {
					$changes["DAMAGE"] = '';
				}
		    	if( ! empty($_POST['COMMENTS_'.$item_code]))
		    		$changes["COMMENTS"] = $_POST['COMMENTS_'.$item_code];
		    	if( count($changes) > 0 )
		    		$this->insp_report_item_table->update($item_code, $changes );
		    	break;

		    case 'tires': //! tires
		    	$changes = array("TIRES_DUALIES" => (isset($_POST['TIRES_DUALIES_'.$item_code]) ? 1 : 0) );
		    	$this->insp_report_item_table->update($item_code, $changes );

		    	if( $unit_type == 'tractor' ) {
			    	$this->process_form_tires( $item_code, 'STL_' );
			    	$this->process_form_tires( $item_code, 'STR_' );

			    	$this->process_form_tires( $item_code, 'OML_' );
			    	$this->process_form_tires( $item_code, 'IML_' );
			    	$this->process_form_tires( $item_code, 'IMR_' );
			    	$this->process_form_tires( $item_code, 'OMR_' );

			    	$this->process_form_tires( $item_code, 'ORL_' );
			    	$this->process_form_tires( $item_code, 'IRL_' );
			    	$this->process_form_tires( $item_code, 'IRR_' );
			    	$this->process_form_tires( $item_code, 'ORR_' );
		    	} else {
			    	$this->process_form_tires( $item_code, 'OFL_' );
			    	$this->process_form_tires( $item_code, 'IFL_' );
			    	$this->process_form_tires( $item_code, 'IFR_' );
			    	$this->process_form_tires( $item_code, 'OFR_' );

			    	$this->process_form_tires( $item_code, 'ORL_' );
			    	$this->process_form_tires( $item_code, 'IRL_' );
			    	$this->process_form_tires( $item_code, 'IRR_' );
			    	$this->process_form_tires( $item_code, 'ORR_' );
		    	}
		    	break;

			case 'parts': //! parts
				// This should match the rows in the table, just get the PART_CODE
				$parts = $this->insp_report_part_table->fetch_rows("ITEM_CODE = ".$item_code,
					"PART_CODE", "PART_CODE ASC");
					
				if( is_array($parts) ) {
					foreach( $parts as $row ) {
						$part_code = $row["PART_CODE"];
						$p = $item_code.$part_code;
						$changes = array();
				    	if( ! empty($_POST['PART_VENDOR_'.$p]))
				    		$changes["VENDOR"] = $_POST['PART_VENDOR_'.$p];
				    	if( ! empty($_POST['PART_NAME_'.$p]))
				    		$changes["PART_NAME"] = $_POST['PART_NAME_'.$p];
				    	if( ! empty($_POST['PART_DESC_'.$p]))
				    		$changes["PART_DESCRIPTION"] = $_POST['PART_DESC_'.$p];
				    	if( ! empty($_POST['PART_QTY_'.$p]))
				    		$changes["QUANTITY"] = $_POST['PART_QTY_'.$p];
				    	if( ! empty($_POST['PART_COST_'.$p]))
				    		$changes["COST"] = $_POST['PART_COST_'.$p];
				    	if( ! empty($_POST['PART_TOTAL_'.$p]))
				    		$changes["TOTAL"] = $_POST['PART_TOTAL_'.$p];
				    		
				    	if( count($changes) > 0 )
				    		$this->insp_report_part_table->update($part_code, $changes );
					}
				}				
				break;
				
			case 'notes': //! notes
				$changes = array();
		    	if( ! empty($_POST['NOTES_'.$item_code]))
		    		$changes["COMMENTS"] = $_POST['NOTES_'.$item_code];
		    	if( count($changes) > 0 )
		    		$this->insp_report_item_table->update($item_code, $changes );
				break;
				
		    case 'odometer': //! SCR# 522 - odometer
		    	$changes = array("ODO_NOW" => $_POST['ODO_NOW_'.$item_code],
		    		"ODO_NEXT" => $_POST['ODO_NEXT_'.$item_code]);
		    	$this->insp_report_item_table->update($item_code, $changes );
		    	break;

		    case 'hours': //! SCR# 522 - hours
		    	$changes = array("HOURS_NOW" => $_POST['HOURS_NOW_'.$item_code],
		    		"HOURS_NEXT" => $_POST['HOURS_NEXT_'.$item_code]);
		    	$this->insp_report_item_table->update($item_code, $changes );
		    	break;

		    case 'cost': //! SCR# 522 - cost
		    	$changes = array("COST" => $_POST['COST_'.$item_code]);
		    	$this->insp_report_item_table->update($item_code, $changes );
		    	break;

		    case 'serial': //! SCR# 522 - serial
		    	$changes = array("SERIAL_NUM" => $_POST['SERIAL_'.$item_code]);
		    	$this->insp_report_item_table->update($item_code, $changes );
		    	break;

		    case 'text': //! SCR# 522 - text
		    	$changes = array("COMMENTS" => $_POST['COMMENTS_'.$item_code]);
		    	$this->insp_report_item_table->update($item_code, $changes );
		    	break;

		    case 'driver': //! SCR# 522 - driver
		    	$changes = array("DRIVER" => $_POST['DRIVER_'.$item_code]);
		    	$this->insp_report_item_table->update($item_code, $changes );
		    	break;

		    case 'tractor': //! SCR# 522 - tractor
		    	$changes = array("TRACTOR" => $_POST['TRACTOR_'.$item_code]);
		    	$this->insp_report_item_table->update($item_code, $changes );
		    	break;

		    case 'trailer': //! SCR# 522 - trailer
		    	$changes = array("TRAILER" => $_POST['TRAILER_'.$item_code]);
		    	$this->insp_report_item_table->update($item_code, $changes );
		    	break;

		    default:
		    	break;
	    }
    }
    
    //! Call this to process a filled in inspection report form
    public function process_form() {
		global $_POST;
	    
	    if( $this->debug ) {
			echo "<p>".__METHOD__.": POST = </p>
			<pre>";
			var_dump($_POST);
			echo "</pre>";
		}
		
		//! CSRF Token check
		if( isset($_POST) && isset($_POST["CSRF"]) && $_POST["CSRF"] == str_rot13(session_id())
			&& isset($_POST["save"]) && ! empty($_POST["REPORT_CODE"])) {
			$pk = $_POST["REPORT_CODE"];
			
			// Update REPORT_DATE, NEXT_DUE
			$check = $this->fetch_rows( "REPORT_CODE = ".$pk,
				"REPORT_DATE, NEXT_DUE, ODO, NEXT_ODO, DECAL_NUMBER,
				UNIT_TYPE, UNIT,
				(SELECT FORM_NAME FROM EXP_RM_FORM WHERE FORM_CODE = RM_FORM) AS FORM_NAME, SS_REPORT");
			if( is_array($check) ) {
			    //! SCR# 522 - Form name
			    $this->form_name = $check[0]["FORM_NAME"];
		    	$unit_type = $check[0]["UNIT_TYPE"];
		    	$unit = $check[0]["UNIT"];

				$changes = array();
				$rd = date("Y-m-d", strtotime($_POST["REPORT_DATE"]));
				if( $rd <> $check[0]["REPORT_DATE"]) $changes["REPORT_DATE"] = $rd;
				if( ! empty($_POST["NEXT_DUE"]) ) {
					$nd = date("Y-m-d", strtotime($_POST["NEXT_DUE"]));
					if( $nd <> $check[0]["NEXT_DUE"]) $changes["NEXT_DUE"] = $nd;
				}
				if( $this->form_name == 'Default' ) {
					//! SCR# 507 - added DECAL_NUMBER 
					if( $check[0]["ODO"] <> $_POST["ODO"] )
						$changes["ODO"] = $_POST["ODO"];
					if( $check[0]["NEXT_ODO"] <> $_POST["NEXT_ODO"] )
						$changes["NEXT_ODO"] = $_POST["NEXT_ODO"];
					if( $check[0]["DECAL_NUMBER"] <> $_POST["DECAL_NUMBER"] )
						$changes["DECAL_NUMBER"] = $_POST["DECAL_NUMBER"];
				}
				
				$changes["SS_REPORT"] = isset($_POST["SS_REPORT"])  ? "1" : "0";
				
				if( count($changes) > 0 )
					$this->update( $pk, $changes );
					
				$this->user_log->log_event( 'inspection', 'EDIT: '.
				$unit_type.'# '.$unit.' -> '. $this->form_name.
				' report# '.$pk);
			}
			
			// Process rows of the report
			if( isset($_POST["ITEM_CODE"]) && is_array($_POST["ITEM_CODE"]) &&
				is_array($_POST["ITEM_TYPE"]) && count($_POST["ITEM_CODE"]) > 0
				&& count($_POST["ITEM_CODE"]) == count($_POST["ITEM_TYPE"]) ) {
				for($c = 0; $c < count($_POST["ITEM_CODE"]); $c++ ) {
					$this->process_form_item( $_POST["ITEM_CODE"][$c], $_POST["ITEM_TYPE"][$c],
						$_POST["UNIT_TYPE"]);
				}
			}
		}
	    if( $this->debug ) echo "<p>".__METHOD__.": exit</p>";

    }
    
    //! Based upon the form information, create a form and list fo fields
    public function create_form( $report, $referer ) {
	    $report_data = $this->fetch_rows( "REPORT_CODE = ".$report, "*" );
	    
	    if( is_array($report_data) && count($report_data) == 1 ) {
		    
		    $this->report_form = array();
		    $this->report_fields = array();
		    $this->report_values = array();
		    
		    //! SCR# 522 - Form name
		    $this->form_name = $report_data[0]["REPORT_NAME"];
		    if( $this->form_name <> 'Default' ) $this->title = $this->form_name;

		    $this->report_form['title'] = '<span class="glyphicon glyphicon-wrench"></span> '.$this->title;
		    $this->report_form['action'] = 'exp_addinsp_report.php';
		    $this->report_form['cancel'] = 'index.php';
		    $this->report_form['name'] = 'addinsp_report';
		    $this->report_form['okbutton'] = 'Save Changes';
		    $this->report_form['cancelbutton'] = 'Back';
		    
			$this->report_fields['REPORT_CODE'] = array( 'format' => 'hidden' );
		    $this->report_values['REPORT_CODE'] = $report;
		    
			$this->report_fields['UNIT_TYPE'] = array( 'format' => 'hidden' );
		    $this->report_values['UNIT_TYPE'] = $report_data[0]["UNIT_TYPE"];
		    
			$this->report_fields['UNIT'] = array( 'label' => 'Unit#', 'format' => 'table',
				'table' => ($report_data[0]["UNIT_TYPE"] == 'tractor' ? TRACTOR_TABLE : TRAILER_TABLE),
				'key' => ($report_data[0]["UNIT_TYPE"] == 'tractor' ? 'TRACTOR_CODE' : 'TRAILER_CODE'),
				'fields' => 'UNIT_NUMBER', 'static' => true, 'extras' => 'readonly' );
		    $this->report_values['UNIT'] = $report_data[0]["UNIT"];
		    
			$this->report_fields['MECHANIC'] = array( 'label' => 'Inspection By', 'format' => 'table',
				'table' => USER_TABLE, 'key' => 'USER_CODE', 'fields' => 'FULLNAME',
				'static' => true, 'extras' => 'readonly' );
		    $this->report_values['MECHANIC'] = $report_data[0]["MECHANIC"];
		    
			$this->report_fields['REPORT_DATE'] = array( 'label' => 'Report Date', 'format' => 'date' );
		    $this->report_values['REPORT_DATE'] = $report_data[0]["REPORT_DATE"];
		    
			$this->report_fields['NEXT_DUE'] = array( 'label' => 'Next Due', 'format' => 'date' );
		    $this->report_values['NEXT_DUE'] = $report_data[0]["NEXT_DUE"];
		    
			$this->report_fields['SS_REPORT'] = array( 'label' => 'Reporting', 'format' => 'bool' );
		    $this->report_values['SS_REPORT'] = $report_data[0]["SS_REPORT"];
		    
			if( $this->form_name == 'Default' ) {
				$this->report_fields['ODO'] = array( 'label' => ($report_data[0]["UNIT_TYPE"] == 'tractor' ? 'ODO' : 'HUB ODO'), 'format' => 'text' );
			    $this->report_values['ODO'] = $report_data[0]["ODO"];
			    
				$this->report_fields['NEXT_ODO'] = array( 'label' => 'Next '.($report_data[0]["UNIT_TYPE"] == 'tractor' ? 'ODO' : 'HUB ODO'), 'format' => 'text' );
			    $this->report_values['NEXT_ODO'] = $report_data[0]["NEXT_ODO"];
			    
				//! SCR# 507 - added DECAL_NUMBER
				$this->report_fields['DECAL_NUMBER'] = array( 'label' => 'Decal#', 'format' => 'text' );
			    $this->report_values['DECAL_NUMBER'] = $report_data[0]["DECAL_NUMBER"];
		    }
		    
		    $this->report_form['layout'] = '
		    	%REPORT_CODE%
		    	%UNIT_TYPE%
		    	<div class="well well-sm">
					<div class="form-group">
						<label for="UNIT" class="col-sm-2 control-label">'.ucfirst($report_data[0]["UNIT_TYPE"]).'#</label>
						<div class="col-sm-4">
							%UNIT%
						</div>
						<label for="MECHANIC" class="col-sm-2 control-label">#MECHANIC#</label>
						<div class="col-sm-4">
							%MECHANIC%
						</div>
					</div>
					<div class="form-group">
						<label for="REPORT_DATE" class="col-sm-2 control-label">#REPORT_DATE#</label>
						<div class="col-sm-4">
							%REPORT_DATE%
						</div>
						<label for="NEXT_DUE" class="col-sm-2 control-label">#NEXT_DUE#</label>
						<div class="col-sm-4">
							%NEXT_DUE%
						</div>
					</div>
					'.($this->form_name == 'Default' ? '<div class="form-group">
						<label for="ODO" class="col-sm-2 control-label">#ODO#</label>
						<div class="col-sm-4">
							%ODO%
						</div>
						<label for="NEXT_ODO" class="col-sm-2 control-label">#NEXT_ODO#</label>
						<div class="col-sm-4">
							%NEXT_ODO%
						</div>
					</div>
					<div class="form-group">
						<label for="DECAL_NUMBER" class="col-sm-2 control-label">#DECAL_NUMBER#</label>
						<div class="col-sm-4">
							%DECAL_NUMBER%
						</div>
					</div>
					' : '').'
					<div class="form-group">
						<label for="SS_REPORT" class="col-sm-2 control-label">#SS_REPORT#</label>
						<div class="col-sm-2">
							%SS_REPORT%
						</div>
						<div class="col-sm-4">
							<label>Include in various reports</label>
						</div>
					</div>
				</div>
		    	';
		    
			if( ! empty($referer) && $referer <> 'unknown' ) {
				$this->report_form['cancel'] = $referer;
				$this->report_form['layout'] .= '<input name="REFERER" type="hidden" value="'.$referer.'">
				';
			}

		    if( $this->debug ) {
				$this->report_form['layout'] .= '<input name="debug" type="hidden" value="on">
				';
			}

	    	$item_data = $this->insp_report_item_table->fetch_rows( "REPORT_CODE = ".$report );
			
			if( is_array($item_data) && count($item_data) > 0 ) {
				$this->report_form['layout'] .= '
					<div class="well well-sm">
						<div class="form-group">
							<div class="col-sm-12">
				';
				foreach( $item_data as $item ) {
					$this->layout( $item );
				}
				$this->report_form['layout'] .= '
							</div>
						</div>
					</div>
					';
			}
			

			/*<div class="form-group">
				<label for="ITEM_TARGET" class="col-sm-2 control-label">#ITEM_TARGET#</label>
				<div class="col-sm-2">
					%ITEM_TARGET%
				</div>
				<div class="col-sm-4">
					<label>What does this apply to</label>
				</div>
			</div>
*/
	    }
	    
	    return array( $this->report_form, $this->report_fields, $this->report_values );
    }

    
    private function ts( $item, $location ) {
	    return '<table align="center" border="2" cellspacing="0">
    		<tr>
    			<td align="right" class="text-right" style="padding: 5px;">'.
    			(isset($this->report_values['PSI_'.$location.$item["ITEM_CODE"]]) ?
    				$this->report_values['PSI_'.$location.$item["ITEM_CODE"]] : '&nbsp;').
    			'</td>
    			<td align="right" class="text-right" style="padding: 5px;">'.
    			(isset($this->report_values['PSI2_'.$location.$item["ITEM_CODE"]]) ?
    				$this->report_values['PSI2_'.$location.$item["ITEM_CODE"]] : '&nbsp;').
    			'</td>
    		</tr>
    		<tr>
    			<td colspan="2" align="right" class="text-right" style="padding: 5px;">'.
    			(isset($this->report_values['TREAD_'.$location.$item["ITEM_CODE"]]) ?
    				$this->report_values['TREAD_'.$location.$item["ITEM_CODE"]] : '&nbsp;').
    			'</td>
    		</tr>
    		<tr>
    			<td colspan="2" align="right" class="text-right" style="padding: 5px;">'.
    			(isset($this->report_values['TLIFE_'.$location.$item["ITEM_CODE"]]) ?
    				$this->report_values['TLIFE_'.$location.$item["ITEM_CODE"]] : '&nbsp;').
    			'</td>
    		</tr>
     		<tr>
    			<td colspan="2" align="right" class="text-right" style="padding: 5px;">'.
    			(isset($this->report_values['TNOTE_'.$location.$item["ITEM_CODE"]]) ?
    				$this->report_values['TNOTE_'.$location.$item["ITEM_CODE"]] : '&nbsp;').
    			'</td>
    		</tr>
   		</table>
    		';	    
    }
    
    private function render_report_item( $item, $unit_type ) {
	    $output = '';
	    
	    if( isset($item['ITEM_TYPE'])) {
		    switch( $item['ITEM_TYPE'] ) {
			    case 'check': //! check
				    $output = '<tr>
				    	<tr>
				    		<td align="center" class="text-center" style="padding: 5px;">'.str_replace(' ', '&nbsp;', $item['CHECK_STATUS']).'</td>
				    		<td style="padding: 5px;">'.$item['ITEM_TEXT'].'</td>
				    		<td style="padding: 5px;">'.($item['ITEM_EXTRA'] == 'brakes' ? '<strong>Brakes: '.$item['BRAKES_STATUS'].'</strong> ' : '').($item['ITEM_EXTRA'] == 'next due' ? '<strong>Next Due: '.date("m/d/Y", strtotime($item['NEXT_DUE'])).'</strong> ' : '').$item['COMMENTS'].'</td>
				    	</tr>
				    ';
			    	break;
				case 'action': //! action
				    $output = '<tr>
				    	<tr>
				    		<td align="center" class="text-center" style="padding: 5px;">'.str_replace(' ', '&nbsp;', $item['ACTION_STATUS']).'</td>
				    		<td style="padding: 5px;">'.$item['ITEM_TEXT'].'</td>
				    		<td style="padding: 5px;">'.($item['ITEM_EXTRA'] == 'next due' ? '<strong>Next Due: '.date("m/d/Y", strtotime($item['NEXT_DUE'])).'</strong> ' : '').'</td>
				    	</tr>
				    ';
			    	break;
			    case 'group': //! group
				    $output = '<tr>
				    	<tr>
				    		<td colspan="2" style="padding: 5px;"><strong>'.$item['ITEM_TEXT'].'</strong></td>
				    		<td style="padding: 5px;"></td>
				    	</tr>
				    ';
			    	break;
			    case 'damage': //! damage
			    	//! SCR# 537 - damage for tractors too
				    $tp = $unit_type;
				    $damage = explode(',', $item['DAMAGE']);
			    	$output = '<center>
			    	<h3 style="text-align: center; margin-bottom: 0px; margin-top: 10px;">Body Condition</h3>
			    	<br>
			    	<table align="center" border="0" cellspacing="0">
				    	<tr>
					    	<td><img '.(in_array($tp.'_ls', $damage) ? 'style="border: 6px solid #8B0000;" ' : '').'src="images/'.$tp.'_ls.png" alt="trailer_ls" width="205" height="100" /></td>
					    	<td><img '.(in_array($tp.'_fr', $damage) ? 'style="border: 6px solid #8B0000;" ' : '').'src="images/'.$tp.'_fr.png" alt="trailer_fr" width="79" height="100" /></td>
					    	<td><img '.(in_array($tp.'_rs', $damage) ? 'style="border: 6px solid #8B0000;" ' : '').'src="images/'.$tp.'_rs.png" alt="trailer_rs" width="202" height="100" /></td>
				    	</tr>
				    	<tr>
					    	<td><img '.(in_array($tp.'_tp', $damage) ? 'style="border: 6px solid #8B0000;" ' : '').'src="images/'.$tp.'_tp.png" alt="trailer_tp" width="205" height="87" /></td>
					    	<td><img '.(in_array($tp.'_re', $damage) ? 'style="border: 6px solid #8B0000;" ' : '').'src="images/'.$tp.'_re.png" alt="trailer_re" width="79" height="87" /></td>
					    	<td>'.($tp=='trailer' ? '<img '.(in_array('trailer_fl', $damage) ? 'style="border: 6px solid #8B0000;" ' : '').'src="images/trailer_fl.png" alt="trailer_fl" width="202" height="87" />' : '').'</td>
				    	</tr>
				    </table>
				    <br>
				    <p><strong>REMARKS:</strong> '.$item['COMMENTS'].'</p>
				    </center>
				    <br>
				    ';
			    	break;

			    case 'tires': //! tires
				    $this->get_tire_values( $item['ITEM_CODE'] );
				    if( $unit_type == 'tractor' ) {
						$output = '<center>
				    	<hr>
				    	<h3 style="text-align: center; margin-bottom: 0px; margin-top: 10px;">Tractor Tires</h3>
						<br>
				    	<table align="center" border="0" cellspacing="0">
					    	<tr>
					    		<td>'.$this->ts( $item, 'STL_' ).'</td>
					    		<td><img src="images/tire1L.png" alt="tire1L" height="140"></td>
					    		<td colspan="3" align="center" class="text-center"><img src="images/bar.png" alt="bar" height="140"></td>
					    		<td><img src="images/tire1R.png" alt="tire1R" height="140"></td>
					    		<td>'.$this->ts( $item, 'STR_' ).'</td>
					    	</tr>
					    	<tr>
					    		<td>'.$this->ts( $item, 'OML_' ).'</td>
					    		'.($item['TIRES_DUALIES'] ? '<td><img src="images/tire2.png" alt="tire2" height="140"></td>
					    		<td>'.$this->ts( $item, 'IML_' ).'</td>
					    		<td><img src="images/bar.png" alt="bar" height="140"></td>
					    		<td>'.$this->ts( $item, 'IMR_' ).'</td>
					    		<td><img src="images/tire2.png" alt="tire2" height="140"></td>' : 
					    		
					    		'<td><img src="images/tire1L.png" alt="tire1L" height="140"></td>
					    		<td colspan="3" align="center" class="text-center"><img src="images/bar.png" alt="bar" height="140"></td>
					    		<td><img src="images/tire1R.png" alt="tire1R" height="140"></td>').'
					    		<td>'.$this->ts( $item, 'OMR_' ).'</td>
					    	</tr>
					    	<tr>
					    		<td>'.$this->ts( $item, 'ORL_' ).'</td>
					    		'.($item['TIRES_DUALIES'] ? '<td><img src="images/tire2.png" alt="tire2" height="140"></td>
					    		<td>'.$this->ts( $item, 'IRL_' ).'</td>
					    		<td><img src="images/bar.png" alt="bar" height="140"></td>
					    		<td>'.$this->ts( $item, 'IRR_' ).'</td>
					    		<td><img src="images/tire2.png" alt="tire2" height="140"></td>' : 
					    		
					    		'<td><img src="images/tire1L.png" alt="tire1L" height="140"></td>
					    		<td colspan="3" align="center" class="text-center"><img src="images/bar.png" alt="bar" height="140"></td>
					    		<td><img src="images/tire1R.png" alt="tire1R" height="140"></td>').'
					    		<td>'.$this->ts( $item, 'ORR_' ).'</td>
					    	</tr>
					    </table>
					    </center>
					    <br>
					    ';
				    } else {
						$output = '<center>
				    	<hr>
				    	<h3 style="text-align: center; margin-bottom: 0px; margin-top: 10px;">Trailer Tires</h3>
						<br>
				    	<table align="center" border="0" cellspacing="0">
					    	<tr valign="top">
					    		<td>'.$this->ts( $item, 'OFL_' ).'</td>
					    		'.($item['TIRES_DUALIES'] ? '<td><img src="images/tire2.png" alt="tire2"></td>
					    		<td>'.$this->ts( $item, 'IFL_' ).'</td>
					    		<td><img src="images/bar.png" alt="bar"></td>
					    		<td>'.$this->ts( $item, 'IFR_' ).'</td>
					    		<td><img src="images/tire2.png" alt="tire2"></td>' : 
					    		
					    		'<td><img src="images/tire1L.png" alt="tire1L" height="140"></td>
					    		<td colspan="3" align="center" class="text-center"><img src="images/bar.png" alt="bar" height="140"></td>
					    		<td><img src="images/tire1R.png" alt="tire1R" height="140"></td>').'
					    		<td>'.$this->ts( $item, 'OFR_' ).'</td>
					    	</tr>
					    	<tr valign="top">
					    		<td>'.$this->ts( $item, 'ORL_' ).'</td>
					    		'.($item['TIRES_DUALIES'] ? '<td><img src="images/tire2.png" alt="tire2"></td>
					    		<td>'.$this->ts( $item, 'IRL_' ).'</td>
					    		<td><img src="images/bar.png" alt="bar"></td>
					    		<td>'.$this->ts( $item, 'IRR_' ).'</td>
					    		<td><img src="images/tire2.png" alt="tire2"></td>' : 
					    		
					    		'<td><img src="images/tire1L.png" alt="tire1L" height="140"></td>
					    		<td colspan="3" align="center" class="text-center"><img src="images/bar.png" alt="bar" height="140"></td>
					    		<td><img src="images/tire1R.png" alt="tire1R" height="140"></td>').'
					    		<td>'.$this->ts( $item, 'ORR_' ).'</td>
					    	</tr>
					    </table>
					    </center>
					    <br>
					    ';
				    }
			    	break;
			    case 'parts': //! parts
			    	$parts = $this->insp_report_part_table->fetch_rows("ITEM_CODE = ".$item['ITEM_CODE'],
						"SUM(TOTAL) AS TOTAL");
					if( is_array($parts) && count($parts) == 1 && isset($parts[0]["TOTAL"]))
						$gtotal = number_format($parts[0]["TOTAL"], 2, ".", "");
					else
						$gtotal = '0.00';
					
				    //! SCR# 596 - R&M - Repair notes - title does not appear
					$output = '<style>
.form-control[disabled], .form-control[readonly], fieldset[disabled] .form-control {
	background-color: #fff;
}
					</style>					
					<center>
			    	<hr>
			    	<h3 style="text-align: center; margin-bottom: 0px; margin-top: 10px;">'.(empty($item['ITEM_TEXT']) ? 'Notes' : $item['ITEM_TEXT']).'</h3>
					<br>
					<table id="part_table" width="98%" align="center" border="2" cellspacing="0">
					<thead>
					<tr valign="top" style="background-color: #4d8e31; color: #fff;">
						<th align="center" class="text-center" style="padding: 5px;" width="10%">Vendor</th>
						<th align="center" class="text-center" style="padding: 5px;" width="10%">Name</th>
						<th align="center" class="text-center" style="padding: 5px;" width="50%">Description</th>
						<th align="center" class="text-center" style="padding: 5px;" width="10%">Quantity</th>
						<th align="center" class="text-center" style="padding: 5px;" width="10%">Cost</th>
						<th align="center" class="text-center" style="padding: 5px;" width="10%">Total</th>
					</tr>
					</thead><tbody>
					'.$this->get_part_values( $item['ITEM_CODE'], true ).'
					</tbody><tfoot>
					<tr>
						<td colspan="4"><span style="float: right; padding: 5px;"><strong>Total:</strong></span></td>
						<td class="text-right"><input class="form-control text-right grdtot"
							type="text" value="'.$gtotal.'" readonly></td>
					</tr>
					</tfoot>
					</table>
				    <br>
				    ';
			    	break;
			    	
			    case 'notes': //! notes
				    //! SCR# 596 - R&M - Repair notes - title does not appear
					$output = '<center>
			    	<hr>
			    	<h3 style="text-align: center; margin-bottom: 0px; margin-top: 10px;">'.(empty($item['ITEM_TEXT']) ? 'Notes' : $item['ITEM_TEXT']).'</h3>
					<br>
			    	<table width="98%" align="center" border="2" cellspacing="0">
				    	<tr>
				    		<td style="padding: 5px;"><p>'.$item['COMMENTS'].'</p></td>
				    	</tr>
				    </table>
				    <br>
				    ';
			    	break;
			    				    
				case 'odometer': //! SCR# 522 - odometer
				    $output = '<tr>
				    	<tr>
				    		<td style="padding: 5px;">'.$item['ITEM_TEXT'].'</td>
				    		<td style="padding: 5px;" align="right">'.$item['ODO_NOW'].'</td>
				    		<td style="padding: 5px;">Next '.$item['ITEM_TEXT'].'</td>
				    		<td style="padding: 5px;" align="right">'.$item['ODO_NEXT'].'</td>
				    	</tr>
				    ';
			    	break;

				case 'hours': //! SCR# 522 - hours
				    $output = '<tr>
				    	<tr>
				    		<td style="padding: 5px;">'.$item['ITEM_TEXT'].'</td>
				    		<td style="padding: 5px;" align="right">'.$item['HOURS_NOW'].'</td>
				    		<td style="padding: 5px;">Next '.$item['ITEM_TEXT'].'</td>
				    		<td style="padding: 5px;" align="right">'.$item['HOURS_NEXT'].'</td>
				    	</tr>
				    ';
			    	break;

			    case 'cost': //! SCR# 522 - cost
				    $output = '<tr>
				    	<tr>
				    		<td align="center" class="text-center" style="padding: 5px;"></td>
				    		<td style="padding: 5px;">'.$item['ITEM_TEXT'].'</td>
				    		<td style="padding: 5px;" align="right">$'.$item['COST'].'</td>
				    	</tr>
				    ';
			    	break;

			    case 'serial': //! SCR# 522 - serial
				    $output = '<tr>
				    	<tr>
				    		<td align="center" class="text-center" style="padding: 5px;"></td>
				    		<td style="padding: 5px;">'.$item['ITEM_TEXT'].'</td>
				    		<td style="padding: 5px;" align="right">'.$item['SERIAL_NUM'].'</td>
				    	</tr>
				    ';
			    	break;

			    case 'text': //! SCR# 522 - text
				    $output = '<tr>
				    	<tr>
				    		<td align="center" class="text-center" style="padding: 5px;"></td>
				    		<td style="padding: 5px;">'.$item['ITEM_TEXT'].'</td>
				    		<td style="padding: 5px;">'.$item['COMMENTS'].'</td>
				    	</tr>
				    ';
			    	break;

			    case 'driver': //! SCR# 522 - driver
				    if( isset($item['DRIVER'])) {
					    $check = $this->database->get_one_row("SELECT concat_ws( ' ', FIRST_NAME , LAST_NAME ) AS NAME
					    	FROM EXP_DRIVER WHERE DRIVER_CODE = ".$item['DRIVER']);
					    
					    if( is_array($check) && isset($check["NAME"]))
					    	$output = '<tr>
					    	<tr>
					    		<td align="center" class="text-center" style="padding: 5px;"></td>
					    		<td style="padding: 5px;">'.$item['ITEM_TEXT'].'</td>
					    		<td style="padding: 5px;">'.$check["NAME"].'</td>
					    	</tr>
					    ';
				    }
			    	break;

			    case 'tractor': //! SCR# 522 - tractor
				    if( isset($item['TRACTOR'])) {
					    $check = $this->database->get_one_row("SELECT UNIT_NUMBER
					    	FROM EXP_TRACTOR WHERE TRACTOR_CODE = ".$item['TRACTOR']);
					    
					    if( is_array($check) && isset($check["UNIT_NUMBER"]))
					    	$output = '<tr>
					    	<tr>
					    		<td align="center" class="text-center" style="padding: 5px;"></td>
					    		<td style="padding: 5px;">'.$item['ITEM_TEXT'].'</td>
					    		<td style="padding: 5px;">'.$check["UNIT_NUMBER"].'</td>
					    	</tr>
					    ';
				    }
			    	break;

			    case 'trailer': //! SCR# 522 - trailer
				    if( isset($item['TRAILER'])) {
					    $check = $this->database->get_one_row("SELECT UNIT_NUMBER
					    	FROM EXP_TRAILER WHERE TRAILER_CODE = ".$item['TRAILER']);
					    
					    if( is_array($check) && isset($check["UNIT_NUMBER"]))
					    	$output = '<tr>
					    	<tr>
					    		<td align="center" class="text-center" style="padding: 5px;"></td>
					    		<td style="padding: 5px;">'.$item['ITEM_TEXT'].'</td>
					    		<td style="padding: 5px;">'.$check["UNIT_NUMBER"].'</td>
					    	</tr>
					    ';
				    }
			    	break;

			    default:
			    	break;
			}
		}
		
		return $output;
    }
    
    //! Create printable/emailable version
    public function render_report( $report ) {
	    $output = '';
	    $report_data = $this->fetch_rows( "REPORT_CODE = ".$report, "*,
	    	CASE WHEN UNIT_TYPE = 'tractor' THEN
		    	(SELECT UNIT_NUMBER FROM EXP_TRACTOR
		    		WHERE TRACTOR_CODE = EXP_INSP_REPORT.UNIT)
	    	ELSE
		    	(SELECT UNIT_NUMBER FROM EXP_TRAILER
		    		WHERE TRAILER_CODE = EXP_INSP_REPORT.UNIT)
		    END AS UNIT_NUMBER,
		    (SELECT FULLNAME FROM EXP_USER
		    WHERE USER_CODE = EXP_INSP_REPORT.MECHANIC) AS FULLNAME
		    " );
	    if( is_array($report_data) && count($report_data) == 1 ) {
		    //! SCR# 522 - Form name
		    $this->form_name = $report_data[0]["REPORT_NAME"];
		    if( $this->form_name <> 'Default' ) $this->title = $this->form_name;

		    $logo = $this->setting_table->get( 'company', 'LOGO' );
		    $report_date = date("m/d/Y", strtotime($report_data[0]["REPORT_DATE"]));
		    $next_due = empty($report_data[0]["NEXT_DUE"]) ? '' : date("m/d/Y", strtotime($report_data[0]["NEXT_DUE"]));
		    $unit_type = ucfirst($report_data[0]["UNIT_TYPE"]);
		    $unit_number = $report_data[0]["UNIT_NUMBER"];
		    $mechanic = $report_data[0]["FULLNAME"];
		    $odo = $report_data[0]["ODO"];
		    $next_odo = $report_data[0]["NEXT_ODO"];
		    //! SCR# 507 - added DECAL_NUMBER
		    $decal_number = $report_data[0]["DECAL_NUMBER"];
		    $odo_type = ($report_data[0]["UNIT_TYPE"] == 'tractor' ? 'Odometer' : 'HUB Odometer');
		    
		    $output .= '<table width="98%" align="center" border="2" cellspacing="0">
	<tr valign="top">
		<td width="100%">
			
			<table width="98%" align="center" border="0" cellspacing="0">
				<tr valign="top">
					<td>
						<img src="'.$logo.'" style="padding: 5px;">
					</td>
					<td>
						<h2 style="margin-bottom: 0px; margin-top: 10px;">'.$this->title.'# '.$report.'</h2>
						<h3 style="margin-top: 10px;">'.$unit_type.'#: '.$unit_number.'</h3>'.(empty($decal_number) ? '' : '
						<h4 style="margin-top: 10px;">Decal#: '.$decal_number.'</h4>').'
					</td>
					<td>
						<p style="text-align: right; style="padding: 5px;">Date: <strong>'.$report_date.'</strong></p>
						'.(empty($next_due) ? '' :
						'<p style="text-align: right; style="padding: 5px;">Next Due: <strong>'.$next_due.'</strong></p>').'
						'.(empty($odo) ? '' :
						'<p style="text-align: right; style="padding: 5px;">'.$odo_type.': <strong>'.$odo.'</strong></p>').'
						'.(empty($next_odo) ? '' :
						'<p style="text-align: right; style="padding: 5px;">Next '.$odo_type.': <strong>'.$next_odo.'</strong></p>').'
						<p style="text-align: right; style="padding: 5px;">Inspected By: <strong>'.$mechanic.'</strong></p>
					</td>
				</tr>
			</table>					
		';
		
	    	$item_data = $this->insp_report_item_table->fetch_rows( "REPORT_CODE = ".$report." AND ITEM_TYPE IN ('odometer','hours')", "*", "SEQUENCE_NO ASC" );
			
			if( is_array($item_data) && count($item_data) > 0 ) {
				$output .= '<table width="98%" align="center" border="2" cellspacing="0">
			<tr valign="top" style="background-color: #4d8e31; color: #fff;">
				<th align="center" class="text-center" style="padding: 5px;">ITEM</th>
				<th align="center" class="text-center" style="padding: 5px;">VALUE</th>
				<th align="center" class="text-center" style="padding: 5px;">ITEM</th>
				<th align="center" class="text-center" style="padding: 5px;">VALUE</th>
			</tr>
			';
				foreach( $item_data as $item ) {
					$output .= $this->render_report_item( $item, $report_data[0]["UNIT_TYPE"] );
				}
				$output .= '</table>
				<br>
				';
			}
		
	    	$item_data = $this->insp_report_item_table->fetch_rows( "REPORT_CODE = ".$report." AND ITEM_TYPE IN ('action','check','group','cost','serial', 'text', 'driver', 'tractor', 'trailer')", "*", "SEQUENCE_NO ASC" );
			
			if( is_array($item_data) && count($item_data) > 0 ) {
				$output .= '<table width="98%" align="center" border="2" cellspacing="0">
			<tr valign="top" style="background-color: #4d8e31; color: #fff;">
				<th align="center" class="text-center" style="padding: 5px;">STATUS</th>
				<th align="center" class="text-center" style="padding: 5px;">ITEM</th>
				<th align="center" class="text-center" style="padding: 5px;">COMMENTS</th>
			</tr>
			';
				foreach( $item_data as $item ) {
					$output .= $this->render_report_item( $item, $report_data[0]["UNIT_TYPE"] );
				}
				$output .= '</table>
				<br>
				';
			}
		
			
	    	$item_data2 = $this->insp_report_item_table->fetch_rows( "REPORT_CODE = ".$report." AND ITEM_TYPE IN ('damage','tires','parts','notes')", "*", "SEQUENCE_NO ASC" );
			
			if( is_array($item_data2) && count($item_data2) > 0 ) {
				foreach( $item_data2 as $item ) {
					$output .= $this->render_report_item( $item, $report_data[0]["UNIT_TYPE"] );
				}
			}
		
			
			
			$output .= '</table>
			<center><p>Delivered by <a href="http://www.exspeedite.com/" target="_blank">Exspeedite&reg;</a></p></center>
				';
		}
		
		return $output;
	}
	
	//! SCR# 522 - R&M Report
	public function render_rm_report( $unit_type, $unit_number ) {
	    $output = '';
		$details = $this->database->get_one_row("
			SELECT T.UNIT_NUMBER, C.CLASS_NAME
			FROM EXP_".strtoupper($unit_type)." T, EXP_RM_CLASS C
			WHERE T.".strtoupper($unit_type)."_CODE = $unit_number
			AND T.RM_CLASS = C.CLASS_CODE
			LIMIT 1");
		
		if( is_array($details) && isset($details["UNIT_NUMBER"]) && isset($details["CLASS_NAME"]) ) {
			$output .= '<h4>Unit ID: '.$details["UNIT_NUMBER"].'&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;R&M Class: '.$details["CLASS_NAME"].'</h4>
			';
		}
		
		$result = $this->database->get_multiple_rows("
			SELECT R.UNIT, R.UNIT_TYPE, R.RM_FORM, R.REPORT_CODE, 
				DATE_FORMAT(R.REPORT_DATE, '%m/%e/%Y') AS REPORT_DATE,
				DATE_FORMAT(R.NEXT_DUE, '%m/%e/%Y') AS NEXT_DUE,
				COALESCE(CURDATE() >= R.NEXT_DUE, 0) AS OVERDUE,
			    R.RECURRING, R.REPORT_NAME,
			(SELECT GROUP_CONCAT(CONCAT_WS(' ', I.ITEM_TEXT, I.ODO_NOW) ORDER BY I.ITEM_CODE SEPARATOR '<br>')
            FROM EXP_INSP_REPORT_ITEM I
			WHERE I.REPORT_CODE = R.REPORT_CODE
			AND I.ODO_NOW IS NOT NULL
			AND ITEM_TYPE = 'ODOMETER') AS ODOMETER,
            (SELECT GROUP_CONCAT(CONCAT_WS(' ', I.ITEM_TEXT, I.ODO_NEXT) ORDER BY I.ITEM_CODE SEPARATOR '<br>')
            FROM EXP_INSP_REPORT_ITEM I
			WHERE I.REPORT_CODE = R.REPORT_CODE
			AND I.ODO_NEXT IS NOT NULL
			AND ITEM_TYPE = 'ODOMETER') AS NEXT_ODOMETER,
			(SELECT GROUP_CONCAT(CONCAT_WS(' ', I.ITEM_TEXT, I.HOURS_NOW) ORDER BY I.ITEM_CODE SEPARATOR '<br>')
            FROM EXP_INSP_REPORT_ITEM I
			WHERE I.REPORT_CODE = R.REPORT_CODE
			AND I.HOURS_NOW IS NOT NULL
			AND ITEM_TYPE = 'HOURS') AS HOURS,
            (SELECT GROUP_CONCAT(CONCAT_WS(' ', I.ITEM_TEXT, I.HOURS_NEXT) ORDER BY I.ITEM_CODE SEPARATOR '<br>')
            FROM EXP_INSP_REPORT_ITEM I
			WHERE I.REPORT_CODE = R.REPORT_CODE
			AND I.HOURS_NEXT IS NOT NULL
			AND ITEM_TYPE = 'HOURS') AS NEXT_HOURS,
			
			(SELECT LEFT(TRIM(I.COMMENTS), 100)
            FROM EXP_INSP_REPORT_ITEM I
			WHERE I.REPORT_CODE = R.REPORT_CODE
			AND ITEM_TYPE = 'notes'
            ORDER BY SEQUENCE_NO ASC
            LIMIT 1) AS NOTES
			
			FROM EXP_INSP_REPORT R
			WHERE R.UNIT=$unit_number
			AND R.UNIT_TYPE = '".$unit_type."'
			AND R.REPORT_CODE = (SELECT R2.REPORT_CODE
				FROM EXP_INSP_REPORT R2
				WHERE R2.UNIT=R.UNIT
				AND R2.UNIT_TYPE = R.UNIT_TYPE
				AND R2.RM_FORM = R.RM_FORM
				ORDER BY R2.REPORT_DATE DESC, R2.REPORT_CODE DESC
				LIMIT 1)

			ORDER BY REPORT_DATE ASC");

		if( is_array($result) && count($result) > 0 ) {
			$output .= '<div class="table-responsive  bg-white">
			<table class="display table table-striped table-condensed table-bordered table-hover table-nobm" id="REVENUE">
			<thead><tr class="exspeedite-bg">
				<th>Form</th>
				<th>Freq</th>
				<th>Report Date</th>
				<th>Next Due</th>
				<th class="text-right">Odometer</th>
				<th class="text-right">Hours</th>
				<th>Notes</th>
			</tr>
			</thead>
			<tbody>';
			foreach($result as $row) {
				$output .= '<tr>
						<td><a href="exp_addinsp_report.php?REPORT='.$row['REPORT_CODE'].'">'.$row['REPORT_NAME'].'</a></td>
						<td>'.$row['RECURRING'].'</td>
						<td>'.$row['REPORT_DATE'].'</td>
						<td'.($row['OVERDUE'] ? ' class="bg-danger"' : '').'>'.
							($row['OVERDUE'] ? '<b>'.$row['NEXT_DUE'].'</b>' : $row['NEXT_DUE']).
							(empty($row['NEXT_ODOMETER']) ? '' : '<br>'.$row['NEXT_ODOMETER']).
							(empty($row['NEXT_HOURS']) ? '' : '<br>'.$row['NEXT_HOURS']).
							'</td>
						<td class="text-right">'.$row['ODOMETER'].'</td>
						<td class="text-right">'.$row['HOURS'].'</td>
						<td>'.$row['NOTES'].'</td>
					</tr>';
			}

			$output .= '
			   		</tbody>
				</table>
			</div>';

		}

		return $output;
	}

	//! SCR# 522 - Check expired
	public function check_expired( $unit_type, $unit_number ) {
	    $responses = array();

		$result = $this->database->get_multiple_rows("
			SELECT R.RM_FORM, R.REPORT_CODE, R.RECURRING, R.REPORT_NAME,
				DATE_FORMAT(R.REPORT_DATE, '%m/%e/%Y') AS REPORT_DATE,
				DATE_FORMAT(R.NEXT_DUE, '%m/%e/%Y') AS NEXT_DUE
			FROM EXP_INSP_REPORT R
			WHERE R.UNIT=$unit_number
			AND R.UNIT_TYPE = '".$unit_type."'
			AND EXISTS (SELECT * FROM EXP_RM_FORM WHERE FORM_CODE = R.RM_FORM)
			AND R.REPORT_CODE = (SELECT R2.REPORT_CODE
				FROM EXP_INSP_REPORT R2
				WHERE R2.UNIT=R.UNIT
				AND R2.UNIT_TYPE = R.UNIT_TYPE
				AND R2.RM_FORM = R.RM_FORM
				ORDER BY R2.REPORT_DATE DESC, R2.REPORT_CODE DESC
				LIMIT 1)
			AND COALESCE(CURDATE() >= R.NEXT_DUE, 0)

			ORDER BY REPORT_DATE ASC");

		if( is_array($result) && count($result) > 0 ) {
			foreach($result as $row) {
				$responses[] =  '<span class="text-danger"><strong><span class="glyphicon glyphicon-warning-sign"></span></strong> Overdue '.$row['REPORT_NAME'].' '.
					($row['RECURRING'] == 'none' ? '' : ' ('.$row['RECURRING'].')').' R&M report, due '.
					$row['NEXT_DUE'].'</span>';
			}
		}
			
		return $responses;
	}
}

class sts_insp_report_item extends sts_table {

	// Constructor does not need the table name
	public function __construct( $database, $debug = false ) {

		$this->debug = $debug;
		$this->primary_key = "ITEM_CODE";
		if( $this->debug ) echo "<p>Create sts_insp_report_item</p>";
		parent::__construct( $database, INSP_REPORT_ITEM_TABLE, $debug);

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

class sts_insp_report_tires extends sts_table {

	// Constructor does not need the table name
	public function __construct( $database, $debug = false ) {

		$this->debug = $debug;
		$this->primary_key = "TIRE_CODE";
		if( $this->debug ) echo "<p>Create sts_insp_report_tires</p>";
		parent::__construct( $database, INSP_REPORT_TIRES, $debug);

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

class sts_insp_report_part extends sts_table {

	// Constructor does not need the table name
	public function __construct( $database, $debug = false ) {

		$this->debug = $debug;
		$this->primary_key = "PART_CODE";
		if( $this->debug ) echo "<p>Create sts_insp_report_part</p>";
		parent::__construct( $database, INSP_REPORT_PART, $debug);

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
    
    public function add_part( $item ) {
	    $this->insp_report_part_table->add(array('ITEM_CODE' => $item_code));

    }
}

//! SCR# 617 - class for Slingshot custom report
class sts_insp_report_grid extends sts_insp_report {
	
	private $grid_layout = false;
	private $grid_edit = false;
	private $grid_bars = false;
	private $csv_toprow = false;

	// Constructor does not need the table name
	public function __construct( $database, $debug = false ) {

		if( $debug ) echo "<p>Create sts_insp_report_grid</p>";
		parent::__construct( $database, $debug);
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
    
    public function get_layout( $match = "" ) {
	    if( $this->grid_layout == false ) {
		   $this->fetch_rows($match); 
	    }
	    return $this->grid_layout;
    }

    public function get_edit( $match = "" ) {
	    if( $this->grid_edit == false ) {
		   $this->fetch_rows($match); 
	    }
	    return $this->grid_edit;
    }

    public function get_toprow( $match = "" ) {
	    if( $this->csv_toprow == false ) {
		   $this->fetch_rows($match); 
	    }
	    return $this->csv_toprow;
    }

    public function get_bars( $match = "" ) {
	    if( $this->grid_bars == false ) {
		   $this->fetch_rows($match); 
	    }
	    $output = '';
	    if( count($this->grid_bars) > 0 ) {
		    foreach( $this->grid_bars as $bar ) {
			    $output .= "$(row).find('td:nth-child(".$bar.")').css('border-left','2px solid #4d8e31');\n";
		    }
	    }
	    
	    return $output;
    }

	// Fetch one or more rows
	public function fetch_rows( $match = "", $fields = "*", $order = "", $limit = "", $groupby = "", $match2 = "" ) {
		global $_GET;
		
		if( $this->debug ) echo "<p>".__METHOD__.": $match</p>";
		$result = array();

		$this->grid_layout = array(
			"UNIT" => array( 'format' => 'hidden' ),
			"UNIT_NUMBER" => array( 'label' => 'Unit', 'format' => 'text' ),
			"UNIT_TYPE" => array( 'label' => 'Type', 'format' => 'text' ),
			"CLASS_NAME" => array( 'label' => 'Class', 'format' => 'text' )
		);
		$this->grid_bars = array( 4 );
			
		$this->grid_edit = array(
			'title' => '<span class="glyphicon glyphicon-wrench"></span> R&M Report',
			'sort' => 'UNIT asc' );
		
		//! SCR# 617 - get the raw data
		$raw = $this->database->get_multiple_rows("
			SELECT R.UNIT, R.RM_FORM,
			CASE WHEN R.UNIT_TYPE = 'tractor' THEN
				(SELECT UNIT_NUMBER FROM EXP_TRACTOR
					WHERE TRACTOR_CODE = R.UNIT)
			ELSE
				(SELECT UNIT_NUMBER FROM EXP_TRAILER
					WHERE TRAILER_CODE = R.UNIT)
			END AS UNIT_NUMBER,
			
			CASE WHEN R.UNIT_TYPE = 'tractor' THEN
				(SELECT C.CLASS_NAME FROM EXP_TRACTOR T, EXP_RM_CLASS C
					WHERE T.TRACTOR_CODE = R.UNIT
					AND T.RM_CLASS = C.CLASS_CODE)
			ELSE
				(SELECT C.CLASS_NAME FROM EXP_TRAILER T, EXP_RM_CLASS C
					WHERE T.TRAILER_CODE = R.UNIT
					AND T.RM_CLASS = C.CLASS_CODE)
			END AS CLASS_NAME,
			
			R.UNIT_TYPE, R.REPORT_NAME, R.REPORT_CODE, 
			DATE_FORMAT(R.REPORT_DATE, '%m/%e/%Y') AS REPORT_DATE,
			DATE_FORMAT(R.NEXT_DUE, '%m/%e/%Y') AS NEXT_DUE,
				
			(SELECT I.ODO_NOW
            FROM EXP_INSP_REPORT_ITEM I
			WHERE I.REPORT_CODE = R.REPORT_CODE
			AND ITEM_TYPE = 'odometer'
            AND I.ITEM_TEXT = 'ODOMETER MONTH BEGIN'
			LIMIT 1) AS ODOMETER_BEGIN,
            
			(SELECT I.ODO_NOW
            FROM EXP_INSP_REPORT_ITEM I
			WHERE I.REPORT_CODE = R.REPORT_CODE
			AND ITEM_TYPE = 'odometer'
            AND I.ITEM_TEXT = 'ODOMETER MONTH END'
            LIMIT 1) AS ODOMETER_END,
            
 			(SELECT I.HOURS_NOW
            FROM EXP_INSP_REPORT_ITEM I
			WHERE I.REPORT_CODE = R.REPORT_CODE
			AND ITEM_TYPE = 'hours'
            AND I.ITEM_TEXT = 'HOURS MONTH BEGIN'
            LIMIT 1) AS HOURS_BEGIN,
            
			(SELECT I.HOURS_NOW
            FROM EXP_INSP_REPORT_ITEM I
			WHERE I.REPORT_CODE = R.REPORT_CODE
			AND ITEM_TYPE = 'hours'
            AND I.ITEM_TEXT = 'HOURS MONTH END'
            LIMIT 1) AS HOURS_END,
            
            (SELECT GROUP_CONCAT(CONCAT_WS(' ', I.ITEM_TEXT, I.ODO_NEXT) ORDER BY I.ITEM_CODE SEPARATOR '<br>')
            FROM EXP_INSP_REPORT_ITEM I
			WHERE I.REPORT_CODE = R.REPORT_CODE
			AND I.ODO_NEXT IS NOT NULL
			AND ITEM_TYPE = 'ODOMETER') AS NEXT_ODOMETER,


			(SELECT GROUP_CONCAT(CONCAT_WS(' ', I.ITEM_TEXT, I.HOURS_NEXT) ORDER BY I.ITEM_CODE SEPARATOR '<br>')
            FROM EXP_INSP_REPORT_ITEM I
			WHERE I.REPORT_CODE = R.REPORT_CODE
			AND I.HOURS_NEXT IS NOT NULL
			AND ITEM_TYPE = 'HOURS') AS NEXT_HOURS,
 
 			(SELECT LEFT(TRIM(I.COMMENTS), 100)
            FROM EXP_INSP_REPORT_ITEM I
			WHERE I.REPORT_CODE = R.REPORT_CODE
			AND ITEM_TYPE = 'notes'
            ORDER BY SEQUENCE_NO ASC
            LIMIT 1) AS NOTES

			FROM EXP_INSP_REPORT R,
				(SELECT R.UNIT, R.UNIT_TYPE, R.REPORT_NAME, max(R.CREATED_DATE) CREATED_DATE
				FROM EXP_INSP_REPORT R
				WHERE R.SS_REPORT = TRUE
				group by R.UNIT, R.UNIT_TYPE, R.REPORT_NAME) R2
			WHERE R.UNIT = R2.UNIT
			AND R.UNIT_TYPE = R2.UNIT_TYPE
			AND R.CREATED_DATE = R2.CREATED_DATE
			
			".($match <> "" ? "AND $match" : "")."

			ORDER BY R.UNIT_TYPE ASC, R.UNIT ASC" );

		//echo "<pre>raw:\n";
		//var_dump($raw);
		//echo "</pre>";
		
		if( is_array($raw) && count($raw) > 0 ) {
			//! SCR# 617 - We need to pivot all this data into rows.
			
			$units = array();
			$unit_types = array();
			$unit_classes = array();
			
			$forms = array();
			$cols = array();
			foreach( $raw as $row ) {
				$type_unit = $row["UNIT"].'&'.$row["UNIT_TYPE"];
				if( ! isset($units[$type_unit]) ) {
					$units[$type_unit] = $row["UNIT_NUMBER"];
					$unit_types[$type_unit] = $row["UNIT_TYPE"];
					$unit_classes[$type_unit] = $row["CLASS_NAME"];
				}
				$report_name = $row["UNIT_TYPE"].': '.$row["REPORT_NAME"];
				if( ! isset($forms[$report_name]) ) {
					$forms[$report_name] = $row["RM_FORM"];
					$cols[$report_name] = array( "REPORT_DATE", "NEXT_DUE" );
				}
				if( isset($row["ODOMETER_BEGIN"]) && ! in_array("ODOMETER_BEGIN", $cols[$report_name]) ) {
					$cols[$report_name][] = "ODOMETER_BEGIN";
				}
				if( isset($row["ODOMETER_END"]) && ! in_array("ODOMETER_END", $cols[$report_name]) ) {
					$cols[$report_name][] = "ODOMETER_END";
				}
				if( isset($row["HOURS_BEGIN"]) && ! in_array("HOURS_BEGIN", $cols[$report_name]) ) {
					$cols[$report_name][] = "HOURS_BEGIN";
				}
				if( isset($row["HOURS_END"]) && ! in_array("HOURS_END", $cols[$report_name]) ) {
					$cols[$report_name][] = "HOURS_END";
				}
				if( isset($row["NOTES"]) && ! in_array("NOTES", $cols[$report_name]) ) {
					$cols[$report_name][] = "NOTES";
				}
			}
			
			ksort($units);
			ksort($cols);
			
			//echo "<pre>rows:\n";
			//var_dump($units);
			//echo "forms, cols:\n";
			//var_dump($forms, $cols);
			//echo "</pre>";
			
			//! SCR# 617 - Build layout for table
			$this->grid_edit['toprow'] = '<tr>
				<th colspan="3" style="border: 0px;">&nbsp;</th>
				';
			$this->csv_toprow = ',,,';
			
			foreach( $cols as $form => $form_cols ) {
				$this->grid_edit['toprow'] .= '<th class="exspeedite-bg text-center" colspan="'.count($form_cols).'">'.$form.'</th>
				';
				$this->csv_toprow .= '"'.$form.'"'.str_repeat(',', count($form_cols));
				//echo "<p>max = ".max($this->grid_bars)." + ".count($form_cols)." ".$form;
				$this->grid_bars[] = max($this->grid_bars) + count($form_cols);
				//echo " bars = ".implode(', ', $this->grid_bars)."</p>";

				foreach( $form_cols as $col ) {
					switch( $col ) {
						case 'REPORT_DATE':
							//$label = $forms[$form]; // form name
							$label = 'Date';
							$format = 'date';
							break;

						case 'NEXT_DUE':
							$label = 'Next Due';
							$format = 'text';
							break;

						case 'ODOMETER_BEGIN':
							$label = 'ODO Begin';
							$format = 'text';
							break;
						
						case 'ODOMETER_END':
							$label = 'ODO End';
							$format = 'text';
							break;
						
						case 'HOURS_BEGIN':
							$label = 'Hours Begin';
							$format = 'text';
							break;
						
						case 'HOURS_END':
							$label = 'Hours End';
							$format = 'text';
							break;
						
						case 'NOTES':
							$label = 'Notes';
							$format = 'text';
							break;
						
						default:
							$label = $col;
							$format = 'text';
					}
										
					$this->grid_layout[$form.'--'.$col] = 
						array( 'label' => $label, 'format' => $format );
				}
			}
			$this->grid_edit['toprow'] .= '
				</tr>
				';
			
			//echo "<pre>layout:\n";
			//var_dump($this->grid_layout);
			//echo "</pre>";

			//! SCR# 617 - Build rows for each unit
			$new_row = array();
			foreach( $units as $unit => $unit_number ) {
				$new_row[$unit] = array(
					"UNIT" => $unit,
					"UNIT_NUMBER" => (isset($_GET) && isset($_GET["EXPORT"]) ? $unit_number : '<a href="exp_edit'.$unit_types[$unit].'.php?CODE='.$unit.'">'.$unit_number.'</a>'),
					"UNIT_TYPE" => $unit_types[$unit],
					"CLASS_NAME" => $unit_classes[$unit]
				);
				
				foreach( $cols as $form => $form_cols ) {
					foreach( $form_cols as $col ) {
						$new_row[$unit][$form.'--'.$col] = NULL;
					}
				}
			}
			
			//echo "<pre>new rows 1:\n";
			//var_dump($new_row);
			//echo "</pre>";

			foreach( $raw as $row ) {
				$report_name = $row["UNIT_TYPE"].': '.$row["REPORT_NAME"];
				$type_unit = $row["UNIT"].'&'.$row["UNIT_TYPE"];
				foreach( $cols[$report_name] as $col ) {
					$new_row[$type_unit][$report_name.'--'.$col] = $row[$col];
					if( $col == 'NEXT_DUE' ) {
						$new_row[$type_unit][$report_name.'--'.$col] .=
							(empty($row['NEXT_ODOMETER']) ? '' : '<br>'.$row['NEXT_ODOMETER']).
							(empty($row['NEXT_HOURS']) ? '' : '<br>'.$row['NEXT_HOURS']);
					}
				}
			}
			
			//echo "<pre>new rows 2:\n";
			//var_dump($new_row);
			//echo "</pre>";
			
			$result = array_values( $new_row );
			
			array_pop($this->grid_bars);
		}

		if( $this->debug ) {
			echo "<p>result for $this->table_name = </p>
			<pre>";
			var_dump($result);
			echo "</pre>";
		}
		return $result;
	}

}

//! Layout Specifications - For use with sts_result

$sts_result_insp_report_layout = array( //! $sts_result_insp_report_layout
	'REPORT_CODE' => array( 'format' => 'hidden' ),
	'REPORT_DATE' => array( 'label' => 'Date', 'format' => 'date' ),
	'NEXT_DUE' => array( 'label' => 'Next Due', 'format' => 'date' ),
	//'ODO' => array( 'label' => 'ODO', 'format' => 'text' ),
	//'NEXT_ODO' => array( 'label' => 'Next ODO', 'format' => 'text' ),
	'REPORT_NAME' => array( 'label' => 'Form', 'format' => 'text' ),

	'RECURRING' => array( 'label' => 'Freq', 'format' => 'text' ),
	'MECHANIC' => array( 'label' => 'Inspector', 'format' => 'table',
		'table' => USER_TABLE, 'key' => 'USER_CODE', 'fields' => 'USERNAME',
		'link' => 'exp_edituser.php?CODE=' ),
	'SCORE' => array( 'label' => 'Score', 'format' => 'subselect',
		'key' => 'REPORT_CODE',
		'query' => "SELECT CONVERT(concat_ws('/', sum(case when check_status='OK' then 1 else 0 end),
			sum(case when check_status='Future Repair' then 1 else 0 end),
			sum(case when check_status='Needs Repair' then 1 else 0 end)) USING utf8) as SCORE
			from exp_insp_report_item
			where report_code = EXP_INSP_REPORT.%KEY%
			and item_type = 'check'", 'searchable' => false),
	'SS_REPORT' => array( 'label' => 'Reporting', 'format' => 'bool', 'align' => 'center' ),
	'CHANGED_DATE' => array( 'label' => 'Changed', 'format' => 'timestamp-s' ),
	'CHANGED_BY' => array( 'label' => 'By', 'format' => 'table',
		'table' => USER_TABLE, 'key' => 'USER_CODE', 'fields' => 'USERNAME',
		'link' => 'exp_edituser.php?CODE='
		),
);

$sts_result_insp_report_all_layout = array( //! $sts_result_insp_report_all_layout
	'REPORT_CODE' => array( 'format' => 'hidden' ),
	'REPORT_DATE' => array( 'label' => 'Date', 'format' => 'date' ),
	'NEXT_DUE' => array( 'label' => 'Next Due', 'format' => 'date' ),
	//'ODO' => array( 'label' => 'ODO', 'format' => 'text' ),
	//'NEXT_ODO' => array( 'label' => 'Next ODO', 'format' => 'text' ),
	'REPORT_NAME' => array( 'label' => 'Form', 'format' => 'text' ),
	'UNIT_TYPE' => array( 'label' => 'Type', 'format' => 'text' ),
	'RECURRING' => array( 'label' => 'Freq', 'format' => 'text' ),
	'UNIT_NUMBER' => array( 'label' => 'Unit', 'format' => 'text',
		'snippet' => "CASE WHEN UNIT_TYPE = 'tractor' THEN
		    	(SELECT UNIT_NUMBER FROM EXP_TRACTOR
		    		WHERE TRACTOR_CODE = EXP_INSP_REPORT.UNIT)
	    	ELSE
		    	(SELECT UNIT_NUMBER FROM EXP_TRAILER
		    		WHERE TRAILER_CODE = EXP_INSP_REPORT.UNIT)
		    END" ),
	'MECHANIC' => array( 'label' => 'Inspector', 'format' => 'table',
		'table' => USER_TABLE, 'key' => 'USER_CODE', 'fields' => 'USERNAME',
		'link' => 'exp_edituser.php?CODE=' ),
	'SCORE' => array( 'label' => 'Score', 'tip' => '#OK / #Future Repair / #Needs Repair',
		'format' => 'subselect',
		'key' => 'REPORT_CODE',
		'query' => "SELECT CONVERT(concat_ws('/', sum(case when check_status='OK' then 1 else 0 end),
			sum(case when check_status='Future Repair' then 1 else 0 end),
			sum(case when check_status='Needs Repair' then 1 else 0 end)) USING utf8) as SCORE
			from exp_insp_report_item
			where report_code = EXP_INSP_REPORT.%KEY%
			and item_type = 'check'", 'searchable' => false),
	'SS_REPORT' => array( 'label' => 'Reporting', 'format' => 'bool', 'align' => 'center' ),
	'CHANGED_DATE' => array( 'label' => 'Changed', 'format' => 'timestamp-s' ),
	'CHANGED_BY' => array( 'label' => 'By', 'format' => 'table',
		'table' => USER_TABLE, 'key' => 'USER_CODE', 'fields' => 'USERNAME',
		'link' => 'exp_edituser.php?CODE='
		),
);

$sts_result_insp_report_extra_layout = array( //! $sts_result_insp_report_extra_layout
	'REPORT_CODE' => array( 'format' => 'hidden' ),
	'REPORT_DATE' => array( 'label' => 'Date', 'format' => 'date' ),
	'NEXT_DUE' => array( 'label' => 'Next Due', 'format' => 'date' ),
	//'ODO' => array( 'label' => 'ODO', 'format' => 'text' ),
	//'NEXT_ODO' => array( 'label' => 'Next ODO', 'format' => 'text' ),
	'REPORT_NAME' => array( 'label' => 'Form', 'format' => 'text' ),
	
	// SCR# 647 - Additional columns
	'PO_NUMBER' => array( 'label' => 'PO#', 'format' => 'text', 'align' => 'right',
		'snippet' => "(SELECT SERIAL_NUM FROM EXP_INSP_REPORT_ITEM I
			WHERE I.REPORT_CODE = EXP_INSP_REPORT.REPORT_CODE
			AND I.ITEM_TYPE = 'serial'
			AND I.ITEM_TEXT IN ('PO NUMBER', 'PO #') LIMIT 1)" ),
	'INVOICE' => array( 'label' => 'Invoice#', 'format' => 'text', 'align' => 'right',
		'snippet' => "(SELECT SERIAL_NUM FROM EXP_INSP_REPORT_ITEM I
			WHERE I.REPORT_CODE = EXP_INSP_REPORT.REPORT_CODE
			AND I.ITEM_TYPE = 'serial'
			AND I.ITEM_TEXT = 'INVOICE #' LIMIT 1)" ),
	'VENDOR' => array( 'label' => 'Vendor', 'format' => 'text', 'align' => 'right',
		'snippet' => "(SELECT COMMENTS FROM EXP_INSP_REPORT_ITEM I
			WHERE I.REPORT_CODE = EXP_INSP_REPORT.REPORT_CODE
			AND I.ITEM_TYPE = 'text'
			AND I.ITEM_TEXT = 'VENDOR' LIMIT 1)" ),
	'REASON' => array( 'label' => 'Reason', 'format' => 'text', 'align' => 'right',
		'snippet' => "(SELECT COMMENTS FROM EXP_INSP_REPORT_ITEM I
			WHERE I.REPORT_CODE = EXP_INSP_REPORT.REPORT_CODE
			AND I.ITEM_TYPE = 'text'
			AND I.ITEM_TEXT = 'REASON FOR REPAIR' LIMIT 1)" ),
	'COST' => array( 'label' => 'Cost', 'format' => 'num2', 'align' => 'right',
		'snippet' => "(SELECT COST FROM EXP_INSP_REPORT_ITEM I
			WHERE I.REPORT_CODE = EXP_INSP_REPORT.REPORT_CODE
			AND I.ITEM_TYPE = 'cost'
			AND I.ITEM_TEXT = 'COST' LIMIT 1)" ),

	'ODO_BEGIN' => array( 'label' => 'ODO Begin', 'format' => 'text', 'align' => 'right',
		'snippet' => "(SELECT ODO_NOW FROM EXP_INSP_REPORT_ITEM I
			WHERE I.REPORT_CODE = EXP_INSP_REPORT.REPORT_CODE
			AND I.ITEM_TYPE = 'odometer'
			AND I.ITEM_TEXT = 'ODOMETER MONTH BEGIN' LIMIT 1)" ),
	'ODO_END' => array( 'label' => 'ODO End', 'format' => 'text', 'align' => 'right',
		'snippet' => "(SELECT ODO_NOW FROM EXP_INSP_REPORT_ITEM I
			WHERE I.REPORT_CODE = EXP_INSP_REPORT.REPORT_CODE
			AND I.ITEM_TYPE = 'odometer'
			AND I.ITEM_TEXT = 'ODOMETER MONTH END' LIMIT 1)" ),
			
	'HOURS_BEGIN' => array( 'label' => 'Hrs Begin', 'format' => 'text', 'align' => 'right',
		'snippet' => "(SELECT HOURS_NOW FROM EXP_INSP_REPORT_ITEM I
			WHERE I.REPORT_CODE = EXP_INSP_REPORT.REPORT_CODE
			AND I.ITEM_TYPE = 'hours'
			AND I.ITEM_TEXT = 'HOURS MONTH BEGIN' LIMIT 1)" ),
	'HOURS_END' => array( 'label' => 'Hrs End', 'format' => 'text', 'align' => 'right',
		'snippet' => "(SELECT HOURS_NOW FROM EXP_INSP_REPORT_ITEM I
			WHERE I.REPORT_CODE = EXP_INSP_REPORT.REPORT_CODE
			AND I.ITEM_TYPE = 'hours'
			AND I.ITEM_TEXT = 'HOURS MONTH END' LIMIT 1)" ),

	'DRIVER' => array( 'label' => 'Driver', 'format' => 'text', 'align' => 'right',
		'snippet' => "(SELECT concat_ws( ' ', D.FIRST_NAME , D.LAST_NAME ) FROM EXP_INSP_REPORT_ITEM I, EXP_DRIVER D
			WHERE I.REPORT_CODE = EXP_INSP_REPORT.REPORT_CODE
			AND I.DRIVER = D.DRIVER_CODE
			AND I.ITEM_TYPE = 'driver'
			AND I.ITEM_TEXT IN ('Driver', 'Driver Name') LIMIT 1)" ),
			
	'UNIT_TYPE' => array( 'label' => 'Type', 'format' => 'text' ),
	'RECURRING' => array( 'label' => 'Freq', 'format' => 'text' ),
	'UNIT_NUMBER' => array( 'label' => 'Unit', 'format' => 'text',
		'snippet' => "CASE WHEN UNIT_TYPE = 'tractor' THEN
		    	(SELECT UNIT_NUMBER FROM EXP_TRACTOR
		    		WHERE TRACTOR_CODE = EXP_INSP_REPORT.UNIT)
	    	ELSE
		    	(SELECT UNIT_NUMBER FROM EXP_TRAILER
		    		WHERE TRAILER_CODE = EXP_INSP_REPORT.UNIT)
		    END" ),
	'MECHANIC' => array( 'label' => 'Inspector', 'format' => 'table',
		'table' => USER_TABLE, 'key' => 'USER_CODE', 'fields' => 'USERNAME',
		'link' => 'exp_edituser.php?CODE=' ),
	'SCORE' => array( 'label' => 'Score', 'tip' => '#OK / #Future Repair / #Needs Repair',
		'format' => 'subselect',
		'key' => 'REPORT_CODE',
		'query' => "SELECT CONVERT(concat_ws('/', sum(case when check_status='OK' then 1 else 0 end),
			sum(case when check_status='Future Repair' then 1 else 0 end),
			sum(case when check_status='Needs Repair' then 1 else 0 end)) USING utf8) as SCORE
			from exp_insp_report_item
			where report_code = EXP_INSP_REPORT.%KEY%
			and item_type = 'check'", 'searchable' => false),
	'SS_REPORT' => array( 'label' => 'Reporting', 'format' => 'bool', 'align' => 'center' ),
	'CHANGED_DATE' => array( 'label' => 'Changed', 'format' => 'timestamp-s' ),
	'CHANGED_BY' => array( 'label' => 'By', 'format' => 'table',
		'table' => USER_TABLE, 'key' => 'USER_CODE', 'fields' => 'USERNAME',
		'link' => 'exp_edituser.php?CODE='
		),
);

//! Edit/Delete Button Specifications - For use with sts_result

$sts_result_insp_report_edit = array( //! $sts_result_insp_report_edit
	'title' => '<span class="glyphicon glyphicon-wrench"></span> Inspection Reports',
	'sort' => 'REPORT_CODE asc',
	//'cancel' => 'exp_listtractor.php',
	//'add' => 'exp_addinsp_report.php',
	//'actionextras' => 'disabled',
	//'addbutton' => 'Add Report',
	//'cancelbutton' => 'Back',
	'rowbuttons' => array(
		array( 'url' => 'exp_viewinsp_report.php?REPORT=', 'key' => 'REPORT_CODE', 'label' => 'REPORT_DATE', 'tip' => 'View report ', 'icon' => 'glyphicon glyphicon-list-alt', 'target' => 'blank' ),
		array( 'url' => 'exp_addinsp_report.php?REPORT=', 'key' => 'REPORT_CODE', 'label' => 'REPORT_DATE', 'tip' => 'Edit report ', 'icon' => 'glyphicon glyphicon-edit', 'showif' => 'inspection' ),
		array( 'url' => 'exp_deleteinsp_report.php?REPORT=', 'key' => 'REPORT_CODE', 'label' => 'REPORT_DATE', 'tip' => 'Delete report ', 'icon' => 'glyphicon glyphicon-trash', 'confirm' => 'yes', 'showif' => 'admin' )
	)
);


?>
