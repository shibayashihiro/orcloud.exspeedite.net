<?php
/**
 * File for class PCMWSStructCoordinates
 * @package PCMWS
 * @subpackage Structs
 * @author WsdlToPhp Team <contact@wsdltophp.com>
 * @version 20150429-01
 * @date 2016-08-25
 */
/**
 * This class stands for PCMWSStructCoordinates originally named Coordinates
 * Meta informations extracted from the WSDL
 * - from schema : {@link http://pcmiler.alk.com/APIs/SOAP/v1.0/Service.svc?xsd=xsd0}
 * @package PCMWS
 * @subpackage Structs
 * @author WsdlToPhp Team <contact@wsdltophp.com>
 * @version 20150429-01
 * @date 2016-08-25
 */
class PCMWSStructCoordinates extends PCMWSWsdlClass
{
    /**
     * The Lat
     * Meta informations extracted from the WSDL
     * - minOccurs : 0
     * - nillable : true
     * @var string
     */
    public $Lat;
    /**
     * The Lon
     * Meta informations extracted from the WSDL
     * - minOccurs : 0
     * - nillable : true
     * @var string
     */
    public $Lon;
    /**
     * Constructor method for Coordinates
     * @see parent::__construct()
     * @param string $_lat
     * @param string $_lon
     * @return PCMWSStructCoordinates
     */
    public function __construct($_lat = NULL,$_lon = NULL)
    {
        parent::__construct(array('Lat'=>$_lat,'Lon'=>$_lon),false);
    }
    /**
     * Get Lat value
     * @return string|null
     */
    public function getLat()
    {
        return $this->Lat;
    }
    /**
     * Set Lat value
     * @param string $_lat the Lat
     * @return string
     */
    public function setLat($_lat)
    {
        return ($this->Lat = $_lat);
    }
    /**
     * Get Lon value
     * @return string|null
     */
    public function getLon()
    {
        return $this->Lon;
    }
    /**
     * Set Lon value
     * @param string $_lon the Lon
     * @return string
     */
    public function setLon($_lon)
    {
        return ($this->Lon = $_lon);
    }
    /**
     * Method called when an object has been exported with var_export() functions
     * It allows to return an object instantiated with the values
     * @see PCMWSWsdlClass::__set_state()
     * @uses PCMWSWsdlClass::__set_state()
     * @param array $_array the exported values
     * @return PCMWSStructCoordinates
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
