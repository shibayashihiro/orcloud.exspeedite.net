<?php
/**
 * File for class PCMWSStructDetailReportLine
 * @package PCMWS
 * @subpackage Structs
 * @author WsdlToPhp Team <contact@wsdltophp.com>
 * @version 20150429-01
 * @date 2016-08-25
 */
/**
 * This class stands for PCMWSStructDetailReportLine originally named DetailReportLine
 * Meta informations extracted from the WSDL
 * - from schema : {@link http://pcmiler.alk.com/APIs/SOAP/v1.0/Service.svc?xsd=xsd0}
 * @package PCMWS
 * @subpackage Structs
 * @author WsdlToPhp Team <contact@wsdltophp.com>
 * @version 20150429-01
 * @date 2016-08-25
 */
class PCMWSStructDetailReportLine extends PCMWSWsdlClass
{
    /**
     * The Warn
     * Meta informations extracted from the WSDL
     * - minOccurs : 0
     * - nillable : true
     * @var string
     */
    public $Warn;
    /**
     * The ArState
     * Meta informations extracted from the WSDL
     * - minOccurs : 0
     * - nillable : true
     * @var string
     */
    public $ArState;
    /**
     * The Stop
     * Meta informations extracted from the WSDL
     * - minOccurs : 0
     * - nillable : true
     * @var string
     */
    public $Stop;
    /**
     * The State
     * Meta informations extracted from the WSDL
     * - minOccurs : 0
     * - nillable : true
     * @var string
     */
    public $State;
    /**
     * The Direction
     * Meta informations extracted from the WSDL
     * - minOccurs : 0
     * - nillable : true
     * @var string
     */
    public $Direction;
    /**
     * The Route
     * Meta informations extracted from the WSDL
     * - minOccurs : 0
     * - nillable : true
     * @var string
     */
    public $Route;
    /**
     * The Miles
     * Meta informations extracted from the WSDL
     * - minOccurs : 0
     * - nillable : true
     * @var string
     */
    public $Miles;
    /**
     * The Time
     * Meta informations extracted from the WSDL
     * - minOccurs : 0
     * - nillable : true
     * @var string
     */
    public $Time;
    /**
     * The InterCh
     * Meta informations extracted from the WSDL
     * - minOccurs : 0
     * - nillable : true
     * @var string
     */
    public $InterCh;
    /**
     * The LMiles
     * Meta informations extracted from the WSDL
     * - minOccurs : 0
     * - nillable : true
     * @var string
     */
    public $LMiles;
    /**
     * The LTime
     * Meta informations extracted from the WSDL
     * - minOccurs : 0
     * - nillable : true
     * @var string
     */
    public $LTime;
    /**
     * The TMiles
     * Meta informations extracted from the WSDL
     * - minOccurs : 0
     * - nillable : true
     * @var string
     */
    public $TMiles;
    /**
     * The TTime
     * Meta informations extracted from the WSDL
     * - minOccurs : 0
     * - nillable : true
     * @var string
     */
    public $TTime;
    /**
     * The LToll
     * Meta informations extracted from the WSDL
     * - minOccurs : 0
     * - nillable : true
     * @var string
     */
    public $LToll;
    /**
     * The TToll
     * Meta informations extracted from the WSDL
     * - minOccurs : 0
     * - nillable : true
     * @var string
     */
    public $TToll;
    /**
     * The TollPlazaAbbr
     * Meta informations extracted from the WSDL
     * - minOccurs : 0
     * - nillable : true
     * @var string
     */
    public $TollPlazaAbbr;
    /**
     * The TollPlazaName
     * Meta informations extracted from the WSDL
     * - minOccurs : 0
     * - nillable : true
     * @var string
     */
    public $TollPlazaName;
    /**
     * The EtaEtd
     * Meta informations extracted from the WSDL
     * - minOccurs : 0
     * - nillable : true
     * @var string
     */
    public $EtaEtd;
    /**
     * The Info
     * Meta informations extracted from the WSDL
     * - minOccurs : 0
     * - nillable : true
     * @var string
     */
    public $Info;
    /**
     * The Restriction
     * Meta informations extracted from the WSDL
     * - minOccurs : 0
     * - nillable : true
     * @var string
     */
    public $Restriction;
    /**
     * The StartCoordinate
     * Meta informations extracted from the WSDL
     * - minOccurs : 0
     * - nillable : true
     * @var string
     */
    public $StartCoordinate;
    /**
     * The EndCoordinate
     * Meta informations extracted from the WSDL
     * - minOccurs : 0
     * - nillable : true
     * @var string
     */
    public $EndCoordinate;
    /**
     * Constructor method for DetailReportLine
     * @see parent::__construct()
     * @param string $_warn
     * @param string $_arState
     * @param string $_stop
     * @param string $_state
     * @param string $_direction
     * @param string $_route
     * @param string $_miles
     * @param string $_time
     * @param string $_interCh
     * @param string $_lMiles
     * @param string $_lTime
     * @param string $_tMiles
     * @param string $_tTime
     * @param string $_lToll
     * @param string $_tToll
     * @param string $_tollPlazaAbbr
     * @param string $_tollPlazaName
     * @param string $_etaEtd
     * @param string $_info
     * @param string $_restriction
     * @param string $_startCoordinate
     * @param string $_endCoordinate
     * @return PCMWSStructDetailReportLine
     */
    public function __construct($_warn = NULL,$_arState = NULL,$_stop = NULL,$_state = NULL,$_direction = NULL,$_route = NULL,$_miles = NULL,$_time = NULL,$_interCh = NULL,$_lMiles = NULL,$_lTime = NULL,$_tMiles = NULL,$_tTime = NULL,$_lToll = NULL,$_tToll = NULL,$_tollPlazaAbbr = NULL,$_tollPlazaName = NULL,$_etaEtd = NULL,$_info = NULL,$_restriction = NULL,$_startCoordinate = NULL,$_endCoordinate = NULL)
    {
        parent::__construct(array('Warn'=>$_warn,'ArState'=>$_arState,'Stop'=>$_stop,'State'=>$_state,'Direction'=>$_direction,'Route'=>$_route,'Miles'=>$_miles,'Time'=>$_time,'InterCh'=>$_interCh,'LMiles'=>$_lMiles,'LTime'=>$_lTime,'TMiles'=>$_tMiles,'TTime'=>$_tTime,'LToll'=>$_lToll,'TToll'=>$_tToll,'TollPlazaAbbr'=>$_tollPlazaAbbr,'TollPlazaName'=>$_tollPlazaName,'EtaEtd'=>$_etaEtd,'Info'=>$_info,'Restriction'=>$_restriction,'StartCoordinate'=>$_startCoordinate,'EndCoordinate'=>$_endCoordinate),false);
    }
    /**
     * Get Warn value
     * @return string|null
     */
    public function getWarn()
    {
        return $this->Warn;
    }
    /**
     * Set Warn value
     * @param string $_warn the Warn
     * @return string
     */
    public function setWarn($_warn)
    {
        return ($this->Warn = $_warn);
    }
    /**
     * Get ArState value
     * @return string|null
     */
    public function getArState()
    {
        return $this->ArState;
    }
    /**
     * Set ArState value
     * @param string $_arState the ArState
     * @return string
     */
    public function setArState($_arState)
    {
        return ($this->ArState = $_arState);
    }
    /**
     * Get Stop value
     * @return string|null
     */
    public function getStop()
    {
        return $this->Stop;
    }
    /**
     * Set Stop value
     * @param string $_stop the Stop
     * @return string
     */
    public function setStop($_stop)
    {
        return ($this->Stop = $_stop);
    }
    /**
     * Get State value
     * @return string|null
     */
    public function getState()
    {
        return $this->State;
    }
    /**
     * Set State value
     * @param string $_state the State
     * @return string
     */
    public function setState($_state)
    {
        return ($this->State = $_state);
    }
    /**
     * Get Direction value
     * @return string|null
     */
    public function getDirection()
    {
        return $this->Direction;
    }
    /**
     * Set Direction value
     * @param string $_direction the Direction
     * @return string
     */
    public function setDirection($_direction)
    {
        return ($this->Direction = $_direction);
    }
    /**
     * Get Route value
     * @return string|null
     */
    public function getRoute()
    {
        return $this->Route;
    }
    /**
     * Set Route value
     * @param string $_route the Route
     * @return string
     */
    public function setRoute($_route)
    {
        return ($this->Route = $_route);
    }
    /**
     * Get Miles value
     * @return string|null
     */
    public function getMiles()
    {
        return $this->Miles;
    }
    /**
     * Set Miles value
     * @param string $_miles the Miles
     * @return string
     */
    public function setMiles($_miles)
    {
        return ($this->Miles = $_miles);
    }
    /**
     * Get Time value
     * @return string|null
     */
    public function getTime()
    {
        return $this->Time;
    }
    /**
     * Set Time value
     * @param string $_time the Time
     * @return string
     */
    public function setTime($_time)
    {
        return ($this->Time = $_time);
    }
    /**
     * Get InterCh value
     * @return string|null
     */
    public function getInterCh()
    {
        return $this->InterCh;
    }
    /**
     * Set InterCh value
     * @param string $_interCh the InterCh
     * @return string
     */
    public function setInterCh($_interCh)
    {
        return ($this->InterCh = $_interCh);
    }
    /**
     * Get LMiles value
     * @return string|null
     */
    public function getLMiles()
    {
        return $this->LMiles;
    }
    /**
     * Set LMiles value
     * @param string $_lMiles the LMiles
     * @return string
     */
    public function setLMiles($_lMiles)
    {
        return ($this->LMiles = $_lMiles);
    }
    /**
     * Get LTime value
     * @return string|null
     */
    public function getLTime()
    {
        return $this->LTime;
    }
    /**
     * Set LTime value
     * @param string $_lTime the LTime
     * @return string
     */
    public function setLTime($_lTime)
    {
        return ($this->LTime = $_lTime);
    }
    /**
     * Get TMiles value
     * @return string|null
     */
    public function getTMiles()
    {
        return $this->TMiles;
    }
    /**
     * Set TMiles value
     * @param string $_tMiles the TMiles
     * @return string
     */
    public function setTMiles($_tMiles)
    {
        return ($this->TMiles = $_tMiles);
    }
    /**
     * Get TTime value
     * @return string|null
     */
    public function getTTime()
    {
        return $this->TTime;
    }
    /**
     * Set TTime value
     * @param string $_tTime the TTime
     * @return string
     */
    public function setTTime($_tTime)
    {
        return ($this->TTime = $_tTime);
    }
    /**
     * Get LToll value
     * @return string|null
     */
    public function getLToll()
    {
        return $this->LToll;
    }
    /**
     * Set LToll value
     * @param string $_lToll the LToll
     * @return string
     */
    public function setLToll($_lToll)
    {
        return ($this->LToll = $_lToll);
    }
    /**
     * Get TToll value
     * @return string|null
     */
    public function getTToll()
    {
        return $this->TToll;
    }
    /**
     * Set TToll value
     * @param string $_tToll the TToll
     * @return string
     */
    public function setTToll($_tToll)
    {
        return ($this->TToll = $_tToll);
    }
    /**
     * Get TollPlazaAbbr value
     * @return string|null
     */
    public function getTollPlazaAbbr()
    {
        return $this->TollPlazaAbbr;
    }
    /**
     * Set TollPlazaAbbr value
     * @param string $_tollPlazaAbbr the TollPlazaAbbr
     * @return string
     */
    public function setTollPlazaAbbr($_tollPlazaAbbr)
    {
        return ($this->TollPlazaAbbr = $_tollPlazaAbbr);
    }
    /**
     * Get TollPlazaName value
     * @return string|null
     */
    public function getTollPlazaName()
    {
        return $this->TollPlazaName;
    }
    /**
     * Set TollPlazaName value
     * @param string $_tollPlazaName the TollPlazaName
     * @return string
     */
    public function setTollPlazaName($_tollPlazaName)
    {
        return ($this->TollPlazaName = $_tollPlazaName);
    }
    /**
     * Get EtaEtd value
     * @return string|null
     */
    public function getEtaEtd()
    {
        return $this->EtaEtd;
    }
    /**
     * Set EtaEtd value
     * @param string $_etaEtd the EtaEtd
     * @return string
     */
    public function setEtaEtd($_etaEtd)
    {
        return ($this->EtaEtd = $_etaEtd);
    }
    /**
     * Get Info value
     * @return string|null
     */
    public function getInfo()
    {
        return $this->Info;
    }
    /**
     * Set Info value
     * @param string $_info the Info
     * @return string
     */
    public function setInfo($_info)
    {
        return ($this->Info = $_info);
    }
    /**
     * Get Restriction value
     * @return string|null
     */
    public function getRestriction()
    {
        return $this->Restriction;
    }
    /**
     * Set Restriction value
     * @param string $_restriction the Restriction
     * @return string
     */
    public function setRestriction($_restriction)
    {
        return ($this->Restriction = $_restriction);
    }
    /**
     * Get StartCoordinate value
     * @return string|null
     */
    public function getStartCoordinate()
    {
        return $this->StartCoordinate;
    }
    /**
     * Set StartCoordinate value
     * @param string $_startCoordinate the StartCoordinate
     * @return string
     */
    public function setStartCoordinate($_startCoordinate)
    {
        return ($this->StartCoordinate = $_startCoordinate);
    }
    /**
     * Get EndCoordinate value
     * @return string|null
     */
    public function getEndCoordinate()
    {
        return $this->EndCoordinate;
    }
    /**
     * Set EndCoordinate value
     * @param string $_endCoordinate the EndCoordinate
     * @return string
     */
    public function setEndCoordinate($_endCoordinate)
    {
        return ($this->EndCoordinate = $_endCoordinate);
    }
    /**
     * Method called when an object has been exported with var_export() functions
     * It allows to return an object instantiated with the values
     * @see PCMWSWsdlClass::__set_state()
     * @uses PCMWSWsdlClass::__set_state()
     * @param array $_array the exported values
     * @return PCMWSStructDetailReportLine
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
