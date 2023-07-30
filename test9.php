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

$sts_subtitle = "Test9 - KeepTruckin";
//require_once( "include/header_inc.php" );

if( isset($_GET['ZIP'])) {
	require_once( "include/sts_archive_class.php" );
	$archive = sts_archive::getInstance($exspeedite_db, $sts_debug);
	$archive->zip_archive($_GET['ZIP']);
	die;
} else
if( isset($_GET['WIN'])) {
		echo "<pre>";
		var_dump(get_current_user());
		var_dump(getenv("username"));
		var_dump(getmypid());
		var_dump(sprintf("%08x", abs(crc32($_SERVER['REMOTE_ADDR'] . $_SERVER['REQUEST_TIME'] . $_SERVER['REMOTE_PORT']))) );
		var_dump($_SERVER['REQUEST_TIME_FLOAT']);
		var_dump( substr($_SERVER['REQUEST_TIME_FLOAT']*1000, -4) );
		echo "</pre>";
} else
if( isset($_GET['LVIEW'])) {
	require_once( "include/sts_archive_class.php" );
	$archive = sts_archive::getInstance($exspeedite_db, $sts_debug);
	
	echo '<h2>View Archive File</h2>
	'.$archive->view_file( $_GET['LVIEW'] );
	die;
} else
if( isset($_GET['LVERIFY'])) {
	require_once( "include/sts_archive_class.php" );
	$archive = sts_archive::getInstance($exspeedite_db, $sts_debug);
	
	echo "<pre>Verify\n";
	var_dump($_GET['LVERIFY']);
	var_dump($archive->verify_file( $_GET['LVERIFY'] ));
	echo "</pre>";
	die;
} else
if( isset($_GET['LDELETE'])) {
	require_once( "include/sts_archive_class.php" );
	$archive = sts_archive::getInstance($exspeedite_db, $sts_debug);
	
	echo "<pre>Delete\n";
	var_dump($archive->delete_load( $_GET['LDELETE'] ));
	echo "</pre>";
	die;
} else
if( isset($_GET['LRESTORE'])) {
	require_once( "include/sts_archive_class.php" );
	$archive = sts_archive::getInstance($exspeedite_db, $sts_debug);
	
	echo "<pre>Restore\n";
	var_dump($archive->restore_file( $_GET['LRESTORE'] ));
	var_dump($archive->last_archive_file());
	var_dump($archive->verify_file( $_GET['LRESTORE'] ));
	echo "</pre>";
	die;
} else
if( isset($_GET['LARCHIVE'])) {
	require_once( "include/sts_archive_class.php" );
	$archive = sts_archive::getInstance($exspeedite_db, $sts_debug);
		
	echo "<pre>Archive\n";
	var_dump($archive->archive_load( $_GET['LARCHIVE'] ));
	var_dump($archive->last_archive_file());
	var_dump($archive->verify_file());
	echo "</pre>";
	die;
} else

if( isset($_GET['SDELETE'])) {
	require_once( "include/sts_archive_class.php" );
	$archive = sts_archive::getInstance($exspeedite_db, $sts_debug);
	
	echo "<pre>Delete\n";
	var_dump($archive->delete_shipment( $_GET['SDELETE'] ));
	echo "</pre>";
	
} else
if( isset($_GET['SARCHIVE'])) {
	require_once( "include/sts_archive_class.php" );
	$archive = sts_archive::getInstance($exspeedite_db, $sts_debug);
		
	echo "<pre>Archive\n";
	var_dump($archive->archive_shipment( $_GET['SARCHIVE'] ));
	var_dump($archive->last_archive_file());
	var_dump($archive->verify_file());
	echo "</pre>";
	
} else
if( isset($_GET['ALERT'])) {
	require_once( "include/sts_alert_class.php" );
	$alert_table = sts_alert::getInstance($exspeedite_db, $sts_debug);
	echo $alert_table->get_alerts();

} else
if( isset($_GET['PRINT'])) {
$str = ' WI†';
$str2 = trim((string) $str);
$str3 =  preg_replace( '/[^[:print:]]/', '',$str);
$str4 =  preg_replace( '/[^[:print:]]/', '',trim((string) $str) );
	echo "<pre>";
	var_dump($str);
	var_dump($str2);
	var_dump($str3);
	var_dump($str4);
	echo "</pre>";
	require_once( "include/sts_zip_class.php" );
	$zip_table = sts_zip::getInstance($exspeedite_db, $sts_debug);
	echo "<pre>";
	var_dump($zip_table->zip_lookup('45833'));
	var_dump($zip_table->zip_lookup('V3B 7V8'));
	echo "</pre>";

} else
if( isset($_GET['ATT'])) {
	require_once( "include/sts_attachment_class.php" );
	$attachment_table = sts_attachment::getInstance($exspeedite_db, $sts_debug);
	$path = $attachment_table->unmoved_attachment();
	
	echo "<pre>Attachment\n";
	var_dump($path, $attachment_table->move( $path[1][0] ));
	echo "</pre>";
	
} else
if( isset($_GET['SS'])) {

	function get_data( $token, $url ) {
		$result = false;
		$ch = curl_init($url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false);   // Do not follow; security risk here
		curl_setopt($ch, CURLOPT_HTTPHEADER, array(
		    'Authorization: Bearer '.$token,
		));
	
		$retr = curl_exec($ch);
		$info = curl_getinfo($ch);
		if( isset($info["http_code"]) && $info["http_code"] = 200 ) {
			$result = json_decode($retr, true);
		}
		return $result;
	}
	
	$token = '390AueFoVTYAUONZoKBthcxwg2SzJs';
	
	$list_address = 'https://api.samsara.com/v1/addresses';		// 2 addresses
	$list_asset = 'https://api.samsara.com/v1/fleet/assets';	// none
	$list_asset_locations = 'https://api.samsara.com/v1/fleet/assets/locations';	// none
	$list_routes = 'https://api.samsara.com/v1/fleet/dispatch/routes';	// none
	$list_trips = 'https://api.samsara.com/v1/fleet/trips';	// none
	$list_vehicles_locations = 'https://api.samsara.com/v1/fleet/vehicles/locations';	// none
	$list_vehicles_stats = 'https://api.samsara.com/v1/fleet/vehicles/stats';	// none
	$list_vehicles = 'https://api.samsara.com/v1/fleet/vehicles';	// none

	$list_fleet = 'https://api.samsara.com/v1/fleet/list';	// list of vehicles
	$list_driver = 'https://api.samsara.com/v1/fleet/drivers';	// got some
	
	$data = get_data( $token, $list_fleet );
	//echo "<pre>";
	//var_dump($data);
	//echo "</pre>";
	echo "<h1>From Samsara API</h1>";
	echo "<h2>Vehicles</h2>";
	if( is_array($data) && is_array($data["vehicles"])) {
		foreach( $data["vehicles"] as $vehicle ) {
			echo "<p>id = ".number_format($vehicle["id"],0,".","").
				" name = ".$vehicle["name"].
				" vin = ".$vehicle["vin"]."</p>";
		}
	}

	$data = get_data( $token, $list_driver );
	//echo "<pre>";
	//var_dump($data);
	//echo "</pre>";
	echo "<h2>Drivers</h2>";
	if( is_array($data) && is_array($data["drivers"])) {
		foreach( $data["drivers"] as $driver ) {
			echo "<p>id = ".number_format($driver["id"],0,".","").
				" name = ".$driver["name"].
				" vehicleId = ".number_format($driver["vehicleId"],0,".","").
				(isset($driver["currentVehicleId"]) ? " currentVehicleId = ".number_format($driver["currentVehicleId"],0,".","") : "")."</p>";
		}
	}
	
} else
if( isset($_GET['DT'])) {
//! Check if the date string is valid
function validateDateTime1($format) {
    return function($dateStr) use ($format) {
        $date = DateTime::createFromFormat($format, $dateStr);
        return $date && $date->format($format) === $dateStr;
    };
}

$validDate1 = validateDateTime1('n/d/Y');
$validDate2 = validateDateTime1('m/d/Y');

function checkIsAValidDate($myDateString){
    return (bool)strtotime($myDateString);
}

$validDate3 = 'checkIsAValidDate';

	echo "<pre>";
	var_dump($validDate1('07/31/2019'));
	var_dump($validDate1('7/31/2019'));
	var_dump($validDate2('07/31/2019'));
	var_dump($validDate2('7/31/2019'));

	var_dump(checkIsAValidDate('07/31/2019'));
	var_dump(checkIsAValidDate('7/31/2019'));
	var_dump($validDate3('07/31/2019'));
	var_dump($validDate3('7/31/2019'));
	echo "</pre>";


} else
if( isset($_GET['KT2'])) {
$kt = sts_keeptruckin::getInstance($exspeedite_db, $sts_debug);

	$data = $kt->companies();
	
		echo "<pre>companies\n";
		var_dump($data);
		echo "</pre>";

	$data = $kt->admin_users();
	
		echo "<pre>admin\n";
		var_dump($data);
		echo "</pre>";

} else
if( isset($_GET['KT1'])) {
$kt = sts_keeptruckin::getInstance($exspeedite_db, $sts_debug);

$il = sts_ifta_log::getInstance($exspeedite_db, $sts_debug);
		echo "<pre>";
		//var_dump($kt);
		var_dump($il->log_event( 'Test9: testing1', EXT_ERROR_DEBUG ));
		var_dump($kt->log_event( 'Test9: testing2', EXT_ERROR_DEBUG ));
		var_dump($kt->test9());
		//var_dump($kt->current_driver_code(7505));
		echo "</pre>";
		die('end');	
	
} else
if( isset($_GET['NULL'])) {
	require_once( "include/sts_insp_report_class.php" );
	$report_table = sts_insp_report::getInstance($exspeedite_db, $sts_debug);
		echo "<pre>";
		var_dump($report_table->is_nullable("ODO"),
			$report_table->is_ai("ODO"),
			$report_table->get_default("ODO"),
			$report_table->is_required("ODO"));
		echo "</pre>";
		
} else
if( isset($_GET['TYPE']) && isset($_GET['UNIT'])) {
	require_once( "include/sts_insp_report_class.php" );
	$report_table = sts_insp_report::getInstance($exspeedite_db, $sts_debug);
	
	echo '<h2>R&M Summary for '.$_GET['TYPE'].' '.$_GET['UNIT'].'</h2>
	<div class="container-full theme-showcase" role="main">
	
	<div class="well  well-md">';
	echo $report_table->render_rm_report( $_GET['TYPE'], $_GET['UNIT']).'
	</div>
	</div>';
} else
if( isset($_GET['FOLLOW'])) {
	require_once( "include/sts_status_codes_class.php" );
	$status_codes_table = sts_status_codes::getInstance($exspeedite_db, $sts_debug);

	echo "<pre>";
	var_dump($status_codes_table->cache_following_states( 'shipment' ), $status_codes_table->cache_following_states( 'shipment-bill' ));
	echo "</pre>";
} else
if( isset($_GET['SCR'])) {
	require_once( "include/sts_scr_class.php" );
	$scr_table = sts_scr::getInstance($exspeedite_db, $sts_debug);

	echo "<pre>";
	var_dump($scr_table->is_nullable( "SETTINGS" ), $scr_table->is_ai( "SETTINGS" ), $scr_table->get_default( "SETTINGS" ), $scr_table->is_required( "SETTINGS" ));
	echo "</pre>";
} else
if( isset($_GET['ATTA'])) {
	require_once( "include/sts_attachment_class.php" );
	$attachment_table = sts_attachment::getInstance($exspeedite_db, $sts_debug);

	echo "<pre>";
	var_dump($attachment_table->manifest_attachments( $_GET['ATTA'] ));
	echo "</pre>";
} else
if( isset($_GET['REPORT'])) {
	require_once( "include/sts_insp_report_class.php" );
	$report_table = sts_insp_report::getInstance($exspeedite_db, $sts_debug);

	echo $report_table->render_report( $_GET['REPORT'] );
} else
if( isset($_GET['CHECK2'])) {
		echo "<pre>";
		var_dump($_POST);
		echo "</pre>";
	
	echo '
<style>
.check
{
    opacity:0.5;
	color: #996;
	border: 4px solid #c12e2a !important;
	
}
.img-trailer1 {
	background-color: #fff;
	border: 4px solid transparent;
	height: 100px;
}
.img-trailer2 {
	background-color: #fff;
	border: 4px solid transparent;
	height: 87px;
}
</style>
	
	<form role="form" class="form-horizontal" action="test9.php?CHECK2" 
				method="post" enctype="multipart/form-data" 
				name="test9" id="test9">
		<h3>Click to show location of damage</h3>
		<div class="form-group">
		<div class="col-sm-10 col-sm-offset-1">
		<label for="trailer_ls"><img src="images/trailer_ls.png" alt="trailer_ls" class="img-responsive img-trailer1 img-check"><input type="checkbox" name="trailer[]" id="trailer_ls" value="trailer_ls" class="hidden" autocomplete="off"></label>
		<label for="trailer_fr"><img src="images/trailer_fr.png" alt="trailer_fr" class="img-responsive img-trailer1 img-check"><input type="checkbox" name="trailer[]" id="trailer_fr" value="trailer_fr" class="hidden" autocomplete="off"></label>
		<label for="trailer_rs"><img src="images/trailer_rs.png" alt="trailer_rs" class="img-responsive img-trailer1 img-check"><input type="checkbox" name="trailer[]" id="trailer_rs" value="trailer_rs" class="hidden" autocomplete="off"></label>
		</div>
		</div>
		<div class="form-group">	
		<div class="col-sm-10 col-sm-offset-1">
		<label for="trailer_tp"><img src="images/trailer_tp.png" alt="trailer_tp" class="img-trailer2 img-check"><input type="checkbox" name="trailer[]" id="trailer_tp" value="trailer_tp" class="hidden" autocomplete="off"></label>
		<label for="trailer_re"><img src="images/trailer_re.png" alt="trailer_re" class="img-trailer2 img-check"><input type="checkbox" name="trailer[]" id="trailer_re" value="trailer_re" class="hidden" autocomplete="off"></label>
		<label for="trailer_fl"><img src="images/trailer_fl.png" alt="trailer_fl" class="img-trailer2 img-check"><input type="checkbox" name="trailer[]" id="trailer_fl" value="trailer_fl" class="hidden" autocomplete="off"></label>
		</div>
		</div>
	<button type="submit" name="save">Save</button>
		</form>
		
	<script language="JavaScript" type="text/javascript"><!--
		
		$(document).ready( function () {
			$(".img-check").click(function(){
				$(this).toggleClass("check");
			});
		});	
	//--></script>
	
		';
} else
if( isset($_GET['CHECK'])) {
		echo "<pre>";
		var_dump($_POST);
		echo "</pre>";
	
	echo '
<style>
.check
{
    opacity:0.5;
	color:#996;
	
}

.img-trailer1 {
	background-color: #fff;
	height: 100px;
}
.img-trailer2 {
	background-color: #fff;
	height: 87px;
}
</style>
	
	<form role="form" class="form-horizontal" action="test9.php?CHECK" 
				method="post" enctype="multipart/form-data" 
				name="test9" id="test9">
		<h3>Click to show location of damage</h3>
		<div class="form-group">
		<div class="col-sm-10 col-sm-offset-1">
		<label class="btn btn-danger"><img src="images/trailer_ls.png" alt="trailer_ls" class="img-responsive img-trailer1 img-check"><input type="checkbox" name="trailer_ls" id="trailer_ls" value="trailer_ls" class="hidden" autocomplete="off"></label>
		<label class="btn btn-danger"><img src="images/trailer_fr.png" alt="trailer_fr" class="img-responsive img-trailer1 img-check"><input type="checkbox" name="trailer_fr" id="trailer_fr" value="trailer_fr" class="hidden" autocomplete="off"></label>
		<label class="btn btn-danger"><img src="images/trailer_rs.png" alt="trailer_rs" class="img-responsive img-trailer1 img-check"><input type="checkbox" name="trailer_rs" id="trailer_rs" value="trailer_rs" class="hidden" autocomplete="off"></label>
		</div>
		</div>
		<div class="form-group">	
		<div class="col-sm-10 col-sm-offset-1">
		<label class="btn btn-danger"><img src="images/trailer_tp.png" alt="trailer_tp" class="img-trailer2 img-check"><input type="checkbox" name="trailer_tp" id="trailer_tp" value="trailer_tp" class="hidden" autocomplete="off"></label>
		<label class="btn btn-danger"><img src="images/trailer_re.png" alt="trailer_re" class="img-trailer2 img-check"><input type="checkbox" name="trailer_re" id="trailer_re" value="trailer_re" class="hidden" autocomplete="off"></label>
		<label class="btn btn-danger"><img src="images/trailer_fl.png" alt="trailer_fl" class="img-trailer2 img-check"><input type="checkbox" name="trailer_fl" id="trailer_fl" value="trailer_fl" class="hidden" autocomplete="off"></label>
		</div>
		</div>
	<button type="submit" name="save">Save</button>
		</form>
		
	<script language="JavaScript" type="text/javascript"><!--
		
		$(document).ready( function () {
			$(".img-check").click(function(){
				$(this).toggleClass("check");
			});
		});	
	//--></script>
	
		';
} else
if( isset($_GET['RADIO'])) {
		echo "<pre>";
		var_dump($_POST);
		var_dump($_GET);
		echo "</pre>";
	
	echo '<form role="form" class="form-horizontal" action="test9.php?RADIO" 
				method="post" enctype="multipart/form-data" 
				name="test9" id="test9">
<div class="btn-group" data-toggle="buttons">
      <label class="btn btn-sm btn-default">
        <input type="radio" name="options" value="option1v" checked> Option 1
      </label>
      <label class="btn btn-sm btn-default">
        <input type="radio" name="options" value="option2v"> Option 2
      </label>
      <label class="btn btn-sm btn-default">
        <input type="radio" name="options" value="option3v"> Option 3
      </label>
</div>
	<button type="submit" name="save">Save</button>
		</form>';
} else
if( isset($_GET['INSP'])) {
	require_once( "include/sts_insp_report_class.php" );
	$report = sts_insp_report::getInstance( $exspeedite_db, $sts_debug );
	$report->create_empty('trailer', 1 );
	echo '<p>Done.</p>';

} else
if( isset($_GET['TH'])) {
	$uniqueid = sprintf("%08x", abs(crc32($_SERVER['REMOTE_ADDR'] . $_SERVER['REQUEST_TIME'] . $_SERVER['REMOTE_PORT'])));
		echo "<pre>";
		var_dump($uniqueid );
		echo "</pre>";
	
} else
if( isset($_GET['DT'])) {
$format = 'Y-m-d\TH:i';
$in = '2010-04-23T10:29';
$date = DateTime::createFromFormat('j-M-Y', '15-Feb-2009');
echo $date->format('Y-m-d');
$date = DateTime::createFromFormat($format, $in);
	echo "<pre>";
	var_dump($format);
	var_dump($format);
	var_dump($date->format($format));
	echo "</pre>";

} else
if( isset($_GET['BOC'])) {
	require_once( "include/sts_exchange_class.php" );
	$ec = sts_exchange::getInstance($exspeedite_db, $sts_debug);
	$ec->get_boc_rates();

} else {
require_once( "include/sts_ifta_log_class.php" );

$kt = sts_keeptruckin::getInstance($exspeedite_db, $sts_debug);

//$url = 'https://api.keeptruckin.com/v1/inspection_reports';
//$url = 'https://api.keeptruckin.com/v1/logs';
$url = 'https://api.keeptruckin.com/v1/users';
//$url = 'https://api.keeptruckin.com/v1/company_webhooks';


$data = $kt->fetch( $url );

	echo "<pre>messages\n";
	var_dump($data);
	echo "</pre>";
	
	//echo "<p>VIN = ".$data["vin"]."</p>";
	//echo "<p>unit 7506 = ".$kt->tractor_code(7506)."</p>";
}
//require_once( "include/footer_inc.php" );
?>