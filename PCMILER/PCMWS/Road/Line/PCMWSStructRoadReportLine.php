<?php
/**
 * File for class PCMWSStructRoadReportLine
 * @package PCMWS
 * @subpackage Structs
 * @author WsdlToPhp Team <contact@wsdltophp.com>
 * @version 20150429-01
 * @date 2016-08-25
 */
/**
 * This class stands for PCMWSStructRoadReportLine originally named RoadReportLine
 * Meta informations extracted from the WSDL
 * - from schema : {@link http://pcmiler.alk.com/APIs/SOAP/v1.0/Service.svc?xsd=xsd0}
 * @package PCMWS
 * @subpackage Structs
 * @author WsdlToPhp Team <contact@wsdltophp.com>
 * @version 20150429-01
 * @date 2016-08-25
 */
class PCMWSStructRoadReportLine extends PCMWSWsdlClass
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
     * The InterSt
     * Meta informations extracted from the WSDL
     * - minOccurs : 0
     * - nillable : true
     * @var string
     */
    public $InterSt;
    /**
     * The InterstNoRamp
     * Meta informations extracted from the WSDL
     * - minOccurs : 0
     * - nillable : true
     * @var string
     */
    public $InterstNoRamp;
    /**
     * The Divide
     * Meta informations extracted from the WSDL
     * - minOccurs : 0
     * - nillable : true
     * @var string
     */
    public $Divide;
    /**
     * The Prime
     * Meta informations extracted from the WSDL
     * - minOccurs : 0
     * - nillable : true
     * @var string
     */
    public $Prime;
    /**
     * The Second
     * Meta informations extracted from the WSDL
     * - minOccurs : 0
     * - nillable : true
     * @var string
     */
    public $Second;
    /**
     * The Ferry
     * Meta informations extracted from the WSDL
     * - minOccurs : 0
     * - nillable : true
     * @var string
     */
    public $Ferry;
    /**
     * The Ramp
     * Meta informations extracted from the WSDL
     * - minOccurs : 0
     * - nillable : true
     * @var string
     */
    public $Ramp;
    /**
     * The Local
     * Meta informations extracted from the WSDL
     * - minOccurs : 0
     * - nillable : true
     * @var string
     */
    public $Local;
    /**
     * The Toll
     * Meta informations extracted from the WSDL
     * - minOccurs : 0
     * - nillable : true
     * @var string
     */
    public $Toll;
    /**
     * The Energy
     * Meta informations extracted from the WSDL
     * - minOccurs : 0
     * - nillable : true
     * @var string
     */
    public $Energy;
    /**
     * Constructor method for RoadReportLine
     * @see parent::__construct()
     * @param PCMWSStructGeocodeOutputLocation $_stop
     * @param string $_lMiles
     * @param string $_interSt
     * @param string $_interstNoRamp
     * @param string $_divide
     * @param string $_prime
     * @param string $_second
     * @param string $_ferry
     * @param string $_ramp
     * @param string $_local
     * @param string $_toll
     * @param string $_energy
     * @return PCMWSStructRoadReportLine
     */
    public function __construct($_stop = NULL,$_lMiles = NULL,$_interSt = NULL,$_interstNoRamp = NULL,$_divide = NULL,$_prime = NULL,$_second = NULL,$_ferry = NULL,$_ramp = NULL,$_local = NULL,$_toll = NULL,$_energy = NULL)
    {
        parent::__construct(array('Stop'=>$_stop,'LMiles'=>$_lMiles,'InterSt'=>$_interSt,'InterstNoRamp'=>$_interstNoRamp,'Divide'=>$_divide,'Prime'=>$_prime,'Second'=>$_second,'Ferry'=>$_ferry,'Ramp'=>$_ramp,'Local'=>$_local,'Toll'=>$_toll,'Energy'=>$_energy),false);
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
     * Get InterSt value
     * @return string|null
     */
    public function getInterSt()
    {
        return $this->InterSt;
    }
    /**
     * Set InterSt value
     * @param string $_interSt the InterSt
     * @return string
     */
    public function setInterSt($_interSt)
    {
        return ($this->InterSt = $_interSt);
    }
    /**
     * Get InterstNoRamp value
     * @return string|null
     */
    public function getInterstNoRamp()
    {
        return $this->InterstNoRamp;
    }
    /**
     * Set InterstNoRamp value
     * @param string $_interstNoRamp the InterstNoRamp
     * @return string
     */
    public function setInterstNoRamp($_interstNoRamp)
    {
        return ($this->InterstNoRamp = $_interstNoRamp);
    }
    /**
     * Get Divide value
     * @return string|null
     */
    public function getDivide()
    {
        return $this->Divide;
    }
    /**
     * Set Divide value
     * @param string $_divide the Divide
     * @return string
     */
    public function setDivide($_divide)
    {
        return ($this->Divide = $_divide);
    }
    /**
     * Get Prime value
     * @return string|null
     */
    public function getPrime()
    {
        return $this->Prime;
    }
    /**
     * Set Prime value
     * @param string $_prime the Prime
     * @return string
     */
    public function setPrime($_prime)
    {
        return ($this->Prime = $_prime);
    }
    /**
     * Get Second value
     * @return string|null
     */
    public function getSecond()
    {
        return $this->Second;
    }
    /**
     * Set Second value
     * @param string $_second the Second
     * @return string
     */
    public function setSecond($_second)
    {
        return ($this->Second = $_second);
    }
    /**
     * Get Ferry value
     * @return string|null
     */
    public function getFerry()
    {
        return $this->Ferry;
    }
    /**
     * Set Ferry value
     * @param string $_ferry the Ferry
     * @return string
     */
    public function setFerry($_ferry)
    {
        return ($this->Ferry = $_ferry);
    }
    /**
     * Get Ramp value
     * @return string|null
     */
    public function getRamp()
    {
        return $this->Ramp;
    }
    /**
     * Set Ramp value
     * @param string $_ramp the Ramp
     * @return string
     */
    public function setRamp($_ramp)
    {
        return ($this->Ramp = $_ramp);
    }
    /**
     * Get Local value
     * @return string|null
     */
    public function getLocal()
    {
        return $this->Local;
    }
    /**
     * Set Local value
     * @param string $_local the Local
     * @return string
     */
    public function setLocal($_local)
    {
        return ($this->Local = $_local);
    }
    /**
     * Get Toll value
     * @return string|null
     */
    public function getToll()
    {
        return $this->Toll;
    }
    /**
     * Set Toll value
     * @param string $_toll the Toll
     * @return string
     */
    public function setToll($_toll)
    {
        return ($this->Toll = $_toll);
    }
    /**
     * Get Energy value
     * @return string|null
     */
    public function getEnergy()
    {
        return $this->Energy;
    }
    /**
     * Set Energy value
     * @param string $_energy the Energy
     * @return string
     */
    public function setEnergy($_energy)
    {
        return ($this->Energy = $_energy);
    }
    /**
     * Method called when an object has been exported with var_export() functions
     * It allows to return an object instantiated with the values
     * @see PCMWSWsdlClass::__set_state()
     * @uses PCMWSWsdlClass::__set_state()
     * @param array $_array the exported values
     * @return PCMWSStructRoadReportLine
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
