<?php

// $Id: sts_archive_class.php 4697 2022-03-09 23:02:23Z duncan $
//! SCR# 606 - Archive class, to archive/de-archive records

// no direct access
defined('_STS_INCLUDE') or die('Restricted access');

require_once( "sts_setting_class.php" );
require_once( "sts_email_class.php" );
require_once( "include/sts_user_log_class.php" );

require_once( "sts_shipment_class.php" );
require_once( "sts_shipment_load_class.php" );
require_once( "sts_status_class.php" );
require_once( "sts_detail_class.php" );
require_once( "sts_attachment_class.php" );

require_once( "sts_stop_class.php" );
require_once( "sts_load_class.php" );

class sts_archive {

	private $debug;
	private $db_schema = '';
	private $db_version = '';
	private $archived_by = '';
	private $archived_on = '';
	private $archive_dir = '';
	private $archive_file = '';
	private $archive_subdir = '';
	private $archive_from = '';
	private $archive_to = '';

	private $log_file;
	private $debug_diag_level;
	private $request_id;
	private $last_error = '';

	private $setting_table;
	private $user_log_table;

	private $shipment_table;
	private $shipment_load_table;
	private $status_table;
	private $detail_table;
	private $attachment_table;
	private $client_billing_table;

	private $load_table;
	private $stop_table;
	
	private $loads;
	private $shipments;

	private $max_size;
	private $file_errors = array(1 => 'php.ini max file size exceeded', 
        	    2 => 'html form max file size exceeded', 
            	3 => 'file upload was only partial', 
            	4 => 'no file was attached',
            	6 => 'Missing a temporary folder',
			    7 => 'Failed to write file to disk.',
			    8 => 'A PHP extension stopped the file upload.');

	// Constructor does not need the table name
	public function __construct( $database, $debug = false ) {
		global $sts_database, $sts_schema_release_name, $sts_error_level_label, $_SESSION;
		
		$this->db_schema = $sts_database;
		$this->db_version = $sts_schema_release_name;
		$this->archived_by = $_SESSION['EXT_USERNAME']." (".$_SESSION['EXT_USER_CODE'].")";
		$this->archived_on = date("Y-m-d H:i");

		$this->database = $database;
		$this->debug = $debug;
		if( $this->debug ) echo "<p>Create sts_archive</p>";

		$this->setting_table = sts_setting::getInstance( $database, $this->debug );
		$this->archive_dir = $this->setting_table->get( 'option', 'ARCHIVE_DIR' );
		$this->log_file = $this->setting_table->get( 'option', 'ARCHIVE_LOG_FILE' );
		$this->debug_diag_level = $this->setting_table->get( 'option', 'ARCHIVE_DIAG_LEVEL' );
		$this->diag_level =  array_search(strtolower($this->debug_diag_level), $sts_error_level_label);
		if( $this->diag_level === false ) $this->diag_level = EXT_ERROR_ALL;
		$this->request_id = substr($_SERVER['REQUEST_TIME_FLOAT']*1000, -4);
		$this->max_size =
			$this->setting_table->get( 'option', 'ATTACHMENTS_MAX_SIZE' );
		
		$this->user_log_table = sts_user_log::getInstance($database, $debug);
	
		$this->shipment_table = sts_shipment::getInstance($database, $debug);
		$this->shipment_load_table = sts_shipment_load::getInstance($database, $debug);
		$this->status_table = sts_status::getInstance($database, $debug);
		$this->detail_table = sts_detail::getInstance($database, $debug);
		$this->attachment_table = sts_attachment::getInstance($database, $debug);
		$this->client_billing_table = new sts_table($database , CLIENT_BILL , $debug);
	
		$this->load_table = sts_load::getInstance($database, $debug);
		$this->stop_table = sts_stop::getInstance($database, $debug);
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
    
	public function log_event( $event, $level = EXT_ERROR_ERROR ) {		
		if( $this->diag_level >= $level ) {
			if( isset($this->log_file) && $this->log_file <> '' && ! is_dir($this->log_file) ) {
				if(is_writable(dirname($this->log_file)) ) {
					file_put_contents($this->log_file, date('m/d/Y h:i:s A')." ".$this->request_id." ".$event.PHP_EOL,
						(file_exists($this->log_file) ? FILE_APPEND : 0) );
				} else {
					if( $this->debug ) echo "<p>log_event: ".dirname($this->log_file)." not writeable</p>";
				}
			}
		}
	}
	
	public function get_last_error() {
		return $this->last_error;
	}

    //! ----------------- Attachments
    // Given a list of attachments, read in files into an array, base64 encoded
    private function import_attachments( $attachments ) {
		$this->log_event( __METHOD__.': entry, '.count($attachments).' attachments', EXT_ERROR_DEBUG );
	    if( $this->debug ) echo "<p>".__METHOD__.": entry, ".count($attachments)." attachments</p>";
	    $attachment_files = array();
	    if( count($attachments) > 0 ) {
		    foreach($attachments as $row) {
			    $file_code = $row['ATTACHMENT_CODE'];
			    $file_name = $row['FILE_NAME'];
				$file_type = $this->attachment_table->mime_type( $file_name );
			    $data = $this->attachment_table->fetch( $file_code );
				$attachment_files[$file_code] = base64_encode($data);
		    }
	    }
	    return $attachment_files;
    }

    // Delete a list of attachments
    private function delete_attachments( $attachments ) {
		$this->log_event( __METHOD__.': entry, '.count($attachments).' attachments', EXT_ERROR_DEBUG );
	    if( $this->debug ) echo "<p>".__METHOD__.": entry, ".count($attachments)." attachments</p>";
	    $result = true;
	    if( count($attachments) > 0 ) {
		    foreach($attachments as $row) {
			    $result = $this->attachment_table->delete_attachment( $row['ATTACHMENT_CODE']  );
			}
		}
		
		$this->log_event( __METHOD__.': return '.($result ? "true" : "false"), EXT_ERROR_DEBUG );
		if( $this->debug ) echo "<p>".__METHOD__.": return".($result ? "true" : "false")."</p>";
		return $result;
	}
   
    // Add a list of attachments
    // Have to be careful rows or files don't already exist
    private function insert_attachments( $attachments, $contents ) {
		$this->log_event( __METHOD__.': entry, '.count($attachments).' attachments', EXT_ERROR_DEBUG );
	    if( $this->debug ) echo "<p>".__METHOD__.": entry, ".count($attachments)." attachments</p>";
	    $result = true;
	    if( count($attachments) > 0 ) {
		    foreach($attachments as $row) {
			    $this->attachment_table->restore_attachment( $row,
			    	base64_decode($contents[$row['ATTACHMENT_CODE']]) );
			}
		}
		
		$this->log_event( __METHOD__.': return '.($result ? "true" : "false"), EXT_ERROR_DEBUG );
		if( $this->debug ) echo "<p>".__METHOD__.": return".($result ? "true" : "false")."</p>";
		return $result;
	}
   
    // Compare two arrays
    private function array_match( $a, $b ) {
	    ksort($a);
	    ksort($b);

	    return $a == $b;
    }
    
    private function cl( $x, $y, $z = '' ) {
	    return is_array($x) && isset($x[$y]) ? $x[$y] : $z;
    }
    
    // Make a text form of the address
    // Prefix can be BILLTO, SHIPPER, CONS, BROKER
    private function make_address( $shipment, $prefix ) {
	    $output = '';
	    
	    if( isset($shipment[$prefix.'_NAME']))
			$output.= $shipment[$prefix.'_NAME'].'<br>';
	    if( isset($shipment[$prefix.'_ADDR1']))
			$output.= $shipment[$prefix.'_ADDR1'].'<br>';
	    if( isset($shipment[$prefix.'_ADDR2']))
			$output.= $shipment[$prefix.'_ADDR2'].'<br>';

		$elements = array();
	    if( isset($shipment[$prefix.'_CITY']))
			$elements[] = $shipment[$prefix.'_CITY'];
	    if( isset($shipment[$prefix.'_STATE']))
			$elements[] = $shipment[$prefix.'_STATE'];
	    if( isset($shipment[$prefix.'_ZIP']))
			$elements[] = $shipment[$prefix.'_ZIP'];
		if( count($elements) > 0 )
			$output .= implode(', ', $elements);

	    if( isset($shipment[$prefix.'_COUNTRY']))
			$output.= '<br>'.$shipment[$prefix.'_COUNTRY'];
			
		return $output;
    }
    
    // I found a case where an enum had a value '' (zero length string)
    // So far only seen in the shipment table
    // This finds it and sets it to the default value
    private function handle_enums( $table, $row ) {
	    $row2 = array();
	    foreach( $row as $label => $value ) {
		    if( in_array($table->column_type($label), array('enum', 'int', 'double')) &&
		    	in_array($value, array(NULL, '')) &&
		    	$table->get_default($label) !== false ) {
			    $row2[$label] = $table->get_default($label);
		    } else if( $table->column_type($label) == 'enum' && $value == '' && $table->is_nullable($label) ) {
			    $row2[$label] = NULL;
		    } else {
			    $row2[$label] = $value;
		    }
	    }
	    return $row2;
    }

	private function report_differences( $table, $file, $db ) {
		$output = '';
		if( count($file) <> count($db) ) {
			$output .= '<br>'.$table->table_name.' file has '.count($file).' rows but '.
				'database has '.count($db).' rows<br>'.PHP_EOL;
		} else {
			for( $c=0; $c<count($file); $c++ ) {
				$d1 = array_diff_assoc($file[$c], $db[$c]);
				$d2 = array_diff_assoc($db[$c], $file[$c]);
				$k1 = array_keys($d1);
				$k2 = array_keys($d2);
				$keys = array_unique(array_merge($k1, $k2));
				if( count($keys) > 0 ) {
					$output .= '<br>'.count($keys).' differences for '.$table->table_name.' '.
						$table->primary_key.' = '.$db[$c][$table->primary_key].'<br>
						<ul>'.PHP_EOL;
					sort($keys);
					foreach( $keys as $key ) {
			    		$output .= '<li>'.$key.' file='.var_export($file[$c][$key], true).' database='.var_export($db[$c][$key], true).'</li>'.PHP_EOL;
					}
					$output .= '</ul>'.PHP_EOL;
				}
			}
		}
		return $output;
	}
	
    
    //! ----------------- Shipments
    // Given a SHIPMENT_CODE, dump out all related tables
    public function fetch_shipment( $code ) {
		$this->log_event( __METHOD__.': entry, code = '.$code, EXT_ERROR_NOTICE );
	    if( $this->debug ) echo "<p>".__METHOD__.": entry, code = $code</p>";
	    $result = false;	    

	    $shipment_row = $this->shipment_table->fetch_rows( $this->shipment_table->primary_key." = ".$code );
	    if( is_array($shipment_row) && count($shipment_row) == 1 ) {
		    $result = array(
		    	"DB_SCHEMA"		=> $this->db_schema,
		    	"DB_VER"		=> $this->db_version,
		    	"ARCHIVED_BY"	=> $this->archived_by,
		    	"ARCHIVED_ON"	=> $this->archived_on,
		    	"ARCHIVED_FROM"	=> $this->archive_from,
		    	"ARCHIVED_TO"	=> $this->archive_to,
		    	"ARCHIVE_TYPE"	=> "shipment",
		    	"EXP_SHIPMENT"	=> $shipment_row
		    );
	
		    $shipment_load = $this->shipment_load_table->fetch_rows( "SHIPMENT_CODE = ".$code );
		    if( is_array($shipment_load) )
		    	$result["EXP_SHIPMENT_LOAD"] = $shipment_load;
		    
		    $details = $this->detail_table->fetch_rows( "SHIPMENT_CODE = ".$code );
		    if( is_array($details) )
		    	$result["EXP_DETAIL"] = $details;
		    
		    $state = $this->status_table->fetch_rows( "ORDER_CODE = ".$code." and SOURCE_TYPE = 'shipment'" );
		    if( is_array($state) )
		    	$result["EXP_STATUS"] = $state;
		    
		    $client_billing = $this->client_billing_table->fetch_rows( "SHIPMENT_ID = ".$code );
		    if( is_array($client_billing) )
		    	$result["EXP_CLIENT_BILLING"] = $client_billing;
		    
		    $attachments = $this->attachment_table->fetch_rows( "SOURCE_CODE = ".$code." and SOURCE_TYPE = 'shipment'" );
		    
		    if( is_array($attachments) ) {
			    $result["EXP_ATTACHMENT"] = $attachments;
			    $result["ATTACHMENT_FILES"] = $this->import_attachments( $attachments );
		    } 
	    } else {
	    	$this->last_error = 'Unable to find SHIPMENT_CODE = '.$code;
			$this->log_event( __METHOD__.': '.$this->last_error, EXT_ERROR_ERROR );
	    }
	    
		$this->log_event( __METHOD__.': return '.($result ? "shipment" : "false"), EXT_ERROR_DEBUG );
		if( $this->debug ) echo "<p>".__METHOD__.": return".($result ? "true" : "false")."</p>";
		return $result;	    
    }

    // Given a fetched shipment, compare it to the DB
    public function verify_shipment( $shipment ) {
	    
	    $result = false;
	    if( is_array($shipment) && is_array($shipment["EXP_SHIPMENT"]) &&
	    	is_array($shipment["EXP_SHIPMENT"][0]) &&
	    	! empty($shipment["EXP_SHIPMENT"][0]["SHIPMENT_CODE"])) {
		    	
		    $code = $shipment["EXP_SHIPMENT"][0]["SHIPMENT_CODE"];
			$this->log_event( __METHOD__.': entry, code = '.$code, EXT_ERROR_NOTICE );
			if( $this->debug ) echo "<p>".__METHOD__.": entry, code = $code</p>";
	    
		    $shipment_row = $this->shipment_table->fetch_rows( $this->shipment_table->primary_key." = ".$code );
		    
			if( is_array($shipment_row) && count($shipment_row) == 1 ) {
			    $shipment_load = $this->shipment_load_table->fetch_rows( "SHIPMENT_CODE = ".$code );
			    $details = $this->detail_table->fetch_rows( "SHIPMENT_CODE = ".$code );
			    $state = $this->status_table->fetch_rows( "ORDER_CODE = ".$code." and SOURCE_TYPE = 'shipment'" );
			    $client_billing = $this->client_billing_table->fetch_rows( "SHIPMENT_ID = ".$code );
			    $attachments = $this->attachment_table->fetch_rows( "SOURCE_CODE = ".$code." and SOURCE_TYPE = 'shipment'" );
			    
			    $attachment_files = array();
			    if( is_array($attachments) ) {
				    $attachment_files = $this->import_attachments( $attachments );
			    }
			    
			    $shipment["EXP_SHIPMENT"][0] = $this->handle_enums($this->shipment_table,$shipment["EXP_SHIPMENT"][0]);
			    $shipment_row[0] = $this->handle_enums($this->shipment_table,$shipment_row[0]);
			    
			    $result = $this->array_match( $shipment["EXP_SHIPMENT"], $shipment_row ) &&
			    	$this->array_match( $shipment["EXP_SHIPMENT_LOAD"], $shipment_load ) &&
			    	$this->array_match( $shipment["EXP_DETAIL"], $details ) &&
			    	$this->array_match( $shipment["EXP_STATUS"], $state ) &&
			    	$this->array_match( $shipment["EXP_CLIENT_BILLING"], $client_billing ) &&
			    	$this->array_match( $shipment["EXP_ATTACHMENT"], $attachments ) &&
			    	$this->array_match( $shipment["ATTACHMENT_FILES"], $attachment_files );
			    if( ! $result ) {
			    	$this->last_error .= 'SHIPMENT_CODE = '.$code.' does not match<br>';
			    	
		    		$this->last_error .= $this->report_differences( $this->shipment_table,$shipment["EXP_SHIPMENT"], $shipment_row );
		    		$this->last_error .= $this->report_differences( $this->shipment_load_table, $shipment["EXP_SHIPMENT_LOAD"], $shipment_load );
		    		
		    		$this->last_error .= $this->report_differences( $this->detail_table, $shipment["EXP_DETAIL"], $details );
		    		$this->last_error .= $this->report_differences( $this->status_table, $shipment["EXP_STATUS"], $state );
		    		$this->last_error .= $this->report_differences( $this->client_billing_table, $shipment["EXP_CLIENT_BILLING"], $client_billing );
		    		$this->last_error .= $this->report_differences( $this->attachment_table, $shipment["EXP_ATTACHMENT"], $attachments );
		    					    	
			    }
		    } else {
		    	$this->last_error .= 'Unable to find SHIPMENT_CODE = '.$code;
				$this->log_event( __METHOD__.': '.$this->last_error, EXT_ERROR_ERROR );
		    }
	    } else {
	    	$this->last_error .= 'Unable to find SHIPMENT_CODE';
			$this->log_event( __METHOD__.': '.$this->last_error, EXT_ERROR_ERROR );
	    }

		$this->log_event( __METHOD__.': return '.($result ? "true" : "false"), EXT_ERROR_DEBUG );
		if( $this->debug ) echo "<p>".__METHOD__.": return".($result ? "true" : "false")."</p>";
		return $result;	    
    }

    // Given a SHIPMENT_CODE, delete from all related tables
    public function delete_shipment( $code ) {
	    if( $this->debug ) echo "<p>".__METHOD__.": entry, code = $code</p>";
		$this->log_event( __METHOD__.': entry, code = '.$code, EXT_ERROR_NOTICE );

	    $result = false;	    
	    $check = $this->shipment_table->fetch_rows( $this->shipment_table->primary_key." = ".$code, $this->shipment_table->primary_key );
	    
	    if( is_array($check) && count($check) == 1 ) {
		    // Found
		    $result = $this->shipment_load_table->delete_row( "SHIPMENT_CODE = ".$code );
		    $result = $result && $this->detail_table->delete_row( "SHIPMENT_CODE = ".$code );
		    $result = $result && $this->status_table->delete_row( "ORDER_CODE = ".$code." and SOURCE_TYPE = 'shipment'" );
		    $result = $result && $this->client_billing_table->delete_row( "SHIPMENT_ID = ".$code );
		    
		    $attachments = $this->attachment_table->fetch_rows( "SOURCE_CODE = ".$code." and SOURCE_TYPE = 'shipment'" );
		    
		    if( is_array($attachments) ) {
			    $result = $result && $this->delete_attachments( $attachments );
		    }
		    
		    // Finally delete the shipment itself
		    $result = $result && $this->shipment_table->delete_row( "SHIPMENT_CODE = ".$code );
	    } else {
	    	$this->last_error = 'Unable to find SHIPMENT_CODE = '.$code;
			$this->log_event( __METHOD__.': '.$this->last_error, EXT_ERROR_ERROR );
	    }

		$this->log_event( __METHOD__.': return '.($result ? "true" : "false"), EXT_ERROR_DEBUG );
		if( $this->debug ) echo "<p>".__METHOD__.": return".($result ? "true" : "false")."</p>";
		return $result;	    
	}
	
    // Given a fetched shipment, insert it into the DB
    public function insert_shipment( $shipment ) {
	    
	    $result = false;
	    if( is_array($shipment) && is_array($shipment["EXP_SHIPMENT"]) &&
	    	is_array($shipment["EXP_SHIPMENT"][0]) &&
	    	! empty($shipment["EXP_SHIPMENT"][0]["SHIPMENT_CODE"])) {
		    	
		    $code = $shipment["EXP_SHIPMENT"][0]["SHIPMENT_CODE"];
			$this->log_event( __METHOD__.': entry, code = '.$code, EXT_ERROR_NOTICE );
			if( $this->debug ) echo "<p>".__METHOD__.": entry, code = $code</p>";
			$this->user_log_table->log_event('admin', 'Restore shipment '.$code);
			
			$check = $this->shipment_table->fetch_rows( $this->shipment_table->primary_key." = ".$code, $this->shipment_table->primary_key );
			if( ! is_array($check) || count($check) == 0 ) {
				//echo "<p>".__METHOD__.": Shipment $code not found</p>";
				$result = true;

			    // Insert the shipment itself
		    	$result = $result && $this->shipment_table->add( $this->handle_enums($this->shipment_table, $shipment["EXP_SHIPMENT"][0]) );

			    if( is_array($shipment["EXP_SHIPMENT_LOAD"]) && count($shipment["EXP_SHIPMENT_LOAD"]) > 0 ) {
				    foreach( $shipment["EXP_SHIPMENT_LOAD"] as $row ) {
				    	$result = $result && $this->shipment_load_table->add( $row );
				    }
			    }
			    
			    if( is_array($shipment["EXP_DETAIL"]) && count($shipment["EXP_DETAIL"]) > 0 ) {
				    foreach( $shipment["EXP_DETAIL"] as $row ) {
				    	$result = $result && $this->detail_table->add( $row );
				    }
			    }
			    
			    // Trigger on add shipment row creates unwanted status. Remove it.
				$result = $result && $this->status_table->delete_row( "ORDER_CODE = ".$code." and SOURCE_TYPE = 'shipment'" );
			    if( is_array($shipment["EXP_STATUS"]) && count($shipment["EXP_STATUS"]) > 0 ) {
				    foreach( $shipment["EXP_STATUS"] as $row ) {
				    	$result = $result && $this->status_table->add( $row );
				    }
			    }
			    
			    if( is_array($shipment["EXP_CLIENT_BILLING"]) && count($shipment["EXP_CLIENT_BILLING"]) > 0 ) {
				    foreach( $shipment["EXP_CLIENT_BILLING"] as $row ) {
				    	$result = $result && $this->client_billing_table->add( $row );
				    }
			    }
			    		    
			    if( is_array($shipment["EXP_ATTACHMENT"]) && count($shipment["EXP_ATTACHMENT"]) > 0 ) {
					$result = $result && $this->insert_attachments( $shipment["EXP_ATTACHMENT"], $shipment["ATTACHMENT_FILES"] );
			    }
			    
	    	} else {
		    	$this->last_error = 'Shipment '.$code.' exists, cannot insert shipment';
				$this->log_event( __METHOD__.': '.$this->last_error, EXT_ERROR_ERROR );
				//echo "<p>".__METHOD__.": Shipment $code exists</p>";
	    	}
	    } else {
	    	$this->last_error = 'Unable to find SHIPMENT_CODE';
			$this->log_event( __METHOD__.': '.$this->last_error, EXT_ERROR_ERROR );
	    }

		$this->log_event( __METHOD__.': return '.($result ? "true" : "false"), EXT_ERROR_DEBUG );
		if( $this->debug ) echo "<p>".__METHOD__.": return".($result ? "true" : "false")."</p>";
		return $result;	    
	}

    // Given a fetched shipment, view it
    public function view_shipment( $shipment ) {
	    
	    $output = '';
	    if( is_array($shipment) && is_array($shipment["EXP_SHIPMENT"]) &&
	    	is_array($shipment["EXP_SHIPMENT"][0]) &&
	    	! empty($shipment["EXP_SHIPMENT"][0]["SHIPMENT_CODE"])) {
		    	
		    $code = $shipment["EXP_SHIPMENT"][0]["SHIPMENT_CODE"];
			$this->log_event( __METHOD__.': entry, code = '.$code, EXT_ERROR_DEBUG );
			if( $this->debug ) echo "<p>".__METHOD__.": entry, code = $code</p>";
		
			$shipment_row = $shipment["EXP_SHIPMENT"][0];
			$office = $this->cl($shipment_row, "SS_NUMBER");
			
			$output .= '<table width="90%" align="center" border="1" cellspacing="0" cellpadding="0">
				<tbody>
				<tr valign="top"><td>
				<table width="100%" align="center" border="0" cellspacing="0" cellpadding="4">
			<tbody>
			<tr valign="top"><td></td><th align="left" class="text-left">Shipment#</th><th align="right" class="text-right">'.$code.(empty($office) ? '': ' / '.$office).'</th>
				<th align="left" class="text-left">Created</th><td align="right" class="text-right">'.$shipment_row["CREATED_DATE"].'</td></tr>
			<tr valign="top"><td></td><th align="left" class="text-left">Status</th><td align="right" class="text-right">'.
				$this->shipment_table->state_name[$shipment_row["CURRENT_STATUS"]].'</td>
				<th align="left" class="text-left">Changed</th><td align="right" class="text-right">'.$shipment_row["CHANGED_DATE"].'</td></tr>
			<tr valign="top"><td></td><th align="left" class="text-left">Billing Status</th><td align="right" class="text-right">'.
				$this->shipment_table->billing_state_name[$shipment_row["BILLING_STATUS"]].'</td>
				<th align="left" class="text-left">Actual Delivery</th><td align="right" class="text-right">'.
					$shipment_row["ACTUAL_DELIVERY"].'</td></tr>

			<tr valign="top"><td></td><th align="left" class="text-left">#Commodities</th><td align="right" class="text-right">'.
				count($shipment["EXP_DETAIL"]).'</td>
				<th align="left" class="text-left">#History</th><td align="right" class="text-right">'.
				count($shipment["EXP_STATUS"]).'</td></tr>

			<tr valign="top"><td></td><th align="left" class="text-left">Shipper</th>
				<th align="left" class="text-left">Consignee</th>
				<th align="left" class="text-left">Bill-to</th><td></td></tr>
			
			<tr valign="top"><td></td><td align="left" class="text-left">'.
				$this->make_address( $shipment_row, 'SHIPPER').'</td>
				<td align="left" class="text-left"">'.
				$this->make_address( $shipment_row, 'CONS').'</td>
				<td align="left" class="text-left"">'.
				$this->make_address( $shipment_row, 'BILLTO').'</td>
				</td></tr>
				</tr>
		
		
			</tbody>
			</table>
		    </td></tr>
			</tbody>
			</table>
			<br>
			';
	    } else {
	    	$this->last_error = 'Unable to find SHIPMENT_CODE';
			$this->log_event( __METHOD__.': '.$this->last_error, EXT_ERROR_ERROR );
	    }
	
		return $output;
	}
	
	// Fetch a shipment and store it in a file
	public function archive_shipment( $code ) {
		if( $this->debug ) echo "<p>".__METHOD__.": entry, code = $code</p>";
		$this->log_event( __METHOD__.': entry, code = '.$code, EXT_ERROR_NOTICE );
		
		$result = false;
		$contents = $this->fetch_shipment( $code );
		
		if( $contents != false ) {
			$subdir_path = $this->archive_dir.$this->archive_subdir;
			if( file_exists($subdir_path) &&
				is_dir($subdir_path) &&
				is_writeable($subdir_path)) {
				$this->archive_file = $subdir_path."SHIPMENT_".$code.".json";
				$result = file_put_contents($this->archive_file, json_encode($contents, JSON_PRETTY_PRINT));
				
				if( $this->verify_file() ) {
					//echo "<p>Verified ok -> delete</p>";
					$result = $this->delete_shipment( $code );
					//echo "<p>delete ".($result ? "true" : "false")."</p>";
					$this->user_log_table->log_event('admin', 'Archive shipment '.$code.' into '.$this->archive_file);
				} else {
					$this->log_event( __METHOD__.': Verify load '.$code.' failed', EXT_ERROR_ERROR );
					//echo "<p>Verify failed</p>";
				}
				
			} else {
		    	$this->last_error = 'Archive Directory '.$this->archive_dir.' Missing or Un-Writeable';
				$this->log_event( __METHOD__.': '.$this->last_error, EXT_ERROR_ERROR );
				echo '<h2>Archive Directory Missing or Un-Writeable</h2>
				<p>The directory '.$this->archive_dir.' is either missing or un-writeable.</p>
				<p>Please have Exspeedite support investigate, and give them this message.</p>';
				die;
			}
		} else {
	    	$this->last_error = 'Nothing to archive. Shipment '.$code.' not found';
			$this->log_event( __METHOD__.': '.$this->last_error, EXT_ERROR_ERROR );
		}
		
		$this->log_event( __METHOD__.': return '.($result ? "true" : "false"), EXT_ERROR_DEBUG );
		if( $this->debug ) echo "<p>".__METHOD__.": return".($result ? "true" : "false")."</p>";
		return $result;
	}
	
    //! ----------------- Loads
    // Given a LOAD_CODE, dump out all related tables
    public function fetch_load( $code ) {
		$this->log_event( __METHOD__.': entry, code = '.$code, EXT_ERROR_NOTICE );
		if( $this->debug ) echo "<p>".__METHOD__.": entry, code = $code</p>";
	    $result = false;
	    $load_row = $this->load_table->fetch_rows( $this->load_table->primary_key." = ".$code );
	    
	    if( is_array($load_row) && count($load_row) == 1 ) {
		    $result = array(
		    	"DB_SCHEMA"		=> $this->db_schema,
		    	"DB_VER"		=> $this->db_version,
		    	"ARCHIVED_BY"	=> $this->archived_by,
		    	"ARCHIVED_ON"	=> $this->archived_on,
		    	"ARCHIVED_FROM"	=> $this->archive_from,
		    	"ARCHIVED_TO"	=> $this->archive_to,
		    	"ARCHIVE_TYPE"	=> "load",
		    	"EXP_LOAD"		=> $load_row
		    );
			
		    $stop = $this->stop_table->fetch_rows( "LOAD_CODE = ".$code, "*", "SEQUENCE_NO ASC" );
		    if( is_array($stop) )
		    	$result["EXP_STOP"] = $stop;
		    
		    $shipments = $this->shipment_table->fetch_rows( "LOAD_CODE = ".$code, "SHIPMENT_CODE" );
		    $shipment_list = array();
		    if( is_array($shipments) && count($shipments) > 0 ) {
			    foreach( $shipments as $row ) {
				    $shipment_list[] = $this->fetch_shipment( $row["SHIPMENT_CODE"]);
			    }
		    }
		    $result["SHIPMENTS"] = $shipment_list;
	
		    $state = $this->status_table->fetch_rows( "ORDER_CODE = ".$code." and SOURCE_TYPE = 'load'" );
		    if( is_array($state) )
		    	$result["EXP_STATUS"] = $state;
		    
		    $attachments = $this->attachment_table->fetch_rows( "SOURCE_CODE = ".$code." and SOURCE_TYPE = 'load'" );
		    
		    if( is_array($attachments) ) {
			    $result["EXP_ATTACHMENT"] = $attachments;
			    $result["ATTACHMENT_FILES"] = $this->import_attachments( $attachments );
		    }
	    } else {
	    	$this->last_error = 'Unable to find LOAD_CODE = '.$code;
			$this->log_event( __METHOD__.': '.$this->last_error, EXT_ERROR_ERROR );
	    }
	    
		$this->log_event( __METHOD__.': return '.($result ? "load" : "false"), EXT_ERROR_DEBUG );
		if( $this->debug ) echo "<p>".__METHOD__.": return".($result ? "true" : "false")."</p>";
		return $result;	    
	}
	
    // Given a fetched load, compare it to the DB
    public function verify_load( $load ) {
	    
	    $result = false;
	    if( is_array($load) && is_array($load["EXP_LOAD"]) &&
	    	is_array($load["EXP_LOAD"][0]) &&
	    	! empty($load["EXP_LOAD"][0]["LOAD_CODE"])) {
		    	
		    $code = $load["EXP_LOAD"][0]["LOAD_CODE"];
			$this->log_event( __METHOD__.': entry, code = '.$code, EXT_ERROR_NOTICE );
			if( $this->debug ) echo "<p>".__METHOD__.": entry, code = $code</p>";
	    
		    $load_row = $this->load_table->fetch_rows( $this->load_table->primary_key." = ".$code );

			if( is_array($load_row) && count($load_row) == 1 ) {
			    $stop = $this->stop_table->fetch_rows( "LOAD_CODE = ".$code, "*", "SEQUENCE_NO ASC" );
	
			    $shipments_verified = true;
			    if( is_array($load["SHIPMENTS"]) && count($load["SHIPMENTS"]) > 0 ) {
				    foreach( $load["SHIPMENTS"] as $row ) {
					    $shipments_verified = $shipments_verified && $this->verify_shipment( $row );
				    }
			    }
	
			    $state = $this->status_table->fetch_rows( "ORDER_CODE = ".$code." and SOURCE_TYPE = 'load'" );
			    $attachments = $this->attachment_table->fetch_rows( "SOURCE_CODE = ".$code." and SOURCE_TYPE = 'load'" );
			    
			    $attachment_files = array();
			    if( is_array($attachments) ) {
				    $attachment_files = $this->import_attachments( $attachments );
			    }
			    
			    $load["EXP_LOAD"][0] = $this->handle_enums($this->load_table,$load["EXP_LOAD"][0]);
			    $load_row[0] = $this->handle_enums($this->load_table,$load_row[0]);

			    $result = $this->array_match( $load["EXP_LOAD"], $load_row ) &&
			    	$this->array_match( $load["EXP_STOP"], $stop ) &&
			    	$shipments_verified &&
			    	$this->array_match( $load["EXP_STATUS"], $state ) &&
			    	$this->array_match( $load["EXP_ATTACHMENT"], $attachments ) &&
			    	$this->array_match( $load["ATTACHMENT_FILES"], $attachment_files );
			    if( ! $result ) {
				    $save_shipments_error = $this->last_error;
			    	$this->last_error = 'LOAD_CODE = '.$code.' does not match<br>';

		    		$this->last_error .= $this->report_differences( $this->load_table,$load["EXP_LOAD"], $load_row );
		    		$this->last_error .= $this->report_differences( $this->stop_table, $load["EXP_STOP"], $stop );
		    		$this->last_error .= $save_shipments_error;
		    		$this->last_error .= $this->report_differences( $this->status_table, $load["EXP_STATUS"], $state );
		    		$this->last_error .= $this->report_differences( $this->attachment_table, $load["EXP_ATTACHMENT"], $attachments );
			    }
		    } else {
		    	$this->last_error = 'Unable to find LOAD_CODE = '.$code;
				$this->log_event( __METHOD__.': '.$this->last_error, EXT_ERROR_ERROR );
		    }
	    } else {
	    	$this->last_error = 'Unable to find LOAD_CODE';
			$this->log_event( __METHOD__.': '.$this->last_error, EXT_ERROR_ERROR );
	    }

		$this->log_event( __METHOD__.': return '.($result ? "true" : "false"), EXT_ERROR_DEBUG );
		if( $this->debug ) echo "<p>".__METHOD__.": return".($result ? "true" : "false")."</p>";
		return $result;	    
	}
	
    // Given a LOAD_CODE, delete from all related tables
    public function delete_load( $code ) {
	    if( $this->debug ) echo "<p>".__METHOD__.": entry, code = $code</p>";
		$this->log_event( __METHOD__.': entry, code = '.$code, EXT_ERROR_NOTICE );

	    $result = false;	    
	    $check = $this->load_table->fetch_rows( $this->load_table->primary_key." = ".$code, $this->load_table->primary_key );
	    
	    if( is_array($check) && count($check) == 1 ) {
		    // Found
		    $result = $this->stop_table->delete_row( "LOAD_CODE = ".$code );

		    $shipments = $this->shipment_table->fetch_rows( "LOAD_CODE = ".$code, "SHIPMENT_CODE" );
		    if( is_array($shipments) && count($shipments) > 0 ) {
			    foreach( $shipments as $row ) {
				    $result = $result && $this->delete_shipment( $row["SHIPMENT_CODE"] );
			    }
		    }

		    $result = $result && $this->status_table->delete_row( "ORDER_CODE = ".$code." and SOURCE_TYPE = 'load'" );
		    
		    $attachments = $this->attachment_table->fetch_rows( "SOURCE_CODE = ".$code." and SOURCE_TYPE = 'load'" );
		    
		    if( is_array($attachments) ) {
			    $result = $result && $this->delete_attachments( $attachments );
		    }
		    
		    // Finally delete the shipment itself
		    $result = $result && $this->load_table->delete_row( "LOAD_CODE = ".$code );
	    } else {
			$this->log_event( __METHOD__.': Unable to find LOAD_CODE = '.$code, EXT_ERROR_ERROR );
	    }

		$this->log_event( __METHOD__.': return '.($result ? "true" : "false"), EXT_ERROR_DEBUG );
		if( $this->debug ) echo "<p>".__METHOD__.": return".($result ? "true" : "false")."</p>";
		return $result;	    
	}
	
    // Given a fetched load, insert it into the DB
    public function insert_load( $load ) {
	    
	    $result = false;
	    if( is_array($load) && is_array($load["EXP_LOAD"]) &&
	    	is_array($load["EXP_LOAD"][0]) &&
	    	! empty($load["EXP_LOAD"][0]["LOAD_CODE"])) {
		    	
		    $code = $load["EXP_LOAD"][0]["LOAD_CODE"];
			$this->log_event( __METHOD__.': entry, code = '.$code, EXT_ERROR_NOTICE );
			if( $this->debug ) echo "<p>".__METHOD__.": entry, code = $code</p>";
			$this->user_log_table->log_event('admin', 'Restore load '.$code);
			
			$check = $this->load_table->fetch_rows( $this->load_table->primary_key." = ".$code, $this->load_table->primary_key );
			if( ! is_array($check) || count($check) == 0 ) {
				//echo "<p>".__METHOD__.": Load $code not found</p>";

			    // Insert the shipment itself
			    // Remove generated columns LUMPER_TOTAL, CARRIER_TOTAL
			    unset($load["EXP_LOAD"][0]["LUMPER_TOTAL"]);
			    unset($load["EXP_LOAD"][0]["CARRIER_TOTAL"]);
		    	$result = $this->load_table->add( $this->handle_enums($this->load_table, $load["EXP_LOAD"][0]) );

				//echo "<p>".__METHOD__.": before EXP_STOP ".($result ? "true" : "false")."</p>";
			    if( is_array($load["EXP_STOP"]) && count($load["EXP_STOP"]) > 0 ) {
				    foreach( $load["EXP_STOP"] as $row ) {
				    	$result = $result && $this->stop_table->add( $row );
				    }
			    }
			    
				//echo "<p>".__METHOD__.": before SHIPMENTS ".($result ? "true" : "false")."</p>";
			    if( is_array($load["SHIPMENTS"]) && count($load["SHIPMENTS"]) > 0 ) {
				    foreach( $load["SHIPMENTS"] as $row ) {
					    $result = $result && $this->insert_shipment( $row );
				    }
			    }

				//echo "<p>".__METHOD__.": before EXP_STATUS ".($result ? "true" : "false")."</p>";
			    // Trigger on add shipment row creates unwanted status. Remove it.
				$result = $result && $this->status_table->delete_row( "ORDER_CODE = ".$code." and SOURCE_TYPE = 'load'" );
			    if( is_array($load["EXP_STATUS"]) && count($load["EXP_STATUS"]) > 0 ) {
				    foreach( $load["EXP_STATUS"] as $row ) {
				    	$result = $result && $this->status_table->add( $row );
				    }
			    }
			    
				//echo "<p>".__METHOD__.": before EXP_ATTACHMENT ".($result ? "true" : "false")."</p>";
			    if( is_array($load["EXP_ATTACHMENT"]) && count($load["EXP_ATTACHMENT"]) > 0 ) {
					$result = $result && $this->insert_attachments( $load["EXP_ATTACHMENT"], $load["ATTACHMENT_FILES"] );
			    }
			    
				//echo "<p>".__METHOD__.": after EXP_ATTACHMENT ".($result ? "true" : "false")."</p>";
	    	} else {
		    	$this->last_error = 'Load '.$code.' exists, cannot insert load';
				$this->log_event( __METHOD__.': '.$this->last_error, EXT_ERROR_ERROR );
				//echo "<p>".__METHOD__.": Load $code exists</p>";
	    	}
	    } else {
	    	$this->last_error = 'Unable to find LOAD_CODE';
			$this->log_event( __METHOD__.': '.$this->last_error, EXT_ERROR_ERROR );
	    }

		$this->log_event( __METHOD__.': return '.($result ? "true" : "false"), EXT_ERROR_DEBUG );
		if( $this->debug ) echo "<p>".__METHOD__.": return".($result ? "true" : "false")."</p>";
		return $result;	    
	}

    // Given a fetched load, view it
    public function view_load( $load ) {
	    
	    $output = '';
	    if( is_array($load) && is_array($load["EXP_LOAD"]) &&
	    	is_array($load["EXP_LOAD"][0]) &&
	    	! empty($load["EXP_LOAD"][0]["LOAD_CODE"])) {
		    	
		    $code = $load["EXP_LOAD"][0]["LOAD_CODE"];
			$this->log_event( __METHOD__.': entry, code = '.$code, EXT_ERROR_DEBUG );
			if( $this->debug ) echo "<p>".__METHOD__.": entry, code = $code</p>";
		
			$load_row = $load["EXP_LOAD"][0];
			
			$output .= '<table width="90%" align="center" border="1" cellspacing="0" cellpadding="0">
				<tbody>
				<tr valign="top"><td>
			<table width="100%" align="center" border="0" cellspacing="0" cellpadding="4">
			<tbody>
			<tr valign="top"><th align="left" class="text-left">Load#</th><th align="right" class="text-right">'.$code.'</th>
				<th align="left" class="text-left">Created</th><td align="right" class="text-right">'.$load_row["CREATED_DATE"].'</td></tr>
			<tr valign="top"><th align="left" class="text-left">Status</th><td align="right" class="text-right">'.
				$this->load_table->state_name[$load_row["CURRENT_STATUS"]].'</td>
				<th align="left" class="text-left">Dispatched</th><td align="right" class="text-right">'.$load_row["DISPATCHED_DATE"].'</td></tr>
			<tr valign="top"><th align="left" class="text-left">Current Stop</th><td align="right" class="text-right">'.
				$load_row["CURRENT_STOP"].'</td>
				<th align="left" class="text-left">Completed</th><td align="right" class="text-right">'.$load_row["COMPLETED_DATE"].'</td></tr>

			<tr valign="top"><th align="left" class="text-left">#Stops</th><td align="right" class="text-right">'.
				count($load["EXP_STOP"]).'</td>
				<th align="left" class="text-left">Changed</th><td align="right" class="text-right">'.$load_row["CHANGED_DATE"].'</td></tr>
		
			<tr valign="top"><th align="left" class="text-left">#Shipments</th><td align="right" class="text-right">'.count($load["SHIPMENTS"]).'</td>
				<th align="left" class="text-left">Total Distance</th><td align="right" class="text-right">'.$load_row["TOTAL_DISTANCE"].'</td></tr>
		
			</tbody>
			</table>
			<br>
			';

		    if( is_array($load["EXP_STOP"]) && count($load["EXP_STOP"]) > 0 ) {
				$output .= '<table width="90%" align="center" border="0" cellspacing="0" cellpadding="4">
				<tbody>
				';
			    
			    
			    foreach( $load["EXP_STOP"] as $row ) {
				    $output .= '<tr valign="top"><td>'.$row["SEQUENCE_NO"].'</td>
					    <td>'.$row["STOP_TYPE"].'</td>
					    <td>'.$row["SHIPMENT"].'</td>
					    <td>'.$this->stop_table->state_name[$row["CURRENT_STATUS"]].'</td>
					    <td>'.$row["ACTUAL_ARRIVE"].'</td>
					    <td>'.$row["ACTUAL_DEPART"].'</td></tr>';
			    }
			    $output .= '</tbody>
				</table>
				<br>
				';
		    }

		    if( is_array($load["SHIPMENTS"]) && count($load["SHIPMENTS"]) > 0 ) {
			    foreach( $load["SHIPMENTS"] as $row ) {
				    $output .= $this->view_shipment( $row );
			    }
		    }
		    $output .= '</td></tr>
			</tbody>
			</table>
			';
		}
	
		return $output;
	}
	
	// Fetch a load and store it in a file
	public function archive_load( $code ) {
		if( $this->debug ) echo "<p>".__METHOD__.": entry, code = $code</p>";
		$this->log_event( __METHOD__.': entry, code = '.$code, EXT_ERROR_NOTICE );

		$result = false;
		$contents = $this->fetch_load( $code );
		
		if( $contents != false ) {
			$subdir_path = $this->archive_dir.$this->archive_subdir;
			if( file_exists($subdir_path) &&
				is_dir($subdir_path) &&
				is_writeable($subdir_path)) {
				$this->archive_file = $subdir_path."LOAD_".$code.".json";
				$result = file_put_contents($this->archive_file, json_encode($contents, JSON_PRETTY_PRINT));
				
				if( $this->verify_file() ) {
					//echo "<p>Verified ok -> delete</p>";
					$result = $this->delete_load( $code );
					//echo "<p>delete ".($result ? "true" : "false")."</p>";
					$this->user_log_table->log_event('admin', 'Archive load '.$code.' into '.$this->archive_file);
				} else {
					$this->log_event( __METHOD__.': Verify load '.$code.' failed', EXT_ERROR_ERROR );
					//echo "<p>Verified failed</p>";
				}
				
			} else {
		    	$this->last_error = 'Archive Directory '.$this->archive_dir.' Missing or Un-Writeable';
				$this->log_event( __METHOD__.': '.$this->last_error, EXT_ERROR_ERROR );
				echo '<h2>Archive Directory Missing or Un-Writeable</h2>
				<p>The directory '.$this->archive_dir.' is either missing or un-writeable.</p>
				<p>Please have Exspeedite support investigate, and give them this message.</p>';
				die;
			}
		} else {
	    	$this->last_error = 'Nothing to archive. Load '.$code.' not found';
			$this->log_event( __METHOD__.': '.$this->last_error, EXT_ERROR_ERROR );
		}
		
		$this->log_event( __METHOD__.': return '.($result ? "true" : "false").PHP_EOL, EXT_ERROR_DEBUG );
		if( $this->debug ) echo "<p>".__METHOD__.": return".($result ? "true" : "false")."</p>";
		return $result;
	}
	
    //! ----------------- Files
	// Get the last archive path used
	public function last_archive_file() {
		return $this->archive_file;
	}
	
	// Get the last archive path used
	public function set_archive_subdir( $from, $to ) {
		$this->archive_subdir = date("Ymd_Hi", strtotime($this->archived_on)).'/';
		$result = mkdir($this->archive_dir.$this->archive_subdir);
		if( $result ) {
			$this->archive_from = $from;
			$this->archive_to = $to;
		}
		
		return $result ? $this->archive_subdir : false;
	}
	
	// Get the archive directory
	public function get_archive_dir() {
		return $this->archive_dir;
	}

	// Get the full path to the archive
	public function get_subdir_path() {
		return $this->archive_dir.$this->archive_subdir;
	}
	
	// Get a list of archives and files within them
	public function list_archives() {
		$result = false;
		if( file_exists($this->archive_dir) && is_dir($this->archive_dir) ) {
			$dirs = array_diff(scandir($this->archive_dir), array('..', '.', 'uploads'));
			if( is_array($dirs) && count($dirs) > 0 ) {
				$result = array();
				foreach($dirs as $dir) {
					$subdir = $this->archive_dir.$dir;
					if( file_exists($subdir) && is_dir($subdir) ) {
						$result[$dir] = array_diff(scandir($subdir), array('..', '.'));
					}
				}
			}
		}
		return $result;
	}
	
	// Get the size of the files in the archive
	public function archive_size( $dir ) {
		$result = false;
		if( file_exists($this->archive_dir) && is_dir($this->archive_dir) ) {
			$subdir = $this->archive_dir.$dir;
			if( file_exists($subdir) && is_dir($subdir) ) {
				$files = array_diff(scandir($subdir), array('..', '.'));
				if( is_array($files) && count($files) > 0 ) {
					$result = array();
					foreach($files as $file) {
						$result[$file] = filesize($subdir.'/'.$file);
					}
				}
			}
		}
		return $result;
	}

	// Get the date range files in an archive
	public function get_range( $dir ) {
		$range = '';
		if( file_exists($this->archive_dir) && is_dir($this->archive_dir) ) {
			$subdir = $this->archive_dir.$dir;
			if( file_exists($subdir) && is_dir($subdir) ) {
				$files = array_diff(scandir($subdir), array('..', '.'));
				if( is_array($files) && count($files) > 0 ) {
					$file = $this->archive_dir.$dir.'/'.end($files);
					
					if( file_exists($file) && is_readable($file) ) {
						$contents = file_get_contents($file);
						if( ! empty($contents)) {
							$decoded = json_decode($contents, true);
							if( ! empty($decoded) ) {
								if( empty($range) && ! empty($decoded["ARCHIVED_FROM"]) &&
									! empty($decoded["ARCHIVED_TO"]) ) {
									$range = $decoded["ARCHIVED_FROM"].' - '.$decoded["ARCHIVED_TO"];
								}
							}
						}
					}
				}
			}
		}
		
		return $range;
	}

	// Read in a file in JSON, get list of loads and shipments
	public function file_contents( $file = false ) {
	    $loads = array();
	    $shipments = array();
	    $schema = '';
	    $range = 'unknown';
	    $this->last_error = '';
		if( $file == false )
			$file = $this->archive_file;
			
		if( $this->debug ) echo "<p>".__METHOD__.": entry, file = $file</p>";
		$this->log_event( __METHOD__.': entry, file = '.$file, EXT_ERROR_DEBUG );

		if( file_exists($file) && is_readable($file) ) {
			$contents = file_get_contents($file);
			if( ! empty($contents)) {
				$decoded = json_decode($contents, true);
				if( ! empty($decoded) ) {
					if( empty($schema) && ! empty($decoded["DB_SCHEMA"]) ) {
						$schema = $decoded["DB_SCHEMA"];
					}
					if( $range == 'unknown' && ! empty($decoded["ARCHIVED_FROM"]) &&
						! empty($decoded["ARCHIVED_TO"]) ) {
						$range = $decoded["ARCHIVED_FROM"].' - '.$decoded["ARCHIVED_TO"];
					}
					
					if( (! empty($decoded["ARCHIVE_TYPE"]) && $decoded["ARCHIVE_TYPE"] == 'load') ) {
						$loads[] = $decoded["EXP_LOAD"][0]["LOAD_CODE"];
						$sh = $decoded["SHIPMENTS"];
						if( is_array($sh) && count($sh) > 0 ) {
							foreach($sh as $row) {
								$code = $row["EXP_SHIPMENT"][0]["SHIPMENT_CODE"];
								$ss = $this->cl($row["EXP_SHIPMENT"][0], "SS_NUMBER");
								$shipments[] = $code.(empty($ss) ? '' : '/'.$ss);
							}
						}
					} else {
						$code = $decoded["EXP_SHIPMENT"][0]["SHIPMENT_CODE"];
						$ss = $this->cl($decoded["EXP_SHIPMENT"][0], "SS_NUMBER");
						$shipments[] = $code.(empty($ss) ? '' : '/'.$ss);
					}
				}
			}
		}

		$this->log_event( __METHOD__.': exit, '.count($loads).' loads, '.count($shipments).' shipments'.PHP_EOL, EXT_ERROR_DEBUG );
		return array($loads, $shipments, $schema, $range);
	}

	// Read in a file in JSON, compare with DB
	public function verify_file( $file = false ) {
	    $result = false;
	    $this->last_error = '';
		if( $file == false )
			$file = $this->archive_file;
			
		if( $this->debug ) echo "<p>".__METHOD__.": entry, file = $file</p>";
		$this->log_event( __METHOD__.': entry, file = '.$file, EXT_ERROR_NOTICE );

		if( file_exists($file) && is_readable($file) ) {
			$contents = file_get_contents($file);
			if( ! empty($contents)) {
				$decoded = json_decode($contents, true);
				if( ! empty($decoded) ) {
					if( (! empty($decoded["ARCHIVE_TYPE"]) && $decoded["ARCHIVE_TYPE"] == 'load') )
						$result = $this->verify_load( $decoded );
					else
						$result = $this->verify_shipment( $decoded );
				}
			}
		}

		$this->log_event( __METHOD__.': return '.($result ? "true" : "false").PHP_EOL, EXT_ERROR_DEBUG );
		if( $this->debug ) echo "<p>".__METHOD__.": return".($result ? "true" : "false")."</p>";
		return $result;
	}

	// Read in a file in JSON, display info
	public function view_file( $file = false ) {
	    $result = '';
		if( $file == false )
			$file = $this->archive_file;
			
		if( $this->debug ) echo "<p>".__METHOD__.": entry, file = $file</p>";
		$this->log_event( __METHOD__.': entry, file = '.$file, EXT_ERROR_DEBUG );

		if( file_exists($file) && is_readable($file) ) {
			$contents = file_get_contents($file);
			if( ! empty($contents)) {
				$decoded = json_decode($contents, true);
				if( ! empty($decoded) ) {
					$result = '<table border="0" cellspacing="0" cellpadding="4">
			<tbody>
			<tr valign="top"><th align="left" class="text-left">File</th><td align="right" class="text-right">'.$file.'</td></tr>
			<tr valign="top"><th align="left" class="text-left">DB Schema</th><td align="right" class="text-right">'.$this->cl($decoded, "DB_SCHEMA").'</td></tr>
			<tr valign="top"><th align="left" class="text-left">DB Version</th><td align="right" class="text-right">'.$this->cl($decoded, "DB_VER").'</td></tr>
			<tr valign="top"><th align="left" class="text-left">Archived From</th><td align="right" class="text-right">'.$this->cl($decoded, "ARCHIVED_FROM", 'unknown').'</td></tr>
			<tr valign="top"><th align="left" class="text-left">Archived To</th><td align="right" class="text-right">'.$this->cl($decoded, "ARCHIVED_TO", 'unknown').'</td></tr>
			<tr valign="top"><th align="left" class="text-left">Archived By</th><td align="right" class="text-right">'.$this->cl($decoded, "ARCHIVED_BY").'</td></tr>
			<tr valign="top"><th align="left" class="text-left">Archived On</th><td align="right" class="text-right">'.$this->cl($decoded, "ARCHIVED_ON").'</td></tr>
			</tbody>
			</table>
			<br>
					
					';
					if( (! empty($decoded["ARCHIVE_TYPE"]) && $decoded["ARCHIVE_TYPE"] == 'load') )
						$result .= $this->view_load( $decoded );
					else
						$result .= $this->view_shipment( $decoded );
				}
			}
		}

		$this->log_event( __METHOD__.': return '.(empty($result) ? "nothing" : "output").PHP_EOL, EXT_ERROR_DEBUG );
		if( $this->debug ) echo "<p>".__METHOD__.": return".($result ? "true" : "false")."</p>";
		return $result;
	}

	// Read in a file in JSON, insert into DB
	public function restore_file( $file = false ) {
	    $result = false;
		if( $file != false )
			$this->archive_file = $file;
			
		if( $this->debug ) echo "<p>".__METHOD__.": entry, file = $this->archive_file</p>";
		$this->log_event( __METHOD__.': entry, file = '.$this->archive_file, EXT_ERROR_NOTICE );
			
		if( file_exists($this->archive_file) && is_readable($this->archive_file) ) {
			$contents = file_get_contents($this->archive_file);
			if( ! empty($contents)) {
				$decoded = json_decode($contents, true);
				if( ! empty($decoded) ) {
					if( (! empty($decoded["ARCHIVE_TYPE"]) && $decoded["ARCHIVE_TYPE"] == 'load') )
						$result .= $this->insert_load( $decoded );
					else
						$result .= $this->insert_shipment( $decoded );
				}
			}
		}

		$this->log_event( __METHOD__.': return '.($result ? "true" : "false").PHP_EOL, EXT_ERROR_DEBUG );
		if( $this->debug ) echo "<p>".__METHOD__.": return".($result ? "true" : "false")."</p>";
		return $result;
	}

	// Zip up an archive
	public function zip_archive( $dir ) {
		$this->log_event( __METHOD__.': entry, dir = '.$dir, EXT_ERROR_DEBUG );
		$result = false;
		
		if( class_exists('ZipArchive') ) {
			if( file_exists($this->archive_dir) && is_dir($this->archive_dir) ) {
				$subdir = $this->archive_dir.$dir;
				
				// Get real path for our folder
				$rootPath = realpath($subdir);
				$zipfile = $rootPath.'.zip';
				$range = $this->get_range( $dir );
				if( ! empty($range) ) {
					list($from,$to) = explode(' - ', $range);
					$range = date("Ymd", strtotime($from)).'_'.date("Ymd", strtotime($to)).'_';
				}
				$localzipfile = $this->db_schema.'_'.$range.$dir.'.zip';
				
				// Initialize archive object
				$zip = new ZipArchive();
				$zip->open($zipfile, ZipArchive::CREATE | ZipArchive::OVERWRITE);
				
				$zip->addEmptyDir($dir);
				
				$files = array_diff(scandir($subdir), array('..', '.'));
				
				foreach ($files as $file) {
			        $zip->addFile($rootPath.'/'.$file, $dir.'/'.$file);
	        		$this->log_event( __METHOD__.': add '.$file, EXT_ERROR_DEBUG );
				}
				
				// Zip archive will be created only after closing object
				$result = $zip->close();
			} else {
		    	$this->last_error = 'The directory '.$dir.' was not found';
				$this->log_event( __METHOD__.': '.$this->last_error, EXT_ERROR_ERROR );			
			}
		} else {
	    	$this->last_error = 'The PHP class ZipArchive does not exist';
			$this->log_event( __METHOD__.': '.$this->last_error, EXT_ERROR_ERROR );			
		}
		
		//$this->log_event( __METHOD__.': return '.($result ? "true" : "false").PHP_EOL, EXT_ERROR_DEBUG );
				
		if( $result ) {
			register_shutdown_function('unlink', $zipfile);
			ob_clean();	// YOU NEED THIS TO CLEAN OUTPUT !!!!		
			header("Content-type: application/zip"); 
		    header("Content-Disposition: attachment; filename=$localzipfile");
			header("Content-length: " . filesize($zipfile));
		    header("Pragma: no-cache"); 
		    header("Expires: 0");
		    readfile($zipfile);
		}
	}

	public function unzip_archive( $zipfile ) {
	
		if( $this->debug ) echo "<p>".__METHOD__.": entry, file = $zipfile</p>";
		$this->log_event( __METHOD__.': entry, file = '.$zipfile, EXT_ERROR_NOTICE );

		$zip = new ZipArchive;
		$zip->open($zipfile);
		$zip->extractTo($this->archive_dir.'uploads');
	    $result = $zip->close();
	    
	    if( $result ) {
		    $zip->open($zipfile);
		    $dir_name = $zip->getNameIndex(0);
		    $dir_name = substr($dir_name,0,-1); // lose the slash
			$zip->close();
			$newname = $this->archive_dir.pathinfo(basename($zipfile), PATHINFO_FILENAME);
			if( file_exists($newname)) {
				array_map('unlink', glob($newname."/*.json"));
				rmdir($newname);
			}
			
			$result = rename($this->archive_dir.'uploads/'.$dir_name, $newname);
			
			unlink($zipfile);
	    }
	    
		$this->log_event( __METHOD__.': return '.($result ? "true" : "false").PHP_EOL, EXT_ERROR_DEBUG );
		if( $this->debug ) echo "<p>".__METHOD__.": return".($result ? "true" : "false")."</p>";
		return $result;
	}
		

	
	public function upload_zip() {
		global $_FILES;
		
		$this->last_error = '';

		if( isset($_FILES["ZIP_FILE_NAME"]) && isset($_FILES["ZIP_FILE_NAME"]['name']) && $_FILES["ZIP_FILE_NAME"]['name'] <> '' ) {
			$zip_name = $_FILES["ZIP_FILE_NAME"]['name'];
			if( $this->debug ) echo "<p>".__METHOD__.": entry, file = $zip_name</p>";
			$this->log_event( __METHOD__.': entry, file = '.$zip_name, EXT_ERROR_NOTICE );
			if( $_FILES["ZIP_FILE_NAME"]['error'] != 0 ) { // upload error
				$this->last_error = $this->file_errors[$_FILES["ZIP_FILE_NAME"]['error']];
				$this->log_event( __METHOD__.': '.$this->last_error, EXT_ERROR_ERROR );
			} else {
				if( ! is_uploaded_file($_FILES["ZIP_FILE_NAME"]['tmp_name']) ) {
					$this->last_error = "not an HTTP upload";
					$this->log_event( __METHOD__.': '.$this->last_error, EXT_ERROR_ERROR );
				} else {
					if( $_FILES["ZIP_FILE_NAME"]['size'] > 0 ) {
						if( $_FILES["ZIP_FILE_NAME"]['size'] > $this->max_size ) {
							$this->last_error = "ZIP file is too big to load";
							$this->log_event( __METHOD__.': '.$this->last_error, EXT_ERROR_ERROR );
						} else {
							$ext = pathinfo($zip_name, PATHINFO_EXTENSION);
							if( $ext <> 'zip' ) {
								$this->last_error = $zip_name." needs to end in .zip";
								$this->log_event( __METHOD__.': '.$this->last_error, EXT_ERROR_ERROR );
							} else {
								$uploads = $this->archive_dir.'uploads';
								if( ! file_exists($uploads) ) {
									mkdir($uploads);
								}

								if( file_exists($uploads) && is_dir($uploads) &&
									is_writable($uploads)) {
									$full_path = $uploads.'/'.$zip_name;
									move_uploaded_file($_FILES["ZIP_FILE_NAME"]['tmp_name'],
										$full_path );
									$this->unzip_archive( $full_path );
								}
							}
						}
					}
				}
			}						
		}							
		
	}

    //! ----------------- Selection

	// Get an initial list of shipments and loads
	private function initial_pass( $from, $to, $cancel = false ) {
		$this->log_event( __METHOD__.': entry, from = '.$from.' to = '.$to.
			' cancel = '.($cancel ? 'true' : 'false'), EXT_ERROR_DEBUG );
			
		$load_table = sts_load::getInstance($this->database, $this->debug);
			
		$cancel_str = $cancel ? 'AND EXP_LOAD.CURRENT_STATUS = '.$load_table->behavior_state['cancel'] : '';

		$shipment_table = sts_shipment::getInstance($this->database, $this->debug);

		$cancel_str2 = $cancel ? 'AND EXP_SHIPMENT.CURRENT_STATUS = '.$shipment_table->behavior_state['cancel'] : '';

		$result1 = $this->database->get_multiple_rows( "SELECT EXP_LOAD.LOAD_CODE,
			COALESCE(SHIPMENT_CODE, 0) AS SHIPMENT_CODE
			FROM EXP_LOAD
            LEFT JOIN EXP_SHIPMENT
            ON EXP_LOAD.LOAD_CODE = EXP_SHIPMENT.LOAD_CODE
            $cancel_str2
			WHERE DATE(EXP_LOAD.CHANGED_DATE) BETWEEN '".date("Y-m-d", strtotime($from)).
				"' AND '".date("Y-m-d", strtotime($to))."'
			$cancel_str
			ORDER BY 1 ASC, 2 ASC");
			
		$this->loads = array();
		$this->shipments = array();
		if( is_array($result1) && count($result1) > 0 ) {
			foreach( $result1 as $row ) {
				if( ! in_array($row["LOAD_CODE"], $this->loads))
					$this->loads[] = $row["LOAD_CODE"];				// List of loads
				if( $row["SHIPMENT_CODE"] > 0 )
					$this->shipments[] = $row["SHIPMENT_CODE"];		// Shipments in those loads
			}
		}
			
		// Find shipments not in loads (LOAD_CODE = 0)
		$result2 = $this->database->get_multiple_rows( "SELECT SHIPMENT_CODE
			FROM EXP_SHIPMENT
			WHERE DATE(CHANGED_DATE) BETWEEN '".date("Y-m-d", strtotime($from)).
				"' AND '".date("Y-m-d", strtotime($to))."'
			$cancel_str2
			AND LOAD_CODE = 0
			ORDER BY 1 ASC");
		
		if( is_array($result2) && count($result2) > 0 ) {
			foreach( $result2 as $row ) {
				if( ! in_array($row["SHIPMENT_CODE"], $this->shipments))
				$this->shipments[] = $row["SHIPMENT_CODE"];
			}
		}
	
		//echo "<pre>Initial pass\n";
		//var_dump(count($this->loads), implode(', ', $this->loads));
		//var_dump(count($this->shipments), implode(', ', $this->shipments));
		//echo "</pre>";

		$this->log_event( __METHOD__.': exit, '.count($this->loads).' loads, '.count($this->shipments).' shipments', EXT_ERROR_DEBUG );
	}

	public function find_crossdock() {
		$this->log_event( __METHOD__.': entry, '.count($this->loads).' loads, '.count($this->shipments).' shipments', EXT_ERROR_DEBUG );

		$gotmore = false;
		if( count($this->shipments) > 0 && count($this->loads) > 0 ) {
			$result1 = $this->database->get_multiple_rows( "SELECT DISTINCT LOAD_CODE
				FROM EXP_SHIPMENT_LOAD
				WHERE SHIPMENT_CODE IN (".implode(', ', $this->shipments).")
				AND LOAD_CODE NOT IN (".implode(', ', $this->loads).")");
	
			if( is_array($result1) && count($result1) > 0 ) {
				$gotmore = true;
				foreach( $result1 as $row ) {
					$this->loads[] = $row["LOAD_CODE"];				// Additional loads
				}
	
				$result2 = $this->database->get_multiple_rows( "SELECT SHIPMENT_CODE
					FROM EXP_SHIPMENT
					WHERE LOAD_CODE IN (".implode(', ', $this->loads).")
					AND SHIPMENT_CODE NOT IN (".implode(', ', $this->shipments).")");
		
				if( is_array($result2) && count($result2) > 0 ) {
					foreach( $result2 as $row ) {
						$this->shipments[] = $row["SHIPMENT_CODE"];	// Additional shipments
					}
				}
			}
		}

		//echo "<pre>After xdock\n";
		//var_dump($gotmore);
		//var_dump(count($this->loads), implode(', ', $this->loads));
		//var_dump(count($this->shipments), implode(', ', $this->shipments));
		//echo "</pre>";
		
		$this->log_event( __METHOD__.': exit, '.count($this->loads).' loads, '.count($this->shipments).' shipments', EXT_ERROR_DEBUG );
		return $gotmore;
	}

	private function find_consolidate() {
		$this->log_event( __METHOD__.': entry, '.count($this->loads).' loads, '.count($this->shipments).' shipments', EXT_ERROR_DEBUG );

		$gotmore = false;
		if( count($this->shipments) > 0 && count($this->loads) > 0 ) {
			$result1 = $this->database->get_multiple_rows( "SELECT DISTINCT CONSOLIDATE_NUM
				FROM EXP_SHIPMENT
				WHERE SHIPMENT_CODE IN (".implode(', ', $this->shipments).")
				AND COALESCE(CONSOLIDATE_NUM, 0) > 0" );
			
			$cons = array();			// Consolidate #
			if( is_array($result1) && count($result1) > 0 ) {
				foreach( $result1 as $row ) {
					$cons[] = $row["CONSOLIDATE_NUM"];
				}
			}
			
			// list of additional shipment# consolidated with shipments
			if( count($cons) > 0 ) {
				$result2 = $this->database->get_multiple_rows( "SELECT SHIPMENT_CODE, LOAD_CODE
					FROM EXP_SHIPMENT
					WHERE CONSOLIDATE_NUM IN (".implode(', ', $cons).")
					AND SHIPMENT_CODE NOT IN (".implode(', ', $this->shipments).")");

				if( is_array($result2) && count($result2) > 0 ) {
					$gotmore = true;
					foreach( $result2 as $row ) {
						$this->shipments[] = $row["SHIPMENT_CODE"];
						if( ! in_array($row["LOAD_CODE"], $this->loads) && $row["LOAD_CODE"] > 0 )
							$this->loads[] = $row["LOAD_CODE"];
					}
				}
			}
		}

		//echo "<pre>After consolidate\n";
		//var_dump($gotmore);
		//var_dump(count($this->loads), implode(', ', $this->loads));
		//var_dump(count($this->shipments), implode(', ', $this->shipments));
		//echo "</pre>";
		
		$this->log_event( __METHOD__.': exit, '.count($this->loads).' loads, '.count($this->shipments).' shipments', EXT_ERROR_DEBUG );
		return $gotmore;
	}

	// Remove shipments that are included in loads
	private function omit_load_shipments() {
		$this->log_event( __METHOD__.': entry, '.count($this->loads).' loads, '.count($this->shipments).' shipments', EXT_ERROR_DEBUG );

		if( 0 ) {
			echo "<pre>Before omit_load_shipments\n";
			var_dump(count($this->loads), implode(', ', $this->loads));
			var_dump(count($this->shipments), implode(', ', $this->shipments));
			echo "</pre>";
		}
		if( count($this->shipments) > 0 && count($this->loads) > 0 ) {
			$result1 = $this->database->get_multiple_rows( "SELECT DISTINCT SHIPMENT_CODE
				FROM EXP_SHIPMENT
				WHERE SHIPMENT_CODE IN (".implode(', ', $this->shipments).")
				AND LOAD_CODE NOT IN (".implode(', ', $this->loads).")" );

			$this->shipments = array();
			if( is_array($result1) && count($result1) > 0 ) {
				foreach( $result1 as $row ) {
					$this->shipments[] = $row["SHIPMENT_CODE"];
				}
			}
		}

		$this->log_event( __METHOD__.': exit, '.count($this->loads).' loads, '.count($this->shipments).' shipments', EXT_ERROR_DEBUG );
		if( 0 ) {
			echo "<pre>After omit_load_shipments\n";
			var_dump(count($this->loads), implode(', ', $this->loads));
			var_dump(count($this->shipments), implode(', ', $this->shipments));
			echo "</pre>";
		}
	}

	// Get list of shipments and loads
	public function get_loads_shipments( $from, $to, $cancel = false ) {
		$this->log_event( __METHOD__.': entry, from = '.$from.' to = '.$to.
			' cancel = '.($cancel ? 'true' : 'false'), EXT_ERROR_NOTICE );

		$this->initial_pass( $from, $to, $cancel );
		
		$gotmore1 = $gotmore2 = false;
		//$loop = 1;
		do {
			//echo "<p>Iteration ".$loop++."</p>";
			$gotmore1 = $this->find_crossdock();
		
			$gotmore2 = $this->find_consolidate();
		} while( $gotmore1 || $gotmore2 );
	
		// Remove shipments that are included in loads
		$this->omit_load_shipments();
	
		//echo "<pre>Final\n";
		//var_dump(count($this->loads), implode(', ', $this->loads));
		//var_dump(count($this->shipments), implode(', ', $this->shipments));
		//echo "</pre>";

		$this->log_event( __METHOD__.': exit, '.count($this->loads).' loads, '.count($this->shipments).' shipments'.PHP_EOL, EXT_ERROR_DEBUG );
		return array($this->loads, $this->shipments);
	}
	
}

//! Form Specifications - For use with sts_form

$sts_form_archive_form = array(	//! $sts_form_archive_form
	'title' => '<span class="glyphicon glyphicon-export"></span> Create Archive',
	'action' => 'exp_add_archive.php',
	'cancel' => 'exp_view_archive.php',
	'name' => 'archive',
	'okbutton' => 'Create Archive',
	'cancelbutton' => 'Cancel',
		'layout' => '
	<div class="form-group">
		<div class="row">
			<div class="col-sm-10 col-sm-offset-1 bg-info">
				<p>This will ARCHIVE and REMOVE all loads and shipments within a given date range. Be careful to only archive old loads and shipments that you no longer need to refer to. Doing so frees up resources and may improve performance.</p>
			</div>
		</div><br>
		<div class="col-sm-12">
			<div class="form-group">
				<label for="ARCHIVE_FROM" class="col-sm-2 control-label">#ARCHIVE_FROM#</label>
				<div class="col-sm-4">
					%ARCHIVE_FROM%
				</div>
				<div class="col-sm-4">
					<label>Start archiving from this date</label>
				</div>
			</div>
			<div class="form-group">
				<label for="ARCHIVE_TO" class="col-sm-2 control-label">#ARCHIVE_TO#</label>
				<div class="col-sm-4">
					%ARCHIVE_TO%
				</div>
				<div class="col-sm-4">
					<label>Archive up to this date</label>
				</div>
			</div>

			<div class="form-group">
				<label for="ARCHIVE_CANCEL" class="col-sm-2 control-label">#ARCHIVE_CANCEL#</label>
				<div class="col-sm-4">
					%ARCHIVE_CANCEL%
				</div>
				<div class="col-sm-4">
					<label>Only Archive Cancelled shipments / loads</label>
				</div>
			</div>

			<div class="row">
				<div class="col-sm-1">
					<img src="images/skull-crossbones.png" alt="skull-crossbones" width="83" height="84" />
				</div>
				<div class="col-sm-8">
					<p class="text-danger"> Disclaimer: Archiving <strong>REMOVES loads and shipments and all the related information</strong> and puts it all in a text file. While we do everything to ensure any archived data is safe, there is some RISK. Try at your peril, here be dragons - arrrgh!</p>
				</div>
				<div class="col-sm-1">
					<img src="images/skull-crossbones.png" alt="skull-crossbones" width="83" height="84" />
				</div>
			</div>
		</div>
	</div>
	
	'
);

$sts_form_archive_fields = array(
	'ARCHIVE_FROM' => array( 'label' => 'From', 'format' => 'date', 'placeholder' => 'mm/dd/yyyy', 'extras' => 'required' ),
	'ARCHIVE_TO' => array( 'label' => 'To', 'format' => 'date', 'placeholder' => 'mm/dd/yyyy', 'extras' => 'required' ),
	'ARCHIVE_CANCEL' => array( 'label' => 'Cancelled Only', 'format' => 'bool' ),
);

$sts_form_add_archive_zip_form = array(	//! $sts_form_add_archive_zip_form
	'title' => '<span class="glyphicon glyphicon-import"></span> Upload ZIP Archive',
	'action' => 'exp_view_archive.php',
	'cancel' => 'exp_view_archive.php',
	'name' => 'addzip',
	'okbutton' => 'Upload ZIP Archive',
	'cancelbutton' => 'Cancel',
		'layout' => '
	<div class="form-group">
		<div class="col-sm-9">
			<div class="form-group" id="FILE_GROUP">
				<label for="ZIP_FILE_NAME" class="col-sm-4 control-label">#ZIP_FILE_NAME#</label>
				<div class="col-sm-8">
					%ZIP_FILE_NAME%
				</div>
				<div class="col-sm-offset-4 col-sm-8" id="TOO_BIG" hidden>
				</div>
			</div>
		</div>
	</div>
		<div class="row">
			<div class="col-sm-10 col-sm-offset-1 bg-info">
				<p>This will Upload a ZIP file containing a previously downloaded ZIP file. This allows you to restore loads/shipments contained within.</p>
			</div>
		</div>
	
	'
);


//! Field Specifications - For use with sts_form

$sts_form_add_archive_zip_fields = array(
	'ZIP_FILE_NAME' => array( 'label' => 'ZIP Archive', 'format' => 'attachment', 'extras' => 'required' ),
);


//! Layout Specifications - For use with sts_result

$sts_result_archive_load_layout = array(
	'LOAD_CODE' => array( 'label' => 'Load#', 'format' => 'num0nc', 'link' => 'exp_viewload.php?CODE=', 'align' => 'right', 'target' => '_blank', 'length' => 60 ),
	'CHANGED_DATE' => array( 'label' => 'Last Changed', 'format' => 'timestamp', 'length' => 60 ),
	'CURRENT_STATUS' => array( 'label' => 'Status', 'format' => 'table',
		'table' => STATUS_CODES_TABLE, 'key' => 'STATUS_CODES_CODE', 'fields' => 'STATUS_STATE', 'length' => 90 ),
	'SHIPMENTS' => array( 'label' => 'Shipments', 'format' => 'text',
		'snippet' => '(SELECT GROUP_CONCAT(
		concat( \'<a href="exp_addshipment.php?CODE=\', SHIPMENT_CODE, \'" target="_blank">\', SHIPMENT_CODE, \'</a>\') 
		 ORDER BY SHIPMENT_CODE ASC SEPARATOR \', \') SHIPMENTS 
			FROM EXP_SHIPMENT
			WHERE EXP_SHIPMENT.LOAD_CODE = EXP_LOAD.LOAD_CODE)'
	)
);

$sts_result_archive_shipment_layout = array(
	'SHIPMENT_CODE' => array( 'label' => 'Shipment#', 'format' => 'num0nc', 'link' => 'exp_addshipment.php?CODE=', 'align' => 'right', 'target' => '_blank', 'length' => 60 ),
	'CHANGED_DATE' => array( 'label' => 'Last Changed', 'format' => 'timestamp', 'length' => 60 ),
	'CURRENT_STATUS' => array( 'label' => 'Status', 'format' => 'table',
		'table' => STATUS_CODES_TABLE, 'key' => 'STATUS_CODES_CODE', 'fields' => 'STATUS_STATE', 'length' => 90 ),
	'BILLING_STATUS' => array( 'label' => 'Billing', 'format' => 'table',
		'table' => STATUS_CODES_TABLE, 'key' => 'STATUS_CODES_CODE', 'fields' => 'STATUS_STATE', 'length' => 80 ),
	'AMOUNT' => array( 'label' => 'Bill Total', 'format' => 'table',
		'table' => CLIENT_BILL, 'key' => 'SHIPMENT_ID', 'pk' => 'SHIPMENT_CODE',
		'fields' => 'TOTAL', 'align' => 'right', 'length' => 80 ),
);

//! Edit/Delete Button Specifications - For use with sts_result

$sts_result_archive_load_edit = array(
	'title' => '<img src="images/load_icon.png" alt="load_icon" height="24"> ## Matching Loads (Links open in new window/tab)',
	'sort' => 'LOAD_CODE asc',
	//'cancel' => 'exp_add_archive.php',
	//'add' => 'exp_additem_list.php',
	//'actionextras' => 'disabled',
	//'addbutton' => 'Add Item',
	//'cancelbutton' => 'Back',
);

$sts_result_archive_shipment_edit = array(
	'title' => '<img src="images/order_icon.png" alt="order_icon" height="24"> ## Shipments Without Loads (Links open in new window/tab)',
	'sort' => 'SHIPMENT_CODE asc',
	//'cancel' => 'index.php',
	//'add' => 'exp_additem_list.php',
	//'actionextras' => 'disabled',
	//'addbutton' => 'Add Item',
	//'cancelbutton' => 'Back',
);

?>