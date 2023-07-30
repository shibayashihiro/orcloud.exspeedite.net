<?php
/**
 * File for class PCMWSStructArrayOfArrayOfArrayOfdouble
 * @package PCMWS
 * @subpackage Structs
 * @author WsdlToPhp Team <contact@wsdltophp.com>
 * @version 20150429-01
 * @date 2016-08-25
 */
/**
 * This class stands for PCMWSStructArrayOfArrayOfArrayOfdouble originally named ArrayOfArrayOfArrayOfdouble
 * Meta informations extracted from the WSDL
 * - from schema : {@link http://pcmiler.alk.com/APIs/SOAP/v1.0/Service.svc?xsd=xsd3}
 * @package PCMWS
 * @subpackage Structs
 * @author WsdlToPhp Team <contact@wsdltophp.com>
 * @version 20150429-01
 * @date 2016-08-25
 */
class PCMWSStructArrayOfArrayOfArrayOfdouble extends PCMWSWsdlClass
{
    /**
     * The ArrayOfArrayOfdouble
     * Meta informations extracted from the WSDL
     * - maxOccurs : unbounded
     * - minOccurs : 0
     * - nillable : true
     * @var PCMWSStructArrayOfArrayOfdouble
     */
    public $ArrayOfArrayOfdouble;
    /**
     * Constructor method for ArrayOfArrayOfArrayOfdouble
     * @see parent::__construct()
     * @param PCMWSStructArrayOfArrayOfdouble $_arrayOfArrayOfdouble
     * @return PCMWSStructArrayOfArrayOfArrayOfdouble
     */
    public function __construct($_arrayOfArrayOfdouble = NULL)
    {
        parent::__construct(array('ArrayOfArrayOfdouble'=>($_arrayOfArrayOfdouble instanceof PCMWSStructArrayOfArrayOfdouble)?$_arrayOfArrayOfdouble:new PCMWSStructArrayOfArrayOfdouble($_arrayOfArrayOfdouble)),false);
    }
    /**
     * Get ArrayOfArrayOfdouble value
     * @return PCMWSStructArrayOfArrayOfdouble|null
     */
    public function getArrayOfArrayOfdouble()
    {
        return $this->ArrayOfArrayOfdouble;
    }
    /**
     * Set ArrayOfArrayOfdouble value
     * @param PCMWSStructArrayOfArrayOfdouble $_arrayOfArrayOfdouble the ArrayOfArrayOfdouble
     * @return PCMWSStructArrayOfArrayOfdouble
     */
    public function setArrayOfArrayOfdouble($_arrayOfArrayOfdouble)
    {
        return ($this->ArrayOfArrayOfdouble = $_arrayOfArrayOfdouble);
    }
    /**
     * Returns the current element
     * @see PCMWSWsdlClass::current()
     * @return PCMWSStructArrayOfArrayOfdouble
     */
    public function current()
    {
        return parent::current();
    }
    /**
     * Returns the indexed element
     * @see PCMWSWsdlClass::item()
     * @param int $_index
     * @return PCMWSStructArrayOfArrayOfdouble
     */
    public function item($_index)
    {
        return parent::item($_index);
    }
    /**
     * Returns the first element
     * @see PCMWSWsdlClass::first()
     * @return PCMWSStructArrayOfArrayOfdouble
     */
    public function first()
    {
        return parent::first();
    }
    /**
     * Returns the last element
     * @see PCMWSWsdlClass::last()
     * @return PCMWSStructArrayOfArrayOfdouble
     */
    public function last()
    {
        return parent::last();
    }
    /**
     * Returns the element at the offset
     * @see PCMWSWsdlClass::last()
     * @param int $_offset
     * @return PCMWSStructArrayOfArrayOfdouble
     */
    public function offsetGet($_offset)
    {
        return parent::offsetGet($_offset);
    }
    /**
     * Returns the attribute name
     * @see PCMWSWsdlClass::getAttributeName()
     * @return string ArrayOfArrayOfdouble
     */
    public function getAttributeName()
    {
        return 'ArrayOfArrayOfdouble';
    }
    /**
     * Method called when an object has been exported with var_export() functions
     * It allows to return an object instantiated with the values
     * @see PCMWSWsdlClass::__set_state()
     * @uses PCMWSWsdlClass::__set_state()
     * @param array $_array the exported values
     * @return PCMWSStructArrayOfArrayOfArrayOfdouble
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
