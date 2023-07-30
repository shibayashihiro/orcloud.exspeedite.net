<?php
/**
 * File for class PCMWSStructRoadSpeed
 * @package PCMWS
 * @subpackage Structs
 * @author WsdlToPhp Team <contact@wsdltophp.com>
 * @version 20150429-01
 * @date 2016-08-25
 */
/**
 * This class stands for PCMWSStructRoadSpeed originally named RoadSpeed
 * Meta informations extracted from the WSDL
 * - from schema : {@link http://pcmiler.alk.com/APIs/SOAP/v1.0/Service.svc?xsd=xsd0}
 * @package PCMWS
 * @subpackage Structs
 * @author WsdlToPhp Team <contact@wsdltophp.com>
 * @version 20150429-01
 * @date 2016-08-25
 */
class PCMWSStructRoadSpeed extends PCMWSWsdlClass
{
    /**
     * The RoadCategory
     * Meta informations extracted from the WSDL
     * - minOccurs : 0
     * @var PCMWSEnumRoadType
     */
    public $RoadCategory;
    /**
     * The Speed
     * Meta informations extracted from the WSDL
     * - minOccurs : 0
     * @var int
     */
    public $Speed;
    /**
     * The State
     * Meta informations extracted from the WSDL
     * - minOccurs : 0
     * - nillable : true
     * @var string
     */
    public $State;
    /**
     * Constructor method for RoadSpeed
     * @see parent::__construct()
     * @param PCMWSEnumRoadType $_roadCategory
     * @param int $_speed
     * @param string $_state
     * @return PCMWSStructRoadSpeed
     */
    public function __construct($_roadCategory = NULL,$_speed = NULL,$_state = NULL)
    {
        parent::__construct(array('RoadCategory'=>$_roadCategory,'Speed'=>$_speed,'State'=>$_state),false);
    }
    /**
     * Get RoadCategory value
     * @return PCMWSEnumRoadType|null
     */
    public function getRoadCategory()
    {
        return $this->RoadCategory;
    }
    /**
     * Set RoadCategory value
     * @uses PCMWSEnumRoadType::valueIsValid()
     * @param PCMWSEnumRoadType $_roadCategory the RoadCategory
     * @return PCMWSEnumRoadType
     */
    public function setRoadCategory($_roadCategory)
    {
        if(!PCMWSEnumRoadType::valueIsValid($_roadCategory))
        {
            return false;
        }
        return ($this->RoadCategory = $_roadCategory);
    }
    /**
     * Get Speed value
     * @return int|null
     */
    public function getSpeed()
    {
        return $this->Speed;
    }
    /**
     * Set Speed value
     * @param int $_speed the Speed
     * @return int
     */
    public function setSpeed($_speed)
    {
        return ($this->Speed = $_speed);
    }
    /**
     * Get State value
     * @return string|null
     */
    public function getState()
    {
        return $this->State;
    }
    /**
     * Set State value
     * @param string $_state the State
     * @return string
     */
    public function setState($_state)
    {
        return ($this->State = $_state);
    }
    /**
     * Method called when an object has been exported with var_export() functions
     * It allows to return an object instantiated with the values
     * @see PCMWSWsdlClass::__set_state()
     * @uses PCMWSWsdlClass::__set_state()
     * @param array $_array the exported values
     * @return PCMWSStructRoadSpeed
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
