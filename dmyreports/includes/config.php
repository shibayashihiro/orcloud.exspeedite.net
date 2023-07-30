<?php
//! Duncan Changes - use Exspeedite settings
// Set flag that this is a parent file
define( '_STS_INCLUDE', 1 );
require_once( "../include/exspeedite_config.php" );

// Basic Parameters
$pageTitle = "Report Manager"; 

// Database To Be Queired
$hostname_connDB = $sts_db_host.':'.$sts_db_port; 	// The host name of the MySql server
$database_connDB = $sts_database; 			// The name of the Database to be queired
$username_connDB = $sts_username;			// Username to login to the server
$password_connDB = $sts_password;			// Password to the MySQL server
$dbVisTables = "";			// The name of the tables to be displayed seperated by commas. 
					// Leave this blank if all the tables and views are to be displayed.
					// eg $dbVisTables = "table1,table2,table3";

//Databse To Save Reports
$hostname_connSave = $sts_db_host.':'.$sts_db_port; 	// The host name of the MySql server where the generated reports are to be saved
$database_connSave = "dmyreports"; 	// The name of the Database to save the generated reports
$username_connSave = $sts_username;		// Username to login to the server
$password_connSave = $sts_password;		// Password to the MySQL server

//Do not edit after this point
$connDB = mysql_pconnect($hostname_connDB, $username_connDB, $password_connDB) or trigger_error(mysql_error(),E_USER_ERROR); 
$connSave = mysql_pconnect($hostname_connSave, $username_connSave, $password_connSave) or trigger_error(mysql_error(),E_USER_ERROR); 
?>