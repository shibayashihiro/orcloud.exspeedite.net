<?php 
// Set flag that this is a parent file
define( '_STS_INCLUDE', 1 );

// Setup Session
require_once( "include/sts_session_setup.php" );

require_once( "include/sts_config.php" );
$sts_debug = (isset($_GET['debug'])  || isset($_POST['debug'])) && in_group( EXT_GROUP_DEBUG );
require_once( "include/sts_session_class.php" );

$my_session = sts_session::getInstance( $exspeedite_db, $sts_debug );
$my_session->access_check( $sts_table_access[USER_TABLE] );	// Make sure we should be here

$sts_subtitle = "Add User";
require_once( "include/header_inc.php" );
require_once( "include/navbar_inc.php" );

require_once( "include/sts_form_class.php" );
require_once( "include/sts_user_class.php" );
require_once( "include/sts_office_class.php" );
require_once( "include/sts_report_class.php" );
require_once( "include/sts_user_log_class.php" );
require_once( "include/sts_setting_class.php" );

$setting_table = sts_setting::getInstance($exspeedite_db, $sts_debug);
$sts_pipedrive_enabled = $setting_table->get( 'option', 'PIPEDRIVE_ENABLED' ) == 'true';

$user_table = new sts_user($exspeedite_db, $sts_debug);
$office_table = sts_office::getInstance($exspeedite_db, $sts_debug);
$report_table = sts_report::getInstance($exspeedite_db, $sts_debug);

$sts_form_adduser_form2 = $office_table->user_checkboxes( $sts_form_adduser_form2 );
$sts_form_adduser_form2 = $report_table->user_checkboxes( $sts_form_adduser_form2 );

if( ! $sts_pipedrive_enabled ) {
	unset($sts_form_add_user_fields['PIPEDRIVE_API_TOKEN']);
	$match = preg_quote('<!-- PIPEDRIVE1 -->').'(.*)'.preg_quote('<!-- PIPEDRIVE2 -->');
	$sts_form_adduser_form2['layout'] = preg_replace('/'.$match.'/s', '',
		$sts_form_adduser_form2['layout'], 1);	
}

$user_form = new sts_form($sts_form_adduser_form2, $sts_form_add_user_fields, $user_table, $sts_debug);

if( isset($_POST) && count($_POST) > 0 ) {		// Process completed form
	$result = $user_form->process_add_form();
	if( $result ) {
		$office_table->process_user_checkboxes($result);
		$report_table->process_user_checkboxes($result);
		//! SCR# 185 - log when we add a user
		$user_log_table = sts_user_log::getInstance($exspeedite_db, $sts_debug);
		$user_log_table->log_event('admin', 'Add user '.$_POST['USERNAME']);
	}
	
	if( $sts_debug ) die; // So we can see the results
	if( $result ) 
		reload_page ( "exp_listuser.php" );
		
}

?>
<div class="container-full theme-showcase" role="main">

<div class="well  well-md">
<?php

if( isset($value) && is_array($value) && $result == false ) {	// If error occured
	echo "<p><strong>Error:</strong> ".$user_table->error()."</p>";
	echo $user_form->render( $value );
} else
	echo $user_form->render();

?>
</div>
</div>

	<script language="JavaScript" type="text/javascript"><!--
		
		// Sets the client info
		$(document).ready( function () {
			
			var username_valid = false;
			var office_valid = false;
			var is_driver = false;
			var is_mechanic = false;
			
			function validate() {
				if( username_valid && ( office_valid || is_driver || is_mechanic )) {
					$('button[type="submit"]').prop('disabled', false);
				} else {
					$('button[type="submit"]').prop('disabled', true);
				}
			}
			
			function validate_office() {
				if( '<?php echo $office_table->multi_company() ? 'true' : 'false'; ?>'
					== 'true' && $('#OFFICES').length &&
					! $('input[name=USER_GROUPS_driver]').prop('checked') &&
					! $('input[name=USER_GROUPS_mechanic]').prop('checked') ) {
					var count = 0;
					$('input.office').each(function() {
						if( $(this).prop('checked') ) count++;
					});
					//console.log('validate_office: ', count);
					if( count > 0 ) {
						office_valid = true;
						$('#OFFICE_HELP').prop('hidden', 'hidden');
					} else {
						office_valid = false;
						$('#OFFICE_HELP').prop('hidden',false);
					}
				} else {
					office_valid = true;
					$('#OFFICE_HELP').prop('hidden', 'hidden');
				}
				validate();
			}
			
			$('input.office').change(function () {
				validate_office();
			});

			validate_office();
			
			function update_margin() {
				if( $('input[name=USER_GROUPS_sales]').prop('checked') ) {
					$('#MARGIN').prop('hidden',false);
				} else {
					$('#MARGIN').prop('hidden', 'hidden');					
				}
			}

			$('input[name=USER_GROUPS_sales]').change(function () {
				update_margin();
			});
			
			update_margin();

			function update_driver() {
				if( $('input[name=USER_GROUPS_driver]').prop('checked') ) {
					$('#LINKED_DRIVER').prop('hidden',false);
					$('input[name=USER_GROUPS_admin]').prop('checked', false).change();
					$('input[name=USER_GROUPS_profiles]').prop('checked', false).change();
					$('input[name=USER_GROUPS_sales]').prop('checked', false).change();
					$('input[name=USER_GROUPS_shipments]').prop('checked', false).change();
					$('input[name=USER_GROUPS_dispatch]').prop('checked', false).change();
					$('input[name=USER_GROUPS_billing]').prop('checked', false).change();
					$('input[name=USER_GROUPS_finance]').prop('checked', false).change();
					$('input[name=USER_GROUPS_Sage50]').prop('checked', false).change();
					$('input[name=USER_GROUPS_HR]').prop('checked', false).change();
					$('input[name=USER_GROUPS_random]').prop('checked', false).change();
					$('input[name=USER_GROUPS_fleet]').prop('checked', false).change();
					$('input[name=USER_GROUPS_manager]').prop('checked', false).change();
					$('input[name=USER_GROUPS_mechanic]').prop('checked', false).change();
					$('input[name=USER_GROUPS_inspection]').prop('checked', false).change();
					$('input[name=USER_GROUPS_debug]').prop('checked', false).change();
					if( $('#OFFICES').length ) {
						$('#OFFICES').prop('hidden', 'hidden');
						$('input.office').prop('checked', false).change();
						is_driver = true;
						is_mechanic = false;
						validate();
					}
				} else {
					$('#LINKED_DRIVER').prop('hidden', 'hidden');
					$('#DRIVER').val("null");					
					if( $('#OFFICES').length )
						$('#OFFICES').prop('hidden',false);
					is_driver = false;
					validate();
				}
			}

			function update_mechanic() {
				if( $('input[name=USER_GROUPS_mechanic]').prop('checked') ) {
					$('#LINKED_DRIVER').prop('hidden', 'hidden');
					$('input[name=USER_GROUPS_admin]').prop('checked', false).change();
					$('input[name=USER_GROUPS_profiles]').prop('checked', false).change();
					$('input[name=USER_GROUPS_sales]').prop('checked', false).change();
					$('input[name=USER_GROUPS_shipments]').prop('checked', false).change();
					$('input[name=USER_GROUPS_dispatch]').prop('checked', false).change();
					$('input[name=USER_GROUPS_billing]').prop('checked', false).change();
					$('input[name=USER_GROUPS_finance]').prop('checked', false).change();
					$('input[name=USER_GROUPS_Sage50]').prop('checked', false).change();
					$('input[name=USER_GROUPS_HR]').prop('checked', false).change();
					$('input[name=USER_GROUPS_random]').prop('checked', false).change();
					$('input[name=USER_GROUPS_fleet]').prop('checked', false).change();
					$('input[name=USER_GROUPS_manager]').prop('checked', false).change();
					$('input[name=USER_GROUPS_driver]').prop('checked', false).change();
					$('input[name=USER_GROUPS_inspection]').prop('checked', false).change();
					$('input[name=USER_GROUPS_debug]').prop('checked', false).change();
					if( $('#OFFICES').length ) {
						$('#OFFICES').prop('hidden', 'hidden');
						$('input.office').prop('checked', false).change();
						is_mechanic = true;
						is_driver = false;
						validate();
					}
				} else {
					if( $('#OFFICES').length )
						$('#OFFICES').prop('hidden',false);
					is_mechanic = false;
					validate();
				}
			}

			$('input[name=USER_GROUPS_admin],input[name=USER_GROUPS_profiles],input[name=USER_GROUPS_sales],input[name=USER_GROUPS_shipments],input[name=USER_GROUPS_dispatch],input[name=USER_GROUPS_billing],input[name=USER_GROUPS_finance],input[name=USER_GROUPS_Sage50],input[name=USER_GROUPS_HR],input[name=USER_GROUPS_random],input[name=USER_GROUPS_fleet],input[name=USER_GROUPS_manager],input[name=USER_GROUPS_inspection],input[name=USER_GROUPS_debug]').change(function ( event ) {
				if( $(this).prop('checked') ) {
					$('input[name=USER_GROUPS_driver]').prop('checked', false).change();
					$('input[name=USER_GROUPS_mechanic]').prop('checked', false).change();
				}
			});
			
			$('input[name=USER_GROUPS_driver]').change(function () {
				update_driver();
			});
			
			update_driver();
			
			$('input[name=USER_GROUPS_mechanic]').change(function () {
				update_mechanic();
			});
			
			update_mechanic();
			
			function unique_username() {
				if( $.trim($('#USERNAME').val()) == '' ) {
					$('#FG_USERNAME').addClass("has-error");
					$('<span id="UNIQUE_HELP" class="help-block"><span class="glyphicon glyphicon-warning-sign"></span> Need a unique username.</span>').insertAfter( $('#USERNAME') );
					username_valid = false;
					validate();
				} else {
					$.ajax({
						url: 'exp_isunique.php',
						data: {
							PW: 'Pencil',
							USERNAME: $.trim($('#USERNAME').val())
						},
						success: function(data) {
							//console.log('data = ',$.trim(data));
							if( $.trim(data) == 'true' ) {
								$('#FG_USERNAME').addClass("has-error");
								$('<span id="UNIQUE_HELP" class="help-block"><span class="glyphicon glyphicon-warning-sign"></span> That username is taken.</span>').insertAfter( $('#USERNAME') );
								username_valid = false;
							} else {
								$('#FG_USERNAME').removeClass("has-error");
								$('#UNIQUE_HELP').remove();
								username_valid = true;
							}
							validate();
						}
					});
				}
						
			}

			$('input#USERNAME').on('input', function() {
				unique_username();
			});
			
			unique_username();
			
		});
	//--></script>

<?php

require_once( "include/footer_inc.php" );
?>

