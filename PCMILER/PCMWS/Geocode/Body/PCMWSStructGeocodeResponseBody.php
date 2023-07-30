<?php
/**
 * File for class PCMWSStructGeocodeResponseBody
 * @package PCMWS
 * @subpackage Structs
 * @author WsdlToPhp Team <contact@wsdltophp.com>
 * @version 20150429-01
 * @date 2016-08-25
 */
/**
 * This class stands for PCMWSStructGeocodeResponseBody originally named GeocodeResponseBody
 * Meta informations extracted from the WSDL
 * - from schema : {@link http://pcmiler.alk.com/APIs/SOAP/v1.0/Service.svc?xsd=xsd0}
 * @package PCMWS
 * @subpackage Structs
 * @author WsdlToPhp Team <contact@wsdltophp.com>
 * @version 20150429-01
 * @date 2016-08-25
 */
class PCMWSStructGeocodeResponseBody extends PCMWSWsdlClass
{
    /**
     * The Locations
     * Meta informations extracted from the WSDL
     * - minOccurs : 0
     * - nillable : true
     * @var PCMWSStructArrayOfGeocodeOutputLocation
     */
    public $Locations;
    /**
     * Constructor method for GeocodeResponseBody
     * @see parent::__construct()
     * @param PCMWSStructArrayOfGeocodeOutputLocation $_locations
     * @return PCMWSStructGeocodeResponseBody
     */
    public function __construct($_locations = NULL)
    {
        parent::__construct(array('Locations'=>($_locations instanceof PCMWSStructArrayOfGeocodeOutputLocation)?$_locations:new PCMWSStructArrayOfGeocodeOutputLocation($_locations)),false);
    }
    /**
     * Get Locations value
     * @return PCMWSStructArrayOfGeocodeOutputLocation|null
     */
    public function getLocations()
    {
        return $this->Locations;
    }
    /**
     * Set Locations value
     * @param PCMWSStructArrayOfGeocodeOutputLocation $_locations the Locations
     * @return PCMWSStructArrayOfGeocodeOutputLocation
     */
    public function setLocations($_locations)
    {
        return ($this->Locations = $_locations);
    }
    /**
     * Method called when an object has been exported with var_export() functions
     * It allows to return an object instantiated with the values
     * @see PCMWSWsdlClass::__set_state()
     * @uses PCMWSWsdlClass::__set_state()
     * @param array $_array the exported values
     * @return PCMWSStructGeocodeResponseBody
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
