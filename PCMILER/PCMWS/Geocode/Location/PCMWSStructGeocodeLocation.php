<?php
/**
 * File for class PCMWSStructGeocodeLocation
 * @package PCMWS
 * @subpackage Structs
 * @author WsdlToPhp Team <contact@wsdltophp.com>
 * @version 20150429-01
 * @date 2016-08-25
 */
/**
 * This class stands for PCMWSStructGeocodeLocation originally named GeocodeLocation
 * Meta informations extracted from the WSDL
 * - from schema : {@link http://pcmiler.alk.com/APIs/SOAP/v1.0/Service.svc?xsd=xsd0}
 * @package PCMWS
 * @subpackage Structs
 * @author WsdlToPhp Team <contact@wsdltophp.com>
 * @version 20150429-01
 * @date 2016-08-25
 */
class PCMWSStructGeocodeLocation extends PCMWSWsdlClass
{
    /**
     * The Address
     * Meta informations extracted from the WSDL
     * - minOccurs : 0
     * - nillable : true
     * @var PCMWSStructAddress
     */
    public $Address;
    /**
     * The Region
     * Meta informations extracted from the WSDL
     * - minOccurs : 0
     * @var PCMWSEnumDataRegion
     */
    public $Region;
    /**
     * The GeoList
     * Meta informations extracted from the WSDL
     * - minOccurs : 0
     * @var boolean
     */
    public $GeoList;
    /**
     * The MaxResults
     * Meta informations extracted from the WSDL
     * - minOccurs : 0
     * - nillable : true
     * @var int
     */
    public $MaxResults;
    /**
     * The CitySearchFilter
     * Meta informations extracted from the WSDL
     * - minOccurs : 0
     * @var PCMWSEnumGeocodeCitySearchFilter
     */
    public $CitySearchFilter;
    /**
     * Constructor method for GeocodeLocation
     * @see parent::__construct()
     * @param PCMWSStructAddress $_address
     * @param PCMWSEnumDataRegion $_region
     * @param boolean $_geoList
     * @param int $_maxResults
     * @param PCMWSEnumGeocodeCitySearchFilter $_citySearchFilter
     * @return PCMWSStructGeocodeLocation
     */
    public function __construct($_address = NULL,$_region = NULL,$_geoList = NULL,$_maxResults = NULL,$_citySearchFilter = NULL)
    {
        parent::__construct(array('Address'=>$_address,'Region'=>$_region,'GeoList'=>$_geoList,'MaxResults'=>$_maxResults,'CitySearchFilter'=>$_citySearchFilter),false);
    }
    /**
     * Get Address value
     * @return PCMWSStructAddress|null
     */
    public function getAddress()
    {
        return $this->Address;
    }
    /**
     * Set Address value
     * @param PCMWSStructAddress $_address the Address
     * @return PCMWSStructAddress
     */
    public function setAddress($_address)
    {
        return ($this->Address = $_address);
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
     * Get GeoList value
     * @return boolean|null
     */
    public function getGeoList()
    {
        return $this->GeoList;
    }
    /**
     * Set GeoList value
     * @param boolean $_geoList the GeoList
     * @return boolean
     */
    public function setGeoList($_geoList)
    {
        return ($this->GeoList = $_geoList);
    }
    /**
     * Get MaxResults value
     * @return int|null
     */
    public function getMaxResults()
    {
        return $this->MaxResults;
    }
    /**
     * Set MaxResults value
     * @param int $_maxResults the MaxResults
     * @return int
     */
    public function setMaxResults($_maxResults)
    {
        return ($this->MaxResults = $_maxResults);
    }
    /**
     * Get CitySearchFilter value
     * @return PCMWSEnumGeocodeCitySearchFilter|null
     */
    public function getCitySearchFilter()
    {
        return $this->CitySearchFilter;
    }
    /**
     * Set CitySearchFilter value
     * @uses PCMWSEnumGeocodeCitySearchFilter::valueIsValid()
     * @param PCMWSEnumGeocodeCitySearchFilter $_citySearchFilter the CitySearchFilter
     * @return PCMWSEnumGeocodeCitySearchFilter
     */
    public function setCitySearchFilter($_citySearchFilter)
    {
        if(!PCMWSEnumGeocodeCitySearchFilter::valueIsValid($_citySearchFilter))
        {
            return false;
        }
        return ($this->CitySearchFilter = $_citySearchFilter);
    }
    /**
     * Method called when an object has been exported with var_export() functions
     * It allows to return an object instantiated with the values
     * @see PCMWSWsdlClass::__set_state()
     * @uses PCMWSWsdlClass::__set_state()
     * @param array $_array the exported values
     * @return PCMWSStructGeocodeLocation
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
