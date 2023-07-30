<?php 
// Set flag that this is a parent file
define( '_STS_INCLUDE', 1 );

// Enable datatables buttons
define( '_STS_BUTTONS', 1 );

// Enable datatables select
define( '_STS_SELECT', 1 );

// Enable datatables editor
define( '_STS_EDITOR', 1 );

// Setup Session
require_once( "include/sts_session_setup.php" );

require_once( "include/sts_config.php" );
$sts_debug = isset($_GET['debug']) && in_group( EXT_GROUP_DEBUG );
require_once( "include/sts_session_class.php" );

$my_session = sts_session::getInstance( $exspeedite_db, $sts_debug );
$my_session->access_check( EXT_GROUP_USER );	// Make sure we should be here

$sts_subtitle = "IFTA Log";
require_once( "include/header_inc.php" );
require_once( "include/navbar_inc.php" );

?>
<div class="container" role="main">
<?php
require_once( "include/sts_result_class.php" );
require_once( "include/sts_ifta_log_class.php" );
require_once( "include/sts_ifta_rate_class.php" );
require_once( "include/sts_tractor_class.php" );
require_once( "include/sts_setting_class.php" );

$setting_table = sts_setting::getInstance($exspeedite_db, $sts_debug);
$sts_length_menu = $setting_table->get( 'option', 'LENGTH_MENU' );
$sts_ifta_base = $setting_table->get( 'api', 'IFTA_BASE_JURISDICTION' );
$ifta_enabled = ($setting_table->get("api", "IFTA_ENABLED") == 'true');
$multi_company = ($setting_table->get("option", "MULTI_COMPANY") == 'true');

if( $ifta_enabled ) {
	$ifta_log = sts_ifta_log::getInstance( $exspeedite_db, $sts_debug );
	$ifta_rate = sts_ifta_rate::getInstance( $exspeedite_db, $sts_debug );
	$tractor_table = sts_tractor::getInstance($exspeedite_db, $sts_debug);
	
	if( isset($_GET['TRACTOR_CODE']) ) $_POST['TRACTOR_CODE'] = $_GET['TRACTOR_CODE'];
	
	//! Make sure IFTA logging is enabled for this tractor
	$check = $tractor_table->fetch_rows($tractor_table->primary_key." = ".$_POST['TRACTOR_CODE'], "LOG_IFTA, UNIT_NUMBER");

	if( is_array($check) && count($check) == 1 &&
		isset($check[0]["LOG_IFTA"]) && $check[0]["LOG_IFTA"]) {
	
		$sts_result_ifta_log_edit['filters_html'] = '<div class="form-group">'.
			$tractor_table->tractor_menu( $_POST['TRACTOR_CODE'] ).
			'<a class="btn btn-sm btn-info" href="exp_listifta_log.php"><span class="glyphicon glyphicon-usd"></span> Quarterly Report</a><a class="btn btn-sm btn-success" href="exp_edittractor.php?CODE='.$_POST['TRACTOR_CODE'].'"><span class="glyphicon glyphicon-edit"></span> Edit Tractor</a><a class="btn btn-sm btn-default" href="exp_listtractor.php"><span class="glyphicon glyphicon-remove"></span> List Tractors</a></div>';
		
		$rslt = new sts_result( $ifta_log, "IFTA_TRACTOR = ".$_POST['TRACTOR_CODE'], $sts_debug );
		echo $rslt->render( $sts_result_ifta_log_layout, $sts_result_ifta_log_edit, false, false );
		$match = "IFTA_TRACTOR = ".$_POST['TRACTOR_CODE'];
	} else {
		echo '<div class="well well-sm">
		<h3 class="text-danger"><img src="images/iftatest.gif" alt="iftatest" height="40"> <span class="glyphicon glyphicon-warning-sign"></span> IFTA Logging Not Enabled For '.$check[0]["UNIT_NUMBER"].'</h3>
		<p>You need to edit the tractor profile to enable IFTA logging.</p>
		<p class="text-center"><a class="btn btn-sm btn-default" id="EXP_TRACTOR_cancel"
		href="exp_edittractor.php?CODE='.$_POST['TRACTOR_CODE'].'"><span class="glyphicon glyphicon-edit"></span> Edit Tractor '.$check[0]["UNIT_NUMBER"].'</a>
		</div>';
	}
?>
</div>
	<script language="JavaScript" type="text/javascript"><!--
		
		var editor; // use a global for the submit and return data rendering in the examples

		$(document).ready( function () {
			//alert(($(window).height() - 150) + "px");
			<?php if( ! $sts_debug ) { ?>
			document.documentElement.style.overflow = 'hidden';  // firefox, chrome
			document.body.scroll = "no"; // ie only
			<?php } ?>

			editor = new $.fn.dataTable.Editor( {
				ajax: "exp_editifta_ajax.php",
				table: "#EXP_IFTA_LOG",
				idSrc: "DT_RowAttr.IFTA_LOG_CODE",
				formOptions: {
					main: {
						focus: null
					}
				},
				fields: [ 
					<?php
						$lines = array();
						foreach( $sts_result_ifta_log_layout as $key => $row ) {
							if( $key <> 'NULL' && $key <> 'CD_ORIGIN' && $row["format"] <> 'hidden')
								$lines[] = '{ label: "'.$row['label'].'", '.
					($key == 'IFTA_DATE' ? 'type: "datetime",
						def:    function () { return new Date(); }, ' : '').
					(isset($row["value"]) ? 'def: '.$row["value"].', ' : '').
					($key == 'IFTA_JURISDICTION' ? $ifta_rate->jurisdiction_menu().
						' def: "'.$sts_ifta_base.'",' : '').
								'name: "'.$key.'" }';
						}
						echo implode(",\n", $lines);
					?>,
					{ type: "hidden", name: "IFTA_TRACTOR", default: <?php echo $_POST['TRACTOR_CODE']; ?> }
				]
			} );
		
			// Activate an inline edit on click of a table cell
			$('#EXP_IFTA_LOG').on( 'click', 'tbody td:not(:first-child)', function (e) {
				editor.inline( this, {
					onBlur: 'submit',
					onReturn: 'submit'
					//this.find('select').one( 'change', this.submit );
				} );
			} );
			
			
			editor.on( 'open', function ( e, json, data ) {
				console.log('open', $( editor.field('IFTA_DATE').input()) );
				$( editor.node('IFTA_JURISDICTION')).on( 'change', function () {
					if( editor.field('IFTA_JURISDICTION').val() !=
						editor.field('IFTA_JURISDICTION').s.opts._lastSet &&
						editor.s.displayed == "inline"
						) {
						editor.submit();
					}
				});
				/*
				var prev = editor.field('IFTA_DATE').val();
				$( editor.field('IFTA_DATE').input()).on( 'change', function () {
					console.log('change', editor.field('IFTA_DATE').val(), prev);
				});
				$( editor.field('IFTA_DATE').input()).on( 'keyup', function () {
					console.log('keyup', editor.field('IFTA_DATE').val(), prev);
				});
				$( editor.field('IFTA_DATE').input()).on( 'mouseup', function () {
					console.log('mouseup', editor.field('IFTA_DATE').val(), prev);
				});
				$( editor.field('IFTA_DATE').input()).on( 'click', function () {
					console.log('click', editor.field('IFTA_DATE').val(), prev);
				});
				*/
			} );
					//console.log('change', editor.field('IFTA_JURISDICTION').val(), editor.field('IFTA_JURISDICTION').s.opts._lastSet );

			//editor.on( 'initEdit', function ( e, json, data ) {
			//	console.log('initEdit');
			//} );
			
			function update_distance ( myValue, data) {
				var miles = parseInt(data.values.ODOMETER_OUT) - parseInt(data.values.ODOMETER_IN);
				if(! isNaN(miles))
					editor.field('DIST_TRAVELED').val( miles );
			}
			
			editor.dependent( 'ODOMETER_IN', update_distance );
			editor.dependent( 'ODOMETER_OUT', update_distance );

			var opts = {
		        //"bLengthChange": false,
		        "bFilter": true,
		        stateSave: true,
		        "stateLoadParams": function (settings, data) {
			        data.order = [[ 1, "asc" ]];
			    },
		        "bSort": true,
		        "bInfo": true,
				//"bAutoWidth": false,
				//"bProcessing": true, 
				//"sScrollX": "100%",
				"sScrollY": ($(window).height() - 340) + "px",
				//"sScrollXInner": "120%",
		        "lengthMenu": [<?php echo isset($sts_length_menu) ? $sts_length_menu : '25, 50, 100, 250'; ?>],
				"bPaginate": true,
				"bScrollCollapse": false,
				"bSortClasses": false,
				"order": [[ 1, "asc" ]],
				"processing": true,
				"serverSide": true,
				"dom": "Bfrtip",
				"deferRender": true,
				"ajax": {
					"url": "exp_editifta_ajax.php",
					"data": function( d ) {
						d.match = encodeURIComponent("<?php echo $match; ?>");
					}
				},
				select: {
					style:    'os',
					selector: 'td:first-child'
				},
				buttons: [
					{ extend: "create", editor: editor },
					{ extend: "edit",   editor: editor },
					{ extend: "remove", editor: editor },
					'csv', 'print'
				],
				
				"rowCallback": function( row, data ) {
					console.log(data);
					if ( $.isNumeric(data.CD_ORIGIN) ) {
						$('td:eq(9)', row).html( '<a href="exp_viewload.php?CODE='+data.CD_ORIGIN+'">'+data.CD_ORIGIN+'</a>' );
					}
				},

				rowId: "DT_RowAttr.IFTA_LOG_CODE",
				"columns": [{
								data: null,
								defaultContent: '',
								className: 'select-checkbox',
								orderable: false
							},
					<?php
						$lines = array();
						foreach( $sts_result_ifta_log_layout as $key => $row ) {
							/*if( $key == 'NULL' )
								$lines[] = "";
							else */
							if( $key <> 'NULL' && $row["format"] <> 'hidden')
								$lines[] = '{ "data": "'.$key.'", '.
								(! empty($row["align"]) ? 'className: "text-'.$row["align"].'", ' : '').
								'"searchable": true, "orderable": true }
						';
						}
						echo implode(",\n", $lines);
					?>
				]
			};
			
			var myTable = $('#EXP_IFTA_LOG').DataTable(opts);

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
} else {
?>

<div class="container" role="main">
	<div class="alert alert-danger alert-dismissable">
	  <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
	  <h2><span class="glyphicon glyphicon-warning-sign"></span> IFTA Logging Is Not Enabled</h2>
		<p>Please contact the folks at Exspeedite if you require this option.</p>
		<p>Otherwise please select from the menu above.</p>
	</div>
</div>

<?php
}
require_once( "include/footer_inc.php" );
?>

