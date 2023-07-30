<?php
/**
 * File for class PCMWSEnumDistanceUnits
 * @package PCMWS
 * @subpackage Enumerations
 * @author WsdlToPhp Team <contact@wsdltophp.com>
 * @version 20150429-01
 * @date 2016-08-25
 */
/**
 * This class stands for PCMWSEnumDistanceUnits originally named DistanceUnits
 * Meta informations extracted from the WSDL
 * - from schema : {@link http://pcmiler.alk.com/APIs/SOAP/v1.0/Service.svc?xsd=xsd0}
 * @package PCMWS
 * @subpackage Enumerations
 * @author WsdlToPhp Team <contact@wsdltophp.com>
 * @version 20150429-01
 * @date 2016-08-25
 */
class PCMWSEnumDistanceUnits extends PCMWSWsdlClass
{
    /**
     * Constant for value 'Miles'
     * @return string 'Miles'
     */
    const VALUE_MILES = 'Miles';
    /**
     * Constant for value 'Kilometers'
     * @return string 'Kilometers'
     */
    const VALUE_KILOMETERS = 'Kilometers';
    /**
     * Return true if value is allowed
     * @uses PCMWSEnumDistanceUnits::VALUE_MILES
     * @uses PCMWSEnumDistanceUnits::VALUE_KILOMETERS
     * @param mixed $_value value
     * @return bool true|false
     */
    public static function valueIsValid($_value)
    {
        return in_array($_value,array(PCMWSEnumDistanceUnits::VALUE_MILES,PCMWSEnumDistanceUnits::VALUE_KILOMETERS));
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
