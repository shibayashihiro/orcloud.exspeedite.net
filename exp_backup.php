<?php 
// Set flag that this is a parent file
define( '_STS_INCLUDE', 1 );

// Setup Session
require_once( "include/sts_session_setup.php" );

require_once( "include/sts_config.php" );
$sts_debug = (isset($_GET['debug'])  || isset($_POST['debug']))  && in_group( EXT_GROUP_DEBUG );
require_once( "include/sts_session_class.php" );

$my_session = sts_session::getInstance( $exspeedite_db, $sts_debug );
$my_session->access_check( EXT_GROUP_MANAGER );	// Make sure we should be here

$sts_subtitle = "Backup Stops";
require_once( "include/header_inc.php" );
require_once( "include/navbar_inc.php" );

?>

<div class="container" role="main">

<!--
<div class="alert alert-warning alert-dismissable">
  <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
  <strong>Warning!</strong> Work in progress.
</div>
-->

<div class="well  well-lg">

<?php

require_once( "include/sts_load_class.php" );

if( isset($_POST['CODE']) && isset($_POST['BACKUP']) ) {

		$load_table = sts_load::getInstance($exspeedite_db, $sts_debug);
		$result = $load_table->backup($_POST['CODE'], $_POST['BACKUP']);
		if( ! $sts_debug )
			reload_page ( "exp_viewload.php?CODE=".$_POST['CODE'] );
			
} else if( isset($_GET['CODE']) ) {
	
	$load_table = sts_load::getInstance($exspeedite_db, $sts_debug);
	//! SCR# 370 - Override - clear the driver
	if( isset($_GET['OVERRIDE']) ) {
		$load_table->update($_GET['CODE'], array('DRIVER' => 0 ));
	}
	
	$load_info = $load_table->fetch_rows( $load_table->primary_key.' = '.$_GET['CODE'],
		"CURRENT_STOP, CURRENT_STATUS, DRIVER, CARRIER,
		(SELECT CONCAT_WS(' ',D.FIRST_NAME,D.LAST_NAME)
			FROM EXP_DRIVER D
			WHERE D.DRIVER_CODE = EXP_LOAD.DRIVER LIMIT 1) AS DRIVER_NAME,
		(SELECT D.CURRENT_LOAD
			FROM EXP_DRIVER D
			WHERE DRIVER = D.DRIVER_CODE) AS DRIVER_LOAD,
		(SELECT D.LAST_LOAD
			FROM EXP_DRIVER D
			WHERE DRIVER = D.DRIVER_CODE) AS DRIVER_LAST_LOAD");
	$stops = $load_table->database->get_multiple_rows("
		SELECT STOP_CODE, SEQUENCE_NO, STOP_TYPE, SHIPMENT, CURRENT_STATUS,
		(SELECT CURRENT_STATUS FROM EXP_SHIPMENT
			WHERE SHIPMENT_CODE = SHIPMENT) AS SHIPMENT_STATUS,
		(SELECT LOAD_CODE FROM EXP_SHIPMENT
			WHERE SHIPMENT_CODE = SHIPMENT) AS SHIPMENT_LOAD,
		(SELECT CONSOLIDATE_NUM FROM EXP_SHIPMENT
			WHERE SHIPMENT_CODE = SHIPMENT) AS CONSOLIDATE_NUM
		FROM EXP_STOP
		WHERE LOAD_CODE = ".$_GET['CODE']."
		ORDER BY SEQUENCE_NO ASC");
		
	if( isset($load_info) && is_array($load_info) && count($load_info) > 0 ) {
		$current_stop = $load_info[0]['CURRENT_STOP'];
		$driver = $load_info[0]['DRIVER'];
		$carrier = $load_info[0]['CARRIER'];
		$driver_name = $load_info[0]['DRIVER_NAME'];
		$driver_load = $load_info[0]['DRIVER_LOAD'];
		$driver_last_load = $load_info[0]['DRIVER_LAST_LOAD'];
		
		//! SCR# 324 - check if driver is still available for this load
		//! SCR# 327 - need to let carrier through
		if( $carrier > 0 || isset($_GET['OVERRIDE']) ||
			($driver > 0 &&						// we have a driver
			($driver_load == $_GET['CODE'] ||	// current load
			($driver_load == 0 && $driver_last_load == $_GET['CODE']))) ) { // completed our load
		
			// Check how far back we can go. Note dropdock is an issue, as the shipment may be
			// already picked up by another load.
			$earliest_stop = 1;
			for( $c=1; $c < $current_stop; $c++ ) {
				if( $stops[$c-1]['STOP_TYPE'] == 'dropdock' &&
					$stops[$c-1]['SHIPMENT_LOAD'] <> 0 && $c < $current_stop) {
						
					// Check if the last load for this shipment is this load or not.
					$shipment_load_table = sts_shipment_load::getInstance($exspeedite_db, $sts_debug);
					$last_load = $shipment_load_table->last_load( $stops[$c-1]['SHIPMENT'] );
					if( is_array($last_load) && isset($last_load[0]) &&
						isset($last_load[0]['LOAD_CODE']) &&
						$last_load[0]['LOAD_CODE'] <> $_GET['CODE'] ) {
						$earliest_stop = $c+1;	// Push the earliest to after this stop.
					}
				} else if( ! is_null($stops[$c-1]['CONSOLIDATE_NUM']) &&
					$stops[$c-1]['CONSOLIDATE_NUM'] <> $stops[$c-1]['SHIPMENT'] &&
					$stops[$c-1]['STOP_TYPE'] == 'drop' ) {
					$earliest_stop = $c+1;	// Push the earliest to after this stop.
				}
			}
			
			if( $sts_debug )
				echo "<p>exp_backup: current = $current_stop earliest = $earliest_stop count = ".count($stops)."</p>";
			
			echo '<form role="form" class="form-horizontal" action="exp_backup.php" 
					method="post" enctype="multipart/form-data" 
					name="editstop" id="editstop">
			'.($sts_debug ? '<input name="debug" type="hidden" value="true">' : '').'
			<input name="CODE" type="hidden" value="'.$_GET['CODE'].'">
			<h2><span class="glyphicon glyphicon-backward"></span> <img src="images/load_icon.png" alt="load_icon" height="24"> Load '.$_GET['CODE'].' Backup/Undo Stops</h2>
			';
			if( $current_stop - $earliest_stop > 0 ) {
			echo '<p>This will back out changes to a previous stop. If you have dropped off to a dock, <strong>you might not be able to back up before that point</strong>, as the shipment is no longer available to return to this load.</p>
			<div class="form-group">
				<label for="BACKUP" class="col-sm-2 control-label">Backup to Stop</label>
				<div class="col-sm-3">
	';
			echo '<select class="form-control input-sm" name="BACKUP" id="BACKUP" >';
			for( $c=$earliest_stop; $c < $current_stop; $c++ ) {
				echo '<option value="'.$c.'" >'.$c.' '.$stops[$c-1]['STOP_TYPE'].($stops[$c-1]['STOP_TYPE'] <> 'stop' ? ' shipment '.$stops[$c-1]['SHIPMENT'] : '').'</option>
				';
			}
			echo '</select>
			';
	
			echo '					</div>
				<div class="col-sm-3">
					<button class="btn btn-md btn-danger" name="save" type="submit" ><span class="glyphicon glyphicon-backward"></span> Back Up</button>
					<a class="btn btn-md btn-default" href="exp_viewload.php?CODE='.$_GET['CODE'].'"><span class="glyphicon glyphicon-remove"></span> Cancel</a>
				</div>
			</div>
			';
			} else {
				echo '<p>There are no available stops to back up to. This could be due to consolidated shipments or shipments that were docked and picked up in another load.</p>
				<p>If you really have to back up this load, try the following:</p>
				<ol>
				<li>Starting with the last shipment, select the shipment and go to the BILLING screen.</li>
				<li>Unapprove the shipment, and re-approve the shipment</li>
				<li>When you get to a consolidation screen, click on the red [Del] buttons to unlink the consolidated shipments. Then click [Back] to not continue with the re-approval.</li>
				<li>Try the backup of the load again</li>
				</ol>
				 <p>If this is not clear, or you still having trouble, please click the [Support] button on the load screen and detail what help you need.</p>
				 
				<a class="btn btn-md btn-default" href="exp_viewload.php?CODE='.$_GET['CODE'].'"><span class="glyphicon glyphicon-remove"></span> Cancel</a>
				';
			}
			echo '
			</form>
			</div>
		
				';
		} else {
			echo '<h2>Driver No Longer Available</h2>
			<p>The driver <a href="exp_editdriver.php?CODE='.$driver.'">'.$driver_name.'</a> is no longer working on load <a href="exp_viewload.php?CODE='.$_GET['CODE'].'">'.$_GET['CODE'].'</a>. Backing up is not possible.</p>
			<a class="btn btn-md btn-default" href="exp_viewload.php?CODE='.$_GET['CODE'].'"><span class="glyphicon glyphicon-remove"></span> Cancel</a>
			';
			//! SCR# 370 - Override button
			if( $my_session->in_group(EXT_GROUP_ADMIN) )
				echo '<a class="btn btn-md btn-danger" href="exp_backup.php?CODE='.$_GET['CODE'].'&OVERRIDE"><span class="glyphicon glyphicon-warning-sign"></span> Override (will clear out the driver, use with caution)</a>
			';
		}
	}
}
?>

</div>
<?php

require_once( "include/footer_inc.php" );
?>

