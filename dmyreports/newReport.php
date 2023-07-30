<?php
session_start();
$_SESSION['appliedConditions'] = "";
$_SESSION['txtReportName'] = ""; 
$_SESSION['lstSortName'] = ""; 
$_SESSION['lstSortOrder'] = ""; 
$_SESSION['txtRecPerPage'] = "";
$_SESSION['selectedFields'] = "";
$_SESSION['selectedTables'] = ""; 
header("Location:index.php");
?>