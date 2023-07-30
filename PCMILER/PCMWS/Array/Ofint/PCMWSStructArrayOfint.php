<?php
/**
 * File for class PCMWSStructArrayOfint
 * @package PCMWS
 * @subpackage Structs
 * @author WsdlToPhp Team <contact@wsdltophp.com>
 * @version 20150429-01
 * @date 2016-08-25
 */
/**
 * This class stands for PCMWSStructArrayOfint originally named ArrayOfint
 * Meta informations extracted from the WSDL
 * - from schema : {@link http://pcmiler.alk.com/APIs/SOAP/v1.0/Service.svc?xsd=xsd3}
 * @package PCMWS
 * @subpackage Structs
 * @author WsdlToPhp Team <contact@wsdltophp.com>
 * @version 20150429-01
 * @date 2016-08-25
 */
class PCMWSStructArrayOfint extends PCMWSWsdlClass
{
    /**
     * The int
     * Meta informations extracted from the WSDL
     * - maxOccurs : unbounded
     * - minOccurs : 0
     * @var int
     */
    public $int;
    /**
     * Constructor method for ArrayOfint
     * @see parent::__construct()
     * @param int $_int
     * @return PCMWSStructArrayOfint
     */
    public function __construct($_int = NULL)
    {
        parent::__construct(array('int'=>$_int),false);
    }
    /**
     * Get int value
     * @return int|null
     */
    public function getInt()
    {
        return $this->int;
    }
    /**
     * Set int value
     * @param int $_int the int
     * @return int
     */
    public function setInt($_int)
    {
        return ($this->int = $_int);
    }
    /**
     * Returns the current element
     * @see PCMWSWsdlClass::current()
     * @return int
     */
    public function current()
    {
        return parent::current();
    }
    /**
     * Returns the indexed element
     * @see PCMWSWsdlClass::item()
     * @param int $_index
     * @return int
     */
    public function item($_index)
    {
        return parent::item($_index);
    }
    /**
     * Returns the first element
     * @see PCMWSWsdlClass::first()
     * @return int
     */
    public function first()
    {
        return parent::first();
    }
    /**
     * Returns the last element
     * @see PCMWSWsdlClass::last()
     * @return int
     */
    public function last()
    {
        return parent::last();
    }
    /**
     * Returns the element at the offset
     * @see PCMWSWsdlClass::last()
     * @param int $_offset
     * @return int
     */
    public function offsetGet($_offset)
    {
        return parent::offsetGet($_offset);
    }
    /**
     * Returns the attribute name
     * @see PCMWSWsdlClass::getAttributeName()
     * @return string int
     */
    public function getAttributeName()
    {
        return 'int';
    }
    /**
     * Method called when an object has been exported with var_export() functions
     * It allows to return an object instantiated with the values
     * @see PCMWSWsdlClass::__set_state()
     * @uses PCMWSWsdlClass::__set_state()
     * @param array $_array the exported values
     * @return PCMWSStructArrayOfint
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
