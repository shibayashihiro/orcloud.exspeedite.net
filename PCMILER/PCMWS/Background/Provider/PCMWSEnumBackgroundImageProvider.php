<?php
/**
 * File for class PCMWSEnumBackgroundImageProvider
 * @package PCMWS
 * @subpackage Enumerations
 * @author WsdlToPhp Team <contact@wsdltophp.com>
 * @version 20150429-01
 * @date 2016-08-25
 */
/**
 * This class stands for PCMWSEnumBackgroundImageProvider originally named BackgroundImageProvider
 * Meta informations extracted from the WSDL
 * - from schema : {@link http://pcmiler.alk.com/APIs/SOAP/v1.0/Service.svc?xsd=xsd0}
 * @package PCMWS
 * @subpackage Enumerations
 * @author WsdlToPhp Team <contact@wsdltophp.com>
 * @version 20150429-01
 * @date 2016-08-25
 */
class PCMWSEnumBackgroundImageProvider extends PCMWSWsdlClass
{
    /**
     * Constant for value 'Default'
     * @return string 'Default'
     */
    const VALUE_DEFAULT = 'Default';
    /**
     * Constant for value 'Sat1'
     * @return string 'Sat1'
     */
    const VALUE_SAT1 = 'Sat1';
    /**
     * Constant for value 'Sat2'
     * @return string 'Sat2'
     */
    const VALUE_SAT2 = 'Sat2';
    /**
     * Constant for value 'Sat3'
     * @return string 'Sat3'
     */
    const VALUE_SAT3 = 'Sat3';
    /**
     * Constant for value 'Sat4'
     * @return string 'Sat4'
     */
    const VALUE_SAT4 = 'Sat4';
    /**
     * Constant for value 'Sat5'
     * @return string 'Sat5'
     */
    const VALUE_SAT5 = 'Sat5';
    /**
     * Constant for value 'Sat6'
     * @return string 'Sat6'
     */
    const VALUE_SAT6 = 'Sat6';
    /**
     * Return true if value is allowed
     * @uses PCMWSEnumBackgroundImageProvider::VALUE_DEFAULT
     * @uses PCMWSEnumBackgroundImageProvider::VALUE_SAT1
     * @uses PCMWSEnumBackgroundImageProvider::VALUE_SAT2
     * @uses PCMWSEnumBackgroundImageProvider::VALUE_SAT3
     * @uses PCMWSEnumBackgroundImageProvider::VALUE_SAT4
     * @uses PCMWSEnumBackgroundImageProvider::VALUE_SAT5
     * @uses PCMWSEnumBackgroundImageProvider::VALUE_SAT6
     * @param mixed $_value value
     * @return bool true|false
     */
    public static function valueIsValid($_value)
    {
        return in_array($_value,array(PCMWSEnumBackgroundImageProvider::VALUE_DEFAULT,PCMWSEnumBackgroundImageProvider::VALUE_SAT1,PCMWSEnumBackgroundImageProvider::VALUE_SAT2,PCMWSEnumBackgroundImageProvider::VALUE_SAT3,PCMWSEnumBackgroundImageProvider::VALUE_SAT4,PCMWSEnumBackgroundImageProvider::VALUE_SAT5,PCMWSEnumBackgroundImageProvider::VALUE_SAT6));
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
