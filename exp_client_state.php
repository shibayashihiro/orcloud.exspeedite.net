<?php 

// $Id: exp_client_state.php 4697 2022-03-09 23:02:23Z duncan $
// Change the state of a lead/prospect/client

// Set flag that this is a parent file
define( '_STS_INCLUDE', 1 );

// Setup Session
require_once( "include/sts_session_setup.php" );

require_once( "include/sts_config.php" );
$sts_debug = (isset($_GET['debug'])  || isset($_POST['debug']))  && in_group( EXT_GROUP_DEBUG );
//$sts_debug = true;
require_once( "include/sts_session_class.php" );
$my_session = sts_session::getInstance( $exspeedite_db, $sts_debug );
$my_session->access_check( $sts_table_access[CLIENT_TABLE], EXT_GROUP_SALES );	// Make sure we should be here

require_once( "include/sts_client_class.php" );
require_once( "include/sts_client_activity_class.php" );
//require_once( "include/sts_setting_class.php" );

//$setting = sts_setting::getInstance($exspeedite_db, $sts_debug);

if( isset($_GET['CODE']) && isset($_GET['STATE']) ) {
	if( $sts_debug ) echo "<p>exp_client_state: client = ".$_GET['CODE']." state = ".$_GET['STATE']."</p>";

	$client_table = sts_client::getInstance($exspeedite_db, $sts_debug);
	$result = $client_table->change_state( $_GET['CODE'], $_GET['STATE'],
		isset($_GET['CSTATE']) ? $_GET['CSTATE'] : -1 );
	if( $sts_debug ) echo "<p>result = ".($result ? 'true' : 'false '.$client_table->error())."</p>";

	if( ! $result ) {
		$sts_subtitle = "Change Client State";
		require_once( "include/header_inc.php" );
		require_once( "include/navbar_inc.php" );

		echo '<div class="container theme-showcase" role="main">

		<div class="well  well-md">
		<h4 class="text-danger"><span class="glyphicon glyphicon-warning-sign"></span> Unable to change Client #'.$_GET['CODE'].' to '.$client_table->state_name[$_GET['STATE']].' state</h4>
		
		<p>'.$client_table->state_change_error.'</p>
		
		<p><a class="btn btn-md btn-default" href="exp_editclient.php?CODE='.$_GET['CODE'].'"><span class="glyphicon glyphicon-edit"></span> Edit Client #'.$_GET['CODE'].'</a>
		<a class="btn btn-md btn-default" href="exp_listclient.php"><span class="glyphicon glyphicon-arrow-up"></span> List Clients</a>
		</p>
		
		</div>
		
		</div>' ;
		$save_error = $client_table->error();
		$email = sts_email::getInstance($exspeedite_db, $sts_debug);
		$email->send_alert('exp_client_state: Unable to change Client #'.$_GET['CODE'].' to '.$client_table->state_name[$_GET['STATE']].' state<br>'.$client_table->state_change_error.
		'<br>'.$save_error, $client_table->state_change_level );

			
	} else { //! Post change interaction
		// Currently not much needed here
		// SCR 47 - if state = assign, go back to editclient
		if( ! $sts_debug ) {
			if( $client_table->state_behavior[$_GET['STATE']] == 'dead' ) {
				reload_page ( 'exp_listclient.php' );
//			} else if( $client_table->state_behavior[$_GET['STATE']] == 'email' ) {
//				reload_page ( 'exp_sendformmail.php' );
			} else if( $client_table->last_activity > 0 &&
				$client_table->state_behavior[$_GET['STATE']] <> 'assign' ){
				reload_page ( 'exp_editclient_activity.php?NEW&CODE='.
					$client_table->last_activity );
			} else {
				reload_page ( 'exp_editclient.php?CODE='.$_GET['CODE'] );
			}
		}
	}
}

?>
