<?php 

// $Id: exp_shipment_bill.php 5030 2023-04-12 20:31:34Z duncan $
// View read-only client billing

// Set flag that this is a parent file
define( '_STS_INCLUDE', 1 );

// Setup Session
require_once( "include/sts_session_setup.php" );

require_once( "include/sts_config.php" );
$sts_debug = isset($_GET['debug']) && in_group( EXT_GROUP_DEBUG );
require_once( "include/sts_session_class.php" );

$my_session = sts_session::getInstance( $exspeedite_db, $sts_debug );
$my_session->access_check( $sts_table_access[SHIPMENT_TABLE] );	// Make sure we should be here

$sts_subtitle = "View Client Billing";
require_once( "include/header_inc.php" );
require_once( "include/navbar_inc.php" );

?>
<div class="container-full" role="main">
<?php
require_once( "include/sts_result_class.php" );
require_once( "include/sts_shipment_class.php" );
require_once( "include/sts_setting_class.php" );

$sql_obj	=	new sts_table($exspeedite_db , SHIPMENT_TABLE , $sts_debug);
$bill_obj=new sts_table($exspeedite_db , CLIENT_BILL , $sts_debug);
$bill_rate=new sts_table($exspeedite_db , CLIENT_BILL_RATES , $sts_debug);
$stop_obj=new sts_table($exspeedite_db , STOP_TABLE , $sts_debug);

$setting_table = sts_setting::getInstance($exspeedite_db, $sts_debug);
$sts_export_qb = $setting_table->get( 'api', 'EXPORT_QUICKBOOKS' ) == 'true';
$sts_export_sage50 = $setting_table->get( 'api', 'EXPORT_SAGE50_CSV' ) == 'true';
$sts_destination = $sts_export_qb ? 'Quickbooks' : 'Accounting';
$multi_company = $setting_table->get("option", "MULTI_COMPANY") == 'true';
$sts_log_hours = $setting_table->get( 'option', 'BILLING_LOG_HOURS' ) == 'true';
$sts_approve_operations = $setting_table->get( 'option', 'SHIPMENT_APPROVE_OPERATIONS' ) == 'true';

$shipment_table = sts_shipment::getInstance($exspeedite_db, $sts_debug);
$rslt = new sts_result( $shipment_table, false, $sts_debug );

$shipment_id=$_GET['id'];
if($shipment_id!="") {
	if( isset($_GET['delete'])) {
		$bill_obj->delete_row("SHIPMENT_ID = ".$shipment_id);
		$dummy = $shipment_table->add_shipment_status( $shipment_id, 'Deleted billing data' );

		reload_page("exp_clientpay.php?id=".$shipment_id);
		die;
	}
	$res['shipment_details']		=$sql_obj->database->get_one_row("
		SELECT *,
		COALESCE((SELECT BC_NAME FROM EXP_BUSINESS_CODE
		WHERE EXP_SHIPMENT.BUSINESS_CODE = EXP_BUSINESS_CODE.BUSINESS_CODE
		LIMIT 1), '') AS BUSINESS_CODE_NAME
		FROM ".SHIPMENT_TABLE."
		WHERE SHIPMENT_CODE=".$shipment_id);
		
	$res['saved_details'] =$bill_obj->database->get_multiple_rows("
		SELECT * FROM ".CLIENT_BILL."
		LEFT JOIN ".CLIENT_BILL_RATES."
		ON ".CLIENT_BILL_RATES.".BILLING_ID=".CLIENT_BILL.".CLIENT_BILLING_ID
		WHERE SHIPMENT_ID=".$shipment_id);
		
	$res['status'] = $shipment_table->state_name[$res['shipment_details']['CURRENT_STATUS']];
	$res['bstatus'] = $shipment_table->billing_state_behavior[$res['shipment_details']['BILLING_STATUS']];
		#----------no. of picks -------#
	$no_of_picks=0;
	if($res['shipment_details']['LOAD_CODE']!=0)
	{
	 $total_picks=$stop_obj->database->get_one_row("select count(*) num_picks from exp_stop where load_code = ".$res['shipment_details']['LOAD_CODE']." and stop_type = 'pick' ");//AND SHIPMENT=".$shipment_id."
	 
	$no_of_picks=$total_picks['num_picks'];
	}
	$res['no_of_picks']=$no_of_picks;
	
   //! EDI Shipment info (EDI_204_PRIMARY == SHIPMENT_CODE)
   if( isset($res['shipment_details']) &&
		isset($res['shipment_details']["EDI_204_PRIMARY"]) &&
		intval($res['shipment_details']["EDI_204_PRIMARY"]) == intval($shipment_id) ) {
		
		$shipments = $sql_obj->database->get_multiple_rows("SELECT SHIPMENT_CODE 
			FROM EXP_SHIPMENT
			WHERE EDI_204_PRIMARY = ".$shipment_id);
		$res['shipment_details']["EDI_SHIPMENTS"] = array();
		if( is_array($shipments)) {
			foreach($shipments as $s) { 
				$res['shipment_details']["EDI_SHIPMENTS"][] = $s["SHIPMENT_CODE"];
			}
		}
	}

	#-------no. of drops---------#
	$no_of_drops=0;
	if($res['shipment_details']['LOAD_CODE']!=0)
	{
	 $total_drops=$stop_obj->database->get_one_row("select count(*) num_drop from exp_stop where load_code = ".$res['shipment_details']['LOAD_CODE']." and stop_type = 'drop'");// AND SHIPMENT=".$shipment_id."
	$no_of_drops=$total_drops['num_drop'];
	}
	$res['no_of_drops']=$no_of_drops;
	
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
	{/*$time1='';
		if($pickupDate['PICKUP_TIME1']!="" && is_numeric($pickupDate['PICKUP_TIME1']))
		//$time1=$pickupDate['PICKUP_TIME1']/60;
		{$time1=gmdate('H:i:s',$pickupDate['PICKUP_TIME1']);}*/
	    $expected_pickup_time=date('m/d H:i',strtotime($pickupDate['PICKUP_DATE']));
	}
	
	if(count($pickupDate)>0 && $pickupDate['DELIVER_DATE']!="")
	{/*$time2='';
		//$time2=$pickupDate['DELIVER_TIME1']/60;
		if($pickupDate['DELIVER_TIME1']!="" && is_numeric($pickupDate['DELIVER_TIME1']))
		{$time2=gmdate('H:i:s',$pickupDate['DELIVER_TIME1']);}*/
		$expected_drop_time=date('m/d H:i',strtotime($pickupDate['DELIVER_DATE']));
	}
	$res['expected_drop_time']=$expected_drop_time;
	$res['expected_pickup_time']=$expected_pickup_time;
	#---------actual arrival & departure-----------------#
	$actual_pickup_time=$actual_drop_time=$actual_pickup_depart_time=$actual_drop_arrival_time='';
	$pick_time=$stop_obj->database->get_one_row("SELECT ACTUAL_ARRIVE,ACTUAL_DEPART FROM exp_stop where STOP_TYPE='pick' AND SHIPMENT=".$shipment_id);
	
	if(is_array($pick_time) && count($pick_time)>0 && $pick_time['ACTUAL_ARRIVE']!="")
	{
		$actual_pickup_time=date('m/d H:i',strtotime($pick_time['ACTUAL_ARRIVE']));//$pick_time['ACTUAL_ARRIVE'];
		if($pick_time['ACTUAL_DEPART']!="")
		{
			$actual_pickup_depart_time=date('m/d H:i',strtotime($pick_time['ACTUAL_DEPART']));
		}
	}
	$drop_time=$stop_obj->database->get_one_row("SELECT ACTUAL_ARRIVE,ACTUAL_DEPART FROM exp_stop where STOP_TYPE='drop' AND SHIPMENT=".$shipment_id);
	
	if(is_array($drop_time) && count($drop_time)>0 && $drop_time['ACTUAL_DEPART']!="")
	{
		$actual_drop_time=date('m/d H:i',strtotime($drop_time['ACTUAL_DEPART']));//$drop_time['ACTUAL_DEPART'];
		if($drop_time['ACTUAL_ARRIVE']!="")
		{
			$actual_drop_arrival_time=date('m/d H:i',strtotime($drop_time['ACTUAL_ARRIVE']));
		}
	}
	$res['actual_pickup_time']=$actual_pickup_time;
	$res['actual_drop_time']=$actual_drop_time;
	$res['actual_pickup_depart_time']=$actual_pickup_depart_time;
	$res['actual_drop_arrival_time']=$actual_drop_arrival_time;
	
	//! Duncan - SCR 26 - add tax info
	require_once( "include/sts_company_tax_class.php" );
	$ctax = sts_company_tax::getInstance($exspeedite_db, $sts_debug);
	$tax_info = $ctax->tax_info( $shipment_id );
	
	if( is_array($tax_info)) {
		$res['tax'] = array();
		foreach( $tax_info as $tax ) {
			$res['tax'][] = array('TAX' => $tax['province'].' '.$tax['tax'].' ('.$tax['rate'].'%)',
				'AMOUNT' =>  $tax['AMOUNT'] );
		}
	} else {
		$res['tax_issue'] =  $ctax->get_issue();
	}
	
	//! Duncan - SCR 34 - include SS_NUMBER
	if( $multi_company )
		$res['SS_NUMBER'] = $res['shipment_details']['SS_NUMBER'];
	else
		$res['SS_NUMBER'] = false;
	
	//! Duncan - SCR 232 - log hours
	$res['LOG_HOURS'] = $sts_log_hours;	

	//! SCR# 702 - Get equipment required for a shipment
	$res['EQUIPMENT'] = $shipment_table->get_equipment_req( $shipment_id );

	$res['SAGE50'] = $sts_export_sage50;
	$res['multi_company'] = $multi_company;
	$res['approve_operations'] = $sts_approve_operations;

}

echo $rslt->render_client_bill_history($res);

?>
</div>
<?php

require_once( "include/footer_inc.php" );
?>

<script type="text/javascript" language="javascript">
function chk_confirm_update(shipment_id)
{
	if(confirm("Do you really want to edit client billing rates?"))
	{
		//alert('exp_updateclientbilling.php?id='+shipment_id);
		//window.location.href='exp_updateclientbilling.php?id='+shipment_id;
		return true;
	}
	else
	{
		return false;
	}
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
</script>

