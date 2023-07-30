<?php
/**
 * File for class PCMWSStructSpeedLimitOptions
 * @package PCMWS
 * @subpackage Structs
 * @author WsdlToPhp Team <contact@wsdltophp.com>
 * @version 20150429-01
 * @date 2016-08-25
 */
/**
 * This class stands for PCMWSStructSpeedLimitOptions originally named SpeedLimitOptions
 * Meta informations extracted from the WSDL
 * - from schema : {@link http://pcmiler.alk.com/APIs/SOAP/v1.0/Service.svc?xsd=xsd0}
 * @package PCMWS
 * @subpackage Structs
 * @author WsdlToPhp Team <contact@wsdltophp.com>
 * @version 20150429-01
 * @date 2016-08-25
 */
class PCMWSStructSpeedLimitOptions extends PCMWSWsdlClass
{
    /**
     * The Heading
     * Meta informations extracted from the WSDL
     * - minOccurs : 0
     * - nillable : true
     * @var double
     */
    public $Heading;
    /**
     * The CurrentSpeed
     * Meta informations extracted from the WSDL
     * - minOccurs : 0
     * - nillable : true
     * @var int
     */
    public $CurrentSpeed;
    /**
     * The Vehicle
     * Meta informations extracted from the WSDL
     * - minOccurs : 0
     * @var PCMWSEnumVehicleType
     */
    public $Vehicle;
    /**
     * The Urban
     * Meta informations extracted from the WSDL
     * - minOccurs : 0
     * @var boolean
     */
    public $Urban;
    /**
     * Constructor method for SpeedLimitOptions
     * @see parent::__construct()
     * @param double $_heading
     * @param int $_currentSpeed
     * @param PCMWSEnumVehicleType $_vehicle
     * @param boolean $_urban
     * @return PCMWSStructSpeedLimitOptions
     */
    public function __construct($_heading = NULL,$_currentSpeed = NULL,$_vehicle = NULL,$_urban = NULL)
    {
        parent::__construct(array('Heading'=>$_heading,'CurrentSpeed'=>$_currentSpeed,'Vehicle'=>$_vehicle,'Urban'=>$_urban),false);
    }
    /**
     * Get Heading value
     * @return double|null
     */
    public function getHeading()
    {
        return $this->Heading;
    }
    /**
     * Set Heading value
     * @param double $_heading the Heading
     * @return double
     */
    public function setHeading($_heading)
    {
        return ($this->Heading = $_heading);
    }
    /**
     * Get CurrentSpeed value
     * @return int|null
     */
    public function getCurrentSpeed()
    {
        return $this->CurrentSpeed;
    }
    /**
     * Set CurrentSpeed value
     * @param int $_currentSpeed the CurrentSpeed
     * @return int
     */
    public function setCurrentSpeed($_currentSpeed)
    {
        return ($this->CurrentSpeed = $_currentSpeed);
    }
    /**
     * Get Vehicle value
     * @return PCMWSEnumVehicleType|null
     */
    public function getVehicle()
    {
        return $this->Vehicle;
    }
    /**
     * Set Vehicle value
     * @uses PCMWSEnumVehicleType::valueIsValid()
     * @param PCMWSEnumVehicleType $_vehicle the Vehicle
     * @return PCMWSEnumVehicleType
     */
    public function setVehicle($_vehicle)
    {
        if(!PCMWSEnumVehicleType::valueIsValid($_vehicle))
        {
            return false;
        }
        return ($this->Vehicle = $_vehicle);
    }
    /**
     * Get Urban value
     * @return boolean|null
     */
    public function getUrban()
    {
        return $this->Urban;
    }
    /**
     * Set Urban value
     * @param boolean $_urban the Urban
     * @return boolean
     */
    public function setUrban($_urban)
    {
        return ($this->Urban = $_urban);
    }
    /**
     * Method called when an object has been exported with var_export() functions
     * It allows to return an object instantiated with the values
     * @see PCMWSWsdlClass::__set_state()
     * @uses PCMWSWsdlClass::__set_state()
     * @param array $_array the exported values
     * @return PCMWSStructSpeedLimitOptions
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
