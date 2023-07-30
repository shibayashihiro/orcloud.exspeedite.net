<?php
/**
 * File for class PCMWSStructGetWeatherAlertsResponse
 * @package PCMWS
 * @subpackage Structs
 * @author WsdlToPhp Team <contact@wsdltophp.com>
 * @version 20150429-01
 * @date 2016-08-25
 */
/**
 * This class stands for PCMWSStructGetWeatherAlertsResponse originally named GetWeatherAlertsResponse
 * Meta informations extracted from the WSDL
 * - from schema : {@link http://pcmiler.alk.com/APIs/SOAP/v1.0/Service.svc?xsd=xsd0}
 * @package PCMWS
 * @subpackage Structs
 * @author WsdlToPhp Team <contact@wsdltophp.com>
 * @version 20150429-01
 * @date 2016-08-25
 */
class PCMWSStructGetWeatherAlertsResponse extends PCMWSWsdlClass
{
    /**
     * The GetWeatherAlertsResult
     * Meta informations extracted from the WSDL
     * - minOccurs : 0
     * - nillable : true
     * @var PCMWSStructWeatherAlertsPointsResponse
     */
    public $GetWeatherAlertsResult;
    /**
     * Constructor method for GetWeatherAlertsResponse
     * @see parent::__construct()
     * @param PCMWSStructWeatherAlertsPointsResponse $_getWeatherAlertsResult
     * @return PCMWSStructGetWeatherAlertsResponse
     */
    public function __construct($_getWeatherAlertsResult = NULL)
    {
        parent::__construct(array('GetWeatherAlertsResult'=>$_getWeatherAlertsResult),false);
    }
    /**
     * Get GetWeatherAlertsResult value
     * @return PCMWSStructWeatherAlertsPointsResponse|null
     */
    public function getGetWeatherAlertsResult()
    {
        return $this->GetWeatherAlertsResult;
    }
    /**
     * Set GetWeatherAlertsResult value
     * @param PCMWSStructWeatherAlertsPointsResponse $_getWeatherAlertsResult the GetWeatherAlertsResult
     * @return PCMWSStructWeatherAlertsPointsResponse
     */
    public function setGetWeatherAlertsResult($_getWeatherAlertsResult)
    {
        return ($this->GetWeatherAlertsResult = $_getWeatherAlertsResult);
    }
    /**
     * Method called when an object has been exported with var_export() functions
     * It allows to return an object instantiated with the values
     * @see PCMWSWsdlClass::__set_state()
     * @uses PCMWSWsdlClass::__set_state()
     * @param array $_array the exported values
     * @return PCMWSStructGetWeatherAlertsResponse
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
