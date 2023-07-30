<?php
/**
 * File for class PCMWSEnumPointGroupDensity
 * @package PCMWS
 * @subpackage Enumerations
 * @author WsdlToPhp Team <contact@wsdltophp.com>
 * @version 20150429-01
 * @date 2016-08-25
 */
/**
 * This class stands for PCMWSEnumPointGroupDensity originally named PointGroupDensity
 * Meta informations extracted from the WSDL
 * - from schema : {@link http://pcmiler.alk.com/APIs/SOAP/v1.0/Service.svc?xsd=xsd0}
 * @package PCMWS
 * @subpackage Enumerations
 * @author WsdlToPhp Team <contact@wsdltophp.com>
 * @version 20150429-01
 * @date 2016-08-25
 */
class PCMWSEnumPointGroupDensity extends PCMWSWsdlClass
{
    /**
     * Constant for value 'Few'
     * @return string 'Few'
     */
    const VALUE_FEW = 'Few';
    /**
     * Constant for value 'Average'
     * @return string 'Average'
     */
    const VALUE_AVERAGE = 'Average';
    /**
     * Constant for value 'Most'
     * @return string 'Most'
     */
    const VALUE_MOST = 'Most';
    /**
     * Return true if value is allowed
     * @uses PCMWSEnumPointGroupDensity::VALUE_FEW
     * @uses PCMWSEnumPointGroupDensity::VALUE_AVERAGE
     * @uses PCMWSEnumPointGroupDensity::VALUE_MOST
     * @param mixed $_value value
     * @return bool true|false
     */
    public static function valueIsValid($_value)
    {
        return in_array($_value,array(PCMWSEnumPointGroupDensity::VALUE_FEW,PCMWSEnumPointGroupDensity::VALUE_AVERAGE,PCMWSEnumPointGroupDensity::VALUE_MOST));
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
