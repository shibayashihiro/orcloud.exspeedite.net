<?php
/**
 * File for class PCMWSEnumSpeedLimitType
 * @package PCMWS
 * @subpackage Enumerations
 * @author WsdlToPhp Team <contact@wsdltophp.com>
 * @version 20150429-01
 * @date 2016-08-25
 */
/**
 * This class stands for PCMWSEnumSpeedLimitType originally named SpeedLimitType
 * Meta informations extracted from the WSDL
 * - from schema : {@link http://pcmiler.alk.com/APIs/SOAP/v1.0/Service.svc?xsd=xsd0}
 * @package PCMWS
 * @subpackage Enumerations
 * @author WsdlToPhp Team <contact@wsdltophp.com>
 * @version 20150429-01
 * @date 2016-08-25
 */
class PCMWSEnumSpeedLimitType extends PCMWSWsdlClass
{
    /**
     * Constant for value 'Default'
     * @return string 'Default'
     */
    const VALUE_DEFAULT = 'Default';
    /**
     * Constant for value 'Historic'
     * @return string 'Historic'
     */
    const VALUE_HISTORIC = 'Historic';
    /**
     * Constant for value 'SpeedGauge'
     * @return string 'SpeedGauge'
     */
    const VALUE_SPEEDGAUGE = 'SpeedGauge';
    /**
     * Constant for value 'Navteq'
     * @return string 'Navteq'
     */
    const VALUE_NAVTEQ = 'Navteq';
    /**
     * Return true if value is allowed
     * @uses PCMWSEnumSpeedLimitType::VALUE_DEFAULT
     * @uses PCMWSEnumSpeedLimitType::VALUE_HISTORIC
     * @uses PCMWSEnumSpeedLimitType::VALUE_SPEEDGAUGE
     * @uses PCMWSEnumSpeedLimitType::VALUE_NAVTEQ
     * @param mixed $_value value
     * @return bool true|false
     */
    public static function valueIsValid($_value)
    {
        return in_array($_value,array(PCMWSEnumSpeedLimitType::VALUE_DEFAULT,PCMWSEnumSpeedLimitType::VALUE_HISTORIC,PCMWSEnumSpeedLimitType::VALUE_SPEEDGAUGE,PCMWSEnumSpeedLimitType::VALUE_NAVTEQ));
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
