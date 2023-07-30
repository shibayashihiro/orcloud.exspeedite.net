<?php

// $Id: sts_canada_tax_class.php 5030 2023-04-12 20:31:34Z duncan $
// Office class, all activity related to offices.

// no direct access
defined('_STS_INCLUDE') or die('Restricted access');

require_once( "sts_table_class.php" );
require_once( "sts_setting_class.php" );

class sts_canada_tax extends sts_table {
	
	private $issue = '';

	// Constructor does not need the table name
	public function __construct( $database, $debug = false ) {

		$this->debug = $debug;
		$this->primary_key = "TAX_CODE";
		if( $this->debug ) echo "<p>Create sts_canada_tax</p>";
		parent::__construct( $database, CANADA_TAX_TABLE, $debug);
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
    
    //! Get tax rate(s) for Canadian shipments
    // returns false if not applicable, or else an array of tax and rate
    public function tax_rates( $shipment ) {
	    $result = false;
	    $this->issue = '';
	    
	    //! SCR# 515 - protect from Subquery returns more than 1 row by adding LIMIT 1
	    $check = $this->database->get_one_row("
	    	SELECT SHIPPER_STATE, SHIPPER_COUNTRY, CONS_STATE, CONS_COUNTRY,
	    	(SELECT COMPANY_CODE FROM EXP_OFFICE 
	    	WHERE EXP_OFFICE.OFFICE_CODE = EXP_SHIPMENT.OFFICE_CODE LIMIT 1) AS COMPANY_CODE,
	    	(SELECT STATE_NAME from EXP_STATES WHERE abbrev = CONS_STATE LIMIT 1) AS PROVINCE,
	    	CONS_STATE AS PROVINCE_SHORT,
	    	(SELECT CURRENCY FROM EXP_CLIENT_BILLING
			    		WHERE SHIPMENT_ID = SHIPMENT_CODE LIMIT 1) AS CURRENCY,
			(SELECT CDN_TAX_EXEMPT FROM EXP_CLIENT
				WHERE CLIENT_CODE = BILLTO_CLIENT_CODE LIMIT 1) AS CLIENT_EXEMPT,
			(SELECT CLIENT_NAME FROM EXP_CLIENT
				WHERE CLIENT_CODE = BILLTO_CLIENT_CODE LIMIT 1) AS CLIENT_NAME,
			BILLTO_CLIENT_CODE,
			CDN_TAX_EXEMPT AS SHIPMENT_EXEMPT
	    	FROM EXP_SHIPMENT
	    	WHERE SHIPMENT_CODE = ".$shipment );
		
		// Has to start and end in Canada
		if( is_array($check) && isset($check["SHIPPER_COUNTRY"]) &&
			isset($check["CONS_COUNTRY"]) &&
			$check["SHIPPER_COUNTRY"] == 'Canada' &&
			$check["CONS_COUNTRY"] == 'Canada') {
				
			// Client exempt / zero rated
			if( isset($check["CLIENT_EXEMPT"]) && $check["CLIENT_EXEMPT"] == 0 ) {
			
				// Shipment exempt / zero rated
				if( isset($check["SHIPMENT_EXEMPT"]) && $check["SHIPMENT_EXEMPT"] == 0 ) {
			
					// Based upon destination province
					if( isset($check["CONS_STATE"])) {
						$rates = $this->fetch_rows( "PROVINCE = '".$check["CONS_STATE"]."'");
						
						if( is_array($rates) && count($rates) == 1 ) {
							$taxes = $rates[0]["APPLICABLE_TAX"];
							
							// If not start and end in Quebec, just GST
							if( isset($check["SHIPPER_STATE"]) &&
								isset($check["CONS_STATE"]) &&
								$check["SHIPPER_STATE"] <> 'QC' &&
								$check["CONS_STATE"] == 'QC' ) {
								$taxes = 'GST';
							}
		
							$result = array();
							foreach( explode('+',$taxes) as $tax ) {
								switch( $tax ) {
									case 'GST': $rate = $rates[0]["GST_RATE"]; break;
									case 'HST': $rate = $rates[0]["HST_RATE"]; break;
									case 'QST': $rate = $rates[0]["QST_RATE"]; break;
									default: $rate = 0; break;
								}
								$result[] = array( 'province' => $check["PROVINCE"],
									'province_short' => $check["PROVINCE_SHORT"],
									'qboe_name' => $rates[0]["QBOE_NAME"],
									'tax' => $tax, 'rate' => $rate,
									'currency' => $check["CURRENCY"],
									'company' => $check["COMPANY_CODE"] );
							}
						} else {
							$this->issue = 'Missing rates for province '.$check["CONS_STATE"];
						}
					} else {
						$this->issue = 'Consignee province not set for shipment '.$shipment;
					}
				} else {
					$this->issue = 'Shipment <a href="exp_addshipment.php?CODE='.$shipment.'">'.$shipment.'</a> marked as tax exempt';
				}
			} else {
				$this->issue = 'Client <a href="exp_editclient.php?CODE='.$check["BILLTO_CLIENT_CODE"].'">'.$check["CLIENT_NAME"].'</a> marked as tax exempt';
			}
		} else {
			$this->issue = 'Shipment <a href="exp_addshipment.php?CODE='.$shipment.'">'.$shipment.'</a> does not start and end in Canada';
		}
		return $result;
    }
    
    //! SCR# 635 - Get tax rate(s) for Canadian lumper/handling
    // returns false if not applicable, or else an array of tax and rate
    public function lumper_tax_rates( $load ) {
	    $result = false;
	    $this->issue = '';
	    
	    $check = $this->database->get_one_row("
			SELECT CONS_STATE, CONS_COUNTRY, COALESCE(CARRIER_HANDLING, 0) AS LUMPER_FEE,
			(SELECT COMPANY_CODE FROM EXP_OFFICE 
			WHERE EXP_OFFICE.OFFICE_CODE = EXP_LOAD.OFFICE_CODE LIMIT 1) AS COMPANY_CODE,
			(SELECT STATE_NAME FROM EXP_STATES WHERE ABBREV = CONS_STATE LIMIT 1) AS PROVINCE,
			CONS_STATE AS PROVINCE_SHORT, LUMPER_CURRENCY,
			EXP_LOAD.CDN_TAX_EXEMPT AS LOAD_EXEMPT,
			LUMPER,
			(SELECT CDN_TAX_EXEMPT FROM EXP_CARRIER
				WHERE LUMPER = CARRIER_CODE LIMIT 1) AS LUMPER_EXEMPT
			
			FROM EXP_LOAD, EXP_SHIPMENT
			WHERE EXP_LOAD.LOAD_CODE = $load
			AND EXP_LOAD.LOAD_CODE = EXP_SHIPMENT.LOAD_CODE
			AND CONS_COUNTRY = 'CANADA'
			ORDER BY SHIPMENT_CODE DESC
			LIMIT 1");
			
		// Has to end in Canada
		if( is_array($check) && isset($check["CONS_COUNTRY"]) &&
			$check["CONS_COUNTRY"] == 'Canada') {

			// Lumper exempt / zero rated
			if( isset($check["LUMPER_EXEMPT"]) && $check["LUMPER_EXEMPT"] == 0 ) {
			
				// Load exempt / zero rated
				if( isset($check["LOAD_EXEMPT"]) && $check["LOAD_EXEMPT"] == 0 ) {
	
					// Based upon destination province
					if( isset($check["CONS_STATE"])) {
						$rates = $this->fetch_rows( "PROVINCE = '".$check["CONS_STATE"]."'");
						
						if( is_array($rates) && count($rates) == 1 ) {
							$taxes = $rates[0]["APPLICABLE_TAX"];
							
							// If not  end in Quebec, just GST
							if( $check["CONS_STATE"] == 'QC' ) {
								$taxes = 'GST';
							}
		
							$result = array();
							foreach( explode('+',$taxes) as $tax ) {
								switch( $tax ) {
									case 'GST': $rate = $rates[0]["GST_RATE"]; break;
									case 'HST': $rate = $rates[0]["HST_RATE"]; break;
									case 'QST': $rate = $rates[0]["QST_RATE"]; break;
									default: $rate = 0; break;
								}
								$result[] = array( 'province' => $check["PROVINCE"],
									'province_short' => $check["PROVINCE_SHORT"],
									'qboe_name' => $rates[0]["QBOE_NAME"],
									'lumper' => $check["LUMPER_FEE"],
									'tax' => $tax, 'rate' => $rate,
									'currency' => $check["LUMPER_CURRENCY"],
									'company' => $check["COMPANY_CODE"] );
							}
						} else {
							$this->issue = 'Missing rates for province '.$check["CONS_STATE"];
						}
					} else {
						$this->issue = 'Consignee province not set for load '.$load;
					}
				} else {
					$this->issue = 'Load <a href="exp_viewload.php?CODE='.$load.'">'.$load.'</a> marked as tax exempt';
				}
			} else {
					$this->issue = 'Lumper/Shag <a href="exp_editcarrier.php?CODE='.$check["LUMPER"].'">'.$check["LUMPER"].'</a> tax exempt (not an issue)';
			}
		} else {
			$this->issue = 'Load <a href="exp_viewload.php?CODE='.$load.'">'.$load.'</a> does not end in Canada';
		}
				
		return $result;
	}

    public function get_issue() {
	    return $this->issue;
    }
}

//! Form Specifications - For use with sts_form

$sts_form_editcanada_tax_form = array( //!$sts_form_editcanada_tax_form
	'title' => '<img src="images/tax_icon.png" alt="tax_icon" height="24"> Edit Canadian Tax Rate',
	'action' => 'exp_edittax.php',
	//'actionextras' => 'disabled',
	'cancel' => 'exp_listtax.php',
	'name' => 'editoffice',
	'okbutton' => 'Save Changes',
	'cancelbutton' => 'Back',
		'layout' => '
	%TAX_CODE%
	<div class="form-group">
		<div class="col-sm-12">
			<div class="form-group tighter">
				<label for="PROVINCE" class="col-sm-2 control-label">#PROVINCE#</label>
				<div class="col-sm-3">
					%PROVINCE%
				</div>
			</div>
			<div class="form-group tighter">
				<label for="FULLNAME" class="col-sm-2 control-label">#FULLNAME#</label>
				<div class="col-sm-3">
					%FULLNAME%
				</div>
			</div>
			<div class="form-group tighter">
				<label for="APPLICABLE_TAX" class="col-sm-2 control-label">#APPLICABLE_TAX#</label>
				<div class="col-sm-3">
					%APPLICABLE_TAX%
				</div>
			</div>
			<div class="form-group tighter">
				<label for="GST_RATE" class="col-sm-2 control-label">#GST_RATE#</label>
				<div class="col-sm-3">
					%GST_RATE%
				</div>
			</div>
			<div class="form-group tighter">
				<label for="HST_RATE" class="col-sm-2 control-label">#HST_RATE#</label>
				<div class="col-sm-3">
					%HST_RATE%
				</div>
			</div>
			<div class="form-group tighter">
				<label for="QST_RATE" class="col-sm-2 control-label">#QST_RATE#</label>
				<div class="col-sm-3">
					%QST_RATE%
				</div>
			</div>
			<!-- QB01 -->
			<div class="form-group tighter">
				<label for="QBOE_NAME" class="col-sm-2 control-label">#QBOE_NAME#</label>
				<div class="col-sm-3">
					%QBOE_NAME%
				</div>
				<div class="col-sm-6 bg-info">
					<p>This name is used to lookup a tax rate in Quickbooks Online.</p>
					<p>If this is blank it combines the TAX and the PROVINCE with a space between. Example: GST AB</p>
					<p>For Quebec taxes, use a + to separarate GST vs both GST+QST</p>
				</div>
			</div>
			<!-- QB02 -->
		</div>
	</div>
	
	'
);

$sts_form_edit_canada_tax_fields = array(	//! $sts_form_edit_canada_tax_fields
	'TAX_CODE' => array( 'format' => 'hidden' ),
	'PROVINCE' => array( 'label' => 'Province', 'format' => 'text', 'extras' => 'disabled' ),
	'FULLNAME' => array( 'label' => 'Name', 'format' => 'text', 'extras' => 'disabled' ),
	'APPLICABLE_TAX' => array( 'label' => 'Applicable Tax', 'format' => 'text', 'extras' => 'disabled' ),
	'GST_RATE' => array( 'label' => 'GST Rate', 'format' => 'number', 'align' => 'right' ),
	'HST_RATE' => array( 'label' => 'HST Rate', 'format' => 'number', 'align' => 'right' ),
	'QST_RATE' => array( 'label' => 'QST Rate', 'format' => 'number', 'align' => 'right' ),
	'QBOE_NAME' => array( 'label' => 'QBOE Name', 'format' => 'text' ),
);

//! Layout Specifications - For use with sts_result

$sts_result_canada_tax_layout = array(	//! $sts_result_canada_tax_layout
	'TAX_CODE' => array( 'format' => 'hidden' ),
	'PROVINCE' => array( 'format' => 'text' ),
	'FULLNAME' => array( 'label' => 'Name', 'format' => 'text' ),
	'APPLICABLE_TAX' => array( 'label' => 'Applicable Tax', 'format' => 'text' ),
	'GST_RATE' => array( 'label' => 'GST Rate', 'format' => 'number', 'align' => 'right' ),
	'HST_RATE' => array( 'label' => 'HST Rate', 'format' => 'number', 'align' => 'right' ),
	'QST_RATE' => array( 'label' => 'QST Rate', 'format' => 'number', 'align' => 'right' ),
	'QBOE_NAME' => array( 'label' => 'QBOE Name', 'format' => 'text' ),
	'CHANGED_DATE' => array( 'label' => 'Changed', 'format' => 'timestamp-s', 'length' => 90 ),
	'CHANGED_BY' => array( 'label' => 'By', 'format' => 'table',
		'table' => USER_TABLE, 'key' => 'USER_CODE', 'fields' => 'USERNAME', 'link' => 'exp_edituser.php?CODE=' )
);

//! Edit/Delete Button Specifications - For use with sts_result

$sts_result_canada_tax_edit = array( //! $sts_result_canada_tax_edit
	'title' => '<img src="images/tax_icon.png" alt="tax_icon" height="24"> Canadian Tax Rates',
	'sort' => 'PROVINCE asc',
	'cancel' => 'index.php',
	//'actionextras' => 'disabled',
	'cancelbutton' => 'Back',
	'rowbuttons' => array(
		array( 'url' => 'exp_edittax.php?CODE=', 'key' => 'TAX_CODE', 'label' => 'FULLNAME', 'tip' => 'Edit Rates for ', 'icon' => 'glyphicon glyphicon-edit' ),
	)
);
