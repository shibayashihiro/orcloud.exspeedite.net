<?php

// $Id: sts_report_class.php 5030 2023-04-12 20:31:34Z duncan $
// Reports and videos

// no direct access
defined('_STS_INCLUDE') or die('Restricted access');

require_once( "sts_table_class.php" );
require_once( "sts_session_class.php" );
require_once( "sts_setting_class.php" );

class sts_report extends sts_table {

	private $setting_table;
	private $session_table;
	private $video_table;
	private $birt_path;			// Path to BIRT
	private $page;				// Current page
	private $lanes_report;
	private $kpi_profit;
	public $reports;
	
	private $internal_defaults = array(
		//! SCR# 617 - Add R&M Report
		array( "REPORT_NAME" => "R&M Report",
			"REPORT_FILE"	=> "exp_listinsp_report2.php",
			"ON_PAGE"		=> "other",
			"REPORT_DESCRIPTION"	=> "R&M Report",
			"REPORT_GROUP"	=> "admin",
			"RESTRICT_BY"	=> "user",
			"REPORT_TYPE"	=> "Internal",
			"REPORT_ICON"	=> "images/order_icon.png"),

		array( "REPORT_NAME" => "Lane Report",
			"REPORT_FILE"	=> "exp_listlane.php",
			"ON_PAGE"		=> "exp_listshipment.php",
			"REPORT_DESCRIPTION"	=> "Lanes report",
			"REPORT_GROUP"	=> "sales",
			"RESTRICT_BY"	=> "user",
			"REPORT_TYPE"	=> "Internal",
			"REPORT_ICON"	=> "images/order_icon.png"),

		array( "REPORT_NAME" => "Lane Report II",
			"REPORT_FILE"	=> "exp_listlane2.php",
			"ON_PAGE"		=> "exp_listshipment.php",
			"REPORT_DESCRIPTION"	=> "Lane Report II",
			"REPORT_GROUP"	=> "sales",
			"RESTRICT_BY"	=> "user",
			"REPORT_TYPE"	=> "Internal",
			"REPORT_ICON"	=> "images/order_icon.png"),

		array( "REPORT_NAME" => "Shipment report",
			"REPORT_FILE"	=> "exp_listlane3.php",
			"ON_PAGE"		=> "exp_listshipment.php",
			"REPORT_DESCRIPTION"	=> "Shipment report",
			"REPORT_GROUP"	=> "sales",
			"RESTRICT_BY"	=> "user",
			"REPORT_TYPE"	=> "Internal",
			"REPORT_ICON"	=> "images/order_icon.png"),

		array( "REPORT_NAME" => "Mileage Report",
			"REPORT_FILE"	=> "exp_listmileage.php",
			"REPORT_DESCRIPTION"	=> "Mileage Report KPI",
			"REPORT_GROUP"	=> "finance",
			"RESTRICT_BY"	=> "group",
			"REPORT_TYPE"	=> "Internal",
			"REPORT_ICON"	=> "images/load_icon.png"),

		array( "REPORT_NAME" => "Top 20 Clients",
			"REPORT_FILE"	=> "exp_list_top20.php",
			"REPORT_DESCRIPTION"	=> "Top 20 Clients KPI",
			"REPORT_GROUP"	=> "finance",
			"RESTRICT_BY"	=> "group",
			"REPORT_TYPE"	=> "Internal",
			"REPORT_ICON"	=> "images/user_icon.png"),

		array( "REPORT_NAME" => "Key Accounts",
			"REPORT_FILE"	=> "exp_list_key_acct.php",
			"REPORT_DESCRIPTION"	=> "Key Accounts KPI",
			"REPORT_GROUP"	=> "finance",
			"RESTRICT_BY"	=> "group",
			"REPORT_TYPE"	=> "Internal",
			"REPORT_ICON"	=> "images/user_icon.png"),

		array( "REPORT_NAME" => "Top 20 Carriers",
			"REPORT_FILE"	=> "exp_list_top20_carrier.php",
			"REPORT_DESCRIPTION"	=> "Top 20 Carriers KPI",
			"REPORT_GROUP"	=> "finance",
			"RESTRICT_BY"	=> "group",
			"REPORT_TYPE"	=> "Internal",
			"REPORT_ICON"	=> "images/carrier_icon.png"),

		array( "REPORT_NAME" => "On Time Delivery Rate",
			"REPORT_FILE"	=> "exp_list_ontime_rate.php",
			"REPORT_DESCRIPTION"	=> "On Time Delivery Rate KPI",
			"REPORT_GROUP"	=> "finance",
			"RESTRICT_BY"	=> "group",
			"REPORT_TYPE"	=> "Internal",
			"REPORT_ICON"	=> "images/order_icon.png"),

		array( "REPORT_NAME" => "Profitability",
			"REPORT_FILE"	=> "exp_list_profitability.php",
			"REPORT_DESCRIPTION"	=> "Profitability KPI",
			"REPORT_GROUP"	=> "finance",
			"RESTRICT_BY"	=> "group",
			"REPORT_TYPE"	=> "Internal",
			"REPORT_ICON"	=> "images/money_bag.png"),

	);
	
	// Constructor does not need the table name
	public function __construct( $database, $debug = false ) {
		global $_SERVER;

		$this->debug = $debug;
		$this->primary_key = "REPORT_CODE";
		if( $this->debug ) echo "<p>Create sts_report</p>";
		parent::__construct( $database, REPORT_TABLE, $debug);
		//$this->set_defaults();
		$this->setting_table = sts_setting::getInstance( $this->database, $this->debug );
		$this->birt_path = $this->setting_table->get( 'api', 'BIRT_REPORTS_PATH' );
		$this->lanes_report = $this->setting_table->get( 'option', 'LANES_REPORT' ) == 'true';
		$this->kpi_profit = $this->setting_table->get( 'option', 'KPI_PROFIT' ) == 'true';
		
		$this->video_table = sts_video::getInstance( $this->database, $this->debug );
		$dirs = explode('/',$_SERVER['SCRIPT_NAME']);
		$this->page = end($dirs);
		$this->session_table = sts_session::getInstance( $this->database, $this->debug );
		$this->set_defaults();

		if( ! isset($this->reports))
			$this->user_reports();
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
    
	//! SCR# 411 - duplicate method
	public function duplicate( $pk ) {
		
		if( $this->debug ) echo "<p>".__METHOD__.": pk = $pk</p>";
		
		// Get current record
		$current_record = $this->fetch_rows( $this->primary_key." = ".$pk );
		$row = $current_record[0];
		
		$new_row = array();
		$new_row["REPORT_NAME"] = $this->trim_to_fit( 'REPORT_NAME', $row['REPORT_NAME'].' (duplicate)');
		$new_row["REPORT_FILE"] = $this->trim_to_fit( 'REPORT_FILE', $row['REPORT_FILE']);
		if( ! empty($row['ON_PAGE']) )
			$new_row["ON_PAGE"] = $this->trim_to_fit( 'ON_PAGE', $row['ON_PAGE']);
		if( ! empty($row['REPORT_DESCRIPTION']) )
			$new_row["REPORT_DESCRIPTION"] = $this->trim_to_fit( 'REPORT_DESCRIPTION', $row['REPORT_DESCRIPTION']);
		$new_row["RESTRICT_BY"] = $row["RESTRICT_BY"];
		$new_row["REPORT_GROUP"] = $row["REPORT_GROUP"];
		$new_row["REPORT_TYPE"] = $row["REPORT_TYPE"];
		if( ! empty($row['REPORT_ICON']) )
			$new_row["REPORT_ICON"] = $this->trim_to_fit( 'REPORT_ICON', $row['REPORT_ICON']);

		$new_pk = $this->add( $new_row );
		if( $this->debug ) echo "<p>".__METHOD__.": new_pk = $new_pk</p>";
		return $new_pk;
	}
	
	public function menu( $query = false ) {
		global $_SESSION;
		
		if( $this->debug ) echo "<p>".__METHOD__.": page = $this->page</p>";
		$output = '';
		$result = false;
		
		if( ! isset($this->reports))
			$this->user_reports();

		$output1 = '';
		if( is_array($this->reports) && count($this->reports) > 0 ) {
			foreach( $this->reports as $row ) {
					if( false && $row['REPORT_NAME'] == 'Carrier' ) {
					echo "<pre>";
					var_dump($row);
					echo "</pre>";
					}
				
				if( in_array($row['ON_PAGE'], array($this->page, '')) ) {
					$file = $row['REPORT_FILE'];
					if( isset($_SESSION['EXT_USER_CODE']))
						$file = preg_replace('/\%USER_CODE\%/', $_SESSION['EXT_USER_CODE'], $file, 1);
					if( isset($_SESSION['EXT_USERNAME']))
						$file = preg_replace('/\%USERNAME\%/', $_SESSION['EXT_USERNAME'], $file, 1);
					if( isset($_SESSION['EXT_FULLNAME']))
						$file = preg_replace('/\%FULLNAME\%/', $_SESSION['EXT_FULLNAME'], $file, 1);
					//! SCR# 186 - handle %PARAMETERS%
					if( $query <> false )
						$file = preg_replace('/\%PARAMETERS\%/', $query, $file, 1);
						
					$url = $row['REPORT_TYPE'] == 'BIRT' ? $this->birt_path.$file : $file;
					
					$blank = 'target="_blank"';
					
					$icon = empty($row['REPORT_ICON']) ?
						'<span class="glyphicon glyphicon-list-alt"></span>' :
						( $row['REPORT_ICON'][0] == '<' ? $row['REPORT_ICON'] : '<img src="'.$row['REPORT_ICON'].'" alt="menu_icon" height="16">');
					
					if( $row['REPORT_TYPE'] == 'BIRT' )
						$icon = '<span class="text-primary">'.$icon.'</span>';
		
					$output1 .= '<li><a href="'.$url.'" '.$blank.'>'.$icon.' '.$row['REPORT_NAME'].'</a></li>
';
				}
			}
		}
		
		if( $output1 <> '' )
			$output1 = '<li class="dropdown-header">Reports</li>
'.$output1;
		
		$output3 = '';
		$modals = '';
		if( ! $this->session_table->in_group(EXT_GROUP_DRIVER) ) {
			list($output2, $modals) = $this->video_table->menu( $this->page );
			$output3 = '<li class="dropdown-header">Help & Videos</li>
			<li><a href="'.EXP_RELATIVE_PATH.'include/Exspeedite help file.html" target="_blank"><span class="glyphicon glyphicon-question-sign"></span> Exspeedite help</a></li>
	'.$output2;
		}

		if( $output1 <> '' && $output3 <> '' )
			$output1 .= '<li role="separator" class="divider"></li>
';

		$output = $output1.$output3;

		if( $output <> '' )
			$output = '<li class="dropdown">
            <a href="#" id="reportsmenu" class="dropdown-toggle" data-toggle="dropdown"><span class="text-success"><span class="glyphicon glyphicon-list-alt"></span><span class="glyphicon glyphicon-film"></span> <b class="caret"></b></span></a>
            <ul class="dropdown-menu">
'.$output.'</ul>
';

		return array($output, $modals);
	}
	
	private function set_defaults() {
		
		if( ! isset($_SESSION["DEFAULT_REPORTS_LOADED"]) ) {
			$_SESSION["DEFAULT_REPORTS_LOADED"] = true;
			$result = $this->fetch_rows("", "REPORT_NAME");
			
			foreach( $this->internal_defaults as $report ) {
				$found = false;
				if( $result && is_array($result) ) {
					foreach( $result as $row ) {
						if( $row['REPORT_NAME'] == $report['REPORT_NAME'] )
							$found = true;
					}
				}
				if( ! $found )
					//! SCR# 529 - hide these reports
					if( ! ((! $this->lanes_report && $report['REPORT_NAME'] == "Lane Report") ||
						(! $this->kpi_profit && $report['REPORT_NAME'] == "Profitability")) )
					$add_result = $this->add( $report );
			}
			unset($_SESSION['EXT_USER_REPORTS'], $this->reports);
		}
	}
	
	//! Create checkboxes for reports
	public function user_checkboxes( $form, $user_code = false ) {
		if( $this->debug ) echo "<p>".__METHOD__.": user_code = $user_code</p>";
		$reports = $this->fetch_rows("RESTRICT_BY = 'user'", "REPORT_NAME, REPORT_CODE, REPORT_FILE", "REPORT_NAME ASC");
		
		if( $user_code )
			$ur_table = sts_user_report::getInstance($this->database, $this->debug);
	
		$reports_str = '';
		if( is_array($reports) && count( $reports ) > 0 ) {
			foreach( $reports as $row ) {
				// Some reports are hidden by settings
				$hidden = (! $this->lanes_report && $row["REPORT_FILE"] == 'exp_listlane.php' ) ||
					(! $this->kpi_profit && $row["REPORT_FILE"] == 'exp_list_profitability.php' );

				if( ! $hidden ) {
					$check = $user_code ?
						$ur_table->fetch_rows("USER_CODE = ".$user_code."
						AND REPORT_CODE = ".$row["REPORT_CODE"]) : false;
					if( $this->debug ) {
						echo "<pre>";
						var_dump($check);
						echo "</pre>";
					}
					$exists = is_array($check) && count($check) > 0;
					if( $this->debug ) echo "<p>".__METHOD__.": exists = ".($exists ? 'true' : 'false')."</p>";
					
					$reports_str .= '<div class="checkbox">
					    <label>
					      <input type="checkbox" class="report" name="REPORT_'.$row["REPORT_CODE"].'" id="REPORT_'.$row["REPORT_CODE"].'" value="'.$row["REPORT_CODE"].'"'.($exists ? ' checked' : '').'> '.$row["REPORT_NAME"].'
					    </label>
					    </div>
					    ';
				}
			}
			if( ! empty($reports_str) ) {
				$reports_str = '<div id="REPORTS" class="panel panel-default">
				  <div class="panel-heading">
				    <h3 class="panel-title">Access To <a href="exp_listreport.php">Reports</a></h3>
				  </div>
				  <div class="panel-body">
				'.$reports_str . '</div>
				</div>
				';		
			
				$form = str_replace('<!-- REPORTS -->', $reports_str, $form);
			}
		}
		return $form;
	}
	
	//! Process checkboxes for reports
	public function process_user_checkboxes( $user_code ) {
		if( $this->debug ) echo "<p>".__METHOD__.": user_code = $user_code</p>";
		$ur_table = sts_user_report::getInstance($this->database, $this->debug);

		$reports = $this->fetch_rows("RESTRICT_BY = 'user'", "REPORT_NAME, REPORT_CODE", "REPORT_NAME ASC");
		
		if( is_array($reports) && count( $reports ) > 0 ) {
			foreach( $reports as $row ) {
				$check = $ur_table->fetch_rows("USER_CODE = ".$user_code."
					AND REPORT_CODE = ".$row["REPORT_CODE"]);
				
				$exists = is_array($check) && count($check) > 0;
				if( $this->debug ) echo "<p>".__METHOD__.": exists = ".($exists ? 'true' : 'false')."</p>";
				
				if( is_array($_POST) &&
					isset($_POST['REPORT_'.$row["REPORT_CODE"]])) {
					
					if( ! $exists )
						$ur_table->add( array( 'USER_CODE' => $user_code, 'REPORT_CODE' => $row["REPORT_CODE"]) );
				} else {
					if( $exists )
						$ur_table->delete_row("USER_CODE = ".$user_code."
					AND REPORT_CODE = ".$row["REPORT_CODE"]);
				}
			}
		}
	}
	
    //! Get the list of available reports for the current user
    // Session variables:
    // EXT_USER_REPORTS - array of reports for menu
    public function user_reports( $force = false) {
	    global $_SESSION;
	    
		if( $this->debug ) echo "<p>".__METHOD__.": entry</p>";
	    $this->reports = array();
		
		//! SCR# 375 - check $_SESSION['EXT_USER_CODE'] is set (should be)
		if( isset($_SESSION['EXT_USER_CODE']) ) {
			if( $force || ! isset($_SESSION['EXT_USER_REPORTS'])) {
				$ur_table = sts_user_report::getInstance($this->database, $this->debug);
				$groups = "'".implode("','",explode(',', $_SESSION['EXT_GROUPS']))."'";
				
				$check = $this->database->get_multiple_rows("
					SELECT DISTINCT R.REPORT_CODE, REPORT_NAME, REPORT_FILE, ON_PAGE,
						REPORT_TYPE, REPORT_ICON 
					FROM EXP_REPORT R, EXP_USER_REPORT U
					WHERE U.USER_CODE = ".$_SESSION['EXT_USER_CODE']."
					AND (U.REPORT_CODE = R.REPORT_CODE
					AND R.RESTRICT_BY = 'USER')
					OR (R.RESTRICT_BY = 'GROUP'
					AND R.REPORT_GROUP IN ($groups))
					ORDER BY REPORT_TYPE ASC, REPORT_NAME ASC");
				
				$this->reports = array();
			    if( is_array($check) && count($check) > 0 ) {
				    foreach( $check as $row ) {
					    $this->reports[$row["REPORT_CODE"]] = $row;
				    }
			    }
			    $_SESSION['EXT_USER_REPORTS'] = $this->reports;
		    } else {
			    $this->reports = $_SESSION['EXT_USER_REPORTS'];
		    }
	    } else {
    		$_SESSION['EXT_USER_REPORTS'] = $this->reports;
	    }
	    
	    return $this->reports;
    }
    
    //! SCR# 695 - Shortcut buttons
    public function shortcuts() {
	    global $EXP_RELATIVE_PATH;
	    if( $this->debug ) echo "<p>".__METHOD__.": entry</p>";
	    $output = '';
	    $choices = [
		    'Shipments' => [
		    	'link' => 'exp_listshipment.php', 'group' => EXT_GROUP_SHIPMENTS ],
		    'Loads' => [
		    	'link' => 'exp_listload.php', 'group' => EXT_GROUP_DISPATCH ],
		    'Summary' => [
		    	'link' => 'exp_listsummary.php', 'group' => EXT_GROUP_DISPATCH ],
		    'Drivers' => [
		    	'link' => 'exp_listdriver.php', 'group' => EXT_GROUP_FLEET ],
		    'Tractors' => [
		    	'link' => 'exp_listtractor.php', 'group' => EXT_GROUP_FLEET ],
		    'Trailers' => [
		    	'link' => 'exp_listtrailer.php', 'group' => EXT_GROUP_PROFILES ],
		    'Carriers' => [
		    	'link' => 'exp_listcarrier.php', 'group' => EXT_GROUP_PROFILES ],
		    'Clients' => [
		    	'link' => 'exp_listclient.php', 'group' => EXT_GROUP_PROFILES ]
	    ];
	    
	    
	    $s1 = $this->setting_table->get( 'main', 'SHORTCUT1' );
	    if( $this->debug ) echo "<p>".__METHOD__.": s1 $s1</p>";
	    if( $s1 != 'None' && $this->session_table->in_group($choices[$s1]['group']) )
	    	$output .= '<a class="btn btn-sm btn-danger" style="padding: 5px;" id="sc1" href="'.$EXP_RELATIVE_PATH.$choices[$s1]['link'].'">'.$s1.'</a>';

	    $s2 = $this->setting_table->get( 'main', 'SHORTCUT2' );
	    if( $this->debug ) echo "<p>".__METHOD__.": s2 $s2</p>";
	    if( $s2 != 'None' && $this->session_table->in_group($choices[$s2]['group']) )
	    	$output .= '<a class="btn btn-sm btn-primary" style="padding: 5px;" id="sc2" href="'.$EXP_RELATIVE_PATH.$choices[$s2]['link'].'">'.$s2.'</a>';

	    $s3 = $this->setting_table->get( 'main', 'SHORTCUT3' );
	    if( $this->debug ) echo "<p>".__METHOD__.": s3 $s3</p>";
	    if( $s3 != 'None' && $this->session_table->in_group($choices[$s3]['group']) )
	    	$output .= '<a class="btn btn-sm btn-success" style="padding: 5px;" id="sc3" href="'.$EXP_RELATIVE_PATH.$choices[$s3]['link'].'">'.$s3.'</a>';
	    
		if( ! empty($output) )
			$output = '<li><div class="btn-group" style="padding: 12px 5px 12px 5px;">
			'.$output.'
			</div></li>
			';
	    
	    return $output;
    }
}

class sts_video extends sts_table {

	private $setting_table;
	private $video_directory;
	
	// Constructor does not need the table name
	public function __construct( $database, $debug = false ) {

		$this->debug = $debug;
		$this->primary_key = "VIDEO_CODE";
		if( $this->debug ) echo "<p>Create sts_video</p>";
		parent::__construct( $database, VIDEO_TABLE, $debug);
		//$this->set_defaults();
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
    
	public function duplicate( $pk ) {
		
		if( $this->debug ) echo "<p>".__METHOD__.": pk = $pk</p>";
		
		// Get current record
		$current_record = $this->fetch_rows( $this->primary_key." = ".$pk );
		$row = $current_record[0];
		
		$new_row = array();
		$new_row["VIDEO_NAME"] = $this->trim_to_fit( 'VIDEO_NAME', $row['VIDEO_NAME'].' (duplicate)');
		$new_row["VIDEO_FILE"] = $this->trim_to_fit( 'VIDEO_FILE', $row['VIDEO_FILE']);
		if( ! empty($row['ON_PAGE']) )
			$new_row["ON_PAGE"] = $this->trim_to_fit( 'ON_PAGE', $row['ON_PAGE']);
		if( ! empty($row['VIDEO_DESCRIPTION']) )
			$new_row["VIDEO_DESCRIPTION"] = $this->trim_to_fit( 'VIDEO_DESCRIPTION', $row['VIDEO_DESCRIPTION']);

		$new_pk = $this->add( $new_row );
		if( $this->debug ) echo "<p>".__METHOD__.": new_pk = $new_pk</p>";
		return $new_pk;
	}
	
	private function url_exists($url) {
		if (!$fp = curl_init($url)) return false;
		return true;
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
			
		$output .= '<li role="separator" class="divider"></li>
			<li class="dropdown-header">Articles</li>
			<li><a href="exp_myarticles.php"><span class="glyphicon glyphicon-link"></span> Articles</a></li>
			';

		return array($output, $modals);
	}
	
	public function all_videos() {
		
		if( $this->debug ) echo "<p>".__METHOD__.": entry</p>";
		$output = '';
		
		$result = $this->cache->get_videos();
		
		if( is_array($result) && count($result) > 0 ) {
		
			foreach( $result as $row ) {
				$file = $row['VIDEO_FILE'];
				if( ! empty($this->video_directory) &&
					(file_exists($this->video_directory.$file) ||
					$this->url_exists($this->video_directory.$file))) {
					$path = $this->video_directory.$file;
					$fn = pathinfo($file,  PATHINFO_FILENAME);
					$fn2 = str_replace(' ', '_', $fn);
					$output .= '<p><a class="btn btn-md btn-default" data-toggle="modal" data-target="#video_modal_'.$fn2.'">'.$file.'</a></p>
		<div class="modal fade" tabindex="-1" role="dialog" aria-labelledby="mySmallModalLabel" aria-hidden="true" id="video_modal_'.$fn2.'">
		  <div class="modal-dialog modal-lg">
			<div class="modal-content">
			<div class="modal-header">
				<button type="button" class="close" data-dismiss="modal" aria-hidden="true" onclick="document.getElementById(\''.$fn2.'\').pause();">&times;</button>
				<h4 class="modal-title" id="myModalLabel"><span class="text-success"><strong>Exspeedite Video</strong></span></h4>
			</div>
			<div class="modal-body">
				<video id="'.$fn2.'" src="'.$path.'" width="100%" controls type=\'video/mp4; codecs="avc1.42E01E, mp4a.40.2"\' ></video>
			</div>
			</div>
			</div>
		</div>
			';
				}
			}
		}


		return $output;
	}
	
}

class sts_user_report extends sts_table {

	// Constructor does not need the table name
	public function __construct( $database, $debug = false ) {

		$this->debug = $debug;
		$this->primary_key = "USER_REPORT_CODE";
		if( $this->debug ) echo "<p>Create sts_user_report</p>";
		parent::__construct( $database, USER_REPORT_TABLE, $debug);
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
    
}

//! Form Specifications - For use with sts_form

$sts_form_addreport_form = array(	//! $sts_form_addreport_form
	'title' => '<img src="images/setting_icon.png" alt="setting_icon" height="24"> Add report',
	'action' => 'exp_addreport.php',
	//'actionextras' => 'disabled',
	'cancel' => 'exp_listreport.php',
	'name' => 'addreport',
	'okbutton' => 'Save Changes',
	'saveadd' => 'Add Another',
	'cancelbutton' => 'Cancel',
		'layout' => '
	<div class="form-group">
		<div class="col-sm-12">
			<div class="form-group">
				<label for="REPORT_TYPE" class="col-sm-2 control-label">#REPORT_TYPE#</label>
				<div class="col-sm-4">
					%REPORT_TYPE%
				</div>
				<div class="col-sm-6">
					<label>Either BIRT or Internal</label>
				</div>
			</div>
			<div class="form-group">
				<label for="REPORT_ICON" class="col-sm-2 control-label">#REPORT_ICON#</label>
				<div class="col-sm-4">
					%REPORT_ICON%
				</div>
				<div class="col-sm-6">
					<label>Icon used for report (leave blank for default)</label>
				</div>
			</div>
			<div class="form-group">
				<label for="REPORT_NAME" class="col-sm-2 control-label">#REPORT_NAME#</label>
				<div class="col-sm-4">
					%REPORT_NAME%
				</div>
				<div class="col-sm-6">
					<label>Name to be seen on menus</label>
				</div>
			</div>
			<div class="form-group">
				<label for="RESTRICT_BY" class="col-sm-2 control-label">#RESTRICT_BY#</label>
				<div class="col-sm-4">
					%RESTRICT_BY%
				</div>
				<div class="col-sm-6">
					<label>Either by group or by user. If user, you enable in edit user screen.</label>
				</div>
			</div>
			<div class="form-group" id="BY_GROUP">
				<label for="REPORT_GROUP" class="col-sm-2 control-label">#REPORT_GROUP#</label>
				<div class="col-sm-4">
					%REPORT_GROUP%
				</div>
				<div class="col-sm-6">
					<label>You need to be in this group to see it</label>
				</div>
			</div>
			<div class="form-group">
				<label for="REPORT_FILE" class="col-sm-2 control-label">#REPORT_FILE#</label>
				<div class="col-sm-4">
					%REPORT_FILE%
				</div>
				<div class="col-sm-6">
					<label>File name of the report or URL to internal report.</label>
				</div>
			</div>
			<div class="form-group">
				<label for="ON_PAGE" class="col-sm-2 control-label">#ON_PAGE#</label>
				<div class="col-sm-4">
					%ON_PAGE%
				</div>
				<div class="col-sm-6">
					<label>Appears on this page (hint exp_SOMETHING.php) or blank=all</label>
				</div>
			</div>
			<div class="form-group">
				<label for="REPORT_DESCRIPTION" class="col-sm-2 control-label">#REPORT_DESCRIPTION#</label>
				<div class="col-sm-8">
					%REPORT_DESCRIPTION%
				</div>
			</div>
		</div>
		<div class="col-sm-6">
		</div>
	</div>
	
	'
);

$sts_form_editreport_form = array( //! $sts_form_editreport_form
	'title' => '<img src="images/setting_icon.png" alt="setting_icon" height="24"> Edit report',
	'action' => 'exp_editreport.php',
	//'actionextras' => 'disabled',
	'cancel' => 'exp_listreport.php',
	'name' => 'editreport',
	'okbutton' => 'Save Changes to report',
	'cancelbutton' => 'Back to reports',
		'layout' => '
		%REPORT_CODE%
	<div class="form-group">
		<div class="col-sm-12">
			<div class="form-group">
				<label for="REPORT_TYPE" class="col-sm-2 control-label">#REPORT_TYPE#</label>
				<div class="col-sm-4">
					%REPORT_TYPE%
				</div>
				<div class="col-sm-6">
					<label>Either BIRT or Internal</label>
				</div>
			</div>
			<div class="form-group">
				<label for="REPORT_ICON" class="col-sm-2 control-label">#REPORT_ICON#</label>
				<div class="col-sm-4">
					%REPORT_ICON%
				</div>
				<div class="col-sm-6">
					<label>Icon used for report (leave blank for default)</label>
				</div>
			</div>
			<div class="form-group">
				<label for="REPORT_NAME" class="col-sm-2 control-label">#REPORT_NAME#</label>
				<div class="col-sm-4">
					%REPORT_NAME%
				</div>
				<div class="col-sm-6">
					<label>Name to be seen on menus</label>
				</div>
			</div>
			<div class="form-group">
				<label for="RESTRICT_BY" class="col-sm-2 control-label">#RESTRICT_BY#</label>
				<div class="col-sm-4">
					%RESTRICT_BY%
				</div>
				<div class="col-sm-6">
					<label>Either by group or by user. If user, you enable in edit user screen.</label>
				</div>
			</div>
			<div class="form-group" id="BY_GROUP">
				<label for="REPORT_GROUP" class="col-sm-2 control-label">#REPORT_GROUP#</label>
				<div class="col-sm-4">
					%REPORT_GROUP%
				</div>
				<div class="col-sm-6">
					<label>You need to be in this group to see it</label>
				</div>
			</div>
			<div class="form-group">
				<label for="REPORT_FILE" class="col-sm-2 control-label">#REPORT_FILE#</label>
				<div class="col-sm-4">
					%REPORT_FILE%
				</div>
				<div class="col-sm-6">
					<label>File name of the report or URL to internal report.</label>
				</div>
			</div>
			<div class="form-group">
				<label for="ON_PAGE" class="col-sm-2 control-label">#ON_PAGE#</label>
				<div class="col-sm-4">
					%ON_PAGE%
				</div>
				<div class="col-sm-6">
					<label>Appears on this page (hint exp_SOMETHING.php) or blank=all</label>
				</div>
			</div>
			<div class="form-group">
				<label for="REPORT_DESCRIPTION" class="col-sm-2 control-label">#REPORT_DESCRIPTION#</label>
				<div class="col-sm-8">
					%REPORT_DESCRIPTION%
				</div>
			</div>
		</div>
		<div class="col-sm-6">
		</div>
	</div>
	
	'
);

$sts_form_addvideo_form = array(	//! sts_form_addvideo_form
	'title' => '<span class="glyphicon glyphicon-film"></span> Add video',
	'action' => 'exp_addvideo.php',
	//'actionextras' => 'disabled',
	'cancel' => 'exp_listvideo.php',
	'name' => 'addvideo',
	'okbutton' => 'Save Changes',
	'saveadd' => 'Add Another',
	'cancelbutton' => 'Cancel',
		'layout' => '
	<div class="form-group">
		<div class="col-sm-12">
			<div class="form-group">
				<label for="VIDEO_NAME" class="col-sm-2 control-label">#VIDEO_NAME#</label>
				<div class="col-sm-4">
					%VIDEO_NAME%
				</div>
				<div class="col-sm-6">
					<label>Name to be seen on menus</label>
				</div>
			</div>
			<div class="form-group">
				<label for="VIDEO_FILE" class="col-sm-2 control-label">#VIDEO_FILE#</label>
				<div class="col-sm-4">
					%VIDEO_FILE%
				</div>
				<div class="col-sm-6">
					<label>File name of the video.<br>If the file is missing, it will not be shown in the menu.</label>
				</div>
			</div>
			<div class="form-group">
				<label for="ON_PAGE" class="col-sm-2 control-label">#ON_PAGE#</label>
				<div class="col-sm-4">
					%ON_PAGE%
				</div>
				<div class="col-sm-6">
					<label>Appears on this page (hint exp_SOMETHING.php)<br>Leave blank for all pages</label>
				</div>
			</div>
			<div class="form-group">
				<label for="VIDEO_DESCRIPTION" class="col-sm-2 control-label">#VIDEO_DESCRIPTION#</label>
				<div class="col-sm-8">
					%VIDEO_DESCRIPTION%
				</div>
			</div>
		</div>
		<div class="col-sm-6">
		</div>
	</div>
	
	'
);

$sts_form_editvideo_form = array( //! $sts_form_editvideo_form
	'title' => '<span class="glyphicon glyphicon-film"></span> Edit video',
	'action' => 'exp_editvideo.php',
	//'actionextras' => 'disabled',
	'cancel' => 'exp_listvideo.php',
	'name' => 'editvideo',
	'okbutton' => 'Save Changes to video',
	'cancelbutton' => 'Back to videos',
		'layout' => '
		%VIDEO_CODE%
	<div class="form-group">
		<div class="col-sm-12">
			<div class="form-group">
				<label for="VIDEO_NAME" class="col-sm-2 control-label">#VIDEO_NAME#</label>
				<div class="col-sm-4">
					%VIDEO_NAME%
				</div>
				<div class="col-sm-6">
					<label>Name to be seen on menus</label>
				</div>
			</div>
			<div class="form-group">
				<label for="VIDEO_FILE" class="col-sm-2 control-label">#VIDEO_FILE#</label>
				<div class="col-sm-4">
					%VIDEO_FILE%
				</div>
				<div class="col-sm-6">
					<label>File name of the video.<br>If the file is missing, it will not be shown in the menu.</label>
				</div>
			</div>
			<div class="form-group">
				<label for="ON_PAGE" class="col-sm-2 control-label">#ON_PAGE#</label>
				<div class="col-sm-4">
					%ON_PAGE%
				</div>
				<div class="col-sm-6">
					<label>Appears on this page (hint exp_SOMETHING.php)<br>Leave blank for all pages</label>
				</div>
			</div>
			<div class="form-group">
				<label for="VIDEO_DESCRIPTION" class="col-sm-2 control-label">#VIDEO_DESCRIPTION#</label>
				<div class="col-sm-8">
					%VIDEO_DESCRIPTION%
				</div>
			</div>
		</div>
		<div class="col-sm-6">
		</div>
	</div>
	
	'
);

//! Field Specifications - For use with sts_form

$sts_form_add_report_fields = array( //! $sts_form_add_report_fields
	'REPORT_TYPE' => array( 'label' => 'Report Type', 'format' => 'enum' ),
	'REPORT_ICON' => array( 'label' => 'Report Icon', 'format' => 'htmltext' ),
	//! SCR# 559 - Need to mark the fields as required.
	'REPORT_NAME' => array( 'label' => 'Report Name', 'format' => 'text', 'extras' => 'required' ),
	'REPORT_FILE' => array( 'label' => 'Report File', 'format' => 'text', 'extras' => 'required' ),
	'ON_PAGE' => array( 'label' => 'On Page', 'format' => 'text' ),
	'REPORT_DESCRIPTION' => array( 'label' => 'Description', 'format' => 'text' ),
	'RESTRICT_BY' => array( 'label' => 'Restrict By', 'format' => 'enum' ),
	'REPORT_GROUP' => array( 'label' => 'Group', 'format' => 'enum' ),
);

$sts_form_edit_report_fields = array( //! $sts_form_edit_report_fields
	'REPORT_CODE' => array( 'format' => 'hidden' ),
	'REPORT_TYPE' => array( 'label' => 'Report Type', 'format' => 'enum' ),
	'REPORT_ICON' => array( 'label' => 'Report Icon', 'format' => 'htmltext' ),
	//! SCR# 559 - Need to mark the fields as required.
	'REPORT_NAME' => array( 'label' => 'Report Name', 'format' => 'text', 'extras' => 'required' ),
	'REPORT_FILE' => array( 'label' => 'Report File', 'format' => 'text', 'extras' => 'required' ),
	'ON_PAGE' => array( 'label' => 'On Page', 'format' => 'text' ),
	'REPORT_DESCRIPTION' => array( 'label' => 'Description', 'format' => 'text' ),
	'RESTRICT_BY' => array( 'label' => 'Restrict By', 'format' => 'enum' ),
	'REPORT_GROUP' => array( 'label' => 'Group', 'format' => 'enum' ),
);

$sts_form_add_video_fields = array( //! $sts_form_add_video_fields
	//! SCR# 559 - Need to mark the fields as required.
	'VIDEO_NAME' => array( 'label' => 'Video Name', 'format' => 'text', 'extras' => 'required' ),
	'VIDEO_FILE' => array( 'label' => 'Video File', 'format' => 'text', 'extras' => 'required' ),
	'ON_PAGE' => array( 'label' => 'On Page', 'format' => 'text' ),
	'VIDEO_DESCRIPTION' => array( 'label' => 'Description', 'format' => 'text' ),
);

$sts_form_edit_video_fields = array( //! $sts_form_edit_video_fields
	'VIDEO_CODE' => array( 'format' => 'hidden' ),
	//! SCR# 559 - Need to mark the fields as required.
	'VIDEO_NAME' => array( 'label' => 'Video Name', 'format' => 'text', 'extras' => 'required' ),
	'VIDEO_FILE' => array( 'label' => 'Video File', 'format' => 'text', 'extras' => 'required' ),
	'ON_PAGE' => array( 'label' => 'On Page', 'format' => 'text' ),
	'VIDEO_DESCRIPTION' => array( 'label' => 'Description', 'format' => 'text' ),
);

//! Layout Specifications - For use with sts_result

$sts_result_reports_layout = array(
	'REPORT_CODE' => array( 'format' => 'hidden' ),
	'REPORT_TYPE' => array( 'label' => 'Type', 'format' => 'text' ),
	'REPORT_NAME' => array( 'label' => 'Report Name', 'format' => 'text' ),
	'REPORT_FILE' => array( 'label' => 'File Name', 'format' => 'text' ),
	'ON_PAGE' => array( 'label' => 'On Page', 'format' => 'text' ),
	'RESTRICT_BY' => array( 'label' => 'Restrict By', 'format' => 'text' ),
	'REPORT_GROUP' => array( 'label' => 'Group', 'format' => 'text' ),
	'REPORT_DESCRIPTION' => array( 'label' => 'Description', 'format' => 'text' ),
	'CHANGED_DATE' => array( 'label' => 'Changed', 'format' => 'timestamp-s', 'length' => 90 ),
	'CHANGED_BY' => array( 'label' => 'By', 'format' => 'table',
		'table' => USER_TABLE, 'key' => 'USER_CODE', 'fields' => 'USERNAME', 'link' => 'exp_edituser.php?CODE=' )

);

$sts_result_videos_layout = array(
	'VIDEO_CODE' => array( 'format' => 'hidden' ),
	'VIDEO_NAME' => array( 'label' => 'Video Name', 'format' => 'text' ),
	'VIDEO_FILE' => array( 'label' => 'File Name', 'format' => 'text' ),
	'ON_PAGE' => array( 'label' => 'On Page', 'format' => 'text' ),
	'VIDEO_DESCRIPTION' => array( 'label' => 'Description', 'format' => 'text' ),
	'CHANGED_DATE' => array( 'label' => 'Changed', 'format' => 'timestamp-s', 'length' => 90 ),
	'CHANGED_BY' => array( 'label' => 'By', 'format' => 'table',
		'table' => USER_TABLE, 'key' => 'USER_CODE', 'fields' => 'USERNAME', 'link' => 'exp_edituser.php?CODE=' )

);

//! Edit/Delete Button Specifications - For use with sts_result

$sts_result_reports_edit = array(
	'title' => '<img src="images/setting_icon.png" alt="setting_icon" height="24"> Reports',
	'sort' => 'REPORT_NAME asc',
	'cancel' => 'index.php',
	'add' => 'exp_addreport.php',
	//'actionextras' => 'disabled',
	'addbutton' => 'Add Report',
	'cancelbutton' => 'Back',
	'rowbuttons' => array(
		array( 'url' => 'exp_editreport.php?CODE=', 'key' => 'REPORT_CODE', 'label' => 'REPORT_NAME', 'tip' => 'Edit report ', 'icon' => 'glyphicon glyphicon-edit' ),
		array( 'url' => 'exp_dupreport.php?CODE=', 'key' => 'REPORT_CODE', 'label' => 'REPORT_NAME', 'tip' => 'Duplicate report ', 'icon' => 'glyphicon glyphicon-repeat' ),
		array( 'url' => 'exp_deletereport.php?CODE=', 'key' => 'REPORT_CODE', 'label' => 'REPORT_NAME', 'tip' => 'Delete report ', 'icon' => 'glyphicon glyphicon-trash', 'confirm' => 'yes' )
	)
);

$sts_result_videos_edit = array(
	'title' => '<span class="glyphicon glyphicon-film"></span> Videos',
	'sort' => 'VIDEO_NAME asc',
	'cancel' => 'index.php',
	'add' => 'exp_addvideo.php',
	//'actionextras' => 'disabled',
	'addbutton' => 'Add Video',
	'cancelbutton' => 'Back',
	'rowbuttons' => array(
		array( 'url' => 'exp_editvideo.php?CODE=', 'key' => 'VIDEO_CODE', 'label' => 'VIDEO_NAME', 'tip' => 'Edit video ', 'icon' => 'glyphicon glyphicon-edit' ),
		array( 'url' => 'exp_dupvideo.php?CODE=', 'key' => 'VIDEO_CODE', 'label' => 'VIDEO_NAME', 'tip' => 'Duplicate video ', 'icon' => 'glyphicon glyphicon-repeat' ),
		array( 'url' => 'exp_deletevideo.php?CODE=', 'key' => 'VIDEO_CODE', 'label' => 'VIDEO_NAME', 'tip' => 'Delete video ', 'icon' => 'glyphicon glyphicon-trash', 'confirm' => 'yes' )
	)
);

?>
