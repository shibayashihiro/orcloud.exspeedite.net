<?php 
// Set flag that this is a parent file
define( '_STS_INCLUDE', 1 );

// Setup Session
require_once( "../include/sts_session_setup.php" );
define( '_STS_SIGNIN_THEME', 1 );

require_once( "../include/sts_config.php" );

// Used in header_inc.php
define( 'EXP_RELATIVE_PATH', '../' );

if( $sts_use_captcha ) {	//! Captcha - for more enhanced security
	require_once($sts_crm_dir.DIRECTORY_SEPARATOR."captcha/simple-php-captcha.php");
}
$sts_debug = false;
require_once( $sts_crm_dir.DIRECTORY_SEPARATOR."include/sts_session_class.php" );

$my_session = sts_session::getInstance( $exspeedite_db, $sts_debug );
	
$sts_subtitle = "Sign In";
require_once( $sts_crm_dir.DIRECTORY_SEPARATOR."include/header_inc.php" );
require_once( $sts_crm_dir.DIRECTORY_SEPARATOR."include/sts_user_class.php" );
require_once( $sts_crm_dir.DIRECTORY_SEPARATOR."include/sts_setting_class.php" );


$setting_table = sts_setting::getInstance($exspeedite_db, $sts_debug);
$sts_logo_path = EXP_RELATIVE_PATH.$setting_table->get( 'company', 'LOGO' );
$sts_company_name = $setting_table->get( 'company', 'NAME' );
$sts_portal_title = $setting_table->get( 'portal', 'PORTAL_TITLE' );
$sts_url_main = $setting_table->get( 'portal', 'URL_MAIN' );
$sts_url_contact = $setting_table->get( 'portal', 'URL_CONTACT' );

$sign_in_message = "Please sign in";

	if( $sts_debug ) {
		echo "<p>server = </p>
		<pre>";
		var_dump($_SERVER);
		echo "</pre>";
	}

if ( isset($_POST['username']) && isset($_POST['password']) ) {
	//! CSRF Token check
	if( isset($_POST) && isset($_POST["CSRF"]) && $_POST["CSRF"] == str_rot13(session_id())) {
		//! Captcha check
		if( $sts_use_captcha && isset($_SESSION['captcha']) ) {
			$passed_catcha = false;
			if( isset($_POST['captcha']) &&  isset($_SESSION['captcha']['code']) &&
				strtolower($_POST['captcha']) == strtolower($_SESSION['captcha']['code']))
				$passed_catcha = true;
		} else {
			$passed_catcha = true;
		}
		if( $passed_catcha ) {
			$user_table = new sts_user($exspeedite_db, $sts_debug);		// User table
			$my_session = sts_session::getInstance( $exspeedite_db, $sts_debug );				// Session object
			$logged_in = $my_session->login( $user_table, $_POST['username'], $_POST['password'] );
			
			if( $logged_in ) {
				if( isset($_POST['return'] ) ) {
					$return_path = $user_table->decryptData($_POST['return']);
		
					if( $sts_debug )
						echo "<p>return_path = ".(isset($return_path) ? $return_path : "null")."</p>";
					
					$file_part = current(explode('?', $return_path));
					if( $sts_debug )
						echo "<p>file_part = ".(isset($file_part) ? $file_part : "null")." cwd = ".getcwd()."</p>";
					
					if( ! file_exists( $file_part ) )
						unset($return_path);
				}
				if( $sts_debug )
					echo "<p>return_path = ".(isset($return_path) ? $return_path : "null")."</p>";
				if( ! $sts_debug )
					reload_page ( (isset($return_path) ? 
						$return_path :	// Return to saved page
						"index.php") );	// Or go to home page
				else
					echo "<p>reload_page ".( (isset($return_path) ? 
						$return_path : "index.php") )."</p>";
			} else
				$sign_in_message = "Please Try again";
		} else
			$sign_in_message = "Please Try again";
	} else
		$sign_in_message = "Please Try again";
}

if( $sts_use_captcha_after_fail && $sign_in_message <> "Please Try again" ) {
	$sts_use_captcha = false;
	if( isset($_SESSION['captcha'])) unset($_SESSION['captcha']);
}

if( $sts_use_captcha ) {
	$_SESSION['captcha'] = simple_php_captcha(array(
	    'min_length' => 3,
	    'max_length' => 4) );
}
?>
<div id="wrap">
	<div class="container">
		<div class="row text-center">
			<div class="col-sm-4">
			<a href="<?php echo $sts_url_main; ?>" title="<?php echo $sts_company_name ?>"><img class="img-responsive center-block" src="<?php echo $sts_logo_path; ?>" alt="<?php echo $sts_company_name ?>"></a>
			</div>
			<div class="col-sm-6">
				<h1><?php echo $sts_portal_title; ?></h1>
			</div>
		</div>

		<form class="form-signin" role="form" action="exp_login.php" method="post" enctype="multipart/form-data" name="login" target="_top">
			<h2 class="form-signin-heading"><?php echo $sign_in_message; ?></h2>
<?php if( isset($_GET['return']) )
		echo '<input name="return" type="hidden" value="'.$_GET['return'].'" />
		';
	echo '<input name="CSRF" type="hidden" value="'.str_rot13(session_id()).'">
	';
?>
			<input name="username" type="username" class="form-control" placeholder="Username" required autofocus>
			<input name="password" type="password" class="form-control" placeholder="Password" required>
<?php if(0) { ?>
			<label class="checkbox">
			<input type="checkbox" value="remember-me"> Remember me
			</label>
<?php } ?>
<?php if( $sts_use_captcha ) { ?>
			<div class="form-group">
				<div class="col-sm-7" style="padding-left: 0px">
					<div class="input-group">
					<input name="captcha" type="text" class="form-control" placeholder="Code" required>
					<span class="input-group-btn">
					<a class="btn btn-lg btn-default" href="exp_login.php"><span class="glyphicon glyphicon-refresh"></span></a>
					</span>
				</div>
				</div>
				<div class="col-sm-5">
					<img src="<?php echo $_SESSION['captcha']['image_src']; ?>" class="img-responsive" style="width: 160px;">
				</div>
			</div>
<?php } ?>
			<button class="btn btn-lg btn-success btn-block" type="submit">Sign in</button>
		</form>
	
	<div class="alert alert-warning">
	  <h3 style="margin-top: 0px;"><span class="glyphicon glyphicon-warning-sign"></span> Terms of use</h3>
	  <p><?php echo $sts_company_name ?> provides this page for our clients to obtain up-to-date information on their loads, and their loads only. By logging in you agree to these terms of use.</p>
	  <p>For more information or to obtain access, please <a href="<?php echo $sts_url_contact; ?>">Contact us</a></p>
	</div>

	</div> <!-- /container -->
</div>

<div id="footer">
	<div class="container">
		<p class="text-muted">Exspeedite developed by the cool guys at <a href="http://www.strongtoweronline.com/" target="_blank">Strong Tower Software</a></p>
	</div>
</div>
<?php

require_once( EXP_RELATIVE_PATH."include/footer_inc.php" );
?>
