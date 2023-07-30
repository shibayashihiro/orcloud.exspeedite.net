<?php 
// Set flag that this is a parent file
define( '_STS_INCLUDE', 1 );

// Setup Session
require_once( "include/sts_session_setup.php" );

require_once( "include/sts_config.php" );
$sts_debug = isset($_GET['debug']) && in_group( EXT_GROUP_DEBUG );
require_once( "include/sts_session_class.php" );
$my_session = sts_session::getInstance( $exspeedite_db, $sts_debug );
$my_session->access_check( EXT_GROUP_USER );	// Make sure we should be here


$sts_subtitle = "Update Client Rate";
require_once( "include/header_inc.php" );
require_once( "include/navbar_inc.php" );

?>
<div class="container-full" role="main">
<?php
require_once( "include/sts_result_class.php" );
require_once( "include/sts_shipment_class.php" );

$client_obj=new sts_table($exspeedite_db , CLIENT_CAT_RATE , $sts_debug);
$cat_obj=new sts_table($exspeedite_db , CLIENT_CAT , $sts_debug);

$shipment_table = sts_shipment::getInstance($exspeedite_db, $sts_debug);
$rslt = new sts_result( $shipment_table, false, $sts_debug );

$res['client_id']=$client_id=$_GET['CODE'];
$taxable='No';
if(isset($_POST['btn_update_clientrate']))
{
	$rate_code=$_POST['RATE_CODE'];
	$rate_name=$_POST['RATE_NAME'];
	$category=$_POST['CATEGORY'];
	$rpm=$_POST['RATE_PER_MILES'];
	if(isset($_POST['TAXABLE']) && $_POST['TAXABLE']=='TAXABLE')
	{$taxable='Yes';}
	$rate_desc=$_POST['RATE_DESC'];
	
	
	$client_obj->update($client_id,array('RATE_CODE'=>$rate_code,'RATE_NAME'=>$rate_name,'CATEGORY'=>$category,'RATE_PER_MILES'=>$rpm,'TAXABLE'=>$taxable,'RATE_DESC'=>$rate_desc));
}

$res['rate_category']=$cat_obj->database->get_multiple_rows("SELECT * FROM ".CLIENT_CAT." WHERE CAT_STATUS='1'");

$res['client_detail']=$client_obj->database->get_one_row("SELECT * FROM ".CLIENT_CAT_RATE." WHERE CLIENT_RATE_ID='".$client_id."'");

echo $rslt->render_update_client_rate($res);

?>
</div>
<?php

require_once( "include/footer_inc.php" );
?>

<script type="text/javascript" language="javascript">
function delete_single_fsc_history(his_id,fsc)
{
	if(confirm("Do you really want to delete FSC History ?"))
	{
		$('#his_loader').show();
		$.ajax({
			url:'exp_save_rates.php?action=deletefschis&history_id='+his_id,
			success:function(res)
			{
				$('#his_loader').hide();
				window.location.href='exp_fsc_history.php?CODE='+fsc;
			}
		});
		return true;
	}
	else
	{
		return false;
	}
}
</script>

