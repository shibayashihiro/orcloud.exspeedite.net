<?php 

// $Id: exp_driverrate.php 3884 2020-01-30 00:21:42Z duncan $
// Add driver rates.

// Set flag that this is a parent file
define( '_STS_INCLUDE', 1 );

// Setup Session
require_once( "include/sts_session_setup.php" ); //used to set a constant

require_once( "include/sts_config.php" );
$sts_debug = isset($_GET['debug']) && in_group( EXT_GROUP_DEBUG );
require_once( "include/sts_session_class.php" );

$my_session = sts_session::getInstance( $exspeedite_db, $sts_debug );
$my_session->access_check( EXT_GROUP_USER );	// Make sure we should be here

$sts_subtitle = "Add Driver Rate";
require_once( "include/header_inc.php" );
require_once( "include/navbar_inc.php" );

$sql_obj	=	new sts_table($exspeedite_db , DRIVER_ASSIGN_RATES , $sts_debug);
$pro_master=new sts_table($exspeedite_db , PROFILE_MASTER , $sts_debug);
$driver_master=new sts_table($exspeedite_db , DRIVER_TABLE , $sts_debug);
$man_code=new sts_table($exspeedite_db , MAN_CODE , $sts_debug);
$ran_code=new sts_table($exspeedite_db , RAN_CODE, $sts_debug);
$range_obj=new sts_table($exspeedite_db , DRIVER_RATES , $sts_debug);
$man_sql_obj	=	new sts_table($exspeedite_db , MANUAL_RATES , $sts_debug);
$ren_obj	=	new sts_table($exspeedite_db , RANGE_RATES , $sts_debug);
$profile_range_obj=new sts_table($exspeedite_db , PROFILE_RATE , $sts_debug);
$profile_man_obj=new sts_table($exspeedite_db , PROFILE_MANUAL_RATE , $sts_debug);
$range=new sts_table($exspeedite_db , PROFILE_RANGE_RATE , $sts_debug);
$manual=new sts_table($exspeedite_db , PROFILE_MANUAL , $sts_debug);
$rate_obj=new sts_table($exspeedite_db , PROFILE_RANGE , $sts_debug);



$is_drivers	=$is_duplicate	=$rate_ids=array();
if(isset($_POST['saverate']))
{
	//$category 	=	$_POST['category'];
	$rate_ids 	= isset($_POST['rate_ids']) ? $_POST['rate_ids'] : '';
	$hid_driver_id	=	$_POST['hid_driver_id'];
	//print_r($rate_ids);exit;
	#--> select rates from driver assign rates table
	/*$is_drivers	=	$sql_obj->database->get_multiple_rows("SELECT ".CATEGORY_TABLE.".CATEGORY_CODE  FROM ".DRIVER_RATES." ,".CATEGORY_TABLE." WHERE  ".DRIVER_RATES.".RATE_ID='".$rate_ids."' AND  ".DRIVER_RATES.".CATEGORY=".CATEGORY_TABLE.".CATEGORY_CODE"); 
	if( count($is_drivers) > 0 ) {
	$is_duplicate	=	$sql_obj->database->get_multiple_rows("SELECT COUNT(*) AS num_dup FROM ".DRIVER_RATES." , ".DRIVER_ASSIGN_RATES."  WHERE  ".DRIVER_RATES.".RATE_ID = ".DRIVER_ASSIGN_RATES.".RATE_ID  AND ".DRIVER_RATES.".CATEGORY = ".$is_drivers[0]['CATEGORY_CODE']);
	*/
	//echo '<pre>';print_r($is_duplicate[0]['num_dup']);
	#--> IF DRIVER CODE is not in table
	$len	=	count($rate_ids);
	if($len	>0)
	{
		for($i=0;$i<$len;$i++)
		{
				$ans=$sql_obj->database->get_multiple_rows("SELECT COUNT(*) AS total FROM ".DRIVER_ASSIGN_RATES." WHERE DRIVER_ID='$hid_driver_id' AND RATE_ID=".$rate_ids[$i]);
			   if($ans[0]['total']==0)
			   {
			   //! SCR# 184 - include PROFILE_ID = 0
				$ins_res	=	$sql_obj->add_row("DRIVER_ID, RATE_ID, PROFILE_ID",
					"$hid_driver_id , '$rate_ids[$i]', 0");
			   }
		}
	}
	/*if($is_duplicate[0]['num_dup']==0)
	{
			$ins_arr 	=	array( array("field" => "RATE_ID" , "value" => "'".$rate_ids[$i]."'" ));	// , array("field"=> "DRIVER_ID" , "value"=>"'".$hid_driver_id."'")
			$ins_res	=	$sql_obj->add_row("DRIVER_ID , RATE_ID ", "$hid_driver_id , '$rate_ids'");
	}
	else
	{
		$is_drivers2	=	$sql_obj->database->get_multiple_rows("SELECT *  FROM ".DRIVER_ASSIGN_RATES.",".DRIVER_RATES." WHERE  ".DRIVER_ASSIGN_RATES.".DRIVER_ID='".$hid_driver_id."' AND  ".DRIVER_ASSIGN_RATES.".RATE_ID=".DRIVER_RATES.".RATE_ID  AND ".DRIVER_RATES.".CATEGORY  = '".$is_drivers[0]['CATEGORY_CODE']."'");   
		$ins_res	=	$sql_obj->update_row(' ASSIGN_ID = "'.$is_drivers2[0]['ASSIGN_ID'].'"',  $ins_arr);
	}*/
//	}
	echo '<script type="text/javascript" language="javascript">window.location.href="exp_driverrate.php?id='.$hid_driver_id.'";</script>';
}
?>
<div class="container-full" role="main">
<?php
require_once( "include/sts_result_class.php" );
require_once( "include/sts_driverrate_mng_class.php" );

$driverrate_obj =	new sts_driverrate_mng($exspeedite_db , $sts_debug);
$res_obj = new sts_result( $driverrate_obj , false, $sts_debug );

$res['driver_rates']			=	$sql_obj->database->get_multiple_rows("SELECT ".DRIVER_RATES.".*, ".CATEGORY_TABLE.".CATEGORY_NAME  FROM ".DRIVER_RATES." , ".CATEGORY_TABLE."  WHERE  ".DRIVER_RATES.".CATEGORY=".CATEGORY_TABLE.".CATEGORY_CODE AND ".DRIVER_RATES.".ISDELETED <> '1'");


if(!isset($_GET['id']) || $_GET['id']=='')
{
	$res['is_selected']='No';
	$res['max_driver_id']		=	$sql_obj->database->get_multiple_rows("SELECT MAX(DRIVER_CODE)  FROM ".DRIVER_TABLE."  WHERE ISACTIVE='Active'  "); 
}
else
{
	$res['is_selected']='Yes';
	$res['max_driver_id'][0]['MAX(DRIVER_CODE)']		=	$_GET['id']; 
}
$res['arr_driver_name']	=	$sql_obj->database->get_multiple_rows("SELECT FIRST_NAME , MIDDLE_NAME , LAST_NAME  FROM ".DRIVER_TABLE."  WHERE  DRIVER_CODE='".$res['max_driver_id'][0]['MAX(DRIVER_CODE)']."'"); 
$res['driver_name']			=	$res['arr_driver_name'][0]['FIRST_NAME']." ".$res['arr_driver_name'][0]['MIDDLE_NAME']." ".$res['arr_driver_name'][0]['LAST_NAME'];
$res['drivers']					=	$sql_obj->database->get_multiple_rows("SELECT DRIVER_CODE ,FIRST_NAME ,MIDDLE_NAME ,LAST_NAME  FROM ".DRIVER_TABLE."  WHERE  ISACTIVE='Active' ORDER BY  LAST_NAME ASC");
$res['drivers_arr']			=	$sql_obj->database->get_multiple_rows("SELECT ".DRIVER_ASSIGN_RATES.".*,".DRIVER_RATES.".* ,".CATEGORY_TABLE.".CATEGORY_NAME  FROM ".DRIVER_ASSIGN_RATES.",".DRIVER_RATES." ,".CATEGORY_TABLE." WHERE  ".DRIVER_ASSIGN_RATES.".DRIVER_ID='".$res['max_driver_id'][0]['MAX(DRIVER_CODE)']."'  AND  ".DRIVER_ASSIGN_RATES.".RATE_ID=".DRIVER_RATES.".RATE_ID  AND ".DRIVER_RATES.".CATEGORY=".CATEGORY_TABLE.".CATEGORY_CODE   ");   

$res['manual_rates']			=	$sql_obj->database->get_multiple_rows(" SELECT  *  FROM ".MANUAL_RATES." WHERE DRIVER_ID='".$res['max_driver_id'][0]['MAX(DRIVER_CODE)']."'");
$res['range_rates']			=	$sql_obj->database->get_multiple_rows(" SELECT  *  FROM ".RANGE_RATES." WHERE DRIVER_ID='".$res['max_driver_id'][0]['MAX(DRIVER_CODE)']."'");

/*$max_manual_id				=  $sql_obj->database->get_multiple_rows(" SELECT 	MAX(MANUAL_RATE_CODE) FROM ".MANUAL_RATES." ");	
if($max_manual_id[0]['MAX(MANUAL_RATE_CODE)']==''){$res['MANUAL_RATE_CODE']='M101';}else{ $arr=explode('M',$max_manual_id[0]['MAX(MANUAL_RATE_CODE)']);$m=$arr[1]+1;  $res['MANUAL_RATE_CODE']='M'.$m;}*/

##--------changes of manual code issue-------##

$max_manual_id				=  $man_code->database->get_multiple_rows(" SELECT 	MAX(UNIQUE_MAN_CODE) FROM ".MAN_CODE." ");	
if($max_manual_id[0]['MAX(UNIQUE_MAN_CODE)']==''){$res['MANUAL_RATE_CODE']='M101';}else{ $arr=explode('M',$max_manual_id[0]['MAX(UNIQUE_MAN_CODE)']);$m=$arr[1]+1;  $res['MANUAL_RATE_CODE']='M'.$m;}

/*$max_range_id				=  $sql_obj->database->get_multiple_rows(" SELECT 	MAX(RANGE_CODE) FROM ".RANGE_RATES." ");	
if($max_range_id[0]['MAX(RANGE_CODE)']==''){$res['RANGE_RATE_CODE']='R101';}else{ $arrr=explode('R',$max_range_id[0]['MAX(RANGE_CODE)']);$r=$arrr[1]+1;  $res['RANGE_RATE_CODE']='R'.$r;}*/
##  ----generating range code from another table-$ran_code----------##
$max_range_id				=  $ran_code->database->get_multiple_rows(" SELECT MAX(UNIQUE_RANGE_CODE) FROM ".RAN_CODE." ");	
if($max_range_id[0]['MAX(UNIQUE_RANGE_CODE)']==''){$res['RANGE_RATE_CODE']='R101';}else{ $arrr=explode('R',$max_range_id[0]['MAX(UNIQUE_RANGE_CODE)']);$r=$arrr[1]+1;  $res['RANGE_RATE_CODE']='R'.$r;}


#category str
$category	=	$sql_obj->database->get_multiple_rows(" SELECT *  FROM ".CATEGORY_TABLE." ORDER BY CATEGORY_NAME");	

$category_str	=	' <select class="form-control" name="category" id="category" style="float:left;width:200px; margin-right:20px;" onchange="javascript: return get_search_records(this.value);"> 
			<option value="">		ALL 	</option>';
			if(count($category)>0)
			{
				foreach($category as $cat)
				{
						$category_str	.='	<option value="'.$cat['CATEGORY_CODE'].'">'.$cat['CATEGORY_NAME'].'</option>';
				 }
			}
	  	$category_str	.='
		</select>';
		
		$res['category_str']=$category_str;

#string for driver rate
$str_dri_rate = '';	// Initialize
if(count($res['drivers_arr']) >0 )
{
	foreach($res['drivers_arr'] as $rest)
	{
			#get all records of perticular rate code.
			$str_dri_rate.= '<tr style="cursor:pointer;">
			<td class="text-center"><a class="btn btn-default btn-xs inform" href="javascript:void(0);" onclick="javascript: return delete_assigned_rate(\''.$rest['ASSIGN_ID'].'\');"> <span style="font-size: 14px;">
			<span class="glyphicon glyphicon-trash"></span> </span> </a></td>
			<td style="text-align:center;">'.$rest['RATE_CODE'].'</td>
			<td style="text-align:center;">'.$rest['RATE_NAME'].'</td>
			<td style="text-align:center;">'.$rest['CATEGORY_NAME'].'</td>
			<td style="text-align:center;">'.$rest['RATE_PER_MILES'].'</td>
			<td style="text-align:center;">'.($rest['RATE_BONUS'] ? '<span class="glyphicon glyphicon-check"></span>' : '<span class="text-muted"><span class="glyphicon glyphicon-unchecked"></span>').'</td></tr>';
	}
}
else
{ 
	$str_dri_rate= '<tr style="cursor:pointer;" ><td class="text-center"><a class="btn btn-default btn-xs " href="javascropt:void(0);" > <span style="font-size: 14px;"> <span class="glyphicon glyphicon"></span> </span> </a></td><td style="text-align:center;" colspan="4">DRIVER DOES NOT HAVE ANY RATES AVAILABLE.</td></tr>';
} 
 $res['driver_assign_rates_str']=$str_dri_rate;
 $res['all_profile_id']=$pro_master->database->get_multiple_rows("SELECT * FROM ".PROFILE_MASTER);
 //$res['my_profile_id']='';
if(isset($_GET['id']) && $_GET['id']!='')
{
   $res['my_profile_id']=$driver_master->database->get_one_row("SELECT PROFILE_ID FROM ".DRIVER_TABLE." WHERE DRIVER_CODE=".$_GET['id']);
}

##--------- Fetching zone---------##
$res['zones']			=	$sql_obj->database->get_multiple_rows(" SELECT  DISTINCT ZF_NAME  FROM ".ZONE_FILTER_TABLE);


##---------add profile name & id (contract id)-------##
if(isset($_POST['save_profile_name']))
{
	$driver_id=$_POST['DRIVER_ID'];
	$pro_name=$_POST['PRO_NAME'];
	
	##------- fetch all rates,range ,manual rates of driver---##
		$get_assigned_rates=$range_obj->database->get_multiple_rows('SELECT '.DRIVER_RATES.'.RATE_ID FROM '.DRIVER_RATES.' JOIN '.DRIVER_ASSIGN_RATES.' ON  '.DRIVER_RATES.'.RATE_ID='.DRIVER_ASSIGN_RATES.'.RATE_ID WHERE '.DRIVER_ASSIGN_RATES.'.DRIVER_ID='.$driver_id);
		
		$get_manual_rates=$man_sql_obj->database->get_multiple_rows('SELECT * FROM '.MANUAL_RATES.' WHERE DRIVER_ID='.$driver_id);
		
		$get_range_rates=$ren_obj->database->get_multiple_rows('SELECT * FROM '.RANGE_RATES.' WHERE DRIVER_ID='.$driver_id);
		
		##----  Generate Contract ID------------##
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
			
		$get_contract_id=$pro_master->add_row("CONTRACT_ID,PROFILE_NAME", "'$contract_id','".addslashes($pro_name)."'");
		}
	
		##-------------   code to add rates  -----------------##
		
		//print_r($get_assigned_rates);
		if(count($get_assigned_rates)>0)
		{
			foreach($get_assigned_rates as $pro_rate)
			{
				//$ins_record=$profile_range_obj->add_row("PROFILE_ID,RATE_ID","'$profile_id','$pro_rate['RATE_ID']'");
				$ins_record=$profile_range_obj->add_row("PROFILE_ID,RATE_ID", "'$contract_id',".$pro_rate['RATE_ID']."");
			}
		}
		
		##---------------   code to add  manual rates -------------------------##
		
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
		
		
		##---------------   code to add range rates -------------------------##
		
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
		
		
		if((count($get_manual_rates)==0) && (count($get_range_rates)==0) && (count($get_assigned_rates)==0))
		{
			//echo 'errorrate';
		}
		
	echo '<script type="text/javascript" language="javascript">window.location.href="exp_driverrate.php?id='.$driver_id.'";</script>';	
	
}

##-----update driver rates-------##
if(isset($_POST['edt_man_rate']))
{
	$ed_man_id=$_POST['ed_man_id'];
	$ed_man_code=$_POST['ed_man_code'];
	$ed_man_name=$_POST['ed_man_name'];
	$ed_man_desc=$_POST['ed_man_desc'];
	$ed_man_rate=$_POST['ed_man_rate'];
	$ed_driver_id=$_POST['ed_driver_id'];
	
	$man_sql_obj->update($ed_man_id,array('MANUAL_NAME'=>$ed_man_name,'MANUAL_RATE_DESC'=>$ed_man_desc,'MANUAL_RATE_RATE'=>$ed_man_rate));
	echo '<script type="text/javascript" language="javascript">window.location.href="exp_driverrate.php?id='.$ed_driver_id.'";</script>';	
}

##------ edit  range of rates--------##
if(isset($_POST['ed_range_id']) && isset($_POST['ed_ran_code']) && isset($_POST['ed_ran_name']) && isset($_POST['ed_ran_frm']) && isset($_POST['ed_ran_to']) && isset($_POST['ed_ran_frm_zone']) && isset($_POST['ed_ran_to_zone']) && isset($_POST['ed_ran_rate']) && isset($_POST['ed_driver_code']))
{
	$ed_range_id=$_POST['ed_range_id'];
	$ed_ran_code=$_POST['ed_ran_code'];
	$ed_ran_name=$_POST['ed_ran_name'];
	$ed_ran_frm=$_POST['ed_ran_frm'];
	$ed_ran_to=$_POST['ed_ran_to'];
	$ed_ran_frm_zone=$_POST['ed_ran_frm_zone'];
	$ed_ran_to_zone=$_POST['ed_ran_to_zone'];
	$ed_ran_rate=$_POST['ed_ran_rate'];
	$ed_driver=$_POST['ed_driver_code'];
	
	$ren_obj->update($ed_range_id,array('RANGE_NAME'=>$ed_ran_name,'RANGE_FROM'=>$ed_ran_frm,'RANGE_TO'=>$ed_ran_to,'ZONE_FROM'=>$ed_ran_frm_zone,'ZONE_TO'=>$ed_ran_to_zone,'RANGE_RATE'=>$ed_ran_rate));
	echo '<script type="text/javascript" language="javascript">window.location.href="exp_driverrate.php?id='.$ed_driver.'";</script>';	
}

$res_obj->render_driver_rate_screen($res);
?>
</div>
<?php
require_once( "include/footer_inc.php" );
?>
<script type="text/javascript" language="javascript" >
function show_driver_rates(id)
{
	document.getElementById("driver_rate_div").innerHTML="<img src=\"images/loading.gif\" border=\"1\" style=\"position:absolute;margin-left:300px;margin-top:185px;\">"; //return false;
	$.ajax({
			url:"exp_ajax.php?id="+id,
			type : "post",
			success:function(res){
					document.getElementById("driver_rate_div").innerHTML=res;
					document.getElementById("hid_driver_id").value=id;
				}
		})	
}

function chk_validation_cat()
{
	var category	=	document.getElementById('category');	
	var rates	=	document.getElementsByName('rate_ids');
	var chk_len	=	rates.length;
	 var flag=0; 
	var count=0;
	for(var i=0; i< chk_len ; i++)
	{
		if(rates[i].checked==true)	
		{
			flag	=	'1'; count=parseInt(count)+parseInt(1);
			//break;
		}
		else
		{	
			flag	=	0;
		}
	}

	/*if(category.value=='')
	{
			alert('Please select the Category.');
			return false;
	}
	else*/ if(count!=1)
	{
			alert('Please select only one rate record.');
			return false;
	}
	else
	{
		return true;	
	}
}

function add_more_fun(val)
{
	$("#"+val).toggle();
}

function add_manual_rates()
{
	    var manual_name	=	document.getElementById('manual_name');		
		var manual_code	=	document.getElementById('manual_code');		
		var manual_desc	=	document.getElementById('manual_desc');		
		var manual_rate	=	document.getElementById('manual_rate');		
		var manual_driver_id	=	document.getElementById('manual_driver_id');
		
		if(manual_name.value=='')
		{
				alert('Enter Manual Rate Name.');
				return false;
		}
		else if(manual_code.value=='')
		{
				alert('Enter Manual Rate Code.');
				return false;
		}
		else if(manual_desc.value=='')
		{
				alert('Enter Manual Rate Description.');
				return false;
		}
		else if(manual_rate.value=='')
		{
				alert('Enter Manual Rate.');
				return false;
		}
		else if(isNaN(manual_rate.value))
		{
				alert('Manual Rate Must Be Numeric.');
				return false;
		}
		else
		{
			$('#reload_manual_rates_div').show();
			$.ajax({
			url:'exp_save_rates.php?action=savemanualrates&manual_code='+manual_code.value+'&manual_name='+manual_name.value+'&manual_desc='+manual_desc.value+'&manual_rate='+manual_rate.value+'&manual_driver_id='+manual_driver_id.value,
			type:'POST',
			success:function(res)
			{
				//$('#add_manual_rates').load(location.href+' #add_manual_rates');
				window.location.href='exp_driverrate.php?id='+manual_driver_id.value;
			}
			});
		}
		
}

function add_rate_range()
{
	   var range_name			=	document.getElementById('range_name');
		var range_from			=	document.getElementById('range_from');		
		var range_to				=	document.getElementById('range_to');		
		var range_rate			=	document.getElementById('range_rate');		
		var range_driver_id	=	document.getElementById('range_driver_id');
		var range_rate_code	=	document.getElementById('range_rate_code');
		var zone_from           =   document.getElementById('zone_from');
		var zone_to                =   document.getElementById('zone_to');
		
		if(range_name.value=='')
		{
				alert('Enter Range Name.');
				return false;
		}
		else if(range_from.value=='')
		{
				alert('Enter Range From.');
				return false;
		}
		else if(isNaN(range_from.value))
		{
				alert('Range Mile From Field Must Be Numeric.');
				return false;
		}
		else if(range_to.value=='')
		{
				alert('Enter Mile Range To Field.');
				return false;
		}
		else if(isNaN(range_to.value))
		{
				alert('Range To Field Must Be Numeric.');
				return false;
		}
		/*else if(zone_from.value=='')
		{
				alert('Select Zone From Field.');
				return false;
		}
		else if(zone_to.value=='')
		{
				alert('Select Zone To Field.');
				return false;
		}*/
		else if(range_rate.value=='')
		{
				alert('Enter Manual Rate.');
				return false;
		}
		else if(isNaN(range_rate.value))
		{
				alert('Range Rate Must Be Numeric.');
				return false;
		}
		else if(parseFloat(range_from.value) > parseFloat(range_to.value))
		{
				alert('From Miles Must Be Less Than To Miles.');
				return false;	
		}
		else
		{
			$('#reload_range_rates_div').show();
			$.ajax({
			url:'exp_save_rates.php?action=saverangerates&range_from='+range_from.value+'&range_to='+range_to.value+'&range_rate='+range_rate.value+'&range_driver_id='+range_driver_id.value+'&range_rate_code='+range_rate_code.value+'&zone_from='+zone_from.value+'&zone_to='+zone_to.value+'&range_name='+range_name.value,
			type:'POST',
			success:function(res)
			{
				if(res.trim()=='error')
				{
					alert("Range already exists.");
					$('#reload_range_rates_div').hide();
					return false;
				}
				else
				{
					window.location.href='exp_driverrate.php?id='+range_driver_id.value;
				}
			}
			});
		}		
}


function delete_range(id)
{
	if(confirm('Do you really want to delete the Records?'))
	{
		$('#reload_range_rates_div').show();
		var range_driver_id	=	document.getElementById('range_driver_id');
		if(id!='' && range_driver_id!='')
		{
			$.ajax({
				url:'exp_save_rates.php?action=deleterangerates&range_driver_id='+range_driver_id.value+'&range_id='+id,
				type:'POST',
				success:function(res)
				{
					//$('#add_manual_rates').load(location.href+' #add_manual_rates');
					window.location.href='exp_driverrate.php?id='+range_driver_id.value;
				}
				});	
		}
	}
	else
	{
		return false;	
	}
}

function delete_manual(id)
{
	if(confirm('Do you really want to delete the Records?'))
	{
		$('#reload_manual_rates_div').show();
		var manual_driver_id	=	document.getElementById('manual_driver_id');
		if(id!='' && manual_driver_id!='')
		{
			$.ajax({
				url:'exp_save_rates.php?action=deletemanualrates&manual_driver_id='+manual_driver_id.value+'&manual_id='+id,
				type:'POST',
				success:function(res)
				{//return false;	
					//$('#add_manual_rates').load(location.href+' #add_manual_rates');
					window.location.href='exp_driverrate.php?id='+manual_driver_id.value;
				}
				});	
		}
	}
	else
	{
		return false;	
	}
}

function delete_assigned_rate(val)
{
	if(confirm('Do you really want to delete the Rate?'))
	{
		$('#reload_drivers_rates_div').show();
		//var manual_driver_id	=	document.getElementById('manual_driver_id');
		if(val!='')
		{
			$.ajax({
				url:'exp_save_rates.php?action=deletedriverrates&val='+val,
				type:'POST',
				success:function(res)
				{//return false;	
					//$('#add_manual_rates').load(location.href+' #add_manual_rates');
					window.location.href='exp_driverrate.php?id='+manual_driver_id.value;
				}
				});	
		}
	}
	else
	{
		return false;	
	}
}

function get_search_records(val)
{
//	if(val !='')
	{
		$('#reload_search_result').show();
		//var manual_driver_id	=	document.getElementById('manual_driver_id');
		//if(val!='')
		{
			$.ajax({
				url:'exp_save_rates.php?action=reloadsearch&val='+val,
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

}

function add_rating_to_profile(driver_id)
{
	if(driver_id!="")
	{
		//$('#reload_search_result').show();
		$.ajax({
			url:'exp_save_rates.php?action=addtoprofile&driver_id='+driver_id,
			type:'POST',
			success:function(res)
			{
				//$('#reload_search_result').hide();
				//alert('**'+res);
				if(res.trim()=='errorrate')
				{
					$('#myModal_profile').modal('hide');
					alert('No rates are available to add to profile.');
				}
				else
				{
					//window.location.href='exp_driverrate.php?id='+driver_id;
				}
			}
		});
	}
}

function check_profile_name()
{
	var profile_name=document.getElementById('PRO_NAME');
	if(profile_name.value=="")
	{
		alert("Enter profile name. ");
		return false;
	}
	else
	{
		return true;
	}
}

function assign_profile(pro_id,driver_id)
{
	if(pro_id!="" && driver_id!="")
	{
		var datastring='pro_id='+pro_id+'&driver_id='+driver_id;
		$.ajax({
			url:'exp_save_rates.php?action=assignprofile',
			data:datastring,
			type:'POST',
			success:function(res)
			{window.location.href='exp_driverrate.php?id='+driver_id;
				
			}
		});
	}
}


function check_edit_manual(man_id)
{
	var ed_man_code=document.getElementById('ed_man_code'+man_id).value;
	var ed_man_name=document.getElementById('ed_man_name'+man_id).value;
	var ed_man_desc=document.getElementById('ed_man_desc'+man_id).value;
	var ed_man_rate=document.getElementById('ed_man_rate'+man_id).value;
	
	if(ed_man_name=="")
	{
		alert("Enter manual code name.");
		return false;
	}
	else if(ed_man_desc=="")
	{
		alert("Enter manual rate description.");
		return false;
	}
	else if(ed_man_rate=="")
	{
		alert("Enter manual rate.");
		return false;
	}
	else if(isNaN(ed_man_rate))
	{
		alert("Enter manual rate in numbers.");
		return false;
	}

	else
	{return true;}
}

function check_edit_driver_range(range_id,driver_id)
{
	var ed_ran_code=document.getElementById('ed_ran_code'+range_id).value;
	var ed_ran_name=document.getElementById('ed_ran_name'+range_id).value;
	var ed_ran_frm=document.getElementById('ed_ran_frm'+range_id).value;
	var ed_ran_to=document.getElementById('ed_ran_to'+range_id).value;
	var ed_ran_frm_zone=document.getElementById('ed_ran_frm_zone'+range_id).value;
	var ed_ran_to_zone=document.getElementById('ed_ran_to_zone'+range_id).value;
	var ed_ran_rate=document.getElementById('ed_ran_rate'+range_id).value;
	//var ed_pro_id=document.getElementById('ed_pro_id').value;
	
	if(ed_ran_name=="")
	{
		alert("Enter Range of Rate Name");
		return false;
	}
	else if(ed_ran_frm=="")
	{
		alert("Enter range from.");
		return false;
	}
	else if(isNaN(ed_ran_frm))
    {
		alert("Enter range from in numbers.");
		return false;
	}
	else if(ed_ran_to=="")
	{
		alert("Enter range to.");
		return false;
	}
	else if(isNaN(ed_ran_to))
   {
		alert("Enter range to in numbers.");
		return false;
	}
	else if(ed_ran_rate=="")
	{
		alert("Enter range rate.");
		return false;
	}
	else if(isNaN(ed_ran_rate))
	{
		alert("Enter range rate in numbers.");
		return false;
	}
	else
	{
		
			$('#edit_loader').show();
			$.ajax({
			url:'exp_save_rates.php?action=editrangeduplication&range_from='+ed_ran_frm+'&range_to='+ed_ran_to+'&range_rate='+ed_ran_rate+'&range_id='+range_id+'&driver_id='+driver_id,
			type:'POST',
			success:function(res)
			{
				if(res>0)
				{
					alert("Range already exists. ");
					$('#edit_loader').hide();
					return false;
				}
				else
				{document.getElementById("form_edit_range"+range_id).submit();
				//window.location.href='exp_rate_profile.php?TYPE=edit&CODE='+ed_pro_id;
				return true;
				}
			}
			});
	return false;	
	}
	
}
</script>