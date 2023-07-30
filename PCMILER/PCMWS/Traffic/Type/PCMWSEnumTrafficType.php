<?php
/**
 * File for class PCMWSEnumTrafficType
 * @package PCMWS
 * @subpackage Enumerations
 * @author WsdlToPhp Team <contact@wsdltophp.com>
 * @version 20150429-01
 * @date 2016-08-25
 */
/**
 * This class stands for PCMWSEnumTrafficType originally named TrafficType
 * Meta informations extracted from the WSDL
 * - from schema : {@link http://pcmiler.alk.com/APIs/SOAP/v1.0/Service.svc?xsd=xsd0}
 * @package PCMWS
 * @subpackage Enumerations
 * @author WsdlToPhp Team <contact@wsdltophp.com>
 * @version 20150429-01
 * @date 2016-08-25
 */
class PCMWSEnumTrafficType extends PCMWSWsdlClass
{
    /**
     * Constant for value 'Congestion'
     * @return string 'Congestion'
     */
    const VALUE_CONGESTION = 'Congestion';
    /**
     * Constant for value 'RoadSpeed'
     * @return string 'RoadSpeed'
     */
    const VALUE_ROADSPEED = 'RoadSpeed';
    /**
     * Constant for value 'Neither'
     * @return string 'Neither'
     */
    const VALUE_NEITHER = 'Neither';
    /**
     * Return true if value is allowed
     * @uses PCMWSEnumTrafficType::VALUE_CONGESTION
     * @uses PCMWSEnumTrafficType::VALUE_ROADSPEED
     * @uses PCMWSEnumTrafficType::VALUE_NEITHER
     * @param mixed $_value value
     * @return bool true|false
     */
    public static function valueIsValid($_value)
    {
        return in_array($_value,array(PCMWSEnumTrafficType::VALUE_CONGESTION,PCMWSEnumTrafficType::VALUE_ROADSPEED,PCMWSEnumTrafficType::VALUE_NEITHER));
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
