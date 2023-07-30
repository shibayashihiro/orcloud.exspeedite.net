<?php
/**
 * File for class PCMWSStructArrayOfWeatherAlertEvent
 * @package PCMWS
 * @subpackage Structs
 * @author WsdlToPhp Team <contact@wsdltophp.com>
 * @version 20150429-01
 * @date 2016-08-25
 */
/**
 * This class stands for PCMWSStructArrayOfWeatherAlertEvent originally named ArrayOfWeatherAlertEvent
 * Meta informations extracted from the WSDL
 * - from schema : {@link http://pcmiler.alk.com/APIs/SOAP/v1.0/Service.svc?xsd=xsd0}
 * @package PCMWS
 * @subpackage Structs
 * @author WsdlToPhp Team <contact@wsdltophp.com>
 * @version 20150429-01
 * @date 2016-08-25
 */
class PCMWSStructArrayOfWeatherAlertEvent extends PCMWSWsdlClass
{
    /**
     * The WeatherAlertEvent
     * Meta informations extracted from the WSDL
     * - maxOccurs : unbounded
     * - minOccurs : 0
     * - nillable : true
     * @var PCMWSStructWeatherAlertEvent
     */
    public $WeatherAlertEvent;
    /**
     * Constructor method for ArrayOfWeatherAlertEvent
     * @see parent::__construct()
     * @param PCMWSStructWeatherAlertEvent $_weatherAlertEvent
     * @return PCMWSStructArrayOfWeatherAlertEvent
     */
    public function __construct($_weatherAlertEvent = NULL)
    {
        parent::__construct(array('WeatherAlertEvent'=>$_weatherAlertEvent),false);
    }
    /**
     * Get WeatherAlertEvent value
     * @return PCMWSStructWeatherAlertEvent|null
     */
    public function getWeatherAlertEvent()
    {
        return $this->WeatherAlertEvent;
    }
    /**
     * Set WeatherAlertEvent value
     * @param PCMWSStructWeatherAlertEvent $_weatherAlertEvent the WeatherAlertEvent
     * @return PCMWSStructWeatherAlertEvent
     */
    public function setWeatherAlertEvent($_weatherAlertEvent)
    {
        return ($this->WeatherAlertEvent = $_weatherAlertEvent);
    }
    /**
     * Returns the current element
     * @see PCMWSWsdlClass::current()
     * @return PCMWSStructWeatherAlertEvent
     */
    public function current()
    {
        return parent::current();
    }
    /**
     * Returns the indexed element
     * @see PCMWSWsdlClass::item()
     * @param int $_index
     * @return PCMWSStructWeatherAlertEvent
     */
    public function item($_index)
    {
        return parent::item($_index);
    }
    /**
     * Returns the first element
     * @see PCMWSWsdlClass::first()
     * @return PCMWSStructWeatherAlertEvent
     */
    public function first()
    {
        return parent::first();
    }
    /**
     * Returns the last element
     * @see PCMWSWsdlClass::last()
     * @return PCMWSStructWeatherAlertEvent
     */
    public function last()
    {
        return parent::last();
    }
    /**
     * Returns the element at the offset
     * @see PCMWSWsdlClass::last()
     * @param int $_offset
     * @return PCMWSStructWeatherAlertEvent
     */
    public function offsetGet($_offset)
    {
        return parent::offsetGet($_offset);
    }
    /**
     * Returns the attribute name
     * @see PCMWSWsdlClass::getAttributeName()
     * @return string WeatherAlertEvent
     */
    public function getAttributeName()
    {
        return 'WeatherAlertEvent';
    }
    /**
     * Method called when an object has been exported with var_export() functions
     * It allows to return an object instantiated with the values
     * @see PCMWSWsdlClass::__set_state()
     * @uses PCMWSWsdlClass::__set_state()
     * @param array $_array the exported values
     * @return PCMWSStructArrayOfWeatherAlertEvent
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
