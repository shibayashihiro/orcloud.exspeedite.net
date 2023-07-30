<?php
/**
 * File for class PCMWSStructDistance
 * @package PCMWS
 * @subpackage Structs
 * @author WsdlToPhp Team <contact@wsdltophp.com>
 * @version 20150429-01
 * @date 2016-08-25
 */
/**
 * This class stands for PCMWSStructDistance originally named Distance
 * Meta informations extracted from the WSDL
 * - from schema : {@link http://pcmiler.alk.com/APIs/SOAP/v1.0/Service.svc?xsd=xsd0}
 * @package PCMWS
 * @subpackage Structs
 * @author WsdlToPhp Team <contact@wsdltophp.com>
 * @version 20150429-01
 * @date 2016-08-25
 */
class PCMWSStructDistance extends PCMWSWsdlClass
{
    /**
     * The Value
     * Meta informations extracted from the WSDL
     * - minOccurs : 0
     * @var double
     */
    public $Value;
    /**
     * The DistanceUnits
     * Meta informations extracted from the WSDL
     * - minOccurs : 0
     * @var PCMWSEnumDistanceUnits
     */
    public $DistanceUnits;
    /**
     * Constructor method for Distance
     * @see parent::__construct()
     * @param double $_value
     * @param PCMWSEnumDistanceUnits $_distanceUnits
     * @return PCMWSStructDistance
     */
    public function __construct($_value = NULL,$_distanceUnits = NULL)
    {
        parent::__construct(array('Value'=>$_value,'DistanceUnits'=>$_distanceUnits),false);
    }
    /**
     * Get Value value
     * @return double|null
     */
    public function getValue()
    {
        return $this->Value;
    }
    /**
     * Set Value value
     * @param double $_value the Value
     * @return double
     */
    public function setValue($_value)
    {
        return ($this->Value = $_value);
    }
    /**
     * Get DistanceUnits value
     * @return PCMWSEnumDistanceUnits|null
     */
    public function getDistanceUnits()
    {
        return $this->DistanceUnits;
    }
    /**
     * Set DistanceUnits value
     * @uses PCMWSEnumDistanceUnits::valueIsValid()
     * @param PCMWSEnumDistanceUnits $_distanceUnits the DistanceUnits
     * @return PCMWSEnumDistanceUnits
     */
    public function setDistanceUnits($_distanceUnits)
    {
        if(!PCMWSEnumDistanceUnits::valueIsValid($_distanceUnits))
        {
            return false;
        }
        return ($this->DistanceUnits = $_distanceUnits);
    }
    /**
     * Method called when an object has been exported with var_export() functions
     * It allows to return an object instantiated with the values
     * @see PCMWSWsdlClass::__set_state()
     * @uses PCMWSWsdlClass::__set_state()
     * @param array $_array the exported values
     * @return PCMWSStructDistance
     */
    public static function __set_state(array $_array)
    {
	    $_array[] = __CLASS__;
        return parent::__set_state($_array);
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
