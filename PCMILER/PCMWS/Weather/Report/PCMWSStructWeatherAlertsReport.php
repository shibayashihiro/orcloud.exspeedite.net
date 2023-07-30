<?php
/**
 * File for class PCMWSStructWeatherAlertsReport
 * @package PCMWS
 * @subpackage Structs
 * @author WsdlToPhp Team <contact@wsdltophp.com>
 * @version 20150429-01
 * @date 2016-08-25
 */
/**
 * This class stands for PCMWSStructWeatherAlertsReport originally named WeatherAlertsReport
 * Meta informations extracted from the WSDL
 * - from schema : {@link http://pcmiler.alk.com/APIs/SOAP/v1.0/Service.svc?xsd=xsd0}
 * @package PCMWS
 * @subpackage Structs
 * @author WsdlToPhp Team <contact@wsdltophp.com>
 * @version 20150429-01
 * @date 2016-08-25
 */
class PCMWSStructWeatherAlertsReport extends PCMWSStructReport
{
    /**
     * The WeatherAlerts
     * Meta informations extracted from the WSDL
     * - minOccurs : 0
     * - nillable : true
     * @var PCMWSStructArrayOfWeatherAlertEvent
     */
    public $WeatherAlerts;
    /**
     * Constructor method for WeatherAlertsReport
     * @see parent::__construct()
     * @param PCMWSStructArrayOfWeatherAlertEvent $_weatherAlerts
     * @return PCMWSStructWeatherAlertsReport
     */
    public function __construct($_weatherAlerts = NULL)
    {
        PCMWSWsdlClass::__construct(array('WeatherAlerts'=>($_weatherAlerts instanceof PCMWSStructArrayOfWeatherAlertEvent)?$_weatherAlerts:new PCMWSStructArrayOfWeatherAlertEvent($_weatherAlerts)),false);
    }
    /**
     * Get WeatherAlerts value
     * @return PCMWSStructArrayOfWeatherAlertEvent|null
     */
    public function getWeatherAlerts()
    {
        return $this->WeatherAlerts;
    }
    /**
     * Set WeatherAlerts value
     * @param PCMWSStructArrayOfWeatherAlertEvent $_weatherAlerts the WeatherAlerts
     * @return PCMWSStructArrayOfWeatherAlertEvent
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
     * @return PCMWSStructWeatherAlertsReport
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
