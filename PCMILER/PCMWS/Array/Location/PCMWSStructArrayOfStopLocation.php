<?php
/**
 * File for class PCMWSStructArrayOfStopLocation
 * @package PCMWS
 * @subpackage Structs
 * @author WsdlToPhp Team <contact@wsdltophp.com>
 * @version 20150429-01
 * @date 2016-08-25
 */
/**
 * This class stands for PCMWSStructArrayOfStopLocation originally named ArrayOfStopLocation
 * Meta informations extracted from the WSDL
 * - from schema : {@link http://pcmiler.alk.com/APIs/SOAP/v1.0/Service.svc?xsd=xsd0}
 * @package PCMWS
 * @subpackage Structs
 * @author WsdlToPhp Team <contact@wsdltophp.com>
 * @version 20150429-01
 * @date 2016-08-25
 */
class PCMWSStructArrayOfStopLocation extends PCMWSWsdlClass
{
    /**
     * The StopLocation
     * Meta informations extracted from the WSDL
     * - maxOccurs : unbounded
     * - minOccurs : 0
     * - nillable : true
     * @var PCMWSStructStopLocation
     */
    public $StopLocation;
    /**
     * Constructor method for ArrayOfStopLocation
     * @see parent::__construct()
     * @param PCMWSStructStopLocation $_stopLocation
     * @return PCMWSStructArrayOfStopLocation
     */
    public function __construct($_stopLocation = NULL)
    {
        parent::__construct(array('StopLocation'=>$_stopLocation),false);
    }
    /**
     * Get StopLocation value
     * @return PCMWSStructStopLocation|null
     */
    public function getStopLocation()
    {
        return $this->StopLocation;
    }
    /**
     * Set StopLocation value
     * @param PCMWSStructStopLocation $_stopLocation the StopLocation
     * @return PCMWSStructStopLocation
     */
    public function setStopLocation($_stopLocation)
    {
        return ($this->StopLocation = $_stopLocation);
    }
    /**
     * Returns the current element
     * @see PCMWSWsdlClass::current()
     * @return PCMWSStructStopLocation
     */
    public function current()
    {
        return parent::current();
    }
    /**
     * Returns the indexed element
     * @see PCMWSWsdlClass::item()
     * @param int $_index
     * @return PCMWSStructStopLocation
     */
    public function item($_index)
    {
        return parent::item($_index);
    }
    /**
     * Returns the first element
     * @see PCMWSWsdlClass::first()
     * @return PCMWSStructStopLocation
     */
    public function first()
    {
        return parent::first();
    }
    /**
     * Returns the last element
     * @see PCMWSWsdlClass::last()
     * @return PCMWSStructStopLocation
     */
    public function last()
    {
        return parent::last();
    }
    /**
     * Returns the element at the offset
     * @see PCMWSWsdlClass::last()
     * @param int $_offset
     * @return PCMWSStructStopLocation
     */
    public function offsetGet($_offset)
    {
        return parent::offsetGet($_offset);
    }
    /**
     * Returns the attribute name
     * @see PCMWSWsdlClass::getAttributeName()
     * @return string StopLocation
     */
    public function getAttributeName()
    {
        return 'StopLocation';
    }
    /**
     * Method called when an object has been exported with var_export() functions
     * It allows to return an object instantiated with the values
     * @see PCMWSWsdlClass::__set_state()
     * @uses PCMWSWsdlClass::__set_state()
     * @param array $_array the exported values
     * @return PCMWSStructArrayOfStopLocation
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
