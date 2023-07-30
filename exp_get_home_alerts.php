<?php

// $Id: exp_get_home_alerts.php 5106 2023-05-22 17:34:36Z duncan $
//! Alerts for home page

// Set flag that this is a parent file
define( '_STS_INCLUDE', 1 );

// Set flag that this is an ajax call
define( '_STS_SESSION_AJAX', 1 );

// Setup Session
require_once( "include/sts_session_setup.php" );

require_once( "include/sts_config.php" );
$sts_debug = isset($_GET['debug']);

if( isset($_GET['pw']) && $_GET['pw'] == 'Worstershire' ) {
	
	require_once( "include/sts_session_class.php" );
	require_once( "include/sts_setting_class.php" );
	require_once( "include/sts_alert_class.php" );

//	Uncomment two lines to test separately
//	$sts_subtitle = "Welcome to Exspeedite";
//	require_once( "include/header_inc.php" );

	$setting_table = sts_setting::getInstance( $exspeedite_db, false );
	$alert_table = sts_alert::getInstance($exspeedite_db, $sts_debug);
	$email_queue = sts_email_queue::getInstance($exspeedite_db, $sts_debug);
	
	$sts_alert_expired = $setting_table->get( 'option', 'ALERT_EXPIRED' );
	
	if( $sts_alert_expired != 'false' ) {

		$expired = $alert_table->get_alerts();
	
		if( ! empty($expired)) {
			if( $sts_alert_expired == 'true' )
				echo '<a class="btn btn-danger btn-lg animated infinite pulse wobble tip" title="You need to take action on these! Click to see more." role="button" data-toggle="collapse" href="#collapseAlert" aria-expanded="false" aria-controls="collapseAlert"><span class="glyphicon glyphicon-warning-sign"></span> Alerts</a><span class="text-muted"><small>'.$alert_table->last_cached().'</small></span>
			<div class="collapse alert alert-info alert-tighter" role="alert" id="collapseAlert">
			'.$email_queue->recent_errors().'
			'.$expired.'
			</div>';
			else
				echo '<span class="text-muted"><small>'.$alert_table->last_cached().'</small></span><br>
				<div class=" alert alert-info alert-tighter" role="alert">
			'.$email_queue->recent_errors().'
			'.$expired.'
			</div>';
			
			echo "
	<script language=\"JavaScript\" type=\"text/javascript\"><!--
		$(document).ready( function () {
			$('.inform').popover({ 
				placement: 'top',
				html: 'true',
				container: 'body',
				trigger: 'hover',
				delay: { show: 50, hide: 1000 },
				title: '<strong>Information</strong> <button type=\"button\" class=\"close\" data-hide=\"confirm\" data-delay=\"0\" aria-hidden=\"true\">&times;</button>' 
			});
						
		});

	//--></script>";
		

		}
	}
}


?>