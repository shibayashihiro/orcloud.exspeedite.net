<?php
/**
 * File for class PCMWSEnumTimeZone
 * @package PCMWS
 * @subpackage Enumerations
 * @author WsdlToPhp Team <contact@wsdltophp.com>
 * @version 20150429-01
 * @date 2016-08-25
 */
/**
 * This class stands for PCMWSEnumTimeZone originally named TimeZone
 * Meta informations extracted from the WSDL
 * - from schema : {@link http://pcmiler.alk.com/APIs/SOAP/v1.0/Service.svc?xsd=xsd0}
 * @package PCMWS
 * @subpackage Enumerations
 * @author WsdlToPhp Team <contact@wsdltophp.com>
 * @version 20150429-01
 * @date 2016-08-25
 */
class PCMWSEnumTimeZone extends PCMWSWsdlClass
{
    /**
     * Constant for value 'Local'
     * @return string 'Local'
     */
    const VALUE_LOCAL = 'Local';
    /**
     * Constant for value 'System'
     * @return string 'System'
     */
    const VALUE_SYSTEM = 'System';
    /**
     * Constant for value 'Hawaii'
     * @return string 'Hawaii'
     */
    const VALUE_HAWAII = 'Hawaii';
    /**
     * Constant for value 'Alaska'
     * @return string 'Alaska'
     */
    const VALUE_ALASKA = 'Alaska';
    /**
     * Constant for value 'Pacific'
     * @return string 'Pacific'
     */
    const VALUE_PACIFIC = 'Pacific';
    /**
     * Constant for value 'Arizona'
     * @return string 'Arizona'
     */
    const VALUE_ARIZONA = 'Arizona';
    /**
     * Constant for value 'Mountain'
     * @return string 'Mountain'
     */
    const VALUE_MOUNTAIN = 'Mountain';
    /**
     * Constant for value 'Central'
     * @return string 'Central'
     */
    const VALUE_CENTRAL = 'Central';
    /**
     * Constant for value 'Eastern'
     * @return string 'Eastern'
     */
    const VALUE_EASTERN = 'Eastern';
    /**
     * Constant for value 'Atlantic'
     * @return string 'Atlantic'
     */
    const VALUE_ATLANTIC = 'Atlantic';
    /**
     * Constant for value 'Newfoundland'
     * @return string 'Newfoundland'
     */
    const VALUE_NEWFOUNDLAND = 'Newfoundland';
    /**
     * Constant for value 'GMT'
     * @return string 'GMT'
     */
    const VALUE_GMT = 'GMT';
    /**
     * Constant for value 'UTC'
     * @return string 'UTC'
     */
    const VALUE_UTC = 'UTC';
    /**
     * Return true if value is allowed
     * @uses PCMWSEnumTimeZone::VALUE_LOCAL
     * @uses PCMWSEnumTimeZone::VALUE_SYSTEM
     * @uses PCMWSEnumTimeZone::VALUE_HAWAII
     * @uses PCMWSEnumTimeZone::VALUE_ALASKA
     * @uses PCMWSEnumTimeZone::VALUE_PACIFIC
     * @uses PCMWSEnumTimeZone::VALUE_ARIZONA
     * @uses PCMWSEnumTimeZone::VALUE_MOUNTAIN
     * @uses PCMWSEnumTimeZone::VALUE_CENTRAL
     * @uses PCMWSEnumTimeZone::VALUE_EASTERN
     * @uses PCMWSEnumTimeZone::VALUE_ATLANTIC
     * @uses PCMWSEnumTimeZone::VALUE_NEWFOUNDLAND
     * @uses PCMWSEnumTimeZone::VALUE_GMT
     * @uses PCMWSEnumTimeZone::VALUE_UTC
     * @param mixed $_value value
     * @return bool true|false
     */
    public static function valueIsValid($_value)
    {
        return in_array($_value,array(PCMWSEnumTimeZone::VALUE_LOCAL,PCMWSEnumTimeZone::VALUE_SYSTEM,PCMWSEnumTimeZone::VALUE_HAWAII,PCMWSEnumTimeZone::VALUE_ALASKA,PCMWSEnumTimeZone::VALUE_PACIFIC,PCMWSEnumTimeZone::VALUE_ARIZONA,PCMWSEnumTimeZone::VALUE_MOUNTAIN,PCMWSEnumTimeZone::VALUE_CENTRAL,PCMWSEnumTimeZone::VALUE_EASTERN,PCMWSEnumTimeZone::VALUE_ATLANTIC,PCMWSEnumTimeZone::VALUE_NEWFOUNDLAND,PCMWSEnumTimeZone::VALUE_GMT,PCMWSEnumTimeZone::VALUE_UTC));
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
