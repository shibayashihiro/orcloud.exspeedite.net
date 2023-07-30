<?php
/**
 * File for class PCMWSStructArrayOfAFSet
 * @package PCMWS
 * @subpackage Structs
 * @author WsdlToPhp Team <contact@wsdltophp.com>
 * @version 20150429-01
 * @date 2016-08-25
 */
/**
 * This class stands for PCMWSStructArrayOfAFSet originally named ArrayOfAFSet
 * Meta informations extracted from the WSDL
 * - from schema : {@link http://pcmiler.alk.com/APIs/SOAP/v1.0/Service.svc?xsd=xsd0}
 * @package PCMWS
 * @subpackage Structs
 * @author WsdlToPhp Team <contact@wsdltophp.com>
 * @version 20150429-01
 * @date 2016-08-25
 */
class PCMWSStructArrayOfAFSet extends PCMWSWsdlClass
{
    /**
     * The AFSet
     * Meta informations extracted from the WSDL
     * - maxOccurs : unbounded
     * - minOccurs : 0
     * - nillable : true
     * @var PCMWSStructAFSet
     */
    public $AFSet;
    /**
     * Constructor method for ArrayOfAFSet
     * @see parent::__construct()
     * @param PCMWSStructAFSet $_aFSet
     * @return PCMWSStructArrayOfAFSet
     */
    public function __construct($_aFSet = NULL)
    {
        parent::__construct(array('AFSet'=>$_aFSet),false);
    }
    /**
     * Get AFSet value
     * @return PCMWSStructAFSet|null
     */
    public function getAFSet()
    {
        return $this->AFSet;
    }
    /**
     * Set AFSet value
     * @param PCMWSStructAFSet $_aFSet the AFSet
     * @return PCMWSStructAFSet
     */
    public function setAFSet($_aFSet)
    {
        return ($this->AFSet = $_aFSet);
    }
    /**
     * Returns the current element
     * @see PCMWSWsdlClass::current()
     * @return PCMWSStructAFSet
     */
    public function current()
    {
        return parent::current();
    }
    /**
     * Returns the indexed element
     * @see PCMWSWsdlClass::item()
     * @param int $_index
     * @return PCMWSStructAFSet
     */
    public function item($_index)
    {
        return parent::item($_index);
    }
    /**
     * Returns the first element
     * @see PCMWSWsdlClass::first()
     * @return PCMWSStructAFSet
     */
    public function first()
    {
        return parent::first();
    }
    /**
     * Returns the last element
     * @see PCMWSWsdlClass::last()
     * @return PCMWSStructAFSet
     */
    public function last()
    {
        return parent::last();
    }
    /**
     * Returns the element at the offset
     * @see PCMWSWsdlClass::last()
     * @param int $_offset
     * @return PCMWSStructAFSet
     */
    public function offsetGet($_offset)
    {
        return parent::offsetGet($_offset);
    }
    /**
     * Returns the attribute name
     * @see PCMWSWsdlClass::getAttributeName()
     * @return string AFSet
     */
    public function getAttributeName()
    {
        return 'AFSet';
    }
    /**
     * Method called when an object has been exported with var_export() functions
     * It allows to return an object instantiated with the values
     * @see PCMWSWsdlClass::__set_state()
     * @uses PCMWSWsdlClass::__set_state()
     * @param array $_array the exported values
     * @return PCMWSStructArrayOfAFSet
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
