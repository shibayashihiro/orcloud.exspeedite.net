<?php
/**
 * File for class PCMWSEnumOutOfRouteCompliance
 * @package PCMWS
 * @subpackage Enumerations
 * @author WsdlToPhp Team <contact@wsdltophp.com>
 * @version 20150429-01
 * @date 2016-08-25
 */
/**
 * This class stands for PCMWSEnumOutOfRouteCompliance originally named OutOfRouteCompliance
 * Meta informations extracted from the WSDL
 * - from schema : {@link http://pcmiler.alk.com/APIs/SOAP/v1.0/Service.svc?xsd=xsd0}
 * @package PCMWS
 * @subpackage Enumerations
 * @author WsdlToPhp Team <contact@wsdltophp.com>
 * @version 20150429-01
 * @date 2016-08-25
 */
class PCMWSEnumOutOfRouteCompliance extends PCMWSWsdlClass
{
    /**
     * Constant for value 'Strict'
     * @return string 'Strict'
     */
    const VALUE_STRICT = 'Strict';
    /**
     * Constant for value 'Moderate'
     * @return string 'Moderate'
     */
    const VALUE_MODERATE = 'Moderate';
    /**
     * Constant for value 'None'
     * @return string 'None'
     */
    const VALUE_NONE = 'None';
    /**
     * Return true if value is allowed
     * @uses PCMWSEnumOutOfRouteCompliance::VALUE_STRICT
     * @uses PCMWSEnumOutOfRouteCompliance::VALUE_MODERATE
     * @uses PCMWSEnumOutOfRouteCompliance::VALUE_NONE
     * @param mixed $_value value
     * @return bool true|false
     */
    public static function valueIsValid($_value)
    {
        return in_array($_value,array(PCMWSEnumOutOfRouteCompliance::VALUE_STRICT,PCMWSEnumOutOfRouteCompliance::VALUE_MODERATE,PCMWSEnumOutOfRouteCompliance::VALUE_NONE));
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
