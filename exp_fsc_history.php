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


$sts_subtitle = "FSC History";
require_once( "include/header_inc.php" );
require_once( "include/navbar_inc.php" );

?>
<div class="container-full" role="main">
<?php
require_once( "include/sts_result_class.php" );
require_once( "include/sts_shipment_class.php" );

$fsc_his=new sts_table($exspeedite_db , FSC_HISTORY , $sts_debug);

$shipment_table = sts_shipment::getInstance($exspeedite_db, $sts_debug);
$rslt = new sts_result( $shipment_table, false, $sts_debug );

$fsc_id=$_GET['CODE'];
if($fsc_id!="")
{
	$res['fsc_detail']	=$fsc_his->database->get_multiple_rows("
		SELECT H.FSC_HIS_ID, H.FSC_UNIQUE_ID, H.FSC_AV_PRICE, H.FSC_START_DATE, H.FSC_END_DATE, 
			H.FSC_MODIFIED_ON, H.ADDED_BY, H.FSC_NAME,
			S.FSC_COLUMN, S.FLAT_AMOUNT, S.PER_MILE_ADJUST, S.PERCENT, 
			(SELECT FULLNAME FROM EXP_USER
			WHERE USER_CODE=H.ADDED_BY) AS FULLNAME
		FROM EXP_FSC_HISTORY H, EXP_FSC_SCHEDULE S
		WHERE H.FSC_UNIQUE_ID='".$fsc_id."'
		AND H.FSC_AV_PRICE BETWEEN LOW_PER_GALLON AND HIGH_PER_GALLON
		AND H.FSC_UNIQUE_ID = S.FSC_UNIQUE_ID
		ORDER BY FSC_HIS_ID ASC");
	
}
$res['fsc_id']=$fsc_id;
echo $rslt->render_fsc_history($res);

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

