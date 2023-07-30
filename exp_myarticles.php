<?php 

// $Id: exp_myarticles.php 5030 2023-04-12 20:31:34Z duncan $
// My Articles

// Set flag that this is a parent file
define( '_STS_INCLUDE', 1 );

// Setup Session
require_once( "include/sts_session_setup.php" );

require_once( "include/sts_config.php" );
$sts_debug = isset($_GET['debug']) && in_group( EXT_GROUP_DEBUG );
require_once( "include/sts_session_class.php" );

$my_session = sts_session::getInstance( $exspeedite_db, $sts_debug );
$my_session->access_check( EXT_GROUP_USER );	// Make sure we should be here

$sts_subtitle = "My Articles";
require_once( "include/header_inc.php" );
require_once( "include/navbar_inc.php" );

?>
<div class="container" role="main">
<h1><span class="glyphicon glyphicon-link"></span> Articles Of Interest</h1>
<?php
require_once( "include/sts_article_class.php" );

$article_table = sts_article::getInstance($exspeedite_db, $sts_debug);
$groups = "'".implode("', '", explode(',', $_SESSION["EXT_GROUPS"]))."'";

$my_articles = $article_table->fetch_rows("ARTICLE_GROUP = 'all' OR ARTICLE_GROUP in (".$groups.")");

if( is_array($my_articles) && count($my_articles) > 0 ) {
	foreach( $my_articles as $row ) {
		echo '<h3><a href="'.$row["ARTICLE_URL"].'" target="_blank">'.$row["ARTICLE_TITLE"].' <span class="glyphicon glyphicon-link"></span></a></h3>
		<p>'.$row["ARTICLE_DESCRIPTION"].'</p>
		<p class="text-right"><em>'.$row["ARTICLE_GROUP"].'</em></p>
		';
	}
}
?>
</div>

	<script language="JavaScript" type="text/javascript"><!--
		
		$(document).ready( function () {
			//alert(($(window).height() - 150) + "px");

			$('#EXP_ARTICLE').dataTable({
		        //"bLengthChange": false,
		        "bFilter": true,
		        "bSort": true,
		        "bInfo": true,
				"bAutoWidth": false,
				//"bProcessing": true, 
				"sScrollX": "100%",
				"sScrollY": ($(window).height() - 256) + "px",
				//"sScrollXInner": "120%",
		        "lengthMenu": [<?php echo isset($sts_length_menu) ? $sts_length_menu : '25, 50, 100, 250'; ?>],
				"bPaginate": true,
				"bScrollCollapse": false,
				"bSortClasses": false		
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

