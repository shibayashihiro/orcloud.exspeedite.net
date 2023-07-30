<?php

// $Id: sts_card_class.php 5110 2023-05-22 17:36:50Z duncan $
// Card class - generic to do with decoding and importing fuel cards

// no direct access
defined('_STS_INCLUDE') or die('Restricted access');
require_once( "sts_email_class.php" );
require_once( "sts_setting_class.php" );
require_once( "sts_tractor_class.php" );
require_once( "sts_driver_class.php" );
require_once( "sts_ifta_log_class.php" );
require_once( "sts_user_log_class.php" );

class sts_card extends sts_table {
	protected $setting_table;
	public $last_error;
	protected $tractor;
	protected $driver;
	protected $ifta_log;
	protected $user_log;
	protected $sts_ifta_base;
	protected $multi_company;
	protected $import_cash_adv;
	protected $card_advance;
	protected $logger;
	protected $state_abbrev;
	protected $tractor_card;
	protected $driver_card;
	protected $count_ifta_import = 0;			//! SCR# 554 - Add more visibility to import process
	protected $count_ifta_import_error = 0;
	protected $count_ifta_duplicate = 0;
	protected $count_advance_import = 0;
	protected $count_advance_import_error = 0;
	protected $count_advance_duplicate = 0;
		
	// Constructor does not need the table name
	public function __construct( $database, $debug = false, $logger = null ) {

		$this->debug = $debug;
		$this->logger = $logger;
		$this->primary_key = "FTP_CODE";
		parent::__construct( $database, CARD_FTP_TABLE, $debug);

		$this->setting_table = sts_setting::getInstance($this->database, $this->debug);
		$this->sts_ifta_base = $this->setting_table->get( 'api', 'IFTA_BASE_JURISDICTION' );
		$this->multi_company = ($this->setting_table->get( 'option', 'MULTI_COMPANY' ) == 'true');
		$this->import_cash_adv = ($this->setting_table->get( 'option', 'IMPORT_CASH_ADVANCES' ) == 'true');
		$this->card_advance = new sts_table( $database, CARD_ADVANCE_TABLE, $this->debug );
		$this->tractor = sts_tractor::getInstance($this->database, $this->debug);
		$this->driver = sts_driver::getInstance($this->database, $this->debug);
		$this->tractor_card = sts_tractor_card::getInstance($this->database, $this->debug);
		$this->driver_card = sts_driver_card::getInstance($this->database, $this->debug);
		$this->ifta_log = sts_ifta_log::getInstance($this->database, $this->debug);
		$this->user_log = sts_user_log::getInstance($this->database, $this->debug);
		$this->load_states();
	}
	
	// Allow re-use of objects - singleton function
	public static function getInstance( $database, $debug = false, $logger = null ) {
		static $instance = null;
		$myclass = get_class ();
		if( $debug ) echo "<p>Get instance of $myclass</p>";
		if (null === $instance) {
			$instance = new $myclass( $database, $debug, $logger );
		}
		return $instance;
    }
    
	private function load_states() {
		$states_table = new sts_table($this->ifta_log->database, STATES_TABLE, false );
		$this->state_abbrev = array();
		
		foreach( $states_table->fetch_rows() as $row ) {
			$this->state_abbrev[strtolower($row['STATE_NAME'])] = $row['abbrev'];
		}
	}

	protected function lookup_state( $state ) {
		if( isset($this->state_abbrev[strtolower($state)])) {
			$result = $this->state_abbrev[strtolower($state)];
		} else {
			$result = $state;
		}
		return $result;
	}
	
	// Make sure the currency is in the right format
	protected function lookup_currency( $curr ) {
		switch( strtoupper($curr[0]) ) {
			case 'C':
				$result = 'CAD';
				break;
			case 'U':
			default:
				$result = 'USD';
				break;
		}
		return $result;
	}

	//! SCR# 554 - Add more visibility to import process
    public function get_import_stats() {
	    return array(
		    'ifta_import'			=> $this->count_ifta_import,
		    'ifta_import_error'		=> $this->count_ifta_import_error,
			'ifta_duplicate'		=> $this->count_ifta_duplicate,
			'advance_import'		=> $this->count_advance_import,
			'advance_import_error'	=> $this->count_advance_import_error,
			'advance_duplicate'		=> $this->count_advance_duplicate,		    
	    );
    }
    
    protected function log_import( $origin, $result ) {
	    $this->user_log->log_event('finance', ($result ? '' : 'Failed ').'fuel card import from '.$origin.
	    	"<br>IFTA import/error/duplicate = ".
			$this->count_ifta_import."/".
			$this->count_ifta_import_error."/".
			$this->count_ifta_duplicate."<br>ADVANCE import/error/duplicate = ".
			$this->count_advance_import."/".
			$this->count_advance_import_error."/".
			$this->count_advance_duplicate );
    }
    
    public function dump_row( $row ) {
	    $result = '';
	    if( $this->debug ) echo "<p>".__METHOD__.": entry.</p>";
	    if( is_array($row) ) {
		    $result .= '<div class="table-responsive">
			<table class="display table table-striped table-condensed table-bordered table-hover" id="FUEL_CARDS">
			<thead><tr class="exspeedite-bg">
			<th>Date<br>Trans#</th><th>Tractor<br>Trailer</th>
			<th>Address</th><th>Tractor Gal<br>Tractor PPG</th>
			<th>Reefer Gal<br>Reefer PPG</th><th>Hub<br>Prev Hub</th>
			<th>Driver<br>Emp num</th><th>Card</th>
			</tr>
			</thead>
			<tbody>';


		    $result .= '<tr>
				<td><strong>'.date("m/d/Y", strtotime($row['IFTA_DATE'])).'<br>'.$row['CD_TRANS'].'</strong></td>
				<td><strong>'.$row['CD_UNIT'].'</strong><br>'.$row['CD_TRAILER'].'</td>
				<td>'.$row['CD_STOP_NAME'].'<br>'.$row['CD_STOP_CITY'].' <strong>'.$row['IFTA_JURISDICTION'].'</strong></td>
				<td class="text-right"><strong>'.number_format($row['FUEL_PURCHASED'], 2).'</strong><br>'.number_format($row['CD_TRACTOR_PPG'], 3).'</td>
				<td class="text-right">'.number_format($row['CD_REEFER_GAL'], 2).'<br>'.number_format($row['CD_TRACTOR_PPG'], 3).'</td>
				<td class="text-right">'.$row['CD_HUB'].'<br>'.$row['CD_PREV_HUB'].'</td>
				<td>'.$row['CD_DRIVER'].'<br>'.$row['CD_EMPLOYEE_NUM'].'</td>
				<td>'.$row['CD_CARD_NUM'].'</td>
			</tr>';

	   		$result .= '</tbody>
			</table>
			</div>';

	    }
	    return $result;
	}

     public function dump_advance_row( $row ) {
	    $result = '';
	    if( $this->debug ) echo "<p>".__METHOD__.": entry.</p>";
	    if( is_array($row) ) {
		    $result .= '<div class="table-responsive">
			<table class="display table table-striped table-condensed table-bordered table-hover" id="FUEL_CARDS">
			<thead><tr class="exspeedite-bg"><th>Source</th>
			<th>Date<br>Trans#</th>
			<th>Address</th>
			<th>Advance</th><th>Fee</th>
			<th>Driver</th><th>Trip#</th><th>Currency</th>
			<th>Card ID</th><th>Card Num</th>
			</tr>
			</thead>
			<tbody>';


		    $result .= '<tr>
				<td>'.$row['CARD_SOURCE'].'</td>
				<td>'.date("m/d/Y", strtotime($row['TRANS_DATE'])).'<br>
				'.$row['TRANS_NUM'].'</td>
				<td>'.$row['STOP'].'<br>'.$row['CITY'].', '.$row['STATE'].'</td>
				<td class="text-right"><strong>'.number_format($row['CASH_ADV'], 2).'</strong></td>
				<td class="text-right">'.number_format($row['CASH_ADV_FEE'], 2).'</td>
				<td>'.$row['DRIVER_NAME'].'</td>
				<td>'.$row['TRIP_NUM'].'</td>
				<td>'.$row['CURRENCY_CODE'].'</td>
				<td>'.$row['FUEL_CARD_ID'].'</td>
				<td><strong>'.$row['CARD_NUM'].'</strong></td>
			</tr>';

	   		$result .= '</tbody>
			</table>
			</div>';

	    }
	    return $result;
	}

	public function count_unresolved() {
		$result = 0;
		$check = $this->ifta_log->fetch_rows( "COALESCE(IFTA_TRACTOR, 0) = 0 AND COALESCE(CD_UNIT,'') != ''", "count(*) NUM");
		if( is_array($check) && count($check) > 0 )
			$result = intval($check[0]['NUM']);
	    if( $this->debug ) echo "<p>".__METHOD__.": return $result</p>";
		return $result;
	}

	public function count_unresolved_advances() {
		$result = 0;
		$check = $this->card_advance->fetch_rows( "COALESCE(DRIVER_CODE, 0) = 0", "count(*) NUM");
		if( is_array($check) && count($check) > 0 )
			$result = intval($check[0]['NUM']);
	    if( $this->debug ) echo "<p>".__METHOD__.": return $result</p>";
		return $result;
	}

	public function tf( $n, $x ) {
		return $n.' = '.($x ? 'true' : 'false').', ';
	}
	
	//! Find an IFTA log entry without a tractor
	public function find_log_to_resolve() {
		$result = false;
	    if( $this->debug ) echo "<p>".__METHOD__.": entry</p>";
		
	//	$check = $this->ifta_log->fetch_rows( "COALESCE(IFTA_TRACTOR, 0) = 0 AND COALESCE(CD_UNIT, '') != ''",
		$check = $this->ifta_log->fetch_rows( "COALESCE(IFTA_TRACTOR, 0) = 0",
			"*", "IFTA_DATE ASC", "1");
		
		if( is_array($check) && count($check) > 0 ) {
			$result = $check[0];			
			$match = "ISACTIVE = 'Active' 
				AND NOT EXISTS (SELECT * FROM EXP_TRACTOR_CARD
					WHERE CARD_SOURCE = '".$check[0]["CD_ORIGIN"]."'
					AND EXP_TRACTOR.TRACTOR_CODE = EXP_TRACTOR_CARD.TRACTOR_CODE)";
            
			// Perhaps the UNIT_NUMBER matches the CD_UNIT, send it as a suggestion
			$suggestion = $this->tractor->fetch_rows("UNIT_NUMBER = '".$check[0]['CD_UNIT']."'", "TRACTOR_CODE, UNIT_NUMBER");
		    if( is_array($suggestion) && count($suggestion) == 1)
		    	$result['MENU'] = $this->tractor->tractor_menu( $suggestion[0]['TRACTOR_CODE'], 'TRACTOR_CODE', $match, false );
		    else
		    	$result['MENU'] = $this->tractor->tractor_menu( false, 'TRACTOR_CODE', $match, false );
			
		}
		
	    if( $this->debug ) echo "<p>".__METHOD__.": return ".$this->tf('result', $result)."</p>";
		return $result;
	}

	//! Find a card advance entry without a driver
	public function find_card_advance_to_resolve() {
		$result = false;
		
		$check = $this->card_advance->fetch_rows( "COALESCE(DRIVER_CODE, 0) = 0",
			"*", "ADVANCE_CODE ASC", "1");
		
		if( is_array($check) && count($check) > 0 ) {
			$result = $check[0];
			$match = "ISACTIVE = 'Active' 
				AND NOT EXISTS (SELECT DRIVER_CODE FROM EXP_DRIVER_CARD
					WHERE CARD_SOURCE = '".$check[0]["CARD_SOURCE"]."'
					AND CARD_NUM = '".$check[0]["CARD_NUM"]."')";
            
			$result['MENU'] = $this->driver->driver_menu( false, 'DRIVER_CODE', $match, false );
		}
		
		return $result;
	}

	//! Remove unwanted data for a unit#
	public function discard( $ifta_log_code, $cd_unit ) {
		$result = $this->ifta_log->delete_row( $this->ifta_log->primary_key." =".$ifta_log_code.
			" AND CD_UNIT = '$cd_unit' AND COALESCE(IFTA_TRACTOR, 0) = 0");
		if( $this->logger )
			$this->logger->log_event( __METHOD__.": discard $ifta_log_code $cd_unit, result = ".($result ? 'true' : 'false'), EXT_ERROR_DEBUG);
		
		$this->user_log->log_event('finance', ($result ? '' : 'Failed ')."discard IFTA_LOG entry $ifta_log_code for unit# $cd_unit");
		return $result;
	}
	
	//! Remove ALL unwanted data for a unit#
	public function discard_all( $cd_unit ) {
		$result = $this->ifta_log->delete_row( "CD_UNIT = '$cd_unit' AND COALESCE(IFTA_TRACTOR, 0) = 0" );
		if( $this->logger )
			$this->logger->log_event( __METHOD__.": discard ALL for $cd_unit, result = ".($result ? 'true' : 'false'), EXT_ERROR_DEBUG);
		
		$this->user_log->log_event('finance', ($result ? '' : 'Failed ')."discard_all IFTA_LOG entries for unit# $cd_unit");
		return $result;
	}
	
	//! Resolve an IFTA log entry without a tractor
	public function resolve( $ifta_log_code, $source, $cd_unit, $tractor_code ) {
		$result = false;
	    $fields = array(
	    	'CARD_SOURCE' => $source,
	    	'UNIT_NUMBER' => $cd_unit,
	    	'TRACTOR_CODE' => $tractor_code
	    );
		
		$result = $this->tractor_card->add( $fields );
		if( $result ) {
			$changes = array('IFTA_TRACTOR' => $tractor_code);
			
			$result = $this->ifta_log->update("CD_UNIT = '$cd_unit' AND COALESCE(IFTA_TRACTOR, 0) = 0",
				$changes);
		}

		if( $this->logger )
			$this->logger->log_event( __METHOD__.": resolve $cd_unit => $tractor_code, result = ".($result ? 'true' : 'false'), EXT_ERROR_DEBUG);
		$this->user_log->log_event('finance', ($result ? '' : 'Failed ')."resolve unit# $cd_unit => tractor $tractor_code");
		return $result;
	}

	//! Discard an advance entry
	public function discard_advance( $advance_code ) {
		$result = $this->card_advance->delete_row( "ADVANCE_CODE = ".$advance_code." AND
			COALESCE(DRIVER_CODE, 0) = 0" );
		if( $this->logger )
			$this->logger->log_event( __METHOD__.": discard $advance_code, result = ".($result ? 'true' : 'false'), EXT_ERROR_DEBUG);
		
		$this->user_log->log_event('finance', ($result ? '' : 'Failed ')."discard_advance $advance_code");
		return $result;
	}
	
	//! Discard all advances for a given card number
	public function discard__all_advance( $card_num ) {
		$result = $this->card_advance->delete_row( "CARD_NUM = '".$card_num."' AND
			COALESCE(DRIVER_CODE, 0) = 0" );
		if( $this->logger )
			$this->logger->log_event( __METHOD__.": discard $card_num, result = ".($result ? 'true' : 'false'), EXT_ERROR_DEBUG);
		
		$this->user_log->log_event('finance', ($result ? '' : 'Failed ')."discard__all_advance for card# $card_num");
		return $result;
	}
	
	//! Resolve an advance entry without a driver
	public function resolve_advance( $advance_code, $source, $card_num, $driver_code ) {
		$result = false;
	    $fields = array(
	    	'CARD_SOURCE' => $source,
	    	'CARD_NUM' => $card_num,
	    	'DRIVER_CODE' => $driver_code
	    );
		
		$result = $this->driver_card->add( $fields );
		
		if( $result ) {			
			$result = $this->card_advance->update("ADVANCE_CODE = $advance_code",
				array( 'DRIVER_CODE' => $driver_code ));
		}

		if( $this->logger )
			$this->logger->log_event( __METHOD__.": resolve $advance_code, $source, $card_num => $driver_code, result = ".($result ? 'true' : 'false'), EXT_ERROR_DEBUG);
		$this->user_log->log_event('finance', ($result ? '' : 'Failed ')."resolve $advance_code, $source, $card_num => driver $driver_code");

		return $result;
	}

	//! Import Yard fuel data
	public function import_csv( $files, $fuel_type ) {
		if ($files["error"] > 0) {
			echo "<p>Error: " . $files["error"] . "</p>";
		} else {
			if( strtolower( substr(strrchr($files["name"], "."), 1)) <> 'csv' ) {
				echo "<p>Error: file needs to end in .csv</p>";
			} else {
				ini_set("auto_detect_line_endings", true);
				if(($handle = fopen($files["tmp_name"], 'r')) !== FALSE) {
					set_time_limit(0);
					while(($data = fgetcsv($handle, 1000, ',')) !== FALSE) {
						if( count($data) >= 3 ) {
							$check = date_parse($data[0]);
							if( count($check["errors"]) == 0 && is_numeric($data[2]) ) {
								$fields = array( 'IFTA_DATE' => date("Y-m-d", strtotime($data[0])),
							    	'CD_STOP_NAME' => "Yard", 'CD_ORIGIN' => "CSV",
							    	'IFTA_JURISDICTION' => $this->sts_ifta_base );
							    if( $fuel_type == "TRUCKS" ) {
								   $fields['IFTA_TRACTOR'] = 0;
								   
								   // Lookup tractor based on UNIT_NUMBER
								   $check = $this->tractor->fetch_rows("UNIT_NUMBER = '".$data[1]."' AND LOG_IFTA = true", "TRACTOR_CODE");
								   if( is_array($check) && count($check) == 1) {
								   	$fields['IFTA_TRACTOR'] = $check[0]['TRACTOR_CODE'];
								   } else {
									   // Lookup tractor based on comdata unit
									   //$check = $this->tractor->fetch_rows("COMDATA_UNIT = '".$data[1]."' AND LOG_IFTA = true", "TRACTOR_CODE");
									 //  if( is_array($check) && count($check) == 1)
									 //  		$fields['IFTA_TRACTOR'] = $check[0]['TRACTOR_CODE'];
								   	}

								   $fields['CD_UNIT'] = $data[1];
								   $fields['FUEL_PURCHASED'] = $data[2];
							    } else {
								   $fields['IFTA_TRACTOR'] = 0;
								   $fields['CD_UNIT'] = 0;
								   $fields['CD_TRAILER'] = $data[1];
								   $fields['CD_REEFER_GAL'] = $data[2];
							    }
							    
								$result = $this->ifta_log->add( $fields );
							    if( ! $result )
							    	break;
								}
						}
					}
					fclose($handle);
				}
			}
		}
	}

}

class sts_driver_card extends sts_table {

	// Constructor does not need the table name
	public function __construct( $database, $debug = false ) {

		$this->debug = $debug;
		$this->primary_key = "FUEL_CARD_CODE";
		if( $this->debug ) echo "<p>Create sts_driver_card</p>";
		parent::__construct( $database, DRIVER_CARD_TABLE, $debug);
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
    
    public function lookup( $card_num, $origin ) {
	    $result = false;
	    
	    if( !empty($card_num) && ! empty($origin) ) {
		    $check = $this->fetch_rows("CARD_SOURCE = '".$origin."' AND CARD_NUM = '".$card_num."'", "DRIVER_CODE");
		    if( is_array($check) && count($check) > 0 && isset($check[0]['DRIVER_CODE']) ) {
			    $result = $check[0]['DRIVER_CODE'];
		    }
	    }
	    
	    return $result;
    }
    
    public function add_driver_card( $driver_code, $card_num, $origin ) {
	    $fields = [
		    'DRIVER_CODE' => $driver_code,
		    'CARD_NUM' => $card_num,
		    'CARD_SOURCE' => $origin
	    ];
	    
	    return $this->add( $fields );
    }
    
}

class sts_tractor_card extends sts_table {

	// Constructor does not need the table name
	public function __construct( $database, $debug = false ) {

		$this->debug = $debug;
		$this->primary_key = "FUEL_CARD_CODE";
		if( $this->debug ) echo "<p>Create sts_tractor_card</p>";
		parent::__construct( $database, TRACTOR_CARD_TABLE, $debug);
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
    
}

//! Layout Specifications - For use with sts_result

$sts_result_driver_card_layout = array(
	'FUEL_CARD_CODE' => array( 'format' => 'hidden' ),
	'CARD_SOURCE' => array( 'label' => 'Vendor', 'format' => 'text' ),
	'CARD_NUM' => array( 'label' => 'Card#', 'format' => 'text' ),
	'DRIVER_CODE' => array( 'label' => 'Driver', 'format' => 'table',
		'table' => DRIVER_TABLE, 'key' => 'DRIVER_CODE', 
		'fields' => 'concat_ws( \' \', FIRST_NAME , LAST_NAME )',
		'link' => 'exp_editdriver.php?CODE=' ),
	'CREATED_DATE' => array( 'label' => 'Created', 'format' => 'timestamp-s' ),
	'CREATED_BY' => array( 'label' => 'Created By', 'format' => 'table',
		'table' => USER_TABLE, 'key' => 'USER_CODE', 'fields' => 'USERNAME', 'link' => 'exp_edituser.php?CODE=' ),
);

//! Edit/Delete Button Specifications - For use with sts_result

$sts_result_driver_card_edit = array(
	'title' => '<img src="images/card_ftp_icon.png" alt="card_ftp_icon" height="40"> Mapping Card# <span class="glyphicon glyphicon-arrow-right"></span> Driver (For Advances)',
	'sort' => 'FUEL_CARD_CODE asc',
	//'cancel' => 'index.php',
	//'add' => 'exp_add_card_ftp.php',
	//'actionextras' => 'disabled',
	//'addbutton' => 'Add Fuel Card Configuration',
	//'cancelbutton' => 'Back',
	'rowbuttons' => array(
		array( 'url' => 'exp_driver_card.php?PW=Society&BACK=exp_list_driver_card.php&DEL_DRIVER=', 'key' => 'FUEL_CARD_CODE', 'label' => 'CARD_NUM', 'tip' => 'Delete Fuel Card Mapping for ', 'icon' => 'glyphicon glyphicon-trash', 'confirm' => 'yes' )
	)
);

$sts_result_tractor_card_layout = array(
	'FUEL_CARD_CODE' => array( 'format' => 'hidden' ),
	'CARD_SOURCE' => array( 'label' => 'Vendor', 'format' => 'text' ),
	'UNIT_NUMBER' => array( 'label' => 'Unit#', 'format' => 'text' ),
	'TRACTOR_CODE' => array( 'label' => 'Tractor', 'format' => 'table',
		'table' => TRACTOR_TABLE, 'key' => 'TRACTOR_CODE', 
		'fields' => 'UNIT_NUMBER',
		'link' => 'exp_edittractor.php?CODE=' ),
	'CREATED_DATE' => array( 'label' => 'Created', 'format' => 'timestamp-s' ),
	'CREATED_BY' => array( 'label' => 'Created By', 'format' => 'table',
		'table' => USER_TABLE, 'key' => 'USER_CODE', 'fields' => 'USERNAME', 'link' => 'exp_edituser.php?CODE=' ),
);

//! Edit/Delete Button Specifications - For use with sts_result

$sts_result_tractor_card_edit = array(
	'title' => '<img src="images/card_ftp_icon.png" alt="card_ftp_icon" height="40"> Mapping Unit# <span class="glyphicon glyphicon-arrow-right"></span> Tractor (For IFTA)',
	'sort' => 'FUEL_CARD_CODE asc',
	'cancel' => 'index.php',
	//'add' => 'exp_add_card_ftp.php',
	//'actionextras' => 'disabled',
	//'addbutton' => 'Add Fuel Card Configuration',
	'cancelbutton' => 'Back',
	'rowbuttons' => array(
		array( 'url' => 'exp_driver_card.php?PW=Society&BACK=exp_list_driver_card.php&DEL_TRACTOR=', 'key' => 'FUEL_CARD_CODE', 'label' => 'UNIT_NUMBER', 'tip' => 'Delete Fuel Card Mapping for ', 'icon' => 'glyphicon glyphicon-trash', 'confirm' => 'yes' )
	)
);

?>