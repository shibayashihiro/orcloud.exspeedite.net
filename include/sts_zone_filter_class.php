<?php

// no direct access
defined('_STS_INCLUDE') or die('Restricted access');

require_once( "sts_table_class.php" );

class sts_zone_filter extends sts_table {

	// Constructor does not need the table name
	public function __construct( $database, $debug = false ) {

		$this->debug = $debug;
		$this->primary_key = "ZONE_FILTER_CODE";
		if( $this->debug ) echo "<p>Create sts_zone_filter</p>";
		parent::__construct( $database, ZONE_FILTER_TABLE, $debug);
	}

	// Find zone filters that match a ZIP code
	public function match_filter( $zip ) {
		global $exspeedite_db;
		
		// Get State
		$status = $exspeedite_db->get_one_row("select State
			FROM zipcodes
			WHERE ZipCode = '".$zip."'
			AND PrimaryRecord = 'P'");
		$zip_state = $status ? $status[0]['State'] : '';
		
		$zip_prefix = substr($zip, 0, 3);
		// Search zone filters, return list of names
		$result = $this->fetch_rows("(ZF_TYPE = 'State' AND ZF_VALUE = '".$zip_state."')
			OR (ZF_TYPE = 'Prefix' AND ZF_VALUE = '".$zip_prefix."')
			OR (ZF_TYPE = 'ZIP Code' AND ZF_VALUE = '".$zip."')", "ZF_NAME");
		
		return $result;
	}

	public function suggest( $query ) {
	
		if( $this->debug ) echo "<p>sts_zone_filter > suggest $query</p>";
		require_once( "include/sts_zip_class.php" );

		$zip_table = new sts_zip($this->database, $this->debug);
		$result1 = $zip_table->suggest( $query );
		
		$result2 = $this->fetch_rows( "ZF_NAME like '".$query."%'", "DISTINCT ZF_NAME AS ZipCode, 'ZONE' AS CityMixedCase, '' AS State", "", "0, 20" );
		
		$result = array_merge($result2, $result1);
		
		return $result;
	}

}


//! Layout Specifications - For use with sts_result

$sts_result_zone_filters_layout = array(
	'NAME' => array( 'label' => 'NAME', 'format' => 'text' ),
	'TYPE' => array( 'label' => 'TYPE', 'format' => 'text' ),
	'TYPE' => array( 'label' => 'TYPE', 'format' => 'text' ),
);

//! Edit/Delete Button Specifications - For use with sts_result

$sts_result_zone_filters_edit = array(
	'title' => '<img src="images/zone_filter_icon.png" alt="zone_filter_icon" height="24"> Loads',
	'sort' => 'LOAD_CODE asc',
	'cancel' => 'index.php',
	'add' => 'exp_addzone_filter.php',
	//'actionextras' => 'disabled',
	'addbutton' => 'Add Load',
	'cancelbutton' => 'Back',
	'rowbuttons' => array(
		array( 'url' => 'exp_addzone_filter.php?CODE=', 'key' => 'LOAD_CODE', 'label' => 'LOAD_CODE', 'tip' => 'Edit zone_filter ', 'icon' => 'glyphicon glyphicon-edit' ),
		array( 'url' => 'exp_cancelzone_filter.php?CODE=', 'key' => 'LOAD_CODE', 'label' => 'LOAD_CODE', 'tip' => 'Cancel zone_filter ', 'icon' => 'glyphicon glyphicon-trash', 'confirm' => 'yes' )
	)
);


?>
