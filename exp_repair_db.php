<?php

// $Id: exp_repair_db.php 5030 2023-04-12 20:31:34Z duncan $
// Repair database tool.
// Also includes DUMP_SCHEMA and CHECK_SCHEMA for patching DB to latest revision.
//! SCR# 614 - use exp_repair_db.php?CHECK_SCHEMA&RESERVED to find reserved columns

// Set flag that this is a parent file
define( '_STS_INCLUDE', 1 );

// Avoid getting timed out
define( '_STS_SESSION_AJAX', 1 );

// Set flag that we are doing a CHECK_SCHEMA
if( isset($_GET['CHECK_SCHEMA']) || isset($_POST["dopatch"]) || isset($_GET['DUMP_SCHEMA']) )
	define( '_STS_CHECK_SCHEMA', 1 );

// Setup Session
require_once( "include/sts_session_setup.php" );

require_once( "include/sts_config.php" );
set_time_limit(0);
ini_set('memory_limit', '1024M');

$sts_debug = isset($_GET['debug']);
require_once( "include/sts_session_class.php" );

if( isset($_SESSION['EXT_USERNAME']) ||
	! (isset($_GET['CHECK_SCHEMA']) || isset($_POST["dopatch"])) ) {
	$my_session = sts_session::getInstance( $exspeedite_db, $sts_debug );
	$my_session->access_check( EXT_GROUP_ADMIN );	// Make sure we should be here
} else {
	if( ! isset($_SESSION) ) $_SESSION = array();
	$_SESSION['EXT_USER_CODE'] = 0;
}

$sts_subtitle = "Repair DB";
require_once( "include/header_inc.php" );

if( ! $sts_debug && (isset($_SESSION['EXT_USERNAME']) ||
	! (isset($_GET['CHECK_SCHEMA']) || isset($_POST["dopatch"]))) ) {
	require_once( "include/navbar_inc.php" );
}
?>

<div class="container" role="main">

<!--
<div class="alert alert-warning alert-dismissable">
  <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
  <strong>Warning!</strong> DO NOT 'FIX' ANY PROBLEMS HERE. They are not problems, I am changing the schema.
</div>
-->
<h2><span class="glyphicon glyphicon-wrench"></span> Repair DB Utility</h2>

<div class="well  well-lg">

<?php
if( isset($_GET['CHECK_SCHEMA']) || isset($_POST["dopatch"]) ) {
	require_once( "include/sts_db_class.php" );
} else {
	require_once( "include/sts_result_class.php" );
	
	require_once( "include/sts_stop_class.php" );
	require_once( "include/sts_shipment_class.php" );
	require_once( "include/sts_shipment_load_class.php" );
	require_once( "include/sts_load_class.php" );
	require_once( "include/sts_status_class.php" );
	require_once( "include/sts_detail_class.php" );
	require_once( "include/sts_status_class.php" );
}

$static_tables = array(
	HOLIDAY_TABLE		=> 'HOLIDAY_CODE',
	UNIT_TABLE			=> 'UNIT_CODE',
	SCHEMA_TABLE		=> 'VERSION_NUMBER',
	STATES_TABLE		=> 'id',
	STATUS_CODES_TABLE	=> 'STATUS_CODES_CODE',
	CATEGORY_TABLE		=> 'CATEGORY_CODE',
	CLIENT_CAT			=> 'CLIENT_CAT',	//! SCR# 374 - add missing table
	CANADA_TAX_TABLE	=> 'TAX_CODE',
	UN_NUMBER_TABLE		=> 'UN_NUMBER',	// About 128 KB
	TIMEZONE_TABLE		=> 'ZONE_OFFSET',	// List of timezones by offset from GMT
	VIDEO_TABLE			=> 'VIDEO_CODE',
	ARTICLE_TABLE		=> 'ARTICLE_CODE',
	
	//ZIP_TABLE			=> 'ZipCode'				// Huge > 24MB
);

$columns_to_ignore = array(
	'CREATED_DATE',
	'CHANGED_DATE',
	'CREATED_BY',
	'CHANGED_BY'
);

function vl( $load_code ) {
	return '<a href="exp_viewload.php?CODE='.$load_code.'" target="_blank">'.$load_code.'</a>';
}

function vs( $load_code ) {
	return '<a href="exp_addshipment.php?CODE='.$load_code.'" target="_blank">'.$load_code.'</a>';
}

function table_columns( $schema ) {
	$result = array();
	$current_table = "";
	foreach( $schema as $row ) {
		if( $row['TABLE_NAME'] <> $current_table ) {
			$current_table = $row['TABLE_NAME'];
			$result[$current_table][$row['COLUMN_NAME']] = $row;
		} else {
			$result[$current_table][$row['COLUMN_NAME']] = $row;
		}
	}
	return $result;
}

function format_add_column( $column_name, $row ) {
	global $mysql_8017, $sts_debug;
	if( $sts_debug ) {
		echo "<pre>".__FUNCTION__."\n";
		var_dump($column_name, $row);
		echo "</pre>";
	}
	
	$ge = str_replace('_utf8mb3', '', $row['GENERATION_EXPRESSION']);
	$ge = str_replace('_utf8mb4', '', $row['GENERATION_EXPRESSION']);
	$ge = str_replace("\\'", "'", $ge);
	if( ! $mysql_8017 && $column_name == 'INSURANCE_CURRENCY' )
		$ge = str_replace("`CURRENCY_CODE`", "'USD'", $ge);

	return "`".$column_name."` ".$row['COLUMN_TYPE'].
		($row['IS_NULLABLE'] == "NO" ? " NOT NULL" :
			(strpos(strtoupper($row['EXTRA']), 'GENERATED') === false ? " NULL" : "")).
		(isset($row['COLUMN_DEFAULT'])  ? " DEFAULT ".
			($row['COLUMN_DEFAULT'] == 'CURRENT_TIMESTAMP' ? $row['COLUMN_DEFAULT'] :
				($row['EXTRA'] == 'DEFAULT_GENERATED' ? '('.$row['COLUMN_DEFAULT'].')' : 
					"'".$row['COLUMN_DEFAULT']."'")) : "").
		($row['EXTRA'] == "auto_increment"  ? " AUTO_INCREMENT" : "");
	//	(in_array(strtoupper($row['EXTRA']), ['VIRTUAL GENERATED', 'STORED GENERATED'] ) ? " GENERATED ALWAYS AS (".$ge.")".
	//		(strtoupper($row['EXTRA']) == 'STORED GENERATED' ? ' STORED' : '') : "")
	//	;
}

function key_type( $add, $type, $index_name ) {

	switch( $type ) {
		case 3:
			$result = "PRIMARY KEY";
			break;
		case 2:
			$result = ($add ? "UNIQUE KEY" : "KEY")." ".$index_name;
			break;
		default:	
			$result = "KEY ".$index_name;
			break;
	}
	return $result;
}

function get_indexes( $table, $keys ) {
	
	$schema_indexes = isset($keys) && isset($keys[$table]) ? $keys[$table] : array();

	$result = array();

	foreach($schema_indexes as $index_name => $index) {
		$result[] = key_type( true, $index["TYPE"], $index_name )." (".$index["COLUMNS"].")";
	}

	return $result;
}

function routine_names( $schema ) {
	$result = array();
	if( is_array($schema) )
		foreach( $schema as $row ) {
			$result[$row['ROUTINE_NAME']] = $row;
		}
	return $result;
}

function trigger_names( $schema ) {
	$result = array();
	if( is_array($schema) )
		foreach( $schema as $row ) {
			$result[$row['TRIGGER_NAME']] = $row;
		}
	return $result;
}

function view_names( $schema ) {
	$result = array();
	if( is_array($schema) )
		foreach( $schema as $row ) {
			$result[$row['TABLE_NAME']] = $row;
		}
	return $result;
}

function routine_parameters( $name, $schema ) {
	$returns = "";
	$params = array();
	foreach( $schema as $row ) {
		if($row["SPECIFIC_NAME"] == $name ) {
			if( $row["ORDINAL_POSITION"] == 0 ) {
				$returns = "RETURNS ".$row["DTD_IDENTIFIER"];
			} else {
				$params[] = $row["PARAMETER_NAME"]." ".$row["DTD_IDENTIFIER"];
			}
		}
	}
	return " ( ".implode(", ", $params)." ) ".$returns;
}

//! get_definer - find a suitable definer for a procedure or trigger
function get_definer( $db, $match ) {
	global $sts_debug;
	
	$definer = false;
	$result = $db->get_multiple_rows("
	SELECT DISTINCT GRANTEE FROM INFORMATION_SCHEMA.USER_PRIVILEGES
	WHERE GRANTEE like '\'".$match."\'%'
	AND (PRIVILEGE_TYPE = 'TRIGGER'
	OR PRIVILEGE_TYPE = 'SUPER')");

	if( $sts_debug ) {
		echo "<pre>";
		var_dump($result);
		echo "</pre>";
	}
	
	if( $match <> 'duncan' && is_array($result) && count($result) > 0 )
		$definer = str_replace("'", "`", $result[0]['GRANTEE']);
	
	return $definer;		
}

//! enquote_values - add quotes to non-int values
function enquote_values( $row, $schema ) {
	$str = array();
	foreach( $row as $column => $value ) {
		if( $value == NULL )
			$str[] = 'NULL';
		else if( in_array($schema[$column]["DATA_TYPE"], array('int', 'tinyint')))
			$str[] = $value;
		else
			$str[] = "'".$value."'";
	}
	return implode(', ', $str);
}

function enquote_value( $row, $column, $schema ) {
	if( $row[$column] == NULL )
		return 'NULL';
	else if( in_array($schema[$column]["DATA_TYPE"], array('int', 'tinyint')))
		return $row[$column];
	else
		return "'".$row[$column]."'";
}

//! Version 8 - int(N) becomes int
function mysql8_int( $str ) {
	global $mysql_8017;
	
	if( $mysql_8017 ) {
		$str = preg_replace('/smallint\(\d+\)/i', 'smallint', $str);
		$str = preg_replace('/tinyint\(\d+\)/i', 'tinyint', $str);
		$str = preg_replace('/int\(\d+\)/i', 'int', $str);
	}
	
	return $str;
}

//! SCR# 432 - reduce string like int(11) to int
function base_type( $str ) {
	$bpos = strpos($str,'(');
	if( $bpos == false ) $bpos = strlen($str);
	return substr($str, 0, $bpos );
}

function import_db_schema( $db, $db_schema ) {
	global $static_tables;
	
	$result1 = $db->get_multiple_rows("
	SELECT c.*
	FROM INFORMATION_SCHEMA.COLUMNS c, INFORMATION_SCHEMA.TABLES t
	WHERE c.TABLE_SCHEMA = '".$db_schema."'
	AND c.TABLE_NAME = t.TABLE_NAME
	AND c.TABLE_SCHEMA = t.TABLE_SCHEMA
	AND t.TABLE_TYPE = 'BASE TABLE'
	ORDER BY c.TABLE_NAME, c.ORDINAL_POSITION");
		
	if( ! is_array($result1) ) die( "</p>import_db Error1: ".$db->error()."</p>" );
	
	$result2 = $db->get_multiple_rows("
	SELECT *
	FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE
	WHERE CONSTRAINT_SCHEMA = '".$db_schema."'
	ORDER BY TABLE_NAME, ORDINAL_POSITION");
		
	if( ! is_array($result2) ) die( "</p>import_db Error2: ".$db->error()."</p>" );

	//INNODB_INDEXES <-- 8.0
	$v = explode('.',$db->server_info());
	$index_table = intval($v[0]) >= 8 ? 'INNODB_INDEXES' : 'INNODB_SYS_INDEXES';

	$result2a = $db->get_multiple_rows("
	SELECT DISTINCT s.TABLE_NAME, s.INDEX_NAME, i.TYPE, 
		GROUP_CONCAT(DISTINCT s.column_name ORDER BY s.seq_in_index) AS `COLUMNS`
	FROM INFORMATION_SCHEMA.STATISTICS s, INFORMATION_SCHEMA.$index_table i
	WHERE TABLE_SCHEMA = '".$db_schema."'
	and s.index_name = i.name
	group by s.TABLE_NAME, s.INDEX_NAME, i.TYPE
	order by s.TABLE_NAME ASC, i.type DESC, 4 ASC");
		
	if( is_array($result2a) ) {
		$last_table = "none";
		$result2b = array();
		foreach( $result2a as $row ) {
			if( $row["TABLE_NAME"] <> $last_table ) {
				$last_table = $row["TABLE_NAME"];
			}
			$result2b[$last_table][$row["INDEX_NAME"]] = array("TYPE" => $row["TYPE"], "COLUMNS" => $row["COLUMNS"]);
		}
	} else
		die( "</p>import_db Error2a: ".$db->error()."</p>" );

	$result3 = $db->get_multiple_rows("
	SELECT *
	FROM INFORMATION_SCHEMA.ROUTINES
	WHERE ROUTINE_SCHEMA = '".$db_schema."'
	ORDER BY ROUTINE_NAME");
		
	if( ! is_array($result3) ) die( "</p>import_db Error3: ".$db->error()."</p>" );

	$result4 = $db->get_multiple_rows("
	SELECT *
	FROM INFORMATION_SCHEMA.PARAMETERS
	WHERE SPECIFIC_SCHEMA = '".$db_schema."'
	ORDER BY SPECIFIC_NAME, ORDINAL_POSITION");
		
	if( ! is_array($result4) ) die( "</p>import_db Error4: ".$db->error()."</p>" );
	
	$result5 = $db->get_multiple_rows("
	SELECT *
	FROM INFORMATION_SCHEMA.TRIGGERS
	WHERE TRIGGER_SCHEMA = '".$db_schema."'
	ORDER BY TRIGGER_NAME");
		
	if( ! is_array($result5) ) die( "</p>import_db Error5: ".$db->error()."</p>" );
	
	$static_result = array();
	foreach( $static_tables as $static_table => $pk) {
		$result6 = $db->get_multiple_rows("
		SELECT *
		FROM `$db_schema`.$static_table
		ORDER BY $pk");
			
		if( is_array($result6) ) {
			foreach( $result6 as $row ) {
				$static_result[$static_table][$row[$pk]] = $row;
			}
		}
	}
	
	$result7 = $db->get_multiple_rows("
	SELECT TABLE_NAME, VIEW_DEFINITION, DEFINER, SECURITY_TYPE
	FROM INFORMATION_SCHEMA.VIEWS
	WHERE TABLE_SCHEMA = '".$db_schema."'
	ORDER BY TABLE_NAME");
	
	// Strip schema name from inside view definition
	if( is_array($result7) ) {
		for($c=0; $c < count($result7); $c++ ) {
			$result7[$c] = str_replace('`'.$db_schema.'`.', '', $result7[$c]);
		}
	}
		
	if( ! is_array($result7) ) die( "</p>import_db Error7: ".$db->error()."</p>" );
	
	$result = array("COLUMNS" => $result1, "KEY_COLUMN_USAGE" => $result2,
		"INDEXES" => $result2b, "ROUTINES" => $result3, "PARAMETERS" => $result4, "TRIGGERS" => $result5,
		"STATIC_TABLES" => $static_result, "VIEWS" => $result7,
		"COMMENT" => "Based on $db_schema from ".$_SERVER['SERVER_NAME']." (".$db->server_info().") ".
		" on ".date('m/d/Y h:i:s A') );
	
	return $result;
}

function import_file_schema( $release ) {
	global $sts_schema_directory, $sts_schema_suffix, $sts_database;
	
	$schema = false;
	if( file_exists( $sts_schema_directory.$release.$sts_schema_suffix ) ) {
		$raw = file_get_contents($sts_schema_directory.$release.$sts_schema_suffix);
		$schema = json_decode($raw, true);
		
		// Strip schema name from inside view definition
		if( isset($schema["VIEWS"]) && is_array($schema["VIEWS"]) ) {
			for($c=0; $c < count($schema["VIEWS"]); $c++ ) {
				$schema["VIEWS"][$c] = str_replace('`'.$sts_database.'`.', '', $schema["VIEWS"][$c]);
			}
		}

	}
	return $schema;	
}

if (ob_get_level() == 0) ob_start();

//! SCR# 726 - Apply patches to DB
// 
if( isset($_POST["dopatch"]) || ! empty($_POST["patches"])) {
	//echo "<pre>";
	//var_dump($_POST);
	//echo "</pre>";

	$num = 0;
	$command = [];
	$recombine = [];
	$delimiter = false;
	//foreach( explode(";", $_POST["patches"]) as $line ) {
		foreach( explode("\n", $_POST["patches"]) as $sub_line ) {
			//echo "<pre>";
			//var_dump(bin2hex($sub_line));
			//echo "</pre>";
			$sub_line = rtrim($sub_line);

			if( ! empty($sub_line) &&
				strlen($sub_line) > 0 &&
				$sub_line != "\r" &&
				(strpos($sub_line, '--') === false || $delimiter) ) {
				//echo "<p>".++$num.($delimiter ? 'D':'').": ".$sub_line."<br>";
				//echo "<pre>";
				//var_dump(substr($sub_line, -1), $delimiter);
				//echo "</pre>";

				if( $sub_line == 'DELIMITER ;;' ) {
					$delimiter = true;
				} else if( $sub_line == 'DELIMITER ;' ) {
					$delimiter = false;
					$command[] = str_replace(';;', ';', implode("\n ", $recombine));
					$recombine = [];
				} else {
					$recombine[] = $sub_line;
					if( substr($sub_line, -1) == ';' && ! $delimiter ) {
						$command[] = implode("\n ", $recombine);
						$recombine = [];
					}
				}
				
			}
			
		}
		
		
	//}
	
	$use = array_shift($command);
	$patch_db = substr($use, 5, strlen($use)-7 );
	
	//echo "<pre>";
	//var_dump($use, $patch_db, $command);
	//echo "</pre>";

	
	if( count($command) > 0 ) {
	
		echo "<pre>".$use."</pre>";
		$patch_db = new sts_db($sts_db_host, $sts_username, $sts_password,
			$patch_db, $sts_db_port, $sts_profiling, $sts_debug);
		if( $patch_db )
			echo '<p class="text-success"><strong><span class="glyphicon glyphicon-ok"></span> OK</strong></p>';
		else
			echo '<p class="text-danger"><strong><span class="glyphicon glyphicon-warning-sign"></span> FAILED: '.$patch_db->error().'<//p></p>';
		ob_flush(); flush();
			
		foreach( $command as $line ) {
			echo "<pre>".$line.";</pre>";
			ob_flush(); flush();
			
			if( substr($line, 5) == 'DELIM' )
				$result = $patch_db->multi_query($line);
			else
				$result = $patch_db->get_one_row($line);
			if( $result )
				echo '<p class="text-success"><strong><span class="glyphicon glyphicon-ok"></span> OK</strong></p>';
			else
				echo '<p class="text-danger"><strong><span class="glyphicon glyphicon-warning-sign"></span> FAILED: '.$patch_db->error().'</strong></p>';
			ob_flush(); flush();
		}
	} else {
		echo "<p>No patches to apply.</p>";
	}
	
	echo '<h3><a class="btn btn-md btn-default" id="back" href="exp_repair_db.php?CHECK_SCHEMA"><span class="glyphicon glyphicon-arrow-left"></span> Back</a></h3>';
	
	die;
} 

//if( in_group( EXT_GROUP_DEBUG ) ) {
	
	if( ! isset($_GET['CHECK_SCHEMA']) ) {
		$load_table = sts_load::getInstance($exspeedite_db, $sts_debug);
		$shipment_table = sts_shipment::getInstance($exspeedite_db, $sts_debug);
		$shipment_load_table = sts_shipment_load::getInstance($exspeedite_db, $sts_debug);
		$stop_table = sts_stop::getInstance($exspeedite_db, $sts_debug);
		$detail_table = sts_detail::getInstance($exspeedite_db, $sts_debug);
		$status_table = sts_status::getInstance($exspeedite_db, $sts_debug);
	}
	
	$errors_found = 0;
	$warnings_found = 0;
	
	//$sts_database = "forl1327_exspeed";
	//$sts_database = "exspeed";
	if( isset($_GET['DB']) )
		$sts_database = $_GET['DB'];
	if( isset($_GET['REL']) )
		$sts_schema_release_name = $_GET['REL'];

	if( isset($_GET['DEFINER']) ) { //! DEFINER
		die( "</p>DEFINER: ".get_definer($exspeedite_db,'root')."</p>" );
	}
	
	//! Check the version of MySQL and alter our code appropriately
	$version_check = $exspeedite_db->get_one_row("SELECT VERSION() AS VER");
	if( is_array($version_check) && isset($version_check["VER"]) &&
		version_compare($version_check["VER"],"5.7.4") < 0 )
		$alter_table = "ALTER IGNORE TABLE";
	else
		$alter_table = "ALTER TABLE";
	
	//! Version 8 is different
	$mysql_ver8 = is_array($version_check) && isset($version_check["VER"]) &&
		version_compare($version_check["VER"],"8.0.0") > 0;
	// Defining the display width within the parenthesis is deprecated for integer types since MySQL 8.0.17	
	$mysql_8017 = is_array($version_check) && isset($version_check["VER"]) &&
		version_compare($version_check["VER"],"8.0.17") > 0;
	
	
	if( isset($_GET['CHARSET']) ) { //! CHARSET
		
		echo "<h3>Fix Character Set and Collation for $sts_database ".'<a class="btn btn-md btn-default inform" href="exp_install.php?code=875406" id="INSTALL" data-toggle="popover" data-content="Check the schema for updates and get a patch."><span class="glyphicon glyphicon-briefcase"></span> Install Page</a>'."</h3>
		<h4>We standardize on utf8 and utf8_general_ci for all tables, columns, etc.<br>
		If you get an error, you may have an issue with mysql or windows user permissions.</h4>";
		
		$general = array(
			"SET collation_connection = 'utf8_general_ci';",
			"ALTER DATABASE `".$sts_database."` CHARACTER SET utf8 COLLATE utf8_general_ci;"
		);
		if( count($general) > 0 ) {
			foreach( $general as $row ) {
				echo "<p>".$row;
				$result0a = $load_table->database->get_one_row($row);
				if( $result0a ) echo " <strong>(OK)</strong>";
				else echo " <strong>(error ".$load_table->database->error().")<//p>";
				echo "</p>";
			}
		}
		
		$result1 = $load_table->database->get_multiple_rows("
		select concat('ALTER TABLE ".$sts_database.".',table_name, ' CONVERT TO CHARACTER SET utf8 
		COLLATE utf8_general_ci;') cmd
		from information_schema.tables
		where table_schema = '".$sts_database."'");
		
		if( ! is_array($result1) ) die( "</p>CHARSET Error1: ".$load_table->database->error()."</p>" );

		if( count($result1) > 0 ) {
			foreach( $result1 as $row ) {
				echo "<p>".$row["cmd"];
				$result1a = $load_table->database->get_one_row($row["cmd"]);
				if( $result1a ) echo " <strong>(OK)</strong>";
				else echo " <strong>(error ".$load_table->database->error().")<//p>";
				echo "</p>";
			}
		}
		
		$result2 = $load_table->database->get_multiple_rows("
		select concat('ALTER TABLE ".$sts_database.".',table_name, ' DEFAULT CHARSET utf8 COLLATE utf8_general_ci;') cmd
		from information_schema.tables
		where table_schema = '".$sts_database."'");	

		if( ! is_array($result2) ) die( "</p>CHARSET Error2: ".$load_table->database->error()."</p>" );

		if( count($result2) > 0 ) {
			foreach( $result2 as $row ) {
				echo "<p>".$row["cmd"];
				$result2a = $load_table->database->get_one_row($row["cmd"]);
				if( $result2a ) echo " <strong>(OK)</strong>";
				else echo " <strong>(error ".$load_table->database->error().")<//p>";
				echo "</p>";
			}
		}
		
		$result3 = $load_table->database->get_multiple_rows("
		SELECT concat('ALTER TABLE ".$sts_database.".',table_name, ' MODIFY ',column_name, ' ', column_type,
		' CHARACTER SET utf8 COLLATE utf8_general_ci;') cmd
		FROM information_schema.COLUMNS
		WHERE TABLE_SCHEMA = '".$sts_database."' AND CHARACTER_SET_NAME = 'latin1'");
		
		if( ! is_array($result3) ) die( "</p>CHARSET Error3: ".$load_table->database->error()."</p>" );
		
		if( count($result3) > 0 ) {
			foreach( $result3 as $row ) {
				echo "<p>".$row["cmd"];
				$result3a = $load_table->database->get_one_row($row["cmd"]);
				if( $result3a ) echo " <strong>(OK)</strong>";
				else echo " <strong>(error ".$load_table->database->error().")<//p>";
				echo "</p>";
			}
		}
		
	die("<p>End of CHARSET fixes.</p>");

	} 
	
	if( isset($_GET['DUMP_SCHEMA']) ) { //! DUMP_SCHEMA
		
		//! SCR# 185 - log when we DUMP_SCHEMA
		require_once( "include/sts_user_log_class.php" );
		$user_log_table = sts_user_log::getInstance($exspeedite_db, $sts_debug);
		$user_log_table->log_event('admin', 'DUMP_SCHEMA');

		$schema = sts_schema_version::getInstance($exspeedite_db, $sts_debug);
		$schema->set_version( $sts_schema_release_name );	//! Update schema version first
		
		$result = import_db_schema($load_table->database, $sts_database);
		
		$file_result = file_put_contents($sts_schema_directory.$sts_schema_release_name.$sts_schema_suffix,
			json_encode($result, JSON_PRETTY_PRINT ) );
		die( "<h3>DUMP_SCHEMA: database $sts_database to schema $sts_schema_release_name ".($file_result ? 'successful' : 'failed')."</h3>" );

		require_once( "include/sts_status_codes_class.php" );
		
		$status_codes_table = sts_status_codes::getInstance($exspeedite_db, $sts_debug);
		if (ob_get_level() == 0) ob_start();
		
		echo '<div class="container" role="main">
			<div id="loading"><h2 class="text-center"><img src="images/loading.gif" alt="loading" width="125" height="125" /><br>Updating Cached Static Information...</h2></div></div>';
		ob_flush(); flush();
	
		$status_codes_table->write_cache();
		update_message( 'loading', '' );
		ob_flush(); flush();
	}

	if( isset($_GET['CHECK_SCHEMA']) ) { //! CHECK_SCHEMA

		//! SCR# 185 - log when we CHECK_SCHEMA
		if( isset($_SESSION['EXT_USERNAME']) ) {
			require_once( "include/sts_user_log_class.php" );
			$user_log_table = sts_user_log::getInstance($exspeedite_db, $sts_debug);
			$user_log_table->log_event('admin', 'CHECK_SCHEMA');
		}

		echo '<h3><a class="btn btn-md btn-danger inform" href="exp_repair_db.php?CHARSET" id="CHARSET" data-toggle="popover" data-content="Fix character set/collation for DB."><span class="glyphicon glyphicon-briefcase"></span> Fix Character Set</a>
		<a class="btn btn-md btn-default inform" href="exp_install.php?code=875406" id="INSTALL" data-toggle="popover" data-content="Check the schema for updates and get a patch."><span class="glyphicon glyphicon-briefcase"></span> Install Page</a></h3>';

		
		if( file_exists( $sts_schema_directory.$sts_schema_release_name.$sts_schema_suffix ) ) {
			$schema = import_file_schema( $sts_schema_release_name );
			
			if( is_array($schema) ) {

				if( isset($_GET['SCHEMA2']) ) {
					$result = import_file_schema( $_GET['SCHEMA2'] );
					echo "<h2>Compare schema ".$_GET['SCHEMA2']." to schema ".$sts_schema_release_name."</h2>";
					$patch = "-- Patch Exspeedite schema ".$_GET['SCHEMA2']." to schema ".$sts_schema_release_name.(isset($schema['COMMENT']) ? "\n-- ".$schema['COMMENT'] : "");
				} else {
					$result = import_db_schema($exspeedite_db, $sts_database);
					echo "<h2>Compare database ".$sts_database." to schema ".$sts_schema_release_name."</h2>";
					$patch = "-- Patch Exspeedite DB ".$sts_database." to schema ".$sts_schema_release_name.(isset($schema['COMMENT']) ? "\n-- ".$schema['COMMENT'] : "");
				}
				
				if( isset($_GET['REVERSE']) ) {	//! REVERSE = switch around db/file
					$tmp = $result;
					$result = $schema;
					$schema = $tmp;
				}
		
				if( is_array($result) ) {
					$result_tables = table_columns( $result['COLUMNS'] );
					$schema_tables = table_columns( $schema['COLUMNS'] );
					$r = array_keys($result_tables);
					
					//! SCR# 614 - Check for reserved words
					if( isset($_GET['RESERVED'])) {
						$count = 1;
						foreach( $r as $table_name ) {
							if( $exspeedite_db->is_reserved( $table_name ) )
								echo "<p>".$count++." TABLE $table_name is RESERVED</p>";
							$c = array_keys($result_tables[$table_name]);
							foreach( $c as $column_name ) {
								if( $exspeedite_db->is_reserved( $column_name ) )
									echo "<p>".$count++." TABLE $table_name COLUMN $column_name is RESERVED</p>";
							}

							echo "</pre>";
						}
					} else
					if( isset($_GET['ROUTINES'])) {
						foreach( $result['ROUTINES'] as $r ) {
							echo "<h2>".$r["ROUTINE_NAME"]."</h2>";
							echo "<pre>";
							var_dump($exspeedite_db->highlight_reserved($r['ROUTINE_DEFINITION']));
							echo "</pre>";
						}
						
						die;
					} else
					if( isset($_GET['TRIGGERS'])) {
/*
						echo "<pre>";
						var_dump($result['TRIGGERS']);
						echo "</pre>";
*/
						foreach( $result['TRIGGERS'] as $r ) {
							echo "<h2>".$r["TRIGGER_NAME"]." - ".$r["EVENT_OBJECT_TABLE"]."</h2>";
							echo "<pre>";
							var_dump($exspeedite_db->highlight_reserved($r['ACTION_STATEMENT']));
							echo "</pre>";
						}
						
						die;
					}

					
					$s = array_keys($schema_tables);
					$combined_keys = array_unique(array_merge($r, $s));
					sort($combined_keys);

					$patch .= "\n\n".
						"USE `".$sts_database."`;\n\n";
						
					$sts_user_host = 'localhost';

					//! MySQL version > 8.0.17 - GRANT USER
					if( $mysql_8017 ) {
						$user_check = $exspeedite_db->get_multiple_rows(
							"SELECT * FROM mysql.user WHERE User = '".$sts_username."'");
						if( is_array($user_check) && count($user_check) > 0 ) {
							$has_localhost = $has_percent = false;
							
							foreach($user_check as $ur) {
								if( $ur["Host"] == 'localhost' )
									$has_localhost = true;
								else if( $ur["Host"] == '%' )
									$has_percent = true;
							}
							
							if( $has_percent && ! $has_localhost ) {
								$sts_user_host = '%';
								$patch .= "-- WARNING: User ".$sts_username."@% found. This may fail, as Exspeedite expects @localhost.\n".
								"-- Also using % is more of a security risk.\n".
								"-- Create a user ".$sts_username."@localhost before you continue...\n\n";
							} else if( $has_percent && $has_localhost ) {
								$patch .= "-- WARNING: User ".$sts_username."@% AND ".$sts_username."@localhost found. \n".
								"-- Using % is a security risk.\n".
								"-- Remove a user ".$sts_username."@% before you continue...\n\n";
							} else {
								$patch .= "-- NOTICE: User ".$sts_username."@localhost found. This is as Exspeedite expects.\n\n";
							}
						} else {
							$patch .= "-- WARNING: No user ".$sts_username." found. This will fail.\n".
							"-- Create a user ".$sts_username."@localhost before you continue...\n\n";
						}

						$patch .= "-- NOTICE: This is needed for MySQL > 8.0.17\n".
						"-- If you aren't patching directly, you may need to edit this next line.\n".
						"GRANT SYSTEM_USER ON *.* TO '".$sts_username."'@'".$sts_user_host."';\n\n";
					}
					
					$patch .= "-- Tables\n\n";
					
					foreach($combined_keys as $table_name) {
						if( isset($result_tables[$table_name]) && ! isset($schema_tables[$table_name]) ) {
							$patch .= "DROP TABLE IF EXISTS `$table_name`;\n";
						} else
						if( ! isset($result_tables[$table_name]) && isset($schema_tables[$table_name]) ) {
							$changes = array();
							foreach( $schema_tables[$table_name] as $column_name => $row ) {
								$changes[] =  format_add_column($column_name, $row);
							}
							
							$indexes = get_indexes( $table_name, $schema['INDEXES'] );

							$patch .= "CREATE TABLE `$table_name` (\n  ".
								implode(",\n  ", $changes).
								(count($indexes) > 0 ? ",\n  ".implode(",\n  ", $indexes) : '').
								"\n) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;\n\n";
		
						} else  {
							$r = array_keys($result_tables[$table_name]);
							$s = array_keys($schema_tables[$table_name]);
							$combined_column_keys = array_unique(array_merge($r, $s));
							sort($combined_column_keys);
							$changes = array();
							
							foreach($combined_column_keys as $column_name) {
									
								//! Version 8 - int(N) becomes int
								if( $mysql_8017 ) {
									if( is_array($schema_tables) &&
										isset($schema_tables[$table_name]) &&
										isset($schema_tables[$table_name][$column_name]) &&
										substr($schema_tables[$table_name][$column_name]['COLUMN_TYPE'], 0, 4) == 'int(' )
										$schema_tables[$table_name][$column_name]['COLUMN_TYPE'] = 'int';
									if( is_array($schema_tables) &&
										isset($schema_tables[$table_name]) &&
										isset($schema_tables[$table_name][$column_name]) &&
										substr($schema_tables[$table_name][$column_name]['COLUMN_TYPE'], 0, 9) == 'smallint(' )
										$schema_tables[$table_name][$column_name]['COLUMN_TYPE'] = 'smallint';
									if( is_array($schema_tables) &&
										isset($schema_tables[$table_name]) &&
										isset($schema_tables[$table_name][$column_name]) &&
										substr($schema_tables[$table_name][$column_name]['COLUMN_TYPE'], 0, 8) == 'tinyint(' &&
									( ! isset($result_tables[$table_name][$column_name]) ||
										$result_tables[$table_name][$column_name]['COLUMN_TYPE'] == 'tinyint') )
										$schema_tables[$table_name][$column_name]['COLUMN_TYPE'] = 'tinyint';
								} else {
									if( is_array($schema_tables) &&
										isset($schema_tables[$table_name]) &&
										isset($schema_tables[$table_name][$column_name]) &&
										$schema_tables[$table_name][$column_name]['COLUMN_TYPE'] == 'int' )
										$schema_tables[$table_name][$column_name]['COLUMN_TYPE'] = 'int(11)';

									if( is_array($schema_tables) &&
										isset($schema_tables[$table_name]) &&
										isset($schema_tables[$table_name][$column_name]) &&
										$schema_tables[$table_name][$column_name]['COLUMN_TYPE'] == 'smallint' )
										$schema_tables[$table_name][$column_name]['COLUMN_TYPE'] = 'smallint(6)';

									if( is_array($schema_tables) &&
										isset($schema_tables[$table_name]) &&
										isset($schema_tables[$table_name][$column_name]) &&
										$schema_tables[$table_name][$column_name]['COLUMN_TYPE'] == 'tinyint' )
										$schema_tables[$table_name][$column_name]['COLUMN_TYPE'] = 'tinyint(4)';
								}
										
								if( isset($result_tables[$table_name][$column_name]) && ! isset($schema_tables[$table_name][$column_name]) ) {
									$changes[] = "DROP COLUMN $column_name";
								} else if( ! isset($result_tables[$table_name][$column_name]) && isset($schema_tables[$table_name][$column_name]) ) {
									if( $sts_debug ) {
										echo "<pre>ADD COLUMN\n";
										var_dump($schema_tables[$table_name][$column_name]);
										echo "</pre>";
									}
									$changes[] =  "ADD COLUMN ".format_add_column($column_name, $schema_tables[$table_name][$column_name]);
								} else if( isset($result_tables[$table_name][$column_name]) &&
									isset($schema_tables[$table_name][$column_name]) ) {
									$r = $result_tables[$table_name][$column_name];
									$s = $schema_tables[$table_name][$column_name];
									
									if( $s['COLUMN_DEFAULT'] == '0000-00-00 00:00:00')
										$s['COLUMN_DEFAULT'] = 'CURRENT_TIMESTAMP';
									
									$s['GENERATION_EXPRESSION'] = str_replace('_utf8mb3', '', $s['GENERATION_EXPRESSION']);
									$s['GENERATION_EXPRESSION'] = str_replace('_utf8mb4', '', $s['GENERATION_EXPRESSION']);
									$s['GENERATION_EXPRESSION'] = str_replace('\\\'', "'", $s['GENERATION_EXPRESSION']);
									$r['GENERATION_EXPRESSION'] = str_replace('_utf8mb3', '', $r['GENERATION_EXPRESSION']);
									$r['GENERATION_EXPRESSION'] = str_replace('_utf8mb4', '', $r['GENERATION_EXPRESSION']);
									$r['GENERATION_EXPRESSION'] = str_replace('\\\'', "'", $r['GENERATION_EXPRESSION']);
									if( $r['COLUMN_TYPE'] == 'timestamp' &&
										$r['COLUMN_DEFAULT'] == 'CURRENT_TIMESTAMP' &&
										$r['EXTRA'] == 'DEFAULT_GENERATED')
										$r['EXTRA'] = '';
									
									if( ! $mysql_8017 &&
										$s['COLUMN_DEFAULT'] == "`CURRENCY_CODE`" &&
										$s['EXTRA'] == "DEFAULT_GENERATED"
										) {
										$s['COLUMN_DEFAULT'] = "USD";
										$s['EXTRA'] = '';
									}
									
									if( $column_name == "CREATED_DATE" &&
										$s['COLUMN_DEFAULT'] == 'CURRENT_TIMESTAMP' &&
										$s['EXTRA'] == "DEFAULT_GENERATED" )
										$s['EXTRA'] = '';
									
									if( $r['COLUMN_TYPE'] <> $s['COLUMN_TYPE'] ||
										$r['IS_NULLABLE'] <> $s['IS_NULLABLE'] ||
										$r['COLUMN_DEFAULT'] <> $s['COLUMN_DEFAULT'] ||
										($r['EXTRA'] <> $s['EXTRA'] && 
										$s['EXTRA'] == 'auto_increment' ) ||
										($r['EXTRA'] <> $s['EXTRA'] &&
										strpos(strtoupper($s['EXTRA']), 'GENERATED') !== false &&
										$r['GENERATION_EXPRESSION'] <> $s['GENERATION_EXPRESSION'] ) ||
										$r['GENERATION_EXPRESSION'] <> $s['GENERATION_EXPRESSION']
										) {

										if( $sts_debug ) {
											echo "<pre>!!TABLE COLUMN\n";
											var_dump($table_name, $column_name);
											var_dump($r['COLUMN_TYPE'], $s['COLUMN_TYPE']);
											var_dump($r['IS_NULLABLE'], $s['IS_NULLABLE']);
											var_dump($r['COLUMN_DEFAULT'], $s['COLUMN_DEFAULT']);
											var_dump($r['EXTRA'], $s['EXTRA']);
											var_dump($r['GENERATION_EXPRESSION'], $s['GENERATION_EXPRESSION']);
											echo "</pre>";
										}

										//! SCR# 432 - varchar -> int use drop/add
										if( base_type($r['COLUMN_TYPE']) == 'varchar' &&
											base_type($s['COLUMN_TYPE']) == 'int' )
											$changes[] =  "DROP COLUMN ".$column_name.
												",\n  ADD COLUMN ".format_add_column($column_name,
												$s);
										else
											$changes[] =  "MODIFY ".format_add_column($column_name,
												$s);
									}
								}
							}
							
/*
ALTER TABLE `pipco1`.`exp_shipment` 
DROP INDEX `LOAD_CODE_I` ,
ADD INDEX `LOAD_CODE_I` (`LOAD_CODE` ASC, `DIRECTION` ASC);

ALTER TABLE `pipco1`.`exp_shipment` 
ADD UNIQUE INDEX `test1` (`QUOTE` ASC);

ALTER TABLE `pipco1`.`exp_shipment` 
DROP PRIMARY KEY,
ADD PRIMARY KEY (`SHIPMENT_CODE`, `SHIPMENT_TYPE`);

*/

							//! Index changes WIP ***

							$result_indexes = isset($result['INDEXES']) && isset($result['INDEXES'][$table_name]) ? $result['INDEXES'][$table_name] : array();
							$schema_indexes = isset($schema['INDEXES']) && isset($schema['INDEXES'][$table_name]) ? $schema['INDEXES'][$table_name] : array();
							if( 0 && $table_name == 'exp_carrier' ) {
								echo "<pre>";
								var_dump($table_name, $result_indexes, $schema_indexes);
								echo "</pre>";
							}

							// Get lists of index names, come up with a list of all
							$ri = array_keys($result_indexes);
							$si = array_keys($schema_indexes);
							$combined_index_keys = array_unique(array_merge($ri, $si));
							sort($combined_index_keys);

							foreach($combined_index_keys as $index_name) {
								if( isset($result_indexes[$index_name]) && ! isset($schema_indexes[$index_name]) ) {
									$changes[] = "DROP ".key_type( false, $result_indexes[$index_name]["TYPE"], $index_name );
								} else if( ! isset($result_indexes[$index_name]) && isset($schema_indexes[$index_name]) ) {
									$changes[] = "ADD ".key_type( true, $schema_indexes[$index_name]["TYPE"], $index_name )." (".$schema_indexes[$index_name]["COLUMNS"].")";
								} else if( isset($result_indexes[$index_name]) &&
									isset($schema_indexes[$index_name]) &&
									( $result_indexes[$index_name]["COLUMNS"] <>
										$schema_indexes[$index_name]["COLUMNS"] ||
									$result_indexes[$index_name]["TYPE"] <>
										$schema_indexes[$index_name]["TYPE"] ) ) {
									$changes[] = "DROP ".key_type( false, $result_indexes[$index_name]["TYPE"], $index_name );
									$changes[] = "ADD ".key_type( true, $schema_indexes[$index_name]["TYPE"], $index_name )." (".$schema_indexes[$index_name]["COLUMNS"].")";
								}
							}
							
							if( count($changes) > 0 ) {
								$patch .= $alter_table." `$table_name`\n  ".implode(",\n  ", $changes).";\n\n";
							}
							
						}
					}
					
					//!ON UPDATE FIX
					$onupdate = $exspeedite_db->get_multiple_rows("
						SELECT c.*
						FROM INFORMATION_SCHEMA.COLUMNS c, INFORMATION_SCHEMA.TABLES t
						WHERE c.TABLE_SCHEMA = '".$sts_database."'
						AND c.TABLE_NAME = t.TABLE_NAME
						AND c.TABLE_SCHEMA = t.TABLE_SCHEMA
						AND t.TABLE_TYPE = 'BASE TABLE'
						and c.DATA_TYPE = 'timestamp'
						and c.EXTRA = 'ON UPDATE CURRENT_TIMESTAMP'
						ORDER BY c.TABLE_NAME, c.ORDINAL_POSITION");
						
					if( is_array($onupdate) && count($onupdate) > 0 ) {
						$patch .= "\n-- ON UPDATE Fix\n\n";
						foreach( $onupdate as $upd ) {
							$patch .= 'ALTER TABLE '.$upd['TABLE_NAME'].
							' CHANGE '.$upd['COLUMN_NAME'].' '.$upd['COLUMN_NAME'].' TIMESTAMP NOT NULL DEFAULT 0;'."\n";
							$patch .= 'ALTER TABLE '.$upd['TABLE_NAME'].
							' CHANGE '.$upd['COLUMN_NAME'].' '.$upd['COLUMN_NAME'].' TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP;'."\n\n";
						}
					}
					
					//!STATIC TABLES
					if( isset($result['STATIC_TABLES']) && isset($schema['STATIC_TABLES']) ) {
						$patch .= "\n-- Static Tables\n\n";
						foreach( $static_tables as $static_table => $pk) {
							//! In schema, but not in DB, add rows
							if( ! isset($result['STATIC_TABLES'][$static_table]) &&
								isset($schema['STATIC_TABLES'][$static_table])) {
								$s = $schema['STATIC_TABLES'][$static_table];
								$sch = $schema_tables[strtolower($static_table)];
								foreach( array_keys($s) as $row_key) {
									$patch .= "INSERT INTO $static_table (".
											implode(', ',array_keys($s[$row_key])).
											") VALUES (".
											enquote_values( $s[$row_key], $sch )
											.");\n";
								}
								
							} else
							if( isset($result['STATIC_TABLES'][$static_table]) &&
								isset($schema['STATIC_TABLES'][$static_table])) {
								$r = $result['STATIC_TABLES'][$static_table];
								$s = $schema['STATIC_TABLES'][$static_table];
								$sch = $schema_tables[strtolower($static_table)];
								//$patch .= "\n-- Static Table $static_table ".count($r)." / ".count($s)."\n\n";
							//echo "<pre>";
							//var_dump($schema_tables[strtolower($static_table)]);
							//echo "</pre>";// ["DATA_TYPE"] == int, tinyint
								$rk = array_keys($r);
								$sk = array_keys($s);
								$combined_row_keys = array_unique(array_merge($rk, $sk));
								sort($combined_row_keys);
								
								foreach( $combined_row_keys as $row_key) {
									if( isset($r[$row_key]) && ! isset($s[$row_key]) ) {
										// remove
										if( $static_table <> UNIT_TABLE )
											$patch .= "DELETE FROM $static_table WHERE $pk = ".enquote_value( $r[$row_key], $pk, $sch ).";\n";
									} else if( ! isset($r[$row_key]) && isset($s[$row_key]) ) {
										// add
										$patch .= "INSERT INTO $static_table (".
											implode(', ',array_keys($s[$row_key])).
											") VALUES (".
											enquote_values( $s[$row_key], $sch )
											.");\n";
											
									} else {
										// change
										$changes = array();
										foreach( $sch as $column_name => $dummy) {
											if( ((! isset($r[$row_key][$column_name]) &&
												isset($s[$row_key][$column_name]) ) ||
												( isset($r[$row_key][$column_name]) &&
												isset($s[$row_key][$column_name]) &&
												$r[$row_key][$column_name] <> $s[$row_key][$column_name] ) &&
												! in_array(strtoupper($column_name), $columns_to_ignore))) {
												$changes[] = $column_name." = ".enquote_value( $s[$row_key], $column_name, $sch );
											}
										}
										
										if( count($changes) > 0 )
											$patch .= "UPDATE $static_table SET ".implode(', ', $changes).
												" WHERE $pk = ".enquote_value( $r[$row_key], $pk, $sch ).";\n";

									}
								}
								
							}
						}
						
					}
					
					
					//!ROUTINES
					$patch .= "\n-- Routines\n\n";
					$r = routine_names( $result['ROUTINES'] );
					$s = routine_names( $schema['ROUTINES'] );
					$rk = array_keys($r);
					$sk = array_keys($s);
					$combined_routines = array_unique(array_merge($rk, $sk));
					sort($combined_routines);
					
					foreach($combined_routines as $routine_name) {
						if( isset($r[$routine_name]) && ! isset($s[$routine_name]) ) {
							$patch .= "DROP ".$r[$routine_name]['ROUTINE_TYPE']." IF EXISTS ".$routine_name.";\n";
						} else if( ! isset($r[$routine_name]) && isset($s[$routine_name]) ) {
							$patch .= "DROP ".$s[$routine_name]['ROUTINE_TYPE']." IF EXISTS ".$routine_name.";\n";
							$patch .= "\nDELIMITER ;;\nCREATE";
							$definer = "`root`@`".$sts_user_host."`";
							$patch .= " DEFINER=".$definer;
							//! SCR# 511 add IS_DETERMINISTIC, SQL_DATA_ACCESS
							$patch .= " ".$s[$routine_name]['ROUTINE_TYPE'].
								" `".$s[$routine_name]['ROUTINE_NAME']."`".
								mysql8_int(routine_parameters( $routine_name, $schema['PARAMETERS']))."\n".
								($s[$routine_name]["IS_DETERMINISTIC"] == 'YES' ?
									'DETERMINISTIC' : 'NOT DETERMINISTIC')."\n".
								$s[$routine_name]["SQL_DATA_ACCESS"]."\n".
								mysql8_int($s[$routine_name]["ROUTINE_DEFINITION"])." ;;\nDELIMITER ;\n";
								
						} else if( isset($r[$routine_name]) && isset($s[$routine_name]) ) {
							
							$ra = mysql8_int( preg_replace('!\s+!', ' ', trim($r[$routine_name]["ROUTINE_DEFINITION"]) ) );
							$sa = mysql8_int( preg_replace('!\s+!', ' ', trim($s[$routine_name]["ROUTINE_DEFINITION"]) ) );
							//! Check DEFINER
							if( strcmp($ra, $sa) <> 0 ||
								$r[$routine_name]["IS_DETERMINISTIC"] <> $s[$routine_name]["IS_DETERMINISTIC"] ||
								$r[$routine_name]["SQL_DATA_ACCESS"] <> $s[$routine_name]["SQL_DATA_ACCESS"] ||
								$r[$routine_name]["DEFINER"] <> $sts_username.'@'.$sts_user_host) {
								if( $sts_debug ) {
									echo "<pre>!!ROUTINE\n";
									var_dump($routine_name, $r[$routine_name]["ROUTINE_DEFINITION"], $ra, $sa);
									var_dump($r[$routine_name]["IS_DETERMINISTIC"], $s[$routine_name]["IS_DETERMINISTIC"]);
									var_dump($r[$routine_name]["SQL_DATA_ACCESS"], $s[$routine_name]["SQL_DATA_ACCESS"]);
									echo "</pre>";
									
								}
								
								$patch .= "DROP ".$r[$routine_name]['ROUTINE_TYPE']." IF EXISTS ".$routine_name.";\n";
								$patch .= "\nDELIMITER ;;\nCREATE";
								$definer = "`".$sts_username."`@`".$sts_user_host."`";
								$patch .= " DEFINER=".$definer;
								//! SCR# 511 add IS_DETERMINISTIC, SQL_DATA_ACCESS
								$patch .= " ".$s[$routine_name]['ROUTINE_TYPE'].
									" `".$s[$routine_name]['ROUTINE_NAME']."`".
									mysql8_int(routine_parameters( $routine_name, $schema['PARAMETERS']))."\n".
									($s[$routine_name]["IS_DETERMINISTIC"] == 'YES' ?
										'DETERMINISTIC' : 'NOT DETERMINISTIC')."\n".
									$s[$routine_name]["SQL_DATA_ACCESS"]."\n".
									mysql8_int($s[$routine_name]["ROUTINE_DEFINITION"])." ;;\nDELIMITER ;\n";
							}
						}
					}
					
					//!TRIGGERS
					$patch .= "\n-- Triggers\n\n";
					$r = trigger_names( $result['TRIGGERS'] );
					$s = trigger_names( $schema['TRIGGERS'] );
					$rk = array_keys($r);
					$sk = array_keys($s);
					$combined_triggers = array_unique(array_merge($rk, $sk));
					sort($combined_triggers);
					
						foreach($combined_triggers as $trigger_name) {
						if( isset($r[$trigger_name]) && ! isset($s[$trigger_name]) ) {
							$patch .= "DROP TRIGGER IF EXISTS ".$trigger_name.";\n";

						} else if( ! isset($r[$trigger_name]) && isset($s[$trigger_name]) ) {
							$patch .= "DROP TRIGGER IF EXISTS ".$trigger_name.";\n";
							$patch .= "\nDELIMITER ;;\nCREATE";
							$definer = "`".$sts_username."`@`".$sts_user_host."`";
							$patch .= " DEFINER=".$definer;
							$patch .= " TRIGGER `".$trigger_name."`\n".
								$s[$trigger_name]["ACTION_TIMING"]." ".
								$s[$trigger_name]["EVENT_MANIPULATION"]." ON `".
								$s[$trigger_name]["EVENT_OBJECT_TABLE"]."`\n".
								"FOR EACH ".$s[$trigger_name]["ACTION_ORIENTATION"]."\n".
								$s[$trigger_name]["ACTION_STATEMENT"]." ;;\nDELIMITER ;\n";

						} else if( isset($r[$trigger_name]) && isset($s[$trigger_name]) ) {
							
							$ra = preg_replace('!\s+!', ' ', trim($r[$trigger_name]["ACTION_STATEMENT"]) );
							$sa = preg_replace('!\s+!', ' ', trim($s[$trigger_name]["ACTION_STATEMENT"]) );
							$rd = preg_replace('!\s+!', ' ', trim($r[$trigger_name]["DEFINER"]) );

							//! Check DEFINER
							if( $r[$trigger_name]["DEFINER"] <> $sts_username.'@'.$sts_user_host ) {
								
								$patch .= "DROP TRIGGER IF EXISTS ".$trigger_name.";\n";
								$patch .= "\nDELIMITER ;;\nCREATE";
								$definer = "`".$sts_username."`@`".$sts_user_host."`";
								$patch .= " DEFINER=".$definer;
								$patch .= " TRIGGER `".$trigger_name."`\n".
									$s[$trigger_name]["ACTION_TIMING"]." ".
									$s[$trigger_name]["EVENT_MANIPULATION"]." ON `".
									$s[$trigger_name]["EVENT_OBJECT_TABLE"]."`\n".
									"FOR EACH ".$s[$trigger_name]["ACTION_ORIENTATION"]."\n".
									$s[$trigger_name]["ACTION_STATEMENT"]." ;;\nDELIMITER ;\n\n";
							}
						}
					}
					
					//!VIEWS
					$patch .= "\n-- Views\n\n";
					$r = view_names( isset($result['VIEWS']) ? $result['VIEWS'] : false );
					$s = view_names( isset($schema['VIEWS']) ? $schema['VIEWS'] : false );
					$rk = array_keys($r);
					$sk = array_keys($s);
					$combined_views = array_unique(array_merge($rk, $sk));
					sort($combined_views);
					
						foreach($combined_views as $view_name) {
						if( isset($r[$view_name]) && ! isset($s[$view_name]) ) {
							$patch .= "DROP VIEW IF EXISTS ".$view_name.";\n";

						} else if( ! isset($r[$view_name]) && isset($s[$view_name]) ) {
							$patch .= "DROP VIEW IF EXISTS ".$view_name.";\n";
							$patch .= "\nCREATE\n ALGORITHM = UNDEFINED\n";
							$definer = "`".$sts_username."`@`".$sts_user_host."`";
							$patch .= " DEFINER=".$definer."\n SQL SECURITY DEFINER\n";
							$patch .= "VIEW `".$view_name."` AS\n".
								$s[$view_name]["VIEW_DEFINITION"].";\n\n";

						} else if( isset($r[$view_name]) && isset($s[$view_name]) ) {
							
							$ra = preg_replace('!\s+!', ' ', trim($r[$view_name]["VIEW_DEFINITION"]) );
							//! Fix for views issue
							$ra = str_replace( '`'.$sts_database.'`.', '', $ra);
							$sa = preg_replace('!\s+!', ' ', trim($s[$view_name]["VIEW_DEFINITION"]) );
							if( strcmp($ra, $sa) <> 0 ||
								$r[$view_name]["DEFINER"] <> $sts_username.'@'.$sts_user_host) {
								if( $sts_debug ) {
									echo "<pre>!!VIEW\n";
									var_dump($view_name, $ra, $sa);
									echo "</pre>";
									
								}
								$patch .= "DROP VIEW IF EXISTS ".$view_name.";\n";
								$patch .= "\nCREATE\n ALGORITHM = UNDEFINED\n";
								$definer = "`".$sts_username."`@`".$sts_user_host."`";
								$patch .= " DEFINER=".$definer."\n SQL SECURITY DEFINER\n";
								$patch .= "VIEW `".$view_name."` AS\n".
									$s[$view_name]["VIEW_DEFINITION"].";\n\n";
							}
						}
					}
					
					$patch .= "\n-- End of Patch\n\n";
					
					//<input type="hidden" name="debug" value="on">
					echo '<form role="form" action="exp_repair_db.php" 
				method="post" enctype="multipart/form-data" 
				name="dorepair" id="dorepair">
					<h4>Click to select &nbsp; OR &nbsp; <button class="btn btn-sm btn-danger" name="dopatch" type="submit" ><span class="glyphicon glyphicon-arrow-right"></span><span class="glyphicon glyphicon-hdd"></span> Apply Patch Directly</button> <a class="btn btn-sm btn-success" href="exp_repair_db.php?CHECK_SCHEMA"><span class="glyphicon glyphicon-refresh"></span></a></h4>
					<input type="hidden" name="debug" value="on">
					
					<textarea name="patches" rows="30" cols="100" onclick="this.focus();this.select()" spellcheck="false">'.$patch.'</textarea>
					</form>';
					
					die("<p>// End of changes</p>");

				} else
					die( "</p>CHECK_SCHEMA Error: ".$shipment_table->database->error()."</p>" );
			} else
				die( "</p>CHECK_SCHEMA: unable to parse file ".$sts_schema_directory.$sts_schema_release_name.$sts_schema_suffix."</p>" );
		} else
			die( "</p>CHECK_SCHEMA: file ".$sts_schema_directory.$sts_schema_release_name.$sts_schema_suffix." not found.</p>" );
	}

	//! SCR# 185 - log when we do a repair DB
	require_once( "include/sts_user_log_class.php" );
	$user_log_table = sts_user_log::getInstance($exspeedite_db, $sts_debug);
	$user_log_table->log_event('admin', 'Repair DB');

	if( isset($_GET['DEL_CANCELLED']) ) { //! DEL_CANCELLED
		$result = $shipment_table->delete_row("CURRENT_STATUS = (SELECT STATUS_CODES_CODE
			FROM EXP_STATUS_CODES WHERE SOURCE_TYPE = 'shipment' AND BEHAVIOR = 'cancel')" );
			
		if( ! $result ) die( "</p>DEL_CANCELLED Error: ".$shipment_table->database->error()."</p>" );
	}

	if( isset($_GET['DEL_EMPTY']) ) { //! DEL_EMPTY
		$empty = $load_table->fetch_rows("(SELECT COUNT(*) FROM EXP_SHIPMENT
			WHERE EXP_SHIPMENT.LOAD_CODE = EXP_LOAD.LOAD_CODE) = 0
			AND (SELECT COUNT(*) FROM EXP_STOP
			WHERE EXP_STOP.LOAD_CODE = EXP_LOAD.LOAD_CODE) = 0", "LOAD_CODE, CURRENT_STATUS", "LOAD_CODE ASC" );
			
		/*COALESCE(DRIVER,0) = 0
			AND COALESCE(TRACTOR,0) = 0
			AND COALESCE(TRAILER,0) = 0
			AND COALESCE(CARRIER,0) = 0
			AND */

		if( is_array($empty) && count($empty) > 0 ) {
			$details = array();
			foreach( $empty as $row ) {
				$details[] = $row['LOAD_CODE'];
			}
			$result = $load_table->delete_row( "LOAD_CODE IN ( ".implode(', ', $details)." )" );
			if( ! $result ) die( "</p>DEL_EMPTY Error: ".$detail_table->database->error()."</p>" );
		}

	}
	
	if( isset($_GET['DEL_EMPTY2']) ) { //! DEL_EMPTY2
		$empty = $load_table->fetch_rows("(SELECT COUNT(*) FROM EXP_SHIPMENT
			WHERE EXP_SHIPMENT.LOAD_CODE = EXP_LOAD.LOAD_CODE) = 0
			AND
			(SELECT COUNT(*) FROM EXP_SHIPMENT_LOAD
			WHERE EXP_SHIPMENT_LOAD.LOAD_CODE = EXP_LOAD.LOAD_CODE) = 0
			AND (SELECT COUNT(*) FROM EXP_STOP
			WHERE EXP_STOP.LOAD_CODE = EXP_LOAD.LOAD_CODE
			AND EXP_STOP.STOP_TYPE!='stop') > 0", 
			"LOAD_CODE, CURRENT_STATUS", "LOAD_CODE ASC" );
			
		if( is_array($empty) && count($empty) > 0 ) {
			$details = array();
			foreach( $empty as $row ) {
				$details[] = $row['LOAD_CODE'];
			}
			$result = $load_table->delete_row( "LOAD_CODE IN ( ".implode(', ', $details)." )" );
			if( ! $result ) die( "</p>DEL_EMPTY Error: ".$detail_table->database->error()."</p>" );
		}

	}
	
	if( isset($_GET['SHIPMENT_LINK']) ) { //! SHIPMENT_LINK
		$result = $shipment_table->update_row("LOAD_CODE > 0
			AND NOT EXISTS
			(SELECT EXP_LOAD.LOAD_CODE
			FROM EXP_LOAD
			WHERE EXP_LOAD.LOAD_CODE = EXP_SHIPMENT.LOAD_CODE)",
			array( array("field" => "LOAD_CODE", "value" => 0) ) );
		if( ! $result ) die( "</p>SHIPMENT_LINK Error: ".$detail_table->database->error()."</p>" );
	}

	if( isset($_GET['STOP_LINK']) ) { //! STOP_LINK
		$result = $stop_table->delete_row("LOAD_CODE > 0
			AND NOT EXISTS
			(SELECT EXP_LOAD.LOAD_CODE
			FROM EXP_LOAD
			WHERE EXP_LOAD.LOAD_CODE = EXP_STOP.LOAD_CODE)");
		if( ! $result ) die( "</p>STOP_LINK Error: ".$detail_table->database->error()."</p>" );
	}
		
	if( isset($_GET['STOP_CANCELLED']) ) { //! STOP_CANCELLED
		$result = $stop_table->delete_row("LOAD_CODE > 0
		AND (SELECT CURRENT_STATUS
		FROM EXP_LOAD 
		WHERE EXP_LOAD.LOAD_CODE = EXP_STOP.LOAD_CODE) = ".$load_table->behavior_state['cancel']);
		if( ! $result ) die( "</p>STOP_CANCELLED Error: ".$detail_table->database->error()."</p>" );
	}
	
	if( isset($_GET['LOAD_CANCELLED']) ) { //! LOAD_CANCELLED
		$result = $shipment_table->update_row("LOAD_CODE > 0
			AND (SELECT CURRENT_STATUS
			FROM EXP_LOAD 
			WHERE EXP_LOAD.LOAD_CODE = EXP_SHIPMENT.LOAD_CODE) = ".$load_table->behavior_state['cancel'],
			array( array("field" => "LOAD_CODE", "value" => 0) ) );
		if( ! $result ) die( "</p>LOAD_CANCELLED Error: ".$detail_table->database->error()."</p>" );
	}

	if( isset($_GET['LOAD_STOP_STATE']) ) { //! LOAD_STOP_STATE
		$load_stop_state = $load_table->database->get_multiple_rows(
			"SELECT LOAD_CODE, CURRENT_STOP, LOAD_STATUS, STOP_TYPE
			FROM (SELECT LOAD_CODE, CURRENT_STOP,
			(SELECT BEHAVIOR FROM EXP_STATUS_CODES
			WHERE CURRENT_STATUS = STATUS_CODES_CODE) AS LOAD_STATUS,
			(SELECT STOP_TYPE FROM EXP_STOP
			WHERE EXP_STOP.LOAD_CODE = EXP_LOAD.LOAD_CODE
			AND SEQUENCE_NO = CURRENT_STOP) AS STOP_TYPE
			FROM EXP_LOAD
			WHERE CURRENT_STATUS IN (".$load_table->behavior_state['arrive cons'].", ".
				$load_table->behavior_state['arrive shipper'].", ".
				$load_table->behavior_state['arrshdock'].", ".
				$load_table->behavior_state['arrrecdock'].", ".
				$load_table->behavior_state['arrive stop'].") ) X
			WHERE (LOAD_STATUS = 'arrive cons' AND STOP_TYPE <> 'drop')
			OR (LOAD_STATUS = 'arrive shipper' AND STOP_TYPE <> 'pick')
			OR (LOAD_STATUS = 'arrshdock' AND STOP_TYPE <> 'pickdock')
			OR (LOAD_STATUS = 'arrrecdock' AND STOP_TYPE <> 'dropdock')
			OR (LOAD_STATUS = 'arrive stop' AND STOP_TYPE <> 'stop')" );
			
		if( is_array($load_stop_state) && count($load_stop_state) > 0 ) {
			foreach( $load_stop_state as $row ) {
				if( $row['STOP_TYPE'] == 'pick' ) 
					$new_status = $load_table->behavior_state['arrive shipper'];
				else if( $row['STOP_TYPE'] == 'drop' ) 
					$new_status = $load_table->behavior_state['arrive cons'];
				else if( $row['STOP_TYPE'] == 'pickdock' ) 
					$new_status = $load_table->behavior_state['arrshdock'];
				else if( $row['STOP_TYPE'] == 'dropdock' ) 
					$new_status = $load_table->behavior_state['arrrecdock'];
				else if( $row['STOP_TYPE'] == 'stop' ) 
					$new_status = $load_table->behavior_state['arrive stop'];
				
				if( $new_status > 0 )
					$result = $load_table->update_row("LOAD_CODE = ".$row['LOAD_CODE'],
						array( array("field" => "CURRENT_STATUS", "value" => $new_status) ) );
				if( ! $result ) die( "</p>LOAD_STOP_STATE Error: ".$detail_table->database->error()."</p>" );
			}
		}

	}
	
	if( isset($_GET['STOP_DUP']) ) { //! STOP_DUP
		$stop_dup = $stop_table->database->get_multiple_rows(
			"SELECT S1.STOP_CODE, S1.LOAD_CODE, S1.SEQUENCE_NO, S1.SHIPMENT, S1.STOP_TYPE, S1.CURRENT_STATUS
				FROM EXP_STOP S1
				INNER JOIN EXP_STOP S2
					ON S1.LOAD_CODE = S2.LOAD_CODE
					AND S1.SEQUENCE_NO = S2.SEQUENCE_NO
					AND S1.STOP_CODE <> S2.STOP_CODE" );
		if( is_array($stop_dup) && count($stop_dup) > 0 ) {
			foreach( $stop_dup as $row ) {
				$stop_table->renumber( $row['LOAD_CODE'] );
				$load_table->fix_current_stop( $row['LOAD_CODE'] );
			}
		}
	}
		
	if( isset($_GET['STOP_NUM']) ) { //! STOP_NUM
		$stop_num = $stop_table->database->get_multiple_rows(
			"SELECT LOAD_CODE
			FROM (
			SELECT LOAD_CODE, MIN(SEQUENCE_NO) MYMIN, MAX(SEQUENCE_NO) MYMAX,
			(SELECT COUNT(*) AS NUM_STOPS
							FROM EXP_STOP
							WHERE MYSTOPS.LOAD_CODE = EXP_STOP.LOAD_CODE) AS NUM_STOPS
			FROM (SELECT DISTINCT SEQUENCE_NO, LOAD_CODE
			FROM EXP_STOP
			ORDER BY LOAD_CODE ASC, SEQUENCE_NO ASC) MYSTOPS
			GROUP BY LOAD_CODE) NUMBERS
			WHERE MYMIN > 1 OR MYMAX < NUM_STOPS" );
			
		if( isset($stop_num) && is_array($stop_num) && count($stop_num) > 0 ) {
			foreach( $stop_num as $row ) {
				$stop_table->renumber( $row['LOAD_CODE'] );
				$load_table->fix_current_stop( $row['LOAD_CODE'] );
			}
		}
	}
		
	if( isset($_GET['SHIPMENT_CANCELLED']) ) { //! SHIPMENT_CANCELLED
		$shipment_cancelled = $shipment_table->database->get_multiple_rows(
			"SELECT LOAD_CODE, SHIPMENT_CODE, CURRENT_STATUS
			FROM EXP_SHIPMENT
			WHERE LOAD_CODE > 0
			AND CURRENT_STATUS = (SELECT STATUS_CODES_CODE
			FROM EXP_STATUS_CODES WHERE SOURCE_TYPE = 'shipment' AND BEHAVIOR = 'cancel')" );
		if( isset($shipment_cancelled) && is_array($shipment_cancelled) && count($shipment_cancelled) > 0 ) {
			foreach( $shipment_cancelled as $row ) {
				// Remove this shipment from the load
				$result = $shipment_table->update_row( $shipment_table->primary_key.' = '.$row['SHIPMENT_CODE'], 
					array( array( "field" => 'LOAD_CODE', "value" => 0 ) ) );
				if( ! $result ) die( "</p>SHIPMENT_CANCELLED Error1: ".$detail_table->database->error()."</p>" );

				// Remove stops for the shipment
				$result = $stop_table->delete_row( "SHIPMENT = ".$row['SHIPMENT_CODE'] );
				if( ! $result ) die( "</p>SHIPMENT_CANCELLED Error2: ".$detail_table->database->error()."</p>" );

				// renumber stops in the load
				$dummy = $stop_table->renumber( $row['LOAD_CODE'] );
				// Fix current_stop
				$dummy = $load_table->fix_current_stop( $row['LOAD_CODE'] );
			}
		}
	}
		
	if( isset($_GET['SHIPMENT_DETAIL']) ) { //! SHIPMENT_DETAIL
		$shipment_detail = $shipment_table->database->get_multiple_rows(
			"SELECT DETAIL_CODE
			FROM EXP_DETAIL
			WHERE NOT EXISTS (SELECT SHIPMENT_CODE
			FROM EXP_SHIPMENT
			WHERE EXP_SHIPMENT.SHIPMENT_CODE = EXP_DETAIL.SHIPMENT_CODE)" );
	
		if( is_array($shipment_detail) && count($shipment_detail) > 0 ) {
			$details = array();
			foreach( $shipment_detail as $row ) {
				$details[] = $row['DETAIL_CODE'];
			}
			$result = $detail_table->delete_row( "DETAIL_CODE IN ( ".implode(', ', $details)." )" );
			if( ! $result ) die( "</p>SHIPMENT_DETAIL Error: ".$detail_table->database->error()."</p>" );
		}
	}

	if( isset($_GET['LOAD_COMPLETE']) ) { //! LOAD_COMPLETE
		$shipment_detail = $shipment_table->database->get_multiple_rows(
			"SELECT X.LOAD_CODE, SHIPMENT_CODE
			FROM (
			SELECT LOAD_CODE
						
					FROM EXP_LOAD
					WHERE LOAD_CODE > 0
					AND CURRENT_STATUS IN (SELECT STATUS_CODES_CODE
						FROM EXP_STATUS_CODES WHERE SOURCE_TYPE = 'load' AND
							BEHAVIOR IN ('complete', 'approved', 'billed'))
						AND (EXISTS (SELECT STOP_CODE
						FROM EXP_STOP
						WHERE EXP_STOP.LOAD_CODE = EXP_LOAD.LOAD_CODE
						AND CURRENT_STATUS = (SELECT STATUS_CODES_CODE
						FROM EXP_STATUS_CODES WHERE SOURCE = 'stop' AND BEHAVIOR <> 'complete'))
						
						OR EXISTS (SELECT SHIPMENT_CODE
						FROM EXP_SHIPMENT
						WHERE EXP_SHIPMENT.LOAD_CODE = EXP_LOAD.LOAD_CODE
						AND CURRENT_STATUS IN (SELECT STATUS_CODES_CODE
						FROM EXP_STATUS_CODES WHERE SOURCE_TYPE = 'shipment' AND
							BEHAVIOR NOT IN ('dropped', 'docked', 'approved', 'billed'))))
							) X
							
					LEFT JOIN EXP_SHIPMENT
					ON EXP_SHIPMENT.LOAD_CODE = X.LOAD_CODE" );
	
		if( is_array($shipment_detail) && count($shipment_detail) > 0 ) {
			$loads = array();
			$shipments = array();
			foreach( $shipment_detail as $row ) {
				if( isset($row['LOAD_CODE']) && $row['LOAD_CODE'] > 0 ) $loads[] = $row['LOAD_CODE'];
				if( isset($row['SHIPMENT_CODE']) && $row['SHIPMENT_CODE'] > 0 ) $shipments[] = $row['SHIPMENT_CODE'];
			}
			$result = $load_table->delete_row( "LOAD_CODE IN ( ".implode(', ', array_unique($loads))." )" );
			if( ! $result ) die( "</p>LOAD_COMPLETE Error1: ".$detail_table->database->error()."</p>" );
			$result = $shipment_table->delete_row( "SHIPMENT_CODE IN ( ".implode(', ', array_unique($shipments))." )" );
			if( ! $result ) die( "</p>LOAD_COMPLETE Error2: ".$detail_table->database->error()."</p>" );
		}
	}

	if( isset($_GET['BAD_STATUS']) ) { //! BAD_STATUS
	$bad_status = $status_table->database->get_multiple_rows(
		"SELECT STATUS_CODE, ORDER_CODE, SOURCE,
			(SELECT EXP_STATUS_CODES.STATUS
			FROM EXP_STATUS_CODES
			WHERE EXP_STATUS_CODES.STATUS_CODES_CODE = EXP_STATUS.STATUS_STATE) STATUS,
			COMMENTS
			FROM EXP_STATUS
			WHERE (SOURCE_TYPE = 'load'
			AND NOT EXISTS (SELECT LOAD_CODE FROM EXP_LOAD
			WHERE LOAD_CODE = ORDER_CODE))
			OR
			(SOURCE_TYPE = 'shipment'
			AND NOT EXISTS (SELECT SHIPMENT_CODE FROM EXP_SHIPMENT
			WHERE SHIPMENT_CODE = ORDER_CODE))" );

	if( is_array($bad_status) && count($bad_status) > 0 ) {
			$details = array();
			foreach( $bad_status as $row ) {
				$details[] = $row['STATUS_CODE'];
			}
			$result = $status_table->delete_row( "STATUS_CODE IN ( ".implode(', ', $details)." )" );
			if( ! $result ) die( "</p>BAD_STATUS Error: ".$status_table->database->error()."</p>" );
		}

	}
	
	if( isset($_GET['DISPATCHED_ZERO']) ) { //!DISPATCHED_ZERO
	$dispzero = $status_table->database->get_multiple_rows(
		"SELECT SHIPMENT_CODE, CURRENT_STATUS, LOAD_CODE
		FROM EXP_SHIPMENT
		WHERE CURRENT_STATUS = (SELECT STATUS_CODES_CODE
			FROM EXP_STATUS_CODES WHERE SOURCE_TYPE = 'shipment' AND BEHAVIOR = 'dispatch')
		AND LOAD_CODE = 0" );

	if( is_array($dispzero) && count($dispzero) > 0 ) {
			$details = array();
			foreach( $dispzero as $row ) {
				$remove_shipment = $row['SHIPMENT_CODE'];
				$check_docked = $shipment_load_table->last_load( $remove_shipment );
				if( isset($check_docked) && count($check_docked) > 0 &&
					isset($check_docked[0]["DOCKED_AT"]) && $check_docked[0]["DOCKED_AT"] > 0 ) {	// Docked
					$shipment_table->add_shipment_status( $remove_shipment, "repair_db: set LOAD_CODE=0 DOCKED_AT=".$check_docked[0]["DOCKED_AT"]." STATUS=".$shipment_table->behavior_state['docked'] );
					$result = $shipment_table->update_row( $shipment_table->primary_key.' = '.$remove_shipment,
						array(
							array( "field" => "LOAD_CODE", "value" => 0),
							array( "field" => "DOCKED_AT", "value" => $check_docked[0]["DOCKED_AT"]),
							array( "field" => "CURRENT_STATUS", "value" => $shipment_table->behavior_state['docked'])
						 ) );
					
				} else {	// Ready Dispatch
					$shipment_table->add_shipment_status( $remove_shipment, "repair_db: set LOAD_CODE=0 STATUS=".$shipment_table->behavior_state['assign'] );
					$result = $shipment_table->update_row( $shipment_table->primary_key.' = '.$remove_shipment,
						array(
							array( "field" => "LOAD_CODE", "value" => 0),
							array( "field" => "CURRENT_STATUS", "value" => $shipment_table->behavior_state['assign'])
						 ) );
				}
				if( ! $result ) die( "</p>DISPATCHED_ZERO Error: ".$shipment_table->error()."</p>" );
			}
		}

	}
	
	if( isset($_GET['SHIPMENT_DUP']) ) { //!SHIPMENT_DUP
		$bad_status = $shipment_table->database->get_multiple_rows(
			"select LOAD_CODE, SHIPMENT_CODE, CURRENT_STATUS, STOPS
			FROM(
			select m.LOAD_CODE, m.SHIPMENT_CODE, m.CURRENT_STATUS,
				(SELECT COUNT(*) FROM EXP_STOP s
								WHERE s.SHIPMENT = m.SHIPMENT_CODE
			                    and s.LOAD_CODE = m.LOAD_CODE ) AS STOPS
					from EXP_SHIPMENT m
					where m.LOAD_CODE > 0) x
			where x.STOPS != 2" );

		if( is_array($bad_status) && count($bad_status) > 0 ) {
			foreach( $bad_status as $row ) {
				$picks = $shipment_table->database->get_multiple_rows(
					"SELECT STOP_CODE, SEQUENCE_NO, STOP_TYPE
					FROM EXP_STOP
					WHERE LOAD_CODE = ".$row["LOAD_CODE"]."
					AND SHIPMENT = ".$row["SHIPMENT_CODE"]."
					AND STOP_TYPE IN ('pick','pickdock')
					ORDER BY SEQUENCE_NO ASC" );
				if( is_array($picks) && count($picks) > 1 ) {
					array_shift($picks); // skip first one
					foreach($picks as $row2) {
						$remove_stops[] = $row2["STOP_CODE"];
					}
					$result = $stop_table->delete_row( $stop_table->primary_key.' IN ('.
						implode(', ', $remove_stops).')' );
					//echo "<p>". $stop_table->primary_key.' IN ('.
					//	implode(', ', $remove_stops).')' ."</p>";
					$load_table->fix_current_stop( $row['LOAD_CODE'] );
				}

				$drops = $shipment_table->database->get_multiple_rows(
					"SELECT STOP_CODE, SEQUENCE_NO, STOP_TYPE
					FROM EXP_STOP
					WHERE LOAD_CODE = ".$row["LOAD_CODE"]."
					AND SHIPMENT = ".$row["SHIPMENT_CODE"]."
					AND STOP_TYPE IN ('drop','dropdock')
					ORDER BY SEQUENCE_NO ASC" );
				if( is_array($drops) && count($drops) > 1 ) {
					array_shift($drops); // skip first one
					foreach($drops as $row2) {
						$remove_stops[] = $row2["STOP_CODE"];
					}
					$result = $stop_table->delete_row( $stop_table->primary_key.' IN ('.
						implode(', ', $remove_stops).')' );
					//echo "<p>". $stop_table->primary_key.' IN ('.
					//	implode(', ', $remove_stops).')' ."</p>";
					$load_table->fix_current_stop( $row['LOAD_CODE'] );
				}
			}
		}
	}

	if( isset($_GET['SHIPMENT_RDY']) ) { //!SHIPMENT_RDY
		$bad_status = $shipment_table->database->get_multiple_rows(
			"SELECT S.SHIPMENT_CODE, S.CURRENT_STATUS AS SHIPMENT_STATUS, 
			S.LOAD_CODE, L.CURRENT_STATUS AS LOAD_STATUS,
            (SELECT CURRENT_STATUS
            FROM EXP_STOP ST
            WHERE ST.LOAD_CODE = S.LOAD_CODE
            AND ST.SHIPMENT = S.SHIPMENT_CODE
            AND ST.STOP_TYPE = 'pick') AS PICK_STATUS,
            (SELECT CURRENT_STATUS
            FROM EXP_STOP ST
            WHERE ST.LOAD_CODE = S.LOAD_CODE
            AND ST.SHIPMENT = S.SHIPMENT_CODE
            AND ST.STOP_TYPE = 'drop') AS DROP_STATUS
            
			FROM EXP_SHIPMENT S, EXP_LOAD L
			WHERE S.CURRENT_STATUS = (SELECT STATUS_CODES_CODE
				FROM EXP_STATUS_CODES WHERE SOURCE_TYPE = 'shipment' AND
				BEHAVIOR = 'assign')
			AND S.LOAD_CODE > 0
			AND S.LOAD_CODE = L.LOAD_CODE
			AND L.CURRENT_STATUS IN (SELECT STATUS_CODES_CODE
				FROM EXP_STATUS_CODES WHERE SOURCE_TYPE = 'load' AND
				BEHAVIOR IN ('dispatch', 'depart stop', 'depshdock',
					'depart shipper', 'deprecdock', 'depart cons', 'arrive stop', 'arrshdock',
					'arrive shipper', 'arrrecdock', 'arrive cons'))" );

		if( is_array($bad_status) && count($bad_status) > 0 ) {
			foreach( $bad_status as $row ) {
				if( $stop_table->state_behavior[$row['DROP_STATUS']] == 'complete')
					$behavior = 'dropped';
				else if( $stop_table->state_behavior[$row['PICK_STATUS']] == 'complete')
					$behavior = 'picked';
				else
					$behavior = 'dispatch';
				
				$result = $shipment_table->update( $row["SHIPMENT_CODE"], 
					array("CURRENT_STATUS" => $shipment_table->behavior_state[$behavior]), false );
				
			}
		}
	}

	//! PUT NEXT ERROR FIXER HERE

	echo '<h3>Check for empty load records (Not an error)</h3>';
	ob_flush(); flush();  sleep(1);
	$empty = $load_table->fetch_rows("(SELECT COUNT(*) FROM EXP_SHIPMENT
		WHERE EXP_SHIPMENT.LOAD_CODE = EXP_LOAD.LOAD_CODE) = 0
		AND (SELECT COUNT(*) FROM EXP_STOP
		WHERE EXP_STOP.LOAD_CODE = EXP_LOAD.LOAD_CODE) = 0", "LOAD_CODE, CURRENT_STATUS", "LOAD_CODE ASC" );
		
	/*COALESCE(DRIVER,0) = 0
		AND COALESCE(TRACTOR,0) = 0
		AND COALESCE(TRAILER,0) = 0
		AND COALESCE(CARRIER,0) = 0
		AND */

	if( is_array($empty) && count($empty) > 0 ) {
		$warnings_found++;
		echo '<h3>Found '.count($empty).' empty load records: <a class="btn btn-md btn-warning"  href="exp_repair_db.php?DEL_EMPTY"><span class="glyphicon glyphicon-trash"></span> Delete</a></h3>';
		echo '<table class="display table table-striped table-condensed table-bordered table-hover">
		<thead>
		<tr class="exspeedite-bg"><th>Load#</th><th>Status</th></tr>
		</thead>
		<tbody>';
		foreach( $empty as $row ) {
			echo '<tr><td>'.vl($row['LOAD_CODE']).'</td><td>'.$load_table->state_name[$row['CURRENT_STATUS']].'</td></tr>';
		}
		echo '</tbody>
		</table>';
	}
	
	echo '<h3>Check for cancelled shipments (Not an error)</h3>';
	ob_flush(); flush();  sleep(1);
	$cancelled = $shipment_table->fetch_rows("CURRENT_STATUS = (SELECT STATUS_CODES_CODE
			FROM EXP_STATUS_CODES WHERE SOURCE_TYPE = 'shipment' AND BEHAVIOR = 'cancel')",
			"SHIPMENT_CODE, CURRENT_STATUS", "SHIPMENT_CODE ASC" );
		
	if( is_array($cancelled) && count($cancelled) > 0 ) {
		$warnings_found++;
		echo '<h3>Found '.count($cancelled).' cancelled shipments: <a class="btn btn-md btn-warning"  href="exp_repair_db.php?DEL_CANCELLED"><span class="glyphicon glyphicon-trash"></span> Delete</a></h3>';
		echo '<table class="display table table-striped table-condensed table-bordered table-hover">
		<thead>
		<tr class="exspeedite-bg"><th>Shipment#</th><th>Status</th></tr>
		</thead>
		<tbody>';
		foreach( $cancelled as $row ) {
			echo '<tr><td>'.vs($row['SHIPMENT_CODE']).'</td><td>'.$shipment_table->state_name[$row['CURRENT_STATUS']].'</td></tr>';
		}
		echo '</tbody>
		</table>';
	}
	
	echo '<h3>Check for load records with stops, but no shipments</h3>';
	ob_flush(); flush();  sleep(1);
	$empty = $load_table->fetch_rows("(SELECT COUNT(*) FROM EXP_SHIPMENT
		WHERE EXP_SHIPMENT.LOAD_CODE = EXP_LOAD.LOAD_CODE) = 0
		AND
		(SELECT COUNT(*) FROM EXP_SHIPMENT_LOAD
		WHERE EXP_SHIPMENT_LOAD.LOAD_CODE = EXP_LOAD.LOAD_CODE) = 0
		AND (SELECT COUNT(*) FROM EXP_STOP
		WHERE EXP_STOP.LOAD_CODE = EXP_LOAD.LOAD_CODE
		AND EXP_STOP.STOP_TYPE!='stop') > 0",
		"LOAD_CODE, CURRENT_STATUS", "LOAD_CODE ASC" );
		
	if( is_array($empty) && count($empty) > 0 ) {
		$errors_found++;
		echo '<h3>Found '.count($empty).' load records with stops, but no shipments: <a class="btn btn-md btn-danger"  href="exp_repair_db.php?DEL_EMPTY2"><span class="glyphicon glyphicon-trash"></span> Delete</a></h3>';
		echo '<table class="display table table-striped table-condensed table-bordered table-hover">
		<thead>
		<tr class="exspeedite-bg"><th>Load#</th><th>Status</th></tr>
		</thead>
		<tbody>';
		foreach( $empty as $row ) {
			echo '<tr><td>'.vl($row['LOAD_CODE']).'</td><td>'.$load_table->state_name[$row['CURRENT_STATUS']].'</td></tr>';
		}
		echo '</tbody>
		</table>';
	}
	
	echo '<h3>Check for shipment records linking to invalid loads</h3>';	
	ob_flush(); flush();  sleep(1);
	$shipment_link = $shipment_table->fetch_rows("LOAD_CODE > 0
		AND NOT EXISTS
		(SELECT EXP_LOAD.LOAD_CODE
		FROM EXP_LOAD
		WHERE EXP_LOAD.LOAD_CODE = EXP_SHIPMENT.LOAD_CODE)", "SHIPMENT_CODE, CURRENT_STATUS, LOAD_CODE", "SHIPMENT_CODE ASC" );

	if( is_array($shipment_link) && count($shipment_link) > 0 ) {
		$errors_found++;
		echo '<h3>Found '.count($shipment_link).' shipment records point to invalid loads: <a class="btn btn-md btn-danger"  href="exp_repair_db.php?SHIPMENT_LINK"><span class="glyphicon glyphicon-wrench"></span> Fix</a></h3>';
		echo '<table class="display table table-striped table-condensed table-bordered table-hover">
		<thead>
		<tr class="exspeedite-bg"><th>Shipment#</th><th>Status</th><th>Load#</th></tr>
		</thead>
		<tbody>';
		foreach( $shipment_link as $row ) {
			echo '<tr><td>'.vs($row['SHIPMENT_CODE']).'</td><td>'.$shipment_table->state_name[$row['CURRENT_STATUS']].'</td><td>'.vl($row['LOAD_CODE']).'</td></tr>';
		}
		echo '</tbody>
		</table>';
	}

	echo '<h3>Check for stop records linking to invalid loads</h3>';	
	ob_flush(); flush();  sleep(1);
	$stop_link = $stop_table->fetch_rows("LOAD_CODE > 0
		AND NOT EXISTS
		(SELECT EXP_LOAD.LOAD_CODE
		FROM EXP_LOAD
		WHERE EXP_LOAD.LOAD_CODE = EXP_STOP.LOAD_CODE)",
		"STOP_CODE, STOP_TYPE, SHIPMENT, CURRENT_STATUS, LOAD_CODE", "STOP_CODE ASC" );

	if( is_array($stop_link) && count($stop_link) > 0 ) {
		$errors_found++;
		echo '<h3>Found '.count($stop_link).' stop records point to invalid loads: <a class="btn btn-md btn-danger"  href="exp_repair_db.php?STOP_LINK"><span class="glyphicon glyphicon-wrench"></span> Fix</a></h3>';
		echo '<table class="display table table-striped table-condensed table-bordered table-hover">
		<thead>
		<tr class="exspeedite-bg"><th>Stop#</th><th>Type</th><th>Shipment#</th><th>Status</th><th>Load#</th></tr>
		</thead>
		<tbody>';
		foreach( $stop_link as $row ) {
			echo '<tr><td>'.$row['STOP_CODE'].'</td><td>'.$row['STOP_TYPE'].'</td><td>'.vs($row['SHIPMENT']).'</td><td>'.$stop_table->state_name[$row['CURRENT_STATUS']].'</td><td>'.vl($row['LOAD_CODE']).'</td></tr>';
		}
		echo '</tbody>
		</table>';
	}

	echo '<h3>Check for stop records linking to cancelled loads</h3>';	
	ob_flush(); flush();  sleep(1);
	$stop_cancelled = $stop_table->fetch_rows("LOAD_CODE > 0
		AND (SELECT CURRENT_STATUS
		FROM EXP_LOAD 
		WHERE EXP_LOAD.LOAD_CODE = EXP_STOP.LOAD_CODE) = ".$load_table->behavior_state['cancel'],
		"STOP_CODE, STOP_TYPE, SHIPMENT, CURRENT_STATUS, LOAD_CODE", "STOP_CODE ASC" );

	if( is_array($stop_cancelled) && count($stop_cancelled) > 0 ) {
		$errors_found++;
		echo '<h3>Found '.count($stop_cancelled).' stop records point to loads that are cancelled: <a class="btn btn-md btn-danger"  href="exp_repair_db.php?STOP_CANCELLED"><span class="glyphicon glyphicon-wrench"></span> Fix</a></h3>';
		echo '<table class="display table table-striped table-condensed table-bordered table-hover">
		<thead>
		<tr class="exspeedite-bg"><th>Stop#</th><th>Type</th><th>Shipment#</th><th>Status</th><th>Load#</th></tr>
		</thead>
		<tbody>';
		foreach( $stop_cancelled as $row ) {
			echo '<tr><td>'.$row['STOP_CODE'].'</td><td>'.$row['STOP_TYPE'].'</td><td>'.vs($row['SHIPMENT']).'</td><td>'.$stop_table->state_name[$row['CURRENT_STATUS']].'</td><td>'.vl($row['LOAD_CODE']).'</td></tr>';
		}
		echo '</tbody>
		</table>';
	}

	echo '<h3>Check for shipment records linking to cancelled loads</h3>';	
	ob_flush(); flush();  sleep(1);
	$shipment_cancelled = $shipment_table->fetch_rows("LOAD_CODE > 0
		AND (SELECT CURRENT_STATUS
		FROM EXP_LOAD 
		WHERE EXP_LOAD.LOAD_CODE = EXP_SHIPMENT.LOAD_CODE) = ".$load_table->behavior_state['cancel'],
		"SHIPMENT_CODE, CURRENT_STATUS, LOAD_CODE", "SHIPMENT_CODE ASC" );

	if( is_array($shipment_cancelled) && count($shipment_cancelled) > 0 ) {
		$errors_found++;
		echo '<h3>Found '.count($shipment_cancelled).' shipment records point to loads that are cancelled: <a class="btn btn-md btn-danger"  href="exp_repair_db.php?LOAD_CANCELLED"><span class="glyphicon glyphicon-wrench"></span> Fix</a></h3>';
		echo '<table class="display table table-striped table-condensed table-bordered table-hover">
		<thead>
		<tr class="exspeedite-bg"><th>Shipment#</th><th>Status</th><th>Load#</th></tr>
		</thead>
		<tbody>';
		foreach( $shipment_cancelled as $row ) {
			echo '<tr><td>'.vs($row['SHIPMENT_CODE']).'</td>
			<td>'.$shipment_table->state_name[$row['CURRENT_STATUS']].'</td>
			<td>'.vl($row['LOAD_CODE']).'</td></tr>';
		}
		echo '</tbody>
		</table>';
	}

	echo '<h3>Check for loads in the wrong state</h3>';	
	ob_flush(); flush();  sleep(1);
	$load_stop_state = $load_table->database->get_multiple_rows(
		"SELECT LOAD_CODE, CURRENT_STOP, LOAD_STATUS, STOP_TYPE
		FROM (SELECT LOAD_CODE, CURRENT_STOP,
		(SELECT BEHAVIOR FROM EXP_STATUS_CODES
		WHERE CURRENT_STATUS = STATUS_CODES_CODE) AS LOAD_STATUS,
		(SELECT STOP_TYPE FROM EXP_STOP
		WHERE EXP_STOP.LOAD_CODE = EXP_LOAD.LOAD_CODE
		AND SEQUENCE_NO = CURRENT_STOP) AS STOP_TYPE
		FROM EXP_LOAD
		WHERE CURRENT_STATUS IN (".$load_table->behavior_state['arrive cons'].", ".
			$load_table->behavior_state['arrive shipper'].", ".
			$load_table->behavior_state['arrshdock'].", ".
			$load_table->behavior_state['arrrecdock'].", ".
			$load_table->behavior_state['arrive stop'].") ) X
		WHERE (LOAD_STATUS = 'arrive cons' AND STOP_TYPE <> 'drop')
		OR (LOAD_STATUS = 'arrive shipper' AND STOP_TYPE <> 'pick')
		OR (LOAD_STATUS = 'arrshdock' AND STOP_TYPE <> 'pickdock')
		OR (LOAD_STATUS = 'arrrecdock' AND STOP_TYPE <> 'dropdock')
		OR (LOAD_STATUS = 'arrive stop' AND STOP_TYPE <> 'stop')" );

	if( is_array($load_stop_state) && count($load_stop_state) > 0 ) {
		$errors_found++;
		echo '<h3>Found '.count($load_stop_state).' loads are in the wrong state: <a class="btn btn-md btn-danger"  href="exp_repair_db.php?LOAD_STOP_STATE"><span class="glyphicon glyphicon-wrench"></span> Fix</a></h3>';
		echo '<table class="display table table-striped table-condensed table-bordered table-hover">
		<thead>
		<tr class="exspeedite-bg"><th>Load#</th><th>Stop#</th><th>Status</th><th>Stop Type</th></tr>
		</thead>
		<tbody>';
		foreach( $load_stop_state as $row ) {
			echo '<tr><td>'.vl($row['LOAD_CODE']).'</td><td>'.$row['CURRENT_STOP'].'</td><td>'.$row['LOAD_STATUS'].'</td><td>'.$row['STOP_TYPE'].'</td></tr>';
		}
		echo '</tbody>
		</table>';
	}

	echo '<h3>Check for stops with the wrong state</h3>';	
	ob_flush(); flush();  sleep(1);
	$stop_status = $stop_table->database->get_multiple_rows(
		"SELECT STOP_CODE, STOP_TYPE, LOAD_CODE, SHIPMENT, CURRENT_STATUS,
		(SELECT STATUS_STATE
		FROM EXP_STATUS_CODES
		WHERE STATUS_CODES_CODE = CURRENT_STATUS) AS STATUS_STATE,
		(SELECT SOURCE_TYPE
		FROM EXP_STATUS_CODES
		WHERE STATUS_CODES_CODE = CURRENT_STATUS) AS SOURCE_TYPE
		FROM EXP_STOP
		WHERE (SELECT SOURCE_TYPE
		FROM EXP_STATUS_CODES
		WHERE STATUS_CODES_CODE = CURRENT_STATUS) <> 'stop'" );

	if( is_array($stop_status) && count($stop_status) > 0 ) {
		$errors_found++;
		echo '<h3>Found '.count($stop_status).' stops have the wrong state: <a class="btn btn-md btn-danger"  href="exp_repair_db.php?STOP_STATUS" disabled><span class="glyphicon glyphicon-wrench"></span> Unsure how to fix</a></h3>';
		echo '<table class="display table table-striped table-condensed table-bordered table-hover">
		<thead>
		<tr class="exspeedite-bg"><th>Stop#</th><th>Stop Type</th><th>Load#</th><th>Shipment#</th><th>Status</th><th>Status</th><th>Source</th></tr>
		</thead>
		<tbody>';
		foreach( $stop_status as $row ) {
			echo '<tr><td>'.$row['STOP_CODE'].'</td><td>'.$row['STOP_TYPE'].'</td><td>'.vl($row['LOAD_CODE']).'</td><td>'.vs($row['SHIPMENT']).'</td><td>'.$row['CURRENT_STATUS'].'</td><td>'.$row['STATUS_STATE'].'</td><td>'.$row['SOURCE_TYPE'].'</td></tr>';
		}
		echo '</tbody>
		</table>';
	}

	echo '<h3>Check for loads with the wrong state</h3>';	
	ob_flush(); flush();  sleep(1);
	$load_status = $load_table->database->get_multiple_rows(
		"SELECT LOAD_CODE, CURRENT_STATUS,
		(SELECT STATUS_STATE
		FROM EXP_STATUS_CODES
		WHERE STATUS_CODES_CODE = CURRENT_STATUS) AS STATUS_STATE,
		(SELECT SOURCE_TYPE
		FROM EXP_STATUS_CODES
		WHERE STATUS_CODES_CODE = CURRENT_STATUS) AS SOURCE_TYPE
		FROM EXP_LOAD
		WHERE (SELECT SOURCE_TYPE
		FROM EXP_STATUS_CODES
		WHERE STATUS_CODES_CODE = CURRENT_STATUS) <> 'load'" );

	if( is_array($load_status) && count($load_status) > 0 ) {
		$errors_found++;
		echo '<h3>Found '.count($load_status).' loads have the wrong state: <a class="btn btn-md btn-danger"  href="exp_repair_db.php?LOAD_STATUS" disabled><span class="glyphicon glyphicon-wrench"></span> Unsure how to fix</a></h3>';
		echo '<table class="display table table-striped table-condensed table-bordered table-hover">
		<thead>
		<tr class="exspeedite-bg"><th>Load#</th><th>Status</th><th>Status</th><th>Source</th></tr>
		</thead>
		<tbody>';
		foreach( $load_status as $row ) {
			echo '<tr><td>'.vl($row['LOAD_CODE']).'</td><td>'.$row['CURRENT_STATUS'].'</td><td>'.$row['STATUS_STATE'].'</td><td>'.$row['SOURCE_TYPE'].'</td></tr>';
		}
		echo '</tbody>
		</table>';
	}

	echo '<h3>Check for shipments with the wrong state</h3>';	
	ob_flush(); flush();  sleep(1);
	$shipment_status = $shipment_table->database->get_multiple_rows(
		"SELECT SHIPMENT_CODE, CURRENT_STATUS,
		(SELECT STATUS_STATE
		FROM EXP_STATUS_CODES
		WHERE STATUS_CODES_CODE = CURRENT_STATUS) AS STATUS_STATE,
		(SELECT SOURCE_TYPE
		FROM EXP_STATUS_CODES
		WHERE STATUS_CODES_CODE = CURRENT_STATUS) AS SOURCE_TYPE
		FROM EXP_SHIPMENT
		WHERE (SELECT SOURCE_TYPE
		FROM EXP_STATUS_CODES
		WHERE STATUS_CODES_CODE = CURRENT_STATUS) <> 'shipment'" );

	if( is_array($shipment_status) && count($shipment_status) > 0 ) {
		$errors_found++;
		echo '<h3>Found '.count($shipment_status).' shipments have the wrong state: <a class="btn btn-md btn-danger"  href="exp_repair_db.php?SHIPMENT_STATUS" disabled><span class="glyphicon glyphicon-wrench"></span> Unsure how to fix</a></h3>';
		echo '<table class="display table table-striped table-condensed table-bordered table-hover">
		<thead>
		<tr class="exspeedite-bg"><th>Shipment#</th><th>Status</th><th>Status</th><th>Source</th></tr>
		</thead>
		<tbody>';
		foreach( $shipment_status as $row ) {
			echo '<tr><td>'.vs($row['SHIPMENT_CODE']).'</td><td>'.$row['CURRENT_STATUS'].'</td><td>'.$row['STATUS_STATE'].'</td><td>'.$row['SOURCE_TYPE'].'</td></tr>';
		}
		echo '</tbody>
		</table>';
	}

	echo '<h3>Check for loads with cancelled shipments</h3>';	
	ob_flush(); flush();  sleep(1);
	$shipment_cancelled = $shipment_table->database->get_multiple_rows(
		"SELECT LOAD_CODE, SHIPMENT_CODE, CURRENT_STATUS
		FROM EXP_SHIPMENT
		WHERE LOAD_CODE > 0
		AND CURRENT_STATUS = (SELECT STATUS_CODES_CODE
		FROM EXP_STATUS_CODES WHERE SOURCE_TYPE = 'shipment' AND BEHAVIOR = 'cancel')" );

	if( is_array($shipment_cancelled) && count($shipment_cancelled) > 0 ) {
		$errors_found++;
		echo '<h3>Found '.count($shipment_cancelled).' shipments are cancelled but still in a load: <a class="btn btn-md btn-danger"  href="exp_repair_db.php?SHIPMENT_CANCELLED"><span class="glyphicon glyphicon-wrench"></span> Fix</a></h3>';
		echo '<table class="display table table-striped table-condensed table-bordered table-hover">
		<thead>
		<tr class="exspeedite-bg"><th>Load#</th><th>Shipment#</th><th>Status</th></tr>
		</thead>
		<tbody>';
		foreach( $shipment_cancelled as $row ) {
			echo '<tr><td>'.vl($row['LOAD_CODE']).'</td><td>'.vs($row['SHIPMENT_CODE']).'</td><td>'.$row['CURRENT_STATUS'].'</td></tr>';
		}
		echo '</tbody>
		</table>';
	}

	echo '<h3>Check for loads with the wrong current_stop</h3>';	
	ob_flush(); flush();  sleep(1);
	$load_cs = $load_table->database->get_multiple_rows(
		"SELECT DISTINCT LOAD_CODE
		FROM EXP_LOAD
		ORDER BY LOAD_CODE ASC" );
	if( is_array($load_cs) && count($load_cs) > 0 ) {
		foreach( $load_cs as $row ) {
			if( $load_table->fix_current_stop( $row['LOAD_CODE'] ) ) {
				$errors_found++;
				echo '<h3>Found &amp; Fixed current_stop for load #'.vl($row['LOAD_CODE']).'</h3>';
			}
		}
	}

	echo '<h3>Check for stops with duplicate sequence numbers for the same load</h3>';	
	ob_flush(); flush();  sleep(1);
	$stop_dup = $stop_table->database->get_multiple_rows(
		"SELECT S1.STOP_CODE, S1.LOAD_CODE, S1.SEQUENCE_NO, S1.SHIPMENT, S1.STOP_TYPE, S1.CURRENT_STATUS
			FROM EXP_STOP S1
			INNER JOIN EXP_STOP S2
				ON S1.LOAD_CODE = S2.LOAD_CODE
				AND S1.SEQUENCE_NO = S2.SEQUENCE_NO
				AND S1.STOP_CODE <> S2.STOP_CODE" );

	if( is_array($stop_dup) && count($stop_dup) > 0 ) {
		$errors_found++;
		echo '<h3>Found '.count($stop_dup).' stops with duplicate sequence numbers: <a class="btn btn-md btn-danger"  href="exp_repair_db.php?STOP_DUP"><span class="glyphicon glyphicon-wrench"></span> Fix</a></h3>';
		echo '<table class="display table table-striped table-condensed table-bordered table-hover">
		<thead>
		<tr class="exspeedite-bg"><th>Stop#</th><th>Stop Type</th><th>Load#</th><th>Shipment#</th><th>Status</th></tr>
		</thead>
		<tbody>';
		foreach( $stop_dup as $row ) {
			echo '<tr><td>'.$row['STOP_CODE'].'</td><td>'.$row['STOP_TYPE'].'</td><td>'.vl($row['LOAD_CODE']).'</td><td>'.vs($row['SHIPMENT']).'</td><td>'.$stop_table->state_name[$row['CURRENT_STATUS']].'</td></tr>';
		}
		echo '</tbody>
		</table>';
	}

	echo '<h3>Check for loads with stops out of sequence</h3>';	
	ob_flush(); flush();  sleep(1);
	$stop_num = $stop_table->database->get_multiple_rows(
		"SELECT LOAD_CODE, MYMIN, MYMAX, NUM_STOPS
			FROM (
			SELECT LOAD_CODE, MIN(SEQUENCE_NO) MYMIN, MAX(SEQUENCE_NO) MYMAX,
			(SELECT COUNT(*) AS NUM_STOPS
							FROM EXP_STOP
							WHERE MYSTOPS.LOAD_CODE = EXP_STOP.LOAD_CODE) AS NUM_STOPS
			FROM (SELECT DISTINCT SEQUENCE_NO, LOAD_CODE
			FROM EXP_STOP
			ORDER BY LOAD_CODE ASC, SEQUENCE_NO ASC) MYSTOPS
			GROUP BY LOAD_CODE) NUMBERS
			WHERE MYMIN > 1 OR MYMAX < NUM_STOPS" );

	if( isset($stop_num) && is_array($stop_num) && count($stop_num) > 0 ) {
		$errors_found++;
		echo '<h3>Found '.count($stop_num).' loads with stops out of sequence: <a class="btn btn-md btn-danger"  href="exp_repair_db.php?STOP_NUM"><span class="glyphicon glyphicon-wrench"></span> Fix</a></h3>';
		echo '<table class="display table table-striped table-condensed table-bordered table-hover">
		<thead>
		<tr class="exspeedite-bg"><th>Load#</th><th>Min Seq#</th><th>Max Seq#</th><th>#Stops</th></tr>
		</thead>
		<tbody>';
		foreach( $stop_num as $row ) {
			echo '<tr><td>'.vl($row['LOAD_CODE']).'</td><td>'.$row['MYMIN'].'</td><td>'.$row['MYMAX'].'</td><td>'.$row['NUM_STOPS'].'</td></tr>';
		}
		echo '</tbody>
		</table>';
	}

	echo '<h3>Check for completed loads with incomplete stops or shipments</h3>';	
	ob_flush(); flush();  sleep(1);
	$load_complete = $load_table->database->get_multiple_rows(
		"SELECT LOAD_CODE, CURRENT_STATUS,
			(SELECT COUNT(*)
			FROM EXP_STOP
			WHERE EXP_STOP.LOAD_CODE = EXP_LOAD.LOAD_CODE
			AND CURRENT_STATUS = (SELECT STATUS_CODES_CODE
			FROM EXP_STATUS_CODES WHERE SOURCE_TYPE = 'stop' AND BEHAVIOR <> 'complete')) STOPS_IN_ERROR,
			(SELECT COUNT(*)
			FROM EXP_SHIPMENT
			WHERE EXP_SHIPMENT.LOAD_CODE = EXP_LOAD.LOAD_CODE
			AND CURRENT_STATUS IN (SELECT STATUS_CODES_CODE
			FROM EXP_STATUS_CODES WHERE SOURCE_TYPE = 'shipment' AND 
				BEHAVIOR NOT IN ('dropped', 'docked', 'approved', 'billed'))) SHIPMENTS_IN_ERROR
			
		FROM EXP_LOAD
		WHERE LOAD_CODE > 0
		AND CURRENT_STATUS IN (SELECT STATUS_CODES_CODE
			FROM EXP_STATUS_CODES WHERE SOURCE_TYPE = 'load' AND
				BEHAVIOR IN ('complete','approved', 'billed'))
			AND (EXISTS (SELECT STOP_CODE
			FROM EXP_STOP
			WHERE EXP_STOP.LOAD_CODE = EXP_LOAD.LOAD_CODE
			AND CURRENT_STATUS = (SELECT STATUS_CODES_CODE
			FROM EXP_STATUS_CODES WHERE SOURCE_TYPE = 'stop' AND BEHAVIOR <> 'complete'))
			
			OR EXISTS (SELECT SHIPMENT_CODE
			FROM EXP_SHIPMENT
			WHERE EXP_SHIPMENT.LOAD_CODE = EXP_LOAD.LOAD_CODE
			AND CURRENT_STATUS IN (SELECT STATUS_CODES_CODE
			FROM EXP_STATUS_CODES WHERE SOURCE_TYPE = 'shipment' AND
				BEHAVIOR NOT IN ('dropped', 'docked', 'approved', 'billed'))))" );

	if( is_array($load_complete) && count($load_complete) > 0 ) {
		$errors_found++;
		echo '<h3>Found '.count($load_complete).' loads with incomplete stops or shipments: <a class="btn btn-md btn-danger"  href="exp_repair_db.php?LOAD_COMPLETE"><span class="glyphicon glyphicon-wrench"></span> Fix (delete all)</a></h3>
		<p>For a non-destructive fix, click on each load, back up the load and step forwards.</p>';
		echo '<table class="display table table-striped table-condensed table-bordered table-hover">
		<thead>
		<tr class="exspeedite-bg"><th>Load#</th><th>Status</th><th>#Stops</th><th>#Shipments</th></tr>
		</thead>
		<tbody>';
		foreach( $load_complete as $row ) {
			echo '<tr><td>'.vl($row['LOAD_CODE']).'</td><td>'.$row['CURRENT_STATUS'].'</td><td>'.$row['STOPS_IN_ERROR'].'</td><td>'.$row['SHIPMENTS_IN_ERROR'].'</td></tr>';
		}
		echo '</tbody>
		</table>';
	}

	echo '<h3>Check for detail lines with non-existant shipments</h3>';	
	ob_flush(); flush();  sleep(1);
	$shipment_detail = $shipment_table->database->get_multiple_rows(
		"SELECT DETAIL_CODE, SHIPMENT_CODE
		FROM EXP_DETAIL
		WHERE NOT EXISTS (SELECT SHIPMENT_CODE
		FROM EXP_SHIPMENT
		WHERE EXP_SHIPMENT.SHIPMENT_CODE = EXP_DETAIL.SHIPMENT_CODE)" );

	if( is_array($shipment_detail) && count($shipment_detail) > 0 ) {
		$errors_found++;
		echo '<h3>Found '.count($shipment_detail).' detail lines with non-existant shipments: <a class="btn btn-md btn-danger"  href="exp_repair_db.php?SHIPMENT_DETAIL"><span class="glyphicon glyphicon-wrench"></span> Fix</a></h3>';
		echo '<table class="display table table-striped table-condensed table-bordered table-hover">
		<thead>
		<tr class="exspeedite-bg"><th>Detail#</th><th>Shipment#</th></tr>
		</thead>
		<tbody>';
		foreach( $shipment_detail as $row ) {
			echo '<tr><td>'.$row['DETAIL_CODE'].'</td><td>'.vs($row['SHIPMENT_CODE']).'</td></tr>';
		}
		echo '</tbody>
		</table>';
	}

	echo '<h3>Check for Status History with non-existant loads/shipments</h3>';	
	ob_flush(); flush();  sleep(1);
	$bad_status = $status_table->database->get_multiple_rows(
		"SELECT STATUS_CODE, ORDER_CODE, SOURCE_TYPE,
			(SELECT EXP_STATUS_CODES.STATUS_STATE
			FROM EXP_STATUS_CODES
			WHERE EXP_STATUS_CODES.STATUS_CODES_CODE = EXP_STATUS.STATUS_STATE) STATUS_STATE,
			COMMENTS
			FROM EXP_STATUS
			WHERE (SOURCE_TYPE = 'load'
			AND NOT EXISTS (SELECT LOAD_CODE FROM EXP_LOAD
			WHERE LOAD_CODE = ORDER_CODE))
			OR
			(SOURCE_TYPE = 'shipment'
			AND NOT EXISTS (SELECT SHIPMENT_CODE FROM EXP_SHIPMENT
			WHERE SHIPMENT_CODE = ORDER_CODE))" );

	if( is_array($bad_status) && count($bad_status) > 0 ) {
		$errors_found++;
		echo '<h3>Found '.count($bad_status).' Status History with non-existant loads/shipments: <a class="btn btn-md btn-danger"  href="exp_repair_db.php?BAD_STATUS"><span class="glyphicon glyphicon-wrench"></span> Fix</a></h3>';
		echo '<table class="display table table-striped table-condensed table-bordered table-hover">
		<thead>
		<tr class="exspeedite-bg"><th>History#</th><th>Load/Shipment#</th><th>Type</th><th>Status</th><th>Comments</th></tr>
		</thead>
		<tbody>';
		foreach( $bad_status as $row ) {
			echo '<tr><td>'.$row['STATUS_CODE'].'</td><td>'.$row['ORDER_CODE'].'</td><td>'.$row['SOURCE_TYPE'].'</td><td>'.$row['STATUS_STATE'].'</td><td>'.$row['COMMENTS'].'</td></tr>';
		}
		echo '</tbody>
		</table>';
	}

	echo '<h3>Check for shipments dispatched on load=0</h3>';	
	ob_flush(); flush();  sleep(1);
	$bad_status = $status_table->database->get_multiple_rows(
		"SELECT SHIPMENT_CODE, CURRENT_STATUS, LOAD_CODE
		FROM EXP_SHIPMENT
		WHERE CURRENT_STATUS = (SELECT STATUS_CODES_CODE
			FROM EXP_STATUS_CODES WHERE SOURCE_TYPE = 'shipment' AND BEHAVIOR = 'dispatch')
		AND LOAD_CODE = 0" );

	if( is_array($bad_status) && count($bad_status) > 0 ) {
		$errors_found++;
		echo '<h3>Found '.count($bad_status).' shipments dispatched on load=0: <a class="btn btn-md btn-danger"  href="exp_repair_db.php?DISPATCHED_ZERO"><span class="glyphicon glyphicon-wrench"></span> Fix</a></h3>';
		echo '<table class="display table table-striped table-condensed table-bordered table-hover">
		<thead>
		<tr class="exspeedite-bg"><th>Shipment#</th><th>Status#</th><th>Load#</th></tr>
		</thead>
		<tbody>';
		foreach( $bad_status as $row ) {
			echo '<tr><td>'.$row['SHIPMENT_CODE'].'</td><td>'.$row['CURRENT_STATUS'].'</td><td>'.$row['LOAD_CODE'].'</td></tr>';
		}
		echo '</tbody>
		</table>';
	}


	echo '<h3>Check for dispatched loads with shipments in ready state</h3>';	
	ob_flush(); flush();  sleep(1);
	$bad_status = $shipment_table->database->get_multiple_rows(
		"SELECT S.SHIPMENT_CODE, S.CURRENT_STATUS AS SHIPMENT_STATUS, 
			S.LOAD_CODE, L.CURRENT_STATUS AS LOAD_STATUS,
            (SELECT CURRENT_STATUS
            FROM EXP_STOP ST
            WHERE ST.LOAD_CODE = S.LOAD_CODE
            AND ST.SHIPMENT = S.SHIPMENT_CODE
            AND ST.STOP_TYPE = 'pick') AS PICK_STATUS,
            (SELECT CURRENT_STATUS
            FROM EXP_STOP ST
            WHERE ST.LOAD_CODE = S.LOAD_CODE
            AND ST.SHIPMENT = S.SHIPMENT_CODE
            AND ST.STOP_TYPE = 'drop') AS DROP_STATUS
            
			FROM EXP_SHIPMENT S, EXP_LOAD L
			WHERE S.CURRENT_STATUS = (SELECT STATUS_CODES_CODE
				FROM EXP_STATUS_CODES WHERE SOURCE_TYPE = 'shipment' AND
				BEHAVIOR = 'assign')
			AND S.LOAD_CODE > 0
			AND S.LOAD_CODE = L.LOAD_CODE
			AND L.CURRENT_STATUS IN (SELECT STATUS_CODES_CODE
				FROM EXP_STATUS_CODES WHERE SOURCE_TYPE = 'load' AND
				BEHAVIOR IN ('dispatch', 'depart stop', 'depshdock',
					'depart shipper', 'deprecdock', 'depart cons', 'arrive stop', 'arrshdock',
					'arrive shipper', 'arrrecdock', 'arrive cons'))" );

	if( is_array($bad_status) && count($bad_status) > 0 ) {
		$errors_found++;
		echo '<h3>Found '.count($bad_status).' dispatched loads with shipments in ready state: <a class="btn btn-md btn-danger"  href="exp_repair_db.php?SHIPMENT_RDY"><span class="glyphicon glyphicon-wrench"></span> Fix</a></h3>
		<p>Once a load is dispatched, the shipments should not be in ready state. The fix will set the shipment to the correct state (Dispatched, Dispatched, Delivered)</p>';
		echo '<table class="display table table-striped table-condensed table-bordered table-hover">
		<thead>
		<tr class="exspeedite-bg"><th>Load#</th><th>Status</th><th>Shipment#</th><th>Status</th><th>Pick Status</th><th>Drop Status</th></tr>
		</thead>
		<tbody>';
		foreach( $bad_status as $row ) {
			echo '<tr><td>'.vl($row['LOAD_CODE']).'</td><td>'.$load_table->state_name[$row['LOAD_STATUS']].'</td><td>'.vs($row['SHIPMENT_CODE']).'</td><td>'.$shipment_table->state_name[$row['SHIPMENT_STATUS']].'</td><td>'.$stop_table->state_name[$row['PICK_STATUS']].'</td><td>'.$stop_table->state_name[$row['DROP_STATUS']].'</td></tr>';
		}
		echo '</tbody>
		</table>';
	}

	//! PUT NEXT ERROR CHECKER HERE

	if( $errors_found > 0 )
		echo '<h3 class="text-danger"><span class="glyphicon glyphicon-warning-sign"></span>'.$errors_found.' Errors Found. '.$warnings_found.' Warnings Found.</p>';
	else
		echo '<h3 class="text-success"><span class="glyphicon glyphicon-ok"></span> No Errors Found. '.$warnings_found.' Warnings Found.</p>';
//}
ob_end_flush();
?>
</div>


	<script language="JavaScript" type="text/javascript"><!--
		
		$(document).ready( function () {
			//alert(($(window).height() - 150) + "px");

			$('.display').dataTable({
		        //"bLengthChange": false,
		        "bFilter": true,
		        "bSort": false,
		        "bInfo": false,
				"bAutoWidth": false,
				//"bProcessing": true, 
				"sScrollX": "100%",
				"sScrollY": "200px",
				//"sScrollXInner": "120%",
		        //"lengthMenu": [[-1, 25, 50], ["All", 25, 50]],
				"bPaginate": false,
				"bScrollCollapse": true,
				"bSortClasses": false		
			});
			
		});
	//--></script>

<?php

require_once( "include/footer_inc.php" );
?>

