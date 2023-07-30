<?php

// $Id: sts_margin_report_class.php 5118 2023-05-30 21:17:35Z dev $
// Margin Report functions

// no direct access
defined('_STS_INCLUDE') or die('Restricted access');

require_once( "sts_table_class.php" );
require_once( "sts_setting_class.php" );
require_once( "sts_shipment_class.php" );

class sts_margin_report extends sts_table {
	private		$setting_table;
	private		$export_sage50;
	private		$ass_backwards;

	// Constructor does not need the table name
	public function __construct( $database, $debug = false ) {

		$this->debug = $debug;
		$this->primary_key = "LOAD_CODE";
		
		$this->setting_table = sts_setting::getInstance( $this->database, $this->debug );
		$this->export_sage50 = $this->setting_table->get( 'api', 'EXPORT_SAGE50_CSV' ) == 'true';
		$this->client_id = ($this->setting_table->get("option", "CLIENT_ID") == 'true');
		$this->ass_backwards = ($this->setting_table->get("option", "MARGIN_LUMPER_NOTAX") == 'true');

		if( $this->debug ) echo "<p>Create sts_margin_report</p>";
		parent::__construct( $database, LOAD_TABLE, $debug);

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
    
    public function ass_backwards() {
	    return $this->ass_backwards;
    }
    
    public function user_menu( $selected = false, $id = 'USER_CODE', $match = '', $onchange = true ) {
		$select = false;

		$choices = $this->database->get_multiple_rows("
			select USER_CODE, USERNAME, FULLNAME 
			from exp_user
			where USER_GROUPS like '%sales%'
			AND ISACTIVE = 'Active'
			UNION
			select -1 as USER_CODE, '' as USERNAME, '-- ALL --' as FULLNAME
			UNION
			select -2 as USER_CODE, '' as USERNAME, '-- No Sales Person --' as FULLNAME 
			order by FULLNAME");

		if( is_array($choices) && count($choices) > 0) {
			
			$select = '<select class="form-control input-lg" name="'.$id.'" id="'.$id.'"'.($onchange ? ' onchange="form.submit();"' : '').'>
			';
			$select .= '<option value="None">Select Sales Person</option>
			';
			foreach( $choices as $row ) {
				$select .= '<option value="'.$row["USER_CODE"].'"';
				if( $selected && $selected == $row["USER_CODE"] )
					$select .= ' selected';
				$select .= '>'.$row["FULLNAME"].($row["USER_CODE"] > 0 ?
					' ('.$row["USERNAME"].')' : '').'</option>
				';
			}
			$select .= '</select>';
		}
			
		return $select;
	}

    public function user_name( $user ) {
	    $result = "No Sales Person";

		$check = $this->database->get_one_row("
			select USER_CODE, FULLNAME
			from (select USER_CODE, FULLNAME 
			from exp_user
			where USER_GROUPS like '%sales%'
			AND ISACTIVE = 'Active'
			UNION
			select -1 as USER_CODE, '-- ALL --' as FULLNAME
			UNION
			select -2 as USER_CODE, '-- No Sales Person --' as FULLNAME 
			order by FULLNAME) x
			where USER_CODE = $user
			LIMIT 1");
			
		if( is_array($check) && isset($check['FULLNAME']) )
			$result = $check['FULLNAME'];
		
		return $result;
	}
	
     public function has_subs( $user, $id = 'HAS_SUBS' ) {
	    $result = "";

		$check = $this->database->get_one_row("
			SELECT count(*) > 0 HAS_SUBS
			FROM exp_user
			where manager = $user");
			
		if( is_array($check) && isset($check['HAS_SUBS']) && $check['HAS_SUBS'] == 1 ) {
			$result = '	<div class="form-group h3">
		<label class="col-sm-3" for="HAS_SUBS">Include Directs: </label>
		<div class="col-sm-4">
			<input type="checkbox" class="my-switch" name="'.$id.'">
		</div>
		</div>';
		}
		
		return $result;
	}
	
   public function office_menu( $selected = false, $user, $id = 'OFFICE_CODE' ) {
		$select = false;

		$choices = $this->database->get_multiple_rows("
			SELECT distinct o.OFFICE_CODE, o.OFFICE_NAME, o.INVOICE_PREFIX, u.USER_GROUPS like '%admin%' IS_ADMIN
			FROM EXP_OFFICE o, EXP_USER u, EXP_USER_OFFICE uo
			WHERE $user < 0
			OR (u.USER_CODE = $user
			AND(u.USER_GROUPS like '%admin%'
				OR (u.USER_CODE = uo.USER_CODE
					AND o.OFFICE_CODE = uo.OFFICE_CODE)))
			GROUP BY o.OFFICE_CODE
			ORDER BY 2 ASC");

		if( is_array($choices) && count($choices) > 0) {
			
			$select = '<select class="form-control input-lg" name="'.$id.'" id="'.$id.'">
			';
			
			// All is only for admin
			if( $user < 0 || (isset($choices[0]["IS_ADMIN"]) && $choices[0]["IS_ADMIN"]) ) {
				$select .= '<option value="-1"';
				if( $selected && $selected == -1 )
					$select .= ' selected';
				$select .= '>-- ALL --</option>
				';
			}

			foreach( $choices as $row ) {
				$select .= '<option value="'.$row["OFFICE_CODE"].'"';
				if( $selected && $selected == $row["OFFICE_CODE"] )
					$select .= ' selected';
				$select .= '>'.$row["OFFICE_NAME"].' ('.$row["INVOICE_PREFIX"].')</option>
				';
			}
			$select .= '</select>';
		}
			
		return $select;
	}

    public function currency_menu( $id = 'CURRENCY' ) {
	    $home = $this->setting_table->get("option", "HOME_CURRENCY");
	    
	    $choices = ['USD', 'CAD'];
	    
		$select = '<select class="form-control input-lg" name="'.$id.'" id="'.$id.'">
		';
			
		foreach( $choices as $row ) {
			$select .= '<option value="'.$row.'"';
			if( $home && $home == $row )
				$select .= ' selected';
			$select .= '>'.$row.'</option>
			';
		}
		$select .= '</select>';
		
		return $select;
    }
    
    public function business_code_menu( $selected = false, $id = 'BUSINESS_CODE' ) {
		$select = false;

		$choices = $this->database->get_multiple_rows("
			select exp_business_code.BUSINESS_CODE, exp_business_code.BC_NAME
			from exp_business_code
			where APPLIES_TO = 'shipment'
			UNION
			select -1 as BUSINESS_CODE, '-- ALL --' as BC_NAME 
			order by BC_NAME");

		if( is_array($choices) && count($choices) > 0) {
			
			$select = '<select class="form-control input-lg" name="'.$id.'" id="'.$id.'">
			';
			foreach( $choices as $row ) {
				$select .= '<option value="'.$row["BUSINESS_CODE"].'"';
				if( $selected && $selected == $row["BUSINESS_CODE"] )
					$select .= ' selected';
				$select .= '>'.$row["BC_NAME"].'</option>
				';
			}
			$select .= '</select>';
		}
			
		return $select;
	}

    public function client_field( $selected = false, $id = 'CLIENT_CODE' ) {
		$ctype = 'bill_to';
		$hb = '<p><strong>{{CLIENT_NAME}}/{{LABEL}}</strong><br>{{CONTACT_TYPE}}, {{ADDRESS}}, {{CITY}}, {{STATE}}, {{ZIP_CODE}}'.($this->client_id ? '<br>Client ID: {{CLIENT_ID}}' : '').($this->export_sage50 ? '<br>Sage50: {{SAGE50_CLIENTID}}' : '').'</p>';
		
		$select = '<input type="hidden" name="'.$id.'" id="'.$id.'" value="-1">
			<div class="form-group h3">
				<label class="col-sm-3" for="CLIENT_NAME">Client: </label>
				<div class="col-sm-4">
					<input class="form-control input-lg" name="CLIENT_NAME" id="CLIENT_NAME" type="text"  autocomplete="off" placeholder="Leave blank for all">
				</div>
			</div>
		
			<script language="JavaScript" type="text/javascript"><!--
		
			var '.$id.'_clients = new Bloodhound({
			  name: \''.$id.'\',
			  remote : {
				  url: \'exp_suggest_client.php?code=Vinegar&type='.$ctype.'&query=%QUERY\',
				  wildcard: \'%QUERY\'
			  },
			  datumTokenizer: Bloodhound.tokenizers.obj.whitespace(\'LABEL\'),
			  queryTokenizer: Bloodhound.tokenizers.whitespace
			});
						
			'.$id.'_clients.initialize();

			$(\'#CLIENT_NAME:not([readonly])\').typeahead(null, {
			  name: \''.$id.'\',
			  minLength: 2,
			  limit: 20,
			  highlight: true,
			  display: \'CLIENT_NAME\',
			  source: '.$id.'_clients,
			  templates: {
			  	suggestion: Handlebars.compile(
			      \''.$hb.'\')
			  }
			});
			
			$(\'#CLIENT_NAME\').on(\'typeahead:selected\', function(obj, datum, name) {
				$(\'input#CLIENT_CODE\').val(datum.CLIENT_CODE);
			});
			
	//--></script>
				';
			
		return $select;
	}
	
	public function margin_report( $user, $has_subs, $office, $business, $client, $from, $to, $currency ) {
		
		$shipment_table = sts_shipment::getInstance($this->database, $this->debug);

		// Note: LOAD_REVENUE fails if NUM_SHIPMENTS > 1 and each shipment has charges
		// should not be a problem for SS
		$report = $this->database->multi_query("
			DROP TEMPORARY TABLE IF EXISTS SHIPMENTS;
			
			CREATE TEMPORARY TABLE SHIPMENTS
			select distinct shipment_code, LOAD_CODE
			from (select l.LOAD_CODE, sh.shipment_code
			from exp_shipment sh, exp_load l
			
			where L.LOAD_CODE = SH.LOAD_CODE
			and sh.PICKUP_DATE between '".date("Y-m-d", strtotime($from))."' and '".date("Y-m-d", strtotime($to))."') x
			
			union ALL
			(select l.LOAD_CODE, sh.shipment_code
			from exp_shipment sh, exp_load l, EXP_SHIPMENT_LOAD SHL
			
			where L.LOAD_CODE = SHL.LOAD_CODE
			and sh.LOAD_CODE = SHL.LOAD_CODE
			and sh.PICKUP_DATE between '".date("Y-m-d", strtotime($from))."' and '".date("Y-m-d", strtotime($to))."');

			select a.*,
			COALESCE(case when ".($this->ass_backwards ? "1" : "0")." AND CLIENT_CURRENCY = '".$currency."' then
				TOTAL_CHARGES
			when LOAD_CODE is NULL then
				TOTAL_CHARGES * exchange_rate1
			else
				LOAD_REVENUE_ORIG * exchange_rate2 end, 0) AS LOAD_REVENUE,
			COALESCE(case when CARRIER_CURRENCY = '".$currency."' then
				CARRIER_TOTAL
			else
				LOAD_EXPENSE_ORIG * exchange_rate2 end, 0) AS LOAD_EXPENSE
			".($this->ass_backwards ? ", COALESCE(LUMPER_BASE * exchange_rate3, 0) AS LUMPER_CHANGE" : "")."
			from(
			select distinct sh.SHIPMENT_CODE,
				sh.PICKUP_DATE,
		        bc.BC_NAME,
		        sh.ss_number,
				l.LOAD_CODE,
				sh.OFFICE_CODE,
				sh.sales_person,
				u.USER_CODE, COALESCE(u.FULLNAME, 'NO SALESPERSON') FULLNAME,
				o.OFFICE_name as Office_Name,
		        concat(o.OFFICE_name,' - ',sh.office_code) as office,
		        sh.BILLTO_NAME, 
		        sh.TOTAL_CHARGES,
		        COALESCE(CASE WHEN L.LOAD_REVENUE IS NULL THEN
					LOAD_REVENUE_CUR(L.LOAD_CODE, l.currency)
				ELSE L.LOAD_REVENUE END, 0) AS LOAD_REVENUE_ORIG,
				COALESCE(".($this->ass_backwards ? "L.CARRIER_TOTAL" :
				"CASE WHEN L.LOAD_EXPENSE IS NULL THEN
					LOAD_EXPENSE_CUR(L.LOAD_CODE, l.currency)
				ELSE L.LOAD_EXPENSE END").", 0) AS LOAD_EXPENSE_ORIG,
		        l.currency as CARRIER_CURRENCY,
		        cb.currency as CLIENT_CURRENCY,
		        L.LUMPER_CURRENCY,
		        CASE WHEN L.LUMPER > 0 THEN L.CARRIER_HANDLING ELSE NULL END AS LUMPER_BASE, 
		        CASE WHEN L.LUMPER > 0 THEN L.LUMPER_TAX ELSE NULL END AS LUMPER_TAX, 
		        CASE WHEN L.LUMPER > 0 THEN L.LUMPER_TOTAL ELSE NULL END AS LUMPER_TOTAL, 
		        L.CARRIER_TOTAL,
		        CONVERT_RATE(sh.PICKUP_DATE, 'USD', 'CAD') exchange_rate,
		        CONVERT_RATE(sh.PICKUP_DATE, cb.currency, '".$currency."') exchange_rate1,  
		        COALESCE(CONVERT_RATE(sh.PICKUP_DATE, l.currency, '".$currency."'), 0) exchange_rate2,
		        ".($this->ass_backwards ? "COALESCE(CONVERT_RATE(sh.PICKUP_DATE, L.LUMPER_CURRENCY, '".$currency."'), 0) exchange_rate3,
" : "")."
		        (select COMPANY_NAME from exp_company
                where exp_company.company_code = o.company_code) as companyName
		        , CONVERT_TO_HOME(SHIPMENT_ID, TOTAL_CHARGES) CNVRTCharges
		 		, bc.BC_NAME BUSINESS
		 		, concat(sc.STATUS_CODES_CODE,'-',sc.STATUS_STATE) STATUS_STATE
				from shipments
				left join exp_shipment sh on sh.shipment_code = shipments.shipment_code
				LEFT JOIN EXP_LOAD L ON L.LOAD_CODE = shipments.load_code

		        LEFT JOIN EXP_OFFICE O ON SH.OFFICE_CODE = O.OFFICE_CODE
		        LEFT JOIN EXP_BUSINESS_CODE BC ON SH.BUSINESS_CODE = BC.BUSINESS_CODE
		        LEFT JOIN EXP_CLIENT_BILLING CB ON CB.SHIPMENT_ID = SH.SHIPMENT_CODE
		        LEFT JOIN EXP_USER U ON SH.SALES_PERSON = U.USER_CODE
		        LEFT JOIN EXP_STATUS_CODES SC ON SH.CURRENT_STATUS = SC.STATUS_CODES_CODE
		where sh.PICKUP_DATE between '".date("Y-m-d", strtotime($from))."' and '".date("Y-m-d", strtotime($to))."'
		 AND (sh.TOTAL_CHARGES > 0 -- remove zero revenue shipments
		 	OR COALESCE(CASE WHEN L.LOAD_REVENUE IS NULL THEN
					LOAD_REVENUE_CUR(L.LOAD_CODE, l.currency)
				ELSE L.LOAD_REVENUE END, 0) = 0)
		".($user == -2 ? "AND IFNULL(u.USER_CODE,0) = 0" :
		($user > 0 ? ($has_subs ? "AND IFNULL(u.USER_CODE,0) IN
			((SELECT USER_CODE FROM EXP_USER WHERE MANAGER = $user OR USER_CODE = $user))" :
		"AND IFNULL(u.USER_CODE,0) = ".$user) : ""))."
		-- AND INSTR(sh.ss_number,'-')=0 -- extra stops
		-- and sh.BILLING_STATUS in (".$shipment_table->billing_behavior_state["approved"].", ".
		-- 	$shipment_table->billing_behavior_state["billed"].")
		and sh.CURRENT_STATUS NOT IN (".$shipment_table->behavior_state["cancel"].", ".
			$shipment_table->behavior_state["entry"].")
		
		".($office > 0 ? "and sh.OFFICE_CODE = ".$office : "")."
		".($business > 0 ? "and sh.BUSINESS_CODE = ".$business : "")."
		".($client > 0 ? "and sh.BILLTO_CLIENT_CODE = ".$client : "")."
		) a
		ORDER BY a.USER_CODE ASC, a.OFFICE_CODE ASC, a.SS_NUMBER ASC, a.LOAD_CODE ASC
		" );

		//	CONVERT_RATE(sh.PICKUP_DATE, cb.currency, '".$currency."') exchange_rate1,   
		//	(select count(*) from exp_shipment s where s.load_code = l.load_code) as NUM_SHIPMENTS,

		return $report;
	}
//		and sh.BILLING_STATUS = ".$shipment_table->billing_behavior_state["billed"]."

}

?>