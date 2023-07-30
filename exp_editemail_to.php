<?php 

// $Id: exp_editemail_to.php 4697 2022-03-09 23:02:23Z duncan $
// Edit Email To - update To: address and resend.

// Set flag that this is a parent file
define( '_STS_INCLUDE', 1 );

// Setup Session
require_once( "include/sts_session_setup.php" );

require_once( "include/sts_config.php" );
$sts_debug = (isset($_GET['debug'])  || isset($_POST['debug'])) && in_group( EXT_GROUP_DEBUG );
require_once( "include/sts_session_class.php" );

$my_session = sts_session::getInstance( $exspeedite_db, $sts_debug );
$my_session->access_check( EXT_GROUP_ADMIN );	// Make sure we should be here

if( isset($_GET['CODE']) ) {
	$sts_subtitle = "Edit Email To";
	require_once( "include/header_inc.php" );
	require_once( "include/navbar_inc.php" );
}

require_once( "include/sts_email_class.php" );
require_once( "include/sts_setting_class.php" );

$setting_table = sts_setting::getInstance($exspeedite_db, $sts_debug);
$multi_company = ($setting_table->get("option", "MULTI_COMPANY") == 'true');

$queue = sts_email_queue::getInstance($exspeedite_db, $sts_debug);

if( isset($_POST) && count($_POST) > 0 ) {		// Process completed form
	$result = false;
	if( isset($_POST["EMAIL_QUEUE_CODE"]) && isset($_POST["SOURCE_TYPE"]) &&
		isset($_POST["SOURCE_CODE"]) && isset($_POST["ORIGINAL_TO"]) && 
		isset($_POST["EMAIL_TO"]) && $_POST["ORIGINAL_TO"] <> $_POST["EMAIL_TO"] ) {
		
		// Update the queued email record
		$result = $queue->update( $_POST["EMAIL_QUEUE_CODE"], ['EMAIL_TO' => $_POST["EMAIL_TO"]]);
		
		if( ! $result ) echo "<p>Error: Update queue ".$_POST["EMAIL_QUEUE_CODE"]." failed</p>";
		
		if( $result && $_POST["SOURCE_TYPE"] == 'shipment') {
			if( $result && isset($_POST["UPDATE_SHIPMENT"]) && isset($_POST["SHIPMENT_CODE"]) ) {
				require_once( "include/sts_shipment_class.php" );
				
				$shipment = sts_shipment::getInstance($exspeedite_db, $sts_debug);
				$result = $shipment->update( $_POST["SHIPMENT_CODE"], ['BILLTO_EMAIL' => $_POST["EMAIL_TO"]]);

				if( ! $result ) echo "<p>Error: Update shipment ".$_POST["SHIPMENT_CODE"]." failed</p>";
			}

			if( $result && isset($_POST["UPDATE_CONTACT"]) && isset($_POST["CONTACT_INFO_CODE"]) ) {
				require_once( "include/sts_contact_info_class.php" );
				
				$contact_info = sts_contact_info::getInstance($exspeedite_db, $sts_debug);
				$result = $contact_info->update( $_POST["CONTACT_INFO_CODE"], ['EMAIL' => $_POST["EMAIL_TO"]]);
				
				if( ! $result ) echo "<p>Error: Update contact_info ".$_POST["CONTACT_INFO_CODE"]." failed</p>";
			}
		}
		else if( $result && $_POST["SOURCE_TYPE"] == 'load') {
			if( $result && isset($_POST["UPDATE_CARRIER_EMAIL"]) && isset($_POST["LOAD_CODE"]) ) {
				require_once( "include/sts_load_class.php" );
				
				$load = sts_load::getInstance($exspeedite_db, $sts_debug);
				$result = $load->update( $_POST["LOAD_CODE"], ['CARRIER_EMAIL' => $_POST["EMAIL_TO"]]);
				
				if( ! $result ) echo "<p>Error: Update load ".$_POST["LOAD_CODE"]." failed</p>";
			}

			if( $result && isset($_POST["UPDATE_CARRIER_NOTIFY"]) &&
				isset($_POST["CARRIER_CODE"]) ) {
				
				require_once( "include/sts_carrier_class.php" );
				
				$carrier = sts_carrier::getInstance($exspeedite_db, $sts_debug);
				$result = $carrier->update( $_POST["CARRIER_CODE"], ['EMAIL_NOTIFY' => $_POST["EMAIL_TO"]]);
				
				if( ! $result ) echo "<p>Error: Update carrier ".$_POST["CARRIER_CODE"]." failed</p>";
			}

			if( $result && isset($_POST["UPDATE_CARRIER_CONTACT"]) &&
				isset($_POST["CONTACT_INFO_CODE"]) ) {
				
				require_once( "include/sts_contact_info_class.php" );
				
				$contact_info = sts_contact_info::getInstance($exspeedite_db, $sts_debug);
				$result = $contact_info->update( $_POST["CONTACT_INFO_CODE"], ['EMAIL' => $_POST["EMAIL_TO"]]);
				
				if( ! $result ) echo "<p>Error: Update contact_info ".$_POST["CONTACT_INFO_CODE"]." failed</p>";
			}

			if( $result && isset($_POST["UPDATE_DRIVER_NOTIFY"]) &&
				isset($_POST["DRIVER_CODE"]) ) {
				
				require_once( "include/sts_driver_class.php" );
				
				$driver = sts_driver::getInstance($exspeedite_db, $sts_debug);
				$result = $driver->update( $_POST["DRIVER_CODE"], ['EMAIL_NOTIFY' => $_POST["EMAIL_TO"]]);
				
				if( ! $result ) echo "<p>Error: Update driver ".$_POST["DRIVER_CODE"]." failed</p>";
			}

			if( $result && isset($_POST["UPDATE_DRIVER_CONTACT"]) &&
				isset($_POST["CONTACT_INFO_CODE"]) ) {
				
				require_once( "include/sts_contact_info_class.php" );
				
				$contact_info = sts_contact_info::getInstance($exspeedite_db, $sts_debug);
				$result = $contact_info->update( $_POST["CONTACT_INFO_CODE"], ['EMAIL' => $_POST["EMAIL_TO"]]);
				
				if( ! $result ) echo "<p>Error: Update contact_info ".$_POST["CONTACT_INFO_CODE"]." failed</p>";
			}

		}
		
		if( $result && isset($_POST["SEND_EMAIL"]) ) {
			$email_type = 'resend';
			$email_code = $_POST['EMAIL_QUEUE_CODE'];
			require_once( "exp_spawn_send_email.php" );
		}

	}

	if( $result ) {
		if( $sts_debug ) die; // So we can see the results
		$back = isset($_POST["BACK"]) ? $_POST["BACK"] : 'exp_listemail_queue.php';

		reload_page ( $back );
	}
}

?>
<style>
span.manifest {
	font-size: 24px;
	line-height: 1.4;
	font-weight: 700;
}
</style>

<div class="container theme-showcase" role="main">

<div class="well  well-lg">
<?php

if( isset($_GET['CODE']) ) {
	$back = isset($_SERVER["HTTP_REFERER"]) ? basename($_SERVER["HTTP_REFERER"]) : 'exp_listemail_queue.php';

	$result = $queue->fetch_rows("EMAIL_QUEUE_CODE = ".$_GET['CODE'],
		"EMAIL_TYPE, SOURCE_TYPE, 
		if(EMAIL_TYPE = 'attachment', (SELECT EXP_ATTACHMENT.SOURCE_CODE FROM EXP_ATTACHMENT
WHERE ATTACHMENT_CODE = EXP_EMAIL_QUEUE.SOURCE_CODE LIMIT 1), SOURCE_CODE) AS SOURCE_CODE,
		EMAIL_TO,
		case when SOURCE_TYPE = 'load' then
		 	(select COALESCE(SS_NUMBER,'') FROM EXP_SHIPMENT
			WHERE LOAD_CODE = if(EMAIL_TYPE = 'attachment', (SELECT EXP_ATTACHMENT.SOURCE_CODE FROM EXP_ATTACHMENT
WHERE ATTACHMENT_CODE = EXP_EMAIL_QUEUE.SOURCE_CODE LIMIT 1), SOURCE_CODE)
			ORDER BY SHIPMENT_CODE ASC
			LIMIT 1)
		 when SOURCE_TYPE = 'shipment' then
		 	(SELECT COALESCE(SS_NUMBER,'') FROM EXP_SHIPMENT
		 	WHERE SHIPMENT_CODE = if(EMAIL_TYPE = 'attachment', (SELECT EXP_ATTACHMENT.SOURCE_CODE FROM EXP_ATTACHMENT
WHERE ATTACHMENT_CODE = EXP_EMAIL_QUEUE.SOURCE_CODE LIMIT 1), SOURCE_CODE))
		 	else NULL end AS SS_NUMBER");
	
	if( is_array($result) && count($result) == 1 ) {
		$email_type = $result[0]["EMAIL_TYPE"];
		$source_type = $result[0]["SOURCE_TYPE"];
		$source_code = $result[0]["SOURCE_CODE"];
		$email_to = $result[0]["EMAIL_TO"];
		
		//echo "<pre>";
		//var_dump($source_type, $source_code, $email_to);
		//echo "</pre>";
		
		if( $source_type == 'shipment' ) {
			$result2 = $queue->database->get_one_row("
				SELECT BILLTO_CLIENT_CODE, BILLTO_NAME, BILLTO_EMAIL,
					CONTACT_INFO_CODE, EMAIL
				FROM EXP_SHIPMENT
				LEFT JOIN EXP_CONTACT_INFO
				ON CONTACT_SOURCE = 'client'
				AND CONTACT_TYPE='bill_to'
				AND ISDELETED=FALSE
				AND EMAIL != ''
				AND CONTACT_CODE=BILLTO_CLIENT_CODE
				WHERE SHIPMENT_CODE = $source_code");
				
		} else if( $source_type == 'load' ) {	// Could be driver or carrier
			$result1 = $queue->database->get_one_row("SELECT DRIVER, CARRIER, CARRIER_EMAIL
				FROM EXP_LOAD
				WHERE LOAD_CODE = $source_code");
				
			//echo "<pre>";
			//var_dump($result1);
			//echo "</pre>";

			if( is_array($result1) ) {
				if( isset($result1["DRIVER"]) && $result1["DRIVER"] > 0 ) {
					$result2 = $queue->database->get_one_row("
						SELECT DRIVER_CODE, CONCAT(FIRST_NAME, ' ', LAST_NAME) AS DRIVER_NAME,
							EMAIL_NOTIFY, CONTACT_INFO_CODE, EMAIL
						FROM EXP_DRIVER
						LEFT JOIN EXP_CONTACT_INFO
						ON CONTACT_SOURCE = 'driver'
						AND CONTACT_TYPE='individual'
						AND EXP_CONTACT_INFO.ISDELETED=false
						AND EMAIL != ''
						AND CONTACT_CODE=DRIVER_CODE
						WHERE DRIVER_CODE = ".$result1["DRIVER"]."
						AND EXP_DRIVER.ISDELETED=false");
				} else {
					$result2 = $queue->database->get_one_row("
						SELECT CARRIER_CODE, CARRIER_NAME,
							EMAIL_NOTIFY, CONTACT_INFO_CODE, EMAIL
						FROM EXP_CARRIER
						LEFT JOIN EXP_CONTACT_INFO
						ON CONTACT_SOURCE = 'carrier'
						AND CONTACT_TYPE = 'company'
						AND EXP_CONTACT_INFO.ISDELETED=false
						AND EMAIL != ''
						AND CONTACT_CODE=CARRIER_CODE
						WHERE CARRIER_CODE = ".$result1["CARRIER"]."
						AND EXP_CARRIER.ISDELETED=false");
				}
			}
		}
		
		$source_from = $source_type.'# '.$source_code.($multi_company ? ' / '.$result[0]["SS_NUMBER"] : '');
		if( $source_type == 'shipment' )
			$source_from = '<a href="exp_addshipment.php?CODE='.$source_code.'">'.$source_from.'</a>';
		else if( $source_type == 'load' )
			$source_from = '<a href=\"exp_viewload.php?CODE='.$source_code.'">'.$source_from.'</a>';
		
		echo '<form role="form" class="form-horizontal" action="exp_editemail_to.php" 
				method="post" enctype="multipart/form-data" 
				name="editemail_to" id="editemail_to">
				
			<input name="EMAIL_QUEUE_CODE" id="EMAIL_QUEUE_CODE" type="hidden" value="'.$_GET['CODE'].'">
			<input name="SOURCE_TYPE" id="SOURCE_TYPE" type="hidden" value="'.$source_type.'">
			<input name="SOURCE_CODE" id="SOURCE_CODE" type="hidden" value="'.$source_code.'">
			<input name="ORIGINAL_TO" id="SOURCE_CODE" type="hidden" value="'.$email_to.'">
			<input name="BACK" id="BACK" type="hidden" value="'.$back.'">
			
			<h2 class="tighter-top"><span class="glyphicon glyphicon-envelope"></span> Edit Email To Address
			<button class="btn btn-sm btn-success" name="save" type="submit" ><span class="glyphicon glyphicon-ok"></span> Save Changes</button> 
			<a class="btn btn-md btn-default" id="editemail_to_cancel" href="'.$back.'"><span class="glyphicon glyphicon-remove"></span> Back</a>
			
	</h2>
	<h4>This is for when an email was sent to the wrong address. You can correct the To: address and re-send it.</h4>
	<h4>If you do not change the address and click save, it does nothing.</h4>
	
	<h4>This email is <span class="bg-warning manifest">#'.$_GET['CODE'].'</span> in the queue.
	It is from <span class="bg-warning manifest">'.$source_from.
	'</span> and it is of type <span class="bg-warning manifest">'.$email_type.'</span>.<h4>
	<br>
	
	<div class="form-group">
		<div class="col-sm-12">
			<div class="form-group tighter">
				<label for="EMAIL_TO" class="col-sm-2 control-label"><span class="text-danger tip" title="The To Address field is required.">To Address<span class="glyphicon glyphicon-star"></span></span></label>
				<div class="col-sm-6">
					
					<input class="form-control" name="EMAIL_TO" id="EMAIL_TO" type="email"  
				placeholder="Name" maxlength="2048" value="'.$email_to.'" required autofocus multiple>
				</div>
			</div>
		</div>
	</div>
	';
	
	if( $source_type == 'shipment' ) {
		echo '<br>
		<input name="SHIPMENT_CODE" id="SHIPMENT_CODE" type="hidden" value="'.$source_code.'">
		<div class="form-group">
			<div class="col-sm-8">
				<h4>Would you also like to update the Bill-to email in <span class="bg-warning manifest">Shipment# '.$source_code.'</span>?</h4>
			</div>
			<div class="col-sm-4">
				<input type="checkbox" class="my-switch" id="UPDATE_SHIPMENT" value="UPDATE_SHIPMENT" name="UPDATE_SHIPMENT">
			</div>
		</div>
		';
		
		if( is_array($result2) && isset($result2["BILLTO_NAME"]) &&
			isset($result2["BILLTO_EMAIL"])) {
			echo '<br>
			<input name="CONTACT_INFO_CODE" id="CONTACT_INFO_CODE" type="hidden" value="'.$result2["CONTACT_INFO_CODE"].'">
			<div class="form-group">
				<div class="col-sm-8">
					<h4>Would you also like to update the Bill-to email for <span class="bg-warning manifest">'.$result2["BILLTO_NAME"].'</span>?</h4>
				</div>
				<div class="col-sm-4">
					<input type="checkbox" class="my-switch" id="UPDATE_CONTACT" value="UPDATE_CONTACT" name="UPDATE_CONTACT" >
				</div>
			</div>
			';
		}
	}
	
	if( $source_type == 'load' && is_array($result2) ) {
		if( isset($result2["DRIVER_CODE"]) && ! empty($result2["EMAIL_NOTIFY"]) ) {
			echo '<br>
			<input name="DRIVER_CODE" id="DRIVER_CODE" type="hidden" value="'.$result2["DRIVER_CODE"].'">
			<div class="form-group">
				<div class="col-sm-8">
					<h4>Would you also like to update the Email Notify for driver <span class="bg-warning manifest">'.$result2["DRIVER_NAME"].'</span>?</h4>
				</div>
				<div class="col-sm-4">
					<input type="checkbox" class="my-switch" id="UPDATE_DRIVER_NOTIFY" value="UPDATE_DRIVER_NOTIFY" name="UPDATE_DRIVER_NOTIFY">
				</div>
			</div>
			';

			if( isset($result2["CONTACT_INFO_CODE"]) && ! empty($result2["EMAIL"]) ) {
				echo '<br>
				<input name="CONTACT_INFO_CODE" id="CONTACT_INFO_CODE" type="hidden" value="'.$result2["CONTACT_INFO_CODE"].'">
				<div class="form-group">
					<div class="col-sm-8">
						<h4>Would you also like to update the contact Email for driver <span class="bg-warning manifest">'.$result2["DRIVER_NAME"].'</span>?</h4>
					</div>
					<div class="col-sm-4">
						<input type="checkbox" class="my-switch" id="UPDATE_DRIVER_CONTACT" value="UPDATE_DRIVER_CONTACT" name="UPDATE_DRIVER_CONTACT">
					</div>
				</div>
				';
			}
		}

		if( $source_type == 'load' && isset($result1["CARRIER"]) && ! empty($result1["CARRIER_EMAIL"]) ) {
			echo '<br>
			<input name="LOAD_CODE" id="LOAD_CODE" type="hidden" value="'.$source_code.'">
			<div class="form-group">
				<div class="col-sm-8">
					<h4>Would you also like to update the carrier email in <span class="bg-warning manifest">load# '.$source_code.'</span>?</h4>
				</div>
				<div class="col-sm-4">
					<input type="checkbox" class="my-switch" id="UPDATE_CARRIER_EMAIL" value="UPDATE_CARRIER_EMAIL" name="UPDATE_CARRIER_EMAIL">
				</div>
			</div>
			';
		}
	
		if( isset($result2["CARRIER_CODE"]) && ! empty($result2["EMAIL_NOTIFY"]) ) {
			echo '<br>
			<input name="CARRIER_CODE" id="CARRIER_CODE" type="hidden" value="'.$result2["CARRIER_CODE"].'">
			<div class="form-group">
				<div class="col-sm-8">
					<h4>Would you also like to update the Email Notify for carrier <span class="bg-warning manifest">'.$result2["CARRIER_NAME"].'</span>?</h4>
				</div>
				<div class="col-sm-4">
					<input type="checkbox" class="my-switch" id="UPDATE_CARRIER_NOTIFY" value="UPDATE_CARRIER_NOTIFY" name="UPDATE_CARRIER_NOTIFY">
				</div>
			</div>
			';

			if( isset($result2["CONTACT_INFO_CODE"]) && ! empty($result2["EMAIL"]) ) {
				echo '<br>
				<input name="CONTACT_INFO_CODE" id="CONTACT_INFO_CODE" type="hidden" value="'.$result2["CONTACT_INFO_CODE"].'">
				<div class="form-group">
					<div class="col-sm-8">
						<h4>Would you also like to update the contact Email for carrier <span class="bg-warning manifest">'.$result2["CARRIER_NAME"].'</span>?</h4>
					</div>
					<div class="col-sm-4">
						<input type="checkbox" class="my-switch" id="UPDATE_CARRIER_CONTACT" value="UPDATE_DRIVER_CONTACT" name="UPDATE_CARRIER_CONTACT">
					</div>
				</div>
				';
			}
		}

	}
	
	echo '<br>
	<div class="form-group">
		<div class="col-sm-8">
			<h4>Would you also like to <span class="bg-warning manifest">re-send the email</span> to the new address?</h4>
		</div>
		<div class="col-sm-4">
			<input type="checkbox" class="my-switch" id="SEND_EMAIL" value="SEND_EMAIL" name="SEND_EMAIL">
		</div>
	</div>
	';
		
	echo '</form>
	';
		
	}
}

?>
</div>
</div>
<?php
if( isset($_GET['CODE']) ) {
	require_once( "include/footer_inc.php" );
}
?>

