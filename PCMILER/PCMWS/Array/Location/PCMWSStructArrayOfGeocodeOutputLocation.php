<?php
/**
 * File for class PCMWSStructArrayOfGeocodeOutputLocation
 * @package PCMWS
 * @subpackage Structs
 * @author WsdlToPhp Team <contact@wsdltophp.com>
 * @version 20150429-01
 * @date 2016-08-25
 */
/**
 * This class stands for PCMWSStructArrayOfGeocodeOutputLocation originally named ArrayOfGeocodeOutputLocation
 * Meta informations extracted from the WSDL
 * - from schema : {@link http://pcmiler.alk.com/APIs/SOAP/v1.0/Service.svc?xsd=xsd0}
 * @package PCMWS
 * @subpackage Structs
 * @author WsdlToPhp Team <contact@wsdltophp.com>
 * @version 20150429-01
 * @date 2016-08-25
 */
class PCMWSStructArrayOfGeocodeOutputLocation extends PCMWSWsdlClass
{
    /**
     * The GeocodeOutputLocation
     * Meta informations extracted from the WSDL
     * - maxOccurs : unbounded
     * - minOccurs : 0
     * - nillable : true
     * @var PCMWSStructGeocodeOutputLocation
     */
    public $GeocodeOutputLocation;
    /**
     * Constructor method for ArrayOfGeocodeOutputLocation
     * @see parent::__construct()
     * @param PCMWSStructGeocodeOutputLocation $_geocodeOutputLocation
     * @return PCMWSStructArrayOfGeocodeOutputLocation
     */
    public function __construct($_geocodeOutputLocation = NULL)
    {
        parent::__construct(array('GeocodeOutputLocation'=>$_geocodeOutputLocation),false);
    }
    /**
     * Get GeocodeOutputLocation value
     * @return PCMWSStructGeocodeOutputLocation|null
     */
    public function getGeocodeOutputLocation()
    {
        return $this->GeocodeOutputLocation;
    }
    /**
     * Set GeocodeOutputLocation value
     * @param PCMWSStructGeocodeOutputLocation $_geocodeOutputLocation the GeocodeOutputLocation
     * @return PCMWSStructGeocodeOutputLocation
     */
    public function setGeocodeOutputLocation($_geocodeOutputLocation)
    {
        return ($this->GeocodeOutputLocation = $_geocodeOutputLocation);
    }
    /**
     * Returns the current element
     * @see PCMWSWsdlClass::current()
     * @return PCMWSStructGeocodeOutputLocation
     */
    public function current()
    {
        return parent::current();
    }
    /**
     * Returns the indexed element
     * @see PCMWSWsdlClass::item()
     * @param int $_index
     * @return PCMWSStructGeocodeOutputLocation
     */
    public function item($_index)
    {
        return parent::item($_index);
    }
    /**
     * Returns the first element
     * @see PCMWSWsdlClass::first()
     * @return PCMWSStructGeocodeOutputLocation
     */
    public function first()
    {
        return parent::first();
    }
    /**
     * Returns the last element
     * @see PCMWSWsdlClass::last()
     * @return PCMWSStructGeocodeOutputLocation
     */
    public function last()
    {
        return parent::last();
    }
    /**
     * Returns the element at the offset
     * @see PCMWSWsdlClass::last()
     * @param int $_offset
     * @return PCMWSStructGeocodeOutputLocation
     */
    public function offsetGet($_offset)
    {
        return parent::offsetGet($_offset);
    }
    /**
     * Returns the attribute name
     * @see PCMWSWsdlClass::getAttributeName()
     * @return string GeocodeOutputLocation
     */
    public function getAttributeName()
    {
        return 'GeocodeOutputLocation';
    }
    /**
     * Method called when an object has been exported with var_export() functions
     * It allows to return an object instantiated with the values
     * @see PCMWSWsdlClass::__set_state()
     * @uses PCMWSWsdlClass::__set_state()
     * @param array $_array the exported values
     * @return PCMWSStructArrayOfGeocodeOutputLocation
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
