<?php
/**
 * File for class PCMWSStructTimeCosts
 * @package PCMWS
 * @subpackage Structs
 * @author WsdlToPhp Team <contact@wsdltophp.com>
 * @version 20150429-01
 * @date 2016-08-25
 */
/**
 * This class stands for PCMWSStructTimeCosts originally named TimeCosts
 * Meta informations extracted from the WSDL
 * - from schema : {@link http://pcmiler.alk.com/APIs/SOAP/v1.0/Service.svc?xsd=xsd0}
 * @package PCMWS
 * @subpackage Structs
 * @author WsdlToPhp Team <contact@wsdltophp.com>
 * @version 20150429-01
 * @date 2016-08-25
 */
class PCMWSStructTimeCosts extends PCMWSWsdlClass
{
    /**
     * The BreakInterval
     * Meta informations extracted from the WSDL
     * - minOccurs : 0
     * @var float
     */
    public $BreakInterval;
    /**
     * The BreakLength
     * Meta informations extracted from the WSDL
     * - minOccurs : 0
     * @var float
     */
    public $BreakLength;
    /**
     * The BorderWait
     * Meta informations extracted from the WSDL
     * - minOccurs : 0
     * @var float
     */
    public $BorderWait;
    /**
     * The DepartTime
     * Meta informations extracted from the WSDL
     * - minOccurs : 0
     * - nillable : true
     * @var PCMWSStructTimeOfDay
     */
    public $DepartTime;
    /**
     * The RemainingHoursOfService
     * Meta informations extracted from the WSDL
     * - minOccurs : 0
     * @var float
     */
    public $RemainingHoursOfService;
    /**
     * Constructor method for TimeCosts
     * @see parent::__construct()
     * @param float $_breakInterval
     * @param float $_breakLength
     * @param float $_borderWait
     * @param PCMWSStructTimeOfDay $_departTime
     * @param float $_remainingHoursOfService
     * @return PCMWSStructTimeCosts
     */
    public function __construct($_breakInterval = NULL,$_breakLength = NULL,$_borderWait = NULL,$_departTime = NULL,$_remainingHoursOfService = NULL)
    {
        parent::__construct(array('BreakInterval'=>$_breakInterval,'BreakLength'=>$_breakLength,'BorderWait'=>$_borderWait,'DepartTime'=>$_departTime,'RemainingHoursOfService'=>$_remainingHoursOfService),false);
    }
    /**
     * Get BreakInterval value
     * @return float|null
     */
    public function getBreakInterval()
    {
        return $this->BreakInterval;
    }
    /**
     * Set BreakInterval value
     * @param float $_breakInterval the BreakInterval
     * @return float
     */
    public function setBreakInterval($_breakInterval)
    {
        return ($this->BreakInterval = $_breakInterval);
    }
    /**
     * Get BreakLength value
     * @return float|null
     */
    public function getBreakLength()
    {
        return $this->BreakLength;
    }
    /**
     * Set BreakLength value
     * @param float $_breakLength the BreakLength
     * @return float
     */
    public function setBreakLength($_breakLength)
    {
        return ($this->BreakLength = $_breakLength);
    }
    /**
     * Get BorderWait value
     * @return float|null
     */
    public function getBorderWait()
    {
        return $this->BorderWait;
    }
    /**
     * Set BorderWait value
     * @param float $_borderWait the BorderWait
     * @return float
     */
    public function setBorderWait($_borderWait)
    {
        return ($this->BorderWait = $_borderWait);
    }
    /**
     * Get DepartTime value
     * @return PCMWSStructTimeOfDay|null
     */
    public function getDepartTime()
    {
        return $this->DepartTime;
    }
    /**
     * Set DepartTime value
     * @param PCMWSStructTimeOfDay $_departTime the DepartTime
     * @return PCMWSStructTimeOfDay
     */
    public function setDepartTime($_departTime)
    {
        return ($this->DepartTime = $_departTime);
    }
    /**
     * Get RemainingHoursOfService value
     * @return float|null
     */
    public function getRemainingHoursOfService()
    {
        return $this->RemainingHoursOfService;
    }
    /**
     * Set RemainingHoursOfService value
     * @param float $_remainingHoursOfService the RemainingHoursOfService
     * @return float
     */
    public function setRemainingHoursOfService($_remainingHoursOfService)
    {
        return ($this->RemainingHoursOfService = $_remainingHoursOfService);
    }
    /**
     * Method called when an object has been exported with var_export() functions
     * It allows to return an object instantiated with the values
     * @see PCMWSWsdlClass::__set_state()
     * @uses PCMWSWsdlClass::__set_state()
     * @param array $_array the exported values
     * @return PCMWSStructTimeCosts
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
