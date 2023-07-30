<?php
/**
 * File for class PCMWSEnumTrafficTime
 * @package PCMWS
 * @subpackage Enumerations
 * @author WsdlToPhp Team <contact@wsdltophp.com>
 * @version 20150429-01
 * @date 2016-08-25
 */
/**
 * This class stands for PCMWSEnumTrafficTime originally named TrafficTime
 * Meta informations extracted from the WSDL
 * - from schema : {@link http://pcmiler.alk.com/APIs/SOAP/v1.0/Service.svc?xsd=xsd0}
 * @package PCMWS
 * @subpackage Enumerations
 * @author WsdlToPhp Team <contact@wsdltophp.com>
 * @version 20150429-01
 * @date 2016-08-25
 */
class PCMWSEnumTrafficTime extends PCMWSWsdlClass
{
    /**
     * Constant for value 'Actual'
     * @return string 'Actual'
     */
    const VALUE_ACTUAL = 'Actual';
    /**
     * Constant for value 'Historic'
     * @return string 'Historic'
     */
    const VALUE_HISTORIC = 'Historic';
    /**
     * Constant for value 'Default'
     * @return string 'Default'
     */
    const VALUE_DEFAULT = 'Default';
    /**
     * Return true if value is allowed
     * @uses PCMWSEnumTrafficTime::VALUE_ACTUAL
     * @uses PCMWSEnumTrafficTime::VALUE_HISTORIC
     * @uses PCMWSEnumTrafficTime::VALUE_DEFAULT
     * @param mixed $_value value
     * @return bool true|false
     */
    public static function valueIsValid($_value)
    {
        return in_array($_value,array(PCMWSEnumTrafficTime::VALUE_ACTUAL,PCMWSEnumTrafficTime::VALUE_HISTORIC,PCMWSEnumTrafficTime::VALUE_DEFAULT));
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
