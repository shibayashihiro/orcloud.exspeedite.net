<?php

// $Id: sts_alert_class.php 5030 2023-04-12 20:31:34Z duncan $
// Alert class - Cache Alerts for faster access to the home page
//! SCR# 604 - Cache Alerts on home page

// no direct access
defined('_STS_INCLUDE') or die('Restricted access');

require_once( "sts_table_class.php" );
require_once( "sts_session_class.php" );
require_once( "sts_carrier_class.php" );
require_once( "sts_driver_class.php" );
require_once( "sts_tractor_class.php" );
require_once( "sts_trailer_class.php" );
require_once( "sts_client_class.php" );
require_once( "sts_setting_class.php" );
require_once( "sts_report_class.php" );

const ALERT_TYPE_FLEET			= 'fleet';
const ALERT_TYPE_FLEET_RANDOM	= 'fleetr';
const ALERT_TYPE_PROFILES		= 'profiles';
const ALERT_TYPE_LEADS			= 'leads'; //! SCR# 769 - leads / probable duplicates

class sts_alert extends sts_table {

	private $cache_duration = 4;	// 4 hours cache life
	private $my_session;
	private $last_cached = '';
	private $setting_table;
	private $show_carriers;
	private $pivot;
	private $reports;
	private $all_types = [ALERT_TYPE_FLEET, ALERT_TYPE_FLEET_RANDOM, ALERT_TYPE_PROFILES, ALERT_TYPE_LEADS];
	private $cms_enabled;
	private $cms_salespeople_leads;

	// Constructor does not need the table name
	public function __construct( $database, $debug = false ) {

		$this->debug = $debug;
		if( $this->debug ) echo "<p>Create sts_alerts</p>";
		parent::__construct( $database, ALERT_CACHE_TABLE, $debug);
		$this->my_session = sts_session::getInstance( $database, $debug );
		$this->setting_table = sts_setting::getInstance($database, $debug);
		$this->show_carriers = ($this->setting_table->get( 'option', 'ALERT_CARRIERS_ENABLED' ) == 'true');
		$this->cms_enabled = $this->setting_table->get("option", "CMS_ENABLED") == 'true';
		$this->cms_salespeople_leads = $this->setting_table->get("option", "CMS_SALESPEOPLE_LEADS") == 'true';


		$this->pivot = $this->setting_table->get("option", "PIVOT_RM_REPORT") == 'true';
		$this->reports = sts_report::getInstance($database, $debug);
		
		if( false && $_SESSION["EXT_USERNAME"] == 'duncan' ) {
			$this->set_debug( true );
		}

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
    
    public function last_cached() {
	    if( empty($this->last_cached) &&
	    	is_array($_SESSION) &&
	    	isset($_SESSION["ALERT_LAST_CACHE"]) &&
	    	is_array($_SESSION["ALERT_LAST_CACHE"]) &&
	    	count($_SESSION["ALERT_LAST_CACHE"]) > 0 ) {
		    $this->last_cached = min($_SESSION["ALERT_LAST_CACHE"]);		    
	    }
	    
	    return (empty($this->last_cached) ? '(not cached' : ' (cached on '.date("m/d/Y h:i A", strtotime($this->last_cached))).' <a class="tip" title="Click here to update the alerts cache" href="index.php?CLEAR_CACHE"><span class="glyphicon glyphicon-refresh"></span></a>)';
    }

    //! Clear out the cache, which will trigger re-creating it.
    public function clear_cache() {
		global $_SESSION;
		
	    if( is_array($_SESSION) && isset($_SESSION["ALERT_CACHE"]) ) {
	    	$_SESSION["ALERT_CACHE"] = [];
	    	$_SESSION["ALERT_LAST_CACHE"] = [];
	    }
	    
	    $result = true;
	    
	    if( $this->my_session->in_group( EXT_GROUP_FLEET ) ) {
			$result = $this->delete_row( "ALERT_TYPE = '".ALERT_TYPE_FLEET."'" ) && $result;
	    }
	    if( $this->my_session->in_group( EXT_GROUP_RANDOM ) ) {
			$result = $this->delete_row( "ALERT_TYPE = '".ALERT_TYPE_FLEET_RANDOM."'" ) && $result;
	    }
	    if( $this->my_session->in_group( EXT_GROUP_PROFILES ) ) {
			$result = $this->delete_row( "ALERT_TYPE = '".ALERT_TYPE_PROFILES."'" ) && $result;
	    }
	    
	    if( $this->my_session->in_group( EXT_GROUP_SALES ) &&
	    	is_array($_SESSION) && isset($_SESSION['EXT_USER_CODE']) ) {
			$result = $this->delete_row( "ALERT_TYPE = '".ALERT_TYPE_LEADS."'
				AND CREATED_BY = '".$_SESSION['EXT_USER_CODE']."'") && $result;
	    }
	    
	    return $result;
	}

	//! Get cached alert HTML string, either fleet or profiles
	public function get_cache( $alert_type ) {
		global $_SESSION;

		if( $this->debug ) echo "<p>".__METHOD__.": entry $alert_type,".
			(isset($_SESSION) ? ' SESSION' : '').
			(is_array($_SESSION) && isset($_SESSION["ALERT_CACHE"]) ? ' ALERT_CACHE' : '').
			(is_array($_SESSION) && isset($_SESSION["ALERT_LAST_CACHE"]) ? ' ALERT_LAST_CACHE' : '').
			(is_array($_SESSION) && isset($_SESSION["ALERT_CACHE"]) && is_array($_SESSION["ALERT_CACHE"]) ? ' ALERT_CACHE[]' : '').
			(is_array($_SESSION) && isset($_SESSION["ALERT_CACHE"]) && isset($_SESSION["ALERT_CACHE"][$alert_type]) ? ' ALERT_CACHE['.$alert_type.']' : '').
			(is_array($_SESSION) && isset($_SESSION["ALERT_LAST_CACHE"]) && is_array($_SESSION["ALERT_LAST_CACHE"]) ? ' ALERT_LAST_CACHE[]' : '').
			(is_array($_SESSION) && isset($_SESSION["ALERT_LAST_CACHE"][$alert_type]) ? ' ALERT_LAST_CACHE['.$alert_type.'] = '.$_SESSION["ALERT_LAST_CACHE"][$alert_type] : '').
			
			"</p>";
		$output = false;
		if( false && $_SESSION["EXT_USERNAME"] == 'duncan' ) {
			echo "<pre>";
			@var_dump($alert_type, $_SESSION["ALERT_LAST_CACHE"]);
			echo "</pre>";
		}
		
		if( isset($_SESSION) &&
			isset($_SESSION["ALERT_CACHE"]) &&
			isset($_SESSION["ALERT_LAST_CACHE"]) &&
			is_array($_SESSION["ALERT_CACHE"]) &&
			isset($_SESSION["ALERT_CACHE"][$alert_type]) &&
			is_array($_SESSION["ALERT_LAST_CACHE"]) &&
			isset($_SESSION["ALERT_LAST_CACHE"][$alert_type]) &&
			strtotime($_SESSION["ALERT_LAST_CACHE"][$alert_type]) > strtotime("now -1 day") ) {
				
			$output = $_SESSION["ALERT_CACHE"][$alert_type];
			if( $this->debug ) echo "<p>".__METHOD__.": use cache</p>";
		} else		
		if( in_array($alert_type, $this->all_types )) {
			$cache = $this->fetch_rows( "ALERT_TYPE = '".$alert_type."' AND
				CREATED_DATE > DATE_SUB(NOW(), INTERVAL ".$this->cache_duration." HOUR)".
				($alert_type == ALERT_TYPE_LEADS ? " AND CREATED_BY = '".$_SESSION['EXT_USER_CODE']."'" : ''),
				"CREATED_DATE, ALERT_HTML" );
			if( false && $_SESSION["EXT_USERNAME"] == 'duncan' ) {
				echo "<pre>";
				var_dump($cache);
				echo "</pre>";
			}
			
			if( is_array($cache) && isset($cache[0]) && isset($cache[0]["ALERT_HTML"]) ) {
				if(! isset($_SESSION))
					$_SESSION = [];
				if(! isset($_SESSION["ALERT_CACHE"]) || ! is_array($_SESSION["ALERT_CACHE"])) {
					$_SESSION["ALERT_CACHE"] = [];
					$_SESSION["ALERT_LAST_CACHE"] = [];
				}
				$_SESSION["ALERT_CACHE"][$alert_type] = $output = $cache[0]["ALERT_HTML"];
				$_SESSION["ALERT_LAST_CACHE"][$alert_type] = $this->last_cached = $cache[0]["CREATED_DATE"];
				if( $this->debug ) echo "<p>".__METHOD__.": update cache</p>";
			}
		}
		
		return $output;
	}
	
	//! Put alert HTML string in cache, either fleet or profiles
	public function put_cache( $alert_type, $alert_html ) {
		
		if( $this->debug ) echo "<p>".__METHOD__.": entry, $alert_type</p>";
		$result = false;
		
		if( in_array($alert_type, [ALERT_TYPE_FLEET, ALERT_TYPE_FLEET_RANDOM,
									ALERT_TYPE_PROFILES, ALERT_TYPE_LEADS] )) {
			
			// Delete previous entry
			if( $alert_type == ALERT_TYPE_LEADS)
				$result = $this->delete_row( "ALERT_TYPE = '".$alert_type."'
					AND CREATED_BY = '".$_SESSION['EXT_USER_CODE']."'");
			else
				$result = $this->delete_row( "ALERT_TYPE = '".$alert_type."'" );
		
			// Add new one
			$result = $this->add( ["ALERT_TYPE" => $alert_type, "ALERT_HTML" => $alert_html] );
			
			$this->last_cached = '';
		}
		
		return $result;
	}
	
	//! Get alerts, using cache if they exist. If not, update cache.
	public function get_alerts() {
		$output = '';
		if( $this->my_session->in_group( EXT_GROUP_FLEET ) ) {
			$cache = $this->my_session->in_group( EXT_GROUP_RANDOM ) ? ALERT_TYPE_FLEET_RANDOM : ALERT_TYPE_FLEET;
			$fleet = $this->get_cache( $cache );
			if( $fleet == false ) {
				$driver_table = sts_driver::getInstance( $this->database, $this->debug );
				$fleet = $driver_table->alert_expired();
			
				$tractor_table = sts_tractor::getInstance( $this->database, $this->debug );
				$fleet .= $tractor_table->alert_expired();
				$this->put_cache( $cache, $fleet );
			}
			$output .= $fleet;
		}
		
		//! SCR# 617 - show R&M Report link
		$rmr =  is_array($this->reports->reports) &&
			count($this->reports->reports) > 0 &&
			array_search('R&M Report', array_column($this->reports->reports, 'REPORT_NAME')) !== false; 
		if($this->pivot && $rmr)
			$output = str_replace('<div id="RMR"></div>', ' <a class="btn btn-xs btn-info" href="exp_listinsp_report2.php"><span class="glyphicon glyphicon-wrench"></span> R&M Report</a>', $output);
		
		if( $this->my_session->in_group( EXT_GROUP_PROFILES ) ) {
			$profiles = $this->get_cache( EXT_GROUP_PROFILES );
			if( $profiles == false ) {
				$trailer_table = sts_trailer::getInstance( $this->database, $this->debug );
				$profiles = $trailer_table->alert_expired();
			
				//! SCR# 616 - allow to hide carrier alerts
				if( $this->show_carriers ) {
					$carrier_table = sts_carrier::getInstance( $this->database, $this->debug );
					$profiles .= $carrier_table->alert_expired();
				}
				$this->put_cache( ALERT_TYPE_PROFILES, $profiles );
			}
			$output .= $profiles;
		}

		//! SCR# 769 - Get probable duplicate leads
		if( $this->cms_enabled && (
				($this->my_session->in_group( EXT_GROUP_SALES ) &&
				$this->cms_salespeople_leads) ||
				$this->my_session->in_group( EXT_GROUP_ADMIN ) ) ) {
			$profiles = $this->get_cache( ALERT_TYPE_LEADS );
			if( $profiles == false ) {
				$client_table = sts_client::getInstance( $this->database, $this->debug );
				$profiles = $client_table->alert_duplicates();
			
				$this->put_cache( ALERT_TYPE_LEADS, $profiles );
			}
			$output .= $profiles;
		}
		
		return $output;
	}
	
}