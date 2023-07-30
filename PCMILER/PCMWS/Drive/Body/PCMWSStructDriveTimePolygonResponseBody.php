<?php
/**
 * File for class PCMWSStructDriveTimePolygonResponseBody
 * @package PCMWS
 * @subpackage Structs
 * @author WsdlToPhp Team <contact@wsdltophp.com>
 * @version 20150429-01
 * @date 2016-08-25
 */
/**
 * This class stands for PCMWSStructDriveTimePolygonResponseBody originally named DriveTimePolygonResponseBody
 * Meta informations extracted from the WSDL
 * - from schema : {@link http://pcmiler.alk.com/APIs/SOAP/v1.0/Service.svc?xsd=xsd0}
 * @package PCMWS
 * @subpackage Structs
 * @author WsdlToPhp Team <contact@wsdltophp.com>
 * @version 20150429-01
 * @date 2016-08-25
 */
class PCMWSStructDriveTimePolygonResponseBody extends PCMWSWsdlClass
{
    /**
     * The PolygonPoints
     * Meta informations extracted from the WSDL
     * - minOccurs : 0
     * - nillable : true
     * @var PCMWSStructArrayOfCoordinates
     */
    public $PolygonPoints;
    /**
     * Constructor method for DriveTimePolygonResponseBody
     * @see parent::__construct()
     * @param PCMWSStructArrayOfCoordinates $_polygonPoints
     * @return PCMWSStructDriveTimePolygonResponseBody
     */
    public function __construct($_polygonPoints = NULL)
    {
        parent::__construct(array('PolygonPoints'=>($_polygonPoints instanceof PCMWSStructArrayOfCoordinates)?$_polygonPoints:new PCMWSStructArrayOfCoordinates($_polygonPoints)),false);
    }
    /**
     * Get PolygonPoints value
     * @return PCMWSStructArrayOfCoordinates|null
     */
    public function getPolygonPoints()
    {
        return $this->PolygonPoints;
    }
    /**
     * Set PolygonPoints value
     * @param PCMWSStructArrayOfCoordinates $_polygonPoints the PolygonPoints
     * @return PCMWSStructArrayOfCoordinates
     */
    public function setPolygonPoints($_polygonPoints)
    {
        return ($this->PolygonPoints = $_polygonPoints);
    }
    /**
     * Method called when an object has been exported with var_export() functions
     * It allows to return an object instantiated with the values
     * @see PCMWSWsdlClass::__set_state()
     * @uses PCMWSWsdlClass::__set_state()
     * @param array $_array the exported values
     * @return PCMWSStructDriveTimePolygonResponseBody
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
