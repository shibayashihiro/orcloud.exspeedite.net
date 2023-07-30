<?php

// no direct access
defined('_STS_INCLUDE') or die('Restricted access');
require_once( "sts_email_class.php" );
//require_once( "sts_setting_class.php" );

if( ! defined('EDI_MAPPING_RUAN') )
	define('EDI_MAPPING_RUAN', 'RUAN');
if( ! defined('EDI_MAPPING_PENSKE') )
	define('EDI_MAPPING_PENSKE', 'PENSKE');

class sts_ftp extends sts_table {
	private $setting_table;
	private $conn_id;
	public $last_error;

	private $ftp_client;
	
	// Constructor does not need the table name
	public function __construct( $database, $debug = false ) {

		$this->debug = $debug;
		$this->primary_key = "FTP_CODE";
		if( ! function_exists('ftp_connect') ) {
			echo "<h2>Function ftp_connect() is undefined.</h2>
			<p>It appears your PHP installation does not have the FTP enabled. This is needed for EDI operation.</p>
			<p>You can confirm this via the installer. Run the installer and click on <strong>Check PHP</strong>.</p>
			<p>If FTP is not enabled, you have to take steps to enable it or re-install PHP.</p>";
			die;
		}
		//$this->setting_table = sts_setting::getInstance($database, $debug);
		//$this->log_file = $this->setting_table->get( 'option', 'DEBUG_LOG_FILE' );
		if( $this->debug ) echo "<p>Create sts_ftp</p>";
		parent::__construct( $database, FTP_TABLE, $debug);
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

	function __destruct() {
		if( $this->debug ) echo "<p>Destroy sts_ftp</p>";
		if( isset($this->conn_id) ) $this->ftp_close();
	}
	
	//! Return a list of active clients
	public function active_clients() {
		$result = false;
		$clients = $this->fetch_rows( "FTP_ENABLED = 1", "FTP_REMOTE_ID" );
		if( is_array($clients) && count($clients) > 0 ) {
			$result = array();
			foreach( $clients as $client ) {
				if( isset($client["FTP_REMOTE_ID"]) )
					$result[] = $client["FTP_REMOTE_ID"];
			}
		}
		return $result;
	}
	
	//! Given a remote ID, return our id, or return false
	public function edi_our_id( $remote ) {
		$result = false;
		// The most likely case, the client is already in $this->ftp_client
		if( is_array($this->ftp_client) &&
			isset($this->ftp_client['FTP_REMOTE_ID']) &&
			$this->ftp_client['FTP_REMOTE_ID'] == $remote &&
			isset($this->ftp_client['FTP_OUR_ID']) ) {
			$result = $this->ftp_client['FTP_OUR_ID'];
		} else {	// Failsafe, look it up.
			$check = $this->database->get_one_row(
				"SELECT FTP_OUR_ID FROM EXP_EDI_FTP
				WHERE FTP_REMOTE_ID = '".$remote."'
				AND FTP_ENABLED = 1" );
			if( is_array($check) && isset($check['FTP_OUR_ID']) )
				$result = $check['FTP_OUR_ID'];
		}
		
		return $result;
	}
	
	//! Given a remote ID, return strict validation, or return false
	public function edi_strict( $remote ) {
		$result = false;
		// The most likely case, the client is already in $this->ftp_client
		if( is_array($this->ftp_client) &&
			isset($this->ftp_client['FTP_REMOTE_ID']) &&
			$this->ftp_client['FTP_REMOTE_ID'] == $remote &&
			isset($this->ftp_client['FTP_EDI_STRICT']) ) {
			$result = $this->ftp_client['FTP_EDI_STRICT'];
		} else {	// Failsafe, look it up.
			$check = $this->database->get_one_row(
				"SELECT FTP_EDI_STRICT FROM EXP_EDI_FTP
				WHERE FTP_REMOTE_ID = '".$remote."'
				AND FTP_ENABLED = 1" );
			if( is_array($check) && isset($check['FTP_EDI_STRICT']) )
				$result = $check['FTP_EDI_STRICT'];
		}
		
		return $result;
	}
	
	//! Given a remote ID, return the format, or return false
	public function edi_format( $remote ) {
		$result = false;
		// The most likely case, the client is already in $this->ftp_client
		if( isset($this->ftp_client) && is_array($this->ftp_client) &&
			isset($this->ftp_client['FTP_REMOTE_ID']) &&
			$this->ftp_client['FTP_REMOTE_ID'] == $remote &&
			isset($this->ftp_client['FTP_EDI_FORMAT']) ) {
			$result = $this->ftp_client['FTP_EDI_FORMAT'];
		} else {	// Failsafe, look it up.
			$check = $this->database->get_one_row(
				"SELECT FTP_EDI_FORMAT FROM EXP_EDI_FTP
				WHERE FTP_REMOTE_ID = '".$remote."'
				AND FTP_ENABLED = 1" );
			if( is_array($check) && isset($check['FTP_EDI_FORMAT']) )
				$result = $check['FTP_EDI_FORMAT'];
		}
		
		return $result;
	}
	
	//! Given a remote ID, return the terminator, or return false
	public function edi_terminator( $remote ) {
		$result = false;
		// The most likely case, the client is already in $this->ftp_client
		if( is_array($this->ftp_client) &&
			isset($this->ftp_client['FTP_REMOTE_ID']) &&
			$this->ftp_client['FTP_REMOTE_ID'] == $remote &&
			isset($this->ftp_client['FTP_EDI_TERMINATOR']) ) {
			$result = $this->ftp_client['FTP_EDI_TERMINATOR'];
		} else {	// Failsafe, look it up.
			$check = $this->database->get_one_row(
				"SELECT FTP_EDI_TERMINATOR FROM EXP_EDI_FTP
				WHERE FTP_REMOTE_ID = '".$remote."'
				AND FTP_ENABLED = 1" );
			if( is_array($check) && isset($check['FTP_EDI_TERMINATOR']) )
				$result = $check['FTP_EDI_TERMINATOR'];
		}
		
		return $result;
	}
	
	//! Given a remote ID, return the separator, or return false
	public function edi_separator( $remote ) {
		$result = false;
		// The most likely case, the client is already in $this->ftp_client
		if( is_array($this->ftp_client) &&
			isset($this->ftp_client['FTP_REMOTE_ID']) &&
			$this->ftp_client['FTP_REMOTE_ID'] == $remote &&
			isset($this->ftp_client['FTP_EDI_SEPARATOR']) ) {
			$result = $this->ftp_client['FTP_EDI_SEPARATOR'];
		} else {	// Failsafe, look it up.
			$check = $this->database->get_one_row(
				"SELECT FTP_EDI_SEPARATOR FROM EXP_EDI_FTP
				WHERE FTP_REMOTE_ID = '".$remote."'
				AND FTP_ENABLED = 1" );
			if( is_array($check) && isset($check['FTP_EDI_SEPARATOR']) )
				$result = $check['FTP_EDI_SEPARATOR'];
		}
		
		return $result;
	}
	
	//! Connect, login, chdir and get list of files in directory
	public function ftp_connect( $client ) {
		if( $this->debug ) echo "<p>".__METHOD__.": Connect to client $client</p>";
		$result = false;
		$this->last_error = '';
		$this->ftp_client = $this->database->get_one_row(
			"SELECT * FROM EXP_EDI_FTP
			WHERE FTP_REMOTE_ID = '".$client."'
			AND FTP_ENABLED = 1" );
		
		if( is_array($this->ftp_client) ) {
			$ipaddr = gethostbyname($this->ftp_client['FTP_SERVER']);
			if( $this->debug ) echo "<p>".__METHOD__.": ".$this->ftp_client['FTP_SERVER']." =>  $ipaddr, port ".$this->ftp_client['FTP_PORT']."</p>";
			$this->conn_id = ftp_connect($ipaddr, $this->ftp_client['FTP_PORT']);
			
			if( $this->conn_id ) {
				// login with username and password
				if( $this->debug ) echo "<p>".__METHOD__.": login ".$this->ftp_client['FTP_USER_NAME'].", ".$this->ftp_client['FTP_USER_PASS']."</p>";
				if( @ftp_login($this->conn_id, $this->ftp_client['FTP_USER_NAME'],
					$this->ftp_client['FTP_USER_PASS']) ) {
					
					if( $this->ftp_client['FTP_USER_PASS'] )
						ftp_pasv( $this->conn_id, true );		// enable passive mode
					
					// Change directory
					if( $this->debug ) echo "<p>".__METHOD__.": chdir ".$this->ftp_client['FTP_DL_PATH']."</p>";
					if (ftp_chdir($this->conn_id, $this->ftp_client['FTP_DL_PATH'])) {

						// get contents of the current directory
						$dir = ! empty($this->ftp_client['FTP_DL_SUFFIX']) ? '*'.$this->ftp_client['FTP_DL_SUFFIX'] : '*';
						if( $this->debug ) echo "<p>".__METHOD__.": nlist ".$dir."</p>";
						$result = ftp_nlist($this->conn_id, $dir );
						if( $result == false )
							$result = array();
						
					} else { 
					    $this->last_error =  "ftp_connect: Couldn't change directory to ".$this->ftp_client['FTP_DL_PATH'];
					}					
				} else {
					$this->last_error =  "ftp_connect: Unable to login to ".$this->ftp_client['FTP_SERVER'].
						' with credentials ('.$this->ftp_client['FTP_USER_NAME'].', '.
						$this->ftp_client['FTP_USER_PASS'].')';
				}
			} else {
				$this->last_error = 'ftp_connect: Unable to connect to '.$this->ftp_client['FTP_SERVER'];
			}
		} else {
			$this->last_error = 'ftp_connect: Client '.$client.' not found.';
		}
		
		if( $this->debug ) echo "<p>".__METHOD__.": return ".(is_array($result) ? 'array('.count($result).')' : ($result ? 'true' : 'false') )."</p>";
		return $result;
	}

	//! Browse files in directory
	public function ftp_browse( $client, $url, $path = '/' ) {
		if( $this->debug ) echo "<p>".__METHOD__.": Connect to client $client</p>";
		$result = false;
		$this->last_error = '';
		$this->ftp_client = $this->database->get_one_row(
			"SELECT * FROM EXP_EDI_FTP
			WHERE FTP_CODE = ".$client );
		
		if( is_array($this->ftp_client) ) {
			$ipaddr = gethostbyname($this->ftp_client['FTP_SERVER']);
			if( $this->debug ) echo "<p>".__METHOD__.": ".$this->ftp_client['FTP_SERVER']." =>  $ipaddr, port ".$this->ftp_client['FTP_PORT']."</p>";
			$this->conn_id = ftp_connect($ipaddr, $this->ftp_client['FTP_PORT']);
			
			if( $this->conn_id ) {
				// login with username and password
				if( $this->debug ) echo "<p>".__METHOD__.": login ".$this->ftp_client['FTP_USER_NAME'].", ".$this->ftp_client['FTP_USER_PASS']."</p>";
				if( ftp_login($this->conn_id, $this->ftp_client['FTP_USER_NAME'],
					$this->ftp_client['FTP_USER_PASS']) ) {
					
					if( $this->ftp_client['FTP_USER_PASS'] )
						ftp_pasv( $this->conn_id, true );		// enable passive mode
					
					// List directory
					$contents = ftp_rawlist($this->conn_id, $path);
					$dl = $this->ftp_client['FTP_DL_PATH'];
					if( substr($dl, -1) <> '/' )
						$dl .= '/';
					$ul = $this->ftp_client['FTP_UL_PATH'];
					if( substr($ul, -1) <> '/' )
						$ul .= '/';
					$result = "<h4>Client: $client Server: ".$this->ftp_client['FTP_SERVER']." Directory: $path".($path == $dl ? ' (Download Directory)' :
						($path == $ul ? ' (Upload Directory)' : '' ) )."</h4>\n";
					if( $path <> '/' ) {
						$arr = explode('/', $path);
						array_splice( $arr, count($arr)-2, 1);
						$up = implode('/', $arr);
						$result .= '<a class="browse_ftp" href="'.$url.'?CLIENT='.$client.'&CWD='.$up.'">Parent Directory</a><br>';
					}
					foreach($contents as $line) {
						if( $line[0] == 'd' ) {
							$arr = explode(' ', $line);
							$last = end($arr);
							$line = '<a class="browse_ftp" href="'.$url.'?CLIENT='.$client.'&CWD='.$path.$last.'/">'.$line.'</a>';
						}
						$result .= $line.'<br>';
					}

				} else {
					$this->last_error =  "ftp_connect: Unable to login to ".$this->ftp_client['FTP_SERVER'].
						' with credentials ('.$this->ftp_client['FTP_USER_NAME'].', '.
						$this->ftp_client['FTP_USER_PASS'].')';
				}
			} else {
				$this->last_error = 'ftp_connect: Unable to connect to '.$this->ftp_client['FTP_SERVER'];
			}
		} else {
			$this->last_error = 'ftp_connect: Client '.$client.' not found.';
		}
		
		return $result;
	}

	//! Fetch a file, return it as a string
	public function ftp_get_contents( $filename, $delete = false ) {
		if( $this->debug ) echo "<p>".__METHOD__.": Get $filename, ".($delete ? "true" : "false")."</p>";
		$result = false;
		$this->last_error = '';
	    // Create temp handle
	    $tempHandle = fopen('php://temp', 'r+');
	   
		// Change to DL directory
		if( $this->debug ) echo "<p>".__METHOD__.": chdir  ".$this->ftp_client['FTP_DL_PATH']."</p>";
		if (ftp_chdir($this->conn_id, $this->ftp_client['FTP_DL_PATH'])) {
		    // Get file from FTP:
		    if (@ftp_fget($this->conn_id, $tempHandle, $filename, FTP_ASCII, 0)) {
		        rewind($tempHandle);
		        $result = stream_get_contents($tempHandle);
		        
		        if( ! $result ) {
			        $this->last_error =  "ftp_get_contents: Couldn't read from temp file.";
		        }
		        
		        // Delete file
		        if( $result && $delete ) {
			        ftp_delete($this->conn_id, $filename);
		        }
		    } else { 
		    	$this->last_error =  "ftp_get_contents: Couldn't download ".$filename;
		    }
		} else { 
		    $this->last_error =  "ftp_get_contents: Couldn't change directory to ".$this->ftp_client['FTP_DL_PATH'];
		}
	    
		if( $this->debug ) echo "<p>".__METHOD__.": return ".($result ? strlen($result)." chars" : "false")."</p>";
	    return $result;
	}

	//! Delete a file
	public function ftp_delete_file( $filename ) {
		if( $this->debug ) echo "<p>".__METHOD__.": $filename</p>";
		$result = false;
		if( isset($this->conn_id) && isset($this->ftp_client) &&
			ftp_chdir($this->conn_id, $this->ftp_client['FTP_DL_PATH'])) {
			$result = ftp_delete($this->conn_id, $filename);
		}
	    return $result;
	}

	//! Send a file from a string
	public function ftp_put_contents( $filename, $contents ) {
		if( $this->debug ) echo "<p>".__METHOD__.": Put $filename</p>";
		$result = false;
		$this->last_error = '';
	    // Create temp handle
	    if( $tempHandle = fopen('php://temp', 'r+') ) {
		    if( fwrite($tempHandle, $contents) && rewind($tempHandle) ) {
	   
				// Change to UL directory
				if( $this->debug ) echo "<p>".__METHOD__.": chdir  ".$this->ftp_client['FTP_UL_PATH']."</p>";
				if (ftp_chdir($this->conn_id, $this->ftp_client['FTP_UL_PATH'])) {
			  	  // Put file to FTP:
			  	  	if( $this->debug ) echo "<p>".__METHOD__.": put $filename</p>";
				    $result = ftp_fput($this->conn_id, $filename, $tempHandle, FTP_ASCII, 0);
			    } else { 
				    $this->last_error =  "ftp_put_contents: Couldn't change directory to ".$this->ftp_client['FTP_UL_PATH'];
				}
			} else { 
			    $this->last_error =  "ftp_put_contents: unable to write temp file";
			}

		} else { 
		    $this->last_error =  "ftp_put_contents: unable to open temp file";
		}
		
		if( $this->debug ) echo "<p>".__METHOD__.": return ".($result ? "true" : "false")."</p>";
	    return $result;
	}

	//! Close the connection
	public function ftp_close() {
		if( $this->debug ) echo "<p>".__METHOD__.": close</p>";
		if( isset($this->conn_id) && is_resource($this->conn_id) )
			ftp_close($this->conn_id);
		unset( $this->conn_id );
		unset( $this->ftp_client );
	}

	//! Flush the connection
	public function ftp_flush( $client ) {
		$count = 0;
		$files = $this->ftp_connect( $client );
		if( is_array($files) && count($files) > 0 ) {
			foreach( $files as $filename ) {
				$this->ftp_delete_file( $filename );
				$count++;
			}
		}
		$this->ftp_close();
		return $count;
	}
	
}

//! Form Specifications - For use with sts_form

$sts_form_addftp_form = array(	//! $sts_form_addftp_form
	'title' => '<img src="images/edi_icon1.png" alt="setting_icon" height="40"> Add FTP Configuration',
	'action' => 'exp_addftp.php',
	//'actionextras' => 'disabled',
	'cancel' => 'exp_listftp.php',
	'name' => 'addftp',
	'okbutton' => 'Save Changes',
	//'saveadd' => 'Add Another',
	'cancelbutton' => 'Cancel',
		'layout' => '
	<div class="form-group">
		<div class="col-sm-4">
			<div class="form-group">
				<label for="FTP_ENABLED" class="col-sm-5 control-label">#FTP_ENABLED#</label>
				<div class="col-sm-7">
					%FTP_ENABLED%
				</div>
			</div>
			<!-- CC01 -->
			<div class="form-group">
				<label for="OFFICE_CODE" class="col-sm-5 control-label">#OFFICE_CODE#</label>
				<div class="col-sm-7">
					%OFFICE_CODE%
				</div>
			</div>
			<!-- CC02 -->
			<div class="form-group">
				<label for="TRANSPORT" class="col-sm-5 control-label">#TRANSPORT#</label>
				<div class="col-sm-7">
					%TRANSPORT%
				</div>
			</div>
			<div class="form-group">
				<label for="FTP_EDI_STRICT" class="col-sm-5 control-label">#FTP_EDI_STRICT#</label>
				<div class="col-sm-7">
					%FTP_EDI_STRICT%
				</div>
			</div>
			<div class="form-group">
				<label for="FTP_REMOTE_ID" class="col-sm-5 control-label">#FTP_REMOTE_ID#</label>
				<div class="col-sm-7">
					%FTP_REMOTE_ID%
				</div>
			</div>
			<div class="form-group">
				<label for="FTP_OUR_ID" class="col-sm-5 control-label">#FTP_OUR_ID#</label>
				<div class="col-sm-7">
					%FTP_OUR_ID%
				</div>
			</div>
			<div class="form-group">
				<label for="FTP_EDI_FORMAT" class="col-sm-5 control-label">#FTP_EDI_FORMAT#</label>
				<div class="col-sm-7">
					%FTP_EDI_FORMAT%
				</div>
			</div>
			<div class="form-group">
				<label for="FTP_EDI_TERMINATOR" class="col-sm-5 control-label">#FTP_EDI_TERMINATOR#</label>
				<div class="col-sm-7">
					%FTP_EDI_TERMINATOR%
				</div>
			</div>
			<div class="form-group">
				<label for="FTP_EDI_SEPARATOR" class="col-sm-5 control-label">#FTP_EDI_SEPARATOR#</label>
				<div class="col-sm-7">
					%FTP_EDI_SEPARATOR%
				</div>
			</div>
		</div>
		<div class="col-sm-4">
			<div class="form-group FTP">
				<label for="FTP_SERVER" class="col-sm-5 control-label">#FTP_SERVER#</label>
				<div class="col-sm-7">
					%FTP_SERVER%
				</div>
			</div>
			<div class="form-group FTP">
				<label for="FTP_PORT" class="col-sm-5 control-label">#FTP_PORT#</label>
				<div class="col-sm-7">
					%FTP_PORT%
				</div>
			</div>
			<div class="form-group FTP">
				<label for="FTP_USER_NAME" class="col-sm-5 control-label">#FTP_USER_NAME#</label>
				<div class="col-sm-7">
					%FTP_USER_NAME%
				</div>
			</div>
			<div class="form-group FTP">
				<label for="FTP_USER_PASS" class="col-sm-5 control-label">#FTP_USER_PASS#</label>
				<div class="col-sm-7">
					%FTP_USER_PASS%
				</div>
			</div>
			<div class="form-group FTP">
				<label for="FTP_PASV" class="col-sm-5 control-label">#FTP_PASV#</label>
				<div class="col-sm-7">
					%FTP_PASV%
				</div>
			</div>
			<div class="form-group">
				<label for="FTP_UL_PATH" class="col-sm-5 control-label">#FTP_UL_PATH#</label>
				<div class="col-sm-7">
					%FTP_UL_PATH%
				</div>
			</div>
			<div class="form-group">
				<label for="FTP_DL_PATH" class="col-sm-5 control-label">#FTP_DL_PATH#</label>
				<div class="col-sm-7">
					%FTP_DL_PATH%
				</div>
			</div>
			<div class="form-group">
				<label for="FTP_DL_SUFFIX" class="col-sm-5 control-label">#FTP_DL_SUFFIX#</label>
				<div class="col-sm-7">
					%FTP_DL_SUFFIX%
				</div>
			</div>
		</div>
		<div class="col-sm-4">
			<div class="alert alert-info">
				<p><strong>Office</strong> applies to multi-company mode and is for incoming EDI 204s. All incoming EDI 204s will be assigned to this office.</p>
				<p><strong>Transport</strong> can be either FTP Transfer for connecting to remote server or File Copy for local to this server.</p>
				<p><strong>Strict</strong> means tighter validation of incoming EDI. If you are losing EDI messages, check the log for why.</p>
				<p>The <strong>EDI format</strong> determines mapping from 204s to Exspeedite and what goes into 990s and 214s.</p>
				<p>The <strong>EDI terminator</strong> determines the segment terminators for 990s and 214s.</p>
				<p>The <strong>EDI separator</strong> determines the element separators for 990s and 214s.</p>
				<p><strong>Port number</strong> is usually 21, but could be 22 for secure FTP.</p>
				<p><strong>Passive Mode</strong> is if you want to connect in PASV mode. Most likely leave this off.</p>
				<p>The <strong>upload path</strong> is the remote directory where 990s and 214s are put.</p>
				<p>The <strong>download path</strong> is the remote directory where 204s are fetched from.</p>
				<p>The <strong>file suffix</strong> is usually .edi or leave it blank for no suffix. Browse their server to confirm.</p>
			</div>
		</div>
	</div>
	
	'
);

$sts_form_editftp_form = array( //! $sts_form_editftp_form
	'title' => '<img src="images/edi_icon1.png" alt="setting_icon" height="40"> Edit FTP Configuration',
	'action' => 'exp_editftp.php',
	//'actionextras' => 'disabled',
	'cancel' => 'exp_listftp.php',
	'name' => 'editftp',
	'okbutton' => 'Save FTP Configuration',
	'cancelbutton' => 'Back',
		'layout' => '
		%FTP_CODE%
	<div class="form-group">
		<div class="col-sm-4">
			<div class="form-group">
				<label for="FTP_ENABLED" class="col-sm-5 control-label">#FTP_ENABLED#</label>
				<div class="col-sm-7">
					%FTP_ENABLED%
				</div>
			</div>
			<!-- CC01 -->
			<div class="form-group">
				<label for="OFFICE_CODE" class="col-sm-5 control-label">#OFFICE_CODE#</label>
				<div class="col-sm-7">
					%OFFICE_CODE%
				</div>
			</div>
			<!-- CC02 -->
			<div class="form-group">
				<label for="TRANSPORT" class="col-sm-5 control-label">#TRANSPORT#</label>
				<div class="col-sm-7">
					%TRANSPORT%
				</div>
			</div>
			<div class="form-group">
				<label for="FTP_EDI_STRICT" class="col-sm-5 control-label">#FTP_EDI_STRICT#</label>
				<div class="col-sm-7">
					%FTP_EDI_STRICT%
				</div>
			</div>
			<div class="form-group">
				<label for="FTP_REMOTE_ID" class="col-sm-5 control-label">#FTP_REMOTE_ID#</label>
				<div class="col-sm-7">
					%FTP_REMOTE_ID%
				</div>
			</div>
			<div class="form-group">
				<label for="FTP_OUR_ID" class="col-sm-5 control-label">#FTP_OUR_ID#</label>
				<div class="col-sm-7">
					%FTP_OUR_ID%
				</div>
			</div>
			<div class="form-group">
				<label for="FTP_EDI_FORMAT" class="col-sm-5 control-label">#FTP_EDI_FORMAT#</label>
				<div class="col-sm-7">
					%FTP_EDI_FORMAT%
				</div>
			</div>
			<div class="form-group">
				<label for="FTP_EDI_TERMINATOR" class="col-sm-5 control-label">#FTP_EDI_TERMINATOR#</label>
				<div class="col-sm-7">
					%FTP_EDI_TERMINATOR%
				</div>
			</div>
			<div class="form-group">
				<label for="FTP_EDI_SEPARATOR" class="col-sm-5 control-label">#FTP_EDI_SEPARATOR#</label>
				<div class="col-sm-7">
					%FTP_EDI_SEPARATOR%
				</div>
			</div>
		</div>
		<div class="col-sm-4">
			<div class="form-group FTP">
				<label for="FTP_SERVER" class="col-sm-5 control-label">#FTP_SERVER#</label>
				<div class="col-sm-7">
					%FTP_SERVER%
				</div>
			</div>
			<div class="form-group FTP">
				<label for="FTP_PORT" class="col-sm-5 control-label">#FTP_PORT#</label>
				<div class="col-sm-7">
					%FTP_PORT%
				</div>
			</div>
			<div class="form-group FTP">
				<label for="FTP_USER_NAME" class="col-sm-5 control-label">#FTP_USER_NAME#</label>
				<div class="col-sm-7">
					%FTP_USER_NAME%
				</div>
			</div>
			<div class="form-group FTP">
				<label for="FTP_USER_PASS" class="col-sm-5 control-label">#FTP_USER_PASS#</label>
				<div class="col-sm-7">
					%FTP_USER_PASS%
				</div>
			</div>
			<div class="form-group FTP">
				<label for="FTP_PASV" class="col-sm-5 control-label">#FTP_PASV#</label>
				<div class="col-sm-7">
					%FTP_PASV%
				</div>
			</div>
			<div class="form-group">
				<label for="FTP_UL_PATH" class="col-sm-5 control-label">#FTP_UL_PATH#</label>
				<div class="col-sm-7">
					%FTP_UL_PATH%
				</div>
			</div>
			<div class="form-group">
				<label for="FTP_DL_PATH" class="col-sm-5 control-label">#FTP_DL_PATH#</label>
				<div class="col-sm-7">
					%FTP_DL_PATH%
				</div>
			</div>
			<div class="form-group">
				<label for="FTP_DL_SUFFIX" class="col-sm-5 control-label">#FTP_DL_SUFFIX#</label>
				<div class="col-sm-7">
					%FTP_DL_SUFFIX%
				</div>
			</div>
		</div>
		<div class="col-sm-4">
			<div class="alert alert-info">
				<p><strong>Office</strong> applies to multi-company mode and is for incoming EDI 204s. All incoming EDI 204s will be assigned to this office.</p>
				<p><strong>Transport</strong> can be either FTP Transfer for connecting to remote server or File Copy for local to this server.</p>
				<p><strong>Strict</strong> means tighter validation of incoming EDI. If you are losing EDI messages, check the log for why.</p>
				<p>The <strong>EDI format</strong> determines mapping from 204s to Exspeedite and what goes into 990s and 214s.</p>
				<p>The <strong>EDI terminator</strong> determines the segment terminators for 990s and 214s.</p>
				<p>The <strong>EDI separator</strong> determines the element separators for 990s and 214s.</p>
				<p><strong>Port number</strong> is usually 21, but could be 22 for secure FTP.</p>
				<p><strong>Passive Mode</strong> is if you want to connect in PASV mode. Most likely leave this off.</p>
				<p>The <strong>upload path</strong> is the remote directory where 990s and 214s are put.</p>
				<p>The <strong>download path</strong> is the remote directory where 204s are fetched from.</p>
				<p>The <strong>file suffix</strong> is usually .edi or leave it blank for no suffix. Browse their server to confirm.</p>
			</div>
		</div>
	</div>
	
	'
);

//! Field Specifications - For use with sts_form

$sts_form_add_ftp_fields = array( //! $sts_form_add_ftp_fields
	'FTP_ENABLED' => array( 'label' => 'Enabled', 'align' => 'right', 'format' => 'bool2' ),
	'FTP_EDI_STRICT' => array( 'label' => 'Strict', 'align' => 'right', 'format' => 'bool2' ),
	'FTP_REMOTE_ID' => array( 'label' => 'Remote ID', 'format' => 'text' ),
	'FTP_OUR_ID' => array( 'label' => 'Our SCAC', 'format' => 'text' ),
	'FTP_SERVER' => array( 'label' => 'FTP Server', 'format' => 'text' ),
	'FTP_PORT' => array( 'label' => 'Port Number', 'format' => 'number' ),
	'FTP_PASV' => array( 'label' => 'Passive Mode', 'align' => 'right', 'format' => 'bool2' ),
	'FTP_USER_NAME' => array( 'label' => 'Username', 'format' => 'text' ),
	'FTP_USER_PASS' => array( 'label' => 'Password', 'format' => 'text' ),
	'FTP_UL_PATH' => array( 'label' => 'Upload Path', 'format' => 'text' ),
	'FTP_DL_PATH' => array( 'label' => 'Download Path', 'format' => 'text' ),
	'FTP_DL_SUFFIX' => array( 'label' => 'File Suffix', 'format' => 'text' ),
	'FTP_EDI_FORMAT' => array( 'label' => 'EDI Format', 'format' => 'enum' ),
	'FTP_EDI_TERMINATOR' => array( 'label' => 'EDI Separator', 'format' => 'enum' ),
	'FTP_EDI_SEPARATOR' => array( 'label' => 'EDI Separator', 'format' => 'enum' ),
	'OFFICE_CODE' => array( 'label' => 'Office', 'format' => 'table',
		'table' => OFFICE_TABLE, 'key' => 'OFFICE_CODE', 'fields' => 'OFFICE_NAME' ),
	'TRANSPORT' => array( 'label' => 'Transport', 'format' => 'enum' ),
);

$sts_form_edit_ftp_fields = array( //! $sts_form_edit_ftp_fields
	'FTP_CODE' => array( 'format' => 'hidden' ),
	'FTP_ENABLED' => array( 'label' => 'Enabled', 'align' => 'right', 'format' => 'bool2' ),
	'FTP_EDI_STRICT' => array( 'label' => 'Strict', 'align' => 'right', 'format' => 'bool2' ),
	'FTP_REMOTE_ID' => array( 'label' => 'Remote ID', 'format' => 'text' ),
	'FTP_OUR_ID' => array( 'label' => 'Our SCAC', 'format' => 'text' ),
	'FTP_SERVER' => array( 'label' => 'FTP Server', 'format' => 'text' ),
	'FTP_PORT' => array( 'label' => 'Port Number', 'format' => 'number' ),
	'FTP_PASV' => array( 'label' => 'Passive Mode', 'align' => 'right', 'format' => 'bool2' ),
	'FTP_USER_NAME' => array( 'label' => 'Username', 'format' => 'text' ),
	'FTP_USER_PASS' => array( 'label' => 'Password', 'format' => 'text' ),
	'FTP_UL_PATH' => array( 'label' => 'Upload Path', 'format' => 'text' ),
	'FTP_DL_PATH' => array( 'label' => 'Download Path', 'format' => 'text' ),
	'FTP_DL_SUFFIX' => array( 'label' => 'File Suffix', 'format' => 'text' ),
	'FTP_EDI_FORMAT' => array( 'label' => 'EDI Format', 'format' => 'enum' ),
	'FTP_EDI_TERMINATOR' => array( 'label' => 'EDI Terminator', 'format' => 'enum' ),
	'FTP_EDI_SEPARATOR' => array( 'label' => 'EDI Separator', 'format' => 'enum' ),
	'OFFICE_CODE' => array( 'label' => 'Office', 'format' => 'table',
		'table' => OFFICE_TABLE, 'key' => 'OFFICE_CODE', 'fields' => 'OFFICE_NAME' ),
	'TRANSPORT' => array( 'label' => 'Transport', 'format' => 'enum' ),
);

//! Layout Specifications - For use with sts_result

$sts_result_ftp_layout = array(
	'FTP_CODE' => array( 'format' => 'hidden' ),
	'FTP_ENABLED' => array( 'label' => 'Enabled', 'align' => 'center', 'format' => 'bool' ),
	'FTP_EDI_STRICT' => array( 'label' => 'Strict', 'align' => 'center', 'format' => 'bool' ),
	'FTP_REMOTE_ID' => array( 'label' => 'Remote ID', 'format' => 'text' ),
	'FTP_OUR_ID' => array( 'label' => 'Our SCAC', 'format' => 'text' ),
	'FTP_EDI_FORMAT' => array( 'label' => 'EDI Format', 'format' => 'text' ),
	'FTP_SERVER' => array( 'label' => 'Server', 'format' => 'text' ),
	'FTP_PORT' => array( 'label' => 'Port', 'format' => 'num0' ),
	'FTP_PASV' => array( 'label' => 'PASV', 'align' => 'center', 'format' => 'bool' ),
	'OFFICE_CODE' => array( 'label' => 'Office', 'format' => 'table',
		'table' => OFFICE_TABLE, 'key' => 'OFFICE_CODE', 'fields' => 'OFFICE_NAME' ),
	'TRANSPORT' => array( 'label' => 'Transport', 'format' => 'enum' ),
	//'FTP_USER_NAME' => array( 'label' => 'Username', 'format' => 'text' ),
	//'FTP_USER_PASS' => array( 'label' => 'Password', 'format' => 'text' ),
	//'FTP_UL_PATH' => array( 'label' => 'Upload', 'format' => 'text' ),
	//'FTP_DL_PATH' => array( 'label' => 'Download', 'format' => 'text' ),
	//'FTP_DL_SUFFIX' => array( 'label' => 'Suffix', 'format' => 'text' ),

	'CHANGED_DATE' => array( 'label' => 'Changed', 'format' => 'timestamp-s' ),
	'CHANGED_BY' => array( 'label' => 'Changed By', 'format' => 'table',
		'table' => USER_TABLE, 'key' => 'USER_CODE', 'fields' => 'USERNAME' ),

);

//! Edit/Delete Button Specifications - For use with sts_result

$sts_result_ftp_edit = array(
	'title' => '<img src="images/edi_icon1.png" alt="setting_icon" height="40"> FTP Configuration',
	'sort' => 'FTP_CODE asc',
	'cancel' => 'index.php',
	'add' => 'exp_addftp.php',
	//'actionextras' => 'disabled',
	'addbutton' => 'Add FTP Settings',
	'cancelbutton' => 'Back',
	'rowbuttons' => array(
		array( 'url' => 'exp_editftp.php?CODE=', 'key' => 'FTP_CODE', 'label' => 'FTP_REMOTE_ID', 'tip' => 'Edit EDI FTP Settings for ', 'icon' => 'glyphicon glyphicon-edit' ),
		array( 'url' => 'exp_browse_ftp.php?CLIENT=', 'class' => 'browse_ftp', 'key' => 'FTP_CODE', 'label' => 'FTP_REMOTE_ID', 'tip' => 'Browse remote FTP for ', 'icon' => 'glyphicon glyphicon-search' ),
		array( 'url' => 'exp_listftp.php?flush=', 'key' => 'FTP_REMOTE_ID', 'label' => 'FTP_REMOTE_ID', 'tip' => 'Flush EDI FTP files for ',
		'tip2' => '<br>The configuration needs to be enabled to work. Will DELETE ALL 204 LOAD OFFER FILES from the remote server.<br><br>Is this what you really want?',
		'icon' => 'glyphicon glyphicon-remove', 'confirm' => 'yes' ),
		array( 'url' => 'exp_deleteftp.php?CODE=', 'key' => 'FTP_CODE', 'label' => 'FTP_REMOTE_ID', 'tip' => 'Delete EDI FTP Settings for ', 'icon' => 'glyphicon glyphicon-trash', 'confirm' => 'yes' )
	)
);
