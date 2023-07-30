<?php
/**
 * File for class PCMWSEnumGeofenceShape
 * @package PCMWS
 * @subpackage Enumerations
 * @author WsdlToPhp Team <contact@wsdltophp.com>
 * @version 20150429-01
 * @date 2016-08-25
 */
/**
 * This class stands for PCMWSEnumGeofenceShape originally named GeofenceShape
 * Meta informations extracted from the WSDL
 * - from schema : {@link http://pcmiler.alk.com/APIs/SOAP/v1.0/Service.svc?xsd=xsd0}
 * @package PCMWS
 * @subpackage Enumerations
 * @author WsdlToPhp Team <contact@wsdltophp.com>
 * @version 20150429-01
 * @date 2016-08-25
 */
class PCMWSEnumGeofenceShape extends PCMWSWsdlClass
{
    /**
     * Constant for value 'Circle'
     * @return string 'Circle'
     */
    const VALUE_CIRCLE = 'Circle';
    /**
     * Constant for value 'Polygon'
     * @return string 'Polygon'
     */
    const VALUE_POLYGON = 'Polygon';
    /**
     * Return true if value is allowed
     * @uses PCMWSEnumGeofenceShape::VALUE_CIRCLE
     * @uses PCMWSEnumGeofenceShape::VALUE_POLYGON
     * @param mixed $_value value
     * @return bool true|false
     */
    public static function valueIsValid($_value)
    {
        return in_array($_value,array(PCMWSEnumGeofenceShape::VALUE_CIRCLE,PCMWSEnumGeofenceShape::VALUE_POLYGON));
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
