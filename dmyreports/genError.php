<?php
session_start();
?>
<?php require_once('includes/config.php'); ?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1">
<title>-=<?php echo $pageTitle;?>=-</title>
<link href="stylesheet.css" rel="stylesheet" type="text/css">
</head>
<script language="javascript" type="text/javascript">

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
                <td height="36" bgcolor="#D2E3FF" class="pageHeader">&nbsp;&nbsp;<?php echo $pageTitle;?> - Error </td>
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
                            <td class="tableHeader"><table width="100%" border="0" cellspacing="0" cellpadding="0">
                                <tr>
                                  <td width="8%"><img src="images/load.gif" alt="Load" width="16" height="16"></td>
                                  <td width="92%">Error</td>
                                </tr>
                              </table></td>
                          </tr>
                          <tr>
                            <td height="53"><table width="95%" border="0" align="center" cellpadding="0" cellspacing="0">
                                <tr>
                                  <td><?php print($_SESSION["dmyError"]); ?></td>
                                </tr>
                              </table></td>
                          </tr>
                          <tr>
                            <td height="19"><div onClick="window.location='<?php print $_SESSION["dmyErrorUrl"];?>';" style="cursor:pointer">
                                <table width="100%" border="0" cellpadding="0" cellspacing="0" class="createReport">
                                  <tr>
                                    <td height="26"><div align="center">Back</div></td>
                                  </tr>
                                </table>
                              </div></td>
                          </tr>
                        </table></td>
                    </tr>
                  </table>
                  <br></td>
              </tr>
              <tr>
                <td height="35" class="statusBar">* An error  occoured in generating the report, please check your report settings to eliminate the error . </td>
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
