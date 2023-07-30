<?php 
// Set flag that this is a parent file
define( '_STS_INCLUDE', 1 );

// Setup Session
require_once( "include/sts_session_setup.php" );

require_once( "include/sts_config.php" );
$sts_debug = (isset($_GET['debug'])  || isset($_POST['debug']))  && in_group( EXT_GROUP_DEBUG );
require_once( "include/sts_session_class.php" );

$my_session = sts_session::getInstance( $exspeedite_db, $sts_debug );
$my_session->access_check( $sts_table_access[LOAD_TABLE], EXT_GROUP_DRIVER );	// Make sure we should be here

$sts_subtitle = "View Manifest";
if( ! isset($_GET["PDF"]))
require_once( "include/header_inc.php" );

require_once( "include/sts_load_class.php" );
require_once( "include/sts_email_class.php" );
require_once( "include/sts_attachment_class.php" );

$attachment_table = sts_attachment::getInstance($exspeedite_db, $sts_debug);

// close the session here to avoid blocking
session_write_close();

if( isset($_GET['CODE']) ) {
	$load_table = sts_load::getInstance($exspeedite_db, $sts_debug);
	
	$result = $load_table->fetch_rows($load_table->primary_key." = ".$_GET['CODE']);
	
	$email = sts_email::getInstance($exspeedite_db, $sts_debug);
	
	if( isset($_GET['OFF']) ) {
		//! SCR# 307 - Rate confirmation email per office
		$email->from_office($_GET['CODE']);
		echo "<pre>";
		var_dump($email);
		echo "</pre>";
		die;
	}

	if( isset($result[0]['CARRIER']) && $result[0]['CARRIER'] > 0 )
		$output = $email->load_confirmation( $_GET['CODE'], 'carrier', $result[0]['CARRIER_BASE'], $result[0]['CARRIER_NOTE'], true );
	else
		$output = $email->load_confirmation( $_GET['CODE'], 'driver', 0, $result[0]['CARRIER_NOTE'], true );
		
	$attachments = $attachment_table->manifest_attachments( $_GET['CODE'] );
	if( is_array($attachments) && count($attachments) > 0 ) {
		foreach($attachments as $row) {
			$output .= $attachment_table->render_inline( $row["ATTACHMENT_CODE"] );
		}
	}

	if( isset($_GET["PDF"]) && $email->pdf_enabled() ) {
		
		$result = $email->convert_html_to_pdf( $output );
		if( $result ) {
			// Save to root folder in website
			header('Content-Type: application/pdf');
			header('Content-Length: ' . strlen($result));
			header('Content-Disposition: inline; filename="mypdf-1.pdf"');
	
			echo $result;
		}
		die;
	}

	echo '<div class="container" role="main">
'.$output.'
</div>';

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
