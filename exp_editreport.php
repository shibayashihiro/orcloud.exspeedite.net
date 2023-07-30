<?php 
// Set flag that this is a parent file
define( '_STS_INCLUDE', 1 );

// Setup Session
require_once( "include/sts_session_setup.php" );

require_once( "include/sts_config.php" );
$sts_debug = (isset($_GET['debug'])  || isset($_POST['debug'])) && in_group( EXT_GROUP_DEBUG );
require_once( "include/sts_session_class.php" );

$my_session = sts_session::getInstance( $exspeedite_db, $sts_debug );
$my_session->access_check( EXT_GROUP_ADMIN );	// Make sure we should be here

$sts_subtitle = "Edit Report";
require_once( "include/header_inc.php" );
require_once( "include/navbar_inc.php" );

require_once( "include/sts_form_class.php" );
require_once( "include/sts_report_class.php" );

$report_table = new sts_report($exspeedite_db, $sts_debug);
$report_form = new sts_form($sts_form_editreport_form, $sts_form_edit_report_fields, $report_table, $sts_debug);

if( isset($_POST) && count($_POST) > 0 ) {		// Process completed form
	$result = $report_form->process_edit_form();

	if( $result ) {
		$report_table->user_reports(true);
		if( $sts_debug ) die; // So we can see the results
		reload_page ( "exp_listreport.php" );
	}
}

?>
<div class="container-full theme-showcase" role="main">

<div class="well  well-lg">
<?php

if( isset($value) && is_array($value) && $result == false ) {	// If error occured
	echo "<p><strong>Error:</strong> ".$report_table->error()."</p>";
	echo $report_form->render( $value );
} else if( isset($_GET['CODE']) ) {
	$result = $report_table->fetch_rows($report_table->primary_key." = ".$_GET['CODE']);
	echo $report_form->render( $result[0] );
}

?>
</div>
</div>

	<script language="JavaScript" type="text/javascript"><!--
		
		$(document).ready( function () {

			function update_group() {
				if( $('#RESTRICT_BY').val() == 'group' ) {
					$('#BY_GROUP').prop('hidden', false).change();
				} else {
					$('#BY_GROUP').prop('hidden', 'hidden').change();
				}
			}
			
			$('#RESTRICT_BY').change(function () {
				update_group();
			});
			
			update_group();

		});
	//--></script>

<?php

require_once( "include/footer_inc.php" );
?>

