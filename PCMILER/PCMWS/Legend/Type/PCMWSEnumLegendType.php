<?php
/**
 * File for class PCMWSEnumLegendType
 * @package PCMWS
 * @subpackage Enumerations
 * @author WsdlToPhp Team <contact@wsdltophp.com>
 * @version 20150429-01
 * @date 2016-08-25
 */
/**
 * This class stands for PCMWSEnumLegendType originally named LegendType
 * Meta informations extracted from the WSDL
 * - from schema : {@link http://pcmiler.alk.com/APIs/SOAP/v1.0/Service.svc?xsd=xsd0}
 * @package PCMWS
 * @subpackage Enumerations
 * @author WsdlToPhp Team <contact@wsdltophp.com>
 * @version 20150429-01
 * @date 2016-08-25
 */
class PCMWSEnumLegendType extends PCMWSWsdlClass
{
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
     * Return true if value is allowed
     * @uses PCMWSEnumLegendType::VALUE_SCALEOFMILES
     * @uses PCMWSEnumLegendType::VALUE_ROUTELEGEND
     * @uses PCMWSEnumLegendType::VALUE_ROADLEGEND
     * @uses PCMWSEnumLegendType::VALUE_HAZMATLEGEND
     * @uses PCMWSEnumLegendType::VALUE_TRAFFICLEGEND
     * @param mixed $_value value
     * @return bool true|false
     */
    public static function valueIsValid($_value)
    {
        return in_array($_value,array(PCMWSEnumLegendType::VALUE_SCALEOFMILES,PCMWSEnumLegendType::VALUE_ROUTELEGEND,PCMWSEnumLegendType::VALUE_ROADLEGEND,PCMWSEnumLegendType::VALUE_HAZMATLEGEND,PCMWSEnumLegendType::VALUE_TRAFFICLEGEND));
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
