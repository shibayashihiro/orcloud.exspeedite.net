<?php require_once('includes/config.php'); ?>
<?php

function dmyError() {
	print "Table Not Found.";
}

mysql_select_db($database_connDB, $connDB);
$query_recGetFields = "SHOW columns FROM " . $_POST["tableName"];
$recGetFields = mysql_query($query_recGetFields, $connDB) or die(dmyError());
$row_recGetFields = mysql_fetch_array($recGetFields);
$totalRows_recGetFields = mysql_num_rows($recGetFields);
?>
<select name="lstAllFields" size="10" multiple id="lstAllFields" style="width:100%">
	<?php do {  ?>
	<option value="<?php echo ($_POST["tableName"] . ".`" . $row_recGetFields[0]) . "`"?>"><?php echo $row_recGetFields[0]?></option>
	<?php
		} while ($row_recGetFields = mysql_fetch_array($recGetFields));
	  		$rows = mysql_num_rows($recGetFields);
	 		if($rows > 0) {
		  		mysql_data_seek($recGetFields, 0);
		  		$row_recGetFields = mysql_fetch_array($recGetFields);
			}
		?>
</select>
<input name="cmdSelectFields" type="button" id="cmdSelectFields" value="Add Field" class="button" style="width:100%" onclick="cmdSelectFields_onclick();">
<?php
mysql_free_result($recGetFields);
?>

