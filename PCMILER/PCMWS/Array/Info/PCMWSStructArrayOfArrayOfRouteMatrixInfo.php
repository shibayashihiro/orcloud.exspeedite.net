<?php
/**
 * File for class PCMWSStructArrayOfArrayOfRouteMatrixInfo
 * @package PCMWS
 * @subpackage Structs
 * @author WsdlToPhp Team <contact@wsdltophp.com>
 * @version 20150429-01
 * @date 2016-08-25
 */
/**
 * This class stands for PCMWSStructArrayOfArrayOfRouteMatrixInfo originally named ArrayOfArrayOfRouteMatrixInfo
 * Meta informations extracted from the WSDL
 * - from schema : {@link http://pcmiler.alk.com/APIs/SOAP/v1.0/Service.svc?xsd=xsd0}
 * @package PCMWS
 * @subpackage Structs
 * @author WsdlToPhp Team <contact@wsdltophp.com>
 * @version 20150429-01
 * @date 2016-08-25
 */
class PCMWSStructArrayOfArrayOfRouteMatrixInfo extends PCMWSWsdlClass
{
    /**
     * The ArrayOfRouteMatrixInfo
     * Meta informations extracted from the WSDL
     * - maxOccurs : unbounded
     * - minOccurs : 0
     * - nillable : true
     * @var PCMWSStructArrayOfRouteMatrixInfo
     */
    public $ArrayOfRouteMatrixInfo;
    /**
     * Constructor method for ArrayOfArrayOfRouteMatrixInfo
     * @see parent::__construct()
     * @param PCMWSStructArrayOfRouteMatrixInfo $_arrayOfRouteMatrixInfo
     * @return PCMWSStructArrayOfArrayOfRouteMatrixInfo
     */
    public function __construct($_arrayOfRouteMatrixInfo = NULL)
    {
        parent::__construct(array('ArrayOfRouteMatrixInfo'=>($_arrayOfRouteMatrixInfo instanceof PCMWSStructArrayOfRouteMatrixInfo)?$_arrayOfRouteMatrixInfo:new PCMWSStructArrayOfRouteMatrixInfo($_arrayOfRouteMatrixInfo)),false);
    }
    /**
     * Get ArrayOfRouteMatrixInfo value
     * @return PCMWSStructArrayOfRouteMatrixInfo|null
     */
    public function getArrayOfRouteMatrixInfo()
    {
        return $this->ArrayOfRouteMatrixInfo;
    }
    /**
     * Set ArrayOfRouteMatrixInfo value
     * @param PCMWSStructArrayOfRouteMatrixInfo $_arrayOfRouteMatrixInfo the ArrayOfRouteMatrixInfo
     * @return PCMWSStructArrayOfRouteMatrixInfo
     */
    public function setArrayOfRouteMatrixInfo($_arrayOfRouteMatrixInfo)
    {
        return ($this->ArrayOfRouteMatrixInfo = $_arrayOfRouteMatrixInfo);
    }
    /**
     * Returns the current element
     * @see PCMWSWsdlClass::current()
     * @return PCMWSStructArrayOfRouteMatrixInfo
     */
    public function current()
    {
        return parent::current();
    }
    /**
     * Returns the indexed element
     * @see PCMWSWsdlClass::item()
     * @param int $_index
     * @return PCMWSStructArrayOfRouteMatrixInfo
     */
    public function item($_index)
    {
        return parent::item($_index);
    }
    /**
     * Returns the first element
     * @see PCMWSWsdlClass::first()
     * @return PCMWSStructArrayOfRouteMatrixInfo
     */
    public function first()
    {
        return parent::first();
    }
    /**
     * Returns the last element
     * @see PCMWSWsdlClass::last()
     * @return PCMWSStructArrayOfRouteMatrixInfo
     */
    public function last()
    {
        return parent::last();
    }
    /**
     * Returns the element at the offset
     * @see PCMWSWsdlClass::last()
     * @param int $_offset
     * @return PCMWSStructArrayOfRouteMatrixInfo
     */
    public function offsetGet($_offset)
    {
        return parent::offsetGet($_offset);
    }
    /**
     * Returns the attribute name
     * @see PCMWSWsdlClass::getAttributeName()
     * @return string ArrayOfRouteMatrixInfo
     */
    public function getAttributeName()
    {
        return 'ArrayOfRouteMatrixInfo';
    }
    /**
     * Method called when an object has been exported with var_export() functions
     * It allows to return an object instantiated with the values
     * @see PCMWSWsdlClass::__set_state()
     * @uses PCMWSWsdlClass::__set_state()
     * @param array $_array the exported values
     * @return PCMWSStructArrayOfArrayOfRouteMatrixInfo
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
