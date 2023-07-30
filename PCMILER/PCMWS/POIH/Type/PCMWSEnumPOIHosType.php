<?php
/**
 * File for class PCMWSEnumPOIHosType
 * @package PCMWS
 * @subpackage Enumerations
 * @author WsdlToPhp Team <contact@wsdltophp.com>
 * @version 20150429-01
 * @date 2016-08-25
 */
/**
 * This class stands for PCMWSEnumPOIHosType originally named POIHosType
 * Meta informations extracted from the WSDL
 * - from schema : {@link http://pcmiler.alk.com/APIs/SOAP/v1.0/Service.svc?xsd=xsd0}
 * @package PCMWS
 * @subpackage Enumerations
 * @author WsdlToPhp Team <contact@wsdltophp.com>
 * @version 20150429-01
 * @date 2016-08-25
 */
class PCMWSEnumPOIHosType extends PCMWSWsdlClass
{
    /**
     * Constant for value 'All'
     * @return string 'All'
     */
    const VALUE_ALL = 'All';
    /**
     * Constant for value 'TruckServiceHoS'
     * @return string 'TruckServiceHoS'
     */
    const VALUE_TRUCKSERVICEHOS = 'TruckServiceHoS';
    /**
     * Constant for value 'RestAreaHoS'
     * @return string 'RestAreaHoS'
     */
    const VALUE_RESTAREAHOS = 'RestAreaHoS';
    /**
     * Return true if value is allowed
     * @uses PCMWSEnumPOIHosType::VALUE_ALL
     * @uses PCMWSEnumPOIHosType::VALUE_TRUCKSERVICEHOS
     * @uses PCMWSEnumPOIHosType::VALUE_RESTAREAHOS
     * @param mixed $_value value
     * @return bool true|false
     */
    public static function valueIsValid($_value)
    {
        return in_array($_value,array(PCMWSEnumPOIHosType::VALUE_ALL,PCMWSEnumPOIHosType::VALUE_TRUCKSERVICEHOS,PCMWSEnumPOIHosType::VALUE_RESTAREAHOS));
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
