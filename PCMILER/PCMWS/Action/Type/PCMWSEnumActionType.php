<?php
/**
 * File for class PCMWSEnumActionType
 * @package PCMWS
 * @subpackage Enumerations
 * @author WsdlToPhp Team <contact@wsdltophp.com>
 * @version 20150429-01
 * @date 2016-08-25
 */
/**
 * This class stands for PCMWSEnumActionType originally named ActionType
 * Meta informations extracted from the WSDL
 * - from schema : {@link http://pcmiler.alk.com/APIs/SOAP/v1.0/Service.svc?xsd=xsd0}
 * @package PCMWS
 * @subpackage Enumerations
 * @author WsdlToPhp Team <contact@wsdltophp.com>
 * @version 20150429-01
 * @date 2016-08-25
 */
class PCMWSEnumActionType extends PCMWSWsdlClass
{
    /**
     * Constant for value 'Save'
     * @return string 'Save'
     */
    const VALUE_SAVE = 'Save';
    /**
     * Constant for value 'Delete'
     * @return string 'Delete'
     */
    const VALUE_DELETE = 'Delete';
    /**
     * Return true if value is allowed
     * @uses PCMWSEnumActionType::VALUE_SAVE
     * @uses PCMWSEnumActionType::VALUE_DELETE
     * @param mixed $_value value
     * @return bool true|false
     */
    public static function valueIsValid($_value)
    {
        return in_array($_value,array(PCMWSEnumActionType::VALUE_SAVE,PCMWSEnumActionType::VALUE_DELETE));
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
