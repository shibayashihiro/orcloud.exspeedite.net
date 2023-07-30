<?php
/**
 * File for class PCMWSStructFuelStopLine
 * @package PCMWS
 * @subpackage Structs
 * @author WsdlToPhp Team <contact@wsdltophp.com>
 * @version 20150429-01
 * @date 2016-08-25
 */
/**
 * This class stands for PCMWSStructFuelStopLine originally named FuelStopLine
 * Meta informations extracted from the WSDL
 * - from schema : {@link http://pcmiler.alk.com/APIs/SOAP/v1.0/Service.svc?xsd=xsd0}
 * @package PCMWS
 * @subpackage Structs
 * @author WsdlToPhp Team <contact@wsdltophp.com>
 * @version 20150429-01
 * @date 2016-08-25
 */
class PCMWSStructFuelStopLine extends PCMWSWsdlClass
{
    /**
     * The FuelLocation
     * Meta informations extracted from the WSDL
     * - minOccurs : 0
     * - nillable : true
     * @var PCMWSStructGeocodeOutputLocation
     */
    public $FuelLocation;
    /**
     * The FuelPur
     * Meta informations extracted from the WSDL
     * - minOccurs : 0
     * - nillable : true
     * @var string
     */
    public $FuelPur;
    /**
     * The FuelCost
     * Meta informations extracted from the WSDL
     * - minOccurs : 0
     * - nillable : true
     * @var string
     */
    public $FuelCost;
    /**
     * The FuelFill
     * Meta informations extracted from the WSDL
     * - minOccurs : 0
     * - nillable : true
     * @var string
     */
    public $FuelFill;
    /**
     * Constructor method for FuelStopLine
     * @see parent::__construct()
     * @param PCMWSStructGeocodeOutputLocation $_fuelLocation
     * @param string $_fuelPur
     * @param string $_fuelCost
     * @param string $_fuelFill
     * @return PCMWSStructFuelStopLine
     */
    public function __construct($_fuelLocation = NULL,$_fuelPur = NULL,$_fuelCost = NULL,$_fuelFill = NULL)
    {
        parent::__construct(array('FuelLocation'=>$_fuelLocation,'FuelPur'=>$_fuelPur,'FuelCost'=>$_fuelCost,'FuelFill'=>$_fuelFill),false);
    }
    /**
     * Get FuelLocation value
     * @return PCMWSStructGeocodeOutputLocation|null
     */
    public function getFuelLocation()
    {
        return $this->FuelLocation;
    }
    /**
     * Set FuelLocation value
     * @param PCMWSStructGeocodeOutputLocation $_fuelLocation the FuelLocation
     * @return PCMWSStructGeocodeOutputLocation
     */
    public function setFuelLocation($_fuelLocation)
    {
        return ($this->FuelLocation = $_fuelLocation);
    }
    /**
     * Get FuelPur value
     * @return string|null
     */
    public function getFuelPur()
    {
        return $this->FuelPur;
    }
    /**
     * Set FuelPur value
     * @param string $_fuelPur the FuelPur
     * @return string
     */
    public function setFuelPur($_fuelPur)
    {
        return ($this->FuelPur = $_fuelPur);
    }
    /**
     * Get FuelCost value
     * @return string|null
     */
    public function getFuelCost()
    {
        return $this->FuelCost;
    }
    /**
     * Set FuelCost value
     * @param string $_fuelCost the FuelCost
     * @return string
     */
    public function setFuelCost($_fuelCost)
    {
        return ($this->FuelCost = $_fuelCost);
    }
    /**
     * Get FuelFill value
     * @return string|null
     */
    public function getFuelFill()
    {
        return $this->FuelFill;
    }
    /**
     * Set FuelFill value
     * @param string $_fuelFill the FuelFill
     * @return string
     */
    public function setFuelFill($_fuelFill)
    {
        return ($this->FuelFill = $_fuelFill);
    }
    /**
     * Method called when an object has been exported with var_export() functions
     * It allows to return an object instantiated with the values
     * @see PCMWSWsdlClass::__set_state()
     * @uses PCMWSWsdlClass::__set_state()
     * @param array $_array the exported values
     * @return PCMWSStructFuelStopLine
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
