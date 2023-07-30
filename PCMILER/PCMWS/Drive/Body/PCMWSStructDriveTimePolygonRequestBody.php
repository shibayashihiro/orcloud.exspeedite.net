<?php
/**
 * File for class PCMWSStructDriveTimePolygonRequestBody
 * @package PCMWS
 * @subpackage Structs
 * @author WsdlToPhp Team <contact@wsdltophp.com>
 * @version 20150429-01
 * @date 2016-08-25
 */
/**
 * This class stands for PCMWSStructDriveTimePolygonRequestBody originally named DriveTimePolygonRequestBody
 * Meta informations extracted from the WSDL
 * - from schema : {@link http://pcmiler.alk.com/APIs/SOAP/v1.0/Service.svc?xsd=xsd0}
 * @package PCMWS
 * @subpackage Structs
 * @author WsdlToPhp Team <contact@wsdltophp.com>
 * @version 20150429-01
 * @date 2016-08-25
 */
class PCMWSStructDriveTimePolygonRequestBody extends PCMWSWsdlClass
{
    /**
     * The Center
     * Meta informations extracted from the WSDL
     * - minOccurs : 0
     * - nillable : true
     * @var PCMWSStructCoordinates
     */
    public $Center;
    /**
     * The Minutes
     * Meta informations extracted from the WSDL
     * - minOccurs : 0
     * @var int
     */
    public $Minutes;
    /**
     * The RouteOptions
     * Meta informations extracted from the WSDL
     * - minOccurs : 0
     * - nillable : true
     * @var PCMWSStructRouteOptions
     */
    public $RouteOptions;
    /**
     * The Region
     * Meta informations extracted from the WSDL
     * - minOccurs : 0
     * @var PCMWSEnumDataRegion
     */
    public $Region;
    /**
     * Constructor method for DriveTimePolygonRequestBody
     * @see parent::__construct()
     * @param PCMWSStructCoordinates $_center
     * @param int $_minutes
     * @param PCMWSStructRouteOptions $_routeOptions
     * @param PCMWSEnumDataRegion $_region
     * @return PCMWSStructDriveTimePolygonRequestBody
     */
    public function __construct($_center = NULL,$_minutes = NULL,$_routeOptions = NULL,$_region = NULL)
    {
        parent::__construct(array('Center'=>$_center,'Minutes'=>$_minutes,'RouteOptions'=>$_routeOptions,'Region'=>$_region),false);
    }
    /**
     * Get Center value
     * @return PCMWSStructCoordinates|null
     */
    public function getCenter()
    {
        return $this->Center;
    }
    /**
     * Set Center value
     * @param PCMWSStructCoordinates $_center the Center
     * @return PCMWSStructCoordinates
     */
    public function setCenter($_center)
    {
        return ($this->Center = $_center);
    }
    /**
     * Get Minutes value
     * @return int|null
     */
    public function getMinutes()
    {
        return $this->Minutes;
    }
    /**
     * Set Minutes value
     * @param int $_minutes the Minutes
     * @return int
     */
    public function setMinutes($_minutes)
    {
        return ($this->Minutes = $_minutes);
    }
    /**
     * Get RouteOptions value
     * @return PCMWSStructRouteOptions|null
     */
    public function getRouteOptions()
    {
        return $this->RouteOptions;
    }
    /**
     * Set RouteOptions value
     * @param PCMWSStructRouteOptions $_routeOptions the RouteOptions
     * @return PCMWSStructRouteOptions
     */
    public function setRouteOptions($_routeOptions)
    {
        return ($this->RouteOptions = $_routeOptions);
    }
    /**
     * Get Region value
     * @return PCMWSEnumDataRegion|null
     */
    public function getRegion()
    {
        return $this->Region;
    }
    /**
     * Set Region value
     * @uses PCMWSEnumDataRegion::valueIsValid()
     * @param PCMWSEnumDataRegion $_region the Region
     * @return PCMWSEnumDataRegion
     */
    public function setRegion($_region)
    {
        if(!PCMWSEnumDataRegion::valueIsValid($_region))
        {
            return false;
        }
        return ($this->Region = $_region);
    }
    /**
     * Method called when an object has been exported with var_export() functions
     * It allows to return an object instantiated with the values
     * @see PCMWSWsdlClass::__set_state()
     * @uses PCMWSWsdlClass::__set_state()
     * @param array $_array the exported values
     * @return PCMWSStructDriveTimePolygonRequestBody
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
