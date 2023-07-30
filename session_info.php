<?php
	if(isset($_GET["pw"]) && $_GET["pw"] == 'Fuzzy') {
		echo "<p>Session Information</p>
		<pre>";
		var_dump($_SERVER);
		var_dump($_SESSION);
		var_dump(ini_get_all());
		echo "</pre>";
	}
?>