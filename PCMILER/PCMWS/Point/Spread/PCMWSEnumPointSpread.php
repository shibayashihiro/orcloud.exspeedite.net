<?php
/**
 * File for class PCMWSEnumPointSpread
 * @package PCMWS
 * @subpackage Enumerations
 * @author WsdlToPhp Team <contact@wsdltophp.com>
 * @version 20150429-01
 * @date 2016-08-25
 */
/**
 * This class stands for PCMWSEnumPointSpread originally named PointSpread
 * Meta informations extracted from the WSDL
 * - from schema : {@link http://pcmiler.alk.com/APIs/SOAP/v1.0/Service.svc?xsd=xsd0}
 * @package PCMWS
 * @subpackage Enumerations
 * @author WsdlToPhp Team <contact@wsdltophp.com>
 * @version 20150429-01
 * @date 2016-08-25
 */
class PCMWSEnumPointSpread extends PCMWSWsdlClass
{
    /**
     * Constant for value 'Most'
     * @return string 'Most'
     */
    const VALUE_MOST = 'Most';
    /**
     * Constant for value 'Average'
     * @return string 'Average'
     */
    const VALUE_AVERAGE = 'Average';
    /**
     * Constant for value 'Cluster'
     * @return string 'Cluster'
     */
    const VALUE_CLUSTER = 'Cluster';
    /**
     * Return true if value is allowed
     * @uses PCMWSEnumPointSpread::VALUE_MOST
     * @uses PCMWSEnumPointSpread::VALUE_AVERAGE
     * @uses PCMWSEnumPointSpread::VALUE_CLUSTER
     * @param mixed $_value value
     * @return bool true|false
     */
    public static function valueIsValid($_value)
    {
        return in_array($_value,array(PCMWSEnumPointSpread::VALUE_MOST,PCMWSEnumPointSpread::VALUE_AVERAGE,PCMWSEnumPointSpread::VALUE_CLUSTER));
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
