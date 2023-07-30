<?php
/**
 * File for class PCMWSStructEstimatedTimeOptions
 * @package PCMWS
 * @subpackage Structs
 * @author WsdlToPhp Team <contact@wsdltophp.com>
 * @version 20150429-01
 * @date 2016-08-25
 */
/**
 * This class stands for PCMWSStructEstimatedTimeOptions originally named EstimatedTimeOptions
 * Meta informations extracted from the WSDL
 * - from schema : {@link http://pcmiler.alk.com/APIs/SOAP/v1.0/Service.svc?xsd=xsd0}
 * @package PCMWS
 * @subpackage Structs
 * @author WsdlToPhp Team <contact@wsdltophp.com>
 * @version 20150429-01
 * @date 2016-08-25
 */
class PCMWSStructEstimatedTimeOptions extends PCMWSWsdlClass
{
    /**
     * The ETAETD
     * Meta informations extracted from the WSDL
     * - minOccurs : 0
     * @var PCMWSEnumETAETDType
     */
    public $ETAETD;
    /**
     * The DateOption
     * Meta informations extracted from the WSDL
     * - minOccurs : 0
     * @var PCMWSEnumDateOption
     */
    public $DateOption;
    /**
     * The DateAndTime
     * Meta informations extracted from the WSDL
     * - minOccurs : 0
     * - nillable : true
     * @var PCMWSStructDateTimeType
     */
    public $DateAndTime;
    /**
     * Constructor method for EstimatedTimeOptions
     * @see parent::__construct()
     * @param PCMWSEnumETAETDType $_eTAETD
     * @param PCMWSEnumDateOption $_dateOption
     * @param PCMWSStructDateTimeType $_dateAndTime
     * @return PCMWSStructEstimatedTimeOptions
     */
    public function __construct($_eTAETD = NULL,$_dateOption = NULL,$_dateAndTime = NULL)
    {
        parent::__construct(array('ETAETD'=>$_eTAETD,'DateOption'=>$_dateOption,'DateAndTime'=>$_dateAndTime),false);
    }
    /**
     * Get ETAETD value
     * @return PCMWSEnumETAETDType|null
     */
    public function getETAETD()
    {
        return $this->ETAETD;
    }
    /**
     * Set ETAETD value
     * @uses PCMWSEnumETAETDType::valueIsValid()
     * @param PCMWSEnumETAETDType $_eTAETD the ETAETD
     * @return PCMWSEnumETAETDType
     */
    public function setETAETD($_eTAETD)
    {
        if(!PCMWSEnumETAETDType::valueIsValid($_eTAETD))
        {
            return false;
        }
        return ($this->ETAETD = $_eTAETD);
    }
    /**
     * Get DateOption value
     * @return PCMWSEnumDateOption|null
     */
    public function getDateOption()
    {
        return $this->DateOption;
    }
    /**
     * Set DateOption value
     * @uses PCMWSEnumDateOption::valueIsValid()
     * @param PCMWSEnumDateOption $_dateOption the DateOption
     * @return PCMWSEnumDateOption
     */
    public function setDateOption($_dateOption)
    {
        if(!PCMWSEnumDateOption::valueIsValid($_dateOption))
        {
            return false;
        }
        return ($this->DateOption = $_dateOption);
    }
    /**
     * Get DateAndTime value
     * @return PCMWSStructDateTimeType|null
     */
    public function getDateAndTime()
    {
        return $this->DateAndTime;
    }
    /**
     * Set DateAndTime value
     * @param PCMWSStructDateTimeType $_dateAndTime the DateAndTime
     * @return PCMWSStructDateTimeType
     */
    public function setDateAndTime($_dateAndTime)
    {
        return ($this->DateAndTime = $_dateAndTime);
    }
    /**
     * Method called when an object has been exported with var_export() functions
     * It allows to return an object instantiated with the values
     * @see PCMWSWsdlClass::__set_state()
     * @uses PCMWSWsdlClass::__set_state()
     * @param array $_array the exported values
     * @return PCMWSStructEstimatedTimeOptions
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
