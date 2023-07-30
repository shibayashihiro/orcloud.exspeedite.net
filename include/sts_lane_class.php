<?php

// $Id: sts_lane_class.php 5090 2023-04-27 18:23:45Z duncan $
//! SCR# 514 - lane class - for pulling lane information

// no direct access
defined('_STS_INCLUDE') or die('Restricted access');

require_once( "sts_table_class.php" );
require_once( "sts_setting_class.php" );

class sts_lane extends sts_table {
	private $setting_table;
	private $multi_company;
	private $nolink = false;

	// Constructor does not need the table name
	public function __construct( $database, $debug = false ) {

		$this->debug = $debug;
		$this->primary_key = "SHIPMENT_CODE";
		if( $this->debug ) echo "<p>Create sts_lane</p>";
		parent::__construct( $database, SHIPMENT_TABLE, $debug);
		$this->setting_table = sts_setting::getInstance( $database, $this->debug );
		$this->multi_company = ($this->setting_table->get( 'option', 'MULTI_COMPANY' ) == 'true');
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
    
    public function nolink() {
	    $this->nolink = true;
    }	

	public function get_clients( $match ) {
		$clients = false;
		if( isset($_SESSION['CACHE_LANE_MATCH1']) && $_SESSION['CACHE_LANE_MATCH1'] == $match &&
			isset($_SESSION['CACHE_LANE_CLIENTS']) ) {
			$clients = $_SESSION['CACHE_LANE_CLIENTS'];
		} else {
			$result = $this->database->get_multiple_rows("
				SELECT distinct c.CLIENT_CODE, c.CLIENT_NAME
				FROM exp_shipment s, EXP_CLIENT c
				where s.billing_status = 65
				and s.current_status = 20
				and s.SHIPPER_CITY is not null
				and s.SHIPPER_STATE is not null
				and s.CONS_CITY is not null
				and s.CONS_STATE is not null
				and c.CLIENT_CODE = s.BILLTO_CLIENT_CODE
				".($this->multi_company ? "and coalesce(s.SS_NUMBER, '') != ''" : "")."
				".($match <> "" ? "and $match" : "")."
				order by 2 asc");
			if( isset($result) && is_array($result) && count($result) > 0 ) {
				$clients = array();
				foreach( $result as $row ) {
					$clients[$row["CLIENT_CODE"]] = $row["CLIENT_NAME"];
				}
			}

			$_SESSION['CACHE_LANE_MATCH1'] = $match;		// Cache in session variables
			$_SESSION['CACHE_LANE_CLIENTS'] = $clients;
		}
		return $clients;
	}

	public function get_pickup( $match ) {
		$pickups = false;
		if( isset($_SESSION['CACHE_LANE_MATCH2']) && $_SESSION['CACHE_LANE_MATCH2'] == $match &&
			isset($_SESSION['CACHE_LANE_PICKUPS']) ) {
			$pickups = $_SESSION['CACHE_LANE_PICKUPS'];
		} else {
			$result = $this->database->get_multiple_rows("
				SELECT distinct concat(s.SHIPPER_CITY,' / ',s.SHIPPER_STATE) as PICKUP
				FROM exp_shipment s, EXP_CLIENT c
				where s.billing_status = 65
				and s.current_status = 20
				and s.SHIPPER_CITY is not null
				and s.SHIPPER_STATE is not null
				and s.CONS_CITY is not null
				and s.CONS_STATE is not null
				and c.CLIENT_CODE = s.BILLTO_CLIENT_CODE
				".($this->multi_company ? "and coalesce(s.SS_NUMBER, '') != ''" : "")."
				".($match <> "" ? "and $match" : "")."
				order by 1 asc");
			if( isset($result) && is_array($result) && count($result) > 0 ) {
				$pickups = array();
				foreach( $result as $row ) {
					$pickups[] = $row["PICKUP"];
				}
			}

			$_SESSION['CACHE_LANE_MATCH2'] = $match;		// Cache in session variables
			$_SESSION['CACHE_LANE_PICKUPS'] = $pickups;
		}
		return $pickups;
	}

	public function get_deliver( $match ) {
		$deliveries = false;
		if( isset($_SESSION['CACHE_LANE_MATCH3']) && $_SESSION['CACHE_LANE_MATCH3'] == $match &&
			isset($_SESSION['CACHE_LANE_DELIVERIES']) ) {
			$deliveries = $_SESSION['CACHE_LANE_DELIVERIES'];
		} else {
			$result = $this->database->get_multiple_rows("
				SELECT distinct concat(s.CONS_CITY,' / ',s.CONS_STATE) as DELIVER
				FROM exp_shipment s, EXP_CLIENT c
				where s.billing_status = 65
				and s.current_status = 20
				and s.SHIPPER_CITY is not null
				and s.SHIPPER_STATE is not null
				and s.CONS_CITY is not null
				and s.CONS_STATE is not null
				and c.CLIENT_CODE = s.BILLTO_CLIENT_CODE
				".($this->multi_company ? "and coalesce(s.SS_NUMBER, '') != ''" : "")."
				".($match <> "" ? "and $match" : "")."
				order by 1 asc");
			if( isset($result) && is_array($result) && count($result) > 0 ) {
				$deliveries = array();
				foreach( $result as $row ) {
					$deliveries[] = $row["DELIVER"];
				}
			}

			$_SESSION['CACHE_LANE_MATCH3'] = $match;		// Cache in session variables
			$_SESSION['CACHE_LANE_DELIVERIES'] = $deliveries;
		}
		return $deliveries;
	}

	// Fetch one or more rows
	public function fetch_rows( $match = "", $fields = "*", $order = "", $limit = "", $groupby = "", $match2 = "" ) {
		if( $this->debug ) echo "<p>".__METHOD__.": $match</p>";
		
		//! SCR# 541 - allow sort by column
		$default_order = "s.SHIPPER_STATE asc, s.SHIPPER_CITY asc, s.CONS_STATE asc, s.CONS_CITY asc, s.SS_NUMBER asc";
		
		$order = empty($order) ? $default_order : $order;
		
		$result = $this->database->get_multiple_rows("SELECT $fields FROM
			(select s.SHIPMENT_CODE, s.SS_NUMBER, s.CLIENT_NAME, s.CONSOLIDATE_NUM,
			s.PICKUP_DATE, s.SHIPPER_CITY, s.SHIPPER_STATE,
			
			s.DELIVER_DATE, s.CONS_CITY, s.CONS_STATE,
			s.CUST_RATE, s.CUST_CURR,
			
			GROUP_CONCAT(case when coalesce(l.driver,0) > 0 then 'driver'
			else 'carrier'
			end order by 1 asc SEPARATOR ' / ') as DC,

			GROUP_CONCAT(l.load_code ORDER BY l.load_code ASC SEPARATOR ' / ') loads,

		    GROUP_CONCAT(case when coalesce(l.carrier,0) > 0 then
		    ".($this->nolink ? "(select coalesce(CARRIER_NAME, '')" :
		    	"(select coalesce(concat('<a href=\"exp_editcarrier.php?CODE=',l.carrier,
		    	 '\">',CARRIER_NAME,'</a>'), '')")." from exp_carrier where
					carrier_code = l.carrier)
			else '' end order by 1 asc SEPARATOR ' / ') as CARRIER,
		
		     case when coalesce(s.CONSOLIDATE_NUM,0) = 0 then
				ROUND(sum(COALESCE(l.CARRIER_TOTAL,0)),2)
		     when coalesce(s.CONSOLIDATE_NUM,0) = s.SHIPMENT_CODE then
		    	ROUND(sum(COALESCE(l.CARRIER_TOTAL,0)),2)
			else 0 end as CEXPENSE,
			l.CURRENCY as CCURR
			
from (SELECT s.SHIPMENT_CODE, s.SS_NUMBER, s.CONSOLIDATE_NUM,
			s.PICKUP_DATE, s.SHIPPER_CITY, s.SHIPPER_STATE,
			
			s.DELIVER_DATE, s.CONS_CITY, s.CONS_STATE,
            
			(select COALESCE(b.TOTAL,0)
			from EXP_CLIENT_BILLING b
			where b.SHIPMENT_ID = s.shipment_code) as CUST_RATE,
		    (select b.CURRENCY
			from EXP_CLIENT_BILLING b
			where b.SHIPMENT_ID = s.shipment_code) as CUST_CURR, c.CLIENT_NAME
		    		    
		FROM EXP_SHIPMENT s, EXP_CLIENT c
		where s.billing_status = 65
		and s.current_status = 20
		and s.SHIPPER_CITY is not null
		and s.SHIPPER_STATE is not null
		and s.CONS_CITY is not null
		and s.CONS_STATE is not null
		and c.CLIENT_CODE = s.BILLTO_CLIENT_CODE
		".($this->multi_company ? "and coalesce(s.SS_NUMBER, '') != ''" : "")."
		".($match <> "" ? "and $match" : "")."
		
		order by SS_NUMBER asc) s

		LEFT JOIN exp_shipment_load shl
        ON s.shipment_code = shl.shipment_code
        
        LEFT JOIN  EXP_LOAD l   		
		ON shl.load_code = l.load_code
 		and l.current_status <> 13
		and (coalesce(l.driver,0) > 0 OR coalesce(l.carrier,0) > 0)
		
		group by s.SHIPMENT_CODE, s.SS_NUMBER, s.CLIENT_NAME
		order by $order ) s" );

		//and s.OFFICE_CODE = 2
		//AND s.PICKUP_DATE > DATE_SUB(CURDATE(), INTERVAL 2 MONTH)
		//and s.SS_NUMBER like 'MB8477%'
		//limit 200

		if( strpos($fields, 'SQL_CALC_FOUND_ROWS') !== false) {
			$result1 = $this->database->get_one_row( "SELECT FOUND_ROWS() AS FOUND" );
			$this->found_rows = is_array($result1) && isset($result1["FOUND"]) ? $result1["FOUND"] : 0;
			if( $this->debug ) echo "<p>".__METHOD__.": found_rows = $this->found_rows</p>";

			$result2 = $this->database->get_one_row( "SELECT COUNT(*) AS CNT
						FROM exp_shipment s
						where s.billing_status = 65
						and s.current_status = 20
						and s.SHIPPER_CITY is not null
						and s.SHIPPER_STATE is not null
						and s.CONS_CITY is not null
						and s.CONS_STATE is not null
						".($this->multi_company ? "and coalesce(s.SS_NUMBER, '') != ''" : "") );
			$this->total_rows = is_array($result2) && isset($result2["CNT"]) ? $result2["CNT"] : 0;
			if( $this->debug ) echo "<p>".__METHOD__.": total_rows = $this->total_rows</p>";
		}

		if( $this->debug ) {
			echo "<p>".__METHOD__.": result for $this->table_name = </p>
			<pre>";
			var_dump($result);
			echo "</pre>";
		}
		return $result;
	}
}

//! Layout Specifications - For use with sts_result

$sts_result_lane_layout = array(
	'SHIPMENT_CODE' => array( 'label' => 'Shipment#', 'format' => 'text', 'link' => 'exp_addshipment.php?CODE=', 'align' => 'right', 'searchable' => true ),
	'SS_NUMBER' => array( 'label' => 'Office#', 'format' => 'text', 'link' => 'exp_addshipment.php?CODE=', 'align' => 'right', 'searchable' => true ),
	'CLIENT_NAME' => array( 'label' => 'Client', 'format' => 'text' ),
	'DC' => array( 'label' => 'DC', 'format' => 'text' ),
	'CARRIER' => array( 'label' => 'Carrier', 'format' => 'text'),
	'CUST_RATE' => array( 'label' => 'Cust$', 'format' => 'num2', 'align' => 'right' ),
	'CUST_CURR' => array( 'label' => 'Curr', 'format' => 'text' ),
	'CEXPENSE' => array( 'label' => 'Carrier$', 'format' => 'num2', 'align' => 'right' ),
	'CCURR' => array( 'label' => 'Curr', 'format' => 'text' ),

	'PICKUP_DATE' => array( 'label' => 'Pickup', 'format' => 'date' ),
	'SHIPPER_CITY' => array( 'label' => 'City', 'format' => 'text', 'searchable' => true ),
	'SHIPPER_STATE' => array( 'label' => 'State', 'format' => 'text', 'searchable' => true ),
	
	'DELIVER_DATE' => array( 'label' => 'Deliver', 'format' => 'date' ),
	'CONS_CITY' => array( 'label' => 'City', 'format' => 'text', 'searchable' => true ),
	'CONS_STATE' => array( 'label' => 'State', 'format' => 'text', 'searchable' => true ),
	
	//'CMDTY' => array( 'label' => 'Cmdty', 'format' => 'text', 'searchable' => true ),
	'CMDTY' => array( 'label' => 'Cmdty', 'format' => 'text',
		'snippet' => "(SELECT GROUP_CONCAT(distinct c.COMMODITY_DESCRIPTION ORDER BY DETAIL_CODE ASC SEPARATOR ' / ')  
		FROM exp_detail d, exp_commodity c
		where d.SHIPMENT_CODE = s.SHIPMENT_CODE and c.COMMODITY_CODE = d.COMMODITY)", 'searchable' => false  ), // turn off searchable
);

//! Edit/Delete Button Specifications - For use with sts_result

$sts_result_lane_edit = array(
	'title' => '<img src="images/order_icon.png" alt="order_icon" height="24"> Lanes',
	//'sort' => 'SOURCE asc, STATUS asc',
	'cancel' => 'exp_listshipment.php',
	//'add' => 'exp_addstatus_codes.php',
	//'actionextras' => 'disabled',
	//'addbutton' => 'Add Status Code',
	'cancelbutton' => 'Back',
);


?>
