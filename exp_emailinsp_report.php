<?php 

// $Id: exp_emailinsp_report.php 3435 2019-03-25 18:53:25Z duncan $
// Email inspection report

// Set flag that this is a parent file
define( '_STS_INCLUDE', 1 );

// Setup Session
require_once( "include/sts_session_setup.php" );

require_once( "include/sts_config.php" );
$sts_debug = (isset($_GET['debug'])  || isset($_POST['debug']))  && in_group( EXT_GROUP_DEBUG );
require_once( "include/sts_session_class.php" );

$my_session = sts_session::getInstance( $exspeedite_db, $sts_debug );
$my_session->access_check( EXT_GROUP_MECHANIC, EXT_GROUP_ADMIN );	// Make sure we should be here

require_once( "include/sts_insp_report_class.php" );
require_once( "include/sts_attachment_class.php" );
require_once( "include/sts_setting_class.php" );

$setting_table = sts_setting::getInstance($exspeedite_db, $sts_debug);
$sts_attachments = $setting_table->get( 'option', 'ATTACHMENTS_ENABLED' ) == 'true';
$insp_title = $setting_table->get( 'option', 'INSPECTION_REPORT_TITLE' );


if( isset($_POST['REPORT']) ) {
	//$sts_debug = true;
	if( $sts_debug ) { 
		echo "<pre>POST\n";
		var_dump($_POST);
		echo "</pre>";
	}
	
	//! Send email
	if( isset($_POST['send'])) {
		$sts_subtitle = "Email ".$insp_title;
		require_once( "include/header_inc.php" );
		// Turn off output buffering
		ini_set('output_buffering', 'off');
		// Implicitly flush the buffer(s)
		ini_set('implicit_flush', true);
		ob_implicit_flush(true);
		
		if (ob_get_level() == 0) ob_start();
		
		echo '<div class="container" role="main">
			<div id="loading"><h2 class="text-center"><img src="images/loading.gif" alt="loading" width="125" height="125" /><br>Emailing '.$insp_title.'...</h2></div>';
		ob_flush(); flush();

		require_once( "include/sts_email_class.php" );
		$email = sts_email::getInstance($exspeedite_db, $sts_debug);
		$report_table = sts_insp_report::getInstance($exspeedite_db, $sts_debug);
		$attachment = sts_attachment::getInstance($exspeedite_db, $sts_debug);
		
		$cc = isset($_POST['CC_YOU']) && $_POST['CC_YOU'] == 'on' ? $_SESSION['EXT_FULLNAME'].' <'.$_SESSION['EXT_EMAIL'].'>' : '';
		
		$output = $report_table->render_report( $_POST['REPORT'] );
		
		if( ! empty($_POST["NOTE"]))
			$output = '<p><strong>Note:</strong><p>
			<pre>'.$_POST["NOTE"].'</pre>
			<br>
			<br>
			'.$output;

		if( $sts_attachments )
			$attachments = $attachment->report_attachments( $_POST['REPORT'] );
		else
			$attachments = false;
		
		if( $email->email_links() )
			$output .= $email->links_to_attachments( $attachments );
		$email->send_email( $_POST['TO_EMAIL'], $cc, $_POST['SUBJECT'],
			$output, $email->email_links() ? false : $attachments );
	}
	
	if( $sts_debug ) die; // So we can see the results
	reload_page ( $_POST["REFERER"] );
	
} else if( isset($_GET['REPORT']) ) {
	if( $sts_debug ) { 
		echo "<pre>GET\n";
		var_dump($_GET);
		echo "</pre>";
	}
	
	$sts_subtitle = "Email ".$insp_title;
	require_once( "include/header_inc.php" );
	require_once( "include/navbar_inc.php" );
	
	if( isset($_SERVER["HTTP_REFERER"]) ) {
		$path = explode('/', $_SERVER["HTTP_REFERER"]); 
		$referer = end($path);
		if( $referer == 'exp_emailinsp_report.php' )
			$referer = 'index.php';
	} else {
		$referer = 'index.php';
	}
	if( isset($_GET["REFERER"])) {
		$referer .= '&REFERER='.$_GET['REFERER'];
	}

	if( $sts_debug ) echo "<p>before container</p>";
	
	echo '<div class="container theme-showcase" role="main">
	';

	$report_table = sts_insp_report::getInstance($exspeedite_db, $sts_debug);

    $report_data = $report_table->fetch_rows( "REPORT_CODE = ".$_GET['REPORT'], "*,
    	CASE WHEN UNIT_TYPE = 'tractor' THEN
	    	(SELECT UNIT_NUMBER FROM EXP_TRACTOR
	    		WHERE TRACTOR_CODE = EXP_INSP_REPORT.UNIT)
    	ELSE
	    	(SELECT UNIT_NUMBER FROM EXP_TRAILER
	    		WHERE TRAILER_CODE = EXP_INSP_REPORT.UNIT)
	    END AS UNIT_NUMBER,
	    (SELECT FULLNAME FROM EXP_USER
	    WHERE USER_CODE = EXP_INSP_REPORT.MECHANIC) AS FULLNAME
	    " );
	
	if( $sts_debug ) { 
		echo "<pre>report_data\n";
		var_dump($report_data);
		echo "</pre>";
	}
	
	if( is_array($report_data) && count($report_data) == 1 ) {
	
		$report_date = date("m/d/Y", strtotime($report_data[0]["REPORT_DATE"]));
	    $unit_type = ucfirst($report_data[0]["UNIT_TYPE"]);
	    $unit_number = $report_data[0]["UNIT_NUMBER"];

		echo '<div class="container-full">
		<div class="well  well-md">
			<form role="form" class="form-horizontal" action="exp_emailinsp_report.php" 
					method="post" enctype="multipart/form-data" 
					name="carrier" id="carrier">
			'.(isset($_GET['debug']) ? '<input name="debug" id="debug" type="hidden" value="true">
	' : '').'
			<input name="REFERER" type="hidden" value="'.$referer.'">
			<input name="REPORT" id="REPORT" type="hidden" value="'.$_GET['REPORT'].'">

			<h2><span class="glyphicon glyphicon-wrench"></span> Send '.$insp_title.' For '.$unit_type.'# '.$unit_number.'</h2>
			<div class="form-group">
				<label for="TO_EMAIL" class="col-sm-3 control-label"><span class="glyphicon glyphicon-envelope"></span> Email To:</label>
				<div class="col-sm-8">
					<input class="form-control" name="TO_EMAIL" 
						id="TO_EMAIL" type="email" placeholder="Recipient" multiple required>
				</div>
			</div>
			<div class="form-group">
				<label for="CC_YOU" class="text-left col-sm-3 control-label"><span class="glyphicon glyphicon-envelope"></span> Copy To:</label>
				<div class="col-sm-8">
					<input class="control-label" name="CC_YOU" 
							id="CC_YOU" type="checkbox" checked> to '.htmlspecialchars($_SESSION['EXT_FULLNAME'].' <'.$_SESSION['EXT_EMAIL'].'>').'					
				</div>
			</div>
			<div class="form-group">
				<label for="SUBJECT" class="col-sm-3 control-label"><span class="glyphicon glyphicon-envelope"></span> Subject:</label>
				<div class="col-sm-8">
					<input class="form-control" name="SUBJECT" 
						id="SUBJECT" type="text" value="'.$insp_title.' For '.$unit_type.'# '.$unit_number.' Dated '.$report_date.'" required>
				</div>
			</div>
			<div class="form-group">
				<label for="NOTE" class="col-sm-3 control-label"><span class="glyphicon glyphicon-pencil"></span> Note:</label>
				<div class="col-sm-8">
					<textarea class="form-control" name="NOTE" 
						id="NOTE" rows="6" placeholder="Note about the '.$insp_title.'"></textarea>
				</div>
			</div>
			<div class="form-group">
				<div class="btn-group col-sm-8">
					<button class="btn btn-md btn-success" name="send" type="submit" ><span class="glyphicon glyphicon-ok"></span> Send Email</button>
					<button class="btn btn-md btn-default" name="cancel" type="cancel" formnovalidate><span class="glyphicon glyphicon-remove"></span> Cancel</button>
				</div>
			</div>
			</form>
			</div>
			</div>
		
				';
	}
}
?>