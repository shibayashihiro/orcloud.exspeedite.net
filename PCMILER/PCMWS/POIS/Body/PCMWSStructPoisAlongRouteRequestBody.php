<?php
/**
 * File for class PCMWSStructPoisAlongRouteRequestBody
 * @package PCMWS
 * @subpackage Structs
 * @author WsdlToPhp Team <contact@wsdltophp.com>
 * @version 20150429-01
 * @date 2016-08-25
 */
/**
 * This class stands for PCMWSStructPoisAlongRouteRequestBody originally named PoisAlongRouteRequestBody
 * Meta informations extracted from the WSDL
 * - from schema : {@link http://pcmiler.alk.com/APIs/SOAP/v1.0/Service.svc?xsd=xsd0}
 * @package PCMWS
 * @subpackage Structs
 * @author WsdlToPhp Team <contact@wsdltophp.com>
 * @version 20150429-01
 * @date 2016-08-25
 */
class PCMWSStructPoisAlongRouteRequestBody extends PCMWSWsdlClass
{
    /**
     * The PoiRoute
     * Meta informations extracted from the WSDL
     * - minOccurs : 0
     * - nillable : true
     * @var PCMWSStructExtendedRoute
     */
    public $PoiRoute;
    /**
     * The RouteLegIndex
     * Meta informations extracted from the WSDL
     * - minOccurs : 0
     * @var int
     */
    public $RouteLegIndex;
    /**
     * The SearchType
     * Meta informations extracted from the WSDL
     * - minOccurs : 0
     * @var PCMWSEnumPOISearchType
     */
    public $SearchType;
    /**
     * The GenericPOICategories
     * Meta informations extracted from the WSDL
     * - minOccurs : 0
     * - nillable : true
     * @var PCMWSStructArrayOfPOIGenericType
     */
    public $GenericPOICategories;
    /**
     * The HoSPOICategories
     * Meta informations extracted from the WSDL
     * - minOccurs : 0
     * - nillable : true
     * @var PCMWSStructArrayOfPOIHosType
     */
    public $HoSPOICategories;
    /**
     * The SearchWindowUnits
     * Meta informations extracted from the WSDL
     * - minOccurs : 0
     * @var PCMWSEnumPoiSearchWindowUnits
     */
    public $SearchWindowUnits;
    /**
     * The SearchWindowStart
     * Meta informations extracted from the WSDL
     * - minOccurs : 0
     * - nillable : true
     * @var double
     */
    public $SearchWindowStart;
    /**
     * The SearchWindowEnd
     * Meta informations extracted from the WSDL
     * - minOccurs : 0
     * - nillable : true
     * @var double
     */
    public $SearchWindowEnd;
    /**
     * The AirDistanceThreshold
     * Meta informations extracted from the WSDL
     * - minOccurs : 0
     * - nillable : true
     * @var double
     */
    public $AirDistanceThreshold;
    /**
     * Constructor method for PoisAlongRouteRequestBody
     * @see parent::__construct()
     * @param PCMWSStructExtendedRoute $_poiRoute
     * @param int $_routeLegIndex
     * @param PCMWSEnumPOISearchType $_searchType
     * @param PCMWSStructArrayOfPOIGenericType $_genericPOICategories
     * @param PCMWSStructArrayOfPOIHosType $_hoSPOICategories
     * @param PCMWSEnumPoiSearchWindowUnits $_searchWindowUnits
     * @param double $_searchWindowStart
     * @param double $_searchWindowEnd
     * @param double $_airDistanceThreshold
     * @return PCMWSStructPoisAlongRouteRequestBody
     */
    public function __construct($_poiRoute = NULL,$_routeLegIndex = NULL,$_searchType = NULL,$_genericPOICategories = NULL,$_hoSPOICategories = NULL,$_searchWindowUnits = NULL,$_searchWindowStart = NULL,$_searchWindowEnd = NULL,$_airDistanceThreshold = NULL)
    {
        parent::__construct(array('PoiRoute'=>$_poiRoute,'RouteLegIndex'=>$_routeLegIndex,'SearchType'=>$_searchType,'GenericPOICategories'=>($_genericPOICategories instanceof PCMWSStructArrayOfPOIGenericType)?$_genericPOICategories:new PCMWSStructArrayOfPOIGenericType($_genericPOICategories),'HoSPOICategories'=>($_hoSPOICategories instanceof PCMWSStructArrayOfPOIHosType)?$_hoSPOICategories:new PCMWSStructArrayOfPOIHosType($_hoSPOICategories),'SearchWindowUnits'=>$_searchWindowUnits,'SearchWindowStart'=>$_searchWindowStart,'SearchWindowEnd'=>$_searchWindowEnd,'AirDistanceThreshold'=>$_airDistanceThreshold),false);
    }
    /**
     * Get PoiRoute value
     * @return PCMWSStructExtendedRoute|null
     */
    public function getPoiRoute()
    {
        return $this->PoiRoute;
    }
    /**
     * Set PoiRoute value
     * @param PCMWSStructExtendedRoute $_poiRoute the PoiRoute
     * @return PCMWSStructExtendedRoute
     */
    public function setPoiRoute($_poiRoute)
    {
        return ($this->PoiRoute = $_poiRoute);
    }
    /**
     * Get RouteLegIndex value
     * @return int|null
     */
    public function getRouteLegIndex()
    {
        return $this->RouteLegIndex;
    }
    /**
     * Set RouteLegIndex value
     * @param int $_routeLegIndex the RouteLegIndex
     * @return int
     */
    public function setRouteLegIndex($_routeLegIndex)
    {
        return ($this->RouteLegIndex = $_routeLegIndex);
    }
    /**
     * Get SearchType value
     * @return PCMWSEnumPOISearchType|null
     */
    public function getSearchType()
    {
        return $this->SearchType;
    }
    /**
     * Set SearchType value
     * @uses PCMWSEnumPOISearchType::valueIsValid()
     * @param PCMWSEnumPOISearchType $_searchType the SearchType
     * @return PCMWSEnumPOISearchType
     */
    public function setSearchType($_searchType)
    {
        if(!PCMWSEnumPOISearchType::valueIsValid($_searchType))
        {
            return false;
        }
        return ($this->SearchType = $_searchType);
    }
    /**
     * Get GenericPOICategories value
     * @return PCMWSStructArrayOfPOIGenericType|null
     */
    public function getGenericPOICategories()
    {
        return $this->GenericPOICategories;
    }
    /**
     * Set GenericPOICategories value
     * @param PCMWSStructArrayOfPOIGenericType $_genericPOICategories the GenericPOICategories
     * @return PCMWSStructArrayOfPOIGenericType
     */
    public function setGenericPOICategories($_genericPOICategories)
    {
        return ($this->GenericPOICategories = $_genericPOICategories);
    }
    /**
     * Get HoSPOICategories value
     * @return PCMWSStructArrayOfPOIHosType|null
     */
    public function getHoSPOICategories()
    {
        return $this->HoSPOICategories;
    }
    /**
     * Set HoSPOICategories value
     * @param PCMWSStructArrayOfPOIHosType $_hoSPOICategories the HoSPOICategories
     * @return PCMWSStructArrayOfPOIHosType
     */
    public function setHoSPOICategories($_hoSPOICategories)
    {
        return ($this->HoSPOICategories = $_hoSPOICategories);
    }
    /**
     * Get SearchWindowUnits value
     * @return PCMWSEnumPoiSearchWindowUnits|null
     */
    public function getSearchWindowUnits()
    {
        return $this->SearchWindowUnits;
    }
    /**
     * Set SearchWindowUnits value
     * @uses PCMWSEnumPoiSearchWindowUnits::valueIsValid()
     * @param PCMWSEnumPoiSearchWindowUnits $_searchWindowUnits the SearchWindowUnits
     * @return PCMWSEnumPoiSearchWindowUnits
     */
    public function setSearchWindowUnits($_searchWindowUnits)
    {
        if(!PCMWSEnumPoiSearchWindowUnits::valueIsValid($_searchWindowUnits))
        {
            return false;
        }
        return ($this->SearchWindowUnits = $_searchWindowUnits);
    }
    /**
     * Get SearchWindowStart value
     * @return double|null
     */
    public function getSearchWindowStart()
    {
        return $this->SearchWindowStart;
    }
    /**
     * Set SearchWindowStart value
     * @param double $_searchWindowStart the SearchWindowStart
     * @return double
     */
    public function setSearchWindowStart($_searchWindowStart)
    {
        return ($this->SearchWindowStart = $_searchWindowStart);
    }
    /**
     * Get SearchWindowEnd value
     * @return double|null
     */
    public function getSearchWindowEnd()
    {
        return $this->SearchWindowEnd;
    }
    /**
     * Set SearchWindowEnd value
     * @param double $_searchWindowEnd the SearchWindowEnd
     * @return double
     */
    public function setSearchWindowEnd($_searchWindowEnd)
    {
        return ($this->SearchWindowEnd = $_searchWindowEnd);
    }
    /**
     * Get AirDistanceThreshold value
     * @return double|null
     */
    public function getAirDistanceThreshold()
    {
        return $this->AirDistanceThreshold;
    }
    /**
     * Set AirDistanceThreshold value
     * @param double $_airDistanceThreshold the AirDistanceThreshold
     * @return double
     */
    public function setAirDistanceThreshold($_airDistanceThreshold)
    {
        return ($this->AirDistanceThreshold = $_airDistanceThreshold);
    }
    /**
     * Method called when an object has been exported with var_export() functions
     * It allows to return an object instantiated with the values
     * @see PCMWSWsdlClass::__set_state()
     * @uses PCMWSWsdlClass::__set_state()
     * @param array $_array the exported values
     * @return PCMWSStructPoisAlongRouteRequestBody
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
