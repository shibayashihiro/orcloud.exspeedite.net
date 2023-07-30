<?php
/**
 * File for class PCMWSEnumRouteOptimizeType
 * @package PCMWS
 * @subpackage Enumerations
 * @author WsdlToPhp Team <contact@wsdltophp.com>
 * @version 20150429-01
 * @date 2016-08-25
 */
/**
 * This class stands for PCMWSEnumRouteOptimizeType originally named RouteOptimizeType
 * Meta informations extracted from the WSDL
 * - from schema : {@link http://pcmiler.alk.com/APIs/SOAP/v1.0/Service.svc?xsd=xsd0}
 * @package PCMWS
 * @subpackage Enumerations
 * @author WsdlToPhp Team <contact@wsdltophp.com>
 * @version 20150429-01
 * @date 2016-08-25
 */
class PCMWSEnumRouteOptimizeType extends PCMWSWsdlClass
{
    /**
     * Constant for value 'None'
     * @return string 'None'
     */
    const VALUE_NONE = 'None';
    /**
     * Constant for value 'ThruAll'
     * @return string 'ThruAll'
     */
    const VALUE_THRUALL = 'ThruAll';
    /**
     * Constant for value 'DestinationFixed'
     * @return string 'DestinationFixed'
     */
    const VALUE_DESTINATIONFIXED = 'DestinationFixed';
    /**
     * Return true if value is allowed
     * @uses PCMWSEnumRouteOptimizeType::VALUE_NONE
     * @uses PCMWSEnumRouteOptimizeType::VALUE_THRUALL
     * @uses PCMWSEnumRouteOptimizeType::VALUE_DESTINATIONFIXED
     * @param mixed $_value value
     * @return bool true|false
     */
    public static function valueIsValid($_value)
    {
        return in_array($_value,array(PCMWSEnumRouteOptimizeType::VALUE_NONE,PCMWSEnumRouteOptimizeType::VALUE_THRUALL,PCMWSEnumRouteOptimizeType::VALUE_DESTINATIONFIXED));
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
