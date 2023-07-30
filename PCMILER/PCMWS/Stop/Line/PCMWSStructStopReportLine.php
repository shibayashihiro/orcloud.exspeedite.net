<?php
/**
 * File for class PCMWSStructStopReportLine
 * @package PCMWS
 * @subpackage Structs
 * @author WsdlToPhp Team <contact@wsdltophp.com>
 * @version 20150429-01
 * @date 2016-08-25
 */
/**
 * This class stands for PCMWSStructStopReportLine originally named StopReportLine
 * Meta informations extracted from the WSDL
 * - from schema : {@link http://pcmiler.alk.com/APIs/SOAP/v1.0/Service.svc?xsd=xsd0}
 * @package PCMWS
 * @subpackage Structs
 * @author WsdlToPhp Team <contact@wsdltophp.com>
 * @version 20150429-01
 * @date 2016-08-25
 */
class PCMWSStructStopReportLine extends PCMWSWsdlClass
{
    /**
     * The Stop
     * Meta informations extracted from the WSDL
     * - minOccurs : 0
     * - nillable : true
     * @var PCMWSStructGeocodeOutputLocation
     */
    public $Stop;
    /**
     * The LMiles
     * Meta informations extracted from the WSDL
     * - minOccurs : 0
     * - nillable : true
     * @var string
     */
    public $LMiles;
    /**
     * The TMiles
     * Meta informations extracted from the WSDL
     * - minOccurs : 0
     * - nillable : true
     * @var string
     */
    public $TMiles;
    /**
     * The LCostMile
     * Meta informations extracted from the WSDL
     * - minOccurs : 0
     * - nillable : true
     * @var string
     */
    public $LCostMile;
    /**
     * The TCostMile
     * Meta informations extracted from the WSDL
     * - minOccurs : 0
     * - nillable : true
     * @var string
     */
    public $TCostMile;
    /**
     * The LHours
     * Meta informations extracted from the WSDL
     * - minOccurs : 0
     * - nillable : true
     * @var string
     */
    public $LHours;
    /**
     * The THours
     * Meta informations extracted from the WSDL
     * - minOccurs : 0
     * - nillable : true
     * @var string
     */
    public $THours;
    /**
     * The LTolls
     * Meta informations extracted from the WSDL
     * - minOccurs : 0
     * - nillable : true
     * @var string
     */
    public $LTolls;
    /**
     * The TTolls
     * Meta informations extracted from the WSDL
     * - minOccurs : 0
     * - nillable : true
     * @var string
     */
    public $TTolls;
    /**
     * The LEstghg
     * Meta informations extracted from the WSDL
     * - minOccurs : 0
     * - nillable : true
     * @var string
     */
    public $LEstghg;
    /**
     * The TEstghg
     * Meta informations extracted from the WSDL
     * - minOccurs : 0
     * - nillable : true
     * @var string
     */
    public $TEstghg;
    /**
     * The EtaEtd
     * Meta informations extracted from the WSDL
     * - minOccurs : 0
     * - nillable : true
     * @var string
     */
    public $EtaEtd;
    /**
     * Constructor method for StopReportLine
     * @see parent::__construct()
     * @param PCMWSStructGeocodeOutputLocation $_stop
     * @param string $_lMiles
     * @param string $_tMiles
     * @param string $_lCostMile
     * @param string $_tCostMile
     * @param string $_lHours
     * @param string $_tHours
     * @param string $_lTolls
     * @param string $_tTolls
     * @param string $_lEstghg
     * @param string $_tEstghg
     * @param string $_etaEtd
     * @return PCMWSStructStopReportLine
     */
    public function __construct($_stop = NULL,$_lMiles = NULL,$_tMiles = NULL,$_lCostMile = NULL,$_tCostMile = NULL,$_lHours = NULL,$_tHours = NULL,$_lTolls = NULL,$_tTolls = NULL,$_lEstghg = NULL,$_tEstghg = NULL,$_etaEtd = NULL)
    {
        parent::__construct(array('Stop'=>$_stop,'LMiles'=>$_lMiles,'TMiles'=>$_tMiles,'LCostMile'=>$_lCostMile,'TCostMile'=>$_tCostMile,'LHours'=>$_lHours,'THours'=>$_tHours,'LTolls'=>$_lTolls,'TTolls'=>$_tTolls,'LEstghg'=>$_lEstghg,'TEstghg'=>$_tEstghg,'EtaEtd'=>$_etaEtd),false);
    }
    /**
     * Get Stop value
     * @return PCMWSStructGeocodeOutputLocation|null
     */
    public function getStop()
    {
        return $this->Stop;
    }
    /**
     * Set Stop value
     * @param PCMWSStructGeocodeOutputLocation $_stop the Stop
     * @return PCMWSStructGeocodeOutputLocation
     */
    public function setStop($_stop)
    {
        return ($this->Stop = $_stop);
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
     * Get LCostMile value
     * @return string|null
     */
    public function getLCostMile()
    {
        return $this->LCostMile;
    }
    /**
     * Set LCostMile value
     * @param string $_lCostMile the LCostMile
     * @return string
     */
    public function setLCostMile($_lCostMile)
    {
        return ($this->LCostMile = $_lCostMile);
    }
    /**
     * Get TCostMile value
     * @return string|null
     */
    public function getTCostMile()
    {
        return $this->TCostMile;
    }
    /**
     * Set TCostMile value
     * @param string $_tCostMile the TCostMile
     * @return string
     */
    public function setTCostMile($_tCostMile)
    {
        return ($this->TCostMile = $_tCostMile);
    }
    /**
     * Get LHours value
     * @return string|null
     */
    public function getLHours()
    {
        return $this->LHours;
    }
    /**
     * Set LHours value
     * @param string $_lHours the LHours
     * @return string
     */
    public function setLHours($_lHours)
    {
        return ($this->LHours = $_lHours);
    }
    /**
     * Get THours value
     * @return string|null
     */
    public function getTHours()
    {
        return $this->THours;
    }
    /**
     * Set THours value
     * @param string $_tHours the THours
     * @return string
     */
    public function setTHours($_tHours)
    {
        return ($this->THours = $_tHours);
    }
    /**
     * Get LTolls value
     * @return string|null
     */
    public function getLTolls()
    {
        return $this->LTolls;
    }
    /**
     * Set LTolls value
     * @param string $_lTolls the LTolls
     * @return string
     */
    public function setLTolls($_lTolls)
    {
        return ($this->LTolls = $_lTolls);
    }
    /**
     * Get TTolls value
     * @return string|null
     */
    public function getTTolls()
    {
        return $this->TTolls;
    }
    /**
     * Set TTolls value
     * @param string $_tTolls the TTolls
     * @return string
     */
    public function setTTolls($_tTolls)
    {
        return ($this->TTolls = $_tTolls);
    }
    /**
     * Get LEstghg value
     * @return string|null
     */
    public function getLEstghg()
    {
        return $this->LEstghg;
    }
    /**
     * Set LEstghg value
     * @param string $_lEstghg the LEstghg
     * @return string
     */
    public function setLEstghg($_lEstghg)
    {
        return ($this->LEstghg = $_lEstghg);
    }
    /**
     * Get TEstghg value
     * @return string|null
     */
    public function getTEstghg()
    {
        return $this->TEstghg;
    }
    /**
     * Set TEstghg value
     * @param string $_tEstghg the TEstghg
     * @return string
     */
    public function setTEstghg($_tEstghg)
    {
        return ($this->TEstghg = $_tEstghg);
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
     * Method called when an object has been exported with var_export() functions
     * It allows to return an object instantiated with the values
     * @see PCMWSWsdlClass::__set_state()
     * @uses PCMWSWsdlClass::__set_state()
     * @param array $_array the exported values
     * @return PCMWSStructStopReportLine
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
