<?php

// $Id: sts_client_activity_class.php 4697 2022-03-09 23:02:23Z duncan $
// Client activity class, sales/prospecting activity.

// no direct access
defined('_STS_INCLUDE') or die('Restricted access');

require_once( "sts_config.php" );
require_once( "sts_table_class.php" );
require_once( "sts_client_class.php" );
require_once( "sts_email_class.php" );
require_once( "sts_setting_class.php" );
require_once( "sts_user_log_class.php" );

class sts_client_activity extends sts_table {
	
	private $client_table;
	private $setting_table;
	private $user_log_table;
	private $cms_enabled;

	// Constructor does not need the table name
	public function __construct( $database, $debug = false ) {

		$this->debug = $debug;
		$this->primary_key = "ACTIVITY_CODE";
		if( $this->debug ) echo "<p>Create sts_client_activity</p>";
		$this->setting_table = sts_setting::getInstance($database, $debug);
		$this->cms_enabled = ($this->setting_table->get( 'option', 'CMS_ENABLED' ) == 'true');
		$this->user_log_table = sts_user_log::getInstance($database, $debug);

		parent::__construct( $database, CLIENT_ACTIVITY_TABLE, $debug);
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
    
    //! Create a client activity for the newly entered lead
    public function enter_lead( $lead, $comment = '' ) {
		if( $this->debug ) echo "<p>".__METHOD__.": lead = $lead</p>";
		$result = false;
		$this->client_table = sts_client::getInstance($this->database, $this->debug);
	    $check = $this->client_table->fetch_rows("CLIENT_CODE = $lead
	    	AND CLIENT_TYPE = 'lead'
	    	AND CURRENT_STATUS = ".$this->client_table->behavior_state['entry'],
	    	"CLIENT_CODE, CLIENT_NAME");
	    if( is_array($check) && count($check) == 1 &&
	    	isset($check[0]["CLIENT_CODE"]) && $check[0]["CLIENT_CODE"] == $lead ) {
		    $info = array(
		    	'CLIENT_CODE' => $lead,
		    	'ACTIVITY' => $this->client_table->behavior_state['entry'],
		    	'NEXT_STEP' => $this->client_table->behavior_state['assign']);
		    if( ! empty($comment) )
		    	$info['NOTE'] = $comment;
		    $result = $this->add($info);
			$this->user_log_table->log_event('sales', 'Add lead '.$check[0]["CLIENT_NAME"].' ('.$lead.')'.(empty($comment) ? '' : ' '.$comment));
	    }
		if( $this->debug ) echo "<p>".__METHOD__.": return $result</p>";
	    return $result;
    }

    //! Create a client activity record
    public function add_activity( $lead, $activity ) {
		if( $this->debug ) echo "<p>".__METHOD__.": lead = $lead</p>";
		
		$this->client_table = sts_client::getInstance($this->database, $this->debug);
		$check = $this->client_table->fetch_rows("CLIENT_CODE = $lead",
	    	"CLIENT_CODE, SALES_PERSON, CLIENT_NAME");

	    $info = array( 'CLIENT_CODE' => $lead,
	    	'ACTIVITY' => $activity );
	    
	    if( is_array($check) && count($check) == 1 &&
	    	! empty($check[0]["SALES_PERSON"]) )
	    	$info['SALES_PERSON'] = $check[0]["SALES_PERSON"];
	    	
		$this->user_log_table->log_event('sales', 'client = <a href="exp_editclient.php?CODE='.$lead.'">'.$check[0]["CLIENT_NAME"].'</a>, activity = '.$this->client_table->state_name[$activity]);

	    $result = $this->add($info);
		if( $this->debug ) echo "<p>".__METHOD__.": return $result</p>";
	    return $result;
    }

	//! Prepare and send calendar appt email
	public function calendar_appt( $activity ) {
		global $sts_crm_root;
		if( $this->debug ) echo "<p>".__METHOD__.": entry, activity = $activity</p>";
		
		$this->client_table = sts_client::getInstance($this->database, $this->debug);

		$ci = $this->database->get_one_row("
			SELECT c.*,
			(SELECT CLIENT_NAME FROM EXP_CLIENT WHERE CLIENT_CODE = a.CLIENT_CODE) AS CLIENT_NAME
			FROM EXP_CONTACT_INFO c, EXP_CLIENT_ACTIVITY a 
			WHERE c.CONTACT_INFO_CODE = a.CONTACT_CODE
			AND a.ACTIVITY_CODE = ".$activity);

		$check = $this->fetch_rows("ACTIVITY_CODE = ".$activity,
			"NEXT_STEP, DUE_BY, CLIENT_CODE, TZ,
			(SELECT USERNAME FROM EXP_USER WHERE USER_CODE = EXP_CLIENT_ACTIVITY.CREATED_BY) AS USERNAME,
			(SELECT EMAIL FROM EXP_USER WHERE USER_CODE = EXP_CLIENT_ACTIVITY.SALES_PERSON) AS EMAIL");

		if( $this->debug ) {
			echo "<pre>ci, check\n";
			var_dump($ci, $check);
			echo "</pre>";
		}

		if( is_array($check) && count($check) == 1 &&
			! empty($check[0]["EMAIL"]) &&
			! empty($check[0]["NEXT_STEP"]) &&
			! empty($check[0]["DUE_BY"]) &&
			! empty($check[0]["TZ"]) ) {
			$to = $check[0]["EMAIL"];
			
			$next_activity = $this->client_table->state_behavior[$check[0]["NEXT_STEP"]];
			
			if( $this->debug ) echo "<p>".__METHOD__.": next_activity = $next_activity</p>";
			if( in_array($next_activity, array('call', 'sentinfo', 'sentquote'))) {	
				switch( $next_activity ) {
					case 'call': $action = 'Call '.$ci["CLIENT_NAME"]; break;
					case 'sentinfo': $action = 'Send info to '.$ci["CLIENT_NAME"]; break;
					case 'sentquote': $action = 'Send quote to '.$ci["CLIENT_NAME"]; break;
				}
				
				$desc = $action;
				if( ! empty($ci["CONTACT_NAME"]))
					$desc .= '\n'.$ci["CONTACT_NAME"];
				if( ! empty($ci["PHONE_OFFICE"])) {
					$desc .= '\n'.$ci["PHONE_OFFICE"];
					if( ! empty($c["PHONE_EXT"]))
						$desc .= 'x'.$ci["PHONE_EXT"];
		
					if( ! empty($c["PHONE_CELL"]))
						$desc .= '\n'.$ci["PHONE_CELL"];
				}
				if( ! empty($ci["EMAIL"]))
					$desc .= '\n'.$ci["EMAIL"];
				if( ! empty($_POST["NOTE"]))
					$desc .= '\n'.$_POST["NOTE"];
				
				$url = $sts_crm_root.
					"/exp_client_state.php?CODE=".$check[0]["CLIENT_CODE"]."&STATE=".
					$check[0]["NEXT_STEP"]."&CSTATE=".$activity;
					
				$desc .= '\n\nClick here: '.$url.
					'\n\nUser: '.$check[0]["USERNAME"];
	
				$desc = wordwrap($desc, 75, "\n ");
	
				$email = sts_email::getInstance($this->database, $this->debug);
				$email->send_calendar_appt( $to, $action, $check[0]["DUE_BY"],
					$desc, $check[0]["TZ"] );
			}
		}		
	}
	
	//! Display overdue activity
	public function overdue( $sales_person ) {
		$result = '';
		if( $this->cms_enabled ) {
			$overdue = $this->database->get_multiple_rows("
				SELECT A.CLIENT_CODE, A.ACTIVITY_CODE AC, S.STATUS_STATE,
				C.CLIENT_NAME, A.DUE_BY, DATEDIFF(CURRENT_TIMESTAMP, A.DUE_BY) AS DAYS
				FROM EXP_CLIENT_ACTIVITY A, EXP_STATUS_CODES S, EXP_CLIENT C, EXP_USER U
				WHERE A.SALES_PERSON = $sales_person
				AND A.SALES_PERSON = U.USER_CODE
				AND U.USER_GROUPS LIKE '%sales%'
                AND A.ACTIVITY_CODE = (SELECT MAX(B.ACTIVITY_CODE)
					FROM EXP_CLIENT_ACTIVITY B
                    WHERE A.CLIENT_CODE = B.CLIENT_CODE
                    LIMIT 1)
				AND A.NEXT_STEP = S.STATUS_CODES_CODE
				AND C.CLIENT_CODE = A.CLIENT_CODE
				AND A.DUE_BY IS NOT NULL
				AND A.DUE_BY < CURRENT_TIMESTAMP
				GROUP BY A.CLIENT_CODE
				ORDER BY A.DUE_BY ASC");
			if( is_array($overdue) && count($overdue) > 0 ) {
				$actions = array();
				foreach($overdue as $row) {
					$actions[] = date("m/d H:i", strtotime($row["DUE_BY"])).' - '.
						'<a href="exp_editclient.php?CODE='.$row["CLIENT_CODE"].'">'.
						$row["STATUS_STATE"].($row["STATUS_STATE"] <> 'Call' ? ' to' : '').
						' '.$row["CLIENT_NAME"].'</a> ('.$row["DAYS"].' days overdue)';
				}
				$result = '<div class="alert alert-danger alert-tighter" role="alert">
				<p><a class="btn btn-sm btn-danger" data-toggle="collapse" href="#collapseSales" aria-expanded="false" aria-controls="collapseSales"><span class="glyphicon glyphicon-warning-sign"></span> '.plural(count($overdue), ' Overdue Sales Activity').'</a></p>
				<div class="collapse" id="collapseSales">
				<p>'.implode("<br>\n", $actions).'</p>
				</div>
				</div>';

			}		
		}
		return $result;
	}
}

//! Form Specifications - For use with sts_form

$sts_form_edit_client_activity_form = array(	//! $sts_form_edit_client_activity_form
	'title' => '<span class="glyphicon glyphicon-user"></span> Lead/Prospect/Client Activity',
	'action' => 'exp_editclient_activity.php',
	//'actionextras' => 'disabled',
	'cancel' => 'exp_editclient.php?CODE=%CLIENT_CODE%',
	'name' => 'editclient_activity',
	'okbutton' => 'Save Changes',
	'saveadd' => 'Save/Next',
	'cancelbutton' => 'Back',
		'layout' => '
	%ACTIVITY_CODE%
	%TZ%
	<div class="form-group">
		<div class="col-sm-6">
			<div class="form-group tighter">
				<label for="CLIENT_CODE" class="col-sm-4 control-label">#CLIENT_CODE#</label>
				<div class="col-sm-6">
					%CLIENT_CODE%
				</div>
			</div>
			<div class="form-group tighter">
				<label for="SALES_PERSON" class="col-sm-4 control-label">#SALES_PERSON#</label>
				<div class="col-sm-6">
					%SALES_PERSON%
				</div>
			</div>
			<div class="form-group tighter">
				<label for="ACTIVITY" class="col-sm-4 control-label">#ACTIVITY#</label>
				<div class="col-sm-6">
					%ACTIVITY%
				</div>
			</div>
			<!-- CONTACT01 -->
			<div class="form-group tighter">
				<label for="CONTACT_CODE" class="col-sm-4 control-label">#CONTACT_CODE#</label>
				<div class="col-sm-6">
					%CONTACT_CODE%
				</div>
			</div>
			<!-- CONTACT02 -->
			<!-- CALL01 -->
			<div class="form-group tighter">
				<label for="CALL_TYPE" class="col-sm-4 control-label">#CALL_TYPE#</label>
				<div class="col-sm-6">
					%CALL_TYPE%
				</div>
			</div>
			<div class="form-group tighter">
				<label for="OBJECTIVE" class="col-sm-4 control-label">#OBJECTIVE#</label>
				<div class="col-sm-6">
					%OBJECTIVE%
				</div>
			</div>
			<div class="form-group tighter">
				<label for="OUTCOME" class="col-sm-4 control-label">#OUTCOME#</label>
				<div class="col-sm-6">
					%OUTCOME%
				</div>
			</div>
			<!-- CALL02 -->
			<div class="form-group tighter">
				<label for="NEXT_STEP" class="col-sm-4 control-label">#NEXT_STEP#</label>
				<div class="col-sm-6">
					%NEXT_STEP%
				</div>
			</div>
			<div class="form-group tighter">
				<label for="DUE_BY" class="col-sm-4 control-label">#DUE_BY#</label>
				<div class="col-sm-6">
					%DUE_BY%
				</div>
			</div>
		</div>
		<div class="col-sm-6">
			<!-- CALL03 -->
			<div class="alert alert-info">
				<label for="RATING" class="col-sm-6 control-label">#RATING#</label>
				<div class="col-sm-4">
					%RATING%
				</div>
				<br clear="all">
				<p>A = Good fit, Value orientated, medium to high volumes</p>
				<p>B = Good fit, Value orientated, low volumes or Good fit price orientated</p>
				<p>C = Qualified fit, price orientated, mediume to high volumes</p>
				<p>D = Qualified fit, price orientated, low volumes</p>
			</div>
			<!-- CALL04 -->
			<!-- CONTACT03 -->
			<div id="CONTACT_INFO" class="alert alert-success">
			</div>
			<!-- CONTACT04 -->
		</div>
	</div>
	<div class="form-group">
		<div class="col-sm-6">
			<div class="form-group tighter">
				<div class="col-sm-12">
					<label for="NOTE" class="control-label">#NOTE#</label>
					%NOTE%
				</div>
			</div>
		</div>
		<div class="col-sm-6">
			<div class="form-group tighter">
				<div class="col-sm-12">
					<label for="CLIENT_NOTES" class="control-label">#CLIENT_NOTES#</label>
					%CLIENT_NOTES%
				</div>
			</div>
		</div>
	</div>
	
	'
);

$sts_form_send_formmail_form = array(	//! $sts_form_send_formmail_form
	'title' => '<span class="glyphicon glyphicon-envelope"></span> Send Form Mail',
	'action' => 'exp_editclient_activity.php?EMAIL',
	//'actionextras' => 'disabled',
	'cancel' => 'exp_editclient_activity.php?CANCEL=%ACTIVITY_CODE%&CODE=%CLIENT_CODE%',
	'name' => 'send_formmail',
	'okbutton' => 'Send Email',
	//'saveadd' => 'Save/Next',
	'cancelbutton' => 'Cancel',
		'layout' => '
	%ACTIVITY_CODE%
	<div class="form-group">
		<div class="form-group tighter">
			<label for="CLIENT_CODE" class="col-sm-2 control-label">#CLIENT_CODE#</label>
			<div class="col-sm-6">
				%CLIENT_CODE%
			</div>
		</div>
		<div class="form-group tighter">
			<label for="SALES_PERSON" class="col-sm-2 control-label">#SALES_PERSON#</label>
			<div class="col-sm-6">
				%SALES_PERSON%
			</div>
		</div>
		<div class="form-group tighter">
			<label for="CONTACT_CODE" class="col-sm-2 control-label">#CONTACT_CODE#</label>
			<div class="col-sm-6">
				%CONTACT_CODE%
			</div>
		</div>
		<div class="form-group tighter">
			<label for="EMAIL_TEMPLATE" class="col-sm-2 control-label">#EMAIL_TEMPLATE#</label>
			<div class="col-sm-6">
				%EMAIL_TEMPLATE%
			</div>
		</div>
	</div>
	'
);

//! Field Specifications - For use with sts_form

$sts_form_edit_client_activity_fields = array( //! $sts_form_edit_client_activity_fields
	'ACTIVITY_CODE' => array( 'format' => 'hidden' ),
	'CLIENT_CODE' => array( 'label' => 'Company Name', 'format' => 'table',
		'table' => CLIENT_TABLE, 'key' => 'CLIENT_CODE', 'fields' => 'CLIENT_NAME',
		'static' => true ),
	'SALES_PERSON' => array( 'label' => 'Sales Person', 'format' => 'table',
		'table' => USER_TABLE, 'key' => 'USER_CODE', 'fields' => 'USERNAME',
		'condition' => 'USER_GROUPS like \'%'.EXT_GROUP_SALES.'%\'',
		'nolink' => true ), // SCR# 46 Allow re-assign sales person
	'ACTIVITY' => array( 'label' => 'Activity', 'format' => 'table',
		'table' => STATUS_CODES_TABLE, 'key' => 'STATUS_CODES_CODE', 'fields' => 'STATUS_STATE',
		'condition' => 'SOURCE_TYPE = \'client\'', 'static' => true ),
	'NEXT_STEP' => array( 'label' => 'Next step', 'format' => 'table',
		'table' => STATUS_CODES_TABLE, 'key' => 'STATUS_CODES_CODE', 'fields' => 'STATUS_STATE',
		'condition' => 'SOURCE_TYPE = \'client\'', 'nolink' => true ),
	'DUE_BY' => array( 'label' => 'Next step Due', 'format' => 'timestamp' ),
	'TZ' => array( 'format' => 'hidden' ),
	'CALL_TYPE' => array( 'label' => 'Call type', 'format' => 'table',
		'table' => ITEM_LIST_TABLE, 'key' => 'ITEM_CODE', 'fields' => 'ITEM',
		'condition' => 'ITEM_TYPE = \'Call type\'' ),
	'CONTACT_CODE' => array( 'label' => 'Contact', 'format' => 'table',
		'table' => CONTACT_INFO_TABLE, 'key' => 'CONTACT_INFO_CODE',
		'fields' => 'CONTACT_NAME,PHONE_OFFICE,PHONE_CELL',
		'condition' => 'TBD' ),
	'OBJECTIVE' => array( 'label' => 'Objective', 'format' => 'table',
		'table' => ITEM_LIST_TABLE, 'key' => 'ITEM_CODE', 'fields' => 'ITEM',
		'condition' => 'ITEM_TYPE = \'Objective\'' ),
	'OUTCOME' => array( 'label' => 'Outcome', 'format' => 'table',
		'table' => ITEM_LIST_TABLE, 'key' => 'ITEM_CODE', 'fields' => 'ITEM',
		'condition' => 'ITEM_TYPE = \'Outcome\'' ),
	'RATING' => array( 'label' => 'Client Rating', 'format' => 'enum' ),
	'CLIENT_NOTES' => array( 'label' => 'Client Notes (edit in client screen)', 'format' => 'textarea',
		'value' => 'TBD', 'extras' => 'rows="6" readonly' ),
	'NOTE' => array( 'label' => 'Call Notes', 'format' => 'textarea', 'extras' => 'rows="6"' ),
);

$sts_form_send_formmail_fields = array( //! $sts_form_send_formmail_fields
	'ACTIVITY_CODE' => array( 'format' => 'hidden' ),
	'CLIENT_CODE' => array( 'label' => 'Company Name', 'format' => 'table',
		'table' => CLIENT_TABLE, 'key' => 'CLIENT_CODE', 'fields' => 'CLIENT_NAME',
		'static' => true ),
	'SALES_PERSON' => array( 'label' => 'From: (Sales Person)', 'format' => 'table',
		'table' => USER_TABLE, 'key' => 'USER_CODE', 'fields' => 'USERNAME',
		'condition' => 'USER_GROUPS like \'%'.EXT_GROUP_SALES.'%\'',
		'static' => true ),
	'CONTACT_CODE' => array( 'label' => 'To: (Contact)', 'format' => 'table',
		'table' => CONTACT_INFO_TABLE, 'key' => 'CONTACT_INFO_CODE',
		'fields' => 'CONTACT_NAME,EMAIL',
		'condition' => "TBD" ),
	'EMAIL_TEMPLATE' => array( 'label' => 'Email Template', 'format' => 'table',
		'table' => FORMMAIL_TABLE, 'key' => 'FORMMAIL_CODE',
		'fields' => 'FORMMAIL_NAME' ),
);


//! Layout Specifications - For use with sts_result

$sts_result_client_activity_layout = array(
	'ACTIVITY_CODE' => array( 'format' => 'hidden' ),
	'CREATED_DATE' => array( 'label' => 'Date', 'format' => 'datetime' ),
	'SALES_PERSON' => array( 'label' => 'Sales', 'format' => 'table',
		'table' => USER_TABLE, 'key' => 'USER_CODE', 'fields' => 'USERNAME',
		'condition' => 'USER_GROUPS like \'%'.EXT_GROUP_SALES.'%\'', 'link' => 'exp_edituser.php?CODE=' ),
	'ACTIVITY' => array( 'label' => 'Activity', 'format' => 'table',
		'table' => STATUS_CODES_TABLE, 'key' => 'STATUS_CODES_CODE', 'fields' => 'STATUS_STATE',
		'condition' => 'SOURCE_TYPE = \'client\'', 'extras' => 'readonly' ),
	'NEXT_STEP' => array( 'label' => 'Next step', 'format' => 'table',
		'table' => STATUS_CODES_TABLE, 'key' => 'STATUS_CODES_CODE', 'fields' => 'STATUS_STATE',
		'condition' => 'SOURCE_TYPE = \'client\'' ),
	'DUE_BY' => array( 'label' => 'Due', 'format' => 'timestamp-s' ),
	'CALL_TYPE' => array( 'label' => 'Call type', 'format' => 'table',
		'table' => ITEM_LIST_TABLE, 'key' => 'ITEM_CODE', 'fields' => 'ITEM',
		'condition' => 'ITEM_TYPE = \'Call type\'' ),
	'NOTE' => array( 'label' => 'Notes', 'format' => 'textarea' ),
	'EMAIL_TEMPLATE' => array( 'label' => 'Email Template', 'format' => 'table',
		'table' => FORMMAIL_TABLE, 'key' => 'FORMMAIL_CODE',
		'fields' => 'FORMMAIL_NAME' ),
	'OBJECTIVE' => array( 'label' => 'Objective', 'format' => 'table',
		'table' => ITEM_LIST_TABLE, 'key' => 'ITEM_CODE', 'fields' => 'ITEM',
		'condition' => 'ITEM_TYPE = \'Objective\'' ),
	'OUTCOME' => array( 'label' => 'Outcome', 'format' => 'table',
		'table' => ITEM_LIST_TABLE, 'key' => 'ITEM_CODE', 'fields' => 'ITEM',
		'condition' => 'ITEM_TYPE = \'Outcome\'' ),
	'CHANGED_DATE' => array( 'label' => 'Changed', 'format' => 'datetime' ),
	'CHANGED_BY' => array( 'label' => 'By', 'format' => 'table',
		'table' => USER_TABLE, 'key' => 'USER_CODE', 'fields' => 'USERNAME', 'link' => 'exp_edituser.php?CODE=' )

);

$sts_result_sales_activity_layout = array(
	'ACTIVITY_CODE' => array( 'format' => 'hidden' ),
	'CREATED_DATE' => array( 'label' => 'Date', 'format' => 'datetime' ),
	'CLIENT_CODE' => array( 'label' => 'Company Name', 'format' => 'table',
		'table' => CLIENT_TABLE, 'key' => 'CLIENT_CODE', 'fields' => 'CLIENT_NAME',
		'link' => 'exp_editclient.php?CODE=' ),
	'SALES_PERSON' => array( 'label' => 'Sales Person', 'format' => 'table',
		'table' => USER_TABLE, 'key' => 'USER_CODE', 'fields' => 'USERNAME',
		'condition' => 'USER_GROUPS like \'%'.EXT_GROUP_SALES.'%\'', 'link' => 'exp_edituser.php?CODE=' ),
	'ACTIVITY' => array( 'label' => 'Activity', 'format' => 'table',
		'table' => STATUS_CODES_TABLE, 'key' => 'STATUS_CODES_CODE', 'fields' => 'STATUS_STATE',
		'condition' => 'SOURCE_TYPE = \'client\'', 'link' => 'exp_editclient_activity.php?CODE=',
		'link_key' => 'ACTIVITY_CODE' ),
	'NEXT_STEP' => array( 'label' => 'Next step', 'format' => 'table',
		'table' => STATUS_CODES_TABLE, 'key' => 'STATUS_CODES_CODE', 'fields' => 'STATUS_STATE',
		'condition' => 'SOURCE_TYPE = \'client\'' ),
	'DUE_BY' => array( 'label' => 'Due', 'format' => 'timestamp-s' ),
	'CALL_TYPE' => array( 'label' => 'Call type', 'format' => 'table',
		'table' => ITEM_LIST_TABLE, 'key' => 'ITEM_CODE', 'fields' => 'ITEM',
		'condition' => 'ITEM_TYPE = \'Call type\'' ),
	'CONTACT_CODE' => array( 'label' => 'Contact', 'format' => 'table',
		'table' => CONTACT_INFO_TABLE, 'key' => 'CONTACT_INFO_CODE',
		'fields' => 'CONTACT_NAME', 'link' => 'exp_editcontact_info.php?CODE=' ),
	'NOTE' => array( 'label' => 'Notes', 'format' => 'textarea' ),
	'OBJECTIVE' => array( 'label' => 'Objective', 'format' => 'table',
		'table' => ITEM_LIST_TABLE, 'key' => 'ITEM_CODE', 'fields' => 'ITEM',
		'condition' => 'ITEM_TYPE = \'Objective\'' ),
	'OUTCOME' => array( 'label' => 'Outcome', 'format' => 'table',
		'table' => ITEM_LIST_TABLE, 'key' => 'ITEM_CODE', 'fields' => 'ITEM',
		'condition' => 'ITEM_TYPE = \'Outcome\'' ),
	'CHANGED_DATE' => array( 'label' => 'Changed', 'format' => 'datetime' ),
	'CHANGED_BY' => array( 'label' => 'By', 'format' => 'table',
		'table' => USER_TABLE, 'key' => 'USER_CODE', 'fields' => 'USERNAME', 'link' => 'exp_edituser.php?CODE=' )

);

//! Edit/Delete Button Specifications - For use with sts_result

$sts_result_client_activity_edit = array(
	'title' => '<span class="glyphicon glyphicon-user"></span> History',
	'sort' => 'ACTIVITY_CODE desc',
	//'cancel' => 'index.php',
	//'add' => 'exp_addtrailer.php',
	//'actionextras' => 'disabled',
	//'addbutton' => 'Add Trailer',
	//'cancelbutton' => 'Back',
	'rowbuttons' => array(
		array( 'url' => 'exp_editclient_activity.php?CODE=', 'key' => 'ACTIVITY_CODE', 'label' => 'ACTIVITY', 'tip' => 'Edit activity ', 'icon' => 'glyphicon glyphicon-edit' ),
	),
);

$sts_result_sales_activity_edit = array(
	'title' => '<span class="glyphicon glyphicon-user"></span> Sales Activity In The Last 7 days',
	'sort' => 'CREATED_DATE asc',
	'cancel' => 'index.php',
	//'add' => 'exp_addtrailer.php',
	//'actionextras' => 'disabled',
	//'addbutton' => 'Add Trailer',
	'cancelbutton' => 'Back',
);


?>
