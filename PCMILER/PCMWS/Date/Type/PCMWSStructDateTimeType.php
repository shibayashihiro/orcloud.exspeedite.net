<?php
/**
 * File for class PCMWSStructDateTimeType
 * @package PCMWS
 * @subpackage Structs
 * @author WsdlToPhp Team <contact@wsdltophp.com>
 * @version 20150429-01
 * @date 2016-08-25
 */
/**
 * This class stands for PCMWSStructDateTimeType originally named DateTimeType
 * Meta informations extracted from the WSDL
 * - from schema : {@link http://pcmiler.alk.com/APIs/SOAP/v1.0/Service.svc?xsd=xsd0}
 * @package PCMWS
 * @subpackage Structs
 * @author WsdlToPhp Team <contact@wsdltophp.com>
 * @version 20150429-01
 * @date 2016-08-25
 */
class PCMWSStructDateTimeType extends PCMWSWsdlClass
{
    /**
     * The CalendarDate
     * Meta informations extracted from the WSDL
     * - minOccurs : 0
     * - nillable : true
     * @var string
     */
    public $CalendarDate;
    /**
     * The DayOfWeek
     * Meta informations extracted from the WSDL
     * - minOccurs : 0
     * @var PCMWSEnumDayOfWeek
     */
    public $DayOfWeek;
    /**
     * The TimeOfDay
     * Meta informations extracted from the WSDL
     * - minOccurs : 0
     * - nillable : true
     * @var string
     */
    public $TimeOfDay;
    /**
     * The TimeZone
     * Meta informations extracted from the WSDL
     * - minOccurs : 0
     * @var PCMWSEnumTimeZone
     */
    public $TimeZone;
    /**
     * Constructor method for DateTimeType
     * @see parent::__construct()
     * @param string $_calendarDate
     * @param PCMWSEnumDayOfWeek $_dayOfWeek
     * @param string $_timeOfDay
     * @param PCMWSEnumTimeZone $_timeZone
     * @return PCMWSStructDateTimeType
     */
    public function __construct($_calendarDate = NULL,$_dayOfWeek = NULL,$_timeOfDay = NULL,$_timeZone = NULL)
    {
        parent::__construct(array('CalendarDate'=>$_calendarDate,'DayOfWeek'=>$_dayOfWeek,'TimeOfDay'=>$_timeOfDay,'TimeZone'=>$_timeZone),false);
    }
    /**
     * Get CalendarDate value
     * @return string|null
     */
    public function getCalendarDate()
    {
        return $this->CalendarDate;
    }
    /**
     * Set CalendarDate value
     * @param string $_calendarDate the CalendarDate
     * @return string
     */
    public function setCalendarDate($_calendarDate)
    {
        return ($this->CalendarDate = $_calendarDate);
    }
    /**
     * Get DayOfWeek value
     * @return PCMWSEnumDayOfWeek|null
     */
    public function getDayOfWeek()
    {
        return $this->DayOfWeek;
    }
    /**
     * Set DayOfWeek value
     * @uses PCMWSEnumDayOfWeek::valueIsValid()
     * @param PCMWSEnumDayOfWeek $_dayOfWeek the DayOfWeek
     * @return PCMWSEnumDayOfWeek
     */
    public function setDayOfWeek($_dayOfWeek)
    {
        if(!PCMWSEnumDayOfWeek::valueIsValid($_dayOfWeek))
        {
            return false;
        }
        return ($this->DayOfWeek = $_dayOfWeek);
    }
    /**
     * Get TimeOfDay value
     * @return string|null
     */
    public function getTimeOfDay()
    {
        return $this->TimeOfDay;
    }
    /**
     * Set TimeOfDay value
     * @param string $_timeOfDay the TimeOfDay
     * @return string
     */
    public function setTimeOfDay($_timeOfDay)
    {
        return ($this->TimeOfDay = $_timeOfDay);
    }
    /**
     * Get TimeZone value
     * @return PCMWSEnumTimeZone|null
     */
    public function getTimeZone()
    {
        return $this->TimeZone;
    }
    /**
     * Set TimeZone value
     * @uses PCMWSEnumTimeZone::valueIsValid()
     * @param PCMWSEnumTimeZone $_timeZone the TimeZone
     * @return PCMWSEnumTimeZone
     */
    public function setTimeZone($_timeZone)
    {
        if(!PCMWSEnumTimeZone::valueIsValid($_timeZone))
        {
            return false;
        }
        return ($this->TimeZone = $_timeZone);
    }
    /**
     * Method called when an object has been exported with var_export() functions
     * It allows to return an object instantiated with the values
     * @see PCMWSWsdlClass::__set_state()
     * @uses PCMWSWsdlClass::__set_state()
     * @param array $_array the exported values
     * @return PCMWSStructDateTimeType
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
