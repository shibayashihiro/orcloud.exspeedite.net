<?php
/**
 * File for class PCMWSEnumGeofenceState
 * @package PCMWS
 * @subpackage Enumerations
 * @author WsdlToPhp Team <contact@wsdltophp.com>
 * @version 20150429-01
 * @date 2016-08-25
 */
/**
 * This class stands for PCMWSEnumGeofenceState originally named GeofenceState
 * Meta informations extracted from the WSDL
 * - from schema : {@link http://pcmiler.alk.com/APIs/SOAP/v1.0/Service.svc?xsd=xsd0}
 * @package PCMWS
 * @subpackage Enumerations
 * @author WsdlToPhp Team <contact@wsdltophp.com>
 * @version 20150429-01
 * @date 2016-08-25
 */
class PCMWSEnumGeofenceState extends PCMWSWsdlClass
{
    /**
     * Constant for value 'Off'
     * @return string 'Off'
     */
    const VALUE_OFF = 'Off';
    /**
     * Constant for value 'Warn'
     * @return string 'Warn'
     */
    const VALUE_WARN = 'Warn';
    /**
     * Constant for value 'Avoid'
     * @return string 'Avoid'
     */
    const VALUE_AVOID = 'Avoid';
    /**
     * Return true if value is allowed
     * @uses PCMWSEnumGeofenceState::VALUE_OFF
     * @uses PCMWSEnumGeofenceState::VALUE_WARN
     * @uses PCMWSEnumGeofenceState::VALUE_AVOID
     * @param mixed $_value value
     * @return bool true|false
     */
    public static function valueIsValid($_value)
    {
        return in_array($_value,array(PCMWSEnumGeofenceState::VALUE_OFF,PCMWSEnumGeofenceState::VALUE_WARN,PCMWSEnumGeofenceState::VALUE_AVOID));
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
