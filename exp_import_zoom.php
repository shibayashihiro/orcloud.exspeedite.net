<?php 
// Set flag that this is a parent file
define( '_STS_INCLUDE', 1 );

// Setup Session
require_once( "include/sts_session_setup.php" );

require_once( "include/sts_config.php" );
$sts_debug = (isset($_GET['debug'])  || isset($_POST['debug'])) && in_group( EXT_GROUP_DEBUG );
//$sts_debug = isset($_POST) && count($_POST) > 0 ;
require_once( "include/sts_session_class.php" );
set_time_limit(120);
ini_set('memory_limit', '1024M');

$my_session = sts_session::getInstance( $exspeedite_db, $sts_debug );
$my_session->access_check( EXT_GROUP_ADMIN );	// Make sure we should be here

$sts_subtitle = "Import Leads From Zoominfo";
require_once( "include/header_inc.php" );
require_once( "include/navbar_inc.php" );

require_once( "include/sts_form_class.php" );
require_once( "include/sts_client_class.php" );
require_once( "include/sts_contact_info_class.php" );
require_once( "include/sts_client_activity_class.php" );
require_once( "include/sts_csv_class.php" );
require_once( "include/sts_setting_class.php" );
require_once( "include/sts_user_class.php" );
require_once( "include/sts_zip_class.php" );

$setting = sts_setting::getInstance($exspeedite_db, $sts_debug);
$user_table = sts_user::getInstance($exspeedite_db, $sts_debug);

$client_table = sts_client::getInstance($exspeedite_db, $sts_debug);
$contact_info_table = sts_contact_info::getInstance($exspeedite_db, $sts_debug);
$csv = new sts_csv(false, false, $sts_debug);
$cat = sts_client_activity::getInstance($exspeedite_db, $sts_debug);
$zip = sts_zip::getInstance($exspeedite_db, $sts_debug);

$setting_table = sts_setting::getInstance($exspeedite_db, $sts_debug);
$sts_import_duplicates = $setting_table->get("option", "ZOOMINFO_IMPORT_DUP") == 'true';

$entry_state = $client_table->behavior_state['entry'];
$assign_state = $client_table->behavior_state['assign'];
$duplicate_state = $client_table->behavior_state['admin'];

$check = $client_table->database->get_one_row("
	SELECT ITEM_CODE
	FROM EXP_ITEM_LIST
	WHERE ITEM = 'Zoominfo.com'");

$lead_source = 0;
if( is_array($check) && isset($check["ITEM_CODE"]))
	$lead_source = $check["ITEM_CODE"];

?>
<div class="container-full theme-showcase" role="main">

<div class="well well-md">
<?php
if( isset($_POST) && isset($_POST["import"])) {
	// Turn off output buffering
	ini_set('output_buffering', 'off');
	// Implicitly flush the buffer(s)
	ini_set('implicit_flush', true);
	ob_implicit_flush(true);

	if (ob_get_level() == 0) ob_start();
	
	echo '<p class="text-center"><img src="images/loading.gif" alt="loading" width="125" height="125" /><br>Importing Leads...</p>
	';
	ob_flush(); flush();

	//! import the CSV file
	$data = $csv->import($_FILES["FILE_NAME"]);
	//echo "<pre>";
	//var_dump($data);
	//echo "</pre>";
	
	if( is_array($data) && count($data) > 0 ) {
		echo '<p class="text-center">'.count($data).' Rows to import: ';
		$count = 0;
		foreach( $data as $row ) {
			
			echo (++$count % 10 == 0 ? $count : '.').($count % 100 == 0 ? '<br>' : '');
			ob_flush(); flush();

			$company_name = empty($row["Company name"]) ? '' : $csv->clean($row["Company name"]);
			$company_address = empty($row["Company Street address"]) ? '' : $csv->clean($row["Company Street address"]);
			$company_city = empty($row["Company City"]) ? '' : $csv->clean($row["Company City"]);
			$company_state = empty($row["Company State"]) ? '' : $csv->lookup_state($csv->clean($row["Company State"]));
			$company_zip_code = empty($row["Company ZIP/Postal code"]) ? '' : $csv->clean($row["Company ZIP/Postal code"]);
			$company_country = empty($row["Company Country"]) ? 'USA' : $csv->clean( $row["Company Country"]);
			if($company_country == 'United States') $company_country = 'USA';
			
			//! SCR# 601 - Bad spelling of state names
			if( ! empty($row["Company State"]) && empty($company_state) &&
				! empty($company_zip_code) ) {
				
				// Use zip to find state
				$check = $zip->zip_lookup($company_zip_code);
				if( is_array($check) && count($check) == 1 ) {
					if( ! empty($check[0]['State']) )
						$company_state = $check[0]['State'];

					// bonus - set the city
					if( empty($company_city) && ! empty($check[0]['City']) )
						$company_state = $check[0]['City'];
				}
			}
			
			// Need a company name
			if( empty($company_name) ) continue;
			
			// Only deal with USA and Canada
			if( ! in_array($company_country, array('USA', 'Canada'))) continue;
			
			$duplicate = $client_table->check_match( $company_name, $company_address,
				$company_city, $company_state, $company_zip_code, $company_country );
	
			//! TO CHECK - Do we skip duplicates?
			if( ! $sts_import_duplicates &&
				is_array($duplicate) && count($duplicate) > 0 ) continue;
			
			$contact_name = array();		// Contact name, combine 3 fields
			if( !empty($row["First name"]))
				$contact_name[] = $csv->clean($row["First name"]);
			if( !empty($row["Middle name"]))
				$contact_name[] = $csv->clean($row["Middle name"]);
			if( !empty($row["Last name"]))
				$contact_name[] = $csv->clean($row["Last name"]);
	
			//! Add client entry
			$client_fields = array( 'CLIENT_NAME' => $company_name,
				'CLIENT_TYPE' => 'lead',
				'CURRENT_STATUS' =>  $entry_state );
			if( ! empty($row["Zoom company ID"]))
				$client_fields["ZOOM_COMPANY_ID"] = $row["Zoom company ID"];
			if( ! empty($row["Company domain name"]))
				$client_fields["CLIENT_URL"] = $row["Company domain name"];
			if( $lead_source > 0 )
				$client_fields["LEAD_SOURCE_CODE"] = $lead_source;
			if( count($contact_name) > 0 )
				$client_fields["CONTACT"] = implode(' ', $contact_name);

			//! SCR# 187 - Assign leads to one sales person
			if( !empty($_POST["ASSIGN_TO"]) && !empty($_POST["SALES_PERSON"]))
				$client_fields["SALES_PERSON"] = $_POST["SALES_PERSON"];
			
			// Add extras to CLIENT_NOTES
			$extras = array();
			if( ! empty($row["Industry label"]))
				$extras[] = "Industry label: ".$row["Industry label"];
			if( ! empty($row["Secondary industry label"]))
				$extras[] = "Secondary industry label: ".$row["Secondary industry label"];
			if( ! empty($row["Revenue Range"]))
				$extras[] = "Revenue Range: ".$row["Revenue Range"];
			if( ! empty($row["Employees"]))
				$extras[] = "Employees: ".number_format($row["Employees"]);
			if( ! empty($row["NAICS1"]))
				$extras[] = "NAICS1: ".$row["NAICS1"];
			if( ! empty($row["NAICS2"]))
				$extras[] = "NAICS2: ".$row["NAICS2"];
			$client_fields["CLIENT_NOTES"] = implode("\n", $extras);
				
			$client_code = $client_table->add( $client_fields );
	
			//! Add contact info
			$contact_info_fields = array( 'CONTACT_CODE' => $client_code,
				'CONTACT_SOURCE' => 'client',
				'CONTACT_TYPE' => 'company');
	
			if( count($contact_name) > 0 )
				$contact_info_fields["CONTACT_NAME"] = implode(' ', $contact_name);
				
			if( ! empty($row["Job title"]))
				$contact_info_fields["JOB_TITLE"] = $row["Job title"];
			$contact_info_fields["ADDRESS"] = $company_address;
			$contact_info_fields["CITY"] = $company_city;
			$contact_info_fields["STATE"] = $company_state;
			$contact_info_fields["ZIP_CODE"] = $company_zip_code;
			$contact_info_fields["COUNTRY"] = $company_country;
			
			//! SCR# 376 - attempt to parse phone number
			if( !empty($row["Company phone number"])) {
				$num = strtolower($row["Company phone number"]);
				if( strpos($num, ' ext. ') !== false) {
					list($contact_info_fields["PHONE_OFFICE"], $contact_info_fields["PHONE_EXT"]) = explode(' ext. ', $num);
				} else if( strpos($num, ' ext ') !== false) {
					list($contact_info_fields["PHONE_OFFICE"], $contact_info_fields["PHONE_EXT"]) = explode(' ext ', $num);
				} else {
					$contact_info_fields["PHONE_OFFICE"] = $row["Company phone number"];
				}
			}
			if( !empty($row["Direct Phone Number"]))
				$contact_info_fields["PHONE_CELL"] = $row["Direct Phone Number"];
			if( !empty($row["Email address"]))
				$contact_info_fields["EMAIL"] = $row["Email address"];
			$contact_info_table->add( $contact_info_fields );
			
			//! Add activity
			$cat->enter_lead( $client_code, 'zoominfo import' );
			
			//! If duplicate, send to admin
			if( is_array($duplicate) && count($duplicate) > 0 ) {
				$client_table->change_state( $client_code, $duplicate_state );
			} else

			//! SCR# 187 - Assign leads to one sales person
			if( !empty($_POST["ASSIGN_TO"])) { //! Move to assign state
				$client_table->change_state( $client_code, $assign_state );
			}
		}
		if( ! $sts_debug )
			reload_page ( "exp_listclient.php" );
	} else {
		echo '<p>Something failed in the import. Make sure the file was ending in .csv, and that it
		was from Zoominfo.com in their format. There should be headings in the first line of the file.</p>
		<p>If you have issues beyond that, you could send a copy of the file that failed to import
		to Exspeedite support, and we will take a look at it.</p>';
	}

	
} else {

	echo '<div class="well well-sm">
	<form role="form" class="form-horizontal" action="exp_import_zoom.php" 
						method="post" enctype="multipart/form-data" 
						name="import_zoom" id="import_zoom">
	'.(isset($_GET['debug']) ? '<input name="debug" type="hidden" value="on">' : '').'
		<h2><img src="images/zi_logo.png" alt="zi_logo" valign="baseline" width="212" height="46" /> Import Leads
			<button class="btn btn-md btn-success" name="import" type="submit" ><span class="glyphicon glyphicon-ok"></span> Import</button> <a class="btn btn-md btn-default" href="exp_listclient.php"><span class="glyphicon glyphicon-remove"></span> Cancel</a></h2>
	
		<div class="form-group">
			<br>
			<div class="col-sm-10">
				<div class="form-group">
					<label for="FILE_NAME" class="col-sm-4 control-label">CSV File</label>
					<div class="col-sm-6">
						<input name="FILE_NAME" id="FILE_NAME" type="file" required>
					
					</div>
				</div>
				<div class="form-group">
					<label for="ASSIGN_TO" class="col-sm-4 control-label">Assign all to</label>
					<div class="col-sm-1">
						<input type="checkbox" class="my-switch" data-text-label="Assign to me" id="ASSIGN_TO" value="ASSIGN_TO" name="ASSIGN_TO">
					</div>
					<div class="col-sm-3" id="LIST_SALESPEOPLE" hidden>
					'.$user_table->sales_menu().'
					</div>
				</div>
				<div class="alert alert-info" role="alert">
					<h2 class="info"><span class="glyphicon glyphicon-warning-sign"></span></h2>
					<p>If a lead is matches existing clients/prospects/leads, it will be flagged as a <strong>Probable Duplicate</strong></p>
					<p>If you assign all leads to a sales person (such as yourself) they will become <strong>prospects</strong>. Once a lead is assigned, it becomes a prospect.</p>
				</div>
			</div>
			<div class="col-sm-2">
				<img src="images/leads.jpg" alt="leads" class="img-responsive">
			</div>
		</div>
		
		
	</form>
	</div>
	<br>
	<br>
	<div class="well well-sm">
		<h2>Export Suppression List <a class="btn btn-md btn-default" href="exp_export_csv.php?pw=GoldUltimate&type=zoominfo"><span class="glyphicon glyphicon-list-alt"></span> <span class="glyphicon glyphicon-arrow-right"></span> <img src="images/zi_logo.png" alt="zi_logo" valign="baseline" height="24" /></a></h2>
		
		<div class="alert alert-info" role="alert">
			<h2 class="info"><span class="glyphicon glyphicon-warning-sign"></span></h2>
		<p>If you want to avoid re-purchasing contacts that you already have in your database, you can upload a Suppression List on the shopping cart page so ZoomInfo can compare the contacts in your database with the output of the list you are generating.</p>
		<p>Click the button to generate a Suppression List for this purpose.</p>
		<p><a href="http://help.zoominfo.com/18575-getting-started-with-build/using-suppression-files" target="_blank"><img src="images/zi_logo.png" alt="zi_logo" valign="baseline" height="24" /> (more info)</a>
	
		</div>
	</div>
</div>
';
?>

	<script language="JavaScript" type="text/javascript"><!--
		
		// Toggle the list of sales people
		$(document).ready( function () {
			
			function update_salespeople() {
				if( $('#ASSIGN_TO').prop('checked') ) {
					$('#LIST_SALESPEOPLE').prop('hidden',false);
				} else {
					$('#LIST_SALESPEOPLE').prop('hidden', 'hidden');					
				}
			}
			
			$('#ASSIGN_TO').change(function () {
				update_salespeople();
			});
			
			update_salespeople();
		});
	//--></script>

<?php
}

require_once( "include/footer_inc.php" );
?>