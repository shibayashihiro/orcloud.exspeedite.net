<?php
/**
 * File for class PCMWSEnumDateOption
 * @package PCMWS
 * @subpackage Enumerations
 * @author WsdlToPhp Team <contact@wsdltophp.com>
 * @version 20150429-01
 * @date 2016-08-25
 */
/**
 * This class stands for PCMWSEnumDateOption originally named DateOption
 * Meta informations extracted from the WSDL
 * - from schema : {@link http://pcmiler.alk.com/APIs/SOAP/v1.0/Service.svc?xsd=xsd0}
 * @package PCMWS
 * @subpackage Enumerations
 * @author WsdlToPhp Team <contact@wsdltophp.com>
 * @version 20150429-01
 * @date 2016-08-25
 */
class PCMWSEnumDateOption extends PCMWSWsdlClass
{
    /**
     * Constant for value 'Current'
     * @return string 'Current'
     */
    const VALUE_CURRENT = 'Current';
    /**
     * Constant for value 'Specific'
     * @return string 'Specific'
     */
    const VALUE_SPECIFIC = 'Specific';
    /**
     * Constant for value 'DayOfWeek'
     * @return string 'DayOfWeek'
     */
    const VALUE_DAYOFWEEK = 'DayOfWeek';
    /**
     * Return true if value is allowed
     * @uses PCMWSEnumDateOption::VALUE_CURRENT
     * @uses PCMWSEnumDateOption::VALUE_SPECIFIC
     * @uses PCMWSEnumDateOption::VALUE_DAYOFWEEK
     * @param mixed $_value value
     * @return bool true|false
     */
    public static function valueIsValid($_value)
    {
        return in_array($_value,array(PCMWSEnumDateOption::VALUE_CURRENT,PCMWSEnumDateOption::VALUE_SPECIFIC,PCMWSEnumDateOption::VALUE_DAYOFWEEK));
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
