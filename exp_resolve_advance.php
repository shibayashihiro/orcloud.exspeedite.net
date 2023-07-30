<?php 

// $Id: exp_resolve_advance.php 3884 2020-01-30 00:21:42Z duncan $
// - Resolve Cash Advances to drivers

// Set flag that this is a parent file
define( '_STS_INCLUDE', 1 );

// Setup Session
require_once( "include/sts_session_setup.php" );

require_once( "include/sts_config.php" );
$sts_debug = (isset($_GET['debug'])  || isset($_POST['debug'])) && in_group( EXT_GROUP_DEBUG );
require_once( "include/sts_session_class.php" );

$my_session = sts_session::getInstance( $exspeedite_db, $sts_debug );
$my_session->access_check( $sts_table_access[CARD_FTP_TABLE] );	// Make sure we should be here

require_once( "include/sts_card_class.php" );
require_once( "include/sts_card_ftp_class.php" );

if( $sts_debug ) {
	echo "<pre>POST, GET variables\n";
	var_dump($_POST);
	var_dump($_GET);
	echo "</pre>";
}

if( isset($_POST['SOURCE']) && isset($_POST['CARD_NUM']) && isset($_POST['DRIVER_CODE']) ) {
	echo '<br><br><center><p class="text-center"><img src="images/loading.gif" alt="loading" width="125" height="125" /></p></center>';
	$ftp = sts_card_ftp::getInstance($exspeedite_db, $sts_debug);
	$card = sts_card::getInstance($exspeedite_db, $sts_debug, $ftp );

	if( isset($_POST['save']) && isset($_POST['DRIVER_CODE'])) {
		$card->resolve_advance(  $_POST['ADVANCE_CODE'], $_POST['SOURCE'], $_POST['CARD_NUM'], $_POST['DRIVER_CODE'] );
	} else if( isset($_POST['discard']) ) {
		$card->discard_advance( $_POST['ADVANCE_CODE'], $_POST['SOURCE'], $_POST['CARD_NUM'] );
	} else if( isset($_POST['discard_all']) ) {
		$card->discard__all_advance( $_POST['CARD_NUM'] );
	}
	if( ! $sts_debug )
		reload_page ( "exp_resolve_advance.php?RESOLVE" );

} else if( isset($_GET['RESOLVE']) ) {//! RESOLVE - Resolve card
	$card = sts_card::getInstance($exspeedite_db, $sts_debug);

	if( $check = $card->find_card_advance_to_resolve() ) {
		$sts_subtitle = "Resolve ".$check['SOURCE'].' / '.$check['CARD_NUM']." to Driver";
		require_once( "include/header_inc.php" );
		require_once( "include/navbar_inc.php" );
		
		?>
		<div class="container" role="main">
		<?php
		
		echo '<div class="well well-md">
				<h3>Data To Resolve From '.$check['SOURCE'].'</h3>
				'.$card->dump_advance_row( $check ).'
				<form role="form" class="form-horizontal" action="exp_resolve_advance.php" 
				method="post" enctype="multipart/form-data" 
				name="carrier" id="resolve">
				<input name="ADVANCE_CODE" id="ADVANCE_CODE" type="hidden" value="'.$check['ADVANCE_CODE'].'">
				<input name="SOURCE" id="SOURCE" type="hidden" value="'.$check['SOURCE'].'">
				<input name="CARD_NUM" id="CARD_NUM" type="hidden" value="'.$check['CARD_NUM'].'">
				'.($sts_debug ? '<input name="debug" type="hidden" value="on">' : '').'
				
						';
		if( $check['MENU'] === false ) {
			echo '<h3 class="text-danger"><span class="glyphicon glyphicon-warning-sign"></span> No Available Drivers To Match This Data</h3>
				<div class="row">
				<div class="col-sm-12">
					<h4>This is likely because you have not defined your drivers in Exspeedite. <br>Go to <a href="exp_listdriver.php" class="btn btn-sm btn-success">Driver Profiles</a> and add your drivers. Then come back to resolve this data.</h4>
					<div class="btn-group" role="group">
						<button class="btn btn-md btn-danger" name="discard" type="submit" ><span class="glyphicon glyphicon-remove"></span> Discard Data</button>
						<a class="btn btn-md btn-default" href="exp_list_card_ftp.php?DL='.$check['CD_ORIGIN'].'"><span class="glyphicon glyphicon-remove"></span> Back</a>
				</div>
				</div>
			';
		} else {	
				
			echo '<h3>Which driver matches card# '.$check['CARD_NUM'].' ('.
				$check["DRIVER_NAME"].')</h3>
			<div class="form-group">
				<div class="col-sm-4">
			'.$check['MENU'].'
				</div>
				<div class="col-sm-8">
					<div class="btn-group" role="group">
						<button class="btn btn-md btn-success" name="save" type="submit" ><span class="glyphicon glyphicon-ok"></span> Assign Driver</button>
						<button class="btn btn-md btn-danger" name="discard" id="discard"
						type="submit"><span class="glyphicon glyphicon-remove"></span> Discard Data</button>
						<button class="btn btn-md btn-danger" name="discard_all" id="discard_all" type="submit" ><span class="glyphicon glyphicon-remove"></span> Discard ALL Data for '.$check['CARD_NUM'].'</button>
						<a class="btn btn-md btn-default" href="exp_list_card_ftp.php?DL='.$check['SOURCE'].'"><span class="glyphicon glyphicon-remove"></span> Back</a>
					</div>
				</div>
				';
			}
			echo '</div>
			</form>
			</div>
			
	<script language="JavaScript" type="text/javascript"><!--
		$(document).ready( function () {

		    $("#discard, #discard_all").click(function (e) {
		    	e.preventDefault();
		    	var missing = this.id;
		        bootbox.confirm({
					title: \'<h3><img src="images/EXSPEEDITEsmr.png" height="32" alt="<?php echo $sts_title ?>"> Confirm Action</h3>\',
					message: "Are you sure you want to discard cash advance data?<br>There is no undo.",
					closeButton: false,
					buttons: {
						confirm: {
							label: \'<span class="glyphicon glyphicon-ok"></span> Confirm\',
							className: \'btn-danger\',
						},
						cancel: {
							label: \'<span class="glyphicon glyphicon-remove"></span> Cancel\',
							className: \'btn-default\'
						}
					},
					callback: function(result) {
			            if (result) {
				            $("<input />").attr("type", "hidden")
						        .attr("name", missing)
						        .attr("value", missing)
						        .appendTo("#resolve");
				            $("#resolve").submit();
			            }
					}
		        });
		    });

		});
	//--></script>


			';
		
	} else {
		// Nothing to resolve
		reload_page ( "exp_list_card_ftp.php" );
	}
	
}

require_once( "include/footer_inc.php" );
?>



