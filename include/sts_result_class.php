<?php

// $Id: sts_result_class.php 5151 2023-07-06 18:00:55Z dev $
// Result class - meant to display a result in tabular form
// Buggered up by webwing.

// no direct access
defined('_STS_INCLUDE') or die('Restricted access');

require_once( "sts_session_class.php" );
require_once( "sts_setting_class.php" );

class sts_result {
	
	private $table;
	private $table2 = false;
	private $table_name;
	private $match;
	private $result;
	private $count_rows;
	private $keys;
	private $count_columns;
	private $valid_alignments = array('left', 'right', 'center');
	private $valid_formats = array('text', 'decrypt', 'groups', 'endorsements',
		'email', 'phone', 'bool', 'valid', 'date', 'date-s', 'states',
		'datetime', 'timestamp', 'timestamp-s', 'due', 'timestamp-s-actual', 'timestamp-s-eta',
		'image', 'link', 'num0', 'numb', 'num0stop', 'num2', 'num0nc', 'num2nc', 'edistatus',
		'carrier_pick');
	private $debug = false;

	// Profiling
	private $profiling = false;
	private $timer;
	private $time_render;
	private $time_render_dropdown;
	private $time_format;
	private $setting_table;
	private $multi_company;
	private $multi_currency;
	private $export_qb;
	private $destination;
	private $cms_enabled;
	
	public function __construct( $table = false, $match = false, $debug = false, $profiling = false ) {
		$this->debug = $debug;
		$this->table = $table;
		$this->table_name = $this->table->get_table_name();
		$this->match = $match;
		$this->profiling = $profiling;
		$this->setting_table = sts_setting::getInstance( $this->table->database, $this->debug );
		$this->multi_company = ($this->setting_table->get( 'option', 'MULTI_COMPANY' ) == 'true');
		$this->multi_currency = ($this->setting_table->get( 'option', 'MULTI_CURRENCY' ) == 'true');
		$this->export_qb = ($this->setting_table->get( 'api', 'EXPORT_QUICKBOOKS' ) == 'true');
		$this->destination = $this->export_qb ? 'Quickbooks' : 'Accounting';
		$this->cms_enabled = $this->setting_table->get("option", "CMS_ENABLED") == 'true';
		
		if( $this->debug ) echo "<p>Create sts_result with $this->count_rows rows.</p>";
	}

	function __destruct() {
		if( $this->debug ) echo "<p>Destroy sts_result</p>";
	}

	private function heading( $key, $layout ) {
		return is_array($layout) && isset($layout[$key]) && isset($layout[$key]['label']) ?
			$layout[$key]['label'] : $key;
	}
	
	public function set_alt_table( $table ) {
		$this->table2 = $table;
	}
	
	public function last_result() {
		return $this->result;
	}
	
	public static function formatBytes($size, $precision = 2) { 
	    $base = log($size, 1024);
	    $suffixes = array('B', 'KB', 'MB', 'GB', 'TB');   
	
	    return $size == 0 ? '0 B' : round(pow(1024, $base - floor($base)), $precision) .' '. $suffixes[floor($base)];
	} 
	
	private function align( $key, $layout, $extras = false ) {
		return is_array($layout) && isset($layout[$key]) &&
			isset($layout[$key]['align']) && in_array($layout[$key]['align'], $this->valid_alignments) ?
			' class="text-'.$layout[$key]['align'].
			($extras ? ' '.$extras : '').'"' : ($extras ? ' class="'.$extras.'"' : '');
	}
	
	public static function chartotime( $str ) {
		return isset($str) && strlen($str) == 4 ? substr($str, 0,2).':'.substr($str, 2,2) : '';
	}
	
	public static function duedate( $date1, $option, $time1, $time2, $separator = '<br>' ) {
		if( isset($date1) ) {
			$formatted = date("m/d", strtotime($date1));
			if( isset($option) ) {
				switch($option) {
					case 'At':
					case 'By':
					case 'After':
						if( isset($time1) ) {
							$formatted .= $separator.$option.' '.sts_result::chartotime($time1);
						}
						break;
					case 'Between':
						if( isset($time1) && isset($time2)) {
							$formatted .= $separator.sts_result::chartotime($time1).' - '.sts_result::chartotime($time2);
						}
						break;
					case 'ASAP':
						$formatted .= $separator.'ASAP';
						break;
					case 'FCFS':
						$formatted .= $separator.'FCFS';
						break;
				}
			}
		} else
			$formatted = '';
			
		return $formatted;
	}
	
private function actualdate( $actual, $date1, $option, $time1, $time2 ) {
	$formatted = isset($actual) && $actual <> '' ? date("m/d/Y H:i", strtotime($actual)) : '';
	
	if( isset($date1) && isset($option) ) {
		switch($option) {
			case 'At':
			case 'By':
				if( isset($time1) ) {
					$time1 = strtotime($date1.' '.$this->chartotime($time1));
					if( strtotime($actual) > $time1 )
						$formatted = '<span class="bg-danger bg-lg">'.$formatted.'</span>';
					else if( strtotime($actual) <= $time1 )
						$formatted = '<span class="bg-success bg-lg">'.$formatted.'</span>';
				}
				break;
			case 'After':
				if( isset($time1) ) {
					$time1 = strtotime($date1.' '.$this->chartotime($time1));
					if( strtotime($actual) > $time1 )
						$formatted = '<span class="bg-success bg-lg">'.$formatted.'</span>';
					else if( strtotime($actual) <= $time1 )
						$formatted = '<span class="bg-danger bg-lg">'.$formatted.'</span>';
				}
				break;
			case 'Between':
				if( isset($time1) && isset($time2)) {
					$time1 = strtotime($date1.' '.$this->chartotime($time1));
					$time2 = strtotime($date1.' '.$this->chartotime($time2));
					if( strtotime($actual) >= $time1 && strtotime($actual) <= $time2 )
						$formatted = '<span class="bg-success bg-lg">'.$formatted.'</span>';
					else
						$formatted = '<span class="bg-danger bg-lg">'.$formatted.'</span>';
				}
				break;
			case 'ASAP':
			case 'FCFS':
				break;
		}
	}

	return $formatted;	
}
	
	private function format( $pk, $key, $value, $layout, $row ) {
		global $sts_opt_groups, $sts_license_endorsements;
		
		if( $this->debug )
			echo "<p>".__METHOD__.": entry, pk = $pk, key = $key</p>";

		if( $this->profiling ) {
			$format_start = $this->timer->split();
		}
		if( is_array($layout) && isset($layout[$key]) ) {
			$format_str = isset($layout[$key]['format2']) ? $layout[$key]['format2'] : 
				(isset($layout[$key]['format']) ? $layout[$key]['format'] : '');
			
			if( $this->debug ) {
				if( $format_str == 'image' )
					echo "<p>sts_result > format: $key, (".strlen($value).") ".substr($value, 0, 30)." ... ".substr($value, -10)."</p>".
					'<img src="'.($value ? $value : 'images/no-image.jpg').'" width="200" height="200" class="img-responsive img-thumbnail">';
				else
					echo "<p>sts_result > format $key, $value, $format_str, ".(isset($layout[$key]['link']) ? $layout[$key]['link'] : '')."</p>";
			}
		
			if( $this->debug )
				echo "<p>".__METHOD__.": XXX key = $key format=".$layout[$key]['format']." format_str=$format_str value=$value</p>";
			
			if( in_array($format_str, $this->valid_formats) ) {
				
				switch( $format_str ) {
					case 'text':	//! Basic text
						if( isset($layout[$key]['group']) && is_array($layout[$key]['group']) ) {
							$formatted_items = array();
							foreach( $layout[$key]['group'] as $group_item) {
								if( isset($row[$group_item]) )
									$formatted_items[] = mb_convert_encoding($row[$group_item], 'UTF-8', 'UTF-8');
								$glue =  isset($layout[$key]['glue']) ? $layout[$key]['glue'] : '<br>';
								$formatted = implode($glue, $formatted_items);
							}
						} else if( ! empty($value) ) {
							$formatted = isset($value) ? mb_convert_encoding($value, 'UTF-8', 'UTF-8') : '';
						} else
							$formatted = '';
						break;
					
					case 'image':	//! Image placeholder
						$formatted = '
			<a href="'.($value ? $value : 'images/no-image.jpg').'" target="_blank">
			<img src="'.($value ? $value : 'images/no-image.jpg').'" width="200" height="200" class="img-responsive img-thumbnail"></a>';
						//'<img src="images/image_icon.png" alt="image_icon" height="24">';
						break;
					
					case 'decrypt':	//! Decrypt before showing
						$formatted = $this->table->decryptData( $value );
						break;
					
					case 'endorsements':	//! Endorsements
						$formatted = '';
						foreach( $sts_license_endorsements as $group => $grp_label )
							if( in_array($group, explode(',',$value)) )
								$formatted .= '<span class="glyphicon glyphicon-check"></span> '.$group.' ';
							else
								$formatted .= '<span class="text-muted"><span class="glyphicon glyphicon-unchecked"></span> '.$group.'</span> ';
						break;
					
					case 'groups':	//! Groups
						$formatted = '';
						foreach( $sts_opt_groups as $group )
							if( in_array($group, explode(',',$value)) )
								$formatted .= '<span class="text-nowrap"><span class="glyphicon glyphicon-check"></span>'.$group.'</span> ';
							else
								$formatted .= '<span class="text-nowrap"><span class="text-muted"><span class="glyphicon glyphicon-unchecked"></span>'.$group.'</span></span> ';
						break;
					
					case 'states':	//! states - for IFTA
						$formatted = implode(' ', explode(',', $value));
						break;
					
					case 'bool':	//! Boolean
						if( isset($value) ) {
							if( $value )
								$formatted = '<span class="glyphicon glyphicon-check" hidden><span class="hidden">1</span></span>';
							else
								$formatted = '<span class="text-muted"><span class="glyphicon glyphicon-unchecked"></span>';
						} else
							$formatted = '';
						break;

					case 'carrier_pick':	//! carrier_pick
	//echo "<pre>carrier_pickXX\n";
	//var_dump($row);
	//echo "</pre>";

						if( $row['EXPIRED'] <> 'red' ) {
							$formatted = '<a class="btn btn-sm btn-success carrier_pick" id="CARRIER_'.$row['CARRIER_CODE'].'"
					data-resource_code = "'.$row['CARRIER_CODE'].'"
					data-resource = "'.$row['CARRIER_NAME'].'"
					><span class="glyphicon glyphicon-plus"></span></a>';
						} else {
							$formatted = '<a class="btn btn-sm btn-danger disabled" name="disabled"><span class="glyphicon glyphicon-remove"></span></a>';
						}
						break;
					
					case 'valid':	//! Valid
						if( isset($value) ) {
							if( $value == 'valid' ) {
								$source = isset($layout[$key]['source']) ? $layout[$key]['source'] : '';
								$source = $source <> '' && isset($row[$source]) ? $row[$source] : 'PC*Miler';
								$lat = isset($layout[$key]['lat']) ? $layout[$key]['lat'] : '';
								$lat = $lat <> '' && isset($row[$lat]) ? floatval($row[$lat]) : 0;
								$lon = isset($layout[$key]['lon']) ? $layout[$key]['lon'] : '';
								$lon = $lon <> '' && isset($row[$lon]) ? floatval($row[$lon]) : 0;
								$formatted = '<div class="inform" data-content="Confirmed via '.$source.
								($lat <> 0 && $lon <> 0 ? '<br>lat='.$lat.' lon='.$lon.' <a href=\'https://www.google.ca/maps/@'.$lat.','.$lon.',16z?hl=en\' target=\'_blank\'><span class=\'glyphicon glyphicon-new-window\'></span></a>' : '').'"><span class="lead text-success"><span class="glyphicon glyphicon-ok"></span></span></div>';
							} else if( in_array($value, array('error','warning')) ) {
								$code = isset($layout[$key]['code']) ? $layout[$key]['code'] : '';
								$code = $code <> '' && isset($row[$code]) ? $row[$code] : '';
								$descr = isset($layout[$key]['descr']) ? $layout[$key]['descr'] : '';
								$descr = $descr <> '' && isset($row[$descr]) ? '<br>'.$row[$descr] : '';
								$popup_text = $code.$descr;
								
								$formatted = '<span class="lead text-'.($value == 'error' ? 'danger' : 'muted').'"><span class="glyphicon glyphicon-'.($value == 'error' ? 'remove' : 'warning-sign').'"></span>';
								if($popup_text<>'')
								$formatted = '<div class="inform" data-content="'.$popup_text.'">'.$formatted.'</div>';
							}
						} else
							$formatted = '';
						break;
					
					case 'email':	//! Email address with clickable link
						$formatted = '<a href="mailto:'.$value.'" title="Send email to '.$value.'">'.$value.'</a>';
						break;
					
					case 'phone':	//! Phone number with clickable link
						$formatted = '<a href="tel:'.$value.'" title="Phone '.$value.'">'.$value.'</a>';
						break;
					
					case 'date':	//! Date
						$formatted = isset($value) && $value <> '' ? date("m/d/Y", strtotime($value)) : '';
						break;
					
					case 'date-s':	//! Date short
						$formatted = isset($value) && $value <> '' ? '<span class="tip" title="'.date("m/d/Y", strtotime($value)).'">'.date("m/d", strtotime($value)).'</span>' : '';
						break;
					
					case 'datetime':	//! Date & time
					case 'timestamp':	// Date & time
						$formatted = isset($value) && $value <> '0000-00-00 00:00:00' && $value <> '' ? date("m/d/Y H:i", strtotime($value)) : '';
						break;
					
					case 'timestamp-s':	//! Date & time short
						$formatted = isset($value) && $value <> '0000-00-00 00:00:00' && $value <> '' ? '<span class="tip" title="'.date("m/d/Y h:i:s A", strtotime($value)).'">'.date("m/d H:i", strtotime($value)).'</span>' : '';
						break;
					
					case 'timestamp-s-eta':	//! Date & time short - STOP_ETA
					
						$formatted = (isset($value) && $value <> '0000-00-00 00:00:00' && $value <> '' ?
							date("m/d/Y H:i", strtotime($value)) : '' );
						
						$formatted = '<div class="form-group">
							<div class="input-group STOP_ETA" code="'.$row['STOP_CODE'].'">
								<input type="text" class="form-control" value="'.$formatted.'">
								<span class="input-group-addon">
								<span class="glyphicon glyphicon-calendar"></span>
								</span>
							</div>
						</div>';
						break;
					
					case 'due':	//! Due date
						if( isset($row['STOP_TYPE']) ) {
							switch($row['STOP_TYPE']) {
								case 'pick':
								case 'pickdock':
									$formatted = '<span class="tip" title="'.date("m/d/Y", strtotime($row['PICKUP_DATE'])).'">'.$this->duedate( $row['PICKUP_DATE'], $row['PICKUP_TIME_OPTION'],
										$row['PICKUP_TIME1'], $row['PICKUP_TIME2'] ).'</span>';
									break;
								case 'drop':
								case 'dropdock':
									$formatted = '<span class="tip" title="'.date("m/d/Y", strtotime($row['DELIVER_DATE'])).'">'.$this->duedate( $row['DELIVER_DATE'], $row['DELIVER_TIME_OPTION'],
										$row['DELIVER_TIME1'], $row['DELIVER_TIME2'] ).'</span>';
									break;
								case 'stop':
									$formatted = '';
									break;
							}
						} else
							$formatted = '';
						break;
					
					case 'timestamp-s-actual':	//! Date & time short / actual
						
						if( isset($value) && $value <> '0000-00-00 00:00:00' && $value <> '' && isset($row['STOP_TYPE']) ) {
							switch($row['STOP_TYPE']) {
								case 'pick':
								case 'pickdock':
									$formatted = '<span class="tip" title="'.date("m/d/Y H:i", strtotime($value)).'">'.$this->actualdate( $value, $row['PICKUP_DATE'],
										$row['PICKUP_TIME_OPTION'], $row['PICKUP_TIME1'], $row['PICKUP_TIME2'] ).'</span>';
									break;
								case 'drop':
								case 'dropdock':
									$formatted = '<span class="tip" title="'.date("m/d/Y H:i", strtotime($value)).'">'.$this->actualdate( $value, $row['DELIVER_DATE'],
										$row['DELIVER_TIME_OPTION'], $row['DELIVER_TIME1'], $row['DELIVER_TIME2'] ).'</span>';
									break;
								case 'stop':
									$formatted = isset($value) && $value <> '' ? '<span class="tip" title="'.date("m/d/Y H:i", strtotime($value)).'">'.date("m/d/Y H:i", strtotime($value)).'</span>' : '';
									break;
							}
						} else
							$formatted = isset($value) && $value <> '0000-00-00 00:00:00' && $value <> '' ? '<span class="tip" title="'.date("m/d/Y H:i", strtotime($value)).'">'.date("m/d H:i", strtotime($value)).'</span>' : '';
						break;
					
					case 'link':	//! Links to another page
						if( isset($layout[$key]['url']) )
							$formatted = '<a href="'.$layout[$key]['url'].urlencode($value).'">'.$value.'</a>';
						else
							$formatted = $value;
						break;
					
					case 'num0':	//! number, zero decimals
						$formatted = number_format((float) $value, 0);
						break;
	
					case 'numb':	//! number, bytes kb, mb etc.
						$formatted = is_numeric($value) && $value > 0 ?
							$this->formatBytes((float) $value ) : '';
						break;
	
					case 'num0stop':	//! number, zero decimals, stop
						$formatted = '<span style="font-size: 16px">'.number_format((float) $value, 0).'</span>';
						if( isset($row['STOP_TYPE']) ) {
							switch($row['STOP_TYPE']) {
								case 'pick':
									$stop_symbol = '<span title="Pickup from shipper">Ps</span>';
									break;
								case 'pickdock':
									$stop_symbol = '<span title="Pickup from dock">Pd</span>';
									break;
								case 'drop':
									$stop_symbol = '<span title="Deliver to consignee">Dc</span>';
									break;
								case 'dropdock':
									$stop_symbol = '<span title="Deliver to dock">Dd</span>';
									break;
								case 'stop':
									$stop_symbol = '<span title="Reposition/return stop">S</span>';
									break;
							}
							$formatted .= '&nbsp;<span class="text-success pull-left" style="font-size: 16px"><strong>'.$stop_symbol.'</strong></span>';
						}
						break;
	
					case 'num2':	//! number two decimals
						$formatted = number_format((float) $value, 2);
						break;
					
					case 'num0nc':	//! number, zero decimals, no thousands separator
						$formatted = number_format((float) $value, 0, '.', '');
						break;
	
					case 'num2nc':	//! number, two decimals, no thousands separator
						$formatted = number_format((float) $value, 2, '.', '');
						break;
	
					case 'edistatus':	//! EDI status
						require_once( "sts_edi_class.php" );
						$edi = sts_edi::getInstance($this->table->database, $this->table->debug);
						$formatted =  '<span title="'.$edi->get_field_value( 'AT702', $value ).'">'.$value.'</span>';
						break;
	
					default:	//! Treat as text
						$formatted = $value;
						break;
					
				}
			} else {
				$formatted = $value;
			}
		} else {
			$formatted = $value;
		}
		
		//if( $this->debug ) echo "<p>sts_result > format: pk = $pk, key = ".(isset($row[$key.'_KEY']) ? $row[$key.'_KEY'] :'')."</p>";
 		if( isset($layout[$key]['link']) && $layout[$key]['format'] == 'table' && isset($row[$key.'_KEY']) ) {
 			$link_key = isset($layout[$key]['link_key']) ? $row[$layout[$key]['link_key']] : $row[$key.'_KEY'];
			$formatted = '<a href="'.str_replace('%pk%', $pk, $layout[$key]['link']).$link_key.'"'.(isset($layout[$key]['button']) ? ' class="btn btn-'.$layout[$key]['button'].'"' : '').'>'.$formatted.'</a>';
		} else
		if( isset($layout[$key]['link']) && $layout[$key]['format'] == 'case' && ! empty($value) )
			$formatted = '<a href="'.str_replace('%pk%', $pk, $layout[$key]['link']).
			(isset($layout[$key]['link_key']) && !empty($row[$layout[$key]['link_key']]) ? $row[$layout[$key]['link_key']] : $value).'"'.
			(isset($layout[$key]['button']) ? ' class="btn btn-'.$layout[$key]['button'].'"' : '').'>'.$formatted.'</a>';
		else
		if( isset($layout[$key]['link']) && ! empty($value) )
			$formatted = '<a href="'.str_replace('%pk%', $pk, $layout[$key]['link']).
			(isset($layout[$key]['key']) && !empty($row[$layout[$key]['key']]) ? $row[$layout[$key]['key']] : $value).'"'.
			(isset($layout[$key]['target']) ? ' target="'.$layout[$key]['target'].'"' : '').

			(isset($layout[$key]['button']) ? ' class="btn btn-'.$layout[$key]['button'].'"' : '').'>'.$formatted.'</a>';
		
		if( $this->profiling ) {
			$format_end = $this->timer->split();
			echo "<p>sts_result > format: elapsed ".number_format((float) ($format_end - $format_start),4)."</p>";
			$this->time_format += $format_end - $format_start;
		}
		return $formatted;
	}
	
	// Get the keys for use in a query
	public function keys( $layout ) {
		$keys = array_keys( $layout );
		$keys2 = array();
		foreach( $keys as $key ) {
			//! Code snippet, like function or subselect
			if( isset($layout[$key]['snippet'])) {
				$keys2[] = $layout[$key]['snippet'].' AS '.$key;
			} else
			
			if( $layout[$key]['format'] == 'subselect' ) {
				$keys2[] = '('.str_replace('%KEY%', $layout[$key]['key'], $layout[$key]['query']).' LIMIT 1) AS '.$key;
			} else if( $layout[$key]['format'] == 'table' ) {
				$keys2[] = (isset($layout[$key]['pk']) ? $layout[$key]['pk'] : $key).' AS '.$key.'_KEY, (SELECT '.$layout[$key]['fields'].' from '.$layout[$key]['table'].
					' X WHERE X.'.$layout[$key]['key'].' = '.$this->table->table_name.'.'.
					(isset($layout[$key]['pk']) ? $layout[$key]['pk'] : $key).' '.
					(isset($layout[$key]['condition']) ? 'AND X.'.$layout[$key]['condition'] : '').
					' LIMIT 0 , 1) AS '.$key;
					
			} else if( $layout[$key]['format'] == 'btable' ) {
				$keys2[] = '(SELECT '.$layout[$key]['fields'].' from '.$layout[$key]['table'].
					' WHERE '.$layout[$key]['key'].' = '.$layout[$key]['pk'].'
					LIMIT 0 , 1) AS '.$key;
					
			} else if( $layout[$key]['format'] == 'count' ) {
				$keys2[] = '(SELECT count(*) from '.$layout[$key]['table'].
					' X WHERE X.'.$layout[$key]['key'].' = '.$this->table->table_name.'.'.$layout[$key]['pk'].'
					) AS '.$key;
					
			} else if( $layout[$key]['format'] == 'sum' ) {
				$keys2[] = '(SELECT coalesce(sum('.$layout[$key]['field'].'),0) from '.$layout[$key]['table'].
					' X WHERE X.'.$layout[$key]['key'].' = '.$this->table->table_name.'.'.$layout[$key]['pk'].'
					) AS '.$key;
					
			} else if( $layout[$key]['format'] == 'case' ) {
				$keys2[] = '(CASE '.$layout[$key]['key'].'
					WHEN \''.$layout[$key]['val1'].'\' THEN '.$layout[$key]['choice1'].'
					ELSE '.$layout[$key]['choice2'].' END
					) AS '.$key;

			} else if( isset($layout[$key]['group']) && is_array($layout[$key]['group']) ) {
				foreach($layout[$key]['group'] as $group_item) {
					$keys2[] = $group_item;
				}
			} else {
				$keys2[] = $key;
			}
		}
		$keys_string =  implode(', ', $keys2);
		if( $this->debug ) echo "<p>keys $keys_string</p>";
		return $keys_string;
	}
	
	//! Display dropdown menu - for load screen only
	public function dropdown_menu( $code, $rowbuttons, $status, $current_stop, $first_stop_status, $num_stops, $current_stop_type, $row2, $show_stops = true ) {
		if( $this->profiling ) {
			$dropdown_start = $this->timer->split();
		}

		if( $this->debug ) echo "<p>sts_result > dropdown_menu: code = $code, status = $status, current_stop = $current_stop, first_stop_status = $first_stop_status, num_stops = $num_stops, current_stop_type = $current_stop_type".($this->profiling ? ", start = ".number_format((float) $dropdown_start,4) : "")."</p>";
		
		$tbl = $this->table2 ? $this->table2 : $this->table;
		$output = '';
		
		$output .= '<div class="btn-group">
  <button type="button" class="dropdown-toggle btn-md" data-toggle="dropdown">
    '.$code.' <span class="caret"></span>
  </button>&nbsp;'.($show_stops ? $current_stop.'&nbsp;of&nbsp;'.$num_stops : '').'
  <ul class="dropdown-menu" role="menu">
  ';
	  	//! SCR# 199 - don't show 'view load' menu option from 'view load' screen
	  	$dirs = explode('/',$_SERVER['SCRIPT_NAME']);
	  	if( end($dirs) <> 'exp_viewload.php' )
	  		$output .= '<li><a href="exp_viewload.php?CODE='.$code.'"><span class="glyphicon glyphicon-eye-open"></span> View load '.$code.'</a></li>
	  	';
  		$menu_items = 1;
  		if( in_array($tbl->state_behavior[$status],
  			array('complete','oapproved','approved','billed') ) ) {
  			$output .= '<li class="dropdown-header"><span class="glyphicon glyphicon-ban-circle"></span> Cannot change load after load complete.</li>
  			';
	  	} else {
	  		foreach( $rowbuttons as $row_item ) {
	  			$output .= '<li><a href="'.$row_item['url'].$code.'"><span class="'.$row_item['icon'].'"></span> '.$row_item['tip'].$code.'</a></li>
	  			';
	  			$menu_items++;
	  		}
	  	}

  		$following = $tbl->following_states( $status );
		if( $this->debug ) {
			echo "<p>sts_result > dropdown_menu: $code following = </p>
			<pre>";
			var_dump($following);
			echo "</pre>";
		}
  		foreach( $following as $row ) {
 			$show_stop = isset($row['BEHAVIOR']) &&
  				(strpos($row['BEHAVIOR'],'arr') !== false ||
  				strpos($row['BEHAVIOR'],'dep') !== false);
  			if( $tbl->state_change_ok( $code, $status, $row['CODE'], $current_stop, $num_stops, $current_stop_type ) ) {
  				if( $row['BEHAVIOR'] == 'cancel' ) { //!HERE  id="modal"
	  				$extra_msg = '';
	  				if( $tbl->state_behavior[$status] == 'imported' ) {
	  					$edi_info = $tbl->database->get_one_row(
							"SELECT  EXP_LOAD.EDI_204_PRIMARY, EDI_204_B204_SID, EDI_204_ORIGIN
							FROM EXP_LOAD, EXP_SHIPMENT
							WHERE SHIPMENT_CODE = EXP_LOAD.EDI_204_PRIMARY
							AND EXP_LOAD.LOAD_CODE = ".$code );
	  					$extra_msg = '<br>If you cancel, this will DECLINE the 204 load offer'.
	  					(isset($edi_info["EDI_204_B204_SID"]) ? ' '.$edi_info["EDI_204_B204_SID"]:'').
	  					(isset($edi_info["EDI_204_ORIGIN"]) ? ' from '.$edi_info["EDI_204_ORIGIN"]:'').'.<br><br>';
	  				}
	  					
	  				
	  				$output .= '<li><a 
	  				onclick="confirmation(\'Cancel Load '.$code.'?<br>'.$extra_msg.'(no undo)\',\'exp_load_state.php?CODE='.$code.'&STATE='.$row['CODE'].'&CSTOP='.$current_stop.'&CSTATE='.$status.'\')" ><span class="text-danger"><span class="lead glyphicon glyphicon-remove" style="top: 5px;"></span> Change state to '.$row['STATUS'].'</span></a></li>
	  			';
	  				
  				} else if( ! in_array($row['BEHAVIOR'], array('complete', 'billed')) ) {
  				
	  				$output .= '<li><a id="modal" href="exp_load_state.php?CODE='.$code.'&STATE='.$row['CODE'].'&CSTOP='.$current_stop.'&CSTATE='.$status.'"><img src="images/status_codes_icon.png" alt="status_codes_icon" height="16" /> Change state to '.$row['STATUS'].
	  				($show_stop ? " (stop $current_stop)" : "").'</a></li>
	  			';
	  			}
  				$menu_items++;
  			} else {
  			if( $row['BEHAVIOR'] == 'manifest' )
  				$output .= '<li class="dropdown-header"><span class="glyphicon glyphicon-ban-circle"></span> not ready to Send Freight Agreement. '.$tbl->state_change_error.'</li>
  			';
  			else if( $row['BEHAVIOR'] == 'dispatch' )
  				$output .= '<li class="dropdown-header"><span class="glyphicon glyphicon-ban-circle"></span> not ready to dispatch. '.$tbl->state_change_error.'</li>
  			';
   			else if( $row['BEHAVIOR'] == 'cancel' )
  				$output .= '<li class="dropdown-header"><span class="glyphicon glyphicon-ban-circle"></span> unable to cancel. '.$tbl->state_change_error.'</li>
  			';
   			else if( $row['BEHAVIOR'] == 'accepted' )
  				$output .= '<li class="dropdown-header"><span class="glyphicon glyphicon-ban-circle"></span> unable to accept 204. '.$tbl->state_change_error.'</li>
  			';
   			else if( $row['BEHAVIOR'] == 'approved' && in_group(EXT_GROUP_FINANCE) )
  				$output .= '<li class="dropdown-header"><span class="glyphicon glyphicon-ban-circle"></span> unable to Approve (finance). '.$tbl->state_change_error.'</li>
  			';
   			else if( $row['BEHAVIOR'] == 'oapproved' && $this->multi_company && in_group(EXT_GROUP_BILLING) )
  				$output .= '<li class="dropdown-header"><span class="glyphicon glyphicon-ban-circle"></span> unable to Approve (office). '.$tbl->state_change_error.'</li>
  			';
 			/*else 
  				$output .= '<li class="dropdown-header"><span class="glyphicon glyphicon-ban-circle"></span>  '.$this->table->state_change_error.'</li>
   			';*/
 			}
 
  		}
  		//! SCR# 342 - not drivers
  		//! SCR# 576 - add oapproved state
  		if( ($tbl->setting_table->get( 'option', 'LOAD_COMPLETE' ) == 'true') &&
	  		! in_group( EXT_GROUP_DRIVER ) &&
	  		! in_array($tbl->state_behavior[$status], 
				array('complete', 'approved', 'oapproved', 'billed', 'cancelled', 'imported')) ) {
			$path = explode('/', $_SERVER["SCRIPT_FILENAME"]);
			$script = str_replace('ajax', '', end($path));
			
			//! SCR# 650 - Restrict "Complete Load" to admin if not enough insurance
			$check ='';
			if( $tbl->state_behavior[$status] == 'entry' &&
				! in_group( EXT_GROUP_ADMIN ) &&
				$tbl->setting_table->get( 'option', 'CARRIER_INSUFFCIENT_INS' ) == 'true' &&
				$tbl->setting_table->get( 'option', 'ADMIN_COMPLETE_LOAD' ) == 'true' ) {

				require_once( "sts_carrier_class.php" );

				$carrier_table = sts_carrier::getInstance($tbl->database, $tbl->debug);
				$check = $carrier_table->check_suitable( $code );
			}
			if( $check <> '' ) {
				$output .= '<li class="dropdown-header"><span class="glyphicon glyphicon-ban-circle"></span> unable to complete load. <strong>Carrier does not have enough insurance</strong>. Need admin to override.</li>';
			} else {
				if( $tbl->can_load_complete($code) ) {
					$output .= '<li><a 
		  				onclick="confirmation(\'Promote this load to complete status.<br>Skip all intermediate steps.<br>Does not record actual arrive/depart dates.<br>Will free up driver and update trailer location to final destination.<br><br><strong>Issues:</strong> Backing up may not undo all of this.<br><br><strong>Use with caution</strong>\',\'exp_viewload.php?CODE='.$code.'&LOAD_COMPLETE&RETURN='.$script.'\')" ><span class="text-danger"><span class="lead glyphicon glyphicon-forward" style="top: 5px;"></span> Complete load '.$code.'</span></a></li>
		  			';
	  			} else {
		  			$output .= '<li class="dropdown-header"><span class="glyphicon glyphicon-ban-circle"></span> Unable to complete. Try dispatching first.</li>
		  			';
	  			}
  			}
  			$menu_items++;
		}
		
		//! SCR# 298 - Check Call functionality
		// Restrict to admin or dispatch
		if( (in_group(EXT_GROUP_ADMIN) || in_group(EXT_GROUP_DISPATCH)) &&
			// Restrict to certain states
			in_array($tbl->state_behavior[$status], array('dispatch', 'arrive cons',
			'arrrecdock','arrive shipper', 'arrshdock', 'arrive stop', 'depart cons',
			'deprecdock', 'depart shipper', 'depshdock', 'depart stop') )
		 ) {
		
			//! SCR# 302 - pass office number through
			$output .= '<li><a data-target="#checkcall_modal" data-toggle="modal" onclick="$(\'#ck-load\').val('.$code.');'.(empty($row2["SS_NUMBER2"]) ? '' : ' $(\'#ck-office\').text(\'/ '.$row2["SS_NUMBER2"].'\');').'"><span class="text-success"><span class="glyphicon glyphicon-earphone"></span> Check Call '.$code.'</span></a></li>
		';
	  		$menu_items++;
  		}

  		
  		if( $menu_items == 0 )
  			$output .= '<li class="dropdown-header">No available actions.</li>
  			';
  		//<li class="dropdown-header">Nav header</li>
  		
		$output .= '</ul>
</div>';
		if( $this->profiling ) {
			$dropdown_end = $this->timer->split();
			echo "<p>sts_result > dropdown_menu: elapsed ".number_format((float) ($dropdown_end - $dropdown_start),4)."</p>";
			$this->time_render_dropdown += $dropdown_end - $dropdown_start;
		}
		return $output;

	}
	
	//! Dropdown menu instead of row buttons, for all except load table
	public function dropdown_menu2($buttons, $have_active, $have_deleted, $row) {
		if( $this->debug ) echo "<p>".__METHOD__.": entry</p>";
		
		if( $this->debug ) {
			echo "<pre>".__METHOD__.": row = ";
			var_dump($row);
			echo "</pre>";
		}
		
		$my_session = sts_session::getInstance( $this->debug );

		$output = '';
		
		$output .= '<div class="btn-group">
  <button type="button" class="dropdown-toggle btn-md" id="toggle'.$row[$this->table->primary_key].'" data-toggle="dropdown"><span class="caret"></span>
  </button>
  <ul class="dropdown-menu" role="menu">
  ';
  		$id = 0;
		foreach( $buttons as $edit_item ) {
			if( $this->debug && $have_deleted ) {
				echo "<p>dropdown_menu2: have_deleted, edit_item, deleted = </p>
				<pre>";
				var_dump($have_deleted, $edit_item, $row['ISDELETED']);
				echo "</pre>";
			}
	
			$button_restricted = false;
			if( isset($edit_item['restrict']) ) {
				$button_restricted = ! $my_session->in_group( $edit_item['restrict'] );
			}
			
			if( ! $button_restricted &&
				(! isset($edit_item['showif']) ||
				( $edit_item['showif'] == 'notdeleted' && $have_deleted && ! $row['ISDELETED'] ) ||
				//! SCR# 421 - Disable button if already done this week
				( $edit_item['showif'] == 'notdone' && ! $row['DONE_THIS_WEEK'] ) ||
				( $edit_item['showif'] == 'notrestricted' && ($my_session->superadmin() || ! $row['RESTRICTED']) ) ||
				( $edit_item['showif'] == 'admin' && $my_session->in_group( EXT_GROUP_ADMIN ) ) ||
				//! SCR# 506 - let mechanic have access to inspection reports again
				( $edit_item['showif'] == 'inspection' && $my_session->in_group( EXT_GROUP_INSPECTION, EXT_GROUP_MECHANIC ) ) ||
				( $edit_item['showif'] == 'deleted' && $have_deleted && $row['ISDELETED'] ) ||
				( $edit_item['showif'] == 'can_delete' && 
				method_exists( get_class($this->table), 'can_delete') &&
				$this->table->can_delete( $row[$edit_item['key']] ) ) ||
				( $edit_item['showif'] == 'notfirst' && $row_num > 1 ) ||
				( $edit_item['showif'] == 'notlast' && $row_num < $this->count_rows ) ||
				//! SCR# 504 - if cancelled, don't show billing button
				( $edit_item['showif'] == 'notcancelled' && $row['CURRENT_STATUS']<>'Cancelled') ||
				( $edit_item['showif'] == 'Dropped' && $row['CURRENT_STATUS']=='Dropped') ||
				( $edit_item['showif'] == 'ready' && $row['CURRENT_STATUS']=='Ready' ) ||
				( $edit_item['showif'] == 'ready204' && $row['CURRENT_STATUS']=='Ready' && $row['EDI_204_PRIMARY']>0) ||
				( $edit_item['showif'] == 'edi' && in_array($row['EDI_TYPE'], array('214','990')) ) ||
				( $edit_item['showif'] == 'notready' && $row['CURRENT_STATUS']=='Entered' ) ||
				( $edit_item['showif'] == 'hasftp' && $row['FTP_TRANSPORT']=='FTP' ) ||
				( $edit_item['showif'] == 'hasbvd' && $row['FTP_TRANSPORT']=='BVD' ) ||
				//! SCR# 435 - used for list clients screen
				//! SCR# 475 - fix for when CMS is diabled
				( $edit_item['showif'] == 'isclient' &&
					(! $this->cms_enabled || $row['CLIENT_TYPE']=='client') &&
					$have_deleted && ! $row['ISDELETED'] )
				) ) {
			
				// For EDI, substitute SHIPMENT_CODE with EDI_204_PRIMARY if set 
				if( isset($edit_item['tip']) &&
					($edit_item['tip'] == 'Unapprove ' ||
					$edit_item['tip'] == 'Client billing ') &&
					isset($row['EDI_204_PRIMARY']) && $row['EDI_204_PRIMARY'] <> '' ) {
					$edit_item['key'] = 'EDI_204_PRIMARY';
					$edit_item['label'] = 'EDI_204_PRIMARY';
				}
				
				// For consolidation
				if( isset($edit_item['tip']) &&
					$edit_item['tip'] == 'Unapprove ' &&
					isset($row['CONSOLIDATE_NUM']) && $row['CONSOLIDATE_NUM'] <> '' ) {
					$edit_item['key'] = 'CONSOLIDATE_NUM';
					$edit_item['label'] = 'CONSOLIDATE_NUM';
				}
				
				$confirm = isset($edit_item['confirm']) && isset($edit_item['confirm']) == 'yes';

				if( $edit_item['label'] == 'BSTATE' ) {
					//! Render state transitions for shipment billing states
			  		$following = $this->table->following_states( $row['BILLING_STATUS_KEY'], 'shipment-bill' );

					$warned = false;
					$bout = '';
					foreach( $following as $frow ) {
						if( ($this->table->export_quickbooks && $frow['BEHAVIOR'] == 'billed') ||
							$this->table->billing_state_change_ok( $row['SHIPMENT_CODE'], $row['BILLING_STATUS_KEY'], $frow['CODE'] ) ) {

							// exp_shipment_state.php?BILLING=true&PW=Soyo&STATE=unapproved&CODE=9144&CSTATE=64'
							// onclick="confirmation('Confirm: Unapprove 9144\n\nThis returns the shipment to the Unapproved state.<br>You can then edit & make adjustments before you approve again.', 'exp_shipment_state.php?BILLING=true&PW=Soyo&STATE=unapproved&CODE=9144&CSTATE=64')"
							// exp_export_csv.php?pw=GoldUltimate&type=invoice&code=9139
							
								if( $frow['BEHAVIOR'] == 'billed' ) {
									if( $this->table->export_quickbooks )
										$bout .= '<li><a id="'.$row['SHIPMENT_CODE'].(++$id).'" href="exp_qb_retry.php?TYPE=shipment&CODE='.$row['SHIPMENT_CODE'].'"><span class="text-success"><span class="glyphicon glyphicon-forward"></span> Resend to QB</span></a>';
									else if( $this->table->export_sage50 )
										$bout .= '<li><a id="'.$row['SHIPMENT_CODE'].(++$id).'" href="exp_export_csv.php?pw=GoldUltimate&type=invoice&code='.$row['SHIPMENT_CODE'].'"><span class="text-success"><span class="glyphicon glyphicon-list-alt"></span> <span class="glyphicon glyphicon-arrow-right"></span> Sage50</span></a>';
								} else
									$bout .= '<li><a id="'.$row['SHIPMENT_CODE'].(++$id).'" onclick="confirmation(\''.$this->table->billing_confirm( $row['SHIPMENT_CODE'], $frow['BEHAVIOR'] ).'\',\'exp_shipment_state.php?BILLING=true&PW=Soyo&STATE='.$frow['BEHAVIOR'].'&CODE='.$row['SHIPMENT_CODE'].'&CSTATE='.$row['BILLING_STATUS_KEY'].'\')"><span class="text-success"><span class="glyphicon glyphicon-usd"></span> Change '.$row['SHIPMENT_CODE'].' billing state to '.$frow['STATUS'].'</span></a></li>
							';
							
						} else {
							if( ! $warned && in_array($frow['BEHAVIOR'], array('approved', 'oapproved')) ) {
								$bout .= '<li class="dropdown-header"><span class="glyphicon glyphicon-ban-circle"></span> Unable to approve '.$row['SHIPMENT_CODE'].' - '.$this->table->state_change_error.'</li>
';
								$warned = true;
							}
						}
					}
					
					if( $bout <> '' )
					$output .= '<li role="separator" class="divider"></li>
'.$bout;
				} else
				if( $edit_item['label'] == 'Separator' ) {
					$output .= '<li role="separator" class="divider"></li>
';
				} else {
					$output .= '<li><a ';
					
					$url = $edit_item['url'];

					if( isset($edit_item['key']) && isset($row[$edit_item['key']]) && 
						$row[$edit_item['key']] <> '')
						$url .= urlencode($row[$edit_item['key']]);
					else
						$url .= urlencode($row[$this->table->primary_key]);

					if( isset($row['CURRENT_STATUS_KEY']) )
						$url .= '&CSTATE='.urlencode($row['CURRENT_STATUS_KEY']);
					
					if( ! $confirm )
						$output .= 'href="'.$url.'"';

					if( isset($edit_item['class']) )
						$output .= ' class="'.$edit_item['class'].'"';
					
					if( isset($edit_item['target']) )
						$output .= ' target="'.$edit_item['target'].'"';
					
					$output .= ' id="'.$row[$edit_item['label']].(++$id).'"';
					
					if( $confirm )
		  				$output .= ' onclick="confirmation(\''.$this->table->real_escape_string($edit_item['tip'].$row[$edit_item['label']]).'?<br>'.
		  					(isset($edit_item['tip2']) ? $this->table->real_escape_string($edit_item['tip2']).'<br>' : '').'(no undo)\',\''.
		  					$url.'\''.(isset($edit_item['inplace']) && $edit_item['inplace'] ? ', \''. $edit_item['inplace'].'\'' : '').')" ><span class="text-danger">';
					else
						$output .= '>';
					$output .= '<span class="'.$edit_item['icon'].'"></span> '.$edit_item['tip'].$row[$edit_item['label']];
					
					if( $confirm )
						$output .= '</span>';
					$output .= '</a></li>
';
				}
			}
		}
		$output .= '</ul>
</div>';
		if( $this->debug ) {
			echo "<pre>".__METHOD__.": output = ";
			var_dump($output);
			echo "</pre>";
		}
		
		if( strpos($output, '<li>') === false ) $output = '';

		return $output;
  /*
		<span class="lead glyphicon glyphicon-remove" style="top: 5px;"></span>
  	  	$output .= '<li><a href="exp_viewload.php?CODE='.$code.'" target="_blank"><span class="glyphicon glyphicon-eye-open"></span> View load '.$code.'</a></li>

		array( 'url' => 'exp_addshipment.php?CODE=', 'key' => 'SHIPMENT_CODE', 'label' => 'SHIPMENT_CODE', 'tip' => 'Edit shipment ', 'icon' => 'glyphicon glyphicon-edit' ),
*/
	}

	// Display tabular results
	public function render( $layout = false, $edit = false, $add = false, $show_body = true ) {
		global $_GET, $_POST, $_SESSION, $exspeedite_db;

		if( $this->debug ) echo "<p>".__METHOD__.": entry</p>";
		$my_session = sts_session::getInstance( $this->debug );
		
		if( $this->profiling ) {
			$this->timer = new sts_timer();
			$this->timer->start();
		}
		if( $this->debug ) echo "<p>render tabular results ".$this->table_name."</p>";
		if( ! $layout && $this->table->layout_fields ) $layout = $this->table->layout_fields;
		if( ! $edit && $this->table->edit_fields ) $edit = $this->table->edit_fields;
		
		$output = '';
		
		if( $this->debug ) {
			echo "<p>render, edit = </p>
			<pre>";
			var_dump($edit);
			echo "</pre>";
		}
		$use_filters_form = ! isset($edit['popup']) && ! (isset($edit['filters']) && $edit['filters'] == false);
		
		if( $use_filters_form ) {
			$output .= '
			<form class="form-inline" role="form" action="'.
			(isset($edit['form_action']) && $edit['form_action'] <> '' ? $edit['form_action'] : $_SERVER["SCRIPT_NAME"]).'" 
					method="post" enctype="multipart/form-data" 
					name="RESULT_FILTERS_'.$this->table_name.'" id="RESULT_FILTERS_'.$this->table_name.'">
			<input name="FILTERS_'.$this->table_name.'" type="hidden" value="on">
			';
			if( isset($_GET['CODE']) && $_GET['CODE'] )
				$output .= '<input name="RESULT_SAVE_CODE" type="hidden" value="'.$_GET['CODE'].'">
				';
			if( $this->debug )
				$output .= '<input name="debug" type="hidden" value="on">';
		}
		// Title and buttons above table
		if( isset($edit['title']) ) {
			$output .= '<h3 class="pad">'.(isset($edit['title']) ? $edit['title'] : '').'
			<div class="btn-group">';
			if( isset($edit['add']) ) {
				if( isset($edit['popup']) ) {	// Popup, not used currently as conflicts with toggles
				
						$output .= '<a data-toggle="modal" id="add_'.$this->table->table_name.'" data-target="#myModal_add_'.$this->table->table_name.'" data-remote="'.$edit['add'].(isset($add) && $add ? $add : '').'" class="btn btn-sm btn-success" '.
				(isset($edit['actionextras']) ? $edit['actionextras'] : '').'><span class="glyphicon glyphicon-plus"></span>'.(isset($edit['addbutton']) ? $edit['addbutton'] : 'Add').'</a>
						
					<div id="myModal_add_'.$this->table->table_name.'" class="modal fade" tabindex="-1" role="dialog" aria-labelledby="myModal_add" aria-hidden="true">
						<div class="modal-dialog modal-lg">
							<div class="modal-content form-horizontal" role="main">
								<div class="modal-body">
									<p class="text-center"><img src="images/loading.gif" alt="loading" width="125" height="125" /></p>
								</div>
							</div>
						</div>
					</div>';
				
				
				} else {	// Normal add button
					$output .= '<a class="btn btn-sm btn-success" id="'.$this->table_name.'_add" href="'.$edit['add'].
					(isset($add) && $add ? $add : '').'" '.
					(isset($edit['actionextras']) ? $edit['actionextras'] : '').
					'><span class="glyphicon glyphicon-plus"></span> '.(isset($edit['addbutton']) ? $edit['addbutton'] : 'Add').'</a>';
				}
			}	
				
			if( isset($edit['cancel'] ) ) {	// Cancel button
				$output .= '<a class="btn btn-sm btn-default" id="'.$this->table_name.'_cancel" href="'.$edit['cancel'].'"><span class="glyphicon glyphicon-remove"></span> '.(isset($edit['cancelbutton']) ? $edit['cancelbutton'] : 'Cancel').'</a>';
			}
	
			#---> By mona 
			if( isset($edit['addratebutton'] ) ) {	// Cancel button
				$output .= '<a class="btn btn-sm btn-success" href="'.$edit['addratelink'].'"><span class="glyphicon glyphicon-plus"></span> '.(isset($edit['addratebutton']) ? $edit['addratebutton'] : 'Add Driver Rate').'</a>';
			}
			#----> By mona
			
			#---> By mona 
			if( isset($edit['addpaybutton'] ) ) {	// Cancel button
				$output .= '<a class="btn btn-sm btn-success" href="'.$edit['addpaylink'].'"><span class="glyphicon glyphicon-plus"></span> '.(isset($edit['addpaybutton']) ? $edit['addpaybutton'] : 'Driver Pay').'</a>';
			}
			#----> By mona
			
			
			$output .= '
			</div>';	// button group
			
			$have_active = $this->table && $this->table->column_exists( 'ISACTIVE' ) && $this->table->column_type( 'ISACTIVE' ) <> 'enum';
			$have_deleted = $this->table && $this->table->column_exists( 'ISDELETED' );
					
			if( $use_filters_form && ($have_active || $have_deleted ) ) {
				$output .= '&nbsp; <div class="form-group">';
				
				if( $have_active ) {	// We have ACTIVE column
		
					if ( ! isset($_POST['FILTERS_'.$this->table_name]) ) {
						$_POST['SHOW_ACTIVE_'.$this->table_name] = "active";
					}
									
					$output .= '
					<select class="form-control" name="SHOW_ACTIVE_'.$this->table_name.'" id="SHOW_ACTIVE_'.$this->table_name.'"   onchange="form.submit();">
						<option value="active" '.
							($_POST['SHOW_ACTIVE_'.$this->table_name] == "active" ? 'selected' : '').'>active</option>
						<option value="inactive" '.
							($_POST['SHOW_ACTIVE_'.$this->table_name] == "inactive" ? 'selected' : '').'>inactive</option>
						<option value="both" '.
							($_POST['SHOW_ACTIVE_'.$this->table_name] == "both" ? 'selected' : '').'>both</option>
					</select>';
				}
					
				if( $have_deleted && ! isset($edit['nodelete']) ) {	// We have DELETED column
		
					if ( ! isset($_POST['FILTERS_'.$this->table_name]) ) {
						$_POST['SHOW_DELETED_'.$this->table_name] = "notdel";
					}
					
					//! SCR# 900 - list carrier screen
					if( isset($_SESSION['SHOW_DELETED_'.$this->table_name]) ) {
						$_POST['SHOW_DELETED_'.$this->table_name] = $_SESSION['SHOW_DELETED_'.$this->table_name];
					}
									
					$output .= '
					<select class="form-control input-sm" name="SHOW_DELETED_'.$this->table_name.'" id="SHOW_DELETED_'.$this->table_name.'"   onchange="form.submit();">
						<option value="notdel" '.
							($_POST['SHOW_DELETED_'.$this->table_name] == "notdel" ? 'selected' : '').'>not deleted</option>
						<option value="deleted" '.
							($_POST['SHOW_DELETED_'.$this->table_name] == "deleted" ? 'selected' : '').'>deleted</option>
						<option value="both" '.
							($_POST['SHOW_DELETED_'.$this->table_name] == "both" ? 'selected' : '').'>both</option>
					</select>
					';
				}
							
				$output .= '</div>';
			}
			
			if( isset($edit['filters_html']) )
				$output .= '&nbsp; <div class="form-group" style="display: inline-block; margin-bottom: 0; vertical-align: middle;">
					'.$edit['filters_html'].'
					</div>';
			
			$output .= '
			</h3>';
		}
		//! end of Title and buttons above table
	
		//! SCR# 900 - list carrier screen
		if( isset($_SESSION['SHOW_DELETED_'.$this->table_name]) ) {
			$_POST['SHOW_DELETED_'.$this->table_name] = $_SESSION['SHOW_DELETED_'.$this->table_name];
		}
							
		$match = $this->match ? $this->match : '';
		if( isset($_POST['SHOW_ACTIVE_'.$this->table_name]) && $_POST['SHOW_ACTIVE_'.$this->table_name] == "active" )
			$match .= ($match <> '' ? ' and ' : '') . 'ISACTIVE = true';
		else if( isset($_POST['SHOW_ACTIVE_'.$this->table_name]) && $_POST['SHOW_ACTIVE_'.$this->table_name] == "inactive" )
			$match .= ($match <> '' ? ' and ' : '') . 'ISACTIVE = false';
		if( isset($_POST['SHOW_DELETED_'.$this->table_name]) && $_POST['SHOW_DELETED_'.$this->table_name] == "deleted" )
			$match .= ($match <> '' ? ' and ' : '') . 'ISDELETED = true';
		else if( isset($_POST['SHOW_DELETED_'.$this->table_name]) && $_POST['SHOW_DELETED_'.$this->table_name] == "notdel" )
			$match .= ($match <> '' ? ' and ' : '') . 'ISDELETED = false';
		else if( isset($_POST['SHOW_STATUS_'.$this->table_name]) && isset( $_POST['SHOW_STATUS_'.$this->table_name] ) )
			$match .= ($match <> '' ? ' and ' : '') . 'CURRENT_STATUS = '.$_POST['SHOW_STATUS_'.$this->table_name];
			
		if( $this->debug ) echo "<p>match = $match </p>";
	
		//! Fetch data from the table
		if( $this->debug ) echo "<p>".__METHOD__.": Fetch data from the table</p>";
		
		if( $this->table && $show_body ) {
			$this->result = $this->table->fetch_rows( $match, 
				( $layout ? $this->keys( $layout ) : '*'), 
				(isset($edit['sort']) ? $edit['sort'] : ''));
			if( $this->debug ) {
				echo "<pre>".__METHOD__.": result\n";
				var_dump($this->result);
				echo "</pre>";
			}
			
			$this->count_rows = is_array($this->result) ? count( $this->result ) : 0;
			if( $this->result && $this->count_rows > 0 )
				$this->keys = array_keys($this->result[0]);
				
			$this->count_columns = is_array($this->keys) ? count( $this->keys ) : 0;
			if( $this->table_name <> LOAD_TABLE )
				$output = preg_replace('/\#\#/', $this->count_rows, $output, 1);
			else {
				$count_loads = 0;
				$last_load = 0;
				if( is_array($this->result) && count($this->result) > 0) {
					foreach( $this->result as $row ) {
						if( $row['LOAD_CODE'] <> $last_load ) {
							$count_loads++;
							$last_load = $row['LOAD_CODE'];
						}
					}
				}
				$output = preg_replace('/\#\#/', $count_loads, $output, 1);
			}

		}
		
		if( ! $show_body )
			$output = preg_replace('/\#\#/', '', $output, 1);
		
		$output .= '<div class="table-responsive">
		<table class="display table table-striped table-condensed table-bordered table-hover" id="'.$this->table_name.'">
		<thead>';
		if(isset($edit['toprow']) && $edit['toprow'] <> '') $output .= $edit['toprow'];
		$output .= '<tr class="exspeedite-bg">';
		$buttons_column = is_array($edit) && isset($edit['rowbuttons']) && is_array($edit['rowbuttons']) &&
			$this->table_name <> LOAD_TABLE;
		if( $buttons_column ) $output .= '<th></th>';
		//! Column Headings
		$visible_columns = $buttons_column ? 1 : 0;
		
		$keys = is_array($layout) ? array_keys($layout) : $this->keys;
		if( count($keys) > 0 )
		foreach( $keys as $key )
			if( $layout[$key]['format'] <> 'hidden' ) {
				$output .= '<th'.$this->align($key, $layout);
				if( isset($layout[$key]['length']) && $layout[$key]['length'] <> '' )
					$output .= ' style="min-width: '.intval($layout[$key]['length']).'px";';
				if( ! empty($layout[$key]['tip']) )
					$output .= ' class="tip" title="'.$layout[$key]['tip'].'"';
				$output .= '>'.$this->heading($key, $layout);
				$output .= '</th>';
				$visible_columns++;
			}
		$output .= '</tr>
		</thead>
		';
		if( $show_body ) {
			$output .= '<tbody>
			';
			if( isset($edit['group']) ) {
				$group_by_column = $edit['group'];
				$group_by_column = strpos($group_by_column, '.') !== false ? 
					substr( $group_by_column, strpos($group_by_column, '.') + 1 ) : $group_by_column;
				$group_rows = true;
			} else {
				$group_by_column = '';
				$group_rows = false;
			}
			
			if( $this->result && $this->count_rows > 0 ) {
				$row_num = 0;
				if( $group_rows ) {
					$prev_column_value = "START";
					$prev_pallets_head = 0;
					$prev_pallets_back = 0;
					$prev_weight_head = 0;
					$prev_weight_back = 0;
					$prev_total_distance = 0;
					$prev_mobile_time = '';
					$prev_mobile_location = '';
				}
				$first_load_row = true;
				foreach( $this->result as $row ) {
					if( $group_rows && $prev_column_value <> $row[$group_by_column] && $prev_column_value <> "START") {
						$output .= '<tr class="exspeedite-bg">
							<td class="thin text-right">'.$prev_total_distance.' Miles</td>
							<td class="thin">&nbsp;</td>
							<td class="thin">&nbsp;</td>
							<td class="thin">&nbsp;</td>
							<td class="thin text-right">'.$prev_weight_head.' lb / '.$prev_weight_back.' lb</td>
							<td class="thin">&nbsp;</td>
							<td class="thin">&nbsp;</td>
							<td class="thin">&nbsp;</td>
							<td class="thin">&nbsp;</td>
							<td class="thin">&nbsp;</td>
							<td class="thin">&nbsp;</td>
							<td class="thin">&nbsp;</td>
							<td class="thin text-right">'.$prev_pallets_head.' / '.$prev_pallets_back.'</td>
							<td class="thin">&nbsp;</td>
							<td class="thin">&nbsp;</td>
							<td class="thin">&nbsp;</td>
							<td class="thin">&nbsp;</td>
							<td class="thin">&nbsp;</td>
							<td class="thin">&nbsp;</td>
							</tr>
						';	// '.$row['SUM_PALLETS'].'
					}
	
					if( $group_rows ) {
						$prev_column_value = $row[$group_by_column];
						$prev_pallets_head = intval($row['SUM_PALLETS_HEAD']);//!Assign pallets head/back
						$prev_pallets_back = intval($row['SUM_PALLETS_BACK']);
						$prev_weight_head = intval($row['SUM_WEIGHT_HEAD']);//!Assign weight head/back
						$prev_weight_back = intval($row['SUM_WEIGHT_BACK']);
						$prev_total_distance = $row['TOTAL_DISTANCE'];
						if( $row['CURRENT_STOP'] > 0 && 
						! in_array($this->table->state_behavior[$row['LOAD_STATUS']], array('cancel','complete')) ) {
							$prev_mobile_time = date("m/d/Y h:i A", strtotime($row['MOBILE_TIME']));
							$prev_mobile_location = $row['MOBILE_LOCATION'];
						} else {
							$prev_mobile_time = '';
							$prev_mobile_location = '';
						}
					}
	
					//! Filter on active stops
					if( $group_rows && $this->table_name == LOAD_TABLE &&
						in_array($row['STOP_STATUS'], array('Complete', 'Picked', 'Dropped')) &&
						$row['SEQUENCE_NO'] < $row['NUM_STOPS'] &&
						isset($_SESSION['LOAD_ACTIVE']) && $_SESSION['LOAD_ACTIVE'] == 'Active') {
						if( $row['SEQUENCE_NO'] == 1 ) $first_load_row = true;
						continue;
					}
	
					//! Filter on current stops
					if( $group_rows && $this->table_name == LOAD_TABLE &&
						$row['SEQUENCE_NO'] <> ($row['CURRENT_STOP'] > 0 ? $row['CURRENT_STOP'] : 1) &&
						isset($_SESSION['LOAD_ACTIVE']) && $_SESSION['LOAD_ACTIVE'] == 'Current') {
						if( $row['SEQUENCE_NO'] == 1 ) $first_load_row = true;
						continue;
					}
	
					$row_num++;
					$output .= '<tr>';
	
					//! Per-row buttons at start of row
				if( $buttons_column ) {
					$output .= '<td>
						'.$this->dropdown_menu2($edit['rowbuttons'], $have_active, $have_deleted, $row).'
						</td>';
				} else
					if( $buttons_column ) {
						$buttons_visible = 0;
						if( ! $group_rows || $prev_column_value <> $row[$group_by_column] ) {
							$buttons_str = '<div class="btn-group btn-group-xs">';
							$id = 0;
							foreach( $edit['rowbuttons'] as $edit_item ) {
								if( $this->debug && $have_deleted ) {
									echo "<p>have_deleted, edit_item, deleted = </p>
									<pre>";
									var_dump($have_deleted, $edit_item, $row['ISDELETED']);
									echo "</pre>";
								}
		
								$button_restricted = false;
								if( isset($edit_item['restrict']) ) {
									$button_restricted = ! $my_session->in_group( $edit_item['restrict'] );
								}
																
								if( ! $button_restricted &&
									(! isset($edit_item['showif']) ||
									( $edit_item['showif'] == 'notdeleted' && $have_deleted && ! $row['ISDELETED'] ) ||
									( $edit_item['showif'] == 'deleted' && $have_deleted && $row['ISDELETED'] ) ||
									( $edit_item['showif'] == 'can_delete' && 
									method_exists( get_class($this->table), 'can_delete') &&
									$this->table->can_delete( $row[$edit_item['key']] ) ) ||
									( $edit_item['showif'] == 'notfirst' && $row_num > 1 ) ||
									( $edit_item['showif'] == 'notlast' && $row_num < $this->count_rows ) ||( $edit_item['showif'] == 'Dropped' && $row['CURRENT_STATUS']=='Dropped')
									) ) {
								
									$buttons_visible++;
									$confirm = isset($edit_item['confirm']) && isset($edit_item['confirm']) == 'yes';
									$buttons_str .= '<a ';
									
									if( ! $confirm )
										$buttons_str .= 'href="'.$edit_item['url'].urlencode($row[$edit_item['key']]).
										'"';
									
									$buttons_str .= 'class="btn btn-default btn-xs '.($confirm ? 'confirm' : 'inform').'"  id="'.$row[$edit_item['label']].(++$id).'" data-placement="bottom" data-toggle="popover" data-content="';
									
									if( $confirm )
										$buttons_str .= '<a id=&quot;'.$row[$edit_item['label']].($id).'_confirm&quot; class=&quot;btn btn-danger btn-sm&quot; href=&quot;'.
										$edit_item['url'].urlencode($row[$edit_item['key']]).'&quot;>'.
										'<span class=&quot;'.$edit_item['icon'].'&quot;></span> '.
										$edit_item['tip'].$row[$edit_item['label']].'</a>'.
										(isset($edit_item['tip2']) ? '<br>'.$edit_item['tip2'] : '').
										'<br>There is no undo';
									else
										$buttons_str .= $edit_item['tip'].$row[$edit_item['label']];
									$buttons_str .= '">'.'<span style="font-size: 14px;"><span class="'.$edit_item['icon'].'"></span></span></a>';
								}
							}
							$buttons_str .= '</div>
							';
						}
						$output .= '<td class="text-center">
						<div style="width: '.(6+$buttons_visible*28).'px;">
						'.$buttons_str;
						$output .= '</div>
						</td>';
					}
					//if( $this->debug ) echo "<p>seq = ".$row['SEQUENCE_NO']."</p>";
					//! Columns data
					foreach( (is_array($layout) ? array_keys($layout) : $this->keys) as $key ) {
						//if( $this->debug ) echo "<p>key = $key ".strpos($key, '.')." ".substr( $key, strpos($key, '.') + 1 )."</p>";
						$key2 = strpos($key, '.') !== false ? substr( $key, strpos($key, '.') + 1 ) : $key;
						//if( $this->debug ) echo "<p>key2 = $key2</p>";
						if( $layout[$key]['format'] <> 'hidden' ) {
							if( $this->table_name == LOAD_TABLE && $key2 == 'STOP_STATUS' &&
								isset($row[$key2]) &&
								in_array($row[$key2], array('Complete', 'Picked', 'Dropped', 'Docked')) )
								$cell_color = 'success2';
							else if( $this->table_name == LOAD_TABLE && $key2 == $group_by_column &&
								( $first_load_row || $row['SEQUENCE_NO'] == '' || $row['SEQUENCE_NO'] == '1' ) ) {
								if( isset($row['LOAD_STATUS']) &&
									in_array($this->table->state_behavior[$row['LOAD_STATUS']], array('arrive shipper', 'depart shipper', 'arrshdock', 'depshdock')) )
									$cell_color = 'inprogress';
								else if( isset($row['LOAD_STATUS']) &&
									in_array($this->table->state_behavior[$row['LOAD_STATUS']], array('arrive cons', 'depart cons', 'arrrecdock', 'deprecdock')) )
									$cell_color = 'inprogress2';
								else if( isset($row['LOAD_STATUS']) &&
									in_array($this->table->state_behavior[$row['LOAD_STATUS']], array('complete')) )
									$cell_color = 'success2';
								else if( isset($row['LOAD_STATUS']) &&
									in_array($this->table->state_behavior[$row['LOAD_STATUS']], array('dispatch')) )
									$cell_color = 'dispatched';
								else
									$cell_color = '';	
							} else if( ! empty($layout[$key]['cell'])) {
								$cell_color = $layout[$key]['cell'];
							} else
								$cell_color = '';
							
							$output .= '<td'.$this->align($key, $layout, 'TAG_'.$key.' '.$cell_color).'>';
							if( $this->debug ) {
								echo "<h3>ZZZ key2 = $key2, pk = ".$this->table->primary_key."</h3>";
		echo "<pre>YYY\n";
		var_dump( $row[$this->table->primary_key], $key, $key2, $row[$key2], $layout[$key], $row);
		var_dump( isset($row[$key2]) );
		var_dump( in_array($key2, array_keys($row)) );
		echo "</pre>";
							}
							
							$cell_value = (in_array($key2, array_keys($row)) ? 
								$this->format( (isset($row[$this->table->primary_key]) ? $row[$this->table->primary_key] : false), $key, $row[$key2], $layout, $row) : '');
								
							if( $this->debug ) echo "<h3>ZZZ2 cell_value = $cell_value</h3>";
							if( isset($layout[$key]['maxlen']) && $layout[$key]['maxlen'] <> '' && strlen($cell_value) > $layout[$key]['maxlen'] )
								$cell_value = substr( $cell_value, 0, intval($layout[$key]['maxlen']) ).'...';
								
							if( $this->table_name == LOAD_TABLE && isset($row['STOP_TYPE']) && $row['STOP_TYPE'] == 'drop' )
								$cell_value = '<strong>'.$cell_value.'</strong>';
							
							if( $key2 == $group_by_column && $this->table_name == LOAD_TABLE ) {
								//if( $row['SEQUENCE_NO'] == '' || $row['SEQUENCE_NO'] == '1' ) {
								if( $first_load_row || $row['SEQUENCE_NO'] == '' || $row['SEQUENCE_NO'] == 1 ) {
									$first_load_row = false;
									$output .= $this->dropdown_menu($row[$key2], $edit['rowbuttons'], $row['LOAD_STATUS'],
											$row['CURRENT_STOP'], $row['STOP_STATUS'], $row['NUM_STOPS'], $row['CURRENT_STOP_TYPE'],$row).'<br>'.
										str_replace(' ', '&nbsp;', $this->table->state_name[$row['LOAD_STATUS']]);
								}
								//if( in_group( EXT_GROUP_DEBUG ) )	//!WIP
								//	$output .= '<br>LOAD '.$row[$key2].'<br>SDD: '.date("m/d H:i", strtotime($row['SDD']));
									
							} else {
								$output .= $cell_value;
							}
	
							if( in_array($this->table_name, array(STOP_TABLE, LOAD_TABLE)) && 
								in_array($key2, array('ACTUAL_ARRIVE', 'ACTUAL_DEPART')) &&
								in_array($row['STOP_STATUS'], array('Picked', 'Dropped', 'Complete')) ) {
								$output .= '<a href="exp_editstop.php?CODE='.$row['STOP_CODE'].'&LOAD_CODE='.$row['LOAD_CODE'].'"style="float: right;"><span style="font-size: 14px;"><span class="glyphicon glyphicon-edit"></span></span></a>';
							}
							
							if( in_array($this->table_name, array(STOP_TABLE, LOAD_TABLE)) &&
								$key2 == 'TRAILER_NUMBER' && isset($row['STOP_CODE']) ) {
								$output .= '&nbsp;<a href="exp_editstop_trailer.php?CODE='.$row['STOP_CODE'].'&LOAD_CODE='.$row['LOAD_CODE'].'"style="float: right;"><span style="font-size: 14px;"><span class="glyphicon glyphicon-edit"></span></span></a>';
							}

							//! SCR# 916 - add links to Add resources
							if( $this->table_name == LOAD_TABLE &&
								in_array($key2, ['TRACTOR_NUMBER', 'DRIVER_NAME2',
									'DRIVER2_NAME2', 'CARRIER_NAME']) ) {
								$output .= '&nbsp;<a href="exp_dispatch3.php?CODE='.$row['LOAD_CODE'].'"style="float: right;"><span style="font-size: 14px;"><span class="glyphicon glyphicon-plus"></span></span></a>';
								echo "<h2>t = ".$this->table_name." k = ".$key2."</h2>";
							}
							
							$output .= '</td>
							';
						}
					}
					$output .= '</tr>
					';
				}
				if( $group_rows ) {
					$output .= '<tr class="exspeedite-bg" style="height: 20px; max-height: 20px;">
						<td class="thin text-right">'.$prev_total_distance.' Miles</td>
						<td class="thin">&nbsp;</td>
						<td class="thin">&nbsp;</td>
						<td class="thin">&nbsp;</td>
						<td class="thin text-right">'.$prev_weight_head.' lb / '.$prev_weight_back.' lb</td>
						<td class="thin">&nbsp;</td>
						<td class="thin">&nbsp;</td>
						<td class="thin">&nbsp;</td>
						<td class="thin">&nbsp;</td>
						<td class="thin">&nbsp;</td>
						<td class="thin">&nbsp;</td>
						<td class="thin">&nbsp;</td>
						<td class="thin text-right">'.$prev_pallets_head.' / '.$prev_pallets_back.'</td>
						<td class="thin">&nbsp;</td>
						<td class="thin">&nbsp;</td>
						<td class="thin">&nbsp;</td>
						<td class="thin">&nbsp;</td>
						<td class="thin">&nbsp;</td>
						<td class="thin">&nbsp;</td>
						</tr>
					';	// '.$row['SUM_PALLETS'].'
				}
				
			} else {
				$output .= '<tr><td>No Data</td>';
				for( $c=1; $c < $visible_columns; $c++ )
					$output .= '<td></td>';
				$output .= '</tr>';
			}
			$output .= '</tbody>
			';
		}
		$output .= '</table>
		</div>';

		if( $use_filters_form ) $output .= '
	</form>';	

		if( $this->profiling ) {
			$this->timer->stop();
			$this->time_render = $this->timer->result();
			list($connect, $query) = $this->table->database->timer_results();
			$output .=  "<p>Connect: $connect Query: $query Render: ".$this->timer->result()."</p>";
		}
		return $output;
	}

	public function latest_result() {
		return $this->result;
	}
	
	public function get_match() {
		global $_GET, $_POST, $_SESSION;
		$match = '';
		
		$have_deleted = $this->table && $this->table->column_exists( 'ISDELETED' );

		if( $have_deleted ) {
			if ( ! isset($_POST['FILTERS_'.$this->table_name]) ) {
				$_POST['SHOW_DELETED_'.$this->table_name] = "notdel";
			}

			//! SCR# 900 - list carrier screen
			if( isset($_SESSION['SHOW_DELETED_'.$this->table_name]) ) {
				$_POST['SHOW_DELETED_'.$this->table_name] = $_SESSION['SHOW_DELETED_'.$this->table_name];
			}									
		}

		if( isset($_POST['SHOW_ACTIVE_'.$this->table_name]) && $_POST['SHOW_ACTIVE_'.$this->table_name] == "active" )
			$match .= ($match <> '' ? ' and ' : '') . 'ISACTIVE = true';
		else if( isset($_POST['SHOW_ACTIVE_'.$this->table_name]) && $_POST['SHOW_ACTIVE_'.$this->table_name] == "inactive" )
			$match .= ($match <> '' ? ' and ' : '') . 'ISACTIVE = false';
		if( isset($_POST['SHOW_DELETED_'.$this->table_name]) && $_POST['SHOW_DELETED_'.$this->table_name] == "deleted" )
			$match .= ($match <> '' ? ' and ' : '') . 'ISDELETED = true';
		else if( isset($_POST['SHOW_DELETED_'.$this->table_name]) && $_POST['SHOW_DELETED_'.$this->table_name] == "notdel" )
			$match .= ($match <> '' ? ' and ' : '') . 'ISDELETED = false';
		else if( isset($_POST['SHOW_STATUS_'.$this->table_name]) && isset( $_POST['SHOW_STATUS_'.$this->table_name] ) )
			$match .= ($match <> '' ? ' and ' : '') . 'CURRENT_STATUS = '.$_POST['SHOW_STATUS_'.$this->table_name];
		return $match;		
	}
	
	private function ajax_search( $request, $layout = false ) {
		
		if( $this->debug ) echo "<p>".__METHOD__.": entry</p>";
		$result = "";
		$search_cols = array();
		if ( is_array($request['search']) && isset($request['search']["value"]) &&
			$request['search']["value"] <> '' ) {
			foreach( $request['columns'] as $row ) {

				if( $row["searchable"] == "true" ) {	// Seachable column
					if( is_array($layout) && isset($layout[$row["data"]]) &&
						isset($layout[$row["data"]]["format"]) &&
						$layout[$row["data"]]["format"] == 'table' ) {		// table
						$search_cols[] = '(SELECT '.$layout[$row["data"]]['fields'].
					' from '.$layout[$row["data"]]['table'].
					' X WHERE X.'.$layout[$row["data"]]['key'].' = '.$this->table->table_name.'.'.
					(isset($layout[$row["data"]]['pk']) ? $layout[$row["data"]]['pk'] : $row["data"]).' '.
					(isset($layout[$row["data"]]['condition']) ? 'AND X.'.$layout[$row["data"]]['condition'] : '').
					' LIMIT 0 , 1)'." LIKE '%".$this->table->real_escape_string($request['search']["value"])."%'";

					} else if( is_array($layout) && isset($layout[$row["data"]]) &&
						isset($layout[$row["data"]]["format"]) &&
						$layout[$row["data"]]["format"] == 'date') {		// date
						$search_cols[] = 'DATE_FORMAT('.$row["data"].', "%m/%e/%Y")'." LIKE '%".$this->table->real_escape_string($request['search']["value"])."%'";
							
							
					} else if( is_array($layout) && isset($layout[$row["data"]]) &&
						isset($layout[$row["data"]]["snippet"]) ) {		// snippet
						$search_cols[] = $layout[$row["data"]]["snippet"]." LIKE '%".$this->table->real_escape_string($request['search']["value"])."%'";
					} else if( ! empty($row["data"]) ) {
						$search_cols[] = $row["data"]." LIKE '%".$this->table->real_escape_string($request['search']["value"])."%'";
					}
					if( is_array($layout) && isset($layout[$row["data"]]) &&
						isset($layout[$row["data"]]["group"])) {
						foreach( $layout[$row["data"]]["group"] as $row2 ) {
							$search_cols[] = $row2." LIKE '%".$this->table->real_escape_string($request['search']["value"])."%'";
						}
					}
				}
			}
			
			//! add in hidden, searchable columns
			if( is_array($layout) && count($layout) > 0 ) {
				foreach($layout as $k => $p) {
					if( isset($p['format']) && isset($p['searchable']) &&
						$p['format'] == 'hidden' && $p['searchable'] == true )
						$search_cols[] = $k." LIKE '%".$this->table->real_escape_string($request['search']["value"])."%'";
				}
			}
			
			$result = implode(' OR ', $search_cols);
		}
		if( $this->debug ) {
			echo "<p>".__METHOD__.": exit</p>";
			echo "<pre>";
			var_dump($result);
			echo "</pre>";
		}
		return $result;
	}

	private function ajax_order( $request ) {
		$result = "";
		$order_cols = array();

		if ( isset($request['order']) && is_array($request['order']) &&  count($request['order']) > 0) {
			foreach( $request['order'] as $row ) {
				if( $request['columns'][$row["column"]]["orderable"] == "true") {
					if( ! empty($request['columns'][$row["column"]]["data"]) )
						$order_cols[] = $request['columns'][$row["column"]]["data"]." ".$row["dir"];
					else if( ! empty($request['columns'][$row["column"]]["name"]) )
						$order_cols[] = $request['columns'][$row["column"]]["name"]." ".$row["dir"];
				}
			}
			$result = implode(', ', $order_cols);
			if( $this->debug ) echo "<p>sts_result > ajax_order: ".$result."</p>";
		}
		return $result;
	}
	
	// Get the results for return by ajax.
	public function render_ajax( $layout = false, $edit = false, $request = false ) {
		
		$my_session = sts_session::getInstance( $this->debug );
		
		//if( $this->profiling ) {
			$this->timer = new sts_timer();
			$this->timer->start();
		//}

		//! Set limit
		$limit = "";
		if ( isset($request['start']) && isset($request['length']) && $request['length'] != -1 ) {
			$limit = intval($request['start']).", ".intval($request['length']);
		}

		$match = $this->match ? $this->match : '';
		
		$search = $this->ajax_search( $request, $layout );
		if( $this->debug ) echo "<p>sts_result > render_ajax: search = $search </p>";

		if( $match == '' ) {
			if( $search <> '' )
				$match = $search;
		} else {
			if( $search <> '' && $this->table_name <> LOAD_TABLE )
				$match .= " AND (".$search.")";
		}
		
		if( $this->debug ) echo "<p>sts_result > render_ajax: match = $match </p>";

		
		$sort = $this->ajax_order( $request );
		if( $sort == '' && isset($edit['sort']) )
			$sort = $edit['sort'];
			
		if( get_class($this->table) == 'sts_mileage' && isset($request['filter']) )
			$search = $request['filter'];
		
		//! Fetch data from the table
		if( $this->debug ) echo "<p>sts_result > render_ajax: QUERY: ".number_format((float) $this->timer->split(),4)."</p>";
		if( $this->table ) {
			$this->result = $this->table->fetch_rows( $match, 
				"SQL_CALC_FOUND_ROWS ".( $layout ? $this->keys( $layout ) : '*'), 
				$sort, $limit, "", $search);
			$found_rows = $this->table->found_rows;
			$this->count_rows = is_array($this->result) ? count( $this->result ) : 0;
			if( $this->result && $this->count_rows > 0 )
				$this->keys = array_keys($this->result[0]);
			$this->count_columns = is_array( $this->keys ) ? count( $this->keys ) : 0;
		}
		
		$have_active = $this->table && $this->table->column_exists( 'ISACTIVE' ) && $this->table->column_type( 'ISACTIVE' ) <> 'enum';
		$have_deleted = $this->table && $this->table->column_exists( 'ISDELETED' );
		
		$data = array();
		
		$buttons_column = is_array($edit) && isset($edit['rowbuttons']) && is_array($edit['rowbuttons']) &&
			$this->table_name <> LOAD_TABLE;

		$visible_columns = $buttons_column ? 1 : 0;
		
		$keys = is_array($layout) ? array_keys($layout) : $this->keys;
		if( $this->debug ) echo "<p>sts_result > render_ajax: keys = [".implode(', ', $keys)."] </p>";
		if( count($keys) > 0 )
		foreach( $keys as $key )
			if( $layout[$key]['format'] <> 'hidden' ) {
				$visible_columns++;
			}

		if( isset($edit['group']) ) {
			$group_by_column = $edit['group'];
			$group_by_column = strpos($group_by_column, '.') !== false ? 
				substr( $group_by_column, strpos($group_by_column, '.') + 1 ) : $group_by_column;
			$group_rows = true;
		} else {
			$group_by_column = '';
			$group_rows = false;
		}
		
		if( $this->result && $this->count_rows > 0 ) {
			$row_num = 0;
			if( $group_rows ) {
				$prev_column_value = "START";
				$prev_pallets_head = 0;
				$prev_pallets_back = 0;
				$prev_weight_head = 0;
				$prev_weight_back = 0;
				$prev_total_distance = 0;
				$prev_mobile_time = '';
				$prev_mobile_location = '';
			}
			$first_load_row = true;
			$count_matched = 0;
			foreach( $this->result as $row ) {
				$data_row = array();
				$hidden_row = array();

				if( $group_rows && $prev_column_value <> $row[$group_by_column] && $prev_column_value <> "START") {
					$count_matched++;
					
					$data[] = array(	//! Divider 1 of 2
						'DT_RowClass' => 'exspeedite-bg-thin',
						'LOAD_CODE' => $prev_total_distance.' Miles',
						'SEQUENCE_NO' => '&nbsp;',
						'STOP_STATUS' => '&nbsp;',
						'SHIPMENT2' => '&nbsp;',
						'SS_NUMBER2' => '&nbsp;',
						'PO_NUMBER' => '&nbsp;',
						'REF_NUMBER2' => '&nbsp;',
						'NAME' => $prev_weight_head.' lb / '.$prev_weight_back.' lb',
						'DIRECTION2' => '&nbsp;',
						'APPT' => '&nbsp;',
						'STOP_ETA' => '&nbsp;',
						'DUE_TS2' => '&nbsp;',
						//'PICKUP_DATE' => '&nbsp;',
						'ACTUAL_ARRIVE' => '&nbsp;',
						'ACTUAL_DEPART' => '&nbsp;',
						'CHECKCALL' => '&nbsp;',
					//	'STBOL' => '&nbsp;',
						'BOL_NUMBER' => '&nbsp;',
						'ST_NUMBER' => '&nbsp;',
						'PALLETS' => $prev_pallets_head.' / '.$prev_pallets_back,
						'TRACTOR_NUMBER' => '&nbsp;',
						'TRAILER_NUMBER' => '&nbsp;',
						'DRIVER_NAME2' => '&nbsp;',
						'DRIVER2_NAME2' => '&nbsp;',
						'CARRIER_NAME' => '&nbsp;',
						'LUMPER_NAME' => '&nbsp;',
						'LUMPER_AMT' => '&nbsp;',
						'BILLTO_NAME' => '&nbsp;',
						'NOTES' => '&nbsp;',
						'CARRIER_NOTE' => '&nbsp;',
					);

					//if ! isset($layout['PO_NUMBER']) -> remove column...
					if( ! isset($layout['PO_NUMBER']) )
						unset($data['PO_NUMBER']);
					if( ! isset($layout['REF_NUMBER2']) )
						unset($data['REF_NUMBER2']);
					if( ! isset($layout['SS_NUMBER2']) )
						unset($data['SS_NUMBER2']);
					if( ! isset($layout['LUMPER_NAME']) )
						unset($data['LUMPER_NAME']);
					if( ! isset($layout['LUMPER_AMT']) )
						unset($data['LUMPER_AMT']);
				}

				if( $group_rows ) {
					$prev_column_value = $row[$group_by_column];
					$prev_pallets_head = intval($row['SUM_PALLETS_HEAD']);//!Assign pallets head/back
					$prev_pallets_back = intval($row['SUM_PALLETS_BACK']);
					$prev_weight_head = intval($row['SUM_WEIGHT_HEAD']);//!Assign weight head/back
					$prev_weight_back = intval($row['SUM_WEIGHT_BACK']);
					$prev_total_distance = $row['TOTAL_DISTANCE'];
					if( $row['CURRENT_STOP'] > 0 && 
					! in_array($this->table->state_behavior[$row['LOAD_STATUS']], array('cancel','complete')) ) {
						$prev_mobile_time = date("m/d/Y h:i A", strtotime($row['MOBILE_TIME']));
						$prev_mobile_location = $row['MOBILE_LOCATION'];
					} else {
						$prev_mobile_time = '';
						$prev_mobile_location = '';
					}
				} else {
					$count_matched++;
				}

				//! Filter on active stops
				if( $group_rows && $this->table_name == LOAD_TABLE &&
					in_array($row['STOP_STATUS'], array('Complete', 'Picked', 'Dropped')) &&
					$row['SEQUENCE_NO'] < $row['NUM_STOPS'] &&
					isset($_SESSION['LOAD_ACTIVE']) && $_SESSION['LOAD_ACTIVE'] == 'Active') {
					if( $row['SEQUENCE_NO'] == 1 ) $first_load_row = true;
					continue;
				}

				//! Filter on current stops
				if( $group_rows && $this->table_name == LOAD_TABLE &&
					$row['SEQUENCE_NO'] <> ($row['CURRENT_STOP'] > 0 ? $row['CURRENT_STOP'] : 1) &&
					isset($_SESSION['LOAD_ACTIVE']) && $_SESSION['LOAD_ACTIVE'] == 'Current') {
					if( $row['SEQUENCE_NO'] == 1 ) $first_load_row = true;
					continue;
				}

				$row_num++;

				//! Per-row buttons at start of row
				if( $buttons_column ) {
					$data_row[] = $this->dropdown_menu2($edit['rowbuttons'], $have_active, $have_deleted, $row);
				} else
				if( $buttons_column && $this->table_name == LOAD_TABLE) {
					$buttons_visible = 0;
					if( ! $group_rows || $prev_column_value <> $row[$group_by_column] ) {
						$buttons_str = '<div class="btn-group btn-group-xs" role="group">';
						$id = 0;
						foreach( $edit['rowbuttons'] as $edit_item ) {
							if( $this->debug && $have_deleted ) {
								echo "<p>sts_result > render_ajax: have_deleted, edit_item, deleted = </p>
								<pre>";
								var_dump($have_deleted, $edit_item, $row['ISDELETED']);
								echo "</pre>";
							}
	
							$button_restricted = false;
							if( isset($edit_item['restrict']) ) {
								$button_restricted = ! $my_session->in_group( $edit_item['restrict'] );
							}
							
							if( ! $button_restricted &&
								(! isset($edit_item['showif']) ||
								( $edit_item['showif'] == 'notdeleted' && $have_deleted && ! $row['ISDELETED'] ) ||
								( $edit_item['showif'] == 'deleted' && $have_deleted && $row['ISDELETED'] ) ||
								( $edit_item['showif'] == 'notfirst' && $row_num > 1 ) ||
								( $edit_item['showif'] == 'notlast' && $row_num < $this->count_rows ) ||( $edit_item['showif'] == 'Dropped' && $row['CURRENT_STATUS']=='Dropped')
								) ) {
							
								$buttons_visible++;
								$confirm = isset($edit_item['confirm']) && isset($edit_item['confirm']) == 'yes';
								$buttons_str .= '<a ';
								
								if( ! $confirm )
									$buttons_str .= 'href="'.$edit_item['url'].urlencode($row[$edit_item['key']]).
									'"';
								
								$buttons_str .= 'class="btn btn-default btn-xs '.($confirm ? 'confirm' : 'inform').'"  id="'.$row[$edit_item['label']].(++$id).'" data-placement="bottom" data-toggle="popover" data-content="';
								
								if( $confirm )
									$buttons_str .= '<a id=&quot;'.$row[$edit_item['label']].($id).'_confirm&quot; class=&quot;btn btn-danger btn-sm&quot; href=&quot;'.
									$edit_item['url'].urlencode($row[$edit_item['key']]).'&quot;>'.
									'<span class=&quot;'.$edit_item['icon'].'&quot;></span> '.
									$edit_item['tip'].$row[$edit_item['label']].'</a>'.
									(isset($edit_item['tip2']) ? '<br>'.$edit_item['tip2'] : '').
									'<br>There is no undo';
								else
									$buttons_str .= $edit_item['tip'].$row[$edit_item['label']];
								$buttons_str .= '">'.'<span style="font-size: 14px;"><span class="'.$edit_item['icon'].'"></span></span></a>';
							}
						}
						$buttons_str .= '</div>
						';
					}
					$data_row[] = '<div style="width: '.(6+$buttons_visible*28).'px;">
						'.$buttons_str.'</div>';
				}

				//if( $this->debug ) echo "<p>sts_result > render_ajax: seq = ".$row['SEQUENCE_NO']."</p>";
				//! Columns data
				foreach( (is_array($layout) ? array_keys($layout) : $this->keys) as $key ) {
					//if( $this->debug ) echo "<p>sts_result > render_ajax: key = $key ".strpos($key, '.')." ".substr( $key, strpos($key, '.') + 1 )."</p>";
					$key2 = strpos($key, '.') !== false ? substr( $key, strpos($key, '.') + 1 ) : $key;
					//if( $this->debug ) echo "<p>key2 = $key2</p>";
					// ! Visible collumn
					if( $layout[$key]['format'] <> 'hidden' ) {
						if( $this->table_name == LOAD_TABLE && $key2 == 'STOP_STATUS' &&
							isset($row[$key2]) &&
							in_array($row[$key2], array('Complete', 'Picked', 'Dropped', 'Docked')) )
							$cell_color = 'success2';
						else if( $this->table_name == LOAD_TABLE && $key2 == $group_by_column &&
							( $first_load_row || $row['SEQUENCE_NO'] == '' || $row['SEQUENCE_NO'] == '1' ) ) {
							if( isset($row['LOAD_STATUS']) &&
								in_array($this->table->state_behavior[$row['LOAD_STATUS']], array('arrive shipper', 'depart shipper', 'arrshdock', 'depshdock')) )
								$cell_color = 'inprogress';
							else if( isset($row['LOAD_STATUS']) &&
								in_array($this->table->state_behavior[$row['LOAD_STATUS']], array('arrive cons', 'depart cons', 'arrrecdock', 'deprecdock')) )
								$cell_color = 'inprogress2';
							else if( isset($row['LOAD_STATUS']) &&
								in_array($this->table->state_behavior[$row['LOAD_STATUS']], array('complete')) )
								$cell_color = 'success2';
							else if( isset($row['LOAD_STATUS']) &&
								in_array($this->table->state_behavior[$row['LOAD_STATUS']], array('dispatch')) )
								$cell_color = 'dispatched';
							else
								$cell_color = '';	
						} else
							$cell_color = '';
						
						//$output .= '<td'.$this->align($key, $layout).' class="TAG_'.$key.' '.$cell_color.'">';
						if( $this->debug ) echo "<p>sts_result > render_ajax: key2 = $key2, pk = ".$this->table->primary_key." ".number_format((float) $this->timer->split(),4)."</p>";
						$cell_value = (isset($row[$key2]) ? $this->format($row[$this->table->primary_key], $key, $row[$key2], $layout, $row) : '');//! XYZZY#1
						if( isset($layout[$key]['maxlen']) && $layout[$key]['maxlen'] <> '' && strlen($cell_value) > $layout[$key]['maxlen'] )
							$cell_value = substr( $cell_value, 0, intval($layout[$key]['maxlen']) ).'...';
							
						if( $this->table_name == LOAD_TABLE && isset($row['STOP_TYPE']) && $row['STOP_TYPE'] == 'drop' )
							$cell_value = '<strong>'.$cell_value.'</strong>';
						
						if( $key2 == $group_by_column && $this->table_name == LOAD_TABLE ) {
							//if( $row['SEQUENCE_NO'] == '' || $row['SEQUENCE_NO'] == '1' ) {
							if( $first_load_row || $row['SEQUENCE_NO'] == '' || $row['SEQUENCE_NO'] == 1 ) {
								$first_load_row = false;
								$data_row[$key2] = $this->dropdown_menu($row[$key2],
									$edit['rowbuttons'], $row['LOAD_STATUS'],
									$row['CURRENT_STOP'], $row['STOP_STATUS'],
									$row['NUM_STOPS'], $row['CURRENT_STOP_TYPE'], $row).'<br>'.
									str_replace(' ', '&nbsp;', $this->table->state_name[$row['LOAD_STATUS']]).
								($this->multi_company && isset($row['OFFICE_NAME']) && strlen($row['OFFICE_NAME']) > 0 ? '<br>'.$row['OFFICE_NAME'] : ''); //! Multi-company - show office name
							} else {
								$data_row[$key2] = '';
							}
							//if( in_group( EXT_GROUP_DEBUG ) )	//!WIP
							//	$output .= '<br>LOAD '.$row[$key2].'<br>SDD: '.date("m/d H:i", strtotime($row['SDD']));
								
						} else if( ! $group_rows && $key2 == 'LOAD_CODE' && $this->table_name == LOAD_TABLE && get_class($this->table) <> 'sts_mileage' &&
						isset($edit['rowbuttons']) ) {
							
							$data_row[$key2] = $this->dropdown_menu($row[$key2], $edit['rowbuttons'],
								$row['LOAD_STATUS'], $row['CURRENT_STOP'], $row['CURRENT_STOP_STATUS'],
								$row['NUM_STOPS'], $row['CURRENT_STOP_TYPE'], $row).
								($this->multi_company && strlen($row['OFFICE_NAME']) > 0 ? '<br>'.$row['OFFICE_NAME'] : ''); //! Multi-company - show office name
						} else {
							$data_row[$key2] = $cell_value;
						}
						
						// !Preserve alignment if present
						if( false && isset($layout[$key]['align']) && in_array($layout[$key]['align'], $this->valid_alignments) ) {
							$data_row[$key2] = '<div'.$this->align($key, $layout).'>'.$data_row[$key2].'</div>';
						}

						if( in_array($this->table_name, array(STOP_TABLE, LOAD_TABLE)) && 
							in_array($key2, array('ACTUAL_ARRIVE', 'ACTUAL_DEPART')) &&
							in_array($row['STOP_STATUS'], array('Picked', 'Dropped', 'Complete')) ) {
							$data_row[$key2] .= '<a href="exp_editstop.php?CODE='.$row['STOP_CODE'].'&LOAD_CODE='.$row['LOAD_CODE'].'"style="float: right;"><span style="font-size: 14px;"><span class="glyphicon glyphicon-edit"></span></span></a>';
						}
						
						if( in_array($this->table_name, array(STOP_TABLE, LOAD_TABLE)) &&
							$key2 == 'TRAILER_NUMBER' && isset($row['STOP_CODE']) ) {
							$data_row[$key2] .= '&nbsp;<a href="exp_editstop_trailer.php?CODE='.$row['STOP_CODE'].'&LOAD_CODE='.$row['LOAD_CODE'].'"style="float: right;"><span style="font-size: 14px;"><span class="glyphicon glyphicon-edit"></span></span></a>';
						}
						
						//! SCR# 916 - add links to Add resources
						if( $this->table_name == LOAD_TABLE &&
							in_array($key2, ['TRACTOR_NUMBER', 'DRIVER_NAME2',
								'DRIVER2_NAME2', 'CARRIER_NAME']) ) {
							$data_row[$key2] .= '&nbsp;<a href="exp_dispatch3.php?CODE='.$row['LOAD_CODE'].'"style="float: right;"><span style="font-size: 14px;"><span class="glyphicon glyphicon-plus"></span></span></a>';
					//		echo "<h2>t = ".$this->table_name." k = ".$key2."</h2>";
						}
							
					} else {
						//! hidden column
						if( isset($key2) && isset($row[$key2]) )
							//$hidden_row[$key2] = $row[$key2];
							//! WIP77 - certain fields (like MEDIUMTEXT in EXP_SCR.DESCRIPTION)
							// contain invalid encoded text.
							$hidden_row[$key2] = mb_convert_encoding($row[$key2], 'UTF-8', 'UTF-8');
						else
							$hidden_row[$key2] = "0";
					}
				}
				if( count($hidden_row) > 0 )
					$data_row["DT_RowAttr"] = $hidden_row;
				$data[] = $data_row;
				unset($data_row);
			}
			
			if( $group_rows ) {
				$count_matched++;
				$data[] = array(	//! Divider 2 of 2
					'DT_RowClass' => 'exspeedite-bg-thin',
					'LOAD_CODE' => $prev_total_distance.' Miles',
					'SEQUENCE_NO' => '&nbsp;',
					'STOP_STATUS' => '&nbsp;',
					'SHIPMENT2' => '&nbsp;',
					'SS_NUMBER2' => '&nbsp;',
					'PO_NUMBER' => '&nbsp;',
					'REF_NUMBER2' => '&nbsp;',
					'NAME' => $prev_weight_head.' lb / '.$prev_weight_back.' lb',
					'DIRECTION2' => '&nbsp;',
					'APPT' => '&nbsp;',
					'STOP_ETA' => '&nbsp;',
					'DUE_TS2' => '&nbsp;',
					//'PICKUP_DATE' => '&nbsp;',
					'ACTUAL_ARRIVE' => '&nbsp;',
					'ACTUAL_DEPART' => '&nbsp;',
					'CHECKCALL' => '&nbsp;',
				//	'STBOL' => '&nbsp;',
					'BOL_NUMBER' => '&nbsp;',
					'ST_NUMBER' => '&nbsp;',
					'PALLETS' => $prev_pallets_head.' / '.$prev_pallets_back,
					'TRACTOR_NUMBER' => '&nbsp;',
					'TRAILER_NUMBER' => '&nbsp;',
					'DRIVER_NAME2' => '&nbsp;',
					'DRIVER2_NAME2' => '&nbsp;',
					'CARRIER_NAME' => '&nbsp;',
					'LUMPER_NAME' => '&nbsp;',
					'LUMPER_AMT' => '&nbsp;',
					'BILLTO_NAME' => '&nbsp;',
					'NOTES' => '&nbsp;',
					'CARRIER_NOTE' => '&nbsp;',
				);

					//if ! isset($layout['PO_NUMBER']) -> remove column...
					if( ! isset($layout['PO_NUMBER']) )
						unset($data['PO_NUMBER']);
					if( ! isset($layout['REF_NUMBER2']) )
						unset($data['REF_NUMBER2']);
					if( ! isset($layout['SS_NUMBER2']) )
						unset($data['SS_NUMBER2']);
					if( ! isset($layout['LUMPER_NAME']) )
						unset($data['LUMPER_NAME']);
					if( ! isset($layout['LUMPER_AMT']) )
						unset($data['LUMPER_AMT']);
			}
		}
		
		//! assemble response
		// Issue when searching in fields, can't use alternate table.
		$tbl = $this->table2 ? $this->table2 : $this->table;
		//$dummy = $tbl->fetch_rows( $match, "SQL_CALC_FOUND_ROWS ".$tbl->primary_key, 
		//		false, $limit);

		//$result1 = $tbl->database->get_one_row( "SELECT FOUND_ROWS() AS FOUND" );
		//$found_rows = is_array($result1) && isset($result1["FOUND"]) ? $result1["FOUND"] : 0;

		if( isset($this->table->total_rows)) {
			$total_rows = $this->table->total_rows;
		} else {
			//$result2 = $tbl->fetch_rows( $match, "COUNT(*) AS CNT" );
			// This will show  (filtered from X total entries) on the list screen
			$result2 = $tbl->fetch_rows( '', "COUNT(*) AS CNT" );
			$total_rows = is_array($result2) && isset($result2[0]) &&
				isset($result2[0]["CNT"]) ? $result2[0]["CNT"] : 0; 
		}

		$this->timer->stop();
		if( $this->profiling ) {
			$this->time_render = $this->timer->result();
			list($connect, $query) = $this->table->database->timer_results();
			echo  "<p>sts_result > render_ajax: Connect: $connect Query: $query Render: ".number_format((float) $this->timer->result(),4)." Dropdown ".number_format((float) $this->time_render_dropdown,4)."  Format ".number_format((float) $this->time_format,4)."</p>";
		}

		if( $this->debug ) echo "<p>sts_result > render_ajax: RESPONSE ".number_format((float) $this->timer->split(),4)."</p>";
		return array(
			"draw" => intval( $request['draw'] ),
			"recordsTotal" => intval( $total_rows ),
			"recordsFiltered" => intval( $found_rows ),
			"timing" => $this->timer->result(),
			"data" => $data
		);
	}
	
	public function render_currency_menu( $selected  = 'USD' ) {
		$output = '';
		if( $this->multi_currency ) {
			$output =  '<select class="form-control" style="display: inline-block; margin-bottom: 0; vertical-align: middle; width: auto;" name="CURRENCY" id="CURRENCY" >
				<option value="USD"'.($selected == 'USD' ? ' selected' : '').'>USD</option>
				<option value="CAD"'.($selected == 'CAD' ? ' selected' : '').'>CAD</option>
			</select>';
		}
		return $output;
	}
	
	public function render_currency( $selected  = false ) {
		$output = '';
		if( $this->multi_currency ) {
			$output .= ' ['.$selected.']';
		}
		return $output;
	}

	public function render_driver_rate_screen($edit)
	{//echo '<pre/>';print_r($edit);
	 if(count($edit['all_profile_id'])>0)
	 {
		 $profile_option='';
		 foreach($edit['all_profile_id'] as $pro)
		 {$selected=''; 
		   if( isset($edit['my_profile_id']) && count($edit['my_profile_id'])>0) {
			 if($pro['CONTRACT_ID']==$edit['my_profile_id']['PROFILE_ID']){$selected='selected="selected"';}}
			 $profile_option.="<option value='".$pro['CONTRACT_ID']."'  ".$selected.">".$pro['CONTRACT_ID']."&nbsp;- &nbsp;".ucfirst(stripslashes($pro['PROFILE_NAME']))."</option>";
		 }
	 }
	 else
	 {
		 $profile_option=''; 
	 }
		if(count($edit['driver_rates']) >0)
		{
			$rate_str='';
				foreach($edit['driver_rates'] as $row)
				{
					$rate_str.='
					<tr style="cursor:pointer;" >
					<td class="text-center"><input type="checkbox"  name="rate_ids[]" value="'.$row['RATE_ID'].'"></td>
					<td style="text-align:center;">  '.$row['CATEGORY_NAME'].'</td>
					<td style="text-align:center;">  '.$row['RATE_CODE'].'</td>
					<td style="text-align:center;">  '.$row['RATE_NAME'].'</td>
					<td style="text-align:center;">  '.$row['RATE_DESC'].'</td>
					<td style="text-align:center;">$  '.$row['RATE_PER_MILES'].'</td>
					 <td style="text-align:center;">  '.($row['RATE_BONUS'] ? '<span class="glyphicon glyphicon-check"></span>' : '<span class="text-muted"><span class="glyphicon glyphicon-unchecked"></span>').'</td>
					 <td style="text-align:center;">  '.$row['ZONES'].'</td>
				  </tr>	';
				}
		}
		$driver_str='';		 
		if(count($edit['drivers']) >0)
		{
			$driver_str='';
				foreach($edit['drivers'] as $row)
				{
					$driver_str.='
					<tr style="cursor:pointer;" onclick="javascript:return show_driver_rates('.$row["DRIVER_CODE"].');">
					<td class="text-center"><span class="glyphicon glyphicon-check"></span></td>
					<td>'.$row["DRIVER_CODE"].'</td>
					<td>'.$row["FIRST_NAME"].'</td>
					<td>'.$row["MIDDLE_NAME"].'</td>
					<td>'.$row["LAST_NAME"].'</td>
				  </tr>
					';
				}
		}
		else
		{
			$driver_str.='
					<tr style="cursor:pointer;" >
					<td class="text-center" colspan="4">No Records Found.</td>
				  </tr>
					';	
		}
		
	
			$driver_manual_rates_str='';
			 if(count($edit['manual_rates'])>0)
			 {
				 foreach($edit['manual_rates'] as $man_rates)
				 {
					$driver_manual_rates_str .=' <tr>
																	<td>
																	<a class="btn btn-default btn-xs " onclick="javascript: return delete_manual('.$man_rates["MANUAL_ID"].')"><span class="glyphicon glyphicon-trash"></span></a>	<a class="btn btn-default btn-xs " data-toggle="modal" data-target="#modal_edit'.$man_rates["MANUAL_ID"].'"><span class="glyphicon glyphicon-edit"></span></a>
																	</td>
																	<td style="font-size:12px; font-family:Arial, Helvetica, sans-serif; text-transform:uppercase; padding:0 6px;">Code</td>
																	<td><input type="text" name="textfield" id="textfield" size="15" class="form-control" value="'.$man_rates['MANUAL_RATE_CODE'].'" readonly="readonly"></td>
																	<td style="font-size:12px; font-family:Arial, Helvetica, sans-serif; text-transform:uppercase; padding:0 6px;">Name</td>
																	<td><input type="text" name="textfield" id="textfield" size="15" class="form-control" value="'.$man_rates['MANUAL_NAME'].'" readonly="readonly"></td>
																	<td style="font-size:12px; font-family:Arial, Helvetica, sans-serif; text-transform:uppercase; padding:0 6px;">Description</td>
																	<td><input type="text" name="textfield" id="textfield" size="15" class="form-control" value="'.$man_rates['MANUAL_RATE_DESC'].'" readonly="readonly"></td>
																	
																	<td style="font-size:12px; font-family:Arial, Helvetica, sans-serif; text-transform:uppercase; padding:0 6px;">Rates</td>
																	<td><input type="text" name="textfield" id="textfield" size="15" class="form-control" value="'.$man_rates['MANUAL_RATE_RATE'].'" readonly="readonly"></td>
																	<td style="font-size:12px; font-family:Arial, Helvetica, sans-serif; text-transform:uppercase; padding:0 6px;">&nbsp;</td>
																	
																	</tr>
																	<tr>
																	<td style="font-size:12px; font-family:Arial, Helvetica, sans-serif; text-transform:uppercase; padding:0 6px;" colspan="6">&nbsp;</td>
																	</tr>
																	<tr>
																	<td>
																	  <div class="modal fade in" id="modal_edit'.$man_rates['MANUAL_ID'].'" tabindex="-1" role="dialog">

<div class="modal-dialog">
<div class="modal-content form-horizontal" role="main">
<div class="modal-header">
<h4 class="modal-title">Edit Manual Rate</h4>
</div>
<div class="modal-body" style="font-size: 14px; body:inherit;">
<form name="form_edit_manual" id="form_edit_manual" action="exp_driverrate.php?id='.$edit['max_driver_id'][0]['MAX(DRIVER_CODE)'].'" method="post">
<div class="btn-group">
<a href="#" class="btn btn-md btn-default" data-dismiss="modal"><span class="glyphicon glyphicon-remove"></span>Close</a>
<input type="submit" name="edt_man_rate" id="edt_man_rate" class="btn btn-md btn-success" onclick="javascript:return check_edit_manual('.$man_rates["MANUAL_ID"].');"  value="Save Changes"/>
</div>

<div class="form-group">
<input class="form-control" name="ed_driver_id" id="ed_driver_id" type="hidden" placeholder="Manual Code" value="'.$edit['max_driver_id'][0]['MAX(DRIVER_CODE)'].'">
<input class="form-control" name="ed_man_id" id="ed_man_id" type="hidden" placeholder="Manual Code" value="'.$man_rates["MANUAL_ID"].'">
		<div class="form-group">
				<label for="Profile" class="col-sm-4 control-label">CODE</label>
				<div class="col-sm-4">
			   <input class="form-control" name="ed_man_code" id="ed_man_code'.$man_rates["MANUAL_ID"].'" type="text" placeholder="Manual Code" value="'.$man_rates["MANUAL_RATE_CODE"].'" readonly="readonly">
				</div>
			</div>
            
            <div class="form-group">
				<label for="Name" class="col-sm-4 control-label"> Name</label>
				<div class="col-sm-4">
			   <input class="form-control" name="ed_man_name" id="ed_man_name'.$man_rates["MANUAL_ID"].'" type="text" placeholder=" Name" value="'.$man_rates["MANUAL_NAME"].'">
				</div>
			</div>
            
            <div class="form-group">
				<label for="Description" class="col-sm-4 control-label">Description</label>
				<div class="col-sm-4">
			   <input class="form-control" name="ed_man_desc" id="ed_man_desc'.$man_rates["MANUAL_ID"].'" type="text" placeholder="Description" value="'.$man_rates["MANUAL_RATE_DESC"].'" >
				</div>
			</div>	

	
<div class="form-group">
				<label for="Rates" class="col-sm-4 control-label">Rates</label>
				<div class="col-sm-4">
			   <input class="form-control" name="ed_man_rate" id="ed_man_rate'.$man_rates["MANUAL_ID"].'" type="text" placeholder="Manual Rate" value="'.$man_rates["MANUAL_RATE_RATE"].'">
				</div>
			</div>
			</div>
			</form>
</div>
<div class="modal-footer">

</div>
</div><!-- /.modal-content -->
</div><!-- /.modal-dialog -->

</div>

							
																	</td>
																	</tr>
																	';
				 }
			 }
			 else
			 {
					$driver_manual_rates_str .='<tr>
																	<td style="font-size:12px; font-family:Arial, Helvetica, sans-serif; text-transform:uppercase; padding:0 6px;" colspan="6">No Records Found.</td>
																	</tr>
																	'; 
			 }
			 $driver_range_rate_title=$driver_range_rates_str='';
			 if(count($edit['range_rates'])>0)
			 {
				$driver_range_rate_title='<tr>
																<td style="font-size:12px; font-family:Arial, Helvetica, sans-serif; text-transform:uppercase; padding:0 6px;">&nbsp;</td>
																<td style="font-size:12px; font-family:Arial, Helvetica, sans-serif; text-transform:uppercase; padding:0 6px;">CODE</td>
																<td style="font-size:12px; font-family:Arial, Helvetica, sans-serif; text-transform:uppercase; padding:0 6px;">NAME</td>
																<td style="font-size:12px; font-family:Arial, Helvetica, sans-serif; text-transform:uppercase; padding:0 6px;">FROM</td>
																<td style="font-size:12px; font-family:Arial, Helvetica, sans-serif; text-transform:uppercase; padding:0 6px;">TO</td>
																<td style="font-size:12px; font-family:Arial, Helvetica, sans-serif; text-transform:uppercase; padding:0 6px;">FROM ZONE</td>
																<td style="font-size:12px; font-family:Arial, Helvetica, sans-serif; text-transform:uppercase; padding:0 6px;">TO ZONE</td>
																<td style="font-size:12px; font-family:Arial, Helvetica, sans-serif; text-transform:uppercase; padding:0 6px;">Rates</td>
																</tr>';
				foreach($edit['range_rates'] as $ran_rates)
				 {$dr_frm_zone=$dr_to_zone='';
					 if(count($edit['zones'])>0)
					 {
						 foreach($edit['zones'] as $drz)
						 {$sel='';
						 if($drz['ZF_NAME']!="SELECT")
						 {
							 if($drz['ZF_NAME']==$ran_rates['ZONE_FROM'])
							 {$sel='selected="selected"';}
					    	$dr_frm_zone.='<option value="'.$drz['ZF_NAME'].'" '.$sel.'>'.$drz['ZF_NAME'].'</option>';
						 }
						 }
						 
						 foreach($edit['zones'] as $dz)
						 {$to_sel='';
						  if($dz['ZF_NAME']!="SELECT")
						 {
							if($dz['ZF_NAME']==$ran_rates['ZONE_TO'])
							 {$to_sel='selected="selected"';}
					    	$dr_to_zone.='<option value="'.$dz['ZF_NAME'].'" '.$to_sel.'>'.$dz['ZF_NAME'].'</option>';
						 }
						 }
					 }
					$driver_range_rates_str .=' 
																<tr>
															
																	<td><a class="btn btn-default btn-xs " href="javascript:void(0);" onclick="javascript: return delete_range('.$ran_rates["RANGE_ID"].')" ><span class="glyphicon glyphicon-trash"></span></a>
																	<a class="btn btn-default btn-xs " href="javascript:void(0);" data-toggle="modal" data-target="#modal_ed_range'.$ran_rates["RANGE_ID"].'"  ><span class="glyphicon glyphicon-edit"></span></a>
																	</td>
																	
																	<td style="padding:0 3px;"><input type="text" name="textfield" id="textfield" size="15" class="form-control" value="'.$ran_rates['RANGE_CODE'].'" readonly="readonly"></td>
																	
																	<td style="padding:0 3px;"><input type="text" name="textfield" id="textfield" size="15" class="form-control" value="'.$ran_rates['RANGE_NAME'].'" readonly="readonly"></td>
																	
																	<td style="padding:0 3px;"><input type="text" name="textfield" id="textfield" size="15" class="form-control" value="'.$ran_rates['RANGE_FROM'].'" readonly="readonly"></td>
																	
																	<td style="padding:0 3px;"><input type="text" name="textfield" id="textfield" size="15" class="form-control" value="'.$ran_rates['RANGE_TO'].'" readonly="readonly"></td>
																	
																	<td style="padding:0 3px;"><input type="text" name="textfield" id="textfield" size="15" class="form-control" value="'.$ran_rates['ZONE_FROM'].'" readonly="readonly"></td>
																	
																	<td style="padding:0 3px;"><input type="text" name="textfield" id="textfield" size="15" class="form-control" value="'.$ran_rates['ZONE_TO'].'" readonly="readonly"></td>
																	
																	<td style="padding:0 3px;"><input type="text" name="textfield" id="textfield" size="15" class="form-control" value="'.$ran_rates['RANGE_RATE'].'" readonly="readonly"></td>
																	<td style="font-size:12px; font-family:Arial, Helvetica, sans-serif; text-transform:uppercase; padding:0 6px;">&nbsp;</td>
																	
																	</tr>
																	<tr>
																	<td style="font-size:12px; font-family:Arial, Helvetica, sans-serif; text-transform:uppercase; padding:0 6px;" colspan="6">&nbsp;</td>
																	</tr>
																		<tr>
										<td>
										  <div class="modal fade in" id="modal_ed_range'.$ran_rates['RANGE_ID'].'" tabindex="-1" role="dialog">

<div class="modal-dialog">
<div class="modal-content form-horizontal" role="main">
<div class="modal-header">
<h4 class="modal-title">Edit Range Rate</h4>
</div>
<div class="modal-body" style="font-size: 14px; body:inherit;">
<form name="form_edit_range" id="form_edit_range'.$ran_rates['RANGE_ID'].'" action="exp_driverrate.php?id='.$edit['max_driver_id'][0]['MAX(DRIVER_CODE)'].'" method="post">
<div class="btn-group">
<a href="#" class="btn btn-md btn-default" data-dismiss="modal"><span class="glyphicon glyphicon-remove"></span>Close</a>
<input type="submit" name="edit_range_rate" id="edit_range_rate" class="btn btn-md btn-success" onclick="javascript:return check_edit_driver_range('.$ran_rates['RANGE_ID'].','.$edit['max_driver_id'][0]['MAX(DRIVER_CODE)'].');"  value="Save Changes"/>
</div>
<input type="hidden" name="ed_range_id" id="ed_range_id" value="'.$ran_rates['RANGE_ID'].'"/>
<input type="hidden" name="ed_driver_code" id="ed_driver_code" value="'.$edit['max_driver_id'][0]['MAX(DRIVER_CODE)'].'"/>
<div class="form-group">

<div id="edit_loader" style="display:none;"><img border="1" style="position:absolute; margin-left:250px;margin-top:10px;" src="images/loading.gif"></div>
		<div class="form-group">
				<label for="Profile" class="col-sm-4 control-label">CODE</label>
				<div class="col-sm-4">
			   <input class="form-control" name="ed_ran_code" id="ed_ran_code'.$ran_rates['RANGE_ID'].'" type="text" placeholder="Manual Code" value="'.$ran_rates['RANGE_CODE'].'" readonly="readonly">
				</div>
			</div>
            
            <div class="form-group">
				<label for="Name" class="col-sm-4 control-label"> Name</label>
				<div class="col-sm-4">
			   <input class="form-control" name="ed_ran_name" id="ed_ran_name'.$ran_rates['RANGE_ID'].'" type="text" placeholder=" Name" value="'.$ran_rates['RANGE_NAME'].'">
				</div>
			</div>
            
            <div class="form-group">
				<label for="Description" class="col-sm-4 control-label">From</label>
				<div class="col-sm-4">
			  <input class="form-control" name="ed_ran_frm" id="ed_ran_frm'.$ran_rates['RANGE_ID'].'" type="text" placeholder=" From Range" value="'.$ran_rates['RANGE_FROM'].'" >
				</div>
			</div>	
            
               <div class="form-group">
				<label for="Description" class="col-sm-4 control-label">To</label>
				<div class="col-sm-4">
			    <input class="form-control" name="ed_ran_to" id="ed_ran_to'.$ran_rates['RANGE_ID'].'" type="text" placeholder=" To Range" value="'.$ran_rates['RANGE_TO'].'">
				</div>
			</div>	
            
               <div class="form-group">
				<label for="Description" class="col-sm-4 control-label">From Zone</label>
				<div class="col-sm-4">
			   <select name="ed_ran_frm_zone" id="ed_ran_frm_zone'.$ran_rates['RANGE_ID'].'" class="form-control">
               <option value="">From</option>
              '.$dr_frm_zone.'
               </select>
				</div>
			</div>	
            
              <div class="form-group">
				<label for="Description" class="col-sm-4 control-label">To Zone</label>
				<div class="col-sm-4">
			   <select name="ed_ran_to_zone" id="ed_ran_to_zone'.$ran_rates['RANGE_ID'].'" class="form-control">
               <option value="">To</option>
               '.$dr_to_zone.'
               </select>
				</div>
			</div>	

	
<div class="form-group">
				<label for="Rates" class="col-sm-4 control-label">Rates</label>
				<div class="col-sm-4">
			   <input class="form-control" name="ed_ran_rate" id="ed_ran_rate'.$ran_rates['RANGE_ID'].'" type="text" placeholder="Range Rate" value="'.$ran_rates['RANGE_RATE'].'" >
				</div>
			</div>
			</div>
			</form>
</div>
<div class="modal-footer">

</div>
</div><!-- /.modal-content -->
</div><!-- /.modal-dialog -->

</div>

										</td>
										</tr>
																	
									<tr>
										<td style="font-size:12px; font-family:Arial, Helvetica, sans-serif; text-transform:uppercase; padding:0 6px;" colspan="6">&nbsp;</td>
									</tr>
																	';
				 } 
				 $driver_range_rates_str=$driver_range_rate_title.$driver_range_rates_str;
			 }
			 else
			 {
					 $driver_range_rates_str .='<tr>
																	<td style="font-size:12px; font-family:Arial, Helvetica, sans-serif; text-transform:uppercase; padding:0 6px;" colspan="7">No Records Found.</td>
																	</tr>
																	';
			 }
/* Zone dropdown*/			 
			$driver_zone_range_str='';
			if(count($edit['zones'])>0)
			{ 
				foreach($edit['zones'] as $zone)
				 {
                 if($zone['ZF_NAME']!="SELECT")
                   {
					 $driver_zone_range_str .='<option value="'.$zone['ZF_NAME'].'">'.$zone['ZF_NAME'].'</option>';
                    }
				 } 
			 }
			else
			{
					 $driver_zone_range_str .='';
			 }			
			
/* Zone dropdown end here */		
		if($edit['is_selected']=='Yes'){
			 echo '<div class="container-full" role="main">
  <form class="form-inline" role="form" action="/trunk/exp_listdriver.php"  	method="post" enctype="multipart/form-data" 	name="RESULT_FILTERS_EXP_DRIVER" id="RESULT_FILTERS_EXP_DRIVER">
    <input name="FILTERS_EXP_DRIVER" type="hidden" value="on">
    <h3> <img src="images/driver_icon.png" alt="driver_icon" height="24" > &nbsp; Drivers &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; <img height="29" alt="tractor_icon" src="images/money_bag.png"> &nbsp;Driver Rates </h3>';
	
	echo '
  </form>
   ';
  
  echo '
  <div class="md_row">
    <div class="well_md_l">
      <div class="well  well-md" ><!--style="height:500px; overflow:auto;float:left;width:48%;"-->
        <div class="table-responsive" style=" ">
          <table class="table table-striped table-condensed table-bordered table-hover" >
            <thead>
              <tr class="exspeedite-bg"> 
                <!--  <th></th>-->
                <th class="text-center">Active</th>
                <!-- <th class="text-center">Del</th>-->
				<th>Code</th>
                <th>First</th>
                <th>Middle</th>
                <th>Last</th>
              </tr>
            </thead>
            <tbody>
              '.$driver_str.'
            </tbody>
          </table>
        </div>
      </div>
    </div>';
	
	echo '
    <div class="well_md_r" id="driver_rate_div">
      <div class="well  well-md"><!--style="height:500px; overflow:auto; float:right; width:48%;"-->
        <div class="table-responsive" style="overflow:auto;  ">
          <h4><div style="float:left; width: 360px;"><img height="16" alt="tractor_icon" src="images/driver_icon.png"> Driver : '.$edit['driver_name'].'</div> <br/>
		  <div id="reload_drivers_rates_div" style="position: absolute;display:none;"><img src="images/loading.gif" border="1" style="position:absolute; margin-left:250px;margin-top:10px;"></div></h4>
		 <h4><div style=" width:200px; ">Contract No :<select id="sel_profile" class="form-control"  name="sel_profile" onchange="return assign_profile(this.value,'.$edit['max_driver_id'][0]['MAX(DRIVER_CODE)'].');">
		 <option value="">Select Profile ID</option>
		 '.$profile_option.'
		 </select></div>
		
		 <div style=" width:140px; float:right;margin-top:-36px;"><button id="addtoprofile" class="btn btn-sm btn-success" type="button"  name="addtoprofile" data-toggle="modal" data-target="#myModal_profile" onclick="javascript:return add_rating_to_profile('.$edit['max_driver_id'][0]['MAX(DRIVER_CODE)'].');"><span class="glyphicon glyphicon-ok"></span>Add to Rate Profile </button></div>
		 </h4>
          <table class="table table-striped table-condensed table-bordered table-hover" >
           <thead>
              <tr class="exspeedite-bg">
                <th class="text-center"><span class="text-muted"><span class="glyphicon glyphicon-unchecked"></span></th>
				<th class="text-center">Rate Code</th>
				<th class="text-center">Rate Name</th>
                <th class="text-center">Description</th>
                <th class="text-center">Rate</th>
                <th class="text-center">Bonus</th>
              </tr>
            </thead>
            <tbody>
             '.$edit['driver_assign_rates_str'].'              
            </tbody>
          </table>';
		  
		  echo '
		  
		  <div class="modal fade in" id="myModal_profile" tabindex="-1" role="dialog">

<div class="modal-dialog">
<div class="modal-content" role="main">
<div class="modal-header">
<h4 class="modal-title">Add Profile Name</h4>
</div>
<div class="modal-body" style="font-size: 14px; body:inherit;">
<form name="add_profile_name" id="add_profile_name" action="exp_driverrate.php?id='.$edit['max_driver_id'][0]['MAX(DRIVER_CODE)'].'" method="post">
<div class="btn-group">
<a href="#" class="btn btn-md btn-default" data-dismiss="modal"><span class="glyphicon glyphicon-remove"></span>Close</a>
<input type="submit" name="save_profile_name" id="save_profile_name" class="btn btn-md btn-success" onclick="javascript:return check_profile_name();"  value="Save Changes"/>
</div>

<div class="form-group">
<input class="form-control text-right" name="DRIVER_ID" id="DRIVER_ID" type="hidden"   value="'.$edit['max_driver_id'][0]['MAX(DRIVER_CODE)'].'" >
			

	
<div class="form-group">
				<label for="Profile" class="col-sm-4 control-label">Profile Name</label>
				<div class="col-sm-4">
			   <input class="form-control" name="PRO_NAME" id="PRO_NAME" type="text" placeholder="Profile Name" >
				</div>
			</div>
			</div>
			</form>
</div>
<div class="modal-footer">

</div>
</div><!-- /.modal-content -->
</div><!-- /.modal-dialog -->

</div>
		 
        
          <h4>Add Manual Rates : <a class="btn btn-sm btn-success" href="javascript:void(0);" onclick="javascript: return add_more_fun(\'add_manual_rates\');"><span class="glyphicon glyphicon-plus"></span> Add </a><div id="reload_manual_rates_div" style="display:none;"><img src="images/loading.gif" border="1" style="position:absolute; margin-left:250px;margin-top:10px;"></div></h4>
		  
		   <table width="100%" border="1" cellspacing="1" cellpadding="0" class="table table-striped table-condensed table-bordered table-hover">
            <tr>
              <td style="padding:10px; ">
			  <table width="100%" border="0" cellspacing="0" cellpadding="0">			  
							'.$driver_manual_rates_str.'	
                </table></td>
            </tr>
            <tr>
              <td>
			  <table width="100%" border="0" cellspacing="0" cellpadding="0" style="margin:5px;display:none;"  id="add_manual_rates">
			  <input type="hidden" name="manual_driver_id" id="manual_driver_id" size="15" class="form-control" value="'.$edit['max_driver_id'][0]['MAX(DRIVER_CODE)'].'" />
                  <tr>
                    <td style="font-size:12px; font-family:Arial, Helvetica, sans-serif; text-transform:uppercase; padding:0 6px;">Code</td>
                    <td><input type="text" name="manual_code" id="manual_code" size="15" class="form-control" value="'.$edit['MANUAL_RATE_CODE'].'" readonly="readonly"></td>
					<td style="font-size:12px; font-family:Arial, Helvetica, sans-serif; text-transform:uppercase; padding:0 6px;">Name</td>
                    <td><input type="text" name="manual_name" id="manual_name" size="15" class="form-control"></td>
                    <td style="font-size:12px; font-family:Arial, Helvetica, sans-serif; text-transform:uppercase; padding:0 6px;">Description</td>
                    <td><input type="text" name="manual_desc" id="manual_desc" size="15" class="form-control"></td>
                    <td style="font-size:12px; font-family:Arial, Helvetica, sans-serif; text-transform:uppercase; padding:0 6px;">Rates</td>
                    <td><input type="text" name="manual_rate" id="manual_rate" size="15" class="form-control"></td>
					<td style="font-size:12px; font-family:Arial, Helvetica, sans-serif; text-transform:uppercase; padding:0 6px;">&nbsp;</td>
                    <td><input name="save_manual_rate" id="save_manual_rate" type="button" class="btn btn-md btn-default" value="Submit" onclick="javascript:return add_manual_rates();"></td>
                  </tr>
                </table></td>
            </tr>
          </table>
		  <h4>Range of rates :  <a class="btn btn-sm btn-success" href="javascript:void(0);" onclick="javascript: return add_more_fun(\'add_range_rates\');"><span class="glyphicon glyphicon-plus"></span>
		   Add </a> <div id="reload_range_rates_div" style="display:none;"><img src="images/loading.gif" border="1" style="position:absolute; margin-left:250px;margin-top:10px;"></div></h4>
          <table width="100%" border="1" cellspacing="1" cellpadding="0" class="table table-striped table-condensed table-bordered table-hover">
            <tr>
              <td style="padding:10px; "><table width="100%" border="0" cellspacing="0" cellpadding="0">
                   '.$driver_range_rates_str.'                 
                </table></td>
            </tr>            
            <tr>
              <td><table width="100%" border="0" cellspacing="0" cellpadding="0" style="margin:5px;display:none;"   id="add_range_rates">
			  	  <input type="hidden" name="range_driver_id" id="range_driver_id" size="15" class="form-control" value="'.$edit['max_driver_id'][0]['MAX(DRIVER_CODE)'].'" />
                  <tr>
				  <td style="font-size:12px; font-family:Arial, Helvetica, sans-serif; text-transform:uppercase; padding:0 3px; ">Code</td>
				    <td style="font-size:12px; font-family:Arial, Helvetica, sans-serif; text-transform:uppercase; padding:0 3px; ">Name</td>
				  <td style="font-size:12px; font-family:Arial, Helvetica, sans-serif; text-transform:uppercase; padding:0 3px;">From</td>
				  <td style="font-size:12px; font-family:Arial, Helvetica, sans-serif; text-transform:uppercase; padding:0 3px;">to</td>
				  <td style="font-size:12px; font-family:Arial, Helvetica, sans-serif; text-transform:uppercase; padding:0 3px;">From Zone</td>
				  <td style="font-size:12px; font-family:Arial, Helvetica, sans-serif; text-transform:uppercase; padding:0 3px;">To Zone</td>
				  <td style="font-size:12px; font-family:Arial, Helvetica, sans-serif; text-transform:uppercase; padding:0 3px;">Rates</td>
					
				  </tr>
				  <tr>
				 	 <td> <input type="text" name="range_rate_code" id="range_rate_code" size="15" class="form-control" value="'.$edit['RANGE_RATE_CODE'].'" readonly="readonly" /></td>				  
                     <td style="padding:0 3px;"><input type="text" name="range_name" id="range_name" size="15" class="form-control" value=""></td>
                    <td style="padding:0 3px;"><input type="text" name="range_from" id="range_from" size="15" class="form-control" value=""></td>
                   
                    <td style="padding:0 3px;"><input type="text" name="range_to" id="range_to" size="15" class="form-control" value=""></td>
					
                    <td style="padding:0 3px;">
						<select name="zone_from" id="zone_from" class="form-control">
							<option value="">From</option>
							'.$driver_zone_range_str.'		
						</select>
					</td>
					
                    <td style="padding:0 3px;">
						<select name="zone_to" id="zone_to" class="form-control">
							<option value="">To</option>
							'.$driver_zone_range_str.'		
						</select>
					</td>
					
					<td style="padding:0 3px;"><input type="text" name="range_rate" id="range_rate" size="15" class="form-control" value=""></td>
					
					<td style="font-size:12px; font-family:Arial, Helvetica, sans-serif; text-transform:uppercase; padding:0 3px;">&nbsp;</td>
                    <td><input name="save_range_rates"  id="save_range_rates" type="button" class="btn btn-md btn-default" value="Submit" onclick="javascript: return add_rate_range();"></td>
                  </tr>
                </table></td>
            </tr>
          </table>
        </div>
      </div>
    </div>
    <div style="clear:both"></div>
  </div>
  <div class="table-responsive">
 <h3><img height="29" src="images/money_bag.png" alt="tractor_icon"> Select Driver Rates : </h3>
  <form name="" id="" action="exp_driverrate.php" method="post">
  <div class="btn-group" style="margin-bottom:20px;">
  
	'.$edit["category_str"].'
		<button class="btn btn-md btn-success" type="submit" name="saverate">
		<span class="glyphicon glyphicon-ok"></span>
		Apply Rates To Driver
		</button></div>
		<div style="float:right;"><a class="btn btn-sm btn-success"  href="exp_adddriverrate.php"><span class="glyphicon glyphicon-plus"></span>Add Driver Rate</a></div>
<br>';

echo ' 
<div id="reload_search_result" style="display:none;"><img src="images/loading.gif" border="1" style="position:absolute; margin-left:250px;margin-top:10px;"></div>
<div id="search_reload">

		<table class="table table-striped table-condensed table-bordered table-hover" >
            <thead>
              <tr class="exspeedite-bg">
                <td class="text-center"><span class="text-muted"><span class="glyphicon glyphicon-unchecked"></span></td>
				 <th class="text-center">Rate Category</th>
				 <th class="text-center">Rate Code</th>
				 <th class="text-center">Rate Name</th>
				 <th class="text-center">Rate Description</th>
                <th class="text-center">Rates</th>
				 <th class="text-center">Bonus</th>
				 <th class="text-center">Zones</th>
				
              </tr>
            </thead>
            <tbody>
              '.$rate_str.'                   
            </tbody>
          </table>
</div>
<input type="hidden" name="hid_driver_id" id="hid_driver_id" value="'.$edit['max_driver_id'][0]['MAX(DRIVER_CODE)'].'" />   
</form>
</div>
</div>'; //echo '<pre>';print_r($edit);	 onclick="javascript: return chk_validation_cat();"
		}
		else{
				echo '<div class="container-full" role="main">
  <form class="form-inline" role="form" action="/trunk/exp_listdriver.php"  	method="post" enctype="multipart/form-data" 	name="RESULT_FILTERS_EXP_DRIVER" id="RESULT_FILTERS_EXP_DRIVER">
    <input name="FILTERS_EXP_DRIVER" type="hidden" value="on">
    <h3> <img src="images/driver_icon.png" alt="driver_icon" height="24" > &nbsp; Drivers &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; <img height="29" alt="tractor_icon" src="images/money_bag.png"> &nbsp;Driver Rates </h3>';
	
	echo '

    </div>
    </h3>
  </form>';
  
  echo '
  <div class="md_row">
    <div class="well_md_l">
      <div class="well  well-md" ><!--style="height:500px; overflow:auto;float:left;width:48%;"-->
        <div class="table-responsive" style="overflow:auto;  ">
          <table class="table table-striped table-condensed table-bordered table-hover" >
            <thead>
              <tr class="exspeedite-bg"> 
                <!--  <th></th>-->
                <th class="text-center">Active</th>
                <!-- <th class="text-center">Del</th>-->
				<th>Code</th>
                <th>First</th>
                <th>Middle</th>
                <th>Last</th>
              </tr>
            </thead>
            <tbody>
              '.$driver_str.'
            </tbody>
          </table>
        </div>
      </div>
    </div>';
	
	echo '
    <div class="well_md_r" id="driver_rate_div">
      <div class="well  well-md"><!--style="height:500px; overflow:auto; float:right; width:48%;"-->
        <div class="table-responsive" style="overflow:auto;  ">
          Please select driver.
		 ';
		  
		  echo '
        
        </div>
      </div>
    </div>
    <div style="clear:both"></div>
  </div>
  <div class="table-responsive">
   <h3><img height="29" src="images/money_bag.png" alt="tractor_icon"> Select Driver Rates : </h3>
  <form name="" id="" action="exp_driverrate.php" method="post">
  <div class="btn-group" style="margin-bottom:20px;">
	'.$edit["category_str"].'
		<button class="btn btn-md btn-success" type="submit" name="saverate">
		<span class="glyphicon glyphicon-ok"></span>
		Apply Rates To Driver
		</button></div>
		<div style="float:right;"><a class="btn btn-sm btn-success"  href="exp_adddriverrate.php"><span class="glyphicon glyphicon-plus"></span>Add Driver Rate</a></div>
<br>';

echo ' 
<div id="reload_search_result" style="display:none;"><img src="images/loading.gif" border="1" style="position:absolute; margin-left:250px;margin-top:10px;"></div>
<div id="search_reload">

		<table class="table table-striped table-condensed table-bordered table-hover" >
            <thead>
              <tr class="exspeedite-bg">
                <td class="text-center"><span class="text-muted"><span class="glyphicon glyphicon-unchecked"></span></td>
				 <th class="text-center">Rate Category</th>
				 <th class="text-center">Rate Code</th>
				  <th class="text-center">Rate Name</th>
				 <th class="text-center">Rate Description</th>
                <th class="text-center">Rates</th>
				 <th class="text-center">Bonus</th>
				 <th class="text-center">Zones</th>
              </tr>
            </thead>
            <tbody>
              '.$rate_str.'                   
            </tbody>
          </table>
</div>
<input type="hidden" name="hid_driver_id" id="hid_driver_id" value="'.$edit['max_driver_id'][0]['MAX(DRIVER_CODE)'].'" />   
</form>
</div>
</div>'; 
		}}
		
	private function slink( $str ) {
		$output = '';
		
		if( ! empty($str) ) {
			$links = [];
			foreach( explode( ', ', $str ) as $shipment ) {
				$links[] = '<a href="exp_addshipment.php?CODE='.$shipment.'" target="_blank">'.$shipment.'</a>';
			}
			$output = implode(', ', $links);
		}
		
		return $output;
	}
	
	//! WIP render function for driver pay screen 
	public function render_driver_pay_screen($edit)
	{
		//print_r($edit);
		if( $_SESSION['EXT_USERNAME'] == 'duncan' && $this->debug ) {
			echo "<pre>render_driver_pay_screen: entry";
			var_dump($edit);
			echo "</pre>";
		}

		//! Duncan - check if there are any driver rates set up.
		if( isset($edit['norates']) ) {
			echo '<div class="container-full" role="main">
			<div class="alert alert-danger" role="alert">
			<h3><span class="glyphicon glyphicon-warning-sign text-danger"></span> This Driver Has No Rates Assigned</h3>
			<p>Please assign some rates or rate profiles for this driver before you can do driver pay.</p>
			<p><a class="btn btn-sm btn-default" href="exp_listdriver.php"><span class="glyphicon glyphicon-remove"></span> Back To Drivers</a></p>
			</div>
			</div>
			';
			return false;
		}

		$fullname=$profile="";
		if(isset($edit['driver_info']) && count($edit['driver_info'])>0) {
			$fullname=$edit['driver_info'][0]['FIRST_NAME']." ".$edit['driver_info'][0]['MIDDLE_NAME']." ".$edit['driver_info'][0]['LAST_NAME'];
			if($edit['driver_info'][0]['PROFILE_ID']!="0") {
				$profile=$edit['driver_info'][0]['PROFILE_ID'].' - '.$edit['contract_name'];
			}
		}
		$fr_date=$to_date='';
		if(isset($_POST['btn_search_load'])) {
			$fr_date=$_POST['ACTUAL_DEPART_FROM'];
			$to_date=$_POST['ACTUAL_DEPART_TO'];
		}
		
		echo '
<div class="container-full" role="main" style="width:86%">
<div class="container-full" role="main" >
  <div style="position:fixed;top:50px;width:82%;margin-left:-31px;background-color:#fff;padding-bottom:50px;z-index:500;">
  <form class="form-inline" role="form" action="exp_adddriverpay.php?id='.$edit['id'].'"  method="post" enctype="multipart/form-data" 	name="RESULT_FILTERS_EXP_DRIVER" id="RESULT_FILTERS_EXP_DRIVER" novalidate>
    <input name="FILTERS_EXP_DRIVER" type="hidden" value="on">'.
    (isset($edit['debug']) && $edit['debug'] ? '<input name="debug" type="hidden" value="on">' : '').'
    <h3><img src="images/driver_icon.png" alt="driver_icon" height="24" > &nbsp; Driver Pay: Select Loads
	<div class="btn-group">
	<a class="btn btn-sm btn-default" href="'.($edit["back"] <> '' ? 'exp_billhistory.php?CODE='.$edit["back"] : 'exp_listdriver.php').'"><span class="glyphicon glyphicon-remove"></span> Back</a>
	</div>&nbsp;</h3>
	<strong>Actual Delivery</strong>&nbsp;&nbsp; FROM <input type="'.($_SESSION['ios'] == 'true' ? 'datetime-local' : 'text').'" value="'.$fr_date.'" placeholder="From" id="ACTUAL_DEPART_FROM" name="ACTUAL_DEPART_FROM" class="form-control'.($_SESSION['ios'] == 'true' ? '' : ' timestamp').'">&nbsp;&nbsp; TO <input type="'.($_SESSION['ios'] == 'true' ? 'datetime-local' : 'text').'" value="'.$to_date.'" placeholder="To" id="ACTUAL_DEPART_TO" name="ACTUAL_DEPART_TO" class="form-control'.($_SESSION['ios'] == 'true' ? '' : ' timestamp').'">
	 &nbsp;&nbsp; <div class="btn-group"><button  class="btn btn-md btn-success" type="submit" name="btn_search_load">
		<span class="glyphicon glyphicon-search"></span>
		Filter
		</button> <button  class="btn btn-md btn-default" type="button" name="btn_search_reload" onclick="javascript:window.location.href=window.location.href;">
		<span class="glyphicon glyphicon-refresh"></span>
		Reset
		</button>
		</div>
  </form>
  </div>
    </div>

<div class="render_driver_pay_screen">
    <div class="table-responsive" style="overflow:auto;">
	 <!--info-->
	 <div style="position:fixed;top:170px;width:82%;background-color:#fff;z-index:500;">
    <table class="table table-striped table-condensed table-bordered table-hover" >
                  <tbody>
				  <tr style="cursor:pointer;" >
				  	<td colspan="4"><div style="float:right;">Week :<strong> '.date("d  M  Y", strtotime($edit["start_date"])).'</strong>  to   <strong>'.date("d M Y", strtotime($edit["end_date"])).'</strong></div></td>
				  </tr>
            	<tr style="cursor:pointer;" >					
					<td width="14%" ><strong>Driver Name</strong></td>
					<td width="16%">'.$fullname.'</td>
                    <td width="14%" ><strong>Contract Id :</strong></td>
					<td width="16%"><input class="form-control input-sm" type="text"  name="" value="'.$profile.'"></td>
                </tr>
            </tbody>
     </table>
     </div>
     
     <form action="exp_adddriverpay.php?id='.$edit['id'].'" method="post">
     <!--info-->
     <input type="hidden" name="start_date" value="'.$edit["start_date"].'"/>
     <input type="hidden" name="end_date" value="'.$edit["end_date"].'"/>'.
    (isset($edit['debug']) && $edit['debug'] ? '<input name="debug" type="hidden" value="on">' : '').'
     ';
	 $permile_flag=0;
	 if(count($edit['driver_assign_load_with_stop'])>0)
	 {$load_cnt=1;//print_r($edit['previous_load']);
	 $temp=0;
	 echo '<div style="margin-top:200px;">';
	 //!Trying to make sense of it all
	/*echo "<pre>";
	var_dump($edit['driver_assign_load_with_stop']);
	var_dump($edit['previous_load']);
	echo "</pre>";*/
		 foreach($edit['driver_assign_load_with_stop']	 as $dri_row)
		 {//echo $dri_row['LOAD_CODE'];
	 $permile_flag=0;
			 if(!in_array($dri_row['LOAD_CODE'],$edit['previous_load']))
			{$temp=1;
			 $name='';
			 $odometer_from=$odometer_to='';
			 if($dri_row['ODOMETER_FROM']!='')
			 {
				 $odometer_from=$dri_row['ODOMETER_FROM'];
			}
			 if($dri_row['ODOMETER_TO']!='')
			 {
				 $odometer_to=$dri_row['ODOMETER_TO'];
			}
			
			 if(isset($dri_row["CONS_NAME"]))
			 {
				 $name=$dri_row["CONS_NAME"];
			 }
			 #get range of miles 
			 $str_range_of_miles='';
			 if(count($edit['range_rates'])>0)
			 {$flag=0;
				//! If no actual_distance, use the total_distance
				if( $dri_row['ACTUAL_DISTANCE'] > 0 )
					$total_dist = $dri_row['ACTUAL_DISTANCE'];
				else
					$total_dist = $dri_row['TOTAL_DISTANCE'];
				 foreach( $edit['range_rates'] as $row_range)
				 {
					 $diff_miles	=	$row_range['RANGE_TO'] - $row_range['RANGE_FROM'];
					 
					if($total_dist >= $row_range['RANGE_FROM'] && $total_dist <= $row_range['RANGE_TO'])
					 {
						 $permile_flag=1;
							$diff=$total_dist - $row_range['RANGE_FROM'];
							//$cal	=$diff	* $row_range['RANGE_RATE'];
							$cal	= $row_range['RANGE_RATE'];
							$flag=1;
							//$cal	=0;
							$str_range_of_miles.='<tr style="cursor:pointer;" >					
							<td width="14%" ><strong>'.$row_range['RANGE_NAME'].'  :  '.$row_range['RANGE_FROM'].' TO '.$row_range['RANGE_TO'].'</strong></td>
							<td width="16%" class="text-right">'.$diff.'</td>
							<td width="14%" class="text-right">$ '.$row_range['RANGE_RATE'].'</td>
							<td width="16%"><div class="input-group">
				<span class="input-group-addon">$</span>
<input class="form-control input-sm text-right" type="text"  name="range_miles'.$dri_row["LOAD_CODE"].'[]" id="range_miles'.$dri_row["LOAD_CODE"].'[]" value="'.number_format($cal,2,".","").'" onkeyup="javascript: calculate_total(\'\',\''.$dri_row["LOAD_CODE"].'\');"></div></td>
						</tr>
						<input type="hidden" name="range_name'.$dri_row["LOAD_CODE"].'[]" value="'.$row_range['RANGE_NAME'].'"/>
						<input type="hidden" name="range_code'.$dri_row["LOAD_CODE"].'[]" value="'.$row_range['RANGE_CODE'].'"/>
						<input type="hidden" name="range_from'.$dri_row["LOAD_CODE"].'[]" value="'.$row_range['RANGE_FROM'].'"/>
						<input type="hidden" name="range_to'.$dri_row["LOAD_CODE"].'[]" value="'.$row_range['RANGE_TO'].'"/>
						<input type="hidden" name="range_rate'.$dri_row["LOAD_CODE"].'[]" value="'.$row_range['RANGE_RATE'].'"/>'; 
					 }	
					 else
					 { 
							//$cal	=	$diff_miles * $row_range['RANGE_RATE'];
							//$cal	=	 $row_range['RANGE_RATE'];
							$cal	=0;
							$str_range_of_miles.='<tr style="cursor:pointer;" >					
							<td width="14%" ><strong>'.$row_range['RANGE_NAME'].' :  '.$row_range['RANGE_FROM'].' TO '.$row_range['RANGE_TO'].'</strong></td>
							<td width="16%" class="text-right">'.$diff_miles.'</td>
							<td width="14%" class="text-right">$ '.$row_range['RANGE_RATE'].'</td>
							<td width="16%"><div class="input-group">
				<span class="input-group-addon">$</span>
<input class="form-control input-sm text-right" type="text"  name="range_miles'.$dri_row["LOAD_CODE"].'[]" id="range_miles'.$dri_row["LOAD_CODE"].'[]" value="'.number_format($cal,2,".","").'" onkeyup="javascript: calculate_total(\'\',\''.$dri_row["LOAD_CODE"].'\');"></div></td>
						</tr>
						<input type="hidden" name="range_name'.$dri_row["LOAD_CODE"].'[]" value="'.$row_range['RANGE_NAME'].'"/>
						<input type="hidden" name="range_code'.$dri_row["LOAD_CODE"].'[]" value="'.$row_range['RANGE_CODE'].'"/>
						<input type="hidden" name="range_from'.$dri_row["LOAD_CODE"].'[]" value="'.$row_range['RANGE_FROM'].'"/>
						<input type="hidden" name="range_to'.$dri_row["LOAD_CODE"].'[]" value="'.$row_range['RANGE_TO'].'"/>
						<input type="hidden" name="range_rate'.$dri_row["LOAD_CODE"].'[]" value="'.$row_range['RANGE_RATE'].'"/>'
						 ; 
					 }
				 }
			 }		 
			 
			//! To debug per-load data
			//echo "<pre>";
			//var_dump($dri_row);
			//echo "</pre>";
		
			//if( $dri_row['MILLEAGE'] > 0 )
			//	$total_miles = $dri_row['MILLEAGE'];
			//else
			//! If no actual_distance, use the total_distance
			if( $dri_row['ACTUAL_DISTANCE'] > 0 )
				$total_miles = $dri_row['ACTUAL_DISTANCE'];
			else
				$total_miles = $dri_row['TOTAL_DISTANCE'];
			
			//! Count stops, drops, picks
			$num_picks = $num_drops = $num_stops = 0;
			$last_city = $last_zip = $last_name = '';
			if( is_array($dri_row['STOP_DETAIL']) && count($dri_row['STOP_DETAIL']) > 0) {
				
				foreach($dri_row['STOP_DETAIL'] as $stop_row) {
					if( in_array($stop_row["STOP_TYPE"], array('pick', 'drop'))) {
						if( $stop_row["STOP_TYPE"] == 'pick' )
							$num_picks++;
						if( $stop_row["STOP_TYPE"] == 'drop' )
							$num_drops++;
					}
						
					if( $stop_row["NAME"] <> $last_name ||
						$stop_row["CITY"] <> $last_city ||
						$stop_row["ZIP"] <> $last_zip ) {
						$num_stops++;
					}
											
					$last_name = $stop_row["NAME"];
					$last_city = $stop_row["CITY"];
					$last_zip = $stop_row["ZIP"];
				}
				$num_stops -= 2;	// Subtract two for initial pick/drop
			}
						
			 //! get add manual rates to loads
			 $manual_rate_str='';
			 if(count($edit['manual_rates'])>0)
			 {
					$manual_rate_str	='<select name="manual_rates'.$dri_row["LOAD_CODE"].'" class="form-control input-sm" id="manual_rates'.$dri_row["LOAD_CODE"].'"  >
													<option value="">--Select--</option>';
													foreach($edit['manual_rates'] as  $mrate)
													{
														$manual_rate_str	.=	'<option value="'.$mrate['MANUAL_ID'].'">'.$mrate['MANUAL_RATE_CODE']." - ".$mrate['MANUAL_RATE_DESC'].'</option>';
													}	
					$manual_rate_str	.=	'</select>';
			 }
			 else
			 {
					 $manual_rate_str	='<select name="manual_rates'.$dri_row["LOAD_CODE"].'" class="form-control input-sm" id="manual_rates'.$dri_row["LOAD_CODE"].'"  >
													<option value="">--Select--</option>';
					$manual_rate_str	.=	'</select>';
			 }
			 $driver_rate_str='';
			 #get number of pallets for this load
			$total_amount=0.00;

			if(count($edit['driver_rates'])>0)
			{
				foreach($edit['driver_rates'] as $row)
				{
				//	echo $row['CATEGORY_NAME']."=>".$dri_row['TOT_PALETS'];
					
					//! SCR# 849 - Team Driver Feature
if( $this->debug ) {
	echo"<h3>Team Driver Feature</h3>";
	echo "<pre>";
	var_dump($row['TEAM_DRIVER'], $dri_row['TEAM'], $dri_row);
	echo "</pre>";
}

if( $this->debug && $dri_row['LOAD_CODE'] == 3847 && trim($row['CATEGORY_NAME'])=="Flat Rate" ) {
	echo"<h3>TEST MORSE</h3>";
	echo "<pre>";
	var_dump($row, $dri_row);
	echo "</pre>";
	echo"<h3>TEST MORSE END</h3>";
}

					if((isset($row['TEAM_DRIVER']) ? $row['TEAM_DRIVER'] : 0)
					 <> (isset($dri_row['TEAM']) ? $dri_row['TEAM'] : 0) ) continue;

					$r1 = 0;
					$per_amt = 0;
					if(trim($row['CATEGORY_NAME'])=="Per Pallet")
					{	
						$per_amt=$dri_row['TOT_PALETS'];
					}
					else if(trim($row['CATEGORY_NAME'])=="Per Mile" && $permile_flag==0)
					{	
						$per_amt=$total_miles;
					}
					else if(trim($row['CATEGORY_NAME'])=="Loaded Miles")
					{	
						//! NEED LOADED MILES
						$per_amt=$total_miles - $dri_row['EMPTY_DISTANCE'];
					}
					else if(trim($row['CATEGORY_NAME'])=="Empty Miles")
					{	
						//! NEED EMPTY MILES
						$per_amt=$dri_row['EMPTY_DISTANCE'];
					}
					else if(trim($row['CATEGORY_NAME'])=="Per Pick Up")
					{	
						//! NEED LOADED MILES
						$per_amt= $num_picks;
					}
					else if(trim($row['CATEGORY_NAME'])=="Per Drop")
					{	
						//! NEED LOADED MILES
						$per_amt= $num_drops;
					}
					else if(trim($row['CATEGORY_NAME'])=="Per Stop")
					{	
						//! NEED LOADED MILES
						$per_amt= $num_stops;
					}
					else if(trim($row['CATEGORY_NAME'])=="Line haul percentage")
					{	
						//! Duncan NEED LINE_HAUL
						$per_amt= $dri_row['LINE_HAUL'];
					}
					else if(trim($row['CATEGORY_NAME'])=="Client Rate Percentage" &&
						! empty($row['CLIENT_RATE_NAME']) &&
						is_array($dri_row["CLIENT_RATES"]) &&
						count($dri_row["CLIENT_RATES"]) > 0)
					{	
						foreach($dri_row["CLIENT_RATES"] as $crate_row ) {
							if( ! empty($crate_row['RATE_CODE']) &&
								$crate_row['RATE_CODE'] == $row['CLIENT_RATE_NAME'])
								$per_amt = $crate_row['RATES'];
						}
						
					}
					else if(trim($row['CATEGORY_NAME'])=="Flat Rate")
					{
						if( ! empty($row['CLIENT_RATE_NAME']) ) {
							$per_amt = 0;

							if( is_array($dri_row["CLIENT_RATES"]) &&
								count($dri_row["CLIENT_RATES"]) > 0 ) {
								foreach($dri_row["CLIENT_RATES"] as $crate_row ) {
									if( ! empty($crate_row['RATE_CODE']) &&
										$crate_row['RATE_CODE'] == $row['CLIENT_RATE_NAME'] &&
										$crate_row['RATES'] > 0 ) {

										$per_amt = 1;
									}
								}
							}
								
if( $this->debug ) {
	echo"<h3>MORE MORSE</h3>";
	echo "<pre>";
	var_dump($row['RATE_CODE'], $row['CLIENT_RATE_NAME'], $dri_row["CLIENT_RATES"], $per_amt);
	echo "</pre>";
	echo"<h3>MORE MORSE END</h3>";
}

						} else if( $row['SHIPPER_CLIENT_CODE'] > 0 ) {
							$per_amt = 0;
							
							foreach( $dri_row["STOP_DETAIL"] as $sr ) {
								if( isset($sr["SHIPPER_CLIENT_CODE"]) &&
									$sr["SHIPPER_CLIENT_CODE"] == $row['SHIPPER_CLIENT_CODE'] )
									$per_amt = 1;
							}
						} else if( $row['CONS_CLIENT_CODE'] > 0 ) {
							$per_amt = 0;
							
							foreach( $dri_row["STOP_DETAIL"] as $sr ) {
								if( isset($sr["CONS_CLIENT_CODE"]) &&
									$sr["CONS_CLIENT_CODE"] == $row['CONS_CLIENT_CODE'] )
									$per_amt = 1;
							}
						} else {
							$per_amt = 1;
						}
if( $this->debug ) {
	echo"<h3>LAST MORSE</h3>";
	echo "<pre>";
	var_dump($per_amt);
	echo "</pre>";
	echo"<h3>LAST MORSE END</h3>";
}
					}	
					else if(trim($row['CATEGORY_NAME'])=="Fuel Surcharge Percentage" &&
						isset($dri_row['FUEL_COST']))
					{	
						$per_amt= $dri_row['FUEL_COST'];
					}
					else if(trim($row['CATEGORY_NAME'])=="Per Mile + Rate")
					{	
						//! SCR# 236 all except miles and FSC
						//! Duncan - SCR# 237 - only if extra_stops
						if( isset($res['extra_stops']) && isset($dri_row['extra_stops']) &&
							$res['extra_stops'] && ! $dri_row['extra_stops'] ) {
							$per_amt= $dri_row['NOT_MILES_FSC'];
							$r1 = 0;
						} else {
							$per_amt= $total_miles*$row['RATE_ONE'] + $dri_row['NOT_MILES_FSC'];
							$r1 = $row['RATE_ONE'];
						}
					}
					else
					{
						$per_amt=0;	
					}
					$res_amount=0;
					$res_amount	=	max( 0, $row['RATE_PER_MILES'] - (isset($row['FREE_HOURS']) ? $row['FREE_HOURS'] : 0)) * $per_amt;

if( $this->debug && $dri_row['LOAD_CODE'] == 3704 && trim($row['CATEGORY_NAME'])=="Flat Rate" ) {
	echo "<pre>MORSE TEST\n";
	var_dump($row['RATE_PER_MILES'], $row['FREE_HOURS'], $per_amt, $res_amount);
	echo "</pre>";
}
						
					$total_amount	+=	$res_amount;
					$nm=str_replace(' ' , '_' ,  $row['CATEGORY_NAME']).'_'.$row['RATE_CODE'];
					
					//! SCR# 183 - Remove/Duplicate buttons (TBD)
					$driver_rate_str	.=	'<tr style="cursor:pointer;" id="RATE_'.$dri_row["LOAD_CODE"].$row['RATE_CODE'].'">					
					<input type="hidden" name="cat_type'.$dri_row["LOAD_CODE"].'[]" id="cat_type" value="'.$nm.'"/>
					<input type="hidden" name="category_name'.$dri_row["LOAD_CODE"].'[]" id="category_name" value="'.$row['CATEGORY_NAME'].'"/>
					<input type="hidden" name="rate_code'.$dri_row["LOAD_CODE"].'[]" id="rate_code" value="'.$row['RATE_CODE'].'"/>
					<input type="hidden" name="rate_name'.$dri_row["LOAD_CODE"].'[]" id="rate_name" value="'.$row['RATE_NAME'].'"/>

					<input type="hidden" name="rate_one'.$dri_row["LOAD_CODE"].'[]" id="rate_one" value="'.$r1.'"/>
					<input type="hidden" name="not_miles_fsc'.$dri_row["LOAD_CODE"].'[]" id="not_miles_fsc" value="'.$dri_row['NOT_MILES_FSC'].'"/>

					<input type="hidden" name="rate_bonus'.$dri_row["LOAD_CODE"].'[]" id="rate_bonus" value="'.$row['RATE_BONUS'].'"/>
					<input type="hidden" name="free_hours'.$dri_row["LOAD_CODE"].'[]" id="'.$dri_row["LOAD_CODE"].$nm.'_free" value="'.$row['FREE_HOURS'].'"/>
					
						<td style="text-align:right; color:#000; font-weight:bold; font-family:Arial, Helvetica, sans-serif; font-size:13px;">'.
					
					'<div class="btn-group" role="group" style="float: left;"><a class="btn btn-sm btn-danger" id="REMOVE_'.$dri_row["LOAD_CODE"].$row['RATE_CODE'].'" title="Remove rate" onclick="javascript: remove_rate(\''.$dri_row["LOAD_CODE"].$row['RATE_CODE'].'\', '.$dri_row["LOAD_CODE"].');"><span class="glyphicon glyphicon-remove"></span></a>'.

					'<a class="btn btn-sm btn-success"  id="ADD_'.$dri_row["LOAD_CODE"].$row['RATE_CODE'].'" title="Duplicate rate" onclick="javascript: duplicate_rate(\''.$dri_row["LOAD_CODE"].$row['RATE_CODE'].'\', '.$dri_row["LOAD_CODE"].');"><span class="glyphicon glyphicon-plus"></span></a></div>'.
					
						$row['RATE_NAME'].'<br>'.$row['CATEGORY_NAME'].
					($row['FREE_HOURS'] > 0 ? ' ['.$row['FREE_HOURS'].' free hours] ' : '').
					($row['TEAM_DRIVER'] == 1 ? ' (T)' : '').
						($row['RATE_BONUS'] == 1 ? ' (B)' : '').' :</td>
	<td><input name="'.$dri_row["LOAD_CODE"].'_qty[]" id="'.$dri_row["LOAD_CODE"].$nm.'_qty" type="text" class="form-control input-sm text-right" value="'.number_format($per_amt,2,".","").'" onkeyup="javascript: calculate_total(\''.$nm.'\',\''.$dri_row["LOAD_CODE"].'\');"></td>
	<td><input name="'.$dri_row["LOAD_CODE"].'_amt[]" id="'.$dri_row["LOAD_CODE"].$nm.'_amt" type="text" class="form-control input-sm text-right" value="'.$row['RATE_PER_MILES'].'" onkeyup="javascript: calculate_total(\''.$nm.'\',\''.$dri_row["LOAD_CODE"].'\');"></td>
						<td><div class="input-group">
				<span class="input-group-addon">$</span>
				<input name="text_total'.$dri_row["LOAD_CODE"].'[]" id="'.$dri_row["LOAD_CODE"].$nm.'_total" type="text" class="form-control input-sm text-right" value="'.number_format($res_amount,2,".","").'"  readonly></div></td>
					  </tr>
					';
				}
			}
			
			$stop_detail='';
			if(count($dri_row['STOP_DETAIL'])>0)
			{//print_r($dri_row['STOP_DETAIL']);
				foreach($dri_row['STOP_DETAIL'] as $stop)
				{
					$stop_type='';
					if(in_array($stop['STOP_TYPE'], array('pick', 'pickdock')))
					{$stop_type='Origin';}
					else if(in_array($stop['STOP_TYPE'], array('drop', 'dropdock')))
					{$stop_type='Destination';}
					else
					{$stop_type='Stop Off';}
					$depart_Date=$arrive_Date='';
					
					if($stop['ACTUAL_DEPART']!="")
					{$depart_Date=date('m/d/Y H:i:s l',strtotime($stop['ACTUAL_DEPART']));
					if($stop['HOLIDAY']!="")
					{$depart_Date.='<br/><span style="color:#d3392b;"><strong>'.$stop['HOLIDAY'].'</strong></span>';}
					}
					
					
					if($stop['ACTUAL_ARRIVE']!="")
					{$arrive_Date=date('m/d/Y H:i:s l',strtotime($stop['ACTUAL_ARRIVE']));
					if($stop['ARRIVAL_HOLIDAY']!="")
					{$arrive_Date.='<br/><span style="color:#d3392b;"><strong>'.$stop['ARRIVAL_HOLIDAY'].'</strong></span>';}
					}
					
					$comma='';					
					if($stop['CITY']!="" && $stop['STATE']!="")
					{$comma=',';}
					$stop_detail.='<tr style="cursor:pointer;">
											<td>'.$stop_type.'</td>
											<td>'.$stop['NAME'].' <br/>'.$stop['CITY'].$comma.$stop['STATE'].'</td>
											<td>'.$arrive_Date.'</td>
											<td>'.$depart_Date.'</td>
											 </tr>';
				}
			}
			else
			{$stop_detail.='<tr style="cursor:pointer;">
											<td colspan="4" style="text-align:center;">STOP DETAIL IS CURRENTLY UNAVAILABLE!</td>';}
			
		//! First one XXXYYY
		echo '
		<!--info7-->
		<div class="well well-sm" id="LOAD_'.$dri_row["LOAD_CODE"].'">
		<input type="hidden" name="LOAD_CODE[]" value="'.$dri_row["LOAD_CODE"].'"/>
		<input type="hidden" id="empty_miles'.$dri_row["LOAD_CODE"].'" value="'.$dri_row["EMPTY_DISTANCE"].'"/>
		<table class="table table-striped table-condensed table-bordered table-hover" >
			    <tbody>
				<tr style="cursor:pointer;" class="exspeedite-bg">					
						 <td width="10%"><a class="btn btn-sm btn-default" id="REMOVE_'.$dri_row["LOAD_CODE"].'" title="Remove load '.$dri_row["LOAD_CODE"].' from list" onclick="javascript: remove_load('.$dri_row["LOAD_CODE"].');"><span class="glyphicon glyphicon-remove"></span> Remove</a></td>
						 <td width="16%" ><strong>Load Id</strong>'.($edit['multi_company'] && ! empty($dri_row["OFFICE_NUM"]) ? ' - '.$dri_row["OFFICE_NUM"] : '').'</td>
						<td width="16%"><input class="form-control input-sm text-right" type="text"  name="load_id[]" id="load_id'.$dri_row["LOAD_CODE"].'" value="'.$dri_row["LOAD_CODE"].'" readonly></td>
						<td width="16%" bgcolor="#000000"><strong>Total Miles</strong></td>
						<td width="16%"><input class="form-control input-sm text-right" type="text"  name="total_miles[]" id="total_miles'.$dri_row["LOAD_CODE"].'" value="'.$total_miles.'" onkeyup="return update_miles_ranges('.$dri_row["LOAD_CODE"].','.$edit['id'].');"></td>
					</tr>
				</tbody>
			</table>
		 <!--info-->';
		 
		echo '
		<!--total settelment2-->
		<input type="hidden" name="tripID" id="tripID" value="0"/>
		  <input type="hidden" name="driver_id" id="driver_id" value="'.$edit['id'].'" />
		   <table class="table table-striped table-condensed table-bordered table-hover" >
				 <tbody>              
					  <tr style="cursor:pointer;" >	
						<td style="text-align:right; color:#000; font-weight:bold; font-family:Arial, Helvetica, sans-serif; font-size:13px;">Shipments :</td>
						<td colspan="3" style="text-align:right; color:#000; font-weight:bold; font-family:Arial, Helvetica, sans-serif; font-size:13px;">'.$this->slink($dri_row["SHIPMENTS"]).'</td>
					  </tr>                 
						  
						  				
						  <tr style="cursor:pointer;" >					
						<td style="text-align:right; color:#000; font-weight:bold; font-family:Arial, Helvetica, sans-serif; font-size:13px;">Tractor :</td>
						<td ><input name="tractor" id="trctor" value="'.$dri_row["TRACTOR_UNIT_NUMBER"].'" type="text" class="form-control input-sm"  readonly></td>
						<td style="text-align:right; color:#000; font-weight:bold; font-family:Arial, Helvetica, sans-serif; font-size:13px;">Approved Date :</td>
						<td><input name="approved_date'.$dri_row["LOAD_CODE"].'" id="approved_date'.$dri_row["LOAD_CODE"].'"  type="text" class="form-control input-sm" value="'.date('m/d/Y').'" ></td>
					  </tr>                 
								 
					  <tr style="cursor:pointer;" >					
						<td style="text-align:right; color:#000; font-weight:bold; font-family:Arial, Helvetica, sans-serif; font-size:13px;">Trailer 1 :</td>
						<td><input name="trailer" id="trailer" value="'.$dri_row["TRAILER_UNIT_NUMBER"].'" type="text" class="form-control input-sm" readonly ></td>
						<td style="text-align:right; color:#000; font-weight:bold; font-family:Arial, Helvetica, sans-serif; font-size:13px;">Trailer 2 :</td>
						<td><input name="" type="text" class="form-control input-sm" readonly ></td>
					  </tr>      
					   <tr style="cursor:pointer;" >					
						<td style="text-align:right; color:#000; font-weight:bold; font-family:Arial, Helvetica, sans-serif; font-size:13px;">Odometer From:</td>
						<td><input name="odometer_from[]" id="odometer_from'.$dri_row["LOAD_CODE"].'" value="'.$odometer_from.'" type="text" class="form-control input-sm text-right"  onkeyup="return calculate_total_miles('.$dri_row["LOAD_CODE"].','.$edit['id'].');"></td>
						<td style="text-align:right; color:#000; font-weight:bold; font-family:Arial, Helvetica, sans-serif; font-size:13px;">Odometer To :</td>
						<td><input name="odometer_to[]" id="odometer_to'.$dri_row["LOAD_CODE"].'" type="text" class="form-control input-sm text-right" value="'.$odometer_to.'" onkeyup="return calculate_total_miles('.$dri_row["LOAD_CODE"].','.$edit['id'].');" ></td>
					  </tr> 
					  
					  <!-- Duncan LINE_HAUL -->               
					   <tr style="cursor:pointer;" >					
						<td style="text-align:right; color:#000; font-weight:bold; font-family:Arial, Helvetica, sans-serif; font-size:13px;">Line haul:</td>
						<td><div class="input-group">
				<span class="input-group-addon">$</span><input name="line_haul[]" id="line_haul'.$dri_row["LOAD_CODE"].'" value="'.$dri_row["LINE_HAUL"].'" type="text" class="form-control input-sm text-right" readonly></div></td>
						
						<td style="text-align:right; color:#000; font-weight:bold; font-family:Arial, Helvetica, sans-serif; font-size:13px;">Fuel Surcharge:</td>
						<td><div class="input-group">
				<span class="input-group-addon">$</span><input name="fuel_cost[]" id="line_haul'.$dri_row["LOAD_CODE"].'" value="'.$dri_row["FUEL_COST"].'" type="text" class="form-control input-sm text-right" readonly></div></td>
					  </tr>                 

					<!--   <tr style="cursor:pointer;" >					
						<td style="text-align:right; color:#000; font-weight:bold; font-family:Arial, Helvetica, sans-serif; font-size:13px;">&nbsp;</td>
						<td>&nbsp;</td>
						<td style="text-align:right; color:#000; font-weight:bold; font-family:Arial, Helvetica, sans-serif; font-size:13px;">Approved Date :</td>
						<td><input name="approved_date'.$dri_row["LOAD_CODE"].'" id="approved_date'.$dri_row["LOAD_CODE"].'"  type="text" class="form-control input-sm" value="'.date('m/d/Y').'" ></td>
					  </tr> -->   
					  
					  <tr style="cursor:pointer;" >					
						<td colspan="4">&nbsp;</td>
					  </tr>
					 				
				<tr style="cursor:pointer;" >
				<th>Stop Type</th>
				<th>Shipper/Consignee</th>
				<th>Actual Arrival Day & Holiday</th>
				<th>Actual Departure Day & Holiday</th>
				</tr>'.$stop_detail;
				
				echo '
				  <tr>                
					<th class="text-center" width="30%">&nbsp;</th>               
					<th>Quantity</th>
					<th>Rate</th>
					<th>Pay</th>                
				  </tr>
				</thead>
				<tbody>  
				'.$driver_rate_str.'


						<tr style="cursor:pointer;" >					
						<td style="text-align:center; color:#000; font-weight:bold; font-family:Arial, Helvetica, sans-serif; font-size:13px;" colspan="4">Miles Range:</td>			
						</tr> 
						<tr>
						<td><div id="reload_range_rate_div'.$dri_row["LOAD_CODE"].'" style="display:none;"><img src="images/loading.gif" border="1" style="position:absolute; margin-left:250px;margin-top:10px;"></div>
						</td>
						</tr>
					     <tr style="cursor:pointer;" >
                        	<td style="text-align:center; color:#000; font-weight:bold; font-family:Arial, Helvetica, sans-serif; font-size:13px;" >&nbsp;</td>		
                            <td style="text-align:center; color:#000; font-weight:bold; font-family:Arial, Helvetica, sans-serif; font-size:13px;" >Difference Between Miles</td>		
                          	<td style="text-align:center; color:#000; font-weight:bold; font-family:Arial, Helvetica, sans-serif; font-size:13px;" >Range Rate</td>		
                            <td style="text-align:center; color:#000; font-weight:bold; font-family:Arial, Helvetica, sans-serif; font-size:13px;" >Rates</td>			
                        </tr>
					  '.$str_range_of_miles.'
					  		<tr style="cursor:pointer;">					
						<td>&nbsp;</td>
						<td>&nbsp;</td>
						<td>&nbsp;</td>
						<td>&nbsp;</td>
					  </tr>

					 <tr style="cursor:pointer;" >					
						<td style="text-align:right; color:#000; font-weight:bold; font-family:Arial, Helvetica, sans-serif; font-size:13px;">Manual Rates  :</td>
						<td>
							'.$manual_rate_str.'
						</td>
						<td><a class="btn btn-sm btn-success" onclick="javascript: return add_manual_fun(\''.$dri_row["LOAD_CODE"].'\',\''.$edit['id'].'\');" href="javascript:void(0);">
								<span class="glyphicon glyphicon-plus"></span>Add</a></td>
						<td>&nbsp;</td>					
					  </tr>   
					  
					  <tr><td><div id="reload_manual_rate_div'.$dri_row["LOAD_CODE"].'" style="display:none;"><img src="images/loading.gif" border="1" style="position:absolute; margin-left:250px;margin-top:10px;"></div></td></tr>
					   <tr style="cursor:pointer;" >					
						<td colspan="4">
								<table width="100%" id="empty_row'.$dri_row["LOAD_CODE"].'" class="table table-striped table-condensed table-bordered table-hover">
								</table>
						</td>
					  </tr>


				<tr style="cursor:pointer;" >					
						<td style="text-align:right; color:#000; font-weight:bold; font-family:Arial, Helvetica, sans-serif; font-size:16px;">Total Trip Pay :</td>
						<td>&nbsp;</td>
						<td>&nbsp;</td>
						<td style="color:#000; font-weight:bold; font-family:Arial, Helvetica, sans-serif; font-size:16px; padding-right: 20px;">
						<input type="hidden" name="total_trip_amount[]" id="total_trip_amount'.$dri_row["LOAD_CODE"].'" value="'.$total_amount.'"><div class="text-right" id="total_trip_amount_div'.$dri_row["LOAD_CODE"].'"> $ '.$total_amount.'</div></td> </tr>

						<tr style="cursor:pointer;" >					
						<td style="text-align:right; color:#000; font-weight:bold; font-family:Arial, Helvetica, sans-serif; font-size:16px;">Bonus-able Trip Pay :</td>
						<td>&nbsp;</td>
						<td>&nbsp;</td>
						<td style="color:#000; font-weight:bold; font-family:Arial, Helvetica, sans-serif; font-size:16px; padding-right: 20px;">
						<input type="hidden" id="total_bonusable_amount'.$dri_row["LOAD_CODE"].'" value=""><div class="text-right" id="total_bonusable_amount_div'.$dri_row["LOAD_CODE"].'"></div></td> </tr>
						

					  
					  <tr style="cursor:pointer;" >					
						<td style="text-align:right; color:#000; font-weight:bold; font-family:Arial, Helvetica, sans-serif; font-size:13px;">Bonus :</td>
						<td><select name="bonus_allow[]" class="form-control input-sm" id="bonus_allow'.$dri_row["LOAD_CODE"].'" onchange="javascript:return calculate_total(\'\',\''.$dri_row["LOAD_CODE"].'\');" >
								<option value="No">No</option>
								<option value="Yes">Yes</option>
						</select></td>
						<td><input class="form-control input-sm text-right" type="text"  name="apply_bonus[]" id="apply_bonus'.$dri_row["LOAD_CODE"].'" onkeyup="javascript:return calculate_total(\'\',\''.$dri_row["LOAD_CODE"].'\');">Bonus in %</td>
						<td><div class="input-group">
				<span class="input-group-addon">$</span>
<input class="form-control input-sm text-right" type="text"  name="bonus_amount[]" id="bonus_amount'.$dri_row["LOAD_CODE"].'" onkeyup="javascript:return calculate_total(\'\',\''.$dri_row["LOAD_CODE"].'\');"></div>$ on Total Trip Pay </td>
					  </tr>    
					  <tr style="cursor:pointer;" >					
						<td >&nbsp;</td>
						<td>&nbsp;</td>
						<td>&nbsp;</td>
						<td>&nbsp;</td>
					  </tr>
					  
						<tr style="cursor:pointer;" >	
						<td style="text-align:right; color:#000; font-weight:bold; font-family:Arial, Helvetica, sans-serif; font-size:13px;">Handling Pallets :</td>
						<td><input class="form-control input-sm text-right" type="text"  name="handling_pallet[]" id="handling_pallet'.$dri_row["LOAD_CODE"].'" value="'.$dri_row['HAND_PALLET'].'" onkeyup="javascript: calculate_total(\'\',\''.$dri_row["LOAD_CODE"].'\');" ></td>				
						<td style="text-align:right; color:#000; font-weight:bold; font-family:Arial, Helvetica, sans-serif; font-size:13px;">Handling Pay :</td>
						
						<td><div class="input-group">
				<span class="input-group-addon">$</span>
<input class="form-control input-sm text-right" type="text"  name="handling_pay[]" id="handling_pay'.$dri_row["LOAD_CODE"].'" value="" onkeyup="javascript: calculate_total(\'\',\''.$dri_row["LOAD_CODE"].'\');" ></div></td>
					  </tr>  
					  
					  	<tr style="cursor:pointer;" >	
						<td style="text-align:right; color:#000; font-weight:bold; font-family:Arial, Helvetica, sans-serif; font-size:13px;">Loaded Detention Hours :</td>
						<td><input class="form-control input-sm text-right" type="text"  name="loaded_det_hr[]" id="loaded_det_hr'.$dri_row["LOAD_CODE"].'" value="'.$dri_row['DETENTION_HOUR'].'" onkeyup="javascript: calculate_total(\'\',\''.$dri_row["LOAD_CODE"].'\');" ></td>				
						<td style="text-align:right; color:#000; font-weight:bold; font-family:Arial, Helvetica, sans-serif; font-size:13px;">Unloaded Detention Hours :</td>
						
						<td><input class="form-control input-sm text-right" type="text"  name="unloaded_det_hr[]" id="unloaded_det_hr'.$dri_row["LOAD_CODE"].'" value="'.$dri_row['UNLOADED_DETENTION_HOUR'].'" onkeyup="javascript: calculate_total(\'\',\''.$dri_row["LOAD_CODE"].'\');" ></td>
					  </tr> 					
					  
						
					  
					  <tr style="cursor:pointer;" >					
						<td style="text-align:right; color:#000; font-weight:bold; font-family:Arial, Helvetica, sans-serif; font-size:18px;">Total Settlement  :</td>
						<td>&nbsp;</td>
						<td>&nbsp;</td>
						<td style="text-align:right; color:#000; font-weight:bold; font-family:Arial, Helvetica, sans-serif; font-size:18px;"><div class="input-group">
				<span class="input-group-addon">$</span>
<input name="totla_settelment[]" id="totla_settelment'.$dri_row["LOAD_CODE"].'" type="text" class="form-control input-sm text-right" style="font-size:18px;" value="" readonly></div></td>
					  </tr>
				</tbody>
			   </table>
		</div>
			  
			  <!-- <input type="button" name="f" onclick="calculate_total(\'\',\''.$dri_row["LOAD_CODE"].'\');"/>-->
		<!--total settelment2-->
	';
	 echo '	<script type="text/javascript" language="javascript">
	$(document).ready(function(){
	calculate_total(\'\',\''.$dri_row["LOAD_CODE"].'\');
	});
</script>';
	
	
	if($load_cnt==count($edit['driver_assign_loads'])	)
		{echo '<script>$(document).ready(function(){calculate_total(\'\',\''.$dri_row["LOAD_CODE"].'\');});</script>';}
	$load_cnt++; }
		
	 }
	 	//echo "<pre>LoadZero\n";
	 	//var_dump($edit['LoadZero']);
	 	//echo "</pre>";
	 
	 //! Allow load 0 (non-trip pay) for all, unless already saved for this period.
	 if( empty($edit['LoadZero']) ) {
	  
			 $name='';
			 $odometer_from=$odometer_to='';
			
			 #get range of miles 
			 $str_range_of_miles='';
			 if(count($edit['range_rates'])>0)
			 {$flag=0;
				$total_dist	=	 $dri_row["ACTUAL_DISTANCE"];	 
				 foreach( $edit['range_rates'] as $row_range)
				 {
					 $diff_miles	=	$row_range['RANGE_TO'] - $row_range['RANGE_FROM'];
					// if($dri_row["ACTUAL_DISTANCE"] > $row_range['RANGE_FROM'] && $dri_row["ACTUAL_DISTANCE"] > $row_range['RANGE_TO'])
					 { 
							//$cal	=	$diff_miles * $row_range['RANGE_RATE'];
							//$cal	=	 $row_range['RANGE_RATE'];
							$cal	=0;
							$str_range_of_miles.='<tr style="cursor:pointer;" >					
							<td width="14%" ><strong>'.$row_range['RANGE_NAME'].' :  '.$row_range['RANGE_FROM'].' TO '.$row_range['RANGE_TO'].'</strong></td>
							<td width="16%">'.$diff_miles.'</td>
							<td width="14%" >$ '.$row_range['RANGE_RATE'].'</td>
							<td width="16%"><div class="input-group">
				<span class="input-group-addon">$</span>
<input class="form-control input-sm" type="text"  name="range_miles0[]" id="range_miles0[]" value="'.$cal.'" onkeyup="javascript: calculate_total(\'\',0);"></div></td>
						</tr>
						<input type="hidden" name="range_name0[]" value="'.$row_range['RANGE_NAME'].'"/>
						<input type="hidden" name="range_code0[]" value="'.$row_range['RANGE_CODE'].'"/>
						<input type="hidden" name="range_from0[]" value="'.$row_range['RANGE_FROM'].'"/>
						<input type="hidden" name="range_to0[]" value="'.$row_range['RANGE_TO'].'"/>
						<input type="hidden" name="range_rate0[]" value="'.$row_range['RANGE_RATE'].'"/>'
						 ; 
					 }
						
				 }
			 }		 
			 
			 #get add manual rates to loads
			 $manual_rate_str='';
			 if(count($edit['manual_rates'])>0)
			 {
					$manual_rate_str	='<select name="manual_rates0" class="form-control input-sm" id="manual_rates0"  >
													<option value="">--Select--</option>';
													foreach($edit['manual_rates'] as  $mrate)
													{
														$manual_rate_str	.=	'<option value="'.$mrate['MANUAL_ID'].'">'.$mrate['MANUAL_RATE_CODE']." - ".$mrate['MANUAL_RATE_DESC'].'</option>';
													}	
					$manual_rate_str	.=	'</select>';
			 }
			 else
			 {
					 $manual_rate_str	='<select name="manual_rates0" class="form-control input-sm" id="manual_rates0"  >
													<option value="">--Select--</option>';
					$manual_rate_str	.=	'</select>';
			 }
			 $driver_rate_str='';
			 #get number of pallets for this load
			 			 
			$total_amount=0.00;
			if(count($edit['driver_rates'])>0)
			{
				foreach($edit['driver_rates'] as $row)
				{
				//	echo $row['CATEGORY_NAME']."=>".$dri_row['TOT_PALETS'];
				$per_amt='0';	//! WIPWIP

				//! SCR# 849 - Team Driver Feature
if( $this->debug ) {
	echo"<h3>Team Driver Feature2</h3>";
	echo "<pre>";
	var_dump($row['TEAM_DRIVER'], $dri_row['TEAM'], $dri_row);
	echo "</pre>";
}
					if( isset($row['TEAM_DRIVER']) && $row['TEAM_DRIVER'] == 1 ) continue;


					/*if(trim($row['CATEGORY_NAME'])=="Per Pallet")
					{	
						$per_amt=$dri_row['TOT_PALETS'];
					}
					else if(trim($row['CATEGORY_NAME'])=="Per Mile")
					{	
						$per_amt=$dri_row['ACTUAL_DISTANCE'];
					}
					else
					{
						$per_amt='1';	
					}*/
					$res_amount='';
					$res_amount	=	$row['RATE_PER_MILES'] * $per_amt;
					$total_amount	+=	$res_amount;
					$nm=str_replace(' ' , '_' ,  $row['CATEGORY_NAME']).'_'.$row['RATE_CODE'];
					
					//! SCR# 183 - Remove/Duplicate buttons
					$driver_rate_str	.=	'<tr style="cursor:pointer;" id="RATE_0'.$row['RATE_CODE'].'">					
					<input type="hidden" name="cat_type0[]" id="cat_type" value="'.$nm.'"/>
					<input type="hidden" name="category_name0[]" id="category_name" value="'.$row['CATEGORY_NAME'].'"/>
					<input type="hidden" name="rate_code0[]" id="rate_code" value="'.$row['RATE_CODE'].'"/>
					<input type="hidden" name="rate_name0[]" id="category_name" value="'.$row['RATE_NAME'].'"/>
					<input type="hidden" name="rate_bonus0[]" id="category_name" value="'.$row['RATE_BONUS'].'"/>
					<input type="hidden" name="free_hours0[]" id="0'.$nm.'_free" value="'.$row['FREE_HOURS'].'"/>
					<input type="hidden" name="card_advance0[]" id="card_Advance" value="0">
						<td style="text-align:right; color:#000; font-weight:bold; font-family:Arial, Helvetica, sans-serif; font-size:13px;">'.
					
					'<div class="btn-group" role="group" style="float: left;"><a class="btn btn-sm btn-danger" id="REMOVE_0'.$row['RATE_CODE'].'" title="Remove rate" onclick="javascript: remove_rate(\'0'.$row['RATE_CODE'].'\', 0);"><span class="glyphicon glyphicon-remove"></span></a>'.

					'<a class="btn btn-sm btn-success"  id="ADD_0'.$row['RATE_CODE'].'" title="Duplicate rate" onclick="javascript: duplicate_rate(\'0'.$row['RATE_CODE'].'\', 0);"><span class="glyphicon glyphicon-plus"></span></a></div>'.
					
					$row['RATE_NAME'].'<br>'.$row['CATEGORY_NAME'].
					($row['FREE_HOURS'] > 0 ? ' ['.$row['FREE_HOURS'].' free hours] ' : '').
						($row['RATE_BONUS'] == 1 ? ' (B)' : '').' :</td>
	<td><input name="0_qty[]" id="0'.$nm.'_qty" type="text" class="form-control input-sm text-right" value="'.number_format($per_amt,2,".","").'" onkeyup="javascript: calculate_total(\''.$nm.'\',0);"></td>
	<td><input name="0_amt[]" id="0'.$nm.'_amt" type="text" class="form-control input-sm text-right" value="'.$row['RATE_PER_MILES'].'" onkeyup="javascript: calculate_total(\''.$nm.'\',0);"></td>
						<td><div class="input-group">
				<span class="input-group-addon">$</span>
<input name="text_total0[]" id="0'.$nm.'_total" type="text" class="form-control input-sm text-right" value="'.$res_amount.'" readonly></div></td>
					  </tr>
					';
				}
			}
			
			//! Duncan - SCR# 70 - Fuel Card Advances
			//! SCR# 180 - display more detail
			if( ! empty($edit['fuel_card_advance'])) {
				foreach($edit['fuel_card_advance'] as $adv_row) {
					$nm = 'Advances_FCADV'.$adv_row['ADVANCE_CODE'];
					$adv_title = $adv_row['CARD_SOURCE'].' #'.$adv_row['TRANS_NUM'].' '.
						$adv_row['STATE'].' '.date("m/d/Y", strtotime($adv_row['TRANS_DATE']));
					
					$driver_rate_str	.=	'<tr style="cursor:pointer;" id="RATE_0'.$adv_row['ADVANCE_CODE'].'">					
					<input type="hidden" name="cat_type0[]" id="cat_type" value="Advances_FCADV">
					<input type="hidden" name="category_name0[]" id="category_name" value="Advances">
					<input type="hidden" name="rate_code0[]" id="category_name" value="FCADV">
					<input type="hidden" name="rate_name0[]" id="category_name" value="'.$adv_title.'">
					<input type="hidden" name="rate_bonus0[]" id="category_name" value="0">
					<input type="hidden" name="free_hours0[]" id="0'.$nm.'_free" value="'.$row['FREE_HOURS'].'"/>
					<input type="hidden" name="card_advance0[]" id="card_Advance" value="'.$adv_row['ADVANCE_CODE'].'">
						<td style="text-align:right; color:#000; font-weight:bold; font-family:Arial, Helvetica, sans-serif; font-size:13px;">'.
					
					'<a class="btn btn-sm btn-danger" style="float: left;" id="REMOVE_0'.$adv_row['ADVANCE_CODE'].'" title="Remove advance" onclick="javascript: remove_rate(\'0'.$adv_row['ADVANCE_CODE'].'\', 0);"><span class="glyphicon glyphicon-remove"></span></a>'.
					
						$adv_title.'<br>Advances'.
						' :</td>
	<td><input name="0_qty[]" id="0'.$nm.'_qty" type="text" class="form-control input-sm text-right" value="1" onkeyup="javascript: calculate_total(\''.$nm.'\',0);"></td>
	<td><input name="0_amt[]" id="0'.$nm.'_amt" type="text" class="form-control input-sm text-right" value="'.-floatval($adv_row['CASH_ADV']).'" onkeyup="javascript: calculate_total(\''.$nm.'\',0);"></td>
						<td><div class="input-group">
				<span class="input-group-addon">$</span>
<input name="text_total0[]" id="0'.$nm.'_total" type="text" class="form-control input-sm text-right" value="'.-floatval($adv_row['CASH_ADV']).'" readonly></div></td>
					  </tr>
					';
				}
			}
			
			
		//!Other one! NON-TRIP RELATED PAY
		echo '
		<!--info8-->
		<div class="well well-sm" id="LOAD_0">
		<input type="hidden" name="LOAD_CODE[]" value="0"/>
		<table class="table table-striped table-condensed table-bordered table-hover" >
			    <tbody>
				<tr style="cursor:pointer;" class="exspeedite-bg">					
						 <td width="10%"><a class="btn btn-sm btn-default" id="REMOVE_0" title="Remove load 0 from list" onclick="javascript: remove_load(0);"><span class="glyphicon glyphicon-remove"></span> Remove</a></td>
						 <td width="90%" class="text-center"><strong>NON-TRIP RELATED PAY</strong>
						 <input class="form-control input-sm text-right" type="hidden"  name="load_id[]" id="load_id0" value="0" readonly="readonly">
						 <input class="form-control input-sm text-right" type="hidden"  name="total_miles[]" id="total_miles0" value="">
						 </td>
					</tr>
				</tbody>
				</table>
		 <!--info8-->';
		 
		echo '
		<!--total settelment2-->
		<input type="hidden" name="tripID" id="tripID" value="'.$edit['tripID'].'"/>
		  <input type="hidden" name="driver_id" id="driver_id" value="'.$edit['id'].'" />
		   <table class="table table-striped table-condensed table-bordered table-hover" >
			<thead>
				  <!--<tr class="" style="background-color:#f5f5f5;">                
					<th class="text-center" colspan="4">&nbsp;</th>               
					<!--<th></th>
					<th></th>
					<th></th>
				  </tr>-->
				</thead>
				 <tbody>              

<input name="tractor" id="trctor" value="" type="hidden" class="form-control input-sm" >
<input name="approved_date0" id="approved_date0"  type="hidden" class="form-control input-sm" value="'.date('d-m-Y').'" >
<input name="trailer" id="trailer" value="" type="hidden" class="form-control input-sm" >
<input name="" type="hidden" class="form-control input-sm" >
<input name="odometer_from[]" id="odometer_from0" value="'.$odometer_from.'" type="hidden" class="form-control input-sm text-right"  onkeyup="return calculate_total_miles1(0,'.$edit['id'].');">
<input name="odometer_to[]" id="odometer_to0" type="hidden" class="form-control input-sm text-right" value="'.$odometer_to.'" onkeyup="return calculate_total_miles1(0,'.$edit['id'].');" >					  
				</tbody>';
				
				echo '<thead>
				  <tr class="" style="background-color:#ffffff;cursor:pointer;" >                
					<th class="text-center" width="30%">&nbsp;</th>               
					<th>Quantity</th>
					<th>Rate</th>
					<th>Pay</th>                
				  </tr>
				</thead>
				<tbody>  
				'.$driver_rate_str.'

					 <tr style="cursor:pointer;" >					
						<td style="text-align:right; color:#000; font-weight:bold; font-family:Arial, Helvetica, sans-serif; font-size:13px;">Manual Rates  :</td>
						<td>
							'.$manual_rate_str.'
						</td>
						<td><a class="btn btn-sm btn-success" onclick="javascript: return add_manual_fun(0,0);" href="javascript:void(0);">
								<span class="glyphicon glyphicon-plus"></span>Add</a></td>
						<td>&nbsp;</td>					
					  </tr>   
					  
					  <tr><td><div id="reload_manual_rate_div0" style="display:none;"><img src="images/loading.gif" border="1" style="position:absolute; margin-left:250px;margin-top:10px;"></div></td></tr>
					   <tr style="cursor:pointer;" >					
						<td colspan="4">
								<table width="100%" id="empty_row0" class="table table-striped table-condensed table-bordered table-hover">
								</table>
						</td>
					  </tr>
					  


				<tr style="cursor:pointer;" >					
						<td style="text-align:right; color:#000; font-weight:bold; font-family:Arial, Helvetica, sans-serif; font-size:16px;">Total Pay:</td>
						<td>&nbsp;</td>
						<td>&nbsp;</td>
						<td style="color:#000; font-weight:bold; font-family:Arial, Helvetica, sans-serif; font-size:16px; padding-right: 20px;">
						<input type="hidden" name="total_trip_amount[]" id="total_trip_amount0" value="'.$total_amount.'"><div class="text-right" id="total_trip_amount_div0"> $ '.$total_amount.'</div></td> </tr>

					  <tr style="cursor:pointer;" >					
						<td style="text-align:right; color:#000; font-weight:bold; font-family:Arial, Helvetica, sans-serif; font-size:16px;">Bonus-able Pay :</td>
						<td>&nbsp;</td>
						<td>&nbsp;</td>
						<td style="color:#000; font-weight:bold; font-family:Arial, Helvetica, sans-serif; font-size:16px; padding-right: 20px;">
						<input type="hidden" id="total_bonusable_amount0" value=""><div class="text-right" id="total_bonusable_amount_div0"></div></td> </tr>					
								
					  		
					  <tr style="cursor:pointer;" >					
						<td style="text-align:right; color:#000; font-weight:bold; font-family:Arial, Helvetica, sans-serif; font-size:13px;">Bonus :</td>
						<td><select name="bonus_allow[]" class="form-control input-sm" id="bonus_allow0" onchange="javascript:return calculate_total(0);" >
								<option value="No">No</option>
								<option value="Yes">Yes</option>
						</select></td>
						<td><input class="form-control input-sm text-right" type="text"  name="apply_bonus[]" id="apply_bonus0" onkeyup="javascript:return add_bonus(0);">Bonus in %</td>
						<td><input class="form-control input-sm text-right" type="text"  name="bonus_amount[]" id="bonus_amount0" >$ on Total Pay </td>
					  </tr>    
					  <tr style="cursor:pointer;" >					
						<td >&nbsp;</td>
						<td>&nbsp;</td>
						<td>&nbsp;</td>
						<td>&nbsp;</td>
					  </tr>
					  
<input class="form-control input-sm text-right" type="hidden"  name="handling_pallet[]" id="handling_pallet0" value="" onkeyup="javascript: calculate_total(\'\',0);" >
<input class="form-control input-sm text-right" type="hidden"  name="handling_pay[]" id="handling_pay0" value="" onkeyup="javascript: calculate_total(\'\',0);" >
<input class="form-control input-sm text-right" type="hidden"  name="loaded_det_hr[]" id="loaded_det_hr0" value="" onkeyup="javascript: calculate_total(\'\',0);" >
<input class="form-control input-sm text-right" type="hidden"  name="unloaded_det_hr[]" id="unloaded_det_hr0" value="" onkeyup="javascript: calculate_total(\'\',0);" >

					  
					  <tr style="cursor:pointer;" >					
						<td style="text-align:right; color:#000; font-weight:bold; font-family:Arial, Helvetica, sans-serif; font-size:18px;">Total Settlement  :</td>
						<td>&nbsp;</td>
						<td>&nbsp;</td>
						<td style="text-align:right; color:#000; font-weight:bold; font-family:Arial, Helvetica, sans-serif; font-size:18px;"><div class="input-group">
				<span class="input-group-addon">$</span>
				<input name="totla_settelment[]" id="totla_settelment0" type="text" class="form-control input-sm text-right" style="font-size:18px;" value="" readonly></div></td>
					  </tr>
			   </table>
			  <!-- <input type="button" name="f" onclick="calculate_total(\'\',0);"/>-->
		<!--total settelment2-->
		</div>
	';
	 echo '	<script type="text/javascript" language="javascript">
	$(document).ready(function(){
	calculate_total(\'\',0);
	});
</script>';
	
	
			if($load_cnt==count($edit['driver_assign_loads']) ) {
				echo '<script>$(document).ready(function(){calculate_total(\'\',0);});</script>';
			}
			$load_cnt++; 
		}
		echo '</div>';
	} else if(count($edit['driver_assign_load_with_stop'])==0 && isset($_POST['btn_search_load'])) {
		 echo '
		 <div style="margin-top:200px;">
		 <table class="table table-striped table-condensed table-bordered table-hover" >
			<tbody>
			<tr style="cursor:pointer;">
			<td colspan="4" align="center"><strong>Driver Load Information Not Available.</strong></td>
			</tr>
			</tbody>
			</table></div>';
	 }
	 else
	 {
		$load_cnt=1;
		
	  
			 $name='';
			 $odometer_from=$odometer_to='';
			
			 #get range of miles 
			 $str_range_of_miles='';
			 if(count($edit['range_rates'])>0)
			 {
				$total_dist	=	0;	 
				 foreach( $edit['range_rates'] as $row_range)
				 {
					 $diff_miles	=	$row_range['RANGE_TO'] - $row_range['RANGE_FROM'];
					// if($dri_row["ACTUAL_DISTANCE"] > $row_range['RANGE_FROM'] && $dri_row["ACTUAL_DISTANCE"] > $row_range['RANGE_TO'])
					 { 
							//$cal	=	$diff_miles * $row_range['RANGE_RATE'];
							//$cal	=	 $row_range['RANGE_RATE'];
							$cal	=0;
							$str_range_of_miles.='<tr style="cursor:pointer;" >					
							<td width="14%" ><strong>'.$row_range['RANGE_NAME'].' :  '.$row_range['RANGE_FROM'].' TO '.$row_range['RANGE_TO'].'</strong></td>
							<td width="16%">'.$diff_miles.'</td>
							<td width="14%" >$ '.$row_range['RANGE_RATE'].'</td>
							<td width="16%"><input class="form-control input-sm" type="text"  name="range_miles0[]" id="range_miles0[]" value="'.$cal.'" onkeyup="javascript: calculate_total(\'\',0);"></td>
						</tr>
						<input type="hidden" name="range_name0[]" value="'.$row_range['RANGE_NAME'].'"/>
						<input type="hidden" name="range_code0[]" value="'.$row_range['RANGE_CODE'].'"/>
						<input type="hidden" name="range_from0[]" value="'.$row_range['RANGE_FROM'].'"/>
						<input type="hidden" name="range_to0[]" value="'.$row_range['RANGE_TO'].'"/>
						<input type="hidden" name="range_rate0[]" value="'.$row_range['RANGE_RATE'].'"/>'
						 ; 
					 }
						
				 }
			 }		 
			 
			 #get add manual rates to loads
			 $manual_rate_str='';
			 if(count($edit['manual_rates'])>0)
			 {
					$manual_rate_str	='<select name="manual_rates0" class="form-control input-sm" id="manual_rates0"  >
													<option value="">--Select--</option>';
													foreach($edit['manual_rates'] as  $mrate)
													{
														$manual_rate_str	.=	'<option value="'.$mrate['MANUAL_ID'].'">'.$mrate['MANUAL_RATE_CODE']." - ".$mrate['MANUAL_RATE_DESC'].'</option>';
													}	
					$manual_rate_str	.=	'</select>';
			 }
			 else
			 {
					 $manual_rate_str	='<select name="manual_rates0" class="form-control input-sm" id="manual_rates0"  >
													<option value="">--Select--</option>';
					$manual_rate_str	.=	'</select>';
			 }
			 $driver_rate_str='';
			 #get number of pallets for this load
			$total_amount=0.00;
			if(count($edit['driver_rates'])>0)
			{
				foreach($edit['driver_rates'] as $row)
				{
				//	echo $row['CATEGORY_NAME']."=>".$dri_row['TOT_PALETS'];
				$per_amt='0';	
					/*if(trim($row['CATEGORY_NAME'])=="Per Pallet")
					{	
						$per_amt=$dri_row['TOT_PALETS'];
					}
					else if(trim($row['CATEGORY_NAME'])=="Per Mile")
					{	
						$per_amt=$dri_row['ACTUAL_DISTANCE'];
					}
					else
					{
						$per_amt='1';	
					}*/
					$res_amount='';
					$res_amount	=	$row['RATE_PER_MILES'] * $per_amt;
					$total_amount	+=	$res_amount;
					$nm=str_replace(' ' , '_' ,  $row['CATEGORY_NAME']).'_'.$row['RATE_CODE'];
					$driver_rate_str	.=	'<input type="hidden" name="cat_type0[]" id="cat_type" value="'.$nm.'"/>
					<input type="hidden" name="category_name0[]" id="category_name" value="'.$row['CATEGORY_NAME'].'"/>
					<input type="hidden" name="rate_code0[]" id="category_name" value="'.$row['RATE_CODE'].'"/>
					<input type="hidden" name="rate_name0[]" id="category_name" value="'.$row['RATE_NAME'].'"/>
					<input type="hidden" name="rate_bonus0[]" id="category_name" value="'.$row['RATE_BONUS'].'"/>
					<input type="hidden" name="free_hours0[]" id="0'.$nm.'_free" value="'.$row['FREE_HOURS'].'"/>
						<tr style="cursor:pointer;" >					
						<td style="text-align:right; color:#000; font-weight:bold; font-family:Arial, Helvetica, sans-serif; font-size:13px;">'.$row['RATE_NAME'].'<br>'.$row['CATEGORY_NAME'].
					($row['FREE_HOURS'] > 0 ? ' ['.$row['FREE_HOURS'].' free hours] ' : '').
						($row['RATE_BONUS'] == 1 ? ' (B)' : '').' :</td>
	<td><input name="0_qty[]" id="0'.$nm.'_qty" type="text" class="form-control input-sm text-right" value="'.number_format($per_amt,2,".","").'" onkeyup="javascript: calculate_total(\''.$nm.'\',0);"></td>
	<td><input name="0_amt[]" id="0'.$nm.'_amt" type="text" class="form-control input-sm text-right" value="'.$row['RATE_PER_MILES'].'" onkeyup="javascript: calculate_total(\''.$nm.'\',0);"></td>
						<td><div class="input-group">
				<span class="input-group-addon">$</span>
<input name="text_total0[]" id="0'.$nm.'_total" type="text" class="form-control input-sm text-right" value="'.$res_amount.'" onkeyup="javascript: calculate_total(\''.$nm.'\',0);"></div></td>
					  </tr>
					';
				}
			}
		
		//! Third one NON-TRIP RELATED PAY	
		echo '
		<div style="margin-top:200px;">
		<!--info9-->
		<div class="well well-sm" id="LOAD_0">
		<input type="hidden" name="LOAD_CODE[]" value="0"/>
		<table class="table table-striped table-condensed table-bordered table-hover" >
			    <tbody>
				<tr style="cursor:pointer;" class="exspeedite-bg">					
						 <td width="10%"><a class="btn btn-sm btn-default" id="REMOVE_0" title="Remove load 0 from list" onclick="javascript: remove_load(0);"><span class="glyphicon glyphicon-remove"></span> Remove</a></td>
						 <td width="90%" class="text-center"><strong>NON-TRIP RELATED PAY.</strong>
						 <input class="form-control input-sm text-right" type="hidden"  name="load_id[]" id="load_id0" value="0" readonly="readonly">
						 <input class="form-control input-sm text-right" type="hidden"  name="total_miles[]" id="total_miles0" value="">
						 </td>
					</tr>
				</tbody>
				</table>
		 <!--info9-->';
		 
		echo '
		<!--total settelment2-->
		<input type="hidden" name="tripID" id="tripID" value="'.$edit['tripID'].'"/>
		  <input type="hidden" name="driver_id" id="driver_id" value="'.$edit['id'].'" />
		   <table class="table table-striped table-condensed table-bordered table-hover" >
			<thead>
				  <!--<tr class="" style="background-color:#f5f5f5;">                
					<th class="text-center" colspan="4">&nbsp;</th>               
					<!--<th></th>
					<th></th>
					<th></th>
				  </tr>-->
				</thead>
				 <tbody>              

<input name="tractor" id="trctor" value="" type="hidden" class="form-control input-sm" >
<input name="approved_date0" id="approved_date0"  type="hidden" class="form-control input-sm" value="'.date('d-m-Y').'" >
<input name="trailer" id="trailer" value="" type="hidden" class="form-control input-sm" >
<input name="" type="hidden" class="form-control input-sm" >
<input name="odometer_from[]" id="odometer_from0" value="'.$odometer_from.'" type="hidden" class="form-control input-sm text-right"  onkeyup="return calculate_total_miles1(0,'.$edit['id'].');">
<input name="odometer_to[]" id="odometer_to0" type="hidden" class="form-control input-sm text-right" value="'.$odometer_to.'" onkeyup="return calculate_total_miles1(0,'.$edit['id'].');" >					  
				</tbody>';
				
				echo '<thead>
				  <tr class="" style="background-color:#ffffff;cursor:pointer;" >                
					<th class="text-center">&nbsp;</th>               
					<th>Quantity</th>
					<th>Rate</th>
					<th>Pay</th>                
				  </tr>
				</thead>
				<tbody>  
				'.$driver_rate_str.'

					 <tr style="cursor:pointer;" >					
						<td style="text-align:right; color:#000; font-weight:bold; font-family:Arial, Helvetica, sans-serif; font-size:13px;">Manual Rates  :</td>
						<td>
							'.$manual_rate_str.'
						</td>
						<td><a class="btn btn-sm btn-success" onclick="javascript: return add_manual_fun(0,0);" href="javascript:void(0);">
								<span class="glyphicon glyphicon-plus"></span>Add</a></td>
						<td>&nbsp;</td>					
					  </tr>   
					  
					  <tr><td><div id="reload_manual_rate_div0" style="display:none;"><img src="images/loading.gif" border="1" style="position:absolute; margin-left:250px;margin-top:10px;"></div></td></tr>
					   <tr style="cursor:pointer;" >					
						<td colspan="4">
								<table width="100%" id="empty_row0" class="table table-striped table-condensed table-bordered table-hover">
								</table>
						</td>
					  </tr>
					  


				<tr style="cursor:pointer;" >					
						<td style="text-align:right; color:#000; font-weight:bold; font-family:Arial, Helvetica, sans-serif; font-size:16px;">Total Pay:</td>
						<td>&nbsp;</td>
						<td>&nbsp;</td>
						<td style="color:#000; font-weight:bold; font-family:Arial, Helvetica, sans-serif; font-size:16px; padding-right: 20px;">
						<input type="hidden" name="total_trip_amount[]" id="total_trip_amount0" value="'.$total_amount.'"><div class="text-right" id="total_trip_amount_div0"> $ '.$total_amount.'</div></td> </tr>

					  <tr style="cursor:pointer;" >					
						<td style="text-align:right; color:#000; font-weight:bold; font-family:Arial, Helvetica, sans-serif; font-size:16px;">Bonus-able Pay :</td>
						<td>&nbsp;</td>
						<td>&nbsp;</td>
						<td style="color:#000; font-weight:bold; font-family:Arial, Helvetica, sans-serif; font-size:16px; padding-right: 20px;">
						<input type="hidden" id="total_bonusable_amount0" value=""><div class="text-right" id="total_bonusable_amount_div0"></div></td> </tr>					
								
					  		
					  <tr style="cursor:pointer;" >					
						<td style="text-align:right; color:#000; font-weight:bold; font-family:Arial, Helvetica, sans-serif; font-size:13px;">Bonus :</td>
						<td><select name="bonus_allow[]" class="form-control input-sm" id="bonus_allow0" onchange="javascript:return calculate_total(0);" >
								<option value="No">No</option>
								<option value="Yes">Yes</option>
						</select></td>
						<td><input class="form-control input-sm text-right" type="text"  name="apply_bonus[]" id="apply_bonus0" onkeyup="javascript:return add_bonus(0);">Bonus in %</td>
						<td><input class="form-control input-sm text-right" type="text"  name="bonus_amount[]" id="bonus_amount0" >$ on Total Pay </td>
					  </tr>    
					  <tr style="cursor:pointer;" >					
						<td >&nbsp;</td>
						<td>&nbsp;</td>
						<td>&nbsp;</td>
						<td>&nbsp;</td>
					  </tr>
					  
<input class="form-control input-sm text-right" type="hidden"  name="handling_pallet[]" id="handling_pallet0" value="" onkeyup="javascript: calculate_total(\'\',0);" >
<input class="form-control input-sm text-right" type="hidden"  name="handling_pay[]" id="handling_pay0" value="" onkeyup="javascript: calculate_total(\'\',0);" >
<input class="form-control input-sm text-right" type="hidden"  name="loaded_det_hr[]" id="loaded_det_hr0" value="" onkeyup="javascript: calculate_total(\'\',0);" >
<input class="form-control input-sm text-right" type="hidden"  name="unloaded_det_hr[]" id="unloaded_det_hr0" value="" onkeyup="javascript: calculate_total(\'\',0);" >

					  
					  <tr style="cursor:pointer;" >					
						<td style="text-align:right; color:#000; font-weight:bold; font-family:Arial, Helvetica, sans-serif; font-size:18px;">Total Settlement  :</td>
						<td>&nbsp;</td>
						<td>&nbsp;</td>
						<td style="text-align:right; color:#000; font-weight:bold; font-family:Arial, Helvetica, sans-serif; font-size:18px;"><div class="input-group">
				<span class="input-group-addon">$</span>
				<input name="totla_settelment[]" id="totla_settelment0" type="text" class="form-control input-sm text-right" style="font-size:18px;" value="" readonly></div></td>
					  </tr>
			   </table>
			  <!-- <input type="button" name="f" onclick="calculate_total(\'\',0);"/>-->
		<!--total settelment2-->
		</div>
	';
	 echo '	<script type="text/javascript" language="javascript">
	$(document).ready(function(){
	calculate_total(\'\',0);
	});
</script>';
	
	if($load_cnt==(is_array($edit['driver_assign_loads']) ? count($edit['driver_assign_loads']) : 0)	)
		{echo '<script>$(document).ready(function(){calculate_total(\'\',0);});</script>';}
	
	 
	 }
    
    echo  '<!--weekly settelment-->
  <table class="table table-striped table-condensed table-bordered table-hover" >
          		  <tr style="cursor:pointer;" >					
					<td width="23%" style="text-align:right; color:#000; font-weight:bold; font-family:Arial, Helvetica, sans-serif; font-size:18px; text-transform:uppercase;">WEEK&nbsp;ENDING&nbsp;TOTAL:</td>
					<td width="26%">&nbsp;</td>
					<td width="25%">&nbsp;</td>
					<td width="26%" style="color:#000; font-weight:bold; font-family:Arial, Helvetica, sans-serif; font-size:13px;"></td>
				  </tr>
                  
                  <tr style="cursor:pointer;"  >					
					<td style="text-align:right; color:#000; font-weight:bold; font-family:Arial, Helvetica, sans-serif; font-size:18px; text-transform:uppercase;">TRIP PAY:</td>
					<td>&nbsp;</td>
					<td>&nbsp;</td>
					<td><div class="input-group">
				<span class="input-group-addon">$</span><input name="final_trip_pay" id="final_trip_pay" type="text" class="form-control input-sm text-right" style="font-size:18px;" value="" readonly></div></td>
				  </tr>
                  
                  <tr style="cursor:pointer;"  >					
					<td style="text-align:right; color:#000; font-weight:bold; font-family:Arial, Helvetica, sans-serif; font-size:18px; text-transform:uppercase;">Bonus:</td>
					<td>&nbsp;</td> 
					<td>&nbsp;</td>
					<td><div class="input-group">
				<span class="input-group-addon">$</span><input name="final_bonus" id="final_bonus" type="text" class="form-control input-sm text-right" style="font-size:18px;" value="" readonly></div></td>
				  </tr>
                  
                  <tr style="cursor:pointer;"  >					
					<td style="text-align:right; color:#000; font-weight:bold; font-family:Arial, Helvetica, sans-serif; font-size:18px; text-transform:uppercase;">Handling:</td>
					<td>&nbsp;</td>
					<td>&nbsp;</td>
					<td><div class="input-group">
				<span class="input-group-addon">$</span><input name="final_handling" id="final_handling" type="text" class="form-control input-sm text-right" style="font-size:18px;" value="" readonly></div></td>
				  </tr>
                  
                  <tr style="cursor:pointer;" >					
					<td style="text-align:right; color:#000; font-weight:bold; font-family:Arial, Helvetica, sans-serif; font-size:18px; text-transform:uppercase;">GROSS EARNINGS</td>
					<td>&nbsp;</td>
					<td>&nbsp;</td>
					<td style="color:#000; font-weight:bold; font-family:Arial, Helvetica, sans-serif; font-size:18px;" >
					<div class="input-group">
				<span class="input-group-addon">$</span><input name="final_settlement" id="final_settlement" type="text" class="form-control input-sm text-right" style="font-size:18px;" value="" readonly></div>
					<div id="final_settlement_div"> </div></td>
				  </tr>
          </table>
    <!--weekly settelment--> 
         
        </div>
    <div style="clear:both"></div>
  <button name="final_savepayrate" type="submit" class="btn btn-md btn-success" onclick="return chk_final_confirmation();" disabled="disabled">
		<span class="glyphicon glyphicon-ok"></span>
		Finalize Settlement
		</button>
    		<button name="savepayrate" type="submit" class="btn btn-md btn-default" onclick="return chk_confirmation();">
		<span class="glyphicon glyphicon-ok"></span>
		Save changes
		</button>
    		</div>
  
</div>
		</form>
		';	
	}
	
	#render function for client rate screem
	public function render_client_rate_screen($edit)
	{
		
		//echo '<pre/>';print_r($edit);
		$check=$check1='';
		if($edit['hazmat']=='1')
		{$check='checked="checked"';}
			if($edit['temp']=='1')
		{$check1='checked="checked"';}
		
		if(count($edit['client_cat'])>0)
		{
			$cat_option='';
			foreach($edit['client_cat'] as $cat)
			{$selected='';
				/*if($cat['CLIENT_CAT']==$edit['category'])
				{
					$selected='selected="selected"';
				}*/
				$cat_option.='<option value="'.$cat['CLIENT_CAT'].'"   '.$selected.'>'.$cat['CATEGORY_NAME'].'</option>';
			}
		}
		$cat_result="<tbody>";
		//echo '<pre/>';print_r($edit['client_rate_cat']);
		if(count($edit['client_rate_cat'])>0)
		{
			foreach($edit['client_rate_cat'] as $cr)
			{
				$cat_result.="<tr style='cursor:pointer;' >
									<td class='text-center'><input type='checkbox'  name='rate_ids[]' value=".$cr['CLIENT_RATE_ID']."></td>
									<td style='text-align:center;'>".$cr['RATE_CODE']."</td>
									<td style='text-align:center;'>".$cr['RATE_NAME']."</td>
									<td style='text-align:center;'>".$cr['RATE_DESC']."</td>
									<td style='text-align:center;'>$  ".$cr['RATE_PER_MILES']."</td>
									 <td style='text-align:center;'>".$cr['CATEGORY_NAME']."</td>
									  <td style='text-align:center;'>".$cr['TAXABLE']."</td>
								  </tr>";
			}
		}
		
		$cat_result.="</tbody>";
		$pall_res='';
		if(count($edit['pallet_rate'])>0)
		{
			foreach($edit['pallet_rate'] as $pl)
			{
				$pall_res.="<tr style='cursor:pointer;' >
				<td class='text-center' style='width:34%'><a onclick='javascript: return delete_client_pallet_charge(".$pl['PALLET_ID'].",".$edit['CODE'].",&quot;".$edit['USER_TYPE']."&quot;);' href='javascript:void(0);' class='btn btn-default btn-xs' data-original-title='' title=''> <span style='font-size: 14px;'><span class='glyphicon glyphicon-trash'></span> </span> </a>&nbsp;&nbsp;<a class='btn btn-sm btn-success'  data-target='#myModal' data-toggle='modal' onclick='javascript:return set_pallet_value(".$pl['PALLET_ID'].");'><span class='glyphicon glyphicon-plus'></span> </a>&nbsp;&nbsp;<a  class='btn btn-default' onclick='javascript:return fetch_pallet_value(".$pl['PALLET_ID'].");'>Show Pallet Rates </a></td>
			<td style='text-align:center;width:18%'>".$pl['CUST_NAME']."</td>
			<td style='text-align:center;width:13%'>".$pl['CUST_CITY']."</td>
			<td style='text-align:center;width:8%'>".$pl['CUST_STATE']."</td>
			<td style='text-align:center;width:8%'>".$pl['CUST_ZIP']."</td>
			<td style='text-align:center;width:8%'>".$pl['MIN_CHARGE']."</td>
				<td style='text-align:center;width:8%'>".$pl['MAX_CHARGE']."</td>
			  </tr>
			  <tr id='pallet_rate_".$pl['PALLET_ID']."'>
		
			  </tr>";
			}
		}
// 		else
// 		{
// 			$pall_res="<tr style='cursor:pointer;' >
// 				<td style='text-align:center;' colspan='15'>NO PALLET RATES ARE AVAILABLE !</td>
// 			  </tr>";
// 		}
		
		$det_res='';
		if(count($edit['detention_rate'])>0)
		{ 
			foreach($edit['detention_rate'] as $dl)
			{
				$det_res.="<tr style='cursor:pointer;' >
				<td class='text-center' style='width:28.49%'>
                <a onclick='javascript: return delete_detention_charge(".$dl['DETENTION_ID'].",".$edit['CODE'].",&quot;".$edit['USER_TYPE']."&quot;);' href='javascript:void(0);' class='btn btn-default btn-xs' data-original-title='' > <span style='font-size: 14px;'><span class='glyphicon glyphicon-trash'></span> </span> </a>&nbsp;&nbsp;
               
                <a class='btn btn-sm btn-success'  onclick='javascript: return show_hide_div(&quot;add_detention&quot;);'><span class='glyphicon glyphicon-edit'></span> </a>
                </td>
			<td style='text-align:center;width:37.49%'>".$dl['DET_CUST_NAME']."</td>
			<td style='text-align:center;width:17.49%'>".$dl['DET_MIN_CHARGE']."</td>
			<td style='text-align:center;'>".$dl['DET_MAX_CHARGE']."</td>
			  </tr>
			 
			 
              <tr style='cursor:pointer;' >
              <td class='text-center'colspan=5>
			  <label>Loaded Detention Charges </label> &nbsp;
               <a class='btn btn-sm btn-success'  data-target='#mydetentionModal' data-toggle='modal' onclick='javascript:return set_detention_value(".$dl['DETENTION_ID'].");'><span class='glyphicon glyphicon-plus'></span> </a>&nbsp;&nbsp;
                <a  class='btn btn-default' onclick='javascript:return fetch_detention_value(".$dl['DETENTION_ID'].");'>Show Loaded Detention Rates </a>&nbsp;&nbsp;
              </td></tr>
			   <tr id='detention_rate_".$dl['DETENTION_ID']."'>
		
			  </tr>
			 <tr style='cursor:pointer;' >
              <td class='text-center'colspan=5>
			  <label>Unloaded Detention Charges </label> &nbsp;
               <a class='btn btn-sm btn-success'  data-target='#myunloadeddetentionModal' data-toggle='modal' onclick='javascript:return set_un_detention_value(".$dl['DETENTION_ID'].");'><span class='glyphicon glyphicon-plus'></span> </a>&nbsp;&nbsp;
                <a  class='btn btn-default' onclick='javascript:return fetch_unloaded_detention_value(".$dl['DETENTION_ID'].");'>Show Unloaded Detention Rates </a>&nbsp;&nbsp;
              </td></tr>
			   <tr id='unloaded_detention_rate_".$dl['DETENTION_ID']."'>
		
			  </tr>";
			}
		}
		else
		{
			$det_res="<tr style='cursor:pointer;' >
				<td style='text-align:center;' colspan='15'>NO DETENTION CHARGES ARE AVAILABLE !</td>
			  </tr>";
		}
		
		
		$handling_result='<input type="hidden" value="" id="HANDLE_CODE" name="HANDLE_CODE">';
		if(count($edit['client_handling'])>0)
		{
			foreach($edit['client_handling'] as $chand)
			{
				$handling_result.="<tr style='cursor:pointer;' >
				<td class='text-center' style='width:3%;'><a onclick='javascript: return delete_client_handling_charge(".$chand['HANDLING_ID'].",".$edit['CODE'].",&quot;".$edit['USER_TYPE']."&quot;);' href='javascript:void(0);' class='btn btn-default btn-xs' data-original-title='' title=''> <span style='font-size: 14px;'><span class='glyphicon glyphicon-trash'></span> </span> </a><a  href='javascript:void(0);' class='btn btn-default btn-xs' data-remote='' data-target='#myModal_handling_".$chand['HANDLING_ID']."' data-toggle='modal' onclick='javascript:return set_handle_id(".$chand['HANDLING_ID']."); ' > <span style='font-size: 14px;'><span class='glyphicon glyphicon-edit'></span> </span> </a></td>
			<td style='text-align:center;width:11%;'>".$chand['ORIGIN_NAME']."</td>
			<td style='text-align:center;width:9%;'>".$chand['FROM_CITY']."</td>
			<td style='text-align:center;width:5%;'>".$chand['FROM_STATE']."</td>
			<td style='text-align:center;width:6%;'>".$chand['FROM_ZIP']."</td>
			<td style='text-align:center;width:7%;'>".$chand['FROM_ZONE']."</td>
			<td style='text-align:center;width:11%;'>".$chand['CONSIGNEE_NAME']."</td>
			<td style='text-align:center;width:9%;'>".$chand['TO_CITY']."</td>
			<td style='text-align:center;width:5%;'>".$chand['TO_STATE']."</td>
			<td style='text-align:center;width:6%;'>".$chand['TO_ZIP']."</td>
			<td style='text-align:center;width:7%;'>".$chand['TO_ZONE']."</td>
			<td style='text-align:center;width:7%;'>".$chand['PALLET']."</td>
			 <td style='text-align:center;width:6%;'>$ ".$chand['INVOICE_COST']."</td>
     		<td style='text-align:center;width:6%;'>$ ".$chand['DRIVER_COST']."</td>
			  </tr>
			  <tr>
			  <td colspan='14'>
			
			  <div aria-hidden='false' aria-labelledby='myModal_handling_".$chand['HANDLING_ID']."' role='dialog' tabindex='-1' class='modal fade in' id='myModal_handling_".$chand['HANDLING_ID']."' >
					<div class='modal-dialog modal-lg'>
						<div role='main' class='modal-content form-horizontal'>	 

<div style='font-size: 14px; body:inherit;' class='modal-body'>
</form>
<form name='edit_handle_form_".$chand['HANDLING_ID']."' id='edit_handle_form_".$chand['HANDLING_ID']."' action='exp_addclientrate.php?TYPE=".$edit['USER_TYPE']."&CODE=".$edit['CODE']."' method='post'>
<h2>Edit Handling Charges
		<div class='btn-group'>
			<button type='submit' name='btn_edit_handle' class='btn btn-md btn-success' onclick='javascript:return chk_edit_handling_charge(".$chand['HANDLING_ID'].");'><span class='glyphicon glyphicon-ok'></span> Save Changes</button><a data-dismiss='modal' class='btn btn-md btn-default'><span class='glyphicon glyphicon-remove'></span> Cancel</a>
			</div>
	</h2>
	<script type='text/javascript' language='javascript'>
	function chk_edit_handling_charge(hn_id)
{
	var origin_name=document.getElementById('ed_shipper_name'+hn_id);
	var from_city=document.getElementById('ed_from_city'+hn_id);
	var from_state=document.getElementById('ed_from_state'+hn_id);
	var from_zip=document.getElementById('ed_from_zip'+hn_id);
	var from_zone=document.getElementById('ed_from_zone'+hn_id);
	var consignee_name=document.getElementById('ed_Consignee_name'+hn_id);
	var to_city=document.getElementById('ed_to_city'+hn_id);
	var to_state=document.getElementById('ed_to_state'+hn_id);
	var to_zip=document.getElementById('ed_to_zip'+hn_id);
	var to_zone=document.getElementById('ed_to_zone'+hn_id);
	var pallets=document.getElementById('ed_pallet'+hn_id);
	var invoice=document.getElementById('ed_invoice'+hn_id);
	var driver_rate=document.getElementById('ed_driverr_rate'+hn_id);
	
	if(origin_name.value=='')
	{
		alert('Please enter shipper name.');
		return false;
	}
	else if(from_city.value=='')
	{
		alert('Please enter shipper city.');
		return false;
	}
	else if(from_state.value=='')
	{
		alert('Please enter shipper state.');
		return false;
	}
	else if(from_zip.value=='')
	{
		alert('Please enter shipper zipcode.');
		return false;
	}
	else if(from_zone.value=='')
	{
		alert('Please enter from zone.');
		return false;
	}
	else if(consignee_name.value=='')
	{
		alert('Please enter consignee name.');
		return false;
	}
	else if(to_city.value=='')
	{
		alert('Please enter consignee city.');
		return false;
	}
	else if(to_state.value=='')
	{
		alert('Please enter consignee state.');
		return false;
	}
	else if(to_zip.value=='')
	{
		alert('Please enter consignee zipcode.');
		return false;
	}
	else if(to_zone.value=='')
	{
		alert('Please enter to zone.');
		return false;
	}
	else if(pallets.value=='')
	{
		alert('Please enter number of pallets.');
		return false;
	}
	else if(isNaN(pallets.value))
	{
		alert('Please enter only number.');
		return false;
	}
	else if(invoice.value=='')
	{
		alert('Please enter invoice rate.');
		return false;
	}
	else if(isNaN(invoice.value))
	{
		alert('Please enter only number.');
		return false;
	}
	else if(driver_rate.value=='')
	{
		alert('Please enter driver rate.');
		return false;
	}
	else if(isNaN(driver_rate.value))
	{
		alert('Please enter only number.');
		return false;
	}
	else
	{
		return true;
	}
}
	</script>
	
	<div class='form-group'>
		<div class='col-sm-5'>
			<div class='form-group'>
				<label class='col-sm-4 control-label' for='Shipper'>Shipper</label>
				<div class='col-sm-8'>
					
			<input type='text' placeholder='Shipper' id='ed_shipper_name".$chand['HANDLING_ID']."' name='ed_shipper_name' class='form-control text-right' value='".stripslashes($chand['ORIGIN_NAME'])."'>
			<input type='hidden' id='ed_handle_id".$chand['HANDLING_ID']."' name='ed_handle_id' value='".$chand['HANDLING_ID']."'/>
				</div>
			</div>
			<div class='form-group'>
				<label class='col-sm-4 control-label' for='City'>City</label>
				<div class='col-sm-8'>
					
			<input type='text' placeholder='Origin city' id='ed_from_city".$chand['HANDLING_ID']."' name='ed_from_city' class='form-control text-right' value='".$chand['FROM_CITY']."'>
				</div>
			</div>
			<div class='form-group'>
				<label class='col-sm-4 control-label' for='State'>State</label>
				<div class='col-sm-4'>
					
			<input type='text' placeholder='Origin State' id='ed_from_state".$chand['HANDLING_ID']."' name='ed_from_state' class='form-control text-right' value='".$chand['FROM_STATE']."'>
				</div>
				<div class='col-sm-4'>
			<input type='text' placeholder='Origin Zipcode' id='ed_from_zip".$chand['HANDLING_ID']."' name='ed_from_zip' class='form-control text-right' value='".$chand['FROM_ZIP']."'>
				</div>
			</div>
            
            <div class='form-group'>
				<label class='col-sm-4 control-label' for='Zonecode'>Zonecode</label>
				<div class='col-sm-8'>
					
			<input type='text' placeholder='Zonecode' id='ed_from_zone".$chand['HANDLING_ID']."' name='ed_from_zone' class='form-control text-right' value='".$chand['FROM_ZONE']."'>
				</div>
			</div>
            
            
			<div class='form-group'>
				<label class='col-sm-4 control-label' for='Consignee'>Consignee</label>
				<div class='col-sm-8'>
					
			<input type='text' placeholder='Consignee' id='ed_Consignee_name".$chand['HANDLING_ID']."' name='ed_Consignee_name' class='form-control text-right' value='".stripslashes($chand['CONSIGNEE_NAME'])."'>
				</div>
			</div>
            
            
            	
			<div class='form-group'>
				<label class='col-sm-4 control-label' for='City'>City</label>
				<div class='col-sm-8'>
					
			<input type='text' placeholder='Destination city' id='ed_to_city".$chand['HANDLING_ID']."' name='ed_to_city' class='form-control text-right' value='".$chand['TO_CITY']."'>
				</div>
			</div>
			<div class='form-group'>
				<label class='col-sm-4 control-label' for='State'>State</label>
				<div class='col-sm-4'>
					
			<input type='text' placeholder='Destination State' id='ed_to_state".$chand['HANDLING_ID']."' name='ed_to_state' class='form-control text-right' value='".$chand['TO_STATE']."'>
				</div>
				<div class='col-sm-4'>
			<input type='text' placeholder='Zipcode' id='ed_to_zip".$chand['HANDLING_ID']."' name='ed_to_zip' class='form-control text-right' value='".$chand['TO_ZIP']."'>
				</div>
			</div>
            
            
             <div class='form-group'>
				<label class='col-sm-4 control-label' for='Zonecode'>Zonecode</label>
				<div class='col-sm-8'>
					
			<input type='text' placeholder='Zonecode' id='ed_to_zone".$chand['HANDLING_ID']."' name='ed_to_zone' class='form-control text-right' value='".$chand['TO_ZONE']."'>
				</div>
			</div>
			
			<script type='text/javascript' language='javascript' >
	 
	var handle_id='".$chand['HANDLING_ID']."';
 
	var ZIP_CODE_zips = new Bloodhound({
			  name: 'ZIP_CODE',
			  remote: {
			  	url: 'exp_suggest_zip.php?code=Balsamic&query=%QUERY',
			  	wildcard: '%QUERY'
				  },
			  datumTokenizer: Bloodhound.tokenizers.obj.whitespace('ZipCode'),
			  queryTokenizer: Bloodhound.tokenizers.whitespace
			});
			
			ZIP_CODE_zips.initialize();

			$('#ed_to_zip'+handle_id).typeahead(null, {
			  name: 'ZIP_CODE',
			  minLength: 2,
			  limit: 10,
			  highlight: true,
			  display: 'ZipCode',
			  source: ZIP_CODE_zips,
			    templates: {
			    suggestion: Handlebars.compile(
			      '<p><strong>{{ZipCode}}</strong> – {{CityMixedCase}}, {{State}}</p>'
			    )
			  }
			});
		
			$('#ed_from_zip'+handle_id).typeahead(null, {
			  name: 'ZIP_CODE',
			  minLength: 2,
			  highlight: true,
			  displayKey: 'ZipCode',
			  source: ZIP_CODE_zips.ttAdapter(),
			    templates: {
			    suggestion: Handlebars.compile(
			      '<p><strong>{{ZipCode}}</strong> – {{CityMixedCase}}, {{State}}</p>'
			    )
			  }
			});
			
			$('#ed_from_zone'+handle_id).typeahead(null, {
			  name: 'ZIP_CODE',
			  minLength: 2,
			  highlight: true,
			  displayKey: 'ZipCode',
			  source: ZIP_CODE_zips.ttAdapter(),
			    templates: {
			    suggestion: Handlebars.compile(
			      '<p><strong>{{ZipCode}}</strong> – {{CityMixedCase}}, {{State}}</p>'
			    )
			  }
			});
			
			$('#ed_to_zone'+handle_id).typeahead(null, {
			  name: 'ZIP_CODE',
			  minLength: 2,
			  highlight: true,
			  displayKey: 'ZipCode',
			  source: ZIP_CODE_zips.ttAdapter(),
			    templates: {
			    suggestion: Handlebars.compile(
			      '<p><strong>{{ZipCode}}</strong> – {{CityMixedCase}}, {{State}}</p>'
			    )
			  }
			});
			
			$('#ed_from_city'+handle_id).typeahead(null, {
			  name: 'ZIP_CODE',
			  minLength: 2,
			  highlight: true,
			  displayKey: 'CityMixedCase',
			  source: ZIP_CODE_zips.ttAdapter(),
			    templates: {
			    suggestion: Handlebars.compile(
			      '<p><strong>{{CityMixedCase}}</strong> – {{State}}, {{ZipCode}}</p>'
			    )
			  }
			});
			
			$('#ed_to_city'+handle_id).typeahead(null, {
			  name: 'ZIP_CODE',
			  minLength: 2,
			  highlight: true,
			  displayKey: 'CityMixedCase',
			  source: ZIP_CODE_zips.ttAdapter(),
			    templates: {
			    suggestion: Handlebars.compile(
			      '<p><strong>{{CityMixedCase}}</strong> – {{State}}, {{ZipCode}}</p>'
			    )
			  }
			});
			
			/* for origin handle charges*/
			$('#ed_from_zip'+handle_id).bind('typeahead:selected', function(obj, datum, name) {
			 	var new_fr=$('#HANDLE_CODE').val(); 
															   $('input#ed_from_city'+new_fr).val(datum.CityMixedCase);
																$('input#ed_from_state'+new_fr).val(datum.State);
                                                                $('input#ed_from_zip'+new_fr).val(datum.ZipCode);
                                                });
			$('#ed_from_zone'+handle_id).bind('typeahead:selected', function(obj, datum, name) {
			 	var new_fr=$('#HANDLE_CODE').val(); 
															   $('input#ed_from_city'+new_fr).val(datum.CityMixedCase);
																$('input#ed_from_state'+new_fr).val(datum.State);
                                                                $('input#ed_from_zip'+new_fr).val(datum.ZipCode);
                                                });
			$('#ed_from_city'+handle_id).bind('typeahead:selected', function(obj, datum, name) {alert(datum.toSource());
				var new_fr=$('#HANDLE_CODE').val(); 
                                                                $('input#ed_from_city'+new_fr).val(datum.CityMixedCase);
                                                                $('input#ed_from_state'+new_fr).val(datum.State);
                                                                $('input#ed_from_zip'+new_fr).val(datum.ZipCode);
                                                });
												
			/* for destination freight charges*/
			$('#ed_to_zip'+handle_id).bind('typeahead:selected', function(obj, datum, name) {//alert(datum.toSource());
				var new_fr=$('#HANDLE_CODE').val(); 
                                                                $('input#ed_to_city'+new_fr).val(datum.CityMixedCase);
                                                                $('input#ed_to_state'+new_fr).val(datum.State);
                                                                $('input#ed_to_zip'+new_fr).val(datum.ZipCode);
                                                });
			$('#ed_to_zone'+handle_id).bind('typeahead:selected', function(obj, datum, name) {//alert(datum.toSource());
				var new_fr=$('#HANDLE_CODE').val(); 
                                                                $('input#ed_to_city'+new_fr).val(datum.CityMixedCase);
                                                                $('input#ed_to_state'+new_fr).val(datum.State);
                                                                $('input#ed_to_zip'+new_fr).val(datum.ZipCode);
                                                });
			$('#ed_to_city'+handle_id).bind('typeahead:selected', function(obj, datum, name) {//alert(datum.toSource());
				var new_fr=$('#HANDLE_CODE').val(); 
                                                                $('input#ed_to_city'+new_fr).val(datum.CityMixedCase);
                                                                $('input#ed_to_state'+new_fr).val(datum.State);
                                                                $('input#ed_to_zip'+new_fr).val(datum.ZipCode);
                                                });
			var SHIPPER_NAME_clients = new Bloodhound({
			  name: 'SHIPPER_NAME',
			  remote: {
			  	url: 'exp_suggest_client.php?code=Vinegar&type=shipper&query=%QUERY',wildcard: '%QUERY'
				  },
			  datumTokenizer: Bloodhound.tokenizers.obj.whitespace('CLIENT_NAME'),
			  queryTokenizer: Bloodhound.tokenizers.whitespace
			});
						
			SHIPPER_NAME_clients.initialize();

			$('#ed_shipper_name'+handle_id).typeahead(null, {
			  name: 'SHIPPER_NAME',
			  minLength: 2,
			  limit: 10,
			  highlight: true,
			  display: 'CLIENT_NAME',
			  source: SHIPPER_NAME_clients,
			  templates: {
		      suggestion: Handlebars.compile(
			      '<p><strong>{{CLIENT_NAME}}</strong> – {{CONTACT_TYPE}}, {{CONTACT_NAME}}, {{PHONE_OFFICE}}</p>'
			    )
			  }
			});
			
			$('#ed_shipper_name'+handle_id).bind('typeahead:selected', function(obj, datum, name) {
					var new_fr=$('#HANDLE_CODE').val(); 
                                                                $('input#ed_from_city'+new_fr).val(datum.CITY);
                                                                $('input#ed_from_state'+new_fr).val(datum.STATE);
                                                                $('input#ed_from_zip'+new_fr).val(datum.ZIP_CODE);
                                                });
			var CONS_NAME_clients = new Bloodhound({
			  name: 'CONS_NAME',
			  remote: {
			  	url: 'exp_suggest_client.php?code=Vinegar&type=consignee&query=%QUERY',
			  	wildcard: '%QUERY'
				  },
			  datumTokenizer: Bloodhound.tokenizers.obj.whitespace('CLIENT_NAME'),
			  queryTokenizer: Bloodhound.tokenizers.whitespace

			});
						
			CONS_NAME_clients.initialize();

			$('#ed_Consignee_name'+handle_id).typeahead(null, {
			  name: 'CONS_NAME',
			  minLength: 2,
			  limit: 10,
			  highlight: true,
			  display: 'CLIENT_NAME',
			  source: CONS_NAME_clients,
			    templates: {
			    suggestion: Handlebars.compile(
			      '<p><strong>{{CLIENT_NAME}}</strong> – {{CONTACT_TYPE}}, {{CONTACT_NAME}}, {{PHONE_OFFICE}}</p>'
			    )
			  }
			});
			
		$('#ed_Consignee_name'+handle_id).bind('typeahead:selected', function(obj, datum, name) {
			var new_fr=$('#HANDLE_CODE').val(); 
                                                                $('input#ed_to_city'+new_fr).val(datum.CITY);
                                                                $('input#ed_to_state'+new_fr).val(datum.STATE);
                                                                $('input#ed_to_zip'+new_fr).val(datum.ZIP_CODE);
                                                });
												
	</script>
			
		</div>
		<div class='col-sm-5'>
		
		<div class='form-group'>
				<label class='col-sm-4 control-label' for='Line Haul'>Pallets</label>
				<div class='col-sm-8'>
					
			<input type='text'  id='ed_pallet".$chand['HANDLING_ID']."' name='ed_pallet' class='form-control text-right' value='".$chand['PALLET']."'>
				</div>
			</div>
			
			
		<div class='form-group'>
				<label class='col-sm-4 control-label' for='Line Haul'>Invoice</label>
				<div class='col-sm-8'>
					
			<input type='text'  id='ed_invoice".$chand['HANDLING_ID']."' name='ed_invoice' class='form-control text-right' value='".$chand['INVOICE_COST']."'>
				</div>
			</div>
		
		
			<div class='form-group'>
				<label class='col-sm-4 control-label' for='Invoice'>Driver Rate</label>
				<div class='col-sm-8'>
					
			
			<input type='text' name='ed_driverr_rate' id='ed_driverr_rate".$chand['HANDLING_ID']."'  class='form-control text-right' value='".$chand['DRIVER_COST']."'>
				
				</div>
			</div>
	
		</div>
	</div>
</form>
</div>
</div>
					</div>
				</div>
			  </td>
			  </tr>";
			}
		}else
		{
				$handling_result.="<tr style='cursor:pointer;' >
				<td style='text-align:center;' colspan='15'>NO CHARGES ARE AVAILABLE !</td>
			  </tr>";
		}
		
		$fr_rate=' <input type="hidden" value="" id="FREIGHT_CODE" name="FREIGHT_CODE">';
		if(count($edit['frieght_rate'])>0)
		{
			foreach($edit['frieght_rate'] as $fr)
			{
				$fr_rate.="<tr style='cursor:pointer;' >
				<td class='text-center' style='width:3%'><a onclick='javascript: return delete_client_frieght_rate(".$fr['FRIGHT_ID'].",".$edit['CODE'].",&quot;".$edit['USER_TYPE']."&quot;);' href='javascript:void(0);' class='btn btn-default btn-xs' data-original-title='' title=''> <span style='font-size: 14px;'><span class='glyphicon glyphicon-trash'></span> </span> </a><a  href='javascript:void(0);' class='btn btn-default btn-xs' data-remote='' data-target='#myModal_freight_".$fr['FRIGHT_ID']."' data-toggle='modal' onclick='javascript:return set_freight_id(".$fr['FRIGHT_ID']."); ' > <span style='font-size: 14px;'><span class='glyphicon glyphicon-edit'></span> </span> </a></td>
			  <td style='text-align:center;width:11%;' >".stripslashes($fr['SHIPPER_NAME'])."</td>
				<td style='text-align:center;width:8%;'>".$fr['ORIGIN_CITY']."</td>
				<td style='text-align:center;width:4%;'>".$fr['ORIGIN_STATE']."</td>
				<td style='text-align:center;width:5%;'>".$fr['ORIGIN_ZIP']."</td>
				  <td style='text-align:center;width:11%;'>".stripslashes($fr['CONSIGNEE_NAME'])."</td>
				 <td style='text-align:center;width:8%;'>".$fr['DESTINATION_CITY']."</td>
				 <td style='text-align:center;width:4%;'>".$fr['DESTINAION_STATE']."</td>
				 <td style='text-align:center;width:5%;'>".$fr['DESTINATION_ZIP']."</td>
				 <td style='text-align:center;width:5%;'>".$fr['LINE_HAUL']." - ".$fr['MIN_LINE_HAUL']."Min</td>
				  <td style='text-align:center;width:5%;'>".$fr['FSC']."</td>
				  <td style='text-align:center;width:5%;'>".$fr['MILEAGE']."</td>
				  <td style='text-align:center;width:5%;'>".$fr['WEEKEND_HOLIDAY']."</td>
				  <td style='text-align:center;width:5%;'>".$fr['ENROUTE_STOP_OFFS']."</td>
				   <td style='text-align:center;width:5%;'>".$fr['DETENTION']."</td>
				   <!--	<td style='text-align:center;'>".$fr['MILES']."</td>-->
					<td style='text-align:center;width:5%;'>".$fr['PER_MILE']."</td>
					<td style='text-align:center;width:5%;'>".$fr['AMOUNT']."</td>
			  </tr>
			  
			  <tr>
			  <td colspan='17'>
			
			  <div aria-hidden='false' aria-labelledby='myModal_freight_".$fr['FRIGHT_ID']."' role='dialog' tabindex='-1' class='modal fade in' id='myModal_freight_".$fr['FRIGHT_ID']."' >
					<div class='modal-dialog modal-lg'>
						<div role='main' class='modal-content form-horizontal'>	 

<div style='font-size: 14px; body:inherit;' class='modal-body'>
</form>
<form name='edit_frt_form_.".$fr['FRIGHT_ID']."' id='edit_frt_form_.".$fr['FRIGHT_ID']."' action='exp_addclientrate.php?TYPE=".$edit['USER_TYPE']."&CODE=".$edit['CODE']."' method='post'>
<h2>Edit Freight Charges
		<div class='btn-group'>
			<button type='submit' name='btn_edit_freight' class='btn btn-md btn-success' onclick='return chk_edit_freight_rates(".$fr['FRIGHT_ID'].");'><span class='glyphicon glyphicon-ok'></span> Save Changes</button><a data-dismiss='modal' class='btn btn-md btn-default'><span class='glyphicon glyphicon-remove'></span> Cancel</a>
			</div>
	</h2>
		<script type='text/javascript' language='javascript' >
		function chk_edit_freight_rates(fr_id)
{
	var freight_miles=document.getElementById('freight_miles'+fr_id);
	
	var origin_city=document.getElementById('ed_origin_city'+fr_id);
	var origin_state=document.getElementById('ed_origin_state'+fr_id);
	var origin_zip=document.getElementById('ed_origin_zip'+fr_id);
	var dest_city=document.getElementById('ed_dest_city'+fr_id);
	var dest_state=document.getElementById('ed_dest_state'+fr_id);
	var dest_zip=document.getElementById('ed_dest_zip'+fr_id);
	var line_haul=document.getElementById('ed_line_haul'+fr_id);
	var min_line_haul=document.getElementById('ed_min_line_haul'+fr_id);
	var fsc_rate=document.getElementById('ed_fsc_rate'+fr_id);
	var fsc_milleage=document.getElementById('ed_fsc_milleage'+fr_id);
	var weekend_hol=document.getElementById('ed_weekend_hol'+fr_id);
	var enroute=document.getElementById('ed_enroute'+fr_id);
	var detention=document.getElementById('ed_detention'+fr_id);
	var shipper_name=document.getElementById('ed_shipper'+fr_id);
	var CONS_NAME=document.getElementById('ed_Consignee'+fr_id);
	var per_miles=document.getElementById('ed_per_miles'+fr_id);
	var amount=document.getElementById('ed_amount'+fr_id);
	var amount=document.getElementById('ed_amount'+fr_id);
	
	/*if(shipper_name.value=='')
	{
		alert('Please enter shipper name.');
		return false;
	}
	else*/  if(origin_city.value=='')
	{
		alert('Please enter shipper city.');
		return false;
	}
	else  if(origin_state.value=='')
	{
		alert('Please enter shipper state.');
		return false;
	}
	else  if(origin_zip.value=='')
	{
		alert('Please enter shipper zipcode.');
		return false;
	}
	/*else  if(CONS_NAME.value=='')
	{
		alert('Please enter consignee name.');
		return false;
	}*/
	else  if(dest_city.value=='')
	{
		alert('Please enter consignee city.');
		return false;
	}
	else  if(dest_state.value=='')
	{
		alert('Please enter consignee state.');
		return false;
	}
	else  if(dest_zip.value=='')
	{
		alert('Please enter consignee zipcode.');
		return false;
	}
	/*else  if(line_haul.value=='')
	{
		alert('Please enter line haul rate.');
		return false;
	}*/
	else  if(line_haul.value!='' && isNaN(line_haul.value))
	{
		alert('Please enter line haul rate in numbers.');
		return false;
	}
	/*else  if(min_line_haul.value=='')
	{
		alert('Please enter minimum line haul rate.');
		return false;
	}*/
	else  if(min_line_haul.value!='' && isNaN(min_line_haul.value))
	{
		alert('Please enter minimum line haul rate in numbers.');
		return false;
	}
	/*else  if(fsc_rate.value=='')
	{
		alert('Please enter fsc.');
		return false;
	}
	else  if(fsc_milleage.value=='')
	{
		alert('Please enter milleage.');
		return false;
	}*/
	else  if(fsc_milleage.value!='' && isNaN(fsc_milleage.value))
	{
		alert('Please enter milleage in numbers.');
		return false;
	}
	else  if(weekend_hol.value!='' && isNaN(weekend_hol.value))
	{
		alert('Please enter weekend holiday numbers.');
		return false;
	}
	else  if(enroute.value!='' && isNaN(enroute.value))
	{
		alert('Please enter enroute in numbers.');
		return false;
	}
	else  if(detention.value!='' && isNaN(detention.value))
	{
		alert('Please enter detention in numbers.');
		return false;
	}
	/*else if(freight_miles.value=='')
	{
		alert('Please enter total miles.');
		return false;
	}
	else if(isNaN(freight_miles.value))
	{
		alert('Please enter  only numbers.');
		return false;
	}
	else if(per_miles.value=='')
	{
		alert('Please enter per mile.');
		return false;
	}*/
	else if(per_miles.value!='' && isNaN(per_miles.value))
	{
		alert('Please enter  per miles in  numbers.');
		return false;
	}
	/*else if(amount.value=='')
	{
		alert('Please enter total amount.');
		return false;
	}*/
	else if(amount.value!='' && isNaN(amount.value))
	{
		alert('Please enter  amount in  numbers.');
		return false;
	}
	else
	{
		return true;
	}
	
	
}

 function calculate_edit_frieght_amount(fr_id)
{
	var fsc_milleage=document.getElementById('ed_fsc_milleage'+fr_id).value;
	var per_miles=document.getElementById('ed_per_miles'+fr_id).value;
	if(fsc_milleage!='0.00' && per_miles!='0.00' &&  !isNaN(fsc_milleage) && !isNaN(per_miles))
	{
		var amnt=parseFloat(fsc_milleage)*parseFloat(per_miles);
		document.getElementById('ed_amount'+fr_id).value=amnt;
	}
	else
	{
		//document.getElementById('ed_amount'+fr_id).value='';
	}
}

		</script>
	
	<div class='form-group'>
		<div class='col-sm-5'>
			<div class='form-group'>
				<label class='col-sm-4 control-label' for='Shipper'>Shipper</label>
				<div class='col-sm-8'>
					
			".'		
			<input type="text" placeholder="Shipper" id="ed_shipper'.$fr['FRIGHT_ID'].'" name="ed_shipper" class="form-control text-right" value="'.stripslashes($fr['SHIPPER_NAME']).'">
            '."
			<input type='hidden' id='ed_frt_id".$fr['FRIGHT_ID']."' name='ed_frt_id' value='".$fr['FRIGHT_ID']."'/>
				</div>
			</div>
			<div class='form-group'>
				<label class='col-sm-4 control-label' for='City'>City</label>
				<div class='col-sm-8'>
					
			<input type='text' placeholder='Origin city' id='ed_origin_city".$fr['FRIGHT_ID']."' name='ed_origin_city' class='form-control text-right' value='".$fr['ORIGIN_CITY']."'>
				</div>
			</div>
			<div class='form-group'>
				<label class='col-sm-4 control-label' for='State'>State</label>
				<div class='col-sm-4'>
					
			<input type='text' placeholder='Origin State' id='ed_origin_state".$fr['FRIGHT_ID']."' name='ed_origin_state' class='form-control text-right' value='".$fr['ORIGIN_STATE']."'>
				</div>
				<div class='col-sm-4'>
			<input type='text' placeholder='Origin Zipcode' id='ed_origin_zip".$fr['FRIGHT_ID']."' name='ed_origin_zip' class='form-control text-right' value='".$fr['ORIGIN_ZIP']."'>
				</div>
			</div>
			<div class='form-group'>
				<label class='col-sm-4 control-label' for='Consignee'>Consignee</label>
				<div class='col-sm-8'>
			".'		
			<input type="text" placeholder="Consignee" id="ed_Consignee'.$fr['FRIGHT_ID'].'" name="ed_Consignee" class="form-control text-right" value="'.stripslashes($fr['CONSIGNEE_NAME']).'">
				</div>
			</div>
            '."
            
            	
			<div class='form-group'>
				<label class='col-sm-4 control-label' for='City'>City</label>
				<div class='col-sm-8'>
					
			<input type='text' placeholder='Destination city' id='ed_dest_city".$fr['FRIGHT_ID']."' name='ed_dest_city' class='form-control text-right' value='".$fr['DESTINATION_CITY']."'>
				</div>
			</div>
			<div class='form-group'>
				<label class='col-sm-4 control-label' for='State'>State</label>
				<div class='col-sm-4'>
					
			<input type='text' placeholder='Destination State' id='ed_dest_state".$fr['FRIGHT_ID']."' name='ed_dest_state' class='form-control text-right' value='".$fr['DESTINAION_STATE']."'>
				</div>
				<div class='col-sm-4'>
			<input type='text' placeholder='Zipcode' id='ed_dest_zip".$fr['FRIGHT_ID']."' name='ed_dest_zip' class='form-control text-right' value='".$fr['DESTINATION_ZIP']."'>
				</div>
			</div>
			
			<script type='text/javascript' language='javascript' >
	 
	var freight_id='".$fr['FRIGHT_ID']."';
 
	

	var ZIP_CODE_zips = new Bloodhound({
			  name: 'ZIP_CODE',
			  remote: {
			  	url: 'exp_suggest_zip.php?code=Balsamic&query=%QUERY',
			  	wildcard: '%QUERY'
				  },
			  datumTokenizer: Bloodhound.tokenizers.obj.whitespace('ZipCode'),
			  queryTokenizer: Bloodhound.tokenizers.whitespace

			});
			
			ZIP_CODE_zips.initialize();

			$('#ed_dest_zip'+freight_id).typeahead(null, {
			  name: 'ZIP_CODE',
			  minLength: 2,
			  limit: 10,
			  highlight: true,
			  display: 'ZipCode',
			  source: ZIP_CODE_zips,
			    templates: {
			    suggestion: Handlebars.compile(
			      '<p><strong>{{ZipCode}}</strong> – {{CityMixedCase}}, {{State}}</p>'
			    )
			  }
			});
		
			$('#ed_origin_zip'+freight_id).typeahead(null, {
			  name: 'ZIP_CODE',
			  minLength: 2,
			  highlight: true,
			  displayKey: 'ZipCode',
			  source: ZIP_CODE_zips.ttAdapter(),
			    templates: {
			    suggestion: Handlebars.compile(
			      '<p><strong>{{ZipCode}}</strong> – {{CityMixedCase}}, {{State}}</p>'
			    )
			  }
			});
			
			$('#ed_origin_city'+freight_id).typeahead(null, {
			  name: 'ZIP_CODE',
			  minLength: 2,
			  highlight: true,
			  displayKey: 'CityMixedCase',
			  source: ZIP_CODE_zips.ttAdapter(),
			    templates: {
			    suggestion: Handlebars.compile(
			      '<p><strong>{{CityMixedCase}}</strong> – {{State}}, {{ZipCode}}</p>'
			    )
			  }
			});
			
			$('#ed_dest_city'+freight_id).typeahead(null, {
			  name: 'ZIP_CODE',
			  minLength: 2,
			  highlight: true,
			  displayKey: 'CityMixedCase',
			  source: ZIP_CODE_zips.ttAdapter(),
			    templates: {
			    suggestion: Handlebars.compile(
			      '<p><strong>{{CityMixedCase}}</strong> – {{State}}, {{ZipCode}}</p>'
			    )
			  }
			});
			
			/* for origin freight charges*/
			$('#ed_origin_zip'+freight_id).bind('typeahead:selected', function(obj, datum, name) {
			 	var new_fr=$('#FREIGHT_CODE').val(); 
															   $('input#ed_origin_city'+new_fr).val(datum.CityMixedCase);
																$('input#ed_origin_state'+new_fr).val(datum.State);
                                                                $('input#ed_origin_zip'+new_fr).val(datum.ZipCode);
                                                });
			$('#ed_origin_city'+freight_id).bind('typeahead:selected', function(obj, datum, name) {//alert(datum.toSource());
				var new_fr=$('#FREIGHT_CODE').val(); 
                                                                $('input#ed_origin_city'+new_fr).val(datum.CityMixedCase);
                                                                $('input#ed_origin_state'+new_fr).val(datum.State);
                                                                $('input#ed_origin_zip'+new_fr).val(datum.ZipCode);
                                                });
												
			/* for destination freight charges*/
			$('#ed_dest_zip'+freight_id).bind('typeahead:selected', function(obj, datum, name) {//alert(datum.toSource());
				var new_fr=$('#FREIGHT_CODE').val(); 
                                                                $('input#ed_dest_city'+new_fr).val(datum.CityMixedCase);
                                                                $('input#ed_dest_state'+new_fr).val(datum.State);
                                                                $('input#ed_dest_zip'+new_fr).val(datum.ZipCode);
                                                });
			$('#ed_dest_city'+freight_id).bind('typeahead:selected', function(obj, datum, name) {//alert(datum.toSource());
				var new_fr=$('#FREIGHT_CODE').val(); 
                                                                $('input#ed_dest_city'+new_fr).val(datum.CityMixedCase);
                                                                $('input#ed_dest_state'+new_fr).val(datum.State);
                                                                $('input#ed_dest_zip'+new_fr).val(datum.ZipCode);
                                                });
			var SHIPPER_NAME_clients = new Bloodhound({
			  name: 'SHIPPER_NAME',
			  remote: {
			  	url: 'exp_suggest_client.php?code=Vinegar&type=shipper&query=%QUERY',
			  	wildcard: '%QUERY'
				  },
			  datumTokenizer: Bloodhound.tokenizers.obj.whitespace('CLIENT_NAME'),
			  queryTokenizer: Bloodhound.tokenizers.whitespace

			});
						
			SHIPPER_NAME_clients.initialize();

			$('#ed_shipper'+freight_id).typeahead(null, {
			  name: 'SHIPPER_NAME',
			  minLength: 2,
			  limit: 10,
			  highlight: true,
			  display: 'CLIENT_NAME',
			  source: SHIPPER_NAME_clients,
			  templates: {
		      suggestion: Handlebars.compile(
			      '<p><strong>{{CLIENT_NAME}}</strong> – {{CONTACT_TYPE}}, {{CONTACT_NAME}}, {{PHONE_OFFICE}}</p>'
			    )
			  }
			});
			
			$('#ed_shipper'+freight_id).bind('typeahead:selected', function(obj, datum, name) {
					var new_fr=$('#FREIGHT_CODE').val(); 
                                                                $('input#ed_origin_city'+new_fr).val(datum.CITY);
                                                                $('input#ed_origin_state'+new_fr).val(datum.STATE);
                                                                $('input#ed_origin_zip'+new_fr).val(datum.ZIP_CODE);
                                                });
			var CONS_NAME_clients = new Bloodhound({
			  name: 'CONS_NAME',
			  remote: {
			  	url: 'exp_suggest_client.php?code=Vinegar&type=consignee&query=%QUERY',
			  	wildcard: '%QUERY'
				  },
			  datumTokenizer: Bloodhound.tokenizers.obj.whitespace('CLIENT_NAME'),
			  queryTokenizer: Bloodhound.tokenizers.whitespace

			});
						
			CONS_NAME_clients.initialize();

			$('#ed_Consignee'+freight_id).typeahead(null, {
			  name: 'CONS_NAME',
			  minLength: 2,
			  limit: 10,
			  highlight: true,
			  display: 'CLIENT_NAME',
			  source: CONS_NAME_clients,
			    templates: {
			    suggestion: Handlebars.compile(
			      '<p><strong>{{CLIENT_NAME}}</strong> – {{CONTACT_TYPE}}, {{CONTACT_NAME}}, {{PHONE_OFFICE}}</p>'
			    )
			  }
			});
			
		$('#ed_Consignee'+freight_id).bind('typeahead:selected', function(obj, datum, name) {
			var new_fr=$('#FREIGHT_CODE').val(); 
                                                                $('input#ed_dest_city'+new_fr).val(datum.CITY);
                                                                $('input#ed_dest_state'+new_fr).val(datum.STATE);
                                                                $('input#ed_dest_zip'+new_fr).val(datum.ZIP_CODE);
                                                });
												
	</script>
			<div class='form-group'>
				<label class='col-sm-4 control-label' for='Line Haul'>Line Haul</label>
				<div class='col-sm-8'>
					
			<input type='text'  id='ed_line_haul".$fr['FRIGHT_ID']."' name='ed_line_haul' class='form-control text-right' value='".$fr['LINE_HAUL']."'>
				</div>
			</div>
		</div>
		<div class='col-sm-5'>
			<div class='form-group'>
				<label class='col-sm-4 control-label' for='min_line_haul'>Min Line Haul</label>
				<div class='col-sm-8'>
					
			
			<input type='text' name='ed_min_line_haul' id='ed_min_line_haul".$fr['FRIGHT_ID']."'  class='form-control' value='".$fr['MIN_LINE_HAUL']."'>
				
				</div>
			</div>
			<div class='form-group'>
				<label class='col-sm-4 control-label' for='FSC'>FSC</label>
				<div class='col-sm-8'>
					
			
			<input type='text'  name='ed_fsc_rate' id='ed_fsc_rate".$fr['FRIGHT_ID']."'  value='".$fr['FSC']."' class='form-control' >
				
				</div>
			</div>
			<div class='form-group'>
				<label class='col-sm-4 control-label' for='Milleage'>Milleage</label>
				<div class='col-sm-8'>
					
			<input type='text'  id='ed_fsc_milleage".$fr['FRIGHT_ID']."' name='ed_fsc_milleage' class='form-control' value='".$fr['MILEAGE']."' onkeyup='return calculate_edit_frieght_amount(".$fr['FRIGHT_ID'].");'>
				</div>
			</div>
			<div class='form-group'>
				<label class='col-sm-4 control-label' for='Weekend Holiday'>Weekend Holiday</label>
				<div class='col-sm-8'>
				<input type='text'  id='ed_weekend_hol".$fr['FRIGHT_ID']."' name='ed_weekend_hol' class='form-control' value='".$fr['WEEKEND_HOLIDAY']."'>
				</div>
			</div>
            
            <div class='form-group'>
				<label class='col-sm-4 control-label' for='Enroute Stop Offs'>Enroute Stop Offs</label>
				<div class='col-sm-8'>
					
			<input type='text'  id='ed_enroute".$fr['FRIGHT_ID']."' name='ed_enroute' class='form-control' value='".$fr['ENROUTE_STOP_OFFS']."'>
				</div>
			</div>
            
            <div class='form-group'>
				<label class='col-sm-4 control-label' for='Detention'>Detention</label>
				<div class='col-sm-8'>
					
			<input type='text'  id='ed_detention".$fr['FRIGHT_ID']."' name='ed_detention' class='form-control' value='".$fr['DETENTION']."'>
				</div>
			</div>
            
            <div class='form-group'>
				<label class='col-sm-4 control-label' for='Per Mile'>Per Mile</label>
				<div class='col-sm-8'>
					
			<input type='text'  id='ed_per_miles".$fr['FRIGHT_ID']."' name='ed_per_miles' class='form-control' value='".$fr['PER_MILE']."' onkeyup='return calculate_edit_frieght_amount(".$fr['FRIGHT_ID'].");'>
				</div>
			</div>
            
            <div class='form-group'>
				<label class='col-sm-4 control-label' for='Amount'>Amount</label>
				<div class='col-sm-8'>
					
			<input type='text'  id='ed_amount".$fr['FRIGHT_ID']."' name='ed_amount' class='form-control' value='".$fr['AMOUNT']."'>
				</div>
			</div>
		</div>
	</div>
</form>
</div>
</div>
					</div>
				</div>
			  </td>
			  </tr>";
			}
		}
		else
		{
			$fr_rate.="<tr style='cursor:pointer;' >
				<td style='text-align:center;' colspan='18'>NO FRIEGHT RATES ARE AVAILABLE !</td>
			  </tr>";
		}
		
		$client_rate='';
		if(count($edit['client_rate'])>0)
		{//print_r($edit['client_rate']);
		$client_rate.="<script language='JavaScript' type='text/javascript'>
		
		$(document).ready( function () {
		
			$('#table_client_rate').dataTable({
		        
		        'bFilter': false,
		        'bSort': true,
		        'bInfo': false,
				
				'sScrollY': (200) + 'px',
			    'bPaginate': false,
				'bScrollCollapse': true,
				'bSortClasses': false	,
				'columnDefs': [ { 'targets': 0, 'orderable': false } ]
				
			});
			
			if( window.HANDLE_RESIZE_EVENTS ) {
				$(window).bind('resize', function(e) {
					console.log('resize event triggered');
					if (window.RT) clearTimeout(window.RT);
					window.RT = setTimeout(function() {
						this.location.reload(false); /* false to get page from cache */
					}, 100);
				});
			}
		});
		</script>";
			foreach($edit['client_rate']  as $cr)
			{
				$client_rate.=" <tr style='cursor:pointer;'>
			<td class='text-center'><a onclick='javascript: return delete_client_assigned_rate(".$cr['ASSIGN_ID'].",".$edit['CODE'].",&quot;".$edit['USER_TYPE']."&quot;);' href='javascript:void(0);' class='btn btn-default btn-xs' data-original-title='' title=''> <span style='font-size: 14px;'><span class='glyphicon glyphicon-trash'></span> </span> </a></td>
				 <td class='text-center'>".$cr['RATE_CODE']."</td>
				  <td class='text-center'>".$cr['RATE_NAME']."</td>
                <td class='text-center'>".$cr['CATEGORY_NAME']."</td>
				<td class='text-center'>".$cr['RATE_PER_MILES']."</td>
				<td class='text-center'>".((float)$cr['RATE_PER_MILES']/35)."</td>
				</tr>";
			}
		}
		else
		{
				$client_rate.="<tr style='cursor:pointer;'>
			<td class='text-center' colspan='5'>NO RATES ARE ASSIGNED ! </td>
			
						</tr>";
		}
		
		$my_fsc="";
		if(count($edit['fsc_client_schedule'])>0)
		{$my_fsc.="<script language='JavaScript' type='text/javascript'><!--
		
		$(document).ready( function () {
		
			$('#table_FSC').dataTable({
		        
		        'bFilter': false,
		        //'bSort': true,
		        'bInfo': false,
				
				'sScrollY': (200) + 'px',
			    'bPaginate': false,
				'bScrollCollapse': false,
				'bSortClasses': true,
				'columnDefs': [ { 'targets': 0, 'orderable': false } ]	
			});
			
			if( window.HANDLE_RESIZE_EVENTS ) {
				$(window).bind('resize', function(e) {
					console.log('resize event triggered');
					if (window.RT) clearTimeout(window.RT);
					window.RT = setTimeout(function() {
						this.location.reload(false); /* false to get page from cache */
					}, 100);
				});
			}
		});
		</script>";
			foreach($edit['fsc_client_schedule'] as $fsc)
			{
			/* $my_fsc.="<tr style='cursor:pointer;'>
			<td class='text-center'><a onclick='javascript: return delete_client_assigned_schedule(".$fsc['ASSIGN_FSC_ID'].",".$edit['CODE'].");' href='javascript:void(0);' class='btn btn-default btn-xs' data-original-title='' title=''> <span style='font-size: 14px;'><span class='glyphicon glyphicon-trash'></span> </span> </a></td>
				 <td class='text-center'>$".$fsc['LOW_PER_GALLON']."</td>
                <td class='text-center'>$".$fsc['HIGH_PER_GALLON']."</td>
				<td class='text-center'>$".$fsc['PER_MILE_ADJUST']."</td>
				</tr>";*/
				$row="";
				if($fsc['FSC_COLUMN']!="" && $fsc['FSC_AVERAGE_RATE']!="")
				{$row="".$fsc['FSC_COLUMN']."-".$fsc['FSC_AVERAGE_RATE']."";}
				
				$my_fsc.="<tr style='cursor:pointer;'>
				       <td class='text-center'><a onclick='javascript: return delete_client_assigned_schedule(".$fsc['ASSIGN_FSC_ID'].",".$edit['CODE'].",&quot;".$edit['USER_TYPE']."&quot;);' href='javascript:void(0);' class='btn btn-default btn-xs' data-original-title='' title=''> <span style='font-size: 14px;'><span class='glyphicon glyphicon-trash'></span> </span> </a></td>
						<td style='text-align:center;'># ".$fsc['FSC_UNIQUE_ID']."</td>
						<td style='text-align:center;'>".$fsc['FSC_NAME']."</td>
						<td style='text-align:center;'>$".$fsc['WEEKLY_FSC_CHARGE']."</td>
						<td style='text-align:center;'>$".$fsc['AVERAGE_PRICE']."</td>
							<td style='text-align:center;'>".$row."</td>
						
						 <td style='text-align:center;'>".date('Y-m-d',strtotime($fsc['STARTING_DATE']))."</td>
						  <td style='text-align:center;'>".date('Y-m-d',strtotime($fsc['FSC_ENDING_DATE']))."</td>
						 </tr>";
			}
		}
		else
		{
			$my_fsc.="<tr style='cursor:pointer;'>
			<td class='text-center' colspan='8'>NO FSC SCHEDULE IS ASSIGNED ! 
			</td></tr>";
		}
		
		$fsc="";
		if(count($edit['fsc_schedule'])>0)
		{ 
			foreach($edit['fsc_schedule'] as $fs)
			{
				/*$fsc.="<tr style='cursor:pointer;' >
									<td class='text-center'><input type='checkbox'  name='fsc_ids[]' value=".$fs['FSC_ID']."></td>
									<td style='text-align:center;'>$".$fs['LOW_PER_GALLON']."</td>
									<td style='text-align:center;'>$".$fs['HIGH_PER_GALLON']."</td>
									<td style='text-align:center;'>$".$fs['PER_MILE_ADJUST']."</td>
									 <td style='text-align:center;'>".date('Y-m-d',strtotime($fs['START_DATE']))."</td>
									  <td style='text-align:center;'>".date('Y-m-d',strtotime($fs['END_DATE']))."</td>
								  </tr>";*/
								  $nrow="";
				if($fs['FSC_COLUMN']!="" && $fs['FSC_AVERAGE_RATE']!="")
				{$nrow="".$fs['FSC_COLUMN']."-".$fs['FSC_AVERAGE_RATE']."";}
				$fsc.="<tr style='cursor:pointer;' >
						<td class='text-center'><input type='checkbox'  name='fsc_ids[]' value=".$fs['FSC_UNIQUE_ID']."></td>
						<td style='text-align:center;'>#".$fs['FSC_UNIQUE_ID']."</td>
						<td style='text-align:center;'>".$fs['FSC_NAME']."</td>
						<td style='text-align:center;'>$".$fs['AVERAGE_PRICE']."</td>
						<td style='text-align:center;'>".$nrow."</td>
						<td style='text-align:center;'>$".$fs['WEEKLY_FSC_CHARGE']."</td>
						 <td style='text-align:center;'>".date('Y-m-d',strtotime($fs['STARTING_DATE']))."</td>
						 <td style='text-align:center;'>".date('Y-m-d',strtotime($fs['FSC_ENDING_DATE']))."</td>
						</tr>";	  
			}
		}
		else
		{
			$fsc="";
		}
		$for_loop="";
		for($i=01;$i<=10;$i++)
		{
			$for_loop.='<option value='.$i.'>'.$i.'</option>';
		}
		$loop="";
		for($i=00;$i<=24;$i++)
		{
			$loop.='<option value='.$i.'>'.$i.'</option>';
		}
		$add_detention_div='';
		if(count($edit['detention_rate'])==0)
		{
		 $add_detention_div="<table width='100%' border='0' cellspacing='0' cellpadding='0' style='margin:5px;' class='table table-striped table-condensed table-bordered table-hover' >
                  <tr>
                    <td style='font-size:12px; font-family:Arial, Helvetica, sans-serif; text-transform:uppercase; padding:1 6px;'>Client Name</td>
                    <td><input type='text' name='det_customer_name' id='det_customer_name' size='15' class='form-control' value=\"".($edit['client_name']['CNAME'])."\" readonly ></td>
                   <!-- <td style='font-size:12px; font-family:Arial, Helvetica, sans-serif; text-transform:uppercase; padding:1 6px;'>City</td>
                    <td><input type='text' name='det_customer_city' id='det_customer_city' size='15' class='form-control' value='' ></td>
                    <td style='font-size:12px; font-family:Arial, Helvetica, sans-serif; text-transform:uppercase; padding:1 6px;'>State</td>
                    <td><input type='text' name='det_customer_state' id='det_customer_state' size='15' class='form-control' value='' maxlength='2'></td>
					 <td style='font-size:12px; font-family:Arial, Helvetica, sans-serif; text-transform:uppercase; padding:1 6px;'>Zipcode</td>
                    <td><input type='text' name='det_customer_zip' id='det_customer_zip' size='15' class='form-control' value='' ></td>-->
					<td style='font-size:12px; font-family:Arial, Helvetica, sans-serif; text-transform:uppercase; padding:1 6px;'>Min  Charge</td>
                    <td><input type='text' name='det_min_charge' id='det_min_charge' size='15' class='form-control' value='' ></td>
					<td style='font-size:12px; font-family:Arial, Helvetica, sans-serif; text-transform:uppercase; padding:1 6px;'>Max Charge</td>
                    <td><input type='text' name='det_max_charge' id='det_max_charge' size='15' class='form-control' value='' ></td>
					 <td><input name='save_detention_rate' id='save_detention_rate' type='submit' class='btn btn-md btn-default' value='Submit' onclick='javascript:return chk_detention_rate();'></td>
					</tr>
				</table>";
		}
		else
		{
			$add_detention_div="<table width='100%' border='0' cellspacing='0' cellpadding='0' style='margin:5px;display:none;' class='table table-striped table-condensed table-bordered table-hover' id='add_detention'>
                  <tr>
                    <td style='font-size:12px; font-family:Arial, Helvetica, sans-serif; text-transform:uppercase; padding:1 6px;'>Client Name</td>
                    <td><input type='text' name='det_customer_name' id='det_customer_name' size='15' class='form-control' value=\"".$edit['client_name']['CNAME']."\" readonly ></td>
					<td style='font-size:12px; font-family:Arial, Helvetica, sans-serif; text-transform:uppercase; padding:1 6px;'>Min  Charge</td>
                    <td><input type='text' name='det_min_charge' id='det_min_charge' size='15' class='form-control' value='".$edit['detention_rate'][0]['DET_MIN_CHARGE']."' ></td>
					<td style='font-size:12px; font-family:Arial, Helvetica, sans-serif; text-transform:uppercase; padding:1 6px;'>Max Charge</td>
                    <td><input type='text' name='det_max_charge' id='det_max_charge' size='15' class='form-control' value='".$edit['detention_rate'][0]['DET_MAX_CHARGE']."' ><input type='hidden' name='det_id' id='det_id' size='15' class='form-control' value='".$edit['detention_rate'][0]['DETENTION_ID']."' ></td>
					 <td><input name='update_detention_rate' id='update_detention_rate' type='submit' class='btn btn-md btn-default' value='Update' onclick='javascript:return chk_detention_rate();'></td>
					</tr>
				</table>";
		}
		
		$str="";
		//$name=($edit['client_name']['CNAME']);
		$str.="
<div class='container-full theme-showcase' role='main'>
				  <div class='well  well-md'>
					<form role='form' class='form-horizontal' action='exp_addclientrate.php?TYPE=".$edit['USER_TYPE']."&CODE=".$edit['CODE']."' method='post'  name='addclientrate' id='addclientrate' enctype='multipart/form-data'>
					<h2><img src='images/driver_icon.png' alt='driver_icon' height='24'> ".$edit['act']." ".ucfirst($edit['USER_TYPE'])." Rate
					
					<button class='btn btn-md btn-success' name='add_rate' id='add_rate' type='submit' onclick='return chk_add_rates();'><span class='glyphicon glyphicon-ok'></span> Save Changes</button> <a class='btn btn-md btn-info' href='exp_editclient.php?CODE=".$edit['CODE']."'><span class='glyphicon glyphicon-edit'></span> Client</a>
					<a class='btn btn-md btn-default' href='exp_list".$edit['USER_TYPE'].".php'><span class='glyphicon glyphicon-remove'></span> Cancel</a>
					
	</h2>
	
	<div class='form-group'>
		<div class='col-sm-4'>
		<div class='form-group'>&nbsp;</div>
			<div class='form-group'>
				<label for='CLIENT_NAME' class='col-sm-4 control-label'>".ucfirst($edit['USER_TYPE'])." Name</label>
				<div class='col-sm-8'>
				<input class='form-control' name='CLIENT_NAME' id='CLIENT_NAME' type='text'  placeholder='".ucfirst($edit['USER_TYPE'])." Name' 
				value=\"".$edit['client_name']['CNAME']."\" readonly='readonly'>
				</div>
			</div>
			
			<div class='form-group'>
				<label for='RATE_PER_MILE' class='col-sm-4 control-label'>Rate Per Mile</label>
				<div class='col-sm-8'>
				<div class=\"input-group\">
				<span class=\"input-group-addon\">$</span>
			  <input class='form-control text-right' name='RATE_PER_MILE' id='RATE_PER_MILE' type='text'  placeholder='Rate Per Mile'  value=".$edit['rpm'].">
				</div>
				</div>
			</div>
			<div class='form-group'>
				<label for='SO_RATE' class='col-sm-4 control-label'>S/O Rate</label>
				<div class='col-sm-8'>
				<div class=\"input-group\">
				<span class=\"input-group-addon\">$</span>
			   <input class='form-control text-right' name='SO_RATE' id='SO_RATE' type='text'  placeholder='S/O Rate' value=".$edit['srate']." >
				</div>
				</div>
			</div>
			
			
            
           <!-- <div class='form-group'>
				<label for='FUEL_COST' class='col-sm-4 control-label'>Fuel Cost</label>
				<div class='col-sm-8'>
			 <input class='form-control' name='FUEL_COST' id='FUEL_COST' type='text' placeholder='Fuel Cost' value=".$edit['fuel']." >
				</div>
			</div>-->
            
            <div class='form-group'>
				<label for='VENDOR_CODE' class='col-sm-4 control-label'>Vendor Code</label>
				<div class='col-sm-8'>
				<input class='form-control' name='VENDOR_CODE' id='VENDOR_CODE' type='text'  placeholder='Vendor Code'>
				</div>
			</div>
            
         
             <div class='form-group'>
				<label for='OTHER' class='col-sm-4 control-label'>Other</label>
              </div>
              <div class='form-group'>
				<div class='col-sm-5'></div>
				<div class='col-sm-1'>
				<input name='HAZMAT' id='HAZMAT' value='1' type='checkbox'  $check>
                </div>
              <label for='HAZMAT' class='col-sm-3 control-label' style='text-align:left;'>Hazmat Only  </label>
                </div>
              <div class='form-group'>
				<div class='col-sm-5'></div>
              <div class='col-sm-1'>
				<input name='TEMP_CONTROLL' id='TEMP_CONTROLL'  value='1'  type='checkbox' ".$check1.">
                </div> 
              <label for='TEMP_CONTROLL' class='col-sm-5 control-label' style='text-align:left;'>Temp Controlled Only   </label>
			</div>
		</div>
		
 	<div class='col-sm-8'>
	 <div class='form-group'>
				<label for='CLIENT_RATES' class='col-sm-4 control-label'>".ucfirst($edit['USER_TYPE'])." Rates</label>
		</div>
	<style>.no-sort::after { display: none!important; }

.no-sort { pointer-events: none!important; cursor: default!important; }</style>
			<div class='form-group' >
			<div id='reload_client_result' style='display:none;'><img src='images/loading.gif' border='1' style='position:absolute; margin-left:250px;margin-top:10px;'></div>
			<div style='height:300px;overflow:auto;' class='table-responsive'>
            <table class='display table table-striped table-condensed table-bordered table-hover' id='table_client_rate' >
            <thead>
              <tr class='exspeedite-bg'>
                 <th class='text-center no-sort'><span class='text-muted'><span class='glyphicon glyphicon-unchecked'></span></span></th>
				 <th class='text-center'>Rate Code</th>
				 <th class='text-center'>Rate Name</th>
                <th class='text-center'>Category</th>
				<th class='text-center'>Rate</th>
				<th class='text-center'>QTY</th>
				
              </tr>
            </thead>
            <tbody>
		  $client_rate
			</tbody>
          </table>
		  </div>
        </div>
		</div>
		
		<div class='col-sm-12'>
	 <div class='form-group'>
				<label for='CLIENT_RATES' class='col-sm-4 control-label'>FSC #</label>
		</div>
	
			<div class='form-group' >
			<div id='reload_fsc_result' style='display:none;'><img src='images/loading.gif' border='1' style='position:absolute; margin-left:250px;margin-top:10px;'></div>
			<div  class='table-responsive' >
            <table class='display table table-striped table-condensed table-bordered table-hover' id='table_FSC'  >
            <thead>
              <tr class='exspeedite-bg'>
                 <th class='text-center no-sort'><span class='text-muted'><span class='glyphicon glyphicon-unchecked'></span></span></th>
				 <th class='text-center'>FSC ID</th>
				 <th class='text-center'>FSC NAME</th>
				  <th class='text-center'>WEEKLY FSC CHARGE</th>
                <th class='text-center'>Average Price</th>
				<th class='text-center'>Average Rate</th>
				<th class='text-center'>Starting Date</th>
				<th class='text-center'>End Date</th>
              </tr>
            </thead>
            <tbody>
			
		 $my_fsc
		
			</tbody>
          </table>
		   </div>
        </div>
		</div>
        
		<div class='col-sm-12'>
			
			<!--<div class='form-group'>
				<label for='PALLET_RATES' class='col-sm-4 control-label'>Pallet Rates</label>
				<div class='col-sm-8'>
				<input class='' name='PALLET_RATES' id='PALLET_RATES' type='file' />
				</div>
			</div>-->
			
			<div class='form-group'>
			<label for='PALLET_RATES' class='col-sm-4 control-label'>Pallet Rates</label>
				<div class='col-sm-8'>
				
				<!--<input  class='btn btn-sm btn-success' name='UPLOAD_PALLETS' id='UPLOAD_PALLETS' type='submit' value='Upload Pallet Rates' onclick='return check_uploaded_file(PALLET_RATES);' />-->
				 <a class='btn btn-sm btn-success' href='javascript:void(0);' onclick='javascript: return show_hide_div(&quot;add_pallet&quot;);'><span class='glyphicon glyphicon-plus'></span> Add Pallet Rates</a>
				</div>
			</div>
            
          <div class='form-group' >
		  <div id='reload_handling_result' style='display:none;'><img src='images/loading.gif' border='1' style='position:absolute; margin-left:250px;margin-top:10px;'></div>
		  <table width='100%' border='0' cellspacing='0' cellpadding='0' style='margin:5px;display:none;' class='table table-striped table-condensed table-bordered table-hover' id='add_pallet'>
                  <tr>
                    <td style='font-size:12px; font-family:Arial, Helvetica, sans-serif; text-transform:uppercase; padding:1 6px;'>Consignee Name</td>
                    <td><input type='text' name='customer_name' id='customer_name' size='15' class='form-control' value='' ></td>
                    <td style='font-size:12px; font-family:Arial, Helvetica, sans-serif; text-transform:uppercase; padding:1 6px;'>City</td>
                    <td><input type='text' name='customer_city' id='customer_city' size='15' class='form-control' value='' ></td>
                    <td style='font-size:12px; font-family:Arial, Helvetica, sans-serif; text-transform:uppercase; padding:1 6px;'>State</td>
                    <td><input type='text' name='customer_state' id='customer_state' size='15' class='form-control' value='' maxlength='2'></td>
					 <td style='font-size:12px; font-family:Arial, Helvetica, sans-serif; text-transform:uppercase; padding:1 6px;'>Zipcode</td>
                    <td><input type='text' name='customer_zip' id='customer_zip' size='15' class='form-control' value='' ></td>
					<td style='font-size:12px; font-family:Arial, Helvetica, sans-serif; text-transform:uppercase; padding:1 6px;'>Min Charge</td>
                    <td><input type='text' name='min_charge' id='min_charge' size='15' class='form-control' value='' ></td>
					<td style='font-size:12px; font-family:Arial, Helvetica, sans-serif; text-transform:uppercase; padding:1 6px;'>Max Charge</td>
                    <td><input type='text' name='max_charge' id='max_charge' size='15' class='form-control' value='' ></td>
					 <td><input name='save_pallet_rate' id='save_pallet_rate' type='submit' class='btn btn-md btn-default' value='Submit' onclick='javascript:return chk_pallet_charge();'></td>
					</tr>
					 <tr>
                   
                    <!--<td style='font-size:12px; font-family:Arial, Helvetica, sans-serif; text-transform:uppercase; padding:1 6px;'>1-4 Pallet Rates</td>
                    <td><input type='text' name='first_pallet_rate' id='first_pallet_rate' size='15' class='form-control' value='' ></td>
                    <td style='font-size:12px; font-family:Arial, Helvetica, sans-serif; text-transform:uppercase; padding:1 6px;'>5-8 Pallet Rates</td>
                    <td><input type='text' name='next_pallet_rate' id='next_pallet_rate' size='15' class='form-control' value='' ></td>
					<td style='font-size:12px; font-family:Arial, Helvetica, sans-serif; text-transform:uppercase; padding:1 6px;'>9+ Pallet Rates</td>
                    <td><input type='text' name='last_pallet_rate' id='last_pallet_rate' size='15' class='form-control' value='' ></td>
                    <td style='font-size:12px; font-family:Arial, Helvetica, sans-serif; text-transform:uppercase; padding:1 6px;'>Max</td>
                    <td><input type='text' name='max_rate' id='max_rate' size='15' class='form-control' value='' ></td>-->
					
					</tr>
					
					
                </table>
				
		  <div  class='table-responsive' >
		  <div  style='position:absolute;margin-top:12px;margin-left:600px;display:none;' id='pallet_loader'><img src='images/loading.gif' /></div>
            <table class='display table table-striped table-condensed table-bordered table-hover' id='table_pallet' >
            <thead >
              <tr class='exspeedite-bg'>
			     <th class='text-center' style='width:34.35%'><span class='text-muted'><span class='glyphicon glyphicon-unchecked'></span></span></th>
			    <th class='text-center' style='width:18%'>
			    	<span style='float:left;'>CONSIGNEE NAME</span> 
					<span style='float:left; width:5px; margin-top:-6px;'>
                		<a href='exp_addclientrate.php?TYPE=".$edit['USER_TYPE']."&CODE=".$edit['CODE']."&SORT_BY=pal_asc&COL=CUST_NAME'><img src='./media/images/sort_asc_disabled1.png'/></a>
                		<a href='exp_addclientrate.php?TYPE=".$edit['USER_TYPE']."&CODE=".$edit['CODE']."&SORT_BY=pal_desc&COL=CUST_NAME'><img style='margin-top:-21px;' src='./media/images/sort_desc_disabled1.png'/></a>
                	</span>
                </th>
                <th class='text-center' style='width:13%'> 
                	<span style='float:left;'>CITY</span> 
					<span style='float:left; width:5px; margin-top:-6px;'>
                		<a href='exp_addclientrate.php?TYPE=".$edit['USER_TYPE']."&CODE=".$edit['CODE']."&SORT_BY=pal_asc&COL=CUST_CITY'><img src='./media/images/sort_asc_disabled1.png'/></a>
                		<a href='exp_addclientrate.php?TYPE=".$edit['USER_TYPE']."&CODE=".$edit['CODE']."&SORT_BY=pal_desc&COL=CUST_CITY'><img style='margin-top:-21px;' src='./media/images/sort_desc_disabled1.png'/></a>
                	</span>
                	</th>
				 <th class='text-center' style='width:8%'> 
				 	<span style='float:left;'>STATE</span> 
					<span style='float:left; width:5px; margin-top:-6px;'>
                		<a href='exp_addclientrate.php?TYPE=".$edit['USER_TYPE']."&CODE=".$edit['CODE']."&SORT_BY=pal_asc&COL=CUST_STATE'><img src='./media/images/sort_asc_disabled1.png'/></a>
                		<a href='exp_addclientrate.php?TYPE=".$edit['USER_TYPE']."&CODE=".$edit['CODE']."&SORT_BY=pal_desc&COL=CUST_STATE'><img style='margin-top:-21px;' src='./media/images/sort_desc_disabled1.png'/></a>
                	</span>
				 </th>
				 <th class='text-center' style='width:8%'> 
				 <span style='float:left;'>ZIP</span> 
					<span style='float:left; width:5px; margin-top:-6px;'>
                		<a href='exp_addclientrate.php?TYPE=".$edit['USER_TYPE']."&CODE=".$edit['CODE']."&SORT_BY=pal_asc&COL=CUST_ZIP'><img src='./media/images/sort_asc_disabled1.png'/></a>
                		<a href='exp_addclientrate.php?TYPE=".$edit['USER_TYPE']."&CODE=".$edit['CODE']."&SORT_BY=pal_desc&COL=CUST_ZIP'><img style='margin-top:-21px;' src='./media/images/sort_desc_disabled1.png'/></a>
                	</span></th>
				 <th class='text-center' style='width:8%'> 
				 	<span style='float:left;'>MIN CHARGE</span> 
					<span style='float:left; width:5px; margin-top:-6px;'>
                		<a href='exp_addclientrate.php?TYPE=".$edit['USER_TYPE']."&CODE=".$edit['CODE']."&SORT_BY=pal_asc&COL=MIN_CHARGE'><img src='./media/images/sort_asc_disabled1.png'/></a>
                		<a href='exp_addclientrate.php?TYPE=".$edit['USER_TYPE']."&CODE=".$edit['CODE']."&SORT_BY=pal_desc&COL=MIN_CHARGE'><img style='margin-top:-21px;' src='./media/images/sort_desc_disabled1.png'/></a>
                	</span></th>
				 <th class='text-center' style='width:8%'> 
				 	<span style='float:left;'>MAX CHARGE</span> 
					<span style='float:left; width:5px; margin-top:-6px;'>
                		<a href='exp_addclientrate.php?TYPE=".$edit['USER_TYPE']."&CODE=".$edit['CODE']."&SORT_BY=pal_asc&COL=MAX_CHARGE'><img src='./media/images/sort_asc_disabled1.png'/></a>
                		<a href='exp_addclientrate.php?TYPE=".$edit['USER_TYPE']."&CODE=".$edit['CODE']."&SORT_BY=pal_desc&COL=MAX_CHARGE'><img style='margin-top:-21px;' src='./media/images/sort_desc_disabled1.png'/></a>
                	</span>
                </th>
				 <!--<th class='text-center'>1-4 PALLET RATES</th>
				 <th class='text-center'> 5-8 PALLET RATES</th>
				 <th class='text-center'> 9+ PALLET RATES</th>
				<th class='text-center'> MAX</th>-->
			 </tr>
            </thead>
			  </table>
			     </div>
				  <div  class='table-responsive' style='max-height:400px;overflow:auto;'>
			   <table class='display table table-striped table-condensed table-bordered table-hover' id='table_pallet' >
            <tbody >
			".$pall_res."
			</tbody>
          </table>
        </div>
		</div>
		
       </div>
	   
	   <div class='col-sm-12'>
			
			<!--<div class='form-group'>
				<label for='PALLET_RATES' class='col-sm-4 control-label'> Detention Charges</label>
				<div class='col-sm-8'>
				<input class='' name='PALLET_RATES' id='PALLET_RATES' type='file' />
				</div>
			</div>-->
			
			<div class='form-group'>
			<label for='PALLET_RATES' class='col-sm-4 control-label'>  Detention Charges</label>
				<div class='col-sm-8'>
				
				<!--<input  class='btn btn-sm btn-success' name='UPLOAD_PALLETS' id='UPLOAD_PALLETS' type='submit' value='Upload Pallet Rates' onclick='return check_uploaded_file(PALLET_RATES);' />-->
				 <!--<a class='btn btn-sm btn-success' href='javascript:void(0);' onclick='javascript: return show_hide_div(&quot;add_detention&quot;);'><span class='glyphicon glyphicon-plus'></span> Add Detention Rates</a>-->
				</div>
			</div>
            
          <div class='form-group' >
		 
		 $add_detention_div
		 
		 
				
		  <div  class='table-responsive' style='max-height:400px;overflow:auto;'>
		  <div  style='position:absolute;margin-top:12px;margin-left:600px;display:none;' id='detention_loader'><img src='images/loading.gif' /></div>
            <table class='table table-striped table-condensed table-bordered table-hover' >
            <thead >
              <tr class='exspeedite-bg'>
			     <th class='text-center' style='width:28.49%'><span class='text-muted'><span class='glyphicon glyphicon-unchecked'></span></span></th>
			    <th class='text-center'style='width:37.49%'>NAME</th>
               <!-- <th class='text-center' style='width:7.49%'> CITY</th>
				 <th class='text-center' style='width:7.49%'> STATE</th>
				 <th class='text-center'> ZIP</th>-->
				 <th class='text-center'style='width:17.49%'> MIN CHARGE</th>
				  <th class='text-center'> MAX CHARGE</th>
			</tr>
            </thead>
			 </table>
			 </div>
			   <div  class='table-responsive' style='max-height:400px;overflow:auto;'>
			 <table class='table table-striped table-condensed table-bordered table-hover' >
            <tbody >
			".$det_res."
			</tbody>
          </table>
        </div>
		</div>
		
       </div>
	   
	   
		<div class='col-sm-12'>
			
			<div class='form-group'>
				<label for='HANDLING_CHARGES' class='col-sm-4 control-label'>Handling Charges</label>
				<div class='col-sm-8'>
				<input class='' name='HANDLING_CHARGES' id='HANDLING_CHARGES' type='file' />
				</div>
			</div>
			
			<div class='form-group'>
				<label for='' class='col-sm-4 control-label'>&nbsp;</label>
				<div class='col-sm-8'>
				<input  class='btn btn-sm btn-success' name='UPLOAD_HANDLING' id='UPLOAD_HANDLING' type='submit' value='Upload Handling Charges' onclick='return check_uploaded_file(HANDLING_CHARGES);' />
				 <a class='btn btn-sm btn-success' href='javascript:void(0);' onclick='javascript: return show_hide_div(&quot;add_handle&quot;);'><span class='glyphicon glyphicon-plus'></span> Add </a>
				</div>
			</div>
            
          <div class='form-group' >
		  <div id='reload_handling_result' style='display:none;'><img src='images/loading.gif' border='1' style='position:absolute; margin-left:250px;margin-top:10px;'></div>
		  <table width='100%' border='0' cellspacing='0' cellpadding='0' style='margin:5px;display:none;' class='table table-striped table-condensed table-bordered table-hover' id='add_handle'>
                  <tr>
                    <td style='font-size:12px; font-family:Arial, Helvetica, sans-serif; text-transform:uppercase; padding:1 6px;'>Shipper</td>
                    <td><input type='text' name='origin_name' id='origin_name' size='15' class='form-control' value='' ></td>
                    <td style='font-size:12px; font-family:Arial, Helvetica, sans-serif; text-transform:uppercase; padding:1 6px;'>City</td>
                    <td><input type='text' name='from_city' id='from_city' size='15' class='form-control' value='' ></td>
                    <td style='font-size:12px; font-family:Arial, Helvetica, sans-serif; text-transform:uppercase; padding:1 6px;'>State</td>
                    <td><input type='text' name='from_state' id='from_state' size='15' class='form-control' value='' maxlength='2'></td>
					 <td style='font-size:12px; font-family:Arial, Helvetica, sans-serif; text-transform:uppercase; padding:1 6px;'>Zipcode</td>
                    <td><input type='text' name='from_zip' id='from_zip' size='15' class='form-control' value='' ></td>
					<td style='font-size:12px; font-family:Arial, Helvetica, sans-serif; text-transform:uppercase; padding:1 6px;'>Zone Code</td>
                    <td><input type='text' name='from_zone' id='from_zone' size='15' class='form-control' value='' ></td>
					</tr>
					 <tr>
                   
                    <td style='font-size:12px; font-family:Arial, Helvetica, sans-serif; text-transform:uppercase; padding:1 6px;'>Consignee</td>
                    <td><input type='text' name='consignee_name' id='consignee_name' size='15' class='form-control' value='' ></td>
                    <td style='font-size:12px; font-family:Arial, Helvetica, sans-serif; text-transform:uppercase; padding:1 6px;'>City</td>
                    <td><input type='text' name='to_city' id='to_city' size='15' class='form-control' value='' ></td>
					<td style='font-size:12px; font-family:Arial, Helvetica, sans-serif; text-transform:uppercase; padding:1 6px;'>State</td>
                    <td><input type='text' name='to_state' id='to_state' size='15' class='form-control' value='' maxlength='2'></td>
                    <td style='font-size:12px; font-family:Arial, Helvetica, sans-serif; text-transform:uppercase; padding:1 6px;'>Zipcode</td>
                    <td><input type='text' name='to_zip' id='to_zip' size='15' class='form-control' value='' ></td>
					<td style='font-size:12px; font-family:Arial, Helvetica, sans-serif; text-transform:uppercase; padding:1 6px;'>Zone Code</td>
                    <td><input type='text' name='to_zone' id='to_zone' size='15' class='form-control' value='' ></td>
					</tr>
					 <tr>
                    
                    <td style='font-size:12px; font-family:Arial, Helvetica, sans-serif; text-transform:uppercase; padding:1 6px;'>Pallets</td>
                    <td><input type='text' name='pallets' id='pallets' size='15' class='form-control' value='' ></td>
					 <td style='font-size:12px; font-family:Arial, Helvetica, sans-serif; text-transform:uppercase; padding:1 6px;'>Invoice</td>
                    <td><input type='text' name='invoice' id='invoice' size='15' class='form-control' value='' ></td>
                    <td style='font-size:12px; font-family:Arial, Helvetica, sans-serif; text-transform:uppercase; padding:1 6px;'>Driver Rate</td>
                    <td><input type='text' name='driver_rate' id='driver_rate' size='15' class='form-control' value='' ></td>
					<!--<td style='font-size:12px; font-family:Arial, Helvetica, sans-serif; text-transform:uppercase; padding:0 6px;'>&nbsp;</td>-->
                    <td colspan='2'><input name='save_handling_charge' id='save_handling_charge' type='submit' class='btn btn-md btn-default' value='Submit' onclick='javascript:return chk_handling_charge();'></td>
					</tr>
					
                </table>
				
		  <div  class='table-responsive' >
            <table class='display table table-striped table-condensed table-bordered table-hover' >
            <thead >
              <tr class='exspeedite-bg'>
			     <th class='text-center' style='width:3%'><span class='text-muted'><span class='glyphicon glyphicon-unchecked'></span></span></th>
			   <th class='text-center' style='width:11%'>
			    <span style='float:left;'>NAME</span> 
				<span style='float:left; width:5px; margin-top:-6px;'>
                <a href='exp_addclientrate.php?TYPE=".$edit['USER_TYPE']."&CODE=".$edit['CODE']."&SORT_BY=han_asc&COL=ORIGIN_NAME'><img src='./media/images/sort_asc_disabled1.png'/></a>
                <a href='exp_addclientrate.php?TYPE=".$edit['USER_TYPE']."&CODE=".$edit['CODE']."&SORT_BY=han_desc&COL=ORIGIN_NAME'><img style='margin-top:-21px;' src='./media/images/sort_desc_disabled1.png'/></a>
                </span>
			   </th>
                <th class='text-center' style='width:9%'> 
                <span style='float:left;'>CITY</span> 
				<span style='float:left; width:5px; margin-top:-6px;'>
                <a href='exp_addclientrate.php?TYPE=".$edit['USER_TYPE']."&CODE=".$edit['CODE']."&SORT_BY=han_asc&COL=FROM_CITY'><img src='./media/images/sort_asc_disabled1.png'/></a>
                <a href='exp_addclientrate.php?TYPE=".$edit['USER_TYPE']."&CODE=".$edit['CODE']."&SORT_BY=han_desc&COL=FROM_CITY'><img style='margin-top:-21px;' src='./media/images/sort_desc_disabled1.png'/></a>
                </span>
			   </th>
				 <th class='text-center' style='width:5%'> 
				 <span style='float:left;'>STATE</span> 
				<span style='float:left; width:5px; margin-top:-6px;'>
                <a href='exp_addclientrate.php?TYPE=".$edit['USER_TYPE']."&CODE=".$edit['CODE']."&SORT_BY=han_asc&COL=FROM_STATE'><img src='./media/images/sort_asc_disabled1.png'/></a>
                <a href='exp_addclientrate.php?TYPE=".$edit['USER_TYPE']."&CODE=".$edit['CODE']."&SORT_BY=han_desc&COL=FROM_STATE'><img style='margin-top:-21px;' src='./media/images/sort_desc_disabled1.png'/></a>
                </span>
                </th>
				 <th class='text-center' style='width:6%'> 
				 <span style='float:left;'>ZIP</span> 
				<span style='float:left; width:5px; margin-top:-6px;'>
                <a href='exp_addclientrate.php?TYPE=".$edit['USER_TYPE']."&CODE=".$edit['CODE']."&SORT_BY=han_asc&COL=FROM_ZIP'><img src='./media/images/sort_asc_disabled1.png'/></a>
                <a href='exp_addclientrate.php?TYPE=".$edit['USER_TYPE']."&CODE=".$edit['CODE']."&SORT_BY=han_desc&COL=FROM_ZIP'><img style='margin-top:-21px;' src='./media/images/sort_desc_disabled1.png'/></a>
                </span>
				 </th>
				 <th class='text-center' style='width:7%'> 
				 <span style='float:left;'>ZONE CODE</span> 
				<span style='float:left; width:5px; margin-top:-6px;'>
                <a href='exp_addclientrate.php?TYPE=".$edit['USER_TYPE']."&CODE=".$edit['CODE']."&SORT_BY=han_asc&COL=FROM_ZONE'><img src='./media/images/sort_asc_disabled1.png'/></a>
                <a href='exp_addclientrate.php?TYPE=".$edit['USER_TYPE']."&CODE=".$edit['CODE']."&SORT_BY=han_desc&COL=FROM_ZONE'><img style='margin-top:-21px;' src='./media/images/sort_desc_disabled1.png'/></a>
                </span>
				 </th>
				 <th class='text-center' style='width:11%'>
				 <span style='float:left;'>CONSIGNEE</span> 
				<span style='float:left; width:5px; margin-top:-6px;'>
                <a href='exp_addclientrate.php?TYPE=".$edit['USER_TYPE']."&CODE=".$edit['CODE']."&SORT_BY=han_asc&COL=CONSIGNEE_NAME'><img src='./media/images/sort_asc_disabled1.png'/></a>
                <a href='exp_addclientrate.php?TYPE=".$edit['USER_TYPE']."&CODE=".$edit['CODE']."&SORT_BY=han_desc&COL=CONSIGNEE_NAME'><img style='margin-top:-21px;' src='./media/images/sort_desc_disabled1.png'/></a>
                </span>
                </th>
				 <th class='text-center'style='width:9%'> 
				 <span style='float:left;'>CITY</span> 
				<span style='float:left; width:5px; margin-top:-6px;'>
                <a href='exp_addclientrate.php?TYPE=".$edit['USER_TYPE']."&CODE=".$edit['CODE']."&SORT_BY=han_asc&COL=TO_CITY'><img src='./media/images/sort_asc_disabled1.png'/></a>
                <a href='exp_addclientrate.php?TYPE=".$edit['USER_TYPE']."&CODE=".$edit['CODE']."&SORT_BY=han_desc&COL=TO_CITY'><img style='margin-top:-21px;' src='./media/images/sort_desc_disabled1.png'/></a>
                </span></th>
				 <th class='text-center'style='width:5%'> 
				 <span style='float:left;'>STATE</span> 
				<span style='float:left; width:5px; margin-top:-6px;'>
                <a href='exp_addclientrate.php?TYPE=".$edit['USER_TYPE']."&CODE=".$edit['CODE']."&SORT_BY=han_asc&COL=TO_STATE'><img src='./media/images/sort_asc_disabled1.png'/></a>
                <a href='exp_addclientrate.php?TYPE=".$edit['USER_TYPE']."&CODE=".$edit['CODE']."&SORT_BY=han_desc&COL=TO_STATE'><img style='margin-top:-21px;' src='./media/images/sort_desc_disabled1.png'/></a>
                </span></th>
				<th class='text-center' style='width:6%'> 
				<span style='float:left;'>ZIP</span> 
				<span style='float:left; width:5px; margin-top:-6px;'>
                <a href='exp_addclientrate.php?TYPE=".$edit['USER_TYPE']."&CODE=".$edit['CODE']."&SORT_BY=han_asc&COL=TO_ZIP'><img src='./media/images/sort_asc_disabled1.png'/></a>
                <a href='exp_addclientrate.php?TYPE=".$edit['USER_TYPE']."&CODE=".$edit['CODE']."&SORT_BY=han_desc&COL=TO_ZIP'><img style='margin-top:-21px;' src='./media/images/sort_desc_disabled1.png'/></a>
                </span></th>
				 <th class='text-center'style='width:7%'> 
				 <span style='float:left;'>ZONE CODE</span> 
				<span style='float:left; width:5px; margin-top:-6px;'>
                <a href='exp_addclientrate.php?TYPE=".$edit['USER_TYPE']."&CODE=".$edit['CODE']."&SORT_BY=han_asc&COL=TO_ZONE'><img src='./media/images/sort_asc_disabled1.png'/></a>
                <a href='exp_addclientrate.php?TYPE=".$edit['USER_TYPE']."&CODE=".$edit['CODE']."&SORT_BY=han_desc&COL=TO_ZONE'><img style='margin-top:-21px;' src='./media/images/sort_desc_disabled1.png'/></a>
                </span></th>
				 <th class='text-center' style='width:7%'>PALLET</th>
       			<th class='text-center'style='width:6%'>INVOICE($)</th>
				 <th class='text-center'style='width:6%'>DRIVER($)</th>
              </tr>
            </thead>
			</table>
			</div>
			 <div  class='table-responsive' style='max-height:400px;overflow:auto;'>
			<table class='display table table-striped table-condensed table-bordered table-hover'>
			 <tbody >
			".$handling_result."
			</tbody>
          </table>
        </div>
		</div>
		
       </div>
	 
	
		<div class='col-sm-12' >
		
			 <div class='form-group'>
				<label for='FREIGHT_MINIMUM_CHARGE' class='col-sm-4 control-label'>Freight Charge</label>
				<div class='col-sm-8'>
			   <input class='' name='FREIGHT_MINIMUM_CHARGE' id='FREIGHT_MINIMUM_CHARGE' type='file'  placeholder='Freight Minimum Charge' >
				</div>
			</div>
                
       	<div class='form-group'>
				<label for='' class='col-sm-4 control-label'>&nbsp;</label>
				<div class='col-sm-8'>
				<input  class='btn btn-sm btn-success' name='UPLOAD_FRIGHT' id='UPLOAD_FRIGHT' type='submit' value='Upload Freight Charges' onclick='return check_uploaded_file(FREIGHT_MINIMUM_CHARGE);'/>
					 <a class='btn btn-sm btn-success' href='javascript:void(0);' onclick='javascript: return show_hide_div(&quot;add_frieght&quot;);'><span class='glyphicon glyphicon-plus'></span> Add </a>
				</div>
			</div>
			<div class='form-group' >
			 <div id='reload_freight_result' style='display:none;'><img src='images/loading.gif' border='1' style='position:absolute; margin-left:250px;margin-top:10px;'></div>
		  <table width='100%' border='0' cellspacing='0' cellpadding='0' style='margin:5px;display:none;' class='table table-striped table-condensed table-bordered table-hover' id='add_frieght'>
                  <tr>
                    <td style='font-size:12px; font-family:Arial, Helvetica, sans-serif; text-transform:uppercase; padding:1 6px;'>Shipper</td>
                    <td><input type='text' name='shipper_name' id='shipper_name' size='15' class='form-control' value='' ></td>
                    <td style='font-size:12px; font-family:Arial, Helvetica, sans-serif; text-transform:uppercase; padding:1 6px;'>City</td>
                    <td><input type='text' name='origin_city' id='origin_city' size='15' class='form-control' value='' ></td>
                    <td style='font-size:12px; font-family:Arial, Helvetica, sans-serif; text-transform:uppercase; padding:1 6px;'>State</td>
                    <td><input type='text' name='origin_state' id='origin_state' size='15' class='form-control' value=''  maxlength='2'></td>
					<td style='font-size:12px; font-family:Arial, Helvetica, sans-serif; text-transform:uppercase; padding:1 6px;'>Zipcode</td>
                    <td><input type='text' name='origin_zip' id='origin_zip' size='15' class='form-control' value='' ></td>
					</tr>
					 <tr>
                    <td style='font-size:12px; font-family:Arial, Helvetica, sans-serif; text-transform:uppercase; padding:1 6px;'>Consignee</td>
                    <td><input type='text' name='CONS_NAME' id='CONS_NAME' size='15' class='form-control' value='' ></td>
					
                    <td style='font-size:12px; font-family:Arial, Helvetica, sans-serif; text-transform:uppercase; padding:1 6px;'>City</td>
                    <td><input type='text' name='dest_city' id='dest_city' size='15' class='form-control' value='' ></td>
					  <td style='font-size:12px; font-family:Arial, Helvetica, sans-serif; text-transform:uppercase; padding:1 6px;'>State</td>
                    <td><input type='text' name='dest_state' id='dest_state' size='15' class='form-control' value='' maxlength='2' ></td>
                    <td style='font-size:12px; font-family:Arial, Helvetica, sans-serif; text-transform:uppercase; padding:1 6px;'>Zipcode</td>
                    <td><input type='text' name='dest_zip' id='dest_zip' size='15' class='form-control' value='' ></td>
					
					</tr>
					 <tr>
                  
                    <td style='font-size:12px; font-family:Arial, Helvetica, sans-serif; text-transform:uppercase; padding:1 6px;'>Line Haul</td>
                    <td><input type='text' name='line_haul' id='line_haul' size='15' class='form-control' value='' ></td>
				
					 <td style='font-size:12px; font-family:Arial, Helvetica, sans-serif; text-transform:uppercase; padding:1 6px;'>Min Line Haul</td>
                    <td><input type='text' name='min_line_haul' id='min_line_haul' size='15' class='form-control' value='' ></td>
                    <td style='font-size:12px; font-family:Arial, Helvetica, sans-serif; text-transform:uppercase; padding:1 6px;'>FSC</td>
                    <td><input type='text' name='fsc_rate' id='fsc_rate' size='15' class='form-control' value='' ></td>
					 <td style='font-size:12px; font-family:Arial, Helvetica, sans-serif; text-transform:uppercase; padding:1 6px;'>Milleage</td>
                    <td><input type='text' name='fsc_milleage' id='fsc_milleage' size='15' class='form-control' value='' onkeyup='return calculate_frieght_amount();'></td>
					</tr>
					<tr>
					 <td style='font-size:12px; font-family:Arial, Helvetica, sans-serif; text-transform:uppercase; padding:1 6px;'>Weekend Holiday</td>
                    <td><input type='text' name='weekend_hol' id='weekend_hol' size='15' class='form-control' value='' ></td>
					
					<td style='font-size:12px; font-family:Arial, Helvetica, sans-serif; text-transform:uppercase; padding:1 6px;'>Enroute Stop Offs</td>
                    <td><input type='text' name='enroute' id='enroute' size='15' class='form-control' value='' ></td>
					<td style='font-size:12px; font-family:Arial, Helvetica, sans-serif; text-transform:uppercase; padding:1 6px;'>Detention</td>
                    <td><input type='text' name='detention' id='detention' size='15' class='form-control' value='' ></td>
					 <!--<td style='font-size:12px; font-family:Arial, Helvetica, sans-serif; text-transform:uppercase; padding:1 6px;'>Miles</td>
                    <td><input type='text' name='freight_miles' id='freight_miles' size='15' class='form-control' value='' ></td>-->
					<td style='font-size:12px; font-family:Arial, Helvetica, sans-serif; text-transform:uppercase; padding:1 6px;'>Per Mile</td>
                    <td><input type='text' name='per_miles' id='per_miles' size='15' class='form-control' value='' onkeyup='return calculate_frieght_amount();' ></td>
					</tr>
					<tr>
					 <td style='font-size:12px; font-family:Arial, Helvetica, sans-serif; text-transform:uppercase; padding:1 6px;'>Amount</td>
                    <td><input type='text' name='amount' id='amount' size='15' class='form-control' value='' ></td>
					<!--<td style='font-size:12px; font-family:Arial, Helvetica, sans-serif; text-transform:uppercase; padding:0 6px;'>&nbsp;</td>-->
                    <td colspan='2'><input name='save_freight_rate' id='save_freight_rate' type='submit' class='btn btn-md btn-default' value='Submit' onclick='javascript:return chk_freight_rates();'></td>
                  </tr>
                </table>
			<div  class='table-responsive'   >
            <table class='display table table-striped table-condensed table-bordered table-hover' id='table_FRIEGHT' >
            <thead>
              <tr class='exspeedite-bg'>
                   <th class='text-center' style='width:3%'><span class='text-muted'><span class='glyphicon glyphicon-unchecked'></span></span></th>
				
                <th class='text-center' style='width:11%'>
                <span style='float:left;'>SHIPPER NAME</span>
                <span style='float:left; width:5px; margin-top:-6px;'>
                <a href='exp_addclientrate.php?TYPE=".$edit['USER_TYPE']."&CODE=".$edit['CODE']."&SORT_BY=fr_asc&COL=SHIPPER_NAME'><img src='./media/images/sort_asc_disabled1.png'/></a>
                <a href='exp_addclientrate.php?TYPE=".$edit['USER_TYPE']."&CODE=".$edit['CODE']."&SORT_BY=fr_desc&COL=SHIPPER_NAME'><img style='margin-top:-21px;' src='./media/images/sort_desc_disabled1.png'/></a>
                </span> 
				</th>
				<th class='text-center' style='width:8%'>
				<span style='float:left;'>CITY</span>
                <span style='float:left; width:5px; margin-top:-6px;'>
                <a href='exp_addclientrate.php?TYPE=".$edit['USER_TYPE']."&CODE=".$edit['CODE']."&SORT_BY=fr_asc&COL=ORIGIN_CITY'><img src='./media/images/sort_asc_disabled1.png'/></a>
                <a href='exp_addclientrate.php?TYPE=".$edit['USER_TYPE']."&CODE=".$edit['CODE']."&SORT_BY=fr_desc&COL=ORIGIN_CITY'><img style='margin-top:-21px;' src='./media/images/sort_desc_disabled1.png'/></a>
                </span>
                </th>
			    <th class='text-center' style='width:4%'>
			    <span style='float:left;'>STATE</span>
                <span style='float:left; width:5px; margin-top:-6px;'>
                <a href='exp_addclientrate.php?TYPE=".$edit['USER_TYPE']."&CODE=".$edit['CODE']."&SORT_BY=fr_asc&COL=ORIGIN_STATE'><img src='./media/images/sort_asc_disabled1.png'/></a>
                <a href='exp_addclientrate.php?TYPE=".$edit['USER_TYPE']."&CODE=".$edit['CODE']."&SORT_BY=fr_desc&COL=ORIGIN_STATE'><img style='margin-top:-21px;' src='./media/images/sort_desc_disabled1.png'/></a>
                </span>
                </th>
				<th class='text-center' style='width:5%'>
				<span style='float:left;'>ZIPCODE</span>
                <span style='float:left; width:5px; margin-top:-6px;'>
                <a href='exp_addclientrate.php?TYPE=".$edit['USER_TYPE']."&CODE=".$edit['CODE']."&SORT_BY=fr_asc&COL=ORIGIN_ZIP'><img src='./media/images/sort_asc_disabled1.png'/></a>
                <a href='exp_addclientrate.php?TYPE=".$edit['USER_TYPE']."&CODE=".$edit['CODE']."&SORT_BY=fr_desc&COL=ORIGIN_ZIP'><img style='margin-top:-21px;' src='./media/images/sort_desc_disabled1.png'/></a>
                </span>
                </th>
				<th class='text-center' style='width:11%'>
				<span style='float:left;'>CONSIGNEE NAME</span>
                <span style='float:left; width:5px; margin-top:-6px;'>
                <a href='exp_addclientrate.php?TYPE=".$edit['USER_TYPE']."&CODE=".$edit['CODE']."&SORT_BY=fr_asc&COL=CONSIGNEE_NAME'><img src='./media/images/sort_asc_disabled1.png'/></a>
                <a href='exp_addclientrate.php?TYPE=".$edit['USER_TYPE']."&CODE=".$edit['CODE']."&SORT_BY=fr_desc&COL=CONSIGNEE_NAME'><img style='margin-top:-21px;' src='./media/images/sort_desc_disabled1.png'/></a>
                </span>
                </th>
				<th class='text-center' style='width:8%'>
				<span style='float:left;'>CITY</span>
                <span style='float:left; width:5px; margin-top:-6px;'>
                <a href='exp_addclientrate.php?TYPE=".$edit['USER_TYPE']."&CODE=".$edit['CODE']."&SORT_BY=fr_asc&COL=DESTINATION_CITY'><img src='./media/images/sort_asc_disabled1.png'/></a>
                <a href='exp_addclientrate.php?TYPE=".$edit['USER_TYPE']."&CODE=".$edit['CODE']."&SORT_BY=fr_desc&COL=DESTINATION_CITY'><img style='margin-top:-21px;' src='./media/images/sort_desc_disabled1.png'/></a>
                </span>
                </th>
			    <th class='text-center' style='width:4%'>
			    <span style='float:left;'>STATE</span>
                <span style='float:left; width:5px; margin-top:-6px;'>
                <a href='exp_addclientrate.php?TYPE=".$edit['USER_TYPE']."&CODE=".$edit['CODE']."&SORT_BY=fr_asc&COL=DESTINAION_STATE'><img src='./media/images/sort_asc_disabled1.png'/></a>
                <a href='exp_addclientrate.php?TYPE=".$edit['USER_TYPE']."&CODE=".$edit['CODE']."&SORT_BY=fr_desc&COL=DESTINAION_STATE'><img style='margin-top:-21px;' src='./media/images/sort_desc_disabled1.png'/></a>
                </span>
                </th>
				<th class='text-center' style='width:5%'>
				<span style='float:left;'>ZIPCODE</span>
                <span style='float:left; width:5px; margin-top:-6px;'>
                <a href='exp_addclientrate.php?TYPE=".$edit['USER_TYPE']."&CODE=".$edit['CODE']."&SORT_BY=fr_asc&COL=DESTINATION_ZIP'><img src='./media/images/sort_asc_disabled1.png'/></a>
                <a href='exp_addclientrate.php?TYPE=".$edit['USER_TYPE']."&CODE=".$edit['CODE']."&SORT_BY=fr_desc&COL=DESTINATION_ZIP'><img style='margin-top:-21px;' src='./media/images/sort_desc_disabled1.png'/></a>
                </span>
                </th>
				<th class='text-center'style='width:5%' >LINE HAUL/PER MILES</th>
				 <th class='text-center' style='width:5%'>FSC</th>
				  <th class='text-center' style='width:5%'>MILEAGE</th>
				 <th class='text-center' style='width:5%'>WEEKEND HOLIDAY</th>
				 <th class='text-center' style='width:5%'>ENROUTE STOP OFFS</th>
				 <th class='text-center' style='width:5%'>DETENTION</th>
				<!--  <th class='text-center'>MILES</th>-->
				  <th class='text-center' style='width:5%'>PER MILES</th>
			   <th class='text-center' style='width:5%'>AMOUNT</th>
              </tr>
            </thead>
			</table>
			</div>
			<div  class='table-responsive'  style='height:250px;overflow:auto;' >
			<table  class='display table table-striped table-condensed table-bordered table-hover'>
            <tbody>
			
			".$fr_rate."
			
			
			</tbody>
          </table>
		  </div>
        </div>
        
	</div>
			
		
	
	</div>
	
</form>



</div>
	<script language='JavaScript' type='text/javascript'><!--
		
		$(document).ready( function () {
		
			
			
			$('#table_client,#table_cfsc').dataTable({
		        
		        'bFilter': false,
		        //'bSort': false,
		        'bInfo': false,
				
				'sScrollY': (200) + 'px',
			    'bPaginate': false,
				'bScrollCollapse': true,
				'bSortClasses': false,
				'columnDefs': [ { 'targets': 0, 'orderable': false } ]
				
			});
			
			if( window.HANDLE_RESIZE_EVENTS ) {
				$(window).bind('resize', function(e) {
					console.log('resize event triggered');
					if (window.RT) clearTimeout(window.RT);
					window.RT = setTimeout(function() {
						this.location.reload(false); /* false to get page from cache */
					}, 100);
				});
			}
			
		});
	//--></script>";

 $str.=" 
	<div class='md_row'>
					<div class='well_md_l'>
 <div class='table-responsive'>
   <h3><img height='29' src='images/money_bag.png' alt='tractor_icon'> Select ".ucfirst($edit['USER_TYPE'])." Rates : </h3>
  <form name='client_rate' id='client_rate' action='exp_addclientrate.php?TYPE=".$edit['USER_TYPE']."&CODE=".$edit['CODE']."' method='post'>
  <div class='btn-group' style='margin-bottom:20px;'>
	 <select class='form-control' name='category' id='category' style='float:left;width:200px; margin-right:20px;' onchange='javascript:return get_search_records(this.value);'> 
			<option value=''>	ALL 	</option>	
            ".$cat_option."
		</select>
		<button class='btn btn-md btn-success' type='submit' name='saverate' id='saverate' onclick='return check_rates();'>
		<span class='glyphicon glyphicon-ok'></span>
		Apply Rates To ".ucfirst($edit['USER_TYPE'])."
		</button>
	</div>
		
<br> 
<div id='reload_search_result' style='display:none;'><img src='images/loading.gif' border='1' style='position:absolute; margin-left:250px;margin-top:10px;'></div>
<div style='height:400px;overflow:auto;' class='table-responsive'>
<div id='search_reload' >
		<table class='display table table-striped table-condensed table-bordered table-hover' id='table_client'>
            <thead>
              <tr class='exspeedite-bg'>
                <th class='text-center no-sort'><span class='text-muted'><span class='glyphicon glyphicon-unchecked'></span></span></th>
				 <th class='text-center'>Rate Code</th>
				 <th class='text-center'>Rate Name</th>
				 <th class='text-center'>Rate Description</th>
                <th class='text-center'>Rates</th>
				<th class='text-center'>Category</th>
				 <th class='text-center'>Bonus</th>
				</tr>
            </thead>
            ".$cat_result."
          </table>
</div>
</div>
</form>
</div></div>
<div class='well_md_r'>
 <div class='table-responsive'>
   <h3><img height='29' src='images/money_bag.png' alt='tractor_icon'> Select FSC # : </h3>
  <form name='client_fsc_rate' id='client_fsc_rate' action='exp_addclientrate.php?TYPE=".$edit['USER_TYPE']."&CODE=".$edit['CODE']."' method='post'>
  <div class='btn-group' style='margin-bottom:20px;'>
	
		<button class='btn btn-md btn-success' type='submit' name='assign_fsc' id='assign_fsc' onclick='return check_fsc();'>
		<span class='glyphicon glyphicon-ok'></span>
		Apply FSC Schedule To ".ucfirst($edit['USER_TYPE'])."
		</button></div>
		
<br> 
<div id='reload_fsc_result' style='display:none;'><img src='images/loading.gif' border='1' style='position:absolute; margin-left:250px;margin-top:10px;'></div>
<div style='height:400px;overflow:auto;' class='table-responsive'>
<div id='search_reload'>
		<table id='FSC_TABLE' class='display table table-striped table-condensed table-bordered table-hover' id='table_cfsc' >
            <thead>
              <tr class='exspeedite-bg'>
                <td class='text-center no-sort'><span class='text-muted'><span class='glyphicon glyphicon-unchecked'></span></span></td>
				 <th class='text-center'>FSC ID</th>
				 <th class='text-center'>FSC Name</th>
				 <th class='text-center'>Average Price</th>
				  <th class='text-center'>Average Rate</th>
				  <th class='text-center'>WEEKLY FSC CHARGE</th>
				 <th class='text-center'>Starting Date</th>
				 <th class='text-center'>Ending Date</th>
                <!--<th class='text-center'>Start Date</th>
				<th class='text-center'>End Date</th>-->
				 </tr>
            </thead>
            ".$fsc."
          </table>
 </div>
</div>
</form>
</div></div>
</div>
<div class='modal fade in' id='myModal' >

<div class='modal-dialog modal-lg'>
<div class='modal-content form-horizontal' role='main'>
<div class='modal-header'>
<h4 class='modal-title'>Add Pallet Rates</h4>
</div>
<div class='modal-body' style='font-size: 14px; body:inherit;'>
<form name='add_pallet_rate' id='add_pallet_rate' action='exp_addclientrate.php?TYPE=".$edit['USER_TYPE']."&CODE=".$edit['CODE']."' method='post'>
<div class='btn-group'>
<a href='#' class='btn btn-md btn-default' data-dismiss='modal'><span class='glyphicon glyphicon-remove'></span>Close</a>
<input type='submit' name='save_client_pallet_rate' id='save_client_pallet_rate' class='btn btn-md btn-success' onclick='javascript:return check();'  value='Save Changes'/>
</div>
<div id='loader' style='display:none; position:absolute;margin-left:250px;'><img src='images/loading.gif' /></div>
<div class='form-group'>
<input class='form-control text-right' name='PALLETS_ID' id='PALLETS_ID' type='hidden'  placeholder='PALLETS' value='' >
			
<div class='form-group'>
				<label for='PALLETS_FROM' class='col-sm-4 control-label'>Pallets From</label>
				<div class='col-sm-4'>
				<select class='form-control' name='PALLETS_FROM' id='PALLETS_FROM'>
				'.$for_loop.'
				<option value='11'>10+</option>
				</select>
				</div>
			</div>
			
			
			
			
<div class='form-group'>
				<label for='PALLETS' class='col-sm-4 control-label'>Pallets TO</label>
				<div class='col-sm-4'>
				<select class='form-control' name='PALLETS_TO' id='PALLETS_TO'>
				'.$for_loop.'
				<option value='11'>10+</option>
				</select>
				</div>
			</div>
<div class='form-group'>
				<label for='PALLETS' class='col-sm-4 control-label'>Pallet Rate</label>
				<div class='col-sm-4'>
			   <input class='form-control text-right' name='PALLETS_RATE' id='PALLETS_RATE' type='PALLETS' placeholder='PALLETS' >
				</div>
			</div>
			</div>
			</form>
</div>
<div class='modal-footer'>

</div>
</div><!-- /.modal-content -->
</div><!-- /.modal-dialog -->

</div>
<!-- detention popup-->
<div class='modal fade in' id='mydetentionModal'>

<div class='modal-dialog modal-lg'>
<div class='modal-content form-horizontal' role='main'>
<div class='modal-header'>
<h4 class='modal-title'>Add Loaded Detention Rates</h4>
</div>
<div class='modal-body' style='font-size: 14px; body:inherit;'>
<form name='add_detention_rate' id='add_detention_rate' action='exp_addclientrate.php?TYPE=".$edit['USER_TYPE']."&CODE=".$edit['CODE']."' method='post'>
<div class='btn-group'>
<a href='#' class='btn btn-md btn-default' data-dismiss='modal'><span class='glyphicon glyphicon-remove'></span>Close</a>
<input type='submit' name='save_client_det_rate' id='save_client_det_rate' class='btn btn-md btn-success' onclick='javascript:return check_det();'  value='Save Changes'/>
</div>
<div id='dloader' style='display:none; position:absolute;margin-left:250px;'><img src='images/loading.gif' /></div>
<div class='form-group'>
<input class='form-control text-right' name='DET_ID' id='DET_ID' type='hidden'  value='' >
			
<div class='form-group'>
				<label for='DET_HOUR' class='col-sm-4 control-label'>Detention From</label>
				<div class='col-sm-4'>
			   <input class='form-control text-right' name='DET_HOUR' id='DET_HOUR' type='number' step='0.01' required>
				</div>
			</div>
			
			<div class='form-group'>
				<label for='DET_HOUR_TO' class='col-sm-4 control-label'>Detention To</label>
				<div class='col-sm-4'>
			   <input class='form-control text-right' name='DET_HOUR_TO' id='DET_HOUR_TO' type='number' step='0.01' required>
				</div>
			</div>

<div class='form-group'>
				<label for='DET_RATE' class='col-sm-4 control-label'>Detention Rate</label>
				<div class='col-sm-4'>
			   <input class='form-control text-right' name='DET_RATE' id='DET_RATE' type='number' step='0.01' required>
				</div>
			</div>
			</div>
			</form>
</div>
<div class='modal-footer'>

</div>
</div><!-- /.modal-content -->
</div><!-- /.modal-dialog -->

</div>

<!-- unloaded detention popup-->
<div class='modal fade in' id='myunloadeddetentionModal'>

<div class='modal-dialog modal-lg'>
<div class='modal-content form-horizontal' role='main'>
<div class='modal-header'>
<h4 class='modal-title'>Add Unloaded Detention Rates</h4>
</div>
<div class='modal-body' style='font-size: 14px; body:inherit;'>
<form name='add_un_detention_rate' id='add_un_detention_rate' action='exp_addclientrate.php?TYPE=".$edit['USER_TYPE']."&CODE=".$edit['CODE']."' method='post'>
<div class='btn-group'>
<a href='#' class='btn btn-md btn-default' data-dismiss='modal'><span class='glyphicon glyphicon-remove'></span>Close</a>
<input type='submit' name='save_unload_det_rate' id='save_unload_det_rate' class='btn btn-md btn-success' onclick='javascript:return check_unloaded_det();'  value='Save Changes'/>
</div>
<div id='un_dloader' style='display:none; position:absolute;margin-left:250px;'><img src='images/loading.gif' /></div>
<div class='form-group'>
<input class='form-control text-right' name='MAIN_DET_ID' id='MAIN_DET_ID' type='hidden'  value='' >
			
<div class='form-group'>
				<label for='UN_DET_HOUR' class='col-sm-4 control-label'>Detention From</label>
				<div class='col-sm-4'>
			   <input class='form-control text-right' name='UN_DET_HOUR' id='UN_DET_HOUR' type='number' step='0.01' required>
				</div>
			</div>
			
			<div class='form-group'>
				<label for='UN_DET_HOUR_TO' class='col-sm-4 control-label'>Detention To</label>
				<div class='col-sm-4'>
			   <input class='form-control text-right' name='UN_DET_HOUR_TO' id='UN_DET_HOUR_TO' type='number' step='0.01' required>
				</div>
			</div>

<div class='form-group'>
				<label for='Detention Rates' class='col-sm-4 control-label'>Detention Rate</label>
				<div class='col-sm-4'>
			   <input class='form-control text-right' name='UN_DET_RATE' id='UN_DET_RATE' type='number' step='0.01' required>
				</div>
			</div>
			</div>
			</form>
</div>
<div class='modal-footer'>

</div>
</div><!-- /.modal-content -->
</div><!-- /.modal-dialog -->

</div>";

echo $str;
	}
	
    ##------view fsc schedule at admin side-------##
	public function render_fsc_screen($code)
	{
		$dec2 = $code['FSC_4DIGIT'] ? 4 : 2;
		//echo '<pre/>';print_r($code);
		
		$result='<tbody>';
		if($code['records']>0)
		{
			foreach($code['records'] as $rec)
			{
				$highlight='';
				if($rec['LOW_PER_GALLON']<=$code['fsc_average'] && $rec['HIGH_PER_GALLON']>=$code['fsc_average'])
				{
					$highlight='class="success"';
				}
			  $result.="<tr style='cursor:pointer;' $highlight>
			  				<td class='text-center'>
							<a  id='WP Rawls1' class='btn btn-default btn-xs' href='exp_view_fsc.php?TYPE=editfsc&CODE=".$rec['FSC_ID']."' data-original-title='' title=''><span style='font-size: 14px;'><span class='glyphicon glyphicon-edit'></span></span></a>
							<a onclick='javascript: return delete_single_fsc(".$rec['FSC_ID'].",&quot;".$rec['FSC_UNIQUE_ID']."&quot;);' href='javascript:void(0);' class='btn btn-default btn-xs' data-original-title='' title=''> <span style='font-size: 14px;'><span class='glyphicon glyphicon-trash'></span> </span> </a></td>
       						 <td style='text-align:center;'>$".$rec['LOW_PER_GALLON']."</td>
      						 <td style='text-align:center;'>$".$rec['HIGH_PER_GALLON']."</td>
							 <td style='text-align:center;'>$".number_format($rec['FLAT_AMOUNT'],$dec2,'.','')."</td>
      					     <td style='text-align:center;'>$".number_format($rec['PER_MILE_ADJUST'],$dec2,'.','')."</td>
							  <td style='text-align:center;'>".number_format($rec['PERCENT'],$dec2,'.','')."</td>
        				     <td style='text-align:center;'>".date('m/d/Y',strtotime($rec['START_DATE']))."</td>
        				     <td style='text-align:center;'>".date('m/d/Y',strtotime($rec['END_DATE']))."</td>
      						</tr>";
			}
		}
		else
		{
			  $result.="<tr style='cursor:pointer;' >
       						 <td style='text-align:center;' colspan='5'>No records are available!</td></tr>";
		}
		$result.='</tbody>'; 
		
		$new_row="<td id='fsc_col'>&nbsp;</td><td id='fsc_col_val'>&nbsp;</td>";
	if($code['fsc_per_mile_field']!="" && $code['fsc_column']!="")
	{$new_row="";
		$new_row="<td id='fsc_col'>".$code['fsc_column'] ."&nbsp;</td><td id='fsc_col_val'><input type='text' name='fsc_new_field' id='fsc_new_field' class='form-control' value='".$code['fsc_per_mile_field']."'  style='width:60%;' readonly/><input type='hidden' name='fsc_new_column' id='fsc_new_column' class='form-control' value='".$code['fsc_column']."'/></td>
	";
	}
		echo '<div class="container-full" role="main">
			<form name="upload-exp" method="post" action="" enctype="multipart/form-data">
		<h3><img src="images/searchdate.png" alt="zone_icon" height="24"> FSC Schedule</h3>
		
'.($this->debug ? '<input name="debug" id="debug" type="hidden" value="true">
' : '').'
		<div class="well well-sm" style="margin-bottom: 2px;">
			<div class="row">
				<label for="fsc" class="col-sm-1 control-label">FSC #</label>
				<div class="col-sm-2">
					<input type="text" name="fsc" id="fsc" class="form-control" value="'.$code['records'][0]['FSC_UNIQUE_ID'].'" readonly >
				</div>
				<label for="fsc_name" class="col-sm-1 control-label">Name</label>
				<div class="col-sm-2">
					<input type="text" name="fsc_name" id="fsc_name" class="form-control" value="'.$code['records'][0]['FSC_NAME'].'">
				</div>
				<label for="fsc_average_price" class="col-sm-1 control-label">Ave&nbsp;Price</label>
				<div class="col-sm-1">
					<input type="text" name="fsc_average_price" id="fsc_average_price" class="form-control text-right" value="'.$code['records'][0]['AVERAGE_PRICE'].'" onkeyup="return chk_avg_price(this.value,&quot;'.$code['records'][0]['FSC_UNIQUE_ID'].'&quot;);">
				</div>
			';
		if($code['fsc_per_mile_field']!="" && $code['fsc_column']!="") {
			echo '
				<label for="fsc_new_field" class="col-sm-2 control-label">'.$code['fsc_column'] .'</label>
				<div class="col-sm-1">
					<input type="text" name="fsc_new_field" id="fsc_new_field" class="form-control text-right" value="'.$code['fsc_per_mile_field'].'" readonly>
					<input type="hidden" name="fsc_new_column" id="fsc_new_column" class="form-control" value="'.$code['fsc_column'].'">
				</div>
			';
		}
		
		//! SCR# 291 - deal with dates
		//! SCR# 304 - fix error from 291
		$date_format = $_SESSION['ios'] == 'true' ? "Y-m-d" : "m/d/Y";
		echo '
			</div>
			<div class="row">
				<label for="fsc_starting_date" class="col-sm-1 control-label">Starting</label>
				<div class="col-sm-2">
					<input type="'.($_SESSION['ios'] == 'true' ? 'date' : 'text').
					'" name="fsc_starting_date" id="fsc_starting_date" class="form-control'.($_SESSION['ios'] != 'true' ? ' date' : '').'" value="'.date($date_format,strtotime($code['records'][0]['STARTING_DATE'])).'">
				</div>
				<label for="fsc_end_date" class="col-sm-1 control-label">Ending</label>
				<div class="col-sm-2">
					<input type="'.($_SESSION['ios'] == 'true' ? 'date' : 'text').'" name="fsc_end_date" id="fsc_end_date" class="form-control'.($_SESSION['ios'] != 'true' ? ' date' : '').'" value="'.date($date_format,strtotime($code['records'][0]['FSC_ENDING_DATE'])).'">
				</div>
				<div class="col-sm-5 bg-info" style="padding-top: 5px;">
					If Ave&nbsp;Price = 0, selection is based on dates only, no history.
				</div>
			</div>
			<hr>
			<div class="row">
				<div class="col-sm-6">
					<div class="btn-group">
						<button type="submit" name="upload_schedule" id="upload_schedule"  class="btn btn-sm btn-success" onclick="return validte_schedule(&quot;edit&quot;);"><span class="glyphicon glyphicon-ok"></span>Update</button>
						<a class="btn btn-sm btn-default" href="exp_view_fsc.php?TYPE=all"><span class="glyphicon glyphicon-remove"></span> Back</a>
						<a class="btn btn-sm btn-success" href="exp_view_fsc.php?TYPE=addfsc&CODE='.$code['records'][0]['FSC_UNIQUE_ID'].'"><span class="glyphicon glyphicon-ok"></span> Add Manual Rate</a>
				
						<a class="btn btn-sm btn-default" href="exp_fsc_history.php?CODE='.$code['records'][0]['FSC_UNIQUE_ID'].'"><span class="glyphicon glyphicon-ok"></span> View FSC History</a>
					</div>
				</div>
				<div class="col-sm-6">
					<div class="col-sm-3">Upload CSV</div>
					<div class="col-sm-4">
						<input type="file" name="fsc_schedule" id="fsc_schedule" class="btn btn-sm">
					</div>
				</div>
			</div>
		</div>
		</form>
		';
		
		echo "<div class='table-responsive'>
		<table class='table table-striped table-condensed table-bordered table-hover' >
		<thead>
		<tr class='exspeedite-bg'>
		<th style='text-align:center;'><span class='text-muted'><span class='glyphicon glyphicon-unchecked'></span></span></th>
        <th style='text-align:center;'>Low Per Gallon</th>
		<th style='text-align:center;'>High Per Gallon</th>
		<th style='text-align:center;'>Flat Amount</th>
		<th style='text-align:center;'>Per Mile Adjust</th>
		<th style='text-align:center;'>Percent(%)</th>
		<th style='text-align:center;'>Start Date</th>
		<th style='text-align:center;'>End Date</th>
        </tr>
		</thead>
		$result
		</table>
		</div>

</div>";
	}
	
	##----- manage rate profile at admin side---------##
	public function render_profile_rate_screen($result)
	{
		$tab='<tbody>';
		if(count($result['profile'])>0)
		{
			$i=1;
			foreach($result['profile'] as $pro)
			{if($i<10){$i='0'.$i;}
				$tab.="<tr style='cursor:pointer;' >
       						 <td style='text-align:center;'>".$i."</td>
      						 <td style='text-align:center;'>".$pro['CONTRACT_ID']."</td>
							  <td style='text-align:center;'>".stripslashes(ucfirst($pro['PROFILE_NAME']))."</td>
      					     <td style='text-align:center;' >
					<div >
					<div class='btn-group btn-group-xs'>
                    <a href='exp_rate_profile.php?TYPE=edit&CODE=".$pro['CONTRACT_ID']."' class='btn btn-default btn-xs inform'  id=edit_".$pro['CONTRACT_ID']." data-placement='bottom' data-toggle='popover' data-content='Edit rate profile ".$pro['CONTRACT_ID']."'> <span style='font-size: 14px;'><span class='glyphicon glyphicon-edit'></span></span></a>
					
					
                    
                    <a class='btn btn-default btn-xs confirm'  id=del_".$pro['CONTRACT_ID']." data-placement='bottom' data-toggle='popover' data-content='<a id=&quot;".$pro['CONTRACT_ID']."_confirm&quot; class=&quot;btn btn-danger btn-sm&quot; href=&quot;javascript:void(0);&quot; onclick=&quot;return delete_profile(".$pro['CONTRACT_ID'].");&quot;><span class=&quot;glyphicon glyphicon-trash&quot;></span> Delete rate profile ".$pro['CONTRACT_ID']."</a><br>There is no undo'><span style='font-size: 14px;'><span class='glyphicon glyphicon-trash'></span></span></a>
					
					 <a href='javascript:void(0);' class='btn btn-success'  id=edit_".$pro['CONTRACT_ID']." data-placement='bottom' data-toggle='modal' data-target='#modal_editprofile".$pro['CONTRACT_ID']."'> <span style='font-size: 14px;'><span class='glyphicon glyphicon-edit'></span></span></a>
					</div>
					
					
						</div>
					</td>
      						</tr>
							<tr>
							<td>
							 <div class='modal fade in' id='modal_editprofile".$pro['CONTRACT_ID']."' tabindex='-1' role='dialog'>

<div class='modal-dialog '>
<div class='modal-content form-horizontal' role='main'>
<div class='modal-header'>
<h4 class='modal-title'>Edit Profile Name</h4>
</div>
<div class='modal-body' style='font-size: 14px; body:inherit;'>
<form name='add_profile_name' id='add_profile_name' action='exp_rate_profile.php' method='post'>
<div class='btn-group'>
<a href='#' class='btn btn-md btn-default' data-dismiss='modal'><span class='glyphicon glyphicon-remove'></span>Close</a>
<input type='submit' name='edit_profile_name' id='edit_profile_name' class='btn btn-md btn-success' onclick='javascript:return check_profile_name(".$pro['PRO_ID'].");'  value='Save Changes'/>
</div>

<div class='form-group'>

		<input type='hidden' name='pro_prim_id' id='pro_prim_id' value='".$pro['PRO_ID']."'/>	
<div class='form-group'>
				<label for='Profile' class='col-sm-4 control-label'>Profile ID</label>
				<div class='col-sm-4'>
			   <input class='form-control' name='PROFILE_ID' id='PROFILE_ID".$pro['PRO_ID']."' type='text'  value='".$pro['CONTRACT_ID']."' readonly='readonly'>
				</div>
			</div>
	
<div class='form-group'>
				<label for='Profile' class='col-sm-4 control-label'>Profile Name</label>
				<div class='col-sm-4'>
			   <input class='form-control' name='PRO_NAME' id='PRO_NAME".$pro['PRO_ID']."' type='text' placeholder='Profile Name' value=\"".($pro['PROFILE_NAME'])."\">
				</div>
			</div>
			</div>
			</form>
</div>
<div class='modal-footer'>

</div>
</div><!-- /.modal-content -->
</div><!-- /.modal-dialog -->

</div>
							</td>
							</tr>";
		 $i++;}
		}
		else
		{
			$tab.="<tr style='cursor:pointer;' >
       						 <td style='text-align:center;' colspan='4'>NO RECORDS ARE AVAILABLE !</td>
							 </tr>";
		}
		$tab.='<tbody>';
		echo $str="<div class='container-full' role='main'>
		<h3><img src='images/rate-profile.png' alt='zone_icon' height='24'> Manage Rate Profile
		<div class='btn-group'>
	
		<a class='btn btn-sm btn-success' href='exp_save_rates.php?action=addprofile'><span class='glyphicon glyphicon-plus'></span> Add Rate Profile</a>
		<a class='btn btn-sm btn-default' href='index.php'><span class='glyphicon glyphicon-remove'></span> Back</a>
		</div>
		</h3>
		<div class='table-responsive'>
		<div id='reload_search_result' style='display:none;'><img src='images/loading.gif' border='1' style='position:absolute; margin-left:250px;margin-top:10px;'></div>
		<table class='table table-striped table-condensed table-bordered table-hover' style='width:60%' >
		<thead>
		<tr class='exspeedite-bg'>
        <th style='text-align:center;'>Serial No</th>
		<th style='text-align:center;'>Profile ID</th>
		<th style='text-align:center;'>Profile Name</th>
		<th style='text-align:center;'>Action</th>
        </tr>
		</thead>
		".$tab."
		</table>
		</div>

</div>";
	}
	
	##----- edit rate profile-------##
	public function render_edit_rate_profile($result)
	{
		$pro_rate='<tbody>';
		if(count($result['profile_rate'])>0)
		{
			foreach($result['profile_rate'] as $pr)
			{
				$pro_rate.='<tr style="cursor:pointer;">
				<td class="text-center"><a class="btn btn-default btn-xs" href="javascript:void(0);" onclick="javascript: return delete_assigned_rate('.$pr['RATE_PROFILE_ID'].','.$result['profile_id'].');"> <span style="font-size: 14px;">
				<span class="glyphicon glyphicon-trash"></span> </span> </a></td>
				<td style="text-align:center;">'.$pr['RATE_CODE'].'</td>
				<td style="text-align:center;">'.$pr['RATE_NAME'].'</td>
				<td style="text-align:center;">'.$pr['RATE_DESC'].'</td>
				<td style="text-align:center;">'.$pr['RATE_PER_MILES'].'</td>
				</tr>';
			}
		}
		else
		{
				$pro_rate.='<tr style="cursor:pointer;">
				<td class="text-center" colspan="5">NO RATES ARE ASSIGNED TO THS PROFILE !
				</td>
				</tr>';
		}
			$pro_rate.='</tbody>';
			
			$manual='';
			if(count($result['profile_manual_rate'])>0)
			{
				foreach($result['profile_manual_rate'] as $man)
				{
					$manual.="<tr>
									<td>
									<a class='btn btn-default btn-xs' onclick='javascript: return delete_profile_manual(".$man['PRO_MANUAL_ID'].",".$result['profile_id'].");'><span class='glyphicon glyphicon-trash'></span>
									</a>
									<a class='btn btn-default btn-xs' data-target='#myModal_manual".$man['PRO_MANUAL_ID']."' data-toggle='modal'><span class='glyphicon glyphicon-edit'></span>
									</a>
									</td>
									<td style='font-size:12px; font-family:Arial, Helvetica, sans-serif; text-transform:uppercase; padding:0 6px;'>Code</td>
									<td><input type='text' name='textfield' id='textfield' size='15' class='form-control' value=".$man['PRO_RATE_CODE']." readonly='readonly'></td>
									<td style='font-size:12px; font-family:Arial, Helvetica, sans-serif; text-transform:uppercase; padding:0 6px;'>Name</td>
									<td><input type='text' name='textfield' id='textfield' size='15' class='form-control' value=\"".$man['PRO_RATE_NAME']."\" readonly='readonly'></td>
									<td style='font-size:12px; font-family:Arial, Helvetica, sans-serif; text-transform:uppercase; padding:0 6px;'>Description</td>
									<td><input type='text' name='textfield' id='textfield' size='15' class='form-control' value=\"".$man['PRO_RATE_DESC']."\" readonly='readonly'></td>
									<td style='font-size:12px; font-family:Arial, Helvetica, sans-serif; text-transform:uppercase; padding:0 6px;'>Rates</td>
									<td><input type='text' name='textfield' id='textfield' size='15' class='form-control' value='".$man['PRO_RATE']."' readonly='readonly'></td>
									<td style='font-size:12px; font-family:Arial, Helvetica, sans-serif; text-transform:uppercase; padding:0 6px;'>&nbsp;</td>
									</tr>
									<tr>
									<td style='font-size:12px; font-family:Arial, Helvetica, sans-serif; text-transform:uppercase; padding:0 6px;' colspan='6'>&nbsp;</td>
									</tr>
									<tr>
									<td>
									  <div class='modal fade in' id='myModal_manual".$man['PRO_MANUAL_ID']."' tabindex='-1' role='dialog'>

<div class='modal-dialog'>
<div class='modal-content form-horizontal' role='main'>
<div class='modal-header'>
<h4 class='modal-title'>Edit Manual Rate</h4>
</div>
<div class='modal-body' style='font-size: 14px; body:inherit;'>
<form name='form_edit_manual' id='form_edit_manual' action='exp_rate_profile.php?TYPE=edit&CODE=".$result['profile_id']."' method='post'>
<div class='btn-group'>
<a href='#' class='btn btn-md btn-default' data-dismiss='modal'><span class='glyphicon glyphicon-remove'></span>Close</a>
<input type='submit' name='edt_man_rate' id='edt_man_rate' class='btn btn-md btn-success' onclick='javascript:return check_edit_profile(".$man['PRO_MANUAL_ID'].");'  value='Save Changes'/>
</div>

<div class='form-group'>
<input class='form-control' name='ed_man_id' id='ed_man_id' type='hidden' placeholder='Manual Code' value='".$man['PRO_MANUAL_ID']."'>
		<div class='form-group'>
				<label for='Profile' class='col-sm-4 control-label'>CODE</label>
				<div class='col-sm-4'>
			   <input class='form-control' name='ed_man_code' id='ed_man_code".$man['PRO_MANUAL_ID']."' type='text' placeholder='Manual Code' value='".$man['PRO_RATE_CODE']."' readonly='readonly'>
				</div>
			</div>
            
            <div class='form-group'>
				<label for='Name' class='col-sm-4 control-label'> Name</label>
				<div class='col-sm-4'>
			   <input class='form-control' name='ed_man_name' id='ed_man_name".$man['PRO_MANUAL_ID']."' type='text' placeholder=' Name' value=\"".$man['PRO_RATE_NAME']."\">
				</div>
			</div>
            
            <div class='form-group'>
				<label for='Description' class='col-sm-4 control-label'>Description</label>
				<div class='col-sm-4'>
			   <input class='form-control' name='ed_man_desc' id='ed_man_desc".$man['PRO_MANUAL_ID']."' type='text' placeholder='Description' value=\"".$man['PRO_RATE_DESC']."\" >
				</div>
			</div>	

	
<div class='form-group'>
				<label for='Rates' class='col-sm-4 control-label'>Rates</label>
				<div class='col-sm-4'>
			   <input class='form-control' name='ed_man_rate' id='ed_man_rate".$man['PRO_MANUAL_ID']."' type='text' placeholder='Manual Rate' value='".$man['PRO_RATE']."'>
				</div>
			</div>
			</div>
			</form>
</div>
<div class='modal-footer'>

</div>
</div><!-- /.modal-content -->
</div><!-- /.modal-dialog -->

</div>

									</td>
									</tr>";
				}
			}
			else
			{
				$manual.="<tr>
									<td colspan='9'>NO MANUAL RATES ARE ADDED FOR THIS PROFILE !</td>
								</tr>";
			}
			
			$range='';
		
			if(count($result['profile_range'])>0)
			{
				foreach($result['profile_range'] as $ran)
				{$pro_frm_zone='';$pro_to_zone='';
				if(count($result['zones'])>0)
				{
					
					foreach($result['zones'] as $fzon)
					{$sel='';
						if($fzon['ZF_NAME']==$ran['PRO_ZONE_FROM'])
						{$sel='selected="selected"';}
						$pro_frm_zone.='<option value="'.$fzon['ZF_NAME'].'" '.$sel.'>'.$fzon['ZF_NAME'].'</option>';
					}
					
					
					foreach($result['zones'] as $tzon)
					{$to_sel='';
						if($tzon['ZF_NAME']==$ran['PRO_ZONE_TO'])
						{$to_sel='selected="selected"';}
						$pro_to_zone.='<option value="'.$tzon['ZF_NAME'].'" '.$to_sel.' >'.$tzon['ZF_NAME'].'</option>';
					}
				}
					$range.=' <tr>
										<td><a class="btn btn-default btn-xs " href="javascript:void(0);" onclick="javascript: return delete_profile_range('.$ran['PRO_RANGE_ID'].','.$result['profile_id'].');" ><span class="glyphicon glyphicon-trash"></span></a>
										
										<a class="btn btn-default btn-xs " href="javascript:void(0);" data-toggle="modal" data-target="#myModal_range'.$ran['PRO_RANGE_ID'].'" ><span class="glyphicon glyphicon-edit"></span></a>
										</td>
										<td style="font-size:12px; font-family:Arial, Helvetica, sans-serif; text-transform:uppercase; padding:0 6px;">CODE</td>
										<td><input type="text" name="textfield" id="textfield" size="15" class="form-control" value='.$ran['PRO_RANGE_CODE'].' readonly="readonly"></td>
										<td style="font-size:12px; font-family:Arial, Helvetica, sans-serif; text-transform:uppercase; padding:0 6px;">NAME</td>
										<td><input type="text" name="textfield" id="textfield" size="15" class="form-control" value="'.$ran['PRO_RANGE_NAME'].'" readonly="readonly"></td>
										<td style="font-size:12px; font-family:Arial, Helvetica, sans-serif; text-transform:uppercase; padding:0 6px;">FROM</td>
										<td><input type="text" name="textfield" id="textfield" size="15" class="form-control" value='.$ran['PRO_RANGE_FROM'].' readonly="readonly"></td>
										<td style="font-size:12px; font-family:Arial, Helvetica, sans-serif; text-transform:uppercase; padding:0 6px;">TO</td>
										<td><input type="text" name="textfield" id="textfield" size="15" class="form-control" value='.$ran['PRO_RANGE_TO'].' readonly="readonly"></td>
										<td style="font-size:12px; font-family:Arial, Helvetica, sans-serif; text-transform:uppercase; padding:0 6px;">FROM ZONE</td>
										<td><input type="text" name="textfield" id="textfield" size="15" class="form-control" value="'.$ran['PRO_ZONE_FROM'].'" readonly="readonly"></td>
										<td style="font-size:12px; font-family:Arial, Helvetica, sans-serif; text-transform:uppercase; padding:0 6px;">TO ZONE</td>
										<td><input type="text" name="textfield" id="textfield" size="15" class="form-control" value="'.$ran['PRO_ZONE_TO'].'" readonly="readonly"></td>
										<td style="font-size:12px; font-family:Arial, Helvetica, sans-serif; text-transform:uppercase; padding:0 6px;">Rates</td>
										<td><input type="text" name="textfield" id="textfield" size="15" class="form-control" value='.$ran['PRO_RANGE_RATE'].' readonly="readonly"></td>
										<td style="font-size:12px; font-family:Arial, Helvetica, sans-serif; text-transform:uppercase; padding:0 6px;">&nbsp;</td>
										</tr>
										<tr>
										<td>
										  <div class="modal fade in" id="myModal_range'.$ran['PRO_RANGE_ID'].'" tabindex="-1" role="dialog">

<div class="modal-dialog">
<div class="modal-content form-horizontal" role="main">
<div class="modal-header">
<h4 class="modal-title">Edit Range Rate</h4>
</div>
<div class="modal-body" style="font-size: 14px; body:inherit;">
<form name="form_edit_range" id="form_edit_range'.$ran['PRO_RANGE_ID'].'" action="exp_rate_profile.php?TYPE=edit&CODE='.$result['profile_id'].'" method="post">
<div class="btn-group">
<a href="#" class="btn btn-md btn-default" data-dismiss="modal"><span class="glyphicon glyphicon-remove"></span>Close</a>
<input type="submit" name="edit_range_rate" id="edit_range_rate" class="btn btn-md btn-success" onclick="javascript:return check_profile_range('.$ran['PRO_RANGE_ID'].');"  value="Save Changes"/>
</div>
<input type="hidden" name="ed_pro_range_id" id="ed_pro_range_id" value="'.$ran['PRO_RANGE_ID'].'"/>
<div class="form-group">
<input class="form-control" name="ed_pro_id" id="ed_pro_id" type="hidden"  value="'.$result['profile_id'].'">
<div id="edit_loader" style="display:none;"><img border="1" style="position:absolute; margin-left:250px;margin-top:10px;" src="images/loading.gif"></div>
		<div class="form-group">
				<label for="Profile" class="col-sm-4 control-label">CODE</label>
				<div class="col-sm-4">
			   <input class="form-control" name="ed_ran_code" id="ed_ran_code'.$ran['PRO_RANGE_ID'].'" type="text" placeholder="Manual Code" value="'.$ran['PRO_RANGE_CODE'].'" readonly="readonly">
				</div>
			</div>
            
            <div class="form-group">
				<label for="Name" class="col-sm-4 control-label"> Name</label>
				<div class="col-sm-4">
			   <input class="form-control" name="ed_ran_name" id="ed_ran_name'.$ran['PRO_RANGE_ID'].'" type="text" placeholder=" Name" value="'.$ran['PRO_RANGE_NAME'].'">
				</div>
			</div>
            
            <div class="form-group">
				<label for="Description" class="col-sm-4 control-label">From</label>
				<div class="col-sm-4">
			  <input class="form-control" name="ed_ran_frm" id="ed_ran_frm'.$ran['PRO_RANGE_ID'].'" type="text" placeholder=" From Range" value="'.$ran['PRO_RANGE_FROM'].'" >
				</div>
			</div>	
            
               <div class="form-group">
				<label for="Description" class="col-sm-4 control-label">To</label>
				<div class="col-sm-4">
			    <input class="form-control" name="ed_ran_to" id="ed_ran_to'.$ran['PRO_RANGE_ID'].'" type="text" placeholder=" To Range" value="'.$ran['PRO_RANGE_TO'].'">
				</div>
			</div>	
            
               <div class="form-group">
				<label for="Description" class="col-sm-4 control-label">From Zone</label>
				<div class="col-sm-4">
			   <select name="ed_ran_frm_zone" id="ed_ran_frm_zone'.$ran['PRO_RANGE_ID'].'" class="form-control">
               <option value="">From</option>
              '.$pro_frm_zone.'
               </select>
				</div>
			</div>	
            
              <div class="form-group">
				<label for="Description" class="col-sm-4 control-label">To Zone</label>
				<div class="col-sm-4">
			   <select name="ed_ran_to_zone" id="ed_ran_to_zone'.$ran['PRO_RANGE_ID'].'" class="form-control">
               <option value="">To</option>
               '.$pro_to_zone.'
               </select>
				</div>
			</div>	

	
<div class="form-group">
				<label for="Rates" class="col-sm-4 control-label">Rates</label>
				<div class="col-sm-4">
			   <input class="form-control" name="ed_ran_rate" id="ed_ran_rate'.$ran['PRO_RANGE_ID'].'" type="text" placeholder="Range Rate" value="'.$ran['PRO_RANGE_RATE'].'" >
				</div>
			</div>
			</div>
			</form>
</div>
<div class="modal-footer">

</div>
</div><!-- /.modal-content -->
</div><!-- /.modal-dialog -->

</div>

										</td>
										</tr>
																	
									<tr>
										<td style="font-size:12px; font-family:Arial, Helvetica, sans-serif; text-transform:uppercase; padding:0 6px;" colspan="6">&nbsp;</td>
									</tr>';
				}
			}
			else
			{
				$range.=' <tr>
								<td>NO RANGE OF RATES ARE AVAILABLE FOR THIS PROFILE !</td>
								</tr>';
			}
		$rate_str='<tbody>';
		
		if(count($result['driver_rates']) >0)
		{
				foreach($result['driver_rates'] as $row)
				{
					$rate_str.="
					<tr style='cursor:pointer;' >
					<td class='text-center'><input type='checkbox'  name='rate_ids[]' value=".$row['RATE_ID']."></td>
					<td style='text-align:center;'>".$row['CATEGORY_NAME']."</td>
					<td style='text-align:center;'>".$row['RATE_CODE']."</td>
					<td style='text-align:center;'>".$row['RATE_NAME']."</td>
					<td style='text-align:center;'>".$row['RATE_DESC']."</td>
					<td style='text-align:center;'>$  ".$row['RATE_PER_MILES']."</td>
					 <td style='text-align:center;'>".($row['RATE_BONUS'] ? '<span class="glyphicon glyphicon-check"></span>' : '<span class="text-muted"><span class="glyphicon glyphicon-unchecked"></span>')."</td>
					 <td style='text-align:center;'>".$row['ZONES']."</td>
				  </tr>	";
				}
		}
		else
		{
			$rate_str.="
					<tr style='cursor:pointer;' >
					<td class='text-center' colspan='8'>NO DRIVER RATES ARE AVAILABLE !
					</td>
					</tr>";
		}
		$rate_str.='</tbody>';
		
		$cat_str='';
		if(count($result['category'])>0)
		{
			foreach($result['category'] as $cat)
			{
				$cat_str.="<option value='".$cat['CATEGORY_CODE']."'>".$cat['CATEGORY_NAME']."</option>";
			}
		}
		
		$zone_str='';
		if(is_array($result['zones']) && count($result['zones'])>0)
		{
			foreach($result['zones'] as $zon)
			{
				$zone_str.="<option value='".$zon['ZF_NAME']."'>".$zon['ZF_NAME']."</option>";
			}
		}
		
		echo $str="<div class='container-full' role='main'>
     			  <div class='container-full' role='main'>
    			 <h3> <img src='images/driver_icon.png' alt='driver_icon' height='24' > &nbsp; Rate Profile 
				 <div class='btn-group'>
				<a class='btn btn-sm btn-default' href='exp_rate_profile.php'><span class='glyphicon glyphicon-remove'></span> Back</a>
		</div></h3>
   			   </div>
  <div class='md_row'>
    <div  id='driver_rate_div'>
      <div class='well  well-md'>
        <div class='table-responsive' style='overflow:auto;'>
          <h4><div style='float:left; '><img height='16' alt='tractor_icon' src='images/driver_icon.png'> Profile ID : ".$result['profile_id']." &nbsp;&nbsp;&nbsp; Profile Name:".ucfirst($result['profile_name'])."</div> <br/>
		  <div id='reload_drivers_rates_div' style='position: absolute;display:none;'><img src='images/loading.gif' border='1' style='position:absolute; margin-left:250px;margin-top:10px;'></div></h4>
		
          <table class='table table-striped table-condensed table-bordered table-hover'>
           <thead>
              <tr class='exspeedite-bg'>
                <th class='text-center'><span class='text-muted'><span class='glyphicon glyphicon-unchecked'></span></span></th>
				<th class='text-center'>Rate Code</th>
				<th class='text-center'>Rate Name</th>
                <th class='text-center'>Description</th>
                <th class='text-center'>Rate</th>
             
              </tr>
            </thead>
            ".$pro_rate."
          </table>
        
          <h4>Add Manual Rates : <a class='btn btn-sm btn-success' href='javascript:void(0);' onclick='javascript: return add_more_fun(&quot;add_manual_rates&quot;);'><span class='glyphicon glyphicon-plus'></span> Add </a><div id='reload_manual_rates_div' style='display:none;'><img src='images/loading.gif' border='1' style='position:absolute; margin-left:250px;margin-top:10px;'></div></h4>
		  
		   <table width='100%' border='1' cellspacing='1' cellpadding='0' class='table table-striped table-condensed table-bordered table-hover'>
            <tr>
              <td style='padding:10px; '>
			  <table width='100%' border='0' cellspacing='0' cellpadding='0'>			  
							 
							 ".$manual."
																		
                </table></td>
            </tr>
            <tr>
              <td>
			  <table width='100%' border='0' cellspacing='0' cellpadding='0' style='margin:5px;display:none;'  id='add_manual_rates'>
			  <input type='hidden' name='profile_id' id='profile_id' size='15' class='form-control' value='".$result['profile_id']."' />
                  <tr>
                    <td style='font-size:12px; font-family:Arial, Helvetica, sans-serif; text-transform:uppercase; padding:0 6px;'>Code</td>
                    <td><input type='text' name='manual_code' id='manual_code' size='15' class='form-control' value='".$result['max_profile_manual_rate']."' readonly='readonly'></td>
					<td style='font-size:12px; font-family:Arial, Helvetica, sans-serif; text-transform:uppercase; padding:0 6px;'>Name</td>
                    <td><input type='text' name='manual_name' id='manual_name' size='15' class='form-control'></td>
                    <td style='font-size:12px; font-family:Arial, Helvetica, sans-serif; text-transform:uppercase; padding:0 6px;'>Description</td>
                    <td><input type='text' name='manual_desc' id='manual_desc' size='15' class='form-control'></td>
                    <td style='font-size:12px; font-family:Arial, Helvetica, sans-serif; text-transform:uppercase; padding:0 6px;'>Rates</td>
                    <td><input type='text' name='manual_rate' id='manual_rate' size='15' class='form-control'></td>
					<td style='font-size:12px; font-family:Arial, Helvetica, sans-serif; text-transform:uppercase; padding:0 6px;'>&nbsp;</td>
                    <td><input name='save_manual_rate' id='save_manual_rate' type='button' class='btn btn-md btn-default' value='Submit' onclick='javascript:return add_manual_rates();'></td>
                  </tr>
                </table></td>
            </tr>
          </table>
		  <h4>Range of rates :  <a class='btn btn-sm btn-success' href='javascript:void(0);' onclick='javascript: return add_more_fun(&quot;add_range_rates&quot;);' ><span class='glyphicon glyphicon-plus'></span>
		   Add </a> <div id='reload_range_rates_div' style='display:none;'><img src='images/loading.gif' border='1' style='position:absolute; margin-left:250px;margin-top:10px;'></div></h4>
          <table width='100%' border='1' cellspacing='1' cellpadding='0' class='table table-striped table-condensed table-bordered table-hover'>
            <tr>
              <td style='padding:10px;'>
			  <table width='100%' border='0' cellspacing='0' cellpadding='0'>
			  ".$range."
                </table>
				</td>
            </tr>            
            <tr>
              <td><table width='100%' border='0' cellspacing='0' cellpadding='0' style='margin:5px;display:none;'   id='add_range_rates'>
			  	
                  <tr>
				    <td style='font-size:12px; font-family:Arial, Helvetica, sans-serif; text-transform:uppercase; padding:0 6px;'>Code</td>
				 	 <td> <input type='text' name='range_rate_code' id='range_rate_code' size='15' class='form-control' value='".$result['max_range']."' readonly='readonly' /></td>				  
					 <td style='font-size:12px; font-family:Arial, Helvetica, sans-serif; text-transform:uppercase; padding:0 6px;'>Name</td>
                    <td><input type='text' name='range_name' id='range_name' size='15' class='form-control' value=''></td>
                    <td style='font-size:12px; font-family:Arial, Helvetica, sans-serif; text-transform:uppercase; padding:0 6px;'>From</td>
                    <td><input type='text' name='range_from' id='range_from' size='15' class='form-control' value=''></td>
                    <td style='font-size:12px; font-family:Arial, Helvetica, sans-serif; text-transform:uppercase; padding:0 6px;'>To</td>
                    <td><input type='text' name='range_to' id='range_to' size='15' class='form-control' value=''></td>
					<td style='font-size:12px; font-family:Arial, Helvetica, sans-serif; text-transform:uppercase; padding:0 6px;'>From Zone</td>
                    <td>	<select name='zone_from' id='zone_from' class='form-control'>
							<option value=''>From</option>
							".$zone_str."
							</select>
					</td>
					<td style='font-size:12px; font-family:Arial, Helvetica, sans-serif; text-transform:uppercase; padding:0 6px;'>To Zone</td>
                    <td><select name='zone_to' id='zone_to' class='form-control'>
							<option value=''>To</option>
							".$zone_str."
							</select>
					</td>
                    <td style='font-size:12px; font-family:Arial, Helvetica, sans-serif; text-transform:uppercase; padding:0 6px;'>Rates</td>
                    <td><input type='text' name='range_rate' id='range_rate' size='15' class='form-control' value=''></td>
					<td style='font-size:12px; font-family:Arial, Helvetica, sans-serif; text-transform:uppercase; padding:0 6px;'>&nbsp;</td>
                    <td><input name='save_range_rates'  id='save_range_rates' type='button' class='btn btn-md btn-default' value='Submit' onclick='javascript: return add_rate_range();'></td>
                  </tr>
                </table></td>
            </tr>
          </table>
        </div>
      </div>
    </div>
    <div style='clear:both'></div>
  </div>
  
  
  <div class='table-responsive'>
 <h3><img height='29' src='images/money_bag.png' alt='tractor_icon'> Select Driver Rates : </h3>
  <form name='' id='' action='exp_rate_profile.php?TYPE=edit&CODE=".$result['profile_id']."' method='post'>
  <div class='btn-group' style='margin-bottom:20px;'>
   <select class='form-control' name='category' id='category' style='float:left;width:200px; margin-right:20px;' onchange='javascript: return get_search_records(this.value);'> 
			<option value=''>	ALL	</option>	
			".$cat_str."
	</select>
		<button class='btn btn-md btn-success' type='submit' name='saverate'>
		<span class='glyphicon glyphicon-ok'></span>
		Apply Rates To Driver
		</button></div>
		<div style='float:right;'><a class='btn btn-sm btn-success'  href='exp_adddriverrate.php'><span class='glyphicon glyphicon-plus'></span>Add Driver Rate</a></div>
<br> 
<div id='reload_search_result' style='display:none;'><img src='images/loading.gif' border='1' style='position:absolute; margin-left:250px;margin-top:10px;'></div>
<div id='search_reload'>

		<table class='table table-striped table-condensed table-bordered table-hover' >
            <thead>
              <tr class='exspeedite-bg'>
                <td class='text-center'><span class='text-muted'><span class='glyphicon glyphicon-unchecked'></span></span></td>
				 <th class='text-center'>Rate Category</th>
				 <th class='text-center'>Rate Code</th>
				 <th class='text-center'>Rate Name</th>
				 <th class='text-center'>Rate Description</th>
                <th class='text-center'>Rates</th>
				 <th class='text-center'>Bonus</th>
				 <th class='text-center'>Zones</th>
				
              </tr>
            </thead>
            ".$rate_str."   
          </table>
</div>
 
</form>
</div>
</div>";
	}

	//! EDI banner - for use in billing screens
	public function render_billing_edi_banner( $edit ) {
		if( isset($edit['shipment_details']["EDI_204_PRIMARY"]) &&
			intval($edit['shipment_details']["EDI_204_PRIMARY"]) == intval($edit['shipment_details']['SHIPMENT_CODE']) ) {
			$ls = array();
			foreach($edit['shipment_details']["EDI_SHIPMENTS"] as $s) {
				$ls[] = '<a href="exp_addshipment.php?CODE='.$s.'" class="alert-link">'.$s.'</a>';
			}
			
			echo '	<div class="alert alert-warning tighter" role="alert">
				<h4><span class="glyphicon glyphicon-info-sign"></span> EDI Shipment# '.
				$edit['shipment_details']["EDI_204_B204_SID"].' From '.
				$edit['shipment_details']["EDI_204_ORIGIN"].', '.plural(count($edit['shipment_details']["EDI_SHIPMENTS"]), 'Shipment').' ('.
				implode(', ', $ls).'), <span class="label label-success">'.
				$edit['shipment_details']["EDI_204_B206_PAYMENT"].'</span></h4>
				<div class="row">
					<div class="col-sm-4">
						<div class="col-sm-4"><strong>Weight:</strong></div>
						<div class="col-sm-8">'.$edit['shipment_details']["EDI_204_L301_WEIGHT"].' '.
				$edit['shipment_details']["EDI_204_L312_WQUAL"].'</div>
					</div>
					<div class="col-sm-4">
						<div class="col-sm-12"><strong>$ ';
						
						//! Penske uses EDI_204_L305_CHARGE
						if( ! empty($edit['shipment_details']["EDI_204_L305_CHARGE"]) &&
							$edit['shipment_details']["EDI_204_L305_CHARGE"] > 0 )
							echo number_format($edit['shipment_details']["EDI_204_L305_CHARGE"], 2,'.','');
						else
							echo number_format($edit['shipment_details']["EDI_204_L303_RATE"], 2,'.','').'</strong> '.
				$edit['shipment_details']["EDI_204_L304_RQUAL"];
				
					echo '</div>
					</div>
					<div class="col-sm-4">
						<div class="col-sm-4"><strong>Accepted:</strong></div>
						<div class="col-sm-8">'.date("m/d/Y h:i A", strtotime($edit['shipment_details']["EDI_990_SENT"])).'</div>
					</div>
				</div>
			</div>
			';
		} 
	}


##------ client pay screen -------##
	public function render_client_pay_screen($edit)
	{//echo '<pre/>';print_r($edit);

		$dec = $edit['FSC_4DIGIT'] ? 'step="0.0001"' : 'step="0.01"';
		$dec2 = $edit['FSC_4DIGIT'] ? 4 : 2;
		$item_list_table = sts_item_list::getInstance($this->table->database, $this->debug);

		$client_rate='';
		$client_total=0;
		if(count($edit['client_rates'])>0 && $edit['client_rates']!="")
		{//print_r($edit['client_rates']);
			foreach($edit['client_rates'] as $ed)
			{
				$client_rate.='<tr style="cursor:pointer;">
			  <td class="text-center">'.$ed['RATE_CODE'].'</td>
			   <td class="text-center">'.$ed['RATE_NAME'].'</td>
                <td class="text-center">'.$ed['CATEGORY_NAME'].'</td>
				<td class="text-center"><input type="text" name="RATE[]" id="RATE" value="'.$ed['RATE_PER_MILES'].'" onkeyup="return add_rates(this.value); "/></td>
				<td class="text-center"><input type="text" name="QTY[]" id="QTY" value="'.((float)$ed['RATE_PER_MILES']/35).'"/></td>
				</tr>
				<input type="hidden" name="CODE[]" value="'.$ed['RATE_CODE'].'" />
				<input type="hidden" name="RATE_NAME[]" value="'.$ed['RATE_NAME'].'" />
				<input type="hidden" name="CATEGORY[]" value="'.$ed['CATEGORY_NAME'].'" /> ';
				$client_total=$client_total+$ed['RATE_PER_MILES'];
			}
			$client_rate.='<tr class="exspeedite-bg"><td colspan="3" class="text-center">Total</td><td class="text-center" >$
			<input type="text" name="TOTAL_RATE" id="TOTAL_RATE" value="'.$client_total.'" style="color:#030303" onkeyup="return calculate_total();"  readonly="readonly" /> </td></tr>';
		}
		else
		{
			$client_rate.='<tr style="cursor:pointer;"><td class="text-center" colspan="5">NO RATES ARE ASSIGNED !</td></tr><tr class="exspeedite-bg"><td colspan="2" class="text-center">Total</td><td class="text-center">  $&nbsp;&nbsp;<input type="text" name="TOTAL_RATE" id="TOTAL_RATE" value="0" style="color:#030303" onkeyup="return calculate_total();" readonly="readonly"/></td></tr>';
		}
		$total=0.0;
		
		$total=floatval(is_array($edit['handling_rate_details']) && isset($edit['handling_rate_details']['INVOICE_COST']) ? $edit['handling_rate_details']['INVOICE_COST'] : 0 )
			+floatval(is_array($edit['frieght_charges']) && isset($edit['frieght_charges']['LINE_HAUL']) ? $edit['frieght_charges']['LINE_HAUL'] : 0 )
			+floatval(is_array($edit['client_detail']) && isset($edit['client_detail']['SO_RATE']) ? $edit['client_detail']['SO_RATE'] : 0 )
			+floatval(is_array($edit['freight_rate']) && isset($edit['freight_rate']['AMOUNT']) ? $edit['freight_rate']['AMOUNT'] : 0 )

			+(floatval(is_array($edit['shipment_details']) && isset($edit['shipment_details']['PALLETS']) ? $edit['shipment_details']['PALLETS'] : 0 )
			*floatval(is_array($edit['pallet_rate']) && isset($edit['pallet_rate']['PALLET_RATE']) ? $edit['pallet_rate']['PALLET_RATE'] : 0 ))
			
			+(floatval($edit['rpm'])*floatval($edit['milleage']))
			+floatval($client_total+$edit['unloaded_detention_Rate'])+floatval($edit['loaded_detention_Rate']);
	
		if($edit['fuel_rate']=='' && $edit['percent']!=0)
		{
			$edit['fuel_rate']=($total*floatval($edit['percent']))/100.0;
		}
		$total=$total+floatval($edit['fuel_rate']);
		
		$total=number_format($total,2, ".", "");
		if( $this->debug ) {
			echo "<pre>Duncan: check total\n";
			var_dump('INVOICE_COST', $edit['handling_rate_details']['INVOICE_COST'],
				'LINE_HAUL', $edit['frieght_charges']['LINE_HAUL'],
				'SO_RATE', $edit['client_detail']['SO_RATE'],
				'AMOUNT', $edit['freight_rate']['AMOUNT'],
				'PALLETS', $edit['shipment_details']['PALLETS'], 'PALLET_RATE', $edit['pallet_rate']['PALLET_RATE'],
				'rpm', $edit['rpm'], 'milleage', $edit['milleage'],
				'client_total', $client_total, 'unloaded_detention_Rate', $edit['unloaded_detention_Rate'], 'loaded_detention_Rate', $edit['loaded_detention_Rate'],
				'total', $total);
			echo "</pre>";
		}
		$check=$checked='';
		
		if(is_array($edit['client_detail']) && $edit['client_detail']['HAZMAT']=='1')
		{$check='checked="checked"';}
		if(is_array($edit['client_detail']) && $edit['client_detail']['TEMP_CONTROLLED']=='1')
		{$checked='checked="checked"';}
		$avg_rate="";
		if(isset($edit["avg_rate_field"]) && $edit["avg_rate_field"]!="" && $edit['avg_rate_val']!="") {
			$func="";
			if($edit["avg_rate_field"]=="Per Mile Adjust") {
				$func="onkeyup='return calculate_rpm_amountb();'";
			} else if($edit["avg_rate_field"]=="Flat Amount") {
				$func="onkeyup='return calculate_fuel_charge();'";
			}
			$avg_rate='<div class="form-group">
				<label for="FUEL_COST" class="col-sm-6 control-label">'.$edit["avg_rate_field"].'</label>
				<div class="col-sm-6">
				<input class="form-control text-right"  type="number" '.$dec.'   name="FUEL_AVG_RATE"  id="FUEL_AVG_RATE"    placeholder="Fuel Average Rate"  value="'.$edit['avg_rate_val'].'" '.$func.' />
				<input class="form-control"  type="hidden"   name="FUEL_AVG_RATE_COL"  id="FUEL_AVG_RATE_COL"      value="'.$edit['avg_rate_field'].'"  />
				</div>
				</div>';
		}
		if(isset($edit["avg_rate_field"]) && $edit["avg_rate_field"]!="") {
			echo '<input type="hidden" name="fsc_col_per_mile" id="fsc_col_per_mile" value="'.$edit["avg_rate_field"].'"/>' ;
		} else {
			echo '<input type="hidden" name="fsc_col_per_mile" id="fsc_col_per_mile" value=""/> ';
		}
		
		$per_pal_rate=0;
		if(isset($edit['pallet_rate']['PALLET_RATE']) && $edit['pallet_rate']['PALLET_RATE']!="")
		{$per_pal_rate=$edit['pallet_rate']['PALLET_RATE'];}
		$new_pallet_rate='0';
		if(isset($edit['shipment_details']['PALLETS']) && isset($edit['pallet_rate']['PALLET_RATE']) && $edit['shipment_details']['PALLETS']!="" && $edit['pallet_rate']['PALLET_RATE']!="")
		{$new_pallet_rate=number_format($edit['shipment_details']['PALLETS']*$edit['pallet_rate']['PALLET_RATE'],2, ".", "");}
		if($new_pallet_rate!="" && $new_pallet_rate!="0" && isset($edit['minmaxpalletrates']['PALLET_ID']))
		{//echo $new_pallet_rate; echo 'e'.$edit['minmaxpalletrates']['MAX_CHARGE'];
			$new_pallet_rate=str_replace(',', '', $new_pallet_rate);
			if(floatval($new_pallet_rate)<floatval($edit['minmaxpalletrates']['MIN_CHARGE']))
			{ //echo '222';
				$new_pallet_rate=number_format($edit['minmaxpalletrates']['MIN_CHARGE'],2, ".", "");
			}
			 else if(floatval($new_pallet_rate) > floatval($edit['minmaxpalletrates']['MAX_CHARGE']))
			{//echo '*ddd**'.$edit['minmaxpalletrates']['MAX_CHARGE'];  exit;
				$new_pallet_rate=number_format($edit['minmaxpalletrates']['MAX_CHARGE'],2, ".", "");
			}
			
		}
		//print_r($edit['shipment_details']);
		//! XYZZY
		echo '<div class="container-full theme-showcase" role="main">
<div class="well  well-md">
<form role="form" class="form-horizontal" action=""  method="post" enctype="multipart/form-data" name="clientpay" id="clientpay" autocomplete="off"><h3><img src="images/driver_icon.png" alt="driver_icon" height="24"> 
Enter Client Billing #'.$edit['shipment_details']['SHIPMENT_CODE'].
($edit['SS_NUMBER'] ? ' / '.$edit['SS_NUMBER'] : '').
' '.$this->render_currency_menu($edit["CURRENCY_CODE"]).
$item_list_table->render_terms_menu($edit['shipment_details']['DEFAULT_TERMS']).
'<button name="savebilling" type="submit" class="btn btn-sm btn-success" onclick="return chk_confirmation();">
		<span class="glyphicon glyphicon-ok"></span> Save Billing</button> 
';

//! Duncan - add shipment, approve button.
echo '
<a class="btn btn-sm btn-info" href="exp_addshipment.php?CODE='.$edit['shipment_details']['SHIPMENT_CODE'].'"><span class="glyphicon glyphicon-edit"></span> Shipment #'.$edit['shipment_details']['SHIPMENT_CODE'].'</a>';

if( $edit['status'] == 'Entered' ) {
	echo ' <a class="btn btn-sm btn-success" id="Ready_Dispatch" onclick="confirmation(\'Confirm: Ready Dispatch\',\'exp_assignshipment.php?CODE='.$edit['shipment_details']['SHIPMENT_CODE'].'&CSTATE=1\')" ><span class="glyphicon glyphicon-arrow-right"></span> Ready Dispatch</a>';
}
								
echo ' <a class="btn btn-sm btn-default" href="exp_listshipment.php"><span class="glyphicon glyphicon-remove"></span> Shipments</a>
			
			
	</h3>
	<div class="form-group tighter">';
	
	//! EDI banner
	$this->render_billing_edi_banner( $edit );
		
	echo '
		<div class="col-sm-4">
			<div class="form-group well well-sm tighter">
				<label class="col-sm-4 control-label"> LOAD# </label>
				<div class="col-sm-8 text-right">'.$edit['shipment_details']['LOAD_CODE'].'</div>
			</div>
		</div>
		<div class="col-sm-2">
			<div class="form-group well well-sm tighter">
				<label class="col-sm-4 control-label">#&nbsp;Picks</label>
				<div class="col-sm-8 text-right">'.$edit['no_of_picks'].'</div>
			</div>
		</div>
		<div class="col-sm-2">
			<div class="form-group well well-sm tighter">
				<label class="col-sm-4 control-label">#&nbsp;Drops</label>
				<div class="col-sm-8 text-right">'.$edit['no_of_drops'].'</div>
			</div>
		</div>
		<div class="col-sm-4">
			<div class="form-group well well-sm tighter">
				<label class="col-sm-4 control-label"> Business&nbsp;Code </label>
				<div class="col-sm-8">'.$edit['shipment_details']['BUSINESS_CODE_NAME'].'</div>
			</div>
		</div>
	</div>
    <div class="form-group">
		<div class="col-sm-4">
			<div class="form-group well well-sm">
				<label for="SHIPPER_NAME" class="col-sm-4 control-label">Shipper</label>
				<div class="col-sm-8">
				<input class="form-control" name="SHIPPER_NAME" id="SHIPPER_NAME" type="text" placeholder="Shipper" maxlength="50" value="'.$edit['shipment_details']['SHIPPER_NAME'].'" readonly>
				</div>
				<label for="SHIPPER_ADDR1" class="col-sm-4 control-label">Address</label>
				<div class="col-sm-8">
				<input class="form-control" name="SHIPPER_ADDR1" id="SHIPPER_ADDR1" type="text"  placeholder="Addr1" maxlength="50" value="'.$edit['shipment_details']['SHIPPER_ADDR1'].'" readonly>
				</div>
				<label for="BOL_NUMBER" class="col-sm-4 control-label">BOL#</label>
				<div class="col-sm-8">
					<input class="form-control" id="BOL_NUMBER" type="text"   value="'.$edit['shipment_details']['BOL_NUMBER'].'" onkeyup="return upd_bol_number('.$edit['shipment_details']['SHIPMENT_CODE'].');">
				
				</div>
			'.($edit['LOG_HOURS'] ? '	<label for="NOT_BILLED_HOURS" class="col-sm-4 control-label"><span class="glyphicon glyphicon-time"></span> Hours</label>
				<div class="col-sm-8">
					<input class="form-control text-right" name="NOT_BILLED_HOURS" id="NOT_BILLED_HOURS" type="text">
				</div>
			' : '').'</div>
		</div>
		<div class="col-sm-8">
			<div class="form-group">
				<div class="col-sm-6">
					<div class="form-group well well-sm tighter">
						<label for="CONS_NAME" class="col-sm-4 control-label">Consignee</label>
						<div class="col-sm-8">
						<input class="form-control" name="CONS_NAME" id="CONS_NAME" type="text"  placeholder="Consignee" maxlength="50" value="'.$edit['shipment_details']['CONS_NAME'].'" readonly>
						</div>
						<label for="CONS_ADDR1" class="col-sm-4 control-label">Address</label>
						<div class="col-sm-8">
						<input class="form-control" name="CONS_ADDR1" id="CONS_ADDR1" type="text"  placeholder="Addr1" maxlength="50" value="'.$edit['shipment_details']['CONS_ADDR1'].'" readonly>
						</div>
					</div>
				</div>
				<div class="col-sm-6">
					<div class="form-group well well-sm tighter">
						<label for="BILLTO_NAME" class="col-sm-4 control-label">Bill-To</label>
						<div class="col-sm-8">
						<input class="form-control" name="BILLTO_NAME" id="BILLTO_NAME" type="text"  placeholder="Bill-To" maxlength="50"   value="'.$edit['shipment_details']['BILLTO_NAME'].'" readonly>
						</div>
						<label for="BILLTO_ADDR1" class="col-sm-4 control-label">Address</label>
						<div class="col-sm-8">	
						<input class="form-control" name="BILLTO_ADDR1" id="BILLTO_ADDR1" type="text"  placeholder="Addr1" maxlength="50" value="'.$edit['shipment_details']['BILLTO_ADDR1'].'" readonly>
						</div>
					</div>
				</div>
				<div class="col-sm-12">
					<div class="form-group well well-sm tighter">
						<label for="NOTES" class="col-sm-2 control-label">Disp Notes</label>
						<div class="col-sm-10">
						<textarea name="NOTES" id="NOTES" class="form-control" style="resize=none;" readonly>'.$edit['shipment_details']['NOTES'].'</textarea>
						</div>
					</div>
				</div>
			</div>
		</div>
	</div>
	<div class="form-group">
		<div class="col-sm-4">
		<div class="form-group">
				<label for="CHARGES" class="col-sm-5 control-label">Pickup Appointment</label>
				<div class="col-sm-6">
				<input class="form-control"  type="text"   value="'.$edit['expected_pickup_time'].'" readonly="readonly">
				</div>
			</div>
			</div>
			<div class="col-sm-4">
			<div class="form-group">
				<label for="CHARGES" class="col-sm-5 control-label">Actual Arrival Time</label>
				<div class="col-sm-6">
				<input class="form-control"  type="text"   value="'.$edit['actual_pickup_time'].'" readonly="readonly">
				</div>
			</div>
			</div>
			<div class="col-sm-4">
			<div class="form-group">
				<label for="CHARGES" class="col-sm-5 control-label">Actual Departure Time</label>
				<div class="col-sm-6">
				<input class="form-control"  type="text"   value="'.$edit['actual_pickup_depart_time'].'" readonly="readonly">
				</div>
			</div>
			</div>
			</div>
			<div class="form-group">
			<div class="col-sm-4">
			<div class="form-group">
				<label for="CHARGES" class="col-sm-5 control-label">Delivery Appointment</label>
				<div class="col-sm-6">
				<input class="form-control"  type="text"   value="'.$edit['expected_drop_time'].'"  readonly="readonly">
				</div>
			</div>
			</div>
			<div class="col-sm-4">
			<div class="form-group">
				<label for="CHARGES" class="col-sm-5 control-label">Actual Arrival Time</label>
				<div class="col-sm-6">
				<input class="form-control"  type="text"   value="'.$edit['actual_drop_arrival_time'].'" readonly="readonly" >
				</div>
			</div>
		</div>
		<div class="col-sm-4">
			<div class="form-group">
				<label for="CHARGES" class="col-sm-5 control-label">Actual Departure Time</label>
				<div class="col-sm-6">
				<input class="form-control"  type="text"   value="'.$edit['actual_drop_time'].'" readonly="readonly" >
				</div>
			</div>
		</div>
		</div>
	<div class="form-group">
		<div class="col-sm-4">
			
            <div class="well well-sm"><!-- Duncan -->
			<div class="form-group">
				<label for="CHARGES" class="col-sm-6 control-label">Pallets</label>
				<div class="col-sm-6">
				<input class="form-control text-right" name="PALLETS" id="PALLETS" type="text"  placeholder="No.of pallets" value="'.$edit['shipment_details']['PALLETS'].'"  onkeyup="return cal_pallet_rate('.$edit['client_id'].',&quot;'.$edit['shipment_details']['CONS_NAME'].'&quot;,&quot;'.$edit['shipment_details']['CONS_CITY'].'&quot;);">
				</div>
			</div>
			
			<div class="form-group">
				<label for="CHARGES" class="col-sm-6 control-label">Per Pallet Rate</label>
				<div class="col-sm-6">
				<input class="form-control text-right" name="PER_PALLETS" id="PER_PALLETS" type="text"  placeholder="Per pallet rate" value="'.$per_pal_rate.'" onkeyup="return cal_pallet_per_rate('.$edit['client_id'].',&quot;'.$edit['shipment_details']['CONS_NAME'].'&quot;,&quot;'.$edit['shipment_details']['CONS_CITY'].'&quot;);" >
				</div>
			</div>
			
				<div class="form-group">
				<label for="CHARGES" class="col-sm-6 control-label">Pallet Total</label>
				<div class="col-sm-6">
				<div class="input-group">
				<span class="input-group-addon">$</span>
				<input class="form-control text-right" name="PALLETS_RATE" id="PALLETS_RATE" type="text"  placeholder="Pallet Rate" value="'.$new_pallet_rate.'" onkeyup="return calculate_total();">
				</div>
				</div>
			</div>
			
			<div class="form-group">
				<label for="CHARGES" class="col-sm-6 control-label">Handling Pallets</label>
				<div class="col-sm-6">
				<div class="input-group">
				<span class="input-group-addon">$</span>
				<input class="form-control text-right" name="HAND_PALLET" id="HAND_PALLET" type="text"  value="" onkeyup="return calculate_total();">
				</div>
				</div>
			</div>
			</div><!-- Duncan -->
			
			<div class="well well-sm"><!-- Duncan -->
			<div class="form-group">
				<label for="CHARGES" class="col-sm-6 control-label">Handling Charges</label>
				<div class="col-sm-6">
				<div class="input-group">
				<span class="input-group-addon">$</span>
				<input class="form-control text-right" name="HAND_CHARGES" id="HAND_CHARGES" type="text"  value="'.(is_array($edit['handling_rate_details']) && isset($edit['handling_rate_details']['INVOICE_COST']) ? $edit['handling_rate_details']['INVOICE_COST'] : 0).'" onkeyup="return calculate_total();">
				</div>
				</div>
			</div>
			
			<div class="form-group">
				<label for="CHARGES" class="col-sm-6 control-label">Freight Charges</label>
				<div class="col-sm-6">
				<div class="input-group">
				<span class="input-group-addon">$</span>
				<input class="form-control text-right" name="FREIGHT_CHARGES" id="FREIGHT_CHARGES" type="text" onkeyup="return calculate_total();" >
				</div>
				</div>
			</div>
			</div><!-- Duncan -->
			
			<div class="well well-sm"><!-- Duncan -->
			'.(! empty($edit['EQUIPMENT']) ? '<div class="form-group">
				<label for="EQUIPMENT" class="col-sm-5 control-label">Equipment</label>
				<div class="col-sm-6 bg-warning">'.$edit['EQUIPMENT'].'</div>
				</div>' : '').'
            <div class="form-group">
				<label for="EXTRA_CHARGES" class="col-sm-5 control-label">Extra Charges</label>
				<div class="col-sm-7">
				<div class="input-group">
				<span class="input-group-addon">$</span>
				<input class="form-control text-right" name="EXTRA_CHARGES" id="EXTRA_CHARGES" type="text"  value="'.(is_array($edit['frieght_charges']) && isset($edit['frieght_charges']['LINE_HAUL']) ? $edit['frieght_charges']['LINE_HAUL'] : 0).'"
				placeholder="Extra Charges" onkeyup="return calculate_total();" >
				</div>
				</div>
			</div>

			<div class="form-group">
				<label for="EXTRA_CHARGES_NOTE" class="col-sm-5 control-label">Description</label>
				<div class="col-sm-7">
				
				<textarea name="EXTRA_CHARGES_NOTE" id="EXTRA_CHARGES_NOTE" class="form-control" style="resize=none;"></textarea>
				</div>
			</div>
			</div>
			
			<div class="well well-sm "><!-- Duncan -->
			<div class="form-group">
				<label for="STOP_OFF" class="col-sm-5 control-label">Stop Off</label>
				<div class="col-sm-7">
				<div class="input-group">
				<span class="input-group-addon">$</span>
				<input class="form-control text-right" name="STOP_OFF" id="STOP_OFF" type="text"  value=""
				placeholder="Stop Off" onkeyup="return calculate_total();" >
				</div>
				</div>
			</div>
			
			<div class="form-group">
				<label for="STOP_OFF_NOTE" class="col-sm-5 control-label">Notes</label>
				<div class="col-sm-7">
				
				<textarea name="STOP_OFF_NOTE" id="STOP_OFF_NOTE" class="form-control" style="resize=none;"></textarea>
				</div>
			</div>
			</div>
			
			<div class="form-group">
				<label for="EXTRA_CHARGES" class="col-sm-6 control-label">Weekend/Holiday</label>
				<div class="col-sm-6">
				<div class="input-group">
				<span class="input-group-addon">$</span>
				<input class="form-control text-right" name="WEEKEND" id="WEEKEND" type="text"  value=""
				placeholder="Weekend/Holiday" onkeyup="return calculate_total();" >
				</div>
				</div>
			</div>
            </div>
            <div class="col-sm-4">
			
            <div class="well well-sm"><!-- Duncan -->
			<div id="det_loader" style="display:none;"><img border="1" style="position:absolute; margin-left:250px;margin-top:10px;" src="images/loading.gif"></div>
			<h4>Loading Detention</h4>
			<div class="form-group">
				<label for="CHARGES" class="col-sm-6 control-label">Free Hours</label>
				<div class="col-sm-6">
				<input class="form-control text-right" name="FREE_DETENTION_HOUR" id="FREE_DETENTION_HOUR" type="text"  value="'.$edit['det_free_hr'].'" placeholder="Free Detention Hour"  readonly>
				</div>
			</div>
			
			<div class="form-group">
				<label for="CHARGES" class="col-sm-6 control-label">Hours</label>
				<div class="col-sm-6">
				<input class="form-control text-right" name="DETENTION_HOUR" id="DETENTION_HOUR" type="text"  value="'.$edit['loaded_detention_hrs'].'" placeholder="Detention Hours 1 to 24" onkeyup="javascript:return calc_det_rate('.$edit['client_id'].',&quot;'.$edit['shipment_details']['CONS_NAME'].'&quot;);">
				</div>
			</div>
			
			<div class="form-group">
				<label for="CHARGES" class="col-sm-6 control-label">Rate Per Hour</label>
				<div class="col-sm-6">
				<input class="form-control text-right" name="RATE_PER_HOUR" id="RATE_PER_HOUR" type="text"  value="'.$edit['loaded_perhr_rate'].'" placeholder="Per Hour Rate" onkeyup="javascript:return calc_det_rate_per_hr('.$edit['client_id'].',&quot;'.$edit['shipment_details']['CONS_NAME'].'&quot;);" >
				</div>
			</div>
			
			<div class="form-group">
				<label for="CHARGES" class="col-sm-6 control-label">Total</label>
				<div class="col-sm-6">
				<div class="input-group">
				<span class="input-group-addon">$</span>
				<input class="form-control text-right" name="DETENTION_RATE" id="DETENTION_RATE" type="text"  value="'.$edit['loaded_detention_Rate'].'" onkeyup="return calculate_total();" placeholder="Detention Rate">
				</div>
				</div>
			</div>
			</div><!-- Duncan -->
			
            <div class="well well-sm"><!-- Duncan -->
			<h4>Unloading Detention</h4>
			<div class="form-group">
				<label for="CHARGES" class="col-sm-6 control-label">Free Hours</label>
				<div class="col-sm-6">
				<input class="form-control text-right" name="FREE_UN_DETENTION_HOUR" id="FREE_UN_DETENTION_HOUR" type="text"  value="'.$edit['un_det_free_hr'].'" placeholder="Free Unloaded Detention Hour" readonly>
				</div>
			</div>
			
			<div class="form-group">
				<label for="CHARGES" class="col-sm-6 control-label">Hours</label>
				<div class="col-sm-6">
				<input class="form-control text-right" name="UNLOADED_DETENTION_HOUR" id="UNLOADED_DETENTION_HOUR" type="text"  value="'.$edit['unloaded_detention_hrs'].'" placeholder="Unloaded Detention Hours 1 to 24" onkeyup="javascript:return calc_unloaded_det_rate('.$edit['client_id'].',&quot;'.$edit['shipment_details']['CONS_NAME'].'&quot;);">
				</div>
			</div>
			
			<div class="form-group">
				<label for="CHARGES" class="col-sm-6 control-label">Rate Per Hour</label>
				<div class="col-sm-6">
				<input class="form-control text-right" name="UN_RATE_PER_HOUR" id="UN_RATE_PER_HOUR" type="text"  value="'.$edit['unloaded_perhr_rate'].'" placeholder="Per Hour Rate" onkeyup="javascript:return calc_unloaded_rate_per_hr('.$edit['client_id'].',&quot;'.$edit['shipment_details']['CONS_NAME'].'&quot;);" >
				</div>
			</div>
			
			<div class="form-group">
				<label for="CHARGES" class="col-sm-6 control-label">Total</label>
				<div class="col-sm-6">
				<div class="input-group">
				<span class="input-group-addon">$</span>
				<input class="form-control text-right" name="UNLOADED_DETENTION_RATE" id="UNLOADED_DETENTION_RATE" type="text"  value="'.$edit['unloaded_detention_Rate'].'" onkeyup="return calculate_total();" placeholder="Unloaded Detention Rate">
				</div>
				</div>
			</div>
			</div><!-- Duncan -->
			</div>

			<div class="col-sm-4">
			<div class="well well-sm"><!-- Duncan -->
			<div class="row">
			<div class="col-sm-6 col-sm-offset-6 text-center text-success">Information Only</div>
			</div>
			<div class="form-group">
				<label for="CARRIER" class="col-sm-6 control-label">Carrier</label>
				<div class="col-sm-6">
				<input class="form-control text-right" name="CARRIER" id="CARRIER" type="text"  placeholder="Carrier"value="" onkeyup="return calculate_total();" >
				</div>
			</div>
			<div class="form-group">
				<label for="COD" class="col-sm-6 control-label">COD</label>
				<div class="col-sm-6">
				<input class="form-control text-right" name="COD" id="COD" type="text"  placeholder="COD "value="" onkeyup="return calculate_total();" >
				</div>
			</div>
			</div>
			
            <div class="well well-sm"><!-- Duncan -->
			<div class="form-group">
				<label for="MILLEAGE" class="col-sm-6 control-label">Distance</label>
				<div class="col-sm-6">
				<input class="form-control text-right"  type="text"   name="MILLEAGE"  id="MILLEAGE"    placeholder="Milleage"  value="'.$edit['milleage'].'"  onkeyup="return calculate_rpm_amount();"/>
				</div>
			</div>
			
			<div class="form-group">
				<label for="RPM" class="col-sm-6 control-label">Rate Per Mile</label>
				<div class="col-sm-6">
				<input class="form-control text-right" name="RPM" id="RPM" type="text"  placeholder="Rate Per Mile" value="'.$edit['rpm'].'" onkeyup="return calculate_rpm_amounta();">
				</div>
			</div>
			
			<div class="form-group">
				<label for="RPM_Rate" class="col-sm-6 control-label">Total</label>
				<div class="col-sm-6">
				<div class="input-group">
				<span class="input-group-addon">$</span>
				<input class="form-control text-right" name="RPM_Rate" id="RPM_Rate" type="text"  placeholder="Rate Per Mile" value="'.number_format($edit['RPM_Rate'],2, ".", "").'" onkeyup="return calculate_total();">
				</div>
				</div>
			</div>
			<hr>
			
		'.$avg_rate.'
				
			<div class="form-group">
				<label for="FUEL_COST" class="col-sm-6 control-label">Fuel Surcharge</label>
				<div class="col-sm-6">
				<div class="input-group">
				<span class="input-group-addon">$</span>
				<input class="form-control text-right"  type="text"   name="FUEL_COST"  id="FUEL_COST"    placeholder="Fuel Cost"  value="'.($avg_rate == '' && $edit['fuel_rate'] == 0 ? '' : $edit['fuel_rate']).'"  onkeyup="return calculate_total();"/>
				</div>
				</div>
			</div>
			</div><!-- Duncan -->
			
			<div class="well well-sm">
			<div class="form-group">
				<label for="ADJUSTMENT_CHARGE_TITLE" class="col-sm-6 control-label"><input class="form-control"  type="text"   name="ADJUSTMENT_CHARGE_TITLE"  id="ADJUSTMENT_CHARGE_TITLE"    placeholder="Adjustment charge"  value=""/></label>
				<div class="col-sm-6">
				<div class="input-group">
				<span class="input-group-addon">$</span>
				<input class="form-control text-right"  type="text"   name="ADJUSTMENT_CHARGE"  id="ADJUSTMENT_CHARGE"    placeholder="Adjustment charge"  value=""  onkeyup="return calculate_total();"/>
				</div>
				</div>
			</div>
			
			<div class="form-group">
				<label for="SELECTION_FEE" class="col-sm-6 control-label">Selection Fee</label>
				<div class="col-sm-6">
				<div class="input-group">
				<span class="input-group-addon">$</span>
				<input class="form-control text-right" name="SELECTION_FEE" id="SELECTION_FEE" type="text" onkeyup="return calculate_total();">
				</div>
				</div>
			</div>

			<div class="form-group">
				<label for="DISCOUNT" class="col-sm-6 control-label">Discount</label>
				<div class="col-sm-6">
				<div class="input-group">
				<span class="input-group-addon">$</span>
				<input class="form-control text-right" name="DISCOUNT" id="DISCOUNT" type="text" onkeyup="return calculate_total();">
				</div>
				</div>
			</div>
			</div>
						
            </div>
            </div>
      <div class="form-group">
         	<div class="col-sm-4">  
			<div class="form-group">
				<label class="col-sm-4 control-label" for="OTHER">Other</label>
              </div>
			  
			  <div class="form-group">
				<div class="col-sm-5"></div>
				<div class="col-sm-1">
				<input type="checkbox" value="1" id="HAZMAT" name="HAZMAT" '.$check.'>
                </div>
              <label style="text-align:left;" class="col-sm-3 control-label" for="HAZMAT">Hazmat Only  </label>
                </div>
				
				<div class="form-group">
				<div class="col-sm-5"></div>
              <div class="col-sm-1">
				<input type="checkbox" value="1" id="TEMP_CONTROLL" name="TEMP_CONTROLL" '.$checked.'>
                </div> 
              <label style="text-align:left;" class="col-sm-5 control-label" for="TEMP_CONTROLL">Temp Controlled Only   </label>
			</div>
			
			
		</div>
        
		<div class="col-sm-8">';
	if(count($edit['client_rates'])>0 && $edit['client_rates']!="")
		echo '
			<div class="form-group">
				<label for="BROKER_INFO" class="col-sm-4 control-label"> Client Rates </label>
                </div>
			
               <div class="form-group">
			<div style="display:none;" id="reload_client_result"><img border="1" style="position:absolute; margin-left:250px;margin-top:10px;" src="images/loading.gif"></div>
            <table class="table table-striped table-condensed table-bordered table-hover">
            <thead>
              <tr class="exspeedite-bg">
			   <th class="text-center">Rate Name</th>
                <th class="text-center">Rate Code</th>
                <th class="text-center">Category</th>
				<th class="text-center">Rate( $ )</th>
				<th class="text-center">QTY</th>
              </tr>
            </thead>
            <tbody>
			'.$client_rate.'
		 
			</tbody>
          </table>
        </div>';
    else
    	echo '<br><br><br><br>';
        echo '
        <div class="form-group">
				<label for="TOTAL" class="col-sm-8 control-label input-lg">Total</label>
				<div class="col-sm-4">
				<div class="input-group">
				<span class="input-group-addon">$</span>
				<input class="form-control text-right input-lg" name="TOTAL" id="TOTAL" type="text"  
				placeholder="Total" value="'.$total.'" readonly="readonly">
				</div>
				</div>
			</div>
           
          
	</div>
	
	</div>
	
</form>
</div>
</div>';
	}
	
	##--------view bill history of driver----------##
	public function render_bill_history($code, $my_session, $sts_qb_online) {		
		//echo "<pre>";
		//var_dump($code);
		//echo "</pre>";
		$tab='<tbody>';
		if(is_array($code['driver_info']) && count($code['driver_info'])>0)
		{//print_r($code['driver_info']);
			$i=1;
			foreach($code['driver_info'] as $dr)
			{if($i<10){$i='0'.$i;}
			//!DUNC WIP
				$range = date('m/d/Y',strtotime($dr['WEEKEND_FROM']))."  &nbsp;TO &nbsp; ".date('m/d/Y',strtotime($dr['WEEKEND_TO']));
				if($dr['WEEKEND_FROM'] == $code['start'] && $dr['WEEKEND_TO'] = $code['end']) {
					$range = '<strong>'.$range.'</strong>';
				}
				$aweek =  date('W', strtotime($dr['WEEKEND_FROM']));
				$tab.="<tr style='cursor:pointer;' >
       						 <td style='text-align:center;'>".($i+$code['paging_offset']).": (week ".$aweek.") ".$range."<br>";
						
				$tab.='<table class="table table-striped table-condensed table-bordered table-hover">
					<thead>
					<tr class="exspeedite-bg">
			        <th class="text-center">Load</th>
			        <th class="text-center">First Arrive</th>
			        <th class="text-center">Last Depart</th>
			        <th class="text-center">Distance</th>
			        <th class="text-center">Trip</th>
			        <th class="text-center">Bonus</th>
			        <th class="text-center">Handling</th>
			        <th class="text-center">Gross</th>
			        </tr>
					</thead>
					<tbody>';
				$tp = $bn = $hn = $gr = 0;
				foreach($code['load_info'][$dr['WEEKEND_FROM']] as $ld) {
					$tab.='<tr class="exspeedite-thinner">
					<td>'.($ld["LOAD_ID"] > 0 ? '<a href="exp_viewload.php?CODE='.$ld["LOAD_ID"].'" target="_blank">'.$ld["LOAD_ID"].'</a>'.($code['multi_company'] && ! empty($ld["OFFICE_NUM"]) ? ' / '.$ld["OFFICE_NUM"] : '') : $ld["LOAD_ID"]).'</td>
					<td>'.(isset($ld["first_arrive"]) && $ld["first_arrive"] <> '0000-00-00 00:00:00' && $ld["first_arrive"] <> '' ? date("m/d H:i", strtotime($ld["first_arrive"])) : '').'</td>
					<td>'.(isset($ld["last_depart"]) && $ld["last_depart"] <> '0000-00-00 00:00:00' && $ld["last_depart"] <> '' ? date("m/d H:i", strtotime($ld["last_depart"])) : '').'</td>
					<td class="text-right">'.$ld["trip_distance"].'</td>
					<td class="text-right">'.number_format((float) $ld["TRIP_PAY"], 2).'</td>
					<td class="text-right">'.number_format((float) $ld["BONUS"], 2).'</td>
					<td class="text-right">'.number_format((float) $ld["HANDLING"], 2).'</td>
					<td class="text-right">'.number_format((float) $ld["GROSS_EARNING"], 2).'</td>
					</tr>';
					$tp += $ld["TRIP_PAY"];
					$bn += $ld["BONUS"];
					$hn += $ld["HANDLING"];
					$gr += $ld["GROSS_EARNING"];
				}
				$tab.='
					<tr class="exspeedite-thinner">
					<th></th>
					<th></th>
					<th></th>
					<th></th>
			        <th class="text-right">'.number_format((float) $tp, 2).'</th>
			        <th class="text-right">'.number_format((float) $bn, 2).'</th>
			        <th class="text-right">'.number_format((float) $hn, 2).'</th>
			        <th class="text-right">'.number_format((float) $gr, 2).'</th>
			        </tr>
				
				</tbody>
					</table>';

      			$tab.="</td>
					<td style='text-align:center;'>".($dr['FINALIZE_STATUS']<>'pending' ? '<span class="glyphicon glyphicon-check"></span>' : '<span class="text-muted"><span class="glyphicon glyphicon-unchecked"></span>').
							($dr['FINALIZE_STATUS']=='paid' ? '<br><br><img src="quickbooks-php-master/online/images/qb_icon.png" alt="qb_icon" width="22" height="22" valign="middle"/>&nbsp;<strong>Paid</strong>' : 
							
							(empty($dr["quickbooks_status_message"]) ? "" : '<br><br><span class="bg-danger">'.$dr["quickbooks_status_message"].'</span>'))."
					</td>
      			<td style='text-align:center;'>".($dr['FINALIZE_STATUS']<>'pending' ?
      			(isset($dr["ADDED_ON"]) && $dr["ADDED_ON"] <> '0000-00-00' && $dr["ADDED_ON"] <> '' ? date("m/d/Y", strtotime($dr["ADDED_ON"])).
      			(isset($dr["PAID_DATE"]) && $dr["PAID_DATE"] <> '0000-00-00' && $dr["PAID_DATE"] <> '' ? '<br><br>'.date("m/d/Y", strtotime($dr["PAID_DATE"])) : '') : '') : '')."
      			</td>
      				<td style='text-align:center;' >
					<div style='width: 120px;'>
					<div class='btn-group btn-group-xs'>";
				
				//! Bill history buttons on right
				if( $dr['FINALIZE_STATUS']<>'pending' ) {
	            	$tab.="<a href='exp_billhistory.php?TYPE=view&ID=".$dr['DRIVER_PAY_ID']."' class='btn btn-default btn-xs inform'  id=bill_".$dr['DRIVER_PAY_ID']." data-placement='bottom' data-toggle='popover' data-content='View settlement history of week ".date('m/d/Y',strtotime($dr['WEEKEND_FROM']))."  &nbsp;TO&nbsp; ".date('m/d/Y',strtotime($dr['WEEKEND_TO']))."'> <span style='font-size: 14px;'><span class='glyphicon glyphicon-eye-open'></span></span></a>";

	            	//! Undo finalize button QBOE && admin only
	            	if( $my_session->in_group(EXT_GROUP_ADMIN) ) {
		            	$tab.="<a onclick=\"confirmation('Do you really want to unapprove this?<br><br>This returns the driver pay to the pending state.<br><br>Be sure to delete the bill in Quickbooks before you re-approve.', 'exp_billhistory.php?CODE=".$code['id']."&unapprove=".$dr['DRIVER_PAY_ID']."')\" class='btn btn-danger btn-xs inform' data-placement='bottom' data-toggle='popover' data-content='Undo finalize/approve<br>Admin only'><span style='font-size: 14px;'><span class='glyphicon glyphicon-backward'></span></span></a>";
		            	//! Resend to QBOE button
		            	$tab.="<a onclick=\"confirmation('Do you really want to resend this to Quickbooks?<br><br>This resends the driver pay to Quickbooks.<br><br>Be sure to delete the bill in Quickbooks before you do this.', 'exp_billhistory.php?CODE=".$code['id']."&resend=".$dr['DRIVER_PAY_ID']."')\" class='btn btn-danger btn-xs inform' data-placement='bottom' data-toggle='popover' data-content='Resend to Quickbooks<br>Admin only'><span style='font-size: 14px;'><span class='glyphicon glyphicon-forward'></span></span></a>";
	            	}
	            } else {
	            	$tab.="<a href='exp_adddriverpay.php?id=".$code['id']."&forceadd&back=".$code["id"]."&start_date=".$dr['WEEKEND_FROM']."&end_date=".$dr['WEEKEND_TO']."' class='btn btn-default btn-xs inform' data-placement='bottom' data-toggle='popover' data-content='Add driver pay for week ".date('m/d/Y',strtotime($dr['WEEKEND_FROM']))."  &nbsp;TO&nbsp; ".date('m/d/Y',strtotime($dr['WEEKEND_TO']))."'><span style='font-size: 14px;'><span class='glyphicon glyphicon-usd'></span></span></a>
	            	
	            	<a href='exp_updatedriverpay.php?payid=".$dr['DRIVER_PAY_ID']."&back=".$code["id"]."' class='btn btn-default btn-xs inform'  id=bill_".$dr['DRIVER_PAY_ID']." data-placement='bottom' data-toggle='popover' data-content='Update driver pay of week ".date('m/d/Y',strtotime($dr['WEEKEND_FROM']))."  &nbsp;TO&nbsp; ".date('m/d/Y',strtotime($dr['WEEKEND_TO']))."'> <span style='font-size: 14px;'><span class='glyphicon glyphicon-edit'></span></span></a>";
	            }
                    
                if( ! in_array($dr['FINALIZE_STATUS'], array('finalized', 'paid')) ) {
	                $tab.="<a class='btn btn-default btn-xs'   data-placement='bottom' data-toggle='popover' onclick='return delete_settlement_history(".$dr['DRIVER_PAY_ID'].",".$dr['DRIVER_ID'].");'><span style='font-size: 14px;'><span class='glyphicon glyphicon-trash'></span></a>";
	                }
                $tab.="</div>
						</div>
					</td>
      						</tr>";//onclick=&quot;return delete_settlement_history(\''.$dr['WEEKEND_FROM'].'\','\'.$dr['WEEKEND_TO'].'\',\''.$dr['DRIVER_ID'].'\');
		 $i++;}
			//}
		}
		else
		{
			$tab.="<tr style='cursor:pointer;' >
       						 <td style='text-align:center;' colspan='3'>NO SETTLEMENT HISTORY IS AVAILABLE !</td>
							 </tr>";
		}
		$tab.='<tbody>';
		echo "<div class='container-full' role='main'>
		";
		
		//! Duncan - check if there are any driver rates set up.
		if( isset($code['norates']) ) {
			echo '<div class="alert alert-danger" role="alert">
			<h3><span class="glyphicon glyphicon-warning-sign text-danger"></span> This Driver Has No Rates Assigned</h3>
			<p>Please assign some rates or rate profiles for this driver before you can do driver pay.</p>
			<p><a class="btn btn-sm btn-default" href="exp_listdriver.php"><span class="glyphicon glyphicon-remove"></span> Back To Drivers</a></p>
			</div>
			';
		} else {
		
			echo "
			<h3><img src='images/rate-profile.png' alt='zone_icon' height='24'> Settlement History
			<div class='btn-group'>";
			
			//! SCR# 421 - Disable button if already done this week
			echo '<a class="btn btn-sm btn-success'.($code["DoneThisWeek"] ? ' disabled tip" title="You have already finalized driver pay for this week."' : '"').' href="exp_adddriverpay.php?id='.$code['id'].'&forceadd&back='.$code["id"].'"><span class="glyphicon glyphicon-usd"></span> Add Pay For Current Week</a>';
	
			if( count($code["previous"]) > 0 )
				echo "<a class='btn btn-sm btn-success' href='exp_updatedriverpay.php?payid=".$code["previous"][0]["DRIVER_PAY_ID"]."&back=".$code["id"]."'><span class='glyphicon glyphicon-edit'></span> Update Current</a>";
			
			echo "<a class='btn btn-sm btn-default' href='exp_listdriver.php'><span class='glyphicon glyphicon-remove'></span> Back To Drivers</a>
			</div>
			&nbsp; Driver Name: ".'<a href="exp_editdriver.php?CODE='.$code["id"].'">'.$code['drivername']."</a>
			<br/>
			</h3>
			";
					
			echo '<h4><div class="btn-group">
			<button type="button" class="dropdown-toggle btn-md" data-toggle="dropdown">Add Driver Pay For Past Weeks<span class="caret"></span></button>
		  <ul class="dropdown-menu" role="menu">
		  ';
			//! SCR# 615 - one week into the future
			$astart = date("m/d/Y", strtotime('monday next week -1 day'));
			$aend = date("m/d/Y", strtotime('sunday next week -1 day'));
			$aweek =  date('W', strtotime($astart));
			$ayear =  date('Y', strtotime($aend));
			
			echo '<li><a class="bg-danger tip" title="Warning: This is NEXT WEEK, in the future. For very special situations. Use with caution." href="exp_adddriverpay.php?id='.
				$code["id"].'&forceadd&back='.$code["id"].'&start_date='.$astart.
				'&end_date='.$aend.'"><b><span class="glyphicon glyphicon-usd"></span> '.
				$ayear.' week '.$aweek.' ('.$astart.' to '.$aend.' FUTURE)</b></a></li>
	';
		
			for($c=0; $c<12; $c++) {
				//! SCR# 615 - fix dates
				$astart = date("m/d/Y", strtotime('monday this week -'.$c.' week -1 day'));
				$aend = date("m/d/Y", strtotime('sunday this week -'.$c.' week -1 day'));
				$aweek =  date('W', strtotime($astart));
				$ayear =  date('Y', strtotime($aend));
				
				echo '<li><a href="exp_adddriverpay.php?id='.
					$code["id"].'&forceadd&back='.$code["id"].'&start_date='.$astart.
					'&end_date='.$aend.'"><span class="glyphicon glyphicon-usd"></span> '.
					$ayear.' week '.$aweek.' ('.$astart.' to '.$aend.')</a></li>
		';
			}
			echo '</ul>
			</div>&nbsp;&nbsp;&nbsp;';
			//! SCR# 498 - paging for billing history
			if( $code['paging_offset'] > 0 ) {
				$prev_offset = $code['paging_offset'] - $code['paging_pagesize'];
				if($prev_offset < 0 ) $prev_offset = 0;
				echo '<a class="btn btn-sm btn-default" href="exp_billhistory.php?CODE='.$code["id"].'&offset='.$prev_offset.'"><span class="glyphicon glyphicon-backward"></span> Previous</a>';
			} else {
				echo '<a class="btn btn-sm btn-default" href="#" disabled><span class="glyphicon glyphicon-backward"></span> Previous</a>';
			}
			if( $code['paging_offset'] + $code['paging_pagesize'] < $code['paging_totalrows'] ) {
				$next_offset = $code['paging_offset'] + $code['paging_pagesize'];
				echo '<a class="btn btn-sm btn-default" href="exp_billhistory.php?CODE='.$code["id"].'&offset='.$next_offset.'"><span class="glyphicon glyphicon-forward"></span> Next</a>';
			} else {
				echo '<a class="btn btn-sm btn-default" href="#" disabled><span class="glyphicon glyphicon-forward"></span> Next</a>';
			}
			
			
			echo '</h4>';
			
			
			echo "<div class='table-responsive'>
			<div id='reload_search_result' style='display:none;'><img src='images/loading.gif' border='1' style='position:absolute; margin-left:250px;margin-top:10px;'></div>
			<table class='table table-striped table-condensed table-bordered table-hover'>
			<thead>
			<tr class='exspeedite-bg'>
			<th style='text-align:center;'>Week</th>
			<th style='text-align:center;'>Approved</th>
			<th style='text-align:center;'>When</th>
			<th style='text-align:center;'>Action</th>
	        </tr>
			</thead>
			".$tab."
			</table>
			</div>
	
	</div>";
		}	
	}
	
	##-------- view settlement history of the week-----------##
	public function render_history_of_week($edit)
	{$contract_name="";
		if($edit['contract_name']!="")
		{
			$contract_name=" - ".$edit['contract_name'];
		}
      echo '<div class="container-full" role="main" style="width:87%">
<div class="container-full" role="main">
<div style="position:fixed;top:50px;width:75%;margin-left:33px;background-color:#fff;padding-bottom:50px;z-index:500;">	
  <form class="form-inline" role="form" action="/trunk/exp_adddriverpay.php"  	method="post" enctype="multipart/form-data" 	name="RESULT_FILTERS_EXP_DRIVER" id="RESULT_FILTERS_EXP_DRIVER">
    <input name="FILTERS_EXP_DRIVER" type="hidden" value="on">
    <h3><img src="images/driver_icon.png" alt="driver_icon" height="24" > &nbsp; Settlement History
	<div class="btn-group">
	<a class="btn btn-sm btn-default" href="exp_billhistory.php?CODE='.$edit['detail']["DRIVER_ID"].'"><span class="glyphicon glyphicon-remove"></span> Back</a>
	</div><span style="color:#9C3;font-size:18px;">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<strong >APPROVED ON : '.date('m/d/Y',strtotime($edit['detail']['ADDED_ON'])).'</strong></span></h3>
  </form>
  </div>
    </div>

<div class="render_driver_pay_screen">
    <div class="table-responsive" style="overflow:auto;">
	 <!--info-->
	 <div style="position:fixed;top:150px;width:74.4%;background-color:#fff;z-index:500;">
    <table class="table table-striped table-condensed table-bordered table-hover" >
	
                  <tbody>
				  <tr style="cursor:pointer;" >
				  	<td colspan="4"><div style="float:right;">Week :<strong> '.date("d  M  Y", strtotime($edit['detail']["WEEKEND_FROM"])).'</strong>  to   <strong>'.date("d M Y", strtotime($edit['detail']["WEEKEND_TO"])).'</strong></div></td>
				  </tr>
            	<tr style="cursor:pointer;" >					
					<td width="14%" ><strong>Driver Name</strong></td>
					<td width="16%">'.$edit['driver']['FIRST_NAME']." ".$edit['driver']['MIDDLE_NAME']." ".$edit['driver']['LAST_NAME'].'</td>
                    <td width="14%" ><strong>Contract Id :</strong></td>
					<td width="16%"><input class="form-control input-sm" type="text"  name="" value="'.$edit['driver']['PROFILE_ID'].$contract_name.'" readonly></td>
                </tr>
            </tbody>
            </table>
            </div>
     <!--info-->
      <div style="margin-top:200px;">';

	}
	
	##--------function for bill details of each load------##
	public function render_load_detail($edit) {
		//echo "<pre>";
		//var_dump($edit);
		//echo "</pre>";
		 #get range of miles 
		 $str_range_of_miles='';
		$bonusable_total=0.00;
		 if(count($edit['range_rates'])>0)
		 {
			$total_dist	=	 $edit["info"]["TOTAL_DISTANCE"];	 
			

			 foreach( $edit['range_rates'] as $row_range)
			 {$str='';
			 if($edit["info"]["TOTAL_DISTANCE"] > $row_range['RANGE_FROM'] && $edit["info"]["TOTAL_DISTANCE"] < $row_range['RANGE_TO'])
			 {
				  $diff_miles	=	$total_dist - $row_range['RANGE_FROM'];
			 }
			 else
			 {
				 $diff_miles	=	$row_range['RANGE_TO'] - $row_range['RANGE_FROM'];
			 }
				 if($row_range['RANGE_RATE']==0)
				 {
					 $str='<td colspan="3">NO MILES ARE AVAILABLE WITHIN A GIVEN RANGE .</td>';
				 }
				 else
				 {
					 $str='<td width="13%" class="text-right">'.$diff_miles.'</td>
					 <td width="13%" class="text-right">$ '.$row_range['RANGE_RATE'].'</td>
						<td width="15%"><div class="input-group">
				<span class="input-group-addon">$</span>
<input class="form-control input-sm text-right" type="text"  name="range_miles[]"  value="'.$row_range['TOTAL_RATE'].'" readonly></div></td>';
					$bonusable_total += $row_range['TOTAL_RATE'];
				 }
				
				 $str_range_of_miles.='<tr style="cursor:pointer;" >					
						<td width="20%" ><strong>'.$row_range['RANGE_NAME'].'  :  '.$row_range['RANGE_FROM'].' TO '.$row_range['RANGE_TO'].'</strong></td>
						'.$str.'
					</tr>
					'; 
			 }
		 }	
		 else
		 {
			 $str_range_of_miles.='<tr style="cursor:pointer;" >					
						<td width="20%" >NO RANGE RATES ARE AVAILABLE !</td>
						</tr>'; 
		 }
		 
		 #get add manual rates to loads
		 $manual_rates='';
		 if(count($edit['manual_rates'])>0) {
			foreach($edit['manual_rates'] as $man) {
			 	$manual_rates.=' <tr style="cursor:pointer;" >
				 <td>  Rates :</td><td style="width:25%;">'.$man['MANUAL_CODE'].'</td><td style="width:25%;">'.$man['MANUAL_DESC'].'</td><td style="width:25%;" class="text-right">'.$man['MANUAL_RATE'].'</td>
				 </tr>';
				 if( $edit['MANUAL_BONUSABLE'] )
				 	$bonusable_total += $man['MANUAL_RATE'];
			}
		 }
		 
		 $driver_rate_str='';
		 #get number of pallets for this load
		$total_amount=0.00;
		if(count($edit['rates'])>0)
		{
			foreach($edit['rates'] as $row)
			{
			//	echo $row['CATEGORY_NAME']."=>".$dri_row['TOT_PALETS'];
		
				$driver_rate_str	.=	'
					<tr style="cursor:pointer;" >					
					<td style="font-weight:bold; font-family:Arial, Helvetica, sans-serif; font-size:13px;">'.$row['LOAD_RATE_NAME'].'<br>'.$row['TITLE'].
					(isset($row['FREE_HOURS']) && $row['FREE_HOURS'] > 0 ? ' ['.$row['FREE_HOURS'].' free hours] ' : '').
						($row['LOAD_RATE_BONUS'] == 1 ? ' (B)' : '').' :</td>
<td><input name="'.$row["LOAD_ID"].'_qty[]" type="text" class="form-control input-sm text-right" value="'.number_format($row['QUANTITY'],2,".","").'" readonly></td>
<td><input name="'.$row["LOAD_ID"].'_amt[]"  type="text" class="form-control input-sm text-right" value="'.$row['RATE'].'" readonly ></td>
					<td><div class="input-group">
				<span class="input-group-addon">$</span>
<input name="text_total'.$row["LOAD_ID"].'[]"  type="text" class="form-control input-sm text-right" value="'.$row['PAY'].'" readonly><div></td>
				  </tr>
				';
				if( $row['LOAD_RATE_BONUS'] == 1 ) $bonusable_total += $row['PAY'];
			}
		}
		else
		{
			$driver_rate_str	.=	'
					<tr style="cursor:pointer;" >					
					<td style="text-align:center;" colspan="4">NO RATES ARE ASSIGNED !</td>
					 </tr>
				';
		}
		
		$stop_detail='';
			if(count($edit['STOP_DETAIL'])>0)
			{//print_r($dri_row['STOP_DETAIL']);
				foreach($edit['STOP_DETAIL'] as $stop)
				{//print_r( $stop);
					$stop_type=$depart_Date=$arrive_Date='';
					if($stop['STOP_TYPE']=='pick')
					{$stop_type='Origin';}
					else
					{$stop_type='Destination';}
					if($stop['ACTUAL_DEPART']!="")
					{$depart_Date=date('m/d/Y H:i:s l',strtotime($stop['ACTUAL_DEPART']));
					if($stop['HOLIDAY']!="")
					{$depart_Date.='<br/><span style="color:#d3392b;"><strong>'.$stop['HOLIDAY'].'</strong></span>';}
					}
					
					if($stop['ACTUAL_ARRIVE']!="")
					{$arrive_Date=date('m/d/Y H:i:s l',strtotime($stop['ACTUAL_ARRIVE']));
					if($stop['ARRIVAL_HOLIDAY']!="")
					{$arrive_Date.='<br/><span style="color:#d3392b;"><strong>'.$stop['ARRIVAL_HOLIDAY'].'</strong></span>';}
					}

					$space='';
					if($stop['CITY']!="" && $stop['STATE']!="")
                     {$space=',';}
					$stop_detail.='<tr style="cursor:pointer;">
											<td>'.$stop_type.'</td>
											<td>'.$stop['NAME'].'<br/>'.$stop['CITY'].$space.$stop['STATE'].'</td>
											<td>'.$arrive_Date.'</td>
											<td>'.$depart_Date.'</td>
							                 </tr>';
				}
			}
			else
			{$stop_detail.='<tr style="cursor:pointer;">
											<td colspan="4" style="text-align:center;">STOP DETAIL IS CURRENTLY UNAVAILABLE !</td>
											</tr>';}
		
	echo '  <!--info-->
	<div class="well well-sm">
    <table class="table table-striped table-condensed table-bordered table-hover" >
                  <tbody>
            <tr style="cursor:pointer;" class="exspeedite-bg" >					
						 ';
	
	if( $edit["load_id"] == 0 )					 
		echo '<td width="90%" class="text-center"><strong>NON-TRIP RELATED PAY</strong>
						 <input class="form-control input-sm text-right" type="hidden"  name="load_id[]" id="load_id0" value="0" readonly="readonly">
						 <input class="form-control input-sm text-right" type="hidden"  name="total_miles[]" id="total_miles0" value="">
						 </td>
	';
	else
		echo '			 <td width="16%" ><strong>Load Id</strong>'.($edit['multi_company'] && ! empty($edit["load_detail"]["OFFICE_NUM"]) ? ' - '.$edit["load_detail"]["OFFICE_NUM"] : '').'</td>
					<td width="16%"><input class="form-control input-sm text-right" type="text"  name="load_id[]" id="load_id'.$edit["load_id"].'" value="'.$edit["load_id"].'" readonly></td>
                    <td width="16%" bgcolor="#000000"><strong>Total Miles</strong></td>
					<td width="16%"><input class="form-control input-sm text-right" type="text"  name="total_miles[]" id="total_miles'.$edit["load_detail"]['TOTAL_MILES'].'" value="'.$edit["load_detail"]['TOTAL_MILES'].'" readonly></td>
	';
	echo '						</tr>
            </tbody>
            </table>
     <!--info-->';
     
    echo '<!--total settelment2-->
       <table class="table table-striped table-condensed table-bordered table-hover" >
	    <thead>
              
            </thead>
	   		 <tbody>
	   		 ';
	if( $edit["load_id"] > 0 ) {             
		 echo '		<tr style="cursor:pointer;" >	
						<td style="text-align:right; color:#000; font-weight:bold; font-family:Arial, Helvetica, sans-serif; font-size:13px;">Shipments :</td>
						<td colspan="3" style="text-align:right; color:#000; font-weight:bold; font-family:Arial, Helvetica, sans-serif; font-size:13px;">'.$this->slink($edit["info"]["SHIPMENTS"]).'</td>
					  </tr>                 
						  				
						  <tr style="cursor:pointer;" >			 		      <tr style="cursor:pointer;" >					
					<td style="text-align:right; color:#000; font-weight:bold; font-family:Arial, Helvetica, sans-serif; font-size:13px;">Tractor :</td>
					<td ><input name="tractor" id="trctor" value="'.$edit["info"]["UNIT_NUMBER"].'" type="text" class="form-control input-sm" readonly></td>
					<!--<td style="text-align:right; color:#000; font-weight:bold; font-family:Arial, Helvetica, sans-serif; font-size:13px;">Destination :</td>
					<td><input name="DESTINATION"  id="DESTINATION"  type="text" class="form-control input-sm" value="'.$edit["info"]["CONS_NAME"].'" readonly></td>-->
					<td style="text-align:right; color:#000; font-weight:bold; font-family:Arial, Helvetica, sans-serif; font-size:13px;">Approved Date :</td>
					<td><input name="approved_date" id="approved_date"  type="text" class="form-control input-sm" value="'.date('m/d/Y',strtotime($edit["load_detail"]['APPROVED_DATE'])).'" readonly></td>
				  </tr>                 
                             
                  <tr style="cursor:pointer;" >					
					<td style="text-align:right; color:#000; font-weight:bold; font-family:Arial, Helvetica, sans-serif; font-size:13px;">Trailer 1 :</td>
					<td><input name="trailer" id="trailer" value="'.$edit["info"]["TRAILER_NUMBER"].'" type="text" class="form-control input-sm" readonly></td>
					<td style="text-align:right; color:#000; font-weight:bold; font-family:Arial, Helvetica, sans-serif; font-size:13px;">Trailer 2 :</td>
					<td><input name="" type="text" class="form-control input-sm" readonly></td>
				  </tr>       
				   <tr style="cursor:pointer;" >					
						<td style="text-align:right; color:#000; font-weight:bold; font-family:Arial, Helvetica, sans-serif; font-size:13px;">Odometer From:</td>
						<td><input name="odometer_from[]" id="odometer_from" value="'.$edit["load_detail"]['ODOMETER_FROM'].'" type="text" class="form-control input-sm text-right" readonly ></td>
						<td style="text-align:right; color:#000; font-weight:bold; font-family:Arial, Helvetica, sans-serif; font-size:13px;">Odometer To :</td>
						<td><input name="odometer_to[]" id="odometer_to" type="text" class="form-control input-sm text-right" value="'.$edit["load_detail"]['ODOMETER_TO'].'" readonly></td>
					  </tr>              
                     
 					  <!-- Duncan LINE_HAUL -->               
					   <tr style="cursor:pointer;" >					
						<td style="text-align:right; color:#000; font-weight:bold; font-family:Arial, Helvetica, sans-serif; font-size:13px;">Line haul:</td>
						<td><div class="input-group">
				<span class="input-group-addon">$</span><input name="line_haul[]" id="line_haul'.$edit["load_id"].'" value="'.$edit["load_detail"]["LINE_HAUL"].'" type="text" class="form-control input-sm text-right" readonly></div></td>
						
						<td style="text-align:right; color:#000; font-weight:bold; font-family:Arial, Helvetica, sans-serif; font-size:13px;">Fuel Surcharge:</td>
						<td><div class="input-group">
				<span class="input-group-addon">$</span><input name="fuel_cost[]" id="line_haul'.$edit["load_id"].'" value="'.$edit["load_detail"]["FUEL_COST"].'" type="text" class="form-control input-sm text-right" readonly></div></td>
					  </tr>                 
                 
                  <tr style="cursor:pointer;" >					
					<td colspan="4">&nbsp;</td>
					
				  </tr>  					
			<tr style="cursor:pointer;" >
			<th>Stop Type</th>
			<th>Shipper /Consignee</th>
			<th>Actual Arrival Day & Holiday</th>
			<th>Actual Delivery Day & Holiday</th>
			</tr>
			'.$stop_detail;
		}	
						
            echo '<tr >                
                <th class="text-center">&nbsp;</th>               
				<th>Quantity</th>
                <th>Rate</th>
                <th>Pay</th>                
              </tr>
            </thead>
            <tbody>  
			'.$driver_rate_str;
			
		if( $edit["load_id"] > 0 ) { 	
			echo '
			<tr style="cursor:pointer;" >					
					<td colspan="4">&nbsp;</td>
					
				  </tr>

				<tr style="cursor:pointer;" >					
					<td style="text-align:center; color:#000; font-weight:bold; font-family:Arial, Helvetica, sans-serif; font-size:13px;" colspan="4">Miles Range:</td>			
					</tr> 
					<tr>
					<td><div id="reload_range_rate_div" style="display:none;"><img src="images/loading.gif" border="1" style="position:absolute; margin-left:250px;margin-top:10px;"></div>
					</td>
					</tr>
					<tr style="cursor:pointer;" >					
					<td style="text-align:center; color:#000; font-weight:bold; font-family:Arial, Helvetica, sans-serif; font-size:13px;" >&nbsp;</td>	
					<td style="text-align:center; color:#000; font-weight:bold; font-family:Arial, Helvetica, sans-serif; font-size:13px;" >Difference Between Miles</td>	
					<td style="text-align:center; color:#000; font-weight:bold; font-family:Arial, Helvetica, sans-serif; font-size:13px;" >Range Rates	</td>	
					<td style="text-align:center; color:#000; font-weight:bold; font-family:Arial, Helvetica, sans-serif; font-size:13px;" >Rates</td>	
					</tr>
				  
				  '.$str_range_of_miles;
			}	  
				  echo '
				 <tr style="cursor:pointer;" >					
					<td colspan="4">&nbsp;</td>
					
				  </tr>


           		 <tr style="cursor:pointer;" >					
					<td style="text-align:right; color:#000; font-weight:bold; font-family:Arial, Helvetica, sans-serif; font-size:13px;">Manual Rates  :</td>
					
				 </tr>   
				
				  '.$manual_rates.'
				
				   <tr style="cursor:pointer;" >					
					<td colspan="4">
							<table width="100%" id="empty_row" class="table table-striped table-condensed table-bordered table-hover">
							</table>
					</td>
				  </tr>




			<tr style="cursor:pointer;" >					
					<td style="text-align:right; color:#000; font-weight:bold; font-family:Arial, Helvetica, sans-serif; font-size:16px;">Total Trip Pay :</td>
					<td>&nbsp;</td>
					<td>&nbsp;</td>
					<td style="color:#000; font-weight:bold; font-family:Arial, Helvetica, sans-serif; font-size:16px; padding-right: 20px;">
					<div id="total_trip_amount_div" class="text-right"> $ '.$edit["load_detail"]['TOTAL_TRIP_PAY'].'</div></td> </tr>

						<tr style="cursor:pointer;" >					
						<td style="text-align:right; color:#000; font-weight:bold; font-family:Arial, Helvetica, sans-serif; font-size:16px;">Bonus-able Trip Pay_:</td>
						<td>&nbsp;</td>
						<td>&nbsp;</td>
						<td style="color:#000; font-weight:bold; font-family:Arial, Helvetica, sans-serif; font-size:16px; padding-right: 20px;">
						<div class="text-right"> $ '.number_format($bonusable_total,2).'</div></td> </tr>

                  <tr style="cursor:pointer;" >					
					<td colspan="4">&nbsp;</td>
					
				  </tr>
					
				  		
                  <tr style="cursor:pointer;" >					
					<td style="text-align:right; color:#000; font-weight:bold; font-family:Arial, Helvetica, sans-serif; font-size:13px;">Bonus :</td>
					<td><select name="bonus_allow[]" class="form-control input-sm" id="bonus_allow" disabled >
							<option value="'.$edit["load_detail"]['BONUS'].'">'.$edit["load_detail"]['BONUS'].'</option>
						
					</select></td>
					<td><input class="form-control input-sm text-right" type="text"  name="apply_bonus[]" id="apply_bonus" value="'.$edit["load_detail"]['APPLY_BONUS'].'" readonly> Bonus in %</td>
					<td><div class="input-group">
				<span class="input-group-addon">$</span>
<input class="form-control input-sm text-right" type="text"  name="bonus_amount[]" value="'.$edit["load_detail"]['BONUS_AMOUNT'].'" readonly></div>$ on Total Trip Pay </td>
				  </tr>    
                  <tr style="cursor:pointer;" >					
					<td colspan="4">&nbsp;</td>
				 </tr>
				  
				    <tr style="cursor:pointer;" >					
					<td style="text-align:right; color:#000; font-weight:bold; font-family:Arial, Helvetica, sans-serif; font-size:13px;">Handling Pallets :</td>
					<td><input class="form-control input-sm text-right" type="text"  name="handling_pallet[]" id="handling_pallet" value="'.$edit["load_detail"]['HANDLING_PALLET'].'"  readonly></td>
					<td style="text-align:right; color:#000; font-weight:bold; font-family:Arial, Helvetica, sans-serif; font-size:13px;">Handling Pay :</td>
					<td><div class="input-group">
				<span class="input-group-addon">$</span>
<input class="form-control input-sm text-right" type="text"  name="handling_pay[]" id="handling_pay" value="'.$edit["load_detail"]['HANDLING_PAY'].'"  readonly></div></td>
				  </tr>  
				  
				      <tr style="cursor:pointer;" >					
					<td style="text-align:right; color:#000; font-weight:bold; font-family:Arial, Helvetica, sans-serif; font-size:13px;">Loaded Detention Hours :</td>
					<td><input class="form-control input-sm text-right" type="text"  name="loaded_hr[]" id="loaded_hr" value="'.$edit["load_detail"]['LOADED_DET_HR'].'"  readonly></td>
					<td style="text-align:right; color:#000; font-weight:bold; font-family:Arial, Helvetica, sans-serif; font-size:13px;">Unoaded Detention Hours :</td>
					<td><input class="form-control input-sm text-right" type="text"  name="unloaded_hr[]" id="unloaded_hr" value="'.$edit["load_detail"]['UNLOADED_DET_HR'].'"  readonly></td>
				  </tr> 					
            </tbody>
				  
					
				  
                  <tr style="cursor:pointer;" >					
					<td style="text-align:right; color:#000; font-weight:bold; font-family:Arial, Helvetica, sans-serif; font-size:18px;">Total Settlement:</td>
					<td>&nbsp;</td>
					<td>&nbsp;</td>
					<td style="text-align:right; color:#000; font-weight:bold; font-family:Arial, Helvetica, sans-serif; font-size:18px;"><div class="input-group">
				<span class="input-group-addon">$</span>
<input name="totla_settelment[]" id="totla_settelment" type="text" class="form-control input-sm text-right" style="font-size:18px;" value="'.$edit["load_detail"]['TOTAL_SETTLEMENT'].'" readonly></div></td>
				  </tr>
           </table>
	<!--total settelment2-->
	</div>';
	
	
	}
	
	##------ end art of bill page----##
	public function render_bill_historty_end($edit) {
		//echo "<pre>";
		//var_dump($edit);
		//echo "</pre>";
		$trip_pay=$bonus=$handling=$gross=0;
		#-------- total of load ------#
		if(count($edit['fetch_totals'])>0 && isset($edit['fetch_totals'][0]['TRIP_PAY']))
		{
			foreach($edit['fetch_totals'] as $tot) {
				$trip_pay=$trip_pay+$tot['TRIP_PAY'];
				$bonus=$bonus+$tot['BONUS'];
				$handling=$handling+$tot['HANDLING'];
				$gross=$gross+$tot['GROSS_EARNING'];
			}
		}
		#-------- total of trip ------#
		
		if(count($edit['fetch_totals_trip'])>0 && isset($edit['fetch_totals_trip'][0]['TRIP_PAY']))
		{
			foreach($edit['fetch_totals_trip'] as $tot) {
				$trip_pay=$trip_pay+$tot['TRIP_PAY'];
				$bonus=$bonus+$tot['BONUS'];
				$handling=$handling+$tot['HANDLING'];
				$gross=$gross+$tot['GROSS_EARNING'];
			}
		}
		
		
		 echo  '<!--weekly settelment-->
		 </div>
  <table class="table table-striped table-condensed table-bordered table-hover" >
          		  <tr style="cursor:pointer;" >					
					<td width="23%" style="text-align:right; color:#000; font-weight:bold; font-family:Arial, Helvetica, sans-serif; font-size:18px; text-transform:uppercase;">WEEK&nbsp;ENDING&nbsp;TOTAL:</td>
					<td width="26%">&nbsp;</td>
					<td width="25%">&nbsp;</td>
					<td width="26%" style="color:#000; font-weight:bold; font-family:Arial, Helvetica, sans-serif; font-size:13px;"></td>
				  </tr>
                  
                  <tr style="cursor:pointer;"  >					
					<td style="text-align:right; color:#000; font-weight:bold; font-family:Arial, Helvetica, sans-serif; font-size:18px; text-transform:uppercase;">TRIP PAY:</td>
					<td>&nbsp;</td>
					<td>&nbsp;</td>
					<td><div class="input-group">
				<span class="input-group-addon">$</span>
				<input name="final_trip_pay" id="final_trip_pay" type="text" class="form-control input-sm text-right" style="font-size:18px;" value="'.number_format($trip_pay,2).'" readonly="readonly"></div></td>
				  </tr>
                  
                  <tr style="cursor:pointer;"  >					
					<td style="text-align:right; color:#000; font-weight:bold; font-family:Arial, Helvetica, sans-serif; font-size:18px; text-transform:uppercase;">Bonus:</td>
					<td>&nbsp;</td> 
					<td>&nbsp;</td>
					<td><div class="input-group">
				<span class="input-group-addon">$</span>
<input name="final_bonus" id="final_bonus" type="text" class="form-control input-sm text-right" style="font-size:18px;" value="'.number_format($bonus,2).'" readonly="readonly"</div></td>
				  </tr>
                  
                  <tr style="cursor:pointer;"  >					
					<td style="text-align:right; color:#000; font-weight:bold; font-family:Arial, Helvetica, sans-serif; font-size:18px; text-transform:uppercase;">Handling:</td>
					<td>&nbsp;</td>
					<td>&nbsp;</td>
					<td><div class="input-group">
				<span class="input-group-addon">$</span>
<input name="final_handling" id="final_handling" type="text" class="form-control input-sm text-right" style="font-size:18px;" value="'.number_format($handling,2).'" readonly="readonly"></div></td>
				  </tr>
                  
                  <tr style="cursor:pointer;" >					
					<td style="text-align:right; color:#000; font-weight:bold; font-family:Arial, Helvetica, sans-serif; font-size:18px; text-transform:uppercase;">GROSS EARNINGS</td>
					<td>&nbsp;</td>
					<td>&nbsp;</td>
					<td style="color:#000; font-weight:bold; font-family:Arial, Helvetica, sans-serif; font-size:18px;" >
					<div class="input-group">
				<span class="input-group-addon">$</span>
<input name="final_settlement" id="final_settlement" type="text" class="form-control input-sm text-right" style="font-size:18px;" value="'.number_format($gross,2).'" readonly="readonly"></div>
					<div id="final_settlement_div"> </div></td>
				  </tr>
          </table>
    <!--weekly settelment--> 
         
        </div>
    <div style="clear:both"></div>
  </div>
  
</div>';	
	}
	
	##-----view client bill under each shipment--------##
	public function render_client_bill_history($edit) {
		$str='';

		$item_list_table = sts_item_list::getInstance($this->table->database, $this->debug);
		if(count($edit['saved_details'])==0) {
			$str='<span style="color:#F00;font-size:15px;margin-left:40px;">CLIENT BILLING INFORMATION IS NOT AVAILABLE!</span>';
		}
		$client_rate='';
		$client_total=0;
		if(count($edit['saved_details'])>0 && $edit['saved_details'][0]['BILL_RATE_ID']!="")
		{
			foreach($edit['saved_details'] as $ed)
			{
				$client_rate.='<tr style="cursor:pointer;">
			<td class="text-center">'.$ed['RATE_CODE'].'</td>
			<td class="text-center">'.$ed['RATE_NAME'].'</td>
                <td class="text-center">'.$ed['CATEGORY'].'</td>
				<td class="text-center"><input type="text" name="RATE[]" id="RATE" value="'.$ed['RATES'].'" readonly="readonly"/></td>
				<td class="text-center"><input type="text" name="QTY[]" id="QTY" value="'.((float)$ed['RATES']/35).'" readonly="readonly"/></td>
				</tr>
				<input type="hidden" name="CODE[]" value="'.$ed['RATE_CODE'].'" />
				<input type="hidden" name="CATEGORY[]" value="'.$ed['CATEGORY'].'" /> ';
				$client_total=$client_total+$ed['RATES'];
			}
			$client_rate.='<tr class="exspeedite-bg"><td colspan="3" class="text-center">Total</td><td class="text-center"><input type="text" name="TOTAL_RATE" id="TOTAL_RATE" value="'.$client_total.'" style="color:#030303" readonly="readonly" />  $</td></tr>';
		}
		else
		{
			$client_rate.='<tr style="cursor:pointer;"><td class="text-center" colspan="5">NO RATES ARE ASSIGNED !</td></tr><tr class="exspeedite-bg"><td colspan="2" class="text-center">Total</td><td class="text-center"><input type="text" name="TOTAL_RATE" id="TOTAL_RATE" value="0" style="color:#030303" readonly="readonly"/>  $</td></tr>';
		}
		$check=$checked='';
		if($edit['saved_details'][0]['HAZMAT']=='1')
		{$check='checked="checked"';}
		if($edit['saved_details'][0]['TEMP_CONTROLLED']=='1')
		{$checked='checked="checked"';}
		
		$avg='';
		if($edit['saved_details'][0]['FSC_COLUMN']!="" &&  $edit['saved_details'][0]['FSC_AVERAGE_RATE']!="")
		{
			$avg='<div class="form-group">
				<label for="FUEL_COST" class="col-sm-6 control-label">'.$edit['saved_details'][0]['FSC_COLUMN'].'</label>
				<div class="col-sm-6">
				<input class="form-control text-right" name="FUEL_COST" id="FUEL_COST" type="text"  placeholder="Fuel Cost"  value="'.$edit['saved_details'][0]['FSC_AVERAGE_RATE'].'" readonly="readonly">
				</div>
			</div>';
		}
		//!XYZZY
		echo '<div class="container-full theme-showcase" role="main">
<div class="well  well-md">
<form role="form" class="form-horizontal" action=""  method="post" enctype="multipart/form-data" name="adddriver" id="adddriver"><h3><img src="images/driver_icon.png" alt="driver_icon" height="24"> 
View Client Billing	#'.$edit['shipment_details']['SHIPMENT_CODE'].
($edit['SS_NUMBER'] ? ' / '.$edit['SS_NUMBER'] : '').
$this->render_currency($edit['saved_details'][0]['CURRENCY']).
$item_list_table->render_terms($edit['saved_details'][0]['TERMS']).'	
         
			<!--<button onclick="return chk_confirm_update(\''.$edit['shipment_details']['SHIPMENT_CODE'].'\');" class="btn btn-sm btn-success" type="submit" name="savebilling">
		<span class="glyphicon glyphicon-ok"></span>Update Client Billing</button>-->
';

//! Duncan - add shipment, approve button. 
//! Duncan WIP - may need to disable edit in some cases
// Added check of CONSOLIDATE_NUM, if not null, cannot approve this
if( ! in_array($edit['bstatus'], array('oapproved','approved','billed')) ) {
	echo '<a class="btn btn-sm btn-success" href="exp_updateclientbilling.php?id='.$edit['shipment_details']['SHIPMENT_CODE'].'"><span class="glyphicon glyphicon-ok"></span> Edit</a>';
	
	echo ' <a class="btn btn-sm btn-danger" onclick="confirmation(\'Do you really want to remove the billing data?\n\nThis lets you re-enter the data. It will also re-calculate any auto rating.\', \'exp_shipment_bill.php?id='.$edit['shipment_details']['SHIPMENT_CODE'].'&delete\')"><span class="glyphicon glyphicon-trash"></span></a>';
	
}

echo '
<a class="btn btn-sm btn-info" href="exp_addshipment.php?CODE='.$edit['shipment_details']['SHIPMENT_CODE'].'"><span class="glyphicon glyphicon-edit"></span> Shipment #'.$edit['shipment_details']['SHIPMENT_CODE'].'</a>';

if( $edit['multi_company'] && in_group(EXT_GROUP_BILLING) && $edit['bstatus'] == 'entry' ) {
	$approve_shipment =  is_null($edit['shipment_details']['CONSOLIDATE_NUM']) ?
		$edit['shipment_details']['SHIPMENT_CODE'] : $edit['shipment_details']['CONSOLIDATE_NUM'];

	$billing_table = new sts_table($this->table->database, CLIENT_BILL, $this->debug);
	$result2 = $billing_table->fetch_rows("SHIPMENT_ID = ".$edit['shipment_details']['SHIPMENT_CODE']);
	if( (is_array($result2) && count($result2) == 1 && isset($result2[0]['TOTAL']))
		|| $approve_shipment <> $edit['shipment_details']['SHIPMENT_CODE'] )

		echo ' <a class="btn btn-sm btn-danger" onclick="confirmation(\'Do you really want to approve this at the office level for billing?<br>This sends the bill to finance. Before you do, check the following:<br><br>You need at least a complete shipper, consignee and bill-to<br>You also need pickup and delivery dates and completed billing information.<br>'.($edit['SAGE50'] ? 'For Sage 50, you need a business code, and the bill-to client needs a Sage ID.<br>' : '').'<br>It also makes the shipment details read only, so be sure the shipment information is complete first.\', \'exp_shipment_state.php?BILLING=true&PW=Soyo&STATE=oapproved&CODE='.$approve_shipment.'&CSTATE='.$edit['shipment_details']['BILLING_STATUS'].'\')"><span class="glyphicon glyphicon-ok"></span> Approve (office) '.$approve_shipment.'</a>';
}

if( $edit['approve_operations'] && in_group(EXT_GROUP_BILLING) && $edit['bstatus'] == 'entry' ) {
	$approve_shipment =  is_null($edit['shipment_details']['CONSOLIDATE_NUM']) ?
		$edit['shipment_details']['SHIPMENT_CODE'] : $edit['shipment_details']['CONSOLIDATE_NUM'];

	$billing_table = new sts_table($this->table->database, CLIENT_BILL, $this->debug);
	$result2 = $billing_table->fetch_rows("SHIPMENT_ID = ".$edit['shipment_details']['SHIPMENT_CODE']);
	if( (is_array($result2) && count($result2) == 1 && isset($result2[0]['TOTAL']))
		|| $approve_shipment <> $edit['shipment_details']['SHIPMENT_CODE'] )

		echo ' <a class="btn btn-sm btn-danger" onclick="confirmation(\'Do you really want to approve this at the operations level for billing?<br>This sends the bill to finance. Before you do, check the following:<br><br>You need at least a complete shipper, consignee and bill-to<br>You also need pickup and delivery dates and completed billing information.<br>'.($edit['SAGE50'] ? 'For Sage 50, you need a business code, and the bill-to client needs a Sage ID.<br>' : '').'<br>It also makes the shipment details read only, so be sure the shipment information is complete first.\', \'exp_shipment_state.php?BILLING=true&PW=Soyo&STATE=oapproved2&CODE='.$approve_shipment.'&CSTATE='.$edit['shipment_details']['BILLING_STATUS'].'\')"><span class="glyphicon glyphicon-ok"></span> Approve (Operations) '.$approve_shipment.'</a>';
}


if( in_group(EXT_GROUP_FINANCE) && in_array($edit['bstatus'], array('entry','oapproved', 'oapproved2','unapproved')) ) {
	$approve_shipment =  is_null($edit['shipment_details']['CONSOLIDATE_NUM']) ?
		$edit['shipment_details']['SHIPMENT_CODE'] : $edit['shipment_details']['CONSOLIDATE_NUM'];
	$re = $edit['bstatus'] == 'unapproved' ? 'Re-' : '';

	$billing_table = new sts_table($this->table->database, CLIENT_BILL, $this->debug);
	$result2 = $billing_table->fetch_rows("SHIPMENT_ID = ".$edit['shipment_details']['SHIPMENT_CODE']);
	if( (is_array($result2) && count($result2) == 1 && isset($result2[0]['TOTAL']))
		|| $approve_shipment <> $edit['shipment_details']['SHIPMENT_CODE'] )

		echo ' <a class="btn btn-sm btn-danger" id="approve_finance" onclick="confirmation(\'Do you really want to approve this for billing?<br>This sends the bill to '.$this->destination.'. Before you do, check the following:<br><br>You need at least a complete shipper, consignee and bill-to<br>You also need pickup and delivery dates and completed billing information.<br>'.($edit['SAGE50'] ? 'For Sage 50, you need a business code, and the bill-to client needs a Sage ID.<br>' : '').'<br>It also makes the shipment details read only, so be sure the shipment information is complete first.\', \'exp_shipment_state.php?BILLING=true&PW=Soyo&STATE=approved&CODE='.$approve_shipment.'&CSTATE='.$edit['shipment_details']['BILLING_STATUS'].'\')"><span class="glyphicon glyphicon-ok"></span> '.$re.'Approve (finance) '.$approve_shipment.'</a>';
}

if( $edit['status'] == 'Entered' ) {
	echo ' <a class="btn btn-sm btn-success" id="Ready_Dispatch" onclick="confirmation(\'Confirm: Ready Dispatch\',\'exp_assignshipment.php?CODE='.$edit['shipment_details']['SHIPMENT_CODE'].'&CSTATE=1\')" ><span class="glyphicon glyphicon-arrow-right"></span> Ready Dispatch</a>';
}

echo '			<a class="btn btn-sm btn-default" href="exp_listshipment.php"><span class="glyphicon glyphicon-remove"></span> Shipments</a>
			'.$str.'
			
	</h3>
	<div class="form-group">
	';

	//! EDI banner
	$this->render_billing_edi_banner( $edit );
	
	echo '
		<div class="col-sm-4">
			<div class="form-group well well-sm tighter">
				<label class="col-sm-4 control-label"> LOAD# </label>
				<div class="col-sm-8 text-right">'.$edit['shipment_details']['LOAD_CODE'].'</div>
			</div>
		</div>
		<div class="col-sm-2">
			<div class="form-group well well-sm tighter">
				<label class="col-sm-4 control-label">#&nbsp;Picks</label>
				<div class="col-sm-8 text-right">'.$edit['no_of_picks'].'</div>
			</div>
		</div>
		<div class="col-sm-2">
			<div class="form-group well well-sm tighter">
				<label class="col-sm-4 control-label">#&nbsp;Drops</label>
				<div class="col-sm-8 text-right">'.$edit['no_of_drops'].'</div>
			</div>
		</div>
		<div class="col-sm-4">
			<div class="form-group well well-sm tighter">
				<label class="col-sm-4 control-label"> Business&nbsp;Code </label>
				<div class="col-sm-8">'.$edit['shipment_details']['BUSINESS_CODE_NAME'].'</div>
			</div>
		</div>
	</div>
    <div class="form-group">
		<div class="col-sm-4">
			<div class="form-group well well-sm">
				<label for="SHIPPER_NAME" class="col-sm-4 control-label">Shipper</label>
				<div class="col-sm-8">
				<input class="form-control" name="SHIPPER_NAME" id="SHIPPER_NAME" type="text" placeholder="Shipper" maxlength="50" value="'.$edit['shipment_details']['SHIPPER_NAME'].'" readonly="readonly">
				</div>
				<label for="SHIPPER_ADDR1" class="col-sm-4 control-label">Address</label>
				<div class="col-sm-8">
				<input class="form-control" name="SHIPPER_ADDR1" id="SHIPPER_ADDR1" type="text"  placeholder="Addr1" maxlength="50" value="'.$edit['shipment_details']['SHIPPER_ADDR1'].'" readonly="readonly">
				</div>
				<label for="BOL_NUMBER" class="col-sm-4 control-label">BOL#</label>
				<div class="col-sm-8">
				<input class="form-control" id="BOL_NUMBER" type="text"   value="'.$edit['shipment_details']['BOL_NUMBER'].'" readonly>
				</div>
			'.($edit['LOG_HOURS'] ? '	<label for="NOT_BILLED_HOURS" class="col-sm-4 control-label"><span class="glyphicon glyphicon-time"></span> Hours</label>
				<div class="col-sm-8">
					<input class="form-control text-right" name="NOT_BILLED_HOURS" id="NOT_BILLED_HOURS" type="text" value="'.$edit['saved_details'][0]['NOT_BILLED_HOURS'].'" readonly>
				</div>
			' : '').'</div>
		</div>
		<div class="col-sm-8">
			<div class="form-group">
				<div class="col-sm-6">
					<div class="form-group well well-sm tighter">
						<label for="CONS_NAME" class="col-sm-4 control-label">Consignee</label>
						<div class="col-sm-8">
						<input class="form-control" name="CONS_NAME" id="CONS_NAME" type="text"  placeholder="Consignee" maxlength="50" value="'.$edit['shipment_details']['CONS_NAME'].'" readonly>
						</div>
						<label for="CONS_ADDR1" class="col-sm-4 control-label">Address</label>
						<div class="col-sm-8">
						<input class="form-control" name="CONS_ADDR1" id="CONS_ADDR1" type="text"  placeholder="Addr1" maxlength="50" value="'.$edit['shipment_details']['CONS_ADDR1'].'" readonly>
						</div>
					</div>
				</div>
				<div class="col-sm-6">
					<div class="form-group well well-sm tighter">
						<label for="BILLTO_NAME" class="col-sm-4 control-label">Bill-To</label>
						<div class="col-sm-8">
						<input class="form-control" name="BILLTO_NAME" id="BILLTO_NAME" type="text"  placeholder="Bill-To" maxlength="50"   value="'.$edit['shipment_details']['BILLTO_NAME'].'" readonly>
						</div>
						<label for="BILLTO_ADDR1" class="col-sm-4 control-label">Address</label>
						<div class="col-sm-8">	
						<input class="form-control" name="BILLTO_ADDR1" id="BILLTO_ADDR1" type="text"  placeholder="Addr1" maxlength="50" value="'.$edit['shipment_details']['BILLTO_ADDR1'].'" readonly>
						</div>
					</div>
				</div>
				<div class="col-sm-12">
					<div class="form-group well well-sm tighter">
						<label for="NOTES" class="col-sm-2 control-label">Disp Notes</label>
						<div class="col-sm-10">
						<textarea name="NOTES" id="NOTES" class="form-control" style="resize=none;" readonly>'.$edit['shipment_details']['NOTES'].'</textarea>
						</div>
					</div>
				</div>
			</div>
		</div>
	</div>
	<div class="form-group">
		<div class="col-sm-4">
		<div class="form-group">
				<label for="CHARGES" class="col-sm-5 control-label">Pickup Appointment</label>
				<div class="col-sm-6">
				<input class="form-control"  type="text"   value="'.$edit['expected_pickup_time'].'" readonly="readonly">
				</div>
			</div>
			</div>
			<div class="col-sm-4">
			<div class="form-group">
				<label for="CHARGES" class="col-sm-5 control-label">Actual Arrival Time</label>
				<div class="col-sm-6">
				<input class="form-control"  type="text"   value="'.$edit['actual_pickup_time'].'" readonly="readonly">
				</div>
			</div>
			</div>
			<div class="col-sm-4">
			<div class="form-group">
				<label for="CHARGES" class="col-sm-5 control-label">Actual Departure Time</label>
				<div class="col-sm-6">
				<input class="form-control"  type="text"   value="'.$edit['actual_pickup_depart_time'].'" readonly="readonly">
				</div>
			</div>
			</div>
			</div>
			<div class="form-group">
			<div class="col-sm-4">
			<div class="form-group">
				<label for="CHARGES" class="col-sm-5 control-label">Delivery Appointment</label>
				<div class="col-sm-6">
				<input class="form-control"  type="text"   value="'.$edit['expected_drop_time'].'" readonly="readonly">
				</div>
			</div>
			</div>
			<div class="col-sm-4">
			<div class="form-group">
				<label for="CHARGES" class="col-sm-5 control-label">Actual Arrival Time</label>
				<div class="col-sm-6">
				<input class="form-control"  type="text"   value="'.$edit['actual_drop_arrival_time'].'" readonly="readonly">
				</div>
			</div>
		</div>

		<div class="col-sm-4">
			<div class="form-group">
				<label for="CHARGES" class="col-sm-5 control-label">Actual Departure Time</label>
				<div class="col-sm-6">
				<input class="form-control"  type="text"   value="'.$edit['actual_drop_time'].'" readonly="readonly">
				</div>
			</div>
		</div>
		</div>

	<div class="form-group">
		<div class="col-sm-4">
			
            <div class="well well-sm"><!-- Duncan -->
			<div class="form-group">
				<label for="CHARGES" class="col-sm-6 control-label">Pallets</label>
				<div class="col-sm-6">
				<input class="form-control text-right" name="PALLETS" id="PALLETS" type="text"  placeholder="No.of pallets" value="'.$edit['saved_details'][0]['PALLETS'].'" readonly="readonly">
				</div>
			</div>
			
			<div class="form-group">
				<label for="CHARGES" class="col-sm-6 control-label">Per Pallet Rate</label>
				<div class="col-sm-6">
				<input class="form-control text-right" name="PER_PALLETS" id="PER_PALLETS" type="text"  placeholder="" value="'.$edit['saved_details'][0]['PER_PALLETS'].'" readonly="readonly">
				</div>
			</div>
			
			<div class="form-group">
				<label for="CHARGES" class="col-sm-6 control-label">Pallet Total</label>
				<div class="col-sm-6">
				<div class="input-group">
				<span class="input-group-addon">$</span>
				<input class="form-control text-right" name="PALLETS_RATE" id="PALLETS_RATE" type="text"  placeholder="No.of pallets" value="'.$edit['saved_details'][0]['PALLETS_RATE'].'" readonly="readonly">
				</div>
				</div>
			</div>
			
			<div class="form-group">
				<label for="CHARGES" class="col-sm-6 control-label">Handling Pallets</label>
				<div class="col-sm-6">
				<div class="input-group">
				<span class="input-group-addon">$</span>
				<input class="form-control text-right" name="HAND_PALLET" id="HAND_PALLET" type="text"  value="'.$edit['saved_details'][0]['HAND_PALLET'].'" readonly="readonly">
				</div>
				</div>
			</div>
			</div><!-- Duncan -->
			
			<div class="well well-sm"><!-- Duncan -->
			<div class="form-group">
				<label for="CHARGES" class="col-sm-6 control-label">Handling Charges</label>
				<div class="col-sm-6">
				<div class="input-group">
				<span class="input-group-addon">$</span>
				<input class="form-control text-right" name="HAND_CHARGES" id="HAND_CHARGES" type="text"  value="'.$edit['saved_details'][0]['HAND_CHARGES'].'" readonly="readonly">
				</div>
				</div>
			</div>
			
			<div class="form-group">
				<label for="CHARGES" class="col-sm-6 control-label">Freight Charges</label>
				<div class="col-sm-6">
				<div class="input-group">
				<span class="input-group-addon">$</span>
				<input class="form-control text-right" name="FREIGHT_CHARGES" id="FREIGHT_CHARGES" type="text"  value="'.$edit['saved_details'][0]['FREIGHT_CHARGES'].'" readonly="readonly">
				</div>
				</div>
			</div>
			</div><!-- Duncan -->
			
			<div class="well well-sm"><!-- Duncan -->
			<div class="form-group">
			'.(! empty($edit['EQUIPMENT']) ? '<div class="form-group">
				<label for="EQUIPMENT" class="col-sm-5 control-label">Equipment</label>
				<div class="col-sm-6 bg-warning">'.$edit['EQUIPMENT'].'</div>
				</div>' : '').'
				<label for="EXTRA_CHARGES" class="col-sm-5 control-label">Extra Charges</label>
				<div class="col-sm-7">
				<div class="input-group">
				<span class="input-group-addon">$</span>
				<input class="form-control text-right" name="EXTRA_CHARGES" id="EXTRA_CHARGES" type="text"  value="'.$edit['saved_details'][0]['EXTRA_CHARGES'].'"
				placeholder="Extra Charges" readonly="readonly" >
				</div>
				</div>
			</div>

			<div class="form-group">
				<label for="EXTRA_CHARGES_NOTE" class="col-sm-5 control-label">Description</label>
				<div class="col-sm-7">
				
				<textarea name="EXTRA_CHARGES_NOTE" id="EXTRA_CHARGES_NOTE" class="form-control" style="resize=none;" readonly="readonly">'.$edit['saved_details'][0]['EXTRA_CHARGES_NOTE'].'</textarea>
				</div>
			</div>
			</div>
			
			<div class="well well-sm"><!-- Duncan -->
			<div class="form-group">
				<label for="STOP_OFF" class="col-sm-5 control-label">Stop Off</label>
				<div class="col-sm-7">
				<div class="input-group">
				<span class="input-group-addon">$</span>
				<input class="form-control text-right" name="STOP_OFF" id="STOP_OFF" type="text"  value="'.$edit['saved_details'][0]['STOP_OFF'].'"
				placeholder="" readonly="readonly" >
				</div>
				</div>
			</div>
			
			<div class="form-group">
				<label for="STOP_OFF_NOTES" class="col-sm-5 control-label">Notes</label>
				<div class="col-sm-7">
				<textarea class="form-control" name="STOP_OFF_NOTES" id="STOP_OFF_NOTES"  readonly="readonly" >'.$edit['saved_details'][0]['STOP_OFF_NOTE'].'</textarea>
				</div>
			</div>
			</div>
			
			
			<div class="form-group">
				<label for="Weekend" class="col-sm-6 control-label">Weekend/Holiday</label>
				<div class="col-sm-6">
				<div class="input-group">
				<span class="input-group-addon">$</span>
				<input class="form-control text-right" name="Weekend" id="Weekend" type="text"  value="'.$edit['saved_details'][0]['WEEKEND'].'"
				placeholder="Weekend" readonly="readonly" >
				</div>
				</div>
			</div>
			
			</div>
            
            <div class="col-sm-4">
			
            <div class="well well-sm"><!-- Duncan -->
			<div id="det_loader" style="display:none;"><img border="1" style="position:absolute; margin-left:250px;margin-top:10px;" src="images/loading.gif"></div>
			<h4>Loading Detention</h4>
			<div class="form-group">
				<label for="FREE_DETENTION_HR" class="col-sm-6 control-label">Free Hours</label>
				<div class="col-sm-6">
				<input class="form-control text-right" name="FREE_DETENTION_HR" id="FREE_DETENTION_HR" type="text"  value="'.$edit['saved_details'][0]['FREE_DETENTION_HOUR'].'" readonly="readonly">
				</div>
			</div>
        	<div class="form-group">
				<label for="DETENTION_HR" class="col-sm-6 control-label">Hours</label>
				<div class="col-sm-6">
				<input class="form-control text-right" name="DETENTION_HR" id="DETENTION_HR" type="text"  value="'.$edit['saved_details'][0]['DETENTION_HOUR'].'" readonly="readonly">
				</div>
			</div>
			
			<div class="form-group">
				<label for="RATE_PER_HR" class="col-sm-6 control-label">Rate Per Hour</label>
				<div class="col-sm-6">
				<input class="form-control text-right" name="RATE_PER_HR" id="RATE_PER_HR" type="text"  value="'.$edit['saved_details'][0]['RATE_PER_HOUR'].'" readonly="readonly">
				</div>
			</div>
			
			<div class="form-group">
				<label for="DETENTION_RATE" class="col-sm-6 control-label">Total</label>
				<div class="col-sm-6">
				<div class="input-group">
				<span class="input-group-addon">$</span>
				<input class="form-control text-right" name="DETENTION_RATE" id="DETENTION_RATE" type="text"  value="'.$edit['saved_details'][0]['DETENTION_RATE'].'" readonly="readonly">
				</div>
				</div>
			</div>
			</div><!-- Duncan -->
			
            <div class="well well-sm"><!-- Duncan -->
			<div id="det_loader" style="display:none;"><img border="1" style="position:absolute; margin-left:250px;margin-top:10px;" src="images/loading.gif"></div>
            <h4>Unloading Detention</h4>
			<div class="form-group">
				<label for="FREE_UN_DETENTION_HR" class="col-sm-6 control-label">Free Hours</label>
				<div class="col-sm-6">
				<input class="form-control text-right" name="FREE_UN_DETENTION_HR" id="FREE_UN_DETENTION_HR" type="text"  value="'.$edit['saved_details'][0]['FREE_UN_DETENTION_HOUR'].'" readonly="readonly">
				</div>
			</div>
			
			<div class="form-group">
				<label for="DETENTION_HR" class="col-sm-6 control-label">Hours</label>
				<div class="col-sm-6">
				<input class="form-control text-right" name="DETENTION_HR" id="DETENTION_HR" type="text"  value="'.$edit['saved_details'][0]['UNLOADED_DETENTION_HOUR'].'" readonly="readonly">
				</div>
			</div>
			
			<div class="form-group">
				<label for="UN_RATE_PER_HR" class="col-sm-6 control-label">Rate Per Hour</label>
				<div class="col-sm-6">
				<input class="form-control text-right" name="UN_RATE_PER_HR" id="UN_RATE_PER_HR" type="text"  value="'.$edit['saved_details'][0]['UN_RATE_PER_HOUR'].'" readonly="readonly">
				</div>
			</div>
			
			<div class="form-group">
				<label for="DETENTION_RATE" class="col-sm-6 control-label">Total</label>
				<div class="col-sm-6">
				<div class="input-group">
				<span class="input-group-addon">$</span>
				<input class="form-control text-right" name="DETENTION_RATE" id="DETENTION_RATE" type="text"  value="'.$edit['saved_details'][0]['UNLOADED_DETENTION_RATE'].'" readonly="readonly">
				</div>
				</div>
			</div>
			</div><!-- Duncan -->
			
			
        </div>
        
		<div class="col-sm-4">
			
			<div class="well well-sm"><!-- Duncan -->
			<div class="row">
			<div class="col-sm-6 col-sm-offset-6 text-center text-success">Information Only</div>
			</div>
			<div class="form-group">
				<label for="CARRIER" class="col-sm-6 control-label">Carrier</label>
				<div class="col-sm-6">
				<input class="form-control text-right" name="CARRIER" id="CARRIER" type="text"  placeholder="Carrier" readonly="readonly"  value="'.$edit['saved_details'][0]['CARRIER'].'">
				</div>
			</div>
			<div class="form-group">
				<label for="COD" class="col-sm-6 control-label">COD</label>
				<div class="col-sm-6">
				<input class="form-control text-right" name="COD" id="COD" type="text"  placeholder="COD " readonly="readonly"  value="'.$edit['saved_details'][0]['COD'].'">
				</div>
			</div>
			</div>			

            <div class="well well-sm"><!-- Duncan -->
			<div class="form-group">
				<label for="FUEL_COST" class="col-sm-6 control-label">Distance</label>
				<div class="col-sm-6">
				<input class="form-control text-right" name="MILLEAGE" id="MILLEAGE" type="text"  placeholder="Milleage"  value="'.$edit['saved_details'][0]['MILLEAGE'].'" readonly="readonly">
				</div>
			</div>
			
			<div class="form-group">
				<label for="RPM" class="col-sm-6 control-label">Rate Per Mile</label>
				<div class="col-sm-6">
				<input class="form-control text-right" name="RPM" id="RPM" type="text"  placeholder="Rate Per Mile"  value="'.$edit['saved_details'][0]['RPM'].'" readonly="readonly">
				</div>
			</div>
			
			<div class="form-group">
				<label for="FUEL_COST" class="col-sm-6 control-label">Total</label>
				<div class="col-sm-6">
				<div class="input-group">
				<span class="input-group-addon">$</span>
				<input class="form-control text-right" name="RPM_MILLEAGE" id="RPM_MILLEAGE" type="text"  placeholder="Milleage"  value="'.$edit['saved_details'][0]['RPM_RATE'].'" readonly="readonly">
				</div>
				</div>
			</div>
			<hr>
			
			'.$avg.'
			
			<div class="form-group">
				<label for="FUEL_COST" class="col-sm-6 control-label">Fuel Surcharge</label>
				<div class="col-sm-6">
				<div class="input-group">
				<span class="input-group-addon">$</span>
				<input class="form-control text-right" name="FUEL_COST" id="FUEL_COST" type="text"  placeholder="Fuel Cost"  value="'.$edit['saved_details'][0]['FUEL_COST'].'" readonly="readonly">
				</div>
				</div>
			</div>
 			</div><!-- Duncan -->
           
 			<div class="well well-sm">
 			<div class="form-group">
				<label for="ADJUSTMENT_CHARGE_TITLE" class="col-sm-6 control-label">'.$edit['saved_details'][0]['ADJUSTMENT_CHARGE_TITLE'].'</label>
				<div class="col-sm-6">
				<div class="input-group">
				<span class="input-group-addon">$</span>
				<input class="form-control text-right"  type="text"   name="ADJUSTMENT_CHARGE"  id="ADJUSTMENT_CHARGE"    placeholder="Adjustment charge"  value="'.$edit['saved_details'][0]['ADJUSTMENT_CHARGE'].'" readonly="readonly" />
				</div>
				</div>
			</div>


			<div class="form-group">
				<label for="SELECTION_FEE" class="col-sm-6 control-label">Selection Fee</label>
				<div class="col-sm-6">
				<div class="input-group">
				<span class="input-group-addon">$</span>
				<input class="form-control text-right" name="SELECTION_FEE" id="SELECTION_FEE" type="text"  value="'.$edit['saved_details'][0]['SELECTION_FEE'].'" readonly>
				</div>
				</div>
			</div>

			<div class="form-group">
				<label for="DISCOUNT" class="col-sm-6 control-label">Discount</label>
				<div class="col-sm-6">
				<div class="input-group">
				<span class="input-group-addon">$</span>
				<input class="form-control text-right" name="DISCOUNT" id="DISCOUNT" type="text"  value="'.$edit['saved_details'][0]['DISCOUNT'].'" readonly>
				</div>
				</div>
			</div>
			</div>
			
			
		</div>
       </div> 
        
        <div class="form-group">
        <div class="col-sm-4">
        			<div class="form-group">
				<label class="col-sm-4 control-label" for="OTHER">Other</label>
              </div>
			  
			  <div class="form-group">
				<div class="col-sm-5"></div>
				<div class="col-sm-1">
				<input type="checkbox" value="1" id="HAZMAT" name="HAZMAT" '.$check.' disabled="disabled">
                </div>
              <label style="text-align:left;" class="col-sm-3 control-label" for="HAZMAT">Hazmat Only  </label>
                </div>
				
				<div class="form-group">
				<div class="col-sm-5"></div>
              <div class="col-sm-1">
				<input type="checkbox" value="1" id="TEMP_CONTROLL" name="TEMP_CONTROLL" '.$checked.' disabled="disabled">
                </div> 
              <label style="text-align:left;" class="col-sm-5 control-label" for="TEMP_CONTROLL">Temp Controlled Only   </label>
			</div>
            </div>

		<div class="col-sm-8">';
	if(count($edit['saved_details'])>0 && $edit['saved_details'][0]['BILL_RATE_ID']!="")
		echo '
			<div class="form-group">
				<label for="BROKER_INFO" class="col-sm-4 control-label"> Client Rates </label>
                </div>
			
               <div class="form-group">
			<div style="display:none;" id="reload_client_result"><img border="1" style="position:absolute; margin-left:250px;margin-top:10px;" src="images/loading.gif"></div>
            <table class="table table-striped table-condensed table-bordered table-hover">
            <thead>
              <tr class="exspeedite-bg">
                <th class="text-center">Rate Code</th>
				<th class="text-center">Rate Name</th>
                <th class="text-center">Category</th>
				<th class="text-center">Rate( $ )</th>
				<th class="text-center">QTY</th>
				
              </tr>
            </thead>
            <tbody>
			'.$client_rate.'
		 
			</tbody>
          </table>
        </div>';
    else
    	echo '<br><br><br><br>';

		$total2 = (float) $edit['saved_details'][0]['TOTAL'];

		if( $this->multi_currency ) {
	        echo ' 
			<div class="form-group">
				<label for="TOTAL" class="col-sm-8 control-label input-lg" style="padding-top: 7px;">'.$edit['saved_details'][0]['CURRENCY'].' Subtotal</label>
				<div class="col-sm-4">
					<div class="input-group">
						<span class="input-group-addon">$</span>
						<input class="form-control text-right input-lg" name="TOTAL"
						id="TOTAL" type="text" placeholder="Subtotal"
						value="'.number_format($edit['saved_details'][0]['TOTAL'], 2).'" readonly="readonly">
					</div>
				</div>
			';
			
			//! Duncan - SCR 26 - add tax info
			if( isset($edit['tax']) && is_array($edit['tax'])) {
				foreach( $edit['tax'] as $tax ) {
					$total2 += (float) $tax['AMOUNT'];
					echo '
				<label for="TAX" class="col-sm-8 control-label input-lg" style="padding-top: 7px;">'.$tax['TAX'].'</label>
				<div class="col-sm-4">
					<div class="input-group">
						<span class="input-group-addon">$</span>
						<input class="form-control text-right input-lg" name="TAX"
						type="text" placeholder="Tax"
						value="'.number_format($tax['AMOUNT'], 2).'" readonly="readonly">
					</div>
				</div>';
				}
			} else {
				echo '<div class="col-sm-12 alert alert-danger pad" role="alert"><h4 class="text-danger"><strong><span class="glyphicon glyphicon-warning-sign"></span> No tax:</strong> '.$edit['tax_issue'].'</h4><h4>Fix this or tax will be missing on the invoice.</h4></div>';
			}
		}
		
		
		echo '
			<label for="TOTAL2" class="col-sm-8 control-label input-lg" style="padding-top: 7px;">'.$edit['saved_details'][0]['CURRENCY'].' Total</label>
			<div class="col-sm-4">
				<div class="input-group">
					<span class="input-group-addon">$</span>
					<input class="form-control text-right input-lg" name="TOTAL2"
					id="TOTAL2" type="text" placeholder="Total"
					value="'.number_format($total2, 2).'" readonly="readonly">
				</div>
			</div>
		
		</div>
           
          
	</div>
	
	</div>
	
</form>
</div>
</div>';
	
	}
	
	##----- manage fsc rates at admin side---------##
	public function render_manage_fsc($result)
	{
		$tab='';
		$tab.='<tbody>';
		//! SCR# 291 - deal with dates
		$date_format = $_SESSION['ios'] == 'true' ? "Y-m-d" : "m/d/Y";
		
		echo $str="<div class='container-full' role='main'>
		<h3><img src='images/searchdate.png' alt='zone_icon' height='24'> Manage FSC Schedule
		<div class='btn-group'>
         <a class='btn btn-sm btn-default' href='index.php'><span class='glyphicon glyphicon-remove'></span> Back</a>
		</div>
		</h3>
		<form class='form-horizontal' method='post' name='add-fsc' id='add-fsc' action='' enctype='multipart/form-data'>

		<table class='table table-striped table-condensed table-bordered table-hover' style='width:100%'>
		<tbody>
		<tr>		
		<td>FSC # &nbsp;</td><td><input type='text' name='fsc' id='fsc' class='form-control' value='".$result['fsc_unique_id']."' readonly='readonly' style='width:40%;'/></td>
		<td>FSC Name &nbsp;</td><td><input type='text' name='fsc_name' id='fsc_name' class='form-control' value=''  style='width:60%;'/></td>
		<td>FSC Average Price &nbsp;</td><td><input type='text' name='fsc_average_price' id='fsc_average_price' class='form-control' value=''  style='width:60%;'/></td>
		</tr>
		<tr>
		<td>Start Date &nbsp;</td><td><div style=\"position: relative\"><input type='".
		($_SESSION['ios'] == 'true' ? 'date' : 'text')."' name='fsc_starting_date' id='fsc_starting_date' class='form-control".($_SESSION['ios'] != 'true' ? ' date' : '').
		"' value='' style='width:60%;'/></div></td>
		<td>End Date &nbsp;</td><td><div style=\"position: relative\"><input type='".
		($_SESSION['ios'] == 'true' ? 'date' : 'text')."' name='fsc_end_date' id='fsc_end_date' class='form-control".($_SESSION['ios'] != 'true' ? ' date' : '').
		"' value='' style='width:60%;'/></div></td>
		</tr>
		<tr>
		<td>Upload CSV &nbsp;</td><td><input type='file' name='fsc_schedule' id='fsc_schedule' class='btn btn-sm'/></td>
		<td><button type='submit' name='add_fsc_schedule' id='add_fsc_schedule'  class='btn btn-sm btn-success' onclick='return validte_schedule(&quot;add&quot;);'/><span class='glyphicon glyphicon-ok'></span>Add FSC Schedule</button></td>
		<td colspan=\"3\" class=\"info\">If you do not specify the CSV file, it will create 1 dummy row.<br>You can then add others manually. Get the template <a href=\"include/fuel_surcharge.csv\" target=\"_blank\">here</a></td>
		
			</tr>
			</tbody>
			</table>
		</form>
		<div class='table-responsive'>
		<div id='reload_search_result' style='display:none;'><img src='images/loading.gif' border='1' style='position:absolute; margin-left:250px;margin-top:10px;'></div>
		<table id='FSC_TABLE' class='table table-striped table-condensed table-bordered table-hover' style='width:70%' >
		<thead>
		<tr class='exspeedite-bg'>
        <th style='text-align:center;'>FSC ID</th>
		<th style='text-align:center;'>FSC Name</th>
		<th style='text-align:center;'>FSC Average Price</th>
		<th style='text-align:center;'>FSC Average Rate</th>
		<th style='text-align:center;'>FSC Starting Date</th>
		<th style='text-align:center;'>FSC Ending Date</th>
		<th style='text-align:center;'>Action</th>
        </tr>
		</thead>
		".$result['tab']."
		</table>
		</div>

</div>";
	}
	
	##------- update single fsc rates -------##
	public function render_edit_single_fsc($result)
	{
		$dec = $result['FSC_4DIGIT'] ? 'step="0.0001"' : 'step="0.01"';
		$dec2 = $result['FSC_4DIGIT'] ? 4 : 2;

		//! SCR# 291 - deal with dates
		$date_format = $_SESSION['ios'] == 'true' ? "Y-m-d" : "m/d/Y";

		echo $str='<div class="container-full theme-showcase" role="main">

<div class="well  well-lg">
<form role="form" class="form-horizontal" action="exp_view_fsc.php?TYPE=editfsc&CODE='.$result['CODE'].'" method="post"  
				name="edit_fsc_rate" id="edit_fsc_rate"><h2><img src="images/searchdate.png" alt="manual_miles_icon" height="24"> Edit FSC Rate
		<div class="btn-group">
			<button class="btn btn-sm btn-success" name="update_single_fsc" type="submit" id="update_single_fsc" onclick="return edit_fsc();"><span class="glyphicon glyphicon-ok"></span> Save Changes</button>
			<a class="btn btn-sm btn-default" href="exp_view_fsc.php?TYPE=edit&CODE='.$result['fsc_detail'][0]['FSC_UNIQUE_ID'].'"><span class="glyphicon glyphicon-remove"></span> Back</a>
			</div>
	</h2>
	'.($this->debug ? '<input name="debug" id="debug" type="hidden" value="true">
' : '').'	
	
	<div class="form-group">
		<div class="col-sm-4">
			
			<div class="form-group">
				<label for="low_per_gallon" class="col-sm-6 control-label">Low Per Gallon</label>
				<div class="col-sm-6">
					
			<input class="form-control text-right" name="low_per_gallon" id="low_per_gallon" type="number" step="0.001"  placeholder="Low Per Gallon" maxlength="20" value="'.$result['fsc_detail'][0]['LOW_PER_GALLON'].'" >
				
	
				
				</div>
			</div>
			<div class="form-group">
				<label for="high_per_gallon" class="col-sm-6 control-label">High Per Gallon</label>
				<div class="col-sm-6">
					
			<input class="form-control text-right" name="high_per_gallon" id="high_per_gallon" type="number" step="0.001"  placeholder="High Per Gallon" maxlength="20" value="'.$result['fsc_detail'][0]['HIGH_PER_GALLON'].'" >
				</div>
			</div>
			<div class="form-group">
				<label for="flat_amount" class="col-sm-6 control-label">Flat Amount</label>
				<div class="col-sm-6">
					
			<input class="form-control text-right" name="flat_amount" id="flat_amount" type="number" '.$dec.'  placeholder="Flat Amount" value="'.number_format($result['fsc_detail'][0]['FLAT_AMOUNT'],$dec2,'.','').'" >
				</div>
			</div>
			
			<div class="form-group">
				<label for="per_mile_adjust" class="col-sm-6 control-label">Per Mile Adjust</label>
				<div class="col-sm-6">
					
			<input class="form-control text-right" name="per_mile_adjust" id="per_mile_adjust" type="number" '.$dec.'  placeholder="Per Mile Adjust" maxlength="20" value="'.number_format($result['fsc_detail'][0]['PER_MILE_ADJUST'],$dec2,'.','').'" >
				</div>
			</div>
			
			<div class="form-group">
				<label for="percent" class="col-sm-6 control-label">Percent</label>
				<div class="col-sm-6">
					
			<input class="form-control text-right" name="percent" id="percent" type="number" '.$dec.'  placeholder="Percent" maxlength="20" value="'.number_format($result['fsc_detail'][0]['PERCENT'],$dec2,'.','').'" >
				</div>
			</div>
			
			<div class="form-group">
				<label for="start_date" class="col-sm-6 control-label">Start Date</label>
				<div class="col-sm-6">
					
			<input class="form-control'.($_SESSION['ios'] != 'true' ? ' date' : '').'" name="start_date" id="start_date" type="'.
		($_SESSION['ios'] == 'true' ? 'date' : 'text').'"   maxlength="20" value="'.date($date_format,strtotime($result['fsc_detail'][0]['START_DATE'])).'" >
				</div>
			</div>
			
			<div class="form-group">
				<label for="end_date" class="col-sm-6 control-label">End Date</label>
				<div class="col-sm-6">
					
			<input class="form-control'.($_SESSION['ios'] != 'true' ? ' date' : '').'" name="end_date" id="end_date" type="'.
		($_SESSION['ios'] == 'true' ? 'date' : 'text').'"   maxlength="20" value="'.date($date_format,strtotime($result['fsc_detail'][0]['END_DATE'])).'" >
				</div>
			</div>
			
		</div>
		<div class="col-sm-4">
			<br><br><br><br><br><br><br><br><br>
			<div class="alert alert-info">
				<p><strong>Percent</strong> is like 28.5 for 28.5% <strong>NOT</strong> 0.285</p>
			</div>
		</div>
		<div class="col-sm-4">
		</div>
	</div>
</form>
</div>
</div>';
	}
	
		##-----update client bill under each shipment--------##
	public function render_update_client_bill_history($edit)
	{$str='';//echo count($edit['saved_details']);exit;
		$item_list_table = sts_item_list::getInstance($this->table->database, $this->debug);
			/*if(count($edit['saved_details'])==0)
			{
				$str='<span style="color:#F00;font-size:15px;margin-left:40px;">CLIENT BILLING INFORMATION IS NOT AVAILABLE!</span>';
			}*/
		$client_rate='';
		$client_total=0;
		if(count($edit['saved_details'])>0 && $edit['saved_details'][0]['BILL_RATE_ID']!="")
		{
			foreach($edit['saved_details'] as $ed)
			{
				$client_rate.='<tr style="cursor:pointer;">
			<td class="text-center">'.$ed['RATE_CODE'].'</td>
			<td class="text-center">'.$ed['RATE_NAME'].'</td>
                <td class="text-center">'.$ed['CATEGORY'].'</td>
				<td class="text-center"><input type="text" name="RATE[]" id="RATE" value="'.$ed['RATES'].'"  onkeyup="return add_rates(this.value); "/></td>
				<td class="text-center"><input type="text" name="QTY[]" id="QTY" value="'.((float)$ed['RATES']/35).'" /></td>
				</tr>
				<input type="hidden" name="CODE_ID[]" value="'.$ed['BILL_RATE_ID'].'" />
				<input type="hidden" name="CODE[]" value="'.$ed['RATE_CODE'].'" />
				<input type="hidden" name="CATEGORY[]" value="'.$ed['CATEGORY'].'" /> ';
				
				$client_total=$client_total+$ed['RATES'];
			}
			$client_rate.='<tr class="exspeedite-bg"><td colspan="3" class="text-center">Total</td><td class="text-center"><input type="text" name="TOTAL_RATE" id="TOTAL_RATE" value="'.$client_total.'" style="color:#030303" readonly="readonly" />  $</td></tr>';
		}
		else
		{
			$client_rate.='<tr style="cursor:pointer;"><td class="text-center" colspan="5">NO RATES ARE ASSIGNED !</td></tr><tr class="exspeedite-bg"><td colspan="2" class="text-center">Total</td><td class="text-center"><input type="text" name="TOTAL_RATE" id="TOTAL_RATE" value="0" style="color:#030303" readonly="readonly"/>  $</td></tr>';
		}
		$check=$checked='';
		if($edit['saved_details'][0]['HAZMAT']=='1')
		{$check='checked="checked"';}
		if($edit['saved_details'][0]['TEMP_CONTROLLED']=='1')
		{$checked='checked="checked"';}
		$avg_price='';
		if($edit['saved_details'][0]['FSC_AVERAGE_RATE']!="" && $edit['saved_details'][0]['FSC_COLUMN']!="")
		{
			$avg_price='<div class="form-group">
				<label for="FUEL_COST" class="col-sm-6 control-label">'.$edit['saved_details'][0]['FSC_COLUMN'].'</label>
				<div class="col-sm-6">
				<input class="form-control text-right" name="FSC_AVERAGE_RATE" id="FSC_AVERAGE_RATE" type="text"  placeholder="Fuel Average Rate"  value="'.$edit['saved_details'][0]['FSC_AVERAGE_RATE'].'" readonly="readonly">
					<input class="form-control" name="FSC_AVERAGE_RATE_COL" id="FSC_AVERAGE_RATE_COL" type="hidden"  value="'.$edit['saved_details'][0]['FSC_COLUMN'].'" >
				</div>
			</div>';
		}
		if($edit['saved_details'][0]['FSC_COLUMN']!="")
		{
			echo '<input type="hidden" name="fsc_col_per_mile" id="fsc_col_per_mile" value="'.$edit['saved_details'][0]['FSC_COLUMN'].'"/>
					 <input type="hidden" name="fsc_col_per_mile_val" id="fsc_col_per_mile_val" value="'.$edit['saved_details'][0]['FUEL_COST'].'"/>';
		}
		else {
			echo '<input type="hidden" name="fsc_col_per_mile" id="fsc_col_per_mile" value=""/>
					 <input type="hidden" name="fsc_col_per_mile_val" id="fsc_col_per_mile_val" value=""/>';
		}
		//!XYZZY
		echo '<div class="container-full theme-showcase" role="main">
<div class="well  well-md">
<form role="form" class="form-horizontal" action=""  method="post" enctype="multipart/form-data" name="updateclientbilling" id="updateclientbilling" autocomplete="off"><h3><img src="images/driver_icon.png" alt="driver_icon" height="24"> 
Edit Client Billing	#'.$edit['shipment_details']['SHIPMENT_CODE'].
($edit['SS_NUMBER'] ? ' / '.$edit['SS_NUMBER'] : '').
' '.$this->render_currency_menu($edit['saved_details'][0]['CURRENCY']).
$item_list_table->render_terms_menu($edit['saved_details'][0]['TERMS']).'<button  type="submit" id="update_client_bill" name="update_client_bill" class="btn btn-sm btn-success"><span class="glyphicon glyphicon-ok"></span> Save Changes</button>
			<a class="btn btn-sm btn-default" href="exp_shipment_bill.php?id='.$edit['shipment_details']['SHIPMENT_CODE'].'"><span class="glyphicon glyphicon-remove"></span> Back</a>
			'.$str.'
			
	</h3>
	<div class="form-group">
	';
	
	//! EDI banner
	$this->render_billing_edi_banner( $edit );
	
	echo '
		<div class="col-sm-4">
			<div class="form-group well well-sm tighter">
				<label class="col-sm-4 control-label"> LOAD# </label>
				<div class="col-sm-8 text-right">'.$edit['shipment_details']['LOAD_CODE'].'</div>
			</div>
		</div>
		<div class="col-sm-2">
			<div class="form-group well well-sm tighter">
				<label class="col-sm-4 control-label">#&nbsp;Picks</label>
				<div class="col-sm-8 text-right">'.$edit['no_of_picks'].'</div>
			</div>
		</div>
		<div class="col-sm-2">
			<div class="form-group well well-sm tighter">
				<label class="col-sm-4 control-label">#&nbsp;Drops</label>
				<div class="col-sm-8 text-right">'.$edit['no_of_drops'].'</div>
			</div>
		</div>
		<div class="col-sm-4">
			<div class="form-group well well-sm tighter">
				<label class="col-sm-4 control-label"> Business&nbsp;Code </label>
				<div class="col-sm-8">'.$edit['shipment_details']['BUSINESS_CODE_NAME'].'</div>
			</div>
		</div>
	</div>
    <div class="form-group">
		<div class="col-sm-4">
			<div class="form-group well well-sm">
				<label for="SHIPPER_NAME" class="col-sm-4 control-label">Shipper</label>
				<div class="col-sm-8">
				<input class="form-control" name="SHIPPER_NAME" id="SHIPPER_NAME" type="text" placeholder="Shipper" maxlength="50" value="'.$edit['shipment_details']['SHIPPER_NAME'].'" readonly="readonly">
				</div>
				<label for="SHIPPER_ADDR1" class="col-sm-4 control-label">Address</label>
				<div class="col-sm-8">
				<input class="form-control" name="SHIPPER_ADDR1" id="SHIPPER_ADDR1" type="text"  placeholder="Addr1" maxlength="50" value="'.$edit['shipment_details']['SHIPPER_ADDR1'].'" readonly="readonly">
				</div>
				<label for="BOL_NUMBER" class="col-sm-4 control-label">BOL#</label>
				<div class="col-sm-8">
					<input class="form-control" id="BOL_NUMBER" type="text"   value="'.$edit['shipment_details']['BOL_NUMBER'].'" onkeyup="return upd_bol_number('.$edit['shipment_details']['SHIPMENT_CODE'].');">
				
				</div>
			'.($edit['LOG_HOURS'] ? '	<label for="NOT_BILLED_HOURS" class="col-sm-4 control-label"><span class="glyphicon glyphicon-time"></span> Hours</label>
				<div class="col-sm-8">
					<input class="form-control text-right" name="NOT_BILLED_HOURS" id="NOT_BILLED_HOURS" type="text" value="'.$edit['saved_details'][0]['NOT_BILLED_HOURS'].'">
				</div>
			' : '').'</div>
		</div>
		<div class="col-sm-8">
			<div class="form-group">
				<div class="col-sm-6">
					<div class="form-group well well-sm tighter">
						<label for="CONS_NAME" class="col-sm-4 control-label">Consignee</label>
						<div class="col-sm-8">
						<input class="form-control" name="CONS_NAME" id="CONS_NAME" type="text"  placeholder="Consignee" maxlength="50" value="'.$edit['shipment_details']['CONS_NAME'].'" readonly>
						</div>
						<label for="CONS_ADDR1" class="col-sm-4 control-label">Address</label>
						<div class="col-sm-8">
						<input class="form-control" name="CONS_ADDR1" id="CONS_ADDR1" type="text"  placeholder="Addr1" maxlength="50" value="'.$edit['shipment_details']['CONS_ADDR1'].'" readonly>
						</div>
					</div>
				</div>
				<div class="col-sm-6">
					<div class="form-group well well-sm tighter">
						<label for="BILLTO_NAME" class="col-sm-4 control-label">Bill-To</label>
						<div class="col-sm-8">
						<input class="form-control" name="BILLTO_NAME" id="BILLTO_NAME" type="text"  placeholder="Bill-To" maxlength="50"   value="'.$edit['shipment_details']['BILLTO_NAME'].'" readonly>
						</div>
						<label for="BILLTO_ADDR1" class="col-sm-4 control-label">Address</label>
						<div class="col-sm-8">	
						<input class="form-control" name="BILLTO_ADDR1" id="BILLTO_ADDR1" type="text"  placeholder="Addr1" maxlength="50" value="'.$edit['shipment_details']['BILLTO_ADDR1'].'" readonly>
						</div>
					</div>
				</div>
				<div class="col-sm-12">
					<div class="form-group well well-sm tighter">
						<label for="NOTES" class="col-sm-2 control-label">Disp Notes</label>
						<div class="col-sm-10">
						<textarea name="NOTES" id="NOTES" class="form-control" style="resize=none;" readonly>'.$edit['shipment_details']['NOTES'].'</textarea>
						</div>
					</div>
				</div>
			</div>
		</div>
	</div>
	<div class="form-group">
		<div class="col-sm-4">
		<div class="form-group">
				<label for="CHARGES" class="col-sm-5 control-label">Pickup Appointment</label>
				<div class="col-sm-6">
				<input class="form-control"  type="text"   value="'.$edit['expected_pickup_time'].'" readonly="readonly">
				</div>
			</div>
			</div>
			<div class="col-sm-4">
			<div class="form-group">
				<label for="CHARGES" class="col-sm-5 control-label">Actual Arrival Time</label>
				<div class="col-sm-6">
				<input class="form-control"  type="text"   value="'.$edit['actual_pickup_time'].'" readonly="readonly">
				</div>
			</div>
			</div>
			<div class="col-sm-4">
			<div class="form-group">
				<label for="CHARGES" class="col-sm-5 control-label">Actual Departure Time</label>
				<div class="col-sm-6">
				<input class="form-control"  type="text"   value="'.$edit['actual_pickup_depart_time'].'" readonly="readonly">
				</div>
			</div>
			</div>
			</div>
			<div class="form-group">
			<div class="col-sm-4">
			<div class="form-group">
				<label for="CHARGES" class="col-sm-5 control-label">Delivery Appointment</label>
				<div class="col-sm-6">
				<input class="form-control"  type="text"   value="'.$edit['expected_drop_time'].'" readonly="readonly">
				</div>
			</div>
			</div>
			<div class="col-sm-4">
			<div class="form-group">
				<label for="CHARGES" class="col-sm-5 control-label">Actual Arrival Time</label>
				<div class="col-sm-6">
				<input class="form-control"  type="text"   value="'.$edit['actual_drop_arrival_time'].'" readonly="readonly">
				</div>
			</div>
		</div>
		<div class="col-sm-4">
			<div class="form-group">
				<label for="CHARGES" class="col-sm-5 control-label">Actual Departure Time</label>
				<div class="col-sm-6">
				<input class="form-control"  type="text"   value="'.$edit['actual_drop_time'].'" readonly="readonly">
				</div>
			</div>
		</div>
		</div>

	<div class="form-group">
		<div class="col-sm-4">
			
            <div class="well well-sm"><!-- Duncan -->
			<div class="form-group">
				<label for="CHARGES" class="col-sm-6 control-label">Pallets</label>
				<div class="col-sm-6">
				<input class="form-control text-right" name="PALLETS" id="PALLETS" type="text"  placeholder="No.of pallets" value="'.$edit['saved_details'][0]['PALLETS'].'" onkeyup="return cal_pallet_rate('.$edit['client_id'].',&quot;'.$edit['shipment_details']['CONS_NAME'].'&quot;,&quot;'.$edit['shipment_details']['BILLTO_NAME'].'&quot;,&quot;'.$edit['shipment_details']['CONS_CITY'].'&quot;);"

				 >
				</div>
			</div>
			
			<div class="form-group">
				<label for="CHARGES" class="col-sm-6 control-label">Per Pallet Rate</label>
				<div class="col-sm-6">
				<input class="form-control text-right" name="PER_PALLETS" id="PER_PALLETS" type="text"  placeholder="No.of pallets" value="'.$edit['saved_details'][0]['PER_PALLETS'].'"  onkeyup="return cal_pallet_per_rate('.$edit['client_id'].',&quot;'.$edit['shipment_details']['CONS_NAME'].'&quot;,&quot;'.$edit['shipment_details']['BILLTO_NAME'].'&quot;,&quot;'.$edit['shipment_details']['CONS_CITY'].'&quot;);" >
				</div>
			</div>
			
			<div class="form-group">
				<label for="CHARGES" class="col-sm-6 control-label">Pallet Total</label>
				<div class="col-sm-6">
				<div class="input-group">
				<span class="input-group-addon">$</span>
				<input class="form-control text-right" name="PALLETS_RATE" id="PALLETS_RATE" type="text"  placeholder="Pallet Rate" value="'.$edit['saved_details'][0]['PALLETS_RATE'].'" onkeyup="return calculate_total();">
				</div>
				</div>
			</div>
			
			<div class="form-group">
				<label for="CHARGES" class="col-sm-6 control-label">Handling Pallets</label>
				<div class="col-sm-6">
				<div class="input-group">
				<span class="input-group-addon">$</span>
				<input class="form-control text-right" name="HAND_PALLET" id="HAND_PALLET" type="text"  value="'.$edit['saved_details'][0]['HAND_PALLET'].'" onkeyup="return calculate_total();">
				</div>
				</div>
			</div>
			</div><!-- Duncan -->
			
			<div class="well well-sm"><!-- Duncan -->
			<div class="form-group">
				<label for="CHARGES" class="col-sm-6 control-label">Handling Charges</label>
				<div class="col-sm-6">
				<div class="input-group">
				<span class="input-group-addon">$</span>
				<input class="form-control text-right" name="HAND_CHARGES" id="HAND_CHARGES" type="text"  value="'.$edit['saved_details'][0]['HAND_CHARGES'].'" onkeyup="return calculate_total();">
				</div>
				</div>
			</div>
			
			<div class="form-group">
				<label for="CHARGES" class="col-sm-6 control-label">Freight Charges</label>
				<div class="col-sm-6">
				<div class="input-group">
				<span class="input-group-addon">$</span>
				<input class="form-control text-right" name="FREIGHT_CHARGES" id="FREIGHT_CHARGES" type="text"  value="'.$edit['saved_details'][0]['FREIGHT_CHARGES'].'" onkeyup="return calculate_total();">
				</div>
				</div>
			</div>
			</div><!-- Duncan -->
			
			<div class="well well-sm"><!-- Duncan -->
            <div class="form-group">
			'.(! empty($edit['EQUIPMENT']) ? '<div class="form-group">
				<label for="EQUIPMENT" class="col-sm-5 control-label">Equipment</label>
				<div class="col-sm-6 bg-warning">'.$edit['EQUIPMENT'].'</div>
				</div>' : '').'
				<label for="EXTRA_CHARGES" class="col-sm-5 control-label">Extra Charges</label>
				<div class="col-sm-7">
				<div class="input-group">
				<span class="input-group-addon">$</span>
				<input class="form-control text-right" name="EXTRA_CHARGES" id="EXTRA_CHARGES" type="text"  value="'.$edit['saved_details'][0]['EXTRA_CHARGES'].'"
				placeholder="Extra Charges" onkeyup="return calculate_total();" >
				</div>
				</div>
			</div>

			<div class="form-group">
				<label for="EXTRA_CHARGES_NOTE" class="col-sm-5 control-label">Description</label>
				<div class="col-sm-7">
				
				<textarea name="EXTRA_CHARGES_NOTE" id="EXTRA_CHARGES_NOTE" class="form-control" style="resize=none;">'.$edit['saved_details'][0]['EXTRA_CHARGES_NOTE'].'</textarea>
				</div>
			</div>
			</div>
			
			<div class="well well-sm"><!-- Duncan -->
			<div class="form-group">
				<label for="STOP_OFF" class="col-sm-5 control-label">Stop Off</label>
				<div class="col-sm-7">
				<div class="input-group">
				<span class="input-group-addon">$</span>
				<input class="form-control text-right" name="STOP_OFF" id="STOP_OFF" type="text"  value="'.$edit['saved_details'][0]['STOP_OFF'].'"
				placeholder="Extra Charges" onkeyup="return calculate_total();" >
				</div>
				</div>
			</div>
			
			<div class="form-group">
				<label for="STOP_OFF_NOTE" class="col-sm-5 control-label">Notes</label>
				<div class="col-sm-7">
				<textarea class="form-control" name="STOP_OFF_NOTE" id="STOP_OFF_NOTE" >'.$edit['saved_details'][0]['STOP_OFF_NOTE'].'</textarea>
				</div>
			</div>
			</div>
			
			<div class="form-group">
				<label for="EXTRA_CHARGES" class="col-sm-6 control-label">Weekend/Holiday</label>
				<div class="col-sm-6">
				<div class="input-group">
				<span class="input-group-addon">$</span>
				<input class="form-control text-right" name="WEEKEND" id="WEEKEND" type="text"  value="'.$edit['saved_details'][0]['WEEKEND'].'"
				placeholder="Extra Charges" onkeyup="return calculate_total();" >
				</div>
				</div>
			</div>
            </div>
			<div class="col-sm-4">
            
            <div class="well well-sm"><!-- Duncan -->
 			<div id="det_loader" style="display:none;"><img border="1" style="position:absolute; margin-left:250px;margin-top:10px;" src="images/loading.gif"></div>
           <h4>Loading Detention</h4>
			<div class="form-group">
				<label for="CHARGES" class="col-sm-6 control-label">Free Hours</label>
				<div class="col-sm-6">
				<input class="form-control text-right" name="FREE_DETENTION_HOUR" id="FREE_DETENTION_HOUR" type="text"  value="'.$edit['saved_details'][0]['FREE_DETENTION_HOUR'].'" readonly="readonly" >
				</div>
			</div>
			
			
			<div class="form-group">
				<label for="CHARGES" class="col-sm-6 control-label">Hours</label>
				<div class="col-sm-6">
				<input class="form-control text-right" name="DETENTION_HOUR" id="DETENTION_HOUR" type="text"  value="'.$edit['saved_details'][0]['DETENTION_HOUR'].'" onkeyup="return calc_det_rate('.$edit['client_id'].',&quot;'.$edit['shipment_details']['CONS_NAME'].'&quot;);" >
				</div>
			</div>
			
			<div class="form-group">
				<label for="CHARGES" class="col-sm-6 control-label"> Rate Per Hour</label>
				<div class="col-sm-6">
				<input class="form-control text-right" name="RATE_PER_HOUR" id="RATE_PER_HOUR" type="text"  value="'.$edit['saved_details'][0]['RATE_PER_HOUR'].'" onkeyup="javascript:return calc_det_rate_per_hr('.$edit['client_id'].',&quot;'.$edit['shipment_details']['CONS_NAME'].'&quot;);">
				</div>
			</div>
			
			<div class="form-group">
				<label for="CHARGES" class="col-sm-6 control-label">Total</label>
				<div class="col-sm-6">
				<div class="input-group">
				<span class="input-group-addon">$</span>
				<input class="form-control text-right" name="DETENTION_RATE" id="DETENTION_RATE" type="text"  value="'.$edit['saved_details'][0]['DETENTION_RATE'].'" onkeyup="return calculate_total();">
				</div>
				</div>
			</div>
			</div><!-- Duncan -->
			
            <div class="well well-sm"><!-- Duncan -->
            <h4>Unloading Detention</h4>
			<div class="form-group">
				<label for="CHARGES" class="col-sm-6 control-label">Free Hours</label>
				<div class="col-sm-6">
				<input class="form-control text-right" name="FREE_UN_DETENTION_HOUR" id="FREE_UN_DETENTION_HOUR" type="text"  value="'.$edit['saved_details'][0]['FREE_UN_DETENTION_HOUR'].'" readonly="readonly" >
				</div>
			</div>
			
				<div class="form-group">
				<label for="CHARGES" class="col-sm-6 control-label">Hours</label>
				<div class="col-sm-6">
				<input class="form-control text-right" name="UNLOADED_DETENTION_HOUR" id="UNLOADED_DETENTION_HOUR" type="text"  value="'.$edit['saved_details'][0]['UNLOADED_DETENTION_HOUR'].'" onkeyup="return calc_unloaded_det_rate('.$edit['client_id'].',&quot;'.$edit['shipment_details']['CONS_NAME'].'&quot;);">
				</div>
			</div>
			
			<div class="form-group">
				<label for="CHARGES" class="col-sm-6 control-label"> Rate Per Hour</label>
				<div class="col-sm-6">
				<input class="form-control text-right" name="UN_RATE_PER_HOUR" id="UN_RATE_PER_HOUR" type="text"  value="'.$edit['saved_details'][0]['UN_RATE_PER_HOUR'].'" onkeyup="javascript:return calc_unloaded_rate_per_hr('.$edit['client_id'].',&quot;'.$edit['shipment_details']['CONS_NAME'].'&quot;);">
				</div>
			</div>
			
			<div class="form-group">
				<label for="CHARGES" class="col-sm-6 control-label">Total</label>
				<div class="col-sm-6">
				<div class="input-group">
				<span class="input-group-addon">$</span>
				<input class="form-control text-right" name="UNLOADED_DETENTION_RATE" id="UNLOADED_DETENTION_RATE" type="text"  value="'.$edit['saved_details'][0]['UNLOADED_DETENTION_RATE'].'" onkeyup="return calculate_total();">
				</div>
				</div>
			</div>
			</div><!-- Duncan -->

			</div>
            <div class="col-sm-4">
            
			<div class="well well-sm"><!-- Duncan -->
			<div class="row">
			<div class="col-sm-6 col-sm-offset-6 text-center text-success">Information Only</div>
			</div>
			<div class="form-group">
				<label for="CARRIER" class="col-sm-6 control-label">Carrier</label>
				<div class="col-sm-6">
				<input class="form-control text-right" name="CARRIER" id="CARRIER" type="text"  placeholder="Carrier"   value="'.$edit['saved_details'][0]['CARRIER'].'" onkeyup="return calculate_total();">
				</div>
			</div>
			<div class="form-group">
				<label for="COD" class="col-sm-6 control-label">COD</label>
				<div class="col-sm-6">
				<input class="form-control text-right" name="COD" id="COD" type="text"  placeholder="COD "   value="'.$edit['saved_details'][0]['COD'].'" onkeyup="return calculate_total();">
				</div>
			</div>
			</div>

            <div class="well well-sm"><!-- Duncan -->
			<div class="form-group">
				<label for="FUEL_COST" class="col-sm-6 control-label">Distance</label>
				<div class="col-sm-6">
				<input class="form-control text-right" name="MILLEAGE" id="MILLEAGE" type="text"  placeholder="Fuel Cost"  value="'.$edit['saved_details'][0]['MILLEAGE'].'"  onkeyup="return calculate_rpm_amount();">
				</div>
			</div>
			
			<div class="form-group">
				<label for="RPM" class="col-sm-6 control-label">Rate Per Mile</label>
				<div class="col-sm-6">
				<input class="form-control text-right" name="RPM" id="RPM" type="text"  placeholder="Rate Per Mile"  value="'.$edit['saved_details'][0]['RPM'].'"onkeyup="return calculate_rpm_amount();">
				</div>
			</div>
			
			<div class="form-group">
				<label for="FUEL_COST" class="col-sm-6 control-label">Total</label>
				<div class="col-sm-6">
				<div class="input-group">
				<span class="input-group-addon">$</span>
				<input class="form-control text-right" name="RPM_MILLEAGE" id="RPM_MILLEAGE" type="text"  placeholder="Milleage"  value="'.$edit['saved_details'][0]['RPM_RATE'].'" onkeyup="return calculate_total();">
				</div>
				</div>
			</div>
			<hr>
			
			'.$avg_price.'
			
			<div class="form-group">
				<label for="FUEL_COST" class="col-sm-6 control-label">Fuel Surcharge</label>
				<div class="col-sm-6">
				<div class="input-group">
				<span class="input-group-addon">$</span>
				<input class="form-control text-right" name="FUEL_COST" id="FUEL_COST" type="text"  placeholder="Fuel Cost"  value="'.$edit['saved_details'][0]['FUEL_COST'].'"  onkeyup="return calculate_total();">
				</div>
				</div>
			</div>
			</div><!-- Duncan -->
			
			<div class="well well-sm">
			<div class="form-group">
				<label for="ADJUSTMENT_CHARGE_TITLE" class="col-sm-6 control-label"><input class="form-control"  type="text"   name="ADJUSTMENT_CHARGE_TITLE"  id="ADJUSTMENT_CHARGE_TITLE"    placeholder="Adjustment charge"  value="'.$edit['saved_details'][0]['ADJUSTMENT_CHARGE_TITLE'].'"/></label>
				<div class="col-sm-6">
				<div class="input-group">
				<span class="input-group-addon">$</span>
				<input class="form-control text-right"  type="text"   name="ADJUSTMENT_CHARGE"  id="ADJUSTMENT_CHARGE"    placeholder="Adjustment charge"  value="'.$edit['saved_details'][0]['ADJUSTMENT_CHARGE'].'"  onkeyup="return calculate_total();"/>
				</div>
				</div>
			</div>

			<div class="form-group">
				<label for="SELECTION_FEE" class="col-sm-6 control-label">Selection Fee</label>
				<div class="col-sm-6">
				<div class="input-group">
				<span class="input-group-addon">$</span>
				<input class="form-control text-right" name="SELECTION_FEE" id="SELECTION_FEE" type="text"  value="'.$edit['saved_details'][0]['SELECTION_FEE'].'" onkeyup="return calculate_total();">
				</div>
				</div>
			</div>

			<div class="form-group">
				<label for="DISCOUNT" class="col-sm-6 control-label">Discount</label>
				<div class="col-sm-6">
				<div class="input-group">
				<span class="input-group-addon">$</span>
				<input class="form-control text-right" name="DISCOUNT" id="DISCOUNT" type="text"  value="'.$edit['saved_details'][0]['DISCOUNT'].'" onkeyup="return calculate_total();">
				</div>
				</div>
			</div>
			</div>
			
            </div>
            </div>
            <div class="form-group">
		<div class="col-sm-4">
            
			<div class="form-group">
				<label class="col-sm-4 control-label" for="OTHER">Other</label>
              </div>
			  
			  <div class="form-group">
				<div class="col-sm-5"></div>
				<div class="col-sm-1">
				<input type="checkbox" value="1" id="HAZMAT" name="HAZMAT" '.$check.' >
                </div>
              <label style="text-align:left;" class="col-sm-3 control-label" for="HAZMAT">Hazmat Only  </label>
                </div>
				
				<div class="form-group">
				<div class="col-sm-5"></div>
              <div class="col-sm-1">
				<input type="checkbox" value="1" id="TEMP_CONTROLL" name="TEMP_CONTROLL" '.$checked.' >
                </div> 
              <label style="text-align:left;" class="col-sm-5 control-label" for="TEMP_CONTROLL">Temp Controlled Only   </label>
			</div>
			
			
		</div>
		<div class="col-sm-8">';
	if(count($edit['saved_details'])>0 && $edit['saved_details'][0]['BILL_RATE_ID']!="")
		echo '
			<div class="form-group">
				<label for="BROKER_INFO" class="col-sm-4 control-label"> Client Rates </label>
                </div>
			
               <div class="form-group">
			<div style="display:none;" id="reload_client_result"><img border="1" style="position:absolute; margin-left:250px;margin-top:10px;" src="images/loading.gif"></div>
            <table class="table table-striped table-condensed table-bordered table-hover">
            <thead>
              <tr class="exspeedite-bg">
                <th class="text-center">Rate Code</th>
				 <th class="text-center">Rate Name</th>
                <th class="text-center">Category</th>
				<th class="text-center">Rate( $ )</th>
				<th class="text-center">QTY</th>
				
              </tr>
            </thead>
            <tbody>
			'.$client_rate.'
		 
			</tbody>
          </table>
        </div>';
    else
    	echo '<br><br><br><br>';
        echo ' 
		<div class="form-group">
				<label for="TOTAL" class="col-sm-8 control-label input-lg">Total</label>
				<div class="col-sm-4">
				<div class="input-group">
				<span class="input-group-addon">$</span>
				<input class="form-control text-right input-lg" name="TOTAL" id="TOTAL" type="text"  
				placeholder="Total" value="'.$edit['saved_details'][0]['TOTAL'].'" readonly="readonly">
				</div>
				</div>
			</div>
           
          
	</div>
	
	</div>
	
</form>
</div>
</div>';
	
	}
	
	##------- add manually  single fsc rates -------##
	public function render_add_single_fsc($result)
	{
		$dec = $result['FSC_4DIGIT'] ? 'step="0.0001"' : 'step="0.01"';

		//! SCR# 291 - deal with dates
		$date_format = $_SESSION['ios'] == 'true' ? "Y-m-d" : "m/d/Y";

		echo $str='<div class="container-full theme-showcase" role="main">

<div class="well  well-lg">
<form role="form" class="form-horizontal" action="exp_view_fsc.php?TYPE=addfsc&CODE='.$result['FSC_UNIQUE_ID'].'" method="post"  
				name="edit_fsc_rate" id="edit_fsc_rate"><h2><img src="images/searchdate.png" alt="manual_miles_icon" height="24"> Add FSC Rate
		<div class="btn-group">
			<button class="btn btn-md btn-success" name="add_single_fsc" type="submit" id="add_single_fsc" onclick="return edit_fsc();"><span class="glyphicon glyphicon-ok"></span> Save Changes</button>
			<a class="btn btn-md btn-default" href="exp_view_fsc.php?TYPE=edit&CODE='.$result['FSC_UNIQUE_ID'].'"><span class="glyphicon glyphicon-remove"></span> Back</a>
			</div>
	</h2>
		
	
	<div class="form-group">
		<div class="col-sm-4">
			
			<div class="form-group">
				<label for="FROM_ZONE" class="col-sm-6 control-label">Low Per Gallon</label>
				<div class="col-sm-6">
					
			<input class="form-control text-right" name="low_per_gallon" id="low_per_gallon" type="number" step="0.001" placeholder="Low Per Gallon" maxlength="20" value="" >
				
	
				
				</div>
			</div>
			<div class="form-group">
				<label for="TO_ZONE" class="col-sm-6 control-label">High Per Gallon</label>
				<div class="col-sm-6">
					
			<input class="form-control text-right" name="high_per_gallon" id="high_per_gallon" type="number" step="0.001"  placeholder="High Per Gallon" maxlength="20" value="" >
				</div>
			</div>
			<div class="form-group">
				<label for="DISTANCE" class="col-sm-6 control-label">Flat Amount</label>
				<div class="col-sm-6">
					
			<input class="form-control text-right" name="flat_amount" id="flat_amount" type="number" '.$dec.'  placeholder="Flat Amount" value="0" >
				</div>
			</div>
			
			<div class="form-group">
				<label for="TO_ZONE" class="col-sm-6 control-label">Per Mile Adjust</label>
				<div class="col-sm-6">
					
			<input class="form-control text-right" name="per_mile_adjust" id="per_mile_adjust" type="number" '.$dec.'  placeholder="Per Mile Adjust" maxlength="20" value="0" >
				</div>
			</div>
			
			<div class="form-group">
				<label for="TO_ZONE" class="col-sm-6 control-label">Percent</label>
				<div class="col-sm-6">
					
			<input class="form-control text-right" name="percent" id="percent" type="number" '.$dec.' placeholder="Percent" maxlength="20" value="0" >
				</div>
			</div>
			
			<div class="form-group">
				<label for="TO_ZONE" class="col-sm-6 control-label">Start Date</label>
				<div class="col-sm-6">
					
			<input class="form-control'.($_SESSION['ios'] != 'true' ? ' date' : '').'" name="start_date" id="start_date" type="'.
		($_SESSION['ios'] == 'true' ? 'date' : 'text').'"   maxlength="20" value="" >
				</div>
			</div>
			
			<div class="form-group">
				<label for="TO_ZONE" class="col-sm-6 control-label">End Date</label>
				<div class="col-sm-6">
					
			<input class="form-control'.($_SESSION['ios'] != 'true' ? ' date' : '').'" name="end_date" id="end_date" type="'.
		($_SESSION['ios'] == 'true' ? 'date' : 'text').'"   maxlength="20" value="" >
				</div>
			</div>
			
		</div>
		<div class="col-sm-4">
		</div>
		<div class="col-sm-4">
		</div>
	</div>
</form>
</div>
</div>';
	}
	
	##------- view fsc history ------------##
	public function render_fsc_history($edit)
	{
		//print_r($edit);echo count($edit['fsc_detail']);
		$result='<tbody>';
		if(count($edit['fsc_detail'])>1)
		{
			foreach($edit['fsc_detail'] as $rec)
			{
			  switch( $rec['FSC_COLUMN'] ) {
				  case 'Flat Amount':
				  	$rate_str = $rec['FSC_COLUMN'].' '.$rec['FLAT_AMOUNT'];
				  	break;
				  case 'Per Mile Adjust':
				  	$rate_str = $rec['FSC_COLUMN'].' '.$rec['PER_MILE_ADJUST'];
				  	break;
				  case 'Percent':
				  	$rate_str = $rec['FSC_COLUMN'].' '.$rec['PERCENT'];
				  	break;
				  default:
				  	$rate_str = 'unknown';
			  }
			  
			  
			  $result.="<tr style='cursor:pointer;' >
			  				<td class='text-center'>
							<a onclick='javascript: return delete_single_fsc_history(".$rec['FSC_HIS_ID'].",&quot;".$rec['FSC_UNIQUE_ID']."&quot;);' href='javascript:void(0);' class='btn btn-default btn-xs' data-original-title='' title=''> <span style='font-size: 14px;'><span class='glyphicon glyphicon-trash'></span> </span> </a></td>
       						 <td style='text-align:center;'>#".$rec['FSC_UNIQUE_ID']."</td>
      						 <td style='text-align:center;'>".$rec['FSC_NAME']."</td>
							 <td style='text-align:center;'>$".$rec['FSC_AV_PRICE']."</td>
							  <td style='text-align:center;'>".$rate_str."</td>
							   <td style='text-align:center;'>".date('m/d/Y',strtotime($rec['FSC_START_DATE']))."</td>
        				     <td style='text-align:center;'>".date('m/d/Y',strtotime($rec['FSC_END_DATE']))."</td>
        				     <td style='text-align:center;'>".date('m/d/Y',strtotime($rec['FSC_MODIFIED_ON']))."</td>
							  <td style='text-align:center;'>".$rec['FULLNAME']."</td>
      						</tr>";
			}
		}
		else
		{
			  $result.="<tr style='cursor:pointer;' >
       						 <td style='text-align:center;' colspan='10'>No records are available!</td></tr>";
		}
		$result.='</tbody>'; 
		echo $str="<div class='container-full' role='main'>
		<h2>
		FSC History		<div class='btn-group'>
			
			<a class='btn btn-md btn-default' href='exp_view_fsc.php?TYPE=edit&CODE=".$edit['fsc_id']."'><span class='glyphicon glyphicon-remove'></span> Back</a>
			</div></h2>
		<div class='table-responsive'>
		 <div  style='position:absolute;margin-top:12px;margin-left:600px;display:none;' id='his_loader'><img src='images/loading.gif' /></div>
		<table class='table table-striped table-condensed table-bordered table-hover' >
		<thead>
		<tr class='exspeedite-bg'>
		<th style='text-align:center;'><span class='text-muted'><span class='glyphicon glyphicon-unchecked'></span></span></th>
        <th style='text-align:center;'>FSC ID</th>
		<th style='text-align:center;'>FSC Name</th>
		<th style='text-align:center;'>Average Price</th>
		<th style='text-align:center;'>Average Rate</th>
		<th style='text-align:center;'>Start Date</th>
		<th style='text-align:center;'>End Date</th>
		<th style='text-align:center;'>Modified On</th>
		<th style='text-align:center;'>Modified By</th>
        </tr>
		</thead>
		$result
		</table>
		</div>

</div>";
	
	}
	
	public function render_update_client_rate($edit)
	{
		$str_cat="";
		$chk='';
		if($edit['client_detail']['TAXABLE']=='Yes')
		{$chk='checked="checked"';}
		if(count($edit['rate_category'])>0)
		{
			foreach($edit['rate_category'] as $ed)
			{
				$sel="";
				if($ed['CLIENT_CAT']==$edit['client_detail']['CATEGORY'])
				{$sel='selected="selected"';}
				$str_cat.="<option value=".$ed['CLIENT_CAT']." $sel>".$ed['CATEGORY_NAME']." </option>";
			}
			
		}
		echo $str='<div class="container-full theme-showcase" role="main">
<div class="well  well-md"><form role="form" class="form-horizontal" action=exp_updateclientrate.php?TYPE=update&CODE='.$edit['client_id'].' 
				method="post" enctype="multipart/form-data" 
				name="addclientrate" id="addclientrate"><h2><img src="images/money_bag.png" alt="driver_icon" height="24"> Update Client Rates
		<div class="btn-group">
			<button class="btn btn-md btn-success" name="btn_update_clientrate" id="btn_update_clientrate" type="submit" ><span class="glyphicon glyphicon-ok"></span> Save Changes</button><a class="btn btn-md btn-default" href="exp_listclientrates.php"><span class="glyphicon glyphicon-remove"></span> Cancel</a>
			</div>
	</h2>
	<div class="form-group">
		<div class="col-sm-4">
			<div class="form-group">
				<label for="RATE_CODE" class="col-sm-4 control-label">Rate Code</label>
				<div class="col-sm-8">
					
			<input class="form-control" name="RATE_CODE" id="RATE_CODE" type="text"  
				placeholder="Rate Code" maxlength="10" value="'.$edit['client_detail']['RATE_CODE'].'" required autofocus readonly>
				</div>
			</div>
			
			<div class="form-group">
				<label for="RATE_CODE" class="col-sm-4 control-label">Rate Name</label>
				<div class="col-sm-8">
					
			<input class="form-control" name="RATE_NAME" id="RATE_NAME" type="text"  
				placeholder="Rate Name" maxlength="100" value="'.$edit['client_detail']['RATE_NAME'].'" required>
				</div>
			</div>
			<div class="form-group">
				<label for="RATE_CODE" class="col-sm-4 control-label">Rate Category</label>
				<div class="col-sm-8">
					
			
			<select class="form-control" name="CATEGORY" id="CATEGORY" required>
			'.$str_cat.'	
			</select>
				</div>
			</div>
			
			
			<div class="form-group">
				<label for="RATE_PER_MILES" class="col-sm-4 control-label">Rate</label>
				<div class="col-sm-8">
					
			<input class="form-control" name="RATE_PER_MILES" id="RATE_PER_MILES" type="text"  
				placeholder="Rate" value="'.$edit['client_detail']['RATE_PER_MILES'].'" required >
				</div>
			</div>
			<div class="form-group">
				<label for="TAXABLE" class="col-sm-4 control-label">Taxable</label>
				<div class="col-sm-8">
					
			
			<input type="checkbox" class="my-switch" data-on="success" data-off="default" data-text-label="Taxable" value="TAXABLE" name="TAXABLE" '.$chk.'>
				
				</div>
			</div>
			<div class="form-group">
				<label for="RATE_DESC" class="col-sm-4 control-label">Description</label>
				<div class="col-sm-8">
					
			<textarea class="form-control" name="RATE_DESC" id="RATE_DESC"  
				placeholder="Description" maxlength="100" required rows="5">'.$edit['client_detail']['RATE_DESC'].'</textarea>
				</div>
			</div>
		</div>
	</div>
	
</form>
</div>
</div>';
	}

	##-------- view settlement history of the week-----------##
	public function render_update_driver_pay($edit) {
	
		if( $_SESSION['EXT_USERNAME'] == 'duncan' && $this->debug ) {
			echo "<pre>render_update_driver_pay: entry";
			var_dump($edit);
			echo "</pre>";
		}

	
	
	$contract_name="";
	if($edit['contract_name']!="")
	{
		$contract_name=" - ".$edit['contract_name'];
	}
	$fr_date=$to_date='';
	if(isset($_POST['btn_search_load']))
	{
		$fr_date=$_POST['ACTUAL_DEPART_FROM'];
		$to_date=$_POST['ACTUAL_DEPART_TO'];
	}
	echo '<form class="form-inline" role="form" action="exp_updatedriverpay.php?payid='.$edit['DRIVER_PAY_ID'].'"  method="post" enctype="multipart/form-data" 	name="RESULT_FILTERS_EXP_DRIVER" id="RESULT_FILTERS_EXP_DRIVER" novalidate>
			<div class="container-full" role="main" style="width:87%">
<div style="position:fixed;top:50px;width:82%;margin-left:31px;background-color:#fff;padding-bottom:50px;z-index:500;">			
<div class="container-full" role="main">
 '.($this->debug ? '<input name="debug" type="hidden" value="on">' : '').'
    <input name="FILTERS_EXP_DRIVER" type="hidden" value="on">
    <h3><img src="images/driver_icon.png" alt="driver_icon" height="24" > &nbsp; Driver Pay
	<div class="btn-group">
	<a class="btn btn-sm btn-default" href="exp_billhistory.php?CODE='.$edit['driver_id'].'"><span class="glyphicon glyphicon-remove"></span> Back</a>
	</div><span style="color:#9C3;font-size:18px;">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<strong >SAVED ON : '.date('m/d/Y',strtotime($edit['detail']['ADDED_ON'])).'</strong></span></h3>
	
			
    
	<strong>Actual Delivery</strong>&nbsp;&nbsp; FROM <input type="'.($_SESSION['ios'] == 'true' ? 'datetime-local' : 'text').'" value="'.$fr_date.'" placeholder="From" id="ACTUAL_DEPART_FROM" name="ACTUAL_DEPART_FROM" class="form-control'.($_SESSION['ios'] == 'true' ? '' : ' timestamp').'">&nbsp;&nbsp; TO <input type="'.($_SESSION['ios'] == 'true' ? 'datetime-local' : 'text').'" value="'.$to_date.'" placeholder="To" id="ACTUAL_DEPART_TO" name="ACTUAL_DEPART_TO" class="form-control'.($_SESSION['ios'] == 'true' ? '' : ' timestamp').'">
	 &nbsp;&nbsp; <div class="btn-group"><button  class="btn btn-md btn-success" type="submit" name="btn_search_load">
		<span class="glyphicon glyphicon-search"></span>
		Filter
		</button> <button  class="btn btn-md btn-default" type="button" name="btn_search_reload" onclick="javascript:window.location.href=window.location.href;">
		<span class="glyphicon glyphicon-refresh"></span>
		Reset
		</button>
		</div>
 
    </div>
	</div>
<div class="render_driver_pay_screen">
    <div class="table-responsive" style="overflow:auto;">
	 <!--info-->
	<div style="position:fixed;top:150px;width:74.4%;background-color:#fff;z-index:500;">
    <table class="table table-striped table-condensed table-bordered table-hover" >
	
                  <tbody>
				  <tr style="cursor:pointer;" >
				  	<td colspan="4"><div style="float:right;">Week :<strong> '.date("d  M  Y", strtotime($edit['detail']["WEEKEND_FROM"])).'</strong>  to   <strong>'.date("d M Y", strtotime($edit['detail']["WEEKEND_TO"])).'</strong></div></td>
				  </tr>
            	<tr style="cursor:pointer;" >
					<td width="14%" ><strong>Driver Name</strong></td>
					<td width="16%">'.$edit['driver']['FIRST_NAME']." ".$edit['driver']['MIDDLE_NAME']." ".$edit['driver']['LAST_NAME'].'</td>
                    <td width="14%" ><strong>Contract Id :</strong></td>
					<td width="16%"><input class="form-control input-sm" type="text"  name="" value="'.$edit['driver']['PROFILE_ID'].$contract_name.'" readonly></td>
                </tr>
            </tbody>
            </table>
			<input type="hidden" name="driver_id" id="driver_id" value="'.$edit['driver_id'].'"/>
     <!--info-->
     </div>
     <div style="margin-top:200px;">';

	
	}
	
	##--------function for bill details of each load------##
	public function render_update_load_detail($edit) {

		if( $_SESSION['EXT_USERNAME'] == 'duncan' && $this->debug ) {
			echo "<pre>render_update_load_detail: entry";
			var_dump($edit);
			echo "</pre>";
		}
		
		if($edit['status']=='error')
		{
			echo '<table class="table table-striped table-condensed table-bordered table-hover" >
			<tbody>
			<tr style="cursor:pointer;">
			<td colspan="4" align="center"><strong>Driver Load Information Not Available.</strong></td>
			</tr>
			</tbody>
			</table>';
		}
		else  {
		$attach_id=$edit["load_id"];
		$type='load';
		if($edit["load_id"]==0)
		{$attach_id='_tr'.$edit['load_detail']['TRIP_ID'];
		 $type='trip'; 
		}
			#get add manual rates to loads
			$manual_rate_str='';
			if(count($edit['all_manual_rates'])>0)
			{
				$manual_rate_str	='<select name="manual_rates'.$attach_id.'" class="form-control input-sm" id="manual_rates'.$attach_id.'"  >
													<option value="">--Select--</option>';
				foreach($edit['all_manual_rates'] as  $mrate)
				{
					$manual_rate_str	.=	'<option value="'.$mrate['MANUAL_ID'].'">'.$mrate['MANUAL_RATE_CODE']." - ".$mrate['MANUAL_RATE_DESC'].'</option>';
				}
				$manual_rate_str	.=	'</select>';
			}
			else
			{
				$manual_rate_str	='<select name="manual_rates'.$attach_id.'" class="form-control input-sm" id="manual_rates'.$attach_id.'"  >
													<option value="">--Select--</option>';
				$manual_rate_str	.=	'</select>';
			}
		#get range of miles
		$permile_flag=$extra_charge=0;
	$str_range_of_miles='';
	if(count($edit['range_rates'])>0)
	{
			$total_dist	=	 $edit["load_detail"]["TOTAL_MILES"];
		
	
			 foreach( $edit['range_rates'] as $row_range)
				 {$str='';
				 
				 if($edit["load_detail"]["TOTAL_MILES"] >= $row_range['RANGE_FROM'] && $edit["load_detail"]["TOTAL_MILES"] <= $row_range['RANGE_TO'])
				 	{$permile_flag=1;
					$newrate=$row_range['TOTAL_RATE'];
					if($row_range['TOTAL_RATE']==0)
					{$newrate=$row_range['RANGE_RATE'];
					}
				 	$diff_miles	=	$total_dist - $row_range['RANGE_FROM'];
					
				 	$str_range_of_miles.='<tr style="cursor:pointer;" >
		<td width="14%" ><strong>'.$row_range['RANGE_NAME'].'  :  '.$row_range['RANGE_FROM'].' TO '.$row_range['RANGE_TO'].'</strong></td>
		<td width="16%" class="text-right">'.$diff_miles.'</td>
		<td width="14%" class="text-right">$ '.$row_range['RANGE_RATE'].'</td>
							<td width="16%"><div class="input-group">
				<span class="input-group-addon">$</span><input class="form-control input-sm text-right" type="text"  name="range_miles'.$attach_id.'[]" id="range_miles'.$attach_id.'[]" value="'.number_format($newrate,2,".","").'" onkeyup="javascript: calculate_total(\'\',\''.$attach_id.'\');"></div></td>
							</tr>
						<input type="hidden" name="range_name'.$attach_id.'[]" value="'.$row_range['RANGE_NAME'].'"/>
						<input type="hidden" name="range_code'.$attach_id.'[]" value="'.$row_range['RANGE_CODE'].'"/>
						<input type="hidden" name="range_from'.$attach_id.'[]" value="'.$row_range['RANGE_FROM'].'"/>
						<input type="hidden" name="range_to'.$attach_id.'[]" value="'.$row_range['RANGE_TO'].'"/>
						<input type="hidden" name="range_rate'.$attach_id.'[]" value="'.$row_range['RANGE_RATE'].'"/>';
				 	
				 }
			 else
				 {
				 	$diff_miles	=	$row_range['RANGE_TO'] - $row_range['RANGE_FROM'];
				 	$cal	=0;
				 	$str_range_of_miles.='<tr style="cursor:pointer;">
		<td width="14%" ><strong>'.$row_range['RANGE_NAME'].' :  '.$row_range['RANGE_FROM'].' TO '.$row_range['RANGE_TO'].'</strong></td>
				<td width="16%" class="text-right">'.$diff_miles.'</td>
				<td width="14%" class="text-right">$ '.$row_range['RANGE_RATE'].'</td>
				<td width="16%"><div class="input-group">
				<span class="input-group-addon">$</span><input class="form-control input-sm text-right" type="text"  name="range_miles'.$attach_id.'[]" id="range_miles'.$attach_id.'[]" value="'.number_format($cal,2,".","").'" onkeyup="javascript: calculate_total(\'\',\''.$attach_id.'\');"></div></td>
						</tr>
						<input type="hidden" name="range_name'.$attach_id.'[]" value="'.$row_range['RANGE_NAME'].'"/>
						<input type="hidden" name="range_code'.$attach_id.'[]" value="'.$row_range['RANGE_CODE'].'"/>
						<input type="hidden" name="range_from'.$attach_id.'[]" value="'.$row_range['RANGE_FROM'].'"/>
						<input type="hidden" name="range_to'.$attach_id.'[]" value="'.$row_range['RANGE_TO'].'"/>
						<input type="hidden" name="range_rate'.$attach_id.'[]" value="'.$row_range['RANGE_RATE'].'"/>'
				 								;
				 }
				 }
		}
		 else
		 {
				 		$str_range_of_miles.='<tr style="cursor:pointer;" >
						<td width="20%" >NO RANGE RATES ARE AVAILABLE !</td>
						</tr>';
		 }
		
	//!get add manual rates to loads
				 		$manual_rates='';
	if(count($edit['manual_rates'])>0) {
		foreach($edit['manual_rates'] as $man) {
			$manual_rates.=' <tr style="cursor:pointer;" >
			<td>  Rates :</td><td style="width:25%;">'.$man['MANUAL_CODE'].'</td><td style="width:25%;">'.$man['MANUAL_DESC'].'</td><td style="width:25%;">'.$man['MANUAL_RATE'].'</td>
			</tr>
			<input type="hidden" name="manual_code'.$attach_id.'[]"  value="'.$man['MANUAL_CODE'].'"/>
			<input type="hidden" name="manual_desc'.$attach_id.'[]"  value="'.$man['MANUAL_DESC'].'"/>
			<input type="hidden" name="load_manual_codes'.$attach_id.'[]" value="'.$man['MANUAL_RATE'].'">';
		}
	}
		
	$driver_rate_str='';
	//!Update Regular rates
	$total_amount=0.00;
		if(count($edit['rates'])>0) {
			foreach($edit['rates'] as $row) {
				$per_rate=$row['PAY'];
			
				//	echo $row['CATEGORY_NAME']."=>".$dri_row['TOT_PALETS'];
				$nm=str_replace(' ' , '_' ,  $row['TITLE']).'_'.$row['LOAD_RATE_CODE'];
						
					//	$total_amount	+=	$res_amount;
				//! SCR# 180 - FCADV readonly
				//! SCR# 183 - Remove/Duplicate buttons (TBD)
				$driver_rate_str	.=	'<tr style="cursor:pointer;" id="RATE_'.$row['LOAD_PAY_RATE_ID'].'">
				<input type="hidden" name="cat_type'.$attach_id.'[]" id="cat_type" value="'.$nm.'"/>
					<input type="hidden" name="category_name'.$attach_id.'[]" id="category_name" value="'.$row['TITLE'].'"/>
					<input type="hidden" name="rate_code'.$attach_id.'[]" id="rate_code" value="'.$row['LOAD_RATE_CODE'].'"/>
					<input type="hidden" name="rate_name'.$attach_id.'[]" id="rate_name" value="'.$row['LOAD_RATE_NAME'].'"/>
					<input type="hidden" name="rate_bonus'.$attach_id.'[]" id="rate_bonus" value="'.$row['LOAD_RATE_BONUS'].'"/>
					<input type="hidden" name="free_hours'.$attach_id.'[]" id="'.$attach_id.$nm.'_free" value="'.$row['FREE_HOURS'].'"/>
					<input type="hidden" name="card_advance'.$attach_id.'[]" id="card_Advance" value="'.(empty($row['CARD_ADVANCE_CODE']) ? 0 : $row['CARD_ADVANCE_CODE']).'">
					<td style="text-align:right; color:#000; font-weight:bold; font-family:Arial, Helvetica, sans-serif; font-size:13px;">'.
					
					'<div class="btn-group" role="group" style="float: left;"><a class="btn btn-sm btn-danger" id="REMOVE_'.$row['LOAD_PAY_RATE_ID'].'" title="Remove advance" onclick="javascript: remove_rate(\''.$row['LOAD_PAY_RATE_ID'].'\', \''.$attach_id.'\');"><span class="glyphicon glyphicon-remove"></span></a>'.

					($row['LOAD_RATE_CODE'] != 'FCADV' ? '<a class="btn btn-sm btn-success"  id="ADD_'.$row['LOAD_PAY_RATE_ID'].'" title="Duplicate rate" onclick="javascript: duplicate_rate(\''.$row['LOAD_PAY_RATE_ID'].'\', \''.$attach_id.'\');"><span class="glyphicon glyphicon-plus"></span></a>' : ''). '</div>'.
					
					$row['LOAD_RATE_NAME'].'<br>'.$row['TITLE'].
					(isset($row['FREE_HOURS']) && $row['FREE_HOURS'] > 0 ? ' ['.$row['FREE_HOURS'].' free hours] ' : '').
						($row['LOAD_RATE_BONUS'] == 1 ? ' (B)' : '').' :</td>
						<td><input name="'.$attach_id.'_qty[]" id="'.$attach_id.$nm.'_qty" type="text" class="form-control input-sm text-right" value="'.number_format($row['QUANTITY'],2,".","").'" onkeyup="javascript: calculate_total(\''.$nm.'\',\''.$attach_id.'\');"></td>
<td><input name="'.$attach_id.'_amt[]" id="'.$attach_id.$nm.'_amt" type="text" class="form-control input-sm text-right" value="'.$row['RATE'].'" onkeyup="javascript: calculate_total(\''.$nm.'\',\''.$attach_id.'\');"></td>
					<td><div class="input-group">
				<span class="input-group-addon">$</span>
				<input name="text_total'.$attach_id.'[]" id="'.$attach_id.$nm.'_total" type="text" class="form-control input-sm text-right" value="'.number_format($per_rate,2,".","").'" readonly></div></td>
				  </tr>
				  </div>
				';
			         }
		}
								else
								{
								$driver_rate_str	.=	'<tr style="cursor:pointer;" >
					     <td style="text-align:center;" colspan="4">NO RATES ARE ASSIGNED !</td>
					 </tr>
				';
								}
			$stop_detail='';
			if(count($edit['STOP_DETAIL'])>0)
			{//print_r($dri_row['STOP_DETAIL']);
					foreach($edit['STOP_DETAIL'] as $stop)
					{//print_r( $stop);
									$stop_type=$depart_Date=$arrive_Date='';
											if($stop['STOP_TYPE']=='pick')
											{$stop_type='Origin';}
											else
											{$stop_type='Destination';}
											if($stop['ACTUAL_DEPART']!="")
											{$depart_Date=date('m/d/Y H:i:s l',strtotime($stop['ACTUAL_DEPART']));
											if($stop['HOLIDAY']!="")
					{$depart_Date.='<br/><span style="color:#d3392b;"><strong>'.$stop['HOLIDAY'].'</strong></span>';}
											}
													
												if($stop['ACTUAL_ARRIVE']!="")
												{$arrive_Date=date('m/d/Y H:i:s l',strtotime($stop['ACTUAL_ARRIVE']));
					if($stop['ARRIVAL_HOLIDAY']!="")
												{$arrive_Date.='<br/><span style="color:#d3392b;"><strong>'.$stop['ARRIVAL_HOLIDAY'].'</strong></span>';}
											}
	
											$space='';
											if($stop['CITY']!="" && $stop['STATE']!="")
											{$space=',';}
											$stop_detail.='<tr style="cursor:pointer;">
											<td>'.$stop_type.'</td>
											<td>'.$stop['NAME'].'<br/>'.$stop['CITY'].$space.$stop['STATE'].'</td>
											<td>'.$arrive_Date.'</td>
											<td>'.$depart_Date.'</td>
							                 </tr>';
				}
			}
			else
			{$stop_detail.='<tr style="cursor:pointer;">
											<td colspan="4" style="text-align:center;">STOP DETAIL IS CURRENTLY UNAVAILABLE !</td>
											</tr>';}
			$bon_sel=$bon_sel1='';
			if($edit["load_detail"]['BONUS']=='Yes'){$bon_sel='selected="selected"';}
			else 
			{$bon_sel1='selected="selected"';}
	
	echo '  <!--info-->
			<div class="well well-sm" id="LOAD_'.$edit["load_id"].'">
			<input type="hidden" name="LOAD_CODE[]" value="'.$edit["load_id"].'"/>
			<input type="hidden" name="tripID[]" id="tripID'.$attach_id.'" value="'.$edit['load_detail']['TRIP_ID'].'"/>
			
    <table class="table table-striped table-condensed table-bordered table-hover" >
                  <tbody>
            <tr style="cursor:pointer;" class="exspeedite-bg" >
						 <td width="10%"><a class="btn btn-sm btn-default" id="REMOVE_'.$edit["load_id"].' title="Remove load '.$edit["load_id"].' from list" onclick="javascript: remove_load('.$edit["load_id"].');"><span class="glyphicon glyphicon-remove"></span> Remove</a></td>
						 ';
		//echo "<pre>";
		//var_dump($edit);
		//echo "</pre>";
	//! WIP
	if( $edit["load_id"] == 0 )					 
		echo '<td width="90%" class="text-center"><strong>NON-TRIP RELATED PAY</strong>
						 <input class="form-control input-sm text-right" type="hidden"  name="load_id[]" id="load_id0" value="0" readonly="readonly">
						 <input class="form-control input-sm text-right" type="hidden"  name="total_miles[]" id="total_miles0" value="">
						 </td>
	';
	else
		echo '<td width="16%" ><strong>Load Id</strong>'.($edit['multi_company'] && ! empty($edit["load_detail"]["OFFICE_NUM"]) ? ' - '.$edit["load_detail"]["OFFICE_NUM"] : '').'</td>
					<td width="16%"><input class="form-control input-sm text-right" type="text"  name="load_id[]" id="load_id'.$attach_id.'" value="'.$edit["load_id"].'" ></td>
                    <td width="16%" bgcolor="#000000"><strong>Total Miles</strong></td>
					<td width="16%"><input class="form-control input-sm text-right" type="text"  name="total_miles[]" id="total_miles'.$attach_id.'" value="'.$edit["load_detail"]['TOTAL_MILES'].'" onkeyup="return update_miles_ranges('.$attach_id.', '.$edit['DRIVER_PAY_ID'].');">
							<input type="hidden" id="empty_miles'.$edit["load_id"].'" value="'.$edit["info"]["EMPTY_DISTANCE"].'" readonly>

					</td>
	';
	echo '						</tr>
								</tbody>
            </table>
			
     <!--info-->';
   
    echo '<!--total settelment2-->
	  <div id="odom_'.$attach_id.'"> 
       <table class="table table-striped table-condensed table-bordered table-hover" >
	   		 <tbody >
	   		 ';
    if( $edit["load_id"] > 0 ) {	
	echo '		<tr style="cursor:pointer;" >	
						<td style="text-align:right; color:#000; font-weight:bold; font-family:Arial, Helvetica, sans-serif; font-size:13px;">Shipments :</td>
						<td colspan="3" style="text-align:right; color:#000; font-weight:bold; font-family:Arial, Helvetica, sans-serif; font-size:13px;">'.$this->slink($edit["info"]["SHIPMENTS"]).'</td>
					  </tr>  
			      <tr style="cursor:pointer;" >
					<td width="18%" style="text-align:right; color:#000; font-weight:bold; font-family:Arial, Helvetica, sans-serif; font-size:13px;">Tractor :</td>
					<td ><input name="tractor" id="trctor" value="'.$edit["info"]["UNIT_NUMBER"].'" type="text" class="form-control input-sm" readonly></td>
					<td style="text-align:right; color:#000; font-weight:bold; font-family:Arial, Helvetica, sans-serif; font-size:13px;">Approved Date :</td>
					<td><input name="approved_date" id="approved_date"  type="text" class="form-control input-sm" value="'.date('m/d/Y',strtotime($edit["load_detail"]['APPROVED_DATE'])).'" readonly></td>
				  </tr>
               
                  <tr style="cursor:pointer;" >
					<td style="text-align:right; color:#000; font-weight:bold; font-family:Arial, Helvetica, sans-serif; font-size:13px;">Trailer 1 :</td>
					<td><input name="trailer" id="trailer" value="'.$edit["info"]["TRAILER_NUMBER"].'" type="text" class="form-control input-sm" readonly></td>
					<td style="text-align:right; color:#000; font-weight:bold; font-family:Arial, Helvetica, sans-serif; font-size:13px;">Trailer 2 :</td>
					<td><input name="" type="text" class="form-control input-sm" readonly></td>
				  </tr>
				   <tr style="cursor:pointer;"  >
						<td style="text-align:right; color:#000; font-weight:bold; font-family:Arial, Helvetica, sans-serif; font-size:13px;">Odometer From:</td>
						<td ><input name="odometer_from[]" id="odometer_from'.$attach_id.'" value="'.$edit["load_detail"]['ODOMETER_FROM'].'" type="text" class="form-control input-sm text-right" onkeyup="return calculate_total_miles(\''.$attach_id.'\','.$edit['DRIVER_PAY_ID'].','.$edit['driver_id'].',\''.$type.'\');" ></td>
						<td style="text-align:right; color:#000; font-weight:bold; font-family:Arial, Helvetica, sans-serif; font-size:13px;">Odometer To :</td>
						<td><input name="odometer_to[]" id="odometer_to'.$attach_id.'" type="text" class="form-control input-sm text-right" value="'.$edit["load_detail"]['ODOMETER_TO'].'" onkeyup="return calculate_total_miles(\''.$attach_id.'\','.$edit['DRIVER_PAY_ID'].','.$edit['driver_id'].',\''.$type.'\');"></td>
						  </tr>

					  <!-- Duncan LINE_HAUL -->               
					   <tr style="cursor:pointer;" >					
						<td style="text-align:right; color:#000; font-weight:bold; font-family:Arial, Helvetica, sans-serif; font-size:13px;">Line haul:</td>
						<td><div class="input-group">
				<span class="input-group-addon">$</span><input name="line_haul[]" id="line_haul'.$attach_id.'" value="'.$edit["load_detail"]["LINE_HAUL"].'" type="text" class="form-control input-sm text-right" readonly></div></td>
						
						<td style="text-align:right; color:#000; font-weight:bold; font-family:Arial, Helvetica, sans-serif; font-size:13px;">Fuel Surcharge:</td>
						<td><div class="input-group">
				<span class="input-group-addon">$</span><input name="fuel_cost[]" id="line_haul'.$attach_id.'" value="'.$edit["load_detail"]["FUEL_COST"].'" type="text" class="form-control input-sm text-right" readonly></div></td>
					  </tr>                 
	
                  <tr style="cursor:pointer;" >
					<td colspan="4">&nbsp;</td>
					
				  </tr>
			  <tr style="cursor:pointer;" >
			<th>Stop Type</th>
			<th>Shipper /Consignee</th>
			<th>Actual Arrival Day & Holiday</th>
			<th>Actual Delivery Day & Holiday</th>
			</tr>
			'.$stop_detail;
		}
            echo '<tr >
								<th class="text-center" width="30%">&nbsp;</th>
								<th>Quantity</th>
                <th>Rate</th>
                <th>Pay</th>
              </tr>
			'.$driver_rate_str;
			
		if( $edit["load_id"] > 0 ) {	
			echo '

				<tr style="cursor:pointer;" >
					<td style="text-align:center; color:#000; font-weight:bold; font-family:Arial, Helvetica, sans-serif; font-size:13px;" colspan="4">Miles Range:</td>
					</tr>
					<tr>
					<td><div id="reload_range_rate_div" style="display:none;"><img src="images/loading.gif" border="1" style="position:absolute; margin-left:250px;margin-top:10px;"></div>
					</td>
					</tr>
					<tr style="cursor:pointer;" >
					<td style="text-align:center; color:#000; font-weight:bold; font-family:Arial, Helvetica, sans-serif; font-size:13px;" >&nbsp;</td>
					<td style="text-align:center; color:#000; font-weight:bold; font-family:Arial, Helvetica, sans-serif; font-size:13px;" >Difference Between Miles</td>
					<td style="text-align:center; color:#000; font-weight:bold; font-family:Arial, Helvetica, sans-serif; font-size:13px;" >Range Rates	</td>
					<td style="text-align:center; color:#000; font-weight:bold; font-family:Arial, Helvetica, sans-serif; font-size:13px;" >Rates</td>
					</tr>
	
				  '.$str_range_of_miles;
			}
							  
				  echo '
				  <tr style="cursor:pointer;" >
					<td colspan="4">&nbsp;</td>
					
				  </tr>

							<tr style="cursor:pointer;" >					
						<td style="text-align:right; color:#000; font-weight:bold; font-family:Arial, Helvetica, sans-serif; font-size:13px;">Manual Rates  :</td>
						<td>
							'.$manual_rate_str.'
						</td>
						<td><a class="btn btn-sm btn-success" onclick="javascript: return add_manual_fun(\''.$attach_id.'\',\''.$edit['driver_id'].'\',\''.$type.'\');" href="javascript:void(0);">
								<span class="glyphicon glyphicon-plus"></span>Add</a></td>
						<td>&nbsp;</td>					
					  </tr>   
					  
					  <tr><td><div id="reload_manual_rate_div'.$attach_id.'" style="display:none;"><img src="images/loading.gif" border="1" style="position:absolute; margin-left:250px;margin-top:10px;"></div></td></tr>
					   <tr style="cursor:pointer;" >					
						<td colspan="4">
								<table width="100%" id="empty_row'.$attach_id.'" class="table table-striped table-condensed table-bordered table-hover">
								</table>
						</td>
					  </tr>
	
				  '.$manual_rates.'
	
			<tr style="cursor:pointer;" >
					<td style="text-align:right; color:#000; font-weight:bold; font-family:Arial, Helvetica, sans-serif; font-size:16px;">Total Trip Pay :</td>
					<td>&nbsp;</td>
					<td>&nbsp;</td>
					<td style="color:#000; font-weight:bold; font-family:Arial, Helvetica, sans-serif; font-size:16px; padding-right: 20px;">
					<input type="hidden" name="total_trip_amount[]" id="total_trip_amount'.$attach_id.'" value="'.$edit["load_detail"]['TOTAL_TRIP_PAY'].'">
					<div class="text-right" id="total_trip_amount_div'.$attach_id.'"> $ '.$edit["load_detail"]['TOTAL_TRIP_PAY'].'</div></td> </tr>

						<tr style="cursor:pointer;" >					
						<td style="text-align:right; color:#000; font-weight:bold; font-family:Arial, Helvetica, sans-serif; font-size:16px;">Bonus-able Trip Pay:</td>
						<td>&nbsp;</td>
						<td>&nbsp;</td>
						<td style="color:#000; font-weight:bold; font-family:Arial, Helvetica, sans-serif; font-size:16px; padding-right: 20px;">
						<input type="hidden" id="total_bonusable_amount'.$attach_id.'" value=""><div class="text-right" id="total_bonusable_amount_div'.$attach_id.'"></div></td> </tr>

                 <tr style="cursor:pointer;" >
					<td colspan="4">&nbsp;</td>
					
				  </tr>
									
	
                  <tr style="cursor:pointer;" >
	                  <td style="text-align:right; color:#000; font-weight:bold; font-family:Arial, Helvetica, sans-serif; font-size:13px;">Bonus :</td>
					<td><select name="bonus_allow[]" class="form-control input-sm" id="bonus_allow'.$attach_id.'" onchange="javascript:return calculate_total(\'\',\''.$attach_id.'\');">
							<option value="No" '.$bon_sel1.'>No</option>
								<option value="Yes" '.$bon_sel.'>Yes</option>
					
	
					</select></td>
					<td><input class="form-control input-sm text-right" type="text"  name="apply_bonus[]" id="apply_bonus'.$attach_id.'" value="'.$edit["load_detail"]['APPLY_BONUS'].'" onkeyup="javascript:return calculate_total(\'\',\''.$attach_id.'\');"> Bonus in %</td>
					<td><div class="input-group">
				<span class="input-group-addon">$</span>
				<input class="form-control input-sm text-right" type="text"  name="bonus_amount[]" id="bonus_amount'.$attach_id.'" value="'.$edit["load_detail"]['BONUS_AMOUNT'].'" ></div>$ on Total Trip Pay </td>
				  </tr>
                  <tr style="cursor:pointer;" >
					<td colspan="4">&nbsp;</td>
					
				  </tr>
				';

				echo '	    <tr style="cursor:pointer;" >
					<td style="text-align:right; color:#000; font-weight:bold; font-family:Arial, Helvetica, sans-serif; font-size:13px;">Handling Pallets :</td>
					<td><input class="form-control input-sm text-right" type="text"  name="handling_pallet[]" id="handling_pallet'.$attach_id.'" value="'.$edit["load_detail"]['HANDLING_PALLET'].'"  onkeyup="javascript: calculate_total(\'\',\''.$attach_id.'\');"></td>
					<td style="text-align:right; color:#000; font-weight:bold; font-family:Arial, Helvetica, sans-serif; font-size:13px;">Handling Pay :</td>
					<td><div class="input-group">
				<span class="input-group-addon">$</span>
<input class="form-control input-sm text-right" type="text"  name="handling_pay[]" id="handling_pay'.$attach_id.'" value="'.$edit["load_detail"]['HANDLING_PAY'].'"  onkeyup="javascript: calculate_total(\'\',\''.$attach_id.'\');"></div></td>
				  </tr>
	
				      <tr style="cursor:pointer;" >
					<td style="text-align:right; color:#000; font-weight:bold; font-family:Arial, Helvetica, sans-serif; font-size:13px;">Loaded Detention Hours :</td>
					<td><input class="form-control input-sm text-right" type="text"  name="loaded_det_hr[]" id="loaded_det_hr'.$attach_id.'" value="'.$edit["load_detail"]['LOADED_DET_HR'].'"  onkeyup="javascript: calculate_total(\'\',\''.$attach_id.'\');"></td>
					<td style="text-align:right; color:#000; font-weight:bold; font-family:Arial, Helvetica, sans-serif; font-size:13px;">Unoaded Detention Hours :</td>
					<td><input class="form-control input-sm text-right" type="text"  name="unloaded_det_hr[]" id="unloaded_det_hr'.$attach_id.'" value="'.$edit["load_detail"]['UNLOADED_DET_HR'].'"  onkeyup="javascript: calculate_total(\'\',\''.$attach_id.'\');"></td>
				  </tr>
				  ';

				  echo '
            </tbody>
           		 
				
	
			
	
								<tr style="cursor:pointer;" >
					<td style="text-align:right; color:#000; font-weight:bold; font-family:Arial, Helvetica, sans-serif; font-size:18px;">Total Settlement  :</td>
					<td>&nbsp;</td>
					<td>&nbsp;</td>
					<td style="text-align:right; color:#000; font-weight:bold; font-family:Arial, Helvetica, sans-serif; font-size:18px;"><div class="input-group">
				<span class="input-group-addon">$</span>
<input name="totla_settelment[]" id="totla_settelment'.$attach_id.'" type="text" class="form-control input-sm text-right" style="font-size:18px;" value="'.$edit["load_detail"]['TOTAL_SETTLEMENT'].'" onkeyup="javascript: calculate_total(\'\',\''.$attach_id.'\');" readonly></div></td>
				  </tr>
           </table>
	  </div>
	  </div>
	<script type="text/javascript" language="javascript">
	$(document).ready(function(){
	calculate_total(\'\',\''.$attach_id.'\');
	});
</script>';
		}
	
	}
	
	
	##------ end art of bill page----##
	public function render_update_driver_end($edit) {
		
		if( $_SESSION['EXT_USERNAME'] == 'duncan' && $this->debug ) {
			echo "<pre>render_update_driver_end: entry";
			var_dump($edit);
			echo "</pre>";
		}
		
		$trip_pay=$bonus=$handling=$gross=0;
		#-------- total of load ------#
		if(count($edit['fetch_totals'])>0 && isset($edit['fetch_totals'][0]['TRIP_PAY'])) {
			foreach($edit['fetch_totals'] as $tot) {
				$trip_pay=$trip_pay+$tot['TRIP_PAY'];
				$bonus=$bonus+$tot['BONUS'];
				$handling=$handling+$tot['HANDLING'];
				$gross=$gross+$tot['GROSS_EARNING'];
			}
		}
		#-------- total of trip ------#
	
		if(count($edit['fetch_totals_trip'])>0 && isset($edit['fetch_totals_trip'][0]['TRIP_PAY'])) {
		foreach($edit['fetch_totals_trip'] as $tot) {
				$trip_pay=$trip_pay+$tot['TRIP_PAY'];
				$bonus=$bonus+$tot['BONUS'];
				$handling=$handling+$tot['HANDLING'];
				$gross=$gross+$tot['GROSS_EARNING'];
			}
		}
	
	
						echo  '<!--weekly settelment-->
						</div>
						<div id="end"></div>
  <table class="table table-striped table-condensed table-bordered table-hover" >
          		  <tr style="cursor:pointer;" >
					<td width="23%" style="text-align:right; color:#000; font-weight:bold; font-family:Arial, Helvetica, sans-serif; font-size:18px; text-transform:uppercase;">WEEK&nbsp;ENDING&nbsp;TOTAL:</td>
					<td width="26%">&nbsp;</td>
					<td width="25%">&nbsp;</td>
					<td width="26%" style="color:#000; font-weight:bold; font-family:Arial, Helvetica, sans-serif; font-size:13px;"></td>
				  </tr>
	
                  <tr style="cursor:pointer;"  >
					<td style="text-align:right; color:#000; font-weight:bold; font-family:Arial, Helvetica, sans-serif; font-size:18px; text-transform:uppercase;">TRIP PAY:</td>
					<td>&nbsp;</td>
					<td>&nbsp;</td>
					<td><div class="input-group">
				<span class="input-group-addon">$</span>
				<input name="final_trip_pay1" id="final_trip_pay1" type="text" class="form-control input-sm text-right" value="'.$trip_pay.'" style="font-size:18px;" readonly></div></td>
				  </tr>
	
					  <tr style="cursor:pointer;"  >
					<td style="text-align:right; color:#000; font-weight:bold; font-family:Arial, Helvetica, sans-serif; font-size:18px; text-transform:uppercase;">Bonus:</td>
					<td>&nbsp;</td>
					<td>&nbsp;</td>
					<td><div class="input-group">
				<span class="input-group-addon">$</span>
<input name="final_bonus1" id="final_bonus1" type="text" class="form-control input-sm text-right" value="'.$bonus.'" style="font-size:18px;" readonly></div></td>
				  </tr>
	
                  <tr style="cursor:pointer;"  >
					<td style="text-align:right; color:#000; font-weight:bold; font-family:Arial, Helvetica, sans-serif; font-size:18px; text-transform:uppercase;">Handling:</td>
					<td>&nbsp;</td>
					<td>&nbsp;</td>
					<td><div class="input-group">
				<span class="input-group-addon">$</span>
<input name="final_handling1" id="final_handling1" type="text" class="form-control input-sm text-right" value="'.$handling.'" style="font-size:18px;" readonly></div></td>
				  </tr>
	
                  <tr style="cursor:pointer;" >
	                  		<td style="text-align:right; color:#000; font-weight:bold; font-family:Arial, Helvetica, sans-serif; font-size:18px; text-transform:uppercase;">GROSS EARNINGS</td>
					<td>&nbsp;</td>
					<td>&nbsp;</td>
					<td style="color:#000; font-weight:bold; font-family:Arial, Helvetica, sans-serif; font-size:13px;" >
					<div class="input-group">
				<span class="input-group-addon">$</span>
<input name="final_settlement1" id="final_settlement1" type="text" class="form-control input-sm text-right" value="'.$gross.'" style="font-size:18px;" readonly></div>
					<div id="final_settlement_div"> </div></td>
				  </tr>
          </table>
    <!--weekly settelment-->
     
        </div>
    <div style="clear:both"></div>
  
					
							
		<span id="final_button">
		<button name="final_savepayrate" type="submit" class="btn btn-md btn-'.
		($gross > 0 ? 'success" onclick="return chk_final_confirmation();">
		<span class="glyphicon glyphicon-ok"></span>
		Finalize&nbsp;/&nbsp;Approve' :
		'danger" disabled><span class="glyphicon glyphicon-remove"></span> Cannot Approve Minus Gross Earnings').'
		</button>
		</span>
    		<button name="savepayrate" type="submit" class="btn btn-md btn-default" onclick="return chk_confirmation();">
		<span class="glyphicon glyphicon-ok"></span>
		Save changes
		</button></div></div>
	   </form>
';
	}
	
	
	

}	

?>