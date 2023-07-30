<?php
/**
 * File for class PCMWSStructReportOptions
 * @package PCMWS
 * @subpackage Structs
 * @author WsdlToPhp Team <contact@wsdltophp.com>
 * @version 20150429-01
 * @date 2016-08-25
 */
/**
 * This class stands for PCMWSStructReportOptions originally named ReportOptions
 * Meta informations extracted from the WSDL
 * - from schema : {@link http://pcmiler.alk.com/APIs/SOAP/v1.0/Service.svc?xsd=xsd0}
 * @package PCMWS
 * @subpackage Structs
 * @author WsdlToPhp Team <contact@wsdltophp.com>
 * @version 20150429-01
 * @date 2016-08-25
 */
class PCMWSStructReportOptions extends PCMWSStructSharedOptions
{
    /**
     * The ExchangeRate
     * Meta informations extracted from the WSDL
     * - minOccurs : 0
     * @var double
     */
    public $ExchangeRate;
    /**
     * The FuelUnits
     * Meta informations extracted from the WSDL
     * - minOccurs : 0
     * @var PCMWSEnumVolumeUnits
     */
    public $FuelUnits;
    /**
     * The IncludeFerryDistance
     * Meta informations extracted from the WSDL
     * - minOccurs : 0
     * - nillable : true
     * @var boolean
     */
    public $IncludeFerryDistance;
    /**
     * The Language
     * Meta informations extracted from the WSDL
     * - minOccurs : 0
     * @var PCMWSEnumLanguageType
     */
    public $Language;
    /**
     * The RouteCosts
     * Meta informations extracted from the WSDL
     * - minOccurs : 0
     * - nillable : true
     * @var PCMWSStructRouteCosts
     */
    public $RouteCosts;
    /**
     * The TimeCosts
     * Meta informations extracted from the WSDL
     * - minOccurs : 0
     * - nillable : true
     * @var PCMWSStructTimeCosts
     */
    public $TimeCosts;
    /**
     * The TollCurrency
     * Meta informations extracted from the WSDL
     * - minOccurs : 0
     * @var PCMWSEnumCurrency
     */
    public $TollCurrency;
    /**
     * The TollDiscount
     * Meta informations extracted from the WSDL
     * - minOccurs : 0
     * - nillable : true
     * @var string
     */
    public $TollDiscount;
    /**
     * The UseCustomRoadSpeeds
     * Meta informations extracted from the WSDL
     * - minOccurs : 0
     * @var boolean
     */
    public $UseCustomRoadSpeeds;
    /**
     * The UseTollData
     * Meta informations extracted from the WSDL
     * - minOccurs : 0
     * - nillable : true
     * @var boolean
     */
    public $UseTollData;
    /**
     * Constructor method for ReportOptions
     * @see parent::__construct()
     * @param double $_exchangeRate
     * @param PCMWSEnumVolumeUnits $_fuelUnits
     * @param boolean $_includeFerryDistance
     * @param PCMWSEnumLanguageType $_language
     * @param PCMWSStructRouteCosts $_routeCosts
     * @param PCMWSStructTimeCosts $_timeCosts
     * @param PCMWSEnumCurrency $_tollCurrency
     * @param string $_tollDiscount
     * @param boolean $_useCustomRoadSpeeds
     * @param boolean $_useTollData
     * @return PCMWSStructReportOptions
     */
    public function __construct($_exchangeRate = NULL,$_fuelUnits = NULL,$_includeFerryDistance = NULL,$_language = NULL,$_routeCosts = NULL,$_timeCosts = NULL,$_tollCurrency = NULL,$_tollDiscount = NULL,$_useCustomRoadSpeeds = NULL,$_useTollData = NULL)
    {
        PCMWSWsdlClass::__construct(array('ExchangeRate'=>$_exchangeRate,'FuelUnits'=>$_fuelUnits,'IncludeFerryDistance'=>$_includeFerryDistance,'Language'=>$_language,'RouteCosts'=>$_routeCosts,'TimeCosts'=>$_timeCosts,'TollCurrency'=>$_tollCurrency,'TollDiscount'=>$_tollDiscount,'UseCustomRoadSpeeds'=>$_useCustomRoadSpeeds,'UseTollData'=>$_useTollData),false);
    }
    /**
     * Get ExchangeRate value
     * @return double|null
     */
    public function getExchangeRate()
    {
        return $this->ExchangeRate;
    }
    /**
     * Set ExchangeRate value
     * @param double $_exchangeRate the ExchangeRate
     * @return double
     */
    public function setExchangeRate($_exchangeRate)
    {
        return ($this->ExchangeRate = $_exchangeRate);
    }
    /**
     * Get FuelUnits value
     * @return PCMWSEnumVolumeUnits|null
     */
    public function getFuelUnits()
    {
        return $this->FuelUnits;
    }
    /**
     * Set FuelUnits value
     * @uses PCMWSEnumVolumeUnits::valueIsValid()
     * @param PCMWSEnumVolumeUnits $_fuelUnits the FuelUnits
     * @return PCMWSEnumVolumeUnits
     */
    public function setFuelUnits($_fuelUnits)
    {
        if(!PCMWSEnumVolumeUnits::valueIsValid($_fuelUnits))
        {
            return false;
        }
        return ($this->FuelUnits = $_fuelUnits);
    }
    /**
     * Get IncludeFerryDistance value
     * @return boolean|null
     */
    public function getIncludeFerryDistance()
    {
        return $this->IncludeFerryDistance;
    }
    /**
     * Set IncludeFerryDistance value
     * @param boolean $_includeFerryDistance the IncludeFerryDistance
     * @return boolean
     */
    public function setIncludeFerryDistance($_includeFerryDistance)
    {
        return ($this->IncludeFerryDistance = $_includeFerryDistance);
    }
    /**
     * Get Language value
     * @return PCMWSEnumLanguageType|null
     */
    public function getLanguage()
    {
        return $this->Language;
    }
    /**
     * Set Language value
     * @uses PCMWSEnumLanguageType::valueIsValid()
     * @param PCMWSEnumLanguageType $_language the Language
     * @return PCMWSEnumLanguageType
     */
    public function setLanguage($_language)
    {
        if(!PCMWSEnumLanguageType::valueIsValid($_language))
        {
            return false;
        }
        return ($this->Language = $_language);
    }
    /**
     * Get RouteCosts value
     * @return PCMWSStructRouteCosts|null
     */
    public function getRouteCosts()
    {
        return $this->RouteCosts;
    }
    /**
     * Set RouteCosts value
     * @param PCMWSStructRouteCosts $_routeCosts the RouteCosts
     * @return PCMWSStructRouteCosts
     */
    public function setRouteCosts($_routeCosts)
    {
        return ($this->RouteCosts = $_routeCosts);
    }
    /**
     * Get TimeCosts value
     * @return PCMWSStructTimeCosts|null
     */
    public function getTimeCosts()
    {
        return $this->TimeCosts;
    }
    /**
     * Set TimeCosts value
     * @param PCMWSStructTimeCosts $_timeCosts the TimeCosts
     * @return PCMWSStructTimeCosts
     */
    public function setTimeCosts($_timeCosts)
    {
        return ($this->TimeCosts = $_timeCosts);
    }
    /**
     * Get TollCurrency value
     * @return PCMWSEnumCurrency|null
     */
    public function getTollCurrency()
    {
        return $this->TollCurrency;
    }
    /**
     * Set TollCurrency value
     * @uses PCMWSEnumCurrency::valueIsValid()
     * @param PCMWSEnumCurrency $_tollCurrency the TollCurrency
     * @return PCMWSEnumCurrency
     */
    public function setTollCurrency($_tollCurrency)
    {
        if(!PCMWSEnumCurrency::valueIsValid($_tollCurrency))
        {
            return false;
        }
        return ($this->TollCurrency = $_tollCurrency);
    }
    /**
     * Get TollDiscount value
     * @return string|null
     */
    public function getTollDiscount()
    {
        return $this->TollDiscount;
    }
    /**
     * Set TollDiscount value
     * @param string $_tollDiscount the TollDiscount
     * @return string
     */
    public function setTollDiscount($_tollDiscount)
    {
        return ($this->TollDiscount = $_tollDiscount);
    }
    /**
     * Get UseCustomRoadSpeeds value
     * @return boolean|null
     */
    public function getUseCustomRoadSpeeds()
    {
        return $this->UseCustomRoadSpeeds;
    }
    /**
     * Set UseCustomRoadSpeeds value
     * @param boolean $_useCustomRoadSpeeds the UseCustomRoadSpeeds
     * @return boolean
     */
    public function setUseCustomRoadSpeeds($_useCustomRoadSpeeds)
    {
        return ($this->UseCustomRoadSpeeds = $_useCustomRoadSpeeds);
    }
    /**
     * Get UseTollData value
     * @return boolean|null
     */
    public function getUseTollData()
    {
        return $this->UseTollData;
    }
    /**
     * Set UseTollData value
     * @param boolean $_useTollData the UseTollData
     * @return boolean
     */
    public function setUseTollData($_useTollData)
    {
        return ($this->UseTollData = $_useTollData);
    }
    /**
     * Method called when an object has been exported with var_export() functions
     * It allows to return an object instantiated with the values
     * @see PCMWSWsdlClass::__set_state()
     * @uses PCMWSWsdlClass::__set_state()
     * @param array $_array the exported values
     * @return PCMWSStructReportOptions
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
