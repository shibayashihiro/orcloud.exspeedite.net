<?php
/**
 * File for class PCMWSStructWeatherAlertsBaseRequestBody
 * @package PCMWS
 * @subpackage Structs
 * @author WsdlToPhp Team <contact@wsdltophp.com>
 * @version 20150429-01
 * @date 2016-08-25
 */
/**
 * This class stands for PCMWSStructWeatherAlertsBaseRequestBody originally named WeatherAlertsBaseRequestBody
 * Meta informations extracted from the WSDL
 * - from schema : {@link http://pcmiler.alk.com/APIs/SOAP/v1.0/Service.svc?xsd=xsd0}
 * @package PCMWS
 * @subpackage Structs
 * @author WsdlToPhp Team <contact@wsdltophp.com>
 * @version 20150429-01
 * @date 2016-08-25
 */
class PCMWSStructWeatherAlertsBaseRequestBody extends PCMWSWsdlClass
{
    /**
     * The Urgency
     * Meta informations extracted from the WSDL
     * - minOccurs : 0
     * - nillable : true
     * @var PCMWSEnumWeatherAlertUrgency
     */
    public $Urgency;
    /**
     * The Severity
     * Meta informations extracted from the WSDL
     * - minOccurs : 0
     * - nillable : true
     * @var PCMWSEnumWeatherAlertSeverity
     */
    public $Severity;
    /**
     * The Certainty
     * Meta informations extracted from the WSDL
     * - minOccurs : 0
     * - nillable : true
     * @var PCMWSEnumWeatherAlertCertainty
     */
    public $Certainty;
    /**
     * The EventNames
     * Meta informations extracted from the WSDL
     * - minOccurs : 0
     * - nillable : true
     * @var PCMWSStructArrayOfstring
     */
    public $EventNames;
    /**
     * The StartTime
     * Meta informations extracted from the WSDL
     * - minOccurs : 0
     * - nillable : true
     * @var string
     */
    public $StartTime;
    /**
     * The EndTime
     * Meta informations extracted from the WSDL
     * - minOccurs : 0
     * - nillable : true
     * @var string
     */
    public $EndTime;
    /**
     * Constructor method for WeatherAlertsBaseRequestBody
     * @see parent::__construct()
     * @param PCMWSEnumWeatherAlertUrgency $_urgency
     * @param PCMWSEnumWeatherAlertSeverity $_severity
     * @param PCMWSEnumWeatherAlertCertainty $_certainty
     * @param PCMWSStructArrayOfstring $_eventNames
     * @param string $_startTime
     * @param string $_endTime
     * @return PCMWSStructWeatherAlertsBaseRequestBody
     */
    public function __construct($_urgency = NULL,$_severity = NULL,$_certainty = NULL,$_eventNames = NULL,$_startTime = NULL,$_endTime = NULL)
    {
        parent::__construct(array('Urgency'=>$_urgency,'Severity'=>$_severity,'Certainty'=>$_certainty,'EventNames'=>($_eventNames instanceof PCMWSStructArrayOfstring)?$_eventNames:new PCMWSStructArrayOfstring($_eventNames),'StartTime'=>$_startTime,'EndTime'=>$_endTime),false);
    }
    /**
     * Get Urgency value
     * @return PCMWSEnumWeatherAlertUrgency|null
     */
    public function getUrgency()
    {
        return $this->Urgency;
    }
    /**
     * Set Urgency value
     * @uses PCMWSEnumWeatherAlertUrgency::valueIsValid()
     * @param PCMWSEnumWeatherAlertUrgency $_urgency the Urgency
     * @return PCMWSEnumWeatherAlertUrgency
     */
    public function setUrgency($_urgency)
    {
        if(!PCMWSEnumWeatherAlertUrgency::valueIsValid($_urgency))
        {
            return false;
        }
        return ($this->Urgency = $_urgency);
    }
    /**
     * Get Severity value
     * @return PCMWSEnumWeatherAlertSeverity|null
     */
    public function getSeverity()
    {
        return $this->Severity;
    }
    /**
     * Set Severity value
     * @uses PCMWSEnumWeatherAlertSeverity::valueIsValid()
     * @param PCMWSEnumWeatherAlertSeverity $_severity the Severity
     * @return PCMWSEnumWeatherAlertSeverity
     */
    public function setSeverity($_severity)
    {
        if(!PCMWSEnumWeatherAlertSeverity::valueIsValid($_severity))
        {
            return false;
        }
        return ($this->Severity = $_severity);
    }
    /**
     * Get Certainty value
     * @return PCMWSEnumWeatherAlertCertainty|null
     */
    public function getCertainty()
    {
        return $this->Certainty;
    }
    /**
     * Set Certainty value
     * @uses PCMWSEnumWeatherAlertCertainty::valueIsValid()
     * @param PCMWSEnumWeatherAlertCertainty $_certainty the Certainty
     * @return PCMWSEnumWeatherAlertCertainty
     */
    public function setCertainty($_certainty)
    {
        if(!PCMWSEnumWeatherAlertCertainty::valueIsValid($_certainty))
        {
            return false;
        }
        return ($this->Certainty = $_certainty);
    }
    /**
     * Get EventNames value
     * @return PCMWSStructArrayOfstring|null
     */
    public function getEventNames()
    {
        return $this->EventNames;
    }
    /**
     * Set EventNames value
     * @param PCMWSStructArrayOfstring $_eventNames the EventNames
     * @return PCMWSStructArrayOfstring
     */
    public function setEventNames($_eventNames)
    {
        return ($this->EventNames = $_eventNames);
    }
    /**
     * Get StartTime value
     * @return string|null
     */
    public function getStartTime()
    {
        return $this->StartTime;
    }
    /**
     * Set StartTime value
     * @param string $_startTime the StartTime
     * @return string
     */
    public function setStartTime($_startTime)
    {
        return ($this->StartTime = $_startTime);
    }
    /**
     * Get EndTime value
     * @return string|null
     */
    public function getEndTime()
    {
        return $this->EndTime;
    }
    /**
     * Set EndTime value
     * @param string $_endTime the EndTime
     * @return string
     */
    public function setEndTime($_endTime)
    {
        return ($this->EndTime = $_endTime);
    }
    /**
     * Method called when an object has been exported with var_export() functions
     * It allows to return an object instantiated with the values
     * @see PCMWSWsdlClass::__set_state()
     * @uses PCMWSWsdlClass::__set_state()
     * @param array $_array the exported values
     * @return PCMWSStructWeatherAlertsBaseRequestBody
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
