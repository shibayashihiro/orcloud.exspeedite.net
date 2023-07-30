<?php
// Set flag that this is a parent file
define( '_STS_INCLUDE', 1 );

// Setup Session
require_once( "include/sts_session_setup.php" );

require_once( "include/sts_config.php" );
$sts_debug = (isset($_GET['debug'])  || isset($_POST['debug'])) && in_group( EXT_GROUP_DEBUG );
//$sts_debug = isset($_POST) && count($_POST) > 0 ;
require_once( "include/sts_session_class.php" );

set_time_limit(0);

$my_session = sts_session::getInstance( $exspeedite_db, $sts_debug );
$my_session->access_check( EXT_GROUP_ADMIN );	// Make sure we should be here

require_once( "include/sts_driver_class.php" );
require_once( "include/sts_client_class.php" );
require_once( "include/sts_carrier_class.php" );
require_once( "include/sts_contact_info_class.php" );
require_once( "include/sts_license_class.php" );
require_once( "include/sts_tractor_class.php" );
require_once( "include/sts_trailer_class.php" );
require_once( "include/sts_csv_class.php" );
require_once( "include/sts_setting_class.php" );

if( isset($_POST) && isset($_POST["template"])) {
	$class_name = 'sts_'.($_POST["PROFILE"] == 'lead' ? 'client' : $_POST["PROFILE"]);
	$profile_table = $class_name::getInstance($exspeedite_db, $sts_debug);
	$csv = new sts_csv($profile_table, "", $sts_debug);

	if( ! $sts_debug ) $csv->header( $_POST["PROFILE"].'_template' );
	$parse = 'sts_csv_'.$_POST["PROFILE"].'_parse';
	if( $sts_debug ) {
			echo "<pre>XYZZY\n";
			var_dump($parse);
			var_dump($$parse);
			echo "</pre>";
	}
	$csv->render_template( $$parse );
	die;
}

$sts_subtitle = "Import Profile from CSV";
require_once( "include/header_inc.php" );
require_once( "include/navbar_inc.php" );


$setting = sts_setting::getInstance($exspeedite_db, $sts_debug);

$client_table = sts_client::getInstance($exspeedite_db, $sts_debug);
$contact_info_table = sts_contact_info::getInstance($exspeedite_db, $sts_debug);
$csv = new sts_csv(false, false, $sts_debug);
$cat = sts_client_activity::getInstance($exspeedite_db, $sts_debug);

$setting_table = sts_setting::getInstance($exspeedite_db, $sts_debug);
$sts_import_duplicates = $setting_table->get("option", "ZOOMINFO_IMPORT_DUP") == 'true';

$entry_state = $client_table->behavior_state['entry'];

?>
<div class="container theme-showcase" role="main">

<?php
if( isset($_POST) && isset($_POST["import"])) {
	// Turn off output buffering
	ini_set('output_buffering', 'off');
	// Implicitly flush the buffer(s)
	ini_set('implicit_flush', true);
	ob_implicit_flush(true);

	if (ob_get_level() == 0) ob_start();
	
	echo '<div id="loading"><p class="text-center"><img src="images/loading.gif" alt="loading" width="125" height="125" /><br>Importing '.$_POST["PROFILE"].'s...</p>
	</div>';
	ob_flush(); flush();

	//! import the CSV file
	$data = $csv->import($_FILES["FILE_NAME"], true); //! SCR# 517 - add verbose
	
	//echo "<pre>";
	//var_dump($data);
	//echo "</pre>";
	
	if( is_array($data) && count($data) > 0 ) {
		$parse = 'sts_csv_'.$_POST["PROFILE"].'_parse';
		$csv->parse( $data, $$parse );
		
		echo '<br><br><a class="btn btn-sm btn-default" href="exp_import_profile.php"><span class="glyphicon glyphicon-arrow-left"></span> Back</a>';
		sleep(2);
		update_message( 'loading', '' );
		
		
		//if( ! $sts_debug )
		//	reload_page ( "exp_listclient.php" );
	} else {
		echo '<p>Something failed in the import. Make sure the file was ending in .csv, and that it
		was in the format like the template. There should be headings in the first line of the file.</p>
		<p>If you have issues beyond that, you could send a copy of the file that failed to import
		to Exspeedite support, and we will take a look at it.</p>';
	}

	
} else {
	$valid_profiles = array( 'driver' => 'Drivers',
							'client'  => 'Clients', 
							'lead'  => 'Leads', 
							'tractor' => 'Tractors', 
							'trailer' => 'Trailers', 
							'carrier' => 'Carriers' );

	echo '<div class="well well-lg">
	<form role="form" class="form-inline" action="exp_import_profile.php" 
						method="post" enctype="multipart/form-data" 
						name="import_profile" id="import_profile">
	'.(isset($_GET['debug']) ? '<input name="debug" type="hidden" value="on">' : '').'
		<h2><img src="images/setting_icon.png" alt="setting_icon" height="24"> Import ';
		
echo '<div class="form-group"><select class="form-control input-md" name="PROFILE" id="PROFILE">';
foreach( $valid_profiles as $value => $label ) {
	echo '<option value="'.$value.'">'.$label.'</option>
	';
}
echo '</select>';
		
	echo ' <div class="btn-group"><button class="btn btn-md btn-success" name="import" type="submit" ><span class="glyphicon glyphicon-ok"></span> Import CSV</button><button class="btn btn-md btn-primary" name="template" type="cancel" formnovalidate><span class="glyphicon glyphicon-list-alt"></span> CSV Template</button><a class="btn btn-md btn-default" href="index.php"><span class="glyphicon glyphicon-remove"></span> Back</a></div></div></h2>
	
		<div class="form-group">
			<br>
			<div class="col-sm-9">
				<div class="form-group">
					<label for="FILE_NAME" class="col-sm-4 control-label">CSV File</label>
					<div class="col-sm-6">
						<input name="FILE_NAME" id="FILE_NAME" type="file" required>
					
					</div>
				</div>
			</div>
		</div>
		
		
	</form>
	<br>
	<div class="alert alert-info">
		<p>This will allow importing from CSV into the multiple table structure in Exspeedite.</p>
		<p>It can create a template to help you with your import, or one you can give to a client</p>
		<p>For import, you need the first row to have the column headings. The order is not important.</p>
		<p>For drivers, it creates one row in EXP_DRIVER, one row in EXP_CONTACT_INFO
		and one row in EXP_LICENSE.<br>If you need multiple rows for multiple contact info, or multiple licenses, you are out of luck.</p>
	</div>
	
</div>
';
}

require_once( "include/footer_inc.php" );
?>