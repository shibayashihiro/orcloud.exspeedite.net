<?php
/**
 * File for class PCMWSStructWeatherAlertsPointsReponseBody
 * @package PCMWS
 * @subpackage Structs
 * @author WsdlToPhp Team <contact@wsdltophp.com>
 * @version 20150429-01
 * @date 2016-08-25
 */
/**
 * This class stands for PCMWSStructWeatherAlertsPointsReponseBody originally named WeatherAlertsPointsReponseBody
 * Meta informations extracted from the WSDL
 * - from schema : {@link http://pcmiler.alk.com/APIs/SOAP/v1.0/Service.svc?xsd=xsd0}
 * @package PCMWS
 * @subpackage Structs
 * @author WsdlToPhp Team <contact@wsdltophp.com>
 * @version 20150429-01
 * @date 2016-08-25
 */
class PCMWSStructWeatherAlertsPointsReponseBody extends PCMWSWsdlClass
{
    /**
     * The WeatherAlerts
     * Meta informations extracted from the WSDL
     * - minOccurs : 0
     * - nillable : true
     * @var PCMWSStructArrayOfArrayOfWeatherAlertEvent
     */
    public $WeatherAlerts;
    /**
     * Constructor method for WeatherAlertsPointsReponseBody
     * @see parent::__construct()
     * @param PCMWSStructArrayOfArrayOfWeatherAlertEvent $_weatherAlerts
     * @return PCMWSStructWeatherAlertsPointsReponseBody
     */
    public function __construct($_weatherAlerts = NULL)
    {
        parent::__construct(array('WeatherAlerts'=>($_weatherAlerts instanceof PCMWSStructArrayOfArrayOfWeatherAlertEvent)?$_weatherAlerts:new PCMWSStructArrayOfArrayOfWeatherAlertEvent($_weatherAlerts)),false);
    }
    /**
     * Get WeatherAlerts value
     * @return PCMWSStructArrayOfArrayOfWeatherAlertEvent|null
     */
    public function getWeatherAlerts()
    {
        return $this->WeatherAlerts;
    }
    /**
     * Set WeatherAlerts value
     * @param PCMWSStructArrayOfArrayOfWeatherAlertEvent $_weatherAlerts the WeatherAlerts
     * @return PCMWSStructArrayOfArrayOfWeatherAlertEvent
     */
    public function setWeatherAlerts($_weatherAlerts)
    {
        return ($this->WeatherAlerts = $_weatherAlerts);
    }
    /**
     * Method called when an object has been exported with var_export() functions
     * It allows to return an object instantiated with the values
     * @see PCMWSWsdlClass::__set_state()
     * @uses PCMWSWsdlClass::__set_state()
     * @param array $_array the exported values
     * @return PCMWSStructWeatherAlertsPointsReponseBody
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
