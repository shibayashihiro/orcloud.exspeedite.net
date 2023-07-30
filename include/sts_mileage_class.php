<?php

// no direct access
defined('_STS_INCLUDE') or die('Restricted access');

require_once( "sts_load_class.php" );
require_once( "sts_setting_class.php" );

class sts_mileage extends sts_load {

	public $setting_table;
	private $multi_currency;
	private $multi_company;

	// Constructor does not need the table name
	public function __construct( $database, $debug = false ) {

		$this->setting_table = sts_setting::getInstance($database, $debug);
		$this->multi_currency = ($this->setting_table->get( 'option', 'MULTI_CURRENCY' ) == 'true');
		$this->multi_company = ($this->setting_table->get( 'option', 'MULTI_COMPANY' ) == 'true');

		if( $debug ) echo "<p>Create sts_mileage</p>";
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
				
		$filter = '';
		if( ! empty($match2) ) {
			switch( $match2 ) {
				case 'unbilled':
					$filter = 'COALESCE(SHIPMENTS,0) > COALESCE(BILLED,0)';
					break;
				case 'revenue':
					$filter = 'COALESCE(REVENUE,0) <= 0';
					break;
				case 'expense':
					$filter = 'COALESCE(EXPENSE,0) <= 0';
					break;
				case 'all':
				default:
					break;
			}
		}
		
		$shipment_table = sts_shipment::getInstance($this->database, $this->debug);

		$result = $this->database->get_multiple_rows("SELECT $fields FROM
			(SELECT *,
				ROUND(COALESCE(REVENUE, 0) / 
				(CASE WHEN COALESCE(ACTUAL_DISTANCE, 0) > 0 THEN ACTUAL_DISTANCE
				ELSE COALESCE(TOTAL_DISTANCE, 0) END), 2) AS REVENUE_MILE,
				ROUND(COALESCE(REVENUE, 0) / COALESCE(WEIGHT, 0), 2) AS REVENUE_POUND,
				
				ROUND(COALESCE(EXPENSE,0), 2) AS TOTAL_COST,
				ROUND(COALESCE(EXPENSE,0) / 
				(CASE WHEN COALESCE(ACTUAL_DISTANCE, 0) > 0 THEN ACTUAL_DISTANCE
				ELSE COALESCE(TOTAL_DISTANCE, 0) END), 2) AS COST_MILE,

				ROUND(COALESCE(FSC, 0) / 
				(CASE WHEN COALESCE(ACTUAL_DISTANCE, 0) > 0 THEN ACTUAL_DISTANCE
				ELSE COALESCE(TOTAL_DISTANCE, 0) END), 2) AS FSC_MILE,

				ROUND(COALESCE(FSC, 0) / 
				(CASE WHEN COALESCE(ACTUAL_DISTANCE, 0) > 0 THEN ACTUAL_DISTANCE
				ELSE COALESCE(TOTAL_DISTANCE, 0) END- COALESCE(EMPTY_DISTANCE, 0)), 2) AS FSC_LOADED_MILE,


				ROUND(COALESCE(EMPTY_DISTANCE, 0) / COALESCE(TOTAL_DISTANCE, 0) * 100,0) PCT_EMPTY,
				ROUND(COALESCE(REVENUE, 0) - COALESCE(EXPENSE,0), 2) AS MARGIN,
				ROUND(COALESCE((COALESCE(REVENUE, 0) - COALESCE(EXPENSE,0)) / COALESCE(REVENUE, 0) * 100, 0),0) AS PCT_MARGIN
				
					
				FROM (
SELECT L.LOAD_CODE, L.CURRENT_STATUS, L.COMPLETED_DATE, L.TOTAL_DISTANCE,
				L.ODOMETER_FROM, L.ODOMETER_TO,
				L.ODOMETER_TO - L.ODOMETER_FROM AS CALC_DISTANCE,
				L.EMPTY_DISTANCE, L.ACTUAL_DISTANCE,
				L.TOTAL_DISTANCE - L.EMPTY_DISTANCE AS LOADED_DISTANCE,
                L.CARRIER, L.DRIVER,
                SUM(S.PALLETS) PALLETS, SUM(S.PIECES) PIECES, SUM(S.WEIGHT) WEIGHT,

				COUNT(S.SHIPMENT_CODE) SHIPMENTS, SUM(S.BILLING_STATUS IN (".$shipment_table->billing_behavior_state['approved'].", ".$shipment_table->billing_behavior_state['billed'].")) BILLED,
				ROUND(LOAD_REVENUE(L.LOAD_CODE),2) REVENUE,
                ROUND(LOAD_EXPENSE(L.LOAD_CODE),2) EXPENSE,
				ROUND(CONVERT_LOAD_TO_HOME(L.LOAD_CODE, COALESCE(SUM(S.FUEL_SURCHARGE),0)), 2) FSC,
                LOAD_HOME_CURRENCY(L.LOAD_CODE) HOME_CURRENCY
					
				FROM (SELECT * 
					FROM EXP_LOAD L
				WHERE L.CURRENT_STATUS IN (".$this->behavior_state['complete'].", ".$this->behavior_state['approved'].", ".$this->behavior_state['billed'].")
				".($match <> "" ? "AND $match" : "")."
            	".($order <> "" ? "ORDER BY $order" : "")."
 				".($filter == "" && $limit <> "" ? "LIMIT $limit" : "")."
           	) L

				LEFT JOIN EXP_SHIPMENT S
				ON S.LOAD_CODE = L.LOAD_CODE
				
				GROUP BY L.LOAD_CODE, L.CURRENT_STATUS, L.COMPLETED_DATE,
				L.TOTAL_DISTANCE, L.ODOMETER_FROM, L.ODOMETER_TO,
				L.ODOMETER_TO - L.ODOMETER_FROM,
				L.EMPTY_DISTANCE, L.ACTUAL_DISTANCE, L.CARRIER, L.DRIVER) X
				".($filter <> "" ? "WHERE $filter" : "")."
				".($filter <> "" && $limit <> "" ? "LIMIT $limit" : "")."
			) EXP_LOAD
			" );
		
		if( strpos($fields, 'SQL_CALC_FOUND_ROWS') !== false) {

			if( $filter == "" )
				$result1 = $this->database->get_one_row( "SELECT COUNT(*) AS FOUND
					FROM EXP_LOAD L
					WHERE L.CURRENT_STATUS IN (".$this->behavior_state['complete'].", ".$this->behavior_state['approved'].", ".$this->behavior_state['billed'].")
					".($match <> "" ? "AND $match" : "")."
	            	".($order <> "" ? "ORDER BY $order" : "") );
            else	
				$result1 = $this->database->get_one_row( "SELECT COUNT(*) AS FOUND
					FROM (
					SELECT L.LOAD_CODE,
					ROUND(COALESCE(SUM(S.TOTAL_CHARGES),0), 2) REVENUE,
					COUNT(S.SHIPMENT_CODE) SHIPMENTS, SUM(S.BILLING_STATUS IN (".$shipment_table->billing_behavior_state['approved'].", ".$shipment_table->billing_behavior_state['billed'].")) BILLED,
					ROUND(COALESCE(CARRIER_TOTAL, 0),2) AS CARRIER_COST,
					ROUND(COALESCE((SELECT M.TOTAL_TRIP_PAY FROM EXP_LOAD_PAY_MASTER M
					WHERE M.LOAD_ID=L.LOAD_CODE),0),2) AS DRIVER_COST
					FROM EXP_LOAD L
	                LEFT JOIN EXP_SHIPMENT S
					ON S.LOAD_CODE = L.LOAD_CODE
					WHERE L.CURRENT_STATUS IN (".$this->behavior_state['complete'].", ".$this->behavior_state['approved'].", ".$this->behavior_state['billed'].")
					".($match <> "" ? "AND $match" : "")."
	                GROUP BY L.LOAD_CODE
	            	".($order <> "" ? "ORDER BY $order" : "").") X
					".($filter <> "" ? "WHERE $filter" : "") );

			$this->found_rows = is_array($result1) && isset($result1["FOUND"]) ? $result1["FOUND"] : 0;
			if( $this->debug ) echo "<p>found_rows = $this->found_rows</p>";

			$result2 = $this->database->get_one_row( "SELECT COUNT(*) AS CNT
				FROM EXP_LOAD L
				WHERE L.CURRENT_STATUS IN (".$this->behavior_state['complete'].", ".$this->behavior_state['approved'].", ".$this->behavior_state['billed'].")
				".($match <> "" ? "AND $match" : "") );
			$this->total_rows = is_array($result2) && isset($result2["CNT"]) ? $result2["CNT"] : 0;
			if( $this->debug ) echo "<p>total_rows = $this->total_rows</p>";
		}

		if( $this->debug ) {
			echo "<p>result for $this->table_name = </p>
			<pre>";
			var_dump($result);
			echo "</pre>";
		}
		return $result;
	}
	
	public function load_revenue( $load ) {
		$result = "";
		$check = $this->database->get_multiple_rows("
			SELECT U.*, ROUND((U.SUBTOTAL * U.RATE / 100.0), 2) AS TAX,
			ROUND((U.SUBTOTAL + (U.SUBTOTAL * U.RATE / 100.0)), 2) AS TOTAL,
			ROUND(CONVERT_TO_HOME( U.SHIPMENT_CODE, ROUND(U.SUBTOTAL, 2) ),2) CONV_SUBTOTAL,
			ROUND(CONVERT_TO_HOME( U.SHIPMENT_CODE,
				ROUND((U.SUBTOTAL + (U.SUBTOTAL * U.RATE / 100.0)), 2) ),2) CONVERTED,
			CONVERT_RATE(GET_ASOF( SHIPMENT_CODE ), SHIPMENT_CURRENCY( SHIPMENT_CODE ),
				SHIPMENT_HOME_CURRENCY( SHIPMENT_CODE ) ) ERATE,
			GET_ASOF( SHIPMENT_CODE ) ASOF

			FROM (
				SELECT S.SHIPMENT_CODE, S.BILLTO_NAME, S.SS_NUMBER, S.SUBTOTAL, S.CURRENCY,
				S.HOME_CURRENCY, S.CONS_STATE AS PROVINCE,
				CASE WHEN t.APPLICABLE_TAX = 'GST+QST' AND S.SHIPPER_STATE != S.CONS_STATE THEN 'GST'
				ELSE t.APPLICABLE_TAX END AS APPLICABLE_TAX,
				
				CASE WHEN CLIENT_EXEMPT = 1 OR SHIPMENT_EXEMPT = 1 OR t.APPLICABLE_TAX IS NULL THEN 0
				WHEN t.APPLICABLE_TAX = 'GST' THEN t.GST_RATE
				WHEN t.APPLICABLE_TAX = 'HST' THEN t.HST_RATE
				WHEN t.APPLICABLE_TAX = 'GST+QST' THEN 
				CASE WHEN S.SHIPPER_STATE = S.CONS_STATE THEN t.GST_RATE + t.QST_RATE
				ELSE t.GST_RATE END
				END AS RATE, S.BILLED
				
				FROM (
					SELECT SHIPMENT_CODE, BILLTO_NAME, SS_NUMBER, SHIPPER_STATE, SHIPPER_COUNTRY,
					CONS_STATE, CONS_COUNTRY,
					SHIPMENT_HOME_CURRENCY( SHIPMENT_CODE ) AS HOME_CURRENCY,
			    	COALESCE((SELECT TOTAL FROM EXP_CLIENT_BILLING
					    WHERE SHIPMENT_ID = EXP_SHIPMENT.SHIPMENT_CODE LIMIT 1),0) AS SUBTOTAL,
					SHIPMENT_CURRENCY( SHIPMENT_CODE ) AS CURRENCY,
					(SELECT CDN_TAX_EXEMPT FROM EXP_CLIENT
						WHERE CLIENT_CODE = EXP_SHIPMENT.BILLTO_CLIENT_CODE) AS CLIENT_EXEMPT,
					CDN_TAX_EXEMPT AS SHIPMENT_EXEMPT,
					BILLING_STATUS IN (SELECT STATUS_CODES_CODE FROM EXP_STATUS_CODES
					WHERE SOURCE_TYPE = 'shipment-bill' AND BEHAVIOR IN ('approved', 'billed')) AS BILLED
		           
			    	FROM EXP_SHIPMENT
					WHERE LOAD_CODE = $load ) S
				left join exp_canada_tax t
				on t.PROVINCE = s.CONS_STATE
				AND s.shipper_country = 'Canada'
                AND s.cons_country = 'Canada') U
		");
		if( $this->debug ) {
			echo "<pre>";
			var_dump($check);
			echo "</pre>";
		}
		if( is_array($check) && count($check) > 0 ) {
		    $result .= '<div class="alert alert-success alert-tighter">
			<h3 class="panel-title">Load Revenue Detail
			'.($this->multi_currency ? ' (Home Currency is '.$check[0]['HOME_CURRENCY'].')' : '').'</h3>
			<div class="table-responsive  bg-white">
			<table class="display table table-striped table-condensed table-bordered table-hover table-nobm" id="REVENUE">
			<thead><tr class="exspeedite-bg">
			<th>Shipment</th>
			'.($this->multi_currency ? '<th class="text-right">Subtotal</th>
			<th>Currency</th><th>CDN Tax</th>
			<th class="text-right">Tax</th>' : '').'
			<th class="text-right">Total</th>
			<th>Billed</th>
			'.($this->multi_currency ? '<th class="text-right">Exchange</th>
			<th class="text-right">'.$check[0]['HOME_CURRENCY'].' Subtotal</th>' : '').'
			<th class="text-right">'.$check[0]['HOME_CURRENCY'].' Total</th>
			</tr>
			</thead>
			<tbody>';
			$total_sub = 0;
			$total_total = 0;
			$span = 3;	//! SCR# 501 - fix column span for single company/single currency
			//if( $this->multi_company ) $span++;
			if( $this->multi_currency ) $span += 5;
			foreach($check as $row) {
				$result .= '<tr>
						<td>'.$row['SHIPMENT_CODE'].
							($this->multi_company ? ' / '.$row['SS_NUMBER']:'').'<br>'.
							$row['BILLTO_NAME'].'</td>
						'.($this->multi_currency ? '<td class="text-right">'.$row['SUBTOTAL'].'</td>
						<td>'.$row['CURRENCY'].'</td>
						<td class="text-right">'.
						(isset($row['APPLICABLE_TAX']) ?
						$row['PROVINCE'].' '.$row['APPLICABLE_TAX'].' ('.$row['RATE'].'%)' : '')
						.'</td>
						<td class="text-right">'.$row['TAX'].'</td>' : '').'
						<td class="text-right">'.$row['TOTAL'].'</td>
						<td class="text-center">'.( $row['BILLED'] ?
							'<span class="text-success lead"><span class="glyphicon glyphicon-ok"></span></span>' :
							'<span class="text-danger lead"><span class="glyphicon glyphicon-remove"></span>').'</td>
						'.($this->multi_currency ? '<td class="text-right">'.($row['ERATE'] <> 1 ? '('.date("m/d", strtotime($row['ASOF'])).') '.$row['ERATE'] : '').'</td>
						<td class="text-right"><strong>'.$row['CONV_SUBTOTAL'].'</strong></td>' : '').'
						<td class="text-right"><strong>'.$row['CONVERTED'].'</strong></td>
					</tr>';
				$total_sub += $row['CONV_SUBTOTAL'];
				$total_total += $row['CONVERTED'];
			}
	   		$result .= '<tr>
	   		<td colspan="'.$span.'"></td>
	   		'.($this->multi_currency ? '<td class="text-right"><strong>'.number_format($total_sub,2,'.','').'<//pre></td>' : '').'
	   		<td class="text-right"><strong>'.number_format($total_total,2,'.','').'<//pre></td>
	   		</tr>
	   		</tbody>
			</table>
			</div>
			</div>';
			
		}	

		return $result;
	}

	public function load_expense( $load ) {
		$result = "";
		$check = $this->database->get_multiple_rows("
			SELECT N.*,
			ROUND(ERATE * COST,2) CONVERTED
			
			FROM(
			SELECT M.*,
			CONVERT_RATE(GET_LOAD_ASOF( LOAD_CODE ), CURRENCY, HOME_CURRENCY ) ERATE,
			GET_LOAD_ASOF( LOAD_CODE ) ASOF
			
			FROM (
			SELECT LOAD_CODE, CURRENT_STATUS,
			CASE WHEN CARRIER > 0 THEN CONCAT('Carrier<br>',
			(SELECT CARRIER_NAME FROM EXP_CARRIER WHERE CARRIER_CODE = CARRIER))
			ELSE CONCAT('Driver<br>', (SELECT CONCAT(FIRST_NAME,' ',LAST_NAME) FROM EXP_DRIVER WHERE DRIVER_CODE = DRIVER))
			END AS EXPENSE,
			CASE WHEN CARRIER > 0 THEN 
				ROUND(COALESCE(CARRIER_TOTAL,0),2)
			ELSE
				ROUND(COALESCE((SELECT M.TOTAL_TRIP_PAY FROM EXP_LOAD_PAY_MASTER M
					WHERE M.LOAD_ID=L.LOAD_CODE),0),2) 
			END AS COST,
			CASE WHEN CARRIER > 0 THEN
				CURRENT_STATUS IN (SELECT STATUS_CODES_CODE FROM EXP_STATUS_CODES
					WHERE SOURCE_TYPE = 'load' AND BEHAVIOR IN ('approved', 'billed'))
			ELSE
				COALESCE((SELECT FINALIZE_STATUS IN ('finalized','paid')
					FROM EXP_DRIVER_PAY_MASTER
					WHERE LOAD_ID = L.LOAD_CODE),0) END
			AS BILLED,
			
			CURRENCY,
			LOAD_HOME_CURRENCY( LOAD_CODE ) AS HOME_CURRENCY
			        
			FROM EXP_LOAD L
			WHERE LOAD_CODE = $load
            UNION 
            SELECT LOAD_CODE, CURRENT_STATUS,
            CONCAT('Lumper<br>',
			(SELECT CARRIER_NAME FROM EXP_CARRIER WHERE CARRIER_CODE = LUMPER)) AS EXPENSE,
            LUMPER_TOTAL AS COST,
            CURRENT_STATUS IN (SELECT STATUS_CODES_CODE FROM EXP_STATUS_CODES
					WHERE SOURCE_TYPE = 'load' AND BEHAVIOR IN ('approved', 'billed')) AS BILLED,
			LUMPER_CURRENCY AS CURRENCY,
			LOAD_HOME_CURRENCY( LOAD_CODE ) AS HOME_CURRENCY
            FROM EXP_LOAD L
			WHERE LOAD_CODE = $load
			AND LUMPER > 0
			) M ) N
		");
		if( $this->debug ) {
			echo "<pre>";
			var_dump($check);
			echo "</pre>";
		}
		if( is_array($check) && count($check) > 0 ) {
		    $result .= '<div class="alert alert-danger alert-tighter">
			<h3 class="panel-title">Load Expense Detail
			'.($this->multi_currency ? ' (Home Currency is '.$check[0]['HOME_CURRENCY'].')' : '').'</h3>
			<div class="table-responsive bg-white">
			<table class="display table table-striped table-condensed table-bordered table-hover table-nobm" id="REVENUE">
			<thead><tr class="exspeedite-bg">
			<th>Expense</th>
			'.($this->multi_currency ? '<th>Currency</th>' : '').'
			<th class="text-right">Total</th>
			<th>Billed</th>
			'.($this->multi_currency ? '<th class="text-right">Exchange</th>' : '').'
			<th class="text-right">'.$check[0]['HOME_CURRENCY'].' Total</th>
			</tr>
			</thead>
			<tbody>';
			$total_revenue = 0;
			$span = 3;
			if( $this->multi_currency ) $span += 2;
			foreach($check as $row) {
				$result .= '<tr>
						<td>'.$row['EXPENSE'].'</td>
						'.($this->multi_currency ? '<td>'.$row['CURRENCY'].'</td>' : '').'
						<td class="text-right">'.$row['COST'].'</td>
						<td class="text-center">'.( $row['BILLED'] ?
							'<span class="text-success lead"><span class="glyphicon glyphicon-ok"></span></span>' :
							'<span class="text-danger lead"><span class="glyphicon glyphicon-remove"></span>').'</td>
						'.($this->multi_currency ? '<td class="text-right">'.($row['ERATE'] <> 1 ? '('.date("m/d", strtotime($row['ASOF'])).') '.$row['ERATE'] : '').'</td>' : '').'
						<td class="text-right"><strong>'.$row['CONVERTED'].'</strong></td>
					</tr>';
				$total_revenue += $row['CONVERTED'];
			}
	   		$result .= '<tr>
	   		<td colspan="'.$span.'"></td>
	   		<td class="text-right"><strong>'.number_format($total_revenue,2,'.','').'<//pre></td>
	   		</tr>
	   		</tbody>
			</table>
			</div>
			</div>';
			
		}	

		return $result;
	}
}

$sts_form_viewmileage_form = array(	//! $sts_form_viewmileage_form
	//'title' => 'Revenue/Expense/Margin For %LOAD_CODE%',
	'action' => 'exp_viewload.php',
	//'actionextras' => 'disabled',
	//'cancel' => 'exp_listload.php',
	'name' => 'viewmileage',
	//'okbutton' => 'Save Changes',
	//'saveadd' => 'Add Another',
	//'cancelbutton' => 'Back',
	'buttons' => array( 
		),
		'layout' => '
	<div class="form-group">
		<div class="col-sm-4 squished">
			<div class="panel panel-success">
				<div class="panel-heading">
					<h3 class="panel-title">Revenue###</h3>
				</div>
				<div class="panel-body">
					<div class="form-group">
						<label for="SHIPMENTS" class="col-sm-8 control-label">#SHIPMENTS#</label>
						<div class="col-sm-4">
							%SHIPMENTS%
						</div>
					</div>
					<div class="form-group">
						<label for="BILLED" class="col-sm-8 control-label">#BILLED#</label>
						<div class="col-sm-4">
							<p class="form-control-static text-right">%BILLED%</p>
						</div>
					</div>
					<div class="form-group">
						<label for="REVENUE" class="col-sm-8 control-label">#REVENUE#</label>
						<div class="col-sm-4">
							<p class="form-control-static text-right"><strong>%REVENUE%</strong></p>
						</div>
					</div>
					<div class="form-group">
						<label for="REVENUE_MILE" class="col-sm-8 control-label">#REVENUE_MILE#</label>
						<div class="col-sm-4">
							%REVENUE_MILE%
						</div>
					</div>
					<div class="form-group">
						<label for="REVENUE_POUND" class="col-sm-8 control-label">#REVENUE_POUND#</label>
						<div class="col-sm-4">
							%REVENUE_POUND%
						</div>
					</div>
				</div>
			</div>
		</div>
		<div class="col-sm-4 squished">
			<div class="panel panel-danger">
				<div class="panel-heading">
					<h3 class="panel-title">- Expense###</h3>
				</div>
				<div class="panel-body">
					<div class="form-group">
						<label for="TOTAL_COST" class="col-sm-8 control-label">#TOTAL_COST#</label>
						<div class="col-sm-4">
							<p class="form-control-static text-right"><strong>%TOTAL_COST%</strong></p>
						</div>
					</div>
					<div class="form-group">
						<label for="COST_MILE" class="col-sm-8 control-label">#COST_MILE#</label>
						<div class="col-sm-4">
							%COST_MILE%
						</div>
					</div>
				</div>
			</div>
		</div>
		<div class="col-sm-4 squished">
			<div class="panel panel-info tighter">
				<div class="panel-heading">
					<h3 class="panel-title">= Margin###</h3>
				</div>
				<div class="panel-body">
					<div class="form-group">
						<label for="MARGIN" class="col-sm-8 control-label">#MARGIN#</label>
						<div class="col-sm-4">
							<p class="form-control-static text-right"><strong>%MARGIN%</strong></p>
						</div>
					</div>
					<div class="form-group">
						<label for="PCT_MARGIN" class="col-sm-8 control-label">#PCT_MARGIN#</label>
						<div class="col-sm-4">
							%PCT_MARGIN%
						</div>
					</div>
				</div>
			</div>
			<div class="panel panel-info tighter">
				<div class="panel-heading">
					<h3 class="panel-title">Fuel Surcharge###</h3>
				</div>
				<div class="panel-body">
					<div class="form-group">
						<label for="FSC" class="col-sm-8 control-label">#FSC#</label>
						<div class="col-sm-4">
							%FSC%
						</div>
					</div>
					<div class="form-group">
						<label for="FSC_MILE" class="col-sm-8 control-label">#FSC_MILE#</label>
						<div class="col-sm-4">
							%FSC_MILE%
						</div>
					</div>
					<div class="form-group">
						<label for="FSC_LOADED_MILE" class="col-sm-8 control-label">#FSC_LOADED_MILE#</label>
						<div class="col-sm-4">
							%FSC_LOADED_MILE%
						</div>
					</div>
				</div>
			</div>
		</div>
	</div>
');

$sts_result_view_mileage_fields = array(
	'LOADED_DISTANCE' => array( 'label' => 'Loaded Distance', 'format' => 'static', 'align' => 'right' ),
	'EMPTY_DISTANCE' => array( 'label' => 'Empty Distance', 'format' => 'static', 'align' => 'right' ),
	'TOTAL_DISTANCE' => array( 'label' => 'Total Distance', 'format' => 'static', 'align' => 'right' ),
	'PCT_EMPTY' => array( 'label' => '% Empty', 'format' => 'static', 'align' => 'right' ),
	'ODOMETER_FROM' => array( 'label' => 'Odometer From', 'format' => 'static', 'align' => 'right' ),
	'ODOMETER_TO' => array( 'label' => 'Odometer To', 'format' => 'static', 'align' => 'right' ),
	'ACTUAL_DISTANCE' => array( 'label' => 'Actual Miles', 'format' => 'static', 'align' => 'right' ),
	'SHIPMENTS' => array( 'label' => '# Shipments', 'format' => 'static', 'align' => 'right' ),
	'BILLED' => array( 'label' => '# Billed', 'format' => 'inline', 'align' => 'right' ),
	'REVENUE' => array( 'label' => 'Revenue', 'format' => 'inline', 'align' => 'right' ),
	'REVENUE_MILE' => array( 'label' => 'Revenue Per Mile', 'format' => 'static', 'align' => 'right' ),
	'REVENUE_POUND' => array( 'label' => 'Revenue Per LB', 'format' => 'static', 'align' => 'right' ),
	'TOTAL_COST' => array( 'label' => 'Total Cost', 'format' => 'inline', 'align' => 'right' ),
	'COST_MILE' => array( 'label' => 'Cost Per Mile', 'format' => 'static', 'align' => 'right' ),
	'MARGIN' => array( 'label' => 'Margin', 'format' => 'inline', 'align' => 'right' ),
	'PCT_MARGIN' => array( 'label' => 'Margin %', 'format' => 'static', 'align' => 'right' ),
	'FSC' => array( 'label' => 'Fuel Surcharge', 'format' => 'static', 'align' => 'right' ),
	'FSC_MILE' => array( 'label' => 'FSC Per Mile', 'format' => 'static', 'align' => 'right' ),
	'FSC_LOADED_MILE' => array( 'label' => 'FSC Per Loaded Mile', 'format' => 'static', 'align' => 'right' ),
);


$sts_result_mileage_layout = array(
	'LOAD_CODE' => array( 'label' => 'Load#', 'format' => 'num0nc', 'link' => 'exp_viewload.php?CODE=', 'align' => 'right' ),
	'CURRENT_STATUS' => array( 'label' => 'Status', 'format' => 'table',
		'table' => STATUS_CODES_TABLE, 'key' => 'STATUS_CODES_CODE', 'fields' => 'STATUS_STATE' ),
	'COMPLETED_DATE' => array( 'label' => 'Completed', 'format' => 'date' ),

	/*'CARRIER' => array( 'label' => 'Carrier', 'format' => 'table',
		'table' => CARRIER_TABLE, 'key' => 'CARRIER_CODE', 'fields' => 'CARRIER_NAME', 'link' => 'exp_editcarrier.php?CODE=' ),
	'DRIVER' => array( 'label' => 'Driver', 'format' => 'table',
		'table' => DRIVER_TABLE, 'key' => 'DRIVER_CODE', 'fields' => 'CONCAT_WS(\' \',FIRST_NAME,LAST_NAME)',
		'static' => true, 'link' => 'exp_editdriver.php?CODE=' ),
		*/

	'TOTAL_DISTANCE' => array( 'label' => 'Est', 'format' => 'number' ),
	'EMPTY_DISTANCE' => array( 'label' => 'Empty', 'format' => 'number', 'align' => 'right' ),
	'PCT_EMPTY' => array( 'label' => '%', 'format' => 'num0', 'align' => 'right' ),
	'ODOMETER_FROM' => array( 'label' => 'From', 'format' => 'number', 'align' => 'right' ),
	'ODOMETER_TO' => array( 'label' => 'To', 'format' => 'number', 'align' => 'right' ),
	//'CALC_DISTANCE' => array( 'label' => 'Calc Dist', 'format' => 'number', 'align' => 'right' ),
	'ACTUAL_DISTANCE' => array( 'label' => 'Diff', 'format' => 'number', 'align' => 'right' ),
	'PALLETS' => array( 'label' => 'Pallets', 'format' => 'number', 'align' => 'right' ),
	//'PIECES' => array( 'label' => 'Pieces', 'format' => 'number', 'align' => 'right' ),
	'WEIGHT' => array( 'label' => 'Weight', 'format' => 'number', 'align' => 'right' ),
	'SHIPMENTS' => array( 'label' => 'Total', 'format' => 'number', 'align' => 'right' ),
	'BILLED' => array( 'label' => 'Billed', 'format' => 'number', 'align' => 'right' ),
	'REVENUE' => array( 'label' => 'Revenue', 'format' => 'num2', 'align' => 'right' ),
	'REVENUE_MILE' => array( 'label' => '/Mile', 'format' => 'num2', 'align' => 'right' ),
	'REVENUE_POUND' => array( 'label' => '/LB', 'format' => 'num2', 'align' => 'right' ),
	'TOTAL_COST' => array( 'label' => 'Expense', 'format' => 'num2', 'align' => 'right' ),
	'COST_MILE' => array( 'label' => 'CPM', 'format' => 'num2', 'align' => 'right' ),
	'MARGIN' => array( 'label' => 'Margin', 'format' => 'num2', 'align' => 'right' ),
	'PCT_MARGIN' => array( 'label' => '%', 'format' => 'num0', 'align' => 'right' ),
	'FSC' => array( 'label' => 'FSC', 'format' => 'num2', 'align' => 'right' ),
	'FSC_MILE' => array( 'label' => 'PerM', 'format' => 'num2', 'align' => 'right' ),
	'FSC_LOADED_MILE' => array( 'label' => 'PerLM', 'format' => 'num2', 'align' => 'right' ),
);

$sts_result_mileage_view = array(
	'title' => '<img src="images/load_icon.png" alt="load_icon" height="24"> Mileage Report',
	'sort' => 'LOAD_CODE ASC',
	'cancel' => 'index.php',
	'cancelbutton' => 'Back',
	'toprow' => '<tr>
		<th colspan="3" style="border: 0px;">&nbsp;</th>
		<th class="exspeedite-bg text-center h4" colspan="3">Distance</th>
		<th class="exspeedite-bg text-center h4" colspan="3">Odometer</th>
		<th class="exspeedite-bg text-center h4" colspan="2">Cargo</th>
		<th class="exspeedite-bg text-center h4" colspan="2">Shipments</th>
		<th class="exspeedite-bg text-center h4" colspan="3">Revenue</th>
		<th class="exspeedite-bg text-center h4" colspan="2">Expense</th>
		<th class="exspeedite-bg text-center h4" colspan="2">Margin</th>
		<th class="exspeedite-bg text-center h4" colspan="3">Fuel Surcharge</th>
	</tr>
	',
	//'cancel' => 'index.php',
	//'add' => 'exp_addshipment.php',
	//'actionextras' => 'disabled',
	//'addbutton' => 'Add Shipment',
	//'cancelbutton' => 'Back',
);
