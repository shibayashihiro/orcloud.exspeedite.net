<?php

// $Id: header_inc.php 4697 2022-03-09 23:02:23Z duncan $
// Header inclulde, includes all botstrap and other css and js

// no direct access
defined('_STS_INCLUDE') or die('Restricted access');

// Used for paths to css, js etc. Redifine if you are not in the normal directory
if( ! defined( 'EXP_RELATIVE_PATH') )
	define( 'EXP_RELATIVE_PATH', '' );

if (!headers_sent() && ! defined('_STS_SKIP_HEADERS') ) {
	header("Cache-Control: no-cache, must-revalidate"); // HTTP/1.1
	header("Expires: Sat, 26 Jul 1997 05:00:00 GMT"); // Date in the past
}

// If debug enabled, include error handler for stack trace.
if( isset($_GET['trace']) && in_group( EXT_GROUP_DEBUG ) ) {
	require( EXP_RELATIVE_PATH.'include/php_error.php' );
	\php_error\reportErrors();
}

?>
<!DOCTYPE html>
<html lang="en">
	<head>
<?php if( ! defined('_STS_SKIP_THIS') ) { ?>
		<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
<?php } ?>
		<meta http-equiv="X-UA-Compatible" content="IE=edge">
		<meta name="viewport" content="width=device-width, initial-scale=1">
		<meta name="description" content="Exspeedite Moble Trucking Software">
		<meta name="author" content="Strong Tower Software LLC">
		<meta name="robots" content="noindex, nofollow">
		<link rel="shortcut icon" href="<?php echo EXP_RELATIVE_PATH; ?>images/EXSPEEDITE_favicon.png">
		
		<title><?php echo $sts_title." - ".$sts_subtitle; ?></title>
		
		<!-- Bootstrap core CSS -->
		<link type="text/css" href="<?php echo EXP_RELATIVE_PATH; ?>dist/css/bootstrap.min.css" rel="stylesheet">
		<!-- Datepicker -->
		<link type="text/css" href="<?php echo EXP_RELATIVE_PATH; ?>alt-datetimepicker/bootstrap-datetimepicker.css" rel="stylesheet">
		<!-- Bootstrap theme -->
		<link type="text/css" href="<?php echo EXP_RELATIVE_PATH; ?>dist/css/bootstrap-theme.min.css" rel="stylesheet">
		<link type="text/css" href="<?php echo EXP_RELATIVE_PATH; ?>css/bootstrap-toggle.min.css" rel="stylesheet">
		<link type="text/css" href="<?php echo EXP_RELATIVE_PATH; ?>css/typeahead.js-bootstrap.css" rel="stylesheet">
<?php if( true ) { ?>
		<link rel="stylesheet" type="text/css" href="<?php echo EXP_RELATIVE_PATH; ?>media/css/dataTables.bootstrap.css">
<?php } else { ?>
		<link rel="stylesheet" type="text/css" href="<?php echo EXP_RELATIVE_PATH; ?>media/css/jquery.dataTables.min.css">
<?php } ?>

<?php if( defined('_STS_FIXEDCOLUMNS') ) { ?>
		<link rel="stylesheet" type="text/css" href="<?php echo EXP_RELATIVE_PATH; ?>Scroller/css/fixedColumns.dataTables.min.css">
<?php } ?>

<?php if( defined('_STS_SCROLLER') ) { ?>
		<link rel="stylesheet" type="text/css" href="<?php echo EXP_RELATIVE_PATH; ?>Scroller/css/dataTables.scroller.min.css">
<?php } ?>
<?php if( defined('_STS_TABLETOOLS') ) { ?>
		<link rel="stylesheet" type="text/css" href="<?php echo EXP_RELATIVE_PATH; ?>TableTools/css/dataTables.tableTools.min.css">
<?php } ?>

<?php if( defined('_STS_BUTTONS') ) { ?>
		<link rel="stylesheet" type="text/css" href="<?php echo EXP_RELATIVE_PATH; ?>media/Buttons/css/buttons.bootstrap.min.css">
<?php } ?>

<?php if( defined('_STS_SELECT') ) { ?>
		<link rel="stylesheet" type="text/css" href="<?php echo EXP_RELATIVE_PATH; ?>media/Select/css/select.bootstrap.css">
<?php } ?>

<?php if( defined('_STS_MULTI_SELECT') ) { ?>
		<link rel="stylesheet" type="text/css" href="<?php echo EXP_RELATIVE_PATH; ?>css/bootstrap-multiselect.css">
<?php } ?>

<?php if( defined('_STS_EDITOR') ) { ?>
		<link rel="stylesheet" type="text/css" href="<?php echo EXP_RELATIVE_PATH; ?>media/Editor/css/rowReorder.dataTables.min.css">
		<link rel="stylesheet" type="text/css" href="<?php echo EXP_RELATIVE_PATH; ?>media/Editor/css/editor.bootstrap.min.css">
<?php } ?>

		<link rel="stylesheet" type="text/css" href="<?php echo EXP_RELATIVE_PATH; ?>navbar/css/navbar.css">

<?php if( defined('_STS_ANIMATE') ) { ?>
		<link rel="stylesheet" type="text/css" href="<?php echo EXP_RELATIVE_PATH; ?>css/animate.css">
<?php } ?>

		<link href="<?php echo EXP_RELATIVE_PATH; ?>css/theme.css" type="text/css" rel="stylesheet">

	    <!-- Bootstrap core JavaScript -->
		<script type="text/javascript" language="javascript" src="https://ajax.googleapis.com/ajax/libs/jquery/2.1.1/jquery.min.js"></script>

<?php if( defined('_STS_JQUERY_FORM') ) { ?>
  	 <script type="text/javascript" language="javascript" src="https://malsup.github.com/jquery.form.js"></script>
<?php } ?>
<?php if( defined('_STS_HIGHCHARTS') ) { ?>
	<script type="text/javascript" language="javascript" src="https://code.highcharts.com/highcharts.js"></script>
	<script type="text/javascript" language="javascript" src="https://code.highcharts.com/highcharts-3d.js"></script>
	<script type="text/javascript" language="javascript" src="https://code.highcharts.com/modules/exporting.js"></script>
<?php } ?>
<?php if( defined('_STS_GOOGLE_CHARTS') ) { ?>
	<script type="text/javascript" language="javascript" src="https://www.gstatic.com/charts/loader.js"></script>
    <script type="text/javascript" language="javascript" src="https://www.google.com/jsapi"></script>
	<script type="text/javascript">
		google.charts.load('current', {packages: ['corechart', 'geochart']});
	</script>
<?php } ?>
	    <script type="text/javascript" language="javascript" src="https://code.jquery.com/ui/1.11.4/jquery-ui.min.js"></script>
	    <script type="text/javascript" language="javascript" src="<?php echo EXP_RELATIVE_PATH; ?>js/jquery.ui.touch-punch.js"></script>
	    <script type="text/javascript" language="javascript" src="<?php echo EXP_RELATIVE_PATH; ?>dist/js/bootstrap.min.js"></script>
	    <script type="text/javascript" language="javascript" src="<?php echo EXP_RELATIVE_PATH; ?>js/bootstrap-toggle.min.js"></script>
	    <script type="text/javascript" language="javascript" src="<?php echo EXP_RELATIVE_PATH; ?>js/numericInput.js"></script>
	    <script type="text/javascript" language="javascript" src="<?php echo EXP_RELATIVE_PATH; ?>alt-datetimepicker/moment.js"></script>
	    <script type="text/javascript" language="javascript" src="<?php echo EXP_RELATIVE_PATH; ?>alt-datetimepicker/bootstrap-datetimepicker.js"></script>
	    <script type="text/javascript" language="javascript" src="<?php echo EXP_RELATIVE_PATH; ?>js/handlebars-v1.3.0.js"></script>
	    <script type="text/javascript" language="javascript" src="<?php echo EXP_RELATIVE_PATH; ?>js/typeahead.bundle.js"></script>
	    <script type="text/javascript" language="javascript" src="<?php echo EXP_RELATIVE_PATH; ?>js/jquery-dateFormat.js"></script>
	    <script type="text/javascript" language="javascript" src="<?php echo EXP_RELATIVE_PATH; ?>js/bootbox.min.js"></script>
	    <script type="text/javascript" language="javascript" src="<?php echo EXP_RELATIVE_PATH; ?>js/readonly.js"></script>
	    <script type="text/javascript" language="javascript" src="<?php echo EXP_RELATIVE_PATH; ?>media/js/jquery.dataTables.js"></script>
	    <script type="text/javascript" language="javascript" src="<?php echo EXP_RELATIVE_PATH; ?>js/datetime-moment.js"></script>
<?php if( true ) { ?>
	    <script type="text/javascript" language="javascript" src="<?php echo EXP_RELATIVE_PATH; ?>media/js/dataTables.bootstrap.min.js"></script>
<?php } ?>

<?php if( defined('_STS_FIXEDCOLUMNS') ) { ?>
  	  <script type="text/javascript" language="javascript" src="<?php echo EXP_RELATIVE_PATH; ?>media/js/dataTables.fixedColumns.min.js"></script>
<?php } ?>

<?php if( defined('_STS_SCROLLER') ) { ?>
  	  <script type="text/javascript" language="javascript" src="<?php echo EXP_RELATIVE_PATH; ?>Scroller/js/dataTables.scroller.min.js"></script>
<?php } ?>
<?php if( defined('_STS_TABLETOOLS') ) { ?>
   	 <script type="text/javascript" language="javascript" src="<?php echo EXP_RELATIVE_PATH; ?>TableTools/js/dataTables.tableTools.min.js"></script>
<?php } ?>

<?php if( defined('_STS_BUTTONS') ) { ?>
   	 <script type="text/javascript" language="javascript" src="<?php echo EXP_RELATIVE_PATH; ?>media/Buttons/js/dataTables.buttons.min.js"></script>
   	 <script type="text/javascript" language="javascript" src="<?php echo EXP_RELATIVE_PATH; ?>media/Buttons/js/buttons.flash.min.js"></script>
   	 <script type="text/javascript" language="javascript" src="<?php echo EXP_RELATIVE_PATH; ?>media/Buttons/js/buttons.html5.min.js"></script>
   	 <script type="text/javascript" language="javascript" src="<?php echo EXP_RELATIVE_PATH; ?>media/Buttons/js/buttons.print.min.js"></script>
   	 <script type="text/javascript" language="javascript" src="<?php echo EXP_RELATIVE_PATH; ?>media/Buttons/js/buttons.bootstrap.min.js"></script>
<?php } ?>
		
<?php if( defined('_STS_SELECT') ) { ?>
   	 <script type="text/javascript" language="javascript" src="<?php echo EXP_RELATIVE_PATH; ?>media/Select/js/dataTables.select.min.js"></script>
<?php } ?>
		
<?php if( defined('_STS_MULTI_SELECT') ) { ?>
   	 <script type="text/javascript" language="javascript" src="<?php echo EXP_RELATIVE_PATH; ?>js/bootstrap-multiselect.js"></script>
<?php } ?>
		
<?php if( defined('_STS_EDITOR') ) { ?>
   	 <script type="text/javascript" language="javascript" src="<?php echo EXP_RELATIVE_PATH; ?>media/Editor/js/dataTables.rowReorder.js"></script>
   	 <script type="text/javascript" language="javascript" src="<?php echo EXP_RELATIVE_PATH; ?>media/Editor/js/dataTables.editor.min.js"></script>
   	 <script type="text/javascript" language="javascript" src="<?php echo EXP_RELATIVE_PATH; ?>media/Editor/js/editor.bootstrap.min.js"></script>
<?php } ?>

   	 <script type="text/javascript" language="javascript" src="<?php echo EXP_RELATIVE_PATH; ?>navbar/js/navbar.js"></script>

<?php if( defined('_STS_LOCATION') ) { ?>
	<script type="text/javascript" language="javascript" src='https://maps.google.com/maps/api/js?&key=<?php echo $sts_google_api_key; ?>&libraries=places'></script>
   	 <script type="text/javascript" language="javascript" src="<?php echo EXP_RELATIVE_PATH; ?>jquery-locationpicker/locationpicker.jquery.js"></script>
<?php } ?>
		
<?php if( defined('_STS_TIMEZONE') ) { ?>
	<script type="text/javascript" language="javascript" src="<?php echo EXP_RELATIVE_PATH; ?>js/jstz.min.js"></script>
<?php } ?>
		
<?php if( defined('_STS_GEO') ) { ?>
	<script type="text/javascript" language="javascript" src="<?php echo EXP_RELATIVE_PATH; ?>js/geo-min.js"></script>
<?php } ?>
		
<?php if( defined('_STS_SIGNIN_THEME') ) { ?>
		<!-- Custom styles for this template -->
		<link type="text/css" href="<?php echo EXP_RELATIVE_PATH; ?>css/signin.css" rel="stylesheet">
<?php } ?>
		<!-- HTML5 shim and Respond.js IE8 support of HTML5 elements and media queries -->
		<!--[if lt IE 9]>
			<script src="https://oss.maxcdn.com/libs/html5shiv/3.7.0/html5shiv.js"></script>
			<script src="https://oss.maxcdn.com/libs/respond.js/1.4.2/respond.min.js"></script>
		<![endif]-->
<?php
	//! See also sts_session_setup.php which is called before this.
	if( ! defined('_STS_SIGNIN_THEME') ) {
		if( isset($sts_session_lifetime) ) { //! This should trigger a logout on session expiry
			echo '
		<!-- This should trigger a logout on session expiry -->
		<meta http-equiv="refresh" content="'.($sts_session_lifetime+5).'">
';
		?>
		<!-- If the above fails, this should trigger a logout on session expiry -->
		<script language="JavaScript" type="text/javascript"><!--

			//! SCR# 641 - Sometime in 2019, Apple changed the navigator.platform attribute.
			// This function confirms if the browser is on a touchscreen device.
			function is_touch_device() {
			  return !!('ontouchstart' in window        // works on most browsers 
			  || navigator.maxTouchPoints);       // works on IE10/11 and Surface
			};
			
			window.HANDLE_RESIZE_EVENTS = ! is_touch_device() &&
				! /PhantomJS/.test(window.navigator.userAgent);

			$.extend( $.fn.dataTable.defaults, {
			    language: {
			        "processing": "Loading. Please wait..."
			    },
			});
		
		--></script>
	<?php } } ?>
	</head>

  <body role="document">
