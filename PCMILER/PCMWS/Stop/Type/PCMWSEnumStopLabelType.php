<?php
/**
 * File for class PCMWSEnumStopLabelType
 * @package PCMWS
 * @subpackage Enumerations
 * @author WsdlToPhp Team <contact@wsdltophp.com>
 * @version 20150429-01
 * @date 2016-08-25
 */
/**
 * This class stands for PCMWSEnumStopLabelType originally named StopLabelType
 * Meta informations extracted from the WSDL
 * - from schema : {@link http://pcmiler.alk.com/APIs/SOAP/v1.0/Service.svc?xsd=xsd0}
 * @package PCMWS
 * @subpackage Enumerations
 * @author WsdlToPhp Team <contact@wsdltophp.com>
 * @version 20150429-01
 * @date 2016-08-25
 */
class PCMWSEnumStopLabelType extends PCMWSWsdlClass
{
    /**
     * Constant for value 'Name'
     * @return string 'Name'
     */
    const VALUE_NAME = 'Name';
    /**
     * Constant for value 'Number'
     * @return string 'Number'
     */
    const VALUE_NUMBER = 'Number';
    /**
     * Constant for value 'Both'
     * @return string 'Both'
     */
    const VALUE_BOTH = 'Both';
    /**
     * Constant for value 'None'
     * @return string 'None'
     */
    const VALUE_NONE = 'None';
    /**
     * Return true if value is allowed
     * @uses PCMWSEnumStopLabelType::VALUE_NAME
     * @uses PCMWSEnumStopLabelType::VALUE_NUMBER
     * @uses PCMWSEnumStopLabelType::VALUE_BOTH
     * @uses PCMWSEnumStopLabelType::VALUE_NONE
     * @param mixed $_value value
     * @return bool true|false
     */
    public static function valueIsValid($_value)
    {
        return in_array($_value,array(PCMWSEnumStopLabelType::VALUE_NAME,PCMWSEnumStopLabelType::VALUE_NUMBER,PCMWSEnumStopLabelType::VALUE_BOTH,PCMWSEnumStopLabelType::VALUE_NONE));
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
