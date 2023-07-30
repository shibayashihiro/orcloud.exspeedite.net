<?php
/**
 * File for class PCMWSStructArrayOfRouteMatrixInfo
 * @package PCMWS
 * @subpackage Structs
 * @author WsdlToPhp Team <contact@wsdltophp.com>
 * @version 20150429-01
 * @date 2016-08-25
 */
/**
 * This class stands for PCMWSStructArrayOfRouteMatrixInfo originally named ArrayOfRouteMatrixInfo
 * Meta informations extracted from the WSDL
 * - from schema : {@link http://pcmiler.alk.com/APIs/SOAP/v1.0/Service.svc?xsd=xsd0}
 * @package PCMWS
 * @subpackage Structs
 * @author WsdlToPhp Team <contact@wsdltophp.com>
 * @version 20150429-01
 * @date 2016-08-25
 */
class PCMWSStructArrayOfRouteMatrixInfo extends PCMWSWsdlClass
{
    /**
     * The RouteMatrixInfo
     * Meta informations extracted from the WSDL
     * - maxOccurs : unbounded
     * - minOccurs : 0
     * - nillable : true
     * @var PCMWSStructRouteMatrixInfo
     */
    public $RouteMatrixInfo;
    /**
     * Constructor method for ArrayOfRouteMatrixInfo
     * @see parent::__construct()
     * @param PCMWSStructRouteMatrixInfo $_routeMatrixInfo
     * @return PCMWSStructArrayOfRouteMatrixInfo
     */
    public function __construct($_routeMatrixInfo = NULL)
    {
        parent::__construct(array('RouteMatrixInfo'=>$_routeMatrixInfo),false);
    }
    /**
     * Get RouteMatrixInfo value
     * @return PCMWSStructRouteMatrixInfo|null
     */
    public function getRouteMatrixInfo()
    {
        return $this->RouteMatrixInfo;
    }
    /**
     * Set RouteMatrixInfo value
     * @param PCMWSStructRouteMatrixInfo $_routeMatrixInfo the RouteMatrixInfo
     * @return PCMWSStructRouteMatrixInfo
     */
    public function setRouteMatrixInfo($_routeMatrixInfo)
    {
        return ($this->RouteMatrixInfo = $_routeMatrixInfo);
    }
    /**
     * Returns the current element
     * @see PCMWSWsdlClass::current()
     * @return PCMWSStructRouteMatrixInfo
     */
    public function current()
    {
        return parent::current();
    }
    /**
     * Returns the indexed element
     * @see PCMWSWsdlClass::item()
     * @param int $_index
     * @return PCMWSStructRouteMatrixInfo
     */
    public function item($_index)
    {
        return parent::item($_index);
    }
    /**
     * Returns the first element
     * @see PCMWSWsdlClass::first()
     * @return PCMWSStructRouteMatrixInfo
     */
    public function first()
    {
        return parent::first();
    }
    /**
     * Returns the last element
     * @see PCMWSWsdlClass::last()
     * @return PCMWSStructRouteMatrixInfo
     */
    public function last()
    {
        return parent::last();
    }
    /**
     * Returns the element at the offset
     * @see PCMWSWsdlClass::last()
     * @param int $_offset
     * @return PCMWSStructRouteMatrixInfo
     */
    public function offsetGet($_offset)
    {
        return parent::offsetGet($_offset);
    }
    /**
     * Returns the attribute name
     * @see PCMWSWsdlClass::getAttributeName()
     * @return string RouteMatrixInfo
     */
    public function getAttributeName()
    {
        return 'RouteMatrixInfo';
    }
    /**
     * Method called when an object has been exported with var_export() functions
     * It allows to return an object instantiated with the values
     * @see PCMWSWsdlClass::__set_state()
     * @uses PCMWSWsdlClass::__set_state()
     * @param array $_array the exported values
     * @return PCMWSStructArrayOfRouteMatrixInfo
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
