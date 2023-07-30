<?php 
// Set flag that this is a parent file
define( '_STS_INCLUDE', 1 );

// Set flag that this is session readonly
define( '_STS_SESSION_READONLY', 1 );

// Set flag that this is an ajax call
define( '_STS_SESSION_AJAX', 1 );

// Setup Session
require_once( "include/sts_session_setup.php" );

require_once( "include/sts_config.php" );
$sts_debug = isset($_GET['debug']) && in_group( EXT_GROUP_DEBUG );
require_once( "include/sts_session_class.php" );

$my_session = sts_session::getInstance( $exspeedite_db, $sts_debug );
$my_session->access_check( $sts_table_access[SHIPMENT_TABLE] );	// Make sure we should be here

require_once( "include/sts_shipment_class.php" );
require_once( "include/sts_email_class.php" );


if( isset($_POST['REFERER']) )
	$referer = $_POST['REFERER'];
else if( isset($_SERVER["HTTP_REFERER"]) ) {
	$path = explode('/', $_SERVER["HTTP_REFERER"]); 
	$referer = end($path);
} else
	$referer = 'unknown';
	
if( isset($_GET['CODE']) ) {
	$shipment_table = sts_shipment::getInstance($exspeedite_db, $sts_debug);
	$email = sts_email::getInstance($exspeedite_db, $sts_debug);

	$result = $shipment_table->ready_dispatch( $_GET['CODE'],
		isset($_GET['CSTATE']) ? $_GET['CSTATE'] : -1 );
	if( $sts_debug ) echo "<p>result = ".($result ? 'true' : 'false '.$shipment_table->error())."</p>";
	
	if( ! $result ) {
		$sts_subtitle = "Shipment Ready For Dispatch";
		define('_STS_SKIP_THIS', 1 );
		require_once( "include/header_inc.php" );
		require_once( "include/navbar_inc.php" );
		
		echo '<div class="container" role="main">
		<div class="jumbotron">
		<h2>Shipment Not Able To Set Ready For Dispatch</h2>
		<p>'.(isset($shipment_table->state_change_error) && $shipment_table->state_change_error <> '' ? $shipment_table->state_change_error : 'The shipment needs a shipper, a consignee, # pallets and weight before it can be set as ready for dispatch.').'</p>
		<a class="btn btn-sm btn-default" href="exp_addshipment.php?CODE='.$_GET['CODE'].'" ><span class="glyphicon glyphicon-arrow-left"></span> Go back</a>
		</div>
		</div>';

		$save_error = $shipment_table->error();
		$email->send_alert('exp_assignshipment: Unable to change Shipment #'.$_GET['CODE'].' to Ready For Dispatch<br>'.$shipment_table->state_change_error.
		'<br>'.$save_error.
		'<br>'.$email->shipment_history($_GET['CODE']), $shipment_table->state_change_level );

		require_once( "include/footer_inc.php" );
	}
	
}

if( $result && ! $sts_debug ) {
	//! Add a delay so I can see any error messages.
	if( $_SESSION['EXT_USERNAME'] == 'duncan' ) sleep(5);
	
	if( isset($referer) && $referer <> 'unknown' )
		reload_page ( $referer );	// Back to referring page
	else
		reload_page ( "exp_listshipment.php" );	// Back to list shipments page
}
?>