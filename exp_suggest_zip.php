<?php 
// Set flag that this is a parent file
define( '_STS_INCLUDE', 1 );

// Set flag that this is an ajax call
define( '_STS_SESSION_AJAX', 1 );

// Setup Session
require_once( "include/sts_session_setup.php" );

require_once( "include/sts_config.php" );
$sts_debug = isset($_GET['debug']);

require_once( "include/sts_zip_class.php" );

if( isset($_GET['query']) && isset($_GET['code']) && $_GET['code'] == 'Balsamic') {

	$zip_table = new sts_zip($exspeedite_db, $sts_debug);
	$result = $zip_table->suggest( $_GET['query'] );

	if( $sts_debug ) {
		echo "<p>result = </p>
		<pre>";
		var_dump($result);
		echo "</pre>";
	} else {
		echo json_encode( $result );
	}
}

else if( isset($_GET['missing_zip']) ) {
	$zip_table = new sts_zip($exspeedite_db, $sts_debug);
	$result = $zip_table->lookup_missing_zip( $_GET['missing_zip'] );
	
	$sts_subtitle = "Add ZIP/Postal Code To Database";
	require_once( "include/header_inc.php" );
	echo '<div class="container" role="main">
		<h1><span class="glyphicon glyphicon-envelope"></span> Add ZIP/Postal Code To Database</h1>
		<p>This used '.$zip_table->get_source().' to lookup a ZIP/Postal Code and add it to the database.</p>
		';

	if( $result ) {
		echo '<h3 class="text-success"><span class="glyphicon glyphicon-ok"></span> Successfully Added '.$_GET['missing_zip'].'</h3>
		<p>Result: '.$zip_table->get_description().'.</p>
		';
			
	} else {
		echo '<h3 class="text-danger"><span class="glyphicon glyphicon-warning-sign"></span> Failed To Add '.$_GET['missing_zip'].'</h3>
		<p>Reason: '.$zip_table->get_description().'.</p>
		';
	}
	echo '<p>Please close this window, refresh your form and try the ZIP/Postal Code again.</p>
		<p><button class="btn btn-lg" onclick="self.close()"><span class="glyphicon glyphicon-remove"></span> Close this window</button></p>
		</div>
	';
	require_once( "include/footer_inc.php" );	
}

?>

