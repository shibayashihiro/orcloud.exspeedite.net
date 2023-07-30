<?php
/**
 * File for class PCMWSEnumMapStyle
 * @package PCMWS
 * @subpackage Enumerations
 * @author WsdlToPhp Team <contact@wsdltophp.com>
 * @version 20150429-01
 * @date 2016-08-25
 */
/**
 * This class stands for PCMWSEnumMapStyle originally named MapStyle
 * Meta informations extracted from the WSDL
 * - from schema : {@link http://pcmiler.alk.com/APIs/SOAP/v1.0/Service.svc?xsd=xsd0}
 * @package PCMWS
 * @subpackage Enumerations
 * @author WsdlToPhp Team <contact@wsdltophp.com>
 * @version 20150429-01
 * @date 2016-08-25
 */
class PCMWSEnumMapStyle extends PCMWSWsdlClass
{
    /**
     * Constant for value 'Default'
     * @return string 'Default'
     */
    const VALUE_DEFAULT = 'Default';
    /**
     * Constant for value 'Classic'
     * @return string 'Classic'
     */
    const VALUE_CLASSIC = 'Classic';
    /**
     * Constant for value 'Monochrome'
     * @return string 'Monochrome'
     */
    const VALUE_MONOCHROME = 'Monochrome';
    /**
     * Constant for value 'RoadAtlas'
     * @return string 'RoadAtlas'
     */
    const VALUE_ROADATLAS = 'RoadAtlas';
    /**
     * Constant for value 'Darkness'
     * @return string 'Darkness'
     */
    const VALUE_DARKNESS = 'Darkness';
    /**
     * Constant for value 'Modern'
     * @return string 'Modern'
     */
    const VALUE_MODERN = 'Modern';
    /**
     * Constant for value 'Contemporary'
     * @return string 'Contemporary'
     */
    const VALUE_CONTEMPORARY = 'Contemporary';
    /**
     * Constant for value 'Night'
     * @return string 'Night'
     */
    const VALUE_NIGHT = 'Night';
    /**
     * Constant for value 'Satellite'
     * @return string 'Satellite'
     */
    const VALUE_SATELLITE = 'Satellite';
    /**
     * Constant for value 'Lightness'
     * @return string 'Lightness'
     */
    const VALUE_LIGHTNESS = 'Lightness';
    /**
     * Constant for value 'Smooth'
     * @return string 'Smooth'
     */
    const VALUE_SMOOTH = 'Smooth';
    /**
     * Constant for value 'Terrain'
     * @return string 'Terrain'
     */
    const VALUE_TERRAIN = 'Terrain';
    /**
     * Return true if value is allowed
     * @uses PCMWSEnumMapStyle::VALUE_DEFAULT
     * @uses PCMWSEnumMapStyle::VALUE_CLASSIC
     * @uses PCMWSEnumMapStyle::VALUE_MONOCHROME
     * @uses PCMWSEnumMapStyle::VALUE_ROADATLAS
     * @uses PCMWSEnumMapStyle::VALUE_DARKNESS
     * @uses PCMWSEnumMapStyle::VALUE_MODERN
     * @uses PCMWSEnumMapStyle::VALUE_CONTEMPORARY
     * @uses PCMWSEnumMapStyle::VALUE_NIGHT
     * @uses PCMWSEnumMapStyle::VALUE_SATELLITE
     * @uses PCMWSEnumMapStyle::VALUE_LIGHTNESS
     * @uses PCMWSEnumMapStyle::VALUE_SMOOTH
     * @uses PCMWSEnumMapStyle::VALUE_TERRAIN
     * @param mixed $_value value
     * @return bool true|false
     */
    public static function valueIsValid($_value)
    {
        return in_array($_value,array(PCMWSEnumMapStyle::VALUE_DEFAULT,PCMWSEnumMapStyle::VALUE_CLASSIC,PCMWSEnumMapStyle::VALUE_MONOCHROME,PCMWSEnumMapStyle::VALUE_ROADATLAS,PCMWSEnumMapStyle::VALUE_DARKNESS,PCMWSEnumMapStyle::VALUE_MODERN,PCMWSEnumMapStyle::VALUE_CONTEMPORARY,PCMWSEnumMapStyle::VALUE_NIGHT,PCMWSEnumMapStyle::VALUE_SATELLITE,PCMWSEnumMapStyle::VALUE_LIGHTNESS,PCMWSEnumMapStyle::VALUE_SMOOTH,PCMWSEnumMapStyle::VALUE_TERRAIN));
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
