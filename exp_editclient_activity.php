<?php 

// $Id: exp_editclient_activity.php 4697 2022-03-09 23:02:23Z duncan $
// Edit Client activity, you get here after a client state change usually

// Set flag that this is a parent file
define( '_STS_INCLUDE', 1 );

// Include the timezone javascript
define('_STS_TIMEZONE', 1 );

// Setup Session
require_once( "include/sts_session_setup.php" );

require_once( "include/sts_config.php" );
$sts_debug = (isset($_GET['debug'])  || isset($_POST['debug'])) && in_group( EXT_GROUP_DEBUG );
require_once( "include/sts_session_class.php" );

$my_session = sts_session::getInstance( $exspeedite_db, $sts_debug );
$my_session->access_check( $sts_table_access[CLIENT_ACTIVITY_TABLE] );	// Make sure we should be here

$sts_subtitle = "Edit Client Activity";
require_once( "include/header_inc.php" );
require_once( "include/navbar_inc.php" );

require_once( "include/sts_form_class.php" );
require_once( "include/sts_client_class.php" );
require_once( "include/sts_client_activity_class.php" );
require_once( "include/sts_email_class.php" );
require_once( "include/sts_setting_class.php" );

$setting_table = sts_setting::getInstance($exspeedite_db, $sts_debug);
$sts_restrict_salesperson = $setting_table->get( 'option', 'EDIT_SALESPERSON_RESTRICTED' ) == 'true';

$client_table = sts_client::getInstance($exspeedite_db, $sts_debug);

$client_activity_table = sts_client_activity::getInstance($exspeedite_db, $sts_debug);

if( isset($_GET) && isset($_GET["CANCEL"]) ) {
	$client_activity_table->delete( $_GET['CANCEL'], 'permdel' );
	reload_page ( "exp_editclient.php?CODE=".$_GET["CODE"] );
	die;
}
	
//! SCR# 420 - add disabled to truly block it
if( $sts_restrict_salesperson && 
	! $my_session->in_group(EXT_GROUP_ADMIN, EXT_GROUP_MANAGER) ) {
	$sts_form_edit_client_activity_fields['SALES_PERSON']['extras'] = 'disabled readonly';
}

$activity_code = isset($_GET['CODE']) ? $_GET['CODE'] : $_POST["ACTIVITY_CODE"];
$check = $client_activity_table->fetch_rows("ACTIVITY_CODE = ".$activity_code,
	"ACTIVITY, CLIENT_CODE, (SELECT CLIENT_NOTES FROM EXP_CLIENT
		WHERE EXP_CLIENT.CLIENT_CODE = EXP_CLIENT_ACTIVITY.CLIENT_CODE
		LIMIT 1) AS CLIENT_NOTES, (SELECT CLIENT_TYPE FROM EXP_CLIENT
		WHERE EXP_CLIENT.CLIENT_CODE = EXP_CLIENT_ACTIVITY.CLIENT_CODE
		LIMIT 1) AS CLIENT_TYPE");
if( is_array($check) && count($check) == 1 && isset($check[0]["ACTIVITY"])) {
	$activity = $client_table->state_behavior[$check[0]["ACTIVITY"]];
	if( $activity <> 'call' ) {
		$match = preg_quote('<!-- CALL01 -->').'(.*)'.preg_quote('<!-- CALL02 -->');
		$sts_form_edit_client_activity_form['layout'] = preg_replace('/'.$match.'/s', '',
			$sts_form_edit_client_activity_form['layout'], 1);	
		$match = preg_quote('<!-- CALL03 -->').'(.*)'.preg_quote('<!-- CALL04 -->');
		$sts_form_edit_client_activity_form['layout'] = preg_replace('/'.$match.'/s', '',
			$sts_form_edit_client_activity_form['layout'], 1);	
	}
	
	if( in_array($activity, array('entry','assign','dead','admin'))) {
		$match = preg_quote('<!-- CONTACT01 -->').'(.*)'.preg_quote('<!-- CONTACT02 -->');
		$sts_form_edit_client_activity_form['layout'] = preg_replace('/'.$match.'/s', '',
			$sts_form_edit_client_activity_form['layout'], 1);	
		$match = preg_quote('<!-- CONTACT03 -->').'(.*)'.preg_quote('<!-- CONTACT04 -->');
		$sts_form_edit_client_activity_form['layout'] = preg_replace('/'.$match.'/s', '',
			$sts_form_edit_client_activity_form['layout'], 1);	
	}
	
	$client_code = $check[0]["CLIENT_CODE"];

	// Replace Lead/Prospect/Client with specific type
	$client_type = ucfirst($check[0]["CLIENT_TYPE"]);
	$sts_form_edit_client_activity_form['title'] = str_replace('Lead/Prospect/Client', $client_type, $sts_form_edit_client_activity_form['title']);

	if( $activity == 'sold' ) {
		$sts_form_edit_client_activity_form['buttons'] = array(
			array( 'label' => 'Add Shipment',
  			'link' => 'exp_addshipment.php?CLIENT='.$client_code,
  			'button' => 'primary', 'tip' => 'Enter shipment information in a new window',
  			'icon' => '<span class="glyphicon glyphicon-plus"></span>',
  			'blank' => true,
  			'restrict' => EXT_GROUP_SALES )	
		);
	}
	
	//! Update next step menu
	$status = $check[0]["ACTIVITY"];
	$following = $client_table->following_states( $status );
	
	// Pass on NEW parameter, indicating newly changed to this state
	if( isset($_GET["NEW"])) {
		$sts_form_edit_client_activity_form['layout'] = 
			'<input name="NEW" type="hidden" value="true">
			'.$sts_form_edit_client_activity_form['layout'];
	
		// Use previous CONTACT_CODE
		$cc = $client_table->database->get_one_row("
			SELECT ACTIVITY_CODE, CONTACT_CODE
			FROM EXP_CLIENT_ACTIVITY
			WHERE CLIENT_CODE = $client_code
			AND ACTIVITY_CODE < $activity_code
			ORDER BY ACTIVITY_CODE DESC
			LIMIT 1");
		
		if( is_array($cc) && ! empty($cc["CONTACT_CODE"]))

		$dummy = $client_activity_table->update($activity_code,
			array('CONTACT_CODE' => $cc["CONTACT_CODE"]));
	}

	$next_steps = array();
	foreach( $following as $row ) {
		$next_steps[] = $row['CODE'];
	}
	$sts_form_edit_client_activity_fields['NEXT_STEP']['condition'] =
		"SOURCE_TYPE = 'client' AND STATUS_CODES_CODE IN (".implode(', ', $next_steps).")";

	$sts_form_edit_client_activity_fields['CONTACT_CODE']['condition'] =
		"CONTACT_SOURCE = 'client' AND CONTACT_CODE = ".$client_code;

	if( ! isset($sts_form_edit_client_activity_form['buttons']) || ! is_array($sts_form_edit_client_activity_form['buttons']))
		$sts_form_edit_client_activity_form['buttons'] = array();
	
	$check2 = $client_table->fetch_rows("CLIENT_CODE = ".$client_code,
		"CURRENT_STATUS");
	$current_status = $check2[0]["CURRENT_STATUS"];
	$following = $client_table->following_states( $current_status );
	if( is_array($following) && count($following) > 0 ) {
		foreach( $following as $row ) {
			if( $client_table->state_change_ok( $client_code, $current_status, $row['CODE'] ) ) {
	  			$sts_form_edit_client_activity_form['buttons'][] = 
	  			array( 'label' => $client_table->state_name[$row['CODE']],
	  			'link' => 'exp_client_state.php?CODE='.$client_code.
	  			'&STATE='.$row['CODE'].'&CSTATE='.$current_status,
	  			'button' => 'primary', 'tip' => $row['DESCRIPTION'],
	  			'icon' => '<span class="glyphicon glyphicon-arrow-right"></span>',
	  			'restrict' => EXT_GROUP_SALES );
			} else {
	  			$sts_form_edit_client_activity_form['buttons'][] = 
	  			array( 'label' => $client_table->state_name[$row['CODE']],
	  			'link' => 'exp_client_state.php?CODE='.$client_code.
	  			'&STATE='.$row['CODE'].'&CSTATE='.$current_status,
	  			'button' => 'primary', 'tip' => $client_table->state_change_error,
	  			'disabled' => true,
	  			'icon' => '<span class="glyphicon glyphicon-remove"></span>',
	  			'restrict' => EXT_GROUP_SALES );
			}
		}
	}

	if( $activity == 'dead' && isset($_GET["NEW"]) ) {
		$check2 = $client_table->fetch_rows("CLIENT_CODE = ".$client_code,"CLIENT_TYPE");
		if( is_array($check2) && count($check2) == 1 && isset($check2[0]["CLIENT_TYPE"]) &&
			in_array($check2[0]["CLIENT_TYPE"], array('lead', 'prospect'))) {
			$sts_form_edit_client_activity_form['buttons'] = array( 
				array( 'label' => 'Delete Lead',
					'link' => 'exp_deleteclient.php?TYPE=permdel&CODE='.$client_code,
					'button' => 'danger',
					'icon' => '<span class="glyphicon glyphicon-trash"></span>',
					'restrict' => EXT_GROUP_ADMIN )
			);
		}
	}
	
	$sts_form_edit_client_activity_fields["CLIENT_NOTES"]['value'] =
		$check[0]["CLIENT_NOTES"];
	
}

if( $activity == 'email' ) {
	$sts_form_send_formmail_fields['CONTACT_CODE']['condition'] =
		"CONTACT_SOURCE = 'client' AND CONTACT_CODE = ".$client_code." AND EMAIL IS NOT NULL";
	if( isset($_GET["NEW"])) {
		$sts_form_send_formmail_form['layout'] = 
			'<input name="NEW" type="hidden" value="true">
			'.$sts_form_send_formmail_form['layout'];
	}
	
	$client_activity_form = new sts_form( $sts_form_send_formmail_form,
		$sts_form_send_formmail_fields, $client_activity_table, $sts_debug);
} else {
	$client_activity_form = new sts_form( $sts_form_edit_client_activity_form,
		$sts_form_edit_client_activity_fields, $client_activity_table, $sts_debug);
}

if( isset($_POST) && count($_POST) > 0 ) {		// Process completed form
	$result = $client_activity_form->process_edit_form();
	
	// SCR# 46 Update sales person
	if( $result && isset($_POST["CLIENT_CODE"]) && isset($_POST["SALES_PERSON"]) &&
		! (isset($_GET) && isset($_GET["EMAIL"])) ) {// form email - don't update
		$client_table->update($_POST["CLIENT_CODE"],
			array("SALES_PERSON" => $_POST["SALES_PERSON"]));
	}
	
	//! First time through, with due date, so send calendar reminder
	if( $result && isset($_POST["NEW"]) && !empty($_POST["DUE_BY"]) ) {		
		$email_type = 'activity';
		$email_code = $_POST["ACTIVITY_CODE"];
		require_once( "exp_spawn_send_email.php" );
	}
	
	if( $result && isset($_POST["NEW"]) && isset($_GET) && isset($_GET["EMAIL"]) ) {
		// Send a form email
		$email = sts_email::getInstance($exspeedite_db, $sts_debug);
		$email->log_email_error( "exp_editclient_activity.php: formmail ".$_POST["ACTIVITY_CODE"] );
		$email_type = 'formmail';
		$email_code = $_POST["ACTIVITY_CODE"];
		require_once( "exp_spawn_send_email.php" );
	}

	if( $result ) {
		if( $sts_debug ) die; // So we can see the results
		if( isset($_POST["saveadd"]) )
			reload_page ( "exp_listclient.php" );
		else
			reload_page ( "exp_editclient.php?CODE=".$_POST["CLIENT_CODE"] );
	}
}

?>
<div class="container-full theme-showcase" role="main">

<div class="well  well-lg">
<?php

if( isset($value) && is_array($value) && $result == false ) {	// If error occured
	echo "<p><strong>Error:</strong> ".$client_activity_table->error()."</p>";
	echo $client_activity_form->render( $value );
} else if( isset($_GET['CODE']) ) {
	$result = $client_activity_table->fetch_rows("ACTIVITY_CODE = ".$_GET['CODE']);
	if( isset($result[0]) && is_null($result[0]["TZ"]) )
		$result[0]["TZ"] = 'TBD';
	echo $client_activity_form->render( $result[0] );
}

if( $activity != 'email' ) {
	$cat = sts_client_activity::getInstance($exspeedite_db, $sts_debug);
	$rslt3 = new sts_result( $cat, "CLIENT_CODE = ".$result[0]["CLIENT_CODE"], $sts_debug );
	echo $rslt3->render( $sts_result_client_activity_layout, $sts_result_client_activity_edit, '?CLIENT_CODE='.$result[0]["CLIENT_CODE"] );
}

?>
</div>
</div>

	<script language="JavaScript" type="text/javascript"><!--
		
		// Sets the client info
		$(document).ready( function () {

			function update_contact_info() {
				$('div#CONTACT_INFO').load('exp_get_contact_info.php?PW=Bisodol&CODE='+$('select#CONTACT_CODE').val()+'&ACT='+$('input#ACTIVITY_CODE').val());
			}
			
			$('select#CONTACT_CODE').change(function () {
				update_contact_info();
			});
			
			//! Set the timzone
			var tz = jstz.determine();
			$('input#TZ').val(tz.name());
			
			update_contact_info();
			
			$('a').off('click.editclient_activity');
			$('form h2 a.btn-default').on('click.editclient_activity', function( event ) {
				if( window.editclient_activity_HAS_CHANGED ) {
					var goto_link;
					if(typeof event.target.href === 'undefined'){
						goto_link = 'index.php';
					} else {
						goto_link = event.target.href;
					}
					event.preventDefault();  //prevent form from submitting
					confirmation('<h2><span class="glyphicon glyphicon-user"></span> Lead/Prospect/Client Activity</h2><p>You have unsaved changes that will be lost. Continue?</p>', goto_link);
				}
			});
			
			$( "form h2 a.btn-primary" ).on('click', function(event) {
				event.preventDefault();
				bootbox.hideAll();
				
				var href = $(this).attr("href");
				var data = $("#editclient_activity :input").serializeArray();
				$.post("exp_editclient_activity.php", data, function( data ) {
					window.location.href = href;
				});
			});
			
		});
	//--></script>

<?php

require_once( "include/footer_inc.php" );
?>

