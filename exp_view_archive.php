<?php 

// $Id: exp_view_archive.php 4350 2021-03-02 19:14:52Z duncan $
// View/Restore Archive Files

// Set flag that this is a parent file
define( '_STS_INCLUDE', 1 );

// Setup Session
require_once( "include/sts_session_setup.php" );

require_once( "include/sts_config.php" );
$sts_debug = (isset($_GET['debug'])  || isset($_POST['debug'])) && in_group( EXT_GROUP_DEBUG );
//$sts_debug = isset($_POST) && count($_POST) > 0 ;
require_once( "include/sts_session_class.php" );

$my_session = sts_session::getInstance( $exspeedite_db, $sts_debug );
$my_session->access_check( EXT_GROUP_ADMIN );	// Make sure we should be here
// maybe use $my_session->superadmin()

$sts_subtitle = "View Archive";
if( ! isset($_GET["ZIPDIR"]) ) {
	require_once( "include/header_inc.php" );
	require_once( "include/navbar_inc.php" );	
}

require_once( "include/sts_form_class.php" );
require_once( "include/sts_archive_class.php" );

$archive = sts_archive::getInstance($exspeedite_db, $sts_debug);

function format_size($size) {
	$mod = 1024;
	$units = explode(' ','B KB MB GB TB PB');
	for ($i = 0; $size > $mod; $i++) {
		$size /= $mod;
	}
	return round($size, 2) . ' ' . $units[$i];
}

if( isset($_POST) && count($_POST) > 0 && isset($_FILES["ZIP_FILE_NAME"]) ) {		// Process completed form
	$archive->upload_zip();
	//reload_page('exp_view_archive.php');
}

?>
<div class="container-full theme-showcase" role="main">

<div class="well  well-md">
<?php

$archives = $archive->list_archives();
/*
echo "<pre>";
var_dump($archives);
echo "</pre>";
*/

if( isset($_GET["UPLOAD"]) ) {	//! UPLOAD
	$archive_form = new sts_form( $sts_form_add_archive_zip_form, $sts_form_add_archive_zip_fields, $archive, $sts_debug);
	echo $archive_form->render();

} else
if( isset($_GET["VIEW"]) && isset($_GET["DIR"])) { //! VIEW
?>
<style>
td, th { padding: 5px; }
</style>
<?php
	
	if( is_array($archives) && is_array($archives[$_GET["DIR"]]) &&
		count($archives[$_GET["DIR"]]) > 0 && in_array($_GET["VIEW"], $archives[$_GET["DIR"]])) {
		$fullpath = $archive->get_archive_dir().$_GET["DIR"].'/'.$_GET["VIEW"];
		echo $archive->view_file( $fullpath );
	echo '<br><br><h3 class="text-success"><span class="glyphicon glyphicon-ok"></span> Done <a class="btn btn-sm btn-default" id="archive_restore1" href="exp_view_archive.php?DIR='.$_GET["DIR"].'"><span class="glyphicon glyphicon-arrow-left"></span> Back</a></h3>';
		
	}
} else	
if( isset($_GET["RESTORE"]) && isset($_GET["DIR"])) { //! RESTORE
	if( is_array($archives) && is_array($archives[$_GET["DIR"]]) &&
		count($archives[$_GET["DIR"]]) > 0 && in_array($_GET["RESTORE"], $archives[$_GET["DIR"]])) {
		echo '<h2 class="tighter-top"><span class="glyphicon glyphicon-import"></span> Restore Archive File: '.$_GET["RESTORE"].'</h2>
		';
		
		$fullpath = $archive->get_archive_dir().$_GET["DIR"].'/'.$_GET["RESTORE"];
		$result = $archive->restore_file( $fullpath );
	echo '<br><br><h3 class="text-'.
		($result ? 'success"><span class="glyphicon glyphicon-ok"></span> Done' :
		'danger"><span class="glyphicon glyphicon-warning-sign"></span> Error '.$archive->get_last_error()).
		' <a class="btn btn-sm btn-default" id="archive_restore1" href="exp_view_archive.php?DIR='.$_GET["DIR"].'"><span class="glyphicon glyphicon-arrow-left"></span> Back</a></h3>';
		
	}
} else	
if( isset($_GET["VERIFY"]) && isset($_GET["DIR"])) { //! VERIFY
	if( is_array($archives) && is_array($archives[$_GET["DIR"]]) &&
		count($archives[$_GET["DIR"]]) > 0 && in_array($_GET["VERIFY"], $archives[$_GET["DIR"]])) {
		echo '<h2 class="tighter-top"><span class="glyphicon glyphicon-import"></span> Verify Archive File: '.$_GET["VERIFY"].'</h2>
		';
		
		$fullpath = $archive->get_archive_dir().$_GET["DIR"].'/'.$_GET["VERIFY"];
		$result = $archive->verify_file( $fullpath );
	echo '<br><h4 class="text-'.
		($result ? 'success"><span class="glyphicon glyphicon-ok"></span> Verified OK' :
		'danger"><span class="glyphicon glyphicon-warning-sign"></span> Error '.$archive->get_last_error()).
		' <a class="btn btn-sm btn-default" id="archive_restore1" href="exp_view_archive.php?DIR='.$_GET["DIR"].'"><span class="glyphicon glyphicon-arrow-left"></span> Back</a></h4>';
		
	}
} else	
if( isset($_GET["DELETEDIR"]) && isset($_GET["DIR"])) { //! DELETEDIR
	$result = false;
	if( is_array($archives) && is_array($archives[$_GET["DIR"]]) ) {
		echo '<h2 class="tighter-top"><span class="glyphicon glyphicon-remove"></span> Delete Archive: '.$_GET["DIR"].'</h2>
		';
		
		$fullpath = $archive->get_archive_dir().$_GET["DIR"];
		if( count($archives[$_GET["DIR"]]) > 0 ) {
			array_map('unlink', glob($fullpath."/*.json"));
		}
		$result = rmdir($fullpath); // TBD - try
	}
	
	echo '<br><br><h3 class="text-'.
		($result ? 'success"><span class="glyphicon glyphicon-ok"></span> Done' :
		'danger"><span class="glyphicon glyphicon-warning-sign"></span> Error').
		' <a class="btn btn-sm btn-default" id="archive_restore1" href="exp_view_archive.php"><span class="glyphicon glyphicon-arrow-left"></span> Back</a></h3>';
} else	
if( isset($_GET["RESTOREALL"]) && isset($_GET["DIR"])) { //! RESTOREALL
	if( is_array($archives) && is_array($archives[$_GET["DIR"]]) && count($archives[$_GET["DIR"]]) > 0 ) {
		// Turn off output buffering
		ini_set('output_buffering', 'off');
		// Implicitly flush the buffer(s)
		ini_set('implicit_flush', true);
		ob_implicit_flush(true);
	
		if (ob_get_level() == 0) ob_start();
		echo '<h2 class="tighter-top"><span class="glyphicon glyphicon-import"></span> Restore Archive: '.$_GET["DIR"].'</h2>
		<h3>Restore '.count($archives[$_GET["DIR"]]).' Files</h3>
		';
		ob_flush(); flush();
		
		foreach($archives[$_GET["DIR"]] as $file) {
			$fullpath = $archive->get_archive_dir().$_GET["DIR"].'/'.$file;
			echo $file.'<br>';
			ob_flush(); flush();
			if( ! $archive->restore_file( $fullpath ) )
				echo $archive->get_last_error().'<br>';
		}
	}
	
	echo '<br><br><h3 class="text-success"><span class="glyphicon glyphicon-ok"></span> Done <a class="btn btn-sm btn-default" id="archive_restore1" href="exp_view_archive.php"><span class="glyphicon glyphicon-arrow-left"></span> Back</a></h3>';

} else	
if( isset($_GET["DIR"])) { //! DIR
	echo '<h2 class="tighter-top"><span class="glyphicon glyphicon-import"></span> Archive Directory '.$_GET["DIR"].': '.
	(is_array($archives) && is_array($archives[$_GET["DIR"]]) && count($archives[$_GET["DIR"]]) > 0 ? count($archives[$_GET["DIR"]]) : 'No').' Files <a class="btn btn-sm btn-default" id="archive_view2" href="exp_view_archive.php"><span class="glyphicon glyphicon-arrow-left"></span> Back</a></h2>
';
	if( is_array($archives) && is_array($archives[$_GET["DIR"]]) && count($archives[$_GET["DIR"]]) > 0 ) {
		$sizes = $archive->archive_size( $_GET["DIR"] );
		$gotschema = false;
		foreach( $archives[$_GET["DIR"]] as $archive_file ) {
			$fullpath = $archive->get_archive_dir().$_GET["DIR"].'/'.$archive_file;
			list($loads, $shipments, $schema, $range) = $archive->file_contents($fullpath);
			$msg = " this ".(count($loads) == 1 ? "load" : "shipment");
			if( ! $gotschema && ! empty($schema) ) {
				echo '<p>Schema: '.$schema.' Range: '.$range.'</p>';
				$gotschema = true;
			}
			
			echo '<div class="row">
			<div class="col-sm-3"><a class="btn btn-sm btn-default tip" title="Click here to view the contents of'.$msg.'" href="exp_view_archive.php?DIR='.$_GET["DIR"].'&VIEW='.$archive_file.'"><span class="glyphicon glyphicon-eye-open"></span> View <strong>'.$archive_file.'</strong></a>'.'<br></div>
			<div class="col-sm-1 text-right">'.implode(', ', $loads).'</div>
			<div class="col-sm-3 text-right">'.implode(', ', $shipments).'</div>
			<div class="col-sm-2 text-right">'.format_size($sizes[$archive_file]).
			'</div><div class="col-sm-1"><a class="btn btn-sm btn-danger tip" title="Click here to restore just this'.$msg.'" href="exp_view_archive.php?RESTORE='.$archive_file.'&DIR='.$_GET["DIR"].'"><span class="glyphicon glyphicon-import"></span> Restore</a></div>
			<div class="col-sm-1"><a class="btn btn-sm btn-default tip" title="Compare the archive file against the database" href="exp_view_archive.php?VERIFY='.$archive_file.'&DIR='.$_GET["DIR"].'"><span class="glyphicon glyphicon-search"></span> Verify</a></div>
			</div>
			';
		}
	}

} else { //! Top Level
	echo '<h2 class="tighter-top"><span class="glyphicon glyphicon-import"></span> '.(is_array($archives) && count($archives) > 0 ? count($archives) : 'No').' Archive Directories <a class="btn btn-sm btn-success" href="exp_view_archive.php"><span class="glyphicon glyphicon-refresh"></span> Refresh</a> <a class="btn btn-sm btn-danger tip" title="This will create an archive for a date range and REMOVE the shipments and loads" id="add_archive" href="exp_add_archive.php"><span class="glyphicon glyphicon-export"></span> Create Archive</a> <a class="btn btn-sm btn-warning tip" title="This will upload a ZIP file containing an archive" id="add_zip" href="exp_view_archive.php?UPLOAD"><span class="glyphicon glyphicon-import"></span> Upload ZIP</a> <a class="btn btn-sm btn-default tip" title="This will get you safely back to the home page" id="archive_view3" href="index.php"><span class="glyphicon glyphicon-arrow-left"></span> Back</a></h2>
	';
	
	if( is_array($archives) && count($archives) > 0 ) {
		foreach( $archives as $archive_dir => $archive_files ) {
						
			echo '<div class="row">
			<div class="col-sm-5"><a class="btn btn-sm btn-default tip" title="This will show you the contrents of the archive" href="exp_view_archive.php?DIR='.$archive_dir.'"><span class="glyphicon glyphicon-eye-open"></span> View <strong>'.$archive_dir.'</strong></a>&nbsp;&nbsp;'.$archive->get_range( $archive_dir ).'
			</div>
			<div class="col-sm-1 text-right">'.
			(is_array($archive_files) && count($archive_files) > 0 ? count($archive_files) : 'no').
			' files</div>
			<div class="col-sm-2 text-right">'.(is_array($archive_files) && count($archive_files) > 0 ? format_size(array_sum($archive->archive_size( $archive_dir ))) : '0B').
			'</div>
			<div class="col-sm-2">'.
			(is_array($archive_files) && count($archive_files) > 0 ? ' <a class="btn btn-sm btn-danger tip" title="This will restore all the shipments and loads in the archive" href="exp_view_archive.php?RESTOREALL&DIR='.$archive_dir.'"><span class="glyphicon glyphicon-import"></span> Restore All</a>' : ' <a class="btn btn-sm btn-danger" href="exp_view_archive.php?DELETEDIR&DIR='.$archive_dir.'"><span class="glyphicon glyphicon-remove"></span> Empty</a>').
			'</div>
			<div class="col-sm-2">'.
			'<a class="btn btn-sm btn-danger tip" title="Click here to download a ZIP file of the archive for your safekeeping" href="exp_zip_archive.php?PW=Gober&DIR='.
				$archive_dir.'"><span class="glyphicon glyphicon-download-alt"></span> Zip</a>'.(! ctype_digit($archive_dir[0]) || $my_session->superadmin() ? ' <a class="btn btn-sm btn-danger tip" title="Delete uploaded ZIP file" href="exp_view_archive.php?DELETEDIR&DIR='.$archive_dir.'"><span class="glyphicon glyphicon-remove"></span></a>' : '').'
			</div>
			</div>
			';
		}
			echo '<div class="row">
			<div class="col-sm-10 bg-info">
			<h4>Archive directories are stored in '.$archive->get_archive_dir().'</h4>
			<p>You can change this via setting <strong>option/ARCHIVE_DIR</strong></p>
			</div>
			</div>
			<div class="row">
				<div class="col-sm-1">
					<img src="images/skull-crossbones.png" alt="skull-crossbones" width="83" height="84" />
				</div>
				<div class="col-sm-8">
					<p class="text-danger"> Disclaimer: Archiving <strong>REMOVES loads and shipments and all the related information</strong> and puts it all in a text file. While we do everything to ensure any archived data is safe, there is some RISK. Try at your peril, here be dragons - arrrgh!</p>
				</div>
				<div class="col-sm-1">
					<img src="images/skull-crossbones.png" alt="skull-crossbones" width="83" height="84" />
				</div>
			</div>';
	}
}

require_once( "include/footer_inc.php" );

?>