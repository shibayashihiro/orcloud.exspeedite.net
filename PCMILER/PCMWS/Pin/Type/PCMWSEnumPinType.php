<?php
/**
 * File for class PCMWSEnumPinType
 * @package PCMWS
 * @subpackage Enumerations
 * @author WsdlToPhp Team <contact@wsdltophp.com>
 * @version 20150429-01
 * @date 2016-08-25
 */
/**
 * This class stands for PCMWSEnumPinType originally named PinType
 * Meta informations extracted from the WSDL
 * - from schema : {@link http://pcmiler.alk.com/APIs/SOAP/v1.0/Service.svc?xsd=xsd0}
 * @package PCMWS
 * @subpackage Enumerations
 * @author WsdlToPhp Team <contact@wsdltophp.com>
 * @version 20150429-01
 * @date 2016-08-25
 */
class PCMWSEnumPinType extends PCMWSWsdlClass
{
    /**
     * Constant for value 'PDW_BMP'
     * @return string 'PDW_BMP'
     */
    const VALUE_PDW_BMP = 'PDW_BMP';
    /**
     * Constant for value 'PDW_CIRCLE'
     * @return string 'PDW_CIRCLE'
     */
    const VALUE_PDW_CIRCLE = 'PDW_CIRCLE';
    /**
     * Constant for value 'PDW_SQUARE'
     * @return string 'PDW_SQUARE'
     */
    const VALUE_PDW_SQUARE = 'PDW_SQUARE';
    /**
     * Constant for value 'PDW_DIAMOND'
     * @return string 'PDW_DIAMOND'
     */
    const VALUE_PDW_DIAMOND = 'PDW_DIAMOND';
    /**
     * Return true if value is allowed
     * @uses PCMWSEnumPinType::VALUE_PDW_BMP
     * @uses PCMWSEnumPinType::VALUE_PDW_CIRCLE
     * @uses PCMWSEnumPinType::VALUE_PDW_SQUARE
     * @uses PCMWSEnumPinType::VALUE_PDW_DIAMOND
     * @param mixed $_value value
     * @return bool true|false
     */
    public static function valueIsValid($_value)
    {
        return in_array($_value,array(PCMWSEnumPinType::VALUE_PDW_BMP,PCMWSEnumPinType::VALUE_PDW_CIRCLE,PCMWSEnumPinType::VALUE_PDW_SQUARE,PCMWSEnumPinType::VALUE_PDW_DIAMOND));
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
