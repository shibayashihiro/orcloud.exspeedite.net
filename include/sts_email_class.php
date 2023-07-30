<?php

// $Id: sts_email_class.php 5146 2023-06-29 19:57:45Z dev $
// Email class - all things to do with email, manifests, invoices, diagnostics

// no direct access
defined('_STS_INCLUDE') or die('Restricted access');
require_once( "sts_setting_class.php" );
require_once( "sts_result_class.php" );

//! Define constants for invoice item types
// Make sure we don't define them twice
if( ! defined('INVOICE_ITEM_DEFAULT') ) {
	define( 'INVOICE_ITEM_DEFAULT', 'default' );
	define( 'INVOICE_ITEM_PALLETS', 'pallets' );
	define( 'INVOICE_ITEM_PALLET_HANDLING', 'pallet handling' );
	define( 'INVOICE_ITEM_HANDLING', 'handling' );
	define( 'INVOICE_ITEM_FRIEGHT', 'freight' );
	define( 'INVOICE_ITEM_EXTRA', 'extra' );
	define( 'INVOICE_ITEM_STOPOFF', 'stop-off' );
	define( 'INVOICE_ITEM_WEEKEND', 'weekend' );
	define( 'INVOICE_ITEM_LOADING', 'loading' );
	define( 'INVOICE_ITEM_UNLOADING', 'unloading' );
	define( 'INVOICE_ITEM_COD', 'COD' );
	define( 'INVOICE_ITEM_MILEAGE', 'mileage' );
	define( 'INVOICE_ITEM_SURCHARGE', 'surcharge' );
	define( 'INVOICE_ITEM_OTHER', 'other' );
	define( 'INVOICE_ITEM_SELECTION', 'selection' );
	define( 'INVOICE_ITEM_DISCOUNT', 'discount' );
}

class sts_email {
	
	//private $debug = false;
	private $cli = false;
	private $sent_count = 0;
	private $log_count = 0;
	public $diagnostic_subject = 'Exspeedite Diagnostic Report';
	public $trace_subject = 'Exspeedite Trace Report For Load# ';

	// Profiling
	private $timer;
	private $time_render;
	
	private $setting;
	private $database;
	
	private $email_log;
	private $multi_currency;
	private $multi_company;
	public $email_crmanifest_cc;
	public $email_drmanifest_cc;
	public $email_manifest_subject;
	public $email_crmanifest_template;
	public $email_drmanifest_template;
	public $email_manifest_sig;
	public $email_manifest_contact;
	public $email_stops_template;

	public $email_delivery_receipt;
	public $email_read_receipt;
	
	private $email_send_invoices;
	public $email_invoice_cc;
	public $email_invoice_subject;
	public $email_invoice_template;
	public $email_invoice_template2;

	public $email_from_name;
	public $email_from_address;
	public $email_diag_address;
	public $email_diag_level;
	public $email_qb_alert;
	public $email_reply_to;
	private $cms_appt_minutes;
	private $email_enabled;
	private $hide_stops_manifest;
	private $hide_shipment_manifest;
	private $billing_currency;
	private $tax;
	private $billing_total;
	private $export_qb;
	private $export_sage50;
	private $sage50;
	private $invoice_terms;
	private $extra_stops;
	private $manifest_hide_repo_stop;
	private $pdf_enabled;
	private $email_links;
	private $email_invoice_override;
	
	public $queue;	//! SCR# 712 - email queuing
	public $pdf_error;
	public $send_error;
		
	public function __construct( $database, $debug = false ) {
		global $sts_crm_dir;
		
		$this->debug = $debug;
		$this->database = $database;
		
		if( $this->debug ) 
			echo "<p>".__METHOD__.": entry, debug = ".($this->debug ? 'true' : 'false')."</p>";

		$this->setting = sts_setting::getInstance( $this->database, $this->debug );
		$this->email_log = $this->setting->get( 'email', 'EMAIL_LOG_FILE' );
		if( isset($this->email_log) && $this->email_log <> '' && 
			$this->email_log[0] <> '/' && $this->email_log[0] <> '\\' 
			&& $this->email_log[1] <> ':' )
			$this->email_log = $sts_crm_dir.$this->email_log;

		$this->multi_currency = $this->setting->get( 'option', 'MULTI_CURRENCY' ) == 'true';
		$this->multi_company = $this->setting->get( 'option', 'MULTI_COMPANY' ) == 'true';
		$this->email_enabled = $this->setting->get( 'email', 'EMAIL_ENABLED' ) == 'true';
		$this->pdf_enabled = $this->setting->get( 'option', 'SEND_PDF_EMAILS' ) == 'true' &&
		! empty($this->setting->get( 'api', 'HTML2PDFROCKET_API_KEY' ));
		
		//! SCR# 459 - send attachments as links
		$this->email_links = $this->setting->get( 'option', 'EMAIL_ATTACHMENT_AS_LINK' ) == 'true';
		
		//! SCR# 200 - Settings to configure rollup of stops
		$this->hide_stops_manifest = $this->setting->get( 'option', 'HIDE_STOPS_MANIFEST' ) == 'true';
		$this->hide_shipment_manifest = $this->setting->get( 'option', 'HIDE_SHIPMENT_MANIFEST' ) == 'true';

		$this->manifest_hide_repo_stop = $this->setting->get( 'option', 'MANIFEST_HIDE_REPO_STOP' ) == 'true';
		$this->email_crmanifest_cc = $this->setting->get( 'email', 'EMAIL_MANIFEST_CC' );
		$this->email_drmanifest_cc = $this->setting->get( 'email', 'EMAIL_DRMANIFEST_CC' );
		$this->email_manifest_subject =	$this->setting->get( 'email', 'EMAIL_MANIFEST_SUBJECT' );
		$this->email_crmanifest_template = $this->setting->get( 'email', 'EMAIL_MANIFEST_TEMPLATE' );
		$this->email_drmanifest_template = $this->setting->get( 'email', 'EMAIL_DRMANIFEST_TEMPLATE' );
		$this->email_manifest_sig = $this->setting->get( 'email', 'EMAIL_MANIFEST_SIG' );
		$this->email_manifest_contact =	$this->setting->get( 'email', 'EMAIL_MANIFEST_CONTACT' );
		$this->email_stops_template =	$this->setting->get( 'option', 'MANIFEST_STOPS' );
		
		//! SCR# 206 - new settings for invoices
		$this->email_send_invoices = $this->setting->get( 'email', 'EMAIL_SEND_INVOICES' ) == 'true';
		$this->email_invoice_cc = $this->setting->get( 'email', 'EMAIL_INVOICE_CC' );
		$this->email_invoice_subject =	$this->setting->get( 'email', 'EMAIL_INVOICE_SUBJECT' );
		$this->email_invoice_template = $this->setting->get( 'email', 'EMAIL_INVOICE_TEMPLATE' );
		$this->email_invoice_template2 = $this->setting->get( 'email', 'EMAIL_INVOICE_TEMPLATE2' );

		$this->email_invoice_override = $this->setting->get( 'email', 'EMAIL_INVOICE_OVERRIDE' );


		$this->email_delivery_receipt = $this->setting->get( 'email', 'REQUEST_DELIVERY_RECEIPT' ) == 'true';
		$this->email_read_receipt = $this->setting->get( 'email', 'REQUEST_READ_RECEIPT' ) == 'true';
		
		$this->export_qb = $this->setting->get( 'api', 'EXPORT_QUICKBOOKS' ) == 'true';
		$this->export_sage50 = $this->setting->get( 'api', 'EXPORT_SAGE50_CSV' ) == 'true';
		$this->invoice_terms = $this->export_qb ?
			$this->setting->get( 'api', 'QUICKBOOKS_INVOICE_TERMS' ) : (
				$this->export_sage50 ? $this->setting->get( 'api', 'SAGE50_INVOICE_TERMS' ) : ''
			);

		$this->email_from_name = $this->setting->get( 'email', 'EMAIL_FROM_NAME' );
		$this->email_from_address = $this->setting->get( 'email', 'EMAIL_FROM_ADDRESS' );
		$this->email_reply_to = $this->setting->get( 'email', 'EMAIL_REPLY_TO' );
		$this->email_diag_address = $this->setting->get( 'email', 'EMAIL_DIAG_ADDRESS' );
		$this->email_diag_level = $this->setting->get( 'email', 'EMAIL_DIAG_LEVEL' );
		$this->email_qb_alert = $this->setting->get( 'email', 'EMAIL_QUICKBOOKS_ALERT' );
		$this->cms_appt_minutes = $this->setting->get( 'option', 'CMS_APPT_MINUTES' );
		$this->extra_stops = $this->setting->get( 'option', 'CLIENT_EXTRA_STOPS' ) == 'true';
		
		$this->queue = sts_email_queue::getInstance( $this->database, $this->debug );
		
		if( $this->debug ) echo "<p>Create sts_email.</p>";
	}

	public function __destruct() {
		if( $this->debug ) echo "<p>Destroy sts_email</p>";
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
    
    public function set_cli() {
		if( $this->debug ) echo "<p>".__METHOD__.": entry</p>";
	    $this->cli = true;
	    return true;
    }
    
    public function enabled() {
		if( $this->debug ) echo "<p>".__METHOD__.": return ".($this->email_enabled ? 'true' : 'false')."</p>";
	    return $this->email_enabled;
    }

    //! SCR# 382 - PDF enabled
    public function pdf_enabled() {
		if( $this->debug ) echo "<p>".__METHOD__.": return ".($this->pdf_enabled ? 'true' : 'false')."</p>";
	    return $this->pdf_enabled;
    }

	//! SCR# 459 - send attachments as links
    public function email_links() {
		if( $this->debug ) echo "<p>".__METHOD__.": return ".($this->email_links ? 'true' : 'false')."</p>";
	    return $this->email_links;
    }

	// $this->email_diag_level will be text, convert to numeric and default to EXT_ERROR_ALL
	public function diag_level() {
	    global $sts_error_level_label;
		$diag_level =  array_search(strtolower($this->email_diag_level),
			$sts_error_level_label);
		if( $diag_level === false )
			$diag_level = EXT_ERROR_ALL;
		return $diag_level;
	}

    public function send_invoices() {
		if( $this->debug ) echo "<p>".__METHOD__.": return ".($this->email_send_invoices ? 'true' : 'false')."</p>";
	    return $this->email_send_invoices;
    }
    
    private function nm2( $x ) {
		return isset($x) ? number_format((float) $x,2):'';
	}

	// From lorenzo-s
	/* http://stackoverflow.com/questions/15025875/what-is-the-best-way-in-php-to-read-last-lines-from-a-file */
	public function tailCustom($filepath, $lines = 1, $adaptive = true) {

		// Open file
		$f = @fopen($filepath, "rb");
		if ($f === false) return false;

		// Sets buffer size
		if (!$adaptive) $buffer = 4096;
		else $buffer = ($lines < 2 ? 64 : ($lines < 10 ? 512 : 4096));

		// Jump to last character
		fseek($f, -1, SEEK_END);

		// Read it and adjust line number if necessary
		// (Otherwise the result would be wrong if file doesn't end with a blank line)
		if (fread($f, 1) != "\n") $lines -= 1;
		
		// Start reading
		$output = '';
		$chunk = '';

		// While we would like more
		while (ftell($f) > 0 && $lines >= 0) {

			// Figure out how far back we should jump
			$seek = min(ftell($f), $buffer);

			// Do the jump (backwards, relative to where we are)
			fseek($f, -$seek, SEEK_CUR);

			// Read a chunk and prepend it to our output
			$output = ($chunk = fread($f, $seek)) . $output;

			// Jump back to where we started reading
			fseek($f, -mb_strlen($chunk, '8bit'), SEEK_CUR);

			// Decrease our line counter
			$lines -= substr_count($chunk, "\n");

		}

		// While we have too many lines
		// (Because of buffer size we might have read too many)
		while ($lines++ < 0) {

			// Find first newline and remove all text before that
			$output = substr($output, strpos($output, "\n") + 1);

		}

		// Close file and return
		fclose($f);
		return trim($output);

	}

	private function generateCallTrace()
	{
	    $e = new Exception();
	    $trace = explode("\n", $e->getTraceAsString());
	    // reverse array to make steps line up chronologically
	    $trace = array_reverse($trace);
	    $length = count($trace);
	    $result = array();
	   
	    for ($i = 0; $i < $length; $i++)
	    {
	        $result[] = ($i + 1)  . ')' . substr($trace[$i], strpos($trace[$i], ' ')); // replace '#someNum' with '$i)', set the right ordering
	    }
	   
	    return "<p>Stack Trace:</p>
		    <ul><li>".implode("</li><li>", $result)."</li></ul>";
	}

	private function log_email( $from, $to, $cc, $subject, $result ) {
	
		if( $this->debug ) echo "<p>log_email: $this->email_log, $from, $to, $subject, ".
			($result ? "success" : "failed ". $this->send_error )."</p>";
		
		if( isset($this->email_log) && $this->email_log <> '' && ! is_dir($this->email_log) ) {
			if(is_writable(dirname($this->email_log)) ) {
				file_put_contents($this->email_log, date('m/d/Y h:i:s A')." pid=".getmypid()." count=".$this->sent_count." log=".(++$this->log_count)." req_time=".$_SERVER["REQUEST_TIME"]." debug=".($this->debug ? "true" : "false")." Subject: ".$subject.
					"\n\tFrom: ".$from.
					"\n\tTo: ".$to.
					"\n\tCc: ".$cc.
					"\n\tResult: ".($result ? "success" : "failed ". $this->send_error )
					//."\n\n".$this->generateCallTrace()
					."\n\n", (file_exists($this->email_log) ? FILE_APPEND : 0) );
			} else {
				if( $this->debug ) echo "<p>log_event: ".dirname($this->email_log)." not writeable</p>";
			}
		}
	}
	
	public function log_email_error( $error ) {
	
		if( $this->debug ) echo "<p>log_email_error: $this->email_log, $error</p>";
		
		if( isset($this->email_log) && $this->email_log <> '' && ! is_dir($this->email_log) ) {
			if(is_writable(dirname($this->email_log)) ) {
			file_put_contents($this->email_log, date('m/d/Y h:i:s A')." pid=".getmypid()." log=".(++$this->log_count)." req_time=".$_SERVER["REQUEST_TIME"]." debug=".($this->debug ? "true" : "false")." Message: ".$error."\n\n", (file_exists($this->email_log) ? FILE_APPEND : 0) );
			} else {
				if( $this->debug ) echo "<p>log_event: ".dirname($this->email_log)." not writeable</p>";
			}
		}
	}
	
	// This is for images not on FilesAnywhere
	private function mime_encocde_image( $boundary, $image_path ) {
		require_once( "sts_attachment_class.php" );
		$attachment_table = sts_attachment::getInstance($this->database, $this->debug);
	
		if( $this->debug ) {
			echo "<p>mime_encocde_image: $image_path</p>";
			//echo "<pre>";
			//var_dump($this->generateCallTrace());
			//echo "</pre>";
		}
		
		$image = basename($image_path);
		$string = file_get_contents($image_path);
		$string = chunk_split(base64_encode($string), 76, "\n");
		
		$type = $attachment_table->mime_type( $image_path );
	
		return '--'.$boundary.'
Content-Type: '.$type.'; x-unix-mode=0644; name="'.$image.'"
Content-Id: <'.$image.'>
Content-Transfer-Encoding: base64
Content-Disposition: inline;  filename="'.$image.'"

'.$string.'
';

	}

	private function mime_encocde_attachment( $boundary, $attachment ) {
		require_once( "sts_attachment_class.php" );
		$attachment_table = sts_attachment::getInstance($this->database, $this->debug);
			
		$image = basename($attachment["FILE_NAME"]);
		$string = $attachment_table->fetch($attachment["ATTACHMENT_CODE"]);
		$string = chunk_split(base64_encode($string), 76, "\n");
		
		$type = $attachment_table->mime_type( $image );
	
		return '--'.$boundary.'
Content-Type: '.$type.'; x-unix-mode=0644; name="'.$image.'"
Content-Transfer-Encoding: base64
Content-Disposition: attachment;  filename="'.$image.'"

'.$string.'
';

	}
	
	//! SCR# 382 - encode PDF content
	private function mime_encocde_pdf( $boundary, $pdf_string, $pdf_file ) {
	
		if( $this->debug ) {
			echo "<p>mime_encocde_pdf: entry</p>";
		}
		
		$string = chunk_split(base64_encode($pdf_string), 76, "\n");
			
		return '--'.$boundary.'
Content-Type: application/pdf; x-unix-mode=0644; name="'.$pdf_file.'.pdf"
Content-Transfer-Encoding: base64
Content-Disposition: attachment;  filename="'.$pdf_file.'.pdf"

'.$string.'
';

	}

	public function mime_encocde_logo( $image_path ) {
		if( $this->debug ) echo "<p>".__METHOD__.": entry, $image_path</p>";

		$value = $image_path;
		if( file_exists($image_path) ) {
			$ext = pathinfo($image_path, PATHINFO_EXTENSION);
			$contents = file_get_contents($image_path);
			$base64   = base64_encode($contents);
			$value = "data:image/".$ext.";base64,".$base64;
		}
		
		if( $this->debug ) echo "<p>".__METHOD__.": return ".($value ? strlen($value).' bytes' : 'false')."</p>";
		return $value;
	}

	//! SCR# 459 - create link to an attachment
	public function link_to_attachment( $name, $code ) {
		global $sts_crm_root;
		$table = new sts_table( DUMMY_DATABASE, "", $this->debug );	// To get at the encryptData method
		
		return '<br><br>Link to attachment <a href="'.$sts_crm_root.'/exp_viewattachment.php?CODE='.$code.
			'&PW='.$table->encryptData($code.'Fuzzy').'">'.$name.'</a>';
	}
	
	//! SCR# 459 - create links to multiple attachments
	public function links_to_attachments( $attachments ) {
		$result = '';

		if( is_array($attachments) && count($attachments) > 0 ) {
			foreach( $attachments as $attachment ) {
				$result .= $this->link_to_attachment( $attachment['FILE_NAME'], $attachment['ATTACHMENT_CODE'] );
			}
		}
		
		return $result;
	}

	//! Send one file attachment plus a message in plain text
	public function send_email_attachment( $from, $to, $cc, $subject, $body, $name, $path, $code ) {
		require_once( "sts_attachment_class.php" );
		$attachment_table = sts_attachment::getInstance($this->database, $this->debug);
				
		//! SCR# 459 - send attachments as links
		if( $this->email_links ) {
			return $this->send_email( $to, $cc, $subject, $body.$this->link_to_attachment( $name, $code ), false, $from );
		}

		if( $this->debug ) echo "<p>".__METHOD__.": ".(++$this->sent_count)." $from, $to, $subject</p>";
		
		//! SCR# 470 - pass from address
		$headers = "From: ".$from."\r\n";
		
		if( !empty($this->email_reply_to) )
			$headers .= "Reply-to: ".$this->email_reply_to."\r\n";
			
		if( !empty($this->email_delivery_receipt) )
			$headers .= "Return-Receipt-To: ".$from."\r\n";
		if( !empty($this->email_read_receipt) )
			$headers .= "Disposition-Notification-To: ".$from."\r\n";

		if( isset($cc) && $cc <> '' )
			$headers .= "Cc: ".$cc."\r\n";

		// Create a Message-ID
		$headers .= "Message-ID:<".strtotime("now")." Exspeedite@".$_SERVER['SERVER_NAME'].">\r\n"; 

		if( $this->debug ) {
			echo "<pre>".__METHOD__.": body1\n";
			var_dump($body);
			echo "</pre>";
		}

		// Generate a boundary string
		$semi_rand = md5(time());
		$mime_boundary = "==Multipart_Boundary_x{$semi_rand}x";
		

		$string = $attachment_table->fetch( $code );
		$string = chunk_split(base64_encode($string), 76, "\n");
		
		$type = $attachment_table->mime_type( str_replace('/', '\\', $path) );
	
		
		// Add the headers for a file attachment
		$headers .= "MIME-Version: 1.0\n" .
"Content-Type: multipart/mixed;\n" .
"\tboundary=\"{$mime_boundary}\"\n" .
"Content-Transfer-Encoding: 7bit";

		// Add a multipart boundary above the plain message
		$message = "This is a multi-part message in MIME format.\n\n" .
"--{$mime_boundary}\n" .
"Content-Type: text/plain; charset=\"iso-8859-1\"\n" .
"Content-Transfer-Encoding: 7bit\n\n" .
$body . "\n\n";

		// Add file attachment to the message
		$message .= '--'.$mime_boundary.'
Content-Type: '.$type.'; x-unix-mode=0644; name="'.$name.'"
Content-Transfer-Encoding: base64
Content-Disposition: attachment;  filename="'.$name.'"

'.$string.'

'.
"--{$mime_boundary}--\n";

		if( $this->debug ) {
			echo "<pre>".__METHOD__.": message\n";
			var_dump($message);
			echo "</pre>";
		}


		if( $this->debug && ! $this->cli ) {
			echo "<p>".__METHOD__.": Debug = Not sending email.</p>";
			$result = false;
			$this->send_error = 'Debug = Not sending email.';
		} else {
			$result = mail($to, $subject, $message, $headers );
			if( ! $result ) {
				$e = error_get_last();
				$this->send_error = isset($e['message']) ? $e['message'] : 'no diagnostics';
			}
		}
		
		if( $this->debug ) echo "<p>".__METHOD__.": ".$this->sent_count." result=".($result ? "success" : "failed ". $this->send_error )."</p>";

		$this->log_email( $from, $to, $cc, $subject, $result);
		
		return $result;
	}


	// May have to offset the time from the difference of the server
	// $sts_crm_timezone = timezone
	// example: DTSTART;TZID=America/New_York:19980119T020000
	public function send_calendar_appt( $to, $subject, $start, $desc, $tz = false ) {
		
		if( $tz != false ) {
			$tz_fmt = ';TZID='.$tz;
			$start_fmt = date('Ymd\THis', strtotime($start));
			$end = strtotime($start) + $this->cms_appt_minutes*60;
			$end_fmt = date('Ymd\THis', $end);
			$now_fmt = date('Ymd\THis');
		} else {
			$tz_fmt = '';
			$start_fmt = gmdate('Ymd\THis\Z', strtotime($start));
			$end = strtotime($start) + $this->cms_appt_minutes*60;
			$end_fmt = gmdate('Ymd\THis\Z', $end);
			$now_fmt = gmdate('Ymd\THis\Z');
		}
		
		$message="

BEGIN:VCALENDAR
VERSION:2.0
CALSCALE:GREGORIAN
METHOD:REQUEST
BEGIN:VEVENT
DTSTART".$tz_fmt.":".$start_fmt."
DTEND".$tz_fmt.":".$end_fmt."
DTSTAMP".$tz_fmt.":".$now_fmt."
ORGANIZER;CN=".$this->email_from_name.":mailto:".$this->email_from_address."
UID:12345678
ATTENDEE;RSVP=TRUE:mailto:".$this->email_from_address."
DESCRIPTION:".$desc."
SEQUENCE:0
STATUS:CONFIRMED
SUMMARY:".$subject."
TRANSP:OPAQUE
BEGIN:VALARM
TRIGGER:-PT10M
DESCRIPTION:".$subject."
ACTION:DISPLAY
END:VALARM
END:VEVENT
END:VCALENDAR";

		/*headers*/
		$headers = "From: ".$this->email_from_name." <".$this->email_from_address .">\n";
		$headers .= "MIME-Version: 1.0\n";
		$headers .= "Content-Type: text/calendar; method=REQUEST;\n";
		$headers .= '        charset="UTF-8"';
		$headers .= "\n";
		$headers .= "Content-Transfer-Encoding: 7bit";
				
		/*mail send*/
		if( $this->debug ) {
			echo "<pre>sts_email > send_email: message\n";
			var_dump($message);
			echo "</pre>";
		}

		if( $this->debug && ! $this->cli ) {
			echo "<p>sts_email > send_email: Debug = Not sending email.</p>";
			$result = false;
		} else {
			$result = mail($to, $subject, $message, $headers );
			if( ! $result ) {
				$e = error_get_last();
				$this->send_error = isset($e['message']) ? $e['message'] : 'no diagnostics';
			}
		}
		
		if( $this->debug ) echo "<p>sts_email > send_email: ".$this->sent_count." result=".($result ? "success" : "failed ". $this->send_error )."</p>";

		$this->log_email( $this->email_from_address, $to, '', $subject, $result);
		
		return $result;
	}
	
	//! SCR# 307 - Rate confirmation email per office
	public function from_office( $load ) {
		require_once( "sts_office_class.php" );
	
		if( $this->multi_company ) {
			$office_table = sts_office::getInstance($this->database, $this->debug);
			list($this->email_from_name, $this->email_from_address) = $office_table->office_from( $load );
		}
	}
	
	//! SCR# 382 - use API from https://www.html2pdfrocket.com/convert-php-to-pdf
	// Returns either a PDF in string or false
	public function convert_html_to_pdf( $html_string, $css = false, $use_landscape = false, $title = 'Email' ) {
		
		$html_string = '<!DOCTYPE html>
<html lang="en">
	<head>
	<meta charset="UTF-8">
	<title>'.$title.'</title>
		'.($css ? $css : '<!-- Latest compiled and minified CSS -->
<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css" integrity="sha384-BVYiiSIFeK1dGmJRAkycuHAHRg32OmUcww7on3RYdg4Va+PmSTsz/K68vbdEjh4u" crossorigin="anonymous">

<!-- Optional theme -->
<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap-theme.min.css" integrity="sha384-rHyoN1iRsVXV4nD0JutlnGaslCJuC7uwjduW9SVrLvRYooPp2bWYgmgJQIXwl/Sp" crossorigin="anonymous">
').'
	</head>
	</body>
	'.$html_string.'
	</body>
</html>';

			if( $this->debug ) {
				echo "<pre>".__METHOD__.": html_string\n";
				var_dump($html_string);
				echo "</pre>";
			}
		
		$api_key = $this->setting->get( 'api', 'HTML2PDFROCKET_API_KEY' );
		$api_url = $this->setting->get( 'api', 'HTML2PDFROCKET_URL' );
		//$api_url = 'https://legacy.html2pdfrocket.com/pdf';
		//$api_url = 'https://api.html2pdfrocket.com/pdf';
		$result = false;
		if( $this->pdf_enabled && ! empty($api_key) ) {
			$postdata = http_build_query(
			    array(
			        'apikey' => $api_key,
			        'value' => $html_string,
			        'PageSize' => 'Letter',
			        'UseLandscape' => $use_landscape ? 'true' : 'false',
			        'FooterFontSize' => '8',
			        'FooterRight' => "\nPage [page] of [toPage]   ",
			    //    'FooterHtml' => '<!DOCTYPE html><center>Page [page] of [toPage]</center>',
			        'MarginBottom' => '15',
			        'MarginTop' => '15'
			    )
			);
			 
			$opts = array('http' =>
			    array(
			        'method'  => 'POST',
			        'header'  => 'Content-type: application/x-www-form-urlencoded',
			        'content' => $postdata
			    )
			);
			
			if( $this->debug ) {
				echo "<pre>".__METHOD__.": opts\n";
				var_dump($opts);
				echo "</pre>";
			//	die;
			}
		
			$attempts = 5;	// Try this 5 times
			do{
				$attempts--;

				try { 
					$context  = stream_context_create($opts);
				 
					// Convert the HTML string to a PDF using those parameters
					$result = file_get_contents($api_url, false, $context);
					if( $result === false ) {
						$error = error_get_last();
						$this->pdf_error = "API returned false. ".$error['message'];
						$this->log_email_error( __METHOD__.": ".$this->pdf_error.
						"\napi_key = ".$api_key." api_url = ".$api_url );
					//	"\nhtml_string = ".$html_string  );
					} else {
						if( ! preg_match("/^%PDF-/", $result) ) {
							$this->log_email_error( __METHOD__.": Error: Invalid PDF returned!" );
							$this->send_alert("Error: Invalid PDF returned from html2pdfrocket.com<br><br>".
				"input first 1024 bytes = <pre>".print_r(substr($html_string, 0, 1024), true)."</pre><br>".
				"output first 1024 bytes = <pre>".print_r(substr($result, 0, 1024), true)."</pre><br>"
							 );
						    $result = false;
						}
					}
				} catch (Exception $e) {
					$this->log_email_error( __METHOD__.": Error: ".$e->getMessage());
					$result = false;
				}
				
				if($result === false) sleep( 300 ); // sleep 5 minutes
				
			} while( $result === false && $attempts > 0 );
		}
		
		return $result;
	}
	
	public function send_email( $to, $cc, $subject, $body, $attachments = false, $from = false ) {
	global $sts_logo_image;
	
	//$this->log_email_error(__METHOD__.": enabled = ".($this->enabled() ? "true" : "false").
	//	" send_invoices = ".($this->send_invoices() ? "true" : "false").
	//	" pdf_enabled = ".($this->pdf_enabled() ? "true" : "false").
	//	" email_links = ".($this->email_links() ? "true" : "false") );
		
		if( ! function_exists('mime_content_type') ) {
			$error =  '<h2>Needed Function Missing: "mime_content_type"</h2>
			<p>You need to have Exspeedite support edit php.ini and uncomment the following line:</p>
			<p>&nbsp;&nbsp;&nbsp;&nbsp;extension=php_fileinfo.dll</p>
			<p>Then restart apache. All should be well after.</p>';
			$this->send_alert($error);
			echo $error;
			die;
		}

		if( $this->debug ) echo "<p>sts_email > send_email: ".(++$this->sent_count)." $this->email_from_name, $this->email_from_address, $to, $subject".
		(is_array($attachments) ? '<br>Attachments: '.print_r($attachments, true) : '')."</p>";
				
		if( $from )
			$headers = "From: ".$from."\r\n";
		else
			$headers = "From: ".str_replace(',','', $this->email_from_name)." <".filter_var($this->email_from_address, FILTER_SANITIZE_EMAIL).">\r\n";

		// Add Reply-to:
		if( !empty($this->email_reply_to) )
			$headers .= "Reply-to: ".$this->email_reply_to."\r\n";
			
		if( !empty($this->email_delivery_receipt) )
			$headers .= "Return-Receipt-To: ".$from."\r\n";
		if( !empty($this->email_read_receipt) )
			$headers .= "Disposition-Notification-To: ".$from."\r\n";

		if( isset($cc) && $cc <> '' )
			$headers .= "Cc: ".$cc."\r\n";
		
		// Create a Message-ID
		$headers .= "Message-ID:<".strtotime("now")." Exspeedite@".$_SERVER['SERVER_NAME'].">\r\n"; 

		if( $this->debug ) {
			echo "<pre>sts_email > send_email: headers\n";
			var_dump($headers);
			echo "</pre>";
		}

		//! If body not wrapped in <html> then wrap it
		if( ! preg_match("/\<html/", $body ) )
			$body = '<html>
			<body>
			' . $body . '
			</body>
			</html>';
			
		if( $this->debug ) {
			echo "<pre>sts_email > send_email: body1\n";
			var_dump($body);
			echo "</pre>";
		}

		// Wrap lines to reasonable length
		$body = wordwrap($body, 200);
		if( $this->debug ) {
			echo "<pre>sts_email > send_email: body2\n";
			var_dump($body);
			echo "</pre>";
		}

		
		//! Replace image paths with cid: references
		$images = array();
		$new_body = '';
		while( preg_match("/(.*?)(<img [^\>]*\>)(.*)/s", $body, $match) ) {

			$before = $match[1];
			$current = $match[2];
			$after = $match[3];

			if( preg_match('/src=\"([^\"]*)\"/', $current, $match2) ) {
				
				$search = $match2[1];
				$replace = "cid:".basename($match2[1]);
				$images[] = $match2[1];
				$current = str_replace($search, $replace, $current );
			}
			$new_body .= $before.$current;
			$body = $after;

		}
		$body = $new_body == '' ? $body : $new_body .= $after;
		$images = array_unique( $images );
	
		// Add the headers for a file attachment
		$headers .= "MIME-Version: 1.0\n" .
"Content-Type: multipart/alternative;\n" .
"\tboundary=\"=StrongTowerInc1\"\n" .
"Content-Transfer-Encoding: 7bit";

		// Add file attachment to the message
		$message = "
--=StrongTowerInc1
Content-Type: multipart/related;
	type=\"text/plain\";
	boundary=\"=StrongTowerInc3\"

--=StrongTowerInc3
Content-Type: text/plain; charset=\"us-ascii\"
Content-Transfer-Encoding: 7bit

View HTML version

--=StrongTowerInc3--

--=StrongTowerInc1
Content-Type: multipart/related;
	type=\"text/html\";
	boundary=\"=StrongTowerInc2\"

--=StrongTowerInc2
Content-Type: text/html; charset=\"us-ascii\"
Content-Transfer-Encoding: 7bit

".$body."
";
		if( count($images) > 0 ) {
			foreach( $images as $image_path ) {
				$message .= $this->mime_encocde_image( '=StrongTowerInc2', $image_path );
			}
		}

		//! SCR# 208 - Add attachments
		if( $this->debug ) echo "<h2>".__METHOD__.": xyzzy - Add attachments</h2>";
		if( is_array($attachments) && count($attachments) > 0 ) {
			foreach( $attachments as $attachment ) {
				$message .= $this->mime_encocde_attachment( '=StrongTowerInc2', $attachment );
			}
		}

		$message .= '
--=StrongTowerInc2--
';


		$message .= "
--=StrongTowerInc1--
";
		//! SCR# 490 - escape dots
		$message = str_replace( "\r\n.", "\r\n..", $message );
			
		if( $this->debug ) {
			echo "<pre>sts_email > send_email: message\n";
			echo htmlspecialchars($message);
			echo "</pre>";
		}


		if( $this->debug && ! $this->cli ) {
			echo "<p>sts_email > send_email: Debug = Not sending email.</p>";
			$result = false;
			$this->send_error = 'Debug = Not sending email.';
		} else {
			$result = mail($to, $subject, $message, $headers );
			if( ! $result ) {
				$e = error_get_last();
				$this->send_error = isset($e['message']) ? $e['message'] : 'no diagnostics';
			}
		}
		
		if( $this->debug ) echo "<p>sts_email > send_email: ".$this->sent_count." result=".($result ? "success" : "failed ". $this->send_error )."</p>";

		$this->log_email( $from? $from : $this->email_from_address, $to, $cc, $subject, $result);
		
		$this->log_email_error( __METHOD__.": return result = ".($result ? "true" : "false") );
		return $result;
	}
	
	public function send_from() {
		return str_replace(',','', $this->email_from_name)." <".filter_var($this->email_from_address, FILTER_SANITIZE_EMAIL).">";
	}
	
	//! SCR# 382 - Send email with no body, just attachments
	public function send_email_nobody( $to, $cc, $subject, $attachments = false, $pdf = false, $pdf_file = false ) {
		global $sts_logo_image;
		$this->send_error = '';
	
		if( ! function_exists('mime_content_type') ) {
			$error =  '<h2>Needed Function Missing: "mime_content_type"</h2>
			<p>You need to have Exspeedite support edit php.ini and uncomment the following line:</p>
			<p>&nbsp;&nbsp;&nbsp;&nbsp;extension=php_fileinfo.dll</p>
			<p>Then restart apache. All should be well after.</p>';
			$this->send_alert($error);
			echo $error;
			die;
		}

		if( $this->debug ) echo "<p>".__METHOD__.": ".(++$this->sent_count)." $this->email_from_name, $this->email_from_address, $to, $subject".
		(is_array($attachments) ? '<br>Attachments: '.implode('<br>', $attachments) : '')."</p>";
				
		$headers = "From: ".str_replace(',','', $this->email_from_name)." <".filter_var($this->email_from_address, FILTER_SANITIZE_EMAIL).">\r\n";

		// Add Reply-to:
		if( !empty($this->email_reply_to) )
			$headers .= "Reply-to: ".$this->email_reply_to."\r\n";
			
		if( !empty($this->email_delivery_receipt) )
			$headers .= "Return-Receipt-To: ".filter_var($this->email_from_address, FILTER_SANITIZE_EMAIL)."\r\n";
		if( !empty($this->email_read_receipt) )
			$headers .= "Disposition-Notification-To: ".filter_var($this->email_from_address, FILTER_SANITIZE_EMAIL)."\r\n";

		if( isset($cc) && $cc <> '' )
			$headers .= "Cc: ".$cc."\r\n";
		
		// Create a Message-ID
		$headers .= "Message-ID:<".strtotime("now")." Exspeedite@".$_SERVER['SERVER_NAME'].">\r\n"; 

	
		// Add the headers for a file attachment
		$headers .= "MIME-Version: 1.0\n" .
"Content-Type: multipart/mixed;\n" .
"\tboundary=\"=StrongTowerInc1\"\n" .
"Content-Transfer-Encoding: 7bit";

		$message = "";

		//! SCR# 382 - Add PDF
		if( $this->pdf_enabled && $pdf ) {
			$message .= $this->mime_encocde_pdf( '=StrongTowerInc1', $pdf, $pdf_file );
		}
		
		//! SCR# 208 - Add attachments
		if( $this->debug ) echo "<h2>".__METHOD__.": xyzzy - Add attachments</h2>";
		if( is_array($attachments) && count($attachments) > 0 ) {
			foreach( $attachments as $attachment ) {
				$message .= $this->mime_encocde_attachment( '=StrongTowerInc1', $attachment );
			}
		}

		$message .= "
--=StrongTowerInc1--
";
	
		if( $this->debug ) {
			echo "<pre>".__METHOD__.": message\n";
			echo htmlspecialchars($message);
			echo "</pre>";
		}


		if( $this->debug && ! $this->cli ) {
			echo "<p>".__METHOD__.": Debug = Not sending email.</p>";
			$result = false;
			$this->send_error = 'Debug = Not sending email.';
		} else {
	
			$result = mail($to, $subject, $message, $headers );
			if( ! $result ) {
				$e = error_get_last();
				$this->send_error = isset($e['message']) ? $e['message'] : 'no diagnostics';
			}
		}
		
		if( $this->debug ) echo "<p>".__METHOD__.": ".$this->sent_count." result=".($result ? "success" : "failed ". $this->send_error )."</p>";

		$this->log_email( $this->email_from_address, $to, $cc, $subject, $result);
		
		$this->log_email_error( __METHOD__.": return result = ".($result ? "true" : "false") );
		return $result;
	}
	
	public function reference( $code, $type = 'load', $show_cons = false ) {
		global $sts_crm_dir;
		require_once( "sts_shipment_class.php" );
		
		$cons = false;
		$output = $code;
		$shipment_table = sts_shipment::getInstance($this->database, $this->debug, $this->cli);
		
		if( $type == 'load' ) {
			$refs = $shipment_table->fetch_rows("LOAD_CODE = ".$code.
				" AND COALESCE(SS_NUMBER,'') <> ''", 
				"MIN(SHIPMENT_CODE) AS MS, SS_NUMBER");
		} else {
			$refs = $shipment_table->fetch_rows("SHIPMENT_CODE = ".$code.
				" AND COALESCE(SS_NUMBER,'') <> ''", "SS_NUMBER");
			$cons = $shipment_table->fetch_rows("CONSOLIDATE_NUM = ".$code,
				"COUNT(*) AS NUM_CONS");
		}
		
		if( is_array($refs) && count($refs) > 0 &&
			! empty($refs[0]["SS_NUMBER"])) {
			$output = $refs[0]["SS_NUMBER"];
			if( $show_cons && is_array($cons) && count($cons) > 0 &&
				! empty($cons[0]["NUM_CONS"]) && $cons[0]["NUM_CONS"] > 0)
				$output .= '<br>(CONSOLIDATED BILL)';
		}
		return $output;
	}
	
	private function carrier_addr( $load ) {
		$output = '';
		$addr = $this->database->get_multiple_rows(
			"SELECT ADDRESS, ADDRESS2, CITY, STATE, ZIP_CODE, COUNTRY
			FROM EXP_CONTACT_INFO, EXP_LOAD
			WHERE CONTACT_CODE = CARRIER AND CONTACT_SOURCE = 'carrier'
			AND CONTACT_TYPE in ('company', 'carrier')
			AND EXP_CONTACT_INFO.ISDELETED = false
			AND CARRIER > 0
			AND LOAD_CODE = $load
			LIMIT 1");
		if( is_array($addr) && count($addr) == 1 ) {
			$a = $addr[0];
			
			$output = (empty($a["ADDRESS"]) ? '' : $a["ADDRESS"].'<br>').
				(empty($a["ADDRESS2"]) ? '' : $a["ADDRESS2"].'<br>').
				(empty($a["CITY"]) ? '' : $a["CITY"].', ').
				(empty($a["STATE"]) ? '' : $a["STATE"].', ').
				(empty($a["ZIP_CODE"]) ? '' : $a["ZIP_CODE"]).', '.
				(empty($a["COUNTRY"]) ? '' : $a["COUNTRY"]);
		}
		return $output;
	}
	
	private function carrier_phone( $load ) {
		$output = '';
		$addr = $this->database->get_multiple_rows(
			"SELECT PHONE_OFFICE
			FROM EXP_CONTACT_INFO, EXP_LOAD
			WHERE CONTACT_CODE = CARRIER AND CONTACT_SOURCE = 'carrier'
			AND CONTACT_TYPE in ('company', 'carrier')
			AND EXP_CONTACT_INFO.ISDELETED = false
			AND CARRIER > 0
			AND LOAD_CODE = $load
			LIMIT 1");
		if( is_array($addr) && count($addr) == 1 ) {
			$a = $addr[0];
			
			$output = (empty($a["PHONE_OFFICE"]) ? '' : $a["PHONE_OFFICE"]);
		}
		return $output;
	}
	
	private function carrier_cell( $load ) {
		$output = '';
		$addr = $this->database->get_multiple_rows(
			"SELECT PHONE_CELL
			FROM EXP_CONTACT_INFO, EXP_LOAD
			WHERE CONTACT_CODE = CARRIER AND CONTACT_SOURCE = 'carrier'
			AND CONTACT_TYPE in ('company', 'carrier')
			AND EXP_CONTACT_INFO.ISDELETED = false
			AND CARRIER > 0
			AND LOAD_CODE = $load
			LIMIT 1");
		if( is_array($addr) && count($addr) == 1 ) {
			$a = $addr[0];
			
			$output = (empty($a["PHONE_CELL"]) ? '' : $a["PHONE_CELL"]);
		}
		return $output;
	}
	
	private function driver_cell( $load ) {
		$output = '';
		$addr = $this->database->get_multiple_rows(
			"SELECT PHONE_CELL
			FROM EXP_CONTACT_INFO, EXP_LOAD
			WHERE CONTACT_CODE = DRIVER AND CONTACT_SOURCE = 'driver'
			AND EXP_CONTACT_INFO.ISDELETED = false
			AND DRIVER > 0
			AND LOAD_CODE = $load
			LIMIT 1");
		if( is_array($addr) && count($addr) == 1 ) {
			$a = $addr[0];
			
			$output = (empty($a["PHONE_CELL"]) ? '' : $a["PHONE_CELL"]);
		}
		return $output;
	}
	
	private function carrier_fax( $load ) {
		$output = '';
		$addr = $this->database->get_multiple_rows(
			"SELECT COALESCE(PHONE_FAX, FAX_NOTIFY) AS FAX
			FROM EXP_CONTACT_INFO, EXP_CARRIER, EXP_LOAD
			WHERE CONTACT_CODE = CARRIER AND CONTACT_SOURCE = 'carrier'
			AND CONTACT_TYPE in ('company', 'carrier')
			AND EXP_CONTACT_INFO.ISDELETED = false
			AND CARRIER > 0
			AND LOAD_CODE = $load
			AND CARRIER_CODE = CARRIER
			LIMIT 1");
		if( is_array($addr) && count($addr) == 1 ) {
			$a = $addr[0];
			
			$output = (empty($a["FAX"]) ? '' : $a["FAX"]);
		}
		return $output;
	}
	
	private function carrier_email( $load ) {
		$output = '';
		$addr = $this->database->get_multiple_rows(
			"SELECT COALESCE(EMAIL, EMAIL_NOTIFY) AS EMAIL
			FROM EXP_CONTACT_INFO, EXP_CARRIER, EXP_LOAD
			WHERE CONTACT_CODE = CARRIER AND CONTACT_SOURCE = 'carrier'
			AND CONTACT_TYPE in ('company', 'carrier')
			AND EXP_CONTACT_INFO.ISDELETED = false
			AND CARRIER > 0
			AND LOAD_CODE = $load
			AND CARRIER_CODE = CARRIER
			LIMIT 1");
		if( is_array($addr) && count($addr) == 1 ) {
			$a = $addr[0];
			
			$output = (empty($a["EMAIL"]) ? '' : $a["EMAIL"]);
		}
		return $output;
	}
	
	private function shipment_notes( $load ) {
		global $sts_crm_dir;
		require_once( "sts_shipment_class.php" );

		$output = '';
		$shipment_table = sts_shipment::getInstance($this->database, $this->debug, $this->cli);
		
		$notes = $shipment_table->fetch_rows("LOAD_CODE = ".$load, 
			"SHIPMENT_CODE, SS_NUMBER, NOTES");
		if( is_array($notes) && count($notes) > 0 ) {
			foreach( $notes as $row ) {
				if( ! empty($row["NOTES"])) {
					$output.= '<br><br><strong>Notes for shipment '.$row["SHIPMENT_CODE"].
					($this->multi_company && ! empty($row["SS_NUMBER"]) ? ' / '.$row["SS_NUMBER"] : '').
					'</strong><br><br>'.str_replace("\n", "<br>", str_replace('$', '\$', $row["NOTES"])) .'<br>';
				}
			}
		}
		return $output;
	}
	
	private function broker( $code, $type = 'load' ) {
		global $sts_crm_dir;
		require_once( "sts_shipment_class.php" );

		$output = '';
		$shipment_table = sts_shipment::getInstance($this->database, $this->debug, $this->cli);
		
		$brokers = $shipment_table->fetch_rows(($type = 'load' ? "LOAD_CODE" : "SHIPMENT_CODE")." = ".$code, 
			"SHIPMENT_CODE, SS_NUMBER,
			BROKER_NAME,BROKER_ADDR1,BROKER_ADDR2,BROKER_STATE,
			BROKER_CITY,BROKER_ZIP,BROKER_PHONE,BROKER_COUNTRY,
			BROKER_EXT,BROKER_FAX,BROKER_CELL,BROKER_CONTACT,
			BROKER_EMAIL");
			
		if( is_array($brokers) && count($brokers) > 0 ) {
			//! SCR# 203 - keep a list of brokers to avoid duplicates
			$prev_broker_name = array();
			$prev_broker_zip = array();
			foreach( $brokers as $a ) {
				if( ! empty($a["BROKER_NAME"]) &&
					! in_array($a["BROKER_NAME"], $prev_broker_name) &&
					! in_array($a["BROKER_ZIP"], $prev_broker_zip)) {
					$output.= '<br>
					<table width="100%" align="center" border="0" cellspacing="0">
					<tbody>
					<tr valign="top"><td width="50%">
					<strong>Customs Broker: '.$a["BROKER_NAME"].'</strong>
					<br>'.
					(empty($a["BROKER_ADDR1"]) ? '' : $a["BROKER_ADDR1"].'<br>').
					(empty($a["BROKER_ADDR2"]) ? '' : $a["BROKER_ADDR2"].'<br>').
					(empty($a["BROKER_CITY"]) ? '' : $a["BROKER_CITY"].', ').
					(empty($a["BROKER_STATE"]) ? '' : $a["BROKER_STATE"].', ').
					(empty($a["BROKER_ZIP"]) ? '' : $a["BROKER_ZIP"]).', '.
					(empty($a["BROKER_COUNTRY"]) ? '' : $a["BROKER_COUNTRY"]).'</td>
					<td  width="50%">'.
					
					(empty($a["BROKER_CONTACT"]) ? '' : '<strong>Contact:</strong> '.$a["BROKER_CONTACT"].'<br>').
					(empty($a["BROKER_PHONE"]) ? '' : '<strong>Phone:</strong> '.$a["BROKER_PHONE"].
						(empty($a["BROKER_EXT"]) ? '' : ' x '.$a["BROKER_EXT"]).'<br>'
					).
					(empty($a["BROKER_FAX"]) ? '' : '<strong>Fax:</strong> '.$a["BROKER_FAX"].'<br>').
					(empty($a["BROKER_EMAIL"]) ? '' : '<strong>Email:</strong> '.$a["BROKER_EMAIL"]).
					'</td></tr></tbody></table>'
					;
					$prev_broker_name[] = $a["BROKER_NAME"];
					$prev_broker_zip[] = $a["BROKER_ZIP"];
				}
			}
		}
		if( $this->debug ) echo "<p>".__METHOD__.": output = ".$output."</p>";
		return $output;
	}
	
	//! SCR# 761 - Find out if the load is HAZMAT
	private function load_hazmat( $load ) {
		require_once( "sts_shipment_class.php" );
		$shipment_table = sts_shipment::getInstance($this->database, $this->debug, $this->cli);
		$out = '';

		$check = $shipment_table->fetch_rows("LOAD_CODE = $load AND DANGEROUS_GOODS = TRUE", "DANGEROUS_GOODS, SHIPMENT_CODE, SS_NUMBER");
		
		if( is_array($check) && count($check) > 0 ) {
			$shipments = [];
			foreach( $check as $row ) {
				$shipments[] = $row["SHIPMENT_CODE"].(isset($row["SS_NUMBER"]) ? ' / '.$row["SS_NUMBER"] : '');
			}
			
			$out = '<strong>**HAZMAT** - Shipments: '.implode(', ', $shipments).'</strong><br><br>';
		}
		
		return  $out;
	}
	
	//! Prepare a load confirmation message to email
	// Look for $sts_email_carrier_manifest in sts_load_class.php
	// Look for $sts_email_carrier_stops in sts_stop_class.php
	// Look for $sts_email_manifest_boilerplate in this file at the bottom
	//
	public function load_confirmation( $load, $target = 'carrier', $base = 0, $note = '', $inline_logo = false ) {
		global $sts_crm_dir, $sts_email_driver_manifest, $sts_email_carrier_manifest, $sts_email_carrier_stops;
		require_once( "sts_load_class.php" );
		require_once( "sts_stop_class.php" );
		require_once( "sts_company_class.php" );
		require_once( "sts_office_class.php" );
			
		$company_table = sts_company::getInstance($this->database, $this->debug);
		$office_table = sts_office::getInstance($this->database, $this->debug);
		
		if( $target == 'driver' ) {
			$template_file = $this->email_drmanifest_template;
		} else {
			$template_file = $this->email_crmanifest_template;
		}
		
		if( $this->debug ) echo "<p>".__METHOD__.": template = ".$template_file."</p>";
		if( file_exists($template_file))
			$template = file_get_contents($template_file);
		else if( file_exists( dirname(__FILE__).DIRECTORY_SEPARATOR.$template_file ) )
			$template = file_get_contents(dirname(__FILE__).DIRECTORY_SEPARATOR.$template_file);
		else
			$template = 'MISSING TEMPLATE FILE '.$template_file;
		

		if( file_exists($this->email_stops_template) )
			require_once($this->email_stops_template);
		else if( file_exists( dirname(__FILE__).DIRECTORY_SEPARATOR.$this->email_stops_template ) )
			require_once(dirname(__FILE__).DIRECTORY_SEPARATOR.$this->email_stops_template);

		if( $this->debug ) {
			echo "<p>emc1</p><pre>";
			var_dump($template);
			echo "</pre>";
		}

		$company_name = $company_table->company_name( $load );
		if( is_string($company_name) && $company_name <> '' ) {
			$template = preg_replace('/\%COMPANY_NAME\%/', $company_name, $template);
		}

		$company_logo = $company_table->company_logo( $load, 'load' );
		if( $this->debug ) echo "<p>".__METHOD__.": company_logo = ".$company_logo."</p>";
		if( $inline_logo && ctype_alpha(substr($company_logo, 0, 1)) && substr($company_logo, 1, 2) == ':\\')
			$company_logo = $this->mime_encocde_logo( $company_logo );
			
		if( is_string($company_logo) && ! empty($company_logo) ) {
			$template = str_replace('%COMPANY_LOGO%', $company_logo, $template);
		} else
			$template = preg_replace('/<img src="\%COMPANY_LOGO\%" align="left">/', '', $template, 1);


		$office_phone = $office_table->office_phone( $load );
		if( is_string($office_phone) && ! empty($office_phone) ) {
			$template = preg_replace('/\%OFFICE_PHONE\%/', $office_phone, $template);
		}

		$emergency_phone = $office_table->emergency_phone( $load );
		if( is_string($emergency_phone) && ! empty($emergency_phone) ) {
			$template = preg_replace('/\%EMERGENCY_PHONE\%/', $emergency_phone, $template);
		}

		$office_email = $office_table->office_email( $load );
		if( is_string($office_email) && ! empty($office_email) ) {
			$template = preg_replace('/\%OFFICE_EMAIL\%/', $office_email, $template);
		}

		$current_date = date("m/d/Y");
		if( is_string($current_date) && ! empty($current_date) ) {
			$template = preg_replace('/\%CURRENT_DATE\%/', $current_date, $template);
		}

		$company_addr = $company_table->company_address( $load );
		if( is_string($company_addr) && ! empty($company_addr) ) {
			$template = preg_replace('/\%COMPANY_ADDR\%/', $company_addr, $template);
		}
		
		$company_phone = $company_table->company_phone( $load );
		if( is_string($company_phone) && ! empty($company_phone) ) {
			$template = preg_replace('/\%COMPANY_PHONE\%/', $company_phone, $template);
		}
		
		$company_email = $company_table->company_email( $load );
		if( is_string($company_email) && ! empty($company_email) ) {
			$template = preg_replace('/\%COMPANY_EMAIL\%/', $company_email, $template);
		}
		
		$broker = $this->broker( $load );
		if( is_string($broker) && ! empty($broker) ) {
			$template = preg_replace('/\%CUSTOMS_BROKER\%/', $broker, $template);
		} else
			$template = preg_replace('/\%CUSTOMS_BROKER\%/', '', $template);

		
		$our_ref = $this->multi_company ? $this->reference( $load ) : $load;
		if( ! empty($our_ref) ) {
			$template = preg_replace('/\%LOAD_CODE\%/', $our_ref, $template);
		}
		
		$carrier_addr = $this->carrier_addr( $load );
		if( ! empty($carrier_addr) ) {
			$template = preg_replace('/\%CARRIER_ADDR\%/', $carrier_addr, $template);
		}

		$carrier_phone = $this->carrier_phone( $load );
		$template = preg_replace('/\%CARRIER_PHONE\%/', $carrier_phone, $template);

		//! SCR# 390 add CARRIER_CELL, DRIVER_CELL
		$carrier_cell = $this->carrier_cell( $load );
		$template = preg_replace('/\%CARRIER_CELL\%/', $carrier_cell, $template);

		$driver_cell = $this->driver_cell( $load );
		$template = preg_replace('/\%DRIVER_CELL\%/', $driver_cell, $template);

		$carrier_fax = $this->carrier_fax( $load );
		$template = preg_replace('/\%CARRIER_FAX\%/', $carrier_fax, $template);

		$carrier_email = $this->carrier_email( $load );
		$template = preg_replace('/\%CARRIER_EMAIL\%/', $carrier_email, $template);

		$company_fax = $company_table->company_fax( $load );
		$template = preg_replace('/\%COMPANY_FAX\%/', 
			($company_fax <> '' ? $company_fax : '(999) 999-9999'), $template);

		$office_fax = $office_table->office_fax( $load );
		if( ! empty($office_fax) )
			$template = preg_replace('/\%OFFICE_FAX\%/', $office_fax, $template);

		if( ! empty($this->email_manifest_sig) )
			$template = preg_replace('/\%OUR_SIG\%/', $this->email_manifest_sig, $template);
		else
			$template = preg_replace('/<img src="\%OUR_SIG\%">/', $this->email_manifest_sig, $template, 1);

		if( ! empty($this->email_manifest_contact) )
			$template = preg_replace('/\%OUR_CONTACT\%/', $this->email_manifest_contact, $template, 1);

		$load_table = sts_load::getInstance($this->database, $this->debug);
		
		//! SCR# 702 - Add required equipment
		$equipment = $load_table->get_equipment_req( $load, true );
		if( ! empty( $equipment ) )
			$template = preg_replace('/\%EQUIPMENT\%/', $equipment, $template);
		else
			$template = preg_replace('/\%EQUIPMENT\%/', '', $template);

		//! SCR# 285 - Add TRACTOR_UNIT, TRAILER_UNIT
		if( $target == 'driver' ) {
			$names = $load_table->fetch_rows("LOAD_CODE = ".$load,
			"COALESCE((SELECT UNIT_NUMBER
				FROM EXP_TRACTOR
				WHERE TRACTOR_CODE = TRACTOR LIMIT 1), 'none') AS TRACTOR_UNIT,
			COALESCE(
				(SELECT DISTINCT T.UNIT_NUMBER
				FROM EXP_TRAILER T, EXP_STOP S WHERE T.TRAILER_CODE = S.TRAILER 
				AND S.LOAD_CODE = EXP_LOAD.LOAD_CODE
				AND S.TRAILER IS NOT NULL
				LIMIT 1),
				(SELECT UNIT_NUMBER
				FROM EXP_TRAILER WHERE TRAILER_CODE = TRAILER LIMIT 1), 'none') AS TRAILER_UNIT" );
			
			if( is_array($names) && count($names) == 1) {
				if( !empty($names[0]["TRACTOR_UNIT"])) {
					$template = preg_replace('/\%TRACTOR_UNIT\%/', $names[0]["TRACTOR_UNIT"], $template, 1);
				}
				if( !empty($names[0]["TRAILER_UNIT"])) {
					$template = preg_replace('/\%TRAILER_UNIT\%/', $names[0]["TRAILER_UNIT"], $template, 1);
				}
			}	
		}	
		
		if( $this->debug ) {
			echo "<p>emc3</p><pre>";
			var_dump($template);
			echo "</pre>";
		}

		$output = $template;
		$load_table2 = new sts_load_manifest($this->database, $this->debug);

		if( $this->debug ) {
			echo "<p>output1</p><pre>";
			var_dump($output);
			echo "</pre>";
		}

		$stop_table2 = new sts_stop_carrier($this->database, $this->debug);
		
		$stops_template = isset($sts_email_carrier_stops) ? $sts_email_carrier_stops : $stop_table2->carrier_stops_template();
		
		$stops_content = $this->render_html_message( $stop_table2, "LOAD_CODE = ".$load.
			($this->manifest_hide_repo_stop ? " AND NOT (STOP_TYPE='stop' AND SEQUENCE_NO = 1 AND COALESCE(SHIPMENT, 0) > 0 AND STOP_COMMENT IS NULL)" : ""),
				$stops_template);
				
		$stops_content .= '<table class="noborder">
	<tr>
		<td>
';
		//! SCR# 761 - Find out if the load is HAZMAT
		$snotes = (isset($note) && trim($note) <> '' ? trim(str_replace('$', '\$', $note)) : '').$this->shipment_notes( $load );
		if( $snotes <> '' )
			$stops_content .= '<br><table class="border">
	<tr>
		<td><p><strong>'.($target=='carrier' ? 'Carrier' : 'Driver').' Notes:</strong></p>
		<p>'.$this->load_hazmat( $load ).$snotes.'</p>
		</td>
		</tr>
		</table>
';

		$output = preg_replace('/\%STOPS_GO_HERE\%/', $stops_content, $output, 1);
		if( $this->debug ) {
			echo "<p>output2</p><pre>";
			var_dump($output);
			echo "</pre>";
		}

		//! SCR# 449 - enhancements to carrier manifest - CARRIER_TOTAL
		$result = $load_table2->fetch_rows("LOAD_CODE = ".$load,
			'DRIVER_NAME, CARRIER_NAME, CARRIER_TOTAL,
			CARRIER_BASE, CARRIER_FSC, CARRIER_HANDLING, CARRIER_TOTAL,
			CURRENCY, (SELECT ITEM FROM EXP_ITEM_LIST WHERE ITEM_CODE = TERMS) AS TERMS');
		if( $this->debug ) {
			echo "<pre>result, base";
			var_dump($result, $base);
			echo "</pre>";
		}

		if( $target == 'driver' ) {
			$output = preg_replace('/\%DRIVER_NAME\%/', $result[0]['DRIVER_NAME'], $output);
		} else {
			$output = preg_replace('/\%CARRIER_NAME\%/', $result[0]['CARRIER_NAME'], $output);
			$output = preg_replace('/\%CARRIER_PAY\%/', number_format((float) $result[0]['CARRIER_TOTAL'],2), $output);

			//! SCR# 449 - enhancements to carrier manifest - CARRIER_TOTAL
			$output = preg_replace('/\%CARRIER_BASE\%/', number_format((float) $result[0]['CARRIER_BASE'],2), $output);
			$output = preg_replace('/\%CARRIER_FSC\%/', number_format((float) $result[0]['CARRIER_FSC'],2), $output);
			$output = preg_replace('/\%CARRIER_HANDLING\%/', number_format((float) $result[0]['CARRIER_HANDLING'],2), $output);
			$output = preg_replace('/\%CARRIER_TOTAL\%/', number_format((float) $result[0]['CARRIER_TOTAL'],2), $output);

			$output = preg_replace('/\%CURRENCY\%/', $result[0]['CURRENCY'], $output);
			$output = preg_replace('/\%TERMS\%/', $result[0]['TERMS'], $output);
		}
		

		if( $this->debug ) {
			echo "<p>output3</p><pre>";
			var_dump($output);
			echo "</pre>";
		}

		return $output;
	}
	
	private function remitto_addr() {
		$output = '';
		$name = $this->setting->get( 'company', 'REMIT_NAME' );
		$a1 = $this->setting->get( 'company', 'REMIT_ADDRESS_1' );
		$a2 = $this->setting->get( 'company', 'REMIT_ADDRESS_2' );
		$c = $this->setting->get( 'company', 'REMIT_CITY' );
		$s = $this->setting->get( 'company', 'REMIT_STATE' );
		$z = $this->setting->get( 'company', 'REMIT_ZIP' );
		$cn = $this->setting->get( 'company', 'REMIT_COUNTRY' );
		
		$output = (empty($name) ? '' : $name.'<br>').
			(empty($a["BILLTO_ADDR1"]) ? '' : $a["BILLTO_ADDR1"].'<br>').
			(empty($a1) ? '' : $a1.'<br>').
			(empty($a2) ? '' : $a2.'<br>').
			(empty($c) ? '' : $c.', ').
			(empty($s) ? '' : $s.', ').
			(empty($z) ? '' : $z).', '.
			(empty($cn) ? '' : $cn);

		return $output;
	}
	
	private function billto_addr( $shipment ) {
		$output = '';
		$addr = $this->database->get_multiple_rows(
			"SELECT BILLTO_NAME, BILLTO_ADDR1, BILLTO_ADDR2, BILLTO_CITY, BILLTO_STATE,
			BILLTO_ZIP, BILLTO_COUNTRY
			FROM EXP_SHIPMENT
			WHERE SHIPMENT_CODE = $shipment
			LIMIT 1");
		if( is_array($addr) && count($addr) == 1 ) {
			$a = $addr[0];
			
			$output = (empty($a["BILLTO_NAME"]) ? '' : $a["BILLTO_NAME"].'<br>').
				(empty($a["BILLTO_ADDR1"]) ? '' : $a["BILLTO_ADDR1"].'<br>').
				(empty($a["BILLTO_ADDR2"]) ? '' : $a["BILLTO_ADDR2"].'<br>').
				(empty($a["BILLTO_CITY"]) ? '' : $a["BILLTO_CITY"].', ').
				(empty($a["BILLTO_STATE"]) ? '' : $a["BILLTO_STATE"].', ').
				(empty($a["BILLTO_ZIP"]) ? '' : $a["BILLTO_ZIP"]).', '.
				(empty($a["BILLTO_COUNTRY"]) ? '' : $a["BILLTO_COUNTRY"]);
		}
		return $output;
	}
	
	private function cons_addr( $shipment ) {
		$output = '';
		$addr = $this->database->get_multiple_rows(
			"SELECT CONS_NAME, CONS_ADDR1, CONS_ADDR2, CONS_CITY, CONS_STATE,
			CONS_ZIP, CONS_COUNTRY
			FROM EXP_SHIPMENT
			WHERE SHIPMENT_CODE = $shipment
			LIMIT 1");
		if( is_array($addr) && count($addr) == 1 ) {
			$a = $addr[0];
			
			$output = (empty($a["CONS_NAME"]) ? '' : $a["CONS_NAME"].'<br>').
				(empty($a["CONS_ADDR1"]) ? '' : $a["CONS_ADDR1"].'<br>').
				(empty($a["CONS_ADDR2"]) ? '' : $a["CONS_ADDR2"].'<br>').
				(empty($a["CONS_CITY"]) ? '' : $a["CONS_CITY"].', ').
				(empty($a["CONS_STATE"]) ? '' : $a["CONS_STATE"].', ').
				(empty($a["CONS_ZIP"]) ? '' : $a["CONS_ZIP"]).', '.
				(empty($a["CONS_COUNTRY"]) ? '' : $a["CONS_COUNTRY"]);
		}
		return $output;
	}

	private function shipper_addr( $shipment ) {
		$output = '';
		$addr = $this->database->get_multiple_rows(
			"SELECT SHIPPER_NAME, SHIPPER_ADDR1, SHIPPER_ADDR2, SHIPPER_CITY, SHIPPER_STATE,
			SHIPPER_ZIP, SHIPPER_COUNTRY
			FROM EXP_SHIPMENT
			WHERE SHIPMENT_CODE = $shipment
			LIMIT 1");
		if( is_array($addr) && count($addr) == 1 ) {
			$a = $addr[0];
			
			$output = (empty($a["SHIPPER_NAME"]) ? '' : $a["SHIPPER_NAME"].'<br>').
				(empty($a["SHIPPER_ADDR1"]) ? '' : $a["SHIPPER_ADDR1"].'<br>').
				(empty($a["SHIPPER_ADDR2"]) ? '' : $a["SHIPPER_ADDR2"].'<br>').
				(empty($a["SHIPPER_CITY"]) ? '' : $a["SHIPPER_CITY"].', ').
				(empty($a["SHIPPER_STATE"]) ? '' : $a["SHIPPER_STATE"].', ').
				(empty($a["SHIPPER_ZIP"]) ? '' : $a["SHIPPER_ZIP"]).', '.
				(empty($a["SHIPPER_COUNTRY"]) ? '' : $a["SHIPPER_COUNTRY"]);
		}
		return $output;
	}

	private function broker_addr( $shipment ) {
		$output = '';
		$addr = $this->database->get_multiple_rows(
			"SELECT BROKER_NAME,BROKER_ADDR1,BROKER_ADDR2,BROKER_STATE,
			BROKER_CITY,BROKER_ZIP,BROKER_PHONE,BROKER_COUNTRY
			FROM EXP_SHIPMENT
			WHERE SHIPMENT_CODE = $shipment
			LIMIT 1");
		if( is_array($addr) && count($addr) == 1 ) {
			$a = $addr[0];
			
			$output = (empty($a["BROKER_NAME"]) ? '' : $a["BROKER_NAME"].'<br>').
				(empty($a["BROKER_ADDR1"]) ? '' : $a["BROKER_ADDR1"].'<br>').
				(empty($a["BROKER_ADDR2"]) ? '' : $a["BROKER_ADDR2"].'<br>').
				(empty($a["BROKER_CITY"]) ? '' : $a["BROKER_CITY"].', ').
				(empty($a["BROKER_STATE"]) ? '' : $a["BROKER_STATE"].', ').
				(empty($a["BROKER_ZIP"]) ? '' : $a["BROKER_ZIP"].', '.
				(empty($a["BROKER_COUNTRY"]) ? '' : $a["BROKER_COUNTRY"]));
		}
		return $output;
	}

	private function pickup_date( $shipment ) {
		$pu = $this->database->get_one_row(
			"SELECT DATE(COALESCE(
				(SELECT ACTUAL_DEPART FROM EXP_STOP
				WHERE SHIPMENT = SHIPMENT_CODE
				AND STOP_TYPE = 'pick'), PICKUP_DATE)) AS PICKUP_DATE
			FROM EXP_SHIPMENT
			WHERE SHIPMENT_CODE = $shipment
			LIMIT 1");
		return empty($pu['PICKUP_DATE']) ? '' : $pu['PICKUP_DATE'];
	}
	
	private function deliver_date( $shipment ) {
		$dl = $this->database->get_one_row(
			"SELECT DATE(COALESCE(
				(SELECT ACTUAL_DEPART FROM EXP_STOP
				WHERE SHIPMENT = SHIPMENT_CODE
				AND STOP_TYPE = 'drop'), DELIVER_DATE)) AS DELIVER_DATE
			FROM EXP_SHIPMENT
			WHERE SHIPMENT_CODE = $shipment
			LIMIT 1");
		return empty($dl['DELIVER_DATE']) ? '' : $dl['DELIVER_DATE'];
	}
	
	//! SCR# 872 - PICKUP_DUE
	private function pickup_due( $shipment ) {
		$pu = $this->database->get_one_row(
			"SELECT DATE(PICKUP_DATE) AS PICKUP_DUE
			FROM EXP_SHIPMENT
			WHERE SHIPMENT_CODE = $shipment
			LIMIT 1");
		return empty($pu['PICKUP_DUE']) ? '' : $pu['PICKUP_DUE'];
	}
	
	//! SCR# 872 - DELIVER_DUE
	private function deliver_due( $shipment ) {
		$dl = $this->database->get_one_row(
			"SELECT DATE(DELIVER_DATE) AS DELIVER_DUE
			FROM EXP_SHIPMENT
			WHERE SHIPMENT_CODE = $shipment
			LIMIT 1");
		return empty($dl['DELIVER_DUE']) ? '' : $dl['DELIVER_DUE'];
	}
	
	private function invoice_date( $shipment ) {
		$id = $this->database->get_one_row(
			"SELECT GET_ASOF( $shipment ) AS INVOICE_DATE");
		return empty($id['INVOICE_DATE']) ? '' : $id['INVOICE_DATE'];
	}
	
	private function pickup_number( $shipment ) {
		$pu = $this->database->get_one_row(
			"SELECT PICKUP_NUMBER
			FROM EXP_SHIPMENT
			WHERE SHIPMENT_CODE = $shipment
			LIMIT 1");
		return empty($pu['PICKUP_NUMBER']) ? '' : $pu['PICKUP_NUMBER'];
	}
	
	private function ref_number( $shipment ) {
		$rn = $this->database->get_one_row(
			"SELECT REF_NUMBER
			FROM EXP_SHIPMENT
			WHERE SHIPMENT_CODE = $shipment
			LIMIT 1");
		return empty($rn['REF_NUMBER']) ? '' : $rn['REF_NUMBER'];
	}
	
	private function customer_number( $shipment ) {
		$cn = $this->database->get_one_row(
			"SELECT CUSTOMER_NUMBER
			FROM EXP_SHIPMENT
			WHERE SHIPMENT_CODE = $shipment
			LIMIT 1");
		return empty($cn['CUSTOMER_NUMBER']) ? '' : $cn['CUSTOMER_NUMBER'];
	}
	
	private function st_number( $shipment ) {
		$pu = $this->database->get_one_row(
			"SELECT ST_NUMBER
			FROM EXP_SHIPMENT
			WHERE SHIPMENT_CODE = $shipment
			LIMIT 1");
		return empty($pu['ST_NUMBER']) ? '' : $pu['ST_NUMBER'];
	}
	
	private function fs_number( $shipment ) {
		$pu = $this->database->get_one_row(
			"SELECT FS_NUMBER
			FROM EXP_SHIPMENT
			WHERE SHIPMENT_CODE = $shipment
			LIMIT 1");
		return empty($pu['FS_NUMBER']) ? '' : $pu['FS_NUMBER'];
	}
	
	private function invoice_amount( $shipment ) {
		$amt = $this->database->get_one_row(
			"SELECT TOTAL, CURRENCY
			FROM exp_client_billing
			WHERE SHIPMENT_ID = $shipment
			LIMIT 1");
		return empty($amt['TOTAL']) ? '' : '\$'.number_format((float) $amt['TOTAL'],2).' '.$amt['CURRENCY'];
	}

	private function invoice_add_sales_line( $item, $description, $amount, $qty = 1 ) {
		$output = '';
		if( $amount <> 0 ) {
			$output = '<tr>
					<td class="w33">
						'.$description.
						//($this->export_sage50 ? ' (GL:'.$this->sage50->get_gl_code( $item ).')' : '').
						'
					</td>
					<td class="w15 text-right">
						'.number_format($amount,2).'
					</td>
					<td class="w15 text-right">
						'.$qty.'
					</td>
					<td class="w15 text-right">
						\$ '.number_format($amount * $qty,2).'
					</td>
					<td class="w10 text-right">
						'.$this->billing_currency.'
					</td>
				</tr>
				';
			$this->billing_total += $amount * $qty;	// Running total
		}
		
		return $output;
	}
	
     //! Add tax lines
    private function invoice_add_tax_lines( $shipment, $our_ref ) {
		$output = '';
	    
	    $tax_info = $this->tax->tax_info( $shipment );
	    if( is_array($tax_info) && count($tax_info) > 0 ) {
		    foreach($tax_info as $row) {
	    
				$output .= '<tr valign="top">
						<td class="w33">
							'.$our_ref.', '.$row['tax'].' ('.$row['rate'].'% Reg# '.(isset($row['TAX_REG_NUMBER']) ? $row['TAX_REG_NUMBER'] : '').')'.'
						</td>
						<td class="w15 text-right">
						</td>
						<td class="w15 text-right">
						</td>
						<td class="w15 text-right">
							\$ '.number_format($row['AMOUNT'],2).'
						</td>
						<td class="w10 text-right">
							'.$this->billing_currency.'
						</td>
					</tr>
					';
				$this->billing_total += $row['AMOUNT'];	// Running total
			}
		}
		
		return $output;
	}
	
	private function invoice_billing( $shipments, $office_number ) {
		require_once( "sts_company_tax_class.php" );

		$output = '';
		$this->tax = sts_company_tax::getInstance($this->database, $this->debug);
		$this->billing_total = 0.0;
		$found = false;
		
		foreach($shipments as $s) {
			$billing = $this->database->get_one_row("
				SELECT *
				FROM EXP_CLIENT_BILLING
				WHERE SHIPMENT_ID = $s");
				
			if( is_array($billing) ) {
				if( ! $found )
					$output = '<table class="noborder">
					<thead>
					<tr>
						<th class="w25">
							DESCRIPTION
						</th>
						<th class="w15 text-right">
							UNIT PRICE
						</th>
						<th class="w15 text-right">
							QTY
						</th>
						<th class="w15 text-right">
							AMOUNT
						</th>
						<th class="w10 text-right">
							CURR
						</th>
					</tr>
					</thead>
					<tbody>
				';
				$found = true;
				
				$billing_pallets		= intval($billing['PALLETS']);
				$billing_per_pallets	= floatval($billing['PER_PALLETS']);
				$billing_amount_pallets	= floatval($billing['PALLETS_RATE']);
				$billing_hand_pallet	= floatval($billing['HAND_PALLET']);
				$billing_handling		= floatval($billing['HAND_CHARGES']);
				$billing_freight		= floatval($billing['FREIGHT_CHARGES']);
				$billing_extra			= floatval($billing['EXTRA_CHARGES']);
				$billing_extra_note		= $billing['EXTRA_CHARGES_NOTE'];
				
				$billing_loading_free_hrs	= floatval($billing['FREE_DETENTION_HOUR']);
				$billing_loading_hrs		= floatval($billing['DETENTION_HOUR']);
				$billing_loading_rate		= floatval($billing['RATE_PER_HOUR']);
				$billing_loading			= floatval($billing['DETENTION_RATE']);
				
				$billing_unloading_free_hrs	= floatval($billing['FREE_UN_DETENTION_HOUR']);
				$billing_unloading_hrs		= floatval($billing['UNLOADED_DETENTION_HOUR']);
				$billing_unloading_rate		= floatval($billing['UN_RATE_PER_HOUR']);
				$billing_unloading			= floatval($billing['UNLOADED_DETENTION_RATE']);
				
				$billing_cod			= floatval($billing['COD']);
				$billing_mileage		= floatval($billing['MILLEAGE']);
				$billing_fsc_rate		= floatval($billing['FSC_AVERAGE_RATE']);
				$billing_mileage_rate	= floatval($billing['RPM']);
				$billing_mileage_total	= floatval($billing['RPM_RATE']);
				$billing_surcharge		= floatval($billing['FUEL_COST']);
		
				$billing_stopoff		= floatval($billing['STOP_OFF']);
				$billing_stopoff_note	= $billing['STOP_OFF_NOTE'];
				$billing_weekend		= floatval($billing['WEEKEND']);
				
				$billing_adjustment_title	= $billing['ADJUSTMENT_CHARGE_TITLE'];
				$billing_adjustment_charge	= floatval($billing['ADJUSTMENT_CHARGE']);
		
				$selection_fee	= floatval($billing['SELECTION_FEE']);
				$discount		= floatval($billing['DISCOUNT']);
				$this->billing_currency	= $billing['CURRENCY'];
				$our_ref = $office_number[$s];
				
				$output .= $this->invoice_add_sales_line( INVOICE_ITEM_PALLETS, $our_ref.', Pallets', $billing_amount_pallets );
				
				$output .= $this->invoice_add_sales_line( INVOICE_ITEM_PALLET_HANDLING, $our_ref.', Pallet Handling', $billing_hand_pallet );
				
				$output .= $this->invoice_add_sales_line( INVOICE_ITEM_FRIEGHT, $our_ref.', Freight charges', $billing_freight );
				
				$output .= $this->invoice_add_sales_line( INVOICE_ITEM_HANDLING, $our_ref.', Handling charges', $billing_handling );
				
				$output .= $this->invoice_add_sales_line( INVOICE_ITEM_FRIEGHT, $our_ref.', '.
					(isset($billing_adjustment_title) && $billing_adjustment_title <> '' ? $billing_adjustment_title : 'Adjustment'), $billing_adjustment_charge );
				
				$output .= $this->invoice_add_sales_line( INVOICE_ITEM_EXTRA, $our_ref.', Extra charges: '.$billing_extra_note, $billing_extra );
				
				$output .= $this->invoice_add_sales_line( INVOICE_ITEM_LOADING, $our_ref.', Loading Detention', $billing_loading );
					
				$output .= $this->invoice_add_sales_line( INVOICE_ITEM_UNLOADING, $our_ref.', Unloading Detention', $billing_unloading );
					
				//! SCR 162 - adjust text
				//! SCR# 168 - if it does not match, use total only
				if( $billing_mileage * $billing_mileage_rate == $billing_mileage_total )
					$output .= $this->invoice_add_sales_line( INVOICE_ITEM_MILEAGE, $our_ref.', Mileage: '.$billing_mileage.' miles @ '.$billing_mileage_rate, $billing_mileage_rate, $billing_mileage );
				else
					$output .= $this->invoice_add_sales_line( INVOICE_ITEM_MILEAGE, $our_ref.', Mileage: '.$billing_mileage.' miles', $billing_mileage_total );
				
				if( $this->debug ) {	
					echo "<pre>".__METHOD__.": our_ref, billing_fsc_rate, billing_mileage, billing_surcharge\n";
					var_dump($our_ref, $billing_fsc_rate, $billing_mileage, $billing_surcharge);
					var_dump($billing_fsc_rate > 0, $billing_mileage * $billing_fsc_rate == $billing_surcharge );
					echo "</pre>";
				}
				
				if( $billing_fsc_rate > 0 ) {
					if( $billing_mileage * $billing_fsc_rate == $billing_surcharge )
						$output .= $this->invoice_add_sales_line( INVOICE_ITEM_SURCHARGE, $our_ref.', FSC: '.$billing_mileage.' miles @ '.$billing_fsc_rate, $billing_fsc_rate, $billing_mileage );
					else
						$output .= $this->invoice_add_sales_line( INVOICE_ITEM_SURCHARGE, $our_ref.', FSC: '.$billing_mileage.' miles @ '.$billing_fsc_rate, $billing_surcharge );
				} else {
					$output .= $this->invoice_add_sales_line( INVOICE_ITEM_SURCHARGE, $our_ref.', FSC: '.$billing_mileage.' miles', $billing_surcharge );
				}
					
				$output .= $this->invoice_add_sales_line( INVOICE_ITEM_STOPOFF, $our_ref.', Stopoff charges: '.$billing_stopoff_note, $billing_stopoff );
					
				$output .= $this->invoice_add_sales_line( INVOICE_ITEM_WEEKEND, $our_ref.', Weekend/Holiday', $billing_weekend );
					
				$output .= $this->invoice_add_sales_line( INVOICE_ITEM_SELECTION, $our_ref.', Selection fee', $selection_fee );
				
				$output .= $this->invoice_add_sales_line( INVOICE_ITEM_DISCOUNT, $our_ref.', Discount', -1 * $discount );
				
				//! Add client rates
				$rates = $this->database->get_multiple_rows("
					SELECT RATE_CODE, RATE_NAME, CATEGORY, RATES
					FROM EXP_CLIENT_BILLING_RATES
					WHERE BILLING_ID = (SELECT CLIENT_BILLING_ID FROM EXP_CLIENT_BILLING
					WHERE SHIPMENT_ID = ".$s.")");
		
				if( is_array($rates) && count($rates) > 0 ) {
					foreach($rates as $r) {
						$output .= $this->invoice_add_sales_line( INVOICE_ITEM_OTHER, $our_ref.', '.$r["RATE_CODE"].' '.$r["RATE_NAME"].' ('.$r["CATEGORY"].')', $r["RATES"] );
					}
				}
				
				$output .= $this->invoice_add_tax_lines( $s, $our_ref );
			}
			
		}
		
		if( $found )
			$output .= '<tr>
						<th class="w33">
							TOTAL
						</th>
						<th class="w15 text-right">
						</th>
						<th class="w15 text-right">
						</th>
						<th class="w15 text-right">
							\$ '.number_format($this->billing_total,2).'
						</th>
						<th class="w10 text-right">
							'.$this->billing_currency.'
						</th>
					</tr>
				</tbody>
			</table>
';
		
		return $output;
	}
	
	//! Who to send an invoice to
	// First look in the shipment, then failing that check the client/contact info
	public function shipment_invoice_recipient( $shipment ) {
		$result = false;
		
		if( empty($this->email_invoice_override) ) {
			$recipient = $this->database->get_one_row("
				SELECT COALESCE(S2.BILLTO_EMAIL,
					(select C.EMAIL
					FROM EXP_CONTACT_INFO C, EXP_SHIPMENT S
					WHERE C.CONTACT_CODE = S.BILLTO_CLIENT_CODE
					AND C.CONTACT_TYPE = 'bill_to'
					AND S.SHIPMENT_CODE = S2.SHIPMENT_CODE
					AND C.EMAIL <> ''
					LIMIT 1)) AS BILLTO_EMAIL
				FROM EXP_SHIPMENT S2
				WHERE S2.SHIPMENT_CODE = ".$shipment );
			
			if( is_array($recipient) && ! empty($recipient["BILLTO_EMAIL"]) )
				$result = $recipient["BILLTO_EMAIL"];
		} else {
			$result = $this->email_invoice_override;
		}
		return $result;
	}
		
	//!SCR# 790 - enhanced invoice (carrier, lumper)
	private function get_carrier_lumper_info( $shipment ) {
		$result = $this->database->get_one_row("
			SELECT EXP_LOAD.LOAD_CODE, EXP_LOAD.CARRIER_TOTAL, EXP_LOAD.CURRENCY,
			EXP_LUMPER.CARRIER_NAME AS LUMPER_NAME, EXP_LUMPER.SAGE50_VENDORID AS LUMPER_VENDORID,
			EXP_LOAD.LUMPER_TOTAL, EXP_LOAD.LUMPER_CURRENCY,
			EXP_CARRIER.CARRIER_CODE, EXP_CARRIER.CARRIER_NAME,
			EXP_CARRIER.SAGE50_VENDORID AS CARRIER_VENDORID,
			EXP_CONTACT_INFO.CONTACT_NAME, EXP_CONTACT_INFO.PHONE_OFFICE,
			EXP_CONTACT_INFO.PHONE_EXT, EXP_CONTACT_INFO.PHONE_FAX, EXP_CONTACT_INFO.PHONE_CELL,
			EXP_CONTACT_INFO.EMAIL
			FROM EXP_SHIPMENT, EXP_LOAD
			LEFT JOIN EXP_CARRIER
			ON EXP_LOAD.CARRIER = EXP_CARRIER.CARRIER_CODE
			
			LEFT JOIN EXP_CARRIER AS EXP_LUMPER
			ON EXP_LOAD.LUMPER = EXP_LUMPER.CARRIER_CODE
			
			LEFT JOIN EXP_CONTACT_INFO
			ON EXP_CONTACT_INFO.CONTACT_SOURCE = 'carrier'
			AND EXP_CONTACT_INFO.CONTACT_CODE = EXP_CARRIER.CARRIER_CODE
			AND EXP_CONTACT_INFO.CONTACT_TYPE IN ('company', 'carrier')
			AND EXP_CONTACT_INFO.ISDELETED = FALSE
			
			WHERE SHIPMENT_CODE = $shipment
			AND EXP_SHIPMENT.LOAD_CODE = EXP_LOAD.LOAD_CODE
			
			LIMIT 1");
		
		return $result;
	}
	
	//! SCR# 206 - prepare an invoice for a shipment
	public function shipment_invoice( $shipment, $inline_logo = false, $alt_invoice = false ) {
		global $sts_email_invoice, $sts_email_extra_stops, $sts_crm_dir;
		require_once( "sts_stop_class.php" );
		require_once( "sts_detail_class.php" );
		require_once( "sts_company_class.php" );
		require_once( "sts_item_list_class.php" );
		require_once( "sts_shipment_class.php" );

		$template_file = $alt_invoice ? $this->email_invoice_template2 : $this->email_invoice_template;
		if( $this->debug ) echo "<p>".__METHOD__.": shipment = $shipment, template = $template_file</p>";
		$company_table = sts_company::getInstance($this->database, $this->debug);
		
		/*
		if( $this->export_sage50 ) {
			require_once( "sts_sage50_class.php" );
			$this->sage50 = sts_sage50_invoice::getInstance( $this->database, $this->debug );
			$this->sage50->get_gl_codes( $shipment );
		}
		*/

		if( file_exists($template_file))
			$template = file_get_contents($template_file);
		else if( file_exists( dirname(__FILE__).DIRECTORY_SEPARATOR.$template_file ) )
			$template = file_get_contents(dirname(__FILE__).DIRECTORY_SEPARATOR.$template_file);
		else
			$template = 'MISSING TEMPLATE FILE '.$template_file;
		
		if( $this->debug ) {
			echo "<p>raw template</p><pre>";
			var_dump($template);
			echo "</pre>";
		}
		
		//$match = '#(.*)'.preg_quote('<!-- CONSOLIDATE_START -->').'(.*)'.
		//	preg_quote('<!-- CONSOLIDATE_END -->').'(.*)#s';
		$match = '#(.*)'.preg_quote('<!-- CONSOLIDATE_START -->').'(.*)'.
			preg_quote('<!-- CONSOLIDATE_END -->').'(.*)#s';
		if( preg_match($match, $template, $matches) ) {
			$template_top		= $matches[1];
			$template_cons		= $matches[2];
			$template_bottom	= $matches[3];
		} else {
			$template_top		= '';
			$template_cons		= $template;
			$template_bottom	= '';
		}
		
		if( $this->debug ) {
			echo "<p>template parts</p><pre>";
			var_dump($match, $matches);
			echo "</pre>";
		}
		
		//! Find the consolidation shipment, and list of all shipments consolidated.
		$rows = $this->database->get_multiple_rows("
			SELECT S.SHIPMENT_CODE, S.CONSOLIDATE_NUM,
				".($this->multi_company ? "COALESCE(S.SS_NUMBER, S.SHIPMENT_CODE)" :
					"S.SHIPMENT_CODE")." AS SS_NUMBER
			FROM EXP_SHIPMENT S
			WHERE S.CONSOLIDATE_NUM = 
				(SELECT S2.CONSOLIDATE_NUM FROM EXP_SHIPMENT S2
				WHERE S2.SHIPMENT_CODE = $shipment)
			ORDER BY S.SHIPMENT_CODE ASC" );
		
		$shipments = array();
		$office_number = array();
		if( is_array($rows) && count($rows) > 0 ) {
			$consolidate_num = $rows[0]["CONSOLIDATE_NUM"];
			foreach( $rows as $row ) {
				$shipments[] = $row["SHIPMENT_CODE"];
				$office_number[$row["SHIPMENT_CODE"]] = $row["SS_NUMBER"];
			}
		} else {
			$consolidate_num = $shipment;	// Not consolidated - just 1 shipment
			$shipments[] = $shipment;
			$office_number[$shipment] = $this->multi_company ? $this->reference( $consolidate_num, 'shipment') : $shipment;
		}
		
		if( $this->debug ) {
			echo "<p>consolidate_num, shipments</p><pre>";
			var_dump($consolidate_num, $shipments);
			echo "</pre>";
		}
		
		//! transform $template_top ------------------------------------------

		$company_name = $company_table->company_name( $consolidate_num, 'shipment' );
		if( is_string($company_name) && $company_name <> '' ) {
			$template_top = preg_replace('/\%COMPANY_NAME\%/', $company_name, $template_top);
		}

		$company_logo = $company_table->company_logo( $consolidate_num, 'shipment' );
		if( $this->debug ) echo "<p>".__METHOD__.": company_logo = ".$company_logo."</p>";
		if( $inline_logo && ctype_alpha(substr($company_logo, 0, 1)) && substr($company_logo, 1, 2) == ':\\')
			$company_logo = $this->mime_encocde_logo( $company_logo );
			
		if( is_string($company_logo) && ! empty($company_logo) ) {
			$template_top = str_replace('%COMPANY_LOGO%', $company_logo, $template_top);
		} else
			$template_top = preg_replace('/<img src="\%COMPANY_LOGO\%">/', '', $template_top, 1);

		$current_date = date("m/d/Y");
		if( is_string($current_date) && ! empty($current_date) ) {
			$template_top = preg_replace('/\%CURRENT_DATE\%/', $current_date, $template_top);
		}

		$invoice_date = $this->invoice_date( $consolidate_num );
		if( is_string($invoice_date) && ! empty($invoice_date) ) {
			$template_top = preg_replace('/\%INVOICE_DATE\%/', date("m/d/Y", strtotime($invoice_date)), $template_top);
		}
		
		$company_addr = $company_table->company_address( $consolidate_num, 'shipment' );
		if( is_string($company_addr) && ! empty($company_addr) ) {
			$template_top = preg_replace('/\%COMPANY_ADDR\%/', $company_addr, $template_top);
		}
		
		$company_phone = $company_table->company_phone( $consolidate_num, 'shipment' );
		if( is_string($company_phone) && ! empty($company_phone) ) {
			$template_top = preg_replace('/\%COMPANY_PHONE\%/', $company_phone, $template_top);
		}
		
		$company_fax = $company_table->company_fax( $consolidate_num, 'shipment' );
		if( is_string($company_phone) && ! empty($company_phone) ) {
			$template_top = preg_replace('/\%COMPANY_FAX\%/', $company_fax, $template_top);
		}

		$company_email = $company_table->company_email( $consolidate_num, 'shipment' );
		if( is_string($company_email) && ! empty($company_email) ) {
			$template_top = preg_replace('/\%COMPANY_EMAIL\%/', $company_email, $template_top);
		}
		
		//! SCR# 591 - Invoice add remit on report
		$remitto = $this->remitto_addr();
		if( is_string($remitto) && ! empty($remitto) ) {
			$template_top = preg_replace('/\%REMIT_TO\%/', $remitto, $template_top);
		}
		
		$billto = $this->billto_addr( $consolidate_num );
		if( is_string($billto) && ! empty($billto) ) {
			$template_top = preg_replace('/\%BILL_TO\%/', $billto, $template_top);
		}
		
		$our_ref = $this->multi_company ? $this->reference( $consolidate_num, 'shipment', true ) : $shipment;
		if( ! empty($our_ref) ) {
			$template_top = preg_replace('/\%SHIPMENT_CODE\%/', $our_ref, $template_top);
		}
		
		//! CCC
		$item_list_table = sts_item_list::getInstance($this->database, $this->debug);
		$bill_obj=new sts_table($this->database , CLIENT_BILL , $this->debug);
		$check = $bill_obj->fetch_rows("SHIPMENT_ID = ".$consolidate_num, "TERMS");
		$terms = 0;
		if( is_array($check) && count($check) == 1 && isset($check[0]["TERMS"]))
			$terms = $check[0]["TERMS"];
		$template_top = preg_replace('/\%TERMS\%/', $item_list_table->render_terms($terms, 'Client Terms', false), $template_top);

		//! transform $template_cons ------------------------------------------
		
		$template_middle = '';
		$carrier_loads = [];
		$template_middles = [];
		foreach($shipments as $s) {	//! Shipment loop
			$template_cons_copy = $template_cons;

			$our_ref = $office_number[$s];
			if( ! empty($our_ref) ) {
				$template_cons_copy = preg_replace('/\%SHIPMENT_CODE\%/', $our_ref, $template_cons_copy);
			}

			$shipper = $this->shipper_addr( $s );
			if( is_string($shipper) && ! empty($shipper) ) {
				$template_cons_copy = preg_replace('/\%SHIPPER\%/', $shipper, $template_cons_copy);
			}
			
			$consignee = $this->cons_addr( $s );
			if( is_string($consignee) && ! empty($consignee) ) {
				$template_cons_copy = preg_replace('/\%CONSIGNEE\%/', $consignee, $template_cons_copy);
			}
		
			$broker = $this->broker_addr( $s);
			if( is_string($broker) && ! empty($broker) ) {
				$template_cons_copy = preg_replace('/\%CUSTOMS_BROKER\%/', $broker, $template_cons_copy);
			} else
				$template_cons_copy = preg_replace('/\%CUSTOMS_BROKER\%/', '', $template_cons_copy);

			$pickup = $this->pickup_date( $s );
			if( is_string($pickup) && ! empty($pickup) ) {
				$template_cons_copy = preg_replace('/\%PICKUP_DATE\%/', date("m/d/Y", strtotime($pickup)), $template_cons_copy);
			} else
				$template_cons_copy = preg_replace('/\%PICKUP_DATE\%/', '', $template_cons_copy);
			
			$deliver = $this->deliver_date( $s );
			if( is_string($deliver) && ! empty($deliver) ) {
				$template_cons_copy = preg_replace('/\%DELIVER_DATE\%/', date("m/d/Y", strtotime($deliver)), $template_cons_copy);
			} else
				$template_cons_copy = preg_replace('/\%DELIVER_DATE\%/', '', $template_cons_copy);
			
			//! SCR# 872 - PICKUP_DUE
			$pickup = $this->pickup_due( $s );
			if( is_string($pickup) && ! empty($pickup) ) {
				$template_cons_copy = preg_replace('/\%PICKUP_DUE\%/', date("m/d/Y", strtotime($pickup)), $template_cons_copy);
			} else
				$template_cons_copy = preg_replace('/\%PICKUP_DUE\%/', '', $template_cons_copy);
			
			//! SCR# 872 - DELIVER_DUE
			$deliver = $this->deliver_due( $s );
			if( is_string($deliver) && ! empty($deliver) ) {
				$template_cons_copy = preg_replace('/\%DELIVER_DUE\%/', date("m/d/Y", strtotime($deliver)), $template_cons_copy);
			} else
				$template_cons_copy = preg_replace('/\%DELIVER_DUE\%/', '', $template_cons_copy);
			
			$pickup_number = $this->pickup_number( $s );
			if( is_string($pickup_number) && ! empty($pickup_number) ) {
				$template_cons_copy = preg_replace('/\%PICKUP_NUMBER\%/', $pickup_number, $template_cons_copy);
			} else
				$template_cons_copy = preg_replace('/\%PICKUP_NUMBER\%/', '', $template_cons_copy);
		//! SCR# 845 - customer# and reference#
			$ref_number = $this->ref_number( $s );
			if( is_string($ref_number) && ! empty($ref_number) ) {
				$template_cons_copy = preg_replace('/\%REF_NUMBER\%/', $ref_number, $template_cons_copy);
			} else
				$template_cons_copy = preg_replace('/\%REF_NUMBER\%/', '', $template_cons_copy);
		
			$customer_number = $this->customer_number( $s );
			if( is_string($customer_number) && ! empty($customer_number) ) {
				$template_cons_copy = preg_replace('/\%CUSTOMER_NUMBER\%/', $customer_number, $template_cons_copy);
			} else
				$template_cons_copy = preg_replace('/\%CUSTOMER_NUMBER\%/', '', $template_cons_copy);
		
			$st_number = $this->st_number( $s );
			if( is_string($st_number) && ! empty($st_number) ) {
				$template_cons_copy = preg_replace('/\%ST_NUMBER\%/', $st_number, $template_cons_copy);
			} else
				$template_cons_copy = preg_replace('/\%ST_NUMBER\%/', '', $template_cons_copy);
		
			$fs_number = $this->fs_number( $s );
			if( is_string($fs_number) && ! empty($fs_number) ) {
				$template_cons_copy = preg_replace('/\%FS_NUMBER\%/', $fs_number, $template_cons_copy);
			} else
				$template_cons_copy = preg_replace('/\%FS_NUMBER\%/', '', $template_cons_copy);
		
			$shipment_table = sts_shipment::getInstance($this->database, $this->debug, $this->cli);

			//! SCR# 702 - Get equipment required for a shipment
			$equipment = $shipment_table->get_equipment_req( $shipment );
			if( ! empty( $equipment ) )
				$template_cons_copy = preg_replace('/\%EQUIPMENT\%/', $equipment, $template_cons_copy);
			else
				$template_cons_copy = preg_replace('/\%EQUIPMENT\%/', '', $template_cons_copy);
		
			$detail_table = sts_detail_invoice::getInstance($this->database, $this->debug);
			
			$detail_content = $this->render_html_message( $detail_table, "SHIPMENT_CODE = ".$s,
					$detail_table->html_template() );
	
			$template_cons_copy = preg_replace('/\%DETAILS_GO_HERE\%/', $detail_content, $template_cons_copy, 1);
			

			//! MOVE BILLING_DETAILS HERE
			if( strpos($template_cons_copy, 'BILLING_GO_HERE') !== false &&
				strpos($our_ref, '-') === false ) {
				$billing_details = $this->invoice_billing( $shipments, $office_number );
				
				$template_cons_copy = preg_replace('/\%BILLING_GO_HERE\%/', $billing_details, $template_cons_copy, 1);
			} else {
				// remove billing info
				$match = preg_quote('<!-- BILLING_DETAILS_START -->').'(.*)'.
					preg_quote('<!-- BILLING_DETAILS_END -->');
				$template_cons_copy = preg_replace('/'.$match.'/s', '',
					$template_cons_copy, 1);	
			}


			//! MOVE CARRIER STUFF HERE
			
			//!SCR# 790 - enhanced invoice (carrier, lumper)
			$clinfo = $this->get_carrier_lumper_info( $s );
			if( $alt_invoice && is_array($clinfo) && ! in_array($clinfo["LOAD_CODE"], $carrier_loads)) {
		//	if( is_array($clinfo) ) {
				$carrier_loads[] = $clinfo["LOAD_CODE"];
				$template_cons_copy = preg_replace('/\%LOAD_CODE\%/',
					empty($clinfo["LOAD_CODE"]) ? '' : $clinfo["LOAD_CODE"], $template_cons_copy, 1);
	
				$template_cons_copy = preg_replace('/\%CARRIER_TOTAL\%/',
					empty($clinfo["CARRIER_TOTAL"]) ? '' : $this->nm2($clinfo["CARRIER_TOTAL"]), $template_cons_copy, 1);
				
				$template_cons_copy = preg_replace('/\%CARRIER_CURRENCY\%/',
					empty($clinfo["CURRENCY"]) ? '' : $clinfo["CURRENCY"], $template_cons_copy, 1);
				
				$template_cons_copy = preg_replace('/\%CARRIER_NAME\%/',
					empty($clinfo["CARRIER_NAME"]) ? '' : $clinfo["CARRIER_NAME"], $template_cons_copy, 1);
				
				$template_cons_copy = preg_replace('/\%CARRIER_VENDORID\%/',
					empty($clinfo["CARRIER_VENDORID"]) ? '' : $clinfo["CARRIER_VENDORID"], $template_cons_copy, 1);
				
				if( ! empty($clinfo["LUMPER_NAME"]) ) {
					$template_cons_copy = preg_replace('/\%LUMPER_NAME\%/',
						empty($clinfo["LUMPER_NAME"]) ? '' : $clinfo["LUMPER_NAME"], $template_cons_copy, 1);
					
					$template_cons_copy = preg_replace('/\%LUMPER_VENDORID\%/',
						empty($clinfo["LUMPER_VENDORID"]) ? '' : $clinfo["LUMPER_VENDORID"], $template_cons_copy, 1);
					
					$template_cons_copy = preg_replace('/\%LUMPER_TOTAL\%/',
						empty($clinfo["LUMPER_TOTAL"]) ? '' : $this->nm2($clinfo["LUMPER_TOTAL"]), $template_cons_copy, 1);
					$template_cons_copy = preg_replace('/\%LUMPER_CURRENCY\%/',
						empty($clinfo["LUMPER_CURRENCY"]) ? '' : $clinfo["LUMPER_CURRENCY"], $template_cons_copy, 1);
				} else {
					// remove carier info
					$match = preg_quote('<!-- LUMPER_DETAILS_START -->').'(.*)'.
						preg_quote('<!-- LUMPER_DETAILS_END -->');
					$template_cons_copy = preg_replace('/'.$match.'/s', '',
						$template_cons_copy, 1);	
				}
				
				$template_cons_copy = preg_replace('/\%CARRIER_CONTACT\%/',
					empty($clinfo["CONTACT_NAME"]) ? '' : $clinfo["CONTACT_NAME"], $template_cons_copy, 1);
				
				$template_cons_copy = preg_replace('/\%CARRIER_PHONE_OFFICE\%/',
					empty($clinfo["PHONE_OFFICE"]) ? '' : $clinfo["PHONE_OFFICE"], $template_cons_copy, 1);
				
				$template_cons_copy = preg_replace('/\%CARRIER_PHONE_EXT\%/',
					empty($clinfo["PHONE_EXT"]) ? '' : $clinfo["PHONE_EXT"], $template_cons_copy, 1);
				
				$template_cons_copy = preg_replace('/\%CARRIER_PHONE_FAX\%/',
					empty($clinfo["PHONE_FAX"]) ? '' : $clinfo["PHONE_FAX"], $template_cons_copy, 1);
				
				$template_cons_copy = preg_replace('/\%CARRIER_PHONE_CELL\%/',
					empty($clinfo["PHONE_CELL"]) ? '' : $clinfo["PHONE_CELL"], $template_cons_copy, 1);
				
				$template_cons_copy = preg_replace('/\%CARRIER_EMAIL\%/',
					empty($clinfo["EMAIL"]) ? '' : $clinfo["EMAIL"], $template_cons_copy, 1);
			} else {
				// remove carier info
				$match = preg_quote('<!-- CARRIER_DETAILS_START -->').'(.*)'.
					preg_quote('<!-- CARRIER_DETAILS_END -->');
				$template_cons_copy = preg_replace('/'.$match.'/s', '',
					$template_cons_copy, 1);	
			}
			
			$template_middles[] = $template_cons_copy;
		}

		//! ADD FOOTER AFTER SHIPMENT FOR MULTIPLE SHIPMENTS
		if( $alt_invoice && count($shipments) > 0 ) {
			$template_middle = implode('<footer>&nbsp;</footer>
			', $template_middles);
		} else {
			$template_middle = implode('', $template_middles);
		}

		//! SCR# 225 - list extra stops
		if( $this->extra_stops ) {
			$check = $this->database->get_one_row( "SELECT 
				(SELECT L.TOTAL_DISTANCE FROM EXP_LOAD L
				WHERE L.LOAD_CODE = S.LOAD_CODE) AS TOTAL_DISTANCE
				FROM EXP_SHIPMENT S
				WHERE S.SHIPMENT_CODE = $shipment
				AND (SELECT COUNT(T.SHIPMENT_CODE) FROM EXP_SHIPMENT T
				WHERE T.LOAD_CODE = S.LOAD_CODE) = 1
				AND (SELECT COUNT(T.STOP_CODE) FROM EXP_STOP T
				WHERE T.LOAD_CODE = S.LOAD_CODE) > 2
				AND (SELECT C.CLIENT_EXTRA_STOPS FROM EXP_CLIENT C
				WHERE C.CLIENT_CODE = S.BILLTO_CLIENT_CODE)" );
				
			if( is_array($check) && isset($check["TOTAL_DISTANCE"]) && $check["TOTAL_DISTANCE"] > 0 ) {

				$stop_table2 = new sts_stop_carrier($this->database, $this->debug);
				
				$stops_content = $this->render_html_message( $stop_table2, "LOAD_CODE = (SELECT LOAD_CODE FROM EXP_SHIPMENT WHERE SHIPMENT_CODE = $shipment) AND STOP_TYPE NOT IN ('pick', 'drop')",
						$stop_table2->extra_stops_template());
				$template_middle .= $stops_content;
			}
		}
		
		//! transform $template_bottom ------------------------------------------
		
		$billing_details = $this->invoice_billing( $shipments, $office_number );
		
		$template_bottom = preg_replace('/\%BILLING_GO_HERE\%/', $billing_details, $template_bottom, 1);

		$output = $template_top.$template_middle.$template_bottom;

		if( $this->debug ) {
			echo "<p>three parts</p><pre>";
			var_dump($template_top, $template_middle, $template_bottom);
			echo "</pre>";
		}

		return $output;
	}
	
	private function render_field( $table, $name, $value ) {
		$formatted = '';
		switch( $table->column_type($name) ) {
			case 'date':	// Date
				$formatted = date("m/d/Y", strtotime($value));
				break;
			
			case 'datetime':	// Date & time
			case 'timestamp':	// Date & time
				$formatted = date("m/d/Y h:i A", strtotime($value));
				break;
				
			case 'varchar':
			case 'int':
			default:
				$formatted = $value;
				break;
		}
		return $formatted;
	}
	
	public function render_html_message( $table = false, $match = false, $html_message = false ) {

		if( $this->debug ) echo "<p>".__METHOD__.": table = ".($table ? $table->table_name : "unknown")."</p>";
		$output = '';

		if( $this->debug ) {
			echo "<p>sts_email > render_html_message, html_message = </p>
			<pre>";
			var_dump($html_message);
			echo "</pre>";
		}
		
		//! Fetch data from the table
		if( $table ) {
			$result = $table->fetch_rows( $match );
			//! SCR# 369 - include last_stop_type as well
			$last_stop_name = $last_stop_zip = $last_stop_type = '';
			
			if( is_array($result) && count($result) > 0 ) {
			
				if( is_array($html_message) && isset($html_message['header']) )
					$output .= $html_message['header'];
	
				foreach( $result as $row ) {
					// ! Template
					$template = is_array($html_message) && isset($html_message['layout']) ? $html_message['layout'] : false;
					//! SCR# 521 - @ symbol prints as # on load sheet.
					if( $template ) {
						$template = str_replace('@', 'XYZZY', $template);
					}
			
					if( $this->debug ) echo "<p>template = ".($template ? '<pre>'.htmlspecialchars($template).'</pre>' : 'false')."</p>";
	
					foreach( $row as $name => $value) {
						//! SCR# 200 - hide shipment number, office number
						if( $this->hide_shipment_manifest &&
							$table->table_name == 'EXP_STOP' &&
							in_array($name, array('SHIPMENT', 'SS_NUMBER')))
							$rendered_field = '';
						//! SCR# 200 - hide duplicate stops
						//! SCR# 288 - include PHONE, EMAIL
						else if( $this->hide_stops_manifest &&
							$table->table_name == 'EXP_STOP' &&
							$last_stop_type == $row["STOP_TYPE"] &&
							$last_stop_name == $row["NAME"] &&
							$last_stop_zip == $row["ZIP_CODE"] &&
							in_array($name, array('SEQUENCE_NO', 'STOP_TYPE', 'NAME', 'ADDRESS',
								'ADDRESS2', 'CITY', 'STATE', 'ZIP_CODE', 'CONTACT', 'POS',
								'APPT', 'STOP_DISTANCE', 'REF_NUMBER',
								'BOL_NUMBER', 'PICKUP_NUMBER', 'CUSTOMER_NUMBER', 'DUE',
								'PHONE', 'EMAIL', 'EQUIPMENT', 'CUSTOMS_BROKER','STOP_COMMENT')))
							$rendered_field = '';
						else
							$rendered_field = $this->render_field( $table, $name, $value );
						if( $template ) {
							if( $rendered_field == '' )
								$template = preg_replace('/#'.$name.'#.*#/U', '', $template, 1);
							else
								$template = preg_replace('/#'.$name.'#(.*)#/U', '$1', $template, 1);
								
							$template = preg_replace('/\%'.$name.'\%/', $rendered_field, $template, 1);
					if( $this->debug ) echo "<p>ZXZname = $name template = ".($template ? '<pre>'.htmlspecialchars($template).'</pre>' : 'false')."</p>";
						} else
							$output .= $rendered_field;
					}
					//! SCR# 521 - @ symbol prints as # on load sheet.
					if( $template ) {
						$template = str_replace('XYZZY', '#', $template);
						$output .= $template;
					}
	
					$last_stop_type = empty($row["STOP_TYPE"]) ? '' : $row["STOP_TYPE"];
					$last_stop_name = empty($row["NAME"]) ? '' : $row["NAME"];
					$last_stop_zip = empty($row["ZIP_CODE"]) ? '' : $row["ZIP_CODE"];
				}

				if( is_array($html_message) && isset($html_message['footer']) )
					$output .= $html_message['footer'];	
			}
		}
			
		if( $this->debug ) echo "<p>output = ".($output ? '<pre>'.htmlspecialchars($output).'</pre>' : 'false')."</p>";
		return $output;
	}
	
	public function load_stops ( $load ) {
		global $sts_result_stops_lj_layout, $sts_result_stops_edit;
		global $sts_crm_dir;
		require_once( "sts_stop_class.php" );

		if( $this->debug ) echo "<p>".__METHOD__.": load = $load</p>";
		$output = "";
		
		$stop_table_lj = new sts_stop_left_join($this->database, $this->debug);
		$rslt = new sts_result( $stop_table_lj, "LOAD_CODE = ".$load, $this->debug );
		$output .= $rslt->render( $sts_result_stops_lj_layout, $sts_result_stops_edit );
		return $output;
	}
	
	public function load_history ( $load ) {
		global $sts_result_status_layout, $sts_result_status_edit;
		global $sts_crm_dir;
		require_once( "sts_load_class.php" );
		require_once( "sts_status_class.php" );
		
		$output = "";
		
		$load_table = sts_load::getInstance($this->database, $this->debug);
		$stat = $load_table->fetch_rows($load_table->primary_key.' = '.$load,
		"CURRENT_STATUS, CURRENT_STOP,
		(SELECT COUNT(*) AS NUM_STOPS
				FROM EXP_STOP
				WHERE EXP_LOAD.LOAD_CODE = EXP_STOP.LOAD_CODE) AS NUM_STOPS,
		(SELECT STOP_TYPE AS CURRENT_STOP_TYPE
				FROM EXP_STOP
				WHERE EXP_LOAD.LOAD_CODE = EXP_STOP.LOAD_CODE
				AND SEQUENCE_NO = CURRENT_STOP) AS CURRENT_STOP_TYPE");
		
		if( is_array($stat) && count($stat) > 0) {
			$row = $stat[0];
			$output .= "<p>CURRENT_STATUS = ".$row["CURRENT_STATUS"]." (".$load_table->state_name[$row["CURRENT_STATUS"]].")</p>";
			$output .= "<p>CURRENT_STOP = ".$row["CURRENT_STOP"]."</p>";
			$output .= "<p>NUM_STOPS = ".$row["NUM_STOPS"]."</p>";
			$output .= "<p>CURRENT_STOP_TYPE = ".$row["CURRENT_STOP_TYPE"]."</p>";
		}
		
		$status_table = sts_status::getInstance($this->database, $this->debug);
		$rslt3 = new sts_result( $status_table, "ORDER_CODE = ".$load." AND SOURCE_TYPE = 'load'", $this->debug );
		$output .= $rslt3->render( $sts_result_status_layout, $sts_result_status_edit );
		return $output;
	}
	
	public function shipment_history ( $shipment ) {
		global $sts_result_status_layout, $sts_result_status_edit;
		global $sts_crm_dir;
		require_once( "sts_shipment_class.php" );
		require_once( "sts_status_class.php" );
		
		$output = "";
		
		$shipment_table = sts_shipment::getInstance($this->database, $this->debug, $this->cli);
		$stat = $shipment_table->fetch_rows($shipment_table->primary_key.' = '.$shipment,
		"CURRENT_STATUS");
		
		if( is_array($stat) && count($stat) > 0) {
			$row = $stat[0];
			$output .= "<p>CURRENT_STATUS = ".$row["CURRENT_STATUS"]." (".$shipment_table->state_name[$row["CURRENT_STATUS"]].")</p>";
		}
		
		$status_table = sts_status::getInstance($this->database, $this->debug);
		$rslt3 = new sts_result( $status_table, "ORDER_CODE = ".$shipment." AND SOURCE_TYPE = 'shipment'", $this->debug );
		$output .= $rslt3->render( $sts_result_status_layout, $sts_result_status_edit );
		return $output;
	}
	
	public function shipment_histories ( $load ) {
		global $sts_crm_dir;
		require_once( "sts_shipment_class.php" );
		
		$shipment_table = sts_shipment::getInstance($this->database, $this->debug, $this->cli);

		$shipments = $shipment_table->fetch_rows("LOAD_CODE = ".$load.
			" OR EXISTS (SELECT SHIPMENT_CODE FROM EXP_SHIPMENT_LOAD WHERE
			EXP_SHIPMENT.SHIPMENT_CODE = EXP_SHIPMENT_LOAD.SHIPMENT_CODE
			AND EXP_SHIPMENT_LOAD.LOAD_CODE = ".$load.")", "SHIPMENT_CODE");
		$shipments_history = "";
		if( is_array($shipments) && count($shipments) > 0 ) {
			foreach($shipments as $row) {
				$shipments_history .= "<h3>Shipment ".$row["SHIPMENT_CODE"]." History</h3>\n".
					$this->shipment_history($row["SHIPMENT_CODE"]);
			}
		}
		
		return $shipments_history;
	}
	
	private function mysql_version() {
		$version_check = $this->setting->database->get_one_row("SELECT VERSION() AS VER");
		return "MySQL Version = ".$version_check["VER"];
	}
	
	public function send_alert( $message, $level = EXT_ERROR_ERROR, $subject = false ) {
		global $sts_database, $sts_crm_timezone, $sts_release_name, 
			$sts_schema_release_name, $sts_error_level_label, $_POST, $_GET, $_FILES;
		
		if( $this->debug ) {
			echo "<p>sts_email > send_alert: to ".(isset($this->email_diag_address) ? $this->email_diag_address : "unknown")."</p>
			<pre>".
			htmlspecialchars(var_export($message, true)).
			"</pre>";
			$result = false;
		}
		
		if( isset($this->email_diag_address) && $this->email_diag_address <> '' &&
			isset($this->email_diag_level) ) {
				
			$diag_level =  $this->diag_level();
			if( $diag_level >= $level ) {	// OK to send email
				$body = '<p>MESSAGE='.$message.'</p>'.
					'<br><p>NOW = '.date("m/d/Y h:i A").
					'<br><p>LEVEL = '.(isset($sts_error_level_label[$level]) ? $sts_error_level_label[$level] : 'UNKNOWN').
					'<br>REQUEST_URI = '.(isset($_SERVER["REQUEST_URI"]) ? $_SERVER["REQUEST_URI"] : '').'<br>'.
					'HTTP_REFERER = '.(isset($_SERVER["HTTP_REFERER"]) ? $_SERVER["HTTP_REFERER"] : '').'<br>'.
					'SERVER_NAME = '.(isset($_SERVER["SERVER_NAME"]) ? $_SERVER["SERVER_NAME"] : '').'<br>'.
					'SERVER_ADDR = '.(isset($_SERVER["SERVER_ADDR"]) ? $_SERVER["SERVER_ADDR"] : '').'<br>'.
					'SERVER_PORT = '.(isset($_SERVER["SERVER_PORT"]) ? $_SERVER["SERVER_PORT"] : '').'<br>'.
					'REMOTE_ADDR = '.(isset($_SERVER["REMOTE_ADDR"]) ? $_SERVER["REMOTE_ADDR"] : '').'</p>'.
					'<br><p>USER='.(isset($_SESSION["EXT_USERNAME"]) ? $_SESSION["EXT_USERNAME"] : '').'<br>'.
					'DB='.$sts_database.'<br>'.
					'REL='.$sts_release_name." (".'SCHEMA='.$sts_schema_release_name.")\n".'DB='.$sts_database.'<br>'.
					'TZ='.$sts_crm_timezone.'<br>'.
					$this->mysql_version().'</p>'.
					$this->generateCallTrace().'<br>'.
					(! empty($_POST) ? "<br>POST:<pre>".print_r($_POST, true)."</pre><br>" : "").
					(! empty($_GET) ? "<br>GET:<pre>".print_r($_GET, true)."</pre><br>" : "").
					(! empty($_FILES) ? "<br>FILES:<pre>".print_r($_FILES, true)."</pre><br>" : "")
					;
				
				$email_subject = $subject ? $subject : $this->diagnostic_subject;
				
				$this->send_email( $this->email_diag_address, '', $email_subject, $body );
			}
		}
	}

	public function send_qb_alert( $message ) {
		global $sts_email_qb_alert_boilerplate;
		
		if( isset($this->email_qb_alert) && $this->email_qb_alert <> '' ) {
			
			$body = $sts_email_qb_alert_boilerplate.$message;
			
			$this->send_email( $this->email_qb_alert, '', 'Please check Quickbooks', $body );
		}
	}
	
	//! SCR# 906 - Send an email to notify a shipment event
	public function notify_shipment_event( $shipment, $event ) {
		global $sts_crm_dir;
		
		$result = false;
		
		if( $this->debug ) {
			echo "<h2>".__METHOD__.": entry $shipment, $event</h2>";
		}
		
		require_once( "sts_shipment_class.php" );
		$shipment_table = sts_shipment::getInstance($this->database, $this->debug, $this->cli);
		
		switch( $event ) {
			case 'arrship':
				$stop_type = 'pick';
				$proceed = 'NOTIFY_ARRIVE_SHIPPER';
				$field = 'ACTUAL_ARRIVE';
				$title = 'Arrive Shipper';
				break;
			case 'picked':
				$stop_type = 'pick';
				$proceed = 'NOTIFY_DEPART_SHIPPER';
				$field = 'ACTUAL_DEPART';
				$title = 'Pick';
				break;
			case 'arrcons':
				$stop_type = 'drop';
				$proceed = 'NOTIFY_ARRIVE_CONS';
				$field = 'ACTUAL_ARRIVE';
				$title = 'Arrive Consignee';
				break;
			case 'dropped':
				$stop_type = 'drop';
				$proceed = 'NOTIFY_DEPART_CONS';
				$field = 'ACTUAL_DEPART';
				$title = 'Drop';
				break;
		}
					
		$details = $this->database->get_one_row("
			SELECT SHIPMENT_CODE, SS_NUMBER,
				(SELECT EMAIL_NOTIFY FROM EXP_CLIENT
				WHERE CLIENT_CODE = BILLTO_CLIENT_CODE) AS EMAIL_NOTIFY,
				(SELECT ".$proceed." FROM EXP_CLIENT
				WHERE CLIENT_CODE = BILLTO_CLIENT_CODE) AS PROCEED,
				(SELECT NOTIFY_DATE_FMT FROM EXP_CLIENT
				WHERE CLIENT_CODE = BILLTO_CLIENT_CODE) AS NOTIFY_DATE_FMT,
				COALESCE(PICKUP_APPT, 'N/A') AS PICKUP_APPT,
				COALESCE(DELIVERY_APPT, 'N/A') AS DELIVERY_APPT,
				COALESCE(CUSTOMER_NUMBER, 'N/A') AS CUSTOMER_NUMBER,
				COALESCE(BOL_NUMBER, 'N/A') AS BOL_NUMBER,
				
				COALESCE(REF_NUMBER, 'N/A') AS REF_NUMBER,
				COALESCE(PICKUP_NUMBER, 'N/A') AS PICKUP_NUMBER,
				
				COALESCE(SHIPPER_NAME, 'N/A') AS SHIPPER_NAME,
				COALESCE(SHIPPER_CITY, 'N/A') AS SHIPPER_CITY,
				COALESCE(SHIPPER_STATE, 'N/A') AS SHIPPER_STATE,
				COALESCE(CONS_NAME, 'N/A') AS CONS_NAME,
				COALESCE(CONS_CITY, 'N/A') AS CONS_CITY,
				COALESCE(CONS_STATE, 'N/A') AS CONS_STATE,
				
				(SELECT COALESCE( ".$field.", CURRENT_DATE) 
				FROM EXP_STOP
				WHERE SHIPMENT = shipment_code
				and STOP_TYPE = '".$stop_type."') AS STOP_DATE
			
			FROM exp_shipment
			where shipment_code = $shipment");
		
		if( $this->debug ) {
			echo "<pre>".__METHOD__.": details\n";
			var_dump($details);
			echo "</pre>";
		}
		
		if( is_array($details) && isset($details['EMAIL_NOTIFY']) &&
			! empty($details['EMAIL_NOTIFY']) &&
			isset($details['PROCEED']) && $details['PROCEED'] ) {
			
			if( $multi_company ) {
				// Get office info
				$office_info = $this->database->get_one_row("
					SELECT COALESCE( CONTACT_NAME, (select THE_VALUE 
					FROM EXP_SETTING
					WHERE CATEGORY = 'company' AND SETTING = 'CONTACT_NAME'), 'N/A') AS CONTACT_NAME,
					
					COALESCE( BUSINESS_PHONE, (select THE_VALUE 
					FROM EXP_SETTING
					WHERE CATEGORY = 'company' AND SETTING = 'BUSINESS_PHONE'), 'N/A') AS BUSINESS_PHONE,
					
					COALESCE( EMAIL, (select THE_VALUE 
					FROM EXP_SETTING
					WHERE CATEGORY = 'company' AND SETTING = 'EMAIL'), 'N/A') AS EMAIL
					
					FROM EXP_OFFICE, EXP_SHIPMENT
					WHERE EXP_OFFICE.OFFICE_CODE = EXP_SHIPMENT.OFFICE_CODE
					AND EXP_SHIPMENT.SHIPMENT_CODE = $shipment");
			} else {
				// Get office info
				$office_info = $this->database->get_one_row("
					SELECT COALESCE( (select THE_VALUE 
					FROM EXP_SETTING
					WHERE CATEGORY = 'company' AND SETTING = 'CONTACT_NAME'), 'N/A') AS CONTACT_NAME,
					
					COALESCE( (select THE_VALUE 
					FROM EXP_SETTING
					WHERE CATEGORY = 'company' AND SETTING = 'BUSINESS_PHONE'), 'N/A') AS BUSINESS_PHONE,
					
					COALESCE( (select THE_VALUE 
					FROM EXP_SETTING
					WHERE CATEGORY = 'company' AND SETTING = 'EMAIL'), 'N/A') AS EMAIL
					");
			}
			
			if( $this->debug ) {
				echo "<pre>".__METHOD__.": office_info\n";
				var_dump($office_info);
				echo "</pre>";
			}
	
			if( is_array($office_info) && isset($office_info['CONTACT_NAME']) &&
				isset($office_info['BUSINESS_PHONE']) && isset($office_info['EMAIL']) ) {
					
				$to = $details['EMAIL_NOTIFY'];
				$date_format = isset($details['NOTIFY_DATE_FMT']) && $details['NOTIFY_DATE_FMT'] == 'date' ? "m/d/Y" : "m/d/Y  H:i a";
				$subject = $title.' Notification - '.$this->reference( $shipment, 'shipment');
				
				$template_file = $this->setting->get( 'email',
					'EMAIL_NOTIFY_TEMPLATE' );

				if( file_exists($template_file))
					$template = file_get_contents($template_file);
				else if( file_exists( dirname(__FILE__).DIRECTORY_SEPARATOR.$template_file ) )
					$template = file_get_contents(dirname(__FILE__).DIRECTORY_SEPARATOR.$template_file);
				else if( ! empty($sts_crm_dir) && file_exists( $sts_crm_dir.DIRECTORY_SEPARATOR.$template_file ) )
					$template = file_get_contents($sts_crm_dir.DIRECTORY_SEPARATOR.$template_file);
				else
					$template = 'MISSING TEMPLATE FILE '.$template_file.'<br>
					'.$sts_crm_dir;
				
				$cd = date("m/d/Y  H:i a");
				$template = preg_replace('/\%CURRENT_DATE\%/', $cd, $template);
				$template = preg_replace('/\%SHIPMENT_CODE\%/', $this->reference( $shipment, 'shipment'), $template);
				$template = preg_replace('/\%TITLE\%/', $title.' Notification ', $template);
				$template = preg_replace('/\%SUBTITLE\%/', ($event == 'picked' ? 'Pick-up' : 'Delivery').
					' Location(s) ', $template);
				$template = preg_replace('/\%OFFICE_CONTACT\%/', $office_info['CONTACT_NAME'], $template);
				$template = preg_replace('/\%OFFICE_EMAIL\%/', $office_info['EMAIL'], $template);
				$template = preg_replace('/\%OFFICE_PHONE\%/', $office_info['BUSINESS_PHONE'], $template);
				$template = preg_replace('/\%PICKUP_APPT\%/', $details['PICKUP_APPT'], $template);
				$template = preg_replace('/\%DELIVERY_APPT\%/', $details['DELIVERY_APPT'], $template);
				$template = preg_replace('/\%CUSTOMER_NUMBER\%/', $details['CUSTOMER_NUMBER'], $template);
				$template = preg_replace('/\%BOL_NUMBER\%/', $details['BOL_NUMBER'], $template);
				
				$template = preg_replace('/\%REF_NUMBER\%/', $details['REF_NUMBER'], $template);
				$template = preg_replace('/\%PICKUP_NUMBER\%/', $details['PICKUP_NUMBER'], $template);
				$template = preg_replace('/\%STOP_DATE\%/', date($date_format, strtotime($details['STOP_DATE'])), $template);
				
				
				if( $event == 'arrship' || $event == 'picked' ) {
					$template = preg_replace('/\%STOP_NAME\%/', $details['SHIPPER_NAME'], $template);
					$template = preg_replace('/\%STOP_CITY\%/', $details['SHIPPER_CITY'], $template);
					$template = preg_replace('/\%STOP_STATE\%/', $details['SHIPPER_STATE'], $template);
					if( $event == 'arrship' )
						$template = preg_replace('/\%STOP_STATUS\%/', 'Arrive Shipper', $template);
					else
						$template = preg_replace('/\%STOP_STATUS\%/', 'Loaded', $template);
				} else {
					$template = preg_replace('/\%STOP_NAME\%/', $details['CONS_NAME'], $template);
					$template = preg_replace('/\%STOP_CITY\%/', $details['CONS_CITY'], $template);
					$template = preg_replace('/\%STOP_STATE\%/', $details['CONS_STATE'], $template);
					if( $event == 'arrcons' )
						$template = preg_replace('/\%STOP_STATUS\%/', 'Arrive Consignee', $template);
					else
						$template = preg_replace('/\%STOP_STATUS\%/', 'Delivered', $template);
				}
				
			//	echo $template;
				$queue_code = $this->queue->enqueue( $event, $shipment,
					$this->send_from(), $to, false, $subject, $template );

				$result = $this->send_email( $to, false, $subject,
					$template );
					
				$this->queue->update_sent( $queue_code, $result, $this->send_error );

				$comment = 'Email '.$event.
					' Notification To='.(empty($to) ? 'NULL' : $to).
					' ('.($result ? 'success' : 'failed').')';
				
				$shipment_table->add_shipment_status($shipment, $comment);
			}
			
		} else {
			if( $this->debug ) {
				echo "<h2>".__METHOD__.": EMAIL_NOTIFY not set</h2>";
			}
		}
		
		return $result;
	}
	
	// First step of creating invoice, return EMAIL_QUEUE_CODE or false
	public function prepare_and_send_invoice( $shipment, $email_type ) {
		$result = false;
		
		if( $this->debug ) {
			echo "<p>".__METHOD__.": entry $shipment, $email_type</p>";
		}
		
		require_once( "sts_attachment_class.php" );
		require_once( "sts_shipment_class.php" );
		$attachment_table = sts_attachment::getInstance($this->database, $this->debug);
		$shipment_table = sts_shipment::getInstance($this->database, $this->debug, $this->cli);

		$to = $this->shipment_invoice_recipient( $shipment );
		if( $to <> false ) {
			$cc = $this->email_invoice_cc;
			$subject = $this->email_invoice_subject.$this->reference( $shipment, 'shipment').($email_type == 'invoicer' ? ' (resend)' : '');
			
			// Gather attachments
			$attachments = $attachment_table->invoice_attachments( $shipment );
			
			// 2nd param, inline_logo, true if pdf_enabled
			$output = $this->shipment_invoice( $shipment, $this->pdf_enabled() );
			
			if( $this->email_links )
				$output .= $this->links_to_attachments( $attachments );
			
			$queue_code = $this->queue->enqueue( $email_type, $shipment,
				$this->send_from(), $to, $cc, $subject, $output );
			
			if( $this->debug ) {
		echo "<pre>".__METHOD__.": output\n";
		var_dump($output);
		echo "</pre>";
			}

			if( $this->pdf_enabled ) {
				$pdf_output = $this->convert_html_to_pdf( $output );
				
				if( $pdf_output ) {
					$this->queue->update_pdf( $queue_code, $pdf_output );
					$shipment_table->update( $shipment, [
						'INVOICE_EMAIL_STATUS'	=> 'pdf-ok',
						'-INVOICE_EMAIL_DATE'	=> 'CURRENT_TIMESTAMP'
					], false);
					
					$result = $this->send_email_nobody( $to, $cc, $subject,
						$this->email_links ? false : $attachments, $pdf_output,
						$this->reference( $shipment, 'shipment') );
						
					$this->queue->update_sent( $queue_code, $result, $this->send_error );
					$shipment_table->update( $shipment, [
						'INVOICE_EMAIL_STATUS'	=> (isset($result) && $result ? 'sent' : 'send-error'),
						'-INVOICE_EMAIL_DATE'	=> 'CURRENT_TIMESTAMP'
					], false);
				} else {
					$this->queue->pdf_failed( $queue_code, $this->pdf_error );
					$shipment_table->update( $shipment, [
						'INVOICE_EMAIL_STATUS'	=> 'pdf-error',
						'-INVOICE_EMAIL_DATE'	=> 'CURRENT_TIMESTAMP'
					], false);
				}
			} else {
				$result = $this->send_email( $to, $cc, $subject,
					$output, $this->email_links ? false : $attachments );
					
				$this->queue->update_sent( $queue_code, $result, $this->send_error );
				$shipment_table->update( $shipment, [
					'INVOICE_EMAIL_STATUS'	=> ($result ? 'sent' : 'send-error'),
					'-INVOICE_EMAIL_DATE'	=> 'CURRENT_TIMESTAMP'
				], false);
			}
		}
		
		//! SCR# 719 - log email event
		$comment = 'Email '.$email_type.
			' To='.(empty($to) ? 'NULL' : $to).
			' Cc='.(empty($cc) ? 'NULL' : $cc).
			' ('.($result ? 'success' : 'failed').')';
		
		$shipment_table->add_shipment_status($shipment, $comment);
		
		return $result;
	}
	
	// First step of creating manifest, return true or false
	public function prepare_and_send_manifest( $load, $email_type ) {
		global $sts_crm_dir;
		$result = false;
		if( $this->debug ) echo "<p>".__METHOD__.": entry $load, $email_type</p>";
		
		require_once( "include/sts_load_class.php" );
		require_once( "sts_attachment_class.php" );
		if( $this->debug ) echo "<p>".__METHOD__.": after requires</p>";
		$load_table = sts_load::getInstance($this->database, $this->debug);
		$attachment_table = sts_attachment::getInstance($this->database, $this->debug);

		// 3 places to look for email address:
		// load table, CARRIER_EMAIL (previously set hopefully)
		// carrier, EMAIL_NOTIFY
		// contact, EMAIL
		$info = $load_table->fetch_rows($load_table->primary_key.' = '.$load,
			"DRIVER, CARRIER, CARRIER_BASE, CARRIER_EMAIL,
			CARRIER_NOTE, EMAIL_CC_USER,
			(SELECT FULLNAME FROM EXP_USER
			WHERE EXP_USER.USER_CODE = EXP_LOAD.EMAIL_CC_USER ) AS CC_FULLNAME,
			(SELECT EMAIL FROM EXP_USER
			WHERE EXP_USER.USER_CODE = EXP_LOAD.EMAIL_CC_USER ) AS CC_EMAIL,
			(SELECT EMAIL_NOTIFY FROM EXP_CARRIER
			WHERE EXP_CARRIER.CARRIER_CODE = EXP_LOAD.CARRIER ) AS CR_EMAIL_NOTIFY,
			(SELECT EMAIL FROM EXP_CONTACT_INFO
			WHERE CONTACT_SOURCE = 'carrier'
			AND CONTACT_TYPE = 'company'
			AND CONTACT_CODE = EXP_LOAD.CARRIER
			AND ISDELETED=false
			LIMIT 1) AS CR_EMAIL_CONTACT_INFO,
			(SELECT EMAIL_NOTIFY FROM EXP_DRIVER
			WHERE EXP_DRIVER.DRIVER_CODE = EXP_LOAD.DRIVER LIMIT 1) AS DR_EMAIL_NOTIFY,
		(SELECT EMAIL FROM EXP_CONTACT_INFO
			WHERE CONTACT_SOURCE = 'driver'
			AND CONTACT_CODE = EXP_LOAD.DRIVER
			AND ISDELETED=false
			LIMIT 1) AS DR_EMAIL_CONTACT_INFO" );
		
		if( is_array($info) && count($info) > 0 ) {
			
			if( isset($info[0]['DRIVER']) && $info[0]['DRIVER'] > 0 ) {
				$driver_email = isset($info[0]['DR_EMAIL_NOTIFY']) ?
					$info[0]['DR_EMAIL_NOTIFY'] :
						(isset($info[0]['DR_EMAIL_CONTACT_INFO']) ?
							$info[0]['DR_EMAIL_CONTACT_INFO'] : '' );
				
				if( $driver_email <> '' ) {		//! Send manifest to DRIVER
					if( $this->debug ) echo "<p>".__METHOD__.": Send manifest to DRIVER</p>";
		
					if( $this->email_enabled ) {
						$carrier_note = isset($info[0]['CARRIER_NOTE']) ? $info[0]['CARRIER_NOTE'] : '';
						
						//! SCR# 292 - include office number in subject
						$subject = $this->email_manifest_subject.' #'.
							$this->reference($load).
							($email_type == 'manifestr' ? ' (resend)' : '');
						
						$cc = $this->email_drmanifest_cc;
						if( isset($info[0]['EMAIL_CC_USER']) && $info[0]['EMAIL_CC_USER'] > 0 ) {
							$cc .= ($cc <> '' ? ', ' : '').$info[0]['CC_FULLNAME'].' <'.$info[0]['CC_EMAIL'].'>';
						}
						
						if( $this->debug ) echo "<p>".__METHOD__.": before from_office</p>";
						//! SCR# 307 - Rate confirmation email per office
						$this->from_office($load);
					
						//! SCR# 339 - include attachments
						$attachments = $attachment_table->manifest_attachments( $load );
	
						if( $this->debug ) echo "<p>".__METHOD__.": before load_confirmation</p>";
						// 5th param, inline_logo, true if pdf_enabled
						$output = $this->load_confirmation( $load, 'driver', 0, $carrier_note,
							$this->pdf_enabled() );
						
						if( $this->email_links() )
							$output .= $this->links_to_attachments( $attachments );
							
						$queue_code = $this->queue->enqueue( $email_type, $load,
							$this->send_from(), $driver_email, $cc, $subject, $output );

						//! SCR# 382 - encode PDF content
						if( $this->pdf_enabled() ) {
							$pdf_output = $this->convert_html_to_pdf( $output );
							
							if( $pdf_output ) {
								$this->queue->update_pdf( $queue_code, $pdf_output );
				
								$result = $this->send_email_nobody( $driver_email, $cc, $subject,
									$this->email_links() ? false : $attachments, $pdf_output,
									$this->reference( $load) );
									
								$this->queue->update_sent( $queue_code, $result, $this->send_error );
							} else {
								$this->queue->pdf_failed( $queue_code, $this->pdf_error );
							}
						} else {
							$result = $this->send_email( $driver_email, $cc, $subject, $output, $this->email_links() ? false : $attachments );

							$this->queue->update_sent( $queue_code, $result, $this->send_error );
						}
					}
					
				}
			} else {
				$carrier_email = isset($info[0]['CARRIER_EMAIL']) ? $info[0]['CARRIER_EMAIL'] : (
					isset($info[0]['CR_EMAIL_NOTIFY']) ? $info[0]['CR_EMAIL_NOTIFY'] : (
						isset($info[0]['CR_EMAIL_CONTACT_INFO']) ? $info[0]['EMAIL_CONTACT_INFO'] : '' ) );
				if( $carrier_email <> '' ) {		//! Send manifest to CARRIER
					if( $this->debug ) echo "<p>".__METHOD__.": Send manifest to DRIVER</p>";
					$carrier_base = isset($info[0]['CARRIER_BASE']) ? $info[0]['CARRIER_BASE'] : 0;
					$carrier_note = isset($info[0]['CARRIER_NOTE']) ? $info[0]['CARRIER_NOTE'] : '';
		
					if( $this->email_enabled ) {
						//! SCR# 292 - include office number in subject
						$subject = $this->email_manifest_subject.' #'.
							$this->reference($load).
							($email_type == 'manifestr' ? ' (resend)' : '');
						
						$cc = $this->email_crmanifest_cc;
						if( isset($info[0]['EMAIL_CC_USER']) && $info[0]['EMAIL_CC_USER'] > 0 ) {
							$cc .= ($cc <> '' ? ', ' : '').$info[0]['CC_FULLNAME'].' <'.$info[0]['CC_EMAIL'].'>';
						}
					
						//! SCR# 307 - Rate confirmation email per office
						$this->from_office($load);
					
						//! SCR# 339 - include attachments
						$attachments = $attachment_table->manifest_attachments( $load );
	
						// 5th param, inline_logo, true if pdf_enabled
						$output = $this->load_confirmation( $load, 'carrier', $carrier_base, $carrier_note,
							$this->pdf_enabled() );
						
						if( $this->email_links() )
							$output .= $this->links_to_attachments( $attachments );
							
						$queue_code = $this->queue->enqueue( $email_type, $load,
							$this->send_from(), $carrier_email, $cc, $subject, $output );

						//! SCR# 382 - encode PDF content
						if( $this->pdf_enabled() ) {
							$pdf_output = $this->convert_html_to_pdf( $output );
							
							if( $pdf_output ) {
								$this->queue->update_pdf( $queue_code, $pdf_output );
				
								$result = $this->send_email_nobody( $carrier_email, $cc, $subject,
									$this->email_links() ? false : $attachments, $pdf_output,
									$this->reference( $load) );
								$this->log_email_error( __METHOD__.": after send_email_nobody result = ".($result ? "true" : "false") );
									
								$this->queue->update_sent( $queue_code, $result, $this->send_error );
							} else {
								$this->queue->pdf_failed( $queue_code, $this->pdf_error );
							}
						} else {
							$result = $this->send_email( $carrier_email, $cc, $subject, $output, $this->email_links() ? false : $attachments );
							$this->log_email_error( __METHOD__.": after send_email result = ".($result ? "true" : "false") );

							$this->queue->update_sent( $queue_code, $result, $this->send_error );
						}
					}
					
					// Update load table, who we sent to and when
					$changes = array( 'CARRIER_BASE' => $carrier_base,
						'CARRIER_EMAIL' => $carrier_email,
						'CARRIER_MANIFEST_SENT' => date("Y-m-d H:i:s") );
							
					$result = $load_table->update( $load, $changes );
	
				}
			}
			
		}
		
		//! SCR# 719 - log email event
		$to = empty($driver_email) ? $carrier_email : $driver_email;
		
		$comment = 'Email '.$email_type.
			' To='.(empty($to) ? 'NULL' : $to).
			' Cc='.(empty($cc) ? 'NULL' : $cc).
			' ('.($result ? 'success' : 'failed').')';
		
		$load_table->add_load_status($load, $comment);

		$this->log_email_error( __METHOD__.": result = ".($result ? "true" : "false") );
		return $result;
	}

	public function prepare_and_send_attachment( $from, $to, $cc, $subject, $body, $code ) {
		global $sts_crm_dir;
		require_once( "sts_attachment_class.php" );
		$attachment_table = sts_attachment::getInstance($this->database, $this->debug);
				
		$result = false;
		$source_type = false;
		$source_code = 0;
		
		$origin = $attachment_table->origin( $code );
		//SOURCE_TYPE, SOURCE_CODE, FILE_NAME, STORED_AS
		
		if( is_array($origin) ) {
			$name = $origin['FILE_NAME'];
			$path = $origin['STORED_AS'];
			$source_type = $origin['SOURCE_TYPE'];
			$source_code = $origin['SOURCE_CODE'];
		
			$queue_code = $this->queue->enqueue( 'attachment', $code,
				$from, $to, $cc, $subject, $body );
					
			$result = $this->send_email_attachment( $from, $to, $cc, $subject, $body, $name, $path, $code );
			
			$this->queue->update_sent( $queue_code, $result, $this->send_error );
		}
		
		if( in_array($source_type, ['shipment', 'load']) ) {
			if( $source_type == 'shipment' )
				require_once( "sts_shipment_class.php" );
			else
				require_once( "include/sts_load_class.php" );

				$table = $source_type == 'shipment' ?
					sts_shipment::getInstance($this->database, $this->debug, $this->cli) :
					sts_load::getInstance($this->database, $this->debug);

			//! SCR# 719 - log email event
			$comment = 'Email attachment'.
				' To='.(empty($to) ? 'NULL' : $to).
				' Cc='.(empty($cc) ? 'NULL' : $cc).
				' ('.($result ? 'success' : 'failed').')';
			
			if( $source_type == 'shipment' )
				$table->add_shipment_status($source_code, $comment);
			else
				$table->add_load_status($source_code, $comment);
		}
		
		return $result;
	}

	//! SCR# 789 resend batch email as well
	public function resend_message( $queue_code ) {
		global $sts_crm_dir;
		require_once( "sts_attachment_class.php" );
		require_once( "sts_shipment_class.php" );
		require_once( "sts_load_class.php" );
		require_once( "sts_batch_invoice_class.php" );
		$attachment_table = sts_attachment::getInstance($this->database, $this->debug);
		$shipment_table = sts_shipment::getInstance($this->database, $this->debug, $this->cli);
		$batch_table = sts_batch_invoice::getInstance($this->database, $this->debug);
		$load_table = sts_load::getInstance($this->database, $this->debug);

		$result = false;
		if( $this->debug ) echo "<p>".__METHOD__.": queue_code = $queue_code</p>";

		if( isset($queue_code) && $queue_code > 0 ) {
			$check = $this->queue->fetch_rows("EMAIL_QUEUE_CODE = $queue_code",
				"EMAIL_TYPE, SOURCE_TYPE, SOURCE_CODE, EMAIL_FROM, EMAIL_TO, EMAIL_CC, EMAIL_SUBJECT, EMAIL_BODY, EMAIL_PDF, EMAIL_STATUS,
				(SELECT SOURCE_CODE
				FROM EXP_ATTACHMENT WHERE ATTACHMENT_CODE = EXP_EMAIL_QUEUE.SOURCE_CODE) AS SC" );
			
			if( is_array($check) && count($check) == 1 ) {
				$msg = $check[0];
				$email_type	= $msg["EMAIL_TYPE"];
				$source_type= $msg["SOURCE_TYPE"];
				$source_code= $msg["SOURCE_CODE"];
				$from		= $msg["EMAIL_FROM"];
				$to			= $msg["EMAIL_TO"];
				$cc			= $msg["EMAIL_CC"];
				$subject	= $msg["EMAIL_SUBJECT"].' [resend]';
				$status		= $msg["EMAIL_STATUS"];
				$output		= $msg["EMAIL_BODY"];
				if( ! empty($msg["SC"]))
					$asc	= $msg["SC"];
				$is_invoice = $source_type == 'shipment' &&
					in_array($email_type, ['invoice', 'invoicer']);
				$is_batch = $source_type == 'client' && $email_type = 'batch';
				$is_potato = false;
				
				if( ! empty($msg["EMAIL_PDF"]))
					$pdf_output	= base64_decode($msg["EMAIL_PDF"]);
				if( $this->debug ) echo "<p>".__METHOD__.": Found message in queue, status=$status email_type=$email_type souce_type=$source_type source_code=$source_code</p>";
				
				// Do we need to make a PDF of the body?
				if( $this->pdf_enabled &&
					! in_array($email_type, ['attachment', 'batch']) &&
					in_array($status, ['unsent', 'pdf-error']) &&
					! empty( $output ) ) {
					if( $this->debug ) echo "<p>".__METHOD__.": calling convert_html_to_pdf</p>";
						
					$pdf_output = $this->convert_html_to_pdf( $output );

					if( $pdf_output ) {
						if( $this->debug ) echo "<p>".__METHOD__.": Convert to PDF worked</p>";
						$this->queue->update_pdf( $queue_code, $pdf_output );
						if( $source_type == 'shipment' ) {
							$shipment_table->update( $source_code, [
								'INVOICE_EMAIL_STATUS'	=> 'pdf-ok',
								'-INVOICE_EMAIL_DATE'	=> 'CURRENT_TIMESTAMP'
							], false);
						}
						
						$status = 'pdf-ok';
					} else {
						if( $this->debug ) echo "<p>".__METHOD__.": Convert to PDF failed</p>";
						$this->queue->pdf_failed( $queue_code, $this->pdf_error );
						if( $source_type == 'shipment' ) {
							$shipment_table->update( $source_code, [
								'INVOICE_EMAIL_STATUS'	=> 'pdf-error',
								'-INVOICE_EMAIL_DATE'	=> 'CURRENT_TIMESTAMP'
							], false);
						}
					}
				}
				
	//	if( $this->debug ) echo "<h3>".__METHOD__.": JJK9 ".$this->email_from_name, $this->email_from_address."</h3>";
				
				// Time to send it
				if( in_array($status, ['pdf-ok', 'send-error', 'sent']) ) {
					
					//! SCR# 307 - Rate confirmation email per office
					if( $source_type == 'load' )
						$this->from_office($source_code);
					
	//	if( $this->debug ) echo "<h3>".__METHOD__.": JJK9a ".$this->email_from_name, $this->email_from_address."</h3>";
				
					// Gather attachments
					if( $source_type == 'shipment' )
						$attachments = $attachment_table->invoice_attachments( $source_code );
					else if( $source_type == 'load' )
						$attachments = $attachment_table->manifest_attachments( $source_code );
					else if( $is_batch )
						$attachments = $batch_table->batch_attachments( $batch_table->batch_shipments( $source_code ) );
					else
						$attachments = false;
					
					if( $email_type == 'attachment' ) {
						$origin = $attachment_table->origin( $source_code );
						
						if( is_array($origin) ) {
							$name = $origin['FILE_NAME'];
							$path = $origin['STORED_AS'];
							$result = $this->send_email_attachment( $from, $to, $cc,
								$subject, $output, $name, $path, $source_code );
							
							$this->queue->update_sent( $queue_code, $result, $this->send_error );
						}
					} else if( $pdf_output ) { //! RESEND HERE
						$result = $this->send_email_nobody( $to, $cc, $subject,
							$this->email_links ? false : $attachments, $pdf_output,
							$this->reference( $source_code, $source_type ) );
						if( $this->debug ) echo "<p>".__METHOD__.": Send PDF ".($result ? "OK" : "failed")."</p>";
						$this->queue->update_sent( $queue_code, $result, $this->send_error.'<br>'.$this->generateCallTrace() );
						if( $source_type == 'shipment' ) {
							$shipment_table->update( $source_code, [
								'INVOICE_EMAIL_STATUS'	=> ($result ? 'sent' : 'send-error'),
								'-INVOICE_EMAIL_DATE'	=> 'CURRENT_TIMESTAMP'
							], false);
						}
					} else {
						$result = $this->send_email( $to, $cc, $subject,
							$output, $this->email_links ? false : $attachments );
						if( $this->debug ) echo "<p>".__METHOD__.": Send HTML ".($result ? "OK" : "failed")."</p>";
						$this->queue->update_sent( $queue_code, $result, $this->send_error.'<br>'.$this->generateCallTrace() );
						if( $source_type == 'shipment' ) {
							$shipment_table->update( $source_code, [
								'INVOICE_EMAIL_STATUS'	=> ($result ? 'sent' : 'send-error'),
								'-INVOICE_EMAIL_DATE'	=> 'CURRENT_TIMESTAMP'
							], false);
						} else if( $is_batch ) {
							// update status for all shipments
							$shipment_table->update( 'SHIPMENT_CODE IN ('.
								implode(',', $batch_table->batch_shipments( $source_code ) ).')',
								[
									'INVOICE_EMAIL_STATUS'	=> ($result ? 'sent' : 'send-error'),
									'-INVOICE_EMAIL_DATE'	=> 'CURRENT_TIMESTAMP'
								], false);
						}
					}
					
					if( $email_type == 'attachment' ) {
						$comment = 'Resend Email '.$email_type.
							' To='.(empty($to) ? 'NULL' : $to).
							' Cc='.(empty($cc) ? 'NULL' : $cc).
							' ('.($result ? 'success' : 'failed').')';
					
						if( $source_type == 'shipment' )
							$shipment_table->add_shipment_status($asc, $comment);
						else if( $source_type == 'load' )
							$load_table->add_load_status($asc, $comment);
					} else if( $source_type == 'shipment' ) {
						$comment = 'Resend Email '.$email_type.
							' To='.(empty($to) ? 'NULL' : $to).
							' Cc='.(empty($cc) ? 'NULL' : $cc).
							' ('.($result ? 'success' : 'failed').')';
						
						$shipment_table->add_shipment_status($source_code, $comment);
					} else if( $source_type == 'load' ) {
						$comment = 'Resend Email '.$email_type.
							' To='.(empty($to) ? 'NULL' : $to).
							' Cc='.(empty($cc) ? 'NULL' : $cc).
							' ('.($result ? 'success' : 'failed').')';
						
						$shipment_table->add_load_status($source_code, $comment);
					}
					
				}
			}
		}

		return $result;
	}
	
	public function view_queued( $queue_code ) {
		if( isset($queue_code) && $queue_code > 0 ) {
			$check = $this->queue->fetch_rows("EMAIL_QUEUE_CODE = $queue_code",
				"EMAIL_TYPE, SOURCE_TYPE, SOURCE_CODE, EMAIL_FROM, EMAIL_TO, EMAIL_CC, EMAIL_SUBJECT, EMAIL_BODY, EMAIL_PDF, EMAIL_STATUS, CREATED_DATE" );
			
			if( is_array($check) && count($check) == 1 ) {
				if( $this->debug ) echo "<p>".__METHOD__.": Found message in queue</p>";
				$msg = $check[0];
				$email_type	= $msg["EMAIL_TYPE"];
				$source_type= $msg["SOURCE_TYPE"];
				$source_code= $msg["SOURCE_CODE"];
				$from		= $msg["EMAIL_FROM"];
				$to			= $msg["EMAIL_TO"];
				$cc			= $msg["EMAIL_CC"];
				$subject	= $msg["EMAIL_SUBJECT"];
				$status		= $msg["EMAIL_STATUS"];
				$body		= $msg["EMAIL_BODY"];
				$sent		= $msg["CREATED_DATE"];
				

				$new_body = '';
				if( preg_match("/(.*?)(<img [^\>]*\>)(.*)/s", $body, $match) ) {
					$before = $match[1];
					$current = $match[2];
					$after = $match[3];
		
					if( preg_match('/src=\"([^\"]*)\"/', $current, $match2) ) {
						$search = $match2[1];
						
						if( substr($search, 0, 5) != 'data:' ) {
							$replace = $this->mime_encocde_logo( $search );
							$current = str_replace($search, $replace, $current );
							
							$body = $before.$current.$after;
						}
					}
				}
				
				echo "<h2>Message# $queue_code, type $email_type, sent $sent, status $status</h2>
				<br>
				<div class=\"well\">
				<div class=\"panel panel-default\">
				<div class=\"panel-heading\">
					<h3>From: ".htmlentities($from)."</h3>
					<h3>To: $to</h3>
					<h3>Cc: $cc</h3>
					<h3>Subject: $subject</h3>
				";
				
				if(! empty($msg["EMAIL_PDF"]) )
					echo '<h3>A PDF version was sent <a class="btn btn-success" target="_blank" href="exp_viewemail_pdf.php?CODE='.
						$queue_code.'"><span class="glyphicon glyphicon-eye-open"></span> View PDF Version</a></h3>';

				echo "</div>".
				$body
				.($email_type == 'attachment' ? '<br><br><h3><a class="btn btn-success" href="exp_viewattachment.php?CODE='.$source_code.'" target="_blank"><span class="glyphicon glyphicon-eye-open"></span> View Attachment</a></h3>' : '');
				
				if( $email_type == 'batch' ) {
					require_once( "sts_batch_invoice_class.php" );
					$batch_table = sts_batch_invoice::getInstance($this->database, $this->debug);
					$attachments = $batch_table->batch_attachments( $batch_table->batch_shipments( $source_code ) );
					if( is_array($attachments) && count($attachments) > 0 ) {
						foreach($attachments as $row) {
							echo '<h3><a class="btn btn-success" href="exp_viewattachment.php?CODE='.$row["ATTACHMENT_CODE"].'" target="_blank"><span class="glyphicon glyphicon-eye-open"></span> View Attachment '.$row["FILE_NAME"].'</a></h3>';
						}
					}
					
				}
				
				echo "
				</div>
				</div>
				</div>";
			}
		}
	}
	
	public function view_queued_pdf( $queue_code ) {
		if( isset($queue_code) && $queue_code > 0 ) {
			$check = $this->queue->fetch_rows("EMAIL_QUEUE_CODE = $queue_code",
				"SOURCE_TYPE, SOURCE_CODE, EMAIL_PDF" );
			
			if( is_array($check) && count($check) == 1 ) {
				if( $this->debug ) echo "<p>".__METHOD__.": Found message in queue</p>";
				$msg = $check[0];
				if(! empty($msg["EMAIL_PDF"]) ) {
					$source_type= $msg["SOURCE_TYPE"];
					$source_code= $msg["SOURCE_CODE"];
					$pdf = base64_decode($msg["EMAIL_PDF"]);
					$disp = 'inline';
					$type = 'application/pdf';
					$file_name = $source_type.'_'.$source_code.'.pdf';

					if( $this->debug ) {
						echo "<pre>";
						var_dump('Content-Type: '.$type );
						var_dump('Content-Length: ' . strlen($pdf));
						var_dump('Content-Disposition: '.$disp.'; filename="'.$file_name.'"');
						var_dump($pdf);
						echo "</pre>";
					} else {
						ob_clean();
						header('Content-Type: '.$type);
						header('Content-Length: ' . strlen($pdf));
						header('Content-Disposition: '.$disp.'; filename="'.$file_name.'"');
						echo $pdf;
					}
					
				}
			}
		}
	}
}

//! SCR# 712 - email queuing
class sts_email_queue extends sts_table {

	private $setting;
	private $email_queueing;
	private $queue_expire;

	// Constructor does not need the table name
	public function __construct( $database, $debug = false ) {

		$this->debug = $debug;
		$this->primary_key = "EMAIL_QUEUE_CODE";
		if( $this->debug ) echo "<p>Create sts_email_queue</p>";
		parent::__construct( $database, EMAIL_QUEUE_TABLE, $debug);

		$this->setting = sts_setting::getInstance( $this->database, $this->debug );
		$this->email_queueing = $this->setting->get( 'option', 'EMAIL_QUEUEING' ) == 'true';
		$this->queue_expire = $this->setting->get( 'option', 'EMAIL_QUEUE_EXPIRE' );
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
    
    public function enabled() {
	    return $this->email_queueing;
    }
    
    //! SCR# 825 - Resend all queued messages
	// return array of queue codes
    public function all_errors() {
	    $result = [];
		$check = $this->fetch_rows("EMAIL_STATUS IN ('pdf-error', 'send-error')",
			"EMAIL_QUEUE_CODE");
		if( is_array($check) && count($check) > 0 ) {
			foreach($check as $row) {
				$result[] = $row['EMAIL_QUEUE_CODE'];
			}
		}
		
		return $result;
    }
    
    private function get_source_type( $email_type, $code ) {
	    switch( $email_type ) {
			case 'attachment':	//! Attachment
				require_once( "sts_attachment_class.php" );
				$attachment_table = sts_attachment::getInstance($this->database, $this->debug);
				$check = $attachment_table->origin( $code );
				
				$source_type = $check['SOURCE_TYPE'];
				break;
				
			case 'activity':	//! Calendar reminder - client activity
			case 'batch':		//! SCR# 789 - Batch invoice
				$source_type = 'client';
				break;
				
			case 'scr':			//! Announce SCR
				$source_type = 'scr';
				break;
				
			case 'manifest':	//! Send manifest
			case 'manifestr':	//! Resend manifest
				$source_type = 'load';
				break;
				
			case 'newcarrier':	//! New carrier announcement
			case 'carrierins':	//! Carrier insurance announcement
				$source_type = 'carrier';
				break;
			case 'invoice':	//! Send invoice
			case 'invoicer':	//! Resend invoice
			default:
				$source_type = 'shipment';
				break;
		}
		
		if( $this->debug ) echo "<p>".__METHOD__.": email_type = $email_type, source_type = $source_type</p>";
		return $source_type;
    }
    
	public function recent_errors() {
		$result = '';
		if( $this->email_queueing && in_group( EXT_GROUP_ADMIN ) ) {
			$duration = 3;
			$check = $this->database->get_one_row( "SELECT COUNT(*) NUM
				FROM EXP_EMAIL_QUEUE
				WHERE CREATED_DATE > DATE_SUB(NOW(), INTERVAL $duration DAY)
				AND EMAIL_STATUS IN ('pdf-error', 'send-error')");
			
			if( is_array($check) && isset($check["NUM"]) && $check["NUM"] > 0 ) {
				$result = '<span style="float: right;"><a class="btn btn-sm btn-primary" href="exp_listemail_queue.php?errors"><span class="glyphicon glyphicon-warning-sign"></span> <span class="glyphicon glyphicon-envelope"></span> '.plural($check["NUM"], 'Email').' Failed in last '.$duration.' days.</a></span>';
			}
		}
		
		return $result;
	}
	
	//! Check for email sent
	public function shipment_emails( $shipment ) {
		$status = 'unsent';
		$when = '';
		
		// First look in shipment table
		$check = $this->database->get_one_row( "SELECT INVOICE_EMAIL_STATUS, INVOICE_EMAIL_DATE
			FROM EXP_SHIPMENT WHERE SHIPMENT_CODE = $shipment");
		
		if( is_array($check) && isset($check["INVOICE_EMAIL_DATE"]) ) {
			$status = $check["INVOICE_EMAIL_STATUS"];
			$when = $check["INVOICE_EMAIL_DATE"];
		} else {
			$check2 = $this->fetch_rows("SOURCE_CODE = $shipment
				AND SOURCE_TYPE = 'shipment' AND EMAIL_TYPE IN('invoice', 'invoicer')",
				"EMAIL_STATUS, CREATED_DATE", "", "1");
			
			if( is_array($check2) && count($check2) == 1 &&
				isset($check2[0]["CREATED_DATE"]) ) {
				$status = $check2[0]["EMAIL_STATUS"];
				$when = $check2[0]["CREATED_DATE"];
				
				// Update shipment table
				$this->database->get_one_row("UPDATE EXP_SHIPMENT
					SET INVOICE_EMAIL_STATUS = '".$status."', INVOICE_EMAIL_DATE = '".$when."'
					WHERE SHIPMENT_CODE = $shipment");
			}
		}
		
		if( $status == 'unsent' )
			$output = ' (No invoice sent by email)';
		else 
			$output = " (Invoice sent by email, status=$status, $when)";
		
		return $output;
	}
	
	public function expire() {
		return $this->database->get_one_row( "DELETE FROM EXP_EMAIL_QUEUE
			WHERE DATEDIFF(NOW(),CREATED_DATE) > ".$this->queue_expire."
			AND EMAIL_STATUS = 'sent'");
	}

    //! Put a new message into the queue
    public function enqueue( $email_type, $code, $from, $to, $cc, $subject, $body ) {
		$result = false;
		if( $this->email_queueing ) {
			$result = $this->add( [
			    'EMAIL_TYPE'	=> $email_type,
			    'SOURCE_TYPE'	=> $this->get_source_type( $email_type, $code ),
			    'SOURCE_CODE'	=> $code,
			    'EMAIL_FROM'	=> $from,
			    'EMAIL_TO'		=> $to,
			    'EMAIL_CC'		=> $cc,
			    'EMAIL_SUBJECT'	=> $subject,
			    'EMAIL_BODY'	=> $body,
			    'EMAIL_STATUS'	=> 'unsent'
		    ] );
	    }
	    
	    return $result;
    }
    
	//! Update queue entry when PDF conversion worked
	public function update_pdf( $queue_code, $pdf_output ) {
		$result = false;
		if( $this->email_queueing && isset($queue_code) && $queue_code > 0 ) {
			$result = $this->update( $queue_code, [
				'EMAIL_STATUS'	=> 'pdf-ok',
				'EMAIL_PDF'	=> base64_encode($pdf_output)
			]);
		}
		
		return $result;
	}
       
    //! Update queue entry when PDF conversion failed
    public function pdf_failed( $queue_code, $diag ) {
		$result = false;
		if( $this->email_queueing && isset($queue_code) && $queue_code > 0 ) {
		    $result = $this->update( $queue_code, [
			    'EMAIL_STATUS'	=> 'pdf-error',
			    'EMAIL_DIAG'	=> $diag,
			    '-EMAIL_ATTEMPTS' => 'EMAIL_ATTEMPTS + 1'
		    ]);
	    }
 	    
	    return $result;
   }
    
    //! Update queue entry when send failed
    public function send_failed( $queue_code, $diag ) {
		$result = false;
		if( $this->email_queueing && isset($queue_code) && $queue_code > 0 ) {
		    $result = $this->update( $queue_code, [
			    'EMAIL_STATUS'	=> 'send-error',
			    'EMAIL_DIAG'	=> $diag,
			    '-EMAIL_ATTEMPTS' => 'EMAIL_ATTEMPTS + 1'
		    ]);
	    }
 	    
	    return $result;
   }
    
    //! Update queue entry when sent
    public function sent( $queue_code ) {
		$result = false;
		if( $this->email_queueing && isset($queue_code) && $queue_code > 0 ) {
		    $result = $this->update( $queue_code, [
			    'EMAIL_STATUS'	=> 'sent',
			    'EMAIL_DIAG'	=> '',
			    '-EMAIL_ATTEMPTS' => 'EMAIL_ATTEMPTS + 1'
		    ]);
	    }
 	    
	    return $result;
   }
    
    //! Shortcut after send attempt
    public function update_sent( $queue_code, $outcome, $diag ) {
 	    return $outcome ? $this->sent( $queue_code ) : $this->send_failed( $queue_code, $diag );
   }
    
}

$sts_email_qb_alert_boilerplate = '
<p>Exspeedite is having trouble connecting to QuickBooks to export invoices and bills. Please can you take a look to see if you can resolve the issue.</p>

<p>On the server where QuickBooks is installed, please first check that it is running.</p>
<p>Also check it is running in multi-user mode. Sometimes it is necessary to run it in single-user mode, and perhaps it was left in that state.</p>
<p>If the above are ok, and the problem persists, please note we are getting additional information sent to Strong Tower via e-mail, and we will investigate.</p>
<p>Additional information that may be of help follows:</p>
<br>
';

$sts_email_manifest_boilerplate = '
<br>
<p>***All charges Except Lumper fees are Included in the above rate.<br>
The carrier is responsible for payment of lumper charge upon delivery.<br>
We will reimburse for lumper charges with Pre-approval and copy of receipt.</p>

<hr>
<p>Please have Driver call <strong>800-524-2702</strong> for Dispatch. Under no circumstance should dispatchers or drivers
contact our customers. Night emergency number <strong>856-297-8904</strong>.</p>
<hr>
<ul>
<li>Product is to be counted before signing the Bill of Lading for pickup & delivery. Must report any
Shortages/Overage Immediately.</li>
<li>Driver must call if there is any unloading or loading required.</li>
<li>NO Double Brokering allowed & will not be paid.</li>
<li>Damaged product (CALL IMMEDIATELY) must be noted on B/Land signed by customer/consignee
for payment.</li>
<li>Detention (CALL IMMEDIATELY) No detention to be paid for late pickup or delivery.</li>
<li>Missing scheduled appointment times will be subject to actual fines imposed with a minimum of $50.00.</li>
<li>No idling at any facilities. In the event of any fines imposed your company will be held responsible.</li>
</ul>

<hr>
<p>Payment of Freight Charges require the following Items:<br>
Signed Original Bill of Lading --------------$25 .00 will be charged if not sent<br>
Certificates of Insurance. Signed Carrier Agreement. Signed rate Confirmation. W-9 Tax Information</p>
';

//! Layout Specifications - For use with sts_result

$sts_result_queue_layout = array(
	'EMAIL_QUEUE_CODE' => array( 'format' => 'hidden' ),
	'SOURCE_CODE' => array( 'format' => 'hidden' ),
	'SOURCE_TYPE' => array( 'format' => 'hidden' ),
	'CREATED_DATE' => array( 'label' => 'Sent', 'format' => 'datetime' ),
	'EMAIL_TYPE' => array( 'label' => 'Type', 'format' => 'text'),
	'EMAIL_HEADER' => array( 'label' => 'Header', 'format' => 'text',
		'snippet' => "concat('From: ', replace(replace(EMAIL_FROM, '<', '&lt;'), '>','&gt;'),
			'<br>To: ', replace(replace(EMAIL_TO, '<', '&lt;'), '>','&gt;'),
			'<br>Cc: ', replace(replace(EMAIL_CC, '<', '&lt;'), '>','&gt;'),
			'<br>Subject: ', EMAIL_SUBJECT)"
	),
//	'EMAIL_CC' => array( 'label' => 'Cc', 'format' => 'text'),
	'EMAIL_SUBJECT' => array( 'label' => 'Subject', 'format' => 'hidden'),
	'EMAIL_STATUS' => array( 'label' => 'Status', 'format' => 'text'),
	'EMAIL_DIAG' => array( 'label' => 'Diag', 'format' => 'text'),

//	'CREATED_BY' => array( 'label' => 'By', 'format' => 'table',
//		'table' => USER_TABLE, 'key' => 'USER_CODE', 'fields' => 'USERNAME', 'link' => 'exp_edituser.php?CODE=' ),
	//'CHANGED_DATE' => array( 'label' => 'Updated', 'format' => 'datetime' )

);

$sts_result_full_queue_layout = array(
	'EMAIL_QUEUE_CODE' => array( 'format' => 'hidden' ),
	'CREATED_DATE' => array( 'label' => 'Sent', 'format' => 'datetime' ),
	'SOURCE_TYPE' => array( 'label' => 'Source', 'format' => 'text',
		'tip' => 'This is the object the email is related to. It can be one
		of client, scr, shipment, load, carrier. You can filter this column above.' ),
	'SOURCE_CODE' => array( 'label' => 'Code', 'format' => 'text',
		'snippet' => "case when SOURCE_TYPE = 'client' then
			concat('<a href=\"exp_editclient.php?CODE=', if(EMAIL_TYPE = 'attachment', (SELECT EXP_ATTACHMENT.SOURCE_CODE FROM EXP_ATTACHMENT
WHERE ATTACHMENT_CODE = EXP_EMAIL_QUEUE.SOURCE_CODE LIMIT 1), SOURCE_CODE), '\">', if(EMAIL_TYPE = 'attachment', (SELECT EXP_ATTACHMENT.SOURCE_CODE FROM EXP_ATTACHMENT
WHERE ATTACHMENT_CODE = EXP_EMAIL_QUEUE.SOURCE_CODE LIMIT 1), SOURCE_CODE), '</a>')
		when SOURCE_TYPE = 'scr' then
			concat('<a href=\"exp_editscr.php?CODE=', if(EMAIL_TYPE = 'attachment', (SELECT EXP_ATTACHMENT.SOURCE_CODE FROM EXP_ATTACHMENT
WHERE ATTACHMENT_CODE = EXP_EMAIL_QUEUE.SOURCE_CODE LIMIT 1), SOURCE_CODE), '\">', if(EMAIL_TYPE = 'attachment', (SELECT EXP_ATTACHMENT.SOURCE_CODE FROM EXP_ATTACHMENT
WHERE ATTACHMENT_CODE = EXP_EMAIL_QUEUE.SOURCE_CODE LIMIT 1), SOURCE_CODE), '</a>')
		when SOURCE_TYPE = 'shipment' then
			concat('<a href=\"exp_addshipment.php?CODE=', if(EMAIL_TYPE = 'attachment', (SELECT EXP_ATTACHMENT.SOURCE_CODE FROM EXP_ATTACHMENT
WHERE ATTACHMENT_CODE = EXP_EMAIL_QUEUE.SOURCE_CODE LIMIT 1), SOURCE_CODE), '\">', if(EMAIL_TYPE = 'attachment', (SELECT EXP_ATTACHMENT.SOURCE_CODE FROM EXP_ATTACHMENT
WHERE ATTACHMENT_CODE = EXP_EMAIL_QUEUE.SOURCE_CODE LIMIT 1), SOURCE_CODE), '</a>')
		when SOURCE_TYPE = 'load' then
			concat('<a href=\"exp_viewload.php?CODE=', if(EMAIL_TYPE = 'attachment', (SELECT EXP_ATTACHMENT.SOURCE_CODE FROM EXP_ATTACHMENT
WHERE ATTACHMENT_CODE = EXP_EMAIL_QUEUE.SOURCE_CODE LIMIT 1), SOURCE_CODE), '\">', if(EMAIL_TYPE = 'attachment', (SELECT EXP_ATTACHMENT.SOURCE_CODE FROM EXP_ATTACHMENT
WHERE ATTACHMENT_CODE = EXP_EMAIL_QUEUE.SOURCE_CODE LIMIT 1), SOURCE_CODE), '</a>')
		when SOURCE_TYPE = 'carrier' then
			concat('<a href=\"exp_editcarrier.php?CODE=', if(EMAIL_TYPE = 'attachment', (SELECT EXP_ATTACHMENT.SOURCE_CODE FROM EXP_ATTACHMENT
WHERE ATTACHMENT_CODE = EXP_EMAIL_QUEUE.SOURCE_CODE LIMIT 1), SOURCE_CODE), '\">', if(EMAIL_TYPE = 'attachment', (SELECT EXP_ATTACHMENT.SOURCE_CODE FROM EXP_ATTACHMENT
WHERE ATTACHMENT_CODE = EXP_EMAIL_QUEUE.SOURCE_CODE LIMIT 1), SOURCE_CODE), '</a>')
		else SOURCE_CODE end", 'tip' => 'If you click on a link, it can take you to the related object.'
	 ),
	 'REFERENCE' => array( 'label' => 'Ref', 'format' => 'text',
		 'snippet' => "case when SOURCE_TYPE = 'load' then
		 	(select COALESCE(SS_NUMBER,'') FROM EXP_SHIPMENT
			WHERE LOAD_CODE = if(EMAIL_TYPE = 'attachment', (SELECT EXP_ATTACHMENT.SOURCE_CODE FROM EXP_ATTACHMENT
WHERE ATTACHMENT_CODE = EXP_EMAIL_QUEUE.SOURCE_CODE LIMIT 1), SOURCE_CODE)
			ORDER BY SHIPMENT_CODE ASC
			LIMIT 1)
		 when SOURCE_TYPE = 'shipment' then
		 	(SELECT COALESCE(SS_NUMBER,'') FROM EXP_SHIPMENT
		 	WHERE SHIPMENT_CODE = if(EMAIL_TYPE = 'attachment', (SELECT EXP_ATTACHMENT.SOURCE_CODE FROM EXP_ATTACHMENT
WHERE ATTACHMENT_CODE = EXP_EMAIL_QUEUE.SOURCE_CODE LIMIT 1), SOURCE_CODE))
		 else NULL end", 'tip' => 'Office Code'
	 ),
	'EMAIL_TYPE' => array( 'label' => 'Type', 'format' => 'text'),
	'EMAIL_HEADER' => array( 'label' => 'Header', 'format' => 'text',
		'snippet' => "concat('From: ', replace(replace(EMAIL_FROM, '<', '&lt;'), '>','&gt;'),
			'<br>To: ', replace(replace(EMAIL_TO, '<', '&lt;'), '>','&gt;'),
			'<br>Cc: ', replace(replace(EMAIL_CC, '<', '&lt;'), '>','&gt;'),
			'<br>Subject: ', EMAIL_SUBJECT)", 'tip' => 'From, To, Cc, Subject'
	),
//	'EMAIL_CC' => array( 'label' => 'Cc', 'format' => 'text'),
	'EMAIL_SUBJECT' => array( 'label' => 'Subject', 'format' => 'hidden'),
	'EMAIL_STATUS' => array( 'label' => 'Status', 'format' => 'text',
		'tip' => 'You can filter this column above.'),
	'EMAIL_DIAG' => array( 'label' => 'Diag', 'format' => 'text',
		'tip' => 'Diagnostics for failures.'),

//	'CREATED_BY' => array( 'label' => 'By', 'format' => 'table',
//		'table' => USER_TABLE, 'key' => 'USER_CODE', 'fields' => 'USERNAME', 'link' => 'exp_edituser.php?CODE=' ),
	//'CHANGED_DATE' => array( 'label' => 'Updated', 'format' => 'datetime' )

);

//! Edit/Delete Button Specifications - For use with sts_result

$sts_result_queue_edit = array(
	'title' => '<span class="glyphicon glyphicon-envelope"></span> Sent Emails',
	'sort' => 'CREATED_DATE asc',
	//'popup' => true,
	//'cancel' => 'index.php',
	//'add' => 'exp_addattachment.php',
	//'actionextras' => 'disabled',
	//'addbutton' => 'Add Attachment',
	//'cancelbutton' => 'Back',
	'rowbuttons' => array(
		array( 'url' => 'exp_viewemail.php?CODE=', 'key' => 'EMAIL_QUEUE_CODE', 'label' => 'EMAIL_SUBJECT', 'tip' => 'View email ', 'icon' => 'glyphicon glyphicon-eye-open', 'target' => '_blank' ),
		array( 'url' => 'exp_resend_email.php?CODE=', 'key' => 'EMAIL_QUEUE_CODE', 'label' => 'EMAIL_SUBJECT', 'tip' => 'Resend email ', 'icon' => 'glyphicon glyphicon-arrow-right' ),
		array( 'url' => 'exp_editemail_to.php?CODE=', 'key' => 'EMAIL_QUEUE_CODE', 'label' => 'EMAIL_SUBJECT', 'tip' => 'Edit To: Address ', 'icon' => 'glyphicon glyphicon-pencil' ),
		array( 'url' => 'exp_delete_email.php?CODE=', 'key' => 'EMAIL_QUEUE_CODE', 'label' => 'EMAIL_SUBJECT', 'tip' => 'Delete email ', 'icon' => 'glyphicon glyphicon-trash', 'confirm' => 'yes' )
	)
);

?>