<?php 
// Set flag that this is a parent file
define( '_STS_INCLUDE', 1 );

// Setup Session
require_once( "include/sts_session_setup.php" ); //used to set a constant

require_once( "include/sts_config.php" );
$sts_debug = isset($_GET['debug']) && in_group( EXT_GROUP_DEBUG );
require_once( "include/sts_session_class.php" );
require_once("include/sts_client_class.php");
require_once( "include/sts_result_class.php" );

$my_session = sts_session::getInstance( $exspeedite_db, $sts_debug );
$my_session->access_check( EXT_GROUP_USER );	// Make sure we should be here

$sts_subtitle = ucfirst($_GET['TYPE'])." Rate";
require_once( "include/header_inc.php" );
require_once( "include/navbar_inc.php" );

//$sql_obj	=	new sts_table($exspeedite_db , CLIENT_RATE , $sts_debug);
$client_master=new sts_table($exspeedite_db , CLIENT_TABLE , $sts_debug);
$client_cat_master=new sts_table($exspeedite_db , CLIENT_CAT , $sts_debug);
$client_cat_rate=new sts_table($exspeedite_db , CLIENT_CAT_RATE , $sts_debug);
$client_assign_rate=new sts_table($exspeedite_db , CLIENT_ASSIGN_RATE , $sts_debug);
$hand_sql_obj	=	new sts_table($exspeedite_db , HANDLING_TABLE , $sts_debug);
$freight_obj=new sts_table($exspeedite_db , FRIGHT_RATE_TABLE , $sts_debug);
$fsc_obj=new sts_table($exspeedite_db , FSC_SCHEDULE , $sts_debug);
$client_fsc=new sts_table($exspeedite_db , CLIENT_FSC , $sts_debug);
$client_rate_obj=new sts_table($exspeedite_db , CLIENT_RATE_MASTER , $sts_debug);
$carrier_obj=new sts_table($exspeedite_db , CARRIER_TABLE , $sts_debug);
$pallet_obj=new sts_table($exspeedite_db , PALLET_MASTER , $sts_debug);
$pallet_rate=new sts_table($exspeedite_db , PALLET_RATE , $sts_debug);
$det_obj=new sts_table($exspeedite_db , DETENTION_MASTER , $sts_debug);
$det_rate=new sts_table($exspeedite_db , DETENTION_RATE , $sts_debug);
$un_det_rate=new sts_table($exspeedite_db , UNLOADED_DETENTION_RATE , $sts_debug);

$client_obj =	new sts_client($exspeedite_db , $sts_debug);
$res_obj = new sts_result( $client_obj , false, $sts_debug );

require_once( "include/sts_setting_class.php" );

$setting_table = sts_setting::getInstance($exspeedite_db, $sts_debug);
$client_matches = (int) $setting_table->get( 'option', 'CLIENT_MATCHES' );
if( $client_matches <= 0 ) $client_matches = 20;

$user_type=$_GET['TYPE'];
$client_id=$_GET['CODE'];
if($client_id!="")
{//echo '<pre/>';print_r($_POST);
	##-------  add detention rate ---------##
	if(isset($_POST['DET_ID']) && isset($_POST['DET_HOUR']) && isset($_POST['DET_HOUR_TO']) && isset($_POST['DET_RATE']))
	{
		$det_id=$_POST['DET_ID'];
		$det_hr=$_POST['DET_HOUR'];
		$det_hr_to=$_POST['DET_HOUR_TO'];
		$det_rat=$_POST['DET_RATE'];
		
		// 3/6 - Duncan, removed unnecessary quotes around numbers.
		$det_rate->add_row('DETENTION_ID, DET_HOUR, DET_HOUR_TO, DET_RATE',
			"$det_id, $det_hr, $det_hr_to, $det_rat");
	}

	##-------update detention rate ---------##
	if(isset($_POST['ED_DET_ID']) && isset($_POST['ED_DET_HOUR']) && isset($_POST['ED_DET_HOUR_TO']) && isset($_POST['ED_DET_RATE']) && isset($_POST['ED_DET_HR_ID']))
	{//print_r($_POST);exit;
		$ed_det_id=$_POST['ED_DET_ID'];
		$ed_det_hr=$_POST['ED_DET_HOUR'];
		$ed_det_hr_to=$_POST['ED_DET_HOUR_TO'];
		$ed_det_rat=$_POST['ED_DET_RATE'];
		$ed_det_hr_id=$_POST['ED_DET_HR_ID'];
		
		$det_rate->update($ed_det_hr_id,
			array('DETENTION_ID'=>$ed_det_id, 'DET_HOUR'=>$ed_det_hr,
			'DET_HOUR_TO'=>$ed_det_hr_to, 'DET_RATE'=>$ed_det_rat));
	}
	
	
	##-------- add unloaded detention rate--------##
	if(isset($_POST['UN_DET_HOUR']) && isset($_POST['UN_DET_HOUR_TO']) && isset($_POST['UN_DET_RATE']) && isset($_POST['MAIN_DET_ID']))
	{
		$det_id=$_POST['MAIN_DET_ID'];
		$un_det_hr=$_POST['UN_DET_HOUR'];
		$un_det_hr_to=$_POST['UN_DET_HOUR_TO'];
		$un_det_rat=$_POST['UN_DET_RATE'];

		// 3/6 - Duncan, removed unnecessary quotes around numbers.
		$un_det_rate->add_row('DETENTION_ID, UNDET_HR_FROM, UNDET_HR_TO, UN_DET_RATE',
			"$det_id, $un_det_hr, $un_det_hr_to, $un_det_rat");
	}
	
	##-------- update unloaded detention rate--------##
	if(isset($_POST['ED_UN_DET_ID']) && isset($_POST['ED_UN_DET_HR_ID']) && isset($_POST['ED_UN_DET_HOUR']) && isset($_POST['ED_UN_DET_HOUR_TO']) && isset($_POST['ED_UN_DET_RATE']))
	{
		$ed_un_det_id=$_POST['ED_UN_DET_ID'];
		$ed_un_det_hr=$_POST['ED_UN_DET_HOUR'];
		$ed_un_det_hr_to=$_POST['ED_UN_DET_HOUR_TO'];
		$ed_un_det_rat=$_POST['ED_UN_DET_RATE'];
		$ed_un_det_hr_id=$_POST['ED_UN_DET_HR_ID'];
		
		$un_det_rate->update($ed_un_det_hr_id,
			array('DETENTION_ID'=>$ed_un_det_id, 'UNDET_HR_FROM'=>$ed_un_det_hr,
			'UNDET_HR_TO'=>$ed_un_det_hr_to, 'UN_DET_RATE'=>$ed_un_det_rat));
	}
	
	//if(isset($_POST['save_client_pallet_rate']))
	##-------  add pallet rate ---------##
	if(isset($_POST['PALLETS_ID']) && isset($_POST['PALLETS_FROM']) && isset($_POST['PALLETS_TO']) && isset($_POST['PALLETS_RATE']))
	{
		$PALLETS_ID=$_POST['PALLETS_ID'];
		$PALLETS_FROM=$_POST['PALLETS_FROM'];
		$PALLETS_TO=$_POST['PALLETS_TO'];
		$PALLETS_RATE=$_POST['PALLETS_RATE'];

		// 3/6 - Duncan, removed unnecessary quotes around numbers.
		$pallet_rate->add_row('PALLET_ID, PALLET_FROM, PALLET_TO, PALLET_RATE',
			"$PALLETS_ID, $PALLETS_FROM, $PALLETS_TO, $PALLETS_RATE");
	}
		
	##-------  edit pallet rate ---------##
	if(isset($_POST['ED_PAL_RATE_ID']) && isset($_POST['ED_PALLETS_FROM']) && isset($_POST['ED_PALLETS_TO']) && isset($_POST['ED_PALLETS_RATE']) && isset($_POST['ED_PALL_ID']))
	{
		$ED_PALLETS_ID=$_POST['ED_PALL_ID'];
		$ED_PALLETS_FROM=$_POST['ED_PALLETS_FROM'];
		$ED_PALLETS_TO=$_POST['ED_PALLETS_TO'];
		$ED_PALLETS_RATE=$_POST['ED_PALLETS_RATE'];
		$ED_PALLETS_RATE_ID=$_POST['ED_PAL_RATE_ID'];
		//$pallet_rate->add_row('PALLET_ID,PALLET_FROM,PALLET_TO,PALLET_RATE','"'.$PALLETS_ID.'","'.$PALLETS_FROM.'","'.$PALLETS_TO.'","'.$PALLETS_RATE.'"');

		$pallet_rate->update($ED_PALLETS_RATE_ID,
			array('PALLET_ID'=>$ED_PALLETS_ID, 'PALLET_FROM'=>$ED_PALLETS_FROM,
			'PALLET_TO'=>$ED_PALLETS_TO, 'PALLET_RATE'=>$ED_PALLETS_RATE));
		
	}
	#upload the handling changes of the client//carrier
	if(isset($_FILES['HANDLING_CHARGES']) && isset($_POST['UPLOAD_HANDLING']))
	{
			$handle = fopen($_FILES['HANDLING_CHARGES']['tmp_name'], "r");
			// Empty all table
			//$hand_sql_obj->database->get_multiple_rows("DELETE FROM ".HANDLING_TABLE." WHERE CLIENT_ID='".$_GET['CODE']."' AND USER_TYPE='$user_type'");
			while (($data = fgetcsv($handle, 1000, ",")) != FALSE) {
				
				// 3/6 - Duncan, removed unnecessary quotes around numbers.
				$fields='CLIENT_ID, USER_TYPE, ORIGIN_NAME, FROM_CITY,
					FROM_STATE, FROM_ZIP, FROM_ZONE, CONSIGNEE_NAME,
					TO_CITY, TO_STATE, TO_ZIP, TO_ZONE,
					PALLET, INVOICE_COST, DRIVER_COST ';
				$values=$_GET['CODE'].", '$user_type', '".$hand_sql_obj->real_escape_string($data[0])."', '$data[1]',
				'$data[2]', '$data[3]', '$data[4]', '".$hand_sql_obj->real_escape_string($data[5])."',
				'$data[6]', '$data[7]', '$data[8]', '$data[9]',
				$data[10], $data[11], $data[12]";

				$hand_sql_obj->add_row( $fields, $values);
			} 
			fclose($handle);
	}

	#upload the handling changes of the client
	if(isset($_FILES['FREIGHT_MINIMUM_CHARGE']) && isset($_POST['UPLOAD_FRIGHT']))
	{
			$frieght = fopen($_FILES['FREIGHT_MINIMUM_CHARGE']['tmp_name'], "r");
			// Empty all table
			//$freight_obj->database->get_multiple_rows("DELETE FROM ".FRIGHT_RATE_TABLE." WHERE CLIENT_ID='".$_GET['CODE']."' AND USER_TYPE='$user_type'");
			while (($data = fgetcsv($frieght, 1000, ",")) != FALSE) {

				// 3/6 - Duncan, removed unnecessary quotes around numbers.
				$fields='CLIENT_ID, USER_TYPE, SHIPPER_NAME, ORIGIN_CITY,
					ORIGIN_STATE, ORIGIN_ZIP, CONSIGNEE_NAME, DESTINATION_CITY,
					DESTINAION_STATE, DESTINATION_ZIP, LINE_HAUL, MIN_LINE_HAUL,
					FSC, MILEAGE, WEEKEND_HOLIDAY, ENROUTE_STOP_OFFS,
					DETENTION, PER_MILE, AMOUNT';
				$values=$_GET['CODE'].", '$user_type', '".$freight_obj->real_escape_string($data[0])."', '$data[1]',
				'$data[2]', '$data[3]', '".$freight_obj->real_escape_string($data[4])."', '$data[5]',
				'$data[6]', '$data[7]', $data[8], $data[9],
				'$data[10]', $data[11], $data[12], $data[13],
				$data[14], $data[15], $data[16]";

				$freight_obj->add_row( $fields, $values);
			} 
			fclose($frieght);
			//print "Import done";
	}
	##-------- assign rates of categories  to current client /carrier------##
	if(isset($_POST['saverate'])) {
		$category_rate_list=$_POST['rate_ids'];
		//print_r($category_rate_list);
		if(count($category_rate_list)>0) {
			foreach($category_rate_list as $clist) {
				// 3/6 - Duncan, removed unnecessary quotes around numbers.
				$cnt=$client_assign_rate->database->get_multiple_rows(
					"SELECT COUNT(*) AS total
					FROM ".CLIENT_ASSIGN_RATE."
					WHERE CLIENT_ID=$client_id
					AND RATE_ID=".$clist."
					AND USER_TYPE='".$user_type."'");
				
				if($cnt[0]['total']==0) {
				// 3/6 - Duncan, removed unnecessary quotes around numbers.
					$client_assign_rate->add_row("CLIENT_ID, RATE_ID, USER_TYPE",
						"$client_id, ".$clist.", '$user_type'");
				}
			}
		}
	}
	
	##-------- assign fsc schedule to current client  /carrier--------------##
	if(isset($_POST['assign_fsc']))
	{
		$fsc_list=$_POST['fsc_ids'];
		if(count($fsc_list)>0)
		{
			foreach($fsc_list as $fs)
			{
				##-----  check duplication  --##
				// 3/6 - Duncan, removed unnecessary quotes around numbers.
				$out=$client_fsc->database->get_multiple_rows(
					"SELECT COUNT(*)  AS CNT
					FROM ".CLIENT_FSC."
					WHERE CLIENT_ID=$client_id
					AND  FSC_ID='".$fs."'
					AND USER_TYPE='$user_type'");
			
				if($out[0]['CNT']==0) {
					// 3/6 - Duncan, removed unnecessary quotes around numbers.
					$client_fsc->add_row("CLIENT_ID, FSC_ID, USER_TYPE",
						"$client_id, '".$fs."', '$user_type'");
				}
			}
		}
	}
	
	##---------- add handling charge----------##
	if(isset($_POST['save_handling_charge'])) {
		$origin_name=$hand_sql_obj->real_escape_string($_POST['origin_name']);
		$from_city=$_POST['from_city'];
		$from_state=$_POST['from_state'];
		$from_zip=$_POST['from_zip'];
		$from_zone=$_POST['from_zone'];
		$consignee_name=$hand_sql_obj->real_escape_string($_POST['consignee_name']);
		$to_city=$_POST['to_city'];
		$to_state=$_POST['to_state'];
		$to_zip=$_POST['to_zip'];
		$to_zone=$_POST['to_zone'];
		$pallets=$_POST['pallets'];
		$invoice=$_POST['invoice'];
		$driver_rate=$_POST['driver_rate'];
		
		// 3/6 - Duncan, removed unnecessary quotes around numbers.
		$fields='CLIENT_ID, USER_TYPE, ORIGIN_NAME, FROM_CITY,
			FROM_STATE, FROM_ZIP, FROM_ZONE, CONSIGNEE_NAME,
			TO_CITY, TO_STATE, TO_ZIP, TO_ZONE,
			PALLET, INVOICE_COST, DRIVER_COST ';
		$values=$_GET['CODE'].", '$user_type', '$origin_name', '$from_city',
			'$from_state', '$from_zip', '$from_zone', '$consignee_name',
			'$to_city', '$to_state', '$to_zip', '$to_zone',
			$pallets, $invoice, $driver_rate";
		
		$hand_sql_obj->add_row( $fields, $values );
	}
	
	##------ update handling charges--------##
	if(isset($_POST['btn_edit_handle'])) {
		$ed_shipper_name=$_POST['ed_shipper_name'];
		$ed_handle_id=$_POST['ed_handle_id'];
		$ed_from_city=$_POST['ed_from_city'];
		$ed_from_state=$_POST['ed_from_state'];
		$ed_from_zip=$_POST['ed_from_zip'];
		$ed_from_zone=$_POST['ed_from_zone'];
		$ed_Consignee_name=$_POST['ed_Consignee_name'];
		$ed_to_city=$_POST['ed_to_city'];
		$ed_to_state=$_POST['ed_to_state'];
		$ed_to_zip=$_POST['ed_to_zip'];
		$ed_to_zone=$_POST['ed_to_zone'];
		$ed_pallet=$_POST['ed_pallet'];
		$ed_invoice=$_POST['ed_invoice'];
		$ed_driverr_rate=$_POST['ed_driverr_rate'];
		
		$hand_sql_obj->update($ed_handle_id,
			array('ORIGIN_NAME'=>$ed_shipper_name, 'FROM_CITY'=>$ed_from_city,
			'FROM_STATE'=>$ed_from_state, 'FROM_ZIP'=>$ed_from_zip,
			'FROM_ZONE'=>$ed_from_zone, 'CONSIGNEE_NAME'=>$ed_Consignee_name,
			'TO_CITY'=>$ed_to_city, 'TO_STATE'=>$ed_to_state,
			'TO_ZIP'=>$ed_to_zip, 'TO_ZONE'=>$ed_to_zone,
			'PALLET'=>$ed_pallet, 'INVOICE_COST'=>$ed_invoice,
			'DRIVER_COST'=>$ed_driverr_rate));
	}
	
	##-------- insert freight rates-----##
   if(isset($_POST['save_freight_rate'])) {
		// echo '<pre/>';print_r($_POST);
		//$freight_miles=$_POST['freight_miles'];
	  	//  $pu_loc=$_POST['pu_loc'];
		$shipper_name=$freight_obj->real_escape_string($_POST['shipper_name']);
		$origin_city=$_POST['origin_city'];
		$origin_state=$_POST['origin_state'];
		$origin_zip=$_POST['origin_zip'];
		$CONS_NAME=$freight_obj->real_escape_string($_POST['CONS_NAME']);
		$dest_city=$_POST['dest_city'];
		$dest_state=$_POST['dest_state'];
		$dest_zip=$_POST['dest_zip'];
		$line_haul=empty($_POST['line_haul']) ? 0 : $_POST['line_haul'];
		$min_line_haul=empty($_POST['min_line_haul']) ? 0 : $_POST['min_line_haul'];
		$fsc_rate=empty($_POST['fsc_rate']) ? '' : $_POST['fsc_rate'];
		$fsc_milleage=empty($_POST['fsc_milleage']) ? 0 : $_POST['fsc_milleage'];
		$weekend_hol=empty($_POST['weekend_hol']) ? 0 : $_POST['weekend_hol'];
		$enroute=empty($_POST['enroute']) ? 0 : $_POST['enroute'];
		$detention=empty($_POST['detention']) ? 0 : $_POST['detention'];
		$per_miles=empty($_POST['per_miles']) ? 0 : $_POST['per_miles'];
		$amount=empty($_POST['amount']) ? 0 : $_POST['amount'];
	  
		// 3/6 - Duncan, removed unnecessary quotes around numbers.
		$freight_fields='CLIENT_ID, USER_TYPE, SHIPPER_NAME, ORIGIN_CITY,
			ORIGIN_STATE, ORIGIN_ZIP, CONSIGNEE_NAME, DESTINATION_CITY,
			DESTINAION_STATE, DESTINATION_ZIP, LINE_HAUL, MIN_LINE_HAUL,
			FSC, MILEAGE, WEEKEND_HOLIDAY, ENROUTE_STOP_OFFS,
			DETENTION, PER_MILE, AMOUNT';
		$freight_value=$_GET['CODE'].", '$user_type', '$shipper_name', '$origin_city',
			'$origin_state', '$origin_zip', '$CONS_NAME', '$dest_city',
			'$dest_state', '$dest_zip', $line_haul, $min_line_haul,
			'$fsc_rate', $fsc_milleage, $weekend_hol, $enroute,
			$detention, $per_miles, $amount";
	  
		$freight_obj->add_row( $freight_fields,  $freight_value);
	  
   }
   
   ##-------- update freight rate------##
   if(isset($_POST['btn_edit_freight'])) {
	   $ed_shipper=$_POST['ed_shipper'];
	   $ed_origin_city=$_POST['ed_origin_city'];
       $ed_origin_state=$_POST['ed_origin_state'];
       $ed_origin_zip=$_POST['ed_origin_zip'];
	   $ed_Consignee=$_POST['ed_Consignee'];
	   $ed_dest_city=$_POST['ed_dest_city'];
	   $ed_dest_state=$_POST['ed_dest_state'];
       $ed_dest_zip=$_POST['ed_dest_zip'];
       $ed_line_haul=$_POST['ed_line_haul'];
	   $ed_min_line_haul=$_POST['ed_min_line_haul'];
	   $ed_fsc_rate=$_POST['ed_fsc_rate'];
	   $ed_fsc_milleage=$_POST['ed_fsc_milleage'];
	   $ed_weekend_hol=$_POST['ed_weekend_hol'];
	   $ed_enroute=$_POST['ed_enroute'];
	   $ed_detention=$_POST['ed_detention'];
	   $ed_per_miles=$_POST['ed_per_miles'];
	   $ed_amount=$_POST['ed_amount'];
	   $ed_frt_id=$_POST['ed_frt_id'];
	   
	   $freight_obj->update($ed_frt_id,
	   		array('SHIPPER_NAME'=>$freight_obj->real_escape_string($ed_shipper), 'ORIGIN_CITY'=>$ed_origin_city,
	   		'ORIGIN_STATE'=> $ed_origin_state, 'ORIGIN_ZIP'=> $ed_origin_zip,
	   		'CONSIGNEE_NAME'=>$freight_obj->real_escape_string($ed_Consignee), 'DESTINATION_CITY'=>$ed_dest_city,
	   		'DESTINAION_STATE'=>$ed_dest_state, 'DESTINATION_ZIP'=>$ed_dest_zip,
	   		'LINE_HAUL'=>$ed_line_haul, 'MIN_LINE_HAUL'=>$ed_min_line_haul,
	   		'FSC'=>$ed_fsc_rate, 'MILEAGE'=>$ed_fsc_milleage,
	   		'WEEKEND_HOLIDAY'=>$ed_weekend_hol, 'ENROUTE_STOP_OFFS'=>$ed_enroute,
	   		'DETENTION'=>$ed_detention, 'PER_MILE'=>$ed_per_miles,
	   		'AMOUNT'=>$ed_amount));
   }
   
	##----- save pallet rates ----##
	if(isset($_POST['save_pallet_rate'])) {
		$customer_name=$_POST['customer_name'];
		$customer_city=$_POST['customer_city'];
		$customer_state=$_POST['customer_state'];
		$customer_zip=$_POST['customer_zip'];
		$min_charge=$_POST['min_charge'];
		$max_charge=$_POST['max_charge'];
		
		// 3/6 - Duncan, removed unnecessary quotes around numbers.
		$pallet_field='CLIENT_ID, USER_TYPE, CUST_NAME, CUST_CITY,
			CUST_STATE, CUST_ZIP, MIN_CHARGE, MAX_CHARGE';
		$pallet_value=$_GET['CODE'].", '$user_type', '".$pallet_obj->real_escape_string($customer_name)."', '$customer_city',
		'$customer_state', '$customer_zip', $min_charge, $max_charge";
		
		$pallet_obj->add_row($pallet_field, $pallet_value);
	}
   
	##------ save detention rates ----##
	if(isset($_POST['save_detention_rate'])) {
		$det_customer_name=$_POST['det_customer_name'];
		/*$det_customer_city=$_POST['det_customer_city'];
		$det_customer_state=$_POST['det_customer_state'];
		$det_customer_zip=$_POST['det_customer_zip'];*/
		$det_min_charge=$_POST['det_min_charge'];
		$det_max_charge=$_POST['det_max_charge'];
			   
		// 3/6 - Duncan, removed unnecessary quotes around numbers.
		$detention_field='CLIENT_ID, USER_TYPE, DET_CUST_NAME, DET_MIN_CHARGE, DET_MAX_CHARGE';
		$detention_val="$client_id, '$user_type', '".$det_obj->real_escape_string($det_customer_name)."', $det_min_charge, $det_max_charge";
		
		$det_obj->add_row($detention_field, $detention_val);
	
	}
   
	##-------update detention rates-------------##
	if(isset($_POST['update_detention_rate'])) {
		$det_customer_name=$_POST['det_customer_name'];
		/*$det_customer_city=$_POST['det_customer_city'];
		$det_customer_state=$_POST['det_customer_state'];
		$det_customer_zip=$_POST['det_customer_zip'];*/
		$det_min_charge=$_POST['det_min_charge'];
		$det_max_charge=$_POST['det_max_charge'];
		$det_id=$_POST['det_id'];
		  
		$det_obj->update($det_id,
			array('DET_MIN_CHARGE'=>$det_min_charge, 'DET_MAX_CHARGE'=>$det_max_charge));
	}

	$res['act']='Add';
	$rate_count=0;
	$client_rate_detail=$client_rate_obj->database->get_multiple_rows(
		"SELECT COUNT(*)  AS CNT, CLIENT_RATE_ID
		FROM ".CLIENT_RATE_MASTER."
		WHERE CLIENT_ID=".$client_id."
		AND USER_TYPE='".$user_type."'");
		
	//print_r($client_rate_detail); exit;
	if($client_rate_detail[0]['CNT']!=0) {
		$res['act']='Update';
		$rate_count=$client_rate_detail[0]['CNT'];
	}
	
	$hazmat=$temp=0;
	if(isset($_POST['add_rate'])) {
		$client_name=$_POST['CLIENT_NAME'];
		$rate_per_mile=empty($_POST['RATE_PER_MILE']) ? 0 : $_POST['RATE_PER_MILE'];
		$so_rate=empty($_POST['SO_RATE']) ? 0 : $_POST['SO_RATE'];
		//$fuel_cost=$_POST['FUEL_COST'];
		$vendor_code=$_POST['VENDOR_CODE'];
		if(isset($_POST['HAZMAT']))
		{$hazmat=$_POST['HAZMAT'];}
		if(isset($_POST['TEMP_CONTROLL']))
		{$temp=$_POST['TEMP_CONTROLL'];}
		if($client_rate_detail[0]['CNT']==0) {
		// 3/6 - Duncan, removed unnecessary quotes around numbers.
			$client_rate_obj->add_row("CLIENT_ID, RATE_PER_MILE, SO_RATE, HAZMAT,
				TEMP_CONTROLLED, USER_TYPE",
			"$client_id, $rate_per_mile, $so_rate, '$hazmat', '$temp', '$user_type'");
			$rate_count=1;
			$res['act']='Update';
		} else {
			$client_rate_obj->update($client_rate_detail[0]['CLIENT_RATE_ID'],
				array('RATE_PER_MILE'=>$rate_per_mile, 'SO_RATE'=>$so_rate,
				'HAZMAT'=>$hazmat, 'TEMP_CONTROLLED'=>$temp));
		}
		
	}

	$res['rpm']=$res['srate']=$res['fuel']='';
	$res['temp']=$res['hazmat']='0';

	if($rate_count!=0) {
		$client_rates=$client_rate_obj->database->get_multiple_rows(
			"SELECT * FROM ".CLIENT_RATE_MASTER."
			WHERE CLIENT_ID=".$client_id."
			AND USER_TYPE='".$user_type."'");
			
		//print_r($client_rates);exit;
		$res['rpm']=$client_rates[0]['RATE_PER_MILE'];
		$res['srate']=$client_rates[0]['SO_RATE'];
		//$res['fuel']=$client_rates[0]['FUEL_COST'];
		$res['hazmat']=$client_rates[0]['HAZMAT'];
		$res['temp']=$client_rates[0]['TEMP_CONTROLLED'];
	}

	if($user_type=='client') {
		$res['client_name'] =$client_master->database->get_one_row(
			"SELECT CLIENT_NAME AS CNAME FROM ".CLIENT_TABLE."
			WHERE CLIENT_CODE=".$client_id);
	} else {
		$res['client_name'] =$carrier_obj->database->get_one_row(
			"SELECT CARRIER_NAME AS CNAME FROM ".CARRIER_TABLE."
			WHERE CARRIER_CODE=".$client_id);
	}

	$res['client_cat'] =$client_cat_master->database->get_multiple_rows(
		"SELECT * FROM ".CLIENT_CAT."
		WHERE CAT_STATUS='1'");
	
	$han_sort='';
	if(isset($_GET['SORT_BY']) && isset($_GET['COL']) && $_GET['COL']!="" &&
		($_GET['SORT_BY']=='han_asc' || $_GET['SORT_BY']=='han_desc')) {
    	$order='';
    	if($_GET['SORT_BY']=='han_asc') {
			$order='ASC';
    	} else {
    		$order='DESC';
    	}
    	$han_sort='ORDER BY '.$_GET['COL'].' '.$order;
    }

	$res['client_handling']	=$hand_sql_obj->database->get_multiple_rows(
		"SELECT * FROM ".HANDLING_TABLE."
		WHERE  CLIENT_ID='$client_id'
		AND USER_TYPE='$user_type' $han_sort");

	$fr_sort='';
	if(isset($_GET['SORT_BY']) && isset($_GET['COL']) && $_GET['COL']!="" &&
		($_GET['SORT_BY']=='fr_asc' || $_GET['SORT_BY']=='fr_desc')) {
    	$order='';
    	if($_GET['SORT_BY']=='fr_asc') {
			$order='ASC';
    	} else {
    		$order='DESC';
    	}
    	$fr_sort='ORDER BY '.$_GET['COL'].' '.$order;
    }

	$res['frieght_rate'] =$hand_sql_obj->database->get_multiple_rows(
		"SELECT * FROM ".FRIGHT_RATE_TABLE."
		WHERE  CLIENT_ID='$client_id'
		AND USER_TYPE='$user_type' $fr_sort");

	$res['client_rate'] =$client_cat_rate->database->get_multiple_rows(
		"SELECT * FROM ".CLIENT_CAT_RATE."
		JOIN ".CLIENT_ASSIGN_RATE."
		ON ".CLIENT_ASSIGN_RATE.".RATE_ID=".CLIENT_CAT_RATE.".CLIENT_RATE_ID
		JOIN  ".CLIENT_CAT."
		ON ".CLIENT_CAT.".CLIENT_CAT=".CLIENT_CAT_RATE.".CATEGORY
		WHERE ".CLIENT_ASSIGN_RATE.".CLIENT_ID=".$client_id."
		AND USER_TYPE='$user_type'");
		
	$res['fsc_schedule'] =$fsc_obj->database->get_multiple_rows(
		"SELECT * FROM ".FSC_SCHEDULE."
		GROUP BY FSC_UNIQUE_ID");

	$res['fsc_client_schedule'] =$client_fsc->database->get_multiple_rows(
		"SELECT * FROM ".CLIENT_FSC."
		JOIN ".FSC_SCHEDULE."
		ON ".FSC_SCHEDULE.".FSC_UNIQUE_ID=".CLIENT_FSC.".FSC_ID
		WHERE  ".CLIENT_FSC.".CLIENT_ID=".$client_id."
		AND USER_TYPE='$user_type'
		GROUP BY FSC_UNIQUE_ID");

	$pal_sort='';
    if(isset($_GET['SORT_BY']) && isset($_GET['COL']) && $_GET['COL']!="" &&
    	($_GET['SORT_BY']=='pal_asc' || $_GET['SORT_BY']=='pal_desc')) {
    	$order='';
    	if($_GET['SORT_BY']=='pal_asc') {
			$order='ASC';
    	} else {
    		$order='DESC';
    	}
		$pal_sort='ORDER BY '.$_GET['COL'].' '.$order;
    }

	$res['pallet_rate'] =$pallet_obj->database->get_multiple_rows(
		"SELECT * FROM ".PALLET_MASTER."
		WHERE  CLIENT_ID=$client_id
		AND USER_TYPE='$user_type' $pal_sort");
	
	$res['detention_rate'] =$det_obj->database->get_multiple_rows(
		"SELECT * FROM ".DETENTION_MASTER."
		WHERE CLIENT_ID='$client_id'
		AND USER_TYPE='$user_type'
		GROUP BY CLIENT_ID");

	##------- by defualt fetch all rates related to categories-------##
	$res['client_rate_cat']=$client_cat_rate->database->get_multiple_rows(
		"SELECT * FROM ".CLIENT_CAT_RATE."
		JOIN ".CLIENT_CAT."
		ON ".CLIENT_CAT.".CLIENT_CAT=".CLIENT_CAT_RATE.".CATEGORY
		WHERE ISDELETED=0");
	
	
	//$res['category']='';
	
	$res['CODE']=$_GET['CODE'];
	$res['USER_TYPE']=$user_type;
	$res_obj->render_client_rate_screen($res); //resadd_pallet_rate
} else {
	echo '<script>window.location.href="exp_listclient.php"</script>';
}
?>
</div>
<?php
require_once( "include/footer_inc.php" );
?>
<script type="text/javascript" language="javascript" >

function set_pallet_value(val)
{
	document.getElementById('PALLETS_ID').value=val;
}

function set_detention_value(val)
{
	document.getElementById('DET_ID').value=val;
}

function set_un_detention_value(val)
{
	document.getElementById('MAIN_DET_ID').value=val;
}

function fetch_detention_value(detention_id)
{
	
	$('#detention_loader').show();
	$.ajax({
		url:'exp_save_rates.php?action=fetchdetention&det_id='+detention_id,
		type:'POST',
		success:function(res)
		{
			document.getElementById('detention_rate_'+detention_id).innerHTML=res;
			$('#detention_loader').hide();
		}
	});
}

function fetch_unloaded_detention_value(detention_id)
{
	$('#detention_loader').show();
	$.ajax({
		url:'exp_save_rates.php?action=fetchunloadeddetention&det_id='+detention_id,
		type:'POST',
		success:function(res)
		{
			document.getElementById('unloaded_detention_rate_'+detention_id).innerHTML=res;
			$('#detention_loader').hide();
		}
	});
}


function check()
{
	var pal_from=document.getElementById('PALLETS_FROM');
	var pal_to=document.getElementById('PALLETS_TO');
	var pal_rate=document.getElementById('PALLETS_RATE');
	var pal_id=document.getElementById('PALLETS_ID');
	//alert(pal_from.value);
	if(parseFloat(pal_from.value)>parseFloat(pal_to.value))
	{
		alert("From value can not be greater than to value.");
		return false;
	}
	else if(pal_rate.value=="")
	{
		alert("Please enter pallet rates");
		return false;
	}
	else if(isNaN(pal_rate.value))
	{
		alert("Please enter pallet rates in numbers.");
		return false;
	}
	else if(pal_rate.value!="")
	{
		$('#loader').show();
		
		$.ajax({
			url:'exp_save_rates.php?action=checkduplicatepallet&pal_from='+pal_from.value+'&pal_to='+pal_to.value+'&pal_id='+pal_id.value,
			type:'POST',
			success:function(res)
			{
				$('#loader').hide();
				if(res==0)
				{
					//window.location.reload();
					
				document.forms["add_pallet_rate"].submit();
				//	document.getElementById("add_pallet_rate").submit();
					return true;
				}
				else
				{
					alert("You have already added for this range.");
					return false;
				}
				return false;
			}
		});
		return false;
	}
}

function check_edit_pall(pal_rate_id)
{
	
	var pal_from=document.getElementById('ED_PALLETS_FROM_'+pal_rate_id);
	var pal_to=document.getElementById('ED_PALLETS_TO_'+pal_rate_id);
	var pal_rate=document.getElementById('ED_PALLETS_RATE_'+pal_rate_id);
	var pal_id=document.getElementById('ED_PALL_ID_'+pal_rate_id);
	//alert(pal_from.value);
	if(parseFloat(pal_from.value)>parseFloat(pal_to.value))
	{
		alert("From value can not be greater than to value.");
		return false;
	}
	else if(pal_rate.value=="")
	{
		alert("Please enter pallet rates");
		return false;
	}
	else if(isNaN(pal_rate.value))
	{
		alert("Please enter pallet rates in numbers.");
		return false;
	}
	else if(pal_rate.value!="")
	{
		$('#loader').show();
		
		$.ajax({
			url:'exp_save_rates.php?action=checkeditduplicatepallet&pal_from='+pal_from.value+'&pal_to='+pal_to.value+'&pal_id='+pal_id.value+'&pal_rate_id='+pal_rate_id,
			type:'POST',
			success:function(res)
			{
				$('#loader').hide();
				if(res==0)
				{
					//window.location.reload();
					
				document.forms["update_pall_rate"+pal_rate_id].submit();
				//	document.getElementById("add_pallet_rate").submit();
					return true;
				}
				else
				{
					alert("You have already added for this range.");
					return false;
				}
				return false;
			}
		});
		return false;
	}
}
function delete_detention_charge(det_id,code,usertype)
{
	if(confirm("Do you really want to delete detention charge?"))
	{
		$('#detention_loader').show();
		if(det_id!='')
		{
			$.ajax({
				url:'exp_save_rates.php?action=deletedetentions&det_ids='+det_id,
				type:'POST',
				success:function(res)
				{
					window.location.href='exp_addclientrate.php?TYPE='+usertype+'&CODE='+code;
				}
				});	
		}
	}
	else
	{
		return false;
	}
}
function check_det()
{
	var det_id=document.getElementById('DET_ID');
	var det_hr=document.getElementById('DET_HOUR');
	var det_hr_to=document.getElementById('DET_HOUR_TO');
	var det_rate=document.getElementById('DET_RATE');
	
	if(parseFloat(det_hr.value)>parseFloat(det_hr_to.value))
	{
		alert("From value can not be greater than to value.");
		return false;
	}
	else if(det_rate.value=="")
	{
		alert("Please enter detention rate.");
		return false;
	}
	else if(isNaN(det_rate.value))
	{
		alert("Please enter detention rate in numbers.");
		return false;
	}
	else if(det_rate.value!="")
	{
		$('#dloader').show();
		$.ajax({
			url:'exp_save_rates.php?action=checkdetention&detention_id='+det_id.value+'&det_hr='+det_hr.value+'&det_hr_to='+det_hr_to.value,
			type:'POST',
			success:function(res)
			{
				$('#dloader').hide();
				if(res==0)
				{
					document.forms["add_detention_rate"].submit();
				}
				else
				{
					alert("You have already added detention rates for this range.");
					return false;
				}
			}
		});
		return false;
	}
}

function check_unloaded_det()
{
	var det_id=document.getElementById('MAIN_DET_ID');
	var un_det_hr=document.getElementById('UN_DET_HOUR');
	var un_det_hr_to=document.getElementById('UN_DET_HOUR_TO');
	var un_det_rate=document.getElementById('UN_DET_RATE');
	
	if(parseFloat(un_det_hr.value)>parseFloat(un_det_hr_to.value))
	{
		alert("From value can not be greater than to value.");
		return false;
	}
	else if(un_det_rate.value=="")
	{
		alert("Please enter unloaded detention rate.");
		return false;
	}
	else if(isNaN(un_det_rate.value))
	{
		alert("Please enter unloaded detention rate in numbers.");
		return false;
	}
	else if(un_det_rate.value!="")
	{
		$('#un_dloader').show();
		$.ajax({
			url:'exp_save_rates.php?action=checkunloadeddetention&detent_id='+det_id.value+'&un_det_hr='+un_det_hr.value+'&un_det_hr_to='+un_det_hr_to.value,
			type:'POST',
			success:function(res)
			{
				$('#un_dloader').hide();
				if(res==0)
				{
					document.forms["add_un_detention_rate"].submit();
				}
				else
				{
					alert("You have already added detention rates for this range.");
					return false;
				}
			}
		});
		return false;
	}
}


function check_edit_det(hr_det_id)
{
	var det_id=document.getElementById('ED_DET_ID');
	var det_hr=document.getElementById('ED_DET_HOUR_'+hr_det_id);
	var det_hr_to=document.getElementById('ED_DET_HOUR_TO_'+hr_det_id);
	var det_rate=document.getElementById('ED_DET_RATE_'+hr_det_id);
	//alert(det_hr.value);alert(det_hr_to.value);alert(hr_det_id);
	if(parseFloat(det_hr.value)>parseFloat(det_hr_to.value))
	{
		alert("From value can not be greater than to value.");
		return false;
	}
	else if(det_rate.value=="")
	{
		alert("Please enter detention rate.");
		return false;
	}
	else if(det_rate.value!="")
	{
		$('#dloader').show();
		$.ajax({
			url:'exp_save_rates.php?action=checkeditdetention&detention_id='+det_id.value+'&det_hr='+det_hr.value+'&det_hr_to='+det_hr_to.value+'&hr_det_id='+hr_det_id,
			type:'POST',
			success:function(res)
			{
				$('#dloader').hide();
				if(res==0)
				{
					document.forms["update_det_rate"+hr_det_id].submit();
				}
				else
				{
					alert("You have already added detention rates for this range.");
					return false;
				}
			}
		});
		return false;
	}
}


function delete_client_pallet_charge(pallet_id,code,user_type)
{
	if(confirm("Do you really want to delete pallet rate?"))
	{
		$('#pallet_loader').show();
		if(pallet_id!='')
		{
			$.ajax({
				url:'exp_save_rates.php?action=deletepallettrates&pallet_ids='+pallet_id,
				type:'POST',
				success:function(res)
				{
					window.location.href='exp_addclientrate.php?TYPE='+user_type+'&CODE='+code;
				}
				});	
		}
	}
	else
	{
		return false;
	}
}

function delete_single_detention(det_id)
{
	if(confirm("Do you really want to delete this rate?"))
	{
		$('#detention_loader').show();
		if(det_id!='')
		{
			$.ajax({
				url:'exp_save_rates.php?action=deletesingledetentions&single_det_id='+det_id,
				type:'POST',
				success:function(res)
				{
					//window.location.href='exp_addclientrate.php?TYPE='+usertype+'&CODE='+code;
					//window.location.reload(true);
					window.location.href=window.location.href;
				}
				});	
		}
	}
	else
	{
		return false;
	}
}

function delete_single_unload_detention(det_id)
{
	if(confirm("Do you really want to delete this rate?"))
	{
		$('#detention_loader').show();
		if(det_id!='')
		{
			$.ajax({
				url:'exp_save_rates.php?action=deleteunloadeddetentions&det_id='+det_id,
				type:'POST',
				success:function(res)
				{
					window.location.href=window.location.href;
				}
				});	
		}
	}
	else
	{
		return false;
	}
}


function delete_single_pallet(pallet_id)
{
	
	if(confirm("Do you really want to delete this pallet rate?"))
	{
		$('#pallet_loader').show();
		if(pallet_id!='')
		{
			$.ajax({
				url:'exp_save_rates.php?action=deletesinglepallet&single_pal_id='+pallet_id,
				type:'POST',
				success:function(res)
				{
					//window.location.href='exp_addclientrate.php?TYPE='+usertype+'&CODE='+code;
					//window.location.reload(true);
					window.location.href=window.location.href;
				}
				});	
		}
	}
	else
	{
		return false;
	}
}
function fetch_pallet_value(pallet_id)
{
	$('#pallet_loader').show();
	$.ajax({
		url:'exp_save_rates.php?action=fetchpallets&pallet_id='+pallet_id,
		type:'POST',
		success:function(res)
		{
			document.getElementById('pallet_rate_'+pallet_id).innerHTML=res;
			$('#pallet_loader').hide();
		}
	});
}
function add_more_fun(val)
{
	$("#"+val).toggle();
}

function get_search_records(val)
{
	$('#reload_search_result').show();
	{
			$.ajax({
				url:'exp_save_rates.php?action=searchbycat&val='+val,
				type:'POST',
				success:function(res)
				{//return false;	
					//$('#add_manual_rates').load(location.href+' #add_manual_rates');
					document.getElementById("search_reload").innerHTML=res;
					$('#reload_search_result').hide();
				}
				});	
		}
}

function check_rates()
{
	var category=document.getElementsByName('rate_ids[]');
	var len=category.length;
	var flag=0;
	if(len>0)
	{
		for(var i=0;i<len;i++)
		{
			if(category[i].checked==true)
			{
				flag=1;
				break;
			}
		}
	}
	
	if(flag==0)
	{
		alert('Please select some rate to assign.');
		return false;
	}
	else
	{
		return true;
	}
}

function check_uploaded_file(file_name)
{
	var uploaded_file=file_name.value;
	var ext=uploaded_file.substring(uploaded_file.lastIndexOf('.')+1);
	
	if(uploaded_file=="")
	{
		alert("Please upload a file.");
		return false;
	}
	else if(uploaded_file!="" && ext!="csv" && ext!="CSV")
	{
		alert("Please upload  only  CSV file.");
		return false;
	}
	else
	{
		return true;
	}
}

function delete_client_assigned_rate(rate_id,client_id,user_type)
{
	if(confirm('Do you really want to delete the Assigned Rate?'))
	{
		$('#reload_client_result').show();
		if(rate_id!='')
		{
			$.ajax({
				url:'exp_save_rates.php?action=deleteclientrates&rate_id='+rate_id,
				type:'POST',
				success:function(res)
				{
					window.location.href='exp_addclientrate.php?TYPE='+user_type+'&CODE='+client_id;
				}
				});	
		}
	}
	else
	{
		return false;	
	}
}

function check_fsc()
{
	var fsc_list=document.getElementsByName('fsc_ids[]');
	var len=fsc_list.length;
	//alert(len);
	var flag=0;
	if(len>0)
	{
		for(var i=0;i<len;i++)
		{
			if(fsc_list[i].checked==true)
			{
				flag=1;
				break;
			}
		}
	}
	
	if(flag==0)
	{
		alert('Please select atleast 1 FSC Schedule to assign.');
		return false;
	}
	else
	{
		return true;
	}
}

function delete_client_assigned_schedule(assigned_id,client_id,user_type)
{
	if(confirm('Do you really want to delete the Assigned FSC Schedule?'))
	{
		$('#reload_fsc_result').show();
		if(assigned_id!='')
		{
			$.ajax({
				url:'exp_save_rates.php?action=deleteclientfsc&assigned_id='+assigned_id,
				type:'POST',
				success:function(res)
				{
					window.location.href='exp_addclientrate.php?TYPE='+user_type+'&CODE='+client_id;
				}
				});	
		}
	}
	else
	{
		return false;	
	}
}

function chk_add_rates()
{
	var rpm=document.getElementById('RATE_PER_MILE');
	var so_rate=document.getElementById('SO_RATE');
	var fuel=document.getElementById('FUEL_COST');
	var vcode=document.getElementById('VENDOR_CODE');
	
	/*if(rpm.value=="")
	{
		alert("Please enter rate per miles.");
		return false;
	}
	else*/ 
	if(rpm.value!="" && isNaN(rpm.value))
	{
		alert("Rate per mile should be numeric.");
		return false;
	}
	/*else if(so_rate.value=="")
	{
		alert("Please enter S/O rate.");
		return false;
	}*/
	else if(so_rate.value!="" && isNaN(so_rate.value))
	{
		alert("S/O rate should be numeric.");
		return false;
	}
	/*else if(fuel.value=="")
	{
		alert("Please enter fuel cost");
		return false;
	}
	else if(isNaN(fuel.value))
	{
		alert("Fuel cost should be numeric.");
		return false;	
	}*/
	/*else if(vcode.value=="")
	{
		alert("Please enter vendor code.");
		return false;
	}*/
	else
	{ return true;}
	
}

function delete_client_handling_charge(handling_id,code,user_type)
{
	if(confirm("Do you really want to delete handling charge?"))
	{
		$('#reload_handling_result').show();
		$.ajax({
			url:'exp_save_rates.php?action=deleteclienthandling&handling_id='+handling_id,
			type:'POST',
			success:function(res)
			{
				window.location.href='exp_addclientrate.php?TYPE='+user_type+'&CODE='+code;
			}
		});
	}
	else
	{
		return false;
	}
}

function delete_client_frieght_rate(frieght_id,code,user_type)
{
	if(confirm("Do you really want to delete frieght rate?"))
	{
		$('#reload_freight_result').show();
		$.ajax({
			url:'exp_save_rates.php?action=deleteclientfrieghtrate&frieght_id='+frieght_id,
			type:'POST',
			success:function(res)
			{
				
				window.location.href='exp_addclientrate.php?TYPE='+user_type+'&CODE='+code;
			}
		});
	}
	else
	{
		return false;
	}
}

function show_hide_div(div_id)
{
	$('#'+div_id).slideToggle('slow');
}

function chk_handling_charge()
{
	var origin_name=document.getElementById('origin_name');
	var from_city=document.getElementById('from_city');
	var from_state=document.getElementById('from_state');
	var from_zip=document.getElementById('from_zip');
	var from_zone=document.getElementById('from_zone');
	var consignee_name=document.getElementById('consignee_name');
	var to_city=document.getElementById('to_city');
	var to_state=document.getElementById('to_state');
	var to_zip=document.getElementById('to_zip');
	var to_zone=document.getElementById('to_zone');
	var pallets=document.getElementById('pallets');
	var invoice=document.getElementById('invoice');
	var driver_rate=document.getElementById('driver_rate');
	
	if(origin_name.value=="")
	{
		alert("Please enter shipper name.");
		return false;
	}
	else if(from_city.value=="")
	{
		alert("Please enter shipper city.");
		return false;
	}
	else if(from_state.value=="")
	{
		alert("Please enter shipper state.");
		return false;
	}
	else if(from_zip.value=="")
	{
		alert("Please enter shipper zipcode.");
		return false;
	}
	else if(from_zone.value=="")
	{
		alert("Please enter from zone.");
		return false;
	}
	else if(consignee_name.value=="")
	{
		alert("Please enter consignee name.");
		return false;
	}
	else if(to_city.value=="")
	{
		alert("Please enter consignee city.");
		return false;
	}
	else if(to_state.value=="")
	{
		alert("Please enter consignee state.");
		return false;
	}
	else if(to_zip.value=="")
	{
		alert("Please enter consignee zipcode.");
		return false;
	}
	else if(to_zone.value=="")
	{
		alert("Please enter to zone.");
		return false;
	}
	else if(pallets.value=="")
	{
		alert("Please enter number of pallets.");
		return false;
	}
	else if(isNaN(pallets.value))
	{
		alert("Please enter only number.");
		return false;
	}
	else if(invoice.value=="")
	{
		alert("Please enter invoice rate.");
		return false;
	}
	else if(isNaN(invoice.value))
	{
		alert("Please enter only number.");
		return false;
	}
	else if(driver_rate.value=="")
	{
		alert("Please enter driver rate.");
		return false;
	}
	else if(isNaN(driver_rate.value))
	{
		alert("Please enter only number.");
		return false;
	}
	else
	{
		return true;
	}
	
	
}

function chk_freight_rates()
{
	var freight_miles=document.getElementById('freight_miles');
	//var pu_loc=document.getElementById('pu_loc');
	var origin_city=document.getElementById('origin_city');
	var origin_state=document.getElementById('origin_state');
	var origin_zip=document.getElementById('origin_zip');
	var dest_city=document.getElementById('dest_city');
	var dest_state=document.getElementById('dest_state');
	var dest_zip=document.getElementById('dest_zip');
	var line_haul=document.getElementById('line_haul');
	var min_line_haul=document.getElementById('min_line_haul');
	var fsc_rate=document.getElementById('fsc_rate');
	var fsc_milleage=document.getElementById('fsc_milleage');
	var weekend_hol=document.getElementById('weekend_hol');
	var enroute=document.getElementById('enroute');
	var detention=document.getElementById('detention');
	var shipper_name=document.getElementById('shipper_name');
	var CONS_NAME=document.getElementById('CONS_NAME');
	var per_miles=document.getElementById('per_miles');
	var amount=document.getElementById('amount');
	
	/*if(shipper_name.value=="")
	{
		alert("Please enter shipper name.");
		return false;
	}
	else*/  if(origin_city.value=="")
	{
		alert("Please enter shipper city.");
		return false;
	}
	else  if(origin_state.value=="")
	{
		alert("Please enter shipper state.");
		return false;
	}
	else  if(origin_zip.value=="")
	{
		alert("Please enter shipper zipcode.");
		return false;
	}
	/*else  if(CONS_NAME.value=="")
	{
		alert("Please enter consignee name.");
		return false;
	}*/
	else  if(dest_city.value=="")
	{
		alert("Please enter consignee city.");
		return false;
	}
	else  if(dest_state.value=="")
	{
		alert("Please enter consignee state.");
		return false;
	}
	else  if(dest_zip.value=="")
	{
		alert("Please enter consignee zipcode.");
		return false;
	}
	/*else  if(line_haul.value=="")
	{
		alert("Please enter line haul rate.");
		return false;
	}*/
	else  if(line_haul.value!="" && isNaN(line_haul.value))
	{
		alert("Please enter line haul rate in numbers.");
		return false;
	}
	/*else  if(min_line_haul.value=="")
	{
		alert("Please enter minimum line haul rate.");
		return false;
	}*/
	else  if(min_line_haul.value!="" && isNaN(min_line_haul.value))
	{
		alert("Please enter minimum line haul rate in numbers.");
		return false;
	}
	/*else  if(fsc_rate.value=="")
	{
		alert("Please enter fsc.");
		return false;
	}
	else  if(fsc_milleage.value=="")
	{
		alert("Please enter milleage.");
		return false;
	}*/
		else  if(fsc_milleage.value!='' && isNaN(fsc_milleage.value))
	{
		alert('Please enter milleage in numbers.');
		return false;
	}
	else  if(weekend_hol.value!="" && isNaN(weekend_hol.value))
	{
		alert("Please enter weekend holiday numbers.");
		return false;
	}
	else  if(enroute.value!="" && isNaN(enroute.value))
	{
		alert("Please enter enroute in numbers.");
		return false;
	}
	else  if(detention.value!="" && isNaN(detention.value))
	{
		alert("Please enter detention in numbers.");
		return false;
	}
	/*else if(freight_miles.value=="")
	{
		alert("Please enter total miles.");
		return false;
	}
	else if(isNaN(freight_miles.value))
	{
		alert("Please enter  only numbers.");
		return false;
	}
	else if(per_miles.value=="")
	{
		alert("Please enter per mile.");
		return false;
	}*/
	else if(per_miles.value!="" && isNaN(per_miles.value))
	{
		alert("Please enter  per miles in  numbers.");
		return false;
	}
	/*else if(amount.value=="")
	{
		alert("Please enter total amount.");
		return false;
	}*/
	else if(amount.value!="" && isNaN(amount.value))
	{
		alert("Please enter  amount in  numbers.");
		return false;
	}
	else
	{
		return true;
	}
	
	
}

function chk_detention_rate()
{
	var det_customer_name=document.getElementById('det_customer_name');
	/*var det_customer_city=document.getElementById('det_customer_city');
	var det_customer_state=document.getElementById('det_customer_state');
	var det_customer_zip=document.getElementById('det_customer_zip');*/
	var det_min_charge=document.getElementById('det_min_charge');
	var det_max_charge=document.getElementById('det_max_charge');
	var numbers=/^[0-9.]+$/; 
	
	if(det_customer_name.value=="")
	{
		alert("Please enter customer name.");
		return false;
	}
	/*else if(det_customer_city.value=="")
	{
		alert("Please enter customer city.");
		return false;
	}
	else if(det_customer_state.value=="")
	{
		alert("Please enter customer state.");
		return false;
	}
	else if(det_customer_zip.value=="")
	{
		alert("Please enter customer zipcode.");
		return false;
	}*/
	else if(det_min_charge.value=="")
	{
		alert("Please enter minimum charge.");
		return false;
	}
	else if(det_min_charge.value!="" && isNaN(det_min_charge.value))
	{
		alert("Please enter minimum charge in numbers.");
		return false;
	}
	else if(det_max_charge.value=="")
	{
		alert("Please enter maximum charge.");
		return false;
	}
	else if(det_max_charge.value!="" &&  !det_max_charge.value.match(numbers))
	{
		alert("Please enter minimum charge in numbers.");
		return false;
	}
	
}
function chk_pallet_charge()
{
	var customer_name=document.getElementById('customer_name');
	var customer_city=document.getElementById('customer_city');
	var customer_state=document.getElementById('customer_state');
	var customer_zip=document.getElementById('customer_zip');
	var min_charge=document.getElementById('min_charge');
	var max_charge=document.getElementById('max_charge');
	 var numbers = /^[0-9.]+$/;  
	/*var first_pallet_rate=document.getElementById('first_pallet_rate');
	var next_pallet_rate=document.getElementById('next_pallet_rate');
	var last_pallet_rate=document.getElementById('last_pallet_rate');
	var max_rate=document.getElementById('max_rate');*/
	
	if(customer_name.value=="")
	{
		alert("Please enter customer name.");
		return false;
	}
	else if(customer_city.value=="")
	{
		alert("Please enter customer city.");
		return false;
	}
	else if(customer_state.value=="")
	{
		alert("Please enter customer state.");
		return false;
	}
	else if(customer_zip.value=="")
	{
		alert("Please enter customer zipcode.");
		return false;
	}
	else if(min_charge.value=="")
	{
		alert("Please enter minimum charge.");
		return false;
	}
	else if(min_charge.value!="" && !min_charge.value.match(numbers))
	{
		alert("Please enter minimum charge in numbers.");
		return false;
	}
	else if(max_charge.value=="")
	{
		alert("Please enter maximum charge.");
		return false;
	}
	else if(max_charge.value!="" && !max_charge.value.match(numbers))
	{
		alert("Please enter maximum charge in numbers.");
		return false;
	}
	/*else if(first_pallet_rate.value=="")
	{
		alert("Please enter 1-4 pallet rates.");
		return false;
	}
	else if(first_pallet_rate.value!="" && isNaN(first_pallet_rate.value))
	{
		alert("Please enter pallet rates in numbers.");
		return false;
	}
	else if(next_pallet_rate.value!="" && isNaN(next_pallet_rate.value))
	{
		alert("Please enter pallet rates in numbers.");
		return false;
	}
	else if(last_pallet_rate.value!="" && isNaN(last_pallet_rate.value))
	{
		alert("Please enter pallet rates in numbers.");
		return false;
	}
	else if(max_rate.value=="")
	{
		alert("Please enter maximum rates.");
		return false;
	}
	else if(max_rate.value!="" && isNaN(max_rate.value))
	{
		alert("Please enter maximum rates in numbers.");
		return false;
	}*/
	else 
	{
		return true;
	}
	
}

function set_freight_id(f_id)
{ 
	document.getElementById('FREIGHT_CODE').value=f_id; 
}
function set_handle_id(h_id)
{ 
	document.getElementById('HANDLE_CODE').value=h_id; 
}

function calculate_frieght_amount()
{
	var fsc_milleage=document.getElementById('fsc_milleage').value;
	var per_miles=document.getElementById('per_miles').value;
	if(fsc_milleage!="" && per_miles!="" && !isNaN(fsc_milleage) && !isNaN(per_miles))
	{
		
		var amnt=parseFloat(fsc_milleage)*parseFloat(per_miles);
		document.getElementById('amount').value=amnt;
	}
	else
	{
		document.getElementById('amount').value='';
	}
}

function check_edit_unloaded_det(un_det_id)
{
	var det_id=document.getElementById('ED_UN_DET_ID');
	var det_hr=document.getElementById('ED_UN_DET_HOUR_'+un_det_id);
	var det_hr_to=document.getElementById('ED_UN_DET_HOUR_TO_'+un_det_id);
	var det_rate=document.getElementById('ED_UN_DET_RATE_'+un_det_id);
	//alert(det_hr.value);alert(det_hr_to.value);alert(un_det_id);
	if(parseFloat(det_hr.value)>parseFloat(det_hr_to.value))
	{
		alert("From value can not be greater than to value.");
		return false;
	}
	else if(det_rate.value=="")
	{
		alert("Please enter unloaded detention rate.");
		return false;
	}
	else if(isNaN(det_rate.value))
	{
		alert("Please enter unloaded detention rate in numbers.");
		return false;
	}
	else if(det_rate.value!="")
	{
		$('#dloader').show();
		$.ajax({
			url:'exp_save_rates.php?action=editunloaddetention&detention_id='+det_id.value+'&det_hr='+det_hr.value+'&det_hr_to='+det_hr_to.value+'&un_det_id='+un_det_id,
			type:'POST',
			success:function(res)
			{
				$('#dloader').hide();
				if(res==0)
				{
					document.forms["update_det_rate"+un_det_id].submit();
				}
				else
				{
					alert("You have already added detention rates for this range.");
					return false;
				}
			}
		});
		return false;
	}
}


//$(document).ready( function () {
		
			var SHIPPER_NAME_clients = new Bloodhound({
			  name: 'SHIPPER_NAME',
			  remote: {
				  url: 'exp_suggest_client.php?code=Vinegar&type=shipper&query=%QUERY',
				  wildcard: '%QUERY'
				  },
			  datumTokenizer: Bloodhound.tokenizers.obj.whitespace('CLIENT_NAME'),
			  queryTokenizer: Bloodhound.tokenizers.whitespace
			});
						
			SHIPPER_NAME_clients.initialize();

			$('#shipper_name,#origin_name').typeahead(null, {
			  name: 'SHIPPER_NAME',
			  minLength: 2,
			  limit: <?php echo $client_matches; ?>,
			  highlight: true,
			  display: 'CLIENT_NAME',
			  source: SHIPPER_NAME_clients,
			  templates: {
		      suggestion: Handlebars.compile(
			      '<p><strong>{{CLIENT_NAME}}</strong> – {{CONTACT_TYPE}}, {{CONTACT_NAME}}, {{PHONE_OFFICE}}</p>'
			    )
			  }
			});
	//		});
	
	var ZIP_CODE_zips = new Bloodhound({
			  name: 'ZIP_CODE',
			  remote: {
				  url: 'exp_suggest_zip.php?code=Balsamic&query=%QUERY',
				  wildcard: '%QUERY'
				  },
			  datumTokenizer: Bloodhound.tokenizers.obj.whitespace('ZipCode'),
			  queryTokenizer: Bloodhound.tokenizers.whitespace
			});
			
			ZIP_CODE_zips.initialize();

			$('#to_zone,#from_zone,#origin_zip,#dest_zip,#customer_zip,#det_customer_zip').typeahead(null, {
			  name: 'ZIP_CODE',
			  minLength: 2,
			  limit: 10,
			  highlight: true,
			  display: 'ZipCode',
			  source: ZIP_CODE_zips,
			    templates: {
			    suggestion: Handlebars.compile(
			      '<p><strong>{{ZipCode}}</strong> – {{CityMixedCase}}, {{State}}</p>'
			    )
			  }
			});
			
			$('#from_city,#to_city,#origin_city,#dest_city,#customer_city,#det_customer_city').typeahead(null, {
			  name: 'ZIP_CODE',
			  minLength: 2,
			  limit: 10,
			  highlight: true,
			  display: 'CityMixedCase',
			  source: ZIP_CODE_zips,
			    templates: {
			    suggestion: Handlebars.compile(
			      '<p><strong>{{CityMixedCase}}</strong> – {{State}}, {{ZipCode}}</p>'
			    )
			  }
			});
			
			/* for freight charges*/
			$('#from_zone,#from_city').bind('typeahead:selected', function(obj, datum, name) {//alert(datum.toSource());
                                                                $('input#from_city').val(datum.CityMixedCase);
                                                                $('input#from_state').val(datum.State);
                                                                $('input#from_zone').val(datum.ZipCode);
                                                });
			$('#to_zone,#to_city').bind('typeahead:selected', function(obj, datum, name) {//alert(datum.toSource());
                                                                $('input#to_city').val(datum.CityMixedCase);
                                                                $('input#to_state').val(datum.State);
                                                                $('input#to_zone').val(datum.ZipCode);
                                                });
												
				/* for handling charges*/
			$('#origin_zip,#origin_city').bind('typeahead:selected', function(obj, datum, name) {//alert(datum.toSource());
                                                                $('input#origin_city').val(datum.CityMixedCase);
                                                                $('input#origin_state').val(datum.State);
                                                                $('input#origin_zip').val(datum.ZipCode);
                                                });
			$('#dest_zip,#dest_city').bind('typeahead:selected', function(obj, datum, name) {//alert(datum.toSource());
                                                                $('input#dest_city').val(datum.CityMixedCase);
                                                                $('input#dest_state').val(datum.State);
                                                                $('input#dest_zip').val(datum.ZipCode);
                                                });
												
												
				/* for pallet rates*/
				$('#customer_zip,#customer_city').bind('typeahead:selected', function(obj, datum, name) {//alert(datum.toSource());
                                                                $('input#customer_city').val(datum.CityMixedCase);
                                                                $('input#customer_state').val(datum.State);
                                                                $('input#customer_zip').val(datum.ZipCode);
                                                });
				/*  for detention charges */
				$('#det_customer_zip,#det_customer_city').bind('typeahead:selected', function(obj, datum, name) {//alert(datum.toSource());
                                                                $('input#det_customer_city').val(datum.CityMixedCase);
                                                                $('input#det_customer_state').val(datum.State);
                                                                $('input#det_customer_zip').val(datum.ZipCode);
                                                });
			
			
			var CONS_NAME_clients = new Bloodhound({
			  name: 'CONS_NAME',
			  remote: {
				  url: 'exp_suggest_client.php?code=Vinegar&type=consignee&query=%QUERY',
				  wildcard: '%QUERY'
				  },
			  datumTokenizer: Bloodhound.tokenizers.obj.whitespace('CLIENT_NAME'),
			  queryTokenizer: Bloodhound.tokenizers.whitespace
			});
						
			CONS_NAME_clients.initialize();

			$('#CONS_NAME,#consignee_name,#customer_name,#det_customer_name').typeahead(null, {
			  name: 'CONS_NAME',
			  minLength: 2,
			  limit: <?php echo $client_matches; ?>,
			  highlight: true,
			  display: 'CLIENT_NAME',
			  source: CONS_NAME_clients,
			    templates: {
			    suggestion: Handlebars.compile(
			      '<p><strong>{{CLIENT_NAME}}</strong> – {{CONTACT_TYPE}}, {{CONTACT_NAME}}, {{PHONE_OFFICE}}</p>'
			    )
			  }
			});
			
		$('#CONS_NAME').bind('typeahead:selected', function(obj, datum, name) {
                                                                $('input#dest_city').val(datum.CITY);
                                                                $('input#dest_state').val(datum.STATE);
                                                                $('input#dest_zip').val(datum.ZIP_CODE);
                                                });
		$('#consignee_name').bind('typeahead:selected', function(obj, datum, name) {
                                                                $('input#to_city').val(datum.CITY);
                                                                $('input#to_state').val(datum.STATE);
                                                                $('input#to_zip').val(datum.ZIP_CODE);
                                                });
			$('#origin_name').bind('typeahead:selected', function(obj, datum, name) {
                                                                $('input#from_city').val(datum.CITY);
                                                                $('input#from_state').val(datum.STATE);
                                                                $('input#from_zip').val(datum.ZIP_CODE);
                                                });
		$('#shipper_name').bind('typeahead:selected', function(obj, datum, name) {
                                                                $('input#origin_city').val(datum.CITY);
                                                                $('input#origin_state').val(datum.STATE);
                                                                $('input#origin_zip').val(datum.ZIP_CODE);
                                                });
		$('#customer_name').bind('typeahead:selected', function(obj, datum, name) {
																$('input#customer_city').val(datum.CITY);
																$('input#customer_state').val(datum.STATE);
																$('input#customer_zip').val(datum.ZIP_CODE);
											   });
		 $('#det_customer_name').bind('typeahead:selected', function(obj, datum, name) {
																$('input#det_customer_city').val(datum.CITY);
																$('input#det_customer_state').val(datum.STATE);
																$('input#det_customer_zip').val(datum.ZIP_CODE);
					                        	});	
			

</script>

