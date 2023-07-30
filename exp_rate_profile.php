<?php 
// Set flag that this is a parent file
define( '_STS_INCLUDE', 1 );

// Setup Session
require_once( "include/sts_session_setup.php" ); //used to set a constant

require_once( "include/sts_config.php" );
$sts_debug = isset($_GET['debug']) && in_group( EXT_GROUP_DEBUG );
require_once( "include/sts_session_class.php" );
require_once( "include/sts_driverrate_mng_class.php" );
require_once( "include/sts_result_class.php" );

$my_session = sts_session::getInstance( $exspeedite_db, $sts_debug );
$my_session->access_check( EXT_GROUP_USER );	// Make sure we should be here

$sts_subtitle = "Rate Profile";
require_once( "include/header_inc.php" );
require_once( "include/navbar_inc.php" );

$sql_obj	=	new sts_table($exspeedite_db , PROFILE_MASTER , $sts_debug);
$driver_obj	=	new sts_table($exspeedite_db , DRIVER_RATES , $sts_debug);
$cat_obj	=	new sts_table($exspeedite_db , CATEGORY_TABLE , $sts_debug);
$manual=new sts_table($exspeedite_db , PROFILE_MANUAL_RATE , $sts_debug);
$range=new sts_table($exspeedite_db , PROFILE_RANGE_RATE , $sts_debug);
$rate=new sts_table($exspeedite_db , PROFILE_RATE , $sts_debug);
$driver_master=new sts_table($exspeedite_db , DRIVER_TABLE , $sts_debug);
$ass_obj=new sts_table($exspeedite_db , DRIVER_ASSIGN_RATES , $sts_debug);
$man_obj=new sts_table($exspeedite_db , MANUAL_RATES , $sts_debug);
$ran_obj=new sts_table($exspeedite_db , RANGE_RATES , $sts_debug);
$zone_obj=new sts_table($exspeedite_db , ZONE_FILTER_TABLE , $sts_debug);
$man_code=new sts_table($exspeedite_db , MAN_CODE , $sts_debug);
$ran_code=new sts_table($exspeedite_db , RAN_CODE, $sts_debug);
$man_rate_obj	=	new sts_table($exspeedite_db , MANUAL_RATES , $sts_debug);
$ren_obj	=	new sts_table($exspeedite_db , RANGE_RATES , $sts_debug);

$driverrate_obj =	new sts_driverrate_mng($exspeedite_db , $sts_debug);
$res_obj = new sts_result( $driverrate_obj , false, $sts_debug );
$output = array();

if(isset($_GET['TYPE']) &&  $_GET['CODE']) {
	if($_GET['TYPE']=='edit' && $_GET['CODE']!="") {
	  
		if(isset($_POST['saverate'])) {
			if(isset($_POST['rate_ids'])) {
				$rate_ids 	= $_POST['rate_ids'];
				if(count($rate_ids)>0) {
					foreach($rate_ids as $r) {
					    $ans=$rate->database->get_multiple_rows("
					    	SELECT COUNT(*) AS total
					    	FROM ".PROFILE_RATE." WHERE
					    	RATE_ID=$r
					    	AND PROFILE_ID=".$_GET['CODE']);
						
						if($ans[0]['total']==0) {
							$rate->add_row("PROFILE_ID, RATE_ID",
								$_GET['CODE'].", $r");
						}
					}
				}
			}
		  
			$driver_code=$driver_master->database->get_multiple_rows("
				SELECT DRIVER_CODE FROM ".DRIVER_TABLE."
				WHERE  PROFILE_ID=".$_GET['CODE']);
		
			if(count($driver_code)>0) {
				foreach($driver_code as $dc) {
					foreach($rate_ids as $r) {
						$ass_obj->add_row("DRIVER_ID, RATE_ID, PROFILE_ID",
							$dc['DRIVER_CODE'].", '$r', ".$_GET['CODE']);
					}
				}
			}
		}
	  
		##---- update manual rates -----##
		if(isset($_POST['edt_man_rate'])) {

			$ed_man_code=$_POST['ed_man_code'];
			$ed_man_name=$_POST['ed_man_name'];
			$ed_man_desc=$_POST['ed_man_desc'];
			$ed_man_rate=floatval($_POST['ed_man_rate']);
			$ed_man_id=$_POST['ed_man_id'];
		  
			$manual->update($ed_man_id,
				array('PRO_RATE_NAME'=>addslashes($ed_man_name),
				'PRO_RATE_DESC'=>$ed_man_desc,
				'PRO_RATE'=>$ed_man_rate));

			$man_rate_obj->database->get_multiple_rows("
				UPDATE ".MANUAL_RATES."
				SET MANUAL_NAME='".addslashes($ed_man_name)."',
				MANUAL_RATE_DESC='".$ed_man_desc."',
				MANUAL_RATE_RATE=".$ed_man_rate."
				WHERE MANUAL_RATE_CODE='".$ed_man_code."'");
		}
	  
		if(isset($_POST['ed_pro_id']) && isset($_POST['ed_ran_code']) &&
			isset($_POST['ed_ran_name']) && isset($_POST['ed_ran_frm']) &&
			isset($_POST['ed_ran_to']) && isset($_POST['ed_ran_frm_zone']) &&
			isset($_POST['ed_ran_to_zone']) && isset($_POST['ed_ran_rate']) &&
			isset($_POST['ed_pro_range_id'])) {

			$ed_pro_id=$_POST['ed_pro_id'];
			$ed_ran_code=$_POST['ed_ran_code'];
			$ed_ran_name=$_POST['ed_ran_name'];
			$ed_ran_frm=intval($_POST['ed_ran_frm']);
			$ed_ran_to=intval($_POST['ed_ran_to']);
			$ed_ran_frm_zone=$_POST['ed_ran_frm_zone'];
			$ed_ran_to_zone=$_POST['ed_ran_to_zone'];
			$ed_ran_rate=floatval($_POST['ed_ran_rate']);
			$ed_pro_range_id=$_POST['ed_pro_range_id'];
		  
			$range->update($ed_pro_range_id,
				array('PRO_RANGE_NAME'=>addslashes($ed_ran_name),
				'PRO_RANGE_FROM'=>$ed_ran_frm,
				'PRO_RANGE_TO'=>$ed_ran_to,
				'PRO_ZONE_FROM'=>$ed_ran_frm_zone,
				'PRO_ZONE_TO'=>$ed_ran_to_zone,
				'PRO_RANGE_RATE'=>$ed_ran_rate));
		  
			$ren_obj->database->get_multiple_rows("
				UPDATE ".RANGE_RATES."
				SET RANGE_NAME='".addslashes($ed_ran_name)."',
				RANGE_FROM=".$ed_ran_frm.",
				RANGE_TO=".$ed_ran_to.",
				ZONE_FROM='".$ed_ran_frm_zone."',
				ZONE_TO='".$ed_ran_to_zone."',
				RANGE_RATE=".$ed_ran_rate."
				WHERE RANGE_CODE='".$ed_ran_code."'");
		}
	  
		$output['profile_id']=$_GET['CODE'];
		$output['profile_name']='';
		$pro_name=$sql_obj->database->get_one_row("
			SELECT * FROM ".PROFILE_MASTER."
			WHERE  CONTRACT_ID=".$_GET['CODE']);
		
		if(count($pro_name)>0 && $pro_name['PROFILE_NAME']!="")
		{
			$output['profile_name']=$pro_name['PROFILE_NAME'];
		}
	  
	  
	  
	$output['profile_rate'] =$driver_obj->database->get_multiple_rows("
		SELECT * FROM ".DRIVER_RATES."
		JOIN ".PROFILE_RATE."
		ON ".PROFILE_RATE.".RATE_ID=".DRIVER_RATES.".RATE_ID
		WHERE ".PROFILE_RATE.".PROFILE_ID=".$_GET['CODE']."
		AND ISDELETED=0");
	
	$output['profile_manual_rate']=$manual->database->get_multiple_rows("
		SELECT * FROM ".PROFILE_MANUAL_RATE."
		JOIN ".PROFILE_MANUAL."
		ON ".PROFILE_MANUAL.".MANUAL_ID=".PROFILE_MANUAL_RATE.".PRO_MANUAL_ID
		WHERE ".PROFILE_MANUAL.".PROFILE_ID=".$_GET['CODE']);
	
	$max_manual_id = $man_code->database->get_multiple_rows("
		SELECT MAX(UNIQUE_MAN_CODE)
		FROM ".MAN_CODE);
	 	
	if($max_manual_id[0]['MAX(UNIQUE_MAN_CODE)']=='') {
		$output['max_profile_manual_rate']='M101';
	} else { 
		$arr=explode('M',$max_manual_id[0]['MAX(UNIQUE_MAN_CODE)']);
		$m=$arr[1]+1;
		$output['max_profile_manual_rate']='M'.$m;
	}	  
	  
	$max_range_id = $ran_code->database->get_multiple_rows("
		SELECT MAX(UNIQUE_RANGE_CODE)
		FROM ".RAN_CODE);
		
	if($max_range_id[0]['MAX(UNIQUE_RANGE_CODE)']=='') {
		$output['max_range']='R101';
	} else {
		$arrr=explode('R',$max_range_id[0]['MAX(UNIQUE_RANGE_CODE)']);
		$r=$arrr[1]+1; 
		$output['max_range']='R'.$r;
	}

	$output['profile_range']=$range->database->get_multiple_rows("
		SELECT * FROM ".PROFILE_RANGE_RATE."
		JOIN ".PROFILE_RANGE."
		ON ".PROFILE_RANGE.".RANGE_RATE=".PROFILE_RANGE_RATE.".PRO_RANGE_ID
		WHERE ".PROFILE_RANGE.".PROFILE_ID=".$_GET['CODE']);
	  
	$output['driver_rates'] =	$driver_obj->database->get_multiple_rows("
		SELECT ".DRIVER_RATES.".*, ".CATEGORY_TABLE.".CATEGORY_NAME
		FROM ".DRIVER_RATES.", ".CATEGORY_TABLE."
		WHERE ".DRIVER_RATES.".CATEGORY=".CATEGORY_TABLE.".CATEGORY_CODE
		AND ".DRIVER_RATES.".ISDELETED=0");
	  
	$output['category']	=	$cat_obj->database->get_multiple_rows("
		SELECT * FROM ".CATEGORY_TABLE."
		ORDER BY CATEGORY_NAME");	
	  
	$output['zones']=$zone_obj->database->get_multiple_rows("
		SELECT ZF_NAME FROM ".ZONE_FILTER_TABLE."
		GROUP BY ZF_NAME");
	  
	  $res_obj->render_edit_rate_profile($output);
  }
} else {
	  //print_r($_POST);
	  if(isset($_POST['edit_profile_name'])) {
		  $profile_id=$_POST['PROFILE_ID'];
		  $profile_name=$_POST['PRO_NAME'];
		  $pr_id=$_POST['pro_prim_id'];
		  $sql_obj->update($pr_id,array('PROFILE_NAME'=>addslashes($profile_name)));
	  }
	 $res['profile']=$sql_obj->database->get_multiple_rows("
	 	SELECT * FROM ".PROFILE_MASTER);
	 	
	 $res_obj->render_profile_rate_screen($res);
  }

?>

<?php
require_once( "include/footer_inc.php" );
?>

<script type="text/javascript" language="javascript">

function delete_profile(profile_id)
{
	if(profile_id!="")
	{	$('#reload_search_result').show();
		$.ajax({
			url:'exp_save_rates.php?action=deleteprofile&profile_id='+profile_id,
			success:function(res)
			{$('#reload_search_result').hide();
					window.location.href='exp_rate_profile.php';
				/*if(res.trim()=='OK')
				{alert('d');
					$('#reload_search_result').hide();
					window.location.href='exp_rate_profile.php';
				}*/
			}
		});
	}
}

function get_search_records(val)
{
  $('#reload_search_result').show();
		{
			$.ajax({
				url:'exp_save_rates.php?action=reloadsearch&val='+val,
				type:'POST',
				success:function(res)
				{
					document.getElementById("search_reload").innerHTML=res;
					$('#reload_search_result').hide();
				}
				});	
		}
}

function add_more_fun(val)
{
	$("#"+val).toggle();
}

function add_manual_rates()
{
		var manual_code	=	document.getElementById('manual_code');	
		var manual_name=	document.getElementById('manual_name');		
		var manual_desc	=	document.getElementById('manual_desc');		
		var manual_rate	=	document.getElementById('manual_rate');		
		var profile_id	=	document.getElementById('profile_id');
		
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
			url:'exp_save_rates.php?action=profilemanualrates&manual_code='+manual_code.value+'&manual_name='+manual_name.value+'&manual_desc='+manual_desc.value+'&manual_rate='+manual_rate.value+'&profile_id='+profile_id.value,
			type:'POST',
			success:function(res)
			{
			window.location.href='exp_rate_profile.php?TYPE=edit&CODE='+profile_id.value;
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
		var zone_from				=	document.getElementById('zone_from');		
		var zone_to			=	document.getElementById('zone_to');	
		var profile_id	=	document.getElementById('profile_id');
		var range_rate_code	=	document.getElementById('range_rate_code');
		
		if(range_name.value=='')
		{
				alert('Enter Range Rate Name.');
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
			url:'exp_save_rates.php?action=profilerangerates&range_from='+range_from.value+'&range_to='+range_to.value+'&zone_from='+zone_from.value+'&zone_to='+zone_to.value+'&range_rate='+range_rate.value+'&profile_id='+profile_id.value+'&range_rate_code='+range_rate_code.value+'&range_name='+range_name.value,
			type:'POST',
			success:function(res)
			{
				
				if(res.trim()=='error')
				{
					alert("Range already exists. ");
					$('#reload_range_rates_div').hide();
					return false;
				}
				else
				{
				window.location.href='exp_rate_profile.php?TYPE=edit&CODE='+profile_id.value;
				}
			}
			});
		}		
}


function delete_assigned_rate(assigned_id,profile_id)
{
	if(confirm('Do you really want to delete the Rate?'))
	{
		$('#reload_drivers_rates_div').show();
		if(assigned_id!='' && profile_id!="")
		{
			$.ajax({
				url:'exp_save_rates.php?action=deleteprofilerates&assigned_id='+assigned_id+'&profile_id='+profile_id,
				type:'POST',
				success:function(res)
				{
					window.location.href='exp_rate_profile.php?TYPE=edit&CODE='+profile_id;
				}
				});	
		}
	}
	else
	{
		return false;	
	}
}

function delete_profile_manual(id,profile_id)
{
	if(confirm('Do you really want to delete the Records?'))
	{
		$('#reload_manual_rates_div').show();
		if(id!='' && profile_id!='')
		{
			$.ajax({
				url:'exp_save_rates.php?action=deleteprofilemanual&profile_id='+profile_id+'&manual_id='+id,
				type:'POST',
				success:function(res)
				{
					window.location.href='exp_rate_profile.php?TYPE=edit&CODE='+profile_id;
				}
				});	
		}
	}
	else
	{
		return false;	
	}
}

function delete_profile_range(id,profile_id)
{
	if(confirm('Do you really want to delete the Records?'))
	{
		$('#reload_range_rates_div').show();
		
		if(id!='' && profile_id!='')
		{
			$.ajax({
				url:'exp_save_rates.php?action=deleteprofile_range&profile_id='+profile_id+'&range_id='+id,
				type:'POST',
				success:function(res)
				{
					window.location.href='exp_rate_profile.php?TYPE=edit&CODE='+profile_id;
				}
				});	
		}
	}
	else
	{
		return false;	
	}
}

function check_profile_name(pro_id)
{

	var profile_name=document.getElementById('PRO_NAME'+pro_id);
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

function check_edit_profile(prof_id)
{
	var ed_man_code=document.getElementById('ed_man_code'+prof_id).value;
	var ed_man_name=document.getElementById('ed_man_name'+prof_id).value;
	var ed_man_desc=document.getElementById('ed_man_desc'+prof_id).value;
	var ed_man_rate=document.getElementById('ed_man_rate'+prof_id).value;
	
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


function check_profile_range(pro_range_id)
{
	var ed_ran_code=document.getElementById('ed_ran_code'+pro_range_id).value;
	var ed_ran_name=document.getElementById('ed_ran_name'+pro_range_id).value;
	var ed_ran_frm=document.getElementById('ed_ran_frm'+pro_range_id).value;
	var ed_ran_to=document.getElementById('ed_ran_to'+pro_range_id).value;
	var ed_ran_frm_zone=document.getElementById('ed_ran_frm_zone'+pro_range_id).value;
	var ed_ran_to_zone=document.getElementById('ed_ran_to_zone'+pro_range_id).value;
	var ed_ran_rate=document.getElementById('ed_ran_rate'+pro_range_id).value;
	var ed_pro_id=document.getElementById('ed_pro_id').value;
	
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
			url:'exp_save_rates.php?action=profilerangeduplication&range_from='+ed_ran_frm+'&range_to='+ed_ran_to+'&range_rate='+ed_ran_rate+'&profile_range_id='+pro_range_id+'&ed_pro_id='+ed_pro_id,
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
				{
					document.getElementById("form_edit_range"+pro_range_id).submit();
				    return true;
				}
			}
			});
	return false;	
	}
	
}
</script>