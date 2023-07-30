<?php
/**
 * File for class PCMWSEnumPOIGenericType
 * @package PCMWS
 * @subpackage Enumerations
 * @author WsdlToPhp Team <contact@wsdltophp.com>
 * @version 20150429-01
 * @date 2016-08-25
 */
/**
 * This class stands for PCMWSEnumPOIGenericType originally named POIGenericType
 * Meta informations extracted from the WSDL
 * - from schema : {@link http://pcmiler.alk.com/APIs/SOAP/v1.0/Service.svc?xsd=xsd0}
 * @package PCMWS
 * @subpackage Enumerations
 * @author WsdlToPhp Team <contact@wsdltophp.com>
 * @version 20150429-01
 * @date 2016-08-25
 */
class PCMWSEnumPOIGenericType extends PCMWSWsdlClass
{
    /**
     * Constant for value 'All'
     * @return string 'All'
     */
    const VALUE_ALL = 'All';
    /**
     * Constant for value 'WeightStation'
     * @return string 'WeightStation'
     */
    const VALUE_WEIGHTSTATION = 'WeightStation';
    /**
     * Constant for value 'CatScale'
     * @return string 'CatScale'
     */
    const VALUE_CATSCALE = 'CatScale';
    /**
     * Constant for value 'LCVLot'
     * @return string 'LCVLot'
     */
    const VALUE_LCVLOT = 'LCVLot';
    /**
     * Constant for value 'Hotel'
     * @return string 'Hotel'
     */
    const VALUE_HOTEL = 'Hotel';
    /**
     * Constant for value 'IntermodalSmall'
     * @return string 'IntermodalSmall'
     */
    const VALUE_INTERMODALSMALL = 'IntermodalSmall';
    /**
     * Constant for value 'IntermodalMedium'
     * @return string 'IntermodalMedium'
     */
    const VALUE_INTERMODALMEDIUM = 'IntermodalMedium';
    /**
     * Constant for value 'IntermodalLarge'
     * @return string 'IntermodalLarge'
     */
    const VALUE_INTERMODALLARGE = 'IntermodalLarge';
    /**
     * Constant for value 'Parking'
     * @return string 'Parking'
     */
    const VALUE_PARKING = 'Parking';
    /**
     * Constant for value 'RestAreaHoS'
     * @return string 'RestAreaHoS'
     */
    const VALUE_RESTAREAHOS = 'RestAreaHoS';
    /**
     * Constant for value 'TruckStop'
     * @return string 'TruckStop'
     */
    const VALUE_TRUCKSTOP = 'TruckStop';
    /**
     * Constant for value 'TruckServiceHoS'
     * @return string 'TruckServiceHoS'
     */
    const VALUE_TRUCKSERVICEHOS = 'TruckServiceHoS';
    /**
     * Constant for value 'HighwayExit'
     * @return string 'HighwayExit'
     */
    const VALUE_HIGHWAYEXIT = 'HighwayExit';
    /**
     * Constant for value 'MajorAirport'
     * @return string 'MajorAirport'
     */
    const VALUE_MAJORAIRPORT = 'MajorAirport';
    /**
     * Constant for value 'EventFacility'
     * @return string 'EventFacility'
     */
    const VALUE_EVENTFACILITY = 'EventFacility';
    /**
     * Return true if value is allowed
     * @uses PCMWSEnumPOIGenericType::VALUE_ALL
     * @uses PCMWSEnumPOIGenericType::VALUE_WEIGHTSTATION
     * @uses PCMWSEnumPOIGenericType::VALUE_CATSCALE
     * @uses PCMWSEnumPOIGenericType::VALUE_LCVLOT
     * @uses PCMWSEnumPOIGenericType::VALUE_HOTEL
     * @uses PCMWSEnumPOIGenericType::VALUE_INTERMODALSMALL
     * @uses PCMWSEnumPOIGenericType::VALUE_INTERMODALMEDIUM
     * @uses PCMWSEnumPOIGenericType::VALUE_INTERMODALLARGE
     * @uses PCMWSEnumPOIGenericType::VALUE_PARKING
     * @uses PCMWSEnumPOIGenericType::VALUE_RESTAREAHOS
     * @uses PCMWSEnumPOIGenericType::VALUE_TRUCKSTOP
     * @uses PCMWSEnumPOIGenericType::VALUE_TRUCKSERVICEHOS
     * @uses PCMWSEnumPOIGenericType::VALUE_HIGHWAYEXIT
     * @uses PCMWSEnumPOIGenericType::VALUE_MAJORAIRPORT
     * @uses PCMWSEnumPOIGenericType::VALUE_EVENTFACILITY
     * @param mixed $_value value
     * @return bool true|false
     */
    public static function valueIsValid($_value)
    {
        return in_array($_value,array(PCMWSEnumPOIGenericType::VALUE_ALL,PCMWSEnumPOIGenericType::VALUE_WEIGHTSTATION,PCMWSEnumPOIGenericType::VALUE_CATSCALE,PCMWSEnumPOIGenericType::VALUE_LCVLOT,PCMWSEnumPOIGenericType::VALUE_HOTEL,PCMWSEnumPOIGenericType::VALUE_INTERMODALSMALL,PCMWSEnumPOIGenericType::VALUE_INTERMODALMEDIUM,PCMWSEnumPOIGenericType::VALUE_INTERMODALLARGE,PCMWSEnumPOIGenericType::VALUE_PARKING,PCMWSEnumPOIGenericType::VALUE_RESTAREAHOS,PCMWSEnumPOIGenericType::VALUE_TRUCKSTOP,PCMWSEnumPOIGenericType::VALUE_TRUCKSERVICEHOS,PCMWSEnumPOIGenericType::VALUE_HIGHWAYEXIT,PCMWSEnumPOIGenericType::VALUE_MAJORAIRPORT,PCMWSEnumPOIGenericType::VALUE_EVENTFACILITY));
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
