<?php 

// $Id: exp_listsummary.php 4350 2021-03-02 19:14:52Z duncan $
// List summary - client view.

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

require_once( "include/sts_setting_class.php" );
$setting_table = sts_setting::getInstance($exspeedite_db, $sts_debug);
//! SCR# 298 Set flag to include JQuery Location Picker
define( '_STS_LOCATION', 1 );
$sts_google_api_key = $setting_table->get( 'api', 'GOOGLE_API_KEY' );

$sts_subtitle = "Summary View";
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
require_once( "include/sts_office_class.php" );

$office_table = sts_office::getInstance($exspeedite_db, $sts_debug);

$sts_length_menu = $setting_table->get( 'option', 'LENGTH_MENU' );
$sts_refresh_rate = $setting_table->get( 'option', 'SUMMARY_SCREEN_REFRESH_RATE' );
$multi_company = $office_table->multi_company();

if( $multi_company ) {
	if( ! isset($_SESSION['LOAD_OFFICE']) ) $_SESSION['LOAD_OFFICE'] = 'all';
	if( isset($_POST['LOAD_OFFICE']) ) $_SESSION['LOAD_OFFICE'] = $_POST['LOAD_OFFICE'];
} else {
	unset($sts_result_loads_summary_layout["OFFICE_NUM"]);
	$sts_result_summary_edit['toprow'] = str_replace('colspan="9"', 'colspan="8"', $sts_result_summary_edit['toprow']);
}

//! SCR# 350 - Add date filters
if( ! isset($_SESSION['LPICKDROP']) ) $_SESSION['LPICKDROP'] = 'pick';
if( ! isset($_SESSION['LDUEACT']) ) $_SESSION['LDUEACT'] = 'due';
if( ! isset($_SESSION['LFROMDATE']) ) $_SESSION['LFROMDATE'] = '';
if( ! isset($_SESSION['LTODATE']) ) $_SESSION['LTODATE'] = '';
if( ! isset($_SESSION['LDFILTER']) ) $_SESSION['LDFILTER'] = 'default';

if( isset($_POST["dofilter"])) {
	if( isset($_POST['LPICKDROP']) ) $_SESSION['LPICKDROP'] = $_POST['LPICKDROP'];
	if( isset($_POST['LDUEACT']) ) $_SESSION['LDUEACT'] = $_POST['LDUEACT'];
	$_SESSION['LFROMDATE'] = empty($_POST['LFROMDATE']) ? '' :
		date("Y-m-d", strtotime($_POST['LFROMDATE']));
	$_SESSION['LTODATE'] = empty($_POST['LTODATE']) ? '' :
		date("Y-m-d", strtotime($_POST['LTODATE']));
} else if( isset($_POST["clearfilter"])) {
	$_SESSION['LPICKDROP'] = 'pick';
	$_SESSION['LDUEACT'] = 'due';
	$_SESSION['LFROMDATE'] = '';
	$_SESSION['LTODATE'] = '';
}

//! Initialize $match
$match = '';
$tip = 'No date filter set';

if( ! empty($_SESSION['LFROMDATE']) && ! empty($_SESSION['LTODATE']) &&
	strtotime($_SESSION['LTODATE']) >= strtotime($_SESSION['LFROMDATE']) ) {
	$_SESSION['LDFILTER'] = 'success';
	$range = "BETWEEN '".date("Y-m-d", strtotime($_SESSION['LFROMDATE']))."' AND '".date("Y-m-d", strtotime($_SESSION['LTODATE']))."'";
	$range2 = 'between '.date("m/d/Y", strtotime($_SESSION['LFROMDATE']))." and ".date("m/d/Y", strtotime($_SESSION['LTODATE']));
	if( $_SESSION['LPICKDROP'] == 'pick') {
		if( $_SESSION['LDUEACT'] == 'due' ) {
			$match = ($match <> '' ? $match." AND " : "")."LOAD_DATE(LOAD_CODE, 'pick', 'due') ".$range;
			$tip = 'Pickup due '.$range2;
		} else {
			$match = ($match <> '' ? $match." AND " : "")."LOAD_DATE(LOAD_CODE, 'pick', 'actual') ".$range;
			$tip = 'Actual Pickup '.$range2;
		}
	} else {
		if( $_SESSION['LDUEACT'] == 'due' ) {
			$match = ($match <> '' ? $match." AND " : "")."LOAD_DATE(LOAD_CODE, 'drop', 'due') ".$range;
			$tip = 'Delivery due '.$range2;
		} else {
			$match = ($match <> '' ? $match." AND " : "")."LOAD_DATE(LOAD_CODE, 'drop', 'actual') ".$range;
			$tip = 'Actual Delivery '.$range2;
		}
	}
	
} else {
	$_SESSION['LDFILTER'] = 'default';
}

//echo '<p>Filter '.$_SESSION['LPICKDROP'].' '.$_SESSION['LDUEACT'].' '.$_SESSION['LFROMDATE'].' - '.$_SESSION['LTODATE'];
	
	

$load_table = new sts_load_left_join($exspeedite_db, $sts_debug);

$match = ($match <> '' ? $match." AND " : "").'EXP_LOAD.CURRENT_STATUS NOT IN ('.$load_table->behavior_state["oapproved"].','.$load_table->behavior_state["approved"].','.
	$load_table->behavior_state["cancel"].','.$load_table->behavior_state["complete"].','.
	$load_table->behavior_state["manifest"].','.$load_table->behavior_state["billed"].')';


$filters_html = '<div class="form-group"><a class="btn btn-sm btn-primary" href="exp_listload.php"><img src="images/load_icon.png" alt="load_icon" height="18"> Trips/Loads</a>
<a class="btn btn-sm btn-success" href="exp_listsummary.php"><span class="glyphicon glyphicon-refresh"></span></a></div>';

if( $multi_company && count($_SESSION['EXT_USER_OFFICES']) > 1 ) {
	$filters_html .= '<select class="form-control input-sm" name="LOAD_OFFICE" id="LOAD_OFFICE"   onchange="form.submit();">';
	$filters_html .= '<option value="all" '.($_SESSION['LOAD_OFFICE'] == 'all' ? 'selected' : '').'>All Offices</option>
		';
	foreach( $_SESSION['EXT_USER_OFFICES'] as $value => $label ) {
		$filters_html .= '<option value="'.$value.'" '.($_SESSION['LOAD_OFFICE'] == $value ? 'selected' : '').'>'.$label.'</option>
		';
	}
	$filters_html .= '</select>';
}

$filters_html .= '<button type="button" class="btn btn-'.$_SESSION['LDFILTER'].' btn-sm'.(empty($tip) ? '"' : ' tip" title="'.$tip.'"').' data-toggle="modal" data-target="#ldatefilter_modal"><span class="glyphicon glyphicon-calendar"></span></button>';

$sts_result_summary_edit['filters_html'] = $filters_html;

$match = $office_table->office_code_match_multiple( $match );
if( $multi_company ) {
	if( $_SESSION['LOAD_OFFICE'] == 'all' )
		$match = $office_table->office_code_match_multiple( $match );
	else
		$match = ($match <> '' ? $match." AND " : "")."OFFICE_CODE = ".$_SESSION['LOAD_OFFICE'];
}

$rslt = new sts_result( $load_table, $match, $sts_debug );
echo $rslt->render( $sts_result_loads_summary_layout, $sts_result_summary_edit, false, false );

//! SCR# 298 - Check Call functionality
require_once("exp_check_call.php");

//! SCR# 350 - Add date filters
if( $_SESSION['LPICKDROP'] == 'pick' ) {
	$pd1 = ' checked';
	$pd2 = '';
} else {
	$pd1 = '';
	$pd2 = ' checked';
}
if( $_SESSION['LDUEACT'] == 'due' ) {
	$da1 = ' checked';
	$da2 = '';
} else {
	$da1 = '';
	$da2 = ' checked';
}

$date_format = $_SESSION['ios'] == 'true' ? "Y-m-d" : "m/d/Y";
if( ! empty($_SESSION['LFROMDATE']) ) {
	$fd = ' value="'.date($date_format, strtotime($_SESSION['LFROMDATE'])).'"';
} else {
	$fd = '';
}
if( ! empty($_SESSION['LTODATE']) ) {
	$td = ' value="'.date($date_format, strtotime($_SESSION['LTODATE'])).'"';
} else {
	$td = '';
}

?>
</div>

	<div class="modal fade fuzzy bs-example-modal-sm" tabindex="-1" role="dialog" aria-labelledby="mySmallModalLabel" aria-hidden="true" id="ldatefilter_modal">
	  <div class="modal-dialog">
		<div class="modal-content">
		<div class="modal-header">
			<button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
			<h4 class="modal-title" id="myModalLabel"><span class="text-success"><strong><span class="glyphicon glyphicon-calendar"></span> Date Filter</strong></span></h4>
		</div>
		<div class="modal-body">
			<form role="form" class="form-horizontal" action="exp_listsummary.php" method="post" enctype="multipart/form-data" name="datefilter" id="datefilter">
					<div class="form-group tighter">
						<div class="col-sm-6 well well-md tighter">
							<div class="form-group tighter">
								<div class="col-sm-6 tighter">
									<label><input name="LPICKDROP" id="LPICKDROP1" type="radio" value="pick"<?php echo $pd1; ?>> Pickup</label>
								</div>
								<div class="col-sm-6 tighter">
									<label><input name="LPICKDROP" id="LPICKDROP2"  type="radio" value="drop"<?php echo $pd2; ?>> Delivery</label>
								</div>
							</div>
						</div>
						<div class="col-sm-6 well well-md tighter">
							<div class="form-group tighter">
								<div class="col-sm-6">
									<label><input name="LDUEACT" id="LDUEACT1" type="radio" value="due"<?php echo $da1; ?>> Due</label>
								</div>
								<div class="col-sm-6 tighter">
									<label><input name="LDUEACT" id="LDUEACT2"  type="radio" value="actual"<?php echo $da2; ?>> Actual</label>
								</div>
							</div>
						</div>
					</div>
					<div class="form-group tighter">
						<div class="col-sm-6 well well-md tighter">
							<input type="<?php echo ($_SESSION['ios'] == 'true' ? 'date' : 'text');?>" class="form-control input-sm<?php echo ($_SESSION['ios'] != 'true' ? ' date' : '');?>" style="width: auto;" name="LFROMDATE" placeholder="From Date"<?php echo $fd; ?>>
						</div>
						<div class="col-sm-6 well well-md tighter">
							<input type="<?php echo ($_SESSION['ios'] == 'true' ? 'date' : 'text');?>" class="form-control input-sm<?php echo ($_SESSION['ios'] != 'true' ? ' date' : '');?>" style="width: auto;" name="LTODATE" placeholder="To Date"<?php echo $td; ?>>
						</div>
					</div>
					<div class="form-group tighter">
						<div class="col-sm-4 col-sm-offset-2 tighter">
							<button class="btn btn-md btn-success" name="dofilter" type="submit"><span class="glyphicon glyphicon-search"></span> Filter</button>
						</div>
						<div class="col-sm-4 col-sm-offset-2 tighter">
							<button class="btn btn-md btn-default" name="clearfilter" type="submit"><span class="glyphicon glyphicon-remove"></span> Clear Filter</button>
						</div>
					</div>
					<div class="form-group">
						<br>
						<p class="text-center">Needs two dates with (To Date >= From Date) to function.</p>
					</div>
			</form>
				
		</div>
		</div>
		</div>
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
			document.documentElement.style.overflow = 'hidden';  // firefox, chrome
			document.body.scroll = "no"; // ie only
			
			function shade_status(row, sattr, col1 = 0, col2 = 0, col3 = 0) {
				var stat = row.attr(sattr);
				if( typeof(stat) !== 'undefined' ) {
					switch(parseInt(stat)) {
						case 1:
							if( col1 > 0 ) row.find("td:nth-child("+col1+")").addClass("inprogress2");
							if( col2 > 0 ) row.find("td:nth-child("+col2+")").addClass("inprogress2");
							if( col3 > 0 ) row.find("td:nth-child("+col3+")").addClass("inprogress2");
							break;

						case 2:
							if( col1 > 0 ) row.find("td:nth-child("+col1+")").addClass("success2");
							if( col2 > 0 ) row.find("td:nth-child("+col2+")").addClass("success2");
							if( col3 > 0 ) row.find("td:nth-child("+col3+")").addClass("success2");
							break;

						default:
							break;
					}
				}
			}
			
			var opts = {
		        //"bLengthChange": false,
		        "bFilter": true,
		        stateSave: true,
		        "bSort": true,
		        "bInfo": true,
				//"bAutoWidth": false,
				//"bProcessing": true, 
				"sScrollX": "100%",
				"sScrollY": ($(window).height() - 334) + "px",
				//"sScrollXInner": "120%",
		        "lengthMenu": [<?php echo isset($sts_length_menu) ? $sts_length_menu : '25, 50, 100, 250'; ?>],
		        <?php if( isset($_POST["EXP_LOAD_length"]) )
			        echo '"pageLength": '.$_POST["EXP_LOAD_length"].','; ?>
				"bPaginate": true,
				"bScrollCollapse": false,
				"bSortClasses": false,
				"order": [[ 0, "desc" ]],
				"processing": true,
				"serverSide": true,
				//"dom": "frtiS",
				"deferRender": true,
				"ajax": {
					"url": "exp_listsummaryajax.php",
					"data": function( d ) {
						d.match = encodeURIComponent("<?php echo $match; ?>");
						d.sort = encodeURIComponent("<?php echo $sts_result_summary_edit['sort']; ?>");
					}

				},
				"columns": [
					//{ "searchable": false, "orderable": false },
					<?php
						foreach( $sts_result_loads_summary_layout as $key => $row ) {
							if( $row["format"] <> 'hidden')
								echo '{ "data": "'.$key.'", "searchable": '.
								(isset($row["searchable"]) && $row["searchable"] ? 'true' : 'false').
								', "orderable": '.
								(isset($row["sortable"]) && $row["sortable"] ? 'true' : 'false').
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
							
							default:
								break;
						}
					}

					// Important
					// These are the column positions, so that the shading and bars line up
					var mc = <?php echo $multi_company ? 1 : 0; ?>;
					var sstat = 9 + mc, 
						cstat0 = 11 + mc, cstat1=14 + mc, cstat2=17 + mc, cstat3=20 + mc,
						bsstat = 23 + mc,
						bcstat0 = 26 + mc, bcstat1=28 + mc;
						
					shade_status($(row), 'sstat', sstat, sstat+1);
					shade_status($(row), 'cstat0', cstat0, cstat0+1, cstat0+2);
					shade_status($(row), 'cstat1', cstat1, cstat1+1, cstat1+2);
					shade_status($(row), 'cstat2', cstat2, cstat2+1, cstat2+2);
					shade_status($(row), 'cstat3', cstat3, cstat3+1, cstat3+2);

					shade_status($(row), 'bsstat', bsstat, bsstat+1, bsstat+2);
					shade_status($(row), 'bcstat0', bcstat0, bcstat0+1);
					shade_status($(row), 'bcstat1', bcstat1, bcstat1+1);


					// Some green vertical bars
					$(row).find("td:nth-child("+sstat+")").css('border-left','2px solid #4d8e31');
					$(row).find("td:nth-child("+cstat0+")").css('border-left','2px solid #4d8e31');
					$(row).find("td:nth-child("+cstat1+")").css('border-left','2px solid #4d8e31');
					$(row).find("td:nth-child("+cstat2+")").css('border-left','2px solid #4d8e31');
					$(row).find("td:nth-child("+cstat3+")").css('border-left','2px solid #4d8e31');
					$(row).find("td:nth-child("+bsstat+")").css('border-left','2px solid #4d8e31');
					$(row).find("td:nth-child("+bcstat0+")").css('border-left','2px solid #4d8e31');
					$(row).find("td:nth-child("+bcstat1+")").css('border-left','2px solid #4d8e31');
					
				},
		
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
			
		});
	//--></script>

<?php

require_once( "include/footer_inc.php" );
?>

