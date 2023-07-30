<?php 
// Set flag that this is a parent file
define( '_STS_INCLUDE', 1 );

// Set flag that this is session readonly
define( '_STS_SESSION_READONLY', 1 );

// Set flag that this is an ajax call
define( '_STS_SESSION_AJAX', 1 );

// Setup Session
require_once( "include/sts_session_setup.php" );

require_once( "include/sts_config.php" );
set_time_limit(0);
ini_set('memory_limit', '1024M');

$sts_debug = isset($_GET['debug']) && in_group( EXT_GROUP_DEBUG );
require_once( "include/sts_session_class.php" );

$my_session = sts_session::getInstance( $exspeedite_db, $sts_debug );
$my_session->access_check( $sts_table_access[LOAD_TABLE], EXT_GROUP_DRIVER );	// Make sure we should be here

require_once( "include/sts_result_class.php" );
require_once( "include/sts_load_class.php" );
require_once( "include/sts_setting_class.php" );

//! SCR# 401 - fix UTF8 encoding of strings before JSON encoding.
function utf8ize($mixed) {
    if (is_array($mixed)) {
        foreach ($mixed as $key => $value) {
            $mixed[$key] = utf8ize($value);
        }
    } else if (is_string ($mixed)) {
        return utf8_encode($mixed);
    }
    return $mixed;
}

$setting_table = sts_setting::getInstance($exspeedite_db, $sts_debug);
$sts_po_fields = $setting_table->get( 'option', 'PO_FIELDS' ) == 'true';
$sts_refnum_fields = $setting_table->get( 'option', 'REFNUM_FIELDS' ) == 'true';
$multi_company = ($setting_table->get("option", "MULTI_COMPANY") == 'true');

//! SCR# 852 - Containers Feature
$sts_containers = $setting_table->get( 'option', 'CONTAINERS' ) == 'true';

//! SCR# 849 - Team Driver Feature
$sts_team_driver = $setting_table->get( 'option', 'TEAM_DRIVER' ) == 'true';

if( $sts_debug ) {
		echo "<p>INPUT = </p><pre>";
		var_dump($_GET);
		echo "</pre>";
}

$load_table = new sts_load_left_join($exspeedite_db, $sts_debug);
$load_table2 = sts_load::getInstance($exspeedite_db, $sts_debug);

$match = isset($_GET["match"]) && $_GET["match"] <> '' ? urldecode($_GET["match"]) : false;
if( isset($_GET["sort"]) && $_GET["sort"] <> '' )
	$sts_result_loads_edit['sort'] = urldecode($_GET["sort"]);

$driver = isset($_GET["rtype"]) && $_GET["rtype"] == 'driver';

if( $driver ) {
	$layout = $sts_result_loads_driver_layout;
	$edit = $sts_result_loads_driver_edit;
} else {
	$layout = $sts_result_loads_lj_layout;
	$edit = $sts_result_loads_edit;
}

if( $sts_debug ) {
	$test = $load_table->database->get_one_row("SELECT @@GLOBAL.time_zone, @@SESSION.time_zone;");
	echo "<pre>TIMEZONE\n";
	var_dump($test);
	echo "</pre>";
}

$rslt = new sts_result( $load_table, $match, $sts_debug );

$rslt->set_alt_table( $load_table2 );

if( ! $sts_po_fields ) {
	unset($layout['PO_NUMBER']);
}
if( ! $sts_refnum_fields ) {
	//! SCR# 658 - changed to REF_NUMBER2 for C-TPAT
	unset($layout['REF_NUMBER2']);
}

//! SCR# 852 - Containers Feature
if( ! $multi_company ) {
	unset($layout['SS_NUMBER2']);
}

if( ! $sts_containers ) {
	unset($sts_result_loads_lj_layout['ST_NUMBER']);
}

$response =  $rslt->render_ajax( $layout, $edit, $_GET );

if( $sts_debug ) {
		echo "<p>response = </p><pre>";
		var_dump($response);
		echo "</pre>";
} else {
	echo json_encode( utf8ize($response) );
}
?>

