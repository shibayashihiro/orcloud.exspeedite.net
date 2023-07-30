<?php

// $Id:$
// Form Mail

// no direct access
defined('_STS_INCLUDE') or die('Restricted access');

require_once( "sts_table_class.php" );
require_once( "sts_email_class.php" );

class sts_formmail extends sts_table {
	private $email_table;

	// Constructor does not need the table name
	public function __construct( $database, $debug = false ) {

		$this->debug = $debug;
		$this->primary_key = "FORMMAIL_CODE";
		if( $this->debug ) echo "<p>Create sts_formmail</p>";
		parent::__construct( $database, FORMMAIL_TABLE, $debug);
		$this->email_table = sts_email::getInstance($database, $debug);
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
    
	public function duplicate( $pk ) {
		
		if( $this->debug ) echo "<p>".__METHOD__.": pk = $pk</p>";
		
		// Get current record
		$current_record = $this->fetch_rows( $this->primary_key." = ".$pk );
		$row = $current_record[0];
		
		$new_row = array();
		$new_row["FORMMAIL_NAME"] = $this->trim_to_fit( 'FORMMAIL_NAME', $row['FORMMAIL_NAME'].' (duplicate)');
		$new_row["FORMMAIL_SUBJECT"] = $row['FORMMAIL_SUBJECT'];
		$new_row["FORMMAIL_LOGO"] = $row['FORMMAIL_LOGO'];
		$new_row["FORMMAIL_BODY"] = $row['FORMMAIL_BODY'];

		$new_pk = $this->add( $new_row );
		if( $this->debug ) echo "<p>".__METHOD__.": new_pk = $new_pk</p>";
		return $new_pk;
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
	
	public function process_template( $template, $client_name = false,
		$contact_name = false, $sales = false ) {
		$check = $this->fetch_rows( $this->primary_key." = ".$template,
			"*, (SELECT STORED_AS FROM EXP_ATTACHMENT WHERE
				STORED_WHERE = 'local' AND SOURCE_TYPE = 'company' AND
				ATTACHMENT_CODE = FORMMAIL_LOGO) AS STORED_AS");
		
		if( is_array($check) && count($check) == 1 ) {
			$row = $check[0];
			$body = $row["FORMMAIL_BODY"];
			$stored_as = str_replace('/', '\\', $row["STORED_AS"]);
			
			$logo = '<img src="'.$this->email_table->mime_encocde_logo( $stored_as ).'">';
			
			$body = str_replace('%LOGO%', $logo, $body);
			
			if( $client_name )
				$body = str_replace('%CLIENT_NAME%', $client_name, $body);
			else
				$body = str_replace('%CLIENT_NAME%', '', $body);

			if( $contact_name )
				$body = str_replace('%CONTACT_NAME%', $contact_name, $body);
			else
				$body = str_replace('%CONTACT_NAME%', '', $body);

			if( $sales )
				$body = str_replace('%SALES_NAME%', $sales, $body);
			else
				$body = str_replace('%SALES_NAME%', '', $body);
			
			
			return $body;
		}
	}
	
	//! Send an email, based on a CLIENT_ACTIVITY_CODE
	public function send_formmail( $code ) {
		$result = false;
		//$this->email_table->log_email_error( __METHOD__.": entry, code = $code" );

		$check = $this->database->get_one_row("
			SELECT SALES_PERSON, CLIENT_CODE, CONTACT_CODE, EMAIL_TEMPLATE,
				(SELECT CLIENT_NAME FROM EXP_CLIENT
					WHERE EXP_CLIENT.CLIENT_CODE = EXP_CLIENT_ACTIVITY.CLIENT_CODE
					LIMIT 1) AS CLIENT_NAME,
				(SELECT CONTACT_NAME FROM EXP_CONTACT_INFO
					WHERE CONTACT_INFO_CODE = EXP_CLIENT_ACTIVITY.CONTACT_CODE) AS CONTACT_NAME,	
				(SELECT EMAIL FROM EXP_CONTACT_INFO
					WHERE CONTACT_INFO_CODE = EXP_CLIENT_ACTIVITY.CONTACT_CODE) AS CLIENT_EMAIL,
				(SELECT FULLNAME FROM EXP_USER
					WHERE USER_CODE = EXP_CLIENT_ACTIVITY.SALES_PERSON) AS SALES_NAME,
				(SELECT EMAIL FROM EXP_USER
					WHERE USER_CODE = EXP_CLIENT_ACTIVITY.SALES_PERSON) AS SALES_EMAIL,
				(SELECT FORMMAIL_SUBJECT FROM EXP_FORMMAIL
					WHERE FORMMAIL_CODE = EXP_CLIENT_ACTIVITY.EMAIL_TEMPLATE) AS FORMMAIL_SUBJECT
				
			FROM EXP_CLIENT_ACTIVITY
			WHERE ACTIVITY_CODE = $code");
		
		if( is_array($check) && isset($check["EMAIL_TEMPLATE"])) {
			$template = $check["EMAIL_TEMPLATE"];
			$client_name = $check["CLIENT_NAME"];
			$contact_name = $check["CONTACT_NAME"];
			$sales = $check["SALES_NAME"];
			$from = $sales.' <'.$check["SALES_EMAIL"].'>';
			$to = $check["CLIENT_EMAIL"];
			$subject = $check["FORMMAIL_SUBJECT"];
			
			$body = $this->process_template( $template, $client_name, $contact_name, $sales );
			
			$result = $this->email_table->send_email( $to, '', $subject, $body, false, $from );
		}
	
		//$this->email_table->log_email_error( __METHOD__.": return ".($result ? 'true' : 'false') );
		return $result;
	}
}

//! Form Specifications - For use with sts_form

$sts_form_addformmail_form = array(	//! $sts_form_addformmail_form
	'title' => '<span class="glyphicon glyphicon-envelope"></span> Add Form Mail Template',
	'action' => 'exp_addformmail.php',
	//'actionextras' => 'disabled',
	'cancel' => 'exp_listformmail.php',
	'name' => 'addformmail',
	'okbutton' => 'Save Changes',
	'saveadd' => 'Add Another',
	'cancelbutton' => 'Cancel',
		'layout' => '
	<div class="form-group">
		<div class="col-sm-12">
			<div class="form-group">
				<label for="FORMMAIL_NAME" class="col-sm-2 control-label">#FORMMAIL_NAME#</label>
				<div class="col-sm-4">
					%FORMMAIL_NAME%
				</div>
			</div>
			<div class="form-group">
				<label for="FORMMAIL_SUBJECT" class="col-sm-2 control-label">#FORMMAIL_SUBJECT#</label>
				<div class="col-sm-8">
					%FORMMAIL_SUBJECT%
				</div>
			</div>
			<div class="form-group">
				<label for="FORMMAIL_LOGO" class="col-sm-2 control-label">#FORMMAIL_LOGO#</label>
				<div class="col-sm-4">
					%FORMMAIL_LOGO%
				</div>
			</div>
			<div class="form-group">
				<label for="FORMMAIL_BODY" class="col-sm-2 control-label">#FORMMAIL_BODY#</label>
				<div class="col-sm-10">
					%FORMMAIL_BODY%
				</div>
			</div>
		</div>
		<div class="col-sm-6">
		</div>
	</div>
	
	'
);

$sts_form_editformmail_form = array( //! $sts_form_editformmail_form
	'title' => '<span class="glyphicon glyphicon-envelope"></span> Edit Form Mail Template',
	'action' => 'exp_editformmail.php',
	//'actionextras' => 'disabled',
	'cancel' => 'exp_listformmail.php',
	'name' => 'editformmail',
	'okbutton' => 'Save Changes',
	'cancelbutton' => 'Cancel',
		'layout' => '
		%FORMMAIL_CODE%
	<div class="form-group">
		<div class="col-sm-12">
			<div class="form-group">
				<label for="FORMMAIL_NAME" class="col-sm-2 control-label">#FORMMAIL_NAME#</label>
				<div class="col-sm-4">
					%FORMMAIL_NAME%
				</div>
			</div>
			<div class="form-group">
				<label for="FORMMAIL_SUBJECT" class="col-sm-2 control-label">#FORMMAIL_SUBJECT#</label>
				<div class="col-sm-8">
					%FORMMAIL_SUBJECT%
				</div>
			</div>
			<div class="form-group">
				<label for="FORMMAIL_LOGO" class="col-sm-2 control-label">#FORMMAIL_LOGO#</label>
				<div class="col-sm-4">
					%FORMMAIL_LOGO%
				</div>
			</div>
			<div class="form-group">
				<label for="FORMMAIL_BODY" class="col-sm-2 control-label">#FORMMAIL_BODY#</label>
				<div class="col-sm-10">
					%FORMMAIL_BODY%
				</div>
			</div>
		</div>
		<div class="col-sm-6">
		</div>
	</div>
	
	'
);

//! Field Specifications - For use with sts_form

$sts_form_add_formmail_fields = array( //! $sts_form_add_formmail_fields
	'FORMMAIL_NAME' => array( 'label' => 'Name', 'format' => 'text', 'extras' => 'required' ),
	'FORMMAIL_SUBJECT' => array( 'label' => 'Subject', 'format' => 'text', 'extras' => 'required' ),
	'FORMMAIL_LOGO' => array( 'label' => 'Logo', 'format' => 'table',
		'table' => ATTACHMENT_TABLE, 'key' => 'ATTACHMENT_CODE', 'fields' => 'FILE_NAME',
		'condition' => "STORED_WHERE = 'local' AND SOURCE_TYPE = 'company'" ),
	'FORMMAIL_BODY' => array( 'label' => 'Description', 'format' => 'textarea',
		'extras' => 'rows="20"' ),
);

$sts_form_edit_formmail_fields = array( //! $sts_form_edit_formmail_fields
	'FORMMAIL_CODE' => array( 'format' => 'hidden' ),
	'FORMMAIL_NAME' => array( 'label' => 'Name', 'format' => 'text', 'extras' => 'required' ),
	'FORMMAIL_SUBJECT' => array( 'label' => 'Subject', 'format' => 'text', 'extras' => 'required' ),
	'FORMMAIL_LOGO' => array( 'label' => 'Logo', 'format' => 'table',
		'table' => ATTACHMENT_TABLE, 'key' => 'ATTACHMENT_CODE', 'fields' => 'FILE_NAME',
		'condition' => "STORED_WHERE = 'local' AND SOURCE_TYPE = 'company'" ),
	'FORMMAIL_BODY' => array( 'label' => 'Description', 'format' => 'textarea',
		'extras' => 'rows="20"' ),
);

//! Layout Specifications - For use with sts_result

$sts_result_formmails_layout = array(
	'FORMMAIL_CODE' => array( 'format' => 'hidden' ),
	'FORMMAIL_NAME' => array( 'label' => 'Name', 'format' => 'text', 'extras' => 'required' ),
	'FORMMAIL_SUBJECT' => array( 'label' => 'Subject', 'format' => 'text', 'extras' => 'required' ),
	'FORMMAIL_LOGO' => array( 'label' => 'Logo', 'format' => 'table',
		'table' => ATTACHMENT_TABLE, 'key' => 'ATTACHMENT_CODE', 'fields' => 'FILE_NAME',
		'condition' => "STORED_WHERE = 'local' AND SOURCE_TYPE = 'company'" ),
	'CHANGED_DATE' => array( 'label' => 'Changed', 'format' => 'timestamp-s', 'length' => 90 ),
	'CHANGED_BY' => array( 'label' => 'By', 'format' => 'table',
		'table' => USER_TABLE, 'key' => 'USER_CODE', 'fields' => 'USERNAME', 'link' => 'exp_edituser.php?CODE=' )

);

//! Edit/Delete Button Specifications - For use with sts_result

$sts_result_formmails_edit = array(
	'title' => '<span class="glyphicon glyphicon-envelope"></span> Form Mail Template',
	'sort' => 'FORMMAIL_NAME asc',
	'cancel' => 'index.php',
	'add' => 'exp_addformmail.php',
	//'actionextras' => 'disabled',
	'addbutton' => 'Add Form Mail Template',
	'cancelbutton' => 'Back',
	'rowbuttons' => array(
		array( 'url' => 'exp_editformmail.php?CODE=', 'key' => 'FORMMAIL_CODE', 'label' => 'FORMMAIL_NAME', 'tip' => 'Edit template ', 'icon' => 'glyphicon glyphicon-edit' ),
		array( 'url' => 'exp_dupformmail.php?CODE=', 'key' => 'FORMMAIL_CODE', 'label' => 'FORMMAIL_NAME', 'tip' => 'Duplicate template ', 'icon' => 'glyphicon glyphicon-repeat' ),
		array( 'url' => 'exp_viewformmail.php?CODE=', 'key' => 'FORMMAIL_CODE', 'label' => 'FORMMAIL_NAME', 'tip' => 'Preview template ', 'icon' => 'glyphicon glyphicon-eye-open',
			'target' => '_blank' ),
		array( 'url' => 'exp_deleteformmail.php?CODE=', 'key' => 'FORMMAIL_CODE', 'label' => 'FORMMAIL_NAME', 'tip' => 'Delete template ', 'icon' => 'glyphicon glyphicon-trash', 'confirm' => 'yes' )
	)
);

?>
