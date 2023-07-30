<?php
session_start();
?>
<?php
header("Content-Type: application/vnd.ms-excel");
header("Expires: 0");
header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
header("content-disposition: attachment;filename=" . str_replace (" ","",$_SESSION['txtReportName']) . ".xls");
?>
<?php require_once('includes/config.php'); ?>
<?php
$currentPage = $_SERVER["PHP_SELF"];
$maxRows_recSQL = "18446744073709551615";
$pageNum_recSQL = 0;
$startRow_recSQL = $pageNum_recSQL * $maxRows_recSQL;

mysql_select_db($database_connDB, $connDB);
$query_recSQL = $_SESSION["tmpSQL"];
$query_limit_recSQL = sprintf("%s LIMIT %d, %d", $query_recSQL, $startRow_recSQL, $maxRows_recSQL);
$recSQL = mysql_query($query_limit_recSQL, $connDB) or die(mysql_error());
$column_count = mysql_num_fields($recSQL) or die("display_db_query:" . mysql_error());

if (isset($_GET['totalRows_recSQL'])) {
  $totalRows_recSQL = $_GET['totalRows_recSQL'];
} else {
  $all_recSQL = mysql_query($query_recSQL);
  $totalRows_recSQL = mysql_num_rows($all_recSQL);
}
$totalPages_recSQL = ceil($totalRows_recSQL/$maxRows_recSQL)-1;

$queryString_recSQL = "";
if (!empty($_SERVER['QUERY_STRING'])) {
  $params = explode("&", $_SERVER['QUERY_STRING']);
  $newParams = array();
  foreach ($params as $param) {
    if (stristr($param, "pageNum_recSQL") == false && 
        stristr($param, "totalRows_recSQL") == false) {
      array_push($newParams, $param);
    }
  }
  if (count($newParams) != 0) {
    $queryString_recSQL = "&" . htmlentities(implode("&", $newParams));
  }
}

$queryString_recSQL = sprintf("&totalRows_recSQL=%d%s", $totalRows_recSQL, $queryString_recSQL);
?>
<?php
print("<TABLE border='1' cellspacing='0' cellpading='0'> \n");
print("<TR ALIGN=LEFT VALIGN=TOP>");
for($column_num = 0; $column_num < $column_count; $column_num++) {
	$field_name = mysql_field_name($recSQL, $column_num);
	print("<TD bgcolor='#D2E3FF'><b>$field_name</b></TD>");
}
print("</TR>\n");
$row = mysql_fetch_row($recSQL);
$rowColor = "#E6FFDD";
do {
	if ($rowColor=="#E6FFDD"){
		print("<TR bgcolor='#E6FFDD'>");
		$rowColor = "#BBE9FF";
	}else{
		print("<TR bgcolor='#BBE9FF'>");
		$rowColor = "#E6FFDD";
	}
	for($column_num = 0; $column_num < $column_count; $column_num++) {
		print("<TD>");
		if ($row[$column_num]!=""){
			print($row[$column_num]);
		}else{
			print("&nbsp;");
		}
		print("</TD>\n");
	}
	print("</TR>\n");
} while ($row = mysql_fetch_row($recSQL)); 
print("</TABLE>");
?>
<?php
mysql_free_result($recSQL);
?>