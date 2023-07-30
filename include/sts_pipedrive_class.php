<?php

// $Id: sts_pipedrive_class.php 5030 2023-04-12 20:31:34Z duncan $
// SCR# 715 - Pipedrive API

// For reference:
// https://developers.pipedrive.com/docs/api/v1/Mailbox

// no direct access
defined('_STS_INCLUDE') or die('Restricted access');

require_once( "sts_setting_class.php" );
require_once( "sts_user_class.php" );
require_once( "sts_zip_class.php" );
require_once( "sts_client_class.php" );
require_once( "sts_contact_info_class.php" );
require_once( "sts_item_list_class.php" );

define( 'TOKEN', 'api_token=' );
define( 'GET_LEADS', '/leads?archived_status=not_archived&api_token=' );
define( 'GET_PERSON', '/persons/' );
define( 'GET_ME', '/users/me?api_token=' );
define( 'GET_LABELS', '/leadLabels?api_token=' );
define( 'CREATE_LABEL', '/leadLabels?api_token=' );
define( 'GET_ORGANIZATION', '/organizations/' );
define( 'GET_NOTES', '/notes?org_id=' );
define( 'ARCHIVE_LEAD', '/leads/' );


class sts_pipedrive extends sts_table {
	public $setting_table;
	protected $pipedrive_log;
	protected $pipedrive_diag_level;	// Text
	protected $diag_level;		// numeric version
	protected $message = "";
	private $user_table;
	private $zip_table;
	private $client_table;
	private $contact_info_table;
	private $cat;
	private $item_list_table;
	private $enabled = false;
	private $api_url = '';
	private $api_label = '';
	private $api_label_id = '';
	private $api_token = '';

	// Constructor does not need the table name
	public function __construct( $database, $debug = false ) {
		global $sts_error_level_label, $sts_crm_dir;

		$this->debug = $debug;
		$this->database = $database;
		$this->setting_table = sts_setting::getInstance($database, $debug);
		$this->pipedrive_log =				$this->setting_table->get( 'api', 'PIPEDRIVE_LOG_FILE' );
		if( isset($this->pipedrive_log) && $this->pipedrive_log <> '' && 
			$this->pipedrive_log[0] <> '/' && $this->pipedrive_log[0] <> '\\' 
			&& $this->pipedrive_log[1] <> ':' )
			$this->pipedrive_log = $sts_crm_dir.$this->pipedrive_log;

		$this->pipedrive_diag_level =		$this->setting_table->get( 'api', 'PIPEDRIVE_DIAG_LEVEL' );
		$this->diag_level =  array_search(strtolower($this->pipedrive_diag_level), $sts_error_level_label);
		if( $this->diag_level === false ) $this->diag_level = EXT_ERROR_ALL;
		
		$this->enabled = $this->setting_table->get( 'option', 'PIPEDRIVE_ENABLED' ) == 'true';
		if( $this->enabled ) {
			$this->api_url = $this->setting_table->get( 'api', 'PIPEDRIVE_URL' );
			$this->api_label = $this->setting_table->get( 'api', 'PIPEDRIVE_LABEL' );
	
			$this->user_table = sts_user::getInstance($database, $debug);
			
			$this->api_token = $this->user_table->pipeline_token( $_SESSION['EXT_USER_CODE'] );
			if( $this->is_enabled() ) {
				$this->zip_table = sts_zip::getInstance($database, $debug);
				
				$this->client_table = sts_client::getInstance($database, $debug);
				$this->contact_info_table = sts_contact_info::getInstance($database, $debug);
				$this->cat = sts_client_activity::getInstance($database, $debug);
				$this->item_list_table = sts_item_list::getInstance($database, $debug);
			
				$this->get_label_id(); // label for imported leads
			}
		}

		$myclass = get_class ();
		if( $debug ) echo "<p>Create $myclass</p>";
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

	public function log_event( $message, $level = EXT_ERROR_ERROR ) {
		//if( $this->debug ) echo "<p>log_event: $this->ifta_log, $message, this->diag_level = $this->diag_level level=$level</p>";
		//file_put_contents($this->ifta_log, date('m/d/Y h:i:s A')." pid=".getmypid()." ".__METHOD__.": $this->ifta_log, $message, this->diag_level = $this->diag_level level=$level\n", FILE_APPEND);
		$this->message = $message;
		if( $this->diag_level >= $level ) {
			if( (file_exists($this->pipedrive_log) && is_writable($this->pipedrive_log)) ||
				is_writable(dirname($this->pipedrive_log)) ) 
				file_put_contents($this->pipedrive_log, date('m/d/Y h:i:s A')." pid=".getmypid().
					" msg=".$message."\n\n", (file_exists($this->pipedrive_log) ? FILE_APPEND : 0) );
		}
	}
	
	public function getMessage() {
		return $this->message;
	}

	public function is_enabled() {
		if( $this->debug ) {
			echo "<pre>";
			var_dump($this->enabled, $this->api_url, $this->api_token);
			echo "</pre>";
		}
	//	$this->log_event(__METHOD__.": enabled = ".($this->enabled ? "true" : "false").
	//		" url = ".(isset($this->api_url) ? $this->api_url : "not set").
	//		" token = ".(isset($this->api_token) ? $this->api_token : "not set"));
		return $this->enabled && isset($this->api_url) && ! empty($this->api_url) &&
			isset($this->api_token) && ! empty($this->api_token);
	}
	
	private function create_label() {
		$result = false;
		//$this->log_event(__METHOD__.": entry, label = ".$this->api_label, EXT_ERROR_DEBUG);

		if( ! empty($this->api_label) ) {
			$url = $this->api_url.CREATE_LABEL.$this->api_token;
			if( $this->debug ) echo "<p>".__METHOD__.": url = $url</p>";
			
			$curl = curl_init($url);
			curl_setopt($curl, CURLOPT_URL, $url);
			curl_setopt($curl, CURLOPT_POST, true);
			curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
			
			$headers = [
				"Accept: application/json",
				"Content-Type: application/json",
			];
			curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
			
			$data = [ 'name' => $this->api_label, 'color' => 'gray' ];
			if( $this->debug ) {
				echo "<pre>".__METHOD__.": headers, data\n";
				var_dump($headers);
				var_dump($data);
				echo "</pre>";
			}
			
			curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($data) );
			
			//for debug only!
			curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
			curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
			
			$resp = curl_exec($curl);
			if (curl_errno($curl)) {
				$error_msg = curl_error($curl);
				if( $this->debug ) echo "<p>".__METHOD__.": error_msg = $error_msg</p>";
			}

			curl_close($curl);
			
			if( $resp ) {
				$info = json_decode($resp, true);
				
				if( $this->debug ) {
					echo "<pre>".__METHOD__.": response\n";
					var_dump($info);
					echo "</pre>";
				}
				if( is_array($info) && count($info) > 0 ) {
					$result = isset($info['success']) && $info['success'];
					if( isset($info['data']) && is_array($info['data']) &&
						isset($info['data']['id']))
						$this->api_label_id = $info['data']['id'];
				}
			}
		}
		$this->log_event(__METHOD__.": exit, result = ".
			($result ? 'true' : 'false')." id = $this->api_label_id", EXT_ERROR_DEBUG);

		return $result;
	}

	//! Fetch the label_id we need
	private function get_label_id() {
		$result = false;
		//$this->log_event(__METHOD__.": entry, label = ".$this->api_label, EXT_ERROR_DEBUG);
		$arrContextOptions=array(
		    "ssl"=>array(
		        "verify_peer"=>false,
		        "verify_peer_name"=>false,
		        'allow_self_signed' => true,
		    ),
		);  		

		if( ! empty($this->api_label) ) {
			$url = $this->api_url.GET_LABELS.$this->api_token;
			if( $this->debug ) echo "<p>".__METHOD__.": url = $url</p>";
		
			@$response = file_get_contents($url, false,
				stream_context_create($arrContextOptions));
			if( $response ) {
				$info = json_decode($response, true);
				if( is_array($info) && count($info) > 0 ) {
					if( isset($info['success']) && $info['success'] &&
						isset($info['data']) && is_array($info['data']) ) {
						foreach( $info['data'] as $row ) {
							if( $row['name'] == $this->api_label ) {
								$result = true;
								$this->api_label_id = $row['id'];
								if( $this->debug ) echo "<p>".__METHOD__.": found $this->api_label_id</p>";
							}
						}
					}
				}
			}
			
			if( ! $result ) {
				if( $this->debug ) echo "<p>".__METHOD__.": $this->api_label NOT found</p>";
				$result = $this->create_label();
			}
		} else {
			if( $this->debug ) echo "<p>".__METHOD__.": PIPEDRIVE_LABEL not configured</p>";
		}
					
		$this->log_event(__METHOD__.": exit, result = ".
			($result ? 'true' : 'false')." id = $this->api_label_id", EXT_ERROR_DEBUG);
		return $result;		
	}
	
	//! Fetch the user name of the current Pipedrive user
	public function get_my_name() {
		$result = 'unknown user';

		$url = $this->api_url.GET_ME.$this->api_token;
		if( $this->debug ) echo "<p>".__METHOD__.": url = $url</p>";
		
		$response = file_get_contents($url);
		if( $response ) {
			$info = json_decode($response, true);
			if( is_array($info) && count($info) > 0 ) {
				if( isset($info['success']) && $info['success'] &&
					isset($info['data']) && is_array($info['data']) ) {
					$data = $info['data'];
					
					if( isset($data['id']) && isset($data['name']) )
						$result = $data['name'].' ('.$data['id'].')';
				}
			}
		}
					
		$this->log_event(__METHOD__.": return $result", EXT_ERROR_DEBUG);
		return $result;		
	}
	
	public function get_person( $id ) {
		$result = false;
		$this->log_event(__METHOD__.": entry, id = $id", EXT_ERROR_DEBUG);

		$url = $this->api_url.GET_PERSON.$id.'?'.TOKEN.$this->api_token;
		if( $this->debug ) echo "<p>".__METHOD__.": url = $url</p>";
		
		$data = file_get_contents($url);
		
		if( $data ) {
			$info = json_decode($data, true);
			//echo "<pre>PERSON\n";
			//var_dump($info);
			//echo "</pre>";

			if( is_array($info) && count($info) > 0 ) {
				if( isset($info['data']) && is_array($info['data']) ) {
					$result = [];
					
					if( ! empty($info['data']['name']) ) {
						$result['CONTACT_NAME'] = $info['data']['name'];
					}
					if( isset($info['data']['phone']) && is_array($info['data']['phone']) &&
						count($info['data']['phone']) > 0 ) {
						foreach( $info['data']['phone'] as $row ) {
							if( isset($row['label']) && $row['label'] == 'work' &&
								! empty($row['value']))
								$result['PHONE_OFFICE'] = $row['value'];
							else if( isset($row['label']) && $row['label'] == 'mobile' &&
								! empty($row['value']))
								$result['PHONE_CELL'] = $row['value'];
						}
					}
					if( isset($info['data']['email']) && is_array($info['data']['email']) &&
						count($info['data']['email']) > 0 ) {
						$row = $info['data']['email'][0];
						if( ! empty($row['value']))
							$result['EMAIL'] = $row['value'];
					}
				}
			}
		}

		//echo "<pre>PERSON\n";
		//var_dump($result);
		//echo "</pre>";
		return $result;
	}
	
	public function get_notes( $id ) {
		$result = false;
		$this->log_event(__METHOD__.": entry, id = $id", EXT_ERROR_DEBUG);

		$url = $this->api_url.GET_NOTES.$id.'&'.TOKEN.$this->api_token;
		if( $this->debug ) echo "<p>".__METHOD__.": url = $url</p>";
		
		$data = file_get_contents($url);
		
		if( $data ) {
			$info = json_decode($data, true);
			//echo "<pre>NOTES\n";
			//var_dump($info);
			//echo "</pre>";
			if( is_array($info) && count($info) > 0 ) {
				if( isset($info['data']) && is_array($info['data']) &&
					count($info['data']) > 0 ) {
					$result = [];
					foreach( $info['data'] as $row ) {
						if( ! empty($row['content']) ) {
							$result[] = date("m/d/Y H:i", strtotime($row['add_time'])).' '.$row['content'];
						}
					}
				}
			}
			
		}

		return $result;
	}
	
	public function get_organiztion( $id ) {
		$result = false;
		$this->log_event(__METHOD__.": entry, id = $id", EXT_ERROR_DEBUG);

		$url = $this->api_url.GET_ORGANIZATION.$id.'?'.TOKEN.$this->api_token;
		if( $this->debug ) echo "<p>".__METHOD__.": url = $url</p>";
		
		$data = file_get_contents($url);
		
		if( $data ) {
			$info = json_decode($data, true);
			//echo "<pre>ORGANIZATION\n";
			//var_dump($info);
			//echo "</pre>";
			
			if( is_array($info) && count($info) > 0 ) {
				if( isset($info['data']) && is_array($info['data']) ) {
					$name = '';
					$address = [];
					$combined_notes = '';
					
					if( ! empty($info['data']['name']) ) {
						$name = $info['data']['name'];
					}
					if( ! empty($info['data']['address_street_number']) ) {
						$number = $info['data']['address_street_number'];
					}
					if( ! empty($info['data']['address_route']) ) {
						$street = $info['data']['address_route'];
					}
					if( ! empty($number) && ! empty($street) )
						$address['ADDRESS'] = $number.' '.$street;
					
					if( ! empty($info['data']['address_postal_code']) ) {
						$address['ZIP_CODE'] = $info['data']['address_postal_code'];
						
						$zip_info = $this->zip_table->zip_lookup( $address['ZIP_CODE'] );
						if( is_array($zip_info) && count($zip_info) > 0 ) {
							$row = $zip_info[0];
							if( isset($row['Country']))
								$address['COUNTRY'] = $row['Country'];
							if( isset($row['State']))
								$address['STATE'] = $row['State'];
							if( isset($row['City']))
								$address['CITY'] = $row['City'];
						}
					}
					//echo "<pre>NAME, ADDRESS\n";
					//var_dump($name, $address);
					//echo "</pre>";

					if( isset($info['data']['notes_count']) &&
						$info['data']['notes_count'] > 0 ) {
						$notes = $this->get_notes( $id );
						if( is_array($notes))
							$combined_notes = implode("\n", $notes);
					}
					
					$result = ['CLIENT_NAME' => $name,
						'address' => $address,
						'notes' => $combined_notes];
				}
			}
		}

		return $result;
	}
	
	private function lead_source() {
		$result = $this->item_list_table->get_item_code( 'Lead source', 'Pipedrive' );
		if( ! $result ) {
			$this->log_event(__METHOD__.": add Pipedrive Lead source", EXT_ERROR_DEBUG);
			$result = $this->item_list_table->add( array('ITEM_TYPE' => 'Lead source',
				    	'ITEM' => 'Pipedrive' ));
			$this->item_list_table->write_cache();
		}
	}
	
	public function archive_lead( $id, $is_archived = true, $not_dup = false, $labels = false ) {
		$result = false;
		$this->log_event(__METHOD__.": entry, id = $id is_archived = ".
			($is_archived ? 'true' : 'false').
			" not_dup = ".($not_dup ? 'true' : 'false'), EXT_ERROR_DEBUG);

		$url = $this->api_url.ARCHIVE_LEAD.$id.'?'.TOKEN.$this->api_token;
		if( $this->debug ) echo "<p>".__METHOD__.": url = $url</p>";
		
		$curl = curl_init($url);
		curl_setopt($curl, CURLOPT_URL, $url);
		curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'PATCH');
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
		
		$headers = [
			"Accept: application/json",
			"Content-Type: application/json",
		];
		curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
		
		$data = [ "is_archived" => $is_archived ];
		
		//! For imported prospects, add a lable while not overwriting other labels
		// This depends on the setting api/PIPEDRIVE_LABEL
		if( ! empty($this->api_label) && $not_dup ) {
			$new_labels = [];
			if( is_array($labels) && count($labels) > 0 ) {
				foreach($labels as $l) {
					if( $l != $this->api_label_id ) {
						$new_labels[] = $l;
					}
				}
			}
			
			$new_labels[] = $this->api_label_id;
			
			$data['label_ids'] = $new_labels;
		}

		if( $this->debug ) {
			echo "<pre>".__METHOD__.": data\n";
			var_dump($data);
			echo "</pre>";
		}
		
		curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($data) );
		
		//for debug only!
		curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
		curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
		
		$resp = curl_exec($curl);
		curl_close($curl);
		
		if( $resp ) {
			$info = json_decode($resp, true);
			
			if( is_array($info) && count($info) > 0 ) {
				$result = isset($info['success']) && $info['success'];
			}
		}
		$this->log_event(__METHOD__.": exit, result = ".
			($result ? 'true' : 'false'), EXT_ERROR_DEBUG);

		return $result;
	}

	public function process_lead( $lead, $interactive = false ) {
		global $_SESSION;
		
		$result = false;
		$this->log_event(__METHOD__.": entry, ".
			(empty($lead['title']) ? '' : $lead['title']).
			(empty($lead['id']) ? '' : ' '.$lead['id'])
			//."\n\n".
			//print_r($lead, true)
			, EXT_ERROR_DEBUG);
		if( isset($lead) && is_array($lead) && isset($lead['id']) ) {
			$lead_id = $lead['id'];
			//echo "<pre>LEAD ID, TITLE\n";
			//var_dump($lead_id, $lead['title']);
			//echo "</pre>";
		
			$person = $organization = false;
			
			if( isset($lead['person_id']) && $lead['person_id'] > 0 ) {
				$person = $this->get_person( $lead['person_id'] );
			}
	
			if( isset($lead['organization_id']) && $lead['organization_id'] > 0 ) {
				$organization = $this->get_organiztion( $lead['organization_id'] );
			}
	
			//echo "<pre>PERSON, ORGANIZATION\n";
			//var_dump($person, $organization);
			//echo "</pre>";
			
			//! Now we add a prospect
			if( is_array($person) && is_array($organization) ) {
				$name = empty($organization["CLIENT_NAME"]) ? '' : $organization["CLIENT_NAME"];
				$addr = $organization['address'];
				$address = empty($addr["ADDRESS"]) ? '' : $addr["ADDRESS"];
				$city = empty($addr["CITY"]) ? '' : $addr["CITY"];
				$state = empty($addr["STATE"]) ? '' : $addr["STATE"];
				$zip_code = empty($addr["ZIP_CODE"]) ? '' : $addr["ZIP_CODE"];
				$country = empty($addr["COUNTRY"]) ? 'USA' : $addr["COUNTRY"];
				
				$duplicate = $this->client_table->check_match( $name, $address, $city, $state, $zip_code, $country );
				
				//! Add client entry
				$entry_state = $this->client_table->behavior_state['entry'];
				$client_fields = array( 'CLIENT_NAME' => $name,
					'CLIENT_TYPE' => 'lead',
					'CURRENT_STATUS' =>  $entry_state,
					'LEAD_SOURCE_CODE' => $this->lead_source(),
					'SALES_PERSON' => $_SESSION["EXT_USER_CODE"],
					'PIPEDRIVE_LEAD_ID' => $lead['id'] );
				
				$import_time = date("m/d/Y H:i");
				$organization["notes"] = "Imported from Pipedrive on $import_time\n".
					(empty($lead['title']) ? '' : "Lead Title = ".$lead['title']."\n").
					$organization["notes"];

				if( !empty($organization["notes"]))
					$client_fields["CLIENT_NOTES"] = $organization["notes"];
		
				//echo "<pre>CLIENT FIELDS\n";
				//var_dump($client_fields);
				//echo "</pre>";
				$client_code = $this->client_table->add( $client_fields );
				$this->log_event(__METHOD__.": ".$lead['title']." client_code = $client_code", EXT_ERROR_DEBUG);
				
				//! Add contact info
				$contact_info_fields = array( 'CONTACT_CODE' => $client_code,
					'CONTACT_SOURCE' => 'client',
					'CONTACT_TYPE' => 'company');
				if( !empty($person["CONTACT_NAME"]))
					$contact_info_fields["CONTACT_NAME"] = $person["CONTACT_NAME"];
				if( !empty($addr["ADDRESS"]))
					$contact_info_fields["ADDRESS"] = $addr["ADDRESS"];
				//if( !empty($addr["ADDRESS2"]))
				//	$contact_info_fields["ADDRESS2"] = $addr["ADDRESS2"];
				if( !empty($addr["CITY"]))
					$contact_info_fields["CITY"] = $addr["CITY"];
				if( !empty($addr["STATE"]))
					$contact_info_fields["STATE"] = $addr["STATE"];
				if( !empty($addr["ZIP_CODE"]))
					$contact_info_fields["ZIP_CODE"] = $addr["ZIP_CODE"];
				if( !empty($addr["COUNTRY"]))
					$contact_info_fields["COUNTRY"] = $addr["COUNTRY"];
				
				if( !empty($person["PHONE_OFFICE"]))
					$contact_info_fields["PHONE_OFFICE"] = $person["PHONE_OFFICE"];
				//if( !empty($person["PHONE_EXT"]))
				//	$contact_info_fields["PHONE_EXT"] = $person["PHONE_EXT"];
				if( !empty($person["PHONE_CELL"]))
					$contact_info_fields["PHONE_CELL"] = $person["PHONE_CELL"];
				if( !empty($person["EMAIL"]))
					$contact_info_fields["EMAIL"] = $person["EMAIL"];
		
				//echo "<pre>CONTACT INFO FIELDS\n";
				//var_dump($contact_info_fields);
				//echo "</pre>";
				$this->contact_info_table->add( $contact_info_fields );
				$this->log_event(__METHOD__.": ".$lead['title']." added contact info", EXT_ERROR_DEBUG);
				
				//! Add activity
				$this->cat->enter_lead( $client_code );
				$this->log_event(__METHOD__.": ".$lead['title']." added client activity", EXT_ERROR_DEBUG);
		
				//! Add note to the activity
				$this->cat->update("CLIENT_CODE = ".$client_code."
					AND ACTIVITY = ".$entry_state,
					array('NOTE' => 'Imported from Pipedrive'));
				
				//! If duplicate, send to admin
				if( is_array($duplicate) && count($duplicate) > 0 ) {
					//! Archive the lead in Pipedrive
					$result = $this->archive_lead( $lead['id'] );
				
					$duplicate_state = $this->client_table->behavior_state['admin'];
					$this->client_table->change_state( $client_code, $duplicate_state );
					$this->log_event(__METHOD__.": ".$lead['title']." duplicate detected", EXT_ERROR_DEBUG);
					if( $interactive ) echo '<h3>'.$lead['title'].' <a href="exp_editclient.php?CODE='.$client_code.'">duplicate detected</a></h3>';
	
				} else { //! Move to assign state
					//! Archive the lead in Pipedrive
					$result = $this->archive_lead( $lead['id'], true, true, $lead['label_ids'] );
				
					$assign_state = $this->client_table->behavior_state['assign'];
					$this->client_table->change_state( $client_code, $assign_state );
					$this->log_event(__METHOD__.": ".$lead['title']." assigned / prospect", EXT_ERROR_DEBUG);
					if( $interactive ) echo '<h3>'.$lead['title'].' assigned / <a href="exp_editclient.php?CODE='.$client_code.'">prospect</a></h3>';
				}
			} else {
				$this->log_event(__METHOD__.": not enough information (person, organization) for lead ".$lead['title'], EXT_ERROR_ERROR);
				if( $interactive ) echo '<h3>'.$lead['title'].' not enough information (person, organization) to import</h3>';
			}
		} else {
			$this->log_event(__METHOD__.": missing lead information", EXT_ERROR_ERROR);
			if( $interactive ) echo '<h3>missing lead information</h3>';
		}

		return $result;
	}
	
	public function get_leads( $interactive = false ) {
		$result = false;
		$count = $errors = 0;
		$this->log_event(__METHOD__.": ENTRY ----------------", EXT_ERROR_DEBUG);
		
		if( $this->is_enabled() ) {
			$url = $this->api_url.GET_LEADS.'&'.TOKEN.$this->api_token;
			if( $this->debug ) echo "<p>".__METHOD__.": url = $url</p>";
			
			do {
				$more_items = false;
					
				$data = file_get_contents($url);
				
				if( $data ) {
					$info = json_decode($data, true);
					//echo "<pre>LEADS\n";
					//var_dump($info);
					//echo "</pre>";
					
					if( is_array($info) && count($info) > 0 ) {
						$success = isset($info['success']) && $info['success'];
						
						if( isset($info['data']) && is_array($info['data']) &&
							count($info['data']) > 0 ) {
							foreach( $info['data'] as $lead ) {
								$resp = $this->process_lead( $lead, $interactive );
								if( $resp )
									$count++;
								else
									$errors++;
							}
						}
						
						$more_items = isset($info['additional_data']) &&
							is_array($info['additional_data']) &&
							isset($info['additional_data']['pagination']) &&
							is_array($info['additional_data']['pagination']) &&
							isset($info['additional_data']['pagination']['more_items_in_collection']) &&
							$info['additional_data']['pagination']['more_items_in_collection'];
					} else {
						$this->log_event(__METHOD__.": error - failed to get leads from Pipedrive API", EXT_ERROR_ERROR);
					}
				}
			} while( $data && $success && $more_items );
		} else {
			$this->log_event(__METHOD__.": error - not enabled", EXT_ERROR_ERROR);
		}
		if( $interactive ) echo '<h3>Imported '.$count.' leads, '.$errors.' errors from Pipedrive</h3>
		';
		$this->log_event(__METHOD__.": EXIT ---------------- imported $count leads, $errors errors", EXT_ERROR_DEBUG);
		
		return $result;
	}
	
}



