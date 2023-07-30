<?php
/**
 * File for class PCMWSEnumMapRegion
 * @package PCMWS
 * @subpackage Enumerations
 * @author WsdlToPhp Team <contact@wsdltophp.com>
 * @version 20150429-01
 * @date 2016-08-25
 */
/**
 * This class stands for PCMWSEnumMapRegion originally named MapRegion
 * Meta informations extracted from the WSDL
 * - from schema : {@link http://pcmiler.alk.com/APIs/SOAP/v1.0/Service.svc?xsd=xsd0}
 * @package PCMWS
 * @subpackage Enumerations
 * @author WsdlToPhp Team <contact@wsdltophp.com>
 * @version 20150429-01
 * @date 2016-08-25
 */
class PCMWSEnumMapRegion extends PCMWSWsdlClass
{
    /**
     * Constant for value 'NA'
     * @return string 'NA'
     */
    const VALUE_NA = 'NA';
    /**
     * Constant for value 'Bermuda'
     * @return string 'Bermuda'
     */
    const VALUE_BERMUDA = 'Bermuda';
    /**
     * Constant for value 'Canada'
     * @return string 'Canada'
     */
    const VALUE_CANADA = 'Canada';
    /**
     * Constant for value 'Mexico'
     * @return string 'Mexico'
     */
    const VALUE_MEXICO = 'Mexico';
    /**
     * Constant for value 'PuertoRico'
     * @return string 'PuertoRico'
     */
    const VALUE_PUERTORICO = 'PuertoRico';
    /**
     * Constant for value 'US'
     * @return string 'US'
     */
    const VALUE_US = 'US';
    /**
     * Constant for value 'EU'
     * @return string 'EU'
     */
    const VALUE_EU = 'EU';
    /**
     * Constant for value 'OC'
     * @return string 'OC'
     */
    const VALUE_OC = 'OC';
    /**
     * Constant for value 'ME'
     * @return string 'ME'
     */
    const VALUE_ME = 'ME';
    /**
     * Constant for value 'AF'
     * @return string 'AF'
     */
    const VALUE_AF = 'AF';
    /**
     * Constant for value 'SA'
     * @return string 'SA'
     */
    const VALUE_SA = 'SA';
    /**
     * Constant for value 'AS'
     * @return string 'AS'
     */
    const VALUE_AS = 'AS';
    /**
     * Return true if value is allowed
     * @uses PCMWSEnumMapRegion::VALUE_NA
     * @uses PCMWSEnumMapRegion::VALUE_BERMUDA
     * @uses PCMWSEnumMapRegion::VALUE_CANADA
     * @uses PCMWSEnumMapRegion::VALUE_MEXICO
     * @uses PCMWSEnumMapRegion::VALUE_PUERTORICO
     * @uses PCMWSEnumMapRegion::VALUE_US
     * @uses PCMWSEnumMapRegion::VALUE_EU
     * @uses PCMWSEnumMapRegion::VALUE_OC
     * @uses PCMWSEnumMapRegion::VALUE_ME
     * @uses PCMWSEnumMapRegion::VALUE_AF
     * @uses PCMWSEnumMapRegion::VALUE_SA
     * @uses PCMWSEnumMapRegion::VALUE_AS
     * @param mixed $_value value
     * @return bool true|false
     */
    public static function valueIsValid($_value)
    {
        return in_array($_value,array(PCMWSEnumMapRegion::VALUE_NA,PCMWSEnumMapRegion::VALUE_BERMUDA,PCMWSEnumMapRegion::VALUE_CANADA,PCMWSEnumMapRegion::VALUE_MEXICO,PCMWSEnumMapRegion::VALUE_PUERTORICO,PCMWSEnumMapRegion::VALUE_US,PCMWSEnumMapRegion::VALUE_EU,PCMWSEnumMapRegion::VALUE_OC,PCMWSEnumMapRegion::VALUE_ME,PCMWSEnumMapRegion::VALUE_AF,PCMWSEnumMapRegion::VALUE_SA,PCMWSEnumMapRegion::VALUE_AS));
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
