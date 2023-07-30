<?php

// $Id: sts_card_transport_class.php 5030 2023-04-12 20:31:34Z duncan $
// - Fuel card Transport class

// no direct access
defined('_STS_INCLUDE') or die('Restricted access');
//require_once( "sts_email_class.php" );
require_once( "sts_setting_class.php" );
require_once( "sts_card_comdata_class.php" );
require_once( "sts_card_csv_class.php" );


class sts_card_transport extends sts_table {
	protected $ftp_client;
	
	protected $setting_table;
	protected $conn_id;
	public $last_error;
	protected $card_log;
	protected $card_diag_level;	// Text
	protected $diag_level;		// numeric version
	protected $comdata;
	protected $csv;
	protected $import_stats = false;
	protected $message;

	// Constructor does not need the table name
	public function __construct( $database, $debug = false ) {
		global $sts_error_level_label, $sts_crm_dir;

		$this->debug = $debug;
		$this->primary_key = "FTP_CODE";
		$this->setting_table = sts_setting::getInstance($database, $debug);
		$this->comdata = sts_card_comdata::getInstance($database, $debug, $this);
		$this->csv = sts_card_csv::getInstance($database, $debug, $this);

		$this->card_log = $this->setting_table->get( 'api', 'FUELCARD_LOG_FILE' );
		if( isset($this->card_log) && $this->card_log <> '' && 
			$this->card_log[0] <> '/' && $this->card_log[0] <> '\\' 
			&& $this->card_log[1] <> ':' )
			$this->card_log = $sts_crm_dir.$this->card_log;

		$this->card_diag_level = $this->setting_table->get( 'api', 'FUELCARD_DIAG_LEVEL' );
		$this->diag_level =  array_search(strtolower($this->card_diag_level), $sts_error_level_label);
		if( $this->diag_level === false ) $this->diag_level = EXT_ERROR_ALL;

		if( $this->debug ) echo "<p>Create sts_card_transport</p>";
		parent::__construct( $database, CARD_FTP_TABLE, $debug);
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
		//if( $this->debug ) echo "<p>log_event: $this->card_log, $message, level=$level</p>";
		$this->message = $message;
		if( $this->diag_level >= $level ) {
			if( (file_exists($this->card_log) && is_writable($this->card_log)) ||
				is_writable(dirname($this->card_log)) ) 
				file_put_contents($this->card_log, date('m/d/Y h:i:s A')." pid=".getmypid().
					" msg=".$message."\n\n", FILE_APPEND);
		}
	}
	
	function __destruct() {
		if( $this->debug ) echo "<p>Destroy sts_card_transport</p>";
		if( isset($this->conn_id) ) $this->ftp_close();
	}
	
	//! Duplicate FTP row.
	public function duplicate( $code ) {
		$result = false;
		$values = $this->fetch_rows($this->primary_key." = ".$code,
			"FTP_TRANSPORT, FTP_SERVER, FTP_PORT, FTP_PASV, FTP_USER_NAME,
			FTP_UL_PATH, FTP_USER_PASS, FTP_DL_PATH, FTP_DL_SUFFIX,
			FTP_OUR_ID, FTP_DELETE_AFTER_IMPORT, FTP_ENABLED, FTP_REMOTE_ID,
			FTP_CARD_FORMAT");
		if( is_array($values) && count($values) == 1 ) {
			$values[0]["FTP_REMOTE_ID"] .= '_copy';
			$result = $this->add( $values[0] );
		}
		
		return $result;
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
	
	//! Given a client ID, return our id, or return false
	public function edi_our_id( $client ) {
		$result = false;
		// The most likely case, the client is already in $this->ftp_client
		if( is_array($this->ftp_client) &&
			isset($this->ftp_client['FTP_REMOTE_ID']) &&
			$this->ftp_client['FTP_REMOTE_ID'] == $client &&
			isset($this->ftp_client['FTP_OUR_ID']) ) {
			$result = $this->ftp_client['FTP_OUR_ID'];
		} else {	// Failsafe, look it up.
			$check = $this->database->get_one_row(
				"SELECT FTP_OUR_ID FROM EXP_CARD_FTP
				WHERE FTP_REMOTE_ID = '".$client."'
				AND FTP_ENABLED = 1" );
			if( is_array($check) && isset($check['FTP_OUR_ID']) )
				$result = $check['FTP_OUR_ID'];
		}
		
		return $result;
	}
	
	//! Given a client ID, return the transport, or return false
	public function transport( $client ) {
		$result = false;
		// The most likely case, the client is already in $this->ftp_client
		if( is_array($this->ftp_client) &&
			isset($this->ftp_client['FTP_REMOTE_ID']) &&
			$this->ftp_client['FTP_REMOTE_ID'] == $client &&
			isset($this->ftp_client['FTP_TRANSPORT']) ) {
			$result = $this->ftp_client['FTP_TRANSPORT'];
		} else {	// Failsafe, look it up.
			$check = $this->database->get_one_row(
				"SELECT FTP_TRANSPORT FROM EXP_CARD_FTP
				WHERE FTP_REMOTE_ID = '".$client."'
				AND FTP_ENABLED = 1" );
			if( is_array($check) && isset($check['FTP_TRANSPORT']) )
				$result = $check['FTP_TRANSPORT'];
		}
		
		return $result;
	}
		
	//! Given a client ID, return the format, or return false
	public function card_format( $client ) {
		$result = false;
		// The most likely case, the client is already in $this->ftp_client
		if( is_array($this->ftp_client) &&
			isset($this->ftp_client['FTP_REMOTE_ID']) &&
			$this->ftp_client['FTP_REMOTE_ID'] == $client &&
			isset($this->ftp_client['FTP_CARD_FORMAT']) ) {
			$result = $this->ftp_client['FTP_CARD_FORMAT'];
		} else {	// Failsafe, look it up.
			$check = $this->database->get_one_row(
				"SELECT FTP_CARD_FORMAT FROM EXP_CARD_FTP
				WHERE FTP_REMOTE_ID = '".$client."'
				AND FTP_ENABLED = 1" );
			if( is_array($check) && isset($check['FTP_CARD_FORMAT']) )
				$result = $check['FTP_CARD_FORMAT'];
		}
		
		return $result;
	}
		
	//! Given a client ID, return delete after import
	public function delete_after_import( $client ) {
		$result = false;
		// The most likely case, the client is already in $this->ftp_client
		if( is_array($this->ftp_client) &&
			isset($this->ftp_client['FTP_REMOTE_ID']) &&
			$this->ftp_client['FTP_REMOTE_ID'] == $client &&
			isset($this->ftp_client['FTP_DELETE_AFTER_IMPORT']) ) {
			$result = $this->ftp_client['FTP_DELETE_AFTER_IMPORT'];
		} else {	// Failsafe, look it up.
			$check = $this->database->get_one_row(
				"SELECT FTP_DELETE_AFTER_IMPORT FROM EXP_CARD_FTP
				WHERE FTP_REMOTE_ID = '".$client."'
				AND FTP_ENABLED = 1" );
			if( is_array($check) && isset($check['FTP_DELETE_AFTER_IMPORT']) )
				$result = $check['FTP_DELETE_AFTER_IMPORT'];
		}
		
		return $result;
	}
		
	//! Get information for a client
	public function client_open( $client ) {
		if( $this->debug ) echo "<p>".__METHOD__.": Get info for client $client</p>";
		$this->log_event( __METHOD__.": Get info for client $client", EXT_ERROR_DEBUG);
		$this->last_error = '';
		$this->ftp_client = $this->database->get_one_row(
			"SELECT * FROM EXP_CARD_FTP
			WHERE FTP_REMOTE_ID = '".$client."'
			AND FTP_ENABLED = 1" );
		return $this->ftp_client;
	}

	//! Public interface for parsing fuel card data into intermediate data
    public function parse( $file ) {
	    if( $this->debug ) echo "<p>".__METHOD__.": ".(isset($file) ? strlen($file)." characters" : "").
	    	" ".(is_array($this->ftp_client) && isset($this->ftp_client['FTP_CARD_FORMAT']) ? $this->ftp_client['FTP_CARD_FORMAT']." format" : "unknown format").".</p>";
		
		if( is_array($this->ftp_client) && isset($this->ftp_client['FTP_CARD_FORMAT']) ) {
			switch( $this->ftp_client['FTP_CARD_FORMAT'] ) {
				case 'COMDATA':
					$this->comdata->parse( $file );
					break;
					
				case 'BVD':
					$this->csv->parse( $file );
					break;
					
				default:
					break;
			}
		}
	}

	//! Public interface for displaying fuel card data
    public function dump( $origin, $back ) {
	    if( $this->debug ) echo "<p>".__METHOD__.": ".(isset($file) ? strlen($file)." characters" : "").
	    	" ".(is_array($this->ftp_client) && isset($this->ftp_client['FTP_CARD_FORMAT']) ? $this->ftp_client['FTP_CARD_FORMAT']." format" : "unknown format").".</p>";
		
		if( is_array($this->ftp_client) && isset($this->ftp_client['FTP_CARD_FORMAT']) ) {
			switch( $this->ftp_client['FTP_CARD_FORMAT'] ) {
				case 'COMDATA':
					$result = $this->comdata->dump( $origin, $back );
					break;
					
				case 'BVD':
					$result = $this->csv->dump( $origin, $back );
					break;
					
				default:
					$result = '';
					break;
			}
		}
		return $result;
	}

	//! Public interface for importing fuel card data
    public function import( $origin ) {
	    $result = false;
	    if( $this->debug ) echo "<p>".__METHOD__.": ".(isset($file) ? strlen($file)." characters" : "").
	    	" ".(is_array($this->ftp_client) && isset($this->ftp_client['FTP_CARD_FORMAT']) ? $this->ftp_client['FTP_CARD_FORMAT']." format" : "unknown format").".</p>";
		
		if( is_array($this->ftp_client) && isset($this->ftp_client['FTP_CARD_FORMAT']) ) {
			switch( $this->ftp_client['FTP_CARD_FORMAT'] ) {
				case 'COMDATA':
					$result = $this->comdata->import( $origin );
					$this->import_stats = $this->comdata->get_import_stats();
					break;
					
				case 'BVD':
					$result = $this->csv->import( $origin );
					$this->import_stats = $this->csv->get_import_stats();
					break;
					
				default:
					break;
			}
		}
		return $result;
	}

	//! SCR# 554 - Add more visibility to import process
    public function get_import_stats() {
	    return $this->import_stats;
	}	
	
}

//! Form Specifications - For use with sts_form

$sts_form_addftp_form = array(	//! sts_form_addftp_form
	'title' => '<img src="images/card_ftp_icon.png" alt="card_ftp_icon" height="40"> Add Fuel Card Configuration',
	'action' => 'exp_add_card_ftp.php',
	//'actionextras' => 'disabled',
	'cancel' => 'exp_list_card_ftp.php',
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
			<div class="form-group">
				<label for="FTP_TRANSPORT" class="col-sm-5 control-label">#FTP_TRANSPORT#</label>
				<div class="col-sm-7">
					%FTP_TRANSPORT%
				</div>
			</div>
			<div class="alert alert-info">
				<p>The <strong>Transport</strong> determines how you download files.</p>
			</div>
			<div class="form-group">
				<label for="FTP_REMOTE_ID" class="col-sm-5 control-label">#FTP_REMOTE_ID#</label>
				<div class="col-sm-7">
					%FTP_REMOTE_ID%
				</div>
			</div>
			<div class="form-group show-ftp">
				<label for="FTP_OUR_ID" class="col-sm-5 control-label">#FTP_OUR_ID#</label>
				<div class="col-sm-7">
					%FTP_OUR_ID%
				</div>
			</div>
			<div class="form-group">
				<label for="FTP_CARD_FORMAT" class="col-sm-5 control-label">#FTP_CARD_FORMAT#</label>
				<div class="col-sm-7">
					%FTP_CARD_FORMAT%
				</div>
			</div>
			<div class="alert alert-info">
				<p>The <strong>Card format</strong> determines the format of downloaded files.</p>
			</div>
		</div>
		<div class="col-sm-4">
			<div class="form-group show-bvd">
				<label for="BVD_SERVER" class="col-sm-5 control-label">#BVD_SERVER#</label>
				<div class="col-sm-7">
					%BVD_SERVER%
				</div>
			</div>
			<div class="form-group show-bvd">
				<label for="BVD_API_KEY" class="col-sm-5 control-label">#BVD_API_KEY#</label>
				<div class="col-sm-7">
					%BVD_API_KEY%
				</div>
			</div>
			<div class="form-group show-bvd">
				<label for="BVD_USER_NAME" class="col-sm-5 control-label">#BVD_USER_NAME#</label>
				<div class="col-sm-7">
					%BVD_USER_NAME%
				</div>
			</div>
			<div class="form-group show-bvd">
				<label for="BVD_USER_PASS show-ftp" class="col-sm-5 control-label">#BVD_USER_PASS#</label>
				<div class="col-sm-7">
					%BVD_USER_PASS%
				</div>
			</div>

			<div class="form-group show-ftp">
				<label for="FTP_SERVER" class="col-sm-5 control-label">#FTP_SERVER#</label>
				<div class="col-sm-7">
					%FTP_SERVER%
				</div>
			</div>
			<div class="form-group show-ftp">
				<label for="FTP_USER_NAME" class="col-sm-5 control-label">#FTP_USER_NAME#</label>
				<div class="col-sm-7">
					%FTP_USER_NAME%
				</div>
			</div>
			<div class="form-group show-ftp">
				<label for="FTP_PASV" class="col-sm-5 control-label">#FTP_PASV#</label>
				<div class="col-sm-7">
					%FTP_PASV%
				</div>
			</div>
			<div class="form-group show-ftp">
				<label for="FTP_DL_PATH" class="col-sm-5 control-label">#FTP_DL_PATH#</label>
				<div class="col-sm-7">
					%FTP_DL_PATH%
				</div>
			</div>
			<div class="form-group show-ftp">
				<label for="FTP_DL_SUFFIX" class="col-sm-5 control-label">#FTP_DL_SUFFIX#</label>
				<div class="col-sm-7">
					%FTP_DL_SUFFIX%
				</div>
			</div>
			<div class="form-group show-ftp">
				<label for="FTP_DELETE_AFTER_IMPORT" class="col-sm-6 control-label">#FTP_DELETE_AFTER_IMPORT#</label>
				<div class="col-sm-6">
					%FTP_DELETE_AFTER_IMPORT%
				</div>
			</div>
		</div>
		<div class="col-sm-4">
			<div class="form-group show-ftp">
				<label for="FTP_PORT" class="col-sm-5 control-label">#FTP_PORT#</label>
				<div class="col-sm-7">
					%FTP_PORT%
				</div>
			</div>
			<div class="form-group show-ftp">
				<label for="FTP_USER_PASS" class="col-sm-5 control-label">#FTP_USER_PASS#</label>
				<div class="col-sm-7">
					%FTP_USER_PASS%
				</div>
			</div>
			<div class="alert alert-info show-ftp">
				<p><strong>Port number</strong> is usually 21, but could be 22 for secure FTP.</p>
				<p><strong>Passive Mode</strong> is if you want to connect in PASV mode. Most likely leave this off.</p>
				<p>The <strong>download path</strong> is the remote directory where files are fetched from.</p>
				<p>The <strong>file suffix</strong> is usually .txt or leave it blank for no suffix. Browse their server to confirm.</p>
				<p>The <strong>Delete after import</strong> lets you choose to delete the file after you have imported it. <strong>THIS SHOULD NORMALLY BE ENABLED TO AVOID DUPLICATE IMPORTS.</strong></p>
			</div>
		</div>
	</div>
	
	'
);

$sts_form_editftp_form = array( //! $sts_form_editftp_form
	'title' => '<img src="images/card_ftp_icon.png" alt="card_ftp_icon" height="40"> Edit Fuel Card Configuration',
	'action' => 'exp_edit_card_ftp.php',
	//'actionextras' => 'disabled',
	'cancel' => 'exp_list_card_ftp.php',
	'name' => 'editftp',
	'okbutton' => 'Save Changes',
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
			<div class="form-group">
				<label for="FTP_TRANSPORT" class="col-sm-5 control-label">#FTP_TRANSPORT#</label>
				<div class="col-sm-7">
					%FTP_TRANSPORT%
				</div>
			</div>
			<div class="alert alert-info">
				<p>The <strong>Transport</strong> determines how you download files.</p>
			</div>
			<div class="form-group">
				<label for="FTP_REMOTE_ID" class="col-sm-5 control-label">#FTP_REMOTE_ID#</label>
				<div class="col-sm-7">
					%FTP_REMOTE_ID%
				</div>
			</div>
			<div class="form-group show-ftp">
				<label for="FTP_OUR_ID" class="col-sm-5 control-label">#FTP_OUR_ID#</label>
				<div class="col-sm-7">
					%FTP_OUR_ID%
				</div>
			</div>
			<div class="form-group">
				<label for="FTP_CARD_FORMAT" class="col-sm-5 control-label">#FTP_CARD_FORMAT#</label>
				<div class="col-sm-7">
					%FTP_CARD_FORMAT%
				</div>
			</div>
			<div class="alert alert-info">
				<p>The <strong>Card format</strong> determines the format of downloaded files.</p>
			</div>
		</div>
		<div class="col-sm-4">
			<div class="form-group show-bvd">
				<label for="BVD_SERVER" class="col-sm-5 control-label">#BVD_SERVER#</label>
				<div class="col-sm-7">
					%BVD_SERVER%
				</div>
			</div>
			<div class="form-group show-bvd">
				<label for="BVD_API_KEY" class="col-sm-5 control-label">#BVD_API_KEY#</label>
				<div class="col-sm-7">
					%BVD_API_KEY%
				</div>
			</div>
			<div class="form-group show-bvd">
				<label for="BVD_USER_NAME" class="col-sm-5 control-label">#BVD_USER_NAME#</label>
				<div class="col-sm-7">
					%BVD_USER_NAME%
				</div>
			</div>
			<div class="form-group show-bvd">
				<label for="BVD_USER_PASS show-ftp" class="col-sm-5 control-label">#BVD_USER_PASS#</label>
				<div class="col-sm-7">
					%BVD_USER_PASS%
				</div>
			</div>

			<div class="form-group show-ftp">
				<label for="FTP_SERVER" class="col-sm-5 control-label">#FTP_SERVER#</label>
				<div class="col-sm-7">
					%FTP_SERVER%
				</div>
			</div>
			<div class="form-group show-ftp">
				<label for="FTP_USER_NAME" class="col-sm-5 control-label">#FTP_USER_NAME#</label>
				<div class="col-sm-7">
					%FTP_USER_NAME%
				</div>
			</div>
			<div class="form-group show-ftp">
				<label for="FTP_PASV" class="col-sm-5 control-label">#FTP_PASV#</label>
				<div class="col-sm-7">
					%FTP_PASV%
				</div>
			</div>
			<div class="form-group show-ftp">
				<label for="FTP_DL_PATH" class="col-sm-5 control-label">#FTP_DL_PATH#</label>
				<div class="col-sm-7">
					%FTP_DL_PATH%
				</div>
			</div>
			<div class="form-group show-ftp">
				<label for="FTP_DL_SUFFIX" class="col-sm-5 control-label">#FTP_DL_SUFFIX#</label>
				<div class="col-sm-7">
					%FTP_DL_SUFFIX%
				</div>
			</div>
			<div class="form-group show-ftp">
				<label for="FTP_DELETE_AFTER_IMPORT" class="col-sm-6 control-label">#FTP_DELETE_AFTER_IMPORT#</label>
				<div class="col-sm-6">
					%FTP_DELETE_AFTER_IMPORT%
				</div>
			</div>
		</div>
		<div class="col-sm-4">
			<div class="form-group show-ftp">
				<label for="FTP_PORT" class="col-sm-5 control-label">#FTP_PORT#</label>
				<div class="col-sm-7">
					%FTP_PORT%
				</div>
			</div>
			<div class="form-group show-ftp">
				<label for="FTP_USER_PASS show-ftp" class="col-sm-5 control-label">#FTP_USER_PASS#</label>
				<div class="col-sm-7">
					%FTP_USER_PASS%
				</div>
			</div>
			<div class="alert alert-info show-ftp">
				<p><strong>Port number</strong> is usually 21, but could be 22 for secure FTP.</p>
				<p><strong>Passive Mode</strong> is if you want to connect in PASV mode. Most likely leave this off.</p>
				<p>The <strong>download path</strong> is the remote directory where files are fetched from.</p>
				<p>The <strong>file suffix</strong> is usually .txt or leave it blank for no suffix. Browse their server to confirm.</p>
				<p>The <strong>Delete after import</strong> lets you choose to delete the file after you have imported it. <strong>THIS SHOULD NORMALLY BE ENABLED TO AVOID DUPLICATE IMPORTS.</strong></p>
			</div>
		</div>
	</div>
	
	'
);

//! Field Specifications - For use with sts_form

$sts_form_add_ftp_fields = array(
	'FTP_ENABLED' => array( 'label' => 'Enabled', 'align' => 'right', 'format' => 'bool2' ),
	'FTP_REMOTE_ID' => array( 'label' => 'Client ID', 'format' => 'text' ),
	'FTP_OUR_ID' => array( 'label' => 'Our ID', 'format' => 'text' ),
	'FTP_SERVER' => array( 'label' => 'FTP Server', 'format' => 'text' ),
	'BVD_SERVER' => array( 'label' => 'BVD Server', 'format' => 'enum' ),
	'BVD_API_KEY' => array( 'label' => 'API Key', 'format' => 'text' ),
	'BVD_USER_NAME' => array( 'label' => 'Username', 'format' => 'text' ),
	'BVD_USER_PASS' => array( 'label' => 'Password', 'format' => 'text' ),
	'FTP_PORT' => array( 'label' => 'Port Number', 'format' => 'number', 'align' => 'right' ),
	'FTP_PASV' => array( 'label' => 'Passive Mode', 'align' => 'right', 'format' => 'bool2' ),
	'FTP_DELETE_AFTER_IMPORT' => array( 'label' => 'Delete after import', 'align' => 'right', 'format' => 'bool2' ),
	'FTP_USER_NAME' => array( 'label' => 'Username', 'format' => 'text' ),
	'FTP_USER_PASS' => array( 'label' => 'Password', 'format' => 'text' ),
	'FTP_DL_PATH' => array( 'label' => 'Download Path', 'format' => 'text' ),
	'FTP_DL_SUFFIX' => array( 'label' => 'File Suffix', 'format' => 'text' ),
	'FTP_CARD_FORMAT' => array( 'label' => 'Card Format', 'format' => 'enum' ),
	'FTP_TRANSPORT' => array( 'label' => 'Transport', 'format' => 'enum' ),
);

$sts_form_edit_ftp_fields = array(
	'FTP_CODE' => array( 'format' => 'hidden' ),
	'FTP_ENABLED' => array( 'label' => 'Enabled', 'align' => 'right', 'format' => 'bool2' ),
	'FTP_REMOTE_ID' => array( 'label' => 'Client ID', 'format' => 'text' ),
	'FTP_OUR_ID' => array( 'label' => 'Our ID', 'format' => 'text' ),
	'FTP_SERVER' => array( 'label' => 'FTP Server', 'format' => 'text' ),
	'BVD_SERVER' => array( 'label' => 'BVD Server', 'format' => 'enum' ),
	'BVD_API_KEY' => array( 'label' => 'API Key', 'format' => 'text' ),
	'BVD_USER_NAME' => array( 'label' => 'Username', 'format' => 'text' ),
	'BVD_USER_PASS' => array( 'label' => 'Password', 'format' => 'text' ),
	'FTP_PORT' => array( 'label' => 'Port Number', 'format' => 'number', 'align' => 'right' ),
	'FTP_PASV' => array( 'label' => 'Passive Mode', 'align' => 'right', 'format' => 'bool2' ),
	'FTP_DELETE_AFTER_IMPORT' => array( 'label' => 'Delete after import', 'align' => 'right', 'format' => 'bool2' ),
	'FTP_USER_NAME' => array( 'label' => 'Username', 'format' => 'text' ),
	'FTP_USER_PASS' => array( 'label' => 'Password', 'format' => 'text' ),
	'FTP_DL_PATH' => array( 'label' => 'Download Path', 'format' => 'text' ),
	'FTP_DL_SUFFIX' => array( 'label' => 'File Suffix', 'format' => 'text' ),
	'FTP_CARD_FORMAT' => array( 'label' => 'Card Format', 'format' => 'enum' ),
	'FTP_TRANSPORT' => array( 'label' => 'Transport', 'format' => 'enum' ),
);

//! Layout Specifications - For use with sts_result

$sts_result_ftp_layout = array(
	'FTP_CODE' => array( 'format' => 'hidden' ),
	'FTP_ENABLED' => array( 'label' => 'Enabled', 'align' => 'center', 'format' => 'bool' ),
	'FTP_TRANSPORT' => array( 'label' => 'Transport', 'format' => 'text' ),
	'FTP_REMOTE_ID' => array( 'label' => 'Client ID', 'format' => 'text', 'link' => 'exp_list_card_ftp.php?DL=' ),
	'FTP_OUR_ID' => array( 'label' => 'Our ID', 'format' => 'text' ),
	'FTP_CARD_FORMAT' => array( 'label' => 'Card Format', 'format' => 'text' ),
	'FTP_SERVER' => array( 'label' => 'Server', 'format' => 'text',
		'snippet' => "CASE WHEN FTP_TRANSPORT = 'FTP' THEN FTP_SERVER
			WHEN FTP_TRANSPORT = 'BVD' THEN BVD_SERVER
			ELSE '' END" ),
	'FTP_PORT' => array( 'label' => 'Port', 'format' => 'num0nc' ),
	'FTP_PASV' => array( 'label' => 'PASV', 'align' => 'center', 'format' => 'bool' ),
	//'FTP_USER_NAME' => array( 'label' => 'Username', 'format' => 'text' ),
	//'FTP_USER_PASS' => array( 'label' => 'Password', 'format' => 'text' ),
	//'FTP_UL_PATH' => array( 'label' => 'Upload', 'format' => 'text' ),
	//'FTP_DL_PATH' => array( 'label' => 'Download', 'format' => 'text' ),
	//'FTP_DL_SUFFIX' => array( 'label' => 'Suffix', 'format' => 'text' ),

	'CHANGED_DATE' => array( 'label' => 'Changed', 'format' => 'timestamp-s' ),
	'CHANGED_BY' => array( 'label' => 'Changed By', 'format' => 'table',
		'table' => USER_TABLE, 'key' => 'USER_CODE', 'fields' => 'USERNAME', 'link' => 'exp_edituser.php?CODE=' ),

);

//! Edit/Delete Button Specifications - For use with sts_result

$sts_result_ftp_edit = array(
	'title' => '<img src="images/card_ftp_icon.png" alt="card_ftp_icon" height="40"> Fuel Card Configuration',
	'sort' => 'FTP_CODE asc',
	'cancel' => 'index.php',
	'add' => 'exp_add_card_ftp.php',
	//'actionextras' => 'disabled',
	'addbutton' => 'Add Fuel Card Configuration',
	'cancelbutton' => 'Back',
	'rowbuttons' => array(
		array( 'url' => 'exp_edit_card_ftp.php?CODE=', 'key' => 'FTP_CODE', 'label' => 'FTP_REMOTE_ID', 'tip' => 'Edit Fuel Card Configuration for ', 'icon' => 'glyphicon glyphicon-edit' ),
		array( 'url' => 'exp_dupcard_ftp.php?CODE=', 'key' => 'FTP_CODE', 'label' => 'FTP_REMOTE_ID', 'tip' => 'Duplicate Fuel Card Configuration for ', 'icon' => 'glyphicon glyphicon-plus' ),
		array( 'url' => 'exp_browse_card_ftp.php?CLIENT=', 'class' => 'browse_ftp', 'key' => 'FTP_CODE', 'label' => 'FTP_REMOTE_ID', 'tip' => 'Browse remote FTP for ', 'icon' => 'glyphicon glyphicon-search',
		'showif' => 'hasftp' ),
		array( 'url' => 'exp_list_card_ftp.php?DL=', 'key' => 'FTP_REMOTE_ID', 'label' => 'FTP_REMOTE_ID', 'tip' => 'Fuel Card Files for ', 'icon' => 'glyphicon glyphicon-saved',
		'showif' => 'hasftp' ),
		array( 'url' => 'exp_list_card_ftp.php?BVD=', 'key' => 'FTP_REMOTE_ID', 'label' => 'FTP_REMOTE_ID', 'tip' => 'Fuel Card import from ', 'icon' => 'glyphicon glyphicon-saved',
		'showif' => 'hasbvd' ),
		//array( 'url' => 'exp_list_card_ftp.php?flush=', 'key' => 'FTP_REMOTE_ID', 'label' => 'FTP_REMOTE_ID', 'tip' => 'Flush Card FTP files for ',
		//'tip2' => '<br>The configuration needs to be enabled to work. Will DELETE ALL FUEL CARD FILES from the remote server.<br><br>Is this what you really want?',
		//'icon' => 'glyphicon glyphicon-remove', 'confirm' => 'yes' ),
		array( 'url' => 'exp_delete_card_ftp.php?CODE=', 'key' => 'FTP_CODE', 'label' => 'FTP_REMOTE_ID', 'tip' => 'Delete Fuel Card Configuration for ', 'icon' => 'glyphicon glyphicon-trash', 'confirm' => 'yes' )
	)
);

?>