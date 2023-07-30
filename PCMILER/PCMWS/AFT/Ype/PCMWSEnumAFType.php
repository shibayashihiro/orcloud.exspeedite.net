<?php
/**
 * File for class PCMWSEnumAFType
 * @package PCMWS
 * @subpackage Enumerations
 * @author WsdlToPhp Team <contact@wsdltophp.com>
 * @version 20150429-01
 * @date 2016-08-25
 */
/**
 * This class stands for PCMWSEnumAFType originally named AFType
 * Meta informations extracted from the WSDL
 * - from schema : {@link http://pcmiler.alk.com/APIs/SOAP/v1.0/Service.svc?xsd=xsd0}
 * @package PCMWS
 * @subpackage Enumerations
 * @author WsdlToPhp Team <contact@wsdltophp.com>
 * @version 20150429-01
 * @date 2016-08-25
 */
class PCMWSEnumAFType extends PCMWSWsdlClass
{
    /**
     * Constant for value 'Avoid'
     * @return string 'Avoid'
     */
    const VALUE_AVOID = 'Avoid';
    /**
     * Constant for value 'Favor'
     * @return string 'Favor'
     */
    const VALUE_FAVOR = 'Favor';
    /**
     * Return true if value is allowed
     * @uses PCMWSEnumAFType::VALUE_AVOID
     * @uses PCMWSEnumAFType::VALUE_FAVOR
     * @param mixed $_value value
     * @return bool true|false
     */
    public static function valueIsValid($_value)
    {
        return in_array($_value,array(PCMWSEnumAFType::VALUE_AVOID,PCMWSEnumAFType::VALUE_FAVOR));
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
