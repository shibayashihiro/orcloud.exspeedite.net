<?php

// $Id: sts_card_ftp_class.php 5030 2023-04-12 20:31:34Z duncan $
// - Fuel card FTP class

// no direct access
defined('_STS_INCLUDE') or die('Restricted access');
require_once( "sts_card_transport_class.php" );


class sts_card_ftp extends sts_card_transport {
	private $connect_timeout = 60;
	private $default_ftp_port = 21;
	
	// Profiling
	private $profiling = false;
	private $timer;
	private $time_connect;
	private $time_login;
	private $time_list;
	private $time_query;

	// Constructor does not need the table name
	public function __construct( $database, $debug = false ) {
		if( ! function_exists('ftp_connect') ) {
			echo "<h2>Function ftp_connect() is undefined.</h2>
			<p>It appears your PHP installation does not have the FTP enabled. This is needed for Fuel Card Import operation.</p>
			<p>You can confirm this via the installer. Run the installer and click on <strong>Check PHP</strong>.</p>
			<p>If FTP is not enabled, you have to take steps to enable it or re-install PHP.</p>";
			die;
		}

		parent::__construct( $database, $debug);
		$this->profiling = $debug;
		
		if( $this->debug ) echo "<p>Create sts_card_ftp</p>";
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

	//! Connect, login, chdir and get list of files in directory
	public function ftp_connect( $client ) {
		if( $this->profiling ) {
			$this->timer = new sts_timer();
			$this->timer->start();
		}
		if( $this->debug ) echo "<p>".__METHOD__.": Connect to client $client</p>";
		$this->log_event( __METHOD__.": Connect to client $client", EXT_ERROR_DEBUG);
		$result = false;
		if( ! (is_array($this->ftp_client) &&
			isset($this->ftp_client['FTP_REMOTE_ID']) &&
			$this->ftp_client['FTP_REMOTE_ID'] == $client ) ) {
			$this->client_open( $client );
		}
		if( $this->transport( $client ) == 'FTP' ) {	//! Transport needs to be FTP
			if( is_array($this->ftp_client) ) {
				if( $this->debug ) echo "<p>".__METHOD__.": Connect to ".$this->ftp_client['FTP_SERVER'].", port ".$this->ftp_client['FTP_PORT']."</p>";
				$this->conn_id = @ftp_connect($this->ftp_client['FTP_SERVER'],
					isset($this->ftp_client['FTP_PORT']) ? $this->ftp_client['FTP_PORT'] : $this->default_ftp_port,
					$this->connect_timeout );
				
				if( $this->profiling ) {
					$this->timer->stop();
					$this->time_connect = $this->timer->result();
					echo "<p>".__METHOD__.": connect Time: ".$this->timer->result()."</p>";
					ob_flush(); flush();
					$this->timer->start();
				}

				if( $this->conn_id ) {
					// login with username and password
					if( $this->debug ) echo "<p>".__METHOD__.": login ".$this->ftp_client['FTP_USER_NAME'].", ".$this->ftp_client['FTP_USER_PASS']."</p>";
					if( ftp_login($this->conn_id, $this->ftp_client['FTP_USER_NAME'],
						$this->ftp_client['FTP_USER_PASS']) ) {
						
						if( $this->ftp_client['FTP_PASV'] )
							ftp_pasv( $this->conn_id, true );		// enable passive mode
						if( $this->profiling ) {
							$this->timer->stop();
							$this->time_login = $this->timer->result();
							echo "<p>".__METHOD__.": login Time: ".$this->timer->result()."</p>";
							ob_flush(); flush();
							$this->timer->start();
						}
						
						// Change directory
						if( $this->debug ) echo "<p>".__METHOD__.": chdir ".$this->ftp_client['FTP_DL_PATH']."</p>";
						if (@ftp_chdir($this->conn_id, $this->ftp_client['FTP_DL_PATH'])) {
	
							// get contents of the current directory
							$dir = ! empty($this->ftp_client['FTP_DL_SUFFIX']) ? '*'.$this->ftp_client['FTP_DL_SUFFIX'] : '*';
							if( $this->debug ) echo "<p>".__METHOD__.": nlist ".$dir."</p>";
							$result = ftp_nlist($this->conn_id, $dir );
							if( $result == false )
								$result = array();

							if( $this->profiling ) {
								$this->timer->stop();
								$this->time_list = $this->timer->result();
								echo "<p>".__METHOD__.": list Time: ".$this->timer->result()."</p>";
								ob_flush(); flush();
							}
						
							$this->log_event( __METHOD__.": Connected, return ".count($result)." entries.", EXT_ERROR_DEBUG);
							
						} else { 
						    $this->last_error =  "ftp_connect: Couldn't change directory to download directory ".$this->ftp_client['FTP_DL_PATH'];
						    $this->log_event( __METHOD__.": ".$this->last_error, EXT_ERROR_ERROR);
						}					
					} else {
						$this->last_error =  "ftp_connect: Unable to login to server ".$this->ftp_client['FTP_SERVER'].
							' with credentials ('.$this->ftp_client['FTP_USER_NAME'].', '.
							$this->ftp_client['FTP_USER_PASS'].')';
						$this->log_event( __METHOD__.": ".$this->last_error, EXT_ERROR_ERROR);
						//! Exception, log error
						require_once( "sts_email_class.php" );
						$email = sts_email::getInstance($this->database, $this->debug);
						$email->send_alert( __METHOD__.": Exception<br>".
							$this->last_error );
					}
				} else {
					$this->last_error = 'ftp_connect: Unable to connect to '.$this->ftp_client['FTP_SERVER'].' port '.(isset($this->ftp_client['FTP_PORT']) ? $this->ftp_client['FTP_PORT'] : $this->default_ftp_port);
					
					if( gethostbyname($this->ftp_client['FTP_SERVER']) == $this->ftp_client['FTP_SERVER'])
					$this->last_error .= '<br>Exspeedite couldn\'t map the server name <strong>'.$this->ftp_client['FTP_SERVER'].'</strong> to an IP address, which means it probably doesn\'t exist.<br>';

					$this->log_event( __METHOD__.": ".$this->last_error, EXT_ERROR_ERROR);
					
					//! Exception, log error
					require_once( "sts_email_class.php" );
					$email = sts_email::getInstance($this->database, $this->debug);
					$email->send_alert( __METHOD__.": Exception<br>".
						$this->last_error );
				}
			} else {
				$this->last_error = 'ftp_connect: Transport not FTP for '.$client;
				$this->log_event( __METHOD__.": ".$this->last_error, EXT_ERROR_ERROR);
			}
		} else {
			$this->last_error = 'ftp_connect: Client '.$client.' not found.';
			$this->log_event( __METHOD__.": ".$this->last_error, EXT_ERROR_ERROR);
		}
		
		if( $this->debug ) echo "<p>".__METHOD__.": return ".(is_array($result) ? 'array('.count($result).')' : ($result ? 'true' : 'false') )."</p>";
		return $result;
	}

	//! Browse files in directory
	public function ftp_browse( $client, $url, $path = '/' ) {
		if( $this->profiling ) {
			$this->timer = new sts_timer();
			$this->timer->start();
		}
		if( $this->debug ) echo "<p>".__METHOD__.": Connect to client $client, url $url, path $path</p>";
		$this->log_event( __METHOD__.": Connect to client $client, url $url, path $path", EXT_ERROR_DEBUG);
		$result = '';
		$this->last_error = '';
		$this->ftp_client = $this->database->get_one_row(
			"SELECT * FROM EXP_CARD_FTP
			WHERE FTP_CODE = ".$client );
		
		if( is_array($this->ftp_client) ) {
			if( $this->debug ) echo "<p>".__METHOD__.": Connect to ".$this->ftp_client['FTP_SERVER'].", port ".$this->ftp_client['FTP_PORT']."</p>";
			$this->conn_id = @ftp_connect($this->ftp_client['FTP_SERVER'],
				isset($this->ftp_client['FTP_PORT']) ? $this->ftp_client['FTP_PORT'] : $this->default_ftp_port, $this->connect_timeout );
			
			if( $this->profiling ) {
				$this->timer->stop();
				$this->time_connect = $this->timer->result();
				echo "<p>".__METHOD__.": connect Time: ".$this->timer->result()."</p>";
				ob_flush(); flush();
				$this->timer->start();
			}

			if( $this->conn_id ) {
				// login with username and password
				if( $this->debug ) echo "<p>".__METHOD__.": login ".$this->ftp_client['FTP_USER_NAME'].", ".$this->ftp_client['FTP_USER_PASS']."</p>";
				if( ftp_login($this->conn_id, $this->ftp_client['FTP_USER_NAME'],
					$this->ftp_client['FTP_USER_PASS']) ) {
					
					if( $this->ftp_client['FTP_PASV'] )
						ftp_pasv( $this->conn_id, true );		// enable passive mode
					
					if( $this->profiling ) {
						$this->timer->stop();
						$this->time_login = $this->timer->result();
						echo "<p>".__METHOD__.": login Time: ".$this->timer->result()."</p>";
						ob_flush(); flush();
						$this->timer->start();
					}
						
					// List directory
					$contents = ftp_rawlist($this->conn_id, $path);

					if( $this->profiling ) {
						$this->timer->stop();
						$this->time_list = $this->timer->result();
						echo "<p>".__METHOD__.": list Time: ".$this->timer->result()."</p>";
						ob_flush(); flush();
					}
						
					if( $this->debug ) {
							echo "<p>".__METHOD__.": After ftp_rawlist, contents = </p><pre>";
							var_dump($contents);
							echo "</pre>";
					}

					$dl = isset($this->ftp_client['FTP_DL_PATH']) ? $this->ftp_client['FTP_DL_PATH'] : '';
					if( substr($dl, -1) <> '/' )
						$dl .= '/';
					$ul = isset($this->ftp_client['FTP_UL_PATH']) ? $this->ftp_client['FTP_UL_PATH'] : '';
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
					if( is_array($contents) && count($contents) > 0 ) {
						foreach($contents as $line) {
							if( $line[0] == 'd' ) {
								$arr = explode(' ', $line);
								$last = end($arr);
								$line = '<a class="browse_ftp" href="'.$url.'?CLIENT='.$client.'&CWD='.$path.$last.'/">'.$line.'</a>';
							}
							$result .= $line.'<br>';
						}
					} else {
						$result .= 'We were able to connect to the remote server, but no files are available.<br>
						You might need to enable/disable passive mode<br>';
					}

				} else {
					$this->last_error =  "ftp_connect: Unable to login to ".$this->ftp_client['FTP_SERVER'].
						' with credentials ('.$this->ftp_client['FTP_USER_NAME'].', '.
						$this->ftp_client['FTP_USER_PASS'].')';
					$this->log_event( __METHOD__.": ".$this->last_error, EXT_ERROR_ERROR);
				}
			} else {
				$result = $this->last_error = 'ftp_connect: Unable to connect to <strong>'.$this->ftp_client['FTP_SERVER'].'</strong> port <strong>'.(isset($this->ftp_client['FTP_PORT']) ? $this->ftp_client['FTP_PORT'] : $this->default_ftp_port).'</strong>';
				$this->log_event( __METHOD__.": ".$this->last_error, EXT_ERROR_ERROR);
				$result .= '<br>Please check your settings.<br>
					';
				if( gethostbyname($this->ftp_client['FTP_SERVER']) == $this->ftp_client['FTP_SERVER'])
					$result .= '<br>Exspeedite couldn\'t map the server name <strong>'.$this->ftp_client['FTP_SERVER'].'</strong> to an IP address, which means it probably doesn\'t exist.<br>';
					
				$result .= '<br>To confirm server exists, try <strong>nslookup '.$this->ftp_client['FTP_SERVER'].'</strong><br>
					Make sure the port number is correct, and try enable/disable passive mode.';
			}
		} else {
			$result = $this->last_error = 'ftp_connect: Client '.$client.' not found.';
			$this->log_event( __METHOD__.": ".$this->last_error, EXT_ERROR_ERROR);
			$result .= '<br>Please check your settings and try again.';
		}
		
		if( $this->debug ) echo "<p>".__METHOD__.": return ".($result ? strlen($result)." chars" : "false (".$this->last_error.")" )."</p>";
		return $result;
	}

	//! gets the last modified time for a file
	public function ftp_mdtm( $filename ) {
		if( $this->debug ) echo "<p>".__METHOD__.": $filename</p>";
		$result = false;
		$this->last_error = '';
	   
		// Change to DL directory
		if( $this->debug ) echo "<p>".__METHOD__.": chdir  ".$this->ftp_client['FTP_DL_PATH']."</p>";
		if (ftp_chdir($this->conn_id, $this->ftp_client['FTP_DL_PATH'])) {
		    // Get file from FTP:
		    $result = ftp_mdtm( $this->conn_id, $filename );
		    if( $result > 0 )
		    	$result = date("m/d h:i A", $result );
		    else
		    	$result = false;
		} else { 
		    $this->last_error =  "ftp_get_contents: Couldn't change directory to ".$this->ftp_client['FTP_DL_PATH'];
		}
	    
		if( $this->debug ) echo "<p>".__METHOD__.": return ".($result ? strlen($result)." chars" : "false")."</p>";
	    return $result;
	}

	//! Fetch a file, return it as a string
	public function ftp_get_contents( $filename, $delete = false ) {
		if( $this->debug ) echo "<p>".__METHOD__.": Get $filename, delete=".($delete ? "true" : "false")."</p>";
		$this->log_event( __METHOD__.": Get $filename, delete=".($delete ? "true" : "false"), EXT_ERROR_DEBUG);
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
		        
		        if( $this->debug ) {
			        	echo "<pre>";
			        	var_dump($tempHandle, $result);
			        	echo "</pre>";
		        }
		        if( $result ) {
					$this->log_event( __METHOD__.": Return ".strlen($result)." bytes.", EXT_ERROR_DEBUG);
		        } else {
			        $this->last_error =  __METHOD__.": Couldn't read from temp file.";
			        $this->log_event( $this->last_error, EXT_ERROR_ERROR);
		        }
		        
		        // Delete file
		        if( $result && $delete ) {
			        ftp_delete($this->conn_id, $filename);
		        }
		    } else { 
		    	$this->last_error =  __METHOD__.": Couldn't download ".$filename;
		    	$this->log_event( $this->last_error, EXT_ERROR_ERROR);
		    }
		} else { 
		    $this->last_error =  __METHOD__.": Couldn't change directory to ".$this->ftp_client['FTP_DL_PATH'];
		    $this->log_event( $this->last_error, EXT_ERROR_ERROR);
		}
	    
		if( $this->debug ) echo "<p>".__METHOD__.": return ".($result ? strlen($result)." chars" : "false (".$this->last_error.")" )."</p>";
	    return $result;
	}

	//! SCR# 430 - Fuel card import enhancements
	//! Size of a file
	public function ftp_file_size( $filename ) {
		if( $this->debug ) echo "<p>".__METHOD__.": $filename</p>";
		$result = false;
		if( isset($this->conn_id) && isset($this->ftp_client) &&
			ftp_chdir($this->conn_id, $this->ftp_client['FTP_DL_PATH'])) {
			$result = ftp_size($this->conn_id, $filename);
		}
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
		$this->log_event( __METHOD__.": $filename, return ".($result ? 'true' : 'false'), EXT_ERROR_DEBUG);
	    return $result;
	}

	//! Send a file from a string
	public function ftp_put_contents( $filename, $contents ) {
		if( $this->debug ) echo "<p>".__METHOD__.": Put $filename</p>";
		$this->log_event( __METHOD__.": $filename", EXT_ERROR_DEBUG);
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
				    $this->last_error =  __METHOD__.": Couldn't change directory to ".$this->ftp_client['FTP_UL_PATH'];
				    $this->log_event($this->last_error, EXT_ERROR_ERROR);
				}
			} else { 
			    $this->last_error =  __METHOD__.": unable to write temp file";
				$this->log_event($this->last_error, EXT_ERROR_ERROR);
			}

		} else { 
		    $this->last_error =  __METHOD__.": unable to open temp file";
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

?>