<?php

// $Id: sts_repair_class.php 5030 2023-04-12 20:31:34Z duncan $
// Repair class, designed for inline repair of a variety of database inconsistencies.

// no direct access
defined('_STS_INCLUDE') or die('Restricted access');

require_once( "sts_table_class.php" );
require_once( "sts_stop_class.php" );
require_once( "sts_shipment_class.php" );
require_once( "sts_shipment_load_class.php" );
require_once( "sts_load_class.php" );
require_once( "sts_email_class.php" );

class sts_repair extends sts_table {
	
	// Constructor does not need the table name
	public function __construct( $database, $debug = false ) {

		$this->debug = $debug;
		$this->database = $database;
		$this->primary_key = "NONE";
		if( $this->debug ) echo "<p>".__METHOD__.": Create sts_repair</p>";
	}
	
	// Allow re-use of objects - singleton function
	public static function getInstance( $database, $debug = false ) {
		static $instance = null;
		$myclass = get_class ();
		if( $debug ) echo "<p>".__METHOD__.": Get instance of $myclass</p>";
		if (null === $instance) {
			$instance = new $myclass( $database, $debug );
		}
		return $instance;
    }

	private function vl( $load_code ) {
		return '<a href="exp_viewload.php?CODE='.$load_code.'" target="_blank">'.$load_code.'</a>';
	}
	
	private function vs( $load_code ) {
		return '<a href="exp_addshipment.php?CODE='.$load_code.'" target="_blank">'.$load_code.'</a>';
	}
	
	private function load_stat( $status ) {
		$load_table = sts_load::getInstance($this->database, $this->debug);
		return $status.' ('.(isset($load_table->state_name[$status]) ? $load_table->state_name[$status] : '??').')';
	}
	
	private function shipment_stat( $status ) {
		$shipment_table = sts_shipment::getInstance($this->database, $this->debug);
		return $status.' ('.(isset($shipment_table->state_name[$status]) ? $shipment_table->state_name[$status] : '??').')';
	}
	
	private function stop_stat( $status ) {
		$stop_table = sts_stop::getInstance($this->database, $this->debug);
		return $status.' ('.(isset($stop_table->state_name[$status]) ? $stop_table->state_name[$status] : '??').')';
	}
	
	//! Checking Functions -----------------------------------------------------------------------

	// Return a list of empty loads.
	public function check_empty_load_record( $load = 0 ) {
		if( $this->debug ) echo "<p>".__METHOD__.": $load</p>";
		$load_table = sts_load::getInstance($this->database, $this->debug);
		$check = $load_table->fetch_rows("(SELECT COUNT(*) FROM EXP_SHIPMENT
			WHERE EXP_SHIPMENT.LOAD_CODE = EXP_LOAD.LOAD_CODE) = 0
			AND (SELECT COUNT(*) FROM EXP_STOP
			WHERE EXP_STOP.LOAD_CODE = EXP_LOAD.LOAD_CODE) = 0".
			($load > 0 ? " AND L.LOAD_CODE = ".$load : ""), 
			"LOAD_CODE, CURRENT_STATUS", "LOAD_CODE ASC" );
		return $check;
	}


	// Return a list of missing shipments.
	public function check_load_missing_shipments( $load = 0 ) {
		if( $this->debug ) echo "<p>".__METHOD__.": $load</p>";
		$load_table = sts_load::getInstance($this->database, $this->debug);
		$check = $load_table->database->get_multiple_rows("
			SELECT L.LOAD_CODE, L.CURRENT_STATUS AS LOAD_STATUS, 
				SH.SHIPMENT_CODE, SH.CURRENT_STATUS AS SHIPMENT_STATUS
			FROM EXP_LOAD L, EXP_STOP S, EXP_SHIPMENT SH
			WHERE L.LOAD_CODE = S.LOAD_CODE
			".($load > 0 ? "AND L.LOAD_CODE = ".$load : "")."
			AND S.STOP_TYPE = 'drop'
			AND S.SHIPMENT > 0
			AND S.SHIPMENT = SH.SHIPMENT_CODE
			AND SH.LOAD_CODE <> L.LOAD_CODE
			ORDER BY S.SHIPMENT
		" );
		return $check;
	}

	// Return a list of missing shipments.
	public function check_load_missing_stops( $load = 0 ) {
		if( $this->debug ) echo "<p>".__METHOD__.": $load</p>";
		$load_table = sts_load::getInstance($this->database, $this->debug);
		$check = $load_table->database->get_multiple_rows("
			SELECT L.LOAD_CODE, L.CURRENT_STATUS AS LOAD_STATUS, 
				SH.SHIPMENT_CODE, SH.CURRENT_STATUS AS SHIPMENT_STATUS
			FROM EXP_LOAD L, EXP_SHIPMENT SH
			WHERE L.LOAD_CODE = SH.LOAD_CODE
			".($load > 0 ? "AND L.LOAD_CODE = ".$load : "")."
			AND NOT EXISTS (SELECT S.STOP_CODE
			FROM EXP_STOP S
			WHERE S.LOAD_CODE = L.LOAD_CODE
			AND S.SHIPMENT = SH.SHIPMENT_CODE)
			ORDER BY SH.SHIPMENT_CODE
		" );
		return $check;
	}

	// Return a list of stops that should be completed.
	public function check_load_incomplete_stops( $load = 0 ) {
		if( $this->debug ) echo "<p>".__METHOD__.": $load</p>";
		$load_table = sts_load::getInstance($this->database, $this->debug);
		$check = $load_table->database->get_multiple_rows("
			SELECT L.LOAD_CODE, L.CURRENT_STATUS AS LOAD_STATUS, S.STOP_CODE, S.STOP_TYPE, S.SEQUENCE_NO, S.CURRENT_STATUS
			FROM EXP_LOAD L, EXP_STOP S
			WHERE (SELECT BEHAVIOR
			FROM EXP_STATUS_CODES 
			WHERE STATUS_CODES_CODE = L.CURRENT_STATUS) IN ('complete', 'approved', 'billed')
			AND S.LOAD_CODE = L.LOAD_CODE
			".($load > 0 ? "AND L.LOAD_CODE = ".$load : "")."
			AND (SELECT BEHAVIOR
			FROM EXP_STATUS_CODES 
			WHERE STATUS_CODES_CODE = S.CURRENT_STATUS) IN ('entry')
		" );
		return $check;
	}

	// Return a list of shipments that should be completed.
	public function check_load_incomplete_shipments( $load = 0 ) {
		if( $this->debug ) echo "<p>".__METHOD__.": $load</p>";
		$load_table = sts_load::getInstance($this->database, $this->debug);
		$check = $load_table->database->get_multiple_rows("
			SELECT L.LOAD_CODE, L.CURRENT_STATUS AS LOAD_STATUS, 
				S.SHIPMENT_CODE,S.CURRENT_STATUS
			FROM EXP_LOAD L, EXP_SHIPMENT S
			WHERE (SELECT BEHAVIOR
			FROM EXP_STATUS_CODES 
			WHERE STATUS_CODES_CODE = L.CURRENT_STATUS) IN ('complete', 'approved', 'billed')
			AND S.LOAD_CODE = L.LOAD_CODE
			".($load > 0 ? "AND L.LOAD_CODE = ".$load : "")."
			AND (SELECT BEHAVIOR
			FROM EXP_STATUS_CODES 
			WHERE STATUS_CODES_CODE = S.CURRENT_STATUS) NOT IN ('dropped', 'approved', 'billed', 'unapproved')
		" );
		return $check;
	}

	// Check if the load state is incorrect.
	// DO NOT USE, work in progress.
	public function check_load_wrong_state_for_stop( $load = 0 ) {
		if( $this->debug ) echo "<p>".__METHOD__.": $load</p>";
		$load_table = sts_load::getInstance($this->database, $this->debug);
		$check = $load_table->database->get_multiple_rows("
			SELECT LOAD_CODE, LOAD_STATUS, CURRENT_STOP, NUM_STOPS, STOP_TYPE,
			CASE STOP_TYPE 
				WHEN 'drop' THEN ".$load_table->behavior_state['arrive cons']."
				WHEN 'pick' THEN ".$load_table->behavior_state['arrive shipper']."
				WHEN 'pickdock' THEN ".$load_table->behavior_state['arrshdock']."
				WHEN 'dropdock' THEN ".$load_table->behavior_state['arrrecdock']."
				WHEN 'stop' THEN ".$load_table->behavior_state['arrive stop']."
			END AS POSSIBLE_STATUS
			FROM (SELECT LOAD_CODE, CURRENT_STATUS AS LOAD_STATUS, CURRENT_STOP,
			(SELECT STOP_TYPE FROM EXP_STOP
			WHERE EXP_STOP.LOAD_CODE = EXP_LOAD.LOAD_CODE
			AND SEQUENCE_NO = CURRENT_STOP) AS STOP_TYPE,
			(SELECT COUNT(*) AS NUM_STOPS FROM EXP_STOP
			WHERE EXP_LOAD.LOAD_CODE = EXP_STOP.LOAD_CODE) AS NUM_STOPS
			FROM EXP_LOAD
			WHERE CURRENT_STOP > 0
			".($load > 0 ? "AND LOAD_CODE = ".$load : "")."
			) X
			WHERE NOT ((LOAD_STATUS = ".$load_table->behavior_state['dispatch']." AND CURRENT_STOP = 1)
			OR (LOAD_STATUS IN (".$load_table->behavior_state['complete'].",".$load_table->behavior_state['approved'].",".$load_table->behavior_state['billed'].") AND CURRENT_STOP = NUM_STOPS))
			AND ( (LOAD_STATUS <> ".$load_table->behavior_state['arrive cons']." AND STOP_TYPE = 'drop')
			OR (LOAD_STATUS <> ".$load_table->behavior_state['arrive shipper']." AND STOP_TYPE = 'pick')
			OR (LOAD_STATUS <> ".$load_table->behavior_state['arrshdock']." AND STOP_TYPE = 'pickdock')
			OR (LOAD_STATUS <> ".$load_table->behavior_state['arrrecdock']." AND STOP_TYPE = 'dropdock')
			OR (LOAD_STATUS <> ".$load_table->behavior_state['arrive stop']." AND STOP_TYPE = 'stop') )
		");
		return $check;
	}

	// Return a list of missing shipments.
	public function check_load_not_complete( $load = 0 ) {
		if( $this->debug ) echo "<p>".__METHOD__.": $load</p>";
		$load_table = sts_load::getInstance($this->database, $this->debug);
		$check = $load_table->database->get_multiple_rows("
			SELECT x.LOAD_CODE, x.STATUS_STATE, x.CURRENT_STOP, y.NUM_STOPS, z.COMPLETE_STOPS
			FROM (SELECT l.LOAD_CODE, s1.STATUS_STATE, l.CURRENT_STOP
			from EXP_LOAD l, exp_status_codes s1
			where l.CURRENT_STATUS = s1.STATUS_CODES_CODE
			and s1.SOURCE_TYPE = 'load'
			and s1.BEHAVIOR not in ( 'complete','cancel','oapproved','approved','billed')) x
			
			join
			(SELECT LOAD_CODE, COUNT(*) NUM_STOPS
			from EXP_STOP s
			group by LOAD_CODE) y
			
			on x.LOAD_CODE = y.LOAD_CODE
			
			join
			(SELECT LOAD_CODE, COUNT(*) COMPLETE_STOPS
			from EXP_STOP, exp_status_codes
			where CURRENT_STATUS = STATUS_CODES_CODE
			and SOURCE_TYPE = 'stop'
			and BEHAVIOR = 'complete'
			group by LOAD_CODE) z
			
			on x.LOAD_CODE = z.LOAD_CODE
			and y.NUM_STOPS = z.COMPLETE_STOPS
			".($load > 0 ? "AND x.LOAD_CODE = ".$load : "")."
			ORDER BY x.LOAD_CODE ASC" );
		return $check;
	}

	// Return a list of dispatched loads with shipments in ready state
	public function check_disp_load_shipments_ready( $load = 0 ) {
		if( $this->debug ) echo "<p>".__METHOD__.": $load</p>";
		$load_table = sts_load::getInstance($this->database, $this->debug);
		$check = $load_table->database->get_multiple_rows("
			SELECT S.SHIPMENT_CODE, S.CURRENT_STATUS AS SHIPMENT_STATUS, 
			S.LOAD_CODE, L.CURRENT_STATUS AS LOAD_STATUS,
			(SELECT CURRENT_STATUS
            FROM EXP_STOP ST
            WHERE ST.LOAD_CODE = S.LOAD_CODE
            AND ST.SHIPMENT = S.SHIPMENT_CODE
            AND ST.STOP_TYPE = 'pick') AS PICK_STATUS,
            (SELECT CURRENT_STATUS
            FROM EXP_STOP ST
            WHERE ST.LOAD_CODE = S.LOAD_CODE
            AND ST.SHIPMENT = S.SHIPMENT_CODE
            AND ST.STOP_TYPE = 'drop') AS DROP_STATUS
			FROM EXP_SHIPMENT S, EXP_LOAD L
			WHERE S.CURRENT_STATUS = (SELECT STATUS_CODES_CODE
				FROM EXP_STATUS_CODES WHERE SOURCE_TYPE = 'shipment' AND
				BEHAVIOR = 'assign')
			AND S.LOAD_CODE > 0
			AND S.LOAD_CODE = L.LOAD_CODE
			".($load > 0 ? "AND L.LOAD_CODE = ".$load : "")."
			AND L.CURRENT_STATUS IN (SELECT STATUS_CODES_CODE
				FROM EXP_STATUS_CODES WHERE SOURCE_TYPE = 'load' AND
				BEHAVIOR IN ('dispatch', 'depart stop', 'depshdock',
					'depart shipper', 'deprecdock', 'depart cons', 'arrive stop', 'arrshdock',
					'arrive shipper', 'arrrecdock', 'arrive cons'))" );
		return $check;
	}
	
	//! Check if a shipment was on this load before
	public function check_onload_before( $row ) {
		$shipment_table = sts_shipment::getInstance($this->database, $this->debug);
		$check = $shipment_table->database->get_multiple_rows("
			SELECT STATUS_CODE
			FROM EXP_STATUS
			WHERE SOURCE_TYPE = 'shipment'
			AND ORDER_CODE = ".$row["SHIPMENT_CODE"]."
			AND LOAD_CODE = ".$row["LOAD_CODE"]."
		");
				
		$result = is_array($check) && count($check) > 0;
		if( $this->debug ) echo "<p>".__METHOD__.": return ".($result ? 'true' : 'false')."</p>";
		return $result;
	}

	public function check_revenue_incorrect( $load = 0 ) {
		if( $this->debug ) echo "<p>".__METHOD__.": $load</p>";
		$load_table = sts_load::getInstance($this->database, $this->debug);
		$check = $load_table->database->get_multiple_rows("
			SELECT LOAD_CODE, LOAD_REVENUE, LOAD_REVENUE_CUR(LOAD_CODE, CURRENCY) AS ACTUAL
			FROM EXP_LOAD
			WHERE LOAD_CODE = $load
			AND COALESCE(0, LOAD_REVENUE) != LOAD_REVENUE_CUR(LOAD_CODE, CURRENCY)
		");
		
		return $check;
	}

	public function check_expense_incorrect( $load = 0 ) {
		if( $this->debug ) echo "<p>".__METHOD__.": $load</p>";
		$load_table = sts_load::getInstance($this->database, $this->debug);
		$check = $load_table->database->get_multiple_rows("
			SELECT LOAD_CODE, LOAD_EXPENSE, LOAD_EXPENSE_CUR(LOAD_CODE, CURRENCY) AS ACTUAL
			FROM EXP_LOAD
			WHERE LOAD_CODE = $load
			AND COALESCE(0, LOAD_EXPENSE) != LOAD_EXPENSE_CUR(LOAD_CODE, CURRENCY)
		");
		
		return $check;
	}


	//! Top Level Checking Functions -----------------------------------------------------
	public function check_issue( $issue, $print = false, $code = 0 ) {
		if( $this->debug ) echo "<p>".__METHOD__.": ".
			(isset($issue['CODE']) ? $issue['CODE'] : '')." ".
			(isset($issue['LEVEL']) ? $issue['LEVEL'] : '')." $code</p>";
		if( $issue['LEVEL'] == 'group') {
			$failed = 0;
			foreach($issue['ISSUES'] as $sub_issue) {
				$result = $this->check_issue( $sub_issue, $print, $code );
				if( isset($issue['STOPONERROR']) && $issue['STOPONERROR'] && $result > 0 ) {
					return $result;					
				} else {
					$failed += $result;
				}
			}
			return $failed;
		} else {
			if( $print ) {
				echo '<h3>Check for '.$issue['TITLE'].'</h3>';	
				ob_flush(); flush();  sleep(1);
			}
			$check_function = $issue['CHECK'];
			$failed = method_exists($this, $check_function) ? $this->$check_function( $code ) : false;
			
			if( ! method_exists($this, $check_function) ) echo "<p>Check function missing</p>";
		
			if( is_array($failed) && count($failed) > 0 && $print ) {
				echo '<h3>Found '.count($failed).' '.$issue['TITLE'].' <a class="btn btn-md btn-'.$issue['LEVEL'].'"  href="exp_repair_db.php?'.$issue['CODE'].'"><span class="glyphicon glyphicon-wrench"></span> Fix</a></h3>';
				echo '<table class="display table table-striped table-condensed table-bordered table-hover">
				<thead>
				<tr class="exspeedite-bg">';
				foreach( $issue['COLUMNS'] as $key => $format ) {
					echo '<th>'.$format['label'].'</th>';
				}
				
				echo '</tr>
				</thead>
				<tbody>';
				foreach( $failed as $row ) {
					echo '<tr>
					';
					foreach( $issue['COLUMNS'] as $key => $format ) {
						echo '<td'.(isset($format['align']) && $format['align'] <> '' ? ' class="text-'.$format['align'].'"' :'').'>';
						switch( $format['format'] ) {
							case 'load': echo $this->vl($row[$key]); break;
							case 'load status': echo $this->load_stat($row[$key]); break;
							case 'shipment': echo $this->vs($row[$key]); break;
							case 'shipment status': echo $this->shipment_stat($row[$key]); break;
							case 'stop status': echo $this->stop_stat($row[$key]); break;
							default: echo $row[$key];
						}
						echo '</td>
						';
					}
					
					echo '</tr>
					';
				}
				echo '</tbody>
				</table>
				';
			}
			return count($failed);
		}
	}

	public function check_issue_inline( $issue, $code = 0 ) {
		if( $this->debug ) echo "<p>".__METHOD__.": ".
			(isset($issue['CODE']) ? $issue['CODE'] : '')." ".
			(isset($issue['LEVEL']) ? $issue['LEVEL'] : '')." $code</p>";
		if( $issue['LEVEL'] == 'group') {
			$failed = 0;
			foreach($issue['ISSUES'] as $sub_issue) {
				$result = $this->check_issue_inline( $sub_issue, $code );
				if( isset($issue['STOPONERROR']) && $issue['STOPONERROR'] && $result > 0 ) {
					return $result;					
				} else {
					$failed += $result;
				}
			}
			return $failed;
		} else {
			$check_function = $issue['CHECK'];
			$failed = method_exists($this, $check_function) ? $this->$check_function( $code ) : false;
		
			if( is_array($failed) && count($failed) > 0 ) {
				
				if( isset($issue['AUTOFIX'])) {	//! AUTOFIX
					foreach( $failed as $row ) {
						foreach( $issue['COLUMNS'] as $key => $format ) {
							if( !empty($row[$key]))
								$_GET[$key] = $row[$key];
						}

						$fix = reset($issue['FIX']);
						$fix_function = $fix["FUNCTION"];
						if( method_exists($this, $fix_function))
							$this->$fix_function();
					}
					
				} else {
					$output = '<div class="alert alert-'.$issue['LEVEL'].' alert-dismissable">
					<button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
					<h4><span class="glyphicon glyphicon-warning-sign"></span> '.$issue['TITLE'].'</h4>
					<p>'.$issue['DESCRIPTION'].'</p>
					';
	
					$output .= '<table class="display table table-striped table-condensed table-bordered table-hover">
					<thead>
					<tr class="exspeedite-bg">';
					foreach( $issue['COLUMNS'] as $key => $format ) {
						if( ! isset($format['hidden']) || ! $format['hidden'] )
							$output .= '<th>'.$format['label'].'</th>';
					}
					
					if( isset($issue['FIX']) && count($issue['FIX']) > 0 ) {
						$output .= '<th>Your Options To Repair</th>';
					}
					
					$output .= '</tr>
					</thead>
					<tbody>';
					foreach( $failed as $row ) {
						$output .= '<tr>
						';
						$params_arr = array();
						foreach( $issue['COLUMNS'] as $key => $format ) {
							if( ! isset($format['hidden']) || ! $format['hidden'] ) {
								$output .= '<td'.(isset($format['align']) && $format['align'] <> '' ? ' class="text-'.$format['align'].'"' :'').'>';
								switch( $format['format'] ) {
									case 'load': $output .= $this->vl($row[$key]); break;
									case 'load status': $output .= $this->load_stat($row[$key]); break;
									case 'shipment': $output .= $this->vs($row[$key]); break;
									case 'shipment status': $output .= $this->shipment_stat($row[$key]); break;
									case 'stop status': $output .= $this->stop_stat($row[$key]); break;
									default: $output .= $row[$key];
								}
								$output .= '</td>
								';
							}
							$params_arr[] .=$key.'='.$row[$key];
						}
						$params_str = implode('&', $params_arr);
						$url = $_SERVER["REQUEST_URI"].(strpos($_SERVER["REQUEST_URI"], '?') ? '&' : '?').'XYZZY&'.$params_str;
						
						if( isset($issue['FIX']) && count($issue['FIX']) > 0 ) {
							$output .= '<td>
							<div class="btn-group">';
							foreach($issue['FIX'] as $fix_key => $fix) {
								//! POSSIBLE FIXES - CONDITION = checking function
								$cond_function = isset($fix['CONDITION']) ? $fix['CONDITION'] : false;
								$cond = $cond_function && method_exists($this, $cond_function) ?
									$this->$cond_function( $row ) : true;
								if( $cond )
									$output .= '<a class="btn btn-sm btn-'.(isset($fix['LEVEL']) ? $fix['LEVEL'] : 'default').'"  href="'.$url.
								'&REPAIR_FUNCTION='.$fix['FUNCTION'].'"><span class="glyphicon glyphicon-wrench"></span> '.$fix['DESCRIPTION'].'</a>';
							}
							$output .= '</div>
							</td>
							';
						}
						
						$output .= '</tr>
						';
					}
					$output .= '</tbody>
					</table>
					</div>
					';
					echo $output;
					
					$output = preg_replace('/\<a class=[^\>]*\>/', '', $output);
					$output = preg_replace('/\<\/a\>/', '', $output);
					
					$email = sts_email::getInstance($this->database, $this->debug);
					$email->send_alert('sts_repair > check_issue_inline: found issue: code = '.$code.
					'<br>'.$output.
					'<br>'.$email->load_stops($_GET['CODE']).
					'<br>'.$email->load_history($_GET['CODE']), EXT_ERROR_ERROR );
				}
			}
			return count($failed);
		}
	}

	//! Repair Functions -----------------------------------------------------------------------

	// Update shipment to add it back into a load
	public function fix_load_add_missing_shipment() {
		if( $this->debug ) echo "<p>".__METHOD__.": ".$_GET["LOAD_CODE"]." ".$_GET["SHIPMENT_CODE"]."</p>";
		$result = false;
		if( isset($_GET["LOAD_CODE"]) && isset($_GET["SHIPMENT_CODE"]) ) {
			$shipment_table = sts_shipment::getInstance($this->database, $this->debug);
			$result = $shipment_table->update( $_GET["SHIPMENT_CODE"], array('LOAD_CODE' => $_GET["LOAD_CODE"]), false);
			$shipment_table->add_shipment_status($_GET["SHIPMENT_CODE"], "Repair/Add to load ".$_GET["LOAD_CODE"], false, false, $_GET["LOAD_CODE"]);
		}
		return $result;
	}

	// Remove stops that point to a shipment being in a load.
	public function fix_load_remove_stops() {
		if( $this->debug ) echo "<p>".__METHOD__.": ".$_GET["LOAD_CODE"]." ".$_GET["SHIPMENT_CODE"]."</p>";
		$result = false;
		if( isset($_GET["LOAD_CODE"]) && isset($_GET["SHIPMENT_CODE"]) ) {
			$stop_table = sts_stop::getInstance($this->database, $this->debug);
			$result = $stop_table->delete_row( "LOAD_CODE = ".$_GET["LOAD_CODE"]." AND SHIPMENT = ".$_GET["SHIPMENT_CODE"] );
			if( $result ) $stop_table->renumber( $_GET["LOAD_CODE"] );
		}
		return $result;
	}

	// Add stops that point to a shipment being in a load.
	public function fix_load_add_missing_stops() {
		if( $this->debug ) echo "<p>".__METHOD__.": ".$_GET["LOAD_CODE"]." ".$_GET["SHIPMENT_CODE"]."</p>";
		$result = false;
		if( isset($_GET["LOAD_CODE"]) && isset($_GET["SHIPMENT_CODE"]) ) {
			$stop_table = sts_stop::getInstance($this->database, $this->debug);
			
			// Get max sequence number
			$check1 = $stop_table->fetch_rows( "LOAD_CODE = ".$_GET["LOAD_CODE"], "COUNT(*) AS MAX_SEQ");
			if( is_array($check1) && count($check1) == 1 && isset($check1[0]["MAX_SEQ"])) {
				$max_seq = $check1[0]["MAX_SEQ"];
				
				// Check the last stop is a repositioning stop
				$check2 = $stop_table->fetch_rows( "LOAD_CODE = ".$_GET["LOAD_CODE"].
					" AND SEQUENCE_NO = (SELECT MAX(SEQUENCE_NO)
						FROM EXP_STOP WHERE LOAD_CODE = ".$_GET["LOAD_CODE"].")", "STOP_CODE, STOP_TYPE");
				
				if( is_array($check2) && count($check2) == 1 && isset($check2[0]["STOP_TYPE"])) {
					if( $check2[0]["STOP_TYPE"] == 'stop') {
						$dummy = $stop_table->update($check2[0]["STOP_CODE"], array("SEQUENCE_NO" => ($max_seq+2)));
						$pick_seq = $max_seq;
						$drop_seq = $max_seq + 1;
					} else {
						$pick_seq = $max_seq + 1;
						$drop_seq = $max_seq + 2;
					}
					
					$pick = array( "LOAD_CODE" => $_GET["LOAD_CODE"],
						"SHIPMENT" => $_GET["SHIPMENT_CODE"],
						"SEQUENCE_NO" => $pick_seq,
						"CURRENT_STATUS" => $stop_table->behavior_state["entry"]				
						);
					
					$check3 = $stop_table->database->get_multiple_rows("
						SELECT SEQUENCE_NO
						FROM EXP_SHIPMENT_LOAD
						WHERE SHIPMENT_CODE = ".$_GET["SHIPMENT_CODE"]."
						AND LOAD_CODE <> ".$_GET["LOAD_CODE"] );
						
					if( is_array($check3) && count($check3) > 0 ) {
						$pick["STOP_TYPE"] = "pickdock";
					} else {
						$pick["STOP_TYPE"] = "pick";
					}
					
					$drop = array( "LOAD_CODE" => $_GET["LOAD_CODE"],
						"SHIPMENT" => $_GET["SHIPMENT_CODE"],
						"SEQUENCE_NO" => $drop_seq,
						"CURRENT_STATUS" => $stop_table->behavior_state["entry"],
						"STOP_TYPE" => "drop"				
						);
					
					$result = $stop_table->add($pick);
					if( $result )
						$result = $stop_table->add($drop);
						
					if( $result )
						$stop_table->renumber( $_GET["LOAD_CODE"] );
				}
			}
		}
		return $result;
	}

	// Update shipment remove it from a load
	public function fix_load_remove_shipment() {
		if( $this->debug ) echo "<p>".__METHOD__.": ".$_GET["LOAD_CODE"]." ".$_GET["SHIPMENT_CODE"]."</p>";
		$result = false;
		if( isset($_GET["LOAD_CODE"]) && isset($_GET["SHIPMENT_CODE"]) ) {
			$shipment_table = sts_shipment::getInstance($this->database, $this->debug);
			$result = $shipment_table->update( $_GET["SHIPMENT_CODE"], array('LOAD_CODE' => 0), false);
			$shipment_table->add_shipment_status($_GET["SHIPMENT_CODE"], "Repair/Remove from load ".$_GET["LOAD_CODE"], false, false, 0);
		}
		return $result;
	}

	// Update shipment to be delivered
	public function fix_load_deliver_shipment() {
		if( $this->debug ) echo "<p>".__METHOD__.": ".$_GET["LOAD_CODE"]." ".$_GET["SHIPMENT_CODE"]."</p>";
		$result = false;
		if( isset($_GET["LOAD_CODE"]) && isset($_GET["SHIPMENT_CODE"]) ) {
			$shipment_table = sts_shipment::getInstance($this->database, $this->debug);
			$result = $shipment_table->update( $_GET["SHIPMENT_CODE"], array('CURRENT_STATUS' => $shipment_table->behavior_state["dropped"]), false);
			$shipment_table->add_shipment_status($_GET["SHIPMENT_CODE"], "Repair/deliver shipment", false, false, $_GET["LOAD_CODE"]);
		}
		return $result;
	}

	// Update stop to be complete
	public function fix_load_complete_stop() {
		if( $this->debug ) echo "<p>".__METHOD__.": ".$_GET["LOAD_CODE"]." ".$_GET["SEQUENCE_NO"]."</p>";
		$result = false;
		if( isset($_GET["LOAD_CODE"]) && isset($_GET["SEQUENCE_NO"]) ) {
			$stop_table = sts_stop::getInstance($this->database, $this->debug);
			$result = $stop_table->completed( $_GET["LOAD_CODE"], $_GET["SEQUENCE_NO"] );
		}
		return $result;
	}

	// Update load current_status to match stop
	public function fix_load_wrong_state_for_stop() {
		if( $this->debug ) echo "<p>".__METHOD__.": ".$_GET["LOAD_CODE"]."</p>";
		$result = false;
		if( isset($_GET["LOAD_CODE"]) ) {
			$load_stop_state = $load_table->database->get_multiple_rows("
				SELECT LOAD_CODE, LOAD_STATUS, CURRENT_STOP, STOP_TYPE
				FROM (SELECT LOAD_CODE, CURRENT_STATUS AS LOAD_STATUS, CURRENT_STOP,
				(SELECT STOP_TYPE FROM EXP_STOP
				WHERE EXP_STOP.LOAD_CODE = EXP_LOAD.LOAD_CODE
				AND SEQUENCE_NO = CURRENT_STOP) AS STOP_TYPE
				FROM EXP_LOAD
				WHERE CURRENT_STATUS IN (".$load_table->behavior_state['arrive cons'].", ".
					$load_table->behavior_state['arrive shipper'].", ".
					$load_table->behavior_state['arrshdock'].", ".
					$load_table->behavior_state['arrrecdock'].", ".
					$load_table->behavior_state['arrive stop'].") 
				AND LOAD_CODE = ".$_GET["LOAD_CODE"]."
				) X
				WHERE (LOAD_STATUS = ".$load_table->behavior_state['arrive cons']." AND STOP_TYPE <> 'drop')
				OR (LOAD_STATUS = ".$load_table->behavior_state['arrive shipper']." AND STOP_TYPE <> 'pick')
				OR (LOAD_STATUS = ".$load_table->behavior_state['arrshdock']." AND STOP_TYPE <> 'pickdock')
				OR (LOAD_STATUS = ".$load_table->behavior_state['arrrecdock']." AND STOP_TYPE <> 'dropdock')
				OR (LOAD_STATUS =  ".$load_table->behavior_state['arrive stop']." AND STOP_TYPE <> 'stop')
			" );
				
			if( is_array($load_stop_state) && count($load_stop_state) > 0 ) {
				foreach( $load_stop_state as $row ) {
					if( $row['STOP_TYPE'] == 'pick' ) 
						$new_status = $load_table->behavior_state['arrive shipper'];
					else if( $row['STOP_TYPE'] == 'drop' ) 
						$new_status = $load_table->behavior_state['arrive cons'];
					else if( $row['STOP_TYPE'] == 'pickdock' ) 
						$new_status = $load_table->behavior_state['arrshdock'];
					else if( $row['STOP_TYPE'] == 'dropdock' ) 
						$new_status = $load_table->behavior_state['arrrecdock'];
					else if( $row['STOP_TYPE'] == 'stop' ) 
						$new_status = $load_table->behavior_state['arrive stop'];
					
					if( $new_status > 0 )
						$result = $load_table->update_row("LOAD_CODE = ".$row['LOAD_CODE'],
							array( array("field" => "CURRENT_STATUS", "value" => $new_status) ) );
						$load_table->add_load_status($row['LOAD_CODE'],
							"Repair status to ".$load_table->state_name[$new_status] );
				}
			}
		}
		return $result;
	}

	// Update stop to be complete
	public function fix_complete_load() {
		if( $this->debug ) echo "<p>".__METHOD__.": ".$_GET["LOAD_CODE"]."</p>";
		$result = false;
		if( isset($_GET["LOAD_CODE"]) ) {
			$load_table = sts_load::getInstance($this->database, $this->debug);
			$result = $load_table->load_complete( $_GET["LOAD_CODE"], true );
		}
		return $result;
	}

	// Update stop to be complete
	public function fix_disp_load_shipments_ready() {
		if( $this->debug ) echo "<p>".__METHOD__.": ".$_GET["LOAD_CODE"]." ".$_GET["SHIPMENT_CODE"]." ".$_GET["PICK_STATUS"]." ".$_GET["DROP_STATUS"]."</p>";
		$result = false;
		if( isset($_GET["LOAD_CODE"]) && isset($_GET["SHIPMENT_CODE"]) ) {
			$shipment_table = sts_shipment::getInstance($this->database, $this->debug);
			$stop_table = sts_stop::getInstance($this->database, $this->debug);
			
			if( $stop_table->state_behavior[$_GET['DROP_STATUS']] == 'complete')
				$behavior = 'dropped';
			else if( $stop_table->state_behavior[$_GET['PICK_STATUS']] == 'complete')
				$behavior = 'picked';
			else
				$behavior = 'dispatch';

			$result = $shipment_table->update( $_GET["SHIPMENT_CODE"],
				array('CURRENT_STATUS' => $shipment_table->behavior_state[$behavior]), false);
			$shipment_table->add_shipment_status($_GET["SHIPMENT_CODE"], "Repair status to $behavior", false, false, $_GET["LOAD_CODE"]);
		}
		return $result;
	}

	// Update cached revenue
	public function fix_load_revenue() {
		if( $this->debug ) echo "<p>".__METHOD__.": ".$_GET["LOAD_CODE"]."</p>";
		$result = false;
		if( isset($_GET["LOAD_CODE"]) ) {
			$result = $this->database->get_one_row("
				UPDATE EXP_LOAD
					SET LOAD_REVENUE = LOAD_REVENUE_CUR(LOAD_CODE, CURRENCY)
					WHERE LOAD_CODE = ".$_GET["LOAD_CODE"] );
		}
		return $result;
	}

	// Update cached expense
	public function fix_load_expense() {
		if( $this->debug ) echo "<p>".__METHOD__.": ".$_GET["LOAD_CODE"]."</p>";
		$result = false;
		if( isset($_GET["LOAD_CODE"]) ) {
			$result = $this->database->get_one_row("
				UPDATE EXP_LOAD
					SET LOAD_EXPENSE = LOAD_EXPENSE_CUR(LOAD_CODE, CURRENCY)
					WHERE LOAD_CODE = ".$_GET["LOAD_CODE"] );
		}
		return $result;
	}

	//! Top Level Repair Functions -----------------------------------------------------

	public function repair_issue() {
		if( $this->debug && isset($_GET["REPAIR_FUNCTION"])) {
			echo "<p>".__METHOD__.":</p><pre>";
			var_dump($_GET["REPAIR_FUNCTION"]);
			echo "</pre>";			
		}
		if( isset($_GET["REPAIR_FUNCTION"]) && method_exists($this, $_GET["REPAIR_FUNCTION"]) ) {
			$repair_function = $_GET["REPAIR_FUNCTION"];
			$result = $this->$repair_function();
			
			$start = strpos($_SERVER["REQUEST_URI"], 'exp_');
			$length = strpos($_SERVER["REQUEST_URI"], '&XYZZY') - $start;
			
			$url = substr($_SERVER["REQUEST_URI"], $start, $length);
			if( ! $this->debug )
				reload_page ( $url );
		}	
	}

}


//! Descriptive Structures -----------------------------------------------------------------------

$sts_issue_empty_loads = array( //! $sts_issue_empty_loads
	'CODE' => 'DEL_EMPTY',
	'LEVEL' => 'warning',
	'TITLE' => 'empty load records (Not an error)',
	'CHECK' => 'check_empty_load_record',
	'COLUMNS' => array(
		'LOAD_CODE' => array( 'label' => 'Trip/Load#', 'format' => 'load' ),
		'CURRENT_STATUS' => array( 'label' => 'Status', 'format' => 'load status' ),
	)
);



$sts_issue_load_missing_shipments = array( //! $sts_issue_load_missing_shipments
	'CODE' => 'MISSING_SHIPMENTS',
	'LEVEL' => 'danger',
	'TITLE' => 'This Load Has Missing Shipments',
	'DESCRIPTION' => 'There is an inconsistency in the database. This load has stops for the following shipment(s), but the shipment(s) do not appear to be part of the load',
	'CHECK' => 'check_load_missing_shipments',
	'COLUMNS' => array(
		'LOAD_CODE' => array( 'label' => 'Trip/Load#', 'format' => 'load', 'hidden' => true ),
		'LOAD_STATUS' => array( 'label' => 'Status', 'format' => 'load status', 'hidden' => true ),
		'SHIPMENT_CODE' => array( 'label' => 'Shipment#', 'format' => 'shipment' ),
		'SHIPMENT_STATUS' => array( 'label' => 'Status', 'format' => 'shipment status' ),
	),
	'FIX' => array(
		'ADD_SHIPMENT' => array(
			'DESCRIPTION' => 'Add Shipment To Load',
			'FUNCTION' => 'fix_load_add_missing_shipment',
			'LEVEL' => 'success',
			'CONDITION' => 'check_onload_before',
		),
		'REMOVE_STOPS' => array(
			'DESCRIPTION' => 'Remove Stops From Load',
			'FUNCTION' => 'fix_load_remove_stops',
			'LEVEL' => 'default',
		),
	)
);

$sts_issue_load_missing_stops = array(
	'CODE' => 'MISSING_STOPS',
	'LEVEL' => 'danger',
	'TITLE' => 'This Load Has Missing Stops',
	'DESCRIPTION' => 'There is an inconsistency in the database. This load has the following shipment(s), but there are no corresponding stops.',
	'CHECK' => 'check_load_missing_stops',
	'COLUMNS' => array(
		'LOAD_CODE' => array( 'label' => 'Trip/Load#', 'format' => 'load', 'hidden' => true ),
		'LOAD_STATUS' => array( 'label' => 'Status', 'format' => 'load status', 'hidden' => true ),
		'SHIPMENT_CODE' => array( 'label' => 'Shipment#', 'format' => 'shipment' ),
		'SHIPMENT_STATUS' => array( 'label' => 'Status', 'format' => 'shipment status' ),
	),
	'FIX' => array(
		'ADD_SHIPMENT' => array(
			'DESCRIPTION' => 'Add Stops To Load',
			'FUNCTION' => 'fix_load_add_missing_stops',
			'LEVEL' => 'success',
		),
		'REMOVE_STOPS' => array(
			'DESCRIPTION' => 'Remove Shipment From Load',
			'FUNCTION' => 'fix_load_remove_shipment'
		),
	)
);


$sts_issue_load_incomplete_stops = array(
	'CODE' => 'STOP_NOT_COMPLETED',
	'LEVEL' => 'danger',
	'TITLE' => 'This Load Has Stops That Should Be Completed',
	'DESCRIPTION' => 'There is an inconsistency in the database. This load has stops(s) that should be completed.',
	'CHECK' => 'check_load_incomplete_stops',
	'COLUMNS' => array(
		'LOAD_CODE' => array( 'label' => 'Trip/Load#', 'format' => 'load', 'hidden' => true ),
		'LOAD_STATUS' => array( 'label' => 'Load Status', 'format' => 'load status', 'hidden' => true ),
		'STOP_CODE' => array( 'label' => 'Stop Code', 'format' => 'number' ),
		'STOP_TYPE' => array( 'label' => 'Stop Type', 'format' => 'text' ),
		'SEQUENCE_NO' => array( 'label' => 'Sequence #', 'format' => 'number', 'align' => 'right' ),
		'CURRENT_STATUS' => array( 'label' => 'Stop Status', 'format' => 'stop status' ),
	),
	'AUTOFIX' => true,	// Don't bother to ask user, must be only one fix
	'FIX' => array(
		'COMPLETE_SHIPMENT' => array(
			'DESCRIPTION' => 'Mark As Complete',
			'FUNCTION' => 'fix_load_complete_stop',
			'LEVEL' => 'success',
		),
	)
);

$sts_issue_load_incomplete_shipments = array( //! $sts_issue_load_incomplete_shipments
	'CODE' => 'SHIPMENT_NOT_COMPLETED',
	'LEVEL' => 'danger',
	'TITLE' => 'This Load Has Shipments That Should Be Completed',
	'DESCRIPTION' => 'There is an inconsistency in the database. This load has shipment(s) that should be completed.',
	'CHECK' => 'check_load_incomplete_shipments',
	'COLUMNS' => array(
		'LOAD_CODE' => array( 'label' => 'Trip/Load#', 'format' => 'load', 'hidden' => true ),
		'LOAD_STATUS' => array( 'label' => 'Load Status', 'format' => 'load status', 'hidden' => true ),
		'SHIPMENT_CODE' => array( 'label' => 'Shipment', 'format' => 'shipment' ),
		'CURRENT_STATUS' => array( 'label' => 'Shipment Status', 'format' => 'shipment status' ),
	),
	'AUTOFIX' => true,	// Don't bother to ask user, must be only one fix
	'FIX' => array(
		'COMPLETE_SHIPMENT' => array(
			'DESCRIPTION' => 'Mark As Delivered',
			'FUNCTION' => 'fix_load_deliver_shipment',
			'LEVEL' => 'success',
		),
	)
);

$sts_issue_load_load_wrong_state_for_stop = array(
	'CODE' => 'LOAD_STOP_STATE',
	'LEVEL' => 'danger',
	'TITLE' => 'This Load Has The Wrong Status Code',
	'DESCRIPTION' => 'There is an inconsistency in the database. This load is in a status that does not match the current stop.',
	'CHECK' => 'check_load_wrong_state_for_stop',
	'COLUMNS' => array(
		'LOAD_CODE' => array( 'label' => 'Trip/Load#', 'format' => 'load', 'hidden' => true ),
		'LOAD_STATUS' => array( 'label' => 'Load Status', 'format' => 'load status' ),
		'CURRENT_STOP' => array( 'label' => 'Current Stop', 'format' => 'number' ),
		'NUM_STOPS' => array( 'label' => 'Number Of Stops', 'format' => 'number' ),
		'STOP_TYPE' => array( 'label' => 'Stop Type', 'format' => 'text' ),
		'POSSIBLE_STATUS' => array( 'label' => 'Recommended Status', 'format' => 'load status' ),
	),
	'FIX' => array(
		'COMPLETE_SHIPMENT' => array(
			'DESCRIPTION' => 'Correct Load Status',
			'FUNCTION' => 'fix_load_wrong_state_for_stop'
		),
	)
);

$sts_issue_load_not_complete = array(	//!WIP
	'CODE' => 'LOAD_NOT_COMPLETE',
	'LEVEL' => 'danger',
	'TITLE' => 'This Load Should Be Complete',
	'DESCRIPTION' => 'There is an inconsistency in the database. This load is not complete, however all the stops are completed. If you choose to complete the load, it will also free up the driver and update the trailer location to its final destination.',
	'CHECK' => 'check_load_not_complete',
	'COLUMNS' => array(
		'LOAD_CODE' => array( 'label' => 'Trip/Load#', 'format' => 'load', 'hidden' => true ),
		'STATUS_STATE' => array( 'label' => 'Load Status', 'format' => 'text' ),
		'CURRENT_STOP' => array( 'label' => 'Current Stop', 'format' => 'number' ),
		'NUM_STOPS' => array( 'label' => 'Number Of Stops', 'format' => 'number' ),
		'COMPLETE_STOPS' => array( 'label' => 'Complete Stops', 'format' => 'number' ),
	),
	'FIX' => array(
		'COMPLETE_LOAD' => array(
			'DESCRIPTION' => 'Complete Load',
			'FUNCTION' => 'fix_complete_load'
		),
	)
);

$sts_issue_disp_load_shipments_ready = array(	//! $sts_issue_disp_load_shipments_ready
	'CODE' => 'DISP_LOAD_SHIP_RDY',
	'LEVEL' => 'danger',
	'TITLE' => 'Dispatched Load With Shipments In Ready State',
	'DESCRIPTION' => 'There is an inconsistency in the database. This load has been dispatched, however there are shipment(s) still in Ready (to be dispatched) status. The fix will set the shipment to the correct state (Dispatched, Dispatched, Delivered) based upon the pick status and drop status.',
	'CHECK' => 'check_disp_load_shipments_ready',
	'COLUMNS' => array(
		'LOAD_CODE' => array( 'label' => 'Trip/Load#', 'format' => 'load', 'hidden' => true ),
		'LOAD_STATUS' => array( 'label' => 'Load Status', 'format' => 'load status' ),
		'SHIPMENT_CODE' => array( 'label' => 'Shipment', 'format' => 'shipment' ),
		'SHIPMENT_STATUS' => array( 'label' => 'Shipment Status', 'format' => 'shipment status' ),
		'PICK_STATUS' => array( 'label' => 'Pick Status', 'format' => 'stop status' ),
		'DROP_STATUS' => array( 'label' => 'Drop Status', 'format' => 'stop status' ),
	),
	'AUTOFIX' => true,	// Don't bother to ask user, must be only one fix
	'FIX' => array(
		'MAKE_DISPATCHED' => array(
			'DESCRIPTION' => 'Correct shipment status',
			'FUNCTION' => 'fix_disp_load_shipments_ready'
		),
	)
);

$sts_issue_load_revenue_incorrect = array(	//!$sts_issue_load_revenue_incorrect
	'CODE' => 'LOAD_REVENUE_INCORRECT',
	'LEVEL' => 'danger',
	'TITLE' => 'Load Cached Revenue Incorrect',
	'DESCRIPTION' => 'There is an inconsistency in the database. Need to refresh the cached revenue.',
	'CHECK' => 'check_revenue_incorrect',
	'COLUMNS' => array(
		'LOAD_CODE' => array( 'label' => 'Trip/Load#', 'format' => 'load' ),
		'LOAD_REVENUE' => array( 'label' => 'Cached Revenue', 'format' => 'number' ),
		'ACTUAL' => array( 'label' => 'Actual Revenue', 'format' => 'number' ),
	),
	'AUTOFIX' => true,	// Don't bother to ask user, must be only one fix
	'FIX' => array(
		'UPDATE_REVENUE' => array(
			'DESCRIPTION' => 'Update cached load revenue',
			'FUNCTION' => 'fix_load_revenue'
		),
	)
);

$sts_issue_load_expense_incorrect = array(	//!$sts_issue_load_revenue_incorrect
	'CODE' => 'LOAD_EXPENSE_INCORRECT',
	'LEVEL' => 'danger',
	'TITLE' => 'Load Cached Expense Incorrect',
	'DESCRIPTION' => 'There is an inconsistency in the database. Need to refresh the cached expense.',
	'CHECK' => 'check_expense_incorrect',
	'COLUMNS' => array(
		'LOAD_CODE' => array( 'label' => 'Trip/Load#', 'format' => 'load' ),
		'LOAD_EXPENSE' => array( 'label' => 'Cached Expense', 'format' => 'number' ),
		'ACTUAL' => array( 'label' => 'Actual Expense', 'format' => 'number' ),
	),
	'AUTOFIX' => true,	// Don't bother to ask user, must be only one fix
	'FIX' => array(
		'UPDATE_REVENUE' => array(
			'DESCRIPTION' => 'Update cached load expense',
			'FUNCTION' => 'fix_load_expense'
		),
	)
);

$sts_issue_all = array(
	'CODE' => 'ALL',
	'LEVEL' => 'group',
	'TITLE' => 'all tests',
	'ISSUES' => array(
		$sts_issue_empty_loads,
		$sts_issue_load_missing_shipments,
		$sts_issue_load_incomplete_stops,
		$sts_issue_load_incomplete_shipments
	)
);

$sts_issue_inline_load = array(
	'CODE' => 'INLINE_LOAD',
	'LEVEL' => 'group',
	'TITLE' => 'all tests for load',
	'ISSUES' => array(
		//$sts_issue_load_load_wrong_state_for_stop,	// Got issues with this test
		$sts_issue_load_missing_shipments,
		$sts_issue_load_missing_stops,
		$sts_issue_load_incomplete_stops,
		$sts_issue_load_incomplete_shipments,
		$sts_issue_load_not_complete,
		$sts_issue_disp_load_shipments_ready,
		$sts_issue_load_revenue_incorrect,
		$sts_issue_load_expense_incorrect
	)
);



?>
