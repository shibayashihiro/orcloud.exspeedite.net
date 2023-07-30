<?php
/**
 * File for class PCMWSStructArrayOfGeocodeLocation
 * @package PCMWS
 * @subpackage Structs
 * @author WsdlToPhp Team <contact@wsdltophp.com>
 * @version 20150429-01
 * @date 2016-08-25
 */
/**
 * This class stands for PCMWSStructArrayOfGeocodeLocation originally named ArrayOfGeocodeLocation
 * Meta informations extracted from the WSDL
 * - from schema : {@link http://pcmiler.alk.com/APIs/SOAP/v1.0/Service.svc?xsd=xsd0}
 * @package PCMWS
 * @subpackage Structs
 * @author WsdlToPhp Team <contact@wsdltophp.com>
 * @version 20150429-01
 * @date 2016-08-25
 */
class PCMWSStructArrayOfGeocodeLocation extends PCMWSWsdlClass
{
    /**
     * The GeocodeLocation
     * Meta informations extracted from the WSDL
     * - maxOccurs : unbounded
     * - minOccurs : 0
     * - nillable : true
     * @var PCMWSStructGeocodeLocation
     */
    public $GeocodeLocation;
    /**
     * Constructor method for ArrayOfGeocodeLocation
     * @see parent::__construct()
     * @param PCMWSStructGeocodeLocation $_geocodeLocation
     * @return PCMWSStructArrayOfGeocodeLocation
     */
    public function __construct($_geocodeLocation = NULL)
    {
        parent::__construct(array('GeocodeLocation'=>$_geocodeLocation),false);
    }
    /**
     * Get GeocodeLocation value
     * @return PCMWSStructGeocodeLocation|null
     */
    public function getGeocodeLocation()
    {
        return $this->GeocodeLocation;
    }
    /**
     * Set GeocodeLocation value
     * @param PCMWSStructGeocodeLocation $_geocodeLocation the GeocodeLocation
     * @return PCMWSStructGeocodeLocation
     */
    public function setGeocodeLocation($_geocodeLocation)
    {
        return ($this->GeocodeLocation = $_geocodeLocation);
    }
    /**
     * Returns the current element
     * @see PCMWSWsdlClass::current()
     * @return PCMWSStructGeocodeLocation
     */
    public function current()
    {
        return parent::current();
    }
    /**
     * Returns the indexed element
     * @see PCMWSWsdlClass::item()
     * @param int $_index
     * @return PCMWSStructGeocodeLocation
     */
    public function item($_index)
    {
        return parent::item($_index);
    }
    /**
     * Returns the first element
     * @see PCMWSWsdlClass::first()
     * @return PCMWSStructGeocodeLocation
     */
    public function first()
    {
        return parent::first();
    }
    /**
     * Returns the last element
     * @see PCMWSWsdlClass::last()
     * @return PCMWSStructGeocodeLocation
     */
    public function last()
    {
        return parent::last();
    }
    /**
     * Returns the element at the offset
     * @see PCMWSWsdlClass::last()
     * @param int $_offset
     * @return PCMWSStructGeocodeLocation
     */
    public function offsetGet($_offset)
    {
        return parent::offsetGet($_offset);
    }
    /**
     * Returns the attribute name
     * @see PCMWSWsdlClass::getAttributeName()
     * @return string GeocodeLocation
     */
    public function getAttributeName()
    {
        return 'GeocodeLocation';
    }
    /**
     * Method called when an object has been exported with var_export() functions
     * It allows to return an object instantiated with the values
     * @see PCMWSWsdlClass::__set_state()
     * @uses PCMWSWsdlClass::__set_state()
     * @param array $_array the exported values
     * @return PCMWSStructArrayOfGeocodeLocation
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
