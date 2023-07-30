<?php

// $Id: sts_zip_class.php 5121 2023-06-02 16:37:56Z dev $
// Zip class - All things zip codes and distance calculations

// no direct access
defined('_STS_INCLUDE') or die('Restricted access');

require_once( "sts_table_class.php" );
require_once( "sts_setting_class.php" );
require_once( "sts_timer_class.php" );

require_once( __DIR__."/../PCMILER/exp_get_miles.php" );
require_once( "sts_email_class.php" );

$sts_conv_km_miles = 0.000621371;

class sts_zip extends sts_table {

	private $setting;
	private $google_api_key;
	private $google_enabled = false;
	private $source = 'none';
	private $last_at = 'NULL';
	private $canada_postcodes;
	
	public $lat = 0;
	public $lon = 0;
	public $code = '';
	public $description = '';
	public $zip_code;
	private $google_result;

	private $timer;
	private $time_pcm;
	private $time_caching;
	private $time_processing;
	
	// Constructor does not need the table name
	public function __construct( $database, $debug = false ) {

		$this->debug = $debug;
		if( $this->debug ) echo "<p>Create sts_zip</p>";
		parent::__construct( $database, ZIP_TABLE, $debug);
		$this->setting = sts_setting::getInstance( $database, $debug );
		$this->canada_postcodes = sts_canada_postcode::getInstance( $database, $debug );
		$this->timer = new sts_timer();
		$this->google_api_key = $this->setting->get( 'api', 'GOOGLE_API_KEY' );
		$this->google_enabled = ! empty( $this->google_api_key );

		/* ! SCR# 623 breaks this
		// Check the number of rows in the table
		// Update this if it ever changes...
		$expected_number_of_rows = 111236;
		
		if( isset($_SESSION["ZIPTABLE_ROWS"])) {
			$number_of_rows = $_SESSION["ZIPTABLE_ROWS"];
		} else {
			$result = $this->fetch_rows( "", "count(*) as x" );
			$number_of_rows = 0;
			if( is_array($result) && count($result) == 1 && isset($result[0]["x"]) ) {
				$_SESSION["ZIPTABLE_ROWS"] = $number_of_rows = intval($result[0]["x"]);
			}
		}
		
		if( $number_of_rows <> $expected_number_of_rows) {
			$email = sts_email::getInstance($this->database, $this->debug);
			$email->send_alert("sts_zip: Something wrong with Zip Code Table<br>".
				"Please check the ".ZIP_TABLE." table<br>".
				"Expected $expected_number_of_rows rows, found $number_of_rows<br>", EXT_ERROR_ERROR );
		}
		*/
		
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

/*=================================================================================
/* Distance Calculator
/*'=================================================================================
/*' This function calculates the distance between two latitude/logitude
/*' coordinates. Disance can be returned as Nautical Miles, Kilometers,
/*' or Miles (default).
/*'
/*' This function was designed for PHP
/*'
/*' Accepts:
/*'	Lat1 = Latitude of point one (decimal, required)
/*'	Lon1 = Longitude of point one (decimal, required)
/*'	Lat2 = Latitude of point two (decimal, required)
/*'	Lon2 = Longitude of point two (decimal, required)
/*'	Unit = Non required character K, N, or M
/*'		where:
/*'			K = Kilometers
/*'			N = Nautical Miles
/*'			M = Miles [default]
/*'
/*' Provided by: http://www.zip-codes.com
/*'
/*' � 2005 Zip-Codes.com, All Rights Reserved
/*'=================================================================================*/

	public function DistanceCalc($lat1, $lon1, $lat2, $lon2, $unit) { 
	
	  $theta = $lon1 - $lon2; 
	  $dist = sin(deg2rad($lat1)) * sin(deg2rad($lat2)) +  cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * cos(deg2rad($theta)); 
	  $dist = acos($dist); 
	  $dist = rad2deg($dist); 
	  $miles = $dist * 60 * 1.1515;
	  $unit = strtoupper($unit);
	
	  if ($unit == "K") {
	    return ($miles * 1.609344); 
	  } else if ($unit == "N") {
	      return ($miles * 0.8684);
	    } else {
	        return $miles;
	      }
	}

/*'=================================================================================*/
/*' Samples
/*'=================================================================================
echo "<h1>Distance Calculator Sample</h1>";
echo DistanceCalc(30.524, -87.235, 34.097, -118.412, "M") . " Miles<br>";
echo DistanceCalc(30.524, -87.235, 34.097, -118.412, "K") . " Kilometers<br>";
echo DistanceCalc(30.524, -87.235, 34.097, -118.412, "N") . " Nautical Miles<br>";
*/

	//! Convert an address array to a string
	public function address_tostring( $address, $glue = ', ' ) {
		$output = '';
		if( is_array($address) ) {
			$elements = array();
			if( isset($address['ADDRESS']))
				$elements[] = $address['ADDRESS'];
			if( isset($address['ADDRESS2']))
				$elements[] = $address['ADDRESS2'];
			if( isset($address['CITY']))
				$elements[] = $address['CITY'];
			if( isset($address['STATE']))
				$elements[] = $address['STATE'];
			if( isset($address['ZIP_CODE']))
				$elements[] = $address['ZIP_CODE'];
			if( isset($address['COUNTRY']))
				$elements[] = $address['COUNTRY'];
			if( count($elements) > 0 )
				$output = implode($glue, $elements);
		}
		
		return $output;		
	}



	// Uses Google API
	// https://developers.google.com/maps/documentation/distancematrix
	// Note we are in violation as there is no map.
	private function get_distance_google( $zip1, $zip2 ) {
	
		global $sts_conv_km_miles;
		
		$distance = -1;
		
		if( $this->debug ) echo "<p>get_distance_google $zip1, $zip2</p>";

		if( $this->google_enabled ) {
			$orig = $zip1;
			$dest = $zip2;
			if( ! strpos($orig, ',') ) $orig .= ',USA';
			if( ! strpos($dest, ',') ) $dest .= ',USA';
			
			$url = 'https://maps.googleapis.com/maps/api/distancematrix/json?key='.
				$this->google_api_key.'&origins='.urlencode($orig).
				'&destinations='.urlencode($dest);
			if( $this->debug ) echo "<p>get_distance_google url = $url</p>";

			$google_exception = false;
			try {
				$data = file_get_contents( $url );
			} catch (Exception $exception) {
				if( $this->debug ) echo "<p>sts_pcm > get_miles exception: ". $exception->getMessage()."</p>";
				$email = sts_email::getInstance($this->database, $this->debug);
				$email->send_alert("sts_zip > get_distance_google exception<br>".
					"<pre>".$exception->getMessage()."</pre><br>".
					"addr1 = <pre>".print_r($zip1, true)."</pre><br>".
					"addr2 = <pre>".print_r($zip2, true)."</pre>", EXT_ERROR_ERROR );
				$google_exception = true;
			}

			if( ! $google_exception ) {
				$result = json_decode($data, true);
				if( $this->debug ) {
					echo "<p>get_distance_google result = </p>
					<pre>";
					var_dump($result);
					echo "</pre>";
				}
				
				if( is_array($result) && isset($result["error_message"]) &&
					$result["error_message"] <> '' ) {
					$email = sts_email::getInstance($this->database, $this->debug);
					$email->send_alert("sts_zip > get_distance_google error<br>".
						"<pre>".$result["error_message"]."</pre><br>".
						"url = <pre>".print_r($url, true)."</pre><br>".
						"result = <pre>".print_r($result, true)."</pre>", EXT_ERROR_ERROR );
				}
	
				if( is_array($result) && is_array($result['rows'])  && isset($result['rows'][0]) &&
					is_array($result['rows'][0]) &&
					is_array($result['rows'][0]['elements']) && is_array($result['rows'][0]['elements'][0])) {
					if( $this->debug ) echo "<p>get_distance_google elem</p>";
					$elem = $result['rows'][0]['elements'][0];

					if( isset($elem['distance']) &&
						is_array($elem['distance']) &&
						isset($elem['distance']['value']) ) {
						if( $this->debug ) echo "<p>get_distance_google calc</p>";
						$distance = floatval($elem['distance']['value']) * $sts_conv_km_miles;
						$this->source='Google';
						$this->last_at=date("Y-m-d H:i:s");
					} else {
						$distance = -1;
					}
				}
			}
		}
		if( $this->debug ) echo "<p>get_distance_google distance = ".number_format((float) $distance, 2)." MI, source = ".$this->source." last_at = ".$this->last_at."</p>";
		return $distance;
	}
	
	private function escape( $arg ) {
		return (isset($arg) ? $this->real_escape_string($arg) : '');
	}
	
	// Uses Google API
	// https://developers.google.com/maps/documentation/distancematrix
	// Note we are in violation as there is no map.
	public function validate_google( $address ) {
		$valid = 'error'; // initialize
		$this->lat = $this->lon = 0;
		$this->code = '';
		$this->description = 'Missing Google API Key';
		$this->source = 'none';
		if( $this->debug ) echo "<p>".__METHOD__.": entry, address = ".$this->address_tostring( $address )."</p>";
		$this->timer->start();
		$this->time_pcm = $this->time_caching = $this->time_processing = 0.0;

		$pcm = new sts_table( $this->database, PCM_CACHE, $this->debug );

		if( $this->google_enabled ) {
			$this->time_processing = $this->timer->split();
			// SCR# 49 - Default country to USA
			//if( empty($address['COUNTRY'])) $address['COUNTRY'] = 'USA';
			
			$url = 'https://maps.googleapis.com/maps/api/geocode/json?address='.
				urlencode($this->address_tostring( $address )).'&key='.$this->google_api_key;
			if( $this->debug ) echo "<p>".__METHOD__.": url = $url</p>";

			$google_exception = false;
			$this->description = 'Google exception';
			try {
				$data = file_get_contents( $url );
			} catch (Exception $exception) {
				if( $this->debug ) echo "<p>".__METHOD__.": ". $exception->getMessage()."</p>";
				$email = sts_email::getInstance($this->database, $this->debug);
				$email->send_alert(__METHOD__.": exception<br>".
					"<pre>".$exception->getMessage()."</pre><br>".
					"addr = <pre>".print_r($address, true)."</pre>", EXT_ERROR_ERROR );
				$google_exception = true;
			}

			if( ! $google_exception ) {
				$this->google_result = json_decode($data, true);
				$this->time_pcm = $this->timer->split() - $this->time_processing;
				if( $this->debug ) {
					echo "<p>".__METHOD__.": result = </p>
					<pre>";
					var_dump($this->google_result);
					echo "</pre>";
				}
				if( isset($this->google_result) && isset($this->google_result["status"]) && $this->google_result["status"] == "OK") {
					$valid = 'valid';
					$this->code = '';
					$this->description = '';
					if( isset($this->google_result["results"]) &&
						isset($this->google_result["results"][0]) &&
						isset($this->google_result["results"][0]["geometry"]) &&
						isset($this->google_result["results"][0]["geometry"]["location"]) ) {
						$location = $this->google_result["results"][0]["geometry"]["location"];
						$this->lat = isset($location["lat"]) ? floatval($location["lat"]) : 0;
						$this->lon = isset($location["lng"]) ? floatval($location["lng"]) : 0;
					}
				} else {
					$valid = 'error';
					$this->code = isset($this->google_result) && isset($this->google_result["status"]) ?
						$this->google_result["status"] : '';
					$this->description = isset($this->google_result) && isset($this->google_result["error_message"]) ?
						$pcm->trim_to_fit('ADDR_DESCR', $this->google_result["error_message"]) :
						'Address not valid';
				}
			}
			$this->source = 'Google';
			$this->last_at=date("Y-m-d H:i:s");
							
		}
		$this->timer->stop();
		$this->time_processing = $this->timer->result();
		if( $this->debug ) echo "<p>".__METHOD__.": pcm = ".
			number_format((float) $this->time_pcm,4)."s (".($this->time_processing > 0 ?
			number_format((float) $this->time_pcm/$this->time_processing*100,2) : '0')."%) cache = ".
			number_format((float) $this->time_caching,4)."s (".($this->time_processing > 0 ?
			number_format((float) $this->time_caching/$this->time_processing*100,2) : '0')."%) total = ".
			number_format((float) $this->time_processing,4)."s (100%)</p>";
		return $valid;
	}


	// Return distance in miles between two zip codes
	public function get_distance( $zip1, $zip2 ) {
	
		if( $this->debug ) echo "<p>get_distance $zip1, $zip2</p>";
		
		$distance = -1;
			/*
		if( $zip1 == $zip2) {
			$distance = 0;
		} else {
			$this->google_api_key = $this->setting->get( 'api', 'GOOGLE_API_KEY' );
			if( isset($this->google_api_key) && $this->google_api_key <> false & $this->google_api_key <> '' )
				$distance = $this->get_distance_google( $zip1, $zip2 );
			*/
			if( $distance == -1 ) {
				$result = $this->fetch_rows( "ZipCode in('".$zip1."', '".$zip2."') 
					AND PrimaryRecord = 'P'", "ZipCode, Latitude, Longitude" );
				if( $result ) {
					foreach( $result as $row ) {
						if( $row['ZipCode'] == $zip1 ) {
							$lat1 = $row['Latitude'];
							$lon1 = $row['Longitude'];
						} else if( $row['ZipCode'] == $zip2 ) {
							$lat2 = $row['Latitude'];
							$lon2 = $row['Longitude'];
						}
					}
					if( isset($lat1) && isset($lon1) && isset($lat2) && isset($lon2) ) {
						$distance = $this->DistanceCalc($lat1, $lon1, $lat2, $lon2, 'M');
						$this->source='Zipcode';
						$this->last_at=date("Y-m-d H:i:s");
					}
				}
			}
		//}
		
		if( $this->debug ) echo "<p>get_distance distance = ".number_format((float) $distance, 2)." MI, source = ".$this->source." last_at = ".$this->last_at."</p>";
		return $distance;
	}
	
	// Return distance in miles between two addresses
	public function get_distance_full( $addr1, $addr2 ) {
	
		if( $this->debug ) echo "<p>get_distance_full $addr1, $addr2</p>";
		
		$distance = -1;
		$this->google_api_key = $this->setting->get( 'api', 'GOOGLE_API_KEY' );
		if( isset($this->google_api_key) && $this->google_api_key <> false & $this->google_api_key <> '' )
			$distance = $this->get_distance_google( $addr1, $addr2 );
			
		if( $this->debug ) echo "<p>get_distance_full distance = ".number_format((float) $distance, 2)." MI, source = ".$this->source." last_at = ".$this->last_at."</p>";
		return $distance;
	}

	//! Try various methods to validate addresses.
	//
	public function validate_various( $address ) {
	
		if( $this->debug ) echo "<p>".__METHOD__.": entry</p>";
		$valid = 'none'; // initialize
		$this->lat = $this->lon = 0;
		$this->code = '';
		$this->description = 'Unknown';
		$this->source = 'none';

		if( is_array($address) ) {
			$cache = sts_validate_cache::getInstance( $this->database, $this->debug );
			
			//! Check the cache first
			if( $valid == 'none' ) {
				$valid = $cache->get_cache($address);
				if( $valid != 'none' ) {
					$this->source=$cache->get_source();
					$this->last_at=$cache->get_last_at();
					$this->code = $cache->get_code();
					$this->description = $cache->get_description();
					$this->lat = $cache->get_lat();
					$this->lon = $cache->get_lon();
				}
			}			

			$pcm = sts_pcmiler_api::getInstance( $this->database, $this->debug );
			
			//! Use PC*Miler
			if( $valid == 'none' && $pcm->use_pcm() ) {
				$valid = $pcm->validate_address( $address );
				if( $valid != 'none' ) {
					$this->source=$pcm->get_source();
					$this->last_at=$pcm->get_last_at();
					$this->code = $pcm->get_code();
					$this->description = $pcm->get_description();
					$this->lat = $pcm->get_lat();
					$this->lon = $pcm->get_lon();
					$this->zip_code = $pcm->zip_code;
					$cache->put_cache( $address, $valid, $pcm );
				}
			}

			//! Use Google API
			else if( $valid == 'none' && $this->google_enabled ) {
				$valid = $this->validate_google( $address );
				if( $valid != 'none' ) {
					$cache->put_cache( $address, $valid, $this );
				}
			}
		}
		
		return $valid;
	}
	
	
	//! Try various methods to get the distance between two addresses.
	//
	public function get_distance_various( $address1, $address2, $use_cache = true ) {
		
		$miles = -1;
		$this->source = 'none';
		$this->last_at = 'NULL';		
		
		if( isset($address1) && isset($address2) ) {

			if( $this->debug ) echo "<p>get_distance_various(".
			$this->address_tostring( $address1 )." / ".$this->address_tostring( $address2 ).")</p>";
		
			$cache = sts_distance_cache::getInstance( $this->database, $this->debug );
			
			//! Check the cache first
			if( $miles == -1 && $use_cache ) {
				$miles = intval( $cache->get_cache($address1, $address2) );
				if( $miles >= 0 ) {
					$this->source=$cache->get_source();
					$this->last_at=$cache->get_last_at();
				}
			}			
			
			$pcm = sts_pcmiler_api::getInstance( $this->database, $this->debug );
			
			//! Use PC*Miler
			if( $miles == -1 && $pcm->use_pcm() ) {
				$miles = intval( $pcm->get_miles($address1, $address2) );
				if( $miles >= 0 ) {
					$this->source=$pcm->get_source();
					$this->last_at=$pcm->get_last_at();
					$cache->put_cache( $address1, $address2, $miles, $pcm );
				}
			}
			
			//! Use Google API
			else if( $miles == -1 ) {
				$addr1 = $this->address_tostring( $address1, ',' );
				$addr2 = $this->address_tostring( $address2, ',' );
				$miles = intval( $this->get_distance_full( $addr1, $addr2 ) );
		if( $this->debug ) echo "<p>get_distance_various, after get_distance_full, distance = ".number_format((float) $miles, 2)." MI, source = ".$this->source." last_at = ".$this->last_at."</p>";

				if( $miles >= 0 ) {
					$cache->put_cache( $address1, $address2, $miles, $this );
				}
			}
		
			if( $miles == -1 && isset($address1['ZIP_CODE']) && isset($address2['ZIP_CODE']) )
				$miles = intval( $this->get_distance( $address1['ZIP_CODE'], $address1['ZIP_CODE'] ) );
			
		}
		if( $this->debug ) echo "<p>get_distance_various distance = ".number_format((float) $miles, 2)." MI, source = ".$this->source." last_at = ".$this->last_at."</p>";
		return $miles;
	}
	
	//! SCR# 601 - lookup info from ZIP code
	public function zip_lookup( $zip ) {
		if( $this->canada_postcodes->enabled() ) {
			$result = $this->database->get_multiple_rows("
				select 'USA' AS Country, State, CityMixedCase AS City
				from ZIPCodes 
				where ZipCode = '".$zip."'
				AND PrimaryRecord = 'P'
		        union all
		        (select 'Canada' AS Country, Province AS State, CityMixedCase AS City
		        from canada_post_codes
		        where PostalCode = '".$zip."')
				limit 1" );
		} else {
			//! SCR# 233 - include lat & lon
			$result = $this->fetch_rows( "ZipCode = '".$zip."'
				AND PrimaryRecord = 'P'",
				"'USA' AS Country, State, CityMixedCase", "", "1" );
		}
		return $result;
	}
	
	//! SCR# 623 - lookup a missing ZIP code and add it to the database
	public function lookup_missing_zip( $zip ) {
		if( $this->debug ) echo "<p>".__METHOD__.": zip = $zip</p>";
		$ac = false;
		$result = false;
		$check = 'none';
		$this->source = 'none';
		
		if( ! empty($zip) ) {
			$addr = array( 'ZIP_CODE' => $zip );
			
			$pcm = sts_pcmiler_api::getInstance( $this->database, $this->debug );
			
			//! Use PC*Miler
			if( $pcm->use_pcm() ) {
				$check = $pcm->validate_address( $addr );
				$this->source=$pcm->get_source();

				if( $check == 'valid' && isset($pcm->pcm_result) ) {
					if( isset($pcm->pcm_result->Address)) {
						if( isset($pcm->pcm_result->Address->CountryAbbreviation))
							$country = (string) $pcm->pcm_result->Address->CountryAbbreviation;
						if( isset($pcm->pcm_result->Address->State))
							$state = (string) $pcm->pcm_result->Address->State;
						if( isset($pcm->pcm_result->Address->City))
							$city = (string) $pcm->pcm_result->Address->City;
						if( isset($pcm->pcm_result->Address->Zip))
							$zip_code = (string) $pcm->pcm_result->Address->Zip;
					}
					$this->lat = $pcm->lat;
					$this->lon = $pcm->lon;					
				} else {
					$this->description = $this->source.' did not find it';
				}
			} else 
			//! Use Google API
			if( $this->google_enabled ) {
				$check = $this->validate_google( $addr );
				if( $check == 'valid' && isset($this->google_result) ) {
					if( isset($this->google_result["results"]) &&
						isset($this->google_result["results"][0]) &&
						isset($this->google_result["results"][0]["address_components"]) ) {
						$ac = $this->google_result["results"][0]["address_components"];
					}
					//echo "<pre>ac:\n";
					//var_dump($ac);
					//echo "</pre>";
					$this->source = 'Google';
					$country = $state = $city = $zip_code = '';
					foreach( $ac as $c ) {
						if( is_array($c) && is_array($c["types"]) && isset($c["types"][0])) {
							switch( $c["types"][0] ) {
								case "country":
									$country = $c["short_name"];
									break;
			
								case "administrative_area_level_1":
									$state = $c["short_name"];
									break;
			
								case "locality":
									$city = $c["long_name"];
									break;
			
								case "postal_code":
									$zip_code = $c["long_name"];
									break;
									
								default:
									break;
							}
						}
					}
				} else {
					$this->description = $this->source.' did not find it';
				}
			}

			if( $this->debug ) {
				echo "<pre>".__METHOD__.": addr:\n";
				var_dump($country, $state, $city, $zip_code, $this->lat, $this->lon);
				echo "</pre>";
			}

			// Do we have enough data to store?
			if( $check == 'valid' && ! empty($country) &&
				! empty($state) && ! empty($city) && ! empty($zip_code) &&
				! empty($this->lat) && ! empty($this->lon)) {
					
				if( $country == 'US' ) {
					// Make sure it is not already there
					$check = $this->database->get_multiple_rows("select *
						from ZIPCodes where ZipCode = '".$zip_code."'");
					if( is_array($check) && count($check) == 0 ) {
						$result = $this->database->get_one_row("
							INSERT INTO ZIPCodes(PrimaryRecord, ZipCode, City,
								CityMixedCase, State, Latitude, Longitude, FinanceNumber )
							VALUES('P', '".$zip_code."', '".strtoupper($city)."',
								'".$city."', '".$state."', ".$this->lat.", ".$this->lon.", 999 )");
						$this->description = 'Added '.$zip_code.', '.$city.', '.$state;
					} else {
						$this->description = $zip_code.' was already found in database';
					}
				} else if( $country == 'CA' ) {
					// Make sure it is not already there
					$check = $this->database->get_multiple_rows("select *
						from canada_post_codes where PostalCode = '".$zip_code."'");
					if( is_array($check) && count($check) == 0 ) {
						$result = $this->database->get_one_row("
							INSERT INTO canada_post_codes(RecordType, PostalCode, City,
								CityMixedCase, Province, Longitude, Latitude )
							VALUES('9', '".$zip_code."', '".strtoupper($city)."',
								'".$city."', '".$state."', ".$this->lat.", ".$this->lon." )");
						$this->description = 'Added '.$zip_code.', '.$city.', '.$state;
					} else {
						$this->description = $zip_code.' was already found in database';
					}
				}
			}
				
		}
		
		return $result;
	}
	
	public function suggest( $query ) {

		if( $this->debug ) echo "<p>suggest $query</p>";
		if( $this->canada_postcodes->enabled() && ctype_alpha($query[0])) {
			$result = $this->database->get_multiple_rows("
				select distinct PostalCode AS ZipCode, GET_TIMEZONE(PostalCode) AS TZONE,
		        CityMixedCase, Province AS State,
				'Canada' AS Country, Longitude as Latitude, Latitude as Longitude
		        from canada_post_codes
		        where PostalCode like '".$query."%'
					OR City like '".$query."%'
					OR CityProvince like '".$query."%'
					
				limit 0, 20" );
		} else {
			//Check for 5digit-4digit
			$query2 = $query;
			if( strpos($query, '-') !== false ) {
				$query2 = substr($query, 0, strpos($query, '-'));
				if( $this->debug ) echo "<p>suggest (updated) $query2</p>";
			}
			
			//! SCR# 233 - include lat & lon
			$result = $this->fetch_rows( "(ZipCode like '".$query2."%'
				OR City like '".$query2."%'
				OR CityState like '".$query2."%')
				AND PrimaryRecord = 'P'",
				"ZipCode, GET_TIMEZONE(ZipCode) AS TZONE, CityMixedCase, State, 'USA' AS Country, Latitude, Longitude", "", "0, 20" );
				
			// Substitute the expanded zip in the result
			if( strpos($query, '-') !== false && is_array($result) && count($result) > 0 ) {
				for( $c = 0; $c < count($result); $c++ ) {
					if( $result[$c]["ZipCode"] == $query2 && strlen($query) == 10 )
						$result[$c]["ZipCode"] = $query;
				}
			}
			
		}
		
		if( $this->debug ) {
			echo "<pre>suggest - result\n";
			var_dump($result);
			echo "</pre>";
		}
		
		return $result;
	}

	public function suggest_prefix( $query ) {

		if( $this->debug ) echo "<p>suggest_prefix $query</p>";
		if( $this->canada_postcodes->enabled() ) {
			$result = $this->database->get_multiple_rows("
				select distinct substring(ZipCode,1,3) Prefix, CityMixedCase, State,
				'USA' AS Country
				from ZIPCodes 
				where substring(ZipCode,1,3) like '".$query."%'
					AND PrimaryRecord = 'P'
		        union all
		        (select substring(PostalCode,1,3) Prefix, CityMixedCase, Province AS State,
				'Canada' AS Country
		        from canada_post_codes
		        where substring(PostalCode,1,3) like '".$query."%')
				limit 0, 20" );
		} else {
			$result = $this->fetch_rows( "substring(ZipCode,1,3) like '".$query."%'
				AND PrimaryRecord = 'P'", "distinct substring(ZipCode,1,3) Prefix, CityMixedCase, State, 'USA' AS Country", "", "0, 20", "substring(ZipCode,1,3)" );
		}
		return $result;
	}
	
	public function get_source() {
		return $this->source;
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
		return (string) $this->description;
	}

	public function get_last_at() {
		return $this->last_at;
	}

	public function get_time_pcm() {
		return $this->time_pcm;
	}

}

//! sts_validate_cache - caching of validation of address.
// Works with Google, PC*Miler
class sts_validate_cache extends sts_table {

	private $profiling = false;
	private $timer;					// Timer for timing PC*Miler performance
	private $time_pcm;
	private $time_caching;
	private $time_processing;
	private $source = '';
	private $last_at = '';
	private $setting;
	private $cache_duration;

	public function __construct( $database, $debug = false, $profiling = false ) {
		$this->debug = $debug;
		parent::__construct( $database, PCM_CACHE, $debug);

		$this->setting = sts_setting::getInstance( $database, $debug );
		$this->cache_duration = $this->setting->get( 'api', 'GEO_CACHE_DURATION' );

		$this->profiling = $profiling || $this->debug;
		$this->timer = new sts_timer();
		if( $this->debug ) echo "<p>Create sts_validate_cache</p>";

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

	public function get_source() {
		return $this->source;
	}

	public function get_code() {
		return (string) $this->code;
	}

	public function get_last_at() {
		return $this->last_at;
	}

	public function get_lat() {
		return $this->lat;
	}

	public function get_lon() {
		return $this->lon;
	}

	public function get_description() {
		return (string) $this->description;
	}

	private function escape( $arg ) {
		return (isset($arg) ? $this->real_escape_string($arg) : '');
	}
	
	public function tidy() {
		return $this->delete_row( "CREATED_DATE < DATE_SUB(NOW(), INTERVAL ".$this->cache_duration." DAY)" );
	}
	
	public function get_duration() {
		return $this->cache_duration;
	}

	// Based on the two addresses, come up with a match for the query
	private function prepare_match ( $address ) {
		if( $this->debug ) echo "<p>".__METHOD__.": entry</p>";
		$matches = array();
		if( is_array($address) ) {
			$matches[] = empty($address['ADDRESS']) ?
				"ADDRESS IS NULL" :
				"ADDRESS = '".$this->escape($address['ADDRESS'])."'";
			$matches[] = empty($address['ADDRESS2']) ?
				"ADDRESS2 IS NULL" :
				"ADDRESS2 = '".$this->escape($address['ADDRESS2'])."'";
			$matches[] = empty($address['CITY']) ?
				"CITY IS NULL" :
				"CITY = '".$this->escape($address['CITY'])."'";
			$matches[] = empty($address['STATE']) ?
				"STATE IS NULL" :
				"STATE = '".$this->escape($address['STATE'])."'";
			$matches[] = empty($address['ZIP_CODE']) ?
				"ZIP_CODE IS NULL" :
				"ZIP_CODE = '".$this->escape($address['ZIP_CODE'])."'";
			$matches[] = empty($address['COUNTRY']) ?
				"COUNTRY IS NULL" :
				"COUNTRY = '".$this->escape($address['COUNTRY'])."'";

		}
		return implode(" AND ", $matches);
	}
	
	public function get_cache( $address ) {

		if( $this->debug ) echo "<p>".__METHOD__.": entry</p>";
		$this->timer->start();
		$this->time_pcm = $this->time_caching = $this->time_processing = 0.0;
	
		$valid = 'none'; // initialize
		$this->source = 'none';
		$this->last_at = date("Y-m-d H:i:s");

		if( is_array($address) ) {
			// SCR# 49 - Default country to USA
			if( empty($address['COUNTRY'])) $address['COUNTRY'] = 'USA';

			
			$this->time_processing = $this->timer->split();
			$cache = $this->fetch_rows( $this->prepare_match ( $address )." AND
				CREATED_DATE > DATE_SUB(NOW(), INTERVAL ".$this->cache_duration." DAY)",
				"ADDR_VALID, ADDR_CODE, ADDR_DESCR, VALID_SOURCE, ACCESS_TIMING, ACCESS_COUNT,
				LAT, LON, CREATED_DATE", "CREATED_DATE DESC" );
		
			$this->time_caching = $this->timer->split() - $this->time_processing;
		
			if( is_array($cache) && count($cache) > 0 ) {	// Found
				$valid = $cache[0]['ADDR_VALID'];
				$this->code = $cache[0]['ADDR_CODE'];
				$this->description = $cache[0]['ADDR_DESCR'];
				$this->source = $cache[0]['VALID_SOURCE'];
				$this->last_at = $cache[0]['CREATED_DATE'];
				$this->lat = isset($cache[0]['LAT']) ? floatval($cache[0]['LAT']) : 0;
				$this->lon = isset($cache[0]['LON']) ? floatval($cache[0]['LON']) : 0;
				
				$timing = (isset($cache[0]['ACCESS_TIMING']) ? $cache[0]['ACCESS_TIMING'] : 0.0) + $this->time_caching;
				$count = (isset($cache[0]['ACCESS_COUNT']) ? $cache[0]['ACCESS_COUNT'] : 0) + 1;
				$cache = $this->update_row( $this->prepare_match ( $address ),
					array(
						array("field" => "ACCESS_TIMING", "value" => $timing),
						array("field" => "ACCESS_COUNT", "value" => $count),
					)
				);
			}
		}
		
		return $valid;
	}

	public function put_cache( $address, $valid, $from ) {
		
		if( $this->debug ) echo "<p>".__METHOD__.": entry</p>";
	
		// Delete previous entry
		$result = $this->delete_row( $this->prepare_match ( $address ) );
	
		//! Gagh - best eaten alive
		$changes = array();
		if( isset($address['ADDRESS'])) $changes['ADDRESS'] = $address['ADDRESS'];
		if( isset($address['ADDRESS2'])) $changes['ADDRESS2'] = $address['ADDRESS2'];
		if( isset($address['CITY'])) $changes['CITY'] = $address['CITY'];
		if( isset($address['STATE'])) $changes['STATE'] = $address['STATE'];
		if( isset($address['ZIP_CODE'])) $changes['ZIP_CODE'] = $address['ZIP_CODE'];
		if( isset($address['COUNTRY'])) $changes['COUNTRY'] = $address['COUNTRY'];
		$changes['ADDR_VALID']	= $valid;
		$changes['ADDR_CODE'] = $from->get_code();
		$changes['ADDR_DESCR'] = $this->trim_to_fit( 'ADDR_DESCR', $from->get_description() );
		$changes['VALID_SOURCE'] = $from->get_source();
		$changes['PCM_TIMING'] = $from->get_time_pcm();
		$lat = $from->get_lat();
		$lon = $from->get_lon();
		if( $lat <> 0 ) $changes['LAT'] = $lat;
		if( $lon <> 0 ) $changes['LON'] = $lon;
									
		$result = $this->add( $changes );
		
		return $result;
	}	
	
	public function top_10() {
		return $this->database->multi_query("
			set @total_access = (select sum(access_count)
				from exp_pcm_cache);
			
			SELECT concat_ws( ', ',address, address2, city, state, zip_code, COUNTRY) as addr,
            ADDR_VALID, ADDR_CODE, ADDR_DESCR, VALID_SOURCE,
			access_count,
			round(access_count / @total_access * 100) as pct
			FROM exp_pcm_cache
			where access_count is not null
			and CREATED_DATE > DATE_SUB(NOW(), INTERVAL ".$this->cache_duration." DAY)
			order by access_count desc
			limit 10");
	}
}

//! sts_distance_cache - caching of distance between two addresses.
// Works with Google, PC*Miler
class sts_distance_cache extends sts_table {

	private $profiling = false;
	private $timer;					// Timer for timing PC*Miler performance
	private $time_pcm;
	private $time_caching;
	private $time_processing;
	private $source = '';
	private $last_at = '';
	private $setting;
	private $cache_duration;

	public function __construct( $database, $debug = false, $profiling = false ) {
		$this->debug = $debug;
		parent::__construct( $database, PCM_DISTANCE_CACHE, $debug);

		$this->setting = sts_setting::getInstance( $database, $debug );
		$this->cache_duration = $this->setting->get( 'api', 'GEO_CACHE_DURATION' );

		$this->profiling = $profiling || $this->debug;
		$this->timer = new sts_timer();
		if( $this->debug ) echo "<p>Create sts_distance_cache</p>";

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

	public function get_source() {
		return $this->source;
	}

	public function get_last_at() {
		return $this->last_at;
	}

	private function escape( $arg ) {
		return (isset($arg) ? $this->real_escape_string($arg) : '');
	}
	
	public function tidy() {
		return $this->delete_row( "CREATED_DATE < DATE_SUB(NOW(), INTERVAL ".$this->cache_duration." DAY)" );
	}
	
	public function get_duration() {
		return $this->cache_duration;
	}

	// Based on the two addresses, come up with a match for the query
	private function prepare_match ( $address1, $address2 ) {
		if( $this->debug ) echo "<p>".__METHOD__.": entry</p>";
		$matches = array();
		if( is_array($address1) ) {
			$matches[] = empty($address1['ADDRESS']) ?
				"ADDRESS_1 IS NULL" :
				"ADDRESS_1 = '".$this->escape($address1['ADDRESS'])."'";
			$matches[] = empty($address1['ADDRESS2']) ?
				"ADDRESS2_1 IS NULL" :
				"ADDRESS2_1 = '".$this->escape($address1['ADDRESS2'])."'";
			$matches[] = empty($address1['CITY']) ?
				"CITY_1 IS NULL" :
				"CITY_1 = '".$this->escape($address1['CITY'])."'";
			$matches[] = empty($address1['STATE']) ?
				"STATE_1 IS NULL" :
				"STATE_1 = '".$this->escape($address1['STATE'])."'";
			$matches[] = empty($address1['ZIP_CODE']) ?
				"ZIP_CODE_1 IS NULL" :
				"ZIP_CODE_1 = '".$this->escape($address1['ZIP_CODE'])."'";
			$matches[] = empty($address1['COUNTRY']) ?
				"COUNTRY_1 IS NULL" :
				"COUNTRY_1 = '".$this->escape($address1['COUNTRY'])."'";

			$matches[] = empty($address2['ADDRESS']) ?
				"ADDRESS_2 IS NULL" :
				"ADDRESS_2 = '".$this->escape($address2['ADDRESS'])."'";
			$matches[] = empty($address2['ADDRESS2']) ?
				"ADDRESS2_2 IS NULL" :
				"ADDRESS2_2 = '".$this->escape($address2['ADDRESS2'])."'";
			$matches[] = empty($address2['CITY']) ?
				"CITY_2 IS NULL" :
				"CITY_2 = '".$this->escape($address2['CITY'])."'";
			$matches[] = empty($address2['STATE']) ?
				"STATE_2 IS NULL" :
				"STATE_2 = '".$this->escape($address2['STATE'])."'";
			$matches[] = empty($address2['ZIP_CODE']) ?
				"ZIP_CODE_2 IS NULL" :
				"ZIP_CODE_2 = '".$this->escape($address2['ZIP_CODE'])."'";
			$matches[] = empty($address2['COUNTRY']) ?
				"COUNTRY_2 IS NULL" :
				"COUNTRY_2 = '".$this->escape($address2['COUNTRY'])."'";

		}
		return implode(" AND ", $matches);
	}
	
	private function identical( $address1, $address2 ) {
		if( $this->debug ) echo "<p>".__METHOD__.": entry</p>";
	    sort( $address1 );
	    sort( $address2 );

		return $address1 == $address2;
	}

	public function get_cache( $address1, $address2 ) {

		if( $this->debug ) echo "<p>".__METHOD__.": entry</p>";
		$this->timer->start();
		$this->time_pcm = $this->time_caching = $this->time_processing = 0.0;
	
		$distance = -1;	// initialize
		$this->source = 'none';
		$this->last_at = date("Y-m-d H:i:s");

		if( is_array($address1) && is_array($address2) ) {
			// SCR# 49 - Default country to USA
			if( empty($address1['COUNTRY'])) $address1['COUNTRY'] = 'USA';
			if( empty($address2['COUNTRY'])) $address2['COUNTRY'] = 'USA';

			// Check if they match, zero distance
			if( $this->identical( $address1, $address2 ) ) {
				$distance = 0;
			} else {
			
				$this->time_processing = $this->timer->split();
				$cache = $this->fetch_rows( $this->prepare_match ( $address1, $address2 )." AND
				CREATED_DATE > DATE_SUB(NOW(), INTERVAL ".$this->cache_duration." DAY)",
				
				"ADDR_CODE, ADDR_DESCR, DISTANCE, DISTANCE_SOURCE, CREATED_DATE, ACCESS_TIMING, ACCESS_COUNT", "CREATED_DATE DESC" );
			
				$this->time_caching = $this->timer->split() - $this->time_processing;
			
				if( is_array($cache) && count($cache) > 0 ) {	// Found
					$this->code = $cache[0]['ADDR_CODE'];
					$this->description = $cache[0]['ADDR_DESCR'];
					$this->source = $cache[0]['DISTANCE_SOURCE'];
					$this->last_at = $cache[0]['CREATED_DATE'];
					$distance = $cache[0]['DISTANCE'];
					
					$timing = (isset($cache[0]['ACCESS_TIMING']) ? $cache[0]['ACCESS_TIMING'] : 0.0) + $this->time_caching;
					$count = (isset($cache[0]['ACCESS_COUNT']) ? $cache[0]['ACCESS_COUNT'] : 0) + 1;
					$cache = $this->update_row( $this->prepare_match ( $address1, $address2 ),
						array(
							array("field" => "ACCESS_TIMING", "value" => $timing),
							array("field" => "ACCESS_COUNT", "value" => $count),
						)
					);
				}
			}
		}
		
		return $distance;
	}

	public function put_cache( $address1, $address2, $distance, $from ) {
		
		if( $this->debug ) echo "<p>".__METHOD__.": entry</p>";
	
		// Delete previous entry
		$result = $this->delete_row( $this->prepare_match ( $address1, $address2 ) );
	
		//! Gagh - best eaten alive
		$changes = array();
		if( isset($address1['ADDRESS']))
			$changes['ADDRESS_1'] = $address1['ADDRESS'];
		if( isset($address1['ADDRESS2']))
			$changes['ADDRESS2_1'] = $address1['ADDRESS2'];
		if( isset($address1['CITY']))
			$changes['CITY_1'] = $address1['CITY'];
		if( isset($address1['STATE']))
			$changes['STATE_1'] = $address1['STATE'];
		if( isset($address1['ZIP_CODE']))
			$changes['ZIP_CODE_1'] = $address1['ZIP_CODE'];
		if( isset($address1['COUNTRY']))
			$changes['COUNTRY_1'] = $address1['COUNTRY'];
	
		if( isset($address2['ADDRESS']))
			$changes['ADDRESS_2'] = $address2['ADDRESS'];
		if( isset($address2['ADDRESS2']))
			$changes['ADDRESS2_2'] = $address2['ADDRESS2'];
		if( isset($address2['CITY']))
			$changes['CITY_2'] = $address2['CITY'];
		if( isset($address2['STATE']))
			$changes['STATE_2'] = $address2['STATE'];
		if( isset($address2['ZIP_CODE']))
			$changes['ZIP_CODE_2'] = $address2['ZIP_CODE'];
		if( isset($address2['COUNTRY']))
			$changes['COUNTRY_2'] = $address2['COUNTRY'];
		
		$changes['ADDR_CODE']		= $from->get_code();
		$changes['ADDR_DESCR']		= $this->trim_to_fit( 'ADDR_DESCR', $from->get_description() );
		$changes['DISTANCE']		= $distance;
		$changes['DISTANCE_SOURCE']	= $from->get_source();
		$changes['PCM_TIMING']		= $from->get_time_pcm();
		
		$result = $this->add( $changes );
		
		return $result;
	}
	
	public function top_10() {
		return $this->database->multi_query("
			set @total_access = (select sum(access_count)
				from exp_pcm_distance_cache);
			
			SELECT concat_ws( ', ',city_1, state_1, zip_code_1, COUNTRY_1) as origin,
			concat_ws( ', ',city_2, state_2, zip_code_2, COUNTRY_2) as destination,
			round(distance) as distance,
			DISTANCE_SOURCE,
			access_count,
			round(access_count / @total_access * 100) as pct
			FROM exp_pcm_distance_cache
			where access_count is not null
			and CREATED_DATE > DATE_SUB(NOW(), INTERVAL ".$this->cache_duration." DAY)
			order by access_count desc
			limit 10");
	}
}
	
class sts_canada_postcode extends sts_table {

	private $setting;
	private $enabled;
	private $table_exists;

	// Constructor does not need the table name
	public function __construct( $database, $debug = false ) {

		$this->debug = $debug;
		if( $this->debug ) echo "<p>Create sts_canada_postcode</p>";
		parent::__construct( $database, CANADA_POST_CODES_TABLE, $debug);
		$this->setting = sts_setting::getInstance( $database, $debug );
		$this->enabled = ($this->setting->get( 'option', 'CANADA_POSTCODES' ) == 'true');
		if( $this->enabled ) {
			$this->table_exists = $this->exists( CANADA_POST_CODES_TABLE );
		
			if( $this->table_exists ) {

				if( isset($_SESSION["CPTABLE_ROWS"])) {
					if( $this->debug ) echo "<p>use CPTABLE_ROWS</p>";
					$number_of_rows = $_SESSION["CPTABLE_ROWS"];
				} else {
					if( $this->debug ) echo "<p>count table</p>";
					$result = $this->fetch_rows( "", "count(*) as x" );
					$number_of_rows = 0;
					if( is_array($result) && count($result) == 1 && isset($result[0]["x"]) ) {
						$_SESSION["CPTABLE_ROWS"] = $number_of_rows = intval($result[0]["x"]);
					}
				}				
				
				if( $number_of_rows == 0 ) {
					$email = sts_email::getInstance($this->database, $this->debug);
					$email->send_alert("sts_canada_postcode: Canada Post Codes Table Empty<br>".
						"Please install the ".CANADA_POST_CODES_TABLE." table<br>", EXT_ERROR_ERROR );
					$this->enabled = false;
				}
		
			} else {
				$email = sts_email::getInstance($this->database, $this->debug);
				$email->send_alert("sts_canada_postcode: Canada Post Codes Table Missing<br>".
					"Please install the ".CANADA_POST_CODES_TABLE." table<br>", EXT_ERROR_ERROR );
					$this->enabled = false;
			}
		}
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
    
    public function enabled() {
	    return $this->enabled;
    }	

}

?>
