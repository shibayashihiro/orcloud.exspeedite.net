<?php

// $Id: sts_exchange_class.php 4697 2022-03-09 23:02:23Z duncan $
// Exchange class - currency exchange

// no direct access
defined('_STS_INCLUDE') or die('Restricted access');

require_once( "sts_table_class.php" );

require_once( "sts_setting_class.php" );
require_once( "sts_shipment_class.php" );

class sts_exchange extends sts_table {

	private $setting_table;
	private $shipment_table;
	public $enabled;
	public $currency_list;
	public $trans_date;
	private $latest = 'http://api.fixer.io/latest';
	private $get_url = 'http://api.fixer.io/';
	// Bank of Canada
	private $boc_url = 'https://www.bankofcanada.ca/valet/observations/FXUSDCAD/json?start_date=';
	private $one_day = 86400; // 60 * 60 * 24 seconds

	// Constructor does not need the table name
	public function __construct( $database, $debug = false ) {

		$this->debug = $debug;
		$this->primary_key = "EXCHANGE_CODE";
		if( $this->debug ) echo "<p>Create sts_exchange</p>";
		$this->setting_table = sts_setting::getInstance($database, $debug);
		$this->enabled = ($this->setting_table->get( 'option', 'MULTI_CURRENCY' ) == 'true');
		if( $this->enabled ) {
			$this->trans_date = $this->setting_table->get( 'api', 'QUICKBOOKS_TRANSACTION_DATE' );

			if( $this->debug ) echo "<p>".__METHOD__.": enabled.</p>";
			$this->shipment_table = sts_shipment::getInstance($database, $debug);
		} else {
			if( $this->debug ) echo "<p>".__METHOD__.": disabled.</p>";
		}

		parent::__construct( $database, EXCHANGE_TABLE, $debug);
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
    
    //! Get rates from the Bank of Canada
    public function get_boc_rates() {
	    if( $this->debug ) echo "<p>".__METHOD__.": entry, ".($this->enabled ? "enabled" : "disabled")."</p>";
	    if( $this->enabled ) {
	    	// Check no more than once per day
	    	$last_checked = intval($this->setting_table->get("api", "BOC_LAST_DL"));
	    	$now = time();
	    	if( $this->debug || $now > $last_checked + $this->one_day ) {
				// Get the data for the last 6 months
				$url = $this->boc_url.date("Y-m-d", strtotime("-6 months") );
								
				$raw = file_get_contents($url);
				if( isset($raw)) {
					$response = json_decode($raw, true);
					if( $this->debug ) {
						echo "<pre>".$url."\n";
						var_dump($response);
						echo "</pre>";
					}
					if( is_array($response["observations"]) && count($response["observations"]) > 0 ) {
						$this->setting_table->set("api", "BOC_LAST_DL", $now);
						foreach($response["observations"] as $row) {
							// $row["d"] - date
							// $row["FXUSDCAD"]["v"] - rate
							// $row["FXUSDCAD"]["e"] - error, such as holiday
							if( is_array($row["FXUSDCAD"]) && isset($row["FXUSDCAD"]["v"])) {
								$check = $this->fetch_rows("ASOF = '".$row["d"]."' AND
									FROM_CURRENCY = 'USD' AND
									TO_CURRENCY = 'CAD'");
								
								if( ! is_array($check) || count($check) == 0 ) {
									$fields = array(
										"FROM_CURRENCY" => 'USD',
										"TO_CURRENCY" => 'CAD',
										"EXCHANGE_RATE" => floatval($row["FXUSDCAD"]["v"]),
										"ASOF" => $row["d"],
										"RATE_SOURCE" => 'bankofcanada.ca',
									);
									$this->add( $fields );
								}
							}
						}
					}
				}
			}
	    }
	}

}

//! Layout Specifications - For use with sts_result

$sts_result_em_layout = array( //! $sts_result_em_layout
	'EXCHANGE_CODE' => array( 'format' => 'hidden' ),	// primary key must be first
	'FROM_CURRENCY' => array( 'label' => 'From Currency', 'format' => 'enum' ),
	'TO_CURRENCY' => array( 'label' => 'To Currency', 'format' => 'enum' ),
	'ASOF' => array( 'label' => 'Date', 'format' => 'date' ),
	'EXCHANGE_RATE' => array( 'label' => 'Exchange Rate', 'format' => 'number', 'align' => 'right' ),
	'RATE_SOURCE' => array( 'label' => 'Source', 'format' => 'text' ),
	'CREATED_DATE' => array( 'label' => 'Added', 'format' => 'date' ),
);

//! Edit/Delete Button Specifications - For use with sts_result

$sts_result_em_edit = array(
	'title' => '<img src="images/exch_icon.png" alt="exch_icon" height="32"> Daily Exchange Rates, from the <a href="https://www.bankofcanada.ca/rates/exchange/daily-exchange-rates/" target="_blank" class="tip-bottom" title="All Bank of Canada exchange rates are indicative rates only, derived from averages of transaction prices and price quotes from financial institutions. As such, they are intended to be broadly indicative of market prices at the time of publication but do not necessarily reflect the rates at which actual market transactions have been or could be conducted. They may differ from the rates provided by financial institutions and other market sources. Bank of Canada exchange rates are released for statistical and analytical purposes only and are not intended to be used as the benchmark rate for executing foreign exchange trades. The Bank of Canada does not guarantee the accuracy or completeness of these exchange rates. The underlying data is sourced from Thomson Reuters.">Bank Of Canada</a>',
	'sort' => 'EXCHANGE_CODE asc',
	'cancel' => 'index.php',
	//'add' => 'exp_addem.php',
	//'actionextras' => 'disabled',
	//'addbutton' => 'Add Exchange Rate',
	'cancelbutton' => 'Back',
);


?>
