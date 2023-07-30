<?php 
// Set flag that this is a parent file
define( '_STS_INCLUDE', 1 );

// Setup Session
require_once( "include/sts_session_setup.php" );

require_once( "include/sts_config.php" );
$sts_debug = isset($_GET['debug']) && in_group( EXT_GROUP_DEBUG );
require_once( "include/sts_session_class.php" );

$my_session = sts_session::getInstance( $exspeedite_db, $sts_debug );
$my_session->access_check( EXT_GROUP_USER );	// Make sure we should be here

$sts_subtitle = "All Exspeedite Videos";
require_once( "include/header_inc.php" );
require_once( "include/navbar_inc.php" );
require_once( "include/sts_setting_class.php" );
require_once( "include/sts_report_class.php" );

echo '<div class="container theme-showcase" role="main">
	<div class="well">
		<h2><span class="glyphicon glyphicon-film"></span> All Exspeedite Videos</h2>
		';

$setting_table = sts_setting::getInstance( $exspeedite_db, false );
$video_table = sts_video::getInstance( $exspeedite_db, false );
$sts_video_directory = str_replace('\\', '/', $setting_table->get( 'option', 'VIDEO_DIR' ));

if( ! empty($sts_video_directory))
	echo '<div class="text-2col">'.$video_table->all_videos().'</div>';
else
	echo '<br><h4>No videos - Have your administrator set up the option/VIDEO_DIR setting</h4>';

echo '	
	</div>
</div>
';
require_once( "include/footer_inc.php" );
?>
