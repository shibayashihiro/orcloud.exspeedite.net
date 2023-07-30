<?php 

// $Id: exp_scr_state.php 4350 2021-03-02 19:14:52Z duncan $
// Change the state of a lead/prospect/client

// Set flag that this is a parent file
define( '_STS_INCLUDE', 1 );

// Set flag that this is session readonly
define( '_STS_SESSION_READONLY', 1 );

// Set flag that this is an ajax call
define( '_STS_SESSION_AJAX', 1 );

// Setup Session
require_once( "include/sts_session_setup.php" );

require_once( "include/sts_config.php" );
$sts_debug = (isset($_GET['debug'])  || isset($_POST['debug']))  && in_group( EXT_GROUP_DEBUG );
//$sts_debug = true;
require_once( "include/sts_session_class.php" );
$my_session = sts_session::getInstance( $exspeedite_db, $sts_debug );
$my_session->access_check( EXT_GROUP_ADMIN );	// Make sure we should be here

require_once( "include/sts_scr_class.php" );

if( isset($_GET['CODE']) && isset($_GET['STATE']) ) {
	if( $sts_debug ) echo "<p>exp_scr_state: SCR = ".$_GET['CODE']." state = ".$_GET['STATE']."</p>";

	$scr_table = sts_scr::getInstance($exspeedite_db, $sts_debug);
	$result = $scr_table->change_state( $_GET['CODE'], $_GET['STATE'],
		isset($_GET['CSTATE']) ? $_GET['CSTATE'] : -1 );
	if( $sts_debug ) echo "<p>result = ".($result ? 'true' : 'false '.$scr_table->error())."</p>";

	if( ! $result ) {
		$sts_subtitle = "Change SCR State";
		require_once( "include/header_inc.php" );
		require_once( "include/navbar_inc.php" );

		echo '<div class="container theme-showcase" role="main">

		<div class="well  well-md">
		<h4 class="text-danger"><span class="glyphicon glyphicon-warning-sign"></span> Unable to change SCR #'.$_GET['CODE'].' to '.$scr_table->state_name[$_GET['STATE']].' state</h4>
		
		<p>'.$scr_table->state_change_error.'</p>
		
		<p><a class="btn btn-md btn-default" href="exp_editscr.php?CODE='.$_GET['CODE'].'"><span class="glyphicon glyphicon-edit"></span> Edit SCR #'.$_GET['CODE'].'</a>
		<a class="btn btn-md btn-default" href="exp_listscr.php"><span class="glyphicon glyphicon-arrow-up"></span> List SCRs</a>
		</p>
		
		</div>
		
		</div>' ;
		$save_error = $scr_table->error();
		$email = sts_email::getInstance($exspeedite_db, $sts_debug);
		$email->send_alert('exp_scr_state: Unable to change SCR #'.$_GET['CODE'].' to '.$scr_table->state_name[$_GET['STATE']].' state<br>'.$scr_table->state_change_error.
		'<br>'.$save_error, $scr_table->state_change_level );

			
	} else { //! Post change interaction
		// Currently not much needed here
		if( ! $sts_debug )
			reload_page ( 'exp_editscr.php?NEW&CODE='.$_GET['CODE'] );
	}
}

?>
