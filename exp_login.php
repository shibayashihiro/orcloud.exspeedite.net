<?php 

// $Id: exp_login.php 5030 2023-04-12 20:31:34Z duncan $
// Login screen

// Set flag that this is a parent file
define( '_STS_INCLUDE', 1 );

// Avoid getting timed out on a login
define( '_STS_SESSION_AJAX', 1 );

// Setup Session
require_once( "include/sts_session_setup.php" );
define( '_STS_SIGNIN_THEME', 1 );
define( '_STS_ANIMATE', 1 );

require_once( "include/sts_config.php" );

function url_exists($url) {
    if (!$fp = curl_init($url)) return false;
    return true;
}

if( $sts_use_captcha ) {	//! Captcha - for more enhanced security
	require_once("captcha/simple-php-captcha.php");
}
$sts_debug = false;
require_once( "include/sts_session_class.php" );

$my_session = sts_session::getInstance( $exspeedite_db, $sts_debug );
	
$sts_subtitle = "Sign In";
require_once( "include/header_inc.php" );
require_once( "include/sts_user_class.php" );
require_once( "include/sts_setting_class.php" );

$setting_table = sts_setting::getInstance( $exspeedite_db, false );
$sts_company_name = $setting_table->get( 'company', 'NAME' );
$sts_company_logo = $setting_table->get( 'company', 'LOGO' );
$sts_pid_file = $setting_table->get( 'option', 'HTTP_PID_FILE' );
$sts_test_login = $setting_table->get( 'option', 'TEST_LOGIN' ) == 'true';
$sts_block_autocomplete = $setting_table->get( 'option', 'LOGIN_BLOCK_AUTOCOMPLETE' ) == 'true';
if( $sts_test_login ) {
	$sts_log_directory = $setting_table->get( 'option', 'DEBUG_LOG_FILE' );
	if( $sts_debug ) echo "<p>exp_login1: sts_log_directory = $sts_log_directory sts_crm_dir = $sts_crm_dir</p>";
	
	if( isset($sts_log_directory) && $sts_log_directory <> '' && 
		$sts_log_directory[0] <> '/' && $sts_log_directory[0] <> '\\' 
		&& $sts_log_directory[1] <> ':' )
		$sts_log_directory = $sts_crm_dir.$sts_log_directory;
	if( $sts_debug ) echo "<p>exp_login2: sts_log_directory = $sts_log_directory</p>";
}
		
function log_event( $event ) {
	global $sts_log_directory, $sts_debug;
	
	if( isset($sts_log_directory) ) {
		if( $sts_debug ) echo "<p>log_event: sts_log_directory = $sts_log_directory</p>";
		if(is_writable($sts_log_directory) ) {
			file_put_contents($sts_log_directory, date('m/d/Y h:i:s A')." ".$event.PHP_EOL, FILE_APPEND);
		} else {
			if( $sts_debug ) echo "<p>log_event: $sts_log_directory not writeable</p>";
		}
	}
}

function uptime( $file ) {

	$result = '';
	if (file_exists($file)) {
		$upsince = filemtime($file);
		$gettime = (time() - filemtime($file));
		$days = floor($gettime / (24 * 3600));
		$gettime = $gettime - ($days * (24 * 3600));
		$hours = floor($gettime / (3600));
		$gettime = $gettime - ($hours * (3600));
		$minutes = floor($gettime / (60));
		$gettime = $gettime - ($minutes * 60);
		$seconds = $gettime;
		
		$result = '/Up:&nbsp;'.$days.'d&nbsp;'.$hours.'h&nbsp;'.$minutes.'m&nbsp;'.$seconds.'s';
	}
	return $result;
}

$sign_in_message = "Please sign in";
$csrf_string = $_SERVER["HTTP_HOST"].date('F');

	if( $sts_debug ) {
		echo "<p>server = </p>
		<pre>";
		var_dump($_SERVER, $csrf_string);
		echo "</pre>";
	}

if ( isset($_POST['username']) && isset($_POST['password']) ) {
	//! CSRF Token check
	if( isset($_POST) && isset($_POST["CSRF"]) && $_POST["CSRF"] == str_rot13($csrf_string)) {
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
			if( $sts_debug ) echo "<p>login: passed_catcha</p>";
			$user_table = new sts_user($exspeedite_db, $sts_debug);		// User table
			$my_session = sts_session::getInstance( $exspeedite_db, $sts_debug );				// Session object
			$logged_in = $my_session->login( $user_table, $_POST['username'], $_POST['password'] );
			
			if( $logged_in ) {
				//! Remember if we are an ios device
				if( isset($_POST['ios']) && $_POST['ios'] <> '')
					$_SESSION['ios'] = $_POST['ios'];
				if( isset($_POST['chrome']) && $_POST['chrome'] <> '')
					$_SESSION['chrome'] = $_POST['chrome'];
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

				if( $sts_test_login ) {
					log_event( "exp_login: Login OK, USER=".$_POST['username'].
						" ios=".(isset($_SESSION['ios']) ? $_SESSION['ios'] : 'not set').
						" chrome=".(isset($_SESSION['chrome']) ? $_SESSION['chrome'] : 'not set') );
				}

				if( ! $sts_debug )
					reload_page ( $my_session->in_group(EXT_GROUP_DRIVER) ? "exp_listload_driver.php" :
						((isset($return_path) ? 
						$return_path :	// Return to saved page
						"index.php")) );	// Or go to home page
				else
					echo "<p>reload_page ".( (isset($return_path) ? 
						$return_path : "index.php") )."</p>";
			} else { // login fail
				if( $sts_test_login ) {
					log_event( "exp_login: Login fail, USER=".$_POST['username'].
						" PW=".$_POST['password']);
				}
				$sign_in_message = "Please Try again";
			}
		} else { // Captcha fail
			if( $sts_debug ) echo "<p>login: Captcha fail</p>";
			if( $sts_test_login ) {
				log_event( "exp_login: Captcha fail, USER=".$_POST['username'].
					" use_captcha=".($sts_use_captcha ? 'true' : 'false').
					" captcha=".(isset($_SESSION['captcha']) ? $_SESSION['captcha']['code'] : 'not set').
					" supplied=".(isset($_POST['captcha']) ? $_POST['captcha'] : 'not set'));
			}
			$sign_in_message = "Please Try again";
		}
	} else { // CSRF fail
		if( $sts_test_login ) {
			log_event( "exp_login: CSRF fail, USER=".$_POST['username'].
				" CSRF=".(isset($_POST["CSRF"]) ? $_POST["CSRF"] : ''));
		}
		$sign_in_message = "Please Try again.";
	}
}

if( $sts_use_captcha_after_fail && $sign_in_message <> "Please Try again" &&
	! isset($_GET['captcha'])) {
	$sts_use_captcha = false;
	if( isset($_SESSION['captcha'])) unset($_SESSION['captcha']);
}

if( $sts_use_captcha ) {
	$_SESSION['captcha'] = simple_php_captcha(array(
	    'min_length' => 3,
	    'max_length' => 4) );
}

$check = $exspeedite_db->get_one_row(
	"SELECT Round(Sum(data_length + index_length) / 1024 / 1024, 0) SIZE 
	FROM information_schema.tables
	where table_schema = '".$sts_database."'");
$db_size = is_array($check) && count($check) == 1 && isset($check["SIZE"]) ? $check["SIZE"] : 0;

$ut = uptime( $sts_pid_file );

if(!function_exists('apache_get_version')){
    function apache_get_version(){
        if(!isset($_SERVER['SERVER_SOFTWARE']) || strlen($_SERVER['SERVER_SOFTWARE']) == 0){
            return false;
        }
        return $_SERVER["SERVER_SOFTWARE"];
    }
}

?>
<style>
.delay1 {
  -webkit-animation-delay: 1s;
  animation-delay: 1s;
}
.delay2 {
  -webkit-animation-delay: 2s;
  animation-delay: 2s;
}

.digit2 {
  float: right;
  font-size: 21px;
  font-weight: bold;
  line-height: 1;
  color: #000;
  text-shadow: 0 1px 0 #fff;
  filter: alpha(opacity=20);
  opacity: .2;
}

.digit {
	height: 160px;
	margin: auto;
	position: absolute;
	right: 10px;
	top: 20px;
	color: #000;
	text-shadow: 0 1px 0 #fff;
	filter: alpha(opacity=20);
	opacity: .2;
}

</style>


	<div class="container container-fuzzy well well-lg well-fuzzy">
		<br>
		<div class="row text-center">
			<img class="img-responsive center-block animated rubberBand delay1" src="images/EXSPEEDITEmedr.png" alt="<?php echo $sts_title ?>">
			<h3 id="Product" style="margin-top: 5px"><i>Exspeedite&reg; <?php echo $sts_release_year ?> - Release <?php echo $sts_release_name ?></i></h3>
			<img class="digit" src="images/release9.png">
		
			<div class="panel-fuzzy text-center">
				<h2 style="margin-top: 2px" class="text-center"><?php if( file_exists($sts_company_logo) || url_exists($sts_company_logo)) echo '<img src="'.$sts_company_logo.'" class="img-responsive center-block">'; ?><br><?php echo $sts_company_name ?></h2>
			</div>
		</div>

		<form class="form-signin animated pulse delay2" role="form" action="exp_login.php" method="post" enctype="multipart/form-data" name="login" target="_top" <?php echo $sts_block_autocomplete ? 'autocomplete="off"' : ''; ?>>
			<h3 class="form-signin-heading"><?php echo $sign_in_message; ?></h3>
<?php if( isset($_GET['return']) )
		echo '<input name="return" type="hidden" value="'.$_GET['return'].'" />
		';
	echo '<input name="CSRF" type="hidden" value="'.str_rot13($csrf_string).'">
	';
?>
			<input name="ios" id="ios" type="hidden" value="false" />
			<input name="chrome" id="chrome" type="hidden" value="false" />
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
					<a class="btn btn-lg btn-default" href="exp_login.php?captcha"><span class="glyphicon glyphicon-refresh"></span></a>
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
		<br>
				<p class="text-muted text-right">Exspeedite&reg; developed by the cool guys at <a href="https://www.exspeedite.com/" target="_blank" xhidden="<?php echo 'APACHE='.apache_get_version().' PHP='.phpversion().' MYSQL='.$setting_table->server_info().' END'; ?>" title="<?php echo 'REL='.$sts_release_name." (".'SCHEMA='.$sts_schema_release_name.")\n".'DB='.$sts_database.($db_size > 0 ? '/'.$db_size.'MB' : '').$ut." ".'TZ='.$sts_crm_timezone; ?>">Exspeedite <img src="https://www.exspeedite.com/wp-content/uploads/2018/03/link.png" height="16" border="0"></a><br>Copyright &copy; since 2014 Exspeedite - All rights reserved.</p>
			<p class="text-left" id="Size">xx</p>
	</div> <!-- /container -->

	<script language="JavaScript" type="text/javascript"><!--
			// Sometime in 2019, Apple changed the navigator.platform attribute.
			// This function confirms if the browser is on a touchscreen device.
			function is_touch_device() {
			  return !!('ontouchstart' in window        // works on most browsers 
			  || navigator.maxTouchPoints);       // works on IE10/11 and Surface
			};

		$(document).ready( function () {

			p = navigator.platform.substr(0, 4);
			if( p === 'iPad' || p === 'iPho' || p === 'iPod' ||
				(p === 'MacI' && is_touch_device()) ){
			    $("#ios").val("true");
			}
			if( /chrom(e|ium)/.test(navigator.userAgent.toLowerCase()) ) {
			    $("#chrome").val("true");
			}
			
			function update_dialog() {
				//console.log( 'dims ', $(window).width(), $(window).height(), $('.container-fuzzy').width(),  $('.container-fuzzy').height(), window.innerHeight );
				console.log( 'width=', $(window).width(), 'height=', $(window).height(), 'cont_width=', $('.container-fuzzy').width(), 'cont_height=', $('.container-fuzzy').height() );
				$('#Size').html('Window: ' + $(window).width() + ' x ' + $(window).height() + ' Dialog: ' + $('.container-fuzzy').width() + ' x ' + $('.container-fuzzy').height() );
				
				xscale = ($(window).width() / $('.container-fuzzy').width() * 0.5).toFixed(2);
				yscale = ($(window).height() / $('.container-fuzzy').height() * 0.5).toFixed(2);
				newScale = Math.min(xscale, yscale)*1.2;
				if( $(window).height() > 1500 ) {
					newScale =  newScale*0.8;
				} else if( $(window).height() > 1100 ) {
					newScale =  newScale*0.8;
				} else if( $(window).width() > $(window).height() && $(window).height() < 500 ) {
					newScale =  newScale*1.8;
				}
				else if( $(window).width() < 1000 ) {
					newScale = ($(window).width() / $('.container-fuzzy').width() * 0.9).toFixed(2);
				}
				console.log('height=', $('.container-fuzzy').height() * newScale, 'space=', ($(window).height() - ($('.container-fuzzy').height() * newScale)) );
				
				
				console.log( 'xscale=', xscale, 'yscale=', yscale, 'newScale=', newScale );
				//if( $(window).width() > 2000 &&
				//	! (newScale <  1.0 && $('.container-fuzzy').width() < $(window).width() ) ) {
					if( 1 ) {
					$('.container-fuzzy').css({
						'-webkit-transform' : 'scale(' + newScale + ')',
						'-moz-transform' : 'scale(' + newScale + ')',
						'-ms-transform' : 'scale(' + newScale + ')',
						'-o-transform' : 'scale(' + newScale + ')',
						'transform' : 'scale(' + newScale + ')',
						// This next line is crucial, it anchors the change at the top
						'transform-origin' : 'top'
						
					}).change();
				}
			}
//'margin-top' : newPos + 'px'
// 'margin-top' : '20%'
			if (/PhantomJS/.test(window.navigator.userAgent)) {
				console.log("exp_login.php: PhantomJS environment detected.");
			} else {
			$(window).bind('orientationchange', function(e) {
				console.log('orientationchange event triggered');
				if (window.RT) clearTimeout(window.RT);
				window.RT = setTimeout(function() {
					this.location.reload(false); /* false to get page from cache */
				}, 100);
			});
			
			//$(window).bind('resize', function(e) {
			//	update_dialog();
			//});
			update_dialog();
			}
		});
	//--></script>
<?php

require_once( "include/footer_inc.php" );
?>
