<?php

// $Id: sts_company_class.php 4350 2021-03-02 19:14:52Z duncan $
// Company class

// $Id: sts_company_class.php 4350 2021-03-02 19:14:52Z duncan $
// Company class, all activity related to companies.

// no direct access
defined('_STS_INCLUDE') or die('Restricted access');

require_once( "sts_table_class.php" );
require_once( "sts_setting_class.php" );
require_once( "sts_item_list_class.php" );

class sts_company extends sts_table {

	private $setting;
	private $item_list;
	private $multi_company;
	private $invoice_prefix;
	private $bill_prefix;
	private $driver_bill_prefix;
	
	// Constructor does not need the table name
	public function __construct( $database, $debug = false ) {

		$this->debug = $debug;
		$this->primary_key = "COMPANY_CODE";
		if( $this->debug ) echo "<p>Create sts_company</p>";
		parent::__construct( $database, COMPANY_TABLE, $debug);
		$this->setting = sts_setting::getInstance( $this->database, $this->debug );
		$this->multi_company = $this->setting->get( 'option', 'MULTI_COMPANY' ) == 'true';
		$this->invoice_prefix = $this->setting->get( 'api', 'QUICKBOOKS_INVOICE_PREFIX' );
		$this->bill_prefix = $this->setting->get( 'api', 'QUICKBOOKS_BILL_PREFIX' );
		$this->driver_bill_prefix = $this->setting->get( 'api', 'QUICKBOOKS_DRIVER_BILL_PREFIX' );
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

	//! Check if we can delete a company.
	// Only possible if company has not been used yet.
	public function can_delete( $code ) {
		$result = false;
		$check = $this->database->get_one_row(
			"SELECT (SELECT COUNT(*)
			FROM EXP_OFFICE
			WHERE COMPANY_CODE = $code) INUSE" );
		if( is_array($check) && isset($check["INUSE"]))
			$result = intval($check["INUSE"]) == 0;
		return $result;	
	}
	
	public function delete( $code, $type = "" ) {
		if( $this->can_delete( $code ) )
			parent::delete( $code, $type );
	}
	
    //! Get the default COMPANY_CODE, usually 1
    public function default_company() {
	    $code = false;
	    if( $this->multi_company ) {
		    $check = $this->fetch_rows("", "MIN(COMPANY_CODE) AS COMPANY_CODE" );
			if( is_array($check) && count($check) == 1 &&
				isset($check[0]["COMPANY_CODE"]))
				$code = $check[0]["COMPANY_CODE"];
	    }
	    return $code;
    }

    //! create a menu of available companies
    public function menu( $selected = false, $id = 'IFTA_COMPANY', $match = '', $onchange = true, $any = false, $size = 'sm' ) {
		if( $this->debug ) echo "<p>".__METHOD__.": entry, multi_company = ".($this->multi_company ? 'true' : 'false')."</p>";
		if( $this->multi_company) {
			$select = false;
	
			$choices = $this->fetch_rows( $match, "COMPANY_CODE, COMPANY_NAME, HOME_CURRENCY", "COMPANY_NAME ASC" );
	
			if( is_array($choices) && count($choices) > 0) {
				
				$select = '<select class="form-control input-'.$size.'" name="'.$id.'" id="'.$id.'"'.($onchange ? ' onchange="form.submit();"' : '').'>
				';
				if( $any ) {
					$select .= '<option value="0"';
					if( $selected && $selected == 0 )
						$select .= ' selected';
					$select .= '>All Companies</option>
					';
				}
				foreach( $choices as $row ) {
					$select .= '<option value="'.$row["COMPANY_CODE"].'"';
					if( $selected && $selected == $row["COMPANY_CODE"] )
						$select .= ' selected';
					$select .= '>'.$row["COMPANY_NAME"].' ('.$row["HOME_CURRENCY"].')</option>
					';
				}
				$select .= '</select>';
			}
		}
			
		return $select;
	}

    //! Get the name of the current office
    public function ifta_base() {
	    global $_SESSION;
		if( $this->debug ) echo "<p>".__METHOD__.": entry, multi_company = ".($this->multi_company ? 'true' : 'false')."</p>";
	    
		if( $this->multi_company && isset($_SESSION['IFTA_COMPANY'])) {
			$check = $this->fetch_rows($this->primary_key." = ".$_SESSION['IFTA_COMPANY'],
				"IFTA_BASE_JURISDICTION" );
			if( is_array($check) && count($check) == 1 &&
				isset($check[0]["IFTA_BASE_JURISDICTION"]))
				$base = $check[0]["IFTA_BASE_JURISDICTION"];
			else
				$base = $this->setting->get( 'api', 'IFTA_BASE_JURISDICTION' );
		} else {
			$base = $this->setting->get( 'api', 'IFTA_BASE_JURISDICTION' );
		}
		
		if( $this->debug ) echo "<p>".__METHOD__.": base = $base</p>";
		return $base;
    }

     //! Get the company info - for Quickbooks API
    public function get_info( $pk ) {
	    $name = $line1 = $line2 = $city = $state = $postal = $email = $phone = $web = "";
    	$qb_multi = $this->setting->get( 'api', 'QUICKBOOKS_MULTI_COMPANY' ) == 'true';
	    if( $this->multi_company && $qb_multi ) {
		    $check = $this->fetch_rows("COMPANY_CODE = ".$pk,
		    	"COMPANY_NAME, ADDRESS_1, ADDRESS_2, CITY, STATE, ZIP, COUNTRY,
		    	EMAIL, BUSINESS_PHONE, WEB_URL, HOME_CURRENCY" );
			if( is_array($check) && count($check) == 1 ) {
				$name = empty($check[0]["COMPANY_NAME"]) ?	'' : $check[0]["COMPANY_NAME"];
				$line1 = empty($check[0]["ADDRESS_1"]) ?	'' : $check[0]["ADDRESS_1"];
				$line2 = empty($check[0]["ADDRESS_2"]) ?	'' : $check[0]["ADDRESS_2"];
				$city = empty($check[0]["CITY"]) ?			'' : $check[0]["CITY"];
				$state = empty($check[0]["STATE"]) ?		'' : $check[0]["STATE"];
				$postal = empty($check[0]["ZIP"]) ?			'' : $check[0]["ZIP"];
				$country = empty($check[0]["COUNTRY"]) ?	'' : $check[0]["COUNTRY"];
				$email = empty($check[0]["EMAIL"]) ?		'' : $check[0]["EMAIL"];
				$phone = empty($check[0]["BUSINESS_PHONE"]) ? '' : $check[0]["BUSINESS_PHONE"];
				$web = empty($check[0]["WEB_URL"]) ?		'' : $check[0]["WEB_URL"];
				$currency = empty($check[0]["HOME_CURRENCY"]) ?		'' : $check[0]["HOME_CURRENCY"];
			}
	    } else {
			$name = $this->setting->get( 'company', 'NAME' );
			$line1 = $this->setting->get( 'company', 'ADDRESS_1' );
			$line2 = $this->setting->get( 'company', 'ADDRESS_2' );
			$city = $this->setting->get( 'company', 'CITY' );
			$state = $this->setting->get( 'company', 'STATE' );
			$postal = $this->setting->get( 'company', 'ZIP' );
			$country = $this->setting->get( 'company', 'COUNTRY' );
			$email = $this->setting->get( 'company', 'EMAIL' );
			$phone = $this->setting->get( 'company', 'BUSINESS_PHONE' );
			$web = $this->setting->get( 'company', 'WEB_URL' );
			$currency = $this->setting->get( 'option', 'HOME_CURRENCY' );
	    }
	    return array($name, $line1, $line2, $city, $state, $postal, $country,
	    	$email, $phone, $web, $currency);
    }

     //! Get the company info - for Quickbooks API
    public function set_info( $pk, $name, $line1, $line2, $city,
    	$state, $postal, $country, $email, $phone, $web, $currency ) {
	    	
    	$qb_multi = $this->setting->get( 'api', 'QUICKBOOKS_MULTI_COMPANY' ) == 'true';
	    if( $this->multi_company && $qb_multi && $pk > 0 ) {
		    $result = $this->update("COMPANY_CODE = ".$pk,
		    	array("COMPANY_NAME" => $name, "ADDRESS_1" => $line1, "ADDRESS_2" => $line2,
			    	"CITY" => $city, "STATE" => $state, "ZIP" => $postal, "COUNTRY" => $country,
			    	"EMAIL" => $email, "BUSINESS_PHONE" => $phone, "WEB_URL" => $web,
			    	"HOME_CURRENCY" => $currency ) );
	    } else {
			$result = $this->setting->set( 'company', 'NAME', $name ) &&
				$this->setting->set( 'company', 'ADDRESS_1', $line1 ) &&
				$this->setting->set( 'company', 'ADDRESS_2', $line2 ) &&
				$this->setting->set( 'company', 'CITY', $city ) &&
				$this->setting->set( 'company', 'STATE', $state ) &&
				$this->setting->set( 'company', 'ZIP', $postal ) &&
				$this->setting->set( 'company', 'COUNTRY', $country ) &&
				$this->setting->set( 'company', 'EMAIL', $email ) &&
				$this->setting->set( 'company', 'BUSINESS_PHONE', $phone ) &&
				$this->setting->set( 'company', 'WEB_URL', $web ) &&
				$this->setting->set( 'option', 'HOME_CURRENCY', $currency );
	    }
	    
	    return $result;
	}


   //! Get the company name
    public function name( $pk, $link = true ) {
	    $name = "";
	    if( $this->multi_company ) {
		    $check = $this->fetch_rows("COMPANY_CODE = ".$pk, "COMPANY_NAME" );
			if( is_array($check) && count($check) == 1 &&
				isset($check[0]["COMPANY_NAME"]))
				$name = $check[0]["COMPANY_NAME"];
				if( in_group( EXT_GROUP_ADMIN ) && $link )
					$name = '<a href="exp_editcompany.php?CODE='.$pk.'">'.$name.'</a>';
	    } else {
		    $name = $this->setting->get( 'company', 'NAME' );
	    }
	    return $name;
    }

    //! Get the company name
    public function company_name( $code, $type = 'load' ) {
	    $name = "";
	    if( $this->multi_company ) {
		    if( $type == 'load' )
		    	$query = "SELECT C.COMPANY_NAME
					FROM EXP_COMPANY C, EXP_OFFICE O, EXP_LOAD L
					WHERE L.OFFICE_CODE = O.OFFICE_CODE
					AND O.COMPANY_CODE = C.COMPANY_CODE
					AND L.LOAD_CODE = ".$code;
			else
		    	$query = "SELECT C.COMPANY_NAME
					FROM EXP_COMPANY C, EXP_OFFICE O, EXP_SHIPMENT S
					WHERE S.OFFICE_CODE = O.OFFICE_CODE
					AND O.COMPANY_CODE = C.COMPANY_CODE
					AND S.SHIPMENT_CODE = ".$code;

		    $check = $this->database->get_one_row( $query );
			if( is_array($check) && isset($check["COMPANY_NAME"]))
				$name = $check["COMPANY_NAME"];
	    } else {
		    $name = $this->setting->get( 'company', 'NAME' );
	    }
	    return $name;
    }

    //! Get the company logo
    public function company_logo( $code, $type = 'load' ) {
		if( $this->debug ) echo "<p>".__METHOD__.": entry, $code, $type</p>";
	    $logo = "";

	    //! SCR# 205 - check for office, company and settings logo
	    if( $type == 'load')
	    	$query = "SELECT GET_LOGO( LOAD_OFFICE( $code ) ) AS LOGO";
	    else
	    	$query = "SELECT GET_LOGO( SHIPMENT_OFFICE( $code ) ) AS LOGO";
	    
	    $check1 = $this->database->get_one_row($query);
         
         if( is_array($check1) && isset($check1["LOGO"]) ) {
	         if( $this->debug ) {
		    	echo "<pre>".__METHOD__.": check1\n";
		    	var_dump($check1);
		    	echo "</pre>";
	         }
             $stored_as = str_replace('/', '\\', $check1['LOGO']);
             if( file_exists($stored_as) )
	             $logo = $stored_as;
	         else {
		         if( $this->debug ) echo "<p>".__METHOD__.": file missing! $stored_as</p>";
		         // fall back to setting
		         $logo = $this->setting->get( 'company', 'LOGO' );
	         }
         }
	    
		if( $this->debug ) echo "<p>".__METHOD__.": code = $code, type = $type, logo = ".$logo."</p>";
	    return $logo;
    }

      //! Get the company phone
    public function company_phone( $code, $type = 'load' ) {
	    $phone = "";
	    if( $this->multi_company ) {
		    if( $type == 'load' )
		    	$query = "SELECT C.BUSINESS_PHONE
					FROM EXP_COMPANY C, EXP_OFFICE O, EXP_LOAD L
					WHERE L.OFFICE_CODE = O.OFFICE_CODE
					AND O.COMPANY_CODE = C.COMPANY_CODE
					AND L.LOAD_CODE = ".$code;
			else
		    	$query = "SELECT C.BUSINESS_PHONE
					FROM EXP_COMPANY C, EXP_OFFICE O, EXP_SHIPMENT S
					WHERE S.OFFICE_CODE = O.OFFICE_CODE
					AND O.COMPANY_CODE = C.COMPANY_CODE
					AND S.SHIPMENT_CODE = ".$code;
			
		    $check = $this->database->get_one_row( $query );
			if( is_array($check) && isset($check["BUSINESS_PHONE"]))
				$phone = $check["BUSINESS_PHONE"];
	    } else {
		    $phone = $this->setting->get( 'company', 'BUSINESS_PHONE' );
	    }
	    return $phone;
    }

    //! Get the company fax
    public function company_fax( $code, $type = 'load' ) {
	    $fax = "";
	    if( $this->multi_company ) {
		    if( $type == 'load' )
		    	$query = "SELECT C.FAX_PHONE
					FROM EXP_COMPANY C, EXP_OFFICE O, EXP_LOAD L
					WHERE L.OFFICE_CODE = O.OFFICE_CODE
					AND O.COMPANY_CODE = C.COMPANY_CODE
					AND L.LOAD_CODE = ".$code;
			else
		    	$query = "SELECT C.FAX_PHONE
					FROM EXP_COMPANY C, EXP_OFFICE O, EXP_SHIPMENT S
					WHERE S.OFFICE_CODE = O.OFFICE_CODE
					AND O.COMPANY_CODE = C.COMPANY_CODE
					AND S.SHIPMENT_CODE = ".$code;
			
		    $check = $this->database->get_one_row( $query );
			if( is_array($check) && isset($check["FAX_PHONE"]))
				$fax = $check["FAX_PHONE"];
	    } else {
		    $fax = $this->setting->get( 'company', 'FAX_PHONE' );
	    }
	    return $fax;
    }

      //! Get the company email
    public function company_email( $code, $type = 'load' ) {
	    $email = "";
	    if( $this->multi_company ) {
		    if( $type == 'load' )
		    	$query = "SELECT C.EMAIL
					FROM EXP_COMPANY C, EXP_OFFICE O, EXP_LOAD L
					WHERE L.OFFICE_CODE = O.OFFICE_CODE
					AND O.COMPANY_CODE = C.COMPANY_CODE
					AND L.LOAD_CODE = ".$code;
			else
		    	$query = "SELECT C.EMAIL
					FROM EXP_COMPANY C, EXP_OFFICE O, EXP_SHIPMENT S
					WHERE S.OFFICE_CODE = O.OFFICE_CODE
					AND O.COMPANY_CODE = C.COMPANY_CODE
					AND S.SHIPMENT_CODE = ".$code;
			
		    $check = $this->database->get_one_row( $query );
			if( is_array($check) && isset($check["EMAIL"]))
				$email = $check["EMAIL"];
	    } else {
		    $email = $this->setting->get( 'company', 'EMAIL' );
	    }
	    return $email;
    }

   //! Get the company address
    public function company_address( $code, $type = 'load' ) {
	    $company_addr = "";
	    if( $this->multi_company ) {
		    if( $type == 'load' )
		    	$query = "SELECT C.ADDRESS_1, C.ADDRESS_1, C.CITY, C.STATE,
			    	C.ZIP, C.COUNTRY, C.BUSINESS_PHONE, C.FAX_PHONE
					FROM EXP_COMPANY C, EXP_OFFICE O, EXP_LOAD L
					WHERE L.OFFICE_CODE = O.OFFICE_CODE
					AND O.COMPANY_CODE = C.COMPANY_CODE
					AND L.LOAD_CODE = ".$code;
			else
		    	$query = "SELECT C.ADDRESS_1, C.ADDRESS_1, C.CITY, C.STATE,
			    	C.ZIP, C.COUNTRY, C.BUSINESS_PHONE, C.FAX_PHONE
					FROM EXP_COMPANY C, EXP_OFFICE O, EXP_SHIPMENT S
					WHERE S.OFFICE_CODE = O.OFFICE_CODE
					AND O.COMPANY_CODE = C.COMPANY_CODE
					AND S.SHIPMENT_CODE = ".$code;

		    $check = $this->database->get_one_row( $query );
			if( is_array($check) ) {
				$company_addr1 = isset($check["ADDRESS_1"]) ? $check["ADDRESS_1"] : '';
				$company_addr2 = isset($check["ADDRESS_2"]) ? $check["ADDRESS_2"] : '';
				$company_city = isset($check["CITY"]) ? $check["CITY"] : '';
				$company_state = isset($check["STATE"]) ? $check["STATE"] : '';
				$company_zip = isset($check["ZIP"]) ? $check["ZIP"] : '';
				$company_country = isset($check["COUNTRY"]) ? $check["COUNTRY"] : '';
				$company_phone = isset($check["BUSINESS_PHONE"]) ? $check["BUSINESS_PHONE"] : '';
				$company_fax = isset($check["FAX_PHONE"]) ? $check["FAX_PHONE"] : '';
				
			} else {
				$company_addr1 = $this->setting->get( 'company', 'ADDRESS_1' );
				$company_addr2 = $this->setting->get( 'company', 'ADDRESS_2' );
				$company_city = $this->setting->get( 'company', 'CITY' );
				$company_state = $this->setting->get( 'company', 'STATE' );
				$company_zip = $this->setting->get( 'company', 'ZIP' );
				$company_country = $this->setting->get( 'company', 'COUNTRY' );
				$company_phone = $this->setting->get( 'company', 'BUSINESS_PHONE' );
				$company_fax = $this->setting->get( 'company', 'FAX_PHONE' );
			}
	    } else {
			$company_addr1 = $this->setting->get( 'company', 'ADDRESS_1' );
			$company_addr2 = $this->setting->get( 'company', 'ADDRESS_2' );
			$company_city = $this->setting->get( 'company', 'CITY' );
			$company_state = $this->setting->get( 'company', 'STATE' );
			$company_zip = $this->setting->get( 'company', 'ZIP' );
			$company_country = $this->setting->get( 'company', 'COUNTRY' );
			$company_phone = $this->setting->get( 'company', 'BUSINESS_PHONE' );
			$company_fax = $this->setting->get( 'company', 'FAX_PHONE' );
	    }
		
		$company_addr = $company_addr1.'<br>'.
			($company_addr2 <> '' ? $company_addr2.'<br>' : '').
			$company_city.', '.$company_state.', '.$company_zip.
			($company_country <> '' ? ', '.$company_country : '');
			//.'<br>'.
			//($company_phone <> '' ? 'Phone: '.$company_phone.'<br>' : '').
			//($company_fax <> '' ? 'Fax: '.$company_fax.'<br>' : '');


	    return $company_addr;
    }
    
    //! Get the company for a shipment
    public function shipment_company( $shipment ) {
	    $result = false;
	    $check = $this->database->get_one_row( "SELECT SHIPMENT_COMPANY( $shipment ) AS COMPANY");
	    if( is_array($check) && isset($check["COMPANY"]))
	    	$result = $check["COMPANY"];
	    return $result;
    }

    //! Get the company for a load
    public function load_company( $load ) {
	    $result = false;
	    $check = $this->database->get_one_row( "SELECT LOAD_COMPANY( $load ) AS COMPANY");
	    if( is_array($check) && isset($check["COMPANY"]))
	    	$result = $check["COMPANY"];
	    return $result;
    }
    
    //! Get the prefix for an invoice/driver/carrier
    public function prefix( $type, $id ) {
	    $result = '';
	    if( $this->multi_company ) {
		    if( $type == 'invoice' ) {
		    	$check = $this->fetch_rows( "COMPANY_CODE = SHIPMENT_COMPANY( $id )",
			    	"INVOICE_PREFIX AS PREFIX");
		    } else if( $type == 'driver' ) {
		    	$check = $this->fetch_rows( "COMPANY_CODE = LOAD_COMPANY( $id )",
			    	"DRIVER_BILL_PREFIX AS PREFIX");
		    } else if( $type == 'carrier' ) {
		    	$check = $this->fetch_rows( "COMPANY_CODE = LOAD_COMPANY( $id )",
			    	"CARRIER_BILL_PREFIX AS PREFIX");
		    }
		    if( is_array($check) && count($check) == 1 && isset($check[0]["PREFIX"]))
		    	$result = $check[0]["PREFIX"];
	    } else {
		    if( $type == 'invoice' ) {
			    $result = $this->invoice_prefix;
		    } else if( $type == 'driver' ) {
			    $result = $this->driver_bill_prefix;
		    } else if( $type == 'carrier' ) {
			    $result = $this->bill_prefix;
		    }
	    }
	    return $result;
    }

}

//! Form Specifications - For use with sts_form

$sts_form_addcompany_form = array(	//! $sts_form_addcompany_form
	'title' => '<img src="images/company_icon.png" alt="company_icon" height="24"> Add company',
	'action' => 'exp_addcompany.php',
	//'actionextras' => 'disabled',
	'cancel' => 'exp_listcompany.php',
	'name' => 'addcompany',
	'okbutton' => 'Save Changes',
	'saveadd' => 'Add Another',
	'cancelbutton' => 'Cancel',
		'layout' => '
	<div class="form-group">
		<div class="col-sm-6">
			<div class="form-group tighter">
				<label for="COMPANY_NAME" class="col-sm-4 control-label">#COMPANY_NAME#</label>
				<div class="col-sm-8">
					%COMPANY_NAME%
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
				<label for="BUSINESS_PHONE" class="col-sm-4 control-label">#BUSINESS_PHONE#</label>
				<div class="col-sm-8">
					%BUSINESS_PHONE%
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
		</div>

		<div class="col-sm-6">
			<div class="form-group tighter">
				<label for="CONTACT_NAME" class="col-sm-4 control-label">#CONTACT_NAME#</label>
				<div class="col-sm-8">
					%CONTACT_NAME%
				</div>
			</div>
			<div class="form-group tighter">
				<label for="DUNS_ID" class="col-sm-4 control-label">#DUNS_ID#</label>
				<div class="col-sm-8">
					%DUNS_ID%
				</div>
			</div>
			<div class="form-group tighter">
				<label for="FED_ID_NUM" class="col-sm-4 control-label">#FED_ID_NUM#</label>
				<div class="col-sm-8">
					%FED_ID_NUM%
				</div>
			</div>
			<div class="form-group tighter">
				<label for="WEB_URL" class="col-sm-4 control-label">#WEB_URL#</label>
				<div class="col-sm-8">
					%WEB_URL%
				</div>
			</div>
			<div class="form-group tighter">
				<label for="HOME_CURRENCY" class="col-sm-4 control-label">#HOME_CURRENCY#</label>
				<div class="col-sm-8">
					%HOME_CURRENCY%
				</div>
			</div>
			<div class="form-group tighter">
				<label for="IFTA_BASE_JURISDICTION" class="col-sm-4 control-label">#IFTA_BASE_JURISDICTION#</label>
				<div class="col-sm-8">
					%IFTA_BASE_JURISDICTION%
				</div>
			</div>
			<div class="form-group tighter">
				<label for="INVOICE_PREFIX" class="col-sm-4 control-label">#INVOICE_PREFIX#</label>
				<div class="col-sm-8">
					%INVOICE_PREFIX%
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
			<!-- SAGE50_1 -->
			<div class="well well-sm">
			<div class="form-group tighter">
				<label for="SAGE50_CAD_AR" class="col-sm-4 control-label">#SAGE50_CAD_AR#</label>
				<div class="col-sm-8">
					%SAGE50_CAD_AR%
				</div>
			</div>
			<div class="form-group tighter">
				<label for="SAGE50_USD_AR" class="col-sm-4 control-label">#SAGE50_USD_AR#</label>
				<div class="col-sm-8">
					%SAGE50_USD_AR%
				</div>
			</div>
			<div class="form-group tighter">
				<label for="SAGE50_CAD_AP" class="col-sm-4 control-label">#SAGE50_CAD_AP#</label>
				<div class="col-sm-8">
					%SAGE50_CAD_AP%
				</div>
			</div>
			<div class="form-group tighter">
				<label for="SAGE50_USD_AP" class="col-sm-4 control-label">#SAGE50_USD_AP#</label>
				<div class="col-sm-8">
					%SAGE50_USD_AP%
				</div>
			</div>
			</div>
			<!-- SAGE50_2 -->

		</div>
	</div>
	
	'
);

$sts_form_editcompany_form = array( //!$sts_form_editcompany_form
	'title' => '<img src="images/company_icon.png" alt="company_icon" height="24"> Edit company',
	'action' => 'exp_editcompany.php',
	//'actionextras' => 'disabled',
	'cancel' => 'exp_listcompany.php',
	'name' => 'editcompany',
	'okbutton' => 'Save Changes to company',
	'cancelbutton' => 'Back to companies',
		'layout' => '
		%COMPANY_CODE%
	<div class="form-group">
		<div class="col-sm-6">
			<div class="form-group tighter">
				<label for="COMPANY_NAME" class="col-sm-4 control-label">#COMPANY_NAME#</label>
				<div class="col-sm-8">
					%COMPANY_NAME%
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
				<label for="BUSINESS_PHONE" class="col-sm-4 control-label">#BUSINESS_PHONE#</label>
				<div class="col-sm-8">
					%BUSINESS_PHONE%
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
				<label for="DUNS_ID" class="col-sm-4 control-label">#DUNS_ID#</label>
				<div class="col-sm-8">
					%DUNS_ID%
				</div>
			</div>
		</div>

		<div class="col-sm-6">
			<div class="form-group tighter">
				<label for="FED_ID_NUM" class="col-sm-4 control-label">#FED_ID_NUM#</label>
				<div class="col-sm-8">
					%FED_ID_NUM%
				</div>
			</div>
			<div class="form-group tighter">
				<label for="WEB_URL" class="col-sm-4 control-label">#WEB_URL#</label>
				<div class="col-sm-8">
					%WEB_URL%
				</div>
			</div>
			<div class="form-group tighter">
				<label for="HOME_CURRENCY" class="col-sm-4 control-label">#HOME_CURRENCY#</label>
				<div class="col-sm-8">
					%HOME_CURRENCY%
				</div>
			</div>
			<div class="form-group tighter">
				<label for="IFTA_BASE_JURISDICTION" class="col-sm-4 control-label">#IFTA_BASE_JURISDICTION#</label>
				<div class="col-sm-8">
					%IFTA_BASE_JURISDICTION%
				</div>
			</div>
			<div class="form-group tighter">
				<label for="INVOICE_PREFIX" class="col-sm-4 control-label">#INVOICE_PREFIX#</label>
				<div class="col-sm-8">
					%INVOICE_PREFIX%
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
			<!-- SAGE50_1 -->
			<div class="well well-sm">
			<div class="form-group tighter">
				<label for="SAGE50_CAD_AR" class="col-sm-4 control-label">#SAGE50_CAD_AR#</label>
				<div class="col-sm-8">
					%SAGE50_CAD_AR%
				</div>
			</div>
			<div class="form-group tighter">
				<label for="SAGE50_USD_AR" class="col-sm-4 control-label">#SAGE50_USD_AR#</label>
				<div class="col-sm-8">
					%SAGE50_USD_AR%
				</div>
			</div>
			<div class="form-group tighter">
				<label for="SAGE50_CAD_AP" class="col-sm-4 control-label">#SAGE50_CAD_AP#</label>
				<div class="col-sm-8">
					%SAGE50_CAD_AP%
				</div>
			</div>
			<div class="form-group tighter">
				<label for="SAGE50_USD_AP" class="col-sm-4 control-label">#SAGE50_USD_AP#</label>
				<div class="col-sm-8">
					%SAGE50_USD_AP%
				</div>
			</div>
			</div>
			<!-- SAGE50_2 -->

		</div>
	</div>
	
	'
);

//! Field Specifications - For use with sts_form

$sts_form_add_company_fields = array(	//! $sts_form_add_company_fields
	'COMPANY_NAME' => array( 'label' => 'Name', 'format' => 'text' ),
	'ADDRESS_1' => array( 'label' => 'Address 1', 'format' => 'text' ),
	'ADDRESS_2' => array( 'label' => 'Address 2', 'format' => 'text' ),
	'CITY' => array( 'label' => 'City', 'format' => 'text' ),
	'STATE' => array( 'label' => 'State/Province', 'format' => 'state' ),
	'ZIP' => array( 'label' => 'Zip/Postcode', 'format' => 'zip' ),
	'COUNTRY' => array( 'label' => 'Country', 'format' => 'country' ),
	'CONTACT_NAME' => array( 'label' => 'Contact name', 'format' => 'text' ),
	'BUSINESS_PHONE' => array( 'label' => 'Phone', 'format' => 'text' ),
	'FAX_PHONE' => array( 'label' => 'Fax#', 'format' => 'text' ),
	'EMAIL' => array( 'label' => 'Email', 'format' => 'text' ),
	'DUNS_ID' => array( 'label' => 'DUNS#', 'format' => 'text' ),
	'FED_ID_NUM' => array( 'label' => 'Fed ID', 'format' => 'text' ),
	'WEB_URL' => array( 'label' => 'Web URL', 'format' => 'text' ),
	'HOME_CURRENCY' => array( 'label' => 'Home Currency', 'format' => 'enum' ),
	'IFTA_BASE_JURISDICTION' => array( 'label' => 'IFTA Base', 'format' => 'state' ),
	'INVOICE_PREFIX' => array( 'label' => 'Invoice Prefix', 'format' => 'text' ),
	'DRIVER_BILL_PREFIX' => array( 'label' => 'Driver Bill Prefix', 'format' => 'text' ),
	'CARRIER_BILL_PREFIX' => array( 'label' => 'Carrier Bill Prefix', 'format' => 'text' ),
	'SAGE50_CAD_AR' => array( 'label' => 'Sage CAD AR', 'format' => 'text' ),
	'SAGE50_USD_AR' => array( 'label' => 'Sage USD AR', 'format' => 'text' ),
	'SAGE50_CAD_AP' => array( 'label' => 'Sage CAD AP', 'format' => 'text' ),
	'SAGE50_USD_AP' => array( 'label' => 'Sage USD AP', 'format' => 'text' ),
);

$sts_form_edit_company_fields = array(	//! $sts_form_edit_company_fields
	'COMPANY_CODE' => array( 'format' => 'hidden' ),
	'COMPANY_NAME' => array( 'label' => 'Name', 'format' => 'text' ),
	'ADDRESS_1' => array( 'label' => 'Address 1', 'format' => 'text' ),
	'ADDRESS_2' => array( 'label' => 'Address 2', 'format' => 'text' ),
	'CITY' => array( 'label' => 'City', 'format' => 'text' ),
	'STATE' => array( 'label' => 'State/Province', 'format' => 'state' ),
	'ZIP' => array( 'label' => 'Zip/Postcode', 'format' => 'zip' ),
	'COUNTRY' => array( 'label' => 'Country', 'format' => 'country' ),
	'CONTACT_NAME' => array( 'label' => 'Contact name', 'format' => 'text' ),
	'BUSINESS_PHONE' => array( 'label' => 'Phone', 'format' => 'text' ),
	'FAX_PHONE' => array( 'label' => 'Fax#', 'format' => 'text' ),
	'EMAIL' => array( 'label' => 'Email', 'format' => 'text' ),
	'DUNS_ID' => array( 'label' => 'DUNS#', 'format' => 'text' ),
	'FED_ID_NUM' => array( 'label' => 'Fed ID', 'format' => 'text' ),
	'WEB_URL' => array( 'label' => 'Web URL', 'format' => 'text' ),
	'HOME_CURRENCY' => array( 'label' => 'Home Currency', 'format' => 'enum' ),
	'IFTA_BASE_JURISDICTION' => array( 'label' => 'IFTA Base', 'format' => 'state' ),
	'INVOICE_PREFIX' => array( 'label' => 'Invoice Prefix', 'format' => 'text' ),
	'DRIVER_BILL_PREFIX' => array( 'label' => 'Driver Bill Prefix', 'format' => 'text' ),
	'CARRIER_BILL_PREFIX' => array( 'label' => 'Carrier Bill Prefix', 'format' => 'text' ),
	'SAGE50_CAD_AR' => array( 'label' => 'Sage CAD AR', 'format' => 'text' ),
	'SAGE50_USD_AR' => array( 'label' => 'Sage USD AR', 'format' => 'text' ),
	'SAGE50_CAD_AP' => array( 'label' => 'Sage CAD AP', 'format' => 'text' ),
	'SAGE50_USD_AP' => array( 'label' => 'Sage USD AP', 'format' => 'text' ),
);

//! Layout Specifications - For use with sts_result

$sts_result_company_layout = array(
	'COMPANY_CODE' => array( 'format' => 'hidden' ),
	'COMPANY_NAME' => array( 'label' => 'Name', 'format' => 'text' ),
	'ADDRESS_1' => array( 'label' => 'Address', 'format' => 'text',
		'group' => array('ADDRESS_1', 'ADDRESS_2', 'CITY', 'STATE', 'ZIP', 'COUNTRY'),
		'glue' => ', ' ),

	'DUNS_ID' => array( 'label' => 'DUNS#', 'format' => 'text' ),
	'EMAIL' => array( 'label' => 'Email', 'format' => 'text' ),
	'FAX_PHONE' => array( 'label' => 'Fax#', 'format' => 'text' ),
	'FED_ID_NUM' => array( 'label' => 'Fed ID', 'format' => 'text' ),
	'WEB_URL' => array( 'label' => 'Web URL', 'format' => 'text' ),
	'HOME_CURRENCY' => array( 'label' => 'Currency', 'format' => 'text' ),
	'IFTA_BASE_JURISDICTION' => array( 'label' => 'IFTA Base', 'format' => 'text' ),
	'INVOICE_PREFIX' => array( 'label' => 'Prefixes', 'format' => 'text',
		'group' => array('INVOICE_PREFIX', 'DRIVER_BILL_PREFIX', 'CARRIER_BILL_PREFIX'),
		'glue' => ', ' ),

);

//! Edit/Delete Button Specifications - For use with sts_result

$sts_result_company_edit = array(
	'title' => '<img src="images/company_icon.png" alt="company_icon" height="24"> Companies',
	'sort' => 'COMPANY_NAME asc',
	'cancel' => 'index.php',
	'add' => 'exp_addcompany.php',
	//'actionextras' => 'disabled',
	'addbutton' => 'Add Company',
	'cancelbutton' => 'Back',
	'filters_html' => '<a class="btn btn-sm btn-success" href="exp_listcompany.php"><span class="glyphicon glyphicon-refresh"></span></a> <a class="btn btn-sm btn-default" href="exp_listoffice.php"><img src="images/company_icon.png" alt="company_icon" height="18"> Offices</a>',
	'rowbuttons' => array(
		array( 'url' => 'exp_editcompany.php?CODE=', 'key' => 'COMPANY_CODE', 'label' => 'COMPANY_NAME', 'tip' => 'Edit company ', 'icon' => 'glyphicon glyphicon-edit' ),
		array( 'url' => 'exp_deletecompany.php?CODE=', 'key' => 'COMPANY_CODE', 'label' => 'COMPANY_NAME', 'tip' => 'Delete company ', 'icon' => 'glyphicon glyphicon-trash', 'confirm' => 'yes', 'showif' => 'can_delete' )
	)
);


?>
