<?php
/**
 * File for class PCMWSStructArrayOfArrayOfdouble
 * @package PCMWS
 * @subpackage Structs
 * @author WsdlToPhp Team <contact@wsdltophp.com>
 * @version 20150429-01
 * @date 2016-08-25
 */
/**
 * This class stands for PCMWSStructArrayOfArrayOfdouble originally named ArrayOfArrayOfdouble
 * Meta informations extracted from the WSDL
 * - from schema : {@link http://pcmiler.alk.com/APIs/SOAP/v1.0/Service.svc?xsd=xsd3}
 * @package PCMWS
 * @subpackage Structs
 * @author WsdlToPhp Team <contact@wsdltophp.com>
 * @version 20150429-01
 * @date 2016-08-25
 */
class PCMWSStructArrayOfArrayOfdouble extends PCMWSWsdlClass
{
    /**
     * The ArrayOfdouble
     * Meta informations extracted from the WSDL
     * - maxOccurs : unbounded
     * - minOccurs : 0
     * - nillable : true
     * @var PCMWSStructArrayOfdouble
     */
    public $ArrayOfdouble;
    /**
     * Constructor method for ArrayOfArrayOfdouble
     * @see parent::__construct()
     * @param PCMWSStructArrayOfdouble $_arrayOfdouble
     * @return PCMWSStructArrayOfArrayOfdouble
     */
    public function __construct($_arrayOfdouble = NULL)
    {
        parent::__construct(array('ArrayOfdouble'=>($_arrayOfdouble instanceof PCMWSStructArrayOfdouble)?$_arrayOfdouble:new PCMWSStructArrayOfdouble($_arrayOfdouble)),false);
    }
    /**
     * Get ArrayOfdouble value
     * @return PCMWSStructArrayOfdouble|null
     */
    public function getArrayOfdouble()
    {
        return $this->ArrayOfdouble;
    }
    /**
     * Set ArrayOfdouble value
     * @param PCMWSStructArrayOfdouble $_arrayOfdouble the ArrayOfdouble
     * @return PCMWSStructArrayOfdouble
     */
    public function setArrayOfdouble($_arrayOfdouble)
    {
        return ($this->ArrayOfdouble = $_arrayOfdouble);
    }
    /**
     * Returns the current element
     * @see PCMWSWsdlClass::current()
     * @return PCMWSStructArrayOfdouble
     */
    public function current()
    {
        return parent::current();
    }
    /**
     * Returns the indexed element
     * @see PCMWSWsdlClass::item()
     * @param int $_index
     * @return PCMWSStructArrayOfdouble
     */
    public function item($_index)
    {
        return parent::item($_index);
    }
    /**
     * Returns the first element
     * @see PCMWSWsdlClass::first()
     * @return PCMWSStructArrayOfdouble
     */
    public function first()
    {
        return parent::first();
    }
    /**
     * Returns the last element
     * @see PCMWSWsdlClass::last()
     * @return PCMWSStructArrayOfdouble
     */
    public function last()
    {
        return parent::last();
    }
    /**
     * Returns the element at the offset
     * @see PCMWSWsdlClass::last()
     * @param int $_offset
     * @return PCMWSStructArrayOfdouble
     */
    public function offsetGet($_offset)
    {
        return parent::offsetGet($_offset);
    }
    /**
     * Returns the attribute name
     * @see PCMWSWsdlClass::getAttributeName()
     * @return string ArrayOfdouble
     */
    public function getAttributeName()
    {
        return 'ArrayOfdouble';
    }
    /**
     * Method called when an object has been exported with var_export() functions
     * It allows to return an object instantiated with the values
     * @see PCMWSWsdlClass::__set_state()
     * @uses PCMWSWsdlClass::__set_state()
     * @param array $_array the exported values
     * @return PCMWSStructArrayOfArrayOfdouble
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
