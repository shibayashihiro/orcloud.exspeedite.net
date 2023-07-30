<?php
/**
 * File for class PCMWSStructPOIAlongRouteMatch
 * @package PCMWS
 * @subpackage Structs
 * @author WsdlToPhp Team <contact@wsdltophp.com>
 * @version 20150429-01
 * @date 2016-08-25
 */
/**
 * This class stands for PCMWSStructPOIAlongRouteMatch originally named POIAlongRouteMatch
 * Meta informations extracted from the WSDL
 * - from schema : {@link http://pcmiler.alk.com/APIs/SOAP/v1.0/Service.svc?xsd=xsd0}
 * @package PCMWS
 * @subpackage Structs
 * @author WsdlToPhp Team <contact@wsdltophp.com>
 * @version 20150429-01
 * @date 2016-08-25
 */
class PCMWSStructPOIAlongRouteMatch extends PCMWSWsdlClass
{
    /**
     * The POILocation
     * Meta informations extracted from the WSDL
     * - minOccurs : 0
     * - nillable : true
     * @var PCMWSStructLocation
     */
    public $POILocation;
    /**
     * The DistanceFromOrigin
     * Meta informations extracted from the WSDL
     * - minOccurs : 0
     * - nillable : true
     * @var PCMWSStructDistance
     */
    public $DistanceFromOrigin;
    /**
     * The POICategory
     * Meta informations extracted from the WSDL
     * - minOccurs : 0
     * - nillable : true
     * @var string
     */
    public $POICategory;
    /**
     * The TimeFromOrigin
     * Meta informations extracted from the WSDL
     * - minOccurs : 0
     * - nillable : true
     * @var string
     */
    public $TimeFromOrigin;
    /**
     * The DistanceOffRoute
     * Meta informations extracted from the WSDL
     * - minOccurs : 0
     * - nillable : true
     * @var PCMWSStructDistance
     */
    public $DistanceOffRoute;
    /**
     * Constructor method for POIAlongRouteMatch
     * @see parent::__construct()
     * @param PCMWSStructLocation $_pOILocation
     * @param PCMWSStructDistance $_distanceFromOrigin
     * @param string $_pOICategory
     * @param string $_timeFromOrigin
     * @param PCMWSStructDistance $_distanceOffRoute
     * @return PCMWSStructPOIAlongRouteMatch
     */
    public function __construct($_pOILocation = NULL,$_distanceFromOrigin = NULL,$_pOICategory = NULL,$_timeFromOrigin = NULL,$_distanceOffRoute = NULL)
    {
        parent::__construct(array('POILocation'=>$_pOILocation,'DistanceFromOrigin'=>$_distanceFromOrigin,'POICategory'=>$_pOICategory,'TimeFromOrigin'=>$_timeFromOrigin,'DistanceOffRoute'=>$_distanceOffRoute),false);
    }
    /**
     * Get POILocation value
     * @return PCMWSStructLocation|null
     */
    public function getPOILocation()
    {
        return $this->POILocation;
    }
    /**
     * Set POILocation value
     * @param PCMWSStructLocation $_pOILocation the POILocation
     * @return PCMWSStructLocation
     */
    public function setPOILocation($_pOILocation)
    {
        return ($this->POILocation = $_pOILocation);
    }
    /**
     * Get DistanceFromOrigin value
     * @return PCMWSStructDistance|null
     */
    public function getDistanceFromOrigin()
    {
        return $this->DistanceFromOrigin;
    }
    /**
     * Set DistanceFromOrigin value
     * @param PCMWSStructDistance $_distanceFromOrigin the DistanceFromOrigin
     * @return PCMWSStructDistance
     */
    public function setDistanceFromOrigin($_distanceFromOrigin)
    {
        return ($this->DistanceFromOrigin = $_distanceFromOrigin);
    }
    /**
     * Get POICategory value
     * @return string|null
     */
    public function getPOICategory()
    {
        return $this->POICategory;
    }
    /**
     * Set POICategory value
     * @param string $_pOICategory the POICategory
     * @return string
     */
    public function setPOICategory($_pOICategory)
    {
        return ($this->POICategory = $_pOICategory);
    }
    /**
     * Get TimeFromOrigin value
     * @return string|null
     */
    public function getTimeFromOrigin()
    {
        return $this->TimeFromOrigin;
    }
    /**
     * Set TimeFromOrigin value
     * @param string $_timeFromOrigin the TimeFromOrigin
     * @return string
     */
    public function setTimeFromOrigin($_timeFromOrigin)
    {
        return ($this->TimeFromOrigin = $_timeFromOrigin);
    }
    /**
     * Get DistanceOffRoute value
     * @return PCMWSStructDistance|null
     */
    public function getDistanceOffRoute()
    {
        return $this->DistanceOffRoute;
    }
    /**
     * Set DistanceOffRoute value
     * @param PCMWSStructDistance $_distanceOffRoute the DistanceOffRoute
     * @return PCMWSStructDistance
     */
    public function setDistanceOffRoute($_distanceOffRoute)
    {
        return ($this->DistanceOffRoute = $_distanceOffRoute);
    }
    /**
     * Method called when an object has been exported with var_export() functions
     * It allows to return an object instantiated with the values
     * @see PCMWSWsdlClass::__set_state()
     * @uses PCMWSWsdlClass::__set_state()
     * @param array $_array the exported values
     * @return PCMWSStructPOIAlongRouteMatch
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
