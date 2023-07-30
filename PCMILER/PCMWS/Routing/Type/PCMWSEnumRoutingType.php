<?php
/**
 * File for class PCMWSEnumRoutingType
 * @package PCMWS
 * @subpackage Enumerations
 * @author WsdlToPhp Team <contact@wsdltophp.com>
 * @version 20150429-01
 * @date 2016-08-25
 */
/**
 * This class stands for PCMWSEnumRoutingType originally named RoutingType
 * Meta informations extracted from the WSDL
 * - from schema : {@link http://pcmiler.alk.com/APIs/SOAP/v1.0/Service.svc?xsd=xsd0}
 * @package PCMWS
 * @subpackage Enumerations
 * @author WsdlToPhp Team <contact@wsdltophp.com>
 * @version 20150429-01
 * @date 2016-08-25
 */
class PCMWSEnumRoutingType extends PCMWSWsdlClass
{
    /**
     * Constant for value 'Practical'
     * @return string 'Practical'
     */
    const VALUE_PRACTICAL = 'Practical';
    /**
     * Constant for value 'Shortest'
     * @return string 'Shortest'
     */
    const VALUE_SHORTEST = 'Shortest';
    /**
     * Constant for value 'Fastest'
     * @return string 'Fastest'
     */
    const VALUE_FASTEST = 'Fastest';
    /**
     * Return true if value is allowed
     * @uses PCMWSEnumRoutingType::VALUE_PRACTICAL
     * @uses PCMWSEnumRoutingType::VALUE_SHORTEST
     * @uses PCMWSEnumRoutingType::VALUE_FASTEST
     * @param mixed $_value value
     * @return bool true|false
     */
    public static function valueIsValid($_value)
    {
        return in_array($_value,array(PCMWSEnumRoutingType::VALUE_PRACTICAL,PCMWSEnumRoutingType::VALUE_SHORTEST,PCMWSEnumRoutingType::VALUE_FASTEST));
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
