<?php 
// Set flag that this is a parent file
define( '_STS_INCLUDE', 1 );

// Setup Session
require_once( "include/sts_session_setup.php" );

require_once( "include/sts_config.php" );

$sts_debug = isset($_GET['debug']) && in_group( EXT_GROUP_DEBUG );
require_once( "include/sts_session_class.php" );

$my_session = sts_session::getInstance( $exspeedite_db, $sts_debug );
$my_session->access_check( EXT_GROUP_ADMIN );	// Make sure we should be here

if( ! $my_session->superadmin() ) {
	reload_page ( "index.php" );
}

$sts_subtitle = "Check Release Levels";
require_once( "include/header_inc.php" );
require_once( "include/navbar_inc.php" );

?>
<div class="container-full theme-showcase" role="main">

<div class="well  well-lg">
<?php
	
$releases = array(
	'http://thefutureisnow.biz/exp_login.php',
	//'http://192.168.1.105/exp_login.php',
	'https://slingshot.exspeedite.net/exp_login.php',
//	'https://testslingshot.exspeedite.net/exp_login.php',
	'https://sstest.exspeedite.net/exp_login.php',
	'https://bm.exspeedite.net/exp_login.php',
//	'https://sales.exspeedite.net/exp_login.php',
//	'https://exp1.exspeedite.net/exp_login.php',
//	'https://200.exspeedite.net/exp_login.php',
	'https://orcloud.exspeedite.net/exp_login.php',
	'https://falcon.exspeedite.net/exp_login.php',
//	'https://newclient.exspeedite.net/exp_login.php',
//	'https://franceslingshot.exspeedite.net/exp_login.php',
//	'https://avalon.exspeedite.net/exp_login.php',
//	'https://midway.exspeedite.net/exp_login.php',
);

	//! SCR# 185 - log when we do a repair DB
	require_once( "include/sts_user_log_class.php" );
	$user_log_table = sts_user_log::getInstance($exspeedite_db, $sts_debug);
	$user_log_table->log_event('admin', 'Check Release Levels');

	// Turn off output buffering
	ini_set('output_buffering', 'off');
	// Implicitly flush the buffer(s)
	ini_set('implicit_flush', true);
	ob_implicit_flush(true);

	if (ob_get_level() == 0) ob_start();

		echo '<h2>Exspeedite Releases</h2>
		<div class="table-responsive">
		<table class="display table table-striped table-condensed table-bordered table-hover" id="RELEASES">
		<thead><tr class="exspeedite-bg"><th>URL</th><th>REL</th><th>SCHEMA</th><th>DB</th><th>TZ</th></tr>
		</thead>
		<tbody>';

foreach( $releases as $rel ) {
	try {
		$arrContextOptions=array(
		    "ssl"=>array(
		        "verify_peer"=>false,
		        "verify_peer_name"=>false,
		    ),
		);  

		$raw = file_get_contents($rel, false, stream_context_create($arrContextOptions) );
		// search for something like this: "REL=3.0.38 (SCHEMA=3_0_38) DB=slingshot TZ=America/Chicago"
		$match1 = '/\"APACHE=(.*)\sPHP=(.*)\sMYSQL=(.*)\sEND\"/';
		$extra = preg_match($match1, $raw, $matches1);
	} catch (Exception $e) {
		$raw = '';
	}

	$match = '/\"REL=(.*)\s\(SCHEMA=([^\)]*)\)\sDB=([^\s]*)\sTZ=([^\"]*)\"/';
	if( preg_match($match, $raw, $matches) ) {
		$db = implode('<br>', explode('/', $matches[3]));
		echo '<tr><td><a href="'.$rel.'" target="_blank">'.$rel.'</a>'.
			($extra ? '<br>APACHE='.$matches1[1].
				'<br>PHP='.$matches1[2].
				' MySQL='.$matches1[3] : '').'</td>
			<td>'.$matches[1].'</td>
			<td>'.$matches[2].'</td>
			<td>'.$db.'</td>
			<td>'.$matches[4].'</td>
			</tr>';			
	} else {
		echo '<tr><td colspan="5"><h3><a href="'.$rel.'" target="_blank">'.$rel.'</a> Not Responding</h3></td>
			</tr>';
	}
	ob_flush(); flush();
	
}
		echo '</tbody>
		</table>
		</div>
				</div>
	</div>
';

?>
</div>
</div>
<?php
require_once( "include/footer_inc.php" );
?>