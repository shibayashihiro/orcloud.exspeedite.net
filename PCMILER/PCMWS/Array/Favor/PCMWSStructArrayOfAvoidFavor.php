<?php
/**
 * File for class PCMWSStructArrayOfAvoidFavor
 * @package PCMWS
 * @subpackage Structs
 * @author WsdlToPhp Team <contact@wsdltophp.com>
 * @version 20150429-01
 * @date 2016-08-25
 */
/**
 * This class stands for PCMWSStructArrayOfAvoidFavor originally named ArrayOfAvoidFavor
 * Meta informations extracted from the WSDL
 * - from schema : {@link http://pcmiler.alk.com/APIs/SOAP/v1.0/Service.svc?xsd=xsd0}
 * @package PCMWS
 * @subpackage Structs
 * @author WsdlToPhp Team <contact@wsdltophp.com>
 * @version 20150429-01
 * @date 2016-08-25
 */
class PCMWSStructArrayOfAvoidFavor extends PCMWSWsdlClass
{
    /**
     * The AvoidFavor
     * Meta informations extracted from the WSDL
     * - maxOccurs : unbounded
     * - minOccurs : 0
     * - nillable : true
     * @var PCMWSStructAvoidFavor
     */
    public $AvoidFavor;
    /**
     * Constructor method for ArrayOfAvoidFavor
     * @see parent::__construct()
     * @param PCMWSStructAvoidFavor $_avoidFavor
     * @return PCMWSStructArrayOfAvoidFavor
     */
    public function __construct($_avoidFavor = NULL)
    {
        parent::__construct(array('AvoidFavor'=>$_avoidFavor),false);
    }
    /**
     * Get AvoidFavor value
     * @return PCMWSStructAvoidFavor|null
     */
    public function getAvoidFavor()
    {
        return $this->AvoidFavor;
    }
    /**
     * Set AvoidFavor value
     * @param PCMWSStructAvoidFavor $_avoidFavor the AvoidFavor
     * @return PCMWSStructAvoidFavor
     */
    public function setAvoidFavor($_avoidFavor)
    {
        return ($this->AvoidFavor = $_avoidFavor);
    }
    /**
     * Returns the current element
     * @see PCMWSWsdlClass::current()
     * @return PCMWSStructAvoidFavor
     */
    public function current()
    {
        return parent::current();
    }
    /**
     * Returns the indexed element
     * @see PCMWSWsdlClass::item()
     * @param int $_index
     * @return PCMWSStructAvoidFavor
     */
    public function item($_index)
    {
        return parent::item($_index);
    }
    /**
     * Returns the first element
     * @see PCMWSWsdlClass::first()
     * @return PCMWSStructAvoidFavor
     */
    public function first()
    {
        return parent::first();
    }
    /**
     * Returns the last element
     * @see PCMWSWsdlClass::last()
     * @return PCMWSStructAvoidFavor
     */
    public function last()
    {
        return parent::last();
    }
    /**
     * Returns the element at the offset
     * @see PCMWSWsdlClass::last()
     * @param int $_offset
     * @return PCMWSStructAvoidFavor
     */
    public function offsetGet($_offset)
    {
        return parent::offsetGet($_offset);
    }
    /**
     * Returns the attribute name
     * @see PCMWSWsdlClass::getAttributeName()
     * @return string AvoidFavor
     */
    public function getAttributeName()
    {
        return 'AvoidFavor';
    }
    /**
     * Method called when an object has been exported with var_export() functions
     * It allows to return an object instantiated with the values
     * @see PCMWSWsdlClass::__set_state()
     * @uses PCMWSWsdlClass::__set_state()
     * @param array $_array the exported values
     * @return PCMWSStructArrayOfAvoidFavor
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
