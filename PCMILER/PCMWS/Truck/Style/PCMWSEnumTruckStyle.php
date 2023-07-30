<?php
/**
 * File for class PCMWSEnumTruckStyle
 * @package PCMWS
 * @subpackage Enumerations
 * @author WsdlToPhp Team <contact@wsdltophp.com>
 * @version 20150429-01
 * @date 2016-08-25
 */
/**
 * This class stands for PCMWSEnumTruckStyle originally named TruckStyle
 * Meta informations extracted from the WSDL
 * - from schema : {@link http://pcmiler.alk.com/APIs/SOAP/v1.0/Service.svc?xsd=xsd0}
 * @package PCMWS
 * @subpackage Enumerations
 * @author WsdlToPhp Team <contact@wsdltophp.com>
 * @version 20150429-01
 * @date 2016-08-25
 */
class PCMWSEnumTruckStyle extends PCMWSWsdlClass
{
    /**
     * Constant for value 'None'
     * @return string 'None'
     */
    const VALUE_NONE = 'None';
    /**
     * Constant for value 'TwentyEightDoubleTrailer'
     * @return string 'TwentyEightDoubleTrailer'
     */
    const VALUE_TWENTYEIGHTDOUBLETRAILER = 'TwentyEightDoubleTrailer';
    /**
     * Constant for value 'FortyStraightTruck'
     * @return string 'FortyStraightTruck'
     */
    const VALUE_FORTYSTRAIGHTTRUCK = 'FortyStraightTruck';
    /**
     * Constant for value 'FortyEightSemiTrailer'
     * @return string 'FortyEightSemiTrailer'
     */
    const VALUE_FORTYEIGHTSEMITRAILER = 'FortyEightSemiTrailer';
    /**
     * Constant for value 'FiftyThreeSemiTrailer'
     * @return string 'FiftyThreeSemiTrailer'
     */
    const VALUE_FIFTYTHREESEMITRAILER = 'FiftyThreeSemiTrailer';
    /**
     * Constant for value 'FullSizeVan'
     * @return string 'FullSizeVan'
     */
    const VALUE_FULLSIZEVAN = 'FullSizeVan';
    /**
     * Constant for value 'TwentySixStraightTruck'
     * @return string 'TwentySixStraightTruck'
     */
    const VALUE_TWENTYSIXSTRAIGHTTRUCK = 'TwentySixStraightTruck';
    /**
     * Return true if value is allowed
     * @uses PCMWSEnumTruckStyle::VALUE_NONE
     * @uses PCMWSEnumTruckStyle::VALUE_TWENTYEIGHTDOUBLETRAILER
     * @uses PCMWSEnumTruckStyle::VALUE_FORTYSTRAIGHTTRUCK
     * @uses PCMWSEnumTruckStyle::VALUE_FORTYEIGHTSEMITRAILER
     * @uses PCMWSEnumTruckStyle::VALUE_FIFTYTHREESEMITRAILER
     * @uses PCMWSEnumTruckStyle::VALUE_FULLSIZEVAN
     * @uses PCMWSEnumTruckStyle::VALUE_TWENTYSIXSTRAIGHTTRUCK
     * @param mixed $_value value
     * @return bool true|false
     */
    public static function valueIsValid($_value)
    {
        return in_array($_value,array(PCMWSEnumTruckStyle::VALUE_NONE,PCMWSEnumTruckStyle::VALUE_TWENTYEIGHTDOUBLETRAILER,PCMWSEnumTruckStyle::VALUE_FORTYSTRAIGHTTRUCK,PCMWSEnumTruckStyle::VALUE_FORTYEIGHTSEMITRAILER,PCMWSEnumTruckStyle::VALUE_FIFTYTHREESEMITRAILER,PCMWSEnumTruckStyle::VALUE_FULLSIZEVAN,PCMWSEnumTruckStyle::VALUE_TWENTYSIXSTRAIGHTTRUCK));
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
