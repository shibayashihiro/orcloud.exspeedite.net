<?php
/**
 * File for class PCMWSStructTimeOfDay
 * @package PCMWS
 * @subpackage Structs
 * @author WsdlToPhp Team <contact@wsdltophp.com>
 * @version 20150429-01
 * @date 2016-08-25
 */
/**
 * This class stands for PCMWSStructTimeOfDay originally named TimeOfDay
 * Meta informations extracted from the WSDL
 * - from schema : {@link http://pcmiler.alk.com/APIs/SOAP/v1.0/Service.svc?xsd=xsd0}
 * @package PCMWS
 * @subpackage Structs
 * @author WsdlToPhp Team <contact@wsdltophp.com>
 * @version 20150429-01
 * @date 2016-08-25
 */
class PCMWSStructTimeOfDay extends PCMWSWsdlClass
{
    /**
     * The Hour
     * Meta informations extracted from the WSDL
     * - minOccurs : 0
     * @var int
     */
    public $Hour;
    /**
     * The Minute
     * Meta informations extracted from the WSDL
     * - minOccurs : 0
     * @var int
     */
    public $Minute;
    /**
     * The AmPm
     * Meta informations extracted from the WSDL
     * - minOccurs : 0
     * @var PCMWSEnumAmPmType
     */
    public $AmPm;
    /**
     * Constructor method for TimeOfDay
     * @see parent::__construct()
     * @param int $_hour
     * @param int $_minute
     * @param PCMWSEnumAmPmType $_amPm
     * @return PCMWSStructTimeOfDay
     */
    public function __construct($_hour = NULL,$_minute = NULL,$_amPm = NULL)
    {
        parent::__construct(array('Hour'=>$_hour,'Minute'=>$_minute,'AmPm'=>$_amPm),false);
    }
    /**
     * Get Hour value
     * @return int|null
     */
    public function getHour()
    {
        return $this->Hour;
    }
    /**
     * Set Hour value
     * @param int $_hour the Hour
     * @return int
     */
    public function setHour($_hour)
    {
        return ($this->Hour = $_hour);
    }
    /**
     * Get Minute value
     * @return int|null
     */
    public function getMinute()
    {
        return $this->Minute;
    }
    /**
     * Set Minute value
     * @param int $_minute the Minute
     * @return int
     */
    public function setMinute($_minute)
    {
        return ($this->Minute = $_minute);
    }
    /**
     * Get AmPm value
     * @return PCMWSEnumAmPmType|null
     */
    public function getAmPm()
    {
        return $this->AmPm;
    }
    /**
     * Set AmPm value
     * @uses PCMWSEnumAmPmType::valueIsValid()
     * @param PCMWSEnumAmPmType $_amPm the AmPm
     * @return PCMWSEnumAmPmType
     */
    public function setAmPm($_amPm)
    {
        if(!PCMWSEnumAmPmType::valueIsValid($_amPm))
        {
            return false;
        }
        return ($this->AmPm = $_amPm);
    }
    /**
     * Method called when an object has been exported with var_export() functions
     * It allows to return an object instantiated with the values
     * @see PCMWSWsdlClass::__set_state()
     * @uses PCMWSWsdlClass::__set_state()
     * @param array $_array the exported values
     * @return PCMWSStructTimeOfDay
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
