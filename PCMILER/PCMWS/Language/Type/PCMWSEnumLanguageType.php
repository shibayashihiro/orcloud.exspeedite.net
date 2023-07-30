<?php
/**
 * File for class PCMWSEnumLanguageType
 * @package PCMWS
 * @subpackage Enumerations
 * @author WsdlToPhp Team <contact@wsdltophp.com>
 * @version 20150429-01
 * @date 2016-08-25
 */
/**
 * This class stands for PCMWSEnumLanguageType originally named LanguageType
 * Meta informations extracted from the WSDL
 * - from schema : {@link http://pcmiler.alk.com/APIs/SOAP/v1.0/Service.svc?xsd=xsd0}
 * @package PCMWS
 * @subpackage Enumerations
 * @author WsdlToPhp Team <contact@wsdltophp.com>
 * @version 20150429-01
 * @date 2016-08-25
 */
class PCMWSEnumLanguageType extends PCMWSWsdlClass
{
    /**
     * Constant for value 'ENUS'
     * @return string 'ENUS'
     */
    const VALUE_ENUS = 'ENUS';
    /**
     * Constant for value 'ENGB'
     * @return string 'ENGB'
     */
    const VALUE_ENGB = 'ENGB';
    /**
     * Constant for value 'DE'
     * @return string 'DE'
     */
    const VALUE_DE = 'DE';
    /**
     * Constant for value 'FR'
     * @return string 'FR'
     */
    const VALUE_FR = 'FR';
    /**
     * Constant for value 'ES'
     * @return string 'ES'
     */
    const VALUE_ES = 'ES';
    /**
     * Constant for value 'IT'
     * @return string 'IT'
     */
    const VALUE_IT = 'IT';
    /**
     * Return true if value is allowed
     * @uses PCMWSEnumLanguageType::VALUE_ENUS
     * @uses PCMWSEnumLanguageType::VALUE_ENGB
     * @uses PCMWSEnumLanguageType::VALUE_DE
     * @uses PCMWSEnumLanguageType::VALUE_FR
     * @uses PCMWSEnumLanguageType::VALUE_ES
     * @uses PCMWSEnumLanguageType::VALUE_IT
     * @param mixed $_value value
     * @return bool true|false
     */
    public static function valueIsValid($_value)
    {
        return in_array($_value,array(PCMWSEnumLanguageType::VALUE_ENUS,PCMWSEnumLanguageType::VALUE_ENGB,PCMWSEnumLanguageType::VALUE_DE,PCMWSEnumLanguageType::VALUE_FR,PCMWSEnumLanguageType::VALUE_ES,PCMWSEnumLanguageType::VALUE_IT));
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
