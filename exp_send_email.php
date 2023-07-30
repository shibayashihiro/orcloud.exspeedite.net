<?php
	
// $Id: exp_send_email.php 5146 2023-06-29 19:57:45Z dev $
// Send an email in the background.

// SCR# 490 - Testing instructions:
// Use ../exp_send_email.php?pw=Reimer&type=scr&code=489&debug to debug and not send
// Use ../exp_send_email.php?pw=Reimer&type=scr&code=489&cli to send
// DON'T FORGET subdomain=strongtco (or other)
// Change the type and code as needed.

// Set flag that this is a parent file
define( '_STS_INCLUDE', 1 );

// Set flag that this is session readonly
define( '_STS_SESSION_READONLY', 1 );

// Set flag that this is an ajax call
define( '_STS_SESSION_AJAX', 1 );

set_time_limit(0);
ini_set('memory_limit', '1024M');

//echo "<p>sapi name = ".php_sapi_name()."</p>";

if( php_sapi_name() == 'cli' || isset($_GET['debug']) || isset($_GET['cli']) ) {
	
	if( php_sapi_name() == 'cli' ) {
		parse_str(implode('&', array_slice($argv, 1)), $_GET);
		
		if( isset($_GET['subdomain']) && ! isset($_SERVER['SERVER_NAME']))
			$sts_subdomain = $_SERVER['SERVER_NAME'] = $_GET['subdomain'];

	//	echo "<p>CLI sts_subdomain = $sts_subdomain</p>\n";
	//	die;

		//! Set a special user when run in CLI mode
		require_once( "include/sts_user_class.php" );
		$user_table = new sts_user($exspeedite_db, false);
		$_SESSION['EXT_USER_CODE'] = $user_table->special_user( EMAIL_USER );
		
	}
	
	
	require_once( "include/sts_config.php" );
	
	//echo "<p>sts_subdomain = $sts_subdomain, sts_crm_dir = $sts_crm_dir sts_database = $sts_database</p>";
	//die;
	
	// Setup Session
	require_once( "include/sts_session_setup.php" );

	$sts_debug = isset($_GET['debug']);
	$sts_cli = isset($_GET['cli']);	

	require_once( "include/sts_email_class.php" );
	$email = sts_email::getInstance($exspeedite_db, $sts_debug);
	if( $sts_cli ) $email->set_cli();
	
	if( isset($_GET['pw']) && $_GET['pw'] == 'Reimer' &&	// Resend a queued message
		isset($_GET['resend']) ) {
		
		$email->log_email_error( "exp_send_email.php: resend ".$_GET['resend'] );
		$email->resend_message( $_GET['resend'] );
		
	} else if( isset($_GET['pw']) && $_GET['pw'] == 'Reimer' &&
		isset($_GET['type']) && isset($_GET['code']) ) {
		
		$result = false;
	
		$email->log_email_error( "exp_send_email.php: type = ".$_GET['type']." code = ".$_GET['code'] );
		
		$session_path = str_replace('\\', '/', ini_get('session.save_path'));

		switch( $_GET['type'] ) {
			case 'resendall':	//! SCR# 825 - Resend all queued messages
				$errors = $email->queue->all_errors();
				$email->log_email_error( "exp_send_email.php: resendall: ".count($errors)." emails to resend" );
				$count_resent = 0;
				
				if( count($errors) > 0 ) {
					foreach( $errors as $code ) {
						$email->log_email_error( "exp_send_email.php: resendall: resend ".$code );
						$result = $email->resend_message( $code );
						if( $result ) $count_resent++;
					}
				}
				
				$email->log_email_error( "exp_send_email.php: resendall: resent $count_resent emails" );
				break;

			case 'resend':	//! resend email
				$email->log_email_error( "exp_send_email.php: resend ".$_GET['code'] );
				$result = $email->resend_message( $_GET['code'] );
				break;

			case 'batch':	//! SCR# 789 - Batch Invoice Mail - code refers to batch_code
				require_once( "include/sts_batch_invoice_class.php" );
				$batch = sts_batch_invoice::getInstance($exspeedite_db, $sts_debug);
				$batches = explode(',', $_GET['code']);
				foreach( $batches as $code ) {
					$batch->send_batch_invoice( $code );
				}
				break;

			case 'formmail':	//! Form Mail - code refers to client_activity_code
				require_once( "include/sts_formmail_class.php" );
				$fm = sts_formmail::getInstance($exspeedite_db, $sts_debug);
				$result = $fm->send_formmail( $_GET['code'] );
				break;

			case 'activity':	//! Calendar reminder - client activity
				require_once( "include/sts_client_activity_class.php" );
				$cat = sts_client_activity::getInstance($exspeedite_db, $sts_debug);
				$result = $cat->calendar_appt( $_GET['code'] );
				break;

			case 'scr':			//! Announce SCR
				require_once( "include/sts_scr_class.php" );
				$scr = sts_scr::getInstance($exspeedite_db, $sts_debug);
				$result = $scr->email_announce( $_GET['code'] );
				break;

			case 'invoice':	//! Send invoice
			case 'invoicer':	//! Resend invoice
				require_once( "include/sts_session_class.php" );

				if( $email->enabled() && $email->send_invoices() ) {
					$result = $email->prepare_and_send_invoice( $_GET['code'], $_GET['type'] );
				}
				break;

			case 'manifest':	//! Send manifest
			case 'manifestr':	//! Resend manifest
				if( $email->enabled() ) {
					$result = $email->prepare_and_send_manifest( $_GET['code'], $_GET['type'] );
				}
				break;

			case 'newclient':	//! SCR# 722 - New client announcement
				require_once( "include/sts_client_class.php" );
				require_once( "include/sts_setting_class.php" );
				
				//$email->log_email_error( "exp_send_email.php: newclient ".$_GET['code'] );

				$client = sts_client::getInstance($exspeedite_db, $sts_debug);
				$setting_table = sts_setting::getInstance($exspeedite_db, $sts_debug);
				
				$notify = $setting_table->get( 'email', 'NOTIFY_NEW_SHIPCONS' ) == 'true';
				
				if(  $email->enabled() && $notify && $_GET['code'] > 0 ) {
					list($notify_address, $subject, $message) = $client->new_shipcons( $_GET['code'] );
					//$email->log_email_error( "exp_send_email.php: notify_addres ".$notify_address );
					
					if( $notify_address !== false )
						$result = $email->send_email( $notify_address, "", $subject, $message );
				} else {
					$email->log_email_error( "exp_send_email.php: email = ".($email->enabled() ? "enabled" : "disabled")." notify = ".($notify ? "enabled" : "disabled")." code = ".$_GET['code'] );
				}
				break;

			case 'newcarrier':	//! New carrier announcement
				require_once( "include/sts_carrier_class.php" );
				require_once( "include/sts_setting_class.php" );

				$carrier = sts_carrier::getInstance($exspeedite_db, $sts_debug);
				$setting_table = sts_setting::getInstance($exspeedite_db, $sts_debug);
				
				$notify_address = $setting_table->get( 'email', 'EMAIL_NOTIFY_ACCOUNTING' );
				
				$check = $carrier->fetch_rows($carrier->primary_key." = ".$_GET['code'],
					"CARRIER_NAME, (SELECT USERNAME FROM EXP_USER
					WHERE USER_CODE = EXP_CARRIER.CREATED_BY) AS USERNAME");
				
				if( $email->enabled() && ! empty($notify_address) &&
					is_array($check) && count($check) == 1 ) {
					$subject = 'New Carrier '.(!empty($check[0]["CARRIER_NAME"]) ? $check[0]["CARRIER_NAME"] : '');
					$message = '<h3>New Carrier '.(!empty($check[0]["CARRIER_NAME"]) ? $check[0]["CARRIER_NAME"] : '').'</h3>
					<p>Carrier: '.$sts_crm_root.'/exp_editcarrier.php?CODE='.$_GET['code'].'</p>
					<p>Added by: '.(!empty($check[0]['USERNAME']) ? $check[0]['USERNAME'] : 'unknown').'</p>';
					
					$result = $email->send_email( $notify_address, "", $subject, $message );
				}
				
				break;

			case 'carrierins':	//! Carrier insurance announcement
				require_once( "include/sts_user_log_class.php" );
				require_once( "include/sts_setting_class.php" );

				$ul = sts_user_log::getInstance($exspeedite_db, $sts_debug);
				$setting_table = sts_setting::getInstance($exspeedite_db, $sts_debug);
				
				$notify_address = $setting_table->get( 'email', 'EMAIL_NOTIFY_ACCOUNTING' );
				$notify_ins = $setting_table->get( 'email', 'INS_NOTIFY_ACCOUNTING' ) == 'true';
				
				if( $email->enabled() && $notify_ins && ! empty($notify_address) ) {
					$subject = 'Insurance update';
					$message = $ul->get_ins_comments( $_GET['code'] );
					
					$email->log_email_error( "exp_send_email.php: message: $message" );

					if( $message )
						$result = $email->send_email( $notify_address, "", $subject, $message );
				}

				break;

			case 'picked':	//! SCR# 906 - send notifications
			case 'dropped':
			case 'arrship':
			case 'arrcons':
				require_once( "include/sts_setting_class.php" );

				$setting_table = sts_setting::getInstance($exspeedite_db, $sts_debug);
				
				$sts_send_notifications = $setting_table->get( 'option', 'SEND_NOTIFICATIONS' ) == 'true';
				
				if( $email->enabled() && $sts_send_notifications ) {
					$message = $email->notify_shipment_event( $_GET['code'], $_GET['type']  );
				}
					
				break;
		}
	
		$email->log_email_error( "exp_send_email.php: result = ".($result ? "true" : "false") );
	}
}

?>

