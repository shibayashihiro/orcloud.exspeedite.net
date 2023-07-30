<?php 

// $Id: exp_listload_driver.php 4350 2021-03-02 19:14:52Z duncan $
//! Driver Home page

// Set flag that this is a parent file
define( '_STS_INCLUDE', 1 );

define( '_STS_GEO', 1 );

// Setup Session
require_once( "include/sts_session_setup.php" );
// We want to use TableTools with DataTables
define( '_STS_TABLETOOLS', 1 );

require_once( "include/sts_config.php" );
$sts_debug = isset($_GET['debug']) && in_group( EXT_GROUP_DEBUG );
require_once( "include/sts_session_class.php" );

$my_session = sts_session::getInstance( $exspeedite_db, $sts_debug );
$my_session->access_check( EXT_GROUP_DRIVER );	// Make sure we should be here

$sts_subtitle = "Loads";
require_once( "include/header_inc.php" );
require_once( "include/navbar_inc.php" );

?>
<div class="container-hundred" role="main">

<?php
require_once( "include/sts_result_class.php" );
require_once( "include/sts_load_class.php" );
require_once( "include/sts_setting_class.php" );
require_once( "include/sts_office_class.php" );

$office_table = sts_office::getInstance($exspeedite_db, $sts_debug);

$setting_table = sts_setting::getInstance($exspeedite_db, $sts_debug);
$sts_length_menu = $setting_table->get( 'option', 'LENGTH_MENU' );
$sts_refresh_rate = $setting_table->get( 'option', 'LOAD_SCREEN_REFRESH_RATE' );
$sts_po_fields = $setting_table->get( 'option', 'PO_FIELDS' ) == 'true';
$sts_refnum_fields = $setting_table->get( 'option', 'REFNUM_FIELDS' ) == 'true';
$multi_company = $office_table->multi_company();
$sts_export_qb = $setting_table->get( 'api', 'EXPORT_QUICKBOOKS' ) == 'true';
$sts_export_sage50 = $setting_table->get( 'api', 'EXPORT_SAGE50_CSV' ) == 'true';

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
assert( count($codes) == 5, "Should have 5 codes" );

$check = $exspeedite_db->get_one_row("
	SELECT DRIVER,
	CONCAT_WS( ' ', FIRST_NAME , LAST_NAME ) AS DNAME
	FROM EXP_USER, EXP_DRIVER
	WHERE DRIVER = DRIVER_CODE
	AND USER_CODE = ".$_SESSION['EXT_USER_CODE']);
//echo "<pre>";
//var_dump($_SESSION['EXT_USER_CODE'], $check);
//echo "</pre>";

if( count($check) == 2 && ! empty($check["DRIVER"]) ) {

	$driver = $check["DRIVER"];
	$dname = $check["DNAME"];
	
	$sts_result_loads_driver_edit['title'] = str_replace('My Trips/Loads', $dname,
		$sts_result_loads_driver_edit['title']);
	
	$load_table = new sts_load_left_join($exspeedite_db, $sts_debug);
	
	$match = 'EXP_LOAD.CURRENT_STATUS NOT IN ('.implode(',', $codes).')';
	
	if( ! isset($_SESSION['LOAD_MODE']) ) $_SESSION['LOAD_MODE'] = 'current';
	if( isset($_POST['LOAD_MODE']) ) $_SESSION['LOAD_MODE'] = $_POST['LOAD_MODE'];
	
	$valid_modes = array(	'all' => 'All Loads',
							'current' => 'Current Loads (By next drop)', 
							'current3' => 'Current Loads (By next pick or drop)', 
							'current2' => 'Current Loads (By Trip/Load#)', 
							'complete' => 'Complete Loads - company');
	
	if( $_SESSION['LOAD_MODE'] == 'current' )
		$sts_result_loads_driver_edit['sort'] = 'SORT_DROP_DUE(LOAD_CODE) ASC, LOAD_CODE ASC, SEQUENCE_NO ASC';
	else if( $_SESSION['LOAD_MODE'] == 'current3' )
		$sts_result_loads_driver_edit['sort'] = 'SORT_PICKDROP_DUE(LOAD_CODE) ASC, LOAD_CODE ASC, SEQUENCE_NO ASC';
		
	if( $_SESSION['LOAD_MODE'] == 'all' )
		$match = '1 = 1';
	else if( $_SESSION['LOAD_MODE'] == 'complete' ) {
		$match = 'EXP_LOAD.CURRENT_STATUS = '.$complete_code.' AND COALESCE(CARRIER,0) = 0';
		$sts_result_loads_driver_edit['sort'] = 'LOAD_CODE DESC, SEQUENCE_NO ASC';
	} else if( $_SESSION['LOAD_MODE'] == 'complete2' ) {
		$match = 'EXP_LOAD.CURRENT_STATUS = '.$complete_code.' AND COALESCE(CARRIER,0) > 0';
		$sts_result_loads_driver_edit['sort'] = 'LOAD_CODE DESC, SEQUENCE_NO ASC';
	}
	
	$match = ($match <> '' ? $match." AND " : "")."DRIVER = ".$driver;
	
	$filters_html = '<div class="form-group"><a class="btn btn-sm btn-success" href="exp_listload_driver.php"><span class="glyphicon glyphicon-refresh"></span></a>';
	
	
	$filters_html .= '<select class="form-control input-sm" name="LOAD_MODE" id="LOAD_MODE"   onchange="$(\'#EXP_LOAD\').DataTable().state.clear(); form.submit();">';
	foreach( $valid_modes as $value => $label ) {
		$filters_html .= '<option value="'.$value.'" '.($_SESSION['LOAD_MODE'] == $value ? 'selected' : '').'>'.$label.'</option>
		';
	}
	$filters_html .= '</select>';
	
	$filters_html .= '</div>';
	
	$sts_result_loads_driver_edit['filters_html'] = $filters_html;
	
	if( ! $sts_po_fields ) {
		unset($sts_result_loads_driver_layout['PO_NUMBER']);
	}
	if( ! $sts_refnum_fields ) {
		//! SCR# 658 - changed to REF_NUMBER2 for C-TPAT
		unset($sts_result_loads_driver_layout['REF_NUMBER2']);
	}
	if( ! $multi_company ) {
		unset($sts_result_loads_driver_layout['SS_NUMBER2']);
	}
	
	$rslt = new sts_result( $load_table, $match, $sts_debug );
	echo $rslt->render( $sts_result_loads_driver_layout, $sts_result_loads_driver_edit, false, false );
} else {
	echo '<div class="container-full theme-showcase" role="main">
	<h2><span class="text-warning glyphicon glyphicon-warning-sign"></span> Error: Driver user not set up correctly</span></h2>
	<p>Please contact your admin to fix this. They need to link the user account to a driver.</p>
	</div>';
}

?>
</div>

	<div class="modal fade bs-example-modal-sm" tabindex="-1" role="dialog" aria-labelledby="mySmallModalLabel" aria-hidden="true" id="listload_modal">
	  <div class="modal-dialog modal-lg">
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

<style>
.modal .modal-content{
overflow:hidden;
}
.modal-body{
max-height: 60vh;
overflow-y:scroll; // to get scrollbar only for y axis
}
</style>

	<div class="modal fade fuzzy bs-example-modal-sm" tabindex="-1" role="dialog" aria-labelledby="mySmallModalLabel" aria-hidden="true" id="motd_modal">
	  <div class="modal-dialog">
		<div class="modal-content">
			<div class="modal-header" id="motd_modal_header">
				<h2 class="modal-title" id="myModalLabel"><span class="text-success"><img src="images/EXSPEEDITEsmr.png" alt="EXSPEEDITEsmr" width="283" height="50" /> Messages</h2>
			</div>
			<div class="modal-body" id="motd_modal_body">
				<p class="text-center"><img src="images/loading.gif" alt="loading" width="125" height="125" /></p>
			</div>
			<div class="modal-footer" id="motd_modal_footer">
			</div>
		</div>
		</div>
	</div>

	<script language="JavaScript" type="text/javascript"><!--
		
		$(document).ready( function () {
			//alert(($(window).height() - 150) + "px");
		<?php if( ! $sts_debug ) { ?>
			document.documentElement.style.overflow = 'hidden';  // firefox, chrome
			document.body.scroll = "no"; // ie only
		<?php } ?>
			var driver = <?php echo $driver; ?>;
			var code = <?php echo (isset($_SESSION['EXT_USER_CODE']) ? $_SESSION['EXT_USER_CODE'] : 0); ?>;
			
// Only do this once per session
<?php if( ! isset($_SESSION["DRIVER_CHECKIN"])) { ?>
			if(geo_position_js.init()){
				geo_position_js.getCurrentPosition(success_callback,error_callback,{enableHighAccuracy:true});
			}
			else {
				//alert("Functionality not available");
			}
	
			function success_callback(p) {
				//alert('lat='+p.coords.latitude.toFixed(6)+';lon='+p.coords.longitude.toFixed(6));
				$.ajax({
					//async: false,
					url: 'exp_driver_checkin.php',
					data: {
						PW: 'Mikos',
						DRIVER: driver,
						LAT: p.coords.latitude.toFixed(6),
						LON: p.coords.longitude.toFixed(6)
					}
				});
			}
			
			function error_callback(p) {
				//alert('error='+p.message);
			}		
<?php }
	$_SESSION["DRIVER_CHECKIN"] = 1; ?>
			var opts = {
		        //"bLengthChange": false,
		        "bFilter": true,
		        stateSave: true,
		        "bSort": false,
		        "bInfo": true,
				//"bAutoWidth": false,
				//"bProcessing": true, 
				"sScrollX": "100%",
				"sScrollY": ($(window).height() - 260) + "px",
				//"sScrollXInner": "120%",
		        "lengthMenu": [<?php echo isset($sts_length_menu) ? $sts_length_menu : '25, 50, 100, 250'; ?>],
		        <?php if( isset($_POST["EXP_LOAD_length"]) )
			        echo '"pageLength": '.$_POST["EXP_LOAD_length"].','; ?>
				"bPaginate": true,
				"bScrollCollapse": false,
				"bSortClasses": false,
				"processing": true,
				"serverSide": true,
				//"dom": "frtiS",
				"deferRender": true,
				"ajax": {
					"url": "exp_listloadajax.php",
					"data": function( d ) {
						d.match = encodeURIComponent("<?php echo $match; ?>");
						d.sort = encodeURIComponent("<?php echo $sts_result_loads_driver_edit['sort']; ?>");
						d.rtype = 'driver';
					}

				},
				"columns": [
					//{ "searchable": false, "orderable": false },
					<?php
						foreach( $sts_result_loads_driver_layout as $key => $row ) {
							if( $row["format"] <> 'hidden')
								echo '{ "data": "'.$key.'", "searchable": '.
								(isset($row["searchable"]) && $row["searchable"] ? 'true' : 'false').', "orderable": false'.
								(isset($row["align"]) ? ',"className": "text-'.$row["align"].'"' : '').
									(isset($row["length"]) ? ', "width": "'.$row["length"].'px"' : '').
									(isset($row["format"]) && $row["format"] == 'hidden' ? ', "visible": false' : '').' },
						';
						}
					?>
				],
				"rowCallback": function( row, data ) {
					var load_status = $(row).attr('load_status');
					if( typeof(load_status) !== 'undefined' && $(row).find("td:first").html() != '' ) {
						switch(parseInt(load_status)) {
							case <?php echo $load_table->behavior_state['arrive shipper']; ?>:
							case <?php echo $load_table->behavior_state['depart shipper']; ?>:
							case <?php echo $load_table->behavior_state['arrshdock']; ?>:
							case <?php echo $load_table->behavior_state['depshdock']; ?>:
								$(row).find("td:first").addClass("inprogress");
								break;

							case <?php echo $load_table->behavior_state['arrive cons']; ?>:
							case <?php echo $load_table->behavior_state['depart cons']; ?>:
							case <?php echo $load_table->behavior_state['arrrecdock']; ?>:
							case <?php echo $load_table->behavior_state['deprecdock']; ?>:
								$(row).find("td:first").addClass("inprogress2");
								break;

							case <?php echo $load_table->behavior_state['complete']; ?>:
								$(row).find("td:first").addClass("success2");
								break;

							case <?php echo $load_table->behavior_state['dispatch']; ?>:
								$(row).find("td:first").addClass("dispatched");
								break;
							
							case <?php echo $load_table->behavior_state['imported']; ?>:
							case <?php echo $load_table->behavior_state['accepted']; ?>:
								$(row).find("td:first").addClass("imported");
								break;
							
							default:
								break;
						}
					}
					var stop_status = $(row).attr('current_stop_status');
					if( typeof(load_status) !== 'undefined' ) {
						if( stop_status == 'complete' ) {
							$(row).find("td:nth-child(3)").addClass("success2");
						}
					}
					
				},
				"infoCallback": function( settings, start, end, max, total, pre ) {
					var api = this.api();
					return pre + ' (' + api.ajax.json().timing + ' s)';
				},
				/*
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
		        */
		
			};

			var myTable = $('#EXP_LOAD').DataTable(opts);
			
			var refresh_rate = <?php echo intval($sts_refresh_rate); ?>;
			var refresh_interval;
			
			if( refresh_rate > 0 ) {
				//alert('rate = '+(refresh_rate * 1000));
				refresh_interval = setInterval( function () {
				    myTable.ajax.reload( null, false ); // user paging is not reset on reload
				}, (refresh_rate * 1000) );
				
				myTable.on( 'draw.dt', function () {
					clearInterval(refresh_interval);
					refresh_interval = setInterval( function () {
					    myTable.ajax.reload( null, false ); // user paging is not reset on reload
					}, (refresh_rate * 1000) );
				});
			}

			$('#EXP_LOAD').on( 'draw.dt', function () {
				$('ul.dropdown-menu li a#modal').on('click', function(event) {
					event.preventDefault();
					$('#listload_modal').modal({
						container: 'body',
						remote: $(this).attr("href")
					}).modal('show');
					return false;					
				});

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
			
			function check_messages() {
				if( code > 0 ) {
					$.ajax({
						url: 'exp_my_messages.php',
						data: {
							GET: code,
							PW: 'AlJazeera'
						},
						dataType: "json",
						success: function(data) {
							console.log(data);
							if( data != false ) {
								console.log('we have messages');
								$('#motd_modal').modal({
									container: 'body'
								});
								var messagetext = '';
								for( var x in data ) {
									console.log(x, data[x].DATE, data[x].FROM, data[x].MESSAGE );
									messagetext += '<div class="row tighter"><div class="col-sm-4">Date: ' + data[x].DATE + ' ' + (data[x].STICKY == 1 ? '<span class="glyphicon glyphicon-pushpin lead tip" title="Sticky - cannot mark read"></span>' : '') +
										'<br>From: ' + data[x].FROM +
										'<br></div><div class="col-sm-8"><strong>' +
										data[x].MESSAGE + '</strong></div></div><hr>';
								}
								$('#motd_modal_body').html(messagetext);
								$('#motd_modal_footer').html('<div class="row tighter"><div class="col-sm-4"></div>' +
									'<div class="col-sm-8"><a class="btn btn-md btn-success" id="mark_read" href="exp_my_messages.php?PW=AlJazeera&READ=' + code + '"><span class="glyphicon glyphicon-remove"></span> Mark Read</a> ' +
									'<a class="btn btn-md btn-default" id="keep" href="exp_listload_driver.php"><span class="glyphicon glyphicon-time"></span> Keep for later</a></div></div>');
							}
						}
					});
				}
			}
			
			check_messages();
		});
	//--></script>

<?php

require_once( "include/footer_inc.php" );
?>

