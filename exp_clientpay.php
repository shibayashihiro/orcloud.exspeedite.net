<?php 

// $Id: exp_clientpay.php 5030 2023-04-12 20:31:34Z duncan $
// Enter client billing

// Set flag that this is a parent file
define( '_STS_INCLUDE', 1 );

// Setup Session
require_once( "include/sts_session_setup.php" ); //used to set a constant
//error_reporting(E_ALL);
require_once( "include/sts_config.php" );
$sts_debug = isset($_GET['debug']) && in_group( EXT_GROUP_DEBUG );
require_once( "include/sts_session_class.php" );
require_once("include/sts_client_class.php");
require_once( "include/sts_result_class.php" );
require_once( "include/sts_shipment_class.php" );
require_once( "include/sts_setting_class.php" );
require_once( "include/sts_item_list_class.php" );
require_once( "include/sts_clientbilling.php" );

$my_session = sts_session::getInstance( $exspeedite_db, $sts_debug );
$my_session->access_check( EXT_GROUP_USER );	// Make sure we should be here

$sts_subtitle = "Enter Client Billing";
require_once( "include/header_inc.php" );
require_once( "include/navbar_inc.php" );

$sql_obj	=	new sts_table($exspeedite_db , SHIPMENT_TABLE , $sts_debug);
$client_master=new sts_table($exspeedite_db , CLIENT_TABLE , $sts_debug);
$client_cat_rate=new sts_table($exspeedite_db , CLIENT_CAT_RATE , $sts_debug);
$client_obj=new sts_table($exspeedite_db , CLIENT_RATE_MASTER , $sts_debug);
$freight_obj=new sts_table($exspeedite_db , FRIGHT_RATE_TABLE , $sts_debug);
$bill_obj=new sts_table($exspeedite_db , CLIENT_BILL , $sts_debug);
$bill_rate=new sts_table($exspeedite_db , CLIENT_BILL_RATES , $sts_debug);
$fr_rate=new sts_table($exspeedite_db , FRIGHT_RATE_TABLE , $sts_debug);
$pallet_obj=new sts_table($exspeedite_db , PALLET_MASTER , $sts_debug);
$pallet_rate1=new sts_table($exspeedite_db , PALLET_RATE , $sts_debug);
$cl_fsc=new sts_table($exspeedite_db , CLIENT_FSC , $sts_debug);
$fsc_sch=new sts_table($exspeedite_db , FSC_SCHEDULE , $sts_debug);
$det_obj=new sts_table($exspeedite_db , DETENTION_RATE , $sts_debug);
$det_master=new sts_table($exspeedite_db , DETENTION_MASTER , $sts_debug);
$undet_obj=new sts_table($exspeedite_db , UNLOADED_DETENTION_RATE , $sts_debug);
$stop_obj=new sts_table($exspeedite_db , STOP_TABLE , $sts_debug);
$clients_obj =	new sts_client($exspeedite_db , $sts_debug);
$res_obj = new sts_result( $clients_obj , false, $sts_debug );
$shipment_table = sts_shipment::getInstance($exspeedite_db, $sts_debug);

$setting_table = sts_setting::getInstance($exspeedite_db, $sts_debug);
$sts_fsc_transaction_date = $setting_table->get( 'option', 'FSC_TRANSACTION_DATE' );
$sts_export_qb = $setting_table->get( 'api', 'EXPORT_QUICKBOOKS' ) == 'true';
$sts_destination = $sts_export_qb ? 'Quickbooks' : 'Accounting';
$multi_company = $setting_table->get("option", "MULTI_COMPANY") == 'true';
$sts_fsc_4digit = $setting_table->get( 'option', 'FSC_4DIGIT' ) == 'true';
$sts_fsc_percent_freight = $setting_table->get( 'option', 'FSC_PERCENT_FREIGHT_ONLY' );
$sts_extra_stops = $setting_table->get( 'option', 'CLIENT_EXTRA_STOPS' ) == 'true';
$sts_log_hours = $setting_table->get( 'option', 'BILLING_LOG_HOURS' ) == 'true';

$shipment_id=$_GET['id'];

if($shipment_id!="") {
	if(isset($_POST['savebilling'])) {
		$getCount=$bill_obj->database->get_one_row(" SELECT COUNT(*) AS CNT FROM ".CLIENT_BILL." WHERE SHIPMENT_ID=".$shipment_id);
		
		if($getCount['CNT']==0) {
			$hazmat=$temp=$pallets=$detention_hrs=$un_detention_hrs=$mil=$rmp_mil=$free_det_hr=$free_undet_hr=$rate_per_hour=$un_rate_per_hour=$stop_off=$weekend=$adjust_value=$selection_fee=$discount=0;
			$handling_charge=$extra_charge=$carrier=$cod=$rpm=$so_rate=0;
			$fuel_cost=$pallet_rate=$per_pall_rate=$avg_rate=$hand_pallet=0;
			$freight=$detention=$un_detention=$not_billed_hours=0;
			$avg_rate_col=$stop_off_note=$adjust_title=$extra_charges_note='';
			$rate=$code=$category=$rate_name=array();
			if(! empty($_POST['PALLETS'])) 		$pallets=intval($_POST['PALLETS']);
			if(! empty($_POST['PER_PALLETS']))	$per_pall_rate=floatval($_POST['PER_PALLETS']);
			if(! empty($_POST['PALLETS_RATE']))	$pallet_rate=floatval($_POST['PALLETS_RATE']);
			if(! empty($_POST['HAND_CHARGES']))
				$handling_charge=floatval($_POST['HAND_CHARGES']);
			if(! empty($_POST['FREIGHT_CHARGES']))
				$freight=floatval($_POST['FREIGHT_CHARGES']);
			if(! empty($_POST['FREE_DETENTION_HOUR']))
				$free_det_hr=floatval($_POST['FREE_DETENTION_HOUR']);
			
			if(! empty($_POST['DETENTION_HOUR']))
				$detention_hrs=floatval($_POST['DETENTION_HOUR']);
			
			if(! empty($_POST['RATE_PER_HOUR']))
				$rate_per_hour=floatval($_POST['RATE_PER_HOUR']);
			
			if(! empty($_POST['DETENTION_RATE']))
				$detention=floatval($_POST['DETENTION_RATE']);
			
			if(! empty($_POST['FREE_UN_DETENTION_HOUR']))
				$free_undet_hr=floatval($_POST['FREE_UN_DETENTION_HOUR']);
			
			if(! empty($_POST['UNLOADED_DETENTION_HOUR']))
				$un_detention_hrs=floatval($_POST['UNLOADED_DETENTION_HOUR']);
			
			if(! empty($_POST['UN_RATE_PER_HOUR']))
				$un_rate_per_hour=floatval($_POST['UN_RATE_PER_HOUR']);
			
			if(! empty($_POST['UNLOADED_DETENTION_RATE']))
				$un_detention=floatval($_POST['UNLOADED_DETENTION_RATE']);

			if(! empty($_POST['EXTRA_CHARGES']))
				$extra_charge=floatval($_POST['EXTRA_CHARGES']);

			//! SCR# 406 - truncate the field rather than fail.
			if(! empty($_POST['EXTRA_CHARGES_NOTE']))
				$extra_charges_note= $bill_obj->trim_to_fit('EXTRA_CHARGES_NOTE', $_POST['EXTRA_CHARGES_NOTE']);
				
			//! Duncan - SCR 232 - log hours
			if($sts_log_hours && ! empty($_POST['NOT_BILLED_HOURS']))
				$not_billed_hours=floatval($_POST['NOT_BILLED_HOURS']);

			if(! empty($_POST['CARRIER']))		$carrier=floatval($_POST['CARRIER']);
			if(! empty($_POST['COD']))			$cod=floatval($_POST['COD']);
			if(! empty($_POST['RATE_NAME']))	$rate_name=$_POST['RATE_NAME'];
			if(! empty($_POST['RPM']))			$rpm=floatval($_POST['RPM']);
			if(! empty($_POST['SO_RATE']))		$so_rate=floatval($_POST['SO_RATE']);
			if(! empty($_POST['FUEL_COST']))	$fuel_cost=floatval($_POST['FUEL_COST']);
			if(! empty($_POST['MILLEAGE']))		$mil=floatval($_POST['MILLEAGE']);
			if(! empty($_POST['RPM_Rate']))		$rmp_mil=floatval($_POST['RPM_Rate']);
			if(! empty($_POST['RATE']))			$rate=$_POST['RATE'];
			if(! empty($_POST['CODE']))			$code=$_POST['CODE'];
			if(! empty($_POST['CATEGORY']))		$category=$_POST['CATEGORY'];
			if(! empty($_POST['HAZMAT']))		$hazmat=$_POST['HAZMAT'];
	
			if(! empty($_POST['TEMP_CONTROLL']))	$temp=$_POST['TEMP_CONTROLL'];
			
			if(! empty($_POST['FUEL_AVG_RATE']))	$avg_rate=floatval($_POST['FUEL_AVG_RATE']);
			
			//! SCR# 406 - truncate the field rather than fail.
			if(! empty($_POST['FUEL_AVG_RATE_COL']))
				$avg_rate_col= $bill_obj->trim_to_fit('FSC_COLUMN', $_POST['FUEL_AVG_RATE_COL']);
			
			if(! empty($_POST['STOP_OFF']))			$stop_off=floatval($_POST['STOP_OFF']);
			
			if(! empty($_POST['STOP_OFF_NOTE']))	$stop_off_note=$_POST['STOP_OFF_NOTE'];
			
			if(! empty($_POST['WEEKEND']))			$weekend=floatval($_POST['WEEKEND']);
			
			if(! empty($_POST['SELECTION_FEE']))	$selection_fee=floatval($_POST['SELECTION_FEE']);
			if(! empty($_POST['DISCOUNT']))			$discount=floatval($_POST['DISCOUNT']);
	
			if(! empty($_POST['HAND_PALLET']))
				$hand_pallet=intval($_POST['HAND_PALLET']);
			
			//! SCR# 406 - truncate the field rather than fail.
			if(! empty($_POST['ADJUSTMENT_CHARGE_TITLE']))
				$adjust_title= $bill_obj->trim_to_fit('ADJUSTMENT_CHARGE_TITLE', $_POST['ADJUSTMENT_CHARGE_TITLE']);

			if(! empty($_POST['ADJUSTMENT_CHARGE']))
				$adjust_value=floatval($_POST['ADJUSTMENT_CHARGE']);
			
			$total=floatval($_POST['TOTAL']);
			
			$currency = 'USD';
			if(isset($_POST['CURRENCY']))
			{$currency=$_POST['CURRENCY'];}
			
			$terms = 0;
			if(isset($_POST['TERMS']))
			{$terms=$_POST['TERMS'];}			
			
			// 3/6 - Duncan, removed unnecessary quotes around numbers.
			$bill_id=$bill_obj->get_insert_id(
			"SHIPMENT_ID, PALLETS, PER_PALLETS, PALLETS_RATE,
			HAND_CHARGES, EXTRA_CHARGES, FREIGHT_CHARGES, FREE_DETENTION_HOUR,
			DETENTION_HOUR, DETENTION_RATE, FREE_UN_DETENTION_HOUR, UNLOADED_DETENTION_HOUR,
			UNLOADED_DETENTION_RATE, CARRIER, COD, RPM,
			SO_RATE, FUEL_COST, HAZMAT, TEMP_CONTROLLED,
			MILLEAGE, RPM_RATE, TOTAL, FSC_COLUMN,
			FSC_AVERAGE_RATE, RATE_PER_HOUR, UN_RATE_PER_HOUR, STOP_OFF,
			WEEKEND, STOP_OFF_NOTE, HAND_PALLET, ADJUSTMENT_CHARGE_TITLE,
			ADJUSTMENT_CHARGE, SELECTION_FEE, DISCOUNT, CURRENCY, TERMS,
			EXTRA_CHARGES_NOTE".($sts_log_hours ? ", NOT_BILLED_HOURS" : ""),
			"$shipment_id, $pallets, $per_pall_rate, $pallet_rate,
			$handling_charge, $extra_charge, $freight, $free_det_hr,
			$detention_hrs, $detention, $free_undet_hr, $un_detention_hrs,
			$un_detention, $carrier, $cod , $rpm,
			$so_rate, $fuel_cost, '$hazmat', '$temp',
			$mil, $rmp_mil, $total,'$avg_rate_col',
			$avg_rate, $rate_per_hour, $un_rate_per_hour, $stop_off,
			$weekend,'".$bill_obj->real_escape_string($stop_off_note)."',$hand_pallet,'".
			$bill_obj->real_escape_string($adjust_title)."',
			$adjust_value, $selection_fee, $discount, '$currency', $terms,
			'".$bill_obj->real_escape_string($extra_charges_note)."'".
			($sts_log_hours ? ", $not_billed_hours" : ""));
		//echo '&&&&'.count($code);
			if($bill_id!="" && (count($code)>0)) {
				for($i=0;$i<count($code);$i++) {
					// 3/6 - Duncan, removed unnecessary quotes around numbers.
					$bill_rate->add_row("BILLING_ID, RATE_CODE, RATE_NAME, CATEGORY,RATES",
					"$bill_id, '".$code[$i]."', '".$rate_name[$i]."', '".$category[$i]."', ".$rate[$i]);
				}
			}

			//! SCR# 719 - log financial details
			log_financial( $shipment_table, $shipment_id, 'Save', $pallet_rate, $hand_pallet,
	$handling_charge, $freight, $extra_charge, $stop_off, $weekend,
	$detention, $un_detention, $rmp_mil, $fuel_cost, $adjust_value,
	$selection_fee, $discount, $total, $currency, $code, $rate );

			check_financial( $shipment_table, $shipment_id, $pallet_rate, $hand_pallet,
	$handling_charge, $freight, $extra_charge, $stop_off, $weekend,
	$detention, $un_detention, $rmp_mil, $fuel_cost, $adjust_value,
	$selection_fee, $discount, $total, $rate );
		}
}

if($shipment_id!='')
{
	$getCount=$bill_obj->database->get_one_row(" SELECT COUNT(*) AS CNT FROM ".CLIENT_BILL." WHERE SHIPMENT_ID=".$shipment_id);
	if($getCount['CNT']=='1')
	{
		if( $sts_debug )
			die;
		else
			echo '<script language="javascript" type="application/javascript">window.location.href="exp_shipment_bill.php?id='.$shipment_id.'";</script>';
	}
}


	$res['shipment_details'] =$sql_obj->database->get_one_row(
		"SELECT *,
		COALESCE((SELECT BC_NAME FROM EXP_BUSINESS_CODE
		WHERE EXP_SHIPMENT.BUSINESS_CODE = EXP_BUSINESS_CODE.BUSINESS_CODE
		LIMIT 1), '') AS BUSINESS_CODE_NAME,
		COALESCE((SELECT TERMS FROM EXP_CLIENT
			WHERE CLIENT_CODE = BILLTO_CLIENT_CODE), 0) AS DEFAULT_TERMS
		FROM ".SHIPMENT_TABLE." WHERE SHIPMENT_CODE=".$shipment_id);
		
	// 3/6 - Duncan, removed unnecessary quotes around numbers.
	$res['handling_rate_details'] =$sql_obj->database->get_one_row(
		"SELECT ".HANDLING_TABLE.".* FROM ".SHIPMENT_TABLE." , ".HANDLING_TABLE."
		WHERE ".SHIPMENT_TABLE.".BILLTO_CLIENT_CODE=".HANDLING_TABLE.".CLIENT_ID  
		AND  ".SHIPMENT_TABLE.".SHIPMENT_CODE=".$shipment_id."
		AND  ".HANDLING_TABLE.".PALLET=".$res['shipment_details']['PALLETS']."
		AND ".HANDLING_TABLE.".USER_TYPE='client'
	");
																													
	//! Duncan - get client code from shipment table.
	$client_id = array();
	$client_id['CLIENT_CODE'] = $res['client_id'] = isset($res['shipment_details']) &&
		isset($res['shipment_details']["BILLTO_CLIENT_CODE"]) ?
			$res['shipment_details']["BILLTO_CLIENT_CODE"] : 0;
			
	//! Duncan - get client currency
	$check = $client_master->fetch_rows("CLIENT_CODE = ".$res['client_id'],"CURRENCY_CODE");
	if( is_array($check) && count($check) == 1 && isset($check[0]["CURRENCY_CODE"]))
		$res['CURRENCY_CODE'] = $check[0]["CURRENCY_CODE"];
	else { //! SCR# 257 - default to correct currency
		$sc=$client_master->database->get_one_row("SELECT SHIPMENT_CURRENCY( $shipment_id ) AS SC");
		if( is_array($sc) && ! empty($sc["SC"]) )
			$res['CURRENCY_CODE'] = $sc["SC"];
		else
			$res['CURRENCY_CODE'] = 'USD'; // Default
	}	
		
	
   /*
   $client_id=$client_master->database->get_one_row("SELECT CLIENT_CODE FROM ".CLIENT_TABLE." WHERE NAME='".$client_master->real_escape_string($res['shipment_details']['BILLTO_NAME'])."'");
   if(isset($client_id['CLIENT_CODE']) && $client_id['CLIENT_CODE']!="")
	{$res['client_id']=$client_id['CLIENT_CODE'];}
	else
	{$res['client_id']='0';
	$client_id['CLIENT_CODE']=0;}
   */
   
   //! EDI Freight rates (EDI_204_PRIMARY == SHIPMENT_CODE)
   $edi_freight = 0;
   if( isset($res['shipment_details']) &&
		isset($res['shipment_details']["EDI_204_PRIMARY"]) &&
		intval($res['shipment_details']["EDI_204_PRIMARY"]) == intval($shipment_id) ) {
		
		$shipments = $client_cat_rate->database->get_multiple_rows("SELECT SHIPMENT_CODE 
			FROM EXP_SHIPMENT
			WHERE EDI_204_PRIMARY = ".$shipment_id);
		$res['shipment_details']["EDI_SHIPMENTS"] = array();
		if( is_array($shipments)) {
			foreach($shipments as $s) { 
				$res['shipment_details']["EDI_SHIPMENTS"][] = $s["SHIPMENT_CODE"];
			}
		}
		
		//! Penske uses EDI_204_L305_CHARGE
		if( ! empty($res['shipment_details']["EDI_204_L305_CHARGE"]) &&
			intval($res['shipment_details']["EDI_204_L305_CHARGE"]) > 0 ) {
			$edi_freight = intval($res['shipment_details']["EDI_204_L305_CHARGE"]);
		} else
		if( isset($res['shipment_details']["EDI_204_L303_RATE"]) &&
			isset($res['shipment_details']["EDI_204_L304_RQUAL"]) ) {
			$edi_freight_rate = $res['shipment_details']["EDI_204_L303_RATE"];
			$edi_rate_qual = $res['shipment_details']["EDI_204_L304_RQUAL"];
			if( $edi_rate_qual == 'Per Hundred Weight' ) {
				if( isset($res['shipment_details']["EDI_204_L301_WEIGHT"]) &&
					isset($res['shipment_details']["EDI_204_L312_WQUAL"]) &&
					$res['shipment_details']["EDI_204_L312_WQUAL"] = 'Pounds' ) {
					$edi_weight = $res['shipment_details']["EDI_204_L301_WEIGHT"];
					$edi_freight = $edi_freight_rate * $edi_weight / 100;
				}
			} else {
				$edi_freight = $edi_freight_rate;	// Flat rate or per shipment
			}
		}
	}
   
	// 3/6 - Duncan, removed unnecessary quotes around numbers.
	$res['frieght_charges']=$freight_obj->database->get_one_row(
	"SELECT LINE_HAUL FROM ".FRIGHT_RATE_TABLE."
	WHERE ".FRIGHT_RATE_TABLE.".CLIENT_ID=".$client_id['CLIENT_CODE']."
	AND DESTINATION_CITY='".$res['shipment_details']['BILLTO_CITY']."'
	AND DESTINAION_STATE='".$res['shipment_details']['BILLTO_STATE']."'
	AND USER_TYPE='client'");
	
	//! SCR# 896 - Client Rates - flat rate if selected shipper or consignee is set
	$res['client_rates'] = [];
	$client_rates = $client_cat_rate->database->get_multiple_rows(
	"SELECT * FROM ".CLIENT_CAT_RATE." JOIN ".CLIENT_ASSIGN_RATE."
	ON ".CLIENT_ASSIGN_RATE.".RATE_ID=".CLIENT_CAT_RATE.".CLIENT_RATE_ID
	JOIN ".CLIENT_CAT." ON ".CLIENT_CAT.".CLIENT_CAT=".CLIENT_CAT_RATE.".CATEGORY
	WHERE ".CLIENT_ASSIGN_RATE.".CLIENT_ID=".$client_id['CLIENT_CODE']."
	AND ".CLIENT_ASSIGN_RATE.".USER_TYPE='client'");
	
	if( is_array($client_rates) && count($client_rates) > 0 ) {
		$scc = is_array($res['shipment_details']) &&
			isset($res['shipment_details']["SHIPPER_CLIENT_CODE"]) ?
				$res['shipment_details']["SHIPPER_CLIENT_CODE"] : -1;
		$ccc = is_array($res['shipment_details']) &&
			isset($res['shipment_details']["CONS_CLIENT_CODE"]) ?
				$res['shipment_details']["CONS_CLIENT_CODE"] : -1;
		if( $sts_debug ) echo "<p>exp_clientpay.php: SHIPPER_CLIENT_CODE = $scc CONS_CLIENT_CODE = $ccc</p>";
	
		foreach( $client_rates as $rate_row ) {
			if( $sts_debug ) {
				echo "<pre>rate_row\n";
				var_dump($rate_row);
				echo "</pre>";
			}
			if( is_array($rate_row) && isset($rate_row['CATEGORY_NAME']) &&
				$rate_row['CATEGORY_NAME'] == 'Shipper' ) {
				if( $sts_debug ) echo "<p>exp_clientpay.php: Shipper rate on ".$rate_row['SHIPPER_CLIENT_CODE']."</p>";

				if( isset($rate_row['SHIPPER_CLIENT_CODE']) &&
					$rate_row['SHIPPER_CLIENT_CODE'] == $scc )
					$res['client_rates'][] = $rate_row;
			} else if( is_array($rate_row) && isset($rate_row['CATEGORY_NAME']) &&
				$rate_row['CATEGORY_NAME'] == 'Consignee' ) {
				if( $sts_debug ) echo "<p>exp_clientpay.php: Consignee rate on ".$rate_row['CONS_CLIENT_CODE']."</p>";
				if( isset($rate_row['CONS_CLIENT_CODE']) &&
					$rate_row['CONS_CLIENT_CODE'] == $ccc )
					$res['client_rates'][] = $rate_row;
			} else {
				$res['client_rates'][] = $rate_row;
			}
		}
	}
	
	$res['client_detail']=$client_obj->database->get_one_row(
	"SELECT * FROM ".CLIENT_RATE_MASTER."
	WHERE CLIENT_ID=".$client_id['CLIENT_CODE']."
	AND USER_TYPE='client'");
	
	//fetch freight rate
	
	//!fetch my matching shipper & conginee name or their zipcode -------##
	
	$res['freight_rate']=$fr_rate->database->get_one_row(
	"SELECT AMOUNT, PER_MILE, MILEAGE, FSC FROM ".FRIGHT_RATE_TABLE."
	WHERE  CLIENT_ID=".$client_id['CLIENT_CODE']."
	AND USER_TYPE='client'
	AND (SHIPPER_NAME='".$fr_rate->real_escape_string($res['shipment_details']['SHIPPER_NAME'])."'
		OR ORIGIN_ZIP='".$res['shipment_details']['SHIPPER_ZIP']."')
	AND (CONSIGNEE_NAME='".$fr_rate->real_escape_string($res['shipment_details']['CONS_NAME'])."'
		OR DESTINATION_ZIP='".$res['shipment_details']['CONS_ZIP']."')");


	//! EDI - override freight amount
	if( $edi_freight > 0 )
		$res['freight_rate']['AMOUNT']=number_format($edi_freight, 2,'.','');
	

	$res['minmaxpalletrates']=$pallet_id=$pallet_obj->database->get_one_row(
	"SELECT PALLET_ID, MIN_CHARGE, MAX_CHARGE
	FROM ".PALLET_MASTER." JOIN ".SHIPMENT_TABLE." ON 
	".SHIPMENT_TABLE.".SHIPMENT_CODE=".$shipment_id."
	AND ".SHIPMENT_TABLE.".BILLTO_CLIENT_CODE=".PALLET_MASTER.".CLIENT_ID
	WHERE ".PALLET_MASTER.".CUST_NAME='".$pallet_obj->real_escape_string($res['shipment_details']['CONS_NAME'])."'
	AND CUST_CITY='".$pallet_obj->real_escape_string($res['shipment_details']['CONS_CITY'])."'
	GROUP BY PALLET_ID");

	//print_r($pallet_id);
	$res['pallet_rate']['PALLET_RATE']='0';
	//$res['pallet_rate']='';
	if(is_array($pallet_id) && count($pallet_id)>0 && $pallet_id['PALLET_ID']!="")
	{
		 $x="SELECT PALLET_RATE FROM ".PALLET_RATE."
		 WHERE PALLET_ID=".$pallet_id['PALLET_ID']."
		 AND ((".$res['shipment_details']['PALLETS']." BETWEEN PALLET_FROM AND PALLET_TO)
		 OR ((PALLET_FROM <=".$res['shipment_details']['PALLETS']." AND PALLET_TO >= 11 )))";
		
		$res['pallet_rate']=$pallet_rate1->database->get_one_row($x);
	//print_r($res['pallet_rate']);
	}
	
	//! Duncan - rate per mile
	$res['rpm']=0.0000;
	if(is_array($res['freight_rate']) && count($res['freight_rate'])>0 && isset($res['freight_rate']['PER_MILE'])) {
		$res['rpm']=$res['freight_rate']['PER_MILE'];
	} else if( isset($res['client_detail']) && isset($res['client_detail']['RATE_PER_MILE']) ){
		$res['rpm']=$res['client_detail']['RATE_PER_MILE'];
	}
	
	//!-- fetch milleage .high priority i given to milleage frm freight rate table,if not available ,fetch from shipment-----##
	$res['milleage']='';
	if(is_array($res['freight_rate']) && count($res['freight_rate'])>0 && isset($res['freight_rate']['MILEAGE']) && $res['freight_rate']['MILEAGE']!="" && $res['freight_rate']['MILEAGE']!="0")
	{
		$res['milleage']=$res['freight_rate']['MILEAGE'];
	}
	else
	{
		$res['milleage']=$res['shipment_details']['DISTANCE'];
	}
	
	//! SCR# 297 - RPM_Rate (= Total) set to AMOUNT
	$res['RPM_Rate']=0.0;
	if(is_array($res['freight_rate']) && count($res['freight_rate'])>0 && isset($res['freight_rate']['AMOUNT']) && $res['freight_rate']['AMOUNT']!="" && $res['freight_rate']['AMOUNT']!="0") {
		if( $sts_debug ) echo "<p>Duncan: use freight_rate/AMOUNT ".$res['freight_rate']['AMOUNT']."</p>";
		$res['RPM_Rate']=floatval($res['freight_rate']['AMOUNT']);
	} else {
		if( $sts_debug ) echo "<p>Duncan: use rpm * milleage</p>";
		$res['RPM_Rate']=floatval($res['rpm']) * floatval($res['milleage']);
	}
	if( $sts_debug ) {
		echo "<pre>Duncan: rpm, milleage, RPM_Rate</p>";
		var_dump($res['rpm'], $res['milleage'], $res['RPM_Rate']);
		echo "</pre>";
	}
	
	//! SCR# 225 Use whole load mileage
	// if option/CLIENT_EXTRA_STOPS and EXP_CLIENT.CLIENT_EXTRA_STOPS
	// and #shipments = 1 and #stops > 2
	if( $sts_extra_stops ) {
		$check = $sql_obj->database->get_one_row( "SELECT 
			(SELECT L.TOTAL_DISTANCE FROM EXP_LOAD L
			WHERE L.LOAD_CODE = S.LOAD_CODE) AS TOTAL_DISTANCE
			FROM EXP_SHIPMENT S
			WHERE S.SHIPMENT_CODE = $shipment_id
			AND (SELECT COUNT(T.SHIPMENT_CODE) FROM EXP_SHIPMENT T
			WHERE T.LOAD_CODE = S.LOAD_CODE) = 1
			AND (SELECT COUNT(T.STOP_CODE) FROM EXP_STOP T
			WHERE T.LOAD_CODE = S.LOAD_CODE) > 2
			AND (SELECT C.CLIENT_EXTRA_STOPS FROM EXP_CLIENT C
			WHERE C.CLIENT_CODE = S.BILLTO_CLIENT_CODE)" );
			
		if( is_array($check) && isset($check["TOTAL_DISTANCE"]) && $check["TOTAL_DISTANCE"] > 0 )
			$res['milleage'] = $check["TOTAL_DISTANCE"];
	}
	
	//! fetch fuel charge---------##
	$res['percent']=0;
	$fsc_id=$cl_fsc->database->get_one_row(
		"SELECT FSC_ID, (SELECT AVERAGE_PRICE FROM EXP_FSC_SCHEDULE
			WHERE FSC_UNIQUE_ID = EXP_CLIENT_FSC.FSC_ID
			LIMIT 1) AS AVERAGE_PRICE
		FROM EXP_CLIENT_FSC
		WHERE CLIENT_ID=".$client_id['CLIENT_CODE']."
		AND USER_TYPE='client'
		GROUP BY FSC_ID
		ORDER BY ASSIGN_FSC_ID DESC");

	if(is_array($fsc_id) && count($fsc_id)>0) {
		if( isset($fsc_id['AVERAGE_PRICE']) && $fsc_id['AVERAGE_PRICE'] == 0) {
			//! SCR# 410 - if AVERAGE_PRICE == 0 then only look at date
			$fsc_detail=$fsc_sch->database->get_one_row("
				SELECT FSC_COLUMN, FLAT_AMOUNT, PER_MILE_ADJUST, PERCENT 
				FROM EXP_FSC_SCHEDULE 
				WHERE FSC_UNIQUE_ID='".$fsc_id['FSC_ID']."'
				AND (START_DATE <= GET_ASOF_FSC(".$shipment_id.") AND  END_DATE >= GET_ASOF_FSC(".$shipment_id.") )
				LIMIT 1");
		} else {
			//! SCR# 349 - Updated FSC to use GET_ASOF_FSC() SQL function	
			//! SCR# 385 - Switch from ASC to DESC, to get history first
			$fsc_detail=$fsc_sch->database->get_one_row("
				SELECT FSC_COLUMN, FLAT_AMOUNT, PER_MILE_ADJUST, PERCENT 
				FROM(
				SELECT 1 AS SEQ, FSC_COLUMN, FLAT_AMOUNT, PER_MILE_ADJUST, PERCENT 
				FROM EXP_FSC_SCHEDULE 
				WHERE FSC_UNIQUE_ID='".$fsc_id['FSC_ID']."'
				AND (START_DATE <= GET_ASOF_FSC(".$shipment_id.") AND  END_DATE >= GET_ASOF_FSC(".$shipment_id.") ) AND 
				AVERAGE_PRICE BETWEEN `LOW_PER_GALLON` AND `HIGH_PER_GALLON`
				UNION
				(SELECT 2 AS SEQ, S.FSC_COLUMN, S.FLAT_AMOUNT, S.PER_MILE_ADJUST, S.PERCENT
				FROM EXP_FSC_HISTORY H, EXP_FSC_SCHEDULE S
				WHERE H.FSC_UNIQUE_ID='".$fsc_id['FSC_ID']."'
				AND (FSC_START_DATE <= GET_ASOF_FSC(".$shipment_id.")AND  FSC_END_DATE >= GET_ASOF_FSC(".$shipment_id.") ) AND 
				FSC_AV_PRICE BETWEEN LOW_PER_GALLON AND HIGH_PER_GALLON
				AND H.FSC_UNIQUE_ID = S.FSC_UNIQUE_ID
				ORDER BY H.FSC_HIS_ID DESC
				LIMIT 1)
				ORDER BY SEQ DESC
				LIMIT 1) a");
		}
		
		$res['fuel_rate']=$res['avg_rate_field']=$res['avg_rate_val']='';
		//print_r($fsc_detail);
		if(is_array($fsc_detail) && $fsc_detail['FLAT_AMOUNT']!="") {
			if($fsc_detail['FLAT_AMOUNT']!="0") {
				$res['fuel_rate']=number_format($fsc_detail['FLAT_AMOUNT'],$sts_fsc_4digit ? 4 : 2,'.','');
				$res['avg_rate_field']='Flat Amount';
				$res['avg_rate_val']=$fsc_detail['FLAT_AMOUNT'];
			} else if($fsc_detail['PER_MILE_ADJUST']!="0") {
				$res['fuel_rate']=number_format($res['milleage']*$fsc_detail['PER_MILE_ADJUST'],2,'.','');
				$res['avg_rate_field']='Per Mile Adjust';
				$res['avg_rate_val']=number_format((float)$fsc_detail['PER_MILE_ADJUST'],$sts_fsc_4digit ? 4 : 2,'.','');
			} else if($fsc_detail['PERCENT']!="0") {
				$res['fuel_rate']='';
				$res['percent']=$percent=$fsc_detail['PERCENT'];
				$res['avg_rate_field']='Percent';
				$res['avg_rate_val']=$fsc_detail['PERCENT'];
			}
		}
		
	}
	else
	{
		$res['fuel_rate']='';
	}

	//! SCR# 297 - Override fuel_rate
	if( is_array($res['freight_rate']) && count($res['freight_rate'])>0 && isset($res['freight_rate']['FSC']) &&
		$res['freight_rate']['FSC'] <> '') {
		$res['fuel_rate'] = $res['freight_rate']['FSC'];
	}
	
	if( $sts_debug ) {
		echo "<pre>Duncan: check FSC - rpm, milleage, RPM_Rate, freight_rate, fuel_rate, avg_rate_val\n";
		var_dump($res['rpm'], $res['milleage'], $res['RPM_Rate']);
		var_dump($res['freight_rate']);
		var_dump($res['fuel_rate']);
		var_dump($res['avg_rate_val']);
		echo "</pre>";
	}
	
	#----------no. of picks -------#
	$no_of_picks=0;
	if($res['shipment_details']['LOAD_CODE']!=0)
	{
	 $total_picks=$stop_obj->database->get_one_row("select count(*) num_picks from exp_stop
		 where load_code = ".$res['shipment_details']['LOAD_CODE']."
		 and stop_type = 'pick' ");//AND SHIPMENT=".$shipment_id."
	 
	$no_of_picks=$total_picks['num_picks'];
	}
	$res['no_of_picks']=$no_of_picks;
	
	#-------no. of drops---------#
	$no_of_drops=0;
	if($res['shipment_details']['LOAD_CODE']!=0)
	{
	 $total_drops=$stop_obj->database->get_one_row("select count(*) num_drop from exp_stop
	 	where load_code = ".$res['shipment_details']['LOAD_CODE']."
	 	and stop_type = 'drop' ");//AND SHIPMENT=".$shipment_id."
	$no_of_drops=$total_drops['num_drop'];
	}
	$res['no_of_drops']=$no_of_drops;
	//echo $time1=gmdate('H:i:s a',28800);
	
	#--------expected arrival & departure---------------#
	$expected_pickup_time=$expected_drop_time='';
	$pickupDate=$sql_obj->database->get_one_row("SELECT TIMESTAMP(PICKUP_DATE,
	(
       CASE 
	   WHEN PICKUP_TIME_OPTION NOT IN ('ASAP', 'FCFS')
	        THEN
			     CASE 
				 WHEN COALESCE(PICKUP_TIME1,'') = ''
				 THEN '00:00'
	                     ELSE
	                   CONCAT_WS(':',SUBSTR(PICKUP_TIME1,1,2),SUBSTR(PICKUP_TIME1,3,2)) 
				END
		ELSE '00:00' 
		END
		)) AS PICKUP_DATE,
	TIMESTAMP(DELIVER_DATE,
	( 
	CASE 
	   WHEN DELIVER_TIME_OPTION NOT IN ('ASAP', 'FCFS') 
	        THEN
	                   CASE
 				   WHEN COALESCE(DELIVER_TIME1,'') = '' 
			          THEN '00:00'
	                        ELSE
	                        CONCAT_WS(':',SUBSTR(DELIVER_TIME1,1,2),SUBSTR(DELIVER_TIME1,3,2)) 
			          END
		ELSE '00:00' 
		END
		)) AS DELIVER_DATE
	 FROM ".SHIPMENT_TABLE." WHERE SHIPMENT_CODE=".$shipment_id);
	 
	
	 
	
	if(count($pickupDate)>0 && $pickupDate['PICKUP_DATE']!="")
	{
		/*$time1='';
		if($pickupDate['PICKUP_TIME1']!="" && is_numeric($pickupDate['PICKUP_TIME1']))
		
		$time1=gmdate('H:i:s',$pickupDate['PICKUP_TIME1']);
		$expected_pickup_time=date('m/d/Y H:i:s',strtotime($pickupDate['PICKUP_DATE'].' '.$time1));*/
		$expected_pickup_time=date('m/d H:i',strtotime($pickupDate['PICKUP_DATE']));
	}
	
	if(count($pickupDate)>0 && $pickupDate['DELIVER_DATE']!="")
	{/*$time2='';
		
		if($pickupDate['DELIVER_TIME1']!="" && is_numeric($pickupDate['DELIVER_TIME1']))
		{$time2=gmdate('H:i:s',$pickupDate['DELIVER_TIME1']);}
		$expected_drop_time=date('m/d/Y H:i:s',strtotime($pickupDate['DELIVER_DATE'].' '.$time2));*/
		$expected_drop_time=date('m/d H:i',strtotime($pickupDate['DELIVER_DATE']));
	}
	$res['expected_drop_time']=$expected_drop_time;
	$res['expected_pickup_time']=$expected_pickup_time;
	#---------actual arrival & departure-----------------#
	$actual_pickup_time=$actual_drop_time=$actual_pickup_depart_time=$actual_drop_arrival_time='';
	$pick_time=$stop_obj->database->get_one_row("SELECT ACTUAL_ARRIVE,ACTUAL_DEPART
		FROM exp_stop
		where STOP_TYPE='pick'
		AND SHIPMENT=".$shipment_id);
	if(is_array($pick_time) && count($pick_time)>0 && $pick_time['ACTUAL_ARRIVE']!="")
	{
		$actual_pickup_time=date('m/d H:i',strtotime($pick_time['ACTUAL_ARRIVE']));
		if($pick_time['ACTUAL_DEPART']!="")
		{
			$actual_pickup_depart_time=date('m/d H:i',strtotime($pick_time['ACTUAL_DEPART']));
		}
	}
	$drop_time=$stop_obj->database->get_one_row("SELECT ACTUAL_ARRIVE,ACTUAL_DEPART
		FROM exp_stop
		where STOP_TYPE='drop'
		AND SHIPMENT=".$shipment_id);
	
	if(is_array($drop_time) && count($drop_time)>0 && $drop_time['ACTUAL_DEPART']!="")
	{
		$actual_drop_time=date('m/d H:i',strtotime($drop_time['ACTUAL_DEPART']));
		if($drop_time['ACTUAL_ARRIVE']!="")
		{
			$actual_drop_arrival_time=date('m/d H:i',strtotime($drop_time['ACTUAL_ARRIVE']));
		}
	}
	$res['actual_pickup_time']=$actual_pickup_time;
	$res['actual_drop_time']=$actual_drop_time;
	$res['actual_pickup_depart_time']=$actual_pickup_depart_time;
	$res['actual_drop_arrival_time']=$actual_drop_arrival_time;
	$loaded_detention_hrs=$unloaded_detention_hrs=0;
	//!---calculate loaded & unloaded detention hours.----##
	if($expected_pickup_time!="" && $actual_pickup_time!="")
	{$hour1 = $hour2 =$hour3=$minutes = 0;
	$date1 = new DateTime($expected_pickup_time);
	$date2 = new DateTime($actual_pickup_time);
	$diff = $date2->diff($date1);
 	if($diff->format('%a') > 0)
	{
	      $hour1 = $diff->format('%a')*24;
	}
	if($diff->format('%h') > 0)
	{
		$hour2 = $diff->format('%h');
	}
	if($diff->format('%i') > 0)
	{
 		$minutes = $diff->format('%i');
		if($minutes >0)
		{
			$hour3=$minutes/60;
		}
	}
$loaded_detention_hrs=number_format(($hour1+$hour2+$hour3),2,'.','');

 
## ---another way to calculate time difference-- ##
		//$timestamp1 = strtotime($expected_pickup_time);
		//$timestamp2 = strtotime($actual_pickup_time);
		//$hour = abs($timestamp2 - $timestamp1)/(60*60);
       //  echo "Difference between two dates is " . $hour . " hour(s)";
	   
	    //--- fetch loaded detention rates
	 
	}
	$res['loaded_detention_hrs']=$loaded_detention_hrs;
	
	//!---calculate unloaded detention hours.----##
	if($expected_drop_time!="" && $actual_drop_time!="")
	{$hour1 = $hour2 =$hour3= $minutes =0;
	$date1 = new DateTime($expected_drop_time);
	$date2 = new DateTime($actual_drop_time);
	$diff = $date2->diff($date1);
 	if($diff->format('%a') > 0)
	{
	      $hour1 = $diff->format('%a')*24;
	}
	if($diff->format('%h') > 0)
	{
		$hour2 = $diff->format('%h');
	}
	if($diff->format('%i') > 0)
	{
 		$minutes = $diff->format('%i');
		if($minutes >0)
		{
			$hour3=$minutes/60;
		}
	}
     $unloaded_detention_hrs=number_format(($hour1+$hour2+$hour3),2,'.','');
	
	}
	$res['unloaded_detention_hrs']=$unloaded_detention_hrs;
	
	//!fetch free detention hours------##
	$res['det_free_hr']=$loaded_perhr_rate=$new_det_hr=$loaded_detention_Rate=0;
	$free_hours=$det_obj->database->get_one_row("SELECT MIN(DET_HOUR_TO) AS MINDET
		FROM ".DETENTION_RATE." JOIN  ".DETENTION_MASTER."
		ON ".DETENTION_MASTER.".DETENTION_ID=".DETENTION_RATE.".DETENTION_ID
		WHERE CLIENT_ID=".$res['client_id']."
		AND USER_TYPE='client'
		AND DET_RATE = 0.00");
	
	if(count($free_hours)>0 )
	{
		if($free_hours['MINDET']!="" && $free_hours['MINDET']!="0")
		{
			$res['det_free_hr']=$free_hours['MINDET'];
		}
	}
	$det_id=$det_master->database->get_one_row("SELECT * FROM ".DETENTION_MASTER."
		WHERE CLIENT_ID='".$res['client_id']."'
		AND USER_TYPE='client' ");
	if(is_array($det_id) && count($det_id)>0 && $det_id['DETENTION_ID']!="") {
		$det_ratess=$det_obj->database->get_multiple_rows("SELECT *
			FROM ".DETENTION_RATE."
			WHERE DETENTION_ID=".$det_id['DETENTION_ID']."
			AND (".$loaded_detention_hrs." BETWEEN DET_HOUR AND DET_HOUR_TO)");
		
		if(count($det_ratess)>0 && $det_ratess[0]['DET_RATE']!="") {
			if($loaded_detention_hrs>$res['det_free_hr']) {
				$new_det_hr=$loaded_detention_hrs-$res['det_free_hr'];
			}
			$loaded_perhr_rate=$det_ratess[0]['DET_RATE'];
			$loaded_detention_Rate=$loaded_perhr_rate*$new_det_hr;
		}
	}
	$res['loaded_detention_Rate']=$loaded_detention_Rate;
	$res['loaded_perhr_rate']=$loaded_perhr_rate;
	
	//! fetch free unloaded detention hours ------##
	$res['un_det_free_hr']=$unloaded_perhr_rate=$new_undet_hr=$unloaded_detention_Rate=0;
	$un_free_hours=$undet_obj->database->get_one_row(
		"SELECT MIN(UNDET_HR_TO) AS MIN_DET, ".UNLOADED_DETENTION_RATE.".DETENTION_ID
		FROM ".UNLOADED_DETENTION_RATE." JOIN  ".DETENTION_MASTER."
		ON ".DETENTION_MASTER.".DETENTION_ID=".UNLOADED_DETENTION_RATE.".DETENTION_ID
		WHERE CLIENT_ID=".$client_id['CLIENT_CODE']."
		AND USER_TYPE='client'
		AND UN_DET_RATE = 0.00");
	
	
	if(count($un_free_hours)>0 &&  isset($un_free_hours['DETENTION_ID']))
	{
		if($un_free_hours['MIN_DET']!="" && $un_free_hours['MIN_DET']!="0")
		{
			$res['un_det_free_hr']=$un_free_hours['MIN_DET'];
		}
	}
	
	if(is_array($det_id) && count($det_id)>0 && $det_id['DETENTION_ID']!="") {
			$unload_det_rate=$undet_obj->database->get_multiple_rows(
				"SELECT * FROM ".UNLOADED_DETENTION_RATE."
				WHERE DETENTION_ID=".$det_id['DETENTION_ID']."
				AND (".$unloaded_detention_hrs." BETWEEN UNDET_HR_FROM AND UNDET_HR_TO)");
	
		
		if(count($unload_det_rate)>0 && $unload_det_rate[0]['UN_DET_RATE']!="") {
			if($unloaded_detention_hrs>$res['un_det_free_hr'])
			{$new_undet_hr=$unloaded_detention_hrs-$res['un_det_free_hr'];}
			$unloaded_perhr_rate=$unload_det_rate[0]['UN_DET_RATE'];
			$unloaded_detention_Rate=$unloaded_perhr_rate*$new_undet_hr;
		}
	}
	$res['unloaded_detention_Rate']=$unloaded_detention_Rate;
	$res['unloaded_perhr_rate']=$unloaded_perhr_rate;
	
	
	$res['shipment_id']=$_GET['id'];
	
	//$res['client_id']=$client_id['CLIENT_CODE']; 
	if(isset($res['shipment_details']['CURRENT_STATUS'])&& $res['shipment_details']['CURRENT_STATUS']!="") {
		$res['status'] = $shipment_table->state_name[$res['shipment_details']['CURRENT_STATUS']];
	} else {
		$res['status'] =array();
	}
	
	$res['bstatus'] = $shipment_table->billing_state_behavior[$res['shipment_details']['BILLING_STATUS']];

	//! Duncan - SCR 34 - include SS_NUMBER
	if( $multi_company )
		$res['SS_NUMBER'] = $res['shipment_details']['SS_NUMBER'];
	else
		$res['SS_NUMBER'] = false;	

	//! Duncan - SCR 232 - log hours
	$res['LOG_HOURS'] = $sts_log_hours;
	
	//! SCR# 702 - Get equipment required for a shipment
	$res['EQUIPMENT'] = $shipment_table->get_equipment_req( $shipment_id );

	$res['FSC_4DIGIT']=$sts_fsc_4digit;
	if( $sts_debug ) {
		echo "<pre>Duncan: check FSC2\n";
		var_dump($res['fuel_rate']);
		echo "</pre>";
	}
	
	$res_obj->render_client_pay_screen($res);
}
else
{
	echo '<script>window.location.href="exp_listclient.php"</script>';
}
?>
</div>
<?php
	if( $sts_debug ) {
		echo "<pre>Duncan: check RES\n";
		var_dump($res);
		echo "</pre>";
	}

require_once( "include/footer_inc.php" );
?>
<script type="text/javascript" language="javascript">

function add_rates(curr_val)
{
	var rates=document.getElementsByName('RATE[]');
	var len=rates.length;
	var total=0;
	
	/*if(curr_val=="")
	{
		alert("Rate can not be empty.");
		return false;
	}
	else if(isNaN(curr_val))
	{
		alert("Rate should be numeric.");
		return false;
	}
	else if(len>0)
	{
		for(var i=0;i<len;i++)
		{
			total=parseFloat(total)+parseFloat(rates[i].value);
		}
		document.getElementById('TOTAL_RATE').value=total;
	}*/
	if(len>0)
	{
		for(var i=0;i<len;i++)
		{
		if(rates[i].value=="")
		{
		alert("Rate can not be empty.");
		}
		else if(isNaN(rates[i].value))
		{
		alert("Rate should be numeric.");
		
		}
		else
		{total=parseFloat(total)+parseFloat(rates[i].value);}
		
		}
		document.getElementById('TOTAL_RATE').value=total;
		calculate_total();
		return false;
	}
	
}

function calculate_total()
{
	var handling=document.getElementById('HAND_CHARGES').value;
	var extra=document.getElementById('EXTRA_CHARGES').value;
	var freight=document.getElementById('FREIGHT_CHARGES').value;
	var detention=document.getElementById('DETENTION_RATE').value;
	var un_detention=document.getElementById('UNLOADED_DETENTION_RATE').value;
	//var carrier=document.getElementById('CARRIER').value;
	//var cod=document.getElementById('COD').value; 
	var rpm=document.getElementById('RPM').value;
	var fuel_cost=document.getElementById('FUEL_COST').value;
	var client_total_check = document.getElementById('TOTAL_RATE');
	if(typeof client_total_check !== 'undefined' && client_total_check !== null) {
    	var client_total=document.getElementById('TOTAL_RATE').value;
    } else {
	    var client_total=0;
    }
	var hand_pallet=document.getElementById('HAND_PALLET').value;
	var pal_rate=document.getElementById('PALLETS_RATE').value;
	var rpm_Rate=document.getElementById('RPM_Rate').value;
	var stop_off=document.getElementById('STOP_OFF').value;
	var adjustment_charge=document.getElementById('ADJUSTMENT_CHARGE').value;
	var weekend=document.getElementById('WEEKEND').value;
	var selection=document.getElementById('SELECTION_FEE').value;
	var discount=document.getElementById('DISCOUNT').value;
	var col_type=document.getElementById('fsc_col_per_mile');
	
	var total=0;
	if(handling=="") {handling=0;}
	if(extra=="") {extra=0;}
	if(freight=="") {freight=0;}
	if(detention=="") {detention=0;}
	if(un_detention=="") {un_detention=0;}
	/*if(carrier=="")
	{carrier=0;}
	if(cod=="")
	{cod=0;}*/
	if(rpm=="")
	{rpm=0;}
	if(fuel_cost=="")
	{fuel_cost=0;}
	if(client_total=="")
	{client_total=0;}
	if(pal_rate=="")
	{pal_rate=0;}
	if(hand_pallet=="")
	{hand_pallet=0;}
	if(rpm_Rate=="")
	{rpm_Rate=0;}
	if(stop_off=="")
	{stop_off=0;}
    if(adjustment_charge=="")
	{adjustment_charge=0;}
    if(weekend=="")
	{weekend=0;}
    if(selection=="")
	{selection=0;}
    if(discount=="")
	{discount=0;}

	//! Calculate FSC - Percentage
	//! SCR# 313 - added freightmiles option
	var fscpf = '<?php echo $sts_fsc_percent_freight ?>';
	if(col_type.value=='Percent') {
		var pct=document.getElementById('FUEL_AVG_RATE').value;
		var subtotal=parseFloat(handling)+parseFloat(extra)+parseFloat(freight)+parseFloat(detention)+parseFloat(un_detention)+parseFloat(pal_rate)+parseFloat(hand_pallet)+parseFloat(rpm_Rate)+parseFloat(stop_off)+parseFloat(adjustment_charge)+parseFloat(weekend)+parseFloat(client_total)+parseFloat(selection)-parseFloat(discount);
		var fc;
		console.log( 'calculate_total: percent', fscpf, freight, rpm_Rate, pct );
		if( fscpf == 'true' || fscpf == 'justfreight' ) {
			fc = Math.round((parseFloat(freight) * parseFloat(pct) / 100)*100)/100;
			document.getElementById('FUEL_COST').value = fc.toFixed(2);
			total = subtotal + fc;
		} else if( fscpf == 'false' || fscpf == 'everything' ) {
			fc = Math.round((subtotal * parseFloat(pct) / 100)*100)/100;
			document.getElementById('FUEL_COST').value = fc.toFixed(2);
			total = subtotal + fc;
		} else { //freightmiles
			fc = Math.round(((parseFloat(freight)+parseFloat(rpm_Rate)) * parseFloat(pct) / 100)*100)/100;
			document.getElementById('FUEL_COST').value = fc.toFixed(2);
			total = subtotal + fc;
		}
		console.log( 'calculate_total: fc = ', fc);
		
	} else {
		console.log( 'calculate_total: not percent', 'handling=', handling, 'extra=', extra, 'freight=', freight, 'detention=', detention, 'un_detention=', un_detention, 'fuel_cost=', fuel_cost, 'pal_rate=', pal_rate, 'hand_pallet=', hand_pallet, 'rpm_Rate=', rpm_Rate, 'stop_off=', stop_off, 'adjustment_charge=', adjustment_charge, 'weekend=', weekend, 'client_total=', client_total, 'selection=', selection, 'discount=', discount);
	total=parseFloat(handling)+parseFloat(extra)+parseFloat(freight)+parseFloat(detention)+parseFloat(un_detention)+parseFloat(fuel_cost)+parseFloat(pal_rate)+parseFloat(hand_pallet)+parseFloat(rpm_Rate)+parseFloat(stop_off)+parseFloat(adjustment_charge)+parseFloat(weekend)+parseFloat(client_total)+parseFloat(selection)-parseFloat(discount);
		
	}
	
	document.getElementById('TOTAL').value=parseFloat(Math.round(total*100)/100).toFixed(2);
	console.log('calculate_total: DOM total = ', document.getElementById('TOTAL').value);
}

function chk_confirmation()
{
	var handling=document.getElementById('HAND_CHARGES').value;
	var extra=document.getElementById('EXTRA_CHARGES').value;
	//var carrier=document.getElementById('CARRIER').value;
	//var cod=document.getElementById('COD').value; 
	var pal_rate=document.getElementById('PALLETS_RATE').value;
	var rpm=document.getElementById('RPM').value;
	var fuel_cost=document.getElementById('FUEL_COST').value;
	var det_hrs=document.getElementById('DETENTION_HOUR').value;
	var det_rate=document.getElementById('DETENTION_RATE').value;
	
	if(handling=="" && extra=="" && rpm=="" && fuel_cost=="" && pal_rate=="" && det_rate=="")
	{
	var msg="Do you really want to save client billing details?If yes,your all empty fields will be stored as 0 ";
	}
	else
	{
		var msg="Do you really want to save client billing details?";
	}
	if(confirm(msg))
	{
		return true;
	}
	else
	{
		return false;
	}
}

function calc_det_rate(client_id,cons_name)
{
	var det_hr=document.getElementById('DETENTION_HOUR').value;
	if(det_hr=="")
	{
		alert("Enter valid detention hours");
		document.getElementById('DETENTION_RATE').value=0;
		return false;
	}
	else if(isNaN(det_hr))
	{
		alert("Enter valid detention hours.");
		return false;
	}
	else
	{var ajreq;
	   $('#det_loader').show();
		 ajreq=$.ajax({
			url:'exp_save_rates.php?action=caldetentionrate&client_id='+client_id+'&det_hr='+det_hr+'&cons_name='+encodeURIComponent(cons_name),
			type:'POST',
			success:function(res)
			{ 
			  var x=JSON.parse(res);
			 
			   if(x.ans=='yes')
			   {var min_rate=x.mindetention;
			   
				   if(x.mindetention>0)
				   {
				     min_rate=parseFloat(x.mindetention);
				   }
				   var new_det_hr=parseFloat(det_hr)-parseFloat(min_rate);
				   
				if(new_det_hr<0)
				{var new_det_hr=det_hr;}
				var calc=parseFloat(x.detrate)*parseFloat(new_det_hr);
				if(!isNaN(calc))
			      {
					  document.getElementById('DETENTION_RATE').value=Math.round((parseFloat(x.detrate)*parseFloat(new_det_hr))*100)/100;
				  }
				  else
				  {document.getElementById('DETENTION_RATE').value=0;}
				document.getElementById('FREE_DETENTION_HOUR').value=min_rate;
				if(x.detrate!="" && (!isNaN(x.detrate)))
				{document.getElementById('RATE_PER_HOUR').value=x.detrate;}
				else
				{document.getElementById('RATE_PER_HOUR').value=0;}
				
			   }
			   else
			   {
				   var min_rate=x.mindetention;
				   if(min_rate!="")
				   {document.getElementById('FREE_DETENTION_HOUR').value=min_rate;}
				   else
				   {document.getElementById('FREE_DETENTION_HOUR').value=0;}
				  document.getElementById('DETENTION_RATE').value=0;
				  document.getElementById('RATE_PER_HOUR').value=0;
			   }
			   
				 $('#det_loader').hide();
				  calculate_total();
			}
		});
	//alert('**'+ajreq.readyState);
/*	if(ajreq && ajreq.readyState !== 4){console.log("abort");
            ajreq.abort();}	*/
	}
}

function calc_unloaded_det_rate(client_id,cons_name)
{
	var det_hr=document.getElementById('UNLOADED_DETENTION_HOUR').value;
	//alert(det_hr);
	if(det_hr==" ")
	{
		alert("Enter valid detention hours");
		document.getElementById('UNLOADED_DETENTION_RATE').value=0;
		return false;
	}
	else if(isNaN(det_hr))
	{
		alert("Enter valid detention hours.");
		return false;
	}
	else
	{//alert('(');
	   $('#det_loader').show();
		$.ajax({
			url:'exp_save_rates.php?action=calunloadeddetentionrate&client_id='+client_id+'&un_det_hr='+det_hr+'&cons_name='+encodeURIComponent(cons_name),
			type:'POST',
			success:function(res)
			{
				var x=JSON.parse(res);
			    if(x.ans=='yes')
			     {
					 var min_rate=x.mindetention;
				   if(x.mindetention>0)
				   {
				     min_rate=parseFloat(x.mindetention);
				   }
				
				   var new_det_hr=parseFloat(det_hr)-parseFloat(min_rate);
				    if(new_det_hr<0)
				   {var new_det_hr=det_hr;}
				   var calc=parseFloat(x.detrate)*parseFloat(new_det_hr);
				   if(!isNaN(calc))
				   {document.getElementById('UNLOADED_DETENTION_RATE').value=Math.round(calc*100)/100;}
				   else
				   {document.getElementById('UNLOADED_DETENTION_RATE').value=0;}
				    document.getElementById('FREE_UN_DETENTION_HOUR').value=min_rate; 
					if(x.detrate!="" && (!isNaN(x.detrate)))
					{document.getElementById('UN_RATE_PER_HOUR').value=x.detrate; }
					else
					{document.getElementById('UN_RATE_PER_HOUR').value=0;}
			   }
			   else
			   {
				   var min_rate=x.mindetention;
				   if(min_rate!="")
				   {document.getElementById('FREE_UN_DETENTION_HOUR').value=min_rate;}
				   else
				   {document.getElementById('FREE_UN_DETENTION_HOUR').value=0;}
				  document.getElementById('UNLOADED_DETENTION_RATE').value=0;
				  document.getElementById('UN_RATE_PER_HOUR').value=0; 
			   }
				//var det_rate=res.trim();
				//document.getElementById('UNLOADED_DETENTION_RATE').value=parseFloat(det_rate)*parseFloat(det_hr);
				 $('#det_loader').hide();
				  calculate_total();
			}
		});
	}
}

function calc_unloaded_rate_per_hr(client_id,cons_name)
{
	var rate_per_hr=$('#UN_RATE_PER_HOUR').val();
	var det_hr=document.getElementById('UNLOADED_DETENTION_HOUR').value;
	if(rate_per_hr.trim()!="")
	{
	$('#det_loader').show();
	$.ajax({
		url:'exp_save_rates.php?action=calunloadeddetentionrate&client_id='+client_id+'&un_det_hr='+det_hr+'&cons_name='+encodeURIComponent(cons_name),
		type:'POST',
		success:function(res)
		{
			var x=JSON.parse(res);
		    if(x.ans=='yes')
		     {
				 var min_rate=x.mindetention;
			   if(x.mindetention>0)
			   {
			     min_rate=parseFloat(x.mindetention);
			   }
			
			   var new_det_hr=parseFloat(det_hr)-parseFloat(min_rate);
			    if(new_det_hr<0)
			   {var new_det_hr=det_hr;}
			   var calc=parseFloat(rate_per_hr)*parseFloat(new_det_hr);
			   if(!isNaN(calc))
			   {document.getElementById('UNLOADED_DETENTION_RATE').value=Math.round(calc*100)/100;}
			   else
			   {document.getElementById('UNLOADED_DETENTION_RATE').value=0;}
			    document.getElementById('FREE_UN_DETENTION_HOUR').value=min_rate; 
// 				if(x.detrate!="" && (!isNaN(x.detrate)))
// 				{document.getElementById('UN_RATE_PER_HOUR').value=x.detrate; }
// 				else
// 				{document.getElementById('UN_RATE_PER_HOUR').value=0;}
		   }
		   else
		   {
			   var min_rate=x.mindetention;
			   if(min_rate!="")
			   {document.getElementById('FREE_UN_DETENTION_HOUR').value=min_rate;
				alert('Unloaded Detention hours you entered are out of range.');
				$('#UNLOADED_DETENTION_RATE').focus();}
			   else
			   {document.getElementById('FREE_UN_DETENTION_HOUR').value=0;}
			  document.getElementById('UNLOADED_DETENTION_RATE').value=0;
			  document.getElementById('UN_RATE_PER_HOUR').value=0; 
		   }
			//var det_rate=res.trim();
			//document.getElementById('UNLOADED_DETENTION_RATE').value=parseFloat(det_rate)*parseFloat(det_hr);
			 $('#det_loader').hide();
			  calculate_total();
		}
	});
	}
}

function calculate_rpm_amount() {
	calculate_rpm_amounta();
	calculate_rpm_amountb();
}

function calculate_rpm_amounta() {
	var rpm=document.getElementById('RPM').value;
	var mil=document.getElementById('MILLEAGE').value;
	var ans=0;
	if(rpm=="")
	{rpm="0";}
	if(mil=="")
	{mil="0";}
	if(rpm!="" && mil!="")
	{
		ans=Math.round((parseFloat(rpm)*parseFloat(mil))*100)/100;
		if(!isNaN(ans))
		{document.getElementById('RPM_Rate').value=ans;}
		else
		{document.getElementById('RPM_Rate').value=0;}
	}
	calculate_total();
}

function calculate_rpm_amountb() {
	var rpm=document.getElementById('RPM').value;
	var mil=document.getElementById('MILLEAGE').value;
	var ans=0;
	if(rpm=="")
	{rpm="0";}
	if(mil=="")
	{mil="0";}
	var col_type=document.getElementById('fsc_col_per_mile');
	
	if(col_type.value=='Per Mile Adjust')
	{
		var per_mile=document.getElementById('FUEL_AVG_RATE').value;
		var per_mile_adjust=parseFloat(per_mile)*parseFloat(mil);
		console.log( 'calculate_rpm_amountb: ', col_type.value, per_mile, per_mile_adjust );
		if(!isNaN(per_mile_adjust)) {
			document.getElementById('FUEL_COST').value=Math.round(per_mile_adjust * 100)/100;
		} else {
			document.getElementById('FUEL_COST').value=0;
		}
	}
	calculate_total();
}

function calculate_fuel_charge()
{
	var avg=document.getElementById('FUEL_AVG_RATE');
	console.log( 'calculate_fuel_charge: ', avg.value );
	if(!isNaN(avg.value)) {
		document.getElementById('FUEL_COST').value=Math.round(avg.value*100)/100;
	}
	calculate_total();
}

function chk_confirm_approve(shipment_id)
{
	if(confirm("Do you really want to approve this for billing?\nThis sends the bill to <?php echo $sts_destination; ?>.\nThere is no undo."))
	{
		return true;
	}
	else
	{
		return false;
	}
}

function calc_det_rate_per_hr(client_id,cons_name)
{
	var rate_per_hr=$('#RATE_PER_HOUR').val();
	var det_hr=document.getElementById('DETENTION_HOUR').value;
	  $('#det_loader').show();
		 ajreq=$.ajax({
			url:'exp_save_rates.php?action=caldetentionrate&client_id='+client_id+'&det_hr='+det_hr+'&cons_name='+encodeURIComponent(cons_name),
			type:'POST',
			success:function(res)
			{ 
			  var x=JSON.parse(res);
			 
			   if(x.ans=='yes')
			   {var min_rate=x.mindetention;
			   
				   if(x.mindetention>0)
				   {
				     min_rate=parseFloat(x.mindetention);
				   }
				   var new_det_hr=parseFloat(det_hr)-parseFloat(min_rate);
				   
				if(new_det_hr<0)
				{var new_det_hr=det_hr;}
				var calc=parseFloat(rate_per_hr)*parseFloat(new_det_hr);
				if(!isNaN(calc))
			      {
					  document.getElementById('DETENTION_RATE').value=Math.round((parseFloat(rate_per_hr)*parseFloat(new_det_hr))*100)/100;
				  }
				  else
				  {document.getElementById('DETENTION_RATE').value=0;}
				document.getElementById('FREE_DETENTION_HOUR').value=min_rate;
// 				if(x.detrate!="" && (!isNaN(x.detrate)))
// 				{document.getElementById('RATE_PER_HOUR').value=x.detrate;}
// 				else
// 				{document.getElementById('RATE_PER_HOUR').value=0;}
				
			   }
			   else
			   {
				   var min_rate=x.mindetention;
				   if(min_rate!="")
				   {document.getElementById('FREE_DETENTION_HOUR').value=min_rate;
					alert('Loaded Detention hours you entered are out of range.');
					$('#DETENTION_RATE').focus();}
				   else
				   {document.getElementById('FREE_DETENTION_HOUR').value=0;}
				  document.getElementById('DETENTION_RATE').value=0;
				  document.getElementById('RATE_PER_HOUR').value=0;
			   }
			   
				 $('#det_loader').hide();
				  calculate_total();
			}
		});
}



function cal_pallet_rate(client_id,cons_name,cons_city)
{
	var pallets=$('#PALLETS').val();
	if(pallets.trim()=="")
	{
		alert("Please Enter  Pallets");
		return false;
	}
	else if( isNaN(pallets.trim()))
	{
		alert("Please Enter  Valid Pallets");
		return false;
	}
	else
	{
		 $('#det_loader').show();
		$.ajax({
			url:'exp_ajax_functions.php?action=calpalletrate&client_id='+client_id+'&pallets='+pallets+'&cons_name='+encodeURIComponent(cons_name)+'&cons_city='+encodeURIComponent(cons_city),
			type:'POST',
			success:function(res)
			{
				var x=JSON.parse(res);
				if(x.ans=="yes")
				{  
					if(!isNaN(x.per_pallet_rate))
					{ document.getElementById('PER_PALLETS').value=Math.round(x.per_pallet_rate*100)/100;}
					else
					{document.getElementById('PER_PALLETS').value=0;}
					if(!isNaN(x.pallet_rate))
					 {document.getElementById('PALLETS_RATE').value=Math.round(x.pallet_rate*100)/100;}
					else
					{document.getElementById('PALLETS_RATE').value=0;}
				}
				else
				{
					 document.getElementById('PER_PALLETS').value=0;
					 document.getElementById('PALLETS_RATE').value=0;
				}
				 $('#det_loader').hide();
				  calculate_total();
			}
		});
	}
}

function cal_pallet_per_rate(client_id,cons_name,cons_city)
{
	var pallets=$('#PALLETS').val();
	var pallet_rate=$('#PER_PALLETS').val();
	 var numbers = /^[0-9.]+$/;  
	if(pallets.trim()=="")
	{
		alert("Please Enter  Pallets");
		return false;
	}
	else if( isNaN(pallets.trim()))
	{
		alert("Please Enter  Valid Pallets");
		return false;
	}
	if(pallet_rate.trim()=="")
	{
		alert("Please Enter  Pallet Rate");
		return false;
	}
	else if(!pallet_rate.match(numbers))
	{
		alert("Please Enter  Valid Pallet Rate");
		return false;
	}
	else
	{
		 $('#det_loader').show();
		$.ajax({
			url:'exp_ajax_functions.php?action=calpallet_perrate&client_id='+client_id+'&pallets='+pallets+'&cons_name='+encodeURIComponent(cons_name)+'&pallet_rate='+pallet_rate+'&cons_city='+encodeURIComponent(cons_city),
			type:'POST',
			success:function(res)
			{
				var x=JSON.parse(res);
				if(x.ans=="yes")
				{  
					if(!isNaN(x.per_pallet_rate))
					{ document.getElementById('PER_PALLETS').value=Math.round(x.per_pallet_rate*100)/100;}
					else
					{document.getElementById('PER_PALLETS').value=0;}
					if(!isNaN(x.pallet_rate))
					 {document.getElementById('PALLETS_RATE').value=Math.round(x.pallet_rate*100)/100;}
					else
					{document.getElementById('PALLETS_RATE').value=0;}
				}
				else
				{
					 $('#PALLETS_RATE').focus();
					 alert('Pallets you entered are out of range.');
					 document.getElementById('PER_PALLETS').value=0;
					 document.getElementById('PALLETS_RATE').value=0;
				}
				 $('#det_loader').hide();
				  calculate_total();
			}
		});
	}
}

function upd_bol_number(shipment_code)
{
	$.ajax({
		url:'exp_ajax_functions.php?action=upd_bol_number&shipment='+shipment_code+'&bol='+encodeURIComponent($('#BOL_NUMBER').val()),
		type:'POST',
		dataType: "json",
		success:function(data) {
			if( data[0]["DUPS"] > 0 ) {
			$('#BOL_NUMBER').after('<span class="input-group-addon" id="BOL_DUP"><span class="glyphicon glyphicon-warning-sign inform  text-danger" data-placement="bottom" data-toggle="popover" data-content="Duplicate BOL number"></span></span>');
			} else {
				$('#BOL_DUP').remove();
			}

			$('.inform').popover({ 
				placement: 'top',
				html: 'true',
				container: 'body',
				trigger: 'hover',
				delay: { show: 50, hide: 3000 },
				title: '<strong>Information</strong> <button type="button" class="close" data-hide="confirm" data-delay="0" aria-hidden="true">&times;</button>' 
			});
		}
	});
}
$(document).ready( function () {
	upd_bol_number(<?php echo $shipment_id; ?>);
	calculate_total();
});


</script>


