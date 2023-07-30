<?php

// $Id: sts_cache_class.php 5134 2023-06-13 22:09:13Z dev $
// Cache static data

// no direct access
defined('_STS_INCLUDE') or die('Restricted access');

class sts_cache {
	
	private $session_path;
	private $file_name;
	private $contents;
	private $full_path;
	private $file_size;
	public $debug;
	public $database;

	public function __construct( $database, $debug = false ) {
		global $sts_database, $_SESSION;
		
		if( false ) { // enable this to track why this is getting called.
			echo "<pre>";
			debug_print_backtrace();
			echo "</pre>";
			die;
		}
		
		$this->debug = $debug;
		$this->database = $database;

		$this->file_name = '/'.$sts_database.'_cache.txt';
		
		if( $this->debug ) echo "<p>Create sts_cache file = $this->file_name</p>";
		
		if( ! $this->read_cache() ) $this->write_cache();

		/*
		echo "<pre>".__METHOD__."\n";
		var_dump(ini_get('session.save_path'));
		var_dump($this->file_name);
		var_dump($this->contents["SETTING_CACHE"]["option/DB_LOGGING"]);
		echo "</pre>";
		*/

		if( isset($this->contents) &&
			is_array($this->contents) &&
			is_array($this->contents["SETTING_CACHE"]) &&
			isset($this->contents["SETTING_CACHE"]["option/DB_LOGGING"]) ) {
			$this->database->set_logging(($this->contents["SETTING_CACHE"]["option/DB_LOGGING"]  == 'true'));
		}
	}

	// Allow re-use of objects - singleton function
	public static function getInstance( $database, $debug = false ) {
		static $instance = null;
		$myclass = get_class ();
		if( $debug ) echo "<p>Get instance of $myclass</p>";
		if (null === $instance) {
			$instance = new $myclass( $database, $debug );
		}
		if( $debug ) echo "<p>CWD = ".getcwd()." Full Path = $instance->full_path Cache file = $instance->file_name</p>";
		return $instance;
    }

	function __destruct() {
		if( $this->debug ) echo "<p>Destroy sts_cache</p>";
	}

	public function set_debug( $debug ) {
		$tmp = $this->debug;
		$this->debug = $debug;
		return $tmp;
	}
	
	private function read_cache() {
		global $sts_database;
		
		$result = false;
		if( $this->debug ) echo "<p>".__METHOD__.": entry</p>";
		
		$this->session_path = str_replace('\\', '/', ini_get('session.save_path')) . '/ex_'.$sts_database;
		
		if( $this->debug ) echo "<p>".__METHOD__.": path = $this->session_path ".
			(file_exists($this->session_path) ? "exists" : "missing")." ".
			(is_dir($this->session_path) ? "dir" : "file")." ".
			(is_writable($this->session_path) ? "writable" : "readonly")."</p>";
		
		$this->full_path = $this->session_path.$this->file_name;
		if( file_exists($this->full_path) ) {
			$this->file_size = filesize($this->full_path);
			$contents_string = file_get_contents( $this->full_path );
			
			if( ! empty($contents_string) ) {
				if( $this->debug ) echo "<p>".__METHOD__.": unserialize ".strlen($contents_string)." bytes</p>";
				$this->contents = unserialize($contents_string);
				$result = true;
			}
		}
		
		return $result;
	}
	
	public function info() {
		global $session_path, $sts_database, $sts_config_path;
		return "<p>".__METHOD__." config_path = $sts_config_path<br>
		db = $sts_database<br>
		sp = $session_path<br>
		session_path = $this->session_path<br>
		full_path = $this->full_path</p>";
	}

	//! Get data for all columns of all tables.
	private function get_all_column_types() {
		if( $this->debug ) echo "<p>".__METHOD__.": entry</p>";
		$tables = $this->database->get_multiple_rows("
			SELECT UPPER(TABLE_NAME) AS TABLE_NAME, UPPER(COLUMN_NAME) AS COLUMN_NAME,
				DATA_TYPE, COLUMN_TYPE, CHARACTER_MAXIMUM_LENGTH,
				IS_NULLABLE, COLUMN_DEFAULT, EXTRA
			FROM INFORMATION_SCHEMA.COLUMNS
			WHERE TABLE_SCHEMA = '".$this->database->schema()."'            
			ORDER BY TABLE_NAME ASC, COLUMN_NAME ASC");
		$column_types = array();
		if( is_array($tables) && count($tables) > 0 ) {
			foreach( $tables as $row ) {
				if( isset($column_types[$row["TABLE_NAME"]]))
					$column_types[$row["TABLE_NAME"]][] = $row;
				else
					$column_types[$row["TABLE_NAME"]] = array( $row );
			}
		}
		
		if( $this->debug ) {
			echo "<pre>".__METHOD__.": return\n";
			var_dump($column_types);
			echo "</pre>";
		}
			
		return $column_types;
	}

	//! Get all states.
	private function get_all_states() {
		if( $this->debug ) echo "<p>".__METHOD__.": entry</p>";
		$states = $this->database->get_multiple_rows("SELECT *
			FROM EXP_STATES");
		$name_state = $state_name = [];
		foreach( $states as $row ) {
			$name_state[$row['STATE_NAME']] = $row['abbrev'];
			$state_name[$row['abbrev']] = $row['STATE_NAME'];
		}
		
		return [ $name_state, $state_name ];
	}
	
	//! Get data for all columns of all tables.
	private function get_all_state_tables() {
		if( $this->debug ) echo "<p>".__METHOD__.": entry</p>";
		$states = $this->database->get_multiple_rows("
			SELECT SOURCE_TYPE, STATUS_CODES_CODE, STATUS_STATE, BEHAVIOR,
				STATUS_DESCRIPTION, PREVIOUS
			FROM EXP_STATUS_CODES
			ORDER BY SOURCE_TYPE ASC, STATUS_CODES_CODE ASC");
		
		$state_tables = array();
		if( is_array($states) && count($states) > 0 ) {
			foreach( $states as $row ) {
				if( isset($state_tables[$row["SOURCE_TYPE"]]))
					$state_tables[$row["SOURCE_TYPE"]][] = $row;
				else
					$state_tables[$row["SOURCE_TYPE"]] = array( $row );
			}
		}
		
		if( $this->debug ) {
			echo "<pre>".__METHOD__.": return\n";
			var_dump($state_tables);
			echo "</pre>";
		}
			
		return $state_tables;
	}
	
	//! Get data for all settings.
	private function get_all_settings() {
		if( $this->debug ) echo "<p>".__METHOD__.": entry</p>";
		$all_settings = $this->database->get_multiple_rows("
			SELECT CONCAT(CATEGORY, '/', SETTING) CS, THE_VALUE
			FROM EXP_SETTING");
		$settings = array();
		if( is_array($all_settings) && count($all_settings) > 0 ) {
			foreach( $all_settings as $row ) {
				$settings[$row["CS"]] = $row["THE_VALUE"];
			}
		}
		
		if( $this->debug ) {
			echo "<pre>".__METHOD__.": return\n";
			var_dump($settings);
			echo "</pre>";
		}
			
		return $settings;
	}

	//! Get data for all videos.
	private function get_all_videos() {
		if( $this->debug ) echo "<p>".__METHOD__.": entry</p>";
		$videos = $this->database->get_multiple_rows("
			SELECT VIDEO_NAME, VIDEO_FILE, COALESCE(ON_PAGE, '') ON_PAGE
			FROM EXP_VIDEO");
		
		if( $this->debug ) {
			echo "<pre>".__METHOD__.": return\n";
			var_dump($videos);
			echo "</pre>";
		}
			
		return $videos;
	}

	public function write_cache() {
		if( $this->debug ) echo "<p>".__METHOD__.": entry</p>";
		
		$this->contents = array();
		$this->contents["CACHED"] = date('m/d/Y h:i:s A');
		$this->contents["COLUMN_TYPES"] = $this->get_all_column_types();
		list($this->contents["NAME_STATE"], $this->contents["STATE_NAME"]) = $this->get_all_states();		
		$this->contents["STATE_TABLES"] = $this->get_all_state_tables();
		$this->contents["SETTING_CACHE"] = $this->get_all_settings();
		$this->contents["VIDEOS"] = $this->get_all_videos();
		
		if( $this->debug ) {
			echo "<pre>".__METHOD__.": contents\n";
			var_dump($this->contents);
			echo "</pre>";
		}
		
		$contents_string = serialize($this->contents);
			
		$this->session_path = str_replace('\\', '/', ini_get('session.save_path'));		
		
		if( $this->debug ) echo "<p>".__METHOD__.": path = $this->session_path ".
			(file_exists($this->session_path) ? "exists" : "missing")." ".
			(is_dir($this->session_path) ? "dir" : "file")." ".
			(is_writable($this->session_path) ? "writable" : "readonly")."</p>";
		
		$this->full_path = $this->session_path.$this->file_name;
		
		if( file_exists($this->full_path) )
			@unlink($this->full_path);
		
		$result = @file_put_contents( $this->full_path, $contents_string );
		$this->file_size = $result;
		
		if( $this->debug ) echo "<p>".__METHOD__.": exit return ".($result === false ? 'false' : $result.' bytes written')."</p>";
		return $result;
	}
	
	public function get_whole_cache() {
		if( $this->debug ) echo "<h3>".__METHOD__.": entrance.</h3>";
		return $this->contents;
	}

	public function get_column_types( $table ) {
		if( $this->debug ) echo "<p>".__METHOD__.": entry $table</p>";
		$key = strtoupper($table);
		return isset($this->contents) &&
			is_array($this->contents["COLUMN_TYPES"]) &&
			isset($this->contents["COLUMN_TYPES"][$key]) &&
			is_array($this->contents["COLUMN_TYPES"][$key]) ?
				$this->contents["COLUMN_TYPES"][$key] : false;
	}
	
	public function get_state_table( $table ) {
		if( $this->debug ) echo "<p>".__METHOD__.": entry $table</p>";
		return isset($this->contents) && is_array($this->contents["STATE_TABLES"]) && is_array($this->contents["STATE_TABLES"][$table]) ? $this->contents["STATE_TABLES"][$table] : false;
	}

	public function get_setting( $category, $setting ) {
		if( $this->debug ) echo "<p>".__METHOD__.": entry $category, $setting</p>";
		$key = $category.'/'.$setting;
		
		return isset($this->contents) && isset($this->contents["SETTING_CACHE"]) &&
			is_array($this->contents["SETTING_CACHE"]) && isset($this->contents["SETTING_CACHE"][$key]) ? $this->contents["SETTING_CACHE"][$key] : false;
	}
	
	public function get_videos( $page = false ) {
		$result = false;
		if( $this->debug ) echo "<p>".__METHOD__.": entry $page</p>";
		if( isset($this->contents) && isset($this->contents["VIDEOS"]) &&
			is_array($this->contents["VIDEOS"]) && count($this->contents["VIDEOS"]) > 0 ) {
			if( $page === false ) {
				$result = $this->contents["VIDEOS"];
			} else {
				$result = array();
				$match = array('', $page);
				foreach($this->contents["VIDEOS"] as $row) {
					if( in_array($row["ON_PAGE"], $match) )
						$result[] = $row;
				}
			}
		}
		
		return $result;
	}
	
	public function date_cached() {
		$result = isset($this->contents) && isset($this->contents["CACHED"]) ? $this->contents["CACHED"] : false;
		
		if( $this->debug ) echo "<p>".__METHOD__.": return ".($result === false ? 'false' : $result)."</p>";
		return $result;
	}
	
	public function get_full_path() {
		$result = isset($this->full_path) ? $this->full_path : 'UNDEFINED';
		
		if( $this->debug ) echo "<p>".__METHOD__.": return ".$result."</p>";
		return $result;
	}
	
	public function get_file_size() {
		$result = isset($this->file_size) ? $this->file_size : 0;
		
		if( $this->debug ) echo "<p>".__METHOD__.": return ".$result."</p>";
		return $result;
	}
	
	
}

?>