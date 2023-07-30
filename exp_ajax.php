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
	$sts_debug = isset($_GET['debug']) && in_group( EXT_GROUP_DEBUG );
	require_once( "include/sts_session_class.php" );
	require_once( "include/sts_session_class.php" );
	$driver_manual_rates_str = '';	// Initialize variables
	$driver_range_rates_str = '';
	$driver_id	=	$_GET['id'];
	if($driver_id!=''){
	$my_session = sts_session::getInstance( $exspeedite_db, $sts_debug );
	$sql_obj	=	new sts_table($exspeedite_db , DRIVER_ASSIGN_RATES , $sts_debug);
	$pro_master=new sts_table($exspeedite_db , PROFILE_MASTER , $sts_debug);
	$driver_master=new sts_table($exspeedite_db , DRIVER_TABLE , $sts_debug);
	$man_code=new sts_table($exspeedite_db , MAN_CODE , $sts_debug);
    $ran_code=new sts_table($exspeedite_db , RAN_CODE, $sts_debug);

	$res['max_driver_id']	=	$sql_obj->database->get_multiple_rows("SELECT FIRST_NAME , MIDDLE_NAME , LAST_NAME  FROM ".DRIVER_TABLE."  WHERE  DRIVER_CODE='".$driver_id."'");
	$res['driver_name'] =	$res['max_driver_id'][0]['FIRST_NAME']." ".$res['max_driver_id'][0]['MIDDLE_NAME']." ".$res['max_driver_id'][0]['LAST_NAME'];
	
	#mona
	$res['drivers_arr']			=	$sql_obj->database->get_multiple_rows("SELECT ".DRIVER_ASSIGN_RATES.".*,".DRIVER_RATES.".* ,".CATEGORY_TABLE.".CATEGORY_NAME  FROM ".DRIVER_ASSIGN_RATES.",".DRIVER_RATES." ,".CATEGORY_TABLE." WHERE  ".DRIVER_ASSIGN_RATES.".DRIVER_ID='".$driver_id."'  AND  ".DRIVER_ASSIGN_RATES.".RATE_ID=".DRIVER_RATES.".RATE_ID  AND ".DRIVER_RATES.".CATEGORY=".CATEGORY_TABLE.".CATEGORY_CODE ");   
	$res['my_profile_id']=$driver_master->database->get_one_row("SELECT PROFILE_ID FROM ".DRIVER_TABLE." WHERE DRIVER_CODE=".$driver_id);
	 $res['all_profile_id']=$pro_master->database->get_multiple_rows("SELECT * FROM ".PROFILE_MASTER);
	  if(count($res['all_profile_id'])>0)
	 {//echo $res['my_profile_id']['PROFILE_ID'];
		 $profile_option=$selected='';
		 foreach($res['all_profile_id'] as $pro)
		 {$selected='';
			 if(count($res['my_profile_id'])>0){ if($pro['CONTRACT_ID']==$res['my_profile_id']['PROFILE_ID']){$selected='selected="selected"';}}
			 $profile_option.="<option value='".$pro['CONTRACT_ID']."' $selected>".$pro['CONTRACT_ID']."&nbsp;- &nbsp;".ucfirst(stripslashes($pro['PROFILE_NAME']))."</option>";
		 }
	 }
	 else
	 {
		 $profile_option=''; 
	 }

/*	$drivers	=	$sql_obj->database->get_multiple_rows("SELECT  PER_MILE , PER_DROP , PER_PICK_UP , SHIPPER_PERC , FUEL_SHIPPER_PERC 	, EXTRA_PERC , TAXABLE , PALLET_COUNT , EMPTY_MILES , LOADED_MILES 	, HOURS , LAYOVER , CHARGES , FUEL_SURCHARGE_PERC , FUEL_SURCHARGE_PER_MILE  FROM ".DRIVER_ASSIGN_RATES." WHERE DRIVER_ID='".$driver_id."'"); */
	
	
	$res['manual_rates']			=	$sql_obj->database->get_multiple_rows(" SELECT  *  FROM ".MANUAL_RATES." WHERE DRIVER_ID='".$driver_id."'");
	$res['range_rates']			=	$sql_obj->database->get_multiple_rows(" SELECT  *  FROM ".RANGE_RATES." WHERE DRIVER_ID='".$driver_id."'");
	
	
	
	/*$max_manual_id				=  $sql_obj->database->get_multiple_rows(" SELECT 	MAX(MANUAL_RATE_CODE) FROM ".MANUAL_RATES." ");	
	if($max_manual_id[0]['MAX(MANUAL_RATE_CODE)']==''){$res['MANUAL_RATE_CODE']='M101';}else{ $arr=explode('M',$max_manual_id[0]['MAX(MANUAL_RATE_CODE)']);$m=$arr[1]+1;  $res['MANUAL_RATE_CODE']='M'.$m;}
	*/
	
##--------changes of manual code issue-------##
	$max_manual_id				=  $man_code->database->get_multiple_rows(" SELECT  MAX(UNIQUE_MAN_CODE) FROM ".MAN_CODE." ");	
if($max_manual_id[0]['MAX(UNIQUE_MAN_CODE)']==''){$res['MANUAL_RATE_CODE']='M101';}else{ $arr=explode('M',$max_manual_id[0]['MAX(UNIQUE_MAN_CODE)']);$m=$arr[1]+1;  $res['MANUAL_RATE_CODE']='M'.$m;}

/*$max_range_id				=  $sql_obj->database->get_multiple_rows(" SELECT 	MAX(RANGE_CODE) FROM ".RANGE_RATES." ");	
if($max_range_id[0]['MAX(RANGE_CODE)']==''){$res['RANGE_RATE_CODE']='R101';}else{ $arrr=explode('R',$max_range_id[0]['MAX(RANGE_CODE)']);$r=$arrr[1]+1;  $res['RANGE_RATE_CODE']='R'.$r;}*/

  $zones	=	$sql_obj->database->get_multiple_rows(" SELECT  DISTINCT ZF_NAME  FROM ".ZONE_FILTER_TABLE);
##  ----generating range code from another table-$ran_code----------##
$max_range_id				=  $ran_code->database->get_multiple_rows(" SELECT MAX(UNIQUE_RANGE_CODE) FROM ".RAN_CODE." ");	
if($max_range_id[0]['MAX(UNIQUE_RANGE_CODE)']==''){$res['RANGE_RATE_CODE']='R101';}else{ $arrr=explode('R',$max_range_id[0]['MAX(UNIQUE_RANGE_CODE)']);$r=$arrr[1]+1;  $res['RANGE_RATE_CODE']='R'.$r;}
	
	 if(count($res['manual_rates'])>0)
			 {
				 foreach($res['manual_rates'] as $man_rates)
				 {
					$driver_manual_rates_str .=' <tr>
																	<td><a class="btn btn-default btn-xs " onclick="javascript: return delete_manual('.$man_rates["MANUAL_ID"].')"><span class="glyphicon glyphicon-trash"></span></a>	<a class="btn btn-default btn-xs " data-toggle="modal" data-target="#modal_edit'.$man_rates["MANUAL_ID"].'"><span class="glyphicon glyphicon-edit"></span></a>												</td>
																	<td style="font-size:12px; font-family:Arial, Helvetica, sans-serif; text-transform:uppercase; padding:0 6px;">Code</td>
																	<td><input type="text" name="textfield" id="textfield" size="15" class="form-control" value="'.$man_rates['MANUAL_RATE_CODE'].'" readonly="readonly"></td>
																	<td style="font-size:12px; font-family:Arial, Helvetica, sans-serif; text-transform:uppercase; padding:0 6px;">Name</td>
																	<td><input type="text" name="textfield" id="textfield" size="15" class="form-control" value="'.$man_rates['MANUAL_NAME'].'" readonly="readonly"></td>
																	<td style="font-size:12px; font-family:Arial, Helvetica, sans-serif; text-transform:uppercase; padding:0 6px;">Description</td>
																	<td><input type="text" name="textfield" id="textfield" size="15" class="form-control" value="'.$man_rates['MANUAL_RATE_DESC'].'" readonly="readonly"></td>
																	<td style="font-size:12px; font-family:Arial, Helvetica, sans-serif; text-transform:uppercase; padding:0 6px;">Rates</td>
																	<td><input type="text" name="textfield" id="textfield" size="15" class="form-control" value="'.$man_rates['MANUAL_RATE_RATE'].'" readonly="readonly"></td>
																	<td style="font-size:12px; font-family:Arial, Helvetica, sans-serif; text-transform:uppercase; padding:0 6px;">&nbsp;</td>
																	</tr>
																	<tr>
																	<td style="font-size:12px; font-family:Arial, Helvetica, sans-serif; text-transform:uppercase; padding:0 6px;" colspan="6">&nbsp;</td>
																	</tr>
																	<tr>
																	<td>
																	  <div class="modal fade in" id="modal_edit'.$man_rates['MANUAL_ID'].'" tabindex="-1" role="dialog">

<div class="modal-dialog">
<div class="modal-content form-horizontal" role="main">
<div class="modal-header">
<h4 class="modal-title">Edit Manual Rate</h4>
</div>
<div class="modal-body" style="font-size: 14px; body:inherit;">
<form name="form_edit_manual" id="form_edit_manual" action="exp_driverrate.php?id='.$driver_id.'" method="post">
<div class="btn-group">
<a href="#" class="btn btn-md btn-default" data-dismiss="modal"><span class="glyphicon glyphicon-remove"></span>Close</a>
<input type="submit" name="edt_man_rate" id="edt_man_rate" class="btn btn-md btn-success" onclick="javascript:return check_edit_manual('.$man_rates["MANUAL_ID"].');"  value="Save Changes"/>
</div>

<div class="form-group">
<input class="form-control" name="ed_driver_id" id="ed_driver_id" type="hidden" placeholder="Manual Code" value="'.$driver_id.'">
<input class="form-control" name="ed_man_id" id="ed_man_id" type="hidden" placeholder="Manual Code" value="'.$man_rates["MANUAL_ID"].'">
		<div class="form-group">
				<label for="Profile" class="col-sm-4 control-label">CODE</label>
				<div class="col-sm-4">
			   <input class="form-control" name="ed_man_code" id="ed_man_code'.$man_rates["MANUAL_ID"].'" type="text" placeholder="Manual Code" value="'.$man_rates["MANUAL_RATE_CODE"].'" readonly="readonly">
				</div>
			</div>
            
            <div class="form-group">
				<label for="Name" class="col-sm-4 control-label"> Name</label>
				<div class="col-sm-4">
			   <input class="form-control" name="ed_man_name" id="ed_man_name'.$man_rates["MANUAL_ID"].'" type="text" placeholder=" Name" value="'.$man_rates["MANUAL_NAME"].'">
				</div>
			</div>
            
            <div class="form-group">
				<label for="Description" class="col-sm-4 control-label">Description</label>
				<div class="col-sm-4">
			   <input class="form-control" name="ed_man_desc" id="ed_man_desc'.$man_rates["MANUAL_ID"].'" type="text" placeholder="Description" value="'.$man_rates["MANUAL_RATE_DESC"].'" >
				</div>
			</div>	

	
<div class="form-group">
				<label for="Rates" class="col-sm-4 control-label">Rates</label>
				<div class="col-sm-4">
			   <input class="form-control" name="ed_man_rate" id="ed_man_rate'.$man_rates["MANUAL_ID"].'" type="text" placeholder="Manual Rate" value="'.$man_rates["MANUAL_RATE_RATE"].'">
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
																	</tr>
																	';
				 }
			 }
			 else
			 {
					$driver_manual_rates_str .='<tr>
																	<td style="font-size:12px; font-family:Arial, Helvetica, sans-serif; text-transform:uppercase; padding:0 6px;" colspan="6">No Records Found.</td>
																	</tr>
																	'; 
			 }
			 
	 if(count($res['range_rates'])>0)
			 {
				 $driver_range_rate_title='<tr>
																<td style="font-size:12px; font-family:Arial, Helvetica, sans-serif; text-transform:uppercase; padding:0 6px;">&nbsp;</td>
																<td style="font-size:12px; font-family:Arial, Helvetica, sans-serif; text-transform:uppercase; padding:0 6px;">CODE</td>
																<td style="font-size:12px; font-family:Arial, Helvetica, sans-serif; text-transform:uppercase; padding:0 6px;">NAME</td>
																<td style="font-size:12px; font-family:Arial, Helvetica, sans-serif; text-transform:uppercase; padding:0 6px;">FROM</td>
																<td style="font-size:12px; font-family:Arial, Helvetica, sans-serif; text-transform:uppercase; padding:0 6px;">TO</td>
																<td style="font-size:12px; font-family:Arial, Helvetica, sans-serif; text-transform:uppercase; padding:0 6px;">FROM ZONE</td>
																<td style="font-size:12px; font-family:Arial, Helvetica, sans-serif; text-transform:uppercase; padding:0 6px;">TO ZONE</td>
																<td style="font-size:12px; font-family:Arial, Helvetica, sans-serif; text-transform:uppercase; padding:0 6px;">Rates</td>
																</tr>';
				foreach($res['range_rates'] as $ran_rates)
				 {$dr_frm_zone=$dr_to_zone='';
					  if(count($zones)>0)
					 {
						 foreach($zones as $drz)
						 {$sel='';
						 if($drz['ZF_NAME']==$ran_rates['ZONE_FROM'])
						 {$sel='selected="selected"';}
					    	$dr_frm_zone.='<option value="'.$drz['ZF_NAME'].'" '.$sel.'>'.$drz['ZF_NAME'].'</option>';
						 }
						 
						 foreach($zones as $dz)
						 {$to_sel='';
						 if($dz['ZF_NAME']==$ran_rates['ZONE_TO'])
						 {$to_sel='selected="selected"';}
					    	$dr_to_zone.='<option value="'.$dz['ZF_NAME'].'" '.$to_sel.'>'.$dz['ZF_NAME'].'</option>';
						 }
					 }
					$driver_range_rates_str .='
					 											<tr>
																	<td>
																	<a class="btn btn-default btn-xs " href="javascript:void(0);" onclick="javascript: return delete_range('.$ran_rates["RANGE_ID"].')" >
																	<span class="glyphicon glyphicon-trash"></span>
																	</a>
																	<a class="btn btn-default btn-xs " href="javascript:void(0);" data-toggle="modal" data-target="#modal_ed_range'.$ran_rates["RANGE_ID"].'"  ><span class="glyphicon glyphicon-edit"></span></a>
																	</td>
																	
																	<td style="padding:0 3px;"><input type="text" name="textfield" id="textfield" size="15" class="form-control" value="'.$ran_rates['RANGE_CODE'].'" readonly="readonly"></td>
																	
																	<td style="padding:0 3px;"><input type="text" name="textfield" id="textfield" size="15" class="form-control" value="'.$ran_rates['RANGE_NAME'].'" readonly="readonly"></td>
																	
																	<td style="padding:0 3px;"><input type="text" name="textfield" id="textfield" size="15" class="form-control" value="'.$ran_rates['RANGE_FROM'].'" readonly="readonly"></td>
																	
																	<td style="padding:0 3px;"><input type="text" name="textfield" id="textfield" size="15" class="form-control" value="'.$ran_rates['RANGE_TO'].'" readonly="readonly"></td>
																	
																	<td style="padding:0 3px;"><input type="text" name="textfield" id="textfield" size="15" class="form-control" value="'.$ran_rates['ZONE_FROM'].'" readonly="readonly"></td>
																	
																	<td style="padding:0 3px;"><input type="text" name="textfield" id="textfield" size="15" class="form-control" value="'.$ran_rates['ZONE_TO'].'" readonly="readonly"></td>
																	
																	<td style="padding:0 3px;"><input type="text" name="textfield" id="textfield" size="15" class="form-control" value="'.$ran_rates['RANGE_RATE'].'" readonly="readonly"></td>
																	<td style="font-size:12px; font-family:Arial, Helvetica, sans-serif; text-transform:uppercase; padding:0 6px;">&nbsp;</td>
																	
																	</tr>
																	<tr>
																	<td style="font-size:12px; font-family:Arial, Helvetica, sans-serif; text-transform:uppercase; padding:0 6px;" colspan="6">&nbsp;</td>
																	</tr>
																	<tr>
										<td>
										  <div class="modal fade in" id="modal_ed_range'.$ran_rates['RANGE_ID'].'" tabindex="-1" role="dialog">

<div class="modal-dialog">
<div class="modal-content form-horizontal" role="main">
<div class="modal-header">
<h4 class="modal-title">Edit Range Rate</h4>
</div>
<div class="modal-body" style="font-size: 14px; body:inherit;">
<form name="form_edit_range" id="form_edit_range'.$ran_rates['RANGE_ID'].'" action="exp_driverrate.php?id='.$driver_id.'" method="post">
<div class="btn-group">
<a href="#" class="btn btn-md btn-default" data-dismiss="modal"><span class="glyphicon glyphicon-remove"></span>Close</a>
<input type="submit" name="edit_range_rate" id="edit_range_rate" class="btn btn-md btn-success" onclick="javascript:return check_edit_driver_range('.$ran_rates['RANGE_ID'].','.$driver_id.');"  value="Save Changes"/>
</div>
<input type="hidden" name="ed_range_id" id="ed_range_id" value="'.$ran_rates['RANGE_ID'].'"/>
<input type="hidden" name="ed_driver_code" id="ed_driver_code" value="'.$driver_id.'"/>
<div class="form-group">

<div id="edit_loader" style="display:none;"><img border="1" style="position:absolute; margin-left:250px;margin-top:10px;" src="images/loading.gif"></div>
		<div class="form-group">
				<label for="Profile" class="col-sm-4 control-label">CODE</label>
				<div class="col-sm-4">
			   <input class="form-control" name="ed_ran_code" id="ed_ran_code'.$ran_rates['RANGE_ID'].'" type="text" placeholder="Manual Code" value="'.$ran_rates['RANGE_CODE'].'" readonly="readonly">
				</div>
			</div>
            
            <div class="form-group">
				<label for="Name" class="col-sm-4 control-label"> Name</label>
				<div class="col-sm-4">
			   <input class="form-control" name="ed_ran_name" id="ed_ran_name'.$ran_rates['RANGE_ID'].'" type="text" placeholder=" Name" value="'.$ran_rates['RANGE_NAME'].'">
				</div>
			</div>
            
            <div class="form-group">
				<label for="Description" class="col-sm-4 control-label">From</label>
				<div class="col-sm-4">
			  <input class="form-control" name="ed_ran_frm" id="ed_ran_frm'.$ran_rates['RANGE_ID'].'" type="text" placeholder=" From Range" value="'.$ran_rates['RANGE_FROM'].'" >
				</div>
			</div>	
            
               <div class="form-group">
				<label for="Description" class="col-sm-4 control-label">To</label>
				<div class="col-sm-4">
			    <input class="form-control" name="ed_ran_to" id="ed_ran_to'.$ran_rates['RANGE_ID'].'" type="text" placeholder=" To Range" value="'.$ran_rates['RANGE_TO'].'">
				</div>
			</div>	
            
               <div class="form-group">
				<label for="Description" class="col-sm-4 control-label">From Zone</label>
				<div class="col-sm-4">
			   <select name="ed_ran_frm_zone" id="ed_ran_frm_zone'.$ran_rates['RANGE_ID'].'" class="form-control">
               <option value="">From</option>
              '.$dr_frm_zone.'
               </select>
				</div>
			</div>	
            
              <div class="form-group">
				<label for="Description" class="col-sm-4 control-label">To Zone</label>
				<div class="col-sm-4">
			   <select name="ed_ran_to_zone" id="ed_ran_to_zone'.$ran_rates['RANGE_ID'].'" class="form-control">
               <option value="">To</option>
               '.$dr_to_zone.'
               </select>
				</div>
			</div>	

	
<div class="form-group">
				<label for="Rates" class="col-sm-4 control-label">Rates</label>
				<div class="col-sm-4">
			   <input class="form-control" name="ed_ran_rate" id="ed_ran_rate'.$ran_rates['RANGE_ID'].'" type="text" placeholder="Range Rate" value="'.$ran_rates['RANGE_RATE'].'" >
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
										</tr>
																	';
				 } 
				 $driver_range_rates_str=$driver_range_rate_title.$driver_range_rates_str;
			 }
			 else
			 {
					 $driver_range_rates_str .='<tr>
																	<td style="font-size:12px; font-family:Arial, Helvetica, sans-serif; text-transform:uppercase; padding:0 6px;" colspan="7">No Records Found.</td>
																	</tr>
																	';
			 }
	 
?>
<div class="well  well-md"><!--style="height:500px; overflow:auto; float:right; width:48%;"-->
        <div class="table-responsive" style="overflow:auto;  ">
          <h4><img height="16" alt="tractor_icon" src="images/driver_icon.png"> Driver : <?php echo $res['driver_name']; ?>
           <div id="reload_drivers_rates_div" style="position: absolute;display:none;"><img src="images/loading.gif" border="1" style="position:absolute; margin-left:250px;margin-top:10px;"></div>
          </h4>
           <h4><div style=" width:200px; ">Contract No :<select id="sel_profile" class="form-control"  name="sel_profile" onchange="return assign_profile(this.value,'<?php echo $driver_id;?>');">
		 <option value="">Select Profile ID</option>
		 <?php echo $profile_option;?>
		 </select></div>
           <div style=" width:140px; float:right;margin-top:-36px;"><button id="addtoprofile" class="btn btn-sm btn-success" type="button"  name="addtoprofile" onclick="javascript:return add_rating_to_profile(<?php echo $driver_id;?>);" data-toggle="modal" data-target="#myModal_profile"><span class="glyphicon glyphicon-ok"></span>Add to Rate Profile </button></div>
           </h4>
           
            <div class="modal fade in" id="myModal_profile" tabindex="-1" role="dialog">

<div class="modal-dialog">
<div class="modal-content" role="main">
<div class="modal-header">
<h4 class="modal-title">Add Profile Name</h4>
</div>
<div class="modal-body" style="font-size: 14px; body:inherit;">
<form name="add_profile_name" id="add_profile_name" action="exp_driverrate.php?id=<?php echo $driver_id;?>" method="post">
<div class="btn-group">
<a href="#" class="btn btn-md btn-default" data-dismiss="modal"><span class="glyphicon glyphicon-remove"></span>Close</a>
<input type="submit" name="save_profile_name" id="save_profile_name" class="btn btn-md btn-success" onclick="javascript:return check_profile_name();"  value="Save Changes"/>
</div>

<div class="form-group">
<input class="form-control text-right" name="DRIVER_ID" id="DRIVER_ID" type="hidden"   value="<?php echo $driver_id;?>" >
			

	
<div class="form-group">
				<label for="Profile" class="col-sm-4 control-label">Profile Name</label>
				<div class="col-sm-4">
			   <input class="form-control" name="PRO_NAME" id="PRO_NAME" type="text" placeholder="Profile Name" >
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

           
          <table class="table table-striped table-condensed table-bordered table-hover" >
            <thead>
              <tr class="exspeedite-bg">
                <th class="text-center"><span class="text-muted"><span class="glyphicon glyphicon-unchecked"></span></span></th>
                <th class="text-center">Rate Code</th>
                 <th class="text-center">Rate Name</th>
                <th class="text-center">Description</th>
                <th class="text-center">Rate</th>
               <!-- <th class="text-center">Bonus</th>-->
              </tr>
            </thead>
            <tbody>
          <?php   
		  // FETCHING ZONES
		  $zones	=	$sql_obj->database->get_multiple_rows(" SELECT  DISTINCT ZF_NAME  FROM ".ZONE_FILTER_TABLE);
		  
         #string for driver rate
if(count($res['drivers_arr']) >0 )
{
	foreach($res['drivers_arr'] as $rest)
	{
			#get all records of perticular rate code.
			?><tr style="cursor:pointer;">
			<td class="text-center"><a class="btn btn-default btn-xs inform" href="javascript:void(0);" onclick="javascript: return delete_assigned_rate('<?php echo $rest['ASSIGN_ID'];?>');"> <span style="font-size: 14px;">
			<span class="glyphicon glyphicon-trash"></span> </span> </a></td>
			<td style="text-align:center;"><?php echo $rest['RATE_CODE']; ?></td>
            <td style="text-align:center;"><?php echo $rest['RATE_NAME']; ?></td>
			<td style="text-align:center;"><?php echo $rest['CATEGORY_NAME']; ?></td>
			<td style="text-align:center;"><?php echo $rest['RATE_PER_MILES']; ?></td>
			<!--<td style="text-align:center;">'.$rest['RATE_BONUS'].'</td></tr>';-->
	<?php }
}
else
{ ?><tr style="cursor:pointer;" ><td class="text-center"><a class="btn btn-default btn-xs " href="javascropt:void(0);" > <span style="font-size: 14px;"> <span class="glyphicon glyphicon-"></span> </span> </a></td><td style="text-align:center;" colspan="4">DRIVER DOES NOT HAVE ANY RATES AVAILABLE.</td></tr>
<?php } ?>
            </tbody>
          </table>
          
           
          <h4>Add Manual Rates : <a class="btn btn-sm btn-success" href="javascript:void(0);" onclick="javascript: return add_more_fun('add_manual_rates');">
          <span class="glyphicon glyphicon-plus"></span> Add </a><div id="reload_manual_rates_div" style="display:none;"><img src="images/loading.gif" border="1" style="position:absolute; margin-left:250px;margin-top:10px;"></div></h4>
		  
		   <table width="100%" border="1" cellspacing="1" cellpadding="0" class="table table-striped table-condensed table-bordered table-hover">
            <tr>
              <td style="padding:10px; ">
			  	<table width="100%" border="0" cellspacing="0" cellpadding="0">			  
						 <?php echo  $driver_manual_rates_str; ?>
                </table>
                </td>
            </tr>
            <tr>
              <td>
			  <table width="100%" border="0" cellspacing="0" cellpadding="0" style="margin:5px;display:none;"  id="add_manual_rates">
			  <input type="hidden" name="manual_driver_id" id="manual_driver_id" size="15" class="form-control" value="<?php echo $driver_id; ?>" />
                  <tr>
                    <td style="font-size:12px; font-family:Arial, Helvetica, sans-serif; text-transform:uppercase; padding:0 6px;">Code</td>
                    <td><input type="text" name="manual_code" id="manual_code" size="15" class="form-control" value="<?php echo $res['MANUAL_RATE_CODE']; ?>" readonly="readonly"></td>
                     <td style="font-size:12px; font-family:Arial, Helvetica, sans-serif; text-transform:uppercase; padding:0 6px;">Name</td>
                    <td><input type="text" name="manual_name" id="manual_name" size="15" class="form-control"></td>
                    <td style="font-size:12px; font-family:Arial, Helvetica, sans-serif; text-transform:uppercase; padding:0 6px;">Description</td>
                    <td><input type="text" name="manual_desc" id="manual_desc" size="15" class="form-control"></td>
                    <td style="font-size:12px; font-family:Arial, Helvetica, sans-serif; text-transform:uppercase; padding:0 6px;">Rates</td>
                    <td><input type="text" name="manual_rate" id="manual_rate" size="15" class="form-control"></td>
					<td style="font-size:12px; font-family:Arial, Helvetica, sans-serif; text-transform:uppercase; padding:0 6px;">&nbsp;</td>
                    <td><input name="save_manual_rate" id="save_manual_rate" type="button" class="btn btn-md btn-default" value="Submit" onclick="javascript:return add_manual_rates();"></td>
                  </tr>
                </table></td>
            </tr>
          </table>
		  <h4>Range of rates :  <a class="btn btn-sm btn-success" href="javascript:void(0);" onclick="javascript: return add_more_fun('add_range_rates');"><span class="glyphicon glyphicon-plus"></span> Add </a>
          <div id="reload_range_rates_div" style="display:none;"><img src="images/loading.gif" border="1" style="position:absolute; margin-left:250px;margin-top:10px;"></div>
          </h4>
          <table width="100%" border="1" cellspacing="1" cellpadding="0" class="table table-striped table-condensed table-bordered table-hover">
            <tr>
              <td style="padding:10px; "><table width="100%" border="0" cellspacing="0" cellpadding="0">
                  <?php echo $driver_range_rates_str; ?>
                 
                </table></td>
            </tr>
            
            <tr>
              <td><table width="100%" border="0" cellspacing="0" cellpadding="0" style="margin:5px;display:none;"   id="add_range_rates">
			  	  <input type="hidden" name="range_driver_id" id="range_driver_id" size="15" class="form-control" value="<?php echo $driver_id; ?>" />
                  <tr>
                  		<td style="font-size:12px; font-family:Arial, Helvetica, sans-serif; text-transform:uppercase; padding:0 3px;">Code</td>
                        <td style="font-size:12px; font-family:Arial, Helvetica, sans-serif; text-transform:uppercase; padding:0 3px;">Name</td>
                        <td style="font-size:12px; font-family:Arial, Helvetica, sans-serif; text-transform:uppercase; padding:0 3px;">From</td>
                        <td style="font-size:12px; font-family:Arial, Helvetica, sans-serif; text-transform:uppercase; padding:0 3px;">to</td>
                        <td style="font-size:12px; font-family:Arial, Helvetica, sans-serif; text-transform:uppercase; padding:0 3px;">Zone From</td>
                        <td style="font-size:12px; font-family:Arial, Helvetica, sans-serif; text-transform:uppercase; padding:0 3px;">Zone To</td>
                        <td style="font-size:12px; font-family:Arial, Helvetica, sans-serif; text-transform:uppercase; padding:0 3px;">Rates</td>
                  </tr>
                  <tr>
                  
                    <td><input type="text" name="range_rate_code" id="range_rate_code" size="15" class="form-control" value="<?php echo $res['RANGE_RATE_CODE']; ?>" readonly="readonly"></td>
                       <td style="padding:0 3px;"><input type="text" name="range_name" id="range_name" size="15" class="form-control" value=""></td>
                    <td style="padding:0 3px;"><input type="text" name="range_from" id="range_from" size="15" class="form-control" value=""></td>
                    
                    <td style="padding:0 3px;"><input type="text" name="range_to" id="range_to" size="15" class="form-control" value=""></td>
                    
                    <td style="padding:0 3px;">
                    	<select name="zone_from" id="zone_from" class="form-control">
							<option value="">From</option>
							<?php
							foreach($zones as $zone){
							?>		
                            <option value="<?php echo $zone['ZF_NAME'];?>"><?php echo $zone['ZF_NAME'];?></option>
                            <?php } ?>
						</select>
                    </td>
                    
                    <td style="padding:0 3px;">
                    	<select name="zone_to" id="zone_to" class="form-control">
							<option value="">To</option>
							<?php
							foreach($zones as $zone){
							?>		
                            <option value="<?php echo $zone['ZF_NAME'];?>"><?php echo $zone['ZF_NAME'];?></option>
                            <?php } ?>
						</select>
                    </td>
                    
                    <td style="padding:0 3px;"><input type="text" name="range_rate" id="range_rate" size="15" class="form-control" value=""></td>
					<td style="font-size:12px; font-family:Arial, Helvetica, sans-serif; text-transform:uppercase; padding:0 6px;">&nbsp;</td>
                    <td><input name="save_range_rates"  id="save_range_rates" type="button" class="btn btn-md btn-default" value="Submit" onclick="javascript: return add_rate_range();"></td>
                  </tr>
                </table></td>
            </tr>
          </table>
        </div>
      </div>
    </div>
    <div style="clear:both"></div>
  </div>
</form>
  </div>
</div>
      <?php } ?>