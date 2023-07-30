<?php
/**
 * File for class PCMWSStructArrayOfPOIAlongRouteMatch
 * @package PCMWS
 * @subpackage Structs
 * @author WsdlToPhp Team <contact@wsdltophp.com>
 * @version 20150429-01
 * @date 2016-08-25
 */
/**
 * This class stands for PCMWSStructArrayOfPOIAlongRouteMatch originally named ArrayOfPOIAlongRouteMatch
 * Meta informations extracted from the WSDL
 * - from schema : {@link http://pcmiler.alk.com/APIs/SOAP/v1.0/Service.svc?xsd=xsd0}
 * @package PCMWS
 * @subpackage Structs
 * @author WsdlToPhp Team <contact@wsdltophp.com>
 * @version 20150429-01
 * @date 2016-08-25
 */
class PCMWSStructArrayOfPOIAlongRouteMatch extends PCMWSWsdlClass
{
    /**
     * The POIAlongRouteMatch
     * Meta informations extracted from the WSDL
     * - maxOccurs : unbounded
     * - minOccurs : 0
     * - nillable : true
     * @var PCMWSStructPOIAlongRouteMatch
     */
    public $POIAlongRouteMatch;
    /**
     * Constructor method for ArrayOfPOIAlongRouteMatch
     * @see parent::__construct()
     * @param PCMWSStructPOIAlongRouteMatch $_pOIAlongRouteMatch
     * @return PCMWSStructArrayOfPOIAlongRouteMatch
     */
    public function __construct($_pOIAlongRouteMatch = NULL)
    {
        parent::__construct(array('POIAlongRouteMatch'=>$_pOIAlongRouteMatch),false);
    }
    /**
     * Get POIAlongRouteMatch value
     * @return PCMWSStructPOIAlongRouteMatch|null
     */
    public function getPOIAlongRouteMatch()
    {
        return $this->POIAlongRouteMatch;
    }
    /**
     * Set POIAlongRouteMatch value
     * @param PCMWSStructPOIAlongRouteMatch $_pOIAlongRouteMatch the POIAlongRouteMatch
     * @return PCMWSStructPOIAlongRouteMatch
     */
    public function setPOIAlongRouteMatch($_pOIAlongRouteMatch)
    {
        return ($this->POIAlongRouteMatch = $_pOIAlongRouteMatch);
    }
    /**
     * Returns the current element
     * @see PCMWSWsdlClass::current()
     * @return PCMWSStructPOIAlongRouteMatch
     */
    public function current()
    {
        return parent::current();
    }
    /**
     * Returns the indexed element
     * @see PCMWSWsdlClass::item()
     * @param int $_index
     * @return PCMWSStructPOIAlongRouteMatch
     */
    public function item($_index)
    {
        return parent::item($_index);
    }
    /**
     * Returns the first element
     * @see PCMWSWsdlClass::first()
     * @return PCMWSStructPOIAlongRouteMatch
     */
    public function first()
    {
        return parent::first();
    }
    /**
     * Returns the last element
     * @see PCMWSWsdlClass::last()
     * @return PCMWSStructPOIAlongRouteMatch
     */
    public function last()
    {
        return parent::last();
    }
    /**
     * Returns the element at the offset
     * @see PCMWSWsdlClass::last()
     * @param int $_offset
     * @return PCMWSStructPOIAlongRouteMatch
     */
    public function offsetGet($_offset)
    {
        return parent::offsetGet($_offset);
    }
    /**
     * Returns the attribute name
     * @see PCMWSWsdlClass::getAttributeName()
     * @return string POIAlongRouteMatch
     */
    public function getAttributeName()
    {
        return 'POIAlongRouteMatch';
    }
    /**
     * Method called when an object has been exported with var_export() functions
     * It allows to return an object instantiated with the values
     * @see PCMWSWsdlClass::__set_state()
     * @uses PCMWSWsdlClass::__set_state()
     * @param array $_array the exported values
     * @return PCMWSStructArrayOfPOIAlongRouteMatch
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
