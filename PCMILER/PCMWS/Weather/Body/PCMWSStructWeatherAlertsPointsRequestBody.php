<?php
/**
 * File for class PCMWSStructWeatherAlertsPointsRequestBody
 * @package PCMWS
 * @subpackage Structs
 * @author WsdlToPhp Team <contact@wsdltophp.com>
 * @version 20150429-01
 * @date 2016-08-25
 */
/**
 * This class stands for PCMWSStructWeatherAlertsPointsRequestBody originally named WeatherAlertsPointsRequestBody
 * Meta informations extracted from the WSDL
 * - from schema : {@link http://pcmiler.alk.com/APIs/SOAP/v1.0/Service.svc?xsd=xsd0}
 * @package PCMWS
 * @subpackage Structs
 * @author WsdlToPhp Team <contact@wsdltophp.com>
 * @version 20150429-01
 * @date 2016-08-25
 */
class PCMWSStructWeatherAlertsPointsRequestBody extends PCMWSStructWeatherAlertsBaseRequestBody
{
    /**
     * The Points
     * Meta informations extracted from the WSDL
     * - minOccurs : 0
     * - nillable : true
     * @var PCMWSStructArrayOfCoordinates
     */
    public $Points;
    /**
     * Constructor method for WeatherAlertsPointsRequestBody
     * @see parent::__construct()
     * @param PCMWSStructArrayOfCoordinates $_points
     * @return PCMWSStructWeatherAlertsPointsRequestBody
     */
    public function __construct($_points = NULL)
    {
        PCMWSWsdlClass::__construct(array('Points'=>($_points instanceof PCMWSStructArrayOfCoordinates)?$_points:new PCMWSStructArrayOfCoordinates($_points)),false);
    }
    /**
     * Get Points value
     * @return PCMWSStructArrayOfCoordinates|null
     */
    public function getPoints()
    {
        return $this->Points;
    }
    /**
     * Set Points value
     * @param PCMWSStructArrayOfCoordinates $_points the Points
     * @return PCMWSStructArrayOfCoordinates
     */
    public function setPoints($_points)
    {
        return ($this->Points = $_points);
    }
    /**
     * Method called when an object has been exported with var_export() functions
     * It allows to return an object instantiated with the values
     * @see PCMWSWsdlClass::__set_state()
     * @uses PCMWSWsdlClass::__set_state()
     * @param array $_array the exported values
     * @return PCMWSStructWeatherAlertsPointsRequestBody
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
