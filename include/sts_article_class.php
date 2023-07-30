<?php

// $Id: sts_article_class.php 5162 2023-07-17 20:57:47Z dev $
// Articles

// no direct access
defined('_STS_INCLUDE') or die('Restricted access');

require_once( "sts_table_class.php" );
require_once( "sts_setting_class.php" );

class sts_article extends sts_table {
	private $setting_table;
	private $video_directory;

	// Constructor does not need the table name
	public function __construct( $database, $debug = false ) {

		$this->debug = $debug;
		$this->primary_key = "ARTICLE_CODE";
		if( $this->debug ) echo "<p>Create sts_video</p>";
		parent::__construct( $database, ARTICLE_TABLE, $debug);

		$this->setting_table = sts_setting::getInstance( $this->database, $this->debug );
		$this->video_directory = str_replace('\\', '/',
			$this->setting_table->get( 'option', 'VIDEO_DIR' ));
		if( ! empty($this->video_directory) && substr($this->video_directory, -1) <> '/' )	// Make sure it ends with a slash
			$this->video_directory .= '/';
	}
	
	// Allow re-use of objects - singleton function
	public static function getInstance( $database, $debug = false ) {
		static $instance = null;
		$myclass = get_class ();
		if( $debug ) echo "<p>Get instance of $myclass</p>";
		if (null === $instance) {
			$instance = new $myclass( $database, $debug );
		}
		return $instance;
    }
    
	public function menu( $page ) {
		
		if( $this->debug ) echo "<p>".__METHOD__.": page = $page</p>";
		$output = '';
		$modals = '';
		if( $page == 'index.php' ) {
			$output .= '<li><a href="exp_videos.php"><span class="glyphicon glyphicon-film"></span> All videos</a></li>
	';
			
		} else {
		$result = $this->cache->get_videos($page);
		
			if( is_array($result) && count($result) > 0 ) {
			
				foreach( $result as $row ) {
					$file = $row['VIDEO_FILE'];
					if( ! empty($this->video_directory) &&
						(file_exists($this->video_directory.$file) ||
						$this->url_exists($this->video_directory.$file))) {
						$fn2 = str_replace('.mp4', '', str_replace(' ', '_', $file));
	
	
						$output .= '<li><a data-toggle="modal" data-target="#video_modal_'.$fn2.'"><span class="text-success"><span class="glyphicon glyphicon-film"></span></span> '.$row['VIDEO_NAME'].'</a></li>
	';

						$modals .= '
	<div class="modal fade" tabindex="-1" role="dialog" aria-labelledby="mySmallModalLabel" aria-hidden="true" id="video_modal_'.$fn2.'">
	  <div class="modal-dialog modal-lg">
		<div class="modal-content">
		<div class="modal-header">
			<button type="button" class="close" data-dismiss="modal" aria-hidden="true" onclick="document.getElementById(\''.$fn2.'\').pause();">&times;</button>
			<h4 class="modal-title" id="myModalLabel"><span class="text-success"><strong>Exspeedite Video: '.$row['VIDEO_NAME'].' ('.$file.')</strong></span></h4>
		</div>
		<div class="modal-body">
			<video id="'.$fn2.'" src="'.$this->video_directory.$file.'" width="100%" controls type=\'video/mp4; codecs="avc1.42E01E, mp4a.40.2"\' ></video>
		</div>
		</div>
		</div>
	</div>

	';
					}
				}
			}
		}
			
		return array($output, $modals);
	}

	private function url_exists($url) {
		if (!$fp = curl_init($url)) return false;
		return true;
	}

}

//! Form Specifications - For use with sts_form

$sts_form_addarticle_form = array(	//! $sts_form_addarticle_form
	'title' => '<span class="glyphicon glyphicon-link"></span> Add Article',
	'action' => 'exp_addarticle.php',
	//'actionextras' => 'disabled',
	'cancel' => 'exp_listarticle.php',
	'name' => 'addarticle',
	'okbutton' => 'Save Changes',
	'saveadd' => 'Add Another',
	'cancelbutton' => 'Cancel',
		'layout' => '
	<div class="form-group">
		<div class="col-sm-12">
			<div class="form-group">
				<label for="ARTICLE_GROUP" class="col-sm-2 control-label">#ARTICLE_GROUP#</label>
				<div class="col-sm-4">
					%ARTICLE_GROUP%
				</div>
				<div class="col-sm-6">
					<label>Group to which the article applies</label>
				</div>
			</div>
			<div class="form-group">
				<label for="ARTICLE_TITLE" class="col-sm-2 control-label">#ARTICLE_TITLE#</label>
				<div class="col-sm-8">
					%ARTICLE_TITLE%
				</div>
			</div>
			<div class="form-group">
				<label for="ARTICLE_URL" class="col-sm-2 control-label">#ARTICLE_URL#</label>
				<div class="col-sm-8">
					%ARTICLE_URL%
				</div>
			</div>
			<div class="form-group">
				<label for="ARTICLE_DESCRIPTION" class="col-sm-2 control-label">#ARTICLE_DESCRIPTION#</label>
				<div class="col-sm-8">
					%ARTICLE_DESCRIPTION%
				</div>
			</div>
		</div>
		<div class="col-sm-6">
		</div>
	</div>
	
	'
);

$sts_form_editarticle_form = array( //! $sts_form_editarticle_form
	'title' => '<span class="glyphicon glyphicon-link"></span> Edit Article',
	'action' => 'exp_editarticle.php',
	//'actionextras' => 'disabled',
	'cancel' => 'exp_listarticle.php',
	'name' => 'editarticle',
	'okbutton' => 'Save Changes to article',
	'cancelbutton' => 'Back to articles',
		'layout' => '
		%ARTICLE_CODE%
	<div class="form-group">
		<div class="col-sm-12">
			<div class="form-group">
				<label for="ARTICLE_GROUP" class="col-sm-2 control-label">#ARTICLE_GROUP#</label>
				<div class="col-sm-4">
					%ARTICLE_GROUP%
				</div>
				<div class="col-sm-6">
					<label>Group to which the article applies</label>
				</div>
			</div>
			<div class="form-group">
				<label for="ARTICLE_TITLE" class="col-sm-2 control-label">#ARTICLE_TITLE#</label>
				<div class="col-sm-8">
					%ARTICLE_TITLE%
				</div>
			</div>
			<div class="form-group">
				<label for="ARTICLE_URL" class="col-sm-2 control-label">#ARTICLE_URL#</label>
				<div class="col-sm-8">
					%ARTICLE_URL%
				</div>
			</div>
			<div class="form-group">
				<label for="ARTICLE_DESCRIPTION" class="col-sm-2 control-label">#ARTICLE_DESCRIPTION#</label>
				<div class="col-sm-8">
					%ARTICLE_DESCRIPTION%
				</div>
			</div>
		</div>
		<div class="col-sm-6">
		</div>
	</div>
	
	'
);

//! Field Specifications - For use with sts_form

$sts_form_add_article_fields = array( //! $sts_form_add_article_fields
	'ARTICLE_GROUP' => array( 'label' => 'Group', 'format' => 'enum' ),
	'ARTICLE_TITLE' => array( 'label' => 'Title', 'format' => 'text', 'extras' => 'required' ),
	'ARTICLE_URL' => array( 'label' => 'URL', 'format' => 'text', 'extras' => 'required' ),
	'ARTICLE_DESCRIPTION' => array( 'label' => 'Description', 'format' => 'textarea' ),
);

$sts_form_edit_article_fields = array( //! $sts_form_edit_article_fields
	'ARTICLE_CODE' => array( 'format' => 'hidden' ),
	'ARTICLE_GROUP' => array( 'label' => 'Group', 'format' => 'enum' ),
	'ARTICLE_TITLE' => array( 'label' => 'Title', 'format' => 'text', 'extras' => 'required' ),
	'ARTICLE_URL' => array( 'label' => 'URL', 'format' => 'text', 'extras' => 'required' ),
	'ARTICLE_DESCRIPTION' => array( 'label' => 'Description', 'format' => 'textarea' ),
);

//! Layout Specifications - For use with sts_result

$sts_result_articles_layout = array(
	'ARTICLE_CODE' => array( 'format' => 'hidden' ),
	'ARTICLE_GROUP' => array( 'label' => 'Group', 'format' => 'enum' ),
	'ARTICLE_TITLE' => array( 'label' => 'Title', 'format' => 'text', 'extras' => 'required' ),
	'ARTICLE_URL' => array( 'label' => 'URL', 'format' => 'text', 'extras' => 'required' ),
	'ARTICLE_DESCRIPTION' => array( 'label' => 'Description', 'format' => 'text' ),
	'CHANGED_DATE' => array( 'label' => 'Changed', 'format' => 'timestamp-s', 'length' => 90 ),
	'CHANGED_BY' => array( 'label' => 'By', 'format' => 'table',
		'table' => USER_TABLE, 'key' => 'USER_CODE', 'fields' => 'USERNAME', 'link' => 'exp_edituser.php?CODE=' )

);

//! Edit/Delete Button Specifications - For use with sts_result

$sts_result_articles_edit = array(
	'title' => '<span class="glyphicon glyphicon-link"></span> Articles',
	'sort' => 'CHANGED_DATE asc',
	'cancel' => 'index.php',
	'add' => 'exp_addarticle.php',
	//'actionextras' => 'disabled',
	'addbutton' => 'Add Article',
	'cancelbutton' => 'Back',
	'rowbuttons' => array(
		array( 'url' => 'exp_editarticle.php?CODE=', 'key' => 'ARTICLE_CODE', 'label' => 'ARTICLE_TITLE', 'tip' => 'Edit article ', 'icon' => 'glyphicon glyphicon-edit' ),
	//	array( 'url' => 'exp_duparticle.php?CODE=', 'key' => 'ARTICLE_CODE', 'label' => 'ARTICLE_TITLE', 'tip' => 'Duplicate article ', 'icon' => 'glyphicon glyphicon-repeat' ),
		array( 'url' => 'exp_deletearticle.php?CODE=', 'key' => 'ARTICLE_CODE', 'label' => 'ARTICLE_TITLE', 'tip' => 'Delete article ', 'icon' => 'glyphicon glyphicon-trash', 'confirm' => 'yes' )
	)
);

?>
