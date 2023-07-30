<?php 

// $Id: exp_adddriverpay.php 5110 2023-05-22 17:36:50Z duncan $
// Enter driver pay.

// Set flag that this is a parent file
define( '_STS_INCLUDE', 1 );
set_time_limit(0);
ini_set('memory_limit', '1024M');

// Setup Session
require_once( "include/sts_session_setup.php" );

require_once( "include/sts_config.php" );
$sts_debug = (isset($_GET['debug']) || isset($_POST['debug'])) && in_group( EXT_GROUP_DEBUG );
require_once( "include/sts_session_class.php" );

$my_session = sts_session::getInstance( $exspeedite_db, $sts_debug );
$my_session->access_check( EXT_GROUP_USER );	// Make sure we should be here

$sts_subtitle = "Add Driver Pay";
require_once( "include/header_inc.php" );
require_once( "include/navbar_inc.php" );
?>
<div class="container-full" role="main">
<?php
require_once( "include/sts_result_class.php" );
require_once( "include/sts_driverrate_mng_class.php");
require_once( "include/sts_setting_class.php" );

$sql_obj	=	new sts_table($exspeedite_db , LOAD_PAY_MASTER , $sts_debug);
$pay_obj=new sts_table($exspeedite_db , LOAD_PAY_RATE , $sts_debug);
$man_obj=new sts_table($exspeedite_db , LOAD_MAN_RATE , $sts_debug);
$range_obj=new sts_table($exspeedite_db , LOAD_RANGE_RATE , $sts_debug);
$driver=new sts_table($exspeedite_db , DRIVER_PAY_MASTER , $sts_debug);
$pro_obj=new sts_table($exspeedite_db , PROFILE_MASTER , $sts_debug);
$stop_obj=new sts_table($exspeedite_db , STOP_TABLE , $sts_debug);
$ship_obj=new sts_table($exspeedite_db , SHIPMENT_TABLE , $sts_debug);
$hol_obj=new sts_table($exspeedite_db , HOLIDAY_TABLE , $sts_debug);

$driverpay_obj =	new sts_driverrate_mng($exspeedite_db , $sts_debug);
$res_obj = new sts_result( $driverpay_obj , false, $sts_debug );

//! Duncan - SCR# 132 - bonusable manual rates
$setting_table = sts_setting::getInstance($exspeedite_db, $sts_debug);
$sts_driver_manual_bonusable = $setting_table->get( 'option', 'DRIVER_MANRATES_BONUSABLE' ) == 'true';
$sts_multi_company = $setting_table->get("option", "MULTI_COMPANY") == 'true';
$sts_extra_stops = $setting_table->get( 'option', 'CLIENT_EXTRA_STOPS' ) == 'true';
//! SCR# 457 - Driver pay - default distance for load based on shipment client billing
$sts_dist_cbill = $setting_table->get( 'option', 'DPAY_DEFDISTANCE_CBILL' ) == 'true';


// close the session here to avoid blocking
session_write_close();

#get driver Id 
if( isset($_GET) ) {
	$res = [];
	$res['debug'] = $sts_debug;

	$res['id']=$id	=	$_GET['id'];
	
	#get driver 

	//! SCR# 615 - The code used to FAIL on 2019-12-30
	$start = date("Y-m-d", strtotime('monday this week -1 day'));
	$end = date("Y-m-d", strtotime('sunday this week -1 day'));
	
	if( isset($_GET["start_date"]) && isset($_GET["end_date"])) {
		$start = $_GET["start_date"];
		$end = $_GET["end_date"];
	}
	
	if( isset($_POST["start_date"]) && isset($_POST["end_date"])) {
		$start = $_POST["start_date"];
		$end = $_POST["end_date"];
	}

	$res['start_date']=$start;
	$res['end_date']=$end;
	
	//! SCR# 615 - Don't let you add to finalized or paid
	$previousResult=$driver->database->get_multiple_rows("
		SELECT * FROM ".DRIVER_PAY_MASTER."
		WHERE WEEKEND_FROM='".date("Y-m-d", strtotime($start))."'
		AND WEEKEND_TO='".date("Y-m-d", strtotime($end))."'
		AND DRIVER_ID=$id
		AND finalize_status in ('finalized', 'paid')");
	if(is_array($previousResult) && count($previousResult)>0) {
		//print_r($previousResult);exit;
		echo '<script>window.location.href="exp_billhistory.php?TYPE=view&ID='.$previousResult[0]['DRIVER_PAY_ID'].'"</script>';
	}

	if( ! isset($_POST) && ! isset($_GET["forceadd"]) ) {
		// If there are pending entries, we need to redirect to exp_updatedriverpay.php to edit them.
		// 3/8 - Duncan, removed unnecessary quotes around numbers.
		$previousResult=$driver->database->get_multiple_rows("
			SELECT * FROM ".DRIVER_PAY_MASTER."
			WHERE WEEKEND_FROM='".date("Y-m-d", strtotime($start))."'
			AND WEEKEND_TO='".date("Y-m-d", strtotime($end))."'
			AND DRIVER_ID=$id
			AND FINALIZE_STATUS='pending'");
		if(is_array($previousResult) && count($previousResult)>0) {
			//print_r($previousResult);exit;
			echo '<script>window.location.href="exp_updatedriverpay.php?payid='.$previousResult[0]['DRIVER_PAY_ID'].'"</script>';
		}
	}

	$res['back'] = '';
	if(isset($_GET['back']) && $_GET['back'] <> '' ) {
		$res['back'] = $_GET['back'];
	}
	
	//! --------------------- WRITE ALL DATA ---------------------
	if(isset($_POST['savepayrate'])) { 
		//echo '<pre/>';print_r($_POST);exit;
		//echo $_POST['approved_date'.$_POST["load_id"][0]];
		//echo '************'.print_r($_POST['approved_date'.$_POST["load_id"][0]]);
		
		if( $sts_debug ) {
			echo "<pre>SAVE-POST\n";
			var_dump($_POST);
			echo "</pre>";
		}
		
		$i=0;
		if(isset($_POST['load_id']) && count($_POST['load_id'])>0) {
			$max_unique_id=0;
			$max_unique=$driver->database->get_one_row("
				SELECT MAX(UNIQUE_ID) AS UNIQ
				FROM ".DRIVER_PAY_MASTER."");
			if($max_unique['UNIQ']!="") {
				$max_unique_id=$max_unique['UNIQ']+1;
			}
			if( $sts_debug ) {
				echo "<pre>max_unique_id\n";
				var_dump($max_unique_id);
				echo "</pre>";
			}
		
			foreach($_POST['load_id'] as $load_id) {
				$load_id=$load_id;
				$get_count=$driver->database->get_one_row(
					"SELECT COUNT(*) AS CNT 
					FROM ".DRIVER_PAY_MASTER." 
					WHERE WEEKEND_FROM='".date("Y-m-d", strtotime($start))."' 
					AND WEEKEND_TO='".date("Y-m-d", strtotime($end))."' 
					AND LOAD_ID=$load_id
					AND DRIVER_ID=$id");
				if($load_id==0) {
					$get_count['CNT']=0;
					$trip=$_POST['tripID'];
				} else {
					$trip=0;
				}
	
				if($get_count['CNT']==0) {
					$approved_date=$_POST['approved_date'.$load_id];
					$bonus_allow=$_POST['bonus_allow'][$i];
					$apply_bonus=floatval($_POST['apply_bonus'][$i]);
					$bonus_amount=floatval($_POST['bonus_amount'][$i]);
					$handling_pay=floatval($_POST['handling_pay'][$i]);
					$handling_pallet=intval($_POST['handling_pallet'][$i]);
					$total_trip_amount=floatval($_POST['total_trip_amount'][$i]);
					$total_settelment=floatval($_POST['totla_settelment'][$i]);
					$odometer_from= empty($_POST['odometer_from'][$i]) ? 0 : $_POST['odometer_from'][$i];
					$odometer_to= empty($_POST['odometer_to'][$i]) ? 0 : $_POST['odometer_to'][$i];
					$total_miles=floatval($_POST['total_miles'][$i]);
					$loaded_det_hr=floatval($_POST['loaded_det_hr'][$i]);
					$unloaded_det_hr=floatval($_POST['unloaded_det_hr'][$i]);
					
					##----------- INSERT INTO LOAD MASTER----------------##
					// 3/8 - Duncan, removed unnecessary quotes around numbers.
					$sql_obj->add_row("LOAD_ID, APPROVED_DATE, ODOMETER_FROM,
						ODOMETER_TO, TOTAL_MILES, BONUS,
						APPLY_BONUS, BONUS_AMOUNT, TOTAL_TRIP_PAY,
						HANDLING_PAY, TOTAL_SETTLEMENT ,DRIVER_ID,
						TRIP_ID, HANDLING_PALLET, LOADED_DET_HR,
						UNLOADED_DET_HR",
					"$load_id, '".date('Y-m-d',strtotime($approved_date))."', $odometer_from,
					$odometer_to, $total_miles, '$bonus_allow',
					$apply_bonus, $bonus_amount, $total_trip_amount,
					$handling_pay, $total_settelment, $id,
					$trip, $handling_pallet, $loaded_det_hr,
					'$unloaded_det_hr'");
				
					##----------- INSERT ALL PAY RATES ---------##
					if(isset($_POST['category_name'.$load_id])) {
						 $total_len=count($_POST['category_name'.$load_id]);
						if($total_len>0) {
							for($j=0;$j<$total_len;$j++) {
								$title=empty($_POST['category_name'.$load_id][$j]) ? '' :
									$_POST['category_name'.$load_id][$j];
								$quantity=empty($_POST[$load_id.'_qty'][$j]) ? 0 :
									floatval($_POST[$load_id.'_qty'][$j]);
								$free_hours=empty($_POST['free_hours'.$load_id][$j]) ? 0 :
									floatval($_POST['free_hours'.$load_id][$j]);
								$rate=empty($_POST[$load_id.'_amt'][$j]) ? 0 :
									floatval($_POST[$load_id.'_amt'][$j]);
								$pay=empty($_POST['text_total'.$load_id][$j]) ? 0 :
									floatval($_POST['text_total'.$load_id][$j]);
								$lrcode = empty($_POST['rate_code'.$load_id][$j]) ? '' :
									$_POST['rate_code'.$load_id][$j];
								$lrname = empty($_POST['rate_name'.$load_id][$j]) ? '' :
									$_POST['rate_name'.$load_id][$j];
								$lrbonus = empty($_POST['rate_bonus'.$load_id][$j]) ? 0 :
									intval($_POST['rate_bonus'.$load_id][$j]);

								//! SCR# 180 - store link back to card advance code
								$card_advance = empty($_POST['card_advance'.$load_id][$j]) ?
									'NULL' : intval($_POST['card_advance'.$load_id][$j]);
									
								//! Insert rates into load_pay_rate
								// 3/8 - Duncan, removed unnecessary quotes around numbers.
								$pay_obj->add_row("LOAD_ID, TITLE, QUANTITY, FREE_HOURS,
									RATE, PAY, DRIVER_ID, TRIP_ID,
									LOAD_RATE_CODE, LOAD_RATE_NAME, LOAD_RATE_BONUS,
									CARD_ADVANCE_CODE",
									"$load_id, '$title', $quantity, $free_hours,
									$rate, $pay, $id, $trip,
									'$lrcode', '$lrname', $lrbonus,
									$card_advance");
							}
						}
					}
					##----------- ADD MANUAL RATES---------##
					if(isset($_POST['load_manual_codes'.$load_id])) {
						$man_len=count($_POST['load_manual_codes'.$load_id]);
						if($man_len>0) {
							for($k=0;$k<$man_len;$k++) {
								$load_manual_codes_rate=$_POST['load_manual_codes'.$load_id][$k];
								$manual_code=$_POST['manual_code'.$load_id][$k];
								$manual_desc=$_POST['manual_desc'.$load_id][$k];
								
								// 3/8 - Duncan, removed unnecessary quotes around numbers.
								$man_obj->add_row("LOAD_ID, MANUAL_CODE, MANUAL_DESC,
									MANUAL_RATE, DRIVER_ID, TRIP_ID",
								"$load_id, '$manual_code', '$manual_desc',
								$load_manual_codes_rate, $id, $trip"); //XXX
							}
						}
					}
				
					##---------- ADD RANGE RATES -----------##
					//print_r($_POST['range_code'.$load_id]);
					if(isset($_POST['range_code'.$load_id])) {
						$ran_len=count($_POST['range_code'.$load_id]);
						if($ran_len>0) {
							for($x=0;$x<$ran_len;$x++) {
								$range_code=$_POST['range_code'.$load_id][$x];
								$range_from=$_POST['range_from'.$load_id][$x];
								$range_to=$_POST['range_to'.$load_id][$x];
								$range_rate=$_POST['range_rate'.$load_id][$x];
								$range_miles=$_POST['range_miles'.$load_id][$x];
								$range_name=$_POST['range_name'.$load_id][$x];
							  
								// 3/8 - Duncan, removed unnecessary quotes around numbers.
								$range_obj->add_row("LOAD_ID, RANGE_CODE, RANGE_NAME,
									RANGE_FROM, RANGE_TO, RANGE_RATE,
									TOTAL_RATE, DRIVER_ID, TRIP_ID",
									"$load_id, '$range_code', '$range_name',
									$range_from, $range_to, $range_rate,
									$range_miles, $id, $trip");
							   
							}
					    }
					}
			   
					##--------- ADD REMAINING INFO IN DRIVER PAY TABLE------##
					$weekend_from=$start;
					$weekend_to=$end;
					$trip_pay=floatval($total_trip_amount); //$_POST['final_trip_pay'];
					$final_bonus=floatval($bonus_amount); //$_POST['final_bonus'];
					$final_handling=floatval($handling_pay); //$_POST['final_handling'];
					$final_settlement=floatval($total_settelment); //$_POST['final_settlement'];
					
					// 3/8 - Duncan, removed unnecessary quotes around numbers.
					$driver->add_row("LOAD_ID, DRIVER_ID, WEEKEND_FROM,
						WEEKEND_TO, TRIP_PAY, BONUS,
						HANDLING, GROSS_EARNING, ADDED_ON,
						TRIP_ID, UNIQUE_ID",
						"$load_id, $id,'".date('Y-m-d', strtotime($start))."',
						'".date('Y-m-d', strtotime($end))."', $trip_pay, $final_bonus,
						$final_handling, $final_settlement, '".date('Y-m-d H:i:s')."',
						$trip, $max_unique_id");
					
					$i++;
				}
			} // foreach
		
			// 3/8 - Duncan, removed unnecessary quotes around numbers.
			$newID=$driver->database->get_one_row("
				SELECT DRIVER_PAY_ID FROM ".DRIVER_PAY_MASTER."
				WHERE DRIVER_ID=$id
				AND WEEKEND_FROM='".date('Y-m-d', strtotime($start))."'
				AND WEEKEND_TO='".date('Y-m-d', strtotime($end))."'");
				
			if( $sts_debug )
				die;
			if(is_array($newID) && $newID['DRIVER_PAY_ID']!="") {
				echo '<script>window.location.href="exp_updatedriverpay.php?payid='.$newID['DRIVER_PAY_ID'].'#end"</script>';
			} else {
				echo '<script>window.location.href="exp_billhistory.php?CODE='.$id.'"</script>';
			}
		}
	}
	
	//! --------------------- GET ALL THE DATA ---------------------
	#driver info array
	// 3/8 - Duncan, removed unnecessary quotes around numbers.
	$res['driver_info'] = $sql_obj->database->get_multiple_rows(
		"SELECT ".DRIVER_TABLE.".* FROM ".DRIVER_TABLE."
		WHERE ".DRIVER_TABLE.".DRIVER_CODE=".$id);

	$contract_name="";
	if(isset($res['driver_info'][0]['PROFILE_ID']) && $res['driver_info'][0]['PROFILE_ID']!=0) {
		$contract_name_arr=$pro_obj->database->get_one_row("SELECT  PROFILE_NAME FROM ".PROFILE_MASTER." WHERE ".PROFILE_MASTER.".CONTRACT_ID=".$res['driver_info'][0]['PROFILE_ID']." ");
		$contract_name=$contract_name_arr['PROFILE_NAME'];
	}
	$res['contract_name']=$contract_name;

	if(isset($_POST['btn_search_load'])) {
		if(isset($_POST['btn_search_load']) ) {
			$from_date=$_POST['ACTUAL_DEPART_FROM'];
			$to_date=$_POST['ACTUAL_DEPART_TO'];
		}
		$where_str='';
		if($from_date!="" && $to_date!="") {
			$where_str="AND ".STOP_TABLE.".STOP_TYPE='drop' AND (ACTUAL_DEPART BETWEEN '".date('Y-m-d H:i:s',strtotime($from_date))."' AND '".date('Y-m-d H:i:s',strtotime($to_date))."')";
		} else if($from_date!="") {
			$where_str="AND ".STOP_TABLE.".STOP_TYPE='drop' AND (ACTUAL_DEPART >='".date('Y-m-d H:i:s',strtotime($from_date))."')";
		} else if($to_date!="") {
			$where_str="AND ".STOP_TABLE.".STOP_TYPE='drop' AND (ACTUAL_DEPART <='".date('Y-m-d H:i:s',strtotime($to_date))."')";
		}
		// 3/8 - Duncan, removed unnecessary quotes around numbers.
		$res['driver_assign_loads']	=$sql_obj->database->get_multiple_rows("
			SELECT ".LOAD_TABLE.".*, 
			COALESCE( (SELECT GROUP_CONCAT( DISTINCT SHIPMENT_CODE
			ORDER BY SHIPMENT_CODE ASC SEPARATOR ', ')
		    FROM EXP_SHIPMENT WHERE EXP_SHIPMENT.LOAD_CODE = EXP_LOAD.LOAD_CODE),
            (SELECT GROUP_CONCAT( DISTINCT SHIPMENT
			ORDER BY SHIPMENT ASC SEPARATOR ', ')
		    FROM EXP_STOP WHERE EXP_STOP.LOAD_CODE = EXP_LOAD.LOAD_CODE)) AS SHIPMENTS,
    
			LOAD_OFFICE_NUM(".LOAD_TABLE.".LOAD_CODE) AS OFFICE_NUM,
			".TRACTOR_TABLE.". UNIT_NUMBER AS TRACTOR_UNIT_NUMBER,
			".TRAILER_TABLE.".UNIT_NUMBER AS TRAILER_UNIT_NUMBER,
			".SHIPMENT_TABLE.".CONS_NAME,
			(SELECT  SUM(PALLETS) AS TOT_PALETS FROM ".SHIPMENT_TABLE."
				WHERE ".SHIPMENT_TABLE.".LOAD_CODE = ".LOAD_TABLE.".LOAD_CODE ) AS TOT_PALETS
			FROM ".LOAD_TABLE."
			LEFT JOIN ".TRACTOR_TABLE."
			ON ".LOAD_TABLE.".TRACTOR=".TRACTOR_TABLE.".TRACTOR_CODE
			LEFT JOIN ".TRAILER_TABLE."
			ON ".LOAD_TABLE.".TRAILER= ".TRAILER_TABLE.".TRAILER_CODE
			LEFT JOIN ".SHIPMENT_TABLE."
			ON ".SHIPMENT_TABLE.".LOAD_CODE = ".LOAD_TABLE.".LOAD_CODE
			JOIN ".STOP_TABLE."
			ON ".STOP_TABLE.".LOAD_CODE=".LOAD_TABLE.".LOAD_CODE
			WHERE ( ".LOAD_TABLE.".DRIVER=".$id." OR ".LOAD_TABLE.".DRIVER2=".$id." )
			AND NOT EXISTS (SELECT DRIVER_PAY_ID FROM EXP_DRIVER_PAY_MASTER
			WHERE  DRIVER_ID=".$id." AND LOAD_ID =".LOAD_TABLE.".LOAD_CODE)
			AND  ".LOAD_TABLE.".CURRENT_STATUS IN ( 19, 68, 33, 34 )
			".$where_str."
			AND COMPLETED_DATE > CURDATE() - INTERVAL 6 MONTH
			AND ".LOAD_TABLE.".LOAD_CODE NOT IN (SELECT LOAD_ID FROM EXP_DRIVER_PAY_MASTER
				WHERE DRIVER_ID=".$id." AND LOAD_ID > 0 AND FINALIZE_STATUS='finalized')
			GROUP BY ".LOAD_TABLE.".LOAD_CODE");
	
	} else {
		// 3/8 - Duncan, removed unnecessary quotes around numbers.
		$res['driver_assign_loads']	=$sql_obj->database->get_multiple_rows("
			SELECT ".LOAD_TABLE.".*, 
			COALESCE( (SELECT GROUP_CONCAT( DISTINCT SHIPMENT_CODE
			ORDER BY SHIPMENT_CODE ASC SEPARATOR ', ')
		    FROM EXP_SHIPMENT WHERE EXP_SHIPMENT.LOAD_CODE = EXP_LOAD.LOAD_CODE),
            (SELECT GROUP_CONCAT( DISTINCT SHIPMENT
			ORDER BY SHIPMENT ASC SEPARATOR ', ')
		    FROM EXP_STOP WHERE EXP_STOP.LOAD_CODE = EXP_LOAD.LOAD_CODE
		    AND EXP_STOP.IM_STOP_TYPE != 'reposition')) AS SHIPMENTS,

			LOAD_OFFICE_NUM(".LOAD_TABLE.".LOAD_CODE) AS OFFICE_NUM,
			".TRACTOR_TABLE.". UNIT_NUMBER AS TRACTOR_UNIT_NUMBER,
			".TRAILER_TABLE.".UNIT_NUMBER AS TRAILER_UNIT_NUMBER,
			".SHIPMENT_TABLE.".CONS_NAME,
			(SELECT  SUM(PALLETS) AS TOT_PALETS FROM ".SHIPMENT_TABLE."
				WHERE ".SHIPMENT_TABLE.".LOAD_CODE = ".LOAD_TABLE.".LOAD_CODE) AS TOT_PALETS
			FROM ".LOAD_TABLE."
			LEFT JOIN ".TRACTOR_TABLE."
			ON  ".LOAD_TABLE.".TRACTOR=".TRACTOR_TABLE.".TRACTOR_CODE
			LEFT JOIN ".TRAILER_TABLE."
			ON  ".LOAD_TABLE.".TRAILER= ".TRAILER_TABLE.".TRAILER_CODE
			LEFT JOIN ".SHIPMENT_TABLE."
			ON ".SHIPMENT_TABLE.".LOAD_CODE = ".LOAD_TABLE.".LOAD_CODE
			WHERE ( ".LOAD_TABLE.".DRIVER=".$id." OR ".LOAD_TABLE.".DRIVER2=".$id." )
			AND NOT EXISTS (SELECT DRIVER_PAY_ID FROM EXP_DRIVER_PAY_MASTER
			WHERE  DRIVER_ID=".$id." AND LOAD_ID =".LOAD_TABLE.".LOAD_CODE)
			AND  ".LOAD_TABLE.".CURRENT_STATUS IN ( 19, 68, 33, 34 )
			AND COMPLETED_DATE > CURDATE() - INTERVAL 6 MONTH
			AND ".LOAD_TABLE.".LOAD_CODE NOT IN (SELECT LOAD_ID FROM EXP_DRIVER_PAY_MASTER
				WHERE DRIVER_ID=".$id." AND LOAD_ID > 0 AND FINALIZE_STATUS='finalized')
			GROUP BY ".LOAD_TABLE.".LOAD_CODE");
	}
	//echo '<pre/>';print_r($res['driver_assign_loads']	);
	$driver_assign_load_with_stop=$final=array();
	if( is_array($res['driver_assign_loads']) && count($res['driver_assign_loads'])>0) {
		$i=0;
		foreach($res['driver_assign_loads'] as $newload) {
			
		$pickup_location=$sql_obj->database->get_multiple_rows(
			"SELECT SHIPMENT_CODE, STOP_CODE, STOP_TYPE, SEQUENCE_NO, 
                (CASE
                    WHEN STOP_TYPE = 'pick' THEN SHIPPER_ADDR1
                    WHEN STOP_TYPE = 'drop' THEN CONS_ADDR1
                    ELSE STOP_ADDR1 END ) AS ADDR1,
                (CASE
                    WHEN STOP_TYPE = 'pick' THEN SHIPPER_ADDR2
                    WHEN STOP_TYPE = 'drop' THEN CONS_ADDR2
                    ELSE STOP_ADDR2 END ) AS ADDR2,
                (CASE
                    WHEN STOP_TYPE = 'pick' THEN SHIPPER_CITY
                    WHEN STOP_TYPE = 'drop' THEN CONS_CITY
                    ELSE STOP_CITY END ) AS CITY,
                (CASE
                    WHEN STOP_TYPE = 'pick' THEN SHIPPER_STATE
                    WHEN STOP_TYPE = 'drop' THEN CONS_STATE
                    ELSE STOP_STATE END ) AS STATE,
                (CASE
                    WHEN STOP_TYPE = 'pick' THEN SHIPPER_ZIP
                    WHEN STOP_TYPE = 'drop' THEN CONS_ZIP
                    ELSE STOP_ZIP END ) AS ZIP,
				(CASE
                    WHEN STOP_TYPE = 'pick' THEN SHIPPER_NAME
                    WHEN STOP_TYPE = 'drop' THEN CONS_NAME
                    ELSE STOP_NAME END ) AS NAME,
                ACTUAL_ARRIVE, ACTUAL_DEPART,
                (CASE
                    WHEN STOP_TYPE = 'pick' THEN SHIPPER_CLIENT_CODE
                    ELSE NULL END ) AS SHIPPER_CLIENT_CODE,
                (CASE
                    WHEN STOP_TYPE = 'drop' THEN CONS_CLIENT_CODE
                    ELSE NULL END ) AS CONS_CLIENT_CODE
                
                FROM EXP_STOP
                left JOIN EXP_SHIPMENT
                ON EXP_SHIPMENT.SHIPMENT_CODE = EXP_STOP.SHIPMENT
                
                WHERE EXP_STOP.LOAD_CODE = ".$newload['LOAD_CODE']."
                AND SEQUENCE_NO <= (SELECT 
                (CASE WHEN CURRENT_STOP = (SELECT COUNT(*) AS NUM_STOPS
                                FROM EXP_STOP
                                WHERE EXP_LOAD.LOAD_CODE = EXP_STOP.LOAD_CODE) THEN CURRENT_STOP
                ELSE CURRENT_STOP - 1 END) AS CURRENT_STOP
                FROM EXP_LOAD 
                WHERE EXP_LOAD.LOAD_CODE = EXP_STOP.LOAD_CODE)
                order by SEQUENCE_NO ASC");

		$driver_assign_load_with_stop[$i]=$newload;  //append array for assigned load

		//! SCR# 835 - Get Client Rates for Load
		$client_rates = $sql_obj->database->get_multiple_rows("
			SELECT R.RATE_CODE, SUM(R.RATES) AS RATES
			FROM EXP_CLIENT_BILLING_RATES R, EXP_CLIENT_BILLING B, EXP_SHIPMENT S
			WHERE R.BILLING_ID = B.CLIENT_BILLING_ID
			AND B.SHIPMENT_ID = S.SHIPMENT_CODE
			AND S.LOAD_CODE = ".$newload['LOAD_CODE']."
			GROUP BY S.LOAD_CODE, R.RATE_CODE");
			
		//! SCR# 835 - Get FSC for Load
		$fsc = $sql_obj->database->get_multiple_rows("
			SELECT SUM(B.FUEL_COST) AS FUEL_COST
			FROM EXP_CLIENT_BILLING B, EXP_SHIPMENT S
			WHERE B.SHIPMENT_ID = S.SHIPMENT_CODE
			AND S.LOAD_CODE = ".$newload['LOAD_CODE']);
		$fuel_cost = 0;
		if( is_array($fsc) && count($fsc) > 0 && isset($fsc[0]['FUEL_COST']) )
			$fuel_cost = $fsc[0]['FUEL_COST'];
		
		$driver_assign_load_with_stop[$i]['CLIENT_RATES']=$client_rates;
		$driver_assign_load_with_stop[$i]['FUEL_COST']=$fuel_cost;

		//! SCR# 457 - Driver pay - default distance for load based on shipment client billing
		if( $sts_dist_cbill ) {
			$check_dist = $sql_obj->database->get_one_row(
				"SELECT C.MILLEAGE AS BILLING_DISTANCE
				FROM EXP_LOAD L, EXP_SHIPMENT S, EXP_CLIENT_BILLING C
				WHERE C.SHIPMENT_ID = S.SHIPMENT_CODE
				AND L.LOAD_CODE = S.LOAD_CODE
				AND (SELECT COUNT(*) FROM EXP_SHIPMENT
					WHERE EXP_SHIPMENT.LOAD_CODE = L.LOAD_CODE) = 1
				AND L.TOTAL_DISTANCE != C.MILLEAGE
				AND L.LOAD_CODE = ".$newload['LOAD_CODE'] );
			
			// If we get a value back, overwrite this into ACTUAL_DISTANCE 
			if( is_array($check_dist) && isset($check_dist["BILLING_DISTANCE"]) &&
				$check_dist["BILLING_DISTANCE"] > 0 ) {
				$driver_assign_load_with_stop[$i]['ACTUAL_DISTANCE'] = $check_dist["BILLING_DISTANCE"];
			}
		}

		$driver_assign_load_with_stop[$i]['STOP_DETAIL']=array();
		foreach($pickup_location as $k=>$v) {
			$driver_assign_load_with_stop[$i]['STOP_DETAIL'][$k]=$v; // append array of stop detail of each load.
			//fetch holiday of each date
			$driver_assign_load_with_stop[$i]['STOP_DETAIL'][$k]['HOLIDAY']=$driver_assign_load_with_stop[$i]['STOP_DETAIL'][$k]['ARRIVAL_HOLIDAY']='';
			if($v['ACTUAL_DEPART']!="") {
				$holiday=$hol_obj->database->get_one_row("
					SELECT HOLIDAY_NAME FROM ".HOLIDAY_TABLE."
					WHERE HOLIDAY_DATE='".date('Y-m-d',strtotime($v['ACTUAL_DEPART']))."'
					AND PAID = 1");
				if( isset($holiday) && is_array($holiday) && count($holiday)>0 && isset($holiday['HOLIDAY_NAME'])) {
					$driver_assign_load_with_stop[$i]['STOP_DETAIL'][$k]['HOLIDAY']=$holiday['HOLIDAY_NAME'];
				}
			}
			if($v['ACTUAL_ARRIVE']!="") {
				$arrival_holiday=$hol_obj->database->get_one_row("
					SELECT HOLIDAY_NAME FROM ".HOLIDAY_TABLE."
					WHERE HOLIDAY_DATE='".date('Y-m-d',strtotime($v['ACTUAL_ARRIVE']))."'
					AND PAID = 1");
				
				if( isset($arrival_holiday) && is_array($arrival_holiday) && count($arrival_holiday)>0 && isset($arrival_holiday['HOLIDAY_NAME'])) {
					$driver_assign_load_with_stop[$i]['STOP_DETAIL'][$k]['ARRIVAL_HOLIDAY']=$arrival_holiday['HOLIDAY_NAME'];
				}
			}
		}
	
	
		##----------fetch handling pallets from client billing screen------##
		$handling_pallets=$ship_obj->database->get_one_row("
			SELECT HAND_PALLET, DETENTION_HOUR, UNLOADED_DETENTION_HOUR,
			CLIENT_BILLING_ID
			FROM ".CLIENT_BILL." JOIN ".SHIPMENT_TABLE."
			ON ".SHIPMENT_TABLE.".SHIPMENT_CODE=".CLIENT_BILL.".SHIPMENT_ID
			WHERE EXP_SHIPMENT.LOAD_CODE=".$newload['LOAD_CODE']);
		
		if((is_array($handling_pallets) && count($handling_pallets)>0) && $handling_pallets['HAND_PALLET']!="") {
			$driver_assign_load_with_stop[$i]['HAND_PALLET']=$handling_pallets['HAND_PALLET'];
			$driver_assign_load_with_stop[$i]['DETENTION_HOUR']=$handling_pallets['DETENTION_HOUR'];
			$driver_assign_load_with_stop[$i]['UNLOADED_DETENTION_HOUR'] =
				$handling_pallets['UNLOADED_DETENTION_HOUR'];
		} else {
			$driver_assign_load_with_stop[$i]['HAND_PALLET']=0;
			$driver_assign_load_with_stop[$i]['DETENTION_HOUR']=0;
			$driver_assign_load_with_stop[$i]['UNLOADED_DETENTION_HOUR']=0;
		}
		//! Duncan - calculate line haul
		//! SCR# 236 all except miles and FSC
		$linehaul = $ship_obj->database->get_one_row(
			"SELECT SUM(B.FREIGHT_CHARGES + B.STOP_OFF + B.DETENTION_RATE + B.UNLOADED_DETENTION_RATE) AS LINE_HAUL,
			SUM(B.PALLETS_RATE + B.HAND_CHARGES + B.EXTRA_CHARGES +
				B.FREIGHT_CHARGES + B.DETENTION_RATE + B.UNLOADED_DETENTION_RATE +
				B.STOP_OFF + B.WEEKEND + B.HAND_PALLET +
				B.ADJUSTMENT_CHARGE - B.DISCOUNT + B.SELECTION_FEE ) AS NOT_MILES_FSC,
			SUM(B.FUEL_COST) FUEL_COST,
			SUM(B.MILLEAGE) MILLEAGE
			FROM EXP_SHIPMENT S
			LEFT JOIN EXP_CLIENT_BILLING B
			ON S.SHIPMENT_CODE = B.SHIPMENT_ID
			WHERE LOAD_CODE = ".$newload['LOAD_CODE']."
			AND LOAD_CODE > 0" );
		
		$driver_assign_load_with_stop[$i]["LINE_HAUL"] = isset($linehaul["LINE_HAUL"]) ? $linehaul["LINE_HAUL"] : 0;
		$driver_assign_load_with_stop[$i]["NOT_MILES_FSC"] = isset($linehaul["NOT_MILES_FSC"]) ? $linehaul["NOT_MILES_FSC"] : 0;
		$driver_assign_load_with_stop[$i]["MILLEAGE"] = isset($linehaul["MILLEAGE"]) ? $linehaul["MILLEAGE"] : 0;
		$driver_assign_load_with_stop[$i]["FUEL_COST"] = isset($linehaul["FUEL_COST"]) ? $linehaul["FUEL_COST"] : 0;

		//! Duncan - SCR# 237 - check if extra stops conditions exist
		$extra_stop = $ship_obj->database->get_one_row("
			SELECT L.LOAD_CODE
			FROM EXP_LOAD L
			WHERE L.LOAD_CODE = ".$newload['LOAD_CODE']."
			AND (SELECT COUNT(T.SHIPMENT_CODE) FROM EXP_SHIPMENT T
			WHERE T.LOAD_CODE = L.LOAD_CODE) = 1
			AND (SELECT COUNT(T.STOP_CODE) FROM EXP_STOP T
			WHERE T.LOAD_CODE = L.LOAD_CODE) > 2
			AND (SELECT C.CLIENT_EXTRA_STOPS
            FROM EXP_CLIENT C, EXP_SHIPMENT S
			WHERE C.CLIENT_CODE = S.BILLTO_CLIENT_CODE
            AND S.LOAD_CODE = L.LOAD_CODE)");
            
        $driver_assign_load_with_stop[$i]["EXTRA_STOPS"] = is_array($extra_stop) &&
        	isset($extra_stop['LOAD_CODE']) &&
        	$extra_stop['LOAD_CODE'] == $newload['LOAD_CODE'];
	
		$i++;
	}
}
$res['driver_assign_load_with_stop']=$driver_assign_load_with_stop;
//echo '<pre/>';print_r($driver_assign_load_with_stop);
##------- code for pickup time------------##
if(is_array($res['driver_assign_loads']) && count($res['driver_assign_loads'])>0) {
	//echo $res['driver_assign_loads'][0]['LOAD_CODE'];
	/*for($i=0;$i<count($res['driver_assign_loads']);$i++)
	{
	   $shipment_details=$stop_obj->database->get_multiple_rows("SELECT * FROM ".STOP_TABLE.",".SHIPMENT_TABLE." WHERE  ".SHIPMENT_TABLE.".LOAD_CODE=".STOP_TABLE.".LOAD_CODE AND ".STOP_TABLE.".LOAD_CODE=".$res['driver_assign_loads'][$i]['LOAD_CODE']);
	 
	}*/
}


//! get driver rates for calculations
// 3/8 - Duncan, removed unnecessary quotes around numbers.
$res['driver_rates']		=	$sql_obj->database->get_multiple_rows("
	SELECT ".DRIVER_RATES.".*, ".CATEGORY_TABLE.".CATEGORY_NAME,
	".DRIVER_ASSIGN_RATES.".*,
	(select RATE_CODE from ".CLIENT_CAT_RATE."
		where CLIENT_RATE_ID = ".DRIVER_RATES.".CLIENT_RATE) AS CLIENT_RATE_NAME
	FROM ".DRIVER_RATES.", ".CATEGORY_TABLE.", ".DRIVER_ASSIGN_RATES."
	WHERE ".DRIVER_RATES.".RATE_ID=".DRIVER_ASSIGN_RATES.".RATE_ID
	AND ".DRIVER_RATES.".CATEGORY=".CATEGORY_TABLE.".CATEGORY_CODE
	AND ".DRIVER_ASSIGN_RATES.".DRIVER_ID=".$id."
	AND ".DRIVER_RATES.".ISDELETED = 0");
	
//! RATES - FREE_HOURS
//	echo "<pre>";
//	var_dump($res['driver_rates']);
//	echo "</pre>";

#get driver manual rates
// 3/8 - Duncan, removed unnecessary quotes around numbers.
$res['manual_rates'] = $sql_obj->database->get_multiple_rows("
	SELECT *
	FROM ".MANUAL_RATES."
	WHERE DRIVER_ID=".$id);

// 3/8 - Duncan, removed unnecessary quotes around numbers.
$res['range_rates'] = $sql_obj->database->get_multiple_rows("
	SELECT *
	FROM ".RANGE_RATES."
	WHERE DRIVER_ID=".$id);
//print_r($res['range_rates']	);

$previous_load=$driver->database->get_multiple_rows("
	SELECT LOAD_ID
	FROM ".DRIVER_PAY_MASTER."
	WHERE DRIVER_ID=".$id."
	AND LOAD_ID != 0");

//echo '<pre/>';print_r($previous_load);.
$arr=array();
//! SCR# 898 - check it's an array before counting...
if( is_array($previous_load) && count($previous_load)>0) {
	foreach($previous_load as $k=>$v) {
		$arr[$k]=$v['LOAD_ID'];
	}
}
//echo '<pre/>';print_r($arr);
$res['previous_load']=$arr;
$tripID=0;

$max_trip_id=$driver->database->get_one_row("
	SELECT COALESCE(MAX(TRIP_ID), 0) AS MAX_TRIP
	FROM ".DRIVER_PAY_MASTER);
	
if(isset($max_trip_id['MAX_TRIP'])) {
	$tripID=$max_trip_id['MAX_TRIP']+1;
}
$res['tripID']=$tripID;

if( $sts_debug ) {
	echo "<pre>tripID\n";
	var_dump($res['tripID']);
	echo "</pre>";
}


//! Check for existing load 0 for this period
// 3/8 - Duncan, removed unnecessary quotes around numbers.
$res['LoadZero']=$driver->database->get_multiple_rows("
	SELECT * FROM ".DRIVER_PAY_MASTER."
	WHERE WEEKEND_FROM='".$start."'
	AND WEEKEND_TO='".$end."'
	AND DRIVER_ID=$id
	AND LOAD_ID=0");

}

//! Duncan - check if there are any driver rates set up.
$dar=$driver->database->get_one_row(
	"SELECT COUNT(*) AS NUM
	FROM EXP_DRIVER_ASSIGN_RATES
	WHERE DRIVER_ID = ".$id);
$drr=$driver->database->get_one_row(
	"SELECT COUNT(*) AS NUM
	FROM EXP_DRIVER_RANGE_RATES
	WHERE DRIVER_ID = ".$id);
$dmr=$driver->database->get_one_row(
	"SELECT COUNT(*) AS NUM
	FROM EXP_DRIVER_MANUAL_RATES
	WHERE DRIVER_ID = ".$id);
if( is_array($dar) && is_array($drr) && is_array($dmr) &&
	isset($dar["NUM"]) && isset($drr["NUM"]) && isset($dmr["NUM"]) &&
	$dar["NUM"] + $drr["NUM"] + $dmr["NUM"] == 0 ) {
	$res['norates']=true;	// Tells us no rates set
}

//! Duncan - SCR# 70 - Add Fuel Card Advances
//! SCR# 180 - include more detail
$check = $driver->database->get_multiple_rows("
	SELECT ADVANCE_CODE, CARD_SOURCE, TRANS_DATE, TRANS_NUM, CARD_STOP, CITY, STATE, CASH_ADV 
	FROM EXP_CARD_ADVANCE
	WHERE DRIVER_CODE = $id
	AND NOT EXISTS (SELECT LOAD_PAY_RATE_ID
	FROM EXP_LOAD_PAY_RATE
	WHERE CARD_ADVANCE_CODE = ADVANCE_CODE)
	ORDER BY TRANS_DATE ASC");

if( is_array($check) && count($check) > 0 ) {
	$res['fuel_card_advance'] = $check;
}

//! Duncan - SCR# 132 - bonusable manual rates
$res["MANUAL_BONUSABLE"] = $sts_driver_manual_bonusable;
$res['multi_company'] = $sts_multi_company;

//! Duncan - SCR# 237 - pass extra_stops
$res['extra_stops'] = $sts_extra_stops;

//	echo "<br><br><br><br><br><pre>";
//	var_dump($res);
//	echo "</pre><br><br><br>";

$res_obj->render_driver_pay_screen($res);
?>
</div>

<?php
require_once( "include/footer_inc.php" );
?>
<script type="text/javascript" language="javascript">
	
function add_bonus(load_id)
{
	var bonus_allow					=	document.getElementById('bonus_allow'+load_id);
	var  assign_tot						=	0.00;
	var total_trip_amount			=	document.getElementById('total_trip_amount'+load_id);
	var total_trip_amount_div	=	document.getElementById('total_trip_amount_div'+load_id);
	var total_bonusable_amount			=	document.getElementById('total_bonusable_amount'+load_id);
	var total_bonusable_amount_div	=	document.getElementById('total_bonusable_amount_div'+load_id);
	var totla_settelment				=	document.getElementById('totla_settelment'+load_id);
	var apply_bonus					=	document.getElementById('apply_bonus'+load_id);
	var text_total							=	document.getElementsByName('text_total'+load_id+'[]');
	var rate_bonus							=	document.getElementsByName('rate_bonus'+load_id+'[]');
	var handling_pay					=	document.getElementById('handling_pay'+load_id);
	var bonus_amount				=	document.getElementById('bonus_amount'+load_id);
	var load_manual_codes		=	document.getElementsByName('load_manual_codes'+load_id+'[]');
	var range_miles						=	document.getElementsByName('range_miles'+load_id+'[]');
		
		var total_trip_pay =tot_bonus_amount = total_handling =total_manual_codes=total_range=total_bonus_cost= final_bonus_cost = final_trip_amt = final_handling_amt=final_sett_amt=0;
		var total_bonusable_trip_pay = 0;
		var tot_len	=	text_total.length;
		for(var i=0 ; i < tot_len ; i++) {
			if(text_total[i].value!="") {
				total_trip_pay = parseFloat(total_trip_pay) + parseFloat(text_total[i].value);
				if(rate_bonus[i].value=="1") {
					total_bonusable_trip_pay = parseFloat(total_bonusable_trip_pay) + parseFloat(text_total[i].value);
				}
			}
		}
			
		//handling pay
		if(handling_pay.value!='')
		{
				total_handling =  parseFloat(handling_pay.value);
		}
		//bonus pay
		if(bonus_amount.value!='')
		{
			tot_bonus_amount =  parseFloat(bonus_amount.value);
		}
		//manual rates
		var man_len	=	load_manual_codes.length;
		for(var j=0 ; j < man_len ; j++)
		{
			if(load_manual_codes[j].value!="")
			{total_manual_codes = parseFloat(total_manual_codes) + parseFloat(load_manual_codes[j].value);}
		}
		//range of rates
		var rang_len	=	range_miles.length;
		for(var k=0 ; k < rang_len ; k++)
		{
			if(range_miles[k].value!="")
			{total_range = parseFloat(total_range) + parseFloat(range_miles[k].value);}
		}
		total_trip_pay = parseFloat(total_trip_pay) + parseFloat(total_range) + parseFloat(total_manual_codes);
		total_bonusable_trip_pay = parseFloat(total_bonusable_trip_pay) + parseFloat(total_range);
		
		//! Duncan - SCR# 132 - bonusable manual rates
		var manual_bonusable = <?php echo $sts_driver_manual_bonusable ? 'true' : 'false' ?>;
		if( manual_bonusable )
			total_bonusable_trip_pay += parseFloat(total_manual_codes);
		
		var subtotal = parseFloat(total_trip_pay) + parseFloat(total_handling);
		// if include handling, add 

		var bonusable_trip_pay = parseFloat(total_bonusable_trip_pay);

		if(bonus_allow.value=='Yes' && apply_bonus.value!='' )
		{
			 assign_tot	=	(parseFloat(bonusable_trip_pay) * parseFloat(apply_bonus.value)) / parseFloat(100);
				 
			 if(isNaN(assign_tot)) {
			 	assign_tot	=	0.00;
			 }

			if(assign_tot!="0.00" && !isNaN(assign_tot))
			{
	  			assign_tot=Math.round(assign_tot*100)/100;
			}
		}
		else if(bonus_allow.value=='Yes' && apply_bonus.value=='' && bonus_amount.value!='' ) {
			assign_tot = parseFloat(bonus_amount.value);
		} else {
			assign_tot = 0;
		}
			
		totla_settelment.value=(Math.round((subtotal + assign_tot)*100)/100).toFixed(2);
		bonus_amount.value = assign_tot.toFixed(2);
		//console.log(load_id, "'"+bonus_allow.value+"'", subtotal, '+', assign_tot, '=', totla_settelment.value);
		
			
		if(total_trip_pay!="" && !isNaN(total_trip_pay))
		{
			total_trip_pay=Math.round(total_trip_pay *100)/100;
		}
		total_trip_amount.value=total_trip_pay;
		total_trip_amount_div.innerHTML	=	total_trip_pay.toFixed(2);
		
		if(total_bonusable_trip_pay!="" && !isNaN(total_bonusable_trip_pay))
		{
			total_bonusable_trip_pay=Math.round(total_bonusable_trip_pay *100)/100;
		}
		total_bonusable_amount.value=total_bonusable_trip_pay;
		total_bonusable_amount_div.innerHTML	=	total_bonusable_trip_pay.toFixed(2);

}

function calculate_total(id,load_id)
{
		console.log('calculate_total: id = ', id, ' load_id = ', load_id);
		var text_total							=	document.getElementsByName('text_total'+load_id+'[]'); 
		var total_trip_amount			=	document.getElementById('total_trip_amount'+load_id);
		var total_trip_amount_div	=	document.getElementById('total_trip_amount_div'+load_id);
		var handling_pay					=	document.getElementById('handling_pay'+load_id);
		var totla_settelment				=	document.getElementById('totla_settelment'+load_id);
		var bonus_amount				=	document.getElementById('bonus_amount'+load_id);
		var load_manual_codes		=	document.getElementsByName('load_manual_codes'+load_id+'[]');
		var range_miles						=	document.getElementsByName('range_miles'+load_id+'[]');
		var bonus_amount_name		=	document.getElementsByName('bonus_amount[]');
		var total_trip_amount			=	document.getElementsByName('total_trip_amount[]');
		var final_bonus						=	document.getElementById('final_bonus');
		var final_handling					=	document.getElementById('final_handling');
		var final_trip_pay					=	document.getElementById('final_trip_pay');
		var handling_pay_name		=	document.getElementsByName('handling_pay[]');
		var final_settlement				=	document.getElementById('final_settlement');
		var settle_name						=	document.getElementsByName('totla_settelment[]');
		
	//	console.log('free_hours', id, free_hours);
		
		var driver_id=document.getElementById('driver_id');
		//var odometer_from=document.getElementById('odometer_from'+load_id);
	    //var odometer_to=document.getElementById('odometer_to'+load_id);
		//calculate_total_miles(load_id,driver_id.value);
		
		if(id!='')
		{
			var qty_val	=	document.getElementById(load_id+id+'_qty').value;
			var free_hours = document.getElementById(load_id+id+'_free').value;
			var amt_val	=	document.getElementById(load_id+id+'_amt').value;
			var total	=	document.getElementById(load_id+id+'_total');
			
			if( qty_val == '' ) qty_val = 0;
			if( free_hours == '' ) free_hours = 0;
			if( amt_val == '' ) amt_val = 0;
			
			console.log(load_id, id, qty_val, free_hours, amt_val, total.value);
			var grand_total	= Math.max( 0, parseFloat(qty_val) - parseFloat(free_hours)) * parseFloat(amt_val);
			
			if(grand_total!="" && !isNaN(grand_total))
			{grand_total=Math.round(grand_total*100)/100;}
			total.value=(grand_total).toFixed(2);
			//console.log(load_id, id, 'grand_total', grand_total, total.value);
		}

		add_bonus(load_id);
			
		//final bonus
		var len_bonus_amount_name	=	bonus_amount_name.length;
		for(var a=0 ; a < len_bonus_amount_name ; a++)
		{
			if(bonus_amount_name[a].value!='') {
				final_bonus_cost = parseFloat(final_bonus_cost) +
					parseFloat(bonus_amount_name[a].value); }
		}
		final_bonus.value=final_bonus_cost;
		if(final_bonus_cost!="" && !isNaN(final_bonus_cost)) {
			final_bonus.value=(Math.round(final_bonus_cost*100)/100).toFixed(2);
		}
		
		//final trip cost
		var final_trip_amount_len	=	total_trip_amount.length;
		for(var b=0 ; b < final_trip_amount_len ; b++)
		{//console.log(total_trip_amount[b].value);
			if(total_trip_amount[b].value!=''){ final_trip_amt = parseFloat(final_trip_amt) + parseFloat(total_trip_amount[b].value); }
		}
		final_trip_pay.value=final_trip_amt;
		if(final_trip_amt!="" && !isNaN(final_trip_amt))
			{
			final_trip_pay.value=(Math.round(final_trip_amt*100)/100).toFixed(2);
			}
		
		//final handling cost
		var final_handling_len	=	handling_pay_name.length;
		for(var c=0 ; c < final_handling_len ; c++)
		{
			if(handling_pay_name[c].value!=''){final_handling_amt = parseFloat(final_handling_amt) + parseFloat(handling_pay_name[c].value);}
		}
		   final_handling.value=final_handling_amt;
		   if(final_handling_amt!="" && !isNaN(final_handling_amt))
			{
			final_handling.value=(Math.round(final_handling_amt*100)/100).toFixed(2);
			}
		
		var final_settlement_len	=	settle_name.length;
		
		
		for(var x=0 ; x < final_settlement_len ; x++)
		{
			//console.log(settle_name[x].value);
			if(settle_name[x].value!=''){final_sett_amt = parseFloat(final_sett_amt) + parseFloat(settle_name[x].value);}
		}
	
		final_settlement.value=final_sett_amt;
		if(final_sett_amt!="" && !isNaN(final_sett_amt))
		{
		final_settlement.value=(Math.round(final_sett_amt*100)/100).toFixed(2);
		}
	
}

function add_manual_fun(load_id,driver_id)
{
	
	var manual_rates	=	document.getElementById('manual_rates'+load_id);
	//alert(manual_rates);
	if(manual_rates.value=="")
	{
		alert("Please select manual rates first.");
		return false;
	}
	else
	{
		$('#reload_manual_rate_div'+load_id).show();
	$.ajax({
			url:'exp_save_rates.php?action=fetchonemanualrate&val='+manual_rates.value+'&load_id='+load_id+'&driver_id='+driver_id,
			type:'POST',
			dataType:'json',
			success:function(res){
				if(res.status=='exists')
				{
					alert('The manual rate you have selected is already assigned to this load. ');
					$('#reload_manual_rate_div'+load_id).hide(); 
					$('#manual_rates'+load_id).val("");
					return false;
				}
				else
				{
				var trcnt	=	$( "#empty_row"+load_id+" tr").length;
				$( "#empty_row"+load_id ).append('<tr style="cursor:pointer;"  id="'+res.data.MANUAL_RATE_CODE+load_id+'"><td style="width:5%;"><a class="btn btn-default btn-xs " onclick="javascript: return delete_manual_fun(\''+res.data.MANUAL_RATE_CODE+load_id+'\',\''+load_id+'\')" href="javascript:void(0);"><span class="glyphicon glyphicon-trash"></span></a></td><td>  Rates :</td><td style="width:25%;">'+res.data.MANUAL_RATE_CODE+'</td><td style="width:25%;">'+res.data.MANUAL_RATE_DESC+'</td><td style="width:25%;"><div class="input-group"><span class="input-group-addon">$</span><input class="form-control input-sm text-right" type="text"  onkeyup="javascript: calculate_total(\'\','+load_id+');"  name="load_manual_codes'+load_id+'[]" value="'+res.data.MANUAL_RATE_RATE+'"></div></td></tr><input type="hidden" name="manual_code'+load_id+'[]"  value="'+res.data.MANUAL_RATE_CODE+'"/></tr><input type="hidden" name="manual_desc'+load_id+'[]"  value="'+res.data.MANUAL_RATE_DESC+'"/> ');
					add_bonus(load_id);
					calculate_total('',load_id);
					$('#reload_manual_rate_div'+load_id).hide(); 
					$('#manual_rates'+load_id).val("");
				}
				}
		});
		 return false;
	}
}

function delete_manual_fun(obj,load_id)
{
	$('#'+obj).remove();
	add_bonus(load_id);
}

function chk_confirmation()
{
	if(confirm("Do you really want to store this settlement?"))
	{
		return true;
	}
	else
	{
		return false;
	}
}

// Once the total_miles has been updated, need to redo the ranges or the per mile
function update_miles_ranges(load_id,driver_id) {
	console.log('update_miles_ranges entry' );
	var total_miles			=	document.getElementById('total_miles'+load_id);
	var empty_miles			=	document.getElementById('empty_miles'+load_id);
	var range_name			=	document.getElementsByName('range_name'+load_id+'[]');
	var range_from			=	document.getElementsByName('range_from'+load_id+'[]');
	var range_to			=	document.getElementsByName('range_to'+load_id+'[]');
	var range_rate			=	document.getElementsByName('range_rate'+load_id+'[]');
	var range_miles			=	document.getElementsByName('range_miles'+load_id+'[]');
	var free_hours			=	document.getElementsByName('free_hours'+load_id+'[]');
	var rate_one			=	document.getElementsByName('rate_one'+load_id+'[]');
	var not_miles_fsc		=	document.getElementsByName('not_miles_fsc'+load_id+'[]');
	
	var category_name			=	document.getElementsByName('category_name'+load_id+'[]');
	var per_miles				=	document.getElementsByName(load_id+'_qty[]');
	var per_miles_amt			=	document.getElementsByName(load_id+'_amt[]');
	var per_miles_total			=	document.getElementsByName('text_total'+load_id+'[]');

	var range_len			=	range_from.length;	// Assume all the arrays are the same length.
	var miles				=	parseFloat(total_miles.value);
	var empty				=	parseFloat(empty_miles.value);
	var matched_rate		=	false;
	
	console.log('update_miles_ranges ', miles);
	console.log('update_miles_ranges: free_hours', load_id, free_hours);
		
	for(var i=0 ; i < range_len ; i++) {
		var from = parseFloat(range_from[i].value);
		var to = parseFloat(range_to[i].value);
		var rate = parseFloat(range_rate[i].value);
		console.log('range', i, from, to, rate);
		if( miles >= from && miles <= to ) {
			console.log('matched range',range_name[i].value, rate);
			matched_rate = true;
			range_miles[i].value = (rate).toFixed(2);
		} else {
			range_miles[i].value = (0).toFixed(2);
		}
	}
	
	if( ! matched_rate ) {
		var rate_len = category_name.length;
		if( rate_len > 0 ) {
			for(var i=0 ; i < rate_len ; i++) {
				if( category_name[i].value == 'Per Mile' ) {
					per_miles[i].value = miles;
					per_miles_total[i].value = (miles * parseFloat(per_miles_amt[i].value)).toFixed(2);
				}
				else if( category_name[i].value == 'Loaded Miles' ) {
					per_miles[i].value = miles - empty;
					per_miles_total[i].value = ((miles - empty) * parseFloat(per_miles_amt[i].value)).toFixed(2);
				}
				else if( category_name[i].value == 'Per Mile + Rate' ) {
					per_miles[i].value = (miles * parseFloat(rate_one[i].value)) + parseFloat(not_miles_fsc[i].value);
					per_miles_total[i].value = (parseFloat(per_miles[i].value) * parseFloat(per_miles_amt[i].value)).toFixed(2);
				}

			}
		}
	} else {
		var rate_len = category_name.length;
		if( rate_len > 0 ) {
			for(var i=0 ; i < rate_len ; i++) {
				if( category_name[i].value == 'Per Mile' ) {
					per_miles[i].value = 0;
					per_miles_total[i].value = (0).toFixed(2);
				}
			}
		}
	}
	calculate_total('',load_id);
}

function calculate_total_miles(load_id,driver_id)
{
	var odometer_from=document.getElementById('odometer_from'+load_id);
	var odometer_to=document.getElementById('odometer_to'+load_id);
	var total_miles=document.getElementById('total_miles'+load_id);
	var total=0;

	if( isNaN(odometer_from.value))
	{
		odometer_from.value=0;
		document.getElementById('odometer_from'+load_id).value=0;
	}
	 if( isNaN(odometer_to.value))
	{
		odometer_to.value=0;
		document.getElementById('odometer_to'+load_id).value=0;
	}
	
	if(odometer_from.value!=""  && odometer_to.value!="" &&  parseFloat(odometer_to.value)>=parseFloat(odometer_from.value))
	{//alert(odometer_to.value);alert(odometer_from.value);return false;
		total=parseFloat(odometer_to.value)-parseFloat(odometer_from.value);
		//$('#total_miles'+load_id).focus();
		add_bonus(load_id);
		document.getElementById('total_miles'+load_id).value=total;
		update_miles_ranges(load_id,driver_id);
		$.ajax({
			url:'exp_save_rates.php?action=updatetotalmiles&load_id='+load_id+'&miles='+total+'&driver_id='+driver_id+'&odometer_from='+odometer_from.value+'&odometer_to='+odometer_to.value,
			success:function(res)
			{
				return true;
			}
			
	});
		
	}
}

function calculate_total_miles1(load_id,driver_id)
{
	var odometer_from=document.getElementById('odometer_from'+load_id);
	var odometer_to=document.getElementById('odometer_to'+load_id);
	var total_miles=document.getElementById('total_miles'+load_id);
	var total=0;

	/*if(odometer_from.value=="")
	{
		alert("Please enter odometer from value.");
		return false;
	}
	else if(isNaN(odometer_from.value))
	{
		alert("Please enter only numbers.");
		return false;
	}
	else if(odometer_to.value=="")
	{
		alert("Please enter odometer to value.");
		return false;
	}
	else if(isNaN(odometer_to.value))
	{
		alert("Please enter only numbers.");
		return false;
	}
	else if(parseFloat(odometer_to.value)<parseFloat(odometer_from.value))
	{
		alert("From value can not be greater than to value.");
		return false;
	}*/
	if( isNaN(odometer_from.value))
	{
		odometer_from.value=0;
		//document.getElementById('odometer_from'+load_id).value=0;
	}
	 if(isNaN(odometer_to.value))
	{
		odometer_to.value=0;
		//document.getElementById('odometer_to'+load_id).value=0;
	}
	
	if(odometer_from.value!="" && odometer_to.value!="" && parseFloat(odometer_to.value)>=parseFloat(odometer_from.value))
	{
		total=parseFloat(odometer_to.value)-parseFloat(odometer_from.value);
		//$('#total_miles'+load_id).focus();
		document.getElementById('total_miles'+load_id).value=total;
	}
	calculate_total('',load_id);
}
function chk_final_confirmation()
{
	 if(confirm("Do you really want to finalize this settlement?Once finalized,you cann not change it."))
	 {return true;}
	 else
	 {return false;}
}
function remove_load(load_id) {
	$('#LOAD_'+load_id).remove();
	var load_code		=	document.getElementsByName('LOAD_CODE[]');
	
	if( load_code.length > 0 ) {
		calculate_total('',load_code[0].value);
	} else {
		document.getElementById('final_bonus').value = 0;
		document.getElementById('final_handling').value = 0;
		document.getElementById('final_trip_pay').value = 0;
		document.getElementById('final_settlement').value = 0;
	}
}

function remove_rate(rate_id, load_id) {
	$('#RATE_'+rate_id).remove();
	calculate_total('',load_id);
}

//! SCR# 183 - duplicate_rate function for duplicate button
String.prototype.replaceAll = function(search, replacement) {
    var target = this;
    return target.replace(new RegExp(search, 'g'), replacement);
};

function duplicate_rate(rate_id, load_id) {
	var orig_rate = $('#RATE_'+rate_id.toString());
	var new_rate = orig_rate.clone();
	var rate_code = new_rate.find('input#rate_code').val();
	var row_id = new_rate.attr('id');
	var i;
	
	for (i = 1; $("#RATE_" + rate_id.toString() + i.toString()).length > 0; i++) {}
	
	new_rate.html(new_rate.html().replaceAll(rate_code,rate_code+i.toString()));
	new_rate.attr('id', row_id+i.toString());

	orig_rate.after(new_rate);
	calculate_total('',load_id);
}

<?php
if(isset($_REQUEST['load_id']) && $_REQUEST['load_id']!='')
{
	?>
$('#odometer_from'+'<?php echo $_REQUEST['load_id'];?>').focus();
<?php
}
?>


</script>