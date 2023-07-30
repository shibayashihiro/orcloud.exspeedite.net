<?php
/**
 * File for class PCMWSStructRoute
 * @package PCMWS
 * @subpackage Structs
 * @author WsdlToPhp Team <contact@wsdltophp.com>
 * @version 20150429-01
 * @date 2016-08-25
 */
/**
 * This class stands for PCMWSStructRoute originally named Route
 * Meta informations extracted from the WSDL
 * - from schema : {@link http://pcmiler.alk.com/APIs/SOAP/v1.0/Service.svc?xsd=xsd0}
 * @package PCMWS
 * @subpackage Structs
 * @author WsdlToPhp Team <contact@wsdltophp.com>
 * @version 20150429-01
 * @date 2016-08-25
 */
class PCMWSStructRoute extends PCMWSWsdlClass
{
    /**
     * The RouteId
     * Meta informations extracted from the WSDL
     * - minOccurs : 0
     * - nillable : true
     * @var string
     */
    public $RouteId;
    /**
     * The Stops
     * Meta informations extracted from the WSDL
     * - minOccurs : 0
     * - nillable : true
     * @var PCMWSStructArrayOfStopLocation
     */
    public $Stops;
    /**
     * The Options
     * Meta informations extracted from the WSDL
     * - minOccurs : 0
     * - nillable : true
     * @var PCMWSStructRouteOptions
     */
    public $Options;
    /**
     * The FuelOptions
     * Meta informations extracted from the WSDL
     * - minOccurs : 0
     * - nillable : true
     * @var PCMWSStructFuelOptions
     */
    public $FuelOptions;
    /**
     * Constructor method for Route
     * @see parent::__construct()
     * @param string $_routeId
     * @param PCMWSStructArrayOfStopLocation $_stops
     * @param PCMWSStructRouteOptions $_options
     * @param PCMWSStructFuelOptions $_fuelOptions
     * @return PCMWSStructRoute
     */
    public function __construct($_routeId = NULL,$_stops = NULL,$_options = NULL,$_fuelOptions = NULL)
    {
        parent::__construct(array('RouteId'=>$_routeId,'Stops'=>($_stops instanceof PCMWSStructArrayOfStopLocation)?$_stops:new PCMWSStructArrayOfStopLocation($_stops),'Options'=>$_options,'FuelOptions'=>$_fuelOptions),false);
    }
    /**
     * Get RouteId value
     * @return string|null
     */
    public function getRouteId()
    {
        return $this->RouteId;
    }
    /**
     * Set RouteId value
     * @param string $_routeId the RouteId
     * @return string
     */
    public function setRouteId($_routeId)
    {
        return ($this->RouteId = $_routeId);
    }
    /**
     * Get Stops value
     * @return PCMWSStructArrayOfStopLocation|null
     */
    public function getStops()
    {
        return $this->Stops;
    }
    /**
     * Set Stops value
     * @param PCMWSStructArrayOfStopLocation $_stops the Stops
     * @return PCMWSStructArrayOfStopLocation
     */
    public function setStops($_stops)
    {
        return ($this->Stops = $_stops);
    }
    /**
     * Get Options value
     * @return PCMWSStructRouteOptions|null
     */
    public function getOptions()
    {
        return $this->Options;
    }
    /**
     * Set Options value
     * @param PCMWSStructRouteOptions $_options the Options
     * @return PCMWSStructRouteOptions
     */
    public function setOptions($_options)
    {
        return ($this->Options = $_options);
    }
    /**
     * Get FuelOptions value
     * @return PCMWSStructFuelOptions|null
     */
    public function getFuelOptions()
    {
        return $this->FuelOptions;
    }
    /**
     * Set FuelOptions value
     * @param PCMWSStructFuelOptions $_fuelOptions the FuelOptions
     * @return PCMWSStructFuelOptions
     */
    public function setFuelOptions($_fuelOptions)
    {
        return ($this->FuelOptions = $_fuelOptions);
    }
    /**
     * Method called when an object has been exported with var_export() functions
     * It allows to return an object instantiated with the values
     * @see PCMWSWsdlClass::__set_state()
     * @uses PCMWSWsdlClass::__set_state()
     * @param array $_array the exported values
     * @return PCMWSStructRoute
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
