<?php 
// Set flag that this is a parent file
define( '_STS_INCLUDE', 1 );

// Setup Session
require_once( "include/sts_session_setup.php" );

require_once( "include/sts_config.php" );
$sts_debug = isset($_GET['debug']) && in_group( EXT_GROUP_DEBUG );
require_once( "include/sts_session_class.php" );

$my_session = sts_session::getInstance( $exspeedite_db, $sts_debug );
$my_session->access_check( $sts_table_access[VACATION_TABLE] );	// Make sure we should be here

$sts_subtitle = "List Vacation";
require_once( "include/header_inc.php" );
require_once( "include/navbar_inc.php" );

?>
<div class="container-full" role="main">
<?php
require_once( "include/sts_driver_class.php" );
require_once( "include/sts_vacation_class.php" );

$driver_table = sts_driver::getInstance($exspeedite_db, $sts_debug);
$drivers = $driver_table->fetch_rows("", "DRIVER_CODE, EMPLOYEE_NUMBER, DRIVER_NUMBER, 
	ISACTIVE, START_DATE, ELIGIBLE_VAC, ELIGIBLE_PERS" );

if( count($drivers) > 0 ) {
	$driver_start_date = array();
	$driver_eligible_vac = array();
	$driver_eligible_pers = array();
	
	foreach( $drivers as $driver ) {
		$driver_start_date[$driver['DRIVER_CODE']] = isset($driver['START_DATE']) && 
			$driver['START_DATE'] <> '0000-00-00' ? $driver['START_DATE'] : '';
		$driver_eligible_vac[$driver['DRIVER_CODE']] = $driver['ELIGIBLE_VAC'];
		$driver_eligible_pers[$driver['DRIVER_CODE']] = $driver['ELIGIBLE_PERS'];
	}
	
	$vacation_table = new sts_vacation_out($exspeedite_db, $sts_debug);
	$vacation_data = $vacation_table->fetch_rows("", "WE, STAFF_CODE, DRIVER_NAME, VACATION_TYPE, WE_DAYS" );
	
	if( $sts_debug ) {
		echo "<p>result = </p>
		<pre>";
		var_dump($drivers, $vacation_data);
		echo "</pre>";
	}
	
	if( count($vacation_data) > 0 ) {
		
		// Get a list of all drivers in the data
		$drivers_names = array();
		$we = array();
		$cell = array();
		foreach( $vacation_data as $row ) {
			if( ! isset($drivers_names[$row['STAFF_CODE']]) )
				$drivers_names[$row['STAFF_CODE']] = $row['DRIVER_NAME'];
			$we_formatted = date("m/d/Y", strtotime($row['WE']));
			
			if( ! isset($we[$we_formatted]) )
				$we[$we_formatted] = 1;
			$cell[$we_formatted][$row['STAFF_CODE']][$row['VACATION_TYPE']] = $row['WE_DAYS'];
		}
		
		if( $sts_debug ) {
			echo "<p>cell = </p>
			<pre>";
			var_dump($cell);
			echo "</pre>";
		}
		
		// Table header
		echo '<h3><img src="images/vacation_icon.png" alt="vacation_icon" height="24"> Vacations Report
				<div class="btn-group"><a class="btn btn-sm btn-success" href="exp_listvacation.php" ><span class="glyphicon glyphicon-plus"></span> List Vacations</a><a class="btn btn-sm btn-default" href="index.php"><span class="glyphicon glyphicon-remove"></span> Back</a>
				</div>
				</h3>
		
		<div class="table-responsive">
				<table class="display table table-striped table-condensed table-bordered table-hover" id="EXP_VACATION">
				<thead>
				<tr class="exspeedite-bg"><th>Week Ending</th>';
		foreach( $drivers_names as $driver => $name ) {
			echo '<th colspan="2"  class="text-center">'.$name.'</th>';
		}
		echo '</tr>
				<tr class="exspeedite-bg"><th>DOH</th>';
		foreach( $drivers_names as $driver => $name ) {
			echo '<th colspan="2"  class="text-right">'.$driver_start_date[$driver].'</th>';
		}
		echo '</tr>
				<tr class="exspeedite-bg"><th></th>';
		foreach( $drivers_names as $driver => $name ) {
			echo '<th class="text-center">Vac</th>';
			echo '<th class="text-center">Pers</th>';
		}
		echo '</tr>
				<tr class="exspeedite-bg"><th>Eligible Days</th>';
		foreach( $drivers_names as $driver => $name ) {
			echo '<th class="text-right">'.$driver_eligible_vac[$driver].'</th>';
			echo '<th class="text-right">'.$driver_eligible_pers[$driver].'</th>';
		}
		echo '</tr>
				</thead>
				<tbody>';
		
		// Body
		foreach( $we as $we_date => $present ) {
			echo '<tr>
				<th>'.$we_date.'</th>';
			foreach( $drivers_names as $driver => $name ) {
				echo '<td class="text-right">'.(isset($cell[$we_date][$driver]['vacation']) ? $cell[$we_date][$driver]['vacation'] : '').'</td>';
				echo '<td class="text-right">'.(isset($cell[$we_date][$driver]['personal']) ? $cell[$we_date][$driver]['personal'] : '').'</td>';
				
				if( isset($cell[$we_date][$driver]['vacation'] ) )
					$driver_eligible_vac[$driver] -= $cell[$we_date][$driver]['vacation'];
		
				if( isset($cell[$we_date][$driver]['personal'] ) )
					$driver_eligible_pers[$driver] -= $cell[$we_date][$driver]['personal'];
			}
			echo '</tr>';
		}
		
		// Footer
		echo '</tbody>
				<tfoot>
				<tr class="exspeedite-bg"><th>Days Remain</th>';
		foreach( $drivers_names as $driver => $name ) {
			echo '<th class="text-right">'.$driver_eligible_vac[$driver].'</th>';
			echo '<th class="text-right">'.$driver_eligible_pers[$driver].'</th>';
		}
				
		echo '</tr>
				</tfoot>
				</table>
				</div>';
	} else
		echo '<h3><img src="images/vacation_icon.png" alt="vacation_icon" height="24"> No Vacation Data To Report</h3>';
} else
		echo '<h3><img src="images/vacation_icon.png" alt="vacation_icon" height="24"> No Driver Data To Report</h3>';
?>
</div>
<?php

require_once( "include/footer_inc.php" );
?>

