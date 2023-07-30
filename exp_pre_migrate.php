<?php
// Set flag that this is a parent file
define( '_STS_INCLUDE', 1 );

// Setup Session
require_once( "include/sts_session_setup.php" );

require_once( "include/sts_config.php" );
set_time_limit(0);
ini_set('memory_limit', '1024M');

$sts_debug = (isset($_GET['debug'])  || isset($_POST['debug']))  && in_group( EXT_GROUP_DEBUG );
require_once( "include/sts_session_class.php" );

$my_session = sts_session::getInstance( $exspeedite_db, $sts_debug );
$my_session->access_check( EXT_GROUP_ADMIN );	// Make sure we should be here


$sts_subtitle = "Pre-Migrate DB";
require_once( "include/header_inc.php" );

if( isset($_GET) && isset($_GET["PW"]) && $_GET["PW"]= "Default" ) {
	
	if (ob_get_level() == 0) ob_start();
	
	echo '<div class="container-full" role="main">
		<h2>Examine Schema '.$sts_database.'</h2>
		<p>Check enums with invalid value, prior to import to MySQL 8</p>
		';
	
	$result1 = $exspeedite_db->get_multiple_rows("
		SELECT c.TABLE_NAME, c.COLUMN_NAME, c.DATA_TYPE, c.COLUMN_DEFAULT, c.IS_NULLABLE, C.COLUMN_TYPE
			FROM INFORMATION_SCHEMA.COLUMNS c, INFORMATION_SCHEMA.TABLES t
			WHERE c.TABLE_SCHEMA = '".$sts_database."'
			AND c.TABLE_NAME = t.TABLE_NAME
			AND c.TABLE_SCHEMA = t.TABLE_SCHEMA
			AND t.TABLE_TYPE = 'BASE TABLE'
		    AND c.DATA_TYPE = 'enum'

		    AND c.COLUMN_DEFAULT IS NOT NULL
			ORDER BY c.TABLE_NAME, c.ORDINAL_POSITION");
			//		    AND c.IS_NULLABLE = 'NO'
	
	//echo "<pre>";
	//var_dump($result1);
	//echo "</pre>";
	
	if( is_array($result1) && count($result1) > 0 ) {
		$errs = 0;
		foreach( $result1 as $row ) {
			$result2 = $exspeedite_db->get_multiple_rows("
				SELECT COUNT(*) NUM
				FROM ".$row["TABLE_NAME"]."
				WHERE COALESCE(".$row["COLUMN_NAME"].", '') = ''");
			
			if( is_array($result2) && count($result2) > 0 && isset($result2[0]) &&
				isset($result2[0]["NUM"]) && $result2[0]["NUM"] > 0) {
				$errs++;
				echo '<div class="row">
					<div class="col-md-2">Table: <strong>'.$row["TABLE_NAME"].'</strong></div>
					<div class="col-md-2">Column: <strong>'.$row["COLUMN_NAME"].'</strong></div>
					<div class="col-md-4">Type: '.$row["COLUMN_TYPE"].'</div>
					<div class="col-md-2">Default: '.$row["COLUMN_DEFAULT"].'</div>
					<div class="col-md-2">'.$result2[0]["NUM"].' Issues</div>
				</div>
				';
				
				$fix = "UPDATE ".$row["TABLE_NAME"]."
SET ".$row["COLUMN_NAME"]." = '".$row["COLUMN_DEFAULT"]."'
WHERE COALESCE(".$row["COLUMN_NAME"].", '') = ''";
				
				echo "<pre>".$fix."</pre>";

				if( isset($_GET["FIX"]) ) {
					$exspeedite_db->get_one_row( $fix );
				}
				ob_flush(); flush();
			}
		}
		
		echo '<br><p>'.$errs.' Tables with issues</p>
		';
	}
	
	echo '</div>';
}
	
	
?>