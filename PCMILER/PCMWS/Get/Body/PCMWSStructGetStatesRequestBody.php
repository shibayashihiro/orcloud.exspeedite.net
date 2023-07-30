<?php
/**
 * File for class PCMWSStructGetStatesRequestBody
 * @package PCMWS
 * @subpackage Structs
 * @author WsdlToPhp Team <contact@wsdltophp.com>
 * @version 20150429-01
 * @date 2016-08-25
 */
/**
 * This class stands for PCMWSStructGetStatesRequestBody originally named GetStatesRequestBody
 * Meta informations extracted from the WSDL
 * - from schema : {@link http://pcmiler.alk.com/APIs/SOAP/v1.0/Service.svc?xsd=xsd0}
 * @package PCMWS
 * @subpackage Structs
 * @author WsdlToPhp Team <contact@wsdltophp.com>
 * @version 20150429-01
 * @date 2016-08-25
 */
class PCMWSStructGetStatesRequestBody extends PCMWSWsdlClass
{
    /**
     * The Format
     * Meta informations extracted from the WSDL
     * - minOccurs : 0
     * @var PCMWSEnumCountryCodeFormat
     */
    public $Format;
    /**
     * The Region
     * Meta informations extracted from the WSDL
     * - minOccurs : 0
     * @var PCMWSEnumDataRegion
     */
    public $Region;
    /**
     * The CountryOnly
     * Meta informations extracted from the WSDL
     * - minOccurs : 0
     * @var boolean
     */
    public $CountryOnly;
    /**
     * Constructor method for GetStatesRequestBody
     * @see parent::__construct()
     * @param PCMWSEnumCountryCodeFormat $_format
     * @param PCMWSEnumDataRegion $_region
     * @param boolean $_countryOnly
     * @return PCMWSStructGetStatesRequestBody
     */
    public function __construct($_format = NULL,$_region = NULL,$_countryOnly = NULL)
    {
        parent::__construct(array('Format'=>$_format,'Region'=>$_region,'CountryOnly'=>$_countryOnly),false);
    }
    /**
     * Get Format value
     * @return PCMWSEnumCountryCodeFormat|null
     */
    public function getFormat()
    {
        return $this->Format;
    }
    /**
     * Set Format value
     * @uses PCMWSEnumCountryCodeFormat::valueIsValid()
     * @param PCMWSEnumCountryCodeFormat $_format the Format
     * @return PCMWSEnumCountryCodeFormat
     */
    public function setFormat($_format)
    {
        if(!PCMWSEnumCountryCodeFormat::valueIsValid($_format))
        {
            return false;
        }
        return ($this->Format = $_format);
    }
    /**
     * Get Region value
     * @return PCMWSEnumDataRegion|null
     */
    public function getRegion()
    {
        return $this->Region;
    }
    /**
     * Set Region value
     * @uses PCMWSEnumDataRegion::valueIsValid()
     * @param PCMWSEnumDataRegion $_region the Region
     * @return PCMWSEnumDataRegion
     */
    public function setRegion($_region)
    {
        if(!PCMWSEnumDataRegion::valueIsValid($_region))
        {
            return false;
        }
        return ($this->Region = $_region);
    }
    /**
     * Get CountryOnly value
     * @return boolean|null
     */
    public function getCountryOnly()
    {
        return $this->CountryOnly;
    }
    /**
     * Set CountryOnly value
     * @param boolean $_countryOnly the CountryOnly
     * @return boolean
     */
    public function setCountryOnly($_countryOnly)
    {
        return ($this->CountryOnly = $_countryOnly);
    }
    /**
     * Method called when an object has been exported with var_export() functions
     * It allows to return an object instantiated with the values
     * @see PCMWSWsdlClass::__set_state()
     * @uses PCMWSWsdlClass::__set_state()
     * @param array $_array the exported values
     * @return PCMWSStructGetStatesRequestBody
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
