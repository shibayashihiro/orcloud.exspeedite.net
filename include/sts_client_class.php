<?php

// $Id: sts_client_class.php 5146 2023-06-29 19:57:45Z dev $
// Client class

// no direct access
defined('_STS_INCLUDE') or die('Restricted access');

require_once( "sts_table_class.php" );

require_once( "sts_setting_class.php" );
require_once( "sts_email_class.php" );
require_once( "sts_client_activity_class.php" );
require_once( "PCMILER/exp_get_miles.php" );

class sts_client extends sts_table {

	private $setting_table;
	private $cat;
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
	private $export_sage50;
	private $usstates;
	private $usstates_rev;
	private $cms_salespeople_leads;
	private $client_id;				//! SCR# 584 - New coulum Client_ID

	// Constructor does not need the table name
	public function __construct( $database, $debug = false ) {

		$this->debug = $debug;
		$this->primary_key = "CLIENT_CODE";
		if( $this->debug ) echo "<p>Create sts_client</p>";
		$this->setting_table = sts_setting::getInstance($database, $debug);
		$this->label = ($this->setting_table->get( 'option', 'CLIENT_LABEL' ) == 'true');
		$this->export_sage50 = ($this->setting_table->get( 'api', 'EXPORT_SAGE50_CSV' ) == 'true');
		$this->matches = (int) $this->setting_table->get( 'option', 'CLIENT_MATCHES' );
		if( $this->matches <= 0 ) $this->matches = 20;
		$this->cms_salespeople_leads = ($this->setting_table->get("option", "CMS_SALESPEOPLE_LEADS") == 'true');
		$this->client_id = ($this->setting_table->get("option", "CLIENT_ID") == 'true');

		$this->cat = sts_client_activity::getInstance($database, $debug);
		parent::__construct( $database, CLIENT_TABLE, $debug);
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

	private function load_usstates() {
		$whole = $this->cache->get_whole_cache();
		foreach( $whole["NAME_STATE"] as $name => $abbrev ) {
			$this->usstates_rev[strtolower($name)] = $abbrev;
		}
		$this->usstates = $whole["STATE_NAME"];
	}

	//! SCR# 769 - Display probable duplicates leads
	public function alert_duplicates() {
		$result = '';

		$duplicates = $this->database->get_multiple_rows("
			SELECT CLIENT_CODE, CLIENT_NAME
			FROM EXP_CLIENT
			WHERE CLIENT_TYPE = 'lead'
			AND CURRENT_STATUS = ".$this->behavior_state['admin']."
			AND ISDELETED = FALSE
			ORDER BY CLIENT_NAME ASC");
			
		if( is_array($duplicates) && count($duplicates) > 0 ) {
			$actions = array();
			foreach($duplicates as $row) {
				$actions[] = '<a class="btn btn-sm btn-warning inform" href="exp_editclient.php?CODE='.$row["CLIENT_CODE"].'" title="<strong>Probable Duplicate: '.$row["CLIENT_NAME"].'</strong>">'.$row["CLIENT_NAME"].'</a>';
			}
			$result = '<p style="margin-bottom: 5px;"><a class="btn btn-sm btn-primary" data-toggle="collapse" href="#collapseLeads" aria-expanded="false" aria-controls="collapseLeads"><span class="glyphicon glyphicon-warning-sign"></span> '.plural(count($duplicates), ' Probable Duplicate').' need attention</a></p>
			<div class="collapse" id="collapseLeads">
			<p>'.implode(" ", $actions).'</p>
			</div>';

		}		
		return $result;
	}

	//! SCR# 601 - Map to two letter state/province or return false if unknown
	public function map_usstate( $name  ) {
		
		$result = false;
		if( ! is_array($this->usstates) )
			$this->load_usstates();
			
		if( isset($this->usstates[$name]))
			$result = $name;
		else if( isset($this->usstates_rev[strtolower($name)]))
			$result = $this->usstates_rev[strtolower($name)];
		
		return $result;
	}
	
	public function usstates_menu( $name, $value ) {
		
		$output = '';
		if( ! is_array($this->usstates) )
			$this->load_usstates();
			
		$output .= '<select class="form-control input-sm" name="'.$name.'" id="'.$name.'"  onchange="form.submit();">
				<option value="all"'.($value=='all' ? ' selected' : '').'>All</option>';
		foreach( $this->usstates as $abbrev => $state_name ) {
			$output .= '
				<option value="'.$abbrev.'" '.($abbrev==$value ? 'selected' : '').'>'.$abbrev.'</option>';
		}
		$output .= '
			</select>';
			
		return $output;
	}
		
	private function load_states() {
		
		$cached = $this->cache->get_state_table( 'client' );
		
		if( is_array($cached) && count($cached) > 0 ) {
			$this->states = $cached;
		} else {
			$this->states = $this->database->get_multiple_rows("select STATUS_CODES_CODE, STATUS_STATE, BEHAVIOR, STATUS_DESCRIPTION, PREVIOUS
				FROM EXP_STATUS_CODES
				WHERE SOURCE_TYPE = 'client'
				ORDER BY STATUS_CODES_CODE ASC");
			assert( count($this->states) > 0, "Unable to load states for clients" );
		}
		
		$this->state_name = array();
		$this->state_behavior = array();
		foreach( $this->states as $row ) {
			$this->state_name[$row['STATUS_CODES_CODE']] = $row['STATUS_STATE'];
			$this->state_behavior[$row['STATUS_CODES_CODE']] = $row['BEHAVIOR'];
			$this->name_state[$row['STATUS_STATE']] = $row['STATUS_CODES_CODE'];
			$this->behavior_state[$row['BEHAVIOR']] = $row['STATUS_CODES_CODE'];
		}
		
		//if( $this->debug ) {
		//	echo "<p>sts_load > load_states: states = </p>
		//	<pre>";
		//	var_dump($this->states);
		//	echo "</pre>";
		//}
	}
	
	public function client_id() {
		return $this->client_id;
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
			if( $matched_previous || $row['BEHAVIOR'] == 'dead' ) {
				$following[] = array( 'CODE' => $row['STATUS_CODES_CODE'], 
					'BEHAVIOR' => $row['BEHAVIOR'],
					'DESCRIPTION' => $row['STATUS_DESCRIPTION'], 
					'STATUS' => $row['STATUS_STATE']);
			}
		}
		
		return $following;
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
					"SALES_PERSON,
					(SELECT USERNAME FROM EXP_USER WHERE USER_CODE = SALES_PERSON
					AND USER_GROUPS LIKE  '%".EXT_GROUP_SALES."%') AS NAME");

				$ok_to_continue = is_array($check) && count($check) == 1 &&
					isset($check[0]["SALES_PERSON"]) && $check[0]["SALES_PERSON"] > 0 &&
					! empty($check[0]["NAME"]);
					
				if( ! $ok_to_continue ) {
					$this->state_change_error = "You need to assign a sales person first.";
				} else if( $this->state_behavior[$current_state] == 'admin') {
					//! SCR# 420 - Allow sales people to grab leads
					$ok_to_continue = ($this->cms_salespeople_leads && in_group( EXT_GROUP_SALES )) ||
						in_group( EXT_GROUP_ADMIN );
					if( ! $ok_to_continue ) {
						$this->state_change_error = "You need to be an admin for this, or enable setting option/CMS_SALESPEOPLE_LEADS.";
					}
				}
				break;
				
			case 'dead':	//! dead
				$ok_to_continue = $this->state_behavior[$current_state] <> 'dead';
				if( ! $ok_to_continue ) {
					$this->state_change_error = "already dead";
				}
				break;

			case 'sold': //! sold
				$check = $this->fetch_rows( $this->primary_key.' = '.$pk,
					"BILL_TO, (SELECT COUNT(*) NUM FROM EXP_CONTACT_INFO
						WHERE CONTACT_CODE = CLIENT_CODE
						AND CONTACT_SOURCE = 'client'
						AND CONTACT_TYPE = 'bill_to'
						AND ISDELETED = false) AS NUM_BILLTO" );
				
				$ok_to_continue = is_array($check) && count($check) == 1 &&
					isset($check[0]["BILL_TO"]) && $check[0]["BILL_TO"];

				if( $ok_to_continue ) {
					$ok_to_continue = isset($check[0]["NUM_BILLTO"]) && $check[0]["NUM_BILLTO"] > 0;

					if( ! $ok_to_continue ) {
						$this->state_change_error = "you need one contact of type bill_to for this contact";
					}
				} else {
					$this->state_change_error = "you need to check Bill-to indicating this client is a bill-to client";
				}
				break;
					

			case 'email': //! email
				$php = phpversion();
				$use_mailchecker = intval($php[0]) >= 7;
				
				if( $use_mailchecker )
					require_once( "Mailchecker/platform/php/MailChecker.php" );
					//dirname(dirname(__FILE__)).DIRECTORY_SEPARATOR.
								
				$check = $this->database->get_multiple_rows( "SELECT EMAIL
					FROM EXP_CONTACT_INFO
					WHERE CONTACT_CODE = $pk
					AND CONTACT_SOURCE = 'client'
					AND ISDELETED = false" );
				
				$ok_to_continue = is_array($check) && count($check) > 0;

				if( $ok_to_continue ) {
					foreach( $check as $row ) {
						$ok_to_continue = ! empty($row["EMAIL"]);
						if( $ok_to_continue ) {
							$emails = explode(',', $row["EMAIL"]);
							foreach($emails as $e) {
								$ok_to_continue = $ok_to_continue && ! empty($e);
								if( $use_mailchecker )
									$ok_to_continue = $ok_to_continue && MailChecker::isValid(trim($e));
							}
						}
					}
				}
					
				if( $ok_to_continue ) {
					$check2 = $this->database->get_one_row("SELECT USERNAME, EMAIL
						FROM EXP_CLIENT, EXP_USER
						WHERE CLIENT_CODE = $pk
						AND SALES_PERSON = USER_CODE");
					
					$ok_to_continue = is_array($check2) && ! empty($check2["EMAIL"]);
					if( $use_mailchecker )
						$ok_to_continue = $ok_to_continue && MailChecker::isValid($check2["EMAIL"]);

					if( ! $ok_to_continue ) {
						$this->state_change_error = "the sales person ".(empty($check2["USERNAME"]) ? '' : $check2["USERNAME"])." doesn't have a valid email address";
					}
				} else {
					$this->state_change_error = "the client needs at least one contact with an email address";
				}
				break;
			
			case 'entry': //! entry
			case 'call': //! call
			case 'quotereq': //! quotereq
			case 'sentquote': //! sentquote
			case 'sentinfo': //! sentinfo
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
		global $sts_qb_dsn, $sts_crm_dir, $sts_error_level_label;
		
		if( $this->debug ) echo "<p>".__METHOD__.": $pk, $state ".
			(isset($this->state_name[$state]) ? $this->state_name[$state] : 'unknown')." / ".
			(isset($this->state_behavior[$state]) ? $this->state_behavior[$state] : 'unknown')."</p>";
		
		$this->state_change_post = '';
		$this->last_activity = 0;
		
		// Fetch current state etc.
		$result = $this->fetch_rows( $this->primary_key.' = '.$pk, 
					"CLIENT_NAME, CURRENT_STATUS, CHANGED_BY, CHANGED_DATE");

		if( is_array($result) && isset($result[0]) && isset($result[0]['CURRENT_STATUS']) ) {
			$name = $result[0]['CLIENT_NAME'];
			$current_state = $result[0]['CURRENT_STATUS'];
			$changed_by = isset($result[0]['CHANGED_BY']) ? $result[0]['CHANGED_BY'] : "unknown";
			$changed_date = isset($result[0]['CHANGED_DATE']) ? $result[0]['CHANGED_DATE'] : 0;

			if( $this->debug ) echo "<p>".__METHOD__.": current_state = $current_state, 
				cstate = $cstate</p>";
			
			// !Check to see if the current state does not match.
			// This likely means a screen is out of date, and someone already updated the client.
			if( $cstate > 0 && $cstate <> $current_state ) {
				$ok_to_continue = false;
				$this->state_change_level = EXT_ERROR_WARNING;
				$this->state_change_error = "Client state is out of date. Likely someone updated it before you could.<br>".
					" Client $name was last updated by <strong>".$changed_by."</strong>".
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
					$this->state_change_error = "Client $name cannot transition from ".$this->state_name[$current_state]." to ".$this->state_name[$state];
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
					//! Add activity record
					$this->last_activity = $this->cat->add_activity( $pk, $state );
					
					//! Post state change triggers
					switch( $this->state_behavior[$state] ) {

						case 'entry':	//! entry
							$result = $this->update( $this->primary_key.' = '.$pk, array( 'CLIENT_TYPE' => 'lead' ) );
							if( $result )
								$result2 = $this->cat->enter_lead( $pk );
							break;
	
						case 'assign':	//! assign
							$result = $this->update( $this->primary_key.' = '.$pk.
								" AND CLIENT_TYPE = 'lead'",
								array( 'CLIENT_TYPE' => 'prospect' ) );
							break;
	
						case 'sold':	//! sold
							$result = $this->update( $this->primary_key.' = '.$pk.
								" AND CLIENT_TYPE = 'prospect'",
								array( 'CLIENT_TYPE' => 'client' ) );
							break;
	
					}
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
	



	public function suggest( $query, $type = 'BILL_TO' ) {

		if( $this->debug ) {
			echo "<p>suggest $query, $type</p>";
			ob_flush(); flush();
		}
		switch( strtolower($type) ) {
			case 'shipper':
			case 'consignee':
			case 'dock':
				$match2 = 'AND '.strtoupper($type).' = TRUE';
				break;

			case 'caller':
				$match2 = '';
				break;
			
			case 'bill_to': //! SCR# 446 - Do NOT suggest clients on credit hold
			default:
				$match2 = 'AND '.'BILL_TO = TRUE AND ON_CREDIT_HOLD = FALSE';
				break;
		}
		
		if( strtolower($type) == 'caller' )
			$match = "(CLIENT_NAME like '".$this->real_escape_string( $query )."%'
				OR CONTACT_NAME like '".$this->real_escape_string( $query )."%'
				OR ZIP_CODE like '".$this->real_escape_string( $query )."%'".
				($this->client_id ? " OR CLIENT_ID like '".$this->real_escape_string( $query )."%'" : "").
				($this->export_sage50 ? " OR SAGE50_CLIENTID like '".$this->real_escape_string( $query )."%'" : "").")";
		else
			$match = "(CLIENT_NAME like '".$this->real_escape_string( $query )."%'
				OR (COALESCE(LABEL, '') <> '' 
					AND LABEL like '".$this->real_escape_string( $query )."%')
				OR ZIP_CODE like '".$this->real_escape_string( $query )."%'".
				($this->client_id ? " OR CLIENT_ID like '".$this->real_escape_string( $query )."%'" : "").
				($this->export_sage50 ? " OR SAGE50_CLIENTID like '".$this->real_escape_string( $query )."%'" : "").")";

		$query2 = "SELECT CLIENT_CODE, CLIENT_NAME, BILL_TO, ON_CREDIT_HOLD, SALES_PERSON,
			CONTACT_TYPE, COALESCE(LABEL,CLIENT_NAME) LABEL, CONTACT_NAME, ADDRESS, ADDRESS2, CITY, STATE,
			ZIP_CODE, GET_TIMEZONE(ZIP_CODE) AS TZONE, COALESCE(COUNTRY,'USA') AS COUNTRY,
			PHONE_OFFICE, PHONE_EXT, PHONE_FAX, PHONE_CELL, EMAIL,
			DEFAULT_EQUIPMENT, DEFAULT_COMMODITY, TERMINAL_ZONE,
			ADDR_VALID, LAT, LON, VALID_SOURCE, SAGE50_CLIENTID, CLIENT_ID, BUSINESS_CODE
			FROM $this->table_name
			JOIN EXP_CONTACT_INFO
			ON CONTACT_CODE = CLIENT_CODE
			AND CONTACT_SOURCE = 'client'
			AND CONTACT_TYPE = '".strtolower($type)."'
			AND EXP_CONTACT_INFO.ISDELETED = FALSE
			
			WHERE $match
			$match2
			AND $this->table_name.ISDELETED = FALSE
			AND CLIENT_TYPE = 'client'
			ORDER BY CLIENT_NAME ASC, CONTACT_NAME ASC
			LIMIT 0,".$this->matches;
		
		$result = $this->database->get_multiple_rows($query2);
		
		if( is_array($result) && count($result) > 0 ) {
			foreach( $result as $row ) {
				if( isset($row['ZIP_CODE']) && (! isset($row['TZONE']) || empty($row['TZONE'])) ) {
					if( ! empty($row['ZIP_CODE']) && ! empty($row['COUNTRY']) ) {
						$pcm = new sts_pcmiler_api( $this->database, $this->debug );
						$check = 'unknown';
						if( $pcm->use_pcm() ) {
							$check = $pcm->validate_address( [ 'ZIP_CODE' => $row['ZIP_CODE'], 'COUNTRY' => $row['COUNTRY'] ]);
						}
					
						if( $check == 'valid' ) {
							$email = sts_email::getInstance($this->database, $this->debug);
							$email->send_alert(__METHOD__."($query): Missing Timezone FIXED for ".$row['ZIP_CODE']."<br>
							PCM check = ".$check." code = ".$pcm->code." description = ".$pcm->description."<br>"
							 );
						} else if( false ) {
							$email = sts_email::getInstance($this->database, $this->debug);
							$email->send_alert(__METHOD__."($query): Missing Timezone for ZIP_CODE ".$row['ZIP_CODE']."!!<br>
							PCM check = ".$check." code = ".$pcm->code." description = ".$pcm->description."
							<pre>".
							print_r($row, true).'</pre>' );
						}
					}
				}
			}
		}

		return $result;
	
	}
	
	//! SCR# 852 - Containers Feature
	public function im_dock( $code ) {
		if( $this->debug ) echo "<p>".__METHOD__.": entry $code</p>";
		
		$query = "SELECT CLIENT_CODE, CLIENT_NAME, BILL_TO, ON_CREDIT_HOLD, SALES_PERSON,
			CONTACT_TYPE, COALESCE(LABEL,CLIENT_NAME) LABEL, CONTACT_NAME, ADDRESS, ADDRESS2, CITY, STATE,
			ZIP_CODE, GET_TIMEZONE(ZIP_CODE) AS TZONE, COALESCE(COUNTRY,'USA') AS COUNTRY,
			PHONE_OFFICE, PHONE_EXT, PHONE_FAX, PHONE_CELL, EMAIL,
			DEFAULT_EQUIPMENT, DEFAULT_COMMODITY, TERMINAL_ZONE,
			ADDR_VALID, LAT, LON, VALID_SOURCE, SAGE50_CLIENTID, CLIENT_ID, BUSINESS_CODE
			FROM $this->table_name
			JOIN EXP_CONTACT_INFO
			ON CONTACT_CODE = CLIENT_CODE
			AND CLIENT_CODE = $code
			AND CONTACT_SOURCE = 'client'
			AND EXP_CONTACT_INFO.ISDELETED = FALSE
			
			WHERE $this->table_name.ISDELETED = FALSE
			AND CLIENT_TYPE = 'client'
			ORDER BY CLIENT_NAME ASC, CONTACT_NAME ASC
			LIMIT 1";

		$result = $this->database->get_one_row($query);

		return $result;
	}

	
	//! SCR# 628 - issue with renamed columns from SCR# 614
	public function get_customs_broker( $pk, $choice = 0 ) {

		$query1 = "SELECT CONTACT_INFO_CODE, LABEL
			FROM EXP_CLIENT
			JOIN EXP_CONTACT_INFO
			ON CONTACT_CODE = CLIENT_CODE
			AND CONTACT_SOURCE = 'client'
			AND CONTACT_TYPE = 'customs broker'
			AND EXP_CONTACT_INFO.ISDELETED = FALSE
			
			WHERE CLIENT_CODE = $pk
			AND $this->table_name.ISDELETED = FALSE
			ORDER BY CONTACT_NAME ASC, CONTACT_NAME ASC";
		
		$query2 = "SELECT CONTACT_INFO_CODE, LABEL, CONTACT_NAME, ADDRESS, ADDRESS2, CITY, STATE,
			ZIP_CODE, COUNTRY, PHONE_OFFICE, PHONE_EXT, PHONE_FAX, PHONE_CELL, EMAIL,
			ADDR_VALID, ADDR_CODE, ADDR_DESCR
			FROM EXP_CLIENT
			JOIN EXP_CONTACT_INFO
			ON CONTACT_CODE = CLIENT_CODE
			AND CONTACT_SOURCE = 'client'
			AND CONTACT_TYPE = 'customs broker'
			AND EXP_CONTACT_INFO.ISDELETED = FALSE
			
			WHERE CLIENT_CODE = $pk
			".($choice > 0 ? "AND CONTACT_INFO_CODE = ".$choice : "")."
			AND $this->table_name.ISDELETED = FALSE
			ORDER BY CONTACT_NAME ASC
			LIMIT 1";
		
		$result1 = $this->database->get_multiple_rows($query1);
		
		$result2 = $this->database->get_multiple_rows($query2);
		
		$result = array();
		if( is_array($result1) && count($result1) > 0 )
			$result['CHOICES'] = $result1;
		
		if( is_array($result2) && count($result2) > 0 )
			$result['SELECTED'] = $result2[0];

		return $result;
	
	}
	
	public function get_billto_info( $pk ) {
		
		$query = "SELECT CLIENT_NAME, CONTACT_INFO_CODE, LABEL, CONTACT_NAME, ADDRESS, ADDRESS2, CITY, STATE,
			ZIP_CODE, COUNTRY, PHONE_OFFICE, PHONE_EXT, PHONE_FAX, PHONE_CELL, EMAIL,
			ADDR_VALID, ADDR_CODE, ADDR_DESCR
			FROM EXP_CLIENT
			JOIN EXP_CONTACT_INFO
			ON CONTACT_CODE = CLIENT_CODE
			AND CONTACT_SOURCE = 'client'
			AND CONTACT_TYPE = 'bill_to'
			AND EXP_CONTACT_INFO.ISDELETED = FALSE
			
			WHERE CLIENT_CODE = $pk
			AND BILL_TO = TRUE AND ON_CREDIT_HOLD = FALSE
			AND EXP_CLIENT.ISDELETED = FALSE
			ORDER BY CLIENT_NAME ASC, CONTACT_NAME ASC
			LIMIT 1";
	
		$result = $this->database->get_multiple_rows($query);
		
		return $result;
	}
	
	public function get_pallet_count( $pk ) {
		return $this->fetch_rows( $this->primary_key." = ".$pk, "PALLET_COUNT" );
	}

	//! Are there existing clients that match
	// return a list
	//! SCR# 377 - do not match if fields are blank
	//! SCR# 573 - escape all the parameters
	//! 1/28/2022 - remove trailing spaces, make upper case for comparison
	// Also if only the name matches (more strict, easier to count as a duplicate match)
	//! IF YOU CHANGE THIS, also change exp_editclient.php
	public function check_match( $name, $address, $city, $state, $zip_code, $country ) {
		return $this->database->get_multiple_rows("
			SELECT L.CLIENT_CODE, L.CLIENT_TYPE, L.CURRENT_STATUS, L.CLIENT_NAME,
			C.CONTACT_TYPE, C.ADDRESS, C.CITY, C.STATE,
			C.ZIP_CODE, C.COUNTRY
			FROM EXP_CLIENT L
			LEFT JOIN EXP_CONTACT_INFO C
			ON C.CONTACT_CODE = L.CLIENT_CODE
			AND C.CONTACT_SOURCE = 'client'
			AND C.CONTACT_TYPE IN ('bill_to', 'company', 'shipper', 'consignee')
			AND L.ISDELETED = 0
			
			WHERE (COALESCE(L.CLIENT_NAME, '') != '' AND COALESCE(C.CITY, '') != '' AND
				COALESCE(C.STATE, '') != ''
				AND UPPER(TRIM(L.CLIENT_NAME)) = UPPER(TRIM('".$this->real_escape_string($name)."'))
				AND UPPER(TRIM(C.CITY)) = UPPER(TRIM('".$this->real_escape_string($city)."'))
				AND UPPER(TRIM(C.STATE)) = UPPER(TRIM('".$this->real_escape_string($state)."')))
			OR (COALESCE(L.CLIENT_NAME, '') != '' AND COALESCE(C.ZIP_CODE, '') != ''
				AND UPPER(TRIM(L.CLIENT_NAME)) = UPPER(TRIM('".$this->real_escape_string($name)."'))
				AND C.ZIP_CODE = '".$this->real_escape_string($zip_code)."')
			OR (COALESCE(C.ADDRESS, '') != '' AND COALESCE(C.CITY, '') != ''
				AND UPPER(TRIM(C.ADDRESS)) = UPPER(TRIM('".$this->real_escape_string($address)."'))
				AND UPPER(TRIM(C.CITY)) = UPPER(TRIM('".$this->real_escape_string($city)."')))
				
			OR (UPPER(TRIM(COALESCE(L.CLIENT_NAME, ''))) = UPPER(TRIM('".$this->real_escape_string($name)."'))
				AND UPPER(TRIM(COALESCE(C.CITY, ''))) = UPPER(TRIM('".$this->real_escape_string($city)."'))
				AND UPPER(TRIM(COALESCE(C.STATE, ''))) = UPPER(TRIM('".$this->real_escape_string($state)."')))
			OR (COALESCE(L.CLIENT_NAME, '') != ''
				AND UPPER(TRIM(L.CLIENT_NAME)) = UPPER(TRIM('".$this->real_escape_string($name)."')))
			");
			// NAME, CITY and STATE match
			// NAME and ZIP_CODE match
			// ADDRESS and CITY match
			// NAME, CITY and STATE match (including blank ones)

	}
	
	//! SCR# 722 - Email notification of new shipper/consignee
	public function new_shipcons( $client_code ) {
		if( $this->debug ) echo "<p>".__METHOD__.": $client_code</p>";

		$check = $this->database->get_one_row( "SELECT *,
			(SELECT EMAIL FROM EXP_USER
			WHERE USER_CODE = EXP_CLIENT.CREATED_BY
			LIMIT 1) AS CREATED_EMAIL,
			(SELECT EMAIL FROM EXP_USER u, EXP_SHIPMENT s
			WHERE u.USER_CODE = s.SALES_PERSON
			AND (s.SHIPPER_CLIENT_CODE = CLIENT_CODE
			OR s.CONS_CLIENT_CODE = CLIENT_CODE)
			LIMIT 1) AS SALES_EMAIL,
			(SELECT SHIPMENT_CODE
				FROM EXP_SHIPMENT
				WHERE SHIPPER_CLIENT_CODE = $client_code
				or CONS_CLIENT_CODE = $client_code
				limit 1) AS SHIPMENT_CODE,
			(SELECT SS_NUMBER
				FROM EXP_SHIPMENT
				WHERE SHIPPER_CLIENT_CODE = $client_code
				or CONS_CLIENT_CODE = $client_code
				limit 1) AS SS_NUMBER

			FROM EXP_CLIENT
			LEFT JOIN EXP_CONTACT_INFO
			ON CONTACT_CODE = CLIENT_CODE
			AND CONTACT_SOURCE = 'client'
			WHERE CLIENT_CODE = $client_code
			LIMIT 1" );
		$notify_address = false;
		$subject = '';
		$message = '';
			
		if( is_array($check) &&
			! empty($check['CLIENT_NAME']) &&
			(! empty($check['SHIPPER']) || ! empty($check['CONSIGNEE'])) &&
			! empty($check['CREATED_EMAIL'])
		) {
			$name = $check['CLIENT_NAME'];
			$type = $check['SHIPPER'] == 1 ? 'shipper' : 'consignee';
			
			$address = $check['ADDRESS']."<br>".
				(empty($check['ADDRESS2']) ? '' : $check['ADDRESS2']."<br>").
				(empty($check['CITY']) ? '' : $check['CITY'].", ").
				(empty($check['STATE']) ? '' : $check['STATE'].", ").
				(empty($check['ZIP_CODE']) ? '' : $check['ZIP_CODE'])."<br>".
				(empty($check['COUNTRY']) ? '' : $check['COUNTRY']);
				
			$shipment = (empty($check['SHIPMENT_CODE']) ? '' :
				'On Shipment '.$check['SHIPMENT_CODE'].
				(empty($check['SS_NUMBER']) ? '' : ' / '.$check['SS_NUMBER']));
			
			$notify_address = $check['CREATED_EMAIL'];
			if( ! empty($check['SALES_EMAIL']) && $check['SALES_EMAIL'] <> $notify_address )
				$notify_address .= ','.$check['SALES_EMAIL'];
			$subject = "New $type notification - $name";
			
			$message = "You just created a new $type for your customer.
<br>
<br>
Name: <strong>$name</strong>
<br>
<br>
<strong>$address</strong>
<br>
<br>
$shipment
<br>
";

		} else {
			if( $this->debug ) {
				echo "<pre>".__METHOD__.": Failed:\n";
				var_dump($check);
				echo "</pre>";
			}
		}
		if( $this->debug ) {
			echo "<pre>";
			var_dump($notify_address, $subject, $message);
			echo "</pre>";
		}

		return [$notify_address, $subject, $message];
	}

}

class sts_client_lj extends sts_client {

	// Constructor does not need the table name
	public function __construct( $database, $debug = false ) {

		if( $debug ) echo "<p>Create sts_client_lj</p>";
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

	// Fetch one or more rows
	public function fetch_rows( $match = "", $fields = "*", $order = "", $limit = "", $groupby = "", $match2 = "" ) {
		if( $this->debug ) echo "<p>fetch_rows $match</p>";
		
		//! SCR# 268 - added BROKERS column
		$result = $this->database->get_multiple_rows("SELECT $fields FROM
			(SELECT CLIENT_CODE, CLIENT_NAME, CLIENT_ID, SAGE50_CLIENTID, ISDELETED, 
			CLIENT_TYPE, CURRENT_STATUS, CURRENCY_CODE, INSURANCE_CURRENCY, TERMS,
			SALES_PERSON, SHIPPER, CONSIGNEE, DOCK, BILL_TO, INTERMODAL, CTPAT_REQUIRED, ON_CREDIT_HOLD,
			DEFAULT_EQUIPMENT, DEFAULT_ZONE,
			TRACK_PALLETS, PALLET_COUNT, CHANGED_DATE, CHANGED_BY, EMAIL,
			CITY, ZIP_CODE, STATE, ADDRESS,
			CONCAT(COALESCE(ADDRESS,''),
			(CASE WHEN ADDRESS2 IS NOT NULL THEN CONCAT('<BR>',ADDRESS2) ELSE '' END),
			'<BR>',COALESCE(CITY,''),', ',COALESCE(STATE,''),', ',COALESCE(ZIP_CODE,'')) AS ADDRESS1,
			CONCAT_WS('+',PHONE_OFFICE, PHONE_EXT) AS PHONE1,
			(select GROUP_CONCAT(LABEL ORDER BY LABEL ASC SEPARATOR '<br>')
				FROM EXP_CONTACT_INFO
				WHERE CONTACT_CODE = CLIENT_CODE
				AND CONTACT_SOURCE = 'client'
				AND CONTACT_TYPE = 'customs broker') AS BROKERS
	
			from EXP_CLIENT
	        LEFT JOIN 
            (SELECT CONTACT_INFO_CODE, CONTACT_CODE, CONTACT_SOURCE, CONTACT_TYPE,
            LABEL, CONTACT_NAME, JOB_TITLE, ADDRESS, ADDRESS2, CITY,  STATE,
            ZIP_CODE, COUNTRY, ADDR_VALID, ADDR_CODE, ADDR_DESCR, VALID_SOURCE,
            LAT, LON, PHONE_OFFICE, PHONE_EXT, PHONE_FAX, PHONE_HOME, PHONE_CELL,
            EMAIL
			FROM EXP_CONTACT_INFO) X
	        ON X.CONTACT_CODE = CLIENT_CODE AND X.CONTACT_SOURCE = 'client'
	        GROUP BY CLIENT_CODE, CLIENT_NAME, CLIENT_ID, SAGE50_CLIENTID, ISDELETED, 
				CLIENT_TYPE, CURRENT_STATUS, CURRENCY_CODE, INSURANCE_CURRENCY, TERMS,
				SALES_PERSON, SHIPPER, CONSIGNEE, DOCK, BILL_TO, CTPAT_REQUIRED, ON_CREDIT_HOLD,
				DEFAULT_EQUIPMENT, DEFAULT_ZONE,
				TRACK_PALLETS, PALLET_COUNT, CHANGED_DATE, CHANGED_BY, EMAIL, 
				CITY, ZIP_CODE, STATE, ADDRESS, ADDRESS1, PHONE1
	        ) EXP_CLIENT
			
			".($match <> "" ? "WHERE $match" : "")."
			".($fields == "COUNT(*) AS CNT" ? "" : "GROUP BY CLIENT_CODE")."
			".($order <> "" ? "ORDER BY $order" : "")."
			".($limit <> "" ? "LIMIT $limit" : "") );

		//! SCR# 379 - added BROKERS column, missed from SCR# 268
		if( strpos($fields, 'SQL_CALC_FOUND_ROWS') !== false) {
			$result1 = $this->database->get_one_row( "SELECT COUNT(*) AS FOUND
			FROM (SELECT CLIENT_CODE, CLIENT_NAME, CLIENT_ID, SAGE50_CLIENTID, ISDELETED, 
			CLIENT_TYPE, CURRENT_STATUS, CURRENCY_CODE, INSURANCE_CURRENCY, TERMS,
			SALES_PERSON, SHIPPER, CONSIGNEE, DOCK, BILL_TO, CTPAT_REQUIRED, ON_CREDIT_HOLD,
			DEFAULT_EQUIPMENT, DEFAULT_ZONE,
			TRACK_PALLETS, PALLET_COUNT, CHANGED_DATE, CHANGED_BY, EMAIL,
			CONCAT(COALESCE(ADDRESS,''),
			(CASE WHEN ADDRESS2 IS NOT NULL THEN CONCAT('<BR>',ADDRESS2) ELSE '' END),
			'<BR>',COALESCE(CITY,''),', ',COALESCE(STATE,''),', ',COALESCE(ZIP_CODE,'')) AS ADDRESS1,
			CONCAT_WS('+',PHONE_OFFICE, PHONE_EXT) AS PHONE1,
			(select GROUP_CONCAT(LABEL ORDER BY LABEL ASC SEPARATOR '<br>')
				FROM EXP_CONTACT_INFO
				WHERE CONTACT_CODE = CLIENT_CODE
				AND CONTACT_SOURCE = 'client'
				AND CONTACT_TYPE = 'customs broker') AS BROKERS
	
			from EXP_CLIENT
	        LEFT JOIN 
            (SELECT CONTACT_INFO_CODE, CONTACT_CODE, CONTACT_SOURCE, CONTACT_TYPE,
            LABEL, CONTACT_NAME, JOB_TITLE, ADDRESS, ADDRESS2, CITY,  STATE,
            ZIP_CODE, COUNTRY, ADDR_VALID, ADDR_CODE, ADDR_DESCR, VALID_SOURCE,
            LAT, LON, PHONE_OFFICE, PHONE_EXT, PHONE_FAX, PHONE_HOME, PHONE_CELL,
            EMAIL
			FROM EXP_CONTACT_INFO) X
	        ON X.CONTACT_CODE = CLIENT_CODE AND X.CONTACT_SOURCE = 'client'
	        GROUP BY CLIENT_CODE, CLIENT_NAME, CLIENT_ID, SAGE50_CLIENTID, ISDELETED, 
				CLIENT_TYPE, CURRENT_STATUS, CURRENCY_CODE, INSURANCE_CURRENCY, TERMS,
				SALES_PERSON, SHIPPER, CONSIGNEE, DOCK, BILL_TO, CTPAT_REQUIRED, ON_CREDIT_HOLD,
				DEFAULT_EQUIPMENT, DEFAULT_ZONE,
				TRACK_PALLETS, PALLET_COUNT, CHANGED_DATE, CHANGED_BY, EMAIL, ADDRESS1, PHONE1
	        ) EXP_CLIENT
				WHERE $match" );
			$this->found_rows = is_array($result1) && isset($result1["FOUND"]) ? $result1["FOUND"] : 0;
			if( $this->debug ) echo "<p>found_rows = $this->found_rows</p>";
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

//! Form Specifications - For use with sts_form

$sts_form_addclient_form = array(	//! $sts_form_addclient_form
	'title' => '<span class="glyphicon glyphicon-user"></span> Add Client',
	'action' => 'exp_addclient.php',
	//'actionextras' => 'disabled',
	'cancel' => 'exp_listclient.php',
	'name' => 'addclient',
	'okbutton' => 'Save Changes',
	'cancelbutton' => 'Cancel',	// well well-sm
		'layout' => '
	<div class="form-group">
		<div class="col-sm-4">
			<div class="form-group tighter">
				<label for="CLIENT_NAME" class="col-sm-4 control-label">#CLIENT_NAME#</label>
				<div class="col-sm-8">
					%CLIENT_NAME%
				</div>
			</div>
			<div class="form-group tighter">
				<label for="LEAD_SOURCE_CODE" class="col-sm-4 control-label">#LEAD_SOURCE_CODE#</label>
				<div class="col-sm-8">
					%LEAD_SOURCE_CODE%
				</div>
			</div>
			<div class="form-group tighter">
				%CLIENT_GROUP_CODE%
				<label for="CLIENT_GROUP" class="col-sm-4 control-label">#CLIENT_GROUP#</label>
				<div class="col-sm-8">
					%CLIENT_GROUP%
				</div>
			</div>
			<!-- CLIENT_ID_1 -->
			<div class="form-group tighter">
				<label for="CLIENT_ID" class="col-sm-4 control-label">#CLIENT_ID#</label>
				<div class="col-sm-8">
					%CLIENT_ID%
				</div>
			</div>
			<!-- CLIENT_ID_2 -->
			<!-- SAGE50_1 -->
			<div class="form-group tighter">
				<label for="SAGE50_CLIENTID" class="col-sm-4 control-label">#SAGE50_CLIENTID#</label>
				<div class="col-sm-8">
					%SAGE50_CLIENTID%
				</div>
			</div>
			<!-- SAGE50_2 -->
			<div class="form-group tighter">
				<label for="CURRENCY_CODE" class="col-sm-4 control-label">#CURRENCY_CODE#</label>
				<div class="col-sm-8">
					%CURRENCY_CODE%
				</div>
			</div>
			<div class="form-group tighter">
				<label for="DEFAULT_ZONE" class="col-sm-4 control-label">#DEFAULT_ZONE#</label>
				<div class="col-sm-8">
					%DEFAULT_ZONE%
				</div>
			</div>
			<div class="form-group tighter">
				<label for="TERMINAL_ZONE" class="col-sm-4 control-label">#TERMINAL_ZONE#</label>
				<div class="col-sm-8">
					%TERMINAL_ZONE%
				</div>
			</div>
			<div class="form-group tighter">
				<label for="OPEN_TIME" class="col-sm-4 control-label">#OPEN_TIME#</label>
				<div class="col-sm-8">
					%OPEN_TIME%
				</div>
			</div>
			<div class="form-group tighter">
				<label for="CLOSE_TIME" class="col-sm-4 control-label">#CLOSE_TIME#</label>
				<div class="col-sm-8">
					%CLOSE_TIME%
				</div>
			</div>
			<div class="form-group tighter">
				<label for="CUSTOMS_BROKER" class="col-sm-4 control-label">#CUSTOMS_BROKER#</label>
				<div class="col-sm-8">
					%CUSTOMS_BROKER%
				</div>
			</div>
			<div class="form-group tighter">
				<label for="DEFAULT_COMMODITY" class="col-sm-4 control-label">#DEFAULT_COMMODITY#</label>
				<div class="col-sm-8">
					%DEFAULT_COMMODITY%
				</div>
			</div>
			<div class="form-group tighter">
				<label for="DEFAULT_EQUIPMENT" class="col-sm-4 control-label">#DEFAULT_EQUIPMENT#</label>
				<div class="col-sm-8">
					%DEFAULT_EQUIPMENT%
				</div>
			</div>
			<div class="form-group tighter">
				<label for="BUSINESS_CODE" class="col-sm-4 control-label">#BUSINESS_CODE#</label>
				<div class="col-sm-8">
					%BUSINESS_CODE%
				</div>
			</div>
			<div class="well well-sm tighter">
				<div class="form-group tighter">
					<div class="col-sm-6">
						<a href="https://en.wikipedia.org/wiki/Customs-Trade_Partnership_Against_Terrorism" target="_blank" class="tip" title="Customs-Trade Partnership Against Terrorism"><img src="images/logo-ctpat.jpg" alt="logo-ctpat" width="100%" /></a>
					</div>
					<div class="col-sm-6">
						%CTPAT_REQUIRED%<br>
						#CTPAT_REQUIRED#
					</div>
				</div>
			</div>
			<!-- NOTIFY_1 -->
			<div class="panel panel-info">
				<div class="panel-heading">
					<h3 class="panel-title"><span class="glyphicon glyphicon-envelope"></span> #EMAIL_NOTIFY#</h3>
				</div>
				<div class="panel-body">
					<div class="form-group tighter">
						<div class="col-sm-12">
							%EMAIL_NOTIFY%
						</div>
					</div>
					<p class="text-info">Put an email address here to receive notifications of picks and drops. Leave blank for no notification.</p>

				<label for="NOTIFY_ARRIVE_SHIPPER" class="col-sm-4 control-label">#NOTIFY_ARRIVE_SHIPPER#</label>
				<div class="col-sm-2">
					%NOTIFY_ARRIVE_SHIPPER%
				</div>
				<label for="NOTIFY_DEPART_SHIPPER" class="col-sm-4 control-label">#NOTIFY_DEPART_SHIPPER#</label>
				<div class="col-sm-2">
					%NOTIFY_DEPART_SHIPPER%
				</div>
				<label for="NOTIFY_ARRIVE_CONS" class="col-sm-4 control-label">#NOTIFY_ARRIVE_CONS#</label>
				<div class="col-sm-2">
					%NOTIFY_ARRIVE_CONS%
				</div>
				<label for="NOTIFY_DEPART_CONS" class="col-sm-4 control-label">#NOTIFY_DEPART_CONS#</label>
				<div class="col-sm-2">
					%NOTIFY_DEPART_CONS%
				</div>

				</div>
			</div>
			<!-- NOTIFY_2 -->
		</div>
		<div class="col-sm-4">
			<div class="form-group tighter">
				<label for="MILEAGE_TYPE" class="col-sm-4 control-label">#MILEAGE_TYPE#</label>
				<div class="col-sm-8">
					%MILEAGE_TYPE%
				</div>
			</div>
			<div class="form-group well well-sm tip-bottom" data-toggle="tooltip" title="Shipper = client can be a shipper<br>
Consignee = client can be a consignee<br>
Bill-to = client can be a bill-to<br>
POD Needed = proof of delivery needed<br>
Appt Needed = appointment needed<br>
Auto rate = use auto rating">
				<label for="SHIPPER" class="col-sm-4 control-label">#SHIPPER#</label>
				<div class="col-sm-2">
					%SHIPPER%
				</div>
				<label for="CONSIGNEE" class="col-sm-4 control-label">#CONSIGNEE#</label>
				<div class="col-sm-2">
					%CONSIGNEE%
				</div>
				<label for="DOCK" class="col-sm-4 control-label">#DOCK#</label>
				<div class="col-sm-2">
					%DOCK%
				</div>
				<label for="BILL_TO" class="col-sm-4 control-label">#BILL_TO#</label>
				<div class="col-sm-2">
					%BILL_TO%
				</div>
				<label for="POD_NEEDED" class="col-sm-4 control-label">#POD_NEEDED#</label>
				<div class="col-sm-2">
					%POD_NEEDED%
				</div>
				<label for="APPOINTMENT_NEEDED" class="col-sm-4 control-label">#APPOINTMENT_NEEDED#</label>
				<div class="col-sm-2">
					%APPOINTMENT_NEEDED%
				</div>
				<!-- INTERMODAL1 -->
				<label for="INTERMODAL" class="col-sm-4 control-label">#INTERMODAL#</label>
				<div class="col-sm-2">
					%INTERMODAL%
				</div>
				<!-- INTERMODAL2 -->
			</div>
			<div id="CLIENT_WARNINGS">
			</div>

			<div class="form-group tighter">
				<label for="CONTACT" class="col-sm-4 control-label">#CONTACT#</label>
				<div class="col-sm-8">
					%CONTACT%
				</div>
			</div>
			<div class="form-group tighter">
				<label for="SALES_PERSON" class="col-sm-4 control-label">#SALES_PERSON#</label>
				<div class="col-sm-8">
					%SALES_PERSON%
				</div>
			</div>
			<div class="form-group tighter">
				<label for="BILLING_CLIENT" class="col-sm-4 control-label">#BILLING_CLIENT#</label>
				<div class="col-sm-8">
					%BILLING_CLIENT%
				</div>
			</div>
			<div class="form-group tighter">
				<label for="API" class="col-sm-4 control-label">#API#</label>
				<div class="col-sm-8">
					%API%
				</div>
			</div>
			<div class="form-group tighter">
				<label for="CODE_TYPE" class="col-sm-4 control-label">#CODE_TYPE#</label>
				<div class="col-sm-8">
					%CODE_TYPE%
				</div>
			</div>
			<div class="form-group tighter">
				<label for="CLIENT_URL" class="col-sm-4 control-label">#CLIENT_URL#</label>
				<div class="col-sm-8">
					%CLIENT_URL%
				</div>
			</div>
			<!-- EQUIPMENT -->
		</div>
		<div class="col-sm-4">
			<div class="form-group  well well-sm">
			<div class="form-group tighter">
				<label for="ON_CREDIT_HOLD" class="col-sm-4 control-label">#ON_CREDIT_HOLD#</label>
				<div class="col-sm-8">
					%ON_CREDIT_HOLD%
				</div>
			</div>
			<div class="form-group tighter">
				<label for="CREDIT_STATUS" class="col-sm-4 control-label">#CREDIT_STATUS#</label>
				<div class="col-sm-8">
					%CREDIT_STATUS%
				</div>
			</div>
			<div class="form-group tighter">
				<label for="CREDIT_LIMIT" class="col-sm-4 control-label">#CREDIT_LIMIT#</label>
				<div class="col-sm-8">
					%CREDIT_LIMIT%
				</div>
			</div>
			<div class="form-group tighter">
				<label for="TERMS" class="col-sm-4 control-label">#TERMS#</label>
				<div class="col-sm-8">
					%TERMS%
				</div>
			</div>
			<div class="form-group tighter">
				<label for="COD_MINIMUM" class="col-sm-4 control-label">#COD_MINIMUM#</label>
				<div class="col-sm-8">
					%COD_MINIMUM%
				</div>
			</div>
			<div class="form-group tighter">
				<label for="COD_MAXIMUM" class="col-sm-4 control-label">#COD_MAXIMUM#</label>
				<div class="col-sm-8">
					%COD_MAXIMUM%
				</div>
			</div>
			</div>
			<div class="form-group tighter">
				<label for="UOM" class="col-sm-4 control-label">#UOM#</label>
				<div class="col-sm-8">
					%UOM%
				</div>
			</div>
			<div class="form-group tighter">
				<label for="DUNS_CODE" class="col-sm-4 control-label">#DUNS_CODE#</label>
				<div class="col-sm-8">
					%DUNS_CODE%
				</div>
			</div>
			<!-- EXTRA_1 -->
			<div class="well well-sm tighter">
				<div class="form-group tighter">
					<label for="CLIENT_EXTRA_STOPS" class="col-sm-6 control-label">#CLIENT_EXTRA_STOPS#</label>
					<div class="col-sm-6">
						%CLIENT_EXTRA_STOPS%
					</div>
				</div>
			</div>
			<!-- EXTRA_2 -->
			<div class="bg-info tighter">
				<div class="form-group tighter">
					<label for="BATCH_EMAIL" class="col-sm-6 control-label">#BATCH_EMAIL#</label>
					<div class="col-sm-6">
						%BATCH_EMAIL%
					</div>
				</div>
				<p>This is for sending invoices to Bill-to clients only, and you need an email in the bill_to contact info</p>
			</div>
			<div class="well well-sm tighter">
				<div class="form-group tighter">
					<label for="TRACK_PALLETS" class="col-sm-6 control-label">#TRACK_PALLETS#</label>
					<div class="col-sm-6">
						%TRACK_PALLETS%
					</div>
				</div>
				<div class="form-group tighter">
					<label for="PALLET_COUNT" class="col-sm-6 control-label">#PALLET_COUNT#</label>
					<div class="col-sm-6">
						%PALLET_COUNT%
					</div>
				</div>
			</div>
			<div class="panel panel-danger" id="CDN_TAX">
				<div class="panel-heading">
					<h3 class="panel-title"><img src="images/flag-ca.png" alt="flag-ca" width="48" height="24" /> Tax Exemption (<a href="http://www.cra-arc.gc.ca/tx/bsnss/tpcs/gst-tps/thr/sctrs/frghtcrrrs-eng.html#appfrtrcarr" target="_blank">info <span class="glyphicon glyphicon-link"></span></a>)</h3>
				</div>
				<div class="panel-body">
					<div class="form-group tighter">
						<label for="CDN_TAX_EXEMPT" class="col-sm-4 control-label">#CDN_TAX_EXEMPT#</label>
						<div class="col-sm-8">
							%CDN_TAX_EXEMPT%
						</div>
					</div>
					<div class="form-group tighter" id="EXEMPT_REASON" hidden>
						<label for="CDN_TAX_EXEMPT_REASON" class="col-sm-4 control-label">#CDN_TAX_EXEMPT_REASON#</label>
						<div class="col-sm-8">
							%CDN_TAX_EXEMPT_REASON%
						</div>
					</div>
				</div>
			</div>
		</div>
	</div>
	<div class="form-group">
		<div class="col-sm-8 well well-sm">
			<div class="form-group tighter">
				<label for="CLIENT_NOTES" class="col-sm-2 control-label">#CLIENT_NOTES#</label>
				<div class="col-sm-10">
					%CLIENT_NOTES%
				</div>
			</div>
			<div class="form-group tighter" id="snotes" hidden>
				<label for="SHIPPER_NOTES" class="col-sm-2 control-label">#SHIPPER_NOTES#</label>
				<div class="col-sm-10">
					%SHIPPER_NOTES%
				</div>
			</div>
			<div class="form-group tighter" id="cnotes" hidden>
				<label for="CONS_NOTES" class="col-sm-2 control-label">#CONS_NOTES#</label>
				<div class="col-sm-10">
					%CONS_NOTES%
				</div>
			</div>
			<div class="form-group tighter" id="bnotes" hidden>
				<label for="BILLTO_NOTES" class="col-sm-2 control-label">#BILLTO_NOTES#</label>
				<div class="col-sm-10">
					%BILLTO_NOTES%
				</div>
			</div>
		</div>
		<div class="col-sm-4">
			<div class="panel panel-warning">
				<div class="panel-heading">
					<div class="form-group tighter">
						<div class="col-sm-8">
							<h3 class="panel-title">Insurance Requirements</h3>
						</div>
						<div class="col-sm-4">
							%INSURANCE_CURRENCY%
						</div>
					</div>
				</div>
				<div class="panel-body">
					<div class="form-group tighter">
						<label for="GENERAL_LIAB_INS" class="col-sm-4 control-label">#GENERAL_LIAB_INS#</label>
						<div class="col-sm-8">
							%GENERAL_LIAB_INS%
						</div>
					</div>
					<div class="form-group tighter">
						<label for="AUTO_LIAB_INS" class="col-sm-4 control-label">#AUTO_LIAB_INS#</label>
						<div class="col-sm-8">
							%AUTO_LIAB_INS%
						</div>
					</div>
					<div class="form-group tighter">
						<label for="CARGO_LIAB_INS" class="col-sm-4 control-label">#CARGO_LIAB_INS#</label>
						<div class="col-sm-8">
							%CARGO_LIAB_INS%
						</div>
					</div>
				</div>
			</div>
		</div>
	</div>
	
	'
);

$sts_form_addlead_form = array(	//! $sts_form_addlead_form
	'title' => '<span class="glyphicon glyphicon-user"></span> Add Lead',
	'action' => 'exp_addlead.php',
	//'actionextras' => 'disabled',
	'cancel' => 'exp_listclient.php',
	'name' => 'addlead',
	'okbutton' => 'Save Changes',
	'cancelbutton' => 'Cancel',	// well well-sm
		'layout' => '
	<div class="form-group">
		<div class="col-sm-5">
			<div class="form-group tighter">
				<label for="CLIENT_NAME" class="col-sm-4 control-label">#CLIENT_NAME#</label>
				<div class="col-sm-8">
					%CLIENT_NAME%
				</div>
			</div>
			<div class="form-group tighter">
				<label for="ADDRESS" class="col-sm-4 control-label">#ADDRESS#</label>
				<div class="col-sm-8">
					%ADDRESS%
				</div>
			</div>
			<div class="form-group tighter">
				<label for="ADDRESS2" class="col-sm-4 control-label">#ADDRESS2#</label>
				<div class="col-sm-8">
					%ADDRESS2%
				</div>
			</div>
			<div class="form-group tighter">
				<label for="CITY" class="col-sm-4 control-label">#CITY#</label>
				<div class="col-sm-8">
					%CITY%
				</div>
			</div>
			<div class="form-group tighter">
				<label for="STATE" class="col-sm-4 control-label">#STATE#</label>
				<div class="col-sm-8">
					%STATE%
				</div>
			</div>
			<div class="form-group tighter">
				<label for="ZIP_CODE" class="col-sm-4 control-label">#ZIP_CODE#</label>
				<div class="col-sm-8">
					%ZIP_CODE%
				</div>
			</div>
			<div class="form-group tighter">
				<label for="COUNTRY" class="col-sm-4 control-label">#COUNTRY#</label>
				<div class="col-sm-8">
					<select class="form-control" name="COUNTRY" id="COUNTRY" >
						<option value="USA" >USA</option>
						<option value="Canada" >Canada</option>
					</select>
				</div>
			</div>
		</div>
		<div class="col-sm-5">
			<div class="form-group tighter">
				<label for="CONTACT_NAME" class="col-sm-4 control-label">#CONTACT_NAME#</label>
				<div class="col-sm-8">
					%CONTACT_NAME%
				</div>
			</div>
			<div class="form-group tighter">
				<label for="PHONE_OFFICE" class="col-sm-4 control-label">#PHONE_OFFICE#</label>
				<div class="col-sm-5">
					%PHONE_OFFICE%
				</div>
				<div class="col-sm-3">
					%PHONE_EXT%
				</div>
			</div>
			<div class="form-group tighter">
				<label for="PHONE_CELL" class="col-sm-4 control-label">#PHONE_CELL#</label>
				<div class="col-sm-8">
					%PHONE_CELL%
				</div>
			</div>
			<div class="form-group tighter">
				<label for="EMAIL" class="col-sm-4 control-label">#EMAIL#</label>
				<div class="col-sm-8">
					%EMAIL%
				</div>
			</div>
			<div class="form-group tighter">
				<label for="LEAD_SOURCE_CODE" class="col-sm-4 control-label">#LEAD_SOURCE_CODE#</label>
				<div class="col-sm-8">
					%LEAD_SOURCE_CODE%
				</div>
			</div>
			<div class="form-group tighter">
				<label for="ASSIGN_ME" class="col-sm-4 control-label">#ASSIGN_ME#</label>
				<div class="col-sm-8">
					%ASSIGN_ME%
				</div>
			</div>
			<br>
			<div class="col-sm-11 col-sm-offset-1">
				<div class="alert alert-info">
					<p><strong><span class="glyphicon glyphicon-info-sign"></span> Tip:</strong> enter the zipcode/postal code first and it will fill in the city, state & country.</p>
					<p>Complete as much as possible, including the lead source</p>
				</div>
			</div>
		</div>
		<div class="col-sm-2">
			<img src="images/leads.jpg" alt="leads" class="img-responsive">
		</div>
	</div>
	<div class="form-group">
		<div class="col-sm-12">
			<div class="form-group tighter">
				<label for="NOTE" class="col-sm-2 control-label">#NOTE#</label>
				<div class="col-sm-10">
					%NOTE%
				</div>
			</div>
		</div>
	</div>
	
	'
);

$sts_form_editlead_form = array( //! $sts_form_editlead_form
	'title' => '<span class="glyphicon glyphicon-user"></span> Edit Lead',
	'action' => 'exp_editclient.php',
	//'actionextras' => 'disabled',
	'cancel' => 'exp_listclient.php',
	'name' => 'editclient',
	'okbutton' => 'Save',
	'saveadd' => 'Add Another',
	'cancelbutton' => 'Back',
		'layout' => '
		%CLIENT_CODE%
		%CLIENT_TYPE%
	<div class="form-group">
		<div class="col-sm-5">
			<div class="form-group tighter">
				<label for="CLIENT_NAME" class="col-sm-4 control-label">#CLIENT_NAME#</label>
				<div class="col-sm-8">
					%CLIENT_NAME%
				</div>
			</div>
			<div class="form-group tighter">
				<label for="LEAD_SOURCE_CODE" class="col-sm-4 control-label">#LEAD_SOURCE_CODE#</label>
				<div class="col-sm-8">
					%LEAD_SOURCE_CODE%
				</div>
			</div>
			<div class="form-group tighter">
				<label for="CURRENT_STATUS" class="col-sm-4 control-label">#CURRENT_STATUS#</label>
				<div class="col-sm-8">
					%CURRENT_STATUS%
				</div>
			</div>
		</div>
		<div class="col-sm-5">
			<div class="form-group tighter">
				<label for="CONTACT" class="col-sm-4 control-label">#CONTACT#</label>
				<div class="col-sm-8">
					%CONTACT%
				</div>
			</div>
			<div class="form-group tighter">
				<label for="SALES_PERSON" class="col-sm-4 control-label">#SALES_PERSON#</label>
				<div class="col-sm-8">
					%SALES_PERSON%
				</div>
			</div>
		</div>
	</div>
	<div class="form-group">
		<div class="col-sm-10">
			<div class="form-group">
				<label for="CLIENT_NOTES" class="col-sm-2 control-label">#CLIENT_NOTES#</label>
				<div class="col-sm-10">
					%CLIENT_NOTES%
				</div>
			</div>
		</div>
	</div>
	
	'
);

$sts_form_editprospect_form = array( //! $sts_form_editprospect_form
	'title' => '<span class="glyphicon glyphicon-user"></span> Edit Prospect',
	'action' => 'exp_editclient.php',
	//'actionextras' => 'disabled',
	'cancel' => 'exp_listclient.php',
	'name' => 'editclient',
	'okbutton' => 'Save',
	'saveadd' => 'Add Another',
	'cancelbutton' => 'Back',
		'layout' => '
		%CLIENT_CODE%
		%CLIENT_TYPE%
	<div class="form-group">
		<div class="col-sm-5">
			<div class="form-group tighter">
				<label for="CLIENT_NAME" class="col-sm-4 control-label">#CLIENT_NAME#</label>
				<div class="col-sm-8">
					%CLIENT_NAME%
				</div>
			</div>
			<div class="form-group tighter">
				<label for="LEAD_SOURCE_CODE" class="col-sm-4 control-label">#LEAD_SOURCE_CODE#</label>
				<div class="col-sm-8">
					%LEAD_SOURCE_CODE%
				</div>
			</div>
			<div class="form-group tighter">
				<label for="CURRENT_STATUS" class="col-sm-4 control-label">#CURRENT_STATUS#</label>
				<div class="col-sm-8">
					%CURRENT_STATUS%
				</div>
			</div>
		</div>
		<div class="col-sm-5">
			<div class="form-group tighter">
				<label for="CONTACT" class="col-sm-4 control-label">#CONTACT#</label>
				<div class="col-sm-8">
					%CONTACT%
				</div>
			</div>
			<div class="form-group tighter">
				<label for="SALES_PERSON" class="col-sm-4 control-label">#SALES_PERSON#</label>
				<div class="col-sm-8">
					%SALES_PERSON%
				</div>
			</div>
			<div class="form-group tighter">
				<label for="BILL_TO" class="col-sm-4 control-label">#BILL_TO#</label>
				<div class="col-sm-2">
					%BILL_TO%
				</div>
			</div>
		</div>
	</div>
	<div class="form-group">
		<div class="col-sm-10">
			<div class="form-group">
				<label for="CLIENT_NOTES" class="col-sm-2 control-label">#CLIENT_NOTES#</label>
				<div class="col-sm-10">
					%CLIENT_NOTES%
				</div>
			</div>
		</div>
	</div>
	
	'
);

$sts_form_editclient_form = array( //! $sts_form_editclient_form
	'title' => '<span class="glyphicon glyphicon-user"></span> Edit Client',
	'action' => 'exp_editclient.php',
	//'actionextras' => 'disabled',
	'cancel' => 'exp_listclient.php',
	'name' => 'editclient',
	'okbutton' => 'Save Changes',
	'saveadd' => 'Add Another',
	'cancelbutton' => 'Back',
		'layout' => '
		%CLIENT_CODE%
		%CLIENT_TYPE%
	<div class="form-group">
		<div class="col-sm-4">
			<div class="form-group tighter">
				<label for="CLIENT_NAME" class="col-sm-4 control-label">#CLIENT_NAME#</label>
				<div class="col-sm-8">
					%CLIENT_NAME%
				</div>
			</div>
			<!-- CMS01 -->
			<div class="form-group tighter">
				<label for="LEAD_SOURCE_CODE" class="col-sm-4 control-label">#LEAD_SOURCE_CODE#</label>
				<div class="col-sm-8">
					%LEAD_SOURCE_CODE%
				</div>
			</div>
			<div class="form-group tighter">
				<label for="CURRENT_STATUS" class="col-sm-4 control-label">#CURRENT_STATUS#</label>
				<div class="col-sm-8">
					%CURRENT_STATUS%
				</div>
			</div>
			<!-- CMS02 -->
			<div class="form-group tighter">
				%CLIENT_GROUP_CODE%
				<label for="CLIENT_GROUP" class="col-sm-4 control-label">#CLIENT_GROUP#</label>
				<div class="col-sm-8">
					%CLIENT_GROUP%
				</div>
			</div>
			<!-- CLIENT_ID_1 -->
			<div class="form-group tighter">
				<label for="CLIENT_ID" class="col-sm-4 control-label">#CLIENT_ID#</label>
				<div class="col-sm-8">
					%CLIENT_ID%
				</div>
			</div>
			<!-- CLIENT_ID_2 -->
			<!-- SAGE50_1 -->
			<div class="form-group tighter">
				<label for="SAGE50_CLIENTID" class="col-sm-4 control-label">#SAGE50_CLIENTID#</label>
				<div class="col-sm-8">
					%SAGE50_CLIENTID%
				</div>
			</div>
			<!-- SAGE50_2 -->
			<div class="form-group tighter">
				<label for="CURRENCY_CODE" class="col-sm-4 control-label">#CURRENCY_CODE#</label>
				<div class="col-sm-8">
					%CURRENCY_CODE%
				</div>
			</div>
			<div class="form-group tighter">
				<label for="DEFAULT_ZONE" class="col-sm-4 control-label">#DEFAULT_ZONE#</label>
				<div class="col-sm-8">
					%DEFAULT_ZONE%
				</div>
			</div>
			<div class="form-group tighter">
				<label for="TERMINAL_ZONE" class="col-sm-4 control-label">#TERMINAL_ZONE#</label>
				<div class="col-sm-8">
					%TERMINAL_ZONE%
				</div>
			</div>
			<div class="form-group tighter">
				<label for="OPEN_TIME" class="col-sm-4 control-label">#OPEN_TIME#</label>
				<div class="col-sm-8">
					%OPEN_TIME%
				</div>
			</div>
			<div class="form-group tighter">
				<label for="CLOSE_TIME" class="col-sm-4 control-label">#CLOSE_TIME#</label>
				<div class="col-sm-8">
					%CLOSE_TIME%
				</div>
			</div>
			<div class="form-group tighter">
				<label for="CUSTOMS_BROKER" class="col-sm-4 control-label">#CUSTOMS_BROKER#</label>
				<div class="col-sm-8">
					%CUSTOMS_BROKER%
				</div>
			</div>
			<div class="form-group tighter">
				<label for="DEFAULT_COMMODITY" class="col-sm-4 control-label">#DEFAULT_COMMODITY#</label>
				<div class="col-sm-8">
					%DEFAULT_COMMODITY%
				</div>
			</div>
			<div class="form-group tighter">
				<label for="DEFAULT_EQUIPMENT" class="col-sm-4 control-label">#DEFAULT_EQUIPMENT#</label>
				<div class="col-sm-8">
					%DEFAULT_EQUIPMENT%
				</div>
			</div>
			<div class="form-group tighter">
				<label for="BUSINESS_CODE" class="col-sm-4 control-label">#BUSINESS_CODE#</label>
				<div class="col-sm-8">
					%BUSINESS_CODE%
				</div>
			</div>
			<div class="well well-sm tighter">
				<div class="form-group tighter">
					<div class="col-sm-6">
						<a href="https://en.wikipedia.org/wiki/Customs-Trade_Partnership_Against_Terrorism" target="_blank" class="tip" title="Customs-Trade Partnership Against Terrorism"><img src="images/logo-ctpat.jpg" alt="logo-ctpat" width="100%" /></a>
					</div>
					<div class="col-sm-6">
						%CTPAT_REQUIRED%<br>
						#CTPAT_REQUIRED#
					</div>
				</div>
			</div>
			<!-- NOTIFY_1 -->
			<div class="panel panel-info">
				<div class="panel-heading">
					<h3 class="panel-title"><span class="glyphicon glyphicon-envelope"></span> #EMAIL_NOTIFY#</h3>
				</div>
				<div class="panel-body">
					<div class="form-group tighter">
						<div class="col-sm-12">
							%EMAIL_NOTIFY%
						</div>
					</div>
					<p class="text-info">Put an email address here to receive notifications of picks and drops. Leave blank for no notification.</p>

				<label for="NOTIFY_ARRIVE_SHIPPER" class="col-sm-4 control-label">#NOTIFY_ARRIVE_SHIPPER#</label>
				<div class="col-sm-2">
					%NOTIFY_ARRIVE_SHIPPER%
				</div>
				<label for="NOTIFY_DEPART_SHIPPER" class="col-sm-4 control-label">#NOTIFY_DEPART_SHIPPER#</label>
				<div class="col-sm-2">
					%NOTIFY_DEPART_SHIPPER%
				</div>
				<label for="NOTIFY_ARRIVE_CONS" class="col-sm-4 control-label">#NOTIFY_ARRIVE_CONS#</label>
				<div class="col-sm-2">
					%NOTIFY_ARRIVE_CONS%
				</div>
				<label for="NOTIFY_DEPART_CONS" class="col-sm-4 control-label">#NOTIFY_DEPART_CONS#</label>
				<div class="col-sm-2">
					%NOTIFY_DEPART_CONS%
				</div>
			<div class="form-group tighter">
				<label for="NOTIFY_DATE_FMT" class="col-sm-4 control-label">#NOTIFY_DATE_FMT#</label>
				<div class="col-sm-8">
					%NOTIFY_DATE_FMT%
				</div>
			</div>

				</div>
			</div>
			<!-- NOTIFY_2 -->
		</div>
		<div class="col-sm-4">
			<div class="form-group tighter">
				<label for="MILEAGE_TYPE" class="col-sm-4 control-label">#MILEAGE_TYPE#</label>
				<div class="col-sm-8">
					%MILEAGE_TYPE%
				</div>
			</div>
			<div class="form-group well well-sm">
				<label for="SHIPPER" class="col-sm-4 control-label">#SHIPPER#</label>
				<div class="col-sm-2">
					%SHIPPER%
				</div>
				<label for="CONSIGNEE" class="col-sm-4 control-label">#CONSIGNEE#</label>
				<div class="col-sm-2">
					%CONSIGNEE%
				</div>
				<label for="DOCK" class="col-sm-4 control-label">#DOCK#</label>
				<div class="col-sm-2">
					%DOCK%
				</div>
				<label for="BILL_TO" class="col-sm-4 control-label">#BILL_TO#</label>
				<div class="col-sm-2">
					%BILL_TO%
				</div>
				<label for="POD_NEEDED" class="col-sm-4 control-label">#POD_NEEDED#</label>
				<div class="col-sm-2">
					%POD_NEEDED%
				</div>
				<label for="APPOINTMENT_NEEDED" class="col-sm-4 control-label">#APPOINTMENT_NEEDED#</label>
				<div class="col-sm-2">
					%APPOINTMENT_NEEDED%
				</div>
				<!-- INTERMODAL1 -->
				<label for="INTERMODAL" class="col-sm-4 control-label">#INTERMODAL#</label>
				<div class="col-sm-2">
					%INTERMODAL%
				</div>
				<!-- INTERMODAL2 -->
			</div>
			<div id="CLIENT_WARNINGS">
			</div>


			<div class="form-group tighter">
				<label for="CONTACT" class="col-sm-4 control-label">#CONTACT#</label>
				<div class="col-sm-8">
					%CONTACT%
				</div>
			</div>
			<div class="form-group tighter">
				<label for="SALES_PERSON" class="col-sm-4 control-label">#SALES_PERSON#</label>
				<div class="col-sm-8">
					%SALES_PERSON%
				</div>
			</div>
			<div class="form-group tighter">
				<label for="BILLING_CLIENT" class="col-sm-4 control-label">#BILLING_CLIENT#</label>
				<div class="col-sm-8">
					%BILLING_CLIENT%
				</div>
			</div>
			<div class="form-group tighter">
				<label for="API" class="col-sm-4 control-label">#API#</label>
				<div class="col-sm-8">
					%API%
				</div>
			</div>
			<div class="form-group tighter">
				<label for="CODE_TYPE" class="col-sm-4 control-label">#CODE_TYPE#</label>
				<div class="col-sm-8">
					%CODE_TYPE%
				</div>
			</div>
			<div class="form-group tighter">
				<label for="CLIENT_URL" class="col-sm-4 control-label">#CLIENT_URL#</label>
				<div class="col-sm-8">
					%CLIENT_URL%
				</div>
			</div>
			<!-- EQUIPMENT -->
		</div>
		<div class="col-sm-4">
			<div class="form-group  well well-sm">
			<div class="form-group tighter">
				<label for="ON_CREDIT_HOLD" class="col-sm-4 control-label">#ON_CREDIT_HOLD#</label>
				<div class="col-sm-8">
					%ON_CREDIT_HOLD%
				</div>
			</div>
			<div class="form-group tighter">
				<label for="CREDIT_STATUS" class="col-sm-4 control-label">#CREDIT_STATUS#</label>
				<div class="col-sm-8">
					%CREDIT_STATUS%
				</div>
			</div>
			<div class="form-group tighter">
				<label for="CREDIT_LIMIT" class="col-sm-4 control-label">#CREDIT_LIMIT#</label>
				<div class="col-sm-8">
					%CREDIT_LIMIT%
				</div>
			</div>
			<div class="form-group tighter">
				<label for="TERMS" class="col-sm-4 control-label">#TERMS#</label>
				<div class="col-sm-8">
					%TERMS%
				</div>
			</div>
			<div class="form-group tighter">
				<label for="COD_MINIMUM" class="col-sm-4 control-label">#COD_MINIMUM#</label>
				<div class="col-sm-8">
					%COD_MINIMUM%
				</div>
			</div>
			<div class="form-group tighter">
				<label for="COD_MAXIMUM" class="col-sm-4 control-label">#COD_MAXIMUM#</label>
				<div class="col-sm-8">
					%COD_MAXIMUM%
				</div>
			</div>
			</div>
			<div class="form-group tighter">
				<label for="UOM" class="col-sm-4 control-label">#UOM#</label>
				<div class="col-sm-8">
					%UOM%
				</div>
			</div>
			<div class="form-group tighter">
				<label for="DUNS_CODE" class="col-sm-4 control-label">#DUNS_CODE#</label>
				<div class="col-sm-8">
					%DUNS_CODE%
				</div>
			</div>
			<!-- EXTRA_1 -->
			<div class="well well-sm tighter">
				<div class="form-group tighter">
					<label for="CLIENT_EXTRA_STOPS" class="col-sm-6 control-label">#CLIENT_EXTRA_STOPS#</label>
					<div class="col-sm-6">
						%CLIENT_EXTRA_STOPS%
					</div>
				</div>
			</div>
			<!-- EXTRA_2 -->
			<div class="bg-info tighter">
				<div class="form-group tighter">
					<label for="BATCH_EMAIL" class="col-sm-6 control-label">#BATCH_EMAIL#</label>
					<div class="col-sm-6">
						%BATCH_EMAIL%
					</div>
				</div>
				<p>This is for sending invoices to Bill-to clients only, and you need an email in the bill_to contact info</p>
			</div>
			<div class="well well-sm tighter">
				<div class="form-group tighter">
					<label for="TRACK_PALLETS" class="col-sm-6 control-label">#TRACK_PALLETS#</label>
					<div class="col-sm-6">
						%TRACK_PALLETS%
					</div>
				</div>
				<div class="form-group tighter">
					<label for="PALLET_COUNT" class="col-sm-6 control-label">#PALLET_COUNT#</label>
					<div class="col-sm-6">
						%PALLET_COUNT%
					</div>
				</div>
			</div>
			<div class="panel panel-danger" id="CDN_TAX">
				<div class="panel-heading">
					<h3 class="panel-title"><img src="images/flag-ca.png" alt="flag-ca" width="48" height="24" /> Tax Exemption (<a href="http://www.cra-arc.gc.ca/tx/bsnss/tpcs/gst-tps/thr/sctrs/frghtcrrrs-eng.html#appfrtrcarr" target="_blank">info <span class="glyphicon glyphicon-link"></span></a>)</h3>
				</div>
				<div class="panel-body">
					<div class="form-group tighter">
						<label for="CDN_TAX_EXEMPT" class="col-sm-4 control-label">#CDN_TAX_EXEMPT#</label>
						<div class="col-sm-8">
							%CDN_TAX_EXEMPT%
						</div>
					</div>
					<div class="form-group tighter" id="EXEMPT_REASON" hidden>
						<label for="CDN_TAX_EXEMPT_REASON" class="col-sm-4 control-label">#CDN_TAX_EXEMPT_REASON#</label>
						<div class="col-sm-8">
							%CDN_TAX_EXEMPT_REASON%
						</div>
					</div>
				</div>
			</div>
		</div>
	</div>
	<div class="form-group">
		<div class="col-sm-8 well well-sm">
			<div class="form-group tighter">
				<label for="CLIENT_NOTES" class="col-sm-2 control-label">#CLIENT_NOTES#</label>
				<div class="col-sm-10">
					%CLIENT_NOTES%
				</div>
			</div>
			<div class="form-group tighter" id="snotes" hidden>
				<label for="SHIPPER_NOTES" class="col-sm-2 control-label">#SHIPPER_NOTES#</label>
				<div class="col-sm-10">
					%SHIPPER_NOTES%
				</div>
			</div>
			<div class="form-group tighter" id="cnotes" hidden>
				<label for="CONS_NOTES" class="col-sm-2 control-label">#CONS_NOTES#</label>
				<div class="col-sm-10">
					%CONS_NOTES%
				</div>
			</div>
			<div class="form-group tighter" id="bnotes" hidden>
				<label for="BILLTO_NOTES" class="col-sm-2 control-label">#BILLTO_NOTES#</label>
				<div class="col-sm-10">
					%BILLTO_NOTES%
				</div>
			</div>
		</div>
		<div class="col-sm-4">
			<div class="panel panel-warning">
				<div class="panel-heading">
					<div class="form-group tighter">
						<div class="col-sm-8">
							<h3 class="panel-title">Insurance Requirements</h3>
						</div>
						<div class="col-sm-4">
							%INSURANCE_CURRENCY%
						</div>
					</div>
				</div>
				<div class="panel-body">
					<div class="form-group tighter">
						<label for="GENERAL_LIAB_INS" class="col-sm-4 control-label">#GENERAL_LIAB_INS#</label>
						<div class="col-sm-8">
							%GENERAL_LIAB_INS%
						</div>
					</div>
					<div class="form-group tighter">
						<label for="AUTO_LIAB_INS" class="col-sm-4 control-label">#AUTO_LIAB_INS#</label>
						<div class="col-sm-8">
							%AUTO_LIAB_INS%
						</div>
					</div>
					<div class="form-group tighter">
						<label for="CARGO_LIAB_INS" class="col-sm-4 control-label">#CARGO_LIAB_INS#</label>
						<div class="col-sm-8">
							%CARGO_LIAB_INS%
						</div>
					</div>
				</div>
			</div>
		</div>
	</div>
	
	'
);

//! Field Specifications - For use with sts_form

$sts_form_add_client_fields = array( //! $sts_form_add_client_fields
	'CLIENT_NAME' => array( 'label' => 'Name', 'format' => 'text', 'extras' => 'required autofocus' ),
	'LEAD_SOURCE_CODE' => array( 'label' => 'Lead source', 'format' => 'table',
		'table' => ITEM_LIST_TABLE, 'key' => 'ITEM_CODE', 'fields' => 'ITEM',
		'condition' => 'ITEM_TYPE = \'Lead source\'' ),
	'CLIENT_URL' => array( 'label' => 'URL', 'format' => 'text' ),
	'SAGE50_CLIENTID' => array( 'label' => 'Sage ID', 'format' => 'text' ),
	'CLIENT_ID' => array( 'label' => 'Client ID', 'format' => 'text' ),
	'CURRENCY_CODE' => array( 'label' => 'Currency', 'format' => 'enum' ),
	'INSURANCE_CURRENCY' => array( 'label' => 'InsCurrency', 'format' => 'enum' ),
	'DEFAULT_ZONE' => array( 'label' => 'Default Zone', 'format' => 'zip' ),
	'TERMINAL_ZONE' => array( 'label' => 'Terminal Zone', 'format' => 'zip' ),
	'OPEN_TIME' => array( 'label' => 'Open', 'placeholder' => 'HH:MM AM', 'format' => 'text' ),
	'CLOSE_TIME' => array( 'label' => 'Close', 'placeholder' => 'HH:MM PM', 'format' => 'text' ),
	'COMMENTS' => array( 'label' => 'Comments', 'format' => 'textarea' ),
	'CUSTOMS_BROKER' => array( 'label' => 'Broker', 'format' => 'text' ),
	'DEFAULT_COMMODITY' => array( 'label' => 'Commodity', 'format' => 'table',
		'table' => COMMODITY_TABLE, 'key' => 'COMMODITY_CODE', 'fields' => 'COMMODITY_NAME' ),
	'CONTACT' => array( 'label' => 'Main Contact', 'format' => 'text' ),
	'BILLING_CLIENT' => array( 'label' => 'Billing Client', 'format' => 'text' ),
	'ON_CREDIT_HOLD' => array( 'label' => 'Hold', 'align' => 'center', 'format' => 'bool2' ),
	'CLIENT_EXTRA_STOPS' => array( 'label' => 'Extra Stops', 'align' => 'center', 'format' => 'bool' ),
	'API' => array( 'label' => 'API', 'format' => 'text' ),
	//'PORTAL_CODE' => array( 'label' => 'Portal Code', 'format' => 'text' ),
	//'PORTAL_PASSWORD' => array( 'label' => 'Portal PW', 'format' => 'password' ),

	'CREDIT_LIMIT' => array( 'label' => 'Credit Limit', 'format' => 'numberc', 'align' => 'right' ),
	'CODE_TYPE' => array( 'label' => 'Code Type', 'format' => 'text' ),
	'COD_MINIMUM' => array( 'label' => 'COD Min', 'format' => 'number' ),
	'COD_MAXIMUM' => array( 'label' => 'COD Max', 'format' => 'number' ),
	'SALES_PERSON' => array( 'label' => 'Sales Person', 'format' => 'table',
		'table' => USER_TABLE, 'key' => 'USER_CODE', 'fields' => 'USERNAME',
		'condition' => 'USER_GROUPS like \'%'.EXT_GROUP_SALES.'%\' AND ISACTIVE != \'Inactive\'' ),
	'MILEAGE_TYPE' => array( 'label' => 'Mileage Type', 'format' => 'text' ),
	'SHIPPER' => array( 'label' => 'Shipper', 'align' => 'center', 'format' => 'bool2' ),
	'CONSIGNEE' => array( 'label' => 'Consignee', 'align' => 'center', 'format' => 'bool2' ),
	'DOCK' => array( 'label' => 'Dock', 'align' => 'center', 'format' => 'bool2' ),
	'BILL_TO' => array( 'label' => 'Bill-to', 'align' => 'center', 'format' => 'bool2' ),
	'POD_NEEDED' => array( 'label' => 'POD Needed', 'align' => 'center', 'format' => 'bool2' ),
	'CDN_TAX_EXEMPT' => array( 'label' => 'Exempt', 'align' => 'center', 'format' => 'bool' ),
	'CDN_TAX_EXEMPT_REASON' => array( 'label' => 'Reason', 'format' => 'text' ),

	'CLIENT_GROUP' => array( 'label' => 'Client Group', 'format' => 'client' ),
	'CLIENT_GROUP_CODE' => array( 'label' => 'Client Group#', 'format' => 'hidden-req' ),
	'UOM' => array( 'label' => 'UOM', 'format' => 'text' ),
	'DEFAULT_EQUIPMENT' => array( 'label' => 'Default Equipment', 'format' => 'enum' ),
	'APPOINTMENT_NEEDED' => array( 'label' => 'Appt Needed', 'format' => 'bool2' ),
	'INTERMODAL' => array( 'label' => 'Intermodal', 'format' => 'bool2' ),
	'DUNS_CODE' => array( 'label' => 'DUNS', 'format' => 'text' ),
	'CREDIT_STATUS' => array( 'label' => 'Credit Status', 'format' => 'text' ),
	'TRACK_PALLETS' => array( 'label' => 'Track Pallets', 'align' => 'center', 'format' => 'bool',
		'value' => true ),
	'PALLET_COUNT' => array( 'label' => '#Pallets', 'format' => 'number' ),
	'CLIENT_NOTES' => array( 'label' => 'Client Notes', 'format' => 'textarea', 'extras' => 'rows="6"' ),
	'SHIPPER_NOTES' => array( 'label' => 'Shipper Notes', 'format' => 'textarea', 'extras' => 'rows="4"', 'placeholder' => 'Default shipment notes when this client is shipper.'   ),
	'CONS_NOTES' => array( 'label' => 'Consignee Notes', 'format' => 'textarea', 'extras' => 'rows="4"', 'placeholder' => 'Default shipment notes when this client is consignee.' ),
	'BILLTO_NOTES' => array( 'label' => 'Bill-to Notes', 'format' => 'textarea', 'extras' => 'rows="4"', 'placeholder' => 'Default shipment notes when this client is bill-to.' ),
	'GENERAL_LIAB_INS' => array( 'label' => 'General', 'format' => 'numberc', 'align' => 'right' ),
	'AUTO_LIAB_INS' => array( 'label' => 'Auto', 'format' => 'numberc', 'align' => 'right' ),
	'CARGO_LIAB_INS' => array( 'label' => 'Cargo', 'format' => 'numberc', 'align' => 'right' ),
	'TERMS' => array( 'label' => 'Default Terms', 'format' => 'table',
		'table' => ITEM_LIST_TABLE, 'key' => 'ITEM_CODE', 'fields' => 'ITEM',
		'condition' => 'ITEM_TYPE = \'Client Terms\'' ),
	'BUSINESS_CODE' => array( 'label' => 'Business Code', 'format' => 'table',
		'table' => BUSINESS_CODE_TABLE, 'key' => 'BUSINESS_CODE',
		'condition' => "APPLIES_TO = 'shipment'", 'fields' => 'BC_NAME' ),
	'CTPAT_REQUIRED' => array( 'label' => 'C-TPAT Required', 'format' => 'bool' ),
	'BATCH_EMAIL' => array( 'label' => 'Batch E-mail', 'align' => 'center', 'format' => 'bool' ),
	'EMAIL_NOTIFY' => array( 'label' => 'Email Notify', 'format' => 'email', 'extras' => 'multiple' ),
	'NOTIFY_ARRIVE_SHIPPER' => array( 'label' => 'Arrive Shipper', 'align' => 'center', 'format' => 'bool2' ),
	'NOTIFY_DEPART_SHIPPER' => array( 'label' => 'Depart Shipper', 'align' => 'center', 'format' => 'bool2' ),
	'NOTIFY_ARRIVE_CONS' => array( 'label' => 'Arrive Cons', 'align' => 'center', 'format' => 'bool2' ),
	'NOTIFY_DEPART_CONS' => array( 'label' => 'Depart Cons', 'align' => 'center', 'format' => 'bool2' ),
	'NOTIFY_DATE_FMT' => array( 'label' => 'Date Format', 'format' => 'enum' ),
);

//! SCR# 441 - add some length restrictions
$sts_form_add_lead_fields = array( //! $sts_form_add_lead_fields
	'CLIENT_NAME' => array( 'label' => 'Company Name', 'format' => 'text', 'extras' => 'required autofocus' ),
	'LEAD_SOURCE_CODE' => array( 'label' => 'Lead source', 'format' => 'table',
		'table' => ITEM_LIST_TABLE, 'key' => 'ITEM_CODE', 'fields' => 'ITEM',
		'condition' => 'ITEM_TYPE = \'Lead source\'' ),

	'CONTACT_NAME' => array( 'label' => 'Contact Name', 'format' => 'text', 'length' => '128' ),
	'ADDRESS' => array( 'label' => 'Addr', 'format' => 'text', 'length' => '50', 'extras' => 'required' ),
	'ADDRESS2' => array( 'label' => 'Addr2', 'format' => 'text', 'length' => '50' ),
	'CITY' => array( 'label' => 'City', 'format' => 'text', 'length' => '40' ),
	'STATE' => array( 'label' => 'State', 'format' => 'state' ),
	'ZIP_CODE' => array( 'label' => 'Zip', 'format' => 'zip', 'length' => '11', 'extras' => 'required' ),
	'COUNTRY' => array( 'label' => 'Country', 'format' => 'text' ),
	'PHONE_OFFICE' => array( 'label' => 'Phone', 'format' => 'text', 'length' => '32' ),
	'PHONE_EXT' => array( 'label' => 'Ext', 'format' => 'text', 'length' => '5' ),
	'PHONE_CELL' => array( 'label' => 'Cell phone', 'format' => 'text', 'length' => '32' ),
	'EMAIL' => array( 'label' => 'Email', 'format' => 'email', 'length' => '128' ),
	'NOTE' => array( 'label' => 'Notes', 'format' => 'textarea', 'extras' => 'rows="4"' ),
	'ASSIGN_ME' => array( 'label' => 'Assign to me', 'align' => 'center', 'format' => 'bool2' ),

);

$sts_form_edit_client_fields = array( //! $sts_form_edit_client_fields
	'CLIENT_CODE' => array( 'format' => 'hidden' ),	// primary key must be first
	'CLIENT_TYPE' => array( 'format' => 'hidden' ),
	'CLIENT_NAME' => array( 'label' => 'Name', 'format' => 'text', 'extras' => 'required autofocus' ),
	'LEAD_SOURCE_CODE' => array( 'label' => 'Lead source', 'format' => 'table',
		'table' => ITEM_LIST_TABLE, 'key' => 'ITEM_CODE', 'fields' => 'ITEM',
		'condition' => 'ITEM_TYPE = \'Lead source\'' ),
	'CURRENT_STATUS' => array( 'label' => 'Status', 'format' => 'table',
		'table' => STATUS_CODES_TABLE, 'key' => 'STATUS_CODES_CODE', 'fields' => 'STATUS_STATE',
		'condition' => 'SOURCE_TYPE = \'client\'', 'extras' => 'readonly', 'static' => true ),
	'SAGE50_CLIENTID' => array( 'label' => 'Sage ID', 'format' => 'text' ),
	'CLIENT_ID' => array( 'label' => 'Client ID', 'format' => 'text' ),
	'CLIENT_URL' => array( 'label' => 'URL', 'format' => 'text' ),
	'CURRENCY_CODE' => array( 'label' => 'Currency', 'format' => 'enum' ),
	'INSURANCE_CURRENCY' => array( 'label' => 'InsCurrency', 'format' => 'enum' ),
	'DEFAULT_ZONE' => array( 'label' => 'Default Zone', 'format' => 'zip' ),
	'TERMINAL_ZONE' => array( 'label' => 'Terminal Zone', 'format' => 'zip' ),
	'OPEN_TIME' => array( 'label' => 'Open', 'format' => 'text' ),
	'CLOSE_TIME' => array( 'label' => 'Close', 'format' => 'text' ),
	'COMMENTS' => array( 'label' => 'Comments', 'format' => 'textarea' ),
	'CUSTOMS_BROKER' => array( 'label' => 'Broker', 'format' => 'text' ),
	'DEFAULT_COMMODITY' => array( 'label' => 'Commodity', 'format' => 'table',
		'table' => COMMODITY_TABLE, 'key' => 'COMMODITY_CODE', 'fields' => 'COMMODITY_NAME' ),
	'CONTACT' => array( 'label' => 'Main Contact', 'format' => 'text' ),
	'BILLING_CLIENT' => array( 'label' => 'Billing Client', 'format' => 'text' ),
	'ON_CREDIT_HOLD' => array( 'label' => 'Hold', 'align' => 'center', 'format' => 'bool2' ),
	'CLIENT_EXTRA_STOPS' => array( 'label' => 'Extra Stops', 'align' => 'center', 'format' => 'bool' ),
	'API' => array( 'label' => 'API', 'format' => 'text' ),
	//'PORTAL_CODE' => array( 'label' => 'Portal Code', 'format' => 'text' ),
	//'PORTAL_PASSWORD' => array( 'label' => 'Portal PW', 'format' => 'text', 'extras' => 'autocomplete="off"' ),

	'CREDIT_LIMIT' => array( 'label' => 'Credit Limit', 'format' => 'numberc', 'align' => 'right' ),
	'CODE_TYPE' => array( 'label' => 'Code Type', 'format' => 'text' ),
	'COD_MINIMUM' => array( 'label' => 'COD Min', 'format' => 'number' ),
	'COD_MAXIMUM' => array( 'label' => 'COD Max', 'format' => 'number' ),
	'SALES_PERSON' => array( 'label' => 'Sales Person', 'format' => 'table',
		'table' => USER_TABLE, 'key' => 'USER_CODE', 'fields' => 'USERNAME',
		'condition' => 'USER_GROUPS like \'%'.EXT_GROUP_SALES.'%\' AND ISACTIVE != \'Inactive\'', 'nolink' => true ),
	'MILEAGE_TYPE' => array( 'label' => 'Mileage Type', 'format' => 'text' ),
	'SHIPPER' => array( 'label' => 'Shipper', 'align' => 'center', 'format' => 'bool2' ),
	'CONSIGNEE' => array( 'label' => 'Consignee', 'align' => 'center', 'format' => 'bool2' ),
	'DOCK' => array( 'label' => 'Dock', 'align' => 'center', 'format' => 'bool2' ),
	'BILL_TO' => array( 'label' => 'Bill-to', 'align' => 'center', 'format' => 'bool2' ),
	'POD_NEEDED' => array( 'label' => 'POD Needed', 'align' => 'center', 'format' => 'bool2' ),
	'CDN_TAX_EXEMPT' => array( 'label' => 'Exempt', 'align' => 'center', 'format' => 'bool' ),
	'CDN_TAX_EXEMPT_REASON' => array( 'label' => 'Reason', 'format' => 'text' ),

	'CLIENT_GROUP' => array( 'label' => 'Client Group', 'format' => 'client' ),
	'CLIENT_GROUP_CODE' => array( 'label' => 'Client Group#', 'format' => 'hidden-req' ),
	'UOM' => array( 'label' => 'UOM', 'format' => 'text' ),
	'DEFAULT_EQUIPMENT' => array( 'label' => 'Default Equipment', 'format' => 'enum' ),
	'APPOINTMENT_NEEDED' => array( 'label' => 'Appt Needed', 'format' => 'bool2' ),
	'INTERMODAL' => array( 'label' => 'Intermodal', 'format' => 'bool2' ),
	'DUNS_CODE' => array( 'label' => 'DUNS', 'format' => 'text' ),
	'CREDIT_STATUS' => array( 'label' => 'Credit Status', 'format' => 'text' ),
	'TRACK_PALLETS' => array( 'label' => 'Track Pallets', 'align' => 'center', 'format' => 'bool',
		'value' => true ),
	'PALLET_COUNT' => array( 'label' => '#Pallets', 'format' => 'static', 
		'placeholder' => 'set', 'link' => 'exp_addpallet.php?client=%pk%&pc=' ),	
	'CLIENT_NOTES' => array( 'label' => 'Client Notes', 'format' => 'textarea', 'extras' => 'rows="6"' ),
	'SHIPPER_NOTES' => array( 'label' => 'Shipper Notes', 'format' => 'textarea', 'extras' => 'rows="4"', 'placeholder' => 'Default shipment notes when this client is shipper.'   ),
	'CONS_NOTES' => array( 'label' => 'Consignee Notes', 'format' => 'textarea', 'extras' => 'rows="4"', 'placeholder' => 'Default shipment notes when this client is consignee.' ),
	'BILLTO_NOTES' => array( 'label' => 'Bill-to Notes', 'format' => 'textarea', 'extras' => 'rows="4"', 'placeholder' => 'Default shipment notes when this client is bill-to.' ),
	'GENERAL_LIAB_INS' => array( 'label' => 'General', 'format' => 'numberc', 'align' => 'right' ),
	'AUTO_LIAB_INS' => array( 'label' => 'Auto', 'format' => 'numberc', 'align' => 'right' ),
	'CARGO_LIAB_INS' => array( 'label' => 'Cargo', 'format' => 'numberc', 'align' => 'right' ),
	'TERMS' => array( 'label' => 'Default Terms', 'format' => 'table',
		'table' => ITEM_LIST_TABLE, 'key' => 'ITEM_CODE', 'fields' => 'ITEM',
		'condition' => 'ITEM_TYPE = \'Client Terms\'' ),
	'BUSINESS_CODE' => array( 'label' => 'Business Code', 'format' => 'table',
		'table' => BUSINESS_CODE_TABLE, 'key' => 'BUSINESS_CODE',
		'condition' => "APPLIES_TO = 'shipment'", 'fields' => 'BC_NAME' ),
	'CTPAT_REQUIRED' => array( 'label' => 'C-TPAT Required', 'format' => 'bool' ),
	'BATCH_EMAIL' => array( 'label' => 'Batch E-mail', 'align' => 'center', 'format' => 'bool' ),
	'EMAIL_NOTIFY' => array( 'label' => 'Email Notify', 'format' => 'email', 'extras' => 'multiple' ),
	'NOTIFY_ARRIVE_SHIPPER' => array( 'label' => 'Arrive Shipper', 'align' => 'center', 'format' => 'bool2' ),
	'NOTIFY_DEPART_SHIPPER' => array( 'label' => 'Depart Shipper', 'align' => 'center', 'format' => 'bool2' ),
	'NOTIFY_ARRIVE_CONS' => array( 'label' => 'Arrive Cons', 'align' => 'center', 'format' => 'bool2' ),
	'NOTIFY_DEPART_CONS' => array( 'label' => 'Depart Cons', 'align' => 'center', 'format' => 'bool2' ),
	'NOTIFY_DATE_FMT' => array( 'label' => 'Date Format', 'format' => 'enum' ),
);

//! Layout Specifications - For use with sts_result

$sts_result_clients_layout = array( //! $sts_result_clients_layout
	'CLIENT_CODE' => array( 'format' => 'hidden' ),
	'CLIENT_NAME' => array( 'label' => 'Name', 'format' => 'text', 'searchable' => true ),
	'CLIENT_ID' => array( 'label' => 'Client ID', 'format' => 'text', 'searchable' => true ),
	'SAGE50_CLIENTID' => array( 'label' => 'SageID', 'format' => 'text', 'searchable' => true ),
	'ISDELETED' => array( 'label' => 'Del', 'align' => 'center', 'format' => 'hidden' ),
	'ADDRESS1' => array( 'label' => 'Address', 'format' => 'text', 'length' => 150, 'searchable' => true ),
	'PHONE1' => array( 'label' => 'Phone', 'format' => 'text', 'searchable' => true ),
	'EMAIL' => array( 'label' => 'E-mail', 'format' => 'text', 'searchable' => true ),
	'CLIENT_TYPE' => array( 'label' => 'Type', 'format' => 'text' ),
	'BROKERS' => array( 'label' => 'Broker', 'format' => 'text', 'searchable' => true ),
	'CURRENT_STATUS' => array( 'label' => 'Status', 'format' => 'table',
		'table' => STATUS_CODES_TABLE, 'key' => 'STATUS_CODES_CODE', 'fields' => 'STATUS_STATE',
		'condition' => 'SOURCE_TYPE = \'client\'', 'extras' => 'readonly', 'static' => true ),
	'CURRENCY_CODE' => array( 'label' => 'Curr', 'format' => 'text' ),
	'INSURANCE_CURRENCY' => array( 'label' => 'Ins', 'format' => 'text' ),
	'TERMS' => array( 'label' => 'Terms', 'format' => 'table',
		'table' => ITEM_LIST_TABLE, 'key' => 'ITEM_CODE', 'fields' => 'ITEM',
		'condition' => 'ITEM_TYPE = \'Client Terms\'' ),
	'SALES_PERSON' => array( 'label' => 'Sales', 'format' => 'table',
		'table' => USER_TABLE, 'key' => 'USER_CODE', 'fields' => 'USERNAME',
		'condition' => 'USER_GROUPS like \'%'.EXT_GROUP_SALES.'%\' AND ISACTIVE != \'Inactive\'',
		'searchable' => true ), //! SCR# 632 - make searchable
	'SHIPPER' => array( 'label' => 'Ship', 'align' => 'center', 'format' => 'bool' ),
	'CONSIGNEE' => array( 'label' => 'Cons', 'align' => 'center', 'format' => 'bool' ),
	'DOCK' => array( 'label' => 'Dock', 'align' => 'center', 'format' => 'bool' ),
	'BILL_TO' => array( 'label' => 'Bill-to', 'align' => 'center', 'format' => 'bool' ),
	'INTERMODAL' => array( 'label' => 'Intermodal', 'align' => 'center', 'format' => 'bool' ),
	'CTPAT_REQUIRED' => array( 'label' => 'C-TPAT', 'align' => 'center', 'format' => 'bool' ),
	'ON_CREDIT_HOLD' => array( 'label' => 'Hold', 'align' => 'center', 'format' => 'hidden' ),
	'DEFAULT_EQUIPMENT' => array( 'label' => 'Equipment', 'format' => 'text', 'length' => 110 ),
	//'DEFAULT_COMMODITY' => array( 'label' => 'Commodity', 'format' => 'table',
	//	'table' => COMMODITY_TABLE, 'key' => 'COMMODITY_CODE', 'fields' => 'COMMODITY_NAME' ),
	'DEFAULT_ZONE' => array( 'label' => 'Zone', 'format' => 'text' ),
	'TRACK_PALLETS' => array( 'label' => 'Track&nbsp;Pallets', 'align' => 'center', 'format' => 'bool' ),
	'PALLET_COUNT' => array( 'label' => '#Pallets', 'format' => 'num0', 'link' => 'exp_addpallet.php?client=%pk%&pc=', 'align' => 'right' ),
	//'SYNERGY_IMPORT' => array( 'label' => 'Syn', 'align' => 'center', 'format' => 'bool' ),
	'CHANGED_DATE' => array( 'label' => 'Changed', 'format' => 'timestamp-s', 'length' => 90 ),
	'CHANGED_BY' => array( 'label' => 'By', 'format' => 'table',
		'table' => USER_TABLE, 'key' => 'USER_CODE', 'fields' => 'USERNAME', 'link' => 'exp_edituser.php?CODE=' )
);

$sts_result_clients_zoominfo_layout = array(	//! $sts_result_clients_zoominfo_layout
	'ZOOM_COMPANY_ID' => array( 'label' => 'ZoomInfo Company ID', 'format' => 'text' ),
	'CLIENT_NAME' => array( 'label' => 'Company Name', 'format' => 'text' ),
	'ADDRESS' => array( 'label' => 'Bill to Address-Line One', 'format' => 'table',
		'snippet' => '(SELECT ADDRESS FROM EXP_CONTACT_INFO
			WHERE CONTACT_CODE = CLIENT_CODE AND CONTACT_SOURCE = \'client\'
			AND CONTACT_TYPE IN(\'company\', \'bill_to\') LIMIT 1)' ),
	'ADDRESS2' => array( 'label' => 'Bill to Address-Line Two', 'format' => 'table',
		'snippet' => '(SELECT ADDRESS2 FROM EXP_CONTACT_INFO
			WHERE CONTACT_CODE = CLIENT_CODE AND CONTACT_SOURCE = \'client\'
			AND CONTACT_TYPE IN(\'company\', \'bill_to\') LIMIT 1)' ),
	'CITY' => array( 'label' => 'Bill to City', 'format' => 'table',
		'snippet' => '(SELECT CITY FROM EXP_CONTACT_INFO
			WHERE CONTACT_CODE = CLIENT_CODE AND CONTACT_SOURCE = \'client\'
			AND CONTACT_TYPE IN(\'company\', \'bill_to\') LIMIT 1)' ),
	'STATE' => array( 'label' => 'Bill to State', 'format' => 'table',
		'snippet' => '(SELECT STATE FROM EXP_CONTACT_INFO
			WHERE CONTACT_CODE = CLIENT_CODE AND CONTACT_SOURCE = \'client\'
			AND CONTACT_TYPE IN(\'company\', \'bill_to\') LIMIT 1)' ),
	'ZIP_CODE' => array( 'label' => 'Bill to Zip', 'format' => 'table',
		'snippet' => '(SELECT ZIP_CODE FROM EXP_CONTACT_INFO
			WHERE CONTACT_CODE = CLIENT_CODE AND CONTACT_SOURCE = \'client\'
			AND CONTACT_TYPE IN(\'company\', \'bill_to\') LIMIT 1)' ),
	'COUNTRY' => array( 'label' => 'Bill to Country', 'format' => 'table',
		'snippet' => '(SELECT COUNTRY FROM EXP_CONTACT_INFO
			WHERE CONTACT_CODE = CLIENT_CODE AND CONTACT_SOURCE = \'client\'
			AND CONTACT_TYPE IN(\'company\', \'bill_to\') LIMIT 1)' ),
	'EMAIL' => array( 'label' => 'Customer E-mail', 'format' => 'table',
		'snippet' => '(SELECT EMAIL FROM EXP_CONTACT_INFO
			WHERE CONTACT_CODE = CLIENT_CODE AND CONTACT_SOURCE = \'client\'
			AND CONTACT_TYPE IN(\'company\', \'bill_to\') LIMIT 1)' ),
	'CLIENT_URL' => array( 'label' => 'URL', 'format' => 'text' ),
);

$sts_result_clients_sage50_layout = array(	//! $sts_result_clients_sage50_layout
	'SAGE50_CLIENTID' => array( 'label' => 'Customer ID', 'format' => 'text' ),
	'CLIENT_NAME' => array( 'label' => 'Customer Name', 'format' => 'text' ),
	'PROSPECT' => array( 'label' => 'Prospect', 'format' => 'table',
		'snippet' => '\'FALSE\'' ),
	'INACTIVE' => array( 'label' => 'Inactive', 'format' => 'table',
		'snippet' => '\'FALSE\'' ),
	'CONTACT_NAME' => array( 'label' => 'Bill to Contact First Name', 'format' => 'table',
		'snippet' => '(SELECT CONTACT_NAME FROM EXP_CONTACT_INFO
			WHERE CONTACT_CODE = CLIENT_CODE AND CONTACT_SOURCE = \'client\'
			AND CONTACT_TYPE = \'bill_to\' LIMIT 1)' ),
	'ADDRESS' => array( 'label' => 'Bill to Address-Line One', 'format' => 'table',
		'snippet' => '(SELECT ADDRESS FROM EXP_CONTACT_INFO
			WHERE CONTACT_CODE = CLIENT_CODE AND CONTACT_SOURCE = \'client\'
			AND CONTACT_TYPE = \'bill_to\' LIMIT 1)' ),
	'ADDRESS2' => array( 'label' => 'Bill to Address-Line Two', 'format' => 'table',
		'snippet' => '(SELECT ADDRESS2 FROM EXP_CONTACT_INFO
			WHERE CONTACT_CODE = CLIENT_CODE AND CONTACT_SOURCE = \'client\'
			AND CONTACT_TYPE = \'bill_to\' LIMIT 1)' ),
	'CITY' => array( 'label' => 'Bill to City', 'format' => 'table',
		'snippet' => '(SELECT CITY FROM EXP_CONTACT_INFO
			WHERE CONTACT_CODE = CLIENT_CODE AND CONTACT_SOURCE = \'client\'
			AND CONTACT_TYPE = \'bill_to\' LIMIT 1)' ),
	'STATE' => array( 'label' => 'Bill to State', 'format' => 'table',
		'snippet' => '(SELECT STATE FROM EXP_CONTACT_INFO
			WHERE CONTACT_CODE = CLIENT_CODE AND CONTACT_SOURCE = \'client\'
			AND CONTACT_TYPE = \'bill_to\' LIMIT 1)' ),
	'ZIP_CODE' => array( 'label' => 'Bill to Zip', 'format' => 'table',
		'snippet' => '(SELECT ZIP_CODE FROM EXP_CONTACT_INFO
			WHERE CONTACT_CODE = CLIENT_CODE AND CONTACT_SOURCE = \'client\'
			AND CONTACT_TYPE = \'bill_to\' LIMIT 1)' ),
	'COUNTRY' => array( 'label' => 'Bill to Country', 'format' => 'table',
		'snippet' => '(SELECT COUNTRY FROM EXP_CONTACT_INFO
			WHERE CONTACT_CODE = CLIENT_CODE AND CONTACT_SOURCE = \'client\'
			AND CONTACT_TYPE = \'bill_to\' LIMIT 1)' ),
	'PHONE1' => array( 'label' => 'Telephone 1', 'format' => 'table',
		'snippet' => '(SELECT PHONE_OFFICE FROM EXP_CONTACT_INFO
			WHERE CONTACT_CODE = CLIENT_CODE AND CONTACT_SOURCE = \'client\'
			AND CONTACT_TYPE = \'bill_to\' LIMIT 1)' ),
	'PHONE2' => array( 'label' => 'Telephone 2', 'format' => 'table',
		'snippet' => '(SELECT PHONE_CELL FROM EXP_CONTACT_INFO
			WHERE CONTACT_CODE = CLIENT_CODE AND CONTACT_SOURCE = \'client\'
			AND CONTACT_TYPE = \'bill_to\' LIMIT 1)' ),
	'FAX' => array( 'label' => 'Fax Number', 'format' => 'table',
		'snippet' => '(SELECT PHONE_FAX FROM EXP_CONTACT_INFO
			WHERE CONTACT_CODE = CLIENT_CODE AND CONTACT_SOURCE = \'client\'
			AND CONTACT_TYPE = \'bill_to\' LIMIT 1)' ),
	'EMAIL' => array( 'label' => 'Customer E-mail', 'format' => 'table',
		'snippet' => '(SELECT EMAIL FROM EXP_CONTACT_INFO
			WHERE CONTACT_CODE = CLIENT_CODE AND CONTACT_SOURCE = \'client\'
			AND CONTACT_TYPE = \'bill_to\' LIMIT 1)' ),
	'TERMS' => array( 'label' => 'Use Standard Terms', 'format' => 'table',
		'snippet' => '\'TRUE\'' ),
	'IDUE' => array( 'label' => 'Due Days', 'format' => 'table',
		'snippet' => '30' ),
);

//! Edit/Delete Button Specifications - For use with sts_result

$sts_result_clients_edit = array( //! $sts_result_clients_edit
	'title' => '<span class="glyphicon glyphicon-user"></span> ## Clients',
	'sort' => 'NAME asc',
	'cancel' => 'index.php',
	'add' => 'exp_addclient.php',
	//'actionextras' => 'disabled',
	'addbutton' => 'Add Client',
	'cancelbutton' => 'Back',
	'rowbuttons' => array(
		array( 'url' => 'exp_editclient.php?CODE=', 'key' => 'CLIENT_CODE', 'label' => 'CLIENT_NAME', 'tip' => 'View/edit client ', 'icon' => 'glyphicon glyphicon-edit', 'showif' => 'notdeleted' ),
		array( 'url' => 'exp_deleteclient.php?TYPE=del&CODE=', 'key' => 'CLIENT_CODE', 'label' => 'CLIENT_NAME', 'tip' => 'Delete client ', 'icon' => 'glyphicon glyphicon-trash', 'confirm' => 'yes', 'showif' => 'notdeleted' ),
		array( 'url' => 'exp_deleteclient.php?TYPE=undel&CODE=', 'key' => 'CLIENT_CODE', 'label' => 'CLIENT_NAME', 'tip' => 'Undelete client ', 'icon' => 'glyphicon glyphicon-arrow-left', 'showif' => 'deleted' ),
		array( 'url' => 'exp_deleteclient.php?TYPE=permdel&CODE=', 'key' => 'CLIENT_CODE', 'label' => 'CLIENT_NAME', 'tip' => 'Permanently Delete client ', 'icon' => 'glyphicon glyphicon-remove', 'confirm' => 'yes', 'showif' => 'deleted' ),
		array( 'url' => 'exp_addclientrate.php?TYPE=client&CODE=', 'key' => 'CLIENT_CODE', 'label' => 'CLIENT_NAME', 'tip' => 'Add client rate ', 'icon' => 'glyphicon glyphicon-usd', 'showif' => 'isclient', 'restrict' => EXT_GROUP_FINANCE )
	),
);

//! SCR# 378 - Possible Duplicates
$sts_result_dup_edit = array( //! $sts_result_dup_edit
	'title' => '<span class="glyphicon glyphicon-user"></span> ## Possible Duplicates',
	'sort' => 'CLIENT_NAME asc',
	//'actionextras' => 'disabled',
	'rowbuttons' => array(
		array( 'url' => 'exp_editclient.php?CODE=', 'key' => 'CLIENT_CODE', 'label' => 'CLIENT_NAME', 'tip' => 'View/edit client ', 'icon' => 'glyphicon glyphicon-edit', 'showif' => 'notdeleted' ),
		array( 'url' => 'exp_deleteclient.php?TYPE=del&CODE=', 'key' => 'CLIENT_CODE', 'label' => 'CLIENT_NAME', 'tip' => 'Delete client ', 'icon' => 'glyphicon glyphicon-trash', 'confirm' => 'yes', 'showif' => 'notdeleted' ),
		array( 'url' => 'exp_deleteclient.php?TYPE=undel&CODE=', 'key' => 'CLIENT_CODE', 'label' => 'CLIENT_NAME', 'tip' => 'Undelete client ', 'icon' => 'glyphicon glyphicon-arrow-left', 'showif' => 'deleted' ),
		array( 'url' => 'exp_deleteclient.php?TYPE=permdel&CODE=', 'key' => 'CLIENT_CODE', 'label' => 'CLIENT_NAME', 'tip' => 'Permanently Delete client ', 'icon' => 'glyphicon glyphicon-remove', 'confirm' => 'yes', 'showif' => 'deleted' ),
		array( 'url' => 'exp_addclientrate.php?TYPE=client&CODE=', 'key' => 'CLIENT_CODE', 'label' => 'CLIENT_NAME', 'tip' => 'Add client rate ', 'icon' => 'glyphicon glyphicon-usd', 'showif' => 'isclient', 'restrict' => EXT_GROUP_FINANCE )
	),
);

//! Use this for parsing CSV files & importing clients
$sts_csv_client_parse = array(
	array( 'table' => CLIENT_TABLE, 'class' => 'sts_client', 'primary' => 'CLIENT_CODE',
		'rows' => array(
			'CLIENT_TYPE' => array( 'value' => 'client' ),
			'CLIENT_NAME' => array( 'label' => 'ClientName', 'format' => 'text',
				'required' => true ),
			'CLIENT_ID' => array( 'label' => 'Client ID', 'format' => 'text' ),
			'SAGE50_CLIENTID' => array( 'label' => 'SageID', 'format' => 'text' ),
			'CLIENT_URL' => array( 'label' => 'ClientURL', 'format' => 'text' ),
			'CURRENCY_CODE' => array( 'label' => 'Currency', 'format' => 'enum',
				'default' => 'USD' ),
			'DEFAULT_ZONE' => array( 'label' => 'DefaultZone', 'format' => 'text' ),
			'TERMINAL_ZONE' => array( 'label' => 'TerminalZone', 'format' => 'text' ),
			'OPEN_TIME' => array( 'label' => 'OpenTime', 'format' => 'text' ),
			'CLOSE_TIME' => array( 'label' => 'CloseTime', 'format' => 'text' ),
			'COMMENTS' => array( 'label' => 'Comments', 'format' => 'text' ),
			'CUSTOMS_BROKER' => array( 'label' => 'Broker', 'format' => 'text' ),
			'CONTACT' => array( 'label' => 'MainContact', 'format' => 'text' ),
			'BILLING_CLIENT' => array( 'label' => 'BillingClient', 'format' => 'text' ),
			'ON_CREDIT_HOLD' => array( 'label' => 'CreditHold', 'format' => 'bool' ),
			'CREDIT_LIMIT' => array( 'label' => 'CreditLimit', 'format' => 'number' ),
			'COD_MINIMUM' => array( 'label' => 'CODMin', 'format' => 'number' ),
			'COD_MAXIMUM' => array( 'label' => 'CODMax', 'format' => 'number' ),
			'MILEAGE_TYPE' => array( 'label' => 'Mileage Type', 'format' => 'text' ),
			'SHIPPER' => array( 'label' => 'IsShipper', 'format' => 'bool' ),
			'CONSIGNEE' => array( 'label' => 'IsConsignee', 'format' => 'bool' ),
			'DOCK' => array( 'label' => 'IsDock',  'format' => 'bool' ),
			'BILL_TO' => array( 'label' => 'IsBill-to', 'format' => 'bool' ),
			'POD_NEEDED' => array( 'label' => 'PODNeeded', 'format' => 'bool' ),
			'CDN_TAX_EXEMPT' => array( 'label' => 'CDNTaxExempt', 'format' => 'bool' ),
			'CDN_TAX_EXEMPT_REASON' => array( 'label' => 'TaxExemptReason',
				'format' => 'text' ),
			'CLIENT_GROUP_CODE' => array( 'label' => 'ClientGroup', 'format' => 'text' ),
			'APPOINTMENT_NEEDED' => array( 'label' => 'ApptNeeded', 'format' => 'bool' ),
			'DUNS_CODE' => array( 'label' => 'ClientDUNSCode', 'format' => 'text' ),
			'CREDIT_STATUS' => array( 'label' => 'CreditStatus', 'format' => 'text' ),
			'TRACK_PALLETS' => array( 'label' => 'TrackPallets', 'format' => 'bool' ),
			'PALLET_COUNT' => array( 'label' => 'PalletCount', 'format' => 'number' ),	
			'CLIENT_NOTES' => array( 'label' => 'ClientNotes', 'format' => 'text' ),
			'GENERAL_LIAB_INS' => array( 'label' => 'GeneralIns', 'format' => 'number' ),
			'AUTO_LIAB_INS' => array( 'label' => 'AutoIns', 'format' => 'number' ),
			'CARGO_LIAB_INS' => array( 'label' => 'CargoIns', 'format' => 'number' ),
		),
	),
	array( 'table' => CONTACT_INFO_TABLE, 'class' => 'sts_contact_info',
		'primary' => 'CONTACT_INFO_CODE',
		'rows' => array(
			'CONTACT_CODE' => array( 'format' => 'link', 'value' => 'CLIENT_CODE',
				'required' => true ),
			'CONTACT_SOURCE' => array( 'format' => 'text', 'value' => 'client' ),
			'CONTACT_TYPE' => array( 'format' => 'enum', 'value' => 'bill_to' ),
	
			'LABEL' => array( 'label' => 'Label', 'format' => 'text' ),
			'CONTACT_NAME' => array( 'label' => 'ContactName', 'format' => 'text' ),
			'JOB_TITLE' => array( 'label' => 'JobTitle', 'format' => 'text' ),
			'ADDRESS' => array( 'label' => 'Address', 'format' => 'text' ),
			'ADDRESS2' => array( 'label' => 'Address2', 'format' => 'text' ),
			'CITY' => array( 'label' => 'City', 'format' => 'text' ),
			'STATE' => array( 'label' => 'State', 'format' => 'state' ),
			'ZIP_CODE' => array( 'label' => 'Zip', 'format' => 'text' ),
			'COUNTRY' => array( 'label' => 'Country', 'format' => 'enum', 'default' => 'USA' ),
			'PHONE_OFFICE' => array( 'label' => 'OfficePhone', 'format' => 'text' ),
			'PHONE_EXT' => array( 'label' => 'PhoneExt', 'format' => 'text' ),
			'PHONE_FAX' => array( 'label' => 'PhoneFax', 'format' => 'text' ),
			'PHONE_HOME' => array( 'label' => 'PhoneHome', 'format' => 'text' ),
			'PHONE_CELL' => array( 'label' => 'PhoneCell', 'format' => 'text' ),
			'EMAIL' => array( 'label' => 'Email', 'format' => 'email' )
		),
	),
);

//! Use this for parsing CSV files & importing clients
$sts_csv_lead_parse = array(
	array( 'table' => CLIENT_TABLE, 'class' => 'sts_client', 'primary' => 'CLIENT_CODE',
		'rows' => array(
			'CLIENT_TYPE' => array( 'value' => 'lead' ),
			'CURRENT_STATUS' => array( 'format' => 'number', 'value' => 45 ), // Might be an issue if this changes.
			'CLIENT_NAME' => array( 'label' => 'ClientName', 'format' => 'text',
				'required' => true ),
			'CLIENT_URL' => array( 'label' => 'ClientURL', 'format' => 'text' ),
			'CURRENCY_CODE' => array( 'label' => 'Currency', 'format' => 'enum',
				'default' => 'USD' ),
			'COMMENTS' => array( 'label' => 'Comments', 'format' => 'text' ),
			'CONTACT' => array( 'label' => 'MainContact', 'format' => 'text' ),
			'BILL_TO' => array( 'format' => 'bool', 'value' => true ),
			'CLIENT_NOTES' => array( 'label' => 'ClientNotes', 'format' => 'text' ),
		),
	),
	array( 'table' => CONTACT_INFO_TABLE, 'class' => 'sts_contact_info',
		'primary' => 'CONTACT_INFO_CODE',
		'rows' => array(
			'CONTACT_CODE' => array( 'format' => 'link', 'value' => 'CLIENT_CODE',
				'required' => true ),
			'CONTACT_SOURCE' => array( 'format' => 'text', 'value' => 'client' ),
			'CONTACT_TYPE' => array( 'format' => 'enum', 'value' => 'bill_to' ),
	
			'LABEL' => array( 'label' => 'Label', 'format' => 'text' ),
			'CONTACT_NAME' => array( 'label' => 'ContactName', 'format' => 'text' ),
			'JOB_TITLE' => array( 'label' => 'JobTitle', 'format' => 'text' ),
			'ADDRESS' => array( 'label' => 'Address', 'format' => 'text' ),
			'ADDRESS2' => array( 'label' => 'Address2', 'format' => 'text' ),
			'CITY' => array( 'label' => 'City', 'format' => 'text' ),
			'STATE' => array( 'label' => 'State', 'format' => 'state' ),
			'ZIP_CODE' => array( 'label' => 'Zip', 'format' => 'text' ),
			'COUNTRY' => array( 'label' => 'Country', 'format' => 'enum', 'default' => 'USA' ),
			'PHONE_OFFICE' => array( 'label' => 'OfficePhone', 'format' => 'text' ),
			'PHONE_EXT' => array( 'label' => 'PhoneExt', 'format' => 'text' ),
			'PHONE_FAX' => array( 'label' => 'PhoneFax', 'format' => 'text' ),
			'PHONE_HOME' => array( 'label' => 'PhoneHome', 'format' => 'text' ),
			'PHONE_CELL' => array( 'label' => 'PhoneCell', 'format' => 'text' ),
			'EMAIL' => array( 'label' => 'Email', 'format' => 'email' )
		),
	),
);
		

?>
