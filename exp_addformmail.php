<?php 

// $Id:$
// Add Form Mail Template

// Set flag that this is a parent file
define( '_STS_INCLUDE', 1 );

// Setup Session
require_once( "include/sts_session_setup.php" );

require_once( "include/sts_config.php" );
$sts_debug = (isset($_GET['debug'])  || isset($_POST['debug'])) && in_group( EXT_GROUP_DEBUG );
//$sts_debug = isset($_POST) && count($_POST) > 0 ;
require_once( "include/sts_session_class.php" );

$my_session = sts_session::getInstance( $exspeedite_db, $sts_debug );
$my_session->access_check( $sts_table_access[FORMMAIL_TABLE] );	// Make sure we should be here

$sts_subtitle = "Add Form Mail Template";
require_once( "include/header_inc.php" );
require_once( "include/navbar_inc.php" );

require_once( "include/sts_formmail_class.php" );
require_once( "include/sts_form_class.php" );

require_once( "include/sts_setting_class.php" );
$setting_table = sts_setting::getInstance($exspeedite_db, $sts_debug);
$form_email_template = $setting_table->get( 'option', 'FORM_EMAIL_TEMPLATE' );

$empty_template = '<!DOCTYPE html>
<html lang="en">
	<head>
		<meta charset="UTF-8">
		<title>Email Message</title>
		<!-- Latest compiled and minified CSS -->
		<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css" integrity="sha384-BVYiiSIFeK1dGmJRAkycuHAHRg32OmUcww7on3RYdg4Va+PmSTsz/K68vbdEjh4u" crossorigin="anonymous">
		
		<!-- Optional theme -->
		<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap-theme.min.css" integrity="sha384-rHyoN1iRsVXV4nD0JutlnGaslCJuC7uwjduW9SVrLvRYooPp2bWYgmgJQIXwl/Sp" crossorigin="anonymous">
	
	</head>
	</body>
		<div class="container-full" role="main">
			<p class="text-right">%LOGO%</p>
			<p>Dear %NAME%,</p>
			<p>Body of message here</p>
			
			<p>Sincerely,</p>
			<p><strong>Bob</strong></p>
		</div>
	</body>
</html>';

if( file_exists($form_email_template))
	$sts_form_add_formmail_fields['FORMMAIL_BODY']['value'] = file_get_contents($form_email_template);
else
	$sts_form_add_formmail_fields['FORMMAIL_BODY']['value'] = $empty_template;

$formmail_table = sts_formmail::getInstance($exspeedite_db, $sts_debug);
$formmail_form = new sts_form( $sts_form_addformmail_form, $sts_form_add_formmail_fields, $formmail_table, $sts_debug);

if( isset($_POST) && count($_POST) > 0 ) {		// Process completed form
	$result = $formmail_form->process_add_form();

	if( $sts_debug ) die; // So we can see the results
	if( $result ) 
		if( isset($_POST["saveadd"]) )
			reload_page ( "exp_addformmail.php" );
		else
			reload_page ( "exp_listformmail.php" );
		
}

?>
<div class="container-full theme-showcase" role="main">

<div class="well  well-md">
<?php

if( isset($value) && is_array($value) && $result == false ) {	// If error occured
	echo "<p><strong>Error:</strong> ".$formmail_table->error()."</p>";
	echo $formmail_form->render( $value );
} else {
	echo $formmail_form->render();
}

?>
</div>
</div>

<?php

require_once( "include/footer_inc.php" );
?>
		

