<?php

// $Id: sts_office_class.php 5030 2023-04-12 20:31:34Z duncan $
// Office class, all activity related to offices.

// no direct access
defined('_STS_INCLUDE') or die('Restricted access');

require_once( "sts_table_class.php" );
require_once( "sts_setting_class.php" );

class sts_office extends sts_table {

	private $setting;
	private $multi_company;
	
	// Constructor does not need the table name
	public function __construct( $database, $debug = false ) {

		$this->debug = $debug;
		$this->primary_key = "OFFICE_CODE";
		if( $this->debug ) echo "<p>Create sts_office</p>";
		parent::__construct( $database, OFFICE_TABLE, $debug);
		$this->setting = sts_setting::getInstance( $this->database, $this->debug );
		$this->multi_company = ($this->setting->get( 'option', 'MULTI_COMPANY' ) == 'true');
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
    
    public function multi_company() {
	    return $this->multi_company;
    }

	//! Check if we can delete an office.
	// Only possible if office has not been used yet.
	public function can_delete( $code ) {
		$result = false;
		$check = $this->database->get_one_row(
			"SELECT (SELECT COUNT(*)
			FROM EXP_USER_OFFICE
			WHERE OFFICE_CODE = $code) +
			(SELECT COUNT(*)
			FROM EXP_SHIPMENT
			WHERE OFFICE_CODE = $code) +
			(SELECT COUNT(*)
			FROM EXP_LOAD
			WHERE OFFICE_CODE = $code) +
			(SELECT COUNT(*)
			FROM EXP_DRIVER
			WHERE OFFICE_CODE = $code) INUSE" );
		if( is_array($check) && isset($check["INUSE"]))
			$result = intval($check["INUSE"]) == 0;
		return $result;	
	}
	
	public function delete( $code, $type = "" ) {
		if( $this->can_delete( $code ) )
			parent::delete( $code, $type );
	}
	
	//! Create checkboxes for companies
	public function user_checkboxes( $form, $user_code = false ) {
		if( $this->debug ) echo "<p>".__METHOD__.": user_code = $user_code</p>";
		if( $this->multi_company) {
			$offices = $this->fetch_rows("", "OFFICE_NAME, OFFICE_CODE", "OFFICE_NAME ASC");
			
			if( $user_code )
				$uo_table = sts_user_office::getInstance($this->database, $this->debug);
		
			if( is_array($offices) && count( $offices ) > 0 ) {
				$offices_str = '<div id="OFFICES" class="panel panel-default">
				  <div class="panel-heading">
				    <h3 class="panel-title">Select At Least One Office</h3>
				  </div>
				  <div class="panel-body">
				';
				foreach( $offices as $row ) {
					$check = $user_code ?
						$uo_table->fetch_rows("USER_CODE = ".$user_code."
						AND OFFICE_CODE = ".$row["OFFICE_CODE"]) : false;
					if( $this->debug ) {
						echo "<pre>";
						var_dump($check);
						echo "</pre>";
					}
					$exists = is_array($check) && count($check) > 0;
					if( $this->debug ) echo "<p>".__METHOD__.": exists = ".($exists ? 'true' : 'false')."</p>";
					
					$offices_str .= '<div class="checkbox">
					    <label>
					      <input type="checkbox" class="office" name="OFFICE_'.$row["OFFICE_CODE"].'" id="OFFICE_'.$row["OFFICE_CODE"].'" value="'.$row["OFFICE_CODE"].'"'.($exists ? ' checked' : '').'> '.$row["OFFICE_NAME"].'
					    </label>
					    </div>
					    ';
				}
				$offices_str .= '</div>
				</div>
				<div id="OFFICE_HELP" hidden><span class="help-block"><span class="glyphicon glyphicon-warning-sign"></span> Select at least one office.</span></div>
				';		
			
				$form = str_replace('<!-- OFFICES -->', $offices_str, $form);
			}
		}
		return $form;
	}
	
	//! Process checkboxes for offices
	public function process_user_checkboxes( $user_code ) {
		if( $this->debug ) echo "<p>".__METHOD__.": user_code = $user_code</p>";
		if( $this->multi_company) {
			$uo_table = sts_user_office::getInstance($this->database, $this->debug);

			$offices = $this->fetch_rows("", "OFFICE_NAME, OFFICE_CODE", "OFFICE_NAME ASC");
			
			if( is_array($offices) && count( $offices ) > 0 ) {
				foreach( $offices as $row ) {
					$check = $uo_table->fetch_rows("USER_CODE = ".$user_code."
						AND OFFICE_CODE = ".$row["OFFICE_CODE"]);
					
					$exists = is_array($check) && count($check) > 0;
					if( $this->debug ) echo "<p>".__METHOD__.": exists = ".($exists ? 'true' : 'false')."</p>";
					
					if( is_array($_POST) &&
						isset($_POST['OFFICE_'.$row["OFFICE_CODE"]])) {
						
						if( ! $exists )
							$uo_table->add( array( 'USER_CODE' => $user_code, 'OFFICE_CODE' => $row["OFFICE_CODE"]) );
					} else {
						if( $exists )
							$uo_table->delete_row("USER_CODE = ".$user_code."
						AND OFFICE_CODE = ".$row["OFFICE_CODE"]);
					}
				}
			}
		}
	}
	
    //! Get the list of available offices for the current user
    // Admin gets all offices
    // Session variables:
    // EXT_USER_OFFICES - array of offices for menu
    // EXT_USER_OFFICE - selected office, default to first in list
    public function user_offices( $force = true) {
	    global $_SESSION;
	    
		if( $this->debug ) echo "<p>".__METHOD__.": entry, multi_company = ".($this->multi_company ? 'true' : 'false')."</p>";
		//! SCR# 375 - check $_SESSION['EXT_USER_CODE'] is set (should be)
		if( $this->multi_company && isset($_SESSION['EXT_USER_CODE']) ) {
			if( $force || ! isset($_SESSION['EXT_USER_OFFICES'])) {
			    if( in_group( EXT_GROUP_ADMIN ) )	//! Admin = all companies
			    	$check = $this->fetch_rows("", "OFFICE_NAME, OFFICE_CODE,
			    	(SELECT COMPANY_NAME FROM EXP_COMPANY
			    		WHERE EXP_OFFICE.COMPANY_CODE = EXP_COMPANY.COMPANY_CODE) COMPANY_NAME", "OFFICE_NAME ASC");
			    else
			    	$check = $this->database->get_multiple_rows(
			    		"SELECT EXP_USER_OFFICE.OFFICE_CODE, OFFICE_NAME,
			    		(SELECT COMPANY_NAME FROM EXP_COMPANY
			    		WHERE EXP_OFFICE.COMPANY_CODE = EXP_COMPANY.COMPANY_CODE) COMPANY_NAME
						FROM EXP_USER_OFFICE, EXP_OFFICE
						WHERE USER_CODE = ".$_SESSION['EXT_USER_CODE']."
						AND EXP_USER_OFFICE.OFFICE_CODE = EXP_OFFICE.OFFICE_CODE
						ORDER BY OFFICE_NAME ASC");
			    
			    if( is_array($check) && count($check) > 0 ) {
				    $offices = array();
				    $companies = array();
				    foreach( $check as $row ) {
					    $offices[$row["OFFICE_CODE"]] = $row["OFFICE_NAME"];
					    $companies[$row["OFFICE_CODE"]] = $row["COMPANY_NAME"];
				    }
				    $_SESSION['EXT_USER_OFFICES'] = $offices;
				    $_SESSION['EXT_USER_COMPANIES'] = $companies;
				    $_SESSION['EXT_USER_OFFICE'] = $check[0]["OFFICE_CODE"];
			    } else {
				    $_SESSION['EXT_USER_OFFICES'] = array(0 => 'NO OFFICE');
				    $_SESSION['EXT_USER_COMPANIES'] = array(0 => 'NO COMPANY');
				    $_SESSION['EXT_USER_OFFICE'] = 0;
			    }
		    }
	    } else {
		    $_SESSION['EXT_USER_OFFICES'] = array(0 => 'NO OFFICE');
		    $_SESSION['EXT_USER_COMPANIES'] = array(0 => 'NO COMPANY');
		    $_SESSION['EXT_USER_OFFICE'] = 0;
	    }
    }
    
    //! Given a company, list possible offices
    public function offices_menu( $company, $choice = null ) {
	    $menu = '';
	    if( $this->multi_company ) {
		    $check = $this->fetch_rows("COMPANY_CODE = $company",
		    	"OFFICE_NAME, OFFICE_CODE", "OFFICE_NAME ASC");
		    
		    if( is_array($check) && count($check) > 0 ) {
			    $menu = '<select class="form-control" name="OFFICE_CODE" id="OFFICE_CODE">
			    <option value="null"'.($choice === null || $choice == 'NULL' ? ' selected' : '').'>Any office</option>
			    ';
			    foreach( $check as $row ) {
				    $menu .= '<option value="'.$row["OFFICE_CODE"].'"'.($choice ==$row["OFFICE_CODE"] ? ' selected' : '').'>'.$row["OFFICE_NAME"].'</option>
				    ';
			    }
			     $menu .= '</select>
			     ';
		    }
		}
		return $menu;
    }
    
     //! Given a company, list possible offices
    public function offices_menu2( $choice = null ) {
	    $menu = '';
	    if( $this->multi_company ) {
		    
		    if( is_array($_SESSION['EXT_USER_OFFICES']) && count($_SESSION['EXT_USER_OFFICES']) > 0 ) {
			    $menu = '<select class="form-control  tip" title="Filter office which carrier has done work for in the last year" name="OFFICE_CODE" id="OFFICE_CODE" onchange="form.submit();">
			    <option value="all"'.($choice == 'all' ? ' selected' : '').'>All offices</option>
			    ';
			    foreach( $_SESSION['EXT_USER_OFFICES'] as $code => $name ) {
				    $menu .= '<option value="'.$code.'"'.($choice ==$code ? ' selected' : '').'>'.$name.'</option>
				    ';
			    }
			     $menu .= '</select>
			     ';
		    }
		}
		return $menu;
    }
    
   //! Get the name of the current office
    public function office_name() {
	    global $_SESSION;
		if( $this->debug ) echo "<p>".__METHOD__.": entry, multi_company = ".($this->multi_company ? 'true' : 'false')."</p>";
	    
		if( $this->multi_company ) {
			if( ! isset($_SESSION['EXT_USER_OFFICES']))
				$this->user_offices();
				
			$name = $_SESSION['EXT_USER_OFFICES'][$_SESSION['EXT_USER_OFFICE']];
		} else {
			$name = $this->setting->get( 'company', 'NAME' );
		}
		
		if( $this->debug ) echo "<p>".__METHOD__.": name = $name</p>";
		return $name;
    }
    
	//! SCR# 307 - Rate confirmation email per office
    //! Get the name & email of the current office
    public function office_from( $load ) {
	    $name = $email = "";
	    if( $this->multi_company ) {
		    $check = $this->database->get_one_row("
		    	SELECT O.OFFICE_NAME, O.EMAIL
				FROM EXP_OFFICE O, EXP_LOAD L
				WHERE L.OFFICE_CODE = O.OFFICE_CODE
				AND L.LOAD_CODE = ".$load );
			if( $this->debug ) {
				echo "<pre>".__METHOD__."check:\n";
				var_dump($check);
				echo "</pre>";
			}
			if( is_array($check) && ! empty($check["OFFICE_NAME"]) && ! empty($check["EMAIL"])) {
				$name = $check["OFFICE_NAME"];
				$email = $check["EMAIL"];
			} else {
				$name = $this->setting->get( 'company', 'NAME' );
			    $email = $this->setting->get( 'company', 'EMAIL' );
			}
	    } else {
			$name = $this->setting->get( 'company', 'NAME' );
		    $email = $this->setting->get( 'company', 'EMAIL' );
	    }
	    if( $this->debug ) echo "<h4>".__METHOD__.": return ".$name, ' ', $email."</h4>";
	    return array($name, $email);
    }
    
   //! Filter on the current office
    public function office_code_match( $prev_match = '' ) {
	    global $_SESSION;
		if( $this->debug ) echo "<p>".__METHOD__.": entry, multi_company = ".($this->multi_company ? 'true' : 'false')."</p>";
		$match = $prev_match;
	    
		if( $this->multi_company ) {
			if( ! isset($_SESSION['EXT_USER_OFFICES']))
				$this->user_offices();
				
			$match = ($prev_match <> '' ? $prev_match." AND " : "")."OFFICE_CODE = ".$_SESSION['EXT_USER_OFFICE'];
		}
		
		if( $this->debug ) echo "<p>".__METHOD__.": match = $match</p>";
		return $match;
    }

    public function office_code_match_multiple( $prev_match = '' ) {
	    global $_SESSION;
		if( $this->debug ) echo "<p>".__METHOD__.": entry, multi_company = ".($this->multi_company ? 'true' : 'false')."</p>";
		$match = $prev_match;
	    
		if( $this->multi_company ) {
			if( ! isset($_SESSION['EXT_USER_OFFICES']))
				$this->user_offices();
				
			$match = ($prev_match <> '' ? $prev_match." AND " : "")."OFFICE_CODE IN (".implode(', ', array_keys($_SESSION['EXT_USER_OFFICES'])).")";
		}
		
		if( $this->debug ) echo "<p>".__METHOD__.": match = $match</p>";
		return $match;
    }

    //! Filter on the same office as the load
    public function office_code_match_load( $load = 0, $prev_match = '' ) {
	    global $_SESSION;
		if( $this->debug ) echo "<p>".__METHOD__.": entry, multi_company = ".($this->multi_company ? 'true' : 'false')."</p>";
		$match = $prev_match;
	    
		if( $this->multi_company ) {
			if( ! isset($_SESSION['EXT_USER_OFFICES']))
				$this->user_offices();
				
			$match = ($prev_match <> '' ? $prev_match." AND " : "")."OFFICE_CODE = 
					(SELECT OFFICE_CODE FROM EXP_LOAD
					WHERE LOAD_CODE = $load)
			";
		}
		
		if( $this->debug ) echo "<p>".__METHOD__.": match = $match</p>";
		return $match;
    }

    //! Filter on the same office as the load
    public function office_code_match_driver( $load = 0, $prev_match = '' ) {
	    global $_SESSION;
		if( $this->debug ) echo "<p>".__METHOD__.": entry, multi_company = ".($this->multi_company ? 'true' : 'false')."</p>";
		$match = $prev_match;
	    
		if( $this->multi_company ) {
			if( ! isset($_SESSION['EXT_USER_OFFICES']))
				$this->user_offices();
				
			$match = ($prev_match <> '' ? $prev_match." AND " : "")."COMPANY_CODE = 
				(SELECT EXP_OFFICE.COMPANY_CODE FROM EXP_LOAD, EXP_OFFICE
					WHERE LOAD_CODE = $load
	                AND EXP_LOAD.OFFICE_CODE = EXP_OFFICE.OFFICE_CODE)
				AND COALESCE(OFFICE_CODE, (SELECT OFFICE_CODE FROM EXP_LOAD
					WHERE LOAD_CODE = $load)) = 
					(SELECT OFFICE_CODE FROM EXP_LOAD
					WHERE LOAD_CODE = $load)
			";
		}
		
		if( $this->debug ) echo "<p>".__METHOD__.": match = $match</p>";
		return $match;
    }

    //! Get the home currency of the current company
    public function home_currency() {
	    global $_SESSION;
		if( $this->debug ) echo "<p>".__METHOD__.": entry, multi_company = ".($this->multi_company ? 'true' : 'false')."</p>";
	    
		if( $this->multi_company ) {			
			$check = $this->fetch_rows( "OFFICE_CODE = ".$_SESSION['EXT_USER_OFFICE'],
				"HOME_CURRENCY, OFFICE_CODE");
			if( is_array($check) && count($check) == 1 &&
				isset($check[0]["HOME_CURRENCY"]) ) {
				$home_currency = $check[0]["HOME_CURRENCY"];
			} else { 
				$home_currency = $this->setting->get( 'option', 'HOME_CURRENCY' );
			}
		} else {
			$home_currency = $this->setting->get( 'option', 'HOME_CURRENCY' );
		}
		
		if( $this->debug ) echo "<p>".__METHOD__.": home_currency = $home_currency</p>";
		return $home_currency;
    }
    
     //! Get the office phone
    public function office_phone( $load ) {
	    $phone = "";
	    if( $this->multi_company ) {
		    $check = $this->database->get_one_row("
		    	SELECT O.BUSINESS_PHONE
				FROM EXP_OFFICE O, EXP_LOAD L
				WHERE L.OFFICE_CODE = O.OFFICE_CODE
				AND L.LOAD_CODE = ".$load );
			if( is_array($check) && isset($check["BUSINESS_PHONE"]))
				$phone = $check["BUSINESS_PHONE"];
	    } else {
		    $phone = $this->setting->get( 'company', 'BUSINESS_PHONE' );
	    }
	    return $phone;
    }

    public function office_fax( $load ) {
	    $phone = "";
	    if( $this->multi_company ) {
		    $check = $this->database->get_one_row("
		    	SELECT O.FAX_PHONE
				FROM EXP_OFFICE O, EXP_LOAD L
				WHERE L.OFFICE_CODE = O.OFFICE_CODE
				AND L.LOAD_CODE = ".$load );
			if( is_array($check) && isset($check["FAX_PHONE"]))
				$phone = $check["FAX_PHONE"];
	    } else {
		    $phone = $this->setting->get( 'company', 'FAX_PHONE' );
	    }
	    return $phone;
    }

     //! Get the emergency/after hours phone
    public function emergency_phone( $load ) {
	    $phone = "";
	    if( $this->multi_company ) {
		    $check = $this->database->get_one_row("
		    	SELECT O.EMERGENCY_PHONE
				FROM EXP_OFFICE O, EXP_LOAD L
				WHERE L.OFFICE_CODE = O.OFFICE_CODE
				AND L.LOAD_CODE = ".$load );
			if( is_array($check) && isset($check["EMERGENCY_PHONE"]))
				$phone = $check["EMERGENCY_PHONE"];
	    } else {
		    $phone = $this->setting->get( 'company', 'EMERGENCY_PHONE' );
	    }
	    return $phone;
    }

     //! Get the office email
    public function office_email( $load ) {
	    $email = "";
	    if( $this->multi_company ) {
		    $check = $this->database->get_one_row("
		    	SELECT O.EMAIL
				FROM EXP_OFFICE O, EXP_LOAD L
				WHERE L.OFFICE_CODE = O.OFFICE_CODE
				AND L.LOAD_CODE = ".$load );
			if( is_array($check) && isset($check["EMAIL"]))
				$email = $check["EMAIL"];
	    } else {
		    $email = $this->setting->get( 'company', 'EMAIL' );
	    }
	    return $email;
    }

}

class sts_user_office extends sts_table {

	// Constructor does not need the table name
	public function __construct( $database, $debug = false ) {

		$this->debug = $debug;
		$this->primary_key = "USER_OFFICE_CODE";
		if( $this->debug ) echo "<p>Create sts_user_office</p>";
		parent::__construct( $database, USER_OFFICE_TABLE, $debug);
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

//! Form Specifications - For use with sts_form

$sts_form_addoffice_form = array(	//! $sts_form_addoffice_form
	'title' => '<img src="images/company_icon.png" alt="company_icon" height="24"> Add office',
	'action' => 'exp_addoffice.php',
	//'actionextras' => 'disabled',
	'cancel' => 'exp_listoffice.php',
	'name' => 'addoffice',
	'okbutton' => 'Save Changes',
	'saveadd' => 'Add Another',
	'cancelbutton' => 'Cancel',
		'layout' => '
	<div class="form-group">
		<div class="col-sm-6">
			<div class="form-group tighter">
				<label for="COMPANY_CODE" class="col-sm-4 control-label">#COMPANY_CODE#</label>
				<div class="col-sm-8">
					%COMPANY_CODE%
				</div>
			</div>
			<div class="form-group tighter">
				<label for="OFFICE_NAME" class="col-sm-4 control-label">#OFFICE_NAME#</label>
				<div class="col-sm-8">
					%OFFICE_NAME%
				</div>
			</div>
			<div class="form-group tighter">
				<label for="ADDRESS_1" class="col-sm-4 control-label">#ADDRESS_1#</label>
				<div class="col-sm-8">
					%ADDRESS_1%
				</div>
			</div>
			<div class="form-group tighter">
				<label for="ADDRESS_2" class="col-sm-4 control-label">#ADDRESS_2#</label>
				<div class="col-sm-8">
					%ADDRESS_2%
				</div>
			</div>
			<div class="form-group tighter">
				<label for="CITY" class="col-sm-4 control-label">#CITY#</label>
				<div class="col-sm-8">
					%CITY%
				</div>
			</div>
			<div class="form-group tighter">
				<label for="STATE" class="col-sm-4 control-label">#STATE#</label>
				<div class="col-sm-8">
					%STATE%
				</div>
			</div>
			<div class="form-group tighter">
				<label for="ZIP" class="col-sm-4 control-label">#ZIP#</label>
				<div class="col-sm-8">
					%ZIP%
				</div>
			</div>
			<div class="form-group tighter">
				<label for="COUNTRY" class="col-sm-4 control-label">#COUNTRY#</label>
				<div class="col-sm-8">
					%COUNTRY%
				</div>
			</div>
			<div class="form-group tighter">
				<label for="BUSINESS_CODE" class="col-sm-4 control-label">#BUSINESS_CODE#</label>
				<div class="col-sm-8">
					%BUSINESS_CODE%
				</div>
			</div>
		</div>

		<div class="col-sm-6">
			<div class="form-group tighter">
				<label for="BUSINESS_PHONE" class="col-sm-4 control-label">#BUSINESS_PHONE#</label>
				<div class="col-sm-8">
					%BUSINESS_PHONE%
				</div>
			</div>
			<div class="form-group tighter">
				<label for="EMERGENCY_PHONE" class="col-sm-4 control-label">#EMERGENCY_PHONE#</label>
				<div class="col-sm-8">
					%EMERGENCY_PHONE%
				</div>
			</div>
			<div class="form-group tighter">
				<label for="FAX_PHONE" class="col-sm-4 control-label">#FAX_PHONE#</label>
				<div class="col-sm-8">
					%FAX_PHONE%
				</div>
			</div>
			<div class="form-group tighter">
				<label for="EMAIL" class="col-sm-4 control-label">#EMAIL#</label>
				<div class="col-sm-8">
					%EMAIL%
				</div>
			</div>
			<div class="form-group tighter">
				<label for="CONTACT_NAME" class="col-sm-4 control-label">#CONTACT_NAME#</label>
				<div class="col-sm-8">
					%CONTACT_NAME%
				</div>
			</div>
			<div class="form-group tighter">
				<label for="INVOICE_PREFIX" class="col-sm-4 control-label">#INVOICE_PREFIX#</label>
				<div class="col-sm-8">
					%INVOICE_PREFIX%
				</div>
			</div>
			<div class="form-group tighter">
				<label for="INVOICE_NUMBER" class="col-sm-4 control-label">#INVOICE_NUMBER#</label>
				<div class="col-sm-8">
					%INVOICE_NUMBER%
				</div>
			</div>
			<div class="form-group tighter">
				<label for="DRIVER_BILL_PREFIX" class="col-sm-4 control-label">#DRIVER_BILL_PREFIX#</label>
				<div class="col-sm-8">
					%DRIVER_BILL_PREFIX%
				</div>
			</div>
			<div class="form-group tighter">
				<label for="CARRIER_BILL_PREFIX" class="col-sm-4 control-label">#CARRIER_BILL_PREFIX#</label>
				<div class="col-sm-8">
					%CARRIER_BILL_PREFIX%
				</div>
			</div>

		</div>
	</div>
	
	'
);

$sts_form_editoffice_form = array( //!$sts_form_editoffice_form
	'title' => '<img src="images/company_icon.png" alt="company_icon" height="24"> Edit office',
	'action' => 'exp_editoffice.php',
	//'actionextras' => 'disabled',
	'cancel' => 'exp_listoffice.php',
	'name' => 'editoffice',
	'okbutton' => 'Save Changes to office',
	'cancelbutton' => 'Back to offices',
		'layout' => '
		%OFFICE_CODE%
	<div class="form-group">
		<div class="col-sm-6">
			<div class="form-group tighter">
				<label for="COMPANY_CODE" class="col-sm-4 control-label">#COMPANY_CODE#</label>
				<div class="col-sm-8">
					%COMPANY_CODE%
				</div>
			</div>
			<div class="form-group tighter">
				<label for="OFFICE_NAME" class="col-sm-4 control-label">#OFFICE_NAME#</label>
				<div class="col-sm-8">
					%OFFICE_NAME%
				</div>
			</div>
			<div class="form-group tighter">
				<label for="ADDRESS_1" class="col-sm-4 control-label">#ADDRESS_1#</label>
				<div class="col-sm-8">
					%ADDRESS_1%
				</div>
			</div>
			<div class="form-group tighter">
				<label for="ADDRESS_2" class="col-sm-4 control-label">#ADDRESS_2#</label>
				<div class="col-sm-8">
					%ADDRESS_2%
				</div>
			</div>
			<div class="form-group tighter">
				<label for="CITY" class="col-sm-4 control-label">#CITY#</label>
				<div class="col-sm-8">
					%CITY%
				</div>
			</div>
			<div class="form-group tighter">
				<label for="STATE" class="col-sm-4 control-label">#STATE#</label>
				<div class="col-sm-8">
					%STATE%
				</div>
			</div>
			<div class="form-group tighter">
				<label for="ZIP" class="col-sm-4 control-label">#ZIP#</label>
				<div class="col-sm-8">
					%ZIP%
				</div>
			</div>
			<div class="form-group tighter">
				<label for="COUNTRY" class="col-sm-4 control-label">#COUNTRY#</label>
				<div class="col-sm-8">
					%COUNTRY%
				</div>
			</div>
			<div class="form-group tighter">
				<label for="BUSINESS_CODE" class="col-sm-4 control-label">#BUSINESS_CODE#</label>
				<div class="col-sm-8">
					%BUSINESS_CODE%
				</div>
			</div>
		</div>

		<div class="col-sm-6">
			<div class="form-group tighter">
				<label for="BUSINESS_PHONE" class="col-sm-4 control-label">#BUSINESS_PHONE#</label>
				<div class="col-sm-8">
					%BUSINESS_PHONE%
				</div>
			</div>
			<div class="form-group tighter">
				<label for="EMERGENCY_PHONE" class="col-sm-4 control-label">#EMERGENCY_PHONE#</label>
				<div class="col-sm-8">
					%EMERGENCY_PHONE%
				</div>
			</div>
			<div class="form-group tighter">
				<label for="FAX_PHONE" class="col-sm-4 control-label">#FAX_PHONE#</label>
				<div class="col-sm-8">
					%FAX_PHONE%
				</div>
			</div>
			<div class="form-group tighter">
				<label for="EMAIL" class="col-sm-4 control-label">#EMAIL#</label>
				<div class="col-sm-8">
					%EMAIL%
				</div>
			</div>
			<div class="form-group tighter">
				<label for="CONTACT_NAME" class="col-sm-4 control-label">#CONTACT_NAME#</label>
				<div class="col-sm-8">
					%CONTACT_NAME%
				</div>
			</div>
			<div class="form-group tighter">
				<label for="INVOICE_PREFIX" class="col-sm-4 control-label">#INVOICE_PREFIX#</label>
				<div class="col-sm-8">
					%INVOICE_PREFIX%
				</div>
			</div>
			<div class="form-group tighter">
				<label for="INVOICE_NUMBER" class="col-sm-4 control-label">#INVOICE_NUMBER#</label>
				<div class="col-sm-8">
					%INVOICE_NUMBER%
				</div>
			</div>
			<div class="form-group tighter">
				<label for="DRIVER_BILL_PREFIX" class="col-sm-4 control-label">#DRIVER_BILL_PREFIX#</label>
				<div class="col-sm-8">
					%DRIVER_BILL_PREFIX%
				</div>
			</div>
			<div class="form-group tighter">
				<label for="CARRIER_BILL_PREFIX" class="col-sm-4 control-label">#CARRIER_BILL_PREFIX#</label>
				<div class="col-sm-8">
					%CARRIER_BILL_PREFIX%
				</div>
			</div>

		</div>
	</div>
	
	'
);

//! Field Specifications - For use with sts_form

$sts_form_add_office_fields = array(	//! $sts_form_add_office_fields
	'OFFICE_NAME' => array( 'label' => 'Name', 'format' => 'text', 'extras' => 'required' ),
	'ADDRESS_1' => array( 'label' => 'Address 1', 'format' => 'text' ),
	'ADDRESS_2' => array( 'label' => 'Address 2', 'format' => 'text' ),
	'CITY' => array( 'label' => 'City', 'format' => 'text' ),
	'STATE' => array( 'label' => 'State/Province', 'format' => 'state' ),
	'ZIP' => array( 'label' => 'Zip/Postcode', 'format' => 'zip' ),
	'COUNTRY' => array( 'label' => 'Country', 'format' => 'country' ),
	'CONTACT_NAME' => array( 'label' => 'Contact name', 'format' => 'text' ),
	'BUSINESS_PHONE' => array( 'label' => 'Phone', 'format' => 'text' ),
	'EMERGENCY_PHONE' => array( 'label' => 'Emergency Phone', 'format' => 'text' ),
	'FAX_PHONE' => array( 'label' => 'Fax#', 'format' => 'text' ),
	'EMAIL' => array( 'label' => 'Email', 'format' => 'text', 'extras' => 'required' ),
	'INVOICE_PREFIX' => array( 'label' => 'Invoice Prefix', 'format' => 'text' ),
	'INVOICE_NUMBER' => array( 'label' => 'Invoice Number', 'format' => 'number', 'align' => 'right' ),
	'DRIVER_BILL_PREFIX' => array( 'label' => 'Driver Bill Prefix', 'format' => 'text' ),
	'CARRIER_BILL_PREFIX' => array( 'label' => 'Carrier Bill Prefix', 'format' => 'text' ),
	'COMPANY_CODE' => array( 'label' => 'Company', 'format' => 'table',
		'table' => COMPANY_TABLE, 'key' => 'COMPANY_CODE', 'fields' => 'COMPANY_NAME' ),
	'BUSINESS_CODE' => array( 'label' => 'Business Code', 'format' => 'table',
		'table' => BUSINESS_CODE_TABLE, 'key' => 'BUSINESS_CODE',
		'condition' => "APPLIES_TO = 'shipment'", 'fields' => 'BC_NAME' ),
);

$sts_form_edit_office_fields = array(	//! $sts_form_edit_office_fields
	'OFFICE_CODE' => array( 'format' => 'hidden' ),
	'OFFICE_NAME' => array( 'label' => 'Name', 'format' => 'text', 'extras' => 'required' ),
	'ADDRESS_1' => array( 'label' => 'Address 1', 'format' => 'text' ),
	'ADDRESS_2' => array( 'label' => 'Address 2', 'format' => 'text' ),
	'CITY' => array( 'label' => 'City', 'format' => 'text' ),
	'STATE' => array( 'label' => 'State/Province', 'format' => 'state' ),
	'ZIP' => array( 'label' => 'Zip/Postcode', 'format' => 'zip' ),
	'COUNTRY' => array( 'label' => 'Country', 'format' => 'country' ),
	'CONTACT_NAME' => array( 'label' => 'Contact name', 'format' => 'text' ),
	'BUSINESS_PHONE' => array( 'label' => 'Phone', 'format' => 'text' ),
	'EMERGENCY_PHONE' => array( 'label' => 'Emergency Phone', 'format' => 'text' ),
	'FAX_PHONE' => array( 'label' => 'Fax#', 'format' => 'text' ),
	'EMAIL' => array( 'label' => 'Email', 'format' => 'text', 'extras' => 'required' ),
	'INVOICE_PREFIX' => array( 'label' => 'Invoice Prefix', 'format' => 'text' ),
	'INVOICE_NUMBER' => array( 'label' => 'Invoice Number', 'format' => 'number', 'align' => 'right' ),
	'DRIVER_BILL_PREFIX' => array( 'label' => 'Driver Bill Prefix', 'format' => 'text' ),
	'CARRIER_BILL_PREFIX' => array( 'label' => 'Carrier Bill Prefix', 'format' => 'text' ),
	'COMPANY_CODE' => array( 'label' => 'Company', 'format' => 'table',
		'table' => COMPANY_TABLE, 'key' => 'COMPANY_CODE', 'fields' => 'COMPANY_NAME' ),
	'BUSINESS_CODE' => array( 'label' => 'Business Code', 'format' => 'table',
		'table' => BUSINESS_CODE_TABLE, 'key' => 'BUSINESS_CODE',
		'condition' => "APPLIES_TO = 'shipment'", 'fields' => 'BC_NAME' ),
);

//! Layout Specifications - For use with sts_result

$sts_result_office_layout = array(
	'OFFICE_CODE' => array( 'format' => 'hidden' ),
	'OFFICE_NAME' => array( 'label' => 'Name', 'format' => 'text' ),
	'COMPANY_CODE' => array( 'label' => 'Company', 'format' => 'table',
		'table' => COMPANY_TABLE, 'key' => 'COMPANY_CODE', 'fields' => 'COMPANY_NAME' ),
	'ADDRESS_1' => array( 'label' => 'Address', 'format' => 'text',
		'group' => array('ADDRESS_1', 'ADDRESS_2', 'CITY', 'STATE', 'ZIP', 'COUNTRY'),
		'glue' => ', ' ),

	'CONTACT_NAME' => array( 'label' => 'Contact', 'format' => 'text' ),
	'EMAIL' => array( 'label' => 'Email', 'format' => 'text' ),
	'BUSINESS_PHONE' => array( 'label' => 'Phone', 'format' => 'text' ),
	'FAX_PHONE' => array( 'label' => 'Fax#', 'format' => 'text' ),
	'INVOICE_PREFIX' => array( 'label' => 'Prefixes', 'format' => 'text',
		'group' => array('INVOICE_PREFIX', 'DRIVER_BILL_PREFIX', 'CARRIER_BILL_PREFIX'),
		'glue' => ', ' ),

);

//! Edit/Delete Button Specifications - For use with sts_result

$sts_result_office_edit = array(
	'title' => '<img src="images/company_icon.png" alt="company_icon" height="24"> Offices',
	'sort' => 'OFFICE_NAME asc',
	'cancel' => 'index.php',
	'add' => 'exp_addoffice.php',
	//'actionextras' => 'disabled',
	'addbutton' => 'Add Office',
	'cancelbutton' => 'Back',
	'filters_html' => '<a class="btn btn-sm btn-success" href="exp_listoffice.php"><span class="glyphicon glyphicon-refresh"></span></a> <a class="btn btn-sm btn-default" href="exp_listcompany.php"><img src="images/company_icon.png" alt="company_icon" height="18"> Companies</a>',
	'rowbuttons' => array(
		array( 'url' => 'exp_editoffice.php?CODE=', 'key' => 'OFFICE_CODE', 'label' => 'OFFICE_NAME', 'tip' => 'Edit office ', 'icon' => 'glyphicon glyphicon-edit' ),
		array( 'url' => 'exp_deleteoffice.php?CODE=', 'key' => 'OFFICE_CODE', 'label' => 'OFFICE_NAME', 'tip' => 'Delete office ', 'icon' => 'glyphicon glyphicon-trash', 'confirm' => 'yes', 'showif' => 'can_delete' )
	)
);


?>
