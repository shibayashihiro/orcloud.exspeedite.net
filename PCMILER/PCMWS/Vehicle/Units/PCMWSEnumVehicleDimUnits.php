<?php
/**
 * File for class PCMWSEnumVehicleDimUnits
 * @package PCMWS
 * @subpackage Enumerations
 * @author WsdlToPhp Team <contact@wsdltophp.com>
 * @version 20150429-01
 * @date 2016-08-25
 */
/**
 * This class stands for PCMWSEnumVehicleDimUnits originally named VehicleDimUnits
 * Meta informations extracted from the WSDL
 * - from schema : {@link http://pcmiler.alk.com/APIs/SOAP/v1.0/Service.svc?xsd=xsd0}
 * @package PCMWS
 * @subpackage Enumerations
 * @author WsdlToPhp Team <contact@wsdltophp.com>
 * @version 20150429-01
 * @date 2016-08-25
 */
class PCMWSEnumVehicleDimUnits extends PCMWSWsdlClass
{
    /**
     * Constant for value 'English'
     * @return string 'English'
     */
    const VALUE_ENGLISH = 'English';
    /**
     * Constant for value 'Metric'
     * @return string 'Metric'
     */
    const VALUE_METRIC = 'Metric';
    /**
     * Return true if value is allowed
     * @uses PCMWSEnumVehicleDimUnits::VALUE_ENGLISH
     * @uses PCMWSEnumVehicleDimUnits::VALUE_METRIC
     * @param mixed $_value value
     * @return bool true|false
     */
    public static function valueIsValid($_value)
    {
        return in_array($_value,array(PCMWSEnumVehicleDimUnits::VALUE_ENGLISH,PCMWSEnumVehicleDimUnits::VALUE_METRIC));
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
