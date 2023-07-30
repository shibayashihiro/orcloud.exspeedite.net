<?php
/**
 * File for class PCMWSEnumPOISearchType
 * @package PCMWS
 * @subpackage Enumerations
 * @author WsdlToPhp Team <contact@wsdltophp.com>
 * @version 20150429-01
 * @date 2016-08-25
 */
/**
 * This class stands for PCMWSEnumPOISearchType originally named POISearchType
 * Meta informations extracted from the WSDL
 * - from schema : {@link http://pcmiler.alk.com/APIs/SOAP/v1.0/Service.svc?xsd=xsd0}
 * @package PCMWS
 * @subpackage Enumerations
 * @author WsdlToPhp Team <contact@wsdltophp.com>
 * @version 20150429-01
 * @date 2016-08-25
 */
class PCMWSEnumPOISearchType extends PCMWSWsdlClass
{
    /**
     * Constant for value 'FuelStop'
     * @return string 'FuelStop'
     */
    const VALUE_FUELSTOP = 'FuelStop';
    /**
     * Constant for value 'HoS'
     * @return string 'HoS'
     */
    const VALUE_HOS = 'HoS';
    /**
     * Constant for value 'Generic'
     * @return string 'Generic'
     */
    const VALUE_GENERIC = 'Generic';
    /**
     * Return true if value is allowed
     * @uses PCMWSEnumPOISearchType::VALUE_FUELSTOP
     * @uses PCMWSEnumPOISearchType::VALUE_HOS
     * @uses PCMWSEnumPOISearchType::VALUE_GENERIC
     * @param mixed $_value value
     * @return bool true|false
     */
    public static function valueIsValid($_value)
    {
        return in_array($_value,array(PCMWSEnumPOISearchType::VALUE_FUELSTOP,PCMWSEnumPOISearchType::VALUE_HOS,PCMWSEnumPOISearchType::VALUE_GENERIC));
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
