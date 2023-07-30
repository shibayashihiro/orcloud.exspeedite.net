<?php require_once('includes/config.php'); ?>

<?php
$currentPage = $_SERVER["PHP_SELF"];

$maxRows_recReports = 10;
$pageNum_recReports = 0;
if (isset($_GET['pageNum_recReports'])) {
  $pageNum_recReports = $_GET['pageNum_recReports'];
}
$startRow_recReports = $pageNum_recReports * $maxRows_recReports;

mysql_select_db($database_connSave, $connSave);
$query_recReports = "SELECT * FROM tblreports WHERE status = 0 ORDER BY id DESC";
$query_limit_recReports = sprintf("%s LIMIT %d, %d", $query_recReports, $startRow_recReports, $maxRows_recReports);
$recReports = mysql_query($query_limit_recReports, $connSave) or die(mysql_error());
$row_recReports = mysql_fetch_assoc($recReports);

if (isset($_GET['totalRows_recReports'])) {
  $totalRows_recReports = $_GET['totalRows_recReports'];
} else {
  $all_recReports = mysql_query($query_recReports);
  $totalRows_recReports = mysql_num_rows($all_recReports);
}
$totalPages_recReports = ceil($totalRows_recReports/$maxRows_recReports)-1;

$queryString_recReports = "";
if (!empty($_SERVER['QUERY_STRING'])) {
  $params = explode("&", $_SERVER['QUERY_STRING']);
  $newParams = array();
  foreach ($params as $param) {
    if (stristr($param, "pageNum_recReports") == false && 
        stristr($param, "totalRows_recReports") == false) {
      array_push($newParams, $param);
    }
  }
  if (count($newParams) != 0) {
    $queryString_recReports = "&" . htmlentities(implode("&", $newParams));
  }
}
$queryString_recReports = sprintf("&totalRows_recReports=%d%s", $totalRows_recReports, $queryString_recReports);
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1">
<title>-= <?php echo $pageTitle;?> =-</title>
<link href="stylesheet.css" rel="stylesheet" type="text/css">
</head>
<script language="javascript" type="text/javascript">

function cmdDelete_onClick(recID) {
	var tmpVal= confirm("Please Confirm Action");
	
	if (tmpVal== true){
		window.open("delReport.php?id=" + recID,"_self");
	} 
}

</script>
<body bgcolor="#dcdedb">
<table width="693" border="0" align="center" cellpadding="0" cellspacing="0">
  <tr>
    <td width="13" height="47"><img src="images/main_window/top_left_corner.gif" width="15" height="47"></td>
    <td width="668" background="images/main_window/top.gif"><img src="images/main_window/title.gif" width="169" height="47"></td>
    <td width="12"><img src="images/main_window/top_right_corner.gif" width="18" height="47"></td>
  </tr>
  <tr>
    <td height="293" background="images/main_window/left.gif">&nbsp;</td>
    <td valign="top" bgcolor="#FBF9F9"><table width="100%"  border="0" cellspacing="0" cellpadding="0">
      <tr>
        <td width="66%"><table width="100%"  border="0" cellspacing="0" cellpadding="3">
          <tr>
            <td height="36" bgcolor="#D2E3FF" class="pageHeader">&nbsp;&nbsp;<?php echo $pageTitle;?> - Create / View Reports </td>
          </tr>
        </table></td>
      </tr>
    </table>
        <table width="100%"  border="0" cellspacing="0" cellpadding="0">
          <tr>
            <td height="22"><table width="100%"  border="0" cellspacing="0" cellpadding="5">
              
              <tr>
                <td height="222"><br>
                  <table width="80%" border="0" align="center" cellpadding="0" cellspacing="0">
                  <tr>
                    <td valign="top"><table width="300" border="0" align="center" cellpadding="0" cellspacing="0" class="tableBorders">
                      <tr>
                        <td class="tableHeader"> <table width="100%" border="0" cellspacing="0" cellpadding="0">
                          <tr>
                            <td width="8%"><img src="images/load.gif" alt="Load" width="16" height="16"></td>
                            <td width="92%">Load Reports</td>
                          </tr>
                        </table>
                          </td>
                      </tr>
                      <tr>
                        <td height="53" valign="top"><table width="300" border="0" align="center" cellpadding="0" cellspacing="0">
						<?php if ($row_recReports!="") { ?>
                          <?php do { ?>
                          <tr>
                            <td height="15"><table border="0" cellpadding="0" cellspacing="0">
                                <tr>
                                  <td width="280" class="tableRules">&nbsp;&raquo;&nbsp;<a href="loadReport.php?id=<?php echo $row_recReports['id']; ?>"><?php echo $row_recReports['txtReportName']; ?></a></td>
                                  <td width="20" class="tableRules"><label>
                                    <input name="cmdDelete" type="image" id="cmdDelete" onClick="cmdDelete_onClick(<?php echo $row_recReports['id']; ?>);" src="images/delete.png" alt="Delete Reports">
                                    </label></td>
                                </tr>
                            </table></td>
                          </tr>
                          <?php } while ($row_recReports = mysql_fetch_assoc($recReports)); ?>
                          <tr>
                            <td height="15"><div align="center" class="tableNav">Records <?php echo ($startRow_recReports + 1) ?> to <?php echo min($startRow_recReports + $maxRows_recReports, $totalRows_recReports) ?> of <?php echo $totalRows_recReports ?> </div></td>
                          </tr>
                          <tr>
						  	<?php if ($pageNum_recReports > 0 || $pageNum_recReports < $totalPages_recReports) { ?>
                            <td height="15" class="tableNav"><table border="0" width="50%" align="center">
                                <tr>
                                  <td width="23%" align="center"><?php if ($pageNum_recReports > 0) { // Show if not first page ?>
                                      <a href="<?php printf("%s?pageNum_recReports=%d%s", $currentPage, 0, $queryString_recReports); ?>">First</a>
                                      <?php } // Show if not first page ?>
                                  </td>
                                  <td width="31%" align="center"><?php if ($pageNum_recReports > 0) { // Show if not first page ?>
                                      <a href="<?php printf("%s?pageNum_recReports=%d%s", $currentPage, max(0, $pageNum_recReports - 1), $queryString_recReports); ?>">Previous</a>
                                      <?php } // Show if not first page ?>
                                  </td>
                                  <td width="23%" align="center"><?php if ($pageNum_recReports < $totalPages_recReports) { // Show if not last page ?>
                                      <a href="<?php printf("%s?pageNum_recReports=%d%s", $currentPage, min($totalPages_recReports, $pageNum_recReports + 1), $queryString_recReports); ?>">Next</a>
                                      <?php } // Show if not last page ?>
                                  </td>
                                  <td width="23%" align="center"><?php if ($pageNum_recReports < $totalPages_recReports) { // Show if not last page ?>
                                      <a href="<?php printf("%s?pageNum_recReports=%d%s", $currentPage, $totalPages_recReports, $queryString_recReports); ?>">Last</a>
                                      <?php } // Show if not last page ?>
                                  </td>
                                </tr>
                            </table></td>
							<?php } ?>
                          </tr>
                          <?php } else { echo "No Saved Reports. Please click on the create report button to continue."; } ?>
                        </table></td>
                      </tr>
                    </table>
                    </td>
                    <td>&nbsp;</td>
                    <td valign="top"><div onClick="window.location='selectTables.php';" style="cursor:pointer"><table width="100%" border="0" cellpadding="0" cellspacing="0" class="createReport">
                      <tr>
                        <td><img src="images/create.gif" alt="Create Report" width="32" height="32"></td>
                        <td>Create Report </td>
                      </tr>
                    </table></div></td>
                  </tr>
                </table>
                <br></td>
              </tr>
              <tr>
                <td height="35" class="statusBar">* Use the options above to create reports or to view existing reports that have been created and saved <br>
                * You can delete reports by clicking on the button marked with a 'X' </td>
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
mysql_free_result($recReports);
?>