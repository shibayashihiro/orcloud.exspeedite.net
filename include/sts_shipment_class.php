<?php
	
// $Id: sts_shipment_class.php 5131 2023-06-13 19:01:28Z dev $
// Shipment class, all activity related to shipments.

// no direct access
defined('_STS_INCLUDE') or die('Restricted access');

require_once( "sts_table_class.php" );
require_once( "sts_detail_class.php" );
require_once( "sts_stop_class.php" );
require_once( "sts_load_class.php" );
require_once( "sts_shipment_load_class.php" );
require_once( "sts_email_class.php" );
require_once( "sts_contact_info_class.php" );
require_once( "sts_user_log_class.php" );
require_once( "sts_status_class.php" );
require_once( "sts_status_codes_class.php" );
require_once( "sts_client_class.php" );

class sts_shipment extends sts_table {

	private $states;
	private $status_table;
	private $status_codes_table;
	private $setting_table;
	private $log_file;
	private $debug_diag_level; //! SCR 531 - debug log level
	private $diag_level;
	private $duplicate_ready_dispatch;
	private $duplicate_freight;

	public $state_name;
	public $state_behavior;
	public $name_state;
	public $behavior_state;
	public $could_not_consolidate;
	
	//! SCR# 499 - cache following states
	private $cache_shipment_following;
	private $cache_billing_following;

	public $billing_states;
	public $billing_state_name;
	public $billing_state_behavior;
	public $billing_name_state;
	public $billing_behavior_state;

	public $state_change_error;
	public $state_change_level;
	private $pcm;
	private $zip;
	public $dock_toggle_direction;
	private $quickbooks_online;
	public $export_quickbooks;
	public $export_sage50;
	private $require_business_code;
	private $multi_company;
	private $track_shipment_disp_ready;
	private $clear_cancelled_billing;
	private $dup_shipment_no_dates;
	private $stopoff_copy_po;
	private $stopoff_copy_pickup;
	private $restrict_salesperson;
	private $containers;
	private $approve_operations;
	private $send_notifications;
	private $dup_reuse_officenum;
		
	// Constructor does not need the table name
	public function __construct( $database, $debug = false ) {
		global $sts_error_level_label, $sts_crm_dir;

		$this->debug = $debug;

		if( $this->debug ) echo "<p>".__METHOD__.": entry, debug = ".($debug ? 'true' : 'false')."</p>";

		$this->primary_key = "SHIPMENT_CODE";
		$this->status_table = sts_status::getInstance($database, $debug);
		$this->setting_table = sts_setting::getInstance($database, $debug);
		
		//! SCR #531 - debug logging setup, including levels
		$this->log_file = $this->setting_table->get( 'option', 'DEBUG_LOG_FILE' );
		if( ctype_alpha($this->log_file[0]) && ctype_alpha($this->log_file[1]) )
			$this->log_file = $sts_crm_dir.DIRECTORY_SEPARATOR.$this->log_file;
		
		$this->debug_diag_level = $this->setting_table->get( 'option', 'DEBUG_DIAG_LEVEL' );
		$this->diag_level =  array_search(strtolower($this->debug_diag_level), $sts_error_level_label);
		if( $this->diag_level === false ) $this->diag_level = EXT_ERROR_ALL;
		
		$this->track_shipment_disp_ready =
			($this->setting_table->get( 'option', 'TRACK_SHIPMENT_DISP_READY' ) == 'true');
		$this->duplicate_ready_dispatch =
			($this->setting_table->get( 'option', 'DUPLICATE_READY_DISPATCH' ) == 'true');
		$this->duplicate_freight =
			($this->setting_table->get( 'option', 'DUPLICATE_SHIPMENT_FREIGHT' ) == 'true');
		$this->dock_toggle_direction = 
			($this->setting_table->get( 'option', 'DOCK_TOGGLE_DIRECTION' ) == 'true');
		$this->export_quickbooks = 
			($this->setting_table->get( 'api', 'EXPORT_QUICKBOOKS' ) == 'true');
		$this->export_sage50 = 
			($this->setting_table->get( 'api', 'EXPORT_SAGE50_CSV' ) == 'true');
		$this->quickbooks_online = 
			($this->setting_table->get( 'api', 'QUICKBOOKS_ONLINE' ) == 'true');
		$this->require_business_code = 
			($this->setting_table->get( 'option', 'REQUIRE_BUSINESS_CODE' ) == 'true');
		$this->multi_company = 
			($this->setting_table->get( 'option', 'MULTI_COMPANY' ) == 'true');
		$this->clear_cancelled_billing = 
			($this->setting_table->get( 'option', 'CLEAR_CANCELLED_BILLING' ) == 'true');
		
		//! SCR# 910 - Reuse office numbers from cancelled shipment on duplicate
		$this->dup_reuse_officenum = 
			($this->setting_table->get( 'option', 'DUP_RESUSE_OFFICENUM' ) == 'true');
			
		$this->dup_shipment_no_dates = 
			$this->setting_table->get( 'option', 'DUP_SHIPMENT_NO_DATES' );

		//! SCR# 852 - Containers Feature
		$this->containers = $this->setting_table->get( 'option', 'CONTAINERS' ) == 'true';

		//! SCR# 906 - send notifications
		$this->send_notifications = $this->setting_table->get( 'option', 'SEND_NOTIFICATIONS' ) == 'true';

		//! SCR# 779 - Stopoff, copy PO#s, Pickups
		$this->stopoff_copy_po = 
			($this->setting_table->get( 'option', 'STOPOFF_COPY_PO' ) == 'true');
		$this->stopoff_copy_pickup = 
			($this->setting_table->get( 'option', 'STOPOFF_COPY_PICKUP' ) == 'true');
			
		$this->approve_operations = $this->setting_table->get( 'option', 'SHIPMENT_APPROVE_OPERATIONS' ) == 'true';

		//! SCR# 818 - restrict salesperson
		$this->restrict_salesperson = $this->setting_table->get( 'option', 'EDIT_SALESPERSON_RESTRICTED' ) == 'true';
		
		if( $this->debug ) echo "<p>Create sts_shipment</p>";
		parent::__construct( $database, SHIPMENT_TABLE, $debug);
		$this->load_states();
		$this->status_codes_table = sts_status_codes::getInstance($database, $debug);
		$this->cache_shipment_following = $this->status_codes_table->cache_following_states( 'shipment' );
		$this->cache_billing_following = $this->status_codes_table->cache_following_states( 'shipment-bill' );
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

	// Override parent to include checking address with PC*Miler
	public function update( $code, $values, $validate = true ) {
		global $sts_crm_dir;
		require_once( "sts_zip_class.php" );
		
		if( $this->debug ) {
			echo "<pre>".__METHOD__." entry, code, values, validate\n";
			var_dump($code, $values, $validate);
			echo "</pre>";
		}
		if( ! isset($this->zip) )
			$this->zip = sts_zip::getInstance( $this->database, $this->debug );

		$result = true;
		if( is_array($values) && isset($values["CURRENT_STATUS"]) &&
			$values["CURRENT_STATUS"] == $this->behavior_state["assign"] &&
			$this->track_shipment_disp_ready ) {
			
			if( strpos($code, '=') !== false ||
				strpos(strtolower($code), ' in ') !== false )
				$match = $code;
			else
				$match = $this->primary_key." = ".$code;

			//! SCR# 607 - don't alert/log if dispatch->ready
			$check = $this->fetch_rows($match, "CURRENT_STATUS, LOAD_CODE");
			if( is_array($check) && count($check) == 1  &&
				isset($check[0]["CURRENT_STATUS"]) &&
				! in_array($check[0]["CURRENT_STATUS"],
					array($this->behavior_state["entry"],
					$this->behavior_state["assign"],
					$this->behavior_state["dispatch"])) ) {
					
				if( $match != $code && is_numeric($code) )
					$this->add_shipment_status( $code, "update: Change CURRENT_STATUS from ".$this->state_name[$check[0]["CURRENT_STATUS"]]." to Ready" );

				$email = sts_email::getInstance($this->database, $this->debug);
				$email->send_alert(__METHOD__.": Change CURRENT_STATUS from ".$this->state_name[$check[0]["CURRENT_STATUS"]]." to Ready<br>".
				"This might not be an error, especially if Duncan is testing something.<br>".
				"values = <pre>".print_r($values, true)."</pre><br>".
				"current values = <pre>".print_r($check, true)."</pre><br>" );
			}
		}
		
		if( $result ) {
			$result = parent::update( $code, $values );
			
			//! SCR# 531 - log event
			$this->log_event( __METHOD__.': UPDATED shipment '.$code.
				' result = '.($result ? 'true' : 'false').
				' by = '.(isset($_SESSION['EXT_USERNAME']) ? $_SESSION['EXT_USERNAME'] : 'unknown').
				"\nvalues\n".print_r($values, true), EXT_ERROR_DEBUG );

			if( $validate ) {
				
				$newvalues = $this->fetch_rows($this->primary_key." = ".$code);
				if( is_array($newvalues) && count($newvalues) > 0 ) {
					$shipper_addr = array('ADDRESS' => $newvalues[0]['SHIPPER_ADDR1'], 'ADDRESS2' => $newvalues[0]['SHIPPER_ADDR2'],
						'CITY' => $newvalues[0]['SHIPPER_CITY'], 'STATE' => $newvalues[0]['SHIPPER_STATE'],
						'ZIP_CODE' => $newvalues[0]['SHIPPER_ZIP'], 'COUNTRY' => $newvalues[0]['SHIPPER_COUNTRY'] );
					$cons_addr = array('ADDRESS' => $newvalues[0]['CONS_ADDR1'], 'ADDRESS2' => $newvalues[0]['CONS_ADDR2'],
						'CITY' => $newvalues[0]['CONS_CITY'], 'STATE' => $newvalues[0]['CONS_STATE'],
						'ZIP_CODE' => $newvalues[0]['CONS_ZIP'], 'COUNTRY' => $newvalues[0]['CONS_COUNTRY'] );
		
					$broker =  $newvalues[0]['SHIPPER_COUNTRY'] <> $newvalues[0]['CONS_COUNTRY'];
	
					//! Gather customs broker address
					if( $broker )
						$broker_addr = array('ADDRESS' => $newvalues[0]['BROKER_ADDR1'], 'ADDRESS2' => $newvalues[0]['BROKER_ADDR2'],
						'CITY' => $newvalues[0]['BROKER_CITY'], 'STATE' => $newvalues[0]['BROKER_STATE'],
						'ZIP_CODE' => $newvalues[0]['BROKER_ZIP'], 'COUNTRY' => $newvalues[0]['BROKER_COUNTRY'] );
					
		
					$bcc = 0;
					if( isset($newvalues[0]['BILLTO_CLIENT_CODE']) && $newvalues[0]['BILLTO_CLIENT_CODE'] > 0 )
						$bcc = $newvalues[0]['BILLTO_CLIENT_CODE'];
						
					unset($newvalues);
					$newvalues = array();
					$valid = $this->zip->validate_various($shipper_addr);
					$newvalues['SHIPPER_VALID'] = $valid;
					$newvalues['SHIPPER_CODE'] = $valid == 'valid' ? '' : $this->zip->get_code();
					$newvalues['SHIPPER_DESCR'] = $valid == 'valid' ? '' : $this->trim_to_fit('SHIPPER_DESCR', $this->zip->get_description());
					$newvalues['SHIPPER_VALID_SOURCE'] = $this->zip->get_source();
					$newvalues['SHIPPER_LAT'] = $this->zip->get_lat();
					$newvalues['SHIPPER_LON'] = $this->zip->get_lon();
	
					$valid = $this->zip->validate_various($cons_addr);
					$newvalues['CONS_VALID'] = $valid;
					$newvalues['CONS_CODE'] = $valid == 'valid' ? '' : $this->zip->get_code();
					$newvalues['CONS_DESCR'] = $valid == 'valid' ? '' : $this->trim_to_fit('CONS_DESCR', $this->zip->get_description());
					$newvalues['CONS_VALID_SOURCE'] = $this->zip->get_source();
					$newvalues['CONS_LAT'] = $this->zip->get_lat();
					$newvalues['CONS_LON'] = $this->zip->get_lon();
	
					//! Validate customs broker address
					if( $broker && is_array($broker_addr) ) {
						$valid = $this->zip->validate_various($broker_addr);
						$newvalues['BROKER_VALID'] = $valid;
						$newvalues['BROKER_CODE'] = $valid == 'valid' ? '' : $this->zip->get_code();
						$newvalues['BROKER_DESCR'] = $valid == 'valid' ? '' : $this->trim_to_fit('BROKER_DESCR', $this->zip->get_description());
						$newvalues['BROKER_VALID_SOURCE'] = $this->zip->get_source();
						$newvalues['BROKER_LAT'] = $this->zip->get_lat();
						$newvalues['BROKER_LON'] = $this->zip->get_lon();
					}
	
					// Get validation from contact_info
					if( $bcc > 0 ) {
						if( $this->debug ) echo "<p>sts_shipment > update: BILLTO_CLIENT_CODE = ".$bcc."</p>";
						
						$contact_info_table = sts_contact_info::getInstance($this->database, $this->debug);
						$validation = $contact_info_table->get_validation( $bcc, 'client', 'bill_to' );
							
						if( is_array($validation) && count($validation) == 1 &&
							isset($validation[0]["ADDR_VALID"]) ) {
							$newvalues['BILLTO_VALID'] = $validation[0]["ADDR_VALID"];
							$newvalues['BILLTO_CODE'] = $validation[0]["ADDR_CODE"];
							$newvalues['BILLTO_DESCR'] = $validation[0]["ADDR_DESCR"];
							$newvalues['BILLTO_VALID_SOURCE'] = $validation[0]["VALID_SOURCE"];
							$newvalues['BILLTO_LAT'] = $validation[0]["LAT"];
							$newvalues['BILLTO_LON'] = $validation[0]["LON"];
						}
					}
					if( $this->debug ) {
						echo "<pre>code, newvalues\n";
						var_dump($code, $newvalues);
						echo "</pre>";
					}					
					$result = parent::update( $code, $newvalues );
				}
			}
		}
		return $result;
	}
	
	//! SCR# 910 - Reuse office numbers from cancelled shipment on duplicate
	public function dup_reuse_officenum() {
		return $this->dup_reuse_officenum;
	}

	//! SCR# 818 - restrict salesperson
	public function restrict_salesperson() {
		return $this->restrict_salesperson;
	}
	
	public function create_empty( $bill_to_code = false ) {
		global $_SESSION;
		
		$fields = [ 'SHIPMENT_TYPE' => 'prepaid',
			'PRIORITY' => 'regular',
			'DISTANCE_SOURCE' => 'none',
			'OFFICE_CODE' => $_SESSION['EXT_USER_OFFICE'],
			'CURRENT_STATUS' => $this->behavior_state['entry'],
			'BILLING_STATUS' => $this->billing_behavior_state['entry'],
			'LOAD_CODE' => 0 ];
		
		//! SCR# 818 - restrict salesperson
		if( ! $this->restrict_salesperson ) {
			$fields['SALES_PERSON'] = $_SESSION['EXT_USER_CODE'];
		}
		
		//! Set the Bill-to client
		if( $bill_to_code ) {
			$query2 = "SELECT CLIENT_CODE, CLIENT_NAME, BILL_TO, ON_CREDIT_HOLD, SALES_PERSON,
				CONTACT_TYPE, COALESCE(LABEL,NAME) LABEL, CONTACT_NAME, ADDRESS, ADDRESS2, CITY, STATE,
				ZIP_CODE, COUNTRY, PHONE_OFFICE, PHONE_EXT, PHONE_FAX, PHONE_CELL, EMAIL,
				DEFAULT_EQUIPMENT, DEFAULT_COMMODITY, TERMINAL_ZONE,
				ADDR_VALID
				FROM EXP_CLIENT
				JOIN EXP_CONTACT_INFO
				ON CONTACT_CODE = CLIENT_CODE
				AND CONTACT_SOURCE = 'client'
				AND CONTACT_TYPE = 'bill_to'
				AND EXP_CONTACT_INFO.ISDELETED = FALSE
				
				WHERE CLIENT_CODE = $bill_to_code
				AND BILL_TO = TRUE
				AND EXP_CLIENT.ISDELETED = FALSE
				ORDER BY CLIENT_NAME ASC, CONTACT_NAME ASC
				LIMIT 1";
			
			$result = $this->database->get_multiple_rows($query2);

			if( is_array($result) && count($result) == 1 ) {
				$fields["BILLTO_CLIENT_CODE"] = $bill_to_code;
				if( ! empty($result[0]["CLIENT_NAME"])) $fields["BILLTO_NAME"] = $result[0]["CLIENT_NAME"];

				if( ! empty($result[0]["ADDRESS"])) $fields["BILLTO_ADDR1"] = $result[0]["ADDRESS"];
				if( ! empty($result[0]["ADDRESS2"])) $fields["BILLTO_ADDR2"] = $result[0]["ADDRESS2"];
				if( ! empty($result[0]["CITY"])) $fields["BILLTO_CITY"] = $result[0]["CITY"];

				if( ! empty($result[0]["STATE"])) $fields["BILLTO_STATE"] = $result[0]["STATE"];
				if( ! empty($result[0]["ZIP_CODE"])) $fields["BILLTO_ZIP"] = $result[0]["ZIP_CODE"];
				if( ! empty($result[0]["COUNTRY"])) $fields["BILLTO_COUNTRY"] = $result[0]["COUNTRY"];

				if( ! empty($result[0]["SALES_PERSON"])) $fields["SALES_PERSON"] = $result[0]["SALES_PERSON"];
				if( ! empty($result[0]["PHONE_OFFICE"])) $fields["BILLTO_PHONE"] = $result[0]["PHONE_OFFICE"];
				if( ! empty($result[0]["PHONE_EXT"])) $fields["BILLTO_EXT"] = $result[0]["PHONE_EXT"];

				if( ! empty($result[0]["PHONE_FAX"])) $fields["BILLTO_FAX"] = $result[0]["PHONE_FAX"];
				if( ! empty($result[0]["PHONE_CELL"])) $fields["BILLTO_CELL"] = $result[0]["PHONE_CELL"];
				if( ! empty($result[0]["EMAIL"])) $fields["BILLTO_EMAIL"] = $result[0]["EMAIL"];

				if( ! empty($result[0]["CONTACT_NAME"])) $fields["BILLTO_CONTACT"] = $result[0]["CONTACT_NAME"];
			}
		}

		$multi_company = 
			($this->setting_table->get( 'option', 'MULTI_COMPANY' ) == 'true');
		//! Add serial number by office
		if( $multi_company ) {
			$fields['-SS_NUMBER'] = 'SS_SERIAL('.$_SESSION['EXT_USER_OFFICE'].')';
		}
	
		//! SCR 160 - Populate the business code from the office table
		if( $_SESSION['EXT_USER_OFFICE'] > 0 ) {
			$check = $this->database->get_one_row("
				SELECT BUSINESS_CODE FROM EXP_OFFICE
				WHERE OFFICE_CODE = ".$_SESSION['EXT_USER_OFFICE']);
			if( is_array($check) && ! empty($check["BUSINESS_CODE"]))
				$fields['BUSINESS_CODE'] = $check["BUSINESS_CODE"];
		}
		
		return $this->add( $fields );
	}
	
	//! SCR 20 - For new bill-to, email accounting for first use
	public function check_new_client( $pk ) {
		global $sts_crm_root;
		
		$notify_address = $this->setting_table->get( 'email', 'EMAIL_NOTIFY_ACCOUNTING' );
		//$this->log_event('check_new_client: '.$pk.' '.$notify_address );
		
		if( isset($notify_address) && $notify_address <> '' ) {
			$check = $this->database->get_one_row("
				SELECT S.BILLTO_CLIENT_CODE, S.SS_NUMBER,
				(SELECT USERNAME FROM EXP_USER
				WHERE USER_CODE = S.SALES_PERSON) USERNAME,
				(SELECT CLIENT_NAME FROM EXP_CLIENT
				WHERE CLIENT_CODE = S.BILLTO_CLIENT_CODE) CLIENT_NAME,
				(SELECT ACCOUNTING_NOTIFIED FROM EXP_CLIENT
				WHERE CLIENT_CODE = S.BILLTO_CLIENT_CODE) NOTIFIED,
				COUNT(*) NUM
				FROM EXP_SHIPMENT S, EXP_SHIPMENT T
				WHERE S.BILLTO_CLIENT_CODE = T.BILLTO_CLIENT_CODE
				AND S.SHIPMENT_CODE = $pk
				AND S.BILLTO_CLIENT_CODE > 0");
			
			if( is_array($check) ) {
				if( isset($check["NOTIFIED"]) && isset($check["NUM"]) &&
					$check["NOTIFIED"] == 0 && $check["NUM"] == 1 ) {
					
					//Send email
					$email = sts_email::getInstance($this->database, $this->debug);
					if( $email->enabled() ) {
						$subject = 'New Bill-to client '.(!empty($check["CLIENT_NAME"]) ? $check["CLIENT_NAME"] : '');
						$message = '<h3>New Bill-to client '.(!empty($check["CLIENT_NAME"]) ? $check["CLIENT_NAME"] : '').'</h3>
						<p>Client: '.$sts_crm_root.'/exp_editclient.php?CODE='.$check["BILLTO_CLIENT_CODE"].'</p>
						<p>Sales person: '.(!empty($check["USERNAME"]) ? $check["USERNAME"] : '').'</p>
						<p>Shipment '.$pk.(!empty($check["SS_NUMBER"]) ? ' / '.$check["SS_NUMBER"] : '').': '.$sts_crm_root.'/exp_addshipment.php?CODE='.$pk.'</p>';
						
						$result = $email->send_email( $notify_address, "", $subject, $message );
					}
				}
				if( isset($check["NOTIFIED"]) && $check["NOTIFIED"] == 0 &&
					isset($check["BILLTO_CLIENT_CODE"]) ) {
					// Set flag to show accounting has been notified
					$this->database->get_one_row("
						UPDATE EXP_CLIENT
						SET ACCOUNTING_NOTIFIED = 1
						WHERE CLIENT_CODE = ".$check["BILLTO_CLIENT_CODE"] );
				}
			}
		}
	}
	
	//! SCR# 607 - if no load given, look it up
	public function add_shipment_status( $pk, $comment, $lat = false, $lon = false, $load = false ) {
			
			if( $load == false ) {
				$check = $this->fetch_rows($this->primary_key." = ".$pk, "LOAD_CODE");
				if( is_array($check) && count($check) == 1 &&
					isset($check[0]) && isset($check[0]["LOAD_CODE"]) && $check[0]["LOAD_CODE"] > 0 )
					$load = $check[0]["LOAD_CODE"];
			}
			$dummy = $this->status_table->add_shipment_status( $pk, $comment, $lat, $lon, $load );
	}
	
	public function check_duplicate_po_numbers( $pk, $po_numbers ) {
		$result = false;
		if( count($po_numbers) > 0 ) {
			$pos = array();
			foreach($po_numbers as $po) {
				$pos[] = urldecode($po);
			}
			$match = "'".implode("', '", $pos)."'";
			$result = $this->fetch_rows( "(PO_NUMBER IN (".$match.") OR
				PO_NUMBER2 IN (".$match.") OR PO_NUMBER3 IN (".$match.") OR
				PO_NUMBER4 IN (".$match.") OR PO_NUMBER5 IN (".$match.")) AND
				SHIPMENT_CODE <> ".$pk." AND CURRENT_STATUS <> ".
				$this->behavior_state["cancel"],
				"SHIPMENT_CODE, CURRENT_STATUS, PICKUP_DATE, DELIVER_DATE,
				PO_NUMBER, PO_NUMBER2, PO_NUMBER3, PO_NUMBER4, PO_NUMBER5" );
		}
		return $result;
	}
	
	private function load_states() {
		
		$cached = $this->cache->get_state_table( 'shipment' );
		
		if( is_array($cached) && count($cached) > 0 ) {
			$this->states = $cached;
		} else {
			$this->states = $this->database->get_multiple_rows("select STATUS_CODES_CODE, STATUS_STATE, BEHAVIOR, PREVIOUS
				FROM EXP_STATUS_CODES
				WHERE SOURCE_TYPE = 'shipment'
				ORDER BY STATUS_CODES_CODE ASC");
			assert( count($this->states)." > 0", "Unable to load states for shipments" );
		}
		
		$this->state_name = array();
		$this->state_behavior = array();
		$this->name_state = array();
		$this->behavior_state = array();
		foreach( $this->states as $row ) {
			$this->state_name[$row['STATUS_CODES_CODE']] = $row['STATUS_STATE'];
			$this->state_behavior[$row['STATUS_CODES_CODE']] = $row['BEHAVIOR'];
			$this->name_state[$row['STATUS_STATE']] = $row['STATUS_CODES_CODE'];
			$this->behavior_state[$row['BEHAVIOR']] = $row['STATUS_CODES_CODE'];
		}

		$cached = $this->cache->get_state_table( 'shipment-bill' );
		
		if( is_array($cached) && count($cached) > 0 ) {
			$this->billing_states = $cached;
		} else {
			$this->billing_states = $this->database->get_multiple_rows("select STATUS_CODES_CODE, STATUS_STATE, BEHAVIOR, PREVIOUS
				FROM EXP_STATUS_CODES
				WHERE SOURCE_TYPE = 'shipment-bill'
				ORDER BY STATUS_CODES_CODE ASC");
			assert( count($this->states)." > 0", "Unable to load states for shipment billing" );
		}
		
		$this->billing_state_name = array();
		$this->billing_state_behavior = array();
		$this->billing_name_state = array();
		$this->billing_behavior_state = array();
		foreach( $this->billing_states as $row ) {
			$this->billing_state_name[$row['STATUS_CODES_CODE']] = $row['STATUS_STATE'];
			$this->billing_state_behavior[$row['STATUS_CODES_CODE']] = $row['BEHAVIOR'];
			$this->billing_name_state[$row['STATUS_STATE']] = $row['STATUS_CODES_CODE'];
			$this->billing_behavior_state[$row['BEHAVIOR']] = $row['STATUS_CODES_CODE'];
		}

	}
	
	public function log_event( $event, $level = EXT_ERROR_ERROR ) {		
		if( $this->diag_level >= $level ) {
			if( isset($this->log_file) && $this->log_file <> '' &&
				 ! is_dir($this->log_file) ) {
				file_put_contents($this->log_file, date('m/d/Y h:i:s A')." ".$event.PHP_EOL,
					(file_exists($this->log_file) ? FILE_APPEND : 0) );
			}
		}
	}

	//! Check for possible shipments that can be combined for billing.
	// If possible, return an array, else return false.
	// Depends on setting option/CONSOLIDATE_SHIPMENTS = true
	public function check_consolidate_shipments( $pk ) {
		$result = false;
		$can_consolidate =
			($this->setting_table->get( 'option', 'CONSOLIDATE_SHIPMENTS' ) == 'true');
		
		if( $this->debug ) echo "<p>".__METHOD__.": shipment = $pk setting = ".($can_consolidate ? 'true' : 'false')."</p>";
		if( $can_consolidate ) {	// Setting enabled
			//! SCR# 450 - never across different offices
			$check = $this->fetch_rows($this->primary_key." = ".$pk,
				"SS_NUMBER, SHIPMENT_CURRENCY(".$pk.") AS CURR,
				BILLING_STATUS, BILLTO_NAME, BILLTO_CLIENT_CODE, COALESCE(OFFICE_CODE, 0) AS OFFICE_CODE");
			if( is_array($check) && count($check) == 1 &&
				isset($check[0]["BILLING_STATUS"]) &&
				isset($check[0]["BILLTO_CLIENT_CODE"]) ) {
				//! SCR# 340 - allow for Approved (Office)
				$can_consolidate =
					in_array( $this->billing_state_behavior[$check[0]["BILLING_STATUS"]],
						array('entry', 'oapproved', 'unapproved'));
				$consolidate_client = $check[0]["BILLTO_CLIENT_CODE"];
				if( $this->multi_company && isset($check[0]["SS_NUMBER"]))
					$ss_number = $check[0]["SS_NUMBER"];
				$currency = $check[0]["CURR"];
			}
		}
		
		if( $can_consolidate && isset($consolidate_client) ) {
			//! SCR# 209 - fix SQL error
			//! SCR# 346 - add oapproved state
			//! SCR# 450 - never across different offices
			$office = intval($check[0]["OFFICE_CODE"]);
			$possibles = $this->database->get_multiple_rows("
				SELECT SHIPMENT_CODE, CURRENT_STATUS, BILLING_STATUS, BILLTO_NAME, BILLTO_CLIENT_CODE,
					SHIPPER_NAME, SHIPPER_CITY, SHIPPER_STATE,
					CONS_NAME, CONS_CITY, CONS_STATE, ACTUAL_DELIVERY, TOTAL,
					CONSOLIDATE_NUM, REF_NUMBER, SS_NUMBER
				FROM EXP_SHIPMENT
				LEFT JOIN EXP_CLIENT_BILLING
				ON SHIPMENT_ID = EXP_SHIPMENT.SHIPMENT_CODE
				WHERE BILLING_STATUS IN (".$this->billing_behavior_state["entry"].", ".$this->billing_behavior_state["oapproved"].", ".$this->billing_behavior_state["unapproved"].")
				AND BILLTO_CLIENT_CODE = ".$consolidate_client."
				AND OFFICE_CODE = ".$office."
				AND CURRENT_STATUS != ".$this->behavior_state["cancel"]."
				AND COALESCE(CONSOLIDATE_NUM, ".$pk.") = ".$pk."
				AND (SHIPMENT_ID IS NULL OR SHIPMENT_CURRENCY(SHIPMENT_CODE) = SHIPMENT_CURRENCY(".$pk.") )
				ORDER BY CONSOLIDATE_NUM DESC, ACTUAL_DELIVERY DESC");
			
			if( is_array($possibles) && count($possibles) > 0 )
				$result = $possibles;
			
			//! SCR# 550 - look for shipments that failed the consolidation
			$this->could_not_consolidate = array();
			if( $this->multi_company && isset($ss_number) ) {
				$failed = $this->database->get_multiple_rows("
					SELECT SHIPMENT_CODE, CURRENT_STATUS, BILLING_STATUS, BILLTO_NAME, BILLTO_CLIENT_CODE,
						SHIPPER_NAME, SHIPPER_CITY, SHIPPER_STATE,
						CONS_NAME, CONS_CITY, CONS_STATE, ACTUAL_DELIVERY, TOTAL,
						CONSOLIDATE_NUM, REF_NUMBER, SS_NUMBER, SHIPMENT_CURRENCY(SHIPMENT_CODE) AS CURR, OFFICE_CODE
					FROM EXP_SHIPMENT
					LEFT JOIN EXP_CLIENT_BILLING
					ON SHIPMENT_ID = EXP_SHIPMENT.SHIPMENT_CODE
					WHERE SS_NUMBER LIKE '".$ss_number."-%'
					AND (BILLING_STATUS NOT IN (".$this->billing_behavior_state["entry"].", ".$this->billing_behavior_state["oapproved"].", ".$this->billing_behavior_state["unapproved"].")
	                OR BILLTO_CLIENT_CODE != ".$consolidate_client."
					OR OFFICE_CODE != ".$office."
					OR CURRENT_STATUS = ".$this->behavior_state["cancel"]."
					OR COALESCE(CONSOLIDATE_NUM, ".$pk.") != ".$pk."
					OR (SHIPMENT_ID IS NOT NULL AND SHIPMENT_CURRENCY(SHIPMENT_CODE) != SHIPMENT_CURRENCY(".$pk.")))
					ORDER BY CONSOLIDATE_NUM DESC, ACTUAL_DELIVERY DESC
				");
				if( is_array($failed) && count($failed) > 0 ) {
					foreach( $failed as $row ) {
						if( ! in_array($row["BILLING_STATUS"], array(
							$this->billing_behavior_state["entry"],
							$this->billing_behavior_state["oapproved"],
							$this->billing_behavior_state["unapproved"])) ) {
							$this->could_not_consolidate[] = array( $row["SHIPMENT_CODE"], $row["SS_NUMBER"], 'Incorrect billing state' );
							
						} else if( $row["BILLTO_CLIENT_CODE"] != $consolidate_client ) {
							$this->could_not_consolidate[] = array( $row["SHIPMENT_CODE"], $row["SS_NUMBER"], 'Different bill-to client' );

						} else if( $row["OFFICE_CODE"] != $office ) {
							$this->could_not_consolidate[] = array( $row["SHIPMENT_CODE"], $row["SS_NUMBER"], 'Different office '.$row["OFFICE_CODE"] );
						} else if( $row["CURRENT_STATUS"] == $this->behavior_state["cancel"] ) {
							$this->could_not_consolidate[] = array( $row["SHIPMENT_CODE"], $row["SS_NUMBER"], 'Cancelled' );
						} else if( isset($row["CONSOLIDATE_NUM"]) && $row["CONSOLIDATE_NUM"] != $pk ) {
							$this->could_not_consolidate[] = array( $row["SHIPMENT_CODE"], $row["SS_NUMBER"], 'Consolidated with '.$row["CONSOLIDATE_NUM"] );
						} else if( $row["CURR"] != $currency ) {
							$this->could_not_consolidate[] = array( $row["SHIPMENT_CODE"], $row["SS_NUMBER"], 'Different currency '.$row["CURR"] );
						}
					}
				}
			}
		}
		if( $this->debug ) {
				echo "<pre>".__METHOD__.": result\n";
				var_dump($result);
				echo "</pre>";
		}
		return $result;
	}
	
	//! Combine two shipments for billing.
	// Depends on setting option/CONSOLIDATE_SHIPMENTS = true
	public function consolidate_shipment( $pk, $add ) {
		$result = false;
		$can_consolidate =
			($this->setting_table->get( 'option', 'CONSOLIDATE_SHIPMENTS' ) == 'true');
		
		if( $this->debug ) echo "<p>".__METHOD__.": shipment = $pk add = $add setting = ".($can_consolidate ? 'true' : 'false')."</p>";
		if( $can_consolidate ) {	// Setting enabled
			$result = $this->update( $this->primary_key.' IN ('.$pk.', '.$add.')',
				array( 'CONSOLIDATE_NUM' => $pk ) );
			$dummy = $this->add_shipment_status( $add, 'Consolidate this to shipment# '.$pk );
			$dummy = $this->add_shipment_status( $pk, 'Consolidate shipment# '.$add.' to this one' );
		}
		
		return $result;
	}
	
	//! SCR# 454 - get links to consolidated shipments or empty string
	public function consolidated_shipments( $pk ) {
		$result = "";
		$check = $this->fetch_rows("CONSOLIDATE_NUM IS NOT NULL
			AND CONSOLIDATE_NUM = (SELECT CONSOLIDATE_NUM
			FROM EXP_SHIPMENT WHERE SHIPMENT_CODE = $pk)", "SHIPMENT_CODE, SS_NUMBER");

		if( is_array($check) && count($check) > 0 ) {
			
			$res = array();
			foreach( $check as $row ) {
				$label = $this->multi_company && ! empty($row["SS_NUMBER"]) ? $row["SS_NUMBER"] : $row["SHIPMENT_CODE"];
				$value = $row["SHIPMENT_CODE"];
				$res[] = '<a class="btn btn-xs btn-info" href="exp_addshipment.php?CODE='.$value.'"'.
					($pk == $value ? ' disabled="disabled"' : '').'>'.$label.'</a>';
			}
			$result = implode(' ', $res);
		}
		
		return $result;
	}
	
	//! Separate two shipments for billing.
	// Depends on setting option/CONSOLIDATE_SHIPMENTS = true
	public function unconsolidate_shipment( $pk, $del ) {
		$result = false;
		$can_consolidate = 
			($this->setting_table->get( 'option', 'CONSOLIDATE_SHIPMENTS' ) == 'true');
		
		if( $this->debug ) echo "<p>".__METHOD__.": shipment = $pk del = $del setting = ".($can_consolidate ? 'true' : 'false')."</p>";
		if( $can_consolidate ) {	// Setting enabled
			$result = $this->update( $this->primary_key.' = '.$del,
				array( 'CONSOLIDATE_NUM' => 'NULL' ) );

			// Any more shipments consolidated?
			$check = $this->database->get_one_row("
				SELECT COUNT(*) AS NUM
				FROM EXP_SHIPMENT
				WHERE CONSOLIDATE_NUM = ".$pk."
				AND SHIPMENT_CODE != ".$pk);
			
			// Clear the link for this one.
			if( is_array($check) && $check["NUM"] == 0 ) {
				$result = $this->update( $this->primary_key.' = '.$pk,
					array( 'CONSOLIDATE_NUM' => 'NULL' ) );
				$dummy = $this->add_shipment_status( $del, 'Remove this from shipment# '.$pk );
				$dummy = $this->add_shipment_status( $pk, 'Remove shipment# '.$del.' from this one' );
			}
		}
		
		return $result;
	}
	
	//! SCR# 499 - use cached following states
	public function following_states( $this_state, $source = 'shipment' ) {
		if( $this->debug ) echo "<p>".__METHOD__.": this_state = $this_state source = $source</p>";
		
		$cache = $source == 'shipment' ? $this->cache_shipment_following :
			$this->cache_billing_following;
	
		$following = is_array($cache) && isset($cache[$this_state]) ? $cache[$this_state] : array();
		
		//if( $this->debug ) {
		//	echo "<p>".__METHOD__.": following= </p>
		//	<pre>";
		//	var_dump($following);
		//	echo "</pre>";
		//}
		return $following;
	}

	//! Check if the billing can change state
	public function billing_state_change_ok( $pk, $current_state, $state ) {

		if( $this->debug ) echo "<p>".__METHOD__.": pk = $pk, current_state = $current_state (".
			$this->billing_state_behavior[$current_state]."), state = $state (".
			$this->billing_state_behavior[$state].")</p>";
		$this->state_change_error = '';
		$this->state_change_level = EXT_ERROR_WARNING;	// Default to warning
		$ok_to_continue = false;

		// Preconditions for changing state
		switch( $this->billing_state_behavior[$state] ) {
	
			case 'oapproved':	//! oapproved - office approved
			case 'oapproved2':	//! oapproved2 - operations approved
				//! SCR# 289 - change to EXT_GROUP_BILLING
				$ok_to_continue = (
					($this->billing_state_behavior[$state] == 'oapproved' && $this->multi_company) ||
					($this->billing_state_behavior[$state] == 'oapproved2' && $this->approve_operations)
					) && in_group(EXT_GROUP_BILLING);

				if( $ok_to_continue ) {
					$check = $this->fetch_rows("BILLING_STATUS <> ".$state." AND ".
						//! SCR# 504 - if cancelled, don't approve
						"CURRENT_STATUS <> ".$this->behavior_state['cancel']." AND ".
						$this->primary_key." = ".$pk, 
						$this->primary_key.", (COALESCE(BILLTO_NAME,'') <> '' AND 
						COALESCE(BILLTO_ADDR1,'') <> '' AND 
						COALESCE(BILLTO_CITY,'') <> '' AND 
						COALESCE(BILLTO_STATE,'') <> '' AND 
						COALESCE(BILLTO_ZIP,'') <> '') AS BILL_TO_COMPLETE,
						COALESCE((SELECT ON_CREDIT_HOLD FROM EXP_CLIENT
						WHERE CLIENT_CODE = BILLTO_CLIENT_CODE),0) AS ON_CREDIT_HOLD,
						(COALESCE(SHIPPER_NAME,'') <> '' AND 
						COALESCE(SHIPPER_ADDR1,'') <> '' AND 
						COALESCE(SHIPPER_CITY,'') <> '' AND 
						COALESCE(SHIPPER_STATE,'') <> '' AND 
						COALESCE(SHIPPER_ZIP,'') <> '') AS SHIPPER_COMPLETE,
						(COALESCE(CONS_NAME,'') <> '' AND 
						COALESCE(CONS_ADDR1,'') <> '' AND 
						COALESCE(CONS_CITY,'') <> '' AND 
						COALESCE(CONS_STATE,'') <> '' AND 
						COALESCE(CONS_ZIP,'') <> '') AS CONS_COMPLETE,
						(COALESCE(PICKUP_DATE,'') <> '' AND 
						COALESCE(DELIVER_DATE,'') <> '') AS PICK_DEL,
						(COALESCE(BUSINESS_CODE,'') <> '') AS GOT_BC,
						(COALESCE((SELECT SAGE50_CLIENTID FROM EXP_CLIENT
						WHERE BILLTO_CLIENT_CODE = CLIENT_CODE),'') <> '') AS GOT_SAGEID,
                        (COALESCE((SELECT TOTAL FROM EXP_CLIENT_BILLING
						WHERE SHIPMENT_ID = shipment_code),'') > 0) AS GOT_BILLING,
						BILLTO_NAME" );

					$ok_to_continue =  is_array($check) && count($check) > 0 && 
						isset($check[0][$this->primary_key]) && $check[0][$this->primary_key] == $pk &&
						isset($check[0]["BILL_TO_COMPLETE"]) && $check[0]["BILL_TO_COMPLETE"] == 1;
						
					if( $ok_to_continue ) {
						$ok_to_continue =  isset($check[0]["ON_CREDIT_HOLD"]) &&
							$check[0]["ON_CREDIT_HOLD"] == 0;
							
						if( $ok_to_continue ) {
							$ok_to_continue =  isset($check[0]["SHIPPER_COMPLETE"]) &&
								$check[0]["SHIPPER_COMPLETE"] == 1;
						
							if( $ok_to_continue ) {
								$ok_to_continue =  isset($check[0]["CONS_COMPLETE"]) &&
									$check[0]["CONS_COMPLETE"] == 1;
							
								if( $ok_to_continue ) {
									$ok_to_continue =  isset($check[0]["PICK_DEL"]) &&
										$check[0]["PICK_DEL"] == 1;
								
									if( $ok_to_continue ) {
										$ok_to_continue =  ! $this->export_sage50 ||
											(isset($check[0]["GOT_BC"]) &&
											$check[0]["GOT_BC"] == 1);
								
										if( $ok_to_continue ) {
											$ok_to_continue =  ! $this->export_sage50 ||
												(isset($check[0]["GOT_SAGEID"]) &&
												$check[0]["GOT_SAGEID"] == 1);
									
											if( $ok_to_continue ) {					
												$ok_to_continue =  isset($check[0]["GOT_BILLING"]) &&
													$check[0]["GOT_BILLING"] == 1;
												
												if( ! $ok_to_continue )
													$this->state_change_error = 'No billing information is available. Try going back to the billing page and click on <span class="label label-success">Save Client Billing</span>';
											} else
												$this->state_change_error = 'The bill-to client needs to have a Sage ID (edit client profile) before you can export to Sage 50.';
										} else
											$this->state_change_error = 'Needs a business code (edit shipment) set before you can export to Sage 50.';
									} else
										$this->state_change_error = 'Needs a pickup date and deliver date.';
								} else
									$this->state_change_error = 'Needs complete consignee name and address, city, state, zip. Try clicking on the edit button below, and make sure the consignee information is complete.';
							} else
								$this->state_change_error = 'Needs complete shipper name and address, city, state, zip. Try clicking on the edit button below, and make sure the shipper information is complete.';
						} else
							$this->state_change_error = 'The bill_to client '.(isset($check[0]["BILLTO_NAME"]) ? $check[0]["BILLTO_NAME"].' ' : '').'is on credit hold.';
					} else
						$this->state_change_error = 'Needs complete bill_to name and address, city, state, zip. Try clicking on the edit button below, and make sure the bill_to information is complete.';
				} else {
					if( $this->billing_state_behavior[$state] == 'oapproved' )
						$this->state_change_error = 'Needs multi-company enabled and your account to be in the billing group.';
					else
						$this->state_change_error = 'Needs SHIPMENT_APPROVE_OPERATIONS enabled and your account to be in the billing group.';
				}
				
				break;

			case 'approved':	//! approved - finance approved
				$ok_to_continue = in_group(EXT_GROUP_FINANCE);
				
				if( $ok_to_continue ) {
					$check = $this->fetch_rows("BILLING_STATUS <> ".$state." AND ".
						//! SCR# 504 - if cancelled, don't approve
						"CURRENT_STATUS <> ".$this->behavior_state['cancel']." AND ".
						$this->primary_key." = ".$pk, 
						$this->primary_key.", (COALESCE(BILLTO_NAME,'') <> '' AND 
						COALESCE(BILLTO_ADDR1,'') <> '' AND 
						COALESCE(BILLTO_CITY,'') <> '' AND 
						COALESCE(BILLTO_STATE,'') <> '' AND 
						COALESCE(BILLTO_ZIP,'') <> '') AS BILL_TO_COMPLETE,
						COALESCE((SELECT ON_CREDIT_HOLD FROM EXP_CLIENT
						WHERE CLIENT_CODE = BILLTO_CLIENT_CODE),0) AS ON_CREDIT_HOLD,
						(COALESCE(SHIPPER_NAME,'') <> '' AND 
						COALESCE(SHIPPER_ADDR1,'') <> '' AND 
						COALESCE(SHIPPER_CITY,'') <> '' AND 
						COALESCE(SHIPPER_STATE,'') <> '' AND 
						COALESCE(SHIPPER_ZIP,'') <> '') AS SHIPPER_COMPLETE,
						(COALESCE(CONS_NAME,'') <> '' AND 
						COALESCE(CONS_ADDR1,'') <> '' AND 
						COALESCE(CONS_CITY,'') <> '' AND 
						COALESCE(CONS_STATE,'') <> '' AND 
						COALESCE(CONS_ZIP,'') <> '') AS CONS_COMPLETE,
						(COALESCE(PICKUP_DATE,'') <> '' AND 
						COALESCE(DELIVER_DATE,'') <> '') AS PICK_DEL,
						(COALESCE(BUSINESS_CODE,'') <> '') AS GOT_BC,
						(COALESCE((SELECT SAGE50_CLIENTID FROM EXP_CLIENT
						WHERE BILLTO_CLIENT_CODE = CLIENT_CODE),'') <> '') AS GOT_SAGEID,
                        (COALESCE((SELECT TOTAL FROM EXP_CLIENT_BILLING
						WHERE SHIPMENT_ID = shipment_code),'') > 0) AS GOT_BILLING,
						COALESCE(CONSOLIDATE_NUM, ".$pk.") AS CONSOLIDATE_NUM,
						BILLTO_NAME" );

					$ok_to_continue =  is_array($check) && count($check) > 0 && 
						isset($check[0][$this->primary_key]) && $check[0][$this->primary_key] == $pk &&
						isset($check[0]["BILL_TO_COMPLETE"]) && $check[0]["BILL_TO_COMPLETE"] == 1;
						
					if( $ok_to_continue ) {
						$ok_to_continue =  isset($check[0]["ON_CREDIT_HOLD"]) &&
							$check[0]["ON_CREDIT_HOLD"] == 0;

						if( $ok_to_continue ) {
							$ok_to_continue =  isset($check[0]["SHIPPER_COMPLETE"]) &&
								$check[0]["SHIPPER_COMPLETE"] == 1;
												
							if( $ok_to_continue ) {
								$ok_to_continue =  isset($check[0]["CONS_COMPLETE"]) &&
									$check[0]["CONS_COMPLETE"] == 1;
							
								if( $ok_to_continue ) {
									$ok_to_continue =  isset($check[0]["PICK_DEL"]) &&
										$check[0]["PICK_DEL"] == 1;
								
									if( $ok_to_continue ) {
										$ok_to_continue =  ! $this->export_sage50 ||
											(isset($check[0]["GOT_BC"]) &&
											$check[0]["GOT_BC"] == 1);
								
										if( $ok_to_continue ) {
											$ok_to_continue =  ! $this->export_sage50 ||
												(isset($check[0]["GOT_SAGEID"]) &&
												$check[0]["GOT_SAGEID"] == 1);
									
											if( $ok_to_continue ) {
												//! Check consolidated shipments
												$ok_to_continue = isset($check[0]["CONSOLIDATE_NUM"]) &&
													$check[0]["CONSOLIDATE_NUM"] == $pk;
							
												if( $ok_to_continue ) {					
													$ok_to_continue =  isset($check[0]["GOT_BILLING"]) &&
														$check[0]["GOT_BILLING"] == 1;
													
													if( ! $ok_to_continue )
														$this->state_change_error = 'No billing information is available. Try going back to the billing page and click on <span class="label label-success">Save Client Billing</span>';
												} else
													$this->state_change_error = 'This shipment is consolidated with shipment '.$check[0]["CONSOLIDATE_NUM"];
				
											} else
												$this->state_change_error = 'The bill-to client needs to have a Sage ID (edit client profile) before you can export to Sage 50.';
										} else
											$this->state_change_error = 'Needs a business code (edit shipment) set before you can export to Sage 50.';
									} else
										$this->state_change_error = 'Needs a pickup date and deliver date.';
								} else
									$this->state_change_error = 'Needs complete consignee name and address, city, state, zip. Try clicking on the edit button below, and make sure the consignee information is complete.';
							} else
								$this->state_change_error = 'Needs complete shipper name and address, city, state, zip. Try clicking on the edit button below, and make sure the shipper information is complete.';
						} else
							$this->state_change_error = 'The bill_to client '.(isset($check[0]["BILLTO_NAME"]) ? $check[0]["BILLTO_NAME"].' ' : '').'is on credit hold.';
					} else
						$this->state_change_error = 'Needs complete bill_to name and address, city, state, zip. Try clicking on the edit button below, and make sure the bill_to information is complete.';
				} else
					$this->state_change_error = 'Your account needs to be in the finance group.';
				
				//! Make sure consolidated shipments are in same state
				if( $ok_to_continue ) {
					$check2 = $this->fetch_rows("CONSOLIDATE_NUM = ".$pk." AND
						BILLING_STATUS NOT IN(".$this->billing_behavior_state['entry'].", ".
						$this->billing_behavior_state['unapproved'].") AND
						SHIPMENT_CODE != ".$pk." AND BILLING_STATUS !=
						(SELECT BILLING_STATUS FROM EXP_SHIPMENT WHERE SHIPMENT_CODE = ".$pk.")" , "SHIPMENT_CODE, BILLING_STATUS");
					if( is_array($check2) && count($check2) > 0 ) {
						$ok_to_continue = false;
						$this->state_change_error = 'Consolidated Shipment(s) not in the same billing state.';
					}
				}

				//! Make sure consolidated shipments are in same state
				if( $ok_to_continue ) {
					$check2 = $this->fetch_rows("CONSOLIDATE_NUM = ".$pk." AND
						BILLING_STATUS NOT IN(".$this->billing_behavior_state['entry'].", ".
						$this->billing_behavior_state['unapproved'].") AND
						SHIPMENT_CODE != ".$pk." AND BILLING_STATUS !=
						(SELECT BILLING_STATUS FROM EXP_SHIPMENT WHERE SHIPMENT_CODE = ".$pk.")" , "SHIPMENT_CODE, BILLING_STATUS");
					if( is_array($check2) && count($check2) > 0 ) {
						$ok_to_continue = false;
						$this->state_change_error = 'Consolidated Shipment(s) not in the same billing state.';
					}
				}
				
				//! SCR# 597 - Make sure consolidated shipments have pickup/delivery dates
				if( $ok_to_continue ) {
					$check3 = $this->fetch_rows("CONSOLIDATE_NUM = ".$pk." AND
						(COALESCE(PICKUP_DATE,'') = '' OR 
						COALESCE(DELIVER_DATE,'') = '') AND
						SHIPMENT_CODE != ".$pk , "SHIPMENT_CODE");
					if( is_array($check3) && count($check3) > 0 ) {
						$ok_to_continue = false;
						$bad_shipments = array();
						foreach( $check3 as $row ) {
							$bad_shipments[] = $row["SHIPMENT_CODE"];
						}
						$this->state_change_error = 'Consolidated Shipment(s) '.implode(', ',$bad_shipments).' need a pickup date and deliver date.';
					}
				}
				
				//! Approval of EDI shipment
				if( $ok_to_continue ) {
					$check = $this->fetch_rows($this->primary_key." = ".$pk, "EDI_204_PRIMARY");
					if( is_array($check) && count($check) == 1 &&
						isset($check[0]["EDI_204_PRIMARY"]) &&
						$check[0]["EDI_204_PRIMARY"] <> '' &&
						intval($check[0]["EDI_204_PRIMARY"]) == intval($pk) ) {
					
						// Related EDI shipments should have the same state to proceed
						$check2 = $this->fetch_rows("EDI_204_PRIMARY = ".$pk." AND
							SHIPMENT_CODE <> ".$pk." AND
							BILLING_STATUS <> ".$current_state,
							"SHIPMENT_CODE, BILLING_STATUS");
						if( is_array($check2) && count($check2) > 0 ) {
							$ok_to_continue = false;
							$this->state_change_error = 'EDI Shipment(s) not in the same state.';
						}
					}
				}
					
				break;

			case 'unapproved':	//! unapproved
				$ok_to_continue = in_group(EXT_GROUP_FINANCE);
				
				if( $ok_to_continue ) {
					//! Check consolidated shipments
					$check2 = $this->fetch_rows($this->primary_key." = ".$pk, "COALESCE(CONSOLIDATE_NUM, ".$pk.") AS CONSOLIDATE_NUM" );
					$ok_to_continue = is_array($check2) && count($check2) == 1 &&
						isset($check2[0]["CONSOLIDATE_NUM"]) &&
						$check2[0]["CONSOLIDATE_NUM"] == $pk;
					
					if( ! $ok_to_continue )	
						$this->state_change_error = 'This shipment is consolidated with shipment '.$check2[0]["CONSOLIDATE_NUM"];
				} else
					$this->state_change_error = 'Your account needs to be in the finance group.';

				break;

			case 'billed':	//! billed
				if( $this->quickbooks_online ) {
					if( $this->export_quickbooks ) {
						$check = $this->fetch_rows("BILLING_STATUS <> ".$state." AND
							quickbooks_listid_customer <> '' AND 
							quickbooks_txnid_invoice <> '' AND ".
							$this->primary_key." = ".$pk, $this->primary_key);
						$ok_to_continue =  $check && $check[0][$this->primary_key] == $pk;
					} else
						$ok_to_continue = true;
					
					if( ! $ok_to_continue ) {
						$this->state_change_level = EXT_ERROR_ERROR;
						$this->state_change_error = "Needs quickbooks customer and transaction ids";
					}
				} else { // Sage 50
					$ok_to_continue = true;
				}
				break;

			default:
				$ok_to_continue = true;
				break;
		}
		
		if( $this->debug ) echo "<p>".__METHOD__.": return ".($ok_to_continue ? "true" : "false - ".$this->state_change_error )."</p>";
		return $ok_to_continue;
	}
	
	// Change billing state for shipment $pk to state $state
	// Optional params $comment and $cstate = current state
	public function billing_change_state( $pk, $state, $comment = false, $cstate = -1 ) {
		global $sts_qb_dsn, $sts_crm_dir, $sts_subdomain;
		
		if( $this->debug ) echo "<p>".__METHOD__.": $pk, $cstate -> $state ".
			(isset($this->billing_state_name[$state]) ? $this->billing_state_name[$state] : 'unknown')." / ".
			(isset($this->billing_state_behavior[$state]) ? $this->billing_state_behavior[$state] : 'unknown')."</p>";
		
		// Fetch current state
		$result = $this->fetch_rows( $this->primary_key.' = '.$pk, "BILLING_STATUS, LOAD_CODE,
			CHANGED_DATE, EDI_204_PRIMARY, SS_NUMBER,
			(SELECT CURRENT_STOP FROM EXP_LOAD 
				WHERE EXP_LOAD.LOAD_CODE = EXP_SHIPMENT.LOAD_CODE) AS CURRENT_STOP,
			(SELECT STOP_CODE FROM EXP_STOP, EXP_LOAD
				WHERE EXP_STOP.SHIPMENT = EXP_SHIPMENT.SHIPMENT_CODE
				AND EXP_LOAD.LOAD_CODE = EXP_SHIPMENT.LOAD_CODE
				AND EXP_STOP.LOAD_CODE = EXP_LOAD.LOAD_CODE
				AND EXP_STOP.SEQUENCE_NO = EXP_LOAD.CURRENT_STOP) AS CURRENT_STOP_CODE,
			(SELECT COUNT(*) SHIPMENTS_IN_LOAD
				FROM EXP_SHIPMENT X
				WHERE X.LOAD_CODE = EXP_SHIPMENT.LOAD_CODE) AS SHIPMENTS_IN_LOAD,
			(SELECT USERNAME from EXP_USER X
				WHERE X.USER_CODE = EXP_SHIPMENT.CHANGED_BY LIMIT 0 , 1) AS CHANGED_BY
" );
		
		if( is_array($result) && isset($result[0]) && isset($result[0]['BILLING_STATUS']) ) {
			$current_state = $result[0]['BILLING_STATUS'];
			$load_code = $result[0]['LOAD_CODE'];
			$shipments_in_load = $result[0]['SHIPMENTS_IN_LOAD'];
			$current_stop = $result[0]['CURRENT_STOP'];
			$current_stop_code = $result[0]['CURRENT_STOP_CODE'];
			$changed_by = isset($result[0]['CHANGED_BY']) ? $result[0]['CHANGED_BY'] : "unknown";
			$changed_date = isset($result[0]['CHANGED_DATE']) ? $result[0]['CHANGED_DATE'] : 0;
			$primary = isset($result[0]['EDI_204_PRIMARY']) ? $result[0]['EDI_204_PRIMARY'] : 0;
			$office_num = isset($result[0]['SS_NUMBER']) ? $result[0]['SS_NUMBER'] : 0;
			
			if( $this->debug ) echo "<p>".__METHOD__.": current_state = $current_state</p>";
			
			// !Check to see if the current state do not match.
			// This likely means a screen is out of date, and someone already updated the load.
			if( $cstate > 0 &&
				$cstate <> $current_state ) {
				$ok_to_continue = false;
				$this->state_change_level = EXT_ERROR_WARNING;
				$this->state_change_error = "Shipment information is out of date. Likely someone updated it before you could.<br>".
					" Shipment <strong>".$pk."</strong> was last updated by <strong>".$changed_by."</strong>".
					($changed_date <> 0 ? " on <strong>".date("m/d H:i", strtotime($changed_date))."</strong>" : "")."<br>
					This is not an error. You can view shipment $pk and check the history tab for full details.<br><br>".
					" (current_state = $current_state, given = $cstate)<br>"
					;
				
			} else {
				// Check the new state is a valid transition
				$following = $this->following_states( $current_state, 'shipment-bill' );
				$match = false;
				if( is_array($following) && count($following) > 0 ) {
					foreach( $following as $row ) {
						if( $row['CODE'] == $state ) $match = true;
					}
				}
				
				if( ! $match ) {
					$this->state_change_level = EXT_ERROR_ERROR;
					$this->state_change_error = "Shipment $pk cannot transition from ".$this->billing_state_name[$current_state]." to ".$this->billing_state_name[$state];
					$ok_to_continue = false;
				} else {
					// Preconditions for changing state
					$ok_to_continue = $this->billing_state_change_ok( $pk, $current_state, $state );
				}
			}
			
			if( $this->debug ) echo "<p>".__METHOD__.": 1 level = $this->state_change_level, error = $this->state_change_error</p>";
				
			if( $ok_to_continue ) {
				$this->state_change_error = "change_state after ok_to_continue";
				// Update the state to the new state
				$result = $this->update( $this->primary_key.' = '.$pk."
					AND BILLING_STATUS <> ".$state, 
					array( 'BILLING_STATUS' => $state ) );
				
				if( ! $result ) {
					$this->state_change_level = EXT_ERROR_ERROR;
					$this->state_change_error = "After update, error = ".$this->error();
				}
				
				$user_log = sts_user_log::getInstance($this->database, $this->debug);
				// Post state change triggers
				switch( $this->billing_state_behavior[$state] ) {
					case 'oapproved':	//! oapproved - office approved
					case 'oapproved2':	//! oapproved2 - operations approved
						$otype = $this->billing_state_behavior[$state] == 'oapproved' ?
							'Office' : 'Operations';

						//! EDI - update any related shipments
						if( $primary ) {
							$dummy = $this->update( 'EDI_204_PRIMARY = '.$pk.' AND
								'.$this->primary_key.' <> '.$pk,
								array('BILLING_STATUS' => $state), false );
						}

						//! Update consolidated shipments
						$dummy = $this->update( 'CONSOLIDATE_NUM = '.$pk.' AND
							'.$this->primary_key.' <> '.$pk,
							array('BILLING_STATUS' => $state), false );

						// Could notify finance here
						$user_log->log_event('finance', 'Approved ('.$otype.') shipment '.$pk.
							($this->multi_company ? ' / '.$office_num : ''));
						break;
					
					case 'approved':	//! approved - finance approved

						//! EDI - update any related shipments
						if( $primary ) {
							$dummy = $this->update( 'EDI_204_PRIMARY = '.$pk.' AND
								'.$this->primary_key.' <> '.$pk,
								array('BILLING_STATUS' => $state), false );
						}

						// Fix the SALES_PERSON
						if( $this->restrict_salesperson ) {
							$this->update( $pk.' AND SALES_PERSON IS NULL', [
								'-SALES_PERSON' => '(SELECT SALES_PERSON FROM EXP_CLIENT
								WHERE CLIENT_CODE = BILLTO_CLIENT_CODE)' ], false );
						}
				
						//! Update consolidated shipments
						$dummy = $this->update( 'CONSOLIDATE_NUM = '.$pk.' AND
							'.$this->primary_key.' <> '.$pk,
							array('BILLING_STATUS' => $state), false );

						if( $this->export_quickbooks ) {
							require_once( $sts_crm_dir."quickbooks-php-master/QuickBooks.php" );
							// Queue up the Quickbooks API request
							$Queue = new QuickBooks_WebConnector_Queue($sts_qb_dsn);
							$Queue->enqueue(QUICKBOOKS_QUERY_CUSTOMER, $pk);
							
							$manual_spawn = $this->setting_table->get( 'api', 'QUICKBOOKS_MANUAL_SPAWN' ) == 'true';
	
							if( $this->quickbooks_online && ! $manual_spawn )
								require_once( __DIR__."/../quickbooks-php-master".
									DIRECTORY_SEPARATOR."online".DIRECTORY_SEPARATOR."spawn_process.php" );
						} else if( $this->export_sage50 ) {
							$result = $this->update( $pk,
								array( "quickbooks_status_message" => "NULL",
									"quickbooks_txnid_invoice" => "NULL" ), false );
						}
						
						//! Update the columns in load table LOAD_REVENUE, LOAD_EXPENSE
						if( $load_code > 0 ) {
							$this->database->get_one_row("
								UPDATE EXP_LOAD    
								SET LOAD_REVENUE = LOAD_REVENUE_CUR( LOAD_CODE, CURRENCY ),
									LOAD_EXPENSE = LOAD_EXPENSE_CUR( LOAD_CODE, CURRENCY )
								WHERE LOAD_CODE = $load_code");
						}
						
						$user_log->log_event('finance', 'Approved (Finance) shipment '.$pk.
							($this->multi_company ? ' / '.$office_num : ''));
						break;

					case 'unapproved':	//! unapproved
						if( in_array($this->billing_state_behavior[$current_state],
							array('approved', 'billed') ) ) {
							$result = $this->update( $pk,
								[ "quickbooks_status_message" => "",
									"quickbooks_txnid_invoice" => "",
									"INVOICE_EMAIL_STATUS" => "unsent",
									"INVOICE_EMAIL_DATE" => "NULL"], false );
						}
						$user_log->log_event('finance', 'Unapproved shipment '.$pk.
							($this->multi_company ? ' / '.$office_num : ''));

						// fall through
					case 'billed':	//! billed
						if( $primary ) {	//! EDI - update any related shipments
							$dummy = $this->update( 'EDI_204_PRIMARY = '.$pk.' AND
								'.$this->primary_key.' <> '.$pk,
								array('BILLING_STATUS' => $state), false );
						}
						//! Update consolidated shipments
						$dummy = $this->update( 'CONSOLIDATE_NUM = '.$pk.' AND
							'.$this->primary_key.' <> '.$pk,
							array('BILLING_STATUS' => $state), false );

						if( $this->billing_state_behavior[$state] == 'billed')

						//! Update the columns in load table LOAD_REVENUE, LOAD_EXPENSE
						if( $load_code > 0 ) {
							$this->database->get_one_row("
								UPDATE EXP_LOAD    
								SET LOAD_REVENUE = LOAD_REVENUE_CUR( LOAD_CODE, CURRENCY ),
									LOAD_EXPENSE = LOAD_EXPENSE_CUR( LOAD_CODE, CURRENCY )
								WHERE LOAD_CODE = $load_code");
						}

						$user_log->log_event('finance', 'Billed shipment '.$pk.
							($this->multi_company ? ' / '.$office_num : ''));
						break;

					default:
						break;
				}

			} else {
			if( $this->debug ) echo "<p>".__METHOD__.": 2 level = $this->state_change_level, error = $this->state_change_error</p>";
				if( $this->debug ) echo "<p>".__METHOD__.": Not a valid state change. $current_state (".
					(isset($this->billing_state_name[$current_state]) ? $this->billing_state_name[$current_state] : 'unknown').") -> $state (".
					(isset($this->billing_state_name[$state]) ? $this->billing_state_name[$state] : 'unknown').")</p>";
				return false;
			}
		}
		if( ! $result ) {
			$dummy = $this->add_shipment_status( $pk, 'change_state: failed, ('.$current_state.' -> '.$state.') '.$this->state_change_error );
		}
		return $result;
	}

	public function state_change_ok( $pk, $current_state, $state ) {

		if( $this->debug ) echo "<p>sts_shipment > state_change_ok: pk = $pk, current_state = $current_state (".$this->state_behavior[$current_state]."), state = $state (".$this->state_behavior[$state].")</p>";
		$this->state_change_error = '';
		$this->state_change_level = EXT_ERROR_WARNING;	// Default to warning
		$ok_to_continue = false;
		
		// Preconditions for changing state
		switch( $this->state_behavior[$state] ) {
			case 'assign':	//! assign / Ready Dispatch
				$check = $this->fetch_rows("CURRENT_STATUS <> ".$state." AND
					SHIPPER_NAME <> '' AND 
					SHIPPER_ADDR1 <> '' AND 
					SHIPPER_CITY <> '' AND 
					SHIPPER_STATE <> '' AND 
					SHIPPER_ZIP <> '' AND 
					CONS_NAME <> '' AND 
					CONS_ADDR1 <> '' AND 
					CONS_CITY <> '' AND 
					CONS_STATE <> '' AND 
					CONS_ZIP <> '' AND ".
					$this->primary_key." = ".$pk, $this->primary_key);
				$ok_to_continue =  $check && $check[0][$this->primary_key] == $pk;
				
				if( $ok_to_continue ) {
					if( $this->require_business_code ) {
						$check2 = $this->fetch_rows( "BUSINESS_CODE IS NOT NULL AND ".
							$this->primary_key." = ".$pk, $this->primary_key);
						
						$ok_to_continue =  $check2 && $check2[0][$this->primary_key] == $pk;
						if( ! $ok_to_continue )
							$this->state_change_error = "Need to set a business code";
					}
				
					//! dispatched -> ready dispatch (when not yet picked)
					if( $ok_to_continue && 
						in_array($this->state_behavior[$current_state], array('dispatch', 'picked')) ) {
						$check = $this->fetch_rows($this->primary_key." = ".$pk, "LOAD_CODE,
							(SELECT DRIVER FROM EXP_LOAD 
								WHERE EXP_LOAD.LOAD_CODE = EXP_SHIPMENT.LOAD_CODE) AS DRIVER" );
						$ok_to_continue =  $check && $check[0]['DRIVER'] > 0;
						
						//! picked -> ready dispatch (when picked)
						if( $ok_to_continue && $this->state_behavior[$current_state] == 'picked' ) {
							$check = $this->fetch_rows($this->primary_key." = ".$pk, "LOAD_CODE,
								(SELECT CURRENT_STOP FROM EXP_LOAD 
									WHERE EXP_LOAD.LOAD_CODE = EXP_SHIPMENT.LOAD_CODE) AS CURRENT_STOP,
								(SELECT SEQUENCE_NO FROM EXP_STOP
									WHERE SHIPMENT = ".$pk."
									AND EXP_STOP.LOAD_CODE = EXP_SHIPMENT.LOAD_CODE
									AND STOP_TYPE = 'pick') AS PICK_STOP" );
							$ok_to_continue =  isset($check) &&
								isset($check[0]['CURRENT_STOP']) &&
								isset($check[0]['PICK_STOP']) &&
								$check[0]['CURRENT_STOP'] == ($check[0]['PICK_STOP'] + 1 );
							if( ! $ok_to_continue )
								$this->state_change_error = "Only works if just picked";
						} else
							$this->state_change_error = "Need a driver";
					}
				} else
					$this->state_change_error = "Needs complete shipper and consignee name and addresses";
				break;

			case 'dropped':	//! dropped
				if( in_array($this->state_behavior[$current_state], array('approved', 'billed') ) ) {
					$ok_to_continue = true;
				} else {
					$check = $this->fetch_rows($this->primary_key." = ".$pk, "LOAD_CODE,
						(SELECT CURRENT_STOP FROM EXP_LOAD 
							WHERE EXP_LOAD.LOAD_CODE = EXP_SHIPMENT.LOAD_CODE) AS CURRENT_STOP,
						(SELECT SEQUENCE_NO FROM EXP_STOP
							WHERE SHIPMENT = ".$pk."
							AND EXP_STOP.LOAD_CODE = EXP_SHIPMENT.LOAD_CODE
							AND STOP_TYPE = 'drop') AS DROP_STOP" );
					
					if( $this->debug ) {
						echo "<p>shipment/state_change_ok check= </p>
						<pre>";
						var_dump($check);
						echo "</pre>";
					}
	
					$ok_to_continue =  isset($check) &&
						isset($check[0]['CURRENT_STOP']) &&
						isset($check[0]['DROP_STOP']) &&
						$check[0]['CURRENT_STOP'] == $check[0]['DROP_STOP'];
					if( ! $ok_to_continue ) {
						$this->state_change_level = EXT_ERROR_ERROR;
						$this->state_change_error = "Stop_type <> drop";
					}
				}
				break;

			case 'docked':	//! docked
				$check = $this->fetch_rows($this->primary_key." = ".$pk, "LOAD_CODE,
					(SELECT CURRENT_STOP FROM EXP_LOAD 
						WHERE EXP_LOAD.LOAD_CODE = EXP_SHIPMENT.LOAD_CODE) AS CURRENT_STOP,
					(SELECT SEQUENCE_NO FROM EXP_STOP
						WHERE SHIPMENT = ".$pk."
						AND EXP_STOP.LOAD_CODE = EXP_SHIPMENT.LOAD_CODE
						AND STOP_TYPE = 'dropdock') AS DOCK_STOP" );
				
				if( $this->debug ) {
					echo "<p>shipment/state_change_ok check= </p>
					<pre>";
					var_dump($check);
					echo "</pre>";
				}

				$ok_to_continue =  isset($check) &&
					isset($check[0]['CURRENT_STOP']) &&
					isset($check[0]['DOCK_STOP']) &&
					$check[0]['CURRENT_STOP'] == $check[0]['DOCK_STOP'];
				if( ! $ok_to_continue ) {
					$this->state_change_level = EXT_ERROR_ERROR;
					$this->state_change_error = "Stop_type <> dropdock";
				}
				break;

			case 'approved':	//! approved - invalid
				$ok_to_continue = false;
				$this->state_change_error = 'State no longer valid';
				break;

			case 'unapproved':	//! unapproved - invalid
				$ok_to_continue = false;
				$this->state_change_error = 'State no longer valid';
				break;

			case 'billed':	//! billed - invalid
				$ok_to_continue = false;
				$this->state_change_error = 'State no longer valid';
				break;

			case 'cancel':	//! cancelled
				$ok_to_continue = $this->state_behavior[$current_state] <> 'cancel';
				if( ! $ok_to_continue ) {
					$this->state_change_error = "already cancelled";
				} else {
					$ok_to_continue = ! in_array($this->state_behavior[$current_state],
						array('dropped','approved','billed'));
					if( ! $ok_to_continue ) {
						$this->state_change_error = "complete, approved or paid";
					}					
				}
				break;

			default:
				$ok_to_continue = true;
				break;
		}
		
		if( $this->debug ) echo "<p>sts_shipment > state_change_ok: return ".($ok_to_continue ? "true" : "false")."</p>";
		return $ok_to_continue;
	}
	
	// Change state for shipment $pk to state $state
	// Optional params $comment and $cstate = current state
	public function change_state( $pk, $state, $comment = false, $cstate = -1 ) {
		global $sts_qb_dsn, $sts_crm_dir;
		
		if( $this->debug ) echo "<p>sts_shipment > change_state: $pk, $cstate -> $state ".
			(isset($this->state_name[$state]) ? $this->state_name[$state] : 'unknown')." / ".
			(isset($this->state_behavior[$state]) ? $this->state_behavior[$state] : 'unknown')."</p>";
		
		// Fetch current state
		$result = $this->fetch_rows( $this->primary_key.' = '.$pk, "CURRENT_STATUS, LOAD_CODE,
			CHANGED_DATE, EDI_204_PRIMARY, ST_NUMBER,
			(SELECT CURRENT_STOP FROM EXP_LOAD 
				WHERE EXP_LOAD.LOAD_CODE = EXP_SHIPMENT.LOAD_CODE) AS CURRENT_STOP,
			(SELECT STOP_CODE FROM EXP_STOP, EXP_LOAD
				WHERE EXP_STOP.SHIPMENT = EXP_SHIPMENT.SHIPMENT_CODE
				AND EXP_LOAD.LOAD_CODE = EXP_SHIPMENT.LOAD_CODE
				AND EXP_STOP.LOAD_CODE = EXP_LOAD.LOAD_CODE
				AND EXP_STOP.SEQUENCE_NO = EXP_LOAD.CURRENT_STOP) AS CURRENT_STOP_CODE,
			(SELECT COUNT(*) SHIPMENTS_IN_LOAD
				FROM EXP_SHIPMENT X
				WHERE X.LOAD_CODE = EXP_SHIPMENT.LOAD_CODE) AS SHIPMENTS_IN_LOAD,
			(SELECT USERNAME from EXP_USER X
				WHERE X.USER_CODE = EXP_SHIPMENT.CHANGED_BY LIMIT 0 , 1) AS CHANGED_BY
" );
		
		if( is_array($result) && isset($result[0]) && isset($result[0]['CURRENT_STATUS']) ) {
			$current_state = $result[0]['CURRENT_STATUS'];
			if( isset($result[0]['ST_NUMBER']) )
				$container =  $result[0]['ST_NUMBER'];
			$load_code = $result[0]['LOAD_CODE'];
			$shipments_in_load = $result[0]['SHIPMENTS_IN_LOAD'];
			$current_stop = $result[0]['CURRENT_STOP'];
			$current_stop_code = $result[0]['CURRENT_STOP_CODE'];
			$changed_by = isset($result[0]['CHANGED_BY']) ? $result[0]['CHANGED_BY'] : "unknown";
			$changed_date = isset($result[0]['CHANGED_DATE']) ? $result[0]['CHANGED_DATE'] : 0;
			$primary = isset($result[0]['EDI_204_PRIMARY']) ? $result[0]['EDI_204_PRIMARY'] : 0;
			
			if( $this->debug ) echo "<p>sts_shipment > change_state: current_state = $current_state</p>";
			
			// !Check to see if the current state do not match.
			// This likely means a screen is out of date, and someone already updated the load.
			if( $cstate > 0 &&
				$cstate <> $current_state ) {
				$ok_to_continue = false;
				$this->state_change_level = EXT_ERROR_WARNING;
				$this->state_change_error = "Shipment information is out of date. Likely someone updated it before you could.<br>".
					" Shipment <strong>".$pk."</strong> was last updated by <strong>".$changed_by."</strong>".
					($changed_date <> 0 ? " on <strong>".date("m/d H:i", strtotime($changed_date))."</strong>" : "")."<br>
					This is not an error. You can view shipment $pk and check the history tab for full details.<br><br>".
					" (current_state = $current_state, given = $cstate)<br>"
					;
				
			} else {
				// Check the new state is a valid transition
				$following = $this->following_states( $current_state, 'shipment' );
				$match = false;
				if( is_array($following) && count($following) > 0 ) {
					foreach( $following as $row ) {
						if( $row['CODE'] == $state ) $match = true;
					}
				}
				
				if( ! $match ) {
					$this->state_change_level = EXT_ERROR_ERROR;
					$this->state_change_error = "Shipment $pk cannot transition from ".$this->state_name[$current_state]." to ".$this->state_name[$state];
					$ok_to_continue = false;
				} else {
					// Preconditions for changing state
					$ok_to_continue = $this->state_change_ok( $pk, $current_state, $state );
				}
			}
			
			if( $this->debug ) echo "<p>sts_shipment > change_state: 1 level = $this->state_change_level, error = $this->state_change_error</p>";
				
			if( $ok_to_continue ) {
				$this->state_change_error = "change_state after ok_to_continue";
				// Update the state to the new state
				$result = $this->update( $this->primary_key.' = '.$pk."
					AND CURRENT_STATUS <> ".$state, 
					array( 'CURRENT_STATUS' => $state ) );
				
				if( ! $result ) {
					$this->state_change_level = EXT_ERROR_ERROR;
					$this->state_change_error = "After update, error = ".$this->error();
				}
				
				//! Post state change triggers
				switch( $this->state_behavior[$state] ) {
					case 'dispatch':	//! dispatch
						if( $this->state_behavior[$current_state] == 'docked') {
							$result = $this->update( $pk, array( "DOCKED_AT" => 0 ), false );
						}
						
						//! Update the columns in load table LOAD_REVENUE, LOAD_EXPENSE
						if( $load_code > 0 ) {
							$this->database->get_one_row("
								UPDATE EXP_LOAD    
								SET LOAD_REVENUE = LOAD_REVENUE_CUR( LOAD_CODE, CURRENCY ),
									LOAD_EXPENSE = LOAD_EXPENSE_CUR( LOAD_CODE, CURRENCY )
								WHERE LOAD_CODE = $load_code");
						}
						
						break;
					
					case 'picked': //! picked
					
						break;
					
					case 'dropped':	//! dropped
						$shipment_load_table = sts_shipment_load::getInstance($this->database, $this->debug);
						$shipment_load_table->add_link( $pk, $load_code );
						
						//! Update the columns in load table LOAD_REVENUE, LOAD_EXPENSE
						if( $load_code > 0 ) {
							$this->database->get_one_row("
								UPDATE EXP_LOAD    
								SET LOAD_REVENUE = LOAD_REVENUE_CUR( LOAD_CODE, CURRENCY ),
									LOAD_EXPENSE = LOAD_EXPENSE_CUR( LOAD_CODE, CURRENCY )
								WHERE LOAD_CODE = $load_code");
						}
						
						break;
					
					case 'docked':	//! docked
						// Add a link to the current load, so we can find our way back later.
						$shipment_load_table = sts_shipment_load::getInstance($this->database, $this->debug);
						$shipment_load_table->add_link( $pk, $load_code );
						
						$result = $this->update( $pk,
							array( "DOCKED_AT" => $current_stop_code,
								"LOAD_CODE" => 0 ), false );
						if( $this->dock_toggle_direction )
							$this->dock_toggle_head( $pk );

						break;
										
					case 'assign':	//! assign / Ready Dispatch
						if( $load_code > 0 ) {
							$load_table = sts_load::getInstance($this->database, $this->debug);
	
							// Remove this shipment from the load
							$dummy = $this->update( $pk, array( 'LOAD_CODE' => 0 ), false );
	
							// Remove stops for the shipment
							$stop_table = sts_stop::getInstance($this->database, $this->debug);
							$dummy = $stop_table->delete_row( "SHIPMENT = ".$pk );
							
							if( $shipments_in_load == 1 ) {
								// Cancel load
								$load_table->change_state_behavior( $load_code, 'cancel' );
							} else {
								$dummy = $stop_table->renumber( $load_code ); // Renumber stops
	
								// Fix current_stop
								$dummy = $load_table->fix_current_stop( $load_code );
							}
						}
							
						if( $this->state_behavior[$current_state] == 'imported' ) {
							// Should only match 1 shipment, or else should not do this.
							$check = $this->fetch_rows( 'EDI_204_PRIMARY = '.$pk, $this->primary_key);

							if( is_array($check) && count($check) == 1) {
								require_once( "sts_edi_trans_class.php" );
								$edi = sts_edi_trans::getInstance($this->database, $this->debug);
								// Accept the 204 via a 990
								$is_accepted =  $edi->accept_204( intval($pk) );
							}
						}
						break;
					
					case 'cancel':	//! cancelled
						if( $load_code > 0  ) {
							// Remove this shipment from the load
							$dummy = $this->update( $pk, array( 'LOAD_CODE' => 0 ), false );
	
							// Remove stops for the shipment
							$stop_table = sts_stop::getInstance($this->database, $this->debug);
							$dummy = $stop_table->delete_row( "SHIPMENT = ".$pk );
	
							// renumber stops in the load
							$dummy = $stop_table->renumber( $load_code ); // Renumber stops
	
							// Fix current_stop
							$load_table = sts_load::getInstance($this->database, $this->debug);
							$dummy = $load_table->fix_current_stop( $load_code );
						}
						
						//! SCR# 437 - optionally remove billing for cancelled shipments
						if( $this->clear_cancelled_billing ) {
							$dummy = $this->database->get_one_row("DELETE FROM EXP_CLIENT_BILLING
								WHERE SHIPMENT_ID = ".$pk );
						}
						
						if( $this->state_behavior[$current_state] == 'imported' ) {
							// Should only match 1 shipment, or else should not do this.
							$check = $this->fetch_rows( 'EDI_204_PRIMARY = '.$pk, $this->primary_key);

							if( is_array($check) && count($check) == 1) {
								require_once( "sts_edi_trans_class.php" );
								$edi = sts_edi_trans::getInstance($this->database, $this->debug);
								// Decline the 204 via a 990
								$is_declined =  $edi->decline_204( intval($pk) );
							}
						}
					default:
						break;
				}

			} else {
			if( $this->debug ) echo "<p>sts_shipment > change_state: 2 level = $this->state_change_level, error = $this->state_change_error</p>";
				if( $this->debug ) echo "<p>sts_shipment > change_state: Not a valid state change. $current_state (".
					(isset($this->state_name[$current_state]) ? $this->state_name[$current_state] : 'unknown').") -> $state (".
					(isset($this->state_name[$state]) ? $this->state_name[$state] : 'unknown').")</p>";
				return false;
			}
		}
		if( ! $result ) {
			$dummy = $this->add_shipment_status( $pk, 'change_state: failed, ('.$current_state.' -> '.$state.') '.$this->state_change_error );
		}
		return $result;
	}

	// Call change_state() after looking up behavior
	// Optional param $cstate = current state
	public function change_state_behavior( $pk, $behavior, $is_billing, $cstate = -1 ) {
		
		$this->state_change_error = "change_state_behavior";
		if( $this->debug ) echo "<p>".__METHOD__.": $pk, ".($is_billing ? 'billing' : 'status').", $cstate > $behavior</p>";
		if( $is_billing )
			return isset($this->billing_behavior_state[$behavior]) ? $this->billing_change_state( $pk, $this->billing_behavior_state[$behavior], false, $cstate ) : false;
		else
			return isset($this->behavior_state[$behavior]) ? $this->change_state( $pk, $this->behavior_state[$behavior], false, $cstate ) : false;
	}
	
	// Change all shipments in a certain load to a state after looking up behavior
	public function load_change_state( $load_code, $behavior ) {
		$result = false;
		
		if( $this->debug ) echo "<p>sts_shipment > load_change_state: $load_code, $behavior</p>";
		
		// Find state for the behavior
		$state = isset($this->behavior_state[$behavior]) ? $this->behavior_state[$behavior] : 0;
		assert( "$state > 0", "Unable to find $behavior status code" );

		// Fetch matching shipments
		$matching = $this->fetch_rows( "LOAD_CODE = ".$load_code, $this->primary_key );
		if( is_array($matching) && count($matching) > 0 ) {
			foreach( $matching as $row ) {
				$ship = $row[$this->primary_key];
				$result = $this->change_state( $ship, $state );
				if( ! $result ) {
					$dummy = $this->add_shipment_status( $ship, 'load_change_state: change state failed load='.$load_code.' shipment='.$ship.' state='.$state );
					break;
				}
			}
		}
		return $result;
	}
	
	public function cancel( $pk ) {
		
		return $this->change_state( $pk, $this->behavior_state['cancel'] );
	}
	
	public function ready_dispatch( $pk, $cstate = -1 ) {
		
		return $this->change_state( $pk, $this->behavior_state['assign'], false, $cstate );
	}
	
	// If the shipment $pk is docked and direction is back, toggle the direction to head.
	public function dock_toggle_head( $pk ) {
		$result = false;
		$check = $this->fetch_rows( $this->primary_key." = ".$pk, "CURRENT_STATUS, DIRECTION" );
		
		if( is_array($check) && count($check) == 1 && isset($check[0]["CURRENT_STATUS"]) && 
			$check[0]["CURRENT_STATUS"] == $this->behavior_state["docked"] &&
			isset($check[0]["DIRECTION"]) && $check[0]["DIRECTION"] == 'back' ) {
			$result = $this->update( $pk, array( 'DIRECTION' => 'head' ), false );
		}
				
		return $result;
	}
	
	public function rollup_totals( $pk ) {
	
		$result = false;
		$pallets = $pieces = $weight = 0;
		
		$totals = $this->database->get_multiple_rows("
			SELECT SUM(PALLETS) AS PALLETS, SUM(PIECES) AS PIECES, SUM(WEIGHT) AS WEIGHT
			FROM EXP_DETAIL 
			WHERE EXP_DETAIL.SHIPMENT_CODE = ".$pk);

		if( is_array($totals) && count($totals) > 0 ) {
			// Make sure each item exists. Do not update empty values.
			$changes = array();
			if( isset($totals[0]['PALLETS']) && $totals[0]['PALLETS'] > 0 )
				$pallets = $totals[0]['PALLETS'];
			if( isset($totals[0]['PIECES']) && $totals[0]['PIECES'] > 0 )
				$pieces = $totals[0]['PIECES'];
			if( isset($totals[0]['WEIGHT']) && $totals[0]['WEIGHT'] > 0 )
				$weight = $totals[0]['WEIGHT'];
		}
		$changes = array('PALLETS' => $pallets, 'PIECES' => $pieces, 'WEIGHT' => $weight);

		$result = $this->update( $pk, $changes, false );
		
		return $result;
	}

	public function totals( $pk ) {
		return $this->fetch_rows( $this->primary_key." = ".$pk, "PALLETS, PIECES, WEIGHT" );
	}

	public function get_osd_info( $pk ) {
		return $this->fetch_rows( $this->primary_key." = ".$pk, "BILLTO_NAME, BILLTO_CITY, BILLTO_STATE,
			SHIPPER_NAME, SHIPPER_CITY, SHIPPER_STATE, CONS_NAME, CONS_CITY, CONS_STATE, LOAD_CODE,
			PO_NUMBER, BOL_NUMBER, ST_NUMBER, FS_NUMBER,
			(SELECT DRIVER FROM EXP_LOAD 
				WHERE EXP_SHIPMENT.LOAD_CODE = EXP_LOAD.LOAD_CODE ) AS DRIVER,
			(SELECT TRAILER FROM EXP_LOAD 
				WHERE EXP_SHIPMENT.LOAD_CODE = EXP_LOAD.LOAD_CODE ) AS TRAILER,
			(SELECT UNIT_NUMBER AS TRAILER_NUMBER
				FROM EXP_TRAILER, EXP_LOAD 
				WHERE EXP_LOAD.TRAILER = EXP_TRAILER.TRAILER_CODE
				AND EXP_SHIPMENT.LOAD_CODE = EXP_LOAD.LOAD_CODE ) AS TRAILER_NUMBER,
			(SELECT concat_ws( ' ', FIRST_NAME , LAST_NAME ) AS DRIVER_NAME
				FROM EXP_DRIVER, EXP_LOAD
				WHERE EXP_LOAD.DRIVER = EXP_DRIVER.DRIVER_CODE
				AND EXP_SHIPMENT.LOAD_CODE = EXP_LOAD.LOAD_CODE) AS DRIVER_NAME,
			(SELECT CARRIER FROM EXP_LOAD 
				WHERE EXP_SHIPMENT.LOAD_CODE = EXP_LOAD.LOAD_CODE ) AS CARRIER,
			(SELECT CARRIER_NAME
				FROM EXP_CARRIER, EXP_LOAD
				WHERE EXP_LOAD.CARRIER = EXP_CARRIER.CARRIER_CODE
				AND EXP_SHIPMENT.LOAD_CODE = EXP_LOAD.LOAD_CODE) AS CARRIER_NAME,
			PALLETS, PIECES, WEIGHT" );
	}

	private function stop_off_number( $num ) {
		if( strpos($num, '-') === false ) {
			$new_num = $num.'-1';
		} else {
			$arr = explode('-', $num);
			if( count($arr) == 2 )
				$new_num = $arr[0].'-'.(intval($arr[1])+1);
			else
				$new_num = $num.'-1';
		}
		return $new_num;
	}
	
	public function tf( $n, $x ) {
		return $n.' = '.($x ? 'true' : 'false').', ';
	}
	
	//! SCR# 779 - Added checkbox values as parameters
	public function duplicate( $pk, $stop_off = false,
		$pos = false, $bol = false, $ref = false, $pick = false,
		$cust = false, $pconf = false, $dconf = false, $broker = false, $empty_drop = false ) {

		if( $this->debug ) echo "<p>shipment/duplicate pk = $pk</p>";
		
		$this->log_event(__METHOD__.": entry, pk = $pk, ".
			$this->tf( "stop_off", $stop_off ).
			$this->tf( "pos", $pos ).
			$this->tf( "bol", $bol ).
			$this->tf( "ref", $ref ).
			$this->tf( "pick", $pick ).
			$this->tf( "cust", $cust ).
			$this->tf( "pconf", $pconf ).
			$this->tf( "dconf", $dconf ).
			$this->tf( "broker", $broker ),
			$this->tf( "empty_drop", $empty_drop ), EXT_ERROR_DEBUG );
			
		// Get current record
		$current_record = $this->fetch_rows( $this->primary_key." = ".$pk );
		$row = $current_record[0];
		
		$new_row = array();
		$new_row["PRIORITY"] = $row['PRIORITY'];
		$new_row["QUOTE"] = $row['QUOTE'];
		
		//! SCR# 818 - restrict salesperson
		if( ! $this->restrict_salesperson ) {
			$new_row["SALES_PERSON"] = $row['SALES_PERSON'];
		}

		//! SCR# 446 - If bill-to is on hold, do not duplicate.
		$check = $this->database->get_one_row("SELECT ON_CREDIT_HOLD FROM EXP_CLIENT
			WHERE CLIENT_CODE = ".$row['BILLTO_CLIENT_CODE']);
		if( is_array($check) && isset($check["ON_CREDIT_HOLD"]) && $check["ON_CREDIT_HOLD"] == "0" ) {
			
			//! SCR# 581 - optionally update Bill-to information when duplicating a shipment
			$update_billto = $this->setting_table->get( 'option', 'SHIPMENT_DUP_UPDT_BILLTO' ) == 'true';
			
			if($update_billto) {

				$client_table = sts_client::getInstance($this->database, $this->debug);
				
				$bill_to = $client_table->get_billto_info( $row['BILLTO_CLIENT_CODE'] );

				if( is_array($bill_to) ) {
					$row2 = $bill_to[0];
				
					$new_row["BILLTO_NAME"] = $row2['CLIENT_NAME'];
					$new_row["BILLTO_ADDR1"] = $row2['ADDRESS'];
					if( isset($row2['ADDRESS2']) )		$new_row["BILLTO_ADDR2"] = $row2['ADDRESS2'];
					if( isset($row2['CITY']) )			$new_row["BILLTO_CITY"] = $row2['CITY'];
					if( isset($row2['STATE']) )			$new_row["BILLTO_STATE"] = $row2['STATE'];
					if( isset($row2['ZIP_CODE']) )		$new_row["BILLTO_ZIP"] = $row2['ZIP_CODE'];
					if( isset($row2['COUNTRY']) )		$new_row["BILLTO_COUNTRY"] = $row2['COUNTRY'];
					if( isset($row2['PHONE_OFFICE']) )	$new_row["BILLTO_PHONE"] = $row2['PHONE_OFFICE'];
					if( isset($row2['PHONE_EXT']) )		$new_row["BILLTO_EXT"] = $row2['PHONE_EXT'];
					if( isset($row2['PHONE_FAX']) )		$new_row["BILLTO_FAX"] = $row2['PHONE_FAX'];
					if( isset($row2['PHONE_CELL']) )	$new_row["BILLTO_CELL"] = $row2['PHONE_CELL'];
					if( isset($row2['CONTACT_NAME']) )	$new_row["BILLTO_CONTACT"] = $row2['CONTACT_NAME'];
					if( isset($row2['EMAIL']) )			$new_row["BILLTO_EMAIL"] = $row2['EMAIL'];
					$new_row["BILLTO_CLIENT_CODE"] = $row['BILLTO_CLIENT_CODE'];
				} else {
					$email = sts_email::getInstance($this->database, $this->debug);
					$email->send_alert(__METHOD__.": Unable to fetch Bill-to client info for ".
					$row['BILLTO_CLIENT_CODE'] );
				}
			} else {
				$new_row["BILLTO_NAME"] = $row['BILLTO_NAME'];
				$new_row["BILLTO_ADDR1"] = $row['BILLTO_ADDR1'];
				$new_row["BILLTO_ADDR2"] = $row['BILLTO_ADDR2'];
				$new_row["BILLTO_CITY"] = $row['BILLTO_CITY'];
				$new_row["BILLTO_STATE"] = $row['BILLTO_STATE'];
				$new_row["BILLTO_ZIP"] = $row['BILLTO_ZIP'];
				$new_row["BILLTO_COUNTRY"] = $row['BILLTO_COUNTRY'];
				$new_row["BILLTO_PHONE"] = $row['BILLTO_PHONE'];
				$new_row["BILLTO_EXT"] = $row['BILLTO_EXT'];
				$new_row["BILLTO_FAX"] = $row['BILLTO_FAX'];
				$new_row["BILLTO_CELL"] = $row['BILLTO_CELL'];
				$new_row["BILLTO_CONTACT"] = $row['BILLTO_CONTACT'];
				$new_row["BILLTO_EMAIL"] = $row['BILLTO_EMAIL'];
				$new_row["BILLTO_CLIENT_CODE"] = $row['BILLTO_CLIENT_CODE'];
			}

			//! SCR# 721 - optionally update sales person when duplicating a shipment
			$update_salesperson = $this->setting_table->get( 'option', 'SHIPMENT_DUP_UPDT_SALES' ) == 'true';
			
			if( $update_salesperson ) {
				$client_table = sts_client::getInstance($this->database, $this->debug);
				
				$salesperson = $client_table->fetch_rows(
					"CLIENT_CODE = ".$row['BILLTO_CLIENT_CODE'],
					"SALES_PERSON" );
				if( is_array($salesperson) && count($salesperson) == 1 &&
					isset($salesperson[0]["SALES_PERSON"]))
					$new_row["SALES_PERSON"] = $salesperson[0]["SALES_PERSON"];
			}

		}

		if( $broker ) {
			$new_row["BROKER_CHOICE"] = $row['BROKER_CHOICE'];
			$new_row["BROKER_NAME"] = $row['BROKER_NAME'];
			$new_row["BROKER_ADDR1"] = $row['BROKER_ADDR1'];
			$new_row["BROKER_ADDR2"] = $row['BROKER_ADDR2'];
			$new_row["BROKER_CITY"] = $row['BROKER_CITY'];
			$new_row["BROKER_STATE"] = $row['BROKER_STATE'];
			$new_row["BROKER_ZIP"] = $row['BROKER_ZIP'];
			$new_row["BROKER_COUNTRY"] = $row['BROKER_COUNTRY'];
			$new_row["BROKER_PHONE"] = $row['BROKER_PHONE'];
			$new_row["BROKER_EXT"] = $row['BROKER_EXT'];
			$new_row["BROKER_FAX"] = $row['BROKER_FAX'];
			$new_row["BROKER_CELL"] = $row['BROKER_CELL'];
			$new_row["BROKER_CONTACT"] = $row['BROKER_CONTACT'];
			$new_row["BROKER_EMAIL"] = $row['BROKER_EMAIL'];
		}

		$new_row["SHIPPER_NAME"] = $row['SHIPPER_NAME'];
		$new_row["SHIPPER_ADDR1"] = $row['SHIPPER_ADDR1'];
		$new_row["SHIPPER_ADDR2"] = $row['SHIPPER_ADDR2'];
		$new_row["SHIPPER_CITY"] = $row['SHIPPER_CITY'];
		$new_row["SHIPPER_STATE"] = $row['SHIPPER_STATE'];
		$new_row["SHIPPER_ZIP"] = $row['SHIPPER_ZIP'];
		$new_row["SHIPPER_COUNTRY"] = $row['SHIPPER_COUNTRY'];
		$new_row["SHIPPER_PHONE"] = $row['SHIPPER_PHONE'];
		$new_row["SHIPPER_EXT"] = $row['SHIPPER_EXT'];
		$new_row["SHIPPER_FAX"] = $row['SHIPPER_FAX'];
		$new_row["SHIPPER_CELL"] = $row['SHIPPER_CELL'];
		$new_row["SHIPPER_CONTACT"] = $row['SHIPPER_CONTACT'];
		$new_row["SHIPPER_EMAIL"] = $row['SHIPPER_EMAIL'];
		$new_row["SHIPPER_TERMINAL"] = $row['SHIPPER_TERMINAL'];
		$new_row["SHIPPER_CLIENT_CODE"] = $row['SHIPPER_CLIENT_CODE'];

		$new_row["CONS_NAME"] = $row['CONS_NAME'];
		$new_row["CONS_ADDR1"] = $row['CONS_ADDR1'];
		$new_row["CONS_ADDR2"] = $row['CONS_ADDR2'];
		$new_row["CONS_CITY"] = $row['CONS_CITY'];
		$new_row["CONS_STATE"] = $row['CONS_STATE'];
		$new_row["CONS_ZIP"] = $row['CONS_ZIP'];
		$new_row["CONS_COUNTRY"] = $row['CONS_COUNTRY'];
		$new_row["CONS_PHONE"] = $row['CONS_PHONE'];
		$new_row["CONS_EXT"] = $row['CONS_EXT'];
		$new_row["CONS_FAX"] = $row['CONS_FAX'];
		$new_row["CONS_CELL"] = $row['CONS_CELL'];
		$new_row["CONS_CONTACT"] = $row['CONS_CONTACT'];
		$new_row["CONS_EMAIL"] = $row['CONS_EMAIL'];
		$new_row["CONS_TERMINAL"] = $row['CONS_TERMINAL'];
		$new_row["CONS_CLIENT_CODE"] = $row['CONS_CLIENT_CODE'];

		$new_row["BILLTO_VALID"] = $row['BILLTO_VALID'];
		$new_row["BILLTO_CODE"] = $row['BILLTO_CODE'];
		$new_row["BILLTO_DESCR"] = $row['BILLTO_DESCR'];
		$new_row["BILLTO_VALID_SOURCE"] = $row['BILLTO_VALID_SOURCE'];
		$new_row["BILLTO_LAT"] = $row['BILLTO_LAT'];
		$new_row["BILLTO_LON"] = $row['BILLTO_LON'];

		$new_row["BROKER_VALID"] = $row['BROKER_VALID'];
		$new_row["BROKER_CODE"] = $row['BROKER_CODE'];
		$new_row["BROKER_DESCR"] = $row['BROKER_DESCR'];
		$new_row["BROKER_VALID_SOURCE"] = $row['BROKER_VALID_SOURCE'];
		$new_row["BROKER_LAT"] = $row['BROKER_LAT'];
		$new_row["BROKER_LON"] = $row['BROKER_LON'];

		$new_row["SHIPPER_VALID"] = $row['SHIPPER_VALID'];
		$new_row["SHIPPER_CODE"] = $row['SHIPPER_CODE'];
		$new_row["SHIPPER_DESCR"] = $row['SHIPPER_DESCR'];
		$new_row["SHIPPER_VALID_SOURCE"] = $row['SHIPPER_VALID_SOURCE'];
		$new_row["SHIPPER_LAT"] = $row['SHIPPER_LAT'];
		$new_row["SHIPPER_LON"] = $row['SHIPPER_LON'];

		$new_row["CONS_VALID"] = $row['CONS_VALID'];
		$new_row["CONS_CODE"] = $row['CONS_CODE'];
		$new_row["CONS_DESCR"] = $row['CONS_DESCR'];
		$new_row["CONS_VALID_SOURCE"] = $row['CONS_VALID_SOURCE'];
		$new_row["CONS_LAT"] = $row['CONS_LAT'];
		$new_row["CONS_LON"] = $row['CONS_LON'];

		$new_row["DISTANCE"] = $row['DISTANCE'];
		$new_row["DANGEROUS_GOODS"] = $row['DANGEROUS_GOODS'];
		$new_row["UN_NUMBERS"] = $row['UN_NUMBERS'];
		$new_row["HIGH_VALUE"] = $row['HIGH_VALUE'];
		
		//! SCR# 588 - Add setting to NOT carry over dates when duplicating shipments
		//! SCR# 603 - Duplicate shipment - current month copy over dates
		if( $this->dup_shipment_no_dates == 'false' || $stop_off ||
			($this->dup_shipment_no_dates == 'cmonth' &&
				date('Ym', strtotime($row['PICKUP_DATE'])) == date('Ym') ) ) {
			$new_row["PICKUP_DATE"] = $row['PICKUP_DATE'];
			$new_row["PICKUP_DATE2"] = $row['PICKUP_DATE2'];
			$new_row["PICKUP_TIME_OPTION"] = $row['PICKUP_TIME_OPTION'];
			$new_row["PICKUP_TIME1"] = $row['PICKUP_TIME1'];
			$new_row["PICKUP_TIME2"] = $row['PICKUP_TIME2'];
			$new_row["DELIVER_DATE"] = $row['DELIVER_DATE'];
			$new_row["DELIVER_DATE2"] = $row['DELIVER_DATE2'];
			$new_row["DELIVER_TIME_OPTION"] = $row['DELIVER_TIME_OPTION'];
			$new_row["DELIVER_TIME1"] = $row['DELIVER_TIME1'];
			$new_row["DELIVER_TIME2"] = $row['DELIVER_TIME2'];
			//$new_row["PICKUP_APPT"] = $row['PICKUP_APPT'];
			//$new_row["DELIVERY_APPT"] = $row['DELIVERY_APPT'];
		}
		$new_row["NOTES"] = $row['NOTES'];

		if( $pos || ($stop_off && $this->stopoff_copy_po) ) {
			$new_row["PO_NUMBER"] = $row['PO_NUMBER'];
			$new_row["PO_NUMBER2"] = $row['PO_NUMBER2'];
			$new_row["PO_NUMBER3"] = $row['PO_NUMBER3'];
			$new_row["PO_NUMBER4"] = $row['PO_NUMBER4'];
			$new_row["PO_NUMBER5"] = $row['PO_NUMBER5'];
		}
		if( $bol ) {
			$new_row["BOL_NUMBER"] = $row['BOL_NUMBER'];
		}
		
		//$new_row["ST_NUMBER"] = $row['ST_NUMBER'];
		//$new_row["FS_NUMBER"] = $row['FS_NUMBER'];

		if( $ref ) {
			$new_row["REF_NUMBER"] = $row['REF_NUMBER'];
		}
		if( $pick || ($stop_off && $this->stopoff_copy_pickup) ) {
			$new_row["PICKUP_NUMBER"] = $row['PICKUP_NUMBER'];
		}
		if( $cust || ($stop_off && $this->stopoff_copy_pickup) ) {
			$new_row["CUSTOMER_NUMBER"] = $row['CUSTOMER_NUMBER'];
		}
		if( $pconf ) {
			$new_row["PICKUP_APPT"] = $row['PICKUP_APPT'];
		}
		if( $dconf ) {
			$new_row["DELIVERY_APPT"] = $row['DELIVERY_APPT'];
		}
		
		//! SCR# 852 - Containers Feature
		if( $empty_drop && $this->containers ) {
			$new_row['IM_EMPTY_DROP'] = $row['IM_EMPTY_DROP'];
		}
		
		$new_row["REEFER_OPS"] = $row['REEFER_OPS'];
		$new_row["INVOICE_NUM"] = $row['INVOICE_NUM'];
		$new_row["DIRECTION"] = $row['DIRECTION'];
		$new_row["BUSINESS_CODE"] = $row['BUSINESS_CODE'];
		
		$new_row["PIECES"] = $row["PIECES"];
		$new_row["PIECES_UNITS"] = $row["PIECES_UNITS"];
		
		$new_row["CDN_TAX_EXEMPT"] = $row["CDN_TAX_EXEMPT"];
		$new_row["CDN_TAX_EXEMPT_REASON"] = $row["CDN_TAX_EXEMPT_REASON"];

		$behavior = $this->duplicate_ready_dispatch ? 'assign' : 'entry';
		$new_row['CURRENT_STATUS'] = $this->behavior_state[$behavior];
		$new_row['BILLING_STATUS'] = $this->billing_behavior_state['entry'];
		
		$new_row['LOAD_CODE'] = "0";
		
		$multi_company = 
			($this->setting_table->get( 'option', 'MULTI_COMPANY' ) == 'true');
		//! Add serial number by office
		if( $multi_company ) {
			$new_row["OFFICE_CODE"] = $row['OFFICE_CODE'];
			
			//! SCR# 910 - Reuse office numbers from cancelled shipment on duplicate
			$check_used = $this->fetch_rows( "SS_NUMBER = '".$row['SS_NUMBER']."' AND CURRENT_STATUS != ".$this->behavior_state['cancel'], "SHIPMENT_CODE" );
			$already_used = is_array($check_used) && count($check_used) > 0;

			if( $this->debug ) {
				echo "<pre>".__METHOD__.": RESUSE CHECK\n";
				var_dump($this->dup_reuse_officenum, $already_used);
				var_dump($check_used);
				echo "</pre>";
			}
			
			if( $this->dup_reuse_officenum && ! $already_used &&
				$row["CURRENT_STATUS"] == $this->behavior_state['cancel'] ) {
				$new_row['SS_NUMBER'] = $row['SS_NUMBER'];
			} else
			
			if( $stop_off ) {
				$new_row['SS_NUMBER'] = $this->stop_off_number( $row['SS_NUMBER'] );
			} else {
				//! SCR# 448 - use the office number from the previous shipment
				$new_row['-SS_NUMBER'] = 'SS_SERIAL('.$row['OFFICE_CODE'].')';
			}
		}

		$new_pk = $this->add( $new_row );
		if( $new_pk ) {
			$dummy = $this->add_shipment_status( $new_pk, 'duplicate of shipment '.$pk );
			if( ! $stop_off ) {
				$detail_table = sts_detail::getInstance($this->database, $this->debug);
				
				$detail_table->duplicate( $pk, $new_pk );
			}
		}
		
		//! SCR# 267 - duplicate freight charges
		//! SCR# 290 - include the total to match the freight charges
		if( $this->duplicate_freight ) {
			$check = $this->database->get_one_row("SELECT FREIGHT_CHARGES FROM
			EXP_CLIENT_BILLING WHERE SHIPMENT_ID = ".$pk);
			
			if( is_array($check) && isset($check["FREIGHT_CHARGES"]) && $check["FREIGHT_CHARGES"] > 0 ) {
				$this->database->get_one_row("INSERT INTO
					EXP_CLIENT_BILLING( SHIPMENT_ID, FREIGHT_CHARGES, TOTAL )
					VALUES( ".$new_pk.", ".$check["FREIGHT_CHARGES"].",
						".$check["FREIGHT_CHARGES"]." )");
			}
		}
		
		if( $this->debug ) echo "<p>shipment/duplicate new_pk = $new_pk</p>";
		$this->log_event(__METHOD__.": exit, new_pk = $new_pk", EXT_ERROR_DEBUG );
		return $new_pk;
	}
	
	public function billing_confirm( $pk, $behavior ) {
		if( $this->debug ) echo "<p>".__METHOD__.": $pk, $behavior</p>";
		$destination = $this->export_quickbooks ? 'Quickbooks' : 'Accounting';
		switch( $behavior ) {
			case 'approved':
				$out = 'Do you really want to approve this for billing?<br>This sends the bill to '.$destination.'. Before you do, check the following:<br><br>You need at least a complete shipper, consignee and bill-to<br>You also need pickup and delivery dates and completed billing information.<br>'.($this->export_sage50 ? 'For Sage 50, you need a business code, and the bill-to client needs a Sage ID.<br>' : '').'<br>It also makes the shipment details read only, so be sure the shipment information is complete first.';
				break;

			case 'oapproved':
				$out = 'Do you really want to approve this at the office level for billing?<br>This sends the bill to finance. Before you do, check the following:<br><br>You need at least a complete shipper, consignee and bill-to<br>You also need pickup and delivery dates and completed billing information.<br>'.($this->export_sage50 ? 'For Sage 50, you need a business code, and the bill-to client needs a Sage ID.<br>' : '').'<br>It also makes the shipment details read only, so be sure the shipment information is complete first.';
				break;

			case 'unapproved':
				$out = 'Unapprove '.$pk.'?<br>This returns the shipment to the Unapproved state.<br>You can then edit &amp; make adjustments before you approve again.';
				break;
			default:
				$out = '';
		}
		return $out;
	}
	
	//! SCR# 702 - Get equipment required for a shipment
	public function get_equipment_req( $shipment ) {
		if( $this->debug ) echo "<p>".__METHOD__.": $shipment</p>";
		$result = '';
		$check = $this->database->get_one_row("
			SELECT GROUP_CONCAT(L.ITEM ORDER BY 1 ASC SEPARATOR ', ') AS REQ
			FROM EXP_ITEM_LIST L, EXP_EQUIPMENT_REQ R
			WHERE R.SOURCE_TYPE = 'shipment'
			AND R.SOURCE_CODE = $shipment
			AND R.ITEM_CODE = L.ITEM_CODE
		");
		
		if( is_array($check) && isset($check["REQ"]))
			$result = $check["REQ"];
			
		return $result;
	}
		
	// Is this shipment ok to use find carrier
	public function business_code_ok( $code ) {
		$sts_find_carrier_bc = $this->setting_table->get( 'option', 'FIND_CARRIER_BC' );
		if( empty($sts_find_carrier_bc) ) {
			$find_carrier = true;
		} else {			
			$fc_bc = $this->database->get_one_row("
				select b.BC_NAME
				FROM EXP_BUSINESS_CODE b, EXP_SHIPMENT s
				where b.APPLIES_TO = 'shipment'
				AND b.BUSINESS_CODE = s.BUSINESS_CODE
				AND s.SHIPMENT_CODE = $code");
			$find_carrier = is_array($fc_bc) && count($fc_bc) == 1 &&
				isset($fc_bc["BC_NAME"]) &&
				in_array($fc_bc["BC_NAME"], explode(',', $sts_find_carrier_bc));
		}
		
		return 	$find_carrier;
	}

}

//! Form Specifications - For use with sts_form

$sts_form_addshipment_form = array(	//! $sts_form_addshipment_form
	'title' => '<img src="images/order_icon.png" alt="order_icon" height="24"> Shipment',
	'action' => 'exp_addshipment.php',
	'noautocomplete' => true,
	//'actionextras' => 'disabled',
	//'cancel' => 'exp_cancelshipment.php?CODE=%SHIPMENT_CODE%',
	//'cancelbutton' => 'Cancel Shipment',
	'name' => 'addshipment',
	'okbutton' => 'Save Changes',
	'saveadd' => 'Add Another',
	'buttons' => array( 
		array( 'label' => 'New Client', 'link' => 'exp_addclient.php?shipment',
		'button' => 'success', 'icon' => '<span class="glyphicon glyphicon-user"></span>' ),
		array( 'label' => 'Dup', 'modal' => 'dupshipment_modal',
			'button' => 'success', 'icon' => '<span class="glyphicon glyphicon-repeat"></span>' ),
		array( 'label' => 'Cancel Shipment', 'link' => 'exp_cancelshipment.php?CODE=%SHIPMENT_CODE%&CSTATE=%CURRENT_STATUS%',
		'button' => 'danger', 'icon' => '<span class="glyphicon glyphicon-remove"></span>',
		'confirm' => true ),
		array( 'label' => 'Ready Dispatch', 'link' => 'exp_assignshipment.php?CODE=%SHIPMENT_CODE%&CSTATE=%CURRENT_STATUS%',
		'button' => 'success', 'icon' => '<span class="glyphicon glyphicon-arrow-right"></span>',
		'confirm' => true )
	),
	'backbutton' => 'Back',
	'back' => 'exp_listshipment.php',
		'layout' => '
	<input name="LAST_CHANGED" id="LAST_CHANGED" type="hidden" value="%CHANGED_DATE%">
	<!-- 204_INFO_HERE -->
	<div class="form-group tighter">
		<div class="col-sm-4">
			<div class="form-group tighter">
				<label for="SHIPMENT_CODE" class="col-sm-5 control-label">#SHIPMENT_CODE#</label>
				<div class="col-sm-7">
					%SHIPMENT_CODE%
				</div>
				<label for="CURRENT_STATUS" class="col-sm-5 control-label">#CURRENT_STATUS#</label>
				<div class="col-sm-7">
					%CURRENT_STATUS%, %BILLING_STATUS%
				</div>
				
				<!-- PO1 -->
				<label for="PO_NUMBER" class="col-sm-5 control-label">#PO_NUMBER#</label>
				<div class="col-sm-7">
					%PO_NUMBER%
				</div>
				<label for="PO_NUMBER2" class="col-sm-5 control-label">#PO_NUMBER2#</label>
				<div class="col-sm-7">
					%PO_NUMBER2%
				</div>
				<label for="PO_NUMBER3" class="col-sm-5 control-label">#PO_NUMBER3#</label>
				<div class="col-sm-7">
					%PO_NUMBER3%
				</div>
				<label for="PO_NUMBER4" class="col-sm-5 control-label">#PO_NUMBER4#</label>
				<div class="col-sm-7">
					%PO_NUMBER4%
				</div>
				<label for="PO_NUMBER5" class="col-sm-5 control-label">#PO_NUMBER5#</label>
				<div class="col-sm-7">
					%PO_NUMBER5%
				</div>
				<!-- PO2 -->
			</div>
		</div>
		<div class="col-sm-4">
			<div class="form-group">
				<!-- CC01 -->
				<label for="OFFICE_CODE" class="col-sm-5 control-label">#OFFICE_CODE#</label>
				<div class="col-sm-7">
					%OFFICE_CODE%
				</div>
				<label for="SS_NUMBER" class="col-sm-5 control-label">#SS_NUMBER#</label>
				<div class="col-sm-7">
					%SS_NUMBER%
				</div>
				<!-- CC02 -->
				<label for="CONSOLIDATE_NUM" class="col-sm-5 control-label">#CONSOLIDATE_NUM#</label>
				<div class="col-sm-7">
					%CONSOLIDATE_NUM%
					<!-- CONS_SHIPMENTS -->
				</div>
				<label for="BOL_NUMBER" class="col-sm-5 control-label">#BOL_NUMBER#</label>
				<div class="col-sm-7">
					%BOL_NUMBER%
				</div>
				<!-- PIPCO1 -->
				<label for="FS_NUMBER" class="col-sm-5 control-label">#FS_NUMBER#</label>
				<div class="col-sm-7">
					%FS_NUMBER%
				</div>
				<!-- PIPCO2 -->
				<label for="REF_NUMBER" class="col-sm-5 control-label">#REF_NUMBER#</label>
				<div class="col-sm-7">
					%REF_NUMBER%
				</div>
				<label for="PICKUP_NUMBER" class="col-sm-5 control-label">#PICKUP_NUMBER#</label>
				<div class="col-sm-7">
					%PICKUP_NUMBER%
				</div>
				<label for="CUSTOMER_NUMBER" class="col-sm-5 control-label">#CUSTOMER_NUMBER#</label>
				<div class="col-sm-7">
					%CUSTOMER_NUMBER%
				</div>
				<label for="SALES_PERSON" class="col-sm-5 control-label">#SALES_PERSON#</label>
				<div class="col-sm-7">
					%SALES_PERSON%
				</div>
			</div>
		</div>
		<div class="col-sm-4">
			<div class="form-group">
				<label for="PRIORITY" class="col-sm-5 control-label">#PRIORITY#</label>
				<div class="col-sm-7">
					%PRIORITY%
				</div>
				<label for="LOAD_CODE" class="col-sm-5 control-label">#LOAD_CODE#</label>
				<div class="col-sm-7">
					%LOAD_CODE%
				</div>
				<label for="DIRECTION" class="col-sm-5 control-label">#DIRECTION#</label>
				<div class="col-sm-7">
					%DIRECTION%
				</div>
				<!-- BUSINESS_CODE1 -->
				<label for="BUSINESS_CODE" class="col-sm-5 control-label">#BUSINESS_CODE#</label>
				<div class="col-sm-7">
					%BUSINESS_CODE%
				</div>
				<!-- BUSINESS_CODE2 -->
				<label for="CALLER_NAME" class="col-sm-5 control-label">#CALLER_NAME#</label>
				<div class="col-sm-7">
					%CALLER_NAME%
				</div>
				<label for="CALLER_PHONE" class="col-sm-5 control-label">#CALLER_PHONE#</label>
				<div class="col-sm-7">
					%CALLER_PHONE%
				</div>
				<!-- INTERMODAL1 -->
				&nbsp;<br>
				<div class="panel panel-info">
					<div class="panel-heading">
						<h3 class="panel-title">Intermodal</h3>
					</div>
					<div class="panel-body">
						<label for="ST_NUMBER" class="col-sm-5 control-label">#ST_NUMBER#</label>
						<div class="col-sm-7">
							%ST_NUMBER%
						</div>
						<label for="IM_EMPTY_DROP" class="col-sm-5 control-label">#IM_EMPTY_DROP#</label>
						<div class="col-sm-7">
							%IM_EMPTY_DROP%
						</div>
					</div>
				</div>
				<!-- INTERMODAL2 -->
			</div>

		</div>
	</div>
	<div class="form-group tighter">
		<div class="col-sm-4">
			<div class="form-group tighter">
				<label for="PICKUP_APPT" class="col-sm-5 control-label">#PICKUP_APPT#</label>
				<div class="col-sm-7">
					%PICKUP_APPT%
				</div>
			</div>
		</div>
		<!-- 204_PICKUP1 -->
		<div class="col-sm-2">
			<div class="form-group tighter">
				<label class="col-sm-4 control-label">#PICKUP_DATE#</label>
				<div class="col-sm-8">
					%PICKUP_DATE%
				</div>
			</div>
		</div>
		<div class="col-sm-2">
			<div class="form-group tighter">
				<div class="col-sm-8">
					%PICKUP_TIME_OPTION%
				</div>
			</div>
		</div>
		<div class="col-sm-2">
			<div class="form-group tighter">
				<div id="PTIME1">
					<div class="col-sm-6 narrower">
						%PICKUP_TIME1%
					</div>
				</div>
				<div id="PTIME2">
					<div class="col-sm-6 narrower">
						%PICKUP_TIME2%
					</div>
				</div>
			</div>
		</div>
		<!-- 204_PICKUP2 -->
		<div class="col-sm-2">
			<span id="SHIPPER_TZONE" class="text-primary"><span class="glyphicon glyphicon-time"></span> %SHIPPER_TZONE%</span>
		</div>
	</div>
	<div class="form-group tighter">
		<div class="col-sm-4">
			<div class="form-group tighter">
				<label for="DELIVERY_APPT" class="col-sm-5 control-label">#DELIVERY_APPT#</label>
				<div class="col-sm-7">
					%DELIVERY_APPT%
				</div>
			</div>
		</div>
		<!-- 204_DELIVER1 -->
		<div class="col-sm-2">
			<div class="form-group tighter">
				<label class="col-sm-4 control-label">#DELIVER_DATE#</label>
				<div class="col-sm-8">
					%DELIVER_DATE%
				</div>
			</div>
		</div>
		<div class="col-sm-2">
			<div class="form-group tighter">
				<div class="col-sm-8">
					%DELIVER_TIME_OPTION%
				</div>
			</div>
		</div>
		<div class="col-sm-2">
			<div class="form-group tighter">
				<div id="DTIME1">
					<div class="col-sm-6 narrower">
						%DELIVER_TIME1%
					</div>
				</div>
				<div id="DTIME2">
					<div class="col-sm-6 narrower">
						%DELIVER_TIME2%
					</div>
				</div>
			</div>
		</div>
		<!-- 204_DELIVER2 -->
		<div class="col-sm-2">
			<span id="CONS_TZONE" class="text-primary"><span class="glyphicon glyphicon-time"></span> %CONS_TZONE%</span>
		</div>
	</div>
	<div class="form-group">
		<div class="col-sm-4">
			<div class="form-group well well-sm tighter">
				%SHIPPER_CLIENT_CODE%
				<label for="SHIPPER_NAME" class="col-sm-4 control-label">#SHIPPER_NAME#</label>
				<div class="col-sm-8">
					%SHIPPER_NAME%
				</div>
				<label for="SHIPPER_ADDR1" class="col-sm-4 control-label">%SHIPPER_VALID%&nbsp;#SHIPPER_ADDR1#</label>
				<div class="col-sm-8">
					%SHIPPER_ADDR1%
				</div>
				<label for="SHIPPER_ADDR2" class="col-sm-4 control-label">#SHIPPER_ADDR2#</label>
				<div class="col-sm-8">
					%SHIPPER_ADDR2%
				</div>
				<label for="SHIPPER_CITY" class="col-sm-4 control-label">#SHIPPER_CITY#</label>
				<div class="col-sm-8">
					%SHIPPER_CITY%
				</div>
				<label for="SHIPPER_STATE" class="col-sm-4 control-label">#SHIPPER_STATE#</label>
				<div class="col-sm-8">
					%SHIPPER_STATE%
				</div>
				<label for="SHIPPER_ZIP" class="col-sm-4 control-label">#SHIPPER_ZIP#</label>
				<div class="col-sm-8">
					%SHIPPER_ZIP%
				</div>
				<label for="SHIPPER_COUNTRY" class="col-sm-4 control-label">#SHIPPER_COUNTRY#</label>
				<div class="col-sm-8">
					%SHIPPER_COUNTRY%
				</div>
				<label for="SHIPPER_CONTACT" class="col-sm-4 control-label">#SHIPPER_CONTACT#</label>
				<div class="col-sm-8">
					%SHIPPER_CONTACT%
				</div>
				<label for="SHIPPER_PHONE" class="col-sm-4 control-label">#SHIPPER_PHONE#</label>
				<div class="col-sm-5">
					%SHIPPER_PHONE%
				</div>
				<div class="col-sm-3">
					%SHIPPER_EXT%
				</div>
				<label for="SHIPPER_FAX" class="col-sm-4 control-label">#SHIPPER_FAX#</label>
				<div class="col-sm-8">
					%SHIPPER_FAX%
				</div>
				<label for="SHIPPER_CELL" class="col-sm-4 control-label">#SHIPPER_CELL#</label>
				<div class="col-sm-8">
					%SHIPPER_CELL%
				</div>
				<label for="SHIPPER_EMAIL" class="col-sm-4 control-label">#SHIPPER_EMAIL#</label>
				<div class="col-sm-8">
					%SHIPPER_EMAIL%
				</div>
				
				<label for="SHIPPER_TERMINAL" class="col-sm-4 control-label" style="padding-left: 0px;"><a id="ADD_SHIPPER_CLIENT" class="btn btn-sm btn-default tip clearbutton" title="Quick Add Shipper"  style="float: left;"><span class="glyphicon glyphicon-plus"></span></a>#SHIPPER_TERMINAL#</label>
				<div class="col-sm-8">
					%SHIPPER_TERMINAL%
				</div>
			</div>
		</div>
		<div class="col-sm-4">
			<div class="form-group well well-sm tighter">
				%CONS_CLIENT_CODE%
				<label for="CONS_NAME" class="col-sm-4 control-label">#CONS_NAME#</label>
				<div class="col-sm-8">
					%CONS_NAME%
				</div>
				<label for="CONS_ADDR1" class="col-sm-4 control-label">%CONS_VALID%&nbsp;#CONS_ADDR1#</label>
				<div class="col-sm-8">
					%CONS_ADDR1%
				</div>
				<label for="CONS_ADDR2" class="col-sm-4 control-label">#CONS_ADDR2#</label>
				<div class="col-sm-8">
					%CONS_ADDR2%
				</div>
				<label for="CONS_CITY" class="col-sm-4 control-label">#CONS_CITY#</label>
				<div class="col-sm-8">
					%CONS_CITY%
				</div>
				<label for="CONS_STATE" class="col-sm-4 control-label">#CONS_STATE#</label>
				<div class="col-sm-8">
					%CONS_STATE%
				</div>
				<label for="CONS_ZIP" class="col-sm-4 control-label">#CONS_ZIP#</label>
				<div class="col-sm-8">
					%CONS_ZIP%
				</div>
				<label for="CONS_COUNTRY" class="col-sm-4 control-label">#CONS_COUNTRY#</label>
				<div class="col-sm-8">
					%CONS_COUNTRY%
				</div>
				<label for="CONS_CONTACT" class="col-sm-4 control-label">#CONS_CONTACT#</label>
				<div class="col-sm-8">
					%CONS_CONTACT%
				</div>
				<label for="CONS_PHONE" class="col-sm-4 control-label">#CONS_PHONE#</label>
				<div class="col-sm-5">
					%CONS_PHONE%
				</div>
				<div class="col-sm-3">
					%CONS_EXT%
				</div>
				<label for="CONS_FAX" class="col-sm-4 control-label">#CONS_FAX#</label>
				<div class="col-sm-8">
					%CONS_FAX%
				</div>
				<label for="CONS_CELL" class="col-sm-4 control-label">#CONS_CELL#</label>
				<div class="col-sm-8">
					%CONS_CELL%
				</div>
				<label for="CONS_EMAIL" class="col-sm-4 control-label">#CONS_EMAIL#</label>
				<div class="col-sm-8">
					%CONS_EMAIL%
				</div>
				<label for="CONS_TERMINAL" class="col-sm-4 control-label" style="padding-left: 0px;"><a id="ADD_CONS_CLIENT" class="btn btn-sm btn-default tip clearbutton" title="Quick Add Consignee" style="float: left;" ><span class="glyphicon glyphicon-plus"></span></a>#CONS_TERMINAL#</label>
				<div class="col-sm-8">
					%CONS_TERMINAL%
				</div>
			</div>
		</div>
		<div class="col-sm-4">
			<div class="form-group well well-sm tighter">
				<input name="BILLTO_NAME_VALID" id="BILLTO_NAME_SELECTED" type="hidden" value="">
				%BILLTO_CLIENT_CODE%
				<label for="BILLTO_NAME" id="BILLTO_LABEL" class="col-sm-4 control-label">#BILLTO_NAME#</label>
				<div class="col-sm-8">
					%BILLTO_NAME%
				</div>
				<label for="BILLTO_ADDR1" class="col-sm-4 control-label">%BILLTO_VALID%&nbsp;#BILLTO_ADDR1#</label>
				<div class="col-sm-8">
					%BILLTO_ADDR1%
				</div>
				<label for="BILLTO_ADDR2" class="col-sm-4 control-label">#BILLTO_ADDR2#</label>
				<div class="col-sm-8">
					%BILLTO_ADDR2%
				</div>
				<label for="BILLTO_CITY" class="col-sm-4 control-label">#BILLTO_CITY#</label>
				<div class="col-sm-8">
					%BILLTO_CITY%
				</div>
				<label for="BILLTO_STATE" class="col-sm-4 control-label">#BILLTO_STATE#</label>
				<div class="col-sm-8">
					%BILLTO_STATE%
				</div>
				<label for="BILLTO_ZIP" class="col-sm-4 control-label">#BILLTO_ZIP#</label>
				<div class="col-sm-8">
					%BILLTO_ZIP%
				</div>
				<label for="BILLTO_COUNTRY" class="col-sm-4 control-label">#BILLTO_COUNTRY#</label>
				<div class="col-sm-8">
					%BILLTO_COUNTRY%
				</div>
				<label for="BILLTO_CONTACT" class="col-sm-4 control-label">#BILLTO_CONTACT#</label>
				<div class="col-sm-8">
					%BILLTO_CONTACT%
				</div>
				<label for="BILLTO_PHONE" class="col-sm-4 control-label">#BILLTO_PHONE#</label>
				<div class="col-sm-5">
					%BILLTO_PHONE%
				</div>
				<div class="col-sm-3">
					%BILLTO_EXT%
				</div>
				<label for="BILLTO_FAX" class="col-sm-4 control-label">#BILLTO_FAX#</label>
				<div class="col-sm-8">
					%BILLTO_FAX%
				</div>
				<label for="BILLTO_CELL" class="col-sm-4 control-label">#BILLTO_CELL#</label>
				<div class="col-sm-8">
					%BILLTO_CELL%
				</div>
				<label for="BILLTO_EMAIL" class="col-sm-4 control-label">#BILLTO_EMAIL#</label>
				<div class="col-sm-8">
					%BILLTO_EMAIL%
				</div>
			</div>

			<div class="panel panel-danger" id="CDN_TAX" hidden>
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

			<div id="BROKER_PANE" class="form-group alert alert-warning tighter" hidden>
				<label for="BROKER_CHOICE" class="col-sm-4 control-label">#BROKER_CHOICE#</label>
				<div class="col-sm-8">
					%BROKER_CHOICE%
				</div>
				<label for="BROKER_NAME" class="col-sm-4 control-label">#BROKER_NAME#</label>
				<div class="col-sm-8">
					%BROKER_NAME%
				</div>
				<label for="BROKER_ADDR1" class="col-sm-4 control-label">%BROKER_VALID%&nbsp;#BROKER_ADDR1#</label>
				<div class="col-sm-8">
					%BROKER_ADDR1%
				</div>
				<label for="BROKER_ADDR2" class="col-sm-4 control-label">#BROKER_ADDR2#</label>
				<div class="col-sm-8">
					%BROKER_ADDR2%
				</div>
				<label for="BROKER_CITY" class="col-sm-4 control-label">#BROKER_CITY#</label>
				<div class="col-sm-8">
					%BROKER_CITY%
				</div>
				<label for="BROKER_STATE" class="col-sm-4 control-label">#BROKER_STATE#</label>
				<div class="col-sm-8">
					%BROKER_STATE%
				</div>
				<label for="BROKER_ZIP" class="col-sm-4 control-label">#BROKER_ZIP#</label>
				<div class="col-sm-8">
					%BROKER_ZIP%
				</div>
				<label for="BROKER_COUNTRY" class="col-sm-4 control-label">#BROKER_COUNTRY#</label>
				<div class="col-sm-8">
					%BROKER_COUNTRY%
				</div>
				<label for="BROKER_CONTACT" class="col-sm-4 control-label">#BROKER_CONTACT#</label>
				<div class="col-sm-8">
					%BROKER_CONTACT%
				</div>
				<label for="BROKER_PHONE" class="col-sm-4 control-label">#BROKER_PHONE#</label>
				<div class="col-sm-5">
					%BROKER_PHONE%
				</div>
				<div class="col-sm-3">
					%BROKER_EXT%
				</div>
				<label for="BROKER_FAX" class="col-sm-4 control-label">#BROKER_FAX#</label>
				<div class="col-sm-8">
					%BROKER_FAX%
				</div>
				<label for="BROKER_CELL" class="col-sm-4 control-label">#BROKER_CELL#</label>
				<div class="col-sm-8">
					%BROKER_CELL%
				</div>
				<label for="BROKER_EMAIL" class="col-sm-4 control-label">#BROKER_EMAIL#</label>
				<div class="col-sm-8">
					%BROKER_EMAIL%
				</div>
			</div>
		</div>
	</div>
	<div class="form-group well well-sm tighter">
		<div class="col-sm-4">
			<!-- EQUIPMENT -->
			<label for="REEFER_OPS" class="col-sm-4 control-label">#REEFER_OPS#</label>
			<div class="col-sm-8">
				%REEFER_OPS%
			</div>
			<label for="DANGEROUS_GOODS" class="col-sm-4 control-label">#DANGEROUS_GOODS#</label>
			<div class="col-sm-8">
				%DANGEROUS_GOODS%
			</div>
			<label for="UN_NUMBERS" class="col-sm-4 control-label">#UN_NUMBERS#</label>
			<div class="col-sm-8">
				%UN_NUMBERS%
			</div>
		</div>
		<div class="col-sm-8">
			<div class="form-group">
				<label for="NOTES" class="col-sm-2 control-label">#NOTES#</label>
				<div class="col-sm-10">
					%NOTES%
				</div>
			</div>
			<div class="form-group">
				<div class="col-sm-6">
					<label for="PALLETS" class="col-sm-4 control-label">#PALLETS#</label>
					<div class="col-sm-8">
						%PALLETS%
					</div>
					<label for="PIECES" class="col-sm-4 control-label">#PIECES#</label>
					<div class="col-sm-4">
						%PIECES%
					</div>
					<div class="col-sm-4">
						%PIECES_UNITS%
					</div>
					<label for="WEIGHT" class="col-sm-4 control-label">#WEIGHT#</label>
					<div class="col-sm-8">
						%WEIGHT%
					</div>
				</div>
				<div class="col-sm-6">
					<label for="DISTANCE" class="col-sm-4 control-label">#DISTANCE#</label>
					<div class="col-sm-8">
						%DISTANCE%
					</div>
					<label for="DISTANCE_SOURCE" class="col-sm-4 control-label">#DISTANCE_SOURCE#</label>
					<div class="col-sm-8">
						%DISTANCE_SOURCE%
					</div>
					<label for="DISTANCE_LAST_AT" class="col-sm-4 control-label">#DISTANCE_LAST_AT#</label>
					<div class="col-sm-8">
						%DISTANCE_LAST_AT%
					</div>
				</div>
			</div>

			<!-- SAGE501 -->
			<div class="panel panel-success APPROVED_ONLY">
				<div class="panel-heading">
					<h3 class="panel-title">Sage 50 Export Status</h3>
				</div>
				<div class="panel-body">
					<div class="form-group">
						<label for="quickbooks_status_message" class="col-sm-4 control-label">#quickbooks_status_message#</label>
						<div class="col-sm-8">
							%quickbooks_status_message%
						</div>
					</div>
				</div>
			</div>
			<!-- SAGE502 -->
			<!-- QUICKBOOKS1 -->
			<div class="panel panel-success APPROVED_ONLY">
				<div class="panel-heading">
					<h3 class="panel-title">Quickbooks Export Status</h3>
				</div>
				<div class="panel-body">
					<div class="form-group">
						<label for="quickbooks_listid_customer" class="col-sm-4 control-label">#quickbooks_listid_customer#</label>
						<div class="col-sm-4">
							%quickbooks_listid_customer%
						</div>
					</div>
					<div class="form-group">
						<label for="quickbooks_txnid_invoice" class="col-sm-4 control-label">#quickbooks_txnid_invoice#</label>
						<div class="col-sm-4">
							%quickbooks_txnid_invoice%
						</div>
					</div>
					<div class="form-group">
						<label for="quickbooks_status_message" class="col-sm-4 control-label">#quickbooks_status_message#</label>
						<div class="col-sm-8">
							%quickbooks_status_message%
						</div>
					</div>
					<!-- DATAEXT1 -->
					<div class="form-group">
						<label for="quickbooks_dataext_retries" class="col-sm-4 control-label">#quickbooks_dataext_retries#</label>
						<div class="col-sm-8">
							%quickbooks_dataext_retries%
						</div>
					</div>
					<!-- DATAEXT2 -->
				</div>
			</div>
			<!-- QUICKBOOKS2 -->
			<div class="panel panel-info">
				<div class="panel-heading">
					<h3 class="panel-title">Email Invoice Status</h3>
				</div>
				<div class="panel-body">
					<div class="form-group">
						<label for="INVOICE_EMAIL_STATUS" class="col-sm-2 control-label">#INVOICE_EMAIL_STATUS#</label>
						<div class="col-sm-3">
							%INVOICE_EMAIL_STATUS%
						</div>
						<label for="INVOICE_EMAIL_DATE" class="col-sm-2 control-label">#INVOICE_EMAIL_DATE#</label>
						<div class="col-sm-3">
							%INVOICE_EMAIL_DATE%
						</div>
					</div>
				</div>
			</div>

		</div>
	</div>
	
	'
);

//! $sts_form_addshipment_204
$sts_form_addshipment_204 = '<div class="form-group">
	<div class="panel panel-warning tighter">
		<div class="panel-heading">
			<h3 class="panel-title"><a data-toggle="collapse" href="#204panel" aria-expanded="false" aria-controls="204panel"><img src="images/edi_icon1.png" alt="edi_icon" height="22" valign="middle"/> Import Via EDI 204</a> - %EDI_204_B204_SID% <span class="label label-default">Offer %EDI_990_STATUS%</span> From %EDI_204_ORIGIN% ##SHIPMENTS## <span class="label label-success">%EDI_204_B206_PAYMENT%</span></h3>
		</div>
		<div class="panel-body collapse" id="204panel">
			<div class="form-group tighter">
				<div class="col-sm-4">
					<div class="form-group tighter">
						<label for="EDI_204_GS04_OFFERED" class="col-sm-5 control-label">#EDI_204_GS04_OFFERED#</label>
						<div class="col-sm-7">
							%EDI_204_GS04_OFFERED%
						</div>
					</div>
				</div>
				<div class="col-sm-4">
					<div class="form-group tighter">
						<label for="EDI_204_L301_WEIGHT" class="col-sm-4 control-label">#EDI_204_L301_WEIGHT#</label>
						<div class="col-sm-5">
							%EDI_204_L301_WEIGHT%
						</div>
						<div class="form-group tighter pad7">
								%EDI_204_L312_WQUAL%
						</div>
					</div>
				</div>
				<div class="col-sm-4">
					<div class="form-group tighter">
						<label for="EDI_204_NTE_MILES" class="col-sm-4 control-label">#EDI_204_NTE_MILES#</label>
						<div class="col-sm-5">
							%EDI_204_NTE_MILES%
						</div>
						<div class="form-group tighter pad7">
								Miles
						</div>
					</div>
				</div>
			</div>
			<div class="form-group tighter">
				<div class="col-sm-4">
					<div class="form-group tighter">
						<label for="EDI_204_G6202_EXPIRES" class="col-sm-5 control-label">#EDI_204_G6202_EXPIRES#</label>
						<div class="col-sm-7">
							%EDI_204_G6202_EXPIRES%
						</div>
					</div>
				</div>
				<div class="col-sm-4">
					<div class="form-group tighter">
						<label for="EDI_204_L303_RATE" class="col-sm-4 control-label">#EDI_204_L303_RATE#</label>
						<div class="col-sm-5">
							%EDI_204_L303_RATE%
						</div>
						<div class="form-group tighter pad7">
							%EDI_204_L304_RQUAL%
						</div>
					</div>
				</div>
				<div class="col-sm-4">
					<div class="form-group tighter">
						<label for="EDI_204_L1101_SERVICE" class="col-sm-4 control-label">#EDI_204_L1101_SERVICE#</label>
						<div class="col-sm-5">
							%EDI_204_L1101_SERVICE%
						</div>
					</div>
				</div>
			</div>
			<div class="form-group tighter">
				<div class="col-sm-4 tighter">
					<div class="form-group tighter">
						<label for="EDI_990_SENT" class="col-sm-5 control-label">#EDI_990_SENT#</label>
						<div class="col-sm-7">
							%EDI_990_SENT%
						</div>
					</div>
				</div>
				<div class="col-sm-4">
					<div class="form-group tighter">
						<label for="EDI_204_L305_CHARGE" class="col-sm-4 control-label">#EDI_204_L305_CHARGE#</label>
						<div class="col-sm-5">
							%EDI_204_L305_CHARGE%
						</div>
					</div>
				</div>
				<div class="col-sm-4">
				</div>
			</div>
			<div class="form-group">
				<div class="col-sm-2">
				</div>
				<div class="col-sm-7">
					<!-- 990_INFO_HERE -->
				</div>
			</div>
		</div>
	</div>
</div>';

$sts_form_addshipment_990_info = '<span class="label label-danger">Cancel</span>&nbsp;=&nbsp;Decline, <span class="label label-success">Ready Dispatch</span>&nbsp;=&nbsp;Accept';


$sts_form_distance_form = array(	//! sts_form_distance_form
	'title' => '<img src="images/order_icon.png" alt="order_icon" height="24"> Check Distance',
	'action' => 'exp_distance.php',
	//'actionextras' => 'disabled',
	'cancel' => 'index.php',
	'cancelbutton' => 'Back',
	'name' => 'distance',
	'okbutton' => 'Check',
		'layout' => '
	<div class="form-group">
		<div class="col-sm-4 well well-lg">
				<h3 class="text-center text-success" style="margin-top: 0px;">From</h3>
				<label for="FROM_ADDR1" class="col-sm-4 control-label">#FROM_ADDR1#</label>
				<div class="col-sm-8">
					%FROM_ADDR1%
				</div>
				<label for="FROM_ADDR2" class="col-sm-4 control-label">#FROM_ADDR2#</label>
				<div class="col-sm-8">
					%FROM_ADDR2%
				</div>
				<label for="FROM_CITY" class="col-sm-4 control-label">#FROM_CITY#</label>
				<div class="col-sm-8">
					%FROM_CITY%
				</div>
				<label for="FROM_STATE" class="col-sm-4 control-label">#FROM_STATE#</label>
				<div class="col-sm-8">
					%FROM_STATE%
				</div>
				<label for="FROM_ZIP" class="col-sm-4 control-label">#FROM_ZIP#</label>
				<div class="col-sm-8">
					%FROM_ZIP%
				</div>
				<label for="FROM_COUNTRY" class="col-sm-4 control-label">#FROM_COUNTRY#</label>
				<div class="col-sm-8">
					%FROM_COUNTRY%
				</div>
		</div>
		<div class="col-sm-4 well well-lg">
				<h3 class="text-center text-success" style="margin-top: 0px;">To</h3>
				<label for="TO_ADDR1" class="col-sm-4 control-label">#TO_ADDR1#</label>
				<div class="col-sm-8">
					%TO_ADDR1%
				</div>
				<label for="TO_ADDR2" class="col-sm-4 control-label">#TO_ADDR2#</label>
				<div class="col-sm-8">
					%TO_ADDR2%
				</div>
				<label for="TO_CITY" class="col-sm-4 control-label">#TO_CITY#</label>
				<div class="col-sm-8">
					%TO_CITY%
				</div>
				<label for="TO_STATE" class="col-sm-4 control-label">#TO_STATE#</label>
				<div class="col-sm-8">
					%TO_STATE%
				</div>
				<label for="TO_ZIP" class="col-sm-4 control-label">#TO_ZIP#</label>
				<div class="col-sm-8">
					%TO_ZIP%
				</div>
				<label for="TO_COUNTRY" class="col-sm-4 control-label">#TO_COUNTRY#</label>
				<div class="col-sm-8">
					%TO_COUNTRY%
				</div>
		</div>
	</div>
	
	'
);


//! Field Specifications - For use with sts_form

$sts_form_add_shipment_fields = array(	//! $sts_form_add_shipment_fields
	//! SCR# 367 - some fields changed to inline as it does not include hidden field.
	'SHIPMENT_CODE' => array( 'label' => 'Shipment#', 'format' => 'static' ),
	'CHANGED_DATE' => array( 'label' => 'Changed', 'format' => 'inline' ),
	
	'SS_NUMBER' => array( 'label' => 'Office&nbsp;#', 'format' => 'inline' ),
	'PO_NUMBER' => array( 'label' => 'PO1&nbsp;#', 'format' => 'text' ),
	'PO_NUMBER2' => array( 'label' => 'PO2&nbsp;#', 'format' => 'text' ),
	'PO_NUMBER3' => array( 'label' => 'PO3&nbsp;#', 'format' => 'text' ),
	'PO_NUMBER4' => array( 'label' => 'PO4&nbsp;#', 'format' => 'text' ),
	'PO_NUMBER5' => array( 'label' => 'PO5&nbsp;#', 'format' => 'text' ),
	'BOL_NUMBER' => array( 'label' => 'BOL&nbsp;#', 'format' => 'text' ),
	'ST_NUMBER' => array( 'label' => 'Container&nbsp;#', 'format' => 'text' ),
	'IM_EMPTY_DROP' => array( 'label' => 'Empty Drop', 'format' => 'table',
		'table' => CONTACT_INFO_TABLE, 'key' => 'CONTACT_CODE', 'fields' => 'LABEL',
		'condition' => "CONTACT_SOURCE = 'client'
			AND CONTACT_TYPE = 'intermodal'
			AND (SELECT INTERMODAL FROM EXP_CLIENT WHERE CLIENT_CODE = CONTACT_CODE)
			AND EXP_CONTACT_INFO.ISDELETED = FALSE" ),
	
	'FS_NUMBER' => array( 'label' => 'Check Digit', 'format' => 'text' ),
	'REF_NUMBER' => array( 'label' => 'Reference&nbsp;#', 'format' => 'text' ),
	'PICKUP_NUMBER' => array( 'label' => 'Pickup&nbsp;#', 'format' => 'text' ),
	'CUSTOMER_NUMBER' => array( 'label' => 'Customer&nbsp;#', 'format' => 'text' ),
	'PICKUP_APPT' => array( 'label' => 'Pickup&nbsp;Conf&nbsp;#', 'format' => 'text' ),
	'DELIVERY_APPT' => array( 'label' => 'Delivery&nbsp;Conf&nbsp;#', 'format' => 'text' ),
	
	'CURRENT_STATUS' => array( 'label' => 'Status', 'format' => 'table',
		'table' => STATUS_CODES_TABLE, 'key' => 'STATUS_CODES_CODE', 'fields' => 'STATUS_STATE',
		'inline' => true ),
	'BILLING_STATUS' => array( 'label' => 'Status', 'format' => 'table',
		'table' => STATUS_CODES_TABLE, 'key' => 'STATUS_CODES_CODE', 'fields' => 'STATUS_STATE',
		'inline' => true ),
	'PRIORITY' => array( 'label' => 'Priority', 'format' => 'enum' ),

	'QUOTE' => array( 'label' => 'Quote', 'format' => 'bool2' ),
	'CALLER_NAME' => array( 'label' => 'Caller Name', 'format' => 'client', 'ctype' => 'caller' ),
	'CALLER_PHONE' => array( 'label' => 'Caller Phone', 'format' => 'text' ),

	'BILLTO_NAME' => array( 'label' => 'Bill-To', 'format' => 'client' ),
	// , 'extras' => 'required' autofocus
	'BILLTO_CLIENT_CODE' => array( 'label' => 'Bill-To Client#', 'format' => 'hidden-req' ),
	'SHIPPER_CLIENT_CODE' => array( 'label' => 'Shipper Client#', 'format' => 'hidden-req' ),
	'CONS_CLIENT_CODE' => array( 'label' => 'Consignee Client#', 'format' => 'hidden-req' ),
	'SALES_PERSON' => array( 'label' => 'Sales Person', 'format' => 'table',
		'table' => USER_TABLE, 'key' => 'USER_CODE', 'fields' => 'USERNAME', 'nolink' => true,
		'condition' => 'USER_GROUPS like \'%'.EXT_GROUP_SALES.'%\' AND ISACTIVE != \'Inactive\'' ),
	'BILLTO_ADDR1' => array( 'label' => 'Addr1', 'format' => 'text', 'extras' => 'readonly' ),
	'BILLTO_ADDR2' => array( 'label' => 'Addr2', 'format' => 'text', 'extras' => 'readonly' ),
	'BILLTO_CITY' => array( 'label' => 'City', 'format' => 'text', 'extras' => 'readonly' ),
	'BILLTO_STATE' => array( 'label' => 'State', 'format' => 'text', 'extras' => 'readonly' ),
	'BILLTO_ZIP' => array( 'label' => 'Zip', 'format' => 'zip', 'extras' => 'readonly' ),
	'BILLTO_COUNTRY' => array( 'label' => 'Country', 'format' => 'enum', 'extras' => 'readonly' ),
	'BILLTO_PHONE' => array( 'label' => 'Phone', 'format' => 'text', 'extras' => 'readonly' ),
	'BILLTO_EXT' => array( 'label' => 'Ext', 'format' => 'text', 'extras' => 'readonly' ),
	'BILLTO_FAX' => array( 'label' => 'Fax', 'format' => 'text', 'extras' => 'readonly' ),
	'BILLTO_CELL' => array( 'label' => 'Cell', 'format' => 'text', 'extras' => 'readonly' ),
	'BILLTO_CONTACT' => array( 'label' => 'Contact', 'format' => 'text', 'extras' => 'readonly' ),
	'BILLTO_EMAIL' => array( 'label' => 'Email', 'format' => 'text', 'extras' => 'readonly' ),

	'BROKER_CHOICE' => array( 'label' => 'Choose', 'format' => 'table',
		'table' => CONTACT_INFO_TABLE, 'key' => 'CONTACT_INFO_CODE', 'fields' => 'LABEL',
		'condition' => "CONTACT_CODE = ##BILLTO_CLIENT_CODE##
			AND CONTACT_SOURCE = 'client'
			AND CONTACT_TYPE = 'customs broker'
			AND EXP_CONTACT_INFO.ISDELETED = FALSE" ),
	'BROKER_NAME' => array( 'label' => 'Customs', 'format' => 'text', 'extras' => 'readonly' ),
	'BROKER_ADDR1' => array( 'label' => 'Addr1', 'format' => 'text', 'extras' => 'readonly' ),
	'BROKER_ADDR2' => array( 'label' => 'Addr2', 'format' => 'text', 'extras' => 'readonly' ),
	'BROKER_CITY' => array( 'label' => 'City', 'format' => 'text', 'extras' => 'readonly' ),
	'BROKER_STATE' => array( 'label' => 'State', 'format' => 'text', 'extras' => 'readonly' ),
	'BROKER_ZIP' => array( 'label' => 'Zip', 'format' => 'text', 'extras' => 'readonly' ),
	'BROKER_COUNTRY' => array( 'label' => 'Country', 'format' => 'text', 'extras' => 'readonly' ),
	'BROKER_PHONE' => array( 'label' => 'Phone', 'format' => 'text', 'extras' => 'readonly' ),
	'BROKER_EXT' => array( 'label' => 'Ext', 'format' => 'text', 'extras' => 'readonly' ),
	'BROKER_FAX' => array( 'label' => 'Fax', 'format' => 'text', 'extras' => 'readonly' ),
	'BROKER_CELL' => array( 'label' => 'Cell', 'format' => 'text', 'extras' => 'readonly' ),
	'BROKER_CONTACT' => array( 'label' => 'Contact', 'format' => 'text', 'extras' => 'readonly' ),
	'BROKER_EMAIL' => array( 'label' => 'Email', 'format' => 'text', 'extras' => 'readonly' ),
	'BROKER_VALID' => array( 'format' => 'valid', 'code' => 'BROKER_CODE', 'descr' => 'BROKER_DESCR',
		'source' => 'BROKER_VALID_SOURCE', 'lat' => 'BROKER_LAT', 'lon' => 'BROKER_LON' ),
	'BROKER_CODE' => array( 'format' => 'hidden' ),
	'BROKER_DESCR' => array( 'format' => 'hidden' ),
	'BROKER_VALID_SOURCE' => array( 'format' => 'hidden' ),
	'BROKER_LAT' => array( 'format' => 'hidden' ),
	'BROKER_LON' => array( 'format' => 'hidden' ),

	'SHIPPER_NAME' => array( 'label' => 'Shipper', 'format' => 'client', 'ctype' => 'shipper' ),
	'SHIPPER_ADDR1' => array( 'label' => 'Addr1', 'format' => 'text' ),
	'SHIPPER_ADDR2' => array( 'label' => 'Addr2', 'format' => 'text' ),
	'SHIPPER_CITY' => array( 'label' => 'City', 'format' => 'text' ),
	'SHIPPER_STATE' => array( 'label' => 'State', 'format' => 'state' ),
	'SHIPPER_ZIP' => array( 'label' => 'Zip', 'format' => 'zip' ),
	'SHIPPER_COUNTRY' => array( 'label' => 'Country', 'format' => 'enum' ),
	'SHIPPER_PHONE' => array( 'label' => 'Phone', 'format' => 'text' ),
	'SHIPPER_EXT' => array( 'label' => 'Ext', 'format' => 'text' ),
	'SHIPPER_FAX' => array( 'label' => 'Fax', 'format' => 'text' ),
	'SHIPPER_CELL' => array( 'label' => 'Cell', 'format' => 'text' ),
	'SHIPPER_CONTACT' => array( 'label' => 'Contact', 'format' => 'text' ),
	'SHIPPER_EMAIL' => array( 'label' => 'Email', 'format' => 'email', 'extras' => 'multiple' ),
	'SHIPPER_TERMINAL' => array( 'label' => 'Terminal', 'format' => 'text' ),
	'SHIPPER_TZONE' => array( 'label' => 'Time Zone', 'format' => 'inline',
		'snippet' => "GET_TIMEZONE( SHIPPER_ZIP )" ),

	'CONS_NAME' => array( 'label' => 'Consignee', 'format' => 'client', 'ctype' => 'consignee' ),
	'CONS_ADDR1' => array( 'label' => 'Addr1', 'format' => 'text' ),
	'CONS_ADDR2' => array( 'label' => 'Addr2', 'format' => 'text' ),
	'CONS_CITY' => array( 'label' => 'City', 'format' => 'text' ),
	'CONS_STATE' => array( 'label' => 'State', 'format' => 'state' ),
	'CONS_ZIP' => array( 'label' => 'Zip', 'format' => 'zip' ),
	'CONS_COUNTRY' => array( 'label' => 'Country', 'format' => 'enum' ),
	'CONS_PHONE' => array( 'label' => 'Phone', 'format' => 'text' ),
	'CONS_EXT' => array( 'label' => 'Ext', 'format' => 'text' ),
	'CONS_FAX' => array( 'label' => 'Fax', 'format' => 'text' ),
	'CONS_CELL' => array( 'label' => 'Cell', 'format' => 'text' ),
	'CONS_CONTACT' => array( 'label' => 'Contact', 'format' => 'text' ),
	'CONS_EMAIL' => array( 'label' => 'Email', 'format' => 'email', 'extras' => 'multiple' ),
	'CONS_TERMINAL' => array( 'label' => 'Terminal', 'format' => 'text' ),
	'CONS_TZONE' => array( 'label' => 'Time Zone', 'format' => 'inline',
		'snippet' => "GET_TIMEZONE( CONS_ZIP )" ),

	'DIRECTION' => array( 'label' => 'Direction', 'format' => 'enum' ),
	
	'PALLETS' => array( 'label' => 'Pallets', 'format' => 'number', 'align' => 'right', 'extras' => 'readonly' ),
	'PIECES' => array( 'label' => '#&nbsp;Items', 'format' => 'number', 'align' => 'right', 'extras' => 'readonly' ),
	'PIECES_UNITS' => array( 'label' => 'Units', 'format' => 'table',
		'table' => UNIT_TABLE, 'key' => 'UNIT_CODE', 'fields' => 'UNIT_NAME',
		'condition' => 'UNIT_TYPE = \'item\'', 'nolink' => true, 'extras' => 'readonly' ),
	'WEIGHT' => array( 'label' => 'Weight', 'format' => 'number', 'align' => 'right', 'extras' => 'readonly' ),
	'DISTANCE' => array( 'label' => 'Distance', 'format' => 'number', 'align' => 'right', 'extras' => 'readonly' ),
	'DISTANCE_SOURCE' => array( 'label' => 'Source', 'format' => 'text', 'align' => 'right', 'value' => 'none', 'extras' => 'readonly' ),
	'DISTANCE_LAST_AT' => array( 'label' => 'Last At', 'format' => 'timestamp', 'align' => 'right', 'value' => 'NULL', 'extras' => 'readonly' ),
	//'HIGH_VALUE' => array( 'label' => 'High', 'format' => 'bool2' ),
	'DANGEROUS_GOODS' => array( 'label' => 'Hazmat', 'format' => 'bool2', 'extras' => 'readonly' ),
	'UN_NUMBERS' => array( 'label' => 'UN#\'s', 'format' => 'text', 'extras' => 'readonly' ),
	
	'PICKUP_DATE' => array( 'label' => 'Pickup', 'format' => 'date', 'placeholder' => 'mm/dd/yyyy',
		'value' => date("m/d/Y") ),
	'PICKUP_DATE2' => array( 'label' => 'Pickup', 'format' => 'date', 'placeholder' => 'mm/dd/yyyy', 'extras' => 'readonly' ),
	'PICKUP_TIME_OPTION' => array( 'label' => 'Pickup option', 'format' => 'enum' ),
	'PICKUP_TIME1' => array( 'label' => 'Pickup time1', 'format' => 'miltime', 'placeholder' => 'hhmm' ),
	'PICKUP_TIME2' => array( 'label' => 'Pickup time2', 'format' => 'miltime', 'placeholder' => 'hhmm' ),
	'DELIVER_DATE' => array( 'label' => 'Deliver', 'format' => 'date', 'placeholder' => 'mm/dd/yyyy',
		'value' => date("m/d/Y", strtotime('+1 day') ) ),
	'DELIVER_DATE2' => array( 'label' => 'Deliver', 'format' => 'date', 'placeholder' => 'mm/dd/yyyy', 'extras' => 'readonly' ),
	'DELIVER_TIME_OPTION' => array( 'label' => 'Deliver', 'format' => 'enum' ),
	'DELIVER_TIME1' => array( 'label' => 'Deliver time1', 'format' => 'miltime', 'placeholder' => 'hhmm' ),
	'DELIVER_TIME2' => array( 'label' => 'Deliver time2', 'format' => 'miltime', 'placeholder' => 'hhmm' ),
	
	'REEFER_OPS' => array( 'label' => 'Reefer', 'format' => 'enum' ),
	'NOTES' => array( 'label' => 'Disp Notes', 'format' => 'textarea', 'extras' => 'rows="5"' ),
	'LOAD_CODE' => array( 'label' => 'Load#', 'format' => 'inline', 'link' => 'exp_viewload.php?CODE=',
	'value' => 0 ),
	'BILLTO_VALID' => array( 'format' => 'valid', 'code' => 'BILLTO_CODE', 'descr' => 'BILLTO_DESCR', 'source' => 'BILLTO_VALID_SOURCE', 'lat' => 'BILLTO_LAT', 'lon' => 'BILLTO_LON' ),
	'BILLTO_CODE' => array( 'format' => 'hidden' ),
	'BILLTO_DESCR' => array( 'format' => 'hidden' ),
	'BILLTO_VALID_SOURCE' => array( 'format' => 'hidden' ),
	'BILLTO_LAT' => array( 'format' => 'hidden' ),
	'BILLTO_LON' => array( 'format' => 'hidden' ),
	'SHIPPER_VALID' => array( 'format' => 'valid', 'code' => 'SHIPPER_CODE', 'descr' => 'SHIPPER_DESCR', 'source' => 'SHIPPER_VALID_SOURCE', 'lat' => 'SHIPPER_LAT', 'lon' => 'SHIPPER_LON' ),
	'SHIPPER_CODE' => array( 'format' => 'hidden' ),
	'SHIPPER_DESCR' => array( 'format' => 'hidden' ),
	'SHIPPER_VALID_SOURCE' => array( 'format' => 'hidden' ),
	'SHIPPER_LAT' => array( 'format' => 'hidden' ),
	'SHIPPER_LON' => array( 'format' => 'hidden' ),
	'CONS_VALID' => array( 'format' => 'valid', 'code' => 'CONS_CODE', 'descr' => 'CONS_DESCR', 'source' => 'CONS_VALID_SOURCE', 'lat' => 'CONS_LAT', 'lon' => 'CONS_LON' ),
	'CONS_CODE' => array( 'format' => 'hidden' ),
	'CONS_DESCR' => array( 'format' => 'hidden' ),
	'CONS_VALID_SOURCE' => array( 'format' => 'hidden' ),
	'CONS_LAT' => array( 'format' => 'hidden' ),
	'CONS_LON' => array( 'format' => 'hidden' ),
	'quickbooks_listid_customer' => array( 'label' => 'Customer ID', 'format' => 'static', 'align' => 'right' ),
	'quickbooks_txnid_invoice' => array( 'label' => 'Transaction ID', 'format' => 'static', 'align' => 'right' ),
	'quickbooks_status_message' => array( 'label' => 'Transfer Status', 'format' => 'static' ),
	'quickbooks_dataext_retries' => array( 'label' => 'Custom Field Retries', 'format' => 'static' ),
	//'CHANGED_DATE' => array( 'label' => 'Changed', 'format' => 'hidden' ),
	//'CHANGED_BY' => array( 'label' => 'Changed By', 'format' => 'hidden' ),

	// New stuff from 204s
	'CONSOLIDATE_NUM' => array( 'label' => 'Cons&nbsp;#', 'format' => 'static', 'link' => 'exp_addshipment.php?CODE=', 'extras' => 'readonly' ),
	'EDI_204_L301_WEIGHT' => array( 'label' => 'Weight', 'format' => 'numberc', 'align' => 'right', 'extras' => 'readonly' ),
	'EDI_204_L303_RATE' => array( 'label' => 'Rate', 'format' => 'numberc', 'align' => 'right', 'extras' => 'readonly' ),
	'EDI_204_L305_CHARGE' => array( 'label' => 'Charge', 'format' => 'numberc', 'align' => 'right', 'extras' => 'readonly' ),
	'EDI_204_NTE_MILES' => array( 'label' => 'Distance', 'format' => 'numberc', 'align' => 'right', 'extras' => 'readonly' ),
	'EDI_204_L304_RQUAL' => array( 'label' => 'Rate&nbsp;Qual', 'format' => 'inline' ),
	'EDI_204_L312_WQUAL' => array( 'label' => 'Weight&nbsp;Units', 'format' => 'static', 'align' => 'left' ),
	'EDI_204_ISA15_USAGE' => array( 'label' => '204&nbsp;Usage', 'format' => 'inline', 'align' => 'right' ),
	'EDI_204_GS04_OFFERED' => array( 'label' => '204&nbsp;Offered&nbsp;on', 'format' => 'timestamp', 'align' => 'right', 'extras' => 'readonly' ),
	'EDI_204_G6202_EXPIRES' => array( 'label' => '204&nbsp;Expires&nbsp;on', 'format' => 'timestamp', 'align' => 'right', 'extras' => 'readonly' ),
	'EDI_990_SENT' => array( 'label' => '990&nbsp;Sent', 'format' => 'timestamp', 'align' => 'right', 'placeholder' => 'Not Yet Sent', 'extras' => 'readonly' ),
	'EDI_990_STATUS' => array( 'label' => '990&nbsp;Status', 'format' => 'inline' ),
	'EDI_204_ORIGIN' => array( 'label' => '204&nbsp;Origin', 'format' => 'inline' ),
	'EDI_204_B204_SID' => array( 'label' => '204&nbsp;Shipment&nbsp;ID', 'format' => 'inline' ),
	'EDI_204_B206_PAYMENT' => array( 'label' => '204&nbsp;Payment', 'format' => 'inline' ),
	'EDI_204_L1101_SERVICE' => array( 'label' => 'Service', 'format' => 'text', 'align' => 'right', 'extras' => 'readonly' ),
	'BUSINESS_CODE' => array( 'label' => 'Business Code', 'format' => 'table',
		'table' => BUSINESS_CODE_TABLE, 'key' => 'BUSINESS_CODE',
		'condition' => "APPLIES_TO = 'shipment'", 'fields' => 'BC_NAME' ),
	'OFFICE_CODE' => array( 'label' => 'Office', 'format' => 'table',
		'table' => OFFICE_TABLE, 'key' => 'OFFICE_CODE', 'fields' => 'OFFICE_NAME',
		'inline' => true ),
	'CDN_TAX_EXEMPT' => array( 'label' => 'Exempt', 'align' => 'center', 'format' => 'bool' ),
	'CDN_TAX_EXEMPT_REASON' => array( 'label' => 'Reason', 'format' => 'text' ),
	'INVOICE_EMAIL_STATUS' => array( 'label' => 'Status', 'format' => 'enum' ),
	'INVOICE_EMAIL_DATE' => array( 'label' => 'As of', 'format' => 'timestamp', 'extras' => 'readonly' ),

);

$sts_form_distance_fields = array( //! $sts_form_distance_fields
	'FROM_ADDR1' => array( 'label' => 'Addr1', 'format' => 'text' ),
	'FROM_ADDR2' => array( 'label' => 'Addr2', 'format' => 'text' ),
	'FROM_CITY' => array( 'label' => 'City', 'format' => 'text' ),
	'FROM_STATE' => array( 'label' => 'State', 'format' => 'state' ),
	'FROM_ZIP' => array( 'label' => 'Zip', 'format' => 'zip' ),
	'FROM_COUNTRY' => array( 'label' => 'Country', 'format' => 'country' ),
	'TO_ADDR1' => array( 'label' => 'Addr1', 'format' => 'text' ),
	'TO_ADDR2' => array( 'label' => 'Addr2', 'format' => 'text' ),
	'TO_CITY' => array( 'label' => 'City', 'format' => 'text' ),
	'TO_STATE' => array( 'label' => 'State', 'format' => 'state' ),
	'TO_ZIP' => array( 'label' => 'Zip', 'format' => 'zip' ),
	'TO_COUNTRY' => array( 'label' => 'Country', 'format' => 'country' ),
);


//! Layout Specifications - For use with sts_result

$sts_result_shipments_layout = array( //! $sts_result_shipments_layout
	'SHIPMENT_CODE' => array( 'label' => 'Shipment#', 'format' => 'num0nc', 'link' => 'exp_addshipment.php?CODE=', 'align' => 'right' ),
	'SS_NUMBER' => array( 'label' => 'Office#', 'format' => 'text', 'align' => 'right' ),
	'LOAD_CODE' => array( 'label' => 'Load#', 'format' => 'num0nc', 'link' => 'exp_viewload.php?CODE=', 'align' => 'right' ),
	'CURRENT_STATUS' => array( 'label' => 'Status', 'format' => 'table',
		'table' => STATUS_CODES_TABLE, 'key' => 'STATUS_CODES_CODE', 'fields' => 'STATUS_STATE', 'length' => 80 ),
	'BILLING_STATUS' => array( 'label' => 'Billing', 'format' => 'table',
		'table' => STATUS_CODES_TABLE, 'key' => 'STATUS_CODES_CODE', 'fields' => 'STATUS_STATE', 'length' => 80 ),
	'EQUIPMENT' => array( 'label' => 'Equipment', 'format' => 'text',
		'snippet' => "(SELECT GROUP_CONCAT(L.ITEM ORDER BY 1 ASC SEPARATOR ', ')
			FROM EXP_ITEM_LIST L, EXP_EQUIPMENT_REQ R
			WHERE R.SOURCE_TYPE = 'shipment'
			AND R.SOURCE_CODE = SHIPMENT_CODE
			AND R.ITEM_CODE = L.ITEM_CODE)" ),
	//'PRIORITY' => array( 'label' => 'Priority', 'format' => 'text' ),
	'ETA' => array( 'label' => 'Next ETA', 'format' => 'timestamp-s',
		'snippet' => "next_eta(SHIPMENT_CODE)", 'length' => 90,
		'tip' => 'For this to work, you need the shipment on a load, with ETAs set for the stops' ),
	'PICKUP_DATE' => array( 'label' => 'Pickup', 'format' => 'date' ),
	'DELIVER_DATE' => array( 'label' => 'Delivery', 'format' => 'date' ),
	'PICKUP_APPT' => array( 'label' => 'Pickup&nbsp;Conf&nbsp;#', 'format' => 'text', 'length' => 90 ),
	'DELIVERY_APPT' => array( 'label' => 'Delivery&nbsp;Conf&nbsp;#', 'format' => 'text', 'length' => 90 ),
	'TOTAL1' => array( 'label' => 'Total', 'format' => 'table',
		'table' => CLIENT_BILL, 'key' => 'SHIPMENT_ID', 'pk' => 'SHIPMENT_CODE', 'fields' => 'TOTAL', 'align' => 'right', 'searchable' => false  ),
	//'SYNERGY_IMPORT' => array( 'label' => 'Syn', 'align' => 'center', 'format' => 'bool' ),
	//'QUOTE' => array( 'label' => 'Quote', 'align' => 'center', 'format' => 'bool' ),
	'SHIPPER_NAME' => array( 'label' => 'Shipper', 'format' => 'client' ),
	'SHIPPER_CITY' => array( 'label' => 'City', 'format' => 'text' ),
	'SHIPPER_STATE' => array( 'label' => 'State', 'format' => 'state' ),
	'CONS_NAME' => array( 'label' => 'Consignee', 'format' => 'client' ),
	'CONS_CITY' => array( 'label' => 'City', 'format' => 'text' ),
	'CONS_STATE' => array( 'label' => 'State', 'format' => 'state' ),
	'DIRECTION' => array( 'label' => 'Dir', 'format' => 'text' ),
	'BILLTO_NAME' => array( 'label' => 'Bill-to', 'format' => 'text' ),
	'SALES_PERSON' => array( 'label' => 'Sales Person', 'format' => 'table',
		'table' => USER_TABLE, 'key' => 'USER_CODE', 'fields' => 'USERNAME',
		'condition' => 'USER_GROUPS like \'%'.EXT_GROUP_SALES.'%\' AND ISACTIVE != \'Inactive\'' ),
	'PALLETS' => array( 'label' => 'Pallets', 'format' => 'number', 'align' => 'right' ),
	//'PIECES' => array( 'label' => 'Items', 'format' => 'number', 'align' => 'right' ),
	'WEIGHT' => array( 'label' => 'Weight', 'format' => 'number', 'align' => 'right' ),
	'CTPAT_REQUIRED' => array( 'label' => 'C-TPAT', 'format' => 'bool', 'align' => 'center',
		'snippet' => 'COALESCE((SELECT CTPAT_REQUIRED FROM EXP_CLIENT
			WHERE CLIENT_CODE = BILLTO_CLIENT_CODE LIMIT 1), FALSE)' ),
	//'CREATED_DATE' => array( 'label' => 'Created', 'format' => 'date' ),
	//'CREATED_BY' => array( 'label' => 'Created By', 'format' => 'table',
	//	'table' => USER_TABLE, 'key' => 'USER_CODE', 'fields' => 'USERNAME' ),
	//'CHANGED_DATE' => array( 'label' => 'Changed', 'format' => 'date' ),
	//'CHANGED_BY' => array( 'label' => 'Changed By', 'format' => 'table',
	//	'table' => USER_TABLE, 'key' => 'USER_CODE', 'fields' => 'USERNAME' )
	'EDI_204_PRIMARY' => array( 'label' => 'Primary', 'format' => 'hidden' ),
	'CONSOLIDATE_NUM' => array( 'label' => 'Cons&nbsp;#', 'format' => 'num0nc', 'link' => 'exp_addshipment.php?CODE=', 'align' => 'right' ),
	'PO_NUMBER' => array( 'label' => 'PO#', 'format' => 'text',
		'group' => array('PO_NUMBER', 'PO_NUMBER2', 'PO_NUMBER3', 'PO_NUMBER4', 'PO_NUMBER5'),
		'glue' => ', ' ),
	'REF_NUMBER' => array( 'label' => 'Ref&nbsp;#', 'format' => 'text' ),
	'ST_NUMBER' => array( 'label' => 'Container#', 'format' => 'text', 'align' => 'right' ),
	'BOL_NUMBER' => array( 'label' => 'BOL#', 'format' => 'text' ),

	'PICKUP_NUMBER' => array( 'label' => 'Pickup#', 'format' => 'text' ),
	'CUSTOMER_NUMBER' => array( 'label' => 'Customer#', 'format' => 'text' ),
	'FS_NUMBER' => array( 'label' => 'Check Digit', 'format' => 'text' ),

);

$sts_result_shipments_last5_layout = array( //! $sts_result_shipments_last5_layout
	'SHIPMENT_CODE' => array( 'label' => 'Ship#', 'format' => 'num0nc', 'link' => 'exp_addshipment.php?CODE=', 'align' => 'right' ),
	'SS_NUMBER' => array( 'label' => 'Office#', 'format' => 'text', 'align' => 'right' ),
	'PO_NUMBER' => array( 'label' => 'PO#', 'format' => 'text',
		'group' => array('PO_NUMBER', 'PO_NUMBER2', 'PO_NUMBER3', 'PO_NUMBER4', 'PO_NUMBER5'),
		'glue' => ', ' ),
	'LOAD_CODE' => array( 'label' => 'Load#', 'format' => 'num0nc', 'link' => 'exp_viewload.php?CODE=', 'align' => 'right' ),
	'CURRENT_STATUS' => array( 'label' => 'Status', 'format' => 'table',
		'table' => STATUS_CODES_TABLE, 'key' => 'STATUS_CODES_CODE', 'fields' => 'STATUS_STATE', 'length' => 80 ),
	'BILLING_STATUS' => array( 'label' => 'Billing', 'format' => 'table',
		'table' => STATUS_CODES_TABLE, 'key' => 'STATUS_CODES_CODE', 'fields' => 'STATUS_STATE', 'length' => 80 ),
	'PICKUP_DATE' => array( 'label' => 'Ship', 'format' => 'date' ),
	'DELIVER_DATE' => array( 'label' => 'Delivery', 'format' => 'date' ),
	//'BILLTO_NAME' => array( 'label' => 'Bill-to', 'format' => 'client' ),
	//'SALES_PERSON' => array( 'label' => 'Sales Person', 'format' => 'table',
	//	'table' => USER_TABLE, 'key' => 'USER_CODE', 'fields' => 'USERNAME',
	//	'condition' => 'USER_GROUPS like \'%'.EXT_GROUP_SALES.'%\' AND ISACTIVE != \'Inactive\'' ),
	'PALLETS' => array( 'label' => 'Pallets', 'format' => 'number', 'align' => 'right' ),
	'PIECES' => array( 'label' => 'Items', 'format' => 'number', 'align' => 'right' ),
	'WEIGHT' => array( 'label' => 'Weight', 'format' => 'num2nc', 'align' => 'right' ),
	'CHANGED_DATE' => array( 'label' => 'Changed', 'format' => 'timestamp-s', 'length' => 90 ),
	'CHANGED_BY' => array( 'label' => 'By', 'format' => 'table',
		'table' => USER_TABLE, 'key' => 'USER_CODE', 'fields' => 'USERNAME', 'link' => 'exp_edituser.php?CODE=' )

);


//! Edit/Delete Button Specifications - For use with sts_result

$sts_result_shipments_edit = array( //! $sts_result_shipments_edit
	'title' => '<img src="images/order_icon.png" alt="order_icon" height="24"> ## Shipments',
	'sort' => 'PICKUP_DATE asc',
	'cancel' => 'index.php',
	'add' => 'exp_addshipment.php',
	//'actionextras' => 'disabled',
	'addbutton' => 'Add Shipment',
	'cancelbutton' => 'Back',
	'rowbuttons' => array(
		array( 'url' => 'exp_addshipment.php?CODE=', 'key' => 'SHIPMENT_CODE', 'label' => 'SHIPMENT_CODE', 'tip' => 'Edit shipment ', 'icon' => 'glyphicon glyphicon-edit' ),
		array( 'url' => 'exp_cancelshipment.php?CODE=', 'key' => 'SHIPMENT_CODE', 'label' => 'SHIPMENT_CODE', 'tip' => 'Cancel shipment ', 'icon' => 'glyphicon glyphicon-trash', 'confirm' => 'yes' ),
		array( 'url' => 'exp_assignshipment.php?CODE=', 'key' => 'SHIPMENT_CODE', 'label' => 'SHIPMENT_CODE', 'tip' => 'Ready Dispatch shipment ', 'icon' => 'glyphicon glyphicon-share',
		'showif' => 'notready' ),
		array( 'url' => 'exp_shipment_state.php?PW=Soyo&STATE=entry&CODE=', 'key' => 'SHIPMENT_CODE', 'label' => 'SHIPMENT_CODE', 'tip' => 'Unready shipment ', 'icon' => 'glyphicon glyphicon-arrow-left',
		'showif' => 'ready' ),
		array( 'url' => 'exp_dupshipment.php?CODE=', 'key' => 'SHIPMENT_CODE', 'label' => 'SHIPMENT_CODE', 'tip' => 'Duplicate shipment ', 'icon' => 'glyphicon glyphicon-repeat' ),
		array( 'url' => 'exp_shipment_state.php?PW=Soyo&STATE=imported&CODE=', 'key' => 'SHIPMENT_CODE', 'label' => 'SHIPMENT_CODE', 'tip' => '204 Imported shipment ', 'icon' => 'glyphicon glyphicon-repeat', 'showif' => 'ready204' ),
		array( 'url' => 'exp_find_carrier.php?CODE=', 'key' => 'SHIPMENT_CODE', 'label' => 'SHIPMENT_CODE', 'tip' => 'Find carrier for shipment ', 'icon' => 'glyphicon glyphicon-hand-right', 'showif' => 'ready' ),
		
 		array( 'label' => 'BSTATE' ), //! For Billing state part of drop down
 		array( 'label' => 'Separator', 'restrict' => EXT_GROUP_BILLING ),
		array( 'url' => 'exp_clientpay.php?id=', 'key' => 'SHIPMENT_CODE', 'label' => 'SHIPMENT_CODE', 'tip' => 'Client billing ', 'icon' => 'glyphicon glyphicon-th-list', 'restrict' => EXT_GROUP_BILLING,
		//! SCR# 504 - if cancelled, don't show billing button
		'showif' => 'notcancelled' ),
	)
);

$sts_result_shipments_view = array( //! $sts_result_shipments_view
	'title' => '<img src="images/order_icon.png" alt="order_icon" height="24"> ## Shipments',
	'sort' => 'SHIPMENT_CODE asc',
	'filters' => false,
	//'cancel' => 'index.php',
	//'add' => 'exp_addshipment.php',
	//'actionextras' => 'disabled',
	//'addbutton' => 'Add Shipment',
	'cancelbutton' => 'Back',
);

$sts_result_shipments_last5_view = array( //! $sts_result_shipments_last5_view
	'title' => '<img src="images/order_icon.png" alt="order_icon" height="24"> Last 5 Updated Shipments',
	'sort' => 'changed_date desc
		limit 5',
	'filters' => false,
	//'cancel' => 'index.php',
	//'add' => 'exp_addshipment.php',
	//'actionextras' => 'disabled',
	//'addbutton' => 'Add Shipment',
	'cancelbutton' => 'Back',
);

?>
