<?php
	// Set flag that this is a parent file
	define( '_STS_INCLUDE', 1 );
	
	// Set flag that this is session readonly
	define( '_STS_SESSION_READONLY', 1 );

	// Set flag that this is an ajax call
	define( '_STS_SESSION_AJAX', 1 );

	// Setup Session
	require_once( "include/sts_session_setup.php" );
	
	require_once( "include/sts_config.php" );
	$sts_debug =isset($_GET['debug']) && in_group( EXT_GROUP_DEBUG );
	require_once( "include/sts_session_class.php" );
	if(isset($_GET['id']))
	{$driver_id	=	$_GET['id'];}
	
	require_once( "include/sts_shipment_class.php" );
	$shipment_table = sts_shipment::getInstance($exspeedite_db, $sts_debug);
	
	$my_session = sts_session::getInstance( $exspeedite_db, $sts_debug );
	
	
	$pall_obj=new sts_table($exspeedite_db , PALLET_RATE , $sts_debug);
	$pall_rate_obj=new sts_table($exspeedite_db , PALLET_MASTER , $sts_debug);
	$load_obj=new sts_table($exspeedite_db , LOAD_PAY_MASTER , $sts_debug);
	$manual=new sts_table($exspeedite_db , PROFILE_MANUAL , $sts_debug);
	$man_obj=new sts_table($exspeedite_db , LOAD_MAN_RATE , $sts_debug);
	
	##---------- calculate pallets---------##
	if($_REQUEST['action']=='calpalletrate' && $_REQUEST['client_id']!="" && $_REQUEST['cons_city']!="")
	{
	   $client_id=$_REQUEST['client_id'];
	   $pallets=$_REQUEST['pallets'];
	   $cons_name=$_REQUEST['cons_name'];
	   $cons_city=$_REQUEST['cons_city'];
	   $pallet_rate=0;
	   $arr=array();
	   
       $pallet_id= $pall_rate_obj->database->get_one_row("SELECT PALLET_ID,MIN_CHARGE,MAX_CHARGE FROM 
       		".PALLET_MASTER." 
       		JOIN ".SHIPMENT_TABLE." ON 
       		".PALLET_MASTER.".CLIENT_ID = '".$client_id."' 
       		WHERE ".PALLET_MASTER.".CUST_NAME='".$pall_rate_obj->real_escape_string($cons_name)."'  
			AND ".PALLET_MASTER.".CUST_CITY='".$pall_rate_obj->real_escape_string($cons_city)."' 
       		AND USER_TYPE='client'  GROUP BY PALLET_ID");
  		 if((count($pallet_id)>0) && isset($pallet_id['PALLET_ID']))
  		{
  			$per_pallet_Rate=0;
  			$x="SELECT PALLET_RATE FROM ".PALLET_RATE." WHERE  PALLET_ID=".$pallet_id['PALLET_ID']." AND (('".$pallets."' BETWEEN PALLET_FROM AND PALLET_TO) OR ((PALLET_FROM <=".$pallets." AND PALLET_TO>= 11 )))";
  			$res['pallet_rate']=$pall_rate_obj->database->get_one_row($x);
  			if(count($res['pallet_rate'])>0 && $res['pallet_rate']['PALLET_RATE']!="" )
  			{$per_pallet_Rate=$res['pallet_rate']['PALLET_RATE'];
  				$pallet_rate=$per_pallet_Rate*$pallets;
  				if( $pallet_rate!="0" && isset($pallet_id['PALLET_ID']))
  				{
  					if($pallet_rate<$pallet_id['MIN_CHARGE'])
  					{
  						$pallet_rate=$pallet_id['MIN_CHARGE'];
  					}
  					else if($pallet_rate>$pallet_id['MAX_CHARGE'])
  					{
  						$pallet_rate=$pallet_id['MAX_CHARGE'];
  					}
  				}
  			}
  			$arr=array('ans'=>'yes','per_pallet_rate'=>$per_pallet_Rate,'pallet_rate'=>$pallet_rate);
  			
  		}
  		else 
  		{
  			$arr=array('ans'=>'no','per_pallet_rate'=>0,'pallet_rate'=>0);
  		}
  		echo json_encode($arr);exit;
	}
	
	if($_REQUEST['action']=='calpallet_perrate' && $_REQUEST['client_id']!="")
	{
		$client_id=$_REQUEST['client_id'];
		$pallets=$_REQUEST['pallets'];
		$cons_name=$_REQUEST['cons_name'];
		$per_pallet_rate=$_REQUEST['pallet_rate'];
		$cons_city=$_REQUEST['cons_city'];
		$arr=array();
		$pallet_rate=0;
       $pallet_id= $pall_rate_obj->database->get_one_row("SELECT PALLET_ID,MIN_CHARGE,MAX_CHARGE FROM 
       		".PALLET_MASTER." 
       		JOIN ".SHIPMENT_TABLE." ON 
       		".PALLET_MASTER.".CLIENT_ID = '".$client_id."' 
       		WHERE ".PALLET_MASTER.".CUST_NAME='".$pall_rate_obj->real_escape_string($cons_name)."'  
			AND ".PALLET_MASTER.".CUST_CITY='".$pall_rate_obj->real_escape_string($cons_city)."' 
       		AND USER_TYPE='client'  GROUP BY PALLET_ID");
			
		if((count($pallet_id)>0) && isset($pallet_id['PALLET_ID']))
		{
			//$per_pallet_Rate=0;
			$x="SELECT PALLET_RATE_ID FROM ".PALLET_RATE." WHERE  PALLET_ID=".$pallet_id['PALLET_ID']." AND (('".$pallets."' BETWEEN PALLET_FROM AND PALLET_TO) OR ((PALLET_FROM <=".$pallets." AND PALLET_TO>= 11 )))";
			$res['pallet_rate']=$pall_rate_obj->database->get_one_row($x);
			if(count($res['pallet_rate'])>0 && $res['pallet_rate']['PALLET_RATE_ID']!="" )
			{
			$pallet_rate=$per_pallet_rate*$pallets;
			if( $pallet_rate!="0" && isset($pallet_id['PALLET_ID']))
			{
				if($pallet_rate<$pallet_id['MIN_CHARGE'])
				{
					$pallet_rate=$pallet_id['MIN_CHARGE'];
				}
				else if($pallet_rate>$pallet_id['MAX_CHARGE'])
				{
					$pallet_rate=$pallet_id['MAX_CHARGE'];
				}
			}
			}
			$arr=array('ans'=>'yes','per_pallet_rate'=>$per_pallet_rate,'pallet_rate'=>$pallet_rate);
				
		}
		else
		{
			$arr=array('ans'=>'no','per_pallet_rate'=>0,'pallet_rate'=>0);
		}
		echo json_encode($arr);exit;
	}
	
	if($_REQUEST['action']=='update_drivertotalmiles' && $_REQUEST['load_id']!="")
	{
		$load_id=$_GET['load_id'];
		$miles=$_GET['miles'];
		$driver_id=$_GET['driver_id'];
		$odometer_from=$_GET['odometer_from'];
		$odometer_to=$_GET['odometer_to'];
		$type=$_GET['type'];
		/*$load_obj->update_row('LOAD_CODE='.$load_id ,array( array('field'=>'TOTAL_DISTANCE','value'=>$miles)));
		$load_obj->update_row('LOAD_CODE='.$load_id ,array( array('field'=>'ODOMETER_FROM','value'=>$odometer_from)));
		$load_obj->update_row('LOAD_CODE='.$load_id ,array( array('field'=>'ODOMETER_TO','value'=>$odometer_to)));*/
	   $whr='';
		if($type=='load')
		{
			$whr='LOAD_ID='.$load_id.' AND';
		}
		else
		{$whr='TRIP_ID='.$load_id.' AND';}
		$load_obj->database->get_one_row("UPDATE ".LOAD_PAY_MASTER." set ODOMETER_FROM='".$odometer_from."',ODOMETER_TO='".$odometer_to."',TOTAL_MILES='".$miles."' where $whr DRIVER_ID=$driver_id");
		
		
		
		echo 'OK';exit;
	}
	if($_REQUEST['action']=='driver_fetchonemanualrate' && $_REQUEST['val']!='' )
	{
		    $load_id=$_REQUEST['load_id'];
		    $driver_id=$_REQUEST['driver_id'];
			$manual_id=$_REQUEST['val'];
			$type=$_REQUEST['type'];
			$prev_cnt=0;
			$arr=array();
			$status='';
			$manual_rates=$manual->database->get_multiple_rows(" SELECT  *  FROM ".MANUAL_RATES." WHERE MANUAL_ID='".$manual_id."'");
			//print_r($manual_rates);
			if(count($manual_rates)>0)
			{
				$whr='';
				if($type=='load')
				{
					$whr='LOAD_ID='.$load_id.' AND';
				}
				else
				{$whr='TRIP_ID='.$trip_id.' AND';}
			  $prev_cnt=$man_obj->database->get_multiple_rows(" SELECT  count(*) as prev_cnt FROM ".LOAD_MAN_RATE." WHERE $whr DRIVER_ID='".$driver_id."' AND MANUAL_CODE='".$manual_rates[0]['MANUAL_RATE_CODE']."'");
			 
			  if((count($prev_cnt)>0))
			  {
			  	if($prev_cnt[0]['prev_cnt']==0)
			  	{ $arr	=	$manual_rates[0];
			  	  $status='ok';
			  	}
			  	else 
			  	{
			  		$status='exists';
			  	}
			  }
			 }
			 $new_arr=array('data'=>$arr,'status'=>$status);
			 
			echo json_encode($new_arr);
			//$arr	=	$manual_rates[0];
			//echo json_encode($arr);
				
	}
		##---------- update BOL---------##
	if($_REQUEST['action']=='upd_bol_number' && isset($_REQUEST['shipment']) &&
		isset($_REQUEST['bol']) && $_REQUEST['shipment']!="" && $_REQUEST['bol']!="") {
		$result = $shipment_table->update( $_REQUEST['shipment'], array( "BOL_NUMBER" => $_REQUEST['bol']));
		if( $result ) {
			$result = $shipment_table->fetch_rows("BOL_NUMBER ='".$_REQUEST['bol']."' AND
				SHIPMENT_CODE <> ".$_REQUEST['shipment'], "COUNT(*) AS DUPS");
		}
		echo json_encode($result);
	}

	
	
	 ?>