<?php
/**
 * File for class PCMWSEnumPostCodeType
 * @package PCMWS
 * @subpackage Enumerations
 * @author WsdlToPhp Team <contact@wsdltophp.com>
 * @version 20150429-01
 * @date 2016-08-25
 */
/**
 * This class stands for PCMWSEnumPostCodeType originally named PostCodeType
 * Meta informations extracted from the WSDL
 * - from schema : {@link http://pcmiler.alk.com/APIs/SOAP/v1.0/Service.svc?xsd=xsd0}
 * @package PCMWS
 * @subpackage Enumerations
 * @author WsdlToPhp Team <contact@wsdltophp.com>
 * @version 20150429-01
 * @date 2016-08-25
 */
class PCMWSEnumPostCodeType extends PCMWSWsdlClass
{
    /**
     * Constant for value 'US'
     * @return string 'US'
     */
    const VALUE_US = 'US';
    /**
     * Constant for value 'Mexico'
     * @return string 'Mexico'
     */
    const VALUE_MEXICO = 'Mexico';
    /**
     * Constant for value 'Both'
     * @return string 'Both'
     */
    const VALUE_BOTH = 'Both';
    /**
     * Return true if value is allowed
     * @uses PCMWSEnumPostCodeType::VALUE_US
     * @uses PCMWSEnumPostCodeType::VALUE_MEXICO
     * @uses PCMWSEnumPostCodeType::VALUE_BOTH
     * @param mixed $_value value
     * @return bool true|false
     */
    public static function valueIsValid($_value)
    {
        return in_array($_value,array(PCMWSEnumPostCodeType::VALUE_US,PCMWSEnumPostCodeType::VALUE_MEXICO,PCMWSEnumPostCodeType::VALUE_BOTH));
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
