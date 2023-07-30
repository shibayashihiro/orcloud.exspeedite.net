<?php
/**
 * File for class PCMWSEnumDayOfWeek
 * @package PCMWS
 * @subpackage Enumerations
 * @author WsdlToPhp Team <contact@wsdltophp.com>
 * @version 20150429-01
 * @date 2016-08-25
 */
/**
 * This class stands for PCMWSEnumDayOfWeek originally named DayOfWeek
 * Meta informations extracted from the WSDL
 * - from schema : {@link http://pcmiler.alk.com/APIs/SOAP/v1.0/Service.svc?xsd=xsd4}
 * @package PCMWS
 * @subpackage Enumerations
 * @author WsdlToPhp Team <contact@wsdltophp.com>
 * @version 20150429-01
 * @date 2016-08-25
 */
class PCMWSEnumDayOfWeek extends PCMWSWsdlClass
{
    /**
     * Constant for value 'Sunday'
     * @return string 'Sunday'
     */
    const VALUE_SUNDAY = 'Sunday';
    /**
     * Constant for value 'Monday'
     * @return string 'Monday'
     */
    const VALUE_MONDAY = 'Monday';
    /**
     * Constant for value 'Tuesday'
     * @return string 'Tuesday'
     */
    const VALUE_TUESDAY = 'Tuesday';
    /**
     * Constant for value 'Wednesday'
     * @return string 'Wednesday'
     */
    const VALUE_WEDNESDAY = 'Wednesday';
    /**
     * Constant for value 'Thursday'
     * @return string 'Thursday'
     */
    const VALUE_THURSDAY = 'Thursday';
    /**
     * Constant for value 'Friday'
     * @return string 'Friday'
     */
    const VALUE_FRIDAY = 'Friday';
    /**
     * Constant for value 'Saturday'
     * @return string 'Saturday'
     */
    const VALUE_SATURDAY = 'Saturday';
    /**
     * Return true if value is allowed
     * @uses PCMWSEnumDayOfWeek::VALUE_SUNDAY
     * @uses PCMWSEnumDayOfWeek::VALUE_MONDAY
     * @uses PCMWSEnumDayOfWeek::VALUE_TUESDAY
     * @uses PCMWSEnumDayOfWeek::VALUE_WEDNESDAY
     * @uses PCMWSEnumDayOfWeek::VALUE_THURSDAY
     * @uses PCMWSEnumDayOfWeek::VALUE_FRIDAY
     * @uses PCMWSEnumDayOfWeek::VALUE_SATURDAY
     * @param mixed $_value value
     * @return bool true|false
     */
    public static function valueIsValid($_value)
    {
        return in_array($_value,array(PCMWSEnumDayOfWeek::VALUE_SUNDAY,PCMWSEnumDayOfWeek::VALUE_MONDAY,PCMWSEnumDayOfWeek::VALUE_TUESDAY,PCMWSEnumDayOfWeek::VALUE_WEDNESDAY,PCMWSEnumDayOfWeek::VALUE_THURSDAY,PCMWSEnumDayOfWeek::VALUE_FRIDAY,PCMWSEnumDayOfWeek::VALUE_SATURDAY));
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
