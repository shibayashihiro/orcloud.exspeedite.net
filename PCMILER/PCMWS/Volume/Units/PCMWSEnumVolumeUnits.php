<?php
/**
 * File for class PCMWSEnumVolumeUnits
 * @package PCMWS
 * @subpackage Enumerations
 * @author WsdlToPhp Team <contact@wsdltophp.com>
 * @version 20150429-01
 * @date 2016-08-25
 */
/**
 * This class stands for PCMWSEnumVolumeUnits originally named VolumeUnits
 * Meta informations extracted from the WSDL
 * - from schema : {@link http://pcmiler.alk.com/APIs/SOAP/v1.0/Service.svc?xsd=xsd0}
 * @package PCMWS
 * @subpackage Enumerations
 * @author WsdlToPhp Team <contact@wsdltophp.com>
 * @version 20150429-01
 * @date 2016-08-25
 */
class PCMWSEnumVolumeUnits extends PCMWSWsdlClass
{
    /**
     * Constant for value 'Gallons'
     * @return string 'Gallons'
     */
    const VALUE_GALLONS = 'Gallons';
    /**
     * Constant for value 'Liters'
     * @return string 'Liters'
     */
    const VALUE_LITERS = 'Liters';
    /**
     * Return true if value is allowed
     * @uses PCMWSEnumVolumeUnits::VALUE_GALLONS
     * @uses PCMWSEnumVolumeUnits::VALUE_LITERS
     * @param mixed $_value value
     * @return bool true|false
     */
    public static function valueIsValid($_value)
    {
        return in_array($_value,array(PCMWSEnumVolumeUnits::VALUE_GALLONS,PCMWSEnumVolumeUnits::VALUE_LITERS));
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
