<?php

// $Id: sts_table_class.php 5112 2023-05-25 18:10:11Z dev $
// Abstraction for table access

// no direct access
defined('_STS_INCLUDE') or die('Restricted access');

require_once( "sts_db_class.php" );
require_once( "sts_cache_class.php" );

ini_set('xdebug.var_display_max_depth', -1);
ini_set('xdebug.var_display_max_children', -1);
ini_set('xdebug.var_display_max_data', -1);

class sts_table {
	
	public $debug = false;
	private $encrypt_key = "FuzzyCat";	// 8 chars
	private $encrypt_key2 = "FuzzyCatFuzzyCatFuzzyCatFuzzyCat"; // 32 chars
	private $data_types;
	private $column_types;
	private $column_max_lengths;
	private $nullable;
	private $default;
	private $extra;
	private $last_error	= "";
	public $cache;
	private $raw_changes;
	private $trace = '';

	public $found_rows;
	public $total_rows;

	public $layout_fields = false;
	public $edit_fields = false;
	public $database;
	public $table_name = "undefined";
	public $primary_key = "undefined";
	public $extra_columns = array( 'CREATED_DATE', 'CHANGED_DATE', 'CREATED_BY', 'CHANGED_BY',
		'CARRIER_SINCE', 'START_DATE', 'CLIENT_SINCE' );

	public function __construct( $database, $table_name, $debug = false ) {
		global $sts_primary_keys;
		
		$this->debug = $debug;
		$this->database = $database;

		if( $this->debug ) echo "<h3>".__METHOD__.": entry, table_name = $table_name, debug = ".($debug ? 'true' : 'false')."</h3>";

		$this->table_name = $table_name;
		if( isset($sts_primary_keys[$this->table_name] ) )
			$this->primary_key = $sts_primary_keys[$this->table_name];
		
		//echo "<p>password_hash ".(function_exists('password_hash') ? 'found' : 'NOT found')."</p>
		//	<p>password_verify ".(function_exists('password_verify') ? 'found' : 'NOT found')."</p>";
			
		if( ! isset($_SESSION["TABLE_MISSING"])) {
			$_SESSION["TABLE_MISSING"] = false;
			$_SESSION["TABLE_MISSING_MSG"] = '';
			if( ! function_exists('password_hash') && 
				! function_exists('sodium_crypto_pwhash_str') &&
				! function_exists('mcrypt_create_iv') &&
				! function_exists('crypt') ) {
				$_SESSION["TABLE_MISSING"] = true;
				$_SESSION["TABLE_MISSING_MSG"] .= '<p><strong>Need either password_hash, sodium_crypto_pwhash_str (sodium), mcrypt or crpyt.</strong> These are used for hashing passwords.</p>
				<p>To setup sodium on xampp:</p>
				<ol>
				<li>Add "extension=sodium" to php.ini (no quotes)</li>
				<li>Copy php/libsodium.dll to apache/bin/</li>
				<li>Restart Server</li>
				</ol>
				';
			}
			
			if( ! function_exists('password_verify') && 
				! function_exists('sodium_crypto_pwhash_str_verify') &&
				! function_exists('crypt') ) {
				$_SESSION["TABLE_MISSING"] = true;
				$_SESSION["TABLE_MISSING_MSG"] .= '<p><strong>Need either password_verify, sodium_crypto_pwhash_str_verify (sodium), or crpyt.</strong> These are used for verifying passwords.</p>
				';
			}
			
			if( ! function_exists('base64_encode') && 
				! function_exists('base64_decode') ) {
				$_SESSION["TABLE_MISSING"] = true;
				$_SESSION["TABLE_MISSING_MSG"] .= '<p><strong>Need base64_encode and base64_decode</strong>. These are used for encoding data.</p>
				';
			}
			
			if(! (extension_loaded('mcrypt') || extension_loaded('sodium')) ) {
				$_SESSION["TABLE_MISSING"] = true;
				$_SESSION["TABLE_MISSING_MSG"] .= '<p><strong>Need mcrypt or sodium.</strong> These are used for encrypting and decrypting.<br>'.
				'mcrypt: '.(extension_loaded('mcrypt') ? 'loaded' : 'NOT loaded').
				', sodium: '.(extension_loaded('sodium') ? 'loaded' : 'NOT loaded').
				'<br>For PHP 7, mcrypt is not supported. We need to migrate to sodium.</p>
				<p>To setup sodium on xampp:</p>
				<ol>
				<li>Add "extension=sodium" to php.ini (no quotes)</li>
				<li>Copy php/libsodium.dll to apache/bin/</li>
				<li>Restart Server</li>
				</ol>
				';
			}

			if( ! extension_loaded('soap') ) {
				$_SESSION["TABLE_MISSING"] = true;
				$_SESSION["TABLE_MISSING_MSG"] .= '<p><strong>Need PHP SOAP module</strong>. These are used for accessing certain web services.</p>
				<p>To setup soap on xampp:</p>
				<ol>
				<li>Edit php.ini</li>
				<li>Search for "soap"</li>
				<li>Remove the semicolon at the start of the line</li>
				<li>Restart Server</li>
				</ol>
				';
			}
			
			if( ! extension_loaded('gd') ) {
				$_SESSION["TABLE_MISSING"] = true;
				$_SESSION["TABLE_MISSING_MSG"] .= '<p><strong>Need PHP GD module</strong>. These are used for Captcha.</p>
				<p>To setup GD on Red Hat / Centos:</p>
				<ol>
				<li>yum install php-gd</li>
				<li>systemctl restart httpd.service</li>
				</ol>
				';
			}
			
			$query = "SHOW VARIABLES LIKE '%lower_case_table_names%'";
			$check = $this->database->get_one_row($query);
			if( is_array($check) && isset($check['Value']) && $check['Value'] == '0' ) {
				$_SESSION["TABLE_MISSING"] = true;
				$_SESSION["TABLE_MISSING_MSG"] .= '<p><strong>Need MySQL setting lower_case_table_names set to 1</strong>. This allows case insensitive table names.</p>
				<p>Edit the my.ini file, add a line lower_case_table_names = 1</p>
				';
			}
			
		//	$query = "SET GLOBAL sql_mode=(SELECT REPLACE(@@sql_mode,'ONLY_FULL_GROUP_BY',''))";
		//	$check = $this->database->get_one_row($query);
		
		}
		
		if( $_SESSION["TABLE_MISSING"] ) {
			echo "<h2>Error: Missing Needed Extensions or Functions</h2>
				<ul>
				<p>There is a problem with the PHP configuration. Exspeedite cannot continue.</p>
				<p>Please contact Exspeedite support.</p>
				<p>PHP Configuration file is here: ".get_cfg_var('cfg_file_path') ."</p>
				<p>Issues</p>
				<ul>
				".$_SESSION["TABLE_MISSING_MSG"]."
				</ul>
				</ul>";
			unset($_SESSION["TABLE_MISSING"], $_SESSION["TABLE_MISSING_MSG"]);
			die;
			//	<p>".implode('<br>', get_loaded_extensions())."</p>
		}
		
		$this->cache = sts_cache::getInstance( $this->database, $this->debug );

		
		if( $database <> DUMMY_DATABASE)
			$this->get_column_types();
		
		if( $this->debug ) echo "<p>Create sts_table $this->table_name pk = $this->primary_key</p>";
	}

	function __destruct() {
		if( $this->debug ) echo "<p>Destroy sts_table $this->table_name</p>";
	}
	
	public function write_cache() {
		$this->cache->write_cache();
	}
	
	public function date_cached() {
		return $this->cache->date_cached();
	}
	
	private function get_column_types() {
	
		//if( $this->debug ) echo "<p>get_column_types for $this->table_name</p>";
		$cached = $this->cache->get_column_types( $this->table_name );
		
		if( is_array($cached) && count($cached) > 0 ) {
			$result = $cached;
		} else {
			$query = "SELECT COLUMN_NAME, DATA_TYPE, COLUMN_TYPE, CHARACTER_MAXIMUM_LENGTH,
				IS_NULLABLE, COLUMN_DEFAULT, EXTRA
				FROM INFORMATION_SCHEMA.COLUMNS
				WHERE table_name = '".$this->table_name."'
				AND table_schema = '".$this->database->schema()."'";
			//! Turn off debug for get_column_types
			if( $this->debug ) $tmp = $this->database->set_debug( false );
			$result = $this->database->get_multiple_rows($query);
			if( $this->debug ) $this->database->set_debug( $tmp );
		}
		
		if ( is_array($result) && count($result) > 0 ) {
			$this->data_types = array();
			$this->column_types = array();
			$this->nullable = array();
			$this->column_max_lengths = array();
			$this->default = array();
			$this->extra = array();
			foreach( $result as $row ) {
				$this->data_types[$row['COLUMN_NAME']] = $row['DATA_TYPE'];
				$this->column_types[$row['COLUMN_NAME']] = $row['COLUMN_TYPE'];
				$this->nullable[$row['COLUMN_NAME']] = $row['IS_NULLABLE'] == 'YES';
				if( isset($row['CHARACTER_MAXIMUM_LENGTH']) && $row['CHARACTER_MAXIMUM_LENGTH'] > 0 )
					$this->column_max_lengths[$row['COLUMN_NAME']] = $row['CHARACTER_MAXIMUM_LENGTH'];
				if( isset($row['COLUMN_DEFAULT']) )
					$this->default[$row['COLUMN_NAME']] = $row['COLUMN_DEFAULT'];
				if( ! empty($row['EXTRA']) )
					$this->extra[$row['COLUMN_NAME']] = $row['EXTRA'];
			}
			
			//if( $this->debug ) {
			//	echo "<p>column_types for $this->table_name = </p>
			//	<pre>";
			//	var_dump($this->data_types);
			//	echo "</pre>";
			//}
			return true;
		} else {
			if( $this->debug ) echo "<p>get_column_types failed for $this->table_name ".$this->error()."</p>";
			$save_error = $this->error();	// Need to save this as the next line clears it
			require_once( "sts_email_class.php" );
			
			$email = sts_email::getInstance($this->database, $this->debug);
			$email->send_alert(__METHOD__.": failed for $this->table_name ".$save_error, EXT_ERROR_ERROR);
			return false;
		}
	}

	public function set_debug( $debug ) {
		//echo "<p>".__METHOD__.": set debug to ".($debug ? "true" : "false")."</p>";
		$tmp = $this->debug;
		$this->debug = $debug;
		if( $this->cache ) $this->cache->set_debug( $debug );
		return $tmp;
	}
	
	// Fetch one or more rows
	public function fetch_rows( $match = "", $fields = "*", $order = "", $limit = "", $groupby = "", $match2 = "" ) {

		if( $this->debug ) echo "<h3>".__METHOD__.": $match</h3>";

		$query = "select $fields
		from $this->table_name 
		".($match <> "" ? "where $match" : "")."
		".($groupby <> "" ? "group by $groupby" : "")."
		".($order <> "" ? "order by $order" : "")."
		".($limit <> "" ? "limit $limit" : "");
		if( $this->debug ) {
			echo "<p>".__METHOD__.": query for $this->table_name = </p>
			<pre>";
			var_dump($query);
			echo "</pre>";
		}
		
		$result = $this->database->get_multiple_rows($query);
		
		if( strpos($fields, 'SQL_CALC_FOUND_ROWS') !== false) {
			$result1 = $this->database->get_one_row( "SELECT FOUND_ROWS() AS FOUND" );
			$this->found_rows = is_array($result1) && isset($result1["FOUND"]) ? $result1["FOUND"] : 0;
			if( $this->debug ) echo "<p>found_rows = $this->found_rows</p>";
		}

		if( $this->debug ) {
			echo "<p>".__METHOD__.": result for $this->table_name = </p>
			<pre>";
			var_dump($result);
			echo "</pre>";
		}
		return $result;
	}

	// Add one row
	public function add_row( $fields, $values ) {

		if( $this->debug ) echo "<p>".__METHOD__.": $fields, $values</p>";

		$query = "INSERT INTO ".$this->table_name."($fields)
		VALUES($values)";
		
		$result = $this->database->insert_row($query);
		
		if( $result === false ) {
			$save_error = $this->error();	// Need to save this as the next line clears it
			require_once( "sts_email_class.php" );

			$email = sts_email::getInstance($this->database, $this->debug);
			$email->send_alert(__METHOD__." failed: ".$save_error.
				"<br>Fields<pre>".print_r($fields, true)."</pre>".
				"<br>Values<pre>".print_r($values, true)."</pre>".
				"<br>Query<pre>".print_r($query, true)."</pre>".
				"<br>Result<pre>".print_r($result, true)."</pre>", EXT_ERROR_ERROR);
		}
		
		if( $this->debug ) echo "<p>".__METHOD__.": result = ".($result === false ? 'false '.$this->error() : $result )."</p>";
		return $result;
	}

	// Delete one row
	public function delete_row( $match ) {

		if( $this->debug ) echo "<p>".__METHOD__.": $match</p>";

		$query = "DELETE FROM $this->table_name
		WHERE $match";
		
		$result = $this->database->get_one_row($query);
		
		if( $this->debug ) echo "<p>".__METHOD__.": result = ".($result ? 'true' : 'false')."</p>";
		return $result;
	}
	
	// Update one row
	// $changes is an array of pairs (field, value)
	// value to be enclosed in quotes as needed.
	public function update_row( $match, $changes ) {

		if( $this->debug ) {
			echo "<p>".__METHOD__.": $match changes = </p>
			<pre>";
			var_dump($changes);
			echo "</pre>";
			echo "<h2>Trace</h2>".$this->trace;
		}

		$query = "UPDATE ".$this->table_name."
		";
		$first = true;
		foreach( $changes as $change ) {
			$query .= ($first ? "SET ":", ").$change["field"]." = ".$change["value"]."\n";
			$first = false;
		}
		
		$query .= "WHERE $match";
		
		if( $this->debug ) {
			echo "<p>".__METHOD__.": query = </p>
			<pre>";
			var_dump($query);
			echo "</pre>";
		}

		$result = $this->database->get_one_row($query);
		
		if( $result === false ) {
			$save_error = $this->error();	// Need to save this as the next line clears it
			require_once( "sts_email_class.php" );
			
			$email = sts_email::getInstance($this->database, $this->debug);
			$email->send_alert("sts_table > get_one_row failed: ".$save_error.
				"<br>Fields<pre>".print_r($match, true)."</pre>".
				"<br>Values<pre>".print_r($changes, true)."</pre>".
				"<br>Query<pre>".print_r($query, true)."</pre>".
				"<br>Result<pre>".print_r($result, true)."</pre>".
				"<br> php_sapi_name ".php_sapi_name().
				"<br>Cache info ".$this->cache->info().
				"<br>Raw changes<pre>".print_r($this->raw_changes, true)."</pre>".
				$this->trace, EXT_ERROR_ERROR);
		}

		if( $this->debug ) echo "<p>".__METHOD__.": result = ".($result ? 'true' : 'false')."</p>";
		return $result;
	}

	public function empty_trash( $dmatch = false ) {
		$result = false;
		if( $this->debug ) echo "<p>".__METHOD__.": entry</p>";
		if( $this->column_exists( 'ISDELETED' ) )
			$result = $this->delete_row( "ISDELETED = 1".($dmatch == false ? '' : ' AND '.$dmatch) );
		
		return $result;
	}
	
	// Delete - moved from sts_driver class
	public function delete( $code, $type = "" ) {

		if( $this->debug ) echo "<p>".__METHOD__.": $type, $code</p>";
		
		if( ! $this->column_exists( 'ISDELETED' ) ) $type = 'permdel';
		
		switch( $type ) {
			case 'del':		// Move to trash, or mark as deleted
				$result = $this->update( $code, array('ISDELETED' => true) );
				break;
				
			case 'undel':	// Recover from trash, unmark as deleted
				$result = $this->update( $code, array('ISDELETED' => false) );
				break;
				
			case 'permdel':	// Empty the trash, delete permanently
				$result = $this->delete_row( $this->primary_key." = '".$code."'" );
				break;
				
			default:
				break;
		}
		
		if( $this->debug ) echo "<p>".__METHOD__.": result = ".($result ? 'true' : 'false '.$this->error())."</p>";
		return $result;
	}

	// Update  - moved from sts_driver class
	public function update( $code, $values ) {
		global $_SESSION;

		if( $this->debug ) {
			echo "<p>".__METHOD__.": code, values = </p>
			<pre>";
			var_dump($code);
			var_dump($values);
			echo "</pre>";
		}
		$this->last_error = "";
		$this->raw_changes = $values;
		$this->trace = '';
		
		$changes = array();
		
		foreach( $values as $field => $value ) {
			if( $this->debug ) echo "<p>".__METHOD__.": foreach $field => $value</p>";
			$this->trace .= "<p>".__METHOD__.": foreach $field => $value</p>";
			$this->trace .= "<p>".__METHOD__.": is_nullable ".($this->is_nullable( $field ) ? "TRUE" : "FALSE")."</p>";
			$this->trace .= "<p>".__METHOD__.": is_boolean ".($this->is_boolean( $field ) ? "TRUE" : "FALSE")."</p>";

			/*if( $field == 'SOCIAL_NUMBER' )
				$new_value = $this->encryptData( $value );
			else*/
			//! Possible fix for <null> & <null
			if( in_array($value, array('', 'NULL', '<null>', '<null')) && $this->is_nullable( $field ) ) {
				$changes[] = array( "field" => $field, "value" => 'NULL' );
			} else if( $this->is_boolean($field) ) {
				$changes[] = array( "field" => $field, "value" => $value ? "TRUE" : "FALSE" );
			} else if( $field == 'USER_PASSWORD' ) {
				if( $value <> "" ) {
					$new_value = $this->hash( $value );
					$changes[] = array( "field" => $field, "value" => $this->enquote_string( $field, $new_value ) );
				}
			} else if( substr($field,0,1) == '-' ) {	// do not enquote
				$field2 = substr($field, 1);
				$changes[] = array( "field" => $field2, "value" => $value );
			} else {
				$new_value = $this->real_escape_string( (string) $value );
			//if( $this->debug ) echo "<p>".__METHOD__.": new_value $new_value</p>";
			//if( $this->debug ) echo "<p>".__METHOD__.": enquoted = ".$this->enquote_string( $field, $new_value )."</p>";
				$changes[] = array( "field" => $field, "value" => $this->enquote_string( $field, $new_value ) );
			}
		}
		 
		if( $this->last_error == "" ) {
			if( count( $changes ) > 0 ) {
				if( $this->column_exists("CHANGED_DATE") )
					$changes[] = array( "field" => "CHANGED_DATE",
						"value" => "CURRENT_TIMESTAMP" );
				if( $this->column_exists("CHANGED_BY") && isset($_SESSION) &&
					isset($_SESSION['EXT_USER_CODE']))
					$changes[] = array( "field" => "CHANGED_BY",
						"value" => $_SESSION['EXT_USER_CODE'] );
				
				if( $this->debug ) {
					echo "<p>".__METHOD__.": changes = </p>
					<pre>";
					var_dump($changes);
					echo "</pre>";
				}
				
				if( strpos($code, '=') !== false ||
					strpos(strtolower($code), ' in ') !== false )
					$match = $code;
				else
					$match = $this->primary_key." = ".$code;
						
				$result = $this->update_row( $match, $changes );
			} else {
				$result = true;		// no changes
			}
		} else {
			$result = false;		// error found
		}
		
		if( $this->debug ) echo "<p>".__METHOD__.": result = ".($result ? 'true' : 'false '.$this->error())."</p>";
		//if( $this->debug ) die;
		return $result;
	}

	// Error string
	public function error() {
		return empty($this->last_error) ? $this->database->error() : $this->last_error;
	}
	
	public function server_info() {
		return $this->database->server_info();
	}
	
	// Enquote string - add quotes around a string if needed
	public function enquote_string( $column, $string ) {
		if( $this->debug ) echo "<p>".__METHOD__.": entry $column, $string</p>";
		
		//if( $this->debug ) echo "<p>".__METHOD__.": data_types ".$this->data_types[strtoupper($column)]."</p>";
	
		if( isset($string) && strtoupper($string) <> 'NULL' && isset($column) && isset($this->data_types[strtoupper($column)]) ) {
			//! SCR# 430 - tinyint(4) is NOT a boolean, include smallint
			$ctype = $this->data_types[strtoupper($column)];
			//! SCR# 439 - fix error
			if( $ctype == 'tinyint' && $this->column_types[strtoupper($column)] <> 'tinyint(1)' )
				$ctype = 'int';
			switch( $ctype ) {
				case 'tinyint':
				case 'bool':
					if( $this->debug ) {
						echo "
						<pre>".$column."\n";
						var_dump($string);
						echo "</pre>";
					}
					if( is_bool($string) ) 
						$string = $string ? "TRUE" : "FALSE";
					if( ! in_array( strtoupper($string) , array("TRUE", "FALSE", "1", "0")))
						$this->last_error = "$column not boolean $string";
					$enquoted = $string;
					break;
	
				case 'int':
				case 'smallint':
				case 'double':
				case 'decimal':
					if( ! ($this->is_nullable($column) && $string == 'NULL') &&
						! is_numeric(trim($string)))
						$this->last_error = "$column not numeric $string ";
					$enquoted = $string;
					break;
	
				case 'varchar':
				case 'timestamp':
				case 'date':
				case 'enum':
				case 'blob':
				default:
					$enquoted = "'".str_replace("\\\'", "'", $string)."'";
					break;
			}
		} else $enquoted = "NULL";
		
		if( $this->debug ) echo "<p>".__METHOD__.": return $enquoted</p>";
		return $enquoted;
	}
	
	// For (true/false) settings, make it an enum
	public function fake_enum( $column, $values ) {
		if( $this->debug ) echo "<p>".__METHOD__.": $column</p>";
		$this->data_types[$column] = 'enum';
		$this->column_types[$column] = "enum('".implode("','", $values)."')";
		if( $this->debug ) echo "<p>".__METHOD__.": set to ".$this->column_types[$column]."</p>";
	}

	// Return the enumerated type choices as an array or false
	public function get_enum_choices( $column ) {

		if( $this->debug ) echo "<p>".__METHOD__.": $column</p>";
		$choices = [];
		if( $this->data_types[strtoupper($column)] == 'enum' ) {
		
			$count = preg_match('/enum\((.*)\)/', $this->column_types[strtoupper($column)], $matches);
			if( $count == 1 ) {
				$choices1 = explode(',', $matches[1]);
				
				foreach( $choices1 as $choice )
					$choices[] = trim($choice, "'\"");

				if( $this->debug ) {
					echo "<p>".__METHOD__.": $count ".$this->column_types[strtoupper($column)]."</p>
					<pre>";
					var_dump($choices);
					echo "</pre>";
				}
			}
		}
		return $choices;
	}
	
	public function b( $x ) {
		return ($x ? 'true' : 'false');
	}

	// Get the table name
	public function get_table_name( ) {
		if( $this->debug ) echo "<p>".__METHOD__.": $this->table_name</p>";

		return $this->table_name;
	}
	
	//! Remove a trailing dash
	public function rd( $column ) {
		if( $this->debug ) echo "<p>".__METHOD__.": $column in table $this->table_name</p>";
		
		return strtoupper(substr($column,0,1) == '-' ? substr($column, 1) : $column);
	}
	
	// Get the maximum length for a given column
	public function get_max_length( $column ) {
		$column2 = $this->rd( $column );
		if( $this->debug ) echo "<p>".__METHOD__.": $column2 in table $this->table_name</p>";

		return (isset($this->column_max_lengths) && isset($this->column_max_lengths[strtoupper($column2)])) ? $this->column_max_lengths[strtoupper($column2)] : false;
	}
	
	// Can this column be null?
	public function is_nullable( $column ) {
		$column2 = $this->rd( $column );
		if( $this->debug ) echo "<p>".__METHOD__.": $column2 in table $this->table_name</p>";
		
		$result = (isset($this->nullable) && is_array($this->nullable) &&
			isset($this->nullable[$column2]) ? $this->nullable[$column2] : false);

		$this->trace .= "<p>".__METHOD__.": $column2 in table $this->table_name, return ".$this->b($result)."</p>";
		return $result;
	}

	// Can this column be null?
	public function is_ai( $column ) {
		$column2 = $this->rd( $column );
		if( $this->debug ) echo "<p>".__METHOD__.": $column2 in table $this->table_name</p>";

		return (isset($this->extra) && isset($this->extra[$column2]) ? $this->extra[$column2] == 'auto_increment' : false);
	}

	// Is this column required? TRUE if not nullable and no default value
	public function is_boolean( $column ) {
		$column2 = $this->rd( $column );
		if( $this->debug ) echo "<p>".__METHOD__.": $column2 in table $this->table_name ".
			($this->column_types[$column2] == 'tinyint(1)' ? "true" : "false")."</p>";

		return $this->column_types[$column2] == 'tinyint(1)';
	}
	
	// Is this column required? TRUE if not nullable and no default value
	public function is_required( $column ) {
		$column2 = $this->rd( $column );
		if( $this->debug ) echo "<p>".__METHOD__.": $column2 in table $this->table_name ".
			(! ( $this->is_nullable( $column2 ) || $this->is_ai( $column2 ) || isset($this->default[$column2])  || $this->data_types[$column2] == 'enum' ) ? "true" : "false")."</p>";

		return ! ( $this->is_nullable( $column2 ) || $this->is_ai( $column2 ) || isset($this->default[$column2]) || $this->data_types[$column2] == 'enum' );
	}
	
	// Get the default value
	public function get_default( $column ) {
		$column2 = $this->rd( $column );
		if( $this->debug ) echo "<p>".__METHOD__.": $this->table_name</p>";

		return isset($this->default[$column2]) ? $this->default[$column2] : false;
	}
	
	// Column type
	public function column_type( $column ) {
		$column2 = $this->rd( $column );
		if( $this->debug ) echo "<p>".__METHOD__.": $column2 in table $this->table_name</p>";

		return (isset($this->data_types) && isset($this->data_types[$column2])) ? $this->data_types[$column2] : false;
	}
	
	// Check a given column exists
	public function column_exists( $column ) {
		$column2 = $this->rd( $column );
		//if( $this->debug ) echo "<p>".__METHOD__.": $column in table $this->table_name</p>";

		$result = (isset($this->data_types) && isset($this->data_types[$column2])) ? true : false;
		//if( $this->debug ) echo "<p>".__METHOD__.": $column = ".($result ? "true" : "false")."</p>";
		return $result;
	}
	
	// trim a value for a given column
	public function trim_to_fit( $column, $value ) {
		$column2 = $this->rd( $column );
		if( $this->debug ) echo "<p>".__METHOD__.": $column2, $value</p>";

		$newvalue = $value;
		$max_length = $this->get_max_length( strtoupper($column2) );
		
		if( $max_length && $max_length > 0 &&
			strlen($value) > $max_length )
			$newvalue = substr($value,0,$max_length);

		return $newvalue;
	}

	// Count records
	public function count_rows() {
	
		if( $this->debug ) echo "<p>count_rows</p>";
		$query = "select count(*) NUM_ROWS
			from $this->table_name";
			
		$result = $this->database->get_one_row($query);
		
		return $result ? $result["NUM_ROWS"] : false;
	}
	
    //! Check if a table exists in the database, return true/false
    public function exists( $table ) {
	    return $this->database->exists( $table );
	}
	
    public function is_empty() {
	    $check = $this->fetch_rows("", $this->primary_key);
	    return is_array($check) && count($check) == 0;
    }

	// Escape string
	public function real_escape_string( $string ) {
		return isset($string) ? $this->database->real_escape_string( $string ) : $string;
	}
	
	public function set_fields( $layout_fields, $edit_fields ) {
		$this->layout_fields = $layout_fields;
		$this->edit_fields = $edit_fields;
	}
	
	private function base64_url_encode($input) {
	 return strtr(base64_encode($input), '+/=', '-_.');
	}
	
	private function base64_url_decode($input) {
	 return base64_decode(strtr($input, '-_.', '+/='));
	}
	
	public function encryptData( $value ){

		if( $value <> false ) {
			if( extension_loaded('mcrypt') ) {
				$iv_size = mcrypt_get_iv_size(MCRYPT_DES, MCRYPT_MODE_ECB);
				$iv = mcrypt_create_iv($iv_size, MCRYPT_RAND);
				$crypttext = mcrypt_encrypt(MCRYPT_DES, $this->encrypt_key, $value, MCRYPT_MODE_ECB, $iv);
				return $this->base64_url_encode($crypttext);
			} else if( extension_loaded('sodium') ) {
				$nonce = random_bytes(
			        SODIUM_CRYPTO_SECRETBOX_NONCEBYTES
			    );
			
			    $cipher = $this->base64_url_encode(
			        $nonce.
			        sodium_crypto_secretbox(
			            $value,
			            $nonce,
			            $this->encrypt_key2
			        )
			    );
			    return $cipher;
			} else {
				return $value;
			}
		} else {
			return $value;
		}
	}
	
	public function decryptData( $value ){

		if( $this->debug ) echo "<p>".__METHOD__.": value = $value</p>";
		if( $value <> false ) {
			if( extension_loaded('mcrypt') ) {
				$crypttext = $this->base64_url_decode($value);
				$iv_size = mcrypt_get_iv_size(MCRYPT_DES, MCRYPT_MODE_ECB);
				$iv = mcrypt_create_iv($iv_size, MCRYPT_RAND);
				$decrypttext = mcrypt_decrypt(MCRYPT_DES, $this->encrypt_key, $crypttext, MCRYPT_MODE_ECB, $iv);
				return strtok($decrypttext, "\0");
			} else if( extension_loaded('sodium') ) {
				$decoded = $this->base64_url_decode($value);
			    if ($decoded === false) {
			        throw new Exception(__METHOD__.': the encoding failed');
			    }
			    if (mb_strlen($decoded, '8bit') < (SODIUM_CRYPTO_SECRETBOX_NONCEBYTES + SODIUM_CRYPTO_SECRETBOX_MACBYTES)) {
			        //throw new Exception(__METHOD__.': the message was truncated');
			        $plain = '';
			    } else {
				    $nonce = mb_substr($decoded, 0, SODIUM_CRYPTO_SECRETBOX_NONCEBYTES, '8bit');
				    $ciphertext = mb_substr($decoded, SODIUM_CRYPTO_SECRETBOX_NONCEBYTES, null, '8bit');
				    $plain = sodium_crypto_secretbox_open(
				        $ciphertext,
				        $nonce,
				        $this->encrypt_key2
				    );
				    
				    if ($plain === false) {
				         throw new Exception(__METHOD__.': the message was tampered with in transit');
				    }
				    sodium_memzero($ciphertext);
			    }	    
			    
			    return $plain;
				
			} else {
				return $value;
			}
		} else {
			return $value;
		}
	}

	// One way encrypt password
	// Tries two possible approaches, depending upon what is available.
	public function hash( $password ) {
	
		if( function_exists('password_hash') ) {
			if( $this->debug ) echo "<p>".__METHOD__.": use password_hash</p>";
			$encrypted = password_hash($password, PASSWORD_DEFAULT);
		} else if( function_exists('sodium_crypto_pwhash_str') ) {
			if( $this->debug ) echo "<p>".__METHOD__.": use sodium_crypto_pwhash_str</p>";
			$encrypted = sodium_crypto_pwhash_str( $password, 
			    SODIUM_CRYPTO_PWHASH_OPSLIMIT_INTERACTIVE,
			    SODIUM_CRYPTO_PWHASH_MEMLIMIT_INTERACTIVE);
		} else if( function_exists('mcrypt_create_iv') && function_exists('crypt') && CRYPT_BLOWFISH == 1 ) {
			if( $this->debug ) echo "<p>".__METHOD__.": use mcrypt_create_iv, crypt</p>";
			$salt = mcrypt_create_iv(22, MCRYPT_DEV_URANDOM);
			$salt = base64_encode($salt);
			$salt = str_replace('+', '.', $salt);
			$encrypted = crypt($password, '$2y$10$'.$salt.'$');
		} else {
			if( $this->debug ) echo "<p>".__METHOD__.": password_hash not found, clear text!</p>";
			$encrypted = $password;
		}
		if( $this->debug ) echo "<p>hash: $password (".strlen($password).") -> $encrypted (".strlen($encrypted).")</p>";
		return $encrypted;
	}

	//! Verify an encrypted password
	public function verify( $password, $encrypted ) {
		$result = false;
		if( function_exists('password_verify') ) {
			if( $this->debug ) echo "<p>".__METHOD__.": use password_verify</p>";
			$result = password_verify( $password, $encrypted );
		} else if( function_exists('crypt') ) {
			if( $this->debug ) echo "<p>".__METHOD__.": use crypt</p>";
			$enc = crypt( $password, $encrypted );
			$result = $enc == $encrypted;
		} else if( function_exists('sodium_crypto_pwhash_str_verify') ) {
			if( $this->debug ) echo "<p>".__METHOD__.": use sodium_crypto_pwhash_str_verify</p>";
			$result = sodium_crypto_pwhash_str_verify( $pwd_hashed, $password );
		}
		
		if( $this->debug ) echo "<p>".__METHOD__.":return ".($result ? 'true' : 'false')."</p>";
		return $result;
	}

	public function get_insert_id($fields, $values)
	{
		if( $this->debug ) echo "<p>".__METHOD__.": $fields, $values</p>";
		
		$query = "INSERT INTO ".$this->table_name."($fields)
			VALUES($values)";
		$result = $this->database->insert_row($query);
		if( $this->debug ) echo "<p>".__METHOD__.": result = ".($result === false ? 'false '.$this->error() : 'ok')."</p>";
		if( $result === false ) {
			$save_error = $this->error();	// Need to save this as the next line clears it
			require_once( "sts_email_class.php" );
			
			$email = sts_email::getInstance($this->database, $this->debug);
			$email->send_alert("sts_table > get_insert_id failed: ".$save_error.
				"<br>Fields<pre>".print_r($fields, true)."</pre>".
				"<br>Values<pre>".print_r($values, true)."</pre>".
				"<br>Query<pre>".print_r($query, true)."</pre>".
				"<br>Result<pre>".print_r($result, true)."</pre>", EXT_ERROR_ERROR);
		}
		
		return $result;
	}

	//! Add() Mk III - try to use this rather than override.
	// Add extra columns to $this->extra_columns property and add a case below.
	// See CARRIER_SINCE for an example.
	public function add( $values ) {
		global $_SESSION;

		if( $this->debug ) {
			echo "<p>".__METHOD__.": values= </p>
			<pre>";
			var_dump($values);
			echo "</pre>";
		}
		$this->last_error = "";

		$column_list = array();
		$values_list = array();
		foreach( $values as $field => $value ) {
			//if( $this->debug ) echo "<p>".__METHOD__.": field = $field value = ".(isset($value) ? $value : 'null')."</p>";
			if( substr($field,0,1) == '-' ) {	// do not enquote
				$field2 = substr($field, 1);
				$column_list[] = $field2;
				$values_list[] = $value;
			} else {
				$column_list[] = $field;
				if( $this->table_name == USER_TABLE && $field == 'USER_PASSWORD' )
					$values_list[] = $this->enquote_string( $field, $this->hash( $value ) );
				else
					$values_list[] = $this->enquote_string( $field, $this->real_escape_string( $value ) );
			}
		}

		// ! Check for missing required columns
		if( $this->last_error == "" ) {
			foreach( $this->data_types as $column => $dummy ) {
				if( $this->is_required( $column ) && ! in_array($column, $column_list) 
					&& ! in_array($column, $this->extra_columns)) {
					$this->last_error = "required column $column is missing";
					break;
				}
			}
		}
				
		if( $this->last_error == "" ) {
			$column_list_str = implode(', ', $column_list);
			$values_list_str = implode(', ', $values_list);
			
			foreach( $this->extra_columns as $column ) {
				if( $this->column_exists( $column) &&
					! in_array($column, $column_list)) {
					switch( $column ) {
						case 'CREATED_DATE':
						case 'CHANGED_DATE':
							$column_list_str .= ', '.$column;
							$values_list_str .= ', CURRENT_TIMESTAMP';
							break;
	
						case 'CREATED_BY':
						case 'CHANGED_BY':
							$column_list_str .= ', '.$column;
							$values_list_str .= ', '.(isset($_SESSION['EXT_USER_CODE']) ? $_SESSION['EXT_USER_CODE'] : "0");
							break;
	
						case 'CARRIER_SINCE':
						case 'START_DATE':
						case 'CLIENT_SINCE':
							$column_list_str .= ', '.$column;
							$values_list_str .= ', CURDATE()';
							break;
	
						default:
							require_once( "sts_email_class.php" );
							
							$email = sts_email::getInstance($this->database, $this->debug);
							$email->send_alert("sts_table > add: unknown extra column ".
								$column." skipped.", EXT_ERROR_ERROR);
							break;
					}
					
				}
			}
					
			$result = $this->get_insert_id($column_list_str, $values_list_str );
		} else {
			$result = false;
			$save_error = $this->error();	// Need to save this as the next line clears it
			require_once( "sts_email_class.php" );

			$email = sts_email::getInstance($this->database, $this->debug);
			$email->send_alert(__METHOD__.": failed:<br>".$save_error.
				"<br>Values<pre>".print_r($values, true)."</pre>", EXT_ERROR_ERROR);
			//echo "<p>".__METHOD__.": failed to add to $this->table_name <br> $save_error <br> A message has been sent to support.</p>";
			$this->last_error = $save_error;
		}
				
		if( $this->debug ) echo "<p>".__METHOD__.": result = ".($result ? $result : 'false '.$this->error())."</p>";
		return $result;
	}

}

if( isset($sts_debug) && $sts_debug ) echo "<p>sts_table_class</p>";

?>