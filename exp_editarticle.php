<?php 

// $Id: exp_editarticle.php 4697 2022-03-09 23:02:23Z duncan $
// Add Article

// Set flag that this is a parent file
define( '_STS_INCLUDE', 1 );

// Setup Session
require_once( "include/sts_session_setup.php" );

require_once( "include/sts_config.php" );
$sts_debug = (isset($_GET['debug'])  || isset($_POST['debug'])) && in_group( EXT_GROUP_DEBUG );
require_once( "include/sts_session_class.php" );

$my_session = sts_session::getInstance( $exspeedite_db, $sts_debug );
$my_session->access_check( EXT_GROUP_ADMIN );	// Make sure we should be here

$sts_subtitle = "Edit Article";
require_once( "include/header_inc.php" );
require_once( "include/navbar_inc.php" );

require_once( "include/sts_article_class.php" );
require_once( "include/sts_form_class.php" );

$article_table = sts_article::getInstance($exspeedite_db, $sts_debug);
$article_form = new sts_form( $sts_form_editarticle_form, $sts_form_edit_article_fields, $article_table, $sts_debug);

if( isset($_POST) && count($_POST) > 0 ) {		// Process completed form
	$result = $article_form->process_edit_form();

	if( $result ) {
		if( $sts_debug ) die; // So we can see the results
		reload_page ( "exp_listarticle.php" );
	}
}

?>
<div class="container-full theme-showcase" role="main">

<div class="well  well-lg">
<?php

if( isset($value) && is_array($value) && $result == false ) {	// If error occured
	echo "<p><strong>Error:</strong> ".$article_table->error()."</p>";
	echo $article_form->render( $value );
} else if( isset($_GET['CODE']) ) {
	$result = $article_table->fetch_rows($article_table->primary_key." = ".$_GET['CODE']);
	echo $article_form->render( $result[0] );
}

?>
</div>
</div>
<?php

require_once( "include/footer_inc.php" );
?>

