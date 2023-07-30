<?php
/**
 * File for class PCMWSEnumWeatherAlertUrgency
 * @package PCMWS
 * @subpackage Enumerations
 * @author WsdlToPhp Team <contact@wsdltophp.com>
 * @version 20150429-01
 * @date 2016-08-25
 */
/**
 * This class stands for PCMWSEnumWeatherAlertUrgency originally named WeatherAlertUrgency
 * Meta informations extracted from the WSDL
 * - from schema : {@link http://pcmiler.alk.com/APIs/SOAP/v1.0/Service.svc?xsd=xsd0}
 * @package PCMWS
 * @subpackage Enumerations
 * @author WsdlToPhp Team <contact@wsdltophp.com>
 * @version 20150429-01
 * @date 2016-08-25
 */
class PCMWSEnumWeatherAlertUrgency extends PCMWSWsdlClass
{
    /**
     * Constant for value 'Immediate'
     * @return string 'Immediate'
     */
    const VALUE_IMMEDIATE = 'Immediate';
    /**
     * Constant for value 'Expected'
     * @return string 'Expected'
     */
    const VALUE_EXPECTED = 'Expected';
    /**
     * Constant for value 'Future'
     * @return string 'Future'
     */
    const VALUE_FUTURE = 'Future';
    /**
     * Constant for value 'Past'
     * @return string 'Past'
     */
    const VALUE_PAST = 'Past';
    /**
     * Constant for value 'Unknown'
     * @return string 'Unknown'
     */
    const VALUE_UNKNOWN = 'Unknown';
    /**
     * Return true if value is allowed
     * @uses PCMWSEnumWeatherAlertUrgency::VALUE_IMMEDIATE
     * @uses PCMWSEnumWeatherAlertUrgency::VALUE_EXPECTED
     * @uses PCMWSEnumWeatherAlertUrgency::VALUE_FUTURE
     * @uses PCMWSEnumWeatherAlertUrgency::VALUE_PAST
     * @uses PCMWSEnumWeatherAlertUrgency::VALUE_UNKNOWN
     * @param mixed $_value value
     * @return bool true|false
     */
    public static function valueIsValid($_value)
    {
        return in_array($_value,array(PCMWSEnumWeatherAlertUrgency::VALUE_IMMEDIATE,PCMWSEnumWeatherAlertUrgency::VALUE_EXPECTED,PCMWSEnumWeatherAlertUrgency::VALUE_FUTURE,PCMWSEnumWeatherAlertUrgency::VALUE_PAST,PCMWSEnumWeatherAlertUrgency::VALUE_UNKNOWN));
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
