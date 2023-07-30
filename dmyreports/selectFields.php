<?php
session_start();
if (isset($_POST["selectedTables"]) && $_POST["selectedTables"]!=""){
	$_SESSION['selectedTables'] = $_POST["selectedTables"]; 
}
?>
<?php require_once('includes/config.php'); ?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1">
<title>-= <?php echo $pageTitle;?> =-</title>
<link href="stylesheet.css" rel="stylesheet" type="text/css">
</head>
<script language="javascript" type="text/javascript" src="ajaxlib.js"></script>
<script language="javascript" type="text/javascript">
var lstSelectedFields;
var lstAllFields;
var cmdNext;
var selectedFields;
var dispFields;
var lstTables;

function initVars() {
	lstSelectedFields = document.getElementById("lstSelectedFields");
	lstAllFields = document.getElementById("lstAllFields");
	selectedFields = document.getElementById("selectedFields");
	cmdNext = document.getElementById("cmdNext");
	dispFields = document.getElementById("dispFields");
	lstTables = document.getElementById("lstTables");
	
	doAjax('getFieldNames.php','tableName=' + lstTables.options[0].value,'displayFields','post',0,'progress');
}

function cmdSelectFields_onclick() {

	initVars();
	
   	var addIndex = lstAllFields.selectedIndex;
   	if(addIndex < 0)
      return;

	for (i = 0; i < lstAllFields.options.length; i++) {
		if (lstAllFields.options[i].selected) {
		
			var tmpFound = 0;
			for (var x = 0; x <= ((lstSelectedFields.options.length)-1); x++)
			{
				if (lstSelectedFields.options[x].value == lstAllFields.options[i].value) {
					tmpFound = 1;
				}
			}
			
			if (tmpFound!=1){
				newOption = document.createElement('option');
				newText = document.createTextNode(lstAllFields.options[i].value);
				
				newOption.appendChild(newText);
				newOption.setAttribute("value",lstAllFields.options[i].value);
			
				lstSelectedFields.appendChild(newOption);
				
				updateFields();
				cmdNext.disabled=false;
			}
		}
	}
}

function cmdRemoveFields_onclick() {

	var selIndex = lstSelectedFields.selectedIndex;
	var itemCount = lstSelectedFields.options.length;
	if(selIndex < 0)
    	return;
    
	for (i = 0; i < itemCount; i++) {
	
		for (x = 0; x < lstSelectedFields.options.length; x++) {
			if (lstSelectedFields.options[x].selected) {
				lstSelectedFields.removeChild(lstSelectedFields.options.item(x))
			}
		}
	}

	updateFields();

	if (lstSelectedFields.options.length==0){
		cmdNext.disabled=true;
	}

}

function updateFields(){

	selectedFields.value = "";
	for (var x = 0; x <= ((lstSelectedFields.options.length)-1); x++)
	{
		selectedFields.value = selectedFields.value + lstSelectedFields.options[x].value + "~";
	}
}

function displayFields(fieldData){
	dispFields.innerHTML = fieldData;
}

function moveUpList() {

   if ( lstSelectedFields.length == -1) {  
      alert("There are no values which can be moved!");
   } else {
      var selected = lstSelectedFields.selectedIndex;
      if (selected == -1) {
         alert("You must select an entry to be moved!");
      } else {  
         if ( lstSelectedFields.length == 0 ) {  
            alert("There is only one entry!\nThe one entry will remain in place.");
         } else {  
            if ( selected == 0 ) {
               alert("The first entry in the list cannot be moved up.");
            } else {
               var moveText1 = lstSelectedFields[selected-1].text;
               var moveText2 = lstSelectedFields[selected].text;
               var moveValue1 = lstSelectedFields[selected-1].value;
               var moveValue2 = lstSelectedFields[selected].value;
               lstSelectedFields[selected].text = moveText1;
               lstSelectedFields[selected].value = moveValue1;
               lstSelectedFields[selected-1].text = moveText2;
               lstSelectedFields[selected-1].value = moveValue2;
               lstSelectedFields.selectedIndex = selected-1; 
			   updateFields();

            }  
         }  
      }  
   }  
}

function moveDownList() {

   if ( lstSelectedFields.length == -1) {
      alert("There are no values which can be moved!");
   } else {
      var selected = lstSelectedFields.selectedIndex;
      if (selected == -1) {
         alert("You must select an entry to be moved!");
      } else {
         if ( lstSelectedFields.length == 0 ) {
            alert("There is only one entry!\nThe one entry will remain in place.");
         } else {
            if ( selected == lstSelectedFields.length-1 ) {
               alert("The last entry in the list cannot be moved down.");
            } else {
               var moveText1 = lstSelectedFields[selected+1].text;
               var moveText2 = lstSelectedFields[selected].text;
               var moveValue1 = lstSelectedFields[selected+1].value;
               var moveValue2 = lstSelectedFields[selected].value;
               lstSelectedFields[selected].text = moveText1;
               lstSelectedFields[selected].value = moveValue1;
               lstSelectedFields[selected+1].text = moveText2;
               lstSelectedFields[selected+1].value = moveValue2;
               lstSelectedFields.selectedIndex = selected+1;
			   updateFields();
            }
         } 
      }
   }
}

function jumpURL(tmpURL) {	
	window.location.href = tmpURL;
}

function cmdNew_onClick() {
	var tmpVal= confirm("Please Confirm Action");
	
	if (tmpVal== true){
		window.open("newReport.php","_self");
	} 
}

</script>

<body onLoad="initVars();" bgcolor="#dcdedb">
<table width="701" border="0" align="center" cellpadding="0" cellspacing="0">
  <tr>
    <td width="15"><img src="images/main_window/top_left_corner.gif" width="15" height="47"></td>
    <td background="images/main_window/top.gif"><img src="images/main_window/title.gif" width="169" height="47"></td>
    <td width="18"><img src="images/main_window/top_right_corner.gif" width="18" height="47"></td>
  </tr>
  <tr>
    <td background="images/main_window/left.gif">&nbsp;</td>
    <td width="668" valign="top" bgcolor="#FBF9F9"><table width="100%"  border="0" cellspacing="0" cellpadding="0">
        <tr>
          <td width="66%"><table width="100%"  border="0" cellspacing="0" cellpadding="3">
              <tr>
                <td height="36" bgcolor="#D2E3FF" class="pageHeader"><table width="100%" border="0" cellspacing="0" cellpadding="0">
                    <tr>
                      <td width="94%">&nbsp;&nbsp;<?php echo $pageTitle;?> - Select Fields </td>
                      <td width="6%"><div id="progress"></div></td>
                    </tr>
                  </table></td>
              </tr>
          </table></td>
        </tr>
    </table>
      <table width="100%"  border="0" cellspacing="0" cellpadding="5">
        <tr>
          <td height="22">&nbsp;</td>
        </tr>
        <tr>
          <td height="27"><table width="300" border="0" align="center" cellpadding="5" cellspacing="0" class="tableBorders">
            <!--DWLayoutTable-->
            <tr>
              <td width="150" class="tableHeader"><table width="100%" border="0" cellspacing="0" cellpadding="0">
                  <tr>
                    <td width="14%"><img src="images/dash.gif" alt="Dash" width="22" height="22"> </td>
                    <td width="86%">Select Tables </td>
                  </tr>
                </table></td>
              <td width="150" class="tableHeader">&nbsp;</td>
            </tr>
            <tr>
              <td height="29" valign="top">
			  <select name="lstTables" id="lstTables" onChange="doAjax('getFieldNames.php','tableName=' + this.value,'displayFields','post',0,'progress');">
                <?php
					$tmpTables = split("~",$_SESSION['selectedTables']);
					for ($x=0; $x<=count($tmpTables)-1; $x+=1) {
						if ($tmpTables[$x]!=""){
				?>
                <option value="<?php echo $tmpTables[$x];?>" <?php if ((count($tmpTables)-1)==1){ print "selected='selected'";} ?>> <?php echo $tmpTables[$x];?> </option>
                <?php
	  					}
	  				}
	  			?>
              </select>
			  </td>
              <td valign="top"><div id="dispFields"></div></td>
            </tr>
            <tr>
              <td height="8" colspan="2" valign="top"></td>
            </tr>
            <tr>
              <td height="19" colspan="2" valign="top"><table width="300" border="0" align="center" cellpadding="0" cellspacing="0">
                  <tr>
                    <td width="270"><select name="lstSelectedFields" size="5" multiple="multiple" id="lstSelectedFields" style="width:270px">
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
                    </select></td>
                    <td width="30" valign="top"><table width="30" height="60" border="0" cellpadding="0" cellspacing="0">
                        <tr>
                          <td><div align="center">
                            <input name="cmdUp" type="image" id="cmdUp" src="images/go-up.png" onclick="moveUpList(lstSelectedFields);">
                          </div></td>
                        </tr>
                        <tr>
                          <td><div align="center">
                            <input name="cmdDown" type="image" id="cmdDown" src="images/go-down.png" onclick="moveDownList(lstSelectedFields);">
                          </div></td>
                        </tr>
                    </table></td>
                  </tr>
              </table></td>
            </tr>
            <tr>
              <td height="19" colspan="2" valign="top"><input name="cmdRemoveFields" type="submit" class="button" id="cmdRemoveFields" style="width:300px" onclick="cmdRemoveFields_onclick();" value="Remove Fields" /></td>
            </tr>
            <tr>
              <td height="19" colspan="2" valign="top"><input name="cmdNew" type="button" class="button" id="cmdNew" style="width:300px" onclick="cmdNew_onClick();" value="New"/></td>
            </tr>
            <tr>
              <td height="19" colspan="2" valign="top"><form id="frmFields" name="frmFields" method="post" action="setConditions.php">
                  <div align="right">
                    <table width="300" border="0" cellspacing="0" cellpadding="0">
                      <tr>
                        <td><input name="cmdBack" type="button" class="button" id="cmdBack" style="width:150px" onclick="jumpURL('selectTables.php');" value="&lt;&lt; Back"/></td>
                        <td><input name="cmdNext" type="submit" class="button" id="cmdNext" style="width:150px" value="Next &gt;&gt;" <?php if($_SESSION['selectedFields']==""){ echo ("disabled='disabled'"); } ?>/></td>
                      </tr>
                    </table>
                    <input name="selectedFields" type="hidden" id="selectedFields" value="<?Php echo($_SESSION['selectedFields']);?>" />
                  </div>
              </form></td>
            </tr>
          </table>
          <br></td>
        </tr>
        <tr>
          <td height="23" class="statusBar">* Select the table name and add the fields that you require for your query </td>
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