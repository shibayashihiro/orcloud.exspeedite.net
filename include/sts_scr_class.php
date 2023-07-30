<?php

// no direct access
defined('_STS_INCLUDE') or die('Restricted access');

require_once( "sts_table_class.php" );

require_once( "sts_email_class.php" );
require_once( "sts_user_log_class.php" );

class sts_scr extends sts_table {

	public $label;
	public $matches;
	public $last_activity;

	//! For state changes
	public $state_name;
	public $state_behavior;
	public $name_state;
	public $behavior_state;
	public $state_change_error;
	public $state_change_level;
	public $state_change_post;
	protected $user_log;
	
	// Default Cc: recipients
	private $default_cc = array('selliott@strongtco.com', 'duncan@strongtco.com');

	// Constructor does not need the table name
	public function __construct( $database, $debug = false ) {

		$this->debug = $debug;
		$this->primary_key = "SCR_CODE";
		if( $this->debug ) echo "<p>Create sts_scr</p>";

		$this->user_log = sts_user_log::getInstance($database, $debug);
		parent::__construct( $database, SCR_TABLE, $debug);
		$this->load_states();
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

	private function load_states() {
		
		$cached = $this->cache->get_state_table( 'scr' );
		
		if( is_array($cached) && count($cached) > 0 ) {
			$this->states = $cached;
		} else {
			$this->states = $this->database->get_multiple_rows("select STATUS_CODES_CODE, STATUS_STATE, BEHAVIOR, STATUS_DESCRIPTION, PREVIOUS
				FROM EXP_STATUS_CODES
				WHERE SOURCE_TYPE = 'scr'
				ORDER BY STATUS_CODES_CODE ASC");
			assert( count($this->states)." > 0", "Unable to load states for scrs" );
		}
		
		$this->state_name = array();
		$this->state_behavior = array();
		foreach( $this->states as $row ) {
			$this->state_name[$row['STATUS_CODES_CODE']] = $row['STATUS_STATE'];
			$this->state_behavior[$row['STATUS_CODES_CODE']] = $row['BEHAVIOR'];
			$this->name_state[$row['STATUS_STATE']] = $row['STATUS_CODES_CODE'];
			$this->behavior_state[$row['BEHAVIOR']] = $row['STATUS_CODES_CODE'];
		}
	}
	
	public function following_states( $this_state ) {
	
		$following = array();
		foreach( $this->states as $row ) {
			$matched_previous = false;
			foreach( explode(',', $row['PREVIOUS']) as $possible ) {
				if( $possible == $this_state ) {
					$matched_previous = true;
					break;
				}
			}
			if( $matched_previous ) {
				$following[] = array( 'CODE' => $row['STATUS_CODES_CODE'], 
					'BEHAVIOR' => $row['BEHAVIOR'],
					'DESCRIPTION' => $row['STATUS_DESCRIPTION'], 
					'STATUS' => $row['STATUS_STATE']);
			}
		}
		
		return $following;
	}
	
	private function add_cc( $em, $orig_em, &$cc ) {
		if( ! empty($em) && $em <> $orig_em && ! in_array($em, $cc)) {
			$cc[] = $em;
		}
	}
	
	//! Announce via email that an SCR changed state
	public function email_announce( $pk ) {
		global $sts_crm_root;
		$result = false;
		$cc = '';
		if( $this->debug ) echo "<p>".__METHOD__.": SCR $pk</p>";
		
		$check = $this->fetch_rows( $this->primary_key.' = '.$pk,
			"TITLE, SCR_DESCRIPTION, CURRENT_STATUS,
			ORIGINATOR, ASSIGNED_DEV, ASSIGNED_QA, CHANGED_BY,
			ANALYSIS, DEV_NOTES, QA_NOTES, BILLABLE, ESTIMATED_HOURS, ACTUAL_HOURS,
			(SELECT EMAIL FROM EXP_USER WHERE USER_CODE = ORIGINATOR) AS O_EMAIL,
			(SELECT EMAIL FROM EXP_USER WHERE USER_CODE = ASSIGNED_DEV) AS D_EMAIL,
			(SELECT EMAIL FROM EXP_USER WHERE USER_CODE = ASSIGNED_QA) AS Q_EMAIL,
			(SELECT EMAIL FROM EXP_USER WHERE USER_CODE = EXP_SCR.CHANGED_BY) AS C_EMAIL,
			(SELECT USERNAME FROM EXP_USER WHERE USER_CODE = EXP_SCR.CHANGED_BY) AS CHANGED");
		
		if( is_array($check) && count($check) == 1 ) {
			$to = $check[0]["O_EMAIL"];
			
			$cc_list = array();
			foreach( $this->default_cc as $em )
				$this->add_cc( $em, $to, $cc_list );
				
			$this->add_cc( $check[0]["D_EMAIL"], $to, $cc_list );
			$this->add_cc( $check[0]["Q_EMAIL"], $to, $cc_list );
			$this->add_cc( $check[0]["C_EMAIL"], $to, $cc_list );
			
			if( count($cc_list) > 0 ) {
				$cc = implode(', ', $cc_list);
			}
			if( $this->debug ) {
				echo "<pre>";
				var_dump($this->default_cc);
				var_dump($to);
				var_dump($cc_list);
				var_dump($cc);
				echo "</pre>";
			}

			$subject = 'SCR # '.$pk.' changed state to '.$this->state_name[$check[0]["CURRENT_STATUS"]];

			$body = '<h2>SCR # '.$pk.' - '.$check[0]["TITLE"].'<br>
Status: '.$this->state_name[$check[0]["CURRENT_STATUS"]].
' (changed by '.$check[0]["CHANGED"].')</h2>
<p>Link: '.$sts_crm_root.'/exp_editscr.php?CODE='.$pk.'</p>
<br>
<strong>Description:</strong>
<pre>
'.$check[0]["SCR_DESCRIPTION"].'
</pre>';
			
			if( ! empty($check[0]["ANALYSIS"]) )
				$body .= '<br>
<strong>Analysis:</strong>
<pre>
'.$check[0]["ANALYSIS"].'
</pre>';
			
			if( isset($check[0]["BILLABLE"]) && $check[0]["BILLABLE"] == 1) {
				if( isset($check[0]["ESTIMATED_HOURS"]) && $check[0]["ESTIMATED_HOURS"] > 0 )
					$body .= '<br>
<strong>Estimated hours:</strong>
<pre>
'.$check[0]["ESTIMATED_HOURS"].'
</pre>';
				if( isset($check[0]["ACTUAL_HOURS"]) && $check[0]["ACTUAL_HOURS"] > 0 )
					$body .= '<br>
<strong>Actual hours:</strong>
<pre>
'.$check[0]["ACTUAL_HOURS"].'
</pre>';
			}
			
			if( in_array($this->state_behavior[$check[0]["CURRENT_STATUS"]],
				array('inprogress', 'fixed', 'tested', 'build')) ||
				! empty($check[0]["DEV_NOTES"]))
				$body .= '<br>
<strong>Dev Notes:</strong>
<pre>
'.$check[0]["DEV_NOTES"].'
</pre>';
			
			if( in_array($this->state_behavior[$check[0]["CURRENT_STATUS"]],
				array('tested', 'build')) ||
				! empty($check[0]["QA_NOTES"]))
				$body .= '<br>
<strong>Test Notes:</strong>
<pre>
'.$check[0]["QA_NOTES"].'
</pre>';
			
			$email = sts_email::getInstance($this->database, $this->debug);
			$result = $email->send_email( $to, $cc, $subject, $body );
		}
		
		return $result;
	}
	
	public function state_change_ok( $pk, $current_state, $state ) {

		if( $this->debug ) echo "<p>".__METHOD__.": pk = $pk, current_state = $current_state (".$this->state_behavior[$current_state]."), state = $state (".$this->state_behavior[$state].")</p>";

		$this->state_change_error = '';
		$this->state_change_level = EXT_ERROR_WARNING;	// Default to warning
		$ok_to_continue = false;
		
		// Preconditions for changing state
		switch( $this->state_behavior[$state] ) {
			case 'assign': //! assign
				$check = $this->fetch_rows( $this->primary_key.' = '.$pk,
					"RESOLUTION, ASSIGNED_DEV");

				$ok_to_continue = is_array($check) && count($check) == 1 &&
					isset($check[0]["RESOLUTION"]) && $check[0]["RESOLUTION"] == 'development';
				if( ! $ok_to_continue ) {
					$this->state_change_error = "Set the resolution to development";
				} else {
					$ok_to_continue = ! empty($check[0]["ASSIGNED_DEV"]);
					if( ! $ok_to_continue ) {
						$this->state_change_error = "Select a developer";
					}
				}
				break;
				
			case 'dead':	//! dead
				$check = $this->fetch_rows( $this->primary_key.' = '.$pk, "RESOLUTION");
				$ok_to_continue = $this->state_behavior[$current_state] <> 'dead';
				if( ! $ok_to_continue ) {
					$this->state_change_error = "Already dead";
				} else {
					$ok_to_continue = is_array($check) && count($check) == 1 &&
						isset($check[0]["RESOLUTION"]) && in_array($check[0]["RESOLUTION"], 
						array('duplicate', 'works ok', 'not fixed',
						'deferred', 'rejected', 'cancelled', 'invalid'));
					if( ! $ok_to_continue ) {
						$this->state_change_error = "Set the resolution to reason for rejection";
					}
				}
				break;

			case 'inprogress': //! inprogress
			case 'fixed': //! fixed
				$check = $this->fetch_rows( $this->primary_key.' = '.$pk, "DEV_NOTES");
				$ok_to_continue = is_array($check) && count($check) == 1 &&
						! empty($check[0]["DEV_NOTES"]);
				if( ! $ok_to_continue ) {
					$this->state_change_error = "Fill in some dev notes";
				}
				break;

			case 'tested': //! tested
				$check = $this->fetch_rows( $this->primary_key.' = '.$pk, "QA_NOTES");
				$ok_to_continue = is_array($check) && count($check) == 1 &&
						! empty($check[0]["QA_NOTES"]);
				if( ! $ok_to_continue ) {
					$this->state_change_error = "Fill in some testing notes";
				}
				break;

			case 'build': //! build
				$check = $this->fetch_rows( $this->primary_key.' = '.$pk, "FIXED_IN_RELEASE");
				$ok_to_continue = is_array($check) && count($check) == 1 &&
						! empty($check[0]["FIXED_IN_RELEASE"]);
				if( ! $ok_to_continue ) {
					$this->state_change_error = "Fill in a release number";
				}
				break;

			case 'unapproved': //! unapproved - Awaiting customer approval to proceed with custom/billable work
				$check = $this->fetch_rows( $this->primary_key.' = '.$pk, "ANALYSIS, BILLABLE, ESTIMATED_HOURS");
				$ok_to_continue = is_array($check) && count($check) == 1 &&
						! empty($check[0]["ANALYSIS"]);
				if( ! $ok_to_continue ) {
					$this->state_change_error = "Fill in some analysis notes - what needs to be done?";
				} else {
					$ok_to_continue = isset($check[0]["BILLABLE"]) && $check[0]["BILLABLE"] == 1;
					if( ! $ok_to_continue ) {
						$this->state_change_error = "Make sure billable is checked";
					} else {
						$ok_to_continue = ! empty($check[0]["ESTIMATED_HOURS"]);
						if( ! $ok_to_continue ) {
							$this->state_change_error = "Include estimated hours - how long will it take?";
						}
					}	
				}
				break;

			case 'sentinfo': //! sentinfo - Awaiting customer acceptance for custom/billable work
				$check = $this->fetch_rows( $this->primary_key.' = '.$pk, "QA_NOTES, ACTUAL_HOURS");
				$ok_to_continue = is_array($check) && count($check) == 1 &&
						! empty($check[0]["QA_NOTES"]);
				if( ! $ok_to_continue ) {
					$this->state_change_error = "Fill in some testing notes - how did we test it?";
				} else {
					$ok_to_continue = ! empty($check[0]["ACTUAL_HOURS"]);
					if( ! $ok_to_continue ) {
						$this->state_change_error = "Include actual hours - how long did it take?";
					}	
				}
				break;

			case 'docked': //! docked - More info needed
			case 'late': //! late - Wish list
				$check = $this->fetch_rows( $this->primary_key.' = '.$pk, "ANALYSIS");
				$ok_to_continue = is_array($check) && count($check) == 1 &&
						! empty($check[0]["ANALYSIS"]);
				if( ! $ok_to_continue ) {
					$this->state_change_error = "Fill in some analysis notes - what info do you need?";
				}
				break;

			case 'entry': //! entry
			default:
				$ok_to_continue = true;
				break;
		}
		if( $this->debug ) echo "<p>".__METHOD__.": return ".($ok_to_continue ? "true" : "false")."</p>";
		return $ok_to_continue;
	}

	// Change state for load $pk to state $state
	// Optional param $cstate = current state
	public function change_state( $pk, $state, $cstate = -1 ) {
		global $sts_qb_dsn, $sts_crm_dir, $sts_error_level_label, $sts_release_name;
		
		if( $this->debug ) echo "<p>".__METHOD__.": $pk, $state ".
			(isset($this->state_name[$state]) ? $this->state_name[$state] : 'unknown')." / ".
			(isset($this->state_behavior[$state]) ? $this->state_behavior[$state] : 'unknown')."</p>";
		
		$this->state_change_post = '';
		$this->last_activity = 0;
		
		// Fetch current state etc.
		$result = $this->fetch_rows( $this->primary_key.' = '.$pk, 
					"CURRENT_STATUS, CHANGED_BY, CHANGED_DATE, TITLE");

		if( is_array($result) && isset($result[0]) && isset($result[0]['CURRENT_STATUS']) ) {
			$current_state = $result[0]['CURRENT_STATUS'];
			$changed_by = isset($result[0]['CHANGED_BY']) ? $result[0]['CHANGED_BY'] : "unknown";
			$changed_date = isset($result[0]['CHANGED_DATE']) ? $result[0]['CHANGED_DATE'] : 0;
			$title = isset($result[0]['TITLE']) ? $result[0]['TITLE'] : '';

			if( $this->debug ) echo "<p>".__METHOD__.": current_state = $current_state, 
				cstate = $cstate</p>";
			
			// !Check to see if the current state does not match.
			// This likely means a screen is out of date, and someone already updated the scr.
			if( $cstate > 0 && $cstate <> $current_state ) {
				$ok_to_continue = false;
				$this->state_change_level = EXT_ERROR_WARNING;
				$this->state_change_error = "SCR state is out of date. Likely someone updated it before you could.<br>".
					" SCR $pk was last updated by <strong>".$changed_by."</strong>".
					($changed_date <> 0 ? " on <strong>".date("m/d H:i", strtotime($changed_date))."</strong>" : "").".<br><br>".
					" (current_state = $current_state, given = $cstate)<br>"
					;
			} else {
				// Check the new state is a valid transition
				$following = $this->following_states( $current_state );
				$match = false;
				if( is_array($following) && count($following) > 0 ) {
					foreach( $following as $row ) {
						if( $row['CODE'] == $state ) $match = true;
					}
				}
				
				if( ! $match ) {
					$this->state_change_level = EXT_ERROR_ERROR;
					$this->state_change_error = "SCR $pk cannot transition from ".$this->state_name[$current_state]." to ".$this->state_name[$state];
					$ok_to_continue = false;
				} else {
					// Preconditions for changing state
					$ok_to_continue = $this->state_change_ok( $pk, $current_state, $state );
				}
			}

			if( $ok_to_continue ) {
				// Update the state to the new state
				$changes = array( 'CURRENT_STATUS' => $state );
				$result = $this->update( $pk."
					AND CURRENT_STATUS <> ".$state, $changes );
				
				if( $result ) {
					//! Post state change triggers
					
					if( $this->state_behavior[$state] == 'fixed' ) {
						$dummy = $this->update( $pk,
							array( 'FIXED_IN_RELEASE' => $sts_release_name ) );
					}

					$this->user_log->log_event( 'scr',	'SCR# '.$pk.' -> '. $this->state_name[$state].
						"<br>".$title );
					
					$email_type = 'scr';
					$email_code = $pk;
					require_once( "exp_spawn_send_email.php" ); // Send email
				} else {
					$save_error = $this->error();
					$email = sts_email::getInstance($this->database, $this->debug);
					$email->send_alert('".__METHOD__."($pk, $state): update state failed.'.
					'<br>'.$save_error );
				}

			} else {
				if( $this->debug ) echo "<p>".__METHOD__.": Not a valid state change. $current_state (".
					(isset($this->state_name[$current_state]) ? $this->state_name[$current_state] : 'unknown').") -> $state (".
					(isset($this->state_name[$state]) ? $this->state_name[$state] : 'unknown').")<br>
					$this->state_change_error<br>
					level = ".$sts_error_level_label[$this->state_change_level]."</p>";
				return false;
			}
		}
		return $result;
	}
	
	public function status_codes() {
		$result = $this->database->get_multiple_rows("
			SELECT S.CURRENT_STATUS, C.STATUS_STATE, COUNT(*) NUM
			FROM EXP_SCR S, EXP_STATUS_CODES C
	        WHERE S.CURRENT_STATUS = C.STATUS_CODES_CODE
	        GROUP BY S.CURRENT_STATUS, C.STATUS_STATE
			ORDER BY STATUS_CODES_CODE ASC");
		return $result;
	}
	
	public function products() {
		$result = $this->database->get_multiple_rows("
			SELECT S.PRODUCT, COUNT(*) NUM
			FROM EXP_SCR S
	        GROUP BY S.PRODUCT
			ORDER BY S.PRODUCT ASC");
		return $result;
	}
	
	//! Get a list of the most recent 5 releases.
	// Ordering is thanks to this article:
	// https://stackoverflow.com/questions/7508313/mysql-sorting-of-version-numbers
	public function fixed_releases() {
		$result = $this->fetch_rows("COALESCE(FIXED_IN_RELEASE, '') <> ''",
			"FIXED_IN_RELEASE, COUNT(*) NUM", "INET_ATON(SUBSTRING_INDEX(CONCAT(FIXED_IN_RELEASE,'.0.0.0'),'.',4))", "", "FIXED_IN_RELEASE");
		if( count($result) > 10 )
			$result = array_slice($result, -10);
		return $result;
	}
	
	public function current_developers() {
		$result = $this->database->get_multiple_rows("
			SELECT S.ASSIGNED_DEV, U.USERNAME, COUNT(*) NUM,
			GROUP_CONCAT(S.SCR_CODE ORDER BY S.SCR_CODE ASC SEPARATOR ', ') SCRS
			FROM EXP_SCR S, EXP_USER U, EXP_STATUS_CODES C
			WHERE S.ASSIGNED_DEV = U.USER_CODE
			AND S.CURRENT_STATUS = C.STATUS_CODES_CODE
			AND C.BEHAVIOR NOT IN ('build', 'dead', 'docked', 'late')
			GROUP BY S.ASSIGNED_DEV, U.USERNAME");
		return $result;
	}
	
	public function all_clients() {
		$result = $this->database->get_multiple_rows("
			SELECT S.SCR_CLIENT, COUNT(*) NUM,
			GROUP_CONCAT(S.SCR_CODE ORDER BY S.SCR_CODE ASC SEPARATOR ', ') SCRS
			FROM EXP_SCR S
			GROUP BY S.SCR_CLIENT
            ORDER BY S.SCR_CLIENT ASC");
		return $result;
	}
	
}

class sts_scr_history extends sts_table {

	// Constructor does not need the table name
	public function __construct( $database, $debug = false ) {

		$this->debug = $debug;
		$this->primary_key = "SCR_HISTORY_CODE";
		if( $this->debug ) echo "<p>Create sts_scr</p>";

		parent::__construct( $database, SCR_HISTORY_TABLE, $debug);
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

$sts_form_addscr_form = array(	//! $sts_form_addscr_form
	'title' => '<img src="images/bug_icon.png" alt="bug_icon" height="32"> Software Change Request',
	'action' => 'exp_addscr.php',
	//'actionextras' => 'disabled',
	'cancel' => 'exp_listscr.php',
	'name' => 'addscr',
	'okbutton' => 'Save Changes',
	'cancelbutton' => 'Cancel',	// well well-sm
		'layout' => '
	%CURRENT_STATUS%
	%ORIGINATOR%
	<div class="form-group">
		<div class="col-sm-12">
			<div class="form-group tighter">
				<label for="TITLE" class="col-sm-2 control-label">#TITLE#</label>
				<div class="col-sm-10">
					%TITLE%
				</div>
			</div>
		</div>
		<div class="col-sm-4">
			<div class="form-group tighter">
				<label for="SCR_TYPE" class="col-sm-6 control-label">#SCR_TYPE#</label>
				<div class="col-sm-6">
					%SCR_TYPE%
				</div>
			</div>
			<div class="form-group tighter">
				<label for="PRODUCT" class="col-sm-6 control-label">#PRODUCT#</label>
				<div class="col-sm-6">
					%PRODUCT%
				</div>
			</div>
			<div class="form-group tighter">
				<label for="SCR_CLIENT" class="col-sm-6 control-label">#SCR_CLIENT#</label>
				<div class="col-sm-6">
					%SCR_CLIENT%
				</div>
			</div>
		</div>
		<div class="col-sm-4">
			<div class="form-group tighter">
				<label for="CLIENT_PRIORITY" class="col-sm-6 control-label">#CLIENT_PRIORITY#</label>
				<div class="col-sm-6">
					%CLIENT_PRIORITY%
				</div>
			</div>
			<div class="form-group tighter">
				<label for="SCR_COMPONENT" class="col-sm-6 control-label">#SCR_COMPONENT#</label>
				<div class="col-sm-6">
					%SCR_COMPONENT%
				</div>
			</div>
			<div class="form-group">
				<label for="ATTACHMENT" class="col-sm-6 control-label">Attachment</label>
				<div class="col-sm-6">
					<input name="ATTACHMENT" id="ATTACHMENT" type="file">
				</div>
			</div>
		</div>
		<div class="col-sm-4">
			<div class="form-group tighter">
				<label for="SEVERITY" class="col-sm-6 control-label">#SEVERITY#</label>
				<div class="col-sm-6">
					%SEVERITY%
				</div>
			</div>
			<div class="form-group tighter">
				<label for="FOUND_IN_RELEASE" class="col-sm-6 control-label">#FOUND_IN_RELEASE#</label>
				<div class="col-sm-6">
					%FOUND_IN_RELEASE%
				</div>
			</div>
			<div class="form-group tighter">
				<label for="BILLABLE" class="col-sm-6 control-label">#BILLABLE#</label>
				<div class="col-sm-6">
					%BILLABLE%
				</div>
			</div>
		</div>
		<div class="col-sm-12">
			<div class="form-group tighter">
				<label for="URL" class="col-sm-2 control-label">#URL#</label>
				<div class="col-sm-10">
					%URL%
				</div>
			</div>
			<div class="form-group tighter">
				<label for="SCR_DESCRIPTION" class="col-sm-2 control-label">#SCR_DESCRIPTION#</label>
				<div class="col-sm-10">
					%SCR_DESCRIPTION%
				</div>
			</div>
			<div class="col-sm-10 col-sm-offset-2 alert alert-info">
			<p>For a <strong>defect</strong>, we need to be able to re-create the issue before we can fix it:</p>
			<ul>
			    <li>What did you try to do?</li>
			    <li>What steps did you take to do that?</li>
			    <li>What did you expect to happen?</li>
			    <li>What happened instead? Copy and paste any error messages. include a screen capture.</li>
			</ul>
			<p>For an <strong>enhancement</strong> or a <strong>new feature</strong>, include a design or marked up screen capture.</p>
			</div>
		</div>
	</div>
	
	'
);

$sts_form_editscr_form1 = array(	//! $sts_form_editscr_form1
	'title' => '<img src="images/bug_icon.png" alt="bug_icon" height="32"> Edit SCR# %SCR_CODE%',
	'action' => 'exp_editscr.php',
	//'actionextras' => 'disabled',
	'cancel' => 'exp_listscr.php',
	'name' => 'editscr',
	'okbutton' => 'Save Changes',
	'cancelbutton' => 'Back',	// well well-sm
		'layout' => '
	<div class="form-group alert alert-warning">
		<div class="col-sm-4">
			<div class="form-group tighter">
				<label for="SCR_CODE" class="col-sm-6 control-label">#SCR_CODE#</label>
				<div class="col-sm-6">
					%SCR_CODE%
				</div>
			</div>
		</div>
		<div class="col-sm-4">
			<div class="form-group">
				<label for="CURRENT_STATUS" class="col-sm-6 control-label">#CURRENT_STATUS#</label>
				<div class="col-sm-6">
					%CURRENT_STATUS%
				</div>
			</div>
		</div>
		<div class="col-sm-4">
			<div class="form-group tighter">
				<label for="ORIGINATOR" class="col-sm-6 control-label">#ORIGINATOR#</label>
				<div class="col-sm-6">
					%ORIGINATOR%
				</div>
			</div>
		</div>
		<div class="col-sm-12">
			<div class="form-group tighter">
				<label for="TITLE" class="col-sm-2 control-label">#TITLE#</label>
				<div class="col-sm-10">
					%TITLE%
				</div>
			</div>
		</div>
		<div class="col-sm-4">
			<div class="form-group tighter">
				<label for="SCR_TYPE" class="col-sm-6 control-label">#SCR_TYPE#</label>
				<div class="col-sm-6">
					%SCR_TYPE%
				</div>
			</div>
			<div class="form-group tighter">
				<label for="PRODUCT" class="col-sm-6 control-label">#PRODUCT#</label>
				<div class="col-sm-6">
					%PRODUCT%
				</div>
			</div>
			<div class="form-group tighter">
				<label for="SCR_CLIENT" class="col-sm-6 control-label">#SCR_CLIENT#</label>
				<div class="col-sm-6">
					%SCR_CLIENT%
				</div>
			</div>
		</div>
		<div class="col-sm-4">
			<div class="form-group tighter">
				<label for="CLIENT_PRIORITY" class="col-sm-6 control-label">#CLIENT_PRIORITY#</label>
				<div class="col-sm-6">
					%CLIENT_PRIORITY%
				</div>
			</div>
			<div class="form-group tighter">
				<label for="SCR_COMPONENT" class="col-sm-6 control-label">#SCR_COMPONENT#</label>
				<div class="col-sm-6">
					%SCR_COMPONENT%
				</div>
			</div>
			<div class="form-group tighter">
				<label for="BILLABLE" class="col-sm-6 control-label">#BILLABLE#</label>
				<div class="col-sm-6">
					%BILLABLE%
				</div>
			</div>
		</div>
		<div class="col-sm-4">
			<div class="form-group tighter">
				<label for="SEVERITY" class="col-sm-6 control-label">#SEVERITY#</label>
				<div class="col-sm-6">
					%SEVERITY%
				</div>
			</div>
			<div class="form-group tighter">
				<label for="FOUND_IN_RELEASE" class="col-sm-6 control-label">#FOUND_IN_RELEASE#</label>
				<div class="col-sm-6">
					%FOUND_IN_RELEASE%
				</div>
			</div>
		</div>
		<div class="col-sm-12">
			<div class="form-group tighter">
				<label for="URL" class="col-sm-2 control-label">#URL#</label>
				<div class="col-sm-10">
					%URL%
				</div>
			</div>
			<div class="form-group tighter">
				<label for="SCR_DESCRIPTION" class="col-sm-2 control-label">#SCR_DESCRIPTION#</label>
				<div class="col-sm-10">
					%SCR_DESCRIPTION%
				</div>
			</div>
		</div>
	</div>
	<div class="form-group alert alert-success">
		<div class="col-sm-4">
			<div class="form-group tighter">
				<label for="DEV_PRIORITY" class="col-sm-6 control-label">#DEV_PRIORITY#</label>
				<div class="col-sm-6">
					%DEV_PRIORITY%
				</div>
			</div>
			<div class="form-group tighter">
				<label for="RESOLUTION" class="col-sm-6 control-label">#RESOLUTION#</label>
				<div class="col-sm-6">
					%RESOLUTION%
				</div>
			</div>
		</div>
		<div class="col-sm-4">
			<div class="form-group tighter">
				<label for="ESTIMATED_WORK" class="col-sm-6 control-label">#ESTIMATED_WORK#</label>
				<div class="col-sm-6">
					%ESTIMATED_WORK%
				</div>
			</div>
			<div class="form-group tighter">
				<label for="ASSIGNED_DEV" class="col-sm-6 control-label">#ASSIGNED_DEV#</label>
				<div class="col-sm-6">
					%ASSIGNED_DEV%
				</div>
			</div>
		</div>
		<div class="col-sm-4">
			<div class="form-group tighter">
				<label for="ESTIMATED_HOURS" class="col-sm-6 control-label">#ESTIMATED_HOURS#</label>
				<div class="col-sm-6">
					%ESTIMATED_HOURS%
				</div>
			</div>
		</div>
		<div class="col-sm-12">
			<div class="form-group tighter">
				<label for="ANALYSIS" class="col-sm-2 control-label">#ANALYSIS#</label>
				<div class="col-sm-10">
					%ANALYSIS%
				</div>
			</div>
		</div>
	</div>
	
	'
);

$sts_form_editscr_form2 = array(	//! $sts_form_editscr_form2
	'title' => '<img src="images/bug_icon.png" alt="bug_icon" height="32"> Edit SCR# %SCR_CODE%',
	'action' => 'exp_editscr.php',
	//'actionextras' => 'disabled',
	'cancel' => 'exp_listscr.php',
	'name' => 'editscr',
	'okbutton' => 'Save Changes',
	'cancelbutton' => 'Back',	// well well-sm
		'layout' => '
	<div class="form-group alert alert-warning">
		<div class="col-sm-4">
			<div class="form-group tighter">
				<label for="SCR_CODE" class="col-sm-6 control-label">#SCR_CODE#</label>
				<div class="col-sm-6">
					%SCR_CODE%
				</div>
			</div>
		</div>
		<div class="col-sm-4">
			<div class="form-group">
				<label for="CURRENT_STATUS" class="col-sm-6 control-label">#CURRENT_STATUS#</label>
				<div class="col-sm-6">
					%CURRENT_STATUS%
				</div>
			</div>
		</div>
		<div class="col-sm-4">
			<div class="form-group tighter">
				<label for="ORIGINATOR" class="col-sm-6 control-label">#ORIGINATOR#</label>
				<div class="col-sm-6">
					%ORIGINATOR%
				</div>
			</div>
		</div>
		<div class="col-sm-12">
			<div class="form-group tighter">
				<label for="TITLE" class="col-sm-2 control-label">#TITLE#</label>
				<div class="col-sm-10">
					%TITLE%
				</div>
			</div>
		</div>
		<div class="col-sm-4">
			<div class="form-group tighter">
				<label for="SCR_TYPE" class="col-sm-6 control-label">#SCR_TYPE#</label>
				<div class="col-sm-6">
					%SCR_TYPE%
				</div>
			</div>
			<div class="form-group tighter">
				<label for="PRODUCT" class="col-sm-6 control-label">#PRODUCT#</label>
				<div class="col-sm-6">
					%PRODUCT%
				</div>
			</div>
			<div class="form-group tighter">
				<label for="SCR_CLIENT" class="col-sm-6 control-label">#SCR_CLIENT#</label>
				<div class="col-sm-6">
					%SCR_CLIENT%
				</div>
			</div>
		</div>
		<div class="col-sm-4">
			<div class="form-group tighter">
				<label for="CLIENT_PRIORITY" class="col-sm-6 control-label">#CLIENT_PRIORITY#</label>
				<div class="col-sm-6">
					%CLIENT_PRIORITY%
				</div>
			</div>
			<div class="form-group tighter">
				<label for="SCR_COMPONENT" class="col-sm-6 control-label">#SCR_COMPONENT#</label>
				<div class="col-sm-6">
					%SCR_COMPONENT%
				</div>
			</div>
			<div class="form-group tighter">
				<label for="BILLABLE" class="col-sm-6 control-label">#BILLABLE#</label>
				<div class="col-sm-6">
					%BILLABLE%
				</div>
			</div>
		</div>
		<div class="col-sm-4">
			<div class="form-group tighter">
				<label for="SEVERITY" class="col-sm-6 control-label">#SEVERITY#</label>
				<div class="col-sm-6">
					%SEVERITY%
				</div>
			</div>
			<div class="form-group tighter">
				<label for="FOUND_IN_RELEASE" class="col-sm-6 control-label">#FOUND_IN_RELEASE#</label>
				<div class="col-sm-6">
					%FOUND_IN_RELEASE%
				</div>
			</div>
		</div>
		<div class="col-sm-12">
			<div class="form-group tighter">
				<label for="URL" class="col-sm-2 control-label">#URL#</label>
				<div class="col-sm-10">
					%URL%
				</div>
			</div>
			<div class="form-group tighter">
				<label for="SCR_DESCRIPTION" class="col-sm-2 control-label">#SCR_DESCRIPTION#</label>
				<div class="col-sm-10">
					%SCR_DESCRIPTION%
				</div>
			</div>
		</div>
	</div>
	<div class="form-group alert alert-success">
		<div class="col-sm-4">
			<div class="form-group tighter">
				<label for="DEV_PRIORITY" class="col-sm-6 control-label">#DEV_PRIORITY#</label>
				<div class="col-sm-6">
					%DEV_PRIORITY%
				</div>
			</div>
			<div class="form-group tighter">
				<label for="RESOLUTION" class="col-sm-6 control-label">#RESOLUTION#</label>
				<div class="col-sm-6">
					%RESOLUTION%
				</div>
			</div>
		</div>
		<div class="col-sm-4">
			<div class="form-group tighter">
				<label for="ESTIMATED_WORK" class="col-sm-6 control-label">#ESTIMATED_WORK#</label>
				<div class="col-sm-6">
					%ESTIMATED_WORK%
				</div>
			</div>
			<div class="form-group tighter">
				<label for="ASSIGNED_DEV" class="col-sm-6 control-label">#ASSIGNED_DEV#</label>
				<div class="col-sm-6">
					%ASSIGNED_DEV%
				</div>
			</div>
		</div>
		<div class="col-sm-4">
			<div class="form-group tighter">
				<label for="ESTIMATED_HOURS" class="col-sm-6 control-label">#ESTIMATED_HOURS#</label>
				<div class="col-sm-6">
					%ESTIMATED_HOURS%
				</div>
			</div>
		</div>
		<div class="col-sm-12">
			<div class="form-group tighter">
				<label for="ANALYSIS" class="col-sm-2 control-label">#ANALYSIS#</label>
				<div class="col-sm-10">
					%ANALYSIS%
				</div>
			</div>
		</div>
	</div>
	<div class="form-group alert alert-info">
		<div class="col-sm-4">
			<div class="form-group tighter">
				<label for="ACTUAL_HOURS" class="col-sm-6 control-label">#ACTUAL_HOURS#</label>
				<div class="col-sm-6">
					%ACTUAL_HOURS%
				</div>
			</div>
		</div>
		<div class="col-sm-3">
			<div class="form-group tighter">
				<label for="ASSIGNED_QA" class="col-sm-6 control-label">#ASSIGNED_QA#</label>
				<div class="col-sm-6">
					%ASSIGNED_QA%
				</div>
			</div>
		</div>
		<div class="col-sm-2">
			<div class="form-group tighter">
				<label for="SETTINGS" class="col-sm-6 control-label">#SETTINGS#</label>
				<div class="col-sm-6">
					%SETTINGS%
				</div>
			</div>
		</div>
		<div class="col-sm-2">
			<div class="form-group tighter">
				<label for="SCHEMA_CHANGES" class="col-sm-6 control-label">#SCHEMA_CHANGES#</label>
				<div class="col-sm-6">
					%SCHEMA_CHANGES%
				</div>
			</div>
		</div>
		<div class="col-sm-12">
			<div class="form-group tighter">
				<label for="DEV_NOTES" class="col-sm-2 control-label">#DEV_NOTES#</label>
				<div class="col-sm-10">
					%DEV_NOTES%
				</div>
			</div>
		</div>
	</div>
	
	'
);

$sts_form_editscr_form3 = array(	//! $sts_form_editscr_form3
	'title' => '<img src="images/bug_icon.png" alt="bug_icon" height="32"> Edit SCR# %SCR_CODE%',
	'action' => 'exp_editscr.php',
	//'actionextras' => 'disabled',
	'cancel' => 'exp_listscr.php',
	'name' => 'editscr',
	'okbutton' => 'Save Changes',
	'cancelbutton' => 'Back',	// well well-sm
		'layout' => '
	<div class="form-group alert alert-warning">
		<div class="col-sm-4">
			<div class="form-group tighter">
				<label for="SCR_CODE" class="col-sm-6 control-label">#SCR_CODE#</label>
				<div class="col-sm-6">
					%SCR_CODE%
				</div>
			</div>
		</div>
		<div class="col-sm-4">
			<div class="form-group">
				<label for="CURRENT_STATUS" class="col-sm-6 control-label">#CURRENT_STATUS#</label>
				<div class="col-sm-6">
					%CURRENT_STATUS%
				</div>
			</div>
		</div>
		<div class="col-sm-4">
			<div class="form-group tighter">
				<label for="ORIGINATOR" class="col-sm-6 control-label">#ORIGINATOR#</label>
				<div class="col-sm-6">
					%ORIGINATOR%
				</div>
			</div>
		</div>
		<div class="col-sm-12">
			<div class="form-group tighter">
				<label for="TITLE" class="col-sm-2 control-label">#TITLE#</label>
				<div class="col-sm-10">
					%TITLE%
				</div>
			</div>
		</div>
		<div class="col-sm-4">
			<div class="form-group tighter">
				<label for="SCR_TYPE" class="col-sm-6 control-label">#SCR_TYPE#</label>
				<div class="col-sm-6">
					%SCR_TYPE%
				</div>
			</div>
			<div class="form-group tighter">
				<label for="PRODUCT" class="col-sm-6 control-label">#PRODUCT#</label>
				<div class="col-sm-6">
					%PRODUCT%
				</div>
			</div>
			<div class="form-group tighter">
				<label for="SCR_CLIENT" class="col-sm-6 control-label">#SCR_CLIENT#</label>
				<div class="col-sm-6">
					%SCR_CLIENT%
				</div>
			</div>
		</div>
		<div class="col-sm-4">
			<div class="form-group tighter">
				<label for="CLIENT_PRIORITY" class="col-sm-6 control-label">#CLIENT_PRIORITY#</label>
				<div class="col-sm-6">
					%CLIENT_PRIORITY%
				</div>
			</div>
			<div class="form-group tighter">
				<label for="SCR_COMPONENT" class="col-sm-6 control-label">#SCR_COMPONENT#</label>
				<div class="col-sm-6">
					%SCR_COMPONENT%
				</div>
			</div>
			<div class="form-group tighter">
				<label for="BILLABLE" class="col-sm-6 control-label">#BILLABLE#</label>
				<div class="col-sm-6">
					%BILLABLE%
				</div>
			</div>
		</div>
		<div class="col-sm-4">
			<div class="form-group tighter">
				<label for="SEVERITY" class="col-sm-6 control-label">#SEVERITY#</label>
				<div class="col-sm-6">
					%SEVERITY%
				</div>
			</div>
			<div class="form-group tighter">
				<label for="FOUND_IN_RELEASE" class="col-sm-6 control-label">#FOUND_IN_RELEASE#</label>
				<div class="col-sm-6">
					%FOUND_IN_RELEASE%
				</div>
			</div>
		</div>
		<div class="col-sm-12">
			<div class="form-group tighter">
				<label for="URL" class="col-sm-2 control-label">#URL#</label>
				<div class="col-sm-10">
					%URL%
				</div>
			</div>
			<div class="form-group tighter">
				<label for="SCR_DESCRIPTION" class="col-sm-2 control-label">#SCR_DESCRIPTION#</label>
				<div class="col-sm-10">
					%SCR_DESCRIPTION%
				</div>
			</div>
		</div>
	</div>
	<div class="form-group alert alert-success">
		<div class="col-sm-4">
			<div class="form-group tighter">
				<label for="DEV_PRIORITY" class="col-sm-6 control-label">#DEV_PRIORITY#</label>
				<div class="col-sm-6">
					%DEV_PRIORITY%
				</div>
			</div>
			<div class="form-group tighter">
				<label for="RESOLUTION" class="col-sm-6 control-label">#RESOLUTION#</label>
				<div class="col-sm-6">
					%RESOLUTION%
				</div>
			</div>
		</div>
		<div class="col-sm-4">
			<div class="form-group tighter">
				<label for="ESTIMATED_WORK" class="col-sm-6 control-label">#ESTIMATED_WORK#</label>
				<div class="col-sm-6">
					%ESTIMATED_WORK%
				</div>
			</div>
			<div class="form-group tighter">
				<label for="ASSIGNED_DEV" class="col-sm-6 control-label">#ASSIGNED_DEV#</label>
				<div class="col-sm-6">
					%ASSIGNED_DEV%
				</div>
			</div>
		</div>
		<div class="col-sm-4">
			<div class="form-group tighter">
				<label for="ESTIMATED_HOURS" class="col-sm-6 control-label">#ESTIMATED_HOURS#</label>
				<div class="col-sm-6">
					%ESTIMATED_HOURS%
				</div>
			</div>
		</div>
		<div class="col-sm-12">
			<div class="form-group tighter">
				<label for="ANALYSIS" class="col-sm-2 control-label">#ANALYSIS#</label>
				<div class="col-sm-10">
					%ANALYSIS%
				</div>
			</div>
		</div>
	</div>
	<div class="form-group alert alert-info">
		<div class="col-sm-4">
			<div class="form-group tighter">
				<label for="ACTUAL_HOURS" class="col-sm-6 control-label">#ACTUAL_HOURS#</label>
				<div class="col-sm-6">
					%ACTUAL_HOURS%
				</div>
			</div>
		</div>
		<div class="col-sm-2">
			<div class="form-group tighter">
				<label for="SETTINGS" class="col-sm-6 control-label">#SETTINGS#</label>
				<div class="col-sm-6">
					%SETTINGS%
				</div>
			</div>
		</div>
		<div class="col-sm-2">
			<div class="form-group tighter">
				<label for="SCHEMA_CHANGES" class="col-sm-6 control-label">#SCHEMA_CHANGES#</label>
				<div class="col-sm-6">
					%SCHEMA_CHANGES%
				</div>
			</div>
		</div>
		<div class="col-sm-2">
			<div class="form-group tighter">
				<label for="PATCHED" class="col-sm-6 control-label">#PATCHED#</label>
				<div class="col-sm-6">
					%PATCHED%
				</div>
			</div>
		</div>
		<div class="col-sm-12">
			<div class="form-group tighter">
				<label for="DEV_NOTES" class="col-sm-2 control-label">#DEV_NOTES#</label>
				<div class="col-sm-10">
					%DEV_NOTES%
				</div>
			</div>
		</div>
	</div>
	<div class="form-group alert alert-danger">
		<div class="col-sm-4">
			<div class="form-group tighter">
				<label for="ASSIGNED_QA" class="col-sm-6 control-label">#ASSIGNED_QA#</label>
				<div class="col-sm-6">
					%ASSIGNED_QA%
				</div>
			</div>
		</div>
		<div class="col-sm-4">
			<div class="form-group tighter">
				<label for="FIXED_IN_RELEASE" class="col-sm-6 control-label">#FIXED_IN_RELEASE#</label>
				<div class="col-sm-6">
					%FIXED_IN_RELEASE%
				</div>
			</div>
		</div>
		<div class="col-sm-12">
			<div class="form-group tighter">
				<label for="QA_NOTES" class="col-sm-2 control-label">#QA_NOTES#</label>
				<div class="col-sm-10">
					%QA_NOTES%
				</div>
			</div>
		</div>
	</div>
	
	'
);


//! Field Specifications - For use with sts_form

$sts_form_add_scr_fields = array( //! $sts_form_add_scr_fields
	'TITLE' => array( 'label' => 'Title', 'format' => 'text', 'extras' => 'required',
		'placeholder' => 'A title that clearly distinguishes this from others' ),
	'CURRENT_STATUS' => array( 'label' => 'Status', 'format' => 'hidden' ),
	'ORIGINATOR' => array( 'label' => 'Originator', 'format' => 'hidden' ),
	'SCR_CLIENT' => array( 'label' => 'For client', 'format' => 'text' ),
	'BILLABLE' => array( 'label' => 'Billable', 'format' => 'bool' ),
	'CLIENT_PRIORITY' => array( 'label' => 'Priority', 'format' => 'enum' ),
	'SEVERITY' => array( 'label' => 'Severity', 'format' => 'enum' ),
	'SCR_TYPE' => array( 'label' => 'SCR Type', 'format' => 'enum' ),
	'PRODUCT' => array( 'label' => 'Product', 'format' => 'enum' ),
	'FOUND_IN_RELEASE' => array( 'label' => 'In Release #', 'format' => 'text' ),
	'SCR_COMPONENT' => array( 'label' => 'Component', 'format' => 'text' ),
	'URL' => array( 'label' => 'URL', 'format' => 'text',
		'placeholder' => 'Paste the URL of the page where the error happens' ),
	'SCR_DESCRIPTION' => array( 'label' => 'Description', 'format' => 'textarea',
		'extras' => 'rows="4"', 'extras' => 'required' ),
);


$sts_form_edit_scr_fields = array( //! $sts_form_edit_scr_fields
	'SCR_CODE' => array( 'label' => 'SCR#', 'format' => 'static', 'align' => 'right'),
	'TITLE' => array( 'label' => 'Title', 'format' => 'text', 'extras' => 'required',
		'placeholder' => 'A title that clearly distinguishes this from others' ),
	'CURRENT_STATUS' => array( 'label' => 'Status', 'format' => 'table',
		'table' => STATUS_CODES_TABLE, 'key' => 'STATUS_CODES_CODE', 'fields' => 'STATUS_STATE',
		'condition' => 'SOURCE_TYPE = \'scr\'', 'extras' => 'readonly', 'static' => true ),
	'ORIGINATOR' => array( 'label' => 'Originator', 'format' => 'table',
		'table' => USER_TABLE, 'key' => 'USER_CODE', 'fields' => 'USERNAME',
		'nolink' => true ),
	'SCR_CLIENT' => array( 'label' => 'For client', 'format' => 'text' ),
	'BILLABLE' => array( 'label' => 'Billable', 'format' => 'bool' ),
	'CLIENT_PRIORITY' => array( 'label' => 'Priority', 'format' => 'enum' ),
	'SEVERITY' => array( 'label' => 'Severity', 'format' => 'enum' ),
	'SCR_TYPE' => array( 'label' => 'SCR Type', 'format' => 'enum' ),
	'PRODUCT' => array( 'label' => 'Product', 'format' => 'enum' ),
	'FOUND_IN_RELEASE' => array( 'label' => 'In Release #', 'format' => 'text' ),
	'SCR_COMPONENT' => array( 'label' => 'Component', 'format' => 'text' ),
	'URL' => array( 'label' => 'URL', 'format' => 'text',
		'placeholder' => 'Paste the URL of the page where the error happens' ),
	'SCR_DESCRIPTION' => array( 'label' => 'Description', 'format' => 'textarea',
		'extras' => 'rows="6"', 'extras' => 'required' ),

	'ANALYSIS' => array( 'label' => 'Analysis Notes', 'format' => 'textarea',
		'extras' => 'rows="6"', 'extras' => 'required',
		'placeholder' => 'Should this work be done? Is there a bug? Is there enough information to find and fix it?' ),
	'ASSIGNED_DEV' => array( 'label' => 'Developer', 'format' => 'table',
		'table' => USER_TABLE, 'key' => 'USER_CODE', 'fields' => 'USERNAME',
		'nolink' => true ),
	'ESTIMATED_WORK' => array( 'label' => 'How much work', 'format' => 'enum' ),
	'ESTIMATED_HOURS' => array( 'label' => 'Est hours', 'format' => 'number', 'align' => 'right' ),
	'DEV_PRIORITY' => array( 'label' => 'Our priority', 'format' => 'enum' ),
	'RESOLUTION' => array( 'label' => 'Resolution', 'format' => 'enum' ),
		
	'DEV_NOTES' => array( 'label' => 'Dev notes', 'format' => 'textarea',
		'extras' => 'rows="6"', 'extras' => 'required',
		'placeholder' => 'Detail what changes were made. Any new settings? New things to be tested? Refer to attached documents as needed.' ),
	'ACTUAL_HOURS' => array( 'label' => 'Actual hours', 'format' => 'number', 'align' => 'right' ),

	'ASSIGNED_QA' => array( 'label' => 'Tester', 'format' => 'table',
		'table' => USER_TABLE, 'key' => 'USER_CODE', 'fields' => 'USERNAME',
		'nolink' => true ),
	'QA_NOTES' => array( 'label' => 'Testing notes', 'format' => 'textarea',
		'extras' => 'rows="6"', 'extras' => 'required',
		'placeholder' => 'Detail what tests were done, and the results.' ),
	
	'FIXED_IN_RELEASE' => array( 'label' => 'Add to release #', 'format' => 'text' ),
	'SETTINGS' => array( 'label' => 'Settings', 'format' => 'bool' ),
	'SCHEMA_CHANGES' => array( 'label' => 'Schema', 'format' => 'bool' ),
	'PATCHED' => array( 'label' => 'Patched', 'format' => 'bool' ),

);

//! Layout Specifications - For use with sts_result

$sts_result_scr_layout = array( //! $sts_result_scr_layout
	'SCR_CODE' => array( 'label' => 'SCR#', 'format' => 'text', 'align' => 'right',
		'link' => 'exp_editscr.php?CODE=', 'button' => 'default btn-sm', 'searchable' => true ),
	'CURRENT_STATUS' => array( 'label' => 'Status', 'format' => 'table',
		'table' => STATUS_CODES_TABLE, 'key' => 'STATUS_CODES_CODE', 'fields' => 'STATUS_STATE',
		'condition' => 'SOURCE_TYPE = \'scr\'' ),
	'RESOLUTION' => array( 'label' => 'Resolution', 'format' => 'text' ),
	'SCR_CLIENT' => array( 'label' => 'Client', 'format' => 'text',
		'searchable' => true ),
	'PRODUCT' => array( 'label' => 'Product', 'format' => 'text' ),
	'TITLE' => array( 'label' => 'Title', 'format' => 'text', 'extras' => 'required',
		'placeholder' => 'A title that clearly distinguishes this from others',
		'searchable' => true ),
	'BILLABLE' => array( 'label' => 'Billable', 'format' => 'bool', 'align' => 'center' ),
	'SETTINGS' => array( 'label' => 'Settings', 'format' => 'bool', 'align' => 'center' ),
	'SCHEMA_CHANGES' => array( 'label' => 'Schema', 'format' => 'bool', 'align' => 'center' ),
	'PATCHED' => array( 'label' => 'Patched', 'format' => 'bool', 'align' => 'center' ),
	'SCR_DESCRIPTION' => array( 'format' => 'hidden', 'searchable' => true),
	'ORIGINATOR' => array( 'label' => 'Originator', 'format' => 'table',
		'table' => USER_TABLE, 'key' => 'USER_CODE', 'fields' => 'USERNAME', 'link' => 'exp_edituser.php?CODE=' ),
	'CLIENT_PRIORITY' => array( 'label' => 'Priority', 'format' => 'text' ),
	'SEVERITY' => array( 'label' => 'Severity', 'format' => 'text' ),
	'SCR_TYPE' => array( 'label' => 'SCR Type', 'format' => 'text' ),
	'DEV_PRIORITY' => array( 'label' => 'Our priority', 'format' => 'text' ),
	'ACTUAL_HOURS' => array( 'label' => 'Hours', 'format' => 'number', 'align' => 'right' ),
	'FOUND_IN_RELEASE' => array( 'label' => 'Found&nbsp;in', 'format' => 'text' ),
	'FIXED_IN_RELEASE' => array( 'label' => 'Fixed&nbsp;in', 'format' => 'text' ),
	'ASSIGNED_DEV' => array( 'label' => 'Developer', 'format' => 'table',
		'table' => USER_TABLE, 'key' => 'USER_CODE', 'fields' => 'USERNAME', 'link' => 'exp_edituser.php?CODE=' ),
	'ASSIGNED_QA' => array( 'label' => 'Tester', 'format' => 'table',
		'table' => USER_TABLE, 'key' => 'USER_CODE', 'fields' => 'USERNAME', 'link' => 'exp_edituser.php?CODE=' ),

	'CHANGED_DATE' => array( 'label' => 'Changed', 'format' => 'timestamp', 'length' => 120 ),
	'CHANGED_BY' => array( 'label' => 'By', 'format' => 'table',
		'table' => USER_TABLE, 'key' => 'USER_CODE', 'fields' => 'USERNAME', 'link' => 'exp_edituser.php?CODE=' )
);

$sts_result_scr_history_layout = array( //! $sts_result_scr_history_layout
	'SCR_HISTORY_CODE' => array( 'format' => 'hidden' ),
	'SCR_STATUS' => array( 'label' => 'Status', 'format' => 'table',
		'table' => STATUS_CODES_TABLE, 'key' => 'STATUS_CODES_CODE', 'fields' => 'STATUS_STATE',
		'condition' => 'SOURCE_TYPE = \'scr\'' ),
	'CREATED_DATE' => array( 'label' => 'When', 'format' => 'timestamp-s', 'length' => 90 ),
	'CREATED_BY' => array( 'label' => 'By', 'format' => 'table',
		'table' => USER_TABLE, 'key' => 'USER_CODE', 'fields' => 'USERNAME', 'link' => 'exp_edituser.php?CODE=' )
);

//! Edit/Delete Button Specifications - For use with sts_result

$sts_result_scr_edit = array( //! $sts_result_scr_edit
	'title' => '<img src="images/bug_icon.png" alt="bug_icon" height="32"> Software Change Requests',
	'sort' => 'SCR_CODE desc',
	'cancel' => 'index.php',
	'add' => 'exp_addscr.php',
	//'actionextras' => 'disabled',
	'addbutton' => 'Add SCR',
	'cancelbutton' => 'Back',
);

$sts_result_scr_history_edit = array( //! $sts_result_scr_history_edit
	'title' => '<img src="images/bug_icon.png" alt="bug_icon" height="32"> SCR History',
	'sort' => 'SCR_HISTORY_CODE asc',
);


?>
