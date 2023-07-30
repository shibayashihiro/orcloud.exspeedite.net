<?php
/**
 * File for class PCMWSEnumVehicleType
 * @package PCMWS
 * @subpackage Enumerations
 * @author WsdlToPhp Team <contact@wsdltophp.com>
 * @version 20150429-01
 * @date 2016-08-25
 */
/**
 * This class stands for PCMWSEnumVehicleType originally named VehicleType
 * Meta informations extracted from the WSDL
 * - from schema : {@link http://pcmiler.alk.com/APIs/SOAP/v1.0/Service.svc?xsd=xsd0}
 * @package PCMWS
 * @subpackage Enumerations
 * @author WsdlToPhp Team <contact@wsdltophp.com>
 * @version 20150429-01
 * @date 2016-08-25
 */
class PCMWSEnumVehicleType extends PCMWSWsdlClass
{
    /**
     * Constant for value 'Truck'
     * @return string 'Truck'
     */
    const VALUE_TRUCK = 'Truck';
    /**
     * Constant for value 'LightTruck'
     * @return string 'LightTruck'
     */
    const VALUE_LIGHTTRUCK = 'LightTruck';
    /**
     * Constant for value 'Auto'
     * @return string 'Auto'
     */
    const VALUE_AUTO = 'Auto';
    /**
     * Return true if value is allowed
     * @uses PCMWSEnumVehicleType::VALUE_TRUCK
     * @uses PCMWSEnumVehicleType::VALUE_LIGHTTRUCK
     * @uses PCMWSEnumVehicleType::VALUE_AUTO
     * @param mixed $_value value
     * @return bool true|false
     */
    public static function valueIsValid($_value)
    {
        return in_array($_value,array(PCMWSEnumVehicleType::VALUE_TRUCK,PCMWSEnumVehicleType::VALUE_LIGHTTRUCK,PCMWSEnumVehicleType::VALUE_AUTO));
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
