<?php
/**
 * File for class PCMWSEnumETAETDType
 * @package PCMWS
 * @subpackage Enumerations
 * @author WsdlToPhp Team <contact@wsdltophp.com>
 * @version 20150429-01
 * @date 2016-08-25
 */
/**
 * This class stands for PCMWSEnumETAETDType originally named ETAETDType
 * Meta informations extracted from the WSDL
 * - from schema : {@link http://pcmiler.alk.com/APIs/SOAP/v1.0/Service.svc?xsd=xsd0}
 * @package PCMWS
 * @subpackage Enumerations
 * @author WsdlToPhp Team <contact@wsdltophp.com>
 * @version 20150429-01
 * @date 2016-08-25
 */
class PCMWSEnumETAETDType extends PCMWSWsdlClass
{
    /**
     * Constant for value 'Arrival'
     * @return string 'Arrival'
     */
    const VALUE_ARRIVAL = 'Arrival';
    /**
     * Constant for value 'Depart'
     * @return string 'Depart'
     */
    const VALUE_DEPART = 'Depart';
    /**
     * Return true if value is allowed
     * @uses PCMWSEnumETAETDType::VALUE_ARRIVAL
     * @uses PCMWSEnumETAETDType::VALUE_DEPART
     * @param mixed $_value value
     * @return bool true|false
     */
    public static function valueIsValid($_value)
    {
        return in_array($_value,array(PCMWSEnumETAETDType::VALUE_ARRIVAL,PCMWSEnumETAETDType::VALUE_DEPART));
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
