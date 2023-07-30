<?php 
// Set flag that this is a parent file
define( '_STS_INCLUDE', 1 );

// Setup Session
require_once( "include/sts_session_setup.php" );

require_once( "include/sts_config.php" );
$sts_debug = isset($_GET['debug']) && in_group( EXT_GROUP_DEBUG );
require_once( "include/sts_session_class.php" );

$my_session = sts_session::getInstance( $exspeedite_db, $sts_debug );
$my_session->access_check( EXT_GROUP_ADMIN );	// Make sure we should be here

$sts_subtitle = "List Zone Filters";
require_once( "include/header_inc.php" );
require_once( "include/navbar_inc.php" );

?>
<div class="container-full" role="main">
<?php
require_once( "include/sts_result_class.php" );
require_once( "include/sts_zone_filter_class.php" );

$zone_filter_table = new sts_zone_filter($exspeedite_db, $sts_debug);

$result = $zone_filter_table->fetch_rows("","ZF_NAME, ZF_TYPE, ZF_VALUE", "ZF_NAME ASC, ZF_TYPE ASC" );
?>
		<h3><img src="images/zone_icon.png" alt="zone_icon" height="24"> Zone Filters
		<div class="btn-group">
		<a class="btn btn-sm btn-success" href="exp_addzone_filter.php" ><span class="glyphicon glyphicon-plus"></span> Add Zone Filter</a>
		<a class="btn btn-sm btn-default" href="index.php"><span class="glyphicon glyphicon-remove"></span> Back</a>
		</div>
		</h3>
		<div class="table-responsive">
		<table class="table table-striped table-condensed table-bordered table-hover" >
		<thead>
		<tr class="exspeedite-bg"><th></th><th>Name</th><th>Value(s)</th></tr>
		</thead>
		<tbody>
<?php
		if( $result && count($result) > 0 ) {
			$values = array();
			foreach( $result as $row ) {
				if( isset($values[$row['ZF_NAME']]) )
					$values[$row['ZF_NAME']] .= ', '.$row['ZF_TYPE'].' = '.$row['ZF_VALUE'];
				else
					$values[$row['ZF_NAME']] = $row['ZF_TYPE'].' = '.$row['ZF_VALUE'];
			}
			foreach( $values as $name => $value_str ) {
				echo '<tr><td class="text-center">
					<div style="width: 56px;">
					<div class="btn-group btn-group-xs"><a href="exp_addzone_filter.php?CODE='.$name.'"class="btn btn-default btn-xs inform" id="'.$name.'1" data-placement="bottom" data-toggle="popover" data-content="Edit Zone Filter '.$name.'"><span style="font-size: 14px;"><span class="glyphicon glyphicon-edit"></span></span></a>
					<a class="btn btn-default btn-xs confirm" id="'.$name.'2" data-placement="bottom" data-toggle="popover" data-content="<a class=&quot;btn btn-danger btn-sm&quot; id=&quot;'.$name.'2_confirm&quot; href=&quot;exp_deletezone_filter.php?code=Windoze8Sux&zname='.$name.'&list=on&quot;><span class=&quot;glyphicon glyphicon-trash&quot;></span> Delete Zone Filter '.$name.'</a><br>There is no undo"><span style="font-size: 14px;"><span class="glyphicon glyphicon-trash"></span></span></a>
					</div>
					</div>
					</td><td>'.$name.'</td><td>'.$value_str.'</td><tr>';
			}
		}
?>
				</tbody>
		</table>
		</div>

</div>
<?php

require_once( "include/footer_inc.php" );
?>

