<?php
/**
 * File for class PCMWSEnumWeatherAlertCertainty
 * @package PCMWS
 * @subpackage Enumerations
 * @author WsdlToPhp Team <contact@wsdltophp.com>
 * @version 20150429-01
 * @date 2016-08-25
 */
/**
 * This class stands for PCMWSEnumWeatherAlertCertainty originally named WeatherAlertCertainty
 * Meta informations extracted from the WSDL
 * - from schema : {@link http://pcmiler.alk.com/APIs/SOAP/v1.0/Service.svc?xsd=xsd0}
 * @package PCMWS
 * @subpackage Enumerations
 * @author WsdlToPhp Team <contact@wsdltophp.com>
 * @version 20150429-01
 * @date 2016-08-25
 */
class PCMWSEnumWeatherAlertCertainty extends PCMWSWsdlClass
{
    /**
     * Constant for value 'Observed'
     * @return string 'Observed'
     */
    const VALUE_OBSERVED = 'Observed';
    /**
     * Constant for value 'Likely'
     * @return string 'Likely'
     */
    const VALUE_LIKELY = 'Likely';
    /**
     * Constant for value 'Possible'
     * @return string 'Possible'
     */
    const VALUE_POSSIBLE = 'Possible';
    /**
     * Constant for value 'Unlikely'
     * @return string 'Unlikely'
     */
    const VALUE_UNLIKELY = 'Unlikely';
    /**
     * Constant for value 'Unknown'
     * @return string 'Unknown'
     */
    const VALUE_UNKNOWN = 'Unknown';
    /**
     * Return true if value is allowed
     * @uses PCMWSEnumWeatherAlertCertainty::VALUE_OBSERVED
     * @uses PCMWSEnumWeatherAlertCertainty::VALUE_LIKELY
     * @uses PCMWSEnumWeatherAlertCertainty::VALUE_POSSIBLE
     * @uses PCMWSEnumWeatherAlertCertainty::VALUE_UNLIKELY
     * @uses PCMWSEnumWeatherAlertCertainty::VALUE_UNKNOWN
     * @param mixed $_value value
     * @return bool true|false
     */
    public static function valueIsValid($_value)
    {
        return in_array($_value,array(PCMWSEnumWeatherAlertCertainty::VALUE_OBSERVED,PCMWSEnumWeatherAlertCertainty::VALUE_LIKELY,PCMWSEnumWeatherAlertCertainty::VALUE_POSSIBLE,PCMWSEnumWeatherAlertCertainty::VALUE_UNLIKELY,PCMWSEnumWeatherAlertCertainty::VALUE_UNKNOWN));
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
