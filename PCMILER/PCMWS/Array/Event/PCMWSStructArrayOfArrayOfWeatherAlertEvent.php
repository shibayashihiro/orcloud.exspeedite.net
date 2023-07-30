<?php
/**
 * File for class PCMWSStructArrayOfArrayOfWeatherAlertEvent
 * @package PCMWS
 * @subpackage Structs
 * @author WsdlToPhp Team <contact@wsdltophp.com>
 * @version 20150429-01
 * @date 2016-08-25
 */
/**
 * This class stands for PCMWSStructArrayOfArrayOfWeatherAlertEvent originally named ArrayOfArrayOfWeatherAlertEvent
 * Meta informations extracted from the WSDL
 * - from schema : {@link http://pcmiler.alk.com/APIs/SOAP/v1.0/Service.svc?xsd=xsd0}
 * @package PCMWS
 * @subpackage Structs
 * @author WsdlToPhp Team <contact@wsdltophp.com>
 * @version 20150429-01
 * @date 2016-08-25
 */
class PCMWSStructArrayOfArrayOfWeatherAlertEvent extends PCMWSWsdlClass
{
    /**
     * The ArrayOfWeatherAlertEvent
     * Meta informations extracted from the WSDL
     * - maxOccurs : unbounded
     * - minOccurs : 0
     * - nillable : true
     * @var PCMWSStructArrayOfWeatherAlertEvent
     */
    public $ArrayOfWeatherAlertEvent;
    /**
     * Constructor method for ArrayOfArrayOfWeatherAlertEvent
     * @see parent::__construct()
     * @param PCMWSStructArrayOfWeatherAlertEvent $_arrayOfWeatherAlertEvent
     * @return PCMWSStructArrayOfArrayOfWeatherAlertEvent
     */
    public function __construct($_arrayOfWeatherAlertEvent = NULL)
    {
        parent::__construct(array('ArrayOfWeatherAlertEvent'=>($_arrayOfWeatherAlertEvent instanceof PCMWSStructArrayOfWeatherAlertEvent)?$_arrayOfWeatherAlertEvent:new PCMWSStructArrayOfWeatherAlertEvent($_arrayOfWeatherAlertEvent)),false);
    }
    /**
     * Get ArrayOfWeatherAlertEvent value
     * @return PCMWSStructArrayOfWeatherAlertEvent|null
     */
    public function getArrayOfWeatherAlertEvent()
    {
        return $this->ArrayOfWeatherAlertEvent;
    }
    /**
     * Set ArrayOfWeatherAlertEvent value
     * @param PCMWSStructArrayOfWeatherAlertEvent $_arrayOfWeatherAlertEvent the ArrayOfWeatherAlertEvent
     * @return PCMWSStructArrayOfWeatherAlertEvent
     */
    public function setArrayOfWeatherAlertEvent($_arrayOfWeatherAlertEvent)
    {
        return ($this->ArrayOfWeatherAlertEvent = $_arrayOfWeatherAlertEvent);
    }
    /**
     * Returns the current element
     * @see PCMWSWsdlClass::current()
     * @return PCMWSStructArrayOfWeatherAlertEvent
     */
    public function current()
    {
        return parent::current();
    }
    /**
     * Returns the indexed element
     * @see PCMWSWsdlClass::item()
     * @param int $_index
     * @return PCMWSStructArrayOfWeatherAlertEvent
     */
    public function item($_index)
    {
        return parent::item($_index);
    }
    /**
     * Returns the first element
     * @see PCMWSWsdlClass::first()
     * @return PCMWSStructArrayOfWeatherAlertEvent
     */
    public function first()
    {
        return parent::first();
    }
    /**
     * Returns the last element
     * @see PCMWSWsdlClass::last()
     * @return PCMWSStructArrayOfWeatherAlertEvent
     */
    public function last()
    {
        return parent::last();
    }
    /**
     * Returns the element at the offset
     * @see PCMWSWsdlClass::last()
     * @param int $_offset
     * @return PCMWSStructArrayOfWeatherAlertEvent
     */
    public function offsetGet($_offset)
    {
        return parent::offsetGet($_offset);
    }
    /**
     * Returns the attribute name
     * @see PCMWSWsdlClass::getAttributeName()
     * @return string ArrayOfWeatherAlertEvent
     */
    public function getAttributeName()
    {
        return 'ArrayOfWeatherAlertEvent';
    }
    /**
     * Method called when an object has been exported with var_export() functions
     * It allows to return an object instantiated with the values
     * @see PCMWSWsdlClass::__set_state()
     * @uses PCMWSWsdlClass::__set_state()
     * @param array $_array the exported values
     * @return PCMWSStructArrayOfArrayOfWeatherAlertEvent
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
