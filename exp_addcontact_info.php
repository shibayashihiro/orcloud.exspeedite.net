<?php 
// Set flag that this is a parent file
define( '_STS_INCLUDE', 1 );

// Setup Session
require_once( "include/sts_session_setup.php" );

require_once( "include/sts_config.php" );
$sts_debug = (isset($_GET['debug'])  || isset($_POST['debug'])) && in_group( EXT_GROUP_DEBUG );
require_once( "include/sts_session_class.php" );

$my_session = sts_session::getInstance( $exspeedite_db, $sts_debug );
$my_session->access_check( $sts_table_access[CONTACT_INFO_TABLE] );	// Make sure we should be here

if( isset($_POST) || ( isset($_GET['CODE']) && isset($_GET['SOURCE']) ) ) {
	$sts_subtitle = "Add Contact Info";
	require_once( "include/header_inc.php" );
	require_once( "include/navbar_inc.php" );
	
	require_once( "include/sts_form_class.php" );
	require_once( "include/sts_contact_info_class.php" );
	
	$contact_info_table = new sts_contact_info($exspeedite_db, $sts_debug);
	$contact_info_form = new sts_form( $sts_form_add_contact_info, $sts_form_add_contact_info_fields,
		$contact_info_table, $sts_debug);
	
	if( isset($_POST) && count($_POST) > 0 ) {		// Process completed form
		$result = $contact_info_form->process_add_form();
	
		if( $sts_debug ) die; // So we can see the results
		switch( $_POST["CONTACT_SOURCE"] ) {
			case 'driver':
				reload_page ( "exp_editdriver.php?CODE=".$_POST["CONTACT_CODE"] );

			case 'carrier':
				reload_page ( "exp_editcarrier.php?CODE=".$_POST["CONTACT_CODE"] );

			case 'client':
				reload_page ( "exp_editclient.php?CODE=".$_POST["CONTACT_CODE"] );
		}
			
	}
	
	?>
<div class="container theme-showcase" role="main">

<div class="well  well-lg">	
	<?php
	
	if( isset($value) && is_array($value) && $result == false ) {	// If error occured
		echo "<p><strong>Error:</strong> ".$contact_info_table->error()."</p>";
		echo $contact_info_form->render( $value );
	} else if( isset($_GET['CODE']) && isset($_GET['SOURCE']) ) {
		$value = array('CONTACT_CODE' => $_GET['CODE'],
			'CONTACT_SOURCE' => $_GET['SOURCE'],
			'PARENT_NAME' => $_GET['PARENT_NAME'],
			'LABEL' => $_GET['PARENT_NAME'] );
		if( $_GET['SOURCE'] == 'carrier' )
			$value["CONTACT_TYPE"] = 'carrier';
		echo $contact_info_form->render( $value );
	}
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
		

