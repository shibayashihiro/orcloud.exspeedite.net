<?php
/**
 * File for class PCMWSStructReverseGeocodeRequestBody
 * @package PCMWS
 * @subpackage Structs
 * @author WsdlToPhp Team <contact@wsdltophp.com>
 * @version 20150429-01
 * @date 2016-08-25
 */
/**
 * This class stands for PCMWSStructReverseGeocodeRequestBody originally named ReverseGeocodeRequestBody
 * Meta informations extracted from the WSDL
 * - from schema : {@link http://pcmiler.alk.com/APIs/SOAP/v1.0/Service.svc?xsd=xsd0}
 * @package PCMWS
 * @subpackage Structs
 * @author WsdlToPhp Team <contact@wsdltophp.com>
 * @version 20150429-01
 * @date 2016-08-25
 */
class PCMWSStructReverseGeocodeRequestBody extends PCMWSWsdlClass
{
    /**
     * The Coords
     * Meta informations extracted from the WSDL
     * - minOccurs : 0
     * - nillable : true
     * @var PCMWSStructArrayOfReverseGeoCoord
     */
    public $Coords;
    /**
     * The IncludePostedSpeedLimit
     * Meta informations extracted from the WSDL
     * - minOccurs : 0
     * @var boolean
     */
    public $IncludePostedSpeedLimit;
    /**
     * The MatchNamedRoadsOnly
     * Meta informations extracted from the WSDL
     * - minOccurs : 0
     * - nillable : true
     * @var boolean
     */
    public $MatchNamedRoadsOnly;
    /**
     * The MaxCleanupMiles
     * Meta informations extracted from the WSDL
     * - minOccurs : 0
     * - nillable : true
     * @var double
     */
    public $MaxCleanupMiles;
    /**
     * Constructor method for ReverseGeocodeRequestBody
     * @see parent::__construct()
     * @param PCMWSStructArrayOfReverseGeoCoord $_coords
     * @param boolean $_includePostedSpeedLimit
     * @param boolean $_matchNamedRoadsOnly
     * @param double $_maxCleanupMiles
     * @return PCMWSStructReverseGeocodeRequestBody
     */
    public function __construct($_coords = NULL,$_includePostedSpeedLimit = NULL,$_matchNamedRoadsOnly = NULL,$_maxCleanupMiles = NULL)
    {
        parent::__construct(array('Coords'=>($_coords instanceof PCMWSStructArrayOfReverseGeoCoord)?$_coords:new PCMWSStructArrayOfReverseGeoCoord($_coords),'IncludePostedSpeedLimit'=>$_includePostedSpeedLimit,'MatchNamedRoadsOnly'=>$_matchNamedRoadsOnly,'MaxCleanupMiles'=>$_maxCleanupMiles),false);
    }
    /**
     * Get Coords value
     * @return PCMWSStructArrayOfReverseGeoCoord|null
     */
    public function getCoords()
    {
        return $this->Coords;
    }
    /**
     * Set Coords value
     * @param PCMWSStructArrayOfReverseGeoCoord $_coords the Coords
     * @return PCMWSStructArrayOfReverseGeoCoord
     */
    public function setCoords($_coords)
    {
        return ($this->Coords = $_coords);
    }
    /**
     * Get IncludePostedSpeedLimit value
     * @return boolean|null
     */
    public function getIncludePostedSpeedLimit()
    {
        return $this->IncludePostedSpeedLimit;
    }
    /**
     * Set IncludePostedSpeedLimit value
     * @param boolean $_includePostedSpeedLimit the IncludePostedSpeedLimit
     * @return boolean
     */
    public function setIncludePostedSpeedLimit($_includePostedSpeedLimit)
    {
        return ($this->IncludePostedSpeedLimit = $_includePostedSpeedLimit);
    }
    /**
     * Get MatchNamedRoadsOnly value
     * @return boolean|null
     */
    public function getMatchNamedRoadsOnly()
    {
        return $this->MatchNamedRoadsOnly;
    }
    /**
     * Set MatchNamedRoadsOnly value
     * @param boolean $_matchNamedRoadsOnly the MatchNamedRoadsOnly
     * @return boolean
     */
    public function setMatchNamedRoadsOnly($_matchNamedRoadsOnly)
    {
        return ($this->MatchNamedRoadsOnly = $_matchNamedRoadsOnly);
    }
    /**
     * Get MaxCleanupMiles value
     * @return double|null
     */
    public function getMaxCleanupMiles()
    {
        return $this->MaxCleanupMiles;
    }
    /**
     * Set MaxCleanupMiles value
     * @param double $_maxCleanupMiles the MaxCleanupMiles
     * @return double
     */
    public function setMaxCleanupMiles($_maxCleanupMiles)
    {
        return ($this->MaxCleanupMiles = $_maxCleanupMiles);
    }
    /**
     * Method called when an object has been exported with var_export() functions
     * It allows to return an object instantiated with the values
     * @see PCMWSWsdlClass::__set_state()
     * @uses PCMWSWsdlClass::__set_state()
     * @param array $_array the exported values
     * @return PCMWSStructReverseGeocodeRequestBody
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
