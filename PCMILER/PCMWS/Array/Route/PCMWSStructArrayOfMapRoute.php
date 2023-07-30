<?php
/**
 * File for class PCMWSStructArrayOfMapRoute
 * @package PCMWS
 * @subpackage Structs
 * @author WsdlToPhp Team <contact@wsdltophp.com>
 * @version 20150429-01
 * @date 2016-08-25
 */
/**
 * This class stands for PCMWSStructArrayOfMapRoute originally named ArrayOfMapRoute
 * Meta informations extracted from the WSDL
 * - from schema : {@link http://pcmiler.alk.com/APIs/SOAP/v1.0/Service.svc?xsd=xsd0}
 * @package PCMWS
 * @subpackage Structs
 * @author WsdlToPhp Team <contact@wsdltophp.com>
 * @version 20150429-01
 * @date 2016-08-25
 */
class PCMWSStructArrayOfMapRoute extends PCMWSWsdlClass
{
    /**
     * The MapRoute
     * Meta informations extracted from the WSDL
     * - maxOccurs : unbounded
     * - minOccurs : 0
     * - nillable : true
     * @var PCMWSStructMapRoute
     */
    public $MapRoute;
    /**
     * Constructor method for ArrayOfMapRoute
     * @see parent::__construct()
     * @param PCMWSStructMapRoute $_mapRoute
     * @return PCMWSStructArrayOfMapRoute
     */
    public function __construct($_mapRoute = NULL)
    {
        parent::__construct(array('MapRoute'=>$_mapRoute),false);
    }
    /**
     * Get MapRoute value
     * @return PCMWSStructMapRoute|null
     */
    public function getMapRoute()
    {
        return $this->MapRoute;
    }
    /**
     * Set MapRoute value
     * @param PCMWSStructMapRoute $_mapRoute the MapRoute
     * @return PCMWSStructMapRoute
     */
    public function setMapRoute($_mapRoute)
    {
        return ($this->MapRoute = $_mapRoute);
    }
    /**
     * Returns the current element
     * @see PCMWSWsdlClass::current()
     * @return PCMWSStructMapRoute
     */
    public function current()
    {
        return parent::current();
    }
    /**
     * Returns the indexed element
     * @see PCMWSWsdlClass::item()
     * @param int $_index
     * @return PCMWSStructMapRoute
     */
    public function item($_index)
    {
        return parent::item($_index);
    }
    /**
     * Returns the first element
     * @see PCMWSWsdlClass::first()
     * @return PCMWSStructMapRoute
     */
    public function first()
    {
        return parent::first();
    }
    /**
     * Returns the last element
     * @see PCMWSWsdlClass::last()
     * @return PCMWSStructMapRoute
     */
    public function last()
    {
        return parent::last();
    }
    /**
     * Returns the element at the offset
     * @see PCMWSWsdlClass::last()
     * @param int $_offset
     * @return PCMWSStructMapRoute
     */
    public function offsetGet($_offset)
    {
        return parent::offsetGet($_offset);
    }
    /**
     * Returns the attribute name
     * @see PCMWSWsdlClass::getAttributeName()
     * @return string MapRoute
     */
    public function getAttributeName()
    {
        return 'MapRoute';
    }
    /**
     * Method called when an object has been exported with var_export() functions
     * It allows to return an object instantiated with the values
     * @see PCMWSWsdlClass::__set_state()
     * @uses PCMWSWsdlClass::__set_state()
     * @param array $_array the exported values
     * @return PCMWSStructArrayOfMapRoute
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
