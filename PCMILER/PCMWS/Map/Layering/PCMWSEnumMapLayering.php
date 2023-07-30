<?php
/**
 * File for class PCMWSEnumMapLayering
 * @package PCMWS
 * @subpackage Enumerations
 * @author WsdlToPhp Team <contact@wsdltophp.com>
 * @version 20150429-01
 * @date 2016-08-25
 */
/**
 * This class stands for PCMWSEnumMapLayering originally named MapLayering
 * Meta informations extracted from the WSDL
 * - from schema : {@link http://pcmiler.alk.com/APIs/SOAP/v1.0/Service.svc?xsd=xsd0}
 * @package PCMWS
 * @subpackage Enumerations
 * @author WsdlToPhp Team <contact@wsdltophp.com>
 * @version 20150429-01
 * @date 2016-08-25
 */
class PCMWSEnumMapLayering extends PCMWSWsdlClass
{
    /**
     * Constant for value 'MapAndPointsOneLayer'
     * @return string 'MapAndPointsOneLayer'
     */
    const VALUE_MAPANDPOINTSONELAYER = 'MapAndPointsOneLayer';
    /**
     * Constant for value 'MapLayer'
     * @return string 'MapLayer'
     */
    const VALUE_MAPLAYER = 'MapLayer';
    /**
     * Constant for value 'PointsLayer'
     * @return string 'PointsLayer'
     */
    const VALUE_POINTSLAYER = 'PointsLayer';
    /**
     * Constant for value 'MapAndPointsTwoLayers'
     * @return string 'MapAndPointsTwoLayers'
     */
    const VALUE_MAPANDPOINTSTWOLAYERS = 'MapAndPointsTwoLayers';
    /**
     * Constant for value 'BackgroundOnly'
     * @return string 'BackgroundOnly'
     */
    const VALUE_BACKGROUNDONLY = 'BackgroundOnly';
    /**
     * Constant for value 'NoBackground'
     * @return string 'NoBackground'
     */
    const VALUE_NOBACKGROUND = 'NoBackground';
    /**
     * Return true if value is allowed
     * @uses PCMWSEnumMapLayering::VALUE_MAPANDPOINTSONELAYER
     * @uses PCMWSEnumMapLayering::VALUE_MAPLAYER
     * @uses PCMWSEnumMapLayering::VALUE_POINTSLAYER
     * @uses PCMWSEnumMapLayering::VALUE_MAPANDPOINTSTWOLAYERS
     * @uses PCMWSEnumMapLayering::VALUE_BACKGROUNDONLY
     * @uses PCMWSEnumMapLayering::VALUE_NOBACKGROUND
     * @param mixed $_value value
     * @return bool true|false
     */
    public static function valueIsValid($_value)
    {
        return in_array($_value,array(PCMWSEnumMapLayering::VALUE_MAPANDPOINTSONELAYER,PCMWSEnumMapLayering::VALUE_MAPLAYER,PCMWSEnumMapLayering::VALUE_POINTSLAYER,PCMWSEnumMapLayering::VALUE_MAPANDPOINTSTWOLAYERS,PCMWSEnumMapLayering::VALUE_BACKGROUNDONLY,PCMWSEnumMapLayering::VALUE_NOBACKGROUND));
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
