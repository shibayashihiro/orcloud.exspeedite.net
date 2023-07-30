<?php
/**
 * File for class PCMWSStructArrayOfRadiusSearchMatch
 * @package PCMWS
 * @subpackage Structs
 * @author WsdlToPhp Team <contact@wsdltophp.com>
 * @version 20150429-01
 * @date 2016-08-25
 */
/**
 * This class stands for PCMWSStructArrayOfRadiusSearchMatch originally named ArrayOfRadiusSearchMatch
 * Meta informations extracted from the WSDL
 * - from schema : {@link http://pcmiler.alk.com/APIs/SOAP/v1.0/Service.svc?xsd=xsd0}
 * @package PCMWS
 * @subpackage Structs
 * @author WsdlToPhp Team <contact@wsdltophp.com>
 * @version 20150429-01
 * @date 2016-08-25
 */
class PCMWSStructArrayOfRadiusSearchMatch extends PCMWSWsdlClass
{
    /**
     * The RadiusSearchMatch
     * Meta informations extracted from the WSDL
     * - maxOccurs : unbounded
     * - minOccurs : 0
     * - nillable : true
     * @var PCMWSStructRadiusSearchMatch
     */
    public $RadiusSearchMatch;
    /**
     * Constructor method for ArrayOfRadiusSearchMatch
     * @see parent::__construct()
     * @param PCMWSStructRadiusSearchMatch $_radiusSearchMatch
     * @return PCMWSStructArrayOfRadiusSearchMatch
     */
    public function __construct($_radiusSearchMatch = NULL)
    {
        parent::__construct(array('RadiusSearchMatch'=>$_radiusSearchMatch),false);
    }
    /**
     * Get RadiusSearchMatch value
     * @return PCMWSStructRadiusSearchMatch|null
     */
    public function getRadiusSearchMatch()
    {
        return $this->RadiusSearchMatch;
    }
    /**
     * Set RadiusSearchMatch value
     * @param PCMWSStructRadiusSearchMatch $_radiusSearchMatch the RadiusSearchMatch
     * @return PCMWSStructRadiusSearchMatch
     */
    public function setRadiusSearchMatch($_radiusSearchMatch)
    {
        return ($this->RadiusSearchMatch = $_radiusSearchMatch);
    }
    /**
     * Returns the current element
     * @see PCMWSWsdlClass::current()
     * @return PCMWSStructRadiusSearchMatch
     */
    public function current()
    {
        return parent::current();
    }
    /**
     * Returns the indexed element
     * @see PCMWSWsdlClass::item()
     * @param int $_index
     * @return PCMWSStructRadiusSearchMatch
     */
    public function item($_index)
    {
        return parent::item($_index);
    }
    /**
     * Returns the first element
     * @see PCMWSWsdlClass::first()
     * @return PCMWSStructRadiusSearchMatch
     */
    public function first()
    {
        return parent::first();
    }
    /**
     * Returns the last element
     * @see PCMWSWsdlClass::last()
     * @return PCMWSStructRadiusSearchMatch
     */
    public function last()
    {
        return parent::last();
    }
    /**
     * Returns the element at the offset
     * @see PCMWSWsdlClass::last()
     * @param int $_offset
     * @return PCMWSStructRadiusSearchMatch
     */
    public function offsetGet($_offset)
    {
        return parent::offsetGet($_offset);
    }
    /**
     * Returns the attribute name
     * @see PCMWSWsdlClass::getAttributeName()
     * @return string RadiusSearchMatch
     */
    public function getAttributeName()
    {
        return 'RadiusSearchMatch';
    }
    /**
     * Method called when an object has been exported with var_export() functions
     * It allows to return an object instantiated with the values
     * @see PCMWSWsdlClass::__set_state()
     * @uses PCMWSWsdlClass::__set_state()
     * @param array $_array the exported values
     * @return PCMWSStructArrayOfRadiusSearchMatch
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
