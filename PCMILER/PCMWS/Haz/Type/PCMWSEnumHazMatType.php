<?php
/**
 * File for class PCMWSEnumHazMatType
 * @package PCMWS
 * @subpackage Enumerations
 * @author WsdlToPhp Team <contact@wsdltophp.com>
 * @version 20150429-01
 * @date 2016-08-25
 */
/**
 * This class stands for PCMWSEnumHazMatType originally named HazMatType
 * Meta informations extracted from the WSDL
 * - from schema : {@link http://pcmiler.alk.com/APIs/SOAP/v1.0/Service.svc?xsd=xsd0}
 * @package PCMWS
 * @subpackage Enumerations
 * @author WsdlToPhp Team <contact@wsdltophp.com>
 * @version 20150429-01
 * @date 2016-08-25
 */
class PCMWSEnumHazMatType extends PCMWSWsdlClass
{
    /**
     * Constant for value 'None'
     * @return string 'None'
     */
    const VALUE_NONE = 'None';
    /**
     * Constant for value 'General'
     * @return string 'General'
     */
    const VALUE_GENERAL = 'General';
    /**
     * Constant for value 'Caustic'
     * @return string 'Caustic'
     */
    const VALUE_CAUSTIC = 'Caustic';
    /**
     * Constant for value 'Explosives'
     * @return string 'Explosives'
     */
    const VALUE_EXPLOSIVES = 'Explosives';
    /**
     * Constant for value 'Flammable'
     * @return string 'Flammable'
     */
    const VALUE_FLAMMABLE = 'Flammable';
    /**
     * Constant for value 'Inhalants'
     * @return string 'Inhalants'
     */
    const VALUE_INHALANTS = 'Inhalants';
    /**
     * Constant for value 'Radioactive'
     * @return string 'Radioactive'
     */
    const VALUE_RADIOACTIVE = 'Radioactive';
    /**
     * Return true if value is allowed
     * @uses PCMWSEnumHazMatType::VALUE_NONE
     * @uses PCMWSEnumHazMatType::VALUE_GENERAL
     * @uses PCMWSEnumHazMatType::VALUE_CAUSTIC
     * @uses PCMWSEnumHazMatType::VALUE_EXPLOSIVES
     * @uses PCMWSEnumHazMatType::VALUE_FLAMMABLE
     * @uses PCMWSEnumHazMatType::VALUE_INHALANTS
     * @uses PCMWSEnumHazMatType::VALUE_RADIOACTIVE
     * @param mixed $_value value
     * @return bool true|false
     */
    public static function valueIsValid($_value)
    {
        return in_array($_value,array(PCMWSEnumHazMatType::VALUE_NONE,PCMWSEnumHazMatType::VALUE_GENERAL,PCMWSEnumHazMatType::VALUE_CAUSTIC,PCMWSEnumHazMatType::VALUE_EXPLOSIVES,PCMWSEnumHazMatType::VALUE_FLAMMABLE,PCMWSEnumHazMatType::VALUE_INHALANTS,PCMWSEnumHazMatType::VALUE_RADIOACTIVE));
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
