<?php
/**
 * File for class PCMWSStructArrayOfPinCategory
 * @package PCMWS
 * @subpackage Structs
 * @author WsdlToPhp Team <contact@wsdltophp.com>
 * @version 20150429-01
 * @date 2016-08-25
 */
/**
 * This class stands for PCMWSStructArrayOfPinCategory originally named ArrayOfPinCategory
 * Meta informations extracted from the WSDL
 * - from schema : {@link http://pcmiler.alk.com/APIs/SOAP/v1.0/Service.svc?xsd=xsd0}
 * @package PCMWS
 * @subpackage Structs
 * @author WsdlToPhp Team <contact@wsdltophp.com>
 * @version 20150429-01
 * @date 2016-08-25
 */
class PCMWSStructArrayOfPinCategory extends PCMWSWsdlClass
{
    /**
     * The PinCategory
     * Meta informations extracted from the WSDL
     * - maxOccurs : unbounded
     * - minOccurs : 0
     * - nillable : true
     * @var PCMWSStructPinCategory
     */
    public $PinCategory;
    /**
     * Constructor method for ArrayOfPinCategory
     * @see parent::__construct()
     * @param PCMWSStructPinCategory $_pinCategory
     * @return PCMWSStructArrayOfPinCategory
     */
    public function __construct($_pinCategory = NULL)
    {
        parent::__construct(array('PinCategory'=>$_pinCategory),false);
    }
    /**
     * Get PinCategory value
     * @return PCMWSStructPinCategory|null
     */
    public function getPinCategory()
    {
        return $this->PinCategory;
    }
    /**
     * Set PinCategory value
     * @param PCMWSStructPinCategory $_pinCategory the PinCategory
     * @return PCMWSStructPinCategory
     */
    public function setPinCategory($_pinCategory)
    {
        return ($this->PinCategory = $_pinCategory);
    }
    /**
     * Returns the current element
     * @see PCMWSWsdlClass::current()
     * @return PCMWSStructPinCategory
     */
    public function current()
    {
        return parent::current();
    }
    /**
     * Returns the indexed element
     * @see PCMWSWsdlClass::item()
     * @param int $_index
     * @return PCMWSStructPinCategory
     */
    public function item($_index)
    {
        return parent::item($_index);
    }
    /**
     * Returns the first element
     * @see PCMWSWsdlClass::first()
     * @return PCMWSStructPinCategory
     */
    public function first()
    {
        return parent::first();
    }
    /**
     * Returns the last element
     * @see PCMWSWsdlClass::last()
     * @return PCMWSStructPinCategory
     */
    public function last()
    {
        return parent::last();
    }
    /**
     * Returns the element at the offset
     * @see PCMWSWsdlClass::last()
     * @param int $_offset
     * @return PCMWSStructPinCategory
     */
    public function offsetGet($_offset)
    {
        return parent::offsetGet($_offset);
    }
    /**
     * Returns the attribute name
     * @see PCMWSWsdlClass::getAttributeName()
     * @return string PinCategory
     */
    public function getAttributeName()
    {
        return 'PinCategory';
    }
    /**
     * Method called when an object has been exported with var_export() functions
     * It allows to return an object instantiated with the values
     * @see PCMWSWsdlClass::__set_state()
     * @uses PCMWSWsdlClass::__set_state()
     * @param array $_array the exported values
     * @return PCMWSStructArrayOfPinCategory
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
