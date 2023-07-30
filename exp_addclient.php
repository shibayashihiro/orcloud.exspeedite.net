<?php 

// $Id: exp_addclient.php 5124 2023-06-05 19:05:42Z dev $
// Add Client

// Set flag that this is a parent file
define( '_STS_INCLUDE', 1 );

// Setup Session
require_once( "include/sts_session_setup.php" );

require_once( "include/sts_config.php" );
$sts_debug = (isset($_GET['debug'])  || isset($_POST['debug'])) && in_group( EXT_GROUP_DEBUG );
//$sts_debug = isset($_POST) && count($_POST) > 0 ;
require_once( "include/sts_session_class.php" );

$my_session = sts_session::getInstance( $exspeedite_db, $sts_debug );
$my_session->access_check( $sts_table_access[CLIENT_TABLE] );	// Make sure we should be here

$sts_subtitle = "Add Client";
require_once( "include/header_inc.php" );
require_once( "include/navbar_inc.php" );

require_once( "include/sts_form_class.php" );
require_once( "include/sts_client_class.php" );
require_once( "include/sts_setting_class.php" );
require_once( "include/sts_item_list_class.php" );
require_once( "include/sts_user_log_class.php" );

$setting_table = sts_setting::getInstance($exspeedite_db, $sts_debug);
$sts_export_sage50 = $setting_table->get( 'api', 'EXPORT_SAGE50_CSV' ) == 'true';
$sts_extra_stops = $setting_table->get( 'option', 'CLIENT_EXTRA_STOPS' ) == 'true';

//! SCR# 639 - Restrict access to insurance / credit limit
$sts_restrict_ins = $setting_table->get( 'option', 'RESTRICT_INSURANCE' ) == 'true';
$sts_restrict_credit = $setting_table->get( 'option', 'RESTRICT_CREDIT_LIMIT' ) == 'true';
$sts_restrict_credit = $setting_table->get( 'option', 'RESTRICT_CREDIT_LIMIT' ) == 'true';

//! SCR# 906 - send notifications
$sts_send_notifications = $setting_table->get( 'option', 'SEND_NOTIFICATIONS' ) == 'true';
if( ! $sts_send_notifications ) {
	$match = preg_quote('<!-- NOTIFY_1 -->').'(.*)'.preg_quote('<!-- NOTIFY_2 -->');
	$sts_form_addclient_form['layout'] = preg_replace('/'.$match.'/s', '',
		$sts_form_addclient_form['layout'], 1);	
}

//! SCR# 852 - Containers Feature
$sts_containers = $setting_table->get( 'option', 'CONTAINERS' ) == 'true';
if( ! $sts_containers ) {
	$match = preg_quote('<!-- INTERMODAL1 -->').'(.*)'.preg_quote('<!-- INTERMODAL2 -->');
	$sts_form_addclient_form['layout'] = preg_replace('/'.$match.'/s', '',
		$sts_form_addclient_form['layout'], 1);	
}

if( ! $sts_export_sage50 ) {
	$match = preg_quote('<!-- SAGE50_1 -->').'(.*)'.preg_quote('<!-- SAGE50_2 -->');
	$sts_form_addclient_form['layout'] = preg_replace('/'.$match.'/s', '',
		$sts_form_addclient_form['layout'], 1);	
}

if( ! $sts_extra_stops ) {
	$match = preg_quote('<!-- EXTRA_1 -->').'(.*)'.preg_quote('<!-- EXTRA_2 -->');
	$sts_form_addclient_form['layout'] = preg_replace('/'.$match.'/s', '',
		$sts_form_addclient_form['layout'], 1);	
}

//! SCR# 639 - Restrict access to insurance / credit limit
if( $sts_restrict_ins && ! $my_session->in_group(EXT_GROUP_ADMIN) ) {
	$sts_form_edit_client_fields['GENERAL_LIAB_INS']['extras'] = 'disabled readonly';
	$sts_form_edit_client_fields['AUTO_LIAB_INS']['extras'] = 'disabled readonly';
	$sts_form_edit_client_fields['CARGO_LIAB_INS']['extras'] = 'disabled readonly';
}

if( $sts_restrict_credit && ! $my_session->in_group(EXT_GROUP_ADMIN) ) {
	$sts_form_edit_client_fields['CREDIT_LIMIT']['extras'] = 'disabled readonly';
}

if( isset($_GET['order']) ) $_SESSION['order'] = true;

$client_table = sts_client::getInstance($exspeedite_db, $sts_debug);
$item_list_table = sts_item_list::getInstance($exspeedite_db, $sts_debug);

$sts_form_addclient_form = $item_list_table->equipment_checkboxes( $sts_form_addclient_form );

//! SCR# 584 - New coulum Client_ID
if( ! $client_table->client_id() ) {
	$match = preg_quote('<!-- CLIENT_ID_1 -->').'(.*)'.preg_quote('<!-- CLIENT_ID_2 -->');
	$sts_form_addclient_form['layout'] = preg_replace('/'.$match.'/s', '',
		$sts_form_addclient_form['layout'], 1);	
}

$client_form = new sts_form( $sts_form_addclient_form, $sts_form_add_client_fields, $client_table, $sts_debug);

if( isset($_POST) && count($_POST) > 0 ) {		// Process completed form
	$result = $client_form->process_add_form();

	if( $sts_debug ) die; // So we can see the results
	if( $result ) {
		$user_log_table = sts_user_log::getInstance($exspeedite_db, $sts_debug);
		$client_type = 'client';
		if( isset($_POST['CLIENT_NAME']))
			$client = '<a href="exp_editclient.php?CODE='.$result.'">'.$_POST['CLIENT_NAME'].'</a>: ';
		else
			$client = 'client# '.$result.': ';
		
		$entry = $user_log_table->log_event('profiles', 'Add '.$client_type.': '.$client);
		
		$item_list_table->process_equipment_checkboxes('client', $result);
		if( isset($_SESSION['order']) ) {
			unset($_SESSION['order']);
			reload_page ( "exp_addorder.php?CODE=".$result );
		} else
			reload_page ( "exp_editclient.php?CODE=".$result );
	}
}

?>
<div class="container-full theme-showcase" role="main">

<div class="well  well-md">
<?php

if( isset($value) && is_array($value) && $result == false ) {	// If error occured
	echo "<p><strong>Error:</strong> ".$client_table->error()."</p>";
	echo $client_form->render( $value );
} else {
	echo $client_form->render();
}

?>
</div>
</div>

	<script language="JavaScript" type="text/javascript"><!--
		
		// Sets the client info
		$(document).ready( function () {

			function update_reason() {
				if( $('#CDN_TAX_EXEMPT').prop('checked') ) {
					$('#EXEMPT_REASON').prop('hidden',false);
				} else {
					$('#EXEMPT_REASON').prop('hidden', 'hidden');					
				}
			}

			$('#CDN_TAX_EXEMPT').change(function () {
				update_reason();
			});
			
			function update_tax() {
				if( $('#BILL_TO').prop('checked') ) {
					$('#CDN_TAX').prop('hidden',false);
					update_reason();
				} else {
					$('#CDN_TAX').prop('hidden', 'hidden');					
				}
			}

			function update_notes() {
				if( $('#SHIPPER').prop('checked') ) {
					$('#snotes').prop('hidden',false);
				} else {
					$('#snotes').prop('hidden', 'hidden');					
				}
				if( $('#CONSIGNEE').prop('checked') ) {
					$('#cnotes').prop('hidden',false);
				} else {
					$('#cnotes').prop('hidden', 'hidden');					
				}
				if( $('#BILL_TO').prop('checked') ) {
					$('#bnotes').prop('hidden',false);
				} else {
					$('#bnotes').prop('hidden', 'hidden');					
				}
			}

			$('#SHIPPER, #CONSIGNEE, #BILL_TO').change(function () {
				update_tax();
				update_notes();
			});
			
			update_tax();
			update_notes();

			//! SCR# 426 - update to CLIENT_GROUP_CODE
			$('#CLIENT_GROUP').on('typeahead:selected', function(obj, datum, name) {
				$('input#CLIENT_GROUP_CODE').val(datum.CLIENT_CODE).change();
				//console.log('client group ',$('input#CLIENT_GROUP_CODE').val(), datum.CLIENT_CODE);
			});
			 
			$('#CLIENT_GROUP').on('typeahead:idle', function(obj, datum, name) {
				if( $('#CLIENT_GROUP').val() == '' )
					$('input#CLIENT_GROUP_CODE').val('').change();
				//console.log('client group3 ',$('input#CLIENT_GROUP_CODE').val());
			});
			 
		});
	//--></script>


<?php

require_once( "include/footer_inc.php" );
?>
		

