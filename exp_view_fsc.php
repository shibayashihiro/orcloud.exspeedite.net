<?php 

//error_reporting(E_ALL);
// Set flag that this is a parent file
define( '_STS_INCLUDE', 1 ); //used to set a constant

// Setup Session
require_once( "include/sts_session_setup.php" );

require_once( "include/sts_config.php" );
$sts_debug = (isset($_GET['debug']) || isset($_POST['debug']))  && in_group( EXT_GROUP_DEBUG );
require_once( "include/sts_session_class.php" );
require_once("include/sts_client_class.php");
require_once( "include/sts_result_class.php" );
require_once( "include/sts_setting_class.php" );

$setting_table = sts_setting::getInstance($exspeedite_db, $sts_debug);
$sts_fsc_4digit = $setting_table->get( 'option', 'FSC_4DIGIT' ) == 'true';

$my_session = sts_session::getInstance( $exspeedite_db, $sts_debug );
$my_session->access_check( EXT_GROUP_USER );	// Make sure we should be here

$sts_subtitle = "FSC Schedule";
require_once( "include/header_inc.php" );
require_once( "include/navbar_inc.php" );

$sql_obj	=	new sts_table($exspeedite_db , FSC_SCHEDULE , $sts_debug);
$client_fsc=new sts_table($exspeedite_db , CLIENT_FSC , $sts_debug);
$fsc_his=new sts_table($exspeedite_db , FSC_HISTORY , $sts_debug);

$client_obj =	new sts_client($exspeedite_db , $sts_debug);
$res_obj = new sts_result( $client_obj , false, $sts_debug );
//print_r($_SESSION);

/*
echo "<pre>";
var_dump($_GET);
var_dump($_POST);
echo "</pre>";
*/

if(isset($_GET['TYPE']) && $_GET['TYPE']=='all')
{
	if(isset($_POST['add_fsc_schedule']))
	{
		$fsc=$_POST['fsc'];
		$fsc_name=$_POST['fsc_name'];
		$fsc_average_price=$_POST['fsc_average_price'];
		$fsc_starting_date=date('Y-m-d',strtotime($_POST['fsc_starting_date']));
		$fsc_end_date=date('Y-m-d',strtotime($_POST['fsc_end_date']));
		
		if( isset($_FILES) && isset($_FILES['fsc_schedule']) && isset($_FILES['fsc_schedule']['tmp_name']) && $_FILES['fsc_schedule']['tmp_name'] <> '') {
			$fsc_csv=fopen($_FILES['fsc_schedule']['tmp_name'],"r");
			
			while(($data=fgetcsv($fsc_csv,1000,',')) != FALSE)
			{	
				if( $data[0] <> 'Low Per Gallon' && $data[1] <> 'High Per Gallon') {

					//! 3/7 - Duncan, removed unnecessary quotes around numbers.
					$fields='FSC_UNIQUE_ID, FSC_NAME, AVERAGE_PRICE, STARTING_DATE,
						FSC_ENDING_DATE, LOW_PER_GALLON, HIGH_PER_GALLON, FLAT_AMOUNT,
						PER_MILE_ADJUST, PERCENT, START_DATE, END_DATE';

		$values="'".$fsc."', '".$fsc_name."', ".$fsc_average_price.", '".$fsc_starting_date."',
			'".$fsc_end_date."', ".$data[0].", ".$data[1].", ".$data[2].",
			".$data[3].", ".$data[4].",'".date('Y-m-d',strtotime($data[5]))."','".date('Y-m-d',strtotime($data[6]))."'";

						$sql_obj->add_row( $fields, $values);
				}
			}
		} else {
			//! No file - add dummy row
			//! 3/7 - Duncan, removed unnecessary quotes around numbers.
			$fields='FSC_UNIQUE_ID, FSC_NAME, AVERAGE_PRICE, STARTING_DATE,
				FSC_ENDING_DATE, LOW_PER_GALLON, HIGH_PER_GALLON, FLAT_AMOUNT,
				PER_MILE_ADJUST, PERCENT, START_DATE, END_DATE';

		$values="'".$fsc."', '".$fsc_name."', ".$fsc_average_price.", '".$fsc_starting_date."',
			'".$fsc_end_date."', ".$fsc_average_price.", ".$fsc_average_price.", 0,
			 0, 0, '".$fsc_starting_date."','".$fsc_end_date."'";

			$sql_obj->add_row( $fields, $values);
		}
		
	}
	$max_fsc=$sql_obj->fetch_rows('','MAX(FSC_UNIQUE_ID) AS max_id');
	if($max_fsc[0]['max_id']=='')
	{
		$res['fsc_unique_id']='F101';
	}
	else
	{
		$arrr=explode('F',$max_fsc[0]['max_id']);
		//print_r($arrr);
		$fsc=$arrr[1]+1;  ;
		$res['fsc_unique_id']='F'.$fsc;
	}
	$res['fsc_records']=$sql_obj->database->get_multiple_rows("
		SELECT * FROM ".FSC_SCHEDULE." GROUP BY FSC_UNIQUE_ID");
	$tab='<tbody>';
	if(count($res['fsc_records'])>0)
		{
			$i=1;
			foreach($res['fsc_records'] as $fsc)
			{
				$average=array();
				$new_fsc_rate="---";
				$average=$sql_obj->database->get_multiple_rows("
					SELECT q.* FROM
						(SELECT * FROM
							(SELECT AVERAGE_PRICE  AS AV FROM ".FSC_SCHEDULE."
							WHERE FSC_UNIQUE_ID='".$fsc['FSC_UNIQUE_ID']."')AV,
						".FSC_SCHEDULE."
						WHERE FSC_UNIQUE_ID='".$fsc['FSC_UNIQUE_ID']."'
						HAVING AV BETWEEN `LOW_PER_GALLON` AND `HIGH_PER_GALLON`)q
					GROUP BY FSC_ID");
				if(count($average)>0)
				{
					if($average[0]['FLAT_AMOUNT']!="0.00")
					{
						$new_fsc_rate='Flat Amount  - '.$average[0]['FLAT_AMOUNT'];
					}
					else  if($average[0]['PER_MILE_ADJUST']!="0.00")
					{
						$new_fsc_rate='Per Mile Adjust - '.$average[0]['PER_MILE_ADJUST'];
					}
					else 
					{
						$new_fsc_rate='Percent - '.$average[0]['PERCENT'];
					}
				}
				$x='&quot;'.$fsc['FSC_UNIQUE_ID'].'&quot;';
				$tab.="<tr style='cursor:pointer;' >
       						 <td style='text-align:center;'>".$fsc['FSC_UNIQUE_ID']."</td>
							  <td style='text-align:center;'>".$fsc['FSC_NAME']."</td>
      						 <td style='text-align:center;'>".$fsc['AVERAGE_PRICE']."</td>
							 <td style='text-align:center;'>".$new_fsc_rate."</td>
							  <td style='text-align:center;'>".date('m/d/Y',strtotime($fsc['STARTING_DATE']))."</td>
							   <td style='text-align:center;'>".date('m/d/Y',strtotime($fsc['FSC_ENDING_DATE']))."</td>
      					     <td style='text-align:center;' >
					<div style='width: 56px;'>
					<div class='btn-group btn-group-xs'>
                    <a href='exp_view_fsc.php?TYPE=edit&CODE=".$fsc['FSC_UNIQUE_ID']."' class='btn btn-default btn-xs inform'  id=edit_".$fsc['FSC_UNIQUE_ID']." data-placement='bottom' data-toggle='popover' data-content='Edit fsc rates ".$fsc['FSC_UNIQUE_ID']."'> <span style='font-size: 14px;'><span class='glyphicon glyphicon-edit'></span></span></a>
                    
                    <a class='btn btn-default btn-xs'  id=del_".$fsc['FSC_UNIQUE_ID']."  onclick='return delete_fsc_schedule($x);'><span style='font-size: 14px;'><span class='glyphicon glyphicon-trash'></span> </span> </a></div>
						</div>
					</td>
      						</tr>";
		 $i++;}
		}
		else
		{
			$tab.="<tr style='cursor:pointer;' >
       						 <td style='text-align:center;' colspan='5'>NO RECORDS ARE AVAILABLE !</td>
							 </tr>";
		}
	$tab.='<tbody>';
	$res['tab']=$tab;
	$res_obj->render_manage_fsc($res);
}


//! Update at the top	
if(isset($_GET['TYPE']) && $_GET['TYPE']=='edit' && $_GET['CODE']!="") {
	//! edit
	if( $sts_debug ) {
		echo "<p>type=edit code=".$_GET['CODE']."</p>";
		echo "<pre>";
		var_dump($_POST);
		echo "</pre>";
	}
	$fsc_unique_id=$_GET['CODE'];
	if(isset($_POST['upload_schedule'])) {
			$fsc=$_POST['fsc'];
			$fsc_name=$_POST['fsc_name'];
			$fsc_average_rate=$fsc_new_column='';
			$fsc_average_price=$_POST['fsc_average_price'];
			if(isset($_POST['fsc_new_field']) && isset($_POST['fsc_new_column'])) {
				$fsc_average_rate=$_POST['fsc_new_field'];
				$fsc_new_column=$_POST['fsc_new_column'];
			}
			$fsc_starting_date=date('Y-m-d',strtotime($_POST['fsc_starting_date']));
			$fsc_end_date=date('Y-m-d',strtotime($_POST['fsc_end_date']));
			
			//! maintain history before updating fsc record---------------------##
			$prev_fsc_detail=$sql_obj->database->get_one_row("SELECT * FROM ".FSC_SCHEDULE." WHERE FSC_UNIQUE_ID='".$fsc."'");
			//print_r($prev_fsc_detail);exit;
			if(count($prev_fsc_detail)>0 ) {
				$check = $fsc_his->fetch_rows("FSC_START_DATE = '".$prev_fsc_detail['STARTING_DATE']."' AND FSC_END_DATE = '".$prev_fsc_detail['FSC_ENDING_DATE']."' AND FSC_UNIQUE_ID='".$fsc."'");
				if( ! is_array($check) || count($check) == 0 ) {

					//! 3/7 - Duncan, removed unnecessary quotes around numbers.
					$his_field='FSC_UNIQUE_ID, FSC_NAME,
						FSC_AV_PRICE, FSC_COLUMN,
						AVERAGE_RATE, FSC_START_DATE,
						FSC_END_DATE, FSC_MODIFIED_ON,
						ADDED_BY';

		$his_val="'".$prev_fsc_detail['FSC_UNIQUE_ID']."', '".$prev_fsc_detail['FSC_NAME']."',
			".(isset($prev_fsc_detail['AVERAGE_PRICE']) ? $prev_fsc_detail['AVERAGE_PRICE'] : 0).", '".$prev_fsc_detail['FSC_COLUMN']."',
			".(isset($prev_fsc_detail['FSC_AVERAGE_RATE']) ? $prev_fsc_detail['FSC_AVERAGE_RATE'] : 0).", '".$prev_fsc_detail['STARTING_DATE']."',
			'".$prev_fsc_detail['FSC_ENDING_DATE']."','".date('Y-m-d')."',
			".$_SESSION['EXT_USER_CODE'];

					$fsc_his->add_row($his_field, $his_val);
				}
			}
			
			if($_FILES['fsc_schedule']['tmp_name']!="")
			{
					$handle = fopen($_FILES['fsc_schedule']['tmp_name'], "r");
					// Empty all table
					$sql_obj->database->get_one_row("
						DELETE FROM ".FSC_SCHEDULE."
						WHERE FSC_UNIQUE_ID='".$fsc_unique_id."'");

					while (($data = fgetcsv($handle, 1000, ",")) != FALSE) {

						//! 3/7 - Duncan, removed unnecessary quotes around numbers.
						$fields='FSC_UNIQUE_ID, FSC_NAME, AVERAGE_PRICE,
							FSC_COLUMN, FSC_AVERAGE_RATE, STARTING_DATE,
							FSC_ENDING_DATE, LOW_PER_GALLON, HIGH_PER_GALLON,
							FLAT_AMOUNT, PER_MILE_ADJUST, PERCENT,
							START_DATE, END_DATE';

				$values="'".$fsc."', '".$fsc_name."', ".$fsc_average_price.",
					'".$fsc_new_column."', ".$fsc_average_rate.", '".$fsc_starting_date."',
					'".$fsc_end_date."', ".$data[0].", ".$data[1].",
					".$data[2].", ".$data[3].", ".$data[4].",
					'".date('Y-m-d',strtotime($data[5]))."', '".date('Y-m-d',strtotime($data[6]))."'";

						$sql_obj->add_row( $fields, $values);
					}
					//$client_fsc->database->get_multiple_rows("DELETE FROM ".CLIENT_FSC);
					fclose($handle);
			} else {
				$fsc_id=$sql_obj->database->get_multiple_rows("
					SELECT FSC_ID FROM ".FSC_SCHEDULE."
					WHERE FSC_UNIQUE_ID='".$fsc_unique_id."' ");

				if(count($fsc_id)>0) {
					foreach($fsc_id as $fs) {
						$sql_obj->update($fs['FSC_ID'],
						array('FSC_NAME'=>$fsc_name, 'AVERAGE_PRICE'=>$fsc_average_price,
						'FSC_COLUMN'=>$fsc_new_column, 'FSC_AVERAGE_RATE'=>$fsc_average_rate,
						'STARTING_DATE'=>$fsc_starting_date, 'FSC_ENDING_DATE'=>$fsc_end_date));
					}
				}
			}
	}
	
	$fsc_per_mile_field=$fsc_column='';
	$fsc_average=array();
	$fsc_average=$sql_obj->database->get_multiple_rows("
		SELECT q.* FROM
			(SELECT * FROM
				(SELECT AVERAGE_PRICE AS AV
				FROM ".FSC_SCHEDULE."
				WHERE FSC_UNIQUE_ID='".$fsc_unique_id."')AV,
			".FSC_SCHEDULE."
			WHERE FSC_UNIQUE_ID='".$fsc_unique_id."'
			HAVING  AV BETWEEN `LOW_PER_GALLON` AND `HIGH_PER_GALLON`)q
		GROUP BY FSC_ID");
	//echo count($fsc_average);
	//echo '<pre/>';print_r($fsc_average);
	if(count($fsc_average)>0) {
		if($fsc_average[0]['FLAT_AMOUNT']!="0.00") {
			$fsc_per_mile_field=$fsc_average[0]['FLAT_AMOUNT'];
			$fsc_column='Flat Amount';
		} else  if($fsc_average[0]['PER_MILE_ADJUST']!="0.00") {
			$fsc_per_mile_field=$fsc_average[0]['PER_MILE_ADJUST'];
			$fsc_column='Per Mile Adjust';
		} else {
			$fsc_per_mile_field=$fsc_average[0]['PERCENT'];
			$fsc_column='Percent';
		}
		$fsc_average=$fsc_average[0]['AV'];
	}
	$res['fsc_per_mile_field']=$fsc_per_mile_field;
	$res['fsc_column']=$fsc_column;
	$res['fsc_average']=$fsc_average;
	//echo '<pre/>';print_r($fsc_average);
	$res['records']=$sql_obj->database->get_multiple_rows("
		SELECT * FROM ".FSC_SCHEDULE."
		WHERE FSC_UNIQUE_ID='".$fsc_unique_id."'");
	if(count($res['records'])==0)
	{echo '<script type="text/javascript" language="javascript">window.location.href="exp_view_fsc.php?TYPE=all";</script>';}
	$res['FSC_4DIGIT']=$sts_fsc_4digit;
	$res_obj->render_fsc_screen($res);
}

//! Edit FSC
if(isset($_GET['TYPE']) && $_GET['TYPE']=='editfsc' && $_GET['CODE']!="")
{
	//! WIP
	if( $sts_debug ) {
		echo "<p>type=editfsc code=".$_GET['CODE']."</p>";
		echo "<pre>";
		var_dump($_POST);
		echo "</pre>";
	}
	 $res['CODE']=$_GET['CODE'];
	 if(isset($_POST['update_single_fsc']))
	 {
		 $low_per_gallon=$_POST['low_per_gallon'];
		 $high_per_gallon=$_POST['high_per_gallon'];
		 $flat_amount=$_POST['flat_amount'];
		 $per_mile_adjust=$_POST['per_mile_adjust'];
		 $percent=$_POST['percent'];
		 $start_date=date('Y-m-d',strtotime($_POST['start_date']));
		 $end_date=date('Y-m-d',strtotime($_POST['end_date']));
		 	
		 $sql_obj->update($_GET['CODE'],
			array('LOW_PER_GALLON'=>$low_per_gallon, 'HIGH_PER_GALLON'=>$high_per_gallon,
			'FLAT_AMOUNT'=>$flat_amount, 'PER_MILE_ADJUST'=>$per_mile_adjust,
			'PERCENT'=>$percent, 'START_DATE'=>$start_date, 'END_DATE'=>$end_date));
		 
	}
	$res['fsc_detail']=$sql_obj->fetch_rows('FSC_ID="'.$res['CODE'].'"');
	$res['FSC_4DIGIT']=$sts_fsc_4digit;
	$res_obj->render_edit_single_fsc($res);
}

//! Add FSC
if(isset($_GET['TYPE']) && $_GET['TYPE']=='addfsc' && $_GET['CODE']!="")
{
	if(isset($_POST['add_single_fsc']))
	{
		 $low_per_gallon=$_POST['low_per_gallon'];
		 $high_per_gallon=$_POST['high_per_gallon'];
		 $flat_amount=$_POST['flat_amount'];
		 $per_mile_adjust=$_POST['per_mile_adjust'];
		 $percent=$_POST['percent'];
		 $start_date=date('Y-m-d',strtotime($_POST['start_date']));
		 $end_date=date('Y-m-d',strtotime($_POST['end_date']));
		 
		 $getfsc=$sql_obj->fetch_rows("FSC_UNIQUE_ID ='".$_GET['CODE']."'",
		 	"FSC_NAME, AVERAGE_PRICE, STARTING_DATE, FSC_ENDING_DATE");
		// print_r($getfsc);

		//! 3/7 - Duncan, removed unnecessary quotes around numbers.
		$sql_obj->add_row("FSC_UNIQUE_ID, FSC_NAME, AVERAGE_PRICE,
			STARTING_DATE, FSC_ENDING_DATE,
			LOW_PER_GALLON, HIGH_PER_GALLON, FLAT_AMOUNT, PER_MILE_ADJUST,
			PERCENT, START_DATE, END_DATE",
			
		"'".$_GET['CODE']."', '".$getfsc[0]['FSC_NAME']."', ".$getfsc[0]['AVERAGE_PRICE'].",
		'".$getfsc[0]['STARTING_DATE']."', '".$getfsc[0]['FSC_ENDING_DATE']."',
		".$low_per_gallon.", ".$high_per_gallon.", ".$flat_amount.", ".$per_mile_adjust.",
		".$percent.", '".$start_date."', '".$end_date."'");
		 
	}
	$res['FSC_UNIQUE_ID']=$_GET['CODE'];
	$res['FSC_4DIGIT']=$sts_fsc_4digit;
	$res_obj->render_add_single_fsc($res);
}
?>

<?php
require_once( "include/footer_inc.php" );
?>
<script language="javascript" type="text/javascript">
function validte_schedule(func_type)
{
	var filename=document.getElementById('fsc_schedule').value;
	var ext = filename.substring(filename.lastIndexOf('.') + 1);
	var fsc_average_price=document.getElementById('fsc_average_price').value;
	var fsc_starting_date=document.getElementById('fsc_starting_date').value;
	var fsc_end_date=document.getElementById('fsc_end_date').value;
	var start_date1=fsc_starting_date.split("/");
	var new_start_date=new Date(start_date1[2]+" "+start_date1[0]+" "+start_date1[1]);
	var end_date1=fsc_end_date.split("/");
	var new_end_date=new Date(end_date1[2]+" "+end_date1[0]+" "+end_date1[1]);
	var fsc_name=document.getElementById('fsc_name').value;
	
	
	if(fsc_name=="")
	{
		alert("Please enter FSC fsc name.");
		return false;
	}
	else if(fsc_average_price=="")
	{
		alert("Please enter FSC average price.");
		return false;
	}
	else if(isNaN(fsc_average_price))
	{
		alert("Please enter FSC average price in numbers.");
		return false;
	}
	else if(fsc_starting_date=="")
	{
		alert("Please select FSC starting date.");
		return false;
	}
	else if(fsc_end_date=="")
	{
		alert("Please select FSC end date.");
		return false;
	}
	else if(new_start_date >new_end_date)
	{
		alert(" End date can not be less than start date.");
		return false;
	}
	/*
	else if (func_type=="add" && filename=="")
	{
		alert("Please select file to upload");	
		return false;
	}
	*/
	else if(filename!="" && ext!="csv"&& ext!="CSV")
	{
		alert("Upload CSV file only");
        return false;
	}
	else
	{
		
		return true;	
	}
}

function delete_fsc_schedule(fsc_unique_id)
{
	if(fsc_unique_id!="")
	{
		if(confirm("Do you really want to delete record?"))
		{
			$.ajax({
						url:'exp_save_rates.php?action=deletefsc&fsc_unique_id='+fsc_unique_id,
						success:function(res)
						{
							window.location.href='exp_view_fsc.php?TYPE=all';
						}
					  });
		}
		else
		{
			return false;
		}
	}
}

function delete_single_fsc(fsc_id,unique_id)
{
	if(fsc_id!="" && unique_id!="")
	{
		if(confirm("Do you really want to delete fsc record?"))
		{
			$.ajax({
				url:'exp_save_rates.php?action=deletesinglefsc&fsc_id='+fsc_id,
				success:function(res)
				{
					window.location.href='exp_view_fsc.php?TYPE=edit&CODE='+unique_id;
				}
			});
		}
	}
	else
	{
		return false;
	}
}

function edit_fsc()
{
	var low_per_gallon=document.getElementById('low_per_gallon');
	var high_per_gallon=document.getElementById('high_per_gallon');
	var flat_amount=document.getElementById('flat_amount');
	var per_mile_adjust=document.getElementById('per_mile_adjust');
	var percent=document.getElementById('percent');
	var start_date=document.getElementById('start_date');
	var end_date=document.getElementById('end_date');
	var start_date1=start_date.value.split("/");
	var new_start_date=new Date(start_date1[2]+" "+start_date1[0]+" "+start_date1[1]);
	
	var end_date1=end_date.value.split("/");
	var new_end_date=new Date(end_date1[2]+" "+end_date1[0]+" "+end_date1[1]);

	if(low_per_gallon.value=="")
	{
		alert("Enter low per gallon value");
		return false;
	}
	else if(isNaN(low_per_gallon.value))
	{
		alert("Enter low per gallon value in numbers");
		return false;
	}
	else if(high_per_gallon.value=="")
	{
		alert("Enter high per gallon value");
		return false;
	}
	else if(isNaN(high_per_gallon.value))
	{
		alert("Enter high per gallon value in numbers");
		return false;
	}
	else if(flat_amount.value=="")
	{
		alert("Enter flat amount");
		return false;
	}
	else if(isNaN(flat_amount.value))
	{
		alert("Enter flat amount in numbers");
		return false;
	}
	else if(per_mile_adjust.value=="")
	{
		alert("Enter per mile adjust");
		return false;
	}
	else if(isNaN(per_mile_adjust.value))
	{
		alert("Enter per mile adjust in numbers");
		return false;
	}
	else if(percent.value=="")
	{
		alert("Enter percent");
		return false;
	}
	else if(isNaN(percent.value))
	{
		alert("Enter percent in numbers");
		return false;
	}
	else if(start_date.value=="")
	{
		alert("Enter start date");
		return false;
	}
	else if(end_date.value=="")
	{
		alert("Enter end date");
		return false;
	}
	else if(new_start_date >new_end_date)
	{
		alert(" End date can not be less than start date.");
		return false;
	}
	
	
}


function chk_avg_price(fsc_val,fsc_id)
{
	if(fsc_val=="" ||  isNaN(fsc_val))
	{
		alert("Please enter valid fsc average price.");
		return false;
	}
	else
	{
		$('#edit_fscloader').show();
		$.ajax({
			url:'exp_save_rates.php?action=fetch_fsc_avg&fsc_val='+fsc_val+"&fsc_id="+fsc_id,
			dataType:'json',
			success:function(res)
			{
				if(res.fsc_column!="" && res.fsc_per_mile_field!="") 
				{
				$('#fsc_col').html(res.fsc_column);
				$('#fsc_col_val').html("<input type='text' name='fsc_new_field' id='fsc_new_field' class='form-control' value=\""+res.fsc_per_mile_field+"\"  style='width:60%;' readonly/><input type='hidden' name='fsc_new_column' id='fsc_new_column' class='form-control' value=\""+res.fsc_column+"\"/>");
				}
				else
				{
					$('#fsc_col_val').html("");
						$('#fsc_col').html("");
				}
				$('#edit_fscloader').hide();
			}
		});
	}
}
</script>

	<script language="JavaScript" type="text/javascript"><!--
		$(document).ready( function () {
			$('#FSC_TABLE').dataTable({
		        //"bLengthChange": false,
		        "bFilter": false,
		        stateSave: true,
		        "bSort": true,	//! SCR# 579
		        "bInfo": true,
				"bAutoWidth": false,
				//"bProcessing": true, 
				//"sScrollX": "100%",
				//"sScrollY": "400px",
				//"sScrollXInner": "120%",
				"bPaginate": false,
				"bScrollCollapse": false,
				"bSortClasses": false,
				"order": [[ 1, "desc" ]],
				//"dom": "frtiS",
			});				
		});				
	//--></script>

<?php

require_once( "include/footer_inc.php" );
?>
