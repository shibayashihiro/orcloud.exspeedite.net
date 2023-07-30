<?php

// $Id: sts_company_tax_class.php 4350 2021-03-02 19:14:52Z duncan $
// Company (Canadian) tax class.

// no direct access
defined('_STS_INCLUDE') or die('Restricted access');

require_once( "sts_table_class.php" );
require_once( "sts_canada_tax_class.php" );
require_once( "sts_company_class.php" );
require_once( "sts_email_class.php" );
require_once( "sts_setting_class.php" );

class sts_company_tax extends sts_table {

	private $canada_tax;
	private $company;
	private $issue = '';
	private $setting_table;
	private	$multi_company;
	private $multi_currency;
	private $sage50;
	private $exempt = false;
	private $qboe_tax_exempt;
	private $alert_tax_issue;
	
	// Constructor does not need the table name
	public function __construct( $database, $debug = false ) {

		$this->debug = $debug;
		$this->primary_key = "COMPANY_TAX_CODE";
		if( $this->debug ) echo "<p>Create sts_company_tax</p>";
		parent::__construct( $database, COMPANY_TAX_TABLE, $debug);
		$this->setting_table = sts_setting::getInstance( $this->database, $this->debug );
		//$this->multi_company = $this->setting_table->get( 'option', 'MULTI_COMPANY' ) == 'true';
		//$this->multi_currency = $this->setting_table->get("option", "MULTI_CURRENCY") == 'true';
		$this->sage50 = $this->setting_table->get( 'api', 'EXPORT_SAGE50_CSV' ) == 'true';
		$this->alert_tax_issue = $this->setting_table->get( 'option', 'ALERT_TAX_ISSUE' ) == 'true';
		$this->qboe_tax_exempt = $this->setting_table->get( 'QuickBooks', 'QBOE_EXEMPT_TAX' );

		$this->canada_tax = sts_canada_tax::getInstance( $this->database, $this->debug );
		$this->company = sts_company::getInstance( $this->database, $this->debug );
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
    
    //! Get all the info needed for taxing a Canadian shipment
    public function tax_info( $shipment ) {
	    $new_info = array();
	    $this->issue = '';
	    $this->exempt = false;

	    // Get tax rates that apply
	    $info = $this->canada_tax->tax_rates( $shipment );
	    
	    if( is_array($info) ) {
			foreach( $info as $row ) {
			    if( $this->sage50 ) {	// In multi-company mode
				    $check = $this->fetch_rows("COMPANY_CODE = ".$row['company']."
				    	AND TAX = '".$row['tax']."'
				    	AND CURRENCY_CODE = '".$row['currency']."'
				    	AND GLTYPE = 'collected'" );
			    	
				    $check2 = $this->database->get_one_row("
				    	SELECT SAGE50_".$row['currency']."_AR FROM EXP_COMPANY
				    	WHERE COMPANY_CODE = ".$row['company'] );
			    }
			    
			    $check3 = $this->database->get_one_row("
			    	SELECT TOTAL FROM exp_client_billing
			    	WHERE SHIPMENT_ID = ".$shipment );
			    
			    if( $this->sage50 &&	// Used for Sage50 export
			    	is_array($check) && count($check) == 1 &&
			    	! empty($check[0]["SAGE50_SALES_TAX_ID"]) &&	// Company tax info
			    	! empty($check[0]["SAGE50_TAX_AGENCY_ID"]) &&
			    	! empty($check[0]["SAGE50_GL"]) &&
			    	! empty($check[0]["TAX_REG_NUMBER"]) ) {
			    				    	
			    	// CAD/USD AR account
			    	if( is_array($check2) && isset($check2["SAGE50_".$row['currency']."_AR"]) ) {
				    
					    $new_row = $row;
					    $new_row['SAGE50_SALES_TAX_ID'] = $check[0]["SAGE50_SALES_TAX_ID"];
					    $new_row['SAGE50_TAX_AGENCY_ID'] = $check[0]["SAGE50_TAX_AGENCY_ID"];
					    $new_row['SAGE50_GL'] = $check[0]["SAGE50_GL"];
					    $new_row["SAGE50_".$row['currency']."_AR"] = $check2["SAGE50_".$row['currency']."_AR"];
					    $new_row['CURRENCY'] = $row['currency'];
					    $new_row['TOTAL'] = round($check3["TOTAL"],2);
					    $new_row['AMOUNT'] = round(floatval($check3["TOTAL"]) / 100.0 * floatval($row['rate']), 2, PHP_ROUND_HALF_EVEN);
						if( $this->debug ) echo "<p>".__METHOD__.": total = ".$check3["TOTAL"].
							" rate = ".$row['rate']." amount = ".$new_row['AMOUNT']."</p>";
					    $new_row['TAX_REG_NUMBER'] = $check[0]["TAX_REG_NUMBER"];
					    $new_info[] = $new_row;
				    } else {
					     $this->issue = $row['currency'].' AR account missing for '.$check[0]["NAME"];
				    }
			    } else if( ! $this->sage50 ) {
				    $new_row = $row;
				    $new_row['CURRENCY'] = $row['currency'];
				    $new_row['TOTAL'] = round($check3["TOTAL"],2);
				    $new_row['AMOUNT'] = round(floatval($check3["TOTAL"]) / 100.0 * floatval($row['rate']), 2, PHP_ROUND_HALF_EVEN);
					$new_info[] = $new_row;
				    
			    } else {
				    if( is_array($check) && count($check) > 1 )
				    	$this->issue = 'Multiple company '.$row['tax'].'/'.$row['currency'].'/collected tax info for '.
				    		$this->company->name($row['company']);
				    else
				     	$this->issue = 'Company '.$row['tax'].'/'.$row['currency'].'/collected tax info missing for '.
						     $this->company->name($row['company']);
			    }
		    }
		    
	    } else {
		    $this->issue = $this->canada_tax->get_issue();
		    if( ! empty($this->issue) &&
		    	(strpos($this->issue, 'tax exempt') !== false ||
		    	strpos($this->issue, 'does not start and end') !== false) ) {
			    $this->exempt = true;
			}
	    }
	    if( count($new_info) == 0 )
	    	$new_info = false;
	    	
	    if( $this->alert_tax_issue && ! empty($this->issue) &&
	    	! $this->exempt ) {
			$email = sts_email::getInstance($this->database, $this->debug);
			$email->send_alert("Error Getting tax info for shipment $shipment<br>".
				"Issue = ".$this->issue."<br>".
				"Issue from canada_tax = ".$this->canada_tax->get_issue()."<br>".
				"Info from canada_tax = <pre>".print_r($info, true)."</pre><br>".
				"New info = <pre>".print_r($new_info, true)."</pre><br>" );
		}
	    
	    return $new_info;
    }

    //! SCR# 635 - Get tax rate(s) for Canadian lumper/handling
    public function lumper_tax_info( $load ) {
	    $new_info = array();
	    $this->issue = '';
	    $this->exempt = false;

	    // Get tax rates that apply
	    $info = $this->canada_tax->lumper_tax_rates( $load );
	    
	    if( is_array($info) ) {
			foreach( $info as $row ) {
			    if( $this->sage50 ) {	// In multi-company mode
				    $check = $this->fetch_rows("COMPANY_CODE = ".$row['company']."
				    	AND TAX = '".$row['tax']."'
				    	AND CURRENCY_CODE = '".$row['currency']."'
				    	AND GLTYPE = 'paid'" );
			    	
				    $check2 = $this->database->get_one_row("
				    	SELECT SAGE50_".$row['currency']."_AP FROM EXP_COMPANY
				    	WHERE COMPANY_CODE = ".$row['company'] );
			    }
			    			    
			    if( $this->sage50 &&	// Used for Sage50 export
			    	is_array($check) && count($check) == 1 &&
			    	! empty($check[0]["SAGE50_SALES_TAX_ID"]) &&	// Company tax info
			    	! empty($check[0]["SAGE50_TAX_AGENCY_ID"]) &&
			    	! empty($check[0]["SAGE50_GL"]) &&
			    	! empty($check[0]["TAX_REG_NUMBER"]) ) {
			    				    	
			    	// CAD/USD AR account
			    	if( is_array($check2) && isset($check2["SAGE50_".$row['currency']."_AP"]) ) {
				    
					    $new_row = $row;
					    $new_row['SAGE50_SALES_TAX_ID'] = $check[0]["SAGE50_SALES_TAX_ID"];
					    $new_row['SAGE50_TAX_AGENCY_ID'] = $check[0]["SAGE50_TAX_AGENCY_ID"];
					    $new_row['SAGE50_GL'] = $check[0]["SAGE50_GL"];
					    $new_row["SAGE50_".$row['currency']."_AP"] = $check2["SAGE50_".$row['currency']."_AP"];
					    $new_row['CURRENCY'] = $row['currency'];
					    $new_row['TOTAL'] = round($row['lumper'],2);
					    $new_row['AMOUNT'] = round(floatval($row['lumper']) / 100.0 * floatval($row['rate']), 2, PHP_ROUND_HALF_EVEN);
						if( $this->debug ) echo "<p>".__METHOD__.": total = ".$row['lumper'].
							" rate = ".$row['rate']." amount = ".$new_row['AMOUNT']."</p>";
					    $new_row['TAX_REG_NUMBER'] = $check[0]["TAX_REG_NUMBER"];
					    $new_info[] = $new_row;
				    } else {
					     $this->issue = $row['currency'].' AP account missing for '.$check[0]["NAME"];
				    }
			    } else if( ! $this->sage50 ) {
				    $new_row = $row;
				    $new_row['CURRENCY'] = $row['currency'];
				    $new_row['TOTAL'] = round($row['lumper'],2);
				    $new_row['AMOUNT'] = round(floatval($row['lumper']) / 100.0 * floatval($row['rate']), 2, PHP_ROUND_HALF_EVEN);
					$new_info[] = $new_row;
				    
			    } else {
				    if( is_array($check) && count($check) > 1 )
				    	$this->issue = 'Multiple company '.$row['tax'].'/'.$row['currency'].'/paid tax info for '.
				    		$this->company->name($row['company']);
				    else
				     	$this->issue = 'Company '.$row['tax'].'/'.$row['currency'].'/paid tax info missing for '.
						     $this->company->name($row['company']);
			    }
		    }
		    
	    } else {
		    $this->issue = $this->canada_tax->get_issue();
		    if( ! empty($this->issue) &&
		    	(strpos($this->issue, 'tax exempt') !== false ||
		    	strpos($this->issue, 'does not start and end') !== false) ) {
			    $this->exempt = true;
			}
	    }
	    if( count($new_info) == 0 )
	    	$new_info = false;
	    	
	    if( false && ! empty($this->issue) &&
	    	! $this->exempt ) {
			$email = sts_email::getInstance($this->database, $this->debug);
			$email->send_alert("Error Getting tax info for load $load<br>".
				"Issue = ".$this->issue."<br>".
				"Issue from canada_tax = ".$this->canada_tax->get_issue()."<br>".
				"Info from canada_tax = <pre>".print_r($info, true)."</pre><br>".
				"New info = <pre>".print_r($new_info, true)."</pre><br>" );
		}
	    
	    return $new_info;
    }

   //! SCR# 257 - tax info for QBOE
    public function qb_tax_info( $shipment ) {
	    $result = false;
	    $info = $this->tax_info( $shipment );
	    if( is_array($info) ) {
		    $province = $info[0]["province_short"];
		    if( ! empty($info[0]["qboe_name"])) {
			    $result = $info[0]["qboe_name"];
			    if( $province == 'QC' && count($info) == 1 ) {
				    $result = explode('+', $result)[0];
			    }
		    } else {
			    $tax = array();
			    foreach($info as $row) {
				    $tax[] = $row["tax"];
			    }
			    $result = implode('+', $tax).' '.$province;
		    }
	    } else if( $this->exempt ) {
		    $result = $this->qboe_tax_exempt;
	    }
	    
	    return $result;
	}

    public function get_issue() {
	    return $this->issue;
    }

}

//! Form Specifications - For use with sts_form

$sts_form_addcompany_tax_form = array( //!$sts_form_addcompany_tax_form
	'title' => '<img src="images/tax_icon.png" alt="tax_icon" height="24"> Add Tax Info',
	'action' => 'exp_addcompany_tax.php',
	//'actionextras' => 'disabled',
	'cancel' => 'exp_editcompany.php?CODE=%COMPANY_CODE%',
	'name' => 'exp_addcompany_tax',
	'okbutton' => 'Save Changes',
	'cancelbutton' => 'Cancel',
		'layout' => '
	<div class="form-group">
		<div class="col-sm-6">
			<div class="form-group tighter">
				<label for="COMPANY_CODE" class="col-sm-4 control-label">#COMPANY_CODE#</label>
				<div class="col-sm-8">
					%COMPANY_CODE%
				</div>
			</div>
			<div class="form-group tighter">
				<label for="TAX" class="col-sm-4 control-label">#TAX#</label>
				<div class="col-sm-8">
					%TAX%
				</div>
			</div>
			<div class="form-group tighter">
				<label for="CURRENCY_CODE" class="col-sm-4 control-label">#CURRENCY_CODE#</label>
				<div class="col-sm-8">
					%CURRENCY_CODE%
				</div>
			</div>
			<div class="form-group tighter">
				<label for="SAGE50_SALES_TAX_ID" class="col-sm-4 control-label">#SAGE50_SALES_TAX_ID#</label>
				<div class="col-sm-8">
					%SAGE50_SALES_TAX_ID%
				</div>
			</div>
			<div class="form-group tighter">
				<label for="SAGE50_TAX_AGENCY_ID" class="col-sm-4 control-label">#SAGE50_TAX_AGENCY_ID#</label>
				<div class="col-sm-8">
					%SAGE50_TAX_AGENCY_ID%
				</div>
			</div>
			<div class="form-group tighter">
				<label for="GLTYPE" class="col-sm-4 control-label">#GLTYPE#</label>
				<div class="col-sm-8">
					%GLTYPE%
				</div>
			</div>
			<div class="form-group tighter">
				<label for="SAGE50_GL" class="col-sm-4 control-label">#SAGE50_GL#</label>
				<div class="col-sm-8">
					%SAGE50_GL%
				</div>
			</div>
			<div class="form-group tighter">
				<label for="TAX_REG_NUMBER" class="col-sm-4 control-label">#TAX_REG_NUMBER#</label>
				<div class="col-sm-8">
					%TAX_REG_NUMBER%
				</div>
			</div>

		</div>
		<div class="col-sm-6">
			<div class="alert alert-info">
				<p>There are 3 Canadian taxes that may apply: GST, HST and QST.</p>
				<p>For each tax you need:</p>
				<ul>
					<li>Sales Tax ID (for Sage 50)</li>
					<li>Sales Tax Agency ID (for Sage 50)</li>
					<li>Type (collected for invoices, paid for carriers)</li>
					<li>Sage 50 GL account number</li>
					<li>Tax Registration# (used in description field)</li>
				</ul>
				<p>In Sage 50, go to <strong>Set Up Sales Taxes</strong> and create matching Sales Taxes and Sales Tax Agencies, including a matching vendor for submission of taxes. Each Sales Tax Agency has a corresponding G/L account, which must match the one here.</p>
			</div>
		</div>
	</div>
	
	'
);

$sts_form_editcompany_tax_form = array( //!$sts_form_editcompany_tax_form
	'title' => '<img src="images/tax_icon.png" alt="tax_icon" height="24"> Edit Canadian Tax Rate',
	'action' => 'exp_editcompany_tax.php',
	//'actionextras' => 'disabled',
	'cancel' => 'exp_editcompany.php?CODE=%COMPANY_CODE%',
	'name' => 'exp_editcompany_tax',
	'okbutton' => 'Save Changes',
	'cancelbutton' => 'Back',
		'layout' => '
	%COMPANY_TAX_CODE%
	<div class="form-group">
		<div class="col-sm-6">
			<div class="form-group tighter">
				<label for="COMPANY_CODE" class="col-sm-4 control-label">#COMPANY_CODE#</label>
				<div class="col-sm-8">
					%COMPANY_CODE%
				</div>
			</div>
			<div class="form-group tighter">
				<label for="TAX" class="col-sm-4 control-label">#TAX#</label>
				<div class="col-sm-8">
					%TAX%
				</div>
			</div>
			<div class="form-group tighter">
				<label for="CURRENCY_CODE" class="col-sm-4 control-label">#CURRENCY_CODE#</label>
				<div class="col-sm-8">
					%CURRENCY_CODE%
				</div>
			</div>
			<div class="form-group tighter">
				<label for="SAGE50_SALES_TAX_ID" class="col-sm-4 control-label">#SAGE50_SALES_TAX_ID#</label>
				<div class="col-sm-8">
					%SAGE50_SALES_TAX_ID%
				</div>
			</div>
			<div class="form-group tighter">
				<label for="SAGE50_TAX_AGENCY_ID" class="col-sm-4 control-label">#SAGE50_TAX_AGENCY_ID#</label>
				<div class="col-sm-8">
					%SAGE50_TAX_AGENCY_ID%
				</div>
			</div>
			<div class="form-group tighter">
				<label for="GLTYPE" class="col-sm-4 control-label">#GLTYPE#</label>
				<div class="col-sm-8">
					%GLTYPE%
				</div>
			</div>
			<div class="form-group tighter">
				<label for="SAGE50_GL" class="col-sm-4 control-label">#SAGE50_GL#</label>
				<div class="col-sm-8">
					%SAGE50_GL%
				</div>
			</div>
			<div class="form-group tighter">
				<label for="TAX_REG_NUMBER" class="col-sm-4 control-label">#TAX_REG_NUMBER#</label>
				<div class="col-sm-8">
					%TAX_REG_NUMBER%
				</div>
			</div>

		</div>
		<div class="col-sm-6">
			<div class="alert alert-info">
				<p>There are 3 Canadian taxes that may apply: GST, HST and QST.</p>
				<p>For each tax you need:</p>
				<ul>
					<li>Sales Tax ID (for Sage 50)</li>
					<li>Sales Tax Agency ID (for Sage 50)</li>
					<li>Type (collected for invoices, paid for carriers)</li>
					<li>Sage 50 GL account number</li>
					<li>Tax Registration# (used in description field)</li>
				</ul>
				<p>In Sage 50, go to <strong>Set Up Sales Taxes</strong> and create matching Sales Taxes and Sales Tax Agencies, including a matching vendor for submission of taxes. Each Sales Tax Agency has a corresponding G/L account, which must match the one here.</p>
			</div>
		</div>
	</div>
	
	'
);

$sts_form_add_company_tax_fields = array(	//! $sts_form_add_company_tax_fields
	//'COMPANY_CODE' => array( 'format' => 'hidden' ),
	'COMPANY_CODE' => array( 'label' => 'Company', 'format' => 'table',
		'table' => COMPANY_TABLE, 'key' => 'COMPANY_CODE', 'fields' => 'COMPANY_NAME',
		'extras' => 'readonly' ),
	'TAX' => array( 'label' => 'Tax', 'format' => 'enum' ),
	'CURRENCY_CODE' => array( 'label' => 'Currency', 'format' => 'enum' ),
	'SAGE50_SALES_TAX_ID' => array( 'label' => 'Sales Tax ID', 'format' => 'text', 'extras' => 'required' ),
	'SAGE50_TAX_AGENCY_ID' => array( 'label' => 'Sales Tax Agency ID', 'format' => 'text', 'extras' => 'required' ),
	'GLTYPE' => array( 'label' => 'Type', 'format' => 'enum' ),
	'SAGE50_GL' => array( 'label' => 'Sage GL', 'format' => 'text', 'extras' => 'required' ),
	'TAX_REG_NUMBER' => array( 'label' => 'Tax Registration#', 'format' => 'text', 'extras' => 'required' ),
);

$sts_form_edit_company_tax_fields = array(	//! $sts_form_edit_company_tax_fields
	'COMPANY_TAX_CODE' => array( 'format' => 'hidden' ),
	'COMPANY_CODE' => array( 'label' => 'Company', 'format' => 'table',
		'table' => COMPANY_TABLE, 'key' => 'COMPANY_CODE', 'fields' => 'COMPANY_NAME',
		'extras' => 'readonly' ),
	'TAX' => array( 'label' => 'Tax', 'format' => 'enum' ),
	'CURRENCY_CODE' => array( 'label' => 'Currency', 'format' => 'enum' ),
	'SAGE50_SALES_TAX_ID' => array( 'label' => 'Sales Tax ID', 'format' => 'text', 'extras' => 'required' ),
	'SAGE50_TAX_AGENCY_ID' => array( 'label' => 'Sales Tax Agency ID', 'format' => 'text', 'extras' => 'required' ),
	'GLTYPE' => array( 'label' => 'Type', 'format' => 'enum' ),
	'SAGE50_GL' => array( 'label' => 'Sage GL', 'format' => 'text', 'extras' => 'required' ),
	'TAX_REG_NUMBER' => array( 'label' => 'Tax Registration#', 'format' => 'text', 'extras' => 'required' ),
);

//! Layout Specifications - For use with sts_result

$sts_result_company_tax_layout = array(	//! $sts_result_company_tax_layout
	'COMPANY_TAX_CODE' => array( 'format' => 'hidden' ),
	'COMPANY_CODE' => array( 'format' => 'hidden' ),
	'TAX' => array( 'label' => 'Tax', 'format' => 'text' ),
	'CURRENCY_CODE' => array( 'label' => 'Currency', 'format' => 'text' ),
	'SAGE50_SALES_TAX_ID' => array( 'label' => 'Sales Tax ID', 'format' => 'text' ),
	'SAGE50_TAX_AGENCY_ID' => array( 'label' => 'Sales Tax Agency ID', 'format' => 'text' ),
	'GLTYPE' => array( 'label' => 'Type', 'format' => 'text' ),
	'SAGE50_GL' => array( 'label' => 'Sage GL', 'format' => 'text' ),
	'TAX_REG_NUMBER' => array( 'label' => 'Tax Registration#', 'format' => 'text' ),
	'CHANGED_DATE' => array( 'label' => 'Changed', 'format' => 'timestamp-s', 'length' => 90 ),
	'CHANGED_BY' => array( 'label' => 'By', 'format' => 'table',
		'table' => USER_TABLE, 'key' => 'USER_CODE', 'fields' => 'USERNAME', 'link' => 'exp_edituser.php?CODE=' )
);

//! Edit/Delete Button Specifications - For use with sts_result

$sts_result_company_tax_edit = array( //! $sts_result_company_tax_edit
	'title' => '<img src="images/tax_icon.png" alt="tax_icon" height="24"> Company Canadian Tax Info',
	'sort' => 'COMPANY_TAX_CODE ASC',
	'add' => 'exp_addcompany_tax.php',
	'addbutton' => 'Add Tax Info',
	'rowbuttons' => array(
		array( 'url' => 'exp_editcompany_tax.php?CODE=', 'key' => 'COMPANY_TAX_CODE', 'label' => 'TAX', 'tip' => 'Edit Tax Info ', 'icon' => 'glyphicon glyphicon-edit' ),
		array( 'url' => 'exp_deletecompany_tax.php?CODE=', 'key' => 'COMPANY_TAX_CODE', 'label' => 'TAX', 'tip' => 'Delete Tax Info ', 'icon' => 'glyphicon glyphicon-trash', 'confirm' => 'yes' )
	)
);
