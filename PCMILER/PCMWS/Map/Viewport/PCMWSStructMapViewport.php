<?php
/**
 * File for class PCMWSStructMapViewport
 * @package PCMWS
 * @subpackage Structs
 * @author WsdlToPhp Team <contact@wsdltophp.com>
 * @version 20150429-01
 * @date 2016-08-25
 */
/**
 * This class stands for PCMWSStructMapViewport originally named MapViewport
 * Meta informations extracted from the WSDL
 * - from schema : {@link http://pcmiler.alk.com/APIs/SOAP/v1.0/Service.svc?xsd=xsd0}
 * @package PCMWS
 * @subpackage Structs
 * @author WsdlToPhp Team <contact@wsdltophp.com>
 * @version 20150429-01
 * @date 2016-08-25
 */
class PCMWSStructMapViewport extends PCMWSWsdlClass
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
     * The ScreenCenter
     * Meta informations extracted from the WSDL
     * - minOccurs : 0
     * - nillable : true
     * @var PCMWSStructPoint
     */
    public $ScreenCenter;
    /**
     * The ZoomRadius
     * Meta informations extracted from the WSDL
     * - minOccurs : 0
     * @var double
     */
    public $ZoomRadius;
    /**
     * The CornerA
     * Meta informations extracted from the WSDL
     * - minOccurs : 0
     * - nillable : true
     * @var PCMWSStructCoordinates
     */
    public $CornerA;
    /**
     * The CornerB
     * Meta informations extracted from the WSDL
     * - minOccurs : 0
     * - nillable : true
     * @var PCMWSStructCoordinates
     */
    public $CornerB;
    /**
     * The Region
     * Meta informations extracted from the WSDL
     * - minOccurs : 0
     * @var PCMWSEnumMapRegion
     */
    public $Region;
    /**
     * Constructor method for MapViewport
     * @see parent::__construct()
     * @param PCMWSStructCoordinates $_center
     * @param PCMWSStructPoint $_screenCenter
     * @param double $_zoomRadius
     * @param PCMWSStructCoordinates $_cornerA
     * @param PCMWSStructCoordinates $_cornerB
     * @param PCMWSEnumMapRegion $_region
     * @return PCMWSStructMapViewport
     */
    public function __construct($_center = NULL,$_screenCenter = NULL,$_zoomRadius = NULL,$_cornerA = NULL,$_cornerB = NULL,$_region = NULL)
    {
        parent::__construct(array('Center'=>$_center,'ScreenCenter'=>$_screenCenter,'ZoomRadius'=>$_zoomRadius,'CornerA'=>$_cornerA,'CornerB'=>$_cornerB,'Region'=>$_region),false);
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
     * Get ScreenCenter value
     * @return PCMWSStructPoint|null
     */
    public function getScreenCenter()
    {
        return $this->ScreenCenter;
    }
    /**
     * Set ScreenCenter value
     * @param PCMWSStructPoint $_screenCenter the ScreenCenter
     * @return PCMWSStructPoint
     */
    public function setScreenCenter($_screenCenter)
    {
        return ($this->ScreenCenter = $_screenCenter);
    }
    /**
     * Get ZoomRadius value
     * @return double|null
     */
    public function getZoomRadius()
    {
        return $this->ZoomRadius;
    }
    /**
     * Set ZoomRadius value
     * @param double $_zoomRadius the ZoomRadius
     * @return double
     */
    public function setZoomRadius($_zoomRadius)
    {
        return ($this->ZoomRadius = $_zoomRadius);
    }
    /**
     * Get CornerA value
     * @return PCMWSStructCoordinates|null
     */
    public function getCornerA()
    {
        return $this->CornerA;
    }
    /**
     * Set CornerA value
     * @param PCMWSStructCoordinates $_cornerA the CornerA
     * @return PCMWSStructCoordinates
     */
    public function setCornerA($_cornerA)
    {
        return ($this->CornerA = $_cornerA);
    }
    /**
     * Get CornerB value
     * @return PCMWSStructCoordinates|null
     */
    public function getCornerB()
    {
        return $this->CornerB;
    }
    /**
     * Set CornerB value
     * @param PCMWSStructCoordinates $_cornerB the CornerB
     * @return PCMWSStructCoordinates
     */
    public function setCornerB($_cornerB)
    {
        return ($this->CornerB = $_cornerB);
    }
    /**
     * Get Region value
     * @return PCMWSEnumMapRegion|null
     */
    public function getRegion()
    {
        return $this->Region;
    }
    /**
     * Set Region value
     * @uses PCMWSEnumMapRegion::valueIsValid()
     * @param PCMWSEnumMapRegion $_region the Region
     * @return PCMWSEnumMapRegion
     */
    public function setRegion($_region)
    {
        if(!PCMWSEnumMapRegion::valueIsValid($_region))
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
     * @return PCMWSStructMapViewport
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
