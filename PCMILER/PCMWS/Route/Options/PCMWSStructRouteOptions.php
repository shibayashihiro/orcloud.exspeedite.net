<?php
/**
 * File for class PCMWSStructRouteOptions
 * @package PCMWS
 * @subpackage Structs
 * @author WsdlToPhp Team <contact@wsdltophp.com>
 * @version 20150429-01
 * @date 2016-08-25
 */
/**
 * This class stands for PCMWSStructRouteOptions originally named RouteOptions
 * Meta informations extracted from the WSDL
 * - from schema : {@link http://pcmiler.alk.com/APIs/SOAP/v1.0/Service.svc?xsd=xsd0}
 * @package PCMWS
 * @subpackage Structs
 * @author WsdlToPhp Team <contact@wsdltophp.com>
 * @version 20150429-01
 * @date 2016-08-25
 */
class PCMWSStructRouteOptions extends PCMWSWsdlClass
{
    /**
     * The AFSetIDs
     * Meta informations extracted from the WSDL
     * - minOccurs : 0
     * - nillable : true
     * @var PCMWSStructArrayOfint
     */
    public $AFSetIDs;
    /**
     * The BordersOpen
     * Meta informations extracted from the WSDL
     * - minOccurs : 0
     * - nillable : true
     * @var boolean
     */
    public $BordersOpen;
    /**
     * The ClassOverrides
     * Meta informations extracted from the WSDL
     * - minOccurs : 0
     * @var PCMWSEnumClassOverrideType
     */
    public $ClassOverrides;
    /**
     * The DistanceUnits
     * Meta informations extracted from the WSDL
     * - minOccurs : 0
     * @var PCMWSEnumDistanceUnits
     */
    public $DistanceUnits;
    /**
     * The ElevLimit
     * Meta informations extracted from the WSDL
     * - minOccurs : 0
     * - nillable : true
     * @var unsignedInt
     */
    public $ElevLimit;
    /**
     * The FerryDiscourage
     * Meta informations extracted from the WSDL
     * - minOccurs : 0
     * @var boolean
     */
    public $FerryDiscourage;
    /**
     * The FuelRoute
     * Meta informations extracted from the WSDL
     * - minOccurs : 0
     * @var boolean
     */
    public $FuelRoute;
    /**
     * The GovernorSpeedLimit
     * Meta informations extracted from the WSDL
     * - minOccurs : 0
     * - nillable : true
     * @var int
     */
    public $GovernorSpeedLimit;
    /**
     * The HazMatType
     * Meta informations extracted from the WSDL
     * - minOccurs : 0
     * @var PCMWSEnumHazMatType
     */
    public $HazMatType;
    /**
     * The HighwayOnly
     * Meta informations extracted from the WSDL
     * - minOccurs : 0
     * - nillable : true
     * @var boolean
     */
    public $HighwayOnly;
    /**
     * The HoSOptions
     * Meta informations extracted from the WSDL
     * - minOccurs : 0
     * - nillable : true
     * @var PCMWSStructHoursOfServiceOptions
     */
    public $HoSOptions;
    /**
     * The HubRouting
     * Meta informations extracted from the WSDL
     * - minOccurs : 0
     * @var boolean
     */
    public $HubRouting;
    /**
     * The OverrideRestrict
     * Meta informations extracted from the WSDL
     * - minOccurs : 0
     * @var boolean
     */
    public $OverrideRestrict;
    /**
     * The RouteOptimization
     * Meta informations extracted from the WSDL
     * - minOccurs : 0
     * @var PCMWSEnumRouteOptimizeType
     */
    public $RouteOptimization;
    /**
     * The RoutingType
     * Meta informations extracted from the WSDL
     * - minOccurs : 0
     * @var PCMWSEnumRoutingType
     */
    public $RoutingType;
    /**
     * The SideOfStreetAdherence
     * Meta informations extracted from the WSDL
     * - minOccurs : 0
     * @var PCMWSEnumSideOfStreetAdherenceLevel
     */
    public $SideOfStreetAdherence;
    /**
     * The TollDiscourage
     * Meta informations extracted from the WSDL
     * - minOccurs : 0
     * @var boolean
     */
    public $TollDiscourage;
    /**
     * The TruckCfg
     * Meta informations extracted from the WSDL
     * - minOccurs : 0
     * - nillable : true
     * @var PCMWSStructTruckConfig
     */
    public $TruckCfg;
    /**
     * The UseAvoidsAndFavors
     * Meta informations extracted from the WSDL
     * - minOccurs : 0
     * @var boolean
     */
    public $UseAvoidsAndFavors;
    /**
     * The VehicleType
     * Meta informations extracted from the WSDL
     * - minOccurs : 0
     * @var PCMWSEnumVehicleType
     */
    public $VehicleType;
    /**
     * Constructor method for RouteOptions
     * @see parent::__construct()
     * @param PCMWSStructArrayOfint $_aFSetIDs
     * @param boolean $_bordersOpen
     * @param PCMWSEnumClassOverrideType $_classOverrides
     * @param PCMWSEnumDistanceUnits $_distanceUnits
     * @param unsignedInt $_elevLimit
     * @param boolean $_ferryDiscourage
     * @param boolean $_fuelRoute
     * @param int $_governorSpeedLimit
     * @param PCMWSEnumHazMatType $_hazMatType
     * @param boolean $_highwayOnly
     * @param PCMWSStructHoursOfServiceOptions $_hoSOptions
     * @param boolean $_hubRouting
     * @param boolean $_overrideRestrict
     * @param PCMWSEnumRouteOptimizeType $_routeOptimization
     * @param PCMWSEnumRoutingType $_routingType
     * @param PCMWSEnumSideOfStreetAdherenceLevel $_sideOfStreetAdherence
     * @param boolean $_tollDiscourage
     * @param PCMWSStructTruckConfig $_truckCfg
     * @param boolean $_useAvoidsAndFavors
     * @param PCMWSEnumVehicleType $_vehicleType
     * @return PCMWSStructRouteOptions
     */
    public function __construct($_aFSetIDs = NULL,$_bordersOpen = NULL,$_classOverrides = NULL,$_distanceUnits = NULL,$_elevLimit = NULL,$_ferryDiscourage = NULL,$_fuelRoute = NULL,$_governorSpeedLimit = NULL,$_hazMatType = NULL,$_highwayOnly = NULL,$_hoSOptions = NULL,$_hubRouting = NULL,$_overrideRestrict = NULL,$_routeOptimization = NULL,$_routingType = NULL,$_sideOfStreetAdherence = NULL,$_tollDiscourage = NULL,$_truckCfg = NULL,$_useAvoidsAndFavors = NULL,$_vehicleType = NULL)
    {
        parent::__construct(array('AFSetIDs'=>($_aFSetIDs instanceof PCMWSStructArrayOfint)?$_aFSetIDs:new PCMWSStructArrayOfint($_aFSetIDs),'BordersOpen'=>$_bordersOpen,'ClassOverrides'=>$_classOverrides,'DistanceUnits'=>$_distanceUnits,'ElevLimit'=>$_elevLimit,'FerryDiscourage'=>$_ferryDiscourage,'FuelRoute'=>$_fuelRoute,'GovernorSpeedLimit'=>$_governorSpeedLimit,'HazMatType'=>$_hazMatType,'HighwayOnly'=>$_highwayOnly,'HoSOptions'=>$_hoSOptions,'HubRouting'=>$_hubRouting,'OverrideRestrict'=>$_overrideRestrict,'RouteOptimization'=>$_routeOptimization,'RoutingType'=>$_routingType,'SideOfStreetAdherence'=>$_sideOfStreetAdherence,'TollDiscourage'=>$_tollDiscourage,'TruckCfg'=>$_truckCfg,'UseAvoidsAndFavors'=>$_useAvoidsAndFavors,'VehicleType'=>$_vehicleType),false);
    }
    /**
     * Get AFSetIDs value
     * @return PCMWSStructArrayOfint|null
     */
    public function getAFSetIDs()
    {
        return $this->AFSetIDs;
    }
    /**
     * Set AFSetIDs value
     * @param PCMWSStructArrayOfint $_aFSetIDs the AFSetIDs
     * @return PCMWSStructArrayOfint
     */
    public function setAFSetIDs($_aFSetIDs)
    {
        return ($this->AFSetIDs = $_aFSetIDs);
    }
    /**
     * Get BordersOpen value
     * @return boolean|null
     */
    public function getBordersOpen()
    {
        return $this->BordersOpen;
    }
    /**
     * Set BordersOpen value
     * @param boolean $_bordersOpen the BordersOpen
     * @return boolean
     */
    public function setBordersOpen($_bordersOpen)
    {
        return ($this->BordersOpen = $_bordersOpen);
    }
    /**
     * Get ClassOverrides value
     * @return PCMWSEnumClassOverrideType|null
     */
    public function getClassOverrides()
    {
        return $this->ClassOverrides;
    }
    /**
     * Set ClassOverrides value
     * @uses PCMWSEnumClassOverrideType::valueIsValid()
     * @param PCMWSEnumClassOverrideType $_classOverrides the ClassOverrides
     * @return PCMWSEnumClassOverrideType
     */
    public function setClassOverrides($_classOverrides)
    {
        if(!PCMWSEnumClassOverrideType::valueIsValid($_classOverrides))
        {
            return false;
        }
        return ($this->ClassOverrides = $_classOverrides);
    }
    /**
     * Get DistanceUnits value
     * @return PCMWSEnumDistanceUnits|null
     */
    public function getDistanceUnits()
    {
        return $this->DistanceUnits;
    }
    /**
     * Set DistanceUnits value
     * @uses PCMWSEnumDistanceUnits::valueIsValid()
     * @param PCMWSEnumDistanceUnits $_distanceUnits the DistanceUnits
     * @return PCMWSEnumDistanceUnits
     */
    public function setDistanceUnits($_distanceUnits)
    {
        if(!PCMWSEnumDistanceUnits::valueIsValid($_distanceUnits))
        {
            return false;
        }
        return ($this->DistanceUnits = $_distanceUnits);
    }
    /**
     * Get ElevLimit value
     * @return unsignedInt|null
     */
    public function getElevLimit()
    {
        return $this->ElevLimit;
    }
    /**
     * Set ElevLimit value
     * @param unsignedInt $_elevLimit the ElevLimit
     * @return unsignedInt
     */
    public function setElevLimit($_elevLimit)
    {
        return ($this->ElevLimit = $_elevLimit);
    }
    /**
     * Get FerryDiscourage value
     * @return boolean|null
     */
    public function getFerryDiscourage()
    {
        return $this->FerryDiscourage;
    }
    /**
     * Set FerryDiscourage value
     * @param boolean $_ferryDiscourage the FerryDiscourage
     * @return boolean
     */
    public function setFerryDiscourage($_ferryDiscourage)
    {
        return ($this->FerryDiscourage = $_ferryDiscourage);
    }
    /**
     * Get FuelRoute value
     * @return boolean|null
     */
    public function getFuelRoute()
    {
        return $this->FuelRoute;
    }
    /**
     * Set FuelRoute value
     * @param boolean $_fuelRoute the FuelRoute
     * @return boolean
     */
    public function setFuelRoute($_fuelRoute)
    {
        return ($this->FuelRoute = $_fuelRoute);
    }
    /**
     * Get GovernorSpeedLimit value
     * @return int|null
     */
    public function getGovernorSpeedLimit()
    {
        return $this->GovernorSpeedLimit;
    }
    /**
     * Set GovernorSpeedLimit value
     * @param int $_governorSpeedLimit the GovernorSpeedLimit
     * @return int
     */
    public function setGovernorSpeedLimit($_governorSpeedLimit)
    {
        return ($this->GovernorSpeedLimit = $_governorSpeedLimit);
    }
    /**
     * Get HazMatType value
     * @return PCMWSEnumHazMatType|null
     */
    public function getHazMatType()
    {
        return $this->HazMatType;
    }
    /**
     * Set HazMatType value
     * @uses PCMWSEnumHazMatType::valueIsValid()
     * @param PCMWSEnumHazMatType $_hazMatType the HazMatType
     * @return PCMWSEnumHazMatType
     */
    public function setHazMatType($_hazMatType)
    {
        if(!PCMWSEnumHazMatType::valueIsValid($_hazMatType))
        {
            return false;
        }
        return ($this->HazMatType = $_hazMatType);
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
     * Get HoSOptions value
     * @return PCMWSStructHoursOfServiceOptions|null
     */
    public function getHoSOptions()
    {
        return $this->HoSOptions;
    }
    /**
     * Set HoSOptions value
     * @param PCMWSStructHoursOfServiceOptions $_hoSOptions the HoSOptions
     * @return PCMWSStructHoursOfServiceOptions
     */
    public function setHoSOptions($_hoSOptions)
    {
        return ($this->HoSOptions = $_hoSOptions);
    }
    /**
     * Get HubRouting value
     * @return boolean|null
     */
    public function getHubRouting()
    {
        return $this->HubRouting;
    }
    /**
     * Set HubRouting value
     * @param boolean $_hubRouting the HubRouting
     * @return boolean
     */
    public function setHubRouting($_hubRouting)
    {
        return ($this->HubRouting = $_hubRouting);
    }
    /**
     * Get OverrideRestrict value
     * @return boolean|null
     */
    public function getOverrideRestrict()
    {
        return $this->OverrideRestrict;
    }
    /**
     * Set OverrideRestrict value
     * @param boolean $_overrideRestrict the OverrideRestrict
     * @return boolean
     */
    public function setOverrideRestrict($_overrideRestrict)
    {
        return ($this->OverrideRestrict = $_overrideRestrict);
    }
    /**
     * Get RouteOptimization value
     * @return PCMWSEnumRouteOptimizeType|null
     */
    public function getRouteOptimization()
    {
        return $this->RouteOptimization;
    }
    /**
     * Set RouteOptimization value
     * @uses PCMWSEnumRouteOptimizeType::valueIsValid()
     * @param PCMWSEnumRouteOptimizeType $_routeOptimization the RouteOptimization
     * @return PCMWSEnumRouteOptimizeType
     */
    public function setRouteOptimization($_routeOptimization)
    {
        if(!PCMWSEnumRouteOptimizeType::valueIsValid($_routeOptimization))
        {
            return false;
        }
        return ($this->RouteOptimization = $_routeOptimization);
    }
    /**
     * Get RoutingType value
     * @return PCMWSEnumRoutingType|null
     */
    public function getRoutingType()
    {
        return $this->RoutingType;
    }
    /**
     * Set RoutingType value
     * @uses PCMWSEnumRoutingType::valueIsValid()
     * @param PCMWSEnumRoutingType $_routingType the RoutingType
     * @return PCMWSEnumRoutingType
     */
    public function setRoutingType($_routingType)
    {
        if(!PCMWSEnumRoutingType::valueIsValid($_routingType))
        {
            return false;
        }
        return ($this->RoutingType = $_routingType);
    }
    /**
     * Get SideOfStreetAdherence value
     * @return PCMWSEnumSideOfStreetAdherenceLevel|null
     */
    public function getSideOfStreetAdherence()
    {
        return $this->SideOfStreetAdherence;
    }
    /**
     * Set SideOfStreetAdherence value
     * @uses PCMWSEnumSideOfStreetAdherenceLevel::valueIsValid()
     * @param PCMWSEnumSideOfStreetAdherenceLevel $_sideOfStreetAdherence the SideOfStreetAdherence
     * @return PCMWSEnumSideOfStreetAdherenceLevel
     */
    public function setSideOfStreetAdherence($_sideOfStreetAdherence)
    {
        if(!PCMWSEnumSideOfStreetAdherenceLevel::valueIsValid($_sideOfStreetAdherence))
        {
            return false;
        }
        return ($this->SideOfStreetAdherence = $_sideOfStreetAdherence);
    }
    /**
     * Get TollDiscourage value
     * @return boolean|null
     */
    public function getTollDiscourage()
    {
        return $this->TollDiscourage;
    }
    /**
     * Set TollDiscourage value
     * @param boolean $_tollDiscourage the TollDiscourage
     * @return boolean
     */
    public function setTollDiscourage($_tollDiscourage)
    {
        return ($this->TollDiscourage = $_tollDiscourage);
    }
    /**
     * Get TruckCfg value
     * @return PCMWSStructTruckConfig|null
     */
    public function getTruckCfg()
    {
        return $this->TruckCfg;
    }
    /**
     * Set TruckCfg value
     * @param PCMWSStructTruckConfig $_truckCfg the TruckCfg
     * @return PCMWSStructTruckConfig
     */
    public function setTruckCfg($_truckCfg)
    {
        return ($this->TruckCfg = $_truckCfg);
    }
    /**
     * Get UseAvoidsAndFavors value
     * @return boolean|null
     */
    public function getUseAvoidsAndFavors()
    {
        return $this->UseAvoidsAndFavors;
    }
    /**
     * Set UseAvoidsAndFavors value
     * @param boolean $_useAvoidsAndFavors the UseAvoidsAndFavors
     * @return boolean
     */
    public function setUseAvoidsAndFavors($_useAvoidsAndFavors)
    {
        return ($this->UseAvoidsAndFavors = $_useAvoidsAndFavors);
    }
    /**
     * Get VehicleType value
     * @return PCMWSEnumVehicleType|null
     */
    public function getVehicleType()
    {
        return $this->VehicleType;
    }
    /**
     * Set VehicleType value
     * @uses PCMWSEnumVehicleType::valueIsValid()
     * @param PCMWSEnumVehicleType $_vehicleType the VehicleType
     * @return PCMWSEnumVehicleType
     */
    public function setVehicleType($_vehicleType)
    {
        if(!PCMWSEnumVehicleType::valueIsValid($_vehicleType))
        {
            return false;
        }
        return ($this->VehicleType = $_vehicleType);
    }
    /**
     * Method called when an object has been exported with var_export() functions
     * It allows to return an object instantiated with the values
     * @see PCMWSWsdlClass::__set_state()
     * @uses PCMWSWsdlClass::__set_state()
     * @param array $_array the exported values
     * @return PCMWSStructRouteOptions
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
