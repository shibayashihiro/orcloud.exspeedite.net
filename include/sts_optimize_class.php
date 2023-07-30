<?php

// $Id: sts_optimize_class.php 5156 2023-07-11 00:54:30Z dev $
// Optimization class, optimize load based on least distance.

// no direct access
defined('_STS_INCLUDE') or die('Restricted access');

require_once( "sts_load_class.php" );
require_once( "sts_stop_class.php" );
require_once( "sts_shipment_class.php" );
require_once( "sts_zip_class.php" );

class sts_optimize extends sts_load {
	private $patterns;
	private $stop_table;
	private $checks;
	
	// Constructor does not need the table name
	public function __construct( $database, $debug = false ) {

		$this->primary_key = "LOAD_CODE";

		if( $this->debug ) echo "<p>Create sts_optimize</p>";
		parent::__construct( $database, $debug);

		$this->stop_table = sts_stop_left_join::getInstance($this->database, $this->debug);
		$this->patterns = [];
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

	private function add_pattern( $tofix ) {
		$this->patterns[] = $tofix;
	}
	
	public function get_patterns() {
		return $this->patterns;
	}
	
	private function get_permutations( $numbers, $prefix ) {
		if( is_array($numbers) && count($numbers) > 0 ) {
			foreach( $numbers as $number ) {
				$tofix = $prefix;
				$tofix[] = $number;
				$next_level = [];
				foreach( $numbers as $copy ) {
					if( $copy != $number ) {
						$next_level[] = $copy;
					}
				}
				if( is_array($next_level) && count($next_level) > 0 ) {
					$this->get_permutations( $next_level, $tofix );
				} else {
				//	echo __METHOD__.': '.implode(', ', $tofix).'<br>';
					$this->add_pattern($tofix);
				}
			}
		}
	}
	
	private function must_be_before($pick, $drop) {
		$save = [];
		foreach( $this->patterns as $pattern ) {
			$got_pick = $got_drop = false;
			$invalid = false;
			foreach( $pattern as $stop ) {
				if( $stop == $pick ) {
					$got_pick = true;
				} else if( $stop == $drop ) {
					$got_drop = true;
				}
				if( $got_drop && ! $got_pick ) {
					$invalid = true;
				}
			}
			
			if( ! $invalid ) {
				$save[] = $pattern;
			}
		}
		
		$this->patterns = $save;
	}
	
	private function must_be_first($reposition) {
		$save = [];
		foreach( $this->patterns as $pattern ) {
			if( $pattern[0] == $reposition ) {
				$save[] = $pattern;
			}
		}
		
		$this->patterns = $save;
	}
	
	private function update_sequence( $pattern ) {
		if( $this->debug ) echo '<h3>New Sequence</h3>'.implode(', ', $pattern ).'<br>';
		
		$sequence_no = 1;
		foreach( $pattern as $stop ) {
			$this->stop_table->update( $stop, ['SEQUENCE_NO' => $sequence_no] );
			$sequence_no++;
		}
	}
	
	public function optimize( $load_code ) {
		$result = false;
		$check = $this->fetch_rows("LOAD_CODE = ".$load_code, "CURRENT_STATUS" );
		
		if( $this->debug ) echo '<h2>'.__METHOD__.': load = '.$load_code.'</h2>';
		
		if( is_array($check) && count($check) == 1 && isset($check[0]["CURRENT_STATUS"])) {
			$load_status = $this->state_behavior[$check[0]["CURRENT_STATUS"]];

			if( $this->debug ) {
				echo "<p>".__METHOD__.": check = </p><pre>";
				var_dump($check);
				echo "</pre>";				
			}			
			if( ! in_array($load_status, array('complete', 'approved', 'billed', 'cancel')) ) {
				if( $this->debug ) echo "<h3>".__METHOD__.": able to optimize.</h3>";
				

				$stops = $this->stop_table->fetch_rows( "LOAD_CODE = ".$load_code,
					"STOP_CODE, SEQUENCE_NO, STOP_TYPE, IM_STOP_TYPE, 
					STOP_CURRENT_STATUS, STOP_STATUS, SHIPMENT,
					(CASE STOP_TYPE WHEN 'stop' AND IM_STOP_TYPE IS NULL THEN NULL ELSE SHIPMENT END) AS SHIPMENT2,
					NAME, ADDRESS, ADDRESS2, CITY, STATE, ZIP_CODE, COUNTRY",
					"SEQUENCE_NO ASC" );
				
				$zip_table = new sts_zip($this->database, $this->debug);

				
				// Make a list of stop numbers
				if( is_array($stops) && count($stops) > 0 ) {
					$stopnum = [];
					$stop_type = [];
					$pick = [];
					$drop = [];
					$stop_address = [];
					foreach($stops as $row) {
						$stopnum[] = $row['STOP_CODE'];
						$stop_type[$row['STOP_CODE']] = $row['STOP_TYPE'];
						
						$address = [];
						if( isset($row['ADDRESS']) && $row['ADDRESS'] <> '' )
							$address['ADDRESS'] = $row['ADDRESS'];
						if( isset($row['ADDRESS2']) && $row['ADDRESS2'] <> '' )
							$address['ADDRESS2'] = $row['ADDRESS2'];
						if( isset($row['CITY']) && $row['CITY'] <> '' )
							$address['CITY'] = $row['CITY'];
						if( isset($row['STATE']) && $row['STATE'] <> '' )
							$address['STATE'] = $row['STATE'];
						if( isset($row['ZIP_CODE']) && $row['ZIP_CODE'] <> '' )
							$address['ZIP_CODE'] = $row['ZIP_CODE'];
						if( isset($row['COUNTRY']) && $row['COUNTRY'] <> '' )
							$address['COUNTRY'] = $row['COUNTRY'];
						$stop_address[$row['STOP_CODE']] = $address;
						
						if( $row['STOP_TYPE'] == 'pick' ) {
							$pick[$row['SHIPMENT']] = $row['STOP_CODE'];
						} else
						if( $row['STOP_TYPE'] == 'drop' ) {
							$drop[$row['SHIPMENT']] = $row['STOP_CODE'];
						}
					}
					
					$this->get_permutations( $stopnum, [] );
	
					// Elliminate patterns where the drop is before the corresponding pick
					foreach( array_keys($pick) as $shipment ) {
						$this->must_be_before($pick[$shipment], $drop[$shipment]);
					}
					
					// If the first stop is of type 'stop' then discard any that don't match.
					if( $stop_type[$stopnum[0]] == 'stop' ) {
						$this->must_be_first( $stopnum[0] );
					}
					

				//	if( $this->debug ) 
					echo '<h4 class="text-center">('.count($this->patterns).' Valid permutations X '.
						(count($stopnum)-1).' distance lookups = '.count($this->patterns) * (count($stopnum)-1).' total)</h4>';
					
					$this->checks = count($this->patterns) * (count($stopnum) - 1);
					$total_distance = [];
					$best_distance = $best_pattern = -1;
					$current_distance = $current_pattern = -1;
					$count = 1;
					for( $pattern_number = 0; $pattern_number < count($this->patterns); $pattern_number++ ) {
						$pattern = $this->patterns[$pattern_number];
						$out = [];
						
						// Calculate distances
						$previous_stop = -1;
						$stop_distance = [];
						$total_distance[$pattern_number] = 0;
						$current = false;
						foreach( $pattern as $stop ) {
							if( $previous_stop == -1 ) {
								$stop_distance[$stop] = 0;
							} else {
								$stop_distance[$stop] =
									$zip_table->get_distance_various( $stop_address[$previous_stop],
										$stop_address[$stop] );
								$this->update_count( $count++.' / '.$this->checks );
								
								usleep(500);	// Sleep a while to not overload upstream
							}
							$previous_stop = $stop;
							
							$total_distance[$pattern_number] += $stop_distance[$stop];

							$out[] = $stop.' ('.$stop_type[$stop].', dist = '.$stop_distance[$stop].')';
						}
						if( $this->debug ) echo 'Pattern# '.$pattern_number.' : '.implode(', ', $out ).' total distance = '.$total_distance[$pattern_number].( $pattern == $stopnum ? ' (current)' : '').'<br>';
						
						if( $pattern == $stopnum ) {
							$current_distance = $total_distance[$pattern_number];
							$current_pattern = $pattern_number;
						}
						
						if( $best_distance == -1 ||
							$total_distance[$pattern_number] < $best_distance) {
							$best_distance = $total_distance[$pattern_number];
							$best_pattern = $pattern_number;
						}
					}

					if( $this->debug ) echo '<h3>Best Pattern is #'.$best_pattern.' with '.$best_distance.' miles</h3>';
					if( $current_pattern > -1 ) {
						if( $this->debug ) echo '<h3>Current Pattern is #'.$current_pattern.' with '.$current_distance.'</h3>
						<h3>Savings '.($current_distance - $best_distance).' miles</h3>';
						
						// We CAN improve things
						if( $best_pattern != $current_pattern ) {
							echo '<h4 class="text-info"><span class="text-info glyphicon glyphicon-info-sign"></span> Optimized route to save '.($current_distance - $best_distance).' miles ('.$best_distance.' miles total)</h4>';
							$this->update_sequence( $this->patterns[$best_pattern] );				
						} else {
							echo '<h4 class="text-info"><span class="text-info glyphicon glyphicon-info-sign"></span> The current route is the best route ('.$best_distance.' miles)</h4>';
						}
					}
					
				}

			} else {
				if( $this->debug ) echo "<h3>".__METHOD__.": wrong status to optimize.</h3>";
			}
		} else {
			if( $this->debug ) echo "<h3>".__METHOD__.": unable to get status.</h3>";
		}
		
		return $result;
	}
	
	public function update_count( $msg ) {
		echo "<script type=\"text/javascript\">

document.getElementById(\"loading_count\").innerHTML=\"".$msg."\";

</script>";
		ob_flush(); flush();
	}
	
}


?>