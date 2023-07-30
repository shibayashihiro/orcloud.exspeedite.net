<?php 
// Set flag that this is a parent file
define( '_STS_INCLUDE', 1 );

// Set flag that this is session readonly
define( '_STS_SESSION_READONLY', 1 );

// Setup Session
require_once( "include/sts_session_setup.php" );

require_once( "include/sts_config.php" );
$sts_debug = (isset($_GET['debug'])  || isset($_POST['debug']))  && in_group( EXT_GROUP_DEBUG );
require_once( "include/sts_session_class.php" );

$my_session = sts_session::getInstance( $exspeedite_db, $sts_debug );
$my_session->access_check( $sts_table_access[SHIPMENT_TABLE] );	// Make sure we should be here

$sts_subtitle = "View Invoice";
require_once( "include/header_inc.php" );

require_once( "include/sts_email_class.php" );
require_once( "include/sts_attachment_class.php" );

$attachment_table = sts_attachment::getInstance($exspeedite_db, $sts_debug);

if( isset($_GET['CODE']) ) {
	$email = sts_email::getInstance($exspeedite_db, $sts_debug);
	
	$output = $email->shipment_invoice( $_GET['CODE'], true, isset($_GET['ALT']));
	$attachments = $attachment_table->invoice_attachments( $_GET['CODE'] );
	if( is_array($attachments) && count($attachments) > 0 ) {
		foreach($attachments as $row) {
			$output .= $attachment_table->render_inline( $row["ATTACHMENT_CODE"] );
		}
	}
	
	//! Re-send invoice by email
	if( isset($_GET['RESEND']) && $email->enabled() && $email->send_invoices() ) {			

		$email_type = 'invoicer';
		$email_code = $_GET['CODE'];
		require_once( "exp_spawn_send_email.php" );		// Background send
	}
	
	echo '<div class="container" role="main">
';
//'."sts_subdomain = $sts_subdomain<br>".$attachment_table->cache->info();

	//! SCR# 293 - Add print button here
	if( ! isset($_GET['PRINT']) ) {
		echo '<p><a class="btn btn-sm btn-success" name="print" id="print" onclick="window.open(\'exp_viewinvoice.php?CODE='.$_GET['CODE'].(isset($_GET['ALT']) ? '&ALT' : '').'&PRINT\', \'newwindow\', \'width=\'+ ($(window).width()*2/3) + \',height=\' + ($(window).height()*2/3)); return false;"><span class="glyphicon glyphicon-print"></span> Print (in a new window)</a>
	';
	
		//! Button to re-send invoice
		if( $email->enabled() && ! isset($_GET['PRINT']) && $email->send_invoices() &&
			! isset($_GET['ALT']) ) {
			$to = $email->shipment_invoice_recipient( $_GET['CODE'] );
			if( $to <> false ) {
				echo ' <a id="Resend" class="btn btn-sm btn-default tip" href="exp_viewinvoice.php?CODE='.
				$_GET['CODE'].'&RESEND"><span class="glyphicon glyphicon-envelope"></span> Resend</a>
				';
				if( $my_session->superadmin() )
					echo '<a id="Resend" class="btn btn-sm btn-danger"
						href="exp_send_email.php?pw=Reimer&type=invoice&code='.$_GET['CODE'].'&debug&cli" target="_blank"><span class="glyphicon glyphicon-envelope"></span> DEBUG BACKGROUND SEND</a>
				';
				 echo 'To: '.$to.(empty($email->email_invoice_cc) ? '' : ' Cc: '.$email->email_invoice_cc).'
	';
			} else {
				echo ' <span class="text-warning"><span class="glyphicon glyphicon-warning-sign"></span> Bill-to client does not have an e-mail address.</span>
	';
			}
		}
		echo '</p>';
	}
	
	echo $output.'
</div>';

	//! SCR# 293 - trigger print dialog
	if( isset($_GET['PRINT'])) {
		echo '
	<script language="JavaScript" type="text/javascript"><!--		
		$(document).ready( function () {
			window.print();
		});
	//--></script>		
		';
	}
}

require_once( "include/footer_inc.php" );
?>
