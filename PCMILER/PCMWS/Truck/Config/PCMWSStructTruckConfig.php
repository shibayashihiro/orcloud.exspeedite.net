<?php
/**
 * File for class PCMWSStructTruckConfig
 * @package PCMWS
 * @subpackage Structs
 * @author WsdlToPhp Team <contact@wsdltophp.com>
 * @version 20150429-01
 * @date 2016-08-25
 */
/**
 * This class stands for PCMWSStructTruckConfig originally named TruckConfig
 * Meta informations extracted from the WSDL
 * - from schema : {@link http://pcmiler.alk.com/APIs/SOAP/v1.0/Service.svc?xsd=xsd0}
 * @package PCMWS
 * @subpackage Structs
 * @author WsdlToPhp Team <contact@wsdltophp.com>
 * @version 20150429-01
 * @date 2016-08-25
 */
class PCMWSStructTruckConfig extends PCMWSWsdlClass
{
    /**
     * The Axles
     * Meta informations extracted from the WSDL
     * - minOccurs : 0
     * @var int
     */
    public $Axles;
    /**
     * The Height
     * Meta informations extracted from the WSDL
     * - minOccurs : 0
     * - nillable : true
     * @var string
     */
    public $Height;
    /**
     * The LCV
     * Meta informations extracted from the WSDL
     * - minOccurs : 0
     * @var boolean
     */
    public $LCV;
    /**
     * The Length
     * Meta informations extracted from the WSDL
     * - minOccurs : 0
     * - nillable : true
     * @var string
     */
    public $Length;
    /**
     * The Units
     * Meta informations extracted from the WSDL
     * - minOccurs : 0
     * @var PCMWSEnumVehicleDimUnits
     */
    public $Units;
    /**
     * The Weight
     * Meta informations extracted from the WSDL
     * - minOccurs : 0
     * - nillable : true
     * @var string
     */
    public $Weight;
    /**
     * The Width
     * Meta informations extracted from the WSDL
     * - minOccurs : 0
     * - nillable : true
     * @var string
     */
    public $Width;
    /**
     * Constructor method for TruckConfig
     * @see parent::__construct()
     * @param int $_axles
     * @param string $_height
     * @param boolean $_lCV
     * @param string $_length
     * @param PCMWSEnumVehicleDimUnits $_units
     * @param string $_weight
     * @param string $_width
     * @return PCMWSStructTruckConfig
     */
    public function __construct($_axles = NULL,$_height = NULL,$_lCV = NULL,$_length = NULL,$_units = NULL,$_weight = NULL,$_width = NULL)
    {
        parent::__construct(array('Axles'=>$_axles,'Height'=>$_height,'LCV'=>$_lCV,'Length'=>$_length,'Units'=>$_units,'Weight'=>$_weight,'Width'=>$_width),false);
    }
    /**
     * Get Axles value
     * @return int|null
     */
    public function getAxles()
    {
        return $this->Axles;
    }
    /**
     * Set Axles value
     * @param int $_axles the Axles
     * @return int
     */
    public function setAxles($_axles)
    {
        return ($this->Axles = $_axles);
    }
    /**
     * Get Height value
     * @return string|null
     */
    public function getHeight()
    {
        return $this->Height;
    }
    /**
     * Set Height value
     * @param string $_height the Height
     * @return string
     */
    public function setHeight($_height)
    {
        return ($this->Height = $_height);
    }
    /**
     * Get LCV value
     * @return boolean|null
     */
    public function getLCV()
    {
        return $this->LCV;
    }
    /**
     * Set LCV value
     * @param boolean $_lCV the LCV
     * @return boolean
     */
    public function setLCV($_lCV)
    {
        return ($this->LCV = $_lCV);
    }
    /**
     * Get Length value
     * @return string|null
     */
    public function getLength()
    {
        return $this->Length;
    }
    /**
     * Set Length value
     * @param string $_length the Length
     * @return string
     */
    public function setLength($_length)
    {
        return ($this->Length = $_length);
    }
    /**
     * Get Units value
     * @return PCMWSEnumVehicleDimUnits|null
     */
    public function getUnits()
    {
        return $this->Units;
    }
    /**
     * Set Units value
     * @uses PCMWSEnumVehicleDimUnits::valueIsValid()
     * @param PCMWSEnumVehicleDimUnits $_units the Units
     * @return PCMWSEnumVehicleDimUnits
     */
    public function setUnits($_units)
    {
        if(!PCMWSEnumVehicleDimUnits::valueIsValid($_units))
        {
            return false;
        }
        return ($this->Units = $_units);
    }
    /**
     * Get Weight value
     * @return string|null
     */
    public function getWeight()
    {
        return $this->Weight;
    }
    /**
     * Set Weight value
     * @param string $_weight the Weight
     * @return string
     */
    public function setWeight($_weight)
    {
        return ($this->Weight = $_weight);
    }
    /**
     * Get Width value
     * @return string|null
     */
    public function getWidth()
    {
        return $this->Width;
    }
    /**
     * Set Width value
     * @param string $_width the Width
     * @return string
     */
    public function setWidth($_width)
    {
        return ($this->Width = $_width);
    }
    /**
     * Method called when an object has been exported with var_export() functions
     * It allows to return an object instantiated with the values
     * @see PCMWSWsdlClass::__set_state()
     * @uses PCMWSWsdlClass::__set_state()
     * @param array $_array the exported values
     * @return PCMWSStructTruckConfig
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
