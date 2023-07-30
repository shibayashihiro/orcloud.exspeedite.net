<?php 
// Set flag that this is a parent file
define( '_STS_INCLUDE', 1 );

// Setup Session
require_once( "include/sts_session_setup.php" );

require_once( "include/sts_config.php" );
$sts_debug = isset($_GET['debug']) && in_group( EXT_GROUP_DEBUG );
require_once( "include/sts_session_class.php" );

$my_session = sts_session::getInstance( $exspeedite_db, $sts_debug );
$my_session->access_check( $sts_table_access[USER_TABLE] );	// Make sure we should be here

$sts_subtitle = "List Users";
require_once( "include/header_inc.php" );
require_once( "include/navbar_inc.php" );

?>
<div class="container-full" role="main">
<?php
require_once( "include/sts_result_class.php" );
require_once( "include/sts_user_class.php" );
require_once( "include/sts_setting_class.php" );

$setting_table = sts_setting::getInstance($exspeedite_db, $sts_debug);
$sts_length_menu = $setting_table->get( 'option', 'LENGTH_MENU' );

if( ! isset($_SESSION['USER_ISACTIVE']) ) $_SESSION['USER_ISACTIVE'] = 'Active';
if( isset($_POST['USER_ISACTIVE']) ) $_SESSION['USER_ISACTIVE'] = $_POST['USER_ISACTIVE'];

$filters_html = '<div class="btn-group"><a class="btn btn-sm btn-success" href="exp_listuser.php"><span class="glyphicon glyphicon-refresh"></span></a>';

$valid_sources = ['All', 'Inactive','Active','OOS'];

if( $valid_sources ) {
	$filters_html .= '<select class="form-control input-sm" name="USER_ISACTIVE" id="USER_ISACTIVE"   onchange="form.submit();">';
	foreach( $valid_sources as $source ) {
		$filters_html .= '<option value="'.$source.'" '.($_SESSION['USER_ISACTIVE'] == $source ? 'selected' : '').'>'.$source.'</option>
		';
	}
	$filters_html .= '</select>';
}

$sts_result_users_edit['filters_html'] = $filters_html;

$user_table = sts_user::getInstance($exspeedite_db, $sts_debug);
$rslt = new sts_result( $user_table, false, $sts_debug );

$match = $rslt->get_match();
if( $_SESSION['USER_ISACTIVE'] <> 'All')
	$match = ($match <> '' ? $match." AND " : "")."ISACTIVE = '".$_SESSION['USER_ISACTIVE']."'";
	
echo $rslt->render( $sts_result_users_layout, $sts_result_users_edit, false, false );


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
				"sScrollY": ($(window).height() - 275) + "px",
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
					"url": "exp_listuserajax.php",
					"data": function( d ) {
						d.match = encodeURIComponent("<?php echo $match; ?>");
					}

				},
				"columns": [
					{ "searchable": false, "orderable": false },
					<?php
						foreach( $sts_result_users_layout as $key => $row ) {
							if( $row["format"] <> 'hidden')
								echo '{ "data": "'.$key.'",
									"searchable": true, "orderable": true },';
						}
					?>
				]
						
			};
			
			var myTable = $('#EXP_USER').dataTable(opts);
			$('#EXP_USER').on( 'draw.dt', function () {
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

