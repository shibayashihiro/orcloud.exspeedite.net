<?php require_once('includes/config.php'); ?>
<?php
//Code to Delete Reports
$colname_recDel = $_GET['id'];

mysql_select_db($database_connSave, $connSave);
$query_recDel = sprintf("SELECT status FROM tblreports WHERE id = %s", $colname_recDel);
$recDel = mysql_query($query_recDel, $connSave) or die(mysql_error());
$row_recDel = mysql_fetch_assoc($recDel);
$totalRows_recDel = mysql_num_rows($recDel);

$updateSQL = "UPDATE tblreports SET status=1 WHERE id = " . $colname_recDel;

mysql_select_db($database_connSave, $connSave);
$Result1 = mysql_query($updateSQL, $connSave) or die(mysql_error());

header("Location:index.php");

mysql_free_result($recDel);
?>