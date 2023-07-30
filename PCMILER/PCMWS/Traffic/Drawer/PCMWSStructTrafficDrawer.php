<?php
/**
 * File for class PCMWSStructTrafficDrawer
 * @package PCMWS
 * @subpackage Structs
 * @author WsdlToPhp Team <contact@wsdltophp.com>
 * @version 20150429-01
 * @date 2016-08-25
 */
/**
 * This class stands for PCMWSStructTrafficDrawer originally named TrafficDrawer
 * Meta informations extracted from the WSDL
 * - from schema : {@link http://pcmiler.alk.com/APIs/SOAP/v1.0/Service.svc?xsd=xsd0}
 * @package PCMWS
 * @subpackage Structs
 * @author WsdlToPhp Team <contact@wsdltophp.com>
 * @version 20150429-01
 * @date 2016-08-25
 */
class PCMWSStructTrafficDrawer extends PCMWSWsdlClass
{
    /**
     * The Type
     * Meta informations extracted from the WSDL
     * - minOccurs : 0
     * @var PCMWSEnumTrafficType
     */
    public $Type;
    /**
     * The TimeType
     * Meta informations extracted from the WSDL
     * - minOccurs : 0
     * @var PCMWSEnumTrafficTime
     */
    public $TimeType;
    /**
     * The DateAndTime
     * Meta informations extracted from the WSDL
     * - minOccurs : 0
     * - nillable : true
     * @var PCMWSStructDateTimeType
     */
    public $DateAndTime;
    /**
     * Constructor method for TrafficDrawer
     * @see parent::__construct()
     * @param PCMWSEnumTrafficType $_type
     * @param PCMWSEnumTrafficTime $_timeType
     * @param PCMWSStructDateTimeType $_dateAndTime
     * @return PCMWSStructTrafficDrawer
     */
    public function __construct($_type = NULL,$_timeType = NULL,$_dateAndTime = NULL)
    {
        parent::__construct(array('Type'=>$_type,'TimeType'=>$_timeType,'DateAndTime'=>$_dateAndTime),false);
    }
    /**
     * Get Type value
     * @return PCMWSEnumTrafficType|null
     */
    public function getType()
    {
        return $this->Type;
    }
    /**
     * Set Type value
     * @uses PCMWSEnumTrafficType::valueIsValid()
     * @param PCMWSEnumTrafficType $_type the Type
     * @return PCMWSEnumTrafficType
     */
    public function setType($_type)
    {
        if(!PCMWSEnumTrafficType::valueIsValid($_type))
        {
            return false;
        }
        return ($this->Type = $_type);
    }
    /**
     * Get TimeType value
     * @return PCMWSEnumTrafficTime|null
     */
    public function getTimeType()
    {
        return $this->TimeType;
    }
    /**
     * Set TimeType value
     * @uses PCMWSEnumTrafficTime::valueIsValid()
     * @param PCMWSEnumTrafficTime $_timeType the TimeType
     * @return PCMWSEnumTrafficTime
     */
    public function setTimeType($_timeType)
    {
        if(!PCMWSEnumTrafficTime::valueIsValid($_timeType))
        {
            return false;
        }
        return ($this->TimeType = $_timeType);
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
     * @return PCMWSStructTrafficDrawer
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
