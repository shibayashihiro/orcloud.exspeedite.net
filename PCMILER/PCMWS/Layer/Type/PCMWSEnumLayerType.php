<?php
/**
 * File for class PCMWSEnumLayerType
 * @package PCMWS
 * @subpackage Enumerations
 * @author WsdlToPhp Team <contact@wsdltophp.com>
 * @version 20150429-01
 * @date 2016-08-25
 */
/**
 * This class stands for PCMWSEnumLayerType originally named LayerType
 * Meta informations extracted from the WSDL
 * - from schema : {@link http://pcmiler.alk.com/APIs/SOAP/v1.0/Service.svc?xsd=xsd0}
 * @package PCMWS
 * @subpackage Enumerations
 * @author WsdlToPhp Team <contact@wsdltophp.com>
 * @version 20150429-01
 * @date 2016-08-25
 */
class PCMWSEnumLayerType extends PCMWSWsdlClass
{
    /**
     * Constant for value 'BaseMap'
     * @return string 'BaseMap'
     */
    const VALUE_BASEMAP = 'BaseMap';
    /**
     * Constant for value 'ScaleOfMiles'
     * @return string 'ScaleOfMiles'
     */
    const VALUE_SCALEOFMILES = 'ScaleOfMiles';
    /**
     * Constant for value 'RouteLegend'
     * @return string 'RouteLegend'
     */
    const VALUE_ROUTELEGEND = 'RouteLegend';
    /**
     * Constant for value 'RoadLegend'
     * @return string 'RoadLegend'
     */
    const VALUE_ROADLEGEND = 'RoadLegend';
    /**
     * Constant for value 'HazMatLegend'
     * @return string 'HazMatLegend'
     */
    const VALUE_HAZMATLEGEND = 'HazMatLegend';
    /**
     * Constant for value 'TrafficLegend'
     * @return string 'TrafficLegend'
     */
    const VALUE_TRAFFICLEGEND = 'TrafficLegend';
    /**
     * Constant for value 'PointMap'
     * @return string 'PointMap'
     */
    const VALUE_POINTMAP = 'PointMap';
    /**
     * Return true if value is allowed
     * @uses PCMWSEnumLayerType::VALUE_BASEMAP
     * @uses PCMWSEnumLayerType::VALUE_SCALEOFMILES
     * @uses PCMWSEnumLayerType::VALUE_ROUTELEGEND
     * @uses PCMWSEnumLayerType::VALUE_ROADLEGEND
     * @uses PCMWSEnumLayerType::VALUE_HAZMATLEGEND
     * @uses PCMWSEnumLayerType::VALUE_TRAFFICLEGEND
     * @uses PCMWSEnumLayerType::VALUE_POINTMAP
     * @param mixed $_value value
     * @return bool true|false
     */
    public static function valueIsValid($_value)
    {
        return in_array($_value,array(PCMWSEnumLayerType::VALUE_BASEMAP,PCMWSEnumLayerType::VALUE_SCALEOFMILES,PCMWSEnumLayerType::VALUE_ROUTELEGEND,PCMWSEnumLayerType::VALUE_ROADLEGEND,PCMWSEnumLayerType::VALUE_HAZMATLEGEND,PCMWSEnumLayerType::VALUE_TRAFFICLEGEND,PCMWSEnumLayerType::VALUE_POINTMAP));
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
