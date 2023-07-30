<?php
/**
 * File for class PCMWSStructArrayOfCustomPlaceLocation
 * @package PCMWS
 * @subpackage Structs
 * @author WsdlToPhp Team <contact@wsdltophp.com>
 * @version 20150429-01
 * @date 2016-08-25
 */
/**
 * This class stands for PCMWSStructArrayOfCustomPlaceLocation originally named ArrayOfCustomPlaceLocation
 * Meta informations extracted from the WSDL
 * - from schema : {@link http://pcmiler.alk.com/APIs/SOAP/v1.0/Service.svc?xsd=xsd0}
 * @package PCMWS
 * @subpackage Structs
 * @author WsdlToPhp Team <contact@wsdltophp.com>
 * @version 20150429-01
 * @date 2016-08-25
 */
class PCMWSStructArrayOfCustomPlaceLocation extends PCMWSWsdlClass
{
    /**
     * The CustomPlaceLocation
     * Meta informations extracted from the WSDL
     * - maxOccurs : unbounded
     * - minOccurs : 0
     * - nillable : true
     * @var PCMWSStructCustomPlaceLocation
     */
    public $CustomPlaceLocation;
    /**
     * Constructor method for ArrayOfCustomPlaceLocation
     * @see parent::__construct()
     * @param PCMWSStructCustomPlaceLocation $_customPlaceLocation
     * @return PCMWSStructArrayOfCustomPlaceLocation
     */
    public function __construct($_customPlaceLocation = NULL)
    {
        parent::__construct(array('CustomPlaceLocation'=>$_customPlaceLocation),false);
    }
    /**
     * Get CustomPlaceLocation value
     * @return PCMWSStructCustomPlaceLocation|null
     */
    public function getCustomPlaceLocation()
    {
        return $this->CustomPlaceLocation;
    }
    /**
     * Set CustomPlaceLocation value
     * @param PCMWSStructCustomPlaceLocation $_customPlaceLocation the CustomPlaceLocation
     * @return PCMWSStructCustomPlaceLocation
     */
    public function setCustomPlaceLocation($_customPlaceLocation)
    {
        return ($this->CustomPlaceLocation = $_customPlaceLocation);
    }
    /**
     * Returns the current element
     * @see PCMWSWsdlClass::current()
     * @return PCMWSStructCustomPlaceLocation
     */
    public function current()
    {
        return parent::current();
    }
    /**
     * Returns the indexed element
     * @see PCMWSWsdlClass::item()
     * @param int $_index
     * @return PCMWSStructCustomPlaceLocation
     */
    public function item($_index)
    {
        return parent::item($_index);
    }
    /**
     * Returns the first element
     * @see PCMWSWsdlClass::first()
     * @return PCMWSStructCustomPlaceLocation
     */
    public function first()
    {
        return parent::first();
    }
    /**
     * Returns the last element
     * @see PCMWSWsdlClass::last()
     * @return PCMWSStructCustomPlaceLocation
     */
    public function last()
    {
        return parent::last();
    }
    /**
     * Returns the element at the offset
     * @see PCMWSWsdlClass::last()
     * @param int $_offset
     * @return PCMWSStructCustomPlaceLocation
     */
    public function offsetGet($_offset)
    {
        return parent::offsetGet($_offset);
    }
    /**
     * Returns the attribute name
     * @see PCMWSWsdlClass::getAttributeName()
     * @return string CustomPlaceLocation
     */
    public function getAttributeName()
    {
        return 'CustomPlaceLocation';
    }
    /**
     * Method called when an object has been exported with var_export() functions
     * It allows to return an object instantiated with the values
     * @see PCMWSWsdlClass::__set_state()
     * @uses PCMWSWsdlClass::__set_state()
     * @param array $_array the exported values
     * @return PCMWSStructArrayOfCustomPlaceLocation
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
