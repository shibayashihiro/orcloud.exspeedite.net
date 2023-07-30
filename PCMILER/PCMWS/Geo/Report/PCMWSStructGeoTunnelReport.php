<?php
/**
 * File for class PCMWSStructGeoTunnelReport
 * @package PCMWS
 * @subpackage Structs
 * @author WsdlToPhp Team <contact@wsdltophp.com>
 * @version 20150429-01
 * @date 2016-08-25
 */
/**
 * This class stands for PCMWSStructGeoTunnelReport originally named GeoTunnelReport
 * Meta informations extracted from the WSDL
 * - from schema : {@link http://pcmiler.alk.com/APIs/SOAP/v1.0/Service.svc?xsd=xsd0}
 * @package PCMWS
 * @subpackage Structs
 * @author WsdlToPhp Team <contact@wsdltophp.com>
 * @version 20150429-01
 * @date 2016-08-25
 */
class PCMWSStructGeoTunnelReport extends PCMWSStructReport
{
    /**
     * The GeoTunnelPoints
     * Meta informations extracted from the WSDL
     * - minOccurs : 0
     * - nillable : true
     * @var PCMWSStructArrayOfCoordinates
     */
    public $GeoTunnelPoints;
    /**
     * Constructor method for GeoTunnelReport
     * @see parent::__construct()
     * @param PCMWSStructArrayOfCoordinates $_geoTunnelPoints
     * @return PCMWSStructGeoTunnelReport
     */
    public function __construct($_geoTunnelPoints = NULL)
    {
        PCMWSWsdlClass::__construct(array('GeoTunnelPoints'=>($_geoTunnelPoints instanceof PCMWSStructArrayOfCoordinates)?$_geoTunnelPoints:new PCMWSStructArrayOfCoordinates($_geoTunnelPoints)),false);
    }
    /**
     * Get GeoTunnelPoints value
     * @return PCMWSStructArrayOfCoordinates|null
     */
    public function getGeoTunnelPoints()
    {
        return $this->GeoTunnelPoints;
    }
    /**
     * Set GeoTunnelPoints value
     * @param PCMWSStructArrayOfCoordinates $_geoTunnelPoints the GeoTunnelPoints
     * @return PCMWSStructArrayOfCoordinates
     */
    public function setGeoTunnelPoints($_geoTunnelPoints)
    {
        return ($this->GeoTunnelPoints = $_geoTunnelPoints);
    }
    /**
     * Method called when an object has been exported with var_export() functions
     * It allows to return an object instantiated with the values
     * @see PCMWSWsdlClass::__set_state()
     * @uses PCMWSWsdlClass::__set_state()
     * @param array $_array the exported values
     * @return PCMWSStructGeoTunnelReport
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
