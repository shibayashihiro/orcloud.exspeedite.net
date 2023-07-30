<?php 
// Set flag that this is a parent file
define( '_STS_INCLUDE', 1 );

// Setup Session
require_once( "include/sts_session_setup.php" );

require_once( "include/sts_config.php" );
$sts_debug = (isset($_GET['debug'])  || isset($_POST['debug'])) && in_group( EXT_GROUP_DEBUG );
//$sts_debug = isset($_POST) && count($_POST) > 0 ;
require_once( "include/sts_session_class.php" );

$my_session = sts_session::getInstance( $exspeedite_db, $sts_debug );
$my_session->access_check( $sts_table_access[CLIENT_TABLE], EXT_GROUP_SALES );	// Make sure we should be here

$sts_subtitle = "Add Lead";
require_once( "include/header_inc.php" );
require_once( "include/navbar_inc.php" );

require_once( "include/sts_form_class.php" );
require_once( "include/sts_client_class.php" );
require_once( "include/sts_contact_info_class.php" );
require_once( "include/sts_client_activity_class.php" );
require_once( "include/sts_setting_class.php" );

$setting = sts_setting::getInstance($exspeedite_db, $sts_debug);

$client_table = sts_client::getInstance($exspeedite_db, $sts_debug);
$contact_info_table = sts_contact_info::getInstance($exspeedite_db, $sts_debug);
$client_form = new sts_form( $sts_form_addlead_form, $sts_form_add_lead_fields, $client_table, $sts_debug);

if( isset($_POST) && count($_POST) > 0 ) {		// Process completed form
	//$result = $client_form->process_add_form();

	//! CSRF Token check
	if( isset($_POST) && isset($_POST["CSRF"]) && $_POST["CSRF"] == str_rot13(session_id())) {

		$name = empty($_POST["CLIENT_NAME"]) ? '' : $_POST["CLIENT_NAME"];
		$address = empty($_POST["ADDRESS"]) ? '' : $_POST["ADDRESS"];
		$city = empty($_POST["CITY"]) ? '' : $_POST["CITY"];
		$state = empty($_POST["STATE"]) ? '' : $_POST["STATE"];
		$zip_code = empty($_POST["ZIP_CODE"]) ? '' : $_POST["ZIP_CODE"];
		$country = empty($_POST["COUNTRY"]) ? 'USA' : $_POST["COUNTRY"];
		
		$duplicate = $client_table->check_match( $name, $address, $city, $state, $zip_code, $country );
		
		//! Add client entry
		$entry_state = $client_table->behavior_state['entry'];
		$client_fields = array( 'CLIENT_NAME' => $name,
			'CLIENT_TYPE' => 'lead',
			'CURRENT_STATUS' =>  $entry_state );
		if( ! empty($_POST["LEAD_SOURCE_CODE"]))
			$client_fields["LEAD_SOURCE_CODE"] = $_POST["LEAD_SOURCE_CODE"];
		if( !empty($_POST["ASSIGN_ME"]))
			$client_fields["SALES_PERSON"] = $_SESSION["EXT_USER_CODE"];
		$client_code = $client_table->add( $client_fields );
		
		//! Add contact info
		$contact_info_fields = array( 'CONTACT_CODE' => $client_code,
			'CONTACT_SOURCE' => 'client',
			'CONTACT_TYPE' => 'company');
		if( !empty($_POST["CONTACT_NAME"]))
			$contact_info_fields["CONTACT_NAME"] = $_POST["CONTACT_NAME"];
		if( !empty($_POST["ADDRESS"]))
			$contact_info_fields["ADDRESS"] = $_POST["ADDRESS"];
		if( !empty($_POST["ADDRESS2"]))
			$contact_info_fields["ADDRESS2"] = $_POST["ADDRESS2"];
		if( !empty($_POST["CITY"]))
			$contact_info_fields["CITY"] = $_POST["CITY"];
		if( !empty($_POST["STATE"]))
			$contact_info_fields["STATE"] = $_POST["STATE"];
		if( !empty($_POST["ZIP_CODE"]))
			$contact_info_fields["ZIP_CODE"] = $_POST["ZIP_CODE"];
		if( !empty($_POST["COUNTRY"]))
			$contact_info_fields["COUNTRY"] = $_POST["COUNTRY"];
		if( !empty($_POST["PHONE_OFFICE"]))
			$contact_info_fields["PHONE_OFFICE"] = $_POST["PHONE_OFFICE"];
		if( !empty($_POST["PHONE_EXT"]))
			$contact_info_fields["PHONE_EXT"] = $_POST["PHONE_EXT"];
		if( !empty($_POST["PHONE_CELL"]))
			$contact_info_fields["PHONE_CELL"] = $_POST["PHONE_CELL"];
		if( !empty($_POST["EMAIL"]))
			$contact_info_fields["EMAIL"] = $_POST["EMAIL"];
		$contact_info_table->add( $contact_info_fields );
		
		//! Add activity
		$cat = sts_client_activity::getInstance($exspeedite_db, $sts_debug);
		$cat->enter_lead( $client_code );

		//! Add note to the activity
		if( !empty($_POST["NOTE"])) {
			$cat->update("CLIENT_CODE = ".$client_code."
				AND ACTIVITY = ".$entry_state,
				array('NOTE' => $_POST["NOTE"]));
		}
		
		//! If duplicate, send to admin
		if( is_array($duplicate) && count($duplicate) > 0 ) {
			$duplicate_state = $client_table->behavior_state['admin'];
			$client_table->change_state( $client_code, $duplicate_state );
		} else
		
		if( !empty($_POST["ASSIGN_ME"])) { //! Move to assign state
			$assign_state = $client_table->behavior_state['assign'];
			$client_table->change_state( $client_code, $assign_state );
		}
		$result = true;
		
	} else {
		if( $this->debug ) echo "<p>CSRF Check failed! - possible Cross-site request forgery</p>";
		$result = false;
	}

	if( $sts_debug ) die; // So we can see the results
	
	if( $result )
		reload_page ( "exp_editclient.php?CODE=".$client_code );
}

?>
<div class="container-full theme-showcase" role="main">

<div class="well  well-md">
<?php

if( isset($value) && is_array($value) && $result == false ) {	// If error occured
	echo "<p><strong>Error:</strong> ".$client_table->error()."</p>";
	echo $client_form->render( $value );
} else {
	echo $client_form->render();
}

?>
</div>
</div>

	<script language="JavaScript" type="text/javascript"><!--
		
		// Sets the HOME_CITY and HOME_STATE when you set the ZIP_CODE zip
		$(document).ready( function () {
			$('#ZIP_CODE').on('typeahead:selected', function(obj, datum, name) {
				$('input#CITY').val(datum.CityMixedCase);
				$('select#STATE').val(datum.State);
				$('select#COUNTRY').val(datum.Country);
			});
		});
	//--></script>

<?php

require_once( "include/footer_inc.php" );
?>
		

