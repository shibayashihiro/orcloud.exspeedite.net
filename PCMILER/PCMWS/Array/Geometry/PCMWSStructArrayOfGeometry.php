<?php
/**
 * File for class PCMWSStructArrayOfGeometry
 * @package PCMWS
 * @subpackage Structs
 * @author WsdlToPhp Team <contact@wsdltophp.com>
 * @version 20150429-01
 * @date 2016-08-25
 */
/**
 * This class stands for PCMWSStructArrayOfGeometry originally named ArrayOfGeometry
 * Meta informations extracted from the WSDL
 * - from schema : {@link http://pcmiler.alk.com/APIs/SOAP/v1.0/Service.svc?xsd=xsd0}
 * @package PCMWS
 * @subpackage Structs
 * @author WsdlToPhp Team <contact@wsdltophp.com>
 * @version 20150429-01
 * @date 2016-08-25
 */
class PCMWSStructArrayOfGeometry extends PCMWSWsdlClass
{
    /**
     * The Geometry
     * Meta informations extracted from the WSDL
     * - maxOccurs : unbounded
     * - minOccurs : 0
     * - nillable : true
     * @var PCMWSStructGeometry
     */
    public $Geometry;
    /**
     * Constructor method for ArrayOfGeometry
     * @see parent::__construct()
     * @param PCMWSStructGeometry $_geometry
     * @return PCMWSStructArrayOfGeometry
     */
    public function __construct($_geometry = NULL)
    {
        parent::__construct(array('Geometry'=>$_geometry),false);
    }
    /**
     * Get Geometry value
     * @return PCMWSStructGeometry|null
     */
    public function getGeometry()
    {
        return $this->Geometry;
    }
    /**
     * Set Geometry value
     * @param PCMWSStructGeometry $_geometry the Geometry
     * @return PCMWSStructGeometry
     */
    public function setGeometry($_geometry)
    {
        return ($this->Geometry = $_geometry);
    }
    /**
     * Returns the current element
     * @see PCMWSWsdlClass::current()
     * @return PCMWSStructGeometry
     */
    public function current()
    {
        return parent::current();
    }
    /**
     * Returns the indexed element
     * @see PCMWSWsdlClass::item()
     * @param int $_index
     * @return PCMWSStructGeometry
     */
    public function item($_index)
    {
        return parent::item($_index);
    }
    /**
     * Returns the first element
     * @see PCMWSWsdlClass::first()
     * @return PCMWSStructGeometry
     */
    public function first()
    {
        return parent::first();
    }
    /**
     * Returns the last element
     * @see PCMWSWsdlClass::last()
     * @return PCMWSStructGeometry
     */
    public function last()
    {
        return parent::last();
    }
    /**
     * Returns the element at the offset
     * @see PCMWSWsdlClass::last()
     * @param int $_offset
     * @return PCMWSStructGeometry
     */
    public function offsetGet($_offset)
    {
        return parent::offsetGet($_offset);
    }
    /**
     * Returns the attribute name
     * @see PCMWSWsdlClass::getAttributeName()
     * @return string Geometry
     */
    public function getAttributeName()
    {
        return 'Geometry';
    }
    /**
     * Method called when an object has been exported with var_export() functions
     * It allows to return an object instantiated with the values
     * @see PCMWSWsdlClass::__set_state()
     * @uses PCMWSWsdlClass::__set_state()
     * @param array $_array the exported values
     * @return PCMWSStructArrayOfGeometry
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
