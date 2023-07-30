<?php
/**
 * File for class PCMWSStructStopCosts
 * @package PCMWS
 * @subpackage Structs
 * @author WsdlToPhp Team <contact@wsdltophp.com>
 * @version 20150429-01
 * @date 2016-08-25
 */
/**
 * This class stands for PCMWSStructStopCosts originally named StopCosts
 * Meta informations extracted from the WSDL
 * - from schema : {@link http://pcmiler.alk.com/APIs/SOAP/v1.0/Service.svc?xsd=xsd0}
 * @package PCMWS
 * @subpackage Structs
 * @author WsdlToPhp Team <contact@wsdltophp.com>
 * @version 20150429-01
 * @date 2016-08-25
 */
class PCMWSStructStopCosts extends PCMWSWsdlClass
{
    /**
     * The CostOfStop
     * Meta informations extracted from the WSDL
     * - minOccurs : 0
     * @var double
     */
    public $CostOfStop;
    /**
     * The HoursPerStop
     * Meta informations extracted from the WSDL
     * - minOccurs : 0
     * @var double
     */
    public $HoursPerStop;
    /**
     * The Loaded
     * Meta informations extracted from the WSDL
     * - minOccurs : 0
     * @var boolean
     */
    public $Loaded;
    /**
     * The OnDuty
     * Meta informations extracted from the WSDL
     * - minOccurs : 0
     * @var boolean
     */
    public $OnDuty;
    /**
     * The UseOrigin
     * Meta informations extracted from the WSDL
     * - minOccurs : 0
     * @var boolean
     */
    public $UseOrigin;
    /**
     * Constructor method for StopCosts
     * @see parent::__construct()
     * @param double $_costOfStop
     * @param double $_hoursPerStop
     * @param boolean $_loaded
     * @param boolean $_onDuty
     * @param boolean $_useOrigin
     * @return PCMWSStructStopCosts
     */
    public function __construct($_costOfStop = NULL,$_hoursPerStop = NULL,$_loaded = NULL,$_onDuty = NULL,$_useOrigin = NULL)
    {
        parent::__construct(array('CostOfStop'=>$_costOfStop,'HoursPerStop'=>$_hoursPerStop,'Loaded'=>$_loaded,'OnDuty'=>$_onDuty,'UseOrigin'=>$_useOrigin),false);
    }
    /**
     * Get CostOfStop value
     * @return double|null
     */
    public function getCostOfStop()
    {
        return $this->CostOfStop;
    }
    /**
     * Set CostOfStop value
     * @param double $_costOfStop the CostOfStop
     * @return double
     */
    public function setCostOfStop($_costOfStop)
    {
        return ($this->CostOfStop = $_costOfStop);
    }
    /**
     * Get HoursPerStop value
     * @return double|null
     */
    public function getHoursPerStop()
    {
        return $this->HoursPerStop;
    }
    /**
     * Set HoursPerStop value
     * @param double $_hoursPerStop the HoursPerStop
     * @return double
     */
    public function setHoursPerStop($_hoursPerStop)
    {
        return ($this->HoursPerStop = $_hoursPerStop);
    }
    /**
     * Get Loaded value
     * @return boolean|null
     */
    public function getLoaded()
    {
        return $this->Loaded;
    }
    /**
     * Set Loaded value
     * @param boolean $_loaded the Loaded
     * @return boolean
     */
    public function setLoaded($_loaded)
    {
        return ($this->Loaded = $_loaded);
    }
    /**
     * Get OnDuty value
     * @return boolean|null
     */
    public function getOnDuty()
    {
        return $this->OnDuty;
    }
    /**
     * Set OnDuty value
     * @param boolean $_onDuty the OnDuty
     * @return boolean
     */
    public function setOnDuty($_onDuty)
    {
        return ($this->OnDuty = $_onDuty);
    }
    /**
     * Get UseOrigin value
     * @return boolean|null
     */
    public function getUseOrigin()
    {
        return $this->UseOrigin;
    }
    /**
     * Set UseOrigin value
     * @param boolean $_useOrigin the UseOrigin
     * @return boolean
     */
    public function setUseOrigin($_useOrigin)
    {
        return ($this->UseOrigin = $_useOrigin);
    }
    /**
     * Method called when an object has been exported with var_export() functions
     * It allows to return an object instantiated with the values
     * @see PCMWSWsdlClass::__set_state()
     * @uses PCMWSWsdlClass::__set_state()
     * @param array $_array the exported values
     * @return PCMWSStructStopCosts
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
