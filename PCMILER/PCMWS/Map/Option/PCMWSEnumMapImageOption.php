<?php
/**
 * File for class PCMWSEnumMapImageOption
 * @package PCMWS
 * @subpackage Enumerations
 * @author WsdlToPhp Team <contact@wsdltophp.com>
 * @version 20150429-01
 * @date 2016-08-25
 */
/**
 * This class stands for PCMWSEnumMapImageOption originally named MapImageOption
 * Meta informations extracted from the WSDL
 * - from schema : {@link http://pcmiler.alk.com/APIs/SOAP/v1.0/Service.svc?xsd=xsd0}
 * @package PCMWS
 * @subpackage Enumerations
 * @author WsdlToPhp Team <contact@wsdltophp.com>
 * @version 20150429-01
 * @date 2016-08-25
 */
class PCMWSEnumMapImageOption extends PCMWSWsdlClass
{
    /**
     * Constant for value 'Both'
     * @return string 'Both'
     */
    const VALUE_BOTH = 'Both';
    /**
     * Constant for value 'Background'
     * @return string 'Background'
     */
    const VALUE_BACKGROUND = 'Background';
    /**
     * Constant for value 'Foreground'
     * @return string 'Foreground'
     */
    const VALUE_FOREGROUND = 'Foreground';
    /**
     * Return true if value is allowed
     * @uses PCMWSEnumMapImageOption::VALUE_BOTH
     * @uses PCMWSEnumMapImageOption::VALUE_BACKGROUND
     * @uses PCMWSEnumMapImageOption::VALUE_FOREGROUND
     * @param mixed $_value value
     * @return bool true|false
     */
    public static function valueIsValid($_value)
    {
        return in_array($_value,array(PCMWSEnumMapImageOption::VALUE_BOTH,PCMWSEnumMapImageOption::VALUE_BACKGROUND,PCMWSEnumMapImageOption::VALUE_FOREGROUND));
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
