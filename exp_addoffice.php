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
$my_session->access_check( EXT_GROUP_ADMIN );	// Make sure we should be here

$sts_subtitle = "Add Office";
require_once( "include/header_inc.php" );
require_once( "include/navbar_inc.php" );

require_once( "include/sts_form_class.php" );
require_once( "include/sts_office_class.php" );

$office_table = sts_office::getInstance($exspeedite_db, $sts_debug);
$office_form = new sts_form( $sts_form_addoffice_form, $sts_form_add_office_fields, $office_table, $sts_debug);

if( isset($_POST) && count($_POST) > 0 ) {		// Process completed form
	$result = $office_form->process_add_form();

	if( $sts_debug ) die; // So we can see the results
	if( $result ) {
		$office_table->user_offices( true );
		if( isset($_POST["saveadd"]) )
			reload_page ( "exp_addoffice.php" );
		else
			reload_page ( "exp_listoffice.php" );
	}	
}

?>
<div class="container-full theme-showcase" role="main">

<div class="well  well-md">
<?php

if( isset($value) && is_array($value) && $result == false ) {	// If error occured
	echo "<p><strong>Error:</strong> ".$bc_table->error()."</p>";
	echo $office_form->render( $value );
} else {
	echo $office_form->render();
}

?>
</div>
</div>

	<script language="JavaScript" type="text/javascript"><!--
		
		// Sets the CITY and STATE when you set the ZIP zip
		$(document).ready( function () {
			$('#ZIP').on('typeahead:selected', function(obj, datum, name) {
				$('input#CITY').val(datum.CityMixedCase);
				$('select#STATE').val(datum.State);
				$('select#COUNTRY').val(datum.Country);
			});

		});
	//--></script>

<?php

require_once( "include/footer_inc.php" );
?>
		

