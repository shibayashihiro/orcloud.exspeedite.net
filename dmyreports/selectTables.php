<?php
session_start();
?>
<?php require_once('includes/config.php'); ?>
<?php
	$visTables =  split(",",$dbVisTables);
	$_SESSION['selectedTables'] = '';
	if (count($visTables)==1) {
		if ($visTables[0]!=""){
			$_SESSION['selectedTables'] = "`" . $visTables[0] . "`";
			header("Location:selectFields.php");
		}
	}
?>
<?php
mysql_select_db($database_connDB, $connDB);
$query_recGetTables = "SHOW TABLES";
$recGetTables = mysql_query($query_recGetTables, $connDB) or die(mysql_error());
$row_recGetTables = mysql_fetch_array($recGetTables);
$totalRows_recGetTables = mysql_num_rows($recGetTables);
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1">
<title>-=<?php echo $pageTitle;?>=-</title>
<link href="stylesheet.css" rel="stylesheet" type="text/css">
</head>
<script language="javascript" type="text/javascript">
var lstAllTables;
var lstTables;
var selectedTables;
var cmdNext;

function initVars() {
	lstAllTables = document.getElementById("lstAllTables");
	lstTables = document.getElementById("lstTables");
	selectedTables = document.getElementById("selectedTables");
	cmdNext = document.getElementById("cmdNext");
}

function cmdSelectTables_onclick() {
   	var addIndex = lstAllTables.selectedIndex;
   	if(addIndex < 0)
      return;
	  
	for (i = 0; i < lstAllTables.options.length; i++) {
		if (lstAllTables.options[i].selected) {

			var tmpFound = 0;
			for (var x = 0; x <= ((lstTables.options.length)-1); x++)
			{
				if (lstTables.options[x].value == lstAllTables.options[i].value) {
					tmpFound = 1;
				}
			}

			if (tmpFound!=1){
				newOption = document.createElement('option');
				newText = document.createTextNode(lstAllTables.options[i].value);
				
				newOption.appendChild(newText);
				newOption.setAttribute("value",lstAllTables.options[i].value);
			
				lstTables.appendChild(newOption);
				
				updateTables();
				cmdNext.disabled=false;
			}
			
		} 
	}
}

function cmdRemoveTables_onclick() {
	var selIndex = lstTables.selectedIndex;
	var itemCount = lstTables.options.length;
	   if(selIndex < 0)
		  return;

	for (i = 0; i < itemCount; i++) {
		for (x = 0; x < lstTables.options.length; x++) {
			if (lstTables.options[x].selected) {
				lstTables.removeChild(lstTables.options.item(x))
			}
		}
	}

	updateTables();

	if (lstTables.options.length==0){
		cmdNext.disabled=true;
	}
	
}

function updateTables(){
	selectedTables.value = "";
	for (var x = 0; x <= ((lstTables.options.length)-1); x++)
	{
		selectedTables.value = selectedTables.value + lstTables.options[x].value + "~";
	}
}

function cmdNew_onClick() {
	var tmpVal= confirm("Please Confirm Action");
	
	if (tmpVal== true){
		window.open("newReport.php","_self");
	} 
}

function jumpURL(tmpURL) {	
	window.location.href = tmpURL;
}

</script>
<body onLoad="initVars();" bgcolor="#dcdedb">
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
            <td height="36" bgcolor="#D2E3FF" class="pageHeader">&nbsp;&nbsp;<?php echo $pageTitle;?> - Select Tables </td>
          </tr>
        </table></td>
      </tr>
    </table>
        <table width="100%"  border="0" cellspacing="0" cellpadding="0">
          <tr>
            <td height="22"><table width="100%"  border="0" cellspacing="0" cellpadding="5">
              <tr>
                <td >&nbsp;</td>
              </tr>
              <tr>
                <td><table width="350" border="0" align="center" cellpadding="5" cellspacing="0" class="tableBorders">
                    <!--DWLayoutTable-->
                    <tr>
                      <td width="152" class="tableHeader"><table width="100%" border="0" cellspacing="0" cellpadding="0">
                          <tr>
                            <td width="14%"><img src="images/dash.gif" alt="Dash" width="22" height="22"> </td>
                            <td width="86%">All Tables</td>
                          </tr>
                        </table></td>
                      <td width="44" class="tableHeader"><!--DWLayoutEmptyCell-->&nbsp;</td>
                      <td width="152" class="tableHeader"><table width="100%" border="0" cellspacing="0" cellpadding="0">
                        <tr>
                          <td width="14%"><img src="images/dash.gif" alt="Dash" width="22" height="22"> </td>
                          <td width="86%">Selected Tables </td>
                        </tr>
                      </table>
                      </td>
                    </tr>
                    <tr>
                      <td><select name="lstAllTables" size="10" multiple id="lstAllTables" style="width:150px;">
                    <?php
					do { 
						if ($dbVisTables!=""){
							$visTables =  split(",",$dbVisTables);
							for ($x=0; $x<=count($visTables)-1; $x+=1) {
								if ($row_recGetTables[0]==trim($visTables[$x])) {
					?>
                          <option value="<?php echo "`".$row_recGetTables[0]."`" ?>"><?php echo $row_recGetTables[0]?></option>
                          <?php
								}
							}
						}else{
					?>
                          <option value="<?php echo "`".$row_recGetTables[0]."`" ?>"><?php echo $row_recGetTables[0]?></option>
                          <?php
						}
					} while ($row_recGetTables = mysql_fetch_array($recGetTables));
					$rows = mysql_num_rows($recGetTables);
				  	if($rows > 0) {
						mysql_data_seek($recGetTables, 0);
						$row_recGetTables = mysql_fetch_array($recGetTables);
					}
					?>
                      </select></td>
                      <td><table width="100%" border="0" cellspacing="0" cellpadding="0">
                        <tr>
                          <td><input name="cmdSelectTables" type="image" id="cmdSelectTables" src="images/add.png" onclick="cmdSelectTables_onclick();"></td>
                        </tr>
                        <tr>
                          <td><input name="cmdRemoveTables" type="image" id="cmdRemoveTables" src="images/remove.png" onclick="cmdRemoveTables_onclick();"></td>
                        </tr>
                      </table></td>
                      <td><select name="lstTables" size="10" multiple id="lstTables" style="width:150px;">
                        <?php
							$tmpTables = split("~",$_SESSION['selectedTables']);
							for ($x=0; $x<=count($tmpTables)-1; $x+=1) {
								if ($tmpTables[$x]!=""){
						?>
                        <option value="<?php echo $tmpTables[$x];?>"> <?php echo $tmpTables[$x];?> </option>
                        <?php
								}
	  						}
	  					?>
                      </select></td>
                    </tr>
                    
                    <tr>
                      <td height="23" colspan="3" valign="top"><input name="cmdNew" type="button" class="button" id="cmdNew" style="width:370px" onclick="cmdNew_onClick();" value="New"/></td>
                    </tr>
                    <tr>
                      <td height="19" colspan="3" valign="top"><form action="selectFields.php" method="post" name="frmTables" id="frmTables">
                          <div align="right">
                            <table width="100%" border="0" cellspacing="0" cellpadding="0">
                              <tr>
                                <td><input name="cmdBack" type="button" class="button" id="cmdBack" style="width:185px" onclick="jumpURL('index.php');" value="&lt;&lt; Back"/></td>
                                <td><input name="cmdNext" type="submit" class="button" id="cmdNext" style="width:185px" value="Next &gt;&gt;" <?php if($_SESSION['selectedTables']==""){ echo ("disabled='disabled'"); } ?>/></td>
                              </tr>
                            </table>
                            <input name="selectedTables" type="hidden" id="selectedTables" value="<?Php echo($_SESSION['selectedTables']);?>">
                          </div>
                      </form></td>
                    </tr>
                </table>
                <br></td>
              </tr>
              <tr>
                <td height="23" class="statusBar">* Select the tables that you require for your query </td>
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
mysql_free_result($recGetTables);
?>
