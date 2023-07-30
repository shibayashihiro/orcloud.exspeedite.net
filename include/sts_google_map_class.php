<?php

// $Id$
// Google map interface. WIP

// no direct access
defined('_STS_INCLUDE') or die('Restricted access');

require_once( "sts_status_codes_class.php" );
require_once( "sts_setting_class.php" );

class sts_google_map extends sts_status_codes {
	
	private $setting_table;
	private $google_api_key;
	
	// Constructor does not need the table name
	public function __construct( $database, $debug = false ) {

		if( $this->debug ) echo "<p>Create sts_google_map</p>";
		parent::__construct( $database, $debug);
		$this->setting_table = sts_setting::getInstance($exspeedite_db, $sts_debug);
		$this->google_api_key = $this->setting_table->get( 'api', 'GOOGLE_API_KEY' );
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

