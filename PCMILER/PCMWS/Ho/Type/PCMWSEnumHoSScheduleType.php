<?php
/**
 * File for class PCMWSEnumHoSScheduleType
 * @package PCMWS
 * @subpackage Enumerations
 * @author WsdlToPhp Team <contact@wsdltophp.com>
 * @version 20150429-01
 * @date 2016-08-25
 */
/**
 * This class stands for PCMWSEnumHoSScheduleType originally named HoSScheduleType
 * Meta informations extracted from the WSDL
 * - from schema : {@link http://pcmiler.alk.com/APIs/SOAP/v1.0/Service.svc?xsd=xsd0}
 * @package PCMWS
 * @subpackage Enumerations
 * @author WsdlToPhp Team <contact@wsdltophp.com>
 * @version 20150429-01
 * @date 2016-08-25
 */
class PCMWSEnumHoSScheduleType extends PCMWSWsdlClass
{
    /**
     * Constant for value 'USFed607LH'
     * @return string 'USFed607LH'
     */
    const VALUE_USFED607LH = 'USFed607LH';
    /**
     * Constant for value 'USFed708LH'
     * @return string 'USFed708LH'
     */
    const VALUE_USFED708LH = 'USFed708LH';
    /**
     * Return true if value is allowed
     * @uses PCMWSEnumHoSScheduleType::VALUE_USFED607LH
     * @uses PCMWSEnumHoSScheduleType::VALUE_USFED708LH
     * @param mixed $_value value
     * @return bool true|false
     */
    public static function valueIsValid($_value)
    {
        return in_array($_value,array(PCMWSEnumHoSScheduleType::VALUE_USFED607LH,PCMWSEnumHoSScheduleType::VALUE_USFED708LH));
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
