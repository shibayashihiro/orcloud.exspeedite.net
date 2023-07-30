<?php

// $Id: sts_card_manual_class.php 3884 2020-01-30 00:21:42Z duncan $
// - Fuel card MANUAL class

// no direct access
defined('_STS_INCLUDE') or die('Restricted access');
require_once( "sts_card_transport_class.php" );


class sts_card_manual extends sts_card_transport {

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

	//! Fetch a file, return it as a string
	public function manual_get_contents( $filename, $delete = false ) {
		if( $this->debug ) echo "<p>".__METHOD__.": Get $filename, delete=".($delete ? "true" : "false")."</p>";
		$this->log_event( __METHOD__.": Get $filename, delete=".($delete ? "true" : "false"), EXT_ERROR_DEBUG);

		$result = file_get_contents($filename);
		
		if( $this->debug ) echo "<p>".__METHOD__.": return ".($result ? strlen($result)." chars" : "false (".$this->last_error.")" )."</p>";
		$this->log_event( __METHOD__.": return ".($result ? strlen($result)." chars" : "false (".$this->last_error.")"), EXT_ERROR_DEBUG);
		return $result;
	}
	
}