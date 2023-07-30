<?php
/**
 * File for class PCMWSStructReduceTripRequestBody
 * @package PCMWS
 * @subpackage Structs
 * @author WsdlToPhp Team <contact@wsdltophp.com>
 * @version 20150429-01
 * @date 2016-08-25
 */
/**
 * This class stands for PCMWSStructReduceTripRequestBody originally named ReduceTripRequestBody
 * Meta informations extracted from the WSDL
 * - from schema : {@link http://pcmiler.alk.com/APIs/SOAP/v1.0/Service.svc?xsd=xsd0}
 * @package PCMWS
 * @subpackage Structs
 * @author WsdlToPhp Team <contact@wsdltophp.com>
 * @version 20150429-01
 * @date 2016-08-25
 */
class PCMWSStructReduceTripRequestBody extends PCMWSWsdlClass
{
    /**
     * The ExtendedOptions
     * Meta informations extracted from the WSDL
     * - minOccurs : 0
     * - nillable : true
     * @var PCMWSStructSharedOptions
     */
    public $ExtendedOptions;
    /**
     * The HighwayOnly
     * Meta informations extracted from the WSDL
     * - minOccurs : 0
     * @var boolean
     */
    public $HighwayOnly;
    /**
     * The OffRouteMiles
     * Meta informations extracted from the WSDL
     * - minOccurs : 0
     * @var double
     */
    public $OffRouteMiles;
    /**
     * The Region
     * Meta informations extracted from the WSDL
     * - minOccurs : 0
     * @var PCMWSEnumDataRegion
     */
    public $Region;
    /**
     * The ReportType
     * Meta informations extracted from the WSDL
     * - minOccurs : 0
     * - nillable : true
     * @var PCMWSStructReportType
     */
    public $ReportType;
    /**
     * The RoutePings
     * Meta informations extracted from the WSDL
     * - minOccurs : 0
     * - nillable : true
     * @var PCMWSStructArrayOfCoordinates
     */
    public $RoutePings;
    /**
     * The RoutingOptions
     * Meta informations extracted from the WSDL
     * - minOccurs : 0
     * - nillable : true
     * @var PCMWSStructRouteOptions
     */
    public $RoutingOptions;
    /**
     * Constructor method for ReduceTripRequestBody
     * @see parent::__construct()
     * @param PCMWSStructSharedOptions $_extendedOptions
     * @param boolean $_highwayOnly
     * @param double $_offRouteMiles
     * @param PCMWSEnumDataRegion $_region
     * @param PCMWSStructReportType $_reportType
     * @param PCMWSStructArrayOfCoordinates $_routePings
     * @param PCMWSStructRouteOptions $_routingOptions
     * @return PCMWSStructReduceTripRequestBody
     */
    public function __construct($_extendedOptions = NULL,$_highwayOnly = NULL,$_offRouteMiles = NULL,$_region = NULL,$_reportType = NULL,$_routePings = NULL,$_routingOptions = NULL)
    {
        parent::__construct(array('ExtendedOptions'=>$_extendedOptions,'HighwayOnly'=>$_highwayOnly,'OffRouteMiles'=>$_offRouteMiles,'Region'=>$_region,'ReportType'=>$_reportType,'RoutePings'=>($_routePings instanceof PCMWSStructArrayOfCoordinates)?$_routePings:new PCMWSStructArrayOfCoordinates($_routePings),'RoutingOptions'=>$_routingOptions),false);
    }
    /**
     * Get ExtendedOptions value
     * @return PCMWSStructSharedOptions|null
     */
    public function getExtendedOptions()
    {
        return $this->ExtendedOptions;
    }
    /**
     * Set ExtendedOptions value
     * @param PCMWSStructSharedOptions $_extendedOptions the ExtendedOptions
     * @return PCMWSStructSharedOptions
     */
    public function setExtendedOptions($_extendedOptions)
    {
        return ($this->ExtendedOptions = $_extendedOptions);
    }
    /**
     * Get HighwayOnly value
     * @return boolean|null
     */
    public function getHighwayOnly()
    {
        return $this->HighwayOnly;
    }
    /**
     * Set HighwayOnly value
     * @param boolean $_highwayOnly the HighwayOnly
     * @return boolean
     */
    public function setHighwayOnly($_highwayOnly)
    {
        return ($this->HighwayOnly = $_highwayOnly);
    }
    /**
     * Get OffRouteMiles value
     * @return double|null
     */
    public function getOffRouteMiles()
    {
        return $this->OffRouteMiles;
    }
    /**
     * Set OffRouteMiles value
     * @param double $_offRouteMiles the OffRouteMiles
     * @return double
     */
    public function setOffRouteMiles($_offRouteMiles)
    {
        return ($this->OffRouteMiles = $_offRouteMiles);
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
     * Get ReportType value
     * @return PCMWSStructReportType|null
     */
    public function getReportType()
    {
        return $this->ReportType;
    }
    /**
     * Set ReportType value
     * @param PCMWSStructReportType $_reportType the ReportType
     * @return PCMWSStructReportType
     */
    public function setReportType($_reportType)
    {
        return ($this->ReportType = $_reportType);
    }
    /**
     * Get RoutePings value
     * @return PCMWSStructArrayOfCoordinates|null
     */
    public function getRoutePings()
    {
        return $this->RoutePings;
    }
    /**
     * Set RoutePings value
     * @param PCMWSStructArrayOfCoordinates $_routePings the RoutePings
     * @return PCMWSStructArrayOfCoordinates
     */
    public function setRoutePings($_routePings)
    {
        return ($this->RoutePings = $_routePings);
    }
    /**
     * Get RoutingOptions value
     * @return PCMWSStructRouteOptions|null
     */
    public function getRoutingOptions()
    {
        return $this->RoutingOptions;
    }
    /**
     * Set RoutingOptions value
     * @param PCMWSStructRouteOptions $_routingOptions the RoutingOptions
     * @return PCMWSStructRouteOptions
     */
    public function setRoutingOptions($_routingOptions)
    {
        return ($this->RoutingOptions = $_routingOptions);
    }
    /**
     * Method called when an object has been exported with var_export() functions
     * It allows to return an object instantiated with the values
     * @see PCMWSWsdlClass::__set_state()
     * @uses PCMWSWsdlClass::__set_state()
     * @param array $_array the exported values
     * @return PCMWSStructReduceTripRequestBody
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
