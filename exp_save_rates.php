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
	
	$my_session = sts_session::getInstance( $exspeedite_db, $sts_debug );
	$ass_obj	=	new sts_table($exspeedite_db , DRIVER_ASSIGN_RATES , $sts_debug);
	$sql_obj	=	new sts_table($exspeedite_db , MANUAL_RATES , $sts_debug);
	$ren_obj	=	new sts_table($exspeedite_db , RANGE_RATES , $sts_debug);
	$range_obj=new sts_table($exspeedite_db , DRIVER_RATES , $sts_debug);
	$profile_range_obj=new sts_table($exspeedite_db , PROFILE_RATE , $sts_debug);
	$profile_man_obj=new sts_table($exspeedite_db , PROFILE_MANUAL_RATE , $sts_debug);
	$manual=new sts_table($exspeedite_db , PROFILE_MANUAL , $sts_debug);
	$range=new sts_table($exspeedite_db , PROFILE_RANGE_RATE , $sts_debug);
	$rate_obj=new sts_table($exspeedite_db , PROFILE_RANGE , $sts_debug);
	$pro_master=new sts_table($exspeedite_db , PROFILE_MASTER , $sts_debug);
	$client_cat_rate=new sts_table($exspeedite_db , CLIENT_CAT_RATE , $sts_debug);
	$driver_obj=new sts_table($exspeedite_db , DRIVER_TABLE , $sts_debug);
	$client_obj=new sts_table($exspeedite_db , CLIENT_ASSIGN_RATE , $sts_debug);
	$client_fsc=new sts_table($exspeedite_db , CLIENT_FSC , $sts_debug);

	$driver=new sts_table($exspeedite_db , DRIVER_PAY_MASTER , $sts_debug);

	$handle_obj=new sts_table($exspeedite_db , HANDLING_TABLE , $sts_debug);
	$fr_obj=new sts_table($exspeedite_db , FRIGHT_RATE_TABLE , $sts_debug);
	$load_obj=new sts_table($exspeedite_db , LOAD_TABLE , $sts_debug);
	$man_code=new sts_table($exspeedite_db , MAN_CODE , $sts_debug);
	$ran_code=new sts_table($exspeedite_db , RAN_CODE, $sts_debug);
	$fsc_obj=new sts_table($exspeedite_db , FSC_SCHEDULE, $sts_debug);
	$pall_obj=new sts_table($exspeedite_db , PALLET_RATE , $sts_debug);
	$pall_rate_obj=new sts_table($exspeedite_db , PALLET_MASTER , $sts_debug);
	$det_obj=new sts_table($exspeedite_db , DETENTION_MASTER , $sts_debug);
	$det_rate=new sts_table($exspeedite_db , DETENTION_RATE , $sts_debug);
	$fsc_his=new sts_table($exspeedite_db , FSC_HISTORY , $sts_debug);
	$un_det_rate=new sts_table($exspeedite_db , UNLOADED_DETENTION_RATE , $sts_debug);
	$man_obj=new sts_table($exspeedite_db , LOAD_MAN_RATE , $sts_debug);
	
	if($_GET['action']=='savemanualrates')
	{
		$manual_driver_id		=	$_GET['manual_driver_id'];
		$manual_code			=	$_GET['manual_code'];
		$manual_name			=	urldecode($_GET['manual_name']);
		$manual_desc			=	urldecode($_GET['manual_desc']);
		$manual_rate				=	$_GET['manual_rate'] ;
		$ins_res				=	$sql_obj->add_row("DRIVER_ID,MANUAL_RATE_CODE,MANUAL_NAME,MANUAL_RATE_DESC,MANUAL_RATE_RATE", "'$manual_driver_id	','$manual_code',\"".$manual_name."\",\"".$manual_desc."\",'$manual_rate'");	
		$man_code->add_row("UNIQUE_MAN_CODE","'$manual_code'");
			
	}
	if($_GET['action']=='deletemanualrates')
	{
		$manual_id					=	$_GET['manual_id'];
		$manual_driver_id		=	$_GET['manual_driver_id'];
		echo $ins_res				=	$sql_obj->delete_row("MANUAL_ID=$manual_id");		
	}
	
	
	if($_GET['action']=='saverangerates')
	{
		$error='';
		$range_rate_code	=	$_GET['range_rate_code'];
		$range_from		=	$_GET['range_from'];
		$range_to			=	$_GET['range_to'];
		$range_rate		=	$_GET['range_rate'];
		$range_driver_id		=	$_GET['range_driver_id'];
		$zone_from		=$_GET['zone_from'];
		$zone_to				=$_GET['zone_to'];
		$range_name		=urldecode($_GET['range_name']);
		
		/*$get_count=$ren_obj->database->get_multiple_rows("SELECT COUNT(*) AS CNT FROM ".RANGE_RATES." WHERE DRIVER_ID='$range_driver_id' AND ((RANGE_FROM<='$range_from' AND RANGE_TO<='$range_from' AND RANGE_FROM<='$range_to' AND RANGE_TO<='$range_to') OR ((RANGE_FROM>='$range_from' AND RANGE_TO>='$range_from' AND RANGE_FROM>='$range_to' AND RANGE_TO>='$range_to')))");*/
		/*$get_count=$ren_obj->database->get_multiple_rows("SELECT COUNT(*) AS CNT FROM ".RANGE_RATES." WHERE DRIVER_ID='$range_driver_id' AND (RANGE_FROM>='$range_from' AND RANGE_TO<='$range_to')");*/
		$get_count=$ren_obj->database->get_multiple_rows("SELECT COUNT(*) AS CNT FROM ".RANGE_RATES." WHERE DRIVER_ID='$range_driver_id' AND (('$range_from'  BETWEEN RANGE_FROM AND RANGE_TO) OR ('$range_to' BETWEEN RANGE_FROM AND RANGE_TO) OR (RANGE_FROM BETWEEN '$range_from' AND '$range_to')  OR (RANGE_TO BETWEEN '$range_from' AND '$range_to')) ");//AND ZONE_FROM='$zone_from' AND ZONE_TO='$zone_to'
		
		//echo '****'.$get_count[0]['CNT'];
		if($get_count[0]['CNT']==0)
		{
		 $ins_res				=	$ren_obj->add_row("DRIVER_ID,RANGE_CODE,RANGE_NAME,RANGE_FROM,RANGE_TO,ZONE_FROM,ZONE_TO,RANGE_RATE", "'$range_driver_id','$range_rate_code',\"".$range_name."\",'$range_from','$range_to','$zone_from','$zone_to','$range_rate'");	
		 
		 $ran_code->add_row("UNIQUE_RANGE_CODE","'$range_rate_code'");
			
		}
		else
		{
			$error='error';
			echo $error;
		}
	}
	
	
	if($_GET['action']=='deleterangerates')
	{
		$range_id					=	$_GET['range_id'];
		$range_driver_id		=	$_GET['range_driver_id'];
		echo $ins_res				=	$ren_obj->delete_row("RANGE_ID=$range_id");		
	}
	if($_GET['action']=='deletedriverrates')
	{
		//$manual_driver_id		=	$_GET['manual_driver_id'];
		echo $ins_res				=	$ass_obj	->delete_row("ASSIGN_ID=".$_GET['val']);		
	}
	if($_GET['action']=='reloadsearch')
	{$str='';
		if($_GET['val']!='')
		{
		$str	=	"AND   ".DRIVER_RATES.".CATEGORY =".$_GET['val'];
		}
		$driver_rates			=	$sql_obj->database->get_multiple_rows("SELECT ".DRIVER_RATES.".*, ".CATEGORY_TABLE.".CATEGORY_NAME  FROM ".DRIVER_RATES." , ".CATEGORY_TABLE."  WHERE  ".DRIVER_RATES.".CATEGORY=".CATEGORY_TABLE.".CATEGORY_CODE  $str AND ".DRIVER_RATES.".ISDELETED <> '1' ");
			$rate_str='';
		if(count($driver_rates) >0)
		{
		
				foreach($driver_rates as $row)
				{
					$rate_str.='
					<tr style="cursor:pointer;" >
					<td class="text-center"><input type="checkbox"  name="rate_ids" value="'.$row['RATE_ID'].'"></td>
					<td style="text-align:center;">  '.$row['CATEGORY_NAME'].'</td>
					<td style="text-align:center;">  '.$row['RATE_CODE'].'</td>
					<td style="text-align:center;">  '.$row['RATE_DESC'].'</td>
					<td style="text-align:center;">$  '.$row['RATE_PER_MILES'].'</td>
					 <td style="text-align:center;">  '.$row['RATE_BONUS'].'</td>
				  </tr>	';
				}
		}
		else
		{
				$rate_str.='<tr><td style="font-size:12px; font-family:Arial, Helvetica, sans-serif; text-transform:uppercase; padding:0 6px;" colspan="6" align="center">No Records Found.</td></tr>';
		}	?>
			<table class="table table-striped table-condensed table-bordered table-hover" style="">
				<thead>
				  <tr class="exspeedite-bg">
					<td class="text-center"><span class="text-muted"><span class="glyphicon glyphicon-unchecked"></span></span></td>
					 <th class="text-center">Rate Category</th>
					 <th class="text-center">Rate Code</th>
					 <th class="text-center">Rate Description</th>
					<th class="text-center">Rates</th>
					 <th class="text-center">Bonus</th>
				  </tr>
				</thead>
				<tbody>
			<?php echo $rate_str; ?>
				</tbody>
			  </table>
		
	<?php }
	
	//! fetch flat amount ,per mile adjust ,percent of a average fsc value--------##
	if($_GET['action']=='fetch_fsc_avg' && isset($_GET['fsc_val']))
	{
		$arr=array();
		$fsc_val=$_GET['fsc_val'];
		$fsc_id=$_GET['fsc_id'];
		$fsc_average=array();
	$fsc_average=$fsc_obj->database->get_multiple_rows("SELECT q.* FROM (SELECT * FROM ".FSC_SCHEDULE." WHERE FSC_UNIQUE_ID='".$fsc_id."' HAVING  ".$fsc_val." BETWEEN `LOW_PER_GALLON` AND `HIGH_PER_GALLON`)q GROUP BY FSC_ID");
	//echo count($fsc_average);
	//echo '<pre/>';print_r($fsc_average);
	$fsc_per_mile_field=$fsc_column="";
	if(count($fsc_average)>0)
	{
		if($fsc_average[0]['FLAT_AMOUNT']!="0.00")
		{
			$fsc_per_mile_field=$fsc_average[0]['FLAT_AMOUNT'];
			$fsc_column='Flat Amount';
		}
		else  if($fsc_average[0]['PER_MILE_ADJUST']!="0.00")
		{
			$fsc_per_mile_field=$fsc_average[0]['PER_MILE_ADJUST'];
			$fsc_column='Per Mile Adjust';
		}
		else 
		{
			$fsc_per_mile_field=$fsc_average[0]['PERCENT'];
			$fsc_column='Percent';
		}
		
	}
	$arr=array('fsc_per_mile_field'=>$fsc_per_mile_field,'fsc_column'=>$fsc_column);
	echo json_encode($arr);exit;
	}
	
	//! ---- ADd current records to profile-------------##
	if($_GET['action']=='addtoprofile')
	{
		$driver_id=$_GET['driver_id'];
		##------- fetch all rates,range ,manual rates of driver---##
		$get_assigned_rates=$range_obj->database->get_multiple_rows('SELECT '.DRIVER_RATES.'.RATE_ID FROM '.DRIVER_RATES.' JOIN '.DRIVER_ASSIGN_RATES.' ON  '.DRIVER_RATES.'.RATE_ID='.DRIVER_ASSIGN_RATES.'.RATE_ID WHERE '.DRIVER_ASSIGN_RATES.'.DRIVER_ID='.$driver_id);
		
		$get_manual_rates=$sql_obj->database->get_multiple_rows('SELECT * FROM '.MANUAL_RATES.' WHERE DRIVER_ID='.$driver_id);
		
		$get_range_rates=$ren_obj->database->get_multiple_rows('SELECT * FROM '.RANGE_RATES.' WHERE DRIVER_ID='.$driver_id);
		
		/*##----  Generate Contract ID------------##
		if((count($get_manual_rates)>0) || (count($get_range_rates)>0) || (count($get_assigned_rates)>0))
		{
		$get_contract_id=$pro_master->database->get_one_row('SELECT MAX(CONTRACT_ID)  AS max_id FROM (SELECT  CONTRACT_ID FROM '.PROFILE_MASTER.') AS T');
			//print_r($get_max_id);
			if(count($get_contract_id)!=0)
			{
				$contract_id=$get_contract_id['max_id']+1;
			}
			else
			{
				$contract_id=1;
			}
			
		$get_contract_id=$pro_master->add_row("CONTRACT_ID", "'$contract_id'");
		}
	
		//! -----   code to add rates  -----------------##
		
		//print_r($get_assigned_rates);
		if(count($get_assigned_rates)>0)
		{
			foreach($get_assigned_rates as $pro_rate)
			{
				//$ins_record=$profile_range_obj->add_row("PROFILE_ID,RATE_ID","'$profile_id','$pro_rate['RATE_ID']'");
				$ins_record=$profile_range_obj->add_row("PROFILE_ID,RATE_ID", "'$contract_id',".$pro_rate['RATE_ID']."");
			}
		}
		
		//! -------   code to add  manual rates -------------------------##
		
		//print_r($get_manual_rates);
		
		if(count($get_manual_rates)>0)
		{
			foreach($get_manual_rates as $pro_man)
			{
		$ans=$profile_man_obj->get_insert_id("PRO_RATE_CODE,PRO_RATE_NAME,PRO_RATE_DESC,PRO_RATE","'".$pro_man['MANUAL_RATE_CODE']."','".$pro_man['MANUAL_NAME']."','".$pro_man['MANUAL_RATE_DESC']."','".$pro_man['MANUAL_RATE_RATE']."'");
		if($ans!="")
		{$manual->add_row("PROFILE_ID,MANUAL_ID","'$contract_id','$ans'");}
			}
			
		}
		
		
		//! -------   code to add range rates -------------------------##
		
		//print_r($get_range_rates);exit;
		//echo count($get_range_rates);
		if(count($get_range_rates)>0)
		{
			
			foreach($get_range_rates as $rng)
			{
		$answer=$range->get_insert_id("PRO_RANGE_CODE,PRO_RANGE_NAME,PRO_RANGE_FROM,PRO_RANGE_TO,PRO_RANGE_RATE,PRO_ZONE_FROM,PRO_ZONE_TO","'".$rng['RANGE_CODE']."','".$rng['RANGE_NAME']."','".$rng['RANGE_FROM']."','".$rng['RANGE_TO']."','".$rng['RANGE_RATE']."','".$rng['ZONE_FROM']."','".$rng['ZONE_TO']."'");
		if($answer!="")
		{$rate_obj->add_row("PROFILE_ID,RANGE_RATE","'$contract_id','$answer'");}
			}
			
		}
		*/
		
		if((count($get_manual_rates)==0) && (count($get_range_rates)==0) && (count($get_assigned_rates)==0))
		{
			echo 'errorrate';
		}
		
		
	}
	
	
	//! Assign currently selected profile to driver profile------##
	if($_GET['action']=='assignprofile')
	{
		if(isset($_POST['driver_id']) && isset($_POST['pro_id']))
		{
			$driver_id=$_POST['driver_id'];
			$profile_id=$_POST['pro_id'];
			if($driver_id!="" && $profile_id!="")
			{
				$del_rate				=	$ass_obj	->delete_row("DRIVER_ID=".$driver_id);		
				$del_manual			=	$sql_obj	->delete_row("DRIVER_ID=".$driver_id);	
				$del_range				=	$ren_obj	->delete_row("DRIVER_ID=".$driver_id);		
				
				##----- fetch profile assigned rates & assign to driver ------##
				$get_profile_rate=$profile_range_obj->database->get_multiple_rows("SELECT RATE_ID FROM ".PROFILE_RATE." WHERE PROFILE_ID=".$profile_id); 
				//print_r($get_profile_rate);
				if(count($get_profile_rate)>0)
				{
					foreach($get_profile_rate as $pro_rate)
					{
						$ass_obj->add_row("DRIVER_ID,RATE_ID,PROFILE_ID","'$driver_id','".$pro_rate['RATE_ID']."','$profile_id'");
					}
				}
				
				
				//!  fetch profile manual rates &  assign to driver----------##
				$get_profile_manual=$profile_man_obj->database->get_multiple_rows("SELECT * FROM ".PROFILE_MANUAL_RATE." JOIN ".PROFILE_MANUAL." ON ".PROFILE_MANUAL.".MANUAL_ID=".PROFILE_MANUAL_RATE.".PRO_MANUAL_ID WHERE ".PROFILE_MANUAL.".PROFILE_ID=".$profile_id);	
				//echo count($get_profile_manual);
				if(count($get_profile_manual)>0)
				{
					foreach($get_profile_manual as $pro_manual)
					{
						$sql_obj->add_row("DRIVER_ID,MANUAL_RATE_CODE,MANUAL_NAME,MANUAL_RATE_DESC,MANUAL_RATE_RATE,PROFILE_ID","'$driver_id','".$pro_manual['PRO_RATE_CODE']."','".$pro_manual['PRO_RATE_NAME']."','".$pro_manual['PRO_RATE_DESC']."','".$pro_manual['PRO_RATE']."','$profile_id'");
					}
				}
				
				//!  fetch profile range  assign to driver---------##
				$get_profile_range=$range->database->get_multiple_rows("SELECT * FROM ".PROFILE_RANGE_RATE." JOIN ".PROFILE_RANGE." ON ".PROFILE_RANGE.".RANGE_RATE=".PROFILE_RANGE_RATE.".PRO_RANGE_ID WHERE ".PROFILE_RANGE.".PROFILE_ID=".$profile_id);
				//print_r($get_profile_range);exit;
				if(count($get_profile_range)>0)
				{
					foreach($get_profile_range as $pro_ran)
					{
						$ren_obj	->add_row("DRIVER_ID,RANGE_CODE,RANGE_NAME,RANGE_FROM,RANGE_TO,RANGE_RATE,PROFILE_ID,ZONE_FROM,ZONE_TO","'$driver_id','".$pro_ran['PRO_RANGE_CODE']."','".$pro_ran['PRO_RANGE_NAME']."','".$pro_ran['PRO_RANGE_FROM']."','".$pro_ran['PRO_RANGE_TO']."','".$pro_ran['PRO_RANGE_RATE']."','$profile_id','".$pro_ran['PRO_ZONE_FROM']."','".$pro_ran['PRO_ZONE_TO']."'");
					}
				}
				$driver_obj->update_row( "DRIVER_CODE=".$driver_id, array(array("field"=>"PROFILE_ID","value"=>$profile_id)));
				echo 'OK';
			}
		}exit;
	}
	//array('PROFILE_ID <>' =>'".$profile_id."');
	if($_GET['action']=='searchbycat')
	{
		 $category=$_GET['val'];
		$str="";
		if($category!="")
		{
			$str="WHERE ".CLIENT_CAT_RATE.".CATEGORY=".$category;
		}
		$res['client_rate_cat']=$client_cat_rate->database->get_multiple_rows("SELECT * FROM ".CLIENT_CAT_RATE." JOIN ".CLIENT_CAT." ON ".CLIENT_CAT.".CLIENT_CAT=".CLIENT_CAT_RATE.".CATEGORY ".$str);
		$res['category']=$category;
		$cat_result="<tbody>";
		//echo '<pre/>';print_r($edit['client_rate_cat']);
		if(count($res['client_rate_cat'])>0)
		{
			foreach($res['client_rate_cat'] as $cr)
			{
				$cat_result.="<tr style='cursor:pointer;' >
									<td class='text-center'><input type='checkbox'  name='rate_ids[]' value=".$cr['CLIENT_RATE_ID']."></td>
									<td style='text-align:center;'>".$cr['RATE_CODE']."</td>
									<td style='text-align:center;'>".$cr['RATE_DESC']."</td>
									<td style='text-align:center;'>$  ".$cr['RATE_PER_MILES']."</td>
									 <td style='text-align:center;'>".$cr['CATEGORY_NAME']."</td>
									  <td style='text-align:center;'>".$cr['TAXABLE']."</td>
								  </tr>";
			}
		}
		else
		{
			$cat_result.="<tr style='cursor:pointer;' >
									<td style='text-align:center;' colspan='5'>  No records are available!</td>
								   </tr>";
		}
		$cat_result.="</tbody>";
		
		echo "<table class='table table-striped table-condensed table-bordered table-hover' >
            <thead>
              <tr class='exspeedite-bg'>
                <td class='text-center'><span class='text-muted'><span class='glyphicon glyphicon-unchecked'></span></span></td>
				 <th class='text-center'>Rate Code</th>
				 <th class='text-center'>Rate Description</th>
                <th class='text-center'>Rates</th>
				<th class='text-center'>Category</th>
				 <th class='text-center'>Taxable</th>
				</tr>
            </thead>
            ".$cat_result."
          </table>";
	}
	
	if($_GET['action']=='addprofile') {
		$check = $pro_master->fetch_rows("", "COALESCE(MAX(CONTRACT_ID) + 1, 1) AS NEXT_ID");
		if( is_array($check) && count($check) == 1 && ! empty($check[0]["NEXT_ID"])) {
			$next_id = $check[0]["NEXT_ID"];
			//echo "<p>next = $next_id</p>";
			$pro_master->add( array( 'CONTRACT_ID' => $next_id, 'PROFILE_NAME' => 'New Profile' ) );
		}
	//	reload_page ( "exp_rate_profile.php?TYPE=edit&CODE=".$next_id ); // throw into edit screen
		reload_page ( "exp_rate_profile.php" );
	} else


	if($_GET['action']=='deleteprofile')
	{
		$rate_profile_id=$_GET['profile_id'];
		if($rate_profile_id!="")
		{
			## delete from profile master table (main table)##
			$pro_master->delete_row("CONTRACT_ID=$rate_profile_id");	
			
			## now delete from profile rate table ##		
			$profile_range_obj->delete_row("PROFILE_ID=$rate_profile_id");	
			
			## now delete from 2 tables of profile manual //use get_multiple_rows($query) here ##
			$manual->database->get_multiple_rows("DELETE ".PROFILE_MANUAL.",".PROFILE_MANUAL_RATE." FROM ".PROFILE_MANUAL." JOIN ".PROFILE_MANUAL_RATE." ON ".PROFILE_MANUAL_RATE.".PRO_MANUAL_ID=".PROFILE_MANUAL.".MANUAL_ID WHERE ".PROFILE_MANUAL.".PROFILE_ID=".$rate_profile_id);
			
			## now delete from 2 tables of profile range//use get_multiple_rows($query) here ##
			$rate_obj->database->get_multiple_rows("DELETE ".PROFILE_RANGE.",".PROFILE_RANGE_RATE." FROM ".PROFILE_RANGE." JOIN ".PROFILE_RANGE_RATE." ON ".PROFILE_RANGE_RATE.".PRO_RANGE_ID=".PROFILE_RANGE.".RANGE_RATE WHERE ".PROFILE_RANGE.".PROFILE_ID=".$rate_profile_id);
			
			##delete from drvier assigned rates of this profile##
			$ass_obj->delete_row("PROFILE_ID=$rate_profile_id");	
			
			##delete from driver assigned manual rates##
			$sql_obj->delete_row("PROFILE_ID=$rate_profile_id");
			
			##delete from drvier range of rates table##
			$ren_obj->delete_row("PROFILE_ID=$rate_profile_id");
			
			## finally set profile id in drvier master table to 0##
			$driver_obj->update_row( "PROFILE_ID=".$rate_profile_id, array(array("field"=>"PROFILE_ID","value"=>"0")));
			
			echo 'OK';
			
		}
	}
	
	if(isset($_REQUEST['val']))
	{
		if($_REQUEST['action']=='fetchonemanualrate' && $_REQUEST['val']!='' )
		{
			/*$manual_id=$_REQUEST['val'];
			$manual_rates			=	$manual->database->get_multiple_rows(" SELECT  *  FROM ".MANUAL_RATES." WHERE MANUAL_ID='".$manual_id."'");	
			$arr	=	$manual_rates[0];
			
			echo json_encode($arr);*/
			
			$load_id=$_REQUEST['load_id'];
			$driver_id=$_REQUEST['driver_id'];
			$manual_id=$_REQUEST['val'];
			$prev_cnt=0;
			$arr=array();
			$status='';
			$manual_rates=$manual->database->get_multiple_rows(" SELECT  *  FROM ".MANUAL_RATES." WHERE MANUAL_ID='".$manual_id."'");
			//print_r($manual_rates);
			if(count($manual_rates)>0)
			{
				$prev_cnt=$man_obj->database->get_multiple_rows(" SELECT  count(*) as prev_cnt FROM ".LOAD_MAN_RATE." WHERE LOAD_ID='".$load_id."' AND DRIVER_ID='".$driver_id."' AND MANUAL_CODE='".$manual_rates[0]['MANUAL_RATE_CODE']."'");
			
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
		}
	}
	
	if(isset($_GET['profile_id']))
	{
		if($_GET['action']=='profilemanualrates' && $_GET['profile_id']!='')
		{
			$profile_id=$_GET['profile_id'];
			$manual_code=$_GET['manual_code'];
			$manual_name=urldecode($_GET['manual_name']);
			$manual_desc=urldecode($_GET['manual_desc']);
			$manual_rate=$_GET['manual_rate'];
			
			$ans=$profile_man_obj->get_insert_id("PRO_RATE_CODE,PRO_RATE_NAME,PRO_RATE_DESC,PRO_RATE","'$manual_code',\"".$manual_name."\",\"".$manual_desc."\",'$manual_rate'");
             $man_code->add_row("UNIQUE_MAN_CODE","'$manual_code'");			
			if($ans!="")
			{
				$manual->add_row("PROFILE_ID,MANUAL_ID","'$profile_id','$ans'");
				$driver_code=$driver_obj->database->get_multiple_rows("SELECT DRIVER_CODE FROM ".DRIVER_TABLE." WHERE  PROFILE_ID=".$profile_id);
				  if(count($driver_code)>0)
				{
					 foreach($driver_code as $dc)
					{
						$sql_obj->add_row("DRIVER_ID,MANUAL_RATE_CODE,MANUAL_NAME,MANUAL_RATE_DESC,MANUAL_RATE_RATE,PROFILE_ID","'".$dc['DRIVER_CODE']."','$manual_code',\"".$manual_name."\",\"".$manual_desc."\",'$manual_rate','$profile_id'");
					}
			   }
			 }
		}
	}
	
	if(isset($_GET['profile_id']))
	{
		if($_GET['action']=='profilerangerates' && $_GET['profile_id']!="")
		{
			$profile_id=$_GET['profile_id'];
			$range_name=urldecode($_GET['range_name']);
			$range_from=$_GET['range_from'];
			$range_to=$_GET['range_to'];
			$range_rate=$_GET['range_rate'];
			$zone_to=$_GET['zone_to'];
			$zone_from=$_GET['zone_from'];
			$range_rate_code=$_GET['range_rate_code'];
			$get_count=$range->database->get_multiple_rows("SELECT COUNT(*) AS CNT FROM ".PROFILE_RANGE_RATE." JOIN ".PROFILE_RANGE." ON ".PROFILE_RANGE.".	RANGE_RATE=".PROFILE_RANGE_RATE.".PRO_RANGE_ID WHERE PROFILE_ID='$profile_id' AND (('$range_from'  BETWEEN PRO_RANGE_FROM AND PRO_RANGE_TO) OR ('$range_to' BETWEEN PRO_RANGE_FROM AND PRO_RANGE_TO) OR (PRO_RANGE_FROM BETWEEN '$range_from' AND '$range_to')  OR (PRO_RANGE_TO BETWEEN '$range_from' AND '$range_to')) ");//AND PRO_ZONE_FROM='$zone_from' AND PRO_ZONE_TO='$zone_to'
			//echo '****'.$get_count[0]['CNT'];
			if($get_count[0]['CNT']==0)
			{
			$answer=$range->get_insert_id("PRO_RANGE_CODE,PRO_RANGE_NAME, PRO_RANGE_FROM, PRO_RANGE_TO, PRO_RANGE_RATE, PRO_ZONE_FROM, PRO_ZONE_TO","'$range_rate_code',\"".$range_name."\",'$range_from','$range_to','$range_rate','$zone_from','$zone_to'");
			
				 $ran_code->add_row("UNIQUE_RANGE_CODE","'$range_rate_code'");
			if($answer!="")
			{
				$rate_obj->add_row("PROFILE_ID,RANGE_RATE","'$profile_id','$answer'");
				 $driver_codes=$driver_obj->database->get_multiple_rows("SELECT DRIVER_CODE FROM ".DRIVER_TABLE." WHERE  PROFILE_ID=".$profile_id);
				  if(count($driver_codes)>0)
				{
					 foreach($driver_codes as $dc)
					{
						$ren_obj->add_row("DRIVER_ID,RANGE_CODE,RANGE_NAME,RANGE_FROM,RANGE_TO,RANGE_RATE,PROFILE_ID,ZONE_FROM,ZONE_TO","'".$dc['DRIVER_CODE']."','$range_rate_code',\"".$range_name."\",'$range_from','$range_to','$range_rate','$profile_id','$zone_from','$zone_to'");
					}
				}
			}
			}
			else
			{
				echo 'error';
			}
			
		}
	}
    
	if(isset($_GET['assigned_id']) && isset($_GET['profile_id']))
	{
		if($_GET['action']=='deleteprofilerates' && $_GET['assigned_id']!="" && $_GET['profile_id']!="")
		{
			$assigned_id=$_GET['assigned_id'];
			$profile_id=$_GET['profile_id'];
			
			$res=$profile_range_obj->database->get_one_row("SELECT  RATE_ID FROM ".PROFILE_RATE."  WHERE ".PROFILE_RATE.".PROFILE_ID=".$profile_id." AND ".PROFILE_RATE.".RATE_PROFILE_ID=".$assigned_id);
			
			$profile_range_obj->delete_row("PROFILE_ID=$profile_id AND RATE_PROFILE_ID=$assigned_id");
			
			if(count($res)>0)
			{
				//print_r($res);exit;
				$ass_obj->delete_row("PROFILE_ID=$profile_id AND RATE_ID=".$res['RATE_ID']."");
			}
		}
	}
	
	if(isset($_GET['manual_id']) && isset($_GET['profile_id']))
	{
		if($_GET['action']=='deleteprofilemanual' && $_GET['manual_id']!="" && $_GET['profile_id']!="")
		{
				$manual_id=$_GET['manual_id'];
				$profile_id=$_GET['profile_id'];
				
				/*$man=$profile_man_obj->database->get_one_row("SELECT  PRO_RATE_CODE FROM ".PROFILE_MANUAL_RATE." JOIN ".PROFILE_MANUAL." ON ".PROFILE_MANUAL.".MANUAL_ID=".PROFILE_MANUAL_RATE.".PRO_MANUAL_ID WHERE ".PROFILE_MANUAL.".PROFILE_ID=".$profile_id." AND ".PROFILE_MANUAL_RATE.".PRO_MANUAL_ID=".$manual_id);*/
				
				$man=$profile_man_obj->database->get_one_row("SELECT  PRO_RATE_CODE FROM ".PROFILE_MANUAL_RATE." WHERE  ".PROFILE_MANUAL_RATE.".PRO_MANUAL_ID=".$manual_id);
				
				$profile_man_obj->database->get_one_row("DELETE  ".PROFILE_MANUAL_RATE.",". PROFILE_MANUAL." FROM ".PROFILE_MANUAL_RATE." JOIN ".PROFILE_MANUAL." ON ".PROFILE_MANUAL.".MANUAL_ID=".PROFILE_MANUAL_RATE.".PRO_MANUAL_ID WHERE ".PROFILE_MANUAL.".PROFILE_ID=".$profile_id." AND ".PROFILE_MANUAL_RATE.".PRO_MANUAL_ID=".$manual_id);
				
				if(count($man)>0)
				{
						$sql_obj->delete_row("PROFILE_ID=$profile_id AND MANUAL_RATE_CODE='".$man['PRO_RATE_CODE']."'");
				}
			}
	}
	
	if(isset($_GET['profile_id']) && isset($_GET['range_id']))
	{
		if($_GET['action']=='deleteprofile_range' && $_GET['profile_id']!="" && $_GET['range_id']!="")
		{
				$range_id=$_GET['range_id'];
				$profile_id=$_GET['profile_id'];
				
				$range_code=$range->database->get_one_row("SELECT PRO_RANGE_CODE FROM ".PROFILE_RANGE_RATE." WHERE  PRO_RANGE_ID=".$range_id);
				//print_r($range_id);
				$range->database->get_one_row(" DELETE ".PROFILE_RANGE_RATE.",".PROFILE_RANGE." FROM ".PROFILE_RANGE_RATE." JOIN ".PROFILE_RANGE." ON ".PROFILE_RANGE.".RANGE_RATE =".PROFILE_RANGE_RATE.".PRO_RANGE_ID WHERE ".PROFILE_RANGE.".PROFILE_ID=".$profile_id." AND ".PROFILE_RANGE_RATE.".PRO_RANGE_ID=".$range_id);
				
				if(count($range_code)>0)
				{
					$ren_obj->delete_row("PROFILE_ID=$profile_id AND RANGE_CODE='".$range_code['PRO_RANGE_CODE']."'");
				}
		}
	}
	
	if(isset($_GET['rate_id']))
	{
		if($_GET['action']=='deleteclientrates' && $_GET['rate_id']!="")
		{
			$assigned_rate=$_GET['rate_id'];
			$client_obj->delete_row("ASSIGN_ID=$assigned_rate");
			echo 'OK';
		}
	}
	
	if(isset($_GET['assigned_id']))
	{
		if($_GET['action']=='deleteclientfsc' && $_GET['assigned_id']!="")
		{
			$assigned_id=$_GET['assigned_id'];
			$client_fsc->delete_row("ASSIGN_FSC_ID=$assigned_id");
			echo 'OK';
		}
	}

	 //! SCR# 188 - Revised delete driver pay
	if(isset($_GET['id']) && isset($_GET['driver_id'])) {
		if($_GET['action']=='deletesettlement' && $_GET['id']!="" && $_GET['driver_id']!="") {
			$pay_id=$_GET['id'];
			$driver_id=$_GET['driver_id'];
			if( $sts_debug ) echo "<p>deletesettlement: pay_id = $pay_id driver_id = $driver_id</p>";

			$load_pay_master=new sts_table($exspeedite_db , LOAD_PAY_MASTER , $sts_debug);
			$load_pay_rate=new sts_table($exspeedite_db , LOAD_PAY_RATE , $sts_debug);
			$load_man_rate=new sts_table($exspeedite_db , LOAD_MAN_RATE , $sts_debug);
			$load_range_rate=new sts_table($exspeedite_db , LOAD_RANGE_RATE , $sts_debug);
		
			// SCR# 188 - Use the $pay_id to find the WEEKEND_FROM and WEEKEND_TO
			$get_week=$driver->database->get_one_row("SELECT WEEKEND_FROM, WEEKEND_TO
				FROM ".DRIVER_PAY_MASTER." WHERE DRIVER_PAY_ID=".$pay_id);
			if( $sts_debug ) {
				echo "<pre>get_week\n";
				var_dump($get_week);
				echo "</pre>";
			}

			$pay_ids = false;
			$load_ids = false;
			$trip_ids = false;
			if( is_array($get_week) && count($get_week)>0 ) {
				// DRIVER_PAY_MASTER
				// Get all matching DRIVER_PAY_ID, LOAD_ID, TRIP_ID
				$check = $driver->fetch_rows("WEEKEND_FROM='".$get_week['WEEKEND_FROM']."'
					AND WEEKEND_TO='".$get_week['WEEKEND_TO']."'
					AND DRIVER_ID='$driver_id'", "DRIVER_PAY_ID, LOAD_ID, TRIP_ID");
				if( $sts_debug ) {
					echo "<pre>check\n";
					var_dump($check);
					echo "</pre>";
				}
					
				// SCR# 188 - Collect lists of DRIVER_PAY_ID, LOAD_ID, TRIP_ID
				if( is_array($check) && count($check) > 0 ) {
					$pay_ids = array();
					$load_ids = array();
					$trip_ids = array();
					foreach($check as $row) {
						$pay_ids[] = $row["DRIVER_PAY_ID"];
						if( $row["LOAD_ID"] > 0 ) $load_ids[] = $row["LOAD_ID"];
						if( $row["TRIP_ID"] > 0 ) $trip_ids[] = $row["TRIP_ID"];
					}
					if( $sts_debug ) {
						echo "<pre>pay_ids, load_ids, trip_ids\n";
						var_dump($pay_ids);
						var_dump($load_ids);
						var_dump($trip_ids);
						echo "</pre>";
					}
				}
			}
			
			if( is_array($pay_ids) && count($pay_ids) > 0 ) {
				// SCR# 188 - delete rows in DRIVER_PAY_MASTER
				$driver->delete_row("DRIVER_ID = ".$driver_id."
					AND DRIVER_PAY_ID IN (".implode(',', $pay_ids).")");
				// SCR# 188 - delete rows in LOAD_PAY_MASTER
				$load_pay_master->delete_row("DRIVER_ID = ".$driver_id."
					AND LOAD_PAY_ID IN (".implode(',', $pay_ids).")");
			}
				
			// SCR# 188 - Remove details for loads
			if( is_array($load_ids) && count($load_ids) > 0 ) {
				// LOAD_PAY_RATE
				$load_pay_rate->delete_row("DRIVER_ID = ".$driver_id."
					AND LOAD_ID IN (".implode(',', $load_ids).")");
				
				// LOAD_MAN_RATE
				$load_man_rate->delete_row("DRIVER_ID = ".$driver_id."
					AND LOAD_ID IN (".implode(',', $load_ids).")");
				
				// LOAD_RANGE_RATE
				$load_range_rate->delete_row("DRIVER_ID = ".$driver_id."
					AND LOAD_ID IN (".implode(',', $load_ids).")");
			}
			
			// SCR# 188 - This is for load 0, we use TRIP_ID instead
			if( is_array($trip_ids) && count($trip_ids) > 0 ) {
				// LOAD_PAY_RATE
				$load_pay_rate->delete_row("DRIVER_ID = ".$driver_id."
					AND TRIP_ID IN (".implode(',', $trip_ids).")");
				
				// LOAD_MAN_RATE
				$load_man_rate->delete_row("DRIVER_ID = ".$driver_id."
					AND TRIP_ID IN (".implode(',', $trip_ids).")");
				
				// LOAD_RANGE_RATE
				$load_range_rate->delete_row("DRIVER_ID = ".$driver_id."
					AND TRIP_ID IN (".implode(',', $trip_ids).")");
			}
					
		echo 'OK';
		}
		
	}
	
	//!  delete client handling rates----------##
	if(isset($_GET['handling_id']))
	{
		if($_GET['action']=='deleteclienthandling' && $_GET['handling_id']!="")
		{
			$handling_id=$_GET['handling_id'];
			$handle_obj->delete_row("HANDLING_ID=$handling_id");
			echo 'OK';
		}
	}
	
	##-------delete client frieght rates---------##
	if(isset($_GET['frieght_id']))
	{
		if($_GET['action']=='deleteclientfrieghtrate' && $_GET['frieght_id']!="")
		{
			$frieght_id=$_GET['frieght_id'];
			$fr_obj->delete_row("FRIGHT_ID=$frieght_id");
			echo 'OK';
		}
	}
	
	//! - update total miles of settlement------------##
	
	if(isset($_GET['load_id']))
	{
		if($_GET['action']=='updatetotalmiles' && $_GET['load_id']!="")
		{
			$load_id=$_GET['load_id'];
			$miles=$_GET['miles'];
			$driver_id=$_GET['driver_id'];
			$odometer_from=$_GET['odometer_from'];
			$odometer_to=$_GET['odometer_to'];
			$load_obj->update_row('LOAD_CODE='.$load_id ,array( array('field'=>'ACTUAL_DISTANCE','value'=>$miles)));
			$load_obj->update_row('LOAD_CODE='.$load_id ,array( array('field'=>'ODOMETER_FROM','value'=>$odometer_from)));
			$load_obj->update_row('LOAD_CODE='.$load_id ,array( array('field'=>'ODOMETER_TO','value'=>$odometer_to)));
			echo 'OK';
		}
	}
	
	//!  delete fsc--------##
	if(isset($_GET['fsc_unique_id']))
	{
		if($_GET['action']=='deletefsc' && $_GET['fsc_unique_id']!="")
		{
			$fsc_unique_id=$_GET['fsc_unique_id'];
			$fsc_obj->delete_row("FSC_UNIQUE_ID='".$fsc_unique_id."'");
			$client_fsc->delete_row("FSC_ID='".$fsc_unique_id."'");
			echo 'OK';
			
		}
	}
	
	##---- delete single fsc record ------##
	if(isset($_GET['fsc_id']))
	{
		if($_GET['action']=='deletesinglefsc' && $_GET['fsc_id']!="")
		{
			$fsc_id=$_GET['fsc_id'];
			$fsc_obj->delete_row("FSC_ID=$fsc_id");
			echo 'OK';
		}
	}
	
	##-----check duplication of pallet rates----##
	if(isset($_GET['pal_id']))
	{
		if($_GET['action']=='checkduplicatepallet' && $_GET['pal_id']!="")
		{
			$pal_id=$_GET['pal_id'];
			$pal_from=$_GET['pal_from'];
			$pal_to=$_GET['pal_to'];
		/*	$get_count=$ren_obj->database->get_multiple_rows("SELECT COUNT(*) AS CNT FROM ".RANGE_RATES." WHERE DRIVER_ID='$range_driver_id' AND (('$range_from'  BETWEEN RANGE_FROM AND RANGE_TO) OR ('$range_to' BETWEEN RANGE_FROM AND RANGE_TO) OR (RANGE_FROM BETWEEN '$range_from' AND '$range_to')  OR (RANGE_TO BETWEEN '$range_from' AND '$range_to')) AND ZONE_FROM='$zone_from' AND ZONE_TO='$zone_to'");*/
			$get_count=$pall_obj->database->get_multiple_rows("SELECT COUNT(*) AS CNT FROM ".PALLET_RATE." WHERE PALLET_ID='".$pal_id."' AND (('".$pal_from."' BETWEEN PALLET_FROM AND PALLET_TO) OR ('".$pal_to."' BETWEEN PALLET_FROM AND PALLET_TO) OR (PALLET_FROM BETWEEN '".$pal_from."' AND '".$pal_to."') OR  (PALLET_FROM BETWEEN '".$pal_from."' AND '".$pal_to."'))");
			echo $get_count[0]['CNT'];
			
		}
	}
	
	##-----fetch all pallets rates for that customer----##
	if(isset($_REQUEST['pallet_id']) && $_REQUEST['action']=='fetchpallets' && $_REQUEST['pallet_id']!="")
	{
		$pallet_id=$_REQUEST['pallet_id'];
		$result=$pall_obj->database->get_multiple_rows("SELECT * FROM ".PALLET_RATE." WHERE PALLET_ID=".$pallet_id);
		$str='</form><td colspan="6"><table>';
		if(count($result)>0)
		{
			foreach($result as $rs)
			{
				$pal_frm=$pal_to='';
				if($rs['PALLET_FROM']=='11')
				{
					$pal_frm='10+';
				}
				else
				{
					$pal_frm=$rs['PALLET_FROM'];
				}
				if($rs['PALLET_TO']=='11')
				{
					$pal_to='10+';
				}
				else
				{
					$pal_to=$rs['PALLET_TO'];
				}
				$for_loop="";
				for($i=01;$i<=11;$i++)
				{
					$sel='';
					if($rs['PALLET_FROM']==$i)
					{$sel='selected="selected"';}
					if($i==11)
					{$for_loop.='<option value='.$i.' '.$sel.'>10+</option>';
					}
					else
					{$for_loop.='<option value='.$i.' '.$sel.'>'.$i.'</option>';}
				}
				
				$to_loop="";
				for($i=01;$i<=11;$i++)
				{
					$sel1='';
					if($rs['PALLET_TO']==$i)
					{$sel1='selected="selected"';}
					if($i==11)
					{$to_loop.='<option value='.$i.' '.$sel1.'>10+</option>';
					}
					else
					{$to_loop.='<option value='.$i.' '.$sel1.'>'.$i.'</option>';}
				}
				$str.='<tr><td style="font-size:12px; font-family:Arial, Helvetica, sans-serif; text-transform:uppercase; padding:0 6px;">Pallet From</td>
				<td><input type="text" readonly="readonly" value="'.$pal_frm.'" class="form-control"  id="textfield" name="textfield"></td>
				<td style="font-size:12px; font-family:Arial, Helvetica, sans-serif; text-transform:uppercase; padding:0 6px;">Pallet To</td>
				<td><input type="text" readonly="readonly" value="'.$pal_to.'" class="form-control"  id="textfield" name="textfield"></td>
				<td style="font-size:12px; font-family:Arial, Helvetica, sans-serif; text-transform:uppercase; padding:0 6px;">Pallet Rate</td>
				<td><input type="text" readonly="readonly" value="'.$rs['PALLET_RATE'].'" class="form-control" id="textfield" name="textfield"></td>
				<td style="font-size:12px; font-family:Arial, Helvetica, sans-serif; text-transform:uppercase; padding:0 6px;">&nbsp;</td>
				<td><a onclick="javascript: return delete_single_pallet('.$rs['PALLET_RATE_ID'].')" class="btn btn-default btn-xs "><span class="glyphicon glyphicon-trash"></span></a>		               &nbsp;&nbsp;<a data-toggle="modal" data-target="#pall_'.$rs['PALLET_RATE_ID'].'" class="btn btn-default btn-xs "><span class="glyphicon glyphicon-edit"></span></a></td>
				<tr>
				<tr>
				<td style="font-size:12px; font-family:Arial, Helvetica, sans-serif; text-transform:uppercase; padding:0 6px;">
<div class="modal fade in" id="pall_'.$rs['PALLET_RATE_ID'].'" >
<div class="modal-dialog modal-lg">
<div class="modal-content form-horizontal" role="main">
<div class="modal-header">
<h4 class="modal-title">Update Pallet Rates</h4>
</div>
<div class="modal-body" style="font-size: 14px; body:inherit;">
<form name="update_pall_rate'.$rs['PALLET_RATE_ID'].'" id="update_pall_rate'.$rs['PALLET_RATE_ID'].'"  method="post">
<div class="btn-group">
<a href="#" class="btn btn-md btn-default" data-dismiss="modal"><span class="glyphicon glyphicon-remove"></span>Close</a>
<!--<input type="submit" name="btn_update_det_rate" id="btn_update_det_rate" class="btn btn-md btn-success"   value="Update Rates"/>-->
<button type="submit" value=" Update Rates"  name="btn_update_pall_rate" id="btn_update_pall_rate" class="btn btn-md btn-success"  onclick="javascript:return check_edit_pall('.$rs['PALLET_RATE_ID'].');"/>Update Rates
</div>
<div id="loader" style="display:none; position:absolute;margin-left:250px;"><img src="images/loading.gif" /></div>
<div class="form-group">
<input class="form-control text-right" name="ED_PALL_ID" id="ED_PALL_ID_'.$rs['PALLET_RATE_ID'].'" type="hidden"  placeholder="" value="'.$rs['PALLET_ID'].'" >
<input class="form-control text-right" name="ED_PAL_RATE_ID" id="ED_PAL_RATE_ID" type="hidden"  placeholder="" value="'.$rs['PALLET_RATE_ID'].'" >
			
<div class="form-group">
				<label for="PALL_HOURS" class="col-sm-4 control-label">Pallets From</label>
				<div class="col-sm-4">
				<select class="form-control" name="ED_PALLETS_FROM" id="ED_PALLETS_FROM_'.$rs['PALLET_RATE_ID'].'">
				'.$for_loop.'
				</select>
				</div>
			</div>
			
			<div class="form-group">
				<label for="PALL_HOURS" class="col-sm-4 control-label">Pallets To</label>
				<div class="col-sm-4">
				<select class="form-control" name="ED_PALLETS_TO" id="ED_PALLETS_TO_'.$rs['PALLET_RATE_ID'].'">
				'.$to_loop.'
				</select>
				</div>
			</div>
<div class="form-group">
				<label for="Pall Rates" class="col-sm-4 control-label">Detention Rate</label>
				<div class="col-sm-4">
			   <input class="form-control text-right" name="ED_PALLETS_RATE" id="ED_PALLETS_RATE_'.$rs['PALLET_RATE_ID'].'" type="text" placeholder="PALLET RATES" value="'.$rs['PALLET_RATE'].'" >
				</div>
			</div>
			</div>
			</form>
</div>
<div class="modal-footer">
</div>
</div><!-- /.modal-content -->
</div><!-- /.modal-dialog -->
</div>
				</td>
				</tr>';
			}
		}
		else
		{
			$str.='<tr><td colspan="6">No Pallet Rates are added.</td></tr>';
		}
		$str.='</table></td>';
		echo $str;exit;
		
	}
	//echo '***'.$_REQUEST['action'];
	
	##----- delete pallet rates -----##
	if(isset($_REQUEST['pallet_ids']))
	{
		if($_REQUEST['action']=='deletepallettrates' && $_REQUEST['pallet_ids']!="")
		{
			$pallet_id=$_REQUEST['pallet_ids'];
			$pall_rate_obj->delete_row("PALLET_ID=$pallet_id");
			$pall_obj->delete_row("PALLET_ID=$pallet_id");
			echo 'OK';
		}
	}
	
	##------ check duplication of  loaded detention rates---##
    if(isset($_REQUEST['detention_id']) && $_REQUEST['detention_id']!="" && $_REQUEST['action']=='checkdetention')
	{
		$det_id=$_REQUEST['detention_id'];
		$det_hr=$_REQUEST['det_hr'];
		$det_hr_to=$_REQUEST['det_hr_to'];
		//DET_HOUR=".$det_hr
		/*$get_count=$det_rate->database->get_multiple_rows("SELECT COUNT(*) AS CNT FROM ".DETENTION_RATE." WHERE DETENTION_ID=".$det_id." AND (('".$det_hr."' BETWEEN DET_HOUR AND DET_HOUR_TO) OR ('".$det_hr_to."' BETWEEN DET_HOUR AND DET_HOUR_TO) OR (DET_HOUR BETWEEN '".$det_hr."' AND '".$det_hr_to."') OR (DET_HOUR_TO BETWEEN '".$det_hr."' AND '".$det_hr_to."'))");*/
		
		$get_count=$det_rate->database->get_multiple_rows("SELECT COUNT(*) AS CNT FROM ".DETENTION_RATE." WHERE DETENTION_ID=".$det_id." AND ((".$det_hr." >DET_HOUR AND ".$det_hr."< DET_HOUR_TO) OR (".$det_hr_to."  >DET_HOUR AND ".$det_hr_to." < DET_HOUR_TO) OR (DET_HOUR >".$det_hr." AND DET_HOUR < ".$det_hr_to.") OR (DET_HOUR_TO >".$det_hr." AND DET_HOUR_TO < ".$det_hr_to."))");
		
		//echo "SELECT COUNT(*) AS CNT FROM ".DETENTION_RATE." WHERE DETENTION_ID=".$det_id." AND ((".$det_hr." >DET_HOUR AND ".$det_hr."< DET_HOUR_TO) OR (".$det_hr_to."  >DET_HOUR AND ".$det_hr_to." < DET_HOUR_TO) )";
		echo $get_count[0]['CNT'];
		
	}
	
	##------ check duplication of unloaded detention rates---##
    if(isset($_REQUEST['detent_id']) && $_REQUEST['detent_id']!="" && $_REQUEST['action']=='checkunloadeddetention')
	{
		$det_id=$_REQUEST['detent_id'];
		$un_det_hr=$_REQUEST['un_det_hr'];
		$un_det_hr_to=$_REQUEST['un_det_hr_to'];
		
		/*$prev_count=$un_det_rate->database->get_multiple_rows("SELECT COUNT(*) AS UN_CNT FROM ".UNLOADED_DETENTION_RATE." WHERE DETENTION_ID=".$det_id." AND (('".$un_det_hr."' BETWEEN UNDET_HR_FROM AND UNDET_HR_TO) OR ('".$un_det_hr_to."' BETWEEN UNDET_HR_FROM AND UNDET_HR_TO) OR (UNDET_HR_FROM BETWEEN '".$un_det_hr."' AND '".$un_det_hr_to."') OR (UNDET_HR_TO BETWEEN '".$un_det_hr."' AND '".$un_det_hr_to."'))");*/
		$prev_count=$un_det_rate->database->get_multiple_rows("SELECT COUNT(*) AS UN_CNT FROM ".UNLOADED_DETENTION_RATE." WHERE DETENTION_ID=".$det_id." AND ((".$un_det_hr." >UNDET_HR_FROM AND ".$un_det_hr."< UNDET_HR_TO) OR (".$un_det_hr_to."  >UNDET_HR_FROM AND ".$un_det_hr_to." < UNDET_HR_TO) OR (UNDET_HR_FROM >".$un_det_hr." AND UNDET_HR_FROM < ".$un_det_hr_to.") OR (UNDET_HR_TO >".$un_det_hr." AND UNDET_HR_TO < ".$un_det_hr_to."))");
		
		echo $prev_count[0]['UN_CNT'];exit;
		
	}
	
	
	
	##-------fetch all  loaded detentions -------##
    if(isset($_REQUEST['det_id']) && $_REQUEST['action']=='fetchdetention')	
	{
		
		$det_id=$_REQUEST['det_id'];
		$result=$det_rate->database->get_multiple_rows("SELECT * FROM ".DETENTION_RATE." WHERE DETENTION_ID=".$det_id);
		$str='</form><td colspan="6"><table>';
		if(count($result)>0)
		{
			foreach($result as $rs)
			{				
				$str.='<tr><td style="font-size:12px; font-family:Arial, Helvetica, sans-serif; text-transform:uppercase; padding:0 6px;">Start Detention From</td>
				<td><input type="text" readonly="readonly" value="'.$rs['DET_HOUR'].'" class="form-control text-right"  id="textfield" name="textfield"></td>
				<td style="font-size:12px; font-family:Arial, Helvetica, sans-serif; text-transform:uppercase; padding:0 6px;">  To</td>
				<td><input type="text" readonly="readonly" value="'.$rs['DET_HOUR_TO'].'" class="form-control text-right"  id="textfield" name="textfield"></td>
				<td style="font-size:12px; font-family:Arial, Helvetica, sans-serif; text-transform:uppercase; padding:0 6px;">Detention Rate</td>
				<td><input type="text" readonly="readonly" value="'.$rs['DET_RATE'].'" class="form-control text-right"  id="textfield" name="textfield"></td>
				<td>&nbsp;&nbsp;</td>
				<td><a onclick="javascript: return delete_single_detention('.$rs['DET_HR_ID'].')" class="btn btn-default btn-xs "><span class="glyphicon glyphicon-trash"></span></a>&nbsp;&nbsp;<a data-toggle="modal" data-target="#det_'.$rs['DET_HR_ID'].'" class="btn btn-default btn-xs "><span class="glyphicon glyphicon-edit"></span></a></td>
				<tr>
				<tr>
				<td style="font-size:12px; font-family:Arial, Helvetica, sans-serif; text-transform:uppercase; padding:0 6px;">
<div class="modal fade in" id="det_'.$rs['DET_HR_ID'].'" >
<div class="modal-dialog modal-lg">
<div class="modal-content form-horizontal" role="main">
<div class="modal-header">
<h4 class="modal-title">Update Loaded Detention Rates</h4>
</div>
<div class="modal-body" style="font-size: 14px; body:inherit;">
<form name="update_det_rate'.$rs['DET_HR_ID'].'" id="update_det_rate'.$rs['DET_HR_ID'].'"  method="post">
<div class="btn-group">
<a href="#" class="btn btn-md btn-default" data-dismiss="modal"><span class="glyphicon glyphicon-remove"></span>Close</a>
<!--<input type="submit" name="btn_update_det_rate" id="btn_update_det_rate" class="btn btn-md btn-success"   value="Update Rates"/>-->
<button type="submit" value=" Update Rates"  name="btn_update_det_rate" id="btn_update_det_rate" class="btn btn-md btn-success"  onclick="javascript:return check_edit_det('.$rs['DET_HR_ID'].');"/>Update Rates
</div>
<div id="loader" style="display:none; position:absolute;margin-left:250px;"><img src="images/loading.gif" /></div>
<div class="form-group">
<input class="form-control text-right" name="ED_DET_ID" id="ED_DET_ID" type="hidden"  placeholder="" value="'.$rs['DETENTION_ID'].'" >
<input class="form-control text-right" name="ED_DET_HR_ID" id="ED_DET_HR_ID" type="hidden"  placeholder="" value="'.$rs['DET_HR_ID'].'" >
			
<div class="form-group">
				<label for="ED_DET_HOUR_'.$rs['DET_HR_ID'].'" class="col-sm-4 control-label">Detention From</label>
				<div class="col-sm-4">
			   <input class="form-control text-right" name="ED_DET_HOUR" id="ED_DET_HOUR_'.$rs['DET_HR_ID'].'" type="number" step="0.01"  value="'.$rs['DET_HOUR'].'" required>
				</div>
			</div>
			
			<div class="form-group">
				<label for="ED_DET_HOUR_TO_'.$rs['DET_HR_ID'].'" class="col-sm-4 control-label">Detention To</label>
				<div class="col-sm-4">
			   <input class="form-control text-right" name="ED_DET_HOUR_TO" id="ED_DET_HOUR_TO_'.$rs['DET_HR_ID'].'" type="number" step="0.01" value="'.$rs['DET_HOUR_TO'].'" required>
				</div>
			</div>
<div class="form-group">
				<label for=id="ED_DET_RATE_'.$rs['DET_HR_ID'].'" class="col-sm-4 control-label">Detention Rate</label>
				<div class="col-sm-4">
			   <input class="form-control text-right" name="ED_DET_RATE" id="ED_DET_RATE_'.$rs['DET_HR_ID'].'" type="text" value="'.$rs['DET_RATE'].'" >
				</div>
			</div>
			</div>
			</form>
</div>
<div class="modal-footer">
</div>
</div><!-- /.modal-content -->
</div><!-- /.modal-dialog -->
</div>
				</td>
				</tr>';
			}
		}
		else
		{
			$str.='<tr><td colspan="6">No Loaded Detention Rates are added.</td></tr>';
		}
		$str.='</table></td>';
		echo $str;
		
	exit;
	}
	
	
	##-------fetch all  unloaded detentions -------##
    if(isset($_REQUEST['det_id']) && $_REQUEST['action']=='fetchunloadeddetention')	
	{
		
		$det_id=$_REQUEST['det_id'];
		$result=$un_det_rate->database->get_multiple_rows("SELECT * FROM ".UNLOADED_DETENTION_RATE." WHERE DETENTION_ID=".$det_id);
		$str='</form><td colspan="6"><table>';
		if(count($result)>0)
		{
			foreach($result as $rs)
			{
				$str.='<tr><td style="font-size:12px; font-family:Arial, Helvetica, sans-serif; text-transform:uppercase; padding:0 6px;">Start Detention From</td>
				<td><input type="text" readonly="readonly" value="'.$rs['UNDET_HR_FROM'].'" class="form-control text-right"  id="textfield" name="textfield"></td>
				<td style="font-size:12px; font-family:Arial, Helvetica, sans-serif; text-transform:uppercase; padding:0 6px;">  To</td>
				<td><input type="text" readonly="readonly" value="'.$rs['UNDET_HR_TO'].'" class="form-control text-right"  id="textfield" name="textfield"></td>
				<td style="font-size:12px; font-family:Arial, Helvetica, sans-serif; text-transform:uppercase; padding:0 6px;">Detention Rate</td>
				<td><input type="text" readonly="readonly" value="'.$rs['UN_DET_RATE'].'" class="form-control text-right"  id="textfield" name="textfield"></td>
				<td>&nbsp;&nbsp;</td>
				<td><a onclick="javascript: return delete_single_unload_detention('.$rs['UN_DET_HR_ID'].')" class="btn btn-default btn-xs "><span class="glyphicon glyphicon-trash"></span></a>&nbsp;&nbsp;<a data-toggle="modal" data-target="#un_det_'.$rs['UN_DET_HR_ID'].'" class="btn btn-default btn-xs "><span class="glyphicon glyphicon-edit"></span></a></td>
				<tr>
				<tr>
				<td style="font-size:12px; font-family:Arial, Helvetica, sans-serif; text-transform:uppercase; padding:0 6px;">
<div class="modal fade in" id="un_det_'.$rs['UN_DET_HR_ID'].'" >
<div class="modal-dialog modal-lg">
<div class="modal-content form-horizontal" role="main">
<div class="modal-header">
<h4 class="modal-title">Update Unloaded Detention Rates</h4>
</div>
<div class="modal-body" style="font-size: 14px; body:inherit;">
<form name="update_det_rate'.$rs['UN_DET_HR_ID'].'" id="update_det_rate'.$rs['UN_DET_HR_ID'].'"  method="post">
<div class="btn-group">
<a href="#" class="btn btn-md btn-default" data-dismiss="modal"><span class="glyphicon glyphicon-remove"></span>Close</a>
<!--<input type="submit" name="btn_update_det_rate" id="btn_update_det_rate" class="btn btn-md btn-success"   value="Update Rates"/>-->
<button type="submit" value=" Update Rates"  name="btn_update_det_rate" id="btn_update_det_rate" class="btn btn-md btn-success"  onclick="javascript:return check_edit_unloaded_det('.$rs['UN_DET_HR_ID'].');"/>Update Rates
</div>
<div id="loader" style="display:none; position:absolute;margin-left:250px;"><img src="images/loading.gif" /></div>
<div class="form-group">
<input class="form-control text-right" name="ED_UN_DET_ID" id="ED_UN_DET_ID" type="hidden"  placeholder="" value="'.$rs['DETENTION_ID'].'" >
<input class="form-control text-right" name="ED_UN_DET_HR_ID" id="ED_UN_DET_HR_ID" type="hidden"  placeholder="" value="'.$rs['UN_DET_HR_ID'].'" >
			
<div class="form-group">
				<label for="ED_UN_DET_HOUR_'.$rs['UN_DET_HR_ID'].'" class="col-sm-4 control-label">Detention From</label>
				<div class="col-sm-4">
			   <input class="form-control text-right" name="ED_UN_DET_HOUR" id="ED_UN_DET_HOUR_'.$rs['UN_DET_HR_ID'].'" type="number" step="0.01"  value="'.$rs['UNDET_HR_FROM'].'" required>
				</div>
			</div>
			
			<div class="form-group">
				<label for="ED_UN_DET_HOUR_TO_'.$rs['UN_DET_HR_ID'].'" class="col-sm-4 control-label">Detention To</label>
				<div class="col-sm-4">
			   <input class="form-control text-right" name="ED_UN_DET_HOUR_TO" id="ED_UN_DET_HOUR_TO_'.$rs['UN_DET_HR_ID'].'" type="number" step="0.01" value="'.$rs['UNDET_HR_TO'].'" required>
				</div>
			</div>
<div class="form-group">
				<label for="ED_UN_DET_RATE_'.$rs['UN_DET_HR_ID'].'" class="col-sm-4 control-label">Detention Rate</label>
				<div class="col-sm-4">
			   <input class="form-control text-right" name="ED_UN_DET_RATE" id="ED_UN_DET_RATE_'.$rs['UN_DET_HR_ID'].'" type="text" placeholder="DETENTION RATES" value="'.$rs['UN_DET_RATE'].'" >
				</div>
			</div>
			</div>
			</form>
</div>
<div class="modal-footer">
</div>
</div><!-- /.modal-content -->
</div><!-- /.modal-dialog -->
</div>
				</td>
				</tr>';
			}
		}
		else
		{
			$str.='<tr><td colspan="6">No Unloaded Detention Rates are added.</td></tr>';
		}
		$str.='</table></td>';
		echo $str;exit;
		
	
	}
	
	##-------delete  loaded detentions------##
	if(isset($_REQUEST['det_ids']) && $_REQUEST['action']=='deletedetentions')
	{
		$det_id=$_REQUEST['det_ids'];
		$det_obj->delete_row("DETENTION_ID=$det_id");
		$det_rate->delete_row("DETENTION_ID=$det_id");
		$un_det_rate->delete_row("DETENTION_ID=$det_id");
		echo 'OK';
	}
		##-------delete  single loaded  detention rate------##
	if(isset($_REQUEST['single_det_id']) && $_REQUEST['action']=='deletesingledetentions')
	{
		$det_id=$_REQUEST['single_det_id'];
		$det_rate->delete_row("DET_HR_ID=$det_id");
		echo 'OK';
	}
	
	##-------delete  single unloaded  detention rate------##
	if(isset($_REQUEST['det_id']) && $_REQUEST['action']=='deleteunloadeddetentions')
	{
		$det_id=$_REQUEST['det_id'];
		$un_det_rate->delete_row("UN_DET_HR_ID=$det_id");
		echo 'OK';
	}
	
	##-------delete single pallet rate ------##
	if(isset($_REQUEST['single_pal_id']) && $_REQUEST['action']=='deletesinglepallet')
	{
		$single_pal_id=$_REQUEST['single_pal_id'];
		$pall_obj->delete_row("PALLET_RATE_ID=$single_pal_id");
		echo 'OK';
	}
	
	##------ calculate detention rates for billing screen---------##
	if(isset($_REQUEST['client_id']) && $_REQUEST['action']=='caldetentionrate')
	{
		$client_id=$_REQUEST['client_id'];
		$cons_name=$_REQUEST['cons_name'];
		$det_hr=$_REQUEST['det_hr'];
		$user_type='client';
		$ret_arr=array();
		$det_id=$det_obj->database->get_one_row("SELECT * FROM ".DETENTION_MASTER." WHERE CLIENT_ID='".$client_id."' AND USER_TYPE='".$user_type."' ");
		//AND 	DET_CUST_NAME='".$det_obj->real_escape_string($cons_name)."'
		$min_det_rate=0;
			if(count($det_id)>0 && $det_id['DETENTION_ID']!="")
			{
				$min_det=$det_rate->database->get_one_row("SELECT MIN(DET_HOUR_TO)  AS MIN_DET FROM ".DETENTION_RATE." WHERE DETENTION_ID=".$det_id['DETENTION_ID']." AND DET_RATE =0.00");
				if(count($min_det)>0 && $min_det['MIN_DET']!="")
				{ $min_det_rate=$min_det['MIN_DET'];}
				$det_ratess=$det_rate->database->get_multiple_rows("SELECT * FROM ".DETENTION_RATE." WHERE DETENTION_ID=".$det_id['DETENTION_ID']." AND (".$det_hr." BETWEEN DET_HOUR AND DET_HOUR_TO)");
			
				if(count($det_ratess)>0 && $det_ratess[0]['DET_RATE']!="")
				{
					//echo $det_ratess[0]['DET_RATE'];
					$ret_arr=array('ans'=>'yes','detrate'=>$det_ratess[0]['DET_RATE'],'mindetention'=>$min_det_rate);
				}
				else
				{
					//echo '0';
					$ret_arr=array('ans'=>'no','detrate'=>0,'mindetention'=>$min_det_rate);
				}
			}
			else
			{
				//echo '0';
				$ret_arr=array('ans'=>'no','detrate'=>0,'mindetention'=>'');
			}
			
			echo json_encode($ret_arr);exit;
		}
		
		##------ calculate unloaded detention rates for billing screen---------##
	if(isset($_REQUEST['client_id']) && $_REQUEST['action']=='calunloadeddetentionrate')
	{
		$client_id=$_REQUEST['client_id'];
		$cons_name=$_REQUEST['cons_name'];
		$un_det_hr=$_REQUEST['un_det_hr'];
		$user_type='client';
		$ret_array=array();
		$det_id=$det_obj->database->get_one_row("SELECT * FROM ".DETENTION_MASTER." WHERE CLIENT_ID='".$client_id."' AND USER_TYPE='".$user_type."' ");
	
		$unmin_det_rate=0;
			if(count($det_id)>0 && $det_id['DETENTION_ID']!="")
			{
				$un_min_det=$un_det_rate->database->get_one_row("SELECT MIN(UNDET_HR_TO)  AS MIN_DET FROM ".UNLOADED_DETENTION_RATE." WHERE DETENTION_ID=".$det_id['DETENTION_ID']." AND UN_DET_RATE =0.00");
				if(count($un_min_det)>0 && $un_min_det['MIN_DET']!="")
				{ $unmin_det_rate=$un_min_det['MIN_DET'];}
				$unload_det_rate=$det_rate->database->get_multiple_rows("SELECT * FROM ".UNLOADED_DETENTION_RATE." WHERE DETENTION_ID=".$det_id['DETENTION_ID']." AND (".$un_det_hr." BETWEEN UNDET_HR_FROM AND UNDET_HR_TO)");
			
				if(count($unload_det_rate)>0 && $unload_det_rate[0]['UN_DET_RATE']!="")
				{
					//echo $unload_det_rate[0]['UN_DET_RATE'];
					$ret_array=array('ans'=>'yes','detrate'=>$unload_det_rate[0]['UN_DET_RATE'],'mindetention'=>$unmin_det_rate);
				}
				else
				{
					//echo '0';
					$ret_array=array('ans'=>'no','detrate'=>0,'mindetention'=>$unmin_det_rate);
				}
			}
			else
			{
				//echo '0';
				$ret_array=array('ans'=>'no','detrate'=>0,'mindetention'=>'');
			}
			
			echo json_encode($ret_array);exit;
		}
		
	##----- delete fsc history ---------##
	if(isset($_REQUEST['history_id']) && $_REQUEST['action']=='deletefschis')
	{
		$history_id=$_REQUEST['history_id'];
		$fsc_his->delete_row("FSC_HIS_ID=$history_id");
		echo 'OK';
	}
	
	##------chech duplication while updating detention charge----------##
	if(isset($_REQUEST['detention_id']) && $_REQUEST['action']=='checkeditdetention')
	{
		$det_id=$_REQUEST['detention_id'];
		$det_hr=$_REQUEST['det_hr'];
		$det_hr_to=$_REQUEST['det_hr_to'];
		$hr_det_id=$_REQUEST['hr_det_id'];
		
		//$get_count=$det_rate->database->get_multiple_rows("SELECT COUNT(*) AS CNT FROM ".DETENTION_RATE." WHERE DETENTION_ID=".$det_id." AND 	DET_HR_ID !=".$hr_det_id." AND (('".$det_hr."' BETWEEN DET_HOUR AND DET_HOUR_TO) OR ('".$det_hr_to."' BETWEEN DET_HOUR AND DET_HOUR_TO) OR (DET_HOUR BETWEEN '".$det_hr."' AND '".$det_hr_to."') OR (DET_HOUR_TO BETWEEN '".$det_hr."' AND '".$det_hr_to."'))");
		
		$get_count=$det_rate->database->get_multiple_rows("SELECT COUNT(*) AS CNT FROM ".DETENTION_RATE." WHERE DETENTION_ID=".$det_id." AND 	DET_HR_ID !=".$hr_det_id." AND ((".$det_hr." >DET_HOUR AND ".$det_hr."< DET_HOUR_TO) OR (".$det_hr_to."  >DET_HOUR AND ".$det_hr_to." < DET_HOUR_TO) OR (DET_HOUR >".$det_hr." AND DET_HOUR < ".$det_hr_to.") OR (DET_HOUR_TO >".$det_hr." AND DET_HOUR_TO < ".$det_hr_to."))");
		
		
		echo $get_count[0]['CNT'];exit;
	
		
	}
	
	##------chech duplication while updating detention charge----------##
	if(isset($_REQUEST['detention_id']) && $_REQUEST['action']=='editunloaddetention')
	{
		$det_id=$_REQUEST['detention_id'];
		$un_det_hr=$_REQUEST['det_hr'];
		$un_det_hr_to=$_REQUEST['det_hr_to'];
		$un_det_id=$_REQUEST['un_det_id'];
		
		/*$get_count=$det_rate->database->get_multiple_rows("SELECT COUNT(*) AS CNT FROM ".UNLOADED_DETENTION_RATE." WHERE DETENTION_ID=".$det_id." AND 	UN_DET_HR_ID !=".$un_det_id." AND (('".$det_hr."' BETWEEN 	UNDET_HR_FROM AND UNDET_HR_TO) OR ('".$det_hr_to."' BETWEEN 	UNDET_HR_FROM AND UNDET_HR_TO) OR (UNDET_HR_FROM BETWEEN '".$det_hr."' AND '".$det_hr_to."') OR (UNDET_HR_TO BETWEEN '".$det_hr."' AND '".$det_hr_to."'))");*/
		$get_count=$det_rate->database->get_multiple_rows("SELECT COUNT(*) AS CNT FROM ".UNLOADED_DETENTION_RATE." WHERE DETENTION_ID=".$det_id." AND 	UN_DET_HR_ID !=".$un_det_id." AND ((".$un_det_hr." >UNDET_HR_FROM AND ".$un_det_hr."< UNDET_HR_TO) OR (".$un_det_hr_to."  >UNDET_HR_FROM AND ".$un_det_hr_to." < UNDET_HR_TO) OR (UNDET_HR_FROM >".$un_det_hr." AND UNDET_HR_FROM < ".$un_det_hr_to.") OR (UNDET_HR_TO >".$un_det_hr." AND UNDET_HR_TO < ".$un_det_hr_to."))");
		
		echo $get_count[0]['CNT'];
	}
	
	##------ check duplication when edit pallet rates-------##
	if($_GET['action']=='checkeditduplicatepallet' && $_GET['pal_id']!="")
		{
			$pal_id=$_GET['pal_id'];
			$pal_from=$_GET['pal_from'];
			$pal_to=$_GET['pal_to'];
		    $pal_rate_id=$_GET['pal_rate_id'];
			
			$get_count=$pall_obj->database->get_multiple_rows("SELECT COUNT(*) AS CNT FROM ".PALLET_RATE." WHERE PALLET_ID='".$pal_id."' AND 	PALLET_RATE_ID !='".$pal_rate_id."'AND (('".$pal_from."' BETWEEN PALLET_FROM AND PALLET_TO) OR ('".$pal_to."' BETWEEN PALLET_FROM AND PALLET_TO) OR (PALLET_FROM BETWEEN '".$pal_from."' AND '".$pal_to."') OR  (PALLET_FROM BETWEEN '".$pal_from."' AND '".$pal_to."'))");
			echo $get_count[0]['CNT'];
		}
		
		//! -check duplication while updating profile range--------##
		if($_GET['action']=='profilerangeduplication')
		{
			$range_from=$_GET['range_from'];
			$range_to=$_GET['range_to'];
			$range_rate=$_GET['range_rate'];
			$profile_range_id=$_GET['profile_range_id'];
			$profile_id=$_GET['ed_pro_id'];
			$get_count=$range->database->get_multiple_rows("SELECT COUNT(*) AS CNT FROM ".PROFILE_RANGE_RATE." JOIN ".PROFILE_RANGE." ON ".PROFILE_RANGE.".	RANGE_RATE=".PROFILE_RANGE_RATE.".PRO_RANGE_ID WHERE PROFILE_ID='$profile_id' AND (('$range_from'  BETWEEN PRO_RANGE_FROM AND PRO_RANGE_TO) OR ('$range_to' BETWEEN PRO_RANGE_FROM AND PRO_RANGE_TO) OR (PRO_RANGE_FROM BETWEEN '$range_from' AND '$range_to')  OR (PRO_RANGE_TO BETWEEN '$range_from' AND '$range_to')) AND  PRO_RANGE_ID!=$profile_range_id");//AND PRO_ZONE_FROM='$zone_from' AND PRO_ZONE_TO='$zone_to'
			
		 echo $get_count[0]['CNT'];
		}
		
		##----- chedk duplication for edit range rates-------------##
		if($_GET['action']=='editrangeduplication')
	{
		$range_from		=	$_GET['range_from'];
		$range_to			=	$_GET['range_to'];
		$range_rate		=	$_GET['range_rate'];
		$range_id		    =  $_GET['range_id'];
		$driver_id			=  $_GET['driver_id'];
			
		$get_count=$ren_obj->database->get_multiple_rows("SELECT COUNT(*) AS CNT FROM ".RANGE_RATES." WHERE DRIVER_ID='$driver_id' AND (('$range_from'  BETWEEN RANGE_FROM AND RANGE_TO) OR ('$range_to' BETWEEN RANGE_FROM AND RANGE_TO) OR (RANGE_FROM BETWEEN '$range_from' AND '$range_to')  OR (RANGE_TO BETWEEN '$range_from' AND '$range_to')) AND RANGE_ID !=".$range_id);//AND ZONE_FROM='$zone_from' AND ZONE_TO='$zone_to'
		
		echo $get_count[0]['CNT'];exit;
	}
	
	
	
	
	 ?>