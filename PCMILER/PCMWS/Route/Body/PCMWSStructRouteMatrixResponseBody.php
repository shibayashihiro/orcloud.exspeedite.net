<?php
/**
 * File for class PCMWSStructRouteMatrixResponseBody
 * @package PCMWS
 * @subpackage Structs
 * @author WsdlToPhp Team <contact@wsdltophp.com>
 * @version 20150429-01
 * @date 2016-08-25
 */
/**
 * This class stands for PCMWSStructRouteMatrixResponseBody originally named RouteMatrixResponseBody
 * Meta informations extracted from the WSDL
 * - from schema : {@link http://pcmiler.alk.com/APIs/SOAP/v1.0/Service.svc?xsd=xsd0}
 * @package PCMWS
 * @subpackage Structs
 * @author WsdlToPhp Team <contact@wsdltophp.com>
 * @version 20150429-01
 * @date 2016-08-25
 */
class PCMWSStructRouteMatrixResponseBody extends PCMWSWsdlClass
{
    /**
     * The Origins
     * Meta informations extracted from the WSDL
     * - minOccurs : 0
     * - nillable : true
     * @var PCMWSStructArrayOfGeocodeOutputLocation
     */
    public $Origins;
    /**
     * The Destinations
     * Meta informations extracted from the WSDL
     * - minOccurs : 0
     * - nillable : true
     * @var PCMWSStructArrayOfGeocodeOutputLocation
     */
    public $Destinations;
    /**
     * The MatrixInfo
     * Meta informations extracted from the WSDL
     * - minOccurs : 0
     * - nillable : true
     * @var PCMWSStructArrayOfArrayOfRouteMatrixInfo
     */
    public $MatrixInfo;
    /**
     * Constructor method for RouteMatrixResponseBody
     * @see parent::__construct()
     * @param PCMWSStructArrayOfGeocodeOutputLocation $_origins
     * @param PCMWSStructArrayOfGeocodeOutputLocation $_destinations
     * @param PCMWSStructArrayOfArrayOfRouteMatrixInfo $_matrixInfo
     * @return PCMWSStructRouteMatrixResponseBody
     */
    public function __construct($_origins = NULL,$_destinations = NULL,$_matrixInfo = NULL)
    {
        parent::__construct(array('Origins'=>($_origins instanceof PCMWSStructArrayOfGeocodeOutputLocation)?$_origins:new PCMWSStructArrayOfGeocodeOutputLocation($_origins),'Destinations'=>($_destinations instanceof PCMWSStructArrayOfGeocodeOutputLocation)?$_destinations:new PCMWSStructArrayOfGeocodeOutputLocation($_destinations),'MatrixInfo'=>($_matrixInfo instanceof PCMWSStructArrayOfArrayOfRouteMatrixInfo)?$_matrixInfo:new PCMWSStructArrayOfArrayOfRouteMatrixInfo($_matrixInfo)),false);
    }
    /**
     * Get Origins value
     * @return PCMWSStructArrayOfGeocodeOutputLocation|null
     */
    public function getOrigins()
    {
        return $this->Origins;
    }
    /**
     * Set Origins value
     * @param PCMWSStructArrayOfGeocodeOutputLocation $_origins the Origins
     * @return PCMWSStructArrayOfGeocodeOutputLocation
     */
    public function setOrigins($_origins)
    {
        return ($this->Origins = $_origins);
    }
    /**
     * Get Destinations value
     * @return PCMWSStructArrayOfGeocodeOutputLocation|null
     */
    public function getDestinations()
    {
        return $this->Destinations;
    }
    /**
     * Set Destinations value
     * @param PCMWSStructArrayOfGeocodeOutputLocation $_destinations the Destinations
     * @return PCMWSStructArrayOfGeocodeOutputLocation
     */
    public function setDestinations($_destinations)
    {
        return ($this->Destinations = $_destinations);
    }
    /**
     * Get MatrixInfo value
     * @return PCMWSStructArrayOfArrayOfRouteMatrixInfo|null
     */
    public function getMatrixInfo()
    {
        return $this->MatrixInfo;
    }
    /**
     * Set MatrixInfo value
     * @param PCMWSStructArrayOfArrayOfRouteMatrixInfo $_matrixInfo the MatrixInfo
     * @return PCMWSStructArrayOfArrayOfRouteMatrixInfo
     */
    public function setMatrixInfo($_matrixInfo)
    {
        return ($this->MatrixInfo = $_matrixInfo);
    }
    /**
     * Method called when an object has been exported with var_export() functions
     * It allows to return an object instantiated with the values
     * @see PCMWSWsdlClass::__set_state()
     * @uses PCMWSWsdlClass::__set_state()
     * @param array $_array the exported values
     * @return PCMWSStructRouteMatrixResponseBody
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
