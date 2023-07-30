<?php
/**
 * File for class PCMWSStructArrayOfStateCountry
 * @package PCMWS
 * @subpackage Structs
 * @author WsdlToPhp Team <contact@wsdltophp.com>
 * @version 20150429-01
 * @date 2016-08-25
 */
/**
 * This class stands for PCMWSStructArrayOfStateCountry originally named ArrayOfStateCountry
 * Meta informations extracted from the WSDL
 * - from schema : {@link http://pcmiler.alk.com/APIs/SOAP/v1.0/Service.svc?xsd=xsd0}
 * @package PCMWS
 * @subpackage Structs
 * @author WsdlToPhp Team <contact@wsdltophp.com>
 * @version 20150429-01
 * @date 2016-08-25
 */
class PCMWSStructArrayOfStateCountry extends PCMWSWsdlClass
{
    /**
     * The StateCountry
     * Meta informations extracted from the WSDL
     * - maxOccurs : unbounded
     * - minOccurs : 0
     * - nillable : true
     * @var PCMWSStructStateCountry
     */
    public $StateCountry;
    /**
     * Constructor method for ArrayOfStateCountry
     * @see parent::__construct()
     * @param PCMWSStructStateCountry $_stateCountry
     * @return PCMWSStructArrayOfStateCountry
     */
    public function __construct($_stateCountry = NULL)
    {
        parent::__construct(array('StateCountry'=>$_stateCountry),false);
    }
    /**
     * Get StateCountry value
     * @return PCMWSStructStateCountry|null
     */
    public function getStateCountry()
    {
        return $this->StateCountry;
    }
    /**
     * Set StateCountry value
     * @param PCMWSStructStateCountry $_stateCountry the StateCountry
     * @return PCMWSStructStateCountry
     */
    public function setStateCountry($_stateCountry)
    {
        return ($this->StateCountry = $_stateCountry);
    }
    /**
     * Returns the current element
     * @see PCMWSWsdlClass::current()
     * @return PCMWSStructStateCountry
     */
    public function current()
    {
        return parent::current();
    }
    /**
     * Returns the indexed element
     * @see PCMWSWsdlClass::item()
     * @param int $_index
     * @return PCMWSStructStateCountry
     */
    public function item($_index)
    {
        return parent::item($_index);
    }
    /**
     * Returns the first element
     * @see PCMWSWsdlClass::first()
     * @return PCMWSStructStateCountry
     */
    public function first()
    {
        return parent::first();
    }
    /**
     * Returns the last element
     * @see PCMWSWsdlClass::last()
     * @return PCMWSStructStateCountry
     */
    public function last()
    {
        return parent::last();
    }
    /**
     * Returns the element at the offset
     * @see PCMWSWsdlClass::last()
     * @param int $_offset
     * @return PCMWSStructStateCountry
     */
    public function offsetGet($_offset)
    {
        return parent::offsetGet($_offset);
    }
    /**
     * Returns the attribute name
     * @see PCMWSWsdlClass::getAttributeName()
     * @return string StateCountry
     */
    public function getAttributeName()
    {
        return 'StateCountry';
    }
    /**
     * Method called when an object has been exported with var_export() functions
     * It allows to return an object instantiated with the values
     * @see PCMWSWsdlClass::__set_state()
     * @uses PCMWSWsdlClass::__set_state()
     * @param array $_array the exported values
     * @return PCMWSStructArrayOfStateCountry
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
