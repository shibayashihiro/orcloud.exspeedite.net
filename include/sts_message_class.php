<?php

// $Id: sts_message_class.php 4350 2021-03-02 19:14:52Z duncan $
// Message class, all activity related to messages.

// no direct access
defined('_STS_INCLUDE') or die('Restricted access');

require_once( "sts_table_class.php" );
require_once( "sts_setting_class.php" );
require_once( "sts_office_class.php" );

class sts_message extends sts_table {

	private $setting_table;
	private $multi_company;
	private $user_message_table;
	private $message_expiry;
	
	// Constructor does not need the table name
	public function __construct( $database, $debug = false ) {

		$this->debug = $debug;
		$this->primary_key = "MESSAGE_CODE";
		if( $this->debug ) echo "<p>Create sts_message</p>";
		parent::__construct( $database, MESSAGE_TABLE, $debug);
		$this->setting_table = sts_setting::getInstance( $this->database, $this->debug );
		$this->multi_company = $this->setting_table->get( 'option', 'MULTI_COMPANY' ) == 'true';
		//! SCR# 547 - Make messages expire after so many days
		$this->message_expiry = intval( $this->setting_table->get( 'option', 'MESSAGES_EXPIRY' ) );
		if( $this->message_expiry < 0 ) $this->message_expiry = 30;

		$this->user_message_table = sts_user_message::getInstance( $this->database, $this->debug );
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
    
    public function multi_company() {
	    return $this->multi_company;
    }

	public function delete( $code, $type = "" ) {
		parent::delete( $code, $type );
		// kill links
		$this->user_message_table->delete_row( "MESSAGE_CODE = ".$code );
	}
	
	//! SCR# 547 - Make messages expire after so many days
	public function expire() {
		$expired = 0;
		if( $this->message_expiry > 0 ) {
			$check = $this->fetch_rows("END_DATE < DATE_SUB(NOW(), INTERVAL ".$this->message_expiry." DAY)",
				"MESSAGE_CODE");
			if( is_array($check) && count($check) > 0 ) {
				$expired = count($check);
				foreach( $check as $row ) {
					$this->delete( $row["MESSAGE_CODE"], 'permdel');
				}
			}
		}
		if( $this->debug ) echo "<p>".__METHOD__.": expire $expired messages.</p>";
		
		return $expired;
	}
	
	//! Get my messages - return an array of messages for me
	// Returns array or false if none.	
	public function my_messages( $code ) {
		if( $this->debug ) echo "<p>".__METHOD__.": entry, code = $code</p>";
		$result = false;
		
		if( $this->debug || ! isset($_SESSION['EXT_SEEN_MESSAGES']) ) {
			$_SESSION['EXT_SEEN_MESSAGES'] = true;
			if( isset($_SESSION['EXT_USER_OFFICES']) )
				$offices = implode(', ', array_keys($_SESSION['EXT_USER_OFFICES']));
			else
				$offices = '0';
				
			$check = $this->database->get_multiple_rows("
				SELECT M.MESSAGE_CODE, U.USERNAME, DATE_FORMAT(M.CHANGED_DATE, '%m/%e/%Y') AS CHANGED_DATE, M.MESSAGE, M.STICKY
				FROM EXP_MESSAGE M, EXP_USER U
				WHERE NOW() BETWEEN M.START_DATE AND M.END_DATE
				AND (COALESCE(M.OFFICE_CODE,0) = 0 OR COALESCE(M.OFFICE_CODE,0) IN($offices))
				AND (COALESCE(M.USER_GROUP,'none') = 'none' OR COALESCE(M.USER_GROUP,'none') IN('".
				implode("', '", explode(',', $_SESSION['EXT_GROUPS']))."'))
				AND M.CHANGED_BY = U.USER_CODE
				AND NOT EXISTS(SELECT USER_MESSAGE_CODE
					FROM EXP_USER_MESSAGE UM
					WHERE UM.USER_CODE = $code
					AND UM.MESSAGE_CODE = M.MESSAGE_CODE)");
			
			if( is_array($check) && count($check) > 0 ) {
				$result = array();
				foreach( $check as $row ) {
					$result[$row["MESSAGE_CODE"]] = array(
						'FROM' => $row["USERNAME"],
						'DATE' => $row["CHANGED_DATE"],
						'MESSAGE' => $row["MESSAGE"],
						'STICKY' => $row["STICKY"]
					);
				}
			}
		}

		return $result;
	}

	//! Mark my messages read
	public function mark_read( $code ) {
		if( $this->debug ) echo "<p>".__METHOD__.": entry, code = $code</p>";

		if( isset($_SESSION['EXT_USER_OFFICES']) )
			$offices = implode(', ', array_keys($_SESSION['EXT_USER_OFFICES']));
		else
			$offices = '0';

		$check = $this->database->get_multiple_rows("
			SELECT M.MESSAGE_CODE
			FROM EXP_MESSAGE M
			WHERE NOW() BETWEEN M.START_DATE AND M.END_DATE
			AND (COALESCE(M.OFFICE_CODE,0) = 0 OR COALESCE(M.OFFICE_CODE,0) IN($offices))
			AND (COALESCE(M.USER_GROUP,'none') = 'none' OR COALESCE(M.USER_GROUP,'none') IN('".
				implode("', '", explode(',', $_SESSION['EXT_GROUPS']))."'))
			AND M.STICKY = 0
			AND NOT EXISTS(SELECT USER_MESSAGE_CODE
				FROM EXP_USER_MESSAGE UM
				WHERE UM.USER_CODE = $code
				AND UM.MESSAGE_CODE = M.MESSAGE_CODE)");
		
		if( is_array($check) && count($check) > 0 ) {
			foreach( $check as $row ) {
				$message_code = $row["MESSAGE_CODE"];
				if( $this->debug ) echo "<p>".__METHOD__.": mark $message_code as read for user $code</p>";
				$this->user_message_table->add( array('USER_CODE' => $code, 'MESSAGE_CODE' => $message_code));
			}
		}
	}
}

class sts_user_message extends sts_table {

	// Constructor does not need the table name
	public function __construct( $database, $debug = false ) {

		$this->debug = $debug;
		$this->primary_key = "USER_MESSAGE_CODE";
		if( $this->debug ) echo "<p>Create sts_user_message</p>";
		parent::__construct( $database, USER_MESSAGE_TABLE, $debug);
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

$sts_form_addmessage_form = array(	//! $sts_form_addmessage_form
	'title' => '<span class="glyphicon glyphicon-envelope"></span> Add message',
	'action' => 'exp_addmessage.php',
	//'actionextras' => 'disabled',
	'cancel' => 'exp_listmessage.php',
	'name' => 'addmessage',
	'okbutton' => 'Save Changes',
	'saveadd' => 'Add Another',
	'cancelbutton' => 'Cancel',
		'layout' => '
	<div class="form-group">
		<div class="col-sm-12">
			<!-- OFFICE1 -->
			<div class="form-group">
				<label for="OFFICE_CODE" class="col-sm-2 control-label">#OFFICE_CODE#</label>
				<div class="col-sm-4">
					%OFFICE_CODE%
				</div>
				<div class="col-sm-6">
					<label>Send message to specific office (none = send to all)</label>
				</div>
			</div>
			<!-- OFFICE2 -->
			<div class="form-group">
				<label for="USER_GROUP" class="col-sm-2 control-label">#USER_GROUP#</label>
				<div class="col-sm-4">
					%USER_GROUP%
				</div>
				<div class="col-sm-6">
					<label>Send message to specific group (none = send to all)</label>
				</div>
			</div>
			<div class="form-group">
				<label for="STICKY" class="col-sm-2 control-label">#STICKY#</label>
				<div class="col-sm-2">
					%STICKY%
				</div>
				<div class="col-sm-6 col-sm-offset-2">
					<label>Sticky messages cannot be marked as read.<br>They will re-appear each login. Use sparingly.</label>
				</div>
			</div>
			<div class="form-group">
				<label for="START_DATE" class="col-sm-2 control-label">#START_DATE#</label>
				<div class="col-sm-4">
					%START_DATE%
				</div>
				<div class="col-sm-6">
					<label>Message is valid after this date.</label>
				</div>
			</div>
			<div class="form-group">
				<label for="END_DATE" class="col-sm-2 control-label">#END_DATE#</label>
				<div class="col-sm-4">
					%END_DATE%
				</div>
				<div class="col-sm-6">
					<label>Message is valid until this date.</label>
				</div>
			</div>
			<div class="form-group">
				<label for="MESSAGE" class="col-sm-2 control-label">#MESSAGE#</label>
				<div class="col-sm-8">
					%MESSAGE%
				</div>
			</div>
		</div>
	</div>
	
	'
);

$sts_form_editmessage_form = array( //!$sts_form_editmessage_form
	'title' => '<span class="glyphicon glyphicon-envelope"></span> Edit message',
	'action' => 'exp_editmessage.php',
	//'actionextras' => 'disabled',
	'cancel' => 'exp_listmessage.php',
	'name' => 'editmessage',
	'okbutton' => 'Save Changes to message',
	'cancelbutton' => 'Back to messages',
		'layout' => '
		%MESSAGE_CODE%
	<div class="form-group">
		<div class="col-sm-12">
			<!-- OFFICE1 -->
			<div class="form-group">
				<label for="OFFICE_CODE" class="col-sm-2 control-label">#OFFICE_CODE#</label>
				<div class="col-sm-4">
					%OFFICE_CODE%
				</div>
				<div class="col-sm-6">
					<label>Send message to specific office (none = send to all)</label>
				</div>
			</div>
			<!-- OFFICE2 -->
			<div class="form-group">
				<label for="USER_GROUP" class="col-sm-2 control-label">#USER_GROUP#</label>
				<div class="col-sm-4">
					%USER_GROUP%
				</div>
				<div class="col-sm-6">
					<label>Send message to specific group (none = send to all)</label>
				</div>
			</div>
			<div class="form-group">
				<label for="STICKY" class="col-sm-2 control-label">#STICKY#</label>
				<div class="col-sm-2">
					%STICKY%
				</div>
				<div class="col-sm-6 col-sm-offset-2">
					<label>Sticky messages cannot be marked as read.<br>They will re-appear each login. Use sparingly.</label>
				</div>
			</div>
			<div class="form-group">
				<label for="START_DATE" class="col-sm-2 control-label">#START_DATE#</label>
				<div class="col-sm-4">
					%START_DATE%
				</div>
				<div class="col-sm-6">
					<label>Message is valid after this date.</label>
				</div>
			</div>
			<div class="form-group">
				<label for="END_DATE" class="col-sm-2 control-label">#END_DATE#</label>
				<div class="col-sm-4">
					%END_DATE%
				</div>
				<div class="col-sm-6">
					<label>Message is valid until this date.</label>
				</div>
			</div>
			<div class="form-group">
				<label for="MESSAGE" class="col-sm-2 control-label">#MESSAGE#</label>
				<div class="col-sm-8">
					%MESSAGE%
				</div>
			</div>
		</div>
	</div>
	
	'
);

//! Field Specifications - For use with sts_form

$sts_form_add_message_fields = array(	//! $sts_form_add_message_fields
	'OFFICE_CODE' => array( 'label' => 'Office', 'format' => 'table',
		'table' => OFFICE_TABLE, 'key' => 'OFFICE_CODE', 'fields' => 'OFFICE_NAME' ),
	'USER_GROUP' => array( 'label' => 'Group', 'format' => 'enum' ),
	'STICKY' => array( 'label' => 'Sticky', 'format' => 'bool' ),
	'START_DATE' => array( 'label' => 'Start Date', 'format' => 'date', 'extras' => 'required' ),
	'END_DATE' => array( 'label' => 'End Date', 'format' => 'date', 'extras' => 'required' ),
	'MESSAGE' => array( 'label' => 'Message', 'format' => 'textarea', 
		'required' => true, 'extras' => 'required rows="6"' ),
);

$sts_form_edit_message_fields = array(	//! $sts_form_edit_message_fields
	'MESSAGE_CODE' => array( 'format' => 'hidden' ),
	'OFFICE_CODE' => array( 'label' => 'Office', 'format' => 'table',
		'table' => OFFICE_TABLE, 'key' => 'OFFICE_CODE', 'fields' => 'OFFICE_NAME' ),
	'USER_GROUP' => array( 'label' => 'Group', 'format' => 'enum' ),
	'STICKY' => array( 'label' => 'Sticky', 'format' => 'bool' ),
	'START_DATE' => array( 'label' => 'Start Date', 'format' => 'date', 'extras' => 'required' ),
	'END_DATE' => array( 'label' => 'End Date', 'format' => 'date', 'extras' => 'required' ),
	'MESSAGE' => array( 'label' => 'Message', 'format' => 'textarea', 
		'required' => true, 'extras' => 'required rows="6"' ),
);

//! Layout Specifications - For use with sts_result

$sts_result_message_layout = array(	//! $sts_result_message_layout
	'MESSAGE_CODE' => array( 'label' => 'Message#', 'format' => 'number', 'align' => 'right', 'length' => 90 ),
	'OFFICE_CODE' => array( 'label' => 'Office', 'format' => 'table',
		'table' => OFFICE_TABLE, 'key' => 'OFFICE_CODE', 'fields' => 'OFFICE_NAME' ),
	'USER_GROUP' => array( 'label' => 'Group', 'format' => 'text' ),
	'STICKY' => array( 'label' => 'Sticky', 'format' => 'bool', 'align' => 'center' ),
	'START_DATE' => array( 'label' => 'Start', 'format' => 'date' ),
	'END_DATE' => array( 'label' => 'End', 'format' => 'date' ),
	'MESSAGE' => array( 'label' => 'Message', 'format' => 'textarea', 'extras' => 'rows="6"' ),
	'SEEN' => array( 'label' => 'Seen by', 'format' => 'table',
		'snippet' => '(SELECT GROUP_CONCAT(U.USERNAME SEPARATOR \', \') FROM EXP_USER_MESSAGE UM, EXP_USER U
	    WHERE UM.MESSAGE_CODE = EXP_MESSAGE.MESSAGE_CODE AND U.USER_CODE = UM.USER_CODE)' ),
	'CHANGED_DATE' => array( 'label' => 'Changed', 'format' => 'timestamp-s', 'length' => 90 ),
	'CHANGED_BY' => array( 'label' => 'By', 'format' => 'table',
		'table' => USER_TABLE, 'key' => 'USER_CODE', 'fields' => 'USERNAME', 'link' => 'exp_edituser.php?CODE=' )
	
);

//! Edit/Delete Button Specifications - For use with sts_result

$sts_result_message_edit = array(	//! $sts_result_message_edit
	'title' => '<span class="glyphicon glyphicon-envelope"></span> Messages',
	'sort' => 'START_DATE asc',
	'cancel' => 'index.php',
	'add' => 'exp_addmessage.php',
	//'actionextras' => 'disabled',
	'addbutton' => 'Add message',
	'cancelbutton' => 'Back',
	'filters_html' => '<a class="btn btn-sm btn-default" href="exp_listcompany.php"><img src="images/company_icon.png" alt="company_icon" height="18"> Companies</a>',
	'rowbuttons' => array(
		array( 'url' => 'exp_editmessage.php?CODE=', 'key' => 'MESSAGE_CODE', 'label' => 'MESSAGE_CODE', 'tip' => 'Edit message ', 'icon' => 'glyphicon glyphicon-edit' ),
		array( 'url' => 'exp_deletemessage.php?CODE=', 'key' => 'MESSAGE_CODE', 'label' => 'MESSAGE_CODE', 'tip' => 'Delete message ', 'icon' => 'glyphicon glyphicon-trash', 'confirm' => 'yes' )
	)
);


?>
