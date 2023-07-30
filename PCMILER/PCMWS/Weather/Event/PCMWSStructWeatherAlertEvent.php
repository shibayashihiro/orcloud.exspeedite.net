<?php
/**
 * File for class PCMWSStructWeatherAlertEvent
 * @package PCMWS
 * @subpackage Structs
 * @author WsdlToPhp Team <contact@wsdltophp.com>
 * @version 20150429-01
 * @date 2016-08-25
 */
/**
 * This class stands for PCMWSStructWeatherAlertEvent originally named WeatherAlertEvent
 * Meta informations extracted from the WSDL
 * - from schema : {@link http://pcmiler.alk.com/APIs/SOAP/v1.0/Service.svc?xsd=xsd0}
 * @package PCMWS
 * @subpackage Structs
 * @author WsdlToPhp Team <contact@wsdltophp.com>
 * @version 20150429-01
 * @date 2016-08-25
 */
class PCMWSStructWeatherAlertEvent extends PCMWSWsdlClass
{
    /**
     * The AlertEvent
     * Meta informations extracted from the WSDL
     * - minOccurs : 0
     * - nillable : true
     * @var string
     */
    public $AlertEvent;
    /**
     * The AreaDesc
     * Meta informations extracted from the WSDL
     * - minOccurs : 0
     * - nillable : true
     * @var string
     */
    public $AreaDesc;
    /**
     * The Certainty
     * Meta informations extracted from the WSDL
     * - minOccurs : 0
     * - nillable : true
     * @var string
     */
    public $Certainty;
    /**
     * The CountryCode
     * Meta informations extracted from the WSDL
     * - minOccurs : 0
     * - nillable : true
     * @var string
     */
    public $CountryCode;
    /**
     * The DispEffective
     * Meta informations extracted from the WSDL
     * - minOccurs : 0
     * - nillable : true
     * @var string
     */
    public $DispEffective;
    /**
     * The DispExpires
     * Meta informations extracted from the WSDL
     * - minOccurs : 0
     * - nillable : true
     * @var string
     */
    public $DispExpires;
    /**
     * The DispPublished
     * Meta informations extracted from the WSDL
     * - minOccurs : 0
     * - nillable : true
     * @var string
     */
    public $DispPublished;
    /**
     * The DispUpdated
     * Meta informations extracted from the WSDL
     * - minOccurs : 0
     * - nillable : true
     * @var string
     */
    public $DispUpdated;
    /**
     * The Effective
     * Meta informations extracted from the WSDL
     * - minOccurs : 0
     * @var dateTime
     */
    public $Effective;
    /**
     * The Expires
     * Meta informations extracted from the WSDL
     * - minOccurs : 0
     * @var dateTime
     */
    public $Expires;
    /**
     * The FipsCodes
     * Meta informations extracted from the WSDL
     * - minOccurs : 0
     * - nillable : true
     * @var string
     */
    public $FipsCodes;
    /**
     * The LanguageCode
     * Meta informations extracted from the WSDL
     * - minOccurs : 0
     * - nillable : true
     * @var string
     */
    public $LanguageCode;
    /**
     * The Polygon
     * Meta informations extracted from the WSDL
     * - minOccurs : 0
     * - nillable : true
     * @var string
     */
    public $Polygon;
    /**
     * The Published
     * Meta informations extracted from the WSDL
     * - minOccurs : 0
     * @var dateTime
     */
    public $Published;
    /**
     * The Severity
     * Meta informations extracted from the WSDL
     * - minOccurs : 0
     * - nillable : true
     * @var string
     */
    public $Severity;
    /**
     * The Summary
     * Meta informations extracted from the WSDL
     * - minOccurs : 0
     * - nillable : true
     * @var string
     */
    public $Summary;
    /**
     * The Title
     * Meta informations extracted from the WSDL
     * - minOccurs : 0
     * - nillable : true
     * @var string
     */
    public $Title;
    /**
     * The Updated
     * Meta informations extracted from the WSDL
     * - minOccurs : 0
     * @var dateTime
     */
    public $Updated;
    /**
     * The Urgency
     * Meta informations extracted from the WSDL
     * - minOccurs : 0
     * - nillable : true
     * @var string
     */
    public $Urgency;
    /**
     * The ID
     * Meta informations extracted from the WSDL
     * - minOccurs : 0
     * - nillable : true
     * @var string
     */
    public $ID;
    /**
     * Constructor method for WeatherAlertEvent
     * @see parent::__construct()
     * @param string $_alertEvent
     * @param string $_areaDesc
     * @param string $_certainty
     * @param string $_countryCode
     * @param string $_dispEffective
     * @param string $_dispExpires
     * @param string $_dispPublished
     * @param string $_dispUpdated
     * @param dateTime $_effective
     * @param dateTime $_expires
     * @param string $_fipsCodes
     * @param string $_languageCode
     * @param string $_polygon
     * @param dateTime $_published
     * @param string $_severity
     * @param string $_summary
     * @param string $_title
     * @param dateTime $_updated
     * @param string $_urgency
     * @param string $_iD
     * @return PCMWSStructWeatherAlertEvent
     */
    public function __construct($_alertEvent = NULL,$_areaDesc = NULL,$_certainty = NULL,$_countryCode = NULL,$_dispEffective = NULL,$_dispExpires = NULL,$_dispPublished = NULL,$_dispUpdated = NULL,$_effective = NULL,$_expires = NULL,$_fipsCodes = NULL,$_languageCode = NULL,$_polygon = NULL,$_published = NULL,$_severity = NULL,$_summary = NULL,$_title = NULL,$_updated = NULL,$_urgency = NULL,$_iD = NULL)
    {
        parent::__construct(array('AlertEvent'=>$_alertEvent,'AreaDesc'=>$_areaDesc,'Certainty'=>$_certainty,'CountryCode'=>$_countryCode,'DispEffective'=>$_dispEffective,'DispExpires'=>$_dispExpires,'DispPublished'=>$_dispPublished,'DispUpdated'=>$_dispUpdated,'Effective'=>$_effective,'Expires'=>$_expires,'FipsCodes'=>$_fipsCodes,'LanguageCode'=>$_languageCode,'Polygon'=>$_polygon,'Published'=>$_published,'Severity'=>$_severity,'Summary'=>$_summary,'Title'=>$_title,'Updated'=>$_updated,'Urgency'=>$_urgency,'ID'=>$_iD),false);
    }
    /**
     * Get AlertEvent value
     * @return string|null
     */
    public function getAlertEvent()
    {
        return $this->AlertEvent;
    }
    /**
     * Set AlertEvent value
     * @param string $_alertEvent the AlertEvent
     * @return string
     */
    public function setAlertEvent($_alertEvent)
    {
        return ($this->AlertEvent = $_alertEvent);
    }
    /**
     * Get AreaDesc value
     * @return string|null
     */
    public function getAreaDesc()
    {
        return $this->AreaDesc;
    }
    /**
     * Set AreaDesc value
     * @param string $_areaDesc the AreaDesc
     * @return string
     */
    public function setAreaDesc($_areaDesc)
    {
        return ($this->AreaDesc = $_areaDesc);
    }
    /**
     * Get Certainty value
     * @return string|null
     */
    public function getCertainty()
    {
        return $this->Certainty;
    }
    /**
     * Set Certainty value
     * @param string $_certainty the Certainty
     * @return string
     */
    public function setCertainty($_certainty)
    {
        return ($this->Certainty = $_certainty);
    }
    /**
     * Get CountryCode value
     * @return string|null
     */
    public function getCountryCode()
    {
        return $this->CountryCode;
    }
    /**
     * Set CountryCode value
     * @param string $_countryCode the CountryCode
     * @return string
     */
    public function setCountryCode($_countryCode)
    {
        return ($this->CountryCode = $_countryCode);
    }
    /**
     * Get DispEffective value
     * @return string|null
     */
    public function getDispEffective()
    {
        return $this->DispEffective;
    }
    /**
     * Set DispEffective value
     * @param string $_dispEffective the DispEffective
     * @return string
     */
    public function setDispEffective($_dispEffective)
    {
        return ($this->DispEffective = $_dispEffective);
    }
    /**
     * Get DispExpires value
     * @return string|null
     */
    public function getDispExpires()
    {
        return $this->DispExpires;
    }
    /**
     * Set DispExpires value
     * @param string $_dispExpires the DispExpires
     * @return string
     */
    public function setDispExpires($_dispExpires)
    {
        return ($this->DispExpires = $_dispExpires);
    }
    /**
     * Get DispPublished value
     * @return string|null
     */
    public function getDispPublished()
    {
        return $this->DispPublished;
    }
    /**
     * Set DispPublished value
     * @param string $_dispPublished the DispPublished
     * @return string
     */
    public function setDispPublished($_dispPublished)
    {
        return ($this->DispPublished = $_dispPublished);
    }
    /**
     * Get DispUpdated value
     * @return string|null
     */
    public function getDispUpdated()
    {
        return $this->DispUpdated;
    }
    /**
     * Set DispUpdated value
     * @param string $_dispUpdated the DispUpdated
     * @return string
     */
    public function setDispUpdated($_dispUpdated)
    {
        return ($this->DispUpdated = $_dispUpdated);
    }
    /**
     * Get Effective value
     * @return dateTime|null
     */
    public function getEffective()
    {
        return $this->Effective;
    }
    /**
     * Set Effective value
     * @param dateTime $_effective the Effective
     * @return dateTime
     */
    public function setEffective($_effective)
    {
        return ($this->Effective = $_effective);
    }
    /**
     * Get Expires value
     * @return dateTime|null
     */
    public function getExpires()
    {
        return $this->Expires;
    }
    /**
     * Set Expires value
     * @param dateTime $_expires the Expires
     * @return dateTime
     */
    public function setExpires($_expires)
    {
        return ($this->Expires = $_expires);
    }
    /**
     * Get FipsCodes value
     * @return string|null
     */
    public function getFipsCodes()
    {
        return $this->FipsCodes;
    }
    /**
     * Set FipsCodes value
     * @param string $_fipsCodes the FipsCodes
     * @return string
     */
    public function setFipsCodes($_fipsCodes)
    {
        return ($this->FipsCodes = $_fipsCodes);
    }
    /**
     * Get LanguageCode value
     * @return string|null
     */
    public function getLanguageCode()
    {
        return $this->LanguageCode;
    }
    /**
     * Set LanguageCode value
     * @param string $_languageCode the LanguageCode
     * @return string
     */
    public function setLanguageCode($_languageCode)
    {
        return ($this->LanguageCode = $_languageCode);
    }
    /**
     * Get Polygon value
     * @return string|null
     */
    public function getPolygon()
    {
        return $this->Polygon;
    }
    /**
     * Set Polygon value
     * @param string $_polygon the Polygon
     * @return string
     */
    public function setPolygon($_polygon)
    {
        return ($this->Polygon = $_polygon);
    }
    /**
     * Get Published value
     * @return dateTime|null
     */
    public function getPublished()
    {
        return $this->Published;
    }
    /**
     * Set Published value
     * @param dateTime $_published the Published
     * @return dateTime
     */
    public function setPublished($_published)
    {
        return ($this->Published = $_published);
    }
    /**
     * Get Severity value
     * @return string|null
     */
    public function getSeverity()
    {
        return $this->Severity;
    }
    /**
     * Set Severity value
     * @param string $_severity the Severity
     * @return string
     */
    public function setSeverity($_severity)
    {
        return ($this->Severity = $_severity);
    }
    /**
     * Get Summary value
     * @return string|null
     */
    public function getSummary()
    {
        return $this->Summary;
    }
    /**
     * Set Summary value
     * @param string $_summary the Summary
     * @return string
     */
    public function setSummary($_summary)
    {
        return ($this->Summary = $_summary);
    }
    /**
     * Get Title value
     * @return string|null
     */
    public function getTitle()
    {
        return $this->Title;
    }
    /**
     * Set Title value
     * @param string $_title the Title
     * @return string
     */
    public function setTitle($_title)
    {
        return ($this->Title = $_title);
    }
    /**
     * Get Updated value
     * @return dateTime|null
     */
    public function getUpdated()
    {
        return $this->Updated;
    }
    /**
     * Set Updated value
     * @param dateTime $_updated the Updated
     * @return dateTime
     */
    public function setUpdated($_updated)
    {
        return ($this->Updated = $_updated);
    }
    /**
     * Get Urgency value
     * @return string|null
     */
    public function getUrgency()
    {
        return $this->Urgency;
    }
    /**
     * Set Urgency value
     * @param string $_urgency the Urgency
     * @return string
     */
    public function setUrgency($_urgency)
    {
        return ($this->Urgency = $_urgency);
    }
    /**
     * Get ID value
     * @return string|null
     */
    public function getID()
    {
        return $this->ID;
    }
    /**
     * Set ID value
     * @param string $_iD the ID
     * @return string
     */
    public function setID($_iD)
    {
        return ($this->ID = $_iD);
    }
    /**
     * Method called when an object has been exported with var_export() functions
     * It allows to return an object instantiated with the values
     * @see PCMWSWsdlClass::__set_state()
     * @uses PCMWSWsdlClass::__set_state()
     * @param array $_array the exported values
     * @return PCMWSStructWeatherAlertEvent
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
