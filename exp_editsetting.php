<?php 

// $Id: exp_editsetting.php 4697 2022-03-09 23:02:23Z duncan $
// Edit Setting

// Set flag that this is a parent file
define( '_STS_INCLUDE', 1 );

// Setup Session
require_once( "include/sts_session_setup.php" );

require_once( "include/sts_config.php" );
$sts_debug = (isset($_GET['debug'])  || isset($_POST['debug'])) && in_group( EXT_GROUP_DEBUG );
require_once( "include/sts_session_class.php" );

$my_session = sts_session::getInstance( $exspeedite_db, $sts_debug );
$my_session->access_check( EXT_GROUP_ADMIN );	// Make sure we should be here

$sts_subtitle = "Edit Setting";
require_once( "include/header_inc.php" );
require_once( "include/navbar_inc.php" );

require_once( "include/sts_result_class.php" );
require_once( "include/sts_form_class.php" );
require_once( "include/sts_setting_class.php" );
require_once( "include/sts_user_log_class.php" );

$setting_table = sts_setting::getInstance($exspeedite_db, $sts_debug);

if( isset($_POST) && count($_POST) > 0 ) {		// Process completed form
	$setting_form = new sts_form($sts_form_editsetting_form, $sts_form_edit_setting_fields, $setting_table, $sts_debug);
	$result = $setting_form->process_edit_form();

	if( $result ) {
		// Update cache
		if( isset($_POST['SETTING_CODE']) &&
			isset($_POST['THE_VALUE']))
			
			$check = $setting_table->fetch_rows("SETTING_CODE = ".$_POST['SETTING_CODE']);
			if( is_array($check) && count($check) == 1 ) {
				if( $check[0]['SETTING'] == 'LAST_CHECKED' )
					$_SESSION["LAST_CHECKED"] = false;

				
				//! SCR# 185 - log when we change a setting
				$user_log_table = sts_user_log::getInstance($exspeedite_db, $sts_debug);
				$user_log_table->log_event('admin', 'Edit setting '.$check[0]['CATEGORY'].'/'.$check[0]['SETTING'].' to '.$_POST['THE_VALUE']);
		
				$setting_table->write_cache();
			}
			
		if( $sts_debug ) die; // So we can see the results
		reload_page ( "exp_listsetting.php" );
	}
}

?>
<div class="container-full theme-showcase" role="main">

<div class="well  well-lg">
<?php

if( isset($value) && is_array($value) && $result == false ) {	// If error occured
	echo "<p><strong>Error:</strong> ".$setting_table->error()."</p>";
	echo $setting_form->render( $value );
} else if( isset($_GET['CODE']) ) {
	$result = $setting_table->fetch_rows($setting_table->primary_key." = ".$_GET['CODE']);
	
	if( isset($result[0]["RESTRICTED"]) && $result[0]["RESTRICTED"] ) {
		$sts_form_editsetting_form['layout'] = str_replace('<!-- RESTRICTED -->', '<span class="label label-danger">Restricted</span> ', $sts_form_editsetting_form['layout']);	
		$setting_form = new sts_form($sts_form_editsetting_form, $sts_form_edit_setting_fields, $setting_table, $sts_debug);
	}
	
	// If comment says (true/false), make it an enum/select.
	if( isset($result[0]["SETTING_COMMENT"]) &&
		preg_match('/\(([^\)]+)\)/', $result[0]["SETTING_COMMENT"], $matches) ) {
			 
		//! SCR# 612 - handle dates
		if( $matches[0] == '(mm/dd/yyyy)') {
			$sts_form_edit_setting_fields['THE_VALUE']['format'] = 'date';
		} else {
			$choices = explode(' / ', $matches[1]);
			if(count($choices) > 1 ) {
				$sts_form_edit_setting_fields['THE_VALUE']['format'] = 'enum';
				$setting_table->fake_enum('THE_VALUE', $choices);
			}
		}
	}
	$setting_form = new sts_form($sts_form_editsetting_form, $sts_form_edit_setting_fields, $setting_table, $sts_debug);

	echo $setting_form->render( $result[0] );
}

?>
</div>
</div>
<?php

require_once( "include/footer_inc.php" );
?>

