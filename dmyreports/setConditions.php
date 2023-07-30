<?php
session_start();
?>
<?php require_once('includes/config.php'); ?>
<?php
if (isset($_POST["selectedFields"]) && $_POST["selectedFields"]!=""){
	$_SESSION['selectedFields'] = $_POST["selectedFields"]; 
}
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1">
<title>-= <?php echo $pageTitle;?> =-</title>
<link href="stylesheet.css" rel="stylesheet" type="text/css">
</head>
<script language="javascript" type="text/javascript" src="ajaxlib.js"></script>
<script language="javascript" type="text/javascript">
var lstType;
var lstFieldNames;
var lstConditions;
var lstValueType;
var inputValue;
var lstConditions;
var cmdNext;
var lstAppliedConditions;
var appliedConditions;
var txtReportName;
var lstSortName;
var lstSortOrder;
var inputType;

function initVars() {
	lstType = document.getElementById("lstType");
	lstFieldNames = document.getElementById("lstFieldNames");
	lstConditions = document.getElementById("lstConditions");
	lstValueType = document.getElementById("lstValueType");
	inputValue = document.getElementById("inputValue");
	lstConditions = document.getElementById("lstConditions");
	cmdNext = document.getElementById("cmdNext");
	lstAppliedConditions = document.getElementById("lstAppliedConditions");
	appliedConditions = document.getElementById("appliedConditions");
	txtReportName = document.getElementById("txtReportName");
	lstSortName = document.getElementById("lstSortName");
	lstSortOrder = document.getElementById("lstSortOrder");
	inputType = document.getElementById("inputType");
}

function jumpURL(tmpURL) {	
	window.location.href = tmpURL;
}

function displayInput(inputData){
	inputType.innerHTML = inputData;
}

function cmdAdd_onClick(){
	initVars();
	if(validateFields()==0){
		if (lstAppliedConditions.options.length==0){
			if (lstConditions.value!="LIKE"){
				if (lstValueType.value=="field"){
					tmpCondition = lstFieldNames.value + " " + lstConditions.value + " " + stripQuotes(inputValue.value);
				}else{
					tmpCondition = lstFieldNames.value + " " + lstConditions.value + " '" + stripQuotes(inputValue.value) + "'";
				}
			}else{
				if (lstValueType.value=="field"){
					tmpCondition = lstFieldNames.value + " " + lstConditions.value + " " + stripQuotes(inputValue.value);
				}else{
					tmpCondition = lstFieldNames.value + " " + lstConditions.value + " '%" + stripQuotes(inputValue.value) + "%'";
				}
			}
		}else{
			if (lstConditions.value!="LIKE"){
				if (lstValueType.value=="field"){
					tmpCondition = lstType.value + " " + lstFieldNames.value + " " + lstConditions.value + " " + stripQuotes(inputValue.value);
				}else{
					tmpCondition = lstType.value + " " + lstFieldNames.value + " " + lstConditions.value + " '" + stripQuotes(inputValue.value) + "'";
				}
			}else{
				if (lstValueType.value=="field"){
					tmpCondition = lstType.value + " " + lstFieldNames.value + " " + lstConditions.value + " " + stripQuotes(inputValue.value);
				}else{
					tmpCondition = lstType.value + " " + lstFieldNames.value + " " + lstConditions.value + " '%" + stripQuotes(inputValue.value) + "%'";
				}
			}
		}
	newOption = document.createElement('option');
	newText = document.createTextNode(tmpCondition);
	
	newOption.appendChild(newText);
	newOption.setAttribute("value",tmpCondition);
	
	lstAppliedConditions.appendChild(newOption);
	updateConditions();
	}
}

function updateConditions(){
	appliedConditions.value = "";

	if (lstAppliedConditions.options.length!=0){
		var splitData = lstAppliedConditions.options[0].value.split(" ");
		if (splitData[0]=='AND' || splitData[0]=='OR') {
			appliedConditions.value = (splitData[1] + splitData[2] + splitData[3]) + " ~";
			
			for (var x = 1; x <= ((lstAppliedConditions.options.length)-1); x++)
			{
				appliedConditions.value = appliedConditions.value + lstAppliedConditions.options[x].value + " ~";
			}
		}else{
			for (var x = 0; x <= ((lstAppliedConditions.options.length)-1); x++)
			{
				appliedConditions.value = appliedConditions.value + lstAppliedConditions.options[x].value + " ~";
			}
		}
	} else {
		for (var x = 0; x <= ((lstAppliedConditions.options.length)-1); x++)
		{
			appliedConditions.value = appliedConditions.value + lstAppliedConditions.options[x].value + " ~";
		}
	}
}

function validateFields(){
var errorValue = 0;
	
	if (lstType.value=="") {
		errorValue = 1;
	}
	
	if (lstFieldNames.value=="") {
		errorValue = 1;
	}
	
	if (lstConditions.value=="") {
		errorValue = 1;
	}
	
	try {	
		if (inputValue.value=="") {
			//errorValue = 1;
		}
	}catch(err){
		errorValue = 1;
	}

return errorValue;
}

function cmdRemove_onClick() {
	var selIndex = lstAppliedConditions.selectedIndex;
	var itemCount = lstAppliedConditions.options.length;
   	if(selIndex < 0)
      return;

	for (i = 0; i < itemCount; i++) {
	
		for (x = 0; x < lstAppliedConditions.options.length; x++) {
			if (lstAppliedConditions.options[x].selected) {
				lstAppliedConditions.removeChild(lstAppliedConditions.options.item(x))
			}
		}
	}
  
	updateConditions();
	if (lstAppliedConditions.options.length==0){
	}

}

function submitForm(){
	var tmpMsg = "";

	if (txtReportName.value=="") {
		tmpMsg = tmpMsg + "Report Name is Required" + "\n";
	}
	
	if (trim(lstSortName.value)!="") {
		if (trim(lstSortOrder.value)=="") {
			tmpMsg = tmpMsg + "Sort Order is Required" + "\n";
		}
	}

	if (trim(lstSortOrder.value)!="") {
		if (trim(lstSortName.value)=="") {
			tmpMsg = tmpMsg + "Sort Name is Required" + "\n";
		}
	}
	
	if (tmpMsg=="") {
	updateConditions();
	document.frmConditions.submit();
	}else{
	alert(tmpMsg);
	}

}

function trim(str)
{
 return str.replace(/^\s+|\s+$/g, ''); 
}

function stripQuotes(string){
return string.replace(/'/g,"");
}

function cmdNew_onClick() {
	var tmpVal= confirm("Please Confirm Action");
	
	if (tmpVal== true){
		window.open("newReport.php","_self");
	} 
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
            <td height="36" bgcolor="#D2E3FF" class="pageHeader"><table width="100%" border="0" cellspacing="0" cellpadding="0">
                <tr>
                  <td width="94%">&nbsp;&nbsp;<?php echo $pageTitle;?> - Set Conditions </td>
                  <td width="6%"><div id="progress"></div></td>
                </tr>
              </table></td>
          </tr>
        </table></td>
      </tr>
    </table>
        <table width="100%"  border="0" cellspacing="0" cellpadding="0">
          <tr>
            <td height="22"><table width="100%"  border="0" cellspacing="0" cellpadding="5">
              
              <tr>
                <td height="27"><table width="650" border="0" align="center" cellpadding="5" cellspacing="0" class="tableBorders">
                    <!--DWLayoutTable-->
                    <tr>
                      <td width="91" class="tableHeader"><table width="100%" border="0" cellspacing="0" cellpadding="0">
                          <tr>
                            <td width="14%"><img src="images/dash.gif" alt="Dash" width="22" height="22"> </td>
                            <td width="86%">Type </td>
                          </tr>
                        </table></td>
                      <td width="111" class="tableHeader"><table width="100%" border="0" cellspacing="0" cellpadding="0">
                          <tr>
                            <td width="14%"><img src="images/dash.gif" alt="Dash" width="22" height="22"> </td>
                            <td width="86%">Field Name</td>
                          </tr>
                        </table></td>
                      <td width="245" class="tableHeader"><table width="100%" border="0" cellspacing="0" cellpadding="0">
                          <tr>
                            <td width="14%"><img src="images/dash.gif" alt="Dash" width="22" height="22"> </td>
                            <td width="86%">Contition </td>
                          </tr>
                        </table></td>
                      <td width="133" class="tableHeader"><table width="100%" border="0" cellspacing="0" cellpadding="0">
                          <tr>
                            <td width="14%"><img src="images/dash.gif" alt="Dash" width="22" height="22"> </td>
                            <td width="86%">Value Type </td>
                          </tr>
                        </table></td>
                      <td width="111" class="tableHeader"><table width="100%" border="0" cellspacing="0" cellpadding="0">
                        <tr>
                          <td width="14%"><img src="images/dash.gif" alt="Dash" width="22" height="22"> </td>
                          <td width="86%">Input </td>
                        </tr>
                      </table>
                      </td>
                      <td width="59" class="tableHeader">&nbsp;</td>
					  </tr>
                    <tr>
                      <td><select name="lstType" id="lstType">
                          <option value="AND">AND</option>
                          <option value="OR">OR</option>
                        </select>                      </td>
                      <td><select name="lstFieldNames" id="lstFieldNames">
                    <?php
					$tmpFields = split("~",$_SESSION['selectedFields']);
					for ($x=0; $x<=count($tmpFields)-1; $x+=1) {
						if ($tmpFields[$x]!=""){
					?>
                          <option value="<?php echo $tmpFields[$x];?>">
                          <?php echo $tmpFields[$x];?>                          
						  </option>
                          <?php
	  					}
	  				}
					?>
                        </select>                      
						</td>
                      <td>
					  <select name="lstConditions" id="lstConditions">
                          <option value="=">Equal</option>
                          <option value="&lt;&gt;">Not Equal</option>
                          <option value="&gt;">Greater Than</option>
                          <option value="&lt;">Less Than</option>
                          <option value="&gt;=">Greater Than or Equal</option>
                          <option value="&lt;=">Less Than or Equal</option>
                          <option value="LIKE">Like</option>
                        </select>                      </td>
                      <td><select name="lstValueType" id="lstValueType" onchange="doAjax('getInputType.php','inputValue=' + this.value,'displayInput','post',0,'progress');">
                          <option value=""></option>
                          <option value="input">Input Value</option>
                          <option value="field">Field</option>
                        </select>                      </td>
                      <td><div id="inputType">-------</div></td>
                      <td><input name="cmdAdd" type="submit" class="button" id="cmdAdd" onclick="cmdAdd_onClick();" value="Add" /></td>
                    </tr>
                    <tr>
                      <td height="19" colspan="6" valign="top"><!--DWLayoutEmptyCell-->&nbsp;</td>
                    </tr>
                    <tr>
                      <td height="19" colspan="6" valign="top"><form id="frmConditions" name="frmConditions" method="post" action="generateSQL.php" style="margin:0px">
                          <table width="500" border="0" cellspacing="0" cellpadding="0">
                            <tr>
                              <td width="147" class="subHeader">Report Name </td>
                              <td width="353"><input name="txtReportName" type="text" id="txtReportName" value="<?php echo $_SESSION['txtReportName'];?>" size="50" /></td>
                            </tr>
                            <tr>
                              <td class="subHeader">Sort By </td>
                              <td><select name="lstSortName" id="lstSortName">
                                  <option value=""></option>
                                  <?php
									$tmpFields = split("~",$_SESSION['selectedFields']);
									for ($x=0; $x<=count($tmpFields)-1; $x+=1) {
										if ($tmpFields[$x]!=""){
	  								?>
                                  		<option value="<?php echo $tmpFields[$x];?>" <?php if (!(strcmp($tmpFields[$x], $_SESSION['lstSortName']))) {echo "selected=\"selected\"";} ?>>
                                  		<?php echo $tmpFields[$x];?>                                  
										</option>
                                  	<?php
	  									}
	  								}
									?>
                                </select>
                                  <input name="appliedConditions" type="hidden" id="appliedConditions" />                              </td>
                            </tr>
                            <tr>
                              <td class="subHeader">Sort Order </td>
                              <td><select name="lstSortOrder" id="lstSortOrder">
                                  <option value="" <?php if (!(strcmp("", $_SESSION['lstSortOrder']))) {echo "selected=\"selected\"";} ?>></option>
                                  <option value="ASC" <?php if (!(strcmp("ASC", $_SESSION['lstSortOrder']))) {echo "selected=\"selected\"";} ?>>Ascending</option>
                                  <option value="DESC" <?php if (!(strcmp("DESC", $_SESSION['lstSortOrder']))) {echo "selected=\"selected\"";} ?>>Descending</option>
                                </select>                              </td>
                            </tr>
                            <tr>
                              <td class="subHeader">Records Per Page </td>
                              <td><input name="txtRecPerPage" type="text" id="txtRecPerPage" value="<?php if ($_SESSION['txtRecPerPage']!='') { echo $_SESSION['txtRecPerPage'];} else { echo ('20'); } ?>" size="15" /></td>
                            </tr>
                            <tr>
                              <td class="subHeader">Save Report </td>
                              <td><select name="lstSave" id="lstSave">
                                  <option value="1">Yes</option>
                                  <option value="0" selected>No</option>
                                </select>                              </td>
                            </tr>
                          </table>
                      </form></td>
                    </tr>
                    <tr>
                      <td height="19" colspan="6" valign="top"><!--DWLayoutEmptyCell-->&nbsp;</td>
                    </tr>
                    <tr>
                      <td height="19" colspan="6" valign="top"><select name="lstAppliedConditions" size="5" multiple="multiple" id="lstAppliedConditions" style="width:650px">
					<?php
					$tmpCondition = split("~",$_SESSION['appliedConditions']);
					for ($x=0; $x<=count($tmpCondition)-1; $x+=1) {
						if ($tmpCondition[$x]!=""){
						?>
                          <option value="<?php echo stripslashes($tmpCondition[$x]);?>">
                          <?php echo stripslashes($tmpCondition[$x]);?>                          
						  </option>
                          <?php
	  					}
	  				}
					?>
                        </select>
					</td>
                    </tr>
                    <tr>
                      <td height="19" colspan="6" valign="top"><table width="100%" border="0" cellspacing="0" cellpadding="0">
                          <tr>
                            <td height="29"><input name="cmdNew" type="button" class="button" id="cmdNew" style="width:100%" onclick="cmdNew_onClick();" value="New"/></td>
                            <td><input name="cmdRemove" type="button" class="button" id="cmdRemove" style="width:100%" onclick="cmdRemove_onClick();" value="Remove"/></td>
                            <td><input name="cmdBack" type="button" class="button" id="cmdBack" style="width:100%" onclick="jumpURL('selectFields.php')" value="&lt;&lt; Back"/></td>
                            <td><input name="cmdNext" type="button" class="button" id="cmdNext" style="width:100%" onclick="submitForm();" value="Next &gt;&gt;"/></td>
                          </tr>
                      </table></td>
                    </tr>
                </table></td>
              </tr>
              <tr>
                <td height="23" class="statusBar">* If no conditions are applied, all records will be displayed. </td>
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