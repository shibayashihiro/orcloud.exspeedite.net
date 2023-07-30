<?php
/**
 * File for class PCMWSEnumAmPmType
 * @package PCMWS
 * @subpackage Enumerations
 * @author WsdlToPhp Team <contact@wsdltophp.com>
 * @version 20150429-01
 * @date 2016-08-25
 */
/**
 * This class stands for PCMWSEnumAmPmType originally named AmPmType
 * Meta informations extracted from the WSDL
 * - from schema : {@link http://pcmiler.alk.com/APIs/SOAP/v1.0/Service.svc?xsd=xsd0}
 * @package PCMWS
 * @subpackage Enumerations
 * @author WsdlToPhp Team <contact@wsdltophp.com>
 * @version 20150429-01
 * @date 2016-08-25
 */
class PCMWSEnumAmPmType extends PCMWSWsdlClass
{
    /**
     * Constant for value 'Military'
     * @return string 'Military'
     */
    const VALUE_MILITARY = 'Military';
    /**
     * Constant for value 'AM'
     * @return string 'AM'
     */
    const VALUE_AM = 'AM';
    /**
     * Constant for value 'PM'
     * @return string 'PM'
     */
    const VALUE_PM = 'PM';
    /**
     * Return true if value is allowed
     * @uses PCMWSEnumAmPmType::VALUE_MILITARY
     * @uses PCMWSEnumAmPmType::VALUE_AM
     * @uses PCMWSEnumAmPmType::VALUE_PM
     * @param mixed $_value value
     * @return bool true|false
     */
    public static function valueIsValid($_value)
    {
        return in_array($_value,array(PCMWSEnumAmPmType::VALUE_MILITARY,PCMWSEnumAmPmType::VALUE_AM,PCMWSEnumAmPmType::VALUE_PM));
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
