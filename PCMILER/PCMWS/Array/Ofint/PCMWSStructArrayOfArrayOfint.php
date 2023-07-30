<?php
/**
 * File for class PCMWSStructArrayOfArrayOfint
 * @package PCMWS
 * @subpackage Structs
 * @author WsdlToPhp Team <contact@wsdltophp.com>
 * @version 20150429-01
 * @date 2016-08-25
 */
/**
 * This class stands for PCMWSStructArrayOfArrayOfint originally named ArrayOfArrayOfint
 * Meta informations extracted from the WSDL
 * - from schema : {@link http://pcmiler.alk.com/APIs/SOAP/v1.0/Service.svc?xsd=xsd3}
 * @package PCMWS
 * @subpackage Structs
 * @author WsdlToPhp Team <contact@wsdltophp.com>
 * @version 20150429-01
 * @date 2016-08-25
 */
class PCMWSStructArrayOfArrayOfint extends PCMWSWsdlClass
{
    /**
     * The ArrayOfint
     * Meta informations extracted from the WSDL
     * - maxOccurs : unbounded
     * - minOccurs : 0
     * - nillable : true
     * @var PCMWSStructArrayOfint
     */
    public $ArrayOfint;
    /**
     * Constructor method for ArrayOfArrayOfint
     * @see parent::__construct()
     * @param PCMWSStructArrayOfint $_arrayOfint
     * @return PCMWSStructArrayOfArrayOfint
     */
    public function __construct($_arrayOfint = NULL)
    {
        parent::__construct(array('ArrayOfint'=>($_arrayOfint instanceof PCMWSStructArrayOfint)?$_arrayOfint:new PCMWSStructArrayOfint($_arrayOfint)),false);
    }
    /**
     * Get ArrayOfint value
     * @return PCMWSStructArrayOfint|null
     */
    public function getArrayOfint()
    {
        return $this->ArrayOfint;
    }
    /**
     * Set ArrayOfint value
     * @param PCMWSStructArrayOfint $_arrayOfint the ArrayOfint
     * @return PCMWSStructArrayOfint
     */
    public function setArrayOfint($_arrayOfint)
    {
        return ($this->ArrayOfint = $_arrayOfint);
    }
    /**
     * Returns the current element
     * @see PCMWSWsdlClass::current()
     * @return PCMWSStructArrayOfint
     */
    public function current()
    {
        return parent::current();
    }
    /**
     * Returns the indexed element
     * @see PCMWSWsdlClass::item()
     * @param int $_index
     * @return PCMWSStructArrayOfint
     */
    public function item($_index)
    {
        return parent::item($_index);
    }
    /**
     * Returns the first element
     * @see PCMWSWsdlClass::first()
     * @return PCMWSStructArrayOfint
     */
    public function first()
    {
        return parent::first();
    }
    /**
     * Returns the last element
     * @see PCMWSWsdlClass::last()
     * @return PCMWSStructArrayOfint
     */
    public function last()
    {
        return parent::last();
    }
    /**
     * Returns the element at the offset
     * @see PCMWSWsdlClass::last()
     * @param int $_offset
     * @return PCMWSStructArrayOfint
     */
    public function offsetGet($_offset)
    {
        return parent::offsetGet($_offset);
    }
    /**
     * Returns the attribute name
     * @see PCMWSWsdlClass::getAttributeName()
     * @return string ArrayOfint
     */
    public function getAttributeName()
    {
        return 'ArrayOfint';
    }
    /**
     * Method called when an object has been exported with var_export() functions
     * It allows to return an object instantiated with the values
     * @see PCMWSWsdlClass::__set_state()
     * @uses PCMWSWsdlClass::__set_state()
     * @param array $_array the exported values
     * @return PCMWSStructArrayOfArrayOfint
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
