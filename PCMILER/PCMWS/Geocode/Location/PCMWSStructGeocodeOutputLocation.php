<?php
/**
 * File for class PCMWSStructGeocodeOutputLocation
 * @package PCMWS
 * @subpackage Structs
 * @author WsdlToPhp Team <contact@wsdltophp.com>
 * @version 20150429-01
 * @date 2016-08-25
 */
/**
 * This class stands for PCMWSStructGeocodeOutputLocation originally named GeocodeOutputLocation
 * Meta informations extracted from the WSDL
 * - from schema : {@link http://pcmiler.alk.com/APIs/SOAP/v1.0/Service.svc?xsd=xsd0}
 * @package PCMWS
 * @subpackage Structs
 * @author WsdlToPhp Team <contact@wsdltophp.com>
 * @version 20150429-01
 * @date 2016-08-25
 */
class PCMWSStructGeocodeOutputLocation extends PCMWSStructLocation
{
    /**
     * The TimeZone
     * Meta informations extracted from the WSDL
     * - minOccurs : 0
     * - nillable : true
     * @var string
     */
    public $TimeZone;
    /**
     * The Errors
     * Meta informations extracted from the WSDL
     * - minOccurs : 0
     * - nillable : true
     * @var PCMWSStructArrayOfError
     */
    public $Errors;
    /**
     * The SpeedLimitInfo
     * Meta informations extracted from the WSDL
     * - minOccurs : 0
     * - nillable : true
     * @var PCMWSStructSpeedLimit
     */
    public $SpeedLimitInfo;
    /**
     * The ConfidenceLevel
     * Meta informations extracted from the WSDL
     * - minOccurs : 0
     * - nillable : true
     * @var string
     */
    public $ConfidenceLevel;
    /**
     * The DistanceFromRoad
     * Meta informations extracted from the WSDL
     * - minOccurs : 0
     * - nillable : true
     * @var double
     */
    public $DistanceFromRoad;
    /**
     * The CrossStreet
     * Meta informations extracted from the WSDL
     * - minOccurs : 0
     * - nillable : true
     * @var string
     */
    public $CrossStreet;
    /**
     * Constructor method for GeocodeOutputLocation
     * @see parent::__construct()
     * @param string $_timeZone
     * @param PCMWSStructArrayOfError $_errors
     * @param PCMWSStructSpeedLimit $_speedLimitInfo
     * @param string $_confidenceLevel
     * @param double $_distanceFromRoad
     * @param string $_crossStreet
     * @return PCMWSStructGeocodeOutputLocation
     */
    public function __construct($_timeZone = NULL,$_errors = NULL,$_speedLimitInfo = NULL,$_confidenceLevel = NULL,$_distanceFromRoad = NULL,$_crossStreet = NULL)
    {
        PCMWSWsdlClass::__construct(array('TimeZone'=>$_timeZone,'Errors'=>($_errors instanceof PCMWSStructArrayOfError)?$_errors:new PCMWSStructArrayOfError($_errors),'SpeedLimitInfo'=>$_speedLimitInfo,'ConfidenceLevel'=>$_confidenceLevel,'DistanceFromRoad'=>$_distanceFromRoad,'CrossStreet'=>$_crossStreet),false);
    }
    /**
     * Get TimeZone value
     * @return string|null
     */
    public function getTimeZone()
    {
        return $this->TimeZone;
    }
    /**
     * Set TimeZone value
     * @param string $_timeZone the TimeZone
     * @return string
     */
    public function setTimeZone($_timeZone)
    {
        return ($this->TimeZone = $_timeZone);
    }
    /**
     * Get Errors value
     * @return PCMWSStructArrayOfError|null
     */
    public function getErrors()
    {
        return $this->Errors;
    }
    /**
     * Set Errors value
     * @param PCMWSStructArrayOfError $_errors the Errors
     * @return PCMWSStructArrayOfError
     */
    public function setErrors($_errors)
    {
        return ($this->Errors = $_errors);
    }
    /**
     * Get SpeedLimitInfo value
     * @return PCMWSStructSpeedLimit|null
     */
    public function getSpeedLimitInfo()
    {
        return $this->SpeedLimitInfo;
    }
    /**
     * Set SpeedLimitInfo value
     * @param PCMWSStructSpeedLimit $_speedLimitInfo the SpeedLimitInfo
     * @return PCMWSStructSpeedLimit
     */
    public function setSpeedLimitInfo($_speedLimitInfo)
    {
        return ($this->SpeedLimitInfo = $_speedLimitInfo);
    }
    /**
     * Get ConfidenceLevel value
     * @return string|null
     */
    public function getConfidenceLevel()
    {
        return $this->ConfidenceLevel;
    }
    /**
     * Set ConfidenceLevel value
     * @param string $_confidenceLevel the ConfidenceLevel
     * @return string
     */
    public function setConfidenceLevel($_confidenceLevel)
    {
        return ($this->ConfidenceLevel = $_confidenceLevel);
    }
    /**
     * Get DistanceFromRoad value
     * @return double|null
     */
    public function getDistanceFromRoad()
    {
        return $this->DistanceFromRoad;
    }
    /**
     * Set DistanceFromRoad value
     * @param double $_distanceFromRoad the DistanceFromRoad
     * @return double
     */
    public function setDistanceFromRoad($_distanceFromRoad)
    {
        return ($this->DistanceFromRoad = $_distanceFromRoad);
    }
    /**
     * Get CrossStreet value
     * @return string|null
     */
    public function getCrossStreet()
    {
        return $this->CrossStreet;
    }
    /**
     * Set CrossStreet value
     * @param string $_crossStreet the CrossStreet
     * @return string
     */
    public function setCrossStreet($_crossStreet)
    {
        return ($this->CrossStreet = $_crossStreet);
    }
    /**
     * Method called when an object has been exported with var_export() functions
     * It allows to return an object instantiated with the values
     * @see PCMWSWsdlClass::__set_state()
     * @uses PCMWSWsdlClass::__set_state()
     * @param array $_array the exported values
     * @return PCMWSStructGeocodeOutputLocation
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
