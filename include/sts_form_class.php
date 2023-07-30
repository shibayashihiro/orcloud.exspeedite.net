<?php

// $Id: sts_form_class.php 5134 2023-06-13 22:09:13Z dev $
// Form class - display form, process input
// Used for adding records, or editing records

// no direct access
defined('_STS_INCLUDE') or die('Restricted access');
require_once( "sts_email_class.php" );
require_once( "sts_setting_class.php" );
require_once( "sts_attachment_class.php" );

class sts_form {
	
	private $fields;		// array of fields and layout information
	private $form;			// array of form information
	private $values;		// array of values
	private $count_fields;
	private $valid_alignments = array('left', 'right', 'center');
	private $valid_formats = array('text', 'groups', 'states', 'email', 'date', 
		'datetime', 'link', 'num0', 'num2', 'num0nc', 'num2nc', 'htmltext');
	private $table;
	private $states;
	private $debug = false;
	private $error ="";
	private $file_errors = array(1 => 'php.ini max file size exceeded', 
        	    2 => 'html form max file size exceeded', 
            	3 => 'file upload was only partial', 
            	4 => 'no file was attached');
    private $countries = array('USA', 'Canada');
    private $currencies = array('USD', 'CAD');
	private $client_matches;
	private $autosave = false;
	private $noconfirm = false;
	private $export_sage50;
	private $table_select_max_length = 60;
	private $client_id;				//! SCR# 584 - New coulum Client_ID
	private $changes_str;
	private $max_field_length = 25;
	private $setting_table;

	public function __construct( $form, $fields, $table = false, $debug = false ) {
		global $exspeedite_db;
		
		$this->debug = $debug;
		$this->fields = $fields;
		$this->count_fields = count( $this->fields );
		$this->form = $form;
		$this->table = $table;
		
		if( $this->debug ) echo "<h3>".__METHOD__.": Create sts_form with $this->count_fields fields.</h3>";
		if( $this->debug ) {
			echo "<p>fields = </p>
			<pre>";
			var_dump($this->fields);
			echo "</pre>";
		}
		if( $this->debug ) echo "<h3>".__METHOD__.": before load_states.</h3>";
		$this->load_states();
		if( $this->debug ) echo "<h3>".__METHOD__.": after load_states.</h3>";
		$this->setting_table = sts_setting::getInstance($exspeedite_db, $debug);
		$this->client_matches = (int) $this->setting_table->get( 'option', 'CLIENT_MATCHES' );
		if( $this->client_matches <= 0 ) $this->client_matches = 20;
		$this->export_sage50 = $this->setting_table->get( 'api', 'EXPORT_SAGE50_CSV' ) == 'true';
		$this->client_id = ($this->setting_table->get("option", "CLIENT_ID") == 'true');
		if( $this->debug ) echo "<h3>".__METHOD__.": END.</h3>";
	}
	
	function __destruct() {
		if( $this->debug ) echo "<p>Destroy sts_form</p>";
	}
	
	public function last_changes() {
		return $this->changes_str;
	}

	//! Call this if you want the form to save contents on click
	public function set_autosave( $setting = true ) {
		$this->autosave = $setting;
	}
	
	//! Call this if you want the form to save contents on click
	public function set_noconfirm( $setting = true ) {
		$this->noconfirm = $setting;
	}
	
	private function load_states() {
		if( $this->debug ) echo "<h3>".__METHOD__.": entrance.</h3>";
		if( $this->debug ) {
			echo "<pre>cache\n";
			var_dump($this->table->cache);
			echo "</pre>";
		}
		$whole = $this->table->cache->get_whole_cache();
		if( $this->debug ) echo "<h3>".__METHOD__.": after get_whole_cache.</h3>";
		$this->states = $whole["STATE_NAME"];
		if( $this->debug ) echo "<h3>".__METHOD__.": exit.</h3>";
	}

	private function align( $key, $layout ) {
		return is_array($layout) && isset($layout[$key]) &&
			isset($layout[$key]['align']) && in_array($layout[$key]['align'], $this->valid_alignments) ?
			' class="text-'.$layout[$key]['align'].'"' : '';
	}
	
	private function scaleImageFileToBlob($file) {
	
		if( $this->debug ) echo "<p>scaleImageFileToBlob: $file </p>";
		
		$source_pic = $file;
		$max_width = 1024;
		$max_height = 1024;
		
		list($width, $height, $image_type) = getimagesize($file);
		if( $this->debug ) echo "<p>scaleImageFileToBlob: width = $width,  height = $height</p>";
		
		switch ($image_type) {
			case 1: $src = imagecreatefromgif($file); break;
			case 2: $src = imagecreatefromjpeg($file);  break;
			case 3: $src = imagecreatefrompng($file); break;
			default: return '';  break;
		}
		
		$x_ratio = $max_width / $width;
		$y_ratio = $max_height / $height;
		
		if( ($width <= $max_width) && ($height <= $max_height) ){
			$tn_width = $width;
			$tn_height = $height;
		}elseif (($x_ratio * $height) < $max_height){
			$tn_height = ceil($x_ratio * $height);
			$tn_width = $max_width;
		}else{
			$tn_width = ceil($y_ratio * $width);
			$tn_height = $max_height;
		}
		
		if( $this->debug ) echo "<p>scaleImageFileToBlob: scale to $tn_width,  $tn_height</p>";
		
		$tmp = imagecreatetruecolor($tn_width,$tn_height);
		
		/* Check if this image is PNG or GIF, then set if Transparent*/
		if(($image_type == 1) OR ($image_type==3)) {
			imagealphablending($tmp, false);
			imagesavealpha($tmp,true);
			$transparent = imagecolorallocatealpha($tmp, 255, 255, 255, 127);
			imagefilledrectangle($tmp, 0, 0, $tn_width, $tn_height, $transparent);
		}
		$result = imagecopyresampled($tmp,$src,0,0,0,0,$tn_width, $tn_height,$width,$height);
		if( $this->debug ) echo "<p>scaleImageFileToBlob: imagecopyresampled ".($result ? "true" : "false")."</p>";
		
		/*
		* imageXXX() only has two options, save as a file, or send to the browser.
		* It does not provide you the oppurtunity to manipulate the final GIF/JPG/PNG file stream
		* So I start the output buffering, use imageXXX() to output the data stream to the browser,
		* get the contents of the stream, and use clean to silently discard the buffered contents.
		*/
		ob_start();
		
		/*switch ($image_type) {
			case 1: imagegif($tmp); break;
			case 2: imagejpeg($tmp, NULL, 100);  break; // best quality
			case 3: imagepng($tmp, NULL, 0); break; // no compression
			default: echo ''; break;
		}
		*/
		imagejpeg($tmp, NULL, 100);
		
		$final_image = ob_get_contents();
		
		ob_end_clean();
		
		return $final_image;
	}

	
	private function render_field( $name, $field_data, $value = false ) {
		global $sts_opt_groups, $sts_title, $exspeedite_db, $sts_edit_pages,
			$sts_license_endorsements;
		
		$output = '';
		
		if( $this->debug ) echo "<p>render_field $name type = ".$field_data['format']." value = ".($value === false ? 'false ' : (is_null($value) ? 'NULL' : $value) )."</p>";
		if( ! is_null($value) && ! $value && ! empty($field_data['value']) )
			$value = $field_data['value'];
		if( is_null($value) )
			$value = false;
		if( $value !== false )
			$value = trim($value);
		
		switch( $field_data['format'] ) {
			case 'valid':	// !Valid
				if( isset($value) && $value <> false ) {
					if( $value == 'valid' ) {
						$source = isset($field_data['source']) ? $field_data['source'] : '';
						$source = $source <> '' && isset($this->values[$source]) ? $this->values[$source] : 'PC*Miler';
						$lat = isset($field_data['lat']) ? $field_data['lat'] : '';
						$lat = $lat <> '' && isset($this->values[$lat]) ? floatval($this->values[$lat]) : 0;
						$lon = isset($field_data['lon']) ? $field_data['lon'] : '';
						$lon = $lon <> '' && isset($this->values[$lon]) ? floatval($this->values[$lon]) : 0;
						$output = '<span  id="'.$name.'" class="text-success inform" data-content="Confirmed via '.$source.($lat <> 0 && $lon <> 0 ? '<br>lat='.$lat.' lon='.$lon.' <a href=\'https://www.google.ca/maps/@'.$lat.','.$lon.',16z?hl=en\' target=\'_blank\'><span class=\'glyphicon glyphicon-new-window\'></span></a>' : '').'"><span class="glyphicon glyphicon-ok"></span></span>';
					} else if( in_array($value, array('error','warning')) ) {
						$code = isset($field_data['code']) ? $field_data['code'] : '';
						$code = $code <> '' && isset($this->values[$code]) ? $this->values[$code] : '';
						$descr = isset($field_data['descr']) ? $field_data['descr'] : '';
						$descr = $descr <> '' && isset($this->values[$descr]) ? '<br>'.$this->values[$descr] : '';
						$popup_text = $code.$descr;
						
						$output = '<span  id="'.$name.'" class="text-'.($value == 'error' ? 'danger' : 'muted').' inform" data-content="'.$popup_text.'"><span class="glyphicon glyphicon-'.($value == 'error' ? 'remove' : 'warning-sign').'"></span></span>';
					}
				} else
					$output = '<span id="'.$name.'"></span>';
				break;

			case 'hidden': //! hidden
				if( isset($value) ) {
					$output .= '
	<input name="'.$name.'" id="'.$name.'" type="hidden" value="'.$value.'">';
				}
				break;
			
			case 'hidden-req': //! hidden-req
				$output .= '
	<input name="'.$name.'" id="'.$name.'" type="hidden" value="'.(is_null($value) ? 'NULL' : $value).'">';
				break;
			
			case 'static': //! static
				if( $value !== false ) {
					
					$v = isset($field_data['link']) && isset($value) && $value > 0 ? 
						'<a href="'.str_replace('%pk%', $this->values[$this->table->primary_key], $field_data['link']).$value.'">'.$value.'</a>' : (isset($value) ?
						(isset($field_data['decimal']) ? number_format(floatval($value), $field_data['decimal'], '.', '') : $value) : '&nbsp;');
				
					$output .= '
	<p id="'.$name.'_STATIC" class="form-control-static'.
	(isset($field_data['align']) ? ' text-'.$field_data['align'] : '').
	'">'.(empty($v) ? '&nbsp;' : $v).'</p>
	<input id="'.$name.'" name="'.$name.'" type="hidden" value="'.(is_null($value) ? 'NULL' : $value).'">';
				} else if( isset($field_data['placeholder']) ) {
					$v = isset($field_data['link']) ? 
						'<a href="'.str_replace('%pk%', $this->values[$this->table->primary_key], $field_data['link']).$field_data['placeholder'].'">'.$field_data['placeholder'].'</a>' : $field_data['placeholder'];
				
					$output .= '
	<p id="'.$name.'_STATIC" class="form-control-static'.
	(isset($field_data['align']) ? ' text-'.$field_data['align'] : '').
	'">'.(empty($v) ? '&nbsp;' : $v).'</p>';
				} else {
					$output .= '&nbsp;';
				}
				break;
			
			case 'inline': //! inline
				//! SCR# 367 - inline does not include hidden field.
				if( $value !== false ) {
					$v = isset($field_data['link']) && $value > 0 ? 
						'<a href="'.str_replace('%pk%', $this->values[$this->table->primary_key], $field_data['link']).$value.'">'.$value.'</a>' : $value;
				
					$output .= $v;
				} else if( isset($field_data['placeholder']) ) {
					$v = isset($field_data['link']) ? 
						'<a href="'.str_replace('%pk%', $this->values[$this->table->primary_key], $field_data['link']).$field_data['placeholder'].'">'.$field_data['placeholder'].'</a>' : $field_data['placeholder'];
				
					$output .= $v;
				}
				break;
			
			case 'enum': //! enum
				$choices = $this->table->get_enum_choices( $name );
				//! SCR# 575 - for readonly form, make sure this is readonly
				if( isset($this->form['readonly']) && $this->form['readonly'] &&
					$name != 'INVOICE_EMAIL_STATUS' ) {
					$output .= '<p id="'.$name.'_STATIC" class="form-control-static'.
						(isset($field_data['align']) ? ' text-'.$field_data['align'] : '').
						'">'.(empty($value) ? '&nbsp;' : $value).'</p>';
					
				} else
				if( is_array($choices) ) {
					$output .= '
			<select class="form-control" name="'.$name.'" id="'.$name.'" '.(isset($field_data['extras']) && $name != 'INVOICE_EMAIL_STATUS' ? $field_data['extras'] : '').'>';

					foreach( $choices as $choice ) {
						$choice = str_replace("''", "'", $choice);
						$output .= '
				<option value="'.$choice.'" '.
							($choice==$value ? 'selected' : '').'>'.$choice.'</option>';
					}
					$output .= '
			</select>';

				}
				break;
			
			case 'enum2': //! enum2 - radio buttons
				$choices = $this->table->get_enum_choices( $name );
				
				if( is_array($choices) ) {
					$output .= '
			<div class="btn-group" data-toggle="buttons">
			';

					$count = 0;
					foreach( $choices as $choice ) {
						$count++;
						$choice = str_replace("''", "'", $choice);
						$checked = $choice==$value || $value == false && $count == 1;
						$output .= '
				    <label class="btn btn-sm btn-default'.
							($checked ? ' active' : '').'">
						<input type="radio" name="'.$name.'" id="'.$name.$count.'" value="'.$choice.'" '.
							($checked ? 'checked' : '').'> '.$choice.'
					</label>
					';
					}
					$output .= '
			</div>';

				}
				break;
			
			case 'state': //! state
				//! SCR# 575 - for readonly form, make sure this is readonly
				if( isset($this->form['readonly']) && $this->form['readonly'] ) {
					$output .= '<p id="'.$name.'_STATIC" class="form-control-static'.
						(isset($field_data['align']) ? ' text-'.$field_data['align'] : '').
						'">'.(empty($value) ? '&nbsp;' : $value).'</p>';
					
				} else
				if( is_array($this->states) ) {
					$output .= '
			<select class="form-control" name="'.$name.'" id="'.$name.'" '.(isset($field_data['extras']) ? $field_data['extras'] : '').'>
				<option value=""'.
							($value=='' ? ' selected' : '').'>Choose State</option>';
					foreach( $this->states as $abbrev => $state_name ) {
						$output .= '
				<option value="'.$abbrev.'" '.
							($abbrev==$value ? 'selected' : '').'>'.$abbrev.' - '.$state_name.'</option>';
					}
					$output .= '
			</select>';

				}
				break;
			
			case 'states': //! states - for IFTA
				foreach( $this->states as $abbrev => $full ) {
					$output .= '
			<label class="col-sm-4">
			<input type="checkbox" class="my-switch" value="'.$abbrev.'" name="'.$name.'_'.$abbrev.'"';
						if( $value && in_array($abbrev, explode(',', $value)) )
							$output .= " checked ";
						$output .= '> '.$full.'
						</label>
				';
				}
				break;

			case 'country': //! country
				if( is_array($this->countries) ) {
					$output .= '
			<select class="form-control" name="'.$name.'" id="'.$name.'" '.(isset($field_data['extras']) ? $field_data['extras'] : '').'>';
				//<option value=""'.
				//			($value=='' ? ' selected' : '').'>Choose Country</option>';
					foreach( $this->countries as $country ) {
						$output .= '
				<option value="'.$country.'" '.
							($country==$value ? 'selected' : '').'>'.$country.'</option>';
					}
					$output .= '
			</select>';

				}
				break;
			
			case 'currency': //! currency
				if( is_array($this->currencies) ) {
					$output .= '
			<select class="form-control" name="'.$name.'" id="'.$name.'" '.(isset($field_data['extras']) ? $field_data['extras'] : '').'>
				<option value=""'.
							($value=='' ? ' selected' : '').'>Choose Currency</option>';
					foreach( $this->currencies as $currency ) {
						$output .= '
				<option value="'.$currency.'" '.
							($currency==$value ? 'selected' : '').'>'.$currency.'</option>';
					}
					$output .= '
			</select>';

				}
				break;
			
			case 'table':	//!table - cross-table links
			// Use for when you have a field that indexes another table.
				if( isset($field_data['table']) && 
					isset($field_data['key']) && 
					isset($field_data['fields']) ) {
					if( $this->debug ) echo "<p>".__METHOD__.": table = ".$field_data['table']." key = ".$field_data['key']." fields = ".$field_data['fields']."</p>";
					
					$table = new sts_table( $exspeedite_db, $field_data['table'], $this->debug );
					if( $table ) {
						$fields = "DISTINCT ".$field_data['key'].", ".(isset($field_data['fields2']) ? $field_data['fields2'] : $field_data['fields']);
						if( isset($field_data['back']) )
							$fields .= ", (SELECT ".$field_data['back']."
								FROM ".$this->table->table_name."
								WHERE ".$field_data['key']." = ".(isset($field_data['pk']) ? $field_data['pk'] : $name)."
								LIMIT 0 , 1) BACK";

						$match = isset($field_data['condition']) ? $field_data['condition'] : '';
						if( ! empty($match) &&
							strpos($match, '##') !== false && is_array($this->values) ) {
							foreach( $this->values as $n => $v) {
								$match = preg_replace('/##'.$n.'##/', $v, $match, 1);
							}					
						}
						
						$order = isset($field_data['order']) ? $field_data['order'] : $field_data['fields'];
						if( isset($field_data['raw']) || isset($field_data['inline']) || isset($field_data['static']) || isset($this->form['readonly']) ) 
							$match .= ($match <> '' ? " AND " : "").$field_data['key']." = ".
								(isset($field_data['pk']) ? $this->values[$field_data['pk']] : $this->table->enquote_string($name, $value));
						$result = $table->fetch_rows($match, $fields, $order);
						
						if( isset($field_data['raw']) ) {
							if( $result && count($result) > 0 ) {
								$tfields = array();
								foreach( explode(',', $field_data['fields']) as $tfield) {
									$tfields[] = $result[0][$tfield];
								}
								$output .= implode((isset($field_data['separator']) ? $field_data['separator'] : ' '), $tfields);
							}
						} else if( isset($field_data['inline']) ) {
							//! SCR# 367 - inline does not include hidden field.
							if( isset($field_data['link']) && $value > 0 )
								$output .= '<a href="'.$field_data['link'].$value.'">';
							
							if( $result && count($result) > 0 ) {
								$tfields = array();
								foreach( explode(',', $field_data['fields']) as $tfield) {
									$tfields[] = $result[0][$tfield];
								}
								$output .= implode((isset($field_data['separator']) ? $field_data['separator'] : ' '), $tfields);
							}
							if( isset($field_data['link']) && $value > 0 )
								$output .= '</a>';
								
						//! SCR# 575 - for readonly form, make sure this is readonly
						} else if( isset($this->form['readonly']) || isset($field_data['static']) ) {
							$output .= '<p id="'.$name.'_STATIC" class="form-control-static'.
								(isset($field_data['align']) ? ' text-'.$field_data['align'] : '').
								'">';
							if( isset($field_data['link']) && $value > 0 )
								$output .= '<a href="'.$field_data['link'].$value.'">';
							
							if( $result && count($result) > 0 ) {
								$tfields = array();								
								foreach( explode(',', $field_data['fields']) as $tfield) {
									$tfields[] = $result[0][$tfield];
								}
								$output .= implode((isset($field_data['separator']) ? $field_data['separator'] : ' '), $tfields);
							}
							if( isset($field_data['link']) && $value > 0 )
								$output .= '</a>';
								
							$output .='&nbsp;</p>
							<input name="'.$name.'" id="'.$name.'" type="hidden" value="'.$value.'">';
						} else if( $result && count($result) > 0 ) {

							$add_link_button = isset($sts_edit_pages[$field_data['table']]) &&
								isset($value) && $value > 0 && ! isset($field_data['nolink']);
							

					$output .= '
			'.($add_link_button ? '<div class="input-group">':'').'
			<select class="form-control" name="'.$name.'" id="'.$name.'" '.(isset($field_data['extras']) ? $field_data['extras'] : '').'>';
				if( $this->table->is_nullable($name) )
					$output .= '<option value="NULL"'.
								($value == '' ? ' selected' : '').'>none</option>';

					foreach( $result as $row ) {
						$output .= '
				<option value="'.$row[$field_data['key']].'" '.
							($row[$field_data['key']]==$value ? 'selected' : '').'>';

							$kk = array();
							foreach( explode(',', $field_data['fields']) as $tfield) {
								$kk[] = $row[$tfield];
							}
							$output .= substr(implode((isset($field_data['separator']) ? $field_data['separator'] : ' '), $kk), 0, $this->table_select_max_length);
							
							if( isset($field_data['back']) && isset($row['BACK']) )
								$output .= ' - '.$row['BACK'];
							$output .= '</option>';
					}
					$output .= '
			</select>';
					if( $add_link_button )
						$output .= '
						<span class="input-group-btn">
						<a class="btn btn-default" href="'.$sts_edit_pages[$field_data['table']].$value.'"><span class="glyphicon glyphicon-link"></span></a>
						</span>
						</div>';

					} else {
						$output .= '<p class="form-control-static" id="'.$name.'">No choices</p>';
					}
					}

				}
				break;

			case 'mtable':	//!mtable - cross-table links, multiple links
				if( isset($field_data['table']) && 
					isset($field_data['key']) && 
					isset($field_data['fields']) ) {
					if( $this->debug ) echo "<p>table = ".$field_data['table']." key = ".$field_data['key']." fields = ".$field_data['fields']."</p>";
					
					$table = new sts_table( $exspeedite_db, $field_data['table'], $this->debug );
					if( $table ) {
						$fields = $field_data['key'].", ".$field_data['fields'];

						$match = isset($field_data['condition']) ? $field_data['condition'] : '';
						if( isset($field_data['static']) ) 
							$match .= ($match <> '' ? " AND " : "").$field_data['key']." = ".
								$this->table->enquote_string($name, $value);
						$result = $table->fetch_rows($match, $fields);
						
						if( isset($field_data['static']) ) {
							$output .= '<p class="form-control-static">'.$result[0][$field_data['fields']].'</p>
							<input name="'.$name.'" type="hidden" value="'.$value.'">';
						} else if( $result && count($result) > 0 ) {							

					$output .= '
			<select multiple class="form-control" name="'.$name.'[]" id="'.$name.'" '.(isset($field_data['extras']) ? $field_data['extras'] : '').'>';
				if( $this->table->is_nullable($name) )
					$output .= '<option value="NULL"'.
								($value == '' ? ' selected' : '').'>none</option>';

					$values = explode(',', $value);
					
					foreach( $result as $row ) {
						$selected = false;
						foreach( $values as $possible ) {
							if( $row[$field_data['key']] == $possible ) {
								$selected = true;
								break;
							}
						}
						$output .= '
				<option value="'.$row[$field_data['key']].'" '.
							($selected ? 'selected' : '').'>';

							foreach( explode(',', $field_data['fields']) as $tfield) {
								$output .= $row[$tfield].' ';
							}
							$output .= '</option>';
					}
					$output .= '
			</select>';

					} else {
						$output .= '<p class="form-control-static">No choices</p>';
					}
					}

				}
				break;

			case 'btable': //! btable - BACK LINK 
				if( isset($field_data['table']) && 
					isset($field_data['key']) && 
					isset($field_data['fields']) && 
					isset($field_data['pk']) ) {
					
					$table = new sts_table( $exspeedite_db, $field_data['table'], $this->debug );
					if( $table ) {
						$result = $table->fetch_rows($field_data['key'].' = '.$this->values[$field_data['pk']], $field_data['fields'].' '.$name.', '.$table->primary_key);
						
						if( $result ) {
							if( isset($field_data['hidden']) && $field_data['hidden'] ) {
								$output .= '
	<input name="'.$name.'" id="'.$name.'" type="hidden" value="'.$result[0][$name].'">';

							} else {
					
							$output .= '
			<p class="form-control-static">'.( isset($sts_edit_pages[$field_data['table']]) ? 
				'<a href="'.$sts_edit_pages[$field_data['table']].$result[0][$table->primary_key].'">'.$result[0][$name].'</a>' : $result[0][$name] ).'</p>';
							}

						}
					}

				}
				break;
			
			case 'bool': //! bool
			if( $this->debug ) echo "<p>bool value = $value</p>";
					$output .= '
			
			<input type="checkbox" class="my-switch"'.
			 (isset($field_data['extras']) && $field_data['extras'] == 'readonly' ? ' disabled' : '').
			' id="'.$name.'" value="'.$name.'" name="'.$name.'"';
						if( $value == "1" || $value == "true" )
							$output .= " checked ";
						$output .= '>
				';
				break;

			case 'bool2': //! bool2
					$output .= '
			
			<input '.
			(isset($field_data['align']) && ($field_data['align'] == 'right') ?
			 'style="float: right;"' : '' ).
			 'type="checkbox" data-text-label="'.$field_data['label'].'"'.
			 (isset($field_data['extras']) && $field_data['extras'] == 'readonly' ? ' onclick="return false;"' : '').
			 ' id="'.$name.'" value="'.$name.'" name="'.$name.'"'; // data-on="success" data-off="default" 
						if( $value == "1" || $value == "true" )
							$output .= " checked ";
						$output .= '>
				';
				break;

			case 'zip': //! zip
				$output .= '
			<input class="form-control" name="'.$name.'" id="'.$name.'" type="text"  
	pattern="(\d{5}([\-]\d{4})?)|([A-Za-z][0-9][A-Za-z] [0-9][A-Za-z][0-9])" placeholder="'.( isset($field_data['placeholder']) ? $field_data['placeholder'] : $field_data['label']).'" ';
				if( isset($field_data['length']) )
					$output .= 'maxlength="'.$field_data['length'].'" ';
				else {
					$length = $this->table ? $this->table->get_max_length( $name ) : 0;
					if( $length )
						$output .= 'maxlength="'.$length.'" ';
				}
				
				if( $value ) {
					$output .= 'value="'.
					(isset($field_data['process']) && $field_data['process'] == 'decrypt' ? $this->table->decryptData( $value ) : $value).'" ';
					}
					
				$output .= (isset($field_data['extras']) ? $field_data['extras'] : '').'>
				
	<script language="JavaScript" type="text/javascript"><!--
		$(document).ready( function () {
		
			var '.$name.'_zips = new Bloodhound({
			  name: \''.$name.'\',
			  remote : {
				  url: \'exp_suggest_zip.php?code=Balsamic&query=%QUERY\',
				  wildcard: \'%QUERY\'
			  },
			  datumTokenizer: Bloodhound.tokenizers.obj.whitespace(\'ZipCode\'),
			  queryTokenizer: Bloodhound.tokenizers.whitespace
			});
			
			'.$name.'_zips.initialize();

			$(\'#'.$name.':not([readonly])\').typeahead(null, {
			  name: \''.$name.'\',
			  minLength: 2,
			  limit: 10,
			  highlight: true,
			  display: \'ZipCode\',
			  source: '.$name.'_zips,
			    templates: {
				empty: function(val){
					return \'<p class="bg-danger text-danger"><strong>Unrecognized ZIP/Postal code</strong> <a href="exp_suggest_zip.php?missing_zip=\'+val.query+\'" target="blank">Please check!</a></p>\';
			    },
				
			    suggestion: Handlebars.compile(
			      \'<p><strong>{{ZipCode}}</strong> – {{CityMixedCase}}, {{State}}</p>\'
			    )
			  }
			}).on(\'typeahead:asyncrequest\', function(e) {
				$(e.target).addClass(\'loading\');
			})
			.on(\'typeahead:asynccancel typeahead:asyncreceive\', function(e) {
				$(e.target).removeClass(\'loading\');
			});
		});
	//--></script>
				';
				break;

			case 'zone': //! zone
				$output .= '
			<input class="form-control" name="'.$name.'" id="'.$name.'" type="text"  
				placeholder="'.( isset($field_data['placeholder']) ? $field_data['placeholder'] : $field_data['label']).'" autocomplete="off" ';
				if( isset($field_data['length']) )
					$output .= 'maxlength="'.$field_data['length'].'" ';
				else {
					$length = $this->table->get_max_length( $name );
					if( $length )
						$output .= 'maxlength="'.$length.'" ';
				}
				
				if( $value ) {
					$output .= 'value="'.
					(isset($field_data['process']) && $field_data['process'] == 'decrypt' ? $this->table->decryptData( $value ) : $value).'" ';
					}
					
				$output .= (isset($field_data['extras']) ? $field_data['extras'] : '').'>
				
	<script language="JavaScript" type="text/javascript"><!--
		$(document).ready( function () {
		
			var '.$name.'_zones = new Bloodhound({
			  name: \''.$name.'\',
			  remote : {
				  url: \'exp_suggest_zone.php?code=Red&query=%QUERY\',
				  wildcard: \'%QUERY\'
			  },
			  datumTokenizer: Bloodhound.tokenizers.obj.whitespace(\'ZipCode\'),
			  queryTokenizer: Bloodhound.tokenizers.whitespace
			});
			
			'.$name.'_zones.initialize();

			$(\'#'.$name.':not([readonly])\').typeahead(null, {
			  name: \''.$name.'\',
			  minLength: 2,
			  limit: 10,
			  highlight: true,
			  display: \'ZipCode\',
			  source: '.$name.'_zones,
			    templates: {
			    suggestion: Handlebars.compile(
			      \'<p><strong>{{ZipCode}}</strong> – {{CityMixedCase}}, {{State}}</p>\'
			    )
			  }
			});
		});
	//--></script>
				';
				break;

			case 'client': //! client
				$ctype = isset($field_data['ctype']) ? $field_data['ctype'] : 'bill_to';
				if( $ctype == 'caller' )
					$hb = '<p><strong>{{CLIENT_NAME}}/{{LABEL}}</strong><br>{{CONTACT_NAME}}, {{PHONE_OFFICE}}'.($this->client_id ? '<br>Client ID: {{CLIENT_ID}}' : '').($this->export_sage50 ? '<br>Sage50: {{SAGE50_CLIENTID}}' : '').'</p>';
				else //! SCR# 278 - include ADDRESS
					$hb = '<p><strong>{{CLIENT_NAME}}/{{LABEL}}</strong><br>{{CONTACT_TYPE}}, {{ADDRESS}}, {{CITY}}, {{STATE}}, {{ZIP_CODE}}'.($this->client_id ? '<br>Client ID: {{CLIENT_ID}}' : '').($this->export_sage50 ? '<br>Sage50: {{SAGE50_CLIENTID}}' : '').'</p>';
				$output .= '
			<input class="form-control" name="'.$name.'" id="'.$name.'" type="text"  autocomplete="off"
				placeholder="'.( isset($field_data['placeholder']) ? $field_data['placeholder'] : $field_data['label']).'" ';
				if( isset($field_data['length']) )
					$output .= 'maxlength="'.$field_data['length'].'" ';
				else {
					$length = $this->table->get_max_length( $name );
					if( $length )
						$output .= 'maxlength="'.$length.'" ';
				}
				
				if( $value ) {
					$output .= 'value="'.
					(isset($field_data['process']) && $field_data['process'] == 'decrypt' ? $this->table->decryptData( $value ) : $value).'" ';
					}
					
				$output .= (isset($field_data['extras']) ? $field_data['extras'] : '').'>
				
	<script language="JavaScript" type="text/javascript"><!--
		// Comment out so it is global scope
		//$(document).ready( function () {
		
			var '.$name.'_clients = new Bloodhound({
			  name: \''.$name.'\',
			  remote : {
				  url: \'exp_suggest_client.php?code=Vinegar&type='.$ctype.'&query=%QUERY\',
				  wildcard: \'%QUERY\'
			  },
			  datumTokenizer: Bloodhound.tokenizers.obj.whitespace(\'LABEL\'),
			  queryTokenizer: Bloodhound.tokenizers.whitespace
			});
						
			'.$name.'_clients.initialize();

			$(\'#'.$name.':not([readonly])\').typeahead(null, {
			  name: \''.$name.'\',
			  minLength: 2,
			  limit: '.$this->client_matches.',
			  highlight: true,
			  display: \''.($ctype == 'caller' ? 'CONTACT_NAME' : (isset($field_data['CLIENT_LABEL']) ? 'LABEL' : 'CLIENT_NAME')).'\',
			  source: '.$name.'_clients,
			  templates: {
			  	suggestion: Handlebars.compile(
			      \''.$hb.'\')
			  }
			});
		//});
	//--></script>
				';
				break;

			case 'image': //! image
				if( strtolower($this->table->column_type($name)) <> 'mediumblob') {
					$email = sts_email::getInstance($this->database, $this->debug);
					$email->send_alert('sts_result > render_field: image Column '.
						$this->table->table_name.'.'.$name.
						' should be of type mediumblob, rather than '.
						strtolower($this->table->column_type($name)), EXT_ERROR_ERROR );
				}
				$output .= '
			<img src="'.($value ? $value : 'images/no-image.jpg').'" width="200" height="200" class="img-responsive img-thumbnail">
			<input name="'.$name.'" id="'.$name.'" type="file"  
				 '; //class="form-control" style="height: auto !important;" 
				
				if( false && $value ) {
					$output .= 'value="'.
					(isset($field_data['process']) && $field_data['process'] == 'decrypt' ? $this->table->decryptData( $value ) : $value).'" ';
					}
					//!images	
				$output .= (isset($field_data['extras']) ? $field_data['extras'] : '').'>
				
	<div class="modal fade bs-example-modal-sm" tabindex="-1" role="dialog" aria-labelledby="mySmallModalLabel" aria-hidden="true" id="'.$name.'_modal">
	  <div class="modal-dialog">
		<div class="modal-content">
		<div class="modal-header">
			<button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
			<h4 class="modal-title" id="myModalLabel"><span class="text-success"><strong>'.$sts_title.'</strong></span> - Invalid File Extension</h4>
		</div>
		<div class="modal-body">
			<p>The '.(isset($field_data['label']) ? $field_data['label'] : $name).' field requires a file type of either gif, png, jpg or jpeg. Please select another image file and try again</p>
		</div>
		</div>
		</div>
	</div>

				
	<script language="JavaScript" type="text/javascript"><!--
		$(document).ready( function () {
			$("#'.$this->form['name'].'").bind("submit", function() {
				if( $(\'#'.$name.'\').val()) {
					var ext = $(\'#'.$name.'\').val().split(\'.\').pop().toLowerCase();
					if($.inArray(ext, [\'gif\',\'png\',\'jpg\',\'jpeg\']) == -1) {
					    $(\'#'.$name.'_modal\').modal();
					    $(\'#'.$name.'\').val(\'\');
					    return false;
					}
				}
			});
		});
	//--></script>				
				';
				break;

			case 'attachment': //! attachment
				$output .= '
			<input name="'.$name.'" id="'.$name.'" type="file" capture="camera" 
				 '; //class="form-control" style="height: auto !important;" 
				
				$output .= (isset($field_data['extras']) ? $field_data['extras'] : '').'>
				';
				break;

			case 'textarea': //! textarea
				$output .= '
			<textarea class="form-control" name="'.$name.'" id="'.$name.'"  
				placeholder="'.( isset($field_data['placeholder']) ? $field_data['placeholder'] :
					(isset($field_data['label']) ? $field_data['label'] : 'textarea') ).'" ';
				if( isset($field_data['length']) )
					$output .= 'maxlength="'.$field_data['length'].'" ';
				else {
					$length = $this->table->get_max_length( $name );
					if( $length )
						$output .= 'maxlength="'.$length.'" ';
				}
				$output .= (isset($field_data['extras']) ? $field_data['extras'] : '').'>';
				
				if( $value ) {
					$output .= isset($field_data['process']) && $field_data['process'] == 'decrypt' ? $this->table->decryptData( $value ) : str_replace('$', '\$', $value);
					//or str_replace('$', '&#36;', $value)
				}
				$output .= '</textarea>';
					
				break;

			case 'text':
			case 'number':
			case 'username':
			case 'password':
			case 'email': //! text, number, username, password, email
				$output .= '
			<input class="form-control'.
			(isset($field_data['align']) && in_array($field_data['align'], $this->valid_alignments) ? ' text-'.$field_data['align'] : '' ).
			'" name="'.$name.'" id="'.$name.'" type="'.$field_data['format'].'"  
				placeholder="'.( isset($field_data['placeholder']) ? $field_data['placeholder'] : $field_data['label']).'" ';
				//! Number of decimal places, example use 'decimal' => '2'
				if( isset($field_data['format']) && $field_data['format'] == 'number'){
					if( isset($field_data['decimal']))
						switch($field_data['decimal']) {
							case '0': $output .= 'step="1" '; break;	// whole number
							case '1': $output .= 'step="0.1" '; break;
							case '2': $output .= 'step="0.01" '; break;
							case '3': $output .= 'step="0.001" '; break;
							case '4': $output .= 'step="0.0001" '; break;
							default: $output .= 'step="0.01" ';
						}
					else
						$output .= 'step="0.01" ';	// default if not specified
				}
					
				if( isset($field_data['length']) )
					$output .= 'maxlength="'.$field_data['length'].'" ';
				else {
					$length = $this->table ? $this->table->get_max_length( $name ) : 0;
					if( $length )
						$output .= 'maxlength="'.$length.'" ';
				}

				if( $value !== false && $field_data['format'] <> 'password') {
					$value = htmlspecialchars($value);
					$output .= 'value="'.
					(isset($field_data['process']) && $field_data['process'] == 'decrypt' ? $this->table->decryptData( $value ) : $value).'" ';
					}
					
				$output .= (isset($field_data['extras']) ? $field_data['extras'] : '').'>';
				break;
				
			case 'htmltext': //! htmltext
				$output .= '
			<input class="form-control'.
			(isset($field_data['align']) && in_array($field_data['align'], $this->valid_alignments) ? ' text-'.$field_data['align'] : '' ).
			'" name="'.$name.'" id="'.$name.'" type="text"  
				placeholder="'.( isset($field_data['placeholder']) ? $field_data['placeholder'] : $field_data['label']).'" ';

				if( isset($field_data['length']) )
					$output .= 'maxlength="'.$field_data['length'].'" ';
				else {
					$length = $this->table ? $this->table->get_max_length( $name ) : 0;
					if( $length )
						$output .= 'maxlength="'.$length.'" ';
				}

				if( $value !== false && $field_data['format'] <> 'password') {
					$output .= 'value="'.
					htmlentities(isset($field_data['process']) && $field_data['process'] == 'decrypt' ? $this->table->decryptData( $value ) : $value).'" ';
					}
					
				$output .= (isset($field_data['extras']) ? $field_data['extras'] : '').'>';
				break;

			case 'numberc':	//! numberc
				$output .= '
			<input class="form-control'.
			(isset($field_data['align']) && in_array($field_data['align'], $this->valid_alignments) ? ' text-'.$field_data['align'] : '' ).
			'" name="'.$name.'" id="'.$name.'" type="'.$field_data['format'].'"  
				placeholder="'.( isset($field_data['placeholder']) ? $field_data['placeholder'] : $field_data['label']).'" ';

				if( $this->debug ) {
					echo "<p>render_field/numberc $name type = ".$field_data['format']." value = ".($value === false ? 'false ' : (is_null($value) ? 'NULL' : $value) )."</p>";
					echo "<pre>";
					var_dump($value);
					echo "</pre>";
				}

				if( $value !== false ) {
					$output .= 'value="'.number_format((float) $value) .'" ';
					}
					
				$output .= (isset($field_data['extras']) ? $field_data['extras'] : '').'>';
				break;

			case 'month': //! month
				$output .= '
			<input class="form-control monthpicker'.
			(isset($field_data['align']) && in_array($field_data['align'], $this->valid_alignments) ? ' text-'.$field_data['align'] : '' ).
			'" name="'.$name.'" id="'.$name.'" type="text"  
				placeholder="'.( isset($field_data['placeholder']) ? $field_data['placeholder'] : $field_data['label']).'" ';

				$output .= 'value="'.$value.'" ';
					
				$output .= (isset($field_data['extras']) ? $field_data['extras'] : '').'>';
				break;
			
			
			case 'date': //! date
				if( $this->debug ) echo "<p>".__METHOD__.": (date) ios ".$_SESSION['ios']." chrome ".$_SESSION['chrome']."</p>";
				$date_format = $_SESSION['ios'] == 'true' ? "Y-m-d" : "m/d/Y";
				$output .= '
			<input class="form-control'.($_SESSION['ios'] != 'true' ? ' date' : '').
			(isset($field_data['align']) && in_array($field_data['align'], $this->valid_alignments) ? ' text-'.$field_data['align'] : '' ).
			'" name="'.$name.'" id="'.$name.'" type="'.($_SESSION['ios'] == 'true' &&
			!(isset($this->form['readonly']) && $this->form['readonly']) ? $field_data['format'] : 'text').'"  
				placeholder="'.( isset($field_data['placeholder']) ? $field_data['placeholder'] : $field_data['label']).'" ';

				if( $value ) {
					if( $value == "0000-00-00" )
						$value = "";
					else
						$value = date($date_format, strtotime($value));
					$output .= 'value="'.$value.'" ';
				}
					
				$output .= (isset($field_data['extras']) ? $field_data['extras'] : '').'>';
				break;

			case 'timestamp':
			case 'datetime': //! datetime, timestamp
				$date_format = $_SESSION['ios'] == 'true' ? "Y-m-d\TH:i" : "m/d/Y H:i";
				if( $_SESSION['ios'] == 'true' &&
					$this->table->is_nullable($name) &&
					!(isset($this->form['readonly']) && $this->form['readonly']) &&
					!(isset($field_data['extras']) && $field_data['extras'] == 'readonly'))
					$output .= '<div class="form-group form-inline">';
				$output .= '<input class="form-control'.($_SESSION['ios'] != 'true' ? ' timestamp' : '').
			(isset($field_data['align']) && in_array($field_data['align'], $this->valid_alignments) ? ' text-'.$field_data['align'] : '' ).
			'" name="'.$name.'" id="'.$name.'" type="'.($_SESSION['ios'] == 'true' &&
			!(isset($this->form['readonly']) && $this->form['readonly']) ? 'datetime-local' : 'text').'"  placeholder="'.( isset($field_data['placeholder']) ? $field_data['placeholder'] : $field_data['label']).'" ';
				if( isset($field_data['length']) )
					$output .= 'maxlength="'.$field_data['length'].'" ';
				else {
					$length = $this->table->get_max_length( $name );
					if( $length )
						$output .= 'maxlength="'.$length.'" ';
				}

				if( $value ) {
					if( $value == "0000-00-00 00:00:00" )
						$value = "";
					else
						$value = date($date_format, strtotime($value));
					$output .= 'value="'.$value.'" ';
				}
					
				$output .= (isset($field_data['extras']) ? $field_data['extras'] : '').'>';
				if( $_SESSION['ios'] == 'true' &&
					$this->table->is_nullable($name) &&
					!(isset($this->form['readonly']) && $this->form['readonly']) &&
					!(isset($field_data['extras']) && $field_data['extras'] == 'readonly') )
					$output .= '<a class="btn btn-md btn-danger clearbutton" onclick="document.getElementById(\''.$name.'\').value=\'\'">x</a></div>';
				break;

			case 'time': //! time
				$date_format = $_SESSION['ios'] == 'true' ? "H:i" : "H:i";
				$output .= '
			<input class="form-control" name="'.$name.'" id="'.$name.'" type="'.$field_data['format'].'"  
				placeholder="'.( isset($field_data['placeholder']) ? $field_data['placeholder'] : $field_data['label']).'" ';
				if( isset($field_data['length']) )
					$output .= 'maxlength="'.$field_data['length'].'" ';
				else {
					$length = $this->table->get_max_length( $name );
					if( $length )
						$output .= 'maxlength="'.$length.'" ';
				}

				if( $value ) {
					if( $value == "00:00:00" )
						$value = "";
					else
						$value = date($date_format, strtotime($value));
					$output .= 'value="'.$value.'" ';
				}
					
				$output .= (isset($field_data['extras']) ? $field_data['extras'] : '').'>';
				break;

			case 'miltime': //! miltime
				$output .= '
			<input class="form-control text-right'.
			'" name="'.$name.'" id="'.$name.'" type="text" length="4" maxlength="4" size="4"
			placeholder="'.( isset($field_data['placeholder']) ? $field_data['placeholder'] : $field_data['label']).'" ';
				// !WIP - unsure about this.
				if( $value !== false )
					$output .= 'value="'.$value.'" ';
					
				$output .= (isset($field_data['extras']) ? $field_data['extras'] : '').'>';
				break;
			
			case 'groups1': //! groups1
				foreach( $sts_opt_groups as $grp ) {
					$output .= '
			<label class="checkbox-inline">
			<input type="checkbox" value="'.$grp.'" name="'.$name.'_'.$grp.'"';
						if( $value && in_array($grp, explode(',', $value)) )
							$output .= " checked ";
						$output .= ' />
				'.$grp.'</label>';
				}
				break;

			case 'endorsements': //! endorsements
				$output .= '<fieldset id="'.$name.'">';
				foreach( $sts_license_endorsements as $grp => $grp_label ) {
					$output .= '
			<label class="checkbox">
			<input type="checkbox" value="'.$grp.'" name="'.$name.'_'.$grp.'"';
						if( $value && in_array($grp, explode(',', $value)) )
							$output .= " checked ";
						$output .= ' />
				'.$grp_label.'</label>';
				}
				$output .= '</fieldset>';
				break;

			case 'groups': //! groups
				foreach( $sts_opt_groups as $grp ) {
					$output .= '
			<label class="col-sm-6">
			<input type="checkbox" class="my-switch" value="'.$grp.'" name="'.$name.'_'.$grp.'"';
						if( $grp == EXT_GROUP_USER )
							$output .= " checked disabled";
						else if( $value && in_array($grp, explode(',', $value)) )
							$output .= " checked ";
						$output .= '> '.$grp.'
						</label>
				';
				}
				break;

			case 'groups2': //! groups2
				$output .= '
		<div class="btn-group" data-toggle="buttons">';

				foreach( $sts_opt_groups as $grp ) {
					$output .= '
			<label class="btn btn-flags">
			<input type="checkbox" value="'.$grp.'" name="'.$name.'_'.$grp.'"';
						if( $value && in_array($grp, explode(',', $value)) )
							$output .= " checked ";
						$output .= ' />
				'.$grp.'</label>';
				}

				$output .= '
				</div>';
				break;
				
			default:
				break;

		}
		
		$formats_to_wrap = array('enum', 'state', 'bool', 'image', 'text', 'username', 'password', 
				'email', 'date', 'groups', 'groups1', 'groups2' );

		if( ! isset($this->form['layout']) &&	// If no template, wrap fields
			in_array( $field_data['format'], $formats_to_wrap ) ) {
			$output = '
	<div class="form-group">
		<label for="'.$name.'" class="col-sm-2 control-label">'.$field_data['label'].'</label>
		<div class="col-sm-6">' . $output . '
				</div>
	</div>';
;
		}

		if( $this->debug ) echo "<p>$name field = <pre>".htmlspecialchars($output)."</pre></p>";
		return $output;
	}
	
	//! Add custom buttons to a form
	private function add_buttons( $buttons, $use_menu ) {
		$output = '';
		
		if( $use_menu )
			$output .= '<div class="btn-group">
  <button type="button" class="dropdown-toggle btn-md" data-toggle="dropdown">
    '.$use_menu.' <span class="caret"></span>
  </button>
  <ul class="dropdown-menu" role="menu">
  ';
		
		foreach( $buttons as $button ) {
			$button_restricted = false;
			if( isset($button['restrict']) ) {
				$button_restricted = ! in_group( $button['restrict'] );
			}
			
			if( ! $button_restricted ) {
				if( $use_menu )
					$output .= '<li><a ';
				else
					$output .= '<'.(isset($button['disabled']) ? 'button' : 'a').' class="btn btn-md btn-'.(isset($button['button']) ? $button['button'] : 'success').' tip'.(isset($button['disabled']) ? ' disabled' : '').'" ';
				
				// Provide an id tag - for automated testing
				$output .= 'id="'.str_replace(' ', '_', $button['label']).'"';
				
				if( isset($button['blank']) && $button['blank'] == true )
					$output .= ' target="_blank" ';
				if( ! isset($button['disabled']) ) {
					if( isset($button['confirm']) && $button['confirm'] == true )
						$output .= 'onclick="confirmation(\'Confirm: '.$button['label'].
						(isset($button['tip']) ? '\n\n'.$button['tip'] : '').'\',
							\''.$button['link'].'\')" '.
						(isset($button['tip']) ? ' title="'.strip_tags($button['tip']).'"' : '');
					else if( isset($button['onclick']) )
						$output .= ' onclick="'.$button['onclick'].'"'.
						(isset($button['tip']) ? ' title="'.$button['tip'].'"' : '');
					else if( isset($button['modal']) ) //! SCR# 413 - allow modal links
						$output .= ' data-toggle="modal" data-target="#'.$button['modal'].'"'.
						(isset($button['tip']) ? ' title="'.$button['tip'].'"' : '');
					else
						$output .= ' href="'.$button['link'].'"'.
						(isset($button['tip']) ? ' title="'.$button['tip'].'"' : '');
				} else
					$output .= (isset($button['tip']) ? ' title="'.$button['tip'].'"' : '');
				
				$output .= (isset($button['disabled']) ? ' disabled' : '').
					'>'.$button['icon'].' '.$button['label'].'</'.(isset($button['disabled']) ? 'button' : 'a').'>'.($use_menu ? '</li>' : '').' ';
			}
		}
		if( $use_menu )
			$output .= '</ul>
			</div> ';
		
		return $output;
	}

	// create a form for the data, possibly using a template.
	// If values are provided, the fields are initialized to the values
	public function render( $values = false ) {

		if( $this->debug ) {
			echo "<p>render form value = </p>
			<pre>";
			var_dump($values);
			echo "</pre>";
		}
		
		$output = '';
		if( $this->count_fields > 0 ) {
			$this->values = isset($values) ? $values : false;
			$template = isset($this->form['layout']) ? $this->form['layout'] : false;
			if( $this->debug ) echo "<p>template = ".($template ? '<pre>'.htmlspecialchars($template).'</pre>' : 'false')."</p>";
			
			if( isset($this->values) && is_array($this->values) ) {
				foreach( $this->values as $name => $value) {
					if( ! empty($this->form['title']) && ! empty($value) )
						$this->form['title'] = preg_replace('/\%'.$name.'\%/', $value, $this->form['title'], 1);
					if( ! empty($this->form['cancel']) && ! empty($value) )
						$this->form['cancel'] = preg_replace('/\%'.$name.'\%/', $value, $this->form['cancel'], 1);
					if( ! empty($this->form['back']) && ! empty($value) )
						$this->form['back'] = preg_replace('/\%'.$name.'\%/', $value, $this->form['back'], 1);
					if( ! empty($this->form['forwards']) && ! empty($value) )
						$this->form['forwards'] = preg_replace('/\%'.$name.'\%/', $value, $this->form['forwards'], 1);
					if( isset($this->form['buttons']) && is_array($this->form['buttons']) ) {
						$count_buttons = count($this->form['buttons']);
						for( $c=0; $c < $count_buttons; $c++ ) {
							if( isset($this->form['buttons'][$c]['link']) )
								$this->form['buttons'][$c]['link'] = preg_replace('/\%'.$name.'\%/', $value, $this->form['buttons'][$c]['link'], 1);
							$this->form['buttons'][$c]['label'] = preg_replace('/\%'.$name.'\%/', $value, $this->form['buttons'][$c]['label'], 1);
						}
					}
				}
			}
			
			$buttons = '<h2 class="tighter-top">'.(isset($this->form['title']) ? $this->form['title'] : '').'
			';
			if( isset($this->form['okbutton'] ) )
				$buttons .= '<button class="btn btn-sm btn-success" name="save" type="submit" '.
			(isset($this->form['actionextras']) ? $this->form['actionextras'] : '')
			.'><span class="glyphicon glyphicon-ok"></span> '.(isset($this->form['okbutton']) ? $this->form['okbutton'] : 'Submit').'</button> ';
			
			if( isset($this->form['saveadd']) && $this->form['saveadd'] ) {
				$buttons .= '<button class="btn btn-sm btn-success" id="saveadd" name="saveadd" type="submit" '.
			(isset($this->form['actionextras']) ? $this->form['actionextras'] : '')
			.'><span class="glyphicon glyphicon-ok"></span> '.(isset($this->form['saveadd']) ? $this->form['saveadd'] : 'Save & Add').'</button> ';

			}

			//! NEW BUTTONS
			if( isset($this->form['buttons']) && is_array($this->form['buttons'])
				&& count($this->form['buttons']) > 0 ) {
				$buttons .= $this->add_buttons( $this->form['buttons'],
					isset($this->form['buttons_menu']) ? $this->form['buttons_menu'] : false );
			}

			if( isset($this->form['popup']) && $this->form['popup'] ) {
				$buttons .= '<a class="btn btn-md btn-default" data-dismiss="modal"><span class="glyphicon glyphicon-remove"></span> '.(isset($this->form['cancelbutton']) ? $this->form['cancelbutton'] : 'Cancel').'</a>
			';
			} else if( isset($this->form['cancel']) ) {
				$buttons .= '<a class="btn btn-md btn-default" id="'.$this->form['name'].'_cancel" href="'.(isset($this->form['cancel']) ? $this->form['cancel'] : 'index.php').'"><span class="glyphicon glyphicon-remove"></span> '.(isset($this->form['cancelbutton']) ? $this->form['cancelbutton'] : 'Cancel').'</a>
			';
			}

			if( isset($this->form['forwards']) && $this->form['forwards'] ) {
				$buttons .= '<a class="btn btn-md btn-success" id="'.$this->form['name'].'_forwards" href="'.(isset($this->form['forwards']) ? $this->form['forwards'] : 'index.php').'"><span class="glyphicon glyphicon-arrow-right"></span> '.(isset($this->form['forwardsbutton']) ? $this->form['forwardsbutton'] : 'Back').'</a> ';

			}

			if( isset($this->form['back']) && $this->form['back'] ) {
				$buttons .= '<a class="btn btn-md btn-default" id="'.$this->form['name'].'_back" href="'.(isset($this->form['back']) ? $this->form['back'] : 'index.php').'"><span class="glyphicon glyphicon-arrow-right"></span> '.(isset($this->form['backbutton']) ? $this->form['backbutton'] : 'Back').'</a> ';

			}

			$buttons .= '
	</h2>';

			$output .= '<form role="form" class="form-horizontal" action="'.$this->form['action'].'" 
				method="post" enctype="multipart/form-data" 
				name="'.$this->form['name'].'" id="'.$this->form['name'].'"'.
				(isset($this->form['noautocomplete']) && $this->form['noautocomplete'] ? ' autocomplete="off"' : '').
				(isset($this->form['novalidate']) && $this->form['novalidate'] ? ' novalidate' : '').'>';
			
			//! CSRF token - extra security
			// See http://en.wikipedia.org/wiki/Cross-site_request_forgery
			$output .= '
	<input name="CSRF" type="hidden" value="'.str_rot13(session_id()).'">
	';
			$output .= $buttons;
			
			if( $this->debug ) {
				$output .= '
	<input name="debug" type="hidden" value="on">';
			}
			
	//echo "<p>XXX</p><pre>";
	//var_dump($this->fields, $this->values);
	//echo "</pre>";
			//! SCR# 354 - check for 'rw' attribute = keep read/write
			//! SCR# 582 - Add stars for required fields
			$contains_required_fields = false;
			$required_star = '<span class="glyphicon glyphicon-star"></span>';
			foreach( $this->fields as $name => $field_data) {
				if( isset($this->form['readonly']) && $this->form['readonly'] &&
					! (isset($field_data['rw']) && $field_data['rw']))
					$field_data['extras'] = isset($field_data['extras']) ? $field_data['extras'].' readonly' : 'readonly';
				//! Note:
				// Use array_key_exists($name, $this->values) to check if a value exists in the array
				// It could be null, and that is valid. 
				// isset($this->values[$name]) does not work for null values
				
				$rendered_field = $this->render_field( $name, $field_data, 
					isset($this->values) && is_array($this->values) && array_key_exists($name, $this->values) ? $this->values[$name] : false );
				if( $template ) {
					//! SCR# 635 - allow multiple replace
					$template = preg_replace('/\%'.$name.'\%/', $rendered_field, $template);
					//! SCR# 582 - Add stars for required fields
					if( isset($field_data['label']) ) {
						$field_label = $field_data['label'];
						if( isset($field_data['extras']) && strpos($field_data['extras'], 'required') !== false ) {
							$field_label = '<span class="text-danger tip" title="The '.$field_label.' field is required.">'.$field_label.$required_star.'</span>';
							$contains_required_fields = true;
						}
						$template = preg_replace('/\#'.$name.'\#/', $field_label, $template, 1);
					}
				} else
					$output .= $rendered_field;
			}
			if( $template )
				$output .= $template;
			
			//! SCR# 582 - Add stars for required fields
			if( $contains_required_fields )
				$output .= '<div class="form-group tighter">
				<div class="col-sm-4 col-sm-offset-8">
				<p class="text-right text-danger small tip" title="There are required fields on this form. You need to fill in the required fields as a minimum before you can click on '.(isset($this->form['okbutton'] ) ? $this->form['okbutton'] : 'Save').'. The required fields have stars to show they are required.">'.$required_star.' = Requred field</p>
				</div>
				</div>';
				
			$output .= '
</form>
';
			//$output .= $buttons;
			$rnd_string = ''; //date('is');
			//! SCR# 368 - don't include type=hidden
			if( ! isset($this->form['readonly']) && ! $this->noconfirm ) {
				$output .= '
	<script language="JavaScript" type="text/javascript"><!--
					
		$(document).ready( function () {
			
			window.'.$this->form['name'].$rnd_string.'_HAS_CHANGED = false;
			
			$("form#'.$this->form['name'].' :input").not(":input[type=button], :input[type=hidden], :input[type=submit]").change(function(e) {
				//console.log(e);
				window.'.$this->form['name'].$rnd_string.'_HAS_CHANGED = true;
			});
			';
			
				if( $this->autosave ) {
					$output .= '
			$(\'a\').not(".clearbutton").on(\'click.'.$this->form['name'].'\', function( event ) {
				//console.log(\'click.'.$this->form['name'].'\', event);
				//console.log( \''.$this->form['name'].$rnd_string.'_HAS_CHANGED\', window.'.$this->form['name'].$rnd_string.'_HAS_CHANGED );
				if( window.'.$this->form['name'].$rnd_string.'_HAS_CHANGED ) {
					//console.log( \'submit\');
					$( "#'.$this->form['name'].'" ).submit();
				}
			});
			
			';
				} else {
					$output .= '
			$(\'a\').not(".clearbutton").on(\'click.'.$this->form['name'].'\', function( event ) {
				//console.log(\'click.'.$this->form['name'].'\', event);
				var to = typeof event.target.href;
				//console.log(event.target.href, to);
				if( window.'.$this->form['name'].$rnd_string.'_HAS_CHANGED ) {
					var goto_link;
					if(to === \'undefined\'){
						goto_link = \''.( isset($this->form) && isset($this->form['back']) ? $this->form['back'] : 'index.php').'\';
					} else {
						goto_link = event.target.href;
					}
					event.preventDefault();  //prevent form from submitting
					confirmation(\'<h2>'.(isset($this->form['title']) ? $this->form['title'] : '').'</h2><p>You have unsaved changes that will be lost. Continue?</p>\', goto_link);
				}
			});

			';
				}
				$output .= '
		});
	//--></script>


';
			}
		}

		if( $this->debug ) echo "<p>output = ".($output ? '<pre>'.htmlspecialchars($output).'</pre>' : 'false')."</p>";
		return $output;
	}

	public function process_field( $name, &$post ) {
		global $sts_opt_groups, $_FILES, $sts_license_endorsements,
			$validDate, $validMiltime, $validTimestamp;

		if( $this->debug ) echo "<p>process_field name = $name ".$this->fields[$name]['format']."</p>
";
		$this->error = "";
		
		if( isset($this->fields[$name]) && isset($this->fields[$name]['format']) ) {
			switch( $this->fields[$name]['format'] ) {
				case 'date':	//! date
					$value = isset($post[$name]) && $post[$name] <> "" &&
					($_SESSION['ios'] == 'true' || $validDate($post[$name])) ? date("Y-m-d", strtotime($post[$name])) : ($this->table->is_nullable($name) ? "NULL" : "");
					break;
					
				case 'miltime':	//! miltime
					$value = isset($post[$name]) && $post[$name] <> "" &&
					($_SESSION['ios'] == 'true' || $validMiltime($post[$name])) ? date("Hi", strtotime($post[$name])) : ($this->table->is_nullable($name) ? "NULL" : "");
					break;
					
				case 'timestamp':	//! timestamp, datetime
				case 'datetime':
					$value = isset($post[$name]) && $post[$name] <> "" &&
					($_SESSION['ios'] == 'true' || $validTimestamp($post[$name])) ? date("Y-m-d H:i:s", strtotime($post[$name])) : ($this->table->is_nullable($name) ? "NULL" : "");
					break;
					
				case 'bool':	//! bool, bool2
				case 'bool2':
					$value = isset($post[$name]) ? "1" : "0";
					if( $this->debug ) echo "<p>process_field in bool value = $value</p>";
					break;
					
				case 'image':	//! image
					if( $this->debug ) {
						echo "<p>process_field FILES = </p>
						<pre>";
						var_dump($_FILES);
						echo "</pre>";
					}
					$value = "";
					if( isset($_FILES[$name]) && isset($_FILES[$name]['name']) && $_FILES[$name]['name'] <> '' ) {
						if( $_FILES[$name]['error'] != 0 ) { // upload error
							$this->error .= $this->file_errors[$_FILES[$name]['error']];
						} else {
							if( ! is_uploaded_file($_FILES[$name]['tmp_name']) ) {
								$this->error .= "not an HTTP upload";
							} else {
								$size = getimagesize($_FILES[$name]['tmp_name']);
								if( ! $size ) {
									$this->error .= "only image uploads are allowed";
								} else {
									$image = $this->scaleImageFileToBlob($_FILES[$name]['tmp_name']);
									$ext = pathinfo($_FILES[$name]['name'], PATHINFO_EXTENSION);
									//$contents = file_get_contents($_FILES[$name]['tmp_name']);
				  					$base64   = base64_encode($image);
				  					//$value = "data:image/".$ext.";base64,".$base64;
				  					$value = "data:image/jpg;base64,".$base64;
				  					if( $this->debug ) {
				  						//echo "<p>process_field length contents = ".strlen($contents)."</p>";
				  						echo "<p>process_field length base64 = ".strlen($base64)."</p>";
				  						echo "<p>process_field value (".strlen($value).") = ".substr($value, 0, 30)." ... ".substr($value, -10)."</p>".
				  						'<img src="'.($value ? $value : 'images/no-image.jpg').'" width="200" height="200" class="img-responsive img-thumbnail">';
				  					}
								}
							}
						}
					}
					
					if( $this->debug ) echo "<p>process_field in file value (".strlen($value).") = ".substr($value, 0, 30)." ... ".substr($value, -10)." error = $this->error</p>
";
					break;
					
				case 'attachment':	//! attachment
					$attachment_table = sts_attachment::getInstance($this->table->database, $this->debug);

					$value = $attachment_table->store_attachment( $name, $post );
					
					// If an error, get the error message
					if( ! isset($value) || empty($value) || ! empty($attachment_table->error) )
						$this->error = $attachment_table->error;
					
					if( $this->debug ) echo "<p>".__METHOD__."/attachment: value = $value</p>";
					break;
					
				case 'groups':	//! groups
					$values = array(EXT_GROUP_USER);
					foreach( $sts_opt_groups as $grp ) {
						if( isset($post[$name.'_'.$grp]) && $post[$name.'_'.$grp] == $grp )
							$values[] = $grp;
					}
					$value = implode(',', $values);
					break;
				
				case 'states':	//! states - for IFTA
					$values = array();
					foreach( $this->states as $abbrev => $full ) {
						if( isset($post[$name.'_'.$abbrev]) && $post[$name.'_'.$abbrev] == $abbrev )
							$values[] = $abbrev;
					}
					$value = implode(',', $values);
					break;
				
				case 'mtable':	//! mtable
					if( $this->debug ) {
						echo "<p>process_field post[$name] = </p>
						<pre>";
						var_dump($post[$name]);
						echo "</pre>";
					}
					$value = implode(',', $post[$name]);					
					break;
				
				case 'endorsements':	//! endorsements
					$values = array();
					foreach( $sts_license_endorsements as $grp => $grp_label ) {
						if( isset($post[$name.'_'.$grp]) && isset($post[$name.'_'.$grp]) == $grp )
							$values[] = $grp;
					}
					$value = implode(',', $values);
					break;
					
				case 'number':	//! number
					//! SCR# 483 - deal with missing fields
					if( ! isset($post[$name]) || $post[$name] == '' ) {
						if( $this->table->is_nullable($name) )
							$value = 'NULL';
						else
							$value = '0';
					} else
						$value = str_replace( ',', '', $post[$name] );
					break;
				
				case 'numberc':	//! numberc
					//! SCR# 483 - deal with missing fields
					if( ! isset($post[$name]) || $post[$name] == '' ) {
						if( $this->table->is_nullable($name) )
							$value = 'NULL';
						else
							$value = '0';
					} else
						$value = str_replace( ',', '', $post[$name] );
					break;
				
				case 'hidden-req': //! hidden-req
				case 'textarea': //! textarea
					if( ! isset($post[$name]) || $post[$name] == '' ) {
						if( $this->table->is_nullable($name) )
							$value = 'NULL';
						else
							$value = '';
					} else
						$value = $post[$name];
					break;

				case 'text':	//! text
				default:
					$value = isset($post[$name]) ?
						$this->table->trim_to_fit( $name, $post[$name] ) : '';
			}
		} else
			$value = isset($post[$name]) ? $post[$name] : '';
			
		if( $this->debug ) {
			if( isset($base64) )
				echo "<p>process_field value (".strlen($value).") = ".substr($value, 0, 30)." ... ".substr($value, -10)."</p>";
			else
				echo "<p>process_field value = $value</p>
";
		}
		return $value;
	}

	public function process_add_form() {
		global $_POST;

		$fields = array_keys( $this->fields );
	
		if( $this->debug ) {
			echo "<p>process_add_form: fields, POST, FILES = </p>
			<pre>";
			var_dump($fields, $_POST, $_FILES);
			echo "</pre>";
		}
		
		//! CSRF Token check
		if( isset($_POST) && isset($_POST["CSRF"]) && $_POST["CSRF"] == str_rot13(session_id())) {
			$value = array();
			foreach( $fields as $field ) {
				$processed_field = $this->process_field( $field, $_POST );
				if( $processed_field <> "" ) 
					$value[$field] = $processed_field;
				if( ! empty($this->error)) break;
			}
			
			if( $this->debug ) {
				echo "<p>process_add_form value = </p>
				<pre>";
				var_dump($value);
				echo "</pre>";
			}
		
			if( empty($this->error)) {
				$result = $this->table->add( $value );
				if( $this->debug ) echo "<p>result = ".($result ? 'true' : 'false '.$this->table->error())."</p>";
			} else {
				echo "<p>Error: ".$this->error."</p>";
				$result = false;
			}
		} else {
			if( $this->debug ) echo "<p>CSRF Check failed! - possible Cross-site request forgery</p>";
			$result = false;
		}
		return $result;
	}

	public function process_edit_form() {
		global $_POST, $_FILES;

		$result = false;
		if( $this->debug ) {
			echo "<p>".__METHOD__.": POST, FILES = </p>
			<pre>";
			var_dump($_POST, $_FILES);
			echo "</pre>";
		}
		//! CSRF Token check
		if( isset($_POST) && isset($_POST["CSRF"]) && $_POST["CSRF"] == str_rot13(session_id())) {
			$fields = array_keys( $this->fields );
			//$primary_key = array_shift( $fields );	// for edit
			$primary_key = $this->table->primary_key;
			if( $this->debug ) {
				echo "<p>".__METHOD__.": primary_key, fields = </p>
				<pre>";
				var_dump($primary_key, $fields);
				echo "</pre>";
			}
		
			$value = array();
			$changed = array();
			$previous = $this->table->fetch_rows($primary_key." = ".$_POST[$primary_key]);
			
			$_GET['CODE'] = $_POST[$primary_key];
			if( is_array($previous) ) {
				foreach( $fields as $field ) {
					if( $this->debug ) echo "<p>".__METHOD__.": $field previous=".(array_key_exists($field, $previous[0]) ? 'exists' : 'does not exist')."</p>";//!xxx
					if( (isset($_POST[$field]) && array_key_exists($field, $previous[0])) ||
						(isset($_FILES[$field]) && $_FILES[$field]['name'] <> '' ) || 
						in_array($this->fields[$field]['format'], array('groups', 'bool', 'bool2', 'states') ) ) {
						
						if( isset($_POST[$field]) )
							$_POST[$field] = str_replace("\\\'", "'", $_POST[$field] );
						$processed_field = $this->process_field( $field, $_POST );
						$value[$field] = $processed_field;
						
						if( $this->debug && $field == 'USER_GROUPS') {
							echo "<pre>";
							var_dump($field);
							var_dump($processed_field);
							var_dump($this->table->column_type($field));
							var_dump($this->fields[$field]['format']);
							var_dump(isset($previous[0][$field]));
							var_dump(is_null($previous[0][$field]));
							var_dump($previous[0][$field]);
							echo "</pre>";
						}
						
						$readonly = $this->fields[$field]['format'] != 'table' &&
							! empty($this->fields[$field]['extras']) &&
							strpos($this->fields[$field]['extras'], 'readonly') !== false;

						if( ! in_array($this->table->column_type($field),
							['varchar', 'hidden-req', 'textarea']) &&
							//isset($previous[0][$field]) &&
							($readonly || is_null($previous[0][$field])) )
							$previous[0][$field] = 'NULL';

						//! readonly columns an issue, see BILLTO_* in shipment screen

						if( $this->debug && $field == 'CDN_TAX_EXEMPT') {
							echo "<pre>".__METHOD__.": xxx\n";
							var_dump($field);
							var_dump(array_key_exists($field, $previous[0]));
							var_dump(isset($_POST[$field]));
							var_dump($this->fields[$field]['format']);
							var_dump($this->table->column_type($field) == 'varchar');
							var_dump(strcmp( $processed_field, $previous[0][$field] ));
							var_dump($processed_field);
							var_dump($previous[0][$field]);
							echo "</pre>";
						}
						
						// check field exists in previous array, or it is a groups field
						if( array_key_exists($field, $previous[0]) && 
							(isset($_POST[$field]) ||
								in_array($this->fields[$field]['format'], ['groups', 'bool', 'bool2'])) &&
							
							// special case for empty password
							! ($this->fields[$field]['format'] == 'password' && $processed_field == '') &&
							// use strcmp for varchar comparison or <> otherwise
							( ($this->table->column_type($field) == 'varchar' &&
							! empty($previous[0][$field]) &&
							strcmp( $processed_field, $previous[0][$field] ) !==0) ||
							($processed_field <> $previous[0][$field]) ) ||
							(isset($this->fields[$field]['format']) &&
							$this->fields[$field]['format'] == 'image'))
							$changed[$field] = $processed_field;
					}
				}
				if( $this->debug ) {
					echo "<p>".__METHOD__.": changed = </p>
					<pre>";
					var_dump($changed);
					echo "</pre>";
				}
				$this->changes_str = '';
				if( is_array($changed) && count($changed) > 0 ) {
					$ch = [];
					foreach( $changed as $field => $value ) {
						if( isset($this->fields) &&
							is_array($this->fields) &&
							is_array($this->fields[$field]) &&
							isset($this->fields[$field]['label']) )
							$label = isset($this->fields[$field]['label2']) ? $this->fields[$field]['label2'] : $this->fields[$field]['label'];
						else
							$label = $field;
						
						// Trim long fields
						if( strlen($value) > $this->max_field_length )
							$value = substr($value,0,$this->max_field_length);
						
						$ch[] = $label.'='.$value;
					}
					$this->changes_str = implode(', ', $ch);
				}
				if( $this->debug ) {
					echo "<p>".__METHOD__.": changed, changes_str = </p>
					<pre>";
					var_dump($changed, $this->changes_str );
					echo "</pre>";
				}
			
				if( count($changed) > 0 ) {
					$result = $this->table->update( $_POST[$primary_key], $changed );
					if( $this->table->table_name == USER_TABLE ) {
						require_once( "include/sts_session_class.php" );
						$my_session = sts_session::getInstance( $this->debug );
						$my_session->update( $_POST[$primary_key], $changed );
					}
				} else {
					if( $this->debug ) echo "<p>".__METHOD__.": no changes</p>";
					$result = true;
				}
			}
	
			if( $this->debug ) echo "<p>".__METHOD__.": result = ".($result ? 'true' : 'false '.$this->table->error())."</p>";
		} else {
			if( $this->debug ) echo "<p>".__METHOD__.": CSRF Check failed! - possible Cross-site request forgery</p>";
			$result = false;
		}

		return $result;
	}

}

?>

