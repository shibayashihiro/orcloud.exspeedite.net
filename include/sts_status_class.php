<?php

// no direct access
defined('_STS_INCLUDE') or die('Restricted access');

require_once( "sts_table_class.php" );

class sts_status extends sts_table {

	// Constructor does not need the table name
	public function __construct( $database, $debug = false ) {

		$this->debug = $debug;
		$this->primary_key = "STATUS_CODE";
		if( $this->debug ) echo "<p>Create sts_status</p>";
		parent::__construct( $database, STATUS_TABLE, $debug);
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

	public function add_load_status( $pk, $comment = "", $lat = false, $lon = false ) {
		if( $this->debug ) echo "<p>sts_status > add_load_status: $comment</p>";
		$values = array( 'ORDER_CODE' => $pk, 
			'-STATUS_STATE' => "(SELECT CURRENT_STATUS FROM EXP_LOAD WHERE LOAD_CODE = $pk)",
			'-ZONE' => "(SELECT CURRENT_ZONE FROM EXP_LOAD WHERE LOAD_CODE = $pk)",
			'SOURCE_TYPE' => 'load' );
		if( $comment <> "" ) $values['COMMENTS'] = $this->trim_to_fit( "COMMENTS", $comment );
		if( $lat !== false && $lon !== false && is_numeric($lat) && is_numeric($lon) ) {
			$values['LAT'] = floatval($lat);
			$values['LON'] = floatval($lon);
		}
		return $this->add( $values );
	}

	public function add_shipment_status( $pk, $comment = "", $lat = false, $lon = false, $load = false ) {
		if( $this->debug ) echo "<p>sts_status > add_shipment_status: $comment</p>";
		$values = array( 'ORDER_CODE' => $pk, 
			'-STATUS_STATE' => "(SELECT CURRENT_STATUS FROM EXP_SHIPMENT WHERE SHIPMENT_CODE = $pk)",
			'-BILLING_STATUS' => "(SELECT BILLING_STATUS FROM EXP_SHIPMENT WHERE SHIPMENT_CODE = $pk)",
			'-ZONE' => "(SELECT COALESCE(EXP_LOAD.CURRENT_ZONE,0) AS CURRENT_ZONE
				FROM EXP_LOAD, EXP_SHIPMENT
				WHERE SHIPMENT_CODE = $pk AND EXP_SHIPMENT.LOAD_CODE = EXP_LOAD.LOAD_CODE)",
			'SOURCE_TYPE' => 'shipment' );
		if( $comment <> "" ) $values['COMMENTS'] = $this->trim_to_fit( "COMMENTS", $comment );
		if( $lat !== false && $lon !== false && is_numeric($lat) && is_numeric($lon) ) {
			$values['LAT'] = floatval($lat);
			$values['LON'] = floatval($lon);
		}
		if( $load !== false && is_numeric($load))
			$values['LOAD_CODE'] = intval($load);
		return $this->add( $values );
	}

}

//! Layout Specifications - For use with sts_result

$sts_result_status_layout = array(
	//! SCR# 719 - adjusted the date column.
	'CREATED_DATE' => array( 'label' => 'Created', 'format' => 'timestamp', 'length' => 140 ),
	'STATUS_CODE' => array( 'format' => 'hidden' ),
	'STATUS_STATE' => array( 'label' => 'Status', 'format' => 'table',
		'table' => STATUS_CODES_TABLE, 'key' => 'STATUS_CODES_CODE', 'fields' => 'STATUS_STATE' ),
	'STATUS_STOP' => array( 'label' => 'Stop#', 'format' => 'number', 'align' => 'right' ),
	'SHIPMENT' => array( 'label' => 'Ship#', 'format' => 'number', 'align' => 'right',
		'link' => 'exp_addshipment.php?CODE=' ),
	'ZONE' => array( 'label' => 'Zone', 'format' => 'text' ),
	'COMMENTS' => array( 'label' => 'Comments', 'format' => 'text' ),
	'LAT' => array( 'label' => 'Lat', 'format' => 'number', 'align' => 'right' ),
	'LON' => array( 'label' => 'Lon', 'format' => 'number', 'align' => 'right' ),
	'CREATED_BY' => array( 'label' => 'Created By', 'format' => 'table',
		'table' => USER_TABLE, 'key' => 'USER_CODE', 'fields' => 'USERNAME', 'link' => 'exp_edituser.php?CODE=' ),

);

$sts_result_shipment_status_layout = array(
	'STATUS_CODE' => array( 'format' => 'hidden' ),
	//! SCR# 719 - adjusted the date column.
	'CREATED_DATE' => array( 'label' => 'Created', 'format' => 'timestamp', 'length' => 140 ),
	'STATUS_STATE' => array( 'label' => 'Status', 'format' => 'table',
		'table' => STATUS_CODES_TABLE, 'key' => 'STATUS_CODES_CODE', 'fields' => 'STATUS_STATE' ),
	'BILLING_STATUS' => array( 'label' => 'Billing', 'format' => 'table',
		'table' => STATUS_CODES_TABLE, 'key' => 'STATUS_CODES_CODE', 'fields' => 'STATUS_STATE' ),
	'ZONE' => array( 'label' => 'Zone', 'format' => 'text' ),
	'LOAD_CODE' => array( 'label' => 'Load#', 'format' => 'num0nc', 'link' => 'exp_viewload.php?CODE=', 'align' => 'right' ),
	'COMMENTS' => array( 'label' => 'Comments', 'format' => 'text' ),
	'CREATED_BY' => array( 'label' => 'Created By', 'format' => 'table',
		'table' => USER_TABLE, 'key' => 'USER_CODE', 'fields' => 'USERNAME', 'link' => 'exp_edituser.php?CODE=' ),

);

//! Edit/Delete Button Specifications - For use with sts_result

$sts_result_status_edit = array(
	'title' => '<img src="images/status_codes_icon.png" alt="status_icon" height="24"> Status History',
	'sort' => 'CREATED_DATE asc, STATUS_CODE asc',
	//'cancel' => 'index.php',
	//'add' => 'exp_addstatus.php',
	//'actionextras' => 'disabled',
	'addbutton' => 'Add Status Code',
	'cancelbutton' => 'Back',
	//'rowbuttons' => array(
	//	array( 'url' => 'exp_editstatus.php?CODE=', 'key' => 'status_CODE', 'label' => 'SLIP_NUMBER', 'tip' => 'Edit status ', 'icon' => 'glyphicon glyphicon-edit' )
	//)
);


?>
