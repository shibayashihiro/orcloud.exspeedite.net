<?php

// $Id: sts_session_class.php 5099 2023-05-17 22:27:43Z duncan $
// Session class

// no direct access
defined('_STS_INCLUDE') or die('Restricted access');

//! Work around to avoid multiple includes.
if( ! defined('_STS_SESSION_CONFIG') ) {
	define('_STS_SESSION_CONFIG', 1);

require_once( "sts_setting_class.php" );
require_once( "sts_user_log_class.php" );

class sts_session {
	
	private $database;
	private $username;
	private $user_code;
	private $groups;
	private $debug = false;
	private $setting_table;
	private $user_log;
	private $alert_session_expiry;
	private $superadmins = array( 'duncan', 'scott', 'Duncan', 'Scott' );
	private $log_file;
	private $debug_diag_level;
	private $diag_level;
	private $backup;

	//! Session lifetime in seconds 8 * 60 * 60 (twice what is in sts_session_setup.php)
	private $session_lifetime = 28800;

	public function __construct( $database, $debug = false ) {
		global $sts_error_level_label, $sts_crm_dir;

		$this->debug = $debug;
		$this->database = $database;
		
		$this->setting_table = sts_setting::getInstance($database, $debug);
		$this->alert_session_expiry = ($this->setting_table->get( 'option', 'ALERT_SESSION_EXPIRY' ) == 'true');
		$this->backup = $this->setting_table->get( 'option', 'BACKUP_SESSION_FILES' ) == 'true';
		if( $this->debug ) echo "<p>Create sts_session</p>";
		if( isset($_SESSION['EXT_USERNAME']) ) $this->username = $_SESSION['EXT_USERNAME'];
		if( isset($_SESSION['EXT_USER_CODE']) ) $this->user_code = $_SESSION['EXT_USER_CODE'];
		if( isset($_SESSION['EXT_GROUPS']) )
			$this->groups = explode(',',$_SESSION['EXT_GROUPS']);
		$this->user_log = sts_user_log::getInstance($database, $debug);

		$this->log_file = $this->setting_table->get( 'option', 'DEBUG_LOG_FILE' );
		$this->debug_diag_level = $this->setting_table->get( 'option', 'DEBUG_DIAG_LEVEL' );
		$this->diag_level = array_search(strtolower($this->debug_diag_level), $sts_error_level_label);
		if( $this->diag_level === false ) $this->diag_level = EXT_ERROR_ALL;

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
			if( isset($this->log_file) && $this->log_file <> '' &&
				is_writable($this->log_file) && ! is_dir($this->log_file) ) {
				file_put_contents($this->log_file, date('m/d/Y h:i:s A')." ".$event.PHP_EOL,
					(file_exists($this->log_file) ? FILE_APPEND : 0) );
			}
		}
	}

	function __destruct() {
		if( $this->debug ) echo "<p>Destroy sts_session</p>";
	}
	
	//! SCR# 593 - allow 2 alternatives
	public function in_group( $group, $alternate_group = false, $alternate_group2 = false ) {
		$result = false;
		if( $group == EXT_GROUP_SUPERADMIN ) {
			$result = $this->superadmin();
		} else if( isset($this->groups) && is_array($this->groups) ) {
			$result = in_array($group, $this->groups) ||
				($alternate_group && in_array($alternate_group, $this->groups)) ||
				($alternate_group2 && in_array($alternate_group2, $this->groups));
		} else {
			$result = false;
		}
		
		return $result;
	}

	public function cms_restricted_salesperson() {
		if( isset($this->groups) && is_array($this->groups) ) {
			if( $this->setting_table->get("option", "CMS_ENABLED") == 'true' &&
				$this->setting_table->get("option", "CMS_RESTRICT_SALESPEOPLE") == 'true' &&
				! in_array(EXT_GROUP_ADMIN, $this->groups) &&
				! in_array(EXT_GROUP_PROFILES, $this->groups) &&
				in_array(EXT_GROUP_SALES, $this->groups) )
				return true;
		} else return false;
	}

	public function superadmin() {
		return in_array($this->username, $this->superadmins);
	}

	// Update session details
	public function update( $code, $changed ) {
		if( $this->user_code == $code ) {
			foreach( $changed as $field => $new_value ) {
				switch( $field ) {
					case "USERNAME":
						$this->username = $_SESSION['EXT_USERNAME'] = $new_value;
						break;

					case "FULLNAME":
						$_SESSION['EXT_FULLNAME'] = $new_value;
						break;

					case "EMAIL":
						$_SESSION['EXT_EMAIL'] = $new_value;
						break;

					case "GROUPS":
						$_SESSION['EXT_GROUPS'] = $new_value;
						$this->groups = explode(',',$_SESSION['EXT_GROUPS']);
						break;
					
					default:
						break;

				}
			}
		}		
	}
	
	private function backup_session() {
		$result = false;
		if( $this->backup ) {
			$sess_file = session_save_path().'/sess_'.session_id();
			$backup_file = session_save_path().'/backup_'.session_id();
			if( file_exists($sess_file) ) {
				$result = copy( $sess_file, $backup_file );
				$this->log_event("backup_session: $sess_file -> $backup_file ".($result? "OK" : "FAILED"), EXT_ERROR_DEBUG);
			} else {
				$this->log_event("backup_session: $sess_file MISSING", EXT_ERROR_DEBUG);
			}
		}
		
		return $result;
	}

	private function restore_session() {
		$result = false;
		if( $this->backup ) {
			$sess_file = session_save_path().'/sess_'.session_id();
			$backup_file = session_save_path().'/backup_'.session_id();
			if( file_exists($backup_file) ) {
				session_write_close();
				$result = copy( $backup_file, $sess_file );
				$this->log_event("restore_session: $backup_file -> $sess_file ".($result? "OK" : "FAILED"), EXT_ERROR_DEBUG);
				session_start();
			} else {
				$this->log_event("restore_session: $backup_file MISSING", EXT_ERROR_DEBUG);
			}
		}
		
		return $result;
	}
	
	private function remove_backup() {
		$result = false;
		if( $this->backup ) {
			$backup_file = session_save_path().'/backup_'.session_id();
			if( file_exists($backup_file) ) {
				$result = unlink($backup_file);
				$this->log_event("remove_backup: $backup_file ".($result? "OK" : "FAILED"), EXT_ERROR_DEBUG);
			} else {
				$this->log_event("remove_backup: $backup_file MISSING", EXT_ERROR_DEBUG);
			}
		}
		
		return $result;
	}

	// Ensure logged in and in the right group for this page.
	//! SCR# 593 - allow 2 alternatives
	public function access_check( $required_group, $alternate_group = false, $alternate_group2 = false ) {
		
		if( $this->debug ) echo "<p>access_check $required_group</p>";
		
		// For AJAX just return, don't want this to fail.
		if( defined('_STS_SESSION_AJAX') ) return;
		
		//! Kluge in case the session file is toast.
		if( ! (isset($_SESSION['EXT_USER_CODE']) && isset($_SESSION['EXT_USERNAME'])) ) {
			$this->restore_session();
		} else {
			$this->backup_session();
		}
		
		$active = false;
		
		//! Alert if session lost
		if( ! (isset($_SESSION['EXT_USER_CODE']) && isset($_SESSION['EXT_USERNAME'])) ) {
			$this->log_event("access_check: session lost ".session_id());
			if( $this->alert_session_expiry ||
				(isset($_SERVER["REMOTE_ADDR"]) &&
				$_SERVER["REMOTE_ADDR"] == '24.166.12.127') ) {
				$sess_file = session_save_path().'/sess_'.session_id();
				require_once( "sts_email_class.php" );
				
				$email = sts_email::getInstance($this->database, $this->debug);
				$email->send_alert(__METHOD__.": lost session info?<br>
				session_id = ".session_id()."<br>
				session status = ".(session_status() == PHP_SESSION_ACTIVE ? 'PHP_SESSION_ACTIVE' : (session_status() == PHP_SESSION_NONE ? 'PHP_SESSION_NONE' : 'PHP_SESSION_DISABLED'))."<br>
				session name = ".(session_status() == PHP_SESSION_ACTIVE ? session_name() : '[none]')."<br>
				session_save_path = ".(session_status() == PHP_SESSION_ACTIVE ? session_save_path() : '[none]')."<br>
				_SESSION =
				<pre>".(isset($_SESSION) ? print_r($_SESSION, true) : "not set")."</pre><br>
				File = 
				<pre>".print_r($sess_file, true)."</pre><br>
				Exists = 
				<pre>".print_r(file_exists($sess_file), true)."</pre>".
				(file_exists($sess_file) ? "<br>
				Modified = 
				<pre>".print_r(filemtime($sess_file), true)."</pre><br>
				Size = 
				<pre>".print_r(filesize($sess_file), true)."</pre>" : "")."<br>
				ini_get_all = 
				<pre>".print_r(ini_get_all(), true)."</pre>", EXT_ERROR_ERROR );
				
				$this->user_log->log_event('logout', __METHOD__.': lost session info'.
					'<br>session id = '.session_id());
			}
		} else if ( isset($_SESSION['EXT_USERNAME']) && isset($_SESSION['EXT_USER_CODE']) ) {
			if( isset($_SESSION['EXT_ISACTIVE']) && --$_SESSION['EXT_ISACTIVE'] > 0 ) {
				$active = true;
			} else {
				$_SESSION['EXT_ISACTIVE'] = 10; // Check every 10 times
				$result = $this->database->get_one_row("SELECT ISACTIVE FROM EXP_USER
					WHERE USER_CODE = ".$_SESSION['EXT_USER_CODE'] );
				if( is_array($result) && count($result) > 0 && isset($result['ISACTIVE']) &&
					$result['ISACTIVE'] == "Active")
					$active = true;
			}
		}
			
		if ( ! $active ) {
			if( $this->debug ) echo "<p>access_check failed - not logged in</p>";
			
			$table = new sts_table( DUMMY_DATABASE, "", $this->debug );	// To get at the encryptData method
			// Be able to return the user back where they were working.
			$path = str_replace($_SERVER['CONTEXT_PREFIX'], '', $_SERVER['REQUEST_URI']);
					
			$ret = $table->encryptData($path);
			$this->logout();
			if( $this->debug ) {
				die( "<p>reload_page exp_login.php?return=$ret</p>" );
			} else {
				reload_page( "exp_login.php?return=".$ret );
				die;
			}
		}
		
		if( ! $this->in_group($required_group, $alternate_group, $alternate_group2 ) ) {
			if( $this->debug ) echo "<p>access_check failed - ".
				$_SESSION['EXT_USERNAME']." not in group $required_group ".
				($alternate_group ? "or $alternate_group " : "").
				($alternate_group2 ? "or $alternate_group2 " : "").
				"(".$_SESSION['EXT_GROUPS']." ".implode(',',$this->groups).")</p>";
			if( $this->debug ) {
				echo "<p>reload_page index.php</p>";
				die;
			} else {
				if( $this->in_group(EXT_GROUP_DRIVER) )
					reload_page ( "exp_listload_driver.php" );	// Back to driver loads page
				else
					reload_page( "index.php" );
				die;
			}
		}
	}
	
	public function access_report( $report ) {
		require_once( "sts_report_class.php" );
		if( $this->debug ) echo "<p>access_report $report</p>";
		
		//! Kluge in case the session file is toast.
		if( ! (isset($_SESSION['EXT_USER_CODE']) && isset($_SESSION['EXT_USERNAME'])) ) {
			$this->restore_session();
		} else {
			$this->backup_session();
		}
		
		$active = false;
		
		//! Alert if session lost
		if( ! (isset($_SESSION['EXT_USER_CODE']) && isset($_SESSION['EXT_USERNAME'])) ) {
			$this->log_event("access_report: session lost ".session_id());
			if( $this->alert_session_expiry ) {
				$sess_file = session_save_path().'/sess_'.session_id();
				require_once( "sts_email_class.php" );
	
				$email = sts_email::getInstance($this->database, $this->debug);
				$email->send_alert("sts_session > access_report - lost session info?<br>
				(Scott, I'm trying to track down this issue, definitely one of mine)<br>
				session status = ".(session_status() == PHP_SESSION_ACTIVE ? 'PHP_SESSION_ACTIVE' : (session_status() == PHP_SESSION_NONE ? 'PHP_SESSION_NONE' : 'PHP_SESSION_DISABLED'))."<br>
				session name = ".(session_status() == PHP_SESSION_ACTIVE ? session_name() : '[none]')."<br>
				session_save_path = ".(session_status() == PHP_SESSION_ACTIVE ? session_save_path() : '[none]')."<br>
				_SESSION =
				<pre>".(isset($_SESSION) ? print_r($_SESSION, true) : "not set")."</pre><br>
				File = 
				<pre>".print_r($sess_file, true)."</pre><br>
				Exists = 
				<pre>".print_r(file_exists($sess_file), true)."</pre>".
				(file_exists($sess_file) ? "<br>
				Modified = 
				<pre>".print_r(filemtime($sess_file), true)."</pre><br>
				Size = 
				<pre>".print_r(filesize($sess_file), true)."</pre>" : "")."<br>
				ini_get_all = 
				<pre>".print_r(ini_get_all(), true)."</pre>", EXT_ERROR_ERROR );
			}
		} else if ( isset($_SESSION['EXT_USERNAME']) && isset($_SESSION['EXT_USER_CODE']) ) {
			$result = $this->database->get_one_row("SELECT ISACTIVE FROM EXP_USER
				WHERE USERNAME = '".$_SESSION['EXT_USERNAME']."'" );
			if( is_array($result) && count($result) > 0 && isset($result['ISACTIVE']) &&
				$result['ISACTIVE'] == "Active")
				$active = true;
		}
			
		if ( ! $active ) {
			if( $this->debug ) echo "<p>access_report failed - not logged in</p>";
			
			$table = new sts_table( DUMMY_DATABASE, "", $this->debug );	// To get at the encryptData method
			// Be able to return the user back where they were working.
			$path = str_replace($_SERVER['CONTEXT_PREFIX'], '', $_SERVER['REQUEST_URI']);
					
			$ret = $table->encryptData($path);
			$this->logout();
			if( $this->debug ) {
				die( "<p>reload_page exp_login.php?return=$ret</p>" );
			} else {
				reload_page( "exp_login.php?return=".$ret );
				die;
			}
		}
		
		$report_table = sts_report::getInstance( $this->database, $this->debug );
		
		if( ! (is_array($report_table->reports) &&
			count($report_table->reports) > 0 &&
			array_search($report, array_column($report_table->reports, 'REPORT_NAME')) !== false ) ) {

			if( $this->debug ) echo "<p>access_report failed</p>";
			if( $this->debug ) {
				echo "<p>reload_page index.php</p>";
				die;
			} else {
				if( $this->in_group(EXT_GROUP_DRIVER) )
					reload_page ( "exp_listload_driver.php" );	// Back to driver loads page
				else
					reload_page( "index.php" );
				die;
			}
		}
	}

	public function access_link( $required_group, $link, $image_ok, $image_notok ) {

		if( $this->debug ) echo "<p>access_link $required_group, $link, $image_ok, $image_notok</p>";
		if( $this->debug ) {
			echo "<p>groups = </p>
			<pre>";
			var_dump($this->groups);
			echo "</pre>";
		}
		if( $this->in_group($required_group) ) {
			$output = '<a href="'.$link.'"><img class="center-block" src="'.$image_ok.'"  height="80" border="0"></a>';
		} else {
			$output = '<img class="center-block" src="'.$image_notok.'" border="0">';
		}
		
		return $output;
	}
	
	public function access_enabled( $required_group ) {

		if( $this->debug ) echo "<p>access_enabled $required_group</p>";
		if( $this->in_group($required_group) ) {
			$output = '';
		} else {
			$output = ' disabled';
		}
		
		return $output;
	}
	
	public function login( $user_table, $username, $password ) {
		global $sts_database;
		$result = false;
		
		if( $this->debug ) echo "<p>".__METHOD__.": login $username, $password</p>";
		$info = $user_table->authenticate( $username, $password );
		if( is_array($info) && count($info) == 1 ) {				// Got one match
			if( isset($info[0]) && isset($info[0]["USERNAME"]) ) {	// Got USERNAME
				$this->username = $_SESSION['EXT_USERNAME'] = $info[0]["USERNAME"];
				$this->user_code = $_SESSION['EXT_USER_CODE'] = $info[0]["USER_CODE"];
				$_SESSION['EXT_FULLNAME'] = $info[0]["FULLNAME"];
				$_SESSION['EXT_EMAIL'] = $info[0]["EMAIL"];
				$_SESSION['EXT_GROUPS'] = $info[0]["USER_GROUPS"];
				// ! Use DB to identify which installation - see gc()
				$_SESSION['EXT_DATABASE'] = $sts_database;
				$this->groups = explode(',',$_SESSION['EXT_GROUPS']);
				
				$_SESSION['LAST_ACTIVITY'] = time(); // update last activity time stamp

				//! SCR# 185 - log when user logs in
				$this->user_log->log_event('login', 'user = '.$this->username.
					'<br>agent = '.$_SERVER['HTTP_USER_AGENT'].
					'<br>session id = '.session_id());
				
				$result = true;	// Success
	
			}
		}
		
		$this->log_event("login: ".session_id().(! empty($this->username) ? " user = ".$this->username : '')." ".($result? "OK" : "FAILED"), EXT_ERROR_DEBUG);
		
		return $result;
	}
	
	public function logout( $expired = false ) {
		if( $this->debug ) echo "<p>".__METHOD__.":logout</p>";
		//! SCR# 185 - log when user logs out
		$this->user_log->log_event('logout',
			(! empty($this->username) ? " user = ".$this->username : '').
			'<br>session id = '.session_id().
			($expired ? '<br>Session Expired, last activity at '.$expired : ''));
		
		// Path to session file
		$session_file = str_replace('\\', '/', ini_get('session.save_path')).'/sess_'.session_id();

		$this->log_event("logout: session = ".session_id().
			" file = ".$session_file.
			(! empty($this->username) ? " user = ".$this->username : '').
			($expired ? ' Session Expired, last activity at '.$expired : ''),
			EXT_ERROR_DEBUG);
		
	    // unset $_SESSION variable for the run-time
	    session_unset();
				
		// Remove the session backup
		$this->remove_backup();
		
		// Next, destroy the session.
		session_regenerate_id();
		session_destroy();
		
		// Finally delete the session file
		if( file_exists($session_file) &&  is_writable($session_file))
			unlink($session_file);
		
		//$this->gc();	// Call garbage collection
	}
	
	//! Garbage collection - remove old session files
	private function gc() {
		global $sts_database;

		// Where all the session files are kept
		$session_path = str_replace('\\', '/', ini_get('session.save_path'));

		if( is_dir($session_path) ) {
			$sfiles = scandir($session_path);	// Get list of files
			foreach( $sfiles as $sfile ) {
				$full_path = $session_path.'/'.$sfile;	// Full path to file
				$age = time() - filemtime($full_path);	// Age of the session file

				// Those that are trully expired (double normal expiry)
				if(! in_array($sfile, array('.', '..', 'static_cache.txt')) &&
					strpos($sfile, 'sess_') === 0 &&
					strpos($sfile, '_cache.txt') === false &&
					$age > $this->session_lifetime) {

					// If we can read the file, get the info to log the expiry event
					if( is_readable($full_path) && filesize($full_path) > 0 ) {
						$raw = file_get_contents($full_path);
						$contents = SessionFile::unserialize($raw);
						if( ! empty($contents["EXT_USERNAME"]) &&
							! empty($contents["EXT_USER_CODE"]) &&
							// ! Use DB to identify which installation
							! empty($contents["EXT_DATABASE"]) &&
							$contents["EXT_DATABASE"] == $sts_database ) {
							$session_id = str_replace('sess_', '', $sfile);
							$username = $contents["EXT_USERNAME"];
							$user_code = $contents["EXT_USER_CODE"];
							$this->user_log->log_expired($user_code, 'Session expired, age = '.gmdate("H:i:s",$age).'<br>'.
								'user = '.$username.
								'<br>session id = '.$session_id);
						}
					}
					unlink($full_path);	// Remove the file
				}
			}
		}
	}

	//! Who - examine session files to see who is on
	public function who( $output = 'default' ) {
		global $sts_database;
		if( $this->debug ) echo "<p>".__METHOD__.": entry my ID = ".session_id()."</p>
			<p>Current script owner: " . get_current_user()."</p>";

		// Where all the session files are kept
		$session_path = str_replace('\\', '/', ini_get('session.save_path'));
		if( $this->debug ) echo "<p>".__METHOD__.": save_path = $session_path db = $sts_database</p>";
		$users = array();
		$found_already = [];

		if( is_dir($session_path) ) {
			$sfiles = scandir($session_path);	// Get list of files
			foreach( $sfiles as $sfile ) {
				$full_path = $session_path.'/'.$sfile;	// Full path to file
				$age = time() - filemtime($full_path);	// Age of the session file
				//echo "<pre>";
				//var_dump($full_path, time(), filemtime($full_path), $age, gmdate("H:i:s",$age), $this->session_lifetime);
				//echo "</pre>";
				
				if( $this->debug ) echo "<p>".__METHOD__.": full_path = $full_path</p>";

				// Those that are trully expired (double normal expiry)
				if(! in_array($sfile, array('.', '..', 'static_cache.txt')) &&
					strpos($sfile, 'sess_') === 0 &&
					strpos($sfile, 'backup_') === false &&
					strpos($sfile, '_cache.txt') === false &&
					$age < $this->session_lifetime) {
			
					// If we can read the file, get the info to log the expiry event
					if( $this->debug ) echo "<p>".__METHOD__.": $full_path readable = ".(is_readable($full_path) ? 'true' : 'false')." size = ".filesize($full_path)." perms = ".substr(sprintf('%o', fileperms($full_path)), -4)."</p>";
					
					// You can't open your own session file.
					if( is_readable($full_path) && filesize($full_path) > 0 &&
						$full_path <> session_save_path().'/sess_'.session_id() ) {
						$raw = @file_get_contents($full_path);
						if( $raw !== false ) {
							$contents = SessionFile::unserialize($raw);
							if( $this->debug ) echo "<p>".__METHOD__.": $full_path username = ".(empty($contents["EXT_USERNAME"]) ? 'none' : $contents["EXT_USERNAME"])." user_code = ".(empty($contents["EXT_USER_CODE"]) ? 'none' : $contents["EXT_USER_CODE"])." db = ".(empty($contents["EXT_DATABASE"]) ? 'none' : $contents["EXT_DATABASE"])."</p>";
							if( ! empty($contents["EXT_USERNAME"]) &&
								! empty($contents["EXT_USER_CODE"]) &&
								// ! Use DB to identify which installation
								! empty($contents["EXT_DATABASE"]) &&
								$contents["EXT_DATABASE"] == $sts_database ) {
								$users[] = '<a href="exp_edituser.php?CODE='.$contents["EXT_USER_CODE"].'" class="btn btn-info btn-xs tip-bottom" title="age = '.gmdate("H:i:s",$age).'">'.$contents["EXT_USERNAME"].'</a>';
							}
						}
					} else if( ! in_array($_SESSION["EXT_USERNAME"], $found_already) ) {
						$found_already[] = $_SESSION["EXT_USERNAME"];
						$users[] = '<a href="exp_edituser.php?CODE='.$_SESSION["EXT_USER_CODE"].'" class="btn btn-default btn-xs" >'.$_SESSION["EXT_USERNAME"].'</a>';
					}
				}
			}
		} else {
			if( $this->debug ) echo "<p>".__METHOD__.": $session_path not a directory!</p>";
		}
		if( $this->debug ) echo "<p>".__METHOD__.": return ".count($users)." users</p>";
		switch( $output ) {
			case 'count':
				$result	= count($users);
				break;
			
			case 'default':
			default:
				$result = count($users) > 0 ? '('.plural(count($users), 'user').') '.implode(' ', $users) : '';
				break;
				
		}
		
		return $result;
		
	}

}

//! Got this from here:
// https://stackoverflow.com/questions/4698432/read-the-session-data-from-session-storage-file
class SessionFile {
    public static function unserialize($session_data) {
        $method = ini_get("session.serialize_handler");
        switch ($method) {
            case "php":
                return self::unserialize_php($session_data);
                break;
            case "php_binary":
                return self::unserialize_phpbinary($session_data);
                break;
            default:
                throw new Exception("Unsupported session.serialize_handler: " . $method . ". Supported: php, php_binary");
        }
    }

    private static function unserialize_php($session_data) {
        $return_data = array();
        $offset = 0;
        while ($offset < strlen($session_data)) {
            if (!strstr(substr($session_data, $offset), "|")) {
                throw new Exception("invalid data, remaining: " . substr($session_data, $offset));
            }
            $pos = strpos($session_data, "|", $offset);
            $num = $pos - $offset;
            $varname = substr($session_data, $offset, $num);
            $offset += $num + 1;
            $data = unserialize(substr($session_data, $offset));
            $return_data[$varname] = $data;
            $offset += strlen(serialize($data));
        }
        return $return_data;
    }

    private static function unserialize_phpbinary($session_data) {
        $return_data = array();
        $offset = 0;
        while ($offset < strlen($session_data)) {
            $num = ord($session_data[$offset]);
            $offset += 1;
            $varname = substr($session_data, $offset, $num);
            $offset += $num;
            $data = unserialize(substr($session_data, $offset));
            $return_data[$varname] = $data;
            $offset += strlen(serialize($data));
        }
        return $return_data;
    }
}

} // _STS_SESSION_CONFIG

?>