<?php
session_start();
?>

<?php require_once('includes/config.php'); ?>

<?php
function dmyError(){
	$_SESSION["dmyError"] = "An error has occurred in generating the report. <br/> This is usually caused when a DB table required/used for this report has been deleted.";
	$_SESSION["dmyErrorUrl"] = "selectTables.php";
	print "<script language=\"JavaScript\">";
	print "window.location = 'genError.php' ";
	print "</script>";
}

function GetSQLValueString($theValue, $theType, $theDefinedValue = "", $theNotDefinedValue = "") 
{
  $theValue = (!get_magic_quotes_gpc()) ? addslashes($theValue) : $theValue;

  switch ($theType) {
    case "text":
      $theValue = ($theValue != "") ? "'" . $theValue . "'" : "NULL";
      break;    
    case "long":
    case "int":
      $theValue = ($theValue != "") ? intval($theValue) : "NULL";
      break;
    case "double":
      $theValue = ($theValue != "") ? "'" . doubleval($theValue) . "'" : "NULL";
      break;
    case "date":
      $theValue = ($theValue != "") ? "'" . $theValue . "'" : "NULL";
      break;
    case "defined":
      $theValue = ($theValue != "") ? $theDefinedValue : $theNotDefinedValue;
      break;
  }
  return $theValue;
}

if (isset($_POST["txtReportName"]) && $_POST["txtReportName"] != "") {
	$_SESSION['appliedConditions'] = $_POST["appliedConditions"]; 
	$_SESSION['txtReportName'] = $_POST["txtReportName"]; 
	$_SESSION['lstSortName'] = $_POST["lstSortName"]; 
	$_SESSION['lstSortOrder'] = $_POST["lstSortOrder"]; 
	$_SESSION['txtRecPerPage'] = $_POST["txtRecPerPage"]; 

	if ($_POST["lstSave"]==1){
		$insertSQL = sprintf("INSERT INTO tblreports (appliedConditions, txtReportName, lstSortName, lstSortOrder, txtRecPerPage, selectedFields, selectedTables) VALUES (%s, %s, %s, %s, %s, %s, %s)",
			GetSQLValueString($_SESSION['appliedConditions'], "text"),
			GetSQLValueString($_SESSION['txtReportName'], "text"),
			GetSQLValueString($_SESSION['lstSortName'], "text"),
			GetSQLValueString($_SESSION['lstSortOrder'], "text"),
			GetSQLValueString($_SESSION['txtRecPerPage'], "text"),
			GetSQLValueString($_SESSION['selectedFields'], "text"),
			GetSQLValueString($_SESSION['selectedTables'], "text"));
		mysql_select_db($database_connSave, $connSave);
		$Result1 = mysql_query($insertSQL, $connSave) or die(mysql_error());
	}
}

// The code to generate the SQL statement
$tmpSQL = "SELECT ";

$tmpFields = split("~",$_SESSION['selectedFields']);
for ($x=0; $x<=count($tmpFields)-1; $x+=1) {
	if ($tmpFields[$x]!=""){
		$tmpSQL = $tmpSQL . $tmpFields[$x] . ", ";
	}
}

$tmpSQL = substr($tmpSQL, 0, (strlen($tmpSQL)-2) );

$tmpSQL = $tmpSQL . " FROM ";

$tmpTables = split("~",$_SESSION['selectedTables']);
for ($x=0; $x<=count($tmpTables)-1; $x+=1) {
	if ($tmpTables[$x]!=""){
		$tmpSQL = $tmpSQL . $tmpTables[$x] . ", ";
	}
}

$tmpSQL = substr($tmpSQL, 0, (strlen($tmpSQL)-2) );

if ($_SESSION['appliedConditions']!="")	{
	$tmpSQL = $tmpSQL . " WHERE ";
	
	$tmpCondition = split("~",$_SESSION['appliedConditions']);
	for ($x=0; $x<=count($tmpCondition)-1; $x+=1) {
		if ($tmpCondition[$x]!=""){
			$tmpSQL = $tmpSQL . stripslashes($tmpCondition[$x]) . " ";
		}
	}
}

if ($_SESSION['lstSortName']!=""){
	$tmpSQL = $tmpSQL . " ORDER BY " . $_SESSION['lstSortName'] . " " . $_SESSION['lstSortOrder'];
}

$_SESSION["tmpSQL"] = $tmpSQL;

$currentPage = $_SERVER["PHP_SELF"];

if ($_SESSION['txtRecPerPage']==""){
	$maxRows_recSQL = "18446744073709551615";
}else{
	$maxRows_recSQL = $_SESSION['txtRecPerPage'];
}
$pageNum_recSQL = 0;
if (isset($_GET['pageNum_recSQL'])) {
  $pageNum_recSQL = $_GET['pageNum_recSQL'];
}
$startRow_recSQL = $pageNum_recSQL * $maxRows_recSQL;

mysql_select_db($database_connDB, $connDB);
$query_recSQL = $tmpSQL;
$query_limit_recSQL = sprintf("%s LIMIT %d, %d", $query_recSQL, $startRow_recSQL, $maxRows_recSQL);
$recSQL = mysql_query($query_limit_recSQL, $connDB) or die(dmyError());
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
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1">
<title>-= <?php echo $pageTitle;?> =-</title>
<link href="stylesheet.css" rel="stylesheet" type="text/css">
</head>
<body bgcolor="#dcdedb">
<table width="693" border="0" align="center" cellpadding="0" cellspacing="0">
  <tr>
    <td width="13" height="47"><img src="images/main_window/top_left_corner.gif" width="15" height="47"></td>
    <td width="668" background="images/main_window/top.gif"><img src="images/main_window/title.gif" width="169" height="47"></td>
    <td width="12"><img src="images/main_window/top_right_corner.gif" width="18" height="47"></td>
  </tr>
  <tr>
    <td height="306" background="images/main_window/left.gif">&nbsp;</td>
    <td valign="top" bgcolor="#FBF9F9"><table width="100%"  border="0" cellspacing="0" cellpadding="0">
      <tr>
        <td width="66%"><table width="100%"  border="0" cellspacing="0" cellpadding="3">
          <tr>
            <td height="36" bgcolor="#D2E3FF" class="pageHeader">&nbsp;&nbsp;<?php echo $pageTitle;?></td>
          </tr>
        </table></td>
      </tr>
    </table>
        <table width="100%"  border="0" cellspacing="0" cellpadding="5">
          <tr>
            <td height="22"><table width="100%"  border="0" cellspacing="0" cellpadding="0">
              
              <tr>
                <td height="27"><table width="100%" border="0" cellspacing="0" cellpadding="0">
                  <tr>
                    <td class="reportHeading"><table width="100%" border="0" cellspacing="0" cellpadding="0">
                        <tr>
                          <td width="6%"><img src="images/create.gif" alt="View Report" width="32" height="32"></td>
                          <td width="94%"><?php echo $_SESSION['txtReportName'];?></td>
                        </tr>
                    </table></td>
                  </tr>
                  <tr>
                    <td><hr size="1" /></td>
                  </tr>
                </table></td>
              </tr>
              <tr>
                <td height="27"><table width="100%" border="0" cellpadding="0" cellspacing="0" class="reportNavi">
                  <tr>
                    <td width="74%" bgcolor="#F0F0F0" class="subHeader">&nbsp;
                      Records <?php echo ($startRow_recSQL + 1) ?> to <?php echo min($startRow_recSQL + $maxRows_recSQL, $totalRows_recSQL) ?> of <?php echo $totalRows_recSQL ?> </td>
                    <td width="26%" bgcolor="#F0F0F0" class="subHeader"><table width="50%" border="0" align="center" cellpadding="5">
                          <tr>
                            <td width="23%" align="center" bgcolor="#C2F4BD"><?php if ($pageNum_recSQL > 0) { // Show if not first page ?>
                                <a href="<?php printf("%s?pageNum_recSQL=%d%s", $currentPage, 0, $queryString_recSQL); ?>"><b>First</b></a>
                                <?php }else{echo"First";} // Show if not first page ?>                            </td>
                            <td width="31%" align="center" bgcolor="#C2F4BD"><?php if ($pageNum_recSQL > 0) { // Show if not first page ?>
                                <a href="<?php printf("%s?pageNum_recSQL=%d%s", $currentPage, max(0, $pageNum_recSQL - 1), $queryString_recSQL); ?>"><b>Previous</b></a>
                                <?php }else{echo"Previous";} // Show if not first page ?>                            </td>
                            <td width="23%" align="center" bgcolor="#C2F4BD"><?php if ($pageNum_recSQL < $totalPages_recSQL) { // Show if not last page ?>
                                <a href="<?php printf("%s?pageNum_recSQL=%d%s", $currentPage, min($totalPages_recSQL, $pageNum_recSQL + 1), $queryString_recSQL); ?>"><b>Next</b></a>
                                <?php }else{echo"Next";} // Show if not last page ?>                            </td>
                            <td width="23%" align="center" bgcolor="#C2F4BD"><?php if ($pageNum_recSQL < $totalPages_recSQL) { // Show if not last page ?>
                                <a href="<?php printf("%s?pageNum_recSQL=%d%s", $currentPage, $totalPages_recSQL, $queryString_recSQL); ?>"><b>Last</b></a>
                                <?php }else{echo"Last";} // Show if not last page ?>                            </td>
                          </tr>
                      </table></td>
                  </tr>
                </table></td>
              </tr>
              <tr>
                <td height="27">
				<?php
					print("<TABLE width='100%' cellspacing='0' cellpading='0' class='tableReports'> \n");
					print("<TR ALIGN=LEFT VALIGN=TOP>");
					for($column_num = 0; $column_num < $column_count; $column_num++) {
						$field_name = mysql_field_name($recSQL, $column_num);
						print("<TD class='tableHeader'><b>$field_name</b></TD>");
					}
					print("</TR>\n");
					
					$row = mysql_fetch_row($recSQL);
					do {
						print("<TR ALIGN=LEFT VALIGN=TOP>");
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
					?>
                    <table width="100%" border="0" cellspacing="0" cellpadding="0">
                      <tr>
                        <td width="57%">&nbsp;</td>
                        <td width="43%">&nbsp;</td>
                      </tr>
                      
                      <tr>
                        <td>
						<input name="cmdBack" type="button" class="button" id="cmdBack" onclick="javascript:window.location.href ='setConditions.php'" value="&lt;&lt; Back" />
              			<input name="cmdExport" type="button" class="button" id="cmdExport" onclick="javascript:window.location.href ='export.php'" value="Export to Excel"></td>
                        <td>&nbsp;</td>
                      </tr>
                  </table></td>
              </tr>
            </table></td>
          </tr>
      </table></td>
    <td background="images/main_window/right.gif">&nbsp;</td>
  </tr>
  <tr>
    <td height="14"><img src="images/main_window/bottom_left_corner.gif" width="15" height="14"></td>
    <td background="images/main_window/bottom.gif"><img src="images/main_window/bottom.gif" width="668" height="14"></td>
    <td><img src="images/main_window/bottom_right_corner.gif" width="18" height="14"></td>
  </tr>
</table>
</body>
</html>
<?php
mysql_free_result($recSQL);
?>
