<?php
/**
 * File for class PCMWSStructETAOutOfRouteRequestBody
 * @package PCMWS
 * @subpackage Structs
 * @author WsdlToPhp Team <contact@wsdltophp.com>
 * @version 20150429-01
 * @date 2016-08-25
 */
/**
 * This class stands for PCMWSStructETAOutOfRouteRequestBody originally named ETAOutOfRouteRequestBody
 * Meta informations extracted from the WSDL
 * - from schema : {@link http://pcmiler.alk.com/APIs/SOAP/v1.0/Service.svc?xsd=xsd0}
 * @package PCMWS
 * @subpackage Structs
 * @author WsdlToPhp Team <contact@wsdltophp.com>
 * @version 20150429-01
 * @date 2016-08-25
 */
class PCMWSStructETAOutOfRouteRequestBody extends PCMWSWsdlClass
{
    /**
     * The RouteID
     * Meta informations extracted from the WSDL
     * - minOccurs : 0
     * - nillable : true
     * @var string
     */
    public $RouteID;
    /**
     * The Origin
     * Meta informations extracted from the WSDL
     * - minOccurs : 0
     * - nillable : true
     * @var PCMWSStructStopLocation
     */
    public $Origin;
    /**
     * The Destination
     * Meta informations extracted from the WSDL
     * - minOccurs : 0
     * - nillable : true
     * @var PCMWSStructStopLocation
     */
    public $Destination;
    /**
     * The CurrentLocations
     * Meta informations extracted from the WSDL
     * - minOccurs : 0
     * - nillable : true
     * @var PCMWSStructArrayOfStopLocation
     */
    public $CurrentLocations;
    /**
     * The RoutingOptions
     * Meta informations extracted from the WSDL
     * - minOccurs : 0
     * - nillable : true
     * @var PCMWSStructRouteOptions
     */
    public $RoutingOptions;
    /**
     * The ReportingOptions
     * Meta informations extracted from the WSDL
     * - minOccurs : 0
     * - nillable : true
     * @var PCMWSStructReportOptions
     */
    public $ReportingOptions;
    /**
     * Constructor method for ETAOutOfRouteRequestBody
     * @see parent::__construct()
     * @param string $_routeID
     * @param PCMWSStructStopLocation $_origin
     * @param PCMWSStructStopLocation $_destination
     * @param PCMWSStructArrayOfStopLocation $_currentLocations
     * @param PCMWSStructRouteOptions $_routingOptions
     * @param PCMWSStructReportOptions $_reportingOptions
     * @return PCMWSStructETAOutOfRouteRequestBody
     */
    public function __construct($_routeID = NULL,$_origin = NULL,$_destination = NULL,$_currentLocations = NULL,$_routingOptions = NULL,$_reportingOptions = NULL)
    {
        parent::__construct(array('RouteID'=>$_routeID,'Origin'=>$_origin,'Destination'=>$_destination,'CurrentLocations'=>($_currentLocations instanceof PCMWSStructArrayOfStopLocation)?$_currentLocations:new PCMWSStructArrayOfStopLocation($_currentLocations),'RoutingOptions'=>$_routingOptions,'ReportingOptions'=>$_reportingOptions),false);
    }
    /**
     * Get RouteID value
     * @return string|null
     */
    public function getRouteID()
    {
        return $this->RouteID;
    }
    /**
     * Set RouteID value
     * @param string $_routeID the RouteID
     * @return string
     */
    public function setRouteID($_routeID)
    {
        return ($this->RouteID = $_routeID);
    }
    /**
     * Get Origin value
     * @return PCMWSStructStopLocation|null
     */
    public function getOrigin()
    {
        return $this->Origin;
    }
    /**
     * Set Origin value
     * @param PCMWSStructStopLocation $_origin the Origin
     * @return PCMWSStructStopLocation
     */
    public function setOrigin($_origin)
    {
        return ($this->Origin = $_origin);
    }
    /**
     * Get Destination value
     * @return PCMWSStructStopLocation|null
     */
    public function getDestination()
    {
        return $this->Destination;
    }
    /**
     * Set Destination value
     * @param PCMWSStructStopLocation $_destination the Destination
     * @return PCMWSStructStopLocation
     */
    public function setDestination($_destination)
    {
        return ($this->Destination = $_destination);
    }
    /**
     * Get CurrentLocations value
     * @return PCMWSStructArrayOfStopLocation|null
     */
    public function getCurrentLocations()
    {
        return $this->CurrentLocations;
    }
    /**
     * Set CurrentLocations value
     * @param PCMWSStructArrayOfStopLocation $_currentLocations the CurrentLocations
     * @return PCMWSStructArrayOfStopLocation
     */
    public function setCurrentLocations($_currentLocations)
    {
        return ($this->CurrentLocations = $_currentLocations);
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
     * Get ReportingOptions value
     * @return PCMWSStructReportOptions|null
     */
    public function getReportingOptions()
    {
        return $this->ReportingOptions;
    }
    /**
     * Set ReportingOptions value
     * @param PCMWSStructReportOptions $_reportingOptions the ReportingOptions
     * @return PCMWSStructReportOptions
     */
    public function setReportingOptions($_reportingOptions)
    {
        return ($this->ReportingOptions = $_reportingOptions);
    }
    /**
     * Method called when an object has been exported with var_export() functions
     * It allows to return an object instantiated with the values
     * @see PCMWSWsdlClass::__set_state()
     * @uses PCMWSWsdlClass::__set_state()
     * @param array $_array the exported values
     * @return PCMWSStructETAOutOfRouteRequestBody
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
