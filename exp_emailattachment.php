<?php 

// $Id: exp_emailattachment.php 5030 2023-04-12 20:31:34Z duncan $
// Email attachment

// Set flag that this is a parent file
define( '_STS_INCLUDE', 1 );

// Setup Session
require_once( "include/sts_session_setup.php" );

require_once( "include/sts_config.php" );
$sts_debug = (isset($_GET['debug'])  || isset($_POST['debug']))  && in_group( EXT_GROUP_DEBUG );
require_once( "include/sts_session_class.php" );

set_time_limit(0);
ini_set('memory_limit', '1024M');

$my_session = sts_session::getInstance( $exspeedite_db, $sts_debug );
$my_session->access_check( $sts_table_access[ATTACHMENT_TABLE] );	// Make sure we should be here

if( isset($_POST['CODE']) ) {
	//$sts_debug = true;
	if( $sts_debug ) { 
		echo "<pre>POST\n";
		var_dump($_POST);
		echo "</pre>";
	}
	
	//! Send email
	if( isset($_POST['send'])) {
		// Turn off output buffering
		ini_set('output_buffering', 'off');
		// Implicitly flush the buffer(s)
		ini_set('implicit_flush', true);
		ob_implicit_flush(true);
		
		if (ob_get_level() == 0) ob_start();
		
		echo '<div class="container" role="main">
			<div id="loading"><center><h2 class="text-center"><img src="images/loading.gif" alt="loading" width="125" height="125" /><br>Emailing Attachment...</h2></center></div>';
		ob_flush(); flush();
		sleep(1);

		require_once( "include/sts_email_class.php" );
		$email = sts_email::getInstance($exspeedite_db, $sts_debug);
		
		$cc = isset($_POST['CC_YOU']) && $_POST['CC_YOU'] == 'on' ? $_SESSION['EXT_FULLNAME'].' <'.$_SESSION['EXT_EMAIL'].'>' : '';
		
		require_once( "include/sts_attachment_class.php" );
		$attachment_table = sts_attachment::getInstance($exspeedite_db, $sts_debug);

		if( ! $attachment_table->attachment_exists($_POST['CODE']) ) {
			$error = '<h2>Stored Attachment Missing</h2>
			<p>The file '.$_POST['FILE_NAME'].' stored in '.$_POST['STORED_AS'].' was not found.<br>
			Attachment code = '.$_POST['CODE'].'<br>
			Please contact Exspeedite support, and copy and paste this message.</p>';
			$email->send_alert($error);
			echo $error;
			die;
		}		

		$email->prepare_and_send_attachment( $_POST['FROM_EMAIL'], $_POST['TO_EMAIL'], $cc, $_POST['SUBJECT'],
			$_POST['NOTE'], $_POST['CODE'] );
		
	}
	
	if( $sts_debug ) die; // So we can see the results
	reload_page ( $_POST["REFERER"] );
	
} else if( isset($_GET['CODE']) ) {
		$sts_subtitle = "Email Attachment";
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
		if( isset($_SESSION["REPORT_REFERRER"])) {
			$referer .= '&REFERER='.$_SESSION["REPORT_REFERRER"];
			unset($_SESSION["REPORT_REFERRER"]);
		}

		echo '<div class="container theme-showcase" role="main">
		';

	require_once( "include/sts_attachment_class.php" );
	require_once( "include/sts_email_attach_class.php" );
	require_once( "include/sts_setting_class.php" );
	$setting_table = sts_setting::getInstance($exspeedite_db, $sts_debug);
	$email_default_client = $setting_table->get( 'email', 'EMAIL_ATT_DEFAULT_CLIENT' ) == 'true';

	$attachment_table = sts_attachment::getInstance($exspeedite_db, $sts_debug);
	$ea_table = sts_email_attach::getInstance($exspeedite_db, $sts_debug);
	
	$att = $attachment_table->fetch_rows( $attachment_table->primary_key." = ".$_GET['CODE'], "SOURCE_CODE, SOURCE_TYPE, FILE_NAME, FILE_TYPE, STORED_AS,
	GET_ATT_EMAIL(ATTACHMENT_CODE) AS CLIENT_EMAIL,
	(SELECT ITEM FROM EXP_ITEM_LIST
	WHERE ITEM_CODE = FILE_TYPE AND
	ITEM_TYPE = 'Attachment type'
	LIMIT 1) AS FILE_TYPE_NAME,
	CASE WHEN SOURCE_TYPE = 'client' THEN
		(SELECT CLIENT_NAME FROM EXP_CLIENT
			WHERE CLIENT_CODE = SOURCE_CODE)
	WHEN SOURCE_TYPE = 'carrier' THEN
		(SELECT CARRIER_NAME FROM EXP_CARRIER
			WHERE CARRIER_CODE = SOURCE_CODE) 
	WHEN SOURCE_TYPE = 'driver' THEN
		(SELECT CONCAT_WS(' ', FIRST_NAME, LAST_NAME) FROM EXP_DRIVER
			WHERE DRIVER_CODE = SOURCE_CODE)
	ELSE SOURCE_CODE
	END AS REF
" );
	
	if( is_array($att) && count($att) == 1 ) {
		
		if( $ea_table->enabled() ) {
			$matches = $ea_table->matches($att[0]['FILE_TYPE'], $att[0]['SOURCE_TYPE']);
			if( count($matches) == 1 ) {
				$single_from = true;
				$from = $matches[0];
			} else {
				$single_from = false;
			}		
		} else { // htmlspecialchars(
			$single_from = true;
			$from = $ea_table->default_from();
		}

		if( $single_from ) {
			$from_inc = '<div class="form-group">
				<label for="FROM_EMAIL" class="col-sm-3 control-label"><span class="glyphicon glyphicon-envelope"></span> Email From:</label>
				<div class="col-sm-8">
					<input name="FROM_EMAIL" type="hidden" value="'.$from.'">
					'.htmlspecialchars($from).'
				</div>
			</div>';
		} else {
			$from_inc = '<div class="form-group">
				<label for="FROM_EMAIL" class="col-sm-3 control-label"><span class="glyphicon glyphicon-envelope"></span> Email From:</label>
				<div class="col-sm-8">
					'.$ea_table->matches_menu($matches).'
				</div>
			</div>';
		}
		
		$client_default = '';
		if( $email_default_client && ! empty($att[0]['CLIENT_EMAIL']) )
			$client_default = 'value="'.$att[0]['CLIENT_EMAIL'].'"';
	
		echo '<div class="container-full">
		<div class="well  well-md">
			<form role="form" class="form-horizontal" action="exp_emailattachment.php" 
					method="post" enctype="multipart/form-data" 
					name="carrier" id="carrier">
			'.(isset($_GET['debug']) ? '<input name="debug" id="debug" type="hidden" value="true">
	' : '').'
			<input name="REFERER" type="hidden" value="'.$referer.'">
			<input name="CODE" id="CODE" type="hidden" value="'.$_GET['CODE'].'">
			<input name="SOURCE_CODE" id="SOURCE_CODE" type="hidden" value="'.$att[0]['SOURCE_CODE'].'">
			<input name="SOURCE_TYPE" id="SOURCE_TYPE" type="hidden" value="'.$att[0]['SOURCE_TYPE'].'">
			<input name="FILE_NAME" id="FILE_NAME" type="hidden" value="'.$att[0]['FILE_NAME'].'">
			<input name="STORED_AS" id="STORED_AS" type="hidden" value="'.$att[0]['STORED_AS'].'">

			<h2><img src="images/image_icon.png" alt="image_icon" height="24"> Send Attachment '.$att[0]['FILE_NAME'].'</h2>
			<h3>Attached to '.$att[0]['SOURCE_TYPE'].' '.$att[0]['REF'].' as '.$att[0]['FILE_TYPE_NAME'].'</h3>
			'.$from_inc.'
			<div class="form-group">
				<label for="TO_EMAIL" class="col-sm-3 control-label"><span class="glyphicon glyphicon-envelope"></span> Email To:</label>
				<div class="col-sm-8">
					<input class="form-control" name="TO_EMAIL" 
						id="TO_EMAIL" type="email" placeholder="Recipient" '.$client_default.' multiple required>
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
						id="SUBJECT" type="text" value="Attachment '.$att[0]['FILE_NAME'].'" required>
				</div>
			</div>
			<div class="form-group">
				<label for="NOTE" class="col-sm-3 control-label"><span class="glyphicon glyphicon-pencil"></span> Note:</label>
				<div class="col-sm-8">
					<textarea class="form-control" name="NOTE" 
						id="NOTE" rows="6" placeholder="Note about the attachment"></textarea>
				</div>
			</div>
			<div class="form-group">
				<div class="btn-group col-sm-8">
					<button class="btn btn-md btn-success" name="send" type="submit" ><span class="glyphicon glyphicon-ok"></span> Send Email</button>
					<a class="btn btn-md btn-default" href="'.$referer.'" name="cancel" type="cancel" formnovalidate><span class="glyphicon glyphicon-remove"></span> Cancel</a>
				</div>
			</div>
			</form>
			</div>
			</div>
		
				';
	}
}
?>