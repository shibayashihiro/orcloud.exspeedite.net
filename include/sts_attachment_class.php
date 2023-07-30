<?php

// $Id: sts_attachment_class.php 5167 2023-07-23 01:28:15Z dev $
// Attachment class - all things attachments
// Also see sts_form_class.php, field type attachment

// no direct access
defined('_STS_INCLUDE') or die('Restricted access');

require_once( "sts_table_class.php" );
require_once( "sts_setting_class.php" );
require_once( "sts_email_class.php" );
require_once( "sts_item_list_class.php" );
require_once( "sts_user_log_class.php" );

class sts_attachment extends sts_table {
	const WHERE_FILESANYWHERE = 'filesanywhere';
	const WHERE_LOCAL = 'local';
	private $setting_table;
	private $item_list_table;
	private $where;
	private $files_where;
	private $files_local;
	private $files_anywhere = false;
	private $invoice_attachment_types;
	private $manifest_attachment_types;
	private $name_length = 13;
	public $error;
	private $file_access_timeout = 10;
	private $max_size;
	private $compress_pdf;
	private $gs_path;
	private $gs_settings;
	private $item_logo = -1;
	private $file_errors = array(1 => 'php.ini max file size exceeded', 
        	    2 => 'html form max file size exceeded', 
            	3 => 'file upload was only partial', 
            	4 => 'no file was attached',
            	6 => 'Missing a temporary folder',
			    7 => 'Failed to write file to disk.',
			    8 => 'A PHP extension stopped the file upload.');
	private $user_log;	// SCR# 792 - log events

	private $log_file;
	private $debug_diag_level;
	private $diag_level;

	// Constructor does not need the table name
	public function __construct( $database, $debug = false ) {
		global $sts_result_image_layout, $sts_result_image_edit, $sts_error_level_label,
			$_SESSION;

		$this->debug = $debug;
		$this->primary_key = "ATTACHMENT_CODE";
		if( $this->debug ) echo "<p>Create sts_attachment</p>";
		$this->setting_table = sts_setting::getInstance($database, $debug);
		$this->invoice_attachment_types =
			$this->setting_table->get( 'email', 'EMAIL_INVOICE_ATTACHMENTS' );
		$this->manifest_attachment_types =
			$this->setting_table->get( 'email', 'EMAIL_MANIFEST_ATTACHMENTS' );
		
		$this->log_file = $this->setting_table->get( 'option', 'ATTACHMENT_LOG_FILE' );
		$this->debug_diag_level = $this->setting_table->get( 'option', 'ATTACHMENT_DIAG_LEVEL' );
		$this->diag_level =  array_search(strtolower($this->debug_diag_level), $sts_error_level_label);
		if( $this->diag_level === false ) $this->diag_level = EXT_ERROR_ALL;		

		//! SCR# 644 - this selects either local or filesanywhere
		$this->where =
			$this->setting_table->get( 'option', 'ATTACHMENTS_WHERE' );
		
		$this->files_local = sts_files_local::getInstance($database, $debug, $this);
			
	    if( $this->where == self::WHERE_FILESANYWHERE) {
			$this->try_filesanywhere();	
		} else {
			$this->files_anywhere = false;
		}
		
		$this->files_where = array(
			self::WHERE_LOCAL => $this->files_local,
			self::WHERE_FILESANYWHERE => $this->files_anywhere
		);

		//! SCR# 459 - new settings
		$this->max_size =
			$this->setting_table->get( 'option', 'ATTACHMENTS_MAX_SIZE' );
		$this->compress_pdf =
			$this->setting_table->get( 'option', 'ATTACHMENTS_COMPRESS_PDF' ) == 'true';
		$this->gs_path =
			$this->setting_table->get( 'option', 'GHOSTSCRIPT_PATH' );
		$this->gs_settings =
			$this->setting_table->get( 'option', 'GHOSTSCRIPT_SETTINGS' );
			
		if( ! is_array($_SESSION) || ! isset($_SESSION['ATTACHMENT_ITEM_LOGO']) ) {
			$check = $database->get_one_row("SELECT ITEM_CODE FROM EXP_ITEM_LIST
				WHERE ITEM_TYPE = 'Attachment type' AND ITEM = 'Logo'");
			
			if( is_array($check) && isset($check["ITEM_CODE"]) )
				$_SESSION['ATTACHMENT_ITEM_LOGO'] = $this->item_logo = $check["ITEM_CODE"];
		} else {
			$this->item_logo = $_SESSION['ATTACHMENT_ITEM_LOGO'];
		}

		$this->user_log = sts_user_log::getInstance($database, $debug);

		parent::__construct( $database, ATTACHMENT_TABLE, $debug);
		$this->load_defaults();
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
    
    private function try_filesanywhere( $fallback = true ) {
		if( $this->debug ) echo "<p>".__METHOD__.": fallback = ".($fallback ? 'true' : 'false')."</p>";

		$this->files_anywhere = new sts_files_anywhere($this->database, $this->debug, $this);
		
		if( ! $this->files_anywhere->active() && $fallback ) {
			$this->log_event(__METHOD__.': filesanywhere NOT Active - fall back to local');
			if( $debug ) {
				echo '<p>'.__METHOD__.': filesanywhere NOT Active - fall back to local</p>';
				ob_flush(); flush();
			}
			$email = sts_email::getInstance($this->database, $this->debug);
			$email->send_alert(__METHOD__.": filesanywhere NOT Active - fall back to local<br>
			<br>
			You will have to reset option/ATTACHMENTS_WHERE<br>
			and migrate the attachments over, once the issue is resolved." );
			$this->setting_table->set( 'option', 'ATTACHMENTS_WHERE', self::WHERE_LOCAL );
			$this->files_anywhere = false;
			$this->where == self::WHERE_LOCAL;
		} else {
			$this->log_event(__METHOD__.': filesanywhere Active');
			if( $this->debug ) {
				echo '<p>'.__METHOD__.': filesanywhere Active</p>';
				ob_flush(); flush();
			}
		}
    }
    
    private function retry_filesanywhere() {
		if( $this->debug ) echo "<p>".__METHOD__.": try and reconnect</p>";
		$this->try_filesanywhere( false );
		
		if( is_object($this->files_anywhere) && $this->files_anywhere->active() ) {
			if( $this->debug ) echo "<p>".__METHOD__.": RESTORE filesanywhere</p>";
			$email = sts_email::getInstance($this->database, $this->debug);
			$email->send_alert(__METHOD__.": filesanywhere ACTIVE again - switched over<br>" );

			$this->setting_table->set( 'option', 'ATTACHMENTS_WHERE', self::WHERE_FILESANYWHERE );
			$this->where = self::WHERE_FILESANYWHERE;
			$this->files_where[self::WHERE_FILESANYWHERE] = $this->files_anywhere;
		}

    }
    
    //! Load default items into table.
    // Check once per session.
    private function load_defaults() {
	    global $_SESSION;
	    
		if( $this->debug ) echo "<p>".__METHOD__.": session v = ".(isset($_SESSION["ATTACHMENT_TYPES_LOADED"]) ? 'set' : 'unset')."</p>";
	    if( ! ( is_array($_SESSION) && isset($_SESSION["ATTACHMENT_TYPES_LOADED"])) ) {
		    $_SESSION["ATTACHMENT_TYPES_LOADED"] = true;

		    $this->item_list_table = sts_item_list::getInstance( $this->database, $this->debug );
			$this->item_list_table->set_attachment_types();
		}
	}
	
	public function log_event( $event, $level = EXT_ERROR_ERROR ) {		
		if( $this->diag_level >= $level ) {
			if( isset($this->log_file) && $this->log_file <> '' && ! is_dir($this->log_file) ) {
				if(is_writable(dirname($this->log_file)) ) {
					file_put_contents($this->log_file, date('m/d/Y h:i:s A')." ".$event.PHP_EOL,
						(file_exists($this->log_file) ? FILE_APPEND : 0) );
				} else {
					if( $this->debug ) echo "<p>log_event: ".dirname($this->log_file)." not writeable</p>";
				}
			}
		}
	}
	
	public function where( $w ) {
		return $this->files_where[$w];
	}
	
	public static function mime_type( $path ) {
		$ext = strtolower( pathinfo($path, PATHINFO_EXTENSION) );
		switch( $ext ) {
			case 'pdf':
				$result = 'application/pdf';
				break;

			case 'jpg':
			case 'jpeg':
				$result = 'image/jpg';
				break;

			case 'msg':
				$result = 'application/vnd.ms-outlook';
				break;

			case 'tif':
			case 'tiff':
				$result = 'image/tiff';
				break;

			case 'png':
				$result = 'image/png';
				break;

			case 'docx':
				$result = 'application/vnd.openxmlformats-officedocument.wordprocessingml.document';
				break;

			case 'doc':
			case 'dot':
				$result = 'application/msword';
				break;

			case 'xlsx':
				$result = 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet';
				break;

			case 'xls':
			case 'xlt':
				$result = 'application/vnd.ms-excel';
				break;
				
			case 'rtf':
				$result = 'application/rtf';
				break;
				
			case 'pptx':
				$result = 'application/vnd.openxmlformats-officedocument.presentationml.presentation';
				break;

			case 'pps':
			case 'ppt':
				$result = 'application/vnd.ms-powerpoint';
				break;
				
			case 'rar':
				$result = 'application/vnd.rar';
				break;
				
			case 'htm':
			case 'html':
				$result = 'text/html';
				break;
				
			case 'ics':
				$result = 'text/calendar';
				break;
				
			case 'vcf':
			case 'vcard':
				$result = 'text/vcard';
				break;
				
			case 'gif':
				$result = 'image/gif';
				break;
				
			case 'txt':
				$result = 'text/plain';
				break;

			default:
				$result = 'application/octet-stream';
				break;
		}
		return $result;
	}

	//! SCR# 599 - allow for hierarchy
	//! SCR# 644 - moved to subordinate classes
	public function stored_as( $ext = 'jpg', $where = false ) {
		if( ! $where )
			$where = $this->where;
		return $this->files_where[$where] ? $this->files_where[$where]->stored_as( $ext ) : false;
	}
	
	//! SCR# 459 - move functionality from form class to here
	// Process upload attachment
	public function store_attachment( $name, &$post ) {
		global $_FILES;
		
		if( $this->debug ) {
			echo "<p>".__METHOD__.": ZZZ name, FILES, CONTENT_LENGTH = </p>
			<pre>";
			var_dump($name, $_FILES, $_SERVER['CONTENT_LENGTH'], ini_get('post_max_size'), ini_get('upload_max_filesize') );
			echo "</pre>";
		}

		$this->error = '';
		$value = "";
		if( isset($_FILES[$name]) && isset($_FILES[$name]['name']) && $_FILES[$name]['name'] <> '' ) {
			if( $_FILES[$name]['error'] != 0 ) { // upload error
				$this->error .= $this->file_errors[$_FILES[$name]['error']];
				if( $this->debug ) {
					echo "<p>".__METHOD__.": error = </p>
					<pre>";
					var_dump($this->error);
					echo "</pre>";
				}
			} else {
				if( ! is_uploaded_file($_FILES[$name]['tmp_name']) ) {
					$this->error .= "not an HTTP upload";
				} else {
					if( $_FILES[$name]['size'] > 0 ) {
						if( $_FILES[$name]['size'] > $this->max_size ) {
							$this->error .= "Attachment file is too big to load";
						} else {
							$ext = pathinfo($_FILES[$name]['name'], PATHINFO_EXTENSION);
							
							$post['STORED_WHERE'] = $this->where; //! SCR# 644
							//! SCR# 644 - company logo stays local
							if( $post['SOURCE_TYPE'] == 'company' &&
								$post['FILE_TYPE'] == $this->item_logo )
								$post['STORED_WHERE'] = self::WHERE_LOCAL;
							
							$post['STORED_AS'] = $this->stored_as( $ext, $post['STORED_WHERE'] );
							
							if( ! empty($post['STORED_AS']) ) {
								$post['STORED_AS'] .= '.'.$ext;
								$result = $this->files_where[$post['STORED_WHERE']]->put( $_FILES[$name]['tmp_name'], $post['STORED_AS'] );
								if( ! $result ) {
									$this->log_event('Unable to Store Attachment,');
									$error = '<h2>Unable to Store Attachment</h2>
		<p>Error: file attachment (put to '.$post['STORED_WHERE'].') failed.</p>
		<p>Please have Exspeedite support investigate, and give them this message.</p>';
									$email = sts_email::getInstance($this->database, $this->debug);
									$email->send_alert($error);
									echo $error;
									die;
								}

								$value = $_FILES[$name]['name'];
								$post['SIZE'] = $_FILES[$name]['size'];
								$post['IS_COMPRESSED'] = 'FALSE';
								
								//! SCR# 792 - log event
								$this->user_log->log_event( 'attachment', 'ADD: '.
								$post['SOURCE_TYPE'].'# '.$post['SOURCE_CODE'].' -> '. $value.
								' stored in '.$post['STORED_WHERE'] );
								
								$this->log_event(__METHOD__.': ADD: '.(empty($_SESSION['EXT_USERNAME']) ? '' : $_SESSION['EXT_USERNAME']).' '.
								$post['SOURCE_TYPE'].'# '.$post['SOURCE_CODE'].' -> '. $value.
								' stored in '.$post['STORED_WHERE'], EXT_ERROR_DEBUG );

							} else {
								$error = '<h2>Attachments Directory Missing or Un-Writeable</h2>
	<p>The directory '.$this->files_where[$this->where]->get_attachment_dir().' is either missing or un-writeable.</p>
	<p>Please have Exspeedite support investigate, and give them this message.</p>';
								$email = sts_email::getInstance($this->database, $this->debug);
								$email->send_alert($error);
								echo $error;
								die;
							}
						}
						
					} else {
						$this->error .= "empty file";
					}
				}
			}
		} else {
			$this->error .= "no file";
		}
		if( $this->debug ) echo "<p>".__METHOD__.": value = $value</p>";

		return $value;
	}
	
	public function add_attachment( $source_code, $source_type, $name, $file_type ) {
		global $_FILES;
		
		if( $this->debug ) echo "<p>".__METHOD__.": $source_code, $source_type, $name, $file_type</p>";
		$this->log_event(__METHOD__.": ".(empty($_SESSION['EXT_USERNAME']) ? '' : $_SESSION['EXT_USERNAME'])." $source_code, $source_type, $name, $file_type", EXT_ERROR_DEBUG );

		$ext = pathinfo($_FILES[$name]['name'], PATHINFO_EXTENSION);
				
		$result = false;
		$p = [	'SOURCE_CODE' => $source_code,
				'SOURCE_TYPE' =>  $source_type,
				'STORED_AS' => $this->stored_as( $ext ),
				'FILE_NAME' => $_FILES[$name]['name'],
				'FILE_TYPE' => $file_type ];
			
		$result1 = $this->store_attachment( $name, $p );
		
		if( $this->debug ) {
			echo "<pre>xxx\n";
			var_dump($result1);
			var_dump($p);
			echo "</pre>";
		}
		
		if( ! empty($result1) )
			$result = $this->add( $p );
			
		//! SCR# 459 - compress PDF attachment
		if( $result )
			$this->compress( $result );
		
		return $result;

	}
	
	public function restore_attachment( $row, $data ) {
		$this->error = '';
		$exists = false;
		if( $this->debug ) {
			echo "<pre>".__METHOD__.": row, data\n";
			var_dump($row, $data);
			echo "</pre>";
		}

		if( is_array($row) &&
			isset($row["ATTACHMENT_CODE"]) && isset($row["SOURCE_CODE"]) &&
			isset($row["SOURCE_TYPE"]) && isset($row["FILE_NAME"]) &&
			isset($row["FILE_TYPE"]) && isset($row["STORED_AS"]) &&
			! empty($data)) {
			if( $this->debug ) echo "<p>".__METHOD__.": required fields exist</p>";
			$result = true;
			$code = $row["ATTACHMENT_CODE"];
	    	$check = $this->fetch_rows( $this->primary_key." = ".$code );
			if( is_array($check) && count($check) == 1 ) { // Row exists already
				$exists = true;
				if( $this->debug ) echo "<p>".__METHOD__.": row exists</p>";
				if( ! $this->array_match($row, $check) ) {
			    	// Problem, different rows
			    	$result = false;
			    	$this->error = 'row '.$code.' exists with different data';
		    	}
		    }
		} else {
			// Problem, missing key info
			$result = false;
			$this->error = 'missing key info';
		}

		if( $result ) {
			$path = tempnam(sys_get_temp_dir(), 'tmp');	// temp file
			$result = file_put_contents($path, $data);
			if( $this->debug ) echo "<p>".__METHOD__.": after file_put_contents $path ".($result ? "true" : "false").(empty($this->error) ? "" : " error = ".$this->error)."</p>";
			
			if( $result ) {
				$where = $this->where;
				if( isset($row["STORED_WHERE"]) )
					$where = $row["STORED_WHERE"];

				if( $this->debug ) {
					echo "<pre>".__METHOD__.": where, source, destination\n";
					var_dump($where, $path, $row['STORED_AS'] );
					echo "</pre>";
				}

				if( ! $this->files_where[$where]->file_exists( $row['STORED_AS'] ) )
					$result = $this->files_where[$where]->put( $path, $row['STORED_AS'] );
				
				if( $result && ! $exists ) {
					$result = $this->add( $row );
					if( $this->debug ) echo "<p>".__METHOD__.": after add ".($result ? "true" : "false").(empty($this->error) ? "" : " error = ".$this->error)."</p>";
					if( ! $result ) {
						$this->error = 'add failed for '.$code;
					}
				} else {
					$this->error = $where.' failed to put to '.$path.' error = '.
						$this->files_where[$where]->error;
				}
				
				if( $result ) {
					unlink($path);	// remove temp file
				}
			} else {
				$this->error = 'file_put_contents failed to write to temp file '.$path;
			}
		}
		
		if( $this->debug ) echo "<p>".__METHOD__.": return ".($result ? "true" : "false").(empty($this->error) ? "" : " error = ".$this->error)."</p>";
		return $result;
	}
	
	public function delete_attachment( $code ) {
		if( $this->debug ) echo "<p>".__METHOD__.": code = $code</p>";
		$this->error = '';
		$result = false;
		
		$check = $this->fetch_rows( $this->primary_key." = ".$code );
		
		if( is_array($check) && count($check) == 1) {
			$file_name = $check[0]['FILE_NAME'];
			$stored_as = str_replace('/', '\\', $check[0]['STORED_AS']);
			$where = $check[0]['STORED_WHERE'];
			$source_type = $check[0]['SOURCE_TYPE'];
			$source_code = $check[0]['SOURCE_CODE'];

			if( $this->files_where[$where] != false &&
				$this->files_where[$where]->file_exists($stored_as)) {
				$result = $this->files_where[$where]->unlink($stored_as);
			}
			
			if( $result ) {
				$result = $this->delete( $code );
			}
			
			$this->user_log->log_event( 'attachment',	'DELETE: attachment# '.$code.' '.
				$source_type.'# '.$source_code.' -> '. $file_name.
				' result: '.($result ? "true" : "false") );
		}
		
		if( $this->debug ) echo "<p>".__METHOD__.": return ".($result ? "true" : "false").(empty($this->error) ? "" : " error = ".$this->error)."</p>";
		
		$this->log_event(__METHOD__.": ".(empty($_SESSION['EXT_USERNAME']) ? '' : $_SESSION['EXT_USERNAME'])." $file_name return ".($result ? "true" : "false").(empty($this->error) ? "" : " error = ".$this->error), EXT_ERROR_DEBUG );
		
		return $result;
	}
	
	//! SCR# 459 - compress a PDF attachment, referenced by the ATTACHMENT_CODE
	// Returns # bytes saved
	public function compress( $code ) {
		if( $this->debug ) echo "<p>".__METHOD__.": entry, code = $code, ".
			($this->compress_pdf ? 'enabled, '.
				(file_exists($this->gs_path) &&
				is_executable($this->gs_path) ? 'executable' : 'no executable '.$this->gs_path )
			: 'disabled')."</p>";
		
		$saving = 0;
		if( $this->compress_pdf &&					// If enabled
			file_exists($this->gs_path) &&			// and we have an executable
			is_executable($this->gs_path) ) {		// which we can execute
			
			// Check it is a PDF
			$row = $this->fetch_rows("ATTACHMENT_CODE = ".$code);
			
			if( is_array($row) &&
				count($row) == 1 &&
				! empty($row[0]["STORED_AS"]) &&		// Where file stored
				file_exists($row[0]["STORED_AS"]) &&	// it exists
				isset($row[0]["IS_COMPRESSED"]) &&
				intval($row[0]["IS_COMPRESSED"]) == 0 &&	// Not already compressed
				isset($row[0]["STORED_WHERE"]) &&
				$row[0]["STORED_WHERE"] == self::WHERE_LOCAL &&	// SCR# 644 - has to be local
				! empty($row[0]["FILE_NAME"])) {		// Original file name
				
				$ext = pathinfo($row[0]["FILE_NAME"], PATHINFO_EXTENSION);
				
				if( $this->debug ) echo "<p>".__METHOD__.": name = ".$row[0]["FILE_NAME"]."</p>";

				if( strtolower($ext) == 'pdf' ) {	// We have a PDF
					
					$old_name = $row[0]["STORED_AS"];
					$old_size = filesize($old_name);		// can't trust $row[0]["SIZE"]
					if( $this->debug ) echo "<p>".__METHOD__.": We have a PDF to compress, size = ".sts_result::formatBytes($old_size)."</p>";
					
					$new_name = $this->stored_as( $ext );
					$new_name .= '.'.$ext;
										
					$str_exec = '"'.$this->gs_path.'" '.
						$this->gs_settings.
						' -sOutputFile='.$new_name.' '.$old_name;
					if( $this->debug ) echo "<p>".__METHOD__.": str_exec = $str_exec</p>";
					
					$output = exec( $str_exec );
					if( $this->debug ) echo "<p>".__METHOD__.": exec = ".$output."</p>";
					
					if( file_exists($new_name) ) {
						$new_size = filesize($new_name);
						if( $this->debug ) echo "<p>".__METHOD__.": $new_name exists, size = ".sts_result::formatBytes($new_size)."</p>";
						if( $new_size < $old_size ) {
							$result = $this->update("ATTACHMENT_CODE = ".$code,
								[ 'STORED_AS' => $new_name,
								'SIZE' => $new_size,
								'IS_COMPRESSED' => TRUE ]);
							if( $result ) unlink($old_name);
							$saving = $old_size - $new_size;
						} else {
							$result = $this->update("ATTACHMENT_CODE = ".$code,
								[ 'SIZE' => $old_size,
								'IS_COMPRESSED' => TRUE ]);
							unlink($new_name);
						}
					} else {
						if( $this->debug ) echo "<p>".__METHOD__.": $new_name not found</p>";
					}
				}
			} else if( is_array($row) &&
				count($row) == 1 &&
				! empty($row[0]["STORED_AS"]) &&		// Where file stored
				file_exists($row[0]["STORED_AS"]) &&
				(empty($row[0]["SIZE"]) || $row[0]["SIZE"] == 0) ) {				// Size missing

				$old_size = filesize($row[0]["STORED_AS"]);		// Just update the size
				$result = $this->update("ATTACHMENT_CODE = ".$code,
					[ 'SIZE' => $old_size ]);
			}
		}
		return $saving;
	}
	
	public function uncompressed_pdf( $limit = 10 ) {
		$num = $this->fetch_rows("FILE_NAME LIKE '%.pdf' AND STORED_WHERE = 'local' AND NOT IS_COMPRESSED", "COUNT(*) NUM");
		$total_possibles = is_array($num) && count($num) == 1 && isset($num[0]["NUM"]) ?
			intval($num[0]["NUM"]) : 0;
		
		$candidates = [];
		if( $limit > 0 ) {	
			$subset = $this->fetch_rows("FILE_NAME LIKE '%.pdf' AND STORED_WHERE = 'local' AND NOT IS_COMPRESSED",
				"ATTACHMENT_CODE", "", $limit);
			
			if( is_array($subset) && count($subset) > 0 ) {
				foreach( $subset as $row ) {
					$candidates[] = intval($row["ATTACHMENT_CODE"]);
				}
			}
		}
		
		return [ $total_possibles, $candidates ];	
		
	}

	//! SCR# 644 - find attachments in local storage
	public function unmoved_attachment( $limit = 10 ) {
		$num = $this->fetch_rows("STORED_WHERE = 'local' AND NOT
			(SOURCE_TYPE = 'company' AND
			FILE_TYPE = (SELECT ITEM_CODE FROM EXP_ITEM_LIST
			WHERE ITEM_TYPE = 'Attachment type' AND ITEM = 'Logo'))", "COUNT(*) NUM");
		$total_possibles = is_array($num) && count($num) == 1 && isset($num[0]["NUM"]) ?
			intval($num[0]["NUM"]) : 0;
		
		$candidates = [];
		if( $limit > 0 ) {	
			$subset = $this->fetch_rows("STORED_WHERE = 'local' AND NOT
			(SOURCE_TYPE = 'company' AND
			FILE_TYPE = (SELECT ITEM_CODE FROM EXP_ITEM_LIST
			WHERE ITEM_TYPE = 'Attachment type' AND ITEM = 'Logo'))",
				"ATTACHMENT_CODE", "", $limit);
			
			if( is_array($subset) && count($subset) > 0 ) {
				foreach( $subset as $row ) {
					$candidates[] = intval($row["ATTACHMENT_CODE"]);
				}
			}
		}
		
		return [ $total_possibles, $candidates ];	
		
	}


	//! Find attachment files that are not in the DB
	public function orphans() {
		$found = array();
		$check = $this->fetch_rows("", "STORED_AS");
		if( is_array($check) && count($check) > 0 ) {
			$in_db = array();
			foreach( $check as $row ) {
				$in_db[$row["STORED_AS"]] = 1;
			}
			$files = scandir($this->files_local->get_attachment_dir());

			foreach($files as $t) {
				$full = rtrim($this->files_local->get_attachment_dir(), '/') . '/' . $t;
				if ($t<>"." && $t<>".." && ! is_dir($full)) {
					if( ! isset($in_db[$full]) )
						$found[] = array($full, filesize($full));
				}
			}
		}
		
		return $found;
	}

	//! SCR# 602 - base64 encode the image and return it in HTML wrapper
	public function render_inline( $code ) {
		$output = '';
		
		$check = $this->fetch_rows( $this->primary_key." = ".$code,
			"*, (SELECT ITEM FROM EXP_ITEM_LIST
					WHERE ITEM_TYPE = 'Attachment type'
					AND ITEM_CODE = FILE_TYPE) AS ITEM");
		
		if( is_array($check) && count($check) == 1) {
			$file_name = $check[0]['FILE_NAME'];
			$stored_as = str_replace('/', '\\', $check[0]['STORED_AS']);
			$where = $check[0]['STORED_WHERE'];
			
			$this->log_event(__METHOD__.": ".(empty($_SESSION['EXT_USERNAME']) ? '' : $_SESSION['EXT_USERNAME'])." $file_name $stored_as $where", EXT_ERROR_DEBUG );

			
			if( ! $this->files_where[$where]->file_exists($stored_as)) {
				$this->log_event(__METHOD__.': '.(empty($_SESSION['EXT_USERNAME']) ? '' : $_SESSION['EXT_USERNAME']).' The file '.$file_name.' stored in '.$where.', '.$stored_as.' was not found.'.PHP_EOL.'Attachment code = '.$check[0]['ATTACHMENT_CODE'], EXT_ERROR_ERROR); 
				$error = '<h2>Stored Attachment Missing</h2>
				<p>The file '.$file_name.' stored in '.$where.', '.$stored_as.' was not found.<br>
				Attachment code = '.$check[0]['ATTACHMENT_CODE'].'<br>
				Please contact Exspeedite support, and copy and paste this message.</p>';
				$email = sts_email::getInstance($this->database, $this->error);
				$email->send_alert($error);
				echo $error;
				die;
			}
			
			$type = $this->mime_type( $stored_as );
			if (strpos($type, 'image') !== false || strpos($type, 'pdf') !== false) {
				$data = $this->files_where[$where]->fetch($stored_as);
				$base64 = 'data:image/' . $type . ';base64,' . base64_encode($data);
				$output = '<p>Attached Image: ('.$check[0]['ITEM'].') <strong>'.$file_name.'</strong><br><img src="'.$base64.'" alt="'.$file_name.'"></p>';
			}
		}
		
		return $output;

	}
	
	public function attachment_exists( $code ) {
		if( $this->debug ) echo "<p>".__METHOD__.": code = $code</p>";
		$result = false;
		$check = $this->fetch_rows( $this->primary_key." = ".$code );
		
		if( is_array($check) && count($check) == 1) {
			$file_name = $check[0]['FILE_NAME'];
			$stored_as = str_replace('/', '\\', $check[0]['STORED_AS']);
			$where = $check[0]['STORED_WHERE'];
			
			$result = $this->files_where[$where]->file_exists($stored_as);
			
			$this->log_event(__METHOD__.": $file_name $stored_as $where ".
				($result ? 'exists' : 'MISSING'), EXT_ERROR_DEBUG );
		}
		
		return $result;		
	}
	
	public function origin( $code ) {		
		$check = $this->fetch_rows( $this->primary_key." = ".$code, "SOURCE_TYPE, SOURCE_CODE, FILE_NAME, STORED_AS" );
		if( is_array($check) && count($check) == 1) {
			$this->log_event(__METHOD__.": type = ".$check[0]['SOURCE_TYPE'].
				" code = ".$check[0]['SOURCE_CODE'].
				" file = ".$check[0]['FILE_NAME'].
				" stored_as = ".$check[0]['STORED_AS']
				, EXT_ERROR_DEBUG );

			return $check[0];
		} else {
			return false;
		}
	}
		
	public function view( $code ) {		
		$check = $this->fetch_rows( $this->primary_key." = ".$code );
		
		if( is_array($check) && count($check) == 1) {
			$file_name = $check[0]['FILE_NAME'];
			$stored_as = str_replace('/', '\\', $check[0]['STORED_AS']);
			$where = $check[0]['STORED_WHERE'];
			
			$this->log_event(__METHOD__.": ".(empty($_SESSION['EXT_USERNAME']) ? '' : $_SESSION['EXT_USERNAME'])." $file_name $stored_as $where", EXT_ERROR_DEBUG );
			
			//! filesanywhere is inactive, but file is there - retry
			if( $where == self::WHERE_FILESANYWHERE && ! is_object($this->files_anywhere) ) {
				$this->retry_filesanywhere();
			}
			
			if( ! $this->files_where[$where] || ! $this->files_where[$where]->file_exists($stored_as)) {
				$this->log_event(__METHOD__.': The file '.$file_name.' stored in '.$where.', '.$stored_as.' was not found.'.PHP_EOL.'Attachment code = '.$check[0]['ATTACHMENT_CODE'], EXT_ERROR_ERROR); 
				$error = '<h2>Stored Attachment Missing</h2>
				<p>The file '.$file_name.' stored in '.$where.', '.$stored_as.' was not found.<br>
				Attachment code = '.$check[0]['ATTACHMENT_CODE'].'<br>
				<br>
				This is a temporary issue, and we are working on it ('.date('m/d/Y').').
				<br>				
				<br>				
				Please contact Exspeedite support, and copy and paste this message.</p>';
				$email = sts_email::getInstance($this->database, $this->error);
				$email->send_alert($error);
				echo $error;
				die;
			}
			
			$type = $this->mime_type( $stored_as );
			if (strpos($type, 'image') !== false || strpos($type, 'pdf') !== false) {
				$disp = 'inline';
			} else {
				$disp = 'attachment';
			}
			
			if( $this->debug ) {
				echo "<pre>";
				var_dump('Content-Type: '.$type );
				var_dump('Content-Length: ' . $this->files_where[$where]->file_size($stored_as));
				var_dump('Content-Disposition: '.$disp.'; filename="'.$file_name.'"');
				var_dump($stored_as);
				echo "</pre>";
			} else {
				ob_clean();
				header('Content-Type: '.$type);
				header('Content-Length: ' . $this->files_where[$where]->file_size($stored_as));
				header('Content-Disposition: '.$disp.'; filename="'.$file_name.'"');
				echo $this->files_where[$where]->fetch( $stored_as );
			}
		}
	}
	
	//! SCR# 208 - get a list of attchments for a shipment
	// that match the types in the setting email/EMAIL_INVOICE_ATTACHMENTS
	public function invoice_attachments( $shipment ) {
		if( $this->debug ) echo "<p>".__METHOD__.": shipment = $shipment</p>";
		$result = false;
		if( ! empty($this->invoice_attachment_types) ) {
			$types = explode(',', $this->invoice_attachment_types);
			$files = $this->fetch_rows("SOURCE_CODE = $shipment
				AND SOURCE_TYPE = 'shipment'
				AND FILE_TYPE IN (SELECT ITEM_CODE FROM EXP_ITEM_LIST
					WHERE ITEM_TYPE = 'Attachment type'
					AND ITEM IN ('".implode("','", $types)."'))", "ATTACHMENT_CODE, FILE_NAME, STORED_AS");
			
			if( is_array($files) && count($files) > 0 ) {
				$result = array();
				foreach($files as $f) {
					$result[] = $f;
				}
			}
		}
		
		$acodes = [];
		if( is_array($result) && count($result) > 0 ) {
			foreach($result as $row) {
				$acodes[] = $row['ATTACHMENT_CODE'];
			}
		}
		if( $this->debug ) {
			echo "<pre>".__METHOD__.": result\n";
			var_dump($result);
			echo "</pre>";
		}
		
		$this->log_event(__METHOD__.": ".(empty($_SESSION['EXT_USERNAME']) ? '' : $_SESSION['EXT_USERNAME'])." shipment = $shipment, attachments = ".(is_array($acodes) && count($acodes) > 0 ?
				implode(', ', $acodes) : 'NONE'), EXT_ERROR_DEBUG );
		
		return $result;
	}
	
	//! SCR# 388 - only attachments of types that match email/EMAIL_MANIFEST_ATTACHMENTS will be sent
	public function manifest_attachments( $load ) {
		$result = false;
		if( ! empty($this->manifest_attachment_types) ) {
			$manifest_types = explode(',', $this->manifest_attachment_types);

			$manifest_files = $this->fetch_rows("SOURCE_CODE = $load
				AND SOURCE_TYPE = 'load'
				AND FILE_TYPE IN (SELECT ITEM_CODE FROM EXP_ITEM_LIST
					WHERE ITEM_TYPE = 'Attachment type'
					AND ITEM IN ('".implode("','", $manifest_types)."'))", "ATTACHMENT_CODE, FILE_NAME, STORED_AS");
			
			$invoice_files = $this->fetch_rows("ATTACHMENT_CODE IN (SELECT A.ATTACHMENT_CODE
				FROM EXP_SHIPMENT S, EXP_ATTACHMENT A
				WHERE S.LOAD_CODE = $load
				AND A.SOURCE_CODE = S.SHIPMENT_CODE AND SOURCE_TYPE = 'shipment')
				AND FILE_TYPE IN (SELECT ITEM_CODE FROM EXP_ITEM_LIST
					WHERE ITEM_TYPE = 'Attachment type'
					AND ITEM IN ('".implode("','", $manifest_types)."'))", "ATTACHMENT_CODE, FILE_NAME, STORED_AS");
			
			if( is_array($manifest_files) && count($manifest_files) > 0 )
				$files = $manifest_files;
			else
				$files = array();
			if( is_array($invoice_files) && count($invoice_files) > 0 )
				$files = array_merge($files, $invoice_files);
			
			if( is_array($files) && count($files) > 0 ) {
				$result = array();
				foreach($files as $f) {
					$result[] = $f;
				}
			}
		}
		
		$acodes = [];
		if( is_array($result) && count($result) > 0 ) {
			foreach($result as $row) {
				$acodes[] = $row['ATTACHMENT_CODE'];
			}
		}
		if( $this->debug ) {
			echo "<pre>manifest_attachments: result\n";
			var_dump($result);
			echo "</pre>";
		}
		
		$this->log_event(__METHOD__.": ".(empty($_SESSION['EXT_USERNAME']) ? '' : $_SESSION['EXT_USERNAME'])." load = $load".PHP_EOL.
			"Attachments = ".(is_array($acodes) && count($acodes) > 0 ?
				implode(', ', $acodes) : 'NONE'), EXT_ERROR_DEBUG );

		return $result;
	}
	
	public function report_attachments( $report ) {
		$result = false;
		$files = $this->fetch_rows("SOURCE_CODE = $report
			AND SOURCE_TYPE = 'insp_report'", "ATTACHMENT_CODE, FILE_NAME, STORED_AS");
		
		if( is_array($files) && count($files) > 0 ) {
			$result = array();
			foreach($files as $f) {
				$result[] = $f;
			}
		}
		
		$acodes = [];
		if( is_array($result) && count($result) > 0 ) {
			foreach($result as $row) {
				$acodes[] = $row['ATTACHMENT_CODE'];
			}
		}

		$this->log_event(__METHOD__.": ".(empty($_SESSION['EXT_USERNAME']) ? '' : $_SESSION['EXT_USERNAME'])." report = $report".PHP_EOL.
			"Attachments = ".(is_array($acodes) && count($acodes) > 0 ?
				implode(', ', $acodes) : 'NONE'), EXT_ERROR_DEBUG );

		return $result;
	}
	
   public function fetch( $code ) {
		if( $this->debug ) echo "<p>".__METHOD__.": code = $code</p>";
		$this->error = '';
		$result = false;
		$stored_as = 'UNKNOWN';

		$check = $this->fetch_rows( $this->primary_key." = ".$code );
		$where = $check[0]['STORED_WHERE'];
		
		if( $this->files_where[$where] && is_array($check) && count($check) == 1) {
			$stored_as = str_replace('/', '\\', $check[0]['STORED_AS']);
		
			$result = $this->files_where[$where]->fetch( $stored_as );
		}
		
		if( $this->debug ) echo "<p>".__METHOD__.": return ".($result ? "true" : "false").(empty($this->error) ? "" : " error = ".$this->error)."</p>";
		
		$this->log_event(__METHOD__.": ".(empty($_SESSION['EXT_USERNAME']) ? '' : $_SESSION['EXT_USERNAME'])." code = $code, where = $where, stored_as = $stored_as, result = ".($result ? "true" : "false"), EXT_ERROR_DEBUG );

		return $result;
   }

	public function pathto_filesanywhere( $source ) {
		if( is_object($this->files_local) && is_object($this->files_anywhere) ) {
			$destination = str_replace(
				$this->files_local->get_attachment_dir(),
				$this->files_anywhere->get_attachment_dir(),
				$source
			);
			$destination = str_replace('/', '\\', $destination);
		} else {
			$destination = $source;
		}
		
		return $destination;
	}

	public function pathto_local( $source ) {
		if( is_object($this->files_local) && is_object($this->files_anywhere) ) {
			$destination = str_replace(
				$this->files_anywhere->get_attachment_dir(),
				$this->files_local->get_attachment_dir(),
				$source
				);
			$destination = str_replace('\\', '/', $destination);
		} else {
			$destination = $source;
		}
		
		return $destination;
	}


  //! Move an attachment from local to filesanywhere
   public function moveto_filesanywhere( $code ) {
		if( $this->debug ) echo "<p>".__METHOD__.": code = $code</p>";
		$this->log_event(__METHOD__.": entry, code = $code", EXT_ERROR_DEBUG );
		$this->error = '';
		$result = false;

		if( is_object($this->files_local) && is_object($this->files_anywhere) ) {
			$check = $this->fetch_rows( $this->primary_key." = ".$code.
				" AND STORED_WHERE = 'local'" );
			
			if( is_array($check) && count($check) == 1) {
				$source = str_replace('/', '\\', $check[0]['STORED_AS']);
				$destination = $this->pathto_filesanywhere( $check[0]['STORED_AS'] );
				if( $this->debug ) {
					echo "<pre>".__METHOD__.": source, destination\n";
					var_dump($source);
					var_dump($destination);
					echo "</pre>";
				}
				$this->log_event(__METHOD__.": source = $source, destination = $destination", EXT_ERROR_DEBUG );
			
			    $dir = dirname($destination);
			    if( ! $this->files_anywhere->dir_exists($dir) )
			    	$this->files_anywhere->mkdir($dir);	// Create the directories
	
				$result1 = $this->files_anywhere->put( $source, $destination );
				
				if( $result1 ) {
					$result = $this->update( $code,
						[ 'STORED_AS' => $destination,
						'STORED_WHERE' => self::WHERE_FILESANYWHERE ] );
					
					$this->files_local->unlink( $source );
				}
				$this->log_event(__METHOD__.": result1 = ".($result1 ? 'true' : 'false').
					" result = ".($result ? 'true' : 'false'), EXT_ERROR_DEBUG );

			}
		} else {
			$this->log_event(__METHOD__.": files_local = ".
				(is_object($this->files_local) ? 'true' : 'false').
				" files_anywhere = ".
				(is_object($this->files_anywhere) ? 'true' : 'false'), EXT_ERROR_DEBUG );

			if( $this->debug ) {
				echo "<pre>";
				var_dump(is_object($this->files_local), is_object($this->files_anywhere));
				echo "</pre>";
				
			}
		}
		
		if( $this->debug ) echo "<p>".__METHOD__.": return ".($result ? "true" : "false").(empty($this->error) ? "" : " error = ".$this->error)."</p>";
		return $result;
   }

   //! Move an attachment from local to filesanywhere
   public function moveto_local( $code ) {
		if( $this->debug ) echo "<p>".__METHOD__.": code = $code</p>";
		$this->error = '';
		$result = false;

		if( is_object($this->files_local) && is_object($this->files_anywhere) ) {
			$check = $this->fetch_rows( $this->primary_key." = ".$code.
				" AND STORED_WHERE = self::WHERE_FILESANYWHERE" );
			
			if( is_array($check) && count($check) == 1) {
				$source = $check[0]['STORED_AS'];
				$destination = $this->pathto_local( $source );
	
				if( $this->debug ) {
					echo "<pre>".__METHOD__.": source, destination\n";
					var_dump($source);
					var_dump($destination);
					echo "</pre>";
				}
			
			    $dir = dirname($destination);
			    if( ! file_exists($dir) )
			    	mkdir($dir, 0777, true);	// Create the directories
	
				$result1 = $this->files_anywhere->fetch( $source );
				
				if( $result1 ) {
					file_put_contents($destination, $result1);
					
					$result = $this->update( $code,
						[ 'STORED_AS' => $destination,
						'STORED_WHERE' => self::WHERE_LOCAL ] );
					
					$this->files_anywhere->unlink( $source );
				}
				
			}
		}
		
		if( $this->debug ) echo "<p>".__METHOD__.": return ".($result ? "true" : "false").(empty($this->error) ? "" : " error = ".$this->error)."</p>";
		return $result;
   }
   
   public function audit() {
		$all = $this->fetch_rows();
		$total_count = count($all);
		$total_missing = 0;

		if( $this->debug ) echo "<p>".__METHOD__.": entry, total=$total_count</p>";
		ob_flush(); flush();
		$this->log_event(__METHOD__.": entry, total=$total_count", EXT_ERROR_DEBUG );
	   
	//	echo "<pre>";
	//	var_dump($all);
	//	echo "</pre>";
		
		if( is_array($all) && count($all) > 0 ) {
			foreach( $all as $row ) {
				$code = $row['ATTACHMENT_CODE'];
				$source_type = $row['SOURCE_TYPE'];
				$source_code = $row['SOURCE_CODE'];
				$file_name = $row['FILE_NAME'];
				$stored_as = str_replace('/', '\\', $row['STORED_AS']);
				$where = $row['STORED_WHERE'];
				
				$exists = $this->files_where[$where]->file_exists( $row['STORED_AS'] );
				
				if( ! $exists ) {
				//	echo "<pre>";
				//	var_dump($row);
				//	echo "</pre>";
				
					echo '<h3><b>MISSING</b>: attachent code='.$code.' '.$source_type.'# '.$source_code.
						' filename='.$file_name.' where='.$where.
						'<br> stored_as='.$stored_as.'</h3>';
					ob_flush(); flush();
					$total_missing++;
				}
			}
			echo "<br><h3>DONE, missing=$total_missing out of $total_count attachments</h3>";
			ob_flush(); flush();
			$this->log_event(__METHOD__.": done, missing=$total_missing", EXT_ERROR_DEBUG );
		} else {
			echo "<br><p>DONE, none missing out of $total_count attachments</p>";
			ob_flush(); flush();
			$this->log_event(__METHOD__.": done, none missing", EXT_ERROR_DEBUG );
		}
	}

}

class sts_files_local extends sts_table {
	private $setting_table;
	private $attachment_dir;
	public $error;
	private $name_length = 13;
	private $parent;

	public function __construct( $database, $debug = false, $parent = false ) {

		$this->debug = $debug;
		$this->parent = $parent;
		if( $this->debug ) echo "<p>Create sts_files_local</p>";
		$this->setting_table = sts_setting::getInstance($database, $debug);
		$this->attachment_dir = $this->setting_table->get( 'option', 'ATTACHMENT_DIR' );
	}
	
	function __destruct() {
		if( $this->debug ) echo "<p>Destroy sts_files_local</p>";
	}
	
	// Allow re-use of objects - singleton function
	public static function getInstance( $database, $debug = false, $parent = false ) {
		static $instance = null;
		$myclass = get_class ();
		if( $debug ) echo "<p>Get instance of $myclass</p>";
		if (null === $instance) {
			$instance = new $myclass( $database, $debug, $parent );
		}
		return $instance;
    }

	public function get_attachment_dir() {
		return $this->attachment_dir;
	}

	//! SCR# 599 - allow for hierarchy
	public function stored_as( $ext = 'jpg' ) {
	    $name = $this->attachment_dir.date('Y/m/d').'/EXP';
	    $dir = dirname($name);
	    if( ! file_exists($dir) ) {
		    if( is_writeable($this->attachment_dir) )
	    		@mkdir($dir, 0777, true);	// Create the directories
	    	else
	    		echo '<h2 class="text-danger">Attachments Directory Missing or read only</h2>
	    		<p>Please check directory permissions, inheritance, etc.</p>';
	    }
	    
	    if( file_exists($dir) && is_dir($dir) && is_writeable($dir)) {
		    $keys = array_merge(range(0, 9), range('a', 'z'));
		
		    for ($i = 0; $i < $this->name_length; $i++) {
		        $name .= $keys[array_rand($keys)];
		    }

			while ( $this->file_exists($name.'.'.$ext) ) {
				$name .= $keys[rand(0,51)];
			}		    
			if( $this->debug ) echo "<p>".__METHOD__.": name = $name</p>";
		} else {
			$name = false;
			if( $this->debug ) echo "<p>".__METHOD__.": name = FALSE</p>";
		}

		return $name;
	}
	
   public function ls( $path ) {
		if( $this->debug ) echo "<p>".__METHOD__.": path = $path</p>";
		$this->error = '';
		$result = false;
		
		$result = scandir($path);

		if( $this->debug ) echo "<p>".__METHOD__.": return ".($result ? count($result)." items" : "false").(empty($this->error) ? "" : " error = ".$this->error)."</p>";
		return $result;
   }

   public function file_exists( $path ) {
		if( $this->debug ) echo "<p>".__METHOD__.": path = $path</p>";
		$this->error = '';
		$result = false;
		
		$result = file_exists( $path );

		if( $this->debug ) echo "<p>".__METHOD__.": return ".($result ? "true" : "false").(empty($this->error) ? "" : " error = ".$this->error)."</p>";

		$this->parent->log_event('   '.__METHOD__.": path = $path, result = ".($result ? "true" : "false").(empty($this->error) ? "" : " error = ".$this->error), EXT_ERROR_DEBUG );		

		return $result;
   }

   public function file_size( $path ) {
		if( $this->debug ) echo "<p>".__METHOD__.": path = $path</p>";
		$this->error = '';
		$result = filesize( $path );
		
		if( $this->debug ) echo "<p>".__METHOD__.": return ".($result ? $result : "false").(empty($this->error) ? "" : " error = ".$this->error)."</p>";

		$this->parent->log_event('   '.__METHOD__.": path = $path, result = ".($result ? $result : "false").(empty($this->error) ? "" : " error = ".$this->error), EXT_ERROR_DEBUG );		

		return $result;
   }
    
   public function dir_exists( $path ) {
		if( $this->debug ) echo "<p>".__METHOD__.": path = $path</p>";
		$this->error = '';
		$result = false;
		
		$result = file_exists( $path ) && is_dir($path);

		if( $this->debug ) echo "<p>".__METHOD__.": return ".($result ? "true" : "false").(empty($this->error) ? "" : " error = ".$this->error)."</p>";
		return $result;
   }

   public function put( $tmp_name, $destination ) {
		if( $this->debug ) echo "<p>".__METHOD__.": $tmp_name, $destinationpath</p>";
		$this->error = '';
		$result = false;
		
		$result = move_uploaded_file($tmp_name, $destination);
		
		if( $this->debug ) echo "<p>".__METHOD__.": return ".($result ? "true" : "false").(empty($this->error) ? "" : " error = ".$this->error)."</p>";
		
		$this->parent->log_event('   '.__METHOD__.": tmp_name = $tmp_name, destination = $destination, result = ".($result ? "true" : "false"), EXT_ERROR_DEBUG );		
		
		return $result;
   }

   public function fetch( $path ) {
		if( $this->debug ) echo "<p>".__METHOD__.": path = $path</p>";
		$this->error = '';
		
		$result = file_get_contents($path);
		
		if( $this->debug ) echo "<p>".__METHOD__.": return ".($result ? "true" : "false").(empty($this->error) ? "" : " error = ".$this->error)."</p>";

		$this->parent->log_event('   '.__METHOD__.": path = $path, result = ".($result ? "true" : "false"), EXT_ERROR_DEBUG );		

		return $result;
   }

   public function unlink( $path ) {
		if( $this->debug ) echo "<p>".__METHOD__.": path = $path</p>";
		$this->error = '';
		
		$result = unlink( $path );
		
		if( $this->debug ) echo "<p>".__METHOD__.": return ".($result ? "true" : "false").(empty($this->error) ? "" : " error = ".$this->error)."</p>";
		if( $this->debug ) {
			echo "<pre>";
		//	var_dump($this);
			var_dump($this->parent);
			echo "</pre>";
		}
		

		$this->parent->log_event('   '.__METHOD__.": path = $path, result = ".($result ? "true" : "false"), EXT_ERROR_DEBUG );		

		return $result;
   }

}

//! SCR# 644 - FilesAnywhere API
class sts_files_anywhere extends sts_table {
	private $setting_table;
	private $api_key;
	private $clientid;
	private $username;
	private $password;
	private $wsdl;
	private $attachment_dir;
	private $name_length = 13;
	private $soapclient;
	private $token = '';
	public $error;
	private $parent;

	public function __construct( $database, $debug = false, $parent = false ) {

		$this->debug = $debug;
		$this->parent = $parent;
		if( $this->debug ) {
			echo "<p>".__METHOD__.": entry</p>";
			ob_flush(); flush();
		}
		if( $this->debug ) echo "<p>Create sts_files_anywhere</p>";
		$this->setting_table = sts_setting::getInstance($database, $debug);
		$this->api_key			= $this->setting_table->get( 'api', 'FA_API_KEY' );
		$this->clientid			= $this->setting_table->get( 'api', 'FA_API_CLIENTID' );
		$this->username			= $this->setting_table->get( 'api', 'FA_API_USERNAME' );
		$this->password			= $this->setting_table->get( 'api', 'FA_API_PASSWORD' );
		$this->wsdl				= $this->setting_table->get( 'api', 'FA_API_WSDL' );
		$this->attachment_dir	= $this->setting_table->get( 'api', 'FA_API_PATH' );
		if( $this->debug ) {
				echo "<pre>".__METHOD__.": settings\n";
				var_dump($this->api_key, $this->clientid, $this->username, $this->password, $this->wsdl, $this->attachment_dir);
				echo "</pre>";
			ob_flush(); flush();
		}
		
		$attempts = 3;	// Try this 3 times
		$old_timeout = ini_set("default_socket_timeout", 15);
		//ini_set("user_agent", 'PHP SoapClient');
		
		$opts = array(
			'http' => array(
				'user_agent' => 'PHPSoapClient'
			)
		);
		$context = stream_context_create($opts);
		$soapClientOptions = array(
			'stream_context' => $context,
			'cache_wsdl' => WSDL_CACHE_BOTH
		);

		do{
			$attempts--;
			$soap_exception = false;

			try {
				if( $this->debug ) {
					echo "<p>".__METHOD__.": before new SoapClient attempts = $attempts</p>";
					ob_flush(); flush();
				}
				// Add , array("trace" => true) for __getLastRequest etc.
				$this->soapclient = new SoapClient($this->wsdl, $soapClientOptions);
			} catch (SoapFault $exception) {
				$soap_exception = true;
				$exception_message = "SoapFault ".$exception->getMessage();
				if( $this->debug ) echo "<p>".__METHOD__.": ". $exception_message."</p>";
			} catch(Exception $exception){
				$soap_exception = true;
				$exception_message = "Exception ".$exception->getMessage();
				if( $this->debug ) echo "<p>".__METHOD__.": ". $exception_message."</p>";
			}
			
			if( $soap_exception && $attempts > 0 ) sleep( 2 ); // sleep 2 seconds
			
		} while( $soap_exception && $attempts > 0 );
		ini_set("default_socket_timeout", $old_timeout);
		
		if( $soap_exception ) {
			if( $this->debug ) {
				echo "<p>".__METHOD__.": got exception</p>";
				ob_flush(); flush();
			}
			$this->soapclient = false;
			$email = sts_email::getInstance($this->database, $this->debug);
			$email->send_alert(__METHOD__.": exception connecting to filesanywhere<br>".
				"<pre>".$exception_message."</pre><br>".
				"addr = <pre>".$this->wsdl."</pre>" );
			$this->parent->log_event('   '.__METHOD__.": exception connecting to filesanywhere\n".
				$exception_message."\n".
				"addr = $this->wsdl", EXT_ERROR_DEBUG );		

		} else {
			$this->parent->log_event('   '.__METHOD__.": connected to filesanywhere", EXT_ERROR_DEBUG );
			if( $this->debug ) {
				echo "<p>".__METHOD__.": no exception</p>";
				echo "<pre>";
				var_dump($this->soapclient);
				echo "</pre>";
				ob_flush(); flush();
			}
		}
		
		// Uncomment this to see a list of functions
		//echo "<pre>".__METHOD__.":\n";
		//var_dump($this->soapclient->__getFunctions());
		//echo "</pre>";
		
		// Only login if you have all five
		if( $this->soapclient &&
			! empty($this->api_key) && ! empty($this->attachment_dir) &&
			! empty($this->username) && ! empty($this->password) )
			$this->login();
	}
	
	function __destruct() {
		if( $this->debug ) echo "<p>Destroy sts_files_anywhere</p>";
		
		$this->logout();
	}

	// Allow re-use of objects - singleton function
	public static function getInstance( $database, $debug = false, $parent = false ) {
		static $instance = null;
		$myclass = get_class ();
		if( $debug ) echo "<p>Get instance of $myclass</p>";
		if (null === $instance) {
			$instance = new $myclass( $database, $debug, $parent );
		}
		return $instance;
    }
    
	public function get_attachment_dir() {
		return $this->attachment_dir;
	}

    public function active() {
	    return $this->soapclient && ! empty($this->token);
    }
    
    private function login() {
		$this->error = '';
		$result = false;

	    if( $this->soapclient == false ) {
		    $this->error = 'soapclient not initialized';
		} else {
		    $params = array(
		    	'APIKey' => $this->api_key,
		    	'OrgID' => intval($this->clientid),
		    	'UserName' => $this->username,
		    	'Password' => $this->password
		    );
			try {
				$response = $this->soapclient->AccountLogin($params);
			} catch (SoapFault $exception) {
				$this->error = "SoapFault ".$exception->getMessage();
				$response = false;
				if( $this->debug ) echo "<p>".__METHOD__.": ". $this->error."</p>";
			} catch(Exception $exception){
				$this->error = "Exception ".$exception->getMessage();
				$response = false;
				if( $this->debug ) echo "<p>".__METHOD__.": ". $this->error."</p>";
			}
			
	 		//echo "<pre>".$this->soapclient->__getLastRequestHeaders()."</pre>";
	 		//echo "<pre>".$this->soapclient->__getLastRequest()."</pre>";
	 		//echo "<pre>".$this->soapclient->__getLastResponse()."</pre>";
		 		
			if( is_object($response) && isset($response->LoginResult) &&
				is_object($response->LoginResult)) {
				$login_result = $response->LoginResult;
				
				if( ! empty($login_result->Token) ) {
					$this->token = $login_result->Token;
					$result = true;
				} else
					$this->error = $login_result->ErrorMessage;
			} else {
				echo '<h2>Problem connecting to FilesAnywhere</h2>
				<p>Please contact Exspeedite support, with this information</p>
				<p>'.$this->error.'</p>';
			}
		}

		if( $this->debug ) echo "<p>".__METHOD__.": return ".($result ? "true" : "false").(empty($this->error) ? "" : " error = ".$this->error)."</p>";
		return $result;
    }
    
    public function logout() {
		$this->error = '';
		$result = false;
		
	    if( $this->soapclient == false ) {
		    $this->error = 'soapclient not initialized';
		} else if( ! empty($this->token)) {
		    $params = array(
		    	'Token' => $this->token
	    	);
		    
	 		$response = $this->soapclient->Logout($params);
	
			if( is_object($response) && isset($response->LogoutResult) &&
				is_object($response->LogoutResult)) {
				$logout_result = $response->LogoutResult;
				
				if( isset($logout_result->LoggedOut) )
					$result = $logout_result->LoggedOut;
					
				if( ! empty($logout_result->ErrorMessage))
					$this->error = $logout_result->ErrorMessage;
					
				if( $result )
					$this->token = '';
			}

		} else
			$this->error = 'No token';

		if( $this->debug ) echo "<p>".__METHOD__.": return ".($result ? "true" : "false").(empty($this->error) ? "" : " error = ".$this->error)."</p>";
		return $result;
   }
   
	public function stored_as( $ext = 'jpg' ) {
	    $name = $this->attachment_dir.date('Y').'\\'.date('m').'\\'.date('d').'\EXP';
	    if( $this->debug ) echo "<p>".__METHOD__.": name = $name</p>";
	    $dir = str_replace('/', '\\', dirname(str_replace('\\', '/', $name)));
	    if( $this->debug ) echo "<p>".__METHOD__.": dir = $dir</p>";
	    if( ! $this->dir_exists($dir) )
	    	$this->mkdir($dir);	// Create the directories
	    
	    if( $this->dir_exists($dir) ) {
		    $keys = array_merge(range(0, 9), range('a', 'z'));
		
		    for ($i = 0; $i < $this->name_length; $i++) {
		        $name .= $keys[array_rand($keys)];
		    }

			while ( $this->file_exists($name.'.'.$ext) ) {
				$name .= $keys[rand(0,51)];
			}		    
			if( $this->debug ) echo "<p>".__METHOD__.": name = $name</p>";
		} else {
			$name = false;
			if( $this->debug ) echo "<p>".__METHOD__.": name = FALSE (dir missing)</p>";
		}

		return $name;
	}

   public function ls( $path ) {
		if( $this->debug ) echo "<p>".__METHOD__.": path = $path</p>";
		$this->error = '';
		$result = false;
		
	    if( $this->soapclient == false ) {
		    $this->error = 'soapclient not initialized';
		} else if( ! empty($this->token)) {
		    $params = array(
		    	'Token' => $this->token,
		    	'Path' => $path,
		    	'PageSize' => 0,
		    	'PageNum' => 0
	    	);
	 		
	 		$response = $this->soapclient->ListItems2($params);
	
			if( is_object($response) && isset($response->ListItemsResult) &&
				is_object($response->ListItemsResult)) {
				$fsg_result = $response->ListItemsResult;
				$items = array();
				
				if( ! empty($fsg_result->ErrorMessage) ) {
					$this->error = $fsg_result->ErrorMessage;
				} else {
					if( is_object($fsg_result->Items) &&
						is_array($fsg_result->Items->Item) &&
						count($fsg_result->Items->Item) > 0 ) {
						
						foreach( $fsg_result->Items->Item as $row ) {
							$items[] = $row->Name;
						}
						$result = $items;
					}
				}
				
			}

			echo "<pre>".__METHOD__.":\n";
			var_dump($items);
			var_dump($this->error);
			echo "</pre>";
				
		} else
			$this->error = 'No token';

		if( $this->debug ) echo "<p>".__METHOD__.": return ".($result ? count($result)." items" : "false").(empty($this->error) ? "" : " error = ".$this->error)."</p>";
		return $result;
   }
    
   public function file_exists( $path ) {
		if( $this->debug ) echo "<p>".__METHOD__.": path = $path</p>";
		$this->error = '';
		$result = false;
		
	    if( $this->soapclient == false ) {
		    $this->error = 'soapclient not initialized';
		} else if( ! empty($this->token)) {
		    $params = array(
		    	'Token' => $this->token,
		    	'Path' => $path,
	    	);
	 		
	 		$response = $this->soapclient->FileProperties($params);
	
			if( is_object($response) && isset($response->ItemsPropertiesResult) &&
				is_object($response->ItemsPropertiesResult)) {
				$fp_result = $response->ItemsPropertiesResult;
				
				if( $fp_result->FileExists )
					$result = true;
				else
					$this->error = $fp_result->ErrorMessage;
			}

		} else
			$this->error = 'No token';

		if( $this->debug ) echo "<p>".__METHOD__.": return ".($result ? "true" : "false").(empty($this->error) ? "" : " error = ".$this->error)."</p>";

		$this->parent->log_event('   '.__METHOD__.": path = $path, result = ".($result ? "true" : "false").(empty($this->error) ? "" : " error = ".$this->error), EXT_ERROR_DEBUG );		

		return $result;
   }
    
   public function file_size( $path ) {
		if( $this->debug ) echo "<p>".__METHOD__.": path = $path</p>";
		$this->error = '';
		$result = false;
		
	    if( $this->soapclient == false ) {
		    $this->error = 'soapclient not initialized';
		} else if( ! empty($this->token)) {
		    $params = array(
		    	'Token' => $this->token,
		    	'Path' => $path,
	    	);
	 		
	 		$response = $this->soapclient->FileProperties($params);
	
			if( is_object($response) && isset($response->ItemsPropertiesResult) &&
				is_object($response->ItemsPropertiesResult)) {
				$fp_result = $response->ItemsPropertiesResult;
				
				if( $fp_result->FileExists && isset($fp_result->Size) )
					$result = $fp_result->Size;
				else
					$this->error = $fp_result->ErrorMessage;
			}

		} else
			$this->error = 'No token';

		if( $this->debug ) echo "<p>".__METHOD__.": return ".($result ? $result : "false").(empty($this->error) ? "" : " error = ".$this->error)."</p>";

		$this->parent->log_event('   '.__METHOD__.": path = $path, result = ".($result ? $result : "false").(empty($this->error) ? "" : " error = ".$this->error), EXT_ERROR_DEBUG );		

		return $result;
   }
    
   public function dir_exists( $path ) {
		if( $this->debug ) echo "<p>".__METHOD__.": path = $path</p>";
		$this->error = '';
		$result = false;
		
	    if( $this->soapclient == false ) {
		    $this->error = 'soapclient not initialized';
		} else if( ! empty($this->token)) {
		    $params = array(
		    	'Token' => $this->token,
		    	'FolderPath' => $path,
	    	);
			if( $this->debug ) {
				echo "<pre>".__METHOD__.": params\n";
				var_dump($params);
				echo "</pre>";
			}
	 			 		
	 		$response = $this->soapclient->GetFolderProperties($params);
	
			if( $this->debug ) {
				echo "<pre>".__METHOD__.": response\n";
				var_dump($response);
				echo "</pre>";
			}
	 		
			if( is_object($response) && isset($response->GetFolderPropertiesResult) &&
				is_object($response->GetFolderPropertiesResult)) {
				$fp_result = $response->GetFolderPropertiesResult;
				
				if( ! empty($fp_result->ErrorMessage) ) {
					$this->error = $fp_result->ErrorMessage;
				} else
					$result = true;
			}

		} else
			$this->error = 'No token';

		if( $this->debug ) echo "<p>".__METHOD__.": return ".($result ? "true" : "false").(empty($this->error) ? "" : " error = ".$this->error)."</p>";

		$this->parent->log_event('   '.__METHOD__.": path = $path, result = ".($result ? "true" : "false").(empty($this->error) ? "" : " error = ".$this->error), EXT_ERROR_DEBUG );		

		return $result;
   }
    
   public function put( $tmp_name, $destination ) {
		if( $this->debug ) echo "<p>".__METHOD__.": $tmp_name, $destination</p>";
		$this->error = '';
		$result = false;
		
	    if( $this->soapclient == false ) {
		    $this->error = 'soapclient not initialized';
		} else {
			$filedata = file_get_contents($tmp_name); 
			
			if( ! empty($this->token)) {
			    $params = array(
			    	'Token' => $this->token,
			    	'Path' => $destination,
			    	'FileData' => $filedata
		    	);
		 		
		 		$response = $this->soapclient->UploadFile($params);
		
				if( is_object($response) && isset($response->UploadFileResult) &&
					is_object($response->UploadFileResult)) {
					$ul_result = $response->UploadFileResult;
	
					if( ! empty($ul_result->ErrorMessage) ) {
						$this->error = $ul_result->ErrorMessage;
					} else
						$result = true;
				}
	
			} else
				$this->error = 'No token';
		}

		if( $this->debug ) echo "<p>".__METHOD__.": return ".($result ? "true" : "false").(empty($this->error) ? "" : " error = ".$this->error)."</p>";

		$this->parent->log_event('   '.__METHOD__.": tmp_name = $tmp_name, destination = $destination, result = ".($result ? "true" : "false").(empty($this->error) ? "" : " error = ".$this->error), EXT_ERROR_DEBUG );		

		return $result;
   }
    
   public function fetch( $path ) {
		if( $this->debug ) echo "<p>".__METHOD__.": path = $path</p>";
		$this->error = '';
		$result = false;
		
	    if( $this->soapclient == false ) {
		    $this->error = 'soapclient not initialized';
		} else if( ! empty($this->token)) {
		    $params = array(
		    	'Token' => $this->token,
		    	'Path' => $path,
	    	);
	    	
			try {
		 		$response = $this->soapclient->DownloadFile($params);
		
				if( is_object($response) && isset($response->DownloadFileResult) &&
					is_object($response->DownloadFileResult)) {
					$dl_result = $response->DownloadFileResult;

					$save = error_reporting(E_ERROR );
					if( ! empty($dl_result->ErrorMessage) ) {
						$this->error = $dl_result->ErrorMessage;
					} else if( ! empty($dl_result->URL) ) {
						$result = file_get_contents($dl_result->URL);
					}
					error_reporting($save);
				}
			} catch (SoapFault $exception) {
				$this->error = "SoapFault ".$exception->getMessage();
				if( $this->debug ) echo "<p>".__METHOD__.": ". $exception_message."</p>";
			} catch(Exception $exception){
				$this->error = "Exception ".$exception->getMessage();
				if( $this->debug ) echo "<p>".__METHOD__.": ". $exception_message."</p>";
			}
				
				/* testing
				ob_clean();
				header('Content-Type: image/jpeg');
				header('Content-Length: ' . strlen($result));
				header('Content-Disposition: inline; filename="fuzzy.jpg"');
				echo $result;
				die;
				*/
				

		} else
			$this->error = 'No token';

		if( $this->debug ) echo "<p>".__METHOD__.": return ".($result ? "true" : "false").(empty($this->error) ? "" : " error = ".$this->error)."</p>";

		$this->parent->log_event('   '.__METHOD__.": path = $path, result = ".($result ? "true" : "false").(empty($this->error) ? "" : " error = ".$this->error), EXT_ERROR_DEBUG );		

		return $result;
   }
    
   public function unlink( $path ) {
		if( $this->debug ) echo "<p>".__METHOD__.": path = $path</p>";
		$this->error = '';
		$result = false;
		
	    if( $this->soapclient == false ) {
		    $this->error = 'soapclient not initialized';
		} else if( ! empty($this->token)) {
		    $params = array(
		    	'Token' => $this->token,
		    	'ItemsToDelete' => array(
		    		'Item' => array(
				    	'Type' => 'file',
				    	'Path' => $path,
					)
		    	),
	    	);
	 		
	 		$response = $this->soapclient->DeleteItems($params);

			if( is_object($response) && isset($response->DeleteItemsResult) &&
				is_object($response->DeleteItemsResult)) {
				$ul_result = $response->DeleteItemsResult;

				if( ! empty($ul_result->ErrorMessage) ) {
					$this->error = $ul_result->ErrorMessage;
				} else
					$result = true;
			}

		} else
			$this->error = 'No token';

		if( $this->debug ) echo "<p>".__METHOD__.": return ".($result ? "true" : "false").(empty($this->error) ? "" : " error = ".$this->error)."</p>";

		$this->parent->log_event('   '.__METHOD__.": path = $path, result = ".($result ? "true" : "false").(empty($this->error) ? "" : " error = ".$this->error), EXT_ERROR_DEBUG );		

		return $result;
   }
    
   public function mkdir( $dir ) {
		if( $this->debug ) echo "<p>".__METHOD__.": dir = $dir</p>";
		$this->error = '';
		$result = false;
		$path = str_replace('/', '\\', dirname(str_replace('\\', '/', $dir)));
		$folder = basename(str_replace('\\', '/', $dir));
		
	    if( $this->soapclient == false ) {
		    $this->error = 'soapclient not initialized';
		} else if( ! empty($this->token)) {
		    $params = array(
		    	'Token' => $this->token,
		    	'Path' => $path,
		    	'NewFolderName' => $folder
	    	);
			if( $this->debug ) {
				echo "<pre>".__METHOD__.": params\n";
				var_dump($params);
				echo "</pre>";
			}
	 		
	 		$response = $this->soapclient->CreateFolderRecursive($params);

			if( $this->debug ) {
				echo "<pre>".__METHOD__.": response\n";
				var_dump($response);
				echo "</pre>";
			}
	 		
			if( is_object($response) && isset($response->CreateFolderRecursiveResult) &&
				is_object($response->CreateFolderRecursiveResult)) {
				$cf_result = $response->CreateFolderRecursiveResult;

				if( ! empty($cf_result->ErrorMessage) ) {
					$this->error = $cf_result->ErrorMessage;
				} else
					$result = true;
			}

		} else
			$this->error = 'No token';

		if( $this->debug ) echo "<p>".__METHOD__.": return ".($result ? "true" : "false").(empty($this->error) ? "" : " error = ".$this->error)."</p>";

		$this->parent->log_event('   '.__METHOD__.": dir = $dir, result = ".($result ? "true" : "false").(empty($this->error) ? "" : " error = ".$this->error), EXT_ERROR_DEBUG );		

		return $result;
   }
    
}

//! Form Specifications - For use with sts_form

$sts_form_add_attachment_form = array(	//! $sts_form_addattachment_form
	'title' => '<img src="images/image_icon.png" alt="image_icon" height="24"> Add Attachment',
	'action' => 'exp_addattachment.php',
	//'actionextras' => 'disabled',
	'popup' => true,	// issue with the toggle switches
	'cancel' => 'exp_listimage.php',
	'name' => 'addattachment',
	'okbutton' => 'Save Changes',
	'cancelbutton' => 'Cancel',
		'layout' => '
	%SOURCE_CODE%
	%SOURCE_TYPE%
	%STORED_WHERE%
	%STORED_AS%
	%IS_COMPRESSED%
	%SIZE%
	<div class="form-group">
		<div class="col-sm-9">
			<div class="form-group" id="FILE_GROUP">
				<label for="FILE_NAME" class="col-sm-4 control-label">#FILE_NAME#</label>
				<div class="col-sm-8">
					%FILE_NAME%
				</div>
				<div class="col-sm-offset-4 col-sm-8" id="TOO_BIG" hidden>
				</div>
			</div>
			<div class="form-group">
				<label for="FILE_TYPE" class="col-sm-4 control-label">#FILE_TYPE#</label>
				<div class="col-sm-6">
					%FILE_TYPE%
				</div>
			</div>
		</div>
	</div>
	
	'
);


//! Field Specifications - For use with sts_form

$sts_form_add_attachment_fields = array(
	'SOURCE_CODE' => array( 'format' => 'hidden' ),
	'SOURCE_TYPE' => array( 'format' => 'hidden' ),
	'FILE_NAME' => array( 'label' => 'File To Attach', 'format' => 'attachment', 'extras' => 'required' ),
	'FILE_TYPE' => array( 'label' => 'Attachment type', 'format' => 'table',
		'table' => ITEM_LIST_TABLE, 'key' => 'ITEM_CODE', 'fields' => 'ITEM',
		'condition' => 'ITEM_TYPE = \'Attachment type\'' ),
	'STORED_WHERE' => array( 'format' => 'hidden', 'value' => 'local' ),
	'STORED_AS' => array( 'format' => 'hidden', 'value' => 'TBD' ),
	'IS_COMPRESSED' => array( 'format' => 'hidden' ),
	'SIZE' => array( 'format' => 'hidden' ),

);

//! Layout Specifications - For use with sts_result

$sts_result_attachment_layout = array(
	'ATTACHMENT_CODE' => array( 'format' => 'hidden' ),
	'SOURCE_CODE' => array( 'format' => 'hidden' ),
	'FILE_NAME' => array( 'label' => 'Attachment', 'format' => 'hidden' ),
	'FILE_NAME2' => array( 'label' => 'Attachment', 'format' => 'table',
		'snippet' => 'CONCAT(\'<span class="tip" title="Stored as \',
		STORED_AS, \' on \', STORED_WHERE, \'">\', FILE_NAME)' ),
	'SOURCE_TYPE' => array( 'format' => 'text', 'label' => 'Attached To' ),
	'SIZE' => array( 'label' => 'Size', 'format' => 'numb', 'align' => 'right' ),
	'IS_COMPRESSED' => array( 'label' => 'Comp', 'format' => 'bool', 'align' => 'center' ),
	'FILE_TYPE' => array( 'label' => 'Attachment type', 'format' => 'table',
		'table' => ITEM_LIST_TABLE, 'key' => 'ITEM_CODE', 'fields' => 'ITEM',
		'condition' => 'ITEM_TYPE = \'Attachment type\'' ),
	//'STORED_WHERE' => array( 'label' => 'Where', 'format' => 'text' ),
	//'STORED_AS' => array( 'label' => 'As', 'format' => 'text' ),

	'CREATED_DATE' => array( 'label' => 'Created', 'format' => 'datetime' ),
	'CREATED_BY' => array( 'label' => 'By', 'format' => 'table',
		'table' => USER_TABLE, 'key' => 'USER_CODE', 'fields' => 'USERNAME', 'link' => 'exp_edituser.php?CODE=' )

);

//! Edit/Delete Button Specifications - For use with sts_result

$sts_result_attachment_edit = array(
	'title' => '<img src="images/image_icon.png" alt="image_icon" height="24"> Attachments',
	'sort' => 'CREATED_DATE asc',
	'popup' => true,
	//'cancel' => 'index.php',
	'add' => 'exp_addattachment.php',
	//'actionextras' => 'disabled',
	'addbutton' => 'Add Attachment',
	//'cancelbutton' => 'Back',
	'rowbuttons' => array(
		array( 'url' => 'exp_viewattachment.php?CODE=', 'key' => 'ATTACHMENT_CODE', 'label' => 'FILE_NAME', 'tip' => 'View/download attachment ', 'icon' => 'glyphicon glyphicon-eye-open', 'target' => '_blank' ),
		array( 'url' => 'exp_compressattachment.php?CODE=', 'key' => 'ATTACHMENT_CODE', 'label' => 'FILE_NAME', 'tip' => 'Compress attachment ', 'icon' => 'glyphicon glyphicon-download-alt', 'showif' => 'admin' ),
		array( 'url' => 'exp_emailattachment.php?CODE=', 'key' => 'ATTACHMENT_CODE', 'label' => 'FILE_NAME', 'tip' => 'Email attachment ', 'icon' => 'glyphicon glyphicon-envelope' ),
		array( 'url' => 'exp_deleteattachment.php?CODE=', 'key' => 'ATTACHMENT_CODE', 'label' => 'FILE_NAME', 'tip' => 'Delete attachment ', 'icon' => 'glyphicon glyphicon-trash', 'confirm' => 'yes' )
	)
);


?>
