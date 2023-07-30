<?php

// $Id: exp_get_miles.php 5152 2023-07-07 19:37:42Z dev $
// API to PC*Miler


// no direct access
defined('_STS_INCLUDE') or die('Restricted access');

require_once(  __DIR__."/../include/sts_setting_class.php" );
require_once( __DIR__."/../include/sts_timer_class.php" );
require_once( __DIR__."/../include/sts_table_class.php" );
require_once( __DIR__."/../include/sts_email_class.php" );
require_once( __DIR__."/../include/sts_zip_class.php" );

if( ini_get('safe_mode') ){
   // safe mode is on
   ini_set('max_execution_time', 1200);		// Set timeout to 20 minutes
   ini_set('memory_limit', '1024M');
}else{
   // it's not
   set_time_limit(1200);
}

//! sts_pcm - API to PC*Miler using SOAP XML
// See http://pcmiler.alk.com/APIs/SOAP/v1.0/help/ for specs
// WSDL: http://pcmiler.alk.com/APIs/SOAP/v1.0/Service.svc?wsdl
class sts_pcmiler_api {

	private $debug = false;
	private $database;
	private $setting;
	private $api_key;
	private $have_credentials;
	private $pcm_name = 'PC*Miler';
	
	//! SCR# 683 - PC*Miler routing options
	private $pcm_routing;
	private $pcm_class_override;
	private $pcm_borders_open;
	private $pcm_highway_only;
	private $pcm_route_optimize;
	
	public $lat = 0;
	public $lon = 0;
	public $code = '';
	public $description = '';
	public $source = '';
	public $last_at = '';
	public $pcm_result;
	public $city;
	public $state;
	public $zip_code;

	private $profiling = false;
	private $timer;					// Timer for timing PC*Miler performance
	private $time_pcm;
	private $time_caching;
	private $time_processing;
	
	public function __construct( $database, $debug = false, $profiling = false ) {
		$this->debug = $debug;
		$this->database = $database;
		$this->setting = sts_setting::getInstance( $this->database, $this->debug );
		
		if( isset($_SESSION["PCM_MISSING"]) || ! extension_loaded('soap') ) {
			$_SESSION["PCM_MISSING"] = true;
			echo "<h2>Error: Missing Needed Extensions or Functions</h2>
				<ul>
				<p>There is a problem with the PHP configuration. Exspeedite cannot continue.</p>
				<p>Please contact Exspeedite support.</p>
				<p>PHP Configuration file is here: ".get_cfg_var('cfg_file_path') ."</p>
				<p>Issues</p>
				<ul>
				<p><strong>Need soap extension.</strong> This is used for communication with PC*Miler.</p>
				</ul>
				</ul>";
			die;
			
		}

		$this->api_key = $this->setting->get( 'api', 'PCM_API_KEY' );
		$this->have_credentials = ( $this->api_key <> false && $this->api_key <> '' );
		
		//! SCR# 683 - PC*Miler routing options - global settings
		$this->pcm_routing = $this->setting->get( 'api', 'PCM_ROUTING' );
		$this->pcm_class_override = $this->setting->get( 'api', 'PCM_CLASS_OVERRIDE' );
		$this->pcm_borders_open = $this->setting->get( 'api', 'PCM_BORDERS_OPEN' ) == 'true';
		$this->pcm_highway_only = $this->setting->get( 'api', 'PCM_HIGHWAY_ONLY' ) == 'true';
		$this->pcm_route_optimize = $this->setting->get( 'api', 'PCM_ROUTE_OPTIMIZE' );
		
		
		$this->profiling = $profiling || $this->debug;
		$this->timer = new sts_timer();
		if( $this->debug ) echo "<p>Create sts_pcm</p>";

	}
	
	// Allow re-use of objects - singleton function
	public static function getInstance( $database, $debug = false, $profiling = false ) {
		static $instance = null;
		$myclass = get_class ();
		if( $debug ) echo "<p>Get instance of $myclass</p>";
		if (null === $instance) {
			$instance = new $myclass( $database, $debug, $profiling );
		}
		return $instance;
    }
    
    // Look for adjacent zips for timezone - best guess
    private function get_timezone( $country, $zip ) {
	    $result = false;
	    
	    if( $country == 'Canada' ) {
	    	$check = $this->database->get_one_row("
	    		SELECT ROUND(avg(Time_Zone),0) as TZ
	    		FROM canada_post_codes
				where PostalCode like '".substr($zip, 0, 3)."%'");
	    	if( is_array($check) && isset($check['TZ']) &&
	    		$check['TZ'] > 0 )
	    		$result = intval($check['TZ']);
		} else {
	    	$check = $this->database->get_one_row("
	    		SELECT ROUND(avg(TimeZone),0) as TZ
	    		FROM zipcodes
	    		WHERE ZipCode like '".substr($zip, 0, 3)."%'");
	    	if( is_array($check) && isset($check['TZ']) &&
	    		$check['TZ'] > 0 )
	    		$result = intval($check['TZ']);
		}
		
		return $result;
    }
    
    //! If this entry exists, but not in our DB, add it to our DB
    private function update_zip( $output ) {
	   $zip = $found = false;
	    
	    if( $this->debug ) {
			echo "<p>".__METHOD__.": output</p><pre>";
			var_dump($output);
			echo "</pre>";
	    }
	    
	    // Preconditions
	    if( isset($output->Address) && isset($output->Address->StreetAddress) &&
		    ! empty($output->Address->StreetAddress->Zip) &&
	    	! empty($output->Address->StreetAddress->Country) &&
	    	! empty($output->Address->StreetAddress->City) &&
	    	! empty($output->Address->StreetAddress->State) ) {
	    
	    	$zip = (string) $output->Address->StreetAddress->Zip;
	    	$country = (string) $output->Address->StreetAddress->Country;
	    	$city = (string) $output->StreetAddress->Address->City;
	    	$state = (string) $output->StreetAddress->Address->State;
		} else
	    if( isset($output->Address) && ! empty($output->Address->Zip) &&
	    	! empty($output->Address->Country) && ! empty($output->Address->City) &&
	    	! empty($output->Address->State) ) {
		    	
	    	$zip = (string) $output->Address->Zip;
	    	$country = (string) $output->Address->Country;
	    	$city = (string) $output->Address->City;
	    	$state = (string) $output->Address->State;
	    }
	    
	    if( $zip != false ) {
		    if( $this->debug ) {
				echo "<p>".__METHOD__.": country, zip, state, city</p><pre>";
				var_dump($country, $zip, $state, $city);
				echo "</pre>";
		    }

	    	if( $country == 'Canada' ) {		// See if it already exists
		    	$check = $this->database->get_one_row("
		    		SELECT COUNT(*) AS NUM
		    		FROM canada_post_codes
		    		WHERE PostalCode = '".$zip."'");
		    	if( is_array($check) && isset($check['NUM']) &&
		    		$check['NUM'] > 0 )
		    		$found = true;
	    	} else {
		    	$check = $this->database->get_one_row("
		    		SELECT COUNT(*) AS NUM
		    		FROM zipcodes
		    		WHERE ZipCode = '".$zip."'
		    		AND PrimaryRecord = 'P'");
		    	if( is_array($check) && isset($check['NUM']) &&
		    		$check['NUM'] > 0 )
		    		$found = true;
	    	}
	    	
	    	if( ! $found ) {		// Not in DB, we can add it
		    	$timezone = $this->get_timezone( $country, $zip );
		    	
		    	if( $timezone > 0 ) {
				    if( $this->debug ) {
						echo "<p>".__METHOD__.": update DB</p><pre>";
						var_dump($country, $zip, $city, $state, $timezone);
						echo "</pre>";
				    }
	    
			    	if( $country == 'Canada' ) {
				    	$query = "INSERT INTO
				    			canada_post_codes (PostalCode, City, Province,
				    				Latitude, Longitude, CityMixedCase, Time_Zone, RecordType)
				    			VALUES ('".$zip."', '".strtoupper($city)."', '".$state."',
				    			".$this->lon.", ".$this->lat.", '".$city."', ".$timezone.", 'X')";
				    } else {
				    	$query = "INSERT INTO
				    			zipcodes (ZipCode, City, State,
				    				Latitude, Longitude, CityMixedCase, TimeZone, PrimaryRecord)
				    			VALUES ('".$zip."', '".strtoupper($city)."', '".$state."',
				    			".$this->lon.", ".$this->lat.", '".$city."', '".$timezone."', 'P')";
				    }
			    	$check = $this->database->get_one_row($query);
				    if( $this->debug ) {
						echo "<p>".__METHOD__.": after update DB</p><pre>";
						var_dump($query, $check, $this->database->error() );
						echo "</pre>";
				    }
		    	}
	    	}
	    
	    }
    }

	private function escape( $arg ) {
		return (isset($arg) ? $this->cache->real_escape_string($arg) : '');
	}
	
	//! use_pcm - return true if we have credentials and should use PC*Miler
	public function use_pcm() {
			
		return $this->have_credentials;
	}
	
	//! SCR# 683 - PC*Miler routing options - override settings
	public function set_routing_parameters( $params ) {
		if( $this->debug ) {
			echo "<pre>".__METHOD__.": params\n";
			var_dump($params);
			echo"</pre>";
		}

		if( is_array($params) && count($params) > 0 ) {
			foreach( $params as $key => $value ) {
				if( $this->debug ) {
					echo "<pre>".__METHOD__.": key, value\n";
					var_dump($key, $value);
					echo"</pre>";
				}
				switch( $key ) {
					case 'PCM_ROUTING':
						$this->pcm_routing = $value;
						break;
						
					case 'PCM_CLASS_OVERRIDE':
						$this->pcm_class_override = $value;
						break;
						
					case 'PCM_BORDERS_OPEN':
						$this->pcm_borders_open = $value;
						break;
						
					case 'PCM_HIGHWAY_ONLY':
						$this->pcm_highway_only = $value;
						break;
						
					case 'PCM_ROUTE_OPTIMIZE':
						$this->pcm_route_optimize = $value;
						break;
					
					default:	// all else ignored						
						break;
				}
			}
		}
	}
	
	public function get_source() {
		return (string) $this->source;
	}

	public function get_code() {
		return (string) $this->code;
	}

	public function get_lat() {
		return $this->lat;
	}

	public function get_lon() {
		return $this->lon;
	}

	public function get_description() {
		return $this->description;
	}

	public function get_last_at() {
		return $this->last_at;
	}

	public function get_time_pcm() {
		return $this->time_pcm;
	}

	//! SCR# 601 - Remove trailing/leading space and unprintable characters
	public function clean( $str ) {
		return preg_replace( '/[^[:print:]]/', '',trim((string) $str) );
	}
	
	//! create_address - convert an assoc array into a PCMWSStructAddress
	// NOTE: PC*Miler only has one StreetAddress attribute at present, we have two.
	// We try to ignore the one with a PO BOX.
	private function create_address( $address ) {
		if( isset($address) && is_array($address) ) {
			$_address1 = new PCMWSStructAddress();
			foreach( $address as $key => $value ) {
				switch( strtoupper($key) ) {
					case 'ADDRESS':
					case 'ADDRESS2':	if( strtoupper(substr($value, 0, 6)) <> 'PO BOX' &&
											strtoupper(substr($value, 0, 8)) <> 'P.O. BOX' &&
											strtoupper(substr($value, 0, 5)) <> 'SUITE' &&
											strtoupper(substr($value, 0, 5)) <> 'PLANT' &&
											strtoupper(substr($value, 0, 4)) <> 'BLDG' &&
											strtoupper(substr($value, 0, 8)) <> 'BUILDING' &&
											strtoupper(substr($value, 0, 9)) <> 'WAREHOUSE' &&
											strtoupper(substr($value, 0, 7)) <> 'SECTION' &&
											strtoupper(substr($value, 0, 6)) <> 'COOLER' &&
											strtoupper(substr($value, 0, 4)) <> 'UNIT' &&
											strtoupper(substr($value, 0, 3)) <> 'APT' &&
											strtoupper(substr($value, 0, 3)) <> 'STE' &&
											strtoupper(substr($value, 0, 4)) <> 'ATTN' &&
											strtoupper(substr($value, 0, 4)) <> 'ROOM' &&
											strtoupper(substr($value, 0, 4)) <> 'BACK' &&
											strtoupper(substr($value, 0, 4)) <> 'DOOR' &&
											strtoupper(substr($value, 0, 4)) <> 'WHSE' &&
											strtoupper(substr($value, 0, 3)) <> 'WHS' &&
											strtoupper(substr($value, 0, 2)) <> '**' &&
											strtoupper(substr($value, 0, 1)) <> '#' )
											$_address1->setStreetAddress( $this->clean($value) );	break;
					case 'CITY':		$_address1->setCity( $this->clean($value) );			break;
					case 'STATE':		$_address1->setState( $this->clean($value) );			break;
					case 'ZIP_CODE':	$_address1->setZip( $this->clean($value) );			break;
					case 'COUNTRY':		$_address1->setCountry( $value == 'USA' ? 'United States' : $this->clean($value) );		break;
					default: break;		// Skip over anything else
				}
			}
			return $_address1;
		}
		return false;
	}
	
	//! get_miles - find the distance between two addresses.
	// If either address has errors, return zero.
	public function get_miles( $address1, $address2 ) {
	
		if( $this->debug ) echo "<p>sts_pcm > get_miles</p>";
		$this->timer->start();
		$this->time_pcm = $this->time_caching = $this->time_processing = 0.0;
	
		$distance = -1;	// initialize
		$this->lat = $this->lon = 0;
		$this->code = $this->description = '';
		
		if( $this->debug ) echo "<p>sts_pcm > get_miles $this->api_key</p>";
	
		if( $this->have_credentials &&
			is_array($address1) && is_array($address2) ) {
			// SCR# 49 - Default country to USA
			if( empty($address1['COUNTRY'])) $address1['COUNTRY'] = 'USA';
			if( empty($address2['COUNTRY'])) $address2['COUNTRY'] = 'USA';

	
			require_once(dirname(__FILE__).DIRECTORY_SEPARATOR.'PCMWS/PCMWSAutoload.php');
	
			date_default_timezone_set(isset($sts_crm_timezone) ? $sts_crm_timezone : 'America/Chicago');
			
	
			$_dataVersion = "current";
			$_requestType = "GetReports";
	
			$_header = new PCMWSStructRequestHeader($_dataVersion, $_requestType);
	
			$getReports = new PCMWSStructGetReports();
	
			$reportReq = new PCMWSStructReportRequest();
	
			$reportReq->setHeader($_header);
			$reportReqBody = new PCMWSStructReportRequestBody();
	
			$_stopLocArray = new PCMWSStructArrayOfStopLocation();
	
			$stop[0] = new PCMWSStructStopLocation();
			$stop[1] = new PCMWSStructStopLocation();
	
			$_address1 = $this->create_address( $address1 );
			$_address2 = $this->create_address( $address2 );
			if( $this->debug ) {
				echo "<pre>";
				print_r($address1);
				print_r($address2);
				print_r($_address1);
				print_r($_address2);
				echo "</pre>";
			}
	
			$stop[0]->setAddress($_address1);
			$stop[0]->setRegion(PCMWSEnumDataRegion::VALUE_NA);
	
			$stop[1]->setAddress($_address2);
			$stop[1]->setRegion(PCMWSEnumDataRegion::VALUE_NA);
	
			$_stopLocArray->setStopLocation($stop);
	
			$reportroute[0] = new PCMWSStructReportRoute();
			$reportroute[0]->setRouteId("ks route");
			$reportroute[0]->setStops($_stopLocArray);
	
			$_reportingOptions = new PCMWSStructReportOptions();
			$_reportingOptions->setUseTollData(true);
			$_reportingOptions->setTollDiscount('All');
			$_reportingOptions->setUseCustomRoadSpeeds(FALSE);
	
			$reportroute[0]->setReportingOptions($_reportingOptions);
	
			$_options = new PCMWSStructRouteOptions();
			$_options->setBordersOpen($this->pcm_borders_open);
			$_options->setHighwayOnly($this->pcm_highway_only);
			$_options->setRoutingType($this->pcm_routing);
			$_options->setRouteOptimization($this->pcm_route_optimize);
			$_options->setVehicleType(PCMWSEnumVehicleType::VALUE_TRUCK);
			
			//! SCR# 683
			//PCMWSEnumClassOverrideType::VALUE_FIFTYTHREEFOOT
			//PCMWSEnumClassOverrideType::VALUE_NATIONALNETWORK
			
			//PCMWSEnumRoadType
			//PCMWSStructRoadSpeed->setRoadCategory
	
			$_options->setClassOverrides($this->pcm_class_override);
			
			$_truckCfg = new PCMWSStructTruckConfig();
			$_truckCfg->setAxles("8");
			$_truckCfg->setWeight("80000");
			$_truckCfg->setHeight('11\'5"');
			$_truckCfg->setWidth('96"');
			$_options->setTruckCfg($_truckCfg);
			
			$reportroute[0]->setOptions($_options);
		
			$soap_exception = false;
			try {
				$_mileageReport = new PCMWSStructMileageReportType();
			} catch (SoapFault $exception) {
				if( $this->debug ) echo "<p>sts_pcm > get_miles exception: ". $exception->getMessage()."</p>";
				$email = sts_email::getInstance($this->database, $this->debug);
				$email->send_alert("sts_pcm > get_miles exception<br>".
					"<pre>".$exception->getMessage()."</pre><br>".
					"addr1 = <pre>".print_r($_address1, true)."</pre><br>".
					"addr2 = <pre>".print_r($_address2, true)."</pre>" );
				$soap_exception = true;
			}
	
			if( ! $soap_exception ) {
				$_directionsReport = new PCMWSStructDirectionsReportType();
				$_directionsReport->setCondenseDirections(TRUE);
		
				$_reportType[0] = new PCMWSStructReportType();
		
				$_reportType[0] = $_mileageReport;
				$_reportType[1] = new PCMWSStructReportType();
				$_reportType[1] = $_directionsReport;
				$_reportType[1]->setCondenseDirections(TRUE);
				
				$_arrayReportType = new PCMWSStructArrayOfReportType($_reportType);
		
				$reportReq->setBody($reportReqBody);
		
				$getReports->setRequest($reportReq);
		
				$reportroute[0]->setReportTypes($_arrayReportType);
				$_arrayreportRoute = new PCMWSStructArrayOfReportRoute();
		
				$_arrayreportRoute->setReportRoute($reportroute);
		
				$reportReqBody->setReportRoutes($_arrayreportRoute);
		
				$getReports->setRequest($reportReq);
		
				$_response = '';
				$pcmServiceGet = new PCMWSServiceGet();
				$authHeader = new PCMWSStructAuthHeader($this->api_key,
					gmdate('D, d M Y H:i:s T', time()));
		
				$pcmServiceGet->setSoapHeaderAuthHeader($authHeader);
		
				$this->time_processing = $this->timer->split();
				if ($pcmServiceGet->GetReports($getReports)) {
				    $_response = $pcmServiceGet->getResult();
				}
				$this->time_pcm = $this->timer->split() - $this->time_processing;
		
				if (is_soap_fault($_response))
				    //print $_response->faultstring;
				    $distance = -1;
				else {
				    $ct = PCMWSServiceGet::getSoapClient();
			
				    $ct->__getLastRequest();
		
				    $request = $ct->__getLastRequest();
		
				    $encode = $ct->__getLastResponse();
				    
					$encode = preg_replace('/(>)(<)(\/*)/', "$1\n$2$3", $encode);
		
				    $clean_xml = str_ireplace(['s:', 'i:', 'SOAP:'], '', $encode);
				    //$xml = simplexml_load_string( $clean_xml, "SimpleXMLElement", 0, "http://schemas.xmlsoap.org/soap/envelope/" );
				    $xml = simplexml_load_string( $clean_xml );
				    $xml->registerXPathNamespace('s', 'http://schemas.xmlsoap.org/soap/envelope/');
				    //$my_response = $xml->xpath('/Envelope/Body/GetReportsResponse/GetReportsResult');
				    
				    if( isset($xml) && isset($xml->Body) && isset($xml->Body->GetReportsResponse) )
					    $GetReportsResult = $xml->Body->GetReportsResponse->GetReportsResult;
					    
				    if( isset($GetReportsResult) && isset($GetReportsResult->Body) && 
				    	isset($GetReportsResult->Body->Reports) && isset($GetReportsResult->Body->Reports->Report) )
				    	$ReportLines = $GetReportsResult->Body->Reports->Report->ReportLines;
				    else if( isset($GetReportsResult) && isset($GetReportsResult->Header) &&
				    	isset($GetReportsResult->Header->Errors) )
				    	$HeaderErrors = $GetReportsResult->Header->Errors;
				    
				    if( isset($ReportLines->StopReportLine) && isset($ReportLines->StopReportLine[0]) )
				    	$origin = $ReportLines->StopReportLine[0];
				    	
				    if( isset($ReportLines->StopReportLine) && isset($ReportLines->StopReportLine[1]) )
				    	$destination = $ReportLines->StopReportLine[1];
		
					if( $this->debug ) {
						echo "<pre>";
						//var_dump(htmlspecialchars($clean_xml));
						print_r($xml);
						//foreach( $my_response as $line ) {
						//print_r($ReportLines);
						//}
						echo "</pre>";
					}
					
					if( isset($HeaderErrors) && isset($HeaderErrors->Error) && 
						$HeaderErrors->Error->Type = 'Exception' ) {
						if( $this->debug ) echo "<p>sts_pcm > get_miles Exception detected ".$HeaderErrors->Error->Code."<br>".$HeaderErrors->Error->Description."</p>";
						$this->code = $HeaderErrors->Error->Code;
						$this->description = $HeaderErrors->Error->Description;
						$distance = -1;
					} else
					if( isset($origin->Stop->Errors) && isset($origin->Stop->Errors->Error) &&
						$origin->Stop->Errors->Error->Type <> 'Warning' ) {
						if( $this->debug ) echo "<p>sts_pcm > get_miles Origin Error detected ".$origin->Stop->Errors->Error->Code."<br>".$origin->Stop->Errors->Error->Description."</p>";
						$this->code = $origin->Stop->Errors->Error->Code;
						$this->description = $origin->Stop->Errors->Error->Description;
						$distance = -1;
					} else
					if( isset($destination->Stop->Errors) && isset($destination->Stop->Errors->Error) &&
						$destination->Stop->Errors->Error->Type <> 'Warning' ) {
						if( $this->debug ) echo "<p>sts_pcm > get_miles Destination Error detected ".$destination->Stop->Errors->Error->Code."<br>".$destination->Stop->Errors->Error->Description."</p>";
						$this->code = $destination->Stop->Errors->Error->Code;
						$this->description = $destination->Stop->Errors->Error->Description;
						$distance = -1;
					} else
						$distance = floatval((string)$destination->TMiles);
					if( $this->debug ) echo "<p>sts_pcm > get_miles ".$distance." miles</p>";

					if( $this->profiling ) {
						$this->time_processing = $this->timer->split();
					}

					$this->source = $this->pcm_name;
					$this->last_at=date("Y-m-d H:i:s");

					if( $this->profiling ) {
						$this->time_caching += $this->timer->split() - $this->time_processing;
					}
				} // exception
		    }
	    }
		$this->timer->stop();
		$this->time_processing = $this->timer->result();
		if( $this->debug ) echo "<p>sts_pcm > get_miles: pcm = ".
			number_format((float) $this->time_pcm,4)."s (".($this->time_processing > 0 ?
			number_format((float) $this->time_pcm/$this->time_processing*100,2) : '0')."%) cache = ".
			number_format((float) $this->time_caching,4)."s (".($this->time_processing > 0 ?
			number_format((float) $this->time_caching/$this->time_processing*100,2) : '0')."%) total = ".
			number_format((float) $this->time_processing,4)."s (100%)</p>";
	    return $distance;
	}
	
	//! validate_address - return true or false if the address is valid.
	public function validate_address( $address ) {
	
		if( $this->debug ) echo "<p>sts_pcm > validate_address</p>";
		$this->timer->start();
		$this->time_pcm = $this->time_caching = $this->time_processing = 0.0;
		
		$valid = 'error'; // initialize
		$this->source = $this->pcm_name;
		$this->lat = $this->lon = 0;
		$this->code = 'INC';
		$this->description = 'incomplete address';
		$this->city = $this->state = $this->zip_code = false;
		
		if( $this->debug ) echo "<p>sts_pcm > validate_address $this->api_key</p>";
	
		//! SCR# 84 - allow missing fields
		//&& isset($address['ADDRESS']) 
		if( $this->have_credentials ) {
			$this->time_processing = $this->timer->split();
			// SCR# 49 - Default country to USA
			//if( empty($address['COUNTRY'])) $address['COUNTRY'] = 'USA';
	
			require_once(dirname(__FILE__).DIRECTORY_SEPARATOR.'PCMWS/PCMWSAutoload.php');
	
			date_default_timezone_set('America/New_York');
	
			$rfc_1123_date = gmdate('D, d M Y H:i:s T', time());
	
			$_dataVersion = "current";
			$_requestType = "ProcessGeocode";
	
			$_header = new PCMWSStructRequestHeader($_dataVersion, $_requestType);
	
			$processGeocode = new PCMWSStructProcessGeocode();
	
			$geocodeReq = new PCMWSStructGeocodeRequest();
	
			$geocodeReq->setHeader($_header);
			$geocodeReqBody = new PCMWSStructGeocodeRequestBody();
	
			$_geocodeLocArray = new PCMWSStructArrayOfGeocodeLocation();
	
			$geocodeLoc[0] = new PCMWSStructGeocodeLocation();
	
			$_address = $this->create_address( $address );
			if( $this->debug ) {
				echo "<pre>";
				print_r($address);
				print_r($_address);
				echo "</pre>";
			}

	
			$geocodeLoc[0]->setAddress($_address);
			$geocodeLoc[0]->setRegion(PCMWSEnumDataRegion::VALUE_NA);
	
			$_geocodeLocArray->setGeocodeLocation($geocodeLoc);
			
			$geocodeReqBody->setLocations($_geocodeLocArray);
	
			$geocodeReq->setBody($geocodeReqBody);
	
			$processGeocode->setRequest($geocodeReq);
	
			$_response = '';
			
			//! SCR# 710 - Try to deal with "can't import schema from" issue
			$attempts = 5;	// Try this 5 times
			do{
				$attempts--;
				$soap_exception = false;

				try {
					$pcmServiceProcess = new PCMWSServiceProcess();
				} catch (SoapFault $exception) {
					if( $this->debug ) echo "<p>sts_pcm > validate_address exception: ". $exception->getMessage()."</p>";
					$email = sts_email::getInstance($this->database, $this->debug);
					$email->send_alert("sts_pcm > validate_address exception ($attempts)<br>".
						"<pre>".$exception->getMessage()."</pre><br>".
						"addr = <pre>".print_r($_address, true)."</pre>" );
					$soap_exception = true;
				}

				if($soap_exception) sleep( 20 ); // sleep 20 seconds
				
			} while( $soap_exception && $attempts > 0 );
			
			if( ! $soap_exception ) {
				$authHeader = new PCMWSStructAuthHeader($this->api_key,
					gmdate('D, d M Y H:i:s T', time()));
				$pcmServiceProcess->setSoapHeaderAuthHeader($authHeader);
		
				$this->time_processing = $this->timer->split();
				if ($pcmServiceProcess->ProcessGeocode($processGeocode)) {
				    $_response = $pcmServiceProcess->getResult();
				}
				$this->time_pcm = $this->timer->split() - $this->time_processing;
		
				if (is_soap_fault($_response))
				    //print $_response->faultstring;
				    $valid = false;
				else {
				    $ct = PCMWSServiceGet::getSoapClient();
		
		
				    $ct->__getLastRequest();
		
				    $request = $ct->__getLastRequest();
		
				    $encode = $ct->__getLastResponse();
				    
					$request = preg_replace('/(>)(<)(\/*)/', "$1\n$2$3", $request);
					$encode = preg_replace('/(>)(<)(\/*)/', "$1\n$2$3", $encode);
		
				    $clean_xml = str_ireplace(['s:', 'i:', 'SOAP:'], '', $encode);
				    //$xml = simplexml_load_string( $clean_xml, "SimpleXMLElement", 0, "http://schemas.xmlsoap.org/soap/envelope/" );
				    $xml = simplexml_load_string( $clean_xml );
				    if( $xml !== false ) {
					    $xml->registerXPathNamespace('s', 'http://schemas.xmlsoap.org/soap/envelope/');
			
					    $locations = $xml->Body->ProcessGeocodeResponse->ProcessGeocodeResult->Body->Locations;
					    $header = $xml->Body->ProcessGeocodeResponse->ProcessGeocodeResult->Header;
					    $this->pcm_result = $output = $locations->GeocodeOutputLocation;
			
						if( $this->debug ) {
							echo "<pre>";
							var_dump(htmlspecialchars($request));
							echo "</pre>";
							echo "<pre>XYZZY";
							//var_dump(htmlspecialchars($clean_xml));
							print_r($xml);
							//foreach( $my_response as $line ) {
							//print_r($reportlines);
							//}
							echo "</pre>";
						}
						if( isset($header->Errors) && isset($header->Errors->Error) &&
							$header->Errors->Error->Type <> 'Warning' ) {
							if( $this->debug ) echo "<p>sts_pcm > validate_address Error detected ".$header->Errors->Error->Code.
								"<br>".$header->Errors->Error->Description."</p>";
							$this->code = $header->Errors->Error->Code;
							$this->description = $header->Errors->Error->Description;
							$valid = 'error';
						} else
						if( isset($output->Errors) && isset($output->Errors->Error) &&
							$output->Errors->Error->Type <> 'Warning' ) {
							if( $this->debug ) echo "<p>sts_pcm > validate_address Error detected ".$output->Errors->Error->Code.
								"<br>".$output->Errors->Error->Description."</p>";
							$this->code = $output->Errors->Error->Code;
							$this->description = $output->Errors->Error->Description;
							$valid = 'error';
						} else
						if( isset($output->Coords) && isset($output->Coords->Lat) && isset($output->Coords->Lon) ) {
							if( $this->debug ) echo "<p>sts_pcm > validate_address lat ".floatval((string)$output->Coords->Lat).
								" lon ".floatval((string)$output->Coords->Lon)."</p>";
							$this->lat = floatval((string)$output->Coords->Lat);
							$this->lon = floatval((string)$output->Coords->Lon);
							if( isset($header->Type) && $header->Type == 'ProcessGeocode' &&
								isset($header->Success) && $header->Success == true ) {
								if( $this->debug ) echo "<p>sts_pcm > validate_address Success!</p>";
								$valid = 'valid';
								$this->code = '';
								$this->description = '';
								
								//! Check the city/state/zip matches
								if( isset($output->Address) && isset($output->Address->StreetAddress) &&
									isset($output->Address->StreetAddress->City) &&
									isset($output->Address->StreetAddress->State) &&
									isset($output->Address->StreetAddress->Zip) ) {
									$this->city = $output->Address->StreetAddress->City;
									$this->state = $output->Address->StreetAddress->State;
									$this->zip_code = $output->Address->StreetAddress->Zip;
								} else
								if( isset($output->Address) && isset($output->Address->City) &&
									isset($output->Address->State) &&
									isset($output->Address->Zip) ) {
									$this->city = $output->Address->City;
									$this->state = $output->Address->State;
									$this->zip_code = $output->Address->Zip;
								}
								
								if( $this->city != false && $this->state != false &&
									$this->zip_code != false &&
									($this->city != $address['CITY'] ||
									$this->state != $address['STATE'] ||
									$this->zip_code != $address['ZIP_CODE']) ) {
									$valid = 'error';
									$this->code = 42;
									$this->description = 'City/State/Zip don\'t match '.
										$this->city.'/'.$this->state.'/'.$this->zip_code;
									$this->source = $this->pcm_name;
									$this->last_at=date("Y-m-d H:i:s");
								} else {
									$this->update_zip( $output );
								}
								
							} else if( isset($output->Errors) && isset($output->Errors->Error) ) {
								$valid = 'warning';
								$this->code = $output->Errors->Error->Code;
								$this->description = $output->Errors->Error->Description;
							}
						}
					} else {
						//! SCR# 601 - Handle XML encoding error
						$this->code = 'XML_ERROR';
						$this->description = 'XML encoding error';
						$valid = 'error';
						
						$email = sts_email::getInstance($this->database, $this->debug);
						$email->send_alert(__METHOD__.": XML encoding error<br>".
							"Address: <pre>".print_r($address, true)."</pre><br>".
							"XML: <pre>".$clean_xml."</pre><br>" );

					}
				}
				$this->source = $this->pcm_name;
				$this->last_at=date("Y-m-d H:i:s");
									
			}
	    }
		$this->timer->stop();
		$this->time_processing = $this->timer->result();
		if( $this->debug ) echo "<p>sts_pcm > get_miles: pcm = ".
			number_format((float) $this->time_pcm,4)."s (".($this->time_processing > 0 ?
			number_format((float) $this->time_pcm/$this->time_processing*100,2) : '0')."%) cache = ".
			number_format((float) $this->time_caching,4)."s (".($this->time_processing > 0 ?
			number_format((float) $this->time_caching/$this->time_processing*100,2) : '0')."%) total = ".
			number_format((float) $this->time_processing,4)."s (100%)</p>";
		return $valid;
	}

	//! reverse_geocode - lookup address given coordinates
	public function reverse_geocode( $lat, $lon ) {
	
		if( $this->debug ) echo "<p>".__METHOD__.": entry, lat = $lat, lon = $lon</p>";
		$this->timer->start();
		$this->time_pcm = $this->time_caching = $this->time_processing = 0.0;
		
		$address = false;
		$this->source = $this->pcm_name;
		$this->last_at=date("Y-m-d H:i:s");
		$this->code = 'INC';
		$this->description = 'incomplete address';
		
		if( $this->debug ) echo "<p>".__METHOD__.": key = $this->api_key</p>";
	
		if( $this->have_credentials && isset($lat) && isset($lon)) {
			$this->time_processing = $this->timer->split();
			$this->time_caching = $this->timer->split() - $this->time_processing;
	
			require_once(dirname(__FILE__).DIRECTORY_SEPARATOR.'PCMWS/PCMWSAutoload.php');
	
			date_default_timezone_set('America/New_York');
	
			$rfc_1123_date = gmdate('D, d M Y H:i:s T', time());
	
			$_dataVersion = "current";
			$_requestType = "ProcessGeocode";
	
			$_header = new PCMWSStructRequestHeader($_dataVersion, $_requestType);
	
			$processReverseGeocode = new PCMWSStructProcessReverseGeocode();
	
			$reverseGeocodeReq = new PCMWSStructReverseGeocodeRequest();
	
			$reverseGeocodeReq->setHeader($_header);
			$reverseGeocodeReqBody = new PCMWSStructReverseGeocodeRequestBody();
	
			$_reverseGeoCoordArray = new PCMWSStructArrayOfReverseGeoCoord();
	
			$reverseGeoCoord[0] = new PCMWSStructReverseGeoCoord();
	
			$reverseGeoCoord[0]->setLat($lat);
			$reverseGeoCoord[0]->setLon($lon);
			$reverseGeoCoord[0]->setRegion(PCMWSEnumDataRegion::VALUE_NA);
	
			$_reverseGeoCoordArray->setReverseGeoCoord($reverseGeoCoord);
			
			$reverseGeocodeReqBody->setCoords($_reverseGeoCoordArray);
	
			$reverseGeocodeReq->setBody($reverseGeocodeReqBody);
	
			$processReverseGeocode->setRequest($reverseGeocodeReq);
	
			$_response = '';
			$soap_exception = false;
			try {
				$pcmServiceProcess = new PCMWSServiceProcess();
			} catch (SoapFault $exception) {
				if( $this->debug ) echo "<p>".__METHOD__.": exception: ". $exception->getMessage()."</p>";
				$email = sts_email::getInstance($this->database, $this->debug);
				$email->send_alert(__METHOD__.": exception<br>".
					"<pre>".$exception->getMessage()."</pre><br>".
					"addr = <pre>".print_r($_address, true)."</pre>" );
				$soap_exception = true;
			}
			
			if( ! $soap_exception ) {
				$authHeader = new PCMWSStructAuthHeader($this->api_key,
					gmdate('D, d M Y H:i:s T', time()));
				$pcmServiceProcess->setSoapHeaderAuthHeader($authHeader);
		
				$this->time_processing = $this->timer->split();
				if ($pcmServiceProcess->ProcessReverseGeocode($processReverseGeocode)) {
				    $_response = $pcmServiceProcess->getResult();
				}
				$this->time_pcm = $this->timer->split() - $this->time_processing;
		
				if (is_soap_fault($_response))
				    //print $_response->faultstring;
				    $valid = false;
				else {
				    $ct = PCMWSServiceGet::getSoapClient();
		
		
				    $ct->__getLastRequest();
		
				    $request = $ct->__getLastRequest();
		
				    $encode = $ct->__getLastResponse();
				    
					$request = preg_replace('/(>)(<)(\/*)/', "$1\n$2$3", $request);
					$encode = preg_replace('/(>)(<)(\/*)/', "$1\n$2$3", $encode);
		
				    $clean_xml = str_ireplace(['s:', 'i:', 'SOAP:'], '', $encode);
				    //$xml = simplexml_load_string( $clean_xml, "SimpleXMLElement", 0, "http://schemas.xmlsoap.org/soap/envelope/" );
				    $xml = simplexml_load_string( $clean_xml );
				    $xml->registerXPathNamespace('s', 'http://schemas.xmlsoap.org/soap/envelope/');
		
				    $locations = $xml->Body->ProcessReverseGeocodeResponse->ProcessReverseGeocodeResult->Body->Locations;
				    $header = $xml->Body->ProcessReverseGeocodeResponse->ProcessReverseGeocodeResult->Header;
				    $output = $locations->GeocodeOutputLocation;
		
					if( $this->debug ) {
						echo "<pre>";
						var_dump(htmlspecialchars($request));
						echo "</pre>";
						echo "<pre>XYZZY";
						//var_dump(htmlspecialchars($clean_xml));
						print_r($xml);
						//foreach( $my_response as $line ) {
						//print_r($reportlines);
						//}
						echo "</pre>";
					}
					if( isset($header->Errors) && isset($header->Errors->Error) &&
						$header->Errors->Error->Type <> 'Warning' ) {
						if( $this->debug ) echo "<p>".__METHOD__.": Error detected ".$output->Errors->Error->Code.
							"<br>".$header->Errors->Error->Description."</p>";
						$this->code = $header->Errors->Error->Code;
						$this->description = $header->Errors->Error->Description;
					} else
					if( isset($output->Errors) && isset($output->Errors->Error) &&
						$output->Errors->Error->Type <> 'Warning' ) {
						if( $this->debug ) echo "<p>".__METHOD__.": Error detected ".$output->Errors->Error->Code.
							"<br>".$output->Errors->Error->Description."</p>";
						$this->code = $output->Errors->Error->Code;
						$this->description = $output->Errors->Error->Description;
					} else
					if( isset($output->Address) ) {
						if( isset($output->Errors) && isset($output->Errors->Error) ) {
							$this->code = $output->Errors->Error->Code;
							$this->description = $output->Errors->Error->Description;
						} else {
							$address = array();
							$addr = $output->Address;
							if( ! empty((string) $addr->StreetAddress) )
								$address["ADDRESS"] = (string) $addr->StreetAddress;
							if( ! empty((string) $addr->City) )
								$address["CITY"] = (string) $addr->City;
							if( ! empty((string) $addr->State) )
								$address["STATE"] = (string) $addr->State;
							if( ! empty((string) $addr->Zip) )
								$address["ZIP_CODE"] = (string) $addr->Zip;

							if( ! empty((string) $addr->Country) ) {
								if((string) $addr->Country == 'United States')
									$address["COUNTRY"] = 'USA';
								else
									$address["COUNTRY"] = (string) $addr->Country;
							}
						}
					}
				}
				$this->source = $this->pcm_name;
				$this->last_at=date("Y-m-d H:i:s");
				
			}
	    }
		$this->timer->stop();
		$this->time_processing = $this->timer->result();
		return $address;
	}

	
	//! log_miles - generate ifta log entries for the load.
	// If either address has errors, return zero.
	public function log_miles( $load_code ) {
		global $sts_crm_dir;
		list($usec, $sec) = explode(" ", microtime());

	
		if( $this->debug ) echo "<p>sts_pcm > log_miles $load_code $usec</p>";
			
		if( $this->debug ) echo "<p>sts_pcm > log_miles $this->api_key</p>";
	
		if( $this->have_credentials ) {
		
			require_once( "include/sts_ifta_log_class.php" );
			$ifta = sts_ifta_log::getInstance( $this->database, $this->debug );
			
			//$ifta->log_event('log_miles: have_credentials: '.getmypid().' '.$usec );
			
			require_once(dirname(__FILE__).DIRECTORY_SEPARATOR.'PCMWS/PCMWSAutoload.php');
	
			date_default_timezone_set(isset($sts_crm_timezone) ? $sts_crm_timezone : 'America/Chicago');
		
			$_dataVersion = "current";
			$_requestType = "GetReports";
	
			$_header = new PCMWSStructRequestHeader($_dataVersion, $_requestType);
	
			$getReports = new PCMWSStructGetReports();
	
			$reportReq = new PCMWSStructReportRequest();
	
			$reportReq->setHeader($_header);
			$reportReqBody = new PCMWSStructReportRequestBody();
	
			$_stopLocArray = new PCMWSStructArrayOfStopLocation();
	
			//! Create stops
			
			$ex_stops = $this->database->get_multiple_rows("
			SELECT STOP_CODE, SEQUENCE_NO, STOP_TYPE,
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
					WHEN 'pick' THEN SHIPPER_COUNTRY
					WHEN 'drop' THEN CONS_COUNTRY
					ELSE STOP_COUNTRY END
				) AS COUNTRY

			FROM EXP_STOP
			LEFT JOIN EXP_SHIPMENT
			ON SHIPMENT = SHIPMENT_CODE
			WHERE EXP_STOP.LOAD_CODE = ".$load_code."
			ORDER BY SEQUENCE_NO ASC" );

			if( $this->debug ) {
				echo "<pre>";
				var_dump($ex_stops);
				echo "</pre>";
			}
			
			if( is_array($ex_stops) && count($ex_stops) > 0 ) {
				foreach($ex_stops as $ex_stop) {
					$stop_num = $ex_stop["SEQUENCE_NO"] - 1;
					$stop[$stop_num] = new PCMWSStructStopLocation();
					$_address = $this->create_address( $ex_stop );
					$stop[$stop_num]->setAddress($_address);
					$stop[$stop_num]->setRegion(PCMWSEnumDataRegion::VALUE_NA);
				}
			}
	
			$_stopLocArray->setStopLocation($stop);
	
			$reportroute[0] = new PCMWSStructReportRoute();
			$reportroute[0]->setRouteId("ks route");
			$reportroute[0]->setStops($_stopLocArray);
	
			$_reportingOptions = new PCMWSStructReportOptions();
			$_reportingOptions->setUseTollData(true);
			$_reportingOptions->setTollDiscount('All');
			$_reportingOptions->setUseCustomRoadSpeeds(FALSE);
	
			$reportroute[0]->setReportingOptions($_reportingOptions);
	
			$_options = new PCMWSStructRouteOptions();
			$_options->setBordersOpen(true);
			$_options->setHighwayOnly(true);
			$_options->setRoutingType(PCMWSEnumRoutingType::VALUE_PRACTICAL);
			$_options->setRouteOptimization(PCMWSEnumRouteOptimizeType::VALUE_NONE);
			$_options->setVehicleType(PCMWSEnumVehicleType::VALUE_TRUCK);
			$_truckCfg = new PCMWSStructTruckConfig();
			$_truckCfg->setAxles("8");
			$_truckCfg->setWeight("80000");
			$_truckCfg->setHeight('11\'5"');
			$_truckCfg->setWidth('96"');
			$_options->setTruckCfg($_truckCfg);
	
			$reportroute[0]->setOptions($_options);
	
			$soap_exception = false;
			try {
				$_mileageReport = new PCMWSStructMileageReportType();
			} catch (SoapFault $exception) {
				if( $this->debug ) echo "<p>sts_pcm > get_miles exception: ". $exception->getMessage()."</p>";
				$email = sts_email::getInstance($this->database, $this->debug);
				$email->send_alert("sts_pcm > get_miles exception<br>".
					"<pre>".$exception->getMessage()."</pre><br>".
					"addr1 = <pre>".print_r($_address1, true)."</pre><br>".
					"addr2 = <pre>".print_r($_address2, true)."</pre>" );
				$soap_exception = true;
			}
	
			if( ! $soap_exception ) {	//! Try here
				$_stateReport = new PCMWSStructStateReportType();
				$_reportType[0] = $_stateReport;
		
				$_arrayReportType = new PCMWSStructArrayOfReportType($_reportType);
		
				$reportReq->setBody($reportReqBody);
		
				$getReports->setRequest($reportReq);
		
				$reportroute[0]->setReportTypes($_arrayReportType);
				$_arrayreportRoute = new PCMWSStructArrayOfReportRoute();
		
				$_arrayreportRoute->setReportRoute($reportroute);
		
				$reportReqBody->setReportRoutes($_arrayreportRoute);
		
				$getReports->setRequest($reportReq);
		
				$_response = '';
				$pcmServiceGet = new PCMWSServiceGet();
				$authHeader = new PCMWSStructAuthHeader($this->api_key,
					gmdate('D, d M Y H:i:s T', time()));
		
				$pcmServiceGet->setSoapHeaderAuthHeader($authHeader);
		
				if ($pcmServiceGet->GetReports($getReports)) {
				    $_response = $pcmServiceGet->getResult();
				}
				
				if (is_soap_fault($_response))
				    //print $_response->faultstring;
				    $distance = -1;
				else {
				    $ct = PCMWSServiceGet::getSoapClient();
			
				    $ct->__getLastRequest();
		
				    $request = $ct->__getLastRequest();
		
				    $encode = $ct->__getLastResponse();
				    
					$encode = preg_replace('/(>)(<)(\/*)/', "$1\n$2$3", $encode);
		
				    $clean_xml = str_ireplace(['s:', 'i:', 'SOAP:'], '', $encode);
				    //$xml = simplexml_load_string( $clean_xml, "SimpleXMLElement", 0, "http://schemas.xmlsoap.org/soap/envelope/" );
				    $xml = simplexml_load_string( $clean_xml );
				    $xml->registerXPathNamespace('s', 'http://schemas.xmlsoap.org/soap/envelope/');
				    //$my_response = $xml->xpath('/Envelope/Body/GetReportsResponse/GetReportsResult');
				    
				    if( isset($xml) && isset($xml->Body) && isset($xml->Body->GetReportsResponse) )
					    $GetReportsResult = $xml->Body->GetReportsResponse->GetReportsResult;
					    
				    if( isset($GetReportsResult) && isset($GetReportsResult->Body) && 
				    	isset($GetReportsResult->Body->Reports) && isset($GetReportsResult->Body->Reports->Report) )
				    	$StateReportLines = $GetReportsResult->Body->Reports->Report->StateReportLines;
				    else if( isset($GetReportsResult) && isset($GetReportsResult->Header) &&
				    	isset($GetReportsResult->Header->Errors) )
				    	$HeaderErrors = $GetReportsResult->Header->Errors;
				    
		
					if( $this->debug ) {
						echo "<pre>";
						//var_dump(htmlspecialchars($clean_xml));
						//print_r($xml);
						print_r($StateReportLines->StateCostReportLine);
						//foreach( $my_response as $line ) {
						//print_r($ReportLines);
						//}
						echo "</pre>";
					}
					
					if( isset($HeaderErrors) && isset($HeaderErrors->Error) && 
						$HeaderErrors->Error->Type = 'Exception' ) {
						if( $this->debug ) echo "<p>sts_pcm > log_miles Exception detected ".$HeaderErrors->Error->Code."<br>".$HeaderErrors->Error->Description."</p>";
						print_r($pcmServiceGet->getLastError());
						$this->code = $HeaderErrors->Error->Code;
						$this->description = $HeaderErrors->Error->Description;
					} else {
						//! inject ifta log entries
						
						$check = $this->database->get_one_row("
							SELECT TRACTOR
							FROM EXP_LOAD
							WHERE LOAD_CODE = ".$load_code );
						
						if( isset($check["TRACTOR"]) && $check["TRACTOR"] > 0 ) {
							$tractor = $check["TRACTOR"];

							// Clear out duplicates
							$ifta->delete_row( "IFTA_TRACTOR = $tractor AND CD_ORIGIN = '".$load_code."'");
						
							//$ifta->log_event('log_miles: before loop: '.getmypid().' '.$usec );
							$c = 0;
							foreach($StateReportLines->StateCostReportLine as $state) {
								if( ! in_array($state->StCntry, array('US', 'Canada', 'TOTAL')) ) {
									//echo "<p>Tractor ".$tractor." State ".$state->StCntry." Total ".$state->Total."</p>";
									$fields = array(
										"IFTA_TRACTOR" => $tractor,
										"IFTA_JURISDICTION" => (string) $state->StCntry,
										"IFTA_DATE" => date("Y-m-d"),
										"DIST_TRAVELED" => floatval((string) $state->Total),
										"CD_ORIGIN" => (string) $load_code
									);
									$ifta->add( $fields );
									//$ifta->log_event('log_miles: '.getmypid().' '.$usec.' '.$c++.' '.(string) $state->StCntry.' '.$state->Total.' '.microtime() );
								}
							} // foreach
							//$ifta->log_event('log_miles: after loop: '.getmypid().' '.$usec );
						} // if
					} // else
				} // else
			} // exception
	    }
	}

}

?>