<?php 

// Set flag that this is a parent file
define( '_STS_INCLUDE', 1 );

// Set flag that this is session readonly
define( '_STS_SESSION_READONLY', 1 );

// Set flag that this is an ajax call
define( '_STS_SESSION_AJAX', 1 );

// Setup Session
require_once( "include/sts_session_setup.php" );
	
error_reporting(E_ERROR | E_PARSE);

require_once( "include/sts_config.php" );
$sts_debug = isset($_GET['debug']);

// Check schema exists
function schema_exists( $db, $schema, $debug = false ) {
	$result = false;
	if( $debug ) echo "<p>".__FUNCTION__.": entry, schema = $schema</p>";
	
	$check = $db->query("SELECT SCHEMA_NAME
		FROM information_schema.SCHEMATA
		WHERE SCHEMA_NAME = '".$schema."'");
		
	if( $debug ) {
		echo "<p>check = </p>
		<pre>";
		var_dump($check);
		echo "</pre>";
	}
	
	if( $check->num_rows > 0 ) {
		$row1 = $check->fetch_assoc();
		$check->close();
		
		if( $debug ) {
			echo "<p>row1 = </p>
			<pre>";
			var_dump($row1);
			echo "</pre>";
		}
		
		$result = is_array($row1) && count($row1) > 0 &&
			$row1["SCHEMA_NAME"] == $schema;
	}
	
	if( $debug ) echo "<p>".__FUNCTION__.": exit, return ".($result ? "TRUE" : "FALSE")."</p>";
	return $result;
}

//create a schema
function create_schema( $db, $schema, $debug = false ) {
	if( $debug ) echo "<p>".__FUNCTION__.": entry, schema = $schema</p>";

	$result = $db->query("create schema `".$schema."` CHARACTER SET = utf8 collate = utf8_general_ci");
		
	if( $debug ) {
		echo "<p>result = </p>
		<pre>";
		var_dump($result);
		echo "</pre>";
	}
	
	if( $debug ) echo "<p>".__FUNCTION__.": exit, return ".($result ? "TRUE" : "FALSE")."</p>";
	return $result;
}

if( isset($_GET['DB_HOST']) &&
	isset($_GET['DB_USER']) &&
	isset($_GET['DB_PASSWORD']) &&
	isset($_GET['DB_DATABASE']) &&
	$_GET['code'] == 'Honey') {

	if( $sts_debug ) {
		echo "<p>Parameters = </p>
		<pre>";
		var_dump($_GET);
		echo "</pre>";
	}

	$result = new mysqli($_GET['DB_HOST'], $_GET['DB_USER'], $_GET['DB_PASSWORD'], "");
	
	$myresult = array();
	$myresult["errno"] = 0;
	
	if( $result->connect_errno == 0 ) {
		$myresult["msg"] = "DB OK";
		
		if( schema_exists( $result, $_GET['DB_DATABASE'], $sts_debug ) ) {
			$myresult["msg"] .= "<br>Found schema ".$_GET['DB_DATABASE'];
		} else {
			$myresult["msg"] .= "<br>Did not find schema ".$_GET['DB_DATABASE'];
			
			if( create_schema( $result, $_GET['DB_DATABASE'], $sts_debug ) ) {
				$myresult["msg"] .= "<br>Created schema ".$_GET['DB_DATABASE'];
			} else {
				$myresult["errno"] = 1;
				$myresult["msg"] .= "<br>Failed to create schema ".$_GET['DB_DATABASE'];
			}
		}
		
		if( $myresult["errno"] == 0 ) {
			
			// Check for root user
			$result2 = $result->query("SELECT * FROM mysql.user
				where host='localhost' and user='root'");
			if( $sts_debug ) {
				echo "<p>result2 = </p>
				<pre>";
				var_dump($result2);
				echo "</pre>";
			}
			if( $result->affected_rows > 0 ) {
				$row2=$result2->fetch_assoc();
				$result2->close();
				if( $sts_debug ) {
					echo "<p>row = </p>
					<pre>";
					var_dump($row2);
					echo "</pre>";
				}
				if( is_array($row2) && count($row2) > 0 ) {
					$result3 = $result->query("SELECT GRANTEE, privilege_type FROM information_schema.USER_PRIVILEGES
						where grantee ='\'root\'@\'localhost\''
						and table_catalog='def'
						and privilege_type='ALTER ROUTINE'
						union all
						SELECT GRANTEE, privilege_type FROM information_schema.SCHEMA_PRIVILEGES
						where grantee ='\'root\'@\'localhost\''
						and table_schema in('".$_GET['DB_DATABASE']."','*')
						and privilege_type='ALTER ROUTINE'");
	
					if( $result->affected_rows > 0 ) {
						$row3=$result3->fetch_assoc();
						$result3->close();
						if( $sts_debug ) {
							echo "<p>row = </p>
							<pre>";
							var_dump($row3);
							echo "</pre>";
						}
						if( is_array($row2) && count($row2) > 0 ) {
							$myresult["msg"] .= "<br>Found root@localhost<br>Priviliges OK";
						} else {
							$myresult["errno"] = 3;
							$myresult["msg"] .= "<br>Found root@localhost<br>Check priviliges!";
						}
						
					} else {
						$myresult["errno"] = 3;
						$myresult["msg"] .= "<br>Found root@localhost<br>Check priviliges!";
					}
	
	
				} else {
					$myresult["errno"] = 2;
					$myresult["msg"] .= "<br>Did not find user root@localhost";
				}
			} else {
				$myresult["errno"] = 2;
				$myresult["msg"] .= "<br>Did not find user root@localhost";
			}
		}
	} else {
		$myresult["errno"] = $result->connect_errno;
		$myresult["msg"] = $result->connect_error;
	}

	if( $sts_debug ) {
		echo "<p>myresult = </p>
		<pre>";
		var_dump($myresult);
		echo "</pre>";
	} else {
		echo json_encode( $myresult );
	}
}


?>

