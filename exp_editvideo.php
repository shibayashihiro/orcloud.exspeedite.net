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

$sts_subtitle = "Edit Video";
require_once( "include/header_inc.php" );
require_once( "include/navbar_inc.php" );

require_once( "include/sts_form_class.php" );
require_once( "include/sts_report_class.php" );

$video_table = sts_video::getInstance($exspeedite_db, $sts_debug);
$video_form = new sts_form($sts_form_editvideo_form, $sts_form_edit_video_fields, $video_table, $sts_debug);

if( isset($_POST) && count($_POST) > 0 ) {		// Process completed form
	$result = $video_form->process_edit_form();

	if( $result ) {
		$video_table->write_cache();
		if( $sts_debug ) die; // So we can see the results
		reload_page ( "exp_listvideo.php" );
	}
}

?>
<div class="container-full theme-showcase" role="main">

<div class="well  well-lg">
<?php

if( isset($value) && is_array($value) && $result == false ) {	// If error occured
	echo "<p><strong>Error:</strong> ".$video_table->error()."</p>";
	echo $video_form->render( $value );
} else if( isset($_GET['CODE']) ) {
	$result = $video_table->fetch_rows($video_table->primary_key." = ".$_GET['CODE']);
	echo $video_form->render( $result[0] );
}

?>
</div>
</div>
<?php

require_once( "include/footer_inc.php" );
?>

