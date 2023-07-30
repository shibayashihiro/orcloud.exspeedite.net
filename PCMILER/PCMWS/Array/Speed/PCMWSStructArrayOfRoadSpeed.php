<?php
/**
 * File for class PCMWSStructArrayOfRoadSpeed
 * @package PCMWS
 * @subpackage Structs
 * @author WsdlToPhp Team <contact@wsdltophp.com>
 * @version 20150429-01
 * @date 2016-08-25
 */
/**
 * This class stands for PCMWSStructArrayOfRoadSpeed originally named ArrayOfRoadSpeed
 * Meta informations extracted from the WSDL
 * - from schema : {@link http://pcmiler.alk.com/APIs/SOAP/v1.0/Service.svc?xsd=xsd0}
 * @package PCMWS
 * @subpackage Structs
 * @author WsdlToPhp Team <contact@wsdltophp.com>
 * @version 20150429-01
 * @date 2016-08-25
 */
class PCMWSStructArrayOfRoadSpeed extends PCMWSWsdlClass
{
    /**
     * The RoadSpeed
     * Meta informations extracted from the WSDL
     * - maxOccurs : unbounded
     * - minOccurs : 0
     * - nillable : true
     * @var PCMWSStructRoadSpeed
     */
    public $RoadSpeed;
    /**
     * Constructor method for ArrayOfRoadSpeed
     * @see parent::__construct()
     * @param PCMWSStructRoadSpeed $_roadSpeed
     * @return PCMWSStructArrayOfRoadSpeed
     */
    public function __construct($_roadSpeed = NULL)
    {
        parent::__construct(array('RoadSpeed'=>$_roadSpeed),false);
    }
    /**
     * Get RoadSpeed value
     * @return PCMWSStructRoadSpeed|null
     */
    public function getRoadSpeed()
    {
        return $this->RoadSpeed;
    }
    /**
     * Set RoadSpeed value
     * @param PCMWSStructRoadSpeed $_roadSpeed the RoadSpeed
     * @return PCMWSStructRoadSpeed
     */
    public function setRoadSpeed($_roadSpeed)
    {
        return ($this->RoadSpeed = $_roadSpeed);
    }
    /**
     * Returns the current element
     * @see PCMWSWsdlClass::current()
     * @return PCMWSStructRoadSpeed
     */
    public function current()
    {
        return parent::current();
    }
    /**
     * Returns the indexed element
     * @see PCMWSWsdlClass::item()
     * @param int $_index
     * @return PCMWSStructRoadSpeed
     */
    public function item($_index)
    {
        return parent::item($_index);
    }
    /**
     * Returns the first element
     * @see PCMWSWsdlClass::first()
     * @return PCMWSStructRoadSpeed
     */
    public function first()
    {
        return parent::first();
    }
    /**
     * Returns the last element
     * @see PCMWSWsdlClass::last()
     * @return PCMWSStructRoadSpeed
     */
    public function last()
    {
        return parent::last();
    }
    /**
     * Returns the element at the offset
     * @see PCMWSWsdlClass::last()
     * @param int $_offset
     * @return PCMWSStructRoadSpeed
     */
    public function offsetGet($_offset)
    {
        return parent::offsetGet($_offset);
    }
    /**
     * Returns the attribute name
     * @see PCMWSWsdlClass::getAttributeName()
     * @return string RoadSpeed
     */
    public function getAttributeName()
    {
        return 'RoadSpeed';
    }
    /**
     * Method called when an object has been exported with var_export() functions
     * It allows to return an object instantiated with the values
     * @see PCMWSWsdlClass::__set_state()
     * @uses PCMWSWsdlClass::__set_state()
     * @param array $_array the exported values
     * @return PCMWSStructArrayOfRoadSpeed
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
