<?php
/**
 * File for class PCMWSStructAddress
 * @package PCMWS
 * @subpackage Structs
 * @author WsdlToPhp Team <contact@wsdltophp.com>
 * @version 20150429-01
 * @date 2016-08-25
 */
/**
 * This class stands for PCMWSStructAddress originally named Address
 * Meta informations extracted from the WSDL
 * - from schema : {@link http://pcmiler.alk.com/APIs/SOAP/v1.0/Service.svc?xsd=xsd0}
 * @package PCMWS
 * @subpackage Structs
 * @author WsdlToPhp Team <contact@wsdltophp.com>
 * @version 20150429-01
 * @date 2016-08-25
 */
class PCMWSStructAddress extends PCMWSWsdlClass
{
    /**
     * The StreetAddress
     * Meta informations extracted from the WSDL
     * - minOccurs : 0
     * - nillable : true
     * @var string
     */
    public $StreetAddress;
    /**
     * The City
     * Meta informations extracted from the WSDL
     * - minOccurs : 0
     * - nillable : true
     * @var string
     */
    public $City;
    /**
     * The State
     * Meta informations extracted from the WSDL
     * - minOccurs : 0
     * - nillable : true
     * @var string
     */
    public $State;
    /**
     * The Zip
     * Meta informations extracted from the WSDL
     * - minOccurs : 0
     * - nillable : true
     * @var string
     */
    public $Zip;
    /**
     * The County
     * Meta informations extracted from the WSDL
     * - minOccurs : 0
     * - nillable : true
     * @var string
     */
    public $County;
    /**
     * The Country
     * Meta informations extracted from the WSDL
     * - minOccurs : 0
     * - nillable : true
     * @var string
     */
    public $Country;
    /**
     * The SPLC
     * Meta informations extracted from the WSDL
     * - minOccurs : 0
     * - nillable : true
     * @var string
     */
    public $SPLC;
    /**
     * The CountryPostalFilter
     * Meta informations extracted from the WSDL
     * - minOccurs : 0
     * @var PCMWSEnumPostCodeType
     */
    public $CountryPostalFilter;
    /**
     * The AbbreviationFormat
     * Meta informations extracted from the WSDL
     * - minOccurs : 0
     * @var PCMWSEnumCountryAbbreviationType
     */
    public $AbbreviationFormat;
    /**
     * Constructor method for Address
     * @see parent::__construct()
     * @param string $_streetAddress
     * @param string $_city
     * @param string $_state
     * @param string $_zip
     * @param string $_county
     * @param string $_country
     * @param string $_sPLC
     * @param PCMWSEnumPostCodeType $_countryPostalFilter
     * @param PCMWSEnumCountryAbbreviationType $_abbreviationFormat
     * @return PCMWSStructAddress
     */
    public function __construct($_streetAddress = NULL,$_city = NULL,$_state = NULL,$_zip = NULL,$_county = NULL,$_country = NULL,$_sPLC = NULL,$_countryPostalFilter = NULL,$_abbreviationFormat = NULL)
    {
        parent::__construct(array('StreetAddress'=>$_streetAddress,'City'=>$_city,'State'=>$_state,'Zip'=>$_zip,'County'=>$_county,'Country'=>$_country,'SPLC'=>$_sPLC,'CountryPostalFilter'=>$_countryPostalFilter,'AbbreviationFormat'=>$_abbreviationFormat),false);
    }
    /**
     * Get StreetAddress value
     * @return string|null
     */
    public function getStreetAddress()
    {
        return $this->StreetAddress;
    }
    /**
     * Set StreetAddress value
     * @param string $_streetAddress the StreetAddress
     * @return string
     */
    public function setStreetAddress($_streetAddress)
    {
        return ($this->StreetAddress = $_streetAddress);
    }
    /**
     * Get City value
     * @return string|null
     */
    public function getCity()
    {
        return $this->City;
    }
    /**
     * Set City value
     * @param string $_city the City
     * @return string
     */
    public function setCity($_city)
    {
        return ($this->City = $_city);
    }
    /**
     * Get State value
     * @return string|null
     */
    public function getState()
    {
        return $this->State;
    }
    /**
     * Set State value
     * @param string $_state the State
     * @return string
     */
    public function setState($_state)
    {
        return ($this->State = $_state);
    }
    /**
     * Get Zip value
     * @return string|null
     */
    public function getZip()
    {
        return $this->Zip;
    }
    /**
     * Set Zip value
     * @param string $_zip the Zip
     * @return string
     */
    public function setZip($_zip)
    {
        return ($this->Zip = $_zip);
    }
    /**
     * Get County value
     * @return string|null
     */
    public function getCounty()
    {
        return $this->County;
    }
    /**
     * Set County value
     * @param string $_county the County
     * @return string
     */
    public function setCounty($_county)
    {
        return ($this->County = $_county);
    }
    /**
     * Get Country value
     * @return string|null
     */
    public function getCountry()
    {
        return $this->Country;
    }
    /**
     * Set Country value
     * @param string $_country the Country
     * @return string
     */
    public function setCountry($_country)
    {
        return ($this->Country = $_country);
    }
    /**
     * Get SPLC value
     * @return string|null
     */
    public function getSPLC()
    {
        return $this->SPLC;
    }
    /**
     * Set SPLC value
     * @param string $_sPLC the SPLC
     * @return string
     */
    public function setSPLC($_sPLC)
    {
        return ($this->SPLC = $_sPLC);
    }
    /**
     * Get CountryPostalFilter value
     * @return PCMWSEnumPostCodeType|null
     */
    public function getCountryPostalFilter()
    {
        return $this->CountryPostalFilter;
    }
    /**
     * Set CountryPostalFilter value
     * @uses PCMWSEnumPostCodeType::valueIsValid()
     * @param PCMWSEnumPostCodeType $_countryPostalFilter the CountryPostalFilter
     * @return PCMWSEnumPostCodeType
     */
    public function setCountryPostalFilter($_countryPostalFilter)
    {
        if(!PCMWSEnumPostCodeType::valueIsValid($_countryPostalFilter))
        {
            return false;
        }
        return ($this->CountryPostalFilter = $_countryPostalFilter);
    }
    /**
     * Get AbbreviationFormat value
     * @return PCMWSEnumCountryAbbreviationType|null
     */
    public function getAbbreviationFormat()
    {
        return $this->AbbreviationFormat;
    }
    /**
     * Set AbbreviationFormat value
     * @uses PCMWSEnumCountryAbbreviationType::valueIsValid()
     * @param PCMWSEnumCountryAbbreviationType $_abbreviationFormat the AbbreviationFormat
     * @return PCMWSEnumCountryAbbreviationType
     */
    public function setAbbreviationFormat($_abbreviationFormat)
    {
        if(!PCMWSEnumCountryAbbreviationType::valueIsValid($_abbreviationFormat))
        {
            return false;
        }
        return ($this->AbbreviationFormat = $_abbreviationFormat);
    }
    /**
     * Method called when an object has been exported with var_export() functions
     * It allows to return an object instantiated with the values
     * @see PCMWSWsdlClass::__set_state()
     * @uses PCMWSWsdlClass::__set_state()
     * @param array $_array the exported values
     * @return PCMWSStructAddress
     */
    public static function __set_state(array $_array)
    {
	    $_array[] = __CLASS__;
        return parent::__set_state($_array);
    }
    /**
     * Method returning the class name
     * @return string __CLASS__
     */
    public function __toString()
    {
        return __CLASS__;
    }
}
