<?php

// no direct access
defined('_STS_INCLUDE') or die('Restricted access');

require_once( "sts_table_class.php" );
require_once( "sts_load_class.php" );

// This class corresponds to EXP_SHIPMENT_LOAD, and links shipments to loads.
// This provides for a many to many relationship, 
// such as in the case of drop and hook (TL) or crossdock (LTL)
class sts_shipment_load extends sts_table {
		
	// Constructor does not need the table name
	public function __construct( $database, $debug = false ) {

		$this->debug = $debug;
		$this->primary_key = "SHIPMENT_LOAD_CODE";
		if( $this->debug ) echo "<p>Create sts_shipment_load</p>";
		parent::__construct( $database, SHIPMENT_LOAD_TABLE, $debug);
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

	//! add_link - add a link from a shipment to a load
	public function add_link( $shipment, $load ) {
		
		$duplicate = $this->fetch_rows( "SHIPMENT_CODE = ".$shipment." AND LOAD_CODE = ".$load, 
			"SEQUENCE_NO", "SEQUENCE_NO DESC");
		if( isset($duplicate) && is_array($duplicate) && count($duplicate) > 0 ) {
			$result = false;
		} else {
			// Work out sequence number
			$sequence_no = 1;
			$existing = $this->fetch_rows( "SHIPMENT_CODE = ".$shipment, 
				"SEQUENCE_NO", "SEQUENCE_NO DESC");
			if( isset($existing) && is_array($existing) && count($existing) > 0 )
				$sequence_no = intval($existing[0]["SEQUENCE_NO"]) + 1;
				
			$result = $this->add( array(	"LOAD_CODE" => $load, 
								"SHIPMENT_CODE" => $shipment,
								"SEQUENCE_NO" => $sequence_no) );
		}
		return $result;
	}
	
	//! get_links - Get a list for a shipment
	public function get_links( $shipment ) {
		$links = $this->fetch_rows( "SHIPMENT_CODE = ".$shipment, 
			"SHIPMENT_CODE, LOAD_CODE, SEQUENCE_NO", "SEQUENCE_NO ASC");
		return $links;
	}

	//! last_load - get the most recent load for a shipment.
	public function last_load( $shipment ) {
		$links = $this->fetch_rows( "SHIPMENT_CODE = ".$shipment." AND
		SEQUENCE_NO = (SELECT MAX(SEQUENCE_NO)
		FROM EXP_SHIPMENT_LOAD X WHERE X.SHIPMENT_CODE = EXP_SHIPMENT_LOAD.SHIPMENT_CODE)", 
			"SHIPMENT_CODE, LOAD_CODE, SEQUENCE_NO,
			(SELECT STOP_CODE FROM EXP_STOP
			WHERE EXP_STOP.LOAD_CODE = EXP_SHIPMENT_LOAD.LOAD_CODE
			AND EXP_STOP.SHIPMENT = EXP_SHIPMENT_LOAD.SHIPMENT_CODE
			AND STOP_TYPE = 'dropdock') AS DOCKED_AT", 
			"", "", "SHIPMENT_CODE, LOAD_CODE, SEQUENCE_NO");
		return $links;
	}

	//! last_docked_at - get the most recent stop docked at.
	// See also, MySQL function LAST_DOCKED_AT()
	public function last_docked_at( $shipment ) {
		$result = $this->database->get_one_row( "
			SELECT STOP_CODE, EXP_SHIPMENT_LOAD.SEQUENCE_NO
			FROM EXP_STOP, EXP_SHIPMENT_LOAD
			WHERE EXP_STOP.SHIPMENT = $shipment
			AND STOP_TYPE = 'dropdock'
			AND (SELECT BEHAVIOR FROM EXP_STATUS_CODES
			WHERE STATUS_CODES_CODE = CURRENT_STATUS) = 'complete'
			AND EXP_STOP.LOAD_CODE = EXP_SHIPMENT_LOAD.LOAD_CODE
			AND EXP_STOP.SHIPMENT = EXP_SHIPMENT_LOAD.SHIPMENT_CODE
			ORDER BY SEQUENCE_NO DESC
			LIMIT 1");
		return $result;
	}

}

class sts_shipment_load_left_join extends sts_shipment_load {

	// Constructor does not need the table name
	public function __construct( $database, $debug = false ) {

		if( $debug ) echo "<p>Create sts_stop_left_join</p>";
		parent::__construct( $database, $debug);
		$this->primary_key = "STOP_CODE";
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

	// Fetch one or more rows
	public function fetch_rows( $match = "", $fields = "*", $order = "", $limit = "", $groupby = "", $match2 = "" ) {
		global $exspeedite_db;
		if( $this->debug ) echo "<p>fetch_rows $match</p>";
		
		$result = $exspeedite_db->get_multiple_rows("SELECT $fields FROM
			(SELECT *,
				(CASE STOP_TYPE
					WHEN 'pick' THEN SHIPPER_NAME
					WHEN 'drop' THEN CONS_NAME
					ELSE STOP_NAME END
				) AS NAME, 
				(CASE STOP_TYPE
					WHEN 'pick' THEN SHIPPER_ADDR1
					WHEN 'drop' THEN CONS_ADDR1
					ELSE STOP_ADDR1 END
				) AS ADDRESS, 
				(CASE STOP_TYPE
					WHEN 'pick' THEN SHIPPER_ADDR2
					WHEN 'drop' THEN CONS_ADDR2
					ELSE STOP_ADDR2 END
				) AS ADDRESS2, 
				(CASE STOP_TYPE
					WHEN 'pick' THEN SHIPPER_CITY
					WHEN 'drop' THEN CONS_CITY
					ELSE STOP_CITY END
				) AS CITY, 
				(CASE STOP_TYPE
					WHEN 'pick' THEN SHIPPER_STATE
					WHEN 'drop' THEN CONS_STATE
					ELSE STOP_STATE END
				) AS STATE, 
				(CASE STOP_TYPE
					WHEN 'pick' THEN SHIPPER_ZIP
					WHEN 'drop' THEN CONS_ZIP
					ELSE STOP_ZIP END
				) AS ZIP_CODE,
				(CASE STOP_TYPE
					WHEN 'pick' THEN SHIPPER_CONTACT
					WHEN 'drop' THEN CONS_CONTACT
					ELSE STOP_CONTACT END
				) AS CONTACT,
				(CASE STOP_TYPE
					WHEN 'pick' THEN PICKUP_APPT
					WHEN 'drop' THEN DELIVERY_APPT
					ELSE '' END
				) AS APPT,
				(CASE STOP_TYPE 
					WHEN 'pick' THEN
						FMT_DUE(PICKUP_DATE, PICKUP_TIME_OPTION, PICKUP_TIME1, PICKUP_TIME2, '<br>', SHIPPER_ZIP)
					WHEN 'drop' THEN
						FMT_DUE(DELIVER_DATE, DELIVER_TIME_OPTION, DELIVER_TIME1, DELIVER_TIME2, '<br>', CONS_ZIP)
					ELSE DATE_FORMAT(STOP_DUE, '%m/%d<br>At %H:%i') END ) AS DUE_TS2,
				COALESCE(TRAILER, (SELECT TRAILER FROM EXP_LOAD 
					WHERE EXP_LOAD.LOAD_CODE = STOP_LOAD_CODE) ) AS TRAILER2,
				(CASE STOP_TYPE
					WHEN 'stop' THEN NULL
					ELSE (SELECT STATUS_STATE from EXP_STATUS_CODES X 
						WHERE X.STATUS_CODES_CODE = EXP_SHIPMENT.CURRENT_STATUS  LIMIT 0 , 1) 
				END) AS SHIPMENT_STATUS

			FROM
			
			(SELECT STOP_CODE, LOAD_CODE AS STOP_LOAD_CODE, SEQUENCE_NO, STOP_TYPE, SHIPMENT,
				CURRENT_STATUS AS STOP_CURRENT_STATUS, STOP_DUE, STOP_ETA,
				STOP_DISTANCE, ACTUAL_ARRIVE, ACTUAL_DEPART,
				STOP_NAME, STOP_ADDR1, STOP_ADDR2, STOP_CITY, STOP_STATE,
				STOP_ZIP, STOP_PHONE, STOP_EXT, STOP_FAX, STOP_CELL,
				STOP_CONTACT, STOP_EMAIL, TRAILER,
			(SELECT STATUS_STATE from EXP_STATUS_CODES X 
				WHERE X.STATUS_CODES_CODE = EXP_STOP.CURRENT_STATUS  LIMIT 0 , 1) AS STOP_STATUS
			FROM EXP_STOP 
			WHERE $match) TMP
			LEFT JOIN
			EXP_SHIPMENT
			ON SHIPMENT_CODE = SHIPMENT
			".($order <> "" ? "ORDER BY $order" : "")." ) EXP_SHIPMENT_LOAD" );

		if( $this->debug ) {
			echo "<p>result for $this->table_name = </p>
			<pre>";
			var_dump($result);
			echo "</pre>";
		}
		return $result;
	}


}



//! Layout Specifications - For use with sts_result

$sts_result_sl_lj_layout = array(
	'STOP_CODE' => array( 'label' => 'Stop Code', 'format' => 'hidden' ),
	'SEQUENCE_NO' => array( 'label' => 'Stop', 'format' => 'num0stop', 'align' => 'right', 'length' => 40 ),
	'STOP_TYPE' => array( 'label' => 'Type', 'format' => 'hidden' ),
	'STOP_STATUS' => array( 'label' => 'Status', 'format' => 'text', 'length' => 70  ),
	'STOP_LOAD_CODE' => array( 'label' => 'Load#', 'format' => 'num0nc', 'align' => 'right', 'link' => 'exp_viewload.php?CODE=' ),
	//'SHIPMENT_STATUS' => array( 'label' => 'Status', 'format' => 'text' ),
	// case format maps to (CASE STOP_TYPE
	//				WHEN 'pick' THEN PICKUP_DATE
	//				ELSE DELIVERY_DATE END) AS DUE
	//'DUE' => array( 'label' => 'Due By', 'format' => 'case',
	//	'key' => 'STOP_TYPE', 'val1' => 'pick', 'choice1' => 'PICKUP_DATE',
	//	'choice2' => 'DELIVER_DATE' ),
	'DUE_TS2' => array( 'label' => 'Due', 'format' => 'text', 'length' => 90 ),
	'STOP_ETA' => array( 'label' => 'ETA', 'format' => 'timestamp-s-eta', 'length' => 200 ),
	'PICKUP_DATE' => array( 'label' => 'Type', 'format' => 'hidden' ),
	'PICKUP_TIME_OPTION' => array( 'label' => 'Type', 'format' => 'hidden' ),
	'PICKUP_TIME1' => array( 'label' => 'Type', 'format' => 'hidden' ),
	'PICKUP_TIME2' => array( 'label' => 'Type', 'format' => 'hidden' ),
	'DELIVER_DATE' => array( 'label' => 'Type', 'format' => 'hidden' ),
	'DELIVER_TIME_OPTION' => array( 'label' => 'Type', 'format' => 'hidden' ),
	'DELIVER_TIME1' => array( 'label' => 'Type', 'format' => 'hidden' ),
	'DELIVER_TIME2' => array( 'label' => 'Type', 'format' => 'hidden' ),
	//'DUE_END' => array( 'label' => 'Due End', 'format' => 'case',
	//	'key' => 'STOP_TYPE', 'val1' => 'pick', 'choice1' => 'PICKUP_BY_END',
	//	'choice2' => 'DELIVER_BY_END' ),
	'ACTUAL_ARRIVE' => array( 'label' => 'Actual Arr', 'format' => 'timestamp-s-actual', 'length' => 100 ),
	'ACTUAL_DEPART' => array( 'label' => 'Actual Dep', 'format' => 'timestamp-s', 'length' => 100 ),
	'TRAILER2' => array( 'label' => 'Trailer', 'format' => 'table',
		'table' => TRAILER_TABLE, 'key' => 'TRAILER_CODE', 'fields' => 'UNIT_NUMBER',
		'length' => 80 ),
	//'link' => 'exp_edittrailer.php?CODE=', 
	'NAME' => array( 'label' => 'Shipper/Consignee', 'format' => 'text' ),
	'CITY' => array( 'label' => 'City', 'format' => 'text' ),
	'STATE' => array( 'label' => 'State', 'format' => 'text' ),
	'ZIP_CODE' => array( 'label' => 'Zip', 'format' => 'text' ),
	'STOP_DISTANCE' => array( 'label' => 'Distance', 'format' => 'num0', 'align' => 'right' ),
	'PIECES2' => array( 'label' => 'Items', 'format' => 'case',
		'key' => 'STOP_TYPE', 'val1' => 'stop', 'choice1' => 'NULL', 
		'choice2' => 'PIECES', 'format2' => 'num0', 'align' => 'right' ),
	//'PIECES' => array( 'label' => 'Items', 'format' => 'num0', 'align' => 'right' ),
	'PALLETS2' => array( 'label' => 'Pallets', 'format' => 'case',
		'key' => 'STOP_TYPE', 'val1' => 'stop', 'choice1' => 'NULL', 
		'choice2' => 'PALLETS', 'format2' => 'num0', 'align' => 'right' ),
	'WEIGHT2' => array( 'label' => 'Weight', 'format' => 'case',
		'key' => 'STOP_TYPE', 'val1' => 'stop', 'choice1' => 'NULL', 
		'choice2' => 'WEIGHT', 'format2' => 'num0', 'align' => 'right' ),
);

$sts_result_sl_view = array(
	'title' => '<img src="images/order_icon.png" alt="order_icon" height="24"> ## Stops',
	'sort' => 'STOP_LOAD_CODE ASC, SEQUENCE_NO ASC',
	'filters' => false,
	//'cancel' => 'index.php',
	//'add' => 'exp_addstop.php',
	//'actionextras' => 'disabled',
	'addbutton' => 'Add Stop',
	'cancelbutton' => 'Back',
	/*'rowbuttons' => array(
		array( 'url' => 'exp_editstop.php?CODE=', 'key' => 'STOP_CODE', 'label' => 'SEQUENCE_NO', 'tip' => 'Edit stop ', 'icon' => 'glyphicon glyphicon-edit' ),
	) */
);

?>
