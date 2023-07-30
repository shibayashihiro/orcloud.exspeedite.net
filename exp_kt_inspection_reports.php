<?php

// $Id: exp_kt_inspection_reports.php 4350 2021-03-02 19:14:52Z duncan $
// Show inspection reports for tractor

// Set flag that this is a parent file
define( '_STS_INCLUDE', 1 );

// Set flag that this is session readonly
define( '_STS_SESSION_READONLY', 1 );

// Setup Session
require_once( "include/sts_session_setup.php" );

require_once( "include/sts_config.php" );
$sts_debug = isset($_GET['debug']) && in_group( EXT_GROUP_DEBUG );
require_once( "include/sts_session_class.php" );

$my_session = sts_session::getInstance( $exspeedite_db, $sts_debug );
//! SCR# 506 - let mechanic see
$my_session->access_check( $sts_table_access[TRACTOR_TABLE], EXT_GROUP_MECHANIC );	// Make sure we should be here

if( isset($_GET['pw']) && $_GET['pw'] == 'WestMinster' && isset($_GET['code']) ) {
	require_once( "include/sts_tractor_class.php" );
	require_once( "include/sts_setting_class.php" );
	require_once( "include/sts_ifta_log_class.php" );
	
	$kt = sts_keeptruckin::getInstance($exspeedite_db, $sts_debug);

	$tractor_table = sts_tractor::getInstance($exspeedite_db, $sts_debug);
	$setting_table = sts_setting::getInstance($exspeedite_db, $sts_debug);
	$kt_api_key = $setting_table->get( 'api', 'KEEPTRUCKIN_KEY' );
	$kt_enabled = ! empty($kt_api_key);
	
	if( $kt_enabled ) {
		$check = $tractor_table->fetch_rows($tractor_table->primary_key." = ".$_GET['code'], "COALESCE(KT_VEHICLE_ID, 0) AS KT_VEHICLE_ID");
		if( is_array($check) && count($check) == 1 &&
			isset($check[0]["KT_VEHICLE_ID"]) && $check[0]["KT_VEHICLE_ID"] > 0 ) {
			
			$start_date = date('Y-m-d', strtotime('now - 3 month'));
			$end_date = date('Y-m-t', strtotime('now'));
			$url = 'https://api.keeptruckin.com/v1//inspection_reports?vehicle_ids%5B%5D='.
				$check[0]["KT_VEHICLE_ID"].
				'&start_date='.$start_date.'&end_date='.$end_date;

			$data = $kt->fetch( $url );

			//echo "<pre>inspection_reports\n";
			//var_dump($data);
			//echo "</pre>";
			
			if( is_array($data) && count($data) > 0 ) {
				echo '<div class="table-responsive">
					<table class="display table table-striped table-condensed table-bordered table-hover" id="KT_REPORTS">
					<thead><tr class="exspeedite-bg">
					<th>Date</th><th>Driver</th><th>Odometer</th><th>Trailers</th>
					<th>Location</th><th>Status</th><th>Defects</th>
					</tr>
					</thead>
					<tbody>';
				foreach( $data as $row ) {
					$report = $row["inspection_report"];
					$defects = array();
					if( is_array($report['defects']) && count($report['defects']) > 0 ) {
						foreach( $report['defects'] as $row2 ) {
							$defect = $row2["defect"];
							$d = $defect["area"]." / ".$defect["category"];
							if( ! empty($defect["notes"]))
								$d .= " / ".$defect["notes"];
							$defects[] = $d;
						}
					}
					
					echo '<tr>
						<td>'.date("m/d/Y", strtotime($report['date'])).'</td>
						<td>'.$report['driver']["first_name"].' '.$report['driver']["last_name"].'</td>
						<td>'.$report['odometer'].'</td>
						<td>'.implode(', ', $report['trailer_nums']).'</td>
						<td>'.$report['location'].'</td>
						<td>'.$report['status'].'</td>
						<td>'.implode('<br>', $defects).'</td>
					</tr>';
				}
				
				echo '</tbody>
			</table>
			</div>';
			} else if($data === false) {
				echo '<div class="container">
					<h2>Error Connecting to KeepTrukin</h2>
					<p>Details: '.$kt->errmsg.'</p>
					<p>This may be because you need to 
					<a href="exp_kt_oauth2.php" class="btn btn-success">re-authenticate with OAuth 2.0</a></p>
				</div>';
			} else {
				echo '<br><h4 class="text-center">No reports found</h4>';
			}
		} else {
			echo '<br><h4 class="text-center">No KeepTrukin Tractor ID for this tractor</h4>';
		}
	}
	
	
}


?>