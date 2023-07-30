<?php
/**
 * File for class PCMWSStructHoursOfServiceOptions
 * @package PCMWS
 * @subpackage Structs
 * @author WsdlToPhp Team <contact@wsdltophp.com>
 * @version 20150429-01
 * @date 2016-08-25
 */
/**
 * This class stands for PCMWSStructHoursOfServiceOptions originally named HoursOfServiceOptions
 * Meta informations extracted from the WSDL
 * - from schema : {@link http://pcmiler.alk.com/APIs/SOAP/v1.0/Service.svc?xsd=xsd0}
 * @package PCMWS
 * @subpackage Structs
 * @author WsdlToPhp Team <contact@wsdltophp.com>
 * @version 20150429-01
 * @date 2016-08-25
 */
class PCMWSStructHoursOfServiceOptions extends PCMWSWsdlClass
{
    /**
     * The Enabled
     * Meta informations extracted from the WSDL
     * - minOccurs : 0
     * @var boolean
     */
    public $Enabled;
    /**
     * The RemainingDriveTimeUntilBreak
     * Meta informations extracted from the WSDL
     * - minOccurs : 0
     * - nillable : true
     * @var double
     */
    public $RemainingDriveTimeUntilBreak;
    /**
     * The RemainingDriveTime
     * Meta informations extracted from the WSDL
     * - minOccurs : 0
     * - nillable : true
     * @var double
     */
    public $RemainingDriveTime;
    /**
     * The RemainingOnDutyTime
     * Meta informations extracted from the WSDL
     * - minOccurs : 0
     * - nillable : true
     * @var double
     */
    public $RemainingOnDutyTime;
    /**
     * The HoSRuleType
     * Meta informations extracted from the WSDL
     * - minOccurs : 0
     * @var PCMWSEnumHoSScheduleType
     */
    public $HoSRuleType;
    /**
     * The RemainingCycleDutyTime
     * Meta informations extracted from the WSDL
     * - minOccurs : 0
     * - nillable : true
     * @var double
     */
    public $RemainingCycleDutyTime;
    /**
     * Constructor method for HoursOfServiceOptions
     * @see parent::__construct()
     * @param boolean $_enabled
     * @param double $_remainingDriveTimeUntilBreak
     * @param double $_remainingDriveTime
     * @param double $_remainingOnDutyTime
     * @param PCMWSEnumHoSScheduleType $_hoSRuleType
     * @param double $_remainingCycleDutyTime
     * @return PCMWSStructHoursOfServiceOptions
     */
    public function __construct($_enabled = NULL,$_remainingDriveTimeUntilBreak = NULL,$_remainingDriveTime = NULL,$_remainingOnDutyTime = NULL,$_hoSRuleType = NULL,$_remainingCycleDutyTime = NULL)
    {
        parent::__construct(array('Enabled'=>$_enabled,'RemainingDriveTimeUntilBreak'=>$_remainingDriveTimeUntilBreak,'RemainingDriveTime'=>$_remainingDriveTime,'RemainingOnDutyTime'=>$_remainingOnDutyTime,'HoSRuleType'=>$_hoSRuleType,'RemainingCycleDutyTime'=>$_remainingCycleDutyTime),false);
    }
    /**
     * Get Enabled value
     * @return boolean|null
     */
    public function getEnabled()
    {
        return $this->Enabled;
    }
    /**
     * Set Enabled value
     * @param boolean $_enabled the Enabled
     * @return boolean
     */
    public function setEnabled($_enabled)
    {
        return ($this->Enabled = $_enabled);
    }
    /**
     * Get RemainingDriveTimeUntilBreak value
     * @return double|null
     */
    public function getRemainingDriveTimeUntilBreak()
    {
        return $this->RemainingDriveTimeUntilBreak;
    }
    /**
     * Set RemainingDriveTimeUntilBreak value
     * @param double $_remainingDriveTimeUntilBreak the RemainingDriveTimeUntilBreak
     * @return double
     */
    public function setRemainingDriveTimeUntilBreak($_remainingDriveTimeUntilBreak)
    {
        return ($this->RemainingDriveTimeUntilBreak = $_remainingDriveTimeUntilBreak);
    }
    /**
     * Get RemainingDriveTime value
     * @return double|null
     */
    public function getRemainingDriveTime()
    {
        return $this->RemainingDriveTime;
    }
    /**
     * Set RemainingDriveTime value
     * @param double $_remainingDriveTime the RemainingDriveTime
     * @return double
     */
    public function setRemainingDriveTime($_remainingDriveTime)
    {
        return ($this->RemainingDriveTime = $_remainingDriveTime);
    }
    /**
     * Get RemainingOnDutyTime value
     * @return double|null
     */
    public function getRemainingOnDutyTime()
    {
        return $this->RemainingOnDutyTime;
    }
    /**
     * Set RemainingOnDutyTime value
     * @param double $_remainingOnDutyTime the RemainingOnDutyTime
     * @return double
     */
    public function setRemainingOnDutyTime($_remainingOnDutyTime)
    {
        return ($this->RemainingOnDutyTime = $_remainingOnDutyTime);
    }
    /**
     * Get HoSRuleType value
     * @return PCMWSEnumHoSScheduleType|null
     */
    public function getHoSRuleType()
    {
        return $this->HoSRuleType;
    }
    /**
     * Set HoSRuleType value
     * @uses PCMWSEnumHoSScheduleType::valueIsValid()
     * @param PCMWSEnumHoSScheduleType $_hoSRuleType the HoSRuleType
     * @return PCMWSEnumHoSScheduleType
     */
    public function setHoSRuleType($_hoSRuleType)
    {
        if(!PCMWSEnumHoSScheduleType::valueIsValid($_hoSRuleType))
        {
            return false;
        }
        return ($this->HoSRuleType = $_hoSRuleType);
    }
    /**
     * Get RemainingCycleDutyTime value
     * @return double|null
     */
    public function getRemainingCycleDutyTime()
    {
        return $this->RemainingCycleDutyTime;
    }
    /**
     * Set RemainingCycleDutyTime value
     * @param double $_remainingCycleDutyTime the RemainingCycleDutyTime
     * @return double
     */
    public function setRemainingCycleDutyTime($_remainingCycleDutyTime)
    {
        return ($this->RemainingCycleDutyTime = $_remainingCycleDutyTime);
    }
    /**
     * Method called when an object has been exported with var_export() functions
     * It allows to return an object instantiated with the values
     * @see PCMWSWsdlClass::__set_state()
     * @uses PCMWSWsdlClass::__set_state()
     * @param array $_array the exported values
     * @return PCMWSStructHoursOfServiceOptions
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
