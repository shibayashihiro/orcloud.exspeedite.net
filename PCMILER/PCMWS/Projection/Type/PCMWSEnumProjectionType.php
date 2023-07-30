<?php
/**
 * File for class PCMWSEnumProjectionType
 * @package PCMWS
 * @subpackage Enumerations
 * @author WsdlToPhp Team <contact@wsdltophp.com>
 * @version 20150429-01
 * @date 2016-08-25
 */
/**
 * This class stands for PCMWSEnumProjectionType originally named ProjectionType
 * Meta informations extracted from the WSDL
 * - from schema : {@link http://pcmiler.alk.com/APIs/SOAP/v1.0/Service.svc?xsd=xsd0}
 * @package PCMWS
 * @subpackage Enumerations
 * @author WsdlToPhp Team <contact@wsdltophp.com>
 * @version 20150429-01
 * @date 2016-08-25
 */
class PCMWSEnumProjectionType extends PCMWSWsdlClass
{
    /**
     * Constant for value 'Default'
     * @return string 'Default'
     */
    const VALUE_DEFAULT = 'Default';
    /**
     * Constant for value 'FixedLat'
     * @return string 'FixedLat'
     */
    const VALUE_FIXEDLAT = 'FixedLat';
    /**
     * Constant for value 'Mercator'
     * @return string 'Mercator'
     */
    const VALUE_MERCATOR = 'Mercator';
    /**
     * Return true if value is allowed
     * @uses PCMWSEnumProjectionType::VALUE_DEFAULT
     * @uses PCMWSEnumProjectionType::VALUE_FIXEDLAT
     * @uses PCMWSEnumProjectionType::VALUE_MERCATOR
     * @param mixed $_value value
     * @return bool true|false
     */
    public static function valueIsValid($_value)
    {
        return in_array($_value,array(PCMWSEnumProjectionType::VALUE_DEFAULT,PCMWSEnumProjectionType::VALUE_FIXEDLAT,PCMWSEnumProjectionType::VALUE_MERCATOR));
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
