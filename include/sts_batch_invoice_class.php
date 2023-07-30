<?php

// $Id: sts_batch_invoice_class.php 5030 2023-04-12 20:31:34Z duncan $
//! SCR# 789 - Batch Invoice class

// no direct access
defined('_STS_INCLUDE') or die('Restricted access');

require_once( "sts_table_class.php" );

require_once( "sts_setting_class.php" );
require_once( "sts_email_class.php" );
require_once( "sts_attachment_class.php" );
require_once( "sts_shipment_class.php" );

class sts_batch_invoice extends sts_table {

	private $setting_table;
	private $attachment_table;
	private $max_email_size;
	private $attachment_type;
	private $available_to_send = 0;
	private $missing_attachments = 0;
	private $attachment_too_big = 0;
	private $email_cc;
	private $batch_subject;
	private $date_attachment;

	public $email;
	public $queue;	//! SCR# 712 - email queuing
	public $pdf_error;
	public $send_error;

	// Constructor does not need the table name
	public function __construct( $database, $debug = false ) {

		$this->debug = $debug;
		$this->primary_key = "BATCH_CODE";
		if( $this->debug ) echo "<p>Create sts_batch_invoice</p>";

		$this->setting_table = sts_setting::getInstance($database, $debug);
		$this->max_email_size = (int) $this->setting_table->get( 'option', 'BATCH_INV_MAX_SIZE' );
		$this->attachment_type = $this->setting_table->get( 'option', 'BATCH_INV_ATTACHMENT' );
		$this->email_cc = $this->setting_table->get( 'email', 'EMAIL_INVOICE_CC' );
		$this->batch_subject = $this->setting_table->get( 'option', 'EMAIL_BATCH_SUBJECT' );
		$this->date_attachment = $this->setting_table->get( 'option', 'BATCH_INV_DATE_ATTACHMENT' ) == 'true';

		$this->email = sts_email::getInstance( $this->database, $this->debug );
		$this->queue = sts_email_queue::getInstance( $this->database, $this->debug );

		parent::__construct( $database, BATCH_INVOICE_TABLE, $debug);
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
    
    public function client_menu( $selected = false, $id = 'CLIENT_CODE', $match = '', $onchange = true ) {
		$select = false;

		$choices = $this->database->get_multiple_rows("
			SELECT CLIENT_CODE, CLIENT_NAME,
				EXISTS (SELECT C.EMAIL
				FROM EXP_CONTACT_INFO C
				WHERE C.CONTACT_CODE = CLIENT_CODE
				AND C.CONTACT_SOURCE = 'client'
				AND C.CONTACT_TYPE = 'bill_to'
				AND C.EMAIL <> ''
				LIMIT 1) AS HAS_EMAIL
			FROM EXP_CLIENT
			WHERE BATCH_EMAIL
			AND ISDELETED = FALSE");

		if( is_array($choices) && count($choices) > 0) {
			
			$select = '<select class="form-control input-lg" name="'.$id.'" id="'.$id.'"'.($onchange ? ' onchange="form.submit();"' : '').'>
			';
			$select .= '<option value="None">Select Client</option>
			';
			
			foreach( $choices as $row ) {
				$select .= '<option value="'.$row["CLIENT_CODE"].'"';
				if( $selected && $selected == $row["CLIENT_CODE"] )
					$select .= ' selected';
				if( ! $row["HAS_EMAIL"] )
					$select .= ' disabled';
				$select .= '>'.$row["CLIENT_NAME"].
					($row["HAS_EMAIL"] ? ' (has email)' : ' (email not found)').'</option>
				';
			}
			$select .= '</select>';
		} else {
			$select = 'There are no clients with Batch Email enabled.';
		}
			
		return $select;
	}
	
	public function max_email_size() {
		return $this->max_email_size;
	}

	public function email_cc() {
		return $this->email_cc;
	}

	public function attachment_type() {
		return $this->attachment_type;
	}

	public function available_to_send() {
		return $this->available_to_send;
	}

 	public function missing_attachments() {
		return $this->missing_attachments;
	}

 	public function attachment_too_big() {
		return $this->attachment_too_big;
	}

   public function client_name( $client ) {
		$result = 'ERROR: No client';
		
		$check = $this->database->get_one_row("
			SELECT CLIENT_CODE, CLIENT_NAME,
				EXISTS (SELECT C.EMAIL
				FROM EXP_CONTACT_INFO C
				WHERE C.CONTACT_CODE = CLIENT_CODE
				AND C.CONTACT_SOURCE = 'client'
				AND C.CONTACT_TYPE = 'bill_to'
				AND C.EMAIL <> ''
				LIMIT 1) AS HAS_EMAIL
			FROM EXP_CLIENT
			WHERE CLIENT_CODE = $client");
			
		if( is_array($check) && isset($check['CLIENT_NAME']) )
			$result = $check['CLIENT_NAME'].($check["HAS_EMAIL"] ? ' (has email)' : ' (email not found)');
		
		if( $this->debug ) echo "<p>".__METHOD__.": client = $client, result = ".$result."</p>";
		return $result;
	}
	
	public function dt( $x ) {
		return isset($x) ? str_replace(' ', '&nbsp;', date("m/d/Y", strtotime($x))):'';
	}

	public function format_size($size) {
		$mod = 1024;
		$units = explode(' ','B KB MB GB TB PB');
		for ($i = 0; $size > $mod; $i++) {
			$size /= $mod;
		}
		return number_format(round($size, 2),2) . '&nbsp;' . $units[$i];
	}

	public function list_invoices( $client, $invoice_date,
		$internal = false, $shipments_selected = false ) {
		if( $this->debug ) echo "<p>".__METHOD__.": client = $client, invoice_date = $invoice_date</p>";
		$result = '<h3>No invoices found</h3>';
		$this->available_to_send = 0;
		$this->missing_attachments = 0;
		$this->attachment_too_big = 0;
		
		$shipment_filter = is_array($shipments_selected) ?
			"and SHIPMENT_CODE in (".implode(',', $shipments_selected).")
			" : "";
		
		if( $this->date_attachment ) {
			$date_match = "AND DATE((select created_date
				from EXP_ATTACHMENT 
				where SOURCE_CODE = SHIPMENT_CODE AND SOURCE_TYPE = 'shipment'
		        and FILE_TYPE = (SELECT ITEM_CODE FROM EXP_ITEM_LIST
					WHERE ITEM_TYPE = 'Attachment type'
					AND ITEM = 'Billing') 
	                LIMIT 1)) = '".$invoice_date."'";
		} else {
			$date_match = "AND DATE((SELECT MAX(CREATED_DATE)
				FROM EXP_STATUS
			    WHERE ORDER_CODE = SHIPMENT_CODE
			    AND BILLING_STATUS IN (SELECT STATUS_CODES_CODE
					FROM EXP_STATUS_CODES
					WHERE SOURCE_TYPE = 'shipment-bill'
					AND BEHAVIOR IN ('approved', 'billed')))) = '".$invoice_date."'";
		}
		
		$invoices = $this->database->get_multiple_rows("
			SELECT SHIPMENT_CODE, SS_NUMBER,
			(SELECT STATUS_STATE
				FROM EXP_STATUS_CODES
				WHERE SOURCE_TYPE = 'shipment-bill'
				AND STATUS_CODES_CODE = BILLING_STATUS) AS BILLING_STATUS,
			GET_ASOF(SHIPMENT_CODE) AS ASOF_DATE, CHANGED_DATE, TOTAL,
			EXISTS (select ATTACHMENT_CODE
				from EXP_ATTACHMENT 
				where SOURCE_CODE = SHIPMENT_CODE AND SOURCE_TYPE = 'shipment'
			    and FILE_TYPE = (SELECT ITEM_CODE FROM EXP_ITEM_LIST
					WHERE ITEM_TYPE = 'Attachment type'
					AND ITEM = '".$this->attachment_type."') ) as HAS_ATTACHMENT,
			COALESCE((select SIZE
				from EXP_ATTACHMENT 
				where SOURCE_CODE = SHIPMENT_CODE AND SOURCE_TYPE = 'shipment'
		        and FILE_TYPE = (SELECT ITEM_CODE FROM EXP_ITEM_LIST
					WHERE ITEM_TYPE = 'Attachment type'
					AND ITEM = '".$this->attachment_type."') 
	                LIMIT 1), 0) as ATTACHMENT_SIZE
			
			FROM EXP_SHIPMENT
			left join exp_client_billing
			on SHIPMENT_CODE = SHIPMENT_ID
			WHERE BILLTO_CLIENT_CODE = $client
			AND INVOICE_EMAIL_STATUS = 'unsent'
			AND (CONSOLIDATE_NUM IS NULL OR CONSOLIDATE_NUM = SHIPMENT_CODE)
			AND BILLING_STATUS IN (SELECT STATUS_CODES_CODE
				FROM EXP_STATUS_CODES
				WHERE SOURCE_TYPE = 'shipment-bill'
				AND BEHAVIOR IN ('approved', 'billed'))
			$date_match
			$shipment_filter
			
			ORDER BY CHANGED_DATE DESC");

		if( is_array($invoices) && count($invoices) > 0) {
			if( $internal ) {
				$result = $invoices;
			} else {
				$result = '<table class="display table table-condensed table-bordered table-hover" id="INVOICES">
				<thead>
					<tr class="exspeedite-bg">
						<th>Select</th>
						<th>Date</th>
						<th>Shipment</th>
						<th>Status</th>
						<th class="text-right">Amount</th>
						<th class="text-center">Has Attachment</th>
						<th class="text-right">Size</th>
					</tr>
				</thead>
				<tbody>';
				
				$batch_size = $this->max_email_size * 1024 * 1024;	// convert to bytes
				
				
				foreach( $invoices as $row ) {
					$result .= '<tr>
						<td class="text-center"><input type="checkbox" name="selected['.$row["SHIPMENT_CODE"].']" id="selected.'.$row["SHIPMENT_CODE"].'" checked></td>
					
					<td>'.$this->dt($row["ASOF_DATE"]).'</td>
					<td><a href="exp_addshipment.php?CODE='.$row["SHIPMENT_CODE"].'" target="_blank">'.$row["SHIPMENT_CODE"].' / '.$row["SS_NUMBER"].'</a></td>
					<td>'.$row["BILLING_STATUS"].'</td>
					<td class="text-right">'.$row["TOTAL"].'</td>
					<td class="text-center">'.($row["HAS_ATTACHMENT"] ?
						'<span class="h2 text-success"><span class="glyphicon glyphicon-ok"></span></span>' :
						'<span class="h2 text-danger tip" title="You need an attached invoice to send"><span class="glyphicon glyphicon-remove"></span></span>').'</td>
					<td class="text-right">'.
					($row["ATTACHMENT_SIZE"] > $batch_size ? '<span class="h3 text-danger tip" title="This attachment exceeds the maximum size ('.$this->max_email_size.'MB)"><span class="glyphicon glyphicon-warning-sign"></span> ' : '<span>').
					$this->format_size($row["ATTACHMENT_SIZE"]).'</span></td>
					</tr>';
					
					if( $row["HAS_ATTACHMENT"] && $row["ATTACHMENT_SIZE"] > 0 &&
						$row["ATTACHMENT_SIZE"] < $batch_size )
						$this->available_to_send++;
					else if( $row["ATTACHMENT_SIZE"] < $batch_size )
						$this->missing_attachments++;
					else
						$this->attachment_too_big++;
				}
		
				$result .= '</tbody>
					</table>
				';
			}
		}

		return $result;
	}
	
	public function batch_recipient( $client ) {
	    $result = false;
	    
	    $recipient = $this->database->get_one_row("
		    SELECT C.EMAIL
			FROM EXP_CONTACT_INFO C
			WHERE C.CONTACT_CODE = $client
			AND C.CONTACT_SOURCE = 'client'
			AND C.CONTACT_TYPE = 'bill_to'
			AND C.EMAIL <> ''
			LIMIT 1");
			
 		if( is_array($recipient) && ! empty($recipient["EMAIL"]) )
			$result = $recipient["EMAIL"];
			
		if( $this->debug ) echo "<p>".__METHOD__.": client = $client, result = ".($result ? $result : 'false')."</p>";
		return $result;
	}
   
	private function count_emails( $list ) {
		$result = 0;
		$this_email_size = 0;
				
		$batch_size = $this->max_email_size * 1024 * 1024;	// convert to bytes
		if( is_array($list) && count($list) > 0 ) {
			foreach( $list as $row ) {
				if( $this_email_size + $row["ATTACHMENT_SIZE"] > $batch_size ) {
					$result++;
					$this_email_size = 0;
				}
				$this_email_size += $row["ATTACHMENT_SIZE"];
			}
			
			if( $this_email_size > 0 )
				$result++;
		}
				
		if( $this->debug ) echo "<p>".__METHOD__.": result = $result</p>";
		return $result;
	}
	
	//! Queue up an email to go out
	private function prepare_email( $client, $shipments, $email_number, $total_emails ) {
		if( $this->debug ) echo "<p>".__METHOD__.": client = $client, shipments = ".implode(', ', $shipments)." ($email_number of $total_emails)</p>";
				
		$result = $this->add( [
			"CLIENT_CODE" => $client,
			"SHIPMENTS" => implode(',', $shipments),
			"EMAIL_CC" => $this->email_cc,
			"EMAIL_SUBJECT" => $this->batch_subject." ($email_number of $total_emails)"
		] );
		
		if( $this->debug ) echo "<p>".__METHOD__.": result = ".($result ? $result : 'false')."</p>";
		return $result;
	}
	
	//! Prepare sending a batch of invoices
	public function prepare_batch( $client, $invoice_date, $shipments_selected = false ) {
		// $this->debug = true;	// To view debug preparing a batch
		if( $this->debug ) echo "<p>".__METHOD__.": client = $client, invoice_date = $invoice_date</p>";

		$batch_size = $this->max_email_size * 1024 * 1024;	// convert to bytes
		$list = $this->list_invoices( $client, $invoice_date, true, $shipments_selected );
		
		$shipments = [];
		$this_email_size = 0;
		if( is_array($list) && count($list) > 0 ) {
			$total_emails = $this->count_emails( $list );
			$email_number = 0;
			$batches = [];
			foreach( $list as $row ) {
				if( $row["HAS_ATTACHMENT"] ) {
					if( $this_email_size + $row["ATTACHMENT_SIZE"] > $batch_size ) {
						// send what we have so far
						if( $this->debug ) echo "<p>".__METHOD__.": send ".implode(', ', $shipments)." size ".$this->format_size($this_email_size)."</p>";
						$batches[] = $this->prepare_email( $client, $shipments, ++$email_number, $total_emails );
						$shipments = [];
						$this_email_size = 0;
					}
					
					if( $row["ATTACHMENT_SIZE"] > $batch_size ) {
						$this->email->log_email_error( __METHOD__.": shipment# ".$row["SHIPMENT_CODE"]." attachment exceeds max_email_size (".
						$this->format_size($row["ATTACHMENT_SIZE"])." vs. ".$this->max_email_size."MB)" );
					} else {
						$shipments[] = $row["SHIPMENT_CODE"];
						$this_email_size += $row["ATTACHMENT_SIZE"];
					}
				}
			}
			
			if( is_array($shipments) && count($shipments) > 0 && $this_email_size > 0 ) {
				// send what we have so far
				if( $this->debug ) echo "<p>".__METHOD__.": send ".implode(', ', $shipments)." size ".$this->format_size($this_email_size)."</p>";
				$batches[] = $this->prepare_email( $client, $shipments, ++$email_number, $total_emails );
			}

			// background send...
			if( count($batches) > 0 ) {
				$result = implode(',', $batches);
				$this->email->log_email_error( __METHOD__.": spawn send batch# $result" );
				$email_type = 'batch';
				$email_code = $result;
				require_once( dirname(__FILE__).DIRECTORY_SEPARATOR."..".
					DIRECTORY_SEPARATOR."exp_spawn_send_email.php" );
			}
		
		}
		
	}
	
	// Given a code to EXP_BATCH_INVOICE, return the array of shipments
	public function batch_shipments( $code ) {
		$shipments = [];
		$check = $this->fetch_rows( "BATCH_CODE = $code", "SHIPMENTS" );	// Fetch the info
		if( is_array($check) && count($check) == 1 ) {
			$batch = $check[0];
			
			$shipments = explode(',', $batch["SHIPMENTS"]);
		}
		return $shipments;
	}
	
	// Given an array of shipments, return a list of attachments
	public function batch_attachments( $shipments ) {
		if( $this->debug ) echo "<p>".__METHOD__.": shipments = ".implode(', ', $shipments)."</p>";
		$result = false;
		
		if( is_array($shipments) && count($shipments) > 0 ) {
			$this->attachment_table = sts_attachment::getInstance($this->database, $this->debug);
			$files = $this->attachment_table->fetch_rows("SOURCE_CODE IN (".implode(', ', $shipments).")
				AND SOURCE_TYPE = 'shipment'
				AND FILE_TYPE = (SELECT ITEM_CODE FROM EXP_ITEM_LIST
					WHERE ITEM_TYPE = 'Attachment type'
					AND ITEM = '".$this->attachment_type."')", "ATTACHMENT_CODE, FILE_NAME, STORED_AS");
	
			if( is_array($files) && count($files) > 0 ) {
				$result = array();
				foreach($files as $f) {
					$result[] = $f;
				}
			}
		}

		if( $this->debug ) {
			echo "<pre>".__METHOD__.": result\n";
			var_dump($result);
			echo "</pre>";
		}
		
		return $result;
   }
    
    //! Given a code to BATCH_INVOICE_TABLE, send out an email
    public function send_batch_invoice( $code ) {
		if( $this->debug ) echo "<p>".__METHOD__.": code = $code</p>";
		$this->email->log_email_error( __METHOD__.": code = $code" );
		$result = false;
		$email_type = 'batch';
		
		$shipment_table = sts_shipment::getInstance($this->database, $this->debug);
		
		$check = $this->fetch_rows("BATCH_CODE = $code" );	// Fetch the info
		if( is_array($check) && count($check) == 1 ) {
			$batch = $check[0];
			
			$references = [];
			$shipments = explode(',', $batch["SHIPMENTS"]);
			foreach( $shipments as $shipment ) {
				$references[] = $this->email->reference( $shipment, 'shipment');
			}
			if( $this->debug ) {
				echo "<pre>".__METHOD__.": shipments, references\n";
				var_dump($batch["SHIPMENTS"], $references);
				echo "</pre>";
			}
			
			$to = $this->batch_recipient( $batch["CLIENT_CODE"] );
			if( $to <> false ) {
				$cc = empty($batch["EMAIL_CC"]) ? '' : $batch["EMAIL_CC"];
				// insert the list of references into the subject
				$subject = str_replace('(', implode(', ', $references).' (', $batch["EMAIL_SUBJECT"]);
				
				if( $this->debug ) {
					echo "<pre>".__METHOD__.": to, cc, subject\n";
					var_dump($to, $cc, $subject);
					echo "</pre>";
				}
			
				// Gather attachments
				$attachments = $this->batch_attachments( $shipments );
				
				$output = "Attached are invoices for: ".implode(', ', $references)."<br><br><br>";
				
				if( $this->email->email_links() )
					$output .= $email->links_to_attachments( $attachments );

				// add to queue
				if( $this->debug ) echo "<p>".__METHOD__.": add to queue</p>";
				$this->email->log_email_error( __METHOD__.": add to queue" );
				$queue_code = $this->queue->enqueue( $email_type, $code,
					$this->email->send_from(), $to, $cc, $subject, $output );

				if( $this->debug ) echo "<p>".__METHOD__.": send email</p>";
				$this->email->log_email_error( __METHOD__.": send email" );
				$result = $this->email->send_email( $to, $cc, $subject,
					$output, $this->email->email_links() ? false : $attachments );
					
				if( $this->debug ) echo "<p>".__METHOD__.": after send_email result = ".print_r($result, true)."</p>";
				$this->email->log_email_error( __METHOD__.": after send_email result = ".print_r($result, true) );

				$this->queue->update_sent( $queue_code, $result, $this->email->send_error );
				
				if( $this->debug ) echo "<p>".__METHOD__.": update status for all shipments result = ".($result ? 'true' : 'false')."</p>";
				$this->email->log_email_error( __METHOD__.": update status for all shipments result = ".($result ? 'true' : 'false') );
				// update status for all shipments
				$check = $shipment_table->update( 'SHIPMENT_CODE IN ('.implode(',', $shipments).')',
					[
						'INVOICE_EMAIL_STATUS'	=> ($result ? 'sent' : 'send-error'),
						'-INVOICE_EMAIL_DATE'	=> 'CURRENT_TIMESTAMP'
					], false);
				
				if( $this->debug ) echo "<p>".__METHOD__.": after update shipments ".print_r($check, true)."</p>";
				$this->email->log_email_error( __METHOD__.": after update shipments ".print_r($check, true) );

				// remove from batch table
				$check = $this->delete_row("BATCH_CODE = $code" );

				if( $this->debug ) echo "<p>".__METHOD__.": after remove from batch queue ".print_r($check, true)."</p>";
				$this->email->log_email_error( __METHOD__.": after remove from batch queue ".print_r($check, true) );
			}
			
		}
	    
    }

}

?>