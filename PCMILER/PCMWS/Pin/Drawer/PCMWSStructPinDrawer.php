<?php
/**
 * File for class PCMWSStructPinDrawer
 * @package PCMWS
 * @subpackage Structs
 * @author WsdlToPhp Team <contact@wsdltophp.com>
 * @version 20150429-01
 * @date 2016-08-25
 */
/**
 * This class stands for PCMWSStructPinDrawer originally named PinDrawer
 * Meta informations extracted from the WSDL
 * - from schema : {@link http://pcmiler.alk.com/APIs/SOAP/v1.0/Service.svc?xsd=xsd0}
 * @package PCMWS
 * @subpackage Structs
 * @author WsdlToPhp Team <contact@wsdltophp.com>
 * @version 20150429-01
 * @date 2016-08-25
 */
class PCMWSStructPinDrawer extends PCMWSWsdlClass
{
    /**
     * The PointGroupDensity
     * Meta informations extracted from the WSDL
     * - minOccurs : 0
     * @var PCMWSEnumPointGroupDensity
     */
    public $PointGroupDensity;
    /**
     * The PointSpreadInGroup
     * Meta informations extracted from the WSDL
     * - minOccurs : 0
     * @var PCMWSEnumPointSpread
     */
    public $PointSpreadInGroup;
    /**
     * The DrawOnMap
     * Meta informations extracted from the WSDL
     * - minOccurs : 0
     * @var boolean
     */
    public $DrawOnMap;
    /**
     * The Pins
     * Meta informations extracted from the WSDL
     * - minOccurs : 0
     * - nillable : true
     * @var PCMWSStructArrayOfPin
     */
    public $Pins;
    /**
     * Constructor method for PinDrawer
     * @see parent::__construct()
     * @param PCMWSEnumPointGroupDensity $_pointGroupDensity
     * @param PCMWSEnumPointSpread $_pointSpreadInGroup
     * @param boolean $_drawOnMap
     * @param PCMWSStructArrayOfPin $_pins
     * @return PCMWSStructPinDrawer
     */
    public function __construct($_pointGroupDensity = NULL,$_pointSpreadInGroup = NULL,$_drawOnMap = NULL,$_pins = NULL)
    {
        parent::__construct(array('PointGroupDensity'=>$_pointGroupDensity,'PointSpreadInGroup'=>$_pointSpreadInGroup,'DrawOnMap'=>$_drawOnMap,'Pins'=>($_pins instanceof PCMWSStructArrayOfPin)?$_pins:new PCMWSStructArrayOfPin($_pins)),false);
    }
    /**
     * Get PointGroupDensity value
     * @return PCMWSEnumPointGroupDensity|null
     */
    public function getPointGroupDensity()
    {
        return $this->PointGroupDensity;
    }
    /**
     * Set PointGroupDensity value
     * @uses PCMWSEnumPointGroupDensity::valueIsValid()
     * @param PCMWSEnumPointGroupDensity $_pointGroupDensity the PointGroupDensity
     * @return PCMWSEnumPointGroupDensity
     */
    public function setPointGroupDensity($_pointGroupDensity)
    {
        if(!PCMWSEnumPointGroupDensity::valueIsValid($_pointGroupDensity))
        {
            return false;
        }
        return ($this->PointGroupDensity = $_pointGroupDensity);
    }
    /**
     * Get PointSpreadInGroup value
     * @return PCMWSEnumPointSpread|null
     */
    public function getPointSpreadInGroup()
    {
        return $this->PointSpreadInGroup;
    }
    /**
     * Set PointSpreadInGroup value
     * @uses PCMWSEnumPointSpread::valueIsValid()
     * @param PCMWSEnumPointSpread $_pointSpreadInGroup the PointSpreadInGroup
     * @return PCMWSEnumPointSpread
     */
    public function setPointSpreadInGroup($_pointSpreadInGroup)
    {
        if(!PCMWSEnumPointSpread::valueIsValid($_pointSpreadInGroup))
        {
            return false;
        }
        return ($this->PointSpreadInGroup = $_pointSpreadInGroup);
    }
    /**
     * Get DrawOnMap value
     * @return boolean|null
     */
    public function getDrawOnMap()
    {
        return $this->DrawOnMap;
    }
    /**
     * Set DrawOnMap value
     * @param boolean $_drawOnMap the DrawOnMap
     * @return boolean
     */
    public function setDrawOnMap($_drawOnMap)
    {
        return ($this->DrawOnMap = $_drawOnMap);
    }
    /**
     * Get Pins value
     * @return PCMWSStructArrayOfPin|null
     */
    public function getPins()
    {
        return $this->Pins;
    }
    /**
     * Set Pins value
     * @param PCMWSStructArrayOfPin $_pins the Pins
     * @return PCMWSStructArrayOfPin
     */
    public function setPins($_pins)
    {
        return ($this->Pins = $_pins);
    }
    /**
     * Method called when an object has been exported with var_export() functions
     * It allows to return an object instantiated with the values
     * @see PCMWSWsdlClass::__set_state()
     * @uses PCMWSWsdlClass::__set_state()
     * @param array $_array the exported values
     * @return PCMWSStructPinDrawer
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
