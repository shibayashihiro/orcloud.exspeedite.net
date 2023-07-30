<?php 

// $Id: exp_updatedriverpay.php 5080 2023-04-13 21:54:41Z duncan $
// Update driver pay.

// Set flag that this is a parent file
define( '_STS_INCLUDE', 1 );

// Setup Session
require_once( "include/sts_session_setup.php" );

require_once( "include/sts_config.php" );
$sts_debug = (isset($_GET['debug'])  || isset($_POST['debug'])) && in_group( EXT_GROUP_DEBUG );
//if(isset($_POST['final_savepayrate'])) $sts_debug = true;
require_once( "include/sts_session_class.php" );

$my_session = sts_session::getInstance( $exspeedite_db, $sts_debug );
$my_session->access_check( $sts_table_access[DRIVER_TABLE] );	// Make sure we should be here

$sts_subtitle = "Update Driver Pay";
require_once( "include/header_inc.php" );
require_once( "include/navbar_inc.php" );

?>
<div class="container-full" role="main">
<?php
require_once( "include/sts_result_class.php" );
require_once( "include/sts_driver_class.php" );
require_once( "include/sts_setting_class.php" );
require_once( "sts_user_log_class.php" );

$setting_table = sts_setting::getInstance($exspeedite_db, $sts_debug);

//! This removes some fields not used for QuickBooks Online
$sts_export_qb = $setting_table->get( 'api', 'EXPORT_QUICKBOOKS' ) == 'true';
$sts_qb_online = $setting_table->get( 'api', 'QUICKBOOKS_ONLINE' ) == 'true';
$sts_manual_spawn = $setting_table->get( 'api', 'QUICKBOOKS_MANUAL_SPAWN' ) == 'true';
//! Duncan - SCR# 132 - bonusable manual rates
$sts_driver_manual_bonusable = $setting_table->get( 'option', 'DRIVER_MANRATES_BONUSABLE' ) == 'true';
$sts_multi_company = $setting_table->get("option", "MULTI_COMPANY") == 'true';


$driver=new sts_table($exspeedite_db , DRIVER_PAY_MASTER , $sts_debug);
$driver_obj=new sts_table($exspeedite_db , DRIVER_TABLE , $sts_debug);
$load_obj=new sts_table($exspeedite_db , LOAD_PAY_MASTER , $sts_debug);
$sql_obj=new sts_table($exspeedite_db , LOAD_TABLE , $sts_debug);
$rate_obj=new sts_table($exspeedite_db , LOAD_PAY_RATE , $sts_debug);
$man_obj=new sts_table($exspeedite_db , LOAD_MAN_RATE , $sts_debug);
$range_obj=new sts_table($exspeedite_db , LOAD_RANGE_RATE , $sts_debug);
$pro_obj=new sts_table($exspeedite_db , PROFILE_MASTER , $sts_debug);
$hol_obj=new sts_table($exspeedite_db , HOLIDAY_TABLE , $sts_debug);


$driver_table = sts_driver::getInstance($exspeedite_db, $sts_debug);
$rslt = new sts_result( $driver_table, false, $sts_debug );

// close the session here to avoid blocking
session_write_close();

########   update settlement details of a particular week ###############
if(isset($_GET['payid']) && $_GET['payid']!="") {
	$res = [];
	$res['debug'] = $sts_debug;

	if( $sts_debug ) {
		echo "<pre>GET, POST\n";
		var_dump($_GET);
		var_dump($_POST);
		echo "</pre>";
	}
	
	//! SCR# 615 - The code used to FAIL on 2019-12-30
	$start = date("Y-m-d", strtotime('monday this week -1 day'));
	$end = date("Y-m-d", strtotime('sunday this week -1 day'));
	
	$dt = $driver->database->get_multiple_rows( 
		"select b.DRIVER_ID, b.DRIVER_PAY_ID, b.LOAD_ID, a.WEEKEND_FROM, a.WEEKEND_TO
			from EXP_DRIVER_PAY_MASTER a, EXP_DRIVER_PAY_MASTER b
			where a.DRIVER_PAY_ID = ".$_GET['payid']."
			AND a.WEEKEND_FROM = b.WEEKEND_FROM
			AND a.WEEKEND_TO = b.WEEKEND_TO
			AND a.DRIVER_ID = b.DRIVER_ID");

	$existing_loads = array();
	$loadid_payid = array();
	if( is_array($dt) && count($dt) > 0 ) {
		$start = $dt[0]["WEEKEND_FROM"];
		$end = $dt[0]["WEEKEND_TO"];
		$driver_id = $dt[0]["DRIVER_ID"];
		foreach($dt as $row) {
			$existing_loads[] = $row["LOAD_ID"];
			$loadid_payid[$row["LOAD_ID"]] = $row["DRIVER_PAY_ID"];
		}
	}
	
	$res['start_date']=$start;
	$res['end_date']=$end;
	if( $sts_debug ) echo "<p>updatedriverpay: driver = $driver_id, start = $start, end = $end</p>";
	
	//! SCR# 508 - check driver pay for a given period for missing data
	$driver_table->check_driver_pay( $driver_id, $start, $end );
	
	$res['back'] = '';
	if(isset($_GET['back']) && $_GET['back'] <> '' ) {
		$res['back'] = $_GET['back'];
	}
	
	//! final_savepayrate or savepayrate...
	if(isset($_POST['final_savepayrate']) || isset($_POST['savepayrate'])) {
			//echo '<pre>';print_r($_POST);exit;
			$i=0;
			if(isset($_POST['load_id']) && count($_POST['load_id'])>0) {
				$max_unique_id=0;
				$max_unique=$driver->database->get_one_row("
					SELECT MAX(UNIQUE_ID) AS UNIQ
					FROM ".DRIVER_PAY_MASTER."");

				if($max_unique['UNIQ']!="")
					$max_unique_id=$max_unique['UNIQ']+1;
				
				//! Check for loads no longer in the form.
				$remove_loads = array_diff($existing_loads, $_POST['load_id']);
				if( $sts_debug ) echo "<p>updatedriverpay: existing_loads = ".
					implode(', ', $existing_loads)."</p>";

				//! SCR# 508 - Remove load 0
				if( in_array(0, $remove_loads)) {
					$remove_loads = array_diff($remove_loads, array(0));
					
					$check = $driver->database->get_one_row("SELECT TRIP_ID
						FROM EXP_DRIVER_PAY_MASTER
						WHERE WEEKEND_FROM = DATE('".$start."') 
						AND WEEKEND_TO = DATE('".$end."')
						AND DRIVER_ID = $driver_id
						AND LOAD_ID=0");
						
					if( is_array($check) && count($check) == 1 && isset($check['TRIP_ID'])) {
						$trip_id = $check['TRIP_ID'];
						if( $sts_debug ) echo "<p>updatedriverpay: remove load 0 trip_id = $trip_id</p>";
						//if( $sts_debug ) die;	// Stop before you delete something you regret...
						$driver->delete_row("WEEKEND_FROM = DATE('".$start."') 
							AND WEEKEND_TO = DATE('".$end."')
							AND DRIVER_ID = $driver_id
							AND TRIP_ID = $trip_id");
						
						//! Remove loads that should not be in exp_load_pay_master
						$load_obj->delete_row("DRIVER_ID = $driver_id AND TRIP_ID = $trip_id");
						
						//! Remove loads that should not be in exp_load_pay_rate
						$rate_obj->delete_row("DRIVER_ID = $driver_id AND TRIP_ID = $trip_id");
						
						//! Remove loads that should not be in exp_load_range_rate
						$range_obj->delete_row("DRIVER_ID = $driver_id AND TRIP_ID = $trip_id");
						
						//! Remove loads that should not be in exp_load_manual_rate
						$man_obj->delete_row("DRIVER_ID = $driver_id AND TRIP_ID = $trip_id");
					}
					
				}
					
				if( $sts_debug ) echo "<p>updatedriverpay: remove_loads = ".
					implode(', ', $remove_loads)."</p>";
				
				//if( $sts_debug ) die;	// Stop before you delete something you regret...
				if( is_array($remove_loads) && count($remove_loads) > 0) {
					//! Remove loads that should not be in exp_driver_pay_master
					$driver->delete_row("WEEKEND_FROM = DATE('".$start."') 
						AND WEEKEND_TO = DATE('".$end."')
						AND DRIVER_ID = $driver_id
						AND LOAD_ID IN (".implode(', ', $remove_loads).")");
					
					//! Remove loads that should not be in exp_load_pay_master
					$load_obj->delete_row("DRIVER_ID = $driver_id AND LOAD_ID IN (".implode(', ', $remove_loads).")");
					
					//! Remove loads that should not be in exp_load_pay_rate
					$rate_obj->delete_row("DRIVER_ID = $driver_id AND LOAD_ID IN (".implode(', ', $remove_loads).")");
					
					//! Remove loads that should not be in exp_load_range_rate
					$range_obj->delete_row("DRIVER_ID = $driver_id AND LOAD_ID IN (".implode(', ', $remove_loads).")");
					
					//! Remove loads that should not be in exp_load_manual_rate
					$man_obj->delete_row("DRIVER_ID = $driver_id AND LOAD_ID IN (".implode(', ', $remove_loads).")");
				}
				
				foreach($_POST['load_id'] as $load_id) {
					$trip=0;
					$load_id=$load_id;
					$where='';
					if($load_id==0) {
						//$get_count['CNT']=0;
						$trip=$_POST['tripID'][$i];
						$where='TRIP_ID='.$trip;
						$driver->database->get_one_row("
							UPDATE ".DRIVER_PAY_MASTER."
							SET FLAG='1'
							WHERE DRIVER_ID=".$_POST['driver_id']."
							AND TRIP_ID=".$trip);
					} else {
						$trip=0;
						$where='LOAD_ID='.$load_id;
					}
				
					//if($get_count['CNT']==0)
					{
						/*if($load_id!=0)
						{$approved_date=$_POST['approved_date'.$load_id];}
						else 
						{$approved_date=$_POST['approved_date'.$trip];}*/
						
						$approved_date=isset($_POST['approved_date']) ? $_POST['approved_date'] : 'NULL';
						$bonus_allow=$_POST['bonus_allow'][$i];
						$apply_bonus=floatval($_POST['apply_bonus'][$i]);
						$bonus_amount=floatval($_POST['bonus_amount'][$i]);
						$handling_pay=floatval($_POST['handling_pay'][$i]);
						$handling_pallet=intval($_POST['handling_pallet'][$i]);
						$total_trip_amount=floatval($_POST['total_trip_amount'][$i]);
						$total_settelment=floatval($_POST['totla_settelment'][$i]);
						$odometer_from=isset($_POST['odometer_from']) && isset($_POST['odometer_from'][$i]) ? $_POST['odometer_from'][$i] : 0;
						$odometer_to=isset($_POST['odometer_to']) && isset($_POST['odometer_to'][$i]) ? $_POST['odometer_to'][$i] : 0;
						$total_miles=floatval($_POST['total_miles'][$i]);
						$loaded_det_hr=floatval($_POST['loaded_det_hr'][$i]);
						$unloaded_det_hr=floatval($_POST['unloaded_det_hr'][$i]);
				
						##----------- INSERT INTO LOAD MASTER----------------##
										
						$load_obj->database->get_one_row("
							UPDATE ".LOAD_PAY_MASTER."
							SET ODOMETER_FROM='$odometer_from',
							ODOMETER_TO='$odometer_to',
							TOTAL_MILES='$total_miles',
							BONUS='$bonus_allow',
							APPLY_BONUS='$apply_bonus',
							BONUS_AMOUNT='$bonus_amount',
							TOTAL_TRIP_PAY='$total_trip_amount',
							HANDLING_PAY='$handling_pay',
							TOTAL_SETTLEMENT='$total_settelment',
							HANDLING_PALLET='$handling_pallet',
							LOADED_DET_HR='$loaded_det_hr',
							UNLOADED_DET_HR='$unloaded_det_hr'
							WHERE $where AND DRIVER_ID=".$_POST['driver_id']);
				
					##----------- delete & then INSERT ALL PAY RATES ---------##
						if($load_id!=0 && isset($_POST['category_name'.$load_id])) {
							$rate_obj->database->get_one_row("
								DELETE FROM ".LOAD_PAY_RATE."
								WHERE LOAD_ID=$load_id
								AND DRIVER_ID=".$_POST['driver_id']);
							
							$total_len=count($_POST['category_name'.$load_id]);
					        if($total_len>0) {
								for($j=0;$j<$total_len;$j++) {
									$title=empty($_POST['category_name'.$load_id][$j]) ? '' :
										$_POST['category_name'.$load_id][$j];
									$quantity=empty($_POST[$load_id.'_qty'][$j]) ? 0 :
										floatval($_POST[$load_id.'_qty'][$j]);
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
				
									//! Insert rates into load_pay_rate
									// 3/9 - Duncan, removed unnecessary quotes around numbers.
									$rate_obj->add_row("LOAD_ID, TITLE, QUANTITY,
										RATE, PAY, DRIVER_ID,
										TRIP_ID, LOAD_RATE_CODE, LOAD_RATE_NAME,
										LOAD_RATE_BONUS, CARD_ADVANCE_CODE",
										"$load_id, '$title', $quantity,
										$rate, $pay, ".$_POST['driver_id'].",
										$trip, '$lrcode', '$lrname',
										$lrbonus, NULL");
								}
							}
						 }
						 if($load_id==0 && isset($_POST['category_name_tr'.$trip]))  {
						 	$rate_obj->database->get_one_row("
						 		DELETE FROM ".LOAD_PAY_RATE."
						 		WHERE TRIP_ID=$trip
						 		AND DRIVER_ID=".$_POST['driver_id']);
						 		
						 	$total_len=count($_POST['category_name_tr'.$trip]);
						 	if($total_len>0) {
						 		for($j=0;$j<$total_len;$j++) {
									//! SCR# 180 - guard against missing data
									$title=empty($_POST['category_name_tr'.$trip][$j]) ? '' :
										$_POST['category_name_tr'.$trip][$j];
									$quantity=empty($_POST['_tr'.$trip.'_qty'][$j]) ? 0 :
										floatval($_POST['_tr'.$trip.'_qty'][$j]);
									$rate=empty($_POST['_tr'.$trip.'_amt'][$j]) ? 0 :
										floatval($_POST['_tr'.$trip.'_amt'][$j]);
									$pay=empty($_POST['text_total_tr'.$trip][$j]) ? 0 :
										floatval($_POST['text_total_tr'.$trip][$j]);
									$lrcode = empty($_POST['rate_code_tr'.$trip][$j]) ? '' :
										$_POST['rate_code_tr'.$trip][$j];
									$lrname = empty($_POST['rate_name_tr'.$trip][$j]) ? '' :
										$_POST['rate_name_tr'.$trip][$j];
									$lrbonus = empty($_POST['rate_bonus_tr'.$trip][$j]) ? 0 :
										intval($_POST['rate_bonus_tr'.$trip][$j]);

									//! SCR# 180 - store link back to card advance code
									$card_advance = empty($_POST['card_advance_tr'.$trip][$j]) ?
										'NULL' : intval($_POST['card_advance_tr'.$trip][$j]);
										
									// 3/9 - Duncan, removed unnecessary quotes around numbers.
									$rate_obj->add_row("LOAD_ID, TITLE, QUANTITY,
										RATE, PAY, DRIVER_ID,
										TRIP_ID, LOAD_RATE_CODE, LOAD_RATE_NAME,
										LOAD_RATE_BONUS, CARD_ADVANCE_CODE",
										"$load_id, '$title', $quantity,
										$rate, $pay, ".$_POST['driver_id'].",
										$trip, '$lrcode', '$lrname',
										$lrbonus, $card_advance");
						 		}
						 	}
						}
						 
						 
				##----------- ADD MANUAL RATES---------##
				if($load_id!=0 && isset($_POST['load_manual_codes'.$load_id]))
				 {
				 	$man_obj->database->get_one_row("
				 		DELETE FROM ".LOAD_MAN_RATE."
				 		WHERE LOAD_ID=$load_id
				 		AND DRIVER_ID=".$_POST['driver_id']);
				 	
					 $man_len=count($_POST['load_manual_codes'.$load_id]);
					 if($man_len>0) {
						for($k=0;$k<$man_len;$k++) {
							$load_manual_codes_rate=
								floatval($_POST['load_manual_codes'.$load_id][$k]);
							$manual_code=$_POST['manual_code'.$load_id][$k];
							$manual_desc=$_POST['manual_desc'.$load_id][$k];
				
							// 3/9 - Duncan, removed unnecessary quotes around numbers.
							$man_obj->add_row("LOAD_ID, MANUAL_CODE, MANUAL_DESC,
								MANUAL_RATE, DRIVER_ID, TRIP_ID",
								"$load_id, '$manual_code', '$manual_desc',
								$load_manual_codes_rate, ".$_POST['driver_id'].", $trip");
						}
					}
				}
				if($load_id==0 && isset($_POST['load_manual_codes_tr'.$trip])) {
					$man_obj->database->get_one_row("
						DELETE FROM ".LOAD_MAN_RATE."
						WHERE TRIP_ID=$trip
						AND DRIVER_ID=".$_POST['driver_id']);

					$man_len=count($_POST['load_manual_codes_tr'.$trip]);
					if($man_len>0) {
						for($k=0;$k<$man_len;$k++) {
							$load_manual_codes_rate=
								floatval($_POST['load_manual_codes_tr'.$trip][$k]);
							$manual_code=$_POST['manual_code_tr'.$trip][$k];
							$manual_desc=$_POST['manual_desc_tr'.$trip][$k];
					
							// 3/9 - Duncan, removed unnecessary quotes around numbers.
							$man_obj->add_row("LOAD_ID, MANUAL_CODE, MANUAL_DESC,
								MANUAL_RATE, DRIVER_ID, TRIP_ID",
								"$load_id, '$manual_code', '$manual_desc',
								$load_manual_codes_rate, ".$_POST['driver_id'].",
								$trip");
						}
					}
				}
				
				##---------- ADD RANGE RATES -----------##
				if($load_id==0 && isset($_POST['range_code'.$load_id])) {
					$range_obj->database->get_one_row("
						DELETE FROM ".LOAD_RANGE_RATE."
						WHERE LOAD_ID=$load_id
						AND DRIVER_ID=".$_POST['driver_id']);
						
					$ran_len=count($_POST['range_code'.$load_id]);
					if($ran_len>0) {
						for($x=0;$x<$ran_len;$x++) {
							$range_code=$_POST['range_code'.$load_id][$x];
							$range_name=$_POST['range_name'.$load_id][$x];
							$range_from=floatval($_POST['range_from'.$load_id][$x]);
							$range_to=floatval($_POST['range_to'.$load_id][$x]);
							$range_rate=floatval($_POST['range_rate'.$load_id][$x]);
							$range_miles=floatval($_POST['range_miles'.$load_id][$x]);
	
							// 3/9 - Duncan, removed unnecessary quotes around numbers.
							$range_obj->add_row("LOAD_ID, RANGE_CODE, RANGE_NAME,
								RANGE_FROM, RANGE_TO, RANGE_RATE,
								TOTAL_RATE, DRIVER_ID, TRIP_ID",
								"$load_id, '$range_code', '$range_name',
								$range_from, $range_to, $range_rate,
								$range_miles, ".$_POST['driver_id'].", $trip");
								
							}
						}
					}
					
					if($load_id!=0 && isset($_POST['range_code_tr'.$trip])) {
						$range_obj->database->get_one_row("
							DELETE FROM ".LOAD_RANGE_RATE."
							WHERE TRIP_ID=$trip
							AND DRIVER_ID=".$_POST['driver_id']);
							
						$ran_len=count($_POST['range_code_tr'.$trip]);
						if($ran_len>0) {
							for($x=0;$x<$ran_len;$x++) {
								$range_code=$_POST['range_code_tr'.$trip][$x];
								$range_name=$_POST['range_name'.$trip][$x];
								$range_from=floatval($_POST['range_from_tr'.$trip][$x]);
								$range_to=floatval($_POST['range_to_tr'.$trip][$x]);
								$range_rate=floatval($_POST['range_rate_tr'.$trip][$x]);
								$range_miles=floatval($_POST['range_miles_tr'.$trip][$x]);
						
								// 3/9 - Duncan, removed unnecessary quotes around numbers.
								$range_obj->add_row("LOAD_ID, RANGE_CODE, RANGE_NAME,
									RANGE_FROM, RANGE_TO, RANGE_RATE,
									TOTAL_RATE, DRIVER_ID, TRIP_ID",
									"$load_id, '$range_code', '$range_name',
									$range_from, $range_to, $range_rate,
									$range_miles, ".$_POST['driver_id'].", $trip");
																					
								}
							}
						}
				
 						##--------- ADD REMAINING INFO IN DRIVER PAY TABLE------##
						$weekend_from=$start;
						$weekend_to=$end;
						$trip_pay=$total_trip_amount; //$_POST['final_trip_pay1'];
						$final_bonus=$bonus_amount; //$_POST['final_bonus1'];
						$final_handling=$handling_pay; //$_POST['final_handling1'];
						$final_settlement=$total_settelment; //$_POST['final_settlement1'];
				
				        // 3/9 - Duncan, removed unnecessary quotes around numbers.
				        $driver->database->get_one_row("
				        	UPDATE ".DRIVER_PAY_MASTER."
				        	SET TRIP_PAY='$trip_pay',
				        	BONUS='$final_bonus',
				        	HANDLING='$final_handling',
				        	GROSS_EARNING='$final_settlement',
				        	ADDED_ON='".date('Y-m-d H:i:s')."',
				        	TRIP_ID=$trip
				        	WHERE DRIVER_ID=".$_POST['driver_id']."
				        	AND $where");
						
						##---------------finalize user ----------------##
						if(isset($_POST['final_savepayrate'])) {
							// 3/9 - Duncan, removed unnecessary quotes around numbers.
							$driver->database->get_one_row("
								UPDATE ".DRIVER_PAY_MASTER."
								SET FINALIZE_STATUS='finalized'
								WHERE DRIVER_ID=".$_POST['driver_id']."
								AND LOAD_ID=".$load_id);

						}
						$i++;
				
				} // $load_id!=0 ...
			} // foreach

			//! Quickbooks - queue up request to pay driver
			// queue QUICKBOOKS_QUERY_EMPLOYEE, EXP_DRIVER_PAY_MASTER.DRIVER_PAY_ID
			if( $sts_export_qb && isset($_POST['final_savepayrate']) ) {
				require_once( $sts_crm_dir."quickbooks-php-master/QuickBooks.php" );

				// Queue up the Quickbooks API request
				$Queue = new QuickBooks_WebConnector_Queue($sts_qb_dsn);
				$Queue->enqueue(QUICKBOOKS_QUERY_VENDOR, $_GET['payid'], 0, 
					array( 'vendortype' => QUICKBOOKS_VENDOR_DRIVER ) );

				if( $sts_qb_online && ! $sts_manual_spawn)	//! Needed for QBOE only
					require_once( $sts_crm_dir.DIRECTORY_SEPARATOR.
						"quickbooks-php-master".
						DIRECTORY_SEPARATOR."online".
						DIRECTORY_SEPARATOR."spawn_process.php" );
			}
			
			//! SCR# 191 - log approval
			if( isset($_POST['final_savepayrate']) ) {
				$user_log = sts_user_log::getInstance($exspeedite_db, $sts_debug);
				$check = $user_log->database->get_one_row("
					SELECT CONCAT_WS(' ',FIRST_NAME, LAST_NAME) AS DRIVER
					FROM EXP_DRIVER
					WHERE DRIVER_CODE = ".$_POST['driver_id']);
				$user_log->log_event('finance', 'Approved Driver Pay for '.
					$check['DRIVER'].' '.$start.' - '.$end);
			}
		}
		//! Jump after save
		if( ! $sts_debug )
			echo '<script>window.location.href="exp_billhistory.php?CODE='.$_POST['driver_id'].'"</script>';
	}
			
			
	//! get saved driver pay info		

	$res['detail']=$driver->database->get_one_row("
		SELECT * FROM ".DRIVER_PAY_MASTER."
		WHERE DRIVER_PAY_ID=".$_GET['payid']);
		
	$res['driver']=$driver_obj->database->get_one_row("
		SELECT FIRST_NAME, LAST_NAME, MIDDLE_NAME, PROFILE_ID
		FROM ".DRIVER_TABLE."
		WHERE DRIVER_CODE=".$res['detail']['DRIVER_ID']);
		
$contract_name="";
if(isset($res['driver']['PROFILE_ID']) && $res['driver']['PROFILE_ID']!=0) {
	$contract_name_arr=$pro_obj->database->get_one_row("
		SELECT PROFILE_NAME FROM ".PROFILE_MASTER."
		WHERE ".PROFILE_MASTER.".CONTRACT_ID=".$res['driver']['PROFILE_ID']);
		
	$contract_name=$contract_name_arr['PROFILE_NAME'];
}
$res['contract_name']=$contract_name;

	$output = array();
	$output['DRIVER_PAY_ID']=$res['DRIVER_PAY_ID']=$_GET['payid'];
	$res['driver_id']=$res['detail']['DRIVER_ID'];
	echo $rslt->render_update_driver_pay( $res );
	if($res['detail']['DRIVER_ID']!="")
	{
		
		if(isset($_POST['btn_search_load']))
		{
			$from_date=$_POST['ACTUAL_DEPART_FROM'];
			$to_date=$_POST['ACTUAL_DEPART_TO'];
			$where_str='';
			if($from_date!="" && $to_date!="")
			{
				$where_str="AND ".STOP_TABLE.".STOP_TYPE='drop' AND (ACTUAL_DEPART BETWEEN '".date('Y-m-d H:i:s',strtotime($from_date))."' AND '".date('Y-m-d H:i:s',strtotime($to_date))."')";
			}
			else if($from_date!="")
			{
				$where_str="AND ".STOP_TABLE.".STOP_TYPE='drop' AND (ACTUAL_DEPART >='".date('Y-m-d H:i:s',strtotime($from_date))."')";
			}
			else if($to_date!="")
			{
				$where_str="AND ".STOP_TABLE.".STOP_TYPE='drop' AND (ACTUAL_DEPART <='".date('Y-m-d H:i:s',strtotime($to_date))."')";
			}
			$load_list=$driver->database->get_multiple_rows("
				SELECT DISTINCT LOAD_ID, TRIP_ID
				FROM ".DRIVER_PAY_MASTER."
				JOIN ".STOP_TABLE."
				ON ".STOP_TABLE.".LOAD_CODE=".DRIVER_PAY_MASTER.".LOAD_ID
				WHERE WEEKEND_FROM='".$start."'
				AND WEEKEND_TO='".$end."'
				AND DRIVER_ID='".$res['detail']['DRIVER_ID']."'
				AND FINALIZE_STATUS <> 'finalized'
				$where_str");
		} else {
			$load_list=$driver->database->get_multiple_rows("
				SELECT LOAD_ID, TRIP_ID, TRIP_PAY, GROSS_EARNING
				FROM ".DRIVER_PAY_MASTER."
				WHERE WEEKEND_FROM='".$start."'
				AND WEEKEND_TO='".$end."'
				AND DRIVER_ID=".$res['detail']['DRIVER_ID']."
				AND FINALIZE_STATUS <> 'finalized'");
		}
	$driver_assign_load_with_stop=array();
	//print_r($load_list);
	$output['driver_id']=$res['detail']['DRIVER_ID'];
	$output['all_manual_rates'] = $sql_obj->database->get_multiple_rows("
		SELECT * FROM ".MANUAL_RATES."
		WHERE DRIVER_ID=".$res['detail']['DRIVER_ID']);
		
	if(count($load_list)>0) {
		$i=0;
		foreach($load_list as $load) {
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
//print_r($stop_detail);/*	(CASE
                                                            //    WHEN STOP_TYPE = 'pick' THEN PICKUP_DATE
                                                              //  WHEN STOP_TYPE = 'drop' THEN DELIVER_DATE
                                                              //  ELSE NULL END ) AS DUE_DATE,*/
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
   $i++; }
}
//echo '<pre/>';print_r($all_stop_detail);
$output['STOP_DETAIL']=$all_stop_detail;
	} else {
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
			
			$output['info']=$sql_obj->database->get_one_row("
				SELECT ".TRACTOR_TABLE.".UNIT_NUMBER,
				".TRAILER_TABLE.".UNIT_NUMBER AS TRAILER_NUMBER,
				".SHIPMENT_TABLE.".CONS_NAME,
				".LOAD_TABLE.".TOTAL_DISTANCE,
				".LOAD_TABLE.".EMPTY_DISTANCE,
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
			SELECT SUM(B.FREIGHT_CHARGES + B.STOP_OFF + B.DETENTION_RATE + B.UNLOADED_DETENTION_RATE) AS LINE_HAUL, SUM(B.FUEL_COST) FUEL_COST
			FROM EXP_SHIPMENT S
			LEFT JOIN EXP_CLIENT_BILLING B
			ON S.SHIPMENT_CODE = B.SHIPMENT_ID
			WHERE LOAD_CODE = ".$load['LOAD_ID'] );
		
		$output['load_detail']["LINE_HAUL"] = isset($linehaul["LINE_HAUL"]) ? $linehaul["LINE_HAUL"] : 0;
		$output['load_detail']["FUEL_COST"] = isset($linehaul["FUEL_COST"]) ? $linehaul["FUEL_COST"] : 0;
			
			$output['status']='success';
			//echo '<pre/>';print_r($output);
			$output['multi_company'] = $sts_multi_company;
			echo $rslt->render_update_load_detail( $output );
			//echo '<pre/>';print_r($output);
		}
	} else {
		$output['status']='error';
		echo $rslt->render_update_load_detail( $output );
	}
	}
	$fetch_totals=$fetch_totals_trip=array();
	$fetch_totals=$driver->database->get_multiple_rows("
		SELECT TRIP_PAY, BONUS, HANDLING, GROSS_EARNING, ADDED_ON
		FROM ".DRIVER_PAY_MASTER."
		WHERE WEEKEND_FROM='".date('Y-m-d',strtotime($res['detail']['WEEKEND_FROM']))."'
		AND WEEKEND_TO='".date('Y-m-d',strtotime($res['detail']['WEEKEND_TO']))."'
		AND DRIVER_ID=".$res['detail']['DRIVER_ID']."
		AND LOAD_ID!=0
		AND FLAG='0'"); //GROUP BY UNIQUE_ID
	
	$fetch_totals_trip=$driver->database->get_multiple_rows("
		SELECT TRIP_PAY, BONUS, HANDLING, GROSS_EARNING, ADDED_ON
		FROM ".DRIVER_PAY_MASTER."
		WHERE WEEKEND_FROM='".date('Y-m-d',strtotime($res['detail']['WEEKEND_FROM']))."'
		AND WEEKEND_TO='".date('Y-m-d',strtotime($res['detail']['WEEKEND_TO']))."'
		AND DRIVER_ID=".$res['detail']['DRIVER_ID']."
		AND TRIP_ID!=0
		AND FLAG='1'"); //GROUP BY UNIQUE_ID
	
	$res['fetch_totals']=$fetch_totals;
	$res['fetch_totals_trip']=$fetch_totals_trip;
	
	echo $rslt->render_update_driver_end( $res );
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

function add_manual_fun(load_id,driver_id,type)
{
	//alert(load_id);
	var manual_rates	=	document.getElementById('manual_rates'+load_id);
	//alert(manual_rates.value);
	if(manual_rates.value=="")
	{
		alert("Please select manual rates first.");
		return false;
	}
	else
	{
		$('#reload_manual_rate_div'+load_id).show();
		if(type=='trip')
		{
			var arr=load_id.split("_tr");
			load_id1=arr[1];
		}
		else
		{
			load_id1=load_id;
		}
	$.ajax({
			url:'exp_ajax_functions.php?action=driver_fetchonemanualrate&val='+manual_rates.value+'&load_id='+load_id1+'&driver_id='+driver_id+'&type='+type,
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
					calculate_total('',load_id);
					add_bonus(load_id);
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
		var final_bonus						=	document.getElementById('final_bonus1');
		var final_handling					=	document.getElementById('final_handling1');
		var final_trip_pay					=	document.getElementById('final_trip_pay1');
		var handling_pay_name		=	document.getElementsByName('handling_pay[]');
		var final_settlement				=	document.getElementById('final_settlement1');
		var settle_name						=	document.getElementsByName('totla_settelment[]');
		
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
			if( amt_val == '' ) amt_val = 0;
			
			console.log(load_id, id, qty_val, free_hours, amt_val, total.value);
			var grand_total	= Math.max( 0, parseFloat(qty_val) - parseFloat(free_hours)) * parseFloat(amt_val);
			if(grand_total!="" && !isNaN(grand_total))
			{grand_total=Math.round(grand_total*100)/100;}
			total.value=(grand_total).toFixed(2);
			//console.log(load_id, id, 'grand_total', grand_total);
		}

		add_bonus(load_id);

		//final bonus
		var len_bonus_amount_name	=	bonus_amount_name.length;
		for(var a=0 ; a < len_bonus_amount_name ; a++)
		{
			if(bonus_amount_name[a].value!=''){ final_bonus_cost = parseFloat(final_bonus_cost) + parseFloat(bonus_amount_name[a].value); }
		}
		final_bonus.value=final_bonus_cost;
		if(final_bonus_cost!="" && !isNaN(final_bonus_cost))
			{
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
		if(final_sett_amt!="" && !isNaN(final_sett_amt)) {
			final_settlement.value=(Math.round(final_sett_amt*100)/100).toFixed(2);
			
			// Update button
			var final_button = document.getElementById("final_button");
			if( final_settlement.value > 0 ) {
				final_button.innerHTML = '<button class="btn btn-md btn-success" name="final_savepayrate" type="submit" onclick="return chk_final_confirmation();"><span class="glyphicon glyphicon-ok"></span> Finalize / Approve</button>';
			} else {
				final_button.innerHTML = '<button class="btn btn-md btn-danger" name="final_savepayrate" type="submit" disabled><span class="glyphicon glyphicon-remove"></span> Cannot Approve Minus Gross Earnings</button>';
			}
		}
		
}

// Once the total_miles has been updated, need to redo the ranges or the per mile
function update_miles_ranges(load_id,driver_id) {
	var total_miles			=	document.getElementById('total_miles'+load_id);
	var empty_miles			=	document.getElementById('empty_miles'+load_id);
	var range_name			=	document.getElementsByName('range_name'+load_id+'[]');
	var range_from			=	document.getElementsByName('range_from'+load_id+'[]');
	var range_to			=	document.getElementsByName('range_to'+load_id+'[]');
	var range_rate			=	document.getElementsByName('range_rate'+load_id+'[]');
	var range_miles			=	document.getElementsByName('range_miles'+load_id+'[]');

	var category_name			=	document.getElementsByName('category_name'+load_id+'[]');
	var per_miles				=	document.getElementsByName(load_id+'_qty[]');
	var per_miles_amt			=	document.getElementsByName(load_id+'_amt[]');
	var per_miles_total			=	document.getElementsByName('text_total'+load_id+'[]');

	var range_len			=	range_from.length;	// Assume all the arrays are the same length.
	var miles				=	parseFloat(total_miles.value);
	var empty				=	parseFloat(empty_miles.value);
	//console.log('miles=',miles,'empty=',empty);
	var matched_rate		=	false;
	
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
					//console.log('Loaded Miles',miles - empty);
					per_miles[i].value = miles - empty;
					per_miles_total[i].value = ((miles - empty) * parseFloat(per_miles_amt[i].value)).toFixed(2);
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

function calculate_total_miles(load_id,driver_pay_id,driver_id,type)
{
	var odometer_from=document.getElementById('odometer_from'+load_id);
	var odometer_to=document.getElementById('odometer_to'+load_id);
	var total_miles=document.getElementById('total_miles'+load_id);
	var trip_id=0;
	var load_id1=load_id;
	if(type=='trip')
	{
		var arr=load_id.split("_tr");
		load_id1=arr[1];
	}
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
	
	if(odometer_from.value!=""  && odometer_to.value!="" &&  (parseFloat(odometer_to.value)>=parseFloat(odometer_from.value)))
	{
		total=parseFloat(odometer_to.value)-parseFloat(odometer_from.value);
		//$('#total_miles'+load_id).focus();
		document.getElementById('total_miles'+load_id).value=total;
		update_miles_ranges(load_id,driver_id);
		$.ajax({
			url:'exp_ajax_functions.php?action=update_drivertotalmiles&load_id='+load_id1+'&miles='+total+'&driver_id='+driver_id+'&odometer_from='+odometer_from.value+'&odometer_to='+odometer_to.value+'&type='+type,
			type:'POST',
			//async:false,
			success:function(res)
			{//return false;
				//window.location.href="exp_updatedriverpay.php?payid="+driver_pay_id+'&load_id='+load_id;
				
				return true;
			}
			
	});
		
	}
}

function chk_final_confirmation()
{
	 if(confirm("Do you really want to finalize this settlement?Once finalized,you cann not change it."))
	 {return true;}
	 else
	 {return false;}
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

function remove_load(load_id) {
	$('#LOAD_'+load_id).remove();
	var load_code		=	document.getElementsByName('LOAD_CODE[]');
	
	if( load_code.length > 0 ) {
		calculate_total('',load_code[0].value);
	} else {
		document.getElementById('final_bonus1').value = 0;
		document.getElementById('final_handling1').value = 0;
		document.getElementById('final_trip_pay1').value = 0;
		document.getElementById('final_settlement1').value = 0;
		$('button[name=savepayrate]').attr('disabled','disabled');
		$('button[name=final_savepayrate]').attr('disabled','disabled');
	}
}

function remove_advance(rate_id, load_id) {
	$('#RATE_'+rate_id).remove();
	calculate_total('',load_id);
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
	var row_id_num = row_id.replace('RATE_', '');
	var i;
	
	for (i = 1; $("#RATE_" + rate_id.toString() + i.toString()).length > 0; i++) {}
	
	var h = new_rate.html();
	h = h.replaceAll(rate_code,rate_code+i.toString());
	h = h.replaceAll('\''+row_id_num+'\'','\''+row_id_num+i.toString()+'\'');
	h = h.replaceAll('_'+row_id_num, '_'+row_id_num+i.toString());
	new_rate.html(h);
		
	new_rate.attr('id', row_id+i.toString());

	orig_rate.after(new_rate);
	calculate_total('',load_id);
}

<?php
if(isset($_REQUEST['load_id']) && isset($_REQUEST['load_id'][0]) && $_REQUEST['load_id'][0]!='')
{
	?>
$('#odometer_from'+'<?php echo $_REQUEST['load_id'][0];?>').focus();
<?php
}if(isset($_REQUEST['focus']) && $_REQUEST['focus']!='')
{
?>
$('#final_settlement1').focus();
<?php }?>
</script>

