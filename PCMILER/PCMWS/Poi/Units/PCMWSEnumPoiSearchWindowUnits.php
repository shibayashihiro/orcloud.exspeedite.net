<?php
/**
 * File for class PCMWSEnumPoiSearchWindowUnits
 * @package PCMWS
 * @subpackage Enumerations
 * @author WsdlToPhp Team <contact@wsdltophp.com>
 * @version 20150429-01
 * @date 2016-08-25
 */
/**
 * This class stands for PCMWSEnumPoiSearchWindowUnits originally named PoiSearchWindowUnits
 * Meta informations extracted from the WSDL
 * - from schema : {@link http://pcmiler.alk.com/APIs/SOAP/v1.0/Service.svc?xsd=xsd0}
 * @package PCMWS
 * @subpackage Enumerations
 * @author WsdlToPhp Team <contact@wsdltophp.com>
 * @version 20150429-01
 * @date 2016-08-25
 */
class PCMWSEnumPoiSearchWindowUnits extends PCMWSWsdlClass
{
    /**
     * Constant for value 'Miles'
     * @return string 'Miles'
     */
    const VALUE_MILES = 'Miles';
    /**
     * Constant for value 'Minutes'
     * @return string 'Minutes'
     */
    const VALUE_MINUTES = 'Minutes';
    /**
     * Return true if value is allowed
     * @uses PCMWSEnumPoiSearchWindowUnits::VALUE_MILES
     * @uses PCMWSEnumPoiSearchWindowUnits::VALUE_MINUTES
     * @param mixed $_value value
     * @return bool true|false
     */
    public static function valueIsValid($_value)
    {
        return in_array($_value,array(PCMWSEnumPoiSearchWindowUnits::VALUE_MILES,PCMWSEnumPoiSearchWindowUnits::VALUE_MINUTES));
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
