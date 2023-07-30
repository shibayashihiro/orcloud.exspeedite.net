<?php
/**
 * File for class PCMWSEnumClassOverrideType
 * @package PCMWS
 * @subpackage Enumerations
 * @author WsdlToPhp Team <contact@wsdltophp.com>
 * @version 20150429-01
 * @date 2016-08-25
 */
/**
 * This class stands for PCMWSEnumClassOverrideType originally named ClassOverrideType
 * Meta informations extracted from the WSDL
 * - from schema : {@link http://pcmiler.alk.com/APIs/SOAP/v1.0/Service.svc?xsd=xsd0}
 * @package PCMWS
 * @subpackage Enumerations
 * @author WsdlToPhp Team <contact@wsdltophp.com>
 * @version 20150429-01
 * @date 2016-08-25
 */
class PCMWSEnumClassOverrideType extends PCMWSWsdlClass
{
    /**
     * Constant for value 'None'
     * @return string 'None'
     */
    const VALUE_NONE = 'None';
    /**
     * Constant for value 'FiftyThreeFoot'
     * @return string 'FiftyThreeFoot'
     */
    const VALUE_FIFTYTHREEFOOT = 'FiftyThreeFoot';
    /**
     * Constant for value 'NationalNetwork'
     * @return string 'NationalNetwork'
     */
    const VALUE_NATIONALNETWORK = 'NationalNetwork';
    /**
     * Return true if value is allowed
     * @uses PCMWSEnumClassOverrideType::VALUE_NONE
     * @uses PCMWSEnumClassOverrideType::VALUE_FIFTYTHREEFOOT
     * @uses PCMWSEnumClassOverrideType::VALUE_NATIONALNETWORK
     * @param mixed $_value value
     * @return bool true|false
     */
    public static function valueIsValid($_value)
    {
        return in_array($_value,array(PCMWSEnumClassOverrideType::VALUE_NONE,PCMWSEnumClassOverrideType::VALUE_FIFTYTHREEFOOT,PCMWSEnumClassOverrideType::VALUE_NATIONALNETWORK));
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
