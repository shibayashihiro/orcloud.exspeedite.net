<?php 

// $Id: exp_billhistory.php 5080 2023-04-13 21:54:41Z duncan $
// Show driver pay.

// Set flag that this is a parent file
define( '_STS_INCLUDE', 1 );

// Setup Session
require_once( "include/sts_session_setup.php" );
//error_reporting(E_ALL);
require_once( "include/sts_config.php" );
$sts_debug = isset($_GET['debug']) && in_group( EXT_GROUP_DEBUG );
require_once( "include/sts_session_class.php" );

$my_session = sts_session::getInstance( $exspeedite_db, $sts_debug );
$my_session->access_check( $sts_table_access[DRIVER_TABLE] );	// Make sure we should be here

$sts_subtitle = "Settlement History";
require_once( "include/header_inc.php" );
require_once( "include/navbar_inc.php" );

?>
<div class="container-full" role="main">
<?php
require_once( "include/sts_result_class.php" );
require_once( "include/sts_driver_class.php" );
require_once( "include/sts_setting_class.php" );
require_once( "include/sts_driver_pay_master_class.php" );
require_once( "include/sts_load_class.php" );
require_once( "sts_user_log_class.php" );

$setting_table = sts_setting::getInstance($exspeedite_db, $sts_debug);

//! This removes some fields not used for QuickBooks Online
$sts_export_qb = $setting_table->get( 'api', 'EXPORT_QUICKBOOKS' ) == 'true';
$sts_qb_online = $setting_table->get( 'api', 'QUICKBOOKS_ONLINE' ) == 'true';
$sts_manual_spawn = $setting_table->get( 'api', 'QUICKBOOKS_MANUAL_SPAWN' ) == 'true';
//! Duncan - SCR# 132 - bonusable manual rates
$sts_driver_manual_bonusable = $setting_table->get( 'option', 'DRIVER_MANRATES_BONUSABLE' ) == 'true';
$sts_multi_company = $setting_table->get("option", "MULTI_COMPANY") == 'true';
//! SCR# 498 - page size for billing history
$sts_page_size = $setting_table->get("option", "DRIVER_PAY_PAGESIZE");
if( $sts_page_size == false ) $sts_page_size = 12;

$driver=new sts_table($exspeedite_db , DRIVER_PAY_MASTER , $sts_debug);
$driver_obj=new sts_table($exspeedite_db , DRIVER_TABLE , $sts_debug);
$load_obj=new sts_table($exspeedite_db , LOAD_PAY_MASTER , $sts_debug);
$sql_obj=new sts_table($exspeedite_db , LOAD_TABLE , $sts_debug);
$rate_obj=new sts_table($exspeedite_db , LOAD_PAY_RATE , $sts_debug);
$man_obj=new sts_table($exspeedite_db , LOAD_MAN_RATE , $sts_debug);
$range_obj=new sts_table($exspeedite_db , LOAD_RANGE_RATE , $sts_debug);
$pro_obj=new sts_table($exspeedite_db , PROFILE_MASTER , $sts_debug);
$hol_obj=new sts_table($exspeedite_db , HOLIDAY_TABLE , $sts_debug);
$driver_pay_master_table = sts_driver_pay_master::getInstance($exspeedite_db, $sts_debug);

$driver_table = sts_driver::getInstance($exspeedite_db, $sts_debug);
$rslt = new sts_result( $driver_table, false, $sts_debug );

// close the session here to avoid blocking
session_write_close();

//! List all settlements for a driver
//! SCR# 615 - The code used to FAIL on 2019-12-30
$start = date("Y-m-d", strtotime('monday this week -1 day'));
$end = date("Y-m-d", strtotime('sunday this week -1 day'));

if(isset($_GET['CODE'])) {
	if($_GET['CODE']!="") {
		if( ! empty($_GET['unapprove']) ) {
			if( $sts_debug ) echo "<p>billhistory: unapprove</p>"; 
	
			$driver_pay_master_table->update_week( $_GET['unapprove'], 
				array('FINALIZE_STATUS' => 'pending',
					'PAID_DATE' => 'NULL',
					'quickbooks_status_message' => '',
					'quickbooks_txnid_ap' => '') );
	
			$load_table = sts_load::getInstance($exspeedite_db, $sts_debug);
			//! update each load to 'Complete' status
			$loads = $driver_pay_master_table->get_loads( $_GET['unapprove'] );
			if( is_array($loads) && count($loads) > 0 ) {
				foreach($loads as $load) {
					$load_table->update($load, array( 'CURRENT_STATUS' => $load_table->behavior_state['complete'] ));
				}
			}

			//! SCR# 191 - log unapproval
			$check = $driver_pay_master_table->database->get_one_row("
				SELECT (SELECT CONCAT_WS(' ',FIRST_NAME, LAST_NAME)
				FROM EXP_DRIVER
				WHERE DRIVER_CODE = DRIVER_ID) AS DRIVER,
				WEEKEND_FROM, WEEKEND_TO
				FROM EXP_DRIVER_PAY_MASTER
				WHERE DRIVER_PAY_ID=".$_GET['unapprove'] );
			$details = "";
			if( is_array($check))
				$details = $check["DRIVER"].' '.date("m/d/Y", strtotime($check["WEEKEND_FROM"])).
					' - '.date("m/d/Y", strtotime($check["WEEKEND_TO"]));
			$user_log = sts_user_log::getInstance($exspeedite_db, $sts_debug);
			$user_log->log_event('finance', 'Unapproved Driver Pay '.$details);

			reload_page( "exp_billhistory.php?CODE=".$_GET['CODE']);
		}

		if( ! empty($_GET['resend']) ) {
			if( $sts_debug ) echo "<p>billhistory: resend</p>"; 
	
			$driver_pay_master_table->update_week( $_GET['resend'], 
				array('FINALIZE_STATUS' => 'finalized',
					'PAID_DATE' => 'NULL',
					'quickbooks_status_message' => '',
					'quickbooks_txnid_ap' => '') );
	
			if( $sts_export_qb ) {
				require_once( $sts_crm_dir."quickbooks-php-master/QuickBooks.php" );
		
				// Queue up the Quickbooks API request
				$Queue = new QuickBooks_WebConnector_Queue($sts_qb_dsn);
				$Queue->enqueue(QUICKBOOKS_QUERY_VENDOR, $_GET['resend'], 0, 
					array( 'vendortype' => QUICKBOOKS_VENDOR_DRIVER ) );
		
				if( $sts_qb_online && ! $sts_manual_spawn)	//! Needed for QBOE only
					require_once( $sts_crm_dir.DIRECTORY_SEPARATOR.
						"quickbooks-php-master".
						DIRECTORY_SEPARATOR."online".
						DIRECTORY_SEPARATOR."spawn_process.php" );
			}
	
			reload_page( "exp_billhistory.php?CODE=".$_GET['CODE']);
		}
		
		//! SCR# 498 - paging for billing history
		$data['id']=$driver_id=$_GET['CODE'];
		$check=$driver->database->get_one_row(
			"SELECT COUNT(*) NUM FROM (
				SELECT DRIVER_PAY_ID FROM EXP_DRIVER_PAY_MASTER
				WHERE DRIVER_ID=".$driver_id."
				GROUP BY WEEKEND_FROM,WEEKEND_TO
				ORDER BY WEEKEND_FROM DESC) X");
		$data['paging_totalrows'] = is_array($check) && count($check) == 1 &&
			isset($check["NUM"]) ? intval($check["NUM"]) : 0;
		if( $sts_debug ) echo "<p>billhistory: paging, total rows = ".$data['paging_totalrows']."</p>"; 
				
		$data['paging_pagesize'] = $sts_page_size;
		$data['paging_offset'] = isset($_GET['offset']) ? intval($_GET['offset']) : 0;
		if($data['paging_offset'] < 0 || $data['paging_offset'] > $data['paging_totalrows'])
			$data['paging_offset'] = 0;
		if( $sts_debug ) echo "<p>billhistory: paging, offset = ".$data['paging_offset'].", size = ".$data['paging_pagesize']."</p>"; 
		
		$data['driver_info']=$driver->database->get_multiple_rows(
			"SELECT * FROM ".DRIVER_PAY_MASTER."
			WHERE DRIVER_ID=".$driver_id."
			GROUP BY WEEKEND_FROM,WEEKEND_TO
			ORDER BY WEEKEND_FROM DESC
			LIMIT ".$data['paging_offset'].", ".$data['paging_pagesize'] );
		
		if( is_array($data['driver_info']) && count($data['driver_info']) > 0) {
			foreach($data['driver_info'] as $row) {

				//! SCR# 508 - check driver pay for a given period for missing data
				// Too many emails if this is enabled
				//$driver_table->check_driver_pay( $driver_id, $row["WEEKEND_FROM"], $row["WEEKEND_TO"] );

				$data['load_info'][$row["WEEKEND_FROM"]]=$driver->database->get_multiple_rows(
					"SELECT DRIVER_PAY_ID, LOAD_ID, LOAD_OFFICE_NUM(LOAD_ID) AS OFFICE_NUM,
					(select min(ACTUAL_ARRIVE) from exp_stop
					where load_code = load_id) as first_arrive,
					(select max(ACTUAL_DEPART) from exp_stop
					where load_code = load_id) as last_depart,
					(select total_miles from ".LOAD_PAY_MASTER."
					where ".DRIVER_PAY_MASTER.".load_id = ".LOAD_PAY_MASTER.".load_id
					limit 1) as trip_distance,
					ADDED_ON, TRIP_PAY, BONUS, HANDLING, GROSS_EARNING
					FROM ".DRIVER_PAY_MASTER." WHERE DRIVER_ID=".$driver_id."
					AND WEEKEND_FROM = DATE('".$row["WEEKEND_FROM"]."')
					AND WEEKEND_TO = DATE('".$row["WEEKEND_TO"]."') ");
			}
		}
		
		$data['previous']=$driver->database->get_multiple_rows(
			"SELECT DRIVER_PAY_ID
			FROM ".DRIVER_PAY_MASTER."
			WHERE WEEKEND_FROM='".$start."'
			AND WEEKEND_TO='".$end."'
			AND DRIVER_ID='$driver_id'
			AND FINALIZE_STATUS='pending'");

		$driver_name=$driver_obj->database->get_one_row(
			"SELECT FIRST_NAME,MIDDLE_NAME,LAST_NAME
			FROM ".DRIVER_TABLE."
			WHERE DRIVER_CODE=".$driver_id);
		/*f(is_array($driver_name))
		{echo count(array_filter($driver_name));}*/
		$data['drivername']='';
		if(isset($driver_name['FIRST_NAME'])) {
			$data['drivername']=$driver_name['FIRST_NAME'].' '.$driver_name['MIDDLE_NAME'].' '.$driver_name['LAST_NAME'];
		}
		
		//! Duncan - check if there are any driver rates set up.
		$dar=$driver_obj->database->get_one_row(
			"SELECT COUNT(*) AS NUM
			FROM EXP_DRIVER_ASSIGN_RATES
			WHERE DRIVER_ID = ".$driver_id);
		$drr=$driver_obj->database->get_one_row(
			"SELECT COUNT(*) AS NUM
			FROM EXP_DRIVER_RANGE_RATES
			WHERE DRIVER_ID = ".$driver_id);
		$dmr=$driver_obj->database->get_one_row(
			"SELECT COUNT(*) AS NUM
			FROM EXP_DRIVER_MANUAL_RATES
			WHERE DRIVER_ID = ".$driver_id);
		if( is_array($dar) && is_array($drr) && is_array($dmr) &&
			isset($dar["NUM"]) && isset($drr["NUM"]) && isset($dmr["NUM"]) &&
			$dar["NUM"] + $drr["NUM"] + $dmr["NUM"] == 0 ) {
			$data['norates']=true;	// Tells us no rates set
		}
		
		//! SCR# 421 - Disable button if already done this week
		$tmp=$driver->database->get_one_row("
			SELECT count(*) AS NUM FROM ".DRIVER_PAY_MASTER."
			WHERE WEEKEND_FROM='".$start."'
			AND WEEKEND_TO='".$end."'
			AND DRIVER_ID=$driver_id
			AND FINALIZE_STATUS in ('finalized', 'paid')");
		$data['DoneThisWeek'] = is_array($tmp) && count($tmp) ==1 &&
			isset($tmp["NUM"]) && intval($tmp["NUM"]) > 0;
		
		$data['start']=$start;
		$data['end']=$end;
		$data['multi_company'] = $sts_multi_company;
		echo $rslt->render_bill_history( $data, $my_session, $sts_qb_online );
	}
}

########   View all settlement details of a particular week ###############
if(isset($_GET['TYPE']) && isset($_GET['ID'])) {
	if($_GET['TYPE']=='view' && $_GET['ID']!="") {
	
		$res['detail']=$driver->database->get_one_row("
			SELECT * FROM ".DRIVER_PAY_MASTER."
			WHERE DRIVER_PAY_ID=".$_GET['ID']);

		//! SCR# 508 - check driver pay for a given period for missing data
		$driver_table->check_driver_pay( $res['detail']['DRIVER_ID'],
			$res['detail']['WEEKEND_FROM'], $res['detail']['WEEKEND_TO'] );

		$res['driver']=$driver_obj->database->get_one_row("
			SELECT FIRST_NAME, LAST_NAME, MIDDLE_NAME, PROFILE_ID
			FROM ".DRIVER_TABLE."
			WHERE DRIVER_CODE=".$res['detail']['DRIVER_ID']);
		$contract_name="";
		if(isset($res['driver']['PROFILE_ID']) && $res['driver']['PROFILE_ID']!=0) {
			$contract_name_arr=$pro_obj->database->get_one_row("
				SELECT PROFILE_NAME
				FROM ".PROFILE_MASTER."
				WHERE ".PROFILE_MASTER.".CONTRACT_ID=".$res['driver']['PROFILE_ID']." ");
			$contract_name=$contract_name_arr['PROFILE_NAME'];
		}
		$res['contract_name']=$contract_name;

	$res['DRIVER_PAY_ID']=$_GET['ID'];
	echo $rslt->render_history_of_week( $res );
	if($res['detail']['DRIVER_ID']!="") {
		if($start==$res['detail']['WEEKEND_FROM'] && $end==$res['detail']['WEEKEND_TO']) {
			$load_list=$driver->database->get_multiple_rows("
				SELECT LOAD_ID,TRIP_ID FROM ".DRIVER_PAY_MASTER."
				WHERE WEEKEND_FROM='".date('Y-m-d',strtotime($res['detail']['WEEKEND_FROM']))."'
				AND WEEKEND_TO='".date('Y-m-d',strtotime($res['detail']['WEEKEND_TO']))."'
				AND FINALIZE_STATUS in('finalized', 'paid')
				AND DRIVER_ID=".$res['detail']['DRIVER_ID']);
		} else {
			$load_list=$driver->database->get_multiple_rows("
				SELECT LOAD_ID,TRIP_ID, TRIP_PAY, GROSS_EARNING FROM ".DRIVER_PAY_MASTER."
				WHERE WEEKEND_FROM='".date('Y-m-d',strtotime($res['detail']['WEEKEND_FROM']))."'
				AND WEEKEND_TO='".date('Y-m-d',strtotime($res['detail']['WEEKEND_TO']))."'
				AND DRIVER_ID=".$res['detail']['DRIVER_ID']);
		}
	
	$driver_assign_load_with_stop=array();
	//print_r($load_list);
	if(count($load_list)>0) {
		$i=0;
		foreach($load_list as $load) {
		$output = array();
		$output['load_id']=$load['LOAD_ID'];
			
			if($load['LOAD_ID']!=0) {
			$output['load_detail']=$load_obj->database->get_one_row("
				SELECT *, LOAD_OFFICE_NUM(LOAD_ID) AS OFFICE_NUM
				FROM ".LOAD_PAY_MASTER."
				WHERE LOAD_ID=".$load['LOAD_ID']."
				AND DRIVER_ID=".$res['detail']['DRIVER_ID']);
			
			$output['rates']=$rate_obj->database->get_multiple_rows("
				SELECT * FROM ".LOAD_PAY_RATE."
				WHERE LOAD_ID=".$load['LOAD_ID']."
				AND DRIVER_ID=".$res['detail']['DRIVER_ID']);
				
			$output['manual_rates']=$man_obj->database->get_multiple_rows("
				SELECT * FROM ".LOAD_MAN_RATE."
				WHERE LOAD_ID=".$load['LOAD_ID']."
				AND DRIVER_ID=".$res['detail']['DRIVER_ID']);
			$output['range_rates']=$range_obj->database->get_multiple_rows("
				SELECT * FROM ".LOAD_RANGE_RATE."
				WHERE LOAD_ID=".$load['LOAD_ID']."
				AND DRIVER_ID=".$res['detail']['DRIVER_ID']);
			$stop_detail=$sql_obj->database->get_multiple_rows("
				SELECT STOP_CODE, STOP_TYPE, SEQUENCE_NO, 
                    (CASE
                                    WHEN STOP_TYPE = 'pick' THEN SHIPPER_ADDR1
                                    WHEN STOP_TYPE = 'drop' THEN CONS_ADDR1
                                    ELSE NULL END ) AS ADDR1,
                    (CASE
                                    WHEN STOP_TYPE = 'pick' THEN SHIPPER_ADDR2
                                    WHEN STOP_TYPE = 'drop' THEN CONS_ADDR2
                                    ELSE NULL END ) AS ADDR2,
                    (CASE
                                    WHEN STOP_TYPE = 'pick' THEN SHIPPER_CITY
                                    WHEN STOP_TYPE = 'drop' THEN CONS_CITY
                                    ELSE NULL END ) AS CITY,
                    (CASE
                                    WHEN STOP_TYPE = 'pick' THEN SHIPPER_STATE
                                    WHEN STOP_TYPE = 'drop' THEN CONS_STATE
                                    ELSE NULL END ) AS STATE,
                    (CASE
                                    WHEN STOP_TYPE = 'pick' THEN SHIPPER_ZIP
                                    WHEN STOP_TYPE = 'drop' THEN CONS_ZIP
                                    ELSE NULL END ) AS ZIP,
					(CASE
                                    WHEN STOP_TYPE = 'pick' THEN SHIPPER_NAME
                                    WHEN STOP_TYPE = 'drop' THEN CONS_NAME
                                    ELSE NULL END ) AS NAME,
                    ACTUAL_ARRIVE, ACTUAL_DEPART
                    
                    FROM EXP_STOP
                     JOIN EXP_SHIPMENT
                    ON EXP_SHIPMENT.SHIPMENT_CODE = EXP_STOP.SHIPMENT
                    
                    WHERE EXP_STOP.LOAD_CODE = ".$load['LOAD_ID']."
                    AND STOP_TYPE IN ('pick','drop')
                    AND SEQUENCE_NO <= (SELECT 
                    (CASE WHEN CURRENT_STOP = (SELECT COUNT(*) AS NUM_STOPS
                                    FROM EXP_STOP
                                    WHERE EXP_LOAD.LOAD_CODE = EXP_STOP.LOAD_CODE) THEN CURRENT_STOP
                    ELSE CURRENT_STOP - 1 END) AS CURRENT_STOP
                    FROM EXP_LOAD 
                    WHERE EXP_LOAD.LOAD_CODE = EXP_STOP.LOAD_CODE)
                    order by SEQUENCE_NO ASC
");
//print_r($stop_detail);
$all_stop_detail=array();
if(count($stop_detail)>0) {
	$i=0;
	foreach($stop_detail as $st) {
		$all_stop_detail[$i]=array();
		$all_stop_detail[$i]=$st;
		
		$all_stop_detail[$i]['HOLIDAY']=$all_stop_detail[$i]['ARRIVAL_HOLIDAY']='';
		if($st['ACTUAL_DEPART']!="") {
			$holiday=$hol_obj->database->get_one_row("
				SELECT HOLIDAY_NAME FROM ".HOLIDAY_TABLE."
				WHERE HOLIDAY_DATE='".date('Y-m-d',strtotime($st['ACTUAL_DEPART']))."'
				AND PAID = 1");
			
			if(is_array($holiday) && count($holiday)>0 && isset($holiday['HOLIDAY_NAME']))
				$all_stop_detail[$i]['HOLIDAY']=$holiday['HOLIDAY_NAME'];
			
		}
		
		if($st['ACTUAL_ARRIVE']!="") {
			$arr_holiday=$hol_obj->database->get_one_row("
				SELECT HOLIDAY_NAME FROM ".HOLIDAY_TABLE."
				WHERE HOLIDAY_DATE='".date('Y-m-d',strtotime($st['ACTUAL_ARRIVE']))."'
				AND PAID = 1");
			
			if(is_array($arr_holiday) && count($arr_holiday)>0 && isset($arr_holiday['HOLIDAY_NAME']))
				$all_stop_detail[$i]['ARRIVAL_HOLIDAY']=$arr_holiday['HOLIDAY_NAME'];
			
		}
		//print_r($all_stop_detail[$i]);
		$i++;
   }
}
//echo '<pre/>';print_r($all_stop_detail);
$output['STOP_DETAIL']=$all_stop_detail;
			} else {
				//! Get BONUS_AMOUNT here
				$output['load_detail']=$load_obj->database->get_one_row("
					SELECT * FROM ".LOAD_PAY_MASTER."
					WHERE TRIP_ID=".$load['TRIP_ID']."
					AND DRIVER_ID=".$res['detail']['DRIVER_ID']);

				//! SCR# 508 - fabricate missing data
				if( ! is_array($output['load_detail']) || count($output['load_detail']) == 0) {
					$output['load_detail']=array( 'TOTAL_TRIP_PAY' => $load['TRIP_PAY'],
					'BONUS' => 'No', 'APPLY_BONUS' => 0, 'BONUS_AMOUNT' => 0,
					'HANDLING_PALLET' => 0, 'HANDLING_PAY' => 0, 'LOADED_DET_HR' => 0,
					'UNLOADED_DET_HR' => 0, 'TOTAL_SETTLEMENT' => $load['GROSS_EARNING']
					);
				}
				
				$output['rates']=$rate_obj->database->get_multiple_rows("
					SELECT * FROM ".LOAD_PAY_RATE."
					WHERE TRIP_ID=".$load['TRIP_ID']."
					AND DRIVER_ID=".$res['detail']['DRIVER_ID']);

				$output['manual_rates']=$man_obj->database->get_multiple_rows("
					SELECT * FROM ".LOAD_MAN_RATE."
					WHERE TRIP_ID=".$load['TRIP_ID']."
					AND DRIVER_ID=".$res['detail']['DRIVER_ID']);

				$output['range_rates']=$range_obj->database->get_multiple_rows("
					SELECT * FROM ".LOAD_RANGE_RATE."
					WHERE TRIP_ID=".$load['TRIP_ID']."
					AND DRIVER_ID=".$res['detail']['DRIVER_ID']);

				$output['STOP_DETAIL']=array();
			}
			
			//! Duncan - SCR# 132 - bonusable manual rates
			$output["MANUAL_BONUSABLE"] = $sts_driver_manual_bonusable;

			$output['info']=$sql_obj->database->get_one_row("
				SELECT ".TRACTOR_TABLE.".UNIT_NUMBER,
				".TRAILER_TABLE.".UNIT_NUMBER AS TRAILER_NUMBER,
				".SHIPMENT_TABLE.".CONS_NAME,
				".LOAD_TABLE.".TOTAL_DISTANCE,
				COALESCE( (SELECT GROUP_CONCAT( DISTINCT SHIPMENT_CODE
				ORDER BY SHIPMENT_CODE ASC SEPARATOR ', ')
			    FROM EXP_SHIPMENT WHERE EXP_SHIPMENT.LOAD_CODE = EXP_LOAD.LOAD_CODE),
	            (SELECT GROUP_CONCAT( DISTINCT SHIPMENT
				ORDER BY SHIPMENT ASC SEPARATOR ', ')
			    FROM EXP_STOP WHERE EXP_STOP.LOAD_CODE = EXP_LOAD.LOAD_CODE
			    AND EXP_STOP.IM_STOP_TYPE != 'reposition')) AS SHIPMENTS
				FROM ".LOAD_TABLE." 
				LEFT JOIN ".TRACTOR_TABLE."
					ON ".TRACTOR_TABLE.".TRACTOR_CODE=".LOAD_TABLE.".TRACTOR  
				LEFT JOIN ".TRAILER_TABLE."
					ON ".TRAILER_TABLE.".TRAILER_CODE=".LOAD_TABLE.".TRAILER 
				LEFT JOIN ".SHIPMENT_TABLE."
					ON ".SHIPMENT_TABLE.".LOAD_CODE = ".LOAD_TABLE.".LOAD_CODE 
				WHERE ".LOAD_TABLE.".LOAD_CODE=".$load['LOAD_ID']);
			
			//! Duncan - calculate line haul
			$linehaul = $sql_obj->database->get_one_row("
				SELECT SUM(B.FREIGHT_CHARGES + B.STOP_OFF + B.DETENTION_RATE + B.UNLOADED_DETENTION_RATE) AS LINE_HAUL,
				SUM(B.FUEL_COST) FUEL_COST
				FROM EXP_SHIPMENT S
				LEFT JOIN EXP_CLIENT_BILLING B
				ON S.SHIPMENT_CODE = B.SHIPMENT_ID
				WHERE LOAD_CODE = ".$load['LOAD_ID'] );
			
			$output['load_detail']["LINE_HAUL"] = isset($linehaul["LINE_HAUL"]) ? $linehaul["LINE_HAUL"] : 0;
			$output['load_detail']["FUEL_COST"] = isset($linehaul["FUEL_COST"]) ? $linehaul["FUEL_COST"] : 0;
			
			$output['multi_company'] = $sts_multi_company;
			echo $rslt->render_load_detail( $output );
			//echo '<pre/>';print_r($output);
		}
	}
	}
	$fetch_totals=$fetch_totals_trip=array();
	//! Get BONUS_AMOUNT here
	$fetch_totals=$driver->database->get_multiple_rows("
		SELECT TRIP_PAY, BONUS, HANDLING, GROSS_EARNING, ADDED_ON
		FROM ".DRIVER_PAY_MASTER."
		WHERE WEEKEND_FROM='".date('Y-m-d',strtotime($res['detail']['WEEKEND_FROM']))."'
		AND WEEKEND_TO='".date('Y-m-d',strtotime($res['detail']['WEEKEND_TO']))."'
		AND DRIVER_ID=".$res['detail']['DRIVER_ID']."
		AND FLAG='0' AND LOAD_ID!=0"); //GROUP BY UNIQUE_ID
	
	$fetch_totals_trip=$driver->database->get_multiple_rows("
		SELECT TRIP_PAY, BONUS, HANDLING, GROSS_EARNING, ADDED_ON
		FROM ".DRIVER_PAY_MASTER."
		WHERE WEEKEND_FROM='".date('Y-m-d',strtotime($res['detail']['WEEKEND_FROM']))."'
		AND WEEKEND_TO='".date('Y-m-d',strtotime($res['detail']['WEEKEND_TO']))."'
		AND DRIVER_ID=".$res['detail']['DRIVER_ID']."
		AND FLAG='1'
		AND TRIP_ID!=0"); //GROUP BY UNIQUE_ID
	//echo '<pre/>';print_r($fetch_totals_trip);
	//echo '<pre/>';print_r($fetch_totals);
	
	$res['fetch_totals']=$fetch_totals;
	$res['fetch_totals_trip']=$fetch_totals_trip;
	
	echo $rslt->render_bill_historty_end( $res );
}
}

?>
</div>
<?php

require_once( "include/footer_inc.php" );
?>

<script type="text/javascript" language="javascript">

function delete_settlement_history(history_id,driver_id)
{
	if(history_id!="" && driver_id!="")
	{
		if(confirm("Do you really want to delete this record?"))
		{
		$.ajax({
			    url:'exp_save_rates.php?action=deletesettlement&id='+history_id+'&driver_id='+driver_id,
				type:'POST',
				success:function(res)
				{
				window.location.href='exp_billhistory.php?CODE='+driver_id;
					/*if(res.trim()=='OK')
					{window.location.href='exp_billhistory.php?CODE='+driver_id;
					}*/
				}
		});
		}
		else
		{
			return false;
		}
	}
}
</script>

<?php

require_once( "include/footer_inc.php" );
?>
