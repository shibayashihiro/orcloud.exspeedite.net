<?php
/**
 * File for class PCMWSEnumRoadType
 * @package PCMWS
 * @subpackage Enumerations
 * @author WsdlToPhp Team <contact@wsdltophp.com>
 * @version 20150429-01
 * @date 2016-08-25
 */
/**
 * This class stands for PCMWSEnumRoadType originally named RoadType
 * Meta informations extracted from the WSDL
 * - from schema : {@link http://pcmiler.alk.com/APIs/SOAP/v1.0/Service.svc?xsd=xsd0}
 * @package PCMWS
 * @subpackage Enumerations
 * @author WsdlToPhp Team <contact@wsdltophp.com>
 * @version 20150429-01
 * @date 2016-08-25
 */
class PCMWSEnumRoadType extends PCMWSWsdlClass
{
    /**
     * Constant for value 'InterStateRural'
     * @return string 'InterStateRural'
     */
    const VALUE_INTERSTATERURAL = 'InterStateRural';
    /**
     * Constant for value 'DividedRural'
     * @return string 'DividedRural'
     */
    const VALUE_DIVIDEDRURAL = 'DividedRural';
    /**
     * Constant for value 'PrimaryRural'
     * @return string 'PrimaryRural'
     */
    const VALUE_PRIMARYRURAL = 'PrimaryRural';
    /**
     * Constant for value 'FerriesRural'
     * @return string 'FerriesRural'
     */
    const VALUE_FERRIESRURAL = 'FerriesRural';
    /**
     * Constant for value 'SecondaryRural'
     * @return string 'SecondaryRural'
     */
    const VALUE_SECONDARYRURAL = 'SecondaryRural';
    /**
     * Constant for value 'LocalRural'
     * @return string 'LocalRural'
     */
    const VALUE_LOCALRURAL = 'LocalRural';
    /**
     * Constant for value 'InterStateUrban'
     * @return string 'InterStateUrban'
     */
    const VALUE_INTERSTATEURBAN = 'InterStateUrban';
    /**
     * Constant for value 'DividedUrban'
     * @return string 'DividedUrban'
     */
    const VALUE_DIVIDEDURBAN = 'DividedUrban';
    /**
     * Constant for value 'PrimaryUrban'
     * @return string 'PrimaryUrban'
     */
    const VALUE_PRIMARYURBAN = 'PrimaryUrban';
    /**
     * Constant for value 'FerriesUrban'
     * @return string 'FerriesUrban'
     */
    const VALUE_FERRIESURBAN = 'FerriesUrban';
    /**
     * Constant for value 'SecondaryUrban'
     * @return string 'SecondaryUrban'
     */
    const VALUE_SECONDARYURBAN = 'SecondaryUrban';
    /**
     * Constant for value 'LocalUrban'
     * @return string 'LocalUrban'
     */
    const VALUE_LOCALURBAN = 'LocalUrban';
    /**
     * Return true if value is allowed
     * @uses PCMWSEnumRoadType::VALUE_INTERSTATERURAL
     * @uses PCMWSEnumRoadType::VALUE_DIVIDEDRURAL
     * @uses PCMWSEnumRoadType::VALUE_PRIMARYRURAL
     * @uses PCMWSEnumRoadType::VALUE_FERRIESRURAL
     * @uses PCMWSEnumRoadType::VALUE_SECONDARYRURAL
     * @uses PCMWSEnumRoadType::VALUE_LOCALRURAL
     * @uses PCMWSEnumRoadType::VALUE_INTERSTATEURBAN
     * @uses PCMWSEnumRoadType::VALUE_DIVIDEDURBAN
     * @uses PCMWSEnumRoadType::VALUE_PRIMARYURBAN
     * @uses PCMWSEnumRoadType::VALUE_FERRIESURBAN
     * @uses PCMWSEnumRoadType::VALUE_SECONDARYURBAN
     * @uses PCMWSEnumRoadType::VALUE_LOCALURBAN
     * @param mixed $_value value
     * @return bool true|false
     */
    public static function valueIsValid($_value)
    {
        return in_array($_value,array(PCMWSEnumRoadType::VALUE_INTERSTATERURAL,PCMWSEnumRoadType::VALUE_DIVIDEDRURAL,PCMWSEnumRoadType::VALUE_PRIMARYRURAL,PCMWSEnumRoadType::VALUE_FERRIESRURAL,PCMWSEnumRoadType::VALUE_SECONDARYRURAL,PCMWSEnumRoadType::VALUE_LOCALRURAL,PCMWSEnumRoadType::VALUE_INTERSTATEURBAN,PCMWSEnumRoadType::VALUE_DIVIDEDURBAN,PCMWSEnumRoadType::VALUE_PRIMARYURBAN,PCMWSEnumRoadType::VALUE_FERRIESURBAN,PCMWSEnumRoadType::VALUE_SECONDARYURBAN,PCMWSEnumRoadType::VALUE_LOCALURBAN));
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
