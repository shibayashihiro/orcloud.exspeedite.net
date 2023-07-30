<?php
/**
 * File for class PCMWSStructCustomPlaceLocation
 * @package PCMWS
 * @subpackage Structs
 * @author WsdlToPhp Team <contact@wsdltophp.com>
 * @version 20150429-01
 * @date 2016-08-25
 */
/**
 * This class stands for PCMWSStructCustomPlaceLocation originally named CustomPlaceLocation
 * Meta informations extracted from the WSDL
 * - from schema : {@link http://pcmiler.alk.com/APIs/SOAP/v1.0/Service.svc?xsd=xsd0}
 * @package PCMWS
 * @subpackage Structs
 * @author WsdlToPhp Team <contact@wsdltophp.com>
 * @version 20150429-01
 * @date 2016-08-25
 */
class PCMWSStructCustomPlaceLocation extends PCMWSWsdlClass
{
    /**
     * The Action
     * Meta informations extracted from the WSDL
     * - minOccurs : 0
     * @var PCMWSEnumActionType
     */
    public $Action;
    /**
     * The Location
     * Meta informations extracted from the WSDL
     * - minOccurs : 0
     * - nillable : true
     * @var PCMWSStructLocation
     */
    public $Location;
    /**
     * Constructor method for CustomPlaceLocation
     * @see parent::__construct()
     * @param PCMWSEnumActionType $_action
     * @param PCMWSStructLocation $_location
     * @return PCMWSStructCustomPlaceLocation
     */
    public function __construct($_action = NULL,$_location = NULL)
    {
        parent::__construct(array('Action'=>$_action,'Location'=>$_location),false);
    }
    /**
     * Get Action value
     * @return PCMWSEnumActionType|null
     */
    public function getAction()
    {
        return $this->Action;
    }
    /**
     * Set Action value
     * @uses PCMWSEnumActionType::valueIsValid()
     * @param PCMWSEnumActionType $_action the Action
     * @return PCMWSEnumActionType
     */
    public function setAction($_action)
    {
        if(!PCMWSEnumActionType::valueIsValid($_action))
        {
            return false;
        }
        return ($this->Action = $_action);
    }
    /**
     * Get Location value
     * @return PCMWSStructLocation|null
     */
    public function getLocation()
    {
        return $this->Location;
    }
    /**
     * Set Location value
     * @param PCMWSStructLocation $_location the Location
     * @return PCMWSStructLocation
     */
    public function setLocation($_location)
    {
        return ($this->Location = $_location);
    }
    /**
     * Method called when an object has been exported with var_export() functions
     * It allows to return an object instantiated with the values
     * @see PCMWSWsdlClass::__set_state()
     * @uses PCMWSWsdlClass::__set_state()
     * @param array $_array the exported values
     * @return PCMWSStructCustomPlaceLocation
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
