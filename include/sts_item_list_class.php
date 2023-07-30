<?php

// $Id: sts_item_list_class.php 5030 2023-04-12 20:31:34Z duncan $
// Item list class - deal with lists of items

// no direct access
defined('_STS_INCLUDE') or die('Restricted access');

require_once( "sts_table_class.php" );
require_once( "sts_setting_class.php" );
require_once( "sts_email_class.php" );

class sts_item_list extends sts_table {

	private $default_lead_sources = array(
		'Blog',
		'Dead lead',
		'Direct mail',
		'Email inquiry',
		'Email marketing',
		'Hoovers.com',
		'Industry list',
		'List rental/purchase',
		'Networking event',
		'Newspaper ad',
		'Online',
		'Online ad',
		'Online directory',
		'Online survey',
		'Other',
		'Past client',
		'Press article',
		'Promotion',
		'Radio ad',
		'Refer.com',
		'Referral',
		'Search engine',
		'Social media',
		'Tele-prospecting',
		'Third party',
		'Trade show',
		'Tv ad',
		'Webinar',
		'Word of mouth',
		'Zoominfo.com',
		'Pipedrive'
	);
	
	private $default_call_types = array(
		'Telephone',
		'Meeting',
		'Cold Call',
		'Email',
		'Drop In'
	);

	private $default_objectives = array(
		'Introduction',
		'Identify decision maker',
		'Follow up',
		'Moved',
		'General Call',
		'Send rates',
		'Discuss opportunity',
		'Book a meeting',
		'Anything moving',
		'Other'
	);

	private $default_outcomes = array(
		'Meeting Set',
		'Follow Up',
		'Got Load',
		'Requested Lane Quote',
		'Sent email requesting lane quote',
		'Sent Quote',
		'Sent email with our info',
		'Voicemail - no message',
		'Left voicemail',
		'Took contact info',
		'Out of office',
		'Away until',
		'Received',
		'Credit Requested',
		'Customer Routed',
		'Will have some later this week',
		'Business closed',
		'Contact no longer there',
		'Not Yet',
		'Thinking about it',
		'Do not call again',
		'Other'
	);

	private $default_attachment_types = array(
		'Bill of lading',
		'Contract',
		'Checklist',
		'Claim form',
		'Employment Contract',
		'Expense form',
		'Insurance',
		'Invoice',
		'Job description',
		'Manifest',
		'MC',
		'Pods',
		'Rate confirmation',
		'Rates',
		'Reference',
		'Ticket',
		'Training record',
		'W-9',
		'Work permit',
		'Screen capture',
		'Requirements',
		'Design',
		'Document',
		'Logo'
	);

	private $default_client_terms = array(
		'NET 60',
		'NET 45',
		'NET 30',
		'NET 14',
		'NET 7',
		'COD',
	);

	private $default_vendor_terms = array(
		'NET 30',
		'NET 14',
		'NET 7',
		'COD',
	);

	//! SCR# 702 - Equipment type in a checklist
	private $default_equipment_types = array(
		'Reefer',
		'Dry van',
		'Flat',
		'Tarped',
		'Rail',
		'Straight truck',
	);

	private $setting_table;
	private $export_sage50;
	private $export_qb;
	private $invoice_terms;
	private $bill_terms;

	// Constructor does not need the table name
	public function __construct( $database, $debug = false ) {

		$this->debug = $debug;
		$this->primary_key = "ITEM_CODE";
		if( $this->debug ) echo "<p>Create sts_item_list</p>";

		$this->setting_table = sts_setting::getInstance($database, $debug);
		$this->export_sage50 = ($this->setting_table->get( 'api', 'EXPORT_SAGE50_CSV' ) == 'true');
		$this->export_qb = $this->setting_table->get( 'api', 'EXPORT_QUICKBOOKS' ) == 'true';
		if( $this->export_sage50 ) {
			$this->invoice_terms = $this->setting_table->get( 'api', 'SAGE50_INVOICE_TERMS' );
			$this->bill_terms = $this->setting_table->get( 'api', 'SAGE50_BILL_TERMS' );
		} else if( $this->export_qb ) {
			$this->invoice_terms = $this->setting_table->get( 'api', 'QUICKBOOKS_INVOICE_TERMS' );
			$this->bill_terms = $this->setting_table->get( 'api', 'QUICKBOOKS_BILL_TERMS' );
		}

		parent::__construct( $database, ITEM_LIST_TABLE, $debug);
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
    public function load_defaults( $force = false ) {
		if( $this->debug ) echo "<p>".__METHOD__.": session v = ".(isset($_SESSION["DEFAULT_ITEMS_LOADED"]) ? 'set' : 'unset')."</p>";
	    if( $force || ! isset($_SESSION["DEFAULT_ITEMS_LOADED"])) {
		    $_SESSION["DEFAULT_ITEMS_LOADED"] = true;
		    if( $force ) {
			    echo '<div id="loadingd"><h2 class="text-center"><img src="images/loading.gif" alt="loading" width="125" height="125" /><br>Updating Default Items...</h2>
			</div>';
				ob_flush(); flush();
			}
		    
		    $check = $this->database->get_multiple_rows("
			    SELECT ITEM_TYPE, COUNT(*) AS NUM
				FROM EXP_ITEM_LIST
				GROUP BY ITEM_TYPE
				ORDER BY ITEM_TYPE ASC
		    ");

		    $item_type = array();
		    if( is_array($check) && count($check) > 0 ) {
			    foreach( $check as $row ) {
				    $item_type[] = $row["ITEM_TYPE"];
			    }
			}
			    
		    if( ! in_array('Lead source', $item_type)) {
			    foreach( $this->default_lead_sources as $item ) {
				    if( $this->debug ) echo "<p>".__METHOD__.": add $item</p>";
				    $this->add( array('ITEM_TYPE' => 'Lead source',
				    	'ITEM' => $item ));
			    }
		    }

		    if( ! in_array('Call type', $item_type)) {
			    foreach( $this->default_call_types as $item ) {
				    $this->add( array('ITEM_TYPE' => 'Call type',
				    	'ITEM' => $item ));
			    }
		    }

		    if( ! in_array('Objective', $item_type)) {
			    foreach( $this->default_objectives as $item ) {
				    $this->add( array('ITEM_TYPE' => 'Objective',
				    	'ITEM' => $item ));
			    }
		    }

		    if( ! in_array('Outcome', $item_type)) {
			    foreach( $this->default_outcomes as $item ) {
				    $this->add( array('ITEM_TYPE' => 'Outcome',
				    	'ITEM' => $item ));
			    }
		    }

		    if( ! in_array('Attachment type', $item_type)) {
			    foreach( $this->default_attachment_types as $item ) {
				    $this->add( array('ITEM_TYPE' => 'Attachment type',
				    	'ITEM' => $item ));
			    }
		    }

		    if( ! in_array('Client Terms', $item_type)) {
			    foreach( $this->default_client_terms as $item ) {
				    $this->add( array('ITEM_TYPE' => 'Client Terms',
				    	'ITEM' => $item ));
			    }
		    }

		    if( ! in_array('Vendor Terms', $item_type)) {
			    foreach( $this->default_vendor_terms as $item ) {
				    $this->add( array('ITEM_TYPE' => 'Vendor Terms',
				    	'ITEM' => $item ));
			    }
		    }

		    if( ! in_array('Equipment Type', $item_type)) {
			    foreach( $this->default_equipment_types as $item ) {
				    $this->add( array('ITEM_TYPE' => 'Equipment Type',
				    	'ITEM' => $item ));
			    }
		    }
		    if( $force )
		    	update_message( 'loadingd', '' );
	    }
    }

	public function set_attachment_types() {
		foreach( $this->default_attachment_types as $item ) {
			$file_type = $this->get_item_code( 'Attachment type', $item );
		    if( ! $file_type ) {
			    $this->add( array('ITEM_TYPE' => 'Attachment type',
					'ITEM' => $item ));
			}
		}
	}
	
	public function get_types() {
		$types = false;
		$result = $this->fetch_rows("", "DISTINCT ITEM_TYPE", "ITEM_TYPE ASC");
		if( isset($result) && is_array($result) && count($result) > 0 ) {
			$category = array();
			foreach( $result as $row ) {
				$types[] = $row["ITEM_TYPE"];
			}
		}
		return $types;
	}
	
	public function get_item_code( $item_type, $item ) {
		$code = false;
		$result = $this->fetch_rows("ITEM_TYPE = '".$item_type."'
			AND ITEM = '".$item."'", "ITEM_CODE");
		if( isset($result) && is_array($result) && count($result) == 1 ) {
			$code = $result[0]["ITEM_CODE"];
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
				if( $selected == $row["ITEM_CODE"] ||
					($selected == 0 && $row["ITEM"] == "NET 30") ) {
					$choice = $row["ITEM"];
					$code = $row["ITEM_CODE"];
				}
				
				$output .= '<option value="'.$row["ITEM_CODE"].'"'.($selected == $row["ITEM_CODE"] || ($selected == 0 && $row["ITEM"] == "NET 30") ? ' selected' : '').'>'.$row["ITEM"].'</option>
				';
			}
			$output .= '</select>
			';
		}
		//! SCR# 676 - lock down the billing terms
		//! SCR# 774 - same for carrier terms
		if( ! in_group(EXT_GROUP_MANAGER) ) {
			$output =  '<input type="hidden" name="TERMS" id="TERMS" value="'.$code.'">['.$choice.']';
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
	
	//! SCR# 702 - Create checkboxes for equipment requirements
	public function equipment_checkboxes( $form, $source_type = false, $source_code = false, $readonly = false ) {
		if( $this->debug ) echo "<p>".__METHOD__.": $source_type, $source_code</p>";
		$equipment = $this->fetch_rows("ITEM_TYPE = 'Equipment Type'", "ITEM, ITEM_CODE", "ITEM ASC");
		
		if( $source_type && $source_code )
			$eq_table = sts_equpiment_req::getInstance($this->database, $this->debug);
	
		if( is_array($equipment) && count( $equipment ) > 0 ) {
			$equipment_str = '<div id="EQUIPMENT" class="panel panel-info">
			  <div class="panel-heading">
			    <h3 class="panel-title">Required Equipment</h3>
			  </div>
			  <div class="panel-body">
			';
			foreach( $equipment as $row ) {
				$check = $source_code ?
					$eq_table->fetch_rows("SOURCE_TYPE = '".$source_type."'
					AND SOURCE_CODE = ".$source_code."
					AND ITEM_CODE = ".$row["ITEM_CODE"]) : false;
				if( $this->debug ) {
					echo "<pre>";
					var_dump($check);
					echo "</pre>";
				}
				$exists = is_array($check) && count($check) > 0;
				if( $this->debug ) echo "<p>".__METHOD__.": exists = ".($exists ? 'true' : 'false')."</p>";
				
				$equipment_str .= '<div class="checkbox">
				    <label>
				      <input type="checkbox" class="office" name="EQUIPMENT_'.$row["ITEM_CODE"].'" id="EQUIPMENT_'.$row["ITEM_CODE"].'" value="'.$row["ITEM_CODE"].'"'.
				      ($exists ? ' checked' : '').
				      ($readonly ? ' disabled="disabled"' : '').'> '.$row["ITEM"].'
				    </label>
				    </div>
				    ';
			}
			$equipment_str .= '</div>
			</div>
			';		
		
			if( is_array($form) && !empty($form['layout'])) {
				$form['layout'] = str_replace('<!-- EQUIPMENT -->', $equipment_str, $form['layout']);
			} else {
				$email = sts_email::getInstance($this->database, $this->debug);
				$email->send_alert(__METHOD__.": form/layout missing" );
			}
		}
		return $form;
	}

	//! Process checkboxes for equipment requirements
	public function process_equipment_checkboxes( $source_type, $source_code ) {
		if( $this->debug ) echo "<p>".__METHOD__.": $source_type, $source_code</p>";
		$eq_table = sts_equpiment_req::getInstance($this->database, $this->debug);

		$equipment = $this->fetch_rows("ITEM_TYPE = 'Equipment Type'", "ITEM, ITEM_CODE", "ITEM ASC");
		
		if( is_array($equipment) && count( $equipment ) > 0 ) {
			foreach( $equipment as $row ) {
				$check = $eq_table->fetch_rows("SOURCE_TYPE = '".$source_type."'
					AND SOURCE_CODE = ".$source_code."
					AND ITEM_CODE = ".$row["ITEM_CODE"]);
				
				$exists = is_array($check) && count($check) > 0;
				if( $this->debug ) echo "<p>".__METHOD__.": exists = ".($exists ? 'true' : 'false')."</p>";
				
				if( is_array($_POST) &&
					isset($_POST['EQUIPMENT_'.$row["ITEM_CODE"]])) {
					
					if( ! $exists )
						$eq_table->add( array( 'SOURCE_TYPE' => $source_type,
							'SOURCE_CODE' => $source_code,
							'ITEM_CODE' => $row["ITEM_CODE"]) );
				} else {
					if( $exists )
						$eq_table->delete_row("SOURCE_TYPE = '".$source_type."'
							AND SOURCE_CODE = ".$source_code."
							AND ITEM_CODE = ".$row["ITEM_CODE"]);
				}
			}
		}
	}
	
	//! Get the requirements for a client and apply to a shipment
	public function propagate_equipment_req( $client, $shipment ) {
		if( $this->debug ) echo "<p>".__METHOD__.": $client, $shipment</p>";
		$eq_table = sts_equpiment_req::getInstance($this->database, $this->debug);
		
		// Get client requirements
		$check = $eq_table->fetch_rows("SOURCE_TYPE = 'client'
			AND SOURCE_CODE = ".$client);

		// Delete old shipment requirements
		$eq_table->delete_row("SOURCE_TYPE = 'shipment'
			AND SOURCE_CODE = ".$shipment);
			
		if( is_array($check) && count($check) > 0 ) {
			foreach( $check as $row ) {
				$eq_table->add( array( 'SOURCE_TYPE' => 'shipment',
					'SOURCE_CODE' => $shipment,
					'ITEM_CODE' => $row["ITEM_CODE"]) );
			}
		}
		$temp = [];
		$temp['layout'] = '<!-- EQUIPMENT -->';
		
		$result = $this->equipment_checkboxes( $temp, 'shipment', $shipment );
		
		return $result['layout'];
	}
	
	//! Check if we can delete an item.
	// Only possible if class has not been used yet.
	public function can_delete( $code ) {
		$result = false;
		$check = $this->database->get_one_row(
			"SELECT COUNT(R.EQUIPMENT_CODE) INUSE
			FROM EXP_ITEM_LIST L, EXP_EQUIPMENT_REQ R
			WHERE L.ITEM_CODE = $code
			AND ITEM_TYPE = 'Equipment Type'
			AND R.ITEM_CODE = L.ITEM_CODE" );
		if( is_array($check) && isset($check["INUSE"]))
			$result = intval($check["INUSE"]) == 0;
		return $result;	
	}
}

//! SCR# 702 - Class to match equipment requirements with clients or shipments
class sts_equpiment_req extends sts_table {

	// Constructor does not need the table name
	public function __construct( $database, $debug = false ) {

		$this->debug = $debug;
		$this->primary_key = "EQUIPMENT_CODE";
		if( $this->debug ) echo "<p>Create sts_equpiment_req</p>";
		parent::__construct( $database, EQUIPMENT_REQ_TABLE, $debug);
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

$sts_form_add_item_list_form = array(	//! $sts_form_add_item_list_form
	'title' => '<span class="glyphicon glyphicon-th-list"></span> Add Item',
	'action' => 'exp_additem_list.php',
	//'actionextras' => 'disabled',
	'cancel' => 'exp_listitem_list.php',
	'name' => 'addbc',
	'okbutton' => 'Save Changes',
	'saveadd' => 'Add Another',
	'cancelbutton' => 'Cancel',
		'layout' => '
	<div class="form-group">
		<div class="col-sm-12">
			<div class="form-group">
				<label for="ITEM_TYPE" class="col-sm-2 control-label">#ITEM_TYPE#</label>
				<div class="col-sm-4">
					%ITEM_TYPE%
				</div>
				<div class="col-sm-4">
					<label>What type of item</label>
				</div>
			</div>
			<div class="form-group">
				<label for="ITEM" class="col-sm-2 control-label">#ITEM#</label>
				<div class="col-sm-4">
					%ITEM%
				</div>
				<div class="col-sm-4">
					<label>Name to be seen on screen</label>
				</div>
			</div>
			<div class="form-group">
				<label for="ITEM_DESCRIPTION" class="col-sm-2 control-label">#ITEM_DESCRIPTION#</label>
				<div class="col-sm-8">
					%ITEM_DESCRIPTION%
				</div>
			</div>
		</div>
		<div class="col-sm-6">
		</div>
	</div>
	
	'
);

$sts_form_edit_item_list_form = array(
	'title' => '<span class="glyphicon glyphicon-th-list"></span> Edit Lead Source',
	'action' => 'exp_edititem_list.php',
	//'actionextras' => 'disabled',
	'cancel' => 'exp_listitem_list.php',
	'name' => 'edititem_list',
	'okbutton' => 'Save Changes',
	'cancelbutton' => 'Back',
		'layout' => '
		%ITEM_CODE%
	<div class="form-group">
		<div class="col-sm-12">
			<div class="form-group">
				<label for="ITEM_TYPE" class="col-sm-2 control-label">#ITEM_TYPE#</label>
				<div class="col-sm-4">
					%ITEM_TYPE%
				</div>
				<div class="col-sm-4">
					<label>What type of item</label>
				</div>
			</div>
			<div class="form-group">
				<label for="ITEM" class="col-sm-2 control-label">#ITEM#</label>
				<div class="col-sm-4">
					%ITEM%
				</div>
				<div class="col-sm-4">
					<label>Name to be seen on menus</label>
				</div>
			</div>
			<div class="form-group">
				<label for="ITEM_DESCRIPTION" class="col-sm-2 control-label">#ITEM_DESCRIPTION#</label>
				<div class="col-sm-8">
					%ITEM_DESCRIPTION%
				</div>
			</div>
		</div>
		<div class="col-sm-6">
		</div>
	</div>
	
	'
);

//! Field Specifications - For use with sts_form

$sts_form_add_item_list_fields = array(
	'ITEM_TYPE' => array( 'label' => 'Type', 'format' => 'enum' ),
	'ITEM' => array( 'label' => 'Item', 'format' => 'text', 'extras' => 'required' ),
	'ITEM_DESCRIPTION' => array( 'label' => 'Description', 'format' => 'text' ),
);

$sts_form_edit_item_list_fields = array(
	'ITEM_CODE' => array( 'format' => 'hidden' ),
	'ITEM_TYPE' => array( 'label' => 'Type', 'format' => 'enum' ),
	'ITEM' => array( 'label' => 'Item', 'format' => 'text', 'extras' => 'required' ),
	'ITEM_DESCRIPTION' => array( 'label' => 'Description', 'format' => 'text' ),
);

//! Layout Specifications - For use with sts_result

$sts_result_item_list_layout = array(
	'ITEM_CODE' => array( 'format' => 'hidden' ),
	'ITEM_TYPE' => array( 'label' => 'Type', 'format' => 'text' ),
	'ITEM' => array( 'label' => 'Item', 'format' => 'text' ),
	'ITEM_DESCRIPTION' => array( 'label' => 'Description', 'format' => 'text' ),
	'CREATED_DATE' => array( 'label' => 'Created', 'format' => 'timestamp-s' ),
	'CHANGED_BY' => array( 'label' => 'By', 'format' => 'table',
		'table' => USER_TABLE, 'key' => 'USER_CODE', 'fields' => 'USERNAME', 'link' => 'exp_edituser.php?CODE=' )
);

//! Edit/Delete Button Specifications - For use with sts_result

$sts_result_item_list_edit = array(
	'title' => '<span class="glyphicon glyphicon-th-list"></span> Items',
	'sort' => 'ITEM_TYPE asc, ITEM asc',
	'cancel' => 'index.php',
	'add' => 'exp_additem_list.php',
	//'actionextras' => 'disabled',
	'addbutton' => 'Add Item',
	'cancelbutton' => 'Back',
	'rowbuttons' => array(
		array( 'url' => 'exp_edititem_list.php?CODE=', 'key' => 'ITEM_CODE', 'label' => 'ITEM', 'tip' => 'Edit item ', 'icon' => 'glyphicon glyphicon-edit' ),
		array( 'url' => 'exp_deleteitem_list.php?CODE=', 'key' => 'ITEM_CODE', 'label' => 'ITEM', 'tip' => 'Delete item (Be careful, if this is in use it could break things) ', 'icon' => 'glyphicon glyphicon-trash', 'confirm' => 'yes', 'showif' => 'can_delete' )
	)
);


?>
