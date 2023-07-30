<?php

// $Id: sts_exchange_month_class.php 3435 2019-03-25 18:53:25Z duncan $
// Exchange month class - monthly currency exchange

// no direct access
defined('_STS_INCLUDE') or die('Restricted access');

require_once( "sts_table_class.php" );

class sts_exchange_month extends sts_table {

	// Constructor does not need the table name
	public function __construct( $database, $debug = false ) {

		$this->debug = $debug;
		$this->primary_key = "EXCHANGE_CODE";
		if( $this->debug ) echo "<p>Create sts_exchange_month</p>";

		parent::__construct( $database, EXCHANGE_MONTH_TABLE, $debug);
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
    
    public function alert_missing() {
		if( $this->debug ) echo "<p>".__METHOD__.": entry</p>";
	    $output = '';
	    $check = $this->fetch_rows("ASOF = concat_ws('/', lpad(month(CURRENT_DATE), 2, '0'), year(CURRENT_DATE))", "COUNT(*) NUM_ROWS");
	    if( is_array($check) && count($check) == 1 &&
	    	isset($check[0]["NUM_ROWS"]) && $check[0]["NUM_ROWS"] == 0 ) {
		    $output = '<p style="margin-bottom: 5px;"><a class="btn btn-sm btn-warning" href="exp_listem.php"><span class="glyphicon glyphicon-warning-sign"></span> Monthly Exchange rates</a> are missing. This will impair currency conversions.</p>';
	    }
	    
	    return $output;
    }

}

//! Form Specifications - For use with sts_form

$sts_form_add_em_form = array(	//! $sts_form_add_em_form
	'title' => '<img src="images/exch_icon.png" alt="exch_icon" height="32"> Add Exchange Rate',
	'action' => 'exp_addem.php',
	//'actionextras' => 'disabled',
	'cancel' => 'exp_listem.php',
	'name' => 'addem',
	'okbutton' => 'Save Changes',
	'cancelbutton' => 'Cancel',	// well well-sm
		'layout' => '
	<div class="form-group">
		<div class="col-sm-6">
			<div class="form-group">
				<label for="FROM_CURRENCY" class="col-sm-6 control-label">#FROM_CURRENCY#</label>
				<div class="col-sm-6">
					%FROM_CURRENCY%
				</div>
			</div>
			<div class="form-group">
				<label for="TO_CURRENCY" class="col-sm-6 control-label">#TO_CURRENCY#</label>
				<div class="col-sm-6">
					%TO_CURRENCY%
				</div>
			</div>
			<div class="form-group">
				<label for="ASOF" class="col-sm-6 control-label">#ASOF#</label>
				<div class="col-sm-6">
					%ASOF%
				</div>
			</div>
			<div class="form-group">
				<label for="EXCHANGE_RATE" class="col-sm-6 control-label">#EXCHANGE_RATE#</label>
				<div class="col-sm-6">
					%EXCHANGE_RATE%
				</div>
			</div>
		</div>
	</div>
	
	'
);

$sts_form_edit_em_form = array( //! $sts_form_edit_em_form
	'title' => '<img src="images/exch_icon.png" alt="exch_icon" height="32"> Edit Exchange Rate',
	'action' => 'exp_editem.php',
	//'actionextras' => 'disabled',
	'cancel' => 'exp_listem.php',
	'name' => 'editem',
	'okbutton' => 'Save Changes',
	'saveadd' => 'Add Another',
	'cancelbutton' => 'Back',
		'layout' => '
		%EXCHANGE_CODE%
	<div class="form-group">
		<div class="col-sm-6">
			<div class="form-group">
				<label for="FROM_CURRENCY" class="col-sm-6 control-label">#FROM_CURRENCY#</label>
				<div class="col-sm-6">
					%FROM_CURRENCY%
				</div>
			</div>
			<div class="form-group">
				<label for="TO_CURRENCY" class="col-sm-6 control-label">#TO_CURRENCY#</label>
				<div class="col-sm-6">
					%TO_CURRENCY%
				</div>
			</div>
			<div class="form-group">
				<label for="ASOF" class="col-sm-6 control-label">#ASOF#</label>
				<div class="col-sm-6">
					%ASOF%
				</div>
			</div>
			<div class="form-group">
				<label for="EXCHANGE_RATE" class="col-sm-6 control-label">#EXCHANGE_RATE#</label>
				<div class="col-sm-6">
					%EXCHANGE_RATE%
				</div>
			</div>
		</div>
	</div>
	
	'
);

//! Field Specifications - For use with sts_form

$sts_form_add_em_fields = array( //! $sts_form_add_em_fields
	'FROM_CURRENCY' => array( 'label' => 'From Currency', 'format' => 'enum' ),
	'TO_CURRENCY' => array( 'label' => 'To Currency', 'format' => 'enum' ),
	'ASOF' => array( 'label' => 'Month', 'format' => 'month', 'placeholder' => 'MM/YYYY' ),
	'EXCHANGE_RATE' => array( 'label' => 'Exchange Rate', 'format' => 'number', 'decimal' => '4', 'align' => 'right' ),
);

$sts_form_edit_em_fields = array( //! $sts_form_edit_em_fields
	'EXCHANGE_CODE' => array( 'format' => 'hidden' ),	// primary key must be first
	'FROM_CURRENCY' => array( 'label' => 'From Currency', 'format' => 'enum' ),
	'TO_CURRENCY' => array( 'label' => 'To Currency', 'format' => 'enum' ),
	'ASOF' => array( 'label' => 'Month', 'format' => 'month', 'placeholder' => 'MM/YYYY' ),
	'EXCHANGE_RATE' => array( 'label' => 'Exchange Rate', 'format' => 'number', 'decimal' => '4', 'align' => 'right' ),
);

//! Layout Specifications - For use with sts_result

$sts_result_em_layout = array( //! $sts_result_em_layout
	'EXCHANGE_CODE' => array( 'format' => 'hidden' ),	// primary key must be first
	'FROM_CURRENCY' => array( 'label' => 'From Currency', 'format' => 'enum' ),
	'TO_CURRENCY' => array( 'label' => 'To Currency', 'format' => 'enum' ),
	'ASOF' => array( 'label' => 'Month', 'format' => 'month' ),
	'EXCHANGE_RATE' => array( 'label' => 'Exchange Rate', 'format' => 'number', 'align' => 'right' ),
);

//! Edit/Delete Button Specifications - For use with sts_result

$sts_result_em_edit = array(
	'title' => '<img src="images/exch_icon.png" alt="exch_icon" height="32"> Monthly Exchange Rates',
	'sort' => 'EXCHANGE_CODE asc',
	'cancel' => 'index.php',
	'add' => 'exp_addem.php',
	//'actionextras' => 'disabled',
	'addbutton' => 'Add Exchange Rate',
	'cancelbutton' => 'Back',
	'rowbuttons' => array(
		array( 'url' => 'exp_editem.php?CODE=', 'key' => 'EXCHANGE_CODE', 'label' => 'ASOF', 'tip' => 'Edit exchange rate ', 'icon' => 'glyphicon glyphicon-edit' ),
	),
);


?>