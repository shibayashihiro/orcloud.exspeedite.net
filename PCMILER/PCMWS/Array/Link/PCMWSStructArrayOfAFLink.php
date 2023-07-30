<?php
/**
 * File for class PCMWSStructArrayOfAFLink
 * @package PCMWS
 * @subpackage Structs
 * @author WsdlToPhp Team <contact@wsdltophp.com>
 * @version 20150429-01
 * @date 2016-08-25
 */
/**
 * This class stands for PCMWSStructArrayOfAFLink originally named ArrayOfAFLink
 * Meta informations extracted from the WSDL
 * - from schema : {@link http://pcmiler.alk.com/APIs/SOAP/v1.0/Service.svc?xsd=xsd0}
 * @package PCMWS
 * @subpackage Structs
 * @author WsdlToPhp Team <contact@wsdltophp.com>
 * @version 20150429-01
 * @date 2016-08-25
 */
class PCMWSStructArrayOfAFLink extends PCMWSWsdlClass
{
    /**
     * The AFLink
     * Meta informations extracted from the WSDL
     * - maxOccurs : unbounded
     * - minOccurs : 0
     * - nillable : true
     * @var PCMWSStructAFLink
     */
    public $AFLink;
    /**
     * Constructor method for ArrayOfAFLink
     * @see parent::__construct()
     * @param PCMWSStructAFLink $_aFLink
     * @return PCMWSStructArrayOfAFLink
     */
    public function __construct($_aFLink = NULL)
    {
        parent::__construct(array('AFLink'=>$_aFLink),false);
    }
    /**
     * Get AFLink value
     * @return PCMWSStructAFLink|null
     */
    public function getAFLink()
    {
        return $this->AFLink;
    }
    /**
     * Set AFLink value
     * @param PCMWSStructAFLink $_aFLink the AFLink
     * @return PCMWSStructAFLink
     */
    public function setAFLink($_aFLink)
    {
        return ($this->AFLink = $_aFLink);
    }
    /**
     * Returns the current element
     * @see PCMWSWsdlClass::current()
     * @return PCMWSStructAFLink
     */
    public function current()
    {
        return parent::current();
    }
    /**
     * Returns the indexed element
     * @see PCMWSWsdlClass::item()
     * @param int $_index
     * @return PCMWSStructAFLink
     */
    public function item($_index)
    {
        return parent::item($_index);
    }
    /**
     * Returns the first element
     * @see PCMWSWsdlClass::first()
     * @return PCMWSStructAFLink
     */
    public function first()
    {
        return parent::first();
    }
    /**
     * Returns the last element
     * @see PCMWSWsdlClass::last()
     * @return PCMWSStructAFLink
     */
    public function last()
    {
        return parent::last();
    }
    /**
     * Returns the element at the offset
     * @see PCMWSWsdlClass::last()
     * @param int $_offset
     * @return PCMWSStructAFLink
     */
    public function offsetGet($_offset)
    {
        return parent::offsetGet($_offset);
    }
    /**
     * Returns the attribute name
     * @see PCMWSWsdlClass::getAttributeName()
     * @return string AFLink
     */
    public function getAttributeName()
    {
        return 'AFLink';
    }
    /**
     * Method called when an object has been exported with var_export() functions
     * It allows to return an object instantiated with the values
     * @see PCMWSWsdlClass::__set_state()
     * @uses PCMWSWsdlClass::__set_state()
     * @param array $_array the exported values
     * @return PCMWSStructArrayOfAFLink
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
