<?php 

// $Id: exp_addload.php 5030 2023-04-12 20:31:34Z duncan $
//! Select shipments for a load page
// If $_GET['CODE'] is set to an existing load code, edit that
// Else create a new load
// 
// https://developers.google.com/maps/documentation/maps-static/dev-guide

// Set flag that this is a parent file
define( '_STS_INCLUDE', 1 );

// Set flag that this is session readonly
define( '_STS_SESSION_READONLY', 1 );

// Setup Session
require_once( "include/sts_session_setup.php" );

require_once( "include/sts_config.php" );
$sts_debug = isset($_GET['debug']) && in_group( EXT_GROUP_DEBUG );
require_once( "include/sts_session_class.php" );

$my_session = sts_session::getInstance( $exspeedite_db, $sts_debug );
$my_session->access_check( $sts_table_access[SHIPMENT_TABLE] );	// Make sure we should be here

$sts_subtitle = "Select Shipments For Load";
require_once( "include/header_inc.php" );
require_once( "include/navbar_inc.php" );

?>
<div class="container" role="main">

<!--
<div class="alert alert-danger alert-dismissable">
  <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
  <h3><strong>Warning!</strong> Working on changes here. Do not use.</h3>
</div>
-->

<?php
require_once( "include/sts_result_class.php" );
require_once( "include/sts_shipment_class.php" );
require_once( "include/sts_load_class.php" );
require_once( "include/sts_office_class.php" );
require_once( "include/sts_setting_class.php" );

$setting_table = sts_setting::getInstance($exspeedite_db, $sts_debug);
$sts_multi_currency = $setting_table->get( 'option', 'MULTI_CURRENCY' ) == 'true';
$sts_maps_key = $setting_table->get( 'api', 'GOOGLE_API_KEY' );

$office_table = sts_office::getInstance($exspeedite_db, $sts_debug);

$multi_company = $office_table->multi_company();

$shipment_table = sts_shipment::getInstance($exspeedite_db, $sts_debug);
$load_table = sts_load::getInstance($exspeedite_db, $sts_debug);

if( isset($_GET['CODE']) && intval($_GET['CODE']) > 0 ) {
	$load = $_GET['CODE'];
} else {
	// Create new load
	$load = $load_table->create_empty();
}

//! SCR# 344 - Make sure if the load is already dispatched
$check = $load_table->fetch_rows("LOAD_CODE = ".$load, "CURRENT_STATUS" );
$ok_to_continue = true;
if( is_array($check) && count($check) == 1 && isset($check[0]["CURRENT_STATUS"])) {
	$load_status = $load_table->state_behavior[$check[0]["CURRENT_STATUS"]];
	if( in_array($load_status, array('complete', 'approved', 'billed', 'cancel'))) {
		echo '<div class="well well-md">
			<h1><span class="text-danger glyphicon glyphicon-warning-sign"></span> Load '.$load.' is '.$load_table->state_name[$check[0]["CURRENT_STATUS"]].'</h1>
			<h3>You can no longer add shipments to this load.</h3>
			
			<p>'.($load_status == 'cancel' ? '' : '<a class="btn btn-sm btn-default" href="exp_viewload.php?CODE='.$load.'"><span class="glyphicon glyphicon-eye-open"></span> View Load '.$load.'</a> ').'<a class="btn btn-sm btn-default" href="exp_listload.php"><span class="glyphicon glyphicon-arrow-up"></span> Back to Loads</a>
		</div>
	</div>
		';
		$ok_to_continue = false;
	} else if( in_array($load_status, array('dispatch', 'depart stop', 'depshdock',
		'depart shipper', 'deprecdock', 'depart cons', 'arrive stop', 'arrshdock',
		'arrive shipper', 'arrrecdock', 'arrive cons'))) {
		echo '<div class="alert alert-danger tighter tighter2">
			<h4 style="margin: 0;"><span class="text-danger glyphicon glyphicon-warning-sign"></span> Load '.$load.' is already dispatched!</h4>
			</div>
			';
	}
}

if( $ok_to_continue ) {
	$check = $exspeedite_db->get_one_row("
		SELECT COALESCE(
		(SELECT CTPAT_CERTIFIED FROM EXP_CARRIER WHERE CARRIER_CODE = CARRIER), FALSE) AS CTPAT_CERTIFIED
		FROM EXP_LOAD
		WHERE LOAD_CODE = $load
		AND CARRIER > 0");
	$no_ctpat = is_array($check) && isset($check["CTPAT_CERTIFIED"]) && $check["CTPAT_CERTIFIED"] == 0;
	
echo '<h2><img src="images/load_icon.png" alt="load_icon" height="24"> 
	Select Shipments For Load <span id="LOADCODE">'.$load.'</span> '
	.($no_ctpat ? ' <span class="label label-danger tip" title="Non-C-TPAT carrier already assigned"><s>C-TPAT</s></span> ' : '')
	.'<div class="btn-group">
		<a class="btn btn-sm btn-danger" onclick="confirmation(\'Confirm: Cancel Load\',
								\'exp_cancelload.php?CODE='.$load.'\')" ><span class="glyphicon glyphicon-remove"></span> Cancel Load</a>
		<a class="btn btn-sm btn-default" href="exp_listload.php"><span class="glyphicon glyphicon-arrow-up"></span> Back to Loads</a>
		<span id="CONTINUE"></span>
	</div>
	</h2>';
	
$assign_state = isset($shipment_table->behavior_state['assign']) ?
	$shipment_table->behavior_state['assign'] : 0;
assert( $assign_state > 0, "Unable to find assign status code" );
$docked_state = isset($shipment_table->behavior_state['docked']) ?
	$shipment_table->behavior_state['docked'] : 0;
assert( $docked_state > 0, "Unable to find docked status code" );

//! Fetch list of existing shipments for this load.
$current = $shipment_table->fetch_rows("LOAD_CODE = ".$load, 
		"SHIPMENT_CODE, 
		(CASE WHEN DOCKED_AT > 0 THEN (SELECT LOAD_CODE FROM EXP_STOP
			WHERE STOP_CODE = DOCKED_AT) ELSE NULL END ) AS PREV_LOAD,
		(CASE WHEN CURRENT_STATUS = ".$docked_state." THEN 
			(SELECT STOP_NAME FROM EXP_STOP
			WHERE STOP_CODE = DOCKED_AT) 
		ELSE SHIPPER_NAME END ) AS SHIPPER_NAME,
		(CASE WHEN CURRENT_STATUS = ".$docked_state." THEN 
			(SELECT STOP_CITY FROM EXP_STOP
			WHERE STOP_CODE = DOCKED_AT) 
		ELSE SHIPPER_CITY END ) AS SHIPPER_CITY,
		(CASE WHEN CURRENT_STATUS = ".$docked_state." THEN 
			(SELECT STOP_STATE FROM EXP_STOP
			WHERE STOP_CODE = DOCKED_AT) 
		ELSE SHIPPER_STATE END ) AS SHIPPER_STATE,
		(CASE WHEN CURRENT_STATUS = ".$docked_state." THEN 
			(SELECT STOP_ZIP FROM EXP_STOP
			WHERE STOP_CODE = DOCKED_AT) 
		ELSE SHIPPER_ZIP END ) AS SHIPPER_ZIP,
		SHIPMENT_CTPAT(SHIPMENT_CODE) AS CTPAT_REQUIRED, ST_NUMBER,
		(SELECT TOTAL FROM EXP_CLIENT_BILLING
		WHERE SHIPMENT_ID = SHIPMENT_CODE) AS TOTAL,
		(SELECT CURRENCY FROM EXP_CLIENT_BILLING
		WHERE SHIPMENT_ID = SHIPMENT_CODE) AS CURRENCY,
		CONS_NAME, CONS_CITY, CONS_STATE, CONS_ZIP, CONS_TERMINAL, PALLETS, WEIGHT,
		PICKUP_DATE, PICKUP_TIME_OPTION, PICKUP_TIME1, PICKUP_TIME2,
		DELIVER_DATE, DELIVER_TIME_OPTION, DELIVER_TIME1, DELIVER_TIME2,
		CURRENT_STATUS, SS_NUMBER, PO_NUMBER, PO_NUMBER2, PO_NUMBER3, PO_NUMBER4, PO_NUMBER5, LOAD_CODE,
		BILLTO_NAME", "PICKUP_DATE ASC, DELIVER_DATE ASC, CONS_NAME ASC");

/*
	"SHIPMENT_CODE, SHIPPER_NAME, SHIPPER_CITY, SHIPPER_STATE, SHIPPER_ZIP, SHIPPER_TERMINAL,
	CONS_NAME, CONS_CITY, CONS_STATE, CONS_ZIP, CONS_TERMINAL, PALLETS, WEIGHT,
	PICKUP_DATE, PICKUP_TIME_OPTION, PICKUP_TIME1, PICKUP_TIME2,
	DELIVER_DATE, DELIVER_TIME_OPTION, DELIVER_TIME1, DELIVER_TIME2,
	CURRENT_STATUS, PO_NUMBER, PO_NUMBER2, PO_NUMBER3, PO_NUMBER4, PO_NUMBER5, BILLTO_NAME");
*/

echo '<div class="sideBySide">
      <div class="left">
      <h4>Available Shipments:<br>
	      <div class="form-group">
	  		<div class="col-sm-4">
		    	<input class="form-control input-sm" name="SHPPER_ZONE" id="SHPPER_ZONE" type="text"  
					placeholder="Shipper Zone" maxlength="20" >
			</div>
	  		<div class="col-sm-4">
		    	<input class="form-control input-sm" name="CONS_ZONE" id="CONS_ZONE" type="text"  
					placeholder="Cons Zone" maxlength="20" >
			</div>
	  		<div class="col-sm-4">
	  			<span class="btn btn-sm btn-default" id="RELOAD"><span class="glyphicon glyphicon-refresh"></span></span>
	  			<span class="btn btn-sm btn-default" id="CLEAR"><span class="glyphicon glyphicon-remove"></span></span>
			</div>
	      </div><!-- form-group -->
  	      <div class="form-group">
  		  	<div class="col-sm-12">
	  		  	<div class="btn-group">
				    <a id="SORT_CODE" class="btn btn-xs btn-info"># <span class="glyphicon glyphicon-arrow-up"></span></a>
				      <a id="SORT_PICKUP" class="btn btn-xs btn-info">Pickup <span class="glyphicon glyphicon-arrow-up"></span></a>
				      <a id="SORT_DELIVER" class="btn btn-xs btn-info">Delivery <span class="glyphicon glyphicon-arrow-up"></span></a>
				      <a id="SORT_SHIPPER" class="btn btn-xs btn-info">Ship <span class="glyphicon glyphicon-arrow-up"></span></a>
				      <a id="SORT_CONS" class="btn btn-xs btn-info">Cons <span class="glyphicon glyphicon-arrow-up"></span></a>
				      <a id="SORT_PALLETS" class="btn btn-xs btn-info">Pall <span class="glyphicon glyphicon-arrow-down"></span></a>
				      <a id="SORT_WEIGHT" class="btn btn-xs btn-info">Wt <span class="glyphicon glyphicon-arrow-down"></span></a>
			      </div>
	 		</div><!-- col-sm-12 -->
	      </div><!-- form-group -->
	    </div><!-- left -->
      <div class="right">
	      <br>
	      <h4>Load: &nbsp;
			 <span id="TOT_PALLETS"></span> &nbsp;
			 <span id="TOT_WEIGHT"></span><br>
			 <span id="TOT_SHIPMENTS"></span> &nbsp;
			<span id="SELECTED_SHIPMENTS"></span> &nbsp;
	      </h4>
      </div><!-- right -->
      </div>
      <div class="sideBySide">
      <div class="left">
      <div class="pane-scroll">
        <ul class="source connected">
		</ul>
      </div><!-- pane-scroll -->
      </div><!-- left -->
      <div class="right">
      <div class="pane-scroll">
       <ul class="target connected">
        ';

if( isset($current) && is_array($current) && count($current) > 0 ) {
	if( $sts_debug ) {
		echo "<p>addload current = </p>
		<pre>";
		var_dump($current);
		echo "</pre>";
	}
	foreach( $current as $row) {
		$fixed = in_array($shipment_table->state_behavior[$row['CURRENT_STATUS']],
			array('picked', 'dropped') );
		echo '<li '.($fixed ? 'class="fixed" ':'').'data-shipment="'.$row['SHIPMENT_CODE'].'" data-shipper="'.$row['SHIPPER_NAME'].'" 
			data-consignee="'.$row['CONS_NAME'].'"
			data-billto="'.$row['BILLTO_NAME'].'"
			data-pallets="'.$row['PALLETS'].'"
			data-weight="'.$row['WEIGHT'].'" 
			data-pickup="'.$row['PICKUP_DATE'].'" 
			data-deliver="'.$row['DELIVER_DATE'].'" 
			data-shipper-city="'.$row['SHIPPER_CITY'].'"
			data-shipper-state="'.$row['SHIPPER_STATE'].'" 
			data-cons-city="'.$row['CONS_CITY'].'" 
			data-cons-state="'.$row['CONS_STATE'].'"><strong><a href="exp_addshipment.php?CODE='.$row['SHIPMENT_CODE'].'" target="_blank" title="edit shipment '.$row['SHIPMENT_CODE'].' in new window">#'.$row['SHIPMENT_CODE'].'</a>'.
				($multi_company && isset($row['SS_NUMBER']) && $row['SS_NUMBER'] <> '' ? ' ('.$row['SS_NUMBER'].')' : '')
			.'</strong> '.$row['SHIPPER_NAME'].($row['CURRENT_STATUS'] == $docked_state ? ' <span class="badge" title="Docked from load #'.$row['PREV_LOAD'].'">D</span>' : '').' -> '.$row['CONS_NAME'].'<br>';

			$po_numbers = array('PO_NUMBER', 'PO_NUMBER2', 'PO_NUMBER3', 'PO_NUMBER4', 'PO_NUMBER5');
			$active_pos = array();
			foreach( $po_numbers as $po ) {
				if( isset($row[$po]) && $row[$po] <> '' )
					$active_pos[] = $row[$po];
			}
			if( count($active_pos) > 0 )
				echo 'PO#s: '.implode(', ', $active_pos).'<br>';

			echo $row['SHIPPER_CITY'].', '.$row['SHIPPER_STATE'].', '.$row['SHIPPER_ZIP'].' -> '.$row['CONS_CITY'].', '.$row['CONS_STATE'].', '.$row['CONS_ZIP'].'<br>'.
			sts_result::duedate( $row['PICKUP_DATE'], $row['PICKUP_TIME_OPTION'], $row['PICKUP_TIME1'], $row['PICKUP_TIME2'], ' ' )
			.' -> '.
			sts_result::duedate( $row['DELIVER_DATE'], $row['DELIVER_TIME_OPTION'], $row['DELIVER_TIME1'], $row['DELIVER_TIME2'], ' ' ).
			($fixed ? ' ('.$shipment_table->state_name[$row['CURRENT_STATUS']].')' : '').
			'<br>'.
			(empty($row['ST_NUMBER']) ? '' : '<strong>Container: '.$row['ST_NUMBER'].'</strong><br>').
			'<strong>'.$row['PALLETS'].' pallets</strong>, <strong>'.$row['WEIGHT'].' lb'.
			(empty($row['TOTAL']) ? '' : ', $'.number_format($row['TOTAL'],2).($sts_multi_currency ? ' '.$row['CURRENCY'] : ''))
			.($row['CTPAT_REQUIRED'] ? ' <span class="badge" title="Customs-Trade Partnership Against Terrorism">C-TPAT</span>' : '')
			.'</strong></li>';
	}
}

echo '        </ul>
        
      </div>
      </div>
    </div>
';

echo '<br>
	<div class="row">
	<a class="btn btn-sm btn-default" href="exp_listshipment.php?SHOW_STATUS_EXP_SHIPMENT=entry" ><span class="glyphicon glyphicon-search"></span> Peek at shipments not ready</a>
	</div>
	<div class="row">
		<div id="MAP"></div>
	</div>
';	

?>

</div>

	<script language="JavaScript" type="text/javascript"><!--



jQuery.fn.sortElements = (function(){
 
    var sort = [].sort;
 
    return function(comparator, getSortable) {
 
        getSortable = getSortable || function(){return this;};
 
        var placements = this.map(function(){
 
            var sortElement = getSortable.call(this),
                parentNode = sortElement.parentNode,
 
                // Since the element itself will change position, we have
                // to have some way of storing its original position in
                // the DOM. The easiest way is to have a 'flag' node:
                nextSibling = parentNode.insertBefore(
                    document.createTextNode(''),
                    sortElement.nextSibling
                );
 
            return function() {
 
                if (parentNode === this) {
                    throw new Error(
                        "You can't sort elements if any one is a descendant of another."
                    );
                }
 
                // Insert before flag:
                parentNode.insertBefore(this, nextSibling);
                // Remove flag:
                parentNode.removeChild(nextSibling);
 
            };
 
        });
 
        return sort.call(this, comparator).each(function(i){
            placements[i].call(getSortable.call(this));
        });
 
    };
 
})();

		$(document).ready( function () {
			
			var load = <?php echo $load; ?>;
			var key = '<?php echo $sts_maps_key; ?>';
			var SHIPMENTS_HAS_CHANGED = false;
			var target_orig = '';
			$("ul.target").children().each(function() {
				target_orig += $(this).data("shipment") + ' ';
			});

			
			//! Load/Reload the left column with a list of shipments
			function reload_source() {
				$("ul.source").html('<div id="loading"><p class="text-center"><img src="images/loading.gif" alt="loading" width="100" height="100" /></p></div>');
				var url = 'exp_filtershipment.php?pw=Cheerios&load='+load;
				if( $('#SHPPER_ZONE').val() != '' )
					url += '&shipper_zone=' + $('#SHPPER_ZONE').val();
				if( $('#CONS_ZONE').val() != '' )
					url += '&cons_zone=' + $('#CONS_ZONE').val();
				
				$("ul.source").load(url, function() {
					// Have to remove any that match the right column.
					var shipments = new Array();
					$("ul.target").children().each(function() {
						shipments.push(parseInt($(this).data("shipment")));
					});
					$("ul.source").children().each(function() {
						var shipment = parseInt($(this).data("shipment"));
						//console.log("test against = ", shipment );
						if( jQuery.inArray(shipment, shipments) > -1 ) {
							//console.log("matched = ",shipment );
							$(this).addClass('remove');
						}
					});
					$("ul.source li.remove").remove();
				});
				
			}
			
			$(function () {
				$(".source, .target").sortable({
					connectWith: ".connected",
					cursor: "move",
					cancel: ".fixed",
				}).on("sortupdate", function( event, ui ) {
					var target_match = '';

					//! Ajax calls to add or remove shipment from load
					//ui.item.data('shipment') = shipment number
					//ui.sender.hasClass( "source" ) true = add, false = delete
					//! SCR# 607 - show diagnostic if unable to add to a load
					if( ui !== undefined && ui.sender !== null ) {
						if( ui.sender.hasClass( "source" ) ) {
							$.ajax({
								url: 'exp_adddel_shipment_load.php',
								data: {
									load: load,
									add: ui.item.data('shipment'),
									pw: 'EggsToast'
								},
								dataType: "json",
								success: function(data) {
									//console.log('Add shipment, return ', data);
									if( ! data ) {
										alert('Could not add shipment# ' + ui.item.data('shipment') + ' to load# '+load+'.'+"\nIt probably was already added to another load.");
										location.reload();
									}
								}
							});
						} else {
							$.ajax({
								url: 'exp_adddel_shipment_load.php',
								data: {
									load: load,
									del: ui.item.data('shipment'),
									pw: 'EggsToast'
								},
								dataType: "json",
								success: function(data) {
									//console.log('Delete shipment, return ', data);
								}
							});
						}
						
					}
					
					$("ul.target").children().sortElements(function(a,b){
						var an = $(a).data('shipment');
						var bn = $(b).data('shipment');
						
						return an > bn ? 1 : -1;
					});
					var tot_shipments = $("ul.target").children().length;
					var tot_pallets = 0;
					var tot_weight = 0;
					var shipments = new Array();
					var locations = '';
					$("ul.target").children().each(function() {
						target_match += $(this).data("shipment") + ' ';
						tot_pallets += $(this).data("pallets");
						tot_weight += $(this).data("weight");
						shipments.push($(this).data("shipment").toString());
						locations += '&path=weight:5|' + encodeURIComponent($(this).data("shipper-city")) +
							 ',' + encodeURIComponent($(this).data("shipper-state")) +
							 '|' + encodeURIComponent($(this).data("cons-city")) +
							 ',' + encodeURIComponent($(this).data("cons-state")) ;
					});
					if( target_orig != target_match ) {
						//console.log('Changed ' + target_orig + '<> ' + target_match);
						SHIPMENTS_HAS_CHANGED = true;
					}
					$("#SELECTED_SHIPMENTS").text(shipments.join());
					$("#TOT_SHIPMENTS").text(tot_shipments + " Shipments:");
					$("#TOT_PALLETS").text("Pallets: " + tot_pallets);
					$("#TOT_WEIGHT").text("Weight: " + tot_weight + " lb");
					if( tot_shipments > 0 && parseInt($("#LOADCODE").text()) > 0 ) {
						$("#CONTINUE").replaceWith('<a id="CONTINUE" class="btn btn-sm btn-success" href="exp_dispatch2.php?CODE=' 
							+ $("#LOADCODE").text() + '" ><span class="glyphicon glyphicon-arrow-right"></span> Continue</a>');
							// removed  + '&SHIPMENTS=' + shipments.join()
						$('#MAP').replaceWith('<div class="text-right" id="MAP"><img src="https://maps.googleapis.com/maps/api/staticmap?key=' + key + '&sensor=false&size=640x400' + locations + '"></div>');
					} else if( parseInt($("#LOADCODE").text()) > 0 ) {
						$("#CONTINUE").replaceWith('<a id="CONTINUE" class="btn btn-sm btn-success" href="exp_dispatch2.php?CODE=' 
							+ $("#LOADCODE").text() + '&SHIPMENTS=" ><span class="glyphicon glyphicon-arrow-right"></span> Empty Load</a>');
					} else {
						$("#CONTINUE").replaceWith('<span id="CONTINUE"></span>');
					}

				}).trigger( "sortupdate" );
				
				$("#SORT_CODE").click(function() {
					$("ul.source").children().sortElements(function(a,b){
						var an = $(a).data('shipment');
						var bn = $(b).data('shipment');
						
						return an > bn ? 1 : -1;
					});

				});
				
				$("#SORT_PICKUP").click(function() {
					$("ul.source").children().sortElements(function(a,b){
						var an = $(a).data('pickup');
						var bn = $(b).data('pickup');
						
						return an > bn ? 1 : -1;
					});

				});
				
				$("#SORT_DELIVER").click(function() {
					$("ul.source").children().sortElements(function(a,b){
						var an = $(a).data('deliver');
						var bn = $(b).data('deliver');
						
						return an > bn ? 1 : -1;
					});

				});
				
				$("#SORT_SHIPPER").click(function() {
					$("ul.source").children().sortElements(function(a,b){
						var an = $(a).data('shipper');
						var bn = $(b).data('shipper');
						
						return an > bn ? 1 : -1;
					});

				});
				
				$("#SORT_CONS").click(function() {
					$("ul.source").children().sortElements(function(a,b){
						var an = $(a).data('consignee');
						var bn = $(b).data('consignee');
						
						return an > bn ? 1 : -1;
					});

				});
				
				$("#SORT_PALLETS").click(function() {
					$("ul.source").children().sortElements(function(a,b){
						var an = $(a).data('pallets');
						var bn = $(b).data('pallets');
						return an < bn ? 1 : -1;
					});

				});
				
				$("#SORT_WEIGHT").click(function() {
					$("ul.source").children().sortElements(function(a,b){
						var an = $(a).data('weight');
						var bn = $(b).data('weight');
						return an < bn ? 1 : -1;
					});

				});

				$('a:not(#CONTINUE)').on('click', function() {
					if( window.SHIPMENTS_HAS_CHANGED ) {
						var answer = confirm('You have unsaved changes that will be lost. Continue?');
						console.log(answer);
						if (answer){
							return true;
						} else {
							return false;
						}
					}
				});
				
				$(".target").on("contentChanged", function() {
						//var stocks = [];
						alert('changed');
						//$("ul.target").children().each(function() {
						//});
				});
			});
			
			$('#RELOAD').on('click', function() {
				//if( $('#SHPPER_ZONE').val() != '' || $('#CONS_ZONE').val() != '' )
					reload_source();
			});

			$('#CLEAR').on('click', function() {
				$('#SHPPER_ZONE').val('');
				$('#CONS_ZONE').val('');
			});
			
			reload_source();
			
			// Adjust the height of the scrolling panes based on the size of the screen
			$(".pane-scroll").css("height", ($(window).height() - 300) + "px");

			if( window.HANDLE_RESIZE_EVENTS ) {
				$(window).bind('resize', function(e) {
					console.log('resize event triggered');
					if (window.RT) clearTimeout(window.RT);
					window.RT = setTimeout(function() {
						this.location.reload(false); /* false to get page from cache */
					}, 100);
				});
			}
		
		});
	//--></script>

<?php
}

require_once( "include/footer_inc.php" );
?>
