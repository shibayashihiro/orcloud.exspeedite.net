<?php 
// Set flag that this is a parent file
define( '_STS_INCLUDE', 1 );

// Setup Session
require_once( "include/sts_session_setup.php" );

require_once( "include/sts_config.php" );
$sts_debug = isset($_GET['debug']);

define( 'IMPORT_SYNERGY_CMD', 'import_shipments.cmd' );
define( 'IMPORT_SYNERGY_SCRIPT', 'exp_import_synergy.php' );

function update_file( $filename, $from, $to ) {
	global $sts_debug;
	
	if( $sts_debug ) echo "<p>update_file $filename, $from, $to</p>";
	
	$result = false;
	if( $data = file_get_contents($filename) ) {	
		if( $sts_debug ) echo "<p>update_file got data</p>";
		$data = str_replace($from, $to, $data);
		if( $sts_debug ) echo "<p>update_file after str_replace length = ".strlen($data)."</p>";
		if( $result = file_put_contents( $filename.'.tmp', $data ) ) {
			if( $sts_debug ) echo "<p>update_file file_put_contents worked</p>";
			$result = rename($filename.'.tmp', $filename);
		}
	}
	return $result;
}

if( isset($_GET['code']) &&
	$_GET['code'] == 'Graham') {
	
	$root_dir_win = rtrim(str_replace('/','\\',$sts_crm_dir), '\\');
	$current_path = get_include_path();
	$new_path = str_replace('.',$root_dir_win,$current_path);
	$path_array = explode(';', $new_path);
	$php_dir = str_replace( '\\PEAR', '', $path_array[count($path_array)-1] );
	
	$import_cmd = "cd /d $root_dir_win".PHP_EOL.
		"$php_dir\php.exe -c $php_dir $root_dir_win\\exp_import_synergy.php".PHP_EOL;
		
	// Completely overwrite the CMD script
	$result = file_put_contents( IMPORT_SYNERGY_CMD, $import_cmd );
	
	// Update the PHP script
	if( $result )
		$result = update_file( IMPORT_SYNERGY_SCRIPT, '%PHP_INCLUDE_PATH%', $new_path );
	else {
		if( $sts_debug )
			echo "<p>update_file, IMPORT_SYNERGY_SCRIPT failed</p>";
	}
	
	if( $sts_debug ) {
		echo "<p>result = </p>
		<pre>";
		var_dump($result);
		echo "</pre>";
	} else {
		echo json_encode( $result );
	}
}


?>

