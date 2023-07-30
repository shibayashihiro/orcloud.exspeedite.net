<?php 
// Set flag that this is a parent file
define( '_STS_INCLUDE', 1 );

// Setup Session
require_once( "include/sts_session_setup.php" );
// We want to use TableTools with DataTables
define( '_STS_TABLETOOLS', 1 );

require_once( "include/sts_config.php" );
$sts_debug = isset($_GET['debug']) && in_group( EXT_GROUP_DEBUG );
require_once( "include/sts_session_class.php" );

$my_session = sts_session::getInstance( $exspeedite_db, $sts_debug );
$my_session->access_check( $sts_table_access[LOAD_TABLE] );	// Make sure we should be here

$sts_subtitle = "Loads";
require_once( "include/header_inc.php" );
require_once( "include/navbar_inc.php" );

?>
<div class="container-hundred" role="main">

<!--
<div class="alert alert-warning alert-dismissable">
  <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
  <strong>Warning!</strong> Working on changes to multiple picks/drops same address. Some issues remain.
</div>
-->

<?php
require_once( "include/sts_result_class.php" );
require_once( "include/sts_load_class.php" );
require_once( "include/sts_setting_class.php" );

$setting_table = sts_setting::getInstance($exspeedite_db, $sts_debug);
$sts_length_menu = $setting_table->get( 'option', 'LENGTH_MENU' );

$status = $exspeedite_db->get_multiple_rows("select BEHAVIOR, MIN(STATUS_CODES_CODE) CODE
			FROM EXP_STATUS_CODES
			WHERE SOURCE_TYPE = 'load' AND BEHAVIOR in ('cancel', 'complete', 'manifest', 'approved', 'billed')
			group by BEHAVIOR");

$codes = array();
foreach( $status as $row ) {
	$codes[] = $row['CODE'];
	switch($row['BEHAVIOR']) {
		case 'cancel':
			$cancel_code = $row['CODE'];
			break;

		case 'complete':
			$complete_code = $row['CODE'];
			break;

		case 'manifest':
			$manifest_code = $row['CODE'];
			break;

		case 'approved':
			$approved_code = $row['CODE'];
			break;

		case 'billed':
			$paid_code = $row['CODE'];
			break;

		default:
			break;
	}
}
assert( count($codes)."== 5", "Should have 5 codes" );

$load_table = new sts_load_left_join($exspeedite_db, $sts_debug);

$match = 'EXP_LOAD.CURRENT_STATUS NOT IN ('.implode(',', $codes).')';

if( ! isset($_SESSION['LOAD_MODE']) ) $_SESSION['LOAD_MODE'] = 'current';
if( isset($_POST['LOAD_MODE']) ) $_SESSION['LOAD_MODE'] = $_POST['LOAD_MODE'];
	
if( ! isset($_SESSION['LOAD_DATE']) ) $_SESSION['LOAD_DATE'] = 'all';
if( isset($_POST['LOAD_DATE']) ) {
	$_SESSION['LOAD_DATE'] = $_POST['LOAD_DATE'];
	if( $_POST['LOAD_DATE'] == 'all' )
		unset( $_SESSION['LOAD_START'], $_SESSION['LOAD_DURATION'] );
}
	
if( ! isset($_SESSION['LOAD_START']) ) $_SESSION['LOAD_START'] = date("m/d/Y");
if( isset($_POST['LOAD_START']) ) $_SESSION['LOAD_START'] = $_POST['LOAD_START'];
	
if( ! isset($_SESSION['LOAD_DURATION']) ) $_SESSION['LOAD_DURATION'] = '5';
if( isset($_POST['LOAD_DURATION']) ) $_SESSION['LOAD_DURATION'] = $_POST['LOAD_DURATION'];

if( ! isset($_SESSION['LOAD_ACTIVE']) ) $_SESSION['LOAD_ACTIVE'] = 'All';
if( isset($_POST['LOAD_ACTIVE']) ) $_SESSION['LOAD_ACTIVE'] = $_POST['LOAD_ACTIVE'];
	
$valid_actives = array(	'Active' => 'Active Stops',
						'Current' => 'Current Stop', 
						'All' => 'All Stops' );

$valid_modes = array(	'all' => 'All Loads',
						'current' => 'Current Loads (By next drop)', 
						'current2' => 'Current Loads (By Trip/Load#)', 
						'manifest' => 'Carrier Freight Agreement Sent',
						'complete' => 'Complete Loads - company',
						'complete2' => 'Complete Loads - carriers',
						'approved' => 'Approved Loads',
						'paid' => 'Paid Loads',
						'cancel' => 'Cancelled Loads');

$valid_dates = array(	'all' => 'All',
						'CREATED_DATE' => 'Created', 
						'DISPATCHED_DATE' => 'Dispatched',
						'COMPLETED_DATE' => 'Completed');

$valid_durations = array(	'5' => 'plus 5 days',
							'10' => 'plus 10 days', 
							'15' => 'plus 15 days',
							'30' => 'plus 30 days');

if( $_SESSION['LOAD_MODE'] == 'current' )
	$sts_result_loads_edit['sort'] = 'SORT_DROP_DUE(LOAD_CODE) ASC, LOAD_CODE ASC, EXP_STOP.SEQUENCE_NO ASC';
if( $_SESSION['LOAD_MODE'] == 'all' )
	$match = '1 = 1';
if( $_SESSION['LOAD_MODE'] == 'cancel' )
	$match = 'EXP_LOAD.CURRENT_STATUS = '.$cancel_code;
else if( $_SESSION['LOAD_MODE'] == 'complete' )
	$match = 'EXP_LOAD.CURRENT_STATUS = '.$complete_code.' AND COALESCE(CARRIER,0) = 0';
else if( $_SESSION['LOAD_MODE'] == 'complete2' )
	$match = 'EXP_LOAD.CURRENT_STATUS = '.$complete_code.' AND COALESCE(CARRIER,0) > 0';
else if( $_SESSION['LOAD_MODE'] == 'manifest' )
	$match = 'EXP_LOAD.CURRENT_STATUS = '.$manifest_code;
else if( $_SESSION['LOAD_MODE'] == 'approved' )
	$match = 'EXP_LOAD.CURRENT_STATUS = '.$approved_code;
else if( $_SESSION['LOAD_MODE'] == 'paid' )
	$match = 'EXP_LOAD.CURRENT_STATUS = '.$paid_code;

if( $_SESSION['LOAD_DATE'] <> 'all' ) {
	$start_date = date("Y-m-d", strtotime($_SESSION['LOAD_START']) ); // Convert to mysql format
	$match .= " AND EXP_LOAD.".$_SESSION['LOAD_DATE']." BETWEEN '".$start_date."'
		AND '".$start_date."' + INTERVAL ".$_SESSION['LOAD_DURATION']." DAY";
		
}

$filters_html = '<div class="form-group"><a class="btn btn-sm btn-success" href="exp_listload.php"><span class="glyphicon glyphicon-refresh"></span></a>';

$filters_html .= '<select class="form-control input-sm" name="LOAD_ACTIVE" id="LOAD_ACTIVE"   onchange="form.submit();">';
foreach( $valid_actives as $value => $label ) {
	$filters_html .= '<option value="'.$value.'" '.($_SESSION['LOAD_ACTIVE'] == $value ? 'selected' : '').'>'.$label.'</option>
	';
}
$filters_html .= '</select>';



$filters_html .= '<select class="form-control input-sm" name="LOAD_MODE" id="LOAD_MODE"   onchange="form.submit();">';
foreach( $valid_modes as $value => $label ) {
	$filters_html .= '<option value="'.$value.'" '.($_SESSION['LOAD_MODE'] == $value ? 'selected' : '').'>'.$label.'</option>
	';
}
$filters_html .= '</select>';

$filters_html .= '<select class="form-control input-sm" name="LOAD_DATE" id="LOAD_DATE"   onchange="form.submit();">';
foreach( $valid_dates as $value => $label ) {
	$filters_html .= '<option value="'.$value.'" '.($_SESSION['LOAD_DATE'] == $value ? 'selected' : '').'>'.$label.'</option>
	';
}
$filters_html .= '</select>';

if( $_SESSION['LOAD_DATE'] <> 'all' ) {
	$filters_html .= '
	<input type="date" class="form-control input-sm" name="LOAD_START" id="LOAD_START"
		value="'.$_SESSION['LOAD_START'].'" onchange="form.submit();">
	'; //<div class="col-sm-4"> </div>

	$filters_html .= '<select class="form-control" name="LOAD_DURATION" id="LOAD_DURATION"   onchange="form.submit();">';
	foreach( $valid_durations as $value => $label ) {
		$filters_html .= '<option value="'.$value.'" '.($_SESSION['LOAD_DURATION'] == $value ? 'selected' : '').'>'.$label.'</option>
		';
	}
	$filters_html .= '</select>';
}
if( $my_session->in_group(EXT_GROUP_FINANCE) && isset($_SESSION['LOAD_MODE']) &&
	$_SESSION['LOAD_MODE'] == 'approved' ) {
	$filters_html .= '<a class="btn btn-sm btn-danger" onclick="confirmation(\'Confirm: Resend ALL Approved Loads to QuickBooks?\n\nThis is helpful when QuickBooks was unavailable, and you want to clear the backlog.\n\nYou can examine the status by looking at each load.\', \'exp_qb_retry.php?TYPE=load&CODE=ALL\')"><span class="glyphicon glyphicon-ok"></span> Resend ALL to QB</a>';
}

$filters_html .= '</div>';

$sts_result_loads_edit['filters_html'] = $filters_html;

$rslt = new sts_result( $load_table, $match, $sts_debug );
echo $rslt->render( $sts_result_loads_lj_layout, $sts_result_loads_edit );

?>
</div>

	<div class="modal fade bs-example-modal-sm" tabindex="-1" role="dialog" aria-labelledby="mySmallModalLabel" aria-hidden="true" id="listload_modal">
	  <div class="modal-dialog">
		<div class="modal-content">
		<div class="modal-header">
			<h4 class="modal-title" id="myModalLabel"><span class="text-success"><strong>Updating...</strong></span></h4>
		</div>
		<div class="modal-body">
			<p class="text-center"><img src="images/loading.gif" alt="loading" width="125" height="125" /></p>
		</div>
		</div>
		</div>
	</div>

	<script language="JavaScript" type="text/javascript"><!--
		
		$(document).ready( function () {
			//alert(($(window).height() - 150) + "px");

			$('ul.dropdown-menu li a#modal').on('click', function(event) {
				event.preventDefault();
				$('#listload_modal').modal({
					container: 'body',
					remote: $(this).attr("href")
				}).modal('show');
				return false;					
			});

			
			$('#EXP_LOAD').dataTable({
		        //"bLengthChange": false,
		        "bFilter": true,
		        stateSave: true,
		        "bSort": false,
		        "bInfo": false,
				//"bAutoWidth": false,
				//"bProcessing": true, 
				"sScrollX": "100%",
				"sScrollY": ($(window).height() - 260) + "px",
				//"sScrollXInner": "120%",
		        "lengthMenu": [<?php echo isset($sts_length_menu) ? $sts_length_menu : '25, 50, 100, 250'; ?>],
				"bPaginate": true,
				//"bScrollCollapse": true,
				"bSortClasses": false,
				dom: 'T<"clear">lfrtip',
				"tableTools": {
		            "sSwfPath": "TableTools/swf/copy_csv_xls.swf",
		            "aButtons": [
		                {
		                    "sExtends":    "csv",
		                    "fnCellRender": function ( sValue, iColumn, nTr, iDataIndex ) {
		                        var output = sValue;
		                        if( output == "&nbsp;" )
		                        	output = "";
								
		                        if ( iColumn == 0 ) {
			                        var re = /View load (\d+)\</;
			                        var found = output.match(re);
			                        if(typeof(found) != "undefined" && found !== null) {
				                        output = found[1];
				                    } else {
					                    output = "";   
				                    }
		                        } else if ( iColumn == 1 ) {
			                        var str = '';
			                        var re = /\>(\d+)\</;
			                        var found = output.match(re);
			                        if(typeof(found) != "undefined" && found !== null) {
				                        str = found[1];
				                    }
			                        var re2 = /\>(Ps|Dc|Pd|Dd|S)\</;
			                        var found2 = output.match(re2);
			                        if(typeof(found2) != "undefined" && found2 !== null) {
				                        str = found2[1]+" "+str;
				                    }
				                    output = str;
		                        } else if ( iColumn == 2 ) {
			                        var re = /\>(.*)\</;
			                        var found = output.match(re);
			                        if(typeof(found) != "undefined" && found !== null) {
				                        output = found[1];
				                    }
		                        } else if ( iColumn == 3 ) {
			                        var re = /\>(\d+)\</;
			                        var found = output.match(re);
			                        if(typeof(found) != "undefined" && found !== null) {
				                        output = found[1];
				                    } else {
					                    output = "";   
				                    }
		                        } else if ( iColumn == 9 || iColumn == 10 ) {
			                        var re = /(.*)\<a href.*\<\/a\>/;
			                        var found = output.match(re);
			                        if(typeof(found) != "undefined" && found !== null) {
				                        output = found[1];
				                    } else {
					                    output = "";   
				                    }
		                        }
		                        
		                        if ( iColumn >= 4 ) {
			                        var re = /\<strong\>(.*)\<\/strong\>/;
			                        var found = output.match(re);
			                        if(typeof(found) != "undefined" && found !== null) {
				                        output = found[1];
				                    }
			                        var re2 = /\<span.*\>(.*)\<\/span\>/;
			                        var found2 = output.match(re2);
			                        if(typeof(found2) != "undefined" && found2 !== null) {
				                        output = found2[1];
				                    }
		                        }
			                    
			                    if ( iColumn == 12 ) {
			                        var re = /(\d+) \/ (\d+)/;
			                        var found = output.match(re);
			                        if(typeof(found) != "undefined" && found !== null) {
				                        output = found[1] + "h / " + found[2] + "b";
				                    }
		                        }
			                    output = output.replace("<br>", "\r");
			                    return output;
		                    }
		                }		            
		            ]
		        }
		
			});
			
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

require_once( "include/footer_inc.php" );
?>

