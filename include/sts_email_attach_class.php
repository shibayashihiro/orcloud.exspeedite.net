<?php

// $Id: sts_email_attach_class.php 3201 2018-09-04 20:27:58Z duncan $
// Email Attachment class - From: emails for sending attachments

// no direct access
defined('_STS_INCLUDE') or die('Restricted access');

require_once( "sts_table_class.php" );
require_once( "sts_setting_class.php" );

class sts_email_attach extends sts_table {

	private $setting_table;
	private $enabled;
	private $email_from_name;
	private $email_from_address;

	// Constructor does not need the table name
	public function __construct( $database, $debug = false ) {

		$this->debug = $debug;
		$this->primary_key = "EMAIL_ATTACH_CODE";
		if( $this->debug ) echo "<p>Create sts_email_attach</p>";

		$this->setting_table = sts_setting::getInstance($database, $debug);
		$this->enabled = ($this->setting_table->get( 'option', 'ATTACHMENTS_ALT_EMAILS' ) == 'true');
		$this->email_from_name = $this->setting_table->get( 'email', 'EMAIL_FROM_NAME' );
		$this->email_from_address = $this->setting_table->get( 'email', 'EMAIL_FROM_ADDRESS' );

		parent::__construct( $database, EMAIL_ATTACH_TABLE, $debug);
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
    
    // Is this enabled
	public function enabled() {
		return $this->enabled;
	}
	
	// The default from address if no matches
	public function default_from() {
		return $this->email_from_name." <".$this->email_from_address .">";
	}
	
	// Find matches for a given type and source
	// Return an array of strings
	public function matches( $attachment_type, $source_type ) {
		$check = $this->fetch_rows("ATTACHMENT_CODE = $attachment_type AND SOURCE_TYPE = '".$source_type."'", "ATTACH_FROM");
		if( is_array($check) && count($check) > 0 ) {
			$emails = [];
			
			foreach( $check as $row ) {
				$from = explode(',', $row["ATTACH_FROM"]);
				foreach( $from as $email ) {
					$emails[] = trim($email);
				}
			}
		} else {
			$emails = [ $this->default_from() ];
		}
		
		return $emails;
	}
	
	public function matches_menu( $matches ) {
		$output = '';
		if( is_array($matches) && count($matches) > 0 ) {
			$output =  '<select class="form-control" style="display: inline-block; margin-bottom: 0; vertical-align: middle; width: auto;" name="FROM_EMAIL" id="FROM_EMAIL" >
			';
			foreach($matches as $row) {
				$output .= '<option value="'.$row.'">'.htmlspecialchars($row).'</option>
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
	
	// Given an atttachment ITEM_CODE, get the name (ITEM)
	public function attachment_type( $code ) {
		$result = "";
		$check = $this->database->get_one_row("
			SELECT ITEM FROM EXP_ITEM_LIST
			WHERE ITEM_CODE = $code
			LIMIT 1");
		if( is_array($check) && count($check) == 1 && isset($check["ITEM"]))
			$result = $check["ITEM"];
		
		return $result;
	}
}

//! Form Specifications - For use with sts_form

$sts_form_add_email_attach_form = array(	//! $sts_form_add_email_attach_form
	'title' => '<span class="glyphicon glyphicon-envelope"></span> Add Email Attachments From',
	'action' => 'exp_addemail_attach.php',
	//'actionextras' => 'disabled',
	'cancel' => 'exp_listemail_attach.php',
	'name' => 'addbc',
	'okbutton' => 'Save Changes',
	'saveadd' => 'Add Another',
	'cancelbutton' => 'Cancel',
		'layout' => '
	<div class="form-group">
		<div class="col-sm-12">
			<div class="form-group">
				<label for="ATTACHMENT_CODE" class="col-sm-2 control-label">#ATTACHMENT_CODE#</label>
				<div class="col-sm-4">
					%ATTACHMENT_CODE%
				</div>
				<div class="col-sm-4">
					<label>What type of attachment</label>
				</div>
			</div>
			<div class="form-group">
				<label for="SOURCE_TYPE" class="col-sm-2 control-label">#SOURCE_TYPE#</label>
				<div class="col-sm-4">
					%SOURCE_TYPE%
				</div>
				<div class="col-sm-4">
					<label>Attached to what</label>
				</div>
			</div>
			<div class="form-group">
				<label for="ATTACH_FROM" class="col-sm-2 control-label">#ATTACH_FROM#</label>
				<div class="col-sm-8">
					%ATTACH_FROM%
				</div>
			</div>
		</div>
		<div class="col-sm-6">
		</div>
	</div>
	
	'
);

$sts_form_edit_email_attach_form = array(	//! $sts_form_edit_email_attach_form
	'title' => '<span class="glyphicon glyphicon-envelope"></span> Edit Email Attachments From',
	'action' => 'exp_editemail_attach.php',
	//'actionextras' => 'disabled',
	'cancel' => 'exp_listemail_attach.php',
	'name' => 'editemail_attach',
	'okbutton' => 'Save Changes',
	'cancelbutton' => 'Back',
		'layout' => '
		%EMAIL_ATTACH_CODE%
	<div class="form-group">
		<div class="col-sm-12">
			<div class="form-group">
				<label for="ATTACHMENT_CODE" class="col-sm-2 control-label">#ATTACHMENT_CODE#</label>
				<div class="col-sm-4">
					%ATTACHMENT_CODE%
				</div>
				<div class="col-sm-4">
					<label>What type of attachment</label>
				</div>
			</div>
			<div class="form-group">
				<label for="SOURCE_TYPE" class="col-sm-2 control-label">#SOURCE_TYPE#</label>
				<div class="col-sm-4">
					%SOURCE_TYPE%
				</div>
				<div class="col-sm-4">
					<label>Attached to what</label>
				</div>
			</div>
			<div class="form-group">
				<label for="ATTACH_FROM" class="col-sm-2 control-label">#ATTACH_FROM#</label>
				<div class="col-sm-8">
					%ATTACH_FROM%
				</div>
			</div>
		</div>
		<div class="col-sm-6">
		</div>
	</div>
	
	'
);

//! Field Specifications - For use with sts_form

$sts_form_add_email_attach_fields = array(	//! $sts_form_add_email_attach_fields
	'ATTACHMENT_CODE' => array( 'label' => 'Attachment type', 'format' => 'table',
		'table' => ITEM_LIST_TABLE, 'key' => 'ITEM_CODE', 'fields' => 'ITEM',
		'condition' => 'ITEM_TYPE = \'Attachment type\'' ),
	'SOURCE_TYPE' => array( 'label' => 'Item Type', 'format' => 'enum' ),
	'ATTACH_FROM' => array( 'label' => 'From', 'format' => 'text', 'placeholder' => 'One or more From addresses, comma separated', 'extras' => 'required' ),
);

$sts_form_edit_email_attach_fields = array(	//! $sts_form_edit_email_attach_fields
	'EMAIL_ATTACH_CODE' => array( 'format' => 'hidden' ),
	'ATTACHMENT_CODE' => array( 'label' => 'Attachment type', 'format' => 'table',
		'table' => ITEM_LIST_TABLE, 'key' => 'ITEM_CODE', 'fields' => 'ITEM',
		'condition' => 'ITEM_TYPE = \'Attachment type\'' ),
	'SOURCE_TYPE' => array( 'label' => 'Item Type', 'format' => 'enum' ),
	'ATTACH_FROM' => array( 'label' => 'From', 'format' => 'text' ),
);

//! Layout Specifications - For use with sts_result

$sts_result_email_attach_layout = array(	//! $sts_result_email_attach_layout
	'EMAIL_ATTACH_CODE' => array( 'format' => 'hidden' ),
	'ATTACHMENT_CODE' => array( 'label' => 'Attachment type', 'format' => 'table',
		'table' => ITEM_LIST_TABLE, 'key' => 'ITEM_CODE', 'fields' => 'ITEM',
		'condition' => 'ITEM_TYPE = \'Attachment type\'' ),
	'SOURCE_TYPE' => array( 'label' => 'Item Type', 'format' => 'text' ),
	'ATTACH_FROM' => array( 'label' => 'From', 'format' => 'rawtext',
		'snippet' => "replace(replace(ATTACH_FROM, '<', '&lt;'), '>','&gt;')"
	 ),
	'CREATED_DATE' => array( 'label' => 'Created', 'format' => 'timestamp-s' ),
	'CHANGED_BY' => array( 'label' => 'By', 'format' => 'table',
		'table' => USER_TABLE, 'key' => 'USER_CODE', 'fields' => 'USERNAME', 'link' => 'exp_edituser.php?CODE=' )
);

//! Edit/Delete Button Specifications - For use with sts_result

$sts_result_email_attach_edit = array(	//! $sts_result_email_attach_edit
	'title' => '<span class="glyphicon glyphicon-envelope"></span> Email Attachments From: addresses',
	'sort' => 'SOURCE_TYPE asc, ATTACHMENT_CODE asc',
	'cancel' => 'index.php',
	'add' => 'exp_addemail_attach.php',
	//'actionextras' => 'disabled',
	'addbutton' => 'Add Item',
	'cancelbutton' => 'Back',
	'rowbuttons' => array(
		array( 'url' => 'exp_editemail_attach.php?CODE=', 'key' => 'EMAIL_ATTACH_CODE', 'label' => 'ATTACH_FROM', 'tip' => 'Edit item ', 'icon' => 'glyphicon glyphicon-edit' ),
		array( 'url' => 'exp_deleteemail_attach.php?CODE=', 'key' => 'EMAIL_ATTACH_CODE', 'label' => 'ATTACH_FROM', 'tip' => 'Delete item ', 'icon' => 'glyphicon glyphicon-trash', 'confirm' => 'yes' )
	)
);


?>
