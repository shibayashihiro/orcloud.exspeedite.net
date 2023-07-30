<?php 

// $Id: exp_listclient.php 5030 2023-04-12 20:31:34Z duncan $
// List client screen

// Set flag that this is a parent file
define( '_STS_INCLUDE', 1 );

// Setup Session
require_once( "include/sts_session_setup.php" );

require_once( "include/sts_config.php" );
$sts_debug = isset($_GET['debug']) && in_group( EXT_GROUP_DEBUG );
require_once( "include/sts_session_class.php" );

$my_session = sts_session::getInstance( $exspeedite_db, $sts_debug );
$my_session->access_check( $sts_table_access[CLIENT_TABLE], EXT_GROUP_SALES );	// Make sure we should be here

if( ! isset($_GET) || ! isset($_GET["EXPORT"])) {
	$sts_subtitle = "List Clients";
	require_once( "include/header_inc.php" );
	require_once( "include/navbar_inc.php" );
}

?>
<div class="container-full" role="main">
<?php
require_once( "include/sts_result_class.php" );
require_once( "include/sts_client_class.php" );
require_once( "include/sts_setting_class.php" );
require_once( "include/sts_client_activity_class.php" );
require_once( "include/sts_csv_class.php" );
require_once( "include/sts_pipedrive_class.php" );

$setting_table = sts_setting::getInstance($exspeedite_db, $sts_debug);
$sts_length_menu = $setting_table->get( 'option', 'LENGTH_MENU' );
$sts_export_sage50 = $setting_table->get( 'api', 'EXPORT_SAGE50_CSV' ) == 'true';
$sts_cms_enabled = $setting_table->get("option", "CMS_ENABLED") == 'true';
$sts_cms_salespeople_leads = $setting_table->get("option", "CMS_SALESPEOPLE_LEADS") == 'true';
$sts_zoominfo_enabled = $setting_table->get("option", "ZOOMINFO_ENABLED") == 'true';

$client_table = sts_client_lj::getInstance($exspeedite_db, $sts_debug);

// SCR# 715 - Pipedrive API
$pipedrive = sts_pipedrive::getInstance( $exspeedite_db, $sts_debug );
$sts_pipedrive_enabled = $pipedrive->is_enabled();
if( isset($_GET["PIPEDRIVE"]) && $sts_pipedrive_enabled ) {
	ob_implicit_flush(true);	
	if (ob_get_level() == 0) ob_start();
	echo '<div class="container" role="main">
		<h2 class="text-center"><img src="images/pipedrive-logo.png" alt="pipedrive-logo" valign="baseline" height="80" /> Importing leads from Pipedrive...<br><br>
		This is just for the Pipedrive user '.$pipedrive->get_my_name().'<br>
		They will appear as leads (duplicates) or prospects.</h3>';
	ob_flush(); flush();

	$pipedrive->get_leads( true );
	echo '<h3><a class="btn btn-success" href="exp_listclient.php"><span class="glyphicon glyphicon-ok"></span> Click to Continue</a>
	<button class="btn btn-default" onclick="window.print();return false;"><span class="glyphicon glyphicon-print"></span> Print</button></h3>
	';
	ob_flush(); flush();
	die;
}

else if( isset($_GET["EMPTYTRASH"]) ) {
	if( is_array($_SESSION) && isset($_SESSION['CLIENT_TYPE']) &&
		$_SESSION['CLIENT_TYPE'] <> 'all' ) {
		$dmatch = "CLIENT_TYPE = '".$_SESSION['CLIENT_TYPE']."'";
	} 

	$client_table->empty_trash( $dmatch );
}

$rslt = new sts_result( $client_table, false, $sts_debug );

//! If true display only my clients
$my_sales_clients =  $my_session->cms_restricted_salesperson();

if( ! isset($_SESSION['CLIENT_FILTER']) )
	$_SESSION['CLIENT_FILTER'] = $my_sales_clients ? 'mine' : 'all';
if( isset($_POST['CLIENT_FILTER']) ) $_SESSION['CLIENT_FILTER'] = $_POST['CLIENT_FILTER'];

$client_title = '';
if( $sts_cms_enabled ) {
	if( ! isset($_SESSION['STATE_FILTER']) )
		$_SESSION['STATE_FILTER'] = 'all';
	if( isset($_POST['STATE_FILTER']) ) $_SESSION['STATE_FILTER'] = $_POST['STATE_FILTER'];

	//! SCR# 420 - List of client types
	if( isset($_GET['CLIENT_TYPE']) )
		$_SESSION['CLIENT_TYPE'] = $_GET['CLIENT_TYPE'];
	else if( isset($_POST['CLIENT_TYPE']) )
		$_SESSION['CLIENT_TYPE'] = $_POST['CLIENT_TYPE'];
	else if( ! isset($_SESSION['CLIENT_TYPE']) )
		$_SESSION['CLIENT_TYPE'] = 'all';

	if( $my_sales_clients && ! $sts_cms_salespeople_leads ) {
		$valid_client_types = array( 'all' => 'All Types',
									'prospect' => 'Prospects', 
									'client' => 'Clients' );
	} else {
		$valid_client_types = array( 'all' => 'All Types',
									'lead' => 'Leads', 
									'prospect' => 'Prospects', 
									'client' => 'Clients' );
	}
	$client_title = '<select class="form-control input-sm" name="CLIENT_TYPE"
		id="CLIENT_TYPE"   onchange="form.submit();">';
	foreach( $valid_client_types as $value => $label ) {
		if( $label == 'sep' )
			$client_title .= '<option class="select-dash" disabled="disabled">----</option>
			';
		else
			$client_title .= '<option value="'.$value.'" '.($_SESSION['CLIENT_TYPE'] == $value ? 'selected' : '').'>'.$label.'</option>
		';
	}
	$client_title .= '</select>';
}

if( $my_sales_clients ) {
	$valid_types = array();
	unset($sts_result_clients_edit['add'], $sts_result_clients_edit['addbutton']);
	$sts_result_clients_edit['rowbuttons'] = array(
		array( 'url' => 'exp_editclient.php?CODE=', 'key' => 'CLIENT_CODE', 'label' => 'NAME', 'tip' => 'View/edit client ', 'icon' => 'glyphicon glyphicon-edit', 'showif' => 'notdeleted' )
	);
	$sts_result_clients_edit['nodelete'] = true;
	$client_title = 'My '.$client_title;
	
} else {
	$valid_types = array(	'all' => 'All Clients',
							'shipper' => 'Shipper', 
							'consignee' => 'Consignee', 
							'dock' => 'Dock', 
							'bill_to' => 'Bill-to' );
}

if( $client_title <> '' )
	$sts_result_clients_edit['title'] =
		str_replace('Clients', $client_title, $sts_result_clients_edit['title']);

if( $sts_cms_enabled ) {
	$valid_types['sep1'] = 'sep';
	$valid_types['mine'] = 'My Clients';
	$valid_types['sep2'] = 'sep';
} else {
	unset($sts_result_clients_layout['CLIENT_TYPE']);
	unset($sts_result_clients_layout['CURRENT_STATUS']);
}

if( $sts_cms_enabled ) {
	$status_desc = $client_table->database->get_multiple_rows("
		SELECT S.STATUS_CODES_CODE, S.STATUS_STATE, S.STATUS_DESCRIPTION
		FROM EXP_STATUS_CODES S
		WHERE SOURCE_TYPE = 'client'
	");
	
	if( is_array($status_desc) && count($status_desc) > 0 ) {
		foreach( $status_desc as $row ) {
			$valid_types[$row["STATUS_STATE"]] = $row["STATUS_STATE"];
		}
	}
	$valid_types['sep3'] = 'sep';
	$sales_desc = $client_table->database->get_multiple_rows("
		SELECT SALES_PERSON, USERNAME, NUM
		FROM
		  (SELECT SALES_PERSON,
		          COUNT(*) NUM
		   FROM EXP_CLIENT
		   WHERE COALESCE(SALES_PERSON, 0) <> 0
		   AND ISDELETED = FALSE
		   GROUP BY SALES_PERSON) X, EXP_USER
		   WHERE EXP_USER.USER_CODE = X.SALES_PERSON
		   AND ISACTIVE = 'Active'
		ORDER BY USERNAME ASC
	");
	
	if( is_array($sales_desc) && count($sales_desc) > 0 ) {
		foreach( $sales_desc as $row ) {
			$valid_types['SALES_'.$row["SALES_PERSON"]] = $row["USERNAME"].' ('.$row["NUM"].')';
		}
	}

}

$filters_html = '<div class="btn-group"><a class="btn btn-sm btn-success tip" title="Refresh this screen" href="exp_listclient.php"><span class="glyphicon glyphicon-refresh"></span></a>';

if( ! $my_sales_clients ) {
	$filters_html .= '<select class="form-control input-sm" name="CLIENT_FILTER"
		id="CLIENT_FILTER"   onchange="form.submit();">';
	foreach( $valid_types as $value => $label ) {
		if( $label == 'sep' )
			$filters_html .= '<option class="select-dash" disabled="disabled">----</option>
			';
		else
			$filters_html .= '<option value="'.$value.'" '.($_SESSION['CLIENT_FILTER'] == $value ? 'selected' : '').'>'.$label.'</option>
		';
	}
	$filters_html .= '</select>';
}

if( $sts_cms_enabled ) {
	$filters_html .= $client_table->usstates_menu( 'STATE_FILTER', $_SESSION['STATE_FILTER'] );
}

$filters_html .= '</div>';

if( in_group(EXT_GROUP_ADMIN) ) {
	$filters_html .= '<a class="btn btn-sm btn-warning tip" title="Empty Trash - permanently remove all deleted leads, prospects, or clients" href="exp_listclient.php?EMPTYTRASH"><span class="glyphicon glyphicon-trash"></span></a>';
}

//! Zoominfo IMPORT
if( $sts_cms_enabled ) {
	$filters_html .= ' <a class="btn btn-sm btn-primary tip" title="Add a Lead" href="exp_addlead.php"><span class="glyphicon glyphicon-plus"></span> Add Lead</a>';
	if( $sts_zoominfo_enabled && in_group(EXT_GROUP_MANAGER) && in_group(EXT_GROUP_SALES) &&
	(($sts_cms_enabled && $_SESSION['CLIENT_TYPE'] == 'lead') ||
	! $sts_cms_enabled) ) {
		$filters_html .= ' <a class="btn btn-sm btn-default tip" title="Import Leads From Zoominfo" href="exp_import_zoom.php"><img src="images/zi_logo.png" alt="zi_logo" valign="baseline" height="20" /></a>';
	}
	
	// SCR# 715 - Pipedrive API
	if( $sts_pipedrive_enabled && in_array($_SESSION['CLIENT_TYPE'], ['lead', 'prospect'])) {
		$filters_html .= ' <a class="btn btn-sm btn-default tip" title="Import Your Leads From Pipedrive" href="exp_listclient.php?PIPEDRIVE"><img src="images/pipedrive-logo.png" alt="pipedrive-logo" valign="baseline" height="20" /></a>';

	}
}

//! SCR# 632 - Export to CSV
if( in_group(EXT_GROUP_MANAGER) && in_group(EXT_GROUP_SALES) ) {
	$filters_html .= ' <a class="btn btn-sm btn-primary tip" title="Export to CSV" href="exp_listclient.php?EXPORT"><span class="glyphicon glyphicon-th-list"></span></a>';
}

//! Sage50 EXPORT
if( $sts_export_sage50 && in_group(EXT_GROUP_SAGE50) &&
	(($sts_cms_enabled && $_SESSION['CLIENT_TYPE'] == 'client') ||
	! $sts_cms_enabled) ) {
	$filters_html .= ' <a class="btn btn-sm btn-danger tip" title="Export Clients to Sage 50" href="exp_export_csv.php?pw=GoldUltimate&type=client"><span class="glyphicon glyphicon-list-alt"></span> <span class="glyphicon glyphicon-arrow-right"></span> Sage 50</a>';
}

//! SCR# 584 - New coulum Client_ID
if( ! $client_table->client_id() ) {
	unset($sts_result_clients_layout['CLIENT_ID']);
}

if( ! $sts_export_sage50 ) {
	unset($sts_result_clients_layout['SAGE50_CLIENTID']);
}

// close the session here to avoid blocking
session_write_close();

$sts_result_clients_edit['filters_html'] = $filters_html;

$match = $rslt->get_match();

if( $_SESSION['CLIENT_FILTER'] == 'shipper' ) {
	$match .= ($match <> '' ? ' and ' : '') . 'SHIPPER = true';
} else if( $_SESSION['CLIENT_FILTER'] == 'consignee' ) {
	$match .= ($match <> '' ? ' and ' : '') . 'CONSIGNEE = true';
} else if( $_SESSION['CLIENT_FILTER'] == 'dock' ) {
	$match .= ($match <> '' ? ' and ' : '') . 'DOCK = true';
} else if( $_SESSION['CLIENT_FILTER'] == 'bill_to' ) {
	$match .= ($match <> '' ? ' and ' : '') . 'BILL_TO = true';
} else if( $_SESSION['CLIENT_FILTER'] == 'mine' ) {
	if( $sts_cms_salespeople_leads )
		$match .= ($match <> '' ? ' and ' : '') . "(SALES_PERSON = ".$_SESSION['EXT_USER_CODE']." OR CLIENT_TYPE = 'lead' AND COALESCE(SALES_PERSON, 0) = 0)";
	else
		$match .= ($match <> '' ? ' and ' : '') . 'SALES_PERSON = '.$_SESSION['EXT_USER_CODE'];
} else if( $sts_cms_enabled && in_group(EXT_GROUP_MANAGER) ) {
	if( is_array($status_desc) && count($status_desc) > 0 ) {
		foreach( $status_desc as $row ) {
			if( $_SESSION['CLIENT_FILTER'] == $row["STATUS_STATE"] ) {
				$match .= ($match <> '' ? ' and ' : '') . 'CURRENT_STATUS = '.$row["STATUS_CODES_CODE"];
			}
		}
		foreach( $sales_desc as $row ) {
			if( $_SESSION['CLIENT_FILTER'] == 'SALES_'.$row["SALES_PERSON"] ) {
				$match .= ($match <> '' ? ' and ' : '') . 'SALES_PERSON = '.$row["SALES_PERSON"];
			}
		}
	}
}

if( $sts_cms_enabled ) {
	if( $_SESSION['STATE_FILTER'] <> 'all' ) {
		$match .= ($match <> '' ? ' and ' : '') . "STATE = '".$_SESSION['STATE_FILTER']."'";
	}

	if( $my_sales_clients && ! $sts_cms_salespeople_leads ) {
		$match .= ($match <> '' ? ' and ' : '') . "CLIENT_TYPE IN ('prospect', 'client')";
	}
	
	if( $_SESSION['CLIENT_TYPE'] <> 'all' ) {
			$match .= ($match <> '' ? ' and ' : '') . "CLIENT_TYPE = '".$_SESSION['CLIENT_TYPE']."'";
	} 
}

//! SCR# 632 - Export to CSV
if( isset($_GET) && isset($_GET["EXPORT"])) {
	$csv = new sts_csv($client_table, $match, $sts_debug);
	
	$csv->header( "Exspeedite_Client" );

	$csv->render( $sts_result_clients_layout );

	die;
}

$cat = sts_client_activity::getInstance( $exspeedite_db, $sts_debug );
$overdue = $cat->overdue($_SESSION['EXT_USER_CODE']);
if( ! empty($overdue))
	echo $overdue;

echo $rslt->render( $sts_result_clients_layout, $sts_result_clients_edit, false, false );


?>
</div>
	<script language="JavaScript" type="text/javascript"><!--
		
		$(document).ready( function () {
			//alert(($(window).height() - 150) + "px");
			<?php if( ! $sts_debug ) { ?>
			document.documentElement.style.overflow = 'hidden';  // firefox, chrome
			document.body.scroll = "no"; // ie only
			<?php } ?>

			var opts = {
		        //"bLengthChange": false,
		        "bFilter": true,
		        stateSave: true,
		        "bSort": true,
		        "bInfo": true,
				//"bAutoWidth": false,
				//"bProcessing": true, 
				"sScrollX": "100%",
				"sScrollY": ($(window).height() - 270 <?php if( ! empty($overdue)) echo '- 50'; ?>) + "px",
				//"sScrollXInner": "120%",
		        "lengthMenu": [<?php echo isset($sts_length_menu) ? $sts_length_menu : '25, 50, 100, 250'; ?>],
				"bPaginate": true,
				"bScrollCollapse": false,
				"bSortClasses": false,
				"order": [[ 1, "asc" ]],
				"processing": true,
				"serverSide": true,
				//"dom": "frtiS",
				"deferRender": true,
				"ajax": {
					"url": "exp_listclientajax.php",
					"data": function( d ) {
						d.match = encodeURIComponent("<?php echo $match; ?>");
					}

				},
				"columns": [
					{ "searchable": false, "orderable": false },
					<?php
						foreach( $sts_result_clients_layout as $key => $row ) {
							if( $row["format"] <> 'hidden')
								echo '{ "data": "'.$key.'", "searchable": '.
								(isset($row["searchable"]) && $row["searchable"] ? 'true' : 'false').
								(isset($row["align"]) ? ', "className": "text-'.$row["align"].'"' : '').
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
				//! SCR# 446 - highlight clients on credit hold
				"rowCallback": function( row, data ) {
					var onhold = $(row).attr('ON_CREDIT_HOLD');
					if(onhold == 1) {
						$(row).addClass("danger");
					}
				}
						
			};
			
			var myTable = $('#EXP_CLIENT').dataTable(opts);
			$('#EXP_CLIENT').on( 'draw.dt', function () {
				myTable.$('.inform').popover({ 
					placement: 'top',
					html: 'true',
					container: 'body',
					trigger: 'hover',
					delay: { show: 50, hide: 3000 },
					title: '<strong>Information</strong> <button type="button" class="close" data-hide="confirm" data-delay="0" aria-hidden="true">&times;</button>' 
				});

				myTable.$('.confirm').popover({ 
					placement: 'top',
					html: 'true',
					container: 'body',
					trigger: 'hover',
					delay: { show: 50, hide: 3000 },
					title: '<strong>Confirm Action</strong> <button type="button" class="close" data-hide="confirm" data-delay="0" aria-hidden="true">&times;</button>' 
				});
			});
			//myTable.$("a[rel=popover]").popover().click(function(e) {e.preventDefault();});
			
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

