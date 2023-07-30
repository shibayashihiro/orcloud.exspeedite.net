<?php
/**
 * File for class PCMWSStructSpeedLimit
 * @package PCMWS
 * @subpackage Structs
 * @author WsdlToPhp Team <contact@wsdltophp.com>
 * @version 20150429-01
 * @date 2016-08-25
 */
/**
 * This class stands for PCMWSStructSpeedLimit originally named SpeedLimit
 * Meta informations extracted from the WSDL
 * - from schema : {@link http://pcmiler.alk.com/APIs/SOAP/v1.0/Service.svc?xsd=xsd0}
 * @package PCMWS
 * @subpackage Structs
 * @author WsdlToPhp Team <contact@wsdltophp.com>
 * @version 20150429-01
 * @date 2016-08-25
 */
class PCMWSStructSpeedLimit extends PCMWSWsdlClass
{
    /**
     * The Speed
     * Meta informations extracted from the WSDL
     * - minOccurs : 0
     * @var int
     */
    public $Speed;
    /**
     * The SpeedType
     * Meta informations extracted from the WSDL
     * - minOccurs : 0
     * @var PCMWSEnumSpeedLimitType
     */
    public $SpeedType;
    /**
     * The SpeedTypeString
     * Meta informations extracted from the WSDL
     * - minOccurs : 0
     * - nillable : true
     * @var string
     */
    public $SpeedTypeString;
    /**
     * Constructor method for SpeedLimit
     * @see parent::__construct()
     * @param int $_speed
     * @param PCMWSEnumSpeedLimitType $_speedType
     * @param string $_speedTypeString
     * @return PCMWSStructSpeedLimit
     */
    public function __construct($_speed = NULL,$_speedType = NULL,$_speedTypeString = NULL)
    {
        parent::__construct(array('Speed'=>$_speed,'SpeedType'=>$_speedType,'SpeedTypeString'=>$_speedTypeString),false);
    }
    /**
     * Get Speed value
     * @return int|null
     */
    public function getSpeed()
    {
        return $this->Speed;
    }
    /**
     * Set Speed value
     * @param int $_speed the Speed
     * @return int
     */
    public function setSpeed($_speed)
    {
        return ($this->Speed = $_speed);
    }
    /**
     * Get SpeedType value
     * @return PCMWSEnumSpeedLimitType|null
     */
    public function getSpeedType()
    {
        return $this->SpeedType;
    }
    /**
     * Set SpeedType value
     * @uses PCMWSEnumSpeedLimitType::valueIsValid()
     * @param PCMWSEnumSpeedLimitType $_speedType the SpeedType
     * @return PCMWSEnumSpeedLimitType
     */
    public function setSpeedType($_speedType)
    {
        if(!PCMWSEnumSpeedLimitType::valueIsValid($_speedType))
        {
            return false;
        }
        return ($this->SpeedType = $_speedType);
    }
    /**
     * Get SpeedTypeString value
     * @return string|null
     */
    public function getSpeedTypeString()
    {
        return $this->SpeedTypeString;
    }
    /**
     * Set SpeedTypeString value
     * @param string $_speedTypeString the SpeedTypeString
     * @return string
     */
    public function setSpeedTypeString($_speedTypeString)
    {
        return ($this->SpeedTypeString = $_speedTypeString);
    }
    /**
     * Method called when an object has been exported with var_export() functions
     * It allows to return an object instantiated with the values
     * @see PCMWSWsdlClass::__set_state()
     * @uses PCMWSWsdlClass::__set_state()
     * @param array $_array the exported values
     * @return PCMWSStructSpeedLimit
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
