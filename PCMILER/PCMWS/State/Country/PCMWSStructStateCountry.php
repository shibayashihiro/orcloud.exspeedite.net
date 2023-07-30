<?php
/**
 * File for class PCMWSStructStateCountry
 * @package PCMWS
 * @subpackage Structs
 * @author WsdlToPhp Team <contact@wsdltophp.com>
 * @version 20150429-01
 * @date 2016-08-25
 */
/**
 * This class stands for PCMWSStructStateCountry originally named StateCountry
 * Meta informations extracted from the WSDL
 * - from schema : {@link http://pcmiler.alk.com/APIs/SOAP/v1.0/Service.svc?xsd=xsd0}
 * @package PCMWS
 * @subpackage Structs
 * @author WsdlToPhp Team <contact@wsdltophp.com>
 * @version 20150429-01
 * @date 2016-08-25
 */
class PCMWSStructStateCountry extends PCMWSWsdlClass
{
    /**
     * The StateAbbr
     * Meta informations extracted from the WSDL
     * - minOccurs : 0
     * - nillable : true
     * @var string
     */
    public $StateAbbr;
    /**
     * The StateName
     * Meta informations extracted from the WSDL
     * - minOccurs : 0
     * - nillable : true
     * @var string
     */
    public $StateName;
    /**
     * The CountryAbbr
     * Meta informations extracted from the WSDL
     * - minOccurs : 0
     * - nillable : true
     * @var string
     */
    public $CountryAbbr;
    /**
     * The CountryName
     * Meta informations extracted from the WSDL
     * - minOccurs : 0
     * - nillable : true
     * @var string
     */
    public $CountryName;
    /**
     * Constructor method for StateCountry
     * @see parent::__construct()
     * @param string $_stateAbbr
     * @param string $_stateName
     * @param string $_countryAbbr
     * @param string $_countryName
     * @return PCMWSStructStateCountry
     */
    public function __construct($_stateAbbr = NULL,$_stateName = NULL,$_countryAbbr = NULL,$_countryName = NULL)
    {
        parent::__construct(array('StateAbbr'=>$_stateAbbr,'StateName'=>$_stateName,'CountryAbbr'=>$_countryAbbr,'CountryName'=>$_countryName),false);
    }
    /**
     * Get StateAbbr value
     * @return string|null
     */
    public function getStateAbbr()
    {
        return $this->StateAbbr;
    }
    /**
     * Set StateAbbr value
     * @param string $_stateAbbr the StateAbbr
     * @return string
     */
    public function setStateAbbr($_stateAbbr)
    {
        return ($this->StateAbbr = $_stateAbbr);
    }
    /**
     * Get StateName value
     * @return string|null
     */
    public function getStateName()
    {
        return $this->StateName;
    }
    /**
     * Set StateName value
     * @param string $_stateName the StateName
     * @return string
     */
    public function setStateName($_stateName)
    {
        return ($this->StateName = $_stateName);
    }
    /**
     * Get CountryAbbr value
     * @return string|null
     */
    public function getCountryAbbr()
    {
        return $this->CountryAbbr;
    }
    /**
     * Set CountryAbbr value
     * @param string $_countryAbbr the CountryAbbr
     * @return string
     */
    public function setCountryAbbr($_countryAbbr)
    {
        return ($this->CountryAbbr = $_countryAbbr);
    }
    /**
     * Get CountryName value
     * @return string|null
     */
    public function getCountryName()
    {
        return $this->CountryName;
    }
    /**
     * Set CountryName value
     * @param string $_countryName the CountryName
     * @return string
     */
    public function setCountryName($_countryName)
    {
        return ($this->CountryName = $_countryName);
    }
    /**
     * Method called when an object has been exported with var_export() functions
     * It allows to return an object instantiated with the values
     * @see PCMWSWsdlClass::__set_state()
     * @uses PCMWSWsdlClass::__set_state()
     * @param array $_array the exported values
     * @return PCMWSStructStateCountry
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
