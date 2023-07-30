<?php
/**
 * File for class PCMWSEnumWeatherAlertSeverity
 * @package PCMWS
 * @subpackage Enumerations
 * @author WsdlToPhp Team <contact@wsdltophp.com>
 * @version 20150429-01
 * @date 2016-08-25
 */
/**
 * This class stands for PCMWSEnumWeatherAlertSeverity originally named WeatherAlertSeverity
 * Meta informations extracted from the WSDL
 * - from schema : {@link http://pcmiler.alk.com/APIs/SOAP/v1.0/Service.svc?xsd=xsd0}
 * @package PCMWS
 * @subpackage Enumerations
 * @author WsdlToPhp Team <contact@wsdltophp.com>
 * @version 20150429-01
 * @date 2016-08-25
 */
class PCMWSEnumWeatherAlertSeverity extends PCMWSWsdlClass
{
    /**
     * Constant for value 'Extreme'
     * @return string 'Extreme'
     */
    const VALUE_EXTREME = 'Extreme';
    /**
     * Constant for value 'Severe'
     * @return string 'Severe'
     */
    const VALUE_SEVERE = 'Severe';
    /**
     * Constant for value 'Moderate'
     * @return string 'Moderate'
     */
    const VALUE_MODERATE = 'Moderate';
    /**
     * Constant for value 'Minor'
     * @return string 'Minor'
     */
    const VALUE_MINOR = 'Minor';
    /**
     * Constant for value 'Unknown'
     * @return string 'Unknown'
     */
    const VALUE_UNKNOWN = 'Unknown';
    /**
     * Return true if value is allowed
     * @uses PCMWSEnumWeatherAlertSeverity::VALUE_EXTREME
     * @uses PCMWSEnumWeatherAlertSeverity::VALUE_SEVERE
     * @uses PCMWSEnumWeatherAlertSeverity::VALUE_MODERATE
     * @uses PCMWSEnumWeatherAlertSeverity::VALUE_MINOR
     * @uses PCMWSEnumWeatherAlertSeverity::VALUE_UNKNOWN
     * @param mixed $_value value
     * @return bool true|false
     */
    public static function valueIsValid($_value)
    {
        return in_array($_value,array(PCMWSEnumWeatherAlertSeverity::VALUE_EXTREME,PCMWSEnumWeatherAlertSeverity::VALUE_SEVERE,PCMWSEnumWeatherAlertSeverity::VALUE_MODERATE,PCMWSEnumWeatherAlertSeverity::VALUE_MINOR,PCMWSEnumWeatherAlertSeverity::VALUE_UNKNOWN));
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
