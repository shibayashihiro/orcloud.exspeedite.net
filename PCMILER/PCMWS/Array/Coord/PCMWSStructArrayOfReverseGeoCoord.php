<?php
/**
 * File for class PCMWSStructArrayOfReverseGeoCoord
 * @package PCMWS
 * @subpackage Structs
 * @author WsdlToPhp Team <contact@wsdltophp.com>
 * @version 20150429-01
 * @date 2016-08-25
 */
/**
 * This class stands for PCMWSStructArrayOfReverseGeoCoord originally named ArrayOfReverseGeoCoord
 * Meta informations extracted from the WSDL
 * - from schema : {@link http://pcmiler.alk.com/APIs/SOAP/v1.0/Service.svc?xsd=xsd0}
 * @package PCMWS
 * @subpackage Structs
 * @author WsdlToPhp Team <contact@wsdltophp.com>
 * @version 20150429-01
 * @date 2016-08-25
 */
class PCMWSStructArrayOfReverseGeoCoord extends PCMWSWsdlClass
{
    /**
     * The ReverseGeoCoord
     * Meta informations extracted from the WSDL
     * - maxOccurs : unbounded
     * - minOccurs : 0
     * - nillable : true
     * @var PCMWSStructReverseGeoCoord
     */
    public $ReverseGeoCoord;
    /**
     * Constructor method for ArrayOfReverseGeoCoord
     * @see parent::__construct()
     * @param PCMWSStructReverseGeoCoord $_reverseGeoCoord
     * @return PCMWSStructArrayOfReverseGeoCoord
     */
    public function __construct($_reverseGeoCoord = NULL)
    {
        parent::__construct(array('ReverseGeoCoord'=>$_reverseGeoCoord),false);
    }
    /**
     * Get ReverseGeoCoord value
     * @return PCMWSStructReverseGeoCoord|null
     */
    public function getReverseGeoCoord()
    {
        return $this->ReverseGeoCoord;
    }
    /**
     * Set ReverseGeoCoord value
     * @param PCMWSStructReverseGeoCoord $_reverseGeoCoord the ReverseGeoCoord
     * @return PCMWSStructReverseGeoCoord
     */
    public function setReverseGeoCoord($_reverseGeoCoord)
    {
        return ($this->ReverseGeoCoord = $_reverseGeoCoord);
    }
    /**
     * Returns the current element
     * @see PCMWSWsdlClass::current()
     * @return PCMWSStructReverseGeoCoord
     */
    public function current()
    {
        return parent::current();
    }
    /**
     * Returns the indexed element
     * @see PCMWSWsdlClass::item()
     * @param int $_index
     * @return PCMWSStructReverseGeoCoord
     */
    public function item($_index)
    {
        return parent::item($_index);
    }
    /**
     * Returns the first element
     * @see PCMWSWsdlClass::first()
     * @return PCMWSStructReverseGeoCoord
     */
    public function first()
    {
        return parent::first();
    }
    /**
     * Returns the last element
     * @see PCMWSWsdlClass::last()
     * @return PCMWSStructReverseGeoCoord
     */
    public function last()
    {
        return parent::last();
    }
    /**
     * Returns the element at the offset
     * @see PCMWSWsdlClass::last()
     * @param int $_offset
     * @return PCMWSStructReverseGeoCoord
     */
    public function offsetGet($_offset)
    {
        return parent::offsetGet($_offset);
    }
    /**
     * Returns the attribute name
     * @see PCMWSWsdlClass::getAttributeName()
     * @return string ReverseGeoCoord
     */
    public function getAttributeName()
    {
        return 'ReverseGeoCoord';
    }
    /**
     * Method called when an object has been exported with var_export() functions
     * It allows to return an object instantiated with the values
     * @see PCMWSWsdlClass::__set_state()
     * @uses PCMWSWsdlClass::__set_state()
     * @param array $_array the exported values
     * @return PCMWSStructArrayOfReverseGeoCoord
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
