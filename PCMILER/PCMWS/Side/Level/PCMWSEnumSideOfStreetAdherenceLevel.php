<?php
/**
 * File for class PCMWSEnumSideOfStreetAdherenceLevel
 * @package PCMWS
 * @subpackage Enumerations
 * @author WsdlToPhp Team <contact@wsdltophp.com>
 * @version 20150429-01
 * @date 2016-08-25
 */
/**
 * This class stands for PCMWSEnumSideOfStreetAdherenceLevel originally named SideOfStreetAdherenceLevel
 * Meta informations extracted from the WSDL
 * - from schema : {@link http://pcmiler.alk.com/APIs/SOAP/v1.0/Service.svc?xsd=xsd0}
 * @package PCMWS
 * @subpackage Enumerations
 * @author WsdlToPhp Team <contact@wsdltophp.com>
 * @version 20150429-01
 * @date 2016-08-25
 */
class PCMWSEnumSideOfStreetAdherenceLevel extends PCMWSWsdlClass
{
    /**
     * Constant for value 'Off'
     * @return string 'Off'
     */
    const VALUE_OFF = 'Off';
    /**
     * Constant for value 'Minimal'
     * @return string 'Minimal'
     */
    const VALUE_MINIMAL = 'Minimal';
    /**
     * Constant for value 'Moderate'
     * @return string 'Moderate'
     */
    const VALUE_MODERATE = 'Moderate';
    /**
     * Constant for value 'Average'
     * @return string 'Average'
     */
    const VALUE_AVERAGE = 'Average';
    /**
     * Constant for value 'Strict'
     * @return string 'Strict'
     */
    const VALUE_STRICT = 'Strict';
    /**
     * Constant for value 'Adhere'
     * @return string 'Adhere'
     */
    const VALUE_ADHERE = 'Adhere';
    /**
     * Constant for value 'StronglyAdhere'
     * @return string 'StronglyAdhere'
     */
    const VALUE_STRONGLYADHERE = 'StronglyAdhere';
    /**
     * Return true if value is allowed
     * @uses PCMWSEnumSideOfStreetAdherenceLevel::VALUE_OFF
     * @uses PCMWSEnumSideOfStreetAdherenceLevel::VALUE_MINIMAL
     * @uses PCMWSEnumSideOfStreetAdherenceLevel::VALUE_MODERATE
     * @uses PCMWSEnumSideOfStreetAdherenceLevel::VALUE_AVERAGE
     * @uses PCMWSEnumSideOfStreetAdherenceLevel::VALUE_STRICT
     * @uses PCMWSEnumSideOfStreetAdherenceLevel::VALUE_ADHERE
     * @uses PCMWSEnumSideOfStreetAdherenceLevel::VALUE_STRONGLYADHERE
     * @param mixed $_value value
     * @return bool true|false
     */
    public static function valueIsValid($_value)
    {
        return in_array($_value,array(PCMWSEnumSideOfStreetAdherenceLevel::VALUE_OFF,PCMWSEnumSideOfStreetAdherenceLevel::VALUE_MINIMAL,PCMWSEnumSideOfStreetAdherenceLevel::VALUE_MODERATE,PCMWSEnumSideOfStreetAdherenceLevel::VALUE_AVERAGE,PCMWSEnumSideOfStreetAdherenceLevel::VALUE_STRICT,PCMWSEnumSideOfStreetAdherenceLevel::VALUE_ADHERE,PCMWSEnumSideOfStreetAdherenceLevel::VALUE_STRONGLYADHERE));
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
