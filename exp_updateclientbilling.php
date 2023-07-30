<?php 

// $Id: exp_updateclientbilling.php 5030 2023-04-12 20:31:34Z duncan $
// Edit client billing

// Set flag that this is a parent file
define( '_STS_INCLUDE', 1 );

// Setup Session
require_once( "include/sts_session_setup.php" );

require_once( "include/sts_config.php" );
$sts_debug = isset($_GET['debug']) && in_group( EXT_GROUP_DEBUG );
require_once( "include/sts_session_class.php" );

$my_session = sts_session::getInstance( $exspeedite_db, $sts_debug );
$my_session->access_check( $sts_table_access[SHIPMENT_TABLE] );	// Make sure we should be here

$sts_subtitle = "Client Bill";
require_once( "include/header_inc.php" );
require_once( "include/navbar_inc.php" );

?>
<div class="container-full" role="main">
<?php
require_once( "include/sts_result_class.php" );
require_once( "include/sts_shipment_class.php" );
require_once( "include/sts_setting_class.php" );
require_once( "include/sts_clientbilling.php" );

$setting_table = sts_setting::getInstance($exspeedite_db, $sts_debug);
$multi_company = $setting_table->get("option", "MULTI_COMPANY") == 'true';
$sts_log_hours = $setting_table->get( 'option', 'BILLING_LOG_HOURS' ) == 'true';

$sql_obj	=	new sts_table($exspeedite_db , SHIPMENT_TABLE , $sts_debug);
$bill_obj=new sts_table($exspeedite_db , CLIENT_BILL , $sts_debug);
$bill_rate=new sts_table($exspeedite_db , CLIENT_BILL_RATES , $sts_debug);
$client_master=new sts_table($exspeedite_db , CLIENT_TABLE , $sts_debug);
$stop_obj=new sts_table($exspeedite_db , STOP_TABLE , $sts_debug);

$shipment_table = sts_shipment::getInstance($exspeedite_db, $sts_debug);
$rslt = new sts_result( $shipment_table, false, $sts_debug );

$shipment_id=$_GET['id'];
if($shipment_id!="") {
	if(isset($_POST['update_client_bill'])) {
		$client_id=$bill_obj->database->get_one_row("SELECT * FROM ".CLIENT_BILL." WHERE SHIPMENT_ID=".$shipment_id);
		//print_r($client_id); echo $client_id['CLIENT_BILLING_ID'];exit;
		$hazmat=$temp=$pallets=$pallets_rate=$detention_hr=$un_detention_hr=$mil=$rpm_mil=$free_det_hr=$free_un_det_hr=$rate_per_hr=$un_rate_per_hr=$stop_off=$weekend=$adjust_value=$selection_fee=$discount=0;
			$handling_charge=$extra_charge=$carrier=$cod=$rpm=$so_rate=$fuel_cost=$un_detention=$per_pall_rate=$fsc_avg_col=$fsc_avg=$hand_pallet=$not_billed_hours=0;
			$stop_off_note=$adjust_title=$extra_charges_note='';
			$rate=$code=$category=$cod_id=array();
			if(isset($_POST['PALLETS']))
				$pallets=intval($_POST['PALLETS']);
			if(isset($_POST['PER_PALLETS']))
				$per_pall_rate=floatval($_POST['PER_PALLETS']);
			if(isset($_POST['PALLETS_RATE']))
				$pallets_rate=floatval($_POST['PALLETS_RATE']);
			if(isset($_POST['HAND_CHARGES']))
				$handling_charge=floatval($_POST['HAND_CHARGES']);
			if(isset($_POST['FREIGHT_CHARGES']))
				$freight=floatval($_POST['FREIGHT_CHARGES']);
			if(isset($_POST['FREE_DETENTION_HOUR']))
				$free_det_hr=floatval($_POST['FREE_DETENTION_HOUR']);
			if(isset($_POST['DETENTION_HOUR']))
				$detention_hr=floatval($_POST['DETENTION_HOUR']);
			if(isset($_POST['RATE_PER_HOUR']))
				$rate_per_hr=floatval($_POST['RATE_PER_HOUR']);
			if(isset($_POST['DETENTION_RATE']))
				$detention=floatval($_POST['DETENTION_RATE']);
			if(isset($_POST['UNLOADED_DETENTION_HOUR']))
				$un_detention_hr=floatval($_POST['UNLOADED_DETENTION_HOUR']);
			if(isset($_POST['UNLOADED_DETENTION_RATE']))
				$un_detention=floatval($_POST['UNLOADED_DETENTION_RATE']);
			if(isset($_POST['UN_RATE_PER_HOUR']))
				$un_rate_per_hr=floatval($_POST['UN_RATE_PER_HOUR']);
			if(isset($_POST['FREE_UN_DETENTION_HOUR']))
				$free_un_det_hr=floatval($_POST['FREE_UN_DETENTION_HOUR']);

			if(isset($_POST['EXTRA_CHARGES']))
				$extra_charge=floatval($_POST['EXTRA_CHARGES']);
			
			//! SCR# 406 - truncate the field rather than fail.
			if(! empty($_POST['EXTRA_CHARGES_NOTE']))
				$extra_charges_note=
					$bill_obj->trim_to_fit('EXTRA_CHARGES_NOTE', $_POST['EXTRA_CHARGES_NOTE']);

			if(isset($_POST['CARRIER']))
				$carrier=floatval($_POST['CARRIER']);
			if(isset($_POST['COD']))
				$cod=floatval($_POST['COD']);
			if(isset($_POST['CODE_ID']))
				$cod_id=$_POST['CODE_ID'];
			if(isset($_POST['RPM']))
				$rpm=floatval($_POST['RPM']);
			if(isset($_POST['SO_RATE']))
				$so_rate=floatval($_POST['SO_RATE']);
			if(isset($_POST['FUEL_COST']))
				$fuel_cost=floatval($_POST['FUEL_COST']);
			if(isset($_POST['MILLEAGE']))
				$mil=floatval($_POST['MILLEAGE']);
			if(isset($_POST['RPM_MILLEAGE']))
				$rpm_mil=floatval($_POST['RPM_MILLEAGE']);
			if(isset($_POST['RATE']))
				$rate=$_POST['RATE'];
			if(isset($_POST['CODE']))
				$code=$_POST['CODE'];
			if(isset($_POST['CATEGORY']))
				$category=$_POST['CATEGORY'];
			if(isset($_POST['HAZMAT']))
				$hazmat=intval($_POST['HAZMAT']);
		  
			if(isset($_POST['TEMP_CONTROLL']))
				$temp=intval($_POST['TEMP_CONTROLL']);
			
			if(isset($_POST['FSC_AVERAGE_RATE']))
				$fsc_avg=floatval($_POST['FSC_AVERAGE_RATE']);
			
			if(isset($_POST['FSC_AVERAGE_RATE_COL']))
				$fsc_avg_col=$_POST['FSC_AVERAGE_RATE_COL'];
			
			if(isset($_POST['STOP_OFF']))
				$stop_off=floatval($_POST['STOP_OFF']);
		   	if(isset($_POST['WEEKEND']))
		   		$weekend=floatval($_POST['WEEKEND']);
			$total=floatval($_POST['TOTAL']);
			
			if(isset($_POST['STOP_OFF_NOTE']))
				$stop_off_note=$_POST['STOP_OFF_NOTE'];
			if(isset($_POST['HAND_PALLET']))
				$hand_pallet=intval($_POST['HAND_PALLET']);
			
			//! SCR# 406 - truncate the field rather than fail.
			if(isset($_POST['ADJUSTMENT_CHARGE_TITLE']))
				$adjust_title=
					$bill_obj->trim_to_fit('ADJUSTMENT_CHARGE_TITLE', $_POST['ADJUSTMENT_CHARGE_TITLE']);
			
			if(isset($_POST['ADJUSTMENT_CHARGE']))
				$adjust_value=floatval($_POST['ADJUSTMENT_CHARGE']);
			if(isset($_POST['SELECTION_FEE']))
				$selection_fee=floatval($_POST['SELECTION_FEE']);
			if(isset($_POST['DISCOUNT']))
				$discount=floatval($_POST['DISCOUNT']);
			
			$currency = 'USD';
			if(isset($_POST['CURRENCY']))
				$currency=$_POST['CURRENCY'];
				
			$terms = 0;
			if(isset($_POST['TERMS']))
			{$terms=$_POST['TERMS'];}			
			
			$changes = array('PALLETS'=>$pallets, 'PER_PALLETS'=>$per_pall_rate,
				'PALLETS_RATE'=>$pallets_rate, 'HAND_CHARGES'=>$handling_charge,
				'FREIGHT_CHARGES'=>$freight, 'FREE_DETENTION_HOUR'=>$free_det_hr,
				'DETENTION_HOUR'=>$detention_hr, 'DETENTION_RATE'=>$detention,
				'UNLOADED_DETENTION_HOUR'=>$un_detention_hr,
				'UNLOADED_DETENTION_RATE'=>$un_detention,
				'EXTRA_CHARGES'=>$extra_charge,
				'EXTRA_CHARGES_NOTE'=>$bill_obj->real_escape_string($extra_charges_note),
				'CARRIER'=>$carrier,
				'COD'=>$cod, 'RPM'=>$rpm, 'SO_RATE'=>$so_rate, 'FUEL_COST'=>$fuel_cost,
				'MILLEAGE'=>$mil, 'RPM_RATE'=>$rpm_mil, 'HAZMAT'=>$hazmat,
				'TEMP_CONTROLLED'=>$temp,
				'TOTAL'=>$total, 'FREE_UN_DETENTION_HOUR'=>$free_un_det_hr,
				'RATE_PER_HOUR'=>$rate_per_hr, 'UN_RATE_PER_HOUR'=>$un_rate_per_hr,
				'WEEKEND'=>$weekend, 'STOP_OFF'=>$stop_off,
				'STOP_OFF_NOTE'=>$bill_obj->real_escape_string($stop_off_note),
				'HAND_PALLET'=>$hand_pallet,
				'ADJUSTMENT_CHARGE_TITLE'=>$bill_obj->real_escape_string($adjust_title),
				'ADJUSTMENT_CHARGE'=>$adjust_value,
				'SELECTION_FEE'=>$selection_fee, 'DISCOUNT'=>$discount,
				'CURRENCY' => $currency,
				'TERMS' => $terms);
				
			//! Duncan - SCR 232 - log hours
			if($sts_log_hours && ! empty($_POST['NOT_BILLED_HOURS']))
				$changes['NOT_BILLED_HOURS'] = floatval($_POST['NOT_BILLED_HOURS']);
			
			$bill_id=$bill_obj->update($client_id['CLIENT_BILLING_ID'], $changes);
			
			if($bill_id!="" && (count($cod_id)>0))
			{
				//$hand_sql_obj->database->get_multiple_rows("DELETE FROM ".CLIENT_BILL_RATES." WHERE BILLING_ID='".$client_id[0]['CLIENT_BILLING_ID']."' ");
				for($i=0;$i<count($cod_id);$i++)
				{
					//$bill_rate->add_row("BILLING_ID,RATE_CODE,CATEGORY,RATES","'".$client_id[0]['CLIENT_BILLING_ID']."','".$code[$i]."','".$category[$i]."','".$rate[$i]."'");
					
					$bill_rate->update($cod_id[$i],array('RATES'=>floatval($rate[$i])));
					
				}
			}
			
		//! SCR# 719 - log financial details
		log_financial( $shipment_table, $shipment_id, 'Update', $pallets_rate, $hand_pallet,
			$handling_charge, $freight, $extra_charge, $stop_off, $weekend,
			$detention, $un_detention, $rpm_mil, $fuel_cost, $adjust_value,
			$selection_fee, $discount, $total, $currency, $code, $rate );

		check_financial( $shipment_table, $shipment_id, $pallets_rate, $hand_pallet,
			$handling_charge, $freight, $extra_charge, $stop_off, $weekend,
			$detention, $un_detention, $rpm_mil, $fuel_cost, $adjust_value,
			$selection_fee, $discount, $total, $rate );

		if( ! $sts_debug )
		reload_page("exp_shipment_bill.php?id=".$shipment_id);
		die;
			
	}
	$res['shipment_details'] =$sql_obj->database->get_one_row("
		SELECT *,
		COALESCE((SELECT BC_NAME FROM EXP_BUSINESS_CODE
		WHERE EXP_SHIPMENT.BUSINESS_CODE = EXP_BUSINESS_CODE.BUSINESS_CODE
		LIMIT 1), '') AS BUSINESS_CODE_NAME
		FROM ".SHIPMENT_TABLE."
		WHERE SHIPMENT_CODE=".$shipment_id);

   //! EDI Shipment info (EDI_204_PRIMARY == SHIPMENT_CODE)
   if( isset($res['shipment_details']) &&
		isset($res['shipment_details']["EDI_204_PRIMARY"]) &&
		intval($res['shipment_details']["EDI_204_PRIMARY"]) == intval($shipment_id) ) {
		
		$shipments = $sql_obj->database->get_multiple_rows("
			SELECT SHIPMENT_CODE 
			FROM EXP_SHIPMENT
			WHERE EDI_204_PRIMARY = ".$shipment_id);
		$res['shipment_details']["EDI_SHIPMENTS"] = array();
		if( is_array($shipments)) {
			foreach($shipments as $s) { 
				$res['shipment_details']["EDI_SHIPMENTS"][] = $s["SHIPMENT_CODE"];
			}
		}
	}

	// Duncan - get client code from shipment table.
	$client_id = array();
	$client_id['CLIENT_CODE'] = $res['client_id'] = isset($res['shipment_details']) &&
		isset($res['shipment_details']["BILLTO_CLIENT_CODE"]) ?
			$res['shipment_details']["BILLTO_CLIENT_CODE"] : 0;

	$res['saved_details'] =$bill_obj->database->get_multiple_rows("
		SELECT * FROM ".CLIENT_BILL."
		LEFT JOIN ".CLIENT_BILL_RATES."
		ON ".CLIENT_BILL_RATES.".BILLING_ID=".CLIENT_BILL.".CLIENT_BILLING_ID
		WHERE SHIPMENT_ID=".$shipment_id);
	
	#----------no. of picks -------#
	$no_of_picks=0;
	if($res['shipment_details']['LOAD_CODE']!=0)
	{
	 $total_picks=$stop_obj->database->get_one_row("
	 	select count(*) num_picks
	 	from exp_stop
	 	where load_code = ".$res['shipment_details']['LOAD_CODE']."
	 	and stop_type = 'pick' ");//AND SHIPMENT=".$shipment_id."
	 
	$no_of_picks=$total_picks['num_picks'];
	}
	$res['no_of_picks']=$no_of_picks;
	
	#-------no. of drops---------#
	$no_of_drops=0;
	if($res['shipment_details']['LOAD_CODE']!=0)
	{
	 $total_drops=$stop_obj->database->get_one_row("
	 	select count(*) num_drop
	 	from exp_stop
	 	where load_code = ".$res['shipment_details']['LOAD_CODE']."
	 	and stop_type = 'drop'");// AND SHIPMENT=".$shipment_id."
	$no_of_drops=$total_drops['num_drop'];
	}
	$res['no_of_drops']=$no_of_drops;
	
	#--------expected arrival & departure---------------#
	$expected_pickup_time=$expected_drop_time='';
	$pickupDate=$sql_obj->database->get_one_row("
	SELECT TIMESTAMP(PICKUP_DATE,
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
		$expected_pickup_time=date('m/d H:i',strtotime($pickupDate['PICKUP_DATE']));
	
	if(count($pickupDate)>0 && $pickupDate['DELIVER_DATE']!="")
		$expected_drop_time=date('m/d H:i',strtotime($pickupDate['DELIVER_DATE']));

	$res['expected_drop_time']=$expected_drop_time;
	$res['expected_pickup_time']=$expected_pickup_time;
	#---------actual arrival & departure-----------------#
	$actual_pickup_time=$actual_drop_time=$actual_pickup_depart_time=$actual_drop_arrival_time='';
	$pick_time=$stop_obj->database->get_one_row("
		SELECT ACTUAL_ARRIVE,ACTUAL_DEPART
		FROM exp_stop
		where STOP_TYPE='pick'
		AND SHIPMENT=".$shipment_id);
	
	if(is_array($pick_time) && count($pick_time)>0 && $pick_time['ACTUAL_ARRIVE']!="") {
		$actual_pickup_time=date('m/d H:i',strtotime($pick_time['ACTUAL_ARRIVE']));
		if($pick_time['ACTUAL_DEPART']!="")
			$actual_pickup_depart_time=date('m/d H:i',strtotime($pick_time['ACTUAL_DEPART']));
	}

	$drop_time=$stop_obj->database->get_one_row("
		SELECT ACTUAL_ARRIVE,ACTUAL_DEPART
		FROM exp_stop
		where STOP_TYPE='drop'
		AND SHIPMENT=".$shipment_id);
	
	if(is_array($drop_time) && count($drop_time)>0 && $drop_time['ACTUAL_DEPART']!="") {
		$actual_drop_time=date('m/d H:i',strtotime($drop_time['ACTUAL_DEPART']));
		if($drop_time['ACTUAL_ARRIVE']!="")
			$actual_drop_arrival_time=date('m/d H:i',strtotime($drop_time['ACTUAL_ARRIVE']));
	}
	$res['actual_pickup_time']=$actual_pickup_time;
	$res['actual_drop_time']=$actual_drop_time;
	$res['actual_pickup_depart_time']=$actual_pickup_depart_time;
	$res['actual_drop_arrival_time']=$actual_drop_arrival_time;

	//! Duncan - SCR 34 - include SS_NUMBER
	if( $multi_company )
		$res['SS_NUMBER'] = $res['shipment_details']['SS_NUMBER'];
	else
		$res['SS_NUMBER'] = false;

	//! Duncan - SCR 232 - log hours
	$res['LOG_HOURS'] = $sts_log_hours;	

	//! SCR# 702 - Get equipment required for a shipment
	$res['EQUIPMENT'] = $shipment_table->get_equipment_req( $shipment_id );

}

echo $rslt->render_update_client_bill_history($res);

?>
</div>
<?php

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
	var rpm=document.getElementById('RPM').value;
	var fuel_cost=document.getElementById('FUEL_COST').value;
	var client_total_check = document.getElementById('TOTAL_RATE');
	if(typeof client_total_check !== 'undefined' && client_total_check !== null) {
    	var client_total=document.getElementById('TOTAL_RATE').value;
    } else {
	    var client_total=0;
    }
	var hand_pallet=document.getElementById('HAND_PALLET').value;
	var pall_rate=document.getElementById('PALLETS_RATE').value;
	var det_rate=document.getElementById('DETENTION_RATE').value;
	var rpm_mil=document.getElementById('RPM_MILLEAGE').value;
	var stop_off=document.getElementById('STOP_OFF').value;
	var adjustment_charge=document.getElementById('ADJUSTMENT_CHARGE').value;
	var weekend=document.getElementById('WEEKEND').value;
	var selection=document.getElementById('SELECTION_FEE').value;
	var discount=document.getElementById('DISCOUNT').value;
	
	var total=0;
	if(handling=="")
	{handling=0;}
	if(extra=="")
	{extra=0;}
	if(freight=="")
	{freight=0;}
	if(detention=="")
	{detention=0;}
	if(un_detention=="")
	{un_detention=0;}
	if(rpm_mil=="")
	{rpm_mil=0;}
	if(fuel_cost=="")
	{fuel_cost=0;}
	if(client_total=="")
	{client_total=0;}
	if(pall_rate=="")
	{pall_rate=0;}
	if(hand_pallet=="")
	{hand_pallet=0;}
	if(det_rate=="")
	{det_rate=0;}
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
	
	total=parseFloat(handling)+parseFloat(extra)+parseFloat(freight)+parseFloat(detention)+parseFloat(un_detention)+parseFloat(rpm_mil)+parseFloat(fuel_cost)+parseFloat(client_total)+parseFloat(pall_rate)+parseFloat(hand_pallet)+parseFloat(adjustment_charge)+parseFloat(weekend)+parseFloat(stop_off)+parseFloat(selection)-parseFloat(discount);//+parseFloat(carrier)+parseFloat(cod)
	
	document.getElementById('TOTAL').value=Math.round(total * 100)/100;
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
	{//alert('(');
	   $('#det_loader').show();
		$.ajax({
			url:'exp_save_rates.php?action=caldetentionrate&client_id='+client_id+'&det_hr='+det_hr+'&cons_name='+cons_name,
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
				   //var min_rate=parseFloat(x.mindetention)-1;
				   var new_det_hr=parseFloat(det_hr)-parseFloat(min_rate);
						if(new_det_hr<0)
						{var new_det_hr=det_hr;}
				document.getElementById('DETENTION_RATE').value=Math.round((parseFloat(x.detrate)*parseFloat(new_det_hr)) * 100)/100;
				document.getElementById('FREE_DETENTION_HOUR').value=min_rate;
				if(x.detrate!="" && (!isNaN(x.detrate)))
				{document.getElementById('RATE_PER_HOUR').value=x.detrate;}
				else
				{document.getElementById('RATE_PER_HOUR').value=0;}
			   }
			   else
			   {
				  document.getElementById('DETENTION_RATE').value=0;
				   if(x.mindetention!="")
				   {
					 var minm_rate=x.mindetention;
					   if(x.mindetention>0)
					   {
						 minm_rate=parseFloat(x.mindetention);
					   }
					   	document.getElementById('FREE_DETENTION_HOUR').value=minm_rate;
				  }
				  	document.getElementById('RATE_PER_HOUR').value=0;
			   }
				/*var det_rate=res.trim();
				document.getElementById('DETENTION_RATE').value=parseFloat(det_rate)*parseFloat(det_hr);*/
				 $('#det_loader').hide();
				  calculate_total();
			}
		});
	}
}

function calc_unloaded_det_rate(client_id,cons_name)
{
	var det_hr=document.getElementById('UNLOADED_DETENTION_HOUR').value;
	if(det_hr=="")
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
			url:'exp_save_rates.php?action=calunloadeddetentionrate&client_id='+client_id+'&un_det_hr='+det_hr+'&cons_name='+cons_name,
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
				  // var min_rate=parseFloat(x.mindetention)-1;
				   var new_det_hr=parseFloat(det_hr)-parseFloat(min_rate);
				    if(new_det_hr<0)
				   {var new_det_hr=det_hr;}
				document.getElementById('UNLOADED_DETENTION_RATE').value=Math.round((parseFloat(x.detrate)*parseFloat(new_det_hr))*100)/100;
				document.getElementById('FREE_UN_DETENTION_HOUR').value=min_rate;
				if(x.detrate!="" && (!isNaN(x.detrate)))
				{document.getElementById('UN_RATE_PER_HOUR').value=x.detrate;}
				else
				{document.getElementById('UN_RATE_PER_HOUR').value=0;}
			   }
			   else
			   {
				  document.getElementById('UNLOADED_DETENTION_RATE').value=0;
				  document.getElementById('UN_RATE_PER_HOUR').value=0;
				  if(x.mindetention!="")
				  {
					 var minm_rate=x.mindetention;
					   if(x.mindetention>0)
					   {
						 minm_rate=parseFloat(x.mindetention);
					   }
					   	document.getElementById('FREE_UN_DETENTION_HOUR').value=minm_rate;
				  }
			   }
				//var det_rate=res.trim();
				//document.getElementById('UNLOADED_DETENTION_RATE').value=parseFloat(det_rate)*parseFloat(det_hr);
				 $('#det_loader').hide();
				  calculate_total();
			}
		});
	}
}

function calculate_rpm_amount()
{
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
		{document.getElementById('RPM_MILLEAGE').value=ans;}
		else
		{document.getElementById('RPM_MILLEAGE').value=0;}
	}
	var col_type=document.getElementById('fsc_col_per_mile');
	var col_val=document.getElementById('fsc_col_per_mile_val');
	if(col_type.value=='Per Mile Adjust')
	{
		var per_mile=document.getElementById('FSC_AVERAGE_RATE').value;
		var per_mile_adjust=parseFloat(per_mile)*parseFloat(mil);
		if(!isNaN(per_mile_adjust))
		{document.getElementById('FUEL_COST').value=Math.round(per_mile_adjust * 100)/100;}
		else
		{document.getElementById('FUEL_COST').value=0;}
	}
	calculate_total();
}

function calc_det_rate_per_hr(client_id,cons_name)
{
	var rate_per_hr=$('#RATE_PER_HOUR').val();
	var det_hr=document.getElementById('DETENTION_HOUR').value;
	  $('#det_loader').show();
		 ajreq=$.ajax({
			url:'exp_save_rates.php?action=caldetentionrate&client_id='+client_id+'&det_hr='+det_hr+'&cons_name='+cons_name,
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



function cal_pallet_rate(client_id,cons_name,billto_name,cons_city)
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
			url:'exp_ajax_functions.php?action=calpalletrate&client_id='+client_id+'&pallets='+pallets+'&cons_name='+encodeURIComponent(cons_name)+'&billto_name='+encodeURIComponent(billto_name)+'&cons_city='+encodeURIComponent(cons_city),
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

function cal_pallet_per_rate(client_id,cons_name,billto_name,cons_city)
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
			url:'exp_ajax_functions.php?action=calpallet_perrate&client_id='+client_id+'&pallets='+pallets+'&cons_name='+encodeURIComponent(cons_name)+'&billto_name='+encodeURIComponent(billto_name)+'&pallet_rate='+pallet_rate+'&cons_city='+encodeURIComponent(cons_city),
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
	upd_bol_number(<?php echo $shipment_id; ?>)
});



</script>

