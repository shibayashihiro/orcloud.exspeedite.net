<?php
/**
 * File for class PCMWSStructFuelReportSummary
 * @package PCMWS
 * @subpackage Structs
 * @author WsdlToPhp Team <contact@wsdltophp.com>
 * @version 20150429-01
 * @date 2016-08-25
 */
/**
 * This class stands for PCMWSStructFuelReportSummary originally named FuelReportSummary
 * Meta informations extracted from the WSDL
 * - from schema : {@link http://pcmiler.alk.com/APIs/SOAP/v1.0/Service.svc?xsd=xsd0}
 * @package PCMWS
 * @subpackage Structs
 * @author WsdlToPhp Team <contact@wsdltophp.com>
 * @version 20150429-01
 * @date 2016-08-25
 */
class PCMWSStructFuelReportSummary extends PCMWSWsdlClass
{
    /**
     * The OptFuel
     * Meta informations extracted from the WSDL
     * - minOccurs : 0
     * - nillable : true
     * @var string
     */
    public $OptFuel;
    /**
     * The TCost
     * Meta informations extracted from the WSDL
     * - minOccurs : 0
     * - nillable : true
     * @var string
     */
    public $TCost;
    /**
     * The ACostG
     * Meta informations extracted from the WSDL
     * - minOccurs : 0
     * - nillable : true
     * @var string
     */
    public $ACostG;
    /**
     * The ACostM
     * Meta informations extracted from the WSDL
     * - minOccurs : 0
     * - nillable : true
     * @var string
     */
    public $ACostM;
    /**
     * The ECost
     * Meta informations extracted from the WSDL
     * - minOccurs : 0
     * - nillable : true
     * @var string
     */
    public $ECost;
    /**
     * The ECostG
     * Meta informations extracted from the WSDL
     * - minOccurs : 0
     * - nillable : true
     * @var string
     */
    public $ECostG;
    /**
     * The ECostM
     * Meta informations extracted from the WSDL
     * - minOccurs : 0
     * - nillable : true
     * @var string
     */
    public $ECostM;
    /**
     * The TSave
     * Meta informations extracted from the WSDL
     * - minOccurs : 0
     * - nillable : true
     * @var string
     */
    public $TSave;
    /**
     * The SaveG
     * Meta informations extracted from the WSDL
     * - minOccurs : 0
     * - nillable : true
     * @var string
     */
    public $SaveG;
    /**
     * The SaveM
     * Meta informations extracted from the WSDL
     * - minOccurs : 0
     * - nillable : true
     * @var string
     */
    public $SaveM;
    /**
     * The RtAvg
     * Meta informations extracted from the WSDL
     * - minOccurs : 0
     * - nillable : true
     * @var string
     */
    public $RtAvg;
    /**
     * The RtMax
     * Meta informations extracted from the WSDL
     * - minOccurs : 0
     * - nillable : true
     * @var string
     */
    public $RtMax;
    /**
     * The RtMin
     * Meta informations extracted from the WSDL
     * - minOccurs : 0
     * - nillable : true
     * @var string
     */
    public $RtMin;
    /**
     * The ReAvg
     * Meta informations extracted from the WSDL
     * - minOccurs : 0
     * - nillable : true
     * @var string
     */
    public $ReAvg;
    /**
     * The ReMax
     * Meta informations extracted from the WSDL
     * - minOccurs : 0
     * - nillable : true
     * @var string
     */
    public $ReMax;
    /**
     * The ReMin
     * Meta informations extracted from the WSDL
     * - minOccurs : 0
     * - nillable : true
     * @var string
     */
    public $ReMin;
    /**
     * Constructor method for FuelReportSummary
     * @see parent::__construct()
     * @param string $_optFuel
     * @param string $_tCost
     * @param string $_aCostG
     * @param string $_aCostM
     * @param string $_eCost
     * @param string $_eCostG
     * @param string $_eCostM
     * @param string $_tSave
     * @param string $_saveG
     * @param string $_saveM
     * @param string $_rtAvg
     * @param string $_rtMax
     * @param string $_rtMin
     * @param string $_reAvg
     * @param string $_reMax
     * @param string $_reMin
     * @return PCMWSStructFuelReportSummary
     */
    public function __construct($_optFuel = NULL,$_tCost = NULL,$_aCostG = NULL,$_aCostM = NULL,$_eCost = NULL,$_eCostG = NULL,$_eCostM = NULL,$_tSave = NULL,$_saveG = NULL,$_saveM = NULL,$_rtAvg = NULL,$_rtMax = NULL,$_rtMin = NULL,$_reAvg = NULL,$_reMax = NULL,$_reMin = NULL)
    {
        parent::__construct(array('OptFuel'=>$_optFuel,'TCost'=>$_tCost,'ACostG'=>$_aCostG,'ACostM'=>$_aCostM,'ECost'=>$_eCost,'ECostG'=>$_eCostG,'ECostM'=>$_eCostM,'TSave'=>$_tSave,'SaveG'=>$_saveG,'SaveM'=>$_saveM,'RtAvg'=>$_rtAvg,'RtMax'=>$_rtMax,'RtMin'=>$_rtMin,'ReAvg'=>$_reAvg,'ReMax'=>$_reMax,'ReMin'=>$_reMin),false);
    }
    /**
     * Get OptFuel value
     * @return string|null
     */
    public function getOptFuel()
    {
        return $this->OptFuel;
    }
    /**
     * Set OptFuel value
     * @param string $_optFuel the OptFuel
     * @return string
     */
    public function setOptFuel($_optFuel)
    {
        return ($this->OptFuel = $_optFuel);
    }
    /**
     * Get TCost value
     * @return string|null
     */
    public function getTCost()
    {
        return $this->TCost;
    }
    /**
     * Set TCost value
     * @param string $_tCost the TCost
     * @return string
     */
    public function setTCost($_tCost)
    {
        return ($this->TCost = $_tCost);
    }
    /**
     * Get ACostG value
     * @return string|null
     */
    public function getACostG()
    {
        return $this->ACostG;
    }
    /**
     * Set ACostG value
     * @param string $_aCostG the ACostG
     * @return string
     */
    public function setACostG($_aCostG)
    {
        return ($this->ACostG = $_aCostG);
    }
    /**
     * Get ACostM value
     * @return string|null
     */
    public function getACostM()
    {
        return $this->ACostM;
    }
    /**
     * Set ACostM value
     * @param string $_aCostM the ACostM
     * @return string
     */
    public function setACostM($_aCostM)
    {
        return ($this->ACostM = $_aCostM);
    }
    /**
     * Get ECost value
     * @return string|null
     */
    public function getECost()
    {
        return $this->ECost;
    }
    /**
     * Set ECost value
     * @param string $_eCost the ECost
     * @return string
     */
    public function setECost($_eCost)
    {
        return ($this->ECost = $_eCost);
    }
    /**
     * Get ECostG value
     * @return string|null
     */
    public function getECostG()
    {
        return $this->ECostG;
    }
    /**
     * Set ECostG value
     * @param string $_eCostG the ECostG
     * @return string
     */
    public function setECostG($_eCostG)
    {
        return ($this->ECostG = $_eCostG);
    }
    /**
     * Get ECostM value
     * @return string|null
     */
    public function getECostM()
    {
        return $this->ECostM;
    }
    /**
     * Set ECostM value
     * @param string $_eCostM the ECostM
     * @return string
     */
    public function setECostM($_eCostM)
    {
        return ($this->ECostM = $_eCostM);
    }
    /**
     * Get TSave value
     * @return string|null
     */
    public function getTSave()
    {
        return $this->TSave;
    }
    /**
     * Set TSave value
     * @param string $_tSave the TSave
     * @return string
     */
    public function setTSave($_tSave)
    {
        return ($this->TSave = $_tSave);
    }
    /**
     * Get SaveG value
     * @return string|null
     */
    public function getSaveG()
    {
        return $this->SaveG;
    }
    /**
     * Set SaveG value
     * @param string $_saveG the SaveG
     * @return string
     */
    public function setSaveG($_saveG)
    {
        return ($this->SaveG = $_saveG);
    }
    /**
     * Get SaveM value
     * @return string|null
     */
    public function getSaveM()
    {
        return $this->SaveM;
    }
    /**
     * Set SaveM value
     * @param string $_saveM the SaveM
     * @return string
     */
    public function setSaveM($_saveM)
    {
        return ($this->SaveM = $_saveM);
    }
    /**
     * Get RtAvg value
     * @return string|null
     */
    public function getRtAvg()
    {
        return $this->RtAvg;
    }
    /**
     * Set RtAvg value
     * @param string $_rtAvg the RtAvg
     * @return string
     */
    public function setRtAvg($_rtAvg)
    {
        return ($this->RtAvg = $_rtAvg);
    }
    /**
     * Get RtMax value
     * @return string|null
     */
    public function getRtMax()
    {
        return $this->RtMax;
    }
    /**
     * Set RtMax value
     * @param string $_rtMax the RtMax
     * @return string
     */
    public function setRtMax($_rtMax)
    {
        return ($this->RtMax = $_rtMax);
    }
    /**
     * Get RtMin value
     * @return string|null
     */
    public function getRtMin()
    {
        return $this->RtMin;
    }
    /**
     * Set RtMin value
     * @param string $_rtMin the RtMin
     * @return string
     */
    public function setRtMin($_rtMin)
    {
        return ($this->RtMin = $_rtMin);
    }
    /**
     * Get ReAvg value
     * @return string|null
     */
    public function getReAvg()
    {
        return $this->ReAvg;
    }
    /**
     * Set ReAvg value
     * @param string $_reAvg the ReAvg
     * @return string
     */
    public function setReAvg($_reAvg)
    {
        return ($this->ReAvg = $_reAvg);
    }
    /**
     * Get ReMax value
     * @return string|null
     */
    public function getReMax()
    {
        return $this->ReMax;
    }
    /**
     * Set ReMax value
     * @param string $_reMax the ReMax
     * @return string
     */
    public function setReMax($_reMax)
    {
        return ($this->ReMax = $_reMax);
    }
    /**
     * Get ReMin value
     * @return string|null
     */
    public function getReMin()
    {
        return $this->ReMin;
    }
    /**
     * Set ReMin value
     * @param string $_reMin the ReMin
     * @return string
     */
    public function setReMin($_reMin)
    {
        return ($this->ReMin = $_reMin);
    }
    /**
     * Method called when an object has been exported with var_export() functions
     * It allows to return an object instantiated with the values
     * @see PCMWSWsdlClass::__set_state()
     * @uses PCMWSWsdlClass::__set_state()
     * @param array $_array the exported values
     * @return PCMWSStructFuelReportSummary
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
