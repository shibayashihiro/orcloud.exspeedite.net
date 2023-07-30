<?php 
// Set flag that this is a parent file
define( '_STS_INCLUDE', 1 );
define( '_STS_FIXEDCOLUMNS', 1 );

// Setup Session
require_once( "include/sts_session_setup.php" );

require_once( "include/sts_config.php" );
$sts_debug = isset($_GET['debug']) && in_group( EXT_GROUP_DEBUG );
require_once( "include/sts_session_class.php" );

$my_session = sts_session::getInstance( $exspeedite_db, $sts_debug );
$my_session->access_check( EXT_GROUP_ADMIN );	// Make sure we should be here

$sts_subtitle = "Software Change Requests";
require_once( "include/header_inc.php" );
require_once( "include/navbar_inc.php" );

?>
<style>
th, td { white-space: nowrap; }
div.dataTables_wrapper {
	margin: 0 auto;
}

table.DTFC_Cloned {
	margin-bottom: -3px !important;
}

div.DTFC_LeftBodyLiner table.DTFC_Cloned {
	margin-top: 0 !important;	
}

table.DTFC_Cloned tbody tr.even td {
	background-color: #fff;
}
</style>

<div class="container-full" role="main">
<?php
require_once( "include/sts_result_class.php" );
require_once( "include/sts_scr_class.php" );

$scr_table = sts_scr::getInstance($exspeedite_db, $sts_debug);

if( ! isset($_SESSION['SCR_FILTER']) ) $_SESSION['SCR_FILTER'] = 'All';
if( isset($_POST['SCR_FILTER']) ) $_SESSION['SCR_FILTER'] = $_POST['SCR_FILTER'];

$match = false;
if( $_SESSION['SCR_FILTER'] <> 'All') {
	if(  $_SESSION['SCR_FILTER'] == 'My SCRs') {
		$match = "ORIGINATOR = '".$_SESSION['EXT_USER_CODE']."' OR ASSIGNED_DEV = '".$_SESSION['EXT_USER_CODE']."' OR ASSIGNED_QA = '".$_SESSION['EXT_USER_CODE']."'";
	} else if(  $_SESSION['SCR_FILTER'] == 'Current') {
		$match = "CURRENT_STATUS NOT IN ( ".$scr_table->behavior_state['build'].
			", ".$scr_table->behavior_state['dead'].
			", ".$scr_table->behavior_state['docked'].
			", ".$scr_table->behavior_state['late'].")";
	} else if(  $_SESSION['SCR_FILTER'] == 'Today') {
		$match = "DATE(CHANGED_DATE) = DATE(NOW())";
	} else if(  $_SESSION['SCR_FILTER'] == 'MyCurrent') {
		$match = "CURRENT_STATUS NOT IN ( ".$scr_table->behavior_state['build'].
			", ".$scr_table->behavior_state['dead'].
			", ".$scr_table->behavior_state['docked'].
			", ".$scr_table->behavior_state['accepted'].
			", ".$scr_table->behavior_state['sentinfo'].
			", ".$scr_table->behavior_state['late'].") AND ASSIGNED_DEV = '".$_SESSION['EXT_USER_CODE']."'";
	} else {
		$found = false;
		foreach( $scr_table->status_codes() as $row ) {
			if( $_SESSION['SCR_FILTER'] == $row["STATUS_STATE"] ) {
				$match = "CURRENT_STATUS = '".$row["CURRENT_STATUS"]."'";
				$found = true;
				break;
			}
		}
		
		if( ! $found ) {
			foreach( $scr_table->products() as $row ) {
				if( $_SESSION['SCR_FILTER'] == $row["PRODUCT"] ) {
					$match = "PRODUCT = '".$_SESSION['SCR_FILTER']."'";
					$found = true;
					break;
				}
			}
		}

		if( ! $found ) {
			foreach( $scr_table->fixed_releases() as $row ) {
				if( $_SESSION['SCR_FILTER'] == $row["FIXED_IN_RELEASE"] ) {
					$match = "FIXED_IN_RELEASE = '".$_SESSION['SCR_FILTER']."'";
					$found = true;
					break;
				}
			}
		}

		if( ! $found ) {
			foreach( $scr_table->current_developers() as $row ) {
				if( $_SESSION['SCR_FILTER'] == 'DEV_'.$row["USERNAME"] ) {
					$match = "CURRENT_STATUS NOT IN ( ".$scr_table->behavior_state['build'].
						", ".$scr_table->behavior_state['dead'].
						", ".$scr_table->behavior_state['docked'].
						", ".$scr_table->behavior_state['late'].") AND ASSIGNED_DEV = '".$row['ASSIGNED_DEV']."'";
					$found = true;
					break;
				}
			}
		}

		if( ! $found ) {
			foreach( $scr_table->all_clients() as $row ) {
				if( $_SESSION['SCR_FILTER'] == 'CLI_'.$row["SCR_CLIENT"] ) {
					$match = "COALESCE(SCR_CLIENT,'') = '".$row['SCR_CLIENT']."'";
					$found = true;
					break;
				}
			}
		}
	}
}

$filters_html = '<div class="btn-group"><a class="btn btn-sm btn-success" href="exp_listscr.php"><span class="glyphicon glyphicon-refresh"></span></a>';

$filters_html .= '<select class="form-control input-sm" name="SCR_FILTER" id="SCR_FILTER"   onchange="form.submit();">';
		$filters_html .= '<option value="All" '.($_SESSION['SCR_FILTER'] == 'All' ? 'selected' : '').'>All SCRs</option>
		';
		$filters_html .= '<option value="Current" '.($_SESSION['SCR_FILTER'] == 'Current' ? 'selected' : '').'>Current SCRs</option>
		';
		$filters_html .= '<option value="Today" '.($_SESSION['SCR_FILTER'] == 'Today' ? 'selected' : '').'>Changed Today</option>
		';
		$filters_html .= '<option value="My SCRs" '.($_SESSION['SCR_FILTER'] == 'My SCRs' ? 'selected' : '').'>My SCRs</option>
		';
		$filters_html .= '<option value="MyCurrent" '.($_SESSION['SCR_FILTER'] == 'MyCurrent' ? 'selected' : '').'>My Current SCRs</option>
		';
$filters_html .= '<option class="select-dash" disabled="disabled">----</option>
';
foreach( $scr_table->status_codes() as $row ) {
	$filters_html .= '<option value="'.$row["STATUS_STATE"].'" '.($_SESSION['SCR_FILTER'] == $row["STATUS_STATE"] ? 'selected' : '').'>'.$row["STATUS_STATE"].' ('.$row["NUM"].')</option>
	';
}

$filters_html .= '<option class="select-dash" disabled="disabled">----</option>
';

foreach( $scr_table->products() as $row ) {
	$filters_html .= '<option value="'.$row["PRODUCT"].'" '.($_SESSION['SCR_FILTER'] == $row["PRODUCT"] ? 'selected' : '').'>'.$row["PRODUCT"].' ('.$row["NUM"].')</option>
	';
}

$filters_html .= '<option class="select-dash" disabled="disabled">----</option>
';

foreach( $scr_table->fixed_releases() as $row ) {
	$filters_html .= '<option value="'.$row["FIXED_IN_RELEASE"].'" '.($_SESSION['SCR_FILTER'] == $row["FIXED_IN_RELEASE"] ? 'selected' : '').'>'.$row["FIXED_IN_RELEASE"].' ('.$row["NUM"].')</option>
	';
}

$filters_html .= '<option class="select-dash" disabled="disabled">----</option>
';

foreach( $scr_table->current_developers() as $row ) {
	$filters_html .= '<option value="DEV_'.$row["USERNAME"].'" '.
		($_SESSION['SCR_FILTER'] == 'DEV_'.$row["USERNAME"] ? 'selected' : '').'>'.
		$row["USERNAME"].' ('.$row["NUM"].')</option>
	';
}

$filters_html .= '<option class="select-dash" disabled="disabled">----</option>
';

foreach( $scr_table->all_clients() as $row ) {
	$filters_html .= '<option value="CLI_'.$row["SCR_CLIENT"].'" '.
		($_SESSION['SCR_FILTER'] == 'CLI_'.$row["SCR_CLIENT"] ? 'selected' : '').'>'.
		$row["SCR_CLIENT"].' ('.$row["NUM"].')</option>
	';
}

$filters_html .= '</select>';

$filters_html .= ' <a class="btn btn-sm btn-primary" href="exp_listscr.php?REPORT"><span class="glyphicon glyphicon-th-list"></span> Text</a>';

$filters_html .= ' <a class="btn btn-sm btn-default" href="exp_kpi_scr.php"><span class="glyphicon glyphicon-eye-open"></span> KPIs</a>';

$filters_html .= ' <a class="btn btn-sm btn-success" href="exp_checkrel.php"><span class="glyphicon glyphicon-dashboard"></span> CheckRel</a></div>';


$sts_result_scr_edit['filters_html'] = $filters_html;

if( isset($_GET["REPORT"])) {
	$result = $scr_table->fetch_rows($match);
	echo '<p><strong>SCRS Matching '.$match.'</strong></p>';
	if( is_array($result) && count($result) > 0 ) {
		foreach( $result as $row ) {
			echo 'SCR# '.$row["SCR_CODE"].', '.$row["TITLE"].
			($row["SETTINGS"] ? ' <strong>[SETTINGS CHANGED]</strong>' : '').
			($row["SCHEMA_CHANGES"] ? ' <strong>[SCHEMA CHANGED]</strong>' : '').
			($row["PATCHED"] ? ' <strong>[PATCHED ON CLIENT SYSTEM]</strong>' : '').
			'<br>';
		}
	}
	echo '<br><br><br><br><a class="btn btn-sm btn-default" href="exp_listscr.php"><span class="glyphicon glyphicon-arrow-left"></span> Back</a>';
} else {
	$rslt = new sts_result( $scr_table, $match, $sts_debug );
	echo $rslt->render( $sts_result_scr_layout, $sts_result_scr_edit, false, false );
}

?>
</div>

	<script language="JavaScript" type="text/javascript"><!--
		
		$(document).ready( function () {
			//alert(($(window).height() - 150) + "px");
			document.documentElement.style.overflow = 'hidden';  // firefox, chrome
			document.body.scroll = "no"; // ie only

			$('#EXP_SCR').dataTable({
		        //"bLengthChange": false,
		        "bFilter": true,
		        //stateSave: true,
		        "bSort": true,
		        "bInfo": true,
				"bAutoWidth": false,
				//"bProcessing": true, 
				"sScrollX": "100%",
				"sScrollY": ($(window).height() - 270) + "px",
				"sScrollXInner": "200%",
		        "lengthMenu": [<?php echo isset($sts_length_menu) ? $sts_length_menu : '25, 50, 100, 250'; ?>],
				"bPaginate": true,
				"bScrollCollapse": false,
				"bSortClasses": false,	
				"processing": true,
				"serverSide": true,
				"deferRender": true,
				"order": [[ 0, "desc" ]],

				"ajax": {
					"url": "exp_listscrajax.php",
					"data": function( d ) {
						d.match = encodeURIComponent("<?php echo $match; ?>");
						d.sort = encodeURIComponent("<?php echo $sts_result_scr_edit['sort']; ?>");
					}

				},
				fixedColumns:   true,
				"columns": [
					//{ "searchable": false, "orderable": false },
					<?php
						foreach( $sts_result_scr_layout as $key => $row ) {
							if( $row["format"] <> 'hidden')
								echo '{ "data": "'.$key.'", "searchable": '.
								(isset($row["searchable"]) && $row["searchable"] ? 'true' : 'false').',
								 "orderable": true'.
								(isset($row["align"]) ? ',"className": "text-'.$row["align"].'"' : '').
									(isset($row["length"]) ? ', "width": "'.$row["length"].'px"' : '').
									(isset($row["format"]) && $row["format"] == 'hidden' ? ', "visible": false' : '').' },
						';
						}
					?>
				],
				"infoCallback": function( settings, start, end, max, total, pre ) {
					var api = this.api();
					return pre + ' (' + api.ajax.json().timing + ' s)';
				},
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

