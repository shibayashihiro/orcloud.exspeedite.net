<?php

// $Id: sts_csv_class.php 5030 2023-04-12 20:31:34Z duncan $
// CSV class - meant to export data in CSV format

// no direct access
defined('_STS_INCLUDE') or die('Restricted access');

require_once( "sts_setting_class.php" );

class sts_csv {
	private $debug = false;
	private $table;
	private $setting_table;
	private $multi_company;
	private $output;
	private $name_length = 4;
	private $state_abbrev;
	private $state_name;
	private $line;
	private $filename;
	public	$result;

	public function __construct( $table = false, $match = false, $debug = false ) {
		$this->debug = $debug;
		$this->table = $table;
		$this->match = $match;
		if( $this->table ) {
			$this->setting_table = sts_setting::getInstance( $this->table->database, $this->debug );
			$this->multi_company = ($this->setting_table->get( 'option', 'MULTI_COMPANY' ) == 'true');
		}
		
		$this->load_states();
		if( $this->debug ) echo "<p>Create sts_csv</p>";
	}

	function __destruct() {
		if( $this->debug ) echo "<p>Destroy sts_csv</p>";
	}
	
	public function import( $files, $verbose = false ) {
		$result = false;
		$this->line = 0;
		if ($files["error"] > 0) {
			echo "<p>Error: " . $files["error"] . "</p>";
		} else {
			if( strtolower( substr(strrchr($files["name"], "."), 1)) <> 'csv' ) {
				echo "<p>".__METHOD__.": Error: file needs to end in .csv</p>";
			} else {
				ini_set("auto_detect_line_endings", true);
				if(($handle = fopen($files["tmp_name"], 'r')) !== FALSE) {
					set_time_limit(0);
					$header = fgetcsv($handle, 0, ',');
					$this->line++;
					
					if( $header !== FALSE ) {
						$num_columns = count($header);
						$result = array();
						while(($data = fgetcsv($handle, 0, ',')) !== FALSE) {
							$this->line++;
							if(count($data) <> $num_columns) {
								if( $verbose ) echo "<p>".__METHOD__.": $this->line - ".count($data)." columns <> ".$num_columns." in header </p>"; 
								break;
							}
							$row = array();
							for( $c=0; $c<$num_columns; $c++) {
								$row[$header[$c]] = $data[$c];
							}
							$result[] = $row;
							unset($row);
						}
					}
					fclose($handle);
				}
			}
		}
		if( $verbose ) echo "<p>".__METHOD__.": imported $this->line lines CSV, ".$num_columns." columns</p>"; 
		return $result;
	}

	private function load_states() {
		global $exspeedite_db;
		
		$states_table = new sts_table($exspeedite_db, STATES_TABLE, false );
		$this->state_abbrev = array();
		
		foreach( $states_table->fetch_rows() as $row ) {
			$this->state_abbrev[strtolower($row['STATE_NAME'])] = $row['abbrev'];
			$this->state_name[$row['abbrev']] = $row['STATE_NAME'];
		}
	}
	
	//! SCR# 601 - Remove trailing/leading space and unprintable characters
	public function clean( $str ) {
		return preg_replace( '/[^[:print:]]/', '',trim((string) $str) );
	}
	
	//! SCR# 601 - Map to two letter state/province or return empty string if unknown
	public function lookup_state( $state ) {
		$result = '';
		if( isset($this->state_abbrev[strtolower($state)])) {
			$result = $this->state_abbrev[strtolower($state)];
		} else if( isset($this->state_name[$state]) ) {
			$result = $state;
		}
		return $result;
	}
	
	public function header( $filename ) {
		
		$this->filename = $filename;
	    // Add some random characters
	    $keys = array_merge(range(0, 9), range('a', 'z'));
	
	    for ($i = 0; $i < $this->name_length; $i++) {
	        $this->filename .= $keys[array_rand($keys)];
	    }
		$this->filename .= '.csv';

		// output headers so that the file is downloaded rather than displayed
		header('Content-Type: text/csv; charset=utf-8');
		header('Content-Disposition: attachment; filename='.$this->filename);
		
		// create a file pointer connected to the output stream
		$this->output = fopen('php://output', 'w');
		ob_clean();
		
		if( is_object($this->table) && method_exists($this->table, 'log_event'))
			$this->table->log_event($_SESSION['EXT_USERNAME']." header( $this->filename )".
				($this->output == false ? " fopen failed!" : ""));
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

	// Format value for CSV Export to sage
	private function format( $column, $value, $layout ) {
		if( $this->debug ) echo "<p>".__METHOD__.": keys $column $value</p>";
		if( isset($layout[$column]) && isset($layout[$column]['format']) ) {
			switch( $layout[$column]['format'] ) {
				case 'date':
					$formatted = isset($value) && $value <> '' ? date("n/j/y", strtotime($value)) : '';
					break;
				case 'bool':
					$formatted = isset($value) && $value ? 'TRUE' : 'FALSE';
					break;
				default:
					$formatted = $value;
					break;
			}
		} else {
			$formatted = $value;
		}
		
		$formatted = str_ireplace('<br>', "\n", $formatted);
		return $formatted;
	}
	
	public function render( $layout, $toprow = false, $sort = false ) {
		
		$match = $this->match ? $this->match : '';
		//! Fetch data from the table
		if( $this->table ) {
			$this->result = $this->table->fetch_rows( $match, 
				( $layout ? $this->keys( $layout ) : '*'),
				( $sort ? $sort : '' ) );
		}
		
		if( is_array($this->result) && count($this->result) > 0 ) {
		
			if( is_object($this->table) && method_exists($this->table, 'log_event'))
				$this->table->log_event($_SESSION['EXT_USERNAME']." render( ".count($this->result)." rows exported to $this->filename )\n");

			if( $toprow <> false ) {
				fwrite($this->output, $toprow."\n");
			}
			
			if( isset($layout) && is_array($layout) ) {
				// output the column headings
				foreach($layout as $key => $row) {
					if( isset($row['format']) && $row['format'] != 'hidden' )
						$labels[] = isset($row['label']) ? $row['label'] : $key;
				}
			} else {
				$labels = array_keys( $this->result[0] );
			}
			if( false === fputcsv($this->output, $labels) )
				$this->table->log_event($_SESSION['EXT_USERNAME']." fputcsv failed to output headings");
			//fwrite($this->output, implode(',', $labels).PHP_EOL);
			
			foreach($this->result as $row) {
				if( isset($layout) && is_array($layout) ) {
					$formatted_row = array();
					foreach($layout as $key => $row2) {
						if( isset($row2['format']) && $row2['format'] != 'hidden' )
							$formatted_row[] = $this->format( $key, $row[$key], $layout );
					}
				} else {
					$formatted_row = $row;
				}
				if( false === fputcsv( $this->output, $formatted_row ) )
					$this->table->log_event($_SESSION['EXT_USERNAME']." fputcsv failed to output row");
				//fwrite($this->output, implode(',', $formatted_row).PHP_EOL);
			}
		} else {
			if( is_object($this->table) && method_exists($this->table, 'log_event'))
				$this->table->log_event($_SESSION['EXT_USERNAME']." render( NO rows exported to $this->filename )\n");
		}
		
		if( false === fclose($this->output) )
			$this->table->log_event($_SESSION['EXT_USERNAME']." fclose failed");
	}

	//! Generate a template for an import structure
	public function render_template( $layout ) {
		global $exspeedite_db;
		
		$labels = array();
		$formats = array();
		foreach($layout as $table) {
			$table_class = $table['class']::getInstance($exspeedite_db, $this->debug);
			foreach($table['rows'] as $column => $fields) {
				if( ! isset($fields['value']) ) {
					$labels[] = $fields['label'];
					switch( $fields['format'] ) {
						case 'bool':
							$formats[] = 'true/false';
							break;
						case 'date':
							$formats[] = 'YYYY-MM-DD or MM/DD/YYYY';
							break;
						case 'state':
							$formats[] = 'AK/AL/AS/AZ/AR etc.';
							break;
						case 'email':
							$formats[] = 'user@host.com';
							break;
						case 'enum':
							$choices = $table_class->get_enum_choices( $column );
							$formats[] = implode('/', $choices);
							break;
						default:
							$formats[] = isset($fields['format']) ? $fields['format'] : '';
					}
				}
			}
		}
		if( $this->debug ) {
			echo "<pre>labels, formats\n";
			var_dump($labels);
			var_dump($formats);
			var_dump(implode(',', $labels));
			var_dump(implode(',', $formats));
			echo "</pre>";
		} else {
			fputcsv($this->output, $labels);
			fputcsv($this->output, $formats);
			fclose($this->output);
		}
	}

	//! Parse imported CSV and add relevant rows
	public function parse( $data, $layout, $verbose = false ) {
		global $exspeedite_db;
		
		if( $this->debug ) echo "<p>".__METHOD__.": entry</p>";
		if( is_array($data) && count($data) > 0 ) {
			$count = 0;
			foreach( $data as $row ) {	// for each line of CSV
				$count++;
				$error = 0; // # errors
				if( $this->debug ) echo "<p>".__METHOD__.": row $count</p>";
				$key = array();
				foreach($layout as $table) {	// for each table
					if( $this->debug ) echo "<p>".__METHOD__.": table ".$table['table']." class ".$table['class']."</p>";
					$table_class = $table['class']::getInstance($exspeedite_db, $this->debug);
					$missing = false;
					$link_missing = false;
					$automatic_fields = 0;
					$add_fields = array();
					// For each field in the table
					foreach($table['rows'] as $column => $field) {
						if( $this->debug ) {
							echo "<pre>column, field\n";
							var_dump($column, $field);
							echo "</pre>";
						}
						// Link to prev table
						if( isset($field['format']) && $field['format'] == 'link' &&
							isset($field['value']) ) {
							if( isset($key[$field['value']])) {
								$add_fields[$column] = $key[$field['value']];
								$automatic_fields++;
							} else {
								$link_missing = true;
							}
						// Set this field to a value, no import
						} else if( isset($field['value']) ) {
							$add_fields[$column] = $field['value'];
							$automatic_fields++;
						// Import a field
						} else if( isset($field['label']) &&
							isset($row[$field['label']]) &&
							! empty($row[$field['label']]) ) {
							switch( $field['format'] ) {
								case 'bool':
									if( isset($row[$field['label']]))
										$add_fields[$column] = $row[$field['label']] ? 1 : 0;
									break;
									
								case 'date':
									if( isset($row[$field['label']]))
										$add_fields[$column] = date("Y-m-d", strtotime($row[$field['label']]));
									break;
									
								case 'enum':
									$choices = $table_class->get_enum_choices( $column );
									if( in_array($row[$field['label']], $choices)) {
										$add_fields[$column] = $row[$field['label']];
									}
									break;
								
								//! SCR# 517 - check column length
								case 'text':
									if( strlen($row[$field['label']]) > $table_class->get_max_length( $column ) ) {
										$error++;
										echo "<p>".__METHOD__.": Row ".$count.": ".$table['table'].
										" value <b>".$row[$field['label']]."</b> too long (max ".$table_class->get_max_length( $column ).") for column $column</p>";
									} else {
										$add_fields[$column] = $row[$field['label']];
									}
								
								default:
									$add_fields[$column] = $row[$field['label']];
							}
						// Set field to default if not already set
						} else if( isset($field['default'])) {
							$add_fields[$column] = $field['default'];
							$automatic_fields++;
						}
						if( $link_missing ) break;
						
						if( isset($field['required']) && $field['required'] &&
							! isset($add_fields[$column])) {
							$missing = true;
							echo "<p>".__METHOD__.": Row ".$count.": ".$table['table']." required column $column".(isset($field['label']) ? " / ".$field['label'] : "")." missing</p>";
						}
					}
				
					if( $this->debug ) echo "<p>".__METHOD__.": missing = ".($missing ? "true" : "false")." count(add_fields) = ".count($add_fields)." automatic_fields = $automatic_fields</p>";
					if( $error == 0 && ! $missing && count($add_fields) > $automatic_fields ){
						if( $this->debug ) {
							$key[$table['primary']] = 999;
							echo "<pre>".__METHOD__.": ".$table['class']."->add()\n";
							var_dump($add_fields, $key);
							echo "</pre>";
						} else {
							$add_result = $table_class->add($add_fields);
							if( $add_result ) {
								$key[$table['primary']] = $add_result;
								echo "<p>".__METHOD__.": Row ".$count.": ".(count($key)>1 ? "&nbsp;&nbsp;&nbsp;&nbsp;" : "")."Added ".$table['table']." record# ".$add_result."</p>";
								//var_dump($add_fields );
							} else {
								echo "<p>".__METHOD__.": Row ".$count.": ".$table['class'].
									" Error ".$table_class->error()."</p>";
							}
						}
					}
				}
			}
		}
	}
}