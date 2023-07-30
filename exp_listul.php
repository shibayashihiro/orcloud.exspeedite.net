<?php 

// $Id: exp_listul.php 4350 2021-03-02 19:14:52Z duncan $
// List user log

// Set flag that this is a parent file
define( '_STS_INCLUDE', 1 );

// Setup Session
require_once( "include/sts_session_setup.php" );

require_once( "include/sts_config.php" );
$sts_debug = isset($_GET['debug']) && in_group( EXT_GROUP_DEBUG );
require_once( "include/sts_session_class.php" );

$my_session = sts_session::getInstance( $exspeedite_db, $sts_debug );
$my_session->access_check( $sts_table_access[USER_LOG_TABLE] );	// Make sure we should be here

$sts_subtitle = "User Events";
require_once( "include/header_inc.php" );
require_once( "include/navbar_inc.php" );

?>
<div class="container-full" role="main">
<?php
require_once( "include/sts_result_class.php" );
require_once( "include/sts_user_log_class.php" );
require_once( "include/sts_setting_class.php" );

$user_log_table = sts_user_log::getInstance($exspeedite_db, $sts_debug);
$setting_table = sts_setting::getInstance($exspeedite_db, $sts_debug);
$sts_length_menu = $setting_table->get( 'option', 'LENGTH_MENU' );

if( isset($_POST['LOG_USER']) ) $_SESSION['LOG_USER'] = $_POST['LOG_USER'];
if( ! isset($_SESSION['LOG_USER']) ) $_SESSION['LOG_USER'] = 0;

if( isset($_POST['LOG_EVENT']) ) $_SESSION['LOG_EVENT'] = $_POST['LOG_EVENT'];
if( ! isset($_SESSION['LOG_EVENT']) ) $_SESSION['LOG_EVENT'] = 'all';

$filters_html = '<div class="btn-group"><a class="btn btn-sm btn-success" href="exp_listul.php"><span class="glyphicon glyphicon-refresh"></span></a>';

$filters_html .=
	$user_log_table->user_menu( $_SESSION['LOG_USER'], 'LOG_USER', '', true, true );

$filters_html .=
	$user_log_table->event_menu( $_SESSION['LOG_EVENT'], 'LOG_EVENT', '', true, true );

$filters_html .= '</div>';

$who = $my_session->who();
if( $who <> '')
	echo '<p class="text-right" id="online">Online: '.$who.'</p>';
if( ! isset($_SESSION['EXT_WHO'] ) )
	$_SESSION['EXT_WHO'] = true;


$sts_result_user_log_edit['filters_html'] = $filters_html;

$match = false;

if( $_SESSION['LOG_USER'] > 0 ) {
	$match = "USER_CODE = ".$_SESSION['LOG_USER'];
}

if( $_SESSION['LOG_EVENT'] <> 'all' ) {
	$match = ($match <> false ? $match." AND " : "")."LOG_EVENT = '".$_SESSION['LOG_EVENT']."'";
}

$rslt = new sts_result( $user_log_table, $match, $sts_debug );

echo $rslt->render( $sts_result_user_log_layout, $sts_result_user_log_edit, false, false );


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
		        //stateSave: true,
		        "bSort": true,
		        "bInfo": true,
				"bAutoWidth": false,
				//"bProcessing": true, 
				"sScrollX": "100%",
				"sScrollY": ($(window).height() - (265 + $('#online').height())) + "px",
				//"sScrollXInner": "120%",
		        "lengthMenu": [<?php echo isset($sts_length_menu) ? $sts_length_menu : '25, 50, 100, 250'; ?>],
				"bPaginate": true,
				"bScrollCollapse": false,
				"bSortClasses": false,
				"order": [[ 0, "desc" ]],
				"processing": true,
				"serverSide": true,
				//"dom": "frtiS",
				"deferRender": true,
				"ajax": {
					"url": "exp_listulajax.php",
					"data": function( d ) {
						d.match = encodeURIComponent("<?php echo $match; ?>");
					}

				},
				"columns": [
					<?php
						foreach( $sts_result_user_log_layout as $key => $row ) {
							if( $row["format"] <> 'hidden')
								echo '{ "data": "'.$key.'", "searchable": '.
								(isset($row["searchable"]) && ! $row["searchable"] ? 'false' : 'true' ).
								(isset($row["align"]) ? ', "className": "text-'.$row["align"].'"' : '').
									(isset($row["length"]) ? ', "width": "'.$row["length"].'px"' : '').
									(isset($row["format"]) && $row["format"] == 'hidden' ? ', "visible": false' : '').' },
						';
						}
					?>
				],
						
			};
			
			var myTable = $('#EXP_USER_LOG').dataTable(opts);
			$('#EXP_USER_LOG').on( 'draw.dt', function () {
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

