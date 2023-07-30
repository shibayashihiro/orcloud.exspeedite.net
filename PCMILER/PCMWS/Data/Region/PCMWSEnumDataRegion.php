<?php
/**
 * File for class PCMWSEnumDataRegion
 * @package PCMWS
 * @subpackage Enumerations
 * @author WsdlToPhp Team <contact@wsdltophp.com>
 * @version 20150429-01
 * @date 2016-08-25
 */
/**
 * This class stands for PCMWSEnumDataRegion originally named DataRegion
 * Meta informations extracted from the WSDL
 * - from schema : {@link http://pcmiler.alk.com/APIs/SOAP/v1.0/Service.svc?xsd=xsd0}
 * @package PCMWS
 * @subpackage Enumerations
 * @author WsdlToPhp Team <contact@wsdltophp.com>
 * @version 20150429-01
 * @date 2016-08-25
 */
class PCMWSEnumDataRegion extends PCMWSWsdlClass
{
    /**
     * Constant for value 'Unknown'
     * @return string 'Unknown'
     */
    const VALUE_UNKNOWN = 'Unknown';
    /**
     * Constant for value 'AF'
     * @return string 'AF'
     */
    const VALUE_AF = 'AF';
    /**
     * Constant for value 'AS'
     * @return string 'AS'
     */
    const VALUE_AS = 'AS';
    /**
     * Constant for value 'EU'
     * @return string 'EU'
     */
    const VALUE_EU = 'EU';
    /**
     * Constant for value 'NA'
     * @return string 'NA'
     */
    const VALUE_NA = 'NA';
    /**
     * Constant for value 'OC'
     * @return string 'OC'
     */
    const VALUE_OC = 'OC';
    /**
     * Constant for value 'SA'
     * @return string 'SA'
     */
    const VALUE_SA = 'SA';
    /**
     * Constant for value 'ME'
     * @return string 'ME'
     */
    const VALUE_ME = 'ME';
    /**
     * Return true if value is allowed
     * @uses PCMWSEnumDataRegion::VALUE_UNKNOWN
     * @uses PCMWSEnumDataRegion::VALUE_AF
     * @uses PCMWSEnumDataRegion::VALUE_AS
     * @uses PCMWSEnumDataRegion::VALUE_EU
     * @uses PCMWSEnumDataRegion::VALUE_NA
     * @uses PCMWSEnumDataRegion::VALUE_OC
     * @uses PCMWSEnumDataRegion::VALUE_SA
     * @uses PCMWSEnumDataRegion::VALUE_ME
     * @param mixed $_value value
     * @return bool true|false
     */
    public static function valueIsValid($_value)
    {
        return in_array($_value,array(PCMWSEnumDataRegion::VALUE_UNKNOWN,PCMWSEnumDataRegion::VALUE_AF,PCMWSEnumDataRegion::VALUE_AS,PCMWSEnumDataRegion::VALUE_EU,PCMWSEnumDataRegion::VALUE_NA,PCMWSEnumDataRegion::VALUE_OC,PCMWSEnumDataRegion::VALUE_SA,PCMWSEnumDataRegion::VALUE_ME));
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
