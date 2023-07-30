<?php
/**
 * File for class PCMWSEnumGeocodeCitySearchFilter
 * @package PCMWS
 * @subpackage Enumerations
 * @author WsdlToPhp Team <contact@wsdltophp.com>
 * @version 20150429-01
 * @date 2016-08-25
 */
/**
 * This class stands for PCMWSEnumGeocodeCitySearchFilter originally named GeocodeCitySearchFilter
 * Meta informations extracted from the WSDL
 * - from schema : {@link http://pcmiler.alk.com/APIs/SOAP/v1.0/Service.svc?xsd=xsd0}
 * @package PCMWS
 * @subpackage Enumerations
 * @author WsdlToPhp Team <contact@wsdltophp.com>
 * @version 20150429-01
 * @date 2016-08-25
 */
class PCMWSEnumGeocodeCitySearchFilter extends PCMWSWsdlClass
{
    /**
     * Constant for value 'CitiesWithZips'
     * @return string 'CitiesWithZips'
     */
    const VALUE_CITIESWITHZIPS = 'CitiesWithZips';
    /**
     * Constant for value 'CityCentersOnly'
     * @return string 'CityCentersOnly'
     */
    const VALUE_CITYCENTERSONLY = 'CityCentersOnly';
    /**
     * Return true if value is allowed
     * @uses PCMWSEnumGeocodeCitySearchFilter::VALUE_CITIESWITHZIPS
     * @uses PCMWSEnumGeocodeCitySearchFilter::VALUE_CITYCENTERSONLY
     * @param mixed $_value value
     * @return bool true|false
     */
    public static function valueIsValid($_value)
    {
        return in_array($_value,array(PCMWSEnumGeocodeCitySearchFilter::VALUE_CITIESWITHZIPS,PCMWSEnumGeocodeCitySearchFilter::VALUE_CITYCENTERSONLY));
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
