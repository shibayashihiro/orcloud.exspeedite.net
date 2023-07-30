<?php

//! Shipment Releated KPI classes
// Moved out of sts_shipment_class.php for performance reasons.

// no direct access
defined('_STS_INCLUDE') or die('Restricted access');

require_once( "sts_shipment_class.php" );

class sts_top20 extends sts_shipment {

	// Constructor does not need the table name
	public function __construct( $database, $debug = false ) {

		if( $debug ) echo "<p>Create sts_top20</p>";
		parent::__construct( $database, $debug);
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
		if( $this->debug ) echo "<p>sts_mileage > fetch_rows m = $match<br>m2 = $match2</p>";
		$result = $this->database->get_multiple_rows("SELECT $fields FROM
			(SELECT S.BILLTO_CLIENT_CODE AS CLIENT_CODE, C.CLIENT_NAME, C.SALES_PERSON,
			COUNT(*) SHIPMENTS, 
			ROUND(SUM(S.TOTAL_CHARGES),2) REVENUE, 
			ROUND(AVG(S.TOTAL_CHARGES),2) AVG_REVENUE
			
			FROM EXP_SHIPMENT S
			LEFT JOIN EXP_CLIENT C
			ON S.BILLTO_CLIENT_CODE = C.CLIENT_CODE
			
			WHERE S.BILLING_STATUS IN (".$this->billing_behavior_state['approved'].", ".$this->billing_behavior_state['billed'].") 
			AND S.ACTUAL_DELIVERY > DATE_SUB(NOW(), INTERVAL 12 MONTH)
			GROUP BY S.BILLTO_CLIENT_CODE 
			ORDER BY 4 DESC
			LIMIT 20) EXP_SHIPMENT
			");
		
		if( $this->debug ) {
			echo "<p>result for $this->table_name = </p>
			<pre>";
			var_dump($result);
			echo "</pre>";
		}
		return $result;
	}
}

class sts_ontime_rate extends sts_shipment {

	// Constructor does not need the table name
	public function __construct( $database, $debug = false ) {

		if( $debug ) echo "<p>Create sts_ontime_rate</p>";
		parent::__construct( $database, $debug);
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
		if( $this->debug ) echo "<p>sts_ontime_rate > fetch_rows m = $match<br>m2 = $match2</p>";
		$result = $this->database->get_multiple_rows("SELECT $fields FROM
			(
				SELECT T.*,
					ROUND(T.LATE_DAYS/T.LATE, 1) AVG_LATE_DAYS,
					ROUND(T.ON_TIME/T.SHIPMENTS*100,0) AS ON_TIME_RATE
					FROM (
					SELECT DATE(ACTUAL_DELIVERY) SDATE, COUNT(*) SHIPMENTS,
					SUM( CASE WHEN DATEDIFF(S.ACTUAL_DELIVERY, S.DUE) <= 0 THEN 1
					ELSE 0 END ) ON_TIME,
					SUM( CASE WHEN DATEDIFF(S.ACTUAL_DELIVERY, S.DUE) > 0 THEN 1
					ELSE 0 END ) LATE,
					SUM(CASE WHEN DATEDIFF(S.ACTUAL_DELIVERY, S.DUE) > 0 THEN
						DATEDIFF(S.ACTUAL_DELIVERY, S.DUE) ELSE 0 END) LATE_DAYS
					FROM (
						SELECT SHIPMENT_CODE, ACTUAL_DELIVERY, 
						DUE_TIMESTAMP(DELIVER_DATE, DELIVER_TIME_OPTION, DELIVER_TIME1) DUE
						FROM EXP_SHIPMENT
						WHERE CURRENT_STATUS = ".$this->behavior_state['dropped']."
						AND ACTUAL_DELIVERY IS NOT NULL
						AND ACTUAL_DELIVERY > DATE_SUB(NOW(), INTERVAL 12 MONTH)
					) S
					GROUP BY DATE(ACTUAL_DELIVERY)
					ORDER BY 1 ASC
				) T
			) EXP_SHIPMENT
			");
		
		if( $this->debug ) {
			echo "<p>result for $this->table_name = </p>
			<pre>";
			var_dump($result);
			echo "</pre>";
		}
		return $result;
	}
}

class sts_key_acct extends sts_shipment {

	// Constructor does not need the table name
	public function __construct( $database, $debug = false ) {

		if( $debug ) echo "<p>Create sts_key_acct</p>";
		parent::__construct( $database, $debug);
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
		if( $this->debug ) echo "<p>sts_key_acct > fetch_rows m = $match<br>m2 = $match2</p>";
		$result = $this->database->get_multiple_rows("SELECT $fields FROM
			(
				SELECT CONCAT_WS('-', YEAR(ACTUAL_DELIVERY), MONTH(ACTUAL_DELIVERY)) MN,
					S.BILLTO_CLIENT_CODE AS CLIENT_CODE, 
					COUNT(*) SHIPMENTS,
					ROUND(SUM(S.TOTAL_CHARGES),2) REVENUE
				
				FROM EXP_SHIPMENT S
				inner join
					(SELECT S.BILLTO_CLIENT_CODE, COUNT(*) SHIPMENTS
					
					FROM EXP_SHIPMENT S
					WHERE S.BILLING_STATUS IN (".$this->billing_behavior_state['approved'].", ".$this->billing_behavior_state['billed'].") 
					AND S.ACTUAL_DELIVERY > DATE_SUB(NOW(), INTERVAL 24 MONTH)
					GROUP BY S.BILLTO_CLIENT_CODE 
					ORDER BY 2 DESC
					LIMIT 5) c
				on c.BILLTO_CLIENT_CODE = S.BILLTO_CLIENT_CODE
						
				WHERE S.BILLING_STATUS IN (".$this->billing_behavior_state['approved'].", ".$this->billing_behavior_state['billed'].")
				and S.BILLTO_CLIENT_CODE is not null
				AND S.ACTUAL_DELIVERY > DATE_SUB(NOW(), INTERVAL 24 MONTH)
				GROUP BY CONCAT_WS('-', YEAR(ACTUAL_DELIVERY), MONTH(ACTUAL_DELIVERY)), S.BILLTO_CLIENT_CODE
				ORDER BY CONCAT_WS('-', YEAR(ACTUAL_DELIVERY), MONTH(ACTUAL_DELIVERY)) ASC
			) EXP_SHIPMENT
			");
		
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

$sts_result_top20_layout = array( //! $sts_result_top20_layout
	'CLIENT_CODE' => array( 'label' => 'Client Name', 'format' => 'table',
		'table' => CLIENT_TABLE, 'key' => 'CLIENT_CODE', 'fields' => 'CLIENT_NAME', 'link' => 'exp_editclient.php?CODE=' ),
	'SALES_PERSON' => array( 'label' => 'Sales Person', 'format' => 'table',
		'table' => USER_TABLE, 'key' => 'USER_CODE', 'fields' => 'USERNAME', 'link' => 'exp_edituser.php?CODE=' ),
	'SHIPMENTS' => array( 'label' => '#Shipments', 'format' => 'number', 'align' => 'right' ),
	'REVENUE' => array( 'label' => 'Revenue', 'format' => 'num2', 'align' => 'right' ),
	'AVG_REVENUE' => array( 'label' => 'Avg Revenue', 'format' => 'num2', 'align' => 'right' ),
);

$sts_result_ontime_rate_layout = array( //! $sts_result_ontime_rate_layout
	'SDATE' => array( 'label' => 'Deliver Date', 'format' => 'date' ),
	'SHIPMENTS' => array( 'label' => '# Shipments', 'format' => 'number', 'align' => 'right' ),
	'ON_TIME' => array( 'label' => '# On Time', 'format' => 'number', 'align' => 'right' ),
	'LATE' => array( 'label' => '# Late', 'format' => 'number', 'align' => 'right' ),
	'AVG_LATE_DAYS' => array( 'label' => 'Avg Days Late', 'format' => 'number', 'align' => 'right' ),
	'ON_TIME_RATE' => array( 'label' => 'On Time %', 'format' => 'number', 'align' => 'right' ),
);

$sts_result_key_acct_layout = array( //! $sts_result_key_acct_layout
	'MN' => array( 'label' => 'Month', 'format' => 'text' ),
	'CLIENT_CODE' => array( 'label' => 'Client Name', 'format' => 'table',
		'table' => CLIENT_TABLE, 'key' => 'CLIENT_CODE', 'fields' => 'CLIENT_NAME', 'link' => 'exp_editclient.php?CODE=' ),
	'SHIPMENTS' => array( 'label' => '# Shipments', 'format' => 'number', 'align' => 'right' ),
	'REVENUE' => array( 'label' => 'Revenue', 'format' => 'num2', 'align' => 'right' ),
);

//! Edit/Delete Button Specifications - For use with sts_result

$sts_result_top20_view = array( //! $sts_result_top20_view
	'title' => '<img src="images/user_icon.png" alt="user_icon" height="24"> Top 20 Clients Over The Past 12 Months',
	'filters' => false,
	//'cancel' => 'index.php',
	//'add' => 'exp_addshipment.php',
	//'actionextras' => 'disabled',
	//'addbutton' => 'Add Shipment',
	'cancelbutton' => 'Back',
);

$sts_result_ontime_rate_view = array( //! $sts_result_ontime_rate_view
	'title' => '<img src="images/order_icon.png" alt="order_icon" height="24"> On Time Delivery Rate Over The Past 12 Months',
	'filters' => false,
	//'cancel' => 'index.php',
	//'add' => 'exp_addshipment.php',
	//'actionextras' => 'disabled',
	//'addbutton' => 'Add Shipment',
	'cancelbutton' => 'Back',
);

$sts_result_key_acct_view = array( //! $sts_result_key_acct_view
	'title' => '<img src="images/user_icon.png" alt="order_icon" height="24"> Key Accounts Over The Past 24 Months',
	'filters' => false,
	//'cancel' => 'index.php',
	//'add' => 'exp_addshipment.php',
	//'actionextras' => 'disabled',
	//'addbutton' => 'Add Shipment',
	'cancelbutton' => 'Back',
);


?>
