<?php 

// $Id$
// View/Print Inspection Report

// Set flag that this is a parent file
define( '_STS_INCLUDE', 1 );

// Setup Session
require_once( "include/sts_session_setup.php" );

require_once( "include/sts_config.php" );
$sts_debug = (isset($_GET['debug'])  || isset($_POST['debug']))  && in_group( EXT_GROUP_DEBUG );
require_once( "include/sts_session_class.php" );

$my_session = sts_session::getInstance( $exspeedite_db, $sts_debug );
$my_session->access_check( EXT_GROUP_USER );	// Make sure we should be here

?>
<html lang="en">
	<head>
		<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
		<link href="dist/css/bootstrap.min.css" rel="stylesheet">
		<script src="https://ajax.googleapis.com/ajax/libs/jquery/2.1.1/jquery.min.js"></script>
<style>
@page  
{ 
    size: auto;   /* auto is the initial value */ 
  font-size: 50%;
  font-family: "Helvetica Neue", Helvetica, Arial, sans-serif;

    margin: 0px; 
} 

body  
{ 
    margin: 50px 30px 50px 30px;      
}
h3 { 
    page-break-before: auto;
  }
</style>
</head>
<body role="document">
<?php

require_once( "include/sts_insp_report_class.php" );
require_once( "include/sts_attachment_class.php" );
require_once( "include/sts_setting_class.php" );

$setting_table = sts_setting::getInstance($exspeedite_db, $sts_debug);
$sts_attachments = $setting_table->get( 'option', 'ATTACHMENTS_ENABLED' ) == 'true';

// close the session here to avoid blocking
session_write_close();

if( isset($_GET['REPORT']) ) {
	$report = sts_insp_report::getInstance($exspeedite_db, $sts_debug);
	$attachment = sts_attachment::getInstance($exspeedite_db, $sts_debug);
	
	$output = $report->render_report( $_GET['REPORT'] );
		
	//! SCR# 602 - include images at the bottom of the report
	if( $sts_attachments )
		$attachments = $attachment->report_attachments( $_GET['REPORT'] );
	else
		$attachments = false;
	
	if( is_array($attachments) && count($attachments) > 0 ) {
		foreach( $attachments as $row ) {
			$output .= $attachment->render_inline( $row["ATTACHMENT_CODE"] );
		}
	}

	echo $output;

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

?>
</body>
</html>

