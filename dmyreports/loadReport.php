<?php session_start();?>
<?php require_once('includes/config.php'); ?>
<?php
$colname_recLoad = "-1";
if (isset($_GET['id'])) {
  $colname_recLoad = (get_magic_quotes_gpc()) ? $_GET['id'] : addslashes($_GET['id']);
}
mysql_select_db($database_connSave, $connSave);
$query_recLoad = sprintf("SELECT * FROM tblreports WHERE id = %s", $colname_recLoad);
$recLoad = mysql_query($query_recLoad, $connSave) or die(mysql_error());
$row_recLoad = mysql_fetch_assoc($recLoad);
$totalRows_recLoad = mysql_num_rows($recLoad);

$_SESSION['appliedConditions'] = $row_recLoad['appliedConditions'];
$_SESSION['txtReportName'] = $row_recLoad['txtReportName'];
$_SESSION['lstSortName'] =$row_recLoad['lstSortName'];
$_SESSION['lstSortOrder'] = $row_recLoad['lstSortOrder'];
$_SESSION['txtRecPerPage'] = $row_recLoad['txtRecPerPage'];
$_SESSION['selectedFields'] = $row_recLoad['selectedFields'];
$_SESSION['selectedTables'] = $row_recLoad['selectedTables'];

header("Location:generateSQL.php");

mysql_free_result($recLoad);
?>