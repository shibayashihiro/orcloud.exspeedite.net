<?php
/**
 * File for class PCMWSEnumCountryCodeFormat
 * @package PCMWS
 * @subpackage Enumerations
 * @author WsdlToPhp Team <contact@wsdltophp.com>
 * @version 20150429-01
 * @date 2016-08-25
 */
/**
 * This class stands for PCMWSEnumCountryCodeFormat originally named CountryCodeFormat
 * Meta informations extracted from the WSDL
 * - from schema : {@link http://pcmiler.alk.com/APIs/SOAP/v1.0/Service.svc?xsd=xsd0}
 * @package PCMWS
 * @subpackage Enumerations
 * @author WsdlToPhp Team <contact@wsdltophp.com>
 * @version 20150429-01
 * @date 2016-08-25
 */
class PCMWSEnumCountryCodeFormat extends PCMWSWsdlClass
{
    /**
     * Constant for value 'FIPS'
     * @return string 'FIPS'
     */
    const VALUE_FIPS = 'FIPS';
    /**
     * Constant for value 'ISO2'
     * @return string 'ISO2'
     */
    const VALUE_ISO2 = 'ISO2';
    /**
     * Constant for value 'ISO3'
     * @return string 'ISO3'
     */
    const VALUE_ISO3 = 'ISO3';
    /**
     * Constant for value 'GENC2'
     * @return string 'GENC2'
     */
    const VALUE_GENC2 = 'GENC2';
    /**
     * Constant for value 'GENC3'
     * @return string 'GENC3'
     */
    const VALUE_GENC3 = 'GENC3';
    /**
     * Return true if value is allowed
     * @uses PCMWSEnumCountryCodeFormat::VALUE_FIPS
     * @uses PCMWSEnumCountryCodeFormat::VALUE_ISO2
     * @uses PCMWSEnumCountryCodeFormat::VALUE_ISO3
     * @uses PCMWSEnumCountryCodeFormat::VALUE_GENC2
     * @uses PCMWSEnumCountryCodeFormat::VALUE_GENC3
     * @param mixed $_value value
     * @return bool true|false
     */
    public static function valueIsValid($_value)
    {
        return in_array($_value,array(PCMWSEnumCountryCodeFormat::VALUE_FIPS,PCMWSEnumCountryCodeFormat::VALUE_ISO2,PCMWSEnumCountryCodeFormat::VALUE_ISO3,PCMWSEnumCountryCodeFormat::VALUE_GENC2,PCMWSEnumCountryCodeFormat::VALUE_GENC3));
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
